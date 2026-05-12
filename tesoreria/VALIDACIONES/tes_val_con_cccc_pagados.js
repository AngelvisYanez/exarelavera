/**
 * JavaScript para tes_con_cccc_pagados.php
 * Módulo de Historial de Créditos Otorgados
 * Extraído para mejor organización del código
 * Compatible con PHP 5.3.8
 */

// Variables globales que se inicializan desde PHP
var isnego = isnego || '';
var phpSelf = phpSelf || '';
var sesSysNom = sesSysNom || '';

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
    var caption = 'Historial de Creditos Otorgados';
    var caption2 = '';
    if ($('#Pec_Cod').val() === '') {
        caption2 = '<b>Desde</b> ' + $('#txt_fec_ini').val() + ' <b>Hasta</b> ' + $('#txt_fec_fin').val();
    } else {
        caption2 = '<b>Periodo</b> ' + $('#Pec_Cod').find('option:selected').text();
    }
    if ($('#PrvCodBus').val() !== '') {
        caption2 = caption2 + ' - ' + $('#lblProv').val();
    }
    $('#capts').html(caption + '<br>' + caption2);
    $('#capts').data('caption', caption);
    $('#capts').data('caption2', caption2);
}

function clearFooter() {
    var $footRow = $("#grillaComp .ui-jqgrid-sdiv .footrow");

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
        } else {
            grid.jqGrid("setCell", rows[i].Cpp_Cod, "Pago", "0.00");
        }
    }
}

function updateTotals(grid) {
    var abonos = 0,
        saldos = 0,
        rows = grid.jqGrid('getRowData');
    for (var i = 0; i < rows.length; i++) {
        abonos = abonos + parseFloat(rows[i]['Abono']);
        saldos = saldos + parseFloat(rows[i]['Saldo']);
    }
    grid.jqGrid('footerData', 'set', {
        vencimiento: "<div style='text-align:right;'>TOTALES:</div>",
        Asi_Val: grid.jqGrid('getCol', 'Asi_Val', false, 'sum')
    });
    grid.jqGrid('footerData', 'set', {
        Abono: "" + abonos
    });
    grid.jqGrid('footerData', 'set', {
        Saldo: "" + saldos
    });
}

function cargarSelect() {
    $('#filtroCCxCC').val($('#FilterBy').val());
    $('#list').submit();
}

/** Aviso: facturas sin cuenta Debe en ccpp_cliente (mismos filtros que la grilla CCXCC). */
function actualizarCcccClieVariosAlerta() {
    if (!$('#formCompTemp').length || !$('#cccc_alerta_clie_varios').length) return;
    var d = $('#formCompTemp').getData('getCcccCuentaAlerta');
    $.post(phpSelf, d, function (resp) {
        if (!resp || resp.success !== true) {
            $('#cccc_alerta_clie_varios').hide();
            return;
        }
        var n = parseInt(resp.count, 10) || 0;
        if (n < 1) {
            $('#cccc_alerta_clie_varios').hide();
            return;
        }
        $('#cccc_alerta_clie_varios').show();
        var $num = $('#cccc_alerta_clie_varios_num');
        $num.text(n).data('rows', resp.rows || []);
        var tip = [];
        $.each(resp.rows || [], function (i, r) {
            var cta = (r.cuenta_debe != null && r.cuenta_debe !== '') ? r.cuenta_debe : (r.cuenta_haber || '');
            tip.push((r.Com_Codigo || '') + ' | Fact. ' + (r.Cop_Num || '') + ' | ' + (r.Cop_Fec || '') + ' | ' + (r.proveedor || '') + ' | Debe: ' + cta);
        });
        $num.attr('title', tip.join('\n'));
    }, 'json').fail(function () { $('#cccc_alerta_clie_varios').hide(); });
}

$(document).on('click', '#cccc_alerta_clie_varios_num', function (e) {
    e.preventDefault();
    var rows = $(this).data('rows') || [];
    if (!rows.length) return;
    var esc = function (s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    };
    var html = '<div style="text-align:left;max-height:340px;overflow:auto;font-size:12px;">';
    html += '<p style="margin-bottom:10px;">La cuenta del asiento <strong>Debe</strong> no est&aacute; en la configuraci&oacute;n <strong>ccpp_cliente</strong> (CxC clientes / &laquo;Clientes varios&raquo;). Debe dar de alta esa cuenta o corregir el asiento.</p><ul style="padding-left:18px;margin:0;">';
    $.each(rows, function (i, r) {
        var cta = (r.cuenta_debe != null && r.cuenta_debe !== '') ? r.cuenta_debe : (r.cuenta_haber || '');
        html += '<li style="margin-bottom:12px;"><b>' + esc(r.Com_Codigo) + '</b> &mdash; Factura <b>' + esc(r.Cop_Num) + '</b>, ' + esc(r.Cop_Fec) + '<br/>' + esc(r.proveedor) + '<br/><span style="color:#555;">Debe: ' + esc(cta) + '</span></li>';
    });
    html += '</ul></div>';
    $('<div title="Facturas excluidas del listado">' + html + '</div>').dialog({
        modal: true,
        width: 540,
        buttons: [{ text: 'Cerrar', click: function () { $(this).dialog('close'); } }],
        close: function () { $(this).remove(); }
    });
});

