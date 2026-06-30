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
                "SELECT MIN(du.Dep_Cod) AS Dep_Cod
                 FROM wf_departamento_usuarios du
                 INNER JOIN departamen d ON d.Dep_Cod = du.Dep_Cod AND d.Emp_Cod = $emp_cod AND d.Dep_Est = 'A'
                 WHERE du.Usu_Cod = $usu_cod;",
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

        $resNum = $this->getRowConsultaSql(
            "SELECT IFNULL(MAX(Sol_Num), 0) + 1 AS Siguiente FROM adq_solicitudes WHERE Emp_Cod = $data[Emp_Cod] AND Suc_Cod = $data[Suc_Cod];",
            $this->conexion
        );
        $sol_num = $resNum['Siguiente'];

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
                $data[Emp_Cod], $data[Suc_Cod], $trq_cod, $sol_num, '$fecha_actual', $usu_sol, $dep_sol, '$sol_pri', $sol_val_est,
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
        $sol_cod = intval($sol_cod);
        $emp_cod = intval($emp_cod);
        $usu_sol = intval($usu_sol);
        $sol = $this->getRowConsultaSql(
            "SELECT Sol_Cod, Sol_Num, Trq_Cod FROM adq_solicitudes
             WHERE Sol_Cod = $sol_cod AND Emp_Cod = $emp_cod AND Usu_Sol = $usu_sol AND Sol_Est = 'P' LIMIT 1;",
            $this->conexion
        );
        if (empty($sol)) {
            throw new Exception('La solicitud en borrador no existe o no puede editarse.');
        }
        return $sol;
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
            if (empty($cot['Prv_Cod']) && empty($cot['Cot_Val']) && empty($cot['Cot_Adj'])) {
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
                if (!empty($cot['Cot_Adj'])) {
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
                if (!empty($c['Prv_Cod']) && floatval($c['Cot_Val']) > 0 && !empty($c['Cot_Adj'])) {
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

    private function validarRequisitosEnvioDesdePost($req, $data, $items, $cotizaciones) {
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
            $validas = 0;
            $ganadora = false;
            foreach ($cotizaciones as $cot) {
                if (!empty($cot['Prv_Cod']) && floatval($cot['Cot_Val']) > 0 && !empty($cot['Cot_Adj'])) {
                    $validas++;
                }
                if (!empty($cot['Cot_Sel'])) {
                    $ganadora = true;
                }
            }
            if ($validas < $min_cot) {
                $faltantes[] = "Se requieren al menos $min_cot cotizacion(es) con proveedor, monto y archivo PDF.";
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
             WHERE s.Sol_Cod = $sol_cod AND s.Emp_Cod = $emp_cod AND s.Usu_Sol = $usu_sol AND s.Sol_Est = 'P'
             LIMIT 1;",
            $this->conexion
        );
        if (empty($sol)) {
            return array('success' => false, 'message' => 'La solicitud en borrador no existe o no puede editarse.');
        }
        $sol = $this->aplicarRequisitosEfectivos($sol);
        $items = $this->getArrayConsultaSql(
            "SELECT * FROM adq_solicitudes_det WHERE Sol_Cod = $sol_cod ORDER BY Sde_Int;",
            $this->conexion
        );
        $cotizaciones = $this->getArrayConsultaSql(
            "SELECT c.*, p.Prs_Nom, p.Prs_Ape, pr.Prv_Com, per.Prs_Ced
             FROM adq_solicitudes_cotizaciones c
             INNER JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod
             INNER JOIN persona per ON per.Prs_Cod = pr.Prs_Cod
             WHERE c.Sol_Cod = $sol_cod;",
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
            'prv_sug_text' => $prv_sug_text
        );
    }

    public function actualizarBorrador($sol_cod, $data, $items, $cotizaciones_nuevas = array(), $cotizaciones_existentes = array(), $cot_eliminar = array()) {
        $emp_cod = intval($data['Emp_Cod']);
        $usu_sol = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;
        $this->ensureSolicitudRequisitosColumns();
        $borrador = $this->assertBorradorEditable($sol_cod, $emp_cod, $usu_sol);
        $sol_cod = intval($borrador['Sol_Cod']);
        $trq_cod = intval($data['Trq_Cod']);

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

            $this->commit_nomsn($this->conexion);
            return array('success' => true, 'Sol_Cod' => $sol_cod, 'Num' => $borrador['Sol_Num'], 'borrador' => true);
        } catch (Exception $e) {
            $this->rollBack_nomsn($this->conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    private function iniciarWorkflowBorrador($sol_cod, $trq_cod) {
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
            $wf_mgr->avanzarSiEstaEnInicio($instancia['Ins_Cod'], 'Envio de solicitud desde borrador.');
        }
        if (!$this->grabarv_registros("UPDATE adq_solicitudes SET Sol_Est = 'E' WHERE Sol_Cod = $sol_cod;", $this->conexion)) {
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
     * Guarda la solicitud como borrador sin iniciar workflow.
     */
    public function guardarBorrador($data, $items, $cotizaciones = array(), $cotizaciones_existentes = array(), $cot_eliminar = array()) {
        $sol_cod_edit = !empty($data['Sol_Cod']) ? intval($data['Sol_Cod']) : 0;
        if ($sol_cod_edit > 0) {
            return $this->actualizarBorrador($sol_cod_edit, $data, $items, $cotizaciones, $cotizaciones_existentes, $cot_eliminar);
        }
        $this->inicio_transaccion($this->conexion);
        try {
            $resultado = $this->persistirSolicitudNueva($data, $items, $cotizaciones);
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
            $this->validarRequisitosEnvioDesdePost($req, $data, $items, $cotizaciones);

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
}
