<?php    
/**
* Descripción: Permite generar archivo XML del Anexo Tranaccional Simplificado 2013 previos requisitos del SRI
* Fecha de actualización:	2010-03-02 
* Desarrollador:	Jose Cumbicos -  Ing. Angelica Galvez
* Fecha de actualización:	2010-03-02 
* Desarrollador:	Lewis Chimarro
* Desarrollador:	Jose Cumbicos     2014-11-25
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anexo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Anx; 	
/**
* Incrementa la capacidad de espacio reservado en la memoria ram para este script 
*/
ini_set("memory_limit" , "32M") ;

if (isset($bt_save))
{
	

	$ini = $anio.'-'.$mes.'-'.'01';
	$fin = $anio.'-'.$mes.'-'.ultimoDia($mes,$anio);

	/**
	* Identificación 
	* Esta consulta nos permite obtener la informacion de la Empresa(Ruc, Nombre, etc) para llenar el encabezado del
	* archivo XML a generar
	*/
	$row_rs_identifica = $obBD_con1->getRowConsulta(226, $Ses_Emp_Cod, $obBD_conexion);
	
	
	
	/**
	* Consulta el total de puntos de impresion - Esto calcula en funcion de las ventas realizadas
	*/
	//$row_puntos = $obBD_con1->getArrayConsulta(392, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);	
	$row_puntos = $obBD_con1->getArrayConsulta(226,$Ses_Emp_Cod, $obBD_conexion);	
	
	
	
	
	
	/**
	* Cargado de etiquetas de identificacion 
	* Las etiquetas obtenidas corresponden al ANEXO TRANSACCIONAL SIMPLIFICADO 2010 parametro 2 -->[3] de la SQL 227, los cuales nos 
	* permiten hacer el armado de todo el archivo XML  
	*/
	$rs_etiquetas = $obBD_con1->getArrayConsulta(227, '-1'.'*'.'4', $obBD_conexion);
	$total_rs_etiquetas = count($rs_etiquetas);
	
	/**
	* Inicio de bucle de la identificación 
	* Asignamos las etiquetas consultadas a la variable "$identificacion[]"
	*/	
	foreach($rs_etiquetas as $row_rs_etiquetas)
	{
		$identificacion[] = $row_rs_etiquetas['Esq_Xml'];		
	}		
	
								/*********************************************************************************/
								/*         E N C A B E Z A D O   P R I N C I P A L   D E L    X M L              */ 
								/*********************************************************************************/								
	/**
	* La varible  "$xml_identifica" es la que contendra el encabezado principal del XML 
	* La variable "$row_rs_identifica[?]" posee la informacion obtenida de la base de datos
	*/
	$row_tipCompr = $obBD_con1->getArrayConsulta(862,'01*04', $obBD_conexion);	
	/* Consulta el total de ventas tipo Factura */
	$row_ventas = $obBD_con1->getRowConsulta(391, $ini.'*'.$fin.'*'.$Ses_Emp_Cod.'*'.$row_tipCompr[0]['Tic_Sri'], $obBD_conexion);
	/* Consulta el total de ventas tipo Notas de Credito */
	$row_ventasNotCredito = $obBD_con1->getRowConsulta(391, $ini.'*'.$fin.'*'.$Ses_Emp_Cod.'*'.$row_tipCompr[1]['Tic_Sri'], $obBD_conexion);
	
	if ($row_ventas['Total']==0)
	{
		$tot_ventas ='00';
	}else{
		$tot_ventas = formato_numero($row_ventas['Total'] - $row_ventasNotCredito['Total'],2,1);
	}
		
	switch (count($row_puntos))
	{
		case 1:	$puntos = "00".count($row_puntos);
		break;
		case 2: $puntos = "0".count($row_puntos);
		break;
		case 3: $puntos = count($row_puntos);
		break;
			
	}	
	
	$xml_identifica = "<".$identificacion[4].">R</".$identificacion[4].">".
					"<".$identificacion[0].">".$row_rs_identifica['Emp_Ruc']."</".$identificacion[0].">".
					"<".$identificacion[1].">".utf8_encode(strtoupper($row_rs_identifica['Emp_Nom']))."</".$identificacion[1].">".
					"<".$identificacion[2].">".utf8_encode($anio)."</".$identificacion[2].">".
					"<".$identificacion[3].">".utf8_encode($mes)."</".$identificacion[3].">".
					"<".$identificacion[7].">".$puntos."</".$identificacion[7].">".
					"<".$identificacion[5].">".$tot_ventas."</".$identificacion[5].">". //total ventas
					"<".$identificacion[6].">IVA</".$identificacion[6].">";
	/**
	* Fin de la Etiqueta del Encabezado
	*/	
												
											/*=========================================*/
											/*    C O M P R A S   D E L    X M L       */
											/*=========================================*/
if(isset($_POST['chk_Compras']))
{	
	/*----------------------------------------*/
	/*    C O M P R A S   D E L    X M L      */
	/*----------------------------------------*/
	
	/**
	* Cargado de etiquetas de nivel cero 
	*/
	$row_rs_etiquetas_cero = $obBD_con1->getRowConsulta(234, '0'.'*'.'4'.'*'.'C', $obBD_conexion);
	
	/**
	* Cargado de los datos de las C O M P R A S detallados del anexo (Cabecera)
	*/	
	$rs_compras = $obBD_con1->getArrayConsulta(260, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion); //$ini.'*'.$fin.
	/**
	* Apertura del cuerpo 
	*/
	$xml_compras = $xml_compras."<".$row_rs_etiquetas_cero['Esq_Xml'].">";

	foreach($rs_compras as $row_rs_compras)
	{
		unset($valores);
		unset($cod_valores);
		
		/**
		* Cargado del primer SubNivel de compras "Detalles" 
		*/
		$rs_etiquetas_det = $obBD_con1->getArrayConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'].'*'.'4', $obBD_conexion);
	
		foreach($rs_etiquetas_det as $row_rs_etiquetas_det)
		{	
			$xml_compras = $xml_compras."<".$row_rs_etiquetas_det['Esq_Xml'].">";	
			/**
			* Cargado de las etiquetas correspondiente al "Detalle" 
			*/
			$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(386, $row_rs_etiquetas_det['Esq_Cod'].'*'.'4', $obBD_conexion);
		
			/**
			* Asigna en un arreglo las etiquetas de los valores del anexo 
			*/
			foreach($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1)
			{				
				if($row_rs_etiquetas_det_1['Esq_Xml']=="air")
				{
					$CodAir=$row_rs_etiquetas_det_1['Esq_Cod'];				
				}				
				$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];	
				$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];						
			}//Fin del $row_rs_etiquetas_det_1
					
			$autorizacion = $row_rs_compras['Cop_Aut'];			
				
			/**
			* Consulta los porcentaje de iva
			*/
			$row_rs_iva = $obBD_con1->getRowConsulta(229, '', $obBD_conexion);				
			$estab = $obBD_con1->establecimiento($row_rs_compras['Cop_Num']);
											
			/**
			* Cargado de los datos detallados del anexo (Detalle)
			*/
			$rs_compras_det = $obBD_con1->getArrayConsulta(230, $row_rs_compras['Cop_Cod'], $obBD_conexion);

			/**
			* Consulta de facturas que no tienen retencion codigo 332
			*/
			$row_rs_compras_sin_retenc = $obBD_con1->getRowConsulta(855, $row_rs_compras['Cop_Cod'], $obBD_conexion);
			$total_rs_compras_sin_retenc = count($row_rs_compras_sin_retenc);
			$cont=0;
			
			if($total_rs_compras_sin_retenc>0)
			{
				$cont++;
				$codRetAir[$cont] = "332";
				$baseImpAir[$cont] = $row_rs_compras_sin_retenc['Cop_Imp'];
				$porcentajeAir[$cont] = 0;
				$valRetAir[$cont] = 0;
				
			}
			
			/**
			* Cargado de los datos detallados del anexo (ICE)
			*/
			$row_rs_compras_ice = $obBD_con1->getRowConsulta(231, $row_rs_compras['Cop_Cod'], $obBD_conexion);
			/**
			* Inicio de variables 
			*/
			$baseImponible = "0.00";
			$baseImpGrav = "0.00";
			$montoIva = "0.00";
			foreach($rs_compras_det as $row_rs_compras_det)
			{								
				if ($row_rs_compras_det['Iva_Sri'] == 2)//2 es el valor en la tabla del sri
				{			
					$baseImpGrav = formato_numero($row_rs_compras_det['Cop_Imp'],2,1);
					
					$montoIva = round(($baseImpGrav * $row_rs_iva['Iva_Por'])/100,2);	
				}
				else
				{					
					$baseImponible = formato_numero($row_rs_compras_det['Cop_Imp'],2,1);
				}
			}//Fin del $row_rs_compras_det
					
			$valorRetBienes="0.00";
			$valorRetServicios="0.00";
			$valorRetServicios100="0.00";
						
			/**
			* Cargado para saber si el monto iva es BIEN o SERVICIO 
			*/
			$rs_bien_serv = $obBD_con1->getArrayConsulta(232, $row_rs_compras['Cop_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
			$total_rs_bien_serv = count($rs_bien_serv);

			if($total_rs_bien_serv==0)
			{
				$valorRetBienes="0.00";
				$valorRetServicios="0.00";
			}
			else
			{
				foreach($rs_bien_serv as $row_rs_bien_serv)
				{       
					if(($row_rs_bien_serv['Adq_Cod']==1) || ($row_rs_bien_serv['Adq_Cod']==2) || ($row_rs_bien_serv['Adq_Cod']==13) && $row_rs_bien_serv['Ren_Por']==30)
					{
						$valorRetBienes= $valorRetBienes + round(($row_rs_bien_serv['Ret_Bas']*$row_rs_bien_serv['Ren_Por'])/100,2);
					}
					else
					{
						if ($row_rs_bien_serv['Adq_Cod']==3 || $row_rs_bien_serv['Adq_Cod']==13 && $row_rs_bien_serv['Ren_Por']==70)
						{
							$valorRetServicios = round(($row_rs_bien_serv['Ret_Bas']*$row_rs_bien_serv['Ren_Por'])/100,2);
						}
					}					
				}//Fin del $row_rs_bien_serv						
			}//Fin del if($total_rs_bien_serv==0)
			/**
			* Cargado para obtener si el porcentaje de retencion del iav es 100%
			*/
			$row_rs_iva_total = $obBD_con1->getArrayConsulta(854, $row_rs_compras['Cop_Cod'], $obBD_conexion);
			$total_rs_iva_total = count($row_rs_iva_total);
			
			if($total_rs_iva_total==1)
			{
				$valorRetServicios100=round(($row_rs_iva_total[0]['Ret_Bas']*$row_rs_iva_total[0]['Ren_Por'])/100,2);				
			}
			else
			{
				$valorRetServicios100="0.00";
			}
										
			/**
			* Cargado del primer SubNivel del  "air" para las Compras  
			*/
			$row_rs_etiquetas_air = $obBD_con1->getRowConsulta(227, $CodAir.'*'.'4',$obBD_conexion);
		
			/**
			* Cargado de las etiquetas correspondiente al "air" para las Compras   
			*/
			$rs_etiquetas_air_1 = $obBD_con1->getArrayConsulta(386, $row_rs_etiquetas_air['Esq_Cod'].'*'.'4', $obBD_conexion);
			/**
			* Asigna en un arreglo las etiquetas de los valores del air 
			*/
			unset($valores_air);
			foreach($rs_etiquetas_air_1 as $row_rs_etiquetas_air_1)
			{			
				$det_air[] = $row_rs_etiquetas_air_1['Esq_Xml'];	
				$cod_air[] = $row_rs_etiquetas_air_1['Esq_Cod'];						
			}//Fin del $row_rs_etiquetas_air_1
			/**
			* Consulta de los valores AIR (solo renta)
			*/
			$row_rs_datos_air = $obBD_con1->getArrayConsulta(233, $row_rs_compras['Cop_Cod'].'*'.'R', $obBD_conexion);
			$total_rs_datos_air = count($row_rs_datos_air);
			
			/**
			* Fecha de registro de la factura
			*/
			if ($total_rs_datos_air > 0)
			{
				/**
				* Fecha de la retencion
				*/
				$Fecha_Reg = $row_rs_datos_air[0]['Ret_Fec'];
				/**
				* Control para asignar la fecha de compra a la retencion
				*/
				if ($Fecha_Reg < $row_rs_compras['Cop_Fec'])
				{
					$Fecha_Reg = $row_rs_compras['Cop_Fec'];
				}
			}
			else
			{
				/**
				* Fecha de la factura
				*/
				$Fecha_Reg = $row_rs_compras['Cop_Fec'];
			}//Fin del else if ($total_rs_datos_air > 0)
								
			$xml_compras = $xml_compras.															/* ETIQUETAS COMPRAS DEL XML*/
			"<".$valores[0].">".$row_rs_compras['Tri_Sri']."</".$valores[0].">".						// codSustento		
			"<".$valores[1].">".$row_rs_compras['Ide_Prc']."</".$valores[1].">". 						// tpIdProv
			"<".$valores[2].">".$row_rs_compras['Prs_Ced']."</".$valores[2].">".   						// idProv
			"<".$valores[3].">".str_pad($row_rs_compras['Tic_Sri'], 2, "0", STR_PAD_LEFT)."</".$valores[3].">".	// tipoComprobante
			"<".$valores[4].">".date("d/m/Y",strtotime($Fecha_Reg))."</".$valores[4].">". 				// fechaRegistro
			"<".$valores[5].">".$estab[0]."</".$valores[5].">".			    							// establecimiento
			"<".$valores[6].">".$estab[1]."</".$valores[6].">".  		    							// puntoEmision
			"<".$valores[7].">".str_pad($estab[2], 9, "0", STR_PAD_LEFT)."</".$valores[7].">".      	 // secuencial
			"<".$valores[8].">".date("d/m/Y",strtotime($row_rs_compras['Cop_Fec']))."</".$valores[8].">".// fechaEmision
			"<".$valores[9].">".$autorizacion."</".$valores[9].">".										// autorizacion
			"<".$valores[10].">"."00.00"."</".$valores[10].">".											// baseNoGraIva
			"<".$valores[11].">".$baseImponible."</".$valores[11].">".									// baseImponible
			"<".$valores[12].">".$baseImpGrav."</".$valores[12].">".									// baseImpGrav
			"<".$valores[13].">".formato_numero($row_rs_compras_ice['Mon_Ice'],2,1)."</".$valores[13].">".				// montoIce
			"<".$valores[14].">".formato_numero($montoIva,2,1)."</".$valores[14].">".									// montoIva
			"<".$valores[15].">".formato_numero($valorRetBienes,2,1)."</".$valores[15].">".								// valorRetBienes
			"<".$valores[16].">".formato_numero($valorRetServicios,2,1)."</".$valores[16].">".							// valorRetServicios
			"<".$valores[17].">".formato_numero($valorRetServicios100,2,1)."</".$valores[17].">".						// valorRetServ100									
			"<".$valores[18].">";																						// pagoExterior
				$rs_pagoExterior = $obBD_con1->getArrayConsulta(386, $cod_valores[18].'*'.'4', $obBD_conexion);
				foreach($rs_pagoExterior as $rowExt)
				{
					$pagExt_Eti[]=$rowExt['Esq_Xml'];
					$pagExt_Cod[]=$rowExt['Esq_Cod'];	
				}
				$xml_compras = $xml_compras.
					"<".$pagExt_Eti[0].">01</".$pagExt_Eti[0].">".						//pagoLocExt
					"<".$pagExt_Eti[1].">NA</".$pagExt_Eti[1].">".						//paisEfecPago
					"<".$pagExt_Eti[2].">NA</".$pagExt_Eti[2].">".						//aplicConvDobTrib
					"<".$pagExt_Eti[3].">NA</".$pagExt_Eti[3].">";						//pagExtSujRetNorLeg																	
			$xml_compras = $xml_compras."</".$valores[18].">";						
			
			if($row_rs_compras['Tpc_Cod']!="1")			
			{
			$xml_compras = $xml_compras."<".$valores[19].">";														// formasDePago
				$rs_ForPagoCom = $obBD_con1->getArrayConsulta(386, $cod_valores[19].'*'.'4', $obBD_conexion);
				$rs_ForPagoComSri = $obBD_con1->getRowConsulta(856, $row_rs_compras['Tpc_Cod'], $obBD_conexion);
				foreach($rs_ForPagoCom as $rowFpc)
				{
					$Fpc_Eti[]=$rowFpc['Esq_Xml'];
					$Fpc_Cod[]=$rowFpc['Esq_Cod'];	
				}	
				$xml_compras = $xml_compras."<".$Fpc_Eti[0].">".$rs_ForPagoComSri['Tpc_Sri']."</".$Fpc_Eti[0].">"; //formaPago					
			$xml_compras=$xml_compras."</".$valores[19].">";
			}
			
			if($row_rs_compras['Tic_Sri']!='4') /* control para evitar lasetiquetas d retencion a las notas de credito */
			{
				$xml_compras=$xml_compras."<".$valores[20].">"; // <air>
								
				// AIR
				/**
				* Control para factura que sustentan credito tributario 
				*/
				if (/*$row_rs_compras['Tri_Sri'] != "00" &&*/ $total_rs_datos_air > 0)
				{			
					foreach($row_rs_datos_air as $row)
					{
						$cont++;
						//$codRetAir[$cont] = $obBD_con1->cod_air($row['Ren_Sri']);
						$codRetAir[$cont] = $row['Ren_Sri'];
						$baseImpAir[$cont] = $row['Ret_Bas'];
						$porcentajeAir[$cont] = $row['Ren_Por'];
						$valRetAir[$cont] = $row['Val_Air'];
						
					}//Fin del $row_rs_datos_air 				
				}						
				/**
				* Detalle air
				* ETIQUETAS AIR
				*/
					for($i=1; $i<=$cont; $i++)
					{
					$xml_compras =$xml_compras."<".$row_rs_etiquetas_air['Esq_Xml'].">".
						"<".$det_air[0].">".$codRetAir[$i]."</".$det_air[0].">".							// codRetAir
						"<".$det_air[1].">".formato_numero($baseImpAir[$i],2,1)."</".$det_air[1].">".		// baseImpAir
						"<".$det_air[2].">".formato_numero($porcentajeAir[$i],2,1)."</".$det_air[2].">".	// porcentajeAir
						"<".$det_air[3].">".formato_numero($valRetAir[$i],2,1)."</".$det_air[3].">".        // valRetAir
						"</".$row_rs_etiquetas_air['Esq_Xml'].">";
					}// fin del for
				
				$xml_compras = $xml_compras."</".$valores[20].">"; //</air>
			}
				/**
				* Continuacion de AIR 
				* Control para factura que sustentan credito tributario 
				*/				
				if ($row_rs_compras['Tri_Sri'] != "00" && $total_rs_datos_air > 0)
				{					
						unset ($estabRet); unset ($ptoEmiRet); unset ($secRet); unset ($autRet); unset ($fechaEmiRet);
						foreach($row_rs_datos_air as $row)
						{							
						 	/**
							* Controla que haya valores en las fechas 
							*/
							$fecha = "";
							if ($row['Ret_Fec'] != "")
							{
								$fecha = date("d/m/Y",strtotime($row['Ret_Fec']));						
							}//Fin del if ($row_rs_datos_air['Ret_Fec'] != "")
							
							/**
							* Asignacion del establecimiento
							*/
							$estab = $obBD_con1->establecimiento($row['Ret_Num']);
							
							$estabRet[] = $estab[0];	
							$ptoEmiRet[] = $estab[1];	
							$secRet[] = $estab[2];
							$autRet[] = $row['Aut_Sri'];	
							$fechaEmiRet[] = $fecha;			
						}//Fin del $row_rs_datos_air
				}
				else // Control para factura que NO sustentan credito tributario 
				{			
					unset ($estabRet); unset ($ptoEmiRet); unset ($secRet); unset ($autRet); unset ($fechaEmiRet);                                          
					foreach($row_rs_datos_air as $row)
					{													 	
						/**
						* asignacion del establecimiento
						*/
						$estabRet[] = "000";	
						$ptoEmiRet[] = "000";	
						$secRet[] = "0";
						$autRet[] = "000";	
						$fechaEmiRet[] = "00/00/0000";			
					}//Fin del $row_rs_datos_air
				}//Fin del if ($row_rs_compras['Tri_Sri'] != 1)		
				
				//if($total_rs_datos_air == 1 )	
//				{
//				  	$estabRet[0] = "000";
//					$ptoEmiRet[0] = "000";	
//					$secRet[0] = "0";
//					$autRet[0] = "000";
//					$fechaEmiRet[0] = "00/00/0000";
//					
//					//$estabRet[1] = "000";
//					//$ptoEmiRet[1] = "000";	
//					//$secRet[1] = "0";
//					//$autRet[1] = "000";
//					//$fechaEmiRet[1] = "00/00/0000";
//				}
						
				if (count($row_rs_datos_air)!=0){
				$xml_compras = $xml_compras."<".$valores[21].">".$estabRet[0]."</".$valores[21].">".	// estabRetencion1
					"<".$valores[22].">".$ptoEmiRet[0]."</".$valores[22].">".							// ptoEmiRetencion1			
					"<".$valores[23].">".str_pad($secRet[0], 9, "0", STR_PAD_LEFT)."</".$valores[23].">". // secRetencion1 str_pad-> completa con ceros hasta 9
					"<".$valores[24].">".$autRet[0]."</".$valores[24].">".	    						// autRetencion1
					"<".$valores[25].">".$fechaEmiRet[0]."</".$valores[25].">";    						// fechaEmiRet1
				/*	"<".$valores[24].">".$estabRet[1]."</".$valores[24].">".							// estabRetencion2
					"<".$valores[25].">".$ptoEmiRet[1]."</".$valores[25].">".							// ptoEmiRetencion2
					"<".$valores[26].">".$secRet[1]."</".$valores[26].">".								// secRetencion2
					"<".$valores[27].">".$autRet[1]."</".$valores[27].">".								// autRetencion2
					"<".$valores[28].">".$fechaEmiRet[1]."</".$valores[28].">";    						// fechaEmiRet2*/
				}
				if($row_rs_compras['Tic_Sri']=='4') /* solo para notas de credito */
				{
				$tipDocMod=$row_rs_compras['Cop_Ntd'];	
				$arrDatNum=explode('-',$row_rs_compras['Cop_Nns']);
				$xml_compras = $xml_compras."<".$valores[31].">".str_pad($tipDocMod, 2, "0", STR_PAD_LEFT)."</".$valores[31].">".// docModificado
					"<".$valores[32].">".$arrDatNum[0]."</".$valores[32].">".							// estabModificado
					"<".$valores[33].">".$arrDatNum[1]."</".$valores[33].">".							// ptoEmiModificado
					"<".$valores[34].">".str_pad($arrDatNum[2], 9, "0", STR_PAD_LEFT)."</".$valores[34].">".// secModificado
					"<".$valores[35].">".$row_rs_compras['Cop_Nna']."</".$valores[35].">";				// autModificado        																			
				}
		$xml_compras = $xml_compras."</".$row_rs_etiquetas_det['Esq_Xml'].">";	
		
		}//Fin del $row_rs_etiquetas_det
		unset($valores);	
	}//Fin de compras
	    $xml_compras = $xml_compras."</".$row_rs_etiquetas_cero['Esq_Xml'].">";
	
}// FIN IF if(isset($chk_Compras))		
	
	
													/*========================================*/
													/*    V E N T A S    D E L     X M L      */
													/*========================================*/
	
if(isset($_POST['chk_Ventas']))
{	
	/**
	* Cargado de etiquetas de nivel cero 
	*/
	$row_rs_etiquetas_cero = $obBD_con1->getRowConsulta(234, '0'.'*'.'4'.'*'.'V', $obBD_conexion);
	
	/**
	* Cargado del primer SubNivel de ventas "Detalles" 
	*/
	$row_rs_etiquetas_det = $obBD_con1->getRowConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'].'*'.'4',$obBD_conexion);
		
	/**
	* Cargado de las etiquetas correspondiente al "Detalle" 
	*/
	$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(386, $row_rs_etiquetas_det['Esq_Cod'].'*'.'4', $obBD_conexion);
	
	/**
	* Consultando los CLIENTES q se emitio Ventas en un determinado MES para el Anexo Transaccional 
	*/
	$rs_ventas = $obBD_con1->getArrayConsulta(387, "A".'*'.$ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);		
					
	/**
	* Asigna en un arreglo las etiquetas de los valores del anexo 
	*/	
	foreach($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1)
	{
		$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];	
		$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];	
	}//Fin del $row_rs_etiquetas_det_1
	
	 /**
	 * Cabecera de la venta 
	 */
	 $xml_ventas = $xml_ventas."<".$row_rs_etiquetas_cero['Esq_Xml'].">";
	 
	 foreach($rs_ventas as $row_rs_ventas)
	 { 
	 	 $NumFact=0;
		 /**
		 * Consulta la cabecera de Facturas 
		 */
		$row_rs_cabecera = $obBD_con1->getArrayConsulta(388, $row_rs_ventas['Cli_Cod'].'*'.$ini.'*'.$fin, $obBD_conexion);
		foreach($row_rs_cabecera as $cabDatos)
		{ 
		  $row_rs_Comprobantes = $obBD_con1->getArrayConsulta(861, $row_rs_ventas['Cli_Cod'].'*'.$ini.'*'.$fin.'*'.$cabDatos['Tic_Cod'], $obBD_conexion);
		 /**
		 * Variable q contiene el numero de Facturas encontradas, ese valor va directo al XML
		 */
		 $NumFact=count($row_rs_Comprobantes); 		 
		 /**
		 * Consulta la suma total de las facturas
		 */
		 $rs_detalle = $obBD_con1->getArrayConsulta(389, $ini.'*'.$fin.'*'.$row_rs_ventas['Cli_Cod'].'*'.$cabDatos['Tic_Cod'], $obBD_conexion);
		 $Total_rs_detalle = count($rs_detalle);
		 /**
		 * Variable q contendran las sumas de los facturas de ventas
		 */
		 $valor_baseImpGrav="0";
		 $valor_montoIva="0";
		 $valor_baseImponible="0";
		 $valorRenta=0;		
		 $valorIva=0;
		 /**
		 * Consultando todas las ventas segun la cedula y fecha y empresa del cliente
		 */
		 $rs_TodasVentas = $obBD_con1->getArrayConsulta(857, $ini.'*'.$fin.'*'.$Ses_Emp_Cod.'*'.$row_rs_ventas['Prs_Ced'].'*'.$cabDatos['Tic_Cod'], $obBD_conexion);	 
		 //echo count($row_datos['Vet_Cod']);
		 foreach($rs_TodasVentas as $row_datos)
		 {
		  	 /*Consultamos los Items detalles de ventas*/
			 $rs_DetalleVentas = $obBD_con1->getArrayConsulta(858, $row_datos['Vet_Cod'], $obBD_conexion);	 			
			 foreach($rs_DetalleVentas as $row_Detdatos)
		 	 {
			 	  if ($row_Detdatos['Ren_Cod']!='')
				  {
					  /*Consultamos el porcentaje de la renta*/
			 		  $rs_rentaPor = $obBD_con1->getRowConsulta(859, $row_Detdatos['Ren_Cod'], $obBD_conexion);	 
					  $valorRenta=$valorRenta + (($row_Detdatos['Tot_Imp']*$rs_rentaPor['Ren_Por'])/100);
				  }
				  if ($row_Detdatos['Ren_Iva']!='')
				  {
					  /*Consultamos el porcentaje de la iva*/
			 		  $rs_ivaPor = $obBD_con1->getRowConsulta(859, $row_Detdatos['Ren_Iva'], $obBD_conexion);
					  $valorIva=$valorIva + (($row_Detdatos['Tot_Iva']*$rs_ivaPor['Ren_Por'])/100);
				  }				  
			 }
		 }
		 		 		 
		 if ($Total_rs_detalle <= 2 ) // inicio IF $Total_rs_detalle
		 {
			 foreach($rs_detalle as $row_rs_detalle)
			 { 
					if($row_rs_detalle['Iva_Por'] > 0) // IF para sumar los Totales de las facturas ya sean con Iva mayor de "0" o menor de "0"
					{ 
						$valor_baseImpGrav= round($row_rs_detalle['Total'],2);//Contiene la suma d Totales de facturas con iva > 0
						$valor_montoIva= round($row_rs_detalle['Iva'],2);//Contiene la suma d todos los montos de Iva d cada factura
					}
					else
					{ 
						$valor_baseImponible = round($row_rs_detalle['Total'],2);//Contiene la suma d Totales d facturas con iva < 0						
					}
					
			  }//Fin del $row_rs_detalle
	 	  }
		  else
		  {
		  	echo error_alerta("<< Alerta >> <br>Descripción: </br>
        No se ha podido generar el XML Anexo Transaccional, se ha detectado que existen varios registros de un cliente aplicando distintos porcentajes de Iva, <br>provocando que los datos del <strong>XML-Ventas</strong> en la etiqueta <strong>montoIva</strong> esten incorrectos...!", 2);
		   	exit();	
	 	  }	// fin IF $Total_rs_detalle
	 	
		 /**
		 * Control para asignar 9999999999, cuando la cedula sea cero
		 */
		 if ($row_rs_ventas['Prs_Ced']=="0")
		 {
			 $cedula = "9999999999999";
		 }
		 else
		 {
			 $cedula = $row_rs_ventas['Prs_Ced'];
		 }
	 	 /*control para poner tipo de comprobante en 18 si la venta es d tipo Factura*/
		 if($cabDatos['Tic_Sri']=='01')
		 { $tipDocVen='18';}
		 /*control para poner tipo de comprobante si la venta es d tipo Factura es Nota Credito*/
		 if($cabDatos['Tic_Sri']=='04')
		 { $tipDocVen=$cabDatos['Tic_Sri'];}
                 /* asignamos el tipo de identificacion del cliente */
		 if($row_rs_ventas['Ide_Prv']!='4' && $row_rs_ventas['Ide_Prv']!='5' && $row_rs_ventas['Ide_Prv']!='7')
		 {
		  	$tipIdeCliente='6';
		 }else{
		 	$tipIdeCliente=$row_rs_ventas['Ide_Prv'];
		 }		 
         
		 $xml_ventas = $xml_ventas."<".$row_rs_etiquetas_det['Esq_Xml'].">";			
			$xml_ventas = $xml_ventas.									     				   /* ETIQUETAS VENTAS DEL XML*/	
			"<".$valores[0].">".str_pad($tipIdeCliente, 2, "0", STR_PAD_LEFT)."</".$valores[0].">".	// tpIdCliente		
			"<".$valores[1].">".$cedula."</".$valores[1].">".  						   		   // idCliente
			"<".$valores[2].">".str_pad($tipDocVen, 2, "0", STR_PAD_LEFT)."</".$valores[2].">".// tipoComprobante $row_rs_cabecera['Tic_Sri']
			"<".$valores[3].">".$NumFact."</".$valores[3].">".								   // numeroComprobantes
			"<".$valores[4].">"."0.00"."</".$valores[4].">".      		 					  // baseNoGraIva - valor en "0" UTSAM no le pueden retenerle iva
			"<".$valores[5].">".formato_numero($valor_baseImponible,2,1)."</".$valores[5].">". // baseImponible
			"<".$valores[6].">".formato_numero($valor_baseImpGrav,2,1)."</".$valores[6].">".   // baseImpGrav
			"<".$valores[7].">".formato_numero($valor_montoIva,2,1)."</".$valores[7].">".      // montoIva
			"<".$valores[8].">".formato_numero($valorIva,2,1)."</".$valores[8].">".      	   // valorRetIva 
			"<".$valores[9].">".formato_numero($valorRenta,2,1)."</".$valores[9].">";		   // valorRetRenta 
		
		 /**
		 * Cierre del detalle de la venta 
		 */
		 $xml_ventas = $xml_ventas."</".$row_rs_etiquetas_det['Esq_Xml'].">";	
		}
	  }//Fin del $row_rs_ventas
	  unset($valores); // Vacio el Arreglo $valores[]
	 /**
	 * Cierre de la cabecera de la venta 
	 */
	 $xml_ventas = $xml_ventas."</".$row_rs_etiquetas_cero['Esq_Xml'].">";
	
										/*=========================================================================*/
										/*    V E N T A S    E S T A B L E C I M I E N T O    D E L     X M L      */
										/*=========================================================================*/
	
	/**
	* Cargado de etiquetas de nivel cero 
	*/
	$row_rs_etiquetas_cero = $obBD_con1->getRowConsulta(234, '0'.'*'.'4'.'*'.'S', $obBD_conexion);
	
	/**
	* Cargado del primer SubNivel de ventas "Detalles" 
	*/
	$row_rs_etiquetas_det = $obBD_con1->getRowConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'].'*'.'4',$obBD_conexion);
		
	/**
	* Cargado de las etiquetas correspondiente al "Detalle" 
	*/
	$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(386, $row_rs_etiquetas_det['Esq_Cod'].'*'.'4', $obBD_conexion);
	
	/**
	* Consultando las ventas por establecimiento (sucursal)
	*/
	$rs_establecimiento = $obBD_con1->getArrayConsulta(392, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);		
	/**
	* Asigna en un arreglo las etiquetas de los valores del anexo 
	*/	
	foreach($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1)
	{
		$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];	
		$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];	
	}//Fin del $row_rs_etiquetas_det_1
	
	 /**
	 * Cabecera de la venta 
	 */
	 $xml_ventasEst = $xml_ventasEst."<".$row_rs_etiquetas_cero['Esq_Xml'].">";
	 
	 foreach($rs_establecimiento as $row_rs_establecimiento)
	 { 
		 $xml_ventasEst = $xml_ventasEst."<".$row_rs_etiquetas_det['Esq_Xml'].">";			
			$xml_ventasEst = $xml_ventasEst.									     		   /* ETIQUETAS VENTAS ESTABLECIMIENTO DEL XML*/	
			"<".$valores[0].">".$row_rs_establecimiento['Suc_Sri']."</".$valores[0].">".	   // codEstab		
			"<".$valores[1].">".$tot_ventas."</".$valores[1].">";  	   // ventasEstab			
		
		 /**
		 * Cierre del detalle de la venta 
		 */
		 $xml_ventasEst = $xml_ventasEst."</".$row_rs_etiquetas_det['Esq_Xml'].">";	
	  
	  }//Fin del $row_rs_ventas
	  unset($valores); // Vacio el Arreglo $valores[]
	 /**
	 * Cierre de la cabecera de la venta 
	 */
	 $xml_ventasEst = $xml_ventasEst."</".$row_rs_etiquetas_cero['Esq_Xml'].">";
} //FIN IF if(isset($chk_Ventas))	 


													/*========================================*/
													/*   A N U L A D O S   D E L    X M L     */
													/*========================================*/
													
