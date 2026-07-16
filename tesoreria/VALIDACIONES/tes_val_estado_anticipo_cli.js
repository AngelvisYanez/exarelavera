var grid = $('#searchGrid');
var arrayDetAsiento = [],
    arrayCheques = [],
    arrayModAsiento = [],
    arrayCuentasPlan = [],
    arrayVerifiCh = [],
    arrayAsiento = [],
    arrayConsumos = [],
    arraySubAntConsumidos = [];
var anticipoDetalleActual = null,
    consumoDetalleActual = null;

var perCodAc

$(function () {



    $("#successDialog").createDialog({ width: 500, height: 200, icon: 'print' });
    $("#verPagosDialog").createDialog({ width: 400, height: 290, icon: 'info-sign' });
    $("#verAsientoDialogMod").createDialog({ width: 700, height: 350, icon: 'info-data' });
    $("#verPagosDialogMod").createDialog({ width: 700, height: 450, icon: 'info-sign' });

    $('#pagosDialog').createDialog({ height: 325, icon: 'usd' });

    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });


    $('.pagination').find('li a').click(function () {
        $('.pagination').find('li').removeClass('active');
        $(this).parent().addClass('active');
        //$('#letra').val($(this).text());
         $('#letra').val($(this).data('value'));
        busquedaAjax();
    });

    $("#tabs_ant_det").tabs();
    $('#tabs_sub_ant_det').tabs();
    $("#Tia_Cod option[data-abr='IN']").prop("selected", true);

    /**
     * Metodos
     */

    changePeriodo();
    createGrid();
    createGridShowAsiDetalle();
    crearGridConsumosAnticipo();
    createGridSubAntConsumidos();
    crearGridShowPagosAsi();
    crearGridshowPagosChe();
    createPagosModGrid();
    changeCuentaCod();
    window._cliPickerTarget = 'form';
});


