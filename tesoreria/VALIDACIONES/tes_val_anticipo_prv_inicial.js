/**
 * Anticipos a Proveedores Iniciales (lote)
 * Fecha y Tipo(Inicial/Pag_Cod) van al grid; carga masiva Excel.
 */
var gridAnticipos;

$(function () {
    $('#successDialog').createDialog({ width: 460, height: 240, icon: 'print' });

    var $fec = $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false });
    if ($.isFunction($.fn.mask)) {
        $fec.mask('9999-99-99', { placeholder: '_' });
    }

    $.post('', { obtenerPeriodoMinMax: true }, function (r) {
        if (r && r.success && r.data) {
            $('#Atp_Fec').dateLimits(r.data.minimo, r.data.maximo);
        }
    }, 'json');

    cargarCuentaAnp();

    if (!$('#Pag_Cod').val()) {
        $.alert('No existe tipo de pago Inicial (Pag_Abr=INI) en tipos_pago. Debe parametrizarlo.');
    }

    $.createSearchDialog('proveedoresDialog', [
        { label: 'Cod.Int.', name: 'Prv_Cod', key: true, width: 15, align: 'center', hidden: true },
        { label: 'Cedula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Proveedor', name: 'nombre', width: 100 },
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'selectProveedorIni' } }
    ], null, null, null, { headertitles: true }, { title: 'Proveedor', text: 'searchPrv' });

    gridAnticipos = $('#gridAnticipos');
    gridAnticipos.createGrid({
        data: [],
        rowNum: 100000,
        height: 260,
        footerrow: true,
        selectGridRows: false,
        colModel: [
            { name: 'index', label: 'Idx', key: true, hidden: true },
            { name: 'Prv_Cod', label: 'Prv', width: 40, align: 'center', hidden: true },
            { name: 'Pag_Cod', label: 'Pag', width: 40, align: 'center', hidden: true },
            { name: 'Atp_Fec', label: 'Fecha', width: 85, align: 'center' },
            { name: 'Pag_Des', label: 'Tipo', width: 95, align: 'center' },
            { name: 'Prs_Ced', label: 'RUC/CI', width: 95, align: 'center' },
            { name: 'nombre', label: 'Proveedor', width: 180 },
            {
                name: 'Valor', label: 'Valor', width: 85, align: 'right',
                formatter: 'textboxExa',
                formatoptions: {
                    type: 'decimal',
                    dataEvents: { keyup: 'recalcularTotalLote();' }
                }
            },
            {
                name: 'Alerta', label: 'Alerta', width: 36, align: 'center', title: false,
                formatter: function (cv) {
                    var msg = $.trim(cv || '');
                    if (msg === '') return '';
                    return $('<div class="aini-alerta-icon"><i class="glyphicon glyphicon-warning-sign"></i></div>')
                        .attr({ title: msg, 'data-originaldata': msg })
                        .prop('outerHTML');
                },
                unformat: $.unformatCellData
            },
            {
                name: 'act', label: '<i class="glyphicon glyphicon-remove"></i>', width: 28, align: 'center', viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: 'eliminarFilaAnticipo',
                    icon: 'remove',
                    type: 'danger',
                    title: 'Quitar',
                    data: function (o) { return o.index; }
                }
            }
        ],
        loadComplete: function () {
            marcarFilasAlerta();
            recalcularTotalLote();
        }
    }, true, 'gridAnticiposPager', { view: false }).gridButtonsAdd([
        {
            caption: 'Limpiar grid',
            buttonicon: 'glyphicon glyphicon-trash',
            classes: 'btn btn-info btn-xs',
            id: 'btnLimpiarGridIni',
            onClickButton: function () {
                gridAnticipos.clearGrid();
                recalcularTotalLote();
            }
        },
        {
            caption: 'Plantilla Excel',
            buttonicon: 'glyphicon glyphicon-download-alt',
            classes: 'btn btn-success btn-xs',
            id: 'btnPlantillaExcelIni',
            onClickButton: function () {
                window.location = '?descargarPlantillaAnticiposIni=1';
            }
        },
        {
            caption: 'Cargar Excel',
            buttonicon: 'glyphicon glyphicon-upload',
            classes: 'btn btn-success btn-xs',
            id: 'btnCargarExcelIni',
            onClickButton: function () {
                $('#archivoPlantilla').click();
            }
        }
    ]);
});

