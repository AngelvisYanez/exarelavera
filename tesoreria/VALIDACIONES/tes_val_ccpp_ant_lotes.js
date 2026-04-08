let ids_pagos = 0;
let numRangos = 4; // Número inicial de rangos

// Función para obtener el texto del rango desde los inputs
function obtenerTextoRango(numRango) {
    var rangoIni = $("#rango" + numRango + "Ini").val();
    var rangoFin = $("#rango" + numRango + "Fin").val();
    if (rangoIni && rangoFin) {
        return rangoIni + "-" + rangoFin + " d&iacute;as";
    }
    return "Rango " + numRango;
}

// Inicializar rangos al cargar la página
$(document).ready(function() {
    inicializarRangos();
    
    // inicializa componentes de fecha en formulario de anticipos
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });

    if ($('#searchGrid_mod').length === 1) {
        if ($('#clientesDialog').length === 1)
            $.createSearchDialog('clientesDialog', [
                { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
                { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Cliente', name: 'nombre', width: 100 },
                { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCliente_mod } }
            ], null, null, null, { headertitles: true }, { title: 'Cliente', text: 'searchCli' });

        cargarFaturasClientesMod();
    }
});

// Calcular rangos siguientes basándose en el rango actual
function calcularRangosSiguientes(rangoActual) {
    var rangoFin = parseInt($("#rango" + rangoActual + "Fin").val());
    
    if (!rangoFin || isNaN(rangoFin)) {
        return;
    }
    
    // Calcular inicio del siguiente rango
    var siguienteRango = rangoActual + 1;
    if ($("#rango" + siguienteRango + "Ini").length) {
        $("#rango" + siguienteRango + "Ini").val(rangoFin + 1);
        
        // Si el siguiente rango tiene fin, actualizar también el siguiente
        if ($("#rango" + siguienteRango + "Fin").val()) {
            calcularRangosSiguientes(siguienteRango);
        }
    }
}

// Inicializar rangos por defecto (4 rangos)
function inicializarRangos() {
    var container = $("#rangosContainer");
    container.empty();
    
    // Crear 4 rangos por defecto
    for (var i = 1; i <= 4; i++) {
        agregarRangoHtml(i);
    }
    
    // Establecer valores por defecto
    $("#rango1Ini").val(1);
    $("#rango1Fin").val(30).trigger('change'); // Trigger para calcular siguientes
    
    numRangos = 4;
    
    // Calcular todos los rangos siguientes
    setTimeout(function() {
        if (!$("#rango2Fin").val()) $("#rango2Fin").val(60).trigger('change');
        if (!$("#rango3Fin").val()) $("#rango3Fin").val(90).trigger('change');
        if (!$("#rango4Fin").val()) $("#rango4Fin").val(120);
        actualizarNumRangos();
    }, 100);
}

// Agregar HTML de un rango
function agregarRangoHtml(numRango) {
    var container = $("#rangosContainer");
    var esPrimerRango = (numRango === 1);
    var readonlyAttr = esPrimerRango ? 'value="1" readonly' : 'readonly';
    
    var html = '<div class="form-group" id="rangoGroup' + numRango + '">' +
               '<label class="col-xs-2 control-label label-xs">Rango ' + numRango + ':</label>' +
               '<div class="col-xs-2">' +
               '<input type="number" class="col-xs-2 input-xs form-control rango-ini" id="rango' + numRango + 'Ini" name="rango' + numRango + 'Ini" min="1" placeholder="Inicio" ' + readonlyAttr + ' />' +
               '</div>' +
               '<div class="col-xs-2">' +
               '<input type="number" class="col-xs-2 input-xs form-control rango-fin" id="rango' + numRango + 'Fin" name="rango' + numRango + 'Fin" min="2" placeholder="Fin" />' +
               '</div>' +
               '</div>';
    container.append(html);
    
    // Actualizar campo oculto con número de rangos
    actualizarNumRangos();
    
    // Eventos para calcular rangos siguientes y actualizar títulos
    $("#rango" + numRango + "Fin").on('input change', function() {
        calcularRangosSiguientes(numRango);
        actualizarTitulosColumnas();
        // Solo actualizar títulos, no reconstruir todo el grid para mejor performance
        // El grid se reconstruirá cuando se haga la búsqueda
    });
}

