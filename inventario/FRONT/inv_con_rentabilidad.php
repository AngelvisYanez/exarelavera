<?php

/**
 * @abstract Permite ver la rentabilidad de productos en un rango de fechas
 * @author Alejandro Camacho
 * @version 1.0
 * Fecha de creacion  04/05/2022
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../LOGICA/inv_log_inventario.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Inv($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Inv;


$hoy = date("Y-m-d");
$mes = date("m");

if (isset($CatSelect)) {
	$rs_tpaj = $obBD_con1->getArrayConsulta(31, $Ses_Emp_Cod . '*' . $CatSelect, $obBD_conexion);
	$Cat_Cod = $CatSelect;
	echo "<option value=''>Todas</option>";
	foreach ($rs_tpaj as $row)
		echo mb_convert_encoding("<option value='$row[Cat_Cod]'>$row[Cat_Des]</option>", 'UTF-8', 'ISO-8859-1');
	exit();
}

if (isset($productos)) {
	try {
		if ($Suc_Cod != "t") {
			$param = "Suc_Cod=" . $Suc_Cod;
		} else {
			$param = "Emp_Cod=" . $Ses_Emp_Cod;
		}
		if ($Cate_Cod != '' and $Sub_Cod == '') {
			$cat = " AND categorias.Cat_Rec=$Cate_Cod ";
		}
		if ($Cate_Cod != '' and $Sub_Cod != '') {
			$cat = " AND item.Cat_Cod=$Sub_Cod ";
		}
		if ($Ubi_Cod != '') {
			$ubi = " AND producto.Ubi_Cod=$Ubi_Cod ";
		}

		$array = $obBD_con1->getArrayConsulta(22, $param . '*' . $cat . '*' . $ubi, $obBD_conexion);
		$array = $obBD_con1->calcularRentabilidad($array, $Ses_Suc_Cod, $ini, $fin, $obBD_conexion);
	} catch (Exception $e) {
		$responce = array(success => false, message => 'No se logro obtener información del Kardex!', error => $e);
	}
	$responce['rows'] = array_values($array);
	utf8_encode_deep($responce['rows']);
	echo json_encode($responce);
	exit();
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
	<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?Php echo "Inv. Kardex Rentab. [EXA]"; ?></TITLE>
	<meta charset="UTF-8">
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<style>
	</style>
</HEAD>

<BODY>

	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; Rentabilidad de Productos</h3>
		</div>

		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">

			<div class="row">
				<div class="col-xs-12">
					<form id="formFiltros" class="form-horizontal normal" action="javascript:$('#prods').Search('#formFiltros','productos');">
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
							<div class="row">
								<div class="col-xs-6">
									<div class="form-group">

										<?php
										$sucursales = $obBD_con1->getArrayConsulta(25, $Ses_Emp_Cod, $obBD_conexion);
										?>
										<label class="col-sm-3 control-label label-xs ">Sucursal:</label>
										<div class="col-sm-6">
											<select name="Suc_Cod" class="form-control input-xs" id="Suc_Cod">
												<option value="t"><?php echo "<< TODAS >>"; ?></option>
												<?php foreach ($sucursales as $datos) { ?>
													<option value="<?php echo $datos['Suc_Cod']; ?>"><?php echo $datos['Suc_Des']; ?></option>
												<?php } ?>
												<select>
										</div>
									</div>
								</div>
								<div class="col-xs-6">
									<div class="form-group">
										<label class="col-sm-2 control-label label-xs ">Desde:</label>
										<div class="col-sm-3">
											<input name="ini" type="text" id="ini" class="form-control input-sm">
										</div>
										<label class="col-sm-2 control-label label-xs ">Hasta:</label>
										<div class="col-sm-3">
											<input name="fin" type="text" id="fin" class="form-control input-sm">
										</div>
										<div class="col-xs-2">
											<div class=""><button type="submit" class="btn btn-sm btn-success" title="Ejecutar Búsqueda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
										</div>
									</div>

									<div class="form-group">
										<label class="col-sm-2 control-label label-xs " for="Cate_Cod">Categoría:</label>
										<div class="col-sm-7">
											<?php $row_rs_categ = $obBD_con1->getArrayConsulta(30, $Ses_Emp_Cod, $obBD_conexion); ?>
											<select name="Cate_Cod" id="Cate_Cod" class="form-control input-xs" data-placeholder="Todas">
												<option value="">Todas</option>
												<?Php foreach ($row_rs_categ as $row) { ?><option value="<?Php echo $row['Cat_Cod']; ?>"><?Php echo /* strtoupper($row['Par_Cat_Des']).' � '. */ $row['Cat_Des']; ?></option><?Php } ?>
											</select>
										</div>
									</div>

									<div class="form-group">
										<label class="col-sm-2 control-label label-xs " for="Sub_Cod">Subcategoría:</label>
										<div class="col-sm-7">
											<select name="Sub_Cod" id="Sub_Cod" class="form-control input-xs" data-placeholder="Todas">
												<option value=''>Todas</option>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label label-xs " for="Ubi_Cod">Ubicación:</label>
										<div class="col-sm-7">
											<?php $rs_ubicacion = $obBD_con1->getArrayConsulta(50, $Ses_Emp_Cod, $obBD_conexion); ?>
											<select name="Ubi_Cod" id="Ubi_Cod" class="form-control input-xs">
												<option value="">Todas</option>
												<?Php foreach ($rs_ubicacion as $row) { ?><option value="<?Php echo $row['Ubi_Cod']; ?>"><?Php echo $row['Ubi_Des']; ?></option><?Php } ?>
											</select>
										</div>
									</div>
								</div>

							</div>
						</fieldset>
					</form>
				</div>
				<div class="col-xs-12" style="min-height: 450px;">
					<table id="prods"></table>
					<div id="prodsPager"></div>
					<script>
						function obteneranio() {
							var fecha = new Date();
							var year = fecha.getFullYear();
							var fechaFormatted = year + '-' + '01' + '-' + '01';
							return fechaFormatted;
						}
						var kardexGrid = $("#prods");
						$(document).ready(function() {
							$.createDialog('#successDialog', 150, 550);
							$.createDateRange('#ini', '#fin');
							$('#ini').val(obteneranio());
							$('#fin').datepicker("setDate", new Date());

							kardexGrid.createGrid({
								autowidth: true,
								shrinkToFit: true,
								height: 350,
								responsive: true,
								footerRow: true,
								caption: 'Listado de Productos',
								hidegrid: false,
								cmTemplate: {
									sortable: false /*,editrules: {edithidden: true}*/
								},
								colModel: [{
										label: 'Cód.Int.',
										name: 'Pro_Cod',
										key: true,
										hidden: false,
										viewable: true,
										width: 8,
										align: 'center'
									},

									{
										label: 'Detalle',
										name: 'Ite_Lar',
										width: 40,
										formatter: function(c, o, r) {
											return r.Ite_Lar + (r.Ite_Lar !== r.Pro_Obs && $.vv(r.Pro_Obs) ? ' ' + r.Pro_Obs : '');
										}
									},

									{
										label: 'Cant.',
										name: 'Sal_Stk',
										width: 15,
										classes: 'columnHighlight3',
										align: 'center',
										formatter: 'number'
									},
									{
										label: 'P.V.P',
										name: 'Sal_Prp',
										width: 15,
										classes: 'columnHighlight3',
										align: 'right',
										formatter: 'currency',
										formatoptions: {
											prefix: '$',
											thousandsSeparator: ',',
											decimalSeparator: '.',
											defaultValue: '0.00',
											decimalPlaces: 2
										},
									},
									{
										label: 'Venta',
										name: 'Sal_Sal',
										width: 15,
										classes: 'columnHighlight3',
										align: 'right',
										formatter: 'currency',
										formatoptions: {
											prefix: '$',
											thousandsSeparator: ',',
											decimalSeparator: '.',
											defaultValue: ''
										},
										summaryTpl: "Total: <b>{0}</b>",
										summaryType: "sum"
									},


									{
										label: 'Cant.',
										name: 'Ent_Stock', //se le puso la misma cantidad de la venta (Ent_Stock estaba de variable) para que pueda realizar los calculos de costo dependiendo de la venta realizada 
										width: 15,
										classes: 'columnHighlight4',
										align: 'center',
										formatter: 'number'
									},
									{
										label: 'P. Promedio',
										name: 'Ent_Prp',
										width: 15,
										classes: 'columnHighlight4',
										align: 'right',
										formatter: 'currency',
										formatoptions: {
											prefix: '$',
											thousandsSeparator: ',',
											decimalSeparator: '.',
											defaultValue: '0.00',
											decimalPlaces: 2
										}
									},
									{
										label: 'Costo',
										name: 'Ent_Valor',
										width: 15,
										classes: 'columnHighlight4',
										align: 'right',
										formatter: 'currency',
										formatoptions: {
											prefix: '$',
											thousandsSeparator: ',',
											decimalSeparator: '.',
											defaultValue: '0.00',
											decimalPlaces: 2
										},
										summaryTpl: "Total: <b>{0}</b>",
										summaryType: "sum"
									},



									{
										label: 'Valor',
										name: 'Ren_Valor',
										width: 15,
										classes: 'columnHighlight2',
										align: 'right',
										formatter: 'currency',
										formatoptions: {
											prefix: '$',
											thousandsSeparator: ',',
											decimalSeparator: '.',
											defaultValue: ''
										},
										summaryTpl: "Total: <b>{0}</b>",
										summaryType: "sum"
									},
									{
										label: 'Porcentaje',
										name: 'Ren_Porce',
										width: 15,
										classes: 'columnHighlight2',
										align: 'right',
										formatter: 'currency',
										formatoptions: {
											prefix: '',
											suffix: '%',
											thousandsSeparator: ',',
											decimalSeparator: '.',
											decimalPlaces: 2,
											defaultValue: ''
										},
									},

								],
								footerrow: true,
								userDataOnFooter: false,
								rowNum: 10000000,
								pager: "#prodsPager",
								gridview: true,
								rownumbers: true,
								viewrecords: true,
								pgbuttons: false,
								pgtext: null,
								loadComplete: function() {
									//kardexGrid.startGridEdit();
									kardexGrid.setGridSummary(['Ent_Valor', 'Sal_Sal', 'Ren_Valor'], {
										Ite_Lar: '<div style="text-align:right;">TOTALES:</div>'
									});
								}
							}, true, "#prodsPager").gridButtonsAdd([{
									buttonicon: 'print',
									caption: 'Imprimir',
									onClickButton: function() {
										printR('#prods');
									}
								},
								{
									buttonicon: 'download-alt',
									caption: 'Descargar',
									onClickButton: function() {
										exportR('#prods');
									}
								}
							]);;
							kardexGrid.jqGrid('setGroupHeaders', {
								useColSpanStyle: true,
								groupHeaders: [{
										startColumnName: 'Ent_Stock',
										numberOfColumns: 3,
										titleText: 'Compra'
									},
									{
										startColumnName: 'Sal_Stk',
										numberOfColumns: 3,
										titleText: 'Venta'
									},
									{
										startColumnName: 'Ren_Valor',
										numberOfColumns: 2,
										titleText: 'Utilidad'
									}
								]
							});
						});
						$('#Cate_Cod').change(function() {
							var cod = $('#Cate_Cod').val();
							$('#Sub_Cod').html('');
							$.get("", {
								CatSelect: cod
							}, function(response) {
								$('#Sub_Cod').html(response);
							})
						});
					</script>
				</div>
			</div>
		</div>
	</div>
	<script>
		function printR(grid) {
			$('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML', {
				generated: false,
				caption: false,
				footer: true,
				bodyBorder: false
			}));
			$('#titleReporte').html($(grid).getCaption());
			$('#formatoReporte').printElement({
				pageTitle: "<?Php echo $Ses_Sys_Nom; ?>",
				printMode: 'popup',
				overrideElementCSS: [{
					href: '../../mascaras/model1/estilos/print.css',
					media: 'print'
				}]
			});
		}

		function exportR(grid) {
			var temp = $('<div>' + $('#formatoExportar').html() + '</div>');
			temp.append($(grid).jqGrid('exportGridHTML', {
				generated: false,
				caption: true,
				bodyBorder: false,
				footer: true,
				sepEnd: true
			}));
			$.downloadFile($.exportarExcelBlob(temp.html(), 'Digitacion'), 'digitacion_' + $.getDate() + '.xls');
		}
	</script>
	<div id="formatoReporte" style="display: none;">
		<div style="width: 1030px;">
			<?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE KARDEX RESUMIDO', '<span id="titleReporte"></span>', $obBD_conexion); ?>
			<table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>
			<?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
		</div>
	</div>
	<div id="formatoExportar" style="width: 700px;display: none;">
		<?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE KARDEX RESUMIDO', '<span class="title_grid"></span>', $obBD_conexion, false, 6); ?>
	</div>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</BODY>

</HTML>