function cargarCuentaAnp() {
    $.post('', { cuentaAnticipoAjax: true }, function (r) {
        if (r && r.success && r.data) {
            $('#Pld_Cod_Deb').val(r.data.Pld_Cod || '');
        } else {
            $('#Pld_Cod_Deb').val('');
            $.alert((r && r.message) ? r.message : 'Cuenta ANP no parametrizada.');
        }
    }, 'json');
}

function setRucAddon(ced) {
    var $a = $('#Prs_Ced_Addon');
    ced = $.trim(ced || '');
    $('#Prs_Ced').val(ced);
    if (ced === '') {
        $a.text('RUC/CI').addClass('empty').attr('title', 'RUC / CI');
    } else {
        $a.text(ced).removeClass('empty').attr('title', 'RUC/CI: ' + ced);
    }
}

function selectProveedorIni(proveedor) {
    $('#Prv_Cod').val(proveedor.Prv_Cod || '');
    $('#Prs_Cod').val(proveedor.Prs_Cod || '');
    $('#nombre').val(proveedor.nombre || '');
    setRucAddon(proveedor.Prs_Ced || '');
    $('#Atp_Obs').val('ANTICIPO INICIAL ' + $.trim(proveedor.nombre || ''));
    $('#proveedoresDialog').dialog('close');
    $('#ValorCap').focus();
}

function limpiarProveedor() {
    $('#Prv_Cod,#Prs_Cod,#nombre,#ValorCap').val('');
    setRucAddon('');
}

function nextFilaIndex() {
    var max = gridAnticipos.jqGrid('getCol', 'index', false, 'max');
    return isNaN(max) ? 1 : (max * 1 + 1);
}

function proveedorYaEnGrid(prvCod) {
    if (!prvCod) return false;
    var ids = gridAnticipos.jqGrid('getDataIDs');
    for (var i = 0; i < ids.length; i++) {
        var r = gridAnticipos.jqGrid('getRowData', ids[i]);
        if (String(r.Prv_Cod) === String(prvCod) && !$.trim(r.Alerta || '')) return true;
    }
    return false;
}

function agregarFilaGrid(data) {
    var idx = nextFilaIndex();
    gridAnticipos.jqGrid('addRowData', idx, $.extend({ index: idx, Alerta: '' }, data), 'last');
    return idx;
}

function agregarProveedorGrid() {
    var prv = $('#Prv_Cod').val();
    var nombre = $.trim($('#nombre').val() || '');
    var ced = $.trim($('#Prs_Ced').val() || '');
    var valor = ($.trim($('#ValorCap').val() || '') + '').replace(/,/g, '');
    var fec = $.trim($('#Atp_Fec').val() || '');
    var pagCod = $('#Pag_Cod').val() || '';
    var pagDes = $.trim($('#Pag_Des').val() || 'Inicial');

    if (!prv) return $.alert('Seleccione un proveedor.');
    if (!fec || fec.indexOf('_') >= 0) return $.alert('Ingrese la fecha del anticipo.');
    if (!pagCod) return $.alert('No hay tipo de pago Inicial parametrizado (Pag_Cod).');
    if (!(valor * 1 > 0)) return $.alert('Ingrese un valor mayor a cero.');
    if (proveedorYaEnGrid(prv)) {
        return $.alert('El proveedor ya esta en el grid. Edite la fila existente o elimine y vuelva a agregar.');
    }

    agregarFilaGrid({
        Prv_Cod: prv,
        Prs_Ced: ced,
        nombre: nombre,
        Atp_Fec: fec,
        Pag_Cod: pagCod,
        Pag_Des: pagDes,
        Valor: (valor * 1).toFixed(2),
        Alerta: ''
    });

    recalcularTotalLote();
    limpiarProveedor();
    $('#Atp_Obs').val('');
}