function initCompGrid() {
    var mostrarColumnas = (isnego === 'S');
    $.createDateRange('#txt_fec_ini', '#txt_fec_fin');
    $('#isnegoCCxCC').val(isnego);
    
    var compGrid = $("#list");
    compGrid.jqGrid({
        caption: '<label id="capts" name="capts">Resultados</label>'+ (isnego === 'S' ? '<div class="pull-right"><b>FILTRADO POR:</b>&nbsp;<select id="FilterBy" onchange="cargarSelect();"><option value="">No filtrar</option><option value="L">Larva</option><option value="B">Balanceado</option><option value="F">Flete Falso</option><option value="I">Insumos</option></select>&nbsp;</div>' : ''),
        url: phpSelf,
        mtype: "GET",
        datatype: "local",
        regional: 'es',
        autowidth: true,
        shrinkToFit: true,
        height: 270,
        hidegrid: false,
        cmTemplate: {
            sortable: false
        },
        colModel: [
            {
                label: 'C&oacute;d.Int.',
                name: 'Cpc_Cod',
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
                label: 'C&eacute;dula',
                name: 'Prs_Ced',
                width: 30,
                align: "center",
                hidden: true
            },
            {
                label: 'No. Compr.',
                name: 'Com_Codigo',
                align: "center",
                width: 40
            },
            {
                label: 'Fecha Emis.',
                name: 'Caj_Fec',
                align: "center",
                width: 35
            },
            {
                label: 'Fecha Venc.',
                name: 'Cpc_Ven',
                align: "center",
                width: 35
            },
            {
                label: 'Vencimiento',
                name: 'vencimiento',
                align: "center",
                width: 40
            },
            {
                label: 'Total',
                name: 'Asi_Val',
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
                    if (!parseFloat(rowObject.Abono)) rowObject.Abono = 0;
                    return $.fn.fmatter.call(this, "currency", rowObject.Abono, options);
                },
                unformat: function(cellValue, options, cell) {
                    var opt = $.extend(true, {}, options);
                    opt.colModel.formatter = "currency";
                    delete opt.colModel.unformat;
                    return $.unformat.call(this, cell, opt);
                },
                summaryTpl: "Total: {0}",
                summaryType: "sum"
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
                        rowObject.Saldo = parseFloat(rowObject.Asi_Val) - parseFloat(rowObject.Abono);
                    }
                    if (parseFloat(rowObject.Abono) === parseFloat(rowObject.Asi_Val)) return "0.00";
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
                summaryType: "sum"
            },
            {
                label: 'Tipo',
                name: 'Tic_Des',
                width: 50,
                align: "center"
            },
            {
                label: 'No. Documento',
                name: 'Vet_Num',
                width: 47,
                align: "center"
            },
            {
                label: 'Obs. Documento',
                name: 'Vet_Obs',
                width: 60
            },
            { 
                label: 'Num.Neg', 
                name: 'Num_Neg', 
                width: 65, 
                align: 'center', 
                hidden: !mostrarColumnas 
            },
            { 
                label: 'Tipo Producto', 
                name: 'Tip_Prod', 
                width: 70, 
                align: 'center', 
                hidden: !mostrarColumnas,
                formatter: function(cellvalue) {
                    if (cellvalue === 'B') return 'Balanceado';
                    if (cellvalue === 'L') return 'Larva';
                    if (cellvalue === 'F') return 'Flete';
                    if (cellvalue === 'I') return 'Insumos';
                    if (cellvalue === null) return '';
                    return cellvalue;
                }
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
                    return '<span class="btn btn-info btn-mini" title="Ver" type="button" onclick="$(\'#list\').viewGridRow(\'' + rowObject.Cpc_Cod + '\');"><i class="icon-info-sign icon-white"></i></span><span>&nbsp;&nbsp;</span>';
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
                if (data.rows[i]['vencimiento'] === 'Vencido')
                    $("#" + data.rows[i].Cpc_Cod).css("background", "#FADDDD");
                if (data.rows[i]['vencimiento'] === 'Pagado')
                    $("#" + data.rows[i].Cpc_Cod).css("background", "#DDFAE2");
            }
            updateTotals($(this));
            actualizarCcccClieVariosAlerta();
        },
        subGridOptions: {
            "plusicon": "ui-icon-triangle-1-e",
            "minusicon": "ui-icon-triangle-1-s",
            "openicon": "ui-icon-arrowreturn-1-e",
            "reloadOnExpand": false,
            "selectOnExpand": true
        },
        subGrid: true,
        multiselect: false,
        subGridRowExpanded: function(subgrid_id, row_id) {
            var subgrid_table_id = subgrid_id + "_t";
            $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
            $("#" + subgrid_table_id).jqGrid({
                url: phpSelf + "?ajaxSubgrid=" + row_id,
                datatype: "json",
                regional: 'es',
                autowidth: true,
                shrinkToFit: true,
                cmTemplate: {
                    sortable: false
                },
                colModel: [{
                        label: 'Cod.Int.',
                        name: "Cpc_Cod",
                        width: 80,
                        key: true,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'Cod.Int.',
                        name: "Com_Cod",
                        width: 80,
                        key: true,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'No. Compr.',
                        name: "Com_Codigo",
                        width: 45,
                        align: "center"
                    },
                    {
                        label: 'Fecha',
                        name: "Cpc_Fec",
                        width: 45,
                        align: "center"
                    },
                    {
                        label: 'Valor',
                        name: "Cpc_Val",
                        width: 45,
                        align: 'right',
                        formatter: 'currency',
                        decimalPlaces: '2',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.'
                        }
                    },
                    { 
                        label: 'Observación', 
                        name: "Cpc_Obs", 
                        width: 100,
                        formatter: function(cellValue, options, rowObject) {
                            return cellValue + (rowObject.Num_Doc ? " / Transf. N#: " + rowObject.Num_Doc : "");
                        }
                    },
                    {
                        label: 'Tipo',
                        name: "Pag_Des",
                        width: 45,
                        align: "center"
                    },
                    {
                        label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                        name: 'act1',
                        width: 18,
                        align: 'center',
                        viewable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            var clic = 'selectDetalle(' + rowObject.Cpc_Cod + ',' + rowObject.Com_Cod + ');';
                            return '<span class="btn btn-info btn-mini" title="Seleccionar" onclick=\'' + clic + '\'><i class="icon-info-sign icon-white"></span>';
                        }
                    }
                ],
                beforeSelectRow: function(rowid, e) {
                    return false;
                },
                rowNum: 10000000,
                pager: "",
                height: '100%'
            });
        }
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
    
    compGrid.jqGrid('bindKeys');
    clearFooter();
    $('#rangeDates').addClass('disabled').find('input').attr('disabled', 'disabled');
}

