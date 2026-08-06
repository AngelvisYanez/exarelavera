<?Php 
/**
 * Logica de las paginas de anexo transaccional
 *
 * @author Lewis Chimarro
 * @version 3.0
 * Fecha de actualizaciï¿½n:	2013-JUN-26
 *
 * @package tesoreria.LOGICA
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("tes_sql_anexo.php");

/**
 * Clase para conexion a la capa de acceso a datos
 *
 * @author Lewis Chimarro
 *
 * @package administrador.LOGICA
 */
class Class_Log_Conexion_Anx extends MysqlConexion{

}

/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
 * @package tesoreria.LOGICA
 */
class Class_Log_Datos_Anx extends MysqlDatos{
	
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
		return $this->consulta(sentencias_anx($sen_sql,$Par_Sql), $obBD->conexion);
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
		$Query = sentencias_anx($sen_sql,$this->parametros($param));
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

		return is_array($row) ? $row : array();
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
	 * Formato standar para reportes
	 * @param int $sucursal Cï¿½digo de la sucursal
	 * @param string $titulo Tï¿½tulo del reporte
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
			    <td width="5%" rowspan="5" valign="top"><img src="<?php echo $row_institucion['../../administrador/LOGICA/Emp_Log']; ?>" width="83" height="67" /></td>
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
		 * @param int $sucursal Cï¿½digo de la sucursal
		 * @param string $usuario Cï¿½digo del usuario 
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
		/**
		* Agrega la serie a numeros de factura que solo contienen el secuencia 
		*/
		function establecimiento($codigo)
		{
			if ($codigo != "")
			{
				$estab = explode('-',$codigo);
				
				if (count($estab) == 1)	
				{
					unset($estab);
					$estab[0] = "001";
					$estab[1] = "001";
					$estab[2] = $codigo;				
				}
			}
			return $estab;
		}
              
                /* Elimina cualquier tipo de letra en un codigo de retencion */	
		function cod_air($codigo)
		{
			$air = substr($codigo,0,3);
			return $air;
		}
                                
}
?>