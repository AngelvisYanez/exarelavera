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
require_once('../LOGICA/tes_log_anexo1.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

function cleanEspecialChar(&$input)
{
	if (is_string($input)) {
		$input = str_ireplace(array('&', "'", "\"", '<', '>', "~", "^", "¿", "?"), array("&amp;", "&apos;", "&quot;", "&lt;", "&gt;", "", "", "", ""), trim($input));
	} else if (is_array($input)) {
		foreach ($input as &$value) {
			cleanEspecialChar($value);
		}
		unset($value);
	} else if (is_object($input)) {
		$vars = array_keys(get_object_vars($input));
		foreach ($vars as $var) {
			cleanEspecialChar($input->$var);
		}
	}
}
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

/*
ini_set("memory_limit", "128M");
ini_set('max_execution_time', 600);*/

ini_set("memory_limit", "128M");
ini_set('max_execution_time', 9600);


$valAuxCom = 0;
$valAuxVen = 0;
$valAuxExp = 0;
$valAuxAnu = 0;

if (isset($bt_save)) {


	if ($chk_fechas) {
		$ini = $Ats_Fec_Ini;
		$fin = $Ats_Fec_Fin;
		$anio = substr($fin, 0, 4);
		$mes = substr($fin, 5, 2);
	} else {
		$ini = $anio . '-' . $mes . '-' . '01';
		$fin = $anio . '-' . $mes . '-' . ultimoDia($mes, $anio);
	}

	/**
	 * Identificaci�n 
	 * Esta consulta nos permite obtener la informacion de la Empresa(Ruc, Nombre, etc) para llenar el encabezado del
	 * archivo XML a generar
	 */
	$row_rs_identifica = $obBD_con1->getRowConsulta(226, $Ses_Emp_Cod, $obBD_conexion);
	cleanEspecialChar($row_rs_identifica);


	/**
	 * Consulta el total de puntos de impresion - Esto calcula en funcion de las ventas realizadas
	 */
	//$row_puntos = $obBD_con1->getArrayConsulta(392, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);	
	$row_puntos = $obBD_con1->getArrayConsulta(226, $Ses_Emp_Cod, $obBD_conexion);


	/**
	 * Cargado de etiquetas de identificacion 
	 * Las etiquetas obtenidas corresponden al ANEXO TRANSACCIONAL SIMPLIFICADO 2010 parametro 2 -->[3] de la SQL 227, los cuales nos 
	 * permiten hacer el armado de todo el archivo XML  
	 */
	$rs_etiquetas = $obBD_con1->getArrayConsulta(227, '-1' . '*' . '8', $obBD_conexion);
	$total_rs_etiquetas = count($rs_etiquetas);

	/**
	 * Inicio de bucle de la identificaci�n 
	 * Asignamos las etiquetas consultadas a la variable "$identificacion[]"
	 */
	foreach ($rs_etiquetas as $row_rs_etiquetas) {
		$identificacion[] = $row_rs_etiquetas['Esq_Xml'];
	}

	/*********************************************************************************/
	/*         E N C A B E Z A D O   P R I N C I P A L   D E L    X M L              */
	/*********************************************************************************/
	/**
	 * La varible  "$xml_identifica" es la que contendra el encabezado principal del XML 
	 * La variable "$row_rs_identifica[?]" posee la informacion obtenida de la base de datos
	 */
	$row_tipCompr = $obBD_con1->getArrayConsulta(862, '01*04', $obBD_conexion);
	/* Consulta el total de ventas tipo Factura */
	$row_ventas = $obBD_con1->getRowConsulta(391, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . '*' . $row_tipCompr[0]['Tic_Sri'], $obBD_conexion);
	/* Consulta el total de ventas tipo Ventas por tipo Reembolso */
	$row_ventasReembolso = $obBD_con1->getRowConsulta(391, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . '*41', $obBD_conexion);
	/* Consulta el total de ventas tipo Notas de Credito */
	$row_ventasNotCredito = $obBD_con1->getRowConsulta(391, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . '*4', $obBD_conexion);
	/* Consultamos si la empresa genera comprobantes electronico */
	$row_rs_configEmpresa = $obBD_con1->getRowConsulta(863, $Ses_Emp_Cod, $obBD_conexion);
	/**
	 * Consultando las ventas por establecimiento (sucursal)
	 */
	$rs_establecimiento = $obBD_con1->getArrayConsulta(393, $ini . '*' . $fin . '*' . $Ses_Emp_Cod, $obBD_conexion);

	$sinVentas = '0';
	if ($row_ventas['Total'] == 0 && count($row_ventasReembolso)) {
		$tot_ventas = '00';
		$sinVentas = '1';
	} else {
		/* nuevo */
		$tot_ventas = 0;
		foreach ($rs_establecimiento as $row_rs_establecimiento)
			$tot_ventas += $row_rs_establecimiento['Total'];
		$tot_ventas = formato_numero($tot_ventas, 2, 1);
		/* nuevo */
		/* comentado por erik, no entendi que hace
		if($row_rs_configEmpresa['Cof_Gce']=='N'){
			$tot_ventas = formato_numero($row_ventas['Total'] - $row_ventasNotCredito['Total'],2,1);
			//var_dump($row_ventasNotCredito);
		}else{
			$row_ventasExt = $obBD_con1->getRowConsulta(880, $ini.'*'.$fin.'*'.$Ses_Emp_Cod.'*'.$row_tipCompr[0]['Tic_Sri'], $obBD_conexion);
			$tot_ventas ='00';		
			//var_dump($row_ventasExt);
			$tot_ventas+=($row_ventasExt['Total']- $row_ventasNotCredito['Total']);
		}*/
	}
	$xml_identifica = "<" . $identificacion[4] . ">R</" . $identificacion[4] . ">" .
		"<" . $identificacion[0] . ">" . $row_rs_identifica['Emp_Ruc'] . "</" . $identificacion[0] . ">" .
		"<" . $identificacion[1] . ">" . strtoupper($row_rs_identifica['Emp_Nom']) . "</" . $identificacion[1] . ">" .
		"<" . $identificacion[2] . ">" . utf8_encode($anio) . "</" . $identificacion[2] . ">" .
		"<" . $identificacion[3] . ">" . utf8_encode($mes) . "</" . $identificacion[3] . ">";

	if ($chk_fechas) {
		$xml_identifica .= "<regimenMicroempresa>SI</regimenMicroempresa>";
	}

	$xml_identifica .= "<" . $identificacion[7] . ">" . str_pad(count($row_puntos), 3, "0", STR_PAD_LEFT) . "</" . $identificacion[7] . ">" .
		"<" . $identificacion[5] . ">" . formato_numero($tot_ventas, 2, 1) . "</" . $identificacion[5] . ">" . //total ventas
		"<" . $identificacion[6] . ">IVA</" . $identificacion[6] . ">";
	/**
	 * Fin de la Etiqueta del Encabezado
	 */

	/*=========================================*/
	/*    C O M P R A S   D E L    X M L       */
	/*=========================================*/
	if (isset($_POST['chk_Compras'])) {
		/*----------------------------------------*/
		/*    C O M P R A S   D E L    X M L      */
		/*----------------------------------------*/
		$valAuxCom = 1;

		/**
		 * Cargado de etiquetas de nivel cero 
		 */
		$row_rs_etiquetas_cero = $obBD_con1->getRowConsulta(234, '0' . '*' . '8' . '*' . 'C', $obBD_conexion);

		/**
		 * Cargado de los datos de las C O M P R A S detallados del anexo (Cabecera)
		 */
		$rs_compras = $obBD_con1->getArrayConsulta(260, $ini . '*' . $fin . '*' . $Ses_Emp_Cod, $obBD_conexion); //$ini.'*'.$fin.
		/**
		 * Apertura del cuerpo 
		 */
		$xml_compras = $xml_compras . "<" . $row_rs_etiquetas_cero['Esq_Xml'] . ">";
		//if($_SESSION['Ses_Prs_Cod']==1) echo count($rs_compras);
		foreach ($rs_compras as $row_rs_compras) {
			unset($valores);
			unset($cod_valores);

			/**
			 * Cargado del primer SubNivel de compras "Detalles" 
			 */
			$rs_etiquetas_det = $obBD_con1->getArrayConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'] . '*' . '8', $obBD_conexion);

			foreach ($rs_etiquetas_det as $row_rs_etiquetas_det) {
				$xml_compras = $xml_compras . "<" . $row_rs_etiquetas_det['Esq_Xml'] . ">";
				/**
				 * Cargado de las etiquetas correspondiente al "Detalle" 
				 */

				$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(386, $row_rs_etiquetas_det['Esq_Cod'] . '*' . '8', $obBD_conexion);
				//var_dump($rs_etiquetas_det_1);

				/**
				 * Asigna en un arreglo las etiquetas de los valores del anexo 
				 */
				foreach ($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1) {
					if ($row_rs_etiquetas_det_1['Esq_Xml'] == "air") {
						$CodAir = $row_rs_etiquetas_det_1['Esq_Cod'];
					}
					$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];
					$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];
				} //Fin del $row_rs_etiquetas_det_1

				$autorizacion = $row_rs_compras['Cop_Aut'];
				///print_r($valores);
				/**
				 * Consulta los porcentaje de iva
				 */
				//$row_rs_iva = $obBD_con1->getRowConsulta(229, '', $obBD_conexion);				
				$row_rs_iva = $obBD_con1->getRowConsulta(876, $ini, $obBD_conexion);
				$estab = $obBD_con1->establecimiento($row_rs_compras['Cop_Num']);

				/**
				 * Cargado de los datos detallados del anexo (Detalle)
				 */
				$rs_compras_det = $obBD_con1->getRowConsulta(230, $row_rs_compras['Cop_Cod'], $obBD_conexion);

				/**
				 * Consulta de facturas que no tienen retencion codigo 332
				 */

				//$row_rs_compras_sin_retenc = $obBD_con1->getRowConsulta(855, $row_rs_compras['Cop_Cod'], $obBD_conexion);
				$row_rs_compras_sin_retenc = $obBD_con1->getArrayConsulta(855, $row_rs_compras['Cop_Cod'], $obBD_conexion);

				//var_dump($row_rs_compras_sin_retenc);

				$cont = 0;

				//if (isset($row_rs_compras_sin_retenc[0]) && is_array($row_rs_compras_sin_retenc[0])) {
				/*if ($row_rs_compras_sin_retenc['Iva_Por'] != "" && $row_rs_compras['Tic_Sri'] != '41') {
						$cont++;
						//$codRetAir[$cont] = "332";
						$baseImpAir[$cont] = $row_rs_compras_sin_retenc['Sub0'] + $row_rs_compras_sin_retenc['Sub12'];
						$porcentajeAir[$cont] = 0;
						$valRetAir[$cont] = 0;
					}*/

				// Procesa múltiples filas
				foreach ($row_rs_compras_sin_retenc as $item_rs_compras_sin_retenc) {
					if ($item_rs_compras_sin_retenc['Iva_Por'] != "" && $row_rs_compras['Tic_Sri'] != '41') {
						$cont++;
						$codRetAir[$cont] = !empty($item_rs_compras_sin_retenc['Ret_Ren_Sri']) ? $item_rs_compras_sin_retenc['Ret_Ren_Sri'] : "332";
						$baseImpAir[$cont] = $item_rs_compras_sin_retenc['Sub0'] + $item_rs_compras_sin_retenc['Sub12'];
						$porcentajeAir[$cont] = 0;
						$valRetAir[$cont] = 0;
					}
				}

				/**
				 * Cargado de los datos detallados del anexo (ICE)
				 */
				$row_rs_compras_ice = $obBD_con1->getRowConsulta(231, $row_rs_compras['Cop_Cod'], $obBD_conexion);
				/**
				 * Inicio de variables 
				 */
				$baseImponible = formato_numero($rs_compras_det['Sub0'], 2, 1);
				$nobIva = formato_numero($rs_compras_det['nobIva'], 2, 1);
				$baseImpGrav = formato_numero($rs_compras_det['Sub12'], 2, 1);
				$montoIva = formato_numero($rs_compras_det['IvaTot'], 2, 1);


				/*foreach($rs_compras_det as $row_rs_compras_det)
			{								
				if ($row_rs_compras_det['Iva_Por'] != 0)//2 es el valor en la tabla del sri
				{	
					$baseImpGrav = formato_numero($row_rs_compras_det['Cop_Imp'],2,1);					
					$montoIva = round($row_rs_compras_det['IvaTot'],2);	
					//echo $baseImpGrav."<br>";		
				}
				else
				{					
					$baseImponible = formato_numero($row_rs_compras_det['Cop_Imp'],2,1);
				}
			}//Fin del $row_rs_compras_det*/

				$valorRetBienes10 = "0.00";
				$valorRetServicios20 = "0.00";
				$valorRetBienes = "0.00";
				$valorRetServicios = "0.00";
				$valRetServ50 = "0.00";
				$valorRetServicios100 = "0.00";

				/**
				 * Cargado para saber si el monto iva es BIEN o SERVICIO 
				 */
				$rs_bien_serv = $obBD_con1->getArrayConsulta(232, $row_rs_compras['Cop_Cod'] . '*' . $Ses_Emp_Cod, $obBD_conexion);
				$total_rs_bien_serv = count($rs_bien_serv);

				if ($total_rs_bien_serv == 0) {
					$valorRetBienes10 = "0.00";
					$valorRetServicios20 = "0.00";
					$valorRetBienes = "0.00";
					$valorRetServicios = "0.00";
					$valRetServ50 = "0.00";
				} else {
					foreach ($rs_bien_serv as $row_rs_bien_serv) {
						/* iva 10%        bienes                       activos fijos                      Gastos    */
						if (($row_rs_bien_serv['Adq_Cod'] == 1 || $row_rs_bien_serv['Adq_Cod'] == 2 || $row_rs_bien_serv['Adq_Cod'] == 13 || $row_rs_bien_serv['Adq_Cod'] == 14) && $row_rs_bien_serv['Ren_Por'] == '10') {
							$valorRetBienes10 = $valorRetBienes10 + round(($row_rs_bien_serv['Ret_Bas'] * $row_rs_bien_serv['Ren_Por']) / 100, 2);
						}
						/* iva 20%       servicio                            Gastos*/
						if (($row_rs_bien_serv['Adq_Cod'] == 3 || $row_rs_bien_serv['Adq_Cod'] == 13) && $row_rs_bien_serv['Ren_Por'] == '20') {
							$valorRetServicios20 = round(($row_rs_bien_serv['Ret_Bas'] * $row_rs_bien_serv['Ren_Por']) / 100, 2);
						}
						/* iva 30%          bienes                 activos fijos                          servicio                           Gastos */
						if (($row_rs_bien_serv['Adq_Cod'] == 1 || $row_rs_bien_serv['Adq_Cod'] == 2 || $row_rs_bien_serv['Adq_Cod'] == 3 || $row_rs_bien_serv['Adq_Cod'] == 13 || $row_rs_bien_serv['Adq_Cod'] == 14) && $row_rs_bien_serv['Ren_Por'] == '30') {
							$valorRetBienes = $valorRetBienes + round(($row_rs_bien_serv['Ret_Bas'] * $row_rs_bien_serv['Ren_Por']) / 100, 2);
						}
						/* iva 50%          bienes                 activos fijos                          servicio                           Gastos */
						if (($row_rs_bien_serv['Adq_Cod'] == 1 || $row_rs_bien_serv['Adq_Cod'] == 2 || $row_rs_bien_serv['Adq_Cod'] == 3 || $row_rs_bien_serv['Adq_Cod'] == 13 || $row_rs_bien_serv['Adq_Cod'] == 14) && $row_rs_bien_serv['Ren_Por'] == '50') {
							$valRetServ50 = $valRetServ50 + round(($row_rs_bien_serv['Ret_Bas'] * $row_rs_bien_serv['Ren_Por']) / 100, 2);
						}
						/* iva 70%          bienes                      servicio                              Gastos                        suministros al parecer */
						if (($row_rs_bien_serv['Adq_Cod'] == 1 || $row_rs_bien_serv['Adq_Cod'] == 3 || $row_rs_bien_serv['Adq_Cod'] == 13 || $row_rs_bien_serv['Adq_Cod'] == 14) && $row_rs_bien_serv['Ren_Por'] == '70') {
							//echo "<br>".$row_rs_compras['Cop_Cod'];                                                          
							$valorRetServicios = round(($row_rs_bien_serv['Ret_Bas'] * $row_rs_bien_serv['Ren_Por']) / 100, 2);
						}
					} //Fin del $row_rs_bien_serv						
				} //Fin del if($total_rs_bien_serv==0)
				/**
				 * Cargado para obtener si el porcentaje de retencion del iva es 100%
				 */
				$row_rs_iva_total = $obBD_con1->getArrayConsulta(854, $row_rs_compras['Cop_Cod'], $obBD_conexion);
				$total_rs_iva_total = count($row_rs_iva_total);

				if ($total_rs_iva_total == 1) {
					$valorRetServicios100 = round(($row_rs_iva_total[0]['Ret_Bas'] * $row_rs_iva_total[0]['Ren_Por']) / 100, 2);
				} else {
					$valorRetServicios100 = "0.00";
				}

				/**
				 * Cargado del primer SubNivel del  "air" para las Compras  
				 */
				$row_rs_etiquetas_air = $obBD_con1->getRowConsulta(227, $CodAir . '*' . '8', $obBD_conexion);

				/**
				 * Cargado de las etiquetas correspondiente al "air" para las Compras   
				 */
				$rs_etiquetas_air_1 = $obBD_con1->getArrayConsulta(386, $row_rs_etiquetas_air['Esq_Cod'] . '*' . '8', $obBD_conexion);
				/**
				 * Asigna en un arreglo las etiquetas de los valores del air 
				 */
				unset($valores_air);
				foreach ($rs_etiquetas_air_1 as $row_rs_etiquetas_air_1) {
					$det_air[] = $row_rs_etiquetas_air_1['Esq_Xml'];
					$cod_air[] = $row_rs_etiquetas_air_1['Esq_Cod'];
				} //Fin del $row_rs_etiquetas_air_1

				/**
				 * Consulta se posee reembolsos
				 */
				$row_rs_reemb = $obBD_con1->getArrayConsulta(890, $row_rs_compras['Cop_Cod'], $obBD_conexion);

				/**
				 * Consulta de los valores AIR (solo renta)
				 */
				$row_rs_datos_air = $obBD_con1->getArrayConsulta(233, $row_rs_compras['Cop_Cod'] . '*' . 'R', $obBD_conexion);
				$total_rs_datos_air = count($row_rs_datos_air);
				if ($total_rs_datos_air == 0) {
					$CanCajas = 0;
					$ValCajas = 0;
				}
				/** 
				 * Fecha de registro de la factura
				 */
				if ($total_rs_datos_air > 0) {
					/**
					 * Fecha de registro compra
					 */
					$Fecha_Reg = $row_rs_compras['Cop_Fec'];

					$CanCajas = $row_rs_datos_air[0]['Ret_Uca']; //Unidad de caja de banano para codigo 338
					$ValCajas = $row_rs_datos_air[0]['Ret_Pca']; //Precio de caja de banano para codigo 338
					$AutTem = $row_rs_datos_air[0]['Aut_Tem'];   // Tipo de Emision de la Autorizacion N=normal  E=electronica
					$FecRetEmi = date('d/m/Y', strtotime(str_replace('/', '-', $row_rs_datos_air[0]['Ret_Fec']))); // fecha de Retencion										
					if ($row_rs_configEmpresa['Cof_Gce'] == 'N') {
						$AutEmiRet = $row_rs_datos_air[0]['Aut_Sri'];
					} else {
						if ($AutTem == 'N') {
							$AutEmiRet = $row_rs_datos_air[0]['Aut_Sri'];
						} else {
							$AutEmiRet = $row_rs_datos_air[0]['Ret_Sri'];
						}
					}
				} else {
					/**
					 * Fecha de la factura
					 */
					$Fecha_Reg = $row_rs_compras['Cop_Fec'];
				} //Fin del else if ($total_rs_datos_air > 0)

				//	var_dump($row_rs_compras);
				//CONDICIONALES

				$xml_condicional_tipoProv = "";
				$xml_condicional_denoProv = "";

				if ($row_rs_compras['Ide_Prc'] == '03') { //Solo cuando es pasaporte o documento del exterior
					$tipo_contribuyente =  $row_rs_compras['Prv_Tic'] == 'N' ? '01' :  '02'; //N = 01  - J=02
					$denominacion_social = !empty($row_rs_compras['Prv_Com']) ? $row_rs_compras['Prv_Com'] : $row_rs_compras['Prv_Tic'];
					$xml_condicional_tipoProv = "<" . $valores[42] . ">" . $tipo_contribuyente . "</" . $valores[42] . ">";
					$xml_condicional_denoProv = "<denoProv>" . $denominacion_social . "</denoProv>";
				}


				$xml_compras = $xml_compras .															/* ETIQUETAS COMPRAS DEL XML*/
					"<" . $valores[0] . ">" . $row_rs_compras['Tri_Sri'] . "</" . $valores[0] . ">" .						// codSustento		
					"<" . $valores[1] . ">" . $row_rs_compras['Ide_Prc'] . "</" . $valores[1] . ">" . 						// tpIdProv
					"<" . $valores[2] . ">" . $row_rs_compras['Prs_Ced'] . "</" . $valores[2] . ">" .   						// idProv
					"<" . $valores[3] . ">" . str_pad($row_rs_compras['Tic_Sri'], 2, "0", STR_PAD_LEFT) . "</" . $valores[3] . ">" .	// tipoComprobante
					$xml_condicional_tipoProv .  //tipoPrv  ::condicional
					$xml_condicional_denoProv . //denoProv   ::condicional
					"<" . $valores[36] . ">NO</" . $valores[36] . ">" .   						// parteRel
					"<" . $valores[4] . ">" . date("d/m/Y", strtotime($Fecha_Reg)) . "</" . $valores[4] . ">" . 				// fechaRegistro
					"<" . $valores[5] . ">" . $estab[0] . "</" . $valores[5] . ">" .			    							// establecimiento
					"<" . $valores[6] . ">" . $estab[1] . "</" . $valores[6] . ">" .  		    							// puntoEmision
					"<" . $valores[7] . ">" . str_pad($estab[2], 9, "0", STR_PAD_LEFT) . "</" . $valores[7] . ">" .      	 // secuencial
					"<" . $valores[8] . ">" . date("d/m/Y", strtotime($row_rs_compras['Cop_Fec'])) . "</" . $valores[8] . ">" . // fechaEmision
					"<" . $valores[9] . ">" . $autorizacion . "</" . $valores[9] . ">" .										// autorizacion
					"<" . $valores[10] . ">" .$nobIva. "</" . $valores[10] . ">" .											// baseNoGraIva
					"<" . $valores[11] . ">" . $baseImponible . "</" . $valores[11] . ">" .									// baseImponible
					"<" . $valores[12] . ">" . $baseImpGrav . "</" . $valores[12] . ">" .									// baseImpGrav			
					"<" . $valores[37] . ">0.00</" . $valores[37] . ">" .									            // baseImpExe			
					"<" . $valores[13] . ">" . formato_numero($row_rs_compras_ice['Mon_Ice'], 2, 1) . "</" . $valores[13] . ">" .				// montoIce
					"<" . $valores[14] . ">" . formato_numero($montoIva, 2, 1) . "</" . $valores[14] . ">" .									// montoIva

					"<" . $valores[38] . ">" . formato_numero($valorRetBienes10, 2, 1) . "</" . $valores[38] . ">" .							// iva                iva 10%
					"<" . $valores[39] . ">" . formato_numero($valorRetServicios20, 2, 1) . "</" . $valores[39] . ">" .						// iva                iva 20%	

					"<" . $valores[15] . ">" . formato_numero($valorRetBienes, 2, 1) . "</" . $valores[15] . ">";								// valorRetBienes     iva 30%
				if ($ini >= '2016-01') {
					$xml_compras = $xml_compras . "<" . $valores[41] . ">" . formato_numero($valRetServ50, 2, 1) . "</" . $valores[41] . ">"; // valorRetServicios  iva 50%
				}
				$xml_compras = $xml_compras . "<" . $valores[16] . ">" . formato_numero($valorRetServicios, 2, 1) . "</" . $valores[16] . ">" . // valorRetServicios  iva 70%
					"<" . $valores[17] . ">" . formato_numero($valorRetServicios100, 2, 1) . "</" . $valores[17] . ">" .						// valorRetServ100	  iva 100%	
					"<" . $valores[40] . ">" . formato_numero($row_rs_reemb[0]['tot'], 2, 1) . "</" . $valores[40] . ">" .																// totbasesImpReemb							
					"<" . $valores[18] . ">";																						// pagoExterior
				$rs_pagoExterior = $obBD_con1->getArrayConsulta(386, $cod_valores[18] . '*' . '8', $obBD_conexion);
				foreach ($rs_pagoExterior as $rowExt) {
					$pagExt_Eti[] = $rowExt['Esq_Xml'];
					$pagExt_Cod[] = $rowExt['Esq_Cod'];
				}
				$xml_compras = $xml_compras .
					"<" . $pagExt_Eti[0] . ">01</" . $pagExt_Eti[0] . ">" .						//pagoLocExt
					"<" . $pagExt_Eti[1] . ">NA</" . $pagExt_Eti[1] . ">" .						//paisEfecPago
					"<" . $pagExt_Eti[2] . ">NA</" . $pagExt_Eti[2] . ">" .						//aplicConvDobTrib
					"<" . $pagExt_Eti[3] . ">NA</" . $pagExt_Eti[3] . ">" .						//pagExtSujRetNorLeg
					"<" . $pagExt_Eti[4] . ">NA</" . $pagExt_Eti[4] . ">";						//pagoRegFis																	
				$xml_compras = $xml_compras . "</" . $valores[18] . ">";

				//if (($baseImponible + $baseImpGrav + $montoIva) >= 1000 && $row_rs_compras['Tic_Sri'] != "4") {
				if (($baseImponible + $baseImpGrav + $montoIva) >= 500 && $row_rs_compras['Tic_Sri'] != "4") {
					//echo str_pad($estab[2], 9, "0", STR_PAD_LEFT)."---".$row_rs_compras['Tpc_Cod']."<br>";
					$xml_compras = $xml_compras . "<" . $valores[19] . ">";														// formasDePago
					$rs_ForPagoCom = $obBD_con1->getArrayConsulta(386, $cod_valores[19] . '*' . '8', $obBD_conexion);
					$rs_ForPagoComSri = $obBD_con1->getRowConsulta(856, $row_rs_compras['Tpc_Cod'], $obBD_conexion);
					foreach ($rs_ForPagoCom as $rowFpc) {
						$Fpc_Eti[] = $rowFpc['Esq_Xml'];
						$Fpc_Cod[] = $rowFpc['Esq_Cod'];
					}
					$xml_compras = $xml_compras . "<" . $Fpc_Eti[0] . ">" . $rs_ForPagoComSri['Tpc_Sri'] . "</" . $Fpc_Eti[0] . ">"; //formaPago					
					$xml_compras = $xml_compras . "</" . $valores[19] . ">";
				}

				if ($row_rs_compras['Tic_Sri'] != '4') /*control para evitar las etiquetas d retencion a las notas de credito */ {
					$xml_compras = $xml_compras . "<" . $valores[20] . ">"; // <air>

					// AIR
					/**
					 * Control para factura que sustentan credito tributario 
					 */
					$flag_control338 = 0;
					if (/*$row_rs_compras['Tri_Sri'] != "00" &&*/$total_rs_datos_air > 0) {
						foreach ($row_rs_datos_air as $row) {
							if ($row['Ren_Sri'] == '338') {
								if ($flag_control338 == '0') {
									$cont++;
									$rs_ret338 = $obBD_con1->getRowConsulta(881, $row['Ret_Cod'] . '*' . $row['Ren_Sri'], $obBD_conexion);
									$codRetAir[$cont] = $rs_ret338['Ren_Sri'];
									$baseImpAir[$cont] = $rs_ret338['Ret_Bas'];
									$porcentajeAir[$cont] = $rs_ret338['Ren_Por'];
									$valRetAir[$cont] = $rs_ret338['Val_Air'];
									$flag_control338 = 1;
								}
							} else {
								$cont++;
								//$codRetAir[$cont] = $obBD_con1->cod_air($row['Ren_Sri']);							
								$codRetAir[$cont] = $row['Ren_Sri'];
								//$baseImpAir[$cont] = $row['Ret_Bas'];
								//$porcentajeAir[$cont] = $row['Ren_Por'];
								if ($row['Ren_Sri'] == '322' && $row['Ren_Por'] * 1 == 0.1) {
									$baseImpAir[$cont] = ($row['Ret_Bas'] * 10) / 100;
									$porcentajeAir[$cont] = 1; //$row['Ren_Por'];
								} else {
									$baseImpAir[$cont] = $row['Ret_Bas'];
									$porcentajeAir[$cont] = $row['Ren_Por'];
								}
								$valRetAir[$cont] = $row['Val_Air'];
							}
						} //Fin del $row_rs_datos_air 				
					}
					/**
					 * Detalle air
					 * ETIQUETAS AIR
					 */
					for ($i = 1; $i <= $cont; $i++) {
						$xml_compras = $xml_compras . "<" . $row_rs_etiquetas_air['Esq_Xml'] . ">" .
							"<" . $det_air[0] . ">" . $codRetAir[$i] . "</" . $det_air[0] . ">" .							// codRetAir
							"<" . $det_air[1] . ">" . formato_numero($baseImpAir[$i], 2, 1) . "</" . $det_air[1] . ">" .		// baseImpAir
							"<" . $det_air[2] . ">" . formato_numero($porcentajeAir[$i], 2, 1) . "</" . $det_air[2] . ">" .	// porcentajeAir
							"<" . $det_air[3] . ">" . formato_numero($valRetAir[$i], 2, 1) . "</" . $det_air[3] . ">";        // valRetAir
						if ($codRetAir[$i] == '338') {															//solo para retenciones con codigo 338
							$xml_compras = $xml_compras . "<numCajBan>" . $CanCajas . "</numCajBan><precCajBan>" . formato_numero($ValCajas, 2, 1) . "</precCajBan>";
						}
						$xml_compras = $xml_compras . "</" . $row_rs_etiquetas_air['Esq_Xml'] . ">";
					} // fin del for

					$xml_compras = $xml_compras . "</" . $valores[20] . ">"; //</air>
				}

				/**
				 * Continuacion de AIR 
				 * Control para factura que sustentan credito tributario 
				 */
				if ($row_rs_compras['Tri_Sri'] != "00" && $total_rs_datos_air > 0) {
					unset($estabRet);
					unset($ptoEmiRet);
					unset($secRet);
					unset($autRet);
					unset($fechaEmiRet);
					foreach ($row_rs_datos_air as $row) {
						/**
						 * Controla que haya valores en las fechas 
						 */
						$fecha = "";
						if ($row['Ret_Fec'] != "") {
							$fecha = date("d/m/Y", strtotime($row['Ret_Fec']));
						} //Fin del if ($row_rs_datos_air['Ret_Fec'] != "")

						/**
						 * Asignacion del establecimiento
						 */
						$estab = $obBD_con1->establecimiento($row['Ret_Num']);

						$estabRet = $row['Suc_Sri'];
						$ptoEmiRet = $row['Pun_Sri'];
						$secRet[] = isset($estab[2]) ? $estab[2] : '0';
					} //Fin del $row_rs_datos_air						
				} else // Control para factura que NO sustentan credito tributario 
				{
					unset($estabRet);
					unset($ptoEmiRet);
					unset($secRet);
					unset($autRet);
					unset($fechaEmiRet);
					foreach ($row_rs_datos_air as $row) {
						/**
						 * asignacion del establecimiento
						 */
						$estabRet[] = "000";
						$ptoEmiRet[] = "000";
						$secRet[] = "0";
						$autRet[] = "000";
						$fechaEmiRet[] = "00/00/0000";
					} //Fin del $row_rs_datos_air
				} //Fin del if ($row_rs_compras['Tri_Sri'] != 1)						



				if (count($row_rs_datos_air) != 0) {
					if (floatval($secRet[0]) > 0) { //Si la secuencia es diferente de cero ingresa

						/*$xml_compras = $xml_compras . "<" . $valores[21] . ">" . $estabRet . "</" . $valores[21] . ">" .	// estabRetencion1
							"<" . $valores[22] . ">" . $ptoEmiRet . "</" . $valores[22] . ">" .							// ptoEmiRetencion1			
							"<" . $valores[23] . ">" . str_pad($secRet[0], 9, "0", STR_PAD_LEFT) . "</" . $valores[23] . ">" . // secRetencion1 str_pad-> completa con ceros hasta 9
							"<" . $valores[24] . ">" . $AutEmiRet . "</" . $valores[24] . ">" .	    						// autRetencion1
							"<" . $valores[25] . ">" . $FecRetEmi . "</" . $valores[25] . ">";    						    // fechaEmiRet1				
					*/

						$xml_compras = $xml_compras .
							"<" . $valores[21] . ">" . $estabRet . "</" . $valores[21] . ">" . // estabRetencion1
							"<" . $valores[22] . ">" . $ptoEmiRet . "</" . $valores[22] . ">" . // ptoEmiRetencion1
							"<" . $valores[23] . ">" . str_pad($secRet[0], 9, "0", STR_PAD_LEFT) . "</" . $valores[23] . ">"; // secRetencion1

						if (!empty($AutEmiRet)) {
							$xml_compras .= "<" . $valores[24] . ">" . $AutEmiRet . "</" . $valores[24] . ">"; // autRetencion1
						}
						$xml_compras .= "<" . $valores[25] . ">" . $FecRetEmi . "</" . $valores[25] . ">"; // fechaEmiRet1

					}
				}


				if ($row_rs_compras['Tic_Sri'] == '41') /* solo para Reembolsos */ {
					$xml_compras = $xml_compras . "<reembolsos>";
					foreach ($row_rs_reemb as $row) {
						$arrdato = explode("-", $row['Rem_Num']);
						echo date_format($row['Rem_Fec'], "d/m/Y");
						$xml_compras = $xml_compras . "<reembolso>
								<tipoComprobanteReemb>" . $row['Rem_Tic'] . "</tipoComprobanteReemb>
								<tpIdProvReemb>" . $row['Rem_Ide'] . "</tpIdProvReemb>
								<idProvReemb>" . $row['Rem_Ced'] . "</idProvReemb>
								<establecimientoReemb>" . $arrdato[0] . "</establecimientoReemb>
								<puntoEmisionReemb>" . $arrdato[1] . "</puntoEmisionReemb>
								<secuencialReemb>" . $arrdato[2] . "</secuencialReemb>
								<fechaEmisionReemb>" . date("d/m/Y", strtotime($row['Rem_Fec'])) . "</fechaEmisionReemb>
								<autorizacionReemb>" . $row['Rem_Aut'] . "</autorizacionReemb>
								<baseImponibleReemb>" . $row['Rem_Niv'] . "</baseImponibleReemb>
								<baseImpGravReemb>" . $row['Rem_Siv'] . "</baseImpGravReemb>
								<baseNoGraIvaReemb>" . $row['Rem_Oiv'] . "</baseNoGraIvaReemb>
								<baseImpExeReemb>" . $row['Rem_Eiv'] . "</baseImpExeReemb>
								<montoIceRemb>" . $row['Rem_Ice'] . "</montoIceRemb>
								<montoIvaRemb>" . $row['Rem_Iva'] . "</montoIvaRemb>
						</reembolso>";
					}
					$xml_compras = $xml_compras . "</reembolsos>";
				}
				if ($row_rs_compras['Tic_Sri'] == '4' || $row_rs_compras['Tic_Sri'] == '5') /* solo para notas de credito y notas de debito */ {
					$tipDocMod = $row_rs_compras['Cop_Ntd'];
					$arrDatNum = explode('-', $row_rs_compras['Cop_Nns']);
					$xml_compras = $xml_compras . "<" . $valores[31] . ">" . str_pad($tipDocMod, 2, "0", STR_PAD_LEFT) . "</" . $valores[31] . ">" . // docModificado
						"<" . $valores[32] . ">" . $arrDatNum[0] . "</" . $valores[32] . ">" .							// estabModificado
						"<" . $valores[33] . ">" . $arrDatNum[1] . "</" . $valores[33] . ">" .							// ptoEmiModificado
						"<" . $valores[34] . ">" . str_pad($arrDatNum[2], 9, "0", STR_PAD_LEFT) . "</" . $valores[34] . ">" . // secModificado
						"<" . $valores[35] . ">" . trim($row_rs_compras['Cop_Nna']) . "</" . $valores[35] . ">";				// autModificado        																			
				}
				$xml_compras = $xml_compras . "</" . $row_rs_etiquetas_det['Esq_Xml'] . ">";
			} //Fin del $row_rs_etiquetas_det
			unset($valores);
		} //Fin de compras
		$xml_compras = $xml_compras . "</" . $row_rs_etiquetas_cero['Esq_Xml'] . ">";
	} // FIN IF if(isset($chk_Compras))		


	/*========================================*/
	/*    V E N T A S    D E L     X M L      */
	/*========================================*/
	if (isset($_POST['chk_Ventas']) > 0 && $sinVentas == '0') {
		$valAuxVen = 1;

		/**
		 * Cargado de etiquetas de nivel cero 
		 */
		$row_rs_etiquetas_cero = $obBD_con1->getRowConsulta(234, '0' . '*' . '8' . '*' . 'V', $obBD_conexion);

		/**
		 * Cargado del primer SubNivel de ventas "Detalles" 
		 */
		$row_rs_etiquetas_det = $obBD_con1->getRowConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'] . '*' . '8', $obBD_conexion);

		/**
		 * Cargado de las etiquetas correspondiente al "Detalle" 
		 */
		$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(386, $row_rs_etiquetas_det['Esq_Cod'] . '*' . '8', $obBD_conexion);

		/**
		 * Consultando los CLIENTES q se emitio Ventas en un determinado MES para el Anexo Transaccional 
		 */
		$rs_ventas = $obBD_con1->getArrayConsulta(387, "A" . '*' . $ini . '*' . $fin . '*' . $Ses_Emp_Cod, $obBD_conexion);

		/**
		 *	Consultamos si emitimos facturas electronicas
		 */
		//$rs_EmiFacElec=$obBD_con1->getRowConsulta(863, $Ses_Emp_Cod, $obBD_conexion);		



		/**
		 * Asigna en un arreglo las etiquetas de los valores del anexo 
		 */
		foreach ($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1) {
			$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];
			$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];
		} //Fin del $row_rs_etiquetas_det_1

		/**
		 * Cabecera de la venta 
		 */
		$xml_ventas = $xml_ventas . "<" . $row_rs_etiquetas_cero['Esq_Xml'] . ">";

		foreach ($rs_ventas as $row_rs_ventas) {
			cleanEspecialChar($row_rs_ventas);
			$NumFact = 0;
			/**
			 * Consulta la cabecera de Facturas 
			 */
			$row_rs_cabecera = $obBD_con1->getArrayConsulta(388, $row_rs_ventas['Cli_Cod'] . '*' . $ini . '*' . $fin, $obBD_conexion);
			foreach ($row_rs_cabecera as $cabDatos) { //echo $cabDatos['Aut_Tem'];
				if ($cabDatos['Aut_Tem'] == 'E') {
					$tipEmiVenta = 'E';
				} else {
					$tipEmiVenta = 'F';
				}

				//$row_rs_Comprobantes = $obBD_con1->getArrayConsulta(861, $row_rs_ventas['Cli_Cod'].'*'.$ini.'*'.$fin.'*'.$cabDatos['Tic_Cod'], $obBD_conexion);
				/**
				 * Variable q contiene el numero de Facturas encontradas, ese valor va directo al XML
				 */
				$NumFact = $cabDatos['total'];
				/**
				 * Consulta la suma total de las facturas
				 */



				$rs_detalle = $obBD_con1->getArrayConsulta(389, $ini . '*' . $fin . '*' . $row_rs_ventas['Cli_Cod'] . '*' . $cabDatos['Tic_Cod'] . '*' . $cabDatos['Aut_Tem'] . '*' . $cabDatos['TicSri'], $obBD_conexion);



				$Total_rs_detalle = count($rs_detalle);
				/**
				 * Variable q contendran las sumas de los facturas de ventas
				 */
				$valor_baseImpGrav = "0";
				$valor_montoIva = "0";
				$valor_baseImponible = "0";
				$valorNograbaIva = 0;
				$valorRenta = 0;
				$valorIva = 0;

				/**
				 *  Consultamos los diferentes tipos de pagos SRI
				 */
				$auxTpcSri = 0;
				$DatostipPagoVent = "";
				$rs_TipoPagoVentas = $obBD_con1->getArrayConsulta(879, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . '*' . $row_rs_ventas['Prs_Ced'] . '*' . $cabDatos['Tic_Cod'], $obBD_conexion);
				foreach ($rs_TipoPagoVentas as $rowTipPagVet) {
					if ($ini >= '2016-06-01' && $rowTipPagVet['Tpc_Sri'] == '01' && $auxTpcSri == 0) {
						$DatostipPagoVent = $DatostipPagoVent . "<formaPago>" . $rowTipPagVet['Tpc_Sri'] . "</formaPago>";
						$auxTpcSri = 1;
					} else {
						if ($ini >= '2016-06-01' && $rowTipPagVet['Tpc_Sri'] == '' && $auxTpcSri == 0) {
							$DatostipPagoVent = $DatostipPagoVent . "<formaPago>01</formaPago>";
							$auxTpcSri = 1;
						} elseif ($ini >= '2016-06-01' && $rowTipPagVet['Tpc_Sri'] != '01') {
							$DatostipPagoVent = $DatostipPagoVent . "<formaPago>" . $rowTipPagVet['Tpc_Sri'] . "</formaPago>";
						}
					}
				}

				/**
				 * Consultando todas las ventas segun la cedula y fecha y empresa del cliente
				 */
				$rs_TodasVentas = $obBD_con1->getArrayConsulta(857, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . '*' . $row_rs_ventas['Prs_Ced'] . '*' . $cabDatos['Tic_Cod'] . '*' . $cabDatos['Aut_Tem'], $obBD_conexion);
				//echo count($row_datos['Vet_Cod']);
				foreach ($rs_TodasVentas as $row_datos) {
					/*Consultamos los Items detalles de ventas*/
					$rs_DetalleVentas = $obBD_con1->getArrayConsulta(858, $row_datos['Vet_Cod'], $obBD_conexion);

					foreach ($rs_DetalleVentas as $row_Detdatos) {
						if ($row_Detdatos['Ren_Cod'] != '') {
							/*Consultamos el porcentaje de la renta*/
							$rs_rentaPor = $obBD_con1->getRowConsulta(864, $row_Detdatos['Ren_Cod'], $obBD_conexion);
							$valorRenta = $valorRenta + (($row_Detdatos['Tot_Imp'] * $rs_rentaPor['Ren_Por']) / 100);
						}
						if ($row_Detdatos['Ren_Iva'] != '') {
							/*Consultamos el porcentaje de la iva*/
							$rs_ivaPor = $obBD_con1->getRowConsulta(864, $row_Detdatos['Ren_Iva'], $obBD_conexion);
							$valorIva = $valorIva + (($row_Detdatos['Tot_Iva'] * $rs_ivaPor['Ren_Por']) / 100);
						}
					}
				}

				if (/*$Total_rs_detalle == 1*/true) // inicio IF $Total_rs_detalle
				{
					foreach ($rs_detalle as $row_rs_detalle) {
						if ($row_rs_detalle['Iva_Por'] > 0) // IF para sumar los Totales de las facturas ya sean con Iva mayor de "0" o menor de "0"
						{
							$valor_baseImpGrav += round($row_rs_detalle['Total'], 2); //Contiene la suma d Totales de facturas con iva > 0
							$valor_montoIva += round($row_rs_detalle['Iva'], 2); //Contiene la suma d todos los montos de Iva d cada factura
						} else {
							if($row_rs_detalle['NgIva']>0)
								$valorNograbaIva+=round($row_rs_detalle['NgIva'], 2);
							else	
								$valor_baseImponible += round($row_rs_detalle['Total'], 2); //Contiene la suma d Totales d facturas con iva < 0						
						}
					} //Fin del $row_rs_detalle
				} else {
					echo error_alerta("<< Alerta >> <br>Descripción: </br>
        No se ha podido generar el XML Anexo Transaccional, se ha detectado que existen varios registros de un cliente aplicando distintos porcentajes de Iva, <br>provocando que los datos del <strong>XML-Ventas</strong> en la etiqueta <strong>montoIva</strong> esten incorrectos...!", 2);
					exit();
				}	// fin IF $Total_rs_detalle


				/*control para poner tipo de comprobante en 18 si la venta es d tipo Factura*/
				if ($cabDatos['TicSri'] == '01') {
					$tipDocVen = '18';
				} else {
					$tipDocVen = $cabDatos['TicSri'];
				}

				/**
				 * Control para asignar 9999999999, cuando la cedula sea cero
				 */
				if ($row_rs_ventas['Prs_Ced'] == "0") {
					$cedula = "9999999999999";
				} else {
					$cedula = $row_rs_ventas['Prs_Ced'];
				}

				/* asignamos el tipo de identificacion del cliente */
				if ($row_rs_ventas['Ide_Prv'] != '4' && $row_rs_ventas['Ide_Prv'] != '5' && $row_rs_ventas['Ide_Prv'] != '7') {
					$tipIdeCliente = '6';
					/*Cuando el cliente es extranjero la cedula debe contener minimo 3 digitos con la fucion STR_PAD rellenamos con ceros*/
					$cedula = str_pad($cedula, 3, "0", STR_PAD_LEFT);
				} else {
					$tipIdeCliente = $row_rs_ventas['Ide_Prv'];
				}

				$xml_ventas = $xml_ventas . "<" . $row_rs_etiquetas_det['Esq_Xml'] . ">";
				$xml_ventas = $xml_ventas .									     				   /* ETIQUETAS VENTAS DEL XML*/
					"<" . $valores[0] . ">" . str_pad($tipIdeCliente, 2, "0", STR_PAD_LEFT) . "</" . $valores[0] . ">" .	// tpIdCliente					
					"<" . $valores[1] . ">" . $cedula . "</" . $valores[1] . ">";  						   		   // idCliente
				if ($tipIdeCliente != '7')/*esta etiqueta no debe ir a consumidor final*/ {
					$xml_ventas = $xml_ventas . "<" . $valores[10] . ">NO</" . $valores[10] . ">";		   // parteRelVtas
				}
				if ($tipIdeCliente == '6' && $ini >= '2016-05-01') { /*etiquetas solo para clientes con pasaporte*/
					$xml_ventas = $xml_ventas . "<tipoCliente>" . $row_rs_ventas['CliTic'] . "</tipoCliente>";
					$xml_ventas = $xml_ventas . "<denoCli>" . trim($row_rs_ventas['Prs_Ape']) . " " . $row_rs_ventas['Prs_Nom'] . "</denoCli>";
				}
				$xml_ventas = $xml_ventas . "<" . $valores[2] . ">" . str_pad($tipDocVen, 2, "0", STR_PAD_LEFT) . "</" . $valores[2] . ">" . // tipoComprobante $row_rs_cabecera['Tic_Sri']
					"<tipoEmision>" . $tipEmiVenta . "</tipoEmision>" .									   // tipoEmision 	
					"<" . $valores[3] . ">" . $NumFact . "</" . $valores[3] . ">" .								   // numeroComprobantes
					"<" . $valores[4] . ">" . $valorNograbaIva . "</" . $valores[4] . ">" .      		 					  // baseNoGraIva - valor en "0" UTSAM no le pueden retenerle iva
					"<" . $valores[5] . ">" . formato_numero($valor_baseImponible, 2, 1) . "</" . $valores[5] . ">" . // baseImponible
					"<" . $valores[6] . ">" . formato_numero($valor_baseImpGrav, 2, 1) . "</" . $valores[6] . ">" .   // baseImpGrav
					"<" . $valores[7] . ">" . formato_numero($valor_montoIva, 2, 1) . "</" . $valores[7] . ">";      // montoIva
				if ($ini >= '2015-03-01') {
					$xml_ventas = $xml_ventas . "<montoIce>0.00</montoIce>";
				}   // montoICE
				$xml_ventas = $xml_ventas . "<" . $valores[8] . ">" . formato_numero($valorIva, 2, 1) . "</" . $valores[8] . ">" .      	   // valorRetIva 
					"<" . $valores[9] . ">" . formato_numero($valorRenta, 2, 1) . "</" . $valores[9] . ">";		   // valorRetRenta 
				if ($ini >= '2016-06-01' && $cabDatos['TicSri'] != '04') {
					$xml_ventas = $xml_ventas . "<formasDePago>" . $DatostipPagoVent . "</formasDePago>";
				}
				/**
				 * Cierre del detalle de la venta 
				 */
				$xml_ventas = $xml_ventas . "</" . $row_rs_etiquetas_det['Esq_Xml'] . ">";
			}
		} //Fin del $row_rs_ventas
		unset($valores); // Vacio el Arreglo $valores[]

		/*RETENCIONES BANCARIAS EN VENTAS*/
		$rs_retenBancarias = $obBD_con1->getArrayConsulta(886, $Ses_Emp_Cod . '*' . $ini . '*' . $fin, $obBD_conexion);
		foreach ($rs_retenBancarias as $row_retDatos) {
			$rs_retenBancPagos = $obBD_con1->getArrayConsulta(887, $Ses_Emp_Cod . '*' . $ini . '*' . $fin . '*' . $row_retDatos['Cli_Cod'], $obBD_conexion);
			$xml_ventas = $xml_ventas . "<detalleVentas>" .
				"<tpIdCliente>" . $row_retDatos['Ide_Prv'] . "</tpIdCliente>" .
				"<idCliente>" . $row_retDatos['Prs_Ced'] . "</idCliente>" .
				"<parteRelVtas>NO</parteRelVtas>" .
				"<tipoComprobante>18</tipoComprobante>" .
				"<tipoEmision>" . $row_retDatos['Rvt_Tem'] . "</tipoEmision>" .
				"<numeroComprobantes>" . $row_retDatos['regtot'] . "</numeroComprobantes>" .
				"<baseNoGraIva>0.00</baseNoGraIva>" .
				"<baseImponible>0.00</baseImponible>" .
				"<baseImpGrav>0.00</baseImpGrav>" .
				"<montoIva>0.00</montoIva>" .
				"<montoIce>0.00</montoIce>" .
				"<valorRetIva>" . formato_numero($row_retDatos['ivaTotal'], 2, 1) . "</valorRetIva>" .
				"<valorRetRenta>" . formato_numero($row_retDatos['renTotal'], 2, 1) . "</valorRetRenta>" .
				"<formasDePago>";
			foreach ($rs_retenBancPagos as $row_retDatosPag) {
				$xml_ventas = $xml_ventas . "<formaPago>" . $row_retDatosPag['Tpc_Sri'] . "</formaPago>";
			}
			$xml_ventas = $xml_ventas . "</formasDePago></detalleVentas>";
		};

		/**
		 * Cierre de la cabecera de la venta 
		 */
		$xml_ventas = $xml_ventas . "</" . $row_rs_etiquetas_cero['Esq_Xml'] . ">";





		/*=========================================================================*/
		/*    V E N T A S    E S T A B L E C I M I E N T O    D E L     X M L      */
		/*=========================================================================*/

		/**
		 * Cargado de etiquetas de nivel cero 
		 */
		$row_rs_etiquetas_cero = $obBD_con1->getRowConsulta(234, '0' . '*' . '8' . '*' . 'S', $obBD_conexion);

		/**
		 * Cargado del primer SubNivel de ventas "Detalles" 
		 */
		$row_rs_etiquetas_det = $obBD_con1->getRowConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'] . '*' . '8', $obBD_conexion);

		/**
		 * Cargado de las etiquetas correspondiente al "Detalle" 
		 */
		$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(386, $row_rs_etiquetas_det['Esq_Cod'] . '*' . '8', $obBD_conexion);


		/**
		 * Asigna en un arreglo las etiquetas de los valores del anexo 
		 */
		foreach ($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1) {
			$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];
			$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];
		} //Fin del $row_rs_etiquetas_det_1

		/**
		 * Cabecera de la venta 
		 */
		$xml_ventasEst = $xml_ventasEst . "<" . $row_rs_etiquetas_cero['Esq_Xml'] . ">";


		foreach ($rs_establecimiento as $row_rs_establecimiento) {
			$xml_ventasEst = $xml_ventasEst . "<" . $row_rs_etiquetas_det['Esq_Xml'] . ">";
			$xml_ventasEst = $xml_ventasEst .									     		   /* ETIQUETAS VENTAS ESTABLECIMIENTO DEL XML*/
				"<" . $valores[0] . ">" . $row_rs_establecimiento['Suc_Sri'] . "</" . $valores[0] . ">" .	   // codEstab		
				"<" . $valores[1] . ">" . formato_numero($row_rs_establecimiento['Total'], 2, 1)/*formato_numero($tot_ventas,2,1)*/ . "</" . $valores[1] . ">";  	   // ventasEstab			
			//formato_numero($row_rs_establecimiento['Total']
			/**
			 * Cierre del detalle de la venta 
			 */
			$xml_ventasEst = $xml_ventasEst . "</" . $row_rs_etiquetas_det['Esq_Xml'] . ">";
		} //Fin del $row_rs_ventas
		unset($valores); // Vacio el Arreglo $valores[]
		/**
		 * Cierre de la cabecera de la venta 
		 */
		$xml_ventasEst = $xml_ventasEst . "</" . $row_rs_etiquetas_cero['Esq_Xml'] . ">";
	} //FIN IF if(isset($chk_Ventas))	 

	/*==================================================*/
	/*   E X P O R T A C I O N E S   D E L    X M L     */
	/*==================================================*/
	/**
	 * Consultamos todas las Exportaciones dentro del MES 
	 */
	$rs_exportacion = $obBD_con1->getArrayConsulta(885, $Ses_Emp_Cod . '*' . $ini . '*' . $fin, $obBD_conexion);
	if (isset($_POST['chk_Export']) && $rs_exportacion[0]['Vet_Cod'] <> 0) {
		$valAuxExp = 1;
		$xmlExportacion = "<exportaciones>";

		foreach ($rs_exportacion as $datos) {
			$xmlExportacion = $xmlExportacion . "<detalleExportaciones>" .
				"<tpIdClienteEx>" . $datos['Ide_Pre'] . "</tpIdClienteEx>" .
				"<idClienteEx>" . $datos['Prs_Ced'] . "</idClienteEx>" .
				"<parteRelExp>" . $datos['EveRel'] . "</parteRelExp>";

			if ($datos['Ide_Pre'] == 21) {
				$xmlExportacion = $xmlExportacion . "<tipoCli>" . $datos['Cli_Tic'] . "</tipoCli><denoExpCli>" . $datos['Prs_Ape'] . ' ' . $datos['Prs_Nom'] . "</denoExpCli>";
			}
			$xmlExportacion = $xmlExportacion . "<tipoRegi>" . $datos['Reg_Cod'] . "</tipoRegi>";
			if ($datos['Reg_Cod'] == '01') {
				$xmlExportacion = $xmlExportacion . "<paisEfecPagoGen>" . $datos['Pas_Sri'] . "</paisEfecPagoGen>";
			} else {
				if ($datos['Reg_Cod'] == '02') {
					$xmlExportacion = $xmlExportacion . "<paisEfecPagoParFis>" . $datos['Paf_Sri'] . "</paisEfecPagoParFis>";
				} else {
					$xmlExportacion = $xmlExportacion . "<denopagoRegFis>" . $datos['RegDen'] . "</denopagoRegFis>";
				}
			}

			$ingextgravotropas = ($datos['Eve_Ren'] > 0) ? 'SI' : 'NO';
			$xmlExportacion = $xmlExportacion . "<paisEfecExp>" . $datos['Pas_Sri'] . "</paisEfecExp>" .
				"<exportacionDe>" . $datos['Ref_Cod'] . "</exportacionDe>" .
				"<tipIngExt>" . $datos['Ein_Sri'] . "</tipIngExt>" .
				"<ingExtGravOtroPais>" . $ingextgravotropas . "</ingExtGravOtroPais>" .
				"<tipoComprobante>" . $datos['Tic_Sri'] . "</tipoComprobante>";
			if ($datos['Ref_Cod'] == '01') {
				$xmlExportacion = $xmlExportacion . "<distAduanero>" . $datos['Edi_Sri'] . "</distAduanero>" .
					"<anio>" . $datos['Eve_Ano'] . "</anio>" .
					"<regimen>" . $datos['Ere_Sri'] . "</regimen>" .
					"<correlativo>" . $datos['Eve_Cor'] . "</correlativo>" .
					"<docTransp>" . $datos['Eve_Dot'] . "</docTransp>";
			}
			$xmlExportacion = $xmlExportacion . "<fechaEmbarque>" . $datos['Eve_Fec'] . "</fechaEmbarque>" .
				"<valorFOB>" . $datos['Eve_Fob'] . "</valorFOB>" .
				"<valorFOBComprobante>" . $datos['total'] . "</valorFOBComprobante>" .
				"<establecimiento>" . $datos['Suc_Sri'] . "</establecimiento>" .
				"<puntoEmision>" . $datos['Pun_Sri'] . "</puntoEmision>" .
				"<secuencial>" . $datos['Vet_Num'] . "</secuencial>" .
				"<autorizacion>" . $datos['Aut_Num'] . "</autorizacion>" .
				"<fechaEmision>" . $datos['Caj_Fec'] . "</fechaEmision>";
			$xmlExportacion = $xmlExportacion . "</detalleExportaciones>";
		}
		$xmlExportacion = $xmlExportacion . "</exportaciones>";
	}
	/*========================================*/
	/*   A N U L A D O S   D E L    X M L     */
	/*========================================*/

	if (isset($_POST['chk_Anulados'])) {
		$valAuxAnu = 1;
		/**
		 * Cargado de etiquetas de nivel cero 
		 */
		$row_rs_etiquetas_cero = $obBD_con1->getRowConsulta(234, '0' . '*' . '8' . '*' . 'A', $obBD_conexion);

		/**
		 * Cargado del primer SubNivel de ventas "Detalles" 
		 */
		$row_rs_etiquetas_det = $obBD_con1->getRowConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'] . '*' . '8', $obBD_conexion);

		/**
		 * Cargado de las etiquetas correspondiente al "Detalle" 
		 */
		$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(386, $row_rs_etiquetas_det['Esq_Cod'] . '*' . '8', $obBD_conexion);


		/*************************************/
		/*	A N U L A D O S    V E N T A S   */
		/*************************************/
		/**
		 * Consultando los CLIENTES q se anularon una factura de Ventas en un determinado MES para el Anexo Transaccional 
		 */
		$rs_anulados = $obBD_con1->getArrayConsulta(390, $ini . '*' . $fin . '*' . $Ses_Emp_Cod, $obBD_conexion);
		unset($valores); // Vacio el Arreglo $valores[]
		unset($cod_valores);

		/**
		 * Asigna en un arreglo las etiquetas de los valores del anexo 
		 */
		foreach ($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1) {
			$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];
			$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];
		} //Fin del $row_rs_etiquetas_det_1	

		$xml_anulados = $xml_anulados . "<" . $row_rs_etiquetas_cero['Esq_Xml'] . ">";
		foreach ($rs_anulados as $row_rs_anulados) {
			$xml_anulados = $xml_anulados . "<" . $row_rs_etiquetas_det['Esq_Xml'] . ">";

			$xml_anulados = $xml_anulados .        /* ETIQUETAS ANULADOS DEL XML*/
				"<" . $valores[0] . ">" . '18' . "</" . $valores[0] . ">" .		// tipoComprobante		
				"<" . $valores[1] . ">" . $row_rs_anulados['Suc_Sri'] . "</" . $valores[1] . ">" .       // establecimiento
				"<" . $valores[2] . ">" . $row_rs_anulados['Pun_Sri'] . "</" . $valores[2] . ">" .   	// puntoEmision
				"<" . $valores[3] . ">" . str_pad($row_rs_anulados['Vet_Num'], 9, "0", STR_PAD_LEFT) . "</" . $valores[3] . ">" .		// secuencialInicio
				"<" . $valores[4] . ">" . str_pad($row_rs_anulados['Vet_Num'], 9, "0", STR_PAD_LEFT) . "</" . $valores[4] . ">" .     	// secuencialFin
				"<" . $valores[5] . ">" . $row_rs_anulados['Aut_Sri'] . "</" . $valores[5] . ">";       // autorizacion

			$xml_anulados = $xml_anulados . "</" . $row_rs_etiquetas_det['Esq_Xml'] . ">";
		}

		/******************************************************/
		/*    A N U L A D O S     R E T E N C I O N E S       */
		/******************************************************/

		/**
		 * Cargado de los datos de las VENTAS ANULADAS detallados del anexo (Cabecera)
		 */
		$rs_anulados = $obBD_con1->getArrayConsulta(237, $ini . '*' . $fin . '*' . 'I' . '*' . $Ses_Emp_Cod, $obBD_conexion);

		if (count($rs_anulados) > 0) {

			foreach ($rs_anulados as $row_rs_anulados) {
				unset($valores);
				unset($cod_valores);

				/**
				 * Cargado de etiquetas del subnivel cero 
				 */
				$rs_etiquetas_det = $obBD_con1->getArrayConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'] . '*' . '8', $obBD_conexion);

				foreach ($rs_etiquetas_det as $row_rs_etiquetas_det) {	//Inicio del $row_rs_etiquetas_det


					//$xml_anulados = $xml_anulados . "<" . $row_rs_etiquetas_det['Esq_Xml'] . ">";


					/**
					 * Cargado de etiquetas del detalle 
					 */
					$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(227, $row_rs_etiquetas_det['Esq_Cod'] . '*' . '8', $obBD_conexion);

					/**
					 * Asigna en un arreglo las etiquetas de los valores del anexo 
					 */
					foreach ($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1) {
						$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];
						$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];
					} //Fin del $row_rs_etiquetas_det_1 

					//$estab = establecimiento($row_rs_anulados['Ret_Num']);
					//$estab = explode("-",$row_rs_anulados['Ret_Num']);

					if (intval($row_rs_anulados['Ret_Num']) > 0) { //si numero de retencion es menor es cero o menor no ingresa
						$xml_anulados = $xml_anulados . "<" . $row_rs_etiquetas_det['Esq_Xml'] . ">";
						$xml_anulados = $xml_anulados .											                            /* ETIQUETAS ANULADOS DEL XML*/
							"<" . $valores[0] . ">" . str_pad($row_rs_anulados['Tic_Sri'], 2, "0", STR_PAD_LEFT) . "</" . $valores[0] . ">" . // tipoComprobante	
							"<" . $valores[1] . ">" . $row_rs_anulados['Suc_Sri'] . "</" . $valores[1] . ">" .							// establecimiento
							"<" . $valores[2] . ">" . $row_rs_anulados['Pun_Sri'] . "</" . $valores[2] . ">" .							// puntoEmision
							"<" . $valores[3] . ">" . $row_rs_anulados['Ret_Num'] . "</" . $valores[3] . ">" .							// secuencialInicio
							"<" . $valores[4] . ">" . $row_rs_anulados['Ret_Num'] . "</" . $valores[4] . ">" .							// secuencialFin
							"<" . $valores[5] . ">" . $row_rs_anulados['Aut_Sri'] . "</" . $valores[5] . ">";		// autorizacion



						$xml_anulados = $xml_anulados . "</" . $row_rs_etiquetas_det['Esq_Xml'] . ">"; //Cerramos la etiqueta </detalleAnulados>
					}


					//	$xml_anulados = $xml_anulados . "</" . $row_rs_etiquetas_det['Esq_Xml'] . ">"; //Cerramos la etiqueta </detalleAnulados>



				} //Fin del $row_rs_etiquetas_det 
			} //Fin del $row_rs_anulados
		} //Fin del if ($total_rs_anulados > 0) de retenciones

		/*****************************************************************************/
		/*   A N U L A D O S    L I Q U I D A C I O N E S    D E    C O M P R A      */
		/*****************************************************************************/


		/**
		 * Cargado de los datos de las VENTAS ANULADAS detallados del anexo (Cabecera)
		 */
		$rs_anulados = $obBD_con1->getArrayConsulta(238, $ini . '*' . $fin . '*' . 'I' . '*' . '8' . '*' . $Ses_Emp_Cod, $obBD_conexion);

		if (count($rs_anulados) > 0) {
			foreach ($rs_anulados as $row_rs_anulados) { //Inicio del $row_rs_anulados
				unset($valores);
				unset($cod_valores);

				/**
				 * Cargado de etiquetas del subnivel cero 
				 */
				$rs_etiquetas_det = $obBD_con1->getArrayConsulta(227, $row_rs_etiquetas_cero['Esq_Cod'] . '*' . '8', $obBD_conexion);

				foreach ($rs_etiquetas_det as $row_rs_etiquetas_det) {	//Inicio del $row_rs_etiquetas_det
					$xml_anulados	= $xml_anulados . "<" . $row_rs_etiquetas_det['Esq_Xml'] . ">";
					/**
					 * Cargado de etiquetas del detalle 
					 */
					$rs_etiquetas_det_1 = $obBD_con1->getArrayConsulta(227, $row_rs_etiquetas_det['Esq_Cod'] . '*' . '8', $obBD_conexion);

					/**
					 * Asigna en un arreglo las etiquetas de los valores del anexo 
					 */
					foreach ($rs_etiquetas_det_1 as $row_rs_etiquetas_det_1) {
						$valores[] = $row_rs_etiquetas_det_1['Esq_Xml'];
						$cod_valores[] = $row_rs_etiquetas_det_1['Esq_Cod'];
					} //Fin del $row_rs_etiquetas_det_1

					//$estab = establecimiento($row_rs_anulados['Cop_Num']);
					$estab = explode("-", $row_rs_anulados['Cop_Num']);

					$xml_anulados = $xml_anulados .															/* ETIQUETAS ANULADOS DEL XML */
						"<" . $valores[0] . ">" . $row_rs_anulados['Tic_Sri'] . "</" . $valores[0] . ">" .		// tipoComprobante
						"<" . $valores[1] . ">" . $estab[0] . "</" . $valores[1] . ">" .							// establecimiento
						"<" . $valores[2] . ">" . $estab[1] . "</" . $valores[2] . ">" .							// puntoEmision
						"<" . $valores[3] . ">" . $estab[2] . "</" . $valores[3] . ">" .							// secuencialInicio
						"<" . $valores[4] . ">" . $estab[2] . "</" . $valores[4] . ">" .							// secuencialFin
						"<" . $valores[5] . ">" . $row_rs_anulados['Cop_Aut'] . "</" . $valores[5] . ">";		// autorizacion																												
					$xml_anulados	= $xml_anulados . "</" . $row_rs_etiquetas_det['Esq_Xml'] . ">"; //Cerramos la etiqueta </detalleAnulados>


				} //Fin del $row_rs_etiquetas_det
			} //FIn del $row_rs_anulados 
		} //Fin del if ($total_rs_anulados > 0) de Liquidaciones de compra 

		/**
		 * Cierre de la cabecera de la anulados </anulados>
		 */
		$xml_anulados = $xml_anulados . "</" . $row_rs_etiquetas_cero['Esq_Xml'] . ">";
	} //FIN IF if(isset($chk_Anulados))	 
}

