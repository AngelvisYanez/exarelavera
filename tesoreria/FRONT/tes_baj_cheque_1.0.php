<?php

/**
 * @abstract Permite anular los cheques
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de creacion  2012-07-19
 * Fecha de actualizacion  2026-08-05
 * @author Wilson Belduma
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');


/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/** 
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Che;
/**
 * Evita el reenvio 
 */
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");
$periodo = "";
$anulada = 0;
$fila = 0;
$asientos = "";
/**
 * Tope de filas del listado, para no generar una pagina inmanejable
 * en periodos con mucho movimiento (hay periodos con mas de 4.500 comprobantes)
 */
$max_filas = 500;
$truncado = 0;
$buscado = false;

/**
 * Detalle contable del comprobante (ventana modal)
 */
if (isset($ajax)) {
	$com_codigo = $ComCod;
	include('../COMPONENTES/tesComDetalleCompr.php');
	exit();
}
/**
 * Anulacion del cheque y de su comprobante
 */
if (isset($anula)) {
	$responce = array('success' => false, 'message' => '');
	/**
	 * Si el cheque proviene de un anticipo a proveedores (A/U/C),
	 * no se permite anularlo desde este modulo.
	 */
	$row_anticipo = $obBD_con1->getRowConsulta(397, $asi_cod, $obBD_conexion);
	if (isset($row_anticipo['Atp_Cod']) && $row_anticipo['Atp_Cod'] != '') {
		$est_atp = isset($row_anticipo['Atp_Est']) ? $row_anticipo['Atp_Est'] : '';
		if ($est_atp === 'C') {
			$responce['message'] = 'Este cheque pertenece al anticipo a proveedores <b>' .
				$row_anticipo['codigo_compro'] . '</b> (c&oacute;d. ' . $row_anticipo['Atp_Cod'] .
				'), que se encuentra <b>consumido</b>. No se puede anular el cheque.';
		} else {
			$responce['message'] = 'Este cheque pertenece al anticipo a proveedores <b>' .
				$row_anticipo['codigo_compro'] . '</b> (c&oacute;d. ' . $row_anticipo['Atp_Cod'] .
				'). Debe <b>anular primero el anticipo</b> desde Modificar Anticipos a Proveedores.';
		}
		$responce['anticipo'] = true;
		$responce['atp_est'] = $est_atp;
		echo json_encode($responce);
		exit();
	}

	$row_rs_compro = $obBD_con1->getRowConsulta(367, $asi_cod, $obBD_conexion);
	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
	$obBD_con1->grabarv_registros(sentencias_che(346, $obBD_con1->parametros($asi_cod)), $obBD_conexion->conexion);
	$obBD_con1->grabarv_registros(sentencias_che(366, $obBD_con1->parametros($row_rs_compro['Com_Cod'])), $obBD_conexion->conexion);
	$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
	if ($obBD_con1->Error == 0) $responce['success'] = true;
	else $responce['success'] = false;
	$responce['message'] = $obBD_con1->MsgError;
	echo json_encode($responce);
	exit();
}
/**
 * Guardado del cheque 
 */
//if ($thisPost->postBlock($_POST['postID']))
{
	if (isset($bt_save) && !isset($hdd_volver)) {
		/**
		 * Inicio de la transaccion
		 */
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);

		foreach ($datos as $puntero => $item) {
			$cant++;
			$param[] = $item;
			if ($cant == 10) {
				$cant = 0;
				/**
				 * Anulacion de cheques 
				 */
				$obBD_con1->grabarv_registros(sentencias_che(191, $obBD_con1->parametros($param[3] . '*' . $param[0] . '*' . $param[1] . '*' . $param[9])), $obBD_conexion->conexion);
				unset($param);
			}
		}
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	} //Fin del if (isset($bt_save))
} //fin del POSTH

/**
 * Periodos contables activos. Al ingresar desde el menu no llega el periodo,
 * por lo que se asume el mas reciente y se muestra directamente el formulario de filtros.
 */
$row_rs_periodos = $obBD_con1->getArrayConsulta(214, $Ses_Emp_Cod, $obBD_conexion);
$total_rs_periodos = count($row_rs_periodos);
/**
 * Ordena del anio mayor al menor, asi el periodo mas reciente queda como opcion inicial
 */
if ($total_rs_periodos > 0) {
	usort($row_rs_periodos, function ($a, $b) {
		return strcmp($b['Pec_Fei'], $a['Pec_Fei']);
	});
}
$periodo_actual = ($total_rs_periodos > 0) ? $row_rs_periodos[0] : array();

