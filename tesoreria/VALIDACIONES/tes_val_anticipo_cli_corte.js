var grid = $('#searchGrid');
var arrayDetAsiento = [],
    arrayCheques = [],
    arrayModAsiento = [],
    arrayCuentasPlan = [],
    arrayVerifiCh = [],
    arrayAsiento = [];

var perCodAct = 0,
    existeCheq = false;

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
        $('#letra').val($(this).text());
        busquedaAjax();
    });

    $("#tabs_ant_det").tabs();
    $('#tabs_sub_ant_det').tabs();
    $("#Tia_Cod option[data-abr='IN']").prop("selected", true);

    /* Metodos */
    changePeriodo();
    createGrid();
    createGridShowAsiDetalle();
    crearGridShowPagosAsi();
    crearGridshowPagosChe();
    createPagosModGrid();
    changeCuentaCod();
});


function busquedaAjax() {
    //anticiposAjax
    //searchGrid
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
    //console.log(params);

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
    $("#fec_show").val(params[0].Ant_Fec);
    $("#obs_show").val(params[0].Ant_Obs);
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
function cambioPreiodoSearch(parm_peri) {
    var selectedOption = $("#Pec_Cod option:selected");
    var value = selectedOption.val();

    if (value === "T") {
        // Caso "Todos" - Usar fechas del periodo máximo
        const inicio = selectedOption.data("inicio");
        const fin = selectedOption.data("fin");
        
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
    } else if (value === "Corte") {
        // $("#txt_fec_ini").prop("disabled", true);
        // Extraer años solo de los periodos reales (excluyendo 'Todos' y 'Corte')
        const years = [];
        $("#Pec_Cod option").each(function() {
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
    $('#Pec_Cod').on('change', function () {
        var sel_fecha = $(this).find('option:selected');
        //console.log(sel_fecha.data('inicio'));
        //console.log(sel_fecha.data('fin'));
        $("#txt_fec_ini").val(sel_fecha.data('inicio'));
        $("#txt_fec_fin").val(sel_fecha.data('fin'));
        $('#txt_fec_ini').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
        $('#txt_fec_fin').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
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
                    papCto = $("#Che_Ctd").val();
                    //$("#Ban_Cod option:selected").attr("data-cue");
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
    for (let j = 0; j < ids.length; j++) {
        let reg_pagoC = $('#showPagosChe').jqGrid('getRowData', ids[j]);
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
        valorDebe = valorDebe + (reg_pago['Debe'] * 1);
        valorHaber = valorHaber + (reg_pago['Haber'] * 1);
    }
    $('#showPagosAsi').jqGrid('footerData', 'set', { Asi_Glo: "<div style='text-align:right;'>TOTALES:</div>" });
    $('#showPagosAsi').jqGrid('footerData', 'set', { Debe: "" + valorDebe });
    $('#showPagosAsi').jqGrid('footerData', 'set', { Haber: "" + valorHaber });
}

function calculateValFooter() {
    let ids = $('#searchGrid').jqGrid('getDataIDs');
    var valorDet = 0,
        valorAtp = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#searchGrid').jqGrid('getRowData', ids[i]);
        var detVal = (reg_pago['sumaDdcVal'].replace(/[^0-9-.]/g, '') * 1);
        valorDet = valorDet + (detVal * 1);
        valorAtp = valorAtp + (reg_pago['sumaAntVal'] * 1);
    }
    $('#searchGrid').jqGrid('footerData', 'set', { Prs_Dir: "<div style='text-align:right;'>TOTALES:</div>", tot_anti: $('#searchGrid').jqGrid('getCol', 'tot_anti', false, 'sum') });
    $('#searchGrid').jqGrid('footerData', 'set', { sumaDdcVal: "" + valorDet });
    $('#searchGrid').jqGrid('footerData', 'set', { sumaAntVal: "" + valorAtp });

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
    let data = $('#searchGrid').jqGrid('getDataIDs');
    if ($.varValid(data)) {
        for (let i = 0, z = data.length; i < z; i++) {
            let getRowData = $('#searchGrid').jqGrid('getRowData', data[i]);
            console.log(getRowData['Ant_Est']);
            if (getRowData['Ant_Est'] === 'U') {
                $("tr#" + data[i] + ' td:not(.jqgrid-rownum)').addClass('cellGreen2');
            }
            if (getRowData['Ant_Est'] === 'C') {
                $("tr#" + data[i] + ' td:not(.jqgrid-rownum)').addClass('cellGray');
            }
            if (getRowData['Ant_Est'] === 'I') {
                $("tr#" + data[i] + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
            }
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
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'Pag_Item', width: 10, align: 'center', viewable: false,
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
            { label: 'Valor', name: 'Che_Val', width: 15, align: 'right', formatter: 'currency',
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

function createGrid() {
    grid.createGrid({
        caption: 'Anticipos',
        height: '450',
        colModel: [
            { label: 'Cod. Int.', name: 'Ant_Cod', key: true, width: 25, align: "center" },
            { label: 'No. Compr.', name: 'codigoCompra', width: 35, align: "center" },
            { label: 'Fecha Ant.', name: 'Ant_Fec', width: 35, align: "center" },
            { label: ' ', name: 'Prs_Cod', hidden: true },
            { label: ' ', name: 'Pac_Cod', hidden: true },
            { label: ' ', name: 'Cli_Cod', hidden: true },
            { label: ' ', name: 'Com_Cod', hidden: true },
            { label: '', name: 'Ant_Est', hidden: true, },
            { label: 'C&eacute;dula', name: 'cedProv', width: 50, align: "center",
                cellattr: function () {
                    return 'style="' + excelFormats.text + '"';
                }
            },
            { label: 'Nombre', name: 'nombre', width: 100, align: "left" },
            { label: 'Direci&oacute;n', name: 'Prs_Dir', width: 100, align: "left" },
            { label: 'Valor', name: 'sumaAntVal', width: 60, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }
            },
            { label: 'Pagos', name: 'sumaDdcVal', width: 60, align: 'right',
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject['sumaDdcVal'] === '' || rowObject['sumaDdcVal'] === null) {
                        return "0.00";
                    } else {
                        return formatMoney(rowObject['sumaDdcVal']);
                    }
                }
            },
            { label: 'Saldo', name: 'tot_anti', width: 60, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' }, summaryType: "sum"
            },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_anti', width: 40, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    var parm_anu = [rowObject];
                    var parm_getdet = [rowObject];

                    if (rowObject.Ant_Est !== "A") {
                        return $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info');
                    } else {
                        if (rowObject.sumaDdcVal.toNum() === 0/*prf[0]['Per_Des'] === 'Administrador de Sistemas'*/) {
                            return $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info');
                        }
                    }
                }
            }
        ],
        footerrow: true,
        userDataOnFooter: true,
        subGrid: true,
        rowNum: 10000,
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
                url: "?movAnticipo=" + rowData['Ant_Cod'] + "&Pec_Cod=" + $("#Pec_Cod").val() + "&txt_fec_ini=" + $("#txt_fec_ini").val() + "&txt_fec_fin=" + $("#txt_fec_fin").val(),
                datatype: "json",
                regional: 'es',
                height: 'auto',
                responsive: true,
                colModel: [
                    { label: '', name: 'Ant_Cod', key: true, hidden: true },
                    { label: '', name: 'Com_Cod', hidden: true },
                    { label: '', name: 'Ant_Est', hidden: true },
                    { label: '', name: 'Tia_Cod', hidden: true },
                    { label: '', name: 'Com_Num', hidden: true },
                    { label: 'No. Compr.', name: 'codigoCompra', width: 30, align: "left" },
                    { label: 'Fecha Compr.', name: 'Com_Fec', width: 35, align: "center" },
                    { label: 'Fecha Ant.', name: 'Ant_Fec', width: 35, align: "center" },
                    { label: 'Observaci&oacute;n', name: 'Ant_Obs', width: 90, align: "left" },
                    { label: 'Concepto', name: 'Com_Con', width: 50, align: "left" },
                    { label: 'Valor', name: 'sumaDacVal', width: 50, align: 'right',
                        formatter: function (cellvalue, options, rowObject) {
                            return formatMoney(rowObject['Ddc_Val']);
                        }
                    },
                    { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_sub_anti', width: 40, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            var parm_getdet = [rowObject];
                            return $.getGridButton(verMovimiento, parm_getdet, 'ver asiento', 'info-sign', '', 'info') + "&nbsp;" +
                                $.getGridButton(imprimirAsiento, parm_getdet, 'Imprimir', 'print', '', 'success');
                        }
                    }
                ]
            });
        },
        loadComplete: function (data) {
            calculateValFooter();
            cellColors();
        }

    }, true, '#searchGridPager', { refresh: true, view: false }
    ) .gridButtonsAdd([
        { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', 
            onClickButton: function () {
                grid.jqGrid('exportGridExcel', {
                    nombre: 'Ant-Cli',
                    hoja: 'HOJA 1',
                    footer: true
                });
            }
        },
        { caption: 'Exportar PDF', buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                imprimir();
            }
        },
        { caption: 'Expandir Todos', buttonicon: 'glyphicon glyphicon-resize-full',
            onClickButton: function () {
                ExpandirAll();
            }
        },
        { caption: 'Contraer Todos', buttonicon: 'glyphicon glyphicon-resize-small',
            onClickButton: function () {
                ContraerAll();
            }, hidden:true 
        }
    ]);

    function ExpandirAll(){
        let ids = grid.getDataIDs();
        for (let i = 0; i < ids.length; i++) {
            grid.expandSubGridRow(ids[i]);
        }
    
    }
    
    function ContraerAll(){
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
            removeCols: [1, 11]
        }));
        $('#imprimir').printElement();
    }
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
async function planCuentaWithNum(tipoAbr, pecCod) {
    let pln = await getNumCWithPlan(tipoAbr, pecCod);
    return pln;
}
async function getCheqNum(valor, bakCod, cheCta, cliCod) {
    let numChq = await getNumCheque(valor, bakCod, cheCta, cliCod);
    return numChq;
}