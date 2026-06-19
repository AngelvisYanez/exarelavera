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

    /**
     * Guarda una solicitud de adquisición e inicia su respectivo workflow
     */
    public function guardarSolicitud($data, $items, $cotizaciones = array()) {
        $this->inicio_transaccion($this->conexion);
        try {
            $fecha_actual = date('Y-m-d H:i:s');
            $usu_sol = isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 0;
            $dep_sol = isset($_SESSION['Ses_Dep_Cod']) ? $_SESSION['Ses_Dep_Cod'] : 1; // Asumir 1 como default

            // 1. Obtener número correlativo de solicitud para esta sucursal/empresa
            $resNum = $this->getRowConsultaSql("SELECT IFNULL(MAX(Sol_Num), 0) + 1 AS Siguiente FROM adq_solicitudes WHERE Emp_Cod = $data[Emp_Cod] AND Suc_Cod = $data[Suc_Cod];", $this->conexion);
            $sol_num = $resNum['Siguiente'];

            // 2. Insertar cabecera de solicitud
            $trq_cod = intval($data['Trq_Cod']);
            $sol_pri = mysqli_real_escape_string($this->conexion->conexion, $data['Sol_Pri']);
            $sol_val_est = floatval($data['Sol_Val_Est']);
            $cdc_cod = !empty($data['Cdc_Cod']) ? "'" . mysqli_real_escape_string($this->conexion->conexion, $data['Cdc_Cod']) . "'" : 'NULL';
            $pry_cod = !empty($data['Pry_Cod']) ? intval($data['Pry_Cod']) : 'NULL';
            $prv_sug = !empty($data['Prv_Sug']) ? intval($data['Prv_Sug']) : 'NULL';
            $sol_jus = mysqli_real_escape_string($this->conexion->conexion, $data['Sol_Jus']);
            $sol_det = mysqli_real_escape_string($this->conexion->conexion, $data['Sol_Det']);

            $sqlInsert = "INSERT INTO adq_solicitudes (Emp_Cod, Suc_Cod, Trq_Cod, Sol_Num, Sol_Fec, Usu_Sol, Dep_Sol, Sol_Pri, Sol_Val_Est, Cdc_Cod, Pry_Cod, Prv_Sug, Sol_Jus, Sol_Det, Sol_Est) 
                          VALUES ($data[Emp_Cod], $data[Suc_Cod], $trq_cod, $sol_num, '$fecha_actual', $usu_sol, $dep_sol, '$sol_pri', $sol_val_est, $cdc_cod, $pry_cod, $prv_sug, '$sol_jus', '$sol_det', 'P');";
            $this->grabarv_registros($sqlInsert, $this->conexion);
            $sol_cod = $this->insercionid($this->conexion);

            // 3. Insertar detalle de ítems
            $idx = 1;
            foreach ($items as $item) {
                $pro_cod = !empty($item['Pro_Cod']) ? intval($item['Pro_Cod']) : 'NULL';
                $sde_des = mysqli_real_escape_string($this->conexion->conexion, $item['Sde_Des']);
                $sde_can = floatval($item['Sde_Can']);
                $sde_pru = floatval($item['Sde_Pru']);
                $sde_iva = !empty($item['Sde_Iva']) ? 1 : 0;

                $sqlDet = "INSERT INTO adq_solicitudes_det (Sol_Cod, Sde_Int, Pro_Cod, Sde_Des, Sde_Can, Sde_Pru, Sde_Iva) 
                           VALUES ($sol_cod, $idx, $pro_cod, '$sde_des', $sde_can, $sde_pru, $sde_iva);";
                $this->grabarv_registros($sqlDet, $this->conexion);
                $idx++;
            }

            // 4. Registrar cotizaciones físicas asociadas
            foreach ($cotizaciones as $cot) {
                $prv_cod = intval($cot['Prv_Cod']);
                $cot_val = floatval($cot['Cot_Val']);
                $cot_adj = mysqli_real_escape_string($this->conexion->conexion, $cot['Cot_Adj']);
                $cot_sel = !empty($cot['Cot_Sel']) ? 1 : 0;
                $cot_jus = mysqli_real_escape_string($this->conexion->conexion, $cot['Cot_Jus']);

                $sqlCot = "INSERT INTO adq_solicitudes_cotizaciones (Sol_Cod, Prv_Cod, Cot_Fec, Cot_Val, Cot_Adj, Cot_Sel, Cot_Jus) 
                           VALUES ($sol_cod, $prv_cod, '" . date('Y-m-d') . "', $cot_val, '$cot_adj', $cot_sel, '$cot_jus');";
                $this->grabarv_registros($sqlCot, $this->conexion);
            }

            // 5. Instanciar e iniciar el Workflow en base al flujo modelo asociado al tipo de requerimiento
            $trq = $this->getRowConsultaSql("SELECT Wfm_Cod FROM adq_tipos_requerimientos WHERE Trq_Cod = $trq_cod;", $this->conexion);
            if (empty($trq) || empty($trq['Wfm_Cod'])) {
                throw new Exception("El Tipo de Requerimiento seleccionado no posee un Flujo Modelo asignado.");
            }

            $wf_mgr = new wf_manager_log($_SESSION['Ses_Dat_Dis']);
            $wf_res = $wf_mgr->iniciarInstancia($trq['Wfm_Cod'], 'adq_solicitudes', $sol_cod);
            
            if (!$wf_res['success']) {
                throw new Exception("Error al instanciar el workflow de aprobación: " . $wf_res['message']);
            }

            // Actualizar estado de solicitud de Borrador 'P' a En Flujo 'E'
            $this->grabarv_registros("UPDATE adq_solicitudes SET Sol_Est = 'E' WHERE Sol_Cod = $sol_cod;", $this->conexion);

            $this->commit_nomsn($this->conexion);
            return array('success' => true, 'Sol_Cod' => $sol_cod, 'Num' => $sol_num);
        } catch (Exception $e) {
            $this->rollBack_nomsn($this->conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
}
