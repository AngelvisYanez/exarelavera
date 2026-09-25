<?php

/**
 * @abstract Permite registrar las labores (Relavera - Mapeo Interactivo Georreferenciado)
 * @author Cesar Bermeo.
 * @version 2.0-maestro-detalle-relavera
 * Fecha de creacion: 07-02-2019
 *
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../bananero/LOGICA/ban_log_labores_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Lab_2;

$hoy = date("Y-m-d");


/**
 * Eliminar Labor
 */
if (isset($elimLabor)) {
	$obBD_ins1 = new Class_Log_Datos_Lab_2;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try {
		$obBD_con1->operacionobBD('labores.setInactive', array('Lab_Cod' => $Lab_Cod , 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
	} catch (Exception $e) {
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_con1->echoJson($resp);
	}
	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$obBD_con1->echoJson($resp);
}


/**
 * Guardar segun tipo Formulario
 *
 */
if (isset($save)) {
	$obBD_con1->echoLog('** PHP SAVE **');
	$resp = array('success' => false);
	$obBD_ins1 =  new Class_Log_Datos_Lab_2;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	//$obBD_ins1->debug(true);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try {
		$data = $_POST;
		$obBD_con1->echoLog($data);
		if (isset($saveUnidad)) {

			$obBD_ins1->operacionobBD('tipo_pago_labor.insert', array('Tpg_Cod' => $data['Tpg_Cod'], 'Tpg_Des' => $data['Tpg_Des'], 'Suc_Cod' => $Ses_Suc_Cod), $obBD_conexionIns);
			$Tpg_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
			//$obBD_con1->echoLog($Tpg_Cod);
		}
		if (isset($saveLabor)) {

			$obBD_ins1->operacionobBD('labores.insert', array('Lab_Cod' => $data['Lab_Cod'], 'Lab_Des' => $data['Lab_Des'], 'Lab_Val' => $data['Lab_Val'], 'Tpg_Cod' => $data['Tpg_Cod']), $obBD_conexionIns);
			$Lab_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
			$obBD_con1->echoLog($Lab_Cod);
		}
		if (isset($saveFinca)) {

			$obBD_ins1->operacionobBD('finca_actividad.insert', array('Fnc_Cod' => $data['Fnc_Cod'], 'Fnc_Des' => $data['Fnc_Des'], 'Fnc_Hec' => $data['Fnc_Hec'], 'Fnc_Dir' => $data['Fnc_Dir'], 'Suc_Cod' => $Ses_Suc_Cod), $obBD_conexionIns);
			$Fnc_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
			//$obBD_con1->echoLog($Fnc_Cod);
		}
		if (isset($saveActividad)) {
			//$obBD_con1->echoLog('**-- PHP GUARDAR ACTIVIDAD RETENCION --**');
			$obBD_ins1->operacionobBD('actividad_labor.insert', array('Pec_Cod' => $data['Pec_Cod'], 'Act_Fec' => $data['Act_Fec'], 'Act_Sem' => $data['Act_Sem'], 'Act_Res' => $data['Act_Res'], 'Fnc_Cod' => $data['Fnc_Cod_D'], 'Usu_Cod' => $Ses_Usu_Cod), $obBD_conexionIns);
			$Act_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
			//$obBD_con1->echoLog($Act_Cod);
			foreach ($actividades as $act) {
				$obBD_ins1->operacionobBD('det_actividad_labor.insert', array('Det_Val' => $act['Lab_Val'], 'Det_Can' => $act['Det_Can'], 'Det_Obs' => $act['Det_Obs'], 'Det_Fec' => $act['Det_Fec'], 'Act_Cod' => $Act_Cod, 'Lab_Cod' => $act['Lab_Cod'], 'Per_Cod' => $act['Per_Cod']), $obBD_conexionIns);
			}
		}
		if (isset($saveModActividad)) {
			//$obBD_con1->echoLog('**-- PHP GUARDAR MODIFICACION RETENCION --**');
			//Actualizar cabecera con los nuevos datos
			$obBD_ins1->operacionobBD('actividad_labor.update', array('Act_Cod' => $data['Act_Cod'], 'Pec_Cod' => $data['Pec_Cod'], 'Act_Fec' => $data['Act_Fec_Mod'], 'Act_Sem' => $data['Act_Sem'], 'Act_Res' => $data['Act_Res'], 'Fnc_Cod' => $data['Fnc_Cod'], 'Usu_Cod' => $Ses_Usu_Cod), $obBD_conexionIns);
			//busco el  detalle existente de esa cabecera
			$Act_Cod = $data['Act_Cod'];
			//$obBD_con1->echoLog($Act_Cod);
			$detTrabajadoresActuales = $obBD_con1->getArrayConsulta('det_actividad_labor.selectWhere', array('det_actividad_labor.Act_Cod' => $data['Act_Cod']), $obBD_conexionIns);
			//Borrar previamente el detalle existente de esa cabecera
			$obBD_ins1->operacionobBD('det_actividad_labor.deleteWhere', array('Act_Cod' => $Act_Cod), $obBD_conexionIns);

			//Insertar nuevamenta
			$obBD_ins1->echoLog(count($actividades));
			foreach ($actividades as $act) {
				if ($act['Lab_Cod'] !== 0 && $act['Per_Cod'] !== 0) {
					$obBD_ins1->operacionobBD('det_actividad_labor.insert', array('Det_Val' => $act['Lab_Val'], 'Det_Can' => $act['Det_Can_Mod'], 'Det_Obs' => $act['Det_Obs'], 'Det_Fec' => $act['Det_Fec_Mod'], 'Act_Cod' => $Act_Cod, 'Lab_Cod' => $act['Lab_Cod'], 'Per_Cod' => $act['Per_Cod']), $obBD_conexionIns);
				}
			}
		}
	} catch (Exception $e) {
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_con1->echoJson($resp);
	}
	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$resp['tipoPago'] = $obBD_con1->getRowConsulta('tipo_pago_labor.selectWhere', array('tipo_pago_labor.Tpg_Cod' => $Tpg_Cod, 'Suc_Cod' => $Ses_Suc_Cod, 'setWhere' => array('isActive')), $obBD_conexion);
	$resp['finca'] = $obBD_con1->getRowConsulta('finca_actividad.selectWhere', array('finca_actividad.Fnc_Cod' => $Fnc_Cod, 'setWhere' => array('isActive')), $obBD_conexion);
	$obBD_con1->echoJson($resp);
}

/**
 * Busqueda de personal
 */
if (isset($personalAjax)) {
	$data = array_merge($_GET,  array('setWhere' => array('isActive'), 'order' => 'Prs_Nom asc'));
	$respuesta = $obBD_con1->getPageGridJson('personal.selectWhere', $data, $obBD_conexion);
}

/**
 * Empleados por area vigentes en el rango de la semana (registro actividades v2).
 */
if (isset($personalPorAreaAjax)) {
	$Are_CodReq = isset($Are_Cod) ? (int)$Are_Cod : 0;
	$Sem_Fei = isset($Sem_Fei) ? trim($Sem_Fei) : '';
	$Sem_Fef = isset($Sem_Fef) ? trim($Sem_Fef) : '';
	$okFecha = preg_match('/^\d{4}-\d{2}-\d{2}$/', $Sem_Fei) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $Sem_Fef);
	if ($Are_CodReq <= 0 || !$okFecha) {
		$obBD_con1->echoJson(array('success' => false, 'message' => 'Seleccione &aacute;rea, per&iacute;odo y semana v&aacute;lidos.', 'empleados' => array()));
	}
	$rows = $obBD_con1->getArrayConsulta(7, array('Are_Cod' => $Are_CodReq, 'Sem_Fei' => $Sem_Fei, 'Sem_Fef' => $Sem_Fef), $obBD_conexion);
	$obBD_con1->echoJson(array('success' => true, 'empleados' => $rows));
}

