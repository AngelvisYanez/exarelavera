/**
 * Estado de Cuenta de Proveedores - Validaciones y grid (jqGrid)
 */

/** Último resultado cargado en el grid (para imprimir con los mismos filtros que en pantalla) */
var _ecPrintSnapshot = { rows: [], totals: {}, resumen: {} };

$(function () {
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });

    $('#verPagoDialog').createDialog({ width: 750, height: 520, icon: 'info-sign' });
    $('#vp_tabs').tabs();

    $('#vpGridAsi').createGrid({ viewrecords: false, caption: '<center>Asientos</center>',
        data: [], rowNum: 200, height: 120, width: 680, footerrow: true, responsive: false,
        onSelectRow: function() { $(this).resetSelection(); },
        colModel: [
            { label: 'index', key: true, name: 'index', hidden: true },
            { label: 'Código', name: 'Pld_Cdc', width: 20 },
            { label: 'Cuenta', name: 'Pld_Des', width: 50 },
            { label: 'Glosa', name: 'Asi_Glo', width: 40 },
            { label: 'Debe', name: 'Debe', width: 20, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Haber', name: 'Haber', width: 20, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } }
        ]
    }, true, '', { view: false });

    $('#vpGridChe').createGrid({ viewrecords: false, caption: '<center>Cheques</center>',
        data: [], rowNum: 50, height: 80, width: 680, responsive: false,
        onSelectRow: function() { $(this).resetSelection(); },
        colModel: [
            { label: 'No. Cheque', name: 'Che_Num', width: 20 },
            { label: 'Fecha', name: 'Che_Fec', width: 20 },
            { label: 'Valor', name: 'Che_Val', width: 20, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Observación', name: 'Che_Obs', width: 50 }
        ]
    }, true, '', { view: false });

    $('#vpGridPag').createGrid({ viewrecords: false, caption: '<center>Pagos aplicados a esta factura</center>',
        data: [], rowNum: 200, height: 110, width: 680, footerrow: true, responsive: false,
        onSelectRow: function() { $(this).resetSelection(); },
        colModel: [
            { label: 'No. Compr.', name: 'codigo_compro', width: 28, align: 'center' },
            { label: 'Fecha', name: 'Com_Fec', width: 22, align: 'center' },
            { label: 'T. Pago', name: 'T_Pago', width: 25, align: 'center' },
            { label: 'Observación', name: 'Pag_Obs', width: 50 },
            { label: 'Valor', name: 'Pag_Val', width: 22, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } }
        ]
    }, true, '', { view: false });

    $('#vpGridFact').createGrid({ viewrecords: false, caption: '<center>Facturas incluidas</center>',
        data: [], rowNum: 200, height: 120, width: 680, footerrow: true, responsive: false,
        onSelectRow: function() { $(this).resetSelection(); },
        colModel: [
            { label: 'No. Factura', name: 'Cop_Num', width: 30, align: 'center' },
            { label: 'Fecha Emis.', name: 'Cop_Fec', width: 22, align: 'center' },
            { label: 'Fecha Venc.', name: 'Cpp_Ven', width: 22, align: 'center' },
            { label: 'Total Factura', name: 'total_factura', width: 28, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Monto Pagado', name: 'monto_pagado', width: 28, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } }
        ]
    }, true, '', { view: false });

    // Establecer rango de fechas por defecto: 1 de enero del año en curso hasta hoy
    var hoy = new Date();
    var anio = hoy.getFullYear();
    var mm   = String(hoy.getMonth() + 1).padStart(2, '0');
    var dd   = String(hoy.getDate()).padStart(2, '0');
    $('#txt_fec_ini').val(anio + '-01-01');
    $('#txt_fec_fin').val(anio + '-' + mm + '-' + dd);

    // Diálogo de búsqueda de proveedores
    $.createSearchDialog('proveedoresDialog', [
        { label: 'Cód.Int.', name: 'Prv_Cod', key: true, width: 15, align: 'center', hidden: true },
        { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Proveedor', name: 'nombre', width: 100 },
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label: ' ', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: seleccionarProveedor } }
    ], null, null, null, { headertitles: true }, { title: 'Proveedor', text: 'searchPrv' });

    cargarGrid();

    // Inicializar Chosen para filtro de T. Pago
    $('#sel_tipo_pago_multi').chosen({
        placeholder_text_multiple: 'Todos los tipos de pago...',
        no_results_text: 'No encontrado',
        width: '100%'
    }).on('change', function () {
        var selected = $(this).val();
        if (!selected || selected.length === 0) {
            $('#filter_tipo_pago').val('');
        } else {
            $('#filter_tipo_pago').val(selected.join(','));
        }
        buscarEstadoCuenta();
    });

    $('#btnBuscar').on('click', function () {
        buscarEstadoCuenta();
    });

    // Deshabilitar T. Pago cuando se filtra solo Facturas
    $('input[name="filter_tipo_row"]').on('change', function() {
        var val = $(this).val();
        if (val === 'Factura') {
            $('#sel_tipo_pago_multi').val([]).trigger('chosen:updated');
            $('#filter_tipo_pago').val('');
            $('#sel_tipo_pago_multi').prop('disabled', true).trigger('chosen:updated');
        } else {
            $('#sel_tipo_pago_multi').prop('disabled', false).trigger('chosen:updated');
        }
    });

    // Buscar automáticamente al cargar con las fechas del año en curso y todos los proveedores
    buscarEstadoCuenta();

    // Ajustar ancho del grid cuando la ventana cambia de tamaño
    $(window).on('resize', function () {
        ajustarAnchoGrid();
    });
});

