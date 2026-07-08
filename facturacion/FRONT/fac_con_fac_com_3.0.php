<?php

/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factu.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Factu;

$hoy = date("Y-m-d");
$mes = date("m");

/* Consulta del tipo de proveedores */
if (isset($provAjax)) {
    $obBD_con1->getPageGridJson(2, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
}
/* Consulta productos */
if (isset($prodReportAjax)) {
    $page = $obBD_con1->getPageGridJson('producto.selectWhere', $_GET, $obBD_conexion);
}
if (isset($ajaxProd)) {
    require_once('../LOGICA/tes_log_kardex.php');
    $obBD_con1 =  new Class_Log_Datos_Kar;
    $Ite_Cod = $Pro_Cod;
    $ini = $hoy;
    $responce['success'] = true;
    $kardex1 = $obBD_con1->getArrayConsulta(1048, $ini . '*' . $Ite_Cod, $obBD_conexion);
    if (count($kardex1) == 1 && $kardex1[0]['Saldo'] !== 0 && $kardex1[0]['Stock'] != 0) {
        $kardex1[0]['Promedio'] = round(($kardex1[0]['Saldo'] / $kardex1[0]['Stock']), 6);
    } else {
        $kardex1[0]['Promedio'] = 0;
        $kardex1[0]['Saldo'] = 0;
        $kardex1[0]['Stock'] = 0;
    }
    list($ann, $mes, $dia) = explode('-', $ini);
    $kardex1[0]['Kar_Det'] = '<b>Saldo al ' . $dia . ', de ' . mes($mes, 1) . ', ' . $ann . '</b>';
    $responce['stocks'] = $kardex1[0];

    $responce['prod'] = $obBD_con1->getRowConsulta(1051, $Ite_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
    $obBD_con1->echoJson($responce);
}
if (isset($ajaxKardex)) {
    require_once('../LOGICA/tes_log_kardex.php');
    $obBD_con1 =  new Class_Log_Datos_Kar;
    $Ite_Cod = $Pro_Cod;

    $responce['rows'] = $obBD_con1->getArrayConsulta(1054, $Ses_Emp_Cod . '*' . $Ses_Suc_Cod . '*' . $Ite_Cod . '*' . $Fec_Ini . '*' . $Fec_Fin, $obBD_conexion);
    $responce['success'] = true;
    $responce['records'] = count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}
/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(8, $Ses_Emp_Cod, $obBD_conexion);

/* busqueda de documentos */
if (isset($searchDocument)) {
    $data = $_GET;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getPageGrid(34, $data, $obBD_conexion);
    if ($responce['records'] > 0) {
        foreach ($responce['rows'] as &$row) {
            $row['Cpp_Edit'] = 'S';
            $row['Cpp_Min'] = 0;
            if (!empty($row['Cpp_Cod'])) {
                $Pagos1 = $obBD_con1->getRowConsulta(57, $row['Cpp_Cod'] . '*' . 'A', $obBD_conexion);
                if ($Pagos1['total'] * 1 > 0) {
                    $row['Cpp_Det'] = 'S'; //tiene pagos activos
                    $Pagos1 = $obBD_con1->getRowConsulta(57, $row['Cpp_Cod'] . '*' . 'A' . '*' . 'SUM', $obBD_conexion);
                    $row['Cpp_Min'] = round($Pagos1['total'] * 1, 2);
                }
                $Pagos2 = $obBD_con1->getRowConsulta(57, $row['Cpp_Cod'], $obBD_conexion);
                if ($Pagos2['total'] * 1 > 0) $row['Cpp_Edit'] = 'N'; //tiene algun pago vinculado
            } else { // Caja Chica
                $caja = $obBD_con1->getRowConsulta(58, $row['Cop_Cod'], $obBD_conexion);
                if ($caja['total'] * 1 > 0) $row['Rcc_Det'] = 'S';
                $caja_pend = $obBD_con1->getRowConsulta(58, $row['Cop_Cod'] . '*' . 'P', $obBD_conexion);
                if ($caja_pend['total'] * 1 > 0) $row['Rcc_Pen'] = 'S';
            }
            if ($configs['Cof_Con'] == 'S' && !empty($row['Com_Cod'])) {
                $cuentas = $obBD_con1->getRowConsulta((!empty($row['Cpp_Cod']) ? (!empty($row['Rcc_Pen']) ? 70 : 37) : 39), $row['Com_Cod'], $obBD_conexion);
                $row['Pld_Cod_Pag'] = $cuentas['Pld_Cod'];
                $otras_comp = $obBD_con1->getRowConsulta(65, $row['Com_Cod'], $obBD_conexion);
                if ($otras_comp['total'] * 1 > 1) $row['Com_Edit'] = 'N';
            }
        }
        unset($row);
    }
    $obBD_con1->echoJson($responce);
}
/* Consulta los totales */
if (isset($ajaxTotales)) {
    $FILTERS = array();
    // array_push($FILTERS, $op_est != 'I' ? 'isActive' : 'isInactive');
    // Filtro por estado (Activo, Inactivo, Todos)
    switch ($op_est) {
        case 'A': // Activos
            array_push($FILTERS, 'isActive');
            break;
        case 'I': // Inactivos
            array_push($FILTERS, 'isInactive');
            break;
        case 'T': // Todos (no aplica filtro)
        default:   // Por defecto muestra todos
            break;
    }

    if ($range == 'S') array_push($FILTERS, 'byDateRange');
    if ($Chk_Ret != 'T') array_push($FILTERS, 'hasRetencion');
    if ($Tic_Cod != 'T') array_push($FILTERS, 'byTipoCompr');
    if ($Tri_Cod != 'T') array_push($FILTERS, 'bySustento');
    if ($cedul == 'S') array_push($FILTERS, 'byPrvCod');
    if ($Suc_Cod != 'T') array_push($FILTERS, 'bySucCod');
    if ($For_Cod != 'T') array_push($FILTERS, 'byTipPago');
    // if ($Pun_Cod != 'T') array_push($FILTERS, 'byPunCod');

    $response = $obBD_con1->getPageGrid('compras.selectWhere', array_merge($_GET, array('where' => array(), 'setWhere' => array_merge($FILTERS, array('setUsuario', 'setRetencion', 'setTotales')))), $obBD_conexion);
    $totalGlobal = $obBD_con1->getRowConsulta('compras.selectWhere', array_merge($_GET, array('where' => array(), 'unsetCols' => array('Vnd_Cod', 'Pun_Cod', 'Vendedor'), 'setWhere' => array_merge($FILTERS, array('setEmpCod', 'setUsuario', 'setRetencion', 'setTotalesGlobales')))), $obBD_conexion);
    $response['userdata'] = array_merge($totalGlobal, array('Cop_Obs' => '<div class="txtRight">TOTAL GLOBAL:</div>', 'Tot_Renta' => 0, 'Tot_Iva' => 0));

    foreach ($response['rows'] as &$row) {
        $row['proveedor'] = $row['Proveedor'];
        $row['vendedor'] = $row['Vendedor'];
        $comprobante = $obBD_con1->getRowConsulta('comprobantes.getComprobanteByCopCod', $row['Cop_Cod'], $obBD_conexion);
        if (!is_null($comprobante)) {
            $row['Com_Codigo'] = $comprobante['Com_Codigo'];
        }

        $pagos = $obBD_con1->getRowConsulta("compras.1", $row['Cop_Cod'], $obBD_conexion);
        $row['Forma_Pago'] = ($pagos['total'] > 0) ? 'Credito' : 'Contado';

        if ($row['Ret_Data'] == "S") {
            $ret_data = $obBD_con1->getRowConsulta('retencion.selectWhere', array('where' => array('retencion.Ret_Cod' => $row['Ret_Cod']), 'group' => 'retencion.Ret_Cod', 'setWhere' => array('setTotales')), $obBD_conexion);
            $row = array_merge(array('Ret_Fec' => $ret_data['Ret_Fec'], 'Ret_Aut' => $ret_data['Ret_Aut'], 'Secuencia' => $ret_data['Secuencia'], 'Autorizacion' => $ret_data['Autorizacion'], 'Tot_Iva' => $ret_data['Tot_Iva'], 'Tot_Renta' => $ret_data['Tot_Renta']), $row);
            $response['userdata']['Tot_Renta'] += ($ret_data['Tot_Renta'] * 1);
            $response['userdata']['Tot_Iva'] += ($ret_data['Tot_Iva'] * 1);
        }
    }
    $obBD_con1->echoJson($response);
}
/* Consulta el detalle del documento */
if (isset($docDetalle)) {
    $resp = array('success' => true, 'Cop_Cod' => $Cop_Cod, 'Cop_Fec' => $Cop_Fec, 'Ret_Cod' => $Ret_Cod, 'rows' => array());
    if (!empty($Cop_Cod)) {
        $resp['items'] = $obBD_con1->getArrayConsulta(35, $Cop_Cod, $obBD_conexion);
        if (count($resp['items']) == 0)
            $resp = array('success' => false, 'message' => 'No se encontraron items en el detalle del documento!');
        else {
            foreach ($resp['items'] as $r) if ($r['Iva_Por'] * 1 > 0) {
                $resp['Iva_Cod'] = $r['Iva_Cod'];
                break;
            }
            if (!empty($Ret_Cod)) {
                $retencion = $obBD_con1->getArrayConsulta(59, $Ret_Cod, $obBD_conexion);
                foreach ($resp['items'] as &$it) {
                    foreach ($retencion as $r) if ($it['Cop_Int'] == $r['Ret_Int']) foreach ($r as $k => $v) $it[($r['Ren_Ret'] == 'R' ? 'Ret_' : 'Iva_') . $k] = $v;
                }
                unset($it);
            }
            if ($configs['Cof_Con'] == 'S' && !empty($Com_Cod)) {
                $iva = $obBD_con1->getRowConsulta(36, $Com_Cod, $obBD_conexion);
                $resp['Pld_Cod'] = $iva['Pld_Cod'];
            }
        }
    } else $resp['success'] = false;
    $obBD_con1->echoJson($resp);
}

if (isset($cargarReportes)) {
    try {
        $response['reportes'] = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        $response['success'] = true;
    } catch (Exception $ex) {
        $response['message'] = $ex->getMessage();
    }
    $obBD_con1->echoJson($response);
}

$rs_tip_compr = $obBD_con1->getArrayConsulta(5, '', $obBD_conexion);
$rs_periodo = $obBD_con1->getArrayConsulta(33, $Ses_Emp_Cod, $obBD_conexion);
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Compras Consultar [EXA]"; ?></TITLE>
    <meta charset="utf-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript">
        var anula = true,
            consultar = true,
            gridFact, index, Cof_Con = '<?php echo $configs['Cof_Con']; ?>',
            cod_banano = <?php echo $cod_banano; ?>;
    </script>
    <script type="text/javascript" src="../VALIDACIONES/fac_val_factu.js?gh=a12"></script>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Consultar Documentos de Compras</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <input type="hidden" id="is_consultar" name="is_consultar" value="CNS">
            <div id="documentoMain" style="visibility: hidden;">
                <?php include '../COMPONENTES/facComFormEdit.php'; ?>
                <div class="row">
                    <div class="col-xs-12">
                        <button class="btn btn-sm btn-inverse btn-main" onclick="$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
                    </div>
                </div>
            </div>
            <div id="documentoSearch" class="ui-tabs ui-tab-fix noPaddingH">
                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                    <li><a href="#tabs-1"><i class="glyphicon glyphicon-list-alt"></i> Individual</a></li>
                    <li><a href="#tabs-2"><i class="glyphicon glyphicon-th-list"></i>  Totales</a></li>
                    <li><a href="#tabs-3"><i class="fa fa-cart-arrow-down" style="font-size: 1.3em;"></i> Por Producto</a></li>
                </ul>
                <div id="tabs-1" class="ui-tabs-panel">
                    <?php include '../COMPONENTES/facComFormSearch.php'; ?>
                    <script>
                        function editDocument(doc) {
                            $('#Ret_Cod').val('');
                            $('#t_descuento').val(0);
                            $('.validate').find('i').removeAttr('class');

                            $('.formDatos').setData(doc, false);

                            $('#Cop_Des').val(doc['Cop_Des']);
                            $('#Ret_Num').data({
                                Ret_Num: doc['Ret_Num'],
                                Aut_Cod: doc['Aut_Cod'],
                                Aut_Sri: doc['Aut_Sri']
                            }).fieldValid();
                            $('#btnAnula').data({
                                Ret_Cod: doc['Ret_Cod'],
                                Com_Cod: doc['Com_Cod'],
                                Cop_Cod: doc['Cop_Cod']
                            });
                            $.getDataJson('', {
                                docDetalle: true,
                                Cop_Cod: doc['Cop_Cod'],
                                Com_Cod: doc['Com_Cod'],
                                Cop_Fec: doc['Cop_Fec'],
                                Ret_Cod: doc['Ret_Cod']
                            }, function(resp) {
                                $('#documento').setRows(resp['items']).startGridEdit();
                                $.each(resp['items'], function(i, v) {
                                    updateRowItem({
                                        rowId: v['index']
                                    });
                                });
                                addItem({});
                                $('#t_descuento').val($.toFixed($("#t_subtotal").val() * 1 * ('0' + $('#Cop_Des').val()) / 100));
                                updateDocument();
                                $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
                                $('#documentoMain').find(':input:not(.btn-main)').attr({
                                    readonly: true,
                                    tabindex: '-1'
                                }).end().find('select,td.btn,button:not(.btn-ret,.btn-main),input').attr({
                                    disabled: true
                                }).unbind('click').end().find('select,input').addClass('readOnly');
                                $('#Iva_Cod,#Iva_Pag').hide();
                                $('#documento').find('tbody tr:last').hide();
                            });
                            var credito = ($.varValid(doc['Cpp_Cod']) && doc['Cpp_Cod'] !== '');
                            $('#For_Cod').val(credito ? 2 : $.varValid(doc['Rcc_Pen']) ? 3 : 1);
                            $('.pagoCredito')[credito ? 'show' : 'hide']();
                            selectProvee2(doc);
                            $('#Aut_Cod').html(doc['Aut_Cod'] || '');

                            //Marcar Check box
                            var checkbox = document.getElementById('Ret_Asu');
                            if (doc.Ret_Asu === 'S') {
                                checkbox.checked = true;
                                checkbox.value = 'S';
                            } else { //Caso contrario lo desactiva
                                checkbox.checked = false;
                            }

                            var c_tresxmil = document.getElementById('c_tresxmil');
                            if(doc.Cop_imp_com && doc.Cop_imp_comb != "0.0000") {
                                c_tresxmil.checked = true;
                                $('#t_imp_comb').val(doc.Cop_imp_comb);
                            } else { //Caso contrario lo desactiva
                                c_tresxmil.checked = false;
                            }

                            var ch_prop = document.getElementById('ch_prop');
                            var tprop = document.getElementById('t_prop');
                            if (doc.Cop_Prop && doc.Cop_Prop != "0.00") {
                                ch_prop.checked = true;
                                $('#t_prop').val(doc.Cop_Prop);
                            } else { //Caso contrario lo desactiva
                                ch_prop.checked = false;
                            }

                            var ch_adic = document.getElementById('ch_adic');
                            var inputAdic = document.getElementById('t_adic');
                            if(doc.Cop_Adic && doc.Cop_Adic != "0.00") {
                                ch_adic.checked = true;
                                $('#t_adic').val(doc.Cop_Adic);
                            } else { //Caso contrario lo desactiva
                                ch_adic.checked = false;
                            }
                        }
                    </script>
                </div>
                <div id="tabs-2" class="ui-tabs-panel" style="display: none">
                    <form id="formSearchReport" action="javascript:if(!$('#op_range').is(':checked') && !$('#op_cedul').is(':checked')) $.alert('Debe seleecionar al menos un filtro!'); else  $('#ReportResumen').Search('#formSearchReport', 'ajaxTotales');" class="form-horizontal normal">
                        <div class="row">
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtros:</legend>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Filtro:</label>
                                        <div class="col-sm-10">
                                            <span class="radioset">
                                                <input id="op_range" name="range" type="checkbox" onchange="setFilter('range',$(this));" value="S" checked><label for="op_range">Rango de Fechas</label>
                                                <input id="op_cedul" name="cedul" type="checkbox" onchange="setFilter('cedul',$(this));" value="S"><label for="op_cedul">&nbsp;Proveedor&nbsp;</label>
                                            </span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            <label>Estado:</label>&nbsp;
                                            <span class="radioset">
                                                <input id="op_est1" name="op_est" type="radio" value="A" checked='checked' style="cursor:pointer"><label for="op_est1"> Activas </label>
                                                <input id="op_est2" name="op_est" type="radio" value="I" style="cursor:pointer"><label for="op_est2">Anuladas</label>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-sm">Rango:</label>
                                        <div class="col-sm-10">
                                            <div class="input-group input-group-sm">
                                                <span class="range input-group-addon alert-info">Desde</span>
                                                <input type="text" name="Fec_Ini" id="txt_fec_ini" class="form-control range" required="" />
                                                <span class="range input-group-addon alert-info">Hasta</span>
                                                <input type="text" name="Fec_Fin" id="txt_fec_fin" class="form-control range" required="" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group cedul" style="display:none;">
                                        <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                                        <div class="col-xs-6" id="ProvSearchFormTemp">
                                            <input name="Prv_Cod" type="text" style="display:none;" />
                                            <input name="op_opciones" type="text" value="c" style="display: none;">
                                            <div class="input-group input-group-xs">
                                                <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#provDialog',selectProvee,'#ProvSearchFormTemp');" type="text" placeholder="Ingrese Proveedor..." class="form-control input-xs clearable dialogSearch cedul" tabindex="1" disabled="" />
                                                <span class="input-group-btn">
                                                    <button type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs cedul" title="Buscar Proveedor" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                        <label class="col-xs-4 control-label label-xs">Oblig.Contab:&nbsp;<i id="Prv_Con_Search" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label>
                                    </div>
                                    <div class="form-group cedul" style="display:none;">
                                        <label class="col-xs-2 control-label label-xs required">Proveedor:</label>
                                        <div class="col-xs-6"><span name="proveedor" class="form-control input-xs databind datatitle"></span></div>
                                        <label class="col-xs-4 control-label label-xs">Contr.Especial:&nbsp;<i id="Prv_Esp_Search" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label>
                                    </div>
                                    <div class="form-group center">
                                        <button type="submit" class="btn btn-success btn-xs" title="Buscar"><i class="glyphicon glyphicon-search"></i> Buscar</button>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Tipos Filtrado:</legend>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Sucursal:</label>
                                        <div class="col-sm-5">
                                            <?php $sucursal = $obBD_con1->getArrayConsulta('sucursal.selectWhere', array('clean' => true, 'unsetCols' => true, 'addCols' => array('sucursal' => array('Suc_Cod', 'Suc_Des')), 'where' => array('Emp_Cod' => $Ses_Emp_Cod)), $obBD_conexion); ?>
                                            <select name="Suc_Cod" class="form-control input-xs">
                                                <option value="T" selected="">
                                                    << TODAS >>
                                                </option>
                                                <?php foreach ($sucursal as $s) { ?>
                                                    <option value="<?php echo $s['Suc_Cod']; ?>"><?php echo $s['Suc_Des']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <label class="col-sm-1 control-label label-xs" style="margin-left: 28px;">Orden:</label>
                                        <div class="col-sm-3">
                                            <select name="CustomOrderBy" class="form-control input-xs">
                                                <option value="" selected="">NINGUNO</option>
                                                <option value="Cop_Fec ASC">Fecha ASC</option>
                                                <option value="Cop_Fec DESC">Fecha DESC</option>
                                                <option value="Proveedor ASC">Proveedor</option>
                                                <option value="Tic_Sri ASC">Tipo Doc.</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Tipo&nbsp;Doc.:</label>
                                        <div class="col-sm-5">
                                            <select name="Tic_Cod" class="form-control input-xs">
                                                <option value="T" selected="">
                                                    << TODOS >>
                                                </option>
                                                <?php foreach ($rs_tip_compr as $row_rs_tip_compr) { ?>
                                                    <option value="<?php echo $row_rs_tip_compr['Tic_Cod'] ?>"><?php echo mb_convert_encoding($row_rs_tip_compr['Tic_Sri'], 'UTF-8', 'ISO-8859-1') . ' - ' . mb_convert_encoding($row_rs_tip_compr['Tic_Des'], 'UTF-8', 'ISO-8859-1'); ?></option>

                                                    <!--option value="<?php echo $row_rs_tip_compr['Tic_Cod'] ?>"><?php echo $row_rs_tip_compr['Tic_Sri'] . '-' . $row_rs_tip_compr['Tic_Des']; ?></option-->
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <label class="col-sm-2 control-label label-xs" style="margin-left: -6px;">Agrupar Por:</label>
                                        <div class="col-sm-3" style="margin-left: -20px;">
                                            <select name="CustomGroupBy" class="form-control input-xs">
                                                <option value="" selected=""><< Sin Agrupar >></option>
                                                <option value="Agr_Prv"> - Proveedor -</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Sustento:</label>
                                        <div class="col-sm-5">
                                            <select name="Tri_Cod" id="Tri_Cod" class="form-control input-xs">
                                                <option value="T">
                                                    << TODOS >>
                                                </option>
                                                <?php foreach ($rs_sustento as $row_rs_sustento) { ?>
                                                    <option value="<?php echo $row_rs_sustento['Tri_Cod']; ?>"><?php echo $row_rs_sustento['Tri_Sri'] . ' - ' . $row_rs_sustento['Tri_Des']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <!-- <div class="col-sm-4"><label class="label-xs"><input name="Chk_Ret" type="checkbox" id="Chk_Ret" class="check-big" value="S"><span>&nbsp; No Sujetas a Ret.</span></label></div> -->
                                        <div class="form-group" style="margin-bottom: -25px;">
                                            <label class="col-sm-2 control-label label-xs" style="margin-left: -12px;">Tiene Retención:</label>
                                            <div class="col-sm-3">
                                                <select name="Chk_Ret" class="form-control input-xs" style="margin-top: 5px; margin-left: -18px;">
                                                    <option value="T" selected=""> << Seleccione >></option>
                                                    <option value="S">SI</option>
                                                    <option value="NS">NO</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Forma de Pago:</label>
                                        <div class="col-sm-5">
                                            <select name="For_Cod" class="form-control input-xs">
                                                <option value="T" selected=""><< TODAS >></option>
                                                <option value="Contado">Contado</option>
                                                <option value="Credito">Crédito</option>
                                            </select>
                                        </div>
                                        <!-- <label class="col-sm-2 control-label label-xs" style="margin-left: -10px;">Punto de SRI:</label>
                                        <div class="col-sm-3" style="margin-left: -12px;">
                                            <?php $punto =   $obBD_con1->getArrayConsulta(1011, $Ses_Suc_Cod, $obBD_conexion); ?>
                                            <select name="Pun_Cod" class="form-control input-xs" id="selectPuntoSri">
                                                <option value="T" selected=""><< TODOS >></option>
                                                <?php foreach ($punto as $v) { ?>
                                                    <option value="<?php echo $v['Pun_Cod']; ?>" data-pun-sri="<?php echo $v['Pun_Sri']; ?>">
                                                        <?php echo '-- Punto de Emisión ' . $v['Pun_Sri'] . ' --'; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                            <input type="hidden" name="Pun_Sri" id="inputPunSri" value="">
                                            <script>
                                                document.getElementById('selectPuntoSri').addEventListener('change', function() {
                                                    const selected = this.options[this.selectedIndex];
                                                    const punSri = selected.dataset.punSri || '';
                                                    document.getElementById('inputPunSri').value = punSri;
                                                });
                                            </script>
                                        </div> -->
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-sm-12">
                            <div>
                                <table id="ReportResumen"></table>
                                <div id="ReportResumenPager"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tabs-3" class="ui-tabs-panel" style="display: none">
                    <div class="row">
                        <form id="formKardex" class="form-horizontal normal" action="javascript:$('#kardex').Search('#formKardex','ajaxKardex');">
                            <div class="col-xs-8">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Descripci&oacute;n Producto:</legend> <!-- Form Name -->
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs ">Descripci&oacute;n:</label>
                                                <div class="col-xs-10">
                                                    <div class="input-group input-group-xs">
                                                        <input type="text" name="Pro_Cod" data-prod="Pro_Cod" id="Pro_Cod" value="" style="display: none" />
                                                        <input id="producto" data-prod="Producto" type="text" class="form-control" placeholder="Seleccione un Producto ..." required readonly />
                                                        <span class="input-group-btn">
                                                            <button class="btn btn-success" onclick="$('#prodReportDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                                                        </span>
                                                    </div><!-- /input-group -->
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <!-- static input-->
                                            <div class="form-group">
                                                <label class="col-xs-4 control-label label-xs ">Marca:</label>
                                                <div class="col-xs-8"><span class="form-control input-xs" data-prod="Mar_Des"></span></div>
                                            </div>
                                            <!-- static input-->
                                            <div class="form-group">
                                                <label class="col-xs-4 control-label label-xs ">Adquisici&oacute;n:</label>
                                                <div class="col-xs-8">
                                                    <div class="input-group input-group-xs">
                                                        <span class="form-control input-xs" data-prod="Adq_Des"></span>
                                                        <span class="input-group-addon alert-info">IVA: </span>
                                                        <span class="input-group-addon"><i id="Iva_Search" class="glyphicon glyphicon-remove blue" style="font-size: 12px;"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Categor&iacute;a:</label>
                                                <div class="col-xs-9">
                                                    <div class="input-group input-group-xs">
                                                        <span class="input-group-addon alert-info" data-prod="Pro_Cdc"></span>
                                                        <span class="form-control input-xs" data-prod="Cat_Des"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">C&oacute;digo:</label>
                                                <div class="col-xs-9"><span class="form-control input-xs" data-prod="Pro_Bar"></span></div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-xs-4">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Estado Actual:</legend> <!-- Form Name -->
                                    <!-- static input-->
                                    <div class="form-group">
                                        <label class="col-xs-4 control-label label-xs ">Stock:</label>
                                        <div class="col-xs-8"><span class="form-control input-xs" data-kardex="Stock"></span></div>
                                    </div>
                                    <!-- static input-->
                                    <div class="form-group">
                                        <label class="col-xs-4 control-label label-xs ">Prec Prom.:</label>
                                        <div class="col-xs-8"><span class="form-control input-xs" data-kardex="Promedio"></span></div>
                                    </div>
                                    <!-- static input-->
                                    <div class="form-group">
                                        <label class="col-xs-4 control-label label-xs ">Saldo Actual:</label>
                                        <div class="col-xs-8"><span class="form-control input-xs" data-kardex="Saldo"></span></div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-xs-8">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-sm ">Desde:</label>
                                                <div class="col-xs-3">
                                                    <input name="Fec_Ini" type="text" id="ini" class="form-control input-sm">
                                                </div>
                                                <label class="col-xs-2 control-label label-sm ">Hasta:</label>
                                                <div class="col-xs-3">
                                                    <input name="Fec_Fin" type="text" id="fin" class="form-control input-sm">
                                                </div>
                                                <div class="col-xs-2">
                                                    <div class=""><button type="button" onclick="if($('#Pro_Cod').val()!==''){this.form.submit();$('#kardex').jqGrid('setCaption', $('#producto').val()+' - '+'Desde '+ $('#ini').val()+' Hasta '+$('#fin').val());}else{$.alert('Seleccione el Producto');}" class="btn btn-sm btn-success" title="Ejecutar Búsqueda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </form>
                        <div class="col-xs-12">
                            <div>
                                <table id="kardex"></table>
                                <div id="kardexPager"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
    <div id="provDialog" title="B&uacute;squeda de Proveedor">
        <form class="form-horizontal normal"> </form>
    </div>
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTO-->
    <div id="prodReportDialog" title="B&uacute;squeda de Productos">
        <script type="text/javascript">
            function selectProvee2(provee) {
                $('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Con'] === 'S' ? 'ok green' : 'remove blue'));
                $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Esp'] === 'S' ? 'ok green' : 'remove blue'));
            }

            function selectProvee(provee) {
                $('div.form-group.cedul').setData($.extend(provee, {
                    op_opciones: 'c'
                })).find('.dialogSearch').addClass('x');
                $('#Prv_Con_Search').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Con'] === 'S' ? 'ok green' : 'remove blue'));
                $('#Prv_Esp_Search').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Esp'] === 'S' ? 'ok green' : 'remove blue'));
                $('#provDialog').dialog('close');
            }

            function clearDocument() {
                $('.formDatos').setData({
                    op_opciones: 'c',
                    Cal_Inv: 'N'
                });
                $('#docuFormTemp').setData({
                    For_Cod: 1,
                    Tri_Cod: 2,
                    Cop_Fec: '<?php echo $hoy; ?>',
                    Com_Fec: '<?php echo $hoy; ?>'
                }).find(':input').attr('readonly');
                gridFact.clearGrid();
                $('#asumirRet').prop('checked', false).hide();
                addItem({});
            }

            function setFilter(cl, $t) {
                var ch = $t.is(':checked');
                $('span.' + cl)[ch ? 'addClass' : 'removeClass']('alert-info');
                $('span.' + cl)[!ch ? 'addClass' : 'removeClass']('alert-disabled');
                $('input.' + cl).prop('required', ch).prop('disabled', !ch);
                $('div.form-group.' + cl)[!ch ? 'hide' : 'show']();
            }

            function sumNotNC(v, n, obj) {
                return isNaN(v) ? 0 : (obj['Tic_Sri'] === '04' ? -1 * v : v);
            }
            $(function() {
                $('#documentoMain').css('visibility', '').hide();
                $("#tabs-1").hide();
                $("#tabs-2").show();


                var ReportResumen = $("#ReportResumen");
                ReportResumen.createGrid({
                    height: 230,
                    autowidth: true,
                    shrinkToFit: false,
                    datatype: 'local',
                    stateCol: 'Cop_Est',
                     caption: 'Resultados de la b&uacute;squeda ',
          
                    postData: $("#formTotales").getData("ajaxTotales"),
                    colModel: [
                        { label: 'Tip.Sri', name: 'Tic_Sri', width: 30, align: "center"/*, frozen: true,*/,
                            cellattr: function(rowId, val, rawObject, cm, rdata) {
                                return 'style="' + excelFormats.text + '"';
                            }
                        },
                        { label: 'Tip.Doc.', name: 'Tic_Des', width: "125px", align: "center"/*, frozen: true*/ },
                        { label: 'Sust.', name: 'Tri_Sri', width: 30, align: "center"/*, frozen: true*/,
                            cellattr: function(rowId, val, rawObject, cm, rdata) {
                                return 'style="' + excelFormats.text + '"';
                            }
                        },
                        // { label: 'Nro.Doc.', name: 'Cop_Num', width: "150px", align: "center"/*, frozen: true*/,
                        //     cellattr: function(rowId, val, rawObject, cm, rdata) {
                        //         return 'style="' + excelFormats.text + '"';
                        //     },
                        //     formatter: function(cellvalue, options, rowObject) {
                        //         // Escapamos los datos JSON para evitar problemas con caracteres especiales
                        //         var jsonData = JSON.stringify(rowObject).replace(/"/g, '&quot;').replace(/'/g, '&#39;');

                        //         return '<a href="javascript:void(0);" onclick="editDocument(' + jsonData + ');">' + cellvalue + ' <span style="color:#254463" class="glyphicon glyphicon-new-window"></span></a>';

                        //     }
                        // },
                        {  label: 'Nro.Doc.', name: 'Cop_Num', width: "150px", align: "center"/*, frozen: true*/,
                            cellattr: function(rowId, val, rawObject, cm, rdata) {
                                return 'style="' + excelFormats.text + '"';
                            },
                            formatter: function(cellvalue, options, rowObject) {
                                // Si CustomGroupBy está activo, mostrar "Doc. Agrupado" y no mostrar Cop_Num
                                var groupBy = $('[name="CustomGroupBy"]').val();
                                if (groupBy && groupBy !== "") {
                                    return "Doc. Agrupado";
                                }
                                // Escapamos los datos JSON para evitar problemas con caracteres especiales
                                var jsonData = JSON.stringify(rowObject).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                                return '<a href="javascript:void(0);" onclick="editDocument(' + jsonData + ');">' + cellvalue + ' <span style="color:#254463" class="glyphicon glyphicon-new-window"></span></a>';
                            }
                        },
                        { label: 'Est.', name: 'Cop_Est', width: "30px", align: "center", key: true/*, frozen: true*/},
                        { label: 'Autorizaci&oacute;n', name: 'Cop_Aut', width: "150px", align: "center"/*, frozen: true*/,
                            cellattr: function(rowId, val, rawObject, cm, rdata) {
                                return 'style="' + excelFormats.text + '"';
                            }
                        },
                        { label: 'Fecha', name: 'Cop_Fec', width: "75px", align: "center"/*, frozen: true*/ },
                        { label: 'C&eacute;dula/Ruc', name: 'Prs_Ced', width: "100px", align: "center"/*, frozen: true*/,
                            cellattr: function(rowId, val, rawObject, cm, rdata) {
                                return 'style="' + excelFormats.text + '"';
                            }
                        },
                        { label: 'C&oacute;d.', name: 'Cop_Cod', width: "50px", align: "center", key: true },
                        { label: 'Proveedor', name: 'Proveedor', width: "200px" },
                        { label: 'Observacion', name: 'Cop_Obs', width: "200px" },
                        { label: 'No Obj. IVA', name: 'NoIVA', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        { label: 'Base 0%', name: 'Sub_0', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        { label: 'Base 12%', name: 'Sub_12', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        { label: 'Base 15%', name: 'Sub_15', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        { label: 'Base 5%', name: 'Sub_5', width: "75", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        { label: 'Base 8%', name: 'Sub_8', width: "75", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        { label: 'Desc', name: 'Descu', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        { label: 'ICE', name: 'Ice_Tot', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        { label: 'IVA', name: 'Iva_Tot', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        { label: 'IRBPNR', name: 'Irbpnr', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        // { label: 'TresxMil', name: 'TresxMil', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        // { label: 'IVA Pres', name: 'IVAPres', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        // { label: 'Propina', name: 'Prop', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        // { label: 'Val.Adic.', name: 'Adic', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC'},
                        { label: 'TOTAL', name: 'Total', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                        { label: 'Reten.', name: 'Ret_Data', width: "50px", align: "center", formatter: 'truefalse',
                            formatoptions: {
                                yesMsg: function(o) {
                                    return 'Ret. Num.: <u class="blue">' + o.Secuencia + '</u>';
                                },
                                noMsg: ' ',
                                yesColor: function(o) {
                                    return o.Cop_Est === 'I' ? 'red' : 'green';
                                }
                            },
                            title: false,
                            cellattr: function(rowId, val, rawObject, cm, rdata) {
                                return 'style="' + excelFormats.text + '"';
                            }
                        },
                        // { label: 'Pdf', name: '', width: "50px", align: "center",
                        //     formatter: function(cellvalue, options, rowObject) {
                        //         if (rowObject.Ret_Cod) { // Asumiendo que 'Ret_Cod' contiene el identificador para generar el PDF
                        //             return '<a href="/facturacion/COMPONENTES/tesPdfElectronicos.php?type=RETENC&Doc_Cod=' + rowObject.Ret_Cod + '" target="_blank"><i style="font-size: 16px;" class="fa fa-file-pdf-o"></i></a>';
                        //         } else {
                        //             return '';
                        //         }
                        //     }
                        // },
                        // { label: 'Aut. Retenci&oacute;n', name: 'Autorizacion', width: "100px", align: "center",
                        //     cellattr: function(rowId, val, rawObject, cm, rdata) {
                        //         return 'style="' + excelFormats.text + '"';
                        //     }
                        // },
                        { label: 'Pdf', name: '', width: "50px", align: "center",
                            formatter: function(cellvalue, options, rowObject) {
                                // Si CustomGroupBy está activo, mostrar "Doc. Agrupado" y no mostrar el PDF
                                var groupBy = $('[name="CustomGroupBy"]').val();
                                if (groupBy && groupBy !== "") {
                                    return "-";
                                }
                                if (rowObject.Ret_Cod) { // Asumiendo que 'Ret_Cod' contiene el identificador para generar el PDF
                                    return '<a href="/facturacion/COMPONENTES/tesPdfElectronicos.php?type=RETENC&Doc_Cod=' + rowObject.Ret_Cod + '" target="_blank"><i style="font-size: 16px;" class="fa fa-file-pdf-o"></i></a>';
                                } else {
                                    return '';
                                }
                            }
                        },
                        { label: 'Aut. Retenci&oacute;n', name: 'Autorizacion', width: "100px", align: "center",
                            cellattr: function(rowId, val, rawObject, cm, rdata) {
                                return 'style="' + excelFormats.text + '"';
                            },
                            formatter: function(cellvalue, options, rowObject) {
                                // Si CustomGroupBy está activo, mostrar "Doc. Agrupado" y no mostrar el contenido
                                var groupBy = $('[name="CustomGroupBy"]').val();
                                if (groupBy && groupBy !== "") {
                                    return "-";
                                }
                                return cellvalue || '';
                            }
                        },
                        { label: 'Renta', name: 'Tot_Renta', width: "75px", align: "right", formatter: 'number' },
                        { label: 'Ret. Iva', name: 'Tot_Iva', width: "75px", align: "right", formatter: 'number' },
                        { label: 'Compr.', name: 'Com_Codigo', width: "50px", align: "center", formatter: 'truefalse',
                            formatoptions: {
                                yesMsg: function(o) {
                                    return 'Comprobante: <u class="blue">' + o.Com_Codigo + '</u>';
                                },
                                noMsg: ' ',
                                yesColor: function(o) {
                                    return o.Cop_Est === 'I' ? 'red' : 'green';
                                }
                            },
                            title: false
                        },
                        // { label: 'Pago', name: 'Forma_Pago', width: "60px", align: "center" },
                        { label: 'Pago', name: 'Forma_Pago', width: "60px", align: "center",
                            formatter: function(cellvalue, options, rowObject) {
                                // Si CustomGroupBy está activo, mostrar "Doc. Agrupado"
                                var groupBy = $('[name="CustomGroupBy"]').val();
                                if (groupBy && groupBy !== "") {
                                    return "Doc. Agrupado";
                                }
                                return cellvalue || '';
                            }
                        },
                        //{label: 'Pdf', name: '', width: "50px", align: "center"},
                        { label: '&nbsp;', name: 'act0', width: "30px", align: 'center', viewable: false, formatter: 'gridButton',
                            formatoptions: {
                                action: 'viewInfo',
                                title: 'Ver Documento',
                                icon: 'info-sign',
                                type: 'info'
                            },
                            title: false
                        }
                    ],
                    footerrow: true,
                    rowNum: 1000,
                    rowList: [1000, 5000, 10000, 15000, 20000],
                    userDataOnFooter: true,
                    totalPage: true,
                    totalCols: ['NoIVA', 'Sub_0', 'Sub_12', 'Sub_15', 'Sub_5', 'Sub_8', 'Descu', 'Ice_Tot', 'Iva_Tot', 'Irbpnr', 'Total', 'Tot_Renta', 'Tot_Iva'],
                    totalDefault: {
                        Cop_Obs: '<div class="txtRight">TOTAL PAGINA:</div>'
                    }
                }, false, "ReportResumenPager").jqGrid('setFrozenColumns').gridButtonsAdd([null, 
                    // { caption: "Exportar Excel&nbsp;", buttonicon: "download-alt",
                    //     onClickButton: function() {
                    //         ReportResumen.jqGrid('exportGridExcel', {
                    //             nombre: "Compras",
                    //             hoja: "HOJA 2",
                    //             footer: true
                    //         });
                    //     },
                    //     position: "last"
                    // }

                    {  caption: "Exportar Excel&nbsp;", buttonicon: "download-alt",
                        onClickButton: function() {
                            var columnasExcluir = [];
                            
                            if ($('#Chk_Ret').is(':checked') && $('#Chk_Ret').val() === 'S') {
                                columnasExcluir = [0, 5, 9, 23, 24, 25, 26, 27, 28, 30];
                            } else {
                                columnasExcluir = [0, 5, 9, 24, 28, 30];
                            }

                            var html = generarReporteHTML("ReportResumen", {
                                <?php
                                $empresa = $obBD_con1->getRowConsulta('empresas.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod)), $obBD_conexion);
                                $Emp_Nom = $empresa['Emp_Nom'];
                                ?>
                                titulo: "<?php echo $Emp_Nom; ?>",
                                subtitulo: "Reporte de Compras",
                                excluirColumnas: columnasExcluir,
                                camposTotales: ["NoIVA,", "Sub_0", "Sub_12", "Sub_15", "Sub_5", "Sub_8", 
                                            "Descu", "Ice_Tot", "Iva_Tot", "Irbpnr", "Total", 
                                            "Tot_Renta", "Tot_Iva"]
                            });

                            if (html) {
                                var fechaActual = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
                                var blob = new Blob(["\ufeff" + html], { type: "application/vnd.ms-excel;charset=utf-8" });
                                var url = URL.createObjectURL(blob);
                                var a = document.createElement("a");
                                a.href = url;
                                a.download = "Compras-" + fechaActual + ".xls";
                                a.click();
                                URL.revokeObjectURL(url);
                            }
                        }
                    },
                    { caption: "Imprimir&nbsp;", buttonicon: "print",
                        onClickButton: function() {
                            var columnasExcluir = [];
                            
                            if ($('#Chk_Ret').is(':checked') && $('#Chk_Ret').val() === 'S') {
                                columnasExcluir = [0, 5, 9, 23, 24, 25, 26, 27, 28, 30];
                            } else {
                                columnasExcluir = [0, 5, 9, 24, 28, 30];
                            }

                            var html = generarReporteHTML("ReportResumen", {
                                
                                <?php
                                $empresa = $obBD_con1->getRowConsulta('empresas.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod)), $obBD_conexion);
                                $Emp_Nom = $empresa['Emp_Nom'];
                                ?>
                                titulo: "<?php echo $Emp_Nom; ?>",
                                subtitulo: "Reporte de Compras",
                                excluirColumnas: columnasExcluir,
                                camposTotales: ["NoIVA","Sub_0", "Sub_12", "Sub_15", "Sub_5", "Sub_8", 
                                            "Descu", "Ice_Tot", "Iva_Tot", "Irbpnr", "Total", 
                                            "Tot_Renta", "Tot_Iva"]
                            });

                            if (html) {
                                var win = window.open('', '_blank');
                                win.document.write(html);
                                win.document.close();
                                win.focus();
                                win.print();
                            }
                        }
                    }
                ]);
            });

            function generarReporteHTML(gridId, opciones) {
            var grid = $("#" + gridId);
            var gridData = grid.jqGrid('getRowData');

            if (gridData.length === 0) {
            $.alert('No hay datos para procesar.');
            return null;
            }

            var excludedIndexes = opciones.excluirColumnas || [];
            var titulo = opciones.titulo || "Reporte";
            var subtitulo = opciones.subtitulo || "Reporte de Compras";
            var mostrarTotales = opciones.mostrarTotales !== false;
            var colModel = grid.jqGrid('getGridParam', 'colModel');
            var camposTotales = opciones.camposTotales || [];

            // Calcular totales
            var totals = {};
            camposTotales.forEach(function(key) {
            totals[key] = 0;
                gridData.forEach(function(row) {
                    // Check if the row corresponds to a credit note (e.g., Tic_Sri === '04')
                    if (row['Tic_Sri'] === '04') {
                        totals[key] -= parseFloat(row[key]) || 0; // Subtract for credit notes
                    } else {
                        totals[key] += parseFloat(row[key]) || 0; // Add for other rows
                    }
                });
            });


            var htmlContent = '<html><head><title>' + titulo + '</title>';
            htmlContent += '<style>';
            htmlContent += '@media print { tfoot {display: none !important;} }';
            htmlContent += 'table {width: 100%; border-collapse: collapse;}';
            htmlContent += 'th, td {border: 1px solid black; padding: 5px; text-align: left; font-size: 10px;}';
            htmlContent += 'th {background-color: #f2f2f2;}';
            htmlContent += '.totales-final {font-weight: bold; background-color: #eee;}';
            htmlContent += '.ajustar-texto { word-break: break-word; white-space: normal; max-width: 98px; font-size: 10px; }';
            htmlContent += '.formato-texto { mso-number-format:"\@"; }'; // Estilo específico para Excel
            htmlContent += '</style>';
            htmlContent += '</head><body>';

            // Encabezado con título y subtítulo
            htmlContent += '<h2 style="text-align: center;">' + titulo + '</h2>';
            htmlContent += '<h3 style="text-align: center;">' + subtitulo + '</h3>';
            if ($('input[name="Fec_Ini"]').val() || $('input[name="Fec_Fin"]').val()) {
            const formatDate = (date) => {
                const [year, month, day] = date.split('-');
                return `${day}-${month}-${year}`;
            };
            const formattedStartDate = $('input[name="Fec_Ini"]').val() ? formatDate($('input[name="Fec_Ini"]').val()) : '';
            const formattedEndDate = $('input[name="Fec_Fin"]').val() ? formatDate($('input[name="Fec_Fin"]').val()) : '';
            htmlContent += '<p style="text-align: center;"><strong>Desde:</strong> ' + formattedStartDate + ' &nbsp;&nbsp;&nbsp; <strong>Hasta:</strong> ' + formattedEndDate + '</p>';
            }

            htmlContent += '<table><thead><tr>';
            htmlContent += '<th>#</th>';

            var includedColumns = [];
            colModel.forEach(function(col, idx) {
            if (!col.hidden && excludedIndexes.indexOf(idx) === -1) {
                htmlContent += '<th>' + col.label + '</th>';
                includedColumns.push({
                name: col.name,
                isText: ['Prs_Ced', 'Cop_Aut', 'Autorizacion'].includes(col.name) // Identificar columnas que deben ser texto
                });
            }
            });

            htmlContent += '</tr></thead><tbody>';

            // Filas de datos
            gridData.forEach(function(row, idx) {
            htmlContent += '<tr>';
            htmlContent += '<td>' + (idx + 1) + '</td>';

            includedColumns.forEach(function(col) {
                var estilo = '';
                if (['Ret_Aut', 'Cop_Aut', 'Autorizacion'].includes(col.name)) {
                    estilo = 'class="formato-texto ajustar-texto" style="mso-number-format:\\@; text-decoration: none; color: black;"';
                } else if (['Proveedor', 'Cop_Obs'].includes(col.name)) {
                    estilo = 'class="ajustar-texto"';
                } else if (col.isText) {
                    estilo = 'class="formato-texto"';
                }
                htmlContent += '<td ' + estilo + '>' + (row[col.name] || '') + '</td>';
            });

            htmlContent += '</tr>';
            });

            // Fila de Totales
            if (mostrarTotales && gridData.length > 0) {
            htmlContent += '<tr class="totales-final">';
            htmlContent += '<td></td>'; // columna de contador #

            var nombreColProveedores = "Cop_Obs";
            includedColumns.forEach(function(col) {
                if (col.name === nombreColProveedores) {
                htmlContent += '<td style="text-align: right;">TOTALES:</td>';
                } else if (totals.hasOwnProperty(col.name)) {
                htmlContent += '<td style="text-align: right;">' + totals[col.name].toFixed(2) + '</td>';
                } else {
                htmlContent += '<td></td>';
                }
            });

            htmlContent += '</tr>';
            }

            htmlContent += '</tbody></table>';
            htmlContent += '<div style="text-align: right; margin-top: 20px;">Generado el ' + new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-') + ' por EXA [Software Contable]</div>';
            htmlContent += '</body></html>';

            return htmlContent;
        }
        </script>
        
        <script>
            $(document).ready(function() {
                $("#tabs-2").hide();
                $("#tabs-3").show();
                var kardexGrid = $("#kardex");
                kardexGrid.createGrid({
                        mtype: "GET",
                        datatype: "local",
                        height: 200,
                        caption: ' ',
                        totalCols: ['Cop_Can', 'Cop_Imp', 'Descuento', 'Iva', 'Total'],
                        totalDefault: {
                            Provee: $.fieldSummary({
                                msg: 'TOTALES'
                            })
                        },
                        colModel: [{
                                label: 'Cod.Int.',
                                name: 'Cop_Key',
                                key: true,
                                hidden: true,
                                viewable: true
                            },
                            {
                                label: 'Fecha',
                                name: 'Cop_Fec',
                                align: "center",
                                width: 40
                            },
                            {
                                label: 'Proveedor',
                                name: 'Provee',
                                width: 120
                            },

                            {
                                label: 'Num. Doc.',
                                name: 'Cop_Num',
                                width: 55,
                                classes: 'columnHighlight2'
                            },
                            {
                                label: 'Asiento',
                                name: 'Com_Codigo',
                                width: 30,
                                classes: 'columnHighlight2'
                            },

                            {
                                label: 'Cant.',
                                name: 'Cop_Can',
                                width: 25,
                                align: "right",
                                formatter: 'interger',
                                classes: 'columnHighlight1'
                            },
                            {
                                label: 'V.Uni.',
                                name: 'Cop_Pru',
                                width: 35,
                                align: "right",
                                formatter: 'number',
                                classes: 'columnHighlight1',
                                formatoptions: {
                                    decimalPlaces: 6
                                }
                            },
                            {
                                label: 'V.Tot.',
                                name: 'Cop_Imp',
                                width: 40,
                                align: "right",
                                formatter: 'currency',
                                classes: 'columnHighlight1',
                                formatoptions: {}
                            },

                            {
                                label: 'Desc.',
                                name: 'Descuento',
                                width: 25,
                                align: "right",
                                formatter: 'currency',
                                classes: 'columnHighlight2',
                                formatoptions: {
                                    defaultValue: ''
                                }
                            },
                            {
                                label: 'IVA',
                                name: 'Iva',
                                width: 35,
                                align: "right",
                                formatter: 'currency',
                                classes: 'columnHighlight2',
                                formatoptions: {
                                    defaultValue: ''
                                }
                            },
                            {
                                label: 'Total',
                                name: 'Total',
                                width: 40,
                                align: "right",
                                formatter: 'currency',
                                classes: 'columnHighlight2',
                                formatoptions: {
                                    defaultValue: ''
                                }
                            },
                            {
                                label: 'C.Uni.',
                                name: 'Unitario',
                                width: 40,
                                align: "right",
                                formatter: 'number',
                                classes: 'columnHighlight2',
                                formatoptions: {
                                    decimalPlaces: 6,
                                    defaultValue: ''
                                }
                            }
                        ],
                        footerrow: true,
                        userDataOnFooter: false
                        /*loadComplete: function () {
                             kardexGrid.jqGrid('footerData', 'set', { Cop_Can:kardexGrid.jqGrid('getCol','Cop_Can',false,'sum'),Provee: '<div style="text-align:right;">Totales:</div>',Total:kardexGrid.jqGrid('getCol','Total',false,'sum'),Cop_Imp:kardexGrid.jqGrid('getCol','Cop_Imp',false,'sum'),Iva:kardexGrid.jqGrid('getCol','Iva',false,'sum'),Descuento:kardexGrid.jqGrid('getCol','Descuento',false,'sum') });
                        }*/
                    }, true, '#kardexPager', {
                        view: false
                    })
                    .gridButtonsAdd([{
                            caption: "Imprimir&nbsp;",
                            buttonicon: "print",
                            onClickButton: function() {
                                kardexGrid.jqGrid('printGrid', {
                                    nombre: 'Compras',
                                    hoja: 'Entradas',
                                    caption: true,
                                    footer: true
                                });
                            },
                            position: "last"
                        },
                        {
                            caption: "Exportar Excel&nbsp;",
                            buttonicon: "download-alt",
                            onClickButton: function() {
                                kardexGrid.jqGrid('exportGridExcel', {
                                    nombre: 'Compras',
                                    hoja: 'Entradas',
                                    caption: true,
                                    footer: true
                                });
                            },
                            position: "last"
                        }
                    ]);

            });
            /*function formatInt(cellValue, options, rowdata, action) {
               if (cellValue === ""|| cellValue*1 === 0 || isNaN(cellValue) || cellValue === null || cellValue === 'null') return "";
               return cellValue;
            }*/
            /*function formatPrecio(cellValue, options, rowdata, action) {
               if (cellValue === ""|| cellValue*1 === 0 || isNaN(cellValue) || cellValue === null || cellValue === 'null') return "";
               var number = parseFloat(cellValue).toFixed(6);
               return number;
            }*/
            /*function formatValor(cellValue, options, rowdata, action) {
               if (cellValue === ""|| cellValue*1 === 0 || isNaN(cellValue) || cellValue === null || cellValue === 'null') return "";
               var number = parseFloat(cellValue).toFixed(2);          //  Give us our number to 2 decimal places
               return $.fn.fmatter.call(this, "number", number, options);
            }*/
            function SelectProd(data) {
                var today = new Date();
                $('#formKardex').setData(data, false);
                $('#ini').datepicker("setDate", new Date(today.getTime() - (30 * 24 * 3600 * 1000)));
                $('#fin').datepicker("setDate", today);
                $.getDataJson('', {
                    'Pro_Cod': data['Pro_Cod'],
                    'ajaxProd': true
                }, function(response) {
                    $('#formKardex').setData(response['prod'], 'prod');
                    $('#formKardex').setData(response['stocks'], 'kardex');
                    $('#Iva_Search').removeAttr('class').addClass('glyphicon glyphicon-' + (response['prod']['Iva_Por'] * 1 > 0 ? 'ok blue' : 'remove blue'));
                    $('#kardex').jqGrid('setCaption', data['Producto'] + ' - ' + 'Desde ' + $('#ini').val() + ' Hasta ' + $('#fin').val());
                    $('#kardex').Search('#formKardex', 'ajaxKardex');
                });
                $('#prodReportDialog').dialog('close');
            }
        </script>
        <script>
            $(document).ready(function() {
                $("#documentoSearch").createTabs();
                $("#tabs-1").find('[id^="gbox_"]').jqGrid("resizeGrid");
                $.createDateRange("#txt_fec_ini", "#txt_fec_fin");
                $.createDateRange('#ini', '#fin');
                $('#prodReportDialog').createSearchDialog({
                    colModel: [{
                            label: 'C&oacute;d.Int.',
                            name: 'Pro_Cod',
                            key: true,
                            width: 15,
                            align: "center",
                            hidden: true
                        },
                        {
                            label: 'Descripci&oacute;n',
                            name: 'Producto',
                            width: 110
                        },
                        {
                            label: 'Marca',
                            name: 'Mar_Des',
                            width: 40
                        },
                        {
                            label: 'Tipo',
                            name: 'Cat_Des',
                            width: 110,
                            align: "center"
                        },
                        {
                            label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                            name: 'act1',
                            width: 20,
                            align: 'center',
                            viewable: false,
                            formatter: 'gridButton',
                            formatoptions: {
                                action: 'SelectProd',
                                data: ['Pro_Cod', 'Producto']
                            }
                        }
                    ]
                }, {
                    title: 'Producto',
                    options: [{
                        label: '&nbsp;&nbsp;Producto&nbsp;&nbsp;',
                        value: 'd'
                    }, {
                        label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',
                        value: 'c'
                    }]
                });
            });
        </script>
        <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>

</HTML>