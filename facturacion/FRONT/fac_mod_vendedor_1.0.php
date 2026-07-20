<?php

/**
 * Descripción: Permite registrar alta de vendedor
 * Fecha de actualización:	2024-04-24
 * Desarrollador:	Wilson Belduma
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_vendedor.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
//require_once('../../Librerias/postclass.php');

/** 
 * Creaci�n del objeto para evitar el reenvio 
 */
//$thisPost = new Post_Block;
/** 
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);
/**
 * Creaci�n del Objeto para consultas
 */
$obBD_con1 =  new Class_Log_Datos_Pro;

/** 
 * Busqueda de los datos del vendedor
 */
if (isset($vendedorAjax)) {
	$data = filter_input_array(INPUT_GET);
	$data["Emp_Cod"] = $Ses_Emp_Cod;
	$data["Suc_Cod"] = $Ses_Suc_Cod;
	$contar = $obBD_con1->getRowConsulta(16, $data, $obBD_conexion);
	$pagination = pages($contar['total'], $page, $rows);
	$responce = $pagination['data'];
	$data["limits"] = $pagination['limits'];
	if ($contar['total'] > 0) {
		$responce['rows'] = $obBD_con1->getArrayConsulta(16, $data, $obBD_conexion);
	}
	utf8_encode_deep($responce['rows']);
	echo json_encode($responce);
	exit();
}

