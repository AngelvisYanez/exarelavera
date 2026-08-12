var grid = $('#searchGrid');
var arrayAsiento = [],
    arrayCheques = [],
    arrayModAsiento = [],
    arrayCuentasPlan = [],
    arrayDetAsiento = [];

var perCodAct = 0,
    existeCheq = false;

$(function () {
    $("#successDialog").createDialog({ width: 500, height: 200, icon: 'print' });
    $("#verPagosDialog").createDialog({ width: 400, height: 290, icon: 'info-sign' });
    $("#verPagosDialogMod").createDialog({ width: 700, height: 450, icon: 'info-sign' });
    $("#verAsientoDialogMod").createDialog({ width: 700, height: 350, icon: 'info-data' });
    $("#cruceDialog").createDialog({ width: 900, height: 485, icon: 'info-sign' });
    $('#pagosDialog').createDialog({ height: 325, icon: 'usd' });
    $("#tabs_ant_det").tabs();
    $('#tabs_sub_ant_det').tabs();
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
    $('.pagination').find('li a').click(function () {
        $('.pagination').find('li').removeClass('active');
        $(this).parent().addClass('active');
        // Usar data-value en lugar del texto para obtener el valor del filtro
        var valorFiltro = $(this).attr('data-value');
        if (valorFiltro) {
            $('#letra').val(valorFiltro);
        } else {
            // Fallback: si no hay data-value, usar el texto (compatibilidad)
            $('#letra').val($(this).text());
        }
        busquedaAjax();
    });
    changePeriodo();
    // Limpiar clases de validación después de inicializar y establecer fechas
    // Asegurar que los labels mantengan solo alert-info
    setTimeout(function () {
        $('#txt_fec_ini, #txt_fec_fin').each(function () {
            var $input = $(this);
            // Buscar el input-group-addon asociado (puede estar antes o después)
            $input.siblings('.input-group-addon').removeClass('alert-warning alert-danger');
            // También limpiar el estado de validación
            $input.fieldValid('return');
        });
    }, 100);
    createGrid();
    // Primera carga tras crear el grid: dar tiempo a que changePeriodo haya rellenado fechas y luego cargar con postData correcto
    setTimeout(function () { busquedaAjax(); }, 150);
    crearGridShowPagosAsi();
    crearGridshowPagosChe();
    createPagosModGrid();
    gridCruce();
    createGridShowAsiDetalle();
    gridCruce();
    gridCuentasCruce();
    changeCuentaCod();

    $('#editInicialDialog').createDialog({
        width: 620,
        height: 300,
        icon: 'pencil',
        modal: true,
        resizable: false,
        dialogClass: 'ini-edit-dialog'
    });
    $('#provIniDialog').createDialog({ width: 560, height: 450, modal: true });
    $.createSearchDialog('provIniDialog', [
        { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15, align: 'center', hidden: true },
        { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Proveedor', name: 'nombre', width: 100 },
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectProveedorIniMod } }
    ], null, null, null, { headertitles: true }, { title: 'Proveedor', text: 'searchPrv' });

    $.createSearchDialog('proveedoresDialog', [
        { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15, align: "center", hidden: true },
        { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Proveedor', name: 'nombre', width: 100 },
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectProveedorCruce } }
    ], null, null, null, { headertitles: true }, { title: 'Proveedor', text: 'searchPrv' });
});

function createGrid() {
    grid.createGrid({
        caption: 'Anticipos', stateCol: 'Atp_Est',
        height: '300',
        url: '',
        datatype: 'local',
        data: [],
        colModel: [
            { label: 'Cod. Int.', name: 'Atp_Cod', key: true, width: 25, align: "left" },
            { label: 'No. Compr.', name: 'codigoCompra', width: 30, align: "left" },
            { label: 'Fecha', name: 'Atp_Fec', width: 30, align: "left" },
            { label: ' ', name: 'Prs_Cod', hidden: true },
            { label: ' ', name: 'Pap_Cod', hidden: true },
            { label: ' ', name: 'Prv_Cod', hidden: true },
            { label: ' ', name: 'Com_Cod', hidden: true },
            { label: '', name: 'Atp_Est', hidden: true },
            { label: '', name: 'Pag_Des', hidden: true },
            { label: '', name: 'Com_Est', hidden: true },
            { label: 'C&eacute;dula', name: 'cedProv', width: 40, align: "left", cellattr: function () { return 'style="' + excelFormats.text + '"'; } },
            { label: 'Proveedor', name: 'nombre', width: 100, align: "left" },
            { label: 'Direci&oacute;n', name: 'Prs_Dir', hidden: true, width: 100, align: "left" },
            { label: 'Valor', name: 'sumaAtpVal', width: 60, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Pagos', name: 'sumaDacVal', width: 60, align: 'right', formatter: function (cellvalue, options, rowObject) { if (rowObject['sumaDacVal'] === '' || rowObject['sumaDacVal'] === null) { return "0.00"; } else { return formatMoney(rowObject['sumaDacVal']); } } },
            { label: 'Saldo', name: 'tot_anti', width: 60, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' }, summaryType: "sum" },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_anti', width: 40, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    var parm_anu = [rowObject];
                    var parm_getdet = [rowObject];
                    if (rowObject.Atp_Est === "I") {
                        return $.createIcon('remove red');
                    }
                    var btns = $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info');
                    if (esAnticipoInicial(rowObject)) {
                        if (!tieneConsumos(rowObject)) {
                            btns += "&nbsp;" + $.getGridButton(modificarAnticipoInicial, parm_getdet, 'Editar anticipo inicial', 'pencil', '', 'success');
                            btns += "&nbsp;" + $.getGridButton(preanularAnticipo, parm_anu, 'Anular anticipo', 'remove', '', 'danger');
                        }
                        return btns;
                    }
                    if (rowObject.Atp_Est !== "A" || rowObject.Pag_Des == "Anticipos") {
                        return btns;
                    }
                    btns += "&nbsp;" + $.getGridButton(modificarAnticipo, parm_getdet, 'Modificar anticipo', 'pencil', '', 'success');
                    btns += "&nbsp;" + $.getGridButton(preanularAnticipo, parm_anu, 'Anular anticipo', 'remove', '', 'danger');
                    return btns;
                }
            }

        ],
        footerrow: true,
        userDataOnFooter: true,
        subGrid: true,
        rowNum: 100,
        rowList: [25, 50, 100, 200,500],
        gridview: true,
        viewrecords: true,
        subGridOptions: {
            "plusicon": "ui-icon-triangle-1-e",
            "minusicon": "ui-icon-triangle-1-s",
            "openicon": "ui-icon-arrowreturn-1-e",
            "reloadOnExpand": false,
            "selectOnExpand": true
        },
        subGridRowExpanded: function (subgrid_id, row_id) {
            let subgrid_table_id = subgrid_id + "_t";
            let rowData = $("#searchGrid").getRowData(row_id);
            $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
            $("#" + subgrid_table_id).createGrid({
                url: "?movAnticipo=" + rowData['Atp_Cod'] + "&Pec_Cod=" + $("#Pec_Cod").val() + "&txt_fec_ini=" + $("#txt_fec_ini").val() + "&txt_fec_fin=" + $("#txt_fec_fin").val(),
                datatype: "json",
                regional: 'es',
                height: 'auto',
                responsive: true,
                colModel: [
                    { label: '', name: 'Atp_Cod', key: true, hidden: true },
                    { label: 'Compr.', name: 'Com_Cod', width: 20, align: "center", hidden: true },
                    { label: '', name: 'Atp_Est', hidden: true },
                    { label: '', name: 'Tia_Cod', hidden: true },
                    { label: '', name: 'Com_Num', hidden: true },
                    { label: 'No. Compr.', name: 'codigoCompra', width: 30, align: "left" },
                    { label: 'Fecha', name: 'Com_Fec', width: 20, align: "left" },
                    { label: 'Observaci&oacute;n', name: 'Atp_Obs', width: 90, align: "left" },
                    { label: 'Concepto', name: 'Com_Con', width: 50, align: "left" },
                    { label: 'Valor', name: 'sumaDacVal', width: 50, align: 'right', formatter: function (cellvalue, options, rowObject) { return formatMoney(rowObject['Dac_Val']); } },
                    {
                        label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_sub_anti', width: 40, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, o) {
                            var parm_getdet = [o];
                            return $.getGridButton(verMovimiento, parm_getdet, 'ver asiento', 'info-sign', '', 'info') + "&nbsp;" +
                                (o.Pap_Es2 == 'M' ? $.getGridButton(editaConsumo, o, 'Editar Consumo', 'pencil', '', 'success') : '') + "&nbsp;" +
                                $.getGridButton(imprimirAsiento, parm_getdet, 'Imprimir diario', 'print', '', 'primary') + "&nbsp;" +
                                (o.Pap_Es2 == 'M' ? $.getGridButton(preanularConsumo, o, 'Anular Consumo', 'remove', '', 'danger') : '');
                        }
                    }
                ]
            });
        },
        loadComplete: function (data) {
            calculateValFooter();
            cellColors();
        },
        loadError: function () {
            // No mostrar mensaje en carga inicial; el usuario puede usar Buscar si falla
            $(this).clearGridData(true);
        }
    }, false, '#searchGridPager', {
        refresh: true,
        view: true
    }).gridButtonsAdd([
        {
            caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                if ($('#chkImprimirTodos').is(':checked')) {
                    exportarOImprimirTodos('excel');
                } else {
                    grid.jqGrid('exportGridExcel', {
                        nombre: 'Ant-Prov',
                        hoja: 'HOJA 1',
                        footer: true
                    });
                }
            }
        },
        {
            caption: 'Exportar PDF', buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                if ($('#chkImprimirTodos').is(':checked')) {
                    exportarOImprimirTodos('pdf');
                } else {
                    imprimir();
                }
            }
        },
        {
            caption: 'Expandir Todos', buttonicon: 'glyphicon glyphicon-resize-full',
            onClickButton: function () {
                ExpandirAll();
            }
        },
        {
            caption: 'Contraer Todos', buttonicon: 'glyphicon glyphicon-resize-small',
            onClickButton: function () {
                ContraerAll();
            }, hidden: true
        }
    ]);

    function ExpandirAll() {
        let ids = grid.getDataIDs();
        for (let i = 0; i < ids.length; i++) {
            grid.expandSubGridRow(ids[i]);
        }
    }

    function ContraerAll() {
        let ids = grid.getDataIDs();
        for (let i = 0; i < ids.length; i++) {
            grid.collapseSubGridRow(ids[i]);
        }
    }

    function imprimir() {
        $('#tablaReporte').html(grid.jqGrid('exportGridInnerHTML', {
            footer: true,
            generated: false,
            removeHiddens: true,
            removeCols: [1, 10]
        }));
        $('#imprimir').printElement();
    }

    function exportarOImprimirTodos(tipo) {
        var url = grid.jqGrid('getGridParam', 'url') || '';
        // Usar el mismo origen de datos que el grid (formulario de búsqueda) para que el servidor reciba anticiposAjax y filtros
        var postData = $.extend(true, {}, $('#searchAnticipos').getData('anticiposAjax') || {}, {
            page: 1,
            rows: 999999,
            sidx: grid.jqGrid('getGridParam', 'sidx') || '',
            sord: grid.jqGrid('getGridParam', 'sord') || 'asc'
        });
        $('#loader').show();
        $.get(url, postData, null, 'json')
            .done(function (resp) {
                if (!resp || !Array.isArray(resp.rows)) {
                    $.alert(resp && resp.error ? resp.error : 'No hay registros para exportar o imprimir.');
                    return;
                }
                if (resp.rows.length === 0) {
                    $.alert('No hay registros para exportar o imprimir.');
                    return;
                }
                var oldPage = grid.jqGrid('getGridParam', 'page');
                var oldRowNum = grid.jqGrid('getGridParam', 'rowNum');
                grid.jqGrid('clearGridData', true);
                // Mostrar todas las filas (sin paginación) para que la exportación/impresión incluya todo
                grid.jqGrid('setGridParam', {
                    datatype: 'local',
                    data: resp.rows,
                    rowNum: Math.max(resp.rows.length, 999999),
                    page: 1
                });
                grid.trigger('reloadGrid');
                calculateValFooter();
                cellColors();
                if (tipo === 'excel') {
                    grid.jqGrid('exportGridExcel', {
                        nombre: 'Ant-Prov',
                        hoja: 'HOJA 1',
                        footer: true
                    });
                } else {
                    imprimir();
                }
                grid.jqGrid('setGridParam', { datatype: 'json', page: oldPage, rowNum: oldRowNum });
                grid.trigger('reloadGrid');
            })
            .fail(function () {
                $.alert('Error al obtener los datos. No se pudo exportar o imprimir.');
            })
            .always(function () {
                $('#loader').hide();
            });
    }
}

