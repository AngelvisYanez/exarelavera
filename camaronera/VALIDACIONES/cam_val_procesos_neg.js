var container = $("#container");
var containerAnticipos = $("#containerAnticipos")
var containerVentas = $("#containerVentas");
var containerEgresos = $("#containerEgresos");
var containerCompras = $("#containerCompras");
var containerLiquidacion = $("#containerLiquidacion");
var tablasPrecLiq = $("#tablasPrecLiq");
var tablasVentasBL = $("#tablasVentasBL");
$(function () {
    $.createDateRange('#Fec_Ini', '#Fec_Fin');
    $.createDateRange($('#frm_cli_frec').find('input:text[name=Fec_Ini]'), $('#frm_cli_frec').find('input:text[name=Fec_Fin]'));
    armarGridPrincipal();
    $('#OrderBy').on('change', function () {
        $('input[name=order]').val($(this).val());
        $('#frm_prod_ven').formSubmit();
    });
});
// Seleccionar/deseleccionar todos los checkboxes de las filas
$(document).on('change', '#chk-select-all', function () {
    var checked = $(this).is(':checked');
    $('.chk-select_n').prop('checked', checked);
});
// armar gridPrincipal
function armarGridPrincipal() {
    container.createGrid({
        caption: 'Negociaciones <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value=" ng.Cod_Neg ASC">Nro.Neg. Asc</option> <option value="Fec_Neg DESC">Fecha DESC.</option> <option value="Fec_Neg ASC">Fecha ASC.</option><select>&nbsp;</div>',
        height: 200, autowidth: true, shrinkToFit: false,
        colModel: [
            { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selecNeg } },
            { label: '&nbsp;', name: 'act4', width: 30, align: 'center', viewable: false, formatter: function (cellvalue, options, rowObject) { return "<button  style='border: 1px solid #285e8e; border-radius: 2px;' id='btn_edit_nego' class='icon-btn btn-primary' data-row-id='" + options.rowId + "'><i class='fa fa-edit'></i></button>"; } },
            //  { name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: delFila, icon: 'remove', type: 'danger', title: 'Eliminar Item', data: function (o) { return o; } }, resizable: false },
            {
                name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 30, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.Est_Neg && String(rowObject.Est_Neg).trim() === "C") {
                        return '<button disabled style="border: 1px solid #888; border-radius: 2px; background:#ccc; color:#888; cursor:not-allowed;" title="Anular / Eliminar" class="icon-btn btn-danger"><i class="glyphicon glyphicon-remove"></i></button>';
                    } else {
                        return $.fn.fmatter.gridButton.call(this, cellvalue, options, rowObject);
                    }
                },
                formatoptions: { action: delFila, icon: 'remove', type: 'danger', title: 'Eliminar Item', data: function (o) { return o; } },
                resizable: false
            },

            {
                name: 'act2', index: 'act2', width: 30, align: 'center', title: false, sortable: false,
                formatter: function (cellvalue, options, rowObject) {
                    return '<input type="checkbox" class="chk-select_n" data-row-id="' + options.rowId + '">';
                }, headertitle: false, hidedlg: true
            },
            { label: 'Cod.Neg', name: 'Cod_Neg', width: 30, key: true, align: 'left', viewable: true, hidden: true },
            { label: 'Num.Neg', name: 'Num_Neg', width: 85 },
            { label: 'Fecha', name: 'Fec_Neg', width: 90 },
            { label: 'Cli_Cod', name: 'Cli_Cod', width: 85, hidden: true },
            { label: 'Vnd_Cod', name: 'Vnd_Cod', width: 85, hidden: true },
            { label: 'F.pesca', name: 'Fec_Pesca', width: 85 },
            { label: 'ProdCod', name: 'Prod_Cod', width: 85, hidden: true },
            { label: 'Est', name: 'Est_Neg', width: 85, hidden: true },
            { label: 'Productor', name: 'productor', width: 200, align: 'center' },
            { label: 'Empacadora', name: 'empacadora', width: 200, align: 'center' },
            { label: 'Lib.Compr', name: 'Tot_Libras', formatter: 'currency', width: 80, align: 'center' },
            { label: 'Anticipo', name: 'Val_Ant', formatter: 'currency', width: 80, align: 'center' },
            { label: 'Tipo', name: 'Clasf', width: 85 },
            { label: 'Aguaje', name: 'Cod_Agu', width: 40 },
            { label: 'Tipo', name: 'Tip_Neg', width: 40, hidden: true },
            { label: 'Comisión', name: 'Prec_Comis', formatter: 'currency', width: 80, align: 'left' },
            { label: 'Total', name: 'total_comi', formatter: 'currency', width: 70, align: 'center' }
        ],
        subGrid: false,
        multiselect: false,
        footerrow: true,
        onSelectRow: function (rowid, status, e) {
            var data = container.jqGrid('getRowData', rowid);
            $("#aux_cod_neg").val(data.Cod_Neg);
            $("#aux_num_neg").val(data.Num_Neg);
            $(".frm_liq_cod_neg").val(data.Cod_Neg);
            $(".frm_liq_num_neg").val(data.Num_Neg);
        },
        ondblClickRow: function (rowid, status, e) {
            if (rowid) {
                var data = container.jqGrid('getRowData', rowid);
                Cod_Neg = null;
                if (data && data.Cod_Neg != null && data.Cod_Neg !== "") { Cod_Neg = data.Cod_Neg; }
                if ($("#tablasVentasBL").getGridParam("reccount") !== undefined) {
                    $("#tablasVentasBL").jqGrid('setGridParam', {
                        datatype: 'json',
                        postData: { Cod_Neg: Cod_Neg, ventasBLajax: true },
                        jsonReader: { root: "response", repeatitems: false },
                    }).trigger("reloadGrid");
                }
            }
        },
        loadComplete: function () {
            var grid = $(this);
            var ids = grid.jqGrid('getDataIDs');
            $.each(ids, function (i, rowId) {
                var rowData = grid.jqGrid('getRowData', rowId);
                // Si la negociación tiene estado C, pintar de gris la fila
                if (rowData.Est_Neg && String(rowData.Est_Neg).trim() === "C") {
                    $("#" + rowId, grid[0]).css({ "background": "#a5a5a5" });
                    $("#" + rowId, grid[0]).find(".btn-danger").attr('disabled', true).css({ "background": "#ccc", "color": "#888", "cursor": "not-allowed" });
                }
            });
            $(this).jqGrid('footerData', 'set', {
                productor: "<div style='text-align:center;'>TOTAL: </div>",
                Tot_Libras: $(this).jqGrid('getCol', 'Tot_Libras', true, 'sum'),
                Val_Ant: $(this).jqGrid('getCol', 'Val_Ant', true, 'sum'),
                total_comi: $(this).jqGrid('getCol', 'total_comi', true, 'sum')
            }, true);
        },
        gridComplete: function () {
            if ($("#chk-select-all").length === 0) {
                $("th[id*='act2']").html('<input type="checkbox" id="chk-select-all">');
            }
        }
    }, true, '#containerPager', { refresh: false, view: true })
        .gridButtonsAdd([
            { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { container.jqGrid('exportGridExcel', { nombre: 'Negociaciones', hoja: 'HOJA 1' }); } },
            { caption: 'Exportar PDF', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { imprimir(); } }
        ]);

    function imprimir() {
        let seleccionados = [];
        $('.chk-select_n:checked').each(function () { seleccionados.push($(this).data('row-id')); });
        if (seleccionados.length === 0) {
            $.alert("Debe seleccionar al menos una negociación para imprimir.",null,'warning');
            return;
        }
        let colNames = container.jqGrid("getGridParam", "colNames");
        let colModel = container.jqGrid("getGridParam", "colModel");

        let totalLibras = 0, totalAnt = 0, totalComi = 0;
        let tablaHTML = '<table style="width:100%; border-collapse: collapse; border: 1px solid #000;">';
        tablaHTML += '<thead style="border:1px solid"><tr>';
        colModel.forEach(function (col, i) {
            if (!col.hidden && col.name && col.name.trim() !== '' && !['act1', 'act2', 'act3', 'act4'].includes(col.name)) {
                tablaHTML += '<th style="padding:5px;">' + colNames[i] + '</th>';
            }
        });
        tablaHTML += '</tr></thead><tbody>';
        seleccionados.forEach(function (id) {
            let row = container.jqGrid('getRowData', id);
            tablaHTML += '<tr>';
            colModel.forEach(function (col) {
                if (!col.hidden && col.name && col.name.trim() !== '' && !['act1', 'act2', 'act3', 'act4'].includes(col.name)) {
                    let valor = row[col.name] ?? '';
                    if (col.name === 'Tot_Libras') totalLibras += parseFloat(valor.replace(/[^0-9.-]+/g, "")) || 0;
                    if (col.name === 'Val_Ant') totalAnt += parseFloat(valor.replace(/[^0-9.-]+/g, "")) || 0;
                    if (col.name === 'total_comi') totalComi += parseFloat(valor.replace(/[^0-9.-]+/g, "")) || 0;
                    tablaHTML += '<td style="padding:5px;">' + valor + '</td>';
                }
            });
            tablaHTML += '</tr>';
        });
        tablaHTML += '</tbody>';
        tablaHTML += '<tfoot><tr>';
        colModel.forEach(function (col) {
            if (!col.hidden && col.name && col.name.trim() !== '' && !['act1', 'act2', 'act3', 'act4'].includes(col.name)) {
                if (col.name === 'productor') {
                    tablaHTML += '<td style="padding:5px;  text-align:center;"><b>TOTAL:</b></td>';
                } else if (col.name === 'Tot_Libras') {
                    tablaHTML += `<td style="padding:5px;"><b>${totalLibras.toLocaleString(undefined, { minimumFractionDigits: 2 })}</b></td>`;
                } else if (col.name === 'Val_Ant') {
                    tablaHTML += `<td style="padding:5px;"><b>${totalAnt.toLocaleString(undefined, { minimumFractionDigits: 2 })}</b></td>`;
                } else if (col.name === 'total_comi') {
                    tablaHTML += `<td style="padding:5px;"><strong>${totalComi.toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong></td>`;
                } else { tablaHTML += '<td style="padding:5px;"></td>'; }
            }
        });
        tablaHTML += '</tr></tfoot></table>';
        $('#tablaReporte').html(tablaHTML);
        $('#imprimir').printElement();
    }
}