if (!isset($Pec_Cod) || trim($Pec_Cod) == "") {
	$Pec_Cod = isset($periodo_actual['Pec_Cod']) ? $periodo_actual['Pec_Cod'] : "";
}
/**
 * Divide la cadena del periodo contable 
 */
$arreglo = explode("*", $Pec_Cod);
$Pec_Cod = $arreglo[0];

if ($total_rs_periodos > 0) {
	/**
	 * Consulta del periodo contable 
	 */
	$rs_periodo = $obBD_con1->consulta(sentencias_che(113, $obBD_con1->parametros($Pec_Cod)), $obBD_conexion->conexion);
	$row_rs_periodo = $obBD_con1->registros();
	$total_rs_periodo = $obBD_con1->numregistros();

	/**
	 * Descripcion del periodo contable 
	 */
	$periodo = "&mdash; Periodo contable " . substr($row_rs_periodo['Pec_Fei'], 0, 4);

	/**
	 * Cargado de los datos de la cabecera 
	 */
	if (isset($codigo)) {
		/**
		 * Consulta los datos del comprobante 
		 */
		$rs_cabcomp = $obBD_con1->consulta(sentencias_che(149, $obBD_con1->parametros('proveedore' . '*' . $codigo . '*' . 'D' . '*' .
			$Pec_Cod . '*' . 'Prv_Cod')), $obBD_conexion->conexion);
		$row_rs_cabcomp = $obBD_con1->registros();
		$total_rs_cabcomp = $obBD_con1->numregistros();
	} else {
		if (!isset($txt_busqueda)) $txt_busqueda = "";
		if (!isset($op_opciones)) $op_opciones = "d";
		if (!isset($bancos)) $bancos = "";
		/**
		 * La consulta se ejecuta unicamente cuando el usuario presiona Buscar,
		 * al abrir la pantalla solo se muestran los filtros.
		 */
		$buscado = isset($bt_buscar);

		if ($buscado) {
			/**
			 * Busqueda de comprobantes de egreso. Sin texto se listan todos los del periodo.
			 * El ultimo parametro ('S') incluye tambien los comprobantes anulados.
			 */
			$rs_cabcompr = $obBD_con1->getArrayConsulta(345, trim($txt_busqueda) . '*' . $Ses_Emp_Cod . '*' . $Pec_Cod . '*' .
				$op_opciones . '*' . $bancos . '*' . 'S', $obBD_conexion);
			$total_rs_cabcompr = count($rs_cabcompr);

			if ($total_rs_cabcompr > $max_filas) {
				$truncado = $total_rs_cabcompr;
				$row_rs_cabcompr = array_slice($rs_cabcompr, 0, $max_filas);
			} else {
				$row_rs_cabcompr = $rs_cabcompr;
			}
		} //Fin del if ($buscado)
		else {
			$total_rs_cabcompr = 0;
			$row_rs_cabcompr = array();
		}
	} //FIn del else if (isset($codigo)) 
} //Fin del if ($total_rs_periodos > 0)
?>
<!DOCTYPE html>
<HTML>

<HEAD>
	<TITLE><?php echo "Cheques Anular [EXA]"; ?></TITLE>
	<meta charset="UTF-8">
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/bootstrap/info.tabs.css" />
	<style>
		.table-exa {
			margin-bottom: 0;
			background: #fff;
		}

		.table-exa>thead>tr>th {
			background-color: #254463;
			color: #fff;
			font-weight: 600;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: .3px;
			border-bottom: 0;
			vertical-align: middle;
			white-space: nowrap;
		}

		.table-exa>tbody>tr>td,
		.table-exa>tfoot>tr>th {
			font-size: 12px;
			padding: 4px 6px;
			vertical-align: middle;
		}

		.table-exa>tbody>tr.row-anulada>td {
			color: #a94442;
			background-color: #fdf0f0;
			text-decoration: line-through;
		}

		.table-exa>tbody>tr.row-anulada>td.no-line {
			text-decoration: none;
		}

		.static-value {
			margin: 0;
			padding-top: 5px;
			font-weight: 600;
			color: #254463;
			font-size: 12px;
			word-break: break-word;
		}

		.leyenda-exa {
			font-size: 11px;
			color: #555;
			padding-top: 4px;
		}

		.leyenda-exa .box {
			display: inline-block;
			width: 26px;
			height: 12px;
			border: 1px solid #bbb;
			vertical-align: middle;
			margin-right: 4px;
		}

		.leyenda-exa .box-anulado {
			background-color: #fdf0f0;
			border-color: #a94442;
		}

		.radioset .ui-button .ui-button-text {
			font-size: 12px;
			padding: 3px 10px;
		}

		.detalle-compr dt {
			width: 120px;
			color: #254463;
		}

		.detalle-compr dd {
			margin-left: 135px;
			font-size: 12px;
		}

		#detalleDialog {
			font-size: 12px;
		}
	</style>
