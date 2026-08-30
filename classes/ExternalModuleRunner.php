<?php
/**
 * ExternalModuleRunner - Servicio para ejecutar módulos externos (Node.js, Python, Shell)
 * 
 * Gestiona el ciclo de vida de módulos externos:
 * - Iniciar/detener servicios
 * - Ejecutar scripts individuales
 * - Monitorear estado y logs
 * - Gestión de configuración
 */

class ExternalModuleRunner
{
    private $api;
    private $logDir;
    private $pidDir;
    private $configDir;

    public function __construct($bdd)
    {
        $this->api = new DataAPI($bdd);
        $this->logDir = dirname(__DIR__) . '/logs/external_modules/';
        $this->pidDir = dirname(__DIR__) . '/logs/external_modules/pids/';
        $this->configDir = dirname(__DIR__) . '/config/external_modules/';
        
        $this->ensureDirectories();
    }

    private function ensureDirectories()
    {
        foreach ([$this->logDir, $this->pidDir, $this->configDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Obtener información de un módulo externo
     */
    public function getModuleInfo($dirCod)
    {
        $module = $this->api->getById('directorio_modulos', 'Dir_Cod', $dirCod);
        if (!$module) {
            throw new \Exception("Módulo no encontrado: $dirCod");
        }
        
        // Obtener configuración del módulo
        $config = $this->getModuleConfig($dirCod, $module['Emp_Cod']);
        $module['config'] = $config;
        
        // Verificar estado real del proceso
        if ($module['Dir_Ext_Pid']) {
            $module['Dir_Ext_Status'] = $this->checkProcessStatus($module['Dir_Ext_Pid']);
        }
        
        return $module;
    }

    /**
     * Listar todos los módulos externos de una empresa
     */
    public function listExternalModules($empCod = null)
    {
        $where = [
            'Dir_Est' => 'A',
            'Dir_Tip' => 'externo',
        ];
        
        if ($empCod) {
            $where['Emp_Cod'] = $empCod;
        }
        
        $modules = $this->api->list('directorio_modulos', $where, 'Dir_Cod ASC', 500);
        
        foreach ($modules as &$module) {
            if ($module['Dir_Ext_Pid']) {
                $module['Dir_Ext_Status'] = $this->checkProcessStatus($module['Dir_Ext_Pid']);
            }
            $module['config'] = $this->getModuleConfig($module['Dir_Cod'], $empCod);
        }
        
        return $modules;
    }

    /**
     * Iniciar un servicio externo (para módulos que corren como daemon)
     */
    public function startService($dirCod, $empCod = null)
    {
        $module = $this->getModuleInfo($dirCod);
        
        if ($module['Dir_Ext_Status'] === 'running') {
            throw new \Exception("El servicio ya está en ejecución");
        }
        
        $cmd = $this->buildStartCommand($module);
        $logFile = $this->logDir . "module_{$dirCod}_" . date('Y-m-d_His') . ".log";
        
        // Ejecutar comando en background
        $pid = $this->executeBackground($cmd, $logFile, $module['Dir_Ext_Cwd']);
        
        if ($pid) {
            // Actualizar estado en BD
            $this->api->update('directorio_modulos', [
                'Dir_Ext_Status' => 'running',
                'Dir_Ext_Pid' => $pid,
                'Dir_Ext_Last_Run' => date('Y-m-d H:i:s'),
            ], 'Dir_Cod', $dirCod);
            
            // Guardar PID en archivo
            file_put_contents($this->pidDir . "module_{$dirCod}.pid", $pid);
            
            // Registrar en log
            $this->logExecution($dirCod, $empCod, $module, 'started');
            
            return [
                'status' => true,
                'pid' => $pid,
                'message' => "Servicio iniciado correctamente",
                'log_file' => $logFile,
            ];
        }
        
        throw new \Exception("No se pudo iniciar el servicio");
    }

    /**
     * Detener un servicio externo
     */
    public function stopService($dirCod)
    {
        $module = $this->getModuleInfo($dirCod);
        
        if ($module['Dir_Ext_Status'] !== 'running' || !$module['Dir_Ext_Pid']) {
            throw new \Exception("El servicio no está en ejecución");
        }
        
        $pid = $module['Dir_Ext_Pid'];
        
        // Intentar detener el proceso
        $this->killProcess($pid);
        
        // Actualizar estado
        $this->api->update('directorio_modulos', [
            'Dir_Ext_Status' => 'stopped',
            'Dir_Ext_Pid' => null,
        ], 'Dir_Cod', $dirCod);
        
        // Limpiar archivo PID
        $pidFile = $this->pidDir . "module_{$dirCod}.pid";
        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
        
        $this->logExecution($dirCod, $module['Emp_Cod'], $module, 'stopped');
        
        return [
            'status' => true,
            'message' => "Servicio detenido correctamente",
        ];
    }

    /**
     * Ejecutar una tarea individual (sin daemon)
     */
    public function executeTask($dirCod, $params = [], $empCod = null)
    {
        $module = $this->getModuleInfo($dirCod);
        
        $cmd = $this->buildExecuteCommand($module, $params);
        $logFile = $this->logDir . "task_{$dirCod}_" . date('Y-m-d_His') . ".log";
        
        $startTime = time();
        
        // Ejecutar comando
        $output = [];
        $returnCode = 0;
        exec($cmd . " 2>&1", $output, $returnCode);
        
        $duration = time() - $startTime;
        $outputStr = implode("\n", $output);
        
        // Registrar ejecución
        $logId = $this->logExecution($dirCod, $empCod, $module, 
            $returnCode === 0 ? 'completed' : 'failed',
            $duration, $outputStr, json_encode($params)
        );
        
        return [
            'status' => $returnCode === 0,
            'duration' => $duration,
            'output' => $outputStr,
            'return_code' => $returnCode,
            'log_id' => $logId,
        ];
    }

    /**
     * Construir comando de inicio para un servicio
     */
    private function buildStartCommand($module)
    {
        $cmd = $module['Dir_Ext_Cmd'];
        $args = json_decode($module['Dir_Ext_Args'] ?? '[]', true);
        
        // Reemplazar variables en argumentos
        $args = $this->replacePlaceholders($args);
        
        return $cmd . ' ' . implode(' ', array_map('escapeshellarg', $args));
    }

    /**
     * Construir comando de ejecución con parámetros
     */
    private function buildExecuteCommand($module, $params)
    {
        $cmd = $module['Dir_Ext_Cmd'];
        $args = json_decode($module['Dir_Ext_Args'] ?? '[]', true);
        
        // Reemplazar placeholders con parámetros
        foreach ($params as $key => $value) {
            $args = str_replace("{{$key}}", $value, $args);
        }
        
        // Reemplazar placeholders de sistema
        $args = $this->replacePlaceholders($args);
        
        return $cmd . ' ' . implode(' ', array_map('escapeshellarg', $args));
    }

    /**
     * Reemplazar placeholders en argumentos
     */
    private function replacePlaceholders($args)
    {
        $replacements = [
            '{timestamp}' => time(),
            '{date}' => date('Y-m-d'),
            '{datetime}' => date('Y-m-d H:i:s'),
            '{tmp_dir}' => sys_get_temp_dir(),
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $args);
    }

    /**
     * Ejecutar comando en background y retornar PID
     */
    private function executeBackground($cmd, $logFile, $cwd = null)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['file', $logFile, 'w'],
                2 => ['file', $logFile, 'a'],
            ];
            
            $workingDir = ($cwd && is_dir($cwd)) ? $cwd : null;
            $process = proc_open('cmd /C ' . $cmd, $descriptors, $pipes, $workingDir);
            
            if (is_resource($process)) {
                if (isset($pipes[0]) && is_resource($pipes[0])) fclose($pipes[0]);
                $status = proc_get_status($process);
                return $status['pid'];
            }
            
            return null;
        }
        
        $cwdArg = $cwd ? "cd " . escapeshellarg($cwd) . " && " : "";
        $fullCmd = $cwdArg . $cmd . " > " . escapeshellarg($logFile) . " 2>&1 & echo $!";
        
        $pid = trim(shell_exec($fullCmd));
        
        if ($pid && is_numeric($pid)) {
            return (int)$pid;
        }
        
        return null;
    }

