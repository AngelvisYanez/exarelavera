<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once ("act_sql_mantenimie.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_Mantenimiento extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_Mantenimiento extends MysqlDatos{
	
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
		return $this->consulta(sentencias_con($sen_sql,$Par_Sql), $obBD->conexion);
	}

	/**
	* Realiza una consulta en la base de datos -  STANDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Rhu $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function operacionobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		$Query = sentencias_con($sen_sql,$Par_Sql);//mismo que el archivo sql
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
 	/**
	 * graba en la base de datos auditoria
	 * @param string $Request_Uri pagina donde se estan modificando valores
	 * @param number $Ses_Usu_Cod codigo del usuario
	 * @param Class_Log_Conexion $obBD_conexion
	 * @return number codigo de error my sql si lo hubiese [0 = 'Sin errores']
	 */
	function grabarAuditoria($Request_Uri, $Ses_Usu_Cod, $obBD_conexion){
		if($this->Error == 0){
			$objAud = new Class_Log_Datos_Aud;
				
			$aux = explode('*', $objAud->grabarAuditoria($Request_Uri, $Ses_Usu_Cod, $this, $obBD_conexion));
	
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


	 /**
	  * Formato standar para reportes
	  * @param int $sucursal Código de la sucursal
	  * @param string $titulo Título del reporte
	  * @param string $subtitulo Subtitulo del reporte
	  */
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD)
	 {
	  /**
	   * Consulta de la cabecera del reporte 
	   */
	  $row_institucion = $this->getRowConsulta(5001, $sucursal, $obBD);
	
	  /**
	   * Consulta la provicia y pais de la sucursal 
	   */
	  $row_provincia = $this->getRowConsulta(5000, $row_institucion['Ciu_Cod'], $obBD);
	   
	  ?>
	  <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0">
	   <tr align="center">
		 <td width="17%" rowspan="4" valign="top"><img src="../../mascaras/model1/img/logo/mics.png" width="91" height="67" /></td>
		 <td width="66%" class="TITULO_REPORTE_2"><?Php echo $row_institucion['Emp_Nom']; ?></td>
		 <td width="17%" rowspan="5" valign="top"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="83" height="67" /></td>
	   </tr>
	   <tr align="center">
		 <td valign="top" class="Texto_Reporte" style="text-align:center"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;        <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
	   </tr>
	   <tr align="center">
		 <td valign="top" class="Texto_Reporte" style="text-align:center"><div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div></td>
	   </tr>
	   <!--<tr align="center">
		<td valign="top" class="Texto_Reporte"><div align="center"><strong>E-MAIL:</strong> &nbsp;<?php //echo $row_institucion['Suc_Cor']; ?></div></td>
	   </tr>-->
	   <tr align="center">
		 <td align="center" valign="top" class="Texto_Reporte" style="text-align:center"><div align="center"><?Php 
		 if (count($row_provincia) > 0)
		 {
		  $provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
		 }
		 else
		 {
		  $provincia = "";     
		 }
		 echo $row_institucion['Ciu_Des'].$provincia;?></div>
		 </td>
		</tr>
		<tr align="center">
		  <td width="16%" valign="top"><hr /></td>
		<td colspan="3" valign="top"><hr /></td>
		</tr>
		<tr align="center">
		<td colspan="3" valign="top" class="TITULO_REPORTE"><?php echo $titulo; ?></td>
		</tr>
		<tr align="center">
		<td colspan="3" valign="top" class="TITULO_REPORTE"><?php echo $subtitulo; ?></td>
		</tr>
	  </table>
	  <?php
	  } 

  /**
   * Formato standar para reportes
   * @param int $sucursal Código de la sucursal
   * @param string $usuario Código del usuario 
   */ 
  function pieReporteStandar($sucursal, $usuario, $obBD)
  { 
   /**
    * Consulta de la cabecera del reporte 
    */
   $row_institucion = $this->getRowConsulta(5001, $sucursal, $obBD); 
   /**
    * Consulta los datos del usuario 
    */
   $row_usuario = $this->getRowConsulta(5002, $usuario, $obBD);
     
   $fecha=explode("-",date("Y-m-d")); 
   $fechaHoy = $row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0] ; 
      
  ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr align="center">
     <td valign="top"><hr /></td>
      </tr>
    <tr align="center">
     <td width="75%" valign="top" class="Texto_Reporte"><div align="center"><strong>FECHA IMPRESI&Oacute;N:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;<strong>USUARIO:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
    </tr>
   </table>
   <?php
  }
	

}//Fin de clase Class_Log_Conexion

/* Funcion que agrega un cero al No de cuenta, cuando se trata de detalle */
 function mascara_cuenta($cuenta)
 {
  $array_cuenta=explode('.',$cuenta);
  $indice=count($array_cuenta)-1;
  /* Pregunta para saber si la cantidad del numero de la ultima cuenta
  es menor a 10 */ 
  if ($array_cuenta[$indice]<10 and count($array_cuenta)>1)
  {
   $concatenado = '0'.$array_cuenta[$indice];
   $retorno = $array_cuenta[0];
   for ($i=1;$i<=count($array_cuenta)-2;$i++)
   {
    $retorno = $retorno.'.'.$array_cuenta[$i];
   }
    return $retorno.'.'.$concatenado;
  }
  else
  {
    return $cuenta;
  }
 }//Fin del function mascara_cuenta($cuenta)
