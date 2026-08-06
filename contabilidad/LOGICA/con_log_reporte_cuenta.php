<?Php 
/**
 * Logica de las paginas de reporte de cuentas
 *
 * @author Patricio Moreno
 * @version 1.0
 * Fecha de actualizacion: 2025-03-24
 *
 * @package contabilidad.LOGICA
 */
require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("con_sql_reporte_cuenta.php");

/*Clase para conexion a la capa de acceso a datos*/
class Class_Log_Conexion_Con extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/*Clase para acceder a los datos*/

class Class_Log_Datos_Con extends MysqlDatos{
	
	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function consultasobBD($sen_sql,$param, $obBD = null) {
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_con($sen_sql,$Par_Sql), $obBD->conexion);
	}

	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function operacionobBD($sen_sql,$param, $obBD = null) {
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_con($sen_sql,$Par_Sql), $obBD->conexion);
	}
	

	function getMonthRanges($start, $end) {
		$timeStart = strtotime($start);
		$timeEnd = strtotime($end);
		$out = array();
		$milestones[] = $timeStart;
		$timeEndMonth = strtotime('first day of next month midnight', $timeStart);
		while ($timeEndMonth < $timeEnd) {
			$milestones[] = $timeEndMonth;
			$timeEndMonth = strtotime('+1 month', $timeEndMonth);
		}
		$milestones[] = $timeEnd;

		$count = count($milestones);
		for ($i = 1; $i < $count; $i++) {

			if($i == ($count-1)){
				$end = $milestones[$i];
			} else {
				$end = $milestones[$i] - 1;
			}

			$out[] =  array(
				'start' => $milestones[$i - 1],
				'end'   => $end
			);
		}
		return $out;
	}


	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	 * @return array $row fila de datos
	 */
	function getRowConsulta($sen_sql,$param,$obBD = null) {
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
	 * @param Class_Log_Datos_Con $obDT para la abtraccion de los datos
	 * @return array $array arreglo de datos asociados
	 */ 
	function getArrayConsulta($sen_sql,$param,$obBD = null) {
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		
		$array = array();
		
		while($row_rs = $this->fetch_assoc($result)) {
			$array[] = $row_rs;
		}
		
		$this->free_result($result);
		
		return $array;
	}

	/**
	 * Inserta o actualiza o elimina los datos de una sola transacccion -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de datos
	 * @param Class_Log_Datos_Con $obBD objeto de conexion
	 */
	function insertUpdateDelete($sen_sql,$param, $obBD = null) {		
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
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD){
		/* Consulta de la cabecera del reporte */
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);
		/* Consulta la provicia y pais de la sucursal */
		$row_provincia = $this->getRowConsulta(3, $row_institucion['Ciu_Cod'], $obBD);	
			
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
				if (count($row_provincia) > 0) {
					$provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
				} else {
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
	 * @param int $sucursal Codigo de la sucursal
	 * @param string $usuario Codigo del usuario 
	 */	
	function pieReporteStandar($sucursal, $usuario, $obBD) {
		/* Consulta de la cabecera del reporte */
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);
		/* Consulta los datos del usuario */
		$row_usuario = $this->getRowConsulta(4, $usuario, $obBD);
		
		$fecha=explode("-",date("Y-m-d"));			
		
		$fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0].", ".date("H:m:s") ;	
			
	?>
		<table width="80%" border="0" cellpadding="0" cellspacing="0">
			<tr align="center">
				<td colspan="2" valign="top"><hr /></td>
			</tr>
			<tr>
				<td align="center" width="33%" valign="top" class="Texto_Reporte"><div align="center"><br><br><br>________________________ <br> <strong>CONTADOR</strong> <br><?php echo $row_institucion['Emp_Con'] ?>&nbsp;<br><strong>C.I.: </strong><?php echo $row_institucion['Emp_Rco'] ?></div></td>
				<td align="center" width="33%" valign="top" class="Texto_Reporte"><div align="center"><br><br><br>________________________ <br><strong>GERENTE</strong><br>&nbsp;<?php echo $row_institucion['Emp_Rep']?><br><strong>C.I.: </strong><?php echo $row_institucion['Emp_Rre'] ?></div></td>
				<td align="center" width="33%" valign="top" class="Texto_Reporte"><div align="center"><strong>Fecha de Impresi&oacute;n:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;</div></td>
			</tr>
		</table>
<?php
	}
	/* Funcion que devuelve un arreglo de los reportes del proceso */
	function reportes($pagina, $empresa, $obBD_conexion) {
		$pag = explode("/", $pagina);
		$row_rs_proceso = $this->getRowConsulta(12, $pag[count($pag)-1], $obBD_conexion);
		$row_rs_reporte = $this->getArrayConsulta(13, $row_rs_proceso['Pcs_Cod'].'*'.$empresa, $obBD_conexion);
		$i=0;
		foreach ($row_rs_reporte as $row){
			$i++;
			$reporte[$i] = $row['Pcs_Nom'];		
		}
		return $reporte;
	}
}
?>