    /**
     * Verificar si un proceso está corriendo
     */
    private function checkProcessStatus($pid)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // En Windows, usar tasklist
            $output = shell_exec("tasklist /FI \"PID eq $pid\" 2>NUL");
            return (strpos($output, "$pid") !== false) ? 'running' : 'stopped';
        } else {
            // En Linux/Mac, usar kill -0
            exec("kill -0 $pid 2>/dev/null", $output, $returnCode);
            return ($returnCode === 0) ? 'running' : 'stopped';
        }
    }

    /**
     * Matar un proceso
     */
    private function killProcess($pid)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            shell_exec("taskkill /PID $pid /F /T 2>NUL");
        } else {
            exec("kill -TERM $pid 2>/dev/null");
            sleep(1);
            exec("kill -KILL $pid 2>/dev/null");
        }
    }

    /**
     * Registrar ejecución en log
     */
    private function logExecution($dirCod, $empCod, $module, $estado, $duracion = null, $salida = null, $parametros = null)
    {
        $data = [
            'Dir_Cod' => $dirCod,
            'Log_Fecha_Inicio' => date('Y-m-d H:i:s'),
            'Log_Estado' => $estado,
            'Log_Duracion_Seg' => $duracion,
            'Log_Salida_Stdout' => $salida,
            'Log_Parametros' => $parametros,
        ];
        
        if ($estado === 'completed' || $estado === 'failed') {
            $data['Log_Fecha_Fin'] = date('Y-m-d H:i:s');
        }
        
        if ($empCod) {
            $data['Log_Creado_Por'] = $empCod;
        }
        
        return $this->api->insert('directorio_modulos_log', $data);
    }

    /**
     * Obtener configuración de un módulo
     */
    public function getModuleConfig($dirCod, $empCod)
    {
        return $this->api->list('directorio_modulos_config', [
            'Dir_Cod' => $dirCod,
            'Emp_Cod' => $empCod,
        ]);
    }

    /**
     * Guardar configuración de un módulo
     */
    public function saveModuleConfig($dirCod, $empCod, $clave, $valor, $tipo = 'string', $descripcion = null)
    {
        // Verificar si ya existe
        $existing = $this->api->list('directorio_modulos_config', [
            'Dir_Cod' => $dirCod,
            'Emp_Cod' => $empCod,
            'Cfg_Clave' => $clave,
        ]);
        
        if (!empty($existing)) {
            // Actualizar
            $this->api->update('directorio_modulos_config', [
                'Cfg_Valor' => $valor,
                'Cfg_Tipo' => $tipo,
                'Cfg_Descripcion' => $descripcion,
            ], 'Cfg_Cod', $existing[0]['Cfg_Cod']);
        } else {
            // Insertar
            $this->api->insert('directorio_modulos_config', [
                'Dir_Cod' => $dirCod,
                'Emp_Cod' => $empCod,
                'Cfg_Clave' => $clave,
                'Cfg_Valor' => $valor,
                'Cfg_Tipo' => $tipo,
                'Cfg_Descripcion' => $descripcion,
            ]);
        }
        
        return true;
    }

    /**
     * Obtener logs de ejecución de un módulo
     */
    public function getExecutionLogs($dirCod, $limit = 50, $offset = 0)
    {
        $logs = $this->api->list('directorio_modulos_log', [
            'Dir_Cod' => $dirCod,
        ], 'Log_Fecha_Inicio DESC', $limit, $offset);
        
        return $logs;
    }

    /**
     * Obtener logs de archivos del sistema
     */
    public function getLogFile($dirCod, $filename = null)
    {
        if ($filename) {
            $logFile = $this->logDir . $filename;
        } else {
            // Obtener el último log del módulo
            $logs = glob($this->logDir . "*_{$dirCod}_*.log");
            if (empty($logs)) {
                return null;
            }
            rsort($logs);
            $logFile = $logs[0];
        }
        
        if (!file_exists($logFile)) {
            return null;
        }
        
        return [
            'filename' => basename($logFile),
            'content' => file_get_contents($logFile),
            'size' => filesize($logFile),
            'modified' => filemtime($logFile),
        ];
    }

    /**
     * Listar todos los logs disponibles para un módulo
     */
    public function listLogFiles($dirCod)
    {
        $pattern = $this->logDir . "*_{$dirCod}_*.log";
        $files = glob($pattern);
        
        $result = [];
        foreach ($files as $file) {
            $result[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file),
            ];
        }
        
        usort($result, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $result;
    }

    /**
     * Verificar dependencias del sistema para módulos externos
     */
    public function checkSystemDependencies()
    {
        $deps = [
            'node' => [
                'name' => 'Node.js',
                'command' => 'node --version',
                'installed' => false,
                'version' => null,
            ],
            'npm' => [
                'name' => 'npm',
                'command' => 'npm --version',
                'installed' => false,
                'version' => null,
            ],
            'python' => [
                'name' => 'Python',
                'command' => 'python --version',
                'installed' => false,
                'version' => null,
            ],
            'python3' => [
                'name' => 'Python 3',
                'command' => 'python3 --version',
                'installed' => false,
                'version' => null,
            ],
            'pip' => [
                'name' => 'pip',
                'command' => 'pip --version',
                'installed' => false,
                'version' => null,
            ],
            'docker' => [
                'name' => 'Docker',
                'command' => 'docker --version',
                'installed' => false,
                'version' => null,
            ],
        ];
        
        foreach ($deps as $key => &$dep) {
            $output = [];
            $returnCode = 0;
            exec($dep['command'] . ' 2>&1', $output, $returnCode);
            
            if ($returnCode === 0 && !empty($output)) {
                $dep['installed'] = true;
                $dep['version'] = trim(implode("\n", $output));
            }
        }
        
        return $deps;
    }
}
