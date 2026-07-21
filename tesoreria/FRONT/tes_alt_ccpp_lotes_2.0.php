<?php

/**
 * @abstract Permite realizar la modificacion de Anticipos Manuales
 * @author Erik Cordova
 * @version 1.0
 * Fecha de creacion  2017-12-06
 * Actualizado por Wilson Belduma 
 * Fecha de actualizacion 2024-06-27
 */

// Aumentar tiempo de ejecución y límite de memoria para manejar grandes volúmenes de datos
ini_set('max_execution_time', 300); // 5 minutos
ini_set('memory_limit', '512M'); // 512 MB de memoria
set_time_limit(300); // Establecer límite de tiempo a 5 minutos

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_ccpp_lotes_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Ccpp($Ses_Dat_Dis);
/**
 * Creación del Objeto para consultas
 */
$obBD_con1 =  new Class_Log_Datos_Ccpp;

//fecha y mes actuales
$hoy = date("Y-m-d");
$mes = date("m");

//para obtener planes de cuenta para agregar aportaciones
if (isset($cuentasAjax)) {
	$obBD_con1->getPageGridJson(12, $_GET, $obBD_conexion);
}

//obtener cuenta por defecto para pago a proveedores
if (isset($getPagoIniAjax)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";
	$response['data'] = $obBD_con1->getRowConsulta(7, "", $obBD_conexion);
	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}
	$obBD_con1->echoJson($response);
	exit();
}

//obtener cuenta por defecto para pago a proveedores
if (isset($getPagoCtaAjax)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";

	if ($tipo == "EFE" || $tipo == "OTR" || $tipo == "DEP") {
		$response['data'] = $obBD_con1->getRowConsulta(8, "", $obBD_conexion);
	}
	if ($tipo == "ANT") {
		$response['data'] = $obBD_con1->getRowConsulta(20, "", $obBD_conexion);
	}
	if ($tipo == "CDC") {
		$response['data'] = $obBD_con1->getRowConsulta(27, "", $obBD_conexion);
	}
	//OBTENER CUENTAS PARAMETRIZADAS DE CAJA REPOSICION
	if ($tipo == "RC") {
		$response['data'] = $obBD_con1->getRowConsulta(277, "", $obBD_conexion);
	}
	//ChromePhp::log("data repocision caja");
	//ChromePhp::log($response['data']);
	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}
	$obBD_con1->echoJson($response);
	exit();
}
//Seccion para obtener los proveedores registrados en la empresa
if (isset($proveedoresAjax)) {
	$obBD_con1->getPageGridJson(1, $_GET, $obBD_conexion);
}

// obtenemos los proveedores y sus anticipos
if (isset($ajaxComprobante)) {
	// $obBD_con1->getPageGridJson(2,$_GET, $obBD_conexion);
	$responce['rows'] = $obBD_con1->getArrayConsulta(2, $_GET, $obBD_conexion);
	foreach ($responce['rows'] as $key => $item) {
		if ($item['Abono'] == $item['Asi_Val']) unset($responce['rows'][$key]);
	}
	$responce['rows'] = array_values($responce['rows']);
	$responce['records'] = count($responce['rows']);
	$obBD_con1->echoJson($responce);
	exit();
}

//verificamos si el numero de un cheque ya esta registrado dentro de la tabla cheques
if (isset($verificarCheNum)) {
	//Se obtiene los numeros de chueques que coincidan con el banco seleccionado
	$response['numero_che'] = false;
	$num_Ches = $obBD_con1->getArrayConsulta(10, $Ban_Cod, $obBD_conexion);
	foreach ($num_Ches as $nch) {
		if ($nch['Che_Num'] == $Che_Num) {
			$response['numero_che'] = true;
		}
	}
	$obBD_con1->echoJson($response);
	exit();
}