function cargarPlantillaExcel(input) {
    if (!input.files || !input.files[0]) return;
    if (!$('#Pag_Cod').val()) {
        input.value = '';
        return $.alert('No hay tipo de pago Inicial parametrizado (Pag_Cod).');
    }

    var doUpload = function () {
        var fd = new FormData();
        fd.append('cargarPlantillaAnticiposIni', true);
        fd.append('archivo', input.files[0]);
        fd.append('Pag_Cod', $('#Pag_Cod').val());
        fd.append('Pag_Des', $('#Pag_Des').val());

        $.postMultiPartJson('', fd, function (r) {
            input.value = '';
            if (!r || !r.success) {
                return $.alert((r && r.message) ? r.message : 'No se pudo cargar la plantilla.');
            }
            gridAnticipos.clearGrid();
            var rows = r.rows || [];
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                agregarFilaGrid({
                    Prv_Cod: row.Prv_Cod || '',
                    Prs_Ced: row.Prs_Ced || '',
                    nombre: row.nombre || '',
                    Atp_Fec: row.Atp_Fec || '',
                    Pag_Cod: row.Pag_Cod || $('#Pag_Cod').val(),
                    Pag_Des: row.Pag_Des || $('#Pag_Des').val(),
                    Valor: row.Valor !== '' && row.Valor != null ? (row.Valor * 1).toFixed(2) : '',
                    Alerta: row.Alerta || ''
                });
            }
            recalcularTotalLote();
            $.alert(r.message || 'Plantilla cargada.');
        }, function () {
            input.value = '';
            $.alert('Error al cargar la plantilla.');
        });
    };

    if (gridAnticipos.jqGrid('getDataIDs').length > 0) {
        $.createDialogConfirm('La carga reemplazara las filas actuales del grid. Desea continuar?', null, doUpload, function () {
            input.value = '';
        });
    } else {
        doUpload();
    }
}

function eliminarFilaAnticipo(id) {
    gridAnticipos.jqGrid('delRowData', id);
    recalcularTotalLote();
}

function marcarFilasAlerta() {
    var ids = gridAnticipos.jqGrid('getDataIDs');
    for (var i = 0; i < ids.length; i++) {
        var id = ids[i];
        var r = gridAnticipos.jqGrid('getRowData', id);
        var omitir = $.trim(r.Alerta || '') !== '' || !r.Prv_Cod;
        $('#' + $.jgrid.jqID(id)).toggleClass('aini-fila-alerta', omitir);
    }
}

function getLoteBatch(soloValidos) {
    if (soloValidos === undefined) soloValidos = true;
    var ids = gridAnticipos.jqGrid('getDataIDs'), rows = [];
    for (var i = 0; i < ids.length; i++) {
        var r = gridAnticipos.jqGrid('getRowData', ids[i]);
        var alerta = $.trim(r.Alerta || '');
        if (soloValidos && (alerta !== '' || !r.Prv_Cod)) continue;

        var valor = $.trim($('#' + ids[i] + '_Valor').val() || r.Valor || '');
        valor = (valor + '').replace(/,/g, '');
        rows.push({
            Prv_Cod: r.Prv_Cod,
            nombre: r.nombre || '',
            Prs_Ced: r.Prs_Ced || '',
            Atp_Fec: r.Atp_Fec || '',
            Pag_Cod: r.Pag_Cod || '',
            Pag_Des: r.Pag_Des || '',
            Valor: valor * 1,
            Alerta: alerta
        });
    }
    return rows;
}

function contarFilasAlerta() {
    var ids = gridAnticipos.jqGrid('getDataIDs'), n = 0;
    for (var i = 0; i < ids.length; i++) {
        var r = gridAnticipos.jqGrid('getRowData', ids[i]);
        if ($.trim(r.Alerta || '') !== '' || !r.Prv_Cod) n++;
    }
    return n;
}

function recalcularTotalLote() {
    marcarFilasAlerta();
    var rows = getLoteBatch(true), tot = 0;
    for (var i = 0; i < rows.length; i++) {
        tot += (rows[i].Valor * 1) || 0;
    }
    var alertas = contarFilasAlerta();
    var lbl = 'TOTAL VALIDO (' + rows.length + ')';
    if (alertas > 0) lbl += ' | Alertas: ' + alertas;
    gridAnticipos.jqGrid('footerData', 'set', {
        nombre: '<div style="text-align:right;font-weight:700;">' + lbl + ':</div>',
        Valor: tot
    }, true);
    return tot;
}

