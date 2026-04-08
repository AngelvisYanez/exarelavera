<?php
/**
 * Lógica de acceso a datos para Estado de Cuenta de Clientes (CCxCC).
 * No modifica módulos existentes de tesorería.
 *
 * @package ccxcc.LOGICA
 */
require_once(__DIR__ . '/../../auditoria/LOGICA/aud_log_auditoria.php');
require_once(__DIR__ . '/ccxcc_sql_estado_cuenta.php');

/** Conexión BD para estado cuenta clientes */
class Class_Log_Conexion_Estado_Cuenta_Cliente extends MysqlConexion { }

/** Datos para estado cuenta clientes (jqGrid/getArrayConsulta) */
class Class_Log_Datos_Estado_Cuenta_Cliente extends MysqlDatosContab {
    public function __construct() {
        $this->setSentencias('sentencias_estado_cuenta_cliente');
    }
}