</HEAD>

<BODY>
	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; Anular Cheques <small style="color:#cddcf0;"><?php echo $periodo; ?></small></h3>
		</div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<?php
			if ($total_rs_periodos == 0) {
				echo error_alerta("La empresa no tiene periodos contables activos, no es posible localizar comprobantes de egreso.", 2, true);
			} //Fin del if ($total_rs_periodos == 0)
			else {
			?>
				<?php
				if (!isset($codigo)) {
					/**
					 * PASO 1: Busqueda de comprobantes de egreso
					 */
				?>
					<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form1" id="form1" class="form-horizontal normal" onsubmit="return mostrarLoaderForm(this, '#form1 button[type=submit]')">
						<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>" />
						<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>" />
						<input name="cmb_mes" id="cmb_mes" type="hidden" value="<?php echo $cmb_mes; ?>" />
						<input name="bt_buscar" type="hidden" value="1" />
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">Buscar Comprobante de Egreso</legend>
							<div class="row">
								<div class="col-sm-8 col-md-7">
									<div class="form-group">
										<label class="col-sm-3 control-label label-sm required" for="Pec_Cod">Periodo:</label>
										<div class="col-sm-3">
											<select name="Pec_Cod" id="Pec_Cod" class="form-control input-sm" style="text-align: center;" required onchange="asignar_fechas(this.value)">
												<?php
												if (count($row_rs_periodos) > 0) {
													foreach ($row_rs_periodos as $row) {
												?>
														<option <?php if ($row['Pec_Cod'] == $Pec_Cod) echo "selected"; ?> value="<?php echo $row['Pec_Cod'] . '*' . $row['Pec_Fei'] . '*' . $row['Pec_Fef']; ?>"><?php echo $row['Periodo']; ?></option>
													<?php
													} //Fin del foreach ($row_rs_periodos as $row)
												} //Fin del if (count($row_rs_periodos) > 0)
												else { ?>
													<option value="">&lt;&lt; SIN PERIODOS &gt;&gt;</option>
												<?php
												} //Fin del else if (count($row_rs_periodos) > 0)
												?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-3 control-label label-xs">Filtrar Por:</label>
										<div class="col-sm-9 radioset opt_search">
											<input id="radsc1" name="op_opciones" type="radio" value="d" <?php if (!isset($op_opciones) || $op_opciones == "d") echo 'checked=""'; ?> onclick="setfocusBusqueda()" alt="" />
											<label for="radsc1">Apellidos</label>
											<input id="radsc2" name="op_opciones" type="radio" value="n" <?php if (isset($op_opciones) && $op_opciones == "n") echo 'checked=""'; ?> onclick="setfocusBusqueda()" alt="" />
											<label for="radsc2">No. de Cheque</label>
											<input id="radsc3" name="op_opciones" type="radio" value="r" <?php if (isset($op_opciones) && $op_opciones == "r") echo 'checked=""'; ?> onclick="setfocusBusqueda()" alt="" />
											<label for="radsc3">No. de Comprobante</label>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-3 control-label label-sm" for="bancos">Banco:</label>
										<div class="col-sm-9">
											<select name="bancos" id="bancos" class="form-control input-sm">
												<option value="">&lt;&lt; TODOS &gt;&gt;</option>
												<?php
												$rs_bancos = $obBD_con1->getArrayConsulta(339, $Ses_Emp_Cod, $obBD_conexion);
												if (count($rs_bancos) > 0) {
													foreach ($rs_bancos as $row) {
												?>
														<option <?php if ($bancos == $row['Pld_Cod']) echo "selected"; ?> value="<?php echo $row['Pld_Cod']; ?>"><?php echo $row['Pld_Des'] . " (Cta.#: " . $row['Ban_Cue'] . ")"; ?></option>
												<?php
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-3 control-label label-sm" for="txt_busqueda">B&uacute;squeda:</label>
										<div class="col-sm-9">
											<div class="input-group input-group-sm">
												<input name="txt_busqueda" type="text" id="txt_busqueda" class="form-control input-sm" value="<?php echo $txt_busqueda; ?>" maxlength="50" placeholder="Vac&iacute;o = todos los comprobantes del periodo" autofocus />
												<span class="input-group-btn">
													<button type="submit" class="btn btn-success btn-sm" title="Buscar">
														<span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
													</button>
												</span>
											</div>
										</div>
									</div>
								</div>
							</div>
						</fieldset>
					</form>

					<?php if (!$buscado) { ?>
						<?php echo error_alerta("Defina los filtros y presione <strong>Buscar</strong> para listar los comprobantes de egreso del periodo.", 1, true); ?>
					<?php } //Fin del if (!$buscado)
					else { ?>
					<fieldset class="exa-fieldset">
						<legend class="Titulos2"><?php echo trim($txt_busqueda) != "" ? 'Resultados de la B&uacute;squeda' : 'Comprobantes del Periodo'; ?> <span class="badge" style="background-color:#254463;"><?php echo (int)$total_rs_cabcompr; ?></span></legend>
						<?php
							if ($truncado > 0) {
								echo error_alerta("Se muestran los primeros <strong>" . $max_filas . "</strong> de <strong>" . $truncado . "</strong> comprobantes. Refine la b&uacute;squeda o filtre por banco para ver el resto.", 2, true);
							}
							if ($total_rs_cabcompr > 0) { ?>
								<div class="table-responsive">
									<table class="table table-condensed table-hover table-exa">
										<thead>
											<tr>
												<th width="6%" class="text-center">Cod. Int.</th>
												<th width="6%" class="text-center">No. Ch.</th>
												<th width="9%" class="text-center">Generaci&oacute;n</th>
												<th width="11%" class="text-center">No. Compr.</th>
												<th width="10%">C&eacute;dula/R.U.C.</th>
												<th width="28%">Proveedor</th>
												<th width="9%" class="text-center">Fecha</th>
												<th width="9%" class="text-right">Valor</th>
												<th width="6%" class="text-center">&nbsp;</th>
												<th width="6%" class="text-center">&nbsp;</th>
											</tr>
										</thead>
										<tbody>
											<?php
											foreach ($row_rs_cabcompr as $row) {
												$anulado = ($row['Com_Est'] != 'A');
												if ($anulado) $anulada++;
												$fecha_compr = explode('-', $row['Com_Fec']);
											?>
												<tr<?php echo $anulado ? ' class="row-anulada"' : ''; ?>>
													<td class="text-center"><?php echo $row['Com_Cod']; ?></td>
													<td class="text-center"><?php echo $row['Che_Num']; ?></td>
													<td class="text-center"><?php echo ($row['Com_Gen'] == 'M') ? 'Manual' : 'Autom&aacute;tico'; ?></td>
													<td class="text-center"><?php echo $row['Tia_Abr'] . '-' . $fecha_compr[1] . '-' . $row['Com_Num']; ?></td>
													<td><?php echo $row['Prs_Ced']; ?></td>
													<td><?php echo $row['Prs_Ape'] . " " . $row['Prs_Nom']; ?></td>
													<td class="text-center"><?php echo $row['Com_Fec']; ?></td>
													<td class="text-right"><?php echo number_format($row['Com_Val'], 2); ?></td>
													<td class="text-center no-line">
														<button type="button" class="btn btn-info btn-xs" title="Ver detalle del comprobante" onclick="verDetalle('<?php echo $row['Com_Cod']; ?>')">
															<span class="glyphicon glyphicon-info-sign"></span>
														</button>
													</td>
													<td class="text-center no-line">
														<?php if (!$anulado) { ?>
															<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" style="margin:0;" onsubmit="return mostrarLoaderForm(this, '.btn-elegir-compr')">
																<input name="codigo" type="hidden" value="<?php echo $row['Com_Cod']; ?>" />
																<input name="Pec_Cod" type="hidden" value="<?php echo $Pec_Cod; ?>" />
																<input name="volver_busqueda" type="hidden" value="<?php echo $txt_busqueda; ?>" />
																<input name="volver_bancos" type="hidden" value="<?php echo $bancos; ?>" />
																<input name="volver_opciones" type="hidden" value="<?php echo $op_opciones; ?>" />
																<input name="volver_mes" type="hidden" value="<?php echo $cmb_mes; ?>" />
																<button type="submit" class="btn btn-success btn-xs btn-elegir-compr" title="Elegir comprobante">
																	<span class="glyphicon glyphicon-arrow-right"></span>
																</button>
															</form>
														<?php } else { ?>
															<span class="label label-danger">Anulado</span>
														<?php } ?>
													</td>
												</tr>
											<?php } //Fin del foreach ($row_rs_cabcompr as $row) 
											?>
										</tbody>
									</table>
								</div>
								<div class="leyenda-exa" <?php echo $anulada > 0 ? '' : 'style="display:none;"'; ?>><span class="box box-anulado"></span> Registro anulado</div>
							<?php } else {
								if (trim($txt_busqueda) != "") {
									echo error_alerta("No se encontraron comprobantes con el criterio de b&uacute;squeda ingresado.", 1, true);
								} else {
									echo error_alerta("El periodo contable seleccionado no registra comprobantes de egreso con cheques.", 1, true);
								}
							} //Fin del else if ($total_rs_cabcompr > 0) 
							?>
					</fieldset>
					<?php } //Fin del else if (!$buscado) ?>
				<?php
				} //Fin del if (!isset($codigo))
				elseif ($total_rs_cabcomp > 0) {
					/**
					 * PASO 2: Detalle del comprobante y anulacion de sus cheques
					 */
					/**
					 * Consulta las cuentas de un comprobante de egreso 
					 */
					$rs_cuentas = $obBD_con1->consulta(sentencias_che(306, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);
					$row_rs_cuentas = $obBD_con1->registros();
					$total_rs_cuentas = $obBD_con1->numregistros();
					/**
					 * Consulta los bancos 
					 */
					$rs_combo = $obBD_con1->consulta(sentencias_che(304, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);
					$row_rs_combo = $obBD_con1->registros();
					$total_rs_combo = $obBD_con1->numregistros();

					$fecha_compr = explode('-', $row_rs_cabcomp['Com_Fec']);
				?>
					<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form2" id="form2" class="form-horizontal normal">
						<?php
						/**
						 * Creacion del campo REPOST
						 */
						$thisPost->startPost();
						?>
						<input name="Pec_Cod" type="hidden" value="<?php echo $Pec_Cod; ?>" />
						<input name="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>" />
						<input name="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>" />
						<input name="codigo" type="hidden" value="<?php echo $codigo; ?>" />
						<input name="cantmodal" type="hidden" value="2" />

						<fieldset class="exa-fieldset">
							<legend class="Titulos2">Datos del Comprobante</legend>
							<div class="row">
								<div class="col-sm-6">
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm">C&oacute;d. Compr:</label>
										<div class="col-sm-8">
											<p class="static-value"><?php echo $row_rs_cabcomp['Tia_Abr'] . '-' . $fecha_compr[1] . '-' . $row_rs_cabcomp['Com_Num']; ?></p>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm">Nombre:</label>
										<div class="col-sm-8">
											<p class="static-value"><?php echo $row_rs_cabcomp['Prs_Ape'] . ' ' . $row_rs_cabcomp['Prs_Nom']; ?></p>
										</div>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm">Fecha:</label>
										<div class="col-sm-8">
											<p class="static-value"><?php echo $row_rs_cabcomp['Com_Fec']; ?></p>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm">Valor:</label>
										<div class="col-sm-8">
											<p class="static-value">$ <?php echo number_format($row_rs_cabcomp['Com_Val'], 2); ?></p>
										</div>
									</div>
								</div>
								<div class="col-sm-12">
									<div class="form-group">
										<label class="col-sm-2 control-label label-sm">Concepto:</label>
										<div class="col-sm-10">
											<p class="static-value"><?php echo trim($row_rs_cabcomp['Com_Con']) != '' ? $row_rs_cabcomp['Com_Con'] : '&mdash;'; ?></p>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label label-sm">Observaci&oacute;n:</label>
										<div class="col-sm-10">
											<p class="static-value"><?php echo trim($row_rs_cabcomp['Com_Obs']) != '' ? $row_rs_cabcomp['Com_Obs'] : '&mdash;'; ?></p>
										</div>
									</div>
								</div>
							</div>
						</fieldset>

						<fieldset class="exa-fieldset">
							<legend class="Titulos2">Cuentas <span class="badge" style="background-color:#254463;"><?php echo (int)$total_rs_cuentas; ?></span></legend>
							<div class="table-responsive">
								<table class="table table-condensed table-hover table-exa">
									<thead>
										<tr>
											<th width="12%">C&oacute;digo</th>
											<th>Descripci&oacute;n</th>
											<th>Glosa</th>
											<th width="11%" class="text-right">Debe</th>
											<th width="11%" class="text-right">Haber</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$total = 0;
										if ($total_rs_cuentas > 0) {
											do {
												$es_debe = ($row_rs_cuentas['Asi_Deh'] == 'D');
												if ($es_debe) $total = $total + $row_rs_cuentas['Asi_Val'];
										?>
												<tr>
													<td><?php echo $row_rs_cuentas['Pld_Cdc']; ?></td>
													<td><?php echo $row_rs_cuentas['Pld_Des']; ?></td>
													<td><?php echo $row_rs_cuentas['Asi_Glo']; ?></td>
													<td class="text-right"><?php echo $es_debe ? number_format($row_rs_cuentas['Asi_Val'], 2) : '&nbsp;'; ?></td>
													<td class="text-right"><?php echo $es_debe ? '&nbsp;' : number_format($row_rs_cuentas['Asi_Val'], 2); ?></td>
												</tr>
										<?php
											} while ($row_rs_cuentas = $obBD_con1->fetch_assoc($rs_cuentas));
										} //Fin del if ($total_rs_cuentas > 0)
										?>
									</tbody>
									<tfoot>
										<tr class="active">
											<th colspan="3" class="text-right">TOTALES</th>
											<th class="text-right"><?php echo number_format($total, 2); ?></th>
											<th class="text-right"><?php echo number_format($total, 2); ?></th>
										</tr>
									</tfoot>
								</table>
							</div>
						</fieldset>

						<?php
						/**
						 * Cargado de los cheques del comprobante 
						 */
						$rs_carcheq = $obBD_con1->consulta(
							sentencias_che(309, $obBD_con1->parametros($row_rs_cabcomp['Com_Cod'])),
							$obBD_conexion->conexion
						);
						$row_rs_carcheq = $obBD_con1->registros();
						$total_rs_carcheq = $obBD_con1->numregistros();
						/**
						 * Cargado del array que contiene lo valores maximos de los cheques x asiento.
						 */
						$rs_arrmax = $obBD_con1->consulta(sentencias_che(304, $obBD_con1->parametros($row_rs_cabcomp['Com_Cod'])), $obBD_conexion->conexion);
						$row_rs_arrmax = $obBD_con1->registros();
						$total_rs_arrmax = $obBD_con1->numregistros();
						/**
						 * Creacion del Array para luego ser procesado
						 */
						if ($total_rs_arrmax > 0) {
							do {
								$codigo_array = explode("*", $row_rs_arrmax['Banasi']);
								$asientos = $asientos . '*' . $codigo_array[1];
							} while ($row_rs_arrmax = $obBD_con1->fetch_assoc($rs_arrmax));
						}
						$anulada = 0;
						?>
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">Cheques Emitidos <span class="badge" style="background-color:#254463;"><?php echo (int)$total_rs_carcheq; ?></span></legend>
							<div class="table-responsive">
								<table class="table table-condensed table-hover table-exa">
									<thead>
										<tr>
											<th width="22%">Banco</th>
											<th width="20%">Proveedor</th>
											<th width="8%" class="text-center">No. Ch.</th>
											<th width="9%" class="text-right">Valor</th>
											<th width="9%" class="text-center">Fec. Elab.</th>
											<th width="9%" class="text-center">Fec. Cobro</th>
											<th width="12%">Observaci&oacute;n</th>
											<th width="11%" class="text-center">&nbsp;</th>
										</tr>
									</thead>
									<tbody id="contenido">
										<?php
										$total = 0;
										if ($total_rs_carcheq > 0) {
											do {
												$anulado = ($row_rs_carcheq['Che_Est'] == 'I');
												if ($anulado) $anulada++;
												$fila++;
												$total = $total + $row_rs_carcheq['Che_Val'];
										?>
												<tr class="fila-cheque<?php echo $anulado ? ' row-anulada' : ''; ?>">
													<td>
														<input name="datos[<?php echo $fila; ?>,1]" type="hidden" value="<?php echo $row_rs_carcheq['Ban_Cod']; ?>" />
														<input name="datos[<?php echo $fila; ?>,2]" type="hidden" value="<?php echo $row_rs_carcheq['Asi_Cod']; ?>" />
														<input name="datos[<?php echo $fila; ?>,10]" type="hidden" value="<?php echo $row_rs_carcheq['Ban_Cod']; ?>" />
														<?php echo $row_rs_carcheq['Pld_Des']; ?>
													</td>
													<td>
														<input name="datos[<?php echo $fila; ?>,3]" type="hidden" value="<?php echo $row_rs_carcheq['Prv_Cod']; ?>" />
														<?php echo $row_rs_carcheq['Prs_Ape'] . ' ' . $row_rs_carcheq['Prs_Nom']; ?>
													</td>
													<td class="text-center">
														<input name="datos[<?php echo $fila; ?>,4]" type="hidden" value="<?php echo $row_rs_carcheq['Che_Num']; ?>" />
														<?php echo $row_rs_carcheq['Che_Num']; ?>
													</td>
													<td class="text-right">
														<input name="datos[<?php echo $fila; ?>,5]" type="hidden" value="<?php echo round($row_rs_carcheq['Che_Val'], 2); ?>" />
														<?php echo number_format($row_rs_carcheq['Che_Val'], 2); ?>
													</td>
													<td class="text-center">
														<input name="datos[<?php echo $fila; ?>,8]" type="hidden" value="<?php echo ($row_rs_carcheq['Che_Fec'] != 0) ? $row_rs_carcheq['Che_Fec'] : ''; ?>" />
														<?php echo ($row_rs_carcheq['Che_Fec'] != 0) ? $row_rs_carcheq['Che_Fec'] : '&mdash;'; ?>
													</td>
													<td class="text-center">
														<input name="datos[<?php echo $fila; ?>,6]" type="hidden" value="<?php echo ($row_rs_carcheq['Che_Cob'] != 0) ? $row_rs_carcheq['Che_Cob'] : ''; ?>" />
														<?php echo ($row_rs_carcheq['Che_Cob'] != 0) ? $row_rs_carcheq['Che_Cob'] : '&mdash;'; ?>
													</td>
													<td>
														<input name="datos[<?php echo $fila; ?>,7]" type="hidden" value="<?php echo $row_rs_carcheq['Che_Obs']; ?>" />
														<input name="datos[<?php echo $fila; ?>,9]" type="hidden" value="<?php echo $row_rs_carcheq['Che_Cod']; ?>" />
														<?php
														echo trim($row_rs_carcheq['Che_Obs']) != '' ? $row_rs_carcheq['Che_Obs'] : '&mdash;';
														if (!empty($row_rs_carcheq['Atp_Cod'])) {
															$lbl_atp = (!empty($row_rs_carcheq['Atp_Est']) && $row_rs_carcheq['Atp_Est'] === 'C') ? 'Anticipo consumido' : 'Anticipo';
															echo ' <span class="label label-warning" title="Anticipo a proveedores #' . $row_rs_carcheq['Atp_Cod'] . '">' . $lbl_atp . '</span>';
														}
														?>
													</td>
													<td class="text-center no-line anulaChe" id="<?php echo $row_rs_carcheq['Asi_Cod'] . '-CH' . $row_rs_carcheq['Che_Cod']; ?>">
														<?php if ($row_rs_carcheq['Che_Est'] == 'A') {
															$atp_est_js = !empty($row_rs_carcheq['Atp_Est']) ? "'" . $row_rs_carcheq['Atp_Est'] . "'" : 'null';
															$tit_anula = !empty($row_rs_carcheq['Atp_Cod'])
																? ((!empty($row_rs_carcheq['Atp_Est']) && $row_rs_carcheq['Atp_Est'] === 'C')
																	? 'No se puede anular: anticipo consumido'
																	: 'Anular (requiere anular anticipo primero)')
																: 'Anular Cheque';
														?>
															<button type="button" class="btn btn-danger btn-xs" title="<?php echo $tit_anula; ?>" onclick="anulaCheque('<?php echo $row_rs_carcheq['Asi_Cod']; ?>','<?php echo $row_rs_carcheq['Che_Cod']; ?>',<?php echo !empty($row_rs_carcheq['Atp_Cod']) ? "'" . $row_rs_carcheq['Atp_Cod'] . "'" : 'null'; ?>,<?php echo $atp_est_js; ?>)">
																<span class="glyphicon glyphicon-ban-circle"></span> <span>Anular</span>
															</button>
														<?php } else { ?>
															<span class="label label-danger">Anulado</span>
														<?php } ?>
													</td>
												</tr>
										<?php
											} while ($row_rs_carcheq = $obBD_con1->fetch_assoc($rs_carcheq));
										} //Fin del if ($total_rs_carcheq > 0)
										?>
									</tbody>
									<tfoot>
										<tr class="active">
											<th colspan="3" class="text-right">TOTAL</th>
											<th class="text-right"><?php echo number_format($total, 2); ?></th>
											<th colspan="4">&nbsp;</th>
										</tr>
									</tfoot>
								</table>
							</div>
							<input name="txt_total" type="hidden" id="txt_total" value="<?php echo number_format($total, 2, '.', ''); ?>" />
							<input id="nfilas" name="nfilas" type="hidden" value="<?php echo $fila; ?>" />
							<input id="asientos" name="asientos" type="hidden" value="<?php echo $asientos; ?>" />
							<input id="bt_save" name="bt_save" type="hidden" value="Grabar" />
							<div id="leyendaAnulado" class="leyenda-exa" <?php echo $anulada > 0 ? '' : 'style="display:none;"'; ?>><span class="box box-anulado"></span> Cheque anulado</div>
						</fieldset>
					</form>

					<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="formVolver" id="formVolver" class="text-center">
						<input name="bt_buscar" type="hidden" value="1" />
						<input name="Pec_Cod" type="hidden" value="<?php echo $Pec_Cod; ?>" />
						<input name="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>" />
						<input name="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>" />
						<input name="txt_busqueda" type="hidden" value="<?php echo $volver_busqueda; ?>" />
						<input name="op_opciones" type="hidden" value="<?php echo $volver_opciones; ?>" />
						<input name="cmb_mes" type="hidden" value="<?php echo $volver_mes; ?>" />
						<input name="bancos" type="hidden" value="<?php echo $volver_bancos; ?>" />
						<button type="submit" class="btn btn-inverse btn-sm" title="Regresar a los resultados">
							<span class="glyphicon glyphicon-arrow-left"></span> <span>Atr&aacute;s</span>
						</button>
					</form>
				<?php
				} //Fin del elseif ($total_rs_cabcomp > 0)
				else {
					echo error_alerta("No se pudo cargar el comprobante seleccionado.", 2, true);
				}
				?>
			<?php
			} //FIn else
			?>
		</div>
	</div>

	<div id="detalleDialog" title="Detalle del Comprobante" style="display:none;"></div>

	<script type="text/javascript">
		/**
		 * Sincroniza las fechas del periodo contable seleccionado
		 */
		function asignar_fechas(valor) {
			var datos = valor.split('*');
			if (document.getElementById('Pec_Fei')) document.getElementById('Pec_Fei').value = datos[1];
			if (document.getElementById('Pec_Fef')) document.getElementById('Pec_Fef').value = datos[2];
		}

		function setfocusBusqueda() {
			var campo = document.getElementById('txt_busqueda');
			if (campo) campo.focus();
		}

		/**
		 * Muestra el loader y bloquea reenvios del formulario
		 */
		function mostrarLoaderForm(form, botones) {
			if (form.getAttribute('data-enviando') === '1') return false;
			form.setAttribute('data-enviando', '1');
			if (botones) $(botones).prop('disabled', true);
			$('#loader').show();
			return true;
		}

		/**
		 * Carga el detalle contable del comprobante en la ventana modal
		 */
		function verDetalle(comCod) {
			var dialog = $('#detalleDialog');
			dialog.html('<div class="text-center" style="padding:30px;"><i class="fa fa-spinner fa-spin fa-2x"></i></div>').dialog('open');
			dialog.load('<?php echo $_SERVER['PHP_SELF']; ?>?ajax=true&ComCod=' + comCod);
		}

		/**
		 * Anula el cheque junto con su comprobante de egreso.
		 * Si proviene de un anticipo A/U/C, se bloquea la anulacion.
		 */
		function anulaCheque(asi, che, atp, atpEst) {
			if (atp) {
				if (atpEst === 'C') {
					$.alert('Este cheque pertenece al anticipo a proveedores <b>#' + atp + '</b>, que se encuentra <b>consumido</b>. No se puede anular el cheque.', null, 'remove');
				} else {
					$.alert('Este cheque pertenece al anticipo a proveedores <b>#' + atp + '</b>. Debe <b>anular primero el anticipo</b> desde Modificar Anticipos a Proveedores.', null, 'remove');
				}
				return;
			}
			$.createDialogConfirm('Est&aacute; seguro que desea <b>anular</b> este cheque y su comprobante?', null, function() {
				$.saveDataJson('<?php echo $_SERVER['PHP_SELF']; ?>', {
					anula: true,
					asi_cod: asi,
					che_cod: che
				}, function() {
					$('.fila-cheque').addClass('row-anulada');
					$('.anulaChe').html('<span class="label label-danger">Anulado</span>');
					$('#leyendaAnulado').show();
				});
			});
		}

		$(document).ready(function() {
			$('#detalleDialog').createDialog({
				height: 480,
				width: 780,
				icon: 'info-sign'
			});
		});
	</script>
</BODY>

</HTML>
<?php
/**
 * Cierra conexiones y libera consultas
 */
@$obBD_con1->free_result($rs_periodos);
@$obBD_con1->free_result($rs_cabcompr);
@$obBD_con1->free_result($rs_cabcomp);
@$obBD_con1->free_result($rs_periodo);
@$obBD_con1->free_result($rs_cuentas);
@$obBD_con1->free_result($rs_combo);
@$obBD_con1->free_result($rs_carcheq);
@$obBD_con1->free_result($rs_arrmax);
@$obBD_conexion->cerrar();
@$obBD_con1->liberar();
?>
