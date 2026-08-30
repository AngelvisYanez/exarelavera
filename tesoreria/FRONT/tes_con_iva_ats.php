<?php
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anexo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion  */
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas  */
$obBD_con1 =  new Class_Log_Datos_Anx;

if (isset($saveAtsXml)) {
	$data = json_decode(stripslashes($data), true);
	foreach ($data as $row) {
		$ats = $ats . "<detalleCompras>
		<codSustento>" . $row['sustento'] . "</codSustento>
		<tpIdProv>" . $row['tpIdProv'] . "</tpIdProv>
		<idProv>" . $row['ruc'] . "</idProv>
		<tipoComprobante>" . $row['tipoComprobante'] . "</tipoComprobante>
		<fechaRegistro>" . $row['fecha'] . "</fechaRegistro>
		<establecimiento>" . $row['estab'] . "</establecimiento>
		<puntoEmision>" . $row['impre'] . "</puntoEmision>
		<secuencial>" . $row['documento'] . "</secuencial>
		<autorizacion>" . $row['autorizacion'] . "</autorizacion>
		<baseImponible>" . ($row['base'] > 1 ? $row['base'] : $row['base'] * -1) . "</baseImponible>
		<montoIva>" . ($row['iva'] > 1 ? $row['iva'] : $row['iva'] * -1) . "</montoIva>
		<ivaSolicitado>" . ($row['ivadev'] > 1 ? $row['ivadev'] : $row['ivadev'] * -1) . "</ivaSolicitado>
		</detalleCompras>";
	}
	$atsIva = '<?xml version="1.0" encoding="ISO-8859-1" standalone="yes"?><devIva><numeroRuc>' . $data[0]['empresa'] . '</numeroRuc><anio>' . $data[0]['anio'] . '</anio><mes>' . $data[0]['mes'] . '</mes><compras>' . $ats . '</compras><importaciones><importacionesBienes><baseImponible>0</baseImponible><montoIva>0</montoIva></importacionesBienes><importacionesActivosFijos> <baseImponible>0</baseImponible><montoIva>0</montoIva></importacionesActivosFijos></importaciones></devIva>';
	//$file=fopen("SRI/1/iva.xml","w+");
	//fwrite ($file,$atsIva);
	//fclose($file);
	$obBD_con1->echoJson(array('success' => true, 'xml' => $atsIva));
}
if (isset($uploadXML)) {
	$responce['success'] = false;
	$responce['message'] = "No se ha encontrado ningun archivo!";
	$tot = count($_FILES["archivoXML"]["name"]);
	$omitir02 = isset($omitir02) && $omitir02 == 'S';
	$omitir07 = isset($omitir07) && $omitir07 == 'S';
	$omitir08 = isset($omitir08) && $omitir08 == 'S';

	$omitirnc = isset($omitirnc) && $omitirnc == 'S';
	$bancarizacion = 5000;
	//este for recorre el arreglo 
	try {
		if ($tot > 0) {
			$z = 0;
			$rows = array();
			$nc = array();
			for ($i = 0; $i < $tot; $i++) {
				$explode_name = explode('.', $_FILES['archivoXML']['name'][$i]);
				if (isset($explode_name[1]) && strtoupper($explode_name[1]) == 'XML') {
					$sri = simplexml_load_file($_FILES["archivoXML"]["tmp_name"][$i]);
					$responce['empresa'] = '<b>EMPRESA(S)&raquo;</b>&nbsp; ' . $sri->razonSocial . '(' . $sri->IdInformante . ')  ';
					$datos = $sri->compras[0]->detalleCompras;
					$totCom = count($datos);

					$anio = '' . $sri->Anio;
					$mes = '' . $sri->Mes;
					$totBase = 0;
					$totBaseAll = 0;
					$totIva = 0;
					$totIvaAll = 0;
					$totales = array();

					for ($x = 0; $x < $totCom; $x++) {
						if ($datos[$x]->tipoComprobante == "04") {
							$ncAux = array(
								'numero' => '' . $datos[$x]->estabModificado . '-' . $datos[$x]->ptoEmiModificado . '-' . $datos[$x]->secModificado,
								'baseImpGrav' => '' . $datos[$x]->baseImpGrav,
								'montoIva' => '' . $datos[$x]->montoIva,
							);
							array_push($nc, $ncAux);
						}
					}

					/*for($x=0;$x<$totCom;$x++){
                        if( $datos[$x]->tipoComprobante=="01"){
                            $add=true;
                            $val_monto=($datos[$x]->tipoComprobante=="04"?-1:1)*((''.$datos[$x]->baseImpGrav)*1)+((''.$datos[$x]->montoIva)*1);
                            foreach($totales AS $k => &$v){
                                if($k==''.$datos[$x]->idProv){ $v+=$val_monto; $add=false; break; }
                            } unset($v);
                            if($add) $totales[''.$datos[$x]->idProv]=$val_monto;
                        }else{  
							if(!$omitirnc||($omitirnc&&''.$datos[$x]->tipoComprobante!='04')){
								$add=true;
								$val_monto=($datos[$x]->tipoComprobante=="04"?-1:1)*((''.$datos[$x]->baseImpGrav)*1)+((''.$datos[$x]->montoIva)*1);
								foreach($totales AS $k => &$v){
									if($k==''.$datos[$x]->idProv){ $v+=$val_monto; $add=false; break; }
								} unset($v);
								if($add) $totales[''.$datos[$x]->idProv]=$val_monto;
							}
						}
                    }*/
					for ($x = 0; $x < $totCom; $x++) {
						if ($datos[$x]->tipoComprobante == "01" || $datos[$x]->tipoComprobante == "04") {
							if (!$omitirnc || ($omitirnc && '' . $datos[$x]->tipoComprobante != '04')) {
								$add = true;
								$val_monto = ($datos[$x]->tipoComprobante == "04" ? -1 : 1) * (('' . $datos[$x]->baseImpGrav) * 1) + (('' . $datos[$x]->montoIva) * 1);
								foreach ($totales as $k => &$v) {
									if ($k == '' . $datos[$x]->idProv) {
										$v += $val_monto;
										$add = false;
										break;
									}
								}
								unset($v);
								if ($add) $totales['' . $datos[$x]->idProv] = $val_monto;
							}
						}
					}
					for ($x = 0; $x < $totCom; $x++) {
						$baseIvaCom = 0;
						$montoIvaCom = 0;
						$devIva = 0;
						if( $datos[$x]->codSustento == '07' AND !empty($omitir07) ) continue;
						if( $datos[$x]->codSustento == '08' AND !empty($omitir08) ) continue;
						if (!$omitir02 || ($omitir02 && '' . $datos[$x]->codSustento != '02')) {
							if (!$omitirnc || ($omitirnc && '' . $datos[$x]->tipoComprobante != '04')) {
								$baseIvaCom = (float)$datos[$x]->baseImpGrav;
								$montoIvaCom = (float)$datos[$x]->montoIva;
								if ($omitirnc == 'S') {
									for ($y = 0; $y < count($nc); $y++) {
										if ($nc[$y]['numero'] === (string)$datos[$x]->establecimiento . '-' . $datos[$x]->puntoEmision . '-' . $datos[$x]->secuencial) {
											$devIva = $nc[$y]['montoIva'];
											break;
										}
									}
								}
								$autRet = '';
								$rs_persona = $obBD_con1->getRowConsulta(875, $datos[$x]->idProv, $obBD_conexion);
								$total_persona = $rs_persona['Prs_Cod'] > 0 ? 1 : 0;
								$nomPrv = ($total_persona != 0) ? $rs_persona['Prs_Ape'] . ' ' . $rs_persona['Prs_Nom'] : '-';

								if ($datos[$x]->tipoComprobante == "01") $tipo = "FACTURA";
								else if ($datos[$x]->tipoComprobante == "02") $tipo = "NOTA DE VENTA";
								else if ($datos[$x]->tipoComprobante == "03") $tipo = "LIQUIDACI&Oacute;N DE COMPRA";
								else if ($datos[$x]->tipoComprobante == "04") $tipo = "NOTA DE CR&Eacute;DITO";
								else if ($datos[$x]->tipoComprobante == "05") $tipo = "NOTA DE D&Eacute;BITO";
								else $tipo = '';

								if (($montoIvaCom - (float)$devIva) > 0) {
									$z = $z + 1;
									$totBase += $datos[$x]->baseImponible;
									$totIva += $datos[$x]->montoIva;
									$codigoFormato = $sri->IdInformante . ($datos[$x]->tipoComprobante == '01' && $totales['' . $datos[$x]->idProv] >= $bancarizacion ? '_bancarizacion' : '') . '_' . strtolower($tipo) . '_' . intval($datos[$x]->secuencial) . '_' . $sri->Anio . '_' . strtolower(mes($sri->Mes, 1));
									if ($datos[$x]->autRetencion1 != 0) {
										$autRet = $datos[$x]->autRetencion1;
									}
									//die(var_dump($total));
									$fila = array(
										'id'        => $z, //'anio'=>$anio,'mes'=>$mes,
										'md5' => md5($codigoFormato),
										'ruc' => '' . $datos[$x]->idProv,
										'empresa' => '' . $sri->IdInformante,
										'anio' => '' . $sri->Anio,
										'mes' => '' . $sri->Mes,
										'tpIdProv' => '' . $datos[$x]->tpIdProv,
										'tipoComprobante' => '' . $datos[$x]->tipoComprobante,
										'codigo' => '' . $codigoFormato,
										'tipo' => '' . $tipo,
										'sustento' => '' . $datos[$x]->codSustento,
										'proveedor' => '' . $nomPrv,
										'fecha'     => '' . $datos[$x]->fechaEmision,
										'estab'     => '' . $datos[$x]->establecimiento,
										'impre'     => '' . $datos[$x]->puntoEmision,
										'documento' => str_pad($datos[$x]->secuencial, 9, "0", STR_PAD_LEFT),
										'autorizacion' => '' . $datos[$x]->autorizacion,
										'autret' => '' . $autRet,
										'base' => ($datos[$x]->tipoComprobante == "04" ? '-' . $baseIvaCom : '' . $baseIvaCom),
										'iva' => ($datos[$x]->tipoComprobante == "04" ? '-' . $montoIvaCom : '' . $montoIvaCom),
										'val_total' => ($montoIvaCom + $baseIvaCom),
										'ivadev' => ($datos[$x]->tipoComprobante == "04" ? '-' . ($montoIvaCom - (float)$devIva) : '' . ($montoIvaCom - (float)$devIva)),
										'formaPago' => '' . $datos[$x]->formasDePago->formaPago,
									);
									array_push($rows, $fila);
									$totBaseAll += ('' . $datos[$x]->baseImpGrav) * ($datos[$x]->tipoComprobante == "04" ? -1 : 1);
									$totIvaAll += ('' . $datos[$x]->montoIva) * ($datos[$x]->tipoComprobante == "04" ? -1 : 1);
									$val_total += ($montoIvaCom + $baseIvaCom);
									$totDevol += ($montoIvaCom - (float)$devIva) * ($datos[$x]->tipoComprobante == "04" ? -1 : 1);
								}
							}
						} //fin omitir sustento 02
					}  //fin for($x=0;$x<$totCom;$x++)
					$responce['success'] = true;
				} else {
					$responce['success'] = false;
					$responce['message'] = "La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)";
					break;
				}
			} //fin for ($i = 0; $i < $tot; $i++)			
		}
	} catch (Exception $e) {
		$responce['success'] = false;
		$responce['message'] = 'ERROR: ' . $e->getMessage();
	}
	if ($responce['success']) {
		$responce['grid'] = array('rows' => $rows, 'records' => count($rows), 'total' => '1', 'userData' => array('base' => $totBaseAll, 'iva' => $totIvaAll, 'val_total'=>$val_total ,'ivadev' => $totDevol));
		utf8_encode_deep($responce['grid']['rows']);
	}
	echo json_encode($responce);
	exit();
}
?>
<!DOCTYPE html>
<HTML>