if(isset($_POST['chk_Anulados']))
{	
	/**
	* Cargado de etiquetas de nivel cero 
	*/
	$row_rs_etiquetas_cero = $obBD_con1->getRowConsulta(234, '0'.'*'.'4'.'*'.'A', $obBD_conexion);
			
	/**
	* Cargado del primer SubNivel de ventas "Detalles" 
	*/
	$row_rs_etiquetas_det = $obBD_con1->getRowConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'].'*'.'4',$obBD_conexion);
	
	/**
	* Cargado de las etiquetas correspondiente al "Detalle" 
	*/
	$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(386, $row_rs_etiquetas_det['Esq_Cod'].'*'.'4', $obBD_conexion);	
	
	
													/*************************************/											
													/*	A N U L A D O S    V E N T A S   */		
													/*************************************/		
	/**
	* Consultando los CLIENTES q se anularon una factura de Ventas en un determinado MES para el Anexo Transaccional 
	*/
	$rs_anulados = $obBD_con1->getArrayConsulta(390, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);
	unset($valores);// Vacio el Arreglo $valores[]
	unset($cod_valores); 
	 
	/**
	* Asigna en un arreglo las etiquetas de los valores del anexo 
	*/
	foreach($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1)
	{
		$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];	
		$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];						
	}//Fin del $row_rs_etiquetas_det_1	
	
	$xml_anulados = $xml_anulados."<".$row_rs_etiquetas_cero['Esq_Xml'].">";
	foreach($rs_anulados as $row_rs_anulados)
	{ 		
		$xml_anulados = $xml_anulados."<".$row_rs_etiquetas_det['Esq_Xml'].">";
					
			$xml_anulados = $xml_anulados.        /* ETIQUETAS ANULADOS DEL XML*/	
			"<".$valores[0].">".str_pad($row_rs_anulados['Tic_Sri'], 2, "0", STR_PAD_LEFT)."</".$valores[0].">".		// tipoComprobante		
			"<".$valores[1].">".$row_rs_anulados['Suc_Sri']."</".$valores[1].">".       // establecimiento
			"<".$valores[2].">".$row_rs_anulados['Pun_Sri']."</".$valores[2].">".   	// puntoEmision
			"<".$valores[3].">".str_pad($row_rs_anulados['Vet_Num'], 9, "0", STR_PAD_LEFT)."</".$valores[3].">".		// secuencialInicio
			"<".$valores[4].">".str_pad($row_rs_anulados['Vet_Num'], 9, "0", STR_PAD_LEFT)."</".$valores[4].">".     	// secuencialFin
			"<".$valores[5].">".$row_rs_anulados['Aut_Sri']."</".$valores[5].">";       // autorizacion
								 
		 $xml_anulados = $xml_anulados."</".$row_rs_etiquetas_det['Esq_Xml'].">";	
		 
	 }
	 
														 /******************************************************/
														 /*    A N U L A D O S     R E T E N C I O N E S       */
														 /******************************************************/
	 
	/**
	* Cargado de los datos de las VENTAS ANULADAS detallados del anexo (Cabecera)
	*/
	$rs_anulados = $obBD_con1->getArrayConsulta(237, $ini.'*'.$fin.'*'.'I'.'*'.$Ses_Emp_Cod, $obBD_conexion);
	
	if (count($rs_anulados) > 0)
	{
	
	foreach($rs_anulados as $row_rs_anulados)
	{ 
		unset($valores);
		unset($cod_valores);
		
		/**
		* Cargado de etiquetas del subnivel cero 
		*/
		$rs_etiquetas_det = $obBD_con1->getArrayConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'].'*'.'4', $obBD_conexion);
	
		foreach($rs_etiquetas_det as $row_rs_etiquetas_det)
		{	//Inicio del $row_rs_etiquetas_det
			$xml_anulados = $xml_anulados."<".$row_rs_etiquetas_det['Esq_Xml'].">";
			/**
			* Cargado de etiquetas del detalle 
			*/
			$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(227, $row_rs_etiquetas_det['Esq_Cod'].'*'.'4', $obBD_conexion);
	
				/**
				* Asigna en un arreglo las etiquetas de los valores del anexo 
				*/		
				foreach($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1)
				{
					$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];	
					$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];	
				}//Fin del $row_rs_etiquetas_det_1 
				
				$estab = establecimiento($row_rs_anulados['Ret_Num']);
				
				$xml_anulados = $xml_anulados.															/* ETIQUETAS ANULADOS DEL XML*/	
							"<".$valores[0].">".$row_rs_anulados['Tic_Sri']."</".$valores[0].">".		// tipoComprobante	
							"<".$valores[1].">".$estab[0]."</".$valores[1].">".							// establecimiento
							"<".$valores[2].">".$estab[1]."</".$valores[2].">".							// puntoEmision
							"<".$valores[3].">".$estab[2]."</".$valores[3].">".							// secuencialInicio
							"<".$valores[4].">".$estab[2]."</".$valores[4].">".							// secuencialFin
							"<".$valores[5].">".$row_rs_anulados['Aut_Sri']."</".$valores[5].">";		// autorizacion
									
			$xml_anulados	= $xml_anulados."</".$row_rs_etiquetas_det['Esq_Xml'].">"; //Cerramos la etiqueta </detalleAnulados>
		}//Fin del $row_rs_etiquetas_det 
	}//Fin del $row_rs_anulados
}//Fin del if ($total_rs_anulados > 0) de retenciones
	 	 
												/*****************************************************************************/ 
												/*   A N U L A D O S    L I Q U I D A C I O N E S    D E    C O M P R A      */
												/*****************************************************************************/
	
	
	/**
	* Cargado de los datos de las VENTAS ANULADAS detallados del anexo (Cabecera)
	*/
	$rs_anulados = $obBD_con1->getArrayConsulta(238, $ini.'*'.$fin.'*'.'I'.'*'.'4'.'*'.$Ses_Emp_Cod, $obBD_conexion);
	
	if (count($rs_anulados) > 0)
	{
	foreach($rs_anulados as $row_rs_anulados)
	{ //Inicio del $row_rs_anulados
		unset($valores);
		unset($cod_valores);
		
		/**
		* Cargado de etiquetas del subnivel cero 
		*/
		$rs_etiquetas_det = $obBD_con1->getArrayConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'].'*'.'4', $obBD_conexion);
	
			foreach($rs_etiquetas_det as $row_rs_etiquetas_det)
			{	//Inicio del $row_rs_etiquetas_det
				$xml_anulados	= $xml_anulados."<".$row_rs_etiquetas_det['Esq_Xml'].">";
					/**
					* Cargado de etiquetas del detalle 
					*/
					$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(227, $row_rs_etiquetas_det['Esq_Cod'].'*'.'4', $obBD_conexion);
		
					/**
					* Asigna en un arreglo las etiquetas de los valores del anexo 
					*/		
					foreach($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1)
					{
						$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];	
						$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];	
					}//Fin del $row_rs_etiquetas_det_1
					
					$estab = establecimiento($row_rs_anulados['Cop_Num']);
					
					$xml_anulados = $xml_anulados.															/* ETIQUETAS ANULADOS DEL XML */
								"<".$valores[0].">".$row_rs_anulados['Tic_Sri']."</".$valores[0].">".		// tipoComprobante
								"<".$valores[1].">".$estab[0]."</".$valores[1].">".							// establecimiento
								"<".$valores[2].">".$estab[1]."</".$valores[2].">".							// puntoEmision
								"<".$valores[3].">".$estab[2]."</".$valores[3].">".							// secuencialInicio
								"<".$valores[4].">".$estab[2]."</".$valores[4].">".							// secuencialFin
								"<".$valores[5].">".$row_rs_anulados['Cop_Aut']."</".$valores[5].">";		// autorizacion								
										
										
				$xml_anulados	= $xml_anulados."</".$row_rs_etiquetas_det['Esq_Xml'].">"; //Cerramos la etiqueta </detalleAnulados>
			}//Fin del $row_rs_etiquetas_det
	}//FIn del $row_rs_anulados 
	} //Fin del if ($total_rs_anulados > 0) de Liquidaciones de compra 
	 	 
	 /**
	 * Cierre de la cabecera de la anulados </anulados>
	 */
	 $xml_anulados = $xml_anulados."</".$row_rs_etiquetas_cero['Esq_Xml'].">";
} //FIN IF if(isset($chk_Anulados))	 
}

