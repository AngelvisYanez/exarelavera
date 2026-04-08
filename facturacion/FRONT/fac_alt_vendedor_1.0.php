<?php

/**
 * Descripción: Permite registrar alta de vendedor
 * Fecha de actualización:	21/04/2024
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
 * Busqueda de los datos del cliente 
 */
function compararFilas($a, $b, $Ses_Suc_Cod)
{
	// Verificar si $a cumple el requisito ($value['Pun_Des'] == 'Caja-Vendedores' && $value['Suc_Cod'] == $Ses_Suc_Cod)
	$aCumpleRequisito = ($a['Pun_Des'] == 'Caja-Vendedores' && $a['Suc_Cod'] == $Ses_Suc_Cod);
	$bCumpleRequisito = ($b['Pun_Des'] == 'Caja-Vendedores' && $b['Suc_Cod'] == $Ses_Suc_Cod);

	// Si ambos cumplen o ambos no cumplen, ordenar por Prs_Cod de forma ascendente
	if ($a['Prs_Cod'] == $b['Prs_Cod']) {
		// Si ambos cumplen la condición, ordenarlos por orden alfabético de nombres
		if ($aCumpleRequisito && $bCumpleRequisito) {
			return strcmp($a['Nombre'], $b['Nombre']);
		}
		// Si solo $a cumple la condición, colocar $a primero
		if ($aCumpleRequisito) {
			return -1;
		}
		// Si solo $b cumple la condición, colocar $b primero
		if ($bCumpleRequisito) {
			return 1;
		}
	}
	// Ordenar por Prs_Cod de forma ascendente
	return $a['Prs_Cod'] - $b['Prs_Cod'];
}


if (isset($personalAjax)) {
	$data = filter_input_array(INPUT_GET);
	$data["Emp_Cod"] = $Ses_Emp_Cod;
	$data["Suc_Cod"] = $Ses_Suc_Cod;
	$contar = $obBD_con1->getRowConsulta(13, $data, $obBD_conexion);
	$pagination = pages($contar['total'], $page, $rows);
	$responce = $pagination['data'];
	$data["limits"] = $pagination['limits'];
	if ($contar['total'] > 0) {
		$responce['rows'] = $obBD_con1->getArrayConsulta(13, $data, $obBD_conexion);
		usort($responce['rows'], function ($a, $b) use ($Ses_Suc_Cod) {
			// Llamar a la función compararFilas con $Ses_Suc_Cod
			return compararFilas($a, $b, $Ses_Suc_Cod);
		});

		$responce['rows_aux'] =  array();
		$prc_ced_aux = null;
		$contador = 0;
		foreach ($responce['rows'] as &$value) {
			if ($value['Prs_Ced'] != $prc_ced_aux  ||   ($value['Pun_Des'] == 'Caja-Vendedores' &&  $value['Suc_Cod'] == $Ses_Suc_Cod)) {
				//if ($cont > 0  ||  ($value['Pun_Des'] == 'Caja-Vendedores'   &&  $value['Suc_Cod'] == $Ses_Suc_Cod)) {
					$descomponer = explode('-', $value['Fec_Sys']);
					$descompone1 = explode('-', $value['Prs_Fec']);
					$Prs_Eda = $descomponer[0] - $descompone1[0];
					$value['Prs_Eda'] = $Prs_Eda;
					if (($value['Pun_Des'] == 'Caja-Vendedores') && $value['Suc_Cod'] == $Ses_Suc_Cod) {
						$value['Prs_es_vendedor'] = true;
					} else {
						$value['Prs_es_vendedor'] = false;
					}
					$responce['rows_aux'][] = $value;
				//}
				$cont++;
				$prc_ced_aux = $value['Prs_Ced'];
			}
		}
		unset($value);
	}
	utf8_encode_deep($responce['rows_aux']);
	echo json_encode($responce['rows_aux']);
	exit();
}



