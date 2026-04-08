/**
 * @fileoverview Libreria con funciones de validaciones
 *
 * @author Erick Cordova
 * @version 0.1
 */
/**
 * Validar el guardado de cheques
 */

var grid_inicial = "";
$(function () {
    cargarBancos();
    cargarPeriodos();
    crear_grid_cheques();
    gridStartMovimiento();
    $('.datepicker').createDatePickers({checkAvailability: false, hideMsg: false}).mask("9999-99-99", {placeholder: "_"});
    $('#periodos').on('change', changePerido);
    $('#TipBus').chosen().change(changeBusTip);
    $('#Bak_Cod').chosen({no_results_text: "Oops, sin coincidencias!"});
    $('#Bak_Cod_Selec').chosen({no_results_text: "Oops, sin coincidencias!"});
    $('#periodos').chosen({no_results_text: "Oops, sin coincidencias!"});
    $('#documentoResult').css('visibility', '').hide();
    $('#documentoResult').show();
    $('#gestionarDialog').createDialog({icon: 'plus', width: 500, height: 356});
    $('#protestarDialog').createDialog({icon: 'plus', width: 500, height: 356});
    $('#successDialog').createDialog({width: 500, height: 200});
    $('#Mov_Tip').on('change', filtrar_movimiento);
    $('select[name=Tia_Ini]').on('change', listarTiposCompr);
});

