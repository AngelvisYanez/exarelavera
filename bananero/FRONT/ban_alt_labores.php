<?php

/**
 * @abstract Permite registrar las labores
 * @author Cesar Bermeo.
 * @version 1.0
 * Fecha de creaci�n: 07-02-2019
 *
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_labores.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Lab;

$hoy = date("Y-m-d");


/**
 * Eliminar Labor
 */
if (isset($elimLabor)) {
	$obBD_ins1 = new Class_Log_Datos_Lab;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try {
		$obBD_con1->operacionobBD('labores.setInactive', array('Lab_Cod' => $Lab_Cod), $obBD_conexion);
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
	$obBD_ins1 =  new Class_Log_Datos_Lab;
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
 * Busqueda Grid Labores
 */
if (isset($laboresAjax)) {
	$data = array_merge($_GET,  array('setWhere' => array('isActive', 'orderByDes', 'byFormaPago', 'setEmpCod')));
	$respuesta = $obBD_con1->getPageGridJson('labores.selectWhere', $data, $obBD_conexion);
}

/**
 * Busqueda de validacion con Finca y Semana
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
 * Busca pagos de Labores apartir de una descripci�n
 */
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
		'listLab' => $obBD_con1->getArrayConsulta('labores.selectWhere', array('setWhere' => array('isActive', 'orderByDes', 'byFormaPago')), $obBD_conexion),
	);
	$obBD_con1->echoJson($laboresList);
}
/**
 * Busqueda de Fincas
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
	$resultado = $obBD_con1->getPageGrid('actividad_labor.selectWhere', $datos, $obBD_conexion);
	$obBD_con1->echoJson($resultado);
}
/**
 * Busqueda detalle activiades
 */
