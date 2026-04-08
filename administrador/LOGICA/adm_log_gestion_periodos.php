<?php
/**
 * Clase para la gestión de periodos contables
 * 
 * @author Sistema
 * @version 1.0
 * Fecha de actualización:	2025-12-31
 * 
 * @package administrador.LOGICA
 */

require_once("adm_sql_gestion_periodos.php");

class Class_Log_Conexion_Gestion_Periodos extends MysqlConexion{
}

class Class_Log_Datos_Gestion_Periodos extends MysqlDatos{
    
    function consultasobBD($sen_sql, $param, $obBD = null)
    {
        $Par_Sql = $this->parametros($param);
        if($obBD == null) {
            return false;
        }
        return $this->consulta(sentencias_gestion_periodos($sen_sql, $Par_Sql), $obBD->conexion);
    }

    function operacionobBD($sen_sql, $param, $obBD = null)
    {
        if($obBD == null) {
            return false;
        }
        
        if (is_string($sen_sql) && strpos($sen_sql, '.') !== false) {
            $sql = $this->loadModel($sen_sql, $param);
            return $this->grabarv_registros($sql, $obBD->conexion);
        } else {
            $Par_Sql = $this->parametros($param);
            return $this->grabarv_registros(sentencias_gestion_periodos($sen_sql, $Par_Sql), $obBD->conexion);
        }
    }
    
    function getRowConsulta($sen_sql, $param, $obBD = null)
    {
        if($obBD == null) {
            return false;
        }
        $result = $this->consultasobBD($sen_sql, $param, $obBD);
        $row = $this->fetch_assoc($result);
        $this->free_result($result);
        return $row;
    } 
    function getArrayConsulta($sen_sql, $param, $obBD = null)
    {
        if($obBD == null) {
            return array();
        }
        $result = $this->consultasobBD($sen_sql, $param, $obBD);
        $array = array();
        while($row_rs = $this->fetch_assoc($result))
        {
            $array[] = $row_rs;
        }
        $this->free_result($result);
        return $array;
    }
}
?>
