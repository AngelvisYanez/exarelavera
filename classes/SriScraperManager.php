<?php
/**
 * SriScraperManager - Orquestador de jobs de descarga masiva SRI.
 * Lanza el proceso Python, lee progreso desde progress.json.
 */
class SriScraperManager
{
    private $conexion;
    private $datos;

    function __construct($bdd)
    {
        $this->conexion = new MysqlConexion($bdd);
        $this->datos = new MysqlDatos();
        $this->datos->setConnection($this->conexion);
    }

    /**
     * Crea un job de descarga masiva y lanza el proceso Python.
     */
    public function createJob($params)
    {
        if (empty($params['ruc']) || empty($params['fecha_desde']) || empty($params['fecha_hasta'])) {
            return ['success' => false, 'error' => 'Faltan parametros: ruc, fecha_desde, fecha_hasta'];
        }

        if (!$this->isValidDate($params['fecha_desde']) || !$this->isValidDate($params['fecha_hasta'])) {
            return ['success' => false, 'error' => 'Formato de fecha invalido (usar YYYY-MM-DD)'];
        }

        $jobDir = $this->getScraperDir();
        if (!is_dir($jobDir)) {
            @mkdir($jobDir, 0775, true);
            if (!is_dir($jobDir)) {
                return ['success' => false, 'error' => 'No se pudo crear directorio de trabajo: ' . $jobDir];
            }
        }

        $job = SriScraperJob::create($this->conexion, $this->datos, $params);
        if (!$job) {
            return ['success' => false, 'error' => 'Error al crear el job en la base de datos'];
        }

        $outputDir = $jobDir . DIRECTORY_SEPARATOR . 'downloads' . DIRECTORY_SEPARATOR . $job->id;
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0775, true);
            if (!is_dir($outputDir)) {
                return ['success' => false, 'error' => 'No se pudo crear directorio de salida: ' . $outputDir];
            }
        }
        $job->update(['output_dir' => $outputDir]);

        // Escribir params a archivo JSON (evita problemas de escaping en CLI)
        $paramsFile = $outputDir . DIRECTORY_SEPARATOR . 'params.json';
        file_put_contents($paramsFile, json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $pid = $this->launchPythonProcess($job, $outputDir, $paramsFile);
        if ($pid === false) {
            $job->markFailed('No se pudo iniciar el proceso de scraping');
            return ['success' => false, 'error' => 'Error al lanzar el proceso Python'];
        }

        $job->markRunning($pid);

        return [
            'success' => true,
            'job_id' => (int)$job->id,
            'status' => 'running',
            'message' => 'Job de descarga masiva iniciado',
        ];
    }

    /**
     * Retorna el estado actual de un job, leyendo progress.json.
     */
    public function getJobStatus($jobId)
    {
        $job = SriScraperJob::find($this->conexion, $this->datos, $jobId);
        if (!$job) return null;

        // Si esta running, leer progress.json
        if ($job->status === 'running') {
            $this->syncProgressFromJson($job);
        }

        return $job->toArray();
    }

    /**
     * Cancela un job en ejecucion.
     */
    public function cancelJob($jobId)
    {
        $job = SriScraperJob::find($this->conexion, $this->datos, $jobId);
        if (!$job) return false;

        if ($job->status === 'running' && $job->pid) {
            $this->killProcess($job->pid);
        }

        // Matar todos los python que usen este output_dir
        if ($job->output_dir) {
            $escapedDir = addslashes($job->output_dir);
            exec("taskkill /F /IM python.exe /T 2>NUL");
        }

        $job->markCancelled();
        return true;
    }

    public function listJobs($ruc = '', $page = 1)
    {
        return SriScraperJob::listJobs($this->conexion, $this->datos, $ruc, $page);
    }

    public function getJobFiles($jobId)
    {
        $job = SriScraperJob::find($this->conexion, $this->datos, $jobId);
        if (!$job || !$job->output_dir) return [];

        $outputDir = $job->output_dir;
        $files = ['xml' => [], 'pdf' => []];

        $xmlDir = $outputDir . DIRECTORY_SEPARATOR . 'xml';
        if (is_dir($xmlDir)) {
            foreach (glob($xmlDir . DIRECTORY_SEPARATOR . '*.xml') as $f) {
                $files['xml'][] = [
                    'name' => basename($f),
                    'size' => filesize($f),
                ];
            }
        }

        $pdfDir = $outputDir . DIRECTORY_SEPARATOR . 'pdf';
        if (is_dir($pdfDir)) {
            foreach (glob($pdfDir . DIRECTORY_SEPARATOR . '*.pdf') as $f) {
                $files['pdf'][] = [
                    'name' => basename($f),
                    'size' => filesize($f),
                ];
            }
        }

        $files['total_xml'] = count($files['xml']);
        $files['total_pdf'] = count($files['pdf']);

        return $files;
    }

    public function getJobFilePath($jobId, $clave, $type)
    {
        $job = SriScraperJob::find($this->conexion, $this->datos, $jobId);
        if (!$job || !$job->output_dir) return null;

        $ext = ($type === 'xml') ? 'xml' : 'pdf';
        $filePath = $job->output_dir . DIRECTORY_SEPARATOR . $ext . DIRECTORY_SEPARATOR . $clave . '.' . $ext;

        return file_exists($filePath) ? $filePath : null;
    }

    // ─── Privados ───

    /**
     * Sincroniza el estado del job desde progress.json.
     */
    private function syncProgressFromJson($job)
    {
        if (!$job->output_dir) return;

        $progressFile = $job->output_dir . DIRECTORY_SEPARATOR . 'progress.json';
        if (!file_exists($progressFile)) return;

        $json = file_get_contents($progressFile);
        if (!$json) return;

        $data = json_decode($json, true);
        if (!$data) return;

        $updates = [];

        if (isset($data['progress_msg'])) {
            $updates['progress_msg'] = $data['progress_msg'];
        }
        if (isset($data['total_found'])) {
            $updates['total_found'] = (int)$data['total_found'];
        }
        if (isset($data['xmls_downloaded'])) {
            $updates['xmls_downloaded'] = (int)$data['xmls_downloaded'];
        }
        if (isset($data['pdfs_downloaded'])) {
            $updates['pdfs_downloaded'] = (int)$data['pdfs_downloaded'];
        }

        // Verificar si Python termino
        $fileTime = filemtime($progressFile);
        $isStale = (time() - $fileTime) > 30; // Sin cambios por 30 seg

        if (isset($data['status']) && $data['status'] === 'completed') {
            $job->update($updates);
            $job->markCompleted([
                'total' => $data['total_found'] ?? $job->total_found,
                'xmls' => $data['xmls_downloaded'] ?? $job->xmls_downloaded,
                'pdfs' => $data['pdfs_downloaded'] ?? $job->pdfs_downloaded,
            ]);
            return;
        }

        if (isset($data['status']) && $data['status'] === 'failed') {
            $job->update($updates);
            $job->markFailed($data['error'] ?? 'Error desconocido');
            return;
        }

        // Verificar si el proceso Python sigue vivo
        if ($job->pid && !$this->isProcessAlive($job->pid)) {
            // Proceso murio sin progress.json de completed
            if (!empty($updates)) {
                $job->update($updates);
            }
            if ($job->total_found > 0) {
                $job->markCompleted([
                    'total' => $job->total_found,
                    'xmls' => $job->xmls_downloaded,
                    'pdfs' => $job->pdfs_downloaded,
                ]);
            } else {
                $job->markFailed('El proceso Python termino inesperadamente');
            }
            return;
        }

        // Proceso sigue vivo, actualizar progreso
        if (!empty($updates)) {
            $job->update($updates);
        }
    }

    /**
     * Lanza el proceso Python con params-file.
     */
    private function launchPythonProcess($job, $outputDir, $paramsFile)
    {
        $scraperScript = $this->getScraperScript();
        if (!file_exists($scraperScript)) {
            error_log("['SriScraper'] Script no encontrado: $scraperScript");
            return false;
        }

        $pythonPath = $this->getPythonPath();

        $cmd = sprintf(
            '%s %s --params-file=%s --output-dir=%s',
            escapeshellcmd($pythonPath),
            escapeshellarg($scraperScript),
            escapeshellarg($paramsFile),
            escapeshellarg($outputDir)
        );

        if (PHP_OS_FAMILY === 'Windows') {
            $logFile = $outputDir . DIRECTORY_SEPARATOR . 'stderr.log';
            $fullCmd = 'start /B cmd /c "' . $cmd . ' > ' . escapeshellarg($logFile) . ' 2>&1"';
            pclose(popen($fullCmd, "r"));
        } else {
            $fullCmd = $cmd . ' > /dev/null 2>&1 &';
            exec($fullCmd);
        }

        // Esperar un momento a que el proceso inicie y escriba progress.json
        usleep(500000); // 500ms

        // Leer PID del proceso python recien creado
        $pid = $this->findPythonPid($outputDir);

        return $pid ?: 0;
    }

    /**
     * Busca el PID del proceso Python que esta usando este output_dir.
     */
    private function findPythonPid($outputDir)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            exec('powershell -NoProfile -Command "Get-CimInstance Win32_Process -Filter \"Name=\'python.exe\'\" | Select-Object ProcessId, CommandLine | ConvertTo-Csv -NoTypeInformation 2>NUL"', $output);
            foreach ($output as $line) {
                if (strpos($line, $outputDir) !== false) {
                    $parts = str_getcsv($line);
                    if (isset($parts[0]) && is_numeric($parts[0])) {
                        return (int)$parts[0];
                    }
                }
            }
            // Fallback: buscar el ultimo python.exe
            exec('tasklist /FI "IMAGENAME eq python.exe" /FO CSV 2>NUL', $output);
            foreach ($output as $line) {
                if (preg_match('/"python\.exe","(\d+)"/', $line, $m)) {
                    return (int)$m[1];
                }
            }
        }
        return 0;
    }

    private function isProcessAlive($pid)
    {
        if (!$pid) return false;
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            exec("tasklist /FI \"PID eq $pid\" /FO CSV 2>NUL", $output);
            foreach ($output as $line) {
                if (strpos($line, (string)$pid) !== false) return true;
            }
            return false;
        }
        return posix_kill($pid, 0);
    }

    private function killProcess($pid)
    {
        if (!$pid) return;
        if (PHP_OS_FAMILY === 'Windows') {
            exec("taskkill /PID $pid /F /T 2>NUL");
        } else {
            posix_kill($pid, SIGTERM);
            sleep(1);
            if ($this->isProcessAlive($pid)) posix_kill($pid, SIGKILL);
        }
    }

    private function getScraperScript()
    {
        $base = realpath(__DIR__ . '/../scrapers');
        return $base . DIRECTORY_SEPARATOR . 'sri_scraper.py';
    }

    private function getPythonPath()
    {
        // Windows: buscar python.exe
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            exec('where python 2>NUL', $output, $code);
            if ($code === 0 && !empty($output)) {
                return trim($output[0]);
            }
        }
        // Linux
        foreach (['/usr/bin/python3', '/usr/local/bin/python3', 'python3'] as $p) {
            $output = [];
            exec(escapeshellcmd($p) . ' --version 2>&1', $output, $code);
            if ($code === 0) return $p;
        }
        return 'python3';
    }

    private function getScraperDir()
    {
        return realpath(__DIR__ . '/../scrapers');
    }

    private function isValidDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
