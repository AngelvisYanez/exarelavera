<?php require_once('../../administrador/LOGICA/seguridad.php');
	  require_once('../LOGICA/logica.php');
	  require_once('../../Librerias/procedimientos/almacenados_standar.php');	  

if (isset($bt_save))
{
	/* Creacion del Objeto de conexion */
	$obBD_conexion = new Class_Mysql;
	/* Cracion del objeto mysql para las consultas */
	$obBD_con1 =  new Class_Datos; 	  
	
	$ini = $anio.'-'.$mes.'-'.'01';
	$fin = $anio.'-'.$mes.'-'.ultimoDia($mes,$anio);

	/* Identificación */
	$rs_identifica = $obBD_con1->consulta(sentencias_tes(226, $obBD_con1->parametros('')), $obBD_conexion->conexion);
	$row_rs_identifica = $obBD_con1->registros();
	$total_rs_identifica = $obBD_con1->numregistros();
	/* Cargado de etiquetas de de identificacion */
	$rs_etiquetas = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros('-1'.'*'.'1')), $obBD_conexion->conexion);
	$row_rs_etiquetas = $obBD_con1->registros();
	$total_rs_etiquetas = $obBD_con1->numregistros();
	/* Inicio de bucle de la identificación */
	do{
		$identificacion[] = $row_rs_etiquetas['Esq_Xml'];
	}while($row_rs_etiquetas = mysqli_fetch_assoc($rs_etiquetas));
	
	$xml_identifica = "<".$identificacion[0].">".$row_rs_identifica['Emp_Ruc']."</".$identificacion[0].">".
					"<".$identificacion[1].">".mb_convert_encoding(strtoupper($row_rs_identifica['Emp_Nom']), 'UTF-8', 'ISO-8859-1')."</".$identificacion[1].">".
					"<".$identificacion[2].">".mb_convert_encoding(strtoupper($row_rs_identifica['Suc_Dir']), 'UTF-8', 'ISO-8859-1')."</".$identificacion[2].">".
					"<".$identificacion[3].">".mb_convert_encoding($row_rs_identifica['Suc_Te1'], 'UTF-8', 'ISO-8859-1')."</".$identificacion[3].">".
					"<".$identificacion[4].">".mb_convert_encoding($row_rs_identifica['Suc_Fax'], 'UTF-8', 'ISO-8859-1')."</".$identificacion[4].">".
					"<".$identificacion[5].">".mb_convert_encoding($row_rs_identifica['Suc_Cor'], 'UTF-8', 'ISO-8859-1')."</".$identificacion[5].">".
					"<".$identificacion[6].">".$row_rs_identifica['Emp_Rce']."</".$identificacion[6].">".
					"<".$identificacion[7].">".$row_rs_identifica['Emp_Rep']."</".$identificacion[7].">".
					"<".$identificacion[8].">".mb_convert_encoding($row_rs_identifica['Emp_Rco'], 'UTF-8', 'ISO-8859-1')."</".$identificacion[8].">".
					"<".$identificacion[9].">".mb_convert_encoding($anio, 'UTF-8', 'ISO-8859-1')."</".$identificacion[9].">".
					"<".$identificacion[10].">".mb_convert_encoding($mes, 'UTF-8', 'ISO-8859-1')."</".$identificacion[10].">";
	
	/* C O M P R A S*/
	/* Cargado de etiquetas de nivel cero */
	$rs_etiquetas_cero = $obBD_con1->consulta(sentencias_tes(234, $obBD_con1->parametros('0'.'*'.'1'.'*'.'C')), $obBD_conexion->conexion);
	$row_rs_etiquetas_cero = $obBD_con1->registros();
	$total_rs_etiquetas_cero = $obBD_con1->numregistros();
	
	/* Cargado de los datos de las C O M P R A S detallados del anexo (Cabecera)*/
	$rs_compras = $obBD_con1->consulta(sentencias_tes(228, $obBD_con1->parametros($ini.'*'.$fin)), $obBD_conexion->conexion);
	$row_rs_compras = $obBD_con1->registros();//.'1'
	$total_rs_compras = $obBD_con1->numregistros();
	