if (isset($searchDetActivi)) {

	$detActividad = array(
		'success' => true,
		'detalleAct' => $obBD_con1->getArrayConsulta('det_actividad_labor.selectWhere', array('det_actividad_labor.Act_Cod' => $Act_Cod, 'setWhere' => array('byLabor', 'byTipoPagoLabor', 'byPersonal', 'byTrabajador')), $obBD_conexion),
	);
	$obBD_con1->echoJson($detActividad);
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
//$obBD_con1->echoLog($tipoPagos);
?>

<!DOCTYPE html>
<HTML>

<HEAD>
	<TITLE>
		<?Php echo $Ses_Sys_Nom; ?>
	</TITLE>
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
	<script> </script>
	<style>
		input::-webkit-outer-spin-button,
		input::-webkit-inner-spin-button {
			/* display: none; <- Crashes Chrome on hover */
			-webkit-appearance: none;
			margin: 0;
			/* <-- Apparently some margin are still there even though it's hidden */
		}
	</style>
</HEAD>

<BODY>
	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; Gestionar Actividades Bananeras</h3>
		</div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div class="row">
				<!-- <div class="col-sm-3">
                        <div class="panels-area form-horizontal normal ">
                            <div class="row">
                                <div class="col-xs-12">
                                    <form class="form-horizontal normal" id="frmPagoLabor" name="frmPagoLabor" autocomplete="off">
                                        <fieldset class="exa-fieldset" >
                                            <legend class="Titulos2">Consultar Tipo Labor</legend>
                                            <div class="form-group">
                                                <label class="col-xs-8 control-label label-xs">Unidades de Labor</label>
                                                <div class="col-xs-10 input-group input-group-xs ret" style="margin:30px;padding-top: 1px;">
                                                    <select id="Tpg_Cod_D" name="Tpg_Cod_D" onchange="" class="form-control input-xs select_tipo_labor">
                                                        <?php foreach ($tipoPagos as $tip) { ?>
	<?php echo mb_convert_encoding($tip, 'ISO-8859-1', 'UTF-8'); ?>
	<option value="<?php echo $tip['Tpg_Cod']; ?>" data-extra="<?php echo $tip['Tpg_Est']; ?>">
		<?php echo mb_convert_encoding($tip['Tpg_Des'], 'ISO-8859-1', 'UTF-8'); ?>
	</option>
	<?php } ?>
	</select>
	<span class="input-group-btn">
		<button id="agregarLabor" type="button" onclick="$('#unidadDialog').dialog('open');" class="btn btn-success btn-xs" title="Agregar Unidad"
		 tabindex="2">
			<span class="glyphicon glyphicon-plus-sign"></span>
		</button>
	</span>
	</div>
	</div>
	</fieldset>
	</form>
	</div>
	</div>
	</div>
	</div> -->
				<div class="col-sm-12">
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
									<div class="col-md-10 col-sm-8 col-md-offset-1">
										<form id="frm_alt_actividad" name="frm_alt_actividad" class="form-horizontal normal" action="javascript:saveData('frm_alt_actividad','saveActividad','actividad') ">
											<fieldset class="exa-fieldset">
												<legend class="Titulos2">Datos</legend>
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
														<div class="input-group input-group-xs">
															<select id="Act_Sem" name="Act_Sem" onchange="verificaExistente()" class="form-control input-xs select_semna">
																<option value="0" required="true">Seleccione...</option>
															</select>
															<span class="input-group-addon validate">
																<i></i>
															</span>
														</div>

													</div>
													<label class="col-xs-2 control-label label-xs required">Fecha:</label>
													<div class="col-xs-2">
														<input type="text" id="Act_Fec" name="Act_Fec" class="form-control input-xs datepickers" style="text-align:center;" required></input>
													</div>

												</div>
												<div class="form-group">
													<div class="col-xs-1"></div>
													<label class="col-xs-2 control-label label-xs required">Finca:</label>
													<div class="col-xs-4">
														<div class="input-group input-group-xs">
															<select id="Fnc_Cod_D" name="Fnc_Cod_D" onchange="verificaExistente()" class="form-control input-xs select_finca">
																<option value="0" required="true">Seleccione...</option>
															</select>
															<span class="input-group-addon validate">
																<i></i>
															</span>
														</div>

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
											<div>
												<table id="tableActividad"></table>
												<div id="tableActividadPager"></div>
											</div>
											<div style="text-align: center;padding-top: 5px;">
												<button type="button" id="btn_gua_act" name="btn_gua_act" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();">
													<span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
											</div>
										</form>
									</div>
								</div>
							</div>
							<div id="tabs-3">
								<div id="tab3" class="row">
									<div class="col-md-10 col-sm-8 col-md-offset-1">
										<form id="frm_mod_actividad" name="frm_mod_actividad" class="form-horizontal normal" action="javascript:$('#consultarGrid').Search('#frm_mod_actividad','searchAllActiv');limpiarBusq();">
											<fieldset class="exa-fieldset">
												<legend class="Titulos2">B&uacute;squeda de Actividades</legend>
												<div class="form-group">
													<label class="col-sm-2 control-label label-xs">Filtrar por:</label>
													<div class="col-sm-5 radioset">
														<input id="rad_ba1" name="op_opciones" type="radio" value="fnc" checked="" onclick="setfocus(this.form.search)" />
														<label for="rad_ba1">&nbsp;&nbsp;Finca&nbsp;&nbsp;</label>
														<input id="rad_ba2" name="op_opciones" type="radio" value="lbr" onclick="setfocus(this.form.search)" />
														<label for="rad_ba2">&nbsp;&nbsp;Trabajador&nbsp;&nbsp;</label>
														<input id="rad_ba3" name="op_opciones" type="radio" value="fch" onclick="setfocus(this.form.search)" />
														<label for="rad_ba3">&nbsp;&nbsp;Por Fecha&nbsp;&nbsp;</label>
													</div>
													<!-- <div class="col-sm-5">
											<div class="col-sm-6">
												<label class="col-sm-6 control-label label-xs">Semana:</label>
												<div class="radioset ui-buttonset">
													<input class="form-check-input" onclick="verficarSemana();" type="radio" name="Semn" id="inlineRadio1" value="S">
													<label class="form-check-label" for="inlineRadio1">Si</label>
													<input class="form-check-input" onclick="verficarSemana();" type="radio" name="Semn" id="inlineRadio2" value="N" checked="checked">
													<label class="form-check-label" for="inlineRadio2">No</label>
												</div>

											</div>

											<div id="selectSelmana" class="col-sm-6" style="display:none;">
												<select name="semna" id="semna" onchange="" class="form-control input-xs select_sem_busq">
													<option value="" data-extra="">Seleccione Semana...</option>
												</select>
											</div>

										</div> -->
												</div>
												<div id="divFecha" class="form-group" style="display:none;">
													<div class="col-xs-2"></div>
													<div class="col-xs-4">
														<div class="input-group input-group-xs por_fecha">
															<span class="input-group-addon">
																<span class=""></span>
															</span>
															<span class="input-group-addon alert-info">Desde</span>
															<input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control" disabled="" />
															<span class="input-group-addon alert-info">Hasta</span>
															<input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control" disabled="" />
														</div>
													</div>

												</div>
												<div class="form-group">
													<label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
													<div class="col-sm-5">
														<div class="input-group">
															<!-- <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda"
												 autofocus=""> -->
															<select name="Cod_Bus" id="Cod_Bus" class="form-control input-xs select_search" data-placeholder="Seleccione..">
																<option value=""></option>
															</select>

															<span class="input-group-btn">
																<button id="btnSearch" onclick="this.form.submit()" class="btn btn-success btn-xs" type="button" title="Buscar Cliente">
																	<span class="glyphicon glyphicon-search"></span> Buscar</button>
															</span>
														</div>
													</div>
												</div>
											</fieldset>
										</form>
										<div class="row">
											<div class="col-sm-12">
												<table id="consultarGrid" name="consultarGrid"></table>
												<div id="cgPager"></div>
											</div>
										</div>
									</div>
									<div id="dialogInfo">
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
													<label class="col-xs-2 control-label label-xs required">Finca:</label>
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
												<i class="glyphicon glyphicon-arrow-left"></i> Atr�s</button>
											<button type="button" id="btn_guardado" name="btn_guardado" class="btn btn-primary btn-sm" onclick="$('#frm_mod_act_edi').formSubmit();"
												disabled="disabled">
												<span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
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

	<script src="../VALIDACIONES/ban_val_labores.js?k=5621"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>

</HTML>