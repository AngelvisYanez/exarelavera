<?php

/**
 * @abstract Permite modificar anticipos clientes
 * @author Cesar Bermeo
 * @version 2.0
 * Fecha creaci�n: 14/06/2019
 *
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anticipo_cli_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Ant_Cli;

$hoy = date("Y-m-d");

/***
 * Get Banco
 */
$bancos = $obBD_con1->getArrayConsulta('bancos.selectWhere', array('setWhere' => array('')), $obBD_conexion);
/**
 * Tipo Asiento
 */
$rows_tipo_asiento = $obBD_con1->getArrayConsulta('tipo_asien.selectWhere', array('where' => array('Tia_Abr' => 'IN'), 'setWhere' => array('isActive'), 'order' => 'tipo_asien.Tia_Abr'), $obBD_conexion);
/**
 * Tipos de pago
 */
$tPagos = $obBD_con1->getArrayConsulta('tipos_pago.selectWhere', array('where' => array("Pag_Abr='EFE' OR Pag_Abr='CHE' OR Pag_Abr='TRF' OR Pag_Abr='DEP'")), $obBD_conexion);
/**
 * Periodos
 */
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
utf8_encode_deep($periodos);

/**
 * Perfiles
 */
$perfil = $obBD_con1->getArrayConsulta('perfiles.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Usu_Cod' => $Ses_Usu_Cod), 'setWhere' => array('getPerfil')), $obBD_conexion);
utf8_encode_deep($perfil);
/**
 * Verificar el numero de cheque
 */
if (isset($verificaCheque)) {
	$obBD_con1->debugLogs(false);
	$resultado = array(
		'success' => true,
		'numCheque' => $obBD_con1->getArrayConsulta('cheques_ext.selectWhere', array('where' => array('Bak_Cod' => $Bak_Cod, 'Che_Num' => $Che_Num, 'Che_Cta' => $Che_Cta, 'Cli_Cod' => $Cli_Cod)), $obBD_conexion, true),
	);
	$obBD_con1->echoJson($resultado);
}

/**
 * Obtener plan de cuentas y No. Cuenta del banco para anticipos con cheques
 */
if (isset($getPlanCuentasCheq)) {
	$obBD_con1->debugLogs(false);
	$campo;
	if ($Ban_Tip === 'EFE' || $Ban_Tip === 'DEP') {
		$campo = 'C';
	}
	if ($Ban_Tip === 'CHE' || $Ban_Tip === 'TRF') {
		$campo = 'B';
	}
	$resultado = array(
		'success' => true,
		'getData' => $obBD_con1->getArrayConsulta('det_plan.selectWhere', array('Ban_Tip' => $campo, 'where' => array('Pec_Cod' => $Pec_Cod), 'setWhere' => array('byPerioCont', 'byBanco')), $obBD_conexion, true),
	);
	$obBD_con1->echoJson($resultado);
}

/**
 * Busqueda de Grid
 */
/*if (isset($anticiposAjax)) {
	//$obBD_con1->debugLogs(false);
	$data = $_GET;

	// implementacion de Fecha Corte
	if (trim($data['letra']) == 'Activos') {
		if ($data['Pec_Cod'] === 'Corte') {
			$datos = array_merge($_GET, array('setWhere' => array('Corte', 'pagos', 'pagoAnticipo', 'getUsuario', 'getDetAntCCCC')));
		} else {
			$datos = array_merge($_GET, array('setWhere' => array('isActiveAndUsed', 'pagos', 'pagoAnticipo', 'getUsuario', 'getDetAntCCCC')));
		}
	}
	if (trim($data['letra']) == 'Anulados') {
		$datos = array_merge($_GET, array('setWhere' => array('isInactive', 'pagos', 'pagoAnticipo', 'getUsuario', 'getDetAntCCCC')));
	}

	$resultado = $obBD_con1->getPageGrid('anticipos_clientes.selectWhere', $datos, $obBD_conexion, true);
	$obBD_con1->echoJson($resultado);
}*/

if (isset($anticiposAjax)) {
	//$obBD_con1->debugLogs(false);
	$data = $_GET;
	// Filtro por estado: AUC (Todos), AU (Por Consumir), C (Consumidos), I (Anulados)
	$estadoFiltro = isset($data['letra']) ? trim($data['letra']) : 'AU';
	$usarFechaCorte = isset($data['Pec_Cod']) && $data['Pec_Cod'] === 'Corte';

	$setWhere = array('getDetAntCCCC', 'pagos', 'pagoAnticipo', 'getUsuario', 'filterByEstado');
	$data['Ant_Est_Filter'] = $estadoFiltro;

	$datos = array_merge($_GET, array('setWhere' => $setWhere));
	$datos['Ant_Est_Filter'] = $data['Ant_Est_Filter'];
	if ($usarFechaCorte && isset($data['txt_fec_ini'])) {
		$datos['txt_fec_ini'] = $data['txt_fec_ini'];
	}
	if ($usarFechaCorte && isset($data['txt_fec_fin'])) {
		$datos['txt_fec_fin'] = $data['txt_fec_fin'];
	}
	$resultado = $obBD_con1->getPageGrid('anticipos_clientes.selectWhere', $datos, $obBD_conexion, true);
	$obBD_con1->echoJson($resultado);
}



/**
 * Busqueda de asiento gridPrincipal
 */
