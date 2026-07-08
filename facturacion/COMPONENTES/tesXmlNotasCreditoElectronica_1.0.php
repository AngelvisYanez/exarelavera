<?php
	/**
*	Componente para generar el xml del comprobante de venta
*	Desarrollador: Jose Cumbicos
*	Fecha creacion: 09/01/2015
*/

//if (isset($hdd_save))	
//{     
       
	$Tan_Cod=7;	
	/**
	*   Consultamos las estiquetas Raiz del XML Factura Electronica
	*/
	$rs_esquema = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*0", $obBD_conexion);				
	/**
	* Inicio de bucle de la identificación 
	* Asignamos las etiquetas consultadas a la variable "$etiqueta[]"
	*/	
	foreach($rs_esquema as $row)
	{
		$Eti_raiz[] = $row['Esq_Xml'];
		$Cod_raiz[] = $row['Esq_Cod'];					
	}    	
	$armado_xml = "<".$Eti_raiz[0].">";  //<infoTributaria>
				/**
				*  Consultamos informacion de la empresa
				*/
				$rs_infoEmpresa = $obBD_con1->getRowConsulta(1211, $Ses_Suc_Cod, $obBD_conexion);												
								
				/**
				*  Consultamos informacion del cliente
				*/
				$rs_infoCliente = $obBD_con1->getRowConsulta(1212, $Vet_Cod, $obBD_conexion);
				/**
				*   consultamos las etiquetas Raiz del XML
				*/
				$rs_infoTributaria = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_raiz[0], $obBD_conexion);				
				/* Calculo del Digito verificador de la claveAcceso*/
				$ceroDoc="";
				for($i=strlen($rs_infoCliente['Vet_Num']); $i<=9-1; $i++)
				{
					$ceroDoc=$ceroDoc."0";
				}			
				$TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
				$TipoEmisionCE=$rs_infoEmpresa['Cof_Fte'];
				/*Control para generar la clave de acceso de tipo  1=Normal  2=Indisponibilidad de Sistema WebService SRI*/
				if($rs_infoEmpresa['Cof_Fte']=='1')
				{	
					$cadena=str_replace("/","",$rs_infoCliente['fecha'])."04".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$rs_infoCliente['Vet_Num']."12345678".$rs_infoEmpresa['Cof_Fte'];	
				}else{
					/*preguntamos si el txt aun posee numeros para usar*/
					if(count(file($Ses_Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']))!=0)
					{	
						$file = file($Ses_Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']);
						/*clave de acceso de tipo emision INDISPONIBILIDAD DEL SISTEMA*/
						$cadena=str_replace("/","",$rs_infoCliente['fecha'])."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].substr($file[0], 14, 23).$rs_infoEmpresa['Cof_Fte'];
					}else{					
						/*clave de acceso de tipo emision NORMAL*/
						$cadena=str_replace("/","",$rs_infoCliente['fecha'])."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$rs_infoCliente['Vet_Num']."12345678".$rs_infoEmpresa['Cof_Fte'];
							    				
						$TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
						$TipoEmisionCE="1";												
					}
				}													
				$factor = 2;
				$suma = 0;
				for($i = strlen($cadena) - 1; $i >= 0; $i--) {
					$suma += $factor * $cadena[$i];
					$factor = $factor % 7 == 0 ? 2 : $factor + 1;
				}
				$dv = 11 - $suma % 11;				
				$dv = $dv == 11 ? 0 : ($dv == 10 ? "1" : $dv);
				$claveAcceso=$cadena.$dv;				
								
				unset ($row);
				foreach($rs_infoTributaria as $row)
				{
					$Eti_infoTri[] = $row['Esq_Xml'];
					$Cod_infoTri[] = $row['Esq_Cod'];					
				}						
				$armado_xml.="<".$Eti_infoTri[0].">".$TipoAmbienteCE."</".$Eti_infoTri[0].">". 							  //<ambiente>
							"<".$Eti_infoTri[1].">".$TipoEmisionCE."</".$Eti_infoTri[1].">".  							  //<tipoEmision>
							"<".$Eti_infoTri[2].">".mb_convert_encoding($rs_infoEmpresa['Emp_Nom'], 'UTF-8', 'ISO-8859-1')."</".$Eti_infoTri[2].">".  //<razonSocial>
							"<".$Eti_infoTri[3].">".mb_convert_encoding($rs_infoEmpresa['Emp_Cor'], 'UTF-8', 'ISO-8859-1')."</".$Eti_infoTri[3].">".  //<nombreComercial>
							"<".$Eti_infoTri[4].">".$rs_infoEmpresa['Emp_Ruc']."</".$Eti_infoTri[4].">".  //<ruc>
							"<".$Eti_infoTri[5].">".$claveAcceso."</".$Eti_infoTri[5].">".  			  //<claveAcceso>
							"<".$Eti_infoTri[6].">".str_pad($rs_infoCliente['Tic_Sri'], 2, "0", STR_PAD_LEFT)."</".$Eti_infoTri[6].">".  //<codDoc>
							"<".$Eti_infoTri[7].">".$rs_infoEmpresa['Suc_Sri']."</".$Eti_infoTri[7].">".  //<estab> 
							"<".$Eti_infoTri[8].">".$rs_infoCliente['Pun_Sri']."</".$Eti_infoTri[8].">".  //<ptoEmi>
							"<".$Eti_infoTri[9].">".$ceroDoc.$rs_infoCliente['Vet_Num']."</".$Eti_infoTri[9].">".  //<secuencial>
							"<".$Eti_infoTri[10].">".mb_convert_encoding($rs_infoEmpresa['Suc_Dir'], 'UTF-8', 'ISO-8859-1')."</".$Eti_infoTri[10].">";//<dirMatriz>						
	$armado_xml .="</".$Eti_raiz[0].">"; //</infoTributaria>
	
	
	$armado_xml .="<".$Eti_raiz[1].">";  //<infoNotaCredito> 				
				
				/**
				* consultamos los totales de la venta sin impuesto y total del descuento
				*/				
				$rs_infoTotales = $obBD_con1->getRowConsulta(1213, $Vet_Cod, $obBD_conexion);
				/* consulta para obtener los importes con iva 12% */
				$rs_infoImpIva12 = $obBD_con1->getRowConsulta(1242, $Vet_Cod, $obBD_conexion);								
				$valorModificado=((($rs_infoImpIva12['total']-$rs_infoImpIva12['Dscto'])*$rs_infoImpIva12['Iva_Por'])/100); //obtener iva solo prodtos con iva 								
				$valorModificado=$valorModificado + $rs_infoTotales['total'];// le sumamos el importe sin iva
				
				$rs_infoFactura = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_raiz[1], $obBD_conexion);								
				unset ($row);
				foreach($rs_infoFactura as $row)
				{
					$Eti_infoFac[] = $row['Esq_Xml'];
					$Cod_infoFac[] = $row['Esq_Cod'];					
				}	
				
				$armado_xml.="<".$Eti_infoFac[0].">".$rs_infoCliente['fecha']."</".$Eti_infoFac[0].">".     //<fechaEmision>							
							"<".$Eti_infoFac[2].">".$rs_infoCliente['Ide_Prv']."</".$Eti_infoFac[2].">". 	//<tipoIdentificacionComprador>
							"<".$Eti_infoFac[3].">".mb_convert_encoding($rs_infoCliente['Prs_Nom']." ".$rs_infoCliente['Prs_Ape'], 'UTF-8', 'ISO-8859-1')."</".$Eti_infoFac[3].">".   //<razonSocialComprador>
							"<".$Eti_infoFac[4].">".$rs_infoCliente['Prs_Ced']."</".$Eti_infoFac[4].">";	//<identificacionComprador>
				if($rs_infoEmpresa['Emp_Reg']!=''){				
				$armado_xml.="<".$Eti_infoFac[5].">".$rs_infoEmpresa['Emp_Reg']."</".$Eti_infoFac[5].">";    //<contribuyenteEspecial>
				}
				$armado_xml.="<".$Eti_infoFac[6].">".$rs_infoEmpresa['Emp_Cnt']."</".$Eti_infoFac[6].">".    //<obligadoContabilidad>
							"<".$Eti_infoFac[7].">".$rs_infoCliente['Vet_Ntd']."</".$Eti_infoFac[7].">".    //<codDocModificado>
							"<".$Eti_infoFac[8].">".$rs_infoCliente['Vet_Nns']."</".$Eti_infoFac[8].">".    //<numDocModificado>
							"<".$Eti_infoFac[9].">".$rs_infoCliente['Vet_Fdm']."</".$Eti_infoFac[9].">".    //<fechaEmisionDocSustento>							
							"<".$Eti_infoFac[10].">".formato_numero($rs_infoTotales['total'],2,1)."</".$Eti_infoFac[10].">".	//<totalSinImpuestos>
							//"<".$Eti_infoFac[14].">".formato_numero($rs_infoTotales['Dscto'],2,1)."</".$Eti_infoFac[7].">".		//<totalDescuento>
							"<".$Eti_infoFac[11].">".formato_numero($valorModificado,2,1)."</".$Eti_infoFac[11].">".		//<valorModificacion>
							"<".$Eti_infoFac[12].">".'DOLAR'."</".$Eti_infoFac[12].">".		//<moneda>
							"<".$Eti_infoFac[13].">";   //<totalConImpuestos>
									$rs_totImpuesto = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_infoFac[13], $obBD_conexion);
									unset ($row);
									foreach($rs_totImpuesto as $row)
									{
										$Eti_totImp[] = $row['Esq_Xml'];
										$Cod_totImp[] = $row['Esq_Cod'];					
									}
									$rs_importeImptos = $obBD_con1->getArrayConsulta(1214, $Vet_Cod, $obBD_conexion);
									$desgloDscto=$rs_infoTotales['Dscto']/count($rs_importeImptos);																										
									foreach($rs_importeImptos as $datosImpto){
										$armado_xml.="<".$Eti_totImp[0].">";   //<totalImpuesto>
										   	
										   $rs_totImpuestoDato = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_totImp[0], $obBD_conexion);											
										   unset ($row);										
										   foreach($rs_totImpuestoDato as $row)
										   {
												$Eti_totImpDat[] = $row['Esq_Xml'];
												$Cod_totImpDat[] = $row['Esq_Cod'];					
										   }
										   /** Sumo todos los valores gnerados por cuention de importe y valor del impuesto */
										   $totalBase=$totalBase+($datosImpto['imp']-$desgloDscto);
										   $totalValor=$totalValor+formato_numero(((($datosImpto['imp']-$desgloDscto)*$datosImpto['Iva_Por'])/100),2,1);
										   
										   $armado_xml.="<".$Eti_totImpDat[0].">2</".$Eti_totImpDat[0].">".                         //<codigo>
										   "<".$Eti_totImpDat[1].">".$datosImpto['Iva_Sri']."</".$Eti_totImpDat[1].">".		        //<codigoPorcentaje>
										   "<".$Eti_totImpDat[2].">".formato_numero(($datosImpto['imp']-$desgloDscto),2,1)."</".$Eti_totImpDat[2].">".	//<baseImponible>
										   "<".$Eti_totImpDat[3].">".formato_numero(((($datosImpto['imp']-$desgloDscto)*$datosImpto['Iva_Por'])/100),2,1)."</".$Eti_totImpDat[3].">";	 //<valor>										
										$armado_xml.="</".$Eti_totImp[0].">";  //</totalImpuesto>
									}
							$armado_xml.="</".$Eti_infoFac[13].">";  //</totalConImpuestos>
							if($rs_infoTotales['Vet_Obs']!=''){$obsNotaCre=$rs_infoTotales['Vet_Obs'];}else{$obsNotaCre="NINGUNA";}
							$armado_xml.="<".$Eti_infoFac[14].">".mb_convert_encoding($obsNotaCre, 'UTF-8', 'ISO-8859-1')."</".$Eti_infoFac[14].">";    //<motivo>																											
	$armado_xml .="</".$Eti_raiz[1].">";// </infoNotaCredito>
	
	
	$armado_xml .="<".$Eti_raiz[2].">"; // <detalles>
				$rs_detalle = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_raiz[2], $obBD_conexion);
				unset ($row);
				foreach($rs_detalle as $row)
				{
					$Eti_detalle[] = $row['Esq_Xml'];
					$Cod_detalle[] = $row['Esq_Cod'];					
				}
				
				/**
				*  consultamos los Items de la factura
				*/
				$rs_items = $obBD_con1->getArrayConsulta(1215, $Vet_Cod, $obBD_conexion);				
				foreach($rs_items as $row_itemDetalle){
					
				$armado_xml.="<".$Eti_detalle[0].">";    //<detalle>
						$rs_cabDetalle = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_detalle[0], $obBD_conexion);
						unset ($row);
						foreach($rs_cabDetalle as $row)
						{
							$Eti_cabDetalle[] = $row['Esq_Xml'];
							$Cod_cabDetalle[] = $row['Esq_Cod'];					
						}
						$armado_xml.="<".$Eti_cabDetalle[0].">".$row_itemDetalle['Pro_Cod']."</".$Eti_cabDetalle[0].">".    //<codigoInterno>									
									"<".$Eti_cabDetalle[1].">".mb_convert_encoding(trim($row_itemDetalle['Pro_Obs']), 'UTF-8', 'ISO-8859-1')."</".$Eti_cabDetalle[1].">".//<descripcion>
									"<".$Eti_cabDetalle[2].">".$row_itemDetalle['Vet_Can']."</".$Eti_cabDetalle[2].">".          //<cantidad>
									"<".$Eti_cabDetalle[3].">".formato_numero($row_itemDetalle['Vet_Pru'],2,1)."</".$Eti_cabDetalle[3].">". //<precioUnitario>  
									"<".$Eti_cabDetalle[4].">".$desgloDscto."</".$Eti_cabDetalle[4].">".          //<descuento>
									"<".$Eti_cabDetalle[5].">".formato_numero((($row_itemDetalle['Vet_Can']*$row_itemDetalle['Vet_Pru']) - $desgloDscto),2,1)."							</".$Eti_cabDetalle[5].">".      //<precioTotalSinImpuesto>
									"<".$Eti_cabDetalle[6].">";       //<impuestos>
											$rs_cabDetalleDat = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_cabDetalle[6], $obBD_conexion);
											unset ($row);
											foreach($rs_cabDetalleDat as $row)
											{
												$Eti_cabDetalleDat[] = $row['Esq_Xml'];
												$Cod_cabDetalleDat[] = $row['Esq_Cod'];					
											}
											
											$armado_xml.="<".$Eti_cabDetalleDat[0].">";  //<impuesto>
												   $rs_cabDetalleDatos = $obBD_con1->getArrayConsulta(1210,$Tan_Cod."*".$Cod_cabDetalleDat[0],$obBD_conexion);
												   unset ($row);
												   foreach($rs_cabDetalleDatos as $row)
												   {
														$Eti_cabDetalleDatos[] = $row['Esq_Xml'];
														$Cod_cabDetalleDatos[] = $row['Esq_Cod'];					
												   }
												   $armado_xml.="<".$Eti_cabDetalleDatos[0].">2</".$Eti_cabDetalleDatos[0].">".       //<codigo>
														 "<".$Eti_cabDetalleDatos[1].">".$row_itemDetalle['Iva_Sri']."</".$Eti_cabDetalleDatos[1].">".//<codigoPorcentaje>
														 "<".$Eti_cabDetalleDatos[2].">".$row_itemDetalle['Iva_Por']."</".$Eti_cabDetalleDatos[2].">".  //<tarifa>
														 "<".$Eti_cabDetalleDatos[3].">".formato_numero((($row_itemDetalle['Vet_Can']*$row_itemDetalle['Vet_Pru']) - $desgloDscto),2,1)."</".$Eti_cabDetalleDatos[3].">".  //<baseImponible>
																 "<".$Eti_cabDetalleDatos[4].">".formato_numero(((($row_itemDetalle['Vet_Can']*$row_itemDetalle['Vet_Pru']) - $desgloDscto)*$row_itemDetalle['Iva_Por']/100),2,1)."</".$Eti_cabDetalleDatos[4].">";   //<valor>
										$armado_xml.="</".$Eti_cabDetalleDat[0].">";  //</impuesto>
											
											
											
									$armado_xml.="</".$Eti_cabDetalle[6].">";    //</impuestos>
				$armado_xml.="</".$Eti_detalle[0].">"; //<detalle>
				}//foreach($rs_items as $row_itemDetalle)
				
	$armado_xml .="</".$Eti_raiz[2].">";  //</detalles>
	
	
	if($rs_infoCliente['Prs_Dir']!="" or $rs_infoCliente['Prs_Tel']!='' or $rs_infoCliente['Prs_Cor']!='')
	{
		$armado_xml .="<".$Eti_raiz[3].">"; //<infoAdicional>
			$rs_infoAdicional = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_raiz[3], $obBD_conexion);
			unset ($row);
			foreach($rs_infoAdicional as $row)
			{
				$Eti_infoAdicional[] = $row['Esq_Xml'];
				$Cod_infoAdicional[] = $row['Esq_Cod'];					
			}
			if($rs_infoCliente['Prs_Dir']!='')
			{ $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Dirección'>".mb_convert_encoding($rs_infoCliente['Prs_Dir'], 'UTF-8', 'ISO-8859-1')."</".$Eti_infoAdicional[0].">";	}
			if($rs_infoCliente['Prs_Tel']!='')
			{ $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Teléfono'>".$rs_infoCliente['Prs_Tel']."</".$Eti_infoAdicional[0].">"; }
			if($rs_infoCliente['Prs_Cor']!='')
			{ $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Email'>".$rs_infoCliente['Prs_Cor']."</".$Eti_infoAdicional[0].">"; }
		$armado_xml .="</".$Eti_raiz[3].">";  //</infoAdicional>
	}
	
	$armado_xml ='<notaCredito version="1.0.0" id="comprobante">'.$armado_xml.'</notaCredito>';	
	$buffer = '<?xml version="1.0" encoding="UTF-8"?>';
	$buffer = $buffer.$armado_xml;
	$archivo = $Ses_Emp_Cod."/".$claveAcceso.".xml";
	
	$xml=new DomDocument("1.0","UTF-8");
	$xml->loadXML($buffer);
	$xml->xmlStandalone=true;
	
	
	$xml->formatOut=true;
	$strings_xml=$xml->saveXML();
	
	$xml->save($archivo)	
	//echo "Archivo XML generado correspondiente a <strong>".mes($mes, 1)."</strong> del <strong>".$anio."</strong>  <a href=".$archivo." target='_blank'><img src='../../mascaras/model1/imagenes/download.gif' title='Descargar XML'></a>"; 
//}
?>