/****************************************************************/
/************************** jose cumbicos ***********************/
function selectProveedorCruce(proveedor) {
    $('#PrsCed').html(proveedor.Prs_Ced);
    $('#Prs_Nom_Pagos').val(proveedor.nombre);
    $('#Prv_Cod_Pagos').val(proveedor.Prv_Cod);
    $('#Prs_Cod_Pagos').val(proveedor.Prs_Cod);
    $('#proveedoresDialog').dialog('close');
    $('#crucesGrid').clearGrid(true);
    $.get('', { anticiposCruceAjax: true, Prv_Cod: proveedor.Prv_Cod }, function (r) {
        if (r.rows.length > 0)
            $('#crucesGrid').setRows(r.rows);
    }, 'json')
        .fail(function (error) {
            console.log("El Servidor ha fallado en responder!");
        });
}

function habilitaCacilleros(tipoPago) {
    //`
    $('.Bloqueo').prop('disabled', true);
    $('.' + tipoPago).prop('disabled', false);
    //$('#infoCruce').find('.' + tipoPago).removeClass('hidden');
    //$('#infoCruce').find('.' + tipoPago).find('.form-control').prop('required', true);

    $('#BanCod option').hide();
    if (tipoPago === 'Efectivo' || tipoPago === 'Deposito')
        $('#BanCod option[data-tip="C"]').show();
    if (tipoPago == 'Transferencia' || tipoPago == 'Deposito' || tipoPago == 'Cheque')
        $('#BanCod option[data-tip="B"]').show();

    $('#BanCod option').filter(function () {
        return $(this).css('display') !== 'none';
    }).first().prop('selected', true);
    if (tipoPago == 'Otros')
        $('#btnCuenta').removeClass('disabled');
    else {
        $('#btnCuenta').addClass('disabled');
        $('#Pld_Des_Otr').val(''); $('#infPldCdc').html('');
    }
}

function preanularConsumo(o) {
    $.createDialogConfirm('¿Est&aacute; seguro que desea anular el Consumo?', o, saveBajaConsumo);
}

function saveBajaConsumo(o) {
    $.post("", { bajaConsumoAjax: true, Com_Cod: o.Com_Cod }, function (r) {
        if (r['success'] === true) {
            $.alert("¡Se Anulo Correctamente!");
            grid.trigger("reloadGrid");
        } else {
            $.alert(r['message']);
        }
    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });
}

function validaNumChequeExt(num) {
    $.getDataJson('', { verificaChequeExt: true, Che_Num: num, Ban_Cod: $('#BakCod').val(), Che_Cta: $('#PapCtd').val() }, (r) => {
        //resolve(r.numCheque);
        if ($.isEmpty(r.numCheque))
            $("#estadoNumChe").removeClass("fa fa-close").addClass("fa fa-check").css("color", "green").attr('title', 'Numero Aceptado');
        else {
            $("#estadoNumChe").removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title', 'El Numero <b>' + num + '</b> ya Existe !');
            $('#cruceForm input[name="Che_Num"]').val('');
        }
    }, (err) => {
        reject(err);
    });
}

function validaNumCheque(num) {
    $.getDataJson('', { verificaCheque: true, Che_Num: num, Ban_Cod: $('#BanCod').val() }, (r) => {
        //resolve(r.numCheque);
        if ($.isEmpty(r.numCheque))
            $("#estadoNumChe").removeClass("fa fa-close").addClass("fa fa-check").css("color", "green").attr('title', 'Numero Aceptado');
        else {
            $("#estadoNumChe").removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title', 'El Numero <b>' + num + '</b> ya Existe !');
            $('#cruceForm input[name="Che_Num"]').val('');
        }
    }, (err) => {
        reject(err);
    });
}

function cambiarCuenta(row) {
    $('#cruceForm input[name="Pld_Cod_Otr"]').val(row.Pld_Cod);
    $('#cruceForm input[name="Pld_Des_Otr"]').val(row.Pld_Des);
    $('#infPldCdc').html(row.Pld_Cdc);
    $('#cuentasDialog').dialog('close');
}

