<?php

/**
 * @abstract Permite realizar la modificacion de Anticipos Manuales
 * @author Edison Moya
 * @version 1.0
 * Fecha de creacion  2017-12-06
 * Fecha de actualizacion 2024-07-08
 * Actualizado por Wilson Belduma
 * Descripción de la actualización: Arreglo del pago de anticipos 
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cccc_lotes_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion_set = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
$obBD_con_set =  new Class_Log_Datos_Cccc;

// $obBD_con_set->getPageGrid("asientos.selectWhere",array('rows'=>1000,'page'=>1,'where'=>array('Com_Cod'=>10933),'order'=>'asientos.Asi_Deh asc'), $obBD_conexion_set, true);
// var_dump($obBD_con_set->select("asientos.selectWhere",array('Com_Cod'=>10933,'order'=>'asientos.Asi_Deh asc'), $obBD_conexion_set));

$obBD_conexion_get = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
$obBD_con_get =  new Class_Log_Datos_Cccc;

//fecha y mes actuales
$hoy = date("Y-m-d");
$mes = date("m");

//para obtener planes de cuenta para agregar aportaciones
if (isset($cuentasAjax)) {
	$obBD_con_get->getPageGridJson(14, $_GET, $obBD_conexion_get);
}


//seccion para obtener los clientes registrados en la empresa
if (isset($clientesAjax)) {
	$obBD_con_get->getPageGridJson(1, $_GET, $obBD_conexion_get);
}

// obtenemos las facturas por cobrar de el cliente seleccionado
if (isset($ajaxComprobante)) {
	$responce['rows'] = $obBD_con_get->getArrayConsulta(2, $_GET, $obBD_conexion_get);
	foreach ($responce['rows'] as $key => $item) {
		if ($item['Abono'] == $item['Asi_Val']) unset($responce['rows'][$key]);
	}
	$responce['rows'] = array_values($responce['rows']);
	$responce['records'] = count($responce['rows']);
	$obBD_con_get->echoJson($responce);
	exit();
}

//obtenemos todos los pagos de una factura de un cliente
if (isset($abonosDetAjax)) {
	$responce['rows'] = $obBD_con_get->getArrayConsulta(3, $abonosDetAjax, $obBD_conexion_get);
	$responce['records'] = count($responce['rows']);
	$obBD_con_get->echoJson($responce);
	exit();
}

if (isset($getPagoIniAjax)) {
	try {

		$response['success'] = false;
		ChromePhp::log(" PLD_CDC:: ".$Pld_Cdc);
		//$response['data'] = $obBD_con_get->getRowConsulta(8, ""   , $obBD_conexion_get);
		$response['data'] = $obBD_con_get->getRowConsulta(8, $Pld_Cdc, $obBD_conexion_get);
		if ($obBD_con_get->Error == 0) {
			$response['success'] = true;
		}
	} catch (Exception $e) {
		$obBD_con_set->rollBack_nomsn($obBD_conexion_set);
		$response['success'] = false;
		$response['message'] = '<span class="red">ERROR:</span> ' . $e->getMessage();
	}

	$obBD_con_get->echoJson($response);
	exit();
}

//verificamos si el numero de un cheque ya esta registrado dentro de la tabla cheques
if (isset($verificarCheNum)) {
	//Se obtiene el socio seleccionado
	$response['numero_che'] = false;
	$num_Ches = $obBD_con_get->getArrayConsulta(11, array('Bak_Cod' => $Bak_Cod, 'Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
	foreach ($num_Ches as $nch) {
		if ($nch['Che_Num'] == $Che_Num) {
			$response['numero_che'] = true;
		}
	}

	$obBD_con_set->echoJson($response);
	exit();
}

if (isset($getAsientosAbono)) {
	//$obBD_con_get->debug(true);
	$response['success'] = false;

	$response['data'] = $obBD_con_get->getArrayConsulta(12, array('Com_Cod' => $Com_Cod), $obBD_conexion_get);
	$response['data_che'] = $obBD_con_get->getArrayConsulta(13, array('Com_Cod' => $Com_Cod), $obBD_conexion_get);

	if ($obBD_con_get->Error == 0) {
		$response['success'] = true;
	}

	$obBD_con_get->echoJson($response);
	exit();
}

if (isset($enableDisableCampos)) {
	//Se obtiene el cuentas diferentes para tipos de pagos
	//$obBD_con_get->debug(true);
	$response['success'] = false;

	if ($tipo == "ANT") {
		$response['data'] = $obBD_con_get->getArrayConsulta(15, "", $obBD_conexion_get);

		//obtenemos los anticipos del cliente
		$anticipos_cnt = $obBD_con_get->getRowConsulta(18, array('Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
		//obtenemos la cantidad de anticipos utilizados por el cliente
		$det_anticipos_cnt = $obBD_con_get->getRowConsulta(19, array('Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
		$detalles = $obBD_con_get->getArrayConsulta(1800, array('Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
		$cheques = count($detalles);
		$resultado = "";
		for ($i = 0; $i < $cheques; $i++) {
			foreach ($detalles[$i] as $clave => $det) {
				if (!empty($det))
					$resultado = $resultado . $clave . "." . $det . " ";
			}
			$resultado = $resultado . "/ ";
		}
		if (count($anticipos_cnt) > 0) {
			$response['data_ant'] = $anticipos_cnt['tot_anti'] - $det_anticipos_cnt['tot_dac'];
			$response['deta'] = $resultado;
		} else {
			$response['data_ant'] = 'none';
		}
	} elseif ($tipo == "CDC") {
		$response['data'] = $obBD_con_get->getArrayConsulta(16, "", $obBD_conexion_get);

		$prv_cli_cod = $obBD_con_get->getRowConsulta(20, array('Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
		$ccc_cnt = $obBD_con_get->getArrayConsulta(21, array('Prv_Cod' => $prv_cli_cod['Prv_Cod']), $obBD_conexion_get);
		$response['rows']=$ccc_cnt;
		if (count($ccc_cnt) > 0) {
			$total = 0;
			$abono = 0;
			foreach ($ccc_cnt as $deu) {
				(float)$total += (float)$deu['Asi_Val'];
				(float)$abono += (float)$deu['Abono'];
			}
			$response['data_cdc'] = (float)$total - (float)$abono;
		} else {
			$response['data_cdc'] = 'none';
		}
	} elseif ($tipo == "EFE" || $tipo == "OTR" || $tipo == "NDC" || $tipo == "CPL") {
		$response['data'] = $obBD_con_get->getArrayConsulta(17, array('Ban_Tip' => 'C'), $obBD_conexion_get);
	} elseif ($tipo == "TDC" || $tipo == "TRF" || $tipo == "DEP") {
		$response['data'] = $obBD_con_get->getArrayConsulta(17, array('Ban_Tip' => 'B'), $obBD_conexion_get);
	} elseif ($tipo == "CHE") {
		$response['data'] = $obBD_con_get->getArrayConsulta(17, array('Ban_Tip' => 'B'), $obBD_conexion_get);
	}

	if ($obBD_con_get->Error == 0) {
		$response['success'] = true;
	}
	$obBD_con_get->echoJson($response);
	exit();
}

//Guardar los pagos ingresados
if (isset($savePago)) {

	//Validar anticipos
	foreach ($save_p as $pago) {
		if ($pago['Pag_Abr'] == 'ANT' &&  (float)$valor_tota_pago <= 0) {
			$response['message'] = "Ha seleccionado el pago con anticipos, pero no ha seleccionado ningún valor. Por favor, verifique nuevamente.";
			$obBD_con_set->echoJson($response);
			exit();
		}
	}



	$obBD_con_set->debug(true);
	$obBD_con_set->debugLogs(false);
	//$obBD_con_get->debugLogs(false);
	$obBD_con_get->validaCierrePeriodo('det_ccpp_c','Cpc_Fec','Dcc_Cod',$Com_Fec,null,$obBD_conexion_get);

	try {
		$response['success'] = false;
		$obBD_con_set->inicio_transaccion($obBD_conexion_set->conexion);
		//generamos el numero de comprobante
		$var_mes = explode('-', $Com_Fec);
		$Com_Num = $obBD_con_get->codigoComprAuto($Tia_Cod, $Pec_Cod, $var_mes[1], $obBD_conexion_get);
		$Proveedor = $obBD_con_get->getArrayConsulta(20, array('Cli_Cod' => $agg_Cli_Cod), $obBD_conexion_get);
		$Prv_Cod = 'null';
		if (count($Proveedor) > 0) {
			foreach ($Proveedor as $prv) {
				$Prv_Cod = $prv['Prv_Cod'];
			}
		}
		//insertamos un comprobante y extraemos el id ingresado
		// $obBD_con_set->operacionobBD(22, array('Pec_Cod' => $Pec_Cod, 'Prv_Cod' => $Prv_Cod, 'Cli_Cod' => $agg_Cli_Cod, 'Com_Num' => $Com_Num, 'Com_Fec' => $Com_Fec, 'Com_Con' => $Com_Con, 'Com_Val' => $Com_Val, 'Com_Obs' => $Com_Obs, 'Tia_Cod' => $Tia_Cod), $obBD_conexion_set);
		$obBD_con_set->operacionobBD(22, array('Pec_Cod' => $Pec_Cod, 'Prv_Cod' => $Prv_Cod, 'Cli_Cod' => $agg_Cli_Cod, 
		'Com_Num' => $Com_Num, 'Com_Fec' => $Com_Fec, 'Com_Con' => $Com_Con, 'Com_Val' => $Com_Val, 
		'Com_Obs' => $Com_Obs, 'Tia_Cod' => $Tia_Cod, 'Num_Doc'=> $Num_Doc ), $obBD_conexion_set);
		$ultimo_comprobate = $obBD_con_set->insercionid($obBD_conexion_set);


		$cntcpp = 0;
		$valC = 0;

		foreach ($save_p as $pago) {

			if ($pago['grid_tipp'] == 'pago') {
				// insertamos un asiento por cada pago
				$obBD_con_set->operacionobBD(23, array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'D', 'Asi_Con' => $pago['concepto'], 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Debe'], 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion_set);
				$ultimo_asiento = $obBD_con_set->insercionid($obBD_conexion_set);

				//********************************************************************************************************************
				if ($pago['Pag_Abr'] == 'CHE') {
					//insertamos un cheque
					$obBD_con_set->operacionobBD(31, array('Bak_Cod' => $pago['Bak_Cod'], 'Cli_Cod' => $agg_Cli_Cod, 'Che_Cta' => $pago['Pac_Cto'], 'Che_Num' => $pago['Che_Num'], 'Che_Fec' => $pago['Che_Fec'], 'Che_Val' => $pago['Debe'], 'Che_Obs' => $pago['Glosa'], 'Che_Cli' => $pago['Che_Cli']), $obBD_conexion_set);
					$ultimo_cheque = $obBD_con_set->insercionid($obBD_conexion_set);
				}

				$var_pag = (float)$pago['Debe'];

				while ($var_pag != "none") {

					$valgrd = 0;
					// echo "--> ".$save_cp[$cntcpp]['Cpc_Cod']." | ".(float)$save_cp[$cntcpp]['Cpc_Val']." | ".$pago['Pag_Cod']." | ".$var_pag."\n";
					if ($save_cp['' . intval($cntcpp)]['Cpc_Val'] == 0) {
						$var_pag = "none";
					} elseif ($var_pag < (float)$save_cp['' . intval($cntcpp)]['Cpc_Val']) {
						$obBD_con_set->operacionobBD(24, array('Cpc_Cod' => $save_cp['' . intval($cntcpp)]['Cpc_Cod'], 'Pag_Cod' => $pago['Pag_Cod'],'Cpc_Cdc'=>$pago['factu'], 'Com_Cod' => $ultimo_comprobate, 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => $var_pag, 'Cpc_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
						$ultimo_dcc_cod = $obBD_con_set->insercionid($obBD_conexion_set);
						$valgrd = $var_pag;

						if ($pago['Pag_Abr'] == 'CHE') {
							$obBD_con_set->operacionobBD(30, array('Che_Cod' => $ultimo_cheque, 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
						}

						$save_cp['' . intval($cntcpp)]['Cpc_Val'] = (float)((float)$save_cp['' . intval($cntcpp)]['Cpc_Val'] - $var_pag);
						$var_pag = "none";
					} elseif ($var_pag == (float)$save_cp['' . intval($cntcpp)]['Cpc_Val']) {
						$obBD_con_set->operacionobBD(24, array('Cpc_Cod' => $save_cp['' . $cntcpp]['Cpc_Cod'], 'Pag_Cod' => $pago['Pag_Cod'],'Cpc_Cdc'=>$pago['factu'], 'Com_Cod' => $ultimo_comprobate, 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => $var_pag, 'Cpc_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
						$ultimo_dcc_cod = $obBD_con_set->insercionid($obBD_conexion_set);
						$valgrd = $var_pag;

						if ($pago['Pag_Abr'] == 'CHE') {
							$obBD_con_set->operacionobBD(30, array('Che_Cod' => $ultimo_cheque, 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
						}

						$var_pag = "none";
						$cntcpp++;
					} elseif ($var_pag > (float)$save_cp['' . intval($cntcpp)]['Cpc_Val']) {
						$obBD_con_set->operacionobBD(24, array('Cpc_Cod' => $save_cp['' . intval($cntcpp)]['Cpc_Cod'], 'Pag_Cod' => $pago['Pag_Cod'],'Cpc_Cdc'=>$pago['factu'], 'Com_Cod' => $ultimo_comprobate, 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => ($var_pag - ($var_pag - (float)$save_cp['' . intval($cntcpp)]['Cpc_Val'])), 'Cpc_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
						$ultimo_dcc_cod = $obBD_con_set->insercionid($obBD_conexion_set);
						$valgrd = ($var_pag - ($var_pag - (float)$save_cp['' . intval($cntcpp)]['Cpc_Val']));

						if ($pago['Pag_Abr'] == 'CHE') {
							$obBD_con_set->operacionobBD(30, array('Che_Cod' => $ultimo_cheque, 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
						}

						$var_pag = $var_pag - (float)$save_cp['' . intval($cntcpp)]['Cpc_Val'];
						$cntcpp++;
					}
				}


				if ($pago['Pag_Abr'] == 'ANT') {

					$contador = 0;
					ChromePhp::log("Inicia");
					foreach ($save_pago_anticipos as $pagoAnt) { //LLEGA DOS VECES   858 - 878
						$contador++;
						ChromePhp::log("PRIMERA FILA " . $contador);
						ChromePhp::log("Codigo de anticipo a buscar:" . $pagoAnt['Ant_Cod']);
						// DOS PAGOS DE ANTICIPOS
						$ctsCli =  $obBD_con_set->getArrayConsulta('pag_anticipo_cli.selectWhere', array('where' => array('Ant_Cod' => $pagoAnt['Ant_Cod'])), $obBD_conexion_set);
						//1008
						ChromePhp::log($ctsCli);

						if (($pagoAnt["saldo_aux"] >= (float)$pagoAnt["saldo_pagar"])  && (float)$pagoAnt["saldo_pagar"] > 0) {

							$saldo_aux = 0;
							foreach ($ctsCli as &$ctsc) { // AHY DOS PAGOS  200000  200000 200000    :: - ::
								if ((float)$pagoAnt["saldo_pagar"] != 0) {
									/**      */
									if ($ctsc['Pac_Est'] != 'C') {
										if ($ctsc["Pac_Val"] >= (float)$pagoAnt["saldo_pagar"]) {
											$saldo_aux = (float)$pagoAnt["saldo_pagar"];
											//LO QUE SOBRA TENGO QUE DEVO PAGAR
										} else if ($ctsc["Pac_Val"] < (float)$pagoAnt["saldo_pagar"]) {  //SI
											$saldo_aux = $ctsc["Pac_Val"];   //1000
										}

										ChromePhp::log('Saldo a pagar ' . ((float)$pagoAnt["saldo_pagar"]));
										$obBD_con_set->operacionobBD(28, array('Ddc_Val' => ((float)$saldo_aux), 'Ddc_Obs' => $pago['Glosa'], 'Ant_Cod' => $pagoAnt['Ant_Cod'], 'Dcc_Cod' => $ultimo_dcc_cod, 'Pac_Cod' => $ctsc['Pac_Cod'], 'Com_Cod' => $ultimo_comprobate), $obBD_conexion_set);
										$obBD_con_set->operacionobBD('pag_anticipo_cli.update', array('Pac_Cod' => $ctsc['Pac_Cod'], 'Ant_Cod' => $ctsc['Ant_Cod'], 'Pac_Est' => 'U'), $obBD_conexion_set);
									}

									if (number_format($pagoAnt["saldo"], 2, '.', '') == 0) {
										$obBD_con_set->operacionobBD(27, array('Ant_Cod' => $pagoAnt['Ant_Cod'], 'Ant_Est' => "C"), $obBD_conexion_set);
										$obBD_con_set->operacionobBD('pag_anticipo_cli.update', array('Pac_Cod' => $ctsc['Pac_Cod'], 'Ant_Cod' => $ctsc['Ant_Cod'], 'Pac_Est' => 'C'), $obBD_conexion_set);
									}

									if (number_format($pagoAnt["saldo"], 2, '.', '') > 0) {
										$obBD_con_set->operacionobBD(27, array('Ant_Cod' => $pagoAnt['Ant_Cod'], 'Ant_Est' => "U"), $obBD_conexion_set);
									}
									$pagoAnt["saldo_pagar"] = (float) $pagoAnt["saldo_pagar"] - $saldo_aux;
								}
							}
							unset($ctsc);
						}
					}
				}
				//}

				//en caso de ser pago con cruce de cuentas se genera los debidos detalles de cuentas por cobrar
				if ($pago['Pag_Abr'] == 'CDC') {
					$compras=json_decode(stripslashes($pago['factu']),true);					
					foreach ($compras as $deu) {
						$obBD_con_set->operacionobBD(29, array('Cpp_Cod' => $deu['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], /*'Cpc_Cdc'=>$pago['factu'],*/'Com_Cod' => $ultimo_comprobate, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => $deu['cruce'], 'Pag_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
					}
				}
				if ($pago['Pag_Abr'] == 'CDCXX') {
					$ccc_cnt = $obBD_con_set->getArrayConsulta(21, array('Prv_Cod' => $Prv_Cod), $obBD_conexion_set);
					(float)$cont_ccc = 0;
					$cnt_destino = (float)$pago['Debe'];
					foreach ($ccc_cnt as $deu) {
						if ($cont_ccc < $cnt_destino) {
							if (($cont_ccc + (float)$deu['saldo']) <= $cnt_destino) {
								//insertamos un pago a cuentas por cobrar
								$obBD_con_set->operacionobBD(29, array('Cpp_Cod' => $deu['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $ultimo_comprobate, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => $deu['saldo'], 'Pag_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
								$cont_ccc += (float)$deu['saldo'];
							} else {
								//if( ( $cont_ccc+(float)$deu['saldo'] ) > $cnt_destino && (($cnt_destino-$cont_ccc)*1) > 0){
								//insertamos un pago a cuentas por cobrar
								$obBD_con_set->operacionobBD(29, array('Cpp_Cod' => $deu['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $ultimo_comprobate, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => ($cnt_destino - $cont_ccc), 'Pag_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
								$cont_ccc += ($cnt_destino - $cont_ccc);
							}
						}
					}
				}
			} else {
				// insertamos un asiento por defecto para el pago a clientes
				$obBD_con_set->operacionobBD(23, array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'H', 'Asi_Con' => $pago['concepto'], 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Haber'], 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion_set);
			}
		}

		$response['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo_comprobate&tabla=clientes&campo=Cli_Cod&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod";
		if ($Ses_Emp_Cod == 300) {
			$response['link2'] = "./tes_pri_recibocobro_1.1_empresa.php?Com_Cod=$ultimo_comprobate";
		} else {
			$response['link2'] = "./tes_pri_recibocobro_1.1.php?Com_Cod=$ultimo_comprobate";
		}


		$obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set->conexion);




		if ($obBD_con_set->Error == 0) {
			$response['success'] = true;
		} else
			$response['error'] = $obBD_con_set->MsgError;
	} catch (Exception $e) {
		$obBD_con_set->rollBack_nomsn($obBD_conexion_set);
		$response['success'] = false;
		$response['message'] = '<span class="red">ERROR:</span> ' . $e->getMessage();
	}

	//ChromePhp::log($obBD_con_set->MsgError);
	$obBD_con_set->echoJson($response);
	exit();
}


//NUEVAS CONSULTAS
if (isset($loadAnticipos)) {
	$response['success'] = false;
	$response['message'] = "No se ha logrado realizar la Transaccion";
	$response['rows'] = $obBD_con_set->getArrayConsulta(45, array('Cli_Cod' => $cli_cod_ant), $obBD_conexion_set);

	if ($obBD_con_set->Error == 0) {
		$response['success'] = true;
	}
	$obBD_con_set->echoJson($response);
	exit();
}


?>
<!DOCTYPE html>
<html>

<head>
	<!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?php echo "Ccxcc Lotes [EXA]"; ?></TITLE>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
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
			<h3 class="panel-title">&raquo; Registrar cobros por lotes a clientes</h3>
		</div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div id="listar_cccc">
				<div class="row">
					<form name="searchCccc" id="searchCccc" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchCccc','ajaxComprobante');">
						<div class="col-sm-6">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">Seleccionar Cliente</legend>
								<div class="form-group">
									<label class="col-sm-4 control-label label-sm">C&eacute;dula/RUC:</label>
									<div class="col-sm-6">
										<input name="Cli_Cod" id="Cli_Cod" type="text" style="display:none;" />
										<input name="tip_trans" id="tip_trans" value="add" type="text" style="display:none;" />

										<div class="input-group input-group-xs">
											<input name="Prs_Ced" id="Prs_Ced" type="text" placeholder="Seleccione o cree un cliente..." class="form-control input-xs" tabindex="1" readonly />
											<span class="input-group-btn">
												<button type="button" onclick="$('#clientesDialog').dialog('open');" class="btn btn-success btn-xs" title="Seleccionar Cliente" tabindex="2"><span class="glyphicon glyphicon-list-alt"></span></button>
											</span>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label label-xs">Cliente:</label>
									<div class="col-sm-6"><input name="nombre" id="nombre" class="form-control input-xs databind datatitle" readonly /></div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label label-xs">Direcci&oacute;n:</label>
									<div class="col-sm-6"><input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-xs databind datatitle" readonly /></div>
								</div>
							</fieldset>
							<div class="Titulos2" style="margin-left: 10px;">
								<span id="plan-footer">
									<strong style="color: #ff0000; font-size: 16px;">NOTA:</strong>
									<span style="color:#000; font-size: 14px;">Si el monto a cobrar excede el saldo pendiente, por favor registre el valor excedente como anticipo de cliente.</span>
								</span>
							</div>
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
												$periodos_rows = $obBD_con_get->getArrayConsulta(4, "", $obBD_conexion_get);
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

							<div class="col-xs-8">
							</div>
							<div class="col-xs-4" style="margin-bottom: 10px">
								<div class="input-group">
									<input name="valorPagar" id="valorPagar" class="form-control input-xs databind datatitle" placeholder="Valor a cobrar" />
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
								<span class="glyphicon glyphicon-book"></span> Cobrar</button>
						</div>
					</div>
				</div>
			</div>
			<div id="agregar_cccc" hidden>
				<div class="row">
					<div class="col-sm-12">
						<div class="row">
							<form class="form-horizontal normal" name="formPagos" id="formPagos" method="post" action="javascript:preGuardarPago()">
								<input name="Com_Cod" id="Com_Cod" value="add" type="text" style="display:none;" />
								<div class="col-sm-12">
									<div class="row">
										<div class="col-sm-5">
											<fieldset class="exa-fieldset">
												<legend class="Titulos2">Datos del asiento y cliente</legend>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-sm required" for="Pec_Cod">Periodo contable:</label>
														<div class="col-sm-2">
															<select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" required="" onchange="setFecPeriodoCom()">
																<?php $rows_periodos = $obBD_con_get->getArrayConsulta(5, "", $obBD_conexion_get);
																if (count($rows_periodos) > 0) {
																	foreach ($rows_periodos as $row) {
																?>
																		<?php echo "<option value='$row[Pec_Cod]' data-pla-cod='$row[Pla_Cod]' data-pec-fei='$row[Pec_Fei]' data-pec-fef='$row[Pec_Fef]'  data-periodo='$row[priodo_m]'>$row[priodo_m]</option>"; ?>

																<?php }
																} ?>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-sm required" for="Tia_Cod">Tipo Comprobante:</label>
														<div class="col-sm-4">
															<select id="Tia_Cod" name="Tia_Cod" class="form-control input-xs" required="" onchange="">
																<?php
																$row_rs_tipo_asien2 = $obBD_con_get->getArrayConsulta(6, "ALL", $obBD_conexion_get);
																foreach ($row_rs_tipo_asien2 as $row) { ?>
																	<option value="<?php echo $row['Tia_Cod']; ?>" data-abr="<?php echo $row['Tia_Abr']; ?>">
																		<?php echo mb_convert_encoding($row['Tia_Des'], 'UTF-8', 'ISO-8859-1') ?>
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
															<input name="agg_Cli_Cod" id="agg_Cli_Cod" type="text" style="display:none;" />
															<input name="agg_Prs_Ced" id="agg_Prs_Ced" type="text" class="form-control input-xs" tabindex="1" readonly />
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs">Cliente:</label>
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
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs">Anticipos:</label>
														<div class="col-sm-3">
															<span id="ant_msg" class="form-control input-xs txtRight">$ 0.00</span>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs">Detalle Anticipos:</label>
														<div class="col-sm-8">
															<textarea id="detal" class="form-control input-xs" readonly style="resize: none;"> </textarea>
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
														<label class="col-sm-3 control-label label-sm required">Tipo de pago:</label>
														<div class="col-sm-3">
															<select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs" onchange="enableDisableCampos();">
																<?php
																$row_rs_tipo_asien2 = $obBD_con_get->getArrayConsulta(44, "", $obBD_conexion_get);
																foreach ($row_rs_tipo_asien2 as $row) { ?>
																	<option value="<?php echo $row['Pag_Cod']; ?>" data-abr="<?php echo $row['Pag_Abr']; ?>">
																		<?php echo mb_convert_encoding($row['Pag_Des'], 'UTF-8', 'ISO-8859-1') ?>
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
															<a class="btn btn-info btn-xs" onclick="$('#comprasDialog').dialog('open');"><span class="glyphicon glyphicon-random"></span>&nbsp; Compras</a>
															<span class="glyphicon glyphicon-info-sign" style="font-size:10px;" title="Cant. disponible para el cruce de cuentas con este proveedor"></span>
															<label class="control-label label-xs">Deuda del proveedor $
																<span id="ccc_info">0.00</span>
															</label>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-sm required" for="Ban_Cod">Acreditar a:</label>
														<div class="col-sm-4">
															<select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs ed_element ed_CHE ed_TRF ed_TDC ed_DEP ed_EFE ed_NDC ed_OTR ed_ANT ed_CDC" required="" onchange="" disabled>
																<?php
																$row_rs_tipo_asien2 = $obBD_con_get->getArrayConsulta(10, array('Ban_Tip' => 'B'), $obBD_conexion_get);
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
														<label class="col-sm-3 control-label label-sm required" for="Bak_Cod">Banco:</label>
														<div class="col-sm-4">
															<select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs ed_element ed_CHE ed_TRF" required="" onchange="$('#Che_Num').trigger('onkeyup');" disabled>
																<?php
																$row_rs_tipo_asien2 = $obBD_con_get->getArrayConsulta(9, "", $obBD_conexion_get);
																foreach ($row_rs_tipo_asien2 as $row) { ?>
																	<option value="<?php echo $row['Bak_Cod']; ?>" data-des="<?php echo $row['Pld_Des']; ?>" data-cdc="<?php echo $row['Pld_Cdc']; ?>" data-pla="<?php echo $row['Pld_Cod']; ?>">
																		<?php echo $row['Bak_Des'] ?>
																	</option>
																<?php } ?>
															</select>
														</div>
													</div>
												</div>

												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs required">No. Cuenta:</label>
														<div class="col-sm-4">
															<input type="text" id="Che_Cta" name="Che_Cta" class="form-control input-xs ed_element ed_CHE" autocomplete="off" disabled>
														</div>
													</div>
												</div>

												<div class="row">
													<div class="form-group">
														<label class="col-sm-3 control-label label-xs" for="Num_Doc">Nro Documento:</label>
														<div class="col-sm-2">
															<input id="Num_Doc" name="Num_Doc" class="form-control input-xs ed_elemento ed_TRF" placeholder="002554" type="text" disabled />
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
															<input id="lim_val_pago_cc" name="lim_val_pago_cc" type="text" value="none"  hidden/>
															<input id="Com_Val_pago" name="Com_Val_pago" class="form-control input-xs" type="text" onchange="cambioValPago($(this));" onkeypress="return  validar_decimal(event)" autocomplete="off" />
														</div>
														<div class="col-sm-6 txt-blue" style="padding-left:0;">
															<span class="glyphicon glyphicon-info-sign" style="font-size:10px;" title="Debe completar esta cantidad para realizar el pago"></span>
															<label class="control-label label-xs" title="Debe completar esta cantidad para realizar el pago">Valor a cobrar $
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
										<span class="glyphicon glyphicon-arrow-left"></span> Atr&aacute;s</a>
									<a class="btn btn-success btn-xs" onclick="$('#formPagos').formSubmit();">
										<span class="glyphicon glyphicon-book"></span> Guardar</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>









			<!-- Asignar el total que se va a cancelar a cada anticipo -->
			<div id="agregar_anticipos" style="display: none;">
				<div class="col-sm-12">
					<!--form class="form-horizontal normal" name="formPagos_anticipo" id="formPagos_anticipo" method="post" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?',null,guardarPago)"-->
					<form class="form-horizontal normal" name="formPagos_anticipo" id="formPagos_anticipo" action="javascript:$('#Lista_Anticipos').Search('#formPagos_anticipo','loadAnticipos');">
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">Datos de anticipos del Cliente</legend>
							<div class="col-sm-4">
							</div>
							<div class="col-sm-8">
								<div class="form-group">

									<input name="cli_cod_ant" id="cli_cod_ant" type="text" style="display:none;" />

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
			</div>
			<!-- Fin de cancelar el anticipo -->
		</div>
	</div>
	
	<div id="comprasDialog" title="Cuentas por Pagar">
		<div class="row">
            <div class="col-sm-12">
              <table id="crucesGrid" name="crucesGrid"></table>
            </div>
        </div>
		<br>
		<div class="form-group center">					
			<a id="btnGuardar" class="btn btn-sm btn-success" onclick="$('#comprasDialog').dialog('close')"> <i class="glyphicon glyphicon-ok"></i> Aceptar</a>
		</div>
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
									<label class="col-xs-4 control-label label-xs">Cliente:</label>
									<div class="col-xs-8">
										<input type="text" id="cli_show" class="form-control input-xs" readonly>
										<input type="text" id="Com_Cod_view" style="display:none">
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">No. Compr.:</label>
									<div class="col-xs-8">
										<input type="text" id="compr_show" class="form-control input-xs" readonly>
									</div>
								</div>
								<br>
								<div class="form-group">
									<div class="col-sm-4"></div>
									<div class="col-xs-8">
										<a id="impCanc" target="_blank" href="" style="display: inline;" title="Imprimir Comprobante de Cancelaci&oacute;n">
											<span class="btn btn-primary btn-xs start">
												<i class="glyphicon glyphicon-print"></i>
												<span>Impr. Compr. de cancelaci&oacute;n</span>
											</span>
										</a>
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

	<div id="clientesDialog" title="B&uacute;squeda de Clientes">
		<form class="form-horizontal normal"> </form>
	</div>
	<div id="cuentasDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
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
					<span>Comprobante</span>
				</span>
			</a>
			<a id="impComprCanc" target="_blank" href="" style="display: inline;" title="Imprimir Comprobante">
				<span class="btn btn-primary start">
					<i class="icon-print icon-white"></i>
					<span>Comprobante de cancelaci&oacute;n</span>
				</span>
			</a>
		</center>
	</div>

	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
	<script src="../VALIDACIONES/tes_val_cccc_lotes.js?a=85"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js?a=27"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>

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

</body>

</html>