if (isset($getAsiento)) {
	$obBD_con1->debugLogs(false);
	$resultado = array(
		'success' => true,
		'dataASiento' => $obBD_con1->getArrayConsulta('anticipos_clientes.selectWhere', array('where' => array('anticipos_clientes.Com_Cod' => $Com_Cod), 'setWhere' => array('byAsiento')), $obBD_conexion, true),
	);

	foreach ($resultado['dataASiento'] as &$resp) {
		$tipoPago = $obBD_con1->getRowConsulta('anticipos_clientes.selectWhere', array('Asi_Cod' => $resp['Asi_Cod'], 'where' => array('pagosAntCli.Ant_Cod' => $resp['Ant_Cod']), 'setWhere' => array('pagoAnticipo2')), $obBD_conexion, true);
		if (!empty($tipoPago['Ant_Cod'])) $resp = array_merge($resp, $tipoPago);
	}
	unset($resp);
	$obBD_con1->echoJson($resultado);
}

/**
 * SubGrid
 */
if (isset($movAnticipo)) {
	$obBD_con1->debugLogs(false);
	//$obBD_con1->debugLogs(false);
	$pecCodSeleccionado = isset($_GET['Pec_Cod']) ? $_GET['Pec_Cod'] : null;
	$data = array_merge($_GET, array('setWhere' => array('subGrid')));
	$respuesta =  $obBD_con1->getPageGrid('det_ant_cccc.selectWhere', $data, $obBD_conexion, true);
	$respuesta['Pec_Cod_seleccionado'] = $pecCodSeleccionado;
	$obBD_con1->echoJson($respuesta);
}
/**
 * Get asiento del Subgrid
 */
if (isset($getSubGridAsient)) {
	$obBD_con1->debugLogs(false);
	$resultado = array(
		'success' => true,
		'dataSubAsiento' => $obBD_con1->getArrayConsulta('asientos.selectWhere', array('where' => array('asientos.Com_Cod' => $Com_Cod), 'setWhere' => array('ByDetPlan')), $obBD_conexion, true),
	);
	$obBD_con1->echoJson($resultado);
}
/**
 * Get cheque
 */
if (isset($getCheques)) {
	$obBD_con1->debugLogs(false);
	$resultado = array(
		'success' => true,
		'dataCheque' => $obBD_con1->getArrayConsulta('cheques_ext.selectWhere', array('where' => array('antc.Ant_Cod' => $Ant_Cod, 'antc.Cli_Cod' => $Cli_Cod), 'setWhere' => array('byChequesclientes', 'byDetAntCCCC')), $obBD_conexion, true),
	);
	$obBD_con1->echoJson($resultado);
}
/**
 * Protestar cheque
 */