//Guardar los pagos ingresados
if (isset($savePago)) {

	$repo_caja_chica = false;
	//ChromePhp::log($save_p);
	//ChromePhp::log($tip_trans . " Valor total pago:" . $valor_tota_pago);
	//	ChromePhp::log(Count($save_pago_anticipos));

	foreach ($save_p as $pago) {
		//ChromePhp::log($valor_tota_pago);
		if (    $pago['Pag_Abr'] == 'ANT' &&   (float)$valor_tota_pago <=0   ) {
			$response['message'] = "Ha seleccionado el pago con anticipos, pero no ha seleccionado ningún valor. Por favor, verifique nuevamente.";
			$obBD_con1->echoJson($response);
			exit();
		}
	}




	//$obBD_con1->debug(true);
	$obBD_con1->debugLogs(false);
	//Validar el cierre del periodo
	$obBD_con1->validaCierrePeriodo('det_ccpp_p', 'Pag_Fec', 'Cpp_Cod', $Com_Fec, null, $obBD_conexion);
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";
	$response['arrayche'] = array();
	$response['bnd_che'] = false;
	$contAct = 0;
	$imagenes_pagos = array();
	$archivos_guardados = array();

	if (!empty($_FILES['Pag_img_archivos']['name']) && is_array($_FILES['Pag_img_archivos']['name'])) {
		$tipos_permitidos = array(
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/webp' => 'webp',
			'image/gif' => 'gif'
		);
		$directorio_abs = dirname(__FILE__) . '/../../facturacion/FRONT/' . intval($Ses_Emp_Cod) . '/compr_prov';
		$directorio_rel = 'facturacion/FRONT/' . intval($Ses_Emp_Cod) . '/compr_prov';

		foreach ($_FILES['Pag_img_archivos']['name'] as $pago_idx => $nombre_original) {
			$error_archivo = isset($_FILES['Pag_img_archivos']['error'][$pago_idx])
				? intval($_FILES['Pag_img_archivos']['error'][$pago_idx])
				: UPLOAD_ERR_NO_FILE;
			if ($error_archivo === UPLOAD_ERR_NO_FILE || trim((string)$nombre_original) === '') {
				continue;
			}
			$pago_post = isset($save_p[$pago_idx]) ? $save_p[$pago_idx] : array();
			if (empty($pago_post['Pag_Abr']) || $pago_post['Pag_Abr'] !== 'TRF') {
				continue;
			}
			if ($error_archivo !== UPLOAD_ERR_OK) {
				$response['message'] = 'No se pudo cargar el comprobante de transferencia.';
				$obBD_con1->echoJson($response);
				exit();
			}

			$tmp_archivo = $_FILES['Pag_img_archivos']['tmp_name'][$pago_idx];
			$tam_archivo = intval($_FILES['Pag_img_archivos']['size'][$pago_idx]);
			$info_imagen = @getimagesize($tmp_archivo);
			$mime_imagen = ($info_imagen && !empty($info_imagen['mime'])) ? $info_imagen['mime'] : '';
			if (!is_uploaded_file($tmp_archivo) || $tam_archivo <= 0 || $tam_archivo > 5242880 || !isset($tipos_permitidos[$mime_imagen])) {
				$response['message'] = 'El comprobante debe ser una imagen JPG, PNG, WEBP o GIF de máximo 5 MB.';
				$obBD_con1->echoJson($response);
				exit();
			}
			if (!is_dir($directorio_abs) && !@mkdir($directorio_abs, 0777, true) && !is_dir($directorio_abs)) {
				$response['message'] = 'No se pudo crear la carpeta para guardar los comprobantes.';
				$obBD_con1->echoJson($response);
				exit();
			}

			$nombre_archivo = 'transferencia_ccpp_' . date('Ymd_His') . '_' . uniqid() . '.' . $tipos_permitidos[$mime_imagen];
			$destino_abs = $directorio_abs . '/' . $nombre_archivo;
			if (!move_uploaded_file($tmp_archivo, $destino_abs)) {
				$response['message'] = 'No se pudo guardar el comprobante de transferencia.';
				$obBD_con1->echoJson($response);
				exit();
			}
			$imagenes_pagos[$pago_idx] = $directorio_rel . '/' . $nombre_archivo;
			$archivos_guardados[] = $destino_abs;
		}
	}

	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
	//generamos el numero de comprobante
	$var_mes = explode('-', $Com_Fec);
	$Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod, $var_mes[1], $obBD_conexion);
	$Cliente = $obBD_con1->getArrayConsulta(25, array('Prv_Cod' => $agg_Prv_Cod), $obBD_conexion);
	$Cli_Cod = 'null';

	if (count($Cliente) > 0) {
		foreach ($Cliente as $cli) {
			$Cli_Cod = $cli['Cli_Cod'];
		}
	}

	//insertamos un comprobante y extraemos el id ingresado
	$obBD_con1->operacionobBD(13, array('Pec_Cod' => $Pec_Cod, 'Prv_Cod' => $agg_Prv_Cod, 'Cli_Cod' => $Cli_Cod, 'Com_Num' => $Com_Num, 'Com_Fec' => $Com_Fec, 'Com_Con' => $Com_Con, 'Com_Val' => $Com_Val, 'Com_Obs' => $Com_Obs, 'Tia_Cod' => $Tia_Cod), $obBD_conexion);
	$ultimo_comprobate = $obBD_con1->insercionid($obBD_conexion); //58..
	$contador_cheque = 0;
	$cntcpp = 0;

	

	foreach ($save_p as $pago_idx => $pago) {



		if ($pago['grid_tipp'] == 'pago') {
			// insertamos un asiento por cada pago
			$obBD_con1->operacionobBD(14, array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'H', 'Asi_Con' => $pago['concepto'], 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Haber'], 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion);
			$ultimo_asiento = $obBD_con1->insercionid($obBD_conexion);
			$pag_img = isset($imagenes_pagos[$pago_idx]) ? $imagenes_pagos[$pago_idx] : '';
			//********************************************************************************************************************
			$var_pag = (float)$pago['Haber']; //Es el pago que yo ingreso
			while ($var_pag != "none") {
				// echo "--> ".$save_cp[$cntcpp]['Cpp_Cod']." | ".(float)$save_cp[$cntcpp]['Pag_Val']." | ".$pago['Pag_Cod']." | ".$var_pag."\n";
				if ($save_cp['' . intval($cntcpp)]['Pag_Val'] == 0) {
					$var_pag = "none";
				} elseif ($var_pag < (float)$save_cp['' . intval($cntcpp)]['Pag_Val']) {
					$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $save_cp['' . intval($cntcpp)]['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $ultimo_comprobate, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => $var_pag, 'Pag_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento, 'Pag_img' => $pag_img), $obBD_conexion);
					$save_cp['' . intval($cntcpp)]['Pag_Val'] = (float)((float)$save_cp['' . intval($cntcpp)]['Pag_Val'] - $var_pag);
					$var_pag = "none";
				} elseif ($var_pag == (float)$save_cp['' . intval($cntcpp)]['Pag_Val']) {
					$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $save_cp['' . $cntcpp]['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $ultimo_comprobate, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => $var_pag, 'Pag_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento, 'Pag_img' => $pag_img), $obBD_conexion);
					$var_pag = "none";
					$cntcpp++;
				} elseif ($var_pag > (float)$save_cp['' . intval($cntcpp)]['Pag_Val']) {
					$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $save_cp['' . intval($cntcpp)]['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $ultimo_comprobate, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => ($var_pag - ($var_pag - (float)$save_cp['' . intval($cntcpp)]['Pag_Val'])), 'Pag_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento, 'Pag_img' => $pag_img), $obBD_conexion);
					$var_pag = $var_pag - (float)$save_cp['' . intval($cntcpp)]['Pag_Val'];
					$cntcpp++;
				}
			}
			//en caso de haber un anticipo entre los pagos
			if ($pago['Pag_Abr'] == 'ANT') {
				//Trae los anticipos que no estan usados
				foreach ($save_pago_anticipos as $pagoAnt) {
					$pAprov = $obBD_con1->getArrayConsulta('pago_anticipo_proveedores.selectWhere', array('where' => array('Atp_Cod' => $pagoAnt['Atp_Cod'])), $obBD_conexion, true);
					if (($pagoAnt["saldo_aux"] >= (float)$pagoAnt["saldo_pagar"])  && (float)$pagoAnt["saldo_pagar"] > 0) {
						$saldo_aux = 0;
						foreach ($pAprov as &$pag) {  ///    2249    = 250       2251 = 150 150  -----3000
							if ((float)$pagoAnt["saldo_pagar"] != 0) {
								if ($pag['Pap_Est'] != 'C') { //
									if ($pag["Pap_Val"] >= (float)$pagoAnt["saldo_pagar"]) {
										$saldo_aux = (float)$pagoAnt["saldo_pagar"];
										//LO QUE SOBRA TENGO QUE DEVO PAGAR
									} else if ($pag["Pap_Val"] < (float)$pagoAnt["saldo_pagar"]) {  //SI
										$saldo_aux = $pag["Pap_Val"];   //1000
									}
									$obBD_con1->operacionobBD(21, array('Dac_Val' => ((float)$saldo_aux), 'Com_Cod' => $ultimo_comprobate, 'Atp_Cod' => $pagoAnt['Atp_Cod'],   'Pap_Cod' => $pag['Pap_Cod']), $obBD_conexion, true);
									$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pag['Pap_Cod'], 'Atp_Cod' => $pag['Atp_Cod'], 'Pap_Est' => 'U'), $obBD_conexion, true);
								}
								//Consultar desde la base de datos los anticipos consumidos
								if (number_format($pagoAnt["saldo"], 2, '.', '') == 0) {
									$obBD_con1->operacionobBD(23, array('Atp_Cod' => $pag['Atp_Cod'], 'Atp_Est' => "C"), $obBD_conexion, true);
									$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pag['Pap_Cod'], 'Atp_Cod' => $pag['Atp_Cod'], 'Pap_Est' => 'C'), $obBD_conexion, true);
								}
								if (number_format($pagoAnt["saldo"], 2, '.', '') > 0) {
									$obBD_con1->operacionobBD(23, array('Atp_Cod' => $pag['Atp_Cod'], 'Atp_Est' => "U"), $obBD_conexion, true);
								}
								$pagoAnt["saldo_pagar"] = (float) $pagoAnt["saldo_pagar"] - $saldo_aux;
							}
						}
					}
				}
			}





			//en caso de ser pago con cruce de cuentas se genera los debidos detalles de cuentas por cobrar
			if ($pago['Pag_Abr'] == 'CDC') {
				//	$ccc_cnt = $obBD_con1->getArrayConsulta(26, array('Cli_Cod' => $Cli_Cod, 'Pec_Cod' => $Pec_Cod), $obBD_conexion, true);
				
				(float)$cont_ccc = 0;
				$cnt_destino = (float)$pago['Haber'];
				
				foreach ($ccc_cnt as $deu) {
					if ($deu['saldo_pagar'] > 0) {
						$obBD_con1->operacionobBD(28, array('Cpc_Cod' => $deu['Cpc_Cod'], 'Com_Cod' => $ultimo_comprobate, 'Pag_Cod' => $pago['Pag_Cod'], 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => $deu['saldo_pagar'], 'Cpc_Obs' => $Com_Obs), $obBD_conexion);
					}
					/*
					ChromePhp::log("TOTAL HABER: " . $cnt_destino . " <=   SALDO:" . $deu['saldo_pagar']);
						if (($cont_ccc + (float)$deu['saldo_pagar']) <= $cnt_destino) {
							//insertamos un pago a cuentas por cobrar
							$obBD_con1->operacionobBD(28, array('Cpc_Cod' => $deu['Cpc_Cod'], 'Com_Cod' => $ultimo_comprobate, 'Pag_Cod' => $pago['Pag_Cod'], 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => $deu['saldo_pagar'], 'Cpc_Obs' => $Com_Obs), $obBD_conexion);
							$cont_ccc += (float)$deu['saldo_pagar']; // 10
						}
						if (($cont_ccc + (float)$deu['saldo_pagar']) > $cnt_destino) {
							//insertamos un pago a cuentas por cobrar
							$obBD_con1->operacionobBD(28, array('Cpc_Cod' => $deu['Cpc_Cod'], 'Com_Cod' => $ultimo_comprobate, 'Pag_Cod' => $pago['Pag_Cod'], 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => ($cnt_destino - $cont_ccc), 'Cpc_Obs' => $Com_Obs), $obBD_conexion);
							$cont_ccc += ($cnt_destino - $cont_ccc); //
						}*/

					/*if ($cont_ccc < $cnt_destino) {


						/if (($cont_ccc + (float)$deu['saldo']) <= $cnt_destino) {
							//insertamos un pago a cuentas por cobrar
							$obBD_con1->operacionobBD(28, array('Cpc_Cod' => $deu['Cpc_Cod'], 'Com_Cod' => $ultimo_comprobate, 'Pag_Cod' => $pago['Pag_Cod'], 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => $deu['saldo'], 'Cpc_Obs' => $Com_Obs), $obBD_conexion);
							$cont_ccc += (float)$deu['saldo']; // 10
						}
						if (($cont_ccc + (float)$deu['saldo']) > $cnt_destino) {
							//insertamos un pago a cuentas por cobrar
							$obBD_con1->operacionobBD(28, array('Cpc_Cod' => $deu['Cpc_Cod'], 'Com_Cod' => $ultimo_comprobate, 'Pag_Cod' => $pago['Pag_Cod'], 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => ($cnt_destino - $cont_ccc), 'Cpc_Obs' => $Com_Obs), $obBD_conexion);
							$cont_ccc += ($cnt_destino - $cont_ccc); //
						}
					}*/
				}
			}









			if ($pago['Pag_Abr'] == 'CHE') {
				//insertamos un cheque
				$contador_cheque++;
				$response['bnd_che'] = true;
				array_push($response['arrayche'], array('link' => "?codigo2=$contador_cheque&asi=" . $ultimo_asiento . "&ban=" . $pago['Ban_Cod'] . "&pro=" . $agg_Prv_Cod, 'che' => "No.:" . $pago['Che_Num'] . " - Valor:$ " . $pago['Haber']));
				$obBD_con1->operacionobBD(17, array('Che_Cod' => $contador_cheque, 'Prv_Cod' => $agg_Prv_Cod, 'Ban_Cod' => $pago['Ban_Cod'], 'Asi_Cod' => $ultimo_asiento, 'Che_Num' => $pago['Che_Num'], 'Che_Fec' => $pago['Che_Fec'], 'Che_Val' => $pago['Haber'], 'Che_Obs' => $Com_Obs, 'Che_Ben' => $Che_Ben_N), $obBD_conexion);
			}

			if ($pago['Pag_Abr'] == 'RC') { //INCERTAMOS A REPOSICION DE CAJA
				//obtengo valor caja chica
				$val_caja = $obBD_con1->getRowConsulta(131, $Ses_Emp_Cod, $obBD_conexion, true);
			
				//Total que he gastado en Caja
				$gasto_caja = $obBD_con1->getRowConsulta(130,  $Ses_Emp_Cod, $obBD_conexion, true);
				
				$saldo_caja = (float)$val_caja["val_caja"] - (float)$gasto_caja["total_g_caja"];
		
				$pago_rep_caja = (float) $pago["Haber"]; //El valor que debo pagar
			
				if ((float)$pago_rep_caja > (float)$saldo_caja) {
					$response['message'] = "El saldo en caja es de <span style='color:blue;'>$" . $saldo_caja . "</span>, y el saldo que intenta pagar es mayor, por favor verifique nuevamente.";
					$obBD_con1->echoJson($response);
				}
			
				foreach ($save_cp as $cp) {
					// 10 > 0 --   9 > 0
					if ($pago_rep_caja > 0) {
						$val_compra = (float) $cp["Pag_Val"]; //Valor a pagar
					
						$Cpp_Cod =  $cp["Cpp_Cod"];
						//Obtento el codigo de la compra
						$Cop_Cod = $obBD_con1->getRowConsulta(128, array('Cpp_Cod' => $Cpp_Cod), $obBD_conexion, true);
						//Veriifcar si existe la compra en det_reposicion
						$existe_compra = $obBD_con1->getRowConsulta(129, $Cop_Cod['Cop_Cod'], $obBD_conexion, true);
						
						// (10 <= 1) (9 <= 2)
						if ($pago_rep_caja <= $val_compra) {
							if ($existe_compra["cant_cop"] == 0) {
								$obBD_con1->operacionobBD(127, $Cop_Cod['Cop_Cod']  . '*' . '0' . '*' . 'P', $obBD_conexion);
								$pago_rep_caja = 0;
							}
						}
						$val_residuo = 0;
						if ($pago_rep_caja > $val_compra) {
							if ($existe_compra["cant_cop"] == 0) {
								// 10 - 1 = 9
								$val_residuo = number_format(($pago_rep_caja - $val_compra), 2, '.', '');
								$obBD_con1->operacionobBD(127, $Cop_Cod['Cop_Cod']  . '*' . '0' . '*' . 'P', $obBD_conexion);
							}
						}
						$pago_rep_caja = $val_residuo;
					}
				}
			}
		} else {
			$obBD_con1->operacionobBD(14, array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'D', 'Asi_Con' => $pago['concepto'], 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Debe'], 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion);
		}
	}
	$response['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo_comprobate&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod";
	$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	} else {
		$response['error'] = $obBD_con1->MsgError;
		foreach ($archivos_guardados as $archivo_guardado) {
			if (is_file($archivo_guardado)) {
				@unlink($archivo_guardado);
			}
		}
	}
	$obBD_con1->echoJson($response);
	exit();
}