// Actualizar campo oculto con número de rangos
function actualizarNumRangos() {
    // Buscar o crear campo oculto para número de rangos
    var numRangosInput = $("#numRangos");
    if (numRangosInput.length === 0) {
        $("#searchCccc").append('<input type="hidden" id="numRangos" name="numRangos" />');
    }
    $("#numRangos").val(numRangos);
}

// Agregar un nuevo rango
function agregarRango() {
    // Calcular el inicio del nuevo rango basándose en el último
    var ultimoFin = parseInt($("#rango" + numRangos + "Fin").val());
    if (!ultimoFin || isNaN(ultimoFin)) {
        alert("Por favor complete el rango anterior antes de agregar uno nuevo");
        return;
    }
    
    numRangos++;
    agregarRangoHtml(numRangos);
    
    // Establecer el inicio del nuevo rango automáticamente
    $("#rango" + numRangos + "Ini").val(ultimoFin + 1);
    
    // Siempre reconstruir el grid cuando se agrega un rango
    reconstruirGrid();
}

// Eliminar el último rango
function eliminarUltimoRango() {
    if (numRangos > 1) {
        $("#rangoGroup" + numRangos).remove();
        numRangos--;
        
        // Siempre reconstruir el grid cuando se elimina un rango
        reconstruirGrid();
    } else {
        alert("Debe haber al menos un rango");
    }
}

// Función para actualizar los títulos de las columnas del grid
function actualizarTitulosColumnas() {
    if ($("#searchGrid_mod").jqGrid('getGridParam')) {
        for (var i = 1; i <= numRangos; i++) {
            var label = obtenerTextoRango(i);
            $("#searchGrid_mod").jqGrid('setLabel', 'Rango' + i, label);
        }
        
        // Actualizar último rango
        var ultimoFin = $("#rango" + numRangos + "Fin").val();
        if (ultimoFin) {
            $("#searchGrid_mod").jqGrid('setLabel', 'RangoUltimo', "> " + ultimoFin + " d&iacute;as");
        }
    }
}

// Reconstruir el grid con las columnas actuales
function reconstruirGrid() {
    // Verificar si el grid existe antes de destruirlo
    var gridExists = false;
    try {
        var gridParam = $("#searchGrid_mod").jqGrid('getGridParam');
        if (gridParam && gridParam !== false) {
            gridExists = true;
        }
    } catch(e) {
        gridExists = false;
    }
    
    // Destruir el grid completamente
    if (gridExists) {
        try {
            // Intentar usar GridUnload
            $.jgrid.gridUnload("#searchGrid_mod");
        } catch(e1) {
            try {
                // Si falla, intentar con GridDestroy
                $.jgrid.gridDestroy("#searchGrid_mod");
            } catch(e2) {
                // Si todo falla, eliminar manualmente
                $("#gbox_searchGrid_mod").remove();
            }
        }
    }
    
    // Limpiar el contenedor y el pager
    $("#searchGrid_mod").empty();
    $("#sgPager").empty();
    
    // Esperar un momento antes de recrear para asegurar que se destruyó completamente
    setTimeout(function() {
        cargarFaturasClientesMod();
    }, 200);
}

// Generar columnas dinámicamente según el número de rangos
function generarColumnas() {
    var columnas = [
        { label: 'Proveedor', name: 'Proveedor', width: 120 },
        { label: 'No. Compr.', name: 'Com_Codigo', align: "center", width: 25, hidden: true },
        {
            label: 'Por Vencer',
            name: 'PorVencer',
            width: 30,
            align: 'right',
            formatter: 'currency',
            decimalPlaces: '2',
            summaryRound: 2,
            formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' },
            summaryTpl: "Total: {0}",
            summaryType: "sum"
        }
    ];
    
    // Agregar columnas de rangos
    for (var i = 1; i <= numRangos; i++) {
        columnas.push({
            label: obtenerTextoRango(i),
            name: 'Rango' + i,
            width: 25,
            align: 'right',
            formatter: 'currency',
            decimalPlaces: '2',
            summaryRound: 2,
            formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' },
            summaryTpl: "{0}",
            summaryType: "sum"
        });
    }
    
    // Agregar columna de último rango
    var ultimoFin = $("#rango" + numRangos + "Fin").val();
    columnas.push({
        label: (ultimoFin ? "> " + ultimoFin + " d&iacute;as" : "> Rango Final"),
        name: 'RangoUltimo',
        width: 25,
        align: 'right',
        formatter: 'currency',
        decimalPlaces: '2',
        summaryRound: 2,
        formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' },
        summaryTpl: "{0}",
        summaryType: "sum"
    });
    
    // Columna de total
    columnas.push({
        label: 'Total',
        name: 'Saldo',
        width: 30,
        align: 'right',
        formatter: 'currency',
        decimalPlaces: '2',
        summaryRound: 2,
        formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' },
        summaryTpl: "Total: {0}",
        summaryType: "sum"
    });
    
    columnas.push({ name: "Prv_Cod", hidden: true });
    
    return columnas;
}