function busquedaAjax() {
    actualizarVisibilidadColumnaClienteKardex();
    $('#searchGrid').Search('#searchAnticipos', 'anticiposAjax');
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

function verAnticipo(params) {
    anticipoDetalleActual = (params && params[0]) ? params[0] : null;
    $("#showPagosAsi").updateGridsSizes();
    $("#showPagosChe").updateGridsSizes();
    $("#showAntConsumos").updateGridsSizes();

    $("#ant_detasi").children("a").trigger("click");
    $("#ant_detche").hide();
    $("#ant_detcons").hide();

    $('#showPagosAsi').clearGrid(true);
    $('#showPagosChe').clearGrid(true);
    $('#showAntConsumos').clearGrid(true);
    $('#showPagosAsi').trigger("reloadGrid");
    $('#showPagosChe').trigger("reloadGrid");
    $('#showAntConsumos').trigger("reloadGrid");

    $("#prov_show").val(params[0].nombre || '');
    $("#ruc_show").val(params[0].Prs_Ced || '');
    $("#compr_show").val(params[0].codigoCompra || '');
    $("#fec_show").val(params[0].Fecha_Mov || params[0].Ant_Fec || '');
    $("#obs_show").val(params[0].Glosa || params[0].Ant_Obs || '');
    $("#usuario").html((params[0].usuario || '') + (params[0].usuario ? '-' : ''));
    $("#Com_Sys").html(params[0].Com_Sys || '');

    const getDataAsiento = async () => { arrayAsiento = await asientoAsync(params[0]); };
    const getDataCheque = async () => { arrayCheques = await chequesAsync(params[0]); };
    const getDataConsumos = async () => { arrayConsumos = await consumosAnticipoAsync(params[0]); };

    $('#verPagosDialogMod').dialog('open');
    Promise.allSettled([getDataAsiento(), getDataCheque(), getDataConsumos()]).then(function (results) {
        if (results[0].status !== 'fulfilled') arrayAsiento = [];
        if (results[1].status !== 'fulfilled') arrayCheques = [];
        if (results[2].status !== 'fulfilled') arrayConsumos = [];

        $('#showPagosAsi').setRows(arrayAsiento || []);
        calSumFooter();

        if ((arrayCheques || []).length > 0) {
            $("#ant_detche").show();
            $('#showPagosChe').setRows(arrayCheques);
            calCheFooter();
            setColorGrid();
        } else $("#ant_detche").hide();

        let acumuladoConsumo = 0;
        arrayConsumos = $.map((arrayConsumos || []), function (row) {
            let valorAnticipo = parseFloat(row.valor_anticipo || row.VALOR_ANTICIPO || 0) || 0;
            let valorConsumo = parseFloat(row.valor_consumo || row.VALOR_CONSUMIDO || 0) || 0;
            acumuladoConsumo += valorConsumo;
            return $.extend({}, row, { saldo_linea: (valorAnticipo - acumuladoConsumo).toFixed(4) });
        });
        if (arrayConsumos.length > 0) {
            $("#ant_detcons").show();
            $('#showAntConsumos').setRows(arrayConsumos);
        } else $("#ant_detcons").hide();

        $('#tabs_ant_det').tabs('refresh');
        var $tabs = $('#tabs_ant_det');
        var idx = $tabs.tabs('option', 'active');
        var $activeLi = $tabs.find('.ui-tabs-nav li').eq(idx);
        if ($activeLi.length && !$activeLi.is(':visible')) $tabs.find('.ui-tabs-nav li:visible:first a').trigger('click');
    });
    $('div#verPagosDialog').siblings('.ui-dialog-titlebar').children('span').text(params[0].nombre || '');
}

function modificarAnticipo(parm_mod) {
    //console.log(parm_mod[0].Tia_Cod);
    perCodAct = 0;
    arrayModAsiento.length = 0;
    const getDataAsiMod = async () => { arrayModAsiento = await asientoAsync(parm_mod[0]); }
    getDataAsiMod().then(() => {
        $('#anticipoCliForm').setData(parm_mod[0]);
        $("#Tia_Cod_temp").val(parm_mod[0].Tia_Cod);
        $("#Tia_Cod option[value=" + parm_mod[0].Tia_Cod + "]").prop("selected", true);
        let f_Act = $('#anticipoCliForm').find('#Ant_Fec').val();
        peridodo.forEach(per => { if (f_Act > per.Pec_Fei && f_Act < per.Pec_Fef) { $("#Ant_Fec").dateLimits(per.Pec_Fei, per.Pec_Fef); } });
        moveToUpdate();
        llenarModAsient(arrayModAsiento);
        verificaChequeEstado();
        perCodAct = parm_mod[0]['Pec_Cod'];
        //console.log(perCodAct);
    });
}

function llenarModAsient(data) {
    //console.log(data);
    $('#pagos').clearGrid(true);
    $("#loader").show();
    let lengthDatos = data.length;
    var next = 0;
    let tipoData = '';
    data.reverse().forEach(respuesta => {
        //console.log(respuesta);
        if (respuesta['Asi_Deh'] === 'H') { tipoData = 'inicial'; } else { tipoData = 'pago'; }
        next = $("#pagos").jqGrid('getCol', 'index', false, 'max');
        next = (isNaN(next) ? 1 : next + 1);
        $("#pagos").jqGrid('addRowData', next, $.extend(respuesta, { index: next, grid_tipp: tipoData, Asi_Cod: respuesta['Asi_Cod'], Che_Cod: respuesta['Che_Cod'], Pac_Cod: respuesta['Pac_Cod'], Pag_Cod: respuesta['Pag_Cod'], Pag_Abr: respuesta['Pag_Abr'], Pag_Des: respuesta['Pag_Des'], Pld_Des: respuesta['Pld_Des'], Pld_Cdc: respuesta['Pld_Cdc'], Pap_Ctd: respuesta['Pap_Ctd'], Ban_Cod: respuesta['Ban_Cod'], Che_Est: respuesta['Che_Est'], Che_Num: respuesta['Che_Num'], Che_Fec: respuesta['Che_Fec'], Pap_Cto: respuesta['Pap_Cto'], Pld_Cod: respuesta['Pld_Cod'], Det_Tip: respuesta['Det_Tip'], Glosa: respuesta['Glosa'], Debe: respuesta['Debe'], Haber: respuesta['Haber'] }), 'last');
        $("#pagos").find('#' + next + '_Debe').on('change', function () {
            if ($(this).val() > 0) {
                //console.log('l1');
                let tnmGrid = $('#pagos').getGridBatch();
                reCalculateDebe(tnmGrid.length);
                $("#_Debe").attr("readonly", "");
                $("#" + next + "_Debe").css('text-align', 'right');
                formaterFotter(next);

            } else {
                $("#" + next + "_Haber").css('text-align', 'right');
                $("#" + next + "_Debe").attr("readonly", "");
            }

        }).trigger('change');

    });

    if (data.length > 0) { $("#loader").hide(); }
}

function borrarPago(row) {
    //console.log('borrar', row);
    $('#pagos').jqGrid('delRowData', row.index);
    arrayModAsiento.length = 0;
    arrayModAsiento = $('#pagos').getGridBatch();
    reCalculateDebe(arrayModAsiento.length);
    formaterFotter(row.index);
}

function changeCuentaCod() {
    $('#Ban_Cod').on('change', function () {
        clearModalPago();
    }).trigger('change');
}
/*
function cambioPreiodoSearch(parm_peri) {

  //  $("#txt_fec_ini").dateLimits($("#Pec_Cod option:selected").attr("data-inicio"), $("#Pec_Cod option:selected").attr("data-fin"));
  //  $("#txt_fec_fin").dateLimits($("#Pec_Cod option:selected").attr("data-inicio"), $("#Pec_Cod option:selected").attr("data-fin"));
    $("#txt_fec_ini").val($("#Pec_Cod option:selected").attr("data-inicio"));
    $("#txt_fec_fin").val($("#Pec_Cod option:selected").attr("data-fin"));

}*/

// function cambioPreiodoSearch(parm_peri) {
//     var selectedOption = $("#Pec_Cod option:selected");
//     var inicio = selectedOption.attr("data-inicio");
//     var fin = selectedOption.attr("data-fin");
//     var value = selectedOption.val();

//     // Si el valor seleccionado es "T" (Todos), no tocar las fechas actuales
//     if (value === "T") {
//         // Permitir que el usuario ingrese cualquier fecha sin sobrescribir
//         $("#txt_fec_ini").datepicker("option", "minDate", null);
//         $("#txt_fec_ini").datepicker("option", "maxDate", null);
//         $("#txt_fec_fin").datepicker("option", "minDate", null);
//         $("#txt_fec_fin").datepicker("option", "maxDate", null);
//     } else {
//         // Si es un período específico, limitar las fechas al rango del período
//         $("#txt_fec_ini").val(inicio);
//         $("#txt_fec_fin").val(fin);

//         // Configurar límites de fecha basados en el período
//         $("#txt_fec_ini").datepicker("option", "minDate", new Date(inicio));
//         $("#txt_fec_ini").datepicker("option", "maxDate", new Date(fin));
//         $("#txt_fec_fin").datepicker("option", "minDate", new Date(inicio));
//         $("#txt_fec_fin").datepicker("option", "maxDate", new Date(fin));
//     }
// }
// Variables para almacenar las fechas seleccionadas
let selectedStartDate = null;
let selectedEndDate = null;
function toLocalDateFromYmd(ymd) {
    var s = String(ymd || '').trim();
    var m = s.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return new Date(s);
    return new Date(parseInt(m[1], 10), parseInt(m[2], 10) - 1, parseInt(m[3], 10));
}
function cambioPreiodoSearch(parm_peri) {
    var selectedOption = $("#Pec_Cod option:selected");
    var value = selectedOption.val();
    if (value === "T") {
        const years = [];
        $("#Pec_Cod option").each(function () {
            const $o = $(this);
            const v = String($o.val() || "");
            if (v !== "T") {
                const y = parseInt($o.data("year"), 10);
                if (!isNaN(y)) years.push(y);
            }
        });
        const minYear = years.length ? Math.min.apply(null, years) : new Date().getFullYear();
        const maxYear = years.length ? Math.max.apply(null, years) : new Date().getFullYear();
        const inicio = minYear + "-01-01";
        const fin = maxYear + "-12-31";
        $("#txt_fec_ini").datepicker("setDate", toLocalDateFromYmd(inicio));
        $("#txt_fec_fin").datepicker("setDate", toLocalDateFromYmd(fin));
        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", { minDate: toLocalDateFromYmd(inicio), maxDate: toLocalDateFromYmd(fin) });
        selectedStartDate = null;
        selectedEndDate = null;
    } else {
        var year = parseInt(selectedOption.data('year'), 10);
        var inicio = !isNaN(year) ? (year + '-01-01') : selectedOption.data('inicio');
        var fin = selectedOption.data('fin');
        $("#txt_fec_ini").datepicker("setDate", toLocalDateFromYmd(inicio));
        $("#txt_fec_fin").datepicker("setDate", toLocalDateFromYmd(fin));
        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", { minDate: toLocalDateFromYmd(inicio), maxDate: toLocalDateFromYmd(fin) });
        selectedStartDate = null;
        selectedEndDate = null;
    }
}

// Evento para cambiar las fechas
$("#txt_fec_ini").change(function() {
    selectedStartDate = $(this).val(); // Actualiza la fecha de inicio seleccionada
});

$("#txt_fec_fin").change(function() {
    selectedEndDate = $(this).val(); // Actualiza la fecha de fin seleccionada
});

function verificaChequeEstado() {
    let ids = verficaDataInGrid();
    if ($.varValid(ids)) {
        for (let j = 0, z = ids.length; j < z; j++) {
            let getRowData = $('#pagos').jqGrid('getRowData', ids[j]);
            if (getRowData['grid_tipp'] !== 'inicial' && getRowData['Che_Est'] !== 'A') {
                //console.log(getRowData);
                $("#" + getRowData['index'] + "_Debe").attr("readonly", "");
            }
        }
    }
}


function reCalculateDebe(tmano) {
    let grid = $("#pagos"),
        valDebe = 0,
        inxD = 0,
        indxH = 0,
        valHaber = 0;
    let tnmGrid = grid.getGridBatch();
    if (tmano === tnmGrid.length) {
        tnmGrid.forEach(gridTam => {
            //console.log(gridTam);
            if ((gridTam['Debe'] * 1) > 0 || gridTam['grid_tipp'] === 'pago') {
                valDebe += (gridTam['Debe'] * 1);
                inxD = (gridTam['index'] * 1);
            }
            if ((gridTam['Haber'] * 1) > 0 || gridTam['grid_tipp'] === 'inicial') {
                valHaber += (gridTam['Haber'] * 1);
                indxH = (gridTam['index'] * 1);
            }
        });
        if (valDebe !== valHaber) { grid.find('#' + indxH + '_Haber').val((valDebe * 1).toFixed(4)); }
        grid.jqGrid('footerData', 'set', { Asi_Glo: "<div style='text-align:right;'>TOTALES:</div>" });
        grid.jqGrid('footerData', 'set', { Debe: "" + formatMoney((valDebe * 1).toFixed(4)) });
        grid.jqGrid('footerData', 'set', { Haber: "" + formatMoney((valDebe * 1).toFixed(4)) });
        $('#totalFinal').val('' + valDebe);
    }
}

$('#clientesDialog').createDialog({
    // icon:'search',
    width:500, 
    height:430,
    autoOpen: false,
    modal: true
});

$(function(){
    $.createSearchDialog('clientesDialog',[
        { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
        { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Cliente', name: 'nombre', width: 100 },
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', 
            formatoptions: {
                action: selectCliente
            }
        }
    ],null,null,null,{ headertitles:true },
    { title:'Cliente', text: 'searchCli' });
});

//para asignar un cliente al anticipo a modificar
function selectCliente(cliente) {
    if (window._cliPickerTarget === 'busqueda') {
        $('#busq_Cli_Cod').val(cliente.Cli_Cod || '');
        $('#busq_Prs_Ced').val(cliente.Prs_Ced || '');
        $('#busq_nombre').val(cliente.nombre || '');
        $('#busq_Prs_Dir').val(cliente.Prs_Dir || '');
        $('#clientesDialog').dialog('close');
        busquedaAjax();
        return;
    }

    $("#bandera_prov").val("sel");
    $("#Ant_Obs").val("ANTICIPO DE CLIENTE - " + cliente.nombre);
    $('#anticipoCliForm').setData($.extend(cliente, { op_opciones: 'c' }), false);

    var newGlosa = "Anticipo de cliente " + cliente.nombre;
     // Verifica que el rowId exista en el grid
    var ids = $("#pagos").jqGrid('getDataIDs');
    if (ids.length > 0) {
        var primerId = ids[0];
        var newGlosa = "Anticipo de cliente " + cliente.nombre;
        $("#pagos").jqGrid('setRowData', primerId, { Asi_Glo: newGlosa });
    }
    $('#clientesDialog').dialog('close');

}

function limpiarClienteBusqueda() {
    $('#busq_Cli_Cod').val('');
    $('#busq_Prs_Ced').val('');
    $('#busq_nombre').val('');
    $('#busq_Prs_Dir').val('');
    busquedaAjax();
}

function formaterFotter(indice) {
    $("#" + indice + "_Debe").css('text-align', 'right');
    $("#_Debe").attr("readonly", "");
    $("#_Haber").css('text-align', 'right');
    $("#_Debe").css('text-align', 'right');
}

//moverse a editar anticipos
function moveToUpdate() {
    $("#documentoSearch").moveComp("#documentoUpdate").updateGridsSizes();
}

function cambiarChek() {
    $("#impchetd td").each(function () {
        $(this).children("a").attr("href", $(this).children("a").attr("data-ruta") + "" + $("#Che_imp option:selected").attr("data-link"));
    });
}
//moverse a el principal
function moveToMain() {
    $("#documentoUpdate").moveComp("#documentoSearch").updateGridsSizes();
    $("#searchGrid").trigger("reloadGrid");
}
//permite el ingreso unicamente de numeros
function soloNumeros(e) {
    // valor = valor.replace(/[^0-9]/g,'');
    var key = window.Event ? e.which : e.keyCode
    return (key >= 48 && key <= 57)
}

function verficaDataInGrid() {
    var ids = $('#pagos').jqGrid('getDataIDs');
    return ids;
}

function limpiarFormAnticipos() {
    $("#pagos").jqGrid("clearGridData").trigger("reloadGrid");
    $('#anticipoCliForm').setData({});
}

function openDialogPagos() {
    $('#Pag_Cod').trigger('change');
    $('#pagosDialog').dialog('open');
}



function changePeriodo() {
    var yearNow = String(new Date().getFullYear());
    var $sel = $('#Pec_Cod');
    var $yearOpt = $sel.find('option').filter(function () {
        return String($(this).data('year') || '') === yearNow && String($(this).val() || '') !== 'T';
    }).first();
    if ($yearOpt.length && (!$sel.val() || $sel.val() === 'T')) {
        $sel.val(String($yearOpt.val()));
    }
    $('#Pec_Cod').off('change.antCliPeriodo').on('change.antCliPeriodo', function () {
        cambioPreiodoSearch('periodo');
        $('#txt_fec_ini').trigger('change');
        $('#txt_fec_fin').trigger('change');
    }).trigger('change');
}

function cambiarCamposPagos(tipoPago, tipoAbr) {
    //console.log(tipoPago, tipoAbr);
    $("#Pac_Ctd").val("");
    $("#Che_Num").val("");
    $("#Pac_Val").val("");
    $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
    $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
    $("#Che_Fec").dateLimits($("#Ant_Fec").val(), $("#Pec_Cod option:selected").attr("data-fin"));
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

function preanularAnticipo(params) {
    $.createDialogConfirm('¿Est&aacute; seguro que desea anular este anticipo?', null, function () {
        $.saveDataJson("", { anularAnticipo: true, data: params }, function (responce) {
            if (responce['success']) {
                $("#searchGrid").trigger("reloadGrid");
                return false;
            }
        });
    });
}

function verificarNoCheque(valor) {
    //console.log(valor);
    $('#btnGuardar').attr('disabled', 'disabled');
    let arrayVerifiCh = [];
    let banCod = $("#Bak_Cod option:selected").attr("value");
    let cheCta = $("#Che_Ctd").val();
    let cliCod = $("#Cli_Cod").val();
    //console.log(valor, banCod, cheCta, cliCod);
    if (valor * 1 > 0 && banCod * 1 > 0 && cheCta != '' && cliCod * 1 > 0) {
        const cheque = async () => {
            arrayVerifiCh.length = 0;
            arrayVerifiCh = await getCheqNum(valor, banCod, cheCta, cliCod);
        }
        if (valor > 0 && banCod > 0) {
            cheque().then(() => {
                //console.log(arrayVerifiCh.length);
                if (arrayVerifiCh.length > 0) {
                    $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
                    $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
                    $("#indicadorChe").addClass("red glyphicon glyphicon-remove");
                    $('span.validate').attr('title', 'El <u> CHEQUE No. <strong>' + valor + '</strong></u> ya se encuentra registrado');
                    $('#btnGuardar').attr('disabled', '');
                    existeCheq = true;
                } else {
                    $('span.validate').attr('title', '');
                    $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
                    $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
                    $("#indicadorChe").addClass("green glyphicon glyphicon-ok");
                    existeCheq = false;
                    if (!verifyChequeInGrid()) { $('#btnGuardar').removeAttr('disabled'); }
                }
            });
        }
    } else {
        let apuntador = document.getElementById("Che_Ctd");
        apuntador.focus();
        $.alert("Faltan datos por ingresar.");
        $("#Che_Num").val('');
    }
}

function preProtestarCheque(row) {
    //console.log(row);
    let shChq = false,
        i = 0;

    row['fechaActual'] = new Date();
    $.createDialogConfirm('¿Est&aacute; seguro que desea marcar como protestado este cheque?', null, function () {
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
                    //console.log(arrayCheques);
                    arrayCheques.forEach((data) => {
                        //console.log(data);
                        if (data['Che_Est'] === "A") {
                            i++;
                            shChq = true;
                            $("#Che_imp").append("<option value='" + i + "' data-link='?codigo2=" + data['Che_Cod'] + "&asi=" + data['Asi_Cod'] + "&ban=" + data['Ban_Cod'] + "&pro=" + data['Cli_Cod'] + "'>No.:" + data['Che_Num'] + " - Valor:" + data['Che_Val'] + "</option>");
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

function agregarFila(aux) {
    //changeValueInPago();
    var glosaDes = '',
        descp = '',
        bakCod = '',
        papCto = '';
    var tipoAbrv = $("#Pag_Cod option:selected").attr("data-abr");
    if (verficaPago()) {
        if (!verifyChequeInGrid()) {
            if (!existeCheq) {
                if (tipoAbrv === 'CHE') {
                    glosaDes = $("#Pag_Cod option:selected").text() + " NO. " + $("#Che_Num").val();
                    //console.log($("#Che_Ctd").val());
                    papCto = $("#Che_Ctd").val(); //$("#Ban_Cod option:selected").attr("data-cue");
                    //console.log(papCto);
                    //console.log($("#Che_Ctd").text());
                    descp = $("#Bak_Cod option:selected").text() + " Cta. " + $("#Che_Ctd").val();
                    bakCod = $("#Bak_Cod option:selected").val();
                } else {
                    glosaDes = 'Ant. cli. ' + $("#Pag_Cod option:selected").text();
                    descp = $("#Ban_Cod option:selected").attr("data-des");
                }
                var $this = $('#pagos');
                var campoGrid = '_Debe';
                var id = $this.nextIndex();
                $this.jqGrid('addRowData', id, { index: id, grid_tipp: 'pago', Che_Cod: '', Asi_Cod: '', Pac_Cod: '', Pag_Cod: $("#Pag_Cod").val(), Pag_Abr: $("#Pag_Cod option:selected").attr("data-abr"), Pag_Des: $("#Pag_Cod option:selected").text(), Pld_Des: descp, Pld_Cdc: $("#Ban_Cod option:selected").attr("data-cdc"), Pac_Ctd: $("#Pac_Ctd").val(), Ban_Cod: $("#Ban_Cod option:selected").attr("value"), Che_Num: $("#Che_Num").val(), Che_Fec: $("#Che_Fec").val(), Pac_Cto: papCto, Che_Cto: papCto, Bak_Cod: bakCod, Pld_Cod: $("#Ban_Cod option:selected").attr("data-pla"), Det_Tip: 'D', Glosa: glosaDes, Debe: parseFloat(($("#Pac_Val").val() * 1)).toFixed(4), Haber: '', Pag_Item: "" }, 'last');
                $this.jqGrid('editRow', id);
                $this.find('#' + id + "_Asi_Glo").val(glosaDes);
                //$this.find('#' + id + "_Haber").val(($("#Pac_Val").val() * 1).toFixed(4));
                $this.find('tr#' + id).find('#' + id + campoGrid).on('change', function () {
                    ////console.log('LOLA', $(this).val());
                    let tnmGrid = $('#pagos').getGridBatch();
                    reCalculateDebe(tnmGrid.length);
                    formaterFotter(id);
                }).trigger('change');
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
        $("#btnGuardar").unbind("click");
        $.alert("Complete todos los campos");
        //$('#btnGuardar').attr('disabled', '');
    }
}

function verFCheque() {
    var tipoAbrv = $("#Pag_Cod option:selected").attr("data-abr");
    let banCod = $("#Bak_Cod option:selected").attr("value");
    let numAct = $("#Che_Num").val();
    let cheCta = $("#Che_Ctd").val();
    let cliCod = $("#Cli_Cod").val();
    //console.log(numAct, banCod, cheCta, cliCod);
    let arrayVerifiCh = [];
    if (tipoAbrv === 'CHE') {
        const vC = async () => {
            arrayVerifiCh.length = 0;
            arrayVerifiCh = await getCheqNum(numAct, banCod, cheCta, cliCod);
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

function clearModalPago() {
    $("#Che_Num").val("");
    $("#Pac_Val").val("");
    $("#Pac_Ctd").val("");
    $("#Che_Ctd").val("");
    $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
    $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
}

function vaciarNumero() {
    $("#Che_Num").val("");
    $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
    $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
}

function changeValueInPago() {
    let campo = $('#Pac_Val').val();
    if (campo * 1 > 0) {
        if (verficaPago()) {
            verFCheque();
            $('#btnGuardar').removeAttr('disabled');
        }
    } else {
        $.alert('El Valor ingresado del Cheque debe superior a 0');
        $(this).val('');
    }
    /* $('#Pac_Val').on('change', function() {

    }).trigger('change'); */
}

function verficaPago() {
    let verifica = false;
    let tipoSelect = $("#Pag_Cod option:selected").text();
    if (tipoSelect === 'Efectivo') {
        if ($("#Pac_Val").val() !== "") {
            verifica = true;
        }
    } else if (tipoSelect === 'Deposito' || tipoSelect === "Transferencia") {
        if ($("#Pac_Val").val() !== "" && $("#Pac_Ctd").val() !== "") {
            verifica = true;
        }
    } else {
        if ($("#Pac_Val").val() !== "" && $("#Che_Num").val() !== "") {
            verifica = true;
        }
    }
    return verifica;

}


function verMovimiento(params) {
    consumoDetalleActual = (params && params[0]) ? params[0] : null;
    $("#sub_prov_show").val(params[0].nombre || '');
    $("#sub_ruc_show").val(params[0].Prs_Ced || '');
    $("#sub_compr_show").val(params[0].codigoCompra || '');
    $("#sub_fec_show").val(params[0].Fecha_Mov || params[0].Ant_Fec || '');
    $("#sub_obs_show").val(params[0].Glosa || params[0].Ant_Obs || '');
    $("#sub_usuario_show").html((params[0].usuario || '') + (params[0].usuario ? ' -' : ''));
    $("#sub_com_sys_show").html(params[0].Com_Sys || '');
    $("#sub_ant_detasi").show();
    $("#sub_ant_detcons").hide();
    $("#sub_ant_detasi").children("a").trigger("click");
    $('#showSubGridAsi').clearGrid(true);
    $('#showSubAntConsumidos').clearGrid(true);
    const getDataByDet = async () => { arrayDetAsiento = await asientoSubGridAsync(params[0]); };
    const getAntConsumidos = async () => { arraySubAntConsumidos = await anticiposConsumidosAsync(params[0]); };
    Promise.allSettled([getDataByDet(), getAntConsumidos()]).then(function (results) {
        if (results[0].status !== 'fulfilled') arrayDetAsiento = [];
        if (results[1].status !== 'fulfilled') arraySubAntConsumidos = [];
        $('#showSubGridAsi').setRows(arrayDetAsiento || []);
        calFooterSubGrid();
        if ((arraySubAntConsumidos || []).length === 0) {
            $("#sub_ant_detcons").hide();
            $('#subAntConsumoSaldoFinalVal').text(formatMoney(0));
        } else {
            $("#sub_ant_detcons").show();
            arraySubAntConsumidos = $.map(arraySubAntConsumidos, function (r) {
                return $.extend({}, r, { consumo_mov: r.valor_consumido || r.VALOR_CONSUMIDO || 0 });
            });
            $('#showSubAntConsumidos').setRows(arraySubAntConsumidos);
            calFooterSubAntConsumidos(arraySubAntConsumidos);
            resaltarAnticipoConsumidoSubGrid(params[0].Ant_Cod, dacCodDesdeRowIdConsumo(params[0]));
        }
        $('#tabs_sub_ant_det').tabs('refresh');
        var $tabsSub = $('#tabs_sub_ant_det');
        var idxSub = $tabsSub.tabs('option', 'active');
        var $activeLiSub = $tabsSub.find('.ui-tabs-nav li').eq(idxSub);
        if ($activeLiSub.length && !$activeLiSub.is(':visible')) $tabsSub.find('.ui-tabs-nav li:visible:first a').trigger('click');
    });
    $('#verAsientoDialogMod').dialog('open');
}

function imprimirAsiento(params) {
    $.saveDataJson("", { impAsiento: true, params: params }, function (responce) {
        if (responce['success']) {
            window.open(responce['link']);
            return false;
        }
    });
}

function encerarFotter() {
    $('#_Debe').val('0.00');
    $('#_Haber').val('0.00');
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

function calculateValFooter() {
    let $g = $('#searchGrid');
    let ids = $g.jqGrid('getDataIDs') || [];
    let rawRows = $g.jqGrid('getGridParam', 'data') || [];
    let rawById = {};
    for (let r = 0; r < rawRows.length; r++) {
        let rr = rawRows[r] || {};
        let rid = rr.row_id || rr.id || rr.ROW_ID;
        if (rid != null && String(rid) !== '') rawById[String(rid)] = rr;
    }
    let sumT = 0, sumC = 0, lastSaldo = 0;
    for (let i = 0; i < ids.length; i++) {
        let rowid = ids[i];
        let reg = $g.jqGrid('getRowData', rowid) || {};
        let raw = rawById[String(rowid)] || {};
        let t = parseKardexNumber(reg['TOTAL'] != null ? reg['TOTAL'] : raw['TOTAL']);
        let c = parseKardexNumber(reg['CONSUMO'] != null ? reg['CONSUMO'] : raw['CONSUMO']);
        let s = parseKardexNumber(reg['tot_anti'] != null ? reg['tot_anti'] : raw['tot_anti']);
        if (antEstKardexRow(raw) !== 'I') { sumT += t; sumC += c; }
        lastSaldo = s;
    }
    $g.jqGrid('footerData', 'set', {
        Tipo_Linea: "<div style='text-align:right;'>TOTALES:</div>",
        TOTAL: sumT.toFixed(2),
        CONSUMO: sumC.toFixed(2),
        tot_anti: lastSaldo.toFixed(2)
    }, true);
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

function cellColors() {
    let $g = $('#searchGrid');
    let ids = $g.jqGrid('getDataIDs');
    let rawRows = $g.jqGrid('getGridParam', 'data') || [];
    let rawById = {};
    for (let k = 0; k < rawRows.length; k++) {
        let rr = rawRows[k];
        let rid = rr.row_id || rr.id || rr.ROW_ID;
        if (rid) rawById[String(rid)] = rr;
    }
    for (let i = 0; i < ids.length; i++) {
        let rowid = ids[i];
        let raw = rawById[String(rowid)] || {};
        let row = $g.jqGrid('getRowData', rowid) || {};
        let tipo = row['Tipo_Linea'] || raw.Tipo_Linea || '';
        let est = antEstKardexRow(raw) || String(row['Ant_Est'] || '').trim().toUpperCase();
        let $tr = $g.find('tr.jqgrow#' + $.jgrid.jqID(String(rowid)));
        let $td = $tr.children('td').not('.jqgrid-rownum');
        $td.removeClass('cellGreen2 cellGray cellBlue2 cellRed2');
        if (tipo === 'Saldo inicial') $td.addClass('cellGray');
        else if (tipo === 'Consumo' && est === 'I') $td.addClass('cellRed2');
        else if (tipo === 'Consumo') $td.addClass('cellBlue2');
        else if (tipo === 'Anticipo') {
            if (est === 'I') $td.addClass('cellRed2');
            else if (est === 'U') $td.addClass('cellGreen2');
            else if (est === 'C') $td.addClass('cellGray');
        }
    }
}

function updateData(formulario, accion, dialogo) {
    var data = $('#' + formulario).getData('save');
    let ids = verficaDataInGrid();
    $.createDialogConfirm(`¿Est&aacute; seguro que desea guardar los datos del anticipo cliente?`, null, function () {
        if (ids.length > 1) {
            data['anticipoGrid'] = $('#pagos').getGridBatch();
            data[accion] = true;
            //console.log(data);
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
            { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } }
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
            { label: ' ', name: 'Che_Cta', hidden: true },
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: 'Asi_Cod', name: 'Asi_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pac_Cod', name: 'Pac_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pag_Cod', name: 'Pag_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pag_Abr', name: 'Pag_Abr', hidden: true, classes: 'bgNoRight' },
            { label: 'Tipo', name: 'Pag_Des', width: 10, align: "center", classes: 'bgNoRight', formatter: function (cellvalue, options, rowObject) { if (rowObject.Asi_Deh === 'H') { return "-"; } else { return rowObject.Pag_Des } } },
            { label: 'Ban_Cod', name: 'Ban_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Bak_Cod', name: 'Bak_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Che_Num', name: 'Che_Num', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Che_Est', hidden: true },
            { label: 'Che_Fec', name: 'Che_Fec', hidden: true, classes: 'bgNoRight' },
            { label: 'Pac_Cto', name: 'Pac_Cto', hidden: true, classes: 'bgNoRight' },
            { label: 'Pac_Ctd', name: 'Pac_Ctd', hidden: true, classes: 'bgNoRight' },
            { label: 'Cuenta_Pld', name: 'Pld_Cod', width: 30, hidden: true, classes: 'bgNoRight' },
            { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 10, classes: 'bgNoRight' },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, classes: 'bgNoRight' },
            { label: 'Glosa', name: 'Asi_Glo', width: 20, editable: true },
            { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'textboxExa' },
            { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'textboxExa', formatoptions: { attr: { readonly: 'readonly' } } },
            //{ label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { defaultValue: '' }, editoptions: { dataInit: function(element) { $(this).createInputDiario3(element, "D", "Det_Tip"); } } },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                name: 'Pag_Item',
                width: 10,
                align: 'center',
                viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.Asi_Deh === 'H') {
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


    },
        true,
        'pagosPager', { view: false }).gridButtonsAdd([{
            id: 'btn_mod_agr',
            caption: 'Agregar Pago',
            buttonicon: 'glyphicon glyphicon-plus',
            onClickButton: function () { /*agregarFila(1);*/ openDialogPagos(); }
        }]);

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
            { label: '', name: 'Pac_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Ant_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Ant_Val', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Com_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Tia_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pld_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Ant_Fec', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Che_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Cli_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Bak_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Asi_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'No. Che.', name: 'Che_Num', width: 15, align: "left" },
            { label: 'Fecha', name: 'Che_Fec', width: 15, align: "left" },
            { label: 'Observaci&oacute;n', name: 'Che_Obs', width: 25, align: "left" },
            {
                label: 'Valor',
                name: 'Che_Val',
                width: 15,
                align: 'right',
                formatter: 'currency',
                editable: true,
                formatoptions: {
                    prefix: '$ ',
                    thousandsSeparator: ',',
                    decimalSeparator: '.',
                    defaultValue: ''
                }
            },
            { label: '', name: 'Che_Est', hidden: true, width: 15, align: "left" },
            { label: 'Estado', name: 'estado', width: 15, align: "center" },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                name: 'btns_anti',
                width: 10,
                align: 'center',
                viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    //console.log(rowObject);
                    if (rowObject.Che_Est === 'P') {
                        return "-";
                    } else {
                        if (rowObject.Che_Est === 'A') {
                            if (rowObject.Pac_Est === 'A') {
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
            { label: '', name: 'Pac_Est', hidden: true },
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: 'Codigo', name: 'Pld_Cdc', width: 10, align: "left" },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, align: "left" },
            { label: 'Glosa', name: 'Asi_Glo', width: 25, align: "left" },
            { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } }
        ]
    }, true, '', { view: false });
}


function antEstKardexRow(reg) {
    if (!reg) return '';
    var e = reg.Ant_Est != null ? reg.Ant_Est : (reg.ant_est != null ? reg.ant_est : reg.ANT_EST);
    return String(e == null ? '' : e).trim().toUpperCase();
}

function parseKardexNumber(v) {
    if (typeof v === 'number') return isFinite(v) ? v : 0;
    var s = String(v == null ? '' : v).trim();
    if (!s) return 0;
    s = s.replace(/\s+/g, '').replace(/\$/g, '').replace(/,/g, '');
    var n = parseFloat(s);
    return isFinite(n) ? n : 0;
}

function actualizarVisibilidadColumnaClienteKardex() {
    var $g = $('#searchGrid');
    if (!$g.length || !$g.data('jqGrid')) return;
    var cliCod = String($('#busq_Cli_Cod').val() || '').trim();
    var ocultar = cliCod !== '' && cliCod !== '0';
    if (ocultar) $g.jqGrid('hideCol', 'nombre');
    else $g.jqGrid('showCol', 'nombre');
}

function getKardexRowsTodasLasPaginas() {
    var g = $('#searchGrid');
    var totalReg = parseInt(g.jqGrid('getGridParam', 'records'), 10) || 0;
    var rowsReq = Math.max(totalReg, 1000);
    var post = ($('#searchAnticipos').length ? $('#searchAnticipos').getData('anticiposAjax') : {});
    post = $.extend({}, post, { anticiposAjax: true, page: 1, rows: rowsReq });
    return new Promise(function (resolve, reject) {
        $.getDataJson("", post, function (result) { resolve((result && result.rows) ? result.rows : []); }, function (err) { reject(err); });
    });
}

function escapeKardexHtml(v) {
    return String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function renderKardexTablaCompleta(rows, g) {
    var mostrarCliente = true;
    try { mostrarCliente = !((g.jqGrid('getColProp', 'nombre') || {}).hidden === true); } catch (e) { mostrarCliente = true; }
    var html = '<table class="kardex-tabla-lista" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;table-layout:auto;font-size:12px;">';
    html += '<thead><tr><th>Tipo</th><th>Estado</th><th>Cod. Int.</th><th>No. Compr.</th><th>Fecha</th><th>Cédula</th>';
    if (mostrarCliente) html += '<th>Cliente</th>';
    html += '<th>Concepto / obs.</th><th>Anticipo</th><th>Consumo</th><th>Saldo</th></tr></thead><tbody>';
    var sumT = 0, sumC = 0, lastSaldo = 0;
    $.each(rows || [], function (_, r) {
        var est = antEstKardexRow(r);
        var t = parseKardexNumber(r.TOTAL || r.total);
        var c = parseKardexNumber(r.CONSUMO || r.consumo);
        var s = parseKardexNumber(r.tot_anti || r.TOT_ANTI);
        if (est !== 'I') { sumT += t; sumC += c; }
        lastSaldo = s;
        var rowStyle = (est === 'I') ? ' style="background:#FADDDD;"' : '';
        html += '<tr' + rowStyle + '><td>' + escapeKardexHtml(r.Tipo_Linea) + '</td><td>' + escapeKardexHtml(r.Estado || '') + '</td><td>' + escapeKardexHtml(r.Ant_Cod) + '</td><td>' + escapeKardexHtml(r.codigoCompra) + '</td><td>' + escapeKardexHtml(r.Fecha_Mov) + '</td><td>' + escapeKardexHtml(r.cedCli || r.Prs_Ced || '') + '</td>';
        if (mostrarCliente) html += '<td>' + escapeKardexHtml(r.nombre || '') + '</td>';
        html += '<td>' + escapeKardexHtml(r.Glosa || '') + '</td><td style="text-align:right;">' + formatMoney(t) + '</td><td style="text-align:right;">' + formatMoney(c) + '</td><td style="text-align:right;">' + formatMoney(s) + '</td></tr>';
    });
    var colspan = mostrarCliente ? 8 : 7;
    html += '<tr class="footrow"><td colspan="' + colspan + '" style="text-align:right;"><b>TOTALES:</b></td><td style="text-align:right;"><b>' + formatMoney(sumT) + '</b></td><td style="text-align:right;"><b>' + formatMoney(sumC) + '</b></td><td style="text-align:right;"><b>' + formatMoney(lastSaldo) + '</b></td></tr>';
    html += '</tbody></table>';
    return html;
}

function imprimirReporteKardexAnticipos() {
    var g = $('#searchGrid');
    var n = parseInt(g.jqGrid('getGridParam', 'records'), 10) || 0;
    if (n < 1) { $.alert('No hay datos en el reporte para imprimir.'); return; }
    getKardexRowsTodasLasPaginas().then(function (rows) {
        $('#tablaReporte').html(renderKardexTablaCompleta(rows, g));
        $('#imprimir').printElement({ pageTitle: 'Estado de Cuenta de Anticipo a Clientes', overrideElementCSS: [{ href: '../../mascaras/model1/estilos/print.css', media: 'print' }] });
    }).catch(function () { $.alert('No se pudo cargar todas las páginas para imprimir.'); });
}

function exportarExcelKardexAnticipos() {
    var g = $('#searchGrid');
    var n = parseInt(g.jqGrid('getGridParam', 'records'), 10) || 0;
    if (n < 1) { $.alert('No hay datos en el reporte para exportar.'); return; }
    getKardexRowsTodasLasPaginas().then(function (rows) {
        $('#tablaReporte').html(renderKardexTablaCompleta(rows, g));
        $.downloadFile($.exportarExcelBlob($('#imprimir').html(), 'Ant-Cli'), 'Ant-Cli_' + $.getDate() + '.xls');
    }).catch(function () { $.alert('No se pudo cargar todas las páginas para exportar.'); });
}

function createGrid() {
    grid.createGrid({
        caption: 'Estado de anticipos (kardex por fecha)',
        height: '300',
        url: (typeof UrlSaveJson !== 'undefined' ? UrlSaveJson : ''),
        mtype: 'GET',
        postData: ($('#searchAnticipos').length ? $('#searchAnticipos').getData('anticiposAjax') : {}),
        colModel: [
            { label: '', name: 'row_id', key: true, hidden: true },
            { label: 'Tipo', name: 'Tipo_Linea', width: 28, align: "left" },
            { label: 'Estado', name: 'Estado', width: 14, align: "center" },
            { label: 'Cod. Int.', name: 'Ant_Cod', width: 22, align: "left" },
            { label: 'No. Compr.', name: 'codigoCompra', width: 30, align: "left" },
            { label: 'Fecha', name: 'Fecha_Mov', width: 28, align: "left" },
            { label: ' ', name: 'Cli_Cod', hidden: true },
            { label: ' ', name: 'Prs_Cod', hidden: true },
            { label: ' ', name: 'cliCod', hidden: true },
            { label: ' ', name: 'Com_Cod', hidden: true },
            { label: '', name: 'Com_Cod_in', hidden: true },
            { label: '', name: 'Ant_Est', hidden: true },
            { label: '', name: 'Pag_Des', hidden: true },
            { label: '', name: 'Pac_Es2', hidden: true },
            { label: '', name: 'Ant_Fec', hidden: true },
            { label: '', name: 'usuario', hidden: true },
            { label: '', name: 'Com_Sys', hidden: true },
            { label: '', name: 'Ant_Obs', hidden: true },
            { label: 'C&eacute;dula', name: 'cedCli', width: 36, align: "left", cellattr: function () { return 'style="' + excelFormats.text + '"'; } },
            { label: '', name: 'Prs_Ced', hidden: true },
            { label: 'Cliente', name: 'nombre', width: 90, align: "left" },
            { label: 'Concepto / obs.', name: 'Glosa', width: 70, align: "left" },
            { label: 'Anticipo', name: 'TOTAL', width: 52, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Consumo', name: 'CONSUMO', width: 52, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Saldo', name: 'tot_anti', width: 52, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            {
                label: '<center><i class="ui-icon ui-icon-info"></i></center>',
                name: 'btn_det',
                width: 10,
                align: 'center',
                sortable: false,
                viewable: false,
                title: false,
                formatter: function (cellValue, options, rowObject) {
                    if ((rowObject.Tipo_Linea || '') === 'Saldo inicial') return '-';
                    return $.getGridButton(verInfoKardex, rowObject, 'Ver detalle', 'info-sign', '', 'primary');
                }
            }
        ],
        footerrow: true,
        userDataOnFooter: true,
        subGrid: false,
        rowNum: 1000,
        rowList: [1000, 1500, 2000],
        gridview: true,
        viewrecords: true,
        loadComplete: function (data) {
            actualizarVisibilidadColumnaClienteKardex();
            calculateValFooter();
            cellColors();
            verificarDescuadres();
        }
    }, false, '#searchGridPager', { refresh: false, view: false });
}

function verInfoKardex(data) {
    if (!data) return;
    if ((data.Tipo_Linea || '') === 'Anticipo') { verAnticipo([data]); return; }
    verMovimiento([data]);
}
//Seccion promesas

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

/* Promesa detalle asiento */
function getDataSubGridAsientoProm(data) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getSubGridAsient: true, Com_Cod: data['Com_Cod'] }, (result) => {
            resolve(result.dataSubAsiento);
        }, (err) => {
            reject(err);
        });
    });
}
/* Promesa asiento gridPrincipal */
function getDataAsientosProm(data) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getAsiento: true, Com_Cod: data['Com_Cod'] }, (result) => {
            resolve(result.dataASiento);
        }, (err) => {
            reject(err);
        });
    });
}
/* Promesa para obtener cheques si se ha utilizado en el anticipo */
function getDataChequesProm(data) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getCheques: true, Ant_Cod: data['Ant_Cod'], Cli_Cod: data['Cli_Cod'] }, (result) => {
            resolve(result.dataCheque);
        }, (err) => {
            reject(err);
        });
    });
}
//Promesa num cheque
function getNumCheque(valor, bakCod, cheCta, cliCod) {
    return new Promise((resolve, reject) => {
        $.getDataJson('', { verificaCheque: true, Che_Num: valor, Bak_Cod: bakCod, Che_Cta: cheCta, Cli_Cod: cliCod }, (resultado) => {
            resolve(resultado.numCheque);
        }, (err) => {
            reject(err);
        });
    });
}




