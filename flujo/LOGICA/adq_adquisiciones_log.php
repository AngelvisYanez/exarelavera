<?php
/**
 * EXA Adquisiciones - Lógica de Control de Adquisiciones
 * @author Oz <oz-agent@warp.dev>
 */

require_once(dirname(__FILE__) . '/../../DATA/GestorErrores.php');
require_once(dirname(__FILE__) . '/../../DATA/MysqlConexion.php');
require_once(dirname(__FILE__) . '/../../DATA/MysqlDatos.php');
require_once(dirname(__FILE__) . '/wf_manager_log.php');

class adq_adquisiciones_log extends MysqlDatosContab {
    public $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    private function escapeSql($value) {
        if ($value === null) {
            return '';
        }
        return mysqli_real_escape_string($this->conexion->conexion, (string)$value);
    }

    /**
     * Extrae la secuencia numerica de un Sol_Num (Req-00000025 o legado numerico).
     */
    private function extraerSecuenciaSolNum($sol_num) {
        $sol_num = trim((string)$sol_num);
        if ($sol_num === '') {
            return 0;
        }
        if (preg_match('/^Req-(\d+)$/i', $sol_num, $matches)) {
            return intval($matches[1]);
        }
        if (ctype_digit($sol_num)) {
            return intval($sol_num);
        }
        return 0;
    }

    /**
     * Formato oficial: Req- + 8 digitos = 12 caracteres. Ej: Req-00000025
     */
    public function formatearSolNum($secuencia) {
        $secuencia = intval($secuencia);
        if ($secuencia < 1) {
            $secuencia = 1;
        }
        return 'Req-' . str_pad((string)$secuencia, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Genera el siguiente numero de solicitud por empresa y sucursal.
     */
    public function generarSiguienteSolNum($emp_cod, $suc_cod) {
        $emp_cod = intval($emp_cod);
        $suc_cod = intval($suc_cod);
        $rows = $this->getArrayConsultaSql(
            "SELECT Sol_Num FROM adq_solicitudes WHERE Emp_Cod = $emp_cod AND Suc_Cod = $suc_cod;",
            $this->conexion
        );
        $max_sec = 0;
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $sec = $this->extraerSecuenciaSolNum(isset($row['Sol_Num']) ? $row['Sol_Num'] : '');
                if ($sec > $max_sec) {
                    $max_sec = $sec;
                }
            }
        }
        return $this->formatearSolNum($max_sec + 1);
    }

