<?php
/**
 * SriScraperJob - Modelo de jobs de descarga masiva SRI.
 * Tabla: sri_scraper_jobs
 */
class SriScraperJob
{
    public $id;
    public $ruc;
    public $fecha_desde;
    public $fecha_hasta;
    public $tipo_comprobante;
    public $flow;
    public $status;
    public $progress_msg;
    public $total_found;
    public $xmls_downloaded;
    public $pdfs_downloaded;
    public $output_dir;
    public $pid;
    public $started_at;
    public $completed_at;
    public $error;
    public $created_by;
    public $created_at;
    public $updated_at;

    private $conexion;
    private $datos;

    function __construct($conexion, $datos)
    {
        $this->conexion = $conexion;
        $this->datos = $datos;
    }

    /**
     * Crea un nuevo job de descarga masiva.
     */
    public static function create($conexion, $datos, $params)
    {
        $job = new self($conexion, $datos);
        $job->ruc = $params['ruc'];
        $job->fecha_desde = $params['fecha_desde'];
        $job->fecha_hasta = $params['fecha_hasta'];
        $job->tipo_comprobante = isset($params['tipo_comprobante']) ? $params['tipo_comprobante'] : 'todos';
        $job->flow = isset($params['flow']) ? $params['flow'] : 'recibidos';
        $job->status = 'pending';
        $job->progress_msg = 'Job creado, esperando inicio...';
        $job->total_found = 0;
        $job->xmls_downloaded = 0;
        $job->pdfs_downloaded = 0;
        $job->created_by = isset($params['Emp_Cod']) ? $params['Emp_Cod'] : '';

        $mysqli = $datos->getMyCon($conexion);
        $ruc = $mysqli->real_escape_string($job->ruc);
        $fd = $mysqli->real_escape_string($job->fecha_desde);
        $fh = $mysqli->real_escape_string($job->fecha_hasta);
        $tc = $mysqli->real_escape_string($job->tipo_comprobante);
        $fl = $mysqli->real_escape_string($job->flow);
        $cb = $mysqli->real_escape_string($job->created_by);

        $sql = "INSERT INTO sri_scraper_jobs 
                (ruc, fecha_desde, fecha_hasta, tipo_comprobante, flow, status, 
                 progress_msg, created_by)
                VALUES ('$ruc', '$fd', '$fh', '$tc', '$fl', 'pending',
                 'Job creado, esperando inicio...', '$cb')";

        $datos->consulta($sql, $conexion);
        if ($datos->Error == 0) {
            $job->id = $datos->insercionid($conexion);
            return $job;
        }
        return null;
    }

    /**
     * Busca un job por ID.
     */
    public static function find($conexion, $datos, $id)
    {
        $id = (int)$id;
        $sql = "SELECT * FROM sri_scraper_jobs WHERE id = $id LIMIT 1";
        $row = $datos->getRowConsultaSql($sql, $conexion);
        if (!$row) return null;

        $job = new self($conexion, $datos);
        $job->fillFromRow($row);
        return $job;
    }

    /**
     * Lista jobs con filtros y paginación.
     */
    public static function listJobs($conexion, $datos, $ruc = '', $page = 1, $rows = 20)
    {
        $where = "WHERE 1=1";
        if (!empty($ruc)) {
            $rucEsc = $conexion->conexion->real_escape_string($ruc);
            $where .= " AND ruc = '$rucEsc'";
        }

        $countSql = "SELECT COUNT(*) AS total FROM sri_scraper_jobs $where";
        $countRow = $datos->getRowConsultaSql($countSql, $conexion);
        $total = $countRow ? (int)$countRow['total'] : 0;

        $start = ($page - 1) * $rows;
        $sql = "SELECT * FROM sri_scraper_jobs $where ORDER BY created_at DESC LIMIT $start, $rows";
        $rows_data = $datos->getArrayConsultaSql($sql, $conexion);

        return [
            'rows' => $rows_data,
            'page' => (int)$page,
            'total' => ceil($total / $rows),
            'records' => $total,
        ];
    }

    /**
     * Actualiza campos del job.
     */
    public function update($data)
    {
        $mysqli = $this->datos->getMyCon($this->conexion);
        $sets = [];
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $escaped = $mysqli->real_escape_string((string)$v);
                $sets[] = "`$k` = '$escaped'";
            }
        }
        if (empty($sets)) return false;

        $id = (int)$this->id;
        $sql = "UPDATE sri_scraper_jobs SET " . implode(', ', $sets) . " WHERE id = $id";
        $this->datos->consulta($sql, $this->conexion);

        // Refrescar propiedades locales
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
        return $this->datos->Error == 0;
    }

    /**
     * Marca el job como en ejecucion.
     */
    public function markRunning($pid)
    {
        $this->update([
            'status' => 'running',
            'pid' => $pid,
            'started_at' => date('Y-m-d H:i:s'),
            'progress_msg' => 'Proceso iniciado...',
        ]);
    }

    /**
     * Marca el job como completado.
     */
    public function markCompleted($stats = [])
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'total_found' => isset($stats['total']) ? $stats['total'] : $this->total_found,
            'xmls_downloaded' => isset($stats['xmls']) ? $stats['xmls'] : $this->xmls_downloaded,
            'pdfs_downloaded' => isset($stats['pdfs']) ? $stats['pdfs'] : $this->pdfs_downloaded,
            'progress_msg' => 'Completado',
        ]);
    }

    /**
     * Marca el job como fallido.
     */
    public function markFailed($error)
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => date('Y-m-d H:i:s'),
            'error' => $error,
            'progress_msg' => 'Error: ' . $error,
        ]);
    }

    /**
     * Marca el job como cancelado.
     */
    public function markCancelled()
    {
        $this->update([
            'status' => 'cancelled',
            'completed_at' => date('Y-m-d H:i:s'),
            'progress_msg' => 'Cancelado por el usuario',
        ]);
    }

    /**
     * Retorna el array con el estado del job para la API.
     */
    public function toArray()
    {
        return [
            'id' => (int)$this->id,
            'ruc' => $this->ruc,
            'fecha_desde' => $this->fecha_desde,
            'fecha_hasta' => $this->fecha_hasta,
            'tipo_comprobante' => $this->tipo_comprobante,
            'flow' => $this->flow,
            'status' => $this->status,
            'progress_msg' => $this->progress_msg,
            'total_found' => (int)$this->total_found,
            'xmls_downloaded' => (int)$this->xmls_downloaded,
            'pdfs_downloaded' => (int)$this->pdfs_downloaded,
            'output_dir' => $this->output_dir,
            'pid' => $this->pid ? (int)$this->pid : null,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'error' => $this->error,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }

    private function fillFromRow($row)
    {
        foreach ($row as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
}
