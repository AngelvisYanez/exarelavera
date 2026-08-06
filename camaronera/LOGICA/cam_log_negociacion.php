<?php

/**
 * Logica para crear una negociacion
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de creacion: 2025-03-01
 *
 * @package camaronera.LOGICA
 */

require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("cam_sql_camaronera.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 */
class  Class_Log_Conexion_Cam extends MysqlConexion {} //Fin de clase Class_Log_Conexion
class Class_Log_datos_Cam extends MysqlDatosContab
{

    function __construct()
    {
         $this->setSentencias('sentencias_camaronera');
    }


    

    /* function consultasobBD($sen_sql, $param, $obBD = null)
    {
        $Par_Sql = $this->parametros($param);
        return $this->consulta(sentencias_cch($sen_sql, $Par_Sql), $obBD->conexion);
    }


    function operacionobBD($sen_sql, $param, $obBD = null)
    {
        $Par_Sql = $this->parametros($param);
        return $this->grabarv_registros(sentencias_cch($sen_sql, $Par_Sql), $obBD->conexion);
    }
    function getRowConsulta($sen_sql, $param, $obBD = null)
    {
        $result = $this->consultasobBD($sen_sql, $param, $obBD);

        $row =  $this->fetch_assoc($result);

        $this->free_result($result);

        return is_array($row) ? $row : array();
    }
    function getArrayConsulta($sen_sql, $param, $obBD = null)
    {
        $result = $this->consultasobBD($sen_sql, $param, $obBD);

        $array = array();

        while ($row_rs = $this->fetch_assoc($result)) {
            $array[] = $row_rs;
        }

        $this->free_result($result);

        return $array;
    }*/
}//Fin de clase Class_Log_Conexion
