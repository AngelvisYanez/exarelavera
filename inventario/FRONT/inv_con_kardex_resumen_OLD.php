<?php

/**
 * @abstract Permite realizar movimientos de inventario
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/inv_log_inventario.php');
require_once('../LOGICA/inv_logica_inventario.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Inv($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Inv;
$obBD_conLogica =  new Class_Logica_Inventario;


$hoy = date("Y-m-d");
$mes = date("m");

if (isset($CatSelect)) {
	$rs_tpaj = $obBD_con1->getArrayConsulta(31, $Ses_Emp_Cod . '*' . $CatSelect, $obBD_conexion);
	$Cat_Cod = $CatSelect;
	echo "<option value=''>Todas</option>";
	foreach ($rs_tpaj as $row)
		echo utf8_encode("<option value='$row[Cat_Cod]'>$row[Cat_Des]</option>");
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

		foreach ($array as $key => &$row) {

			$Ite_Cod = $row['Pro_Cod'];
			$kardex1 = array();



			$saldoInicial = $obBD_con1->getRowConsulta('kardex_ie.7', array('Pro_Cod' => $Ite_Cod, 'Fecha' => $ini), $obBD_conexion);
			$saldoFinal = $obBD_con1->getRowConsulta('kardex_ie.8', array('Pro_Cod' => $Ite_Cod, 'Fecha' => $fin), $obBD_conexion);




			if (count($saldoInicial) > 0) {
				$kardex1[0]['Promedio'] = $saldoInicial['Kar_Promedio'];
				$kardex1[0]['Saldo'] = $saldoInicial['Kar_Saldo'];
				$kardex1[0]['Stock'] = $saldoInicial['Kar_Stock'];
			} else {
				$kardex1[0]['Promedio'] = 0;
				$kardex1[0]['Saldo'] = 0;
				$kardex1[0]['Stock'] = 0;
			}

			$kardex2 = $obBD_con1->getArrayConsulta('kardex_ie.9', array('Pro_Cod' => $Ite_Cod, 'Fecha_Ini' => $ini, 'Fecha_Fin' => $fin), $obBD_conexion);


			if (count($kardex2) > 0) {
				$kardex = array_merge($kardex1, $kardex2);
				$x = COUNT($kardex);

				for ($i = 0; $i < $x; $i++) {

					/*if ($kardex[$i]['Kar_Sal'] * 1 != 0) {
						$kardex[$i]['Kar_Pre'] = $kardex[$i - 1]['Promedio'];
						$kardex[$i]['Kar_Ime'] = $kardex[$i]['Kar_Pre'] * $kardex[$i]['Kar_Sal'];
					}*/
					$row['Ent_Stk'] += $kardex[$i]['Kar_Can'] * 1;
					$row['Ent_Sal'] += $kardex[$i]['Kar_Ims'] * 1;
					$row['Sal_Stk'] += $kardex[$i]['Kar_Sal'] * 1;


					$row['Sal_Sal'] += $kardex[$i]['Kar_Ime'] * 1;

					//Nuevo stock kardex
					$kardex[$i]['Saldo'] = $kardex[$i - 1]['Saldo'] * 1 + $kardex[$i]['Kar_Ims'] * 1 - $kardex[$i]['Kar_Ime'] * 0.99999;
				}
			} else {
				$kardex = $kardex1;
				$row['Ent_Stk'] = 0;
				$row['Ent_Sal'] = 0;
				$row['Sal_Stk'] = 0;
				$row['Sal_Sal'] = 0;
			}


			$row['Ini_Stk'] = (string)(empty($kardex[0]['Stock']) ? 0.00 : $kardex[0]['Stock']);
			$row['Ini_Prp'] = (string)round((empty($kardex[0]['Promedio']) ? 0.00 : $kardex[0]['Promedio']), 2);
			$row['Ini_Sal'] = (string)(empty($kardex[0]['Saldo']) ? 0.00 : $kardex[0]['Saldo']);

			$row['Ent_Prp'] += ($row['Ent_Stk'] != 0 ? round($row['Ent_Sal'] / $row['Ent_Stk'], 6) : null);
			$row['Sal_Prp'] += ($row['Sal_Stk'] != 0 ? $row['Sal_Sal'] / $row['Sal_Stk'] : null);

			$row['Kar_Stk'] = (string)(empty($saldoFinal['Kar_Stock']) ? 0.00 : $saldoFinal['Kar_Stock']);




			$row['Kar_Prp'] = (string)round((empty($saldoFinal['Kar_Promedio']) ? 0.00 : $saldoFinal['Kar_Promedio']), 6);





			//$row['Kar_Sal'] = (string)(empty($kardex[$x - 1]['Saldo']) ? 0.00 : $kardex[$x - 1]['Saldo']); //Nuevos fila cambiada
			
			$row['Kar_Sal'] = (string)(empty($kardex[$x - 1]['Saldo']) ? 0.00 : $kardex[$x - 1]['Saldo']);
			//$row['Kar_Sal'] = (string)(empty($kardex[$x - 1]['Saldo']) ? 0.00 : $kardex[$x - 1]['Saldo']);

 

			//MAL stock kardex
			//$row['Kar_Sal'] = (string)(empty($saldoFinal['Kar_Saldo']) ? 0.00 : $saldoFinal['Kar_Saldo']);


			//$row['Sal_Prp'] += ($row['Sal_Stk'] != 0 ? round($row['Sal_Sal'] / $row['Sal_Stk'], 6) : null);





		}
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
	<TITLE><?Php echo "Inv. Kardex Resumido [EXA]"; ?></TITLE>
	<meta charset="UTF-8">
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<style>
	</style>
