<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("tca_sql_factura.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_viajeFactura extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_viajeFactura extends MysqlDatos{
	
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
    * @param Class_Log_Conexion_Rhu $obBD para realizar la conexcion correspondiente
    * @return result si existen datos de retorno
    */
    function consultasobBD($sen_sql,$param, $obBD)
    {
        $Par_Sql= $this->parametros($param);
        return $this->consulta(sentencias_viajeFactura($sen_sql,$Par_Sql), $obBD->conexion);
    }

    /**
    * Realiza una consulta en la base de datos -  STARDARD
    *
    * @param int $sen_sql numero de la sql
    * @param string $param cadena de valores para el filtrado de la busqueda
    * @param Class_Log_Conexion_Rhu $obBD para realizar la conexcion correspondiente
    * @return result si existen datos de retorno
    */
    function operacionobBD($sen_sql,$param, $obBD)
    {
        $Par_Sql= $this->parametros($param);
        $Query = sentencias_viajeFactura($sen_sql,$Par_Sql);//mismo que el archivo sql
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
    function getRowConsulta($sen_sql,$param,$obBD)
    {
        $result = $this->consultasobBD($sen_sql,$param,$obBD);

        $row =  $this->fetch_assoc($result);

        $this->free_result($result);

        return $row;
    }
    
    /**
     * Codigo de los comprobantes
     * @param int $Tia_Cod Tipo de comprobante
     * @param int $Pec_Cod periodo contable
     * @param int $mes mes
     */		
    function codigoComprAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion)
    {
        /* 
        * Codificación numerica en base al periodo contable y mensualmente 
        */
        $result=$this->consulta("SELECT MAX(Com_Num)+1 AS Com_Num FROM comprobantes WHERE Tia_Cod = $Tia_Cod AND Pec_Cod = $Pec_Cod AND MONTH(Com_Fec) = $mes", $obBD->conexion);
        $row_rs_numcom =  $this->fetch_assoc($result);
        $this->free_result($result);

        //$row_rs_numcom = $this->getRowConsulta(633, $Tia_Cod.'*'.$Pec_Cod.'*'.$mes, $obBD_conexion);
        // Revisar la condición (todo funciona correctamente pero con artificio)
        if ((count($row_rs_numcom) > 0) && ($row_rs_numcom['Com_Num'] != ''))
        {
                $Com_Num=$row_rs_numcom['Com_Num'];
        } else {
                $Com_Num=1;
        }					
        return $Com_Num;
    }
    
    /**
     * Ejecuta cualquier consulta a la base de datos -  STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de valores para el filtrado de la busqueda
     * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
     * @param Class_Log_Datos_Cli $obDT para la abtraccion de los datos
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
    
    /* 
    * Funcion que devuelve un arreglo de los reportes del proceso 
    */
    function reportes($pagina, $empresa, $obBD_conexion)
    {
            $pag = explode("/", $pagina);
            $row_rs_proceso = $this->getRowConsulta(51, $pag[count($pag)-1], $obBD_conexion);

            $row_rs_reporte = $this->getArrayConsulta(52, $row_rs_proceso['Pcs_Cod'].'*'.$empresa, $obBD_conexion);

            $i=0;
            foreach ($row_rs_reporte as $row)
            {
                    $i++;
                    $reporte[$i] = $row['Rut_Des'].$row['Pcs_Nom'];		
            }		
            return $reporte;
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
}//Fin de clase Class_Log_Conexion