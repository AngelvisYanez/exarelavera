<?Php 
/**
 * Logica de las paginas que tienen que ver con usuarios
 *
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualización:	2012-04-18
 *
 * @package administrador.LOGICA
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("adm_sql_usuarios_2.0.php");

/**
 * Clase para conexion a la capa de acceso a datos
 *
 * @author car.87cod :)
 *
 * @package administrador.LOGICA
 */
class Class_Log_Conexion_Admu extends MysqlConexion{

}

/**
 * Clase para acceder a los datos
 * @author car.87cod :)
 *
 * @package administrador.LOGICA
 */
class Class_Log_Datos_Admu extends MysqlDatos{
	
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
	 * Numero de la tabla que se encontro resultados
	 * 1 - 5: estudiante
	 * 2 - 6: cliente
	 * 3 - 7: proveedor
	 * 4 - 8: personal
	 * @var int
	 */
	public $id_tabla;
	
	/**
	 * Realiza una consulta en la base de datos -  STARDARD
	 *
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return result si existen datos de retorno
	 */
	function consultasobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_admu($sen_sql,$Par_Sql), $obBD->conexion);
	}

	/**
	 * Realiza una consulta en la base de datos -  STARDARD
	 *
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return result si existen datos de retorno
	 */
	function operacionobBD($sen_sql,$param, $obBD = null)
	{
		$Query = sentencias_admu($sen_sql,$this->parametros($param));
		$this->sentencias .= $Query.'*';
		$result = $this->grabarv_registros($Query, $obBD->conexion);
		$this->codigos .= $this->insercionid($obBD->conexion).'*';
		return $result;
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
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
	 * Inserta o actualiza o elimina los datos de una sola transacccion -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de datos
	 * @param Class_Log_Datos_Cli $obBD objeto de conexion
	 */
	function insertUpdateDelete($sen_sql,$param, $obBD = null)
	{
		$this->inicio_transaccion($obBD->conexion);

		//Realiza Insert, Update o Delete
		$this->operacionobBD($sen_sql,$param,$obBD);
			
		$this->fin_transaccion($obBD->conexion);
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
			while((count($Arr_Persona) == 0) && ($id != 0));
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
	 * Formato standar para reportes
	 * @param int $sucursal Código de la sucursal
	 * @param string $titulo Título del reporte
	 * @param string $subtitulo Subtitulo del reporte
	 */
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD)
	{
		/* Consulta de la cabecera del reporte */
		$row_institucion = $this->getRowConsulta(22, $sucursal, $obBD);
		/* Consulta la provicia y pais de la sucursal */
		$row_provincia = $this->getRowConsulta(21, $row_institucion['Ciu_Cod'], $obBD);
			
		?>
			<table width="80%" border="0" cellpadding="0" cellspacing="0">
			  <tr align="center">
			    <td width="5%" rowspan="5" valign="top"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="83" height="67" /></td>
			    <td width="75%" class="TITULO_REPORTE_2"><?Php echo $row_institucion['Emp_Nom']; ?></td>
			  </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td align="center" valign="top" class="Texto_Reporte"><div align="center"><?Php 
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
			    <td colspan="2" valign="top"><hr /></td>
	  		  </tr>
			  <tr align="center">
			    <td colspan="2" valign="top" class="TITULO_REPORTE"><?php echo $titulo; ?></td>
	  		  </tr>
			  <tr align="center">
			    <td colspan="2" valign="top" class="TITULO_REPORTE"><?php echo $subtitulo; ?></td>
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
			/* Consulta de la cabecera del reporte */
			$row_institucion = $this->getRowConsulta(22, $sucursal, $obBD);	
			/* Consulta los datos del usuario */
			$row_usuario = $this->getRowConsulta(23, $usuario, $obBD);
			
			$fecha=explode("-",date("Y-m-d"));	
	   	    $fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0] ;	
				
		?>
			<table width="80%" border="0" cellpadding="0" cellspacing="0">
	   		  <tr align="center">
			    <td valign="top"><hr /></td>
	  		  </tr>
			  <tr align="center">
			    <td width="75%" valign="top" class="Texto_Reporte"><div align="center"><strong>FECHA IMPRESI&Oacute;N:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;		      <strong>USUARIO:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
		      </tr>
		    </table>
	<?php
		} 
}
?>