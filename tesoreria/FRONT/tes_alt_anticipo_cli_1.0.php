<?php

/**
 *
 * @abstract Premite registrar anticipos a Proveedores
 * @author Edison Moya
 * @version 1.0
 * Fecha de creacion  2018-03-09
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anticipo_cli_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del objeto de conexion
 */
$obBD_conexion_set = new Class_Log_Conexion_Ant_cli($Ses_Dat_Dis);
$obBD_con_set =  new Class_Log_Datos_Ant_cli;

$obBD_conexion_get = new Class_Log_Conexion_Ant_cli($Ses_Dat_Dis);
$obBD_con_get =  new Class_Log_Datos_Ant_cli;

//NEGOCIACIONES CAMARON
$configs = $obBD_con_get->getRowConsulta(40, $Ses_Emp_Cod, $obBD_conexion_get);
if ($configs["Cof_NegCam"] == 'S') {
	$grupo_empresas = $obBD_con_get->getRowConsulta(43, $Ses_Emp_Cod, $obBD_conexion_get);
	if (isset($negociacionesAjax)) {
		$Emp_Cod = $Ses_Emp_Cod;
		if (!empty($grupo_empresas["Emp_Cod"])) {
			$empresas = array_merge((array)$Emp_Cod, (array)$grupo_empresas["Emp_Cod"]);
			$Emp_Cod = implode(",", $empresas);
		}
		$data_negociaciones = $obBD_con_get->getArrayConsulta(41,  $Emp_Cod . '*' . $search, $obBD_conexion_get);
		$obBD_con_get->echoJson($data_negociaciones);
	}
}
//seccion para obtener los clientes registrados en la empresa
if (isset($clientesAjax)) {
	$obBD_con_get->getPageGridJson(1, $_GET, $obBD_conexion_get);
}

//para obtener planes de cuenta para agregar aportaciones
if (isset($cuentasAjax)) {
	$obBD_con_get->getPageGridJson(9, $_GET, $obBD_conexion_get);
}

if (isset($cargar_cuentas_pagos)) {
	$resp['bandera'] = true;
	$resp['message'] = "No se lograron cargar los datos";
	$data_ban = null;
	if ($tipo == 'INICIAL') {
		$data = $obBD_con_get->getRowConsulta(3, "", $obBD_conexion_get);
		$resp['message'] = "ANTICIPOS DE CLIENTES";
	}
	if ($tipo == 'EFE' || $tipo == 'OTR') {
		$data = $obBD_con_get->getArrayConsulta(4, array('Ban_Tip' => 'C'), $obBD_conexion_get);
		$resp['message'] = "PAGOS EN ";
	}
	if ($tipo == 'DEP' || $tipo == 'TRF' || $tipo == 'NDC') {
		$data = $obBD_con_get->getArrayConsulta(4, array('Ban_Tip' => 'B'), $obBD_conexion_get);
		$resp['message'] = "PAGOS EN ";
	}
	if ($tipo == 'CHE') {
		$data_ban = $obBD_con_get->getArrayConsulta(4, array('Ban_Tip' => 'B'), $obBD_conexion_get);
		$data = $obBD_con_get->getArrayConsulta(5, "", $obBD_conexion_get);
		$resp['message'] = "PAGOS EN ";
	}


	if (count($data) < 1) {
		$resp['bandera'] = false;
	}

	$resp['data'] = $data;
	$resp['data_ban'] = $data_ban;
	if ($obBD_con_get->Error == 0) {
		$resp['success'] = true;
		$resp['message'] = "Transaccion exitosa!";
	}
	$obBD_con_get->echoJson($resp);
}

//obtener el siguiente valor para ant_doc
if (isset($get_ant_doc)) {
	$resp['success'] = false;

	$ultimo_anticipo = $obBD_con_get->getRowConsulta(19, "", $obBD_conexion_get);
	if ($ultimo_anticipo['sig'] == 0) {
		$resp['data'] = "1";
	} else {
		$ultimo_ant_doc = $obBD_con_get->getRowConsulta(20, $ultimo_anticipo['sig'], $obBD_conexion_get);
		$resp['data'] = ($ultimo_ant_doc['Ant_Doc'] + 1);
	}

	if ($obBD_con_get->Error == 0) {
		$resp['success'] = true;
		$resp['message'] = "Transaccion exitosa!";
	}
	$obBD_con_get->echoJson($resp);
}