//ver ventas balanceado y larva
// armar gridPrincipal
function armarGridBL() {
    $("#tableLiq").hide();
    $("#ventasLB").show();
    tablasVentasBL.createGrid({
        caption: 'Ventas balanceado y larva <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="com.Cop_Cod ASC">Nro. Compra</option><option value="total DESC">Total Desc.</option>  <option value="Cop_Fec DESC">Fecha DESC.</option> <option value="Cop_Fec ASC">Fecha ASC.</option><select>&nbsp;</div>',
        height: 200, autowidth: true, gridview: false, shrinkToFit: false,
        colModel: [
            /* { label: 'Cod.Ven', name: 'Vet_Cod', width: 60, hidden: true },
             { label: 'Num.Fac', name: 'Vet_Num', width: 80 },
             { label: 'Docu', name: 'Tic_Des', width: 80 },
             { label: 'Fechas', name: 'Com_Fec', width: 120 },
             { label: 'TipProd', name: 'Tip_Prod', width: 120, hidden: true }, //si es L color rojo la fila si es B color azul
             { label: 'Cliente', name: 'cliente', width: 200 },
             { label: 'Iva', name: 'iva', formatter: 'currency', width: 80, align: 'center' },
             { label: 'Subtotal', name: 'subtotal', formatter: 'currency', width: 80, align: 'center' },
             { label: 'Total', name: 'total', formatter: 'currency', width: 80, align: 'center' }*/
            { label: 'Cod.Ven', name: 'Vet_Cod', width: 60, hidden: true },
            { label: 'Num.Fac', name: 'Vet_Num', width: 80 },
            { label: 'Docu', name: 'Tic_Des', width: 80 },
            { label: 'Fechas', name: 'Caj_Fec', width: 120 },
            { label: 'TipProd', name: 'Tip_Prod', width: 120, hidden: true }, //si es L color rojo la fila si es B color azul
            { label: 'Cliente', name: 'cliente', width: 200 },
            { label: 'Total', name: 'Asi_Val', formatter: 'currency', width: 80, align: 'center' },
            { label: 'Abono', name: 'Abono', formatter: 'currency', width: 80, align: 'center' },
            { label: 'Saldo', name: 'saldo', formatter: 'currency', width: 80, align: 'center' }
        ],
        subGrid: false, multiselect: false, footerrow: true,
        loadComplete: function () {
            var tot_balan = 0, tot_larv = 0, tot_flete = 0, tot_insumos = 0, tot_otros_desc = 0;
            $('#tablasVentasBL tr').each(function () {
                const tipoProducto = $(this).find("td").eq(5).text();
                const total = parseFloat($(this).find("td").eq(7).text().replace(/[^0-9.-]+/g, "")) || 0;
                if (tipoProducto === "L") {
                    $(this).css("background", "#69aa46").addClass("myAltRowClass");
                    tot_larv = tot_larv + total;
                } else if (tipoProducto === "B") {
                    $(this).css("background", "#82dcff").addClass("myAltRowClass");
                    tot_balan = tot_balan + total;
                } else if (tipoProducto === "F") {
                    $(this).css("background", "#ff928bff").addClass("myAltRowClass");
                    tot_flete = tot_flete + total;
                } else if (tipoProducto === "I") {
                    $(this).css("background", "#dfcb5dff").addClass("myAltRowClass");
                    tot_insumos = tot_insumos + total;
                } else if (tipoProducto === "D") {
                    $(this).css("background", "#4ea1d8ff").addClass("myAltRowClass");
                    tot_otros_desc = tot_otros_desc + total;
                }
            });

            $("#total_vntas_bal").val(tot_balan.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $("#total_vntas_larva").val(tot_larv.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $("#total_flete_falso").val(tot_flete.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $("#total_vnta_insumos").val(tot_insumos.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $("#total_otros_desc").val(tot_otros_desc.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            $(this).jqGrid('footerData', 'set', {
                cliente: "<div style='text-align:center;'>TOTAL: </div>",
                Asi_Val: $(this).jqGrid('getCol', 'Asi_Val', true, 'sum'),
                Abono: $(this).jqGrid('getCol', 'Abono', true, 'sum'),
                saldo: $(this).jqGrid('getCol', 'saldo', true, 'sum')
            }, true);
        },
    }, true,);
}
function armarGridLiqui() {
    $("#ventasLB").hide();
    $("#tableLiq").show();
}
function eliminarFila(rowid) {
    $("#tablasPrecLiq").jqGrid('delRowData', rowid);
}

$(function () {
    //CARGAR PARA CARGAR LOS PRECIOS A LA LIQUIDACION
    tablasPrecLiq.createGrid({
        autowidth: true, shrinkToFit: false, height: 170,
        colModel: [
            { label: 'Cod', name: 'Cod_Prec', width: 40 },
            { label: 'clasf', name: 'Tip_Cla', width: 60, hidden: true },
            { label: 'Tipo', name: 'Tip', width: 90 },
            { label: 'Precio', name: 'Prec', width: 70 },
            { label: 'Talla', name: 'Talla', width: 70 },
            { label: 'Cantidad', name: 'Cant', width: 75, align: 'center' },
            { label: 'Medida', name: 'Med', width: 70, align: 'center' },
            { label: 'Total', name: 'total', formatter: 'currency', width: 70, align: 'center' },
            {
                label: 'Acción', name: '', width: 60, align: 'center', formatter: function (cellvalue, options, rowObject) {
                    return '<button style="border: 1px solid #c63632; border-radius: 2px;" onclick="eliminarFila(\'' + options.rowId + '\')" class="delete-btn btn-danger p-0"> <i class="fa fa-trash"></i> </button>';
                }
            }
        ],
        datatype: "local", footerrow: true,
        loadComplete: function () {
            $(this).jqGrid('footerData', 'set', {
                Med: "<div style='text-align:center;'>TOTAL: </div>",
                total: $(this).jqGrid('getCol', 'total', true, 'sum')
            }, true);
        }
    });
    //COMPRAS
    containerCompras.createGrid({
        autowidth: true,
        caption: 'Detalles de Compras',
        height: 100,
        colModel: [
            { label: 'Cod.Cop', name: 'Cop_Cod', width: 60, hidden: false },
            { label: 'fecha', name: 'Cop_Fec', width: 80 },
            { label: 'Num.Fac', name: 'Cop_Num', width: 120 },

            { label: 'Proveedor', name: 'proveedor', width: 200 },
            { label: 'Total', name: 'Asi_Val', formatter: 'currency', width: 80, align: 'center' },
            { label: 'Abono', name: 'Abono', formatter: 'currency', width: 80, align: 'center' },
            { label: 'Saldo', name: 'saldo', formatter: 'currency', width: 80, align: 'center' }
        ],
        datatype: "local",
        footerrow: true,
        ondblClickRow: function (rowid, status, e) {
            if (rowid) {
                var data = containerCompras.jqGrid('getRowData', rowid);
                load_pag_compras(data);
            }
        },
        loadComplete: function () {
            $(this).jqGrid('footerData', 'set', {
                proveedor: "<div style='text-align:center;'>TOTAL: </div>",
                Iva: $(this).jqGrid('getCol', 'Iva', true, 'sum'),
                subtotal: $(this).jqGrid('getCol', 'subtotal', true, 'sum'),
                Asi_Val: $(this).jqGrid('getCol', 'Asi_Val', true, 'sum'),
                Abono: $(this).jqGrid('getCol', 'Abono', true, 'sum'),
                saldo: $(this).jqGrid('getCol', 'saldo', true, 'sum')
            }, true);
        }
    });

    containerEgresos.createGrid({
        autowidth: true, shrinkToFit: false,
        caption: 'Egresos',
        height: 100,
        colModel: [
            { label: 'Cod.Doc', name: 'Cop_Cod', width: 60, hidden: false },
            { label: 'Fecha', name: 'Pag_Fec', width: 80 },
            { label: 'Proveedor', name: 'proveedor', width: 200 },
            { label: 'Obs.', name: 'Pag_Obs', width: 200 },
            { label: 'Tip.Pago', name: 'Pag_Des', width: 80, align: 'center' },
            { label: 'Num.Doc', name: 'Cop_Num', width: 90 },
            { label: 'Total', name: 'Pag_Val', formatter: 'currency', width: 90, align: 'center' },
            { label: 'Abono', name: 'Abono', formatter: 'currency', width: 90 },
            { label: 'Saldo', name: 'Saldo', formatter: 'currency', width: 90 },
        ],
        jsonReader: {
            root: "response", repeatitems: false
        },
        datatype: "local",
        footerrow: true,
        loadComplete: function () {
            $(this).jqGrid('footerData', 'set', {
                Pag_Val: $(this).jqGrid('getCol', 'Pag_Val', true, 'sum'),
                Abono: $(this).jqGrid('getCol', 'Abono', true, 'sum'),
                Saldo: $(this).jqGrid('getCol', 'Saldo', true, 'sum')
            }, true);
        }
    });
    //Ventas
    containerVentas.createGrid({
        autowidth: true, caption: 'Ventas', height: 100,
        colModel: [
            { label: 'Cod.Ven', name: 'Vet_Cod', width: 60, hidden: true },
            { label: 'Num.Fac', name: 'Vet_Num', width: 80 },
            { label: 'Fechas', name: 'Caj_Fec', width: 100 },
            { label: 'Cliente', name: 'cliente', width: 200 },
            { label: 'Total', name: 'Asi_Val', formatter: 'currency', width: 80, align: 'center' },
            { label: 'Abono', name: 'Abono', formatter: 'currency', width: 80, align: 'center' },
            { label: 'Saldo', name: 'saldo', formatter: 'currency', width: 80, align: 'center' }],
        jsonReader: { root: "response", repeatitems: false },
        datatype: "local", footerrow: true,
        ondblClickRow: function (rowid, status, e) {
            if (rowid) {
                var data = containerVentas.jqGrid('getRowData', rowid);
                load_pag_ventas(data);
            }
        },
        loadComplete: function () {
            $(this).jqGrid('footerData', 'set', {
                Pld_Des: "<div style='text-align:center;'>TOTAL: </div>",
                Asi_Val: $(this).jqGrid('getCol', 'Asi_Val', true, 'sum'),
                Abono: $(this).jqGrid('getCol', 'Abono', true, 'sum'),
                saldo: $(this).jqGrid('getCol', 'saldo', true, 'sum')
            }, true);
        }
    });

    containerAnticipos.createGrid({
        autowidth: true, caption: 'Ingresos', height: 100,
        colModel: [
            { label: 'Cod.Doc', name: 'Vet_Cod', width: 60, hidden: false },
            { label: 'Fecha', name: 'Cpc_Fec', width: 100 },
            { label: 'Cliente', name: 'cliente', width: 200 },
            { label: 'Obs.', name: 'Cpc_Obs', width: 200, align: 'center' },
            { label: 'Tip.Pago', name: 'Pag_Des', width: 70, align: 'center' },
            { label: 'Num.Doc', name: 'Vet_Num', width: 100, align: 'center' },
            { label: 'Total', name: 'Cpc_Val', formatter: 'currency', width: 80, align: 'center' }],
        jsonReader: { root: "response", repeatitems: false },
        datatype: "local", footerrow: true,
        loadComplete: function () {
            $(this).jqGrid('footerData', 'set', { Cpc_Val: $(this).jqGrid('getCol', 'Cpc_Val', true, 'sum') }, true);
        }
    });

    //Liquidacion
    containerLiquidacion.createGrid({
        autowidth: true, shrinkToFit: false, height: 250,
        colModel: [
                {
                name: 'delete',
                label: '<i class="glyphicon glyphicon-remove"></i>',
                width: 30,
                align: 'center',
                viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    // Bloquear botón eliminar si Est_Neg == 'C'
                    if (rowObject.Est_Neg && String(rowObject.Est_Neg).trim() === "C") {
                        return '<button disabled style="border: 1px solid #888; border-radius: 2px; background:#ccc; color:#888; cursor:not-allowed;" title="Eliminar Item" class="icon-btn btn-danger"><i class="glyphicon glyphicon-remove"></i></button>';
                    } else {
                        // Usar la función gridButton como antes (mismo diseño)
                        return $.fn.fmatter.gridButton.call(this, cellvalue, options, rowObject);
                    }
                },
                formatoptions: { action: delFilaLiq, icon: 'remove', type: 'danger', title: 'Eliminar Item', data: function (o) { return o; } },
                resizable: false
            },
            {
                label: '&nbsp;',
                name: 'act2',
                width: 30,
                align: 'center',
                viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    // Bloquear botón editar si Est_Neg == 'C'
                    if (rowObject.Est_Neg && String(rowObject.Est_Neg).trim() === "C") {
                        return "<button disabled style='border: 1px solid #888; border-radius: 2px; background:#ccc; color:#888; cursor:not-allowed;' id='btn_edit_liqui' class='icon-btn btn-primary' data-row-id='" + options.rowId + "'><i class='fa fa-edit'></i></button>";
                    } else {
                        return "<button style='border: 1px solid #285e8e; border-radius: 2px;' id='btn_edit_liqui' class='icon-btn btn-primary' data-row-id='" + options.rowId + "'><i class='fa fa-edit'></i></button>";
                    }
                }
            },
            {
                label: '&nbsp;',
                name: 'act1',
                width: 30,
                align: 'center',
                viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    // Bloquear botón select si Est_Neg == 'C'
                    if (rowObject.Est_Neg && String(rowObject.Est_Neg).trim() === "C") {
                        // Esto mantiene el diseño que da gridButton, pero desactiva el botón.
                        return '<button disabled style="border: 1px solid #888; border-radius: 2px; background:#ccc; color:#888; cursor:not-allowed;" class="icon-btn btn-primary"><i class="glyphicon glyphicon-ok"></i></button>';
                    } else {
                        return $.fn.fmatter.gridButton.call(this, cellvalue, options, rowObject);
                    }
                },
                formatoptions: { action: selectLiq }
            },
            
            
            
            
            { label: 'Cod.Agu', name: 'Cod_Agu', width: 40, hidden: true },
            { label: 'Piscina', name: 'Val_Pisc', width: 40, hidden: true },
            { label: 'comision', name: 'Val_Comision', width: 40, hidden: true },
            { label: 'Produc.', name: 'productor', width: 40, hidden: true },
            { label: 'PesNeto.', name: 'Peso_Net', width: 40, hidden: true },
            { label: 'Lote.', name: 'Val_Lote', width: 40, hidden: true },
            { label: 'Guia.', name: 'Val_Guia', width: 40, hidden: true },
            { label: 'PesoProm.', name: 'Peso_Prom', width: 40, hidden: true },
            { label: 'Num_Neg.', name: 'Num_Neg', width: 40, hidden: true },
            { label: 'Gast_Control', name: 'Gast_Control', width: 40, hidden: true },
            { label: 'Empa_Cod', name: 'Empa_Cod', width: 40, hidden: true },
            { label: 'Prod_Cod', name: 'Prod_Cod', width: 40, hidden: true },
            { label: 'Num_Liq', name: 'Num_Liq', width: 40, hidden: true },
            { label: 'Lib_Falt', name: 'Lib_Falt', width: 40, hidden: true },
            { label: 'Otr_Gastos', name: 'Otr_Gastos', width: 40, hidden: true },
            { label: 'Val_Gram_Glo', name: 'Val_Gram_Glo', width: 40, hidden: true },
            { label: 'Nom_Agu', name: 'Nom_Agu', width: 40, hidden: true },
            {
                name: 'seleccionar', title: false, sortable: false, index: 'seleccionar', width: 30, align: 'center', sortable: false,
                formatter: function (cellvalue, options, rowObject) { return '<input type="checkbox" class="chk-select" data-row-id="' + options.rowId + '">'; }
            },
            { label: 'Cod.Liq', name: 'Liq_Cod', width: 40, hidden: true },
            { label: 'Fecha.Ing', name: 'Liq_Fecha', width: 80 },
            { label: 'Peso', name: 'Peso_Rem', width: 80 },
            { label: 'CodNeg', name: 'Cod_Neg', width: 80, hidden: true },
            { label: 'Peso Planta', name: 'Peso_Planta', formatter: 'currency', width: 80, align: 'center' },
            {
                label: 'Diferencia', name: 'diferencia', formatter: 'currency', width: 80, align: 'center', cellattr: function (rowId, cellValue, rawObject, cm, rdata) {
                    return 'style="background:#ffbb7f;"';  // Cambia "red" por otro color
                }
            },
            { label: 'Basura', name: 'Basur', formatter: 'currency', width: 80, align: 'center' },
            {
                label: 'Procesadas', name: 'Lib_Proces', formatter: 'currency', width: 80, align: 'center', hidden: true, cellattr: function (rowId, cellValue, rawObject, cm, rdata) {
                    return 'style="background:#b3ff7f;"';  // Cambia "red" por otro color
                }
            },
            { label: 'Lib.Procesadas', name: 'Lib_Proces', formatter: 'currency', width: 80, align: 'center' },
            {
                label: 'Rendimiento', name: 'Val_Rendi', width: 80, align: 'center', formatoptions: { decimalPlaces: 2 }, cellattr: function (rowId, cellValue, rawObject, cm, rdata) {
                    return 'style="background:#fff37f;"';  // Cambia "red" por otro color
                }
            }],
        jsonReader: { root: "response", repeatitems: false },
        datatype: "local",
        footerrow: true,
        gridComplete: function () {
            if ($("#chk-select-all_l").length === 0) {
                $("th[id*='seleccionar']").html('<input type="checkbox" id="chk-select-all_l">');
            }
        },
        loadComplete: function () {
            $(this).jqGrid('footerData', 'set', {
                Liq_Fecha: "<div style='text-align:center;'>TOTAL: </div>",
                Peso_Rem: $(this).jqGrid('getCol', 'Peso_Rem', true, 'sum').toFixed(2),
                Peso_Planta: $(this).jqGrid('getCol', 'Peso_Planta', true, 'sum').toFixed(2),
                diferencia: $(this).jqGrid('getCol', 'diferencia', true, 'sum').toFixed(2),
                Basur: $(this).jqGrid('getCol', 'Basur', true, 'sum').toFixed(2),
                Lib_Proces: $(this).jqGrid('getCol', 'Lib_Proces', true, 'sum').toFixed(2),
                Val_Rendi: $(this).jqGrid('getCol', 'Val_Rendi', true, 'sum').toFixed(2)
            }, true);
        },
    });
});

$(document).on('change', '#chk-select-all_l', function () {
    var checked = $(this).is(':checked');
    $('.chk-select').prop('checked', checked);
});

function selecNeg(auto) {
    //Cargar las liquidaciones
    load_liquidacion(auto);
    //Cargar los documentos
    load_documentos_negociacion(auto);
    load_doc_negociacion(auto);
    load_pag_compras(auto);
    load_pag_ventas(auto);
}

function selectLiq(auto) {
    $("#editLiqDialog").dialog({ autoOpen: false, modal: true, width: 650, maxWidth: 600, height: 550, position: { my: "center", at: "center", of: window }, show: { effect: "fade", duration: 300 }, hide: { effect: "fade", duration: 300 } });
    $('#editLiqDialog').dialog('open');
    $("#frm_det_liq #Cod_Agu").val(auto.Cod_Agu);
    $("#frm_det_liq #Num_Liq").val(auto.Num_Liq);
    $("#frm_det_liq #Liq_Cod").val(auto.Liq_Cod);
    $("#frm_det_liq #Num_Neg").val(auto.Num_Neg);
    $.post('', { loadDetLiquijax: true, Liq_Cod: auto.Liq_Cod }, function (response) {
        if (response && response.response) {
            $("#tablasPrecLiq").jqGrid('clearGridData').jqGrid('setGridParam', { datatype: 'local', data: response.response }).trigger("reloadGrid");
        } else { console.warn("Los datos de liquidación están vacíos o incorrectos."); }
    }, 'json').fail(function () { console.error("Error en la petición AJAX."); });
}


function load_liquidacion(data) {
    Cod_Neg = null;
    if (data && data.Cod_Neg != null && data.Cod_Neg !== "") { Cod_Neg = data.Cod_Neg; }
    if ($("#containerLiquidacion").getGridParam("reccount") !== undefined) {
        $("#containerLiquidacion").jqGrid('setGridParam', {
            datatype: 'json',
            postData: { Cod_Neg: Cod_Neg, loadLiquiAjax: true },
            jsonReader: { root: "response", repeatitems: false },
        }).trigger("reloadGrid");
    }
}

function load_documentos_negociacion(data) {
    Cod_Neg = null;
    if (data && data.Cod_Neg != null && data.Cod_Neg !== "") { Cod_Neg = data.Cod_Neg; }
    if ($("#containerCompras").getGridParam("reccount") !== undefined) {
        $("#containerCompras").jqGrid('setGridParam', {
            datatype: 'json',
            postData: { Cod_Neg: Cod_Neg, comprasAjax: true },
            jsonReader: { root: "response", repeatitems: false },
        }).trigger("reloadGrid");
    }
}
function load_doc_negociacion(data) {
    Cod_Neg = null;
    if (data && data.Cod_Neg != null && data.Cod_Neg !== "") { Cod_Neg = data.Cod_Neg; }
    if ($("#containerVentas").getGridParam("reccount") !== undefined) {
        $("#containerVentas").jqGrid('setGridParam', {
            datatype: 'json',
            postData: { Cod_Neg: Cod_Neg, ventasAjax: true },
            jsonReader: { root: "response", repeatitems: false },
        }).trigger("reloadGrid");
    }
}
//PAGO COMPRAS
function load_pag_compras(data) {
    Cod_Neg = null; Cop_Cod = null;
    if (data && data.Cod_Neg != null && data.Cod_Neg !== "") {
        Cod_Neg = data.Cod_Neg;
    } else {
        Cod_Neg = $("#aux_cod_neg").val();
        Cop_Cod = data.Cop_Cod;
    }
    if ($("#containerEgresos").getGridParam("reccount") !== undefined) {
        $("#containerEgresos").jqGrid('setGridParam', {
            datatype: 'json',
            postData: { Cod_Neg: Cod_Neg, pagoComprasAjax: true, Cop_Cod: Cop_Cod },
            jsonReader: { root: "response", repeatitems: false },
        }).trigger("reloadGrid");
    }
}
//COBROS DE VENTAS
function load_pag_ventas(data) {
    Cod_Neg = null, Vet_Cod = null;
    if (data && data.Cod_Neg != null && data.Cod_Neg !== "") {
        Cod_Neg = data.Cod_Neg;
    } else {
        Cod_Neg = $("#aux_cod_neg").val();
        Vet_Cod = data.Vet_Cod
    }
    if ($("#containerAnticipos").getGridParam("reccount") !== undefined) {
        $("#containerAnticipos").jqGrid('setGridParam', {
            datatype: 'json',
            postData: { Cod_Neg: Cod_Neg, pagoVentasAjax: true, Vet_Cod: Vet_Cod },
            jsonReader: { root: "response", repeatitems: false },
        }).trigger("reloadGrid");
    }
}
//Modal de liquidación
function liquidacionDialog() {
    var frm_liq_cod_neg = $(".frm_liq_cod_neg").val();
    if (frm_liq_cod_neg == null || frm_liq_cod_neg <= 0) {
        $.alert("Por favor, Seleccione una negociación");
        return false
    };
    $("#liquidacionDialog").dialog({ autoOpen: false, modal: true, width: 800, maxWidth: 900, height: 550, position: { my: "center", at: "center", of: window }, show: { effect: "fade", duration: 300 }, hide: { effect: "fade", duration: 300 } });
    $("#form_bandera").val("LIQ");
    $.post('', { 'aguajesAjax': true }, function (response) {
        let select = $('#frm_liquidacion #Cod_Agu'); // Reemplaza con el ID de tu <select>
        select.empty();
        let data = response.response || response;
        select.append($('<option>', { value: '', text: '-- Seleccione --' })); // Opción vacía
        if (Array.isArray(data)) {
            data.forEach(item => {
                let option = $('<option>', { value: item.Agu_Cod, text: item.Nom_Agu + ' - ' + item.Desc_Agu + ' - ' + item.Num_Agu });
                select.append(option);
            });
        } else { console.error("El servidor no devolvió un array:", response); }
    }, 'json').fail(function () { $.alert(); });
    $('#liquidacionDialog').dialog('open');
}

//NEGOCIACION
if ($('#prodDialog').length > 0)
    $('#prodDialog').createSearchDialog({
        colModel: [
            { label: 'C&oacute;d.Int.', name: 'Prod_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Productor', name: 'Proveedor', width: 100 },
            { label: 'Cont.', name: 'Prv_Con', width: 20, align: "center", labelLong: 'Obligado a Llevar Contabilidad', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: 'Espe.', name: 'Prv_Esp', width: 20, align: "center", labelLong: 'Contribuyente Especial', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectProd } }
        ]
    }, { title: 'Productor' });

function validarNum_Neg(generado = false) {
    $.post('', { 'secNegAjax': true }, function (Num_Neg) {
        $('#frm_negociacion').setData({ 'Num_Neg': Num_Neg }, false);
    }, 'json').fail(function () { $.alert(); });
}

function negociacionDialog() {
    $("#form_bandera").val("NEG");
    validarNum_Neg(true);
    $("#negociacionDialog").dialog({ autoOpen: false, modal: true, width: 900, maxWidth: 1000, height: 500, position: { my: "center", at: "center", of: window }, show: { effect: "fade", duration: 300 }, hide: { effect: "fade", duration: 300 } });
    $('#negociacionDialog').dialog('open');
}
//Modal venta
function ventaDialog() {
    $("#ventaDialog").dialog({ autoOpen: false, modal: true, width: 900, maxWidth: 1000, height: 500, position: { my: "center", at: "center", of: window }, show: { effect: "fade", duration: 300 }, hide: { effect: "fade", duration: 300 } });
    $('#ventaDialog').dialog('open');
}
function validaDocument() {
    $.saveDataJson('', $('#frm_negociacion').getData('saveNegociacion'), function (resp) {
        $('#frm_negociacion')[0].reset();
        $('#container').Search('#frm_prod_ven', 'negociacionesAjax');
        $('#negociacionDialog').dialog('close');
    });
}
//MODAL AGUAJES
function aguajesDialog() {
    $("#aguajesDialog").dialog({ autoOpen: false, modal: true, width: 1100, maxWidth: 2000, height: 600, position: { my: "center", at: "center", of: window }, show: { effect: "fade", duration: 300 }, hide: { effect: "fade", duration: 300 } });
    load_documentos_negociacion();
    load_Aguajes();
    load_Tip_TallEntero();
    load_Tip_TallB();
    load_Tip_TallA();
    load_tallTipNac();
    $('#aguajesDialog').dialog('open');
}

//Modal Add Aguajes
function agregarAguajesAddDialog(opc) {
    $('#frm_aguaje')[0].reset();
    if (opc == 'add') validarNum_Agu(true);
    loadEmpacadoraSelect();
    $("#agregarAguajesAddDialog").dialog({ autoOpen: false, modal: true, width: 400, maxWidth: 350, height: 350, position: { my: "center", at: "center", of: window }, show: { effect: "fade", duration: 300 }, hide: { effect: "fade", duration: 300 } });
    $('#agregarAguajesAddDialog').dialog('open');
}

function validarNum_Agu(generado = false) {
    $.post('', { 'secAguajeAjax': true }, function (Num_Agu) {
        $('#frm_aguaje').setData({ 'Num_Agu': Num_Agu }, false);
    }, 'json').fail(function () { $.alert(); });
}

function validaDocumentAguaje() {
    $.saveDataJson('', $('#frm_aguaje').getData('saveAguaje'), function (resp) {
        load_Aguajes();
        $('#agregarAguajesAddDialog').dialog('close');
    });
}

$.fn.fmatter.gridEditButton = function (cellvalue, options, rowObject) {
    return "<button style='border: 1px solid #285e8e; border-radius: 2px;' class='icon-btn btn-primary'><i class='fa fa-edit'></i></button>";
};
var containerAguajes = $("#containerAguajes");
containerAguajes.createGrid({
    rowNum: 1000,
    height: 200, autowidth: true,
    colModel: [
        { label: 'Cod.Cop', name: 'Agu_Cod', width: 80 },
        { label: 'Num.Agu', name: 'Num_Agu', width: 100 },
        { label: 'Aguaje', name: 'Nom_Agu', width: 180 },
        { label: 'Empacadora', name: 'Empacadora', width: 250, align: 'center' },
        { label: 'Nota', name: 'Desc_Agu', width: 300, align: 'center' },
        { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selecAguaje } },
        { label: '&nbsp;', name: 'act4', width: 30, align: 'center', viewable: false, formatter: function (cellvalue, options, rowObject) { return "<button type='button' style='border: 1px solid #285e8e; border-radius: 2px;' class='icon-btn btn-primary' onclick='selecEditAguaje(" + JSON.stringify(rowObject) + ")'><i class='fa fa-edit'></i></button>"; } },
        { label: '&nbsp;', name: 'act3', width: 30, align: 'center', viewable: false, formatter: function (cellvalue, options, rowObject) { return "<button style='border: 1px solid #c63632; border-radius: 2px;' class='delete-btn btn-danger p-0' data-row-id='" + options.rowId + "'><i class='fa fa-trash'></i></button>"; } },
    ],
    jsonReader: { root: "response", repeatitems: false },
    datatype: "local",
    footerrow: false,
});