/**
 * Busqueda Grid Labores
 */
if (isset($laboresAjax)) {
	$data = array_merge($_GET,  array('setWhere' => array('isActive', 'orderByDes', 'byFormaPago', 'setEmpCod')));
	$respuesta = $obBD_con1->getPageGridJson('labores.selectWhere', $data, $obBD_conexion  );
}

/**
 * Busqueda de validacion con Sector y Semana
 */
if (isset($verificaFincaSemana)) {
	$resultExiste = array(
		'success' => true,
		'fincaSemana' => $obBD_con1->getArrayConsulta('actividad_labor.selectWhere', array(
			'actividad_labor.Act_Sem' => $Act_Sem,
			'actividad_labor.Fnc_Cod' => $Fnc_Cod,
			'actividad_labor.Pec_Cod' => $Pec_Cod,
			'setWhere' => array('isActive', 'byFinca')
		),  $obBD_conexion),
	);
	$obBD_con1->echoJson($resultExiste);
}

/**
 * Busca semanas ocupadas
 */
if (isset($semanasOcupadasAjax)) {
	$pecO = isset($Pec_Cod) ? (int)$Pec_Cod : 0;
	$fncO = isset($Fnc_Cod) ? (int)$Fnc_Cod : 0;
	if ($pecO <= 0 || $fncO <= 0) {
		$obBD_con1->echoJson(array('success' => true, 'ocupadas' => array()));
	}
	$rowsO = $obBD_con1->getArrayConsulta(8, array('Pec_Cod' => $pecO, 'Fnc_Cod' => $fncO), $obBD_conexion);
	$ocupadas = array();
	foreach ($rowsO as $ro) {
		if (isset($ro['Act_Sem'])) {
			$ocupadas[] = (int)$ro['Act_Sem'];
		}
	}
	$obBD_con1->echoJson(array('success' => true, 'ocupadas' => $ocupadas));
}

if (isset($verificaDesc)) {

	$resultPagosDesc = array(
		'success' => true,
		'tipPagoDesc' => $obBD_con1->getArrayConsulta('tipo_pago_labor.selectWhere', array('tipo_pago_labor.Tpg_Des' => $Tpg_Des, 'setWhere' => array('isActive')), $obBD_conexion),
	);
	$obBD_con1->echoJson($resultPagosDesc);
}

/**
 * Buscar pagos de Labores
 */
if (isset($buscarLaborPago)) {

	$resultPagos = array(
		'success' => true,
		'tipPago' => $obBD_con1->getArrayConsulta('tipo_pago_labor.selectWhere', array('setWhere' => array('isActive')), $obBD_conexion),
	);
	$obBD_con1->echoJson($resultPagos);
}

/**
 * Buscar Labores existentes
 */
if (isset($laborAjax)) {

	$laboresList = array(
		'success' => true,
		'listLab' => $obBD_con1->getArrayConsulta('labores.selectWhere', array('setWhere' => array('isActive', 'orderByDes', 'byFormaPago' ,'setEmpCod')), $obBD_conexion),
	);
	$obBD_con1->echoJson($laboresList);
}

/**
 * Autocompletar labores (LIKE en Lab_Des) para tablas nativas v2; respuesta ligera.
 */
if (isset($laborSuggestAjax)) {
	$q = isset($_REQUEST['q']) ? trim((string) $_REQUEST['q']) : '';
	if ($q === '') {
		$dataTop = array(
			'setWhere' => array('isActive', 'orderByDes', 'byFormaPago', 'setEmpCod'),
			'limits' => ' LIMIT 41',
		);
		$listTop = $obBD_con1->getArrayConsulta('labores.selectWhere', $dataTop, $obBD_conexion);
		if (!is_array($listTop)) {
			$listTop = array();
		}
		if (count($listTop) > 40) {
			$listTop = array_slice($listTop, 0, 40);
		}
		$obBD_con1->echoJson(array('success' => true, 'listLab' => $listTop));
	} else {
		if (function_exists('mb_strlen') && mb_strlen($q, 'UTF-8') > 80) {
			$q = mb_substr($q, 0, 80, 'UTF-8');
		} elseif (strlen($q) > 80) {
			$q = substr($q, 0, 80);
		}
		$dataSuggest = array(
			'setWhere' => array('isActive', 'orderByDes', 'byFormaPago', 'setEmpCod'),
			'op_opciones' => 'd',
			'search' => $q,
			/* Requisito del modelo: LIKE solo en rama grid (_selectBasicGrid). */
			'isGrid' => true,
			/* Sin limits, selectWhere invoca setCount() y la SQL deja de devolver filas. */
			'limits' => ' LIMIT 41',
		);
		$listS = $obBD_con1->getArrayConsulta('labores.selectWhere', $dataSuggest, $obBD_conexion);
		if (!is_array($listS)) {
			$listS = array();
		}
		if (count($listS) > 40) {
			$listS = array_slice($listS, 0, 40);
		}
		$obBD_con1->echoJson(array('success' => true, 'listLab' => $listS));
	}
}