//Secci�n ajax para guardar un nuevo socio en la base de datos
if (isset($saveAnticipo)) {
	//Bloque el periodo contable
	//$obBD_con_get->validaCierrePeriodo('anticipos_clientes','Ant_Fec','Ant_Cod',$Ant_Fec,null,$obBD_conexion_get);
	//$obBD_con_set->debug(true);
	try {
		$response = array('success' => false);
		$obBD_con_set->inicio_transaccion($obBD_conexion_set->conexion);

		$Pec_Cod = $obBD_con_get->getRowConsulta(10, $Ant_Fec, $obBD_conexion_get);

		$var_mes = explode('-', $Ant_Fec);
		$Com_Num = $obBD_con_get->codigoComprAuto($Tia_Cod, $Pec_Cod['Pec_Cod'], $var_mes[1], $obBD_conexion_get);

		//insertamos un comprobante y extraemos el id ingresado
		$obBD_con_set->operacionobBD(11, array('Pec_Cod' => $Pec_Cod['Pec_Cod'], 'Cli_Cod' => $Cli_Cod, 'Com_Num' => $Com_Num, 'Com_Fec' => $Ant_Fec, 'Com_Con' => $Ant_Obs, 'Com_Val' => $Ant_Val, 'Tia_Cod' => $Tia_Cod), $obBD_conexion_set);
		$ultimo_comprobante = $obBD_con_set->insercionid($obBD_conexion_set);

		$antdoc = "";
		$ult_ant = $obBD_con_get->getRowConsulta(19, "", $obBD_conexion_get);
		if ($ult_ant['sig'] == 0) {
			$antdoc = "1";
		} else {
			$ultimo_ant_doc = $obBD_con_get->getRowConsulta(20, $ult_ant['sig'], $obBD_conexion_get);
			$antdoc = ($ultimo_ant_doc['Ant_Doc'] + 1);
		}
		//insertamos un anticipo de clientes
		$obBD_con_set->operacionobBD(12, array('Ant_Doc' => $antdoc, 'Ant_Fec' => $Ant_Fec, 'Ant_Val' => $Ant_Val, 'Ant_Obs' => $Ant_Obs, 'Com_Cod' => $ultimo_comprobante, 'Cli_Cod' => $Cli_Cod), $obBD_conexion_set);
		$ultimo_anticipo = $obBD_con_set->insercionid($obBD_conexion_set);

		//REGISTRAR LA NEGOCIACION DE CAMARON
		if (isset($Cod_Neg) && !empty($Cod_Neg) && $Cod_Neg != 0) {
			$obBD_con_set->operacionobBD(42, $Cod_Neg . '*' . $ultimo_anticipo . '*' . 'ANTC', $obBD_conexion_set);
		}

		// insertamos los pagos y sus respectivos asientos
		foreach ($pago_anticipo_clientes as $pago) {
			if ($pago['grid_tipp'] == 'pago') {
				// insertamos un asiento por cada pago
				$obBD_con_set->operacionobBD(13, array('Com_Cod' => $ultimo_comprobante, 'Asi_Deh' => 'D', 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Debe'], 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion_set);
				$ultimo_asiento = $obBD_con_set->insercionid($obBD_conexion_set);

				if ($pago['Pag_Abr'] == 'CHE') {
					// insertamos un registro en la tabla cheques_ext
					$obBD_con_set->operacionobBD(15, array('Bak_Cod' => $pago['Ban_Cod'], 'Cli_Cod' => $Cli_Cod, 'Che_Cta' => $pago['Pac_Cto'], 'Che_Num' => $pago['Che_Num'], 'Che_Fec' => $pago['Che_Fec'], 'Che_Val' => $pago['Debe'], 'Che_Obs' => $Ant_Obs, 'Che_Cli' => $nombre), $obBD_conexion_set);
					$ultimo_Cheque = $obBD_con_set->insercionid($obBD_conexion_set);

					// insertamos un pago de anticipo a proveedores
					$obBD_con_set->operacionobBD(14, array('Pac_Obs' => $pago['Glosa'], 'Pac_Num' => $pago['Pac_Num'], 'Pac_Cto' => $pago['Pac_Cto'], 'Pac_Ctd' => $pago['Pac_Ctd'], 'Pac_Val' => $pago['Debe'], 'Ant_Cod' => $ultimo_anticipo, 'Che_Cod' => $ultimo_Cheque, 'Pag_Cod' => $pago['Pag_Cod'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
				} else {
					// insertamos un pago de anticipo a proveedores
					$obBD_con_set->operacionobBD(14, array('Pac_Obs' => $pago['Glosa'], 'Pac_Num' => $pago['Pac_Num'], 'Pac_Cto' => $pago['Pac_Cto'], 'Pac_Ctd' => $pago['Pac_Ctd'], 'Pac_Val' => $pago['Debe'], 'Ant_Cod' => $ultimo_anticipo, 'Che_Cod' => 'null', 'Pag_Cod' => $pago['Pag_Cod'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
				}
			} else {
				$Pld_Cod_ini = $obBD_con_get->getRowConsulta(3, "", $obBD_conexion_get);

				// insertamos un asiento por cada pago
				$obBD_con_set->operacionobBD(13, array('Com_Cod' => $ultimo_comprobante, 'Asi_Deh' => 'H', 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Haber'], 'Pld_Cod' => $Pld_Cod_ini['Pld_Cod']), $obBD_conexion_set);
			}
		}
		$response['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo_comprobante&tabla=clientes&campo=Cli_Cod&tipo=$Tia_Cod&Pec_Cod=" . $Pec_Cod['Pec_Cod'];
		$response['link2'] = "./tes_pri_anticipo_cli_1.0.php?anticip=$ultimo_anticipo&client=$Cli_Cod";

		$obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set->conexion);
		if ($obBD_con_set->Error == 0) {
			$response['success'] = true;
		}
	} catch (Exception $e) {
		$obBD_con_set->rollBack_nomsn($obBD_conexion_set);
		$response['success'] = false;
		$response['message'] = '<span class="red">ERROR:</span> ' . $e->getMessage();
	}

	$obBD_con_set->echoJson($response);
}

if (isset($obtenerPeriodoMinMax)) {
	$resp['success'] = false;
	$resp['message'] = "No se ha logrado realizar la Transaccion";

	$resp['data'] = $obBD_con_get->getRowConsulta(8, "", $obBD_conexion_get);

	$resp['success'] = true;
	$obBD_con_set->echoJson($resp);
}

//verificamos si el numero de un cheque ya esta registrado dentro de la tabla cheques
if (isset($verificarCheNum)) {
	//Se obtiene el socio seleccionado
	$response['numero_che'] = false;
	$num_Ches = $obBD_con_get->getArrayConsulta(6, array('Bak_Cod' => $Bak_Cod, 'Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
	foreach ($num_Ches as $nch) {
		if ($nch['Che_Num'] == $Che_Num) {
			$response['numero_che'] = true;
		}
	}

	$obBD_con_set->echoJson($response);
	exit();
}
?>

<!DOCTYPE html>
<HTML>

<HEAD>
	<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?Php echo "Ant.Cliente Registrar [EXA]"; ?></TITLE>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
	<script src="../VALIDACIONES/tes_val_anticipo_cli_1.2.js?a=9"></script>
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
			<h3 class="panel-title">&raquo;Anticipos de Clientes</h3>
		</div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div class="row">
				<form class="form-horizontal normal" id="AnticipoCliForm" method="post" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?',null,guardar_anticipo)">
					<div class="col-sm-12">
						<div class="form-group Titulos2">
							<div class="col-sm-12">
								<b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
								<hr />
							</div>
						</div>
					</div>

					<div class="col-sm-12">
						<div class="row">
							<div class="col-sm-6">
								<fieldset class="exa-fieldset">
									<legend class="Titulos2">Datos del Cliente</legend>
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm required">C&eacute;dula/RUC:</label>
										<div class="col-sm-6">
											<input name="bandera_prov" id="bandera_prov" type="text" value="nosel" style="display:none;" />
											<input name="Prs_Cod" id="Prs_Cod" type="text" style="display:none;" />
											<input name="Cli_Cod" id="Cli_Cod" type="text" style="display:none;" />
											<input name="save_bnd" id="save_bnd" type="text" value="n" style="display:none;" />
											<input name="Ant_Val" id="Ant_Val" type="text" value="0.00" style="display: none;" />
											<div class="input-group input-group-sm">
												<input name="Prs_Ced" id="Prs_Ced" type="text" placeholder="Seleccione o cree un cliente..." class="form-control input-sm" tabindex="1" required="" readonly />
												<span class="input-group-btn">
													<button type="button" onclick="$('#clientesDialog').dialog('open');" class="btn btn-success btn-sm" title="Buscar Cliente" tabindex="2"><span class="glyphicon glyphicon-list-alt"></span></button>
												</span>
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm">Cliente:</label>
										<div class="col-sm-6"><input name="nombre" id="nombre" class="form-control input-sm databind datatitle" readonly /></div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm">Direcci&oacute;n:</label>
										<div class="col-sm-6"><input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-sm databind datatitle" readonly /></div>
									</div>

									<!-- Negociacion -->
									<?php if ($configs["Cof_NegCam"] == 'S') { ?>
										<div class="form-group">
											<label class="col-sm-4 control-label label-sm">Neg. camarón:</label>
											<div class="col-sm-6">
												<div class="input-group input-group-sm">
													<input type="text" name="Num_Neg" id="Num_Neg" placeholder="Ingrese cod.Negociación..." class="form-control input-sm clearable dialogSearch" tabindex="1" readonly />
													<input type="text" name="Cod_Neg" id="Cod_Neg" style="display:none;" />
													<span class="input-group-btn">
														<button id="Prv_Btn_" type="button" onclick="$('#negDialog').dialog('open');" class="btn btn-success btn-sm" title="Buscar Negociación" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
													</span>
												</div>
											</div>
										</div>
									<?php } ?>


								</fieldset>
							</div>
							<div class="col-sm-6">
								<fieldset class="exa-fieldset">
									<legend class="Titulos2">Datos del Anticipo</legend>
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm">No. Ant.:</label>
										<div class="col-sm-2">
											<!-- <div class="input-group input-group-sm"> -->
											<input name="Ant_Doc_ver" type="text" id="Ant_Doc_ver" class="form-control input-sm" readonly />
											<input name="Ant_Doc" type="text" id="Ant_Doc" hidden />
											<!-- </div> -->
										</div>
									</div>
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
											<select id="Tia_Cod" name="Tia_Cod" class="form-control input-sm readOnly" required="">
												<?php $rows_tipo_asiento = $obBD_con_set->getArrayConsulta(7, "", $obBD_conexion_set);
												if (count($rows_tipo_asiento) > 0) {
													foreach ($rows_tipo_asiento as $row) {
														echo "<option value='$row[Tia_Cod]' data-abr='$row[Tia_Abr]'>$row[Tia_Abr] - $row[Tia_Des]</option>";
													}
												} ?>
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
					<div class="row">
						<div class="col-sm-12">
							<div class="center">
								<div class="center">
									<br>
									<a class="btn btn-sm btn-success no" onclick="preguardadopagos();">
										<i class="glyphicon glyphicon-floppy-disk"></i> Guardar</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Inicio del diálogo para buscar Proveedores -->
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
								<?php $rows_tipo_pago = $obBD_con_get->getArrayConsulta(2, "", $obBD_conexion_get);
								if (count($rows_tipo_pago) > 0) {
									foreach ($rows_tipo_pago as $row) {
										echo "<option value='$row[Pag_Cod]' data-abr='$row[Pag_Abr]' data-class='$row[Pag_Des]' >$row[Pag_Des]</option>";
									}
								} ?>
							</select>
						</div>
					</div>

					<!-- Bancos de DataBase -->
					<div class="form-group Transferencia Efectivo Deposito NotaCredito Otros">
						<label class="col-xs-4 control-label label-xs required">Acreditar a:</label>
						<div class="col-xs-6">
							<select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly" required="">
							</select>
						</div>
					</div>

					<div class="form-group Cheque">
						<label class="col-xs-4 control-label label-xs required">Banco:</label>
						<div class="col-xs-6">
							<select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs readOnly" required="">
							</select>
						</div>
					</div>

					<div class="form-group Transferencia Deposito NotaCredito Otros">
						<label class="col-xs-4 control-label label-xs" id="doc">No. Docum.:</label>
						<div class="col-xs-6">
							<input type="text" id="Pac_Num" name="Pac_Num" onchange="" class="form-control input-xs">
						</div>
					</div>

					<div class="form-group  Cheque Transferencia">
						<label class="col-xs-4 control-label label-xs required">Cta. Origen:</label>
						<div class="col-xs-6">
							<input type="text" id="Pac_Cto" name="Pac_Cto" onchange="" onkeypress="return soloNumeros(event)" class="form-control input-xs">
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
								<span class="input-group-addon">
									<i id="indicadorChe" class=""></i>
								</span>
								<input type="text" id="Che_Num" name="Che_Num" onchange="" class="form-control input-xs" onkeyup="verificarNoCheque(this.value)"
									onkeypress="return soloNumeros(event)">
							</div>
						</div>
					</div>


					<div class="form-group Transferencia Deposito NotaCredito Efectivo Cheque Otros">
						<label class="col-xs-4 control-label label-sm required">Valor:</label>
						<div class="col-xs-6 ">
							<div class="input-group input-group-xs">
								<span class="input-group-addon">
									<i id="indicadorChe" class="glyphicon glyphicon-usd"></i>
								</span>
								<input name="Pac_Val" type="text" id="Pac_Val" size="10" class="form-control input-xs" required="" autocomplete="off" onchange="cambioValPago($(this));"
									onkeypress="return  validar_decimal(event)" />
							</div>
						</div>
					</div>

					<div class="form-group center">
						</br>
						<a class="btn btn-sm btn-primary" onclick="AgregarPago()">
							<i class="glyphicon glyphicon-floppy-disk"></i> Agregar</a>
					</div>
				</form>
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
							<span>Imprimir Comprobante</span>
						</span>
					</a>
					<a id="impAnt" target="_blank" href="" style="display: inline;" title="Imprimir Comprobante">
						<span class="btn btn-success start">
							<i class="icon-print icon-white"></i>
							<span>Imprimir Anticipo</span>
						</span>
					</a>
				</center>
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

					<div class="form-group  Cheque Transferencia Efectivo Otros">
						<label class="col-xs-4 control-label label-xs">No. Docum.:</label>
						<div class="col-xs-6">
							<input type="text" id="Pac_Num_ver" name="Pac_Num_ver" class="form-control input-xs" readonly>
						</div>
					</div>

					<!-- Bancos de DataBase -->
					<div class="form-group Cheque Transferencia">
						<label class="col-xs-4 control-label label-xs">Cuenta:</label>
						<div class="col-xs-6">
							<input type="text" id="cuenta_ver" class="form-control input-xs" readonly>
						</div>
					</div>

					<div class="form-group  Deposito NotaCredito Transferencia">
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

					<div class="form-group Transferencia Deposito NotaCredito Efectivo Cheque Otros">
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

		</div>
	</div>
	<div id="cuentasDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>

	<!-- Negociaciones-->
	<div id="negDialog" title="B&uacute;squeda de Negociación">
		<form id="frm_nego" name="frm_nego" class="form-horizontal normal" action="javascript:$('#containerNegoci').Search('#frm_nego','negociacionesAjax'); ">
			<fieldset class="exa-fieldset" id="prodFormTemp">
				<div class="col-xs-12 col-sm-12">
					<legend class="Titulos2">B&uacute;squeda</legend>
					<div class="form-group">
						<div class="col-sm-12">
							<div class="input-group">
								<input id="search" name="search" onkeydown=" this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
								<span class="input-group-btn">
									<button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="-1">
										<span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
									</button>
								</span>
							</div>
						</div>
					</div>
					<input type="text" tabindex="-1" style="display:none;">
				</div>
			</fieldset>
		</form>
		<table id="containerNegoci"></table>
	</div>

	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script>
		//Ver negociaciones
		$('#negDialog').dialog({
			autoOpen: false
		});
		var containerNegoci = $("#containerNegoci");
		$(function() {
			armargrid();
		});

		function armargrid() {
			containerNegoci.createGrid({
				width: 260,
				height: 140,
				colModel: [{
						label: 'Cod.Cop',
						name: 'Cod_Neg',
						width: 30
					},
					{
						label: 'Num.Agu',
						name: 'Num_Neg',
						width: 80
					},
					{
						label: '&nbsp;',
						name: 'act1',
						width: 30,
						align: 'center',
						viewable: false,
						formatter: 'gridButton',
						formatoptions: {
							action: selectNego
						}
					},
				],
				jsonReader: {
					root: "response",
					repeatitems: false
				},
				datatype: "local",
				footerrow: false,
			});
		}

		function selectNego(data) {
			$('#Num_Neg').val(data['Num_Neg']);
			$('#Cod_Neg').val(data['Cod_Neg']);
			$('#negDialog').dialog('close');
		}
	</script>


</BODY>

</html>