//Llamar a promesas
async function asientoSubGridAsync(row) {
    let resultado = await getDataSubGridAsientoProm(row);
    return resultado;
}


async function chequesAsync(row) {
    let chq = await getDataChequesProm(row);
    return chq;
}

async function asientoAsync(row) {
    let result = await getDataAsientosProm(row);
    return result;
}
async function consumosAnticipoAsync(row) {
    return await getConsumosAnticipoProm(row);
}
async function anticiposConsumidosAsync(row) {
    return await getAnticiposConsumidosProm(row);
}
async function planCuentaWithNum(tipoAbr, pecCod) {
    let pln = await getNumCWithPlan(tipoAbr, pecCod);
    return pln;
}
async function getCheqNum(valor, bakCod, cheCta, cliCod) {
    let numChq = await getNumCheque(valor, bakCod, cheCta, cliCod);
    return numChq;
}

function getConsumosAnticipoProm(data) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getConsumosAnticipo: true, Ant_Cod: data['Ant_Cod'] }, (result) => {
            resolve(result.data || result.rows || result.getData || []);
        }, (err) => reject(err));
    });
}
function getAnticiposConsumidosProm(data) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getAnticiposConsumidos: true, Com_Cod: data['Com_Cod'] }, (result) => {
            resolve(result.data || result.rows || result.getData || []);
        }, (err) => reject(err));
    });
}

