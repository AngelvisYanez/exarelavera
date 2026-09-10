<?php
/**
 * Lógica del módulo Gestión Operativa del Despacho
 * Requiere MysqlConexion, MysqlDatos y aud_sql_despacho_1.0 (sentencias_despacho).
 *
 * @author Sistema EXA
 * @version 1.0
 * @package auditoria.LOGICA
 */
require_once(__DIR__ . '/../../DATA/MysqlConexion.php');
require_once(__DIR__ . '/../../DATA/MysqlDatos.php');
require_once(__DIR__ . '/aud_sql_despacho_1.0.php');

/**
 * Clase de conexión (extiende MysqlConexion)
 */
class Class_Log_Conexion_Despacho extends MysqlConexion
{
}

/**
 * Clase de datos para Despacho (extiende MysqlDatosContab para getPageGridJson)
 */
class Class_Log_Datos_Despacho extends MysqlDatosContab
{
    public function __construct()
    {
        $this->setSentencias('sentencias_despacho');
    }

    /**
     * Consulta en BD usando sentencias_despacho
     */
    function consultasobBD($sen_sql, $param, $obBD)
    {
        $Par_Sql = $this->parametros($param);
        return $this->consulta(sentencias_despacho($sen_sql, $Par_Sql), $obBD->conexion);
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
        if ($result !== false && $result !== null) {
            while ($row_rs = $this->fetch_assoc($result)) {
                $array[] = $row_rs;
            }
            $this->free_result($result);
        }
        return $array;
    }

    /**
     * Insert/Update/Delete usando sentencias_despacho
     */
    function operacionobBD($sen_sql, $param, $obBD)
    {
        $Par_Sql = $this->parametros($param);
        $sql = sentencias_despacho($sen_sql, $Par_Sql);
        if (strpos($sql, ';') !== false) {
            $parts = explode(';', $sql);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $this->grabarv_registros($p, $obBD->conexion);
                    if ($this->Error != 0) return false;
                }
            }
            return true;
        }
        return $this->grabarv_registros($sql, $obBD->conexion);
    }
}
