<?Php 
/**
 * Logica de las paginas que tienen que ver con las faltas
 *
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualización:	26-10-2012
 *
 * @package tesoreria.LOGICA
 */

require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("fac_sql_deudas.php");

/**
 * Clase para conexion a la capa de acceso a datos
 */
class Class_Log_Conexion_Deu extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/**
 * Clase para los datos a la capa de acceso a datos
 */
class Class_Log_Datos_Deu extends MysqlDatos{
	
	/**
	* Realiza una consulta en la base de datos.
	*
	* @return result si existen datos de retorno
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Deu $obBD para realizar la conexcion correspondiente
	*/
	function consultasobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_deu($sen_sql,$Par_Sql), $obBD->conexion);
	}
	
	
	/**
	 * Realiza una consulta en la base de datos -  STARDARD
	 *
	 * @return result si existen datos de retorno
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Deu $obBD para realizar la conexcion correspondiente
	 */
	function operacionobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_deu($sen_sql,$Par_Sql), $obBD->conexion);
	}
	
	/**
	 * Ejecuta cualquier consulta a la base de datos
	 * @return array $row fila de datos
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Deu $obBD para realizar la conexcion correspondiente
	 */
	function getRowConsulta($sen_sql,$param,$obBD = null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		
		$row =  $this->fetch_assoc($result);
		
		$this->free_result($result);
		
		return is_array($row) ? $row : array();
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos
	 * @return array $array arreglo de datos asociados
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Deu $obBD para realizar la conexcion correspondiente
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
	 * @param Class_Log_Datos_Deu $obBD objeto de conexion
	 */
	function insertUpdateDelete($sen_sql,$param, $obBD = null)
	{
		/**
		 * Inicio de la transaccion
		 */
		$this->inicio_transaccion($obBD->conexion);
	
		/**
		 * Ejecutar sentencia
		 */
		$this->operacionobBD($sen_sql,$param,$obBD);
	
		/**
		 * Codigo de autoincremento
		 * @var int
		 */
		$cod = $this->insercionid($obBD->conexion);
		/**
		 * Finalizar transaccion
		 */
		$this->fin_transaccion($obBD->conexion);
		
		return $cod;
	}
	
	/**
	 * Genera las deudas desde la tabla costos para los estudiantes con matricula activa en el periodo actual
	 * @param number $Cli_Cod
	 * @param Class_Log_Datos_Deu $obBD_conexion
	 */
	function generar_deudas($Cli_Cod, $obBD_conexion)
	{
	
		$hoy = date("Y-m-d");
		 
		/**
		 * Consulta de las matriculas que tiene el cliente en las diferentes modalidades 
		 */
		$Arr_Matriculas = $this->getArrayConsulta(168, $hoy.'*'.$Cli_Cod.'*'.'A', $obBD_conexion);
	
		/**
		 * Si el cliente posee matriculas procede a buscar los costos en la fecha actual 
		 */
		if (count($Arr_Matriculas) > 0)
		{
			$this->inicio_transaccion($obBD_conexion->conexion);
			
			foreach ($Arr_Matriculas as $row_rs_matriculas){
				
				$Nge_Cod = $row_rs_matriculas['Nge_Cod'];
				
				/**
				 * C O N T R O L   D E   P E N S I O N E S   Y   O T R O S   R U B R O S
				 *
				 * Consulta todos los costos menores o iguales a la fecha indicada 
				 */
				$Arr_Costos = $this->getArrayConsulta(169, $row_rs_matriculas['Sem_Cod'].'*'.$hoy.'*'.'N', $obBD_conexion);
	
				if (count($Arr_Costos) > 0)
				{
					foreach($Arr_Costos as $row_rs_costos){
						
						$Pro_Cod = $row_rs_costos['Pro_Cod'];
						$Cos_Pre = $row_rs_costos['Cos_Pre'];
						$Cos_Fec = $row_rs_costos['Cos_Fec'];
						$Asi_Int = $row_rs_costos['Asi_Int'];
						 
						/**
						 * Control de beca reasignadas
						 * Consultar si Bec_Cod esta NULL
						 */
						$Arr_Deuda_Existe = $this->getArrayConsulta(383, $Pro_Cod.'*'.$Nge_Cod.'*'.$Cli_Cod, $obBD_conexion);
						
						if( count($Arr_Deuda_Existe) > 0 )
						{    
							/**
							 * Consultar si el producto se encuentra en la tabla becas 
							 */
							$Arr_deuda_Asignada = $this->getArrayConsulta(384, $row_rs_matriculas['Mat_Int'].'*'.$Pro_Cod, $obBD_conexion);
	
							if( count($Arr_deuda_Asignada) > 0 )
							{
								/**
								 * Baja de la deuda registrada en la tabla deudas
								 */
								$this->operacionobBD(385, $Pro_Cod.'*'.$Nge_Cod.'*'.$Cli_Cod.'*'.$Arr_Deuda_Existe[0]['Deu_Int'], $obBD_conexion);
							}
						}
						/***************************************************************/
						/**** Fin control de beca reasignadas ****/
						/***************************************************************/
	
						/**
						 * Consulta de la posible beca y su porcentaje que tenga el estudiante ACTUAL
						 */
						$Arr_Becas = $this->getArrayConsulta(73, $Cli_Cod.'*'.$Pro_Cod.'*'.$row_rs_matriculas['Sem_Cod'], $obBD_conexion);
	
						if (count($Arr_Becas) > 0)
						{
							$Bec_Cod = $Arr_Becas[0]['Bec_Cod'];
						}
						else
						{
							$Bec_Cod = 'NULL';
						}
	
						/**
						 * Consulta, si el costo actual ya se encuentra registrado en Deudas 
						 */
						$Arr_existe_deudas = $this->getArrayConsulta(170, $row_rs_matriculas['Sem_Cod'].'*'.$Pro_Cod.'*'.$Cli_Cod.'*'.$Asi_Int, $obBD_conexion);
						$total_existe_deudas = count($Arr_existe_deudas);
	
						/**
						 * Si es igual a cero (0) significa que no esta ingresada esa deuda 
						 */
						if ($total_existe_deudas == 0)
						{
							$row = $this->getRowConsulta(10, $Cli_Cod.'*'.$Nge_Cod, $obBD_conexion);
							
							$Deu_Int = isset($row['Deu_Int'])? $row['Deu_Int'] + 1:1;
							
							/**
							 * Inserta las deudas generadas automaticamente 
							 */
							$this->operacionobBD(171, $Pro_Cod.'*'.$Nge_Cod.'*'.$Cli_Cod.'*'.$Cos_Pre.'*'.$hoy.'*'.$Cos_Fec.'*'.$Bec_Cod.'*'.'0'.'*'.$Deu_Int, $obBD_conexion);
						}
					}
				}
				
				/**
				 * C O N T R O L   DE   M A T R I C U L A S
				 * Consulta todos los costos menores o iguales a la fecha indicada 
				 */
				$Arr_costos_matr =$this->getArrayConsulta(196, $row_rs_matriculas['Pem_Cod'].'*'.$hoy."*".$row_rs_matriculas['Sem_Cod'], $obBD_conexion); 
				 
				if (count($Arr_costos_matr) > 0)
				{
					foreach($Arr_costos_matr as $row_rs_costos_matr){
						
						$Pro_Cod = $row_rs_costos_matr['Pro_Cod'];
						$Cos_Pre = $row_rs_costos_matr['Cos_Pre'];
						$Asi_Int = 0;
						$Cos_Fec = $hoy; //La matricula vence el mismo dia de registro
						$Bec_Cod = 'NULL'; //No hay becas en rubros de matriculas
	
						/**
						 * Consulta, si el costo actual ya se encuentra registrado en Deudas 
						 */
						$Arr_existe_deudas = $this->getArrayConsulta(170, $row_rs_matriculas['Sem_Cod'].'*'.$Pro_Cod.'*'.$Cli_Cod.'*'.$Asi_Int, $obBD_conexion);
	
						/**
						 * Si es igual a cero (0) significa que no esta ingresada esa deuda 
						 */
						if (count($Arr_existe_deudas) == 0)
						{
							$row = $this->getRowConsulta(10, $Cli_Cod.'*'.$Nge_Cod, $obBD_conexion);
								
							$Deu_Int = isset($row['Deu_Int'])? $row['Deu_Int'] + 1:1;
							
							/**
							 * Inserta las deudas generadas automaticamente 
							 */
							$this->operacionobBD(171, $Pro_Cod.'*'.$Nge_Cod.'*'.$Cli_Cod.'*'.$Cos_Pre.'*'.$hoy.'*'.$Cos_Fec.'*'.$Bec_Cod.'*'.'0'.'*'.$Deu_Int, $obBD_conexion);
						}
					}
				}
			}
			$this->fin_transaccion_nomsn($obBD_conexion->conexion);
		}
	}
	
	/**
	 * Funcion que genera el interes automaticamente
	 * @param number $Cli_Cod código único del cliente
	 * @param number $Pro_Cod código único del producto
	 * @param number $Nge_Cod codigo de notas gener del estudiante
	 * @param number $saldo saldo actual del rubro
	 * @param number $Deu_Int codigo incremental por contrato
	 * @param Class_Log_Datos_Deu $obBD_conexion
	 */
	function interes($Cli_Cod, $Pro_Cod, $Nge_Cod, $saldo, $Deu_Int,$obBD_conexion)
	{
		/**
		 * Fecha actual del sistema
		 */
		$hoy = date("Y-m-d");
		
		/**
		 * Consulta si el rubro o producto acepta interes 
		 */
		$Arr_Acepta_int = $this->getArrayConsulta(51, $Pro_Cod, $obBD_conexion);
		
		/**
		 * Si esto es verdadero significa que va a verificar si se debe generar el interes 
		 */
		if (count($Arr_Acepta_int) > 0)
		{
			/**
			 * Inicia la transacción
			 */
			$this->inicio_transaccion($obBD_conexion->conexion);
		
			/**
			 * Consulta los dias de mora que tiene un rubro 
			 */
			$row_rs_mora = $this->getRowConsulta(54, $Cli_Cod.'*'.$Pro_Cod.'*'.$Nge_Cod.'*'.$Asi_Int.'*'.$Deu_Int, $obBD_conexion);
				
			/**
			 * Si es menor a cero significa que se debe contar los dias de prorroga para el cobro del interes 
			 */
			if ($row_rs_mora['Mora'] < 0)
			{
				/**
				 * Consulta de los dias de prorroga del interes y calculo del interes 
				 */
				$Arr_Interes = $this->getArrayConsulta(56, $Ses_Emp_Cod, $obBD_conexion);
		
				foreach($Arr_Interes as $rs_interes){
					
					/**
					 * Se suma los dias de prorroga $row_rs_interes['Por_Dia'] ya $row_rs_mora['Mora'] es negativo 
					 */
					$dias_mora = $row_rs_mora['Mora'] + $row_rs_interes['Int_Dia'];
					
					/**
					 * Si aun de los dias de prorroga el valor es negativo entonces se debe calcular el interes 
					 */
					if ($dias_mora < 0)
					{
						
						/**
						 * Consulta los rubros recursivos (INTERES) 
						 */
						$Arr_existe = $this->getArrayConsulta(57, $Cli_Cod.'*'.$Nge_Cod.'*'.$Asi_Int.'*'.$Pro_Cod.'*'.$Deu_Int, $obBD_conexion);
		
						$Bec_Cod = 'NULL';
		
						if (count($Arr_existe) == 0)
						{
							$porc_int = abs($dias_mora) * $row_rs_interes['Int_Por'];
							
							$interes = ($saldo * $porc_int) / 100;
							
							/**
							 * Inserta las deudas INTERES por primera vez 
							 */
							$this->operacionobBD(171, $row_rs_interes['Pro_Cod'].'*'.$Nge_Cod.'*'.$Cli_Cod.'*'.$interes.'*'.$hoy.'*'.$row_rs_mora['Deu_Fec'].'*'.$Bec_Cod.'*'.$Pro_Cod.'*'.$Asi_Int.'*'.$Deu_Int, $obBD_conexion);
						}
						else
						{
							$row_rs_existe = $Arr_existe[0];
							
							/**
							 * Control para saber si debe calcular el interes un dia despues del ultimo calculo 
							 */
							if ($row_rs_existe['Dias_Mora'] < 0)
							{
								$porc_int = abs($row_rs_existe['Dias_Mora']) * $row_rs_interes['Int_Por'];
								
								$interes = ($saldo * $porc_int) / 100;
								
								$interes_anterior = $row_rs_existe['Deu_Val'];
								
								$acum_interes = $interes_anterior + $interes;
								
								/**
								 * Actualiza el interes 
								 */
								$this->operacionobBD(64, $acum_interes.'*'.$hoy.'*'.$Nge_Cod.'*'.$Cli_Cod.'*'.$Asi_Int.'*'.$row_rs_interes['Pro_Cod'].'*'.$Pro_Cod.'*'.$Deu_Int, $obBD_conexion);
							}
								
						}
								
					}
				}
						
			}
			
			/**
			 * Finaliza la transacción 
			 */
			$this->fin_transaccion_nomsn($obBD_conexion->conexion);
		}
	}
	
	
	/**
	 * Funcion que genera el interes automaticamente
	 * @param number $Cli_Cod código único del cliente
	 * @param number $Pro_Cod código único del producto
	 * @param number $Cnt_Cod codigo de contrato del cliente
	 * @param number $saldo saldo actual del rubro
	 * @param number $Deu_Int codigo incremental por contrato
	 * @param Class_Log_Datos_Deu $obBD_conexion
	 */
	function InteresServicios($Cli_Cod, $Pro_Cod, $Cnt_Cod, $saldo, $Deu_Int,$obBD_conexion)
	{
		/**
		 * Fecha actual del sistema
		 */
		$hoy = date("Y-m-d");
		
		/**
		 * Consulta si el rubro o producto acepta interes
		 */
		$Arr_Acepta_int = $this->getArrayConsulta(51, $Pro_Cod, $obBD_conexion);
		
		/**
		 * Si esto es verdadero significa que va a verificar si se debe generar el interes
		 */
		if (count($Arr_Acepta_int) > 0)
		{
			/**
			 * Inicia la transacción
			 */
			$this->inicio_transaccion($obBD_conexion->conexion);
		
			/**
			 * Consulta los dias de mora que tiene un rubro
			 */
			$row_rs_mora = $this->getRowConsulta(3, $Cli_Cod.'*'.$Pro_Cod.'*'.$Cnt_Cod.'*'.$Asi_Int.'*'.$Deu_Int, $obBD_conexion);
		
			/**
			 * Si es menor a cero significa que se debe contar los dias de prorroga para el cobro del interes
			 */
			if ($row_rs_mora['Mora'] < 0)
			{
				/**
				 * Consulta de los dias de prorroga del interes y calculo del interes
				 */
				$Arr_Interes = $this->getArrayConsulta(56, $Ses_Emp_Cod, $obBD_conexion);
		
				foreach($Arr_Interes as $rs_interes){
						
					/**
					 * Se suma los dias de prorroga $row_rs_interes['Por_Dia'] ya $row_rs_mora['Mora'] es negativo
					 */
					$dias_mora = $row_rs_mora['Mora'] + $row_rs_interes['Int_Dia'];
						
					/**
					 * Si aun de los dias de prorroga el valor es negativo entonces se debe calcular el interes
					 */
					if ($dias_mora < 0)
					{
		
						/**
						 * Consulta los rubros recursivos (INTERES)
						 */
						$Arr_existe = $this->getArrayConsulta(4, $Cli_Cod.'*'.$Cnt_Cod.'*'.$Asi_Int.'*'.$Pro_Cod.'*'.$Deu_Int, $obBD_conexion);
		
						$Bec_Cod = 'NULL';
		
						if (count($Arr_existe) == 0)
						{
							$porc_int = abs($dias_mora) * $row_rs_interes['Int_Por'];
								
							$interes = ($saldo * $porc_int) / 100;
								
							/**
							 * Inserta las deudas INTERES por primera vez
							 */
							$this->operacionobBD(5, $row_rs_interes['Pro_Cod'].'*'.$Cnt_Cod.'*'.$Cli_Cod.'*'.$interes.'*'.$hoy.'*'.$row_rs_mora['Deu_Fec'].'*'.$Bec_Cod.'*'.$Pro_Cod.'*'.$Asi_Int.'*'.$Deu_Int, $obBD_conexion);
						}
						else
						{
							$row_rs_existe = $Arr_existe[0];
								
							/**
							 * Control para saber si debe calcular el interes un dia despues del ultimo calculo
							 */
							if ($row_rs_existe['Dias_Mora'] < 0)
							{
								$porc_int = abs($row_rs_existe['Dias_Mora']) * $row_rs_interes['Int_Por'];
		
								$interes = ($saldo * $porc_int) / 100;
		
								$interes_anterior = $row_rs_existe['Deu_Val'];
		
								$acum_interes = $interes_anterior + $interes;
		
								/**
								 * Actualiza el interes
								 */
								$this->operacionobBD(6, $acum_interes.'*'.$hoy.'*'.$Cnt_Cod.'*'.$Cli_Cod.'*'.$Asi_Int.'*'.$row_rs_interes['Pro_Cod'].'*'.$Pro_Cod.'*'.$Deu_Int, $obBD_conexion);
							}
		
						}
		
					}
				}
		
			}
			/**
			 * Finaliza la transacción
			 */
			$this->fin_transaccion_nomsn($obBD_conexion->conexion);
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
		$row_institucion = $this->getRowConsulta(48, $sucursal, $obBD);
		
		/**
		 * Consulta la provicia y pais de la sucursal 
		 */
		$row_provincia = $this->getRowConsulta(47, $row_institucion['Ciu_Cod'], $obBD);
			
		?>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
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
					echo $row_institucion['Ciu_Des'].$provincia;?></div>
				</td>
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
			/**
			 * Consulta de la cabecera del reporte 
			 */
			$row_institucion = $this->getRowConsulta(48, $sucursal, $obBD);	

			/**
			 * Consulta los datos del usuario 
			 */
			$row_usuario = $this->getRowConsulta(49, $usuario, $obBD);
					
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