function crearGridConsumosAnticipo() {
    if (!$('#showAntConsumos').length) return;
    $('#showAntConsumos').createGrid({
        viewrecords: false, caption: "<center>Consumos aplicados al anticipo</center>",
        data: [], rowNum: 100, height: 100, width: 650, footerrow: false, responsive: false,
        onSelectRow: function () { $(this).resetSelection(); },
        colModel: [
            { label: 'No. Compr.', name: 'codigo_consumo', width: 25, align: "left" },
            { label: 'Fecha', name: 'fecha_consumo', width: 15, align: "left" },
            { label: 'Concepto / obs.', name: 'glosa_consumo', width: 40, align: "left" },
            { label: 'Valor Anticipo', name: 'valor_anticipo', width: 20, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Consumido', name: 'valor_consumo', width: 20, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Saldo', name: 'saldo_linea', width: 20, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } }
        ]
    }, true, '', { view: false });
}
function createGridSubAntConsumidos() {
    if (!$('#showSubAntConsumidos').length) return;
    $('#showSubAntConsumidos').createGrid({
        viewrecords: false, caption: "<center>Anticipos consumidos en este asiento</center>",
        data: [], rowNum: 100, height: 'auto', width: 650, footerrow: true, responsive: false,
        onSelectRow: function () { $(this).resetSelection(); },
        colModel: [
            { label: 'ID Anticipo', name: 'Ant_Cod', width: 15, align: "left" },
            { label: 'Asiento Anticipo', name: 'asiento_anticipo', width: 30, align: "left" },
            { label: 'Valor Anticipo', name: 'valor_anticipo', width: 18, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Consumo', name: 'consumo_mov', width: 18, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Saldo (momento)', name: 'saldo_momento', width: 18, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: '', name: 'Dac_Cod', hidden: true },
            { label: '', name: 'saldo_final_hoy', hidden: true }
        ]
    }, true, '', { view: false });
}
function calFooterSubAntConsumidos(dataRows) {
    let rows = $.isArray(dataRows) ? dataRows : [];
    let saldoFinalHoy = 0, totalValorAnticipo = 0, totalConsumido = 0;
    for (let i = 0; i < rows.length; i++) {
        let va = parseFloat(rows[i]['valor_anticipo'] || rows[i]['VALOR_ANTICIPO'] || 0);
        let vc = parseFloat(rows[i]['valor_consumido'] || rows[i]['VALOR_CONSUMIDO'] || 0);
        let sf = parseFloat(rows[i]['saldo_final_hoy'] || rows[i]['SALDO_FINAL_HOY'] || 0);
        if (!isFinite(va)) va = 0; if (!isFinite(vc)) vc = 0; if (!isFinite(sf)) sf = 0;
        totalValorAnticipo += va; totalConsumido += vc; saldoFinalHoy = sf;
    }
    $('#showSubAntConsumidos').jqGrid('footerData', 'set', {
        asiento_anticipo: "<div style='text-align:right;'>TOTALES:</div>",
        valor_anticipo: totalValorAnticipo.toFixed(2),
        consumo_mov: totalConsumido.toFixed(2)
    }, true);
    $('#subAntConsumoSaldoFinalVal').text(formatMoney(saldoFinalHoy));
}
function dacCodDesdeRowIdConsumo(row) {
    if (!row) return '';
    let rid = String(row.row_id || row.ROW_ID || row.rowId || '');
    let m = rid.match(/^C(\d+)$/i);
    if (m) return m[1];
    let dc = row.Dac_Cod || row.DAC_COD;
    if (dc != null && String(dc) !== '') return String(dc);
    return '';
}
function resaltarAnticipoConsumidoSubGrid(antCodSeleccionado, dacCodSeleccionado) {
    let ids = $('#showSubAntConsumidos').jqGrid('getDataIDs');
    let objetivoAnt = String(antCodSeleccionado || '');
    let objetivoDac = String(dacCodSeleccionado || '');
    for (let i = 0; i < ids.length; i++) {
        let rowId = ids[i];
        let row = $('#showSubAntConsumidos').jqGrid('getRowData', rowId) || {};
        let ant = String(row['Ant_Cod'] || '');
        let dac = String(row['Dac_Cod'] || row['DAC_COD'] || '');
        let $tds = $('#showSubAntConsumidos').find("tr#" + rowId + " td:not(.jqgrid-rownum)");
        $tds.css('background-color', '');
        let ok = (objetivoDac !== '' && dac !== '' && dac === objetivoDac) || (objetivoDac === '' && objetivoAnt !== '' && ant === objetivoAnt);
        if (ok) $tds.css('background-color', '#fff3a6');
    }
}

