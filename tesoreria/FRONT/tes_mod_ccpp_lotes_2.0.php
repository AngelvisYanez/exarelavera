<?php

/**
 * @abstract Permite realizar la modificacion de Anticipos Manuales
 * @author Erik Cordova
 * @version 1.0
 * Fecha de creacion  2017-12-06
 * Actualizado: Wilson Belduma
 * Fecha de actualizacion 2024-06-27
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_ccpp_lotes_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Ccpp($Ses_Dat_Dis);
/**
 * Creaciï¿½n del Objeto para consultas
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
if (isset($modPago)) {
	$obBD_conexion2 = new Class_Log_Conexion_Ccpp($Ses_Dat_Dis);
	$obBD_con2 =  new Class_Log_Datos_Ccpp;
	try {

		// $obBD_con1->debug(true);
		$obBD_con1->validaCierrePeriodo('det_ccpp_p', 'Pag_Fec', 'Cpp_Cod', $Com_Fec, null, $obBD_conexion);

		$response = array('success' => false);
		$response['arrayche'] = array();
		$response['bnd_che'] = false;

		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);

		//generamos el numero de comprobante
		$var_mes = explode('-', $Com_Fec);

		if ($Tia_Cod != $Tia_Cod_temp) {
			$Com_Num = $obBD_con2->codigoComprAuto($Tia_Cod, $Pec_Cod, $var_mes[1], $obBD_conexion2);
		}
		$Cliente = $obBD_con2->getArrayConsulta(25, array('Prv_Cod' => $agg_Prv_Cod), $obBD_conexion2);

		//asignamos el codigo de cliente de ses proveedor en caso de tenerlo
		$Cli_Cod = 'null';
		if (count($Cliente) > 0) {
			foreach ($Cliente as $cli) {
				$Cli_Cod = $cli['Cli_Cod'];
			}
		}

		//actualizamos el comprobante
		$obBD_con1->operacionobBD(33, array('Com_Cod' => $Com_Cod, 'Pec_Cod' => $Pec_Cod, 'Com_Num' => $Com_Num, 'Com_Fec' => $Com_Fec, 'Com_Con' => $Com_Con, 'Com_Val' => $Com_Val, 'Com_Obs' => $Com_Obs, 'Tia_Cod' => $Tia_Cod, 'Num_Doc' => $Num_Doc), $obBD_conexion);

		$pagosCuotas = $obBD_con2->getArrayConsulta('det_ant_ccpp.selectWhere', array('where' => array('det_ant_ccpp.Com_Cod' => $Com_Cod)), $obBD_conexion2, true);
		$obBD_con2->echoLog($pagosCuotas);

		foreach ($pagosCuotas as $pCuo) {
			$pAprov = $obBD_con2->getArrayConsulta('pago_anticipo_proveedores.selectWhere', array('where' => array('Pap_Cod' => $pCuo['Pap_Cod'])), $obBD_conexion2, true);
			$obBD_con2->echoLog($pAprov);
			$atpCod = $pAprov[0]['Atp_Cod'];
			if ($pAprov[0]['Pap_Val'] == $pCuo['Dac_Val']) {
				$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pAprov[0]['Pap_Cod'], 'Atp_Cod' => $pAprov[0]['Atp_Cod'], 'Pap_Est' => 'A'), $obBD_conexion, true);
			} else {
				//Verificamos si se cambiaron todos los estados en pagos_anticipos_proveedores
				$pCts = $obBD_con2->getArrayConsulta('det_ant_ccpp.selectWhere', array('where' => array('det_ant_ccpp.Atp_Cod' => $atpCod)), $obBD_conexion2, true);
				$obBD_con2->echoLog($pCts);
				$counter = 0;
				foreach ($pCts as &$ct) {
					if ($ct['Pap_Cod'] == $pCuo['Pap_Cod']) {
						$counter += 1;
					}
				}
				unset($ct);
				$obBD_con2->echoLog($counter);
				if ($counter > 1) {
					$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pAprov[0]['Pap_Cod'], 'Atp_Cod' => $pAprov[0]['Atp_Cod'], 'Pap_Est' => 'U'), $obBD_conexion, true);
				} else {
					$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pAprov[0]['Pap_Cod'], 'Atp_Cod' => $pAprov[0]['Atp_Cod'], 'Pap_Est' => 'A'), $obBD_conexion, true);
				}
			}
		}
		$anticipos_del = $obBD_con2->getArrayConsulta(37, array('Com_Cod' => $Com_Cod), $obBD_conexion2);
		$obBD_con2->echoLog($counter);
		foreach ($anticipos_del as $ant_del) {
			if ($counter > 1) {
				//actualizamos el estado del anticipo utilizado
				$obBD_con1->operacionobBD(74, array('Atp_Cod' => $ant_del['Atp_Cod']), $obBD_conexion);
			} else {
				//actualizamos el estado del anticipo utilizado
				$obBD_con1->operacionobBD(38, array('Atp_Cod' => $ant_del['Atp_Cod']), $obBD_conexion);
			}
		}
		//throw new Exception("The field is undefined.");

		//eliminamos pagos con anticipo
		$obBD_con1->operacionobBD(35, array('Com_Cod' => $Com_Cod), $obBD_conexion);

		//eliminamos pagos con cruce de cuentas
		$obBD_con1->operacionobBD(40, array('Com_Cod' => $Com_Cod), $obBD_conexion);

		$asi_temp = "";
		foreach ($save_p as $pago) {
			if ($pago['grid_tipp'] == 'pago') {
				if ($pago['Pag_Abr'] == 'CHE') {
					if ($pago['Asi_Cod'] != "") {
						$cheques_agregados = $obBD_con2->getArrayConsulta(55, array('Asi_Cod' => $pago['Asi_Cod']), $obBD_conexion2);
						if (count($cheques_agregados) > 0) {
							$asi_temp .= "asientos.Asi_Cod != " . $pago['Asi_Cod'] . " and ";
						}
					}
				}
			}
		}

		//eliminamos los asientos de cada pago de este comprobante
		$obBD_con1->operacionobBD(36, array('Com_Cod' => $Com_Cod, 'Asi_Cod' => $asi_temp), $obBD_conexion);
		$obBD_con1->operacionobBD(64, $Com_Cod, $obBD_conexion);
		if (isset($Com_Cod)) {
			/* Delete registros det_reposicion de los pagos existentes con el Com_Cod */
			$obBD_con1->operacionobBD('det_reposicion.deleteWhere', array('Com_Cod' => $Com_Cod, 'Dre_Tip' => 'P'), $obBD_conexion);
		}
		$contador_cheque = 0;
		$cntcpp = 0;
		$acum = 0;
		$pag_sald_temp = ($save_cp['' . intval($cntcpp)]['Pag_Val'] * 1) + ($save_cp['' . intval($cntcpp)]['Saldo'] * 1);
		$bandera_ex = false;
		$bnd_ant = false;
		$bandera_exD = false;

		foreach ($save_p as $pago) {
			if ($pago['grid_tipp'] == 'pago') {
				$ultimo_asiento = "";

				//*********************************************************************************************************************

				//en caso de haber un anticipo entre los pagos
				if ($pago['Pag_Abr'] == 'ANT') {

					$anticipos_no_utl = $obBD_con2->getArrayConsulta(22, array('Prv_Cod' => $agg_Prv_Cod), $obBD_conexion2, true);
					$contador_ant = 0;
					$saldoExistente = 0;
					$valorEditado = 0;
					$valorDeCuotas = 0;
					$vrfc = false;
					$checkSiT = false;
					$valorChange = 0;
					$valorSaldo = 0;
					foreach ($anticipos_no_utl as $ant) {
						if ((float)$contador_ant < (float)$pago['Haber']) {

							$ant_utilizados = $obBD_con2->getRowConsulta(24, array('Prv_Cod' => $agg_Prv_Cod, 'Atp_Cod' => $ant['Atp_Cod']), $obBD_conexion2, true);
							$ant_utl_val = (float)$ant_utilizados['tot_dac'];
							$disponibleSaldo = ($ant['ttfinal'] * 1) - ($ant_utl_val * 1);

							if ((round(($disponibleSaldo * 1), 2)) <= (float)$pago['Haber']  && $saldoExistente == 0) {
								$obBD_con1->echoLog('si');
								//cambiamos el estado del anticipo a utilizado
								$obBD_con1->operacionobBD(23, array('Atp_Cod' => $ant['Atp_Cod'], 'Atp_Est' => "C"), $obBD_conexion);
								$saldoExistente = (float)$pago['Haber'] - ((float)$ant['ttfinal'] - (round(($ant_utl_val * 1), 2) * 1));
								$pAprov = $obBD_con1->getArrayConsulta('pago_anticipo_proveedores.selectWhere', array('where' => array('Atp_Cod' => $ant['Atp_Cod'])), $obBD_conexion, true);
								foreach ($pAprov as &$pag) {
									if ($pag['Pap_Est'] != 'C') {
										$pagoSaldo = array();
										//Verifico los pagos en det_ant_ccpp con ese PAP_COD
										$pagoSaldo = $obBD_con1->getArrayConsulta('det_ant_ccpp.selectWhere', array('where' => array('Pap_Cod' => $pag['Pap_Cod'])), $obBD_conexion, true);
										if (count($pagoSaldo) > 0) {
											foreach ($pagoSaldo as &$ps) {
												$valorDeCuotas += ($ps['Dac_Val'] * 1);
											}
											unset($ps);
										} else {
											$valorDeCuotas = 0;
										}
										$saldoE = (($pag['Pap_Val'] * 1) - ($valorDeCuotas * 1));
										$obBD_con1->echoLog('-1111-');
										$obBD_con1->echoLog($saldoE);
										$obBD_con1->echoLog($pago['Haber']);
										if ($checkSiT) {
											$valorChange = $valorEditado;
											$valorSaldo = $valorEditado;
										} else {
											$valorChange = (float)$pago['Haber'];
											$valorSaldo = (float)$pago['Haber'];
										}
										if (($saldoE * 1) >= ((float)$valorChange) && (float)$contador_ant < ((float)$pago['Haber'])) {
											$obBD_con1->echoLog('-1-');
											$obBD_con1->echoLog($saldoE);
											$obBD_con1->echoLog($valorChange);
											$obBD_con1->echoLog($valorSaldo);
											$obBD_con1->echoLog($saldoExistente);
											$contador_ant += ($valorEditado);
											$saldoExistente = ($valorEditado);
											if ($saldoExistente == ($pag['Pap_Val'] * 1)) {
												$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pag['Pap_Cod'], 'Atp_Cod' => $pag['Atp_Cod'], 'Pap_Est' => 'C'), $obBD_conexion, true);
											} else {
												if (($saldoExistente * 1) > 0) {
													$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pag['Pap_Cod'], 'Atp_Cod' => $pag['Atp_Cod'], 'Pap_Est' => 'U'), $obBD_conexion, true);
												} else {
													$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pag['Pap_Cod'], 'Atp_Cod' => $pag['Atp_Cod'], 'Pap_Est' => 'C'), $obBD_conexion, true);
												}
											}
											if (($saldoExistente * 1) == 0) {
												$valorEditado = $saldoE;
											}
											//insertamos un detalle de pago con anticipos
											$obBD_con1->operacionobBD(21, array('Dac_Val' => ($valorEditado), 'Com_Cod' => $Com_Cod, 'Atp_Cod' => $ant['Atp_Cod'], 'Pap_Cod' => $pag['Pap_Cod']), $obBD_conexion, true);
											break;
										} else {
											$obBD_con1->echoLog('-2-');
											$obBD_con1->echoLog($saldoE);
											$stdo = 'C';
											if (($saldoE * 1) > $valorEditado &&  ($contador_ant * 1) > 0) {
												$saldoE = $valorEditado;
												$stdo = 'U';
											}
											$obBD_con1->operacionobBD(21, array('Dac_Val' => ($saldoE * 1), 'Com_Cod' => $Com_Cod, 'Atp_Cod' => $ant['Atp_Cod'], 'Pap_Cod' => $pag['Pap_Cod']), $obBD_conexion, true);
											$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pag['Pap_Cod'], 'Atp_Cod' => $pag['Atp_Cod'], 'Pap_Est' => $stdo), $obBD_conexion, true);

											$obBD_con1->echoLog($valorEditado);
											$obBD_con1->echoLog($contador_ant);

											if (($contador_ant * 1) > 0) {
												$valorEditado = ((float)$valorEditado - ($saldoE * 1));
											} else {
												$valorEditado = ((float)$pago['Haber'] - ($saldoE * 1));
											}
											$contador_ant += (float)$saldoE;
											$checkSiT = true;
											$obBD_con1->echoLog($valorEditado);
										}


										/* //insertamos un detalle de pago con anticipos
							$obBD_con1->operacionobBD(21, array('Dac_Val'=>( (float)$ant['Atp_Val']-($valorDeCuotas*1) ),'Com_Cod'=>$Com_Cod,'Atp_Cod'=>$ant['Atp_Cod'],'Pap_Cod'=>$pag['Pap_Cod']), $obBD_conexion);
							$obBD_con1->operacionobBD('pago_anticipo_proveedores.update',array('Pap_Cod'=>$pag['Pap_Cod'],'Atp_Cod'=>$pag['Atp_Cod'],'Pap_Est'=>'C'), $obBD_conexion,true);
							(float)$contador_ant+=( ((float)$pag['Pap_Val'] -($valorDeCuotas*1))); */
									}
								}
								unset($pag);
							} else {
								$obBD_con1->echoLog('else');
								$obBD_con1->echoLog(round(($disponibleSaldo * 1), 2));
								$obBD_con1->echoLog($pago['Haber']);
								$obBD_con1->echoLog(round(($ant_utl_val * 1), 2));
								$obBD_con1->echoLog($saldoExistente);
								$obBD_con1->echoLog(($ant['ttfinal'] * 1));
								if (((float)$disponibleSaldo - (float)$pago['Haber'])  > 0  || $ant_utl_val == 0) {
									//verificamos el valor de la cuota
									if ($saldoExistente > 0) {
										$valorEditado = $saldoExistente;
									} else {
										$valorEditado = ($pago['Haber'] * 1);
									}
									//cambiamos el estado del anticipo a utilizado
									$obBD_con1->operacionobBD(23, array('Atp_Cod' => $ant['Atp_Cod'], 'Atp_Est' => 'U'), $obBD_conexion);



									$pAprov = $obBD_con1->getArrayConsulta('pago_anticipo_proveedores.selectWhere', array('where' => array('Atp_Cod' => $ant['Atp_Cod'])), $obBD_conexion, true);
									foreach ($pAprov as &$pgo) {
										if ($pgo['Pap_Est'] != 'C') {
											$pagoSaldo = array();
											//Verifico los pagos en det_ant_ccpp con ese PAP_COD
											$pagoSaldo = $obBD_con1->getArrayConsulta('det_ant_ccpp.selectWhere', array('where' => array('Pap_Cod' => $pgo['Pap_Cod'])), $obBD_conexion, true);
											if (count($pagoSaldo) > 0) {
												foreach ($pagoSaldo as &$ps) {
													$valorDeCuotas += ($ps['Dac_Val'] * 1);
												}
												unset($ps);
											} else {
												$valorDeCuotas = 0;
											}
											$saldoE = (($pgo['Pap_Val'] * 1) - ($valorDeCuotas * 1));
											$obBD_con1->echoLog($valorDeCuotas);
											$obBD_con1->echoLog($saldoE);
											if ($vrfc) {
												$valorChange = $valorEditado;
												$valorSaldo = $valorEditado;
											} else {
												$valorChange = (float)$pago['Haber'];
												$valorSaldo = (float)$pago['Haber'];
											}

											$obBD_con1->echoLog($valorChange);
											$obBD_con1->echoLog($valorSaldo);
											$obBD_con1->echoLog($saldoExistente);
											$obBD_con1->echoLog($contador_ant);

											//Si la cuota cubre con pago
											if (($saldoE * 1) >= ((float)$valorChange) && (float)$contador_ant < ((float)$pago['Haber'])) {
												$contador_ant += ($valorEditado);
												$saldoExistente = ($valorEditado);
												if ($saldoExistente == ($pgo['Pap_Val'] * 1)) {
													$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pgo['Pap_Cod'], 'Atp_Cod' => $pgo['Atp_Cod'], 'Pap_Est' => 'C'), $obBD_conexion, true);
												} else {
													if ($saldoExistente > 0) {
														$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pgo['Pap_Cod'], 'Atp_Cod' => $pgo['Atp_Cod'], 'Pap_Est' => 'U'), $obBD_conexion, true);
													} else {
														$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pgo['Pap_Cod'], 'Atp_Cod' => $pgo['Atp_Cod'], 'Pap_Est' => 'C'), $obBD_conexion, true);
													}
												}
												//insertamos un detalle de pago con anticipos
												$obBD_con1->operacionobBD(21, array('Dac_Val' => ($valorEditado), 'Com_Cod' => $Com_Cod, 'Atp_Cod' => $ant['Atp_Cod'], 'Pap_Cod' => $pgo['Pap_Cod']), $obBD_conexion, true);
												break;
											} else {
												$obBD_con1->operacionobBD(21, array('Dac_Val' => ($saldoE * 1), 'Com_Cod' => $Com_Cod, 'Atp_Cod' => $ant['Atp_Cod'], 'Pap_Cod' => $pgo['Pap_Cod']), $obBD_conexion, true);
												$obBD_con1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pgo['Pap_Cod'], 'Atp_Cod' => $pgo['Atp_Cod'], 'Pap_Est' => 'C'), $obBD_conexion, true);
												if (($contador_ant * 1) > 0) {
													$valorEditado = ((float)$valorEditado - ($saldoE * 1));
												} else {
													$valorEditado = ((float)$pago['Haber'] - ($saldoE * 1));
												}
												$contador_ant += (float)$saldoE;
												$vrfc = true;
											}
										}
									}
									unset($pgo);
								}
							}
						}
					}
					//throw new Exception("The field is undefined.");
				}

				//en caso de ser pago con cruce de cuentas se genera los debidos detalles de cuentas por cobrar
				if ($pago['Pag_Abr'] == 'CDC') {
					$ccc_cnt = $obBD_con2->getArrayConsulta(26, array('Cli_Cod' => $Cli_Cod, 'Pec_Cod' => $Pec_Cod), $obBD_conexion2);
					(float)$cont_ccc = 0;
					$cnt_destino = (float)$pago['Haber'];


					foreach ($ccc_cnt as $deu) {
						if ($cont_ccc < $cnt_destino) {
							if (($cont_ccc + (float)$deu['saldo']) <= $cnt_destino) {
								//insertamos un pago a cuentas por cobrar
								$obBD_con1->operacionobBD(28, array('Cpc_Cod' => $deu['Cpc_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Cod' => $pago['Pag_Cod'], 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => $deu['saldo'], 'Cpc_Obs' => $Com_Obs), $obBD_conexion);
								$cont_ccc += (float)$deu['saldo'];
							}
							if (($cont_ccc + (float)$deu['saldo']) > $cnt_destino) {
								//insertamos un pago a cuentas por cobrar
								$obBD_con1->operacionobBD(28, array('Cpc_Cod' => $deu['Cpc_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Cod' => $pago['Pag_Cod'], 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => ($cnt_destino - $cont_ccc), 'Cpc_Obs' => $Com_Obs), $obBD_conexion);
								$cont_ccc += ($cnt_destino - $cont_ccc);
							}
						}
					}
				}

				//INCERTAMOS A REPOSICION DE CAJA				 
				if ($pago['Pag_Abr'] == 'RC') {
					//$obBD_con1->operacionobBD(134, array('Com_Cod'=>$Com_Cod), $obBD_conexion,true); //Eliminamos los registros en det_reposicicion de las compras que intervienen					
					$val_caja = $obBD_con1->getRowConsulta(131, $Ses_Emp_Cod, $obBD_conexion, true);
					$gasto_caja = $obBD_con1->getRowConsulta(130,  $Ses_Emp_Cod, $obBD_conexion, true);
					$saldo_caja = (float)$val_caja["val_caja"] - (float)$gasto_caja["total_g_caja"];
					$pago_rep_caja = (float) $pago["Haber"];
					if ((float)$pago_rep_caja > (float)$saldo_caja) {
						$response['message'] = "El saldo en caja es de <span style='color:blue;'>$" . $saldo_caja . "</span>, y el saldo que intenta pagar es mayor, por favor verifique nuevamente.";
						$obBD_con1->echoJson($response);
					}
					foreach ($save_cp as $cp) {
						if ($pago_rep_caja > 0) {
							$val_compra = (float) $cp["Pag_Val"];
							$Cpp_Cod = $cp["Cpp_Cod"];
							$Cop_Cod = $obBD_con1->getRowConsulta(128, array('Cpp_Cod' => $Cpp_Cod), $obBD_conexion, true);
							$existe_compra = $obBD_con1->getRowConsulta(129, $Cop_Cod['Cop_Cod'], $obBD_conexion, true);
							$dreInt = $obBD_con1->getRowConsulta('det_reposicion.1', array('Cop_Cod' => $Cop_Cod['Cop_Cod']), $obBD_conexion, true);
							if ($pago_rep_caja <= $val_compra) {
								//$obBD_con1->operacionobBD(127, $Cop_Cod['Cop_Cod']  . '*' . '0' . '*' . 'P', $obBD_conexion);
								$obBD_con1->operacionobBD(133, array('Cop_Cod' => $Cop_Cod['Cop_Cod'], 'Dre_Int' => $dreInt['Dre_Int'], 'Com_Cod' => $Com_Cod, 'Dre_Val' => $pago_rep_caja), $obBD_conexion);
								//$obBD_con1->operacionobBD('det_reposicion.insert',array('Cop_Cod'=>$Cop_Cod['Cop_Cod'],'Dre_Int'=>(!empty($Dre_Int)?$Dre_Int:1),'Dre_Tip'=>'P','Dre_Val'=>$pago_rep_caja),$obBD_conexion,true);
								$pago_rep_caja = 0;
							}
							if ($pago_rep_caja > $val_compra) {
								$val_residuo = number_format(($pago_rep_caja - $val_compra), 2, '.', '');
								//$obBD_con1->operacionobBD(127, $Cop_Cod['Cop_Cod']  . '*' . '0' . '*' . 'P', $obBD_conexion);
								$obBD_con1->operacionobBD(133, array('Cop_Cod' => $Cop_Cod['Cop_Cod'], 'Dre_Int' => $dreInt['Dre_Int'], 'Com_Cod' => $Com_Cod, 'Dre_Val' => $pago_rep_caja), $obBD_conexion);
								//$obBD_con1->operacionobBD('det_reposicion.insert',array('Cop_Cod'=>$Cop_Cod['Cop_Cod'],'Dre_Int'=>(!empty($Dre_Int)?$Dre_Int:1),'Dre_Tip'=>'P','Dre_Val'=>$pago_rep_caja),$obBD_conexion,true);							
							}
							$pago_rep_caja = $val_residuo;
						}
					}
				}

				if ($pago['Pag_Abr'] == 'CHE') {
					if ($pago['Asi_Cod'] != "") {
						$cheques_agregados = $obBD_con2->getArrayConsulta(55, array('Asi_Cod' => $pago['Asi_Cod']), $obBD_conexion2);
						if (count($cheques_agregados) > 0) {
							$ultimo_asiento = $pago['Asi_Cod'];
							//Modificamos un cheque y su asiento
							$response['bnd_che'] = true;
							array_push($response['arrayche'], array('link' => "?codigo2=" . $pago['Che_Cod'] . "&asi=" . $ultimo_asiento . "&ban=" . $pago['Ban_Cod'] . "&pro=" . $agg_Prv_Cod, 'che' => "No.:" . $pago['Che_Num'] . " - Valor:$ " . $pago['Haber']));
							$obBD_con1->operacionobBD(56, array('Asi_Cod' => $pago['Asi_Cod'], 'Asi_Val' => $pago['Haber'], 'Asi_Con' => $pago['Glosa'], 'Asi_Glo' => $pago['Glosa']), $obBD_conexion);
						}
					} else {
						// insertamos un asiento por cada pago
						$obBD_con1->operacionobBD(14, array('Com_Cod' => $Com_Cod, 'Asi_Deh' => 'H', 'Asi_Con' => $pago['Glosa'], 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Haber'], 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion);
						$ultimo_asiento = $obBD_con1->insercionid($obBD_conexion);
						//insertamos un cheque
						$contador_cheque++;
						$response['bnd_che'] = true;
						array_push($response['arrayche'], array('link' => "?codigo2=$contador_cheque&asi=" . $ultimo_asiento . "&ban=" . $pago['Ban_Cod'] . "&pro=" . $agg_Prv_Cod, 'che' => "No.:" . $pago['Che_Num'] . " - Valor:$ " . $pago['Haber']));
						$obBD_con1->operacionobBD(17, array('Che_Cod' => $contador_cheque, 'Prv_Cod' => $agg_Prv_Cod, 'Ban_Cod' => $pago['Ban_Cod'], 'Asi_Cod' => $ultimo_asiento, 'Che_Num' => $pago['Che_Num'], 'Che_Fec' => $pago['Che_Fec'], 'Che_Val' => $pago['Haber'], 'Che_Obs' => $Com_Obs, 'Che_Ben' => $Che_Ben_N), $obBD_conexion);
					}
				} else {
					// insertamos un asiento por cada pago
					$obBD_con1->operacionobBD(14, array('Com_Cod' => $Com_Cod, 'Asi_Deh' => 'H', 'Asi_Con' => $pago['Glosa'], 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Haber'], 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion);
					$ultimo_asiento = $obBD_con1->insercionid($obBD_conexion);
				}

				//*****************************************Anticipo con el valor exedente****************************************************
				if ($pago['Pag_Abr'] == $PgabrEx && $opt_guardar == "a" && $bandera_ex == false) {
					//insertamos un anticipo a proveedores
					$obBD_con1->operacionobBD(65, array('Atp_Fec' => $Com_Fec, 'Atp_Val' => $disminuir, 'Atp_Obs' => "ANTICIPO POR VALOR EXEDENTE EN PAGO A PROVEEDORES", 'Com_Cod' => $Com_Cod, 'Prv_Cod' => $agg_Prv_Cod), $obBD_conexion);
					$ultimo_anticipo = $obBD_con1->insercionid($obBD_conexion);

					// insertamos un pago de anticipo a proveedores
					$obBD_con1->operacionobBD(66, array('Pap_Cto' => '', 'Pap_Ctd' => '', 'Pap_Val' => $disminuir, 'Atp_Cod' => $ultimo_anticipo, 'Pag_Cod' => $pago['Pag_Cod'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion);

					$bandera_ex = true;
				}

				//********************************************************************************************************************
				$var_pag = $pago['Haber'] * 1;
				$pag_sald = ($save_cp['' . intval($cntcpp)]['Pag_Val'] * 1) + ($save_cp['' . intval($cntcpp)]['Saldo'] * 1);
				$vdism = 0;

				if (floatval(($var_pag - $vdism)) < floatval($save_cp['' . intval($cntcpp)]['Pag_Val'] * 1)) {
					//if($pago['Pag_Abr'] == 'CHE' && $bnd_ant == false){$vdism=$disminuir;$bnd_ant=true;} else {$vdism=0;}
					$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $save_cp['' . intval($cntcpp)]['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => ($var_pag - $vdism), 'Pag_Obs' => $Com_Con, 'Asi_Cod' => $ultimo_asiento), $obBD_conexion);
					$save_cp['' . intval($cntcpp)]['Pag_Val'] = ($save_cp['' . intval($cntcpp)]['Pag_Val'] * 1) - (($var_pag - $vdism) * 1);
				} elseif (floatval(($var_pag - $vdism)) == floatval($save_cp['' . intval($cntcpp)]['Pag_Val'] * 1)) {
					//if($pago['Pag_Abr'] == 'CHE' && $bnd_ant == false){$vdism=$disminuir;$bnd_ant=true;} else {$vdism=0;}
					$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $save_cp['' . intval($cntcpp)]['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => ($var_pag - $vdism), 'Pag_Obs' => $Com_Con, 'Asi_Cod' => $ultimo_asiento), $obBD_conexion);
					$cntcpp++;
				} elseif (floatval(($var_pag - $vdism)) > floatval($save_cp['' . intval($cntcpp)]['Pag_Val'] * 1)) {
					if (($Com_Val * 1) <= ($Com_Val_temp * 1)) {
						while (($var_pag - $vdism) != "none") {

							if ($save_cp['' . intval($cntcpp)]['Pag_Val'] == 0) {
								$var_pag = "none";
							} elseif (($var_pag - $vdism) < (float)$save_cp['' . intval($cntcpp)]['Pag_Val']) {
								//if($pago['Pag_Abr'] == 'CHE' && $bnd_ant == false){$vdism=$disminuir;$bnd_ant=true;} else {$vdism=0;}
								$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $save_cp['' . intval($cntcpp)]['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => ($var_pag - $vdism), 'Pag_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion);
								$save_cp['' . intval($cntcpp)]['Pag_Val'] = (float)((float)$save_cp['' . intval($cntcpp)]['Pag_Val'] - ($var_pag - $vdism));
								$var_pag = "none";
							} elseif (($var_pag - $vdism) == (float)$save_cp['' . intval($cntcpp)]['Pag_Val']) {
								//if($pago['Pag_Abr'] == 'CHE' && $bnd_ant == false){$vdism=$disminuir;$bnd_ant=true;} else {$vdism=0;}
								$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $save_cp['' . intval($cntcpp)]['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => ($var_pag - $vdism), 'Pag_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion);
								$var_pag = "none";
								$cntcpp++;
							} elseif (($var_pag - $vdism) > (float)$save_cp['' . intval($cntcpp)]['Pag_Val']) {
								//if($pago['Pag_Abr'] == 'CHE' && $bnd_ant == false){$vdism=$disminuir;$bnd_ant=true;} else {$vdism=0;}
								$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $save_cp['' . intval($cntcpp)]['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => (($var_pag - $vdism) - (($var_pag - $vdism) - (float)$save_cp['' . intval($cntcpp)]['Pag_Val'])), 'Pag_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion);
								$var_pag = ($var_pag - $vdism) - (float)$save_cp['' . intval($cntcpp)]['Pag_Val'];
								$cntcpp++;
							}
						}
					} else {
						$vp_temp = false;
						$valor_guardado = 0;
						for ($i = 0; $i < ($pag_sald * 1); $i = $i + ($valor_guardado)) {
							if ($vp_temp == true) {
								$pag_sald_temp = ($save_cp['' . intval($cntcpp)]['Pag_Val'] * 1) + ($save_cp['' . intval($cntcpp)]['Saldo'] * 1);
								$vp_temp = false;
							}
							if ((($var_pag - $vdism) * 1) < ($pag_sald_temp * 1)) {
								if ($pago['Pag_Abr'] == $PgabrEx &&  $bnd_ant == false) {
									$vdism = $disminuir;
									$bnd_ant = true;
								} else {
									$vdism = 0;
								}
								$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $save_cp['' . intval($cntcpp)]['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => ($pag_sald_temp - ($pag_sald_temp - ($var_pag - $vdism)) * 1), 'Pag_Obs' => $Com_Con, 'Asi_Cod' => $ultimo_asiento), $obBD_conexion);
								$pag_sald_temp = $pag_sald_temp - ($var_pag - $vdism);
								$valor_guardado = ($pag_sald_temp - ($pag_sald_temp - ($var_pag - $vdism)) * 1);
								break;
							} elseif ((($var_pag - $vdism) * 1) == ($pag_sald_temp * 1)) {
								if ($pago['Pag_Abr'] == $PgabrEx && $bnd_ant == false) {
									$vdism = $disminuir;
									$bnd_ant = true;
								} else {
									$vdism = 0;
								}
								$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $save_cp['' . intval($cntcpp)]['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => ($var_pag - $vdism), 'Pag_Obs' => $Com_Con, 'Asi_Cod' => $ultimo_asiento), $obBD_conexion);
								$cntcpp++;
								$valor_guardado = ($var_pag - $vdism);
								break;
							} elseif ((($var_pag - $vdism) * 1) > ($pag_sald_temp * 1)) {
								if ($pago['Pag_Abr'] == $PgabrEx && $bnd_ant == false) {
									$vdism = $disminuir;
									$bnd_ant = true;
								} else {
									$vdism = 0;
								}
								$obBD_con1->operacionobBD(15, array('Cpp_Cod' => $save_cp['' . intval($cntcpp)]['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => $pag_sald_temp, 'Pag_Obs' => $Com_Con, 'Asi_Cod' => $ultimo_asiento), $obBD_conexion);
								$cntcpp++;
								$vp_temp = true;
								$var_pag = ($var_pag - $vdism) - $pag_sald_temp;
								$valor_guardado = $pag_sald_temp;
							}
						}
					}
				}
			} else {
				// insertamos un asiento por defecto para el pago a proveedores
				if ($bandera_exD == false && $opt_guardar == "a") {
					// asiento general
					$obBD_con1->operacionobBD(14, array('Com_Cod' => $Com_Cod, 'Asi_Deh' => 'D', 'Asi_Con' => $pago['Glosa'], 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => (($pago['Debe'] - $disminuir) * 1 + $Com_Val_dism * 1), 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion);

					//asiento en el debe para exedente en pago a proveedores
					$anticipos_cta = $obBD_con1->getRowConsulta(67, "", $obBD_conexion);
					$obBD_con1->operacionobBD(14, array('Com_Cod' => $Com_Cod, 'Asi_Deh' => 'D', 'Asi_Con' => "VALOR EXEDENTE EN PAGO A PROVEEDORES", 'Asi_Glo' => "VALOR EXEDENTE EN PAGO A PROVEEDORES", 'Asi_Val' => $disminuir, 'Pld_Cod' => $anticipos_cta['Pld_Cod']), $obBD_conexion);
					$bandera_exD = true;
				} else {
					$obBD_con1->operacionobBD(14, array('Com_Cod' => $Com_Cod, 'Asi_Deh' => 'D', 'Asi_Con' => $pago['Glosa'], 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => ($pago['Debe'] * 1 + $Com_Val_dism * 1), 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion);
				}
			}
		}
		// throw new Exception('Prueba SQL!');
		$response['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$Com_Cod&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod";

		$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

		if ($obBD_con1->Error == 0) {
			$response['success'] = true;
		}
	} catch (Exception $e) {
		$obBD_con1->rollBack_nomsn($obBD_conexion);
		$response['success'] = false;
		$response['message'] = '<span class="red">ERROR:</span> ' . $e->getMessage();
	}
	$obBD_con1->echoJson($response);
}

//obtener cantidad sisponible en anticipos a proveedores
if (isset($getAnticipoCantAjax)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";

	$anticipos_cnt = $obBD_con1->getRowConsulta(18, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
	$det_anticipos_cnt = $obBD_con1->getRowConsulta(19, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
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

//obtenemos todas las aportaciones de un socio
if (isset($abonosDetAjax)) {
	$responce['rows'] = $obBD_con1->getArrayConsulta(29, $abonosDetAjax, $obBD_conexion);
	$responce['records'] = count($responce['rows']);
	foreach ($responce['rows'] as &$v) {
		$c=$obBD_con1->getRowConsulta(136, array('Com_Cod' => $v['Com_Cod']), $obBD_conexion,true);
		$v['conci']=!empty($c)?'S':'N';
	}
	unset($v);
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

//obtenemos todos los asientos y chuque de un abono
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
	$response['data_fact'] = $obBD_con1->getArrayConsulta(72, array('Com_Cod' => $Com_Cod), $obBD_conexion);
	foreach ($response['data_fact'] as &$v) {
		$v = array_merge($obBD_con1->getRowConsulta(73, array('Com_Cod' => $v['Com_Cpp']), $obBD_conexion), $v);
	}
	unset($v);

	$anticipos_cnt = $obBD_con1->getRowConsulta(18, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
	$det_anticipos_cnt = $obBD_con1->getRowConsulta(19, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
	if (count($anticipos_cnt) > 0) {
		$response['data_ant'] = $anticipos_cnt['tot_anti'] - $det_anticipos_cnt['tot_dac'];
	} else {
		$response['data_ant'] = 'none';
	}

	$prv_cli_cod = $obBD_con1->getRowConsulta(25, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
	//$ccc_cnt = $obBD_con1->getArrayConsulta(26, array('Cli_Cod' =>$prv_cli_cod['Cli_Cod'], 'Pec_Cod' =>$Pec_Cod), $obBD_conexion);
	$ccc_cnt = $obBD_con1->getArrayConsulta(26, array('Cli_Cod' => $prv_cli_cod['Cli_Cod'], 'Pec_Cod' => isset($Pec_Cod) ? $Pec_Cod : ''), $obBD_conexion);
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

//obtenemos todos los pagos de un abono
if (isset($delAbono)) {
	$obBD_conexion2 = new Class_Log_Conexion_Ccpp($Ses_Dat_Dis);
	$obBD_con2 =  new Class_Log_Datos_Ccpp;
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";
	$obBD_con1->operacionobBD(41, array('Com_Cod' => $Com_Cod), $obBD_conexion);
	//Revertir el anticipo ( det_ant_ccpp, anticipo_proveedor, pag_ant_proveedor)
	//1) Busco los codigo del anticipo de la tabla det_ant_ccpp que contengan el CodigoComprobante del pago
	$anticipos_vinculados = $obBD_con2->getArrayConsulta(37, array('Com_Cod' => $Com_Cod), $obBD_conexion2);
	//2) Si es mayor de 0 la busqueda del punto 1
	if (count($anticipos_vinculados) > 0) {
		foreach ($anticipos_vinculados as $cod) {
			//2.1) Busco si existen detalles de anticipo diferentes al codigoComprobante y con codigo de anticipo igual 
			$anticipos_diferentes = $obBD_con2->getArrayConsulta(124, array('Atp_Cod' => $cod['Atp_Cod'], 'Com_Cod' => $Com_Cod), $obBD_conexion2);
			//2.2) Si el punto 2.1 es mayor a 0 le pongo de estado al anticipo y pagoAnticipoproveedor U caso contrario le pongo A
			if (count($anticipos_diferentes) > 0) {
				//Estado U en anticipo_proveedores y pag_ant_prov
				$obBD_con1->operacionobBD(23, array('Atp_Cod' => $cod['Atp_Cod'], 'Atp_Est' => 'U'), $obBD_conexion);
				$obBD_con1->operacionobBD(123, array('Atp_Cod' => $cod['Atp_Cod'], 'Pap_Est' => 'U'), $obBD_conexion);
			} else {
				//Estado A en anticipo_proveedores y pag_ant_prov
				$obBD_con1->operacionobBD(23, array('Atp_Cod' => $cod['Atp_Cod'], 'Atp_Est' => 'A'), $obBD_conexion);
				$obBD_con1->operacionobBD(123, array('Atp_Cod' => $cod['Atp_Cod'], 'Pap_Est' => 'A'), $obBD_conexion);
			}
		}
	}

	//Si es reposicion de caja inactivar det_reposicion
	if ($Pag_Abr == 'RC') {
		$obBD_con1->operacionobBD(132, array('Cpp_Cod' => $Cpp_Cod), $obBD_conexion);
	}

	//3) Elimino el det_ant_ccpp
	$obBD_con1->operacionobBD(35, array('Com_Cod' => $Com_Cod), $obBD_conexion);
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
		$obBD_con1->operacionobBD(13, array('Pec_Cod' => $Pec_Cod['Pec_Cod'], 'Prv_Cod' => $Prv_Cod, 'Cli_Cod' => $Cli_Cod, 'Com_Num' => $Com_Num, 'Com_Fec' => $hoy, 'Com_Con' => 'REINGRESO DE VALORES POR CHEQUE PROTESTADO', 'Com_Val' => $Che_Val, 'Com_Obs' => "CHEQUE No. " . $Che_Num . " protestado", 'Tia_Cod' => $Tia_Cod), $obBD_conexion);
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
		$response['message'] = "Advertencia: Hace falta un periodo contable para el a�o actual";
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

?>
<!DOCTYPE html>
<html>

<head>
	<!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?php echo "Ccxpp Modificar [EXA] "; ?></TITLE>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
	<script src="../VALIDACIONES/tes_val_mod_ccpp_lotes.js?a=39"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
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
	</style>
</head>

<body>
	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; Modificar pagos por lotes a proveedores</h3>
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
									<div class="col-xs-2">
										<div class="input-group">
											<span class="input-group-addon" style="padding:2px 5px 0px 5px;margin:0;line-height:0;">
												<input type="checkbox" id="f_periodo" name="f_periodo" onchange="cambiarFiltro()">
											</span>
											<select class="form-control input-xs" name="sel_per" id="sel_per" onchange="cambioPreiodoSearch('peri')" disabled>
												<?php
												$periodos_rows = $obBD_con1->getArrayConsulta(45, "", $obBD_conexion);
												if (count($periodos_rows) > 0) {
													foreach ($periodos_rows as $row) {
														echo "<option value='$row[Pec_Cod]' data-inicio='$row[Pec_Fei]' data-fin='$row[Pec_Fef]'>$row[anio]</option>";
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
												<button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Documento" tabindex="-1">
													<span class="glyphicon glyphicon-search"></span>
													<span>Buscar</span>
												</button>
											</span>
										</div>
									</div>
								</div>
							</fieldset>
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
								<span class="glyphicon glyphicon-stop" style="color:#8bff9f;"></span> Pagados
							</span>
						</div>
					</div>
				</div>
			</div>
			<div id="agregar_ccpp" hidden>
				<div class="row">
					<div class="col-sm-12">
						<div class="row">
							<form class="form-horizontal normal" name="formPagos" id="formPagos" method="post" action="javascript:preGuardarPago()">
								<input name="tip_trans" id="tip_trans" value="add" type="text" style="display:none;" />
								<input name="Com_Cod" id="Com_Cod" value="add" type="text" style="display:none;" />
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
																<?php echo "<option value='$row[Pec_Cod]' data-pla-cod='$row[Pla_Cod]' data-pec-fei='$row[Pec_Fei]' data-pec-fef='$row[Pec_Fef]'  data-periodo='$row[priodo_m]'>$row[priodo_m]</option>"; ?>

														<?php }
														} ?>
													</select>
												</div>
												<div id="btn_view_facts" class="col-sm-6" style="text-align:right;" hidden>
													<a onclick="$('#verFactsDialog').dialog('open');" title="Facturas incluidas en este abono" class="btn btn-xs btn-success">
														<span class="glyphicon glyphicon-eye-open"></span> Ver Facturas</a>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="form-group">
												<label class="col-sm-3 control-label label-sm required" for="Tia_Cod">Tipo Comprobante:</label>
												<div class="col-sm-4">
													<input name="Tia_Cod_temp" id="Tia_Cod_temp" type="text" style="display:none;" />
													<input name="Com_Num" id="Com_Num" type="text" style="display:none;" />

													<select id="Tia_Cod" name="Tia_Cod" class="form-control input-xs" required="" onchange="">
														<?php
														$row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(4, "ALL", $obBD_conexion);
														foreach ($row_rs_tipo_asien2 as $row) { ?>
															<option value="<?php echo $row['Tia_Cod']; ?>">
																<?php echo  mb_convert_encoding($row['Tia_Des'], 'UTF-8', 'ISO-8859-1') ?>
															</option>
														<?php } ?>
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
									</fieldset>
								</div>
								<div class="col-sm-6">
									<fieldset class="exa-fieldset">
										<legend class="Titulos2">Datos del pago</legend>
										<div class="row">
											<div class="form-group">
												<label class="col-sm-3 control-label label-xs required" for="Com_Fec">Fecha del comprobante:</label>
												<div class="col-sm-2">
													<input id="Com_Fec" name="Com_Fec" class="form-control input-xs datepicker" placeholder="yy-mm-dd" type="text" />
												</div>
											</div>
										</div>
										<div class="row">
											<div class="form-group">
												<label class="col-sm-3 control-label label-sm required" for="Pag_Cod">Tipo de pago:</label>
												<div class="col-sm-3">
													<select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs" required="" onchange="enableDisableCampos()">
														<?php
														$row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(5, "", $obBD_conexion);
														foreach ($row_rs_tipo_asien2 as $row) {
															/*if ($row['Pag_Abr'] != 'ANT') {*/ ?>
																<option value="<?php echo $row['Pag_Cod']; ?>" data-abr="<?php echo $row['Pag_Abr']; ?>">
																	<?php echo mb_convert_encoding($row['Pag_Des'], 'UTF-8', 'ISO-8859-1') ?>
																</option>
														<?php /*}*/
														} ?>
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
													<select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs ed_element ed_CHE ed_TRF ed_TDC" required="" onchange="" disabled>
														<?php
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
													<input id="Num_Doc" name="Num_Doc" class="form-control input-xs ed_elemento ed_TRF " placeholder="002554" type="text" disabled />
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
													<input id="PgabrEx" name="PgabrEx" type="text" hidden />
													<input id="Cpp_Cod" name="Cpp_Cod" type="text" hidden />
													<input id="Com_Val" name="Com_Val" type="text" hidden />
													<input id="Com_Val_temp" name="Com_Val_temp" type="text" hidden />
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
												<a class="btn btn-success btn-xs" onclick="preAddPago()">
													<span class="glyphicon glyphicon-arrow-down"></span> Agregar pago</a>
											</div>
										</div>
									</fieldset>
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
										<span class="glyphicon glyphicon-book"></span> Guardar</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="proveedoresDialog" title="B&uacute;squeda de Proveedores">
		<form class="form-horizontal normal"> </form>
	</div>

	<div id="successDialog" title="Mensaje del Sistema">
		<center>
			<h2>El Comprobante se ha registrado con Exito!</h2>
		</center>
		<center>
			<button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-danger fileinput-button" style="display: inline;">
				<i class="icon-ban-circle icon-white"></i>
				<span>Cerrar</span>
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
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>cheques/1/tes_pri_cheque_loj_1.0.php" href="" target="_blank" title="Banco del Austro">
											<img src="../../mascaras/model1/imagenes/32x32/banco_loja.jpg" width="32" height="32" />
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
		<div class="row" id="ndc_info" hidden>
			<div class="col-xs-12">
				<center>
					<h5>Pago generado por una nota de cr&eacute;dito</h5>
				</center>
			</div>
		</div>
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

	<div id="verFactsDialog" title="Facturas incluidas en este abono">
		<div class="row">
			<div class="col-sm-12">
				<center>
					<h4>Desmarque las facturas que desea excluir de este abono</h4>
				</center>
			</div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-12">
				<fieldset class="exa-fieldset">
					<legend class="Titulos2">Datos de las facturas</legend>
					<table id="factsGrid" name="factsGrid"></table>
				</fieldset>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12" style="text-align:right">
				<a class="btn btn-sm btn-success" onclick="$('#verFactsDialog').dialog('close');recTotAPagar();">Aceptar</a>
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
							<?php echo $Ses_Emp_Nom; ?>
						</span>
					</td>
				</tr>
				<tr>
					<td align="right">
						<b>EMISI&Oacute;N:</b>
					</td>
					<td>
						<span>
							<?php $fecha = explode('-', $hoy);
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

	<div id="optExedDialog" title="Valor exedente">
		<div class="row">
			<div class="col-sm-12">
				<center>
					<h5>El total a pagar no puede sobrepasar los $
						<span id="maximo_info"></span>
					</h5>
				</center>
				<center>
					<h5>Que desea hacer con el valor exedente ($
						<span id="exedente_info"></span>) ?
					</h5>
				</center>
			</div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-12" style="text-align:center">
				<a class="btn btn-sm btn-success" onclick="guardarPago('a');$('#optExedDialog').dialog('close');">Guardar / Enviar a anticipos</a>
				<a class="btn btn-sm btn-primary" onclick="$('#optExedDialog').dialog('close');">Quedarse y corregir valores</a>
			</div>
		</div>
	</div>
</body>

</html>