function gridCuentasCruce() {
    $.createSearchDialog('cuentasDialog', [
        { label: 'Cod. Int.', name: 'Pld_Cod', width: 20, align: "left" },
        { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50, align: "left" },
        { label: 'Cuenta Contable', name: 'Pld_Des', width: 100, align: "left" },
        {
            label: '&nbsp;', name: 'plsel', width: 15, align: 'center', viewable: false, title: false,
            formatter: function (cellvalue, options, rowObject) {
                return $.getGridButton(cambiarCuenta, rowObject, 'Seleccionar cuenta', 'check', '', 'success');
            }
        }
    ], null, null, null, null, {
        title: 'Cuenta',
        options: [
            { label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' },
            { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }
        ]
    })
        .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:</label><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> <input name="Pec_Cod" type="hidden" /><input name="Asi_Cod" type="hidden" /></div>');
}

function gridCruce() {
    $('#crucesGrid').createGrid({
        viewrecords: false,
        caption: "<center>Anticipos del Proveedor</center>",
        data: [], rowNum: 100, height: 130, width: 850, footerrow: true, responsive: false, totalCols: ['Atp_Val', 'cruce', 'pendiente'],
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            //{ label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: 'ID', key: true, name: 'Atp_Cod', width: 6 },
            { label: 'Diario', name: 'Com_Num', width: 10, align: "left" },
            { label: 'Fecha', name: 'Atp_Fec', width: 10, align: "center" },
            { label: 'Observ.', name: 'Atp_Obs', width: 15, align: "left" },
            { label: '<i class="ui-icon ui-icon-circle-check"></i>', name: 'chkAnt', align: "center", width: 4, formatter: 'checkboxExa', formatoptions: { dataEvents: { Change: 'setPagoCellAnt(this.dataset.rowId);' } } },
            //{ label: 'Obser.', name: 'Atp_Obs', width: 25, align: "left" },            
            { label: 'Saldo', name: 'Atp_Val', width: 10, align: 'right', formatter: 'currency', decimalPlaces: '2', summaryRound: 2, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' }, summaryTpl: "Total: {0}", summaryType: "sum" },
            { label: 'A Cruzar', name: 'cruce', classes: 'columnDisabled no_padding', width: 10, align: "right", title: false, formatter: 'textboxExa', formatoptions: { type: 'decimal', decimals: 8, attr: { disabled: 'disabled' }, dataEvents: { keyup: 'updateRowItemAnt.call(this);' } } },
            { label: 'Pendiente', name: 'pendiente', width: 10, align: 'right', formatter: 'currency', decimalPlaces: '2', summaryRound: 2, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' }, summaryTpl: "Total: {0}", summaryType: "sum" }
        ]
    }, true, '', { view: false });
}
function setPagoCellAnt(row) {
    let saldo = $('#crucesGrid').getCell(row, 'Atp_Val');
    if ($("#" + row + "_chkAnt").prop('checked')) {
        $("#" + row + "_cruce").prop("disabled", false);
        $("#" + row + "_cruce").val(saldo.toNum());
        $('#crucesGrid').setCell(row, 'pendiente', '0.00');
    } else {
        $("#" + row + "_cruce").prop("disabled", true);
        $("#" + row + "_cruce").val("0.00");
        $('#crucesGrid').setCell(row, 'pendiente', saldo);
    }
    let sum_cruce_ant = $('#crucesGrid').getGridSummary(['cruce']);
    let sum_pendi = $('#crucesGrid').getGridSummary(['pendiente'])
    $('#crucesGrid').jqGrid("footerData", "set", { cruce: "" + sum_cruce_ant.cruce.toFixed(2), pendiente: sum_pendi.pendiente.toFixed(2) });
    $('#PapVal').val(sum_cruce_ant.cruce.toFixed(2));
}
function updateRowItemAnt() {
    let rowId = $(this).data('rowId');
    let saldo = $('#crucesGrid').getCell(rowId, 'Atp_Val');
    let cruce_act = $('#crucesGrid').getCell(rowId, 'cruce');

    if (cruce_act.toNum() >= saldo.toNum()) {
        $("#" + rowId + "_cruce").val($.toFixed(saldo, 2));
        $('#crucesGrid').setCell(rowId, 'pendiente', '0.00');
        //$('#'+rowId+'_chk').prop("checked", false).trigger("onchange");
    } else
        $('#crucesGrid').setCell(rowId, 'pendiente', $.toFixed(saldo.toNum() - cruce_act.toNum(), 2));
    let sum_cruce_ant = $('#crucesGrid').getGridSummary(['cruce']);
    let sum_pendi = $('#crucesGrid').getGridSummary(['pendiente'])
    $('#crucesGrid').jqGrid("footerData", "set", { cruce: "" + sum_cruce_ant.cruce.toFixed(2), pendiente: sum_pendi.pendiente.toFixed(2) });
    $('#PapVal').val(sum_cruce_ant.cruce.toFixed(2));
}
function editaConsumo(data) {
    vaciarGridCruce();
    $("input[name='Prv_Cod_Pagos']").val(data.prvCod);
    $("input[name='Cli_Cod_Pagos']").val(data.Cli_Cod);
    $("input[name='Prs_Cod_Pagos']").val(data.Prs_Cod);
    $("input[name='Com_Cod']").val(data.Com_Cod);
    $("input[name='Prs_Nom_Pagos']").val(data.nombre);
    $('#PrsCed').html(data.Prs_Ced);
    $('#PapCtd').val(data.Pap_Ctd);
    $('#PapVal').val(data.Com_Val);
    $('#PapObs').val(data.Pap_Obs);
    $('#Com_Fec_Old').val(data.Com_Fec);
    $("#PagCod").val(data.Pag_Cod).trigger('change');
    $("input[name='Com_Fec']").val(data.Com_Fec);
    //$('#Com_Cod').val(data.Com_Cod); 
    //$('#Prs_Nom_Pagos').val(data.Prs_Nom); 
    //$('#PagCod').val(data.Pag_Cod); 
    //$('#Com_Fec').val(data.Com_Fec); 

    $.get('', { getDetalleConsumo: true, Asi_Cod: data.Asi_Cod, Com_Cod: data.Com_Cod, Prv_Cod: data.prvCod }, (r) => {
        if (r.che) {
            $("#CheNum").val(r.che.Che_Num);
            $("#CheFec").val(r.che.Che_Fec);
            $("#estadoNumChe").removeClass("fa fa-close").addClass("fa fa-check").css("color", "green").attr('title', 'Numero Aceptado');
        }
        $('#crucesGrid').setRows(r.det);
        $.each(r.det, function (i, v) { if (v.cruce * 1 > 0) $('#' + v.Atp_Cod + '_cruce').prop('disabled', false) });
        return false;

    }, 'json')
        .fail(function (error) {
            console.log("El Servidor ha fallado en responder!");
        });
    $('#cruceDialog').dialog('open');
}

function preSaveConsumo() {
    let data = $('#cruceForm').getData();
    data.Pld_Cod_banco = $('#BanCod option:selected').attr('data-pld');
    data.Pld_Des_banco = $('#BanCod option:selected').attr('data-des');
    data.Pap_Cto = $('#BanCod option:selected').attr('data-cta');
    data.Bak_Des_banco = $('#BakCod option:selected').attr('data-des');
    data.BakCod = $('#BakCod').val();
    data.Bak_Cta_banco = $('#Pem_Cba').val();
    data.tipo = $('#PagCod option:selected').attr('data-abr');
    data.anticipo = $.map($('#crucesGrid').getGridBatch(o => o.chkAnt === 'S'), o => [{ Atp_Cod: o.Atp_Cod, Acl_Cru: o.cruce }]);
    data.saveConsumoAjax = true;
    console.log(data);
    $.createDialogConfirm('Est&aacute; seguro que desea guardar los datos?', data, saveConsumo);
}

function saveConsumo(data) {
    $.saveDataJson('', data, function (r) {
        vaciarGridCruce();
        $('#cruceDialog').dialog('close');
        if ($.ifEmpty(r.link))
            window.open(r.link);
    }, function (r) {
        console.log(r);
    });
}

function vaciarGridCruce() {
    $('#PapObs').val('');
    $('#CheNum').val('');
    $('#PapCtd').val('');
    $('#PapVal').val('');
    $('#cheCli').val('');
    $('#Pld_Cod_Otr').val('');
    $('#Pld_Des_Otr').val('');
    $('#infPldCdc').html('');
    $("input[name='Com_Cod']").val('');
    $('#Prv_Cod_Pagos').val('');
    $('#Com_Fec_Old').val('');

    $('#Prs_Nom_Pagos').val('');
    $('#PrsCed').html('');
    $('#crucesGrid').clearGrid(true);
    $("#estadoNumChe").removeClass("fa fa-close").removeClass("fa fa-check")
}
/********************* fin *******************/

function verificaChequeEstado() {
    let ids = verficaDataInGrid();
    if ($.varValid(ids)) {
        for (let j = 0, z = ids.length; j < z; j++) {
            let getRowData = $('#pagos').jqGrid('getRowData', ids[j]);
            if (getRowData['grid_tipp'] !== 'inicial' && getRowData['Che_Est'] !== 'A') {
                //console.log(getRowData);
                $("#" + getRowData['index'] + "_Haber").attr("readonly", "");
            }
        }
    }
}

function cellColors() {
    let data = $('#searchGrid').jqGrid('getDataIDs');
    //console.log(data);
    if ($.varValid(data)) {
        for (let i = 0, z = data.length; i < z; i++) {
            //console.log(data[i]);
            let getRowData = $('#searchGrid').jqGrid('getRowData', data[i]);
            //console.log(getRowData);
            if (getRowData['Atp_Est'] === 'U') {
                //console.log(getRowData);
                $("tr#" + data[i] + ' td:not(.jqgrid-rownum)').addClass('cellGreen2');
            }
            if (getRowData['Atp_Est'] === 'C') {
                //console.log(getRowData);
                $("tr#" + data[i] + ' td:not(.jqgrid-rownum)').addClass('cellGray');
            }
        }
    }
}

// Variables para almacenar las fechas seleccionadas
let selectedStartDate = null;
let selectedEndDate = null;

function cambioPreiodoSearch(parm_peri) {
    var selectedOption = $("#Pec_Cod option:selected");
    var value = selectedOption.val();

    if (value === "T") {
        // Caso "Todos" - Extraer años solo de los periodos reales (excluyendo 'Todos' y 'Corte')
        const years = [];
        $("#Pec_Cod option").each(function () {
            const $option = $(this);
            // Excluir opciones no numéricas
            if ($option.val() !== "T" && $option.val() !== "Corte") {
                const year = parseInt($option.data("year"));
                if (!isNaN(year)) years.push(year);
            }
        });

        const minYear = years.length > 0 ? Math.min(...years) : new Date().getFullYear();

        // Establecer la fecha de inicio al año mínimo + 01-01
        const inicio = `${minYear}-01-01`;
        const fin = new Date(); // Fecha actual

        // Establecer fechas sin restricciones
        $("#txt_fec_ini").datepicker("setDate", inicio);
        $("#txt_fec_fin").datepicker("setDate", fin);

        // Remover límites del datepicker
        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", {
            minDate: null,
            maxDate: null
        });

        // Resetear las fechas seleccionadas
        selectedStartDate = null;
        selectedEndDate = null;
    } else if (value === "PorFechas") {

        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", {
            minDate: null,
            maxDate: null
        });

        // Forzar actualización del datepicker para que respete los límites
        $("#txt_fec_ini, #txt_fec_fin").datepicker("refresh");

        // Limpiar clases de validación de ambos labels
        $("#txt_fec_ini, #txt_fec_fin").each(function () {
            var $input = $(this);
            $input.siblings('.input-group-addon').removeClass('alert-warning alert-danger');
            $input.fieldValid('return');
        });
    } else if (value === "Corte") {
        // Extraer años solo de los periodos reales (excluyendo 'Todos' y 'Corte')
        const years = [];
        $("#Pec_Cod option").each(function () {
            const $option = $(this);
            // Excluir opciones no numéricas
            if ($option.val() !== "T" && $option.val() !== "Corte") {
                const year = parseInt($option.data("year"));
                if (!isNaN(year)) years.push(year);
            }
        });

        const minYear = years.length > 0 ? Math.min(...years) : new Date().getFullYear();

        // Establecer la fecha de inicio al año mínimo
        if (!selectedStartDate) {
            selectedStartDate = `${minYear}-01-01`;
        }
        if (!selectedEndDate) {
            selectedEndDate = new Date(); // Fecha actual
        }

        $("#txt_fec_ini").datepicker("setDate", selectedStartDate);
        $("#txt_fec_fin").datepicker("setDate", selectedEndDate);

        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", {
            minDate: new Date(minYear, 0, 1),
            maxDate: new Date()
        });
    } else {
        // Caso períodos normales
        var inicio = selectedOption.data('inicio');
        var fin = selectedOption.data('fin');

        $("#txt_fec_ini").datepicker("setDate", new Date(inicio));
        $("#txt_fec_fin").datepicker("setDate", new Date(fin));

        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", {
            minDate: new Date(inicio),
            maxDate: new Date(fin)
        });

        // Resetear las fechas seleccionadas
        selectedStartDate = null;
        selectedEndDate = null;
    }
}