function selecEditAguaje(data) {
    agregarAguajesAddDialog();
    $("#frm_aguaje #Agu_Cod").val(data.Agu_Cod);
    $("#frm_aguaje #Num_Agu").val(data.Num_Agu);
    $("#frm_aguaje #Nom_Agu").val(data.Nom_Agu);
    $("#frm_aguaje #Desc_Agu").val(data.Desc_Agu);
     setTimeout(function () {
        $("#frm_aguaje #Prod_Cod").val(data.Prod_Cod).trigger('change');
    }, 100);
    /*$(document).ready(function () {
        const selectElement = document.querySelector('#frm_aguaje #Prod_Cod');
        if (selectElement) { selectElement.value = data.Prod_Cod; }
    });*/
}

function selecAguaje(rowData) {
    $("#frm_precios #Agu_Cod").val(rowData.Agu_Cod);
    $("#frm_precios #Nom_Agu").val(rowData.Nom_Agu);
    $.post('', { 'loadTallAguajesAjax': true, 'Cod_Agu': rowData.Agu_Cod }, function (response) {
        let data = response.response || response;
        console.log("data", data);
        let dataMap = {};
        if (Array.isArray(data)) { data.forEach(item => { dataMap[item.Cod_Tall] = item; }); }
        let procesados = new Set();
        $("[id^='precioA_'], [id^='precioB_'], [id^='precioNA_'], [id^='precioNB_']").each(function () {
            let idParts = this.id.split("_");
            let rowId = idParts[1];
            if (procesados.has(rowId)) return;
            procesados.add(rowId);
            let inputA = $("#precioA_" + rowId);
            let inputB = $("#precioB_" + rowId);
            let inputNA = $("#precioNA_" + rowId);
            let inputNB = $("#precioNB_" + rowId);
            let item = dataMap[rowId];
            let precA = parseFloat(item?.Prec_A || 0).toFixed(5);
            let precB = parseFloat(item?.Prec_B || 0).toFixed(5);
            if (inputA.length) inputA.val(precA);
            if (inputNA.length) inputNA.val(precA);
            if (inputB.length) inputB.val(precB);
            if (inputNB.length) inputNB.val(precB);
        });
    }, 'json').fail(function () {
        $.alert({ title: 'Error', content: 'No se pudo obtener los datos.' });
    });
}

