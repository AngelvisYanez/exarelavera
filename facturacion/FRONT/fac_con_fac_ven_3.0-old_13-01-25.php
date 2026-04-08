<?php

/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new MysqlDatos(true);

if (isset($gridAjax)) {
    $resp = $obBD_con1->getPageGridJson('tabla.selectWhere', array_merge($_GET, array('setWhere' => array())));
}
/* Consulta los totales */
if (isset($ajaxTotales)) {
    $FILTERS = array();
    array_push($FILTERS, $op_est != 'I' ? 'isActive' : 'isInactive');
    if ($range == 'S') array_push($FILTERS, 'byDateRange');
    if ($Tic_Cod != 'T') array_push($FILTERS, 'byTipoCompr');
    if ($cedul == 'S') array_push($FILTERS, 'byCliCod');
    /*if($Chk_Ret=='S') array_push($FILTERS,'notHasRetencion');
    if($Tri_Cod!='T') array_push($FILTERS,'bySustento');
    if($Suc_Cod!='T') array_push($FILTERS,'bySucCod');*/
    $response = $obBD_con1->getPageGrid('ventas', array_merge($_GET, array('where' => array(), 'setWhere' => array_merge($FILTERS, array(/*'setUsuario','setRetencion',*/'setTotales')))));
    $totalGlobal = $obBD_con1->getRowConsulta('ventas', array_merge($_GET, array('where' => array(), 'unsetCols' => array(/*'Vnd_Cod','Pun_Cod','Vendedor'*/), 'setWhere' => array_merge($FILTERS, array('setEmpCod',/*'setUsuario','setRetencion',*/ 'isSummary')))));
    $response['userdata'] = array_merge($totalGlobal, array('Cliente' => '<div class="txtRight">TOTAL GLOBAL:</div>', 'Tot_Renta' => 0, 'Tot_Iva' => 0));
    foreach ($response['rows'] as &$row) {
        //        $row['proveedor']=$row['Proveedor'];
        //        $row['vendedor']=$row['Vendedor'];
        $comprobante = $obBD_con1->getRowConsulta('comprobantes.getComprobanteByVetCod', $row['Vet_Cod']);
        if (!is_null($comprobante)) {
            $row['Com_Codigo'] = $comprobante['Com_Codigo'];
        }
        $pagos = $obBD_con1->getRowConsulta("ventas.2", $row['Vet_Cod']);
        $row['Forma_Pago'] = ($pagos['total'] > 0) ? 'Credito' : 'Contado';
        if (!empty($row['Ret_Num'])) {
            $ret_data = $obBD_con1->getRowConsulta('ventas.getRetencionVet', $row['Vet_Cod']);
            $row = array_merge(array('Tot_Iva' => $ret_data['Tot_Iva'], 'Tot_Renta' => $ret_data['Tot_Renta']), $row);
            $response['userdata']['Tot_Renta'] += ($ret_data['Tot_Renta'] * 1);
            $response['userdata']['Tot_Iva'] += ($ret_data['Tot_Iva'] * 1);
        }
    }
    $obBD_con1->echoJson($response);
}