if (isset($protestarCheq)) {
	$obBD_con1->debugLogs(false);
	$resp = array('success' => false);
	$obBD_ins1 = new  Class_Log_Datos_Ant_Cli;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	//$obBD_ins1->debug(true);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try {
		$Pec_Cod = $obBD_ins1->getRowConsulta('perio_cont.selectWhere', array('Date' => date("Y-m-d"), 'where' => array(), 'setWhere' => array('getByDate', 'getPerioByFec')),  $obBD_conexion, true);
		if (count($Pec_Cod) > 0) {
			$var_mes = explode('-', $hoy);
			$tipo_asien_prt = $obBD_ins1->getRowConsulta('tipo_asien.selectWhere', array('where' => array('Tia_Abr' => 'DG'), 'setWhere' => array('isActive')), $obBD_conexion, true);
			$Com_Num = $obBD_ins1->getComNumPecAuto($tipo_asien_prt['Tia_Cod'], $Pec_Cod['Pec_Cod'], $var_mes[1], $obBD_conexion, true);
			//Actualizamos el estado del cheque
			$obBD_ins1->operacionobBD('cheques_ext.update', array('Che_Cod' => $row['Che_Cod'], 'Cli_Cod' => $row['Cli_Cod'], 'Bak_Cod' => $row['Bak_Cod'], 'Che_Est' => 'P'), $obBD_conexionIns, true);
			//Buscamos ese pago
			$pagoAntProv = $obBD_ins1->getRowConsulta('pag_anticipo_cli.selectWhere', array('where' => array('Pac_Cod' => $row['Pac_Cod'])), $obBD_conexion, true);
			//actualizar estado del pago de anticip� a clientes
			$obBD_ins1->operacionobBD('pag_anticipo_cli.update', array('Pac_Cod' => $pagoAntProv['Pac_Cod'], 'Ant_Cod' => $pagoAntProv['Ant_Cod'], 'Asi_Cod' => $pagoAntProv['Asi_Cod'], 'Pag_Cod' => $pagoAntProv['Pag_Cod'], 'Pac_Est' => 'C', 'Pac_Obs' => 'CHEQUE No.' . $row['Che_Num'] . ' PROTESTADO'), $obBD_conexionIns, true);
			//modificar asiento de cheque protestado
			$obBD_ins1->operacionobBD('asientos.update', array('Asi_Cod' => $row['Asi_Cod'], 'Asi_Glo' => "CHEQUE No. " . $row['Che_Num'] . " protestado"), $obBD_conexionIns, true);
			//insertamos un comprobante
			$obBD_ins1->operacionobBD('comprobantes.insert', array('Pec_Cod' => $Pec_Cod['Pec_Cod'], 'Cli_Cod' => $row['Cli_Cod'], 'Com_num' => $Com_Num, 'Com_Fec' => $hoy, 'Com_Con' => "CHEQUE No. " . $row['Che_Num'] . " protestado", 'Com_Val' => $row['Che_Val'], 'Tia_Cod' => $tipo_asien_prt['Tia_Cod']), $obBD_conexionIns, true);
			$ultimo_comprobate = $obBD_ins1->insercionid($obBD_conexionIns);
			//Busco la actual cuenta que esta al DEBE
			$PldCod_Actual = $obBD_ins1->getRowConsulta('asientos.selectWhere', array('where' => array('Com_Cod' => $row['Com_Cod'], 'Asi_Deh' => 'D')), $obBD_conexion, true);
			// insertamos un asiento inicial para el cheque protestado
			$obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'H', 'Asi_Glo' => "CHEQUES PROTESTADOS", 'Asi_Val' => $row['Che_Val'], 'Pld_Cod' => $PldCod_Actual['Pld_Cod']), $obBD_conexionIns, true);
			$obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'D', 'Asi_Glo' => "CHEQUE No. " . $row['Che_Num'] . " protestado", 'Asi_Val' => $row['Che_Val'], 'Pld_Cod' => $row['Pld_Cod']), $obBD_conexionIns, true);
			//creamos un registro en det_ant_cccc
			$obBD_ins1->operacionobBD('det_ant_cccc.insert', array('Ant_Cod' => $row['Ant_Cod'], 'Ddc_Val' => $row['Che_Val'], 'Com_Cod' => $ultimo_comprobate, 'Ddc_Obs' => "CHEQUE No. " . $row['Che_Num'] . " protestado", 'Pac_Cod' => $row['Pac_Cod']), $obBD_conexionIns, true);
			//Busco el anticipo clientes, cambios estado de anticpos clientes a usado Ant_Cod
			$anticipoCambio = $obBD_ins1->getRowConsulta('anticipos_clientes.selectWhere', array('where' => array('anticipos_clientes.Ant_Cod' => $row['Ant_Cod']), array('Ant_Cod', 'Com_Cod', 'Cli_Cod')), $obBD_conexion, true);
			$obBD_ins1->echoLog($anticipoCambio);
			$obBD_ins1->operacionobBD('anticipos_clientes.update', array('Ant_Cod' => $anticipoCambio['Ant_Cod'], 'Com_Cod' => $anticipoCambio['Com_Cod'], 'Cli_Cod' => $anticipoCambio['Cli_Cod'], 'Ant_Est' => 'U'), $obBD_conexionIns, true);
			$Pec_Cod_val = $Pec_Cod['Pec_Cod'];
			$resp['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo_comprobate&tabla=clientes&campo=Cli_Cod&tipo=" . $tipo_asien_prt['Tia_Cod'] . "&Pec_Cod=$Pec_Cod_val";
			//throw new Exception("The field is undefined.");
		}
	} catch (Exception $e) {
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_ins1->echoJson($resp);
	}
	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$obBD_con1->echoJson($resp);
}
/**
 * Anular anticipo
 */
if (isset($anularAnticipo)) {
	$obBD_con1->debugLogs(false);
	$resp = array('success' => false);
	$obBD_ins1 = new Class_Log_Datos_Ant_Cli;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	//$obBD_ins1->debug(true);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try {
		$obBD_ins1->echoLog($data);
		//Inactivar un anticipo
		$obBD_ins1->operacionobBD('anticipos_clientes.setInactive', array('Ant_Cod' => $data[0]['Ant_Cod']), $obBD_conexionIns, true);
		//Inactivar comprobantes
		$obBD_ins1->operacionobBD('comprobantes.2', array('Com_Cod' => $data[0]['Com_Cod']), $obBD_conexionIns, true);
		//Buscamos  el desglose del anticipo
		$pagosAntCli = $obBD_ins1->getArrayConsulta('pag_anticipo_cli.selectWhere', array('where' => array('pag_anticipo_cli.Ant_Cod' => $data[0]['Ant_Cod'])),  $obBD_conexion, true);
		foreach ($pagosAntCli as &$pgc) {
			//Inactivar un pago anticipo clientes
			$obBD_ins1->operacionobBD('pag_anticipo_cli.setInactive', array('Pac_Cod' => $pgc['Pac_Cod']), $obBD_conexionIns, true);
		}
		unset($pgc);
		//throw new Exception("The field is undefined.");

	} catch (Exception $e) {
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_ins1->echoJson($resp);
	}
	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$obBD_con1->echoJson($resp);
}
/**
 * Guardar Anticipo
 */