function load_Aguajes(search) {
    if ($("#containerAguajes").getGridParam("reccount") !== undefined) {
        $("#containerAguajes").jqGrid('setGridParam', {
            datatype: 'json',
            postData: { aguajesAjax: true, search: search },
            jsonReader: { root: "response", repeatitems: false },
        }).trigger("reloadGrid");
    }
}
//TALLAS DE CAMARON
var tallEntero = $("#tallEntero");
tallEntero.createGrid({
    width: "auto", height: 100, rownumbers: false, multiselect: false, shrinkToFit: false, responsive: false,
    colModel: [
        { label: 'Cod', name: 'Cod_Tall', width: 5, hidden: true, key: true },
        { label: 'Talla', name: 'Talla', width: 50 },
        { label: 'Precio A', name: 'Precio_A', width: 60, formatter: function (cellvalue, options, rowObject) { return '<input type="text" class="form-control input-xs precioA" id="precioA_' + options.rowId + '" ' + 'value="' + (cellvalue ? cellvalue : '0.00000') + '" />'; } },
        { label: 'Precio B', name: 'Precio_B', width: 60, align: 'center', formatter: function (cellvalue, options, rowObject) { return '<input type="text" class="form-control input-xs precioB" id="precioB_' + options.rowId + '" ' + 'value="' + (cellvalue ? cellvalue : '0.00000') + '" />'; } },
        { label: 'Medidas', name: 'Tip_Med', width: 60, align: 'center' }],
    jsonReader: { root: "response", repeatitems: false },
    datatype: "local",
    footerrow: false
});

