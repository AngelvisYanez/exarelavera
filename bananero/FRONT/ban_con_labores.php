<?php

/**
 * @abstract Permite consultar las labores
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de creacion: 07-02-2019
 *
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_con_labores.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Con_Lab;

$hoy = date("Y-m-d");

/**
 * Busqueda actividad labor por Cod y Per_Cod
 */
if (isset($movLabor)) {
	$data = array_merge($_GET, array('setWhere' => array('byDetAct', 'byLabor', 'byTipoPagoLabor', 'isMovActCod', 'isMovPerCod', 'addTotal')));
	$respuesta =  $obBD_con1->getPageGrid('actividad_labor.selectWhere', $data, $obBD_conexion);
	$obBD_con1->echoJson($respuesta);
}

/**
 * Busqueda de semanas
 */
if (isset($searchSemanas)) {
	//$obBD_con1->echoLog('Semanas lola');
	$resultado = array(
		'success' => true,
		'encabezadosPorFinca' => $obBD_con1->getArrayConsulta('actividad_labor.selectWhere', array('Fnc_Cod' => $Fnc_Cod, /* 'where'=>array('Fnc_Cod'=>$Fnc_Cod), */ 'setWhere' => array('isActive', 'byDetAct', 'byLabor', 'byPersonal', 'byTrabajador')), $obBD_conexion),
	);

	$obBD_con1->echoJson($resultado);
}

/* * *
 * Busqueda de semanas por finca y a�o
 */
if (isset($weekAjax)) {
	$semanasList = array(
		'success' => true,
		'listWeek' => $obBD_con1->getArrayConsulta('actividad_labor.selectWhere', array('Fec_Ini' => $Fec_Ini, 'Fec_Fin' => $Fec_Fin, 'where' => array('Fnc_Cod' => $Fnc_Cod), 'setWhere' => array('isActive', 'xFecha')), $obBD_conexion),
	);
	$obBD_con1->echoJson($semanasList);
}
/**
 * Busqueda de los datos del SubGrid
 */