function validarLoteAnticipos() {
    if (!$('#Pag_Cod').val()) {
        $.alert('No hay tipo de pago Inicial parametrizado (Pag_Cod).');
        return false;
    }
    if (!$('#Pld_Cod_Deb').val()) {
        $.alert('No hay cuenta ANP parametrizada para anticipos a proveedores.');
        return false;
    }
    var rows = getLoteBatch(true);
    if (!rows.length) {
        var alertas = contarFilasAlerta();
        if (alertas > 0) {
            $.alert('No hay filas validas para guardar. Revise las alertas del grid.');
        } else {
            $.alert('Agregue al menos un proveedor al grid.');
        }
        return false;
    }
    for (var i = 0; i < rows.length; i++) {
        if (!rows[i].Atp_Fec) {
            $.alert('Fila valida ' + (i + 1) + ': falta fecha.');
            return false;
        }
        if (!(rows[i].Valor > 0)) {
            $.alert('Fila valida ' + (i + 1) + ': el valor debe ser mayor a cero.');
            return false;
        }
    }
    return true;
}

function guardarAnticiposLote() {
    if (!validarLoteAnticipos()) return;
    var rows = getLoteBatch(true);
    var tot = recalcularTotalLote();
    var alertas = contarFilasAlerta();
    var totFmt = tot.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    var msg = ''
        + '<div style="width:280px;margin:0 auto;text-align:left;line-height:1.25;">'
        +   '<div style="margin-bottom:8px;text-align:center;font-size:13px;font-weight:700;color:#2f6a9b;">Detalle de registros</div>'
        +   '<div style="border:1px solid #e0e8ef;border-radius:3px;overflow:hidden;font-size:12px;">'
        +     '<div style="display:table;width:100%;background:#eef8f0;">'
        +       '<div style="display:table-cell;padding:5px 8px;color:#2e7d32;">Validos</div>'
        +       '<div style="display:table-cell;padding:5px 8px;text-align:right;font-weight:700;color:#2e7d32;">' + rows.length + '</div>'
        +     '</div>'
        +     '<div style="display:table;width:100%;background:#eaf4fb;border-top:1px solid #e0e8ef;">'
        +       '<div style="display:table-cell;padding:5px 8px;color:#2f6a9b;">Monto</div>'
        +       '<div style="display:table-cell;padding:5px 8px;text-align:right;font-weight:700;color:#2f6a9b;">$' + totFmt + '</div>'
        +     '</div>';
    if (alertas > 0) {
        msg += ''
            + '<div style="display:table;width:100%;background:#fdf0f0;border-top:1px solid #e0e8ef;">'
            +   '<div style="display:table-cell;padding:5px 8px;color:#c62828;">Omitidos</div>'
            +   '<div style="display:table-cell;padding:5px 8px;text-align:right;font-weight:700;color:#c62828;">' + alertas + '</div>'
            + '</div>';
    }
    msg += '</div>'
        +   '<div style="margin-top:8px;text-align:center;font-size:12px;font-weight:600;color:#37474f;">Confirmar guardado?</div>'
        + '</div>';

    $.createDialogConfirm(msg, null, function () {
        $.post('', {
            saveAnticiposIniciales: true,
            Atp_Obs: $('#Atp_Obs').val(),
            items: rows
        }, function (r) {
            if (r && r.success) {
                if (r.link) $('#impCompr').attr('href', r.link);
                $('#successMsg').text(r.message || '');
                $('#successDialog').dialog('open');
                nuevoLoteAnticipos();
            } else {
                $.alert((r && r.message) ? r.message : 'No se pudo guardar el lote.');
            }
        }, 'json').fail(function () {
            $.alert('El servidor no respondio.');
        });
    });
}

function nuevoLoteAnticipos() {
    limpiarProveedor();
    $('#Atp_Obs,#ValorCap').val('');
    gridAnticipos.clearGrid();
    recalcularTotalLote();
}