var tallTipA = $("#tallTipA");
tallTipA.createGrid({
    width: "auto", height: 100, rownumbers: false, multiselect: false, shrinkToFit: false, responsive: false,
    colModel: [
        { label: 'Cod', name: 'Cod_Tall', width: 5, hidden: true, key: true },
        { label: 'Talla', name: 'Talla', width: 50 },
        { label: 'Precio A', name: 'Precio_CA', width: 60, formatter: function (cellvalue, options, rowObject) { return '<input type="text" class="form-control input-xs precioA" id="precioA_' + options.rowId + '" ' + 'value="' + (cellvalue ? cellvalue : '0.00000') + '" />'; } },
        { label: 'Precio B', name: 'Precio_CB', width: 60, align: 'center', formatter: function (cellvalue, options, rowObject) { return '<input type="text" class="form-control input-xs precioB" id="precioB_' + options.rowId + '" ' + 'value="' + (cellvalue ? cellvalue : '0.00000') + '" />'; } },
        { label: 'Medidas', name: 'Tip_Med', width: 60, align: 'center' }],
    jsonReader: { root: "response", repeatitems: false },
    datatype: "local",
    footerrow: false
});

var tallTipB = $("#tallTipB");
tallTipB.createGrid({
    width: "auto", height: 100, rownumbers: false, multiselect: false, shrinkToFit: false, responsive: false,
    colModel: [
        { label: 'Cod', name: 'Cod_Tall', width: 5, hidden: true, key: true },
        { label: 'Talla', name: 'Talla', width: 50 },
        { label: 'Precio A', name: 'Precio_A', width: 60, formatter: function (cellvalue, options, rowObject) { return '<input type="text" class="form-control input-xs precioA" id="precioA_' + options.rowId + '" ' + 'value="' + (cellvalue ? cellvalue : '0.00000') + '" />'; } },
        { label: 'Precio B', name: 'Precio_B', width: 60, align: 'center', formatter: function (cellvalue, options, rowObject) { return '<input type="text" class="form-control input-xs precioB" id="precioB_' + options.rowId + '" ' + 'value="' + (cellvalue ? cellvalue : '0.00000') + '" />'; } },
        { label: 'Medidas', name: 'Tip_Med', width: 60, align: 'center' }],
    jsonReader: { root: "response", repeatitems: false },
    datatype: "local",
    footerrow: false
});

var tallTipNac = $("#tallTipNac");
tallTipNac.createGrid({
    width: "auto", height: 100, rownumbers: false, multiselect: false, shrinkToFit: false, responsive: false,
    colModel: [
        { label: 'Cod', name: 'Cod_Tall', width: 5, hidden: true, key: true },
        { label: 'Talla', name: 'Talla', width: 50 },
        { label: 'Precio A', name: 'Precio_NA', width: 60, formatter: function (cellvalue, options, rowObject) { return '<input type="text" class="form-control input-xs Precio_NA" id="precioNA_' + options.rowId + '" ' + 'value="' + (cellvalue ? cellvalue : '0.00000') + '" />'; } },
        { label: 'Precio B', name: 'Precio_NB', width: 60, align: 'center', formatter: function (cellvalue, options, rowObject) { return '<input type="text" class="form-control input-xs Precio_NB" id="precioNB_' + options.rowId + '" ' + 'value="' + (cellvalue ? cellvalue : '0.00000') + '" />'; } },
        { label: 'Medidas', name: 'Tip_Med', width: 60, align: 'center' }],
    jsonReader: { root: "response", repeatitems: false },
    datatype: "local",
    footerrow: false
});

function load_Tip_TallEntero() {
    if ($("#tallEntero").getGridParam("reccount") !== undefined) {
        $("#tallEntero").jqGrid('setGridParam', {
            datatype: 'json',
            postData: { tallaEnteroAjax: true },
            jsonReader: { root: "response", repeatitems: false },
        }).trigger("reloadGrid");
    }
}
function load_Tip_TallA() {
    if ($("#tallTipA").getGridParam("reccount") !== undefined) {
        $("#tallTipA").jqGrid('setGridParam', {
            datatype: 'json',
            postData: { tallaColaAAjax: true },
            jsonReader: { root: "response", repeatitems: false },
        }).trigger("reloadGrid");
    }
}

function load_Tip_TallB() {
    if ($("#tallTipB").getGridParam("reccount") !== undefined) {
        $("#tallTipB").jqGrid('setGridParam', {
            datatype: 'json',
            postData: { tallaColaBajax: true },
            jsonReader: { root: "response", repeatitems: false },
        }).trigger("reloadGrid");
    }
}

function load_tallTipNac() {
    if ($("#tallTipNac").getGridParam("reccount") !== undefined) {
        $("#tallTipNac").jqGrid('setGridParam', {
            datatype: 'json',
            postData: { tallaColaNaAjax: true },
            jsonReader: { root: "response", repeatitems: false },
        }).trigger("reloadGrid");
    }
}
//Registrar precios Aguajes
function validaPreciosAguaje() {
    let data = $('#frm_precios').serializeObject();
    data["savePreciosCamaronAjax"] = true;
    data["tallEntero"] = $('#tallEntero').getGridBatch();
    data["tallEntero"].forEach(item => {
        item.Precio_A = $('#precioA_' + item.Cod_Tall).val();
        item.Precio_B = $('#precioB_' + item.Cod_Tall).val();
    });
    data["tallTipA"] = $('#tallTipA').getGridBatch();
    data["tallTipA"].forEach(item => {
        item.Precio_CA = $('#precioA_' + item.Cod_Tall).val();
        item.Precio_CB = $('#precioB_' + item.Cod_Tall).val();
    });
    data["tallTipB"] = $('#tallTipB').getGridBatch();
    data["tallTipB"].forEach(item => {
        item.Precio_A = $('#precioA_' + item.Cod_Tall).val();
        item.Precio_B = $('#precioB_' + item.Cod_Tall).val();
    });
    data["tallTipNac"] = $('#tallTipNac').getGridBatch();
    data["tallTipNac"].forEach(item => {
        item.Precio_NA = $('#precioNA_' + item.Cod_Tall).val();
        item.Precio_NB = $('#precioNB_' + item.Cod_Tall).val();
    });
    data["Agu_Cod"] = $('#frm_precios #Agu_Cod').val();
    $.post("", data, function (responce) {
        if (responce['success'] === true) {
            $.alert("registrado con éxito");
            $('#frm_precios')[0].reset();
            $('#aguajesDialog').dialog('close');
        } else {
            $.alert(responce['message']);
        }
    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });
}