function imprimirAnticipoActual() {
    if (!anticipoDetalleActual) return $.alert('No hay un anticipo seleccionado para imprimir.');
    imprimirDetalleAnticipoCompleto();
}
function imprimirConsumoActual() {
    if (!consumoDetalleActual) return $.alert('No hay un consumo seleccionado para imprimir.');
    imprimirDetalleConsumoCompleto();
}
function abrirVentanaImpresionDetalle(title, html) {
    var w = window.open('', '_blank');
    if (!w) return $.alert('El navegador bloqueó la ventana de impresión.');
    w.document.write('<html><head><title>' + title + '</title></head><body style="font-family:Arial,sans-serif;padding:12px;">' + html + '</body></html>');
    w.document.close(); w.focus(); w.print();
}
async function imprimirDetalleConsumoCompleto() {
    if (!consumoDetalleActual) return;
    var row = consumoDetalleActual;
    var esc = function (v) { return String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); };
    var money = function (v) { var n = parseFloat(v || 0); if (!isFinite(n)) n = 0; return formatMoney(n); };
    var asientos = [], consumidos = [];
    try { asientos = await asientoSubGridAsync(row); } catch (e) { asientos = []; }
    try { consumidos = await anticiposConsumidosAsync(row); } catch (e) { consumidos = []; }
    var html = '<h3 style="margin:0 0 8px 0;">Detalle del consumo</h3>'
        + '<table style="width:100%;border-collapse:collapse;font-size:12px;margin-bottom:12px;">'
        + '<tr><td><b>Cliente:</b> ' + esc(row.nombre || '') + '</td><td><b>Cédula/RUC:</b> ' + esc(row.Prs_Ced || '') + '</td></tr>'
        + '<tr><td><b>No. Compr.:</b> ' + esc(row.codigoCompra || '') + '</td><td><b>Fecha:</b> ' + esc(row.Fecha_Mov || row.Ant_Fec || '') + '</td></tr>'
        + '<tr><td colspan="2"><b>Observación:</b> ' + esc(row.Glosa || row.Ant_Obs || '') + '</td></tr></table>';
    if ($.isArray(asientos) && asientos.length) {
        html += '<h4 style="margin:12px 0 6px 0;">Asiento</h4><table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="4"><tr><th>Código</th><th>Cuenta</th><th>Glosa</th><th>Debe</th><th>Haber</th></tr>';
        $.each(asientos, function (_, r) { html += '<tr><td>' + esc(r.Pld_Cdc || '') + '</td><td>' + esc(r.Pld_Des || '') + '</td><td>' + esc(r.Asi_Glo || '') + '</td><td style="text-align:right;">' + money(r.Debe) + '</td><td style="text-align:right;">' + money(r.Haber) + '</td></tr>'; });
        html += '</table>';
    }
    if ($.isArray(consumidos) && consumidos.length) {
        html += '<h4 style="margin:12px 0 6px 0;">Anticipos consumidos</h4><table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="4"><tr><th>ID Anticipo</th><th>Asiento Anticipo</th><th>Valor Anticipo</th><th>Consumo</th><th>Saldo (momento)</th><th>Saldo final (hoy)</th></tr>';
        $.each(consumidos, function (_, r) { html += '<tr><td>' + esc(r.Ant_Cod || '') + '</td><td>' + esc(r.asiento_anticipo || '') + '</td><td style="text-align:right;">' + money(r.valor_anticipo) + '</td><td style="text-align:right;">' + money(r.valor_consumido) + '</td><td style="text-align:right;">' + money(r.saldo_momento) + '</td><td style="text-align:right;">' + money(r.saldo_final_hoy) + '</td></tr>'; });
        html += '</table>';
    }
    abrirVentanaImpresionDetalle('Impresión detalle consumo', html);
}
async function imprimirDetalleAnticipoCompleto() {
    if (!anticipoDetalleActual) return;
    var row = anticipoDetalleActual;
    var esc = function (v) { return String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); };
    var money = function (v) { var n = parseFloat(v || 0); if (!isFinite(n)) n = 0; return formatMoney(n); };
    var asientos = [], cheques = [], consumos = [];
    try { asientos = await asientoAsync(row); } catch (e) { asientos = []; }
    try { cheques = await chequesAsync(row); } catch (e) { cheques = []; }
    try { consumos = await consumosAnticipoAsync(row); } catch (e) { consumos = []; }
    var html = '<h3 style="margin:0 0 8px 0;">Detalle de anticipo</h3>'
        + '<table style="width:100%;border-collapse:collapse;font-size:12px;margin-bottom:12px;">'
        + '<tr><td><b>Cliente:</b> ' + esc(row.nombre || '') + '</td><td><b>Cédula/RUC:</b> ' + esc(row.Prs_Ced || '') + '</td></tr>'
        + '<tr><td><b>No. Compr.:</b> ' + esc(row.codigoCompra || '') + '</td><td><b>Fecha:</b> ' + esc(row.Fecha_Mov || row.Ant_Fec || '') + '</td></tr>'
        + '<tr><td colspan="2"><b>Observación:</b> ' + esc(row.Glosa || row.Ant_Obs || '') + '</td></tr></table>';
    if ($.isArray(asientos) && asientos.length) {
        html += '<h4 style="margin:12px 0 6px 0;">Asientos</h4><table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="4"><tr><th>Código</th><th>Cuenta</th><th>Glosa</th><th>Debe</th><th>Haber</th></tr>';
        $.each(asientos, function (_, r) { html += '<tr><td>' + esc(r.Pld_Cdc || '') + '</td><td>' + esc(r.Pld_Des || '') + '</td><td>' + esc(r.Asi_Glo || '') + '</td><td style="text-align:right;">' + money(r.Debe) + '</td><td style="text-align:right;">' + money(r.Haber) + '</td></tr>'; });
        html += '</table>';
    }
    if ($.isArray(consumos) && consumos.length) {
        html += '<h4 style="margin:12px 0 6px 0;">Consumos</h4><table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="4"><tr><th>No. Compr.</th><th>Fecha</th><th>Concepto</th><th>Valor Anticipo</th><th>Consumido</th><th>Saldo</th></tr>';
        $.each(consumos, function (_, r) { html += '<tr><td>' + esc(r.codigo_consumo || '') + '</td><td>' + esc(r.fecha_consumo || '') + '</td><td>' + esc(r.glosa_consumo || '') + '</td><td style="text-align:right;">' + money(r.valor_anticipo) + '</td><td style="text-align:right;">' + money(r.valor_consumo) + '</td><td style="text-align:right;">' + money(r.saldo_anticipo || r.saldo_linea) + '</td></tr>'; });
        html += '</table>';
    }
    if ($.isArray(cheques) && cheques.length) {
        html += '<h4 style="margin:12px 0 6px 0;">Cheques</h4><table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="4"><tr><th>No. Cheque</th><th>Fecha</th><th>Observación</th><th>Valor</th><th>Estado</th></tr>';
        $.each(cheques, function (_, r) { html += '<tr><td>' + esc(r.Che_Num || '') + '</td><td>' + esc(r.Che_Fec || '') + '</td><td>' + esc(r.Che_Obs || '') + '</td><td style="text-align:right;">' + money(r.Che_Val) + '</td><td>' + esc(r.estado || '') + '</td></tr>'; });
        html += '</table>';
    }
    abrirVentanaImpresionDetalle('Impresión detalle anticipo', html);
}