if (isset($detallePorGrid)) {
	$resultado = array(
		'success' => true,
		'detSubGrid' => $obBD_con1->getArrayConsulta('actividad_labor.selectWhere', array('where' => array('dfnc.Per_Cod' => $Per_Cod, 'dfnc.Act_Cod' => $Act_Cod), 'setWhere' => array('byDetAct', 'byLabor', 'byTipoPagoLabor', 'addTotal')), $obBD_conexion),
	);
	$obBD_con1->echoJson($resultado);
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

if (isset($rolesAjax)) {
	$data = $_POST;
	$responce['rows'] = $obBD_con1->getArrayConsulta(1, $data, $obBD_conexion);
	$responce['records'] = count($responce['rows']);
	$responce['success'] = true;
	$obBD_con1->echoJson($responce);
}


if (isset($printRolIndAjax)) {
	$data = $_GET;
	$trabajadores = $obBD_con1->getArrayConsulta(2, $data, $obBD_conexion);
	$contador = 0;
	$responce['tabla'] =  '<!DOCTYPE html>
							<html>
							<head>
							    <style>
							        table {
							            border-collapse: collapse;
							            width: 100%;
							            font-size:11px;
							        }
							        th, td {
							            border: 1px solid black;
							            padding: 8px;
							            text-align: center;
							        }
							        th {
							            background-color: #f2f2f2;
							        }
							         .miTabla {
								      width: 100%;
								      text-align: center;
								      border-collapse: collapse; /* Combina los bordes de las celdas */
								      margin-top: 60px;
								      margin-bottom: 80px;
								      font-size:11px;
								    }

								    /* Estilos para las celdas con clase "miCelda" */
								    .miCelda {
								      border: none;
								      padding: 10px; /* Espacio alrededor del texto */
								      border-top: 1px solid black; /* Borde superior */
								    }

								    @media all { div.saltopagina{ display: none; } } 
								    @media print{ div.saltopagina{ display:block; page-break-before:always; } } 

							    </style>
							</head>
							<body>';

	foreach ($trabajadores as $trabajador) {
		$contador++;
		$responce['tabla'] .=	'<table>
						    <tbody>
						        <tr>
						            <td style="font-weight: bold;">Area:</td>
						            <td >' . $trabajador['Are_Des'] . '</td>
						        </tr>

						        <tr style="font-weight: bold;"><td>Cod.Int</td><td>Cedula</td><td>Trabajador</td><td>Total</td><td colspan="2">SEMANA</td>
						        </tr>

						        <tr>
						            <td>' . $trabajador['Rol_Cod'] . '</td>
						            <td>' . $trabajador['Prs_Ced'] . '</td>
						            <td>' . $trabajador['trabajador'] . '</td>
						            <td>' . $trabajador['total'] . '</td>
						            <td colspan="2">' . $trabajador['Rol_Num'] . '</td>
						        </tr>

						        <tr style="font-weight: bold;"><td>Fecha ini</td><td>Fecha fin</td><td>Labor</td><td>P.Unitario</td><td>Cantidad</td><td>Total</td>
						        </tr>';

		$data['Prs_Ced'] = $trabajador['Prs_Ced'];
		$detalleTrabajador = $obBD_con1->getArrayConsulta(3, $data, $obBD_conexion);
		foreach ($detalleTrabajador as $detalle) {
			$cantidadHoras = 1;
			$valorHora = $detalle['Rol_Val'];

			if ($detalle['Cam_Var'] == 'TTL_HREXTO') {
				$data['Cam_Var'] = 'CANT_HREX';
				$cantidadHorasq = $obBD_con1->getRowConsulta(5, $data, $obBD_conexion);
				$cantidadHoras = $cantidadHorasq['Rol_Val'];
				$valorHora = ROUND($detalle['Rol_Val'] / $cantidadHoras, 2);
			}

			if ($detalle['Cam_Var'] == 'TOTAL _HRS') {
				$data['Cam_Var'] = 'CANT_HRSUP';
				$cantidadHorasq = $obBD_con1->getRowConsulta(5, $data, $obBD_conexion);
				$cantidadHoras = $cantidadHorasq['Rol_Val'];
				$valorHora = ROUND($detalle['Rol_Val'] / $cantidadHoras, 2);
			}

			if ($detalle['Cam_Var'] == 'sueldo_dias') {
				$data['Rol_Num'] = $detalle['Rol_Num'];
				$data['Pec_Cod'] = $detalle['Pec_Cod'];
				$data['Per_Cod'] = $detalle['Per_Cod'];
				$detalleLaboresq = $obBD_con1->getRowConsulta(6, $data, $obBD_conexion);
				$detalleLabores = $detalleLaboresq['descripcion']; //$cantidadHorasq['Rol_Val'];
				$detalle['Cam_Des'] = $detalleLabores;
			}


			$responce['tabla'] .=	'<tr>
							            <td>' . $trabajador['Rol_Fei'] . '</td>
							            <td>' . $trabajador['Rol_Fef'] . '</td>
							            <td>' . $detalle['Cam_Des'] . '</td>
							            <td>' . $valorHora . '</td>
							            <td>' . $cantidadHoras . '</td>
							            <td>' . $detalle['Rol_Val'] . '</td>
							        </tr>';
		}

		$responce['tabla'] .= '</tbody>
							</table>

							<table class="miTabla">
							  <tr>
							    <td class="miCelda">Elaborado por</td>
							    <td style="border: none;"></td>
							    <td class="miCelda">Revisado por</td>
							  </tr>
							</table>';

		if ($contador % 2 == 0) {
			$responce['tabla'] .= '<div class="saltopagina"></div>';
		}
	}

	$responce['tabla'] .=	'</body>
						</html>';
	$responce['success'] = true;
	$obBD_con1->echoJson($responce);
}

//Periodos
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
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
	<script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
	<script></script>
	<style>
		.visibilityHide {
			visibility: hidden;
		}
	</style>
</HEAD>

<BODY>
	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; REPORTE LABORES</h3>
		</div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div class="row">
				<div class="col-sm-12">
					<div id="tabsConLabores" class="ui-tab-fix">
						<ul>
							<li><a href="#tabs-1">Comparativo</a></li>
							<li><a href="#tabs-2">Individual</a></li>
							<li><a href="#tabs-3">Certificacion</a></li>
						</ul>
						<div class="panels-area form-horizontal normal ">
							<div id="tabs-1">
								<div class="row">
									<div class="col-xs-12">
										<div id="formDatosConsultarLabor" class="col-md-10 col-sm-8 col-md-offset-1">
											<form class="form-horizontal normal" id="frmLaborSearch" name="frmLaborSearch" autocomplete="off" action="javascript:getColumGrid('frmLaborSearch','sinMetodo','noEsDialog')">
												<fieldset class="exa-fieldset">
													<legend class="Titulos2">Filtros</legend>

													<div class="form-group">
														<label class="control-label col-xs-3 col-xs-4 label-sm required">Finca:</label>
														<div class="col-md-6 col-sm-4">
															<select id="Fnc_Cod" name="Fnc_Cod" onchange="habilitaSemana()" class="form-control input-xs select_finca">
																<option value="0" required="true">Seleccione...</option>
															</select>
														</div>
													</div>
													<div class="form-group">
														<label class="control-label col-xs-3 col-xs-4 label-sm required">Semanas:</label>
														<input type="text" name="por_peri" id="por_peri" value="n" style="display:none">
														<div class="col-md-6 col-sm-4">
															<div class="input-group">
																<span class="input-group-addon" style="padding:2px 5px 0px 5px;margin:0;line-height:0;">
																	<input type="checkbox" id="f_periodo" name="f_periodo" onchange="cambioFiltro()" disabled>
																</span>
																<select class="form-control input-xs select_perido" name="sel_per" id="sel_per" onchange="searchSemanas()" disabled>
																	<option value="0" required="true">Seleccione...</option>
																	<?php
																	foreach ($periodos as $p) {
																		echo "<option data--year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data--pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
																	}
																	?>
																</select>
																<select multiple id="Act_Sem" name="Act_Sem" class="form-control input-xs select_semana" onchange="verificaSelSem()" data-placeholder="Semanas a Asignar" disabled>
																</select>
															</div>
														</div>
													</div>

												</fieldset>
												<div class="col-xs-12 center vcenter" style="height: 70px;">
													<button type="submit" class="btn btn-success" id="btn_buscar" name="btn_buscar" disabled>
														<i class="glyphicon glyphicon-list-alt"></i> Generar</button>
													<button class="btn btn-primary" id="btn_limpiar" name="btn_limpiar" onclick="limpiarTodo();" disabled>
														<i class="glyphicon glyphicon-refresh"></i> Limpiar</button>
												</div>
											</form>
										</div>
										<div class="col-xs-12" style="padding-bottom: 8px; min-height: 300px;" id="gridContainer">
											<table id="list"></table>
											<div id="listPager"></div>
										</div>
									</div>
								</div>
							</div>

							<div id="tabs-2">
								<div class="row">
									<div class="col-xs-12">
										<div id="formDatosConsultarSemana" class="col-md-3 col-sm-3 col-md-offset-1">
											<form class="form-horizontal normal" id="frmLaborSearchWeeks" name="frmLaborSearchWeeks" autocomplete="off" action="javascript:getBuildGrid('frmLaborSearchWeeks','sinMetodo','noEsDialog')">
												<fieldset class="exa-fieldset">
													<legend class="Titulos2">Filtros</legend>

													<div class="form-group">
														<div class="col-xs-12" style="text-align: center;padding-top: 10px;">
															<label for="Per_Con" class="control-label label-xs">Periodo</label>
															<select name="Per_Con" id="Per_Con" onchange="limpioTodo();" class="form-control input-sm select_perido_ind">
																<option value="0" data-extra="">Seleccione...</option>
																<?php
																foreach ($periodos as $p) {
																	echo "<option data--year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data--pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
																}
																?>
															</select>
														</div>
													</div>
													<div class="form-group">
														<div class="col-xs-12" style="text-align: center;padding-top: 10px;">
															<label for="Fnc_Cod" class="control-label label-xs">Finca</label>
															<select name="Fnc_Cod" id="Fnc_Cod" onchange="habilitaPeriodo();" class="form-control input-sm select_finca_ind">
																<option value="0" data-extra="">Seleccione...</option>
															</select>
														</div>
													</div>
													<div class="form-group">
														<div class="col-xs-12" style="text-align: center;padding-top: 10px;">
															<label for="Act_Sem" class="control-label label-xs">Semana</label>
															<select name="Act_Sem" id="Act_Sem" onchange="limpiaGrid();" class="form-control input-sm select_semna_ind" disabled>
																<option value="0" data-extra="">Seleccione...</option>
															</select>
														</div>
													</div>
												</fieldset>
												<div class="col-xs-12 center vcenter" style="height: 70px;">
													<button type="submit" class="btn btn-success" id="btn_buscar_ind" name="btn_buscar_ind" disabled>
														<i class="glyphicon glyphicon-list-alt"></i> Generar</button>

												</div>
												<div id='MessageHolder'></div>
												<a href="#" id="testAnchor"></a>
											</form>
										</div>
										<div class="col-xs-8" style="padding-bottom: 8px; min-height: 300px;" id="gridContainerInd">
											<table id="listInd"></table>
											<div id="listPagerInd"></div>
										</div>
									</div>
								</div>
							</div>

							<div id="tabs-3">
								<div class="row">
									<div class="col-xs-12">
										<div id="formDatosConsultarSemanaCertificado" class="col-md-3 col-sm-3 col-md-offset-1">
											<form class="form-horizontal normal" id="frmLaborSearchCertificado" name="frmLaborSearchCertificado" autocomplete="off" action="javascript:getBuildGrid('frmLaborSearchCertificado','sinMetodo','noEsDialog')">
												<fieldset class="exa-fieldset">
													<legend class="Titulos2">Filtros</legend>

													<div class="form-group">
														<div class="col-xs-12" style="text-align: center;padding-top: 10px;">
															<label for="Pec_Cod" class="control-label label-xs">Periodo</label>
															<select name="Pec_Cod" id="Pec_Cod" onchange="getRoles()" class="form-control input-sm select_perido_ind">
																<?php
																foreach ($periodos as $p) {
																	echo "<option data--year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data--pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
																}
																?>
															</select>
														</div>
													</div>

													<div class="form-group">
														<div class="col-xs-12" style="text-align: center;padding-top: 10px;">
															<label for="Are_Cod" class="control-label label-xs">Area</label>
															<select id="Are_Cod" name="Are_Cod" class="form-control input-xs" onchange="getRoles()">
																<option value="">TODAS</option>
																<?php $rs_area = $obBD_con1->getArrayConsulta(4, $Ses_Emp_Cod, $obBD_conexion);
																foreach ($rs_area as $row) { ?>
																	<option value="<?php echo $row['Are_Cod']; ?>"><?php echo $row['Are_Des']; ?></option>
																<?php } ?>
															</select>
														</div>
													</div>
													<div class="form-group">
														<div class="col-xs-12" style="text-align: center;padding-top: 10px;">
															<label for="Rol_Tip" class="control-label label-xs">Tipo</label>
															<select id="Rol_Tip" name="Rol_Tip" class="form-control input-xs readOnly datatrigger" onchange="getRoles()">
																<option value="M">Mensual</option>
																<option value="Q">Quincenal</option>
																<option value="BS">Bi Semanal</option>
																<option value="S">Semanal</option>
															</select>
														</div>
													</div>

													<div class="form-group ranges M Q S BS" style="text-align: center;padding-top: 10px;">
														<div class="col-xs-12 ranges M Q">
															<label class="control-label label-xs">Mes:</label>
															<div class="input-group input-group-xs">
																<input id="Month" name="Month" type="hidden">
																<span id="Mes" class="form-control"></span>
																<span class="input-group-btn">
																	<button id="MonthButton" onclick="$('#Month').monthpicker('show','#Mes');" class="btn btn-success" type="button"><span class="glyphicon glyphicon-calendar" title="Seleccione Mes"></span></button>
																</span>
															</div>
														</div>
													</div>

													<div class="form-group">
														<div class="col-xs-12" style="text-align: center;padding-top: 10px;">
															<label for="Fnc_Cod" class="control-label label-xs">Roles</label>
															<select name="Rol_Cod" id="Rol_Cod" class="form-control input-sm select_finca_ind">
															</select>
														</div>
													</div>

												</fieldset>
												<div class="col-xs-12 center vcenter" style="height: 70px;">
													<button type="submit" class="btn btn-success" id="btn_buscar_certificado" name="btn_buscar_certificado" onclick="printRolDetailIndiv($(this).data('originaldata'))" disabled>
														<i class="glyphicon glyphicon-list-alt"></i> Generar</button>

												</div>
												<div id='MessageHolder'></div>
												<a href="#" id="testAnchor"></a>
											</form>
										</div>
										<div class="col-xs-8" style="padding-bottom: 8px; min-height: 300px;" id="gridContainerInd">
											<table id="listInd"></table>
											<div id="listPagerInd"></div>
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
	<div id="imprimirRoles" style="display: none;width: 1200px;"></div>
	<script src="../VALIDACIONES/ban_val_con_labores.js?k=555"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
	<script type="text/javascript">
		$(function() {
			$('#Month').attr('data-monthplacer', '#Mes').createMonthPicker({
				showYear: false,
				prepend: 'Seleccione Mes',
				openOnFocus: false
			}).monthpicker('setMonthActive', 0);;
		});
	</script>
</BODY>

</HTML>