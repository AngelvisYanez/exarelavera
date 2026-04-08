<?
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("tca_sql_cli_vehi.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_cli_vehi extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_cli_vehi extends MysqlDatos{
	
    /**
     * Guardara las sql concatenadas con *
     * de Insert, Update, Delete
     * @var string
     */
    var $sentencias = '';

    /**
     * guarda los codigos de autoincrementos en los insert
     * concatenados con *
     * @var string
     */
    var $codigos = '';

    /**
    * Realiza una consulta en la base de datos -  STARDARD
    *
    * @param int $sen_sql numero de la sql
    * @param string $param cadena de valores para el filtrado de la busqueda
    * @param Class_Log_Conexion_cli_vehi $obBD para realizar la conexcion correspondiente
    * @return result si existen datos de retorno
    */
    function consultasobBD($sen_sql,$param, $obBD)
    {
        $Par_Sql= $this->parametros($param);
        return $this->consulta(sentencias_cli_vehi($sen_sql,$Par_Sql), $obBD->conexion);
    }

    /**
    * Realiza una consulta en la base de datos -  STARDARD
    *
    * @param int $sen_sql numero de la sql
    * @param string $param cadena de valores para el filtrado de la busqueda
    * @param Class_Log_Conexion_cli_vehi $obBD para realizar la conexcion correspondiente
    * @return result si existen datos de retorno
    */
    function operacionobBD($sen_sql,$param, $obBD)
    {
        $Par_Sql= $this->parametros($param);
        $Query = sentencias_cli_vehi($sen_sql,$Par_Sql);//mismo que el archivo sql
        $this->sentencias .= $Query.'*';
        $result = $this->grabarv_registros($Query, $obBD->conexion);
        $this->codigos .= $this->insercionid($obBD->conexion).'*';
        return $result;
    }

    /**
     * Ejecuta cualquier consulta a la base de datos -  STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de valores para el filtrado de la busqueda
     * @param Class_Log_Conexion_cli_vehi $obBD para realizar la conexcion correspondiente
     * @return array $row fila de datos
     */
    function getRowConsulta($sen_sql,$param,$obBD)
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
     * @param Class_Log_Conexion_cli_vehi $obBD para realizar la conexcion correspondiente
     * @return array $array arreglo de datos asociados
     */ 
    function getArrayConsulta($sen_sql,$param,$obBD)
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
    
    /**
     * graba en la base de datos auditoria
     * @param string $Request_Uri pagina donde se estan modificando valores
     * @param number $Ses_Usu_Cod codigo del usuario
     * @param Class_Log_Conexion $obBD_conexion
     * @return number codigo de error my sql si lo hubiese [0 = 'Sin errores']
     */
    function grabarAuditoria($Ses_Dat_Dis,$Request_Uri, $Ses_Usu_Cod, $obBD_conexion){
        if($this->Error == 0){
            $objAud = new Class_Log_Datos_Aud;

            $aux = explode('*', $objAud->grabarAuditoria($Ses_Dat_Dis,$Request_Uri, $Ses_Usu_Cod, $this, $obBD_conexion));

            foreach ($aux as $row){
                $this->grabarv_registros($row,$obBD_conexion->conexion);
                if($this->Error > 0){
                    return $this->Error;
                }
            }
            $objAud->GuardarCierreSesion($_SESSION['Ses_Ses_Cod'], date('Y-m-d H:i:s'), $Ses_Usu_Cod);
        }else{
            return $this->Error;
        }
    }
}//Fin de clase Class_Log_Datos_cli_vehi