/* =========================================================
 *  DESCUADRE BANNER + MODAL
 * ========================================================= */

/**
 * Consulta el contador de anticipos con descuadre y actualiza el banner.
 * Se llama después de que el searchGrid carga datos.
 */
function verificarDescuadres() {
    if (!$('#bannerDescuadre').length || !$('#bannerDescuadreTxt').length) {
        return;
    }
    $.getDataJson('', { getDescuadreCount: true }, function(res) {
        if (res && res.success) {
            var cnt = res.count || 0;
            if (cnt > 0) {
                $('#bannerDescuadreTxt').text(
                    '\u26a0 ' + cnt + ' anticipo' + (cnt === 1 ? '' : 's') +
                    ' con descuadre: consumo total en det_ant_cccc difiere del consumo con comprobantes activos.'
                );
                $('#bannerDescuadre').slideDown(300);
            } else {
                $('#bannerDescuadre').slideUp(300);
            }
        }
    }, function() { /* silencioso en error */ });
}

/**
 * Abre el modal con la tabla de anticipos en descuadre.
 */
function abrirModalDescuadre() {
    if (!$('#descuadreDialog').length || !$('#gridDescuadre').length) {
        return;
    }
    $('#descuadreDialog').dialog('open');
    $.getDataJson('', { getDescuadreList: true }, function(res) {
        if (res && res.success) {
            if (res.truncated) {
                $('#descuadreTruncMsg').show();
            } else {
                $('#descuadreTruncMsg').hide();
            }
            $('#gridDescuadre').setRows(res.data || []);
        }
    });
}