function cargarFaturasClientesMod() {
    $("#searchGrid_mod").createGrid({
        postData: $("#searchCccc").getData("ajaxComprobante"),
        height: 300,
        mtype: "GET",
        datatype: "local",
        regional: 'es',
        shrinkToFit: true,
        colModel: generarColumnas(),
        subGrid: true,
        multiselect: false,
        subGridRowExpanded: function(subgrid_id, row_id) {
            let subgrid_table_id = subgrid_id + "_t";
            let ccc_data = $('#searchGrid_mod').jqGrid('getRowData', row_id);
            $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
            $("#" + subgrid_table_id).createGrid({
                url: "?abonosDetAjax=" + ccc_data.Prv_Cod,
                datatype: "json",
                regional: 'es',
                height: 'auto',
                colModel: [
                    { label: '', name: 'indx', key: true, hidden: true },
                    { label: 'No. Compra', name: 'Cop_Num', width: 30, align: "left" },
                    { label: 'No. Compr.', name: 'Com_Codigo', width: 30, align: "left" },
                    { label: 'Fecha', name: 'Fecha', width: 30, align: "left" },
                    { label: 'Fecha Venc.', name: 'FechaVenc', width: 30, align: "left" },
                    { label: 'Vencimiento', name: 'vencimiento', width: 30, align: "left" },
                    {
                        label: 'Total',
                        name: 'Total',
                        width: 50,
                        align: 'right',
                        formatter: 'currency',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.',
                            defaultValue: ''
                        }
                    },
                    {
                        label: 'Abono',
                        name: 'Abono',
                        width: 50,
                        align: 'right',
                        formatter: 'currency',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.',
                            defaultValue: ''
                        }
                    },
                    {
                        label: 'Saldo',
                        name: 'Saldo',
                        width: 50,
                        align: 'right',
                        formatter: 'currency',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.',
                            defaultValue: ''
                        }
                    }
                ],
                loadComplete: function() {
                    //
                },
                beforeSelectRow: function(rowid, e) { return false; },
                rowNum: 10000,
                pager: ""
            });
        },
        loadComplete: function(data) {
            actualizarTotalesSGmod();
            actualizarTitulosColumnas(); // Actualizar títulos después de cargar
            $('#searchGrid_mod tr').each(function() {
                if ($(this).find("td").eq(10).text() === "Vencido") {
                    $(this).addClass("cellRed2");
                    $(this).addClass("myAltRowClass");
                }
                if ($(this).find("td").eq(10).text() === "Pagado") {
                    $(this).css("background", "#DDFAE2");
                    $(this).addClass("myAltRowClass");
                }
            });

        },
        pager: "#sgPager",
        rownumbers: true,
        rowNum: 10000,
        gridview: true,
        viewrecords: true,
        footerrow: true,
        userDataOnFooter: false,
        onSelectRow: function(rowid, e) { $("#searchGrid_mod").resetSelection(); },
        multiselect: false
    }, false, "#sgPager", { view: false });
    $("#searchGrid_mod").updateGridsSizes();
    $("#pagosGrid").updateGridsSizes();
}