//obtener cantidad Disponible en anticipos a proveedores
if (isset($getAnticipoCantAjax)) {
	//$obBD_con1->debug(true);
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";
	$anticipos_cnt = $obBD_con1->getRowConsulta(18, array('Prv_Cod' => $Prv_Cod), $obBD_conexion, true);
	$det_anticipos_cnt = $obBD_con1->getRowConsulta(19, array('Prv_Cod' => $Prv_Cod), $obBD_conexion, true);
	$response['Atp_Fec'] = $anticipos_cnt['Atp_Fec'];

	if (count($anticipos_cnt) > 0) {
		$response['data'] = $anticipos_cnt['tot_anti'] - $det_anticipos_cnt['tot_dac'];
	} else {
		$response['data'] = 'none';
	}
	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}
	$obBD_con1->echoJson($response);
	exit();
}

//obtenemos todos los pagos de una factura
if (isset($abonosDetAjax)) {
	$responce['rows'] = $obBD_con1->getArrayConsulta(29, $abonosDetAjax, $obBD_conexion);
	$responce['records'] = count($responce['rows']);
	$obBD_con1->echoJson($responce);
	exit();
}

//obtener cantidad sisponible en anticipos a proveedores getArrayConsulta
if (isset($getCccAjax)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";
	$prv_cli_cod = $obBD_con1->getRowConsulta(25, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
	$ccc_cnt = $obBD_con1->getArrayConsulta(26, array('Cli_Cod' => $prv_cli_cod['Cli_Cod'], 'Pec_Cod' => $Pec_Cod), $obBD_conexion);
	if (count($ccc_cnt) > 0) {
		$total = 0;
		$abono = 0;
		foreach ($ccc_cnt as $deu) {
			(float)$total += (float)$deu['Asi_Val'];
			(float)$abono += (float)$deu['Abono'];
		}
		$response['data'] = (float)$total - (float)$abono;
	} else {
		$response['data'] = 'none';
	}
	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}
	$obBD_con1->echoJson($response);
	exit();
}

//obtenemos todos los asientos y chuques de un determinado abono
if (isset($getAsientosAbono)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";

	$response['data'] = $obBD_con1->getArrayConsulta(30, array('Com_Cod' => $Com_Cod), $obBD_conexion);
	$response['data_che'] = $obBD_con1->getArrayConsulta(31, array('Com_Cod' => $Com_Cod), $obBD_conexion);

	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}

	$obBD_con1->echoJson($response);
	exit();
}