$message = "";
if (isset($hdd_save) && !isset($hdd_volver)) {
	//Consulta si existe un vendedor registrado en una sucursal
	$cantidad = $obBD_con1->getArrayConsulta(14,  $Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
	//Validar si el empleado ya existe que se vuelva a registrar
	if ($cantidad[0]["Cant_Vent"] < 1) {
		//Registrar punto de impresion
		$obBD_con1->operacionobBD(15, $Ses_Suc_Cod . "*" . $Pun_Descripcion . "*" . $Pun_Ubi, $obBD_conexion);
		$Pun_Cod  = $obBD_con1->insercionid($obBD_conexion);
		//Registrar vendedor
		$obBD_con1->operacionobBD(6, $Pun_Cod . "*" . $Prs_Cod, $obBD_conexion);
		$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
		//$message = '<div class="alert alert-success" role="alert"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> Se registro exitosamente.<button type="button" class="close" onclick="closeMessage()"> <span aria-hidden="true">&times;</span></button></div>';

		$_SESSION['message'] = '<div class="alert alert-success" role="alert"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> Se registro exitosamente.<button type="button" class="close" onclick="closeMessage()"> <span aria-hidden="true">&times;</span></button></div>';
	} else {
		//$message = '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> Este vendedor ya se encuentra registrado.<button type="button" class="close" onclick="closeMessage()"> <span aria-hidden="true">&times;</span></button></div>';
		$_SESSION['message'] = '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> Este vendedor ya se encuentra registrado.<button type="button" class="close" onclick="closeMessage()"> <span aria-hidden="true">&times;</span></button></div>';
	}
	header("Location: " . $_SERVER['PHP_SELF']);
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<title><?Php echo $Ses_Sys_Nom; ?></title>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<link href="../../framework/jquery/bootstrap/bootstrap-fileinput/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<meta http-equiv="Content-Type" content="text/html;">
	<style>
		.kv-avatar .file-preview-frame,
		.kv-avatar .file-preview-frame:hover {
			margin: 0;
			padding: 0;
			border: none;
			box-shadow: none;
			text-align: center;
		}

		.kv-avatar .file-input {
			display: table-cell;
			max-width: 220px;
		}

		.file-upload-indicator {
			display: none;
		}

		.file-footer-caption {
			margin: 0px;
		}

		.file-actions {
			display: none;
		}

		.swlFlyout_title {
			background-color: #439943;
			color: white;
		}

		.panel {
			margin-bottom: 1px;
		}

		.center-block {
			margin-bottom: 20px;
		}
	</style>
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
									<form id="formBuscar" name="formBuscar" class="form-horizontal normal" action="javascript:$('#list').Search('#formBuscar','personalAjax');">
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
								<fieldset class="exa-fieldset" id="tabla_datos_personal">
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
							<h3 class="panel-title">&raquo; Seleccionar un vendedor para registrar</h3>
						</div>
						<div class="panel-body">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">Registrar Vendedor</legend>
								<form name="form2" id="form2" method="post" action="<?php echo ($_SERVER['PHP_SELF']); ?>" onsubmit="return validarFormulario_vendedor()" style="padding: 20px;">
									<div class="message"><?php if (isset($_SESSION['message'])) {echo $_SESSION['message'];unset($_SESSION['message']);}   ?></div>
									<div class="form-group">
										<label for="Prs_Ced" class="control-label label-sm required">Cédula/R.U.C.:</label>
										<input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" readonly>
									</div>
									<div class="form-group">
										<label for="empleado" class="control-label label-sm required">Nombre:</label>
										<input id="empleado" name="empleado" type="text" class="form-control input-xs" readonly>
									</div>
									<input type="hidden" id="Prs_Cod" name="Prs_Cod">
									<input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
									<div class="form-group">
										<label for="Pun_Des" class="control-label label-sm required">Punto de Impresión:</label>
										<input type="text" name="Pun_Descripcion" id="Pun_Descripcion" class="form-control input-xs" value="Caja-Vendedores" readonly>
									</div>
									<div class="form-group">
										<label for="Pun_Ubi" class="control-label label-sm">Descripción (opcional):</label>
										<textarea name="Pun_Ubi" id="Pun_Ubi" cols="20" rows="5" class="form-control input-xs"></textarea>
									</div>
									<div class="form-group">
										<button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onclick="campos_hide()">
											<i class="fa fa-ban"></i> Cancelar
										</button>
										<button type="submit" class="btn btn-primary" title="Guardar">
											<i class="fa fa-save"></i> Guardar
										</button>
									</div>
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
				url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
				mtype: "GET",
				datatype: "json",
				regional: 'es',
				responsive: true,
				postData: $("#formBuscar").getData("personalAjax"),
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
						label: 'Edad',
						name: 'Prs_Eda',
						width: 40,
						align: "center"
					},
					{
						label: 'G&eacute;nero',
						name: 'Prs_Gen',
						width: 50,
						align: "center"
					},
					/*{
						label: 'T&iacute;tulo',
						name: 'Per_Ti1',
						width: 50,
						align: "center"
					},*/
					{
						label: 'Ciudad',
						name: 'Ciu_Des',
						width: 70,
						align: "center"
					},
					{
						label: 'Sucursal',
						name: 'Suc_Cod',
						width: 70,
						align: "center"
					},
					{
						label: 'Vendedor',
						name: 'Prs_es_vendedor',
						width: 35,
						align: "center",
						formatter: 'truefalse',
						formatoptions: {
							yesMsg: 'Registrado como vendedor',
							noMsg: ' '
						},
						title: false
					},
					{
						label: 'Estado',
						name: 'Per_Est',
						width: 25,
						align: "center",
						formatter: 'truefalse'
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