?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>        
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>	
        <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>        
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; ANEXO TRANSACCIONAL 2014 ( tipo contribuyente especial ) </td>
  </tr>
  <tr>
      <td height="389" align="left" valign="top">
    	  <form action="<?Php echo $_SERVER['PHP_SELF']?>" method="post" name= "form1">
		   <?Php echo mensaje_requerido(); ?>
			<FIELDSET>
			<LEGEND>
			<label class="Titulos2">Generar archivo Xml:</label>
			</LEGEND>		  
    	    <table width="57%" border="0">

              <tr>
                <td width="13%" class="Etiqueta1"><span class="Asterisco">* </span>A&ntilde;o:&nbsp; </td>
                <td width="87%">
				<? $rs_periodo = $obBD_con1->getArrayConsulta(860, $Ses_Emp_Cod, $obBD_conexion); ?>
                <select name="anio" id="anio">                                   
				  <? foreach($rs_periodo as $dato){?>
                  <option <? if($anio==$dato['Pec_Fei']){ echo "selected";}?> value="<?Php echo $dato['Pec_Fei'];?>"><?Php echo $dato['Pec_Fei'];?></option>				                  <? } ?>
                </select></td>
              </tr>
              <tr>
                <td class="Etiqueta1"><span class="Asterisco">* </span>Mes:&nbsp;</td>
                <td>
				<select name="mes" id="mes">					
					<option <? if($mes=="01"){ echo "selected";}?> value="01">Enero</option>
					<option <? if($mes=="02"){ echo "selected";}?> value="02">Febrero</option>
					<option <? if($mes=="03"){ echo "selected";}?> value="03">Marzo</option>
					<option <? if($mes=="04"){ echo "selected";}?> value="04">Abril</option>
					<option <? if($mes=="05"){ echo "selected";}?> value="05">Mayo</option>
					<option <? if($mes=="06"){ echo "selected";}?> value="06">Junio</option>
					<option <? if($mes=="07"){ echo "selected";}?> value="07">Julio</option>
					<option <? if($mes=="08"){ echo "selected";}?> value="08">Agosto</option>
					<option <? if($mes=="09"){ echo "selected";}?> value="09">Septiembre</option>
					<option <? if($mes=="10"){ echo "selected";}?> value="10">Octubre</option>																																								
					<option <? if($mes=="11"){ echo "selected";}?> value="11">Noviembre</option>																																								
					<option <? if($mes=="12"){ echo "selected";}?> value="12">Diciembre</option>																																																																																															
                </select>                
				</td>
              </tr>
            </table>
			</FIELDSET>
			
			<FIELDSET>
			<LEGEND>
			<label class="Titulos2">Rubros a Generar:</label>
			</LEGEND>
			<table width="170" border="0">
				<tr>
					<td width="60" class="Etiqueta1">Compras:</td>
					<td width="100">&nbsp;<input name="chk_Compras" type="checkbox" id="chk_Compras" checked ></td>
				</tr>
				<tr>
					<td class="Etiqueta1">Ventas:</td>
					<td>&nbsp;<input type="checkbox" id="chk_Ventas" name="chk_Ventas" checked="checked" ></td>
				</tr>
				<tr>
					<td class="Etiqueta1">Anulados:</td>
					<td>&nbsp;<input name="chk_Anulados" type="checkbox" id="chk_Anulados" checked ></td>
				</tr>				
			</table>
			</FIELDSET>
			<?php 
			if ($total_rs_etiquetas > 0)/*  ------  Ojo se modifico el IF ------ */
			{
			?>
            <FIELDSET>
			<LEGEND>
			<label class="Titulos2">Descargar Xml:</label>
			</LEGEND>
            <table width="100%" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">             
              <tr>
                <td>
				<?php
				 $buffer = '<?xml version="1.0" encoding="UTF-8"?><iva>';
				 $buffer = $buffer.$xml_identifica.$xml_compras.$xml_ventas.$xml_ventasEst.$xml_anulados.'</iva>';
                                
                                 /* eliminamos el file para crear uno nuevo */
				 unlink("SRI/".$Ses_Emp_Cod."/ATS".$mes.$anio.".xml");
                                                                
				 $archivo="SRI/".$Ses_Emp_Cod."/ATS".$mes.$anio.".xml";
				 		 
				 //$file=fopen($anio.$mes.".xml","w+");
				 $file=fopen($archivo,"w+");
				 fwrite ($file,$buffer);
				 fclose($file);
				 echo "Archivo XML generado correspondiente a <strong>".mes($mes, 1)."</strong> del <strong>".$anio."</strong>  <a href=".$archivo." target='_blank'><img src='../../mascaras/model1/imagenes/32x32/download.gif' title='Descargar XML:<br>1. Clic con el botón derecho del mouse sobre este ícono.<br>2. Del menú desplegable, seleccionar la opción Guardar enlace como...<br>3. Dar la ubicación deseada y presionar el botón guardar.'></a>"; ?>
                 
                 </td>
              </tr>
            </table>
            </FIELDSET>
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
		    <table width="176" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="181"><input id="bt_save" name="bt_save" type="hidden" value="Grabar">                  
                  
		<button type="button" class="btn btn-primary start" title="Guardar" onClick= "validar_requeridos(this.form, 'anio*mes', 1)">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
     </button>                  
                </td>
              </tr>
            </table>
		    
          </form>
	  </td>
  </tr>
</table>
</div>
<?Php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>