//obtenemos todos los pagos de un abono
if (isset($getPagsAbono)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";

	$response['data'] = $obBD_con1->getArrayConsulta(32, array('Com_Cod' => $Com_Cod), $obBD_conexion);

	$anticipos_cnt = $obBD_con1->getRowConsulta(18, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
	$det_anticipos_cnt = $obBD_con1->getRowConsulta(19, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
	if (count($anticipos_cnt) > 0) {
		$response['data_ant'] = $anticipos_cnt['tot_anti'] - $det_anticipos_cnt['tot_dac'];
	} else {
		$response['data_ant'] = 'none';
	}

	$prv_cli_cod = $obBD_con1->getRowConsulta(25, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
	$ccc_cnt = $obBD_con1->getArrayConsulta(26, array('Cli_Cod' => $prv_cli_cod['Cli_Cod'], 'Pec_Cod' => $Pec_Cod), $obBD_conexion);
	if (count($ccc_cnt) > 0) {
		$total = 0;
		$abono = 0;
		foreach ($ccc_cnt as $deu) {
			(float)$total += (float)$deu['Asi_Val'];
			(float)$abono += (float)$deu['Abono'];
		}
		$response['data_ccc'] = (float)$total - (float)$abono;
	} else {
		$response['data_ccc'] = 'none';
	}


	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}

	$obBD_con1->echoJson($response);
	exit();
}

//obtenemos las facturas incluidas en un determinado abono
if (isset($getFactsAbono)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";

	$response['data'] = $obBD_con1->getArrayConsulta(42, array('Com_Cod' => $Com_Cod, 'Prv_Cod' => $Prv_Cod), $obBD_conexion);

	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}

	$obBD_con1->echoJson($response);
	exit();
}

//protestar el chueque seleccionado asignando un contraasiento para dicho cheuque
if (isset($protestarChe)) {

	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transacci&oacute;n";

	$hoy = date("Y-m-d");
	$Pec_Cod = $obBD_con1->getRowConsulta(51, $Che_Fec, $obBD_conexion);

	//en caso de no existir un periodo contable para la fecha en la que se protesta el cheque
	//se retorna un mensaje que notifica dicho conflicto
	if (count($Pec_Cod) > 0) {
		$response['pec_ban'] = "si";
		$var_mes = explode('-', $hoy);
		$Com_Num = $obBD_con1->codigoComprAuto($Prv_Cod, $Pec_Cod['Pec_Cod'], $var_mes[1], $obBD_conexion);
		$tipo_asien_prt = $obBD_con1->getRowConsulta(49, "", $obBD_conexion);
		$Tia_Cod = $tipo_asien_prt['Tia_Cod'];

		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);

		$obBD_con1->operacionobBD(50, array('Che_Cod' => $Che_Cod, 'Prv_Cod' => $Prv_Cod, 'Ban_Cod' => $Ban_Cod, 'Asi_Cod' => $Asi_Cod), $obBD_conexion);

		//modificar asiento de un cheque protestado
		$obBD_con1->operacionobBD(52, array('Asi_Cod' => $Asi_Cod, 'Asi_Glo' => "CHEQUE No. " . $Che_Num . " protestado"), $obBD_conexion);

		$Cliente = $obBD_con1->getArrayConsulta(25, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
		$Cli_Cod = 'null';
		if (count($Cliente) > 0) {
			foreach ($Cliente as $cli) {
				$Cli_Cod = $cli['Cli_Cod'];
			}
		}
		//insertamos un comprobante y extraemos el id ingresado
		// $obBD_con1->operacionobBD(13, array('Pec_Cod' => $Pec_Cod['Pec_Cod'], 'Prv_Cod' => $Prv_Cod, 'Cli_Cod' => $Cli_Cod, 'Com_Num' => $Com_Num, 'Com_Fec' => $hoy, 'Com_Con' => 'REINGRESO DE VALORES POR CHEQUE PROTESTADO', 'Com_Val' => $Che_Val, 'Com_Obs' => "CHEQUE No. " . $Che_Num . " protestado", 'Tia_Cod' => $Tia_Cod), $obBD_conexion);
		$obBD_con1->operacionobBD(13, array(
			'Pec_Cod' => $Pec_Cod,
			'Prv_Cod' => $agg_Prv_Cod,
			'Cli_Cod' => $Cli_Cod,
			'Com_Num' => $Com_Num,
			'Com_Fec' => $Com_Fec,
			'Com_Con' => $Com_Con,
			'Com_Val' => $Com_Val,
			'Com_Obs' => $Com_Obs,
			'Tia_Cod' => $Tia_Cod,
			'Num_Doc' => $Num_Doc
		), $obBD_conexion);
		$ultimo_comprobate = $obBD_con1->insercionid($obBD_conexion);

		// insertamos un asiento inical Para el cheque protestado
		$Pld_Cod_ini = $obBD_con1->getRowConsulta(7, "", $obBD_conexion);
		$obBD_con1->operacionobBD(14, array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'H', 'Asi_Con' => "CHEQUES PROTESTADOS", 'Asi_Glo' => "CHEQUES PROTESTADOS", 'Asi_Val' => $Che_Val, 'Pld_Cod' => $Pld_Cod_ini['Pld_Cod']), $obBD_conexion);
		$obBD_con1->operacionobBD(14, array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'D', 'Asi_Con' => "CHEQUES PROTESTADOS", 'Asi_Glo' => "CHEQUE No. " . $Che_Num . " protestado", 'Asi_Val' => $Che_Val, 'Pld_Cod' => $Pld_Cod), $obBD_conexion);
		$ultimo_asiento = $obBD_con1->insercionid($obBD_conexion);

		$t_pag = $obBD_con1->getRowConsulta(53, "", $obBD_conexion);
		$che_facts = $obBD_con1->getArrayConsulta(54, array('Asi_Cod' => $Asi_Cod), $obBD_conexion);
		foreach ($che_facts as $chf) {
			//insertamos un registro en la tabla det_ccpp_p
			$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $chf['Cpp_Cod'], 'Pag_Cod' => $t_pag['Pag_Cod'], 'Com_Cod' => $ultimo_comprobate, 'Pag_Fec' => $hoy, 'Pag_Val' => (-$chf['Pag_Val']), 'Pag_Obs' => 'REINGRESO DE VALORES POR CHEUQUE PROTESTADO /CHE. No. ' . $Che_Num . ' protestado', 'Asi_Cod' => $ultimo_asiento), $obBD_conexion);
		}

		$Pec_Cod_val = $Pec_Cod['Pec_Cod'];
		$response['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo_comprobate&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod_val";
		$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
	} else {
		$response['message'] = "Advertencia: Hace falta un periodo contable para el año actual";
		$response['pec_ban'] = "no";
	}

	//en caso de existir error ne las transacciones a la base de datos
	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}

	$obBD_con1->echoJson($response);
	exit();
}


//obtenemos los detalles
if (isset($a_migrar)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";

	$datos_mig = $obBD_con1->getArrayConsulta(60, "", $obBD_conexion);
	if (count($datos_mig) > 0) {
		$response['dat'] = $datos_mig;
	} else {
		$response['dat'] = "none";
	}

	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}

	$obBD_con1->echoJson($response);
	exit();
}

//obtenemos las facturas incluidas en un determinado abono
if (isset($migrar)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";

	$to_save = $obBD_con1->getRowConsulta(63, $Com_Cod, $obBD_conexion);
	$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $Cpp_Cod, 'Pag_Cod' => $Pag_Cod, 'Com_Cod' => $Com_Cod, 'Pag_Fec' => $Pag_Fec, 'Pag_Val' => $Pag_Val, 'Pag_Obs' => $Pag_Obs, 'Asi_Cod' => $to_save['Asi_Cod']), $obBD_conexion);

	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}

	$obBD_con1->echoJson($response);
	exit();
}

//obtenemos las facturas incluidas en un determinado abono
if (isset($migrado)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";

	//borramos todos los detallles que tengan Asi_Cod = 0
	$obBD_con1->operacionobBD(58, "", $obBD_conexion);

	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}
	$obBD_con1->echoJson($response);
	exit();
}


//Consultar anticipos disponibles
if (isset($loadAnticipos)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";
	$response['rows'] = $obBD_con1->getArrayConsulta(126,  $Prv_Cod_ant . "*" . $Ses_Emp_Cod, $obBD_conexion);
	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}
	$obBD_con1->echoJson($response);
	exit();
}


// Consultar facturas para el cruze de cuentas
if (isset($loadCruzeCuentas)) {
	$Cliente = $obBD_con1->getArrayConsulta(25, array('Prv_Cod' => $Prv_Cod_Cdc), $obBD_conexion);
	$Cli_Cod = 'null';
	if (count($Cliente) > 0) {
		foreach ($Cliente as $cli) {
			$Cli_Cod = $cli['Cli_Cod'];
		}
	}
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transacción";
	$rows = $obBD_con1->getArrayConsulta(26, array('Cli_Cod' => $Cli_Cod), $obBD_conexion);
	$filtradas = array();
	foreach ($rows as $deu) {
		if ((float)$deu['saldo'] != 0) {
			$filtradas[] = $deu;
		}
	}
	if (count($filtradas) > 0) {
		$response['rows'] = $filtradas;
	} else {
		$response['rows'] = 'none';
	}
	if ($obBD_con1->Error == 0) {
		$response['success'] = true;
	}
	$obBD_con1->echoJson($response);
	exit();
}
?>
<!DOCTYPE html>
<html>