//LIQUIDACION
function validarNum_Liq(generado = false) {
    $.post('', { 'numLiqAjax': true }, function (Num_Liq) {
        $('#frm_liquidacion').setData({ 'Num_Liq': Num_Liq }, false);
    }, 'json').fail(function () { $.alert(); });
}
validarNum_Liq(true);
//CARGAR DATOS PARA LA LIQUIDACION
/*function loadDataLiq(opc) {
    let cod_Agu = 0;
    if (opc == 'editLiq') { cod_Agu = $('#frm_Edit_Liq #Cod_Agu').val(); }
    if (opc == 'addLiq') { cod_Agu = $('#frm_liquidacion #Cod_Agu').val(); }
    $.post('', { 'aguajesAjax': true, 'Agu_Cod': cod_Agu }, function (response) {
        let data = response.response || response;
        if (opc == 'editLiq') {
            $('#frm_Edit_Liq').setData({ 'Dir_Emp': data[0].Prs_Dir, 'Empa_Cod': data[0].Prod_Cod, 'Ciu': data[0].Ciu_Des, 'Nom_Emp': data[0].Empacadora }, false);
        }
        if (opc == 'addLiq') {
            $('#frm_liquidacion').setData({ 'Dir_Emp': data[0].Prs_Dir, 'Empa_Cod': data[0].Prod_Cod, 'Ciu': data[0].Ciu_Des, 'Nom_Emp': data[0].Empacadora }, false);
        }
    }, 'json').fail(function () { $.alert(); });
}*/
//CARGAR DATOS PARA LA LIQUIDACION
function loadDataLiq(opc) {
    let cod_Agu = 0;
    if (opc == 'editLiq') {
        cod_Agu = $('#frm_Edit_Liq #Cod_Agu').val();
    }
    if (opc == 'addLiq') {
        cod_Agu = $('#frm_liquidacion #Cod_Agu').val();
    }
    // alert(cod_Agu);
    $.post('', { 'aguajesAjax': true, 'Agu_Cod': cod_Agu }, function (response) {
        console.log(response.response);
        let data = response.response || response;
        if (opc == 'editLiq') {
            $('#frm_Edit_Liq').setData({
                'Num_Neg': cod_Agu, 'Dir_Emp': data[0].Prs_Dir, 'Empa_Cod': data[0].Emc_Cod, 'Ciu': data[0].Ciu_Des, 'Nom_Emp': data[0].Empacadora
            }, false);
        }

        if (opc == 'addLiq') {
            $('#frm_liquidacion').setData({
                'Num_Neg': cod_Agu, 'Dir_Emp': data[0].Prs_Dir, 'Empa_Cod': data[0].Emc_Cod, 'Ciu': data[0].Ciu_Des, 'Nom_Emp': data[0].Empacadora
            }, false);
        }
    }, 'json').fail(function () { $.alert(); });
}
//Registrar liquidacion
function validaDocumentLiq() {
    $.saveDataJson('', $('#frm_liquidacion').getData('saveLiqAjax'), function (resp) {
        $('#frm_liquidacion')[0].reset();
        $('#liquidacionDialog').dialog('close');
    });
}

function tipPagoNego(val) { // 1 con anticipo (crédito), 2 sin anticipo (contado)
    if (val == 1) {
        $("#link_garan").show();
        $("#linkVerfGaran").show();
    } else {
        $("#link_garan").hide();
        $("#linkVerfGaran").hide();
    }
}

$(document).ready(function () {
    function calcularDivision() {
        var peso = parseFloat($("#frm_liquidacion #Peso_Net").val()) || 0;
        var libs = parseFloat($("#frm_liquidacion #Lib_Proces").val()) || 0;
        //var resultado = (libs !== 0) ? (libs / peso).toFixed(2) : "";
        var resultado = (libs !== 0) ? ((libs / peso) * 100).toFixed(2) : "";
        $("#frm_liquidacion #Val_Rendi").val(resultado);
    }
    function obt_libFalta() {
        var peso_rem = parseFloat($("#frm_liquidacion #Peso_Rem").val());
        var pes_planta = parseFloat($("#frm_liquidacion #Peso_Planta").val());
        var resultado = (peso_rem - pes_planta).toFixed(2);
        $("#frm_liquidacion #Lib_Falt").val(resultado);
    }
    function obt_pesNet() {
        var pes_planta = parseFloat($("#frm_liquidacion #Peso_Planta").val());
        var basura = parseFloat($("#frm_liquidacion #Basur").val());
        var resultado = (pes_planta - basura).toFixed(2);
        $("#frm_liquidacion #Peso_Net").val(resultado);
    }
    //AGREGAR LIQUIDACION
    $("#frm_liquidacion #Peso_Net, #frm_liquidacion #Lib_Proces").on("input", calcularDivision);
    $("#frm_liquidacion #Peso_Rem, #frm_liquidacion #Peso_Planta").on("input", obt_libFalta);
    $("#frm_liquidacion #Basur, #frm_liquidacion #Peso_Planta").on("input", obt_pesNet);
    //EDITAR LIQUIDACION
    function calRendEdiLiq() {
        var peso = parseFloat($("#frm_Edit_Liq #Peso_Net").val()) || 0;
        var libs = parseFloat($("#frm_Edit_Liq #Lib_Proces").val()) || 0;
        //var resultado = (libs !== 0) ? (peso / libs).toFixed(2) : "";
        var resultado = (libs !== 0) ? ((libs / peso) * 100).toFixed(2) : "";
        $("#frm_Edit_Liq #Val_Rendi").val(resultado);
    }
    function edit_obt_libFalta() {
        var peso_rem = parseFloat($("#frm_Edit_Liq #Peso_Rem").val());
        var pes_planta = parseFloat($("#frm_Edit_Liq #Peso_Planta").val());
        var resultado = (peso_rem - pes_planta).toFixed(2);
        $("#frm_Edit_Liq #Lib_Falt").val(resultado);
    }
    function edit_obt_pesNet() {
        var pes_planta = parseFloat($("#frm_Edit_Liq #Peso_Planta").val());
        var basura = parseFloat($("#frm_Edit_Liq #Basur").val());
        var resultado = (pes_planta - basura).toFixed(2);
        $("#frm_Edit_Liq #Peso_Net").val(resultado);
    }
    $("#frm_Edit_Liq #Peso_Net, #frm_Edit_Liq #Lib_Proces").on("input", calRendEdiLiq);
    $("#frm_Edit_Liq #Peso_Rem, #frm_Edit_Liq #Peso_Planta").on("input", edit_obt_libFalta);
    $("#frm_Edit_Liq #Basur, #frm_Edit_Liq #Peso_Planta").on("input", edit_obt_pesNet);
});

$(document).ready(function () {
    $("input[name='tipPaq']").on("change", function () {
        var Cod_Agu = $('#frm_det_liq #Cod_Agu').val(); // Obtener el valor, no el objeto
        var tipoEmpaque = $(this).val();
        $.post('', { 'loadCostosaguajesAjax': true, 'Cod_Agu': Cod_Agu, 'Tip': tipoEmpaque }, function (response) {
            let select = $('#frm_det_liq #Cod_Prec');
            select.empty();
            let data = response.response || response;
            if (Array.isArray(data)) {
                data.forEach(item => {
                    let option = $('<option>', { value: item.Cod_Prec, 'data-precio': item.Prec_A, text: item.Talla + " --- " + item.Cod_Prec });
                    select.append(option);
                });
            } else {
                console.error("El servidor no devolvió un array:", response);
            }
        }, 'json').fail(function () {
            $.alert({ title: 'Error', content: 'No se pudo obtener los datos.' });
        });
    });
});

$(document).ready(function () {
    $(".saveDetLiq").on("click", function (e) {
        e.preventDefault(); // Evitar recargar la página
        let codPrec = $("#Cod_Prec").val();
        let precio = $("#Cod_Prec option:selected").attr("data-precio");
        let talla = $("#Cod_Prec option:selected").text(); // Obtener el texto del select
        let cantidad = $("#cant").val();
        let medida = $("input[name='medTip']:checked").val();
        let tipo = $("input[name='tipPaq']:checked").val();
        let Tip_Cla = $("input[name='clasf']:checked").val();
        if (!codPrec || !cantidad) { $.alert('Debe ingresar un costo y cantidad.'); return; }
        tablasPrecLiq.jqGrid('addRowData', codPrec, { Cod_Prec: codPrec, Tip_Cla: Tip_Cla, Tip: tipo, Talla: talla, Prec: precio, Cant: cantidad, Med: medida, total: (cantidad * precio).toFixed(2) });
        actualizarFooter();
        $("#Cod_Prec").val('');
        $("#cant").val('');
    });
});

function actualizarFooter() {
    let totalSum = tablasPrecLiq.jqGrid('getCol', 'total', false, 'sum'); // Sumar la columna "total"
    tablasPrecLiq.jqGrid('footerData', 'set', {
        total: totalSum.toFixed(2) // Mostrar el total en el footer
    }, true);
}