function cargarGrid() {
    var gridOpt = {
        caption: 'Estado de Cuenta (Kardex)',
        url: (typeof UrlSaveJson !== 'undefined' ? UrlSaveJson : ''),
        // postData: function () {
        //     return $('#formEstadoCuenta').getData('getEstadoCuentaProveedor');
        // },
        height: 320,
        responsive: false,
        colModel: [
            { label: '# Compr.', name: 'Com_Codigo', width: 28, align: 'center' },
            { label: 'Proveedor', name: 'Proveedor', width: 55, align: 'left' },
            { label: 'Fecha Emisión', name: 'Fecha_Emision', width: 22, align: 'center' },
            { label: 'Fecha Venc.', name: 'Fecha_Venc', width: 22, align: 'center' },
            { label: 'Días venc.', name: 'Dias_Vencimiento', width: 22, align: 'center',
                formatter: function (cellValue, options, rowObject) {
                    if (cellValue === null || cellValue === '' || rowObject.Tipo === 'Pago') return '';
                    var d = parseInt(cellValue, 10);
                    if (isNaN(d)) return '';
                    if (d > 0) return 'Vencido ' + d + ' d';
                    if (d < 0) return 'Vence en ' + (-d) + ' d';
                    return 'Hoy';
                }
            },
            { label: 'Tipo', name: 'Tipo', width: 18, align: 'center' },
            { label: 'Documento', name: 'Documento', width: 50, align: 'left' },
            { label: 'Cuenta Bancaria', name: 'Cuenta_Bancaria', width: 28, align: 'left' },
            { label: 'Fecha Cheque', name: 'Fecha_Cheque', width: 22, align: 'center' },
            { label: 'Che / Transf', name: 'Num_Ref', width: 28, align: 'center' },
            { label: 'T. Pago', name: 'Tipo_Pago', width: 22, align: 'center' },
            { label: 'TOTAL', name: 'TOTAL', width: 28, align: 'right', formatter: 'currency', summaryType: 'sum',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' }, summaryTpl: 'Total: {0}' },
            { label: 'ABONO', name: 'ABONO', width: 28, align: 'right', formatter: 'currency', summaryType: 'sum',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' }, summaryTpl: 'Total: {0}' },
            { label: 'SALDO', name: 'SALDO', width: 28, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' } },
            { label: 'Com_Cod_Pago', name: 'Com_Cod_Pago', hidden: true },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_ver', width: 12, align: 'center', viewable: false, sortable: false,
                formatter: function(cellValue, options, rowObject) {
                    if (!rowObject.Com_Cod_Pago || rowObject.Com_Cod_Pago == 0) return '';
                    return $.getGridButton(verPagoEstadoCuenta, rowObject, 'Ver detalle', 'info-sign', '', 'info');
                }
            }
        ],
        loadComplete: function (data) {
            actualizarTotalesFooter(data);
            actualizarResumenCard(data);
            _ecPrintSnapshot.rows = (data && data.rows) ? data.rows.slice() : [];
            _ecPrintSnapshot.totals = (data && data.totals) ? $.extend({}, data.totals) : {};
            _ecPrintSnapshot.resumen = (data && data.resumen) ? $.extend({}, data.resumen) : {};
            actualizarAlertaEcProveVarios(data);
            // Aplicar color por estado usando data.rows (el grid puede no incluir Estado_Color en getRowData)
            var rows = (data && data.rows) ? data.rows : [];
            $('#searchGrid').find('tbody tr.jqgrow').each(function (idx) {
                var row = rows[idx];
                if (!row) return;
                var color = (row.Estado_Color || '').toString().trim();
                if (color) $(this).addClass('cell-' + color);
            });
            setTimeout(function () { $('#searchGrid').syncJqGridRownumWithHeader(); }, 0);
        },
        rowNum: 10000,
        gridview: true,
        viewrecords: true,
        footerrow: true,
        userDataOnFooter: true,
        multiselect: false,
        datatype: 'local',
        mtype: 'POST',
        jsonReader: { root: 'rows', page: 'page', total: 'total', records: 'records', repeatitems: false }
    };
    $('#searchGrid').createGrid(gridOpt, false, '#pag_sg', { view: false, refresh: true });
}

function actualizarTotalesFooter(data) {
    var totals = (data && data.totals) ? data.totals : {};
    $('#searchGrid').jqGrid('footerData', 'set', {
        Com_Codigo: 'TOTALES:',
        TOTAL: totals.TOTAL != null ? totals.TOTAL : $('#searchGrid').jqGrid('getCol', 'TOTAL', false, 'sum'),
        ABONO: totals.ABONO != null ? totals.ABONO : $('#searchGrid').jqGrid('getCol', 'ABONO', false, 'sum'),
        SALDO: totals.SALDO != null ? totals.SALDO : ''
    }, true);
}

function actualizarAlertaEcProveVarios(data) {
    var a = data && data.alerta_ccpp ? data.alerta_ccpp : null;
    var $w = $('#ec_alerta_prove_varios');
    if (!$w.length) return;
    if (!a || !a.count || parseInt(a.count, 10) < 1) {
        $w.hide();
        return;
    }
    $w.show();
    var $n = $('#ec_alerta_prove_varios_num');
    $n.text(a.count).data('rows', a.rows || []);
    var tip = [];
    $.each(a.rows || [], function (i, r) {
        tip.push((r.Com_Codigo || '') + ' | Doc. ' + (r.Cop_Num || '') + ' | ' + (r.Cop_Fec || '') + ' | ' + (r.proveedor || '') + ' | Haber: ' + (r.cuenta_haber || ''));
    });
    $n.attr('title', tip.join('\n'));
}

$(document).on('click', '#ec_alerta_prove_varios_num', function (e) {
    e.preventDefault();
    var rows = $(this).data('rows') || [];
    if (!rows.length) return;
    var esc = function (s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    };
    var html = '<div style="text-align:left;max-height:340px;overflow:auto;font-size:12px;">';
    html += '<p style="margin-bottom:10px;">La cuenta del asiento <strong>Haber</strong> no est&aacute; en <strong>ccpp_prove</strong> (CxP proveedores / &laquo;Proveedores varios&raquo;). No se listan en este estado de cuenta hasta corregir la cuenta o el asiento.</p><ul style="padding-left:18px;margin:0;">';
    $.each(rows, function (i, r) {
        html += '<li style="margin-bottom:12px;"><b>' + esc(r.Com_Codigo) + '</b> &mdash; Factura <b>' + esc(r.Cop_Num) + '</b>, ' + esc(r.Cop_Fec) + '<br/>' + esc(r.proveedor) + '<br/><span style="color:#555;">Haber: ' + esc(r.cuenta_haber) + '</span></li>';
    });
    html += '</ul></div>';
    $('<div title="Facturas excluidas del estado de cuenta">' + html + '</div>').dialog({
        modal: true,
        width: 540,
        buttons: [{ text: 'Cerrar', click: function () { $(this).dialog('close'); } }],
        close: function () { $(this).remove(); }
    });
});

function actualizarResumenCard(data) {
    var resumen = (data && data.resumen) ? data.resumen : {};
    var saldoTotal = resumen.Saldo_Total != null ? parseFloat(resumen.Saldo_Total) : 0;
    var saldoVencido = resumen.Saldo_Vencido != null ? parseFloat(resumen.Saldo_Vencido) : 0;
    var saldoPorVencer = resumen.Saldo_Por_Vencer != null ? parseFloat(resumen.Saldo_Por_Vencer) : 0;
    var fmt = function (n) { return '$ ' + (typeof n === 'number' ? n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0.00'); };
    $('#resumenSaldoTotal').text(fmt(saldoTotal));
    $('#resumenSaldoVencido').text(fmt(saldoVencido));
    $('#resumenSaldoPorVencer').text(fmt(saldoPorVencer));
    $('#resumenEstadoCuenta').show();
}

function limpiarResumenCard() {
    $('#resumenSaldoTotal').text('$ 0.00');
    $('#resumenSaldoVencido').text('$ 0.00');
    $('#resumenSaldoPorVencer').text('$ 0.00');
}

function ajustarAnchoGrid() {
    var w = $('#searchGrid').closest('.ui-jqgrid').parent().width();
    if (w > 0) {
        $('#searchGrid').jqGrid('setGridWidth', w, true);
    }
}

function buscarEstadoCuenta() {
    var conProveedor = $('#Prv_Cod').val() && $('#Prv_Cod').val() !== '';
    if (conProveedor) {
        $('#searchGrid').jqGrid('hideCol', 'Proveedor');
    } else {
        $('#searchGrid').jqGrid('showCol', 'Proveedor');
    }
    ajustarAnchoGrid();

    var postData = $('#formEstadoCuenta').getData('getEstadoCuentaProveedor');
    postData['filter_tipo_pago'] = $('#filter_tipo_pago').val() || '';
    postData['filter_tipo_row']  = $('input[name="filter_tipo_row"]:checked').val() || '';

    $('#searchGrid').jqGrid('setGridParam', {
        postData: postData,
        datatype: 'json',
        page: 1
    }).trigger('reloadGrid');
}

function seleccionarProveedor(proveedor) {
    $('#Prv_Cod').val(proveedor.Prv_Cod);
    $('#Prs_Ced').val(proveedor.Prs_Ced);
    $('#nombre').val(proveedor.nombre);
    $('#Prs_Dir').val(proveedor.Prs_Dir || '');
    $('#proveedoresDialog').dialog('close');
    buscarEstadoCuenta();
}

function limpiarProveedor() {
    $('#Prv_Cod').val('');
    $('#Prs_Ced').val('');
    $('#nombre').val('');
    $('#Prs_Dir').val('');
    buscarEstadoCuenta();
}

function verPagoEstadoCuenta(rowObject) {
    var esFactura = (rowObject.Tipo === 'Factura' || rowObject.Tipo === 'Nota Crédito');

    $('#vpGridAsi').jqGrid('clearGridData');
    $('#vpGridChe').jqGrid('clearGridData');
    $('#vpGridPag').jqGrid('clearGridData');
    $('#vpGridFact').jqGrid('clearGridData');
    $('#vp_prov').val(rowObject.Proveedor || '');
    $('#vp_compr').val(rowObject.Com_Codigo || '');
    $('#vp_fec').val(rowObject.Fecha_Emision || '');
    $('#vp_obs').val(rowObject.Documento || '');

    // Adaptar tabs según tipo
    if (esFactura) {
        $('#vp_li_che').hide();
        $('#vp_li_pag').show();
        $('#vp_li_fact').hide();
        // Mostrar/ocultar botón PDF
        if (rowObject.Carm_Cla && rowObject.Carm_Cla !== '') {
            $('#btnDescargarPdf').data('carm_cla', rowObject.Carm_Cla).show();
        } else {
            $('#btnDescargarPdf').hide();
        }
    } else {
        /* Cheques: solo si el backend trae filas (evita tab vacío) */
        $('#vp_li_che').hide();
        $('#vp_li_pag').hide();
        $('#vp_li_fact').show();
        $('#btnDescargarPdf').hide();
    }
    $('#vp_tabs').tabs('refresh');
    $('#vp_tabs').find('ul li:visible:first a').trigger('click');
    $('#verPagoDialog').dialog('open');

    $.post('', { getAsientosAbono: true, Com_Cod: rowObject.Com_Cod_Pago, Cpp_Cod: rowObject.Cpp_Cod_Row || 0 }, function(resp) {
        if (!resp.success) return;

        // Asientos
        for (var i = 0; i < resp.data.length; i++) {
            var d = resp.data[i];
            var debe = '', haber = '';
            if (d.Asi_Deh === 'D') { debe = d.Asi_Val; } else { haber = d.Asi_Val; }
            $('#vpGridAsi').jqGrid('addRowData', i + 1, { index: i + 1, Pld_Cdc: d.Pld_Cdc, Pld_Des: d.Pld_Des, Asi_Glo: d.Asi_Glo, Debe: debe, Haber: haber }, 'last');
        }
        $('#vpGridAsi').jqGrid('footerData', 'set', {
            Pld_Des: '<div style="text-align:right">TOTALES:</div>',
            Debe:  $('#vpGridAsi').jqGrid('getCol', 'Debe',  false, 'sum'),
            Haber: $('#vpGridAsi').jqGrid('getCol', 'Haber', false, 'sum')
        }, true);

        // Cheques (solo pestaña en pagos cuando existan registros)
        var nChe = (resp.data_che && resp.data_che.length) ? resp.data_che.length : 0;
        if (nChe > 0) {
            for (var j = 0; j < resp.data_che.length; j++) {
                var c = resp.data_che[j];
                $('#vpGridChe').jqGrid('addRowData', j + 1, { Che_Num: c.Che_Num, Che_Fec: c.Che_Fec, Che_Val: c.Che_Val, Che_Obs: c.Che_Obs }, 'last');
            }
        }
        if (!esFactura) {
            if (nChe > 0) {
                $('#vp_li_che').show();
            } else {
                $('#vp_li_che').hide();
            }
        }

        // Facturas
        if (resp.data_facts && resp.data_facts.length > 0) {
            var tot_f = 0, tot_p = 0;
            for (var k = 0; k < resp.data_facts.length; k++) {
                var f = resp.data_facts[k];
                tot_f += parseFloat(f.total_factura) || 0;
                tot_p += parseFloat(f.monto_pagado) || 0;
                $('#vpGridFact').jqGrid('addRowData', k + 1, { Cop_Num: f.Cop_Num, Cop_Fec: f.Cop_Fec, Cpp_Ven: f.Cpp_Ven, total_factura: f.total_factura, monto_pagado: f.monto_pagado }, 'last');
            }
            $('#vpGridFact').jqGrid('footerData', 'set', {
                Cop_Fec: '<div style="text-align:right">TOTALES:</div>',
                total_factura: tot_f, monto_pagado: tot_p
            }, true);
        }

        // Pagos aplicados (para facturas)
        if (resp.data_pagos && resp.data_pagos.length > 0) {
            var tot_pag = 0;
            for (var p = 0; p < resp.data_pagos.length; p++) {
                var pg = resp.data_pagos[p];
                tot_pag += parseFloat(pg.Pag_Val) || 0;
                $('#vpGridPag').jqGrid('addRowData', p + 1, {
                    codigo_compro: pg.codigo_compro, Com_Fec: pg.Com_Fec,
                    T_Pago: pg.T_Pago, Pag_Obs: pg.Pag_Obs, Pag_Val: pg.Pag_Val
                }, 'last');
            }
            $('#vpGridPag').jqGrid('footerData', 'set', {
                Pag_Obs: '<div style="text-align:right">TOTAL ABONADO:</div>',
                Pag_Val: tot_pag
            }, true);
        }

        $('#vp_tabs').tabs('refresh');
        if (!$('#vp_li_che').is(':visible')) {
            var $tabs = $('#vp_tabs'), idx = $tabs.tabs('option', 'active'),
                href = $tabs.find('.ui-tabs-nav li').eq(idx).find('a').attr('href');
            if (href === '#vp_tab_che') {
                var firstVis = -1;
                $tabs.find('.ui-tabs-nav li').each(function (i) {
                    if ($(this).is(':visible')) { firstVis = i; return false; }
                });
                if (firstVis >= 0) { $tabs.tabs('option', 'active', firstVis); }
            }
        }

        $('#vpGridAsi').updateGridsSizes();
        $('#vpGridChe').updateGridsSizes();
        $('#vpGridPag').updateGridsSizes();
        $('#vpGridFact').updateGridsSizes();
    }, 'json').fail(function() { $.alert && $.alert('Error al obtener detalle del pago.'); });
}

var _carm_cla_actual = '';
function descargarPdfFactura() {
    var carm_cla = $('#btnDescargarPdf').data('carm_cla');
    if (!carm_cla) return;
    $.post('../FRONT/tes_alt_carga_masiva1.0.php', { downloadFileSri: true, Carm_Cla: carm_cla, CLAVE_ACCESO: carm_cla, type: 'pdf' }, function(resp) {
        if (resp && resp.pdf) {
            var byteChars = atob(resp.pdf);
            var byteArr = [];
            for (var i = 0; i < byteChars.length; i++) byteArr.push(byteChars.charCodeAt(i));
            var blob = new Blob([new Uint8Array(byteArr)], { type: 'application/pdf' });
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href = url; a.download = carm_cla + '.pdf';
            document.body.appendChild(a); a.click();
            document.body.removeChild(a); URL.revokeObjectURL(url);
        } else {
            var msg = (resp && resp.error) ? resp.error : 'No se encontró el XML en carga masiva para esta clave; no se puede generar el PDF.';
            $.alert && $.alert(msg);
        }
    }, 'json').fail(function() { $.alert && $.alert('Error al descargar el PDF.'); });
}

function aplicarFiltroTipo() {
    buscarEstadoCuenta();
}

function _ecEscHtml(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
}

function _ecFmtMonto(n) {
    var x = parseFloat(n);
    if (isNaN(x)) x = 0;
    return '$ ' + x.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function _ecDiasVencText(row) {
    var cellValue = row.Dias_Vencimiento;
    if (cellValue === null || cellValue === '' || row.Tipo === 'Pago') return '';
    var d = parseInt(cellValue, 10);
    if (isNaN(d)) return '';
    if (d > 0) return 'Vencido ' + d + ' d';
    if (d < 0) return 'Vence en ' + (-d) + ' d';
    return 'Hoy';
}

function _ecBgPorEstado(colorKey) {
    var c = (colorKey || '').toString().trim();
    switch (c) {
        case 'verde': return '#c8e6c9';
        case 'amarillo': return '#fff9c4';
        case 'rojo': return '#ffcdd2';
        case 'celeste': return '#b3e5fc';
        case 'gris':
        default: return '';
    }
}

function _ecTextoFiltrosImpresion() {
    var provNom = ($('#nombre').val() || '').trim();
    var provTxt = provNom ? ('<strong>Proveedor:</strong> ' + _ecEscHtml(provNom)) : '<strong>Proveedor:</strong> Todos';
    var rango = '<strong>Rango:</strong> ' + _ecEscHtml($('#txt_fec_ini').val()) + ' &mdash; ' + _ecEscHtml($('#txt_fec_fin').val());
    var ft = ($('input[name="filter_tipo_row"]:checked').val() || '').trim();
    var tipoTxt = '<strong>Tipo mov.:</strong> ';
    if (ft === 'Factura') tipoTxt += 'Facturas';
    else if (ft === 'Pago') tipoTxt += 'Pagos';
    else tipoTxt += 'Todos';
    var selPagos = ($('#filter_tipo_pago').val() || '').trim();
    var tp = '<strong>T. pago:</strong> ';
    tp += selPagos ? _ecEscHtml(selPagos.replace(/,/g, ', ')) : 'Todos';
    return provTxt + '<br/>' + rango + '<br/>' + tipoTxt + ' &nbsp;|&nbsp; ' + tp;
}

/**
 * Imprime el estado de cuenta con encabezado de reporte, resumen y tabla (datos actuales del grid).
 */
function imprimirEstadoCuenta() {
    var rows = _ecPrintSnapshot.rows || [];
    if (!rows.length) {
        $.alert && $.alert('No hay datos para imprimir. Realice una búsqueda primero.');
        return;
    }
    var totals = _ecPrintSnapshot.totals || {};
    var resumen = _ecPrintSnapshot.resumen || {};
    var muestraProv = !($('#Prv_Cod').val() && $('#Prv_Cod').val() !== '');

    $('#ec_print_filtros').html(_ecTextoFiltrosImpresion());
    $('#ec_print_res_total').text(_ecFmtMonto(resumen.Saldo_Total));
    $('#ec_print_res_venc').text(_ecFmtMonto(resumen.Saldo_Vencido));
    $('#ec_print_res_pvenc').text(_ecFmtMonto(resumen.Saldo_Por_Vencer));

    var th = '<tr>';
    if (muestraProv) th += '<th># Compr.</th><th>Proveedor</th>';
    else th += '<th># Compr.</th>';
    th += '<th>Fecha Emisi&oacute;n</th><th>Fecha Venc.</th><th>D&iacute;as venc.</th><th>Tipo</th><th>Documento</th>';
    th += '<th>Cuenta Bancaria</th><th>Fecha Cheque</th><th>Che / Transf</th><th>T. Pago</th><th>TOTAL</th><th>ABONO</th><th>SALDO</th>';
    th += '</tr>';
    $('#ec_print_thead').html(th);

    var ncol = muestraProv ? 14 : 13;
    var body = '';
    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var bg = _ecBgPorEstado(r.Estado_Color);
        var st = bg ? ' style="background:' + bg + ';"' : '';
        body += '<tr' + st + '>';
        body += '<td' + st + '>' + _ecEscHtml(r.Com_Codigo) + '</td>';
        if (muestraProv) body += '<td' + st + '>' + _ecEscHtml(r.Proveedor) + '</td>';
        body += '<td' + st + '>' + _ecEscHtml(r.Fecha_Emision) + '</td>';
        body += '<td' + st + '>' + _ecEscHtml(r.Fecha_Venc) + '</td>';
        body += '<td' + st + ' style="text-align:center">' + _ecEscHtml(_ecDiasVencText(r)) + '</td>';
        body += '<td' + st + ' style="text-align:center">' + _ecEscHtml(r.Tipo) + '</td>';
        body += '<td' + st + '>' + _ecEscHtml(r.Documento) + '</td>';
        body += '<td' + st + '>' + _ecEscHtml(r.Cuenta_Bancaria) + '</td>';
        body += '<td' + st + ' style="text-align:center">' + _ecEscHtml(r.Fecha_Cheque) + '</td>';
        body += '<td' + st + ' style="text-align:center">' + _ecEscHtml(r.Num_Ref) + '</td>';
        body += '<td' + st + ' style="text-align:center">' + _ecEscHtml(r.Tipo_Pago) + '</td>';
        body += '<td' + st + ' style="text-align:right">' + _ecEscHtml(_ecFmtMonto(r.TOTAL)) + '</td>';
        body += '<td' + st + ' style="text-align:right">' + _ecEscHtml(_ecFmtMonto(r.ABONO)) + '</td>';
        body += '<td' + st + ' style="text-align:right">' + _ecEscHtml(_ecFmtMonto(r.SALDO)) + '</td>';
        body += '</tr>';
    }
    $('#ec_print_body').html(body);

    var cspan = ncol - 3;
    var ft = '<tr style="font-weight:bold;border-top:2px solid #000;">';
    ft += '<td colspan="' + cspan + '" style="text-align:right;">TOTALES:</td>';
    ft += '<td style="text-align:right;">' + _ecEscHtml(_ecFmtMonto(totals.TOTAL)) + '</td>';
    ft += '<td style="text-align:right;">' + _ecEscHtml(_ecFmtMonto(totals.ABONO)) + '</td>';
    ft += '<td style="text-align:right;">' + _ecEscHtml(_ecFmtMonto(totals.SALDO)) + '</td>';
    ft += '</tr>';
    $('#ec_print_tfoot').html(ft);

    var printCss = '../../mascaras/model1/estilos/print.css';
    $('#print_estado_cuenta').printElement({
        pageTitle: 'Estado de Cuenta Proveedores',
        printMode: 'iframe',
        overrideElementCSS: [{ href: printCss, media: 'print' }]
    });
}

function imprimirDetallePago() {
    // Copiar datos del header
    $('#vp_prov_print').text($('#vp_prov').val());
    $('#vp_compr_print').text($('#vp_compr').val());
    $('#vp_fec_print').text($('#vp_fec').val());
    $('#vp_obs_print').text($('#vp_obs').val());

    // Asientos
    var asiRows = $('#vpGridAsi').jqGrid('getDataIDs');
    var asiHtml = '', debe_tot = 0, haber_tot = 0;
    for (var i = 0; i < asiRows.length; i++) {
        var r = $('#vpGridAsi').jqGrid('getRowData', asiRows[i]);
        var debe  = parseFloat(r.Debe)  || 0;
        var haber = parseFloat(r.Haber) || 0;
        debe_tot  += debe;
        haber_tot += haber;
        asiHtml += '<tr><td>' + (r.Pld_Cdc || '') + '</td><td>' + (r.Pld_Des || '') + '</td><td>' + (r.Asi_Glo || '') + '</td>'
                 + '<td style="text-align:right">' + (debe  ? '$ ' + debe.toFixed(2)  : '') + '</td>'
                 + '<td style="text-align:right">' + (haber ? '$ ' + haber.toFixed(2) : '') + '</td></tr>';
    }
    $('#vp_print_asi_body').html(asiHtml);
    $('#vp_print_debe_tot').text(debe_tot  ? '$ ' + debe_tot.toFixed(2)  : '');
    $('#vp_print_haber_tot').text(haber_tot ? '$ ' + haber_tot.toFixed(2) : '');

    // Cheques
    var cheRows = $('#vpGridChe').jqGrid('getDataIDs');
    var cheHtml = '';
    for (var j = 0; j < cheRows.length; j++) {
        var c = $('#vpGridChe').jqGrid('getRowData', cheRows[j]);
        cheHtml += '<tr><td>' + (c.Che_Num || '') + '</td><td>' + (c.Che_Fec || '') + '</td>'
                 + '<td style="text-align:right">$ ' + (parseFloat(c.Che_Val) || 0).toFixed(2) + '</td>'
                 + '<td>' + (c.Che_Obs || '') + '</td></tr>';
    }
    $('#vp_print_che_body').html(cheHtml);
    $('#vp_print_che_wrap').toggle(cheRows.length > 0);

    // Facturas
    var factRows = $('#vpGridFact').jqGrid('getDataIDs');
    var factHtml = '', fact_tot = 0, fact_pag_tot = 0;
    for (var k = 0; k < factRows.length; k++) {
        var f = $('#vpGridFact').jqGrid('getRowData', factRows[k]);
        fact_tot     += parseFloat(f.total_factura) || 0;
        fact_pag_tot += parseFloat(f.monto_pagado)  || 0;
        factHtml += '<tr><td>' + (f.Cop_Num || '') + '</td><td>' + (f.Cop_Fec || '') + '</td><td>' + (f.Cpp_Ven || '') + '</td>'
                  + '<td style="text-align:right">$ ' + (parseFloat(f.total_factura) || 0).toFixed(2) + '</td>'
                  + '<td style="text-align:right">$ ' + (parseFloat(f.monto_pagado)  || 0).toFixed(2) + '</td></tr>';
    }
    $('#vp_print_fact_body').html(factHtml);
    $('#vp_print_fact_tot').text('$ ' + fact_tot.toFixed(2));
    $('#vp_print_fact_pag_tot').text('$ ' + fact_pag_tot.toFixed(2));
    $('#vp_print_fact_wrap').toggle(factRows.length > 0);

    $('#print_detalle_pago').printElement();
}

function exportarExcel() {
    var postData = {
        exportExcelEstadoCuenta: true,
        Prv_Cod: $('#Prv_Cod').val(),
        txt_fec_ini: $('#txt_fec_ini').val(),
        txt_fec_fin: $('#txt_fec_fin').val(),
        nombre_proveedor: $('#nombre').val() || ''
    };
    $.post('', postData, function (response) {
        if (response.success && response.html) {
            $('#tabla_export_ex').html(response.html);
            if (typeof $.downloadFile === 'function' && typeof $.exportarExcelBlob === 'function') {
                $.downloadFile($.exportarExcelBlob($('#exportar').html(), 'EstadoCuentaProveedor'), 'EstadoCuentaProveedor_' + $.getDate() + '.xls');
            } else {
                var w = window.open('', '_blank');
                w.document.write('<html><head><meta charset="UTF-8"><title>' + (response.titulo || 'Estado de Cuenta') + '</title></head><body>' + $('#exportar').html() + '</body></html>');
                w.document.close();
            }
        } else {
            $.alert && $.alert(response.message || 'Error al exportar.');
        }
    }, 'json').fail(function () {
        $.alert && $.alert('Error de conexión.');
    });
}