?>
<HTML>

<HEAD>
	<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE> <?php echo "ATS [EXA]"; ?></TITLE>
	<meta charset="UTF-8">
	<?Php require_once("../../mascaras/model1/estilos/estilos.php") ?>
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<!--Librerias para interfaz -->
	<script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
	<script type="text/javascript">
		$(function() {
			$('#set1 *').tooltip({
				showURL: false
			});
		});
	</script>
	<meta http-equiv="Content-Type" content="text/html;">
</HEAD>

<BODY>
	<div id="set1">
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
			<tr class="BarraTitulo">
				<td height="10">&raquo; ANEXO TRANSACCIONAL SIMPLIFICADO </td>
			</tr>
			<tr>
				<td height="389" align="left" valign="top">
					<form action="<?Php echo $_SERVER['PHP_SELF'] ?>" method="post" name="form1">
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
											<? foreach ($rs_periodo as $dato) { ?>
												<option <? if (isset($anio) || $dato['Pec_Fei'] == date("Y")) {
															echo "selected";
														} ?> value="<?Php echo $dato['Pec_Fei']; ?>"><?Php echo $dato['Pec_Fei']; ?></option> <? } ?>
										</select>
									</td>
								</tr>
								<tr>
									<td class="Etiqueta1"><span class="Asterisco">* </span>Mes:&nbsp;</td>
									<td>
										<select name="mes" id="mes">
											<option <? if (isset($mes) && $mes == "01") {
														echo "selected";
													} ?> value="01">Enero</option>
											<option <? if (isset($mes) && $mes == "02") {
														echo "selected";
													} ?> value="02">Febrero</option>
											<option <? if (isset($mes) && $mes == "03") {
														echo "selected";
													} ?> value="03">Marzo</option>
											<option <? if (isset($mes) && $mes == "04") {
														echo "selected";
													} ?> value="04">Abril</option>
											<option <? if (isset($mes) && $mes == "05") {
														echo "selected";
													} ?> value="05">Mayo</option>
											<option <? if (isset($mes) && $mes == "06") {
														echo "selected";
													} ?> value="06">Junio</option>
											<option <? if (isset($mes) && $mes == "07") {
														echo "selected";
													} ?> value="07">Julio</option>
											<option <? if (isset($mes) && $mes == "08") {
														echo "selected";
													} ?> value="08">Agosto</option>
											<option <? if (isset($mes) && $mes == "09") {
														echo "selected";
													} ?> value="09">Septiembre</option>
											<option <? if (isset($mes) && $mes == "10") {
														echo "selected";
													} ?> value="10">Octubre</option>
											<option <? if (isset($mes) && $mes == "11") {
														echo "selected";
													} ?> value="11">Noviembre</option>
											<option <? if (isset($mes) && $mes == "12") {
														echo "selected";
													} ?> value="12">Diciembre</option>
										</select>
									</td>
								</tr>

								<tr>
									<td class="Etiqueta1"> Fechas <input type="checkbox" id="chk_fechas" name="chk_fechas" value="first_checkbox" onclick="filtros();"></td>
								</tr>

								<tr>
									<td class="Etiqueta1"><span class="Asterisco">* </span>Inicio:&nbsp;</td>
									<td>
										<input id="Ats_Fec_Ini" name="Ats_Fec_Ini" type="date" data-date="" data-date-format="DD MMMM YYYY" class="form-control input-xs datepickers" tabindex="8" required="" disabled="true" />
										<span class="input-group-addon input-xs" title="Fecha de inicio"><i class="glyphicon glyphicon-info-sign blue"></i></span>
									</td>
								</tr>

								<tr>
									<td class="Etiqueta1"><span class="Asterisco">* </span>Fin:&nbsp;</td>
									<td>
										<input id="Ats_Fec_Fin" name="Ats_Fec_Fin" type="date" data-date="" data-date-format="DD MMMM YYYY" class="form-control input-xs datepickers" tabindex="8" required="" disabled="true" />
										<span class="input-group-addon input-xs" title="Fecha fin"><i class="glyphicon glyphicon-info-sign blue"></i></span>
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
									<td width="100">&nbsp;<input name="chk_Compras" type="checkbox" id="chk_Compras" checked></td>
								</tr>
								<tr>
									<td class="Etiqueta1">Ventas:</td>
									<td>&nbsp;<input type="checkbox" id="chk_Ventas" name="chk_Ventas" checked="checked"></td>
								</tr>
								<tr>
									<td class="Etiqueta1">Exportaciones</td>
									<td>&nbsp;<input type="checkbox" id="chk_Export" name="chk_Export" checked="checked"></td>
								</tr>
								<tr>
									<td class="Etiqueta1">Anulados:</td>
									<td>&nbsp;<input name="chk_Anulados" type="checkbox" id="chk_Anulados" checked></td>
								</tr>
							</table>
						</FIELDSET>
						<?php
						if ($total_rs_etiquetas > 0)/*  ------  Ojo se modifico el IF ------ */ {
						?>
							<FIELDSET>
								<LEGEND>
									<label class="Titulos2">Descargar Xml:</label>
								</LEGEND>
								<table width="100%" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
									<tr>
										<td>
											<?php
											$buffer = '<?xml version="1.0" encoding="UTF-8"?><!--Archivo XML generado por Exa (http://www.exa.ofsercont.com)--><iva>';
											$buffer = utf8_encode($buffer . $xml_identifica . $xml_compras . $xml_ventas . $xml_ventasEst . $xmlExportacion . $xml_anulados . '</iva>');

											$archivo = "SRI/" . $Ses_Emp_Cod . "/ATS" . $mes . $anio . ".xml";
											if (file_exists($archivo))
												unlink("SRI/" . $Ses_Emp_Cod . "/ATS" . $mes . $anio . ".xml");/* eliminamos el file para crear uno nuevo */

											//$file=fopen($anio.$mes.".xml","w+");
											$file = fopen($archivo, "w+");
											fwrite($file, $buffer);
											fclose($file);
											echo "&raquo;&nbsp;&nbsp;Archivo XML generado correspondiente a <strong>" . mes($mes, 1) . "</strong> del <strong>" . $anio . "</strong>  <a href='" . $archivo . "?X=" . rand(1, 100) . "' target='_blank'><img src='../../mascaras/model1/imagenes/32x32/download.gif' title='Descargar XML:<br>1. Clic con el botón derecho del mouse sobre este Icono.<br>2. Del menú desplegable, seleccionar la opción Guardar enlace como...<br>3. Dar la ubicación deseada y presionar el botón guardar.'></a><br>
				 &raquo;&nbsp;&nbsp;Tal&oacute;n de Resumen correspondiente a <strong>" . mes($mes, 1) . "</strong> del <strong>" . $anio . "</strong> <a href='tes_pri_ats_resumen_2.0.php?ini=" . $ini . "&fin=" . $fin . "&aCom=" . $valAuxCom . "&bVen=" . $valAuxVen . "&Exp=" . $valAuxExp . "&cNul=" . $valAuxAnu . "&url=" . $archivo . "' target='_blank'><img src='../../mascaras/model1/imagenes/32x32/download.gif' title='Imprimir Tal&oacute;n de Resumen:'></a><br>
				 ";

											// echo "&raquo;&nbsp;&nbsp;IVA 5%, 15% (desde Abril 2024)  Tal&oacute;n de Resumen correspondiente a <strong>" . mes($mes, 1) . "</strong> del <strong>" . $anio . "</strong> <a href='tes_pri_ats_resumen_2.1.php?ini=" . $ini . "&fin=" . $fin . "&aCom=" . $valAuxCom . "&bVen=" . $valAuxVen . "&Exp=" . $valAuxExp . "&cNul=" . $valAuxAnu . "&url=" . $archivo . "' target='_blank'><img src='../../mascaras/model1/imagenes/32x32/download.gif' title='Imprimir Tal&oacute;n de Resumen'></a>";

											?>

										</td>
									</tr>
								</table>
							</FIELDSET>
						<?php
						} //Fin del if ($total_rs_compras > 0)
						else {
							if (isset($bt_save)) {
								echo error_alerta("No hay resultados que mostrar", 1);
							}
						}
						?>
						<br>
						<table width="176" border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td width="181"><input id="bt_save" name="bt_save" type="hidden" value="Grabar">

									<button type="button" class="btn btn-primary start" title="Guardar" onClick="validar_requeridos(this.form, 'anio*mes', 1)">
										<i class="icon-book icon-white"></i>
										<span>Generar</span>
									</button>
								</td>
							</tr>
						</table>

					</form>
				</td>
			</tr>
		</table>
	</div>

	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script>
		$.clearValidate();
	</script>
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
	<script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
	<script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
	<link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />

	<script type="text/javascript">
		function filtros() {
			if (document.getElementById("chk_fechas").checked) {
				document.getElementById("Ats_Fec_Fin").disabled = false;
				document.getElementById("Ats_Fec_Ini").disabled = false;
				document.getElementById("mes").disabled = true;
				document.getElementById("anio").disabled = true;
			} else {
				document.getElementById("mes").removeAttribute("disabled");
				document.getElementById("anio").removeAttribute("disabled");
				document.getElementById("Ats_Fec_Fin").disabled = true;
				document.getElementById("Ats_Fec_Ini").disabled = true;
			}
		}

		$("input").on("change", function() {
			this.setAttribute(
				"data-date",
				moment(this.value, "YYYY-MM-DD")
				.format(this.getAttribute("data-date-format"))
			)
		}).trigger("change")
	</script>

	<?Php
	/**
	 * Cerrado de las conexiones 
	 */
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	?>