//Registrar detalles de la liquidacion 
function validaDocEditLiq() {
    let data = $('#frm_det_liq').serializeObject();
    data["det_liqui"] = $('#tablasPrecLiq').getGridBatch();
    data["saveDetLiqAjax"] = true;
    console.log(data);
    $.post("", data, function (responce) {
        if (responce['status'] === "success") {
            $.alert("registrado con éxito");
        } else {
            $.alert(responce['message']);
        }
    }, 'json').fail(function (error) { $.alert("El Servidor ha fallado en responder!"); });
}

//Imprimir liquidaciones
/*function imprimirLiquidacion(banTipo) {
    let dataLiq = [];
    let cod_neg = 0;
    $("#containerLiquidacion .chk-select:checked").each(function () {
        let rowId = $(this).data("row-id"); // Obtener el rowId de la fila
        let rowData = $("#containerLiquidacion").jqGrid('getRowData', rowId); // Obtener datos de la fila
        cod_neg = rowData.Cod_Neg;
        dataLiq.push(rowData); // Guardar en el array
    });
    $.post("", { dataReportLiq: true, dataLiq: (dataLiq), Cod_Neg: cod_neg }, function (response) {
        if (response['success'] === true) {
            if (banTipo)
                $(response['html']).printElement({ pageTitle: ' Negociación camarón [EXA]' });
            else
                $.downloadFile($.exportarExcelBlob(response['html'], 'CCPP'), 'CtaPorCobrar-' + $.getDate() + '.xls');
        } else { $.alert(response['message']); }
    }, 'json').fail(function (error) { $.alert("El Servidor ha fallado en responder!"); });
}*/

//Imprimir liquidaciones
function imprimirLiquidacion(banTipo) {
    // Bloquea múltiples clics mientras el servidor genera/carga el reporte
    if (window.__imprimirLiquidacionBloqueado === true) return;
    const $btnImprimir = $('#btn_imprimir_liquidacion');
    const $btnImprimirOriginalHtml = $btnImprimir.length ? $btnImprimir.html() : null;
    window.__imprimirLiquidacionBloqueado = true;
    if ($btnImprimir.length) {
        $btnImprimir.prop('disabled', true).addClass('disabled');
        $btnImprimir.html('<i class="fa fa-spinner fa-spin"></i> Generando...');
    }
    let dataLiq = [];
    let cod_neg = 0;
    $("#containerLiquidacion .chk-select:checked").each(function () {
        let rowId = $(this).data("row-id"); // Obtener el rowId de la fila
        let rowData = $("#containerLiquidacion").jqGrid('getRowData', rowId); // Obtener datos de la fila
        cod_neg = rowData.Cod_Neg;
        productor = rowData.productor;
        dataLiq.push(rowData); // Guardar en el array
    });
    // Si no hay seleccionados, no tiene sentido bloquear / llamar al servidor
    if (dataLiq.length === 0) {
        if ($btnImprimir.length && $btnImprimirOriginalHtml !== null) {
            $btnImprimir.prop('disabled', false).removeClass('disabled');
            $btnImprimir.html($btnImprimirOriginalHtml);
        }
        window.__imprimirLiquidacionBloqueado = false;
        $.alert("Debe seleccionar al menos una liquidación para imprimir.");
        return;
    }
    $.post("", { dataReportLiq: true, dataLiq: (dataLiq), Cod_Neg: cod_neg, productor: productor }, function (response) {
        try {
            if (response['success'] === true) {
                if (banTipo)
                    $(response['html']).printElement({ pageTitle: ' Negociación camarón [EXA]' });
                else
                    $.downloadFile($.exportarExcelBlob(response['html'], 'LIQUI'), 'liquidacion-' + $.getDate() + '.xls');
            } else {
                $.alert(response['message']);
            }
        } finally {
            window.__imprimirLiquidacionBloqueado = false;
            if ($btnImprimir.length && $btnImprimirOriginalHtml !== null) {
                $btnImprimir.prop('disabled', false).removeClass('disabled');
                $btnImprimir.html($btnImprimirOriginalHtml);
            }
        }
    }, 'json').fail(function (error) {
        window.__imprimirLiquidacionBloqueado = false;
        if ($btnImprimir.length && $btnImprimirOriginalHtml !== null) {
            $btnImprimir.prop('disabled', false).removeClass('disabled');
            $btnImprimir.html($btnImprimirOriginalHtml);
        }
        $.alert("El Servidor ha fallado en responder!");
    });
}

function exportar(banTipo) {
    var batch = new Array();
    var grid = $("#searchGrid_mod");
    var ids = grid.jqGrid('getDataIDs');
    var periodo = $("#por_peri").val();
    var valor_peri = $("#sel_per").val();
    var rango1Ini = $("#rango1Ini").val();
    var rango1Fin = $("#rango1Fin").val();
    var rango2Ini = $("#rango2Ini").val();
    var rango2Fin = $("#rango2Fin").val();
    var rango3Ini = $("#rango3Ini").val();
    var rango3Fin = $("#rango3Fin").val();
    for (var i = 0; i < ids.length; i++) {
        var datos = grid.jqGrid('getRowData', ids[i]), ban = true;
        for (var j = 0; j < batch.length; j++) {
            if (datos['Cli_Cod'] === batch[j]['Cli_Cod']) { ban = false; }
        }
        if (ban) batch.push({ Cli_Cod: datos['Cli_Cod'], Cliente: datos['Cliente'] });
    }
    if (batch.length > 0) {
        $.post("", { dataReportLiq: batch, por_peri: periodo, sel_per: valor_peri, rango1Ini: rango1Ini, rango1Fin: rango1Fin, rango2Ini: rango2Ini, rango2Fin: rango2Fin, rango3Ini: rango3Ini, rango3Fin: rango3Fin }, function (response) {
            if (response['success'] === true) {
                if (banTipo)
                    $(response['html']).printElement({ pageTitle: '<?php echo $Ses_Sys_Nom; ?>' });
                else
                    $.downloadFile($.exportarExcelBlob(response['html'], 'CCPP'), 'CtaPorCobrar-' + $.getDate() + '.xls');
            } else { $.alert(response['message']); }
        }, 'json').fail(function (error) { $.alert("El Servidor ha fallado en responder!"); });
    }
    else {
        $("#searchGrid_mod").startGridEdit(); $.alert("No hay Datos!");
    }
}

function cancelarNegociacion() {
    $('#frm_negociacion')[0].reset(); // Limpia el formulario
    $('#negociacionDialog').dialog('close');      // Cierra el modal
}

function canceEditNego() {
    $('#frm_EditNego')[0].reset(); // Limpia el formulario
    $('#liqNegDialog').dialog('close');      // Cierra el modal
}

function cancelarEditLiq() {
    $('#editLiqDialog').dialog('close');      // Cierra el modal
}
//CARGAR LISTADO DE AGUAJES 
function loadEditSelect() {
    $.post('', { 'aguajesAjax': true }, function (response) {
        let select = $('#frm_EditNego #Cod_Agu'); // Reemplaza con el ID de tu <select>
        select.empty();
        let data = response.response || response;
        select.append($('<option>', { value: '', text: '-- Seleccione --' })); // Opción vacía
        if (Array.isArray(data)) {
            data.forEach(item => {
                let option = $('<option>', { value: item.Agu_Cod, text: item.Nom_Agu + ' - ' + item.Desc_Agu + ' - ' + item.Num_Agu });
                select.append(option);
            });
        } else { console.error("El servidor no devolvió un array:", response); }
    }, 'json').fail(function () { $.alert(); });
}

loadEditSelect();
function loadSector(Prod_Cod) {
    $.post('', { 'sectorAjax': true, Prod_Cod: Prod_Cod }, function (response) {
        let select = $('#frm_EditNego #Sec_Cod'); // Reemplaza con el ID de tu <select>
        select.empty();
        if (Array.isArray(response.response)) {
            response.response.forEach(function (item) {
                console.log(item.Sec_Cod);
                select.append(`<option value="${item.Sec_Cod}">${item.Sec_Nom}</option>`);
            });
        } else { console.error('La respuesta no es un array:', response); }
    }, 'json').fail(function () { $.alert(); });
}
// Anular negociación
function delFila(data) {
    $("<div></div>").dialog({
        modal: true, title: "Confirmación de Anulación",
        open: function () {
            var markup = "¿Estás seguro de que deseas anular la negociación?";
            $(this).html(markup);
        }, buttons: {
            "Anular": function () {
                $.post('', { anularNegAjax: true, Cod_Neg: data.Cod_Neg }, function (response) {
                    if (response.success) {
                        $("#container").jqGrid('delRowData', data.id);
                        $.alert(response.message)
                    } else { $.alert(response.message) }
                }, 'json').fail(function () { $.alert("Error en la comunicación con el servidor.") });
                $(this).dialog("close");
            }, "Cancelar": function () { $(this).dialog("close"); }
        },
        close: function () { $(this).remove(); }
    });
}

function delFilaLiq(data) {
    $("<div></div>").dialog({
        modal: true, title: "Confirmación de Anulación",
        open: function () {
            var markup = "¿Estás seguro de que deseas anular la Liquidación?";
            $(this).html(markup);
        }, buttons: {
            "Anular": function () {
                $.post('', { anularLiqAjax: true, Liq_Cod: data.Liq_Cod }, function (response) {
                    if (response.success) {
                        $("#containerLiquidacion").jqGrid('delRowData', data.id);
                        $.alert(response.message)
                    } else { $.alert(response.message) }
                }, 'json').fail(function () { $.alert("Error en la comunicación con el servidor.") });
                $(this).dialog("close");
            }, "Cancelar": function () { $(this).dialog("close"); }
        },
        close: function () { $(this).remove(); }
    });
}