/**
 * Busqueda de Sectores
 */
if (isset($fincasAjax)) {

	$fincasList = array(
		'success' => true,
		'listaFincas' => $obBD_con1->getArrayConsulta('finca_actividad.selectWhere', array('setWhere' => array('setSucCod', 'orderByDes', 'isActive')), $obBD_conexion),
	);
	$obBD_con1->echoJson($fincasList);
}

/**
 * Busqueda de Trabajadores
 */
if (isset($trabajadoresAjax)) {

	$trbjList = array(
		'success' => true,
		'listTrabajadores' => $obBD_con1->getArrayConsulta('personal.selectWhere', array('setWhere' => array('setEmpCod', 'isActive')), $obBD_conexion),
	);
	$obBD_con1->echoJson($trbjList);
}

/**
 * Busqueda actividades searchAllActiv
 */
if (isset($searchAllActiv)) {

	$datos = array_merge($_GET, array('setWhere' => array('byFinca', 'addSemana', 'byUsuarios', 'byPersonaUsu', 'byDetAct', 'byPersonal', 'byTrabajador', 'byPeriodo')));
	$whereExtra = array();
	$pecI = isset($_GET['Pec_Cod']) ? (int)$_GET['Pec_Cod'] : 0;
	$fncI = isset($_GET['Fnc_Cod']) ? (int)$_GET['Fnc_Cod'] : 0;
	$semI = isset($_GET['Act_Sem']) ? (int)$_GET['Act_Sem'] : 0;
	if ($pecI > 0) {
		$whereExtra['actividad_labor.Pec_Cod'] = $pecI;
	}
	if ($fncI > 0) {
		$whereExtra['actividad_labor.Fnc_Cod'] = $fncI;
	}
	if ($semI > 0) {
		$whereExtra['actividad_labor.Act_Sem'] = $semI;
	}
	if (!empty($whereExtra)) {
		if (!empty($datos['where']) && is_array($datos['where'])) {
			$datos['where'] = array_merge($datos['where'], $whereExtra);
		} else {
			$datos['where'] = $whereExtra;
		}
	}
	$resultado = $obBD_con1->getPageGrid('actividad_labor.selectWhere', $datos, $obBD_conexion);
	$obBD_con1->echoJson($resultado);
}

/**
 * Busqueda detalle activiades
 */
if (isset($searchDetActivi)) {

	$actCodReq = isset($Act_Cod) ? $Act_Cod : (isset($_REQUEST['Act_Cod']) ? $_REQUEST['Act_Cod'] : null);
	if ($actCodReq === null || $actCodReq === '') {
		$obBD_con1->echoJson(array('success' => false, 'message' => 'Falta Act_Cod.', 'detalleAct' => array()));
	}
	$condDet = array(
		'det_actividad_labor.Act_Cod' => $actCodReq,
		'setWhere' => array('byLabor', 'byTipoPagoLabor', 'byPersonal', 'byTrabajador'),
	);
	$perF = isset($Per_Cod) ? (int)$Per_Cod : (isset($_REQUEST['Per_Cod']) ? (int)$_REQUEST['Per_Cod'] : 0);
	if ($perF > 0) {
		$condDet['det_actividad_labor.Per_Cod'] = $perF;
	}
	$detActividad = array(
		'success' => true,
		'detalleAct' => $obBD_con1->getArrayConsulta('det_actividad_labor.selectWhere', $condDet, $obBD_conexion),
	);
	$obBD_con1->echoJson($detActividad);
}

/**
 * Modificar v2: actividades donde figura un empleado en el periodo (sector + semana desconocidas).
 */
if (isset($modificarActividadesPorEmpleado)) {
	$pecE = isset($_REQUEST['Pec_Cod']) ? (int)$_REQUEST['Pec_Cod'] : 0;
	$perE = isset($_REQUEST['Per_Cod']) ? (int)$_REQUEST['Per_Cod'] : 0;
	if ($pecE <= 0 || $perE <= 0) {
		$obBD_con1->echoJson(array(
			'success' => false,
			'message' => 'Seleccione per&iacute;odo y empleado.',
			'matches' => array(),
		));
	} else {
		$listaM = $obBD_con1->getArrayConsulta(10, array('Pec_Cod' => $pecE, 'Per_Cod' => $perE), $obBD_conexion);
		$obBD_con1->echoJson(array(
			'success' => true,
			'matches' => is_array($listaM) ? $listaM : array(),
		));
	}
}

/**
 * Modificar v2: cabecera + empleados (totales) por periodo, sector y semana.
 */
