<?php
/**
 * Lógica del módulo Dashboard de Tareas (Control de Personal)
 * Requiere aud_log_auditoria (MysqlConexion, MysqlDatos) y aud_sql_dashboard_tareas_1.0 (sentencias).
 * La clase de datos extiende MysqlDatosContab para usar getPageGridJson.
 *
 * @author Sistema EXA
 * @version 1.0
 * @package auditoria.LOGICA
 */
require_once(__DIR__ . '/../../DATA/MysqlConexion.php');
require_once(__DIR__ . '/../../DATA/MysqlDatos.php');
require_once(__DIR__ . '/aud_log_auditoria.php');
require_once(__DIR__ . '/aud_sql_dashboard_tareas_1.0.php');

/**
 * Clase de conexión (extiende MysqlConexion)
 */
class Class_Log_Conexion_Aud_Tareas extends MysqlConexion
{
}

/**
 * Clase de datos para Dashboard Tareas (extiende MysqlDatosContab para getPageGridJson)
 */
class Class_Log_Datos_Aud_Tareas extends MysqlDatosContab
{
    public function __construct()
    {
        $this->setSentencias('sentencias_aud');
    }

    /**
     * Consulta en BD usando sentencias_aud
     */
    function consultasobBD($sen_sql, $param, $obBD)
    {
        $Par_Sql = $this->parametros($param);
        return $this->consulta(sentencias_aud($sen_sql, $Par_Sql), $obBD->conexion);
    }

    /**
     * Obtiene una fila de consulta
     */
    function getRowConsulta($sen_sql, $param, $obBD)
    {
        $result = $this->consultasobBD($sen_sql, $param, $obBD);
        $row = $this->fetch_assoc($result);
        $this->free_result($result);
        return $row;
    }

    /**
     * Obtiene array de consulta
     */
    function getArrayConsulta($sen_sql, $param, $obBD)
    {
        $result = $this->consultasobBD($sen_sql, $param, $obBD);
        $array = array();
        while ($row_rs = $this->fetch_assoc($result)) {
            $array[] = $row_rs;
        }
        $this->free_result($result);
        return $array;
    }

    /**
     * Insert/Update/Delete usando sentencias_aud
     */
    function operacionobBD($sen_sql, $param, $obBD)
    {
        $Par_Sql = $this->parametros($param);
        return $this->grabarv_registros(sentencias_aud($sen_sql, $Par_Sql), $obBD->conexion);
    }
}
