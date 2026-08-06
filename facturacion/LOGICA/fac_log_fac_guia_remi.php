<?php
/**
 * Logica de las paginas de facturaciÃ³n ventas
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaciÃ³n:	2012-05-07
 *
 * @package tesoreria.LOGICA
 */

require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once ("fac_sql_fac_guia_remi.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro 
 *
 * @package contabilidad.LOGICA
*/
class Class_Log_Conexion_Tes extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
 */
class Class_Log_Datos_Tes extends MysqlDatos{
	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function consultasobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_tes($sen_sql,$Par_Sql), $obBD->conexion);
	}

	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function operacionobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_tes($sen_sql,$Par_Sql), $obBD->conexion);
	}
	
	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
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
	 * @param Class_Log_Datos_Con $obDT para la abtraccion de los datos
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
	 * @param Class_Log_Datos_Con $obBD objeto de conexion
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
	 * @param int $sucursal CÃ³digo de la sucursal
	 * @param string $titulo TÃ­tulo del reporte
	 * @param string $subtitulo Subtitulo del reporte
	 */	
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD)
	{ 
		/* 
		* Consulta de la cabecera del reporte 
		*/
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);
		/* 
		* Consulta la provicia y pais de la sucursal 
		*/
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
	 * @param int $sucursal CÃ³digo de la sucursal
	 * @param string $usuario CÃ³digo del usuario 
	 */	
	function pieReporteStandar($sucursal, $usuario, $obBD)
	{ 
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
		    <td align="center" width="50%" valign="top" class="Texto_Reporte"><div align="center"><strong>Fecha de Impresi&oacute;n:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;</div></td>
		    <td align="center" width="50%" valign="top" class="Texto_Reporte"><div align="center"><strong>Usuario:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
	      </tr>
	    </table>
<?php
	} 	

	/* 
	* Funcion que devuelve un arreglo de los reportes del proceso 
	*/
	function reportes($pagina, $empresa, $obBD_conexion)
	{
		$pag = explode("/", $pagina);
		$row_rs_proceso = $this->getRowConsulta(12, $pag[count($pag)-1], $obBD_conexion);
			
		$row_rs_reporte = $this->getArrayConsulta(13, $row_rs_proceso['Pcs_Cod'].'*'.$empresa, $obBD_conexion);
		
		$i=0;
		foreach ($row_rs_reporte as $row)
		{
			$i++;
			$reporte[$i] = $row['Rut_Des'].$row['Pcs_Nom'];		
		}		
		return $reporte;
	}	

	 /* 
	 * Genera las deudas desde la tabla costos para los estudiantes con matricula activa en el periodo actual 
	 */
	 function generarDeudas($Cli_Cod, $obBD_conexion)
	 {		
		$hoy = date("Y-m-d");		 
		/* 
		* Consulta de las matriculas que tiene el cliente en las diferentes modalidades 
		*/
		$row_rs_matriculas = $this->getArrayConsulta(168, $hoy.'*'.$Cli_Cod.'*'.'A', $obBD_conexion); //Antes $rs_confimatri['Con_Mac']
		
		/* 
		* Si el cliente posee matriculas procede a buscar los costos en la fecha actual 
		*/
		if (count($row_rs_matriculas) > 0)
		{
		 /*
		 * Inicio de la transaccion
		 */
		 $this->inicio_transaccion($obBD_conexion->conexion);
		 
		foreach ($row_rs_matriculas as $row_mat)
		{
		 $Nge_Cod = $row_mat['Nge_Cod'];
		 /*************************************************************************/
		 /* 
		 * C O N T R O L   D E   P E N S I O N E S   Y   O T R O S   R U B R O S 
		 */
		 /*************************************************************************/   
		 /* 
		 * Consulta todos los costos menores o iguales a la fecha indicada 
		 */
		 $row_rs_costos = $this->getArrayConsulta(169, $row_mat['Sem_Cod'].'*'.$hoy.'*'.'N', $obBD_conexion);
		  
		 if (count($row_rs_costos) > 0)
		 {   
	   		foreach ($row_rs_costos as $row_cost)
			{
				$Pro_Cod = $row_cost['Pro_Cod'];   
				$Cos_Pre = $row_cost['Cos_Pre'];
				$Cos_Fec = $row_cost['Cos_Fec'];
				$Asi_Int = $row_cost['Asi_Int']; 
				 
				/*
				* Control de beca reasignadas 
				*/
				/* 
				* Consultar si Bec_Cod esta NULL
				*/
				$row_rs_existe = $this->getRowConsulta(383, $Pro_Cod.'*'.$Nge_Cod.'*'.$Cli_Cod, $obBD_conexion);

				if(count($row_rs_existe) >0)
				{    
					/* 
					* Consultar si el producto se encuentra en la tabla becas 
					*/
				  $row_rs_existe_deuda=$this->getRowConsulta(384, $row_mat['Mat_Int'].'*'.$Pro_Cod, $obBD_conexion);
				  
				  if(count($row_rs_existe_deuda) > 0)
				  {
				   /* 
				   * Baja de la deuda registrada en la tabla deudas
				   */
				$this->grabarv_registros(sentencias_tes(385, $this->parametros($Pro_Cod.'*'.$Nge_Cod.'*'.$Cli_Cod.'*'.$Asi_Int)),$obBD_conexion->conexion); 
				  }//FIn del if($num_row_rs_existe_deuda>0)       
				}//Fin del if($num_row_rs_existe>0)
				/*
				* Fin control de beca reasignadas 
				*/				
				/* 
				* Consulta de la posible beca y su porcentaje que tenga el estudiante ACTUAL
				*/
				$row_rs_becas = $this->getRowConsulta(73, $Cli_Cod.'*'.$Pro_Cod.'*'.$row_mat['Sem_Cod'], $obBD_conexion); 
				
				if (count($row_rs_becas) > 0)
				{
				 $Bec_Cod = $row_rs_becas['Bec_Cod'];
				}
				else
				{
				 $Bec_Cod = 'NULL';
				}    
				
				/* 
				* Consulta, si el costo actual ya se encuentra registrado en Deudas 
				* - Se mantiene la forma anterior de programar
				*/
				$row_existe_deudas = $this->consulta(sentencias_tes(170, $this->parametros
				 ($row_mat['Sem_Cod'].'*'.$Pro_Cod.'*'.$Cli_Cod.'*'.$Asi_Int)), 
				 $obBD_conexion->conexion);    
				$total_existe_deudas = $this->numregistros();
			  
				/* 
				* Si el Asi_Int es diferente de cero, entonces significa que el costo para dicho semestre y 
				* asignatura, es modular 
				*/     
				if ($Asi_Int != 0)
				{
				 /* 
				 * Consulta la existencia de la asignatura del cliente en el semestre determinado y de tipo Normal 
				 */       
				 $row_rs_asignatura = $this->getRowConsulta(185, $row_mat['Sem_Cod'].'*'.$Cli_Cod.'*'.$Asi_Int.'*'.'N', $obBD_conexion);  				 
				 /* 
				 * Si la cantidad es igual a cero, significa que no tiene registrada la asigantura 
				 */   
				 if (count($row_rs_asignatura) == 0)
				 {
				  /* 
				  * Se inicializa en 1 la variable para que no se le cargue la deuda 
				  */
				  $total_existe_deudas = 1;
				 }//Fin del if ($total_rs_asignatura == 0)
				}//Fin del if ($Asi_Int != 0)
				
				/* 
				* Si es igual a cero (0) significa que no esta ingresada esa deuda 
				*/
				if ($total_existe_deudas == 0)
				{
				 /* 
				 * Inserta las deudas generadas automaticamente 
				 */
				 $this->grabarv_registros(sentencias_tes(171, $this->parametros($Pro_Cod.'*'.$Nge_Cod.'*'.
				 $Cli_Cod.'*'.$Cos_Pre.'*'.$hoy.'*'.$Cos_Fec.'*'.$Bec_Cod.'*'.'0'.'*'.$Asi_Int)), 
				 $obBD_conexion->conexion);      
				}//Fin del if ($total_existe_deudas == 0)
			}// FIn del foreach -> $row_rs_costos
	   }//Fin del if ($total_rs_costos > 0)
	   /********************************************/
	   /* 
	   * C O N T R O L   DE   M A T R I C U L A S 
	   */
	   /********************************************/   
	   /* 
	   * Consulta todos los costos menores o iguales a la fecha indicada 
	   */
	   $row_rs_costos_matr = $this->getArrayConsulta(196, $row_mat['Pem_Cod'].'*'.$hoy."*".$row_mat['Sem_Cod'], $obBD_conexion);
	   
	   if (count($row_rs_costos_matr) > 0)
	   {   
			foreach ($row_rs_costos_matr as $row_cts)
			{
				 $Pro_Cod = $row_cts['Pro_Cod'];
				 $Cos_Pre = $row_cts['Cos_Pre'];
				 $Asi_Int = 0;
				 $Cos_Fec = $hoy; //La matricula vence el mismo dia de registro
				 $Bec_Cod = 'NULL'; //No hay becas en rubros de matriculas
				 
				 /* 
				 * Consulta, si el costo actual ya se encuentra registrado en Deudas 
				 */
				 $row_existe_deudas = $this->getRowConsulta(170, $row_mat['Sem_Cod'].'*'.$Pro_Cod.'*'.$Cli_Cod.'*'.$Asi_Int, $obBD_conexion);    		
				 /* 
				 * Si es igual a cero (0) significa que no esta ingresada esa deuda 
				 */
				 if (count($row_existe_deudas) == 0)
				 {
				  /* 
				  * Inserta las deudas generadas automaticamente 
				  */
				  $this->grabarv_registros(sentencias_tes(171, $this->parametros($Pro_Cod.'*'.$Nge_Cod.'*'.
					 $Cli_Cod.'*'.$Cos_Pre.'*'.$hoy.'*'.$Cos_Fec.'*'.$Bec_Cod.'*'.'0'.'*'.$Asi_Int)), 
					 $obBD_conexion->conexion);      
				 }//Fin del if ($total_existe_deudas == 0)
			}//Fin del foreach $row_rs_costos_matr 
	   	}//Fin del if ($total_rs_costos_matr > 0)	
	  }//Fin del foreach -> $rs_matriculas 
	   /****************************************************************/
	   $this->fin_transaccion_nomsn($obBD_conexion->conexion);
	   /***************************************************************/                       
	  }//Fin del if ($total_rs_matriculas > 0)
	}//Fin de function generar_deudas($obBD_conexion, $obBD_con1, Cli_Cod)
	
	/* 
	* Funcion que genera el interes automaticamente 
	*/
	function interes($Cli_Cod, $Pro_Cod, $Nge_Cod, $Asi_Int, $saldo, $obBD_conexion)
	{
		$hoy = date("Y-m-d");
		/* 
		* Consulta si el rubro o producto acepta interes 
		*/
		$row_si_interes = $this->getRowConsulta(51, $Pro_Cod, $obBD_conexion);		
		/* 
		* Si esto es verdadero significa que va a verificar si se debe generar el interes 
		*/
		if (count($row_si_interes) > 0)
		{
			/*
			*Inicio de la transaccion
			*/
			$this->inicio_transaccion($obBD_conexion->conexion);		
			/* 
			* Consulta los dias de mora que tiene un rubro 
			*/
			$row_rs_mora = $this->getRowConsulta(54, $Cli_Cod.'*'.$Pro_Cod.'*'.$Nge_Cod.'*'.$Asi_Int, $obBD_conexion);
			/* 
			* Si es menor a cero significa que se debe contar los dias de prorroga para el cobro del interes 
			*/
			if ($row_rs_mora['Mora'] < 0)
			{
				/* 
				* Consulta de los dias de prorroga del interes y calculo del interes 
				*/
				$row_rs_interes = $this->getArrayConsulta(56, $Ses_Emp_Cod, $obBD_conexion);
		
				foreach($row_rs_interes as $row_int)
				{
					/* 
					* Se suma los dias de prorroga $row_rs_interes['Por_Dia'] ya $row_rs_mora['Mora'] es negativo 
					*/
					$dias_mora = $row_rs_mora['Mora'] + $row_int['Int_Dia'];
					/* 
					* Si aun de los dias de prorroga el valor es negativo entonces de debe calcular el interes 
					*/
					if ($dias_mora < 0)
					{
						/* 
						* Consulta los rubros recursivos (INTERES) 
						*/
						$row_rs_existe = $this->getArrayConsulta(57, $Cli_Cod.'*'.$Nge_Cod.'*'.$Asi_Int.'*'.$Pro_Cod, $obBD_conexion);
						
						$Bec_Cod = 'NULL';
						
						if (count($row_rs_existe) == 0) //Si entra aqui es porque no esta creado el interes 
						{
							$porc_int = abs($dias_mora) * $row_int['Int_Por'];
							$interes = ($saldo * $porc_int) / 100;							
							/* 
							* Inserta las deudas INTERES por primera vez 
							*/
							$this->grabarv_registros(sentencias_tes(171, $this->parametros($row_int['Pro_Cod']
								.'*'.$Nge_Cod.'*'.$Cli_Cod.'*'.$interes.'*'.$hoy.'*'.$row_rs_mora['Deu_Fec'].'*'.$Bec_Cod.'*'.
								$Pro_Cod.'*'.$Asi_Int)), $obBD_conexion->conexion);																			
						}//Fin del if ($total_rs_existe == 0)
						else
						{
							/* 
							* Control para saber si debe calcular el interes un dia despues del 
							* ultimo calculo 
							*/
							if ($row_rs_existe['Dias_Mora'] < 0)
							{
								$porc_int = abs($row_rs_existe['Dias_Mora']) * $row_int['Int_Por'];
								$interes = ($saldo * $porc_int) / 100;							
								$interes_anterior = $row_rs_existe['Deu_Val'];
								$acum_interes = $interes_anterior + $interes; 
								/* 
								* Actualiza el interes 
								*/
								$this->grabarv_registros(sentencias_tes(64, $this->parametros($acum_interes.'*'.
									$hoy.'*'.$Nge_Cod.'*'.$Cli_Cod.'*'.$Asi_Int.'*'.$row_int['Pro_Cod'].'*'.$Pro_Cod)),
									$obBD_conexion->conexion);		
							}							
						}//Fin Else if ($total_rs_existe == 0)					
					}//Fin del if ($dias_mora < 0)				
				}//Fin del foreach $row_rs_interes
			
			}//Fin del if ($row_rs_mora['Mora'] < 0)
			/*
			* Fin de la transacciÃ³n
			*/
			$this->fin_transaccion_nomsn($obBD_conexion->conexion);
		}//Fin del if ($total_rs_si_interes > 0)
	}

	/*
	* funcion que devuelve el codigo automatico de la factura 
	*/	
	function codigoSiguiente($Aut, $Num_Ini, $obBD_conexion)
	{
		/* 
		* Incremento del numero manual de la factura dependiendo de la autorizacion
		*/
		$row_Max = $this->getRowConsulta(27, $Aut, $obBD_conexion);
	
		if ($row_Max['Num'] > 0)
		{
			$maximo = $row_Max['Num'];					
			$maximo++;			
		}//Fin del if ($row_Max['Num'] > 0)
		else
		{
			$maximo = $Num_Ini;
		}//Fin del else if ($row_Max['Num'] > 0)
		
		return $maximo;
		
		@$obBD_con1->free_result($rs_Max);
	} //Fin del codigo_siguiente($Aut, $Num_Ini, $obBD_con1, $obBD_conexion)
	
	/*
	* Funcion que realiza los calculos de totales de facturas
	*/
	function calculos($Vet_Cod, $obBD_conexion)
	{
		/* Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL */
		
		$row_rs_calculos = $this->getArrayConsulta(39, $Vet_Cod, $obBD_conexion); 

		foreach ($row_rs_calculos as $row)
		{
			/* % de Descuento total */
			$Vet_Des = $row['Vet_Des'];
		
			/* Calculo del total de la factura */
			$subtotal= $subtotal + $row['Vet_Imp'];
				
			/* Calculo de las tarifas */
			if ($row['Iva_Por'] == 0)
			{
				$tarifa_0 = $tarifa_0 + $row['Vet_Imp'];
				/*Descuento individual */
				$des_0 = $des_0 + round(($row['Vet_Imp'] * $row['Vet_Dec'])/100,2);
			}
			else
			{
				$tarifa_12 = $tarifa_12 + $row['Vet_Imp'];
				/*Descuento individual */
				$des_12 = $des_12 + ($row['Vet_Imp'] * $row['Vet_Dec'])/100;			
				$iva_12 = $row['Iva_Por'];
			}						
		}//Fin del foreach -> $row_rs_calculos 
		/* 
		* Suma del descuento 
		*/
		$des = $des_0 + $des_12;	
		/* 
		* Calculo del iva con descuento individual 
		*/
		$iva = (($tarifa_12 - $des_12) * $iva_12)/100;

		/* 
		* Calculo del descuento total 
		*/
		if ($Vet_Des != 0)
		{
			$des = ($subtotal * $Vet_Des) / 100;
			$des_12 = ($tarifa_12 * $Vet_Des) / 100;
			$iva = (($tarifa_12 - $des_12) * $iva_12)/100;		
		}
	
		/*
		* Calculo del total 
		*/
		$total = $subtotal - round($des,2) + $iva;
		
		return $subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$des.'*'.$total;		
	}	

	/* 
	* Calcula el total de las ventas 
	*/
	function calculosVentas($ini, $fin, $tipo, $tipo_compr, $Pun_Cod, $obBD_conexion)
	{
		/* Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL */

		/* Control para el punto de impresion */
		if ($Pun_Cod > 0)
		{
			$Pun_Cod = " AND caja_aper.Pun_Cod = ".$Pun_Cod;
		}
		else
		{
			$Pun_Cod = "";
		}//Fin del if ($Pun_Cod > 0)

		/* Consulta del total de las ventas */
		$rs_ventas = $this->consulta(sentencias_tes(235, $this->parametros($ini.'*'.$fin.'*'.$tipo.'*'.$tipo_compr.'*'.$Pun_Cod)), 
							$obBD_conexion->conexion);
		$row_rs_ventas = $this->registros();
		$total_rs_ventas = $this->numregistros();	

		$mover = false;
		$subtotal = 0;
		$tarifa_0 = 0;
		$tarifa_12 = 0;
		$iva = 0;
		$des = 0;
		$total = 0;
		
		if ($row_rs_ventas['Iva_Sri'] == 2)//2 es el valor en la tabla del sri
		{			
			$subtotal = $row_rs_ventas['Importe'];
			$tarifa_12 = $row_rs_ventas['Importe'];
			$iva = $row_rs_ventas['Iva'];
			$des = $row_rs_ventas['Descuento'];
			$total = $row_rs_ventas['Total'] + $iva;

			/* En caso de existir 2 registros de total de ventas se mueve el apuntador de la tabla */
			$mover = true;					
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 2)

		if ($mover == true)
		{
			/* Vuelve al fin del puntero la consulta creditos */
			$row_rs_ventas = first_last($rs_ventas, $row_rs_ventas, $total_rs_ventas);			  
		}//Fin del if ($mover == true)
		
		if ($row_rs_ventas['Iva_Sri'] == 0)//2 es el valor en la tabla del sri
		{				
			$subtotal = $subtotal + $row_rs_ventas['Importe']; //Suma los dos importes de la consulta
			$tarifa_0 = $row_rs_ventas['Importe'];
			$des = $des + $row_rs_ventas['Descuento'];
			$total = $total + $row_rs_ventas['Total'];
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 0)
	
		return $subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$des.'*'.$total;
	}//Fin del function calculos_ventas($ini, $fin)
	
	/* 
	* Calcula el total de las ventas 
	*/
	function calculosConsultaVentas($ini, $fin, $tipo, $tipo_compr, $Pun_Cod, $obBD_conexion)
	{  
		/* Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL */

		/* Control para el punto de impresion */
		if ($Pun_Cod > 0)
		{
			$Pun_Cod = " AND puntos_imp.Suc_Cod = ".$Pun_Cod;
		}
		else
		{
			$Pun_Cod = "";
		}//Fin del if ($Pun_Cod > 0)

		/* Consulta del total de las ventas */
		$rs_ventas = $this->consulta(sentencias_tes(1239, $this->parametros($ini.'*'.$fin.'*'.$tipo.'*'.$tipo_compr.'*'.$Pun_Cod)),$obBD_conexion->conexion);
		$row_rs_ventas = $this->registros();
		$total_rs_ventas = $this->numregistros();	
        
		$mover = false;
		$subtotal = 0;
		$tarifa_0 = 0;
		$tarifa_12 = 0;
		$iva = 0;
		$des = 0;
		$total = 0;
		
		if ($row_rs_ventas['Iva_Sri'] == 2)//2 es el valor en la tabla del sri
		{			
			$subtotal = $row_rs_ventas['Importe'];
			$tarifa_12 = $row_rs_ventas['Importe'];
			$iva = $row_rs_ventas['Iva'];
			$des = $row_rs_ventas['Descuento'];
			$total = $row_rs_ventas['Total'] + $iva;

			/* En caso de existir 2 registros de total de ventas se mueve el apuntador de la tabla */
			$mover = true;					
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 2)

		if ($mover == true)
		{
			/* Vuelve al fin del puntero la consulta creditos */
			$row_rs_ventas = first_last($rs_ventas, $row_rs_ventas, $total_rs_ventas);			  
		}//Fin del if ($mover == true)
		
		if ($row_rs_ventas['Iva_Sri'] == 0)//2 es el valor en la tabla del sri
		{				
			$subtotal = $subtotal + $row_rs_ventas['Importe']; //Suma los dos importes de la consulta
			$tarifa_0 = $row_rs_ventas['Importe'];
			$des = $des + $row_rs_ventas['Descuento'];
			$total = $total + $row_rs_ventas['Total'];
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 0)
	
		return $subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$des.'*'.$total;
	}//Fin del function calculos_ventas($ini, $fin)

	function enviarMail($Dest, $Subject, $MsgHTML)
	{
		require_once('../../Librerias/PHPMail/class.phpmailer.php');	
		$mail = new PHPMailer(true);	
		$mail->IsSMTP();
	
		try {
			$mail->SMTPDebug  = 0;                     // enables SMTP debug information (for testing)
			$mail->SMTPAuth   = true;                  // enable SMTP authentication
			$mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
			$mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
			$mail->Port       = 465;                   // set the SMTP port for the GMAIL server
			$mail->Username   = "lewis.chimarro@gmail.com";  // GMAIL username
			$mail->Password   = "integrado";            // GMAIL password
			$mail->AddAddress($Dest['Correo'], $Dest['Nombre']);		
			$mail->SetFrom('lewis.chimarro@gmail.com', 'Lewis Chimarro');
			$mail->AddReplyTo('lewis.chimarro@gmail.com', 'Lewis Chimarro');
	
			$mail->Subject = $Subject;
	
			$mail->MsgHTML($MsgHTML);
	
	
			$mail->Send();
	
			return true;
		} catch (phpmailerException $e) {
					echo $mail->ErrorInfo;
			return false;
		} catch (Exception $e) {
			return false;
		}
	}

	/* 
	* Consulta el total de las ventas por carrera 
	*/
	function calculosVentasCarreras($ini, $fin, $tipo, $tipo_compr, $Car_Int, $Pun_Cod, $obBD_conexion)
	{
		/* 
		* Opciones para el retorno 
		* 0 = SUBTOTAL
		* 1 = TARIFA 0
		* 2 = TARIFA 12
		* 3 = IVA
		* 4 = DESCUENTO
		* 5 = TOTAL 
		*/

		/* 
		* Control para el punto de impresion 
		*/
		if ($Pun_Cod > 0)
		{
			$Pun_Cod = " AND caja_aper.Pun_Cod = ".$Pun_Cod;
		}
		else
		{
			$Pun_Cod = "";
		}//Fin del if ($Pun_Cod > 0)

		/* 
		* Consulta del total de las ventas 
		*/
		$rs_ventas = $this->consulta(sentencias_tes(177, $this->parametros($ini.'*'.$fin.'*'.$tipo.'*'.
									$tipo_compr.'*'.$Car_Int.'*'.$Pun_Cod)), 
							$obBD_conexion->conexion);
		$row_rs_ventas = $this->registros();
		$total_rs_ventas = $this->numregistros();	

		$mover = false;
		$subtotal = 0;
		$tarifa_0 = 0;
		$tarifa_12 = 0;
		$iva = 0;
		$des = 0;
		$total = 0;
		
		if ($row_rs_ventas['Iva_Sri'] == 2)//2 es el valor en la tabla del sri
		{			
			$subtotal = $row_rs_ventas['Importe'];
			$tarifa_12 = $row_rs_ventas['Importe'];
			$iva = $row_rs_ventas['Iva'];
			$des = $row_rs_ventas['Descuento'];
			$total = $row_rs_ventas['Total'] + $iva;

			/* 
			* En caso de existir 2 registros de total de ventas se mueve el apuntador de la tabla 
			*/
			$mover = true;					
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 2)

		if ($mover == true)
		{
			/* 
			* Vuelve al fin del puntero la consulta creditos 
			*/
			$row_rs_ventas = first_last($rs_ventas, $row_rs_ventas, $total_rs_ventas);			  
		}//Fin del if ($mover == true)
		
		if ($row_rs_ventas['Iva_Sri'] == 0)//2 es el valor en la tabla del sri
		{				
			$subtotal = $subtotal + $row_rs_ventas['Importe']; //Suma los dos importes de la consulta
			$tarifa_0 = $row_rs_ventas['Importe'];
			$des = $des + $row_rs_ventas['Descuento'];
			$total = $total + $row_rs_ventas['Total'];
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 0)
	
		return $subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$des.'*'.$total;
	}//Fin del function calculos_ventas_carreras($ini, $fin)
		
}//Fin de clase Class_Log_Conexion



?>