// Evento para cambiar las fechas
$("#txt_fec_ini").change(function () {
    selectedStartDate = $(this).val(); // Actualiza la fecha de inicio seleccionada
});

$("#txt_fec_fin").change(function () {
    selectedEndDate = $(this).val(); // Actualiza la fecha de fin seleccionada
});

function changePeriodo() {
    var valorAnterior = null; // Almacenar valor anterior
    $('#Pec_Cod').on('change', function () {
        var sel_fecha = $(this).find('option:selected');
        var value = $(this).val();

        if (value === "PorFechas") {
            // Verificar si las fechas están vacías o son del formato por defecto
            var fechaIniActual = $("#txt_fec_ini").val();
            var fechaFinActual = $("#txt_fec_fin").val();

            var debeEstablecerFechas = (valorAnterior !== "PorFechas" || !fechaIniActual || !fechaFinActual);
            const hoy = new Date();
            const añoActual = hoy.getFullYear();
            const mesActual = hoy.getMonth(); // getMonth() devuelve 0-11

            // Crear fecha de inicio: año actual, mes actual, día 01
            const inicio = new Date(añoActual, mesActual, 1);
            const fin = hoy;

            // Formatear fecha de inicio como YYYY-MM-DD
            const añoInicio = inicio.getFullYear();
            const mesInicio = (inicio.getMonth() + 1).toString().padStart(2, '0');
            const diaInicio = inicio.getDate().toString().padStart(2, '0');
            const fechaInicioStr = `${añoInicio}-${mesInicio}-${diaInicio}`;

            // Formatear fecha fin como YYYY-MM-DD
            const añoFin = fin.getFullYear();
            const mesFin = (fin.getMonth() + 1).toString().padStart(2, '0');
            const diaFin = fin.getDate().toString().padStart(2, '0');
            const fechaFinStr = `${añoFin}-${mesFin}-${diaFin}`;

            $('#txt_fec_ini, #txt_fec_fin').datepicker("option", {
                minDate: null,
                maxDate: null
            });

            // Forzar actualización del datepicker para que respete los límites
            $('#txt_fec_ini, #txt_fec_fin').datepicker("refresh");

            // Solo establecer fechas automáticamente si se está cambiando DE otra opción A "PorFechas"
            if (debeEstablecerFechas) {
                // Establecer fechas solo con val para evitar que se abra el calendario
                $("#txt_fec_ini").val(fechaInicioStr);
                $("#txt_fec_fin").val(fechaFinStr);
            }

            // Limpiar clases de validación de ambos labels inmediatamente
            $("#txt_fec_ini, #txt_fec_fin").each(function () {
                var $input = $(this);
                $input.siblings('.input-group-addon').removeClass('alert-warning alert-danger');
                $input.fieldValid('return');
            });
        } else {
            // Para otros casos, usar data-inicio y data-fin
            $("#txt_fec_ini").val(sel_fecha.data('inicio'));
            $("#txt_fec_fin").val(sel_fecha.data('fin'));
            $('#txt_fec_ini').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
            $('#txt_fec_fin').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
        }

        // Limpiar clases de validación de ambos labels después de establecer fechas
        $('#txt_fec_ini, #txt_fec_fin').each(function () {
            var $input = $(this);
            // Buscar y limpiar el input-group-addon asociado
            $input.siblings('.input-group-addon').removeClass('alert-warning alert-danger');
            // Limpiar el estado de validación
            $input.fieldValid('return');
        });

        // Actualizar el valor anterior después del cambio
        valorAnterior = value;

        $('#txt_fec_ini').trigger('change');
        $('#txt_fec_fin').trigger('change');
    }).trigger('change');
}

function formatMoney(number, places, symbol, thousand, decimal) {
    number = number || 0;
    places = !isNaN(places = Math.abs(places)) ? places : 2;
    symbol = symbol !== undefined ? symbol : "$";
    thousand = thousand || ",";
    decimal = decimal || ".";
    var negative = number < 0 ? "-" : "",
        i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
    return symbol + negative + (j ? i.substr(0, j) + thousand : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
}

function calculateValFooter() {
    let ids = $('#searchGrid').jqGrid('getDataIDs');
    var valorDet = 0,
        valorAtp = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#searchGrid').jqGrid('getRowData', ids[i]);
        var detVal = (reg_pago['sumaDacVal'].replace(/[^0-9-.]/g, '') * 1);
        valorDet = valorDet + (detVal * 1);
        valorAtp = valorAtp + (reg_pago['sumaAtpVal'] * 1);
    }

    $('#searchGrid').jqGrid('footerData', 'set', { nombre: "<div style='text-align:right;'>TOTALES:</div>", tot_anti: $('#searchGrid').jqGrid('getCol', 'tot_anti', false, 'sum') });
    $('#searchGrid').jqGrid('footerData', 'set', { sumaDacVal: "" + valorDet });
    $('#searchGrid').jqGrid('footerData', 'set', { sumaAtpVal: "" + valorAtp });

}

function calCheFooter() {
    //showPagosChe
    let ids = $('#showPagosChe').jqGrid('getDataIDs');
    var valor = 0;
    //console.log(ids.length);
    for (let j = 0; j < ids.length; j++) {
        let reg_pagoC = $('#showPagosChe').jqGrid('getRowData', ids[j]);
        //console.log(reg_pagoC);
        valor = valor + (reg_pagoC['Che_Val'] * 1);
    }
    $('#showPagosChe').jqGrid('footerData', 'set', { Che_Obs: "<div style='text-align:right;'>TOTALES:</div>" });
    $('#showPagosChe').jqGrid('footerData', 'set', { Che_Val: "" + valor });
}

function setColorGrid() {
    let ids = $('#showPagosChe').jqGrid('getDataIDs');
    for (let j = 0; j < ids.length; j++) {
        let getRow = $('#showPagosChe').jqGrid('getRowData', ids[j]);
        if (getRow['Che_Est'] === 'P') {
            $('#showPagosChe').find("tr#" + (j + 1) + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
        }
    }
}

function calSumFooter() {
    let ids = $('#showPagosAsi').jqGrid('getDataIDs');
    var valorDebe = 0,
        valorHaber = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#showPagosAsi').jqGrid('getRowData', ids[i]);
        //console.log(reg_pago);
        valorDebe = valorDebe + (reg_pago['Debe'] * 1);
        valorHaber = valorHaber + (reg_pago['Haber'] * 1);
    }
    $('#showPagosAsi').jqGrid('footerData', 'set', { Asi_Glo: "<div style='text-align:right;'>TOTALES:</div>" });
    $('#showPagosAsi').jqGrid('footerData', 'set', { Debe: "" + valorDebe });
    $('#showPagosAsi').jqGrid('footerData', 'set', { Haber: "" + valorHaber });

}

