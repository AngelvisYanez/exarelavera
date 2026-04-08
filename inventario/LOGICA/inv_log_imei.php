<?php
/**
 * Lógica de las páginas para el control de IMEI de teléfonos
 *
 * @author Exa Contable
 * @version 1.0
 * Fecha de actualización: 2026-01-19
 *
 * @package inventario.LOGICA
 */

require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("inv_sql_imei.php");

/**
 * Clase para conexión a la capa de acceso a datos
 * 
 * @author Sistema
 *
 * @package inventario.LOGICA
 */
class Class_Log_Conexion_Imei extends MysqlConexion
{
}

/**
 * Clase para acceder a los datos
 * @author Sistema
 */
class Class_Log_Datos_Imei extends MysqlDatos
{
    function __construct()
    {
        $this->setSentencias('sentencias_imei');
    }
}
?>