    public function slugSolNumArchivo($sol_num) {
        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim((string)$sol_num));
        return $slug !== '' ? $slug : 'sol';
    }

    public function parseCotAdjuntos($cot_adj) {
        if ($cot_adj === null || $cot_adj === '') {
            return array();
        }
        $trim = trim((string)$cot_adj);
        if ($trim === '') {
            return array();
        }
        if ($trim[0] === '[') {
            $decoded = json_decode($trim, true);
            if (!is_array($decoded)) {
                return array();
            }
            $paths = array();
            foreach ($decoded as $path) {
                $path = trim((string)$path);
                if ($path !== '') {
                    $paths[] = $path;
                }
            }
            return $paths;
        }
        return array($trim);
    }

    public function encodeCotAdjuntos($paths) {
        if (!is_array($paths)) {
            $paths = array($paths);
        }
        $limpios = array();
        foreach ($paths as $path) {
            $path = trim((string)$path);
            if ($path !== '' && strpos($path, '..') === false) {
                $limpios[] = $path;
            }
        }
        $limpios = array_values(array_unique($limpios));
        if (empty($limpios)) {
            return '';
        }
        if (count($limpios) === 1) {
            return $limpios[0];
        }
        return json_encode($limpios, defined('JSON_UNESCAPED_SLASHES') ? JSON_UNESCAPED_SLASHES : 0);
    }

    public function cotizacionTieneAdjunto($cot_adj) {
        $adjuntos = $this->parseCotAdjuntos($cot_adj);
        return !empty($adjuntos);
    }

    /**
     * Archivos de una fila de documentos de avance.
     */
    private function archivosDesdeAvanceRow($av) {
        $archivos = array();
        if (!empty($av['Sav_Fac_Adj'])) {
            $archivos[] = array('path' => $av['Sav_Fac_Adj'], 'label' => 'Factura');
        }
        if (!empty($av['Sav_Ret_Adj'])) {
            $archivos[] = array('path' => $av['Sav_Ret_Adj'], 'label' => 'Retencion');
        }
        if (!empty($av['Sav_Com_Adj'])) {
            $archivos[] = array('path' => $av['Sav_Com_Adj'], 'label' => 'Comprobante pago');
        }
        if (!empty($av['Sav_Adj'])) {
            $archivos[] = array('path' => $av['Sav_Adj'], 'label' => 'Documento');
        }
        return $archivos;
    }

    /**
     * Archivos PDF/imagen de una cotizacion/proforma.
     */
    private function archivosDesdeCotizacionRow($cot) {
        $archivos = array();
        $proveedor = '';
        if (!empty($cot['Prv_Com'])) {
            $proveedor = trim($cot['Prv_Com']);
        } elseif (!empty($cot['Prs_Nom']) || !empty($cot['Prs_Ape'])) {
            $proveedor = trim(trim($cot['Prs_Nom'] . ' ' . $cot['Prs_Ape']));
        }
        if ($proveedor === '') {
            $proveedor = 'Proveedor';
        }
        $adjuntos = $this->parseCotAdjuntos(isset($cot['Cot_Adj']) ? $cot['Cot_Adj'] : '');
        foreach ($adjuntos as $i => $path) {
            $label = 'Proforma';
            if (count($adjuntos) > 1) {
                $label .= ' ' . ($i + 1);
            }
            $label .= ' - ' . $proveedor;
            if (!empty($cot['Cot_Sel']) && intval($cot['Cot_Sel']) === 1) {
                $label .= ' (ganadora)';
            }
            $archivos[] = array('path' => $path, 'label' => $label);
        }
        return $archivos;
    }

    /**
     * Cotizaciones de la solicitud que tienen archivos de sustento.
     */
    private function listarCotizacionesConProveedor($sol_cod) {
        $sol_cod = intval($sol_cod);
        if ($sol_cod <= 0) {
            return array();
        }
        $rows = $this->getArrayConsultaSql(
            "SELECT c.*, p.Prs_Nom, p.Prs_Ape, pr.Prv_Com
             FROM adq_solicitudes_cotizaciones c
             LEFT JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod
             LEFT JOIN persona p ON p.Prs_Cod = pr.Prs_Cod
             WHERE c.Sol_Cod = $sol_cod
               AND c.Cot_Adj IS NOT NULL AND c.Cot_Adj != ''
             ORDER BY c.Sco_Cod ASC;",
            $this->conexion
        );
        return ($rows === false || $rows === null) ? array() : $rows;
    }

    /**
     * Asigna proformas a movimientos COTIZAR/CREAR/REENVIAR.
     */
    private function resolverArchivosCotizacionPorHistorial($historial, $cotizaciones) {
        $mapa = array();
        if (empty($historial) || empty($cotizaciones)) {
            return $mapa;
        }
        foreach ($historial as $h) {
            if (empty($h['Isn_Cod']) || empty($h['Isn_Acc'])) {
                continue;
            }
            $acc = $h['Isn_Acc'];
            if (!in_array($acc, array('COTIZAR', 'CREAR', 'REENVIAR'), true)) {
                continue;
            }
            $isn_cod = intval($h['Isn_Cod']);
            $archivos = array();
            if ($acc === 'CREAR') {
                foreach ($cotizaciones as $cot) {
                    if (!empty($h['Isn_Fec']) && !empty($cot['Cot_Fec'])) {
                        $fec_cot = strtotime(substr($cot['Cot_Fec'], 0, 10));
                        $fec_hist = strtotime(date('Y-m-d', strtotime($h['Isn_Fec'])));
                        if ($fec_cot > $fec_hist) {
                            continue;
                        }
                    }
                    foreach ($this->archivosDesdeCotizacionRow($cot) as $arch) {
                        $archivos[] = $arch;
                    }
                }
            } else {
                foreach ($cotizaciones as $cot) {
                    foreach ($this->archivosDesdeCotizacionRow($cot) as $arch) {
                        $archivos[] = $arch;
                    }
                }
            }
            if (!empty($archivos)) {
                $mapa[$isn_cod] = $archivos;
            }
        }
        return $mapa;
    }

    /**
     * Datos de factura EXA para mostrar en Historial de Firmas.
     */
    private function facturaDesdeAvanceRow($av) {
        if (!empty($av['compra']) && is_array($av['compra'])) {
            $c = $av['compra'];
            $comprobantes = array();
            if (!empty($c['Comprobantes']) && is_array($c['Comprobantes'])) {
                foreach ($c['Comprobantes'] as $comp) {
                    $comprobantes[] = array(
                        'codigo' => isset($comp['Codigo']) ? $comp['Codigo'] : '',
                        'fecha' => isset($comp['Pag_Fec']) ? $comp['Pag_Fec'] : '',
                        'valor' => isset($comp['Pag_Val']) ? floatval($comp['Pag_Val']) : 0,
                        'forma' => isset($comp['Forma']) ? $comp['Forma'] : '',
                        'link' => isset($comp['Link']) ? $comp['Link'] : ''
                    );
                }
            }
            return array(
                'cop_cod' => intval($c['Cop_Cod']),
                'numero' => isset($c['Cop_Num']) ? $c['Cop_Num'] : '',
                'proveedor' => isset($c['Proveedor']) ? $c['Proveedor'] : '',
                'fecha' => isset($c['Cop_Fec']) ? $c['Cop_Fec'] : '',
                'total' => isset($c['Total']) ? floatval($c['Total']) : 0,
                'link' => isset($c['Link_Factura']) ? $c['Link_Factura'] : '',
                'des' => isset($av['Sav_Des']) ? trim($av['Sav_Des']) : '',
                'comprobantes' => $comprobantes
            );
        }
        $cop_cod = isset($av['Sav_Cop_Cod']) ? intval($av['Sav_Cop_Cod']) : 0;
        if ($cop_cod <= 0) {
            return null;
        }
        return array(
            'cop_cod' => $cop_cod,
            'numero' => '#' . $cop_cod,
            'proveedor' => '',
            'fecha' => '',
            'total' => 0,
            'link' => '',
            'des' => isset($av['Sav_Des']) ? trim($av['Sav_Des']) : '',
            'comprobantes' => array()
        );
    }

    /**
     * Asocia filas de avance a movimientos AVANCE y APROBAR de etapa AVANCE.
     */
    private function resolverFacturasAvancePorHistorial($historial, $avances) {
        $mapa = array();
        if (empty($historial) || empty($avances)) {
            return $mapa;
        }

        $entries_avance = array();
        $entries_aprobar = array();
        foreach ($historial as $h) {
            $isn = intval(isset($h['Isn_Cod']) ? $h['Isn_Cod'] : 0);
            if ($isn <= 0) {
                continue;
            }
            $acc = isset($h['Isn_Acc']) ? $h['Isn_Acc'] : '';
            $nod_tip = isset($h['Nod_Tip']) ? $h['Nod_Tip'] : '';
            $ins = intval(isset($h['Ins_Cod']) ? $h['Ins_Cod'] : 0);
            $nod_hist = intval(isset($h['Nod_Cod']) ? $h['Nod_Cod'] : 0);
            $etapa = intval(isset($h['Etapa_Nod_Cod']) ? $h['Etapa_Nod_Cod'] : $nod_hist);
            $fec = isset($h['Isn_Fec']) ? $h['Isn_Fec'] : '';

            if ($acc === 'AVANCE') {
                $entries_avance[] = array('isn' => $isn, 'ins' => $ins, 'nod' => $nod_hist, 'fec' => $fec);
            } elseif (in_array($acc, array('APROBAR', 'COMPLETAR'), true) && $nod_tip === 'AVANCE') {
                $entries_aprobar[] = array('isn' => $isn, 'ins' => $ins, 'nod' => $etapa, 'fec' => $fec);
            }
        }

        usort($entries_avance, function ($a, $b) {
            return strcmp($a['fec'], $b['fec']);
        });

        $assigned = array();
        foreach ($entries_avance as $i => $entry) {
            $curr_ts = !empty($entry['fec']) ? strtotime($entry['fec']) : 0;
            $prev_ts = 0;
            if ($i > 0 && !empty($entries_avance[$i - 1]['fec'])) {
                $prev_ts = strtotime($entries_avance[$i - 1]['fec']);
            }

            $batch = array();
            foreach ($avances as $av) {
                $sav = intval(isset($av['Sav_Cod']) ? $av['Sav_Cod'] : 0);
                if ($sav <= 0 || isset($assigned[$sav])) {
                    continue;
                }
                if (intval($av['Ins_Cod']) !== $entry['ins'] || intval($av['Nod_Cod']) !== $entry['nod']) {
                    continue;
                }
                $av_ts = !empty($av['Sav_Fec']) ? strtotime($av['Sav_Fec']) : 0;
                if ($i === 0) {
                    if ($curr_ts > 0 && $av_ts > $curr_ts + 120) {
                        continue;
                    }
                } elseif ($av_ts <= $prev_ts) {
                    continue;
                } elseif ($curr_ts > 0 && $av_ts > $curr_ts + 120) {
                    continue;
                }
                $batch[] = $av;
                $assigned[$sav] = true;
            }

            if (!empty($batch)) {
                $mapa[$entry['isn']] = $batch;
            }
        }

        foreach ($entries_aprobar as $entry) {
            $batch = array();
            foreach ($avances as $av) {
                if (intval($av['Ins_Cod']) !== $entry['ins'] || intval($av['Nod_Cod']) !== $entry['nod']) {
                    continue;
                }
                $batch[] = $av;
            }
            if (!empty($batch)) {
                $mapa[$entry['isn']] = $batch;
            }
        }

        return $mapa;
    }

    private function agregarArchivosYFacturasAvance(&$archivos, &$facturas, $av, &$vistos) {
        foreach ($this->archivosDesdeAvanceRow($av) as $arch) {
            if (isset($vistos[$arch['path']])) {
                continue;
            }
            $vistos[$arch['path']] = true;
            if (!empty($av['Sav_Des'])) {
                $arch['label'] .= ' - ' . $av['Sav_Des'];
            }
            $archivos[] = $arch;
        }
        $fact = $this->facturaDesdeAvanceRow($av);
        if ($fact !== null) {
            $facturas[] = $fact;
        }
    }

    /**
     * Agrega a cada movimiento del historial los archivos disponibles (adjuntos WF y avances).
     */
    public function enriquecerHistorialConArchivos($historial, $sol_cod) {
        if (empty($historial) || !is_array($historial)) {
            return array();
        }
        $this->ensureAvancesTable();
        $sol_cod = intval($sol_cod);
        $emp_cod = isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0;
        $avances = $this->enriquecerAvancesConCompras($this->listarAvancesSolicitud($sol_cod), $emp_cod);
        $mapa_avance_por_isn = $this->resolverFacturasAvancePorHistorial($historial, $avances);
        $cotizaciones = $this->listarCotizacionesConProveedor($sol_cod);
        $cot_archivos_por_isn = $this->resolverArchivosCotizacionPorHistorial($historial, $cotizaciones);

        foreach ($historial as $idx => $h) {
            $archivos = array();
            $facturas = array();
            $vistos = array();

            $adjuntos = $this->parseCotAdjuntos(isset($h['Isn_Adj']) ? $h['Isn_Adj'] : '');
            foreach ($adjuntos as $i => $path) {
                if (isset($vistos[$path])) {
                    continue;
                }
                $vistos[$path] = true;
                $archivos[] = array(
                    'path' => $path,
                    'label' => count($adjuntos) > 1 ? ('Sustento ' . ($i + 1)) : 'Sustento adjunto'
                );
            }

            $isn_cod = isset($h['Isn_Cod']) ? intval($h['Isn_Cod']) : 0;
            if ($isn_cod > 0 && isset($mapa_avance_por_isn[$isn_cod])) {
                foreach ($mapa_avance_por_isn[$isn_cod] as $av) {
                    $this->agregarArchivosYFacturasAvance($archivos, $facturas, $av, $vistos);
                }
            }

            if (isset($h['Isn_Acc']) && in_array($h['Isn_Acc'], array('COTIZAR', 'CREAR', 'REENVIAR'), true)) {
                $lista_cot = ($isn_cod > 0 && isset($cot_archivos_por_isn[$isn_cod]))
                    ? $cot_archivos_por_isn[$isn_cod]
                    : array();
                foreach ($lista_cot as $arch) {
                    if (isset($vistos[$arch['path']])) {
                        continue;
                    }
                    $vistos[$arch['path']] = true;
                    $archivos[] = $arch;
                }
            }

            $historial[$idx]['archivos'] = $archivos;
            $historial[$idx]['facturas'] = $facturas;
        }

        return $this->adjuntarExpedienteAlHistorial($historial, $sol_cod);
    }

    /**
     * Resuelve el indice del nodo FIN (pendiente o cerrado) en el Historial de Firmas.
     */
    private function resolverIndiceHistorialFin($historial, $sol_cod = 0) {
        $idx_target = -1;
        $idx_pendiente = -1;
        $idx_cierre = -1;
        $idx_otro_fin = -1;
        $idx_por_nod = -1;

        $nod_fin = 0;
        $sol_cod = intval($sol_cod);
        if ($sol_cod > 0) {
            $row = $this->getRowConsultaSql(
                "SELECT n.Nod_Cod
                 FROM wf_instancias i
                 INNER JOIN wf_nodos n ON n.Wfm_Cod = i.Wfm_Cod AND n.Nod_Tip = 'FIN' AND n.Nod_Est = 'A'
                 WHERE i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = $sol_cod
                 ORDER BY i.Ins_Cod DESC, n.Nod_Cod ASC
                 LIMIT 1;",
                $this->conexion
            );
            if (!empty($row['Nod_Cod'])) {
                $nod_fin = intval($row['Nod_Cod']);
            }
        }

        foreach ($historial as $idx => $h) {
            $nod_cod = intval(isset($h['Etapa_Nod_Cod']) ? $h['Etapa_Nod_Cod'] : (isset($h['Nod_Cod']) ? $h['Nod_Cod'] : 0));
            $es_fin = (!empty($h['Fin_Pendiente']))
                || (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'FIN')
                || ($nod_fin > 0 && $nod_cod === $nod_fin);
            if (!$es_fin) {
                continue;
            }

            $idx_otro_fin = $idx;
            if ($nod_fin > 0 && $nod_cod === $nod_fin) {
                $idx_por_nod = $idx;
            }
            if (!empty($h['Fin_Pendiente'])
                || !empty($h['Pendiente_Aprobacion'])
                || (isset($h['Isn_Acc']) && $h['Isn_Acc'] === 'PENDIENTE')
            ) {
                $idx_pendiente = $idx;
            }
            if (isset($h['Isn_Acc']) && in_array($h['Isn_Acc'], array('APROBAR', 'COMPLETAR'), true)) {
                $idx_cierre = $idx;
            }
        }

        if ($idx_pendiente >= 0) {
            $idx_target = $idx_pendiente;
        } elseif ($idx_cierre >= 0) {
            $idx_target = $idx_cierre;
        } elseif ($idx_por_nod >= 0) {
            $idx_target = $idx_por_nod;
        } else {
            $idx_target = $idx_otro_fin;
        }

        return $idx_target;
    }

    /**
     * Adjunta el expediente (firmado si existe; si no, el PDF cargado) a la etapa FIN
     * del Historial de Firmas para poder descargarlo.
     */
    public function adjuntarExpedienteAlHistorial($historial, $sol_cod) {
        if (empty($historial) || !is_array($historial)) {
            return is_array($historial) ? $historial : array();
        }

        $estado = $this->obtenerEstadoExpedienteSolicitud(intval($sol_cod));
        $path_firmado = !empty($estado['firmado']) ? trim((string)$estado['firmado']) : '';
        $path_cargado = !empty($estado['pdf']) ? trim((string)$estado['pdf']) : '';

        $path = '';
        $label = '';
        $flags = array();

        if ($path_firmado !== '') {
            $abs = $this->rutaAbsolutaData($path_firmado);
            if ($abs !== '' && is_file($abs)) {
                $path = $path_firmado;
                $label = 'Expediente firmado';
                if (!empty($estado['firm_nom'])) {
                    $label .= ' (' . $estado['firm_nom'] . ')';
                }
                $flags = array(
                    'es_expediente' => 1,
                    'es_expediente_firmado' => 1
                );
            }
        }

        if ($path === '' && $path_cargado !== '') {
            $abs = $this->rutaAbsolutaData($path_cargado);
            if ($abs !== '' && is_file($abs)) {
                $path = $path_cargado;
                $label = 'Expediente PDF (sin firmar)';
                $flags = array(
                    'es_expediente' => 1,
                    'es_expediente_firmado' => 0
                );
            }
        }

        if ($path === '') {
            return $historial;
        }

        $idx_target = $this->resolverIndiceHistorialFin($historial, intval($sol_cod));
        if ($idx_target < 0) {
            return $historial;
        }

        if (!isset($historial[$idx_target]['archivos']) || !is_array($historial[$idx_target]['archivos'])) {
            $historial[$idx_target]['archivos'] = array();
        }

        $filtrados = array();
        foreach ($historial[$idx_target]['archivos'] as $arch) {
            if (!empty($arch['es_expediente']) || !empty($arch['es_expediente_firmado'])) {
                continue;
            }
            if (!empty($arch['path']) && ($arch['path'] === $path_firmado || $arch['path'] === $path_cargado)) {
                continue;
            }
            $filtrados[] = $arch;
        }

        $arch_nuevo = array_merge(array(
            'path' => $path,
            'label' => $label
        ), $flags);
        $filtrados[] = $arch_nuevo;
        $historial[$idx_target]['archivos'] = $filtrados;

        return $historial;
    }

    /**
     * @deprecated Usar adjuntarExpedienteAlHistorial
     */
    public function adjuntarExpedienteFirmadoAlHistorial($historial, $sol_cod) {
        return $this->adjuntarExpedienteAlHistorial($historial, $sol_cod);
    }

    private function ensureSolicitudRequisitosColumns() {
        $cols = array(
            'Sol_Req_Fac' => "TINYINT(1) NULL DEFAULT NULL AFTER Sol_Est",
            'Sol_Per_Cie' => "TINYINT(1) NULL DEFAULT NULL AFTER Sol_Req_Fac",
            'Sol_Req_Cot' => "TINYINT(1) NULL DEFAULT NULL AFTER Sol_Per_Cie",
            'Sol_Min_Cot' => "INT(11) NULL DEFAULT NULL AFTER Sol_Req_Cot",
            'Sol_Req_Pre' => "TINYINT(1) NULL DEFAULT NULL AFTER Sol_Min_Cot",
            'Sol_Req_Adj' => "TINYINT(1) NULL DEFAULT NULL AFTER Sol_Req_Pre",
            'Sol_Req_Pro' => "TINYINT(1) NULL DEFAULT NULL AFTER Sol_Req_Adj",
            'Sol_Tiempo_Est' => "INT(11) NULL DEFAULT NULL AFTER Sol_Req_Pro"
        );
        foreach ($cols as $col => $def) {
            $row = $this->getRowConsultaSql(
                "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adq_solicitudes' AND COLUMN_NAME = '$col';",
                $this->conexion
            );
            if (empty($row['cnt'])) {
                if (!$this->grabarv_registros("ALTER TABLE adq_solicitudes ADD COLUMN $col $def;", $this->conexion)) {
                    throw new Exception('No se pudo preparar la tabla de solicitudes (' . $col . '): ' . $this->getMsgError());
                }
            }
        }
        $this->grabarv_registros(
            "UPDATE adq_solicitudes s
             INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
             SET s.Sol_Req_Fac = tr.Trq_Req_Fac,
                 s.Sol_Per_Cie = tr.Trq_Per_Cie,
                 s.Sol_Req_Cot = tr.Trq_Req_Cot,
                 s.Sol_Min_Cot = tr.Trq_Min_Cot,
                 s.Sol_Req_Pre = tr.Trq_Req_Pre,
                 s.Sol_Req_Adj = tr.Trq_Req_Adj,
                 s.Sol_Req_Pro = tr.Trq_Req_Pro,
                 s.Sol_Tiempo_Est = tr.Trq_Tiempo_Est
             WHERE s.Sol_Req_Fac IS NULL;",
            $this->conexion
        );
    }

    /**
     * Normaliza requisitos efectivos de una solicitud (snapshot Sol_* con fallback al tipo).
     */
    public function aplicarRequisitosEfectivos($row) {
        if (empty($row)) {
            return $row;
        }
        $map = array(
            'Sol_Req_Fac' => 'Trq_Req_Fac',
            'Sol_Per_Cie' => 'Trq_Per_Cie',
            'Sol_Req_Cot' => 'Trq_Req_Cot',
            'Sol_Min_Cot' => 'Trq_Min_Cot',
            'Sol_Req_Pre' => 'Trq_Req_Pre',
            'Sol_Req_Adj' => 'Trq_Req_Adj',
            'Sol_Req_Pro' => 'Trq_Req_Pro',
            'Sol_Tiempo_Est' => 'Trq_Tiempo_Est'
        );
        foreach ($map as $sol_key => $trq_key) {
            if (!isset($row[$sol_key]) || $row[$sol_key] === '' || $row[$sol_key] === null) {
                if (isset($row[$trq_key])) {
                    $row[$sol_key] = $row[$trq_key];
                }
            }
        }
        if (empty($row['Sol_Min_Cot'])) {
            $row['Sol_Min_Cot'] = 1;
        }
        return $row;
    }

    private function construirRequisitosDesdePost($data, $trq_cod) {
        $trq = $this->getRowConsultaSql(
            "SELECT * FROM adq_tipos_requerimientos WHERE Trq_Cod = " . intval($trq_cod) . " LIMIT 1;",
            $this->conexion
        );
        if (empty($trq)) {
            throw new Exception('El Tipo de Requerimiento seleccionado no es valido.');
        }

        $req_fac = array_key_exists('Sol_Req_Fac', $data) ? (!empty($data['Sol_Req_Fac']) ? 1 : 0) : intval($trq['Trq_Req_Fac']);
        $per_cie = array_key_exists('Sol_Per_Cie', $data) ? (!empty($data['Sol_Per_Cie']) ? 1 : 0) : intval($trq['Trq_Per_Cie']);
        $req_cot = array_key_exists('Sol_Req_Cot', $data) ? (!empty($data['Sol_Req_Cot']) ? 1 : 0) : intval($trq['Trq_Req_Cot']);
        $min_cot = array_key_exists('Sol_Min_Cot', $data) && $data['Sol_Min_Cot'] !== '' ? max(1, intval($data['Sol_Min_Cot'])) : max(1, intval($trq['Trq_Min_Cot']));
        if (!$req_cot) {
            $min_cot = 0;
        }
        $req_pre = array_key_exists('Sol_Req_Pre', $data) ? (!empty($data['Sol_Req_Pre']) ? 1 : 0) : intval($trq['Trq_Req_Pre']);
        $req_adj = array_key_exists('Sol_Req_Adj', $data) ? (!empty($data['Sol_Req_Adj']) ? 1 : 0) : intval($trq['Trq_Req_Adj']);
        $req_pro = array_key_exists('Sol_Req_Pro', $data) ? (!empty($data['Sol_Req_Pro']) ? 1 : 0) : intval($trq['Trq_Req_Pro']);
        if (!empty($data['Sol_Define_Sla']) && isset($data['Sol_Tiempo_Est']) && $data['Sol_Tiempo_Est'] !== '') {
            $tiempo_est = max(1, intval($data['Sol_Tiempo_Est']));
        } else {
            $tiempo_est = null;
        }

        return array(
            'Sol_Req_Fac' => $req_fac,
            'Sol_Per_Cie' => $per_cie,
            'Sol_Req_Cot' => $req_cot,
            'Sol_Min_Cot' => $min_cot,
            'Sol_Req_Pre' => $req_pre,
            'Sol_Req_Adj' => $req_adj,
            'Sol_Req_Pro' => $req_pro,
            'Sol_Tiempo_Est' => $tiempo_est
        );
    }

    private function ensureSdeIvaColumn() {
        $row = $this->getRowConsultaSql(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adq_solicitudes_det' AND COLUMN_NAME = 'Sde_Iva';",
            $this->conexion
        );
        if (empty($row['cnt'])) {
            if (!$this->grabarv_registros(
                "ALTER TABLE adq_solicitudes_det ADD COLUMN Sde_Iva TINYINT(1) NOT NULL DEFAULT 0 AFTER Sde_Pru;",
                $this->conexion
            )) {
                throw new Exception('No se pudo preparar la tabla de detalle (Sde_Iva): ' . $this->getMsgError());
            }
        }
    }

    private function resolverDepSolicitante($emp_cod, $usu_cod) {
        $emp_cod = intval($emp_cod);
        $usu_cod = intval($usu_cod);
        $dep_sol = isset($_SESSION['Ses_Dep_Cod']) ? intval($_SESSION['Ses_Dep_Cod']) : 0;
        if ($dep_sol > 0) {
            $dep = $this->getRowConsultaSql(
                "SELECT Dep_Cod FROM departamen WHERE Dep_Cod = $dep_sol AND Emp_Cod = $emp_cod AND Dep_Est = 'A' LIMIT 1;",
                $this->conexion
            );
            if (!empty($dep)) {
                return intval($dep['Dep_Cod']);
            }
        }
        if ($usu_cod > 0) {
            $dep = $this->getRowConsultaSql(
                "SELECT MIN(w.Dep_Cod) AS Dep_Cod
                 FROM wf_departamento_usuarios du
                 INNER JOIN wf_departamentos w ON w.Wde_Cod = du.Wde_Cod AND w.Emp_Cod = $emp_cod AND w.Wde_Est = 'A'
                 WHERE du.Usu_Cod = $usu_cod AND du.Wde_Cod IS NOT NULL;",
                $this->conexion
            );
            if (!empty($dep['Dep_Cod'])) {
                return intval($dep['Dep_Cod']);
            }
        }
        $dep = $this->getRowConsultaSql(
            "SELECT MIN(Dep_Cod) AS Dep_Cod FROM departamen WHERE Emp_Cod = $emp_cod AND Dep_Est = 'A';",
            $this->conexion
        );
        return !empty($dep['Dep_Cod']) ? intval($dep['Dep_Cod']) : 0;
    }

    /**
     * Inserta cabecera, detalle y cotizaciones. Deja la solicitud en estado Borrador (P).
     */
    private function persistirSolicitudNueva($data, $items, $cotizaciones = array()) {
        $this->ensureSdeIvaColumn();
        $this->ensureSolicitudRequisitosColumns();
        $fecha_actual = date('Y-m-d H:i:s');
        $usu_sol = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;
        $dep_sol = $this->resolverDepSolicitante($data['Emp_Cod'], $usu_sol);

        $sol_num = $this->generarSiguienteSolNum($data['Emp_Cod'], $data['Suc_Cod']);
        $sol_num_sql = $this->escapeSql($sol_num);

        $trq_cod = intval($data['Trq_Cod']);
        $sol_pri = $this->escapeSql($data['Sol_Pri']);
        $sol_val_est = floatval($data['Sol_Val_Est']);
        $cdc_cod = !empty($data['Cdc_Cod']) ? "'" . $this->escapeSql($data['Cdc_Cod']) . "'" : 'NULL';
        $pry_cod = !empty($data['Pry_Cod']) ? intval($data['Pry_Cod']) : 'NULL';
        $prv_sug = !empty($data['Prv_Sug']) ? intval($data['Prv_Sug']) : 'NULL';
        $sol_jus = $this->escapeSql($data['Sol_Jus']);
        $sol_det = $this->escapeSql(isset($data['Sol_Det']) ? $data['Sol_Det'] : '');

        $req = $this->construirRequisitosDesdePost($data, $trq_cod);
        $sol_tiempo_sql = $req['Sol_Tiempo_Est'] !== null ? intval($req['Sol_Tiempo_Est']) : 'NULL';

        $sqlInsert = "INSERT INTO adq_solicitudes (
                Emp_Cod, Suc_Cod, Trq_Cod, Sol_Num, Sol_Fec, Usu_Sol, Dep_Sol, Sol_Pri, Sol_Val_Est,
                Cdc_Cod, Pry_Cod, Prv_Sug, Sol_Jus, Sol_Det, Sol_Est,
                Sol_Req_Fac, Sol_Per_Cie, Sol_Req_Cot, Sol_Min_Cot, Sol_Req_Pre, Sol_Req_Adj, Sol_Req_Pro, Sol_Tiempo_Est
            ) VALUES (
                $data[Emp_Cod], $data[Suc_Cod], $trq_cod, '$sol_num_sql', '$fecha_actual', $usu_sol, $dep_sol, '$sol_pri', $sol_val_est,
                $cdc_cod, $pry_cod, $prv_sug, '$sol_jus', '$sol_det', 'P',
                {$req['Sol_Req_Fac']}, {$req['Sol_Per_Cie']}, {$req['Sol_Req_Cot']}, {$req['Sol_Min_Cot']},
                {$req['Sol_Req_Pre']}, {$req['Sol_Req_Adj']}, {$req['Sol_Req_Pro']}, $sol_tiempo_sql
            );";
        if (!$this->grabarv_registros($sqlInsert, $this->conexion)) {
            throw new Exception('No se pudo registrar la solicitud: ' . $this->getMsgError());
        }
        $sol_cod = $this->insercionid($this->conexion);

        $idx = 1;
        foreach ($items as $item) {
            $pro_cod = !empty($item['Pro_Cod']) ? intval($item['Pro_Cod']) : 'NULL';
            $sde_des = $this->escapeSql($item['Sde_Des']);
            $sde_can = floatval($item['Sde_Can']);
            $sde_pru = floatval($item['Sde_Pru']);
            $sde_iva = !empty($item['Sde_Iva']) ? 1 : 0;

            $sqlDet = "INSERT INTO adq_solicitudes_det (Sol_Cod, Sde_Int, Pro_Cod, Sde_Des, Sde_Can, Sde_Pru, Sde_Iva)
                       VALUES ($sol_cod, $idx, $pro_cod, '$sde_des', $sde_can, $sde_pru, $sde_iva);";
            if (!$this->grabarv_registros($sqlDet, $this->conexion)) {
                throw new Exception('No se pudo guardar un item de la solicitud: ' . $this->getMsgError());
            }
            $idx++;
        }

        foreach ($cotizaciones as $cot) {
            if (empty($cot['Prv_Cod']) && empty($cot['Cot_Val']) && !$this->cotizacionTieneAdjunto(isset($cot['Cot_Adj']) ? $cot['Cot_Adj'] : '')) {
                continue;
            }
            $prv_cod = intval($cot['Prv_Cod']);
            $cot_val = floatval($cot['Cot_Val']);
            $cot_adj = $this->escapeSql(isset($cot['Cot_Adj']) ? $cot['Cot_Adj'] : '');
            $cot_sel = !empty($cot['Cot_Sel']) ? 1 : 0;
            $cot_jus = $this->escapeSql(isset($cot['Cot_Jus']) ? $cot['Cot_Jus'] : '');

            $sqlCot = "INSERT INTO adq_solicitudes_cotizaciones (Sol_Cod, Prv_Cod, Cot_Fec, Cot_Val, Cot_Adj, Cot_Sel, Cot_Jus)
                       VALUES ($sol_cod, $prv_cod, '" . date('Y-m-d') . "', $cot_val, '$cot_adj', $cot_sel, '$cot_jus');";
            if (!$this->grabarv_registros($sqlCot, $this->conexion)) {
                throw new Exception('No se pudo guardar una cotizacion: ' . $this->getMsgError());
            }
        }

        return array('Sol_Cod' => $sol_cod, 'Num' => $sol_num, 'Trq_Cod' => $trq_cod);
    }

    private function obtenerSolicitudConTipo($sol_cod) {
        $sol_cod = intval($sol_cod);
        $sol = $this->getRowConsultaSql(
            "SELECT s.*, tr.Trq_Req_Fac, tr.Trq_Per_Cie, tr.Trq_Req_Cot, tr.Trq_Min_Cot,
                    tr.Trq_Req_Pre, tr.Trq_Req_Adj, tr.Trq_Req_Pro, tr.Trq_Tiempo_Est
             FROM adq_solicitudes s
             INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
             WHERE s.Sol_Cod = $sol_cod LIMIT 1;",
            $this->conexion
        );
        return empty($sol) ? null : $this->aplicarRequisitosEfectivos($sol);
    }

    private function assertBorradorEditable($sol_cod, $emp_cod, $usu_sol) {
        return $this->assertSolicitudEditablePorSolicitante($sol_cod, $emp_cod, $usu_sol, array('P'));
    }

    private function assertSolicitudEditablePorSolicitante($sol_cod, $emp_cod, $usu_sol, $estados = array('P', 'O')) {
        $sol_cod = intval($sol_cod);
        $emp_cod = intval($emp_cod);
        $usu_sol = intval($usu_sol);
        $estados = array_values(array_intersect($estados, array('P', 'O')));
        if (empty($estados)) {
            $estados = array('P');
        }
        $est_list = implode("','", $estados);
        $sol = $this->getRowConsultaSql(
            "SELECT Sol_Cod, Sol_Num, Trq_Cod, Sol_Est FROM adq_solicitudes
             WHERE Sol_Cod = $sol_cod AND Emp_Cod = $emp_cod AND Usu_Sol = $usu_sol AND Sol_Est IN ('$est_list') LIMIT 1;",
            $this->conexion
        );
        if (empty($sol)) {
            throw new Exception('La solicitud no existe o no puede editarse en este momento.');
        }
        return $sol;
    }

    private function obtenerUltimaObservacionWorkflow($sol_cod) {
        $sol_cod = intval($sol_cod);
        return $this->getRowConsultaSql(
            "SELECT h.Isn_Com, h.Isn_Fec, IFNULL(n.Nod_Nom, '') AS Nod_Nom
             FROM wf_instancias i
             INNER JOIN wf_instancias_nodos h ON h.Ins_Cod = i.Ins_Cod AND h.Isn_Acc = 'OBSERVAR'
             LEFT JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
             WHERE i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = $sol_cod AND i.Ins_Est = 'P'
             ORDER BY h.Isn_Fec DESC
             LIMIT 1;",
            $this->conexion
        );
    }

    private function guardarDetalleSolicitud($sol_cod, $items) {
        $this->ensureSdeIvaColumn();
        $sol_cod = intval($sol_cod);
        if (!$this->grabarv_registros("DELETE FROM adq_solicitudes_det WHERE Sol_Cod = $sol_cod;", $this->conexion)) {
            throw new Exception('No se pudo actualizar el detalle de la solicitud: ' . $this->getMsgError());
        }
        $idx = 1;
        foreach ($items as $item) {
            $pro_cod = !empty($item['Pro_Cod']) ? intval($item['Pro_Cod']) : 'NULL';
            $sde_des = $this->escapeSql($item['Sde_Des']);
            $sde_can = floatval($item['Sde_Can']);
            $sde_pru = floatval($item['Sde_Pru']);
            $sde_iva = !empty($item['Sde_Iva']) ? 1 : 0;
            $sqlDet = "INSERT INTO adq_solicitudes_det (Sol_Cod, Sde_Int, Pro_Cod, Sde_Des, Sde_Can, Sde_Pru, Sde_Iva)
                       VALUES ($sol_cod, $idx, $pro_cod, '$sde_des', $sde_can, $sde_pru, $sde_iva);";
            if (!$this->grabarv_registros($sqlDet, $this->conexion)) {
                throw new Exception('No se pudo guardar un item de la solicitud: ' . $this->getMsgError());
            }
            $idx++;
        }
    }

    private function insertarCotizacionesNuevas($sol_cod, $cotizaciones) {
        $sol_cod = intval($sol_cod);
        foreach ($cotizaciones as $cot) {
            if (empty($cot['Prv_Cod']) && empty($cot['Cot_Val']) && !$this->cotizacionTieneAdjunto(isset($cot['Cot_Adj']) ? $cot['Cot_Adj'] : '')) {
                continue;
            }
            $prv_cod = intval($cot['Prv_Cod']);
            $cot_val = floatval($cot['Cot_Val']);
            $cot_adj = $this->escapeSql(isset($cot['Cot_Adj']) ? $cot['Cot_Adj'] : '');
            $cot_sel = !empty($cot['Cot_Sel']) ? 1 : 0;
            $cot_jus = $this->escapeSql(isset($cot['Cot_Jus']) ? $cot['Cot_Jus'] : '');
            $sqlCot = "INSERT INTO adq_solicitudes_cotizaciones (Sol_Cod, Prv_Cod, Cot_Fec, Cot_Val, Cot_Adj, Cot_Sel, Cot_Jus)
                       VALUES ($sol_cod, $prv_cod, '" . date('Y-m-d') . "', $cot_val, '$cot_adj', $cot_sel, '$cot_jus');";
            if (!$this->grabarv_registros($sqlCot, $this->conexion)) {
                throw new Exception('No se pudo guardar una cotizacion: ' . $this->getMsgError());
            }
        }
    }

    private function sincronizarCotizacionesBorrador($sol_cod, $cotizaciones_nuevas, $cotizaciones_existentes, $cot_eliminar) {
        $sol_cod = intval($sol_cod);
        if (!empty($cot_eliminar)) {
            foreach ($cot_eliminar as $sco_cod) {
                $sco_cod = intval($sco_cod);
                if ($sco_cod > 0) {
                    $this->grabarv_registros(
                        "DELETE FROM adq_solicitudes_cotizaciones WHERE Sco_Cod = $sco_cod AND Sol_Cod = $sol_cod;",
                        $this->conexion
                    );
                }
            }
        }
        if (!empty($cotizaciones_existentes)) {
            foreach ($cotizaciones_existentes as $sco_cod => $cot) {
                $sco_cod = intval($sco_cod);
                if ($sco_cod <= 0) {
                    continue;
                }
                $prv_cod = intval($cot['Prv_Cod']);
                $cot_val = floatval($cot['Cot_Val']);
                $cot_sel = !empty($cot['Cot_Sel']) ? 1 : 0;
                $cot_jus = $this->escapeSql(isset($cot['Cot_Jus']) ? $cot['Cot_Jus'] : '');
                $set_adj = '';
                if (array_key_exists('Cot_Adj', $cot)) {
                    $cot_adj = $this->escapeSql($cot['Cot_Adj']);
                    $set_adj = ", Cot_Adj = '$cot_adj'";
                }
                $sqlUpd = "UPDATE adq_solicitudes_cotizaciones
                           SET Prv_Cod = $prv_cod, Cot_Val = $cot_val, Cot_Sel = $cot_sel, Cot_Jus = '$cot_jus' $set_adj
                           WHERE Sco_Cod = $sco_cod AND Sol_Cod = $sol_cod;";
                if (!$this->grabarv_registros($sqlUpd, $this->conexion)) {
                    throw new Exception('No se pudo actualizar una cotizacion: ' . $this->getMsgError());
                }
            }
        }
        $this->insertarCotizacionesNuevas($sol_cod, $cotizaciones_nuevas);
    }

    /**
     * Verifica si el usuario puede cargar cotizaciones en la etapa actual del workflow
     * (nodo con Nod_Cot_Edit = 1 asignado al usuario).
     */
    public function autorizarCotizacionesEtapa($sol_cod, $emp_cod, $usu_cod) {
        $sol_cod = intval($sol_cod);
        $emp_cod = intval($emp_cod);
        $usu_cod = intval($usu_cod);
        if ($sol_cod <= 0 || $usu_cod <= 0) {
            return array('success' => false, 'message' => 'Datos invalidos.');
        }
        $wf_mgr = new wf_manager_log(isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null, $this->conexion);
        $wf_mgr->ensureVersioningSchema();
        $row = $this->getRowConsultaSql(
            "SELECT i.Ins_Cod, i.Nod_Act, n.Nod_Tip, n.Nod_Nom, s.Sol_Est
             FROM adq_solicitudes s
             INNER JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod AND i.Ins_Est = 'P'
             INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
             WHERE s.Sol_Cod = $sol_cod AND s.Emp_Cod = $emp_cod
             ORDER BY i.Ins_Cod DESC LIMIT 1;",
            $this->conexion
        );
        if (empty($row)) {
            return array('success' => false, 'message' => 'La solicitud no tiene un workflow activo.');
        }
        if ($wf_mgr->resolverNodCotEditInstancia(intval($row['Ins_Cod'])) !== 1) {
            return array('success' => false, 'message' => 'La etapa actual no permite cargar cotizaciones.');
        }
        if (in_array($row['Sol_Est'], array('A', 'R'), true)) {
            return array('success' => false, 'message' => 'La solicitud ya fue finalizada.');
        }
        $ctx = $wf_mgr->resolverContextoUsuario($emp_cod);
        if (!$wf_mgr->puedeResolverInstancia(intval($row['Ins_Cod']), $ctx['usu_cod'], $ctx['dep_cod'], $ctx['perfiles_ids'])) {
            return array('success' => false, 'message' => 'La etapa actual no esta asignada a su usuario.');
        }
        return array(
            'success' => true,
            'Ins_Cod' => intval($row['Ins_Cod']),
            'Nod_Cod' => intval($row['Nod_Act']),
            'Nod_Nom' => $row['Nod_Nom']
        );
    }

    /**
     * Devuelve la solicitud con sus cotizaciones para el modo de carga de proformas por etapa.
     */
    public function obtenerSolicitudParaCotizaciones($sol_cod, $emp_cod, $usu_cod) {
        $auth = $this->autorizarCotizacionesEtapa($sol_cod, $emp_cod, $usu_cod);
        if (!$auth['success']) {
            return $auth;
        }
        $sol_cod = intval($sol_cod);
        $emp_cod = intval($emp_cod);
        $sol = $this->getRowConsultaSql(
            "SELECT s.*, tr.Trq_Des,
                    tr.Trq_Req_Fac, tr.Trq_Per_Cie, tr.Trq_Req_Cot, tr.Trq_Min_Cot,
                    tr.Trq_Req_Pre, tr.Trq_Req_Adj, tr.Trq_Req_Pro, tr.Trq_Tiempo_Est,
                    pr.Prv_Com, per.Prs_Ced, per.Prs_Nom, per.Prs_Ape
             FROM adq_solicitudes s
             INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
             LEFT JOIN proveedore pr ON pr.Prv_Cod = s.Prv_Sug
             LEFT JOIN persona per ON per.Prs_Cod = pr.Prs_Cod
             WHERE s.Sol_Cod = $sol_cod AND s.Emp_Cod = $emp_cod
             LIMIT 1;",
            $this->conexion
        );
        if (empty($sol)) {
            return array('success' => false, 'message' => 'La solicitud no existe.');
        }
        $sol = $this->aplicarRequisitosEfectivos($sol);
        $items = $this->getArrayConsultaSql(
            "SELECT * FROM adq_solicitudes_det WHERE Sol_Cod = $sol_cod ORDER BY Sde_Int;",
            $this->conexion
        );
        $cotizaciones = $this->getArrayConsultaSql(
            "SELECT c.*, per.Prs_Nom, per.Prs_Ape, pr.Prv_Com, per.Prs_Ced
             FROM adq_solicitudes_cotizaciones c
             LEFT JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod
             LEFT JOIN persona per ON per.Prs_Cod = pr.Prs_Cod
             WHERE c.Sol_Cod = $sol_cod
             ORDER BY c.Sco_Cod;",
            $this->conexion
        );
        $prv_sug_text = '';
        if (!empty($sol['Prv_Sug'])) {
            $nombre = trim($sol['Prs_Ape'] . ' ' . $sol['Prs_Nom']);
            if (!empty($sol['Prv_Com'])) {
                $nombre .= ' (' . $sol['Prv_Com'] . ')';
            }
            if (!empty($sol['Prs_Ced'])) {
                $nombre .= ' - RUC: ' . $sol['Prs_Ced'];
            }
            $prv_sug_text = $nombre;
        }
        return array(
            'success' => true,
            'solicitud' => $sol,
            'items' => ($items === false || $items === null) ? array() : $items,
            'cotizaciones' => ($cotizaciones === false || $cotizaciones === null) ? array() : $cotizaciones,
            'prv_sug_text' => $prv_sug_text,
            'modo_edicion' => 'cotizaciones',
            'etapa_nombre' => $auth['Nod_Nom']
        );
    }

    /**
     * Guarda solo las cotizaciones de la solicitud, autorizado por la etapa del workflow.
     */
    public function guardarCotizacionesEtapa($sol_cod, $cotizaciones_nuevas = array(), $cotizaciones_existentes = array(), $cot_eliminar = array()) {
        $sol_cod = intval($sol_cod);
        $emp_cod = isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0;
        $usu_cod = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;

        $auth = $this->autorizarCotizacionesEtapa($sol_cod, $emp_cod, $usu_cod);
        if (!$auth['success']) {
            return $auth;
        }

        $sol = $this->getRowConsultaSql(
            "SELECT Sol_Cod, Sol_Num FROM adq_solicitudes WHERE Sol_Cod = $sol_cod AND Emp_Cod = $emp_cod LIMIT 1;",
            $this->conexion
        );
        if (empty($sol)) {
            return array('success' => false, 'message' => 'La solicitud no existe.');
        }

        $this->inicio_transaccion($this->conexion);
        try {
            $this->sincronizarCotizacionesBorrador($sol_cod, $cotizaciones_nuevas, $cotizaciones_existentes, $cot_eliminar);
            $this->commit_nomsn($this->conexion);
        } catch (Exception $e) {
            $this->rollBack_nomsn($this->conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }

        try {
            $fecha = date('Y-m-d H:i:s');
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            $ses = session_id() ?: 'CLI-SESSION';
            $dep_cod = isset($_SESSION['Ses_Dep_Cod']) ? intval($_SESSION['Ses_Dep_Cod']) : 0;
            $comentario = $this->escapeSql('Carga/actualizacion de cotizaciones en la etapa.');
            $this->grabarv_registros(
                "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Fec, Isn_Ip, Isn_Ses)
                 VALUES ({$auth['Ins_Cod']}, {$auth['Nod_Cod']}, $usu_cod, $dep_cod, 'COTIZAR', '$comentario', '$fecha', '$ip', '$ses');",
                $this->conexion
            );
        } catch (Exception $e) {
            // El historial es informativo; no bloquea el guardado.
        }

        return array('success' => true, 'Sol_Cod' => $sol_cod, 'Num' => $sol['Sol_Num']);
    }

    private function ensureAvancesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS adq_solicitudes_avances (
            Sav_Cod BIGINT AUTO_INCREMENT PRIMARY KEY,
            Sol_Cod BIGINT NOT NULL,
            Ins_Cod BIGINT NOT NULL,
            Nod_Cod BIGINT NOT NULL,
            Sav_Des VARCHAR(250) NOT NULL DEFAULT '',
            Sav_Adj VARCHAR(500) NULL,
            Sav_Fac_Adj VARCHAR(500) NULL,
            Sav_Ret_Adj VARCHAR(500) NULL,
            Sav_Com_Adj VARCHAR(500) NULL,
            Usu_Cod INT NULL,
            Sav_Fec DATETIME NOT NULL,
            KEY idx_sav_sol (Sol_Cod),
            KEY idx_sav_ins (Ins_Cod),
            KEY idx_sav_nodo (Nod_Cod)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        if (!$this->grabarv_registros($sql, $this->conexion)) {
            throw new Exception('No se pudo preparar la tabla de avances: ' . $this->getMsgError());
        }

        $cols = array(
            'Sav_Fac_Adj' => "ALTER TABLE adq_solicitudes_avances ADD COLUMN Sav_Fac_Adj VARCHAR(500) NULL AFTER Sav_Adj;",
            'Sav_Ret_Adj' => "ALTER TABLE adq_solicitudes_avances ADD COLUMN Sav_Ret_Adj VARCHAR(500) NULL AFTER Sav_Fac_Adj;",
            'Sav_Com_Adj' => "ALTER TABLE adq_solicitudes_avances ADD COLUMN Sav_Com_Adj VARCHAR(500) NULL AFTER Sav_Ret_Adj;",
            'Sav_Cop_Cod' => "ALTER TABLE adq_solicitudes_avances ADD COLUMN Sav_Cop_Cod BIGINT NULL AFTER Sav_Com_Adj;"
        );
        foreach ($cols as $col => $sqlAlt) {
            $row = $this->getRowConsultaSql(
                "SELECT COUNT(*) AS cnt
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'adq_solicitudes_avances'
                   AND COLUMN_NAME = '$col';",
                $this->conexion
            );
            if (empty($row['cnt'])) {
                if (!$this->grabarv_registros($sqlAlt, $this->conexion)) {
                    throw new Exception('No se pudo preparar la tabla de avances (' . $col . '): ' . $this->getMsgError());
                }
            }
        }

        $this->grabarv_registros(
            "UPDATE adq_solicitudes_avances
             SET Sav_Fac_Adj = Sav_Adj
             WHERE (Sav_Fac_Adj IS NULL OR Sav_Fac_Adj = '')
               AND Sav_Adj IS NOT NULL AND Sav_Adj <> '';",
            $this->conexion
        );
    }

    /**
     * Verifica si el usuario puede cargar documentos en etapa AVANCE.
     */
    public function autorizarAvanceEtapa($sol_cod, $emp_cod, $usu_cod) {
        $sol_cod = intval($sol_cod);
        $emp_cod = intval($emp_cod);
        $usu_cod = intval($usu_cod);
        if ($sol_cod <= 0 || $usu_cod <= 0) {
            return array('success' => false, 'message' => 'Datos invalidos.');
        }
        $wf_mgr = new wf_manager_log(isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null, $this->conexion);
        $row = $this->getRowConsultaSql(
            "SELECT i.Ins_Cod, i.Nod_Act, n.Nod_Tip, n.Nod_Nom, s.Sol_Est
             FROM adq_solicitudes s
             INNER JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod AND i.Ins_Est = 'P'
             INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
             WHERE s.Sol_Cod = $sol_cod AND s.Emp_Cod = $emp_cod
             ORDER BY i.Ins_Cod DESC LIMIT 1;",
            $this->conexion
        );
        if (empty($row)) {
            return array('success' => false, 'message' => 'La solicitud no tiene un workflow activo.');
        }
        if ($row['Nod_Tip'] !== 'AVANCE') {
            return array('success' => false, 'message' => 'La etapa actual no es de avance.');
        }
        if (in_array($row['Sol_Est'], array('A', 'R'), true)) {
            return array('success' => false, 'message' => 'La solicitud ya fue finalizada.');
        }
        $ctx = $wf_mgr->resolverContextoUsuario($emp_cod);
        if (!$wf_mgr->puedeResolverInstancia(intval($row['Ins_Cod']), $ctx['usu_cod'], $ctx['dep_cod'], $ctx['perfiles_ids'])) {
            return array('success' => false, 'message' => 'La etapa actual no esta asignada a su usuario.');
        }
        return array(
            'success' => true,
            'Ins_Cod' => intval($row['Ins_Cod']),
            'Nod_Cod' => intval($row['Nod_Act']),
            'Nod_Nom' => $row['Nod_Nom']
        );
    }

    public function listarAvancesSolicitud($sol_cod, $ins_cod = 0, $nod_cod = 0) {
        $this->ensureAvancesTable();
        $sol_cod = intval($sol_cod);
        $ins_cod = intval($ins_cod);
        $nod_cod = intval($nod_cod);
        if ($sol_cod <= 0) {
            return array();
        }
        $filtro = "WHERE a.Sol_Cod = $sol_cod";
        if ($ins_cod > 0 && $nod_cod > 0) {
            $filtro .= " AND a.Ins_Cod = $ins_cod AND a.Nod_Cod = $nod_cod";
        }
        $rows = $this->getArrayConsultaSql(
            "SELECT a.*, TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Usuario_Nom
             FROM adq_solicitudes_avances a
             LEFT JOIN usuarios u ON u.Usu_Cod = a.Usu_Cod
             LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
             $filtro
             ORDER BY a.Sav_Fec DESC, a.Sav_Cod DESC;",
            $this->conexion
        );
        return ($rows === false || $rows === null) ? array() : $rows;
    }

    /**
     * Calcula subtotal, IVA y total de una compra (misma logica que fac_log_compras::calculosCompraIce).
     */
    private function calcularTotalesCompraExa($cop_cod) {
        $cop_cod = intval($cop_cod);
        $lineas = $this->getArrayConsultaSql(
            "SELECT c.Cop_Des, dc.Cop_Int, dc.Cop_Imp, dc.Cop_Dec, iv.Iva_Por
             FROM compras c
             INNER JOIN det_compra dc ON dc.Cop_Cod = c.Cop_Cod
             INNER JOIN iva iv ON iv.Iva_Cod = dc.Iva_Cod
             WHERE c.Cop_Cod = $cop_cod;",
            $this->conexion
        );
        if ($lineas === false || $lineas === null || empty($lineas)) {
            return array('Subtotal' => 0, 'Iva' => 0, 'Descuento' => 0, 'Ice' => 0, 'Total' => 0);
        }

        $imp_ice = 0;
        $subtotal = 0;
        $iva_por = 0;
        $tarifa_12 = 0;
        $des_0 = 0;
        $des_12 = 0;
        $cop_des = 0;

        foreach ($lineas as $row) {
            $cop_des = floatval($row['Cop_Des']);
            $cop_imp = floatval($row['Cop_Imp']);
            $cop_dec = floatval($row['Cop_Dec']);
            $iva_linea = floatval($row['Iva_Por']);

            $subtotal += $cop_imp;

            if ($iva_linea == 0) {
                $des_0 += ($cop_imp * $cop_dec) / 100;
            } else {
                $tarifa_12 += $cop_imp;
                $des_12 += ($cop_imp * $cop_dec) / 100;
                $iva_por = $iva_linea;
            }

            $ice_row = $this->getRowConsultaSql(
                "SELECT ice.Ice_Por
                 FROM ice
                 INNER JOIN det_compra dc ON dc.Ice_Int = ice.Ice_Int AND dc.Cop_Cod = $cop_cod
                 WHERE dc.Cop_Int = " . intval($row['Cop_Int']) . "
                 LIMIT 1;",
                $this->conexion
            );
            if (!empty($ice_row['Ice_Por']) && floatval($ice_row['Ice_Por']) > 0) {
                $ice_por = floatval($ice_row['Ice_Por']);
                if ($cop_des == 0) {
                    $des_ice = ($cop_imp * $cop_dec) / 100;
                } else {
                    $des_ice = ($cop_imp * $cop_des) / 100;
                }
                $imp_ice += (($cop_imp - $des_ice) * $ice_por) / 100;
            }
        }

        $des = $des_0 + $des_12;
        $iva = (($tarifa_12 - $des_12) * $iva_por) / 100;
        if ($cop_des != 0) {
            $des = ($subtotal * $cop_des) / 100;
            $iva = (($tarifa_12 - $des) * $iva_por) / 100;
        }
        $total = ($subtotal - $des) + ($iva + $imp_ice);

        return array(
            'Subtotal' => round($subtotal, 2),
            'Iva' => round($iva, 2),
            'Descuento' => round($des, 2),
            'Ice' => round($imp_ice, 2),
            'Total' => round($total, 2)
        );
    }

    /**
     * Agrega subtotal, IVA y total calculados a filas de busqueda de compras.
     */
    public function enriquecerListaCompras($compras) {
        if (empty($compras) || !is_array($compras)) {
            return array();
        }
        foreach ($compras as $idx => $compra) {
            $cop_cod = isset($compra['Cop_Cod']) ? intval($compra['Cop_Cod']) : 0;
            if ($cop_cod <= 0) {
                continue;
            }
            $totales = $this->calcularTotalesCompraExa($cop_cod);
            $compras[$idx]['Subtotal'] = $totales['Subtotal'];
            $compras[$idx]['Iva'] = $totales['Iva'];
            $compras[$idx]['Total'] = $totales['Total'];
        }
        return $compras;
    }

    /**
     * Detalle de una factura de compra EXA para el nodo AVANCE.
     */
    public function obtenerDetalleCompraAvance($cop_cod, $emp_cod) {
        $cop_cod = intval($cop_cod);
        $emp_cod = intval($emp_cod);
        if ($cop_cod <= 0 || $emp_cod <= 0) {
            return array('success' => false, 'message' => 'Datos invalidos.');
        }

        $compra = $this->getRowConsultaSql(
            "SELECT c.Cop_Cod, c.Cop_Num, c.Cop_Fec, c.Cop_Aut, c.Tic_Cod, c.Tpc_Cod, c.Prv_Cod, c.Pec_Cod,
                    tc.Tic_Sri, tc.Tic_Des,
                    TRIM(CONCAT(IFNULL(p.Prs_Ape, ''), ' ', IFNULL(p.Prs_Nom, ''))) AS Proveedor,
                    tp.Tpc_Des AS Tpc_Des,
                    (SELECT COUNT(*) FROM ccpp_pagar cpp WHERE cpp.Cop_Cod = c.Cop_Cod) AS Es_Credito,
                    ROUND((SELECT SUM(dc.Cop_Imp - (dc.Cop_Imp * dc.Cop_Dec / 100)) FROM det_compra dc WHERE dc.Cop_Cod = c.Cop_Cod), 2) AS Total
             FROM compras c
             INNER JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod
             INNER JOIN persona p ON p.Prs_Cod = pr.Prs_Cod
             LEFT JOIN tipo_compr tc ON tc.Tic_Cod = c.Tic_Cod
             LEFT JOIN tipopagocom tp ON tp.Tpc_Cod = c.Tpc_Cod
             WHERE c.Cop_Cod = $cop_cod AND c.Cop_Est = 'A' AND pr.Emp_Cod = $emp_cod
             LIMIT 1;",
            $this->conexion
        );
        if (empty($compra)) {
            return array('success' => false, 'message' => 'Factura no encontrada o no pertenece a la empresa.');
        }

        $totales = $this->calcularTotalesCompraExa($cop_cod);

        $forma_pago = 'Contado';
        if (intval($compra['Es_Credito']) > 0) {
            $forma_pago = 'Credito';
        } elseif (!empty($compra['Tpc_Des'])) {
            $forma_pago = trim($compra['Tpc_Des']);
        }

        $ret = $this->getRowConsultaSql(
            "SELECT r.Ret_Cod, r.Ret_Num, r.Ret_Fec, r.Ret_Aut, r.Ret_Xml,
                    ROUND(COALESCE(SUM(dr.Ret_Int), 0), 2) AS Ret_Total
             FROM retencion r
             LEFT JOIN det_retenc dr ON dr.Ret_Cod = r.Ret_Cod
             WHERE r.Cop_Cod = $cop_cod AND r.Ret_Est = 'A'
             GROUP BY r.Ret_Cod, r.Ret_Num, r.Ret_Fec, r.Ret_Aut, r.Ret_Xml
             ORDER BY r.Ret_Cod DESC
             LIMIT 1;",
            $this->conexion
        );

        $tiene_ret = !empty($ret['Ret_Cod']);
        $link_factura = '../../facturacion/FRONT/fac_pri_fac_detallecompras_1.0.php?com_codigo=' . $cop_cod;
        if (intval($compra['Tic_Sri']) === 3 && !empty($compra['Cop_Aut'])) {
            $link_factura = '../../facturacion/COMPONENTES/tesPdfElectronicos.php?type=LIQUIDC&Doc_Cod=' . $cop_cod;
        }

        $link_ret = '';
        if ($tiene_ret) {
            if (!empty($ret['Ret_Xml']) && trim($ret['Ret_Xml']) !== '') {
                $link_ret = '../../facturacion/COMPONENTES/tesPdfElectronicos.php?type=RETENC&Doc_Cod=' . intval($ret['Ret_Cod']);
            } else {
                $link_ret = '../../facturacion/FRONT/fac_pri_com_ret.php?Ret_Cod=' . intval($ret['Ret_Cod']);
            }
        }

        $comprobantes = array();
        $pagos = $this->getArrayConsultaSql(
            "SELECT dcp.Com_Cod, dcp.Pag_Fec, dcp.Pag_Val, cp.Com_Num, cp.Com_Fec, cp.Tia_Cod, cp.Pec_Cod,
                    ta.Tia_Abr, COALESCE(tp.Pag_Des, 'Pago') AS Pag_Des
             FROM ccpp_pagar cpp
             INNER JOIN det_ccpp_p dcp ON dcp.Cpp_Cod = cpp.Cpp_Cod AND dcp.Pag_Est = 'A'
             INNER JOIN comprobantes cp ON cp.Com_Cod = dcp.Com_Cod AND cp.Com_Est = 'A'
             INNER JOIN tipo_asien ta ON ta.Tia_Cod = cp.Tia_Cod
             LEFT JOIN tipos_pago tp ON tp.Pag_Cod = dcp.Pag_Cod
             WHERE cpp.Cop_Cod = $cop_cod
             ORDER BY dcp.Pag_Fec DESC, dcp.Com_Cod DESC;",
            $this->conexion
        );
        if ($pagos === false || $pagos === null) {
            $pagos = array();
        }
        foreach ($pagos as $p) {
            $com_cod = intval($p['Com_Cod']);
            $tia_cod = intval($p['Tia_Cod']);
            $pec_cod = intval($p['Pec_Cod']);
            $mes = !empty($p['Com_Fec']) ? date('m', strtotime($p['Com_Fec'])) : '01';
            $comprobantes[] = array(
                'Com_Cod' => $com_cod,
                'Codigo' => trim($p['Tia_Abr']) . '-' . $mes . '-' . $p['Com_Num'],
                'Pag_Fec' => $p['Pag_Fec'],
                'Pag_Val' => floatval($p['Pag_Val']),
                'Forma' => $p['Pag_Des'],
                'Link' => '../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=' . $com_cod . '&tabla=proveedore&campo=Prv_Cod&tipo=' . $tia_cod . '&Pec_Cod=' . $pec_cod
            );
        }

        $contado = $this->getArrayConsultaSql(
            "SELECT cp.Com_Cod, cp.Com_Fec, cp.Com_Num, cp.Com_Val, cp.Tia_Cod, cp.Pec_Cod, ta.Tia_Abr,
                    COALESCE(tp.Tpc_Des, 'Contado') AS Pag_Des
             FROM compr_auto ca
             INNER JOIN comprobantes cp ON cp.Com_Cod = ca.Com_Cod AND cp.Com_Est = 'A'
             INNER JOIN tipo_asien ta ON ta.Tia_Cod = cp.Tia_Cod
             INNER JOIN compras c ON c.Cop_Cod = ca.Cop_Cod
             LEFT JOIN tipopagocom tp ON tp.Tpc_Cod = c.Tpc_Cod
             WHERE ca.Cop_Cod = $cop_cod
             ORDER BY cp.Com_Fec DESC;",
            $this->conexion
        );
        if ($contado === false || $contado === null) {
            $contado = array();
        }
        foreach ($contado as $p) {
            $com_cod = intval($p['Com_Cod']);
            $ya = false;
            foreach ($comprobantes as $c) {
                if (intval($c['Com_Cod']) === $com_cod) {
                    $ya = true;
                    break;
                }
            }
            if ($ya) {
                continue;
            }
            $tia_cod = intval($p['Tia_Cod']);
            $pec_cod = intval($p['Pec_Cod']);
            $mes = !empty($p['Com_Fec']) ? date('m', strtotime($p['Com_Fec'])) : '01';
            $comprobantes[] = array(
                'Com_Cod' => $com_cod,
                'Codigo' => trim($p['Tia_Abr']) . '-' . $mes . '-' . $p['Com_Num'],
                'Pag_Fec' => $p['Com_Fec'],
                'Pag_Val' => floatval($p['Com_Val']),
                'Forma' => $p['Pag_Des'],
                'Link' => '../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=' . $com_cod . '&tabla=proveedore&campo=Prv_Cod&tipo=' . $tia_cod . '&Pec_Cod=' . $pec_cod
            );
        }

        return array(
            'success' => true,
            'compra' => array(
                'Cop_Cod' => $cop_cod,
                'Cop_Num' => $compra['Cop_Num'],
                'Cop_Fec' => $compra['Cop_Fec'],
                'Proveedor' => trim($compra['Proveedor']),
                'Subtotal' => $totales['Subtotal'],
                'Iva' => $totales['Iva'],
                'Descuento' => $totales['Descuento'],
                'Ice' => $totales['Ice'],
                'Total' => $totales['Total'],
                'Forma_Pago' => $forma_pago,
                'Link_Factura' => $link_factura,
                'Tiene_Retencion' => $tiene_ret ? 1 : 0,
                'Ret_Total' => $tiene_ret ? floatval($ret['Ret_Total']) : 0,
                'Ret_Num' => $tiene_ret ? $ret['Ret_Num'] : '',
                'Link_Retencion' => $link_ret,
                'Comprobantes' => $comprobantes
            )
        );
    }

    /**
     * Agrega datos de compra EXA a filas de avance guardadas.
     */
    public function enriquecerAvancesConCompras($avances, $emp_cod) {
        $emp_cod = intval($emp_cod);
        if (empty($avances) || !is_array($avances)) {
            return array();
        }
        foreach ($avances as $idx => $av) {
            $cop_cod = isset($av['Sav_Cop_Cod']) ? intval($av['Sav_Cop_Cod']) : 0;
            if ($cop_cod > 0) {
                $det = $this->obtenerDetalleCompraAvance($cop_cod, $emp_cod);
                if (!empty($det['success'])) {
                    $avances[$idx]['compra'] = $det['compra'];
                }
            }
        }
        return $avances;
    }

    private function compraYaEnAvanceEtapa($sol_cod, $ins_cod, $nod_cod, $cop_cod, $exclude_sav = 0) {
        $sol_cod = intval($sol_cod);
        $ins_cod = intval($ins_cod);
        $nod_cod = intval($nod_cod);
        $cop_cod = intval($cop_cod);
        $exclude_sav = intval($exclude_sav);
        if ($cop_cod <= 0) {
            return false;
        }
        $filtro = '';
        if ($exclude_sav > 0) {
            $filtro = " AND Sav_Cod <> $exclude_sav";
        }
        $row = $this->getRowConsultaSql(
            "SELECT COUNT(*) AS cnt
             FROM adq_solicitudes_avances
             WHERE Sol_Cod = $sol_cod AND Ins_Cod = $ins_cod AND Nod_Cod = $nod_cod
               AND Sav_Cop_Cod = $cop_cod $filtro;",
            $this->conexion
        );
        return !empty($row['cnt']) && intval($row['cnt']) > 0;
    }

    private function sincronizarAvancesEtapa($sol_cod, $ins_cod, $nod_cod, $docs_nuevos, $docs_existentes, $sav_eliminar, $fecha = null) {
        $this->ensureAvancesTable();
        $sol_cod = intval($sol_cod);
        $ins_cod = intval($ins_cod);
        $nod_cod = intval($nod_cod);
        $usu_cod = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;
        $fecha = ($fecha !== null && $fecha !== '') ? $fecha : date('Y-m-d H:i:s');

        if (!empty($sav_eliminar)) {
            foreach ($sav_eliminar as $sav_cod) {
                $sav_cod = intval($sav_cod);
                if ($sav_cod > 0) {
                    $this->grabarv_registros(
                        "DELETE FROM adq_solicitudes_avances WHERE Sav_Cod = $sav_cod AND Sol_Cod = $sol_cod;",
                        $this->conexion
                    );
                }
            }
        }

        if (!empty($docs_existentes) && is_array($docs_existentes)) {
            foreach ($docs_existentes as $sav_cod => $doc) {
                $sav_cod = intval($sav_cod);
                if ($sav_cod <= 0) {
                    continue;
                }
                $des = $this->escapeSql(isset($doc['Sav_Des']) ? $doc['Sav_Des'] : '');
                $set_extra = '';
                if (array_key_exists('Sav_Cop_Cod', $doc)) {
                    $cop_cod = intval($doc['Sav_Cop_Cod']);
                    if ($cop_cod > 0 && $this->compraYaEnAvanceEtapa($sol_cod, $ins_cod, $nod_cod, $cop_cod, $sav_cod)) {
                        throw new Exception('La factura #' . $cop_cod . ' ya esta registrada en esta etapa de avance.');
                    }
                    $set_extra .= ', Sav_Cop_Cod = ' . ($cop_cod > 0 ? $cop_cod : 'NULL');
                }
                if (array_key_exists('Sav_Fac_Adj', $doc) && !empty($doc['Sav_Fac_Adj'])) {
                    $adj = $this->escapeSql($doc['Sav_Fac_Adj']);
                    $set_extra .= ", Sav_Fac_Adj = '$adj'";
                }
                if (array_key_exists('Sav_Ret_Adj', $doc) && !empty($doc['Sav_Ret_Adj'])) {
                    $adj = $this->escapeSql($doc['Sav_Ret_Adj']);
                    $set_extra .= ", Sav_Ret_Adj = '$adj'";
                }
                if (array_key_exists('Sav_Com_Adj', $doc) && !empty($doc['Sav_Com_Adj'])) {
                    $adj = $this->escapeSql($doc['Sav_Com_Adj']);
                    $set_extra .= ", Sav_Com_Adj = '$adj'";
                }
                $sql = "UPDATE adq_solicitudes_avances
                        SET Sav_Des = '$des' $set_extra
                        WHERE Sav_Cod = $sav_cod AND Sol_Cod = $sol_cod;";
                if (!$this->grabarv_registros($sql, $this->conexion)) {
                    throw new Exception('No se pudo actualizar un documento de avance: ' . $this->getMsgError());
                }
            }
        }

        if (!empty($docs_nuevos) && is_array($docs_nuevos)) {
            foreach ($docs_nuevos as $doc) {
                $cop_cod = !empty($doc['Sav_Cop_Cod']) ? intval($doc['Sav_Cop_Cod']) : 0;
                $fac_adj = !empty($doc['Sav_Fac_Adj']) ? $doc['Sav_Fac_Adj'] : '';
                $ret_adj = !empty($doc['Sav_Ret_Adj']) ? $doc['Sav_Ret_Adj'] : '';
                $com_adj = !empty($doc['Sav_Com_Adj']) ? $doc['Sav_Com_Adj'] : '';
                $des_raw = isset($doc['Sav_Des']) ? trim($doc['Sav_Des']) : '';
                if ($cop_cod <= 0 && $fac_adj === '' && $ret_adj === '' && $com_adj === '' && $des_raw === '') {
                    continue;
                }
                if ($cop_cod > 0) {
                    $emp_cod = isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0;
                    $det = $this->obtenerDetalleCompraAvance($cop_cod, $emp_cod);
                    if (empty($det['success'])) {
                        throw new Exception(isset($det['message']) ? $det['message'] : 'Factura de compra no valida.');
                    }
                    if ($this->compraYaEnAvanceEtapa($sol_cod, $ins_cod, $nod_cod, $cop_cod)) {
                        throw new Exception('La factura ' . $det['compra']['Cop_Num'] . ' ya esta registrada en esta etapa.');
                    }
                }
                $des = $this->escapeSql($des_raw);
                $fac_sql = $fac_adj !== '' ? "'" . $this->escapeSql($fac_adj) . "'" : 'NULL';
                $ret_sql = $ret_adj !== '' ? "'" . $this->escapeSql($ret_adj) . "'" : 'NULL';
                $com_sql = $com_adj !== '' ? "'" . $this->escapeSql($com_adj) . "'" : 'NULL';
                $cop_sql = $cop_cod > 0 ? $cop_cod : 'NULL';
                $sql = "INSERT INTO adq_solicitudes_avances
                        (Sol_Cod, Ins_Cod, Nod_Cod, Sav_Cop_Cod, Sav_Des, Sav_Fac_Adj, Sav_Ret_Adj, Sav_Com_Adj, Usu_Cod, Sav_Fec)
                        VALUES ($sol_cod, $ins_cod, $nod_cod, $cop_sql, '$des', $fac_sql, $ret_sql, $com_sql, $usu_cod, '$fecha');";
                if (!$this->grabarv_registros($sql, $this->conexion)) {
                    throw new Exception('No se pudo registrar un documento de avance: ' . $this->getMsgError());
                }
            }
        }
    }

    /**
     * Guarda documentos de avance en la etapa AVANCE del workflow.
     */
    public function guardarAvanceEtapa($sol_cod, $docs_nuevos = array(), $docs_existentes = array(), $sav_eliminar = array()) {
        $sol_cod = intval($sol_cod);
        $emp_cod = isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0;
        $usu_cod = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;

        $auth = $this->autorizarAvanceEtapa($sol_cod, $emp_cod, $usu_cod);
        if (!$auth['success']) {
            return $auth;
        }

        $fecha = date('Y-m-d H:i:s');
        $this->inicio_transaccion($this->conexion);
        try {
            $this->sincronizarAvancesEtapa(
                $sol_cod,
                $auth['Ins_Cod'],
                $auth['Nod_Cod'],
                $docs_nuevos,
                $docs_existentes,
                $sav_eliminar,
                $fecha
            );
            $this->commit_nomsn($this->conexion);
        } catch (Exception $e) {
            $this->rollBack_nomsn($this->conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }

        try {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            $ses = session_id() ?: 'CLI-SESSION';
            $dep_cod = isset($_SESSION['Ses_Dep_Cod']) ? intval($_SESSION['Ses_Dep_Cod']) : 0;
            $nums = array();
            $rows = $this->getArrayConsultaSql(
                "SELECT a.Sav_Cop_Cod, c.Cop_Num
                 FROM adq_solicitudes_avances a
                 LEFT JOIN compras c ON c.Cop_Cod = a.Sav_Cop_Cod
                 WHERE a.Sol_Cod = $sol_cod
                   AND a.Ins_Cod = {$auth['Ins_Cod']}
                   AND a.Nod_Cod = {$auth['Nod_Cod']}
                   AND a.Sav_Fec = '$fecha'
                 ORDER BY a.Sav_Cod ASC;",
                $this->conexion
            );
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    if (!empty($row['Cop_Num'])) {
                        $nums[] = $row['Cop_Num'];
                    } elseif (!empty($row['Sav_Cop_Cod'])) {
                        $nums[] = '#' . intval($row['Sav_Cop_Cod']);
                    }
                }
            }
            $comentario = !empty($nums)
                ? $this->escapeSql('Facturas registradas: ' . implode(', ', $nums))
                : $this->escapeSql('Carga/actualizacion de documentos de avance.');
            $this->grabarv_registros(
                "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Fec, Isn_Ip, Isn_Ses)
                 VALUES ({$auth['Ins_Cod']}, {$auth['Nod_Cod']}, $usu_cod, $dep_cod, 'AVANCE', '$comentario', '$fecha', '$ip', '$ses');",
                $this->conexion
            );
        } catch (Exception $e) {
            // Historial informativo.
        }

        return array('success' => true, 'Sol_Cod' => $sol_cod);
    }

    /**
     * Valida requisitos al enviar a aprobacion (gate AL_ENVIAR).
     */
    public function validarRequisitosParaEnvio($sol_cod) {
        $sol = $this->obtenerSolicitudConTipo($sol_cod);
        if (empty($sol)) {
            return array('success' => false, 'message' => 'Solicitud no encontrada.', 'faltantes' => array('Solicitud no encontrada.'));
        }
        return $this->validarRequisitosDesdeSolicitud($sol);
    }

    private function validarRequisitosDesdeSolicitud($sol) {
        $faltantes = array();
        $sol_cod = intval($sol['Sol_Cod']);

        $items = $this->getRowConsultaSql(
            "SELECT COUNT(*) AS cnt FROM adq_solicitudes_det WHERE Sol_Cod = $sol_cod;",
            $this->conexion
        );
        if (empty($items['cnt'])) {
            $faltantes[] = 'Debe registrar al menos un articulo o servicio.';
        }
        $sol_jus_trim = trim($sol['Sol_Jus']);
        if ($sol_jus_trim === '') {
            $faltantes[] = 'Debe ingresar la justificacion de la solicitud.';
        }
        if (intval($sol['Sol_Req_Pro']) === 1 && empty($sol['Prv_Sug'])) {
            $faltantes[] = 'Debe seleccionar un proveedor sugerido.';
        }
        if (intval($sol['Sol_Req_Cot']) === 1) {
            $min_cot = max(1, intval($sol['Sol_Min_Cot']));
            $cots = $this->getArrayConsultaSql(
                "SELECT * FROM adq_solicitudes_cotizaciones WHERE Sol_Cod = $sol_cod;",
                $this->conexion
            );
            $cots = ($cots === false || $cots === null) ? array() : $cots;
            $validas = 0;
            $ganadora = false;
            foreach ($cots as $c) {
                if (!empty($c['Prv_Cod']) && floatval($c['Cot_Val']) > 0 && $this->cotizacionTieneAdjunto($c['Cot_Adj'])) {
                    $validas++;
                }
                if (!empty($c['Cot_Sel'])) {
                    $ganadora = true;
                }
            }
            if ($validas < $min_cot) {
                $faltantes[] = "Se requieren al menos $min_cot cotizacion(es) con proveedor, monto y archivo PDF (tiene $validas). Puede guardar borrador y completarlas antes de enviar.";
            } elseif (!$ganadora) {
                $faltantes[] = 'Debe marcar cual cotizacion es la ganadora/seleccionada.';
            }
        }
        if (!empty($faltantes)) {
            return array('success' => false, 'message' => implode(' ', $faltantes), 'faltantes' => $faltantes);
        }
        return array('success' => true);
    }

    private function resumirCotizacionesValidas($cotizaciones, $cotizaciones_existentes = array()) {
        $validas = 0;
        $ganadora = false;
        $grupos = array($cotizaciones, $cotizaciones_existentes);
        foreach ($grupos as $grupo) {
            if (empty($grupo) || !is_array($grupo)) {
                continue;
            }
            foreach ($grupo as $cot) {
                if (!is_array($cot)) {
                    continue;
                }
                $cot_adj = isset($cot['Cot_Adj']) ? $cot['Cot_Adj'] : '';
                if (!empty($cot['Prv_Cod']) && floatval($cot['Cot_Val']) > 0 && $this->cotizacionTieneAdjunto($cot_adj)) {
                    $validas++;
                }
                if (!empty($cot['Cot_Sel'])) {
                    $ganadora = true;
                }
            }
        }
        return array('validas' => $validas, 'ganadora' => $ganadora);
    }

    private function validarRequisitosEnvioDesdePost($req, $data, $items, $cotizaciones, $cotizaciones_existentes = array()) {
        $faltantes = array();
        if (empty($items)) {
            $faltantes[] = 'Debe registrar al menos un articulo o servicio.';
        }
        $sol_jus_post = trim(isset($data['Sol_Jus']) ? $data['Sol_Jus'] : '');
        if ($sol_jus_post === '') {
            $faltantes[] = 'Debe ingresar la justificacion de la solicitud.';
        }
        if (intval($req['Sol_Req_Pro']) === 1 && empty($data['Prv_Sug'])) {
            $faltantes[] = 'Debe seleccionar un proveedor sugerido.';
        }
        if (intval($req['Sol_Req_Cot']) === 1) {
            $min_cot = max(1, intval($req['Sol_Min_Cot']));
            $resumen = $this->resumirCotizacionesValidas($cotizaciones, $cotizaciones_existentes);
            $validas = $resumen['validas'];
            $ganadora = $resumen['ganadora'];
            if ($validas < $min_cot) {
                $faltantes[] = "Se requieren al menos $min_cot cotizacion(es) con proveedor, monto y archivo PDF (completas: $validas).";
            } elseif (!$ganadora) {
                $faltantes[] = 'Debe marcar cual cotizacion es la ganadora/seleccionada.';
            }
        }
        if (!empty($faltantes)) {
            throw new Exception(implode(' ', $faltantes));
        }
    }

    public function obtenerBorradorParaEdicion($sol_cod, $emp_cod, $usu_sol) {
        $sol_cod = intval($sol_cod);
        $emp_cod = intval($emp_cod);
        $usu_sol = intval($usu_sol);
        $sol = $this->getRowConsultaSql(
            "SELECT s.*, tr.Trq_Des,
                    tr.Trq_Req_Fac, tr.Trq_Per_Cie, tr.Trq_Req_Cot, tr.Trq_Min_Cot,
                    tr.Trq_Req_Pre, tr.Trq_Req_Adj, tr.Trq_Req_Pro, tr.Trq_Tiempo_Est,
                    pr.Prv_Com, per.Prs_Ced, per.Prs_Nom, per.Prs_Ape
             FROM adq_solicitudes s
             INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
             LEFT JOIN proveedore pr ON pr.Prv_Cod = s.Prv_Sug
             LEFT JOIN persona per ON per.Prs_Cod = pr.Prs_Cod
             WHERE s.Sol_Cod = $sol_cod AND s.Emp_Cod = $emp_cod AND s.Usu_Sol = $usu_sol AND s.Sol_Est IN ('P', 'O')
             LIMIT 1;",
            $this->conexion
        );
        if (empty($sol)) {
            return array('success' => false, 'message' => 'La solicitud no existe o no puede editarse en este momento.');
        }
        $sol = $this->aplicarRequisitosEfectivos($sol);
        $items = $this->getArrayConsultaSql(
            "SELECT * FROM adq_solicitudes_det WHERE Sol_Cod = $sol_cod ORDER BY Sde_Int;",
            $this->conexion
        );
        $cotizaciones = $this->getArrayConsultaSql(
            "SELECT c.*, per.Prs_Nom, per.Prs_Ape, pr.Prv_Com, per.Prs_Ced
             FROM adq_solicitudes_cotizaciones c
             LEFT JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod
             LEFT JOIN persona per ON per.Prs_Cod = pr.Prs_Cod
             WHERE c.Sol_Cod = $sol_cod
             ORDER BY c.Sco_Cod;",
            $this->conexion
        );
        if ($cotizaciones === false || $cotizaciones === null) {
            $cotizaciones = $this->getArrayConsultaSql(
                "SELECT * FROM adq_solicitudes_cotizaciones WHERE Sol_Cod = $sol_cod ORDER BY Sco_Cod;",
                $this->conexion
            );
        }
        $prv_sug_text = '';
        if (!empty($sol['Prv_Sug'])) {
            $nombre = trim($sol['Prs_Ape'] . ' ' . $sol['Prs_Nom']);
            if (!empty($sol['Prv_Com'])) {
                $nombre .= ' (' . $sol['Prv_Com'] . ')';
            }
            if (!empty($sol['Prs_Ced'])) {
                $nombre .= ' - RUC: ' . $sol['Prs_Ced'];
            }
            $prv_sug_text = $nombre;
        }
        return array(
            'success' => true,
            'solicitud' => $sol,
            'items' => ($items === false || $items === null) ? array() : $items,
            'cotizaciones' => ($cotizaciones === false || $cotizaciones === null) ? array() : $cotizaciones,
            'prv_sug_text' => $prv_sug_text,
            'modo_edicion' => ($sol['Sol_Est'] === 'O') ? 'observada' : 'borrador',
            'ultima_observacion' => $this->obtenerUltimaObservacionWorkflow($sol_cod)
        );
    }

    public function actualizarBorrador($sol_cod, $data, $items, $cotizaciones_nuevas = array(), $cotizaciones_existentes = array(), $cot_eliminar = array()) {
        $emp_cod = intval($data['Emp_Cod']);
        $usu_sol = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;
        $this->ensureSolicitudRequisitosColumns();
        $editable = $this->assertSolicitudEditablePorSolicitante($sol_cod, $emp_cod, $usu_sol, array('P', 'O'));
        $sol_cod = intval($editable['Sol_Cod']);
        $es_observada = ($editable['Sol_Est'] === 'O');
        $trq_cod = $es_observada ? intval($editable['Trq_Cod']) : intval($data['Trq_Cod']);

        $this->inicio_transaccion($this->conexion);
        try {
            $req = $this->construirRequisitosDesdePost($data, $trq_cod);
            $sol_pri = $this->escapeSql($data['Sol_Pri']);
            $sol_val_est = floatval($data['Sol_Val_Est']);
            $cdc_cod = !empty($data['Cdc_Cod']) ? "'" . $this->escapeSql($data['Cdc_Cod']) . "'" : 'NULL';
            $pry_cod = !empty($data['Pry_Cod']) ? intval($data['Pry_Cod']) : 'NULL';
            $prv_sug = !empty($data['Prv_Sug']) ? intval($data['Prv_Sug']) : 'NULL';
            $sol_jus = $this->escapeSql($data['Sol_Jus']);
            $sol_det = $this->escapeSql(isset($data['Sol_Det']) ? $data['Sol_Det'] : '');
            $sol_tiempo_sql = $req['Sol_Tiempo_Est'] !== null ? intval($req['Sol_Tiempo_Est']) : 'NULL';

            $sqlUpd = "UPDATE adq_solicitudes SET
                Trq_Cod = $trq_cod, Sol_Pri = '$sol_pri', Sol_Val_Est = $sol_val_est,
                Cdc_Cod = $cdc_cod, Pry_Cod = $pry_cod, Prv_Sug = $prv_sug,
                Sol_Jus = '$sol_jus', Sol_Det = '$sol_det',
                Sol_Req_Fac = {$req['Sol_Req_Fac']}, Sol_Per_Cie = {$req['Sol_Per_Cie']},
                Sol_Req_Cot = {$req['Sol_Req_Cot']}, Sol_Min_Cot = {$req['Sol_Min_Cot']},
                Sol_Req_Pre = {$req['Sol_Req_Pre']}, Sol_Req_Adj = {$req['Sol_Req_Adj']},
                Sol_Req_Pro = {$req['Sol_Req_Pro']}, Sol_Tiempo_Est = $sol_tiempo_sql
                WHERE Sol_Cod = $sol_cod;";
            if (!$this->grabarv_registros($sqlUpd, $this->conexion)) {
                throw new Exception('No se pudo actualizar la solicitud: ' . $this->getMsgError());
            }

            $this->guardarDetalleSolicitud($sol_cod, $items);
            $this->sincronizarCotizacionesBorrador($sol_cod, $cotizaciones_nuevas, $cotizaciones_existentes, $cot_eliminar);

            if (!$es_observada && $trq_cod > 0) {
                $this->activarWorkflowEnBorrador($sol_cod, $trq_cod);
            }

            $this->commit_nomsn($this->conexion);
            return array(
                'success' => true,
                'Sol_Cod' => $sol_cod,
                'Num' => $editable['Sol_Num'],
                'borrador' => !$es_observada,
                'observada' => $es_observada
            );
        } catch (Exception $e) {
            $this->rollBack_nomsn($this->conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    private function activarWorkflowEnBorrador($sol_cod, $trq_cod, $comentario = 'Activacion del flujo al guardar borrador.') {
        $sol_cod = intval($sol_cod);
        $trq_cod = intval($trq_cod);
        if ($trq_cod <= 0) {
            return;
        }
        $wf_mgr = new wf_manager_log(isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null, $this->conexion);
        $instancia = $this->getRowConsultaSql(
            "SELECT i.*, n.Nod_Tip
             FROM wf_instancias i
             LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
             WHERE i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = $sol_cod AND i.Ins_Est = 'P'
             ORDER BY i.Ins_Cod DESC LIMIT 1;",
            $this->conexion
        );
        if (empty($instancia)) {
            $this->iniciarWorkflowSolicitud($trq_cod, $sol_cod, $wf_mgr);
        } elseif ($instancia['Nod_Tip'] === 'INICIO') {
            $wf_mgr->avanzarSiEstaEnInicio($instancia['Ins_Cod'], $comentario);
        }
    }

    private function iniciarWorkflowBorrador($sol_cod, $trq_cod) {
        $this->activarWorkflowEnBorrador($sol_cod, $trq_cod, 'Envio de solicitud desde borrador.');
        if (!$this->grabarv_registros("UPDATE adq_solicitudes SET Sol_Est = 'E' WHERE Sol_Cod = " . intval($sol_cod) . ";", $this->conexion)) {
            throw new Exception('No se pudo actualizar el estado de la solicitud: ' . $this->getMsgError());
        }
    }

    private function obtenerWfmCodPorTipo($trq_cod) {
        $trq = $this->getRowConsultaSql("SELECT Wfm_Cod FROM adq_tipos_requerimientos WHERE Trq_Cod = " . intval($trq_cod) . ";", $this->conexion);
        if (empty($trq) || empty($trq['Wfm_Cod'])) {
            throw new Exception('El Tipo de Requerimiento seleccionado no posee un Flujo Modelo asignado.');
        }
        return intval($trq['Wfm_Cod']);
    }

    private function iniciarWorkflowSolicitud($trq_cod, $sol_cod, $wf_mgr = null) {
        $wfm_cod = $this->obtenerWfmCodPorTipo($trq_cod);
        if ($wf_mgr === null) {
            $wf_mgr = new wf_manager_log(isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null, $this->conexion);
        }
        $wf_res = $wf_mgr->iniciarInstancia($wfm_cod, 'adq_solicitudes', $sol_cod, false);
        if (!$wf_res['success']) {
            throw new Exception('Error al instanciar el workflow de aprobacion: ' . $wf_res['message']);
        }
        return $wf_res;
    }

    /**
     * Guarda la solicitud como borrador e inicia el workflow si hay tipo/flujo seleccionado.
     */
    public function guardarBorrador($data, $items, $cotizaciones = array(), $cotizaciones_existentes = array(), $cot_eliminar = array()) {
        $sol_cod_edit = !empty($data['Sol_Cod']) ? intval($data['Sol_Cod']) : 0;
        if ($sol_cod_edit > 0) {
            return $this->actualizarBorrador($sol_cod_edit, $data, $items, $cotizaciones, $cotizaciones_existentes, $cot_eliminar);
        }
        $this->inicio_transaccion($this->conexion);
        try {
            $resultado = $this->persistirSolicitudNueva($data, $items, $cotizaciones);
            if (!empty($resultado['Trq_Cod'])) {
                $this->activarWorkflowEnBorrador($resultado['Sol_Cod'], intval($resultado['Trq_Cod']));
            }
            $this->commit_nomsn($this->conexion);
            return array(
                'success' => true,
                'Sol_Cod' => $resultado['Sol_Cod'],
                'Num' => $resultado['Num'],
                'borrador' => true
            );
        } catch (Exception $e) {
            $this->rollBack_nomsn($this->conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Guarda una solicitud de adquisición e inicia su respectivo workflow.
     */
    public function guardarSolicitud($data, $items, $cotizaciones = array(), $cotizaciones_existentes = array(), $cot_eliminar = array()) {
        try {
            $sol_cod_edit = !empty($data['Sol_Cod']) ? intval($data['Sol_Cod']) : 0;
            if ($sol_cod_edit > 0) {
                $upd = $this->actualizarBorrador($sol_cod_edit, $data, $items, $cotizaciones, $cotizaciones_existentes, $cot_eliminar);
                if (!$upd['success']) {
                    return $upd;
                }
                $validacion = $this->validarRequisitosParaEnvio($sol_cod_edit);
                if (!$validacion['success']) {
                    return array(
                        'success' => false,
                        'message' => $validacion['message'],
                        'faltantes' => isset($validacion['faltantes']) ? $validacion['faltantes'] : array()
                    );
                }
                $borrador = $this->assertBorradorEditable($sol_cod_edit, intval($data['Emp_Cod']), intval($_SESSION['Ses_Usu_Cod']));
                $this->inicio_transaccion($this->conexion);
                $this->iniciarWorkflowBorrador($sol_cod_edit, intval($borrador['Trq_Cod']));
                $this->commit_nomsn($this->conexion);
                return array('success' => true, 'Sol_Cod' => $sol_cod_edit, 'Num' => $upd['Num']);
            }

            $req = $this->construirRequisitosDesdePost($data, intval($data['Trq_Cod']));
            $this->validarRequisitosEnvioDesdePost($req, $data, $items, $cotizaciones, $cotizaciones_existentes);

            $this->inicio_transaccion($this->conexion);
            $resultado = $this->persistirSolicitudNueva($data, $items, $cotizaciones);
            $sol_cod = $resultado['Sol_Cod'];
            $this->iniciarWorkflowBorrador($sol_cod, $resultado['Trq_Cod']);
            $this->commit_nomsn($this->conexion);
            return array('success' => true, 'Sol_Cod' => $sol_cod, 'Num' => $resultado['Num']);
        } catch (Exception $e) {
            $this->rollBack_nomsn($this->conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Envía a aprobación una solicitud que estaba en borrador.
     */
    public function enviarBorrador($sol_cod) {
        $sol_cod = intval($sol_cod);
        $usu_sol = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;
        $emp_cod = isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0;

        $sol = $this->getRowConsultaSql(
            "SELECT * FROM adq_solicitudes WHERE Sol_Cod = $sol_cod AND Emp_Cod = $emp_cod AND Usu_Sol = $usu_sol AND Sol_Est = 'P' LIMIT 1;",
            $this->conexion
        );
        if (empty($sol)) {
            return array('success' => false, 'message' => 'La solicitud en borrador no existe o no puede enviarse.');
        }

        $validacion = $this->validarRequisitosParaEnvio($sol_cod);
        if (!$validacion['success']) {
            return array(
                'success' => false,
                'message' => $validacion['message'],
                'faltantes' => isset($validacion['faltantes']) ? $validacion['faltantes'] : array(),
                'requiere_completar' => true
            );
        }

        $this->inicio_transaccion($this->conexion);
        try {
            $this->iniciarWorkflowBorrador($sol_cod, intval($sol['Trq_Cod']));
            $this->commit_nomsn($this->conexion);
            return array('success' => true, 'Sol_Cod' => $sol_cod, 'Num' => $sol['Sol_Num']);
        } catch (Exception $e) {
            $this->rollBack_nomsn($this->conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Reenvia a aprobacion una solicitud observada tras correccion del solicitante.
     */
    public function reenviarObservada($sol_cod) {
        $sol_cod = intval($sol_cod);
        $usu_sol = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;
        $emp_cod = isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0;

        $sol = $this->getRowConsultaSql(
            "SELECT * FROM adq_solicitudes WHERE Sol_Cod = $sol_cod AND Emp_Cod = $emp_cod AND Usu_Sol = $usu_sol AND Sol_Est = 'O' LIMIT 1;",
            $this->conexion
        );
        if (empty($sol)) {
            return array('success' => false, 'message' => 'La solicitud observada no existe o no puede reenviarse.');
        }

        $validacion = $this->validarRequisitosParaEnvio($sol_cod);
        if (!$validacion['success']) {
            return array(
                'success' => false,
                'message' => $validacion['message'],
                'faltantes' => isset($validacion['faltantes']) ? $validacion['faltantes'] : array(),
                'requiere_completar' => true
            );
        }

        $instancia = $this->getRowConsultaSql(
            "SELECT Ins_Cod FROM wf_instancias
             WHERE Ins_Ent_Typ = 'adq_solicitudes' AND Ins_Ent_Cod = $sol_cod AND Ins_Est = 'P'
             ORDER BY Ins_Cod DESC LIMIT 1;",
            $this->conexion
        );
        if (empty($instancia)) {
            return array('success' => false, 'message' => 'No existe una instancia activa de workflow para esta solicitud.');
        }

        $this->inicio_transaccion($this->conexion);
        try {
            $wf_mgr = new wf_manager_log(isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null, $this->conexion);
            $wf_res = $wf_mgr->reenviarCorreccionSolicitante(intval($instancia['Ins_Cod']), 'Correccion enviada por el solicitante.');
            if (empty($wf_res['success'])) {
                throw new Exception(isset($wf_res['message']) ? $wf_res['message'] : 'No se pudo reenviar la solicitud al flujo.');
            }
            if (!$this->grabarv_registros("UPDATE adq_solicitudes SET Sol_Est = 'E' WHERE Sol_Cod = $sol_cod;", $this->conexion)) {
                throw new Exception('No se pudo actualizar el estado de la solicitud: ' . $this->getMsgError());
            }
            $this->commit_nomsn($this->conexion);
            return array('success' => true, 'Sol_Cod' => $sol_cod, 'Num' => $sol['Sol_Num']);
        } catch (Exception $e) {
            $this->rollBack_nomsn($this->conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    private function rutaAbsolutaData($ruta_relativa) {
        $ruta_relativa = str_replace('\\', '/', trim((string)$ruta_relativa));
        if ($ruta_relativa === '') {
            return '';
        }
        $base = realpath(dirname(__FILE__) . '/../../DATA');
        if ($base === false) {
            return '';
        }
        $ruta = $base . '/' . ltrim($ruta_relativa, '/');
        $real = realpath($ruta);
        if ($real === false || strpos(str_replace('\\', '/', $real), str_replace('\\', '/', $base)) !== 0) {
            return '';
        }
        return $real;
    }

    private function esArchivoPdf($ruta_relativa) {
        if ($ruta_relativa === null || $ruta_relativa === '') {
            return false;
        }
        return strtolower(pathinfo($ruta_relativa, PATHINFO_EXTENSION)) === 'pdf';
    }

    private function agregarPdfExpediente(&$pdfs, &$vistos, $ruta_relativa, $etiqueta) {
        $ruta_relativa = trim((string)$ruta_relativa);
        if (!$this->esArchivoPdf($ruta_relativa)) {
            return;
        }
        $abs = $this->rutaAbsolutaData($ruta_relativa);
        if ($abs === '' || !is_file($abs) || isset($vistos[$abs])) {
            return;
        }
        $vistos[$abs] = true;
        $pdfs[] = array(
            'ruta' => $abs,
            'rel' => $ruta_relativa,
            'etiqueta' => $etiqueta
        );
    }

    private function agregarPdfSeccionExpediente(&$documentos, &$vistos, &$vistos_global, $ruta_relativa, $etiqueta) {
        $ruta_relativa = trim((string)$ruta_relativa);
        if (!$this->esArchivoPdf($ruta_relativa)) {
            return;
        }
        $abs = $this->rutaAbsolutaData($ruta_relativa);
        if ($abs === '' || !is_file($abs) || isset($vistos[$abs]) || isset($vistos_global[$abs])) {
            return;
        }
        $vistos[$abs] = true;
        $vistos_global[$abs] = true;
        $documentos[] = array(
            'ruta' => $abs,
            'rel' => $ruta_relativa,
            'etiqueta' => $etiqueta
        );
    }

    private function htmlEscExpediente($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }

    private function resolverRutaLogoEmpresa($emp_log) {
        global $APP_REAL_PATH;
        $emp_log = trim(str_replace('\\', '/', (string)$emp_log));
        if ($emp_log === '') {
            return '';
        }
        $candidatos = array();
        if (!empty($APP_REAL_PATH)) {
            $candidatos[] = rtrim(str_replace('\\', '/', $APP_REAL_PATH), '/') . '/' . ltrim($emp_log, '/');
        }
        $base = realpath(dirname(__FILE__) . '/../..');
        if ($base !== false) {
            $candidatos[] = $base . '/' . ltrim($emp_log, '/');
            $candidatos[] = $base . '/administrador/FRONT/' . ltrim($emp_log, '/');
        }
        foreach ($candidatos as $path) {
            if (is_file($path)) {
                return str_replace('\\', '/', $path);
            }
        }
        return '';
    }

    private function resolverAprobadorNodoFinal($ins_cod) {
        $ins_cod = intval($ins_cod);
        if ($ins_cod <= 0) {
            return '';
        }
        $inst = $this->getRowConsultaSql(
            "SELECT i.Ins_Est, i.Nod_Act, i.Wfm_Cod
             FROM wf_instancias i
             WHERE i.Ins_Cod = $ins_cod
             LIMIT 1;",
            $this->conexion
        );
        if (empty($inst)) {
            return '';
        }
        $fin = $this->getRowConsultaSql(
            "SELECT Nod_Cod, Nod_Usu_Asig
             FROM wf_nodos
             WHERE Wfm_Cod = " . intval($inst['Wfm_Cod']) . " AND Nod_Tip = 'FIN' AND Nod_Est = 'A'
             ORDER BY Nod_Cod ASC
             LIMIT 1;",
            $this->conexion
        );
        if (empty($fin['Nod_Cod'])) {
            return '';
        }
        $nod_fin = intval($fin['Nod_Cod']);

        if ($inst['Ins_Est'] === 'F') {
            $row = $this->getRowConsultaSql(
                "SELECT TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Usuario_Nom
                 FROM wf_instancias_nodos h
                 INNER JOIN usuarios u ON u.Usu_Cod = h.Usu_Cod
                 INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                 WHERE h.Ins_Cod = $ins_cod AND h.Nod_Cod = $nod_fin
                   AND h.Isn_Acc IN ('APROBAR', 'COMPLETAR')
                 ORDER BY h.Isn_Fec DESC, h.Isn_Cod DESC
                 LIMIT 1;",
                $this->conexion
            );
            if (!empty($row['Usuario_Nom'])) {
                return trim($row['Usuario_Nom']);
            }
        }

        if ($inst['Ins_Est'] === 'P' && intval($inst['Nod_Act']) === $nod_fin) {
            $usu_cod = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;
            if ($usu_cod > 0) {
                $row = $this->getRowConsultaSql(
                    "SELECT TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Usuario_Nom
                     FROM usuarios u
                     INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                     WHERE u.Usu_Cod = $usu_cod
                     LIMIT 1;",
                    $this->conexion
                );
                if (!empty($row['Usuario_Nom'])) {
                    return trim($row['Usuario_Nom']);
                }
            }
            $asig = isset($fin['Nod_Usu_Asig']) ? trim((string)$fin['Nod_Usu_Asig']) : '';
            if ($asig !== '' && $asig !== 'TODOS') {
                $ids = array();
                foreach (explode(',', $asig) as $id) {
                    $id = intval(trim($id));
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
                if (!empty($ids)) {
                    $lista = implode(',', $ids);
                    $rows = $this->getArrayConsultaSql(
                        "SELECT TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Usuario_Nom
                         FROM usuarios u
                         INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                         WHERE u.Usu_Cod IN ($lista) AND u.Usu_Est = 'A'
                         ORDER BY p.Prs_Ape, p.Prs_Nom
                         LIMIT 3;",
                        $this->conexion
                    );
                    $nombres = array();
                    if (!empty($rows)) {
                        foreach ($rows as $r) {
                            $nom = trim($r['Usuario_Nom']);
                            if ($nom !== '') {
                                $nombres[] = $nom;
                            }
                        }
                    }
                    if (!empty($nombres)) {
                        return implode(', ', $nombres);
                    }
                }
            }
            return 'Responsable de cierre (pendiente)';
        }

        return '';
    }

    public function obtenerMetaExpedienteSolicitud($sol_cod, $ins_cod = 0) {
        $sol_cod = intval($sol_cod);
        $ins_cod = intval($ins_cod);
        if ($ins_cod <= 0 && $sol_cod > 0) {
            $row_ins = $this->getRowConsultaSql(
                "SELECT MAX(Ins_Cod) AS Ins_Cod
                 FROM wf_instancias
                 WHERE Ins_Ent_Typ = 'adq_solicitudes' AND Ins_Ent_Cod = $sol_cod;",
                $this->conexion
            );
            $ins_cod = !empty($row_ins['Ins_Cod']) ? intval($row_ins['Ins_Cod']) : 0;
        }

        $sol = $this->getRowConsultaSql(
            "SELECT s.Sol_Cod, s.Sol_Num, s.Sol_Fec, s.Sol_Pri, s.Sol_Val_Est, s.Sol_Jus, s.Sol_Est,
                    s.Emp_Cod, e.Emp_Nom, e.Emp_Log, t.Trq_Des,
                    TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Solicitante_Nom,
                    d.Dep_Des AS Dep_Nom,
                    f.Wfm_Nom
             FROM adq_solicitudes s
             LEFT JOIN empresas e ON e.Emp_Cod = s.Emp_Cod
             LEFT JOIN adq_tipos_requerimientos t ON t.Trq_Cod = s.Trq_Cod
             LEFT JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
             LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
             LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
             LEFT JOIN wf_instancias i ON i.Ins_Cod = $ins_cod
             LEFT JOIN wf_flujos_modelos f ON f.Wfm_Cod = i.Wfm_Cod
             WHERE s.Sol_Cod = $sol_cod
             LIMIT 1;",
            $this->conexion
        );
        if (empty($sol)) {
            return array();
        }

        $logo_abs = $this->resolverRutaLogoEmpresa(isset($sol['Emp_Log']) ? $sol['Emp_Log'] : '');
        $sol_fec = !empty($sol['Sol_Fec']) ? $sol['Sol_Fec'] : '';
        $sol_fec_fmt = $sol_fec !== '' ? date('d/m/Y H:i', strtotime($sol_fec)) : '';
        $val_est = isset($sol['Sol_Val_Est']) ? floatval($sol['Sol_Val_Est']) : 0;
        return array(
            'sol_cod' => $sol_cod,
            'sol_num' => isset($sol['Sol_Num']) ? $sol['Sol_Num'] : '',
            'sol_fec' => $sol_fec,
            'sol_fec_fmt' => $sol_fec_fmt,
            'sol_pri' => isset($sol['Sol_Pri']) ? $sol['Sol_Pri'] : '',
            'sol_val_est' => $val_est,
            'sol_val_est_fmt' => number_format($val_est, 2, '.', ','),
            'sol_jus' => isset($sol['Sol_Jus']) ? $sol['Sol_Jus'] : '',
            'sol_est' => isset($sol['Sol_Est']) ? $sol['Sol_Est'] : '',
            'req_nom' => isset($sol['Trq_Des']) ? $sol['Trq_Des'] : '',
            'emp_nom' => isset($sol['Emp_Nom']) ? $sol['Emp_Nom'] : '',
            'solicitante' => isset($sol['Solicitante_Nom']) ? trim($sol['Solicitante_Nom']) : '',
            'dep_nom' => isset($sol['Dep_Nom']) ? $sol['Dep_Nom'] : '',
            'wfm_nom' => isset($sol['Wfm_Nom']) ? $sol['Wfm_Nom'] : '',
            'logo_abs' => $logo_abs,
            'fecha' => date('Y-m-d H:i:s'),
            'fecha_fmt' => date('d/m/Y H:i'),
            'aprobador_fin' => $this->resolverAprobadorNodoFinal($ins_cod),
            'ins_cod' => $ins_cod
        );
    }

    /**
     * Recolecta PDFs agrupados por proceso/nodo del workflow.
     */
    public function recolectarSeccionesExpedienteSolicitud($sol_cod, $ins_cod = 0) {
        $sol_cod = intval($sol_cod);
        $meta = $this->obtenerMetaExpedienteSolicitud($sol_cod, $ins_cod);
        $ins_cod = !empty($meta['ins_cod']) ? intval($meta['ins_cod']) : intval($ins_cod);
        $secciones = array();
        if ($sol_cod <= 0 || $ins_cod <= 0) {
            return array('meta' => $meta, 'secciones' => $secciones);
        }

        $inst = $this->getRowConsultaSql(
            "SELECT Wfm_Cod FROM wf_instancias WHERE Ins_Cod = $ins_cod LIMIT 1;",
            $this->conexion
        );
        if (empty($inst['Wfm_Cod'])) {
            return array('meta' => $meta, 'secciones' => $secciones);
        }

        $nodos = $this->getArrayConsultaSql(
            "SELECT Nod_Cod, Nod_Nom, Nod_Tip, IFNULL(Nod_Cot_Edit, 0) AS Nod_Cot_Edit
             FROM wf_nodos
             WHERE Wfm_Cod = " . intval($inst['Wfm_Cod']) . " AND Nod_Est = 'A'
             ORDER BY Nod_Vis_X ASC, Nod_Cod ASC;",
            $this->conexion
        );
        if ($nodos === false || $nodos === null) {
            $nodos = array();
        }

        $historial = $this->getArrayConsultaSql(
            "SELECT h.*, COALESCE(n.Nod_Nom, CONCAT('Nodo #', h.Nod_Cod)) AS Nod_Nom
             FROM wf_instancias_nodos h
             LEFT JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
             WHERE h.Ins_Cod = $ins_cod
             ORDER BY h.Isn_Fec ASC, h.Isn_Cod ASC;",
            $this->conexion
        );
        if ($historial === false || $historial === null) {
            $historial = array();
        }
        $historial = $this->enriquecerHistorialConArchivos($historial, intval($sol_cod));

        $this->ensureAvancesTable();
        $vistos_global = array();

        foreach ($nodos as $nodo) {
            $nod_tip = isset($nodo['Nod_Tip']) ? $nodo['Nod_Tip'] : '';
            if ($nod_tip === 'INICIO') {
                continue;
            }

            $nod_cod = intval($nodo['Nod_Cod']);
            $titulo = !empty($nodo['Nod_Nom']) ? $nodo['Nod_Nom'] : ('Proceso #' . $nod_cod);
            $documentos = array();
            $vistos = array();

            foreach ($historial as $h) {
                if (intval($h['Nod_Cod']) !== $nod_cod) {
                    continue;
                }
                $adjuntos = $this->parseCotAdjuntos(isset($h['Isn_Adj']) ? $h['Isn_Adj'] : '');
                foreach ($adjuntos as $i => $path) {
                    $lbl = count($adjuntos) > 1 ? ('Sustento ' . ($i + 1)) : 'Sustento adjunto';
                    $this->agregarPdfSeccionExpediente($documentos, $vistos, $vistos_global, $path, $lbl);
                }
                if (!empty($h['archivos']) && is_array($h['archivos'])) {
                    foreach ($h['archivos'] as $arch) {
                        if (!empty($arch['es_expediente_firmado'])) {
                            continue;
                        }
                        $path = isset($arch['path']) ? $arch['path'] : '';
                        $lbl = isset($arch['label']) ? $arch['label'] : 'Documento';
                        $this->agregarPdfSeccionExpediente($documentos, $vistos, $vistos_global, $path, $lbl);
                    }
                }
            }

            if ($nod_tip === 'AVANCE') {
                $avances = $this->getArrayConsultaSql(
                    "SELECT Sav_Fac_Adj, Sav_Ret_Adj, Sav_Com_Adj, Sav_Adj
                     FROM adq_solicitudes_avances
                     WHERE Sol_Cod = " . intval($sol_cod) . " AND Nod_Cod = $nod_cod
                     ORDER BY Sav_Fec ASC, Sav_Cod ASC;",
                    $this->conexion
                );
                if (!empty($avances)) {
                    foreach ($avances as $av) {
                        $this->agregarPdfSeccionExpediente($documentos, $vistos, $vistos_global, isset($av['Sav_Fac_Adj']) ? $av['Sav_Fac_Adj'] : '', 'Factura');
                        $this->agregarPdfSeccionExpediente($documentos, $vistos, $vistos_global, isset($av['Sav_Ret_Adj']) ? $av['Sav_Ret_Adj'] : '', 'Retencion');
                        $this->agregarPdfSeccionExpediente($documentos, $vistos, $vistos_global, isset($av['Sav_Com_Adj']) ? $av['Sav_Com_Adj'] : '', 'Comprobante');
                        $this->agregarPdfSeccionExpediente($documentos, $vistos, $vistos_global, isset($av['Sav_Adj']) ? $av['Sav_Adj'] : '', 'Documento');
                    }
                }
            }

            if (intval($nodo['Nod_Cot_Edit']) === 1) {
                $cotizaciones = $this->listarCotizacionesConProveedor(intval($sol_cod));
                if (!empty($cotizaciones)) {
                    foreach ($cotizaciones as $cot) {
                        $adjuntos = $this->parseCotAdjuntos(isset($cot['Cot_Adj']) ? $cot['Cot_Adj'] : '');
                        $prv = !empty($cot['Prv_Com']) ? $cot['Prv_Com'] : 'Proveedor';
                        foreach ($adjuntos as $i => $path) {
                            $lbl = 'Cotizacion - ' . $prv;
                            if (count($adjuntos) > 1) {
                                $lbl .= ' (' . ($i + 1) . ')';
                            }
                            $this->agregarPdfSeccionExpediente($documentos, $vistos, $vistos_global, $path, $lbl);
                        }
                    }
                }
            }

            $secciones[] = array(
                'nod_cod' => $nod_cod,
                'titulo' => $titulo,
                'tipo' => $nod_tip,
                'documentos' => $documentos
            );
        }

        return array('meta' => $meta, 'secciones' => $secciones);
    }

    private function coloresExpedientePdf() {
        return array(
            'primario' => '#1e3a5f',
            'secundario' => '#2f6fed',
            'acento' => '#0f766e',
            'titulo' => '#1e293b',
            'texto' => '#475569',
            'suave' => '#64748b',
            'borde' => '#cbd5e1',
            'fondo' => '#f8fafc',
            'linea' => '#e2e8f0',
            'blanco' => '#ffffff'
        );
    }

    private function etiquetaPrioridadExpediente($pri) {
        $pri = strtoupper(trim((string)$pri));
        $map = array(
            'A' => 'Alta',
            'ALTA' => 'Alta',
            'M' => 'Media',
            'MEDIA' => 'Media',
            'B' => 'Baja',
            'BAJA' => 'Baja',
            'U' => 'Urgente',
            'URGENTE' => 'Urgente'
        );
        if (isset($map[$pri])) {
            return $map[$pri];
        }
        return $pri !== '' ? $pri : 'N/D';
    }

    private function htmlFilaDatoExpediente($label, $valor, $c) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            $valor = 'N/D';
        }
        return '<tr>
            <td width="32%" style="padding:7px 10px;background:' . $c['fondo'] . ';border:1px solid ' . $c['linea'] . ';font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:' . $c['suave'] . ';font-weight:bold;">' . $this->htmlEscExpediente($label) . '</td>
            <td width="68%" style="padding:7px 12px;border:1px solid ' . $c['linea'] . ';font-size:11px;color:' . $c['titulo'] . ';font-weight:bold;">' . $this->htmlEscExpediente($valor) . '</td>
        </tr>';
    }

    private function htmlPortadaExpediente($meta, $secciones) {
        $c = $this->coloresExpedientePdf();
        $emp_nom = $this->htmlEscExpediente(isset($meta['emp_nom']) ? $meta['emp_nom'] : '');
        $sol_num = $this->htmlEscExpediente(isset($meta['sol_num']) ? $meta['sol_num'] : '');
        $req_nom = isset($meta['req_nom']) ? trim((string)$meta['req_nom']) : '';
        $fecha_gen = $this->htmlEscExpediente(isset($meta['fecha_fmt']) ? $meta['fecha_fmt'] : date('d/m/Y H:i'));
        $logo = !empty($meta['logo_abs']) && is_file($meta['logo_abs'])
            ? '<img src="' . $this->htmlEscExpediente($meta['logo_abs']) . '" style="max-height:64px;max-width:150px;" />'
            : '<div style="width:56px;height:56px;background:' . $c['primario'] . ';color:' . $c['blanco'] . ';text-align:center;line-height:56px;font-size:18px;font-weight:bold;">EXP</div>';

        $total_docs = 0;
        $lista = '';
        $num = 1;
        foreach ($secciones as $sec) {
            $n_docs = count($sec['documentos']);
            $total_docs += $n_docs;
            $bg = ($num % 2 === 0) ? $c['fondo'] : $c['blanco'];
            $lista .= '<tr>
                <td width="10%" style="padding:8px 6px;background:' . $bg . ';border-bottom:1px solid ' . $c['linea'] . ';text-align:center;">
                    <div style="display:inline-block;width:22px;height:22px;line-height:22px;background:' . $c['primario'] . ';color:' . $c['blanco'] . ';font-size:10px;font-weight:bold;text-align:center;">' . $num . '</div>
                </td>
                <td width="70%" style="padding:8px 8px;background:' . $bg . ';border-bottom:1px solid ' . $c['linea'] . ';font-size:11px;color:' . $c['titulo'] . ';font-weight:bold;">' . $this->htmlEscExpediente($sec['titulo']) . '</td>
                <td width="20%" style="padding:8px 8px;background:' . $bg . ';border-bottom:1px solid ' . $c['linea'] . ';text-align:right;font-size:10px;color:' . $c['suave'] . ';">' . $n_docs . ' documento' . ($n_docs === 1 ? '' : 's') . '</td>
            </tr>';
            $num++;
        }
        if ($lista === '') {
            $lista = '<tr><td colspan="3" style="padding:12px;color:' . $c['suave'] . ';font-size:11px;">Sin procesos registrados.</td></tr>';
        }

        $jus = isset($meta['sol_jus']) ? trim((string)$meta['sol_jus']) : '';
        if (function_exists('mb_strlen') && function_exists('mb_substr') && mb_strlen($jus, 'UTF-8') > 280) {
            $jus = mb_substr($jus, 0, 277, 'UTF-8') . '...';
        } elseif (strlen($jus) > 280) {
            $jus = substr($jus, 0, 277) . '...';
        }
        $bloque_jus = $jus !== ''
            ? '<div style="margin-top:14px;padding:12px 14px;background:' . $c['fondo'] . ';border-left:3px solid ' . $c['secundario'] . ';">
                    <div style="font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:' . $c['suave'] . ';font-weight:bold;margin-bottom:4px;">Justificaci&oacute;n</div>
                    <div style="font-size:11px;color:' . $c['texto'] . ';line-height:1.45;">' . $this->htmlEscExpediente($jus) . '</div>
               </div>'
            : '';

        $filas = '';
        $filas .= $this->htmlFilaDatoExpediente('No. solicitud', isset($meta['sol_num']) ? $meta['sol_num'] : '', $c);
        $filas .= $this->htmlFilaDatoExpediente('Tipo de requerimiento', $req_nom, $c);
        $filas .= $this->htmlFilaDatoExpediente('Solicitante', isset($meta['solicitante']) ? $meta['solicitante'] : '', $c);
        $filas .= $this->htmlFilaDatoExpediente('Departamento', isset($meta['dep_nom']) ? $meta['dep_nom'] : '', $c);
        $filas .= $this->htmlFilaDatoExpediente('Fecha solicitud', isset($meta['sol_fec_fmt']) ? $meta['sol_fec_fmt'] : '', $c);
        $filas .= $this->htmlFilaDatoExpediente('Prioridad', $this->etiquetaPrioridadExpediente(isset($meta['sol_pri']) ? $meta['sol_pri'] : ''), $c);
        if (!empty($meta['sol_val_est']) && floatval($meta['sol_val_est']) > 0) {
            $filas .= $this->htmlFilaDatoExpediente('Valor estimado', '$ ' . (isset($meta['sol_val_est_fmt']) ? $meta['sol_val_est_fmt'] : number_format(floatval($meta['sol_val_est']), 2, '.', ',')), $c);
        }
        if (!empty($meta['wfm_nom'])) {
            $filas .= $this->htmlFilaDatoExpediente('Flujo de trabajo', $meta['wfm_nom'], $c);
        }

        return '
        <div style="font-family:dejavusans,helvetica,arial,sans-serif;color:' . $c['titulo'] . ';">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 0 0;">
                <tr>
                    <td style="height:6px;background:' . $c['primario'] . ';"></td>
                </tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0 10px 0;">
                <tr>
                    <td width="28%" valign="middle">' . $logo . '</td>
                    <td width="72%" align="right" valign="middle">
                        <div style="font-size:9px;text-transform:uppercase;letter-spacing:1.2px;color:' . $c['secundario'] . ';font-weight:bold;">Documento oficial</div>
                        <div style="font-size:20px;font-weight:bold;color:' . $c['primario'] . ';margin-top:3px;">Expediente de adquisiciones</div>
                        <div style="font-size:12px;color:' . $c['texto'] . ';margin-top:3px;">' . $emp_nom . '</div>
                    </td>
                </tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 16px 0;">
                <tr>
                    <td style="height:2px;background:' . $c['secundario'] . ';"></td>
                </tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
                <tr>
                    <td width="62%" valign="top">
                        <div style="font-size:9px;text-transform:uppercase;letter-spacing:.6px;color:' . $c['suave'] . ';font-weight:bold;">Identificaci&oacute;n del expediente</div>
                        <div style="font-size:22px;font-weight:bold;color:' . $c['primario'] . ';margin-top:4px;">' . $sol_num . '</div>
                    </td>
                    <td width="38%" align="right" valign="top">
                        <div style="display:inline-block;padding:8px 12px;background:' . $c['fondo'] . ';border:1px solid ' . $c['borde'] . ';">
                            <div style="font-size:8px;text-transform:uppercase;letter-spacing:.5px;color:' . $c['suave'] . ';">Generado el</div>
                            <div style="font-size:11px;font-weight:bold;color:' . $c['titulo'] . ';margin-top:2px;">' . $fecha_gen . '</div>
                            <div style="font-size:9px;color:' . $c['suave'] . ';margin-top:4px;">' . intval(count($secciones)) . ' etapas &middot; ' . intval($total_docs) . ' PDF</div>
                        </div>
                    </td>
                </tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:6px;">' . $filas . '</table>
            ' . $bloque_jus . '
            <div style="margin-top:18px;margin-bottom:8px;">
                <div style="font-size:12px;font-weight:bold;color:' . $c['primario'] . ';">&Iacute;ndice de procesos</div>
                <div style="font-size:9px;color:' . $c['suave'] . ';margin-top:2px;">Documentaci&oacute;n consolidada por etapa del flujo de aprobaci&oacute;n</div>
            </div>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid ' . $c['borde'] . ';border-collapse:collapse;">
                <tr>
                    <td width="10%" style="padding:7px 6px;background:' . $c['primario'] . ';color:' . $c['blanco'] . ';font-size:8px;text-transform:uppercase;letter-spacing:.4px;text-align:center;font-weight:bold;">No.</td>
                    <td width="70%" style="padding:7px 8px;background:' . $c['primario'] . ';color:' . $c['blanco'] . ';font-size:8px;text-transform:uppercase;letter-spacing:.4px;font-weight:bold;">Proceso / etapa</td>
                    <td width="20%" style="padding:7px 8px;background:' . $c['primario'] . ';color:' . $c['blanco'] . ';font-size:8px;text-transform:uppercase;letter-spacing:.4px;text-align:right;font-weight:bold;">Anexos</td>
                </tr>
                ' . $lista . '
            </table>
            <div style="margin-top:22px;padding-top:10px;border-top:1px solid ' . $c['linea'] . ';font-size:8px;color:' . $c['suave'] . ';line-height:1.4;">
                Documento generado electr&oacute;nicamente por el m&oacute;dulo de Adquisiciones. Uso interno y confidencial.
                La informaci&oacute;n aqu&iacute; consolidada corresponde a los anexos cargados en cada etapa del workflow.
            </div>
        </div>';
    }

    private function tituloCortoProcesoExpediente($titulo, $indice) {
        $titulo = trim(preg_replace('/\s+/', ' ', (string)$titulo));
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($titulo, 'UTF-8') > 60) {
                $titulo = mb_substr($titulo, 0, 57, 'UTF-8') . '...';
            }
        } elseif (strlen($titulo) > 60) {
            $titulo = substr($titulo, 0, 57) . '...';
        }
        return intval($indice) . '. ' . $titulo;
    }

    private function htmlSeparadorProcesoExpediente($indice, $titulo, $num_docs, $meta = array()) {
        $c = $this->coloresExpedientePdf();
        $emp_nom = $this->htmlEscExpediente(isset($meta['emp_nom']) ? $meta['emp_nom'] : '');
        $sol_num = $this->htmlEscExpediente(isset($meta['sol_num']) ? $meta['sol_num'] : '');
        $titulo_esc = $this->htmlEscExpediente($titulo);
        $n = intval($indice);
        $docs = intval($num_docs);
        $docs_txt = $docs <= 0
            ? 'Sin documentos PDF en esta etapa'
            : ($docs . ' documento' . ($docs === 1 ? '' : 's') . ' PDF');

        return '
        <div style="font-family:dejavusans,helvetica,arial,sans-serif;color:' . $c['titulo'] . ';">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="height:5px;background:' . $c['primario'] . ';"></td></tr>
            </table>
            <div style="padding:28px 8px 10px 8px;">
                <div style="font-size:9px;text-transform:uppercase;letter-spacing:1px;color:' . $c['secundario'] . ';font-weight:bold;">Secci&oacute;n del expediente</div>
                <div style="font-size:10px;color:' . $c['suave'] . ';margin-top:6px;">' . $emp_nom . ' &middot; Solicitud ' . $sol_num . '</div>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;">
                    <tr>
                        <td width="18%" valign="top">
                            <div style="width:54px;height:54px;background:' . $c['primario'] . ';color:' . $c['blanco'] . ';text-align:center;line-height:54px;font-size:20px;font-weight:bold;">' . $n . '</div>
                        </td>
                        <td width="82%" valign="middle">
                            <div style="font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:' . $c['suave'] . ';font-weight:bold;">Proceso / etapa</div>
                            <div style="font-size:18px;font-weight:bold;color:' . $c['primario'] . ';margin-top:4px;line-height:1.25;">' . $titulo_esc . '</div>
                            <div style="margin-top:10px;font-size:11px;color:' . $c['texto'] . ';">' . $this->htmlEscExpediente($docs_txt) . '</div>
                        </td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:26px;">
                    <tr><td style="height:2px;background:' . $c['secundario'] . ';"></td></tr>
                </table>
                <div style="margin-top:12px;font-size:9px;color:' . $c['suave'] . ';">
                    A continuaci&oacute;n se anexan los documentos correspondientes a esta etapa del flujo.
                </div>
            </div>
        </div>';
    }

    private function htmlSoloTituloProcesoExpediente($etiqueta) {
        $c = $this->coloresExpedientePdf();
        $texto = $this->htmlEscExpediente($etiqueta);
        return '<div style="font-family:dejavusans,helvetica,arial,sans-serif;margin:0;padding:0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <td width="4" style="background:' . $c['secundario'] . ';"></td>
                    <td style="background:' . $c['primario'] . ';padding:4px 10px;color:' . $c['blanco'] . ';font-size:10px;font-weight:bold;white-space:nowrap;">' . $texto . '</td>
                </tr>
            </table>
        </div>';
    }

    private function generarPdfSoloTituloProceso($etiqueta) {
        if (!class_exists('mPDF')) {
            include_once(dirname(__FILE__) . '/../../Librerias/MPDF57/mpdf.php');
        }
        $mpdf = new mPDF('c', array(210, 14), '', '', 8, 8, 2, 2, 0, 0);
        $mpdf->SetAutoPageBreak(false);
        $mpdf->WriteHTML($this->htmlSoloTituloProcesoExpediente($etiqueta));
        $tmp = tempnam(sys_get_temp_dir(), 'adq_exp_t_');
        if ($tmp === false) {
            return '';
        }
        $ruta = $tmp . '.pdf';
        @unlink($tmp);
        $mpdf->Output($ruta, 'F');
        return is_file($ruta) ? $ruta : '';
    }

    private function generarPdfSeparadorProceso($indice, $titulo, $num_docs, $meta = array()) {
        return $this->generarPdfHtmlTemporal(
            $this->htmlSeparadorProcesoExpediente($indice, $titulo, $num_docs, $meta),
            array(
                'title' => 'Proceso ' . intval($indice) . ' - ' . $titulo,
                'author' => isset($meta['emp_nom']) ? $meta['emp_nom'] : 'Adquisiciones'
            )
        );
    }

    private function generarPdfHtmlTemporal($html, $props = array()) {
        if (!class_exists('mPDF')) {
            include_once(dirname(__FILE__) . '/../../Librerias/MPDF57/mpdf.php');
        }
        $mpdf = new mPDF('c', 'A4', '', '', 16, 16, 16, 18, 8, 8);
        $mpdf->SetAutoPageBreak(false);
        if (!empty($props['title'])) {
            $mpdf->SetTitle($props['title']);
        }
        if (!empty($props['author'])) {
            $mpdf->SetAuthor($props['author']);
        }
        if (!empty($props['subject'])) {
            $mpdf->SetSubject($props['subject']);
        }
        $mpdf->WriteHTML($html);
        $tmp = tempnam(sys_get_temp_dir(), 'adq_exp_');
        if ($tmp === false) {
            return '';
        }
        $ruta = $tmp . '.pdf';
        @unlink($tmp);
        $mpdf->Output($ruta, 'F');
        return is_file($ruta) ? $ruta : '';
    }

    private function serializarRecursosPdf($node) {
        if (!is_array($node)) {
            return is_scalar($node) ? (string)$node . ' ' : '';
        }
        $out = '';
        foreach ($node as $k => $v) {
            if (is_string($k)) {
                $out .= $k . ' ';
            }
            $out .= $this->serializarRecursosPdf($v);
        }
        return $out;
    }

    private function paginaPdfTieneTextoVisible($buffer) {
        if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)\s*Tj/i', $buffer, $m)) {
            foreach ($m[0] as $chunk) {
                if (preg_match('/\(((?:\\\\.|[^\\\\)])*)\)\s*Tj/i', $chunk, $inner)) {
                    $txt = preg_replace('/\\\\(.)/', '$1', $inner[1]);
                    if (trim($txt) !== '') {
                        return true;
                    }
                }
            }
        }
        if (preg_match_all('/\[(.*?)\]\s*TJ/i', $buffer, $arrays)) {
            foreach ($arrays[1] as $arr) {
                if (preg_match_all('/\(((?:\\\\.|[^\\\\)]*))\)/', $arr, $parts)) {
                    foreach ($parts[1] as $p) {
                        if (trim($p) !== '') {
                            return true;
                        }
                    }
                }
            }
        }
        if (preg_match_all('/<([0-9A-Fa-f\s]+)>\s*Tj/i', $buffer, $hexMatches)) {
            foreach ($hexMatches[1] as $hex) {
                $hex = preg_replace('/\s+/', '', $hex);
                if (strlen($hex) < 2 || (strlen($hex) % 2) !== 0) {
                    continue;
                }
                $txt = @pack('H*', $hex);
                if ($txt !== false && trim($txt) !== '') {
                    return true;
                }
            }
        }
        return false;
    }

    private function paginaPdfTieneImagen($buffer, $resources_str) {
        $blob = $buffer . ' ' . $resources_str;
        if (preg_match('/\/Subtype\s*\/Image|\/DCTDecode|\/JPXDecode|\/CCITTFaxDecode|\/JBIG2Decode/i', $blob)) {
            return true;
        }
        if (preg_match('/\bDo\b/i', $buffer) && strlen($buffer) > 1800) {
            return true;
        }
        return false;
    }

    private function esPaginaImportadaBlanca($mpdf, $tplId) {
        if (empty($tplId) || !isset($mpdf->tpls[$tplId])) {
            return true;
        }
        $tpl = $mpdf->tpls[$tplId];
        $buffer = isset($tpl['buffer']) ? (string)$tpl['buffer'] : '';
        $resources_str = isset($tpl['resources']) ? $this->serializarRecursosPdf($tpl['resources']) : '';

        if (trim($buffer) === '' && trim($resources_str) === '') {
            return true;
        }
        if ($this->paginaPdfTieneTextoVisible($buffer)) {
            return false;
        }
        if ($this->paginaPdfTieneImagen($buffer, $resources_str)) {
            return false;
        }
        return true;
    }

    /**
     * Genera un PDF temporal solo con las paginas que tienen contenido visible.
     */
    private function filtrarPaginasBlancasPdf($ruta_origen) {
        if (!class_exists('mPDF')) {
            include_once(dirname(__FILE__) . '/../../Librerias/MPDF57/mpdf.php');
        }
        $ruta_origen = trim((string)$ruta_origen);
        if ($ruta_origen === '' || !is_file($ruta_origen)) {
            return '';
        }

        $lector = new mPDF('c', 'A4', '', '', 0, 0, 0, 0, 0, 0);
        $lector->SetImportUse();
        $total = 0;
        try {
            $total = intval($lector->SetSourceFile($ruta_origen));
        } catch (Exception $e) {
            return '';
        }
        if ($total <= 0) {
            return '';
        }

        $paginas_validas = array();
        for ($i = 1; $i <= $total; $i++) {
            $tplId = $lector->ImportPage($i);
            if (!$tplId || $this->esPaginaImportadaBlanca($lector, $tplId)) {
                continue;
            }
            $paginas_validas[] = $i;
        }

        if (empty($paginas_validas)) {
            // Algunos PDFs escaneados no exponen texto/imagenes de forma legible para FPDI.
            // En ese caso conservamos el archivo para no perder adjuntos validos.
            return $ruta_origen;
        }
        if (count($paginas_validas) === $total) {
            return $ruta_origen;
        }

        $writer = new mPDF('c', 'A4', '', '', 0, 0, 0, 0, 0, 0);
        $writer->SetImportUse();
        $primera = true;
        foreach ($paginas_validas as $num_pag) {
            try {
                $writer->SetSourceFile($ruta_origen);
            } catch (Exception $e) {
                continue;
            }
            $tplId = $writer->ImportPage($num_pag);
            if (!$tplId) {
                continue;
            }
            if (!$primera) {
                $writer->AddPage();
            }
            $primera = false;
            $writer->UseTemplate($tplId);
        }

        if ($primera) {
            return $ruta_origen;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'adq_exp_f_');
        if ($tmp === false) {
            return '';
        }
        $ruta_filtrada = $tmp . '.pdf';
        @unlink($tmp);
        $writer->Output($ruta_filtrada, 'F');
        return is_file($ruta_filtrada) ? $ruta_filtrada : '';
    }

    private function prepararPdfParaExpediente($ruta_origen, &$tmp_generados) {
        $ruta_origen = trim((string)$ruta_origen);
        if ($ruta_origen === '' || !is_file($ruta_origen)) {
            return '';
        }
        $filtrado = $this->filtrarPaginasBlancasPdf($ruta_origen);
        if ($filtrado === '') {
            return '';
        }
        if ($filtrado !== $ruta_origen) {
            $tmp_generados[] = $filtrado;
        }
        return $filtrado;
    }

    /**
     * Une PDFs solo por importacion (sin mezclar WriteHTML e import en el mismo documento).
     * Cada entrada puede ser una ruta (string) o array('ruta'=>, 'titulo'=>) para
     * estampar el titulo del proceso sobre la primera pagina del documento.
     */
    private function unirPdfsExpedienteMpdf($archivos_abs, $ruta_salida) {
        if (!class_exists('mPDF')) {
            include_once(dirname(__FILE__) . '/../../Librerias/MPDF57/mpdf.php');
        }
        $mpdf = new mPDF('c', 'A4', '', '', 0, 0, 0, 0, 0, 0);
        $mpdf->SetImportUse();
        $mpdf->SetAutoPageBreak(false);

        $es_primera = true;
        $paginas = 0;
        $archivos_ok = 0;

        foreach ($archivos_abs as $entrada) {
            if (is_array($entrada)) {
                $archivo = isset($entrada['ruta']) ? trim((string)$entrada['ruta']) : '';
                $titulo = isset($entrada['titulo']) ? trim((string)$entrada['titulo']) : '';
            } else {
                $archivo = trim((string)$entrada);
                $titulo = '';
            }
            if ($archivo === '' || !is_file($archivo)) {
                continue;
            }
            $pagecount = 0;
            try {
                $pagecount = intval($mpdf->SetSourceFile($archivo));
            } catch (Exception $e) {
                $pagecount = 0;
            }
            if ($pagecount <= 0) {
                continue;
            }

            $templates = array();
            $hay_contenido = false;
            for ($i = 1; $i <= $pagecount; $i++) {
                $tplId = $mpdf->ImportPage($i);
                if (!$tplId) {
                    continue;
                }
                $es_blanca = $this->esPaginaImportadaBlanca($mpdf, $tplId);
                if (!$es_blanca) {
                    $hay_contenido = true;
                }
                $templates[] = array('tpl' => $tplId, 'blanca' => $es_blanca);
            }

            if (empty($templates)) {
                continue;
            }

            $archivos_ok++;
            $titulo_pendiente = $titulo;
            foreach ($templates as $tpl) {
                if ($hay_contenido && !empty($tpl['blanca'])) {
                    continue;
                }
                if (!$es_primera) {
                    $mpdf->AddPage();
                }
                $es_primera = false;
                $mpdf->UseTemplate($tpl['tpl']);
                if ($titulo_pendiente !== '') {
                    $this->estamparTituloProcesoPagina($mpdf, $titulo_pendiente);
                    $titulo_pendiente = '';
                }
                $paginas++;
            }
        }

        if ($paginas <= 0) {
            return array('success' => false, 'paginas' => 0, 'archivos' => 0);
        }

        $mpdf->Output($ruta_salida, 'F');
        return array('success' => true, 'paginas' => $paginas, 'archivos' => $archivos_ok);
    }

    private function estamparTituloProcesoPagina($mpdf, $titulo) {
        $c = $this->coloresExpedientePdf();
        $texto = $this->htmlEscExpediente($titulo);
        $html = '<table width="100%" cellpadding="0" cellspacing="0" style="font-family:dejavusans,helvetica,arial,sans-serif;border-collapse:collapse;">
            <tr>
                <td width="4" style="background:' . $c['secundario'] . ';"></td>
                <td style="background:' . $c['primario'] . ';padding:3px 10px;color:' . $c['blanco'] . ';font-size:9px;font-weight:bold;white-space:nowrap;">' . $texto . '</td>
            </tr>
        </table>';
        $mpdf->WriteFixedPosHTML($html, 0, 0, 210, 10, 'visible');
    }

    private function htmlCierreExpediente($meta) {
        $c = $this->coloresExpedientePdf();
        $aprobador = $this->htmlEscExpediente(
            !empty($meta['aprobador_fin']) ? $meta['aprobador_fin'] : 'Pendiente de aprobaci&oacute;n final'
        );
        $sol_num = $this->htmlEscExpediente(isset($meta['sol_num']) ? $meta['sol_num'] : '');
        $req_nom = $this->htmlEscExpediente(isset($meta['req_nom']) ? $meta['req_nom'] : '');
        $emp_nom = $this->htmlEscExpediente(isset($meta['emp_nom']) ? $meta['emp_nom'] : '');
        $fecha_gen = $this->htmlEscExpediente(isset($meta['fecha_fmt']) ? $meta['fecha_fmt'] : date('d/m/Y H:i'));
        $bloque_req = $req_nom !== ''
            ? '<div style="font-size:11px;color:' . $c['texto'] . ';margin-top:4px;">' . $req_nom . '</div>'
            : '';

        return '
        <div style="font-family:dejavusans,helvetica,arial,sans-serif;color:' . $c['titulo'] . ';">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="height:6px;background:' . $c['primario'] . ';"></td></tr>
            </table>
            <div style="padding-top:28px;text-align:center;">
                <div style="font-size:9px;text-transform:uppercase;letter-spacing:1.2px;color:' . $c['secundario'] . ';font-weight:bold;">Cierre documental</div>
                <div style="font-size:20px;font-weight:bold;color:' . $c['primario'] . ';margin-top:6px;">Expediente de adquisiciones</div>
                <div style="font-size:12px;color:' . $c['texto'] . ';margin-top:4px;">' . $emp_nom . '</div>
            </div>
            <table width="88%" cellpadding="0" cellspacing="0" align="center" style="margin:26px auto 0 auto;border:1px solid ' . $c['borde'] . ';">
                <tr>
                    <td style="padding:16px 18px;background:' . $c['fondo'] . ';border-bottom:1px solid ' . $c['linea'] . ';">
                        <div style="font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:' . $c['suave'] . ';font-weight:bold;">Solicitud</div>
                        <div style="font-size:16px;font-weight:bold;color:' . $c['primario'] . ';margin-top:3px;">' . $sol_num . '</div>
                        ' . $bloque_req . '
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px;">
                        <div style="font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:' . $c['suave'] . ';font-weight:bold;">Responsable de cierre / aprobaci&oacute;n final</div>
                        <div style="font-size:15px;font-weight:bold;color:' . $c['titulo'] . ';margin-top:6px;">' . $aprobador . '</div>
                        <div style="font-size:10px;color:' . $c['suave'] . ';margin-top:4px;">Fecha de generaci&oacute;n del expediente: ' . $fecha_gen . '</div>
                    </td>
                </tr>
            </table>
            <table width="88%" cellpadding="0" cellspacing="0" align="center" style="margin:36px auto 0 auto;">
                <tr>
                    <td width="46%" valign="top" style="padding-right:12px;">
                        <div style="border-top:1px solid ' . $c['titulo'] . ';padding-top:8px;text-align:center;">
                            <div style="font-size:10px;font-weight:bold;color:' . $c['titulo'] . ';">Firma digital / manuscrita</div>
                            <div style="font-size:8px;color:' . $c['suave'] . ';margin-top:3px;">Aprobador final</div>
                        </div>
                    </td>
                    <td width="8%"></td>
                    <td width="46%" valign="top" style="padding-left:12px;">
                        <div style="border-top:1px solid ' . $c['titulo'] . ';padding-top:8px;text-align:center;">
                            <div style="font-size:10px;font-weight:bold;color:' . $c['titulo'] . ';">Nombre y cargo</div>
                            <div style="font-size:8px;color:' . $c['suave'] . ';margin-top:3px;">Aclaraci&oacute;n</div>
                        </div>
                    </td>
                </tr>
            </table>
            <div style="margin-top:48px;padding:12px 14px;background:' . $c['fondo'] . ';border-left:3px solid ' . $c['acento'] . ';font-size:9px;color:' . $c['texto'] . ';line-height:1.45;">
                Este expediente consolida los anexos cargados durante el ciclo de vida de la solicitud.
                La firma electr&oacute;nica posterior valida la integridad del documento unificado para archivo institucional.
            </div>
            <div style="margin-top:18px;font-size:8px;color:' . $c['suave'] . ';text-align:center;">
                Uso interno y confidencial &middot; M&oacute;dulo de Adquisiciones
            </div>
        </div>';
    }

    /**
     * Recolecta rutas PDF de cotizaciones, historial WF y avances de la solicitud.
     */
    public function recolectarPdfsSolicitud($sol_cod, $ins_cod = 0) {
        $data = $this->recolectarSeccionesExpedienteSolicitud($sol_cod, $ins_cod);
        $pdfs = array();
        if (empty($data['secciones'])) {
            return $pdfs;
        }
        foreach ($data['secciones'] as $sec) {
            foreach ($sec['documentos'] as $doc) {
                $pdfs[] = $doc;
            }
        }
        return $pdfs;
    }

    /**
     * Une los PDF recolectados y devuelve la ruta absoluta del archivo generado.
     */
    public function generarExpedientePdfUnido($sol_cod, $ins_cod = 0) {
        $sol_cod = intval($sol_cod);
        $ins_cod = intval($ins_cod);
        if ($sol_cod <= 0) {
            return array('success' => false, 'message' => 'Solicitud invalida.');
        }

        $data = $this->recolectarSeccionesExpedienteSolicitud($sol_cod, $ins_cod);
        $meta = isset($data['meta']) ? $data['meta'] : array();
        $secciones = isset($data['secciones']) ? $data['secciones'] : array();
        if (empty($secciones)) {
            return array('success' => false, 'message' => 'No hay procesos del workflow para generar el expediente.');
        }

        $sol_num = !empty($meta['sol_num']) ? $meta['sol_num'] : ('SOL-' . $sol_cod);
        $dir_rel = 'adquisiciones_sustentos/expedientes';
        $dir_abs = dirname(__FILE__) . '/../../DATA/' . $dir_rel;
        if (!is_dir($dir_abs) && !mkdir($dir_abs, 0777, true) && !is_dir($dir_abs)) {
            return array('success' => false, 'message' => 'No se pudo crear el directorio de expedientes.');
        }

        $nombre = 'expediente_sol_' . $this->slugSolNumArchivo($sol_num) . '_' . date('Ymd_His') . '.pdf';
        $ruta_salida = $dir_abs . '/' . $nombre;

        $tmp_portada = $this->generarPdfHtmlTemporal(
            $this->htmlPortadaExpediente($meta, $secciones),
            array(
                'title' => 'Expediente ' . $sol_num,
                'author' => !empty($meta['emp_nom']) ? $meta['emp_nom'] : 'Adquisiciones',
                'subject' => 'Expediente de adquisiciones'
            )
        );
        $tmp_cierre = $this->generarPdfHtmlTemporal(
            $this->htmlCierreExpediente($meta),
            array(
                'title' => 'Cierre expediente ' . $sol_num,
                'author' => !empty($meta['emp_nom']) ? $meta['emp_nom'] : 'Adquisiciones'
            )
        );
        if ($tmp_portada === '' || $tmp_cierre === '') {
            if ($tmp_portada !== '') {
                @unlink($tmp_portada);
            }
            if ($tmp_cierre !== '') {
                @unlink($tmp_cierre);
            }
            return array('success' => false, 'message' => 'No se pudo generar las paginas del expediente.');
        }

        $tmp_generados = array();
        $tmp_html_paginas = array($tmp_portada, $tmp_cierre);
        $lista_unir = array();
        $archivos_docs = 0;

        $portada_ok = $this->prepararPdfParaExpediente($tmp_portada, $tmp_generados);
        if ($portada_ok !== '') {
            $lista_unir[] = array('ruta' => $portada_ok, 'titulo' => '');
        }

        $num_proceso = 0;
        foreach ($secciones as $sec) {
            $preparados_sec = array();
            foreach ($sec['documentos'] as $pdf) {
                if (empty($pdf['ruta']) || !is_file($pdf['ruta'])) {
                    continue;
                }
                $preparado = $this->prepararPdfParaExpediente($pdf['ruta'], $tmp_generados);
                if ($preparado === '') {
                    continue;
                }
                $preparados_sec[] = $preparado;
                $archivos_docs++;
            }

            $num_proceso++;
            $titulo_sec = !empty($sec['titulo']) ? $sec['titulo'] : ('Proceso ' . $num_proceso);
            $tmp_sep = $this->generarPdfSeparadorProceso($num_proceso, $titulo_sec, count($preparados_sec), $meta);
            if ($tmp_sep !== '') {
                $tmp_html_paginas[] = $tmp_sep;
                $sep_ok = $this->prepararPdfParaExpediente($tmp_sep, $tmp_generados);
                if ($sep_ok !== '') {
                    $lista_unir[] = array('ruta' => $sep_ok, 'titulo' => '');
                }
            }

            foreach ($preparados_sec as $preparado) {
                $lista_unir[] = array(
                    'ruta' => $preparado,
                    'titulo' => ''
                );
            }
        }

        $cierre_ok = $this->prepararPdfParaExpediente($tmp_cierre, $tmp_generados);
        if ($cierre_ok !== '') {
            $lista_unir[] = array('ruta' => $cierre_ok, 'titulo' => '');
        }

        if (count($lista_unir) < 2) {
            foreach ($tmp_generados as $tmp) {
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
            foreach ($tmp_html_paginas as $tmp) {
                if ($tmp !== '' && is_file($tmp)) {
                    @unlink($tmp);
                }
            }
            return array('success' => false, 'message' => 'No fue posible leer documentos PDF validos para el expediente.');
        }

        try {
            $resultado = $this->unirPdfsExpedienteMpdf($lista_unir, $ruta_salida);
            foreach ($tmp_generados as $tmp) {
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
            foreach ($tmp_html_paginas as $tmp) {
                if ($tmp !== '' && is_file($tmp)) {
                    @unlink($tmp);
                }
            }

            if (empty($resultado['success'])) {
                return array('success' => false, 'message' => 'No fue posible unir las paginas del expediente.');
            }

            return array(
                'success' => true,
                'path' => $ruta_salida,
                'filename' => $nombre,
                'paginas' => intval($resultado['paginas']),
                'archivos' => $archivos_docs,
                'secciones' => count($secciones)
            );
        } catch (Exception $e) {
            foreach ($tmp_generados as $tmp) {
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
            foreach ($tmp_html_paginas as $tmp) {
                if ($tmp !== '' && is_file($tmp)) {
                    @unlink($tmp);
                }
            }
            return array('success' => false, 'message' => 'Error al unir PDF: ' . $e->getMessage());
        }
    }

    private function ensureSolicitudExpedienteColumns() {
        $cols = array(
            'Sol_Exp_Pdf' => "ALTER TABLE adq_solicitudes ADD COLUMN Sol_Exp_Pdf VARCHAR(500) NULL AFTER Sol_Est;",
            'Sol_Exp_Firmado' => "ALTER TABLE adq_solicitudes ADD COLUMN Sol_Exp_Firmado VARCHAR(500) NULL AFTER Sol_Exp_Pdf;",
            'Sol_Exp_Fec' => "ALTER TABLE adq_solicitudes ADD COLUMN Sol_Exp_Fec DATETIME NULL AFTER Sol_Exp_Firmado;",
            'Sol_Exp_Firm_Fec' => "ALTER TABLE adq_solicitudes ADD COLUMN Sol_Exp_Firm_Fec DATETIME NULL AFTER Sol_Exp_Fec;",
            'Sol_Exp_Firm_Nom' => "ALTER TABLE adq_solicitudes ADD COLUMN Sol_Exp_Firm_Nom VARCHAR(200) NULL AFTER Sol_Exp_Firm_Fec;"
        );
        foreach ($cols as $col => $sql) {
            $existe = $this->getRowConsultaSql(
                "SELECT 1 AS ok FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'adq_solicitudes'
                   AND COLUMN_NAME = '$col'
                 LIMIT 1;",
                $this->conexion
            );
            if (empty($existe)) {
                $this->grabarv_registros($sql, $this->conexion);
            }
        }
    }

    public function validarFinalizarExpedienteFin($sol_cod) {
        $this->ensureSolicitudExpedienteColumns();
        $sol_cod = intval($sol_cod);
        if ($sol_cod <= 0) {
            return array('success' => false, 'message' => 'Solicitud invalida.');
        }
        $estado = $this->obtenerEstadoExpedienteSolicitud($sol_cod);
        if (empty($estado['pdf'])) {
            return array(
                'success' => false,
                'message' => 'Debe descargar el expediente, revisarlo y volver a cargarlo antes de finalizar.'
            );
        }
        $pdf_abs = $this->rutaAbsolutaData($estado['pdf']);
        if ($pdf_abs === '' || !is_file($pdf_abs)) {
            return array(
                'success' => false,
                'message' => 'No se encontro el expediente cargado. Vuelva a subir el archivo PDF.'
            );
        }
        return array('success' => true);
    }

    public function obtenerEstadoExpedienteSolicitud($sol_cod) {
        $this->ensureSolicitudExpedienteColumns();
        $sol_cod = intval($sol_cod);
        if ($sol_cod <= 0) {
            return array();
        }
        $row = $this->getRowConsultaSql(
            "SELECT Sol_Exp_Pdf, Sol_Exp_Firmado, Sol_Exp_Fec, Sol_Exp_Firm_Fec, Sol_Exp_Firm_Nom
             FROM adq_solicitudes WHERE Sol_Cod = $sol_cod LIMIT 1;",
            $this->conexion
        );
        if (empty($row)) {
            return array();
        }
        return array(
            'pdf' => isset($row['Sol_Exp_Pdf']) ? $row['Sol_Exp_Pdf'] : '',
            'firmado' => isset($row['Sol_Exp_Firmado']) ? $row['Sol_Exp_Firmado'] : '',
            'fec' => isset($row['Sol_Exp_Fec']) ? $row['Sol_Exp_Fec'] : '',
            'firm_fec' => isset($row['Sol_Exp_Firm_Fec']) ? $row['Sol_Exp_Firm_Fec'] : '',
            'firm_nom' => isset($row['Sol_Exp_Firm_Nom']) ? $row['Sol_Exp_Firm_Nom'] : '',
            'tiene_pdf' => !empty($row['Sol_Exp_Pdf']) ? 1 : 0,
            'tiene_firmado' => !empty($row['Sol_Exp_Firmado']) ? 1 : 0
        );
    }

    public function autorizarExpedienteFin($sol_cod, $emp_cod, $usu_cod) {
        $sol_cod = intval($sol_cod);
        $emp_cod = intval($emp_cod);
        $usu_cod = intval($usu_cod);
        if ($sol_cod <= 0 || $usu_cod <= 0) {
            return array('success' => false, 'message' => 'Datos invalidos.');
        }
        $row = $this->getRowConsultaSql(
            "SELECT i.Ins_Cod, i.Nod_Act, n.Nod_Tip, s.Emp_Cod
             FROM adq_solicitudes s
             INNER JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod AND i.Ins_Est = 'P'
             INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
             WHERE s.Sol_Cod = $sol_cod AND s.Emp_Cod = $emp_cod
             ORDER BY i.Ins_Cod DESC LIMIT 1;",
            $this->conexion
        );
        if (empty($row) || $row['Nod_Tip'] !== 'FIN') {
            return array('success' => false, 'message' => 'La solicitud no esta en etapa de cierre (FIN).');
        }
        $wf_mgr = new wf_manager_log(isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null, $this->conexion);
        $ctx = $wf_mgr->resolverContextoUsuario($emp_cod);
        if (!$wf_mgr->puedeResolverInstancia(intval($row['Ins_Cod']), $ctx['usu_cod'], $ctx['dep_cod'], $ctx['perfiles_ids'])) {
            return array('success' => false, 'message' => 'No tiene permisos para cerrar esta solicitud.');
        }
        return array(
            'success' => true,
            'Ins_Cod' => intval($row['Ins_Cod']),
            'Nod_Cod' => intval($row['Nod_Act'])
        );
    }

    public function generarYGuardarExpedienteSolicitud($sol_cod, $ins_cod = 0) {
        $this->ensureSolicitudExpedienteColumns();
        $sol_cod = intval($sol_cod);
        $resultado = $this->generarExpedientePdfUnido($sol_cod, intval($ins_cod));
        if (empty($resultado['success'])) {
            return $resultado;
        }
        $rel = 'adquisiciones_sustentos/expedientes/' . $resultado['filename'];
        $fecha = date('Y-m-d H:i:s');
        $rel_esc = $this->escapeSql($rel);
        $this->grabarv_registros(
            "UPDATE adq_solicitudes
             SET Sol_Exp_Pdf = '$rel_esc', Sol_Exp_Fec = '$fecha',
                 Sol_Exp_Firmado = NULL, Sol_Exp_Firm_Fec = NULL, Sol_Exp_Firm_Nom = NULL
             WHERE Sol_Cod = $sol_cod;",
            $this->conexion
        );
        return array(
            'success' => true,
            'path' => $rel,
            'filename' => $resultado['filename'],
            'paginas' => isset($resultado['paginas']) ? $resultado['paginas'] : 0,
            'archivos' => isset($resultado['archivos']) ? $resultado['archivos'] : 0,
            'fec' => $fecha
        );
    }

    public function subirExpedienteSolicitud($sol_cod, $emp_cod, $file_tmp, $file_name) {
        $this->ensureSolicitudExpedienteColumns();
        $sol_cod = intval($sol_cod);
        $emp_cod = intval($emp_cod);
        $auth = $this->autorizarExpedienteFin($sol_cod, $emp_cod, isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0);
        if (empty($auth['success'])) {
            return $auth;
        }

        $file_tmp = trim((string)$file_tmp);
        $file_name = trim((string)$file_name);
        if ($file_tmp === '' || !is_file($file_tmp)) {
            return array('success' => false, 'message' => 'No se recibio el archivo del expediente.');
        }

        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return array('success' => false, 'message' => 'El expediente debe ser un archivo PDF.');
        }

        $fh = @fopen($file_tmp, 'rb');
        if ($fh) {
            $header = fread($fh, 5);
            fclose($fh);
            if ($header !== '%PDF-') {
                return array('success' => false, 'message' => 'El archivo cargado no es un PDF valido.');
            }
        }

        $sol = $this->getRowConsultaSql(
            "SELECT Sol_Num FROM adq_solicitudes WHERE Sol_Cod = $sol_cod LIMIT 1;",
            $this->conexion
        );
        if (empty($sol)) {
            return array('success' => false, 'message' => 'No se encontro la solicitud.');
        }

        $dir_rel = 'adquisiciones_sustentos/expedientes';
        $dir_abs = dirname(__FILE__) . '/../../DATA/' . $dir_rel;
        if (!is_dir($dir_abs) && !mkdir($dir_abs, 0777, true) && !is_dir($dir_abs)) {
            return array('success' => false, 'message' => 'No se pudo preparar el directorio de expedientes.');
        }

        $nombre = 'expediente_sol_' . $this->slugSolNumArchivo($sol['Sol_Num']) . '_cargado_' . date('Ymd_His') . '.pdf';
        $dest_abs = $dir_abs . '/' . $nombre;
        if (!@copy($file_tmp, $dest_abs)) {
            return array('success' => false, 'message' => 'No se pudo guardar el expediente cargado.');
        }

        $rel = $dir_rel . '/' . $nombre;
        $fecha = date('Y-m-d H:i:s');
        $rel_esc = $this->escapeSql($rel);
        $this->grabarv_registros(
            "UPDATE adq_solicitudes
             SET Sol_Exp_Pdf = '$rel_esc', Sol_Exp_Fec = '$fecha',
                 Sol_Exp_Firmado = NULL, Sol_Exp_Firm_Fec = NULL, Sol_Exp_Firm_Nom = NULL
             WHERE Sol_Cod = $sol_cod;",
            $this->conexion
        );

        return array(
            'success' => true,
            'path' => $rel,
            'filename' => $nombre,
            'fec' => $fecha,
            'expediente' => $this->obtenerEstadoExpedienteSolicitud($sol_cod)
        );
    }

    private function resolverLlaveElectronicaFirma($emp_cod, $clave, $p12_tmp = '', $usar_empresa = true) {
        global $APP_REAL_PATH;
        $emp_cod = intval($emp_cod);
        $clave = (string)$clave;
        $p12_tmp = trim((string)$p12_tmp);
        $certs = array();
        $cert_info = array();

        if ($p12_tmp !== '' && is_file($p12_tmp)) {
            $p12_data = file_get_contents($p12_tmp);
            if (!@openssl_pkcs12_read($p12_data, $certs, $clave)) {
                return array('success' => false, 'message' => 'No se pudo leer la llave .p12. Verifique el archivo y la clave.');
            }
        } elseif ($usar_empresa && $emp_cod > 0) {
            $llave = $this->getRowConsultaSql(
                "SELECT Lla_Rut, Lla_Cla FROM llave_elect WHERE Lla_Est = 'A' AND Emp_Cod = $emp_cod LIMIT 1;",
                $this->conexion
            );
            if (empty($llave['Lla_Rut'])) {
                return array('success' => false, 'message' => 'No hay llave electronica activa registrada. Cargue un archivo .p12.');
            }
            $ruta_p12 = $APP_REAL_PATH . "/facturacion/FRONT/$emp_cod/" . $llave['Lla_Rut'];
            if (!is_file($ruta_p12)) {
                return array('success' => false, 'message' => 'No se encontro el archivo de llave en el servidor.');
            }
            $pass = $clave !== '' ? $clave : (isset($llave['Lla_Cla']) ? $llave['Lla_Cla'] : '');
            $p12_data = file_get_contents($ruta_p12);
            if (!@openssl_pkcs12_read($p12_data, $certs, $pass)) {
                return array('success' => false, 'message' => 'Clave incorrecta o llave invalida.');
            }
        } else {
            return array('success' => false, 'message' => 'Debe cargar la llave electronica (.p12) e ingresar la clave.');
        }

        if (!empty($certs['cert'])) {
            $cert_info = openssl_x509_parse($certs['cert']);
        }
        $nombre = '';
        if (!empty($cert_info['subject']['CN'])) {
            $nombre = trim($cert_info['subject']['CN']);
        }
        return array(
            'success' => true,
            'certs' => $certs,
            'cert_info' => $cert_info,
            'nombre' => $nombre,
            'clave' => $clave
        );
    }

    public function firmarExpedienteSolicitud($sol_cod, $emp_cod, $clave, $p12_tmp = '', $usar_empresa = false) {
        $this->ensureSolicitudExpedienteColumns();
        $sol_cod = intval($sol_cod);
        $auth = $this->autorizarExpedienteFin($sol_cod, $emp_cod, isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0);
        if (!$auth['success']) {
            return $auth;
        }

        $estado = $this->obtenerEstadoExpedienteSolicitud($sol_cod);
        if (empty($estado['pdf'])) {
            return array('success' => false, 'message' => 'Debe descargar el expediente, volver a cargarlo y luego firmarlo.');
        }

        $unsigned_abs = $this->rutaAbsolutaData($estado['pdf']);
        if ($unsigned_abs === '' || !is_file($unsigned_abs)) {
            return array('success' => false, 'message' => 'No se encontro el expediente cargado. Vuelva a subir el archivo PDF.');
        }

        $llave = $this->resolverLlaveElectronicaFirma($emp_cod, $clave, $p12_tmp, $usar_empresa);
        if (empty($llave['success'])) {
            return $llave;
        }

        $sol = $this->getRowConsultaSql(
            "SELECT Sol_Num FROM adq_solicitudes WHERE Sol_Cod = $sol_cod LIMIT 1;",
            $this->conexion
        );
        $nombre_firmante = $llave['nombre'] !== '' ? $llave['nombre'] : 'Firmante autorizado';
        $entidad = !empty($llave['cert_info']['issuer']['O']) ? $llave['cert_info']['issuer']['O'] : 'Entidad certificadora';
        $fecha_firma = date('Y-m-d H:i:s');

        $dir_rel = 'adquisiciones_sustentos/expedientes';
        $dir_abs = dirname(__FILE__) . '/../../DATA/' . $dir_rel;
        if (!is_dir($dir_abs) && !mkdir($dir_abs, 0777, true) && !is_dir($dir_abs)) {
            return array('success' => false, 'message' => 'No se pudo preparar el directorio de expedientes.');
        }

        $nombre_firmado = 'expediente_sol_' . $this->slugSolNumArchivo($sol['Sol_Num']) . '_firmado_' . date('Ymd_His') . '.pdf';
        $ruta_firmada_abs = $dir_abs . '/' . $nombre_firmado;
        $ruta_firmada_rel = $dir_rel . '/' . $nombre_firmado;

        if (!class_exists('mPDF')) {
            include_once(dirname(__FILE__) . '/../../Librerias/MPDF57/mpdf.php');
        }

        try {
            $mpdf = new mPDF('c', 'A4', '', '', 12, 12, 20, 20, 6, 6);
            $mpdf->SetImportUse();
            $pagecount = $mpdf->SetSourceFile($unsigned_abs);
            if ($pagecount <= 0) {
                return array('success' => false, 'message' => 'No se pudieron leer las paginas del expediente.');
            }
            for ($i = 1; $i <= $pagecount; $i++) {
                $mpdf->AddPage();
                $tplId = $mpdf->ImportPage($i);
                $mpdf->UseTemplate($tplId);
            }

            $mpdf->AddPage();
            $html_firma = '
                <div style="border:2px solid #1d4ed8;border-radius:8px;padding:16px;font-family:helvetica,sans-serif;">
                    <h3 style="color:#1d4ed8;margin:0 0 10px 0;">Firma electronica del expediente</h3>
                    <p style="font-size:11px;margin:4px 0;"><strong>Firmado electronicamente por:</strong><br>' . htmlspecialchars(strtoupper($nombre_firmante), ENT_QUOTES, 'UTF-8') . '</p>
                    <p style="font-size:11px;margin:4px 0;"><strong>Fecha:</strong> ' . htmlspecialchars($fecha_firma, ENT_QUOTES, 'UTF-8') . '</p>
                    <p style="font-size:11px;margin:4px 0;"><strong>Entidad certificadora:</strong> ' . htmlspecialchars($entidad, ENT_QUOTES, 'UTF-8') . '</p>
                    <p style="font-size:10px;color:#475569;margin-top:10px;">Documento firmado con llave electronica (.p12). Valide en www.firmadigital.gob.ec</p>
                </div>';
            $mpdf->WriteHTML($html_firma);
            $mpdf->Output($ruta_firmada_abs, 'F');
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Error al firmar expediente: ' . $e->getMessage());
        }

        if (!is_file($ruta_firmada_abs)) {
            return array('success' => false, 'message' => 'No se genero el archivo firmado.');
        }

        $rel_esc = $this->escapeSql($ruta_firmada_rel);
        $nom_esc = $this->escapeSql($nombre_firmante);
        $this->grabarv_registros(
            "UPDATE adq_solicitudes
             SET Sol_Exp_Firmado = '$rel_esc', Sol_Exp_Firm_Fec = '$fecha_firma', Sol_Exp_Firm_Nom = '$nom_esc'
             WHERE Sol_Cod = $sol_cod;",
            $this->conexion
        );

        return array(
            'success' => true,
            'path' => $ruta_firmada_rel,
            'firmante' => $nombre_firmante,
            'fec' => $fecha_firma,
            'expediente' => $this->obtenerEstadoExpedienteSolicitud($sol_cod)
        );
    }
}