function calFooterSubGrid() {
    let ids = $('#showSubGridAsi').jqGrid('getDataIDs');
    var valorDebe = 0,
        valorHaber = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#showSubGridAsi').jqGrid('getRowData', ids[i]);
        //console.log(reg_pago);
        valorDebe = valorDebe + (reg_pago['Debe'] * 1);
        valorHaber = valorHaber + (reg_pago['Haber'] * 1);
    }
    $('#showSubGridAsi').jqGrid('footerData', 'set', { Asi_Glo: "<div style='text-align:right;'>TOTALES:</div>" });
    $('#showSubGridAsi').jqGrid('footerData', 'set', { Debe: "" + valorDebe });
    $('#showSubGridAsi').jqGrid('footerData', 'set', { Haber: "" + valorHaber });
}

function verMovimiento(params) {
    //console.log('lola verMovimiento', params);
    const getDataByDet = async () => {
        arrayDetAsiento.length = 0;
        arrayDetAsiento = await asientoSubGridAsync(params[0]);
    }
    getDataByDet().then(() => {
        $('#showSubGridAsi').setRows(arrayDetAsiento);
        calFooterSubGrid();
    });

    $('#verAsientoDialogMod').dialog('open');
}

function esAnticipoInicial(row) {
    if (!row) return false;
    if (row.codigoCompra === 'INICIAL' || row.Com_Est === 'E') return true;
    var pagDes = $.trim(row.Pag_Des || '').toUpperCase();
    return pagDes.indexOf('INICIAL') >= 0;
}

function tieneConsumos(row) {
    if (!row) return false;
    if (row.Atp_Est === 'U' || row.Atp_Est === 'C') return true;
    var consumo = parseFloat(String(row.sumaDacVal || '0').replace(/[^0-9.-]/g, ''));
    return !isNaN(consumo) && consumo > 0;
}

function modificarAnticipoInicial(params) {
    var row = params[0];
    if (tieneConsumos(row)) {
        $.alert('El anticipo tiene consumos registrados y no puede editarse.');
        return;
    }
    if (row.Atp_Est !== 'A') {
        $.alert('Solo se pueden editar anticipos iniciales activos sin consumos.');
        return;
    }
    var valor = row.Atp_Val || row.sumaAtpVal || '0';
    $('#ini_Atp_Cod').val(row.Atp_Cod);
    $('#ini_Com_Cod').val(row.Com_Cod);
    $('#ini_Prv_Cod').val(row.Prv_Cod);
    $('#ini_Prs_Cod').val(row.Prs_Cod || '');
    $('#ini_Prs_Ced').val(row.cedProv || row.Prs_Ced || '');
    $('#ini_nombre').val(row.nombre || '');
    $('#ini_Atp_Fec').val(row.Atp_Fec || '');
    $('#ini_Atp_Val').val(parseFloat(String(valor).replace(/[^0-9.-]/g, '')) || 0);
    var fAct = $('#ini_Atp_Fec').val();
    if (typeof peridodo !== 'undefined' && peridodo.length) {
        peridodo.forEach(function (per) {
            if (fAct >= per.Pec_Fei && fAct <= per.Pec_Fef) {
                $('#ini_Atp_Fec').dateLimits(per.Pec_Fei, per.Pec_Fef);
            }
        });
    }
    $('#editInicialDialog').dialog('open');
}

function selectProveedorIniMod(proveedor) {
    $('#ini_Prv_Cod').val(proveedor.Prv_Cod);
    $('#ini_Prs_Cod').val(proveedor.Prs_Cod || '');
    $('#ini_Prs_Ced').val(proveedor.Prs_Ced || '');
    $('#ini_nombre').val(proveedor.nombre || '');
    $('#provIniDialog').dialog('close');
}

function guardarEditInicial() {
    var data = $('#editInicialForm').getData('save');
    var valor = parseFloat(String(data.Atp_Val || '0').replace(/,/g, ''));
    if (!data.Atp_Fec || !data.Prv_Cod || isNaN(valor) || valor <= 0) {
        $.alert('Complete fecha, proveedor y valor mayor a cero.');
        return;
    }
    data.Atp_Val = valor;
    data.saveEditInicialAjax = true;
    $.createDialogConfirm('&iquest;Guardar los cambios del anticipo inicial?', null, function () {
        $.saveDataJson('', data, function (resp) {
            if (resp.success) {
                $('#editInicialDialog').dialog('close');
                busquedaAjax();
            }
        });
    });
}

function verAnticipo(params) {
    $("#showPagosAsi").updateGridsSizes();
    $("#showPagosChe").updateGridsSizes();

    $("#ant_detasi").children("a").trigger("click");

    $('#showPagosAsi').clearGrid(true);
    $('#showPagosChe').clearGrid(true);
    $('#showPagosAsi').trigger("reloadGrid");
    $('#showPagosChe').trigger("reloadGrid");

    $("#prov_show").val(params[0].nombre);
    $("#ruc_show").val(params[0].Prs_Ced);
    $("#compr_show").val(params[0].codigoCompra);
    $("#fec_show").val(params[0].Atp_Fec);
    $("#obs_show").val(params[0].Atp_Obs);
    $("#usuario").html(params[0].usuario + '-');
    $("#Com_Sys").html(params[0].Com_Sys);

    const getDataAsiento = async () => {
        arrayAsiento.length = 0;
        arrayAsiento = await asientoAsync(params[0]);
    }
    const getDataCheque = async () => {
        arrayCheques.length = 0;
        arrayCheques = await chequesAsync(params[0]);
    }
    getDataAsiento().then(() => {
        $('#showPagosAsi').setRows(arrayAsiento);
        calSumFooter();
    });
    getDataCheque().then(() => {
        if (arrayCheques.length === 0) {
            $("#ant_detche").hide();
        } else {
            $("#ant_detche").show();
            $('#showPagosChe').setRows(arrayCheques);
            calCheFooter();
            setColorGrid();
        }

    });

    $('#verPagosDialogMod').dialog('open');
    $('div#verPagosDialog').siblings('.ui-dialog-titlebar').children('span').text(params[0].nombre);
    //console.log(params);
}

function modificarAnticipo(parm_mod) {

    console.log('prueba antiicos');
    console.log(parm_mod);
    perCodAct = 0;
    //console.log(parm_mod);
    //console.log(peridodo);
    arrayModAsiento.length = 0;
    const getDataAsiMod = async () => { arrayModAsiento = await asientoAsync(parm_mod[0]); }
    getDataAsiMod().then(() => {
        $('#anticipoPrvForm').setData(parm_mod[0]);
        $("#Tia_Cod_temp").val(parm_mod[0].Tia_Cod);
        let f_Act = $('#anticipoPrvForm').find('#Atp_Fec').val();
        peridodo.forEach(per => {
            if (f_Act > per.Pec_Fei && f_Act < per.Pec_Fef) {
                $("#Atp_Fec").dateLimits(per.Pec_Fei, per.Pec_Fef);
            }
        });
        moveToUpdate();
        
        console.log(arrayModAsiento);
        llenarModAsient(arrayModAsiento);
        //llenar negociacion en caso de existir
        llenarNego(parm_mod[0]['Atp_Cod']);
        verificaChequeEstado();
        perCodAct = parm_mod[0]['Pec_Cod'];
        //console.log(perCodAct);
    });
}

function llenarModAsient(data) {
    // console.log(data);
    $('#pagos').clearGrid(true);
    $("#loader").show();
    let lengthDatos = data.length;
    var next = 0;
    let tipoData = '';
    data.forEach(respuesta => {
        //console.log(respuesta);
        if (respuesta['Asi_Deh'] === 'D') { tipoData = 'inicial'; } else { tipoData = 'pago'; }
        next = $("#pagos").jqGrid('getCol', 'index', false, 'max');
        next = (isNaN(next) ? 1 : next + 1);
        $("#pagos").jqGrid('addRowData', next, $.extend(respuesta, { index: next, grid_tipp: tipoData, Asi_Cod: respuesta['Asi_Cod'], Che_Cod: respuesta['Che_Cod'], Pap_Cod: respuesta['Pap_Cod'], Pag_Cod: respuesta['Pag_Cod'], Pag_Abr: respuesta['Pag_Abr'], Pag_Des: respuesta['Pag_Des'], Pld_Des: respuesta['Pld_Des'], Pld_Cdc: respuesta['Pld_Cdc'], Pap_Ctd: respuesta['Pap_Ctd'], Ban_Cod: respuesta['Ban_Cod'], Che_Est: respuesta['Che_Est'], Che_Num: respuesta['Che_Num'], Che_Fec: respuesta['Che_Fec'], Pap_Cto: respuesta['Pap_Cto'], Pld_Cod: respuesta['Pld_Cod'], Det_Tip: respuesta['Det_Tip'], Glosa: respuesta['Glosa'], Debe: respuesta['Debe'], Haber: respuesta['Haber'] }), 'last');

        $("#pagos").find('#' + next + '_Haber').on('change', function () {
            if ($(this).val() > 0) {
                //console.log($(this).val());
                let tnmGrid = $('#pagos').getGridBatch();
                reCalculateHaber(tnmGrid.length);
                $("#_Haber").attr("readonly", "");
                $("#" + next + "_Haber").css('text-align', 'right');

                formaterFotter(next);
            } else {
                $("#" + next + "_Debe").css('text-align', 'right');
                $("#" + next + "_Haber").attr("readonly", "");
            }

        }).trigger('change');

    });
    formaterFotter(next);
    if (data.length > 0) { $("#loader").hide(); }
}

