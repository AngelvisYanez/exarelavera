<?
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("act_sql_depreciacion.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_Depreciacion extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_Depreciacion extends MysqlDatos{
	
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
        return $this->consulta(sentencias_depreciacion($sen_sql,$Par_Sql), $obBD->conexion);
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
        $Query = sentencias_depreciacion($sen_sql,$Par_Sql);//mismo que el archivo sql
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
    function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$colspan,$obBD)
	{
            /* Consulta de la cabecera del reporte */
            $result1= $this->consulta("SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $sucursal AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod", $obBD->conexion);
            $row_institucion =  $this->fetch_assoc($result1);		
            $this->free_result($result1);

            /* Consulta la provicia y pais de la sucursal */
            $result2= $this->consulta("SELECT provincia.Pro_Nom, pais.Pas_Nom FROM provincia INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod) INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod) INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod) WHERE ciudad.Ciu_Cod = ".$row_institucion['Ciu_Cod'], $obBD->conexion);
            $row_provincia =  $this->fetch_assoc($result2);		
            $this->free_result($result2);		
            ?>
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td width="75%" colspan="<?php echo $colspan;?>" class="TITULO_REPORTE_2"><b><?Php echo $row_institucion['Emp_Nom']; ?></b></td>
                    </tr>
                    <tr align="center">
			<td valign="top" colspan="<?php echo $colspan;?>" class="Texto_Reporte"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
                    </tr>
<!--                    <tr align="center">
                        <td valign="top" colspan="<?php echo $colspan;?>" class="Texto_Reporte"><div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div></td>
                    </tr>
                    <tr align="center">
			<td valign="top" colspan="<?php echo $colspan;?>" class="Texto_Reporte"><div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div></td>
                    </tr>-->
                    <tr align="center">
                        <td align="center" valign="top" colspan="<?php echo $colspan;?>" class="Texto_Reporte"><div align="center"><?Php 
                            if (count($row_provincia) > 0)
                            {
                                $provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
                            }
                            else
                            {
                                $provincia = "";					
                            }
                        echo $row_institucion['Ciu_Des'].$provincia;?></div></td>
                    </tr>
                    <tr align="center">
                        <td colspan="<?php echo $colspan;?>" valign="top" class="TITULO_REPORTE"><b><? echo $titulo; ?></b></td>
                    </tr>
                    <tr align="center">
                        <td colspan="<?php echo $colspan;?>" valign="top" class="TITULO_REPORTE"><? echo $subtitulo; ?></td>
                    </tr>
                </table>
	<?php
	} 
}//Fin de clase Class_Log_Conexion



