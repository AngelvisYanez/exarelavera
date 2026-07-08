<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por lotes
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaciï¿½n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_nota.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
/** 
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Cccc;
/**
 * Evita el reenvio 
 */
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if (isset($dataReport)) {
    $full = !isset($resumido) || !(isset($resumido) && $resumido == 'true');

    $responce['success'] = false;
    if ($tipo == 'true') $b = '1px';
    else $b = '0.1pt';
    $table['{body}'] = '';
    $table['{caption}'] = $caption;
    $table['{empresa}'] = $Ses_Emp_Nom;
    $fecha = explode('-', $hoy);
    $table['{fecha}'] = dias(1) . '' . $fecha[2] . ' de ' . mes($fecha[1], 1) . ' de ' . $fecha[0];
    $saldoGeneral = 0;
    foreach ($dataReport as $provee) {
        $saldoProvee = 0;
        $saldoFacturas = 0;
        $table['{body}'] = $table['{body}'] . '<tr><td colspan="7" style="border: ' . $b . ' solid #000;"><b>Cliente:</b> ' . $provee['Cliente'] . '</td> 
                <td style="border: ' . $b . ' solid #000;text-align:center;">Total</td>
                <td style="border: ' . $b . ' solid #000;text-align:center;">Abonos</td>
                <td style="border: ' . $b . ' solid #000;text-align:center;">Saldo</td>
                </tr>';
        foreach ($provee['Cpcs'] as $cuenta) {
            $Cpp_Data = $obBD_con1->getRowConsulta(31, $cuenta, $obBD_conexion);
            if (empty($Cpp_Data)) $Cpp_Data = $obBD_con1->getRowConsulta(46, $cuenta, $obBD_conexion);
            $saldo = $Cpp_Data['total'] * 1;
            $saldoFacturas = $saldoFacturas + $Cpp_Data['total'] * 1;
            $saldoTotalFact = $saldoTotalFact + $Cpp_Data['total'] * 1;
            if ($full) $table['{body}'] = $table['{body}'] . '<tr><td style="font-weight:bold;"  colspan="2">' . $Cpp_Data['Tic_Des'] . '</td>  	    
                    <td style="font-weight:bold;" >' . $Cpp_Data['Caj_Fec'] . '</td> 
                    <td style="font-weight:bold;" colspan="4">Fact.: ' . $Cpp_Data['Vet_Num'] . ' -  ' . $provee['Cliente'] . '</td>
                    <td style="text-align:center;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($Cpp_Data['total'], 2) . '</td>
		        </tr>';

            $pagoRetencion = false;
            if ($Cpp_Data['Cpc_Cod'] != NULL) {
                $cancelaciones = $obBD_con1->getArrayConsulta(7, $Cpp_Data['Cpc_Cod'], $obBD_conexion);
                foreach ($cancelaciones as $pago) {

                    $abrevi = $obBD_con1->getRowConsulta(53, $pago['Pag_Cod'], $obBD_conexion);
                    $banco = NULL;
                    $PagAbr = $abrevi['Pag_Abr'];

                    if ($PagAbr != 'RET') {
                        if ($PagAbr != 'EF')
                            $banco = $obBD_con1->getRowConsulta(38, $pago['Dcc_Cod'], $obBD_conexion);
                        $saldo = $saldo - $pago['Cpc_Val'];
                        if ($full) $table['{body}'] = $table['{body}'] . '<tr>
                            <td style="font-weight:bold;border-right: ' . $b . ' solid #000;">&gt;</td>
                            <td>' . $pago['Com_Codigo'] . '</td>
                            <td style="text-align:center;">' . $pago['Cpc_Fec'] . '</td>
                            <td>' . $PagAbr . '</td>
                            <td style="mso-number-format:&#39;@&#39;;">' . ($banco != NULL ? $banco['Che_Num'] : '') . '</td>
                            <td style="white-space:nowrap;overflow:hidden;">' . ($banco != NULL ? $banco['Che_Cta'] . '/' . $banco['Banco'] : '') . '</td>
                            <td style="text-align:center;">' . ($banco != NULL ? $banco['Che_Fec'] : '') . '</td>
                            <td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;" colspan="2">' . number_format($pago['Cpc_Val'], 2) . '</td>
                            <td></td>
                        </tr>';
                    }
                }
            }

            if ($pagoRetencion == false) {
                $Retenciones1 = $obBD_con1->getArrayConsulta(32, $Cpp_Data['Vet_Cod'], $obBD_conexion);
                $Retenciones2 = $obBD_con1->getArrayConsulta(33, $Cpp_Data['Vet_Cod'], $obBD_conexion);
                $Retenciones = array_merge($Retenciones1, $Retenciones2);
                foreach ($Retenciones as $ret) {
                    $saldo = $saldo - round($ret['retencion'], 2);
                    if ($full) $table['{body}'] = $table['{body}'] . '<tr><td style="font-weight:bold;border-right: ' . $b . ' solid #000;">&gt;</td><td style="color:gray;">--------------</td><td style="text-align:center;">' . $ret['Ret_Fec'] . '</td><td>' . $ret['tipo'] . '</td><td colspan="2" style="mso-number-format:&#39;@&#39;;">' . $ret['Ret_Num'] . '</td><td></td><td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;" colspan="2">' . number_format($ret['retencion'], 2) . '</td><td></td></tr>';
                }
            }

            if ($full) $table['{body}'] = $table['{body}'] . '<tr><td style="word-wrap: break-word;" colspan="6"><strong>Obs:</strong> ' . $Cpp_Data['Vet_Obs'] . '</td><td colspan="3" style="text-align:right;border-top: ' . $b . ' solid #000;">SALDO DOCUMENTO:</td><td style="border-top: ' . $b . ' solid #000;text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldo, 2) . '</td></tr><tr><td colspan="9" style="height:20px;"></td></tr>';
            $saldoProvee = $saldoProvee + $saldo;
            $saldoAbono = $saldoFacturas - $saldoProvee;
        }
        if ($full) $table['{body}'] = $table['{body}'] . '<tr><td colspan="9" style="height:10px;"></td></tr>';
        if ($full) {
            $table['{body}'] = $table['{body}'] . '<tr>
    	  <td></td>
	  <td></td>
	  <td></td>   
	  <td></td>
	  <td></td>        
    	  <td colspan="4" style="border-top: ' . $b . ' solid #000; border-bottom: 3px double #000; font-weight:bold;text-align:right;">TOTAL CLIENTE: ' . $provee['Cliente'] . ' &nbsp;&gt;&gt;</td>
  	  <td style="border-bottom: 3px double #000;text-align:right;border-top: ' . $b . ' solid #000; mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoProvee, 2) . '</td>
	</tr>
	<tr>
	<td colspan="9"></td>
	</tr>';
        } else {
            $table['{body}'] = $table['{body}'] . '<tr>
    	  <td colspan="7"></td>
	  <td style="text-align:center;">' . number_format($saldoFacturas, 2) . '</td>
          <td style="text-align:center;">' . number_format($saldoAbono, 2) . '</td>
     	  <td style="text-align:center;">' . number_format($saldoProvee, 2) . '</td>
        </tr>
	<tr>
	<td colspan="9"></td>
	</tr>';
        }
        $saldoGeneral = $saldoGeneral + $saldoProvee;
    }
    if ($full) {
        $table['{rowtotal}'] = $table['{rowtotal}'] . '<tr>
	<td colspan="8" style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;border-right:0;">TOTAL GENERAL:</td>       
    	<td colspan="2" style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;border-left:0;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoGeneral, 2) . '</td>
	</tr>';
    } else {
        $table['{rowtotal}'] = $table['{rowtotal}'] . '<tr>
	<td colspan="7" style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;border-right:0;">TOTAL GENERAL:</td> 
	<td style="font-weight:bold;text-align:center; border-bottom: 3px double #000; border-top:1px solid #000;border-right:0;">' . number_format($saldoTotalFact, 2) . '</td> 
	<td style="font-weight:bold;text-align:center; border-bottom: 3px double #000; border-top:1px solid #000;border-left:0;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoTotalFact - $saldoGeneral, 2) . '</td>
	<td style="font-weight:bold;text-align:center; border-bottom: 3px double #000; border-top:1px solid #000;border-left:0;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoGeneral, 2) . '</td>
	</tr>';
    }
    //$table['{saldoFinal}']=number_format($saldoGeneral,2);
    $responce['html'] = reporteHtml($table, 'tes_pri_ccpp_cobros.html');
    $responce['success'] = true;

    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}
