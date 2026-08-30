<?php
	/**
*	Componente para generar el xml del comprobante de venta
*	Desarrollador: Jose Cumbicos
*	Fecha creacion: 09/01/2015
*/

//if (isset($hdd_save))	
//{     
       
	$Tan_Cod=9;	
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
				$rs_infoCliente = $obBD_con1->getRowConsulta(1212, $Gui_Cod, $obBD_conexion);
				/**
				*   consultamos las etiquetas Raiz del XML
				*/
				$rs_infoTributaria = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_raiz[0], $obBD_conexion);				
				/* Calculo del Digito verificador de la claveAcceso*/
				$ceroDoc="";
				for($i=strlen($rs_infoCliente['Gui_Num']); $i<=9-1; $i++)
				{
					$ceroDoc=$ceroDoc."0";
				}
				$TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
				$TipoEmisionCE=$rs_infoEmpresa['Cof_Fte'];
												
				/*Control para generar la clave de acceso de tipo  1=Normal  2=Indisponibilidad de Sistema WebService SRI*/
				if($rs_infoEmpresa['Cof_Fte']=='1')
				{	
					$cadena=str_replace("/","",$rs_infoCliente['fecha2'])."06".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$rs_infoCliente['Gui_Num']."12345678".$rs_infoEmpresa['Cof_Fte'];	
				}else{
					/*preguntamos si el txt aun posee numeros para usar*/
					if(count(file($Ses_Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']))!=0)
					{	
						$file = file($Ses_Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']);
						/*clave de acceso de tipo emision INDISPONIBILIDAD DEL SISTEMA*/
						$cadena=str_replace("/","",$rs_infoCliente['fecha2'])."06".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].substr($file[0], 14, 23).$rs_infoEmpresa['Cof_Fte'];
					}else{					
						/*clave de acceso de tipo emision NORMAL*/
						$cadena=str_replace("/","",$rs_infoCliente['fecha2'])."06".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$rs_infoCliente['Gui_Num']."12345678".$rs_infoEmpresa['Cof_Fte'];
							    				
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
				/*-------------------------*/												
				unset ($row);
				foreach($rs_infoTributaria as $row)
				{
					$Eti_infoTri[] = $row['Esq_Xml'];
					$Cod_infoTri[] = $row['Esq_Cod'];					
				}						
				$armado_xml.="<".$Eti_infoTri[0].">".$rs_infoEmpresa['Cof_Fac']."</".$Eti_infoTri[0].">". //<ambiente>
							"<".$Eti_infoTri[1].">".$rs_infoEmpresa['Cof_Fte']."</".$Eti_infoTri[1].">".  //<tipoEmision>
							"<".$Eti_infoTri[2].">".mb_convert_encoding($rs_infoEmpresa['Emp_Nom'], 'UTF-8', 'ISO-8859-1')."</".$Eti_infoTri[2].">".  //<razonSocial>
							"<".$Eti_infoTri[3].">".mb_convert_encoding($rs_infoEmpresa['Emp_Cor'], 'UTF-8', 'ISO-8859-1')."</".$Eti_infoTri[3].">".  //<nombreComercial>
							"<".$Eti_infoTri[4].">".$rs_infoEmpresa['Emp_Ruc']."</".$Eti_infoTri[4].">".  //<ruc>
							"<".$Eti_infoTri[5].">".$claveAcceso."</".$Eti_infoTri[5].">".  			  //<claveAcceso>
							"<".$Eti_infoTri[6].">".str_pad($rs_infoCliente['Tic_Sri'], 2, "0", STR_PAD_LEFT)."</".$Eti_infoTri[6].">".  //<codDoc>
							"<".$Eti_infoTri[7].">".$rs_infoEmpresa['Suc_Sri']."</".$Eti_infoTri[7].">".  //<estab> 
							"<".$Eti_infoTri[8].">".$rs_infoCliente['Pun_Sri']."</".$Eti_infoTri[8].">".  //<ptoEmi>
							"<".$Eti_infoTri[9].">".$ceroDoc.$rs_infoCliente['Gui_Num']."</".$Eti_infoTri[9].">".  //<secuencial>
							"<".$Eti_infoTri[10].">".mb_convert_encoding($rs_infoEmpresa['Suc_Dir'], 'UTF-8', 'ISO-8859-1')."</".$Eti_infoTri[10].">";//<dirMatriz>						
	$armado_xml .="</".$Eti_raiz[0].">"; //</infoTributaria>
	$armado_xml .="<".$Eti_raiz[1].">";  //<infoGuiRemision> 				
												
				$rs_infoFactura = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_raiz[1], $obBD_conexion);								
				unset ($row);
				foreach($rs_infoFactura as $row)
				{
					$Eti_infoFac[] = $row['Esq_Xml'];
					$Cod_infoFac[] = $row['Esq_Cod'];					
				}		
				
				$rs_infoTrans = $obBD_con1->getRowConsulta(1268, $Gui_Cod."*".$Ses_Emp_Cod, $obBD_conexion);	
				$armado_xml.="<".$Eti_infoFac[0].">".$rs_infoTrans['Gui_Dsa']."</".$Eti_infoFac[0].">".     //<dirPartida>								
							 "<".$Eti_infoFac[1].">".mb_convert_encoding($rs_infoTrans['Prs_Nom']." ".$rs_infoTrans['Prs_Ape'], 'UTF-8', 'ISO-8859-1')."</".$Eti_infoFac[1].">".//<razonSocialTransportista>
							 "<".$Eti_infoFac[2].">".$rs_infoTrans['Ide_Prv']."</".$Eti_infoFac[2].">". 	//<tipoIdentificacionTransportista>
							 "<".$Eti_infoFac[3].">".$rs_infoTrans['Prs_Ced']."</".$Eti_infoFac[3].">".   //<rucTransportista>
							 "<".$Eti_infoFac[4].">".$rs_infoEmpresa['Emp_Cnt']."</".$Eti_infoFac[4].">".   //<obligadoContabilidad>                                       
							 "<".$Eti_infoFac[5].">".$rs_infoTrans['Gui_Fsa']."</".$Eti_infoFac[5].">".	//<fechaIniTransporte>
							 "<".$Eti_infoFac[6].">".$rs_infoTrans['Gui_Far']."</".$Eti_infoFac[6].">".	//<fechaFinTransporte>
							 "<".$Eti_infoFac[7].">".$rs_infoTrans['Gui_Pla']."</".$Eti_infoFac[7].">";	//<placa>							
	$armado_xml .="</".$Eti_raiz[1].">";// </infoGuiRemision>
	
	
	$armado_xml .="<".$Eti_raiz[2].">"; // <destinatarios>
					$rs_destina = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_raiz[2], $obBD_conexion);
					unset ($row);
					foreach($rs_destina as $row)
					{
						$Eti_destina[] = $row['Esq_Xml'];
						$Cod_destina[] = $row['Esq_Cod'];					
					}
					$armado_xml.="<".$Eti_destina[0].">";    //<destinatario>
								$rs_destInfo = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_destina[0], $obBD_conexion);			 
					unset ($row);
					foreach($rs_destInfo as $row)
					{
						$Eti_destInfo[] = $row['Esq_Xml'];
						$Cod_destInfo[] = $row['Esq_Cod'];					
					}			 
					$rs_infoDestin = $obBD_con1->getRowConsulta(1269, $Gui_Cod."*".$Ses_Emp_Cod, $obBD_conexion);				
					$armado_xml.="<".$Eti_destInfo[0].">".$rs_infoDestin['Prs_Ced']."</".$Eti_destInfo[0].">".   //<identificacionDestinatario>
								 "<".$Eti_destInfo[1].">".mb_convert_encoding($rs_infoDestin['Prs_Ape'].' '.$rs_infoDestin['Prs_Nom'], 'UTF-8', 'ISO-8859-1')."</".$Eti_destInfo[1].">".   //<razonSocialDestinatario>
								 "<".$Eti_destInfo[2].">".$rs_infoDestin['Gui_Dar']."</".$Eti_destInfo[2].">".   //<dirDestinatario>
								 "<".$Eti_destInfo[3].">".$rs_infoDestin['Gui_Mot']."</".$Eti_destInfo[3].">".   //<motivoTraslado>
								 "<".$Eti_destInfo[4].">".$rs_infoDestin['Des_Adu']."0</".$Eti_destInfo[4].">".   //<docAduaneroUnico>
								 "<".$Eti_destInfo[5].">".$rs_infoDestin['Des_Sri']."</".$Eti_destInfo[5].">".   //<codEstabDestino>
								 "<".$Eti_destInfo[6].">".$rs_infoDestin['Gui_Rut']."</".$Eti_destInfo[6].">";   //<ruta>
								 if($rs_infoDestin['Gui_Dve']!=''){//<codDocSustento>
								 $armado_xml.="<".$Eti_destInfo[7].">".$rs_infoDestin['Gui_Dve']."</".$Eti_destInfo[7].">";}
								 if($rs_infoDestin['Gui_Nve']!=''){//<numDocSustento>
								 $armado_xml.="<".$Eti_destInfo[8].">".$rs_infoDestin['Gui_Nve']."</".$Eti_destInfo[8].">";}
								 if($rs_infoDestin['Gui_Ave']!=''){//<numAutDocSustento>
								 $armado_xml.="<".$Eti_destInfo[9].">".$rs_infoDestin['Gui_Ave']."</".$Eti_destInfo[9].">";}
								 if($rs_infoDestin['Gui_Fsa']!='0000-00-00'){//<fechaEmisionDocSustento>
								 $armado_xml.="<".$Eti_destInfo[10].">".$rs_infoDestin['Gui_Fsa']."</".$Eti_destInfo[10].">";}								 
								 $armado_xml.="<".$Eti_destInfo[11].">"; //<detalles>
										$rs_detalle = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_destInfo[11], $obBD_conexion);
										$rs_items = $obBD_con1->getArrayConsulta(1270, $Gui_Cod, $obBD_conexion);
										unset ($row);
										foreach($rs_detalle as $row)
										{
											$Eti_detalle[] = $row['Esq_Xml'];
											$Cod_detalle[] = $row['Esq_Cod'];					
										}
										foreach($rs_items as $dato)
										{
										$armado_xml.="<".$Eti_detalle[0].">";//<detalle>
											$rs_detInfo = $obBD_con1->getArrayConsulta(1210, $Tan_Cod."*".$Cod_detalle[0], $obBD_conexion);
											unset ($row);
											foreach($rs_detInfo as $row)
											{
												$Eti_detInfo[] = $row['Esq_Xml'];
												$Cod_detInfo[] = $row['Esq_Cod'];					
											}
											$armado_xml.="<".$Eti_detInfo[0].">".$dato['Pro_Cod']."</".$Eti_detInfo[0].">".//<codigoInterno>
														 "<".$Eti_detInfo[1].">".$dato['Ite_Lar'].' '.$dato['Pro_Obs']."</".$Eti_detInfo[1].">".//<descripcion>
														 "<".$Eti_detInfo[2].">".$dato['Gui_Can']."</".$Eti_detInfo[2].">";//<cantidad>
														 //"<".$Eti_detInfo[3].">".$dato['Gui_Can']."</".$Eti_detInfo[3].">";//<codigoAdicional>										
										$armado_xml.="</".$Eti_detalle[0].">";//</detalle>								 
										}
								 $armado_xml.="</".$Eti_destInfo[11].">";//</detalles>	
					$armado_xml.="</".$Eti_destina[0].">";    //</destinatario>													
	$armado_xml .="</".$Eti_raiz[2].">";  //</destinatarios>
	
	
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
	
	$armado_xml ='<guiaRemision version="1.0.0" id="comprobante">'.$armado_xml.'</guiaRemision>';	
	$buffer = '<?xml version="1.0" encoding="UTF-8"?>';
	$buffer = $buffer.$armado_xml;
	$archivo = $Ses_Emp_Cod."/".$claveAcceso.".xml";
	
	$xml=new DomDocument("1.0","UTF-8");
	$xml->loadXML($buffer);
	$xml->xmlStandalone=true;
	
	
	$xml->formatOut=true;
	$strings_xml=$xml->saveXML();
	
	$xml->save($archivo);
	//echo "Archivo XML generado correspondiente a <strong>".mes($mes, 1)."</strong> del <strong>".$anio."</strong>  <a href=".$archivo." target='_blank'><img src='../../mascaras/model1/imagenes/download.gif' title='Descargar XML'></a>"; 
//}
?>