function crear_grid_cheques() {
    $('#searchGrid').createGrid({
        caption: 'Resultado de la B�squeda', height: 270, datatype: "local", caption: 'Resultados',
        colModel: [
            {label: 'C�d. Int.', name: 'Che_Box', width: 30, align: "center", formatter: 'checkboxExa', formatoptions: {yes: 'Yes'}},
            {label: 'C�d. Int.', name: 'Che_Cod', width: 30, align: "center", key: true},
            {label: 'Banco', name: 'Bak_Cod', hidden: true, align: "center"},
            {label: 'Banco', name: 'Bak_Des', width: 90, align: "center"},
            {label: 'N. Cuenta', name: 'Che_Cta', width: 50, align: "center"},
            {label: 'No. Cheque', name: 'Che_Num', width: 80, formatter: format_che_num, unformat: unformat_che_num, align: "center"},
            {label: 'Fecha', name: 'Che_Fec', width: 45, align: "center"},
            {label: 'Cli_Cod', name: 'Cli_Cod', hidden: true, width: 45, align: "center"},
            {label: 'Cliente', name: 'Cli_Nom', width: 160, align: "center"},
            {label: 'Valor', name: 'Che_Val', summaryRound: 2, formatter: "currency", width: 45, align: "right"},
            {label: 'Estado', name: 'Che_Est', width: 65, editable: false, viewable: false, formatter: 'select', title: false, edittype: "select", editoptions: {
                    dataInit: function (el) {
                        if ($(el).find('option:selected').val() === 'C')
                        {
                            $(el).find('option[value="A"]').prop('disabled', true);
                        }
                    }, value: "A:No Cobrado;C:Cobrado;P:Protestado;D:Depositado"}},
            {label: 'Aplazado', name: 'Che_Apl', width: 40, editable: true, edittype: "text", viewable: false, editoptions: {
                    dataInit: function (el, obj) {

                        $(el).createDatePickers({checkAvailability: true, clean: true});
                        $(el).datepicker("option", {"minDate": $(this).jqGrid('getRowData', obj['rowId']).Che_Fec, "disabled": ($(this).jqGrid('getRowData', obj['rowId']).Che_Est !== 'A' ? true : false)}).mask("9999-99-99", {placeholder: "_"});
                    }
                }, classes: 'bgNoRight bgNoColor'
            },
            {label: 'Cobrado', name: 'Che_Cob', width: 40, editable: false, edittype: "text", viewable: false, editoptions: {
                    dataInit: function (el, obj) {
                        $(el).createDatePickers({checkAvailability: true, clean: true});
                        $(el).datepicker("option", "minDate", $(this).jqGrid('getRowData', obj['rowId']).Che_Fec).mask("9999-99-99", {placeholder: "_"});
                    }
                }
            },
            {label: '&nbsp;', name: 'act0', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: {action: viewInfo, title: 'Ver Movimientos', icon: 'info-sign', type: 'info'}, title: false},
            {label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'protestar', title: false}
        ], rowNum: 10000000, footerrow: true,
        loadComplete: function (data) {
            //console.log(data.rows);
            $(this).setGridSummary(['Che_Val'], {Cli_Nom: '<div style="text-align:right;">TOTAL:</div>'});
            grid_inicial = $('#searchGrid').getGridBatch();
            $('#searchGrid').startGridEdit();
            $('select[name=Che_Est]').attr('onChange', 'changeEstado($(this))');
            if ($.varValid(data.rows))
                for (var i = 0, z = data.rows.length; i < z; i++) {
                    if (data.rows[i]['Che_Est'] !== 'A')
                        $("#" + data.rows[i].Che_Cod + ' input[type=checkbox]').attr('disabled', 'disabled');
                    if (data.rows[i]['Che_Est'] === 'P')
                        $("#" + data.rows[i].Che_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
                    if (data.rows[i]['Che_Est'] === 'C')
                        $("#" + data.rows[i].Che_Cod + ' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                    if (data.rows[i]['Che_Est'] === 'D')
                        $("#" + data.rows[i].Che_Cod + ' td:not(.jqgrid-rownum)').addClass('cellBlue2');
                }

        }
    }, true, '#searchGridPager', {refresh: true}).gridButtonsAdd([null,
        {caption: "Marcar", buttonicon: "glyphicon glyphicon-check", title: 'Marcar Todos',
            onClickButton: function () {
                $('input[type=checkbox][disabled!="disabled"]').prop('checked', true);

            }
        },
        {caption: "Desmarcar", buttonicon: "glyphicon glyphicon-unchecked", title: 'desmarcar Todos',
            onClickButton: function () {
                //$('#searchGrid').selectAllByCol('Che_Box', false);
                $('input[type=checkbox][disabled!="disabled"]').prop('checked', false);
            }
        },
        {caption: "Gestionar", buttonicon: "fa fa-cog", title: 'Gestioanar',
            onClickButton: function () {
                openGestinar();
            }
        },
        {buttonicon: 'print', caption: 'Imprimir', onClickButton: function () {
                $('#searchGrid').getGridBatch()
                printR('#searchGrid');
                $('#searchGrid').startGridEdit();
            }},
        {buttonicon: 'download-alt', caption: 'Excel', onClickButton: function () {
                
                exportR('#searchGrid');
            }}
    ]);
}
function printR(grid) {
    $('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML', {removeHiddens:true,removeCols:[1,2,12,13,15,16],generated: false, caption: false, footer: true, bodyBorder: false}));
    $('#titleReporte').html($(grid).getCaption());
    $('#formatoReporte').printElement({pageTitle: "Cheques Recibidos", printMode: 'popup', overrideElementCSS: [{href: '../../mascaras/model1/estilos/print.css', media: 'print'}]});
}
function exportR(grid) {
    var temp = $('<div>' + $('#formatoExportar').html() + '</div>');
    temp.append($(grid).jqGrid('exportGridHTML', {removeHiddens:true,removeCols:[1,2,12,13,15,16],generated: false, caption: true, bodyBorder: false, footer: true, sepEnd: true}));
    $.downloadFile($.exportarExcelBlob(temp.html(), 'Cheques Recibidos'), 'cheques_ext_' + $.getDate() + '.xls');
}


var gridStartMovimiento = () => {
    $('#movimientosDialog').createSearchDialog({caption: 'Movimientos', height: 140, colModel: [
            {label: 'C&oacute;d.Int.', name: 'Com_Cod', key: true, width: 30, align: "center"},
            {label: 'Fecha', name: 'Mov_Fec', width: 40, align: "center"},
            {label: 'Comp.', name: 'Com_Num', width: 35, align: "center"},
            {label: 'Usuario', name: 'Usuario', width: 45},
            {label: 'Movimiento', name: 'Mov_Tip', formatter: 'select', edittype: "select", editoptions: {value: "A:No Cobrado;C:Cobrado;P:Protestado;D:Depositado"}, width: 35, align: "center"},
            {label: 'Doc.', name: 'Mov_Doc', width: 30, align: "center"},
            {label: 'Estado C.', name: 'Com_Est', width: 30, formatter: 'truefalse', formatoptions: {yesValue: 'A', yesMsg: 'Comprobante Activo', noMsg: 'Comprobante Inactivo'}, align: "center", title: false},
            {label: 'Estado M.', name: 'Mov_Est', width: 30, formatter: 'truefalse', formatoptions: {yesValue: 'A', yesMsg: 'Movimiento Activo', noMsg: 'Movimiento Inactivo'}, align: "center", title: false},
            {label: '&nbsp;', name: 'Com_Link', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: {action: mostrarComprobante, title: 'Imprimir comprobante', icon: 'fa-print', type: 'info'}, title: false}
        ]});
};


$.fn.fmatter.protestar = function (cv, opts, cObjt) {
    if (cObjt['Che_Est'] === 'A')
        return '<i title="Aun no se ha depositado" class="glyphicon glyphicon-lock orange"></i>';
    if (cObjt['Che_Est'] === 'C')
        return '<i title="Ya ha sido cobrado" class="glyphicon glyphicon-lock orange"></i>';
    if (cObjt['Che_Est'] === 'P')
        return '<i title="Ya ha sido protestado" class="glyphicon glyphicon-lock orange"></i>';
    return $.getGridButton(openProtestar, cObjt, 'Protestar Cheque', 'fa-exclamation-circle ', null, 'danger');
};

function format_che_num(cellvalue, options, rowObject) {
    return  pad(cellvalue, 9);
}
function unformat_che_num(cellvalue, options, rowObject) {
    return cellvalue * 1;
}
function pad(n, length) {
    var n = n.toString();
    while (n.length < length)
        n = "0" + n;
    return n;
}
function format_posfec(cellvalue, options, rowObject) {
    if (!$.varValid(cellvalue)) {
        cellvalue = "-";
    }
    return cellvalue;
}
function unformat_posfec(cellvalue, options, rowObject) {
    if (cellvalue === "-") {
        cellvalue = "";
    }
    return cellvalue;
}
$.fn.fmatter.edicion = function (cellvalue, options, rowObject) {
    return $.getGridButton(cargarDoc, rowObject);
};
function viewInfo(cheque) {
    //mostrar informacion de fila de cheque
    $('#movimientosDialog').setData(cheque);
    $('#movimientosDialog').getDialogGrid().Search({'Che_Cod': cheque.Che_Cod, 'movimientosAjax': true});

    $('#movimientosDialog').dialog('open');
}


function mostrarComprobante(val) {
    $.imprimirUrl(val.Com_Link);
}

function cargarBancos() {
    $.getDataJson('', {'cargarBancos': true}, function (resp) {
        //console.log(resp['bancos']);
        var option = $('<option></option>').text('<< TODOS >>').attr('value', 0);
        $('#Bak_Cod').append(option);
        $.each(resp['bancos'], function (index, banco) {
            var option = $('<option></option>').text(banco.Bak_Des).attr('value', banco.Bak_Cod).data(banco);
            $('#Bak_Cod').append(option);
        });
        $('#Bak_Cod').trigger("chosen:updated");
    }, function (err) {
        console.log(err['messsage']);
    });
}

function cargarPeriodos() {
    $.getDataJson('', {'cargarPeriodos': true}, function (resp) {
        $.each(resp['periodos'], function (index, periodo) {
            var option = $('<option></option>').text(periodo.Periodo).attr('value', periodo.Pec_Cod).data(periodo);
            $('#periodos').append(option);
        });
        $('#periodos').trigger("chosen:updated");
    }, function (err) {
        console.log(err['messsage']);
    });
}

function changePerido(event) {
    var select_date = $(this).find(':selected').data('Pec_Fef');
    $.each(['txt_fec_ini', 'txt_fec_fin'], function (index, elemento) {
        var setDate = moment($('#' + elemento).val() + '').year(moment(select_date).year());
        $('#' + elemento).val(setDate.format('YYYY-MM-DD')).datepicker("option", "changeYear", ($.varValid(select_date) ? false : true));
    });
}

function changeBusTip(event) {
    var array_selec = $(this).val();
    var array_bloque = $.grep(["2", "3", "4", "5"], function (n) {
        return $.inArray(n, array_selec) < 0;
    });
    if ($.inArray("0", array_selec) >= 0) {
        $("#TipBus option").not("#TipBus option[value=0]").attr('disabled', 'disabled');
    } else {
        $("#TipBus option").removeAttr('disabled');
        if (array_selec) {
            $("#TipBus option[value=0]").attr('disabled', 'disabled');
        } else {
            $("#TipBus option[value=0]").removeAttr('disabled');
        }
        if (array_bloque.length >= 4) {//mostrar
            $.each(array_bloque, function (ind, val) {
                $("#TipBus option[value=" + val + "]").removeAttr('disabled');
            });
        } else {//deshabilitar
            $.each(array_bloque, function (ind, val) {
                $("#TipBus option[value=" + val + "]").attr('disabled', 'disabled');
            });
        }
    }
    $('#TipBus').trigger("chosen:updated");
}



function cargarDoc(fila_cheque) {
    //console.log(fila_cheque);
    $('.formDatosCheque').setData(fila_cheque);
    $('#Bak_Cod_Selec').trigger("chosen:updated");
    $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
}


function verificarCambio(grid_final = $('#searchGrid').getGridBatch()) {
    $('#searchGrid').startGridEdit();
    return grid_final.filter((fila, indice) => {
        var filt_bool = !(grid_inicial[indice].Che_Apl === fila.Che_Apl);
        return filt_bool;
    }).map((fila) => {
        if (fila.Che_Apl === '')
            fila.Che_Apl = null;
        return fila;
    });
}

function cuentasAsignar(anio = moment().year(), Che_Cod = 0) {
    $('select[name=Pld_Cod]').find('option').remove();
    $.getDataJson('', {'cuentasMovimiento': true, fecha: anio}, function (resp) {
        resp['cuentas'].forEach((current, index, array) => {
            if (array.map((elem) => elem.Pld_Cod * 1).indexOf(current.Pld_Cod * 1, index + 1) < 0) {
                var option = $('<option></option>').text(current.Pld_Des + ' ' + (current.Ban_Cue * 1 !== 0 ? current.Ban_Cue : '')).attr('value', current.Pld_Cod).attr('banco', current.banco).data(current);
                $('select[name=Pld_Cod]').append(option);
            }
        });
        if (Che_Cod * 1 > 0) {
            $('select[name=Pld_Cod].protestar').find('option').hide();
            $('select[name=Pld_Cod].protestar').find('option[banco=si]').show();
            $.getDataJson('', {'cargarBancoProtestar': true, 'Che_Cod': Che_Cod}, function (resp) {
                $("select[name=Pld_Cod].protestar").val(resp.banco.Pld_Cod * 1);
            }, function (err) {
                console.log(err['message']);
            });
        } else {
            $('#Mov_Tip').trigger('change');
        }
    }, function (err) {
        console.log(err['message']);
    });
}


function filtrar_movimiento() {
    banco_si = $(this).find('option:selected').attr('banco');
    $('select[name=Pld_Cod]').find('option').hide();
    $('select[name=Pld_Cod]').find('option[banco=' + banco_si + ']').show();
    $("select[name=Pld_Cod]").val($("select[name=Pld_Cod] option[banco=" + banco_si + "]").val());
    if (banco_si === "si") {
        $('.deposito').show(700);
        $('#Com_Doc').attr('required', true);
    } else {
        $('.deposito').hide(700);
        $('#Com_Doc').removeAttr('required');
    }
}

function openGestinar() {
    $('#gestionarDialog').setData({comprobante: '', observacion: ''}, false);
    let data = [];
    data['cheques'] = $('#searchGrid').getSeletedByComlumn('Che_Box');
	$('#searchGrid').startGridEdit();
    if (data['cheques'].length > 0) {
        data['saveChequesExt'] = true;
        cuentasAsignar();
        $('#Tia_Ini').trigger('change');
        $('#gestionarDialog').dialog('open');
    } else {
        $.alert('Seleccione un Cheque');
    }
}
function openProtestar(fila) {
    $('#Che_Cod').data(fila);
    let data = [];
    data['saveChequesExt'] = true;
    cuentasAsignar(moment().year(), fila.Che_Cod);
    $('select[name=Tia_Ini].protestar').trigger('change');
    $('#protestarDialog').dialog('open');
}



var validarDocument = () => {
    let data = $('#movimientoForm').getData();
    data['cheques'] = $('#searchGrid').getSeletedByComlumn('Che_Box');
    che_bad_estate = data['cheques'].filter((cheque) => cheque.Che_Est !== "A");
    if (che_bad_estate.length > 0) {
        $.alert(`los cheques ${che_bad_estate.map((cheque) => cheque.Che_Num)} no pueden ser ${data.Mov_Tip_Data.Mov_Tip_Txt}s`);
    } else {
        che_sum = data['cheques'].map((che) => che.Che_Val * 1).reduce((a, b) => a + b);
        data['Com_Val'] = che_sum;
        codeine = data['cheques'].map((che) => che.Che_Cod);
        data['Che_Cod'] = codeine[0];
        console.log(data['Che_Cod']);
        cliente = data['cheques'].map((che) => che.Cli_Cod);
        data['Cli_Cod'] = cliente[0];
        console.log(data['Cli_Cod']);
        data['saveChequesExt'] = true;
        $.createDialogConfirm('¿Está seguro que desea realizar este movimiento?', data, saveDoc);
    }
};

var saveDoc = (data) => {
    $.saveDataJson('', data, respTrueMod, respFalseMod);
};


function listarTiposCompr() {
    $('select[name=Tia_Cod]').find('option').remove();
    $.getDataJson('', {'getTipoCompr': true, 'Tia_Ini': $(this).val()}, function (resp) {
        $.each(resp['tipos_compr'], function (index, tipo) {
            var option = $('<option></option>').text(tipo.Tia_Des).attr('value', tipo.Tia_Cod).data(tipo);
            $('select[name=Tia_Cod]').append(option);
        });
    }, function (err) {
        console.log(err['message']);
    });
}

var guardarCambios = (filas = verificarCambio()) => {
    data = {};
    if (filas.length > 0) {
        data['aplazarCheques'] = true;
        data['cheques'] = filas.map((cheque) => {
            return {'Che_Cod': cheque.Che_Cod, 'Che_Apl': cheque.Che_Apl};
        });
        //console.log(data, 'data send');
        $.saveDataJson('', data, respTrueMod, respFalseMod);
    } else {
        $.alert('No ha aplazado ningun cheque');
}
};



var respTrueMod = (resp) => {
    $('.dialog-exa').dialog('close');
    $('#searchGrid').trigger('reloadGrid');
    if ($.isset(resp['Com_Link']))
        $('#successDialog').dialog('open');
    $('#impCompr').attr('href', resp['Com_Link']);
};
var respFalseMod = (err) => {
    console.log(err);
};


var validarProtest = () => {
    let data = $('#protestarDialog').getData();
    data['saveChequesExt'] = true;
    data['cheque_selec'] = $('#Che_Cod').data();
    $.createDialogConfirm('¿Está seguro que desea realizar este movimiento?', data, saveDoc);
};