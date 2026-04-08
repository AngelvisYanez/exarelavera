<?php
/**
 * Lógica para consulta de empresas activas con periodo
 * 
 * @author Sistema
 * @version 1.0
 * Fecha de actualización:	2025-01-XX
 * 
 * @package administrador.LOGICA
 */

require_once("../../DATA/DAC.php");
require_once("adm_sql_empresas_activas.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @package administrador.LOGICA
 */
class Class_Log_Conexion_Empresas_Activas extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * 
 * @package administrador.LOGICA
 */
class Class_Log_Datos_Empresas_Activas extends MysqlDatos{
    
    /**
     * Realiza una consulta en la base de datos -  STANDARD
     *
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de valores para el filtrado de la busqueda
     * @param Class_Log_Conexion_Empresas_Activas $obBD para realizar la conexion correspondiente
     * @return result si existen datos de retorno
     */
    function consultasobBD($sen_sql, $param, $obBD = null)
    {
        $Par_Sql = $this->parametros($param);
        if($obBD == null) {
            return false;
        }
        return $this->consulta(sentencias_empresas_activas($sen_sql, $Par_Sql), $obBD->conexion);
    }

    /**
     * Realiza una consulta en la base de datos -  STANDARD
     *
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de valores para el filtrado de la busqueda
     * @param Class_Log_Conexion_Empresas_Activas $obBD para realizar la conexion correspondiente
     * @return result si existen datos de retorno
     */
    function operacionobBD($sen_sql, $param, $obBD = null)
    {
        $Par_Sql = $this->parametros($param);
        if($obBD == null) {
            return false;
        }
        return $this->grabarv_registros(sentencias_empresas_activas($sen_sql, $Par_Sql), $obBD->conexion);
    }
    
    /**
     * Ejecuta cualquier consulta a la base de datos -  STANDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de valores para el filtrado de la busqueda
     * @param Class_Log_Conexion_Empresas_Activas $obBD para realizar la conexion correspondiente
     * @return array $row fila de datos
     */
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

    /**
     * Ejecuta cualquier consulta a la base de datos -  STANDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de valores para el filtrado de la busqueda
     * @param Class_Log_Conexion_Empresas_Activas $obBD para realizar la conexion correspondiente
     * @return array $array arreglo de datos asociados
     */ 
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

    /**
     * Inserta o actualiza o elimina los datos de una sola transaccion -  STANDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de datos
     * @param Class_Log_Conexion_Empresas_Activas $obBD objeto de conexion
     */
    function insertUpdateDelete($sen_sql, $param, $obBD = null)
    {
        if($obBD == null) {
            return false;
        }
        $this->inicio_transaccion($obBD->conexion);
        //Realiza Insert, Update o Delete
        $this->operacionobBD($sen_sql, $param, $obBD);
        $this->fin_transaccion($obBD->conexion);
    }
}
?>