function reCalculateHaber(tmano) {
    let grid = $("#pagos"),
        valDebe = 0,
        inxD = 0,
        indxH = 0,
        valHaber = 0;
    let tnmGrid = grid.getGridBatch();
    if (tmano === tnmGrid.length) {
        tnmGrid.forEach(gridTam => {
            //console.log(gridTam);
            if ((gridTam['Debe'] * 1) > 0 || gridTam['grid_tipp'] === 'inicial') {
                valDebe += (gridTam['Debe'] * 1);
                inxD = (gridTam['index'] * 1);
            }
            if ((gridTam['Haber'] * 1) > 0 || gridTam['grid_tipp'] === 'pago') {
                valHaber += (gridTam['Haber'] * 1);
                indxH = (gridTam['index'] * 1);
            }
        });
        if (valDebe !== valHaber) { grid.find('#' + inxD + '_Debe').val((valHaber * 1).toFixed(4)); }

        grid.jqGrid('footerData', 'set', { Asi_Glo: "<div style='text-align:right;'>TOTALES:</div>" });
        grid.jqGrid('footerData', 'set', { Debe: "" + formatMoney((valHaber * 1).toFixed(4)) });
        grid.jqGrid('footerData', 'set', { Haber: "" + formatMoney((valHaber * 1).toFixed(4)) });
        $('#totalFinal').val('' + valHaber);
    }
}

function createGridShowAsiDetalle() {
    $('#showSubGridAsi').createGrid({
        viewrecords: false,
        caption: "<center>Detalle del anticipo</center>",
        data: [],
        rowNum: 100,
        height: 180,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: '', name: 'Pap_Est', hidden: true },
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: 'Codigo', name: 'Pld_Cdc', width: 10, align: "left" },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, align: "left" },
            { label: 'Glosa', name: 'Asi_Glo', width: 25, align: "left" },
            {
                label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true,
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }
            },
            {
                label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'currency', editable: true,
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }
            }
        ]
    }, true, '', { view: false });
}

function createPagosModGrid() {
    $('#pagos').createGrid({
        viewrecords: false,
        data: [],
        rowNum: 100,
        height: 150,
        footerrow: true,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: ' ', name: 'Det_Tip', hidden: true },
            { label: ' ', name: 'grid_tipp', hidden: true },
            { label: ' ', name: 'Che_Cod', hidden: true },
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: 'Asi_Cod', name: 'Asi_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pap_Cod', name: 'Pap_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pag_Cod', name: 'Pag_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pag_Abr', name: 'Pag_Abr', hidden: true, classes: 'bgNoRight' },
            {
                label: 'Tipo', name: 'Pag_Des', width: 10, align: "center", classes: 'bgNoRight',
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.Asi_Deh === 'D') {
                        return "-";
                    } else {
                        return rowObject.Pag_Des
                    }
                }
            },
            { label: 'Ban_Cod', name: 'Ban_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Che_Num', name: 'Che_Num', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Che_Est', hidden: true },
            { label: 'Che_Fec', name: 'Che_Fec', hidden: true, classes: 'bgNoRight' },
            { label: 'Pap_Cto', name: 'Pap_Cto', hidden: true, classes: 'bgNoRight' },
            { label: 'Pap_Ctd', name: 'Pap_Ctd', hidden: true, classes: 'bgNoRight' },
            { label: 'Cuenta_Pld', name: 'Pld_Cod', width: 30, hidden: true, classes: 'bgNoRight' },
            { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 10, classes: 'bgNoRight' },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, classes: 'bgNoRight' },
            { label: 'Glosa', name: 'Asi_Glo', width: 20, editable: true },
            {
                label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'textboxExa',
                formatoptions: {
                    attr: { readonly: 'readonly' }
                }
            },
            { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'textboxExa' },
            //{ label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { defaultValue: '' }, editoptions: { dataInit: function(element) { $(this).createInputDiario3(element, "D", "Det_Tip"); } } },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'Pag_Item', width: 10, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.Asi_Deh === 'D') {
                        return "-";
                    } else if (rowObject.Che_Est === 'C' || rowObject.Che_Est === 'U') {
                        return $.createIcon('lock orange', false, 'title="Cheque usado."');
                    } else {
                        return $.getGridButton(borrarPago, rowObject, 'Borrar pago', 'remove', '', 'danger');
                    }
                },
                title: false
            }
        ],
        loadComplete: function () {
            //verificaChequeEstado();
        }
    }, true, 'pagosPager', { view: false }).gridButtonsAdd([
        {
            caption: 'Agregar Pago', id: 'btn_mod_agr', buttonicon: 'glyphicon glyphicon-plus',
            onClickButton: function () { /*agregarFila(1);*/ openDialogPagos(); }
        }
    ]);
}

function crearGridShowPagosAsi() {
    $('#showPagosAsi').createGrid({
        viewrecords: false,
        caption: "<center>Detalle del anticipo</center>",
        data: [],
        rowNum: 100,
        height: 100,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pap_Est', hidden: true },
            { label: 'Codigo', name: 'Pld_Cdc', width: 10, align: "left" },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, align: "left" },
            { label: 'Glosa', name: 'Asi_Glo', width: 25, align: "left" },
            {
                label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true,
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }
            },
            {
                label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'currency', editable: true,
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }
            }
        ]
    }, true, '', { view: false });
}

function crearGridshowPagosChe() {
    $('#showPagosChe').createGrid({
        viewrecords: false,
        caption: "<center>Cheques emitidos en este anticipo</center>",
        data: [],
        rowNum: 100,
        height: 100,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'index', key: true, name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pap_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Atp_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Atp_Val', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Com_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Tia_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pld_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Atp_Fec', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Che_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Prv_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Ban_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Asi_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'No. Che.', name: 'Che_Num', width: 15, align: "left" },
            { label: 'Fecha', name: 'Che_Fec', width: 15, align: "left" },
            { label: 'Observaci&oacute;n', name: 'Che_Obs', width: 25, align: "left" },
            {
                label: 'Valor', name: 'Che_Val', width: 15, align: 'right', formatter: 'currency', editable: true,
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }
            },
            { label: '', name: 'Che_Est', hidden: true, width: 15, align: "left" },
            { label: 'Estado', name: 'estado', width: 15, align: "center" },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_anti', width: 10, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    //console.log(rowObject);
                    if (rowObject.Che_Est === 'P') {
                        return "-";
                    } else {
                        if (rowObject.Che_Est === 'A') {
                            if (rowObject.Pap_Est === 'A') {
                                return $.getGridButton(preProtestarCheque, rowObject, 'Marcar como protestado', 'ban-circle', '', 'danger');
                            } else {
                                return $.createIcon('lock orange', false, 'title="El Cheque esta siendo usado!"');
                            }
                        } else {
                            return $.createIcon('lock orange', false, 'title="Esta siendo usado!"');
                        }
                    }
                }
            }
        ]
    }, true, '', { view: false });
}

function imprimirAsiento(params) {
    $.saveDataJson("", { impAsiento: true, params: params }, function (responce) {
        if (responce['success']) {
            window.open(responce['link']);
            return false;
        }
    });
}

function preanularAnticipo(params) {
    //console.log(params);
    $.createDialogConfirm('¿Est&aacute; seguro que desea anular este anticipo?', null, function () {
        $.saveDataJson("", { anularAnticipo: true, data: params }, function (responce) {
            if (responce['success']) {
                $("#searchGrid").trigger("reloadGrid");
                return false;
            }
        });
    });
}

function preProtestarCheque(row) {
    //console.log(row);
    let shChq = false,
        i = 0;
    row['fechaActual'] = new Date();
    $.createDialogConfirm('¿Est&aacute; seguro que desea marcar como protestado este cheque?', null, function () {
        //console.log(row);
        $.saveDataJson("", { protestarCheq: true, row: row }, function (responce) {
            if (responce['success']) {

                $("#searchGrid").trigger("reloadGrid");
                $('#verPagosDialogMod').dialog('close');
                $('#impCompr').attr('href', responce['link']);
                $.alert('La transacci&oacute;n se realizo con exito.');
                // Verifica chueques
                $("#Che_imp option").remove();
                const vrchq = async () => {
                    arrayCheques.length = 0;
                    arrayCheques = await chequesAsync(row);
                }
                vrchq().then(() => {
                    console.log(arrayCheques);
                    arrayCheques.forEach((data) => {
                        //console.log(data);
                        if (data['Che_Est'] === "A") {
                            i++;
                            shChq = true;
                            $("#Che_imp").append("<option value='" + i + "' data-link='?codigo2=" + data['Che_Cod'] + "&asi=" + data['Asi_Cod'] + "&ban=" + data['Ban_Cod'] + "&pro=" + data['Prv_Cod'] + "'>No.:" + data['Che_Num'] + " - Valor:" + data['Che_Val'] + "</option>");
                            cambiarChek();
                        }
                    });
                    if (shChq) {
                        $("#successDialog").dialog({ width: 500, height: 355 });
                        $("#siche").removeAttr("hidden");
                    } else {
                        $("#successDialog").dialog({ width: 500, height: 200 });
                        $("#siche").attr("hidden", "");
                    }
                    $('#successDialog').dialog('open');
                });
                return false;
            }
        });
    });
}