/* Inicializar el modal de descuadre y su grid */
$(function() {
    if (!$('#descuadreDialog').length || !$('#gridDescuadre').length) {
        return;
    }
    $('#descuadreDialog').createDialog({
        width: 900,
        height: 480,
        autoOpen: false,
        modal: true,
        icon: 'warning-sign'
    });

    $('#gridDescuadre').createGrid({
        caption: '<center>Anticipos con Descuadre</center>',
        data: [],
        rowNum: 500,
        height: 300,
        responsive: true,
        colModel: [
            { label: 'Ant_Cod',   name: 'Ant_Cod',    width: 20, align: 'center', key: true },
            { label: 'Manif.',    name: 'Ama_Cod',    width: 20, align: 'center',
              formatter: function(v) { return v ? $('<span>').text(v).html() : '-'; } },
            { label: 'Cliente',   name: 'nombre',     width: 90, align: 'left',
              formatter: function(v) { return $('<span>').text(v).html(); } },
            { label: 'Cédula',    name: 'Prs_Ced',    width: 40, align: 'left' },
            { label: 'Ddc Total', name: 'ddc_total',  width: 30, align: 'right',
              formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Ddc Activo',name: 'ddc_activo', width: 30, align: 'right',
              formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Diferencia',name: 'diferencia', width: 30, align: 'right',
              formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } }
        ]
    }, true, '', { view: false });
});