function actualizarTotalesSGmod() {
    let ids = $('#searchGrid_mod').jqGrid('getDataIDs');
    let abonos = 0,
        porvencer = 0,
        saldos = 0,
        tot = 0;
    let rangos = {};
    
    // Inicializar rangos
    for (var i = 1; i <= numRangos; i++) {
        rangos['Rango' + i] = 0;
    }
    rangos['RangoUltimo'] = 0;

    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#searchGrid_mod').jqGrid('getRowData', ids[i]);
        tot = tot + parseFloat(reg_pago.Asi_Val || 0);
        abonos = abonos + parseFloat(reg_pago.Abono || 0);
        porvencer = porvencer + parseFloat(reg_pago.PorVencer || 0);
        
        // Sumar todos los rangos
        for (var j = 1; j <= numRangos; j++) {
            rangos['Rango' + j] += parseFloat(reg_pago['Rango' + j] || 0);
        }
        rangos['RangoUltimo'] += parseFloat(reg_pago['RangoUltimo'] || 0);
        
        if ($.isNumeric(reg_pago.Saldo)) {
            saldos = saldos + parseFloat(reg_pago.Saldo);
        }
    }

    $('#searchGrid_mod').jqGrid('footerData', 'set', { Cliente: "TOTALES:", Asi_Val: "" + tot });
    $('#searchGrid_mod').jqGrid('footerData', 'set', { Abono: "" + abonos });
    $('#searchGrid_mod').jqGrid('footerData', 'set', { Saldo: "" + saldos });
    $('#searchGrid_mod').jqGrid('footerData', 'set', { PorVencer: "" + porvencer });
    
    // Establecer totales de rangos
    for (var k = 1; k <= numRangos; k++) {
        $('#searchGrid_mod').jqGrid('footerData', 'set', { ['Rango' + k]: "" + rangos['Rango' + k] });
    }
    $('#searchGrid_mod').jqGrid('footerData', 'set', { RangoUltimo: "" + rangos['RangoUltimo'] });
}

// Variable para prevenir múltiples llamadas simultáneas
var exportando = false;

function exportar(banTipo){
    // Prevenir múltiples llamadas simultáneas
    if (exportando) {
        return false;
    }
    exportando = true;
    
    var batch = new Array();               
    var grid = $("#searchGrid_mod");
    var ids = grid.jqGrid('getDataIDs');

    // Recopilar todos los rangos
    var params = { dataReport: batch };
    for (var i = 1; i <= numRangos; i++) {
        params['rango' + i + 'Ini'] = $("#rango" + i + "Ini").val();
        params['rango' + i + 'Fin'] = $("#rango" + i + "Fin").val();
    }
    params['numRangos'] = numRangos;

    for (var i = 0; i < ids.length; i++) {
        var datos = grid.jqGrid('getRowData', ids[i]), ban = true;
        for (var j = 0; j < batch.length; j++) {
            if(datos['Prv_Cod'] === batch[j]['Prv_Cod']){
                ban = false;
            }
        }
        if(ban) batch.push({Cli_Cod:datos['Prv_Cod'],Cliente:datos['Proveedor']});
    }

   if(batch.length > 0){ 
        params.dataReport = batch;
        $.post("", params, function( response ) {
            exportando = false; // Liberar el bloqueo
            if(response['success'] === true){
                if(banTipo) {
                    // Limpiar cualquier elemento previo de impresión
                    $('.printElement').remove();
                    // Crear un contenedor temporal para el HTML
                    var $printContainer = $('<div class="printElement" style="display:none;"></div>').html(response['html']);
                    $('body').append($printContainer);
                    // Imprimir
                    $printContainer.printElement({
                        pageTitle: '<?Php echo $Ses_Sys_Nom; ?>'
                    });
                    // Limpiar después de un tiempo
                    setTimeout(function() {
                        $printContainer.remove();
                    }, 1000);
                } else {
                    $.downloadFile($.exportarExcelBlob(response['html'],'CCPP'),'CtaPorPagar-'+$.getDate()+'.xls');
                }
            }else{
                $.alert(response['message']);
            }                                   
         },'json').fail(function(error) { 
            exportando = false; // Liberar el bloqueo en caso de error
            $.alert("El Servidor ha fallado en responder!"); 
            console.log(error); 
        });
    }
    else{
        exportando = false; // Liberar el bloqueo
        $("#searchGrid_mod").startGridEdit();
        $.alert("No hay Datos!");
    }
}