</HEAD>

<BODY>

	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; Kardex Resumido</h3>
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

										<?
										$sucursales = $obBD_con1->getArrayConsulta(25, $Ses_Emp_Cod, $obBD_conexion);
										?>
										<label class="col-sm-3 control-label label-xs ">Sucursal:</label>
										<div class="col-sm-6">
											<select name="Suc_Cod" class="form-control input-xs" id="Suc_Cod">
												<option value="t"><? echo "<< TODAS >>"; ?></option>
												<? foreach ($sucursales as $datos) { ?>
													<option value="<? echo $datos['Suc_Cod']; ?>"><? echo $datos['Suc_Des']; ?></option>
												<? } ?>
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
					<div style="padding-top: 10px;">
						<button class="btn btn-success btn-sm btn-frm" onclick="imprimir();" type="button"><span class="glyphicon glyphicon-floppy-disk" title="Agregar Producto"></span> Imprimir</button>
					</div>
					<script>
						var kardexGrid = $("#prods");
						$(document).ready(function() {
							$.createDialog('#successDialog', 150, 550);
							$.createDateRange('#ini', '#fin');
							$('#ini').val('2015-01-01'); //$('#ini').datepicker("setDate", new Date(today.getTime() - (30 * 24 * 3600 * 1000)));
							$('#fin').datepicker("setDate", new Date());


							kardexGrid.createGrid({
								autowidth: true,
								shrinkToFit: true,
								height: 270,
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
										width: 25,
										align: 'center'
									},
									{
										label: 'Cód.Emp.',
										name: 'Pro_Cod_Emp',
										hidden: false,
										viewable: true,
										width: 25,
										align: 'center'
									},

									{
										label: 'Detalle',
										name: 'Ite_Lar',
										width: 120,
										formatter: function(c, o, r) {
											return r.Ite_Lar + (r.Ite_Lar !== r.Pro_Obs && $.vv(r.Pro_Obs) ? ' ' + r.Pro_Obs : '');
										}
									},
									{
										label: 'Det. Cor.',
										name: 'Ite_Cor',
										key: true,
										hidden: false,
										viewable: true,
										width: 30,
										align: 'left'
									},

									{
										label: 'Stock',
										name: 'Ini_Stk',
										width: 30,
										classes: 'columnHighlight3',
										align: 'center',
										formatter: 'number'
									},
									{
										label: 'P. Promedio',
										name: 'Ini_Prp',
										width: 40,
										classes: 'columnHighlight3',
										align: 'right'
									},
									{
										label: 'Saldo',
										name: 'Ini_Sal',
										width: 40,
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
										label: 'Stock',
										name: 'Ent_Stk',
										width: 30,
										classes: 'columnHighlight2',
										align: 'center',
										formatter: 'number'
									},
									{
										label: 'P. Promedio2',
										name: 'Ent_Prp',
										width: 40,
										classes: 'columnHighlight2',
										align: 'right',
										formatter: 'number',
										formatoptions: {
											decimalPlaces: 6
										}
									},
									{
										label: 'Saldo',
										name: 'Ent_Sal',
										width: 40,
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
										label: 'Stock',
										name: 'Sal_Stk',
										width: 30,
										classes: 'columnHighlight4',
										align: 'center',
										formatter: 'number'
									},
									{
										label: 'P. Promedio3',
										name: 'Sal_Prp',
										width: 40,
										classes: 'columnHighlight4',
										align: 'right',
										formatter: 'number',
										formatoptions: {
											decimalPlaces: 6
										}
									},
									{
										label: 'Saldo',
										name: 'Sal_Sal',
										width: 40,
										classes: 'columnHighlight4',
										align: 'right',
										formatter: 'currency',
										formatoptions: {
											prefix: '$',
											thousandsSeparator: ',',
											decimalSeparator: '.',
											defaultValue: '',
											decimalPlaces: 4
										},
										summaryTpl: "Total: <b>{0}</b>",
										summaryType: "sum"
									},

									{
										label: 'Stock',
										name: 'Kar_Stk',
										width: 30,
										classes: 'columnHighlight1',
										align: 'center',
										formatter: 'number'
									},
									{
										label: 'P. Promedio',
										name: 'Kar_Prp',
										width: 40,
										classes: 'columnHighlight1',
										align: 'right',
										formatter: 'number',
										formatoptions: {
											decimalPlaces: 6
										}
									},
									{
										label: 'Saldo',
										name: 'Kar_Sal',
										width: 40,
										classes: 'columnHighlight1',
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
									kardexGrid.setGridSummary(['Ini_Sal', 'Ent_Sal', 'Sal_Sal', 'Kar_Sal'], {
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
										startColumnName: 'Ini_Stk',
										numberOfColumns: 3,
										titleText: 'Stock Inicio'
									},
									{
										startColumnName: 'Ent_Stk',
										numberOfColumns: 3,
										titleText: 'Entradas'
									},
									{
										startColumnName: 'Sal_Stk',
										numberOfColumns: 3,
										titleText: 'Salidas'
									},
									{
										startColumnName: 'Kar_Stk',
										numberOfColumns: 3,
										titleText: 'Stock Kardex'
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
								//$('#proGrid').trigger('reloadGrid', [{ page: 1 }]);
								// Grid.clearGrid();
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