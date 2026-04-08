<?
	/**
*	Componente para generar el xml del comprobante de venta
*	Desarrollador: Jose Cumbicos
*	Fecha creacion: 09/01/2015
*/
   
    /*
	*  Tabla Esquema ;	
	*/
	$Tan_Cod=6;	
	/**
	*   Consultamos las estiquetas Raiz del XML Factura Electronica
	*/
	$rs_esquema = $obBD_con1->getArrayConsulta(1048, $Tan_Cod."*0", $obBD_conexion);				
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
				$rs_infoEmpresa = $obBD_con1->getRowConsulta(1049, $Ses_Suc_Cod, $obBD_conexion);												
				
				/**
				*  Consultamos informacion del proveedor
				*/
				$rs_infoCliente = $obBD_con1->getRowConsulta(1050, $Cop_Cod, $obBD_conexion);
				/**
				*   consultamos las etiquetas Raiz del XML
				*/
				$rs_infoTributaria = $obBD_con1->getArrayConsulta(1048, $Tan_Cod."*".$Cod_raiz[0], $obBD_conexion);				
				/* Calculo del Digito verificador de la claveAcceso*/
				$ceroDoc="";
				for($i=strlen($rs_infoCliente['Ret_Num']); $i<=9-1; $i++)
				{	
					$ceroDoc=$ceroDoc."0";
				}	
				$TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
				$TipoEmisionCE=$rs_infoEmpresa['Cof_Fte'];
				
				/*Control para generar la clave de acceso de tipo  1=Normal  2=Indisponibilidad de Sistema WebService SRI*/
				if($rs_infoEmpresa['Cof_Fte']=='1')
				{	
					/*clave de acceso de tipo emision NORMAL*/
					$cadena=str_replace("/","",$rs_infoCliente['fecha'])."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$rs_infoCliente['Ret_Num']."12345678".$rs_infoEmpresa['Cof_Fte'];
				}else{
					/*preguntamos si el txt aun posee numeros para usar*/
					if(count(file($Ses_Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']))!=0)
					{	
						$file = file($Ses_Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']);
						/*clave de acceso de tipo emision INDISPONIBILIDAD DEL SISTEMA*/
						$cadena=str_replace("/","",$rs_infoCliente['fecha'])."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].substr($file[0], 14, 23).$rs_infoEmpresa['Cof_Fte'];
					}else{					
						/*clave de acceso de tipo emision NORMAL*/
						$cadena=str_replace("/","",$rs_infoCliente['fecha'])."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$rs_infoCliente['Ret_Num']."12345678".$rs_infoEmpresa['Cof_Fte'];							    				
						$TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
						$TipoEmisionCE="1";												
					}
				}
							
				$factor = 2;
				$suma = 0;
				//echo $cadena;
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
				$armado_xml.="<".$Eti_infoTri[0].">".$TipoAmbienteCE."</".$Eti_infoTri[0].">".                         //<ambiente>
							"<".$Eti_infoTri[1].">".$TipoEmisionCE."</".$Eti_infoTri[1].">".  						   //<tipoEmision>
							"<".$Eti_infoTri[2].">".utf8_encode($rs_infoEmpresa['Emp_Nom'])."</".$Eti_infoTri[2].">".  //<razonSocial>
							"<".$Eti_infoTri[3].">".utf8_encode($rs_infoEmpresa['Emp_Cor'])."</".$Eti_infoTri[3].">".  //<nombreComercial>
							"<".$Eti_infoTri[4].">".$rs_infoEmpresa['Emp_Ruc']."</".$Eti_infoTri[4].">".               //<ruc>
							"<".$Eti_infoTri[5].">".$claveAcceso."</".$Eti_infoTri[5].">".  			               //<claveAcceso>
							"<".$Eti_infoTri[6].">".'07'."</".$Eti_infoTri[6].">".               //<codDoc>
							"<".$Eti_infoTri[7].">".$rs_infoEmpresa['Suc_Sri']."</".$Eti_infoTri[7].">".               //<estab> 
							"<".$Eti_infoTri[8].">".$rs_infoCliente['Pun_Sri']."</".$Eti_infoTri[8].">".               //<ptoEmi>
							"<".$Eti_infoTri[9].">".$ceroDoc.$rs_infoCliente['Ret_Num']."</".$Eti_infoTri[9].">".      //<secuencial>
							"<".$Eti_infoTri[10].">".utf8_encode($rs_infoEmpresa['Suc_Dir'])."</".$Eti_infoTri[10].">";//<dirMatriz>						
	$armado_xml .="</".$Eti_raiz[0].">"; //</infoTributaria>
	$armado_xml .="<".$Eti_raiz[1].">";  //<infoCompRetencion> 				
				
				/**
				* consultamos los totales de la venta sin impuesto y total del descuento
				*/
				$rs_infoTotales = $obBD_con1->getRowConsulta(1051, $Cop_Cod, $obBD_conexion);
				
				/**
				* consultamos el periodo contable
				*/
				$rs_perContable = $obBD_con1->getRowConsulta(1053, $Ses_Emp_Cod, $obBD_conexion);
				
				$rs_infoFactura = $obBD_con1->getArrayConsulta(1048, $Tan_Cod."*".$Cod_raiz[1], $obBD_conexion);								
				unset ($row);
				foreach($rs_infoFactura as $row)
				{
					$Eti_infoFac[] = $row['Esq_Xml'];
					$Cod_infoFac[] = $row['Esq_Cod'];					
				}							
				$armado_xml.="<".$Eti_infoFac[0].">".$rs_infoCliente['fecha']."</".$Eti_infoFac[0].">".               //<fechaEmision>
							"<".$Eti_infoFac[1].">".utf8_encode($rs_infoEmpresa['Suc_Dir'])."</".$Eti_infoFac[1].">"; //<dirEstablecimiento>
				if($rs_infoEmpresa['Emp_Reg']!=''){				
				$armado_xml.="<".$Eti_infoFac[2].">".$rs_infoEmpresa['Emp_Reg']."</".$Eti_infoFac[2].">";            //<contribuyenteEspecial>
				}
				$armado_xml.="<".$Eti_infoFac[3].">".$rs_infoEmpresa['Emp_Cnt']."</".$Eti_infoFac[3].">". 	         //<obligadoContabilidad>
							"<".$Eti_infoFac[4].">".$rs_infoCliente['Ide_Prv']."</".$Eti_infoFac[4].">".  	         //<tipoIdentificacionSujetoRetenido>
							"<".$Eti_infoFac[5].">".utf8_encode($rs_infoCliente['Prs_Nom']." ".$rs_infoCliente['Prs_Ape'])."</".$Eti_infoFac[5].">".	//<razonSocialSujetoRetenido> 
							"<".$Eti_infoFac[6].">".$rs_infoCliente['Prs_Ced']."</".$Eti_infoFac[6].">".	  //<identificacionSujetoRetenido>
							"<".$Eti_infoFac[7].">".date("m/Y",strtotime($rs_infoCliente['Ret_Fec']))."</".$Eti_infoFac[7].">";		  //<periodoFiscal>
	$armado_xml .="</".$Eti_raiz[1].">";  //</infoCompRetencion> 	$rs_perContable['PerCon']																					
	
	$armado_xml .="<".$Eti_raiz[2].">"; 	//<impuestos>						
				
				$rs_infoFactura = $obBD_con1->getArrayConsulta(1048, $Tan_Cod."*".$Cod_raiz[2], $obBD_conexion);
				//print_r($rs_infoFactura);								
				unset ($row);
				foreach($rs_infoFactura as $row)
				{
					$Eti_infoImp[] = $row['Esq_Xml'];
					$Cod_infoImp[] = $row['Esq_Cod'];					
				}							
								
				$rs_infoFactura = $obBD_con1->getArrayConsulta(1048, $Tan_Cod."*".$Cod_infoImp[0], $obBD_conexion);																
				unset ($row);
				foreach($rs_infoFactura as $row)
				{
					$Eti_infoImps[] = $row['Esq_Xml'];
					$Cod_infoImps[] = $row['Esq_Cod'];					
				}
				
				//Consultamos los Datos de la retencion
				//$rs_retInfoFact = $obBD_con1->getArrayConsulta(1056, $Cop_Cod, $obBD_conexion);
				$rs_retInfoFact = $obBD_con1->getArrayConsulta(1113, $Cop_Cod, $obBD_conexion);
				foreach($rs_retInfoFact as $Ret_Info)
				{
				    $armado_xml .="<".$Eti_infoImp[0].">"; 	//<impuesto>																												
							$armado_xml .="<".$Eti_infoImps[0].">".$Ret_Info['ImpCod']."</".$Eti_infoImps[0].">". 	              //<codigo>
									  "<".$Eti_infoImps[1].">".$Ret_Info['codigo']."</".$Eti_infoImps[1].">".                     //<codigoRetencion>
									  "<".$Eti_infoImps[2].">".formato_numero($Ret_Info['Ret_Bas'],2,1)."</".$Eti_infoImps[2].">".//<baseImponible>
									  "<".$Eti_infoImps[3].">".formato_numero($Ret_Info['Ren_Por'],1,1)."</".$Eti_infoImps[3].">".//<porcentajeRetener>
									  "<".$Eti_infoImps[4].">".formato_numero($Ret_Info['ValRet'],2,1)."</".$Eti_infoImps[4].">". //<valorRetenido>
									  "<".$Eti_infoImps[5].">".str_pad($Ret_Info['Tic_Sri'], 2, "0", STR_PAD_LEFT)."</".$Eti_infoImps[5].">". //<codDocSustento>
									  "<".$Eti_infoImps[6].">".str_replace("-","",$Ret_Info['Cop_Num'])."</".$Eti_infoImps[6].">".            //<numDocSustento>
									  "<".$Eti_infoImps[7].">".$Ret_Info['Cop_Fec']."</".$Eti_infoImps[7].">";                    //<fechaEmisionDocSustento>
					$armado_xml .="</".$Eti_infoImp[0].">";  //</impuesto>
			}				
	$armado_xml .="</".$Eti_raiz[2].">"; 	//</impuestos>						

	
	if($rs_infoCliente['Prs_Dir']!="" or $rs_infoCliente['Prs_Tel']!='' or $rs_infoCliente['Prs_Cor']!='')
	{

		$armado_xml .="<".$Eti_raiz[3].">"; //<infoAdicional>
			$rs_infoAdicional = $obBD_con1->getArrayConsulta(1048, $Tan_Cod."*".$Cod_raiz[3], $obBD_conexion);
			unset ($row);
			foreach($rs_infoAdicional as $row)
			{
				$Eti_infoAdicional[] = $row['Esq_Xml'];
				$Cod_infoAdicional[] = $row['Esq_Cod'];					
			}
			if($rs_infoCliente['Prs_Dir']!='')
			{ $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Direccion'>".utf8_encode($rs_infoCliente['Prs_Dir'])."</".$Eti_infoAdicional[0].">";	}
			if($rs_infoCliente['Prs_Tel']!='')
			{ $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Teléfono'>".$rs_infoCliente['Prs_Tel']."</".$Eti_infoAdicional[0].">"; }
			if($rs_infoCliente['Prs_Cor']!='')
			{ $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Email'>".$rs_infoCliente['Prs_Cor']."</".$Eti_infoAdicional[0].">"; }

		$armado_xml .="</".$Eti_raiz[3].">";  //</infoAdicional>
	}		
		
	
	$armado_xml ='<comprobanteRetencion id="comprobante" version="1.0.0">'.$armado_xml.'</comprobanteRetencion>';	
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