/* =========================================================
 *  SUBGRID MOVIMIENTO: TAB "Detalle anticipos"
 * ========================================================= */

/* Grid para det_ant_cccc por Com_Cod */
$(function() {
    if (!$('#showSubGridDdc').length) {
        return;
    }
    $('#showSubGridDdc').createGrid({
        caption: '<center>Líneas det_ant_cccc para este comprobante</center>',
        data: [],
        rowNum: 200,
        height: 'auto',
        responsive: true,
        colModel: [
            { label: 'Ddc_Cod',       name: 'Ddc_Cod',             width: 15, align: 'center', key: true, hidden: true },
            { label: 'Ant_Cod',       name: 'Ant_Cod',             width: 20, align: 'center' },
            { label: 'Anticipo',      name: 'codigo_anticipo',     width: 45, align: 'left' },
            { label: 'Cliente',       name: 'cliente_nombre',      width: 80, align: 'left',
              formatter: function(v) { return $('<span>').text(v).html(); } },
            { label: 'Cédula',        name: 'Prs_Ced',             width: 35, align: 'left' },
            { label: 'Valor',         name: 'Ddc_Val',             width: 25, align: 'right',
              formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Obs.',          name: 'Ddc_Obs',             width: 60, align: 'left' }
        ]
    }, true, '', { view: false });
});

/**
 * Carga det_ant_cccc para el Com_Cod del movimiento subgrid y actualiza el tab.
 * @param {object} rowData - fila del subgrid que contiene Com_Cod y Ant_Cod
 */
function cargarDetAntCcc(rowData) {
    if (!$('#showSubGridDdc').length) {
        return;
    }
    var comCod = rowData['Com_Cod'] || 0;
    var antCod = rowData['Ant_Cod'] || 0;    // El anticipo principal (tipo IN)

    $('#showSubGridDdc').clearGrid(true);
    $('#sub_ddc_msg').hide();

    if (!comCod || comCod == 0) {
        $('#sub_ddc_msg')
            .text('Este comprobante no tiene Com_Cod definido.')
            .show();
        return;
    }

    $.getDataJson('', { getDetAntCccxCom: true, Com_Cod: comCod }, function(res) {
        if (res && res.success) {
            if (!res.data || res.data.length === 0) {
                // Verificar si es un comprobante IN (anticipo de ingreso)
                var tiaAbr = (rowData['codigoCompra'] || rowData['codigo_compra'] || '').substring(0, 2);
                if (tiaAbr === 'IN' || antCod == (rowData['Ant_Cod'])) {
                    $('#sub_ddc_msg')
                        .html('<span class="glyphicon glyphicon-info-sign"></span> ' +
                              'Este comprobante es el <strong>ingreso del anticipo (IN)</strong>. ' +
                              'Los comprobantes de tipo IN no registran aplicaciones en <code>det_ant_cccc</code>. ' +
                              'Las aplicaciones aparecen en los comprobantes de cobranza que consumen este anticipo.')
                        .show();
                } else {
                    $('#sub_ddc_msg')
                        .text('No existen líneas en det_ant_cccc para este comprobante.')
                        .show();
                }
                $('#showSubGridDdc').clearGrid(true);
            } else {
                $('#sub_ddc_msg').hide();
                $('#showSubGridDdc').setRows(res.data);
            }
        }
    });
}

/* Sobrescribir verMovimiento para cargar también el tab de det_ant_cccc */
var _verMovimientoOrig = verMovimiento;
verMovimiento = function(params) {
    _verMovimientoOrig(params);
    // Cargar el tab de detalle en det_ant_cccc para este comprobante de movimiento (NO del anticipo IN)
    cargarDetAntCcc(params[0]);
    // Activar primer tab
    $('#tabs_sub_ant_det').tabs('option', 'active', 0);
};
