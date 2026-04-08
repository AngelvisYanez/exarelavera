<?php
/**
 * Lógica de acceso a datos para Estado de Cuenta de Proveedores (CCxPP).
 * No modifica módulos existentes de tesorería.
 *
 * @package ccxpp.LOGICA
 */
require_once(__DIR__ . '/../../auditoria/LOGICA/aud_log_auditoria.php');
require_once(__DIR__ . '/tes_sql_estado_cuenta.php');

/** Conexión BD para estado cuenta proveedores */
class Class_Log_Conexion_Estado_Cuenta_Proveedor extends MysqlConexion {}

/** Datos para estado cuenta proveedores (jqGrid/getArrayConsulta) */
class Class_Log_Datos_Estado_Cuenta_Proveedor extends MysqlDatosContab {
    public function __construct() {
        $this->setSentencias('sentencias_estado_cuenta_proveedor');
    }
}