<head>
	<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?Php echo "Ccxpp Lotes [EXA] "; ?></TITLE>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
	<script src="../VALIDACIONES/tes_val_alt_ccpp_lotes.js?a=46"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
	<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<style>
		.txt-green {
			color: #29a827;
		}

		.txt-red {
			color: #ff0000;
		}

		.txt-blue {
			color: #467de8;
		}

		.obs-mayus {
			text-transform: uppercase;
		}

		.btn-sg-pg {
			padding-right: 2;
		}

		#searchGrid .no_padding {
			padding: 0 !important;
		}

		#searchGrid .no_padding input[type="text"] {
			height: 23px;
			font-size: 14px;
			font-weight: bold;
			-moz-appearance: textfield !important;
		}

		#searchGrid .no_padding input[type="text"]::-webkit-outer-spin-button,
		#searchGrid .no_padding input[type="text"]::-webkit-inner-spin-button {
			-webkit-appearance: none !important;
			margin: 0 !important;
		}

		#searchGrid input[type="text"]:read-only {
			background-color: #a2a2a2;
			border: none;
		}

		.ccpp-comprobante-control {
			display: flex;
			align-items: center;
			flex-wrap: wrap;
			gap: 8px;
		}

		.ccpp-comprobante-btn {
			display: inline-flex;
			align-items: center;
			gap: 5px;
			margin: 0;
			padding: 4px 8px;
			border: 1px solid #b8c0c7;
			border-radius: 4px;
			background: linear-gradient(180deg, #fff 0%, #eef1f3 100%);
			color: #4f5b64;
			font-size: 11px;
			font-weight: 600;
			cursor: pointer;
			box-shadow: 0 1px 2px rgba(45, 55, 65, .12);
		}

		.ccpp-comprobante-btn:hover {
			border-color: #929da6;
			color: #303940;
			background: #e4e8eb;
		}

		.ccpp-comprobante-preview {
			display: none;
			width: 58px;
			height: 58px;
			border: 1px solid #cbd5df;
			border-radius: 4px;
			object-fit: cover;
		}

		.ccpp-comprobante-accion {
			display: none;
			padding: 2px 5px;
			border: 0;
			background: transparent;
			color: #54636e;
		}
	</style>
</head>

<body>
	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; Registrar pagos por lotes a proveedores</h3>
		</div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div id="listar_ccpp">
				<div class="row">
					<form name="searchCcpp" id="searchCcpp" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchCcpp','ajaxComprobante');">
						<div class="col-sm-6">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">Seleccionar Proveedor</legend>
								<div class="form-group">
									<label class="col-sm-4 control-label label-sm">C&eacute;dula/RUC:</label>
									<div class="col-sm-6">
										<input name="Prv_Cod" id="Prv_Cod" type="text" style="display:none;" />

										<div class="input-group input-group-xs">
											<input name="Prs_Ced" id="Prs_Ced" type="text" placeholder="Seleccione o cree un proveedor..." class="form-control input-xs" tabindex="1" readonly />
											<span class="input-group-btn">
												<button type="button" onclick="$('#proveedoresDialog').dialog('open');" class="btn btn-success btn-xs" title="Seleccionar Proveedor" tabindex="2"><span class="glyphicon glyphicon-list-alt"></span></button>
											</span>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label label-xs">Proveedor:</label>
									<div class="col-sm-6"><input name="nombre" id="nombre" class="form-control input-xs databind datatitle" readonly /></div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label label-xs">Direcci&oacute;n:</label>
									<div class="col-sm-6"><input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-xs databind datatitle" readonly /></div>
								</div>
							</fieldset>
						</div>
						<div class="col-sm-6">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">Filtros</legend>
								<div class="form-group">
									<label class="col-xs-2 control-label label-xs">Por Periodo:</label>
									<input type="text" name="por_peri" id="por_peri" value="n" style="display:none">
									<div class="col-xs-3">
										<div class="input-group">
											<span class="input-group-addon" style="padding:2px 5px 0px 5px;margin:0;line-height:0;">
												<input type="checkbox" id="f_periodo" name="f_periodo" onchange="cambiarFiltro()">
											</span>
											<select class="form-control input-xs" name="sel_per" id="sel_per" onchange="cambioPreiodoSearch('peri')" disabled>
												<?
												$periodos_rows = $obBD_con1->getArrayConsulta(45, "", $obBD_conexion);
												if (count($periodos_rows) > 0) {
													foreach ($periodos_rows as $row) {
														//echo "<option value='$row[Pec_Cod]' data-inicio='$row[Pec_Fei]' data-fin='$row[Pec_Fef]'>$row[anio]</option>";
														echo "<option value='{$row['Pec_Cod']}' data-inicio='{$row['Pec_Fei']}' data-fin='{$row['Pec_Fef']}'>" . utf8_encode($row['anio']) . "</option>";
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-xs-6">
										<div class="input-group input-group-xs">
											<span class="input-group-addon bold alert-info">Desde:</span>
											<input disabled onchange="cambioPreiodoSearch('txt')" name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="form-control input-xs datepicker databind" style="text-align: center;" />
											<span class="input-group-addon bold alert-info">Hasta:</span>
											<input disabled name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="form-control input-xs datepicker databind" style="text-align: center;" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
									<div class="col-xs-8 radioset opt_search">
										<input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" onchange="setSelVen('T')" alt="" />
										<label for="radsc1">Todos&nbsp;</label>
										<input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" onchange="setSelVen('V')" alt="" />
										<label for="radsc2">Vencidos</label>
										<input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" onchange="setSelVen('P')" alt="" />
										<label for="radsc3">Por Vencer</label>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-2 control-label">B&uacute;squeda:</label>
									<div class="col-xs-5">
										<div class="input-group">
											<select class="form-control input-xs" name="sel_ven" id="sel_ven">
												<option value="1">Todos</option>
											</select>
											<span class="input-group-btn">
												<button id="loadAnticiposSearch" type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Documento" tabindex="-1">
													<span class="glyphicon glyphicon-search"></span>
													<span>Buscar</span>
												</button>
											</span>
										</div>
									</div>
								</div>
							</fieldset>

							<div class="col-xs-8">
							</div>
							<div class="col-xs-4" style="margin-bottom: 10px">
								<div class="input-group">
									<input name="valorPagar" id="valorPagar" class="form-control input-xs databind datatitle" placeholder="Valor a pagar" />
									<span class="input-group-btn">
										<button type="button" onclick="asignarPago($('#valorPagar').val())" class="btn btn-success btn-xs" title="Asignar Valor">
											<span class="glyphicon glyphicon-ok"></span>
											<span>Asignar</span>
										</button>
									</span>
								</div>
							</div>

							<input name="order" type="hidden" value="" />
						</div>
					</form>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<table id="searchGrid" name="searchGrid"></table>
						<div id="sgPager"></div>
						<div class="Titulos2">
							<span id="plan-footer">
								<strong>Leyenda:</strong>
								<span class="glyphicon glyphicon-stop" style="color:#ff8a8a;"></span> Vencidos
							</span>
						</div>
						<br>
						<div class="">
							<button class="btn btn-sm btn-primary" onclick="gestionarPago()" title="Realizar pago de Facturas seleccionadas">
								<span class="glyphicon glyphicon-book"></span> Pagar</button>
						</div>
					</div>
				</div>
			</div>
			<div id="agregar_ccpp" hidden>
				<div class="row">
					<div class="col-sm-12">
						<div class="row">
							<form class="form-horizontal normal" name="formPagos" id="formPagos" method="post" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?',null,guardarPago)">
								<input name="tip_trans" id="tip_trans" value="add" type="text" style="display:none;" />
								<input name="Com_Cod" id="Com_Cod" value="add" type="text" style="display:none;" />
								<div class="col-sm-12">
									<div class="row">
										<div class="col-sm-6">
											<fieldset class="exa-fieldset">
												<legend class="Titulos2">Datos del asiento y proveedor</legend>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-sm required" for="Pec_Cod">Periodo contable:</label>
														<div class="col-sm-2">
															<select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" required="" onchange="setFecPeriodoCom()">
																<?php $rows_periodos = $obBD_con1->getArrayConsulta(3, "", $obBD_conexion);
																if (count($rows_periodos) > 0) {
																	foreach ($rows_periodos as $row) {
																?>
																		<?php
																		echo "<option value='{$row['Pec_Cod']}' data-pla-cod='{$row['Pla_Cod']}' data-pec-fei='{$row['Pec_Fei']}' data-pec-fef='{$row['Pec_Fef']}' data-periodo='" . utf8_encode($row['priodo_m']) . "'>" . utf8_encode($row['priodo_m']) . "</option>";
																		?>

																		<!--? echo "<option value='$row[Pec_Cod]' data-pla-cod='$row[Pla_Cod]' data-pec-fei='$row[Pec_Fei]' data-pec-fef='$row[Pec_Fef]'  data-periodo='$row[priodo_m]'>$row[priodo_m]</option>"; ?-->

																<?php }
																} ?>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-sm">C&eacute;dula/RUC:</label>
														<div class="col-sm-4">
															<input name="agg_Prv_Cod" id="agg_Prv_Cod" type="text" style="display:none;" />
															<input name="agg_Prs_Ced" id="agg_Prs_Ced" type="text" class="form-control input-xs" tabindex="1" readonly />
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs">Proveedor:</label>
														<div class="col-sm-6">
															<input name="agg_nombre" id="agg_nombre" class="form-control input-xs databind datatitle" readonly />
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs required" for="Com_Con">Concepto:</label>
														<div class="col-sm-6">
															<textarea id="Com_Con" name="Com_Con" class="form-control input-xs obs-mayus" style="resize: none;" required=""></textarea>
														</div>
														<div class="col-sm-3">
															<label>
																<input id="chkObs" type="checkbox" onchange="$('#Com_Con').val($('#Com_Con').data($(this).is(':checked')?'observacion':'facturas'))" class="check-big"> Copiar Obs.</label>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs required" for="Com_Obs">Observaci&oacute;n:</label>
														<div class="col-sm-6">
															<textarea id="Com_Obs" name="Com_Obs" class="form-control input-xs obs-mayus" style="resize: none;" required=""></textarea>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs">Anticipos a Favor:</label>
														<div class="col-sm-3">
															<span id="ant_msg" class="form-control input-xs txtRight">$ 0.00</span>
														</div>
													</div>
												</div>
											</fieldset>

										</div>
										<div class="col-sm-6">
											<fieldset class="exa-fieldset">
												<legend class="Titulos2">Datos del pago</legend>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs required" for="Com_Fec">Fecha del comprobante:</label>
														<div class="col-sm-2">
															<input id="Com_Fec" name="Com_Fec" class="form-control input-xs datepicker" placeholder="yy-mm-dd" type="text" onchange="validar_fecha_comprobante()" />
														</div>
														<div class="col-sm-4 txt-blue" style="padding-left:0;">
															<!--<label id="saldo_info" class="control-label label-xs txt-red">Existe anticipo a su favor $ <span id="saldo_info2">0.00</span></label>-->
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-sm required" for="Tia_Cod">Tipo Comprobante:</label>
														<div class="col-sm-4">
															<select id="Tia_Cod" name="Tia_Cod" class="form-control input-xs" required="" onchange="">
																<?Php
																$row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(4, "ALL", $obBD_conexion);
																foreach ($row_rs_tipo_asien2 as $row) { ?>
																	<option value="<?php echo $row['Tia_Cod']; ?>" data-abr="<?php echo $row['Tia_Abr']; ?>">
																		<!--?php echo $row['Tia_Des'] ?-->
																		<?php echo utf8_encode($row['Tia_Des']); ?>
																	</option>
																<?php } ?>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-sm required" for="Pag_Cod">Tipo de pago:</label>
														<div class="col-sm-3">

															<select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs" required="" onchange="enableDisableCampos()">
																<?Php
																$row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(5, "", $obBD_conexion);
																foreach ($row_rs_tipo_asien2 as $row) { ?>
																	<option value="<?php echo $row['Pag_Cod']; ?>" data-abr="<?php echo $row['Pag_Abr']; ?>">
																		<?php echo utf8_encode($row['Pag_Des']) ?>
																	</option>
																<?php } ?>
															</select>
														</div>
														<div id="cont_anticipo_info" class="col-sm-6 txt-blue" style="padding-left:0;" hidden>
															<span class="glyphicon glyphicon-info-sign" style="font-size:10px;" title="Cant. disponible en anticipos para este proveedor"></span>
															<label class="control-label label-xs">Disponible $
																<span id="anticipo_info">0.00</span>
															</label>
														</div>
														<div id="cont_ccc_info" class="col-sm-6 txt-blue" style="padding-left:0;" hidden>
															<span class="glyphicon glyphicon-info-sign" style="font-size:10px;" title="Cant. disponible para el cruce de cuentas con este proveedor"></span>
															<label class="control-label label-xs">Deuda del proveedor $
																<span id="ccc_info">0.00</span>
															</label>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-sm required" for="Ban_Cod">Banco:</label>
														<div class="col-sm-4">
															<select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs ed_element ed_CHE ed_TRF ed_TDC ed_NDD" required="" onchange="" disabled>
																<?Php
																$row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(9, array('Ban_Tip' => 'B'), $obBD_conexion);
																foreach ($row_rs_tipo_asien2 as $row) { ?>
																	<option value="<?php echo $row['Ban_Cod']; ?>" data-des="<?php echo $row['Pld_Des']; ?>" data-cue="<?php echo $row['Ban_Cue']; ?>" data-cdc="<?php echo $row['Pld_Cdc']; ?>" data-pla="<?php echo $row['Pld_Cod']; ?>">
																		<?php echo $row['Pld_Des'] ?>-
																		<?php echo $row['Ban_Cue'] ?>
																	</option>
																<?php } ?>
															</select>
														</div>
													</div>
												</div>

												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs" for="Num_Doc">Nro. Documento:</label>
														<div class="col-sm-2">
															<input id="Num_Doc" name="Num_Doc" class="form-control input-xs ed_elemento ed_TRF" placeholder="002554" type="text" disabled />
														</div>
													</div>
												</div>

												<div class="row" id="grupoPagImg" hidden>
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs">Comprobante:</label>
														<div class="col-sm-7">
															<div class="ccpp-comprobante-control">
																<label for="Pag_img_archivo" class="ccpp-comprobante-btn">
																	<span class="glyphicon glyphicon-picture"></span> Cargar imagen
																</label>
																<input type="file" id="Pag_img_archivo" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" onchange="seleccionarComprobanteCcpp(this)">
																<span id="Pag_img_nombre" class="text-muted" style="font-size:11px;">Ningún archivo</span>
																<img id="Pag_img_preview" class="ccpp-comprobante-preview" alt="Vista previa del comprobante">
																<button type="button" id="Pag_img_ver" class="ccpp-comprobante-accion" onclick="verComprobanteCcppActual()" title="Ampliar imagen">
																	<span class="glyphicon glyphicon-eye-open"></span>
																</button>
																<button type="button" id="Pag_img_quitar" class="ccpp-comprobante-accion" onclick="quitarComprobanteCcpp()" title="Quitar imagen" style="color:#c0392b;">
																	<span class="glyphicon glyphicon-remove"></span>
																</button>
															</div>
															<small class="text-muted">Opcional · JPG, PNG, WEBP o GIF · máximo 5 MB</small>
														</div>
													</div>
												</div>

												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs required" for="Che_Fec">Fecha del cheque:</label>
														<div class="col-sm-2">
															<input id="Che_Fec" name="Che_Fec" class="form-control input-xs datepicker ed_element ed_CHE" placeholder="yy-mm-dd" type="text" disabled />
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs required">No. cheque:</label>
														<div class="col-sm-2">
															<div class="input-group input-group-xs">
																<input type="text" id="Che_Num" name="Che_Num" onchange="" class="form-control input-xs ed_element ed_CHE" onkeyup="verificarNoCheque(this.value)" onkeypress="return soloNumeros(event)" autocomplete="off" disabled>
																<span class="input-group-addon">
																	<i id="indicadorChe" class=""></i>
																</span>
															</div>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs required">Beneficiario:</label>
														<div class="col-sm-4">
															<input type="text" id="Che_Ben_N" name="Che_Ben_N" class="form-control input-xs ed_element ed_CHE" autocomplete="off" disabled>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs required" for="Com_Val_pago">Valor:</label>
														<div class="col-sm-2">
															<input id="Cpp_Cod" name="Cpp_Cod" type="text" hidden />
															<input id="Com_Val" name="Com_Val" type="text" hidden />
															<input id="Com_Val_dism" name="Com_Val_dism" type="text" value="none" hidden />
															<input id="lim_val_pago" name="lim_val_pago" type="text" value="none" hidden />
															<input id="lim_val_pago_cc" name="lim_val_pago_cc" type="text" value="none" hidden />
															<input id="Com_Val_pago" name="Com_Val_pago" class="form-control input-xs" type="text" onchange="cambioValPago($(this));" onkeypress="return  validar_decimal(event)" autocomplete="off" />



														</div>
														<div class="col-sm-6 txt-blue" style="padding-left:0;">
															<span class="glyphicon glyphicon-info-sign" style="font-size:10px;" title="Debe completar esta cantidad para realizar el pago"></span>
															<label class="control-label label-xs" title="Debe completar esta cantidad para realizar el pago">Valor a pagar $
																<span id="saldo_info1">0.00</span> - </label>
															<label id="saldo_info" class="control-label label-xs txt-red">(Total agregado: $
																<span id="saldo_info2">0.00</span>)</label>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-sm-6">
													</div>
													<div class="col-sm-6">
														<a id="btn_addpago" class="btn btn-success btn-xs" onclick="preAddPago()">
															<span class="glyphicon glyphicon-arrow-down"></span> Agregar pago</a>
													</div>
												</div>
											</fieldset>
										</div>
									</div>
								</div>
							</form>
						</div>
						<div class="row">
							<div class="col-sm-12">
								<table id="pagosGrid" name="pagosGrid"></table>
								<table id="pagosGridPager"></table>
								<div class="Titulos2">
									<span id="plan-footer">
										<strong>Leyenda:</strong>
										<span class="glyphicon glyphicon-stop" style="color:#ff8a8a;"></span>Cheques Protestados
									</span>
								</div>
								<br>
								<div class="">
									<a class="btn btn-inverse btn-xs" onclick="limpiarPagos();moveToList();">
										<span class="glyphicon glyphicon-arrow-left"></span> Atras</a>
									<a class="btn btn-success btn-xs" onclick="$('#formPagos').formSubmit();">
										<span class="glyphicon glyphicon-book"></span> Guardar </a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Fin de cancelar el anticipo -->
			<!-- Asignar el total que se va a cancelar a cada anticipo -->
			<!--div id="agregar_anticipos" style="display: none;">
				<div class="col-sm-12">
					<form class="form-horizontal normal" name="formPagos_anticipo" id="formPagos_anticipo" action="javascript:$('#Lista_Anticipos').Search('#formPagos_anticipo','loadAnticipos');">
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">Datos de anticipos del proveedor</legend>
							<div class="col-sm-4">
							</div>
							<div class="col-sm-8">
								<div class="form-group">
									<input name="Prv_Cod_ant" id="Prv_Cod_ant" type="text" style="display:none;" />
									<label class="col-sm-4 control-label" style="margin-bottom: 7px;">Monto a pagar $:</label>
									<div class="col-xs-8 text-right">
										<div class="col-sm-4">
											<input class="form-control input-xs" type="text" name="monto_total" id="monto_total" readonly>
										</div>
										<div class="col-sm-4">
											<button type="button" id="calcular" class="btn btn-success btn-xs" onclick="calcularMontosPagar()">
												<span class="glyphicon glyphicon-check"></span> Asignar
											</button>
										</div>
										<div class="col-sm-4">
											<button class="btn btn-success btn-xs" type="button" onclick="this.form.submit()"> <span class="glyphicon glyphicon-refresh"></span> Recargar</button>
										</div>

									</div>
								</div>
							</div>
						</fieldset>
						<div class="row">
							<div class="col-sm-12">
								<table id="Lista_Anticipos" style="width: 100%!important;"></table>
								<div id="Pag_Cli"></div>
							</div>
						</div>
					</form>
				</div>
			</div-->
			<!-- Fin de cancelar el anticipo -->

			<!-- Asignar el total que se va a cruzar con facturas -->
			<div id="agregar_cruze_cuentas" style="display: none;">
				<div class="col-sm-12">
					<form class="form-horizontal normal" name="formPago_cdc" id="formPago_cdc" action="javascript:$('#lista_facturas_cruze').Search('#formPago_cdc','loadCruzeCuentas');">
						<!--form id="frm_prod_ven" name="frm_prod_ven" class="form-horizontal normal" action="javascript:$('#container').Search('#frm_prod_ven','prodAjax');"-->
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">Datos de ventas para hacer el cruze</legend>
							<div class="col-sm-4"></div>
							<div class="col-sm-8">
								<div class="form-group">
									<input name="Prv_Cod_Cdc" id="Prv_Cod_Cdc" type="text" style="display:none;" />

									<label class="col-sm-4 control-label" style="margin-bottom: 7px;">Monto a pagar $:</label>
									<div class="col-xs-8 text-right">
										<div class="col-sm-4">
											<input class="form-control input-xs" type="text" name="monto_total_cruze" id="monto_total_cruze" readonly>
										</div>
										<div class="col-sm-4">
											<button type="button" id="calcular" class="btn btn-success btn-xs" onclick="calcularMontosPagarCruze()">
												<span class="glyphicon glyphicon-check"></span> Asignar
											</button>
										</div>
										<div class="col-sm-4">
											<button class="btn btn-success btn-xs" type="button" onclick="this.form.submit()"> <span class="glyphicon glyphicon-refresh"></span> Recargar</button>
										</div>

									</div>
								</div>
							</div>
						</fieldset>
						<div class="row">
							<div class="col-sm-12">
								<table id="lista_facturas_cruze" style="width: 100%!important;"></table>
								<div id="Pag_Cli_CDC"></div>
							</div>
						</div>
					</form>
				</div>
			</div>
			<!-- Fin de cancelar con cruze de facturas -->


		</div>
	</div>

	<div id="proveedoresDialog" title="B&uacute;squeda de Proveedores">
		<form class="form-horizontal normal"> </form>
	</div>
	<div id="agregar_anticipos" title="Pagar con Anticipos">
		<form class="form-horizontal normal" name="formPagos_anticipo" id="formPagos_anticipo" action="javascript:$('#Lista_Anticipos').Search('#formPagos_anticipo','loadAnticipos');">
			<fieldset class="exa-fieldset">
				<legend class="Titulos2">Asignaci&oacute;n de anticipos</legend>
				<input name="Prv_Cod_ant" id="Prv_Cod_ant" type="hidden" />
				<div class="form-group">
					<label class="col-xs-3 control-label label-xs">Monto a pagar $:</label>
					<div class="col-xs-3">
						<input class="form-control input-xs" type="text" name="monto_total" id="monto_total" readonly>
					</div>
					<div class="col-xs-6 text-right">
						<button type="button" id="calcular" class="btn btn-success btn-xs" onclick="calcularMontosPagar()">
							<span class="glyphicon glyphicon-check"></span> Asignar
						</button>
						<button class="btn btn-success btn-xs" type="button" onclick="cargarDatosAnticipos(true);">
							<span class="glyphicon glyphicon-refresh"></span> Recargar
						</button>
						<button type="button" id="btn_siguiente" name="btn_siguiente" class="btn btn-primary btn-xs" onclick="validarAnticiposModal();">
							<span class="glyphicon glyphicon-arrow-right"></span> Siguiente
						</button>
					</div>
				</div>
			</fieldset>
			<div class="condensed">
				<table id="Lista_Anticipos"></table>
				<div id="Pag_Cli"></div>
			</div>
		</form>
	</div>
	<div id="cuentasDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
	<div id="comprobanteCcppDialog" title="Comprobante de transferencia">
		<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:300px;padding:12px;background:#f3f5f7;border-radius:6px;">
			<img id="comprobanteCcppGrande" alt="Comprobante de transferencia" style="display:block;max-width:100%;max-height:72vh;border-radius:5px;box-shadow:0 5px 20px rgba(0,0,0,.2);">
			<a id="comprobanteCcppDescargar" class="btn btn-success btn-sm" href="#" download style="margin-top:12px;">
				<span class="glyphicon glyphicon-download-alt"></span> Descargar imagen
			</a>
		</div>
	</div>
	<div id="successDialog" title="Mensaje del Sistema">
		<center>
			<h2>El Comprobante se ha registrado con Exito!</h2>
		</center>
		<center>
			<button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-danger fileinput-button" style="display: inline;">
				<i class="icon-ban-circle icon-white"></i>
				<span>Continuar</span>
			</button>
			<a id="impCompr" target="_blank" href="" style="display: inline;" title="Imprimir Comprobante">
				<span class="btn btn-success start">
					<i class="icon-print icon-white"></i>
					<span>Imprimir</span>
				</span>
			</a>
			<br>
			<br>
			<fieldset class="exa-fieldset" id="siche" hidden>
				<legend class="Titulos2">Impresi&oacute;n de Cheques</legend>
				<div>
					<center>
						<h5>Eliga el cheque que desea imprimir!</h5>
					</center>
					<div class="row">
						<div class="form-group">
							<div class="col-sm-3"></div>
							<div class="col-sm-6">
								<div class="input-group">
									<select id="Che_imp" name="Che_imp" class="form-control input-xs" onchange="cambiarChe()">
									</select>
								</div>
							</div>
						</div>
					</div>
					<br>
					<div class="row">
						<?php $ruta = './' . (file_exists('cheques/' . $Ses_Emp_Cod) ? "cheques/$Ses_Emp_Cod/" : ''); ?>
						<div id="conten_bancos_imp">
							<table style="margin-bottom:10px;" cellpadding="1" border="1">
								<tr>
									<td align="center" class="ui-widget-content" colspan="7">
										<b>&nbsp; plantillas &nbsp;</b>
									</td>
								</tr>
								<tr id="impchetd">
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_mac_1.0.php" href="" target="_blank" title="Banco de Machala">
											<img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_pac_1.0.php" href="" target="_blank" title="Banco del Pacifico">
											<img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_rum_1.0.php" href="" target="_blank" title="Banco del Rumiñahui">
											<img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_gua_1.0.php" href="" target="_blank" title="Banco del Guayaquil">
											<img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_pch_1.0.php" href="" target="_blank" title="Banco del Pichincha">
											<img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_int_1.0.php" href="" target="_blank" title="Banco Internacional">
											<img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32" />
										</a>
									</td>

									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_aust_1.0.php" href="" target="_blank" title="Banco del Austro">
											<img src="../../mascaras/model1/imagenes/32x32/ban_aust.jpg" width="32" height="32" />
										</a>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</fieldset>
		</center>
	</div>

	<div id="verPagosDialogMod" title="Pagos">
		<div class="row">
			<div class="col-sm-12">
				<fieldset class="exa-fieldset">
					<legend class="Titulos2">Datos del Abono</legend>
					<form id="verPagosForm" class="form-horizontal normal">
						<div class="row">
							<div class="col-sm-7">
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">Proveedor:</label>
									<div class="col-xs-8">
										<input type="text" id="prov_show" class="form-control input-xs" readonly>
										<input type="text" id="Com_Cod_view" style="display:none">
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">No. Compr.:</label>
									<div class="col-xs-8">
										<input type="text" id="compr_show" class="form-control input-xs" readonly>
									</div>
								</div>
							</div>
							<div class="col-sm-5">
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">C&eacute;dula/R.U.C.:</label>
									<div class="col-xs-8">
										<input type="text" id="ruc_show" class="form-control input-xs" readonly>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">Fecha:</label>
									<div class="col-xs-8">
										<input type="text" id="fec_show" class="form-control input-xs" readonly>
									</div>
								</div>
							</div>
						</div>
					</form>
				</fieldset>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<fieldset class="exa-fieldset">
					<legend class="Titulos2">Observaci&oacute;n</legend>
					<div class="form-group">
						<div class="col-xs-12">
							<textarea id="obs_show" class="form-control input-xs" readonly></textarea>
						</div>
					</div>
				</fieldset>
			</div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-12">
				<div id="tabs_abo_det" class="ui-tab-fix">
					<ul style="font-size: 12px;" role="tablist">
						<li id="ant_detasi">
							<a href="#ant_det_asi">Asientos</a>
						</li>
						<li id="ant_detche">
							<a href="#ant_det_che">Cheques</a>
						</li>
					</ul>
					<div id="ant_det_asi">
						<div class="row">
							<div class="col-sm-12" style="padding-top: 10px;">
								<table id="showPagosAsi" name="showPagosAsi"></table>
							</div>
						</div>
					</div>
					<div id="ant_det_che">
						<div class="row">
							<div class="col-sm-12" style="padding-top: 10px;">
								<table id="showPagosChe" name="showPagosChe"></table>
								<div class="Titulos2">
									<span id="plan-footer">
										<strong>Leyenda:</strong>
										<span class="glyphicon glyphicon-stop" style="color:#ff8a8a;"></span> Protestados
									</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div id="imprimir_ccpp" style="display:none">
		<div style="text-align:center">
			<h4 style="margin-bottom:0;padding-bottom:0;">
				<b>ESTADO DE CUENTAS POR PAGAR</b>
			</h4>
			<span style="margin-top:0;padding-top:0;font-size:14px;">
				<b>Historial de abonos a proveedores</b>
			</span>
		</div>
		<div style="font-size:13px;">
			<table>
				<tr>
					<td align="right">
						<b>EMPRESA:</b>
					</td>
					<td>
						<span>
							<? echo $Ses_Emp_Nom; ?>
						</span>
					</td>
				</tr>
				<tr>
					<td align="right">
						<b>EMISI&Oacute;N:</b>
					</td>
					<td>
						<span>
							<? $fecha = explode('-', $hoy);
							echo dias(calcula_numero_dia_semana($fecha[2], $fecha[1], $fecha[0]), 1) . ', ' . $fecha[2] . ' de ' . mes($fecha[1], 1) . ' de ' . $fecha[0]; ?>
						</span>
					</td>
				</tr>
			</table>
			<br>
			<table width="100%">
				<thead>
					<th style='border: black 1px solid;'>NO. COMPR.</th>
					<th style='border: black 1px solid;'>FECHA</th>
					<th style='border: black 1px solid;'>T. PAG.</th>
					<th style='border: black 1px solid;'>DOC.</th>
					<th style='border: black 1px solid;'>CTA. BANCARIA/ BANCO</th>
					<th style='border: black 1px solid;'>FECHA CH.</th>
					<th style='border: black 1px solid;'>SALDOS</th>
				</thead>
				<tbody id="tabla_export">
				</tbody>
			</table>
		</div>
	</div>

	<div id="anular_abo_dialog" title="Anular o protestar chueque">
		<div class="row">
			<div class="col-sm-12">
				<center>
					<h5>Anular Abono</h5>
				</center>
			</div>
		</div>
		<div class="row">

		</div>
		<div class="row">
			<div class="col-sm-12" style="text-align:right">
				<a class="btn btn-xs btn-success" onclick="">
					<span class="glyphicon glyphicon-ok"></span> Aceptar</a>
				<a class="btn btn-xs btn-danger" onclick="">
					<span class="glyphicon glyphicon-remove"></span> Cancelar</a>
			</div>
		</div>
	</div>

	<div id="altr_ant" title="Pago con anticipo">
		<center>
			<h4>Tiene disponible $
				<span id="altr_ant_info"></span> en anticipos!
			</h4>
		</center>
		<center>
			<button type="button" onclick="$('#altr_ant').dialog('close');" class="btn btn-danger fileinput-button" style="display: inline;">
				<i class="icon-ban-circle icon-white"></i>
				<span>Cancelar</span>
			</button>
			<a onclick="usarAnticipo()" style="display: inline;" title="Pagar con anticipo">
				<span class="btn btn-success start">
					<i class="icon-print icon-white"></i>
					<span>Usar anticipo</span>
				</span>
			</a>
		</center>
	</div>

	<script type="text/javascript">
		function asignarPago(valor) {
			limpiarPago();
			var valorNumero = parseFloat(valor);
			var i = 0;
			$("#searchGrid tbody tr[role='row']").each(function(i) {
				if (i != 0) {
					var codFila = $(this).attr('id');
					var sal = $(this).find("td[aria-describedby='searchGrid_Saldo']").text().replace(',', '');
					var saldo = sal.substr(2, sal.lenght);

					$("#sg_act_" + codFila).prop('checked', true)
					$("#sg_pago_" + codFila).removeAttr("readonly");
					var saldoNumero = parseFloat(saldo);
					if (valorNumero >= saldoNumero) {
						$("#sg_pago_" + codFila).val(saldoNumero.toFixed(2));
						valorNumero -= saldoNumero;
					} else {
						$("#sg_pago_" + codFila).val(parseFloat(valorNumero).toFixed(2));
						return false;
					}
				}
				i++;
			});
			actualizarTotalesSG();
		}

		function limpiarPago() {
			var i = 0;
			$("#searchGrid tbody tr[role='row']").each(function(i) {
				if (i != 0) {
					var codFila = $(this).attr('id');
					$("#sg_act_" + codFila).prop('checked', false);
					$("#sg_pago_" + codFila).attr("readonly", "");
					$("#sg_pago_" + codFila).val("0.00");
				}
				i++;
			});
		}
	</script>
	<script>
	</script>
</body>

</html>