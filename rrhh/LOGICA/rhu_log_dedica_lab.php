<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("rhu_sql_dedica_lab.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_dedica extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_dedica extends MysqlDatos{
	
    /**
     * Guardara las sql concatenadas con *
     * de Insert, Update, Delete
     * @var string
     */
    public $sentencias = '';

    /**
     * guarda los codigos de autoincrementos en los insert
     * concatenados con *
     * @var string
     */
    public $codigos = '';

    /**
    * Realiza una consulta en la base de datos -  STARDARD
    *
    * @param int $sen_sql numero de la sql
    * @param string $param cadena de valores para el filtrado de la busqueda
    * @param Class_Log_Conexion_Rhu $obBD para realizar la conexcion correspondiente
    * @return result si existen datos de retorno
    */
    function consultasobBD($sen_sql,$param, $obBD = null)
    {
            $Par_Sql= $this->parametros($param);
            return $this->consulta(sentencias_dedica($sen_sql,$Par_Sql), $obBD->conexion);
    }

    /**
    * Realiza una consulta en la base de datos -  STARDARD
    *
    * @param int $sen_sql numero de la sql
    * @param string $param cadena de valores para el filtrado de la busqueda
    * @param Class_Log_Conexion_Rhu $obBD para realizar la conexcion correspondiente
    * @return result si existen datos de retorno
    */
    function operacionobBD($sen_sql,$param, $obBD = null)
    {
            $Par_Sql= $this->parametros($param);
            $Query = sentencias_dedica($sen_sql,$Par_Sql);//mismo que el archivo sql
            $this->sentencias .= $Query.'*';
            $result = $this->grabarv_registros($Query, $obBD->conexion);
            $this->codigos .= $this->insercionid($obBD->conexion).'*';
            return $result;
    }

    /**
     * Ejecuta cualquier consulta a la base de datos -  STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de valores para el filtrado de la busqueda
     * @param Class_Log_Conexion_Rhu $obBD para realizar la conexcion correspondiente
     * @return array $row fila de datos
     */
    function getRowConsulta($sen_sql,$param,$obBD = null)
    {
            $result = $this->consultasobBD($sen_sql,$param,$obBD);

            $row =  $this->fetch_assoc($result);

            $this->free_result($result);

            return $row;
    }

    /**
     * Ejecuta cualquier consulta a la base de datos -  STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de valores para el filtrado de la busqueda
     * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
     * @param Class_Log_Datos_Cli $obDT para la abtraccion de los datos
     * @return array $array arreglo de datos asociados
     */ 
    function getArrayConsulta($sen_sql,$param,$obBD = null)
    {
            $result = $this->consultasobBD($sen_sql,$param,$obBD);

            $array = array();

            while($row_rs = $this->fetch_assoc($result))
            {
                    $array[] = $row_rs;
            }

            $this->free_result($result);

            return $array;
    }	
}//Fin de clase Class_Log_Conexion


