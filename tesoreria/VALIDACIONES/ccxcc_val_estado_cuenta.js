/**
 * Estado de Cuenta de Clientes - Validaciones y grid (jqGrid)
 */
$(function () {
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });

    // Diálogo de búsqueda de clientes
    $.createSearchDialog('clientesDialog', [
        { label: 'Cód.Int.', name: 'Cli_Cod', key: true, width: 15, align: 'center', hidden: true },
        { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Cliente', name: 'nombre', width: 100 },
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label: ' ', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: seleccionarCliente } }
    ], null, null, null, { headertitles: true }, { title: 'Cliente', text: 'searchCli' });

    cargarGrid();

    // Evento de búsqueda movido a navButtonAdd o mantenido si es necesario
    $('#btnBuscar').on('click', function () {
        buscarEstadoCuenta();
    });
});

function cargarGrid() {
    var gridOpt = {
        caption: 'Estado de Cuenta (Kardex)',
        url: (typeof UrlSaveJson !== 'undefined' ? UrlSaveJson : ''),
        // postData: function () {
        //     return $('#formEstadoCuenta').getData('getEstadoCuentaCliente');
        // },
        height: 320,
        responsive: false,
        colModel: [
            { label: '# Compr.', name: 'Com_Codigo', width: 28, align: 'center' },
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
            { label: 'TOTAL', name: 'TOTAL', width: 28, align: 'right', formatter: 'currency', summaryType: 'sum',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' }, summaryTpl: 'Total: {0}' },
            { label: 'ABONO', name: 'ABONO', width: 28, align: 'right', formatter: 'currency', summaryType: 'sum',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' }, summaryTpl: 'Total: {0}' },
            { label: 'SALDO', name: 'SALDO', width: 28, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' } }
        ],
        loadComplete: function (data) {
            actualizarTotalesFooter(data);
            actualizarResumenCard(data);
            // Aplicar color por estado usando data.rows (el grid puede no incluir Estado_Color en getRowData)
            var rows = (data && data.rows) ? data.rows : [];
            $('#searchGrid').find('tbody tr.jqgrow').each(function (idx) {
                var row = rows[idx];
                if (!row) return;
                var color = (row.Estado_Color || '').toString().trim();
                if (color) $(this).addClass('cell-' + color);
            });
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
    
    // Agregar botón de Excel al pager
    $('#searchGrid').jqGrid('navButtonAdd', '#pag_sg', {
        caption: ' Exportar a Excel', title: 'Exportar a Excel',
        buttonicon: 'glyphicon glyphicon-download-alt',
        onClickButton: function() {
            exportarExcel();
        },
        position: 'last'
    });
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

function buscarEstadoCuenta() {
    if (!$('#Cli_Cod').val() || $('#Cli_Cod').val() === '') {
        $.alert && $.alert('Seleccione un cliente.');
        return;
    }
    $('#searchGrid').jqGrid('setGridParam', {
        postData: $('#formEstadoCuenta').getData('getEstadoCuentaCliente'),
        datatype: 'json',
        page: 1
    }).trigger('reloadGrid');
}

function seleccionarCliente(cliente) {
    $('#Cli_Cod').val(cliente.Cli_Cod);
    $('#Prs_Ced').val(cliente.Prs_Ced);
    $('#nombre').val(cliente.nombre);
    $('#Prs_Dir').val(cliente.Prs_Dir || '');
    $('#clientesDialog').dialog('close');
    buscarEstadoCuenta();
}

function limpiarCliente() {
    $('#Cli_Cod').val('');
    $('#Prs_Ced').val('');
    $('#nombre').val('');
    $('#Prs_Dir').val('');
    $('#searchGrid').jqGrid('clearGridData');
    limpiarResumenCard();
}

function exportarExcel() {
    if (!$('#Cli_Cod').val() || $('#Cli_Cod').val() === '') {
        $.alert && $.alert('Seleccione un cliente.');
        return;
    }
    var postData = {
        exportExcelEstadoCuenta: true,
        Cli_Cod: $('#Cli_Cod').val(),
        txt_fec_ini: $('#txt_fec_ini').val(),
        txt_fec_fin: $('#txt_fec_fin').val(),
        nombre_cliente: $('#nombre').val() || ''
    };
    $.post('', postData, function (response) {
        if (response.success && response.html) {
            $('#tabla_export_ex').html(response.html);
            if (typeof $.downloadFile === 'function' && typeof $.exportarExcelBlob === 'function') {
                $.downloadFile($.exportarExcelBlob($('#exportar').html(), 'EstadoCuentaCliente'), 'EstadoCuentaCliente_' + $.getDate() + '.xls');
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