if ($total_rs_compras > 0)
{
	/* Apertura del cuerpo */
	$xml_compras = $xml_compras."<".$row_rs_etiquetas_cero['Esq_Xml'].">";
	
	do{ //Inicio del $row_rs_compras
	unset($valores);
	unset($cod_valores);
		
			/* Cargado de etiquetas del subnivel cero */
			$rs_etiquetas_det = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros($row_rs_etiquetas_cero['Esq_Cod'].'*'.
			'1')),
										$obBD_conexion->conexion);
			$row_rs_etiquetas_det = $obBD_con1->registros();
		
			do{	
				$xml_compras	= $xml_compras."<".$row_rs_etiquetas_det['Esq_Xml'].">";
					/* Cargado de etiquetas del detalle */
					$rs_etiquetas_det_1 = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros($row_rs_etiquetas_det['Esq_Cod']
												.'*'.'1')), $obBD_conexion->conexion);
					$row_rs_etiquetas_det_1 = $obBD_con1->registros();
		
					/* Asigna en un arreglo las etiquetas de los valores del anexo */		
					do{
						$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];	
						$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];	
					}while($row_rs_etiquetas_det_1 = mysqli_fetch_assoc($rs_etiquetas_det_1));
		
					/* Consulta el porcentaje de iva actual de la tabla */
					$rs_iva = $obBD_con1->consulta(sentencias_tes(229, ''), $obBD_conexion->conexion);
					$row_rs_iva = $obBD_con1->registros();
				
					$estab = establecimiento($row_rs_compras['Cop_Num']);
		
					/* Cargado de los datos detallados del anexo (Detalle)*/
					$rs_compras_det = $obBD_con1->consulta(sentencias_tes(230, $obBD_con1->parametros($row_rs_compras['Cop_Cod'])), 
									$obBD_conexion->conexion);
					$row_rs_compras_det = $obBD_con1->registros();
			
					/* Cargado de los datos detallados del anexo (ICE)*/
					$rs_compras_ice = $obBD_con1->consulta(sentencias_tes(231, $obBD_con1->parametros($row_rs_compras['Cop_Cod'])), 
									$obBD_conexion->conexion);
					$row_rs_compras_ice = $obBD_con1->registros();
					/* Inicio de variables */
					$baseImponible = "0.00";
					$baseImpGrav = "0.00";
					$montoIva = "0.00";
					do{								
						if ($row_rs_compras_det['Iva_Sri'] == 2)//2 es el valor en la tabla del sri
						{			
							$baseImpGrav = ndecimal(number_format($row_rs_compras_det['Cop_Imp'],2));
							/* Calculo del monto del iva */
							$montoIva = number_format(($baseImpGrav * $row_rs_iva['Iva_Por'])/100,2);			
						}
						else
						{					
							$baseImponible = ndecimal(number_format($row_rs_compras_det['Cop_Imp'],2));
						}
					}while($row_rs_compras_det = mysqli_fetch_assoc($rs_compras_det));
		
					/* Cargado para saber si el monto iva es BIEN o SERVICIO */
					$rs_bien_serv = $obBD_con1->consulta(sentencias_tes(232, $obBD_con1->parametros($row_rs_compras['Cop_Cod'].'*'.'I')), 
									$obBD_conexion->conexion);
					$row_rs_bien_serv = $obBD_con1->registros();
					$total_rs_bien_serv = $obBD_con1->numregistros();
					
					/* Tipo de servicio */
					$Ren_Tip = $row_rs_bien_serv['Ren_Tip'];
					
					/* Control utilizando en caso de que no se haya retendi IVA y se desea saber si la factura 
					es BIEN O SERVICIO */
					if ($total_rs_bien_serv == 0)
					{
						/* Cargado para saber si el monto iva es BIEN o SERVICIO */
						$rs_bien_serv_renta = $obBD_con1->consulta(sentencias_tes(232, $obBD_con1->parametros($row_rs_compras['Cop_Cod'].'*'.'R')), 
										$obBD_conexion->conexion);
						$row_rs_bien_serv_renta = $obBD_con1->registros();
						
						/* Tipo de servicio */
						$Ren_Tip = $row_rs_bien_serv_renta['Ren_Tip'];						
					}					
					
					/* Pregunta si se trata de un BIEN (TODO ESTO EN CASO DE HABER GENERADO UNA RETENCION */
					if ($Ren_Tip == 'B')
					{
						/* BIENES */
						$montoIvaBienes = $montoIva;
						$porRetBienes = cod_air($row_rs_bien_serv['Ren_Sri']);//antes estaba Ren_Sri
						$valorRetBienes = ($montoIvaBienes * $row_rs_bien_serv['Ren_Por'])/100;
					
						/* SERVICIOS */
						$montoIvaServicios = "0.00";
						$porRetServicios = "0";
						$valorRetServicios = "0.00";
					}
					else
					{
						/* SERVICIOS */
						if ($Ren_Tip == 'S')
						{
							$montoIvaServicios = $montoIva;
						}
						else
						{
							$montoIvaServicios = $montoIva; //"0.00"; HAY que saber si son servicio o bien al momento de facturar la compra
						}
						$porRetServicios = cod_air($row_rs_bien_serv['Ren_Sri']);//antes estaba Ren_Sri
						$valorRetServicios = ($montoIvaServicios * $row_rs_bien_serv['Ren_Por'])/100;
						
						/* BIENES */
						$montoIvaBienes = "0.00";
						$porRetBienes = "0";
						$valorRetBienes = "0.00";									
					}
									
					$xml_compras = $xml_compras."<".$valores[0].">".$row_rs_compras['Tri_Sri']."</".$valores[0].">".
										"<".$valores[1].">"."N"."</".$valores[1].">".
										"<".$valores[2].">".$row_rs_compras['Ide_Prc']."</".$valores[2].">".
										"<".$valores[3].">".$row_rs_compras['Prs_Ced']."</".$valores[3].">".
										"<".$valores[4].">".$row_rs_compras['Tic_Sri']."</".$valores[4].">".
										"<".$valores[5].">".date("d/m/Y",strtotime($row_rs_compras['Cop_Fec']))."</".$valores[5].">".
										"<".$valores[6].">".$estab[0]."</".$valores[6].">".
										"<".$valores[7].">".$estab[1]."</".$valores[7].">".
										"<".$valores[8].">".$estab[2]."</".$valores[8].">".
										"<".$valores[9].">".date("d/m/Y",strtotime($row_rs_compras['Cop_Imf']))."</".$valores[9].">".
										"<".$valores[10].">".$row_rs_compras['Cop_Aut']."</".$valores[10].">".
										"<".$valores[11].">".date("m/Y",strtotime($row_rs_compras['Cop_Cad']))."</".$valores[11].">".
										"<".$valores[12].">".$baseImponible."</".$valores[12].">".
										"<".$valores[13].">".$baseImpGrav."</".$valores[13].">".
										"<".$valores[14].">".$row_rs_iva['Iva_Sri']."</".$valores[14].">".
										"<".$valores[15].">".$montoIva."</".$valores[15].">".
										"<".$valores[16].">".number_format($row_rs_compras_ice['Cop_Imp'],2)."</".$valores[16].">".
										"<".$valores[17].">".number_format($row_rs_compras_ice['Ice_Cod'],0)."</".$valores[17].">".
										"<".$valores[18].">".number_format($row_rs_compras_ice['Mon_Ice'],2)."</".$valores[18].">".
										"<".$valores[19].">".number_format($montoIvaBienes,2)."</".$valores[19].">".
										"<".$valores[20].">".number_format($porRetBienes,0)."</".$valores[20].">".
										"<".$valores[21].">".number_format($valorRetBienes,2)."</".$valores[21].">".
										"<".$valores[22].">".number_format($montoIvaServicios,2)."</".$valores[22].">".	
										"<".$valores[23].">".number_format($porRetServicios,0)."</".$valores[23].">".
										"<".$valores[24].">".number_format($valorRetServicios,2)."</".$valores[24].">";
										
										/* Controles para AIR */
										$rs_esquema_air = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros(
												$cod_valores[25].'*'.'1')), $obBD_conexion->conexion);
										$row_rs_esquema_air = $obBD_con1->registros();
										
										/* Controles para AIR detalle */
										$rs_esquema_air_det = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->
														parametros($row_rs_esquema_air['Esq_Cod'].'*'.'1')), $obBD_conexion->conexion);
										$row_rs_esquema_air_det = $obBD_con1->registros();
										
										unset($valores_air);									
										/* Asigna en un arreglo las etiquetas de los valores del anexo AIR */		
										do{
											$valores_air[] = $row_rs_esquema_air_det['Esq_Xml'];	
										}while($row_rs_esquema_air_det = mysqli_fetch_assoc($rs_esquema_air_det));
	
										/* Consulta de los valores AIR (solo renta)*/
										$rs_datos_air = $obBD_con1->consulta(sentencias_tes(233, $obBD_con1->
												parametros($row_rs_compras['Cop_Cod'].'*'.'R')), $obBD_conexion->conexion);
										$row_rs_datos_air = $obBD_con1->registros();
										$total_rs_datos_air = $obBD_con1->numregistros();
										
										$codRetAir = cod_air($row_rs_datos_air['Ren_Sri']);
										//$codRetAir = $row_rs_datos_air['Ren_Sri'];								
										$xml_compras = $xml_compras."<".$valores[25].">";
										
										if ($total_rs_datos_air > 0)
										{
										$xml_compras =$xml_compras."<".$row_rs_esquema_air['Esq_Xml'].">".				
													"<".$valores_air[0].">".number_format($codRetAir,0)."</".$valores_air[0].">".							
													"<".$valores_air[1].">".ndecimal(number_format($row_rs_datos_air['Ret_Bas'],2)).
															"</".$valores_air[1].">".																																																																				
													"<".$valores_air[2].">".number_format($row_rs_datos_air['Ren_Por'],2).
															"</".$valores_air[2].">".																																																															
													"<".$valores_air[3].">".number_format($row_rs_datos_air['Val_Air'],2).
															"</".$valores_air[3].">".																																																															
											 "</".$row_rs_esquema_air['Esq_Xml'].">";
										}//Fin del if ($total_rs_datos_air > 0)					
										$xml_compras = $xml_compras."</".$valores[25].">";
						if ($total_rs_datos_air > 0)
						{
						/* Continuacion de AIR */
						$estab = establecimiento($row_rs_datos_air['Ret_Num']);
						/* Controla que haya valores en las fechas */
						$fechaEmiRet1 = "";
					if ($row_rs_datos_air['Ret_Fec'] != "")
					{
							$fechaEmiRet1 = date("d/m/Y",strtotime($row_rs_datos_air['Ret_Fec']));
						
						}//Fin del if ($row_rs_datos_air['Ret_Fec'] != "")
	
						$xml_compras = $xml_compras."<".$valores[26].">".$estab[0]."</".$valores[26].">".
										"<".$valores[27].">".$estab[1]."</".$valores[27].">".
										"<".$valores[28].">".$estab[2]."</".$valores[28].">".
										"<".$valores[29].">".$row_rs_datos_air['Aut_Sri']."</".$valores[29].">".									
										"<".$valores[30].">".$fechaEmiRet1."</".
										$valores[30].">";									
					}//Fin del if ($total_rs_datos_air > 0)																																														
						/* Control XML de los modificados */
						$xml_compras = $xml_compras."<".$valores[31].">"."0"."</".$valores[31].">".
										"<".$valores[32].">"."00/00/0000"."</".$valores[32].">".
										"<".$valores[33].">"."000"."</".$valores[33].">".
										"<".$valores[34].">"."000"."</".$valores[34].">".
										"<".$valores[35].">"."0000000"."</".$valores[35].">".
										"<".$valores[36].">"."0000000000"."</".$valores[36].">".
										"<".$valores[37].">"."0.00"."</".$valores[37].">".
										"<".$valores[38].">"."0.00"."</".$valores[38].">";																		
				$xml_compras	= $xml_compras."</".$row_rs_etiquetas_det['Esq_Xml'].">";
			}while($row_rs_etiquetas_det = mysqli_fetch_assoc($rs_etiquetas_det));
			
	}while($row_rs_compras=mysqli_fetch_assoc($rs_compras));
	$xml_compras	= $xml_compras."</".$row_rs_etiquetas_cero['Esq_Xml'].">";
	
	/* FIN DE LAS C O M P R A S */
	
	/* V E N T A S */
	/* Cargado de etiquetas de nivel cero */
	$rs_etiquetas_cero = $obBD_con1->consulta(sentencias_tes(234, $obBD_con1->parametros('0'.'*'.'1'.'*'.'V')), $obBD_conexion->conexion);
	$row_rs_etiquetas_cero = $obBD_con1->registros();
	$total_rs_etiquetas_cero = $obBD_con1->numregistros();
	
		/* Cargado de etiquetas del subnivel cero */
		$rs_etiquetas_det = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros($row_rs_etiquetas_cero['Esq_Cod'].'*'.
		'1')),
									$obBD_conexion->conexion);
		$row_rs_etiquetas_det = $obBD_con1->registros();
	
		/* Apertura del cuerpo de las ventas*/
		$xml_ventas	= $xml_ventas."<".$row_rs_etiquetas_cero['Esq_Xml'].">";
	
		unset($valores_ven);
		unset($cod_valores_ven);	
	
			do{	
				$xml_ventas	= $xml_ventas."<".$row_rs_etiquetas_det['Esq_Xml'].">";
	
				/* Cargado de etiquetas del detalle */
				$rs_etiquetas_det_1 = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros($row_rs_etiquetas_det['Esq_Cod']
											.'*'.'1')), $obBD_conexion->conexion);
				$row_rs_etiquetas_det_1 = $obBD_con1->registros();
	
					/* Asigna en un arreglo las etiquetas de los valores del anexo */		
					do{
						$valores_ven[] = $row_rs_etiquetas_det_1['Esq_Xml'];	
						$cod_valores_ven[] = $row_rs_etiquetas_det_1['Esq_Cod'];	
					}while($row_rs_etiquetas_det_1 = mysqli_fetch_assoc($rs_etiquetas_det_1));
			
					/* Consulta del total de las ventas */
					$rs_ventas = $obBD_con1->consulta(sentencias_tes(235, $obBD_con1->parametros($ini.'*'.$fin)), 
										$obBD_conexion->conexion);
					$row_rs_ventas = $obBD_con1->registros();
					$total_rs_ventas = $obBD_con1->numregistros();
					
					$baseImpGrav = "0.00";				
					$montoIva = 0;
					$mover = false;
					if ($row_rs_ventas['Iva_Sri'] == 2)//2 es el valor en la tabla del sri
					{			
	//					$baseImponible = "0.00";				
						$baseImpGrav = ndecimal(number_format($row_rs_ventas['Total'],2));					
						/* Calculo del monto del iva */
						$montoIva = number_format(($baseImpGrav * $row_rs_ventas['Iva_Por'])/100,2);			
						$mover = true;					
					}
					
					if ($mover == true)
					{
						/* Vuelve al fin del puntero la consulta creditos */
						$row_rs_ventas = first_last($rs_ventas, $row_rs_ventas, $total_rs_ventas);			  
					}
					
					$baseImponible = "0.00";
					if ($row_rs_ventas['Iva_Sri'] == 0)//2 es el valor en la tabla del sri
					{				
						$baseImponible = ndecimal(number_format($row_rs_ventas['Total'],2));
						//$baseImpGrav = "0.00";
						//$montoIva = "0.00";
					}
					//$baseImponible = ndecimal(number_format($row_rs_ventas['Vet_Imp'],2));
		
					$rs_total_ventas =  $obBD_con1->consulta(sentencias_tes(239, $obBD_con1->parametros($ini.'*'.$fin.'*'.'A')), 
										$obBD_conexion->conexion);
					$row_rs_total_ventas = $obBD_con1->registros();
					
					$xml_ventas = $xml_ventas."<".$valores_ven[0].">"."04"."</".$valores_ven[0].">".
										"<".$valores_ven[1].">".$row_rs_identifica['Emp_Ruc']."</".$valores_ven[1].">".
										"<".$valores_ven[2].">"."18"."</".$valores_ven[2].">".
										"<".$valores_ven[3].">".date("d/m/Y",strtotime($fin))."</".$valores_ven[3].">".
										"<".$valores_ven[4].">".$row_rs_total_ventas['Vet_Cnt']."</".$valores_ven[4].">".
										"<".$valores_ven[5].">".date("d/m/Y",strtotime($fin))."</".$valores_ven[5].">".																		
										"<".$valores_ven[6].">".$baseImponible."</".$valores_ven[6].">".									
										"<".$valores_ven[7].">"."N"."</".$valores_ven[7].">".									
										"<".$valores_ven[8].">".$baseImpGrav."</".$valores_ven[8].">".									
										"<".$valores_ven[9].">".$row_rs_iva['Iva_Sri']."</".$valores_ven[9].">".									
										"<".$valores_ven[10].">".$montoIva."</".$valores_ven[10].">".									
										"<".$valores_ven[11].">"."0.00"."</".$valores_ven[11].">".																																													
										"<".$valores_ven[12].">"."0"."</".$valores_ven[12].">".																																																						
										"<".$valores_ven[13].">"."0.00"."</".$valores_ven[13].">".																																																																								
										"<".$valores_ven[14].">"."0.00"."</".$valores_ven[14].">".																																																																								
										"<".$valores_ven[15].">"."0"."</".$valores_ven[15].">".																																																															
										"<".$valores_ven[16].">"."0.00"."</".$valores_ven[16].">".																																																																							
										"<".$valores_ven[17].">"."0.00"."</".$valores_ven[17].">".																																																																								
										"<".$valores_ven[18].">"."0"."</".$valores_ven[18].">".																																																																																	
										"<".$valores_ven[19].">"."0.00"."</".$valores_ven[19].">".																																																																																																			
										"<".$valores_ven[20].">"."N"."</".$valores_ven[20].">".
										"<".$valores_ven[21]."></".$valores_ven[21].">";																																																																																																																														
				$xml_ventas = $xml_ventas."</".$row_rs_etiquetas_det['Esq_Xml'].">";
			}while($row_rs_etiquetas_det = mysqli_fetch_assoc($rs_etiquetas_det));
	
		$xml_ventas	= $xml_ventas."</".$row_rs_etiquetas_cero['Esq_Xml'].">";
	 
	 
	/* I M P O R T A C I O N E S */
	/* Cargado de etiquetas de nivel cero */
	$rs_etiquetas_cero = $obBD_con1->consulta(sentencias_tes(234, $obBD_con1->parametros('0'.'*'.'1'.'*'.'I')), $obBD_conexion->conexion);
	$row_rs_etiquetas_cero = $obBD_con1->registros();
	$total_rs_etiquetas_cero = $obBD_con1->numregistros();
	
	$xml_impor	= $xml_impor."<".$row_rs_etiquetas_cero['Esq_Xml']."></".$row_rs_etiquetas_cero['Esq_Xml'].">";
	 
	/* E X P O R T A C I O N E S */
	/* Cargado de etiquetas de nivel cero */
	$rs_etiquetas_cero = $obBD_con1->consulta(sentencias_tes(234, $obBD_con1->parametros('0'.'*'.'1'.'*'.'E')), $obBD_conexion->conexion);
	$row_rs_etiquetas_cero = $obBD_con1->registros();
	$total_rs_etiquetas_cero = $obBD_con1->numregistros();
	
	$xml_expor	= $xml_expor."<".$row_rs_etiquetas_cero['Esq_Xml']."></".$row_rs_etiquetas_cero['Esq_Xml'].">";
	
	/* R E C A P  */
	/* Cargado de etiquetas de nivel cero */
	$rs_etiquetas_cero = $obBD_con1->consulta(sentencias_tes(234, $obBD_con1->parametros('0'.'*'.'1'.'*'.'R')), $obBD_conexion->conexion);
	$row_rs_etiquetas_cero = $obBD_con1->registros();
	$total_rs_etiquetas_cero = $obBD_con1->numregistros();
	
	$xml_recap	= $xml_recap."<".$row_rs_etiquetas_cero['Esq_Xml']."></".$row_rs_etiquetas_cero['Esq_Xml'].">";
	
	/* F I D E I C O M I S O S  */
	/* Cargado de etiquetas de nivel cero */
	$rs_etiquetas_cero = $obBD_con1->consulta(sentencias_tes(234, $obBD_con1->parametros('0'.'*'.'1'.'*'.'F')), $obBD_conexion->conexion);
	$row_rs_etiquetas_cero = $obBD_con1->registros();
	$total_rs_etiquetas_cero = $obBD_con1->numregistros();
	
	$xml_fidei	= $xml_fidei."<".$row_rs_etiquetas_cero['Esq_Xml']."></".$row_rs_etiquetas_cero['Esq_Xml'].">";
	
	/* A N U L A D O S  VENTAS */
	/* Cargado de etiquetas de nivel cero */
	$rs_etiquetas_cero = $obBD_con1->consulta(sentencias_tes(234, $obBD_con1->parametros('0'.'*'.'1'.'*'.'A')), $obBD_conexion->conexion);
	$row_rs_etiquetas_cero = $obBD_con1->registros();
	$total_rs_etiquetas_cero = $obBD_con1->numregistros();
	
	/* Cargado de los datos de las VENTAS ANULADAS detallados del anexo (Cabecera)*/
	$rs_anulados = $obBD_con1->consulta(sentencias_tes(236, $obBD_con1->parametros($ini.'*'.$fin.'*'.'I')), $obBD_conexion->conexion);
	$row_rs_anulados = $obBD_con1->registros();
	$total_rs_anulados = $obBD_con1->numregistros();
	
	/* Apertura del cuerpo */
	$xml_anul = $xml_anul."<".$row_rs_etiquetas_cero['Esq_Xml'].">";
	
	if ($total_rs_anulados > 0)
	{
	
	do{ //Inicio del $row_rs_anulados
	unset($valores_anul);
	unset($cod_valores_anul);
		
			/* Cargado de etiquetas del subnivel cero */
			$rs_etiquetas_det = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros($row_rs_etiquetas_cero['Esq_Cod']
								.'*'.'1')), $obBD_conexion->conexion);
			$row_rs_etiquetas_det = $obBD_con1->registros();
	
			do{	//Inicio del $row_rs_etiquetas_det
				$xml_anul	= $xml_anul."<".$row_rs_etiquetas_det['Esq_Xml'].">";
					/* Cargado de etiquetas del detalle */
					$rs_etiquetas_det_1 = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros($row_rs_etiquetas_det['Esq_Cod']
												.'*'.'1')), $obBD_conexion->conexion);
					$row_rs_etiquetas_det_1 = $obBD_con1->registros();
		
					/* Asigna en un arreglo las etiquetas de los valores del anexo */		
					do{
						$valores_anul[] = $row_rs_etiquetas_det_1['Esq_Xml'];	
						$cod_valores_anul[] = $row_rs_etiquetas_det_1['Esq_Cod'];	
					}while($row_rs_etiquetas_det_1 = mysqli_fetch_assoc($rs_etiquetas_det_1));
					
					$estab = establecimiento($row_rs_anulados['Vet_Num']);
					
					$xml_anul = $xml_anul."<".$valores_anul[0].">".$row_rs_anulados['Tic_Sri']."</".$valores_anul[0].">".
										"<".$valores_anul[1].">".$estab[0]."</".$valores_anul[1].">".
										"<".$valores_anul[2].">".$estab[1]."</".$valores_anul[2].">".
										"<".$valores_anul[3].">".$estab[2]."</".$valores_anul[3].">".
										"<".$valores_anul[4].">".$estab[2]."</".$valores_anul[4].">".
										"<".$valores_anul[5].">".$row_rs_anulados['Aut_Sri']."</".$valores_anul[5].">".
										"<".$valores_anul[6].">".date("d/m/Y",strtotime($row_rs_anulados['Caj_Fec']))
												."</".$valores_anul[6].">";
						
										
				$xml_anul	= $xml_anul."</".$row_rs_etiquetas_det['Esq_Xml'].">";
			}while($row_rs_etiquetas_det = mysqli_fetch_assoc($rs_etiquetas_det));
			
	}while($row_rs_anulados = mysqli_fetch_assoc($rs_anulados));
	}//Fin del if ($total_rs_anulados > 0) de ventas
	
	/* A N U L A D O S  RENTENCIONES */
	/* Cargado de los datos de las VENTAS ANULADAS detallados del anexo (Cabecera)*/
	$rs_anulados = $obBD_con1->consulta(sentencias_tes(237, $obBD_con1->parametros($ini.'*'.$fin.'*'.'I')), $obBD_conexion->conexion);
	$row_rs_anulados = $obBD_con1->registros();
	$total_rs_anulados = $obBD_con1->numregistros();
	
	if ($total_rs_anulados > 0)
	{
	
	do{ //Inicio del $row_rs_anulados
	unset($valores_anul);
	unset($cod_valores_anul);
		
			/* Cargado de etiquetas del subnivel cero */
			$rs_etiquetas_det = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros($row_rs_etiquetas_cero['Esq_Cod']
								.'*'.'1')), $obBD_conexion->conexion);
			$row_rs_etiquetas_det = $obBD_con1->registros();
	
			do{	//Inicio del $row_rs_etiquetas_det
				$xml_anul	= $xml_anul."<".$row_rs_etiquetas_det['Esq_Xml'].">";
					/* Cargado de etiquetas del detalle */
					$rs_etiquetas_det_1 = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros($row_rs_etiquetas_det['Esq_Cod']
												.'*'.'1')), $obBD_conexion->conexion);
					$row_rs_etiquetas_det_1 = $obBD_con1->registros();
		
					/* Asigna en un arreglo las etiquetas de los valores del anexo */		
					do{
						$valores_anul[] = $row_rs_etiquetas_det_1['Esq_Xml'];	
						$cod_valores_anul[] = $row_rs_etiquetas_det_1['Esq_Cod'];	
					}while($row_rs_etiquetas_det_1 = mysqli_fetch_assoc($rs_etiquetas_det_1));
					
					$estab = establecimiento($row_rs_anulados['Ret_Num']);
					
					$xml_anul = $xml_anul."<".$valores_anul[0].">".$row_rs_anulados['Tic_Sri']."</".$valores_anul[0].">".
										"<".$valores_anul[1].">".$estab[0]."</".$valores_anul[1].">".
										"<".$valores_anul[2].">".$estab[1]."</".$valores_anul[2].">".
										"<".$valores_anul[3].">".$estab[2]."</".$valores_anul[3].">".
										"<".$valores_anul[4].">".$estab[2]."</".$valores_anul[4].">".
										"<".$valores_anul[5].">".$row_rs_anulados['Aut_Sri']."</".$valores_anul[5].">".
										"<".$valores_anul[6].">".date("d/m/Y",strtotime($row_rs_anulados['Ret_Fec']))
												."</".$valores_anul[6].">";
				$xml_anul	= $xml_anul."</".$row_rs_etiquetas_det['Esq_Xml'].">";
			}while($row_rs_etiquetas_det = mysqli_fetch_assoc($rs_etiquetas_det));
	}while($row_rs_anulados = mysqli_fetch_assoc($rs_anulados));
	}//Fin del if ($total_rs_anulados > 0) de retenciones
	
	/* A N U L A D O S  LIQUIDACIONES DE COMPRA */
	/* Cargado de los datos de las VENTAS ANULADAS detallados del anexo (Cabecera)*/
	$rs_anulados = $obBD_con1->consulta(sentencias_tes(238, $obBD_con1->parametros($ini.'*'.$fin.'*'.'I'.'*'.'3')), $obBD_conexion->conexion);
	$row_rs_anulados = $obBD_con1->registros();
	$total_rs_anulados = $obBD_con1->numregistros();
	
	if ($total_rs_anulados > 0)
	{
	do{ //Inicio del $row_rs_anulados
	unset($valores_anul);
	unset($cod_valores_anul);
		
			/* Cargado de etiquetas del subnivel cero */
			$rs_etiquetas_det = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros($row_rs_etiquetas_cero['Esq_Cod']
								.'*'.'1')), $obBD_conexion->conexion);
			$row_rs_etiquetas_det = $obBD_con1->registros();
	
			do{	//Inicio del $row_rs_etiquetas_det
				$xml_anul	= $xml_anul."<".$row_rs_etiquetas_det['Esq_Xml'].">";
					/* Cargado de etiquetas del detalle */
					$rs_etiquetas_det_1 = $obBD_con1->consulta(sentencias_tes(227, $obBD_con1->parametros($row_rs_etiquetas_det['Esq_Cod']
												.'*'.'1')), $obBD_conexion->conexion);
					$row_rs_etiquetas_det_1 = $obBD_con1->registros();
		
					/* Asigna en un arreglo las etiquetas de los valores del anexo */		
					do{
						$valores_anul[] = $row_rs_etiquetas_det_1['Esq_Xml'];	
						$cod_valores_anul[] = $row_rs_etiquetas_det_1['Esq_Cod'];	
					}while($row_rs_etiquetas_det_1 = mysqli_fetch_assoc($rs_etiquetas_det_1));
					
					$estab = establecimiento($row_rs_anulados['Cop_Num']);
					
					$xml_anul = $xml_anul."<".$valores_anul[0].">".$row_rs_anulados['Tic_Sri']."</".$valores_anul[0].">".
										"<".$valores_anul[1].">".$estab[0]."</".$valores_anul[1].">".
										"<".$valores_anul[2].">".$estab[1]."</".$valores_anul[2].">".
										"<".$valores_anul[3].">".$estab[2]."</".$valores_anul[3].">".
										"<".$valores_anul[4].">".$estab[2]."</".$valores_anul[4].">".
										"<".$valores_anul[5].">".$row_rs_anulados['Cop_Aut']."</".$valores_anul[5].">".
										"<".$valores_anul[6].">".date("d/m/Y",strtotime($row_rs_anulados['Cop_Fec']))
												."</".$valores_anul[6].">";
				$xml_anul	= $xml_anul."</".$row_rs_etiquetas_det['Esq_Xml'].">";
			}while($row_rs_etiquetas_det = mysqli_fetch_assoc($rs_etiquetas_det));
	}while($row_rs_anulados = mysqli_fetch_assoc($rs_anulados));
	} //Fin del if ($total_rs_anulados > 0) de Liquidaciones de compra
	
	$xml_anul = $xml_anul."</".$row_rs_etiquetas_cero['Esq_Xml'].">";
	
	//header("Content-Type: text/xml");			
	/* Cabecera inicial */
	/*echo '<?xml version="1.0" encoding="UTF-8"?>*/
	//<iva xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
	//	echo $xml_identifica;
	//	echo $xml_compras;
	//	echo $xml_ventas;
	//	echo $xml_impor;
	//	echo $xml_expor;
	//	echo $xml_recap;
	//	echo $xml_fidei;
	//	echo $xml_anul;
	//	
	//	
	//echo '</iva>';
	}//Fin del if ($total_rs_compras > 0)		  
}//Fin del if (isset(bt_save))	   
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" type="text/css" media="all" href="../../Librerias/jscalendar/calendar-win2k-cold-1.css" title="win2k-cold-1" />
		<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="Titulos1">
	  <td height="10">&raquo; Declaracion de impuestos </td>
  </tr>
  <tr>
      <td height="389" align="left" valign="top">
    	  <form action="<?Php echo $_SERVER['PHP_SELF']?>" method="post" name= "form1">
		   <?Php echo mensaje_requerido(); ?>
			<FIELDSET>
			<LEGEND>
			<label class="Titulos2">Generar declaracion del:</label>
			</LEGEND>		  
    	    <table width="57%" border="0">

              <tr>
                <td width="13%" class="Etiquetas"><span class="Asterisco">* </span>A&ntilde;o:&nbsp; </td>
                <td width="87%"><select name="anio" id="anio">
                  <option></option>
                  <?Php			  
						for ($i=date("Y")-1; $i<= date("Y"); $i++)
						{
					?>
                  <option value="<?Php echo $i; ?>"><?Php echo $i; ?></option>
                  <?Php
						}
					?>
                </select></td>
              </tr>
              <tr>
                <td class="Etiquetas"><span class="Asterisco">* </span>Mes:&nbsp;</td>
                <td><select name="mes" id="mes">
					<option><option>
					<option value="01">Enero</option>
					<option value="02">Febrero</option>
					<option value="03">Marzo</option>
					<option value="04">Abril</option>
					<option value="05">Mayo</option>
					<option value="06">Junio</option>
					<option value="07">Julio</option>
					<option value="08">Agosto</option>
					<option value="09">Septiembre</option>
					<option value="10">Octubre</option>																																								
					<option value="11">Noviembre</option>																																								
					<option value="12">Diciembre</option>																																																																																															
                </select>                </td>
              </tr>
            </table>
			</FIELDSET>
			<?php
			if ($total_rs_compras > 0)
			{
			?>
            <table width="57%" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
              <tr>
                <td width="78%">&nbsp;</td>
              </tr>
              <tr>
                <td><?php	$buffer = '<?xml version="1.0" encoding="UTF-8"?>
	<iva xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
	$buffer = $buffer.$xml_identifica.$xml_compras.$xml_ventas.$xml_impor.$xml_expor.$xml_recap.$xml_fidei.$xml_anul.'</iva>';
	
//	 $file=fopen("$anio.$mes.".xml","w+"); 
	 $file=fopen("archivo.xml","w+"); 
	 fwrite ($file,$buffer); 
	 fclose($file); 
	 echo "Archivo XML generado correspondiente a <strong>".mes($mes, 1)."</strong> del <strong>".$anio."</strong>  <a href='archivo.xml'>Descargar</a>"; ?></td>
              </tr>
            </table>
			<?php
			}//Fin del if ($total_rs_compras > 0)
			else
			{	
				if (isset($bt_save))
				{		
					echo error_alerta("No hay resultados que mostrar", 1);
				}
			}
			?>
            <br>
		    <table border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="181"><input id="bt_save" name="bt_save" type="hidden" value="Grabar">
                  &nbsp;&nbsp;&nbsp;
                    <input name="bt_grabar" type="button" class="Boton_Guardar" value="Grabar" onClick="validar_anexo(document.form1)">
                </td>
              </tr>
            </table>
    	  </form>
	  </td>
  </tr>
</table>
<?Php
@mysqli_free_result($rs_identifica);
@mysqli_free_result($rs_etiquetas);
@mysqli_free_result($rs_etiquetas_cero);
@mysqli_free_result($rs_compras);
@mysqli_free_result($rs_etiquetas_det);
@mysqli_free_result($rs_iva);
@mysqli_free_result($rs_compras_det);
@mysqli_free_result($rs_compras_ice);
@mysqli_free_result($rs_bien_serv);
@mysqli_free_result($rs_bien_serv_renta);
@mysqli_free_result($rs_esquema_air);
@mysqli_free_result($rs_esquema_air_det);
@mysqli_free_result($rs_datos_air);
@mysqli_free_result($rs_ventas);
@mysqli_free_result($rs_anulados);
?>