if (isset($save)) {

	//$Pec_Cod=$obBD_con1->getRowConsulta('perio_cont.selectWhere', array('Date'=>date("Y-m-d"),'where'=>array(),'setWhere'=>array('getByDate','getPerioByFec')),  $obBD_conexion,true);
	$resp = array('success' => false);
	$resp['arrayCheques'] = array();
	$resp['isCheque'] = false;
	$obBD_ins1 = new Class_Log_Datos_Ant_Cli;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	//$obBD_ins1->debug(true);
	$obBD_ins1->debugLogs(false);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);

	try {
		$data = $_POST;
		$obBD_ins1->echoLog($data);
		$arrayData = $obBD_con1->getArrayConsulta('anticipos_clientes.selectWhere', array('where' => array('anticipos_clientes.Ant_Cod' => $data['Ant_Cod']), 'setWhere' => array('byAsiento', 'searchPagos')),  $obBD_conexion, true);
		$obBD_ins1->echoLog($arrayData);
		foreach ($arrayData as &$respt) {
			//Borro cheques
			if (!empty($respt['Che_Cod'])) $obBD_ins1->operacionobBD('cheques_ext.deleteWhere', array('Che_Cod' => $respt['Che_Cod']), $obBD_conexionIns, true);
		}
		unset($respt);

		$Pec_Cod = $obBD_con1->getRowConsulta('perio_cont.selectWhere', array('Date' => $data["Ant_Fec"], 'where' => array(), 'setWhere' => array('getByDate', 'getPerioByFec')),  $obBD_conexion, true);
		if ($data['Ant_Val'] * 1 != $data['totalFinal'] * 1) {
			$valorAct = $data['totalFinal'];
		} else {
			$valorAct = $data['Ant_Val'];
		}
		if (isset($saveDataModAnt)) {
			//Update comprobantes Com_Cod
			$obBD_ins1->operacionobBD('comprobantes.update', array('Com_Cod' => $data['Com_Cod'], 'Pec_Cod' => $Pec_Cod['Pec_Cod'], 'Cli_Cod' => $data['Cli_Cod'], 'Com_Num' => $data['Com_Num'], 'Com_Fec' => $data['Ant_Fec'], 'Com_Con' => $data['Ant_Obs'], 'Com_Val' => $valorAct, 'Tia_Cod' => $data['Tia_Cod']), $obBD_conexionIns, true);
			//Insert anticipo
			$obBD_ins1->operacionobBD('anticipos_clientes.update', array('Ant_Cod' => $data['Ant_Cod'], 'Ant_Fec' => $data['Ant_Fec'], 'Ant_Val' => $valorAct, 'Ant_Obs' => $data['Ant_Obs'], 'Com_Cod' => $data['Com_Cod'], 'Cli_Cod' => $data['Cli_Cod']), $obBD_conexionIns, true);
			//Borrar asientos que tenga referencia con anticipos y que no sean algun cheque protestado
			$obBD_ins1->operacionobBD('asientos.2', array('Com_Cod' => $data['Com_Cod']), $obBD_conexionIns, true);
			$contChk = 0;
			$obBD_ins1->echoLog($anticipoGrid);
			foreach ($anticipoGrid as $pago) {
				if ($pago['grid_tipp'] != 'pago') {
					$obBD_ins1->echoLog($pago);
					//Select del la cuenta contable para Pago anticipos clientes
					$Pld_Cod_ini = $obBD_con1->getArrayConsulta('det_plan.selectWhere', array('setWhere' => array('setEmpCod', 'setPlanParam', 'isActive', 'isParamANC')), $obBD_conexion, true);
					//Inserto asiento de tipo Debe
					$obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'H', 'Asi_Val' => $pago['Haber'], 'Pld_Cod' => $Pld_Cod_ini[0]['Pld_Cod'], 'Asi_Glo' => $pago['Asi_Glo'], 'Asi_Con' => 'ANTICIPO A CLIENTES'), $obBD_conexionIns, true);
				} else {
					$obBD_ins1->echoLog('else');
					$obBD_ins1->echoLog($pago);
					/* if(!empty($pago['Che_Cod'])){
							//$obBD_ins1->echoLog('entro');
							//Borro cheques
							//$obBD_ins1->operacionobBD('cheques_ext.deleteWhere',array('Che_Cod'=>$pago['Che_Cod']), $obBD_conexionIns,true);
						} */
					//obBD_con_set->operacionobBD('perfiorgan.deleteWhere', array('Per_Cod'=>$Per_Cod), $obBD_conexion_set);
					//Inserto asiento de tipo Haber
					$obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'D', 'Asi_Val' => $pago['Debe'], 'Pld_Cod' => $pago['Pld_Cod'], 'Asi_Glo' => $pago['Asi_Glo']), $obBD_conexionIns, true);
					$lastAsiento = $obBD_ins1->insercionid($obBD_conexionIns);
					// Verificamos si el pago es en cheque
					if ($pago['Pag_Abr'] == 'CHE') {
						$obBD_ins1->echoLog($resp['arrayCheques']);

						$contChk += 1;
						$resp['isCheque'] = true;
						array_push($resp['arrayCheques'], array('link' => "?codigo2=$contChk&asi=" . $lastAsiento . "&ban=" . $pago['Bak_Cod'] . "&pro=" . $data['Cli_Cod'], 'che' => "No.:" . $pago['Che_Num'] . " - Valor:$ " . $pago['Debe']));
						//insertar un registro en la tabla cheques
						$obBD_ins1->echoLog($pago['Che_Est']);
						$obBD_ins1->operacionobBD('cheques_ext.insert', array('Cli_Cod' => $data['Cli_Cod'], 'Bak_Cod' => $pago['Bak_Cod'], 'Che_Num' => $pago['Che_Num'], 'Che_Fec' => $pago['Che_Fec'], 'Che_Val' => $pago['Debe'], 'Che_Obs' => $data['Ant_Obs'], 'Che_Cli' => $data['nombre'], 'Che_Cta' => $pago['Pac_Cto']), $obBD_conexionIns, true);
						$ultimo_cheque =  $obBD_ins1->insercionid($obBD_conexionIns);
					}
					if ($pago['Pag_Abr'] == 'EFE' || $pago['Pag_Abr'] == 'DEP' || $pago['Pag_Abr'] == 'TRF') {
						$valorCto = '';
						$cheCod = null;
					} else {
						$valorCto = $pago['Pac_Cto'];
						$cheCod = $ultimo_cheque;
					}
					// insertamos un pag_anticipo_cli
					$obBD_ins1->operacionobBD('pag_anticipo_cli.insert', array('Pac_Num' => '0', 'Pac_Cto' => $valorCto, 'Pac_Ctd' => $pago['Pac_Ctd'], 'Pac_Val' => $pago['Debe'], 'Ant_Cod' => $data['Ant_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Asi_Cod' => $lastAsiento, 'Che_Cod' => $cheCod), $obBD_conexionIns, true);
				}
			}
			$pecCod = $Pec_Cod['Pec_Cod'];
			$resp['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=" . $data['Com_Cod'] . "&tabla=cliente&campo=Cli_Cod&tipo=" . $data['Tia_Cod'] . "&Pec_Cod=$pecCod";
			$obBD_con1->echoLog($resp);
			//throw new Exception("The field is undefined.");
		}
	} catch (Exception $e) {
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_ins1->echoJson($resp);
	}
	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$obBD_con1->echoJson($resp);
}