if (isset($modificarCargarActividadSemana)) {
	$pecM = isset($_REQUEST['Pec_Cod']) ? (int)$_REQUEST['Pec_Cod'] : 0;
	$fncM = isset($_REQUEST['Fnc_Cod']) ? (int)$_REQUEST['Fnc_Cod'] : 0;
	$semM = isset($_REQUEST['Act_Sem']) ? (int)$_REQUEST['Act_Sem'] : 0;
	if ($pecM <= 0 || $fncM <= 0 || $semM <= 0) {
		$obBD_con1->echoJson(array('success' => false, 'message' => 'Seleccione per&iacute;odo, sector y semana.'));
	}
	$cabM = $obBD_con1->getArrayConsulta('actividad_labor.selectWhere', array(
		'actividad_labor.Pec_Cod' => $pecM,
		'actividad_labor.Fnc_Cod' => $fncM,
		'actividad_labor.Act_Sem' => $semM,
		'setWhere' => array('isActive', 'byFinca'),
	), $obBD_conexion);
	if (empty($cabM)) {
		$obBD_con1->echoJson(array('success' => false, 'message' => 'No hay actividad registrada para esa combinaci&oacute;n.'));
	}
	$actM = $cabM[0];
	$actCodM = isset($actM['Act_Cod']) ? (int)$actM['Act_Cod'] : 0;
	$detsM = $obBD_con1->getArrayConsulta('det_actividad_labor.selectWhere', array(
		'det_actividad_labor.Act_Cod' => $actCodM,
		'setWhere' => array('byLabor', 'byTipoPagoLabor', 'byPersonal', 'byTrabajador'),
	), $obBD_conexion);
	$byPerM = array();
	foreach ($detsM as $dm) {
		$pcM = isset($dm['Per_Cod']) ? (string)$dm['Per_Cod'] : '';
		if ($pcM === '') {
			continue;
		}
		if (!isset($byPerM[$pcM])) {
			$byPerM[$pcM] = array(
				'Per_Cod' => $pcM,
				'personal' => isset($dm['personal']) ? $dm['personal'] : '',
				'total' => 0.0,
			);
		}
		$dv = isset($dm['Det_Val']) ? (float)$dm['Det_Val'] : 0.0;
		$dc = isset($dm['Det_Can']) ? (float)$dm['Det_Can'] : 0.0;
		$byPerM[$pcM]['total'] += $dv * $dc;
	}
	$empsM = array();
	foreach ($byPerM as $rwM) {
		$rwM['total'] = number_format($rwM['total'], 2, '.', '');
		$empsM[] = $rwM;
	}
	usort($empsM, function ($a, $b) {
		return strcmp(isset($a['personal']) ? $a['personal'] : '', isset($b['personal']) ? $b['personal'] : '');
	});
	$obBD_con1->echoJson(array(
		'success' => true,
		'actividad' => $actM,
		'empleados' => $empsM,
	));
}

/**
 * Busqueda de todos los detalles
 */
if (isset($searchAllDetail)) {
	$allDetail = array(
		'success' => true,
		'allDetail' => $obBD_con1->getArrayConsulta('det_actividad_labor.selectWhere', array('setWhere' => array('byLabor', 'byTipoPagoLabor', 'byPersonal', 'byTrabajador')), $obBD_conexion),
	);
	$obBD_con1->echoJson($allDetail);
}

$tipoPagos = $obBD_con1->getArrayConsulta('tipo_pago_labor.selectWhere', array('setWhere' => array('isActive')), $obBD_conexion);
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
$areas_rrhh = $obBD_con1->getArrayConsulta(4, isset($Ses_Emp_Cod) ? $Ses_Emp_Cod : $_SESSION['Ses_Emp_Cod'], $obBD_conexion);
$listaFincas = $obBD_con1->getArrayConsulta('finca_actividad.selectWhere', array('setWhere' => array('setSucCod', 'orderByDes', 'isActive')), $obBD_conexion);
if (!is_array($listaFincas)) {
	$listaFincas = array();
}
?>

<!DOCTYPE html>
<HTML>

<HEAD>
	<TITLE>Gestionar Actividades Relavera</TITLE>
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
	<link rel="stylesheet" href="../RECURSOS/relavera_ui.css?v=9" />
</HEAD>

