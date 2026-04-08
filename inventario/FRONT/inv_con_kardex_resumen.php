<?php

/**
 * @abstract Permite realizar movimientos de inventario
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/inv_log_inventario.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

@set_time_limit(480);
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

if (isset($productos)) {
	try {
		if ($Suc_Cod != "t") {
			$param = "Suc_Cod=" . $Suc_Cod;
		} else {
			$param = "Emp_Cod=" . $Ses_Emp_Cod;
		}
		$array = $obBD_con1->getArrayConsulta(22, $param, $obBD_conexion);

		foreach ($array as $key => &$row) {
			$Ite_Cod = $row['Pro_Cod'];

			$kardex1 = $obBD_con1->getArrayConsulta(23, $ini . '*' . $Ite_Cod, $obBD_conexion);
			
			if (count($kardex1) == 1 && $kardex1[0]['Saldo'] !== 0 && $kardex1[0]['Stock'] != 0) {
				$kardex1[0]['Promedio'] = round(($kardex1[0]['Saldo'] / $kardex1[0]['Stock']), 6);
			} else {
				$kardex1[0]['Promedio'] = 0;
				$kardex1[0]['Saldo'] = 0;
				$kardex1[0]['Stock'] = 0;
			}

			list($ann, $mes, $dia) = split('[/.-]', $ini);
			$kardex1[0]['Kar_Det'] = '<b>Saldo al ' . $dia . ', de ' . mes($mes, 1) . ', ' . $ann . '</b>';
			$kardex2 = $obBD_con1->getArrayConsulta(24, $ini . '*' . $fin . '*' . $Ite_Cod, $obBD_conexion);


			if (count($kardex2) > 0) {
				$kardex = array_merge($kardex1, $kardex2);
			} else {
				$kardex = $kardex1;
			}

			$row['Ent_Stk'] = 0;
			$row['Ent_Sal'] = 0;
			$row['Sal_Stk'] = 0;
			$row['Sal_Sal'] = 0;

			$x = COUNT($kardex);
			for ($i = 1; $i < $x; $i++) {
				if ($kardex[$i]['Kar_Sal'] * 1 != 0) {
					$kardex[$i]['Kar_Pre'] = $kardex[$i - 1]['Promedio'];
					$kardex[$i]['Kar_Ime'] = $kardex[$i]['Kar_Pre'] * $kardex[$i]['Kar_Sal'];
				}

				$kardex[$i]['Stock'] = $kardex[($i - 1)]['Stock'] * 1 + $kardex[$i]['Kar_Can'] * 1 - $kardex[$i]['Kar_Sal'] * 1;
				
				
				$kardex[$i]['Saldo'] = $kardex[$i - 1]['Saldo'] * 1 + $kardex[$i]['Kar_Ims'] * 1 - $kardex[$i]['Kar_Ime'] * 1;
				
				
				$kardex[$i]['Promedio'] = ($kardex[$i]['Stock'] != 0 ? $kardex[$i]['Saldo'] / $kardex[$i]['Stock'] : $kardex[$i - 1]['Promedio']);
				$row['Ent_Stk'] += $kardex[$i]['Kar_Can'] * 1;
				$row['Ent_Sal'] += $kardex[$i]['Kar_Ims'] * 1;
				$row['Sal_Stk'] += $kardex[$i]['Kar_Sal'] * 1;
				$row['Sal_Sal'] += $kardex[$i]['Kar_Ime'] * 1;
			}

			$row['Ini_Stk'] = (string)(empty($kardex[0]['Stock']) ? 0.000000 : $kardex[0]['Stock']);
			$row['Ini_Prp'] = (string) round((empty($kardex[0]['Promedio']) ? 0.000000 : $kardex[0]['Promedio']), 8);
			
			
			
			$row['Ini_Sal'] = (string)(empty($kardex[0]['Saldo']) ? 0.000000 : $kardex[0]['Saldo']);
			
			
			
			
			$row['Ent_Prp'] +=    ($row['Ent_Stk'] != 0 ? $row['Ent_Sal'] / $row['Ent_Stk'] : null);
			//$row['Ent_Prp'] += ($row['Ent_Stk'] != 0 ? number_format($row['Ent_Sal'] / $row['Ent_Stk'], 5, '.', '') : null);
			$row['Sal_Prp'] += ($row['Sal_Stk'] != 0 ? $row['Sal_Sal'] / $row['Sal_Stk'] : null);
			$row['Kar_Stk'] = (string)(empty($kardex[$x - 1]['Stock']) ? 0.000000 : $kardex[$x - 1]['Stock']);
			$row['Kar_Prp'] = (string)round((empty($kardex[$x - 1]['Promedio']) ? 0.000000 : $kardex[$x - 1]['Promedio']), 8);
			$row['Kar_Sal'] = (string)(empty($kardex[$x - 1]['Saldo']) ? 0.000000 : $kardex[$x - 1]['Saldo']);
		}
	} catch (Exception $e) {
		$responce = array(success => false, message => 'No se logro obtener información del Kardex!', error => $e);
	}
	$responce['rows'] = array_values($array);
	utf8_encode_deep($responce['rows']);
	echo json_encode($responce);
	exit();
}


if (isset($productos)) {
	if ($Suc_Cod != "t") {
		$param = "Suc_Cod=" . $Suc_Cod;
	} else {
		$param = "Emp_Cod=" . $Ses_Emp_Cod;
	}
	$array = $obBD_con1->getArrayConsulta(22, $param, $obBD_conexion);
	//var_dum
	foreach ($array as $key => &$row) {
		$row['Pro_Sal'] = (string)round($row['Pro_Stk'] * $row['Pro_Prp'], 2);
		$kardexHist = $obBD_con1->getArrayConsulta(13, $ini . '*' . $fin . '*' . $row['Pro_Cod'], $obBD_conexion, true);
		$kardex = array_merge(array(0 => array()), $kardexHist);
		$x = COUNT($kardex);
		for ($i = 1; $i < $x; $i++) {
			if ($kardex[$i]['Kar_Sal'] * 1 != 0) {
				$kardex[$i]['Kar_Pre'] =  empty($kardex[$i - 1]['Promedio']) ? 0 : $kardex[$i - 1]['Promedio'];
				$kardex[$i]['Kar_Ime'] = round($kardex[$i]['Kar_Pre'] * $kardex[$i]['Kar_Sal'], 2);
			}
			$kardex[$i]['Stock'] = $kardex[($i - 1)]['Stock'] * 1 + $kardex[$i]['Kar_Can'] * 1 - $kardex[$i]['Kar_Sal'];
			$kardex[$i]['Saldo'] = round($kardex[$i - 1]['Saldo'] * 1 + $kardex[$i]['Kar_Ims'] * 1 - $kardex[$i]['Kar_Ime'], 2);
			$kardex[$i]['Promedio'] = $kardex[$i]['Saldo'] / $kardex[$i]['Stock'];
		}
		$row['Kar_Stk'] = (string)(empty($kardex[$x - 1]['Stock']) ? 0.000000 : $kardex[$x - 1]['Stock']);
		$row['Kar_Prp'] = (string)round((empty($kardex[$x - 1]['Promedio']) ? 0.000000 : $kardex[$x - 1]['Promedio']), 8);
		$row['Kar_Sal'] = (string)(empty($kardex[$x - 1]['Saldo']) ? 0.000000 : $kardex[$x - 1]['Saldo']);
		// var_dump($row['Kar_Stk'],$row['Pro_Stk']);
		// var_dump($row['Pro_Cod'],round($row['Kar_Prp'],8)==round($row['Pro_Prp'],8)); 
		// $row['Dif']=round($row['Pro_Stk'],2)-round($row['Stk_Can'],2);
		// $row['Dif2']=round($row['Kar_Stk'],2)-round($row['Stk_Can'],2);
		//if(round($row['Kar_Stk'],2)==round($row['Stk_Can'],2))
		//   unset($array[$key]);
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
	<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
	<meta charset="UTF-8">
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<style>
	</style>
</HEAD>

<BODY>

	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; Ajuste de Inventario General</h3>
		</div>

		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div class="row">
				<div class="col-xs-12">
					<form id="formFiltros" class="form-horizontal normal">
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
							<div class="row">
								<div class="col-xs-6">
									<div class="form-group">
										<?php
										//$sucursales = $obBD_con1->getArrayConsulta(25, $Ses_Emp_Cod, $obBD_conexion, true);
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
											<div class=""><button type="button" onclick="kardexGrid.setGridParam({postData:$('#formFiltros').getData('productos')}); kardexGrid.trigger('reloadGrid', [{page:1}]) " class="btn btn-sm btn-success" title="Ejecutar Búsqueda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
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
							$('#ini').val('2000-01-01'); //$('#ini').datepicker("setDate", new Date(today.getTime() - (30 * 24 * 3600 * 1000)));
							$('#fin').datepicker("setDate", new Date());
							kardexGrid.createGrid({
								url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
								mtype: "GET",
								datatype: "json",
								regional: 'es', //ajaxRowOptions: { async: true },
								postData: $('#formFiltros').getData('productos'),
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
										label: 'Detalle',
										name: 'Ite_Lar',
										width: 150,
										formatter: function(c, o, r) {
											return r.Ite_Lar + (r.Ite_Lar !== r.Pro_Obs && $.vv(r.Pro_Obs) ? ' ' + r.Pro_Obs : '');
										}
									},
									/*{ label: 'Stock',name: 'Pro_Stk', width: 30,classes:'columnHighlight3',align:'center',formatter:'number'},
                                        { label: 'P. Promedio',name: 'Pro_Prp', width: 40,classes:'columnHighlight3',align:'right'},
										{ label: 'Saldo',name: 'Pro_Sal', width: 40,classes:'columnHighlight3',align:'right',formatter:'currency', formatoptions: {prefix:'$', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum"},
										{ label: 'Stock',name: 'Stk_Can', width: 30,classes:'columnHighlight3',align:'center',formatter:'number'},
                                        { label: 'P. Promedio',name: 'Stk_Prp', width: 40,classes:'columnHighlight3',align:'right'},
                                        { label: 'Saldo',name: 'Stk_Sal', width: 40,classes:'columnHighlight3',align:'right',formatter:'currency', formatoptions: {prefix:'$', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum"},*/
									{
										label: 'Stock--',
										name: 'Ini_Stk',
										width: 30,
										classes: 'columnHighlight3',
										align: 'center',
										formatter: 'number',
										formatoptions: {
											decimalPlaces: 6
										}



									},
									{
										label: 'P. Promedio',
										name: 'Ini_Prp',
										width: 40,
										classes: 'columnHighlight3',
										align: 'right',
										formatter: 'number',
										formatoptions: {
											decimalPlaces: 6
										}
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
										label: 'P. Promedio',
										name: 'Ent_Prp',
										width: 40,
										classes: 'columnHighlight2',
										align: 'right',
										formatter: 'number',
										formatoptions: {
											decimalPlaces: 6 // Esto asegura que se muestren siempre cinco decimales
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
											defaultValue: '',
											decimalPlaces: 6
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
										formatter: 'number',
										formatoptions: {
											decimalPlaces: 6
										}
									},
									{
										label: 'P. Promedio',
										name: 'Sal_Prp',
										width: 40,
										classes: 'columnHighlight4',
										align: 'right'
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
											defaultValue: ''
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
										align: 'right'
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