if (isset($provAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(3, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0)
        $responce['rows'] =  $obBD_con1->getArrayConsulta(3, $data, $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}
if (isset($provAjax)) {
    $contar = $obBD_con1->getRowConsulta(1, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*', $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(1, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*' . $pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

if (isset($ajaxComprobante)) {
    if (!isset($txt_fec_ini)) $txt_fec_ini = '';
    if (!isset($txt_fec_fin)) $txt_fec_fin = '';
    $responce['rows'] = $obBD_con1->getArrayConsulta(8, $Ses_Emp_Cod . '*' . $Cli_Cod . '*' . $Pec_Cod . '*' . $txt_fec_ini . '*' . $txt_fec_fin . '*' . $op_opciones, $obBD_conexion);
    if ($op_opciones != 'T')
        foreach ($responce['rows'] as $key => $item) {
            if ($op_opciones == '1') {
                utf8_encode_deep($responce['rows']);
            }
            if ($op_opciones == '2') {
                utf8_encode_deep($responce['rows']);
            }
        }
    $responce['rows'] = array_values($responce['rows']);
    $responce['success'] = true;
    $responce['records'] = count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

if (isset($ajaxSubgrid)) {
    $responce['rows'] = $obBD_con1->getArrayConsulta(7, $ajaxSubgrid, $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    $responce['records'] = count($responce['rows']);
    echo json_encode($responce);
    exit();
}

if (isset($detAjax)) {
    $responce['success'] = false;
    $responce['com'] = $obBD_con1->getRowConsulta(9, $Com, $obBD_conexion);
    $responce['pag'] = $obBD_con1->getRowConsulta(30, $Cpc . '*' . $Com, $obBD_conexion);
    $responce['asi']['rows'] = $obBD_con1->getArrayConsulta(10, $Com, $obBD_conexion);
    $responce['asi']['records'] = count($responce['asi']['rows']);
    $responce['che']['rows'] = $obBD_con1->getArrayConsulta(11, $Cpc . '*' . $Com, $obBD_conexion);
    $responce['che']['records'] = count($responce['che']['rows']);
    $responce['link_rec'] = "/tesoreria/FRONT/tes_pri_recibocobro_1.0.php?Com_Cod=$Com";
    utf8_encode_deep($responce);
    $responce['success'] = true;
    echo json_encode($responce);
    exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>

<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid.php") ?>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <style>

    </style>
</HEAD>

<BODY>
    <div id="set1">
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table" style="table-layout:fixed;">
            <tr class="BarraTitulo">
                <td colspan="2" height="10">&raquo; Historial De Cr&eacute;ditos Otorgados </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div id="comp1">
                        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
                            <tr>
                                <td>
                                    <FIELDSET>
                                        <LEGEND>
                                            <label class="Titulos2">Seleccionar Cliente</label>
                                        </LEGEND>
                                        <form id="provFormTemp">
                                            <div class="segmento">C&eacute;dula/R.U.C.:</div>
                                            <div class="datasegmento">

                                                <input id="docu" name="search" maxlength="13" onkeydown='if (event.keyCode === 13) $.SearchOrDialog("#provDialog",selectProvee);' type="text" class="search ui-corner-all" placeholder="Ingrese Cedula/R.U.C." title="Buscar Cliente Por Documento o Descripci&oacute;n" autofocus />
                                                <input type="text" name="op_opciones" value="c" style="display: none;" />
                                                <input id="PrvCodBus" type="hidden" name="Cli_Cod" value="" />
                                                <a onclick="$('#provDialog').dialog('open');/*$('#docu').removeAttr('readOnly');*/" title="B&uacute;squeda de Clientes" class="btn btn-success btn-mini"><i class=" icon-check icon-white"></i></a>
                                                <a onclick="selectProvee();" title="Quitar Cliente" class="btn btn-success btn-mini"><i class=" icon-eject icon-white"></i></a>

                                            </div>
                                        </form>
                                        <div class="segmento">Cliente:</div>
                                        <div class="datasegmento"><input id="lblProv" type="text" class="label ui-widget-content ui-corner-all" readonly /></div><br />
                                        <div class="segmento">Direcci&oacute;n:</div>
                                        <div class="datasegmento"><input id="lblDirec" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                                    </FIELDSET>



                                </td>
                                <td>
                                    <FIELDSET>
                                        <LEGEND>
                                            <label class="Titulos2">Filtros</label>
                                        </LEGEND>
                                        <form id="formCompTemp" action="javascript:$('#list').Search('#formCompTemp','ajaxComprobante');setCaption();">
                                            <input type="hidden" name="Cli_Cod" value="" />
                                            <div class="segmento">Tipo de pago:</div>
                                            <div class="datasegmento">
                                                <select class="ui-corner-all" name="op_opciones" id="op_opciones">
                                                    <option value="">Todos</option>
                                                    <option value="1">Contado</option>
                                                    <option value="2">Credito</option>
                                                </select>
                                            </div>
                                            <div>
                                                <div class="segmento">
                                                    Desde:
                                                </div>
                                                <div id="rangeDates" class="datasegmento">
                                                    <input name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="focus ui-corner-all" style="text-align: center;" />&nbsp;&nbsp;&nbsp;&nbsp;
                                                    Hasta:<input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="focus ui-corner-all" style="text-align: center;" />
                                                </div>
                                            </div>
                                            <div>
                                                <div class="segmento">Per&iacute;odo:</div>
                                                <div class="datasegmento">
                                                    <select name="Pec_Cod" id="Pec_Cod" onchange="if($('#Pec_Cod').val()!==''){$('#rangeDates').addClass('disabled').find('input').attr('disabled','disabled');}else{$('#rangeDates').removeClass('disabled').find('input').removeAttr('disabled');}" class="ui-corner-all">
                                                        <?php $rs_perio = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
                                                        foreach ($rs_perio as $row) { ?>
                                                            <option value="<?php echo $row['Pec_Cod']; ?>" data-inicio="<?php echo $row['Pec_Fei']; ?>" data-fin="<?php echo $row['Pec_Fef']; ?>"><?php echo $row['Anio']; ?>
                                                            </option>
                                                        <?php   } ?>
                                                        <option value="">Por Fecha</option>
                                                    </select>&nbsp;&nbsp;&nbsp;&nbsp;
                                                    <button type="button" onclick="this.form.submit()" class="btn btn-success" style="height: 27px;" title="Ejecutar B&uacute;squeda">
                                                        <i class="icon-search icon-white"></i>
                                                        <span>Buscar</span>
                                                    </button>
                                                </div>

                                            </div>
                                        </form>
                                    </FIELDSET>
                                </td>
                            </tr>
                            <tr>

                                <td colspan="2" height="389" align="left" valign="top">
                                    <FIELDSET>
                                        <LEGEND>
                                            <label class="Titulos2">Resultados de la b&uacute;squeda</label>
                                        </LEGEND>
                                        <div id="grillaComp">
                                            <table id="list"></table>
                                            <div id="listPager"></div>
                                        </div>
                                        <div style="padding:15px;">
                                            <button onclick="exportar(true)" title="Imprimir Reporte" type="button" class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></button>
                                            <button onclick="exportar(false)" class="btn btn-primary start" title="Descargar archivo de Excel"> <i class="icon-share icon-white"></i> <span>Excel</span></button>
                                            <!--<button type="button" class="btn btn-primary start" onclick="exportarExcel('Exportar')"> <i class="icon-share icon-white"></i> <span>Excel</span></button>-->
                                        </div>
                                        <script type="text/javascript">
                                            var tipo = 'lista';

                                            function buscaCedula() {
                                                var array = {
                                                    'search': $('#docu2').val(),
                                                    'op_opciones': 'C'
                                                };
                                                $.SearchOrDialogArray("#provDialog", selectProvee, array);
                                            }

                                            function selectProvee(data) {
                                                if (typeof data === 'undefined') {
                                                    $("#lblProv").val('');
                                                    $("#lblDirec").val('');
                                                    $("input[name='Cli_Cod']").val('');
                                                    $("#docu").val('');
                                                    $('#PrvCodBus').val('');
                                                    $('#list').Search('#formCompTemp', 'ajaxComprobante');
                                                } else {
                                                    if (tipo === 'lista') {
                                                        $("#lblProv").val(data['cliente']);
                                                        $("#lblDirec").val(data['Prs_Dir']);
                                                        $("input[name='Cli_Cod']").val(data['Cli_Cod']);
                                                        $("#docu").val(data['Prs_Ced']);
                                                        $("#provDialog").dialog("close");
                                                        $('#list').Search('#formCompTemp', 'ajaxComprobante');
                                                        //$("#docu").attr("readOnly","readOnly");
                                                    }
                                                    if (tipo === 'pago') {
                                                        $("#lblProvee2").val(data['cliente']);
                                                        $("#cod_pvr").val(data['Cli_Cod']);
                                                        $("#provDialog").dialog("close");
                                                    }
                                                }
                                                setCaption();
                                            }

                                            function setCaption() {
                                                var caption = '';
                                                caption = "Historial de Cr&eacute;ditos Otorgados - ";
                                                if ($('#Pec_Cod').val() === '') caption = caption + ' Desde ' + $('#txt_fec_ini').val() + ' Hasta ' + $('#txt_fec_fin').val();
                                                else caption = caption + ' Periodo ' + $('#Pec_Cod').find('option:selected').text();
                                                if ($('#PrvCodBus').val() !== '') caption = caption + ' - ' + $('#lblProv').val();
                                                $('#list').jqGrid('setCaption', caption + '<div style="float:right;"> <label>Resumido: </label> <input id="resumido" type="checkbox" value="S"  /> </div>');
                                            }

                                            function clearFooter() {
                                                public $footRow = $("#grillaComp .ui-jqgrid-sdiv .footrow");

                                                $footRow.find('>td[aria-describedby="list_subgrid"]').css("border-right-color", "transparent");
                                                $footRow.find('>td[aria-describedby="list_Com_Codigo"]').css("border-right-color", "transparent");
                                                $footRow.find('>td[aria-describedby="list_Caj_Fec"]').css("border-right-color", "transparent");
                                                $footRow.find('>td[aria-describedby="list_Cpc_Ven"]').css("border-right-color", "transparent");
                                                $footRow.find('>td[aria-describedby="list_Vet_Num"]').css("border-right-color", "transparent");

                                                $footRow.find('>td[aria-describedby="list_subgrid"]').css("background-color", "white");
                                                $footRow.find('>td[aria-describedby="list_Com_Codigo"]').css("background-color", "white");
                                                $footRow.find('>td[aria-describedby="list_vencimiento"]').css("background-color", "white");
                                                $footRow.find('>td[aria-describedby="list_Caj_Fec"]').css("background-color", "white");
                                                $footRow.find('>td[aria-describedby="list_Cpc_Ven"]').css("background-color", "white");
                                                $footRow.find('>td[aria-describedby="list_Vet_Num"]').css("background-color", "white");
                                                $footRow.find('>td[aria-describedby="list_proveedor"]').css("background-color", "white");
                                            }

                                            function updateSaldos(grid) {
                                                var rows = grid.jqGrid('getRowData');
                                                for (var i = 0; i < rows.length; i++) {
                                                    if (rows[i].act === "Yes") {
                                                        grid.jqGrid("setCell", rows[i].Cpp_Cod, "Pago", rows[i].Saldo);
                                                        /*console.log(rows[i].Saldo);*/
                                                    } else {
                                                        grid.jqGrid("setCell", rows[i].Cpp_Cod, "Pago", "0.00");
                                                    }
                                                }
                                            }

                                            function updateTotals(grid) {
                                                var abonos = 0,
                                                    saldos = 0,
                                                    rows = grid.jqGrid('getRowData'); //alert( grid.jqGrid('getCol', 'Com_Val', false, 'sum'));
                                                for (var i = 0; i < rows.length; i++) {
                                                    abonos = abonos + parseFloat(rows[i]['Abono']);
                                                    saldos = saldos + parseFloat(rows[i]['Saldo']);
                                                }
                                                grid.jqGrid('footerData', 'set', {
                                                    vencimiento: "<div style='text-align:right;'>TOTALES:</div>",
                                                    Vet_Tot: grid.jqGrid('getCol', 'Vet_Tot', false, 'sum'),
                                                   Abono: grid.jqGrid('getCol', 'Abono', false, 'sum'),
                                                    Saldo: grid.jqGrid('getCol', 'Saldo', false, 'sum')
                                                });
                                                /*  grid.jqGrid('footerData', 'set', {
                                                      Abono: "" + abonos
                                                  }   );*/
                                                 /* grid.jqGrid('footerData', 'set', {
                                                      Saldo: "" + saldos
                                                  }  );*/

                                            }
                                            $(document).ready(function() {
                                                $.createDateRange('#txt_fec_ini', '#txt_fec_fin');
                                                var compGrid = $("#list");
                                                compGrid.jqGrid({
                                                    url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                                    mtype: "GET",
                                                    datatype: "local",
                                                    regional: 'es', //ajaxRowOptions: { async: true },
                                                    //postData: $("#form1").getData("ajaxGrid"),
                                                    autowidth: true,
                                                    shrinkToFit: true,
                                                    height: 270,
                                                    caption: ' ',
                                                    hidegrid: false,
                                                    cmTemplate: {
                                                        sortable: false
                                                    },
                                                    colModel: [{
                                                            label: 'C&oacute;d.Int.',
                                                            name: 'Vet_Cod',
                                                            key: true,
                                                            width: 15,
                                                            align: "center",
                                                            hidden: true
                                                        },
                                                        {
                                                            label: 'C&oacute;d.Int.',
                                                            name: 'Asi_Cod',
                                                            width: 15,
                                                            align: "center",
                                                            hidden: true
                                                        },
                                                        {
                                                            label: 'C&oacute;d.Int.',
                                                            name: 'Cli_Cod',
                                                            width: 15,
                                                            align: "center",
                                                            hidden: true
                                                        },
                                                        {
                                                            label: 'Tipo',
                                                            name: 'Tic_Des',
                                                            align: "center",
                                                            width: 45
                                                        },
                                                        {
                                                            label: 'Forma',
                                                            name: 'For_Des',
                                                            align: "center",
                                                            width: 35
                                                        },
                                                        {
                                                            label: 'Fecha Emis.',
                                                            name: 'Caj_Fec',
                                                            align: "center",
                                                            width: 35
                                                        },
                                                        //{ label: 'Fecha Venc.', name: 'Cpc_Ven', align:"center", width: 35},
                                                        //{ label: 'Vencimiento', name: 'vencimiento', align:"center", width: 40},
                                                        {
                                                            label: 'Total',
                                                            name: 'Vet_Tot',
                                                            width: 40,
                                                            align: 'right',
                                                            formatter: 'currency',
                                                            decimalPlaces: '2',
                                                            summaryRound: 2,
                                                            formatoptions: {
                                                                prefix: '$ ',
                                                                thousandsSeparator: ',',
                                                                decimalSeparator: '.'
                                                            },
                                                            summaryTpl: "Total: {0}",
                                                            summaryType: "sum"
                                                        },
                                                        {
                                                            label: 'Abono',
                                                            name: 'Abono',
                                                            width: 30,
                                                            align: 'right',
                                                            decimalPlaces: '2',
                                                            summaryRound: 2,
                                                            formatoptions: {
                                                                prefix: '$ ',
                                                                thousandsSeparator: ',',
                                                                decimalSeparator: '.'
                                                            },
                                                            formatter: function(cellValue, options, rowObject) {
                                                                if (!parseFloat(rowObject.Abono)) {
                                                                    if (rowObject.For_Cod == 1) {
                                                                        rowObject.Abono = rowObject.Vet_Tot;
                                                                        return $.fn.fmatter.call(this, "currency", rowObject.Abono, options);
                                                                    } else {
                                                                        rowObject.Abono = 0;
                                                                        return $.fn.fmatter.call(this, "currency", rowObject.Abono, options);
                                                                    }
                                                                }
                                                            },
                                                            unformat: function(cellValue, options, cell) {
                                                                var opt = $.extend(true, {}, options);
                                                                opt.colModel.formatter = "currency";
                                                                delete opt.colModel.unformat;
                                                                return $.unformat.call(this, cell, opt);
                                                            },
                                                            summaryTpl: "Total: {0}",
                                                            summaryType: "sum" // set the formula to calculate the summary type 
                                                        },
                                                        {
                                                            label: 'Saldo',
                                                            name: 'Saldo',
                                                            width: 30,
                                                            align: 'right',
                                                            decimalPlaces: '2',
                                                            summaryRound: 2,
                                                            formatoptions: {
                                                                prefix: '$ ',
                                                                thousandsSeparator: ',',
                                                                decimalSeparator: '.'
                                                            },
                                                            formatter: function(cellValue, options, rowObject) {
                                                                if (!parseFloat(rowObject.Saldo)) {
                                                                    rowObject.Saldo = parseFloat(rowObject.Vet_Tot) - parseFloat(rowObject.Abono);
                                                                }
                                                                if (parseFloat(rowObject.Abono) === parseFloat(rowObject.Vet_Tot))
                                                                    return 'Pagado';
                                                                else
                                                                    return $.fn.fmatter.call(this, "currency", rowObject.Saldo, options);
                                                            },
                                                            unformat: function(cellValue, options, cell) {
                                                                var opt = $.extend(true, {}, options);
                                                                opt.colModel.formatter = "currency";
                                                                delete opt.colModel.unformat;
                                                                return $.unformat.call(this, cell, opt);
                                                            },
                                                            summaryTpl: "Total: {0}",
                                                            summaryType: "sum" // set the formula to calculate the summary type 
                                                        },
                                                        {
                                                            label: 'No. Documento',
                                                            name: 'Vet_Num',
                                                            width: 50,
                                                            align: "center"
                                                        },
                                                        {
                                                            label: 'Observaci&oacute;n',
                                                            name: 'Vet_Obs',
                                                            width: 60
                                                        },
                                                        {
                                                            label: 'Cliente',
                                                            name: 'proveedor',
                                                            width: 80
                                                        },
                                                        {
                                                            label: '&nbsp;',
                                                            name: 'act1',
                                                            width: 15,
                                                            align: 'center',
                                                            viewable: false,
                                                            formatter: function(cellvalue, options, rowObject) {
                                                                return '<span class="btn btn-info btn-mini" title="Ver" type="button" onclick="$(\'#list\').viewGridRow(\'' + rowObject.Vet_Cod + '\');"><i class="icon-info-sign icon-white"></i></span><span>&nbsp;&nbsp;</span>';
                                                            }
                                                        }
                                                    ],
                                                    rowNum: 10000000,
                                                    pager: "#listPager",
                                                    gridview: true,
                                                    rownumbers: true,
                                                    viewrecords: true,
                                                    pgbuttons: false,
                                                    pgtext: null,
                                                    footerrow: true,
                                                    userDataOnFooter: false,
                                                    onSelectRow: function(rowid, e) {
                                                        compGrid.resetSelection();
                                                    },
                                                    loadComplete: function(data) {
                                                        var total = data.records;
                                                        for (var i = 0; i < total; i++) {
                                                            if (data.rows[i]['Saldo'] === 'Vencido')
                                                                $("#" + data.rows[i].Vet_Cod).css("background", "#FADDDD");
                                                            if (data.rows[i]['Saldo'] === 'Pagado')
                                                                $("#" + data.rows[i].Vet_Cod).css("background", "#DDFAE2");
                                                        }
                                                        updateTotals($(this));
                                                    },
                                                    subGridOptions: {
                                                        "plusicon": "ui-icon-triangle-1-e",
                                                        "minusicon": "ui-icon-triangle-1-s",
                                                        "openicon": "ui-icon-arrowreturn-1-e",
                                                        "reloadOnExpand": false,
                                                        "selectOnExpand": true
                                                    }
                                                    /*,subGrid: true,multiselect: false,
                                                                                subGridRowExpanded: function(subgrid_id, row_id) {
                                                                                    var subgrid_table_id = subgrid_id+"_t";         
                                                                                    $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                                                                                    $("#"+subgrid_table_id).jqGrid({
                                                                                            url:"<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>?ajaxSubgrid="+row_id, datatype: "json", regional: 'es',
                                                                                            autowidth : true, shrinkToFit: true,cmTemplate: {sortable:false},//colNames: ['No','Item','Qty','Unit','Line Total'],
                                                                                            colModel: [
                                                                                                    {label:'Cod.Int.',name:"Cpc_Cod",width:80,key:true,align:"center",hidden:true},
                                                                                                    {label:'Cod.Int.',name:"Com_Cod",width:80,key:true,align:"center",hidden:true},                                                
                                                                                                    {label:'No. Compr.',name:"Com_Codigo",width:45,align:"center"},
                                                                                                    {label:'Fecha',name:"Cpc_Fec",width:45,align:"center"},
                                                                                                    {label:'Valor',name:"Cpc_Val",width:45, align: 'right', formatter:'currency', decimalPlaces: '2', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}},
                                                                                                    {label:'Observación',name:"Cpc_Obs",width:100},
                                                                                                    {label:'Tipo',name:"Pag_Des",width:45,align:"center"},                      
                                                                                                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                                                                                            formatter:function (cellvalue, options, rowObject) { 
                                                                                                                var clic='selectDetalle('+rowObject.Cpc_Cod+','+rowObject.Com_Cod+');';
                                                                                                                return  '<span class="btn btn-info btn-mini" title="Seleccionar" onclick=\''+clic+'\'><i class="icon-info-sign icon-white"></span>'; 
                                                                                                            }
                                                                                                        }
                                                                                            ],beforeSelectRow: function(rowid, e) {return false;},
                                                                                            rowNum:10000000, pager: "",height: '100%'
                                                                                    });                                
                                                                                }*/
                                                });
                                                compGrid.navGrid('#listPager', {
                                                    edit: false,
                                                    add: false,
                                                    del: false,
                                                    search: false,
                                                    refresh: true,
                                                    view: false,
                                                    position: "left",
                                                    cloneToTop: false
                                                });
                                                //                            .jqGrid('navButtonAdd',"#listPager",{ caption: "Exportar &nbsp;",buttonicon: "ui-icon-arrowthickstop-1-s",title:"Exportar Excel",
                                                //                                onClickButton: function() {
                                                //                                    compGrid.jqGrid('exportGridExcel',{nombre:"CreditosOtorgados",hoja:"CCCC"});	
                                                //                                },position: "last"
                                                //                            });
                                                compGrid.jqGrid('bindKeys');
                                                clearFooter();
                                                $('#rangeDates').addClass('disabled').find('input').attr('disabled', 'disabled');
                                            });
                                        </script>
                                    </FIELDSET>
                                </td>
                            </tr>
                        </table>
                    </div>

                </td>
            </tr>
        </table>
    </div>

    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
    <div id="provDialog" title="B&uacute;squeda de Clientes">
        <form>
            <fieldset>
                <legend><label class="Titulos2">B&uacute;squeda de Cliente</label></legend>
                <table border="0">
                    <tr>
                        <td width="205"><input name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" />
                            <span class="LetraNegra"><strong>Apellido</strong></span>
                        </td>
                        <td width="266"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
                            <span class="LetraNegra"><strong>C&eacute;dula/R.U.C.</strong></span>
                        </td>
                    </tr>
                </table>
                <table height="36" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;">
                            <div align="right"><strong>B&uacute;squeda</strong></div>
                        </td>
                        <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cliente a buscar..." autofocus /><input type="text" style="display:none" /></td>
                        <td width="109" align="center">
                            <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar cuenta">
                                <i class="icon-search icon-white"></i>
                                <span>Buscar</span>
                            </button>
                        </td>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            $.createSearchDialog('#provDialog', [{
                    label: 'Cód.Int.',
                    name: 'Cli_Cod',
                    key: true,
                    hidden: true,
                    viewable: true
                },
                {
                    label: 'C&eacute;dula/R.U.C.',
                    name: 'Prs_Ced',
                    width: 50
                },
                {
                    label: 'Cliente',
                    name: 'cliente',
                    width: 190,
                    cellattr: function(rowId, tv, rawObject, cm, rdata) {
                        return 'style="white-space: normal;"';
                    }
                },
                {
                    label: 'Direcci&oacute;n',
                    name: 'Prs_Dir',
                    hidden: true,
                    viewable: true
                },
                {
                    label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                    name: 'act1',
                    width: 18,
                    align: 'center',
                    viewable: false,
                    formatter: function(cellvalue, options, rowObject) {
                        var clic = 'selectProvee($("#provGrid").jqGrid("getRowData",' + rowObject.Cli_Cod + '))';
                        return '<span class="btn btn-success btn-mini" title="Seleccionar" onclick=\'' + clic + '\'><i class="icon-arrow-right icon-white"></span>';
                    }
                }
            ]);

        });
    </script>
    <!-- FIN DEL DIALOGO PROVEEDOR-->
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jqgrid.ExcelExport.js"></script>


    <!--INICIO DEL DIALOGO DETALLE PAGO -->
    <div id="pagoDialog" title="Detalle Pago">

        <div>
            <div style="width: 50%;display: inline;float:left;">
                <fieldset>
                    <legend><label class="Titulos2">Datos Comprobante</label></legend>
                    <div class="row">
                        <div class="segmento">Compr. No.:</div>
                        <div class="datasegmento"><input id="lblComp2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                    </div>
                    <div class="row">
                        <div class="segmento">Fecha:</div>
                        <div class="datasegmento"><input id="lblComFe2" type="text" class="label medium ui-widget-content ui-corner-all" style="text-align: center;" readonly /></div>
                    </div>
                    <div class="row">
                        <div class="segmento">Valor:</div>
                        <div class="datasegmento"><input id="lblComVal2" type="text" class="text medium ui-widget-content ui-corner-all" style="text-align: right;" readonly />
                            <a id="impRecib" target="_blank" href="" style="display: inline;" title="Imprimir Recibo"><span class="btn btn-primary btn-mini start"> <i class="icon-print icon-white"></i> <span>Recibo</span></span> </a>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div style="width: 50%;display: inline;float:right;">
                <fieldset>
                    <legend><label class="Titulos2">Datos del Cliente</label></legend>
                    <div class="row">
                        <div class="segmento">Cédula:</div>
                        <div class="datasegmento"><input id="lblCed2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                    </div>
                    <div class="row">
                        <div class="segmento">Cliente:</div>
                        <div class="datasegmento"><input id="lblProv2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                    </div>
                    <div class="row">
                        <div class="segmento">Dirección:</div>
                        <div class="datasegmento"><input id="lblDirec2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                    </div>
                </fieldset>
            </div>
            <div class="row" style="padding-top: 5px;padding-bottom: 15px;">
                <fieldset>
                    <legend><label class="Titulos2">Observación</label></legend>
                    <div class="datasegmento" style="width:95%;"><input id="lblConce2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                </fieldset>
            </div>
        </div>
        <div id="tabs">
            <ul>
                <li><a href="#detalleComp">Detalle Pago</a></li>
                <li><a href="#asienComp">Asiento Contable</a></li>
                <li><a href="#chequeComp">Cheques</a></li>
            </ul>
            <div id="asienComp" class="condensed">
                <table id="asiento"></table>
                <div id="asientoPager"></div>
            </div>
            <div id="chequeComp" class="condensed">
                <table id="cheque"></table>
                <div id="chequePager"></div>
            </div>
            <div id="detalleComp" style="clear: both;">
                <div class="row">
                    <div class="segmento">Factura:</div>
                    <div class="datasegmento"><input id="lblFac2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="row">
                    <div class="segmento">Vencimiento:</div>
                    <div class="datasegmento"><input id="lblVen2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="row">
                    <div class="segmento">Observación:</div>
                    <div class="datasegmento"><input id="lblObsV2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="row">
                    <div class="segmento">Tipo Pago:</div>
                    <div class="datasegmento"><input id="lblTipPa2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="row">
                    <div class="segmento">Fecha Pago:</div>
                    <div class="datasegmento"><input id="lblFePa2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="row">
                    <div class="segmento">Valor Pago:</div>
                    <div class="datasegmento"><input id="lblVaPa2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            $.createDialog('#pagoDialog', 415, 650);
            $("#tabs").tabs();
            $.createDatePickers("input[name='Com_Fec']");
            $.createDatePickers("input[name='Che_Fec']");
            $('#asiento').jqGrid({
                datatype: "local",
                regional: 'es', //ajaxRowOptions: { async: true },                             
                width: 618,
                height: 75,
                postData: {
                    CheListAjax: true
                },
                caption: 'Asiento Contable',
                cmTemplate: {
                    sortable: false
                },
                colModel: [{
                        label: 'Cód.Int.',
                        name: 'Asi_Cod',
                        key: true,
                        width: 15,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'Tipo',
                        name: 'Asi_Deh',
                        hidden: true
                    },
                    {
                        label: 'Código',
                        name: 'Pld_Cdc',
                        width: 45
                    },
                    {
                        label: 'Cuenta',
                        name: 'Pld_Des',
                        width: 130
                    },
                    {
                        label: 'Glosa',
                        name: 'Glosa',
                        width: 130
                    },
                    {
                        label: 'Debe',
                        name: 'Debe',
                        width: 65,
                        align: 'right',
                        formatter: 'currency',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.',
                            defaultValue: ''
                        },
                        summaryType: "sum"
                    },
                    {
                        label: 'Haber',
                        name: 'Haber',
                        width: 65,
                        align: 'right',
                        formatter: 'currency',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.',
                            defaultValue: ''
                        },
                        summaryType: "sum"
                    }
                ],
                loadComplete: function(data) {
                    $(this).jqGrid('footerData', 'set', {
                        Glosa: "<div style='text-align:right;'>TOTALES:</div>",
                        Debe: $(this).jqGrid('getCol', 'Debe', true, 'sum'),
                        Haber: $(this).jqGrid('getCol', 'Haber', true, 'sum')
                    }, true);
                },
                rowNum: 10000,
                gridview: true,
                viewrecords: true,
                footerrow: true,
                userDataOnFooter: false
            });
            $.clearFooterDiario("#asiento");
            $('#cheque').jqGrid({
                datatype: "local",
                regional: 'es', //ajaxRowOptions: { async: true },                             
                width: 618,
                height: 97,
                postData: {
                    CheListAjax: true
                },
                caption: 'Cheques Recibidos',
                cmTemplate: {
                    sortable: false
                },
                colModel: [{
                        label: 'Cód.Int.',
                        name: 'Che_Cod',
                        key: true,
                        hidden: true,
                        viewable: true
                    },
                    {
                        label: 'Fecha',
                        name: 'Che_Fec',
                        key: true,
                        width: 50,
                        align: "center"
                    },
                    {
                        label: 'Num.',
                        name: 'Che_Num',
                        key: true,
                        width: 30,
                        align: "center"
                    },
                    {
                        label: 'Banco',
                        name: 'Bak_Des',
                        width: 100,
                        title: 'Cuenta Bancaria'
                    },
                    {
                        label: 'No. Cuenta',
                        name: 'Che_Cta',
                        width: 90
                    },
                    {
                        label: 'Valor',
                        name: 'Che_Val',
                        key: true,
                        width: 60,
                        align: "right",
                        formatter: 'currency',
                        decimalPlaces: '2',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.'
                        }
                    }
                ],
                rowNum: 10000,
                gridview: true,
                viewrecords: true
            });
            $.createDialog('#successDialog', 150, 550);
        });

        function selectDetalle(Cpc, Com) {

            $.post("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", {
                detAjax: true,
                Cpc: Cpc,
                Com: Com
            }, function(response) {
                if (response['success'] === true) {
                    $("#lblComp2").val(response['com']['Com_Num']);
                    $("#lblComFe2").val(response['com']['Com_Fec']);
                    $("#lblComVal2").val(response['com']['Com_Val']);
                    $("#lblConce2").val(response['com']['Com_Obs']);
                    $("#lblCed2").val(response['com']['Prs_Ced']);
                    $("#lblProv2").val(response['com']['Prs_Ape'] + ' ' + response['com']['Prs_Nom']);
                    $("#lblProv2").attr('title', response['com']['Prs_Ape'] + ' ' + response['com']['Prs_Nom']);
                    $("#lblDirec2").val(response['com']['Prs_Dir']);
                    $("#lblDirec2").attr('title', response['com']['Prs_Dir']);

                    $("#lblFac2").val(response['pag']['Vet_Num']);
                    $("#lblVen2").val(response['pag']['Cpc_Ven']);
                    $("#lblObsV2").val(response['pag']['Cpc_Obs']);
                    $("#lblTipPa2").val(response['pag']['Pag_Des']);
                    $("#lblFePa2").val(response['pag']['Cpc_Fec']);
                    $("#lblVaPa2").val('$ ' + response['pag']['Cpc_Val']);

                    $("#asiento").jqGrid("clearGridData");
                    $("#asiento").jqGrid('setGridParam', {
                        rowNum: response['asi']['records']
                    });
                    $("#asiento").jqGrid('setGridParam', {
                        data: response['asi']['rows'],
                        page: 1,
                        records: response['asi']['records']
                    }).trigger('reloadGrid');
                    $("#cheque").jqGrid("clearGridData");
                    $("#cheque").jqGrid('setGridParam', {
                        rowNum: response['che']['records']
                    });
                    $("#cheque").jqGrid('setGridParam', {
                        data: response['che']['rows'],
                        page: 1,
                        records: response['che']['records']
                    }).trigger('reloadGrid');

                    $('#impRecib').attr('href', response['link_rec']);
                    $('#pagoDialog').dialog('open');
                } else {
                    $.alert(response['message']);
                }
            }, 'json').fail(function(error) {
                $.alert("El Servidor ha fallado en responder!");
            });

        }

        function exportar(banTipo) {
            var batch = new Array();
            var grid = $("#list");
            var ids = grid.jqGrid('getDataIDs');
            for (var i = 0; i < ids.length; i++) {
                var datos = grid.jqGrid('getRowData', ids[i]),
                    ban = true;
                for (var j = 0; j < batch.length; j++) {
                    if (datos['Cli_Cod'] === batch[j]['Cli_Cod']) {
                        ban = false;
                    }
                }
                if (ban) batch.push({
                    Cli_Cod: datos['Cli_Cod'],
                    Cliente: datos['proveedor']
                });
            }
            for (var i = 0; i < batch.length; i++) {
                batch[i]['Cpcs'] = new Array();
                for (var j = 0; j < ids.length; j++) {
                    var datos = grid.jqGrid('getRowData', ids[j]);
                    if (datos['Cli_Cod'] === batch[i]['Cli_Cod']) {
                        batch[i]['Cpcs'].push(datos['Cpc_Cod']);
                    }
                }
            }
            //console.log(batch);       
            if (batch.length > 0) {
                $.post("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", {
                    resumido: $('#resumido').prop('checked'),
                    dataReport: batch,
                    tipo: banTipo,
                    caption: grid.parent().parent().parent().find('.ui-jqgrid-title').text()
                }, function(response) {
                    if (response['success'] === true) {
                        $('#Exportar').html(response['html']);
                        if (banTipo)
                            $(response['html']).printElement({
                                pageTitle: '<?Php echo $Ses_Sys_Nom; ?>'
                            });
                        else
                            $.downloadFile($.exportarExcelBlob(response['html'], 'CCPP'), 'CtaPorCobrar-' + $.getDate() + '.xls');
                    } else {
                        $.alert(response['message']);
                    }
                }, 'json').fail(function(error) {
                    $.alert("El Servidor ha fallado en responder!");
                });
            } else {
                $("#list").startGridEdit();
                $.alert("No hay Datos!");
            }
        }
    </script>
    <!-- FIN DEL DIALOGO DETALLE PAGO -->
    <div id="output"></div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <div id="Exportar" style="display: none;"></div>
</BODY>

</HTML>