<BODY>
	<div class="panel panel-main v2-labores-panel">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; Gestionar Actividades Relavera</h3>
		</div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div class="row">
				<div class="col-sm-12 v2-labores-page-host">
					<div id="tabsLabores" class="ui-tab-fix">
						<ul>
							<li>
								<a href="#tabs-2">Registrar Actividades</a>
							</li>
							<li>
								<a href="#tabs-3">Modificar Actividades</a>
							</li>
						</ul>

						<div class="panels-area form-horizontal normal ">
							<div id="tabs-2">
								<div id="tab2" class="row">
									<div class="col-md-12 col-sm-12">
										<form id="frm_alt_actividad" name="frm_alt_actividad" class="form-horizontal normal" action="javascript:saveData('frm_alt_actividad','saveActividad','actividad') ">
											<fieldset class="exa-fieldset v2-datos-actividad">
												<legend class="Titulos2">Datos</legend>
												<!-- Fila 1: Periodo + Fecha -->
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required">Per&iacute;odo:</label>
													<div class="col-xs-2">
														<select id="Pec_Cod" name="Pec_Cod" onchange="" class="form-control input-xs">
															<?php
															foreach ($periodos as $p) {
																echo "<option data--year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data--pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
															}
															?>
														</select>
													</div>
													<label class="col-xs-2 control-label label-xs required v2-label-fecha-pega">Fecha:</label>
													<div class="col-xs-2 v2-col-fecha">
														<input type="text" id="Act_Fec" name="Act_Fec" class="form-control input-xs datepickers" style="justify-items: center;" required></input>
													</div>
												</div>
												<!-- Fila 2: Sector (con Mapeo Interactivo Georreferenciado) -->
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required">Sector:</label>
													<div class="col-xs-6 col-sector-v2">
														<div class="input-group input-group-xs">
															<select id="Fnc_Cod_D" name="Fnc_Cod_D" onchange="v2OnFincaRegistroActividadChanged();" class="form-control input-xs select_finca" data-v2-fincas-preload="1">
																<option value="0" required="true">Seleccione...</option>
																<?php foreach ($listaFincas as $fincaRow) { ?>
																	<option value="<?php echo (int)$fincaRow['Fnc_Cod']; ?>" data-lat="<?php echo isset($fincaRow['Fnc_Lat']) ? $fincaRow['Fnc_Lat'] : ''; ?>" data-lng="<?php echo isset($fincaRow['Fnc_Lng']) ? $fincaRow['Fnc_Lng'] : ''; ?>"><?php echo htmlspecialchars($fincaRow['Fnc_Des'], ENT_QUOTES, 'UTF-8'); ?></option>
																<?php } ?>
															</select>
															<span class="input-group-addon validate">
																<i></i>
															</span>
															<span class="input-group-btn">
																<button type="button" class="btn btn-default btn-xs" onclick="recargarSelectUbicaciones()" title="Actualizar lista de sectores">
																	<span class="glyphicon glyphicon-refresh text-primary"></span>
																</button>
																<button type="button" class="btn btn-success btn-xs" onclick="abrirMapeoParaNuevaLocacion('actividad')" title="Agregar o georreferenciar sector en Mapeo Interactivo">
																	<span class="glyphicon glyphicon-map-marker"></span>
																</button>
															</span>
														</div>
													</div>
													<div class="col-xs-3"></div>
												</div>
												<!-- Fila 3: Semana -->
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required">Semana:</label>
													<div class="col-xs-3">
														<div class="input-group input-group-xs">
															<select id="Act_Sem" name="Act_Sem" onchange="verificaExistente()" class="form-control input-xs select_semna">
																<option value="0" required="true">Seleccione...</option>
															</select>
															<span class="input-group-addon validate">
																<i></i>
															</span>
														</div>
													</div>
													<div class="col-xs-6"></div>
												</div>
												<!-- Fila 4: Area -->
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs">&Aacute;rea (listado empleados):</label>
													<div class="col-xs-9">
														<div class="input-group input-group-xs">
															<select id="Are_Cod_Lab" name="Are_Cod_Lab" class="form-control input-xs">
																<option value="0">Seleccione &aacute;rea...</option>
																<?php foreach ($areas_rrhh as $arow) { ?>
																	<option value="<?php echo $arow['Are_Cod']; ?>"><?php echo $arow['Are_Des']; ?></option>
																<?php } ?>
															</select>
															<span class="input-group-btn">
																<button type="button" id="btn_emp_por_area" class="btn btn-info btn-xs" title="Agrega a la grilla todos los empleados con contrato vigente en la semana elegida y cargo en esta &aacute;rea">
																	Listar empleados
																</button>
															</span>
															<span class="input-group-btn">
																<button type="button" class="btn btn-default btn-xs v2-btn-ayuda-area" tabindex="0" title="Use el per&iacute;odo, el sector y la semana indicados arriba; el rango se calcula desde el inicio del per&iacute;odo (7 d&iacute;as por n&uacute;mero de semana). No duplica quien ya est&eacute; en la lista." aria-label="Ayuda: listado de empleados por &aacute;rea">
																	<span class="glyphicon glyphicon-info-sign text-info"></span>
																</button>
															</span>
														</div>
													</div>
												</div>
												<!-- Fila 5: Responsable -->
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required ">Responsable (Mayordomo):</label>
													<div class="col-xs-9">
														<div class="input-group input-group-sm">
															<span id="prefijo" class="input-group-addon bold"></span>
															<input id="Act_Res" name="Act_Res" class="form-control span datatitle" type="text" required="true">
														</div>
													</div>
												</div>
											</fieldset>
											<div id="regActividadV2" class="row v2-reg-actividad-row" style="margin-top:6px;">
												<div class="col-md-4 col-sm-12 v2-reg-col">
													<fieldset class="exa-fieldset v2-reg-fieldset">
														<div id="tableEmpRegAct" class="v2-emp-wrap">
															<div class="v2-emp-caption ui-widget-header">EMPLEADOS EN ESTA ACTIVIDAD</div>
															<div class="v2-emp-scroll">
																<table class="table table-bordered table-condensed v2-emp-table">
																	<thead>
																		<tr class="ui-widget-header">
																			<th style="width:28px;" class="text-center">#</th>
																			<th>Trabajador</th>
																			<th style="width:94px;" class="text-right">Tot. Labores</th>
																			<th style="width:38px;" class="text-center"><span class="glyphicon glyphicon-pencil" title="Modificar"></span></th>
																			<th style="width:38px;" class="text-center"><span class="glyphicon glyphicon-trash" title="Eliminar"></span></th>
																		</tr>
																	</thead>
																	<tbody id="v2EmpTbody"></tbody>
																</table>
															</div>
															<div class="v2-emp-sum-bar ui-widget-content">
																<span>TOTAL</span>
																<span class="text-right v2-emp-grand-total" id="v2EmpGrandTotal">0.00</span>
															</div>
															<div class="v2-emp-footer ui-widget-content">
																<button type="button" id="btn_agr_emp" class="btn btn-success btn-xs" title="Agregar empleado">
																	<span class="glyphicon glyphicon-user"></span> Agregar empleado
																</button>
																<span id="v2EmpStatus" class="text-muted small">Sin empleados</span>
															</div>
														</div>
													</fieldset>
												</div>
												<div class="col-md-8 col-sm-12 v2-reg-col">
													<fieldset class="exa-fieldset v2-reg-fieldset">
														<div id="tableActividad" class="v2-lab-wrap">
															<div class="v2-lab-caption ui-widget-header">LABORES DEL EMPLEADO SELECCIONADO</div>
															<div class="v2-lab-scroll">
																<table class="table table-bordered table-condensed v2-lab-table">
																	<thead>
																		<tr class="ui-widget-header">
																			<th style="width:34px;" class="text-center">#</th>
																			<th style="width:24%;"><span class="required"></span> Labor</th>
																			<th style="width:8%;">Unidad</th>
																			<th style="width:12%;"><span class="required"></span> Fecha</th>
																			<th style="width:20%;"><span class="required"></span> Observaci&oacute;n</th>
																			<th style="width:10%;">P. Unitario</th>
																			<th style="width:9%;"><span class="required"></span> Cantidad</th>
																			<th style="width:8%;" class="text-right">Total</th>
																			<th style="width:38px;" class="text-center"><span class="glyphicon glyphicon-trash" title="Eliminar"></span></th>
																		</tr>
																	</thead>
																	<tbody id="v2LabTbody"></tbody>
																</table>
															</div>
															<div class="v2-lab-sum-bar ui-widget-content">
																<div class="v2-lab-sum-inner">
																	<strong>Total:</strong>
																	<span class="v2-lab-foot-sum">0.00</span>
																</div>
															</div>
															<div class="v2-lab-footer ui-widget-content">
																<button type="button" id="btn_agr" class="btn btn-success btn-xs" title="Agregar l&iacute;nea de labor">
																	<span class="glyphicon glyphicon-plus"></span> Agregar labor
																</button>
																<span id="v2LabStatus" class="text-muted small">Sin labores</span>
															</div>
														</div>
													</fieldset>
												</div>
											</div>
											<div style="text-align: center;padding-top: 5px;">
												<button type="button" id="btn_gua_act" name="btn_gua_act" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();">
													<span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
												<button type="button" id="btn_can_act_reg" class="btn btn-danger btn-sm" style="margin-left:8px;" title="Descartar cambios en pantalla y recargar el registro de actividades">
													<span class="glyphicon glyphicon-ban-circle"></span> Cancelar</button>
											</div>
										</form>
									</div>
								</div>
							</div>
							<div id="tabs-3">
								<div id="tab3" class="row">
									<div class="col-md-12 col-sm-12 v2-labores-page-host">
										<form id="frm_mod_actividad" name="frm_mod_actividad" class="form-horizontal normal" action="javascript:void(0);">
											<fieldset class="exa-fieldset v2-datos-actividad v2-mod-busq-act">
												<legend class="Titulos2">Consulta de actividades</legend>
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required">Per&iacute;odo:</label>
													<div class="col-xs-9">
														<select id="modq_Pec_Cod" class="form-control input-xs">
															<?php
															foreach ($periodos as $p) {
																echo "<option data--year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data--pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
															}
															?>
														</select>
													</div>
												</div>
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs">Buscar por empleado:</label>
													<div class="col-xs-9">
														<input type="hidden" id="modq_lookup_Per_Cod" value="" />
														<div class="input-group input-group-sm">
															<input type="text" id="modq_lookup_emp_nombre" class="form-control input-xs" readonly="readonly" placeholder="Ninguno seleccionado" />
															<span class="input-group-btn">
																<button type="button" id="btn_mod_elegir_empleado_busq" class="btn btn-default btn-xs" title="Buscar persona en el listado del sistema">
																	Elegir empleado
																</button>
																<button type="button" id="btn_mod_buscar_por_empleado" class="btn btn-info btn-xs" title="Localizar actividades de este empleado en el per&iacute;odo (rellena sector y semana)">
																	<span class="glyphicon glyphicon-user"></span> Buscar actividades
																</button>
															</span>
														</div>
													</div>
												</div>
												<div id="modq_match_panel" class="form-group" style="display:none;">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs">Varias coincidencias:</label>
													<div class="col-xs-9">
														<div class="v2-modq-match-row input-group-sm">
															<select id="modq_actividad_match" class="form-control input-xs v2-modq-match-select"></select>
															<span class="input-group-btn">
																<button type="button" id="btn_mod_cargar_coincidencia" class="btn btn-primary btn-xs" title="Aplicar sector y semana de la opci&oacute;n elegida">
																	<span class="glyphicon glyphicon-ok"></span> Cargar Datos
																</button>
															</span>
															<span class="v2-modq-match-tail" aria-hidden="true"></span>
														</div>
													</div>
												</div>
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required">Sector:</label>
													<div class="col-xs-6 col-sector-v2">
														<div class="input-group input-group-xs">
															<select id="modq_Fnc_Cod" class="form-control input-xs select_finca" data-v2-fincas-preload="1">
																<option value="0">Seleccione...</option>
																<?php foreach ($listaFincas as $fincaRow) { ?>
																	<option value="<?php echo (int)$fincaRow['Fnc_Cod']; ?>" data-lat="<?php echo isset($fincaRow['Fnc_Lat']) ? $fincaRow['Fnc_Lat'] : ''; ?>" data-lng="<?php echo isset($fincaRow['Fnc_Lng']) ? $fincaRow['Fnc_Lng'] : ''; ?>"><?php echo htmlspecialchars($fincaRow['Fnc_Des'], ENT_QUOTES, 'UTF-8'); ?></option>
																<?php } ?>
															</select>
															<span class="input-group-btn">
																<button type="button" class="btn btn-default btn-xs" onclick="recargarSelectUbicaciones()" title="Actualizar lista de sectores">
																	<span class="glyphicon glyphicon-refresh text-primary"></span>
																</button>
																<button type="button" class="btn btn-success btn-xs" onclick="abrirMapeoParaNuevaLocacion('actividad_mod')" title="Ver / Georreferenciar sector en Mapeo Interactivo">
																	<span class="glyphicon glyphicon-map-marker"></span>
																</button>
															</span>
														</div>
													</div>
													<div class="col-xs-3"></div>
												</div>
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required">Semana:</label>
													<div class="col-xs-3">
														<select id="modq_Act_Sem" class="form-control input-xs">
															<option value="0">Seleccione...</option>
														</select>
													</div>
													<div class="col-xs-5">
														<button type="button" id="btn_mod_buscar_act" class="btn btn-success btn-sm" title="Cargar empleados y permitir ver labores por trabajador">
															<span class="glyphicon glyphicon-search"></span> Buscar
														</button>
													</div>
												</div>
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required">Responsable (Mayordomo):</label>
													<div class="col-xs-9">
														<div class="input-group input-group-sm">
															<span id="modq_prefijo" class="input-group-addon bold"></span>
															<input id="modq_Act_Res" name="modq_Act_Res" type="text" class="form-control input-xs datatitle" autocomplete="off" placeholder="Mayordomo de la actividad" />
														</div>
													</div>
												</div>
											</fieldset>
										</form>
										<div id="modRegActividadV2" class="row v2-reg-actividad-row" style="margin-top:8px;">
											<div class="col-md-4 col-sm-12 v2-reg-col">
												<fieldset class="exa-fieldset v2-reg-fieldset" style="border:none;padding:0;">
													<div id="modTableEmpRegAct" class="v2-emp-wrap">
														<div class="v2-emp-caption ui-widget-header">EMPLEADOS EN ESTA ACTIVIDAD</div>
														<div class="v2-emp-scroll">
															<table class="table table-bordered table-condensed v2-mod-emp-table">
																<thead>
																	<tr class="ui-widget-header">
																		<th style="width:28px;" class="text-center">#</th>
																		<th>Trabajador</th>
																		<th style="width:94px;" class="text-right">Tot. Labores</th>
																		<th style="width:38px;" class="text-center"><span class="glyphicon glyphicon-pencil" title="Modificar"></span></th>
																		<th style="width:38px;" class="text-center"><span class="glyphicon glyphicon-trash" title="Eliminar"></span></th>
																	</tr>
																</thead>
																<tbody id="modV2EmpTbody"></tbody>
															</table>
														</div>
														<div class="v2-emp-sum-bar ui-widget-content">
															<span>TOTAL</span>
															<span class="text-right" id="modV2EmpGrandTotal">0.00</span>
														</div>
														<div class="v2-emp-footer ui-widget-content">
															<div class="v2-mod-emp-footer-actions">
																<button type="button" id="btn_mod_agr_emp" class="btn btn-success btn-xs" title="Agregar un empleado desde el buscador">
																	<span class="glyphicon glyphicon-user"></span> Agregar empleado
																</button>
																<button type="button" id="btn_mod_emp_por_area" class="btn btn-info btn-xs" title="Abre un cuadro para elegir el &aacute;rea y agregar empleados con contrato vigente en la semana de la actividad">
																	<span class="glyphicon glyphicon-list"></span> Listar empleados
																</button>
															</div>
															<span id="modV2EmpStatus" class="text-muted small">Sin empleados</span>
														</div>
													</div>
												</fieldset>
											</div>
											<div class="col-md-8 col-sm-12 v2-reg-col">
												<fieldset class="exa-fieldset v2-reg-fieldset" style="border:none;padding:0;">
													<div id="modTableActividad" class="v2-lab-wrap">
														<div class="v2-lab-caption ui-widget-header">LABORES DEL EMPLEADO SELECCIONADO</div>
														<div class="v2-lab-scroll">
															<table class="table table-bordered table-condensed v2-mod-lab-table">
																<thead>
																	<tr class="ui-widget-header">
																		<th style="width:34px;" class="text-center">#</th>
																		<th style="width:24%;"><span class="required"></span> Labor</th>
																		<th style="width:8%;">Unidad</th>
																		<th style="width:12%;"><span class="required"></span> Fecha</th>
																		<th style="width:20%;"><span class="required"></span> Observaci&oacute;n</th>
																		<th style="width:10%;">P. Unitario</th>
																		<th style="width:9%;"><span class="required"></span> Cantidad</th>
																		<th style="width:8%;" class="text-right">Total</th>
																		<th style="width:38px;" class="text-center"><span class="glyphicon glyphicon-trash" title="Eliminar"></span></th>
																	</tr>
																</thead>
																<tbody id="modV2LabTbody"></tbody>
															</table>
														</div>
														<div class="v2-lab-sum-bar ui-widget-content">
															<div class="v2-lab-sum-inner">
																<strong>Total:</strong>
																<span class="v2-mod-lab-foot-sum">0.00</span>
															</div>
														</div>
														<div class="v2-lab-footer ui-widget-content">
															<div class="v2-mod-lab-footer-actions">
																<button type="button" id="btn_mod_agr" class="btn btn-success btn-xs" title="Agregar l&iacute;nea de labor">
																	<span class="glyphicon glyphicon-plus"></span> Agregar labor
																</button>
																<button type="button" id="btn_mod_rst_lab" class="btn btn-warning btn-xs" title="Volver al detalle de este empleado tal como se carg&oacute; al pulsar Buscar (no afecta a otros trabajadores)">
																	<span class="glyphicon glyphicon-repeat"></span> Reestablecer labores
																</button>
															</div>
															<span id="modV2LabStatus" class="text-muted small">Sin labores</span>
														</div>
													</div>
												</fieldset>
											</div>
										</div>
										<div style="text-align: center;padding-top: 5px;">
											<button type="button" id="btn_mod_gua_act" class="btn btn-primary btn-sm" title="Guardar cambios de la actividad">
												<span class="glyphicon glyphicon-floppy-disk"></span> Guardar
											</button>
											<button type="button" id="btn_can_act_mod" class="btn btn-danger btn-sm" style="margin-left:8px;" title="Descartar cambios en pantalla y recargar la modificaci&oacute;n de actividades">
												<span class="glyphicon glyphicon-ban-circle"></span> Cancelar</button>
										</div>
										<div class="row v2-consultar-grid-host" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
											<table id="consultarGrid" name="consultarGrid"></table>
											<div id="cgPager"></div>
										</div>
									</div>
									<div id="dialogInfo" style="display:none;">
										<fieldset class="exa-fieldset">
											<legend class="Titulos2"> Datos Actividad:</legend>
											<div class="form-horizontal normal" style="padding: 0 4px;">
												<div class="form-group">
													<label class="col-xs-2 control-label label-xs">Cod.Int:</label>
													<div class="col-xs-2">
														<span name="Act_Cod" class="form-control input-xs"></span>
													</div>
												</div>
												<div class="form-group">
													<label class="col-xs-2 control-label label-xs">Responsable:</label>
													<div class="col-xs-7" style="text-align: center;">
														<span name="Act_Res" class="form-control input-xs"></span>
													</div>
												</div>
												<div class="form-group">
													<label class="col-xs-2 control-label label-xs">Fecha Creacion:</label>
													<div class="col-xs-5" style="text-align: center;">
														<span name="Act_Sys" class="form-control input-sm"></span>
													</div>
												</div>
											</div>
											<div class="form-group condensed">
												<div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;">
													<b>Usuario:</b>
													<span id="usuario" name="usuario" class="databind"></span>
												</div>
											</div>
										</fieldset>
									</div>
								</div>
								<!--DIV DE EDICION  -->
								<div class="row" id="divEdic" style="display:none;">
									<div class="col-md-10 col-sm-8 col-md-offset-1">
										<form id="frm_mod_act_edi" name="frm_mod_act_edi" class="form-horizontal normal" action="javascript:saveData('frm_mod_act_edi','saveModActividad','actividad');">
											<input id="Act_Cod" name="Act_Cod" type="text" class="hidden" />

											<fieldset class="exa-fieldset">
												<legend class="Titulos2">Datos Edici&oacute;n</legend>
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required">Perido:</label>
													<div class="col-xs-3">
														<select id="Pec_Cod" name="Pec_Cod" onchange="" class="form-control input-xs">
															<?php
															foreach ($periodos as $p) {
																echo "<option data--year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data--pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
															}
															?>
														</select>
													</div>
												</div>
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required">Semana:</label>
													<div class="col-xs-2">
														<select id="Act_Sem" name="Act_Sem" onchange="" class="form-control input-xs select_semna">
															<option value="0" required="true">Seleccione...</option>
														</select>
													</div>
													<label class="col-xs-2 control-label label-xs required">Fecha:</label>
													<div class="col-xs-2">
														<input type="text" id="Act_Fec_Mod" name="Act_Fec_Mod" class="form-control input-xs datepickers" style="text-align:center;"
															required></input>
													</div>

												</div>
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required">Sector:</label>
													<div class="col-xs-4">
														<select id="Fnc_Cod" name="Fnc_Cod" onchange="" class="form-control input-xs select_finca">
															<option value="0" required="true">Seleccione...</option>
														</select>
													</div>
												</div>
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required ">Responsable (Mayordomo):</label>
													<div class="col-sm-6">
														<div class="input-group input-group-sm">
															<span id="prefijo" class="input-group-addon bold"></span>
															<input id="Act_Res" name="Act_Res" class="form-control span datatitle" type="text" required="true">
														</div>
													</div>
												</div>
											</fieldset>
										</form>
										<div class="col-sm-12">
											<table id="tableActividadMod"></table>
											<div id="tableActividadModPager"></div>
										</div>
										<div class="separator"></div>
										<div id="btn_atras" class="col-sm-12" style="text-align: left;padding-top: 15px;">
											<button type="button" class="btn btn-sm btn-inverse" onclick="clearDocument();$('#divEdic').moveComp('#tab3').updateGridsSizes();">
												<i class="glyphicon glyphicon-arrow-left"></i> Atr&aacute;s</button>
											<button type="button" id="btn_guardado" name="btn_guardado" class="btn btn-primary btn-sm" onclick="$('#frm_mod_act_edi').formSubmit();"
												disabled="disabled">
												<span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
										</div>

									</div>
								</div>
							</div>


						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div id="personalDialog" title="B&uacute;squeda del Personal">
		<form id="frmPersonal">
			<input type="hidden" id="CodFormBus" name="CodFormBus">
		</form>
	</div>
	<div id="laboresDialog" title="B&uacute;squeda de Labores">
		<form id="frmLabores">
			<input type="hidden" id="CodFormBusLab" name="CodFormBusLab">
		</form>
	</div>
	<div id="modListarEmpAreaDialog" style="display:none;" title="Listar empleados por &aacute;rea">
		<form class="form-horizontal normal" onsubmit="return false;">
			<p class="text-muted small" style="margin:0 0 10px 0;">Se agregar&aacute;n a la actividad los trabajadores con contrato vigente en la <strong>semana</strong> de la actividad cargada.</p>
			<div class="form-group" style="margin-bottom:8px;">
				<label class="col-xs-3 control-label label-xs required" for="mod_listar_emp_Are_Cod">&Aacute;rea:</label>
				<div class="col-xs-9">
					<select id="mod_listar_emp_Are_Cod" class="form-control input-xs">
						<option value="0">Seleccione &aacute;rea...</option>
						<?php foreach ($areas_rrhh as $arow) { ?>
							<option value="<?php echo $arow['Are_Cod']; ?>"><?php echo $arow['Are_Des']; ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="text-right" style="padding-top:8px;border-top:1px solid #e0e0e0;">
				<button type="button" id="btn_mod_listar_emp_cancel" class="btn btn-default btn-sm">Cancelar</button>
				<button type="button" id="btn_mod_listar_emp_ok" class="btn btn-info btn-sm" style="margin-left:6px;">
					<span class="glyphicon glyphicon-list"></span> Listar
				</button>
			</div>
		</form>
	</div>

	<!-- Modal Popup para Visualizar / Vectorizar en Mapa Satelital HD -->
	<div id="dialogMapeoSector" title="Mapeo Interactivo Georreferenciado - Relavera El Tabl&oacute;n" style="display:none; padding:0; overflow:hidden;">
		<div style="background:#0f172a; color:#fff; padding:6px 12px; font-size:12px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #334155;">
			<span id="dialogMapeoIndicacion">
				<i class="glyphicon glyphicon-info-sign text-info"></i> Seleccione un punto o sector en el mapa para vincularlo a la labor.
			</span>
			<div class="btn-group btn-group-xs">
				<button type="button" class="btn btn-default btn-xs" onclick="recargarIframeMapeo()" title="Recargar mapa">
					<span class="glyphicon glyphicon-refresh"></span>
				</button>
				<button type="button" class="btn btn-warning btn-xs" onclick="cerrarModalMapeoSector()" title="Cerrar modal">
					<span class="glyphicon glyphicon-remove"></span> Cerrar
				</button>
			</div>
		</div>
		<iframe id="iframeMapeoSector" src="about:blank" style="width:100%; height:490px; border:none; display:block;"></iframe>
	</div>

	<script src="../VALIDACIONES/ban_val_labores_act_relavera.js?v=3"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
	<!-- Cierra y libera las conexiones a la base de datos -->
	<?php
		$obBD_con1->liberar();
		$obBD_conexion->cerrar();
	?>
</BODY>

</HTML>