<?php
/**
 * Logica de las paginas de ajuste de inventario
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2012-07-03
 *
 * @package tesoreria.LOGICA
 */

require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once ("fac_sql_aju.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro 
 *
 * @package tesoreria.LOGICA
*/
class Class_Log_Conexion_Tes extends MysqlConexion{
}//Fin de clase Class_Log_Conexion

/*
 * Clase para acceder a los datos
*/

class Class_Log_Datos_Tes extends MysqlDatos{
	
	/*
	 * Guardara las sql concatenadas con *
	 * de Insert, Update, Delete
	 */
	var $sentencias = '';
		
	/*
	 * guarda los codigos de autoincrementos en los insert
	 * concatenados con *
	 */
	var $codigos = '';
	
	/*
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
		return $this->consulta(sentencias_tes($sen_sql,$Par_Sql), $obBD->conexion);
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
		$Query = sentencias_tes($sen_sql,$Par_Sql);//mismo que el archivo sql
		$this->sentencias .= $Query.'*';
		$result = $this->grabarv_registros($Query, $obBD->conexion);
		$this->codigos .= $this->insercionid($obBD->conexion).'*';
		return $result;
		
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
					//echo $row.'<br>';
					$this->grabarv_registros($row,$obBD_conexion->conexion);
					if($this->Error > 0){
						return $this->Error;
					}
				}
			}else{
				return $this->Error;
			}
		}
	/**
	 * Buscar una persona registrada en las tablas
	 * [estudiante - cliente - proveedor - personal]
	 * 
	 * @param number $Ses_Emp_Cod codigo de la empresa
	 * @param string $txt_busqueda lo que se va ha buscar
	 * @param string $op_opciones la opcion que escojio
	 * @param Class_Log_Datos_Adm $obBD_conexion objeto de conexcion
	 * @return array resultado de la busqueda
	 */
	function searchPersona($Ses_Emp_Cod,$txt_busqueda,$op_opciones,$obBD_conexion)
	{
		/**
		 * Arreglo de personas encontradas
		 * @var array
		 */
		$Arr_Persona = null;
		
		
		if($op_opciones == "d")
		{
			/**
			 * para recorrer las sql
			 * @var int
			 */
			$id = 0;
			do
			{
				$id++;
				$Arr_Persona = $this->getArrayConsulta($id,$Ses_Emp_Cod.'*'.$txt_busqueda, $obBD_conexion);
			}
			while((count($Arr_Persona) == 0) && ($id != 4));
		}
		else
		{
			$id = 4;
			do
			{
				$id++;
				$Arr_Persona = $this->getArrayConsulta($id,$Ses_Emp_Cod.'*'.$txt_busqueda, $obBD_conexion);
			}
			while((count($Arr_Persona) == 0) && ($id != 8));
		}
		
		$this->id_tabla = $id;
		
		return $Arr_Persona;
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
		$row_institucion = $this->getRowConsulta(935, $sucursal, $obBD);
		
		/**
		 * Consulta la provicia y pais de la sucursal 
		 */
		$row_provincia = $this->getRowConsulta(934, $row_institucion['Ciu_Cod'], $obBD);
			
		/* Consulta datos systema */
		$row_system = $this->getRowConsulta(2,'', $obBD);	
		?>
		<table align="center" width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr align="center">
			  <td width="16%" rowspan="5" valign="top"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="83" height="67" /></td>
			  <td width="65%" class="TITULO_REPORTE_2"><?Php echo $row_institucion['Emp_Nom']; ?></td>
			  <td width="19%" rowspan="6" valign="top">&nbsp;</td>
			</tr>
			<tr align="center">
			  <td valign="top" class="Texto_Reporte" style="text-align:center"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
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
			  <td align="center" valign="top" class="Texto_Reporte"><div align="center"><? echo $row_system['Sys_Tit'];?></div></td>
		  </tr>
			 <tr align="center">
			   <td width="16%" valign="top"><hr /></td>
				<td colspan="3" valign="top"><hr /></td>
			 </tr>
			 <tr align="center">
				<td colspan="3" valign="top" class="TITULO_REPORTE"><? echo $titulo; ?></td>
			 </tr>
			 <tr align="center">
				<td colspan="3" valign="top" class="TITULO_REPORTE"><? echo $subtitulo; ?></td>
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
			$row_institucion = $this->getRowConsulta(935, $sucursal, $obBD);	

			/**
			 * Consulta los datos del usuario 
			 */
			$row_usuario = $this->getRowConsulta(936, $usuario, $obBD);
					
			$fecha=explode("-",date("Y-m-d"));	
			$fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0] ;	
						
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


?>