function busquedaAjax() {
    //anticiposAjax
    //searchGrid
    $('#searchGrid').Search('#searchAnticipos', 'anticiposAjax');
}

async function asientoSubGridAsync(row) {
    let resultado = await getDataSubGridAsientoProm(row);
    return resultado;
}

async function asientoAsync(row) {
    let result = await getDataAsientosProm(row);
    return result;
}

async function chequesAsync(row) {
    let chq = await getDataChequesProm(row);
    return chq;
}

async function planCuentaWithNum(tipoAbr, pecCod) {
    let pln = await getNumCWithPlan(tipoAbr, pecCod);
    return pln;
}

async function getCheqNum(valor, banCod) {
    let numChq = await getNumCheque(valor, banCod);
    return numChq;
}

//Promesa plan de cuentas y No. Cuenta del banco para anticipos con cheques
function getNumCWithPlan(abr, pecCod) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getPlanCuentasCheq: true, Ban_Tip: abr, Pec_Cod: pecCod }, (result) => {
            resolve(result.getData);
        }, (err) => {
            reject(err);
        })
    });
}

//Promesa asiento detalle
function getDataSubGridAsientoProm(data) {
    //console.log(data);
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getSubGridAsient: true, Com_Cod: data['Com_Cod'] }, (result) => {
            resolve(result.dataSubAsiento);
        }, (err) => {
            reject(err);
        });
    });
}

// Promesa de asientos
function getDataAsientosProm(data) {
    //console.log(data);
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getAsientos: true, Com_Cod: data['Com_Cod'] }, (result) => {
            resolve(result.dataASiento);
        }, (err) => {
            reject(err);
        });
    });
}

//Promesa de cheques
function getDataChequesProm(data) {
    //console.log(data);
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getCheques: true, Atp_Cod: data['Atp_Cod'], Prv_Cod: data['Prv_Cod'] }, (result) => {
            resolve(result.dataCheque);
        }, (err) => {
            reject(err);
        });
    });
}

//Promesa num cheque
function getNumCheque(valor, banCod) {
    return new Promise((resolve, reject) => {
        $.getDataJson('', { verificaCheque: true, Che_Num: valor, Ban_Cod: banCod }, (resultado) => {
            resolve(resultado.numCheque);
        }, (err) => {
            reject(err);
        });
    });
}

//moverse a editar anticipos
function moveToUpdate() {
    $("#documentoSearch").moveComp("#documentoUpdate").updateGridsSizes();
}
//moverse a el principal
function moveToMain() {
    $("#documentoUpdate").moveComp("#documentoSearch").updateGridsSizes();
    $("#searchGrid").trigger("reloadGrid");
}

function limpiarFormAnticipos() {
    console.log('lolga');
    $("#pagos").jqGrid("clearGridData").trigger("reloadGrid");
    $('#anticipoPrvForm').setData({});
}

function openDialogPagos() {
    $('#Pag_Cod').trigger('change');
    $('#pagosDialog').dialog('open');
}

$('#provDialog').createDialog({
    // icon:'search',
    width: 500,
    height: 430,
    autoOpen: false,
    modal: true,
});