<HEAD>
	<TITLE>EXA - Software Contable</TITLE>
	<meta charset="UTF-8"> <!-- Configuración de UTF-8 para el manejo de caracteres -->
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<style></style>
</HEAD>

<BODY>
	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; Examinar Iva ATS</h3>
		</div>

		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div class="row">
				<div class="col-xs-12">
					<FIELDSET class="exa-fieldset">
						<LEGEND class="Titulos2">Cargar ATS</LEGEND>
						<form method="post" name="form3" id="form3" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="form-horizontal normal">
							<div class="form-group">
								<label class="col-xs-1 control-label label-sm required">Seleccione:</label>								
								<div class="col-xs-4">
									<div class="input-group">
										<input type="file" multiple name="archivoXML[]" id="archivoXML[]" value="" accept="text/xml" class="form-control input-sm" required="" />
										<span class="input-group-btn"><button type="button" class="btn btn-sm btn-primary start" onclick="loadXML();"><i class="glyphicon glyphicon-upload"></i> <span>Cargar</span> </button></span>
									</div>
								</div>
								<div class="col-xs-4" >
									<input type="checkbox" id="omitir02" name="omitir02" value="S" offval="N" class="check-big"><label class="control-label label-sm">&nbsp;Omitir Sust. 02</label>&nbsp;&nbsp;
									<input type="checkbox" id="omitir07" name="omitir07" value="S" offval="N" class="check-big"><label class="control-label label-sm">&nbsp;Omitir Sust. 07</label>&nbsp;&nbsp;
									<input type="checkbox" id="omitir08" name="omitir08" value="S" offval="N" class="check-big"><label class="control-label label-sm">&nbsp;Omitir Sust. 08</label>&nbsp;&nbsp;
									<input type="checkbox" id="omitirnc" name="omitirnc" value="S" offval="N" class="check-big"><label class="control-label label-sm">&nbsp;Omitir N/C</label>
								</div>																
							</div>							
						</form>
					</FIELDSET>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12" style="min-height: 350px;">
					<FIELDSET class="exa-fieldset">
						<LEGEND class="Titulos2">Resultados</LEGEND>
						<table id="list"></table>
						<div id="listPager"></div>
					</FIELDSET>
				</div>
				<div class="col-xs-12">
					<button onclick="$('#list').jqGrid('printGrid',{nombre:'Reporte de ATS',caption:true,bodyBorder:false,footer:true});" title="Imprimir Reporte" type="button" class="btn btn-sm btn-primary start"> <i class="glyphicon glyphicon-print"></i> <span>Imprimir</span></button>
					<button onclick="$('#list').jqGrid('exportGridExcel',{nombre:'Reporte_ATS',hoja:'Hoja ATS',caption:true,generated:false,footer:true});" title="Exportar Excel" class="btn btn-sm btn-primary start"> <i class="glyphicon glyphicon-download-alt"></i> <span>Excel</span></button>
					<button onclick="saveXml();" title="Exportar Excel" class="btn btn-sm btn-primary start"> <i class="glyphicon glyphicon-download-alt"></i> <span>ATS-Devolucion Iva</span></button>
				</div>
			</div>
		</div>
	</div>
	<script>
		var jgrid;

		function loadXML() {
			var formData = new FormData(document.getElementById("form3"));
			formData.append("uploadXML", true);
			$("#loader").show();
			//formData.append(f.attr("name"), $(this)[0].files[0]);
			$.ajax({
				url: "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",
				type: "post",
				dataType: "json",
				data: formData,
				cache: false,
				contentType: false,
				processData: false
			}).done(function(response) {
				jgrid.jqGrid("clearGridData");
				$("#loader").fadeOut("slow");
				if (response['success'] === true) {
					jgrid.jqGrid("setCaption", response['empresa']);
					jgrid.jqGrid('setGridParam', {
						rowNum: response['grid']['records']
					});
					jgrid.jqGrid('setGridParam', {
						userData: $.extend({
							autret: '<div style="text-align:right">TOTALES:</div>'
						}, response['grid']['userData']),
						data: response['grid']['rows'],
						page: 1,
						records: response['grid']['records'],
						total: response['grid']['total']
					}).trigger('reloadGrid');
					jgrid.effect("highlight", {}, 500);
				} else {
					$.alert(response['message']);
				}
			}).fail(function(error) {
				$.alert("El Servidor ha fallado en responder! ");
				$("#loader").hide();
			});
		}
		$(document).ready(function() {
			//        $("#chngroup").change(function(){
			//            var vl = $(this).val();
			//            if(vl) {
			//                    if(vl === "clear") {jgrid.jqGrid('groupingRemove',true);} 
			//                    else {jgrid.jqGrid('groupingGroupBy',vl);}
			//            }
			//        });
			jgrid = $("#list").createGrid({
				colModel: [{
						label: 'C�d.Int.',
						name: 'id',
						key: true,
						width: 55,
						align: "center",
						hidden: true
					},
					{
						label: 'Código',
						name: 'codigo',
						width: 75,
						hidden: false,
						align: "center",
						cellattr: function() {
							return 'style="' + excelFormats.text + '"';
						},
						classes: 'bgNoRight bgNoColor'
					},
					{
						label: 'md5.',
						name: 'md5',
						key: true,
						width: 55,
						align: "center",
						hidden: false
					},

					{
						label: 'TipoPrv',
						width: 45,
						name: 'tpIdProv',
						align: "center",
						hidden: true,
						sorttype: "date",
						classes: 'bgNoRight bgNoColor'
					},
					{
						label: 'Empresa',
						width: 45,
						name: 'empresa',
						align: "center",
						hidden: false,
						sorttype: "date",
						classes: 'bgNoRight bgNoColor'
					},
					{
						label: 'Anio',
						width: 45,
						name: 'anio',
						align: "center",
						hidden: true,
						sorttype: "date",
						classes: 'bgNoRight bgNoColor'
					},
					{
						label: 'Mes',
						width: 45,
						name: 'mes',
						align: "center",
						hidden: true,
						sorttype: "date",
						classes: 'bgNoRight bgNoColor'
					},
					{
						label: 'TipoDoc',
						width: 45,
						name: 'tipoComprobante',
						align: "center",
						hidden: true,
						sorttype: "date",
						classes: 'bgNoRight bgNoColor'
					},
					{
						label: 'Tipo',
						name: 'tipo',
						width: 45,
						align: "center",
						cellattr: function() {
							return 'style="' + excelFormats.text + '"';
						},
						classes: 'bgNoRight bgNoColor'
					},
					{
						label: 'Form.Pago',
						name: 'formaPago',
						width: 45,
						align: "center",
						cellattr: function() {
							return 'style="' + excelFormats.text + '"';
						},
						classes: 'bgNoRight bgNoColor'
					},
					{
						label: 'Sustento',
						name: 'sustento',
						width: 35,
						align: "center",
						cellattr: function() {
							return 'style="' + excelFormats.text + '"';
						},
						classes: 'bgNoRight bgNoColor'
					},
					{
						label: 'C.I/R.U.C',
						name: 'ruc',
						width: 50,
						align: "center",
						cellattr: function() {
							return 'style="' + excelFormats.text + '"';
						},
						classes: 'bgNoRight bgNoColor'
					},
					{
						label: 'Proveedor',
						name: 'proveedor',
						width: 80,
						cellattr: function() {
							return 'style="' + excelFormats.text + '"';
						},
						classes: 'bgNoRight bgNoColor'
					},

					{
						label: 'Fecha',
						width: 45,
						name: 'fecha',
						align: "center",
						sorttype: "date",
						classes: 'bgNoRight bgNoColor'
					},
					//{ label: 'Estab.', width: 20,name: 'estab',align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';}, classes:'bgNoRight bgNoColor'},
					//{ label: 'Impre.', width: 20,name: 'impre',align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';}, classes:'bgNoRight bgNoColor'},

					{
						label: 'Estab. | Impre.',
						name: 'estab_impre', // Combined data field (you'll handle this in the formatter)
						width: 40,
						align: "center",
						cellattr: function() {
							return 'style="' + excelFormats.text + '"';
						},
						classes: 'bgNoRight bgNoColor',
						formatter: function(cellvalue, options, rowObject) {
							// Assuming you have 'estab' and 'impre' fields in your rowObject
							var estab = rowObject.estab || ''; // Get estab value, or empty if undefined
							var impre = rowObject.impre || ''; // Get impre value, or empty if undefined
							return estab + ' - ' + impre; // Combine with a separator
						}
					},
					{
						label: 'Documento',
						name: 'documento',
						width: 40,
						align: "center",
						cellattr: function(rowId, val, rawObject, cm, rdata) {
							return 'style="' + excelFormats.text + '"';
						},
						classes: 'bgNoRight bgNoColor'
					},
					{
						label: 'Aut. Compra',
						name: 'autorizacion',
						width: 40,
						cellattr: function(rowId, val, rawObject, cm, rdata) {
							return 'style="' + excelFormats.text + '"';
						},
						classes: 'bgNoRight bgNoColor'
					},

					{
						label: 'Base Imp.',
						name: 'base',
						width: 38,
						align: 'right',
						formatter: 'number',
						formatoptions: {},
						summaryTpl: "{0}",
						summaryType: sumNotNC,
						summaryRound: '2',
						summaryRoundType: 'round'
					}, // set the formula to calculate the summary type 
					{
						label: 'Iva',
						name: 'iva',
						width: 38,
						align: 'right',
						formatter: 'number',
						formatoptions: {},
						summaryTpl: "{0}",
						summaryType: sumNotNC,
						summaryRound: '2',
						summaryRoundType: 'round'
					}, // set the formula to calculate the summary type


					{
						label: 'total',
						name: 'val_total',
						width: 38,
						align: 'right',
						/*	formatter: function(cellvalue, options, rowObject) {
									var base = parseFloat(rowObject.base) || 0;
									var iva = parseFloat(rowObject.iva) || 0;
									return (base + iva).toFixed(2);
									
								},
								summaryTpl: "{0}",
								summaryType: function(v, n, obj) {
									var base = parseFloat(obj.base) || 0;
									var iva = parseFloat(obj.iva) || 0;
									return (base + iva);
								},*/
						formatoptions: {},
						summaryType: sumNotNC,
						summaryRound: '2',
						summaryRoundType: 'round'
					},


					{
						label: 'Aut. Reten.',
						name: 'autret',
						width: 40,
						hidden: false,
						cellattr: function(rowId, val, rawObject, cm, rdata) {
							return 'style="' + excelFormats.text + '"';
						},
						classes: 'bgNoColor'
					},
					{
						label: 'Devol',
						name: 'ivadev',
						width: 38,
						align: 'right',
						formatter: 'number',
						hidden: false,
						formatoptions: {},
						summaryTpl: "{0}",
						summaryType: sumNotNC,
						summaryRound: '2',
						summaryRoundType: 'round'
					}, // set the formula to calculate the summary type
					{
						label: '&nbsp;',
						width: 15,
						name: 'act',
						align: "center",
						classes: 'bgNoRight bgNoColor',
						formatter: 'gridButton',
						formatoptions: {
							title: "Quitar",
							action: 'deleteRow',
							data: 'id',
							type: 'danger',
							icon: 'remove'
						}
					},
				],
				height: 350,
				caption: '&nbsp;',
				footerrow: true,
				userDataOnFooter: true,
				gridview: true,
				rownumbers: false,
				viewrecords: true,
				pginput: false,
				pgbuttons: false,
				pgtext: "Mostrando {0} Documentos."
				//loadComplete: function(){ jgrid.setGridSummary(['base','iva','ivadev'],{autret:''},true); }                          
				//          groupingView: {
				//                groupField: ["proveedor"],groupColumnShow: [true],
				//                groupText: ["<div><span style='float:left;'> {1} Documento(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  <b style='position: absolute;right: 25px;'>Total: $ {importe} <b></div>"],
				//                groupOrder: ["asc"],groupSummary: [true],groupCollapse: false
				//          },grouping: false

			}, true, '#listPager');
		});

		function deleteRow(id) {
			//8console.log(id);
			jgrid.delRowData(id);
			jgrid.setGridSummary(['ivadev', 'base', 'iva'], {
				autret: '<div style="text-align:right">TOTALES:</div>'
			});
		}

		function saveXml() {
			var data = {
				saveAtsXml: true,
				data: $.jsonParser($('#list').getGridBatch())
			};
			//console.log(data);
			$.saveDataJson('', data, function(r) {
				$.downloadFile(r.xml, 'Devolucion_Iva.xml');
				$("#loader").hide();
			});
		}

		function sumNotNC(v, n, obj) {
			return isNaN(v) ? 0 : (obj['tipoComprobante'] === '04' ? -1 * v : v);
		}
	</script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>

</HTML>
<?Php
/* Cerrado de las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