if (isset($hdd_save) && !isset($hdd_volver)) {
	$obBD_con1->operacionobBD(11, $Pun_Cod . "*" . $Vnd_Est  . "*" . $Prs_Cod . '*' . $Vnd_Cod, $obBD_conexion);
	$obBD_con1->operacionobBD(17, $Pun_Ubi . "*" . $Ses_Suc_Cod  . "*" . $Pun_Cod, $obBD_conexion);
	$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
	if ($obBD_con1->Error == 0) {
		$message = '<div class="alert alert-success" role="alert"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> Se actualizo correctamente.<button type="button" class="close" onclick="closeMessage()"> <span aria-hidden="true">&times;</span></button></div>';
	} else {
		$message = '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> Existe un error, vuelva a intentarlo.<button type="button" class="close" onclick="closeMessage()"> <span aria-hidden="true">&times;</span></button></div>';
	}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html;">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<link href="../../framework/jquery/bootstrap/bootstrap-fileinput/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
</head>

<body>
	<div class="panel panel-main exa-body" id="buscar_personal">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; Consultar Informaci&oacute;n del Personal</h3>
		</div>
		<div class="container-fluid">
			<div class="row">
				<div class="col-md-8">
					<div id="busca" class="panel-body">
						<div class="row">
							<div class="col-sm-12">
								<fieldset class="exa-fieldset">
									<legend class="Titulos2">Filtro de B&uacute;squeda</legend>
									<form id="formBuscar" name="formBuscar" class="form-horizontal normal" action="javascript:$('#list').Search('#formBuscar','vendedorAjax');">
										<div class="form-group">
											<label class="col-sm-3 control-label label-xs">Filtrar Por:</label>
											<div class="col-sm-9 radioset">
												<input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;</label>
												<input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" alt="" /><label for="rad_ba2">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
											</div>
										</div>

										
										<div class="form-group">
											<label class="col-sm-3 control-label">B&uacute;squeda:</label>
											<div class="col-sm-8">
												<div class="input-group">
													<input name="search" onkeydown="if (event.keyCode === 13)
                                                        this.form.submit()" type="text" size="50" maxlength="50" value="" placeholder="Ingrese empleado a buscar..." autofocus class="form-control input-sm" />
													<span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta"><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
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
									<legend class="Titulos2">Resultados de la B&uacute;squeda</legend>
									<table id="list"></table>
									<div id="listPager"></div>
								</fieldset>
							</div>
						</div>
					</div>
				</div>

				<div class="col-md-4">
					<div class="panel panel-main exa-body" id="panel_registrar_vendedor">
						<div class="panel-heading exa-header">
							<h3 class="panel-title">&raquo; Editar vendedor</h3>
						</div>
						<div class="panel-body">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">Editar Vendedor</legend>

								<form name="form2" id="form2" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>" onsubmit="return validarFormulario_vendedor()" style="padding:7px">
									<div class="message"><?php echo $message; ?></div>
									<input type="hidden" name="Pun_Cod" id="Pun_Cod">
									<input type="hidden" name="Vnd_Cod" id="Vnd_Cod">
									<div class="form-group">
										<label for="Prs_Ced" class="control-label label-sm required"> Cédula/R.U.C.:</label>
										<input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" readonly="" />
									</div>
									<div class="form-group">
										<label for="empleado" class="control-label label-sm required">Nombre.:</label>
										<input id="empleado" name="empleado" type="text" class="form-control input-xs" readonly="" />
									</div>
									<input type="hidden" id="Prs_Cod" name="Prs_Cod">
									<input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
									<div class="form-group">
										<label for="Pun_Descripcion" class="control-label label-sm required">Punto de Impresión:</label>
										<input type="text" name="Pun_Descripcion" id="Pun_Descripcion" class="form-control input-xs" value="Caja-Vendedores" readonly="">
									</div>
									<div class="form-group">
										<label for="Pun_Ubi" class="control-label label-sm required">Descripción:</label>
										<textarea name="Pun_Ubi" id="Pun_Ubi" cols="20" rows="5" class="form-control input-xs"> Pun_Ubi </textarea><br>
										<fieldset class="exa-fieldset">
											<div class="form-group">
												<input type="radio" name="Vnd_Est" value="A">Activo
												<input type="radio" name="Vnd_Est" value="I">Inactivo
											</div>
										</fieldset>
										<button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide()">
											<i class="fa fa-ban"></i>
											<span>&nbsp;&nbsp;Cancelar</span>
										</button>
										<button type="submit" class="btn btn-primary start" title="Guardar">
											<i class="fa fa-save"></i>
											<span>Actualizar</span>
										</button>
								</form>
							</fieldset>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
		$(function() {
			$("#list").jqGrid({
				url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
				mtype: "GET",
				datatype: "json",
				regional: 'es',
				responsive: true,
				postData: $("#formBuscar").getData("vendedorAjax"),
				autowidth: true,
				shrinkToFit: true,
				height: 300,
				cmTemplate: {
					sortable: false
				},
				colModel: [{
						label: 'C&oacute;digo',
						name: 'Prs_Cod',
						width: 30,
						align: "center"
					},
					{
						label: 'C&eacute;dula',
						name: 'Prs_Ced',
						width: 60,
						align: "center"
					},
					{
						label: 'Empleado',
						name: 'empleado',
						width: 140,
						align: "center"
					},
					{
						label: 'Detalle',
						name: 'Pun_Ubi',
						width: 40,
						align: "center"
					},
					{
						label: 'Estado',
						name: 'Vnd_Est',
						width: 40,
						align: "center"
					},
					{
						label: 'Pun Cod',
						name: 'Pun_Cod',
						width: 40,
						align: "center",
						hidden: true
					},
					{
						label: 'Vnd_Cod',
						name: 'Vnd_Cod',
						width: 40,
						align: "center",
						hidden: true
					},
					{
						label: '&nbsp;',
						name: 'act1',
						width: 30,
						align: 'center',
						viewable: false,
						formatter: function(cellvalue, options, rowObject) {
							return $.getGridButton(cargarEmpleado, rowObject);
						}
					}
				],
				rowNum: 10000,
				pager: "#listPager",
				gridview: false,
				rownumbers: false,
				viewrecords: true,
				pgbuttons: false,
				pgtext: null,
				altRows: true,
				altclass: "myAltRowClass"
			});
		});
	</script>
	<script type="text/javascript" src="../VALIDACIONES/fac_par_vendedor.js"></script>
</body>

</html>