$rs_tip_compr = $obBD_con1->getArray('tipo_compr', array('Tic_Est' => 'A'));
//$rs_sustento = $obBD_con1->getArray('sustento.selectWhere', array('clean'=>true,'where'=>array('Tri_Est'=>'A')) );
$rs_periodo = $obBD_con1->getArrayConsulta(33, $Ses_Emp_Cod, $obBD_conexion);
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Ventas Consultar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style></style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Consultar Documentos de Ventas</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="documentoSearch" class="ui-tabs ui-tab-fix noPaddingH">
                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                    <li><a href="#tab2">Totales</a></li>
                    <li><a href="#tab1">Individual</a></li>
                    <li><a href="#tab3">Detalles</a></li>
                    <li><a href="#tab4">Ultimas</a></li>
                </ul>
                <div id="tab1" class="ui-tabs-panel"></div>
                <div id="tab2" class="ui-tabs-panel">
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
                                                <input id="op_cedul" name="cedul" type="checkbox" onchange="setFilter('cedul',$(this));" value="S"><label for="op_cedul">&nbsp;Cliente&nbsp;</label>
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
                                            <div class="input-group input-group-sm dateRangeInputs">
                                                <span class="range input-group-addon alert-info">Desde</span>
                                                <input type="text" name="Fec_Ini" class="form-control range" required="" />
                                                <span class="range input-group-addon alert-info">Hasta</span>
                                                <input type="text" name="Fec_Fin" class="form-control range" required="" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group cedul" style="display:none;">
                                        <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                                        <div class="col-xs-6" id="ProvSearchFormTemp">
                                            <input name="Cli_Cod" type="text" style="display:none;" />
                                            <input name="op_opciones" type="text" value="c" style="display: none;">
                                            <div class="input-group input-group-xs">
                                                <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#provDialog',selectProvee,'#ProvSearchFormTemp');" type="text" placeholder="Ingrese Cliente..." class="form-control input-xs clearable dialogSearch cedul" tabindex="1" disabled="" />
                                                <span class="input-group-btn">
                                                    <button type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs cedul" title="Buscar Cliente" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                        <label class="col-xs-4 control-label label-xs">Oblig.Contab:&nbsp;<i id="Prv_Con_Search" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label>
                                    </div>
                                    <div class="form-group cedul" style="display:none;">
                                        <label class="col-xs-2 control-label label-xs required">Cliente:</label>
                                        <div class="col-xs-6"><span name="Cliente" class="form-control input-xs databind datatitle"></span></div>
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
                                        <div class="col-sm-6">
                                            <?php $sucursal = $obBD_con1->getArray('sucursal.selectWhere', array('clean' => true, 'unsetCols' => true, 'addCols' => array('sucursal' => array('Suc_Cod', 'Suc_Des')), 'where' => array('Emp_Cod' => $Ses_Emp_Cod))); ?>
                                            <select name="Suc_Cod" class="form-control input-xs">
                                                <option value="T">
                                                    << TODAS>>
                                                </option>
                                                <?php echo $obBD_con1->htmlOptions($sucursal, 'Suc_Cod', 'Suc_Des'); ?>
                                            </select>
                                        </div>
                                        <label class="col-sm-1 control-label label-xs">Orden:</label>
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
                                        <div class="col-sm-6">
                                            <select name="Tic_Cod" class="form-control input-xs">
                                                <option value="T">
                                                    << TODOS>>
                                                </option>
                                                <?php
                                                function TicDes($v)
                                                {
                                                    return "$v[Tic_Sri] - $v[Tic_Des]";
                                                }
                                                function selFactura($v)
                                                {
                                                    return $v['Tic_Sri'] == '01';
                                                }
                                                echo $obBD_con1->htmlOptions($rs_tip_compr, 'Tic_Cod', 'TicDes', false, 'selFactura');
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-4"><label class="label-xs"><input name="Chk_Ret" type="checkbox" id="Chk_Ret" class="check-big" value="S"><span>&nbsp; No Sujetas a Ret.</span></label></div>
                                    </div>
                                    <!--<div class="form-group">
                                <label class="col-sm-2 control-label label-xs" >Sustento:</label>
                                <div class="col-sm-10">
                                    <select name="Tri_Cod" id="Tri_Cod" class="form-control input-xs">
                                        <option value="T"><< TODOS >></option>
                                        <?php //function TriDes($v){ return "$v[Tri_Sri] - $v[Tri_Des]"; } echo $obBD_con1->htmlOptions($rs_sustento, 'Tri_Cod', 'Suc_Des'); 
                                        ?>
                                    </select>
                                </div>
                            </div>-->
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
                <div id="tab3" class="ui-tabs-panel"></div>
                <div id="tab4" class="ui-tabs-panel"></div>
            </div>
        </div>
    </div>


    <script type="text/javascript">
        var sumatorias = [{
                label: 'Base 0%',
                campo: 'Sub_0'
            },
            {
                label: 'Base IVA',
                campo: 'Sub_12'
            },
            {
                label: 'Descuento',
                campo: 'Descu'
            },
            {
                label: 'ICE',
                campo: 'Ice_Tot'
            },
            {
                label: 'IVA',
                campo: 'Iva_Tot'
            },
            {
                label: 'IRBPNR',
                campo: 'Irbpnr'
            },
            {
                label: 'TOTAL',
                campo: 'Total'
            },
            {
                label: 'Imp. Renta',
                campo: 'Tot_Renta'
            },
            {
                label: 'Ret. IVA',
                campo: 'Tot_Iva'
            }
        ];

        function sumNotNC(v, n, obj) {
            return isNaN(v) ? 0 : (obj['Tic_Sri'] === '04' ? -1 * v : v);
        }
        $(function() {
            $('#documentoSearch').createTabs();
            var ReportResumen = $("#ReportResumen");
            ReportResumen.createGrid({
                height: 230,
                autowidth: true,
                shrinkToFit: false,
                datatype: 'local',
                stateCol: 'Cop_Est',
                caption: "Resultados de la búsqueda",
                postData: $("#formTotales").getData("ajaxTotales"),
                colModel: [{
                        label: 'Tip.Sri',
                        name: 'Tic_Sri',
                        width: 30,
                        align: "center",
                        frozen: true,
                        excel: 'text'
                    },
                    {
                        label: 'Tip.Doc.',
                        name: 'Tic_Des',
                        width: "125px",
                        align: "center",
                        frozen: true
                    },
                    {
                        label: 'Aut',
                        name: 'Vet_Aut',
                        width: "25px",
                        align: "center",
                        frozen: true
                    },
                    //{label: 'Sust.', name: 'Tri_Sri', width: 30, align: "center", frozen:true, excel:'text'},
                    {
                        label: 'Nro.Doc.',
                        name: 'Secuencia',
                        width: "150px",
                        align: "center",
                        frozen: true,
                        excel: 'text'
                    },
                    {
                        label: 'Fecha',
                        name: 'Caj_Fec',
                        width: "75px",
                        align: "center",
                        frozen: true
                    },
                    {
                        label: 'Cédula/Ruc',
                        name: 'Prs_Ced',
                        width: "100px",
                        align: "center",
                        frozen: true,
                        excel: 'text'
                    },
                    {
                        label: 'Cód.',
                        name: 'Vet_Cod',
                        width: "50px",
                        align: "center",
                        key: true
                    },
                    {
                        label: 'Cliente',
                        name: 'Cliente',
                        width: "200px"
                    },
                    {
                        label: 'Base 0%',
                        name: 'Sub_0',
                        width: "75px",
                        align: "right",
                        formatter: 'number',
                        summaryType: 'sumNotNC'
                    },
                    {
                        label: 'Base 15%',
                        name: 'Sub_12',
                        width: "75px",
                        align: "right",
                        formatter: 'number',
                        summaryType: 'sumNotNC'
                    },

                    {
                        label: 'Base 5%',
                        name: 'Sub_5',
                        width: "75",
                        align: "right",
                        formatter: 'number',
                        summaryType: 'sumNotNC'
                    },
                    {
                        label: 'Base 8%',
                        name: 'Sub_8',
                        width: "75",
                        align: "right",
                        formatter: 'number',
                        summaryType: 'sumNotNC'
                    },

                    {
                        label: 'Desc',
                        name: 'Descu',
                        width: "75px",
                        align: "right",
                        formatter: 'number',
                        summaryType: 'sumNotNC'
                    },
                    {
                        label: 'ICE',
                        name: 'Ice_Tot',
                        width: "75px",
                        align: "right",
                        formatter: 'number',
                        summaryType: 'sumNotNC'
                    },
                    {
                        label: 'IVA',
                        name: 'Iva_Tot',
                        width: "75px",
                        align: "right",
                        formatter: 'number',
                        summaryType: 'sumNotNC'
                    },
                    {
                        label: 'IRBPNR',
                        name: 'Irbpnr',
                        width: "75px",
                        align: "right",
                        formatter: 'number',
                        summaryType: 'sumNotNC'
                    },
                    {
                        label: 'TOTAL',
                        name: 'Total',
                        width: "75px",
                        align: "right",
                        formatter: 'number',
                        summaryType: 'sumNotNC'
                    },
                    {
                        label: 'Reten.',
                        name: 'Ret_Num',
                        width: "50px",
                        align: "center",
                        formatter: 'truefalse',
                        formatoptions: {
                            yesMsg: function(o) {
                                return 'Ret. Num.: <u class="blue">' + o.Ret_Num + '</u>';
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
                    {
                        label: 'Fec.Ret.',
                        name: 'Ret_Fec',
                        width: "75px",
                        align: "center"
                    },
                    {
                        label: 'Aut. Retención',
                        name: 'Ret_Aut',
                        width: "100px",
                        align: "center",
                        excel: 'text'
                    },
                    {
                        label: 'Renta',
                        name: 'Tot_Renta',
                        width: "75px",
                        align: "right",
                        formatter: 'number'
                    },
                    {
                        label: 'Ret. Iva',
                        name: 'Tot_Iva',
                        width: "75px",
                        align: "right",
                        formatter: 'number'
                    },
                    {
                        label: 'Compr.',
                        name: 'Com_Codigo',
                        width: "50px",
                        align: "center",
                        formatter: 'truefalse',
                        formatoptions: {
                            yesMsg: function(o) {
                                return 'Comprobante: <u class="blue">' + o.Com_Codigo + '</u>';
                            },
                            noMsg: ' ',
                            yesColor: function(o) {
                                return o.Cop_Est === 'I' ? 'red' : 'green';
                            }
                        }
                    },
                    {
                        label: 'Pago',
                        name: 'Forma_Pago',
                        width: "60px",
                        align: "center"
                    }, // Credito o Contado, query
                    //{label: 'Pdf', name: '', width: "50px", align: "center"},
                    {
                        label: '&nbsp;',
                        name: 'act0',
                        width: "30px",
                        align: 'center',
                        viewable: false,
                        formatter: 'gridButton',
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
                userDataOnFooter: true,
                totalPage: true,
                totalCols: ['Sub_0', 'Sub_12', 'Sub_5', 'Sub_8', 'Descu', 'Ice_Tot', 'Iva_Tot', 'Irbpnr', 'Total', 'Tot_Renta', 'Tot_Iva'],
                totalDefault: {
                    Cliente: '<div class="txtRight">TOTAL PAGINA:</div>'
                }
            }, false, "ReportResumenPager").jqGrid('setFrozenColumns').gridButtonsAdd([null,
                {
                    caption: "Sumas",
                    buttonicon: "eye-open",
                    onClickButton: function() {
                        var sumas = ReportResumen[0].p.userData,
                            rows = [];
                        $.each(sumatorias, function() {
                            this.val = sumas[this.campo];
                            rows.push(this);
                        });
                        console.log(rows);
                        $('#sumatorias').setRows(rows);
                        $('#sumatorias').dialog('open');
                    },
                    position: "last"
                },
                null,
                {
                    caption: "Exportar Excel&nbsp;",
                    buttonicon: "download-alt",
                    onClickButton: function() {
                        ReportResumen.jqGrid('exportGridExcel', {
                            nombre: "Ventas",
                            hoja: "HOJA 1",
                            footer: true
                        });
                    },
                    position: "last"
                }
            ]);
            $('.dateRangeInputs').createDateRange(30);
            $('#sumatorias').createDialogDetail([{
                    label: 'Campo',
                    name: 'label',
                    width: "75px"
                },
                {
                    label: 'Valor',
                    name: 'val',
                    width: "75px",
                    align: "right",
                    formatter: 'currency'
                }
            ]);
        });

        function setFilter(cl, $t) {
            var ch = $t.is(':checked');
            $('span.' + cl)[!ch ? 'addClass' : 'removeClass']('alert-disabled');
            $('input.' + cl).prop('required', ch).prop('disabled', !ch);
            $('div.form-group.' + cl)[!ch ? 'hide' : 'show']();
        }
    </script>
    <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <div id="sumatorias" title="Sumatoria Reporte"></div>
</BODY>

</HTML>