function initProvDialog() {
    $.createSearchDialog('#provDialog', [
        {
            label: 'Cód.Int.',
            name: 'Cli_Cod',
            key: true,
            hidden: true,
            viewable: true
        },
        {
            label: 'Cédula/R.U.C.',
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
            label: 'Dirección',
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
}

function initPagoDialog() {
    $.createDialog('#pagoDialog', 415, 650);
    $("#tabs").tabs();
    $.createDatePickers("input[name='Com_Fec']");
    $.createDatePickers("input[name='Che_Fec']");
    
    $('#asiento').jqGrid({
        datatype: "local",
        regional: 'es',
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
        regional: 'es',
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
}

function selectDetalle(Cpc, Com) {
    $.post(phpSelf, {
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
            Cliente: datos['proveedor'],
            Prs_Ced: datos['Prs_Ced'] || ''
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
    
    if (batch.length > 0) {
        var seleccionado = (document.querySelector('input[name="resumido"]:checked')).value;

        $.post(phpSelf, {
            resumido: (seleccionado === 'S'),
            resumido1: seleccionado,
            dataReport: batch,
            tipo: banTipo,
            caption: $('#capts').data('caption') || 'Historial de Créditos Otorgados',
            caption2: $('#capts').data('caption2') || ''
        }, function(response) {
            console.log(response);

            if (response['success'] === true) {
                if (banTipo)
                    $(response['html']).printElement({
                        pageTitle: sesSysNom
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

// Inicialización cuando el documento esté listo
$(document).ready(function() {
    // Inicializar grid de comprobantes
    initCompGrid();
    
    // Inicializar diálogo de búsqueda de proveedor
    initProvDialog();
    
    // Inicializar diálogo de pago
    initPagoDialog();
});