/**
 * Imprimir asiento subgrid
 */
if (isset($impAsiento)) {
	$resp = array('success' => false);
	$obBD_ins1 = new Class_Log_Datos_Ant_Cli;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	$obBD_ins1->debug(true);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try {
		$Pec_Cod = $obBD_con1->getRowConsulta('perio_cont.selectWhere', array('Date' => date("Y-m-d"), 'where' => array(), 'setWhere' => array('getByDate', 'getPerioByFec')),  $obBD_conexion, true);
		$Pec_Cod_val = $Pec_Cod['Pec_Cod'];
		$resp['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=" . $params[0]['Com_Cod'] . "&tabla=cliente&campo=Cli_Cod&tipo=" . $params[0]['Tia_Cod'] . "&Pec_Cod=$Pec_Cod_val";
	} catch (Exception $e) {
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_ins1->echoJson($resp);
	}
	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$obBD_con1->echoJson($resp);
}
/* GET clientes */
if (isset($clientesAjax)) {
	$dataClie = $obBD_con1->getPageGridJson(1, $_GET, $obBD_conexion);
	$obBD_con1->echoJson($dataClie);
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
	<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?Php echo "Ant.Cliente Modif/Consultar [EXA]"; ?></TITLE>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
	<script>
		var peridodo = <?php echo json_encode($periodos) ?>,
			prf = <?php echo json_encode($perfil) ?>;
	</script>
	<style>
		.pagination>li>a,
		.pagination>li>span {
			padding: 4px 2px;
		}

		.pagination {
			/*display: block;*/
			margin: 0;
			padding: 0;
		}

		.chosen-default span,
		.chosen-single span {
			color: #555;
		}

		.chosen-single span {
			padding-left: 5px;
		}
	</style>
</HEAD>

<BODY>
	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo;Modificar Anticipos a Clientes</h3>
		</div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div id="documentoSearch">
				<div class="row">
					<form name="searchAnticipos" id="searchAnticipos" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchAnticipos','anticiposAjax');">
						<div class="col-xs-5">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">B&uacute;squeda</legend>
								<div class="form-group">
									<label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
									<div class="col-xs-10 radioset opt_search">
										<input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">Cliente</label>
										<input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radsc2">C&eacute;dula/RUC</label>
									</div>
								</div>

								<div class="form-group">
									<label class="col-xs-2 control-label">B&uacute;squeda:</label>
									<div class="col-xs-7">
										<div class="input-group">
											<input name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
											<span class="input-group-btn">
												<button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Documento" tabindex="-1">
													<span class="glyphicon glyphicon-search"></span>
													<span>Buscar</span>
												</button>
											</span>
										</div>
									</div>
									<input type="text" tabindex="-1" style="display:none;" />
								</div>
							</fieldset>
						</div>
						<div class="col-sm-7">
						<fieldset class="exa-fieldset">
								<legend class="Titulos2">Filtros</legend>
								<div class="form-group">
									<label class="col-sm-1 control-label label-xs">Periodo:</label>
									<div class="col-sm-2">
										<!--select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs">
											<option data-year='2018' data-inicio='2018-01-01' data-fin='2030-12-31' value="T">Todos</option>
											<option data-year='Corte' data-inicio='' data-fin='' value="Corte">Fecha Corte</option>
											<?php
											foreach ($periodos as $p) {
												echo "<option data-year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data-pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
											}
											?>
										</select-->
										<select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs">
											<option data-year='2018' data-inicio='2018-01-01' data-fin='2030-12-31' value="T">Todos</option>
											<option data-year='Corte' data-inicio='' data-fin='' value="Corte">Fecha Corte</option>
											<?php
											$primerPeriodo = null;
											if (count($periodos) > 0) {
												$primerPeriodo = $periodos[0]['Pec_Cod'];
											}
											foreach ($periodos as $p) {
												$selected = ($primerPeriodo && $p['Pec_Cod'] == $primerPeriodo) ? 'selected' : '';
												echo "<option data-year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data-pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]' $selected>Periodo $p[Year]</option>";
											}
											?>
										</select>

									</div>
									<div class="col-sm-3" id="btnFiltroCorte" style="display: none; margin-left: -20px; width: 1px;">
										<button type="button" class="btn btn-info" size="4" style="height: 22px; width: 22px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Filtro Fecha Corte Activo: se esta visualizando todos los anticipos de clientes hasta la fecha elegida">
											<i class="glyphicon glyphicon-info-sign" style="font-size: 14px;"></i>
										</button>
									</div>
									<script>
										document.getElementById('Pec_Cod').addEventListener('change', function() {
											var btnFiltroCorte = document.getElementById('btnFiltroCorte');
											if (this.value === 'Corte') {
												btnFiltroCorte.style.display = 'block';
											} else {
												btnFiltroCorte.style.display = 'none';
											}
										});
									</script>
									<div class="col-sm-8" style="margin-left: 10px;">
										<div class="input-group input-group-xs">
											<span class="input-group-addon bold alert-info">Desde:</span>
											<input onchange="cambioPreiodoSearch('txt')" name="txt_fec_ini" type="text" id="txt_fec_ini" size="6" class="form-control input-xs datepicker databind" style="text-align: center;"/>
											<span class="input-group-addon bold alert-info">Hasta:</span>
											<input name="txt_fec_fin" type="text" id="txt_fec_fin" size="6" class="form-control input-xs datepicker databind" style="text-align: center;" />
										</div>
									</div>
								</div>
							</fieldset>
							<!-- Seccion por si se quiere implementar Consumidos-Por Consumir -->

							<!--div class="form-group">
								<div class="col-sm-12 center">
									<input type="hidden" id="letra" name="letra" value="Activos" />
									<nav>
										<?php $valores = array("Anulados", "Activos"); ?>
										<ul class="pagination pagination-centered">
											<?php foreach ($valores as $val) { ?>
												<li <?php if ($val == 'Activos') echo 'class="active"'; ?>>
													<a>
														<?php echo $val ?>
													</a>
												</li>
											<?php } ?>
										</ul>
									</nav>
								</div>
							</div-->
							<div class="form-group">
								<div class="col-sm-12 center">
									<?php
									$valorActual = isset($_GET['letra']) ? $_GET['letra'] : 'AU';
									$valores = array(
										array("label" => "Todos", "value" => "AUC"),
										array("label" => "Por Consumir", "value" => "AU"),
										array("label" => "Consumidos", "value" => "C"),
										array("label" => "Anulados", "value" => "I")
									);?>
									<input type="hidden" id="letra" name="letra" value="<?php echo htmlspecialchars($valorActual); ?>" />
									<nav>
										<ul class="pagination pagination-centered">
											<?php foreach ($valores as $val) { ?>
												<li <?php if ($val['value'] == $valorActual) echo 'class="active"'; ?>>
													<a data-value="<?php echo htmlspecialchars($val['value']); ?>">
														<?php echo $val['label']; ?>
													</a>
												</li>
											<?php } ?>
										</ul>
									</nav>
								</div>
							</div>

						</div>
					</form>
					<div class="col-xs-12" style="min-height: 360px;">
						<table id="searchGrid" name="searchGrid"></table>
						<div class="Titulos2">
							<span id="plan-footer">
								<strong>Leyenda:</strong>
								<span class="glyphicon glyphicon-stop green"></span> Anticipos Usados </span>
							<span class="glyphicon glyphicon-stop gray"></span> Anticipos Consumidos </span>
							<span class="glyphicon glyphicon-stop red"></span> Anticipos Anulados </span>
						</div>
						<div id="searchGridPager"></div>
					</div>
				</div>
			</div>
			<div id="verAsientoDialogMod" title="Datos">
				<div class="row">
					<div class="col-sm-12">
						<div id="tabs_sub_ant_det" class="ui-tab-fix">
							<ul style="font-size: 12px;" role="tablist">
								<li id="sub_ant_detasi">
									<a href="#sub_ant_det_asi">Asiento</a>
								</li>
							</ul>
							<div id="sub_ant_det_asi">
								<div class="row">
									<div class="col-sm-12" style="padding-top: 10px;">
										<table id="showSubGridAsi" name="showSubGridAsi"></table>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="verPagosDialogMod" title="Pago">
				<div class="row">
					<div class="col-sm-12">
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">Datos del Anticipo</legend>
							<form id="verPagosForm" class="form-horizontal normal">
								<div class="row">
									<div class="col-sm-7">
										<div class="form-group">
											<label class="col-xs-4 control-label label-xs">Cliente:</label>
											<div class="col-xs-8">
												<input type="text" id="prov_show" class="form-control input-xs" readonly>
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
								<div class="form-group condensed">
									<div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;">
										<b>USUARIO:</b>
										<span id="usuario" name="usuario" class="databind"></span>

										<b>CREACIÓN:</b>
										<span id="Com_Sys" name="Com_Sys" class="databind"></span>
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
						<div id="tabs_ant_det" class="ui-tab-fix">
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
												<span class="glyphicon glyphicon-stop red"></span> Cheques protestados </span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
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
											<select id="Che_imp" name="Che_imp" class="form-control input-xs" onchange="cambiarChek()">
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
											<td align="center" class="ui-widget-content" colspan="6">
												<b>&nbsp; plantillas &nbsp;</b>
											</td>
										</tr>
										<tr id="impchetd">
											<td align="center">
												<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_mac_1.0.php" href="" target="_blank"
													title="Banco de Machala">
													<img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35" />
												</a>
											</td>
											<td align="center">
												<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_pac_1.0.php" href="" target="_blank"
													title="Banco del Pacifico">
													<img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23" />
												</a>
											</td>
											<td align="center">
												<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_rum_1.0.php" href="" target="_blank"
													title="Banco del Rumiñahui">
													<img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15" />
												</a>
											</td>
											<td align="center">
												<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_gua_1.0.php" href="" target="_blank"
													title="Banco del Guayaquil">
													<img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18" />
												</a>
											</td>
											<td align="center">
												<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_pch_1.0.php" href="" target="_blank"
													title="Banco del Pichincha">
													<img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30" />
												</a>
											</td>
											<td align="center">
												<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_int_1.0.php" href="" target="_blank"
													title="Banco Internacional">
													<img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32" />
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
			<div id="documentoUpdate" hidden="true">
				<div class="row">
					<div class="col-sm-12">
						<form class="form-horizontal normal" id="anticipoCliForm" name="anticipoCliForm" action="javascript:updateData('anticipoCliForm','saveDataModAnt','modificar')">
							<div class="col-sm-12">
								<div class="row">
									<div class="col-sm-6">
										<fieldset class="exa-fieldset">
											<legend class="Titulos2">Datos del Proveedor</legend>
											<div class="form-group">
												<label class="col-sm-4 control-label label-sm required">C&eacute;dula/RUC:</label>
												<div class="col-sm-6">
													<input name="bandera_prov" id="bandera_prov" type="text" value="nosel" style="display:none;" />
													<input name="Prs_Cod" id="Prs_Cod" type="text" style="display:none;" />
													<input name="Cli_Cod" id="Cli_Cod" type="text" style="display:none;" />
													<input name="Com_Cod" id="Com_Cod" type="text" style="display:none;" />
													<input name="Ant_Cod" id="Ant_Cod" type="text" style="display:none;" />
													<input name="op_opciones" type="text" value="c" style="display: none;" />
													<input type="text" name="totalFinal" id="totalFinal" hidden>
													<input name="Ant_Val" id="Ant_Val" type="text" value="0.00" style="display: none;" />
													<!-- <input name="Prs_Ced" id="Prs_Ced" type="text" class="form-control input-sm" tabindex="1" required="" readonly /> -->
													<div class="input-group input-group-sm">
													<input name="Prs_Ced" id="Prs_Ced" type="text" placeholder="Seleccione o cree un cliente..."  class="form-control input-sm" tabindex="1" required="" readonly/>
														<span class="input-group-btn">
															<button type="button" onclick="$('#clientesDialog').dialog('open');" class="btn btn-success btn-sm" title="Buscar Cliente"  tabindex="2">
																<span class="glyphicon glyphicon-search"></span>
															</button>
														</span>
													</div>
												</div>
											</div>
											<div class="form-group">
												<label class="col-sm-4 control-label label-sm">Cliente:</label>
												<div class="col-sm-6">
													<input name="nombre" id="nombre" class="form-control input-sm databind datatitle" readonly />
												</div>
											</div>
											<div class="form-group">
												<label class="col-sm-4 control-label label-sm">Direcci&oacute;n:</label>
												<div class="col-sm-6">
													<input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-sm databind datatitle" readonly />
												</div>
											</div>
										</fieldset>
									</div>
									<div class="col-sm-6">
										<fieldset class="exa-fieldset">
											<legend class="Titulos2">Datos del Anticipo</legend>
											<div class="form-group">
												<label class="col-sm-4 control-label label-sm required">Fecha:</label>
												<div class="col-sm-6">
													<div class="input-group">
														<input name="Ant_Fec" type="text" id="Ant_Fec" size="10" class="form-control input-sm datepicker" required="" />
														<span class="input-group-addon">
															<span class="glyphicon glyphicon-calendar"></span>
														</span>
													</div>
												</div>
											</div>
											<div class="form-group">
												<label class="col-sm-4 control-label label-sm required">Tipo de Asiento:</label>
												<div class="col-sm-6">
													<input type="text" name="Tia_Cod_temp" id="Tia_Cod_temp" hidden>
													<input type="text" name="Com_Num" id="Com_Num" hidden>
													<select id="Tia_Cod" name="Tia_Cod" class="form-control input-sm readOnly" required="">
														<?php
														if (count($rows_tipo_asiento) > 0) {
															foreach ($rows_tipo_asiento as $row) {
																echo "<option value='$row[Tia_Cod]' data-abr='$row[Tia_Abr]'>$row[Tia_Abr] - $row[Tia_Des]</option>";
															}
														}
														?>
													</select>
												</div>
											</div>

											<div class="form-group">
												<label class="col-sm-4 control-label label-sm ">Observaci&oacute;n:</label>
												<div class="col-sm-6">
													<!-- <div class="input-group input-group-sm"> -->
													<textarea class="form-control" id="Ant_Obs" val="" name="Ant_Obs" rows="2"></textarea>
													<!-- </div> -->
												</div>
											</div>
										</fieldset>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<div class="row">
							<div class="col-sm-12">
								<div id="contenedor_pagos" style="width: 100%;padding-top: 10px;">
									<table id="pagos"></table>
									<div id="pagosPager"></div>
								</div>
							</div>
						</div>
						<div class="separator"></div>
						<div class="row">
							<div class="col-sm-12">
								<button class="btn btn-sm btn-inverse no" onclick="moveToMain();limpiarFormAnticipos();">
									<i class="glyphicon glyphicon-arrow-left"></i> Atras</button>
								<button class="btn btn-sm btn-success no" onclick="$('#anticipoCliForm').formSubmit();">
									<i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
							</div>
						</div>
					</div>
				</div>
			</div>


			<div id="imprimir" style="display: none;">
				<div style="width: 1030px;">
					<?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE ANTICIPOS CLIENTES', '<span class="subtitle">Total de registros</span>', $obBD_conexion) ?>
					<table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;table-layout:auto  ;font-size:12px;"></table>
					<?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Inicio del diálogo para buscar Clientes -->
	<div id="clientesDialog" title="B&uacute;squeda de Clientes">
		<form class="form-horizontal normal"> </form>
	</div>

	<!-- dialogo de registro de pagos de anticipo -->
	<div id="pagosDialog" title="Agregar Pagos">
		<form id="pagosForm" class="form-horizontal normal">
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Tipo:</label>
				<div class="col-xs-6">
					<select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs readOnly" onchange="cambiarCamposPagos($(this).find(':selected').data().class, $('#Pag_Cod option:selected').attr('data-abr'))"
						required="">
						<?php
						if (count($tPagos) > 0) {
							foreach ($tPagos as $row) {
								echo "<option value='$row[Pag_Cod]' data-abr='$row[Pag_Abr]' data-class='$row[Pag_Des]' >$row[Pag_Des]</option>";
							}
						}
						?>
					</select>
				</div>
			</div>

			<!-- Bancos de DataBase -->
			<div class="form-group Transferencia Efectivo Deposito">
				<label class="col-xs-4 control-label label-xs required">Cuenta:</label>
				<div class="col-xs-6">
					<select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly" required="">
					</select>
				</div>
			</div>
			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs required">Bancos:</label>
				<div class="col-xs-6">
					<select name="Bak_Cod" id="Bak_Cod" class="form-control input-xs readOnly" onchange="" required="">
						<?php
						if (count($bancos) > 0) {
							foreach ($bancos as $bank) {
								if ($bank['Bak_Des'] != 'Ninguno') {
									echo "<option value='$bank[Bak_Cod]'>$bank[Bak_Des]</option>";
								}
							}
						}
						?>
					</select>
				</div>
			</div>

			<div class="form-group  Deposito Transferencia">
				<label class="col-xs-4 control-label label-xs required">Cta. Destino:</label>
				<div class="col-xs-6">
					<input type="text" id="Pac_Ctd" name="Pac_Ctd" onchange="" onkeypress="return soloNumeros(event)" class="form-control input-xs">
				</div>
			</div>
			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs required">Cta. Cheque:</label>
				<div class="col-xs-6">
					<input type="text" id="Che_Ctd" name="Che_Ctd" onchange="vaciarNumero()" onkeypress="return soloNumeros(event)" class="form-control input-xs">
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs required">Fecha:</label>
				<div class="col-xs-6">
					<input name="Che_Fec" type="text" id="Che_Fec" size="10" class="form-control input-xs datepicker" required="" />
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs required">No. cheque:</label>
				<div class="col-xs-6">
					<div class="input-group input-group-xs">
						<span class="input-group-addon validate">
							<i id="indicadorChe" class=""></i>
						</span>
						<input type="text" id="Che_Num" name="Che_Num" onchange="" class="form-control input-xs" onkeyup="verificarNoCheque(this.value)"
							onkeypress="return soloNumeros(event)">
					</div>
				</div>
			</div>


			<div class="form-group Transferencia Deposito Efectivo Cheque">
				<label class="col-xs-4 control-label label-sm required">Valor:</label>
				<div class="col-xs-6 ">
					<div class="input-group input-group-xs">
						<span class="input-group-addon">
							<i id="indicadorChe" class="glyphicon glyphicon-usd"></i>
						</span>
						<input name="Pac_Val" type="text" id="Pac_Val" size="10" class="form-control input-xs" onchange="changeValueInPago()" required=""
							autocomplete="off" onkeypress="return  validar_decimal(event)" />
					</div>
				</div>
			</div>

			<div class="form-group center">
				</br>
				<a id="btnGuardar" class="btn btn-sm btn-primary" onclick="agregarFila(0)">
					<i class="glyphicon glyphicon-floppy-disk"></i> Agregar</a>
			</div>
		</form>
	</div>
	<div id="verPagosDialog" title="Pago">
		<form id="verPagosForm" class="form-horizontal normal">
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs">Tipo de pago:</label>
				<div class="col-xs-6">
					<input type="text" id="pago_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs">No. cheque:</label>
				<div class="col-xs-6">
					<input type="text" id="numero_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<!-- Bancos de DataBase -->
			<div class="form-group Cheque Transferencia">
				<label class="col-xs-4 control-label label-xs">Cuenta:</label>
				<div class="col-xs-6">
					<input type="text" id="cuenta_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group  Deposito Transferencia">
				<label class="col-xs-4 control-label label-xs">Cta. Destino:</label>
				<div class="col-xs-6">
					<input type="text" id="destino_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs">Fecha:</label>
				<div class="col-xs-6">
					<input type="text" id="fecha_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group Transferencia Deposito Efectivo Cheque">
				<label class="col-xs-4 control-label label-sm">Valor:</label>
				<div class="col-xs-6 ">
					<input type="text" id="valor_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group center">
				</br>
				<a class="btn btn-sm btn-primary" onclick="$('#verPagosDialog').dialog('close');">
					<i class="glyphicon glyphicon-remove"></i> Cerrar</a>
			</div>
		</form>
	</div>



	<script src="../VALIDACIONES/tes_val_anticipo_mod_cli_2.0.js?k=151"></script>

	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>

</HTML>