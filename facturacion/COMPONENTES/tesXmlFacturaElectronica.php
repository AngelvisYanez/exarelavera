<?php
	/**
*	Componente para generar el xml del comprobante de venta
*	Desarrollador: Jose Cumbicos
*	Fecha creacion: 09/01/2015
*/

//if (isset($hdd_save))	
//{
	$Tan_Cod=5;
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

	$armado_xml = "<".$Eti_raiz[0].">";
				$rs_infoTributaria = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_raiz[0], $obBD_conexion);
				unset ($row);
				foreach($rs_infoTributaria as $row)
				{
					$Eti_infoTri[] = $row['Esq_Xml'];
					$Cod_infoTri[] = $row['Esq_Cod'];					
				}			
				$armado_xml="<".$Eti_infoTri[0].">1</".$Eti_infoTri[0].">".
							"<".$Eti_infoTri[1].">1</".$Eti_infoTri[1].">".
							"<".$Eti_infoTri[2].">1</".$Eti_infoTri[2].">".
							"<".$Eti_infoTri[3].">CUMBICOS JOSE</".$Eti_infoTri[3].">".
							"<".$Eti_infoTri[4].">AGRONUEVO</".$Eti_infoTri[4].">".
							"<".$Eti_infoTri[5].">0791733790001</".$Eti_infoTri[5].">".
							"<".$Eti_infoTri[6].">0901201501079173379000110010010000000031234567810</".$Eti_infoTri[6].">".
							"<".$Eti_infoTri[7].">01</".$Eti_infoTri[7].">".
							"<".$Eti_infoTri[8].">001</".$Eti_infoTri[8].">".
							"<".$Eti_infoTri[9].">001</".$Eti_infoTri[9].">".
							"<".$Eti_infoTri[10].">000000003</".$Eti_infoTri[10].">".
							"<".$Eti_infoTri[11].">AV.25 JUNIO Y MACHALA</".$Eti_infoTri[11].">";
	$armado_xml ="</".$Eti_raiz[0].">";
	$armado_xml ="<".$Eti_raiz[1].">";						
				$rs_infoFactura = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_raiz[1], $obBD_conexion);
				unset ($row);
				foreach($rs_infoFactura as $row)
				{
					$Eti_infoFac[] = $row['Esq_Xml'];
					$Cod_infoFac[] = $row['Esq_Cod'];					
				}			
				$armado_xml="<".$Eti_infoFac[0].">09/01/2015</".$Eti_infoFac[0].">".
							"<".$Eti_infoFac[1].">SI</".$Eti_infoFac[1].">".
							"<".$Eti_infoFac[2].">05</".$Eti_infoFac[2].">".
							"<".$Eti_infoFac[3].">MORAN KERLY</".$Eti_infoFac[3].">".
							"<".$Eti_infoFac[4].">0704187673</".$Eti_infoFac[4].">".
							"<".$Eti_infoFac[5].">88.00</".$Eti_infoFac[5].">".
							"<".$Eti_infoFac[6].">2.00</".$Eti_infoFac[6].">".
							"<".$Eti_infoFac[7].">";
									$rs_totImpuesto = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_infoFac[7], $obBD_conexion);
									unset ($row);
									foreach($rs_totImpuesto as $row)
									{
										$Eti_totImp[] = $row['Esq_Xml'];
										$Cod_totImp[] = $row['Esq_Cod'];					
									}
									$armado_xml="<".$Eti_totImp[0].">";
										$rs_totImpuestoDato = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_infoFac[7], $obBD_conexion);
										unset ($row);
										foreach($rs_totImpuestoDato as $row)
										{
											$Eti_totImpDat[] = $row['Esq_Xml'];
											$Cod_totImpDat[] = $row['Esq_Cod'];					
										}
										$armado_xml="<".$Eti_totImpDat[0].">2</".$Eti_totImpDat[0].">".
													"<".$Eti_totImpDat[1].">2</".$Eti_totImpDat[1].">".
													"<".$Eti_totImpDat[2].">88.00</".$Eti_totImpDat[2].">".
													"<".$Eti_totImpDat[3].">10.56</".$Eti_totImpDat[3].">".
										
									$armado_xml="</".$Eti_totImp[0].">";
												
				$armado_xml="</".$Eti_infoFac[7].">".
							"<".$Eti_infoFac[8].">0.00</".$Eti_infoFac[8].">".
							"<".$Eti_infoFac[9].">98.56</".$Eti_infoFac[9].">".
							"<".$Eti_infoFac[10].">DOLAR</".$Eti_infoFac[10].">";																					
	$armado_xml ="</".$Eti_raiz[1].">";
	$armado_xml ="<".$Eti_raiz[2].">";
				$rs_detalle = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_raiz[2], $obBD_conexion);
				unset ($row);
				foreach($rs_detalle as $row)
				{
					$Eti_detalle[] = $row['Esq_Xml'];
					$Cod_detalle[] = $row['Esq_Cod'];					
				}
				$armado_xml="<".$Eti_detalle[0].">";
						$rs_cabDetalle = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_detalle[0], $obBD_conexion);
						unset ($row);
						foreach($rs_cabDetalle as $row)
						{
							$Eti_cabDetalle[] = $row['Esq_Xml'];
							$Cod_cabDetalle[] = $row['Esq_Cod'];					
						}
						$armado_xml="<".$Eti_cabDetalle[0].">AP-006</".$Eti_cabDetalle[0].">".
									"<".$Eti_cabDetalle[1].">AP-006</".$Eti_cabDetalle[1].">".
									"<".$Eti_cabDetalle[2].">TROPICO</".$Eti_cabDetalle[2].">".
									"<".$Eti_cabDetalle[3].">1</".$Eti_cabDetalle[3].">".
									"<".$Eti_cabDetalle[4].">15</".$Eti_cabDetalle[4].">".
									"<".$Eti_cabDetalle[5].">2</".$Eti_cabDetalle[5].">".
									"<".$Eti_cabDetalle[6].">13.00</".$Eti_cabDetalle[6].">".
									"<".$Eti_cabDetalle[7].">";
											$rs_cabDetalleDat = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_cabDetalle[7], $obBD_conexion);
											unset ($row);
											foreach($rs_cabDetalleDat as $row)
											{
												$Eti_cabDetalleDat[] = $row['Esq_Xml'];
												$Cod_cabDetalleDat[] = $row['Esq_Cod'];					
											}
											$armado_xml="<".$Eti_cabDetalleDat[0].">2</".$Eti_cabDetalleDat[0].">".
														"<".$Eti_cabDetalleDat[1].">2</".$Eti_cabDetalleDat[1].">".
														"<".$Eti_cabDetalleDat[2].">12.00</".$Eti_cabDetalleDat[2].">".
														"<".$Eti_cabDetalleDat[3].">13.00</".$Eti_cabDetalleDat[3].">".
														"<".$Eti_cabDetalleDat[4].">1.56</".$Eti_cabDetalleDat[4].">";
									$armado_xml="</".$Eti_cabDetalle[7].">";  
				$armado_xml="</".$Eti_detalle[0].">";
	
	$armado_xml ="</".$Eti_raiz[2].">";
	
	$armado_xml ="<".$Eti_raiz[3].">";
				$rs_detalle = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_raiz[3], $obBD_conexion);
				unset ($row);
				foreach($rs_detalle as $row)
				{
					$Eti_detalle[] = $row['Esq_Xml'];
					$Cod_detalle[] = $row['Esq_Cod'];					
				}
				$armado_xml="<".$Eti_detalle[0]." NOMBRE='Dirección'>SAN VICENTE, ARENILLAS</".$Eti_detalle[0].">".
							"<".$Eti_detalle[0]." NOMBRE='Teléfono'>2906019</".$Eti_detalle[0].">".
							"<".$Eti_detalle[0]." NOMBRE='Email'>kelyta_230283@hotmail.com</".$Eti_detalle[0].">";
				
	$armado_xml ="</".$Eti_raiz[3].">";
	$armado_xml ="<factura version='1.0.0' id='comprobante'>".$armado_xml."</factura>";
	
	$buffer = '<?xml version="1.0" encoding="UTF-8" standalone="true"?>';
	$buffer = $buffer.$armado_xml;

	$archivo="aaa/prueba".$mes.$anio.".xml";
	 
	//$file=fopen($anio.$mes.".xml","w+"); 
	$file=fopen($archivo,"w+"); 
	fwrite ($file,$buffer); 
	fclose($file); 
	//echo "Archivo XML generado correspondiente a <strong>".mes($mes, 1)."</strong> del <strong>".$anio."</strong>  <a href=".$archivo." target='_blank'><img src='../../mascaras/model1/imagenes/download.gif' title='Descargar XML'></a>"; 
//}
?>