//Editar Negociación
$(document).on('click', '#btn_edit_nego', function () {
    $("#form_bandera").val("NEG_EDIT");
    var rowId = $(this).data('row-id');
    var data = $('#container').jqGrid('getRowData', rowId);
    $("#liqNegDialog").dialog({ autoOpen: false, modal: true, width: 700, maxWidth: 2000, height: 500, position: { my: "center", at: "center", of: window }, show: { effect: "fade", duration: 300 }, hide: { effect: "fade", duration: 300 } });
    $('#frm_EditNego #Cod_Neg').val(data.Cod_Neg);
    $('#frm_EditNego #Num_Neg').val(data.Num_Neg);
    $('#frm_EditNego #Fec_Neg').val(data.Fec_Neg);
    $('#frm_EditNego #Tip_Neg').val(data.Tip_Neg);
    $('#frm_EditNego #Cod_Agu').val(data.Cod_Agu).trigger('change');
    $('#frm_EditNego #Prod_Cod').val(data.Prod_Cod);
    loadSector(data.Prod_Cod);
    $('#frm_EditNego #Cli_Cod').val(data.Cli_Cod);
    $('input[name="Clasf"][value="' + data.Clasf + '"]').prop("checked", true);
    $('#frm_EditNego #Prec_Comis').val(data.Prec_Comis);
    $('#frm_EditNego #Telf_Prod').val(data.Telf_Prod);
    $('#frm_EditNego #Tot_Libras').val(data.Tot_Libras);
    $('#frm_EditNego #clasf').val(data.clasf);
    if (data.Est_Neg === "C") { $('#frm_EditNego #EstCose').prop("checked", true); } else { $('#frm_EditNego #EstCose').prop("checked", false); }
    $('#frm_EditNego #Sec_Cod').val(data.Sec_Cod);
    $('#frm_EditNego #Fec_Pesca').val(data.Fec_Pesca);
    $('#frm_EditNego #Val_Ant').val(data.Val_Ant);
    $('#frm_EditNego #Nom_Prod').val(data.productor);
    loadEmpacadoraEditLiq();
    if (data.Est_Neg === "C") { $('#btn_update_nego').hide(); } else { $('#btn_update_nego').show(); }
    $('#liqNegDialog').dialog('open');
});
//Registrar liquidacion
function valDocUpdNeg() {
    $.saveDataJson('', $('#frm_EditNego').getData('updateNegAjax'), function (resp) {
        container.jqGrid('setGridParam', { datatype: 'json' }).trigger('reloadGrid');
    });
}
//Editar liquidación
function loadAguajes() {
    $.post('', { 'aguajesAjax': true }, function (response) {
        let select = $('#frm_Edit_Liq #Cod_Agu'); // Reemplaza con el ID de tu <select>
        select.empty();
        let data = response.response || response;
        select.append($('<option>', { value: '', text: '-- Seleccione --' })); // Opción vacía
        if (Array.isArray(data)) {
            data.forEach(item => {
                let option = $('<option>', { value: item.Agu_Cod, text: item.Nom_Agu + ' - ' + item.Desc_Agu + ' - ' + item.Num_Agu });
                select.append(option);
            });
        } else { console.error("El servidor no devolvió un array:", response); }
    }, 'json').fail(function () { $.alert(); });
}
loadAguajes();
//Cargar productor
function loadProductor(Prod_Cod) {
    $.post('', { 'productorAjax': true, Prod_Cod: Prod_Cod, Tip_Prod: 'PROD' }, function (response) {
        let data = response.response || response;
        $('#frm_Edit_Liq #productor').val(data.nombres);
        $('#frm_Edit_Liq #Telf_Prod').val(data.Prs_Tel);
        $('#frm_Edit_Liq #Prs_Dir').val(data.Prs_Dir);
    }, 'json').fail(function () { $.alert(); });
}
//Cargar Empacadora
/*
function loadEmpacadora(Empa_Cod) {
    $.post('', { 'productorAjax': true, Prod_Cod: Empa_Cod, Tip_Prod: 'EMPA' }, function (response) {
        let data = response.response || response;
        $('#frm_Edit_Liq #Nom_Emp').val(data.nombres);
        $('#frm_Edit_Liq #Dir_Emp').val(data.Prs_Dir);
        $('#frm_Edit_Liq #Ciu').val(data.Ciu_Des);
    }, 'json').fail(function () { $.alert(); });
}*/
//Cargar Empacadora
function loadEmpacadora(Empa_Cod) {
    $.post('', { 'loadempacadoraAjax': true, Prod_Cod: Empa_Cod, Tip_Prod: 'EMPA' }, function (response) {
        let data = response.response || response;
        $('#frm_Edit_Liq #Nom_Emp').val(data.nombres);
        $('#frm_Edit_Liq #Dir_Emp').val(data.Prs_Dir);
        $('#frm_Edit_Liq #Ciu').val(data.Ciu_Des);
    }, 'json').fail(function () { $.alert(); });
}

$(document).on('click', '#btn_edit_liqui', function () {
    $("#form_bandera").val("LIQ_EDIT");
    var rowId = $(this).data('row-id');
    var data = $('#containerLiquidacion').jqGrid('getRowData', rowId);
    $("#liquiEditDialog").dialog({ autoOpen: false, modal: true, width: 700, maxWidth: 2000, height: 500, position: { my: "center", at: "center", of: window }, show: { effect: "fade", duration: 300 }, hide: { effect: "fade", duration: 300 } });
    //CARGAR LIQUIDACION
    $('#frm_Edit_Liq #Liq_Cod').val(data.Liq_Cod);
    $('#frm_Edit_Liq #Val_Guia').val(data.Val_Guia);
    $('#frm_Edit_Liq #Val_Gram_Glo').val(data.Val_Gram_Glo);
    $('#frm_Edit_Liq #Peso_Prom').val(data.Peso_Prom);
    $('#frm_Edit_Liq #Cod_Neg').val(data.Cod_Neg);
    $('#frm_Edit_Liq #Val_Pisc').val(data.Val_Pisc);
    $('#frm_Edit_Liq #Val_Comision').val(data.Val_Comision);
    $('#frm_Edit_Liq #Gast_Control').val(data.Gast_Control);
    $('#frm_Edit_Liq #Otr_Gastos').val(data.Otr_Gastos);
    $('#frm_Edit_Liq #Liq_Fecha').val(data.Liq_Fecha);
    $('#frm_Edit_Liq #Num_Liq').val(data.Num_Liq);
    $('#frm_Edit_Liq #Num_Neg').val(data.Num_Neg);
    $('#frm_Edit_Liq #Cod_Agu').val(data.Cod_Agu).trigger('change');
    loadProductor(data.Prod_Cod);
    $('#frm_Edit_Liq #Prod_Cod').val(data.Prod_Cod);
    $('#frm_Edit_Liq #Empa_Cod').val(data.Empa_Cod);
    loadEmpacadora(data.Empa_Cod);
    $('#frm_Edit_Liq #Nom_Emp').val(data.Nom_Emp);
    $('#frm_Edit_Liq #Dir_Emp').val(data.Dir_Emp);
    $('#frm_Edit_Liq #Ciu').val(data.Ciu);
    $('#frm_Edit_Liq #Liq_Fecha').val(data.Liq_Fecha);
    $('#frm_Edit_Liq #Peso_Rem').val(data.Peso_Rem);
    $('#frm_Edit_Liq #Peso_Planta').val(data.Peso_Planta);
    $('#frm_Edit_Liq #Lib_Falt').val(data.Lib_Falt);
    $('#frm_Edit_Liq #Basur').val(data.Basur);
    $('#frm_Edit_Liq #Peso_Net').val(data.Peso_Net);
    $('#frm_Edit_Liq #Lib_Proces').val(data.Lib_Proces);
    $('#frm_Edit_Liq #Val_Rendi').val(data.Val_Rendi);
    $('#frm_Edit_Liq #Val_Lote').val(data.Val_Lote);
    $('#liquiEditDialog').dialog('open');
});

//Editar liquidacion
function validaDocumentEditLiq() {
    $.saveDataJson('', $('#frm_Edit_Liq').getData('editLiqAjax'), function (resp) {
        Cod_Neg = $('#frm_Edit_Liq #Cod_Neg').val();
        load_liquidacion({ Cod_Neg: Cod_Neg });
        $('#frm_Edit_Liq')[0].reset();
        $('#liquiEditDialog').dialog('close');
    });
}
function cancelarLiqui() {
    $('#frm_Edit_Liq')[0].reset();
    $('#liquiEditDialog').dialog('close');
}
function searchAguaje() {
    search = $('#frm_precios #search').val();
    load_Aguajes(search);
}
function cancelarAddLiq() {
    $('#frm_liquidacion')[0].reset();
    $('#liquidacionDialog').dialog('close');
}

//CARGAR LISTADO DE Empacadoras
function loadEmpacadoraSelect() {
    $.post('', { 'empacadoraAjax': true }, function (response) {
        let select = $('#frm_aguaje #Prod_Cod'); // Reemplaza con el ID de tu <select>
        select.empty();
        let data = response.response || response;
        select.append($('<option>', { value: '', text: '-- Seleccione --' })); // Opción vacía
        if (Array.isArray(data)) {
            data.forEach(item => {
                //let option = $('<option>', { value: item.Prod_Cod, text: item.Empacadora });
                let option = $('<option>', { value: item.Emc_Cod, text: item.Empacadora });
                select.append(option);
            });
        } else { console.error("El servidor no devolvió un array:", response); }
    }, 'json').fail(function () { $.alert(); });
}

//CARGAR LISTADO DE AGUAJES 
function loadEmpacadoraEditLiq() {
    $.post('', { 'empacadoraAjax': true }, function (response) {
        let select = $('#frm_EditNego #Empa_Cod'); // Reemplaza con el ID de tu <select>
        select.empty();
        let data = response.response || response;
        select.append($('<option>', { value: '', text: '-- Seleccione --' })); // Opción vacía
        if (Array.isArray(data)) {
            data.forEach(item => {
                //let option = $('<option>', { value: item.Prod_Cod, text: item.Empacadora });
                let option = $('<option>', { value: item.Emc_Cod, text: item.Empacadora });
                select.append(option);
            });
        } else { console.error("El servidor no devolvió un array:", response); }
    }, 'json').fail(function () { $.alert(); });
}