$(function () {
    $.createSearchDialog('provDialog', [
        { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15, align: "center", hidden: true },
        { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Proveedor', name: 'nombre', width: 100 },
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        {
            label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton',
            formatoptions: {
                action: selectProveedor
            }
        }
    ], null, null, null, { headertitles: true },
        { title: 'Proveedor', text: 'searchPrv' });
});

/**
 * cargar clientes
 * @param  {object} proveedor row seleccionada del dialogo de proveedores
 * @return {void}
 */
function selectProveedor(proveedor) {
    $("#bandera_prov").val("sel");
    $("#Atp_Obs").val("ANTICIPO A PROVEEDOR - " + proveedor.nombre);
    $('#anticipoPrvForm').setData($.extend(proveedor, { op_opciones: 'c' }), false);
    $('#provDialog').dialog('close');
}

function cambiarCamposPagos(tipoPago, tipoAbr) {
    //console.log(tipoPago, tipoAbr);
    $("#Pap_Ctd").val("");
    $("#Che_Num").val("");
    $("#Pap_Val").val("");
    $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
    $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
    $("#Che_Fec").dateLimits($("#Atp_Fec").val(), $("#Pec_Cod option:selected").attr("data-fin"));
    const getCuentaPlan = async () => {
        arrayCuentasPlan.length = 0;
        arrayCuentasPlan = await planCuentaWithNum(tipoAbr, perCodAct);
    }

    getCuentaPlan().then(() => {
        //console.log(arrayCuentasPlan);
        $("#Ban_Cod option").remove();
        for (let i = 0; i < arrayCuentasPlan.length; i++) {
            if (tipoAbr == "EFE" || tipoAbr == "DEP") {
                $("#Ban_Cod").append("<option value='" + arrayCuentasPlan[i].Ban_Cod + "' data-pla='" + arrayCuentasPlan[i].Pld_Cod + "' data-cdc='" + arrayCuentasPlan[i].Pld_Cdc + "' data-cue='" + arrayCuentasPlan[i].Ban_Cue + "' data-des='" + arrayCuentasPlan[i].Pld_Des + "'>" + arrayCuentasPlan[i].Pld_Des + "</option>");
            } else {
                $("#Ban_Cod").append("<option value='" + arrayCuentasPlan[i].Ban_Cod + "' data-pla='" + arrayCuentasPlan[i].Pld_Cod + "' data-cdc='" + arrayCuentasPlan[i].Pld_Cdc + "' data-cue='" + arrayCuentasPlan[i].Ban_Cue + "' data-des='" + arrayCuentasPlan[i].Pld_Des + "'>" + arrayCuentasPlan[i].Pld_Des + " - " + arrayCuentasPlan[i].Ban_Cue + "</option>");
            }
        }
        $('#pagosForm').children().not(':first,:last').addClass('hidden');
        $('#pagosForm').find('.' + tipoPago).removeClass('hidden');
        $('#pagosForm').find('.' + tipoPago).find('.form-control').prop('required', true);
    });


}

function borrarPago(row) {
    //console.log('borrar', row);
    let editar = true;
    $('#pagos').jqGrid('delRowData', row.index);
    //console.log(arrayModAsiento.length);
    arrayModAsiento.length = 0;
    arrayModAsiento = $('#pagos').getGridBatch();
    reCalculateHaber(arrayModAsiento.length);
    formaterFotter(row.index);
}

function formaterFotter(indice) {
    $("#" + indice + "_Haber").css('text-align', 'right');
    $("#_Haber").attr("readonly", "");
    $("#_Haber").css('text-align', 'right');
    $("#_Debe").css('text-align', 'right');
}

function agregarFila(aux) {
    changeValueInPago();
    var glosaDes = '',
        papCto = '';
    var tipoAbrv = $("#Pag_Cod option:selected").attr("data-abr");
    if (verficaPago()) {
        if (!verifyChequeInGrid()) {
            if (!existeCheq) {
                //console.log(aux);
                //console.log(tipoAbrv);
                if (tipoAbrv === 'CHE') {
                    glosaDes = $("#Pag_Cod option:selected").text() + " NO. " + $("#Che_Num").val();
                    papCto = $("#Ban_Cod option:selected").attr("data-cue");
                } else { glosaDes = 'Ant. prov. ' + $("#Pag_Cod option:selected").text(); }
                var $this = $('#pagos');
                var campoGrid = '_Haber';
                var id = $this.nextIndex();
                $this.jqGrid('addRowData', id, { index: id, grid_tipp: 'pago', Che_Cod: '', Asi_Cod: '', Pap_Cod: '', Pag_Cod: $("#Pag_Cod").val(), Pag_Abr: $("#Pag_Cod option:selected").attr("data-abr"), Pag_Des: $("#Pag_Cod option:selected").text(), Pld_Des: $("#Ban_Cod option:selected").attr("data-des"), Pld_Cdc: $("#Ban_Cod option:selected").attr("data-cdc"), Pap_Ctd: $("#Pap_Ctd").val(), Ban_Cod: $("#Ban_Cod option:selected").attr("value"), Che_Num: $("#Che_Num").val(), Che_Fec: $("#Che_Fec").val(), Pap_Cto: papCto, Pld_Cod: $("#Ban_Cod option:selected").attr("data-pla"), Det_Tip: 'H', Glosa: glosaDes, Debe: '', Haber: parseFloat($("#Pap_Val").val()), Pag_Item: "" }, 'last');
                $this.jqGrid('editRow', id);
                $this.find('#' + id + "_Asi_Glo").val(glosaDes);
                $this.find('#' + id + "_Haber").val(($("#Pap_Val").val() * 1).toFixed(4));
                //$("#Pap_Val").val() 5_Haber
                $this.find('tr#' + id).find('#' + id + campoGrid).on('change', function () {
                    //console.log('LOLA', $(this).val());
                    let tnmGrid = $('#pagos').getGridBatch();
                    reCalculateHaber(tnmGrid.length);
                    formaterFotter(id);
                }).trigger('change');
                let tnmGrid = $this.getGridBatch();
                //console.log(tnmGrid);
                reCalculateHaber(tnmGrid.length);
                formaterFotter(id);
                glosaDes = '';
                papCto = '';
                tipoAbrv = '';
                clearModalPago();
            } else {
                $.alert("Ya existe el registro de pago con el mismo n&uacute;mero de cheque");
            }
        } else {
            clearModalPago();
            $.alert("No puede ingresar dos pagos con el mismo n&uacute;mero de cheque");
        }
    } else {
        $.alert("Complete todos los campos");
        $('#btnGuardar').attr('disabled', '');
    }
}

function clearModalPago() {
    $("#Che_Num").val("");
    $("#Pap_Val").val("");
    $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
    $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
}

function verFCheque() {
    var tipoAbrv = $("#Pag_Cod option:selected").attr("data-abr");
    let banCod = $("#Ban_Cod option:selected").attr("value");
    let numAct = $("#Che_Num").val();
    let arrayVerifiCh = [];
    if (tipoAbrv === 'CHE') {
        const vC = async () => {
            arrayVerifiCh.length = 0;
            arrayVerifiCh = await getCheqNum(numAct, banCod);
        }
        vC().then(() => {
            if (arrayVerifiCh.length > 0) {
                existeCheq = true;
            } else {
                existeCheq = false;
            }
        });
    }
}

function verificarNoCheque(valor) {
    $('#btnGuardar').attr('disabled', 'disabled');
    //console.log(valor);
    let arrayVerifiCh = [];
    let banCod = $("#Ban_Cod option:selected").attr("value");
    //console.log(banCod);

    const cheque = async () => {
        arrayVerifiCh.length = 0;
        arrayVerifiCh = await getCheqNum(valor, banCod);
    }
    if (valor > 0 && banCod > 0) {
        cheque().then(() => {
            //console.log(arrayVerifiCh.length);
            if (arrayVerifiCh.length > 0) {
                //console.log('si');
                $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
                $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
                $("#indicadorChe").addClass("red glyphicon glyphicon-remove");
                $('span.validate').attr('title', 'El <u> CHEQUE No. <strong>' + valor + '</strong></u> ya se encuentra registrado');
                $('#btnGuardar').attr('disabled', '');
                existeCheq = true;
            } else {
                //console.log('no');
                $('span.validate').attr('title', '');
                $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
                $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
                $("#indicadorChe").addClass("green glyphicon glyphicon-ok");
                existeCheq = false;
                if (!verifyChequeInGrid()) { $('#btnGuardar').removeAttr('disabled'); }
            }
        });
    }
}

//Verifica en grid el numero de cheque
function verifyChequeInGrid() {
    let stado = false;
    let numAct = $("#Che_Num").val();
    let getData = $('#pagos').jqGrid("getCol", "Che_Num", false);
    if (numAct !== '') {
        for (let i = 0; i < getData.length; i++) {
            if (numAct === getData[i]) {
                stado = true;
                break;
            }
        }
    }
    return stado;
}

//Guardar Anticipo
function updateData(formulario, accion, dialogo) {
    var data = $('#' + formulario).getData('save');
    let ids = verficaDataInGrid();
    $.createDialogConfirm(`�Est&aacute; seguro que desea guardar los datos del anticipo proveedor?`, null, function () {
        if (ids.length > 1) {
            //console.log('si');
            //data = $('#AnticipoPrvForm').serializeObject();
            data['anticipoGrid'] = $('#pagos').getGridBatch();
            $.arraySpliceFields(data['anticipoGrid'], ['index', 'Pag_Item', 'false']);
            data[accion] = true;
            $.saveDataJson('', data, function (resp) {
                if (resp['success']) {
                    $('#' + formulario)[0].reset();
                    $('#pagos').clearGrid('true');
                    if (resp['isCheque']) {
                        $("#successDialog").dialog({ width: 500, height: 355 });
                        $("#siche").removeAttr("hidden");
                        $("#Che_imp option").remove();
                        for (let i = 0; i < resp['arrayCheques'].length; i++) {
                            $("#Che_imp").append("<option value='" + i + "' data-link='" + resp['arrayCheques'][i].link + "'>" + resp['arrayCheques'][i].che + "</option>");
                        }
                        $("#Che_imp").trigger("onchange");
                    } else {
                        $("#successDialog").dialog({ width: 500, height: 200 });
                        $("#siche").attr("hidden", "");
                    }
                    $('#impCompr').attr('href', resp['link']);
                    $('#successDialog').dialog('open');

                    encerarFotter();
                    moveToMain();
                    $.alert('La transacci&oacute;n se realizo con exito.');
                    return false;
                }
            });
        } else {
            $.alert("Debe agregar al menos un pago");
        }
    });
}

function cambiarChek() {
    $("#impchetd td").each(function () {
        $(this).children("a").attr("href", $(this).children("a").attr("data-ruta") + "" + $("#Che_imp option:selected").attr("data-link"));
    });
}

function encerarFotter() {
    $('#_Debe').val('0.00');
    $('#_Haber').val('0.00');
}

function verficaDataInGrid() {
    var ids = $('#pagos').jqGrid('getDataIDs');
    return ids;
}

function verficaPago() {
    let verifica = false;
    let tipoSelect = $("#Pag_Cod option:selected").text();
    if (tipoSelect === 'Efectivo') {
        if ($("#Pap_Val").val() !== "") {
            verifica = true;
        }
    } else if (tipoSelect === 'Deposito' || tipoSelect === "Transferencia") {
        if ($("#Pap_Val").val() !== "" && $("#Pap_Ctd").val() !== "") {
            verifica = true;
        }
    } else {
        if ($("#Pap_Val").val() !== "" && $("#Che_Num").val() !== "") {
            verifica = true;
        }
    }
    return verifica;
}

function changeCuentaCod() {
    $('#Ban_Cod').on('change', function () {
        clearModalPago();
    }).trigger('change');
}

function changeValueInPago() {
    $('#Pap_Val').on('change', function () {
        if ($(this).val() * 1 > 0) {
            if (verficaPago()) {
                verFCheque();
                $('#btnGuardar').removeAttr('disabled');
            }
        } else {
            $.alert('El Valor ingresado del Cheque debe superior a 0');
            $(this).val('');
        }
    }).trigger('change');
}

//permite el ingreso unicamente de numeros
function soloNumeros(e) {
    // valor = valor.replace(/[^0-9]/g,'');
    var key = window.Event ? e.which : e.keyCode
    return (key >= 48 && key <= 57)
}

function formatMoney(number, places, symbol, thousand, decimal) {
    number = number || 0;
    places = !isNaN(places = Math.abs(places)) ? places : 2;
    symbol = symbol !== undefined ? symbol : "$";
    thousand = thousand || ",";
    decimal = decimal || ".";
    var negative = number < 0 ? "-" : "",
        i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
    return symbol + negative + (j ? i.substr(0, j) + thousand : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
}

//Obtener negociaciones
function llenarNego(param) {
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: $.param({ searchNegAntAjax: true, Atp_Cod: param })
    }).then(response => response.json()).then(responce => {
        if (responce && responce.data) {
            $("#Num_Neg").val(responce.data['Num_Neg']);
            $("#Cod_Neg").val(responce.data['Cod_Neg']);
            $("#Cod_Nd").val(responce.data['Cod_Nd']);
        } else { $("#Num_Neg, #Cod_Neg, #Cod_Nd").val(''); }
    }).catch(() => { $("#Num_Neg, #Cod_Neg, #Cod_Nd").val(''); });
}
function clearNego() {
    document.querySelector("#Num_Neg").value = "";
    document.querySelector("#Cod_Neg").value = "";
}

// Sí se puede usar $.saveDataJson para este caso, así queda más consistente con otros usos AJAX del sistema (si ya está definida esa función en el proyecto).
function saveNego() {

    $.createDialogConfirm('Está seguro que desea realizar esta acción', null, function () {
        var data = {
            saveNego: true,
            Atp_Cod: document.querySelector("#Atp_Cod").value,
            Cod_Neg: document.querySelector("#Cod_Neg").value,
            Cod_Nd: document.querySelector("#Cod_Nd").value,
            Abr_Doc: 'ANTP'
        };
        $.saveDataJson('', data, function (resp) {
            if (resp && resp.success) {
                moveToMain();
                //$.alert('La transacci&oacute;n se realizo con exito.');
            } else {
                $.alert('No se pudo realizar el proceso.');
            }
        }, function () {
            $.alert('Error al guardar la negociación.');
        });
    });
}