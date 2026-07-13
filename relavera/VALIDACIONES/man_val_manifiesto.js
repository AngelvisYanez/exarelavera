var grid = $('#searchGrid');
var arrayAsiento = [],
    arrayCheques = [],
    arrayModAsiento = [],
    arrayCuentasPlan = [],
    arrayDetAsiento = [];

var perCodAct = 0,
    existeCheq = false;
var turnoSeleccionado = null;
var plantaSaldosSeleccionada = null;
var gridManifiestoInicializado = false;

$(function () {
    $("#successDialog").createDialog({ width: 500, height: 200, icon: 'print' });
    $("#verAsientoDialogMod").createDialog({ width: 700, height: 350, icon: 'info-data' });
    $("#transporteDialog").createDialog({ width: 400, height: 290, icon: 'info-sign' });
    $("#vehiculoDialog").createDialog({ width: 400, height: 290, icon: 'info-sign' });
    $("#choferDialog").createDialog({ width: 500, height: 400, icon: 'user' });
    $("#plantaDialog").createDialog({ width: 450, height: 230, icon: 'home' });
    $("#turnoDialog").createDialog({ width: 500, height: 520, icon: 'time' });
    $("#modalModificarTiempoLlegada").createDialog({ width: 450, height: 250, icon: 'time' });
    $("#infoManifiestoDialog").createDialog({ width: 380, height: 400, icon: 'info-sign' });
    $("#sancionPlantaDialog").createDialog({ width: 480, height: 380, icon: 'alert' });
    $("#modalSelectorPlantaSaldos").createDialog({ width: 900, height: 520, icon: 'home', autoOpen: false, modal: true });
    $("#modalSelectorPlantaSaldos").on("dialogopen", function () {
        setTimeout(ajustarAnchoGridPlantasSaldos, 120);
    });
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
    $('#Man_Gui').mask('999-999-999999999', { placeholder: '_' });
    $('#Man_Gui').on('keyup change blur', function () {
        validarManGui($(this).val());
    });
    $(document).on('click', '#btnRecargarChoferes', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) { return; }
        $btn.prop('disabled', true).addClass('is-loading');
        recargarVehiculosYChoferes(true).always(function () {
            $btn.prop('disabled', false).removeClass('is-loading');
        });
    });
    $('.pagination').find('li a').click(function () {
        $('.pagination').find('li').removeClass('active');
        $(this).parent().addClass('active');
        $('#letra').val($(this).text());
        busquedaAjax();
    });
    // Evento para el filtro de factura
    $('#filtro_factura').on('change', function() {
        busquedaAjax();
    });

    // Placeholder según tipo de búsqueda (No. Manifiesto usa formato M11-0012)
    $('#searchManifiesto input[name="op_opciones"]').on('change', function() {
        var ph = $(this).val() === 'm' ? 'Ej: M12-0012' : 'Ingrese búsqueda...';
        $('#searchManifiesto input[name="search"]').attr('placeholder', ph);
    });

    // Evento para el ordenamiento (usando delegación de eventos para que funcione con el select en el caption)
    // $(document).on('change', '#ordenar_por', function() {
    //     // Actualizar el campo oculto en el formulario para que se envíe con la búsqueda
    //     $('#ordenar_por_hidden').val($(this).val());
    //     busquedaAjax();
    // });
    $('#Ciu_Cod').createChosen('input-xs', {
        tabIndex: 6,
        width: '100%',
        template: function(t, d) {
            return '<div class="over"><b>' + t + '</b></div><div class="over desc" style="font-size:11px;"><b>Provincia:</b> ' + d['prov'] + ' <b>Pa&iacute;s:</b> ' + d['pais'] + '</div>';
        }
    });

    changePeriodo();

    // Establecer fecha inicial: día y mes actual, año del período
    setTimeout(function() {
        // Obtener límites del período seleccionado
        var selectedOption = $("#Pec_Cod option:selected");
        var inicio = selectedOption.data('inicio');
        var fin = selectedOption.data('fin');

        // Obtener día y mes actual (parsear 'hoy' como fecha local para evitar zona horaria)
        var partesHoy = String(hoy).split('-');
        var fecha = partesHoy.length === 3
            ? new Date(parseInt(partesHoy[0], 10), parseInt(partesHoy[1], 10) - 1, parseInt(partesHoy[2], 10))
            : new Date();
        var diaActual = fecha.getDate();
        var mesActual = fecha.getMonth(); // 0-11

        var fechaParaUsar;
        var fechaFormato;

        // Si hay límites del período, usar el año del período
        if (inicio && fin) {
            // Parsear fechas del período
            var partesInicio = inicio.split('-');
            var partesFin = fin.split('-');
            var añoPeriodo = parseInt(partesInicio[0]);

            // Crear fecha con día y mes actual, pero año del período
            // Usar componentes locales para evitar problemas de zona horaria
            fechaParaUsar = new Date(añoPeriodo, mesActual, diaActual);

            // Crear objetos Date para comparación (usando componentes locales)
            var fechaInicio = new Date(parseInt(partesInicio[0]), parseInt(partesInicio[1]) - 1, parseInt(partesInicio[2]));
            var fechaFin = new Date(parseInt(partesFin[0]), parseInt(partesFin[1]) - 1, parseInt(partesFin[2]));

            // Verificar que la fecha esté dentro del rango del período
            if (fechaParaUsar < fechaInicio) {
                // Si la fecha es menor que el inicio, usar la fecha de inicio
                fechaParaUsar = new Date(fechaInicio);
            } else if (fechaParaUsar > fechaFin) {
                // Si la fecha es mayor que el fin, usar la fecha de fin
                fechaParaUsar = new Date(fechaFin);
            }
        } else {
            // Si no hay período, usar fecha actual (hoy)
            fechaParaUsar = new Date(añoHoy, mesHoy, diaHoy);
        }

            // Formatear fecha para el input (YYYY-MM-DD)
            var año = fechaParaUsar.getFullYear();
            var mes = String(fechaParaUsar.getMonth() + 1).padStart(2, '0');
            var dia = String(fechaParaUsar.getDate()).padStart(2, '0');
            fechaFormato = año + '-' + mes + '-' + dia;
        // } else {
        //     // Si no hay período, usar fecha actual (formato YYYY-MM-DD)
        //     fechaParaUsar = new Date(fecha.getFullYear(), mesActual, diaActual);
        //     var año = fechaParaUsar.getFullYear();
        //     var mes = String(fechaParaUsar.getMonth() + 1).padStart(2, '0');
        //     var dia = String(fechaParaUsar.getDate()).padStart(2, '0');
        //     fechaFormato = año + '-' + mes + '-' + dia;
        // }

        // Establecer fecha en ambos campos
        $("#txt_fec_ini").val(fechaFormato);
        $("#txt_fec_fin").val(fechaFormato);

        // Luego establecer la fecha en el datepicker
        // Usar setDate con el objeto Date directamente
        try {
            $('#txt_fec_ini').datepicker("setDate", fechaParaUsar);
            $('#txt_fec_fin').datepicker("setDate", fechaParaUsar);
        } catch(e) {
            // Si falla, intentar con el formato de string
            $('#txt_fec_ini').datepicker("setDate", fechaFormato);
            $('#txt_fec_fin').datepicker("setDate", fechaFormato);
        }

        // Forzar actualización del valor del input después de setDate
        setTimeout(function() {
            var fechaIni = $('#txt_fec_ini').datepicker("getDate");
            var fechaFin = $('#txt_fec_fin').datepicker("getDate");
            if (fechaIni) {
                var año = fechaIni.getFullYear();
                var mes = String(fechaIni.getMonth() + 1).padStart(2, '0');
                var dia = String(fechaIni.getDate()).padStart(2, '0');
                $("#txt_fec_ini").val(año + '-' + mes + '-' + dia);
            }
            if (fechaFin) {
                var año = fechaFin.getFullYear();
                var mes = String(fechaFin.getMonth() + 1).padStart(2, '0');
                var dia = String(fechaFin.getDate()).padStart(2, '0');
                $("#txt_fec_fin").val(año + '-' + mes + '-' + dia);
            }
            buscarYActualizarSaldos();
        }, 100);
    }, 500);

    aplicarEstiloOpcionesEnRuta($('#Veh_Cod'));
    aplicarEstiloOpcionesEnRuta($('#Cho_Cod'));

});

function createGrid() {
    grid.createGrid({
        caption: 'Manifiestos Generados <div class="pull-right"><b>Ordenar por:</b>&nbsp;<select id="ordenar_por" onchange="cargarSelect();"><option value="">Por defecto</option><option value="manifiesto">Nº Manif</option><option value="cliente">Cliente</option><option value="fecha">Fecha</option><option value="placa">Placa</option><option value="hora_llegada">H. Llegada</option><option value="guia">No. Guia</option></select>&nbsp;</div>',
        height: 350,datatype: 'local',
        jsonReader: { root: "rows", id: "Man_Cod" },
        colModel: [
            { label: 'Cod. Int.', name: 'Man_Cod', key: true, width: 25, align: "center" },
            { label: 'Fecha', name: 'Man_Fec', width: 30, align: "center" },
            { label: 'Man_Est', name: 'Man_Est', hidden: true },
            { label: 'Usu_Cod', name: 'Usu_Cod', hidden: true },
            { label: 'Man_Sys', name: 'Man_Sys_Formatted', hidden: true },
            { label: 'Usuario Creador', name: 'usuario_creador', hidden: true },
            { label: 'No Manif.', name: 'ManNum', width: 30, align: "center" },
            { label: 'Guia', name: 'Man_Gui', width: 40, align: "center" },
            { label: 'C&eacute;dula', name: 'Prs_Ced', width: 40, align: "center", cellattr: function () { return 'style="' + excelFormats.text + '"'; } },
            { label: 'Cliente', name: 'cliente', width: 100, align: "left" },
            { label: 'Planta', name: 'Pla_Nom', width: 70, align: "left" },
            { label: 'Peso (KG)', name: 'Man_Pes', width: 25, align: "center" },
            { label: 'H. LLegada', name: 'Man_Fea_Hor', width: 25, title: 'Hora de Llegada', align: "center" },
            { label: 'Factura', name: 'Vet_Num', width: 30, align: "center" },
            { label: 'Vehiculo', name: 'Veh_Pla', width: 30, align: "center" },
            { label: '<i class="glyphicon glyphicon-log-in" style="color: #ffc107;" title="Garita In - Ingreso a garita"></i>', name: 'Man_Tip_1', width: 12, align: "center",
                formatter: function(val, opts, row) {
                    return (row.Man_Tip_1 === 'GI' || row.Man_Tip_1 === 'GE') ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Entrada a Garita"></i>' : '';
                }
            },
            { label: '<i class="glyphicon glyphicon-ok-circle" style="color: #28a745;" title="Aprobado - Aprobacion del Tecnico"></i>', name: 'Man_Tip_2', width: 12, align: "center",
                formatter: function(val, opts, row) {
                    return row.Man_Tip_2 === 'A' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Aprobacion del Tecnico"></i>' : '';
                }
            },
            { label: '<i class="glyphicon glyphicon-log-out" style="color: #17a2b8;" title="Garita Out - Salida de garita"></i>', name: 'Man_Tip_3', width: 12, align: "center",
                formatter: function(val, opts, row) {
                    return row.Man_Tip_3 === 'GS' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Salida de Garita"></i>' : '';
                }
            },
            { label: '<i class="glyphicon glyphicon-file" style="color: #007bff;" title="Facturado - Manifiesto Facturado"></i>', name: 'Man_Tip_4', width: 12, align: "center",
                formatter: function(val, opts, row) {
                    return row.Man_Tip_4 === 'F' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Facturado"></i>' : '';
                }
            },
            { label: '<i class="glyphicon glyphicon-remove-circle" style="color: #dc3545;" title="Rechazado - Manifiesto Rechazado"></i>', name: 'Man_Tip_5', width: 12, align: "center",
                formatter: function(val, opts, row) {
                    return row.Man_Tip_5 === 'R' ? '<i class="glyphicon glyphicon-ok" style="color: #dc3545; font-size: 16px;" title="Rechazado"></i>' : '';
                }
            },
            /*{ label: 'Estado', name: 'estado', width: 50, align: "center",
                formatter: function(val, opts, row) {
                    if (val === 'PENDIENTE') {return '<span class="">PENDIENTE</span>';
                    }else if (val === 'APROBADO') {return '<span class="badge-activo">APROBADO</span>';
                    }else if (val === 'FACTURADO') {return '<span class="badge-facturado">FACTURADO</span>';
                    }else if (val === 'GARITA IN') {return '<span class="badge-garita-in">GARITA IN</span>';
                    }else if (val === 'GARITA OUT') {return '<span class="badge-garita-out">GARITA OUT</span>';
                    }else if (val === 'RECHAZADO') {return '<span class="badge-inactivo">RECHAZADO</span>';}
                }
            },*/
            { label: 'Valor', name: 'total', width: 30, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',',decimalSeparator: '.', defaultValue: '$ 0.00' }, summaryType: "sum" },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_anti', width: 60, align: 'center', viewable: false,
                formatter: function (cellvalue, options, o) {
                    if(o.Man_Est=='I') return '';
                    var botones = '';
                    // Botón de información (siempre visible)
                    botones += $.getGridButton('mostrarInfoManifiesto', {Man_Cod: o.Man_Cod, Usu_Cod: o.Usu_Cod, Man_Sys: o.Man_Sys_Formatted, usuario_creador: o.usuario_creador, chofer: o.chofer, tecnico: o.tecnico, Veh_Pla: o.Veh_Pla}, 'Información del registro', 'info-sign', '', 'info') + "&nbsp;";

                    var esAdminSistemasPlanta = prf.some(function(p) {
                        var des = p.Per_Des.trim();
                        return des === 'Administrador de Sistemas' || des === 'Plantas';
                    });

                    if (!$.isEmpty(Cli_Cod_Man)) {
                        botones += ($.isEmpty(Cli_Cod_Man)?$.getGridButton('print', {Man_Cod: o.Man_Cod,tipo: 'admin'}, 'Manifiesto Administrativo', 'print', '', 'primary') + "&nbsp;":'') +
                        (o.Man_Tip=='F'?$.getGridButton('print', {Man_Cod: o.Man_Cod,tipo: 'certi'}, 'Imprimir Certificado', 'print', '', 'primary')+ "&nbsp;":'')  +
                        (o.Man_Tip!='F'?$.getGridButton('print', {Man_Cod: o.Man_Cod,tipo: 'cliente'}, 'Imprimir Manifiesto', 'print', '', 'primary') + "&nbsp;":'') +
                        (o.Man_Tip!='GS' || o.Man_Tip!='F'?$.getGridButton('printClienteQR',o.Man_Cod, 'Generar codigo QR', 'qrcode', '', 'info')+ "&nbsp;":'')  +
                        (o.Man_Tip=='F'  && esAdminSistemasPlanta?$.getGridButton('printCertificado', {Man_Cod: o.Man_Cod,tipo: 'certif'}, 'Imprimir Certf. Desechos', 'print', '', 'warning')+ "&nbsp;":'')  +
                        /*(o.Man_Tip=='P' && $.isEmpty(Cli_Cod_Man) && !esPerfilLectura?$.getGridButton('modalModificarTiempoLlegada', {Man_Cod: o.Man_Cod,Man_Num: o.ManNum,Man_Fea: o.Man_Fea,Man_Fea_Hora: o.Man_Fea_Hor}, 'Modificar Hora Llegada', 'time', '', 'warning')+ "&nbsp;":'')  +*/
                        /*(o.Man_Tip=='P'?$.getGridButton('modificaManifiesto', o, 'Editar Manifiesto', 'pencil', '', 'success')+ "&nbsp;" : '')  +*/
                        (o.Man_Est=='A' && o.Man_Tip=='P' && $.isEmpty(Cli_Cod_Man) && !esPerfilLectura?$.getGridButton('anularManifiesto', o.Man_Cod, 'Anular manifiesto', 'remove', '', 'danger') : '');
                    } else {
                        botones += $.getGridButton('print', {Man_Cod: o.Man_Cod,tipo: 'admin'}, 'Manifiesto Administrativo', 'print', '', 'primary') + "&nbsp;" +
                        $.getGridButton('print', {Man_Cod: o.Man_Cod,tipo: 'cliente'}, 'Imprimir Manifiesto', 'print', '', 'primary') + "&nbsp;" +
                        (o.Man_Tip=='F' && esAdminSistemasPlanta ? $.getGridButton('printCertificado', {Man_Cod: o.Man_Cod,tipo: 'certif'}, 'Imprimir Certf. Desechos', 'print', '', 'warning')+ "&nbsp;":'')  +
                        (o.Man_Tip=='P' && $.isEmpty(Cli_Cod_Man) && !esPerfilLectura?$.getGridButton('modalModificarTiempoLlegada', {Man_Cod: o.Man_Cod,Man_Num: o.ManNum,Man_Fea: o.Man_Fea,Man_Fea_Hora: o.Man_Fea_Hor}, 'Modificar Hora Llegada', 'time', '', 'warning')+ "&nbsp;":'')  +
                        // (o.Man_Tip!='GS' || o.Man_Tip!='F'?$.getGridButton('printClienteQR',o.Man_Cod, 'Generar codigo QR', 'qrcode', '', 'info')+ "&nbsp;":'')  +
                        /*(o.Man_Tip=='P' && $.isEmpty(Cli_Cod_Man)?$.getGridButton('modificaManifiesto', o, 'Editar Manifiesto', 'pencil', '', 'success')+ "&nbsp;" : '')  +*/
                        (o.Man_Est=='A' && o.Man_Tip=='P' && $.isEmpty(Cli_Cod_Man) && !esPerfilLectura?$.getGridButton('anularManifiesto', o.Man_Cod, 'Anular manifiesto', 'remove', '', 'danger') : '');
                    }
                    return botones;
                }
            }

        ],loadComplete: function () {
            $(this).setGridSummary(['total'], { total: '<div style="text-align:right;">TOTAL:</div>' });
            // Aplicar estilo rojo a los registros anulados (Man_Est = 'I')
            let ids = $(this).jqGrid('getDataIDs');
            for (let i = 0; i < ids.length; i++) {
                let rowData = $(this).jqGrid('getRowData', ids[i]);
                // Si Man_Est es 'I' (Anulado/Inactivo), aplicar estilo rojo a todas las celdas excepto la última (botones)
                if (rowData.Man_Est === 'I') {
                    $("tr#" + ids[i] + " td:not(:last-child):not(.jqgrid-rownum)").css({
                        'color': '#dc3545',
                        'font-style': 'italic'
                    });
                }
            }
            $('.ui-pg-selbox option[value=10000]').text('Todos');
        }, footerrow: true, userDataOnFooter: true, headertitles: true, rowNum: 250, rowList: [250, 500, 1000, 2000, 10000], gridview: true, viewrecords: true
    }, false, '#searchGridPager', { refresh: true, view: false }).gridButtonsAdd([
        { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                grid.jqGrid('exportGridExcel', {
                    nombre: 'Manifiesto',
                    hoja: 'HOJA 1',
                    footer: true,
                    // removeHiddens: true,
                    // removeCols: [1, 10]
                });
            }
        }
    ]);

    $('#searchGridPager table.navtable').find('td.ui-pg-button.ui-corner-all').unbind('mouseenter mouseleave').removeClass('ui-pg-button').addClass('btn btn-xs btn-success').find('.ui-pg-div span').removeClass('ui-icon').addClass('glyphicon');

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

    function printManifiesto() {
        $('#tablaReporte').html(grid.jqGrid('exportGridInnerHTML', {
            footer: true,
            generated: false,
            removeHiddens: true,
            removeCols: [1, 10]
        }));
        $('#imprimir').printElement();
    }

}
/** funciones manifiesto **/
function cargarSelect(){
    let filtro = $("#ordenar_por").val();
    // Actualizar el campo oculto para que se envíe con la búsqueda (incluso al paginar)
    $("#ordenar_por_hidden").val(filtro);

    // Recargar el grid con el nuevo filtro
    $('#searchGrid').Search('#searchManifiesto','manifiestoAjax');
}

// Función para mostrar información del manifiesto (usuario creador y fecha de creación)
function mostrarInfoManifiesto(data) {
    var usuario = data.usuario_creador || 'No disponible';
    var usuCod = data.Usu_Cod || 'N/A';
    var fechaCreacion = data.Man_Sys || 'No disponible';

    // Formatear la fecha si está disponible
    if (fechaCreacion !== 'No disponible' && fechaCreacion) {
        try {
            var fecha = new Date(fechaCreacion);
            if (!isNaN(fecha.getTime())) {
                var dia = String(fecha.getDate()).padStart(2, '0');
                var mes = String(fecha.getMonth() + 1).padStart(2, '0');
                var año = fecha.getFullYear();
                var horas = String(fecha.getHours()).padStart(2, '0');
                var minutos = String(fecha.getMinutes()).padStart(2, '0');
                var segundos = String(fecha.getSeconds()).padStart(2, '0');
                fechaCreacion = dia + '/' + mes + '/' + año + ' ' + horas + ':' + minutos + ':' + segundos;
            }
        } catch(e) {
            // Si falla el formateo, usar el valor original
        }
    }

    // Llenar el contenido del diálogo
    $('#infoManifiestoUsuario').text(usuario);
    $('#infoManifiestoUsuCod').text(usuCod);
    $('#infoManifiestoFecha').text(fechaCreacion);
    $('#infoManifiestoChofer').text(data.chofer || '-');
    $('#infoManifiestoTecnico').text(data.tecnico || '-');
    $('#infoManifiestoPlaca').text(data.Veh_Pla || '-');

    // Abrir el diálogo
    $('#infoManifiestoDialog').dialog('open');
}

// Función para abrir modal de modificar tiempo de llegada
function modalModificarTiempoLlegada(o) {
    // Limpiar formulario
    $('#modificarTiempoLlegadaForm')[0].reset();
    $('#Man_Cod_Mod').val(o.Man_Cod);
    $('#Man_Num_Modificar').val(o.Man_Num);
    $('#Man_Fea_Modi').val(o.Man_Fea);
    $('#Man_Fea_Hor_Modificar').val(o.Man_Fea_Hora);
    $('#modalModificarTiempoLlegada').dialog('open');
}

// Función para guardar modificación de tiempo de llegada
function guardarModificacionTiempoLlegada() {
    // Validar que los campos requeridos estén completos
    var Man_Cod = $('#Man_Cod_Mod').val();
    var Man_Fea = $('#Man_Fea_Modi').val();
    var Man_Fea_Hor = $('#Man_Fea_Hor_Modificar').val();

    if (!Man_Cod) {
        $.alert('Error: No se identificó el manifiesto.');
        return;
    }

    if (!Man_Fea || !Man_Fea_Hor) {
        $.alert('Por favor, complete la fecha y hora de llegada.');
        return;
    }

    // Confirmar antes de guardar
    $.createDialogConfirm('¿Está seguro que desea modificar el tiempo de llegada del manifiesto?',
        { Man_Cod: Man_Cod, Man_Fea: Man_Fea, Man_Fea_Hor: Man_Fea_Hor },
        function(data) {
            modificarTiempoLlegada(data);
        }
    );
}

// Función para modificar tiempo de llegada en el servidor
function modificarTiempoLlegada(data) {
    $.post("", {
        modificarTiempoLlegadaAjax: true,
        Man_Cod: data.Man_Cod,
        Man_Fea: data.Man_Fea,
        Man_Fea_Hor: data.Man_Fea_Hor
    }, function (r) {
        if (r['success'] === true) {
            $.alert('Tiempo de llegada modificado correctamente', function() {
                // Cerrar modal
                $('#modalModificarTiempoLlegada').dialog('close');
                // Recargar el grid para mostrar los cambios
                $('#searchGrid').trigger('reloadGrid');
            });
        } else {
            $.alert('Error al modificar el tiempo de llegada: ' + (r['message'] || r['error'] || 'Error desconocido'));
        }
    }, 'json')
    .fail(function(error) {
        $.alert("Error de conexión con el servidor al modificar el tiempo de llegada.");
    });
}

if (Cli_Cod_Man !== '')
    $('#btn_span').hide();
else
    $('#btn_span').show();
// Evento para cambiar las fechas
$("#txt_fec_ini").change(function () {
    selectedStartDate = $(this).val(); // Actualiza la fecha de inicio seleccionada
});
function vahiculosTransporte(id) {
    $.getDataJson('', { ajaxVehiculoManifiesto: true, Mat_Cod: id }, (r) => {
        if (!$.isEmpty(r)) {
            $('#Veh_Cod').empty();
            $.each(r.rows, function (i, item) {
                $('#Veh_Cod').append($('<option>', {
                    value: item.Veh_Cod,
                    text: item.Veh_Pla + ' - ' + item.Veh_Mar,
                    'data-peso': item.Veh_Cap,
                    'data-marca': item.Veh_Mar
                }));
            });

        }
    }, (err) => {
        reject(err);
    });
}
function printClienteQR(id) {
    window.open('tes_rep_cliente_qr.php?Man_Cod=' + id, '_blank', 'width=900,height=900,scrollbars=yes,resizable=yes');
}
function print(o) {
    $.getDataJson('man_alt_manifiesto.php', { imprimirAjax: true, Man_Cod: o.Man_Cod,tipo: o.tipo}, (r) => {
        if (!$.isEmpty(r)) {
            var ventana = window.open('', '_blank');
            if (ventana) {
                ventana.document.write(r.tabla);
                ventana.document.close();
                ventana.focus();

                // Si es tipo 'ticket', solo mostrar en nueva pestaña sin imprimir automáticamente
                // El usuario puede usar el botón de imprimir del navegador para imprimir
                if (o.tipo === 'ticket') {
                    // No llamar a print() automáticamente, solo mostrar el reporte
                    // El botón de imprimir en el template permitirá al usuario imprimir cuando lo desee
                } else {
                    // Para otros tipos de reporte, imprimir automáticamente
                    ventana.print();
                }
            }
        }
    }, (err) => {
        reject(err);
    });
}

function printCertificado(o) {
    if (printCertificado.isProcessing) return;
    printCertificado.isProcessing = true;

    let id = (typeof o === 'object' && o.Man_Cod) ? o.Man_Cod : o;
    let params = { printCertificadoAjax: true, Man_Cod: id };
    if (typeof o === 'object' && o.tipo) params.tipo = o.tipo;
    $.getDataJson('man_alt_manifiesto.php', params, (r) => {
        printCertificado.isProcessing = false;
        if (!$.isEmpty(r)) {
            if (r.success === false) {
                $.alert(r.message + '<br>Ruta: ' + r.path);
                return;
            }
            var ventana = window.open('', '_blank');
            if (ventana) {
                ventana.document.write(r.tabla);
                ventana.document.close();
            }
        }
    }, (err) => {
        printCertificado.isProcessing = false;
        reject(err);
    });
}

function preSaveVehiculo() {
    let data = $('#vehiculoForm').getData();
    data.saveVehiculoAjax = true;
    data.Cli_Cod = $('#Cli_Cod').val();
    console.log(data);
    $.createDialogConfirm('Est&aacute; seguro que desea guardar los datos?', data, saveVehiculo);
}
function saveVehiculo(data) {
    $.saveDataJson('', data, function (r) {
        if (r['success'] === true) {
            $('#Veh_Cod').append("<option value='" + r.Veh_Cod_New + "' data-mar='" + data.Veh_Mar + "' data-peso='" + data.Veh_Cap + "' >" + data.Veh_Pla + " - " + data.Veh_Mar + "</option>");
            $('#vehiculoForm')[0].reset();
            $('#vehiculoDialog').dialog('close');
        } else
            $.alert(r['message']);
    }, function (r) {
        console.log(r);
    });
}
function editarVehiculo(id) {
    $.post("", { editVehiculoAjax: true, Veh_Cod: id }, function (r) {
        if (r['success'] === true) {
            $('#vehiculoForm').setData(r.rows);
            $('#vehiculoDialog').dialog('open');
        } else {
            $.alert(r['message']);
        }
    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });
}

function preSaveTransporte() {
    let data = $('#transporteForm').getData();
    data.saveTransporteAjax = true;
    data.Cli_Cod = $('#Cli_Cod').val();
    console.log(data);
    $.createDialogConfirm('Est&aacute; seguro que desea guardar los datos?', data, saveTransporte);
}
function saveTransporte(data) {
    $.saveDataJson('', data, function (r) {
        if (r['success'] === true) {
            $('#Mat_Cod').append("<option value='" + r.Mat_Cod_New + "' data-mae='" + data.Mat_Mae + "' data-pco='" + data.Mat_Pco + "' >" + data.Mat_Des + "</option>");
            $('#Mat_Cod_New').append("<option value='" + r.Mat_Cod_New + "' data-mae='" + data.Mat_Mae + "' data-pco='" + data.Mat_Pco + "' >" + data.Mat_Des + "</option>");
            $('#transporteForm')[0].reset();
            $('#transporteDialog').dialog('close');
        } else
            $.alert(r['message']);
    }, function (r) {
        console.log(r);
    });
}
function editarTransporte(id) {
    $.post("", { editTransporteAjax: true, Mat_Cod: id }, function (r) {
        if (r['success'] === true) {
            $('#transporteForm').setData(r.rows);
            $('#transporteDialog').dialog('open');
        } else {
            $.alert(r['message']);
        }
    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });
}

/* Funciones para Chofer */

// Abrir modal de chofer solo si hay cliente seleccionado
function abrirModalChofer() {
    if ($.isEmpty($('#Cli_Cod').val())) {
        $.alert('Debe seleccionar un <b>Cliente</b> antes de agregar un chofer.');
        return false;
    }
    resetChoferForm();
    $('#choferDialog').dialog('open');
}

// Buscar persona por cédula
function buscarPersonaPorCedula(cedula) {
    if ($.isEmpty(cedula) || cedula.length < 10) {
        $("#Cho_Ced_Est").removeClass("fa fa-check fa-spinner fa-spin").addClass("fa fa-close").css("color", "orange").attr('title', 'Ingrese una c&eacute;dula v&aacute;lida');
        return;
    }

    // Mostrar spinner de carga
    $("#Cho_Ced_Est").removeClass("fa fa-check fa-close").addClass("fa fa-spinner fa-spin").css("color", "#337ab7").attr('title', 'Buscando...');

    $.post("", { buscarPersonaCedulaAjax: true, Prs_Ced: cedula }, function (r) {
        if (r.existe === true) {
            // Persona encontrada - llenar campos
            $('#Prs_Cod_Chofer').val(r.persona.Prs_Cod);
            $('#Prs_Nom').val(r.persona.Prs_Nom);
            $('#Prs_Ape').val(r.persona.Prs_Ape);
            if (r.persona.Prs_Tel) $('#Cho_Tel').val(r.persona.Prs_Tel);

            // Indicador verde - encontrado
            $("#Cho_Ced_Est").removeClass("fa fa-close fa-spinner fa-spin").addClass("fa fa-check").css("color", "green").attr('title', 'Persona encontrada: ' + r.persona.Prs_Nom + ' ' + r.persona.Prs_Ape);

            // Deshabilitar campos de nombre si ya existe
            $('#Prs_Nom, #Prs_Ape').prop('readonly', true).css('background-color', '#eee');
        } else {
            // Persona no encontrada - limpiar y habilitar campos
            $('#Prs_Cod_Chofer').val('');
            $('#Prs_Nom').val('').prop('readonly', false).css('background-color', '');
            $('#Prs_Ape').val('').prop('readonly', false).css('background-color', '');

            // Indicador azul - nuevo registro
            $("#Cho_Ced_Est").removeClass("fa fa-close fa-spinner fa-spin").addClass("fa fa-check").css("color", "#337ab7").attr('title', 'Nueva persona - complete los datos');
        }
    }, 'json')
        .fail(function (error) {
            $("#Cho_Ced_Est").removeClass("fa fa-check fa-spinner fa-spin").addClass("fa fa-close").css("color", "red").attr('title', 'Error al buscar');
            $.alert("Error al buscar la persona!");
        });
}

function preSaveChofer() {
    let data = $('#choferForm').getData();
    data.saveChoferAjax = true;
    data.Cli_Cod = $('#Cli_Cod').val();
    console.log(data);
    $.createDialogConfirm('&iquest;Est&aacute; seguro que desea guardar los datos del chofer?', data, saveChofer);
}

function saveChofer(data) {
    $.saveDataJson('', data, function (r) {
        if (r['success'] === true) {
            // Agregar el nuevo chofer al select
            let nombreCompleto = r.nombre || (data.Prs_Nom + ' ' + data.Prs_Ape);
            $('#Cho_Cod').append("<option value='" + r.Cho_Cod_New + "' data-lic='" + data.Cho_Tli + "' data-ced='" + data.Cho_Ced + "'>" + nombreCompleto + "</option>");
            resetChoferForm();
            $('#choferDialog').dialog('close');
        } else {
            $.alert(r['message']);
        }
    }, function (r) {
        console.log(r);
    });
}

// Función para resetear el formulario de chofer
function resetChoferForm() {
    $('#choferForm')[0].reset();
    $('#Prs_Cod_Chofer').val('');
    $('#Prs_Nom, #Prs_Ape').prop('readonly', false).css('background-color', '');
    $("#Cho_Ced_Est").removeClass("fa fa-check fa-close fa-spinner fa-spin").css("color", "").attr('title', '');
}

function anularChofer() {
    let Cho_Cod = $('#Cho_Cod_Form').val();
    if ($.isEmpty(Cho_Cod)) {
        $.alert('No hay chofer seleccionado para anular.');
        return;
    }
    $.createDialogConfirm('&iquest;Est&aacute; seguro que desea anular este chofer?', { Cho_Cod: Cho_Cod }, function (data) {
        $.post("", { anularChoferAjax: true, Cho_Cod: data.Cho_Cod }, function (r) {
            if (r['success'] === true) {
                $('#Cho_Cod option[value="' + data.Cho_Cod + '"]').remove();
                $('#choferForm')[0].reset();
                $('#choferDialog').dialog('close');
                $.alert('Chofer anulado correctamente.');
            } else {
                $.alert(r['message']);
            }
        }, 'json')
            .fail(function (error) {
                $.alert("El Servidor ha fallado en responder!");
            });
    });
}

/* Funciones para Planta */

// Abrir modal de planta solo si hay cliente seleccionado
function abrirModalPlanta(){
    if($.isEmpty($('#Cli_Cod').val())){
        $.alert('Debe seleccionar un <b>Cliente</b> antes de agregar una planta.');
        return false;
    }
    $('#plantaForm')[0].reset();
    $('#Pla_Cod_Form').val('');
    $('#Ciu_Cod').val('').trigger('chosen:updated');
    $('#plantaDialog').dialog('open');
    // Inicializar Chosen después de abrir el dialog
    setTimeout(function(){
        $('#Ciu_Cod').chosen({ width: '100%', no_results_text: 'No se encontró: ' });
    }, 100);
}

// Seleccionar planta y cargar datos
function seleccionarPlanta(select){
    let $opt = $(select).find(':selected');
    if($opt.val()){
        // Si la planta tiene licencia, se puede usar para llenar Man_Lac
        let licencia = $opt.data('lic');
        if(licencia && licencia !== ''){
            $('#Man_Lac').val(licencia);
            $('#Man_Dsa').val($opt.data('dir'));
        }
    }
}

function preSavePlanta(){
	let data = $('#plantaForm').getData();
	data.savePlantaAjax = true;
    data.Cli_Cod = $('#Cli_Cod').val();
	console.log(data);
	$.createDialogConfirm('&iquest;Est&aacute; seguro que desea guardar los datos de la planta?', data, savePlanta);
}

function savePlanta(data){
	$.saveDataJson('', data, function(r){
        if(r['success'] === true){
            // Agregar la nueva planta al select
            $('#Pla_Cod').append("<option value='"+ r.Pla_Cod_New +"' data-lic='"+ data.Pla_Lic +"' data-dir='"+ data.Pla_Dir +"'>"+ data.Pla_Nom +"</option>");
            // Seleccionar la nueva planta
            $('#Pla_Cod').val(r.Pla_Cod_New);
            $('#plantaForm')[0].reset();
            $('#plantaDialog').dialog('close');
        } else {
			$.alert(r['message']);
        }
    }, function(r){
        console.log(r);
    });
}

// Cargar plantas cuando se selecciona un cliente
function cargarPlantasCliente(Cli_Cod){
    $.get("", {listPlantasAjax: true, Cli_Cod: Cli_Cod}, function(r) {
        if(r['success'] === true){
            $('#Pla_Cod').empty();
            $('#Pla_Cod').append('<option value="">Seleccione planta...</option>');
            if(r.plantas && r.plantas.length > 0){
                $.each(r.plantas, function(i, item){
                    $('#Pla_Cod').append("<option value='"+ item.Pla_Cod +"' data-lic='"+ item.Pla_Lic +"' data-dir='"+ item.Pla_Dir +"'>"+ item.Pla_Nom +"</option>");
                });

                 // Si solo tiene una planta, seleccionarla automáticamente
                if(r.plantas.length === 1){
                    $('#Pla_Cod').val(r.plantas[0].Pla_Cod).trigger('change');
                }
            }
        }
    }, 'json');
}

function abrirModalSelectorPlantaSaldos() {
    inicializarGridPlantasSaldos();
    cargarListadoPlantasSaldos('');
    $('#txtBuscarPlantaSaldos').val('');
    $('#modalSelectorPlantaSaldos').dialog('open');
    setTimeout(function () {
        $('#txtBuscarPlantaSaldos').focus();
        ajustarAnchoGridPlantasSaldos();
    }, 200);
}

/** Ajusta el jqGrid al ancho útil del modal (el grid se crea con el modal oculto y el ancho inicial suele fallar). */
function ajustarAnchoGridPlantasSaldos() {
    var $grid = $('#tablaPlantasSaldos');
    if (!$grid.length || !$grid[0].grid) return;
    var $root = $('#modalSelectorPlantaSaldos');
    var w = $root.innerWidth();
    if (!w || w < 80) {
        w = $('#wrapGridPlantasSaldos').innerWidth();
    }
    if (!w || w < 80) {
        var $dlg = $root.closest('.ui-dialog');
        w = $dlg.length ? $dlg.find('.ui-dialog-content').first().innerWidth() : 0;
    }
    if (w && w > 80) {
        $grid.jqGrid('setGridWidth', Math.floor(w - 6), true);
    }
}

function inicializarGridPlantasSaldos() {
    if ($('#tablaPlantasSaldos')[0] && $('#tablaPlantasSaldos')[0].grid) return;
    $('#tablaPlantasSaldos').createGrid({
        caption: 'Plantas disponibles',
        height: 260,
        rowNum: 10000,
        autowidth: true,
        //shrinkToFit: true,
        data: [],
        colModel: [
            { label: 'Cod', name: 'Pla_Cod', key: true, width: 7, align: 'center' },
            { label: 'RUC', name: 'Prs_Ced', width: 13, align: 'center' },
            { label: 'Planta', name: 'Pla_Nom', width: 32 },
            { label: 'Cliente', name: 'Cli_Nom', width: 30 },           
            { label: 'Cli_Cod', name: 'Cli_Cod', hidden: true },
            {
                label: '', name: 'act1', width: 8, align: 'center', viewable: false, title: false,
                formatter: function (_, __, rowObject) {
                    var plaCod = rowObject.Pla_Cod || '';
                    var cliCod = rowObject.Cli_Cod || '';
                    var plaNom = String(rowObject.Pla_Nom || '').replace(/'/g, "\\'");
                    return '<button type="button" class="btn btn-success btn-xs" onclick="seleccionarPlantaSaldos(' + plaCod + ', ' + cliCod + ', \'' + plaNom + '\');"><i class="glyphicon glyphicon-arrow-right"></i></button>';
                }
            }
        ],
        ondblClickRow: function (id) {
            var row = $('#tablaPlantasSaldos').jqGrid('getRowData', id);
            if (row && row.Pla_Cod) {
                seleccionarPlantaSaldos(row.Pla_Cod, row.Cli_Cod, row.Pla_Nom);
            }
        }
    }, true, '#tablaPlantasSaldosPager', { refresh: false, view: false, add: false, del: false, search: false });
}

function cargarListadoPlantasSaldos(filtroTexto) {
    var plantas = Array.isArray(plantasSaldosModal) ? plantasSaldosModal : [];
    var txt = (filtroTexto || '').toLowerCase().trim();
    var rows = [];

    $.each(plantas, function (_, p) {
        var contenido = [
            p.Pla_Cod || '',
            p.Pla_Nom || '',
            p.Cli_Nom || '',
            p.Prs_Ced || ''
        ].join(' ').toLowerCase();

        if (txt === '' || contenido.indexOf(txt) !== -1) {
            rows.push(p);
        }
    });
    $('#tablaPlantasSaldos').setRows(rows);
    setTimeout(function () {
        ajustarAnchoGridPlantasSaldos();
    }, 50);
}

function seleccionarPlantaSaldos(plaCod, cliCod, plaNom) {
    if ($.isEmpty(plaCod)) {
        $.alert('No se pudo identificar la planta seleccionada.');
        return;
    }

    $('#Pla_Cod').val(plaCod);
    if ($('#Pla_Nom').length > 0 && plaNom) {
        $('#Pla_Nom').text(plaNom).attr('title', plaNom);
    }

    plantaSaldosSeleccionada = {
        plaCod: plaCod,
        cliCod: cliCod || '',
        plaNom: plaNom || ''
    };
    actualizarEstadoPlantaSaldos();
    obtenerSaldos(plaCod, cliCod);
    $('#modalSelectorPlantaSaldos').dialog('close');
}

function cerrarSaldosPlantaSeleccionada() {
    plantaSaldosSeleccionada = null;
    actualizarEstadoPlantaSaldos();
    obtenerSaldos();
}

function actualizarEstadoPlantaSaldos() {
    var $btnCerrar = $('#btnCerrarSaldosPlanta');
    var $lblPlanta = $('#lblPlantaSaldosActiva');
    if (plantaSaldosSeleccionada && plantaSaldosSeleccionada.plaCod) {
        if ($btnCerrar.length > 0) $btnCerrar.show();
        if ($lblPlanta.length > 0) {
            var texto = 'Saldos: ' + (plantaSaldosSeleccionada.plaNom || ('Planta #' + plantaSaldosSeleccionada.plaCod));
            $lblPlanta.text(texto).attr('title', texto).show();
        }
    } else {
        if ($btnCerrar.length > 0) $btnCerrar.hide();
        if ($lblPlanta.length > 0) $lblPlanta.hide().text('');
    }
}

$(document).on('keyup', '#txtBuscarPlantaSaldos', function () {
    cargarListadoPlantasSaldos($(this).val());
});

/**
 * cargar clientes
 * @param  {object} cliente row seleccionada del dialogo de proveedores
 * @return {void}
 */
function selectCliente(cli) {
    $('#manifiestoForm input[name="Prs_Cod"]').val(cli.Prs_Cod);
    $('#manifiestoForm input[name="Cli_Cod"]').val(cli.Cli_Cod);
    $('#manifiestoForm input[name="saldo"]').val((cli.saldo * 1).toFixed(2));
    $("#Prs_Ced_Inf").html(cli.Prs_Ced);
    $("#cliente").val(cli.nombre);
    $('#cliDialog').dialog('close');

    // Cargar plantas del cliente
    cargarPlantasCliente(cli.Cli_Cod);

    $.get('', { listaTransporteAjax: true, Cli_Cod: cli.Cli_Cod}, function(r){
        if(r.trans.length>0){ /* carga Vehculos */
            $('#Mat_Cod').empty();
            $('#Mat_Cod_New').empty();
            $('#Mat_Cod').append($('<option>', { value: '', text: 'Seleccione...' }));
            $('#Mat_Cod_New').append($('<option>', { value: '', text: 'Seleccione...' }));
            $.each(r.trans, function (i, item) {
                $('#Mat_Cod').append($('<option>', { value: item.Mat_Cod, text: item.Mat_Des, 'data-mae': item.Mat_Mae, 'data-pco': item.Mat_Pco }));
                $('#Mat_Cod_New').append($('<option>', { value: item.Mat_Cod, text: item.Mat_Des, 'data-mae': item.Mat_Mae, 'data-pco': item.Mat_Pco }));
            });
        }
        if (r.chof.length > 0) { /* carga Vehculos */
            $('#Cho_Cod').empty();
            $('#Cho_Cod').append($('<option>', { value: '', text: 'Seleccione...' }));
            $.each(r.chof, function (i, item) {
                $('#Cho_Cod').append($('<option>', {
                    value: item.Cho_Cod,
                    text: item.nombre,
                    'data-lic': item.Cho_Tli,
                    'data-ced': item.Prs_Ced
                }));
            });
        }
    }, 'json')
        .fail(function (error) {
            console.log("El Servidor ha fallado en responder!");
        });
}
/* Verifica Numero de manifiesto */
function numeroManifiesto(num) {
    if ($.isEmpty($('#Cli_Cod').val())) {
        $("#Man_Num_Est").removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title', 'Falta escoger <b>Cliente</b> para continuar !');
        $("#Man_Num").val('');
        return false;
    } else
        $("#Man_Num_Est").removeClass("fa fa-close");

    $.post("", { numeroManiAjax: true, Mat_Num: num, Cli_Cod: $('#Cli_Cod').val() }, function (r) {
        if (r.numero.length > 0) {
            $("#Man_Num_Est").removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title', 'El numero <b>' + num + '</b> ya Existe !');
        } else {
            $("#Man_Num_Est").removeClass("fa fa-close").addClass("fa fa-check").css("color", "green").attr('title', 'Numero Aceptado');
        }
    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });
}

function validarManGui(manGui) {
    var $estado = $("#Man_Gui_Est");
    var gui = $.trim(manGui || '');
    var guiClean = gui.replace(/\D/g, '');
    var plaCod = $('#Pla_Cod').val();
    var manCod = $('#Man_Cod').val() || 0;

    if (!gui || guiClean.length < 15) {
        $estado.removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title', 'Complete la guía para validar');
        return false;
    }
    if ($.isEmpty(plaCod)) {
        $estado.removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title', 'No se encontró la planta para validar');
        return false;
    }

    $.post('', { validarManGuiAjax: true, Pla_Cod: plaCod, Man_Cod: manCod, Man_Gui: gui }, function (r) {
        if (r && r.success === true && r.valido === true) {
            $estado.removeClass("fa fa-close").addClass("fa fa-check").css("color", "green").attr('title', r.message || 'Guía disponible');
        } else {
            $estado.removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title', (r && r.message) ? r.message : 'Guía no válida');
        }
    }, 'json').fail(function () {
        $estado.removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title', 'No se pudo validar la guía');
    });
}

function preSaveManifiesto() {
    // // Validar que las horas estén completas antes de guardar
    // let Man_Fea_Hor = $('#Man_Fea_Hor').val();
    // let Man_Fes_Hor = $('#Man_Fes_Hor').val();

    // if (!Man_Fea_Hor || Man_Fea_Hor.trim() === '') {
    //     $.alert('Error: La hora de llegada no está establecida. Por favor, seleccione un turno válido.');
    //     return false;
    // }

    // if (!Man_Fes_Hor || Man_Fes_Hor.trim() === '') {
    //     $.alert('Error: La hora de salida no está establecida. Por favor, seleccione un turno válido.');
    //     return false;
    // }

    var Man_Gui = $('#Man_Gui').val();
    var guiTrim = $.trim(Man_Gui || '');
    var guiClean = guiTrim.replace(/\D/g, '');
    var $estadoGui = $("#Man_Gui_Est");
    var plaCod = $('#Pla_Cod').val();
    var manCod = $('#Man_Cod').val() || 0;

    if (!guiTrim) {
        $.alert('Error: El número de <b>Guía de Remisión</b> es requerido.');
        $('#Man_Gui').focus();
        return false;
    }
    if (guiClean.length < 15) {
        $.alert('Error: Complete la <b>Guía de Remisión</b> (15 dígitos) antes de guardar.');
        $('#Man_Gui').focus();
        validarManGui(guiTrim);
        return false;
    }
    if ($.isEmpty(plaCod)) {
        $.alert('Error: No se encontró la planta para validar la guía.');
        return false;
    }

    // Validar siempre en servidor antes de confirmar (evita guardar con guía repetida si el ícono quedó desactualizado o la petición anterior no terminó).
    $.post('', { validarManGuiAjax: true, Pla_Cod: plaCod, Man_Cod: manCod, Man_Gui: guiTrim }, function (r) {
        if (r && r.success === true && r.valido === true) {
            $estadoGui.removeClass("fa fa-close").addClass("fa fa-check").css("color", "green").attr('title', r.message || 'Guía disponible');
            var data = $('#manifiestoForm').getData();
            data.saveManifiestoAjax = true;
            data.Cli_Cod = $('#Cli_Cod').val();
            data.Mat_Cod = $('#Veh_Cod option:selected').data('mat_cod');
            $.createDialogConfirm('Est&aacute; seguro que desea guardar los datos?', data, saveManifiesto);
        } else {
            var msgGui = (r && r.message) ? r.message : 'La guía no es válida o ya existe para esta planta.';
            $estadoGui.removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title', msgGui);
            $.alert('Error: ' + msgGui);
            $('#Man_Gui').focus();
        }
    }, 'json').fail(function () {
        $estadoGui.removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title', 'No se pudo validar la guía');
        $.alert('No se pudo verificar la guía. Intente de nuevo.');
        $('#Man_Gui').focus();
    });
}
//Guardar Manifiesto
function saveManifiesto(data) {
    $.saveDataJson('', data, function (resp) {
        if (resp['success']) {
            $.alert('La transacci&oacute;n se realizo con exito.', function() {
                // Recargar la página para actualizar los bloqueos de vehículos y choferes
                window.location.reload();
            });
            return false;
        }
    });
}

// Función para actualizar los saldos en la interfaz
function actualizarSaldos(saldos) {
    if (saldos && typeof saldos === 'object') {
        var saldoMinPla = parseFloat(saldos.pla_smi);
        if (isNaN(saldoMinPla)) {
            saldoMinPla = parseFloat($('#pla_smi_saldo_min').val()) || 0;
        } else {
            $('#pla_smi_saldo_min').val(saldoMinPla.toFixed(2));
        }
        var minTxt = saldoMinPla.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        // Actualizar saldo de anticipos
        var saldoAnticipo = parseFloat(saldos.anticipo || 0);
        var anticipoElement = $('#anticipo_saldo');
        if (anticipoElement.length > 0) {
            anticipoElement.text(saldoAnticipo.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
        }

        // Actualizar saldo sin facturar
        var saldoSinFactura = parseFloat(saldos.sin_factura || 0);
        var sinFacturaElement = $('#saldo_sin_factura');
        if (sinFacturaElement.length > 0) {
            sinFacturaElement.text(saldoSinFactura.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
        }

        // Actualizar saldo total
        var saldoTotal = parseFloat(saldos.total || 0);
        var saldoTotalElement = $('#saldo_total');
        var saldoInputElement = $('#saldo');
        var btnNuevoElement = $('#btnNuevo');

        if (saldoTotalElement.length > 0) {
            saldoTotalElement.text(saldoTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));

            // Actualizar estilos según el saldo total
            var saldoTotalContainer = $('#saldo_total_panel');
            if (saldoTotalContainer.length === 0) {
                saldoTotalContainer = saldoTotalElement.closest('div[style*="background"]');
            }
            if (saldoTotalContainer.length > 0) {
                var saldoEnCero = (Math.abs(saldoTotal) < 0.00001);
                var isInsufficient = saldoEnCero || (saldoMinPla > 0 && saldoTotal < saldoMinPla);

                // Actualizar fondo y borde del contenedor
                if (isInsufficient) {
                    saldoTotalContainer.css({
                        'background': 'linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%)',
                        'border-color': '#dc3545'
                    });
                } else {
                    saldoTotalContainer.css({
                        'background': 'linear-gradient(135deg, #d1e7dd 0%, #badbcc 100%)',
                        'border-color': '#198754'
                    });
                }

                // Actualizar color del saldo total
                saldoTotalElement.css('color', isInsufficient ? '#dc3545' : '#198754');

                // Actualizar color del símbolo $
                var dollarSign = saldoTotalElement.siblings('span[style*="font-size: 11px"]');
                if (dollarSign.length > 0) {
                    dollarSign.css('color', isInsufficient ? '#dc3545' : '#198754');
                }

                // Actualizar color del label
                var labelElement = saldoTotalContainer.find('label.control-label');
                if (labelElement.length > 0) {
                    labelElement.css('color', isInsufficient ? '#721c24' : '#0f5132');
                }

                // Actualizar badge de estado (Insuficiente/Disponible)
                // Buscar todos los badges dentro del contenedor
                var allBadgesInsufficient = saldoTotalContainer.find('span.label.label-danger');
                var allBadgesDisponible = saldoTotalContainer.find('span').filter(function() {
                    var text = $(this).text().trim();
                    return text.indexOf('✓ Disponible') !== -1 || text === '✓ Disponible';
                });

                // Eliminar badges duplicados (mantener solo el primero de cada tipo)
                if (allBadgesInsufficient.length > 1) {
                    allBadgesInsufficient.slice(1).remove();
                }
                if (allBadgesDisponible.length > 1) {
                    allBadgesDisponible.slice(1).remove();
                }

                var badgeInsufficient = saldoTotalContainer.find('span.label.label-danger').first();
                var badgeDisponible = saldoTotalContainer.find('span').filter(function() {
                    var text = $(this).text().trim();
                    return text.indexOf('✓ Disponible') !== -1 || text === '✓ Disponible';
                }).first();

                if (isInsufficient) {
                    // Ocultar todos los badges Disponible
                    badgeDisponible.hide();
                    // Mostrar badge Insuficiente
                    if (badgeInsufficient.length === 0) {
                        // Crear badge si no existe (dentro del contenedor, después del div del saldo)
                        var parentDiv = saldoTotalElement.parent();
                        var newBadge = $('<span class="label label-danger" title="Saldo total insuficiente (mínimo según planta Pla_Smi: $' + minTxt + ')" style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; padding: 3px 8px; margin-left: 6px; border-radius: 10px; background-color: #dc3545; border: 1px solid #b02a37;"><i class="glyphicon glyphicon-exclamation-sign" style="font-size: 11px;"></i> Insuficiente</span>');
                        parentDiv.after(newBadge);
                    } else {
                        badgeInsufficient.show();
                    }
                } else {
                    // Ocultar todos los badges Insuficiente
                    badgeInsufficient.hide();
                    // Mostrar badge Disponible
                    if (badgeDisponible.length === 0) {
                        // Crear badge si no existe (dentro del contenedor, después del div del saldo)
                        var parentDiv = saldoTotalElement.parent();
                        var newBadge = $('<span style="font-size: 9px; color: #0f5132; font-weight: 600; margin-left: 6px; padding: 2px 6px; background: rgba(255,255,255,0.5); border-radius: 6px;">✓ Disponible</span>');
                        parentDiv.after(newBadge);
                    } else {
                        badgeDisponible.show();
                    }
                }
            } else {
                // Si no encuentra el contenedor con estilo inline, aplicar estilo directo
                saldoTotalElement.css('color', (saldoMinPla > 0 && saldoTotal < saldoMinPla) ? '#dc3545' : '#198754');
            }
        }

        var saldoEnCeroGlobal = (Math.abs(saldoTotal) < 0.00001);
        var insufPorMin = saldoEnCeroGlobal || (saldoMinPla > 0 && saldoTotal < saldoMinPla);
        // Actualizar el input #saldo con el mismo valor que saldo_total (sin comas para formato numérico)
        if (saldoInputElement.length > 0) {
            saldoInputElement.val(saldoTotal.toFixed(2));
            saldoInputElement.css('color', insufPorMin ? '#dc3545' : '');
            saldoInputElement.attr('title', insufPorMin ? ('Saldo insuficiente (mínimo Pla_Smi: $' + minTxt + ')') : '');
        }

        // Actualizar estado del botón Nuevo
        if (btnNuevoElement.length > 0) {
            if (insufPorMin) {
                btnNuevoElement.prop('disabled', true).attr('title', 'No se puede generar manifiesto: saldo total en cero o insuficiente (mínimo Pla_Smi: ' + minTxt + ')');
                btnNuevoElement.removeAttr('onclick');
            } else {
                btnNuevoElement.prop('disabled', false).removeAttr('title');
                btnNuevoElement.attr('onclick', 'abrirModalTurno();');
            }
        }

        // Si existe btnGuardar, también actualizarlo
        var btnGuardarElement = $('#btnGuardar');
        if (btnGuardarElement.length > 0) {
            if (insufPorMin) {
                btnGuardarElement.prop('disabled', true).attr('title', 'Saldo total insuficiente para guardar el manifiesto');
            } else {
                btnGuardarElement.prop('disabled', false).removeAttr('title');
            }
        }
    }
}

// Función para obtener los saldos del servidor
function obtenerSaldos(plaCod, cliCod) {
    var data = { getSaldosAjax: true };
    if (!$.isEmpty(plaCod)) {
        data.getSaldosPla_Cod = plaCod;
    }
    if (!$.isEmpty(cliCod)) {
        data.getSaldosCli_Cod = cliCod;
    }
    $.post('', data, function(resp) {
        if (resp['success'] && resp['saldos']) {
            actualizarSaldos(resp['saldos']);
        }
    }, 'json').fail(function() {
        console.error('Error al obtener los saldos actualizados');
    });
}

// Función para anular manifiesto
function anularManifiesto(Man_Cod) {
    $.createDialogConfirm('¿Está seguro que desea anular este manifiesto?', {Man_Cod: Man_Cod}, function(data) {
        $.post('', {
            anularManifiestoAjax: true,
            Man_Cod: data.Man_Cod
        }, function(resp) {
            if (resp['success']) {
                // Actualizar los saldos
                if (resp['saldos']) {
                    actualizarSaldos(resp['saldos']);
                } else {
                    obtenerSaldos();
                }

                // Refrescar el grid
                $('#searchGrid').trigger('reloadGrid');

                $.alert('El manifiesto ha sido anulado correctamente.');
            } else {
                $.alert('Error al anular el manifiesto: ' + (resp['message'] || resp['error'] || 'Error desconocido'));
            }
        }, 'json').fail(function() {
            $.alert('Error de comunicación con el servidor al anular el manifiesto.');
        });
    });
}

function modificaManifiesto(data) {
    $('#manifiestoForm').setData(data);
    $('#Prs_Ced_Inf').html(data.Prs_Ced);
    $.get('', { dataModificarAjax: true, Cli_Cod: data.Cli_Cod }, function (r) {
        if (!$.isEmpty(r.trans)) { /* Carga empresa Transporte */
            $('#Mat_Cod').empty();
            $('#Mat_Cod').append($('<option>', { value: '', text: 'Seleccione...' }));
            $.each(r.trans, function (i, item) {
                let arr = { value: item.Mat_Cod, text: item.Mat_Des, 'data-mae': item.Mat_Mae, 'data-pco': item.Mat_Pco }
                if (item.Mat_Cod == data.Mat_Cod) arr.selected = 'selected';
                $('#Mat_Cod').append($('<option>', arr));
            });
        }
        if (!$.isEmpty(r.chof)) {  /* carga Chofer */
            $('#Cho_Cod').empty();
            $('#Cho_Cod').append($('<option>', { value: '', text: 'Seleccione...' }));
            $.each(r.chof, function (i, item) {
                let arr = { value: item.Cho_Cod, text: item.nombre, 'data-ced': item.Prs_Ced, 'data-lic': item.Cho_Tli }
                if (item.Cho_Cod == data.Cho_Cod) { arr.selected = 'selected'; $('#lic_cho').val('TIPO ' + item.Cho_Tli) }
                $('#Cho_Cod').append($('<option>', arr));
            });
        }
        if (!$.isEmpty(r.vehi)) {  /* carga Vehiculos */
            $('#Veh_Cod').empty();
            $('#Veh_Cod').append($('<option>', { value: '', text: 'Seleccione...' }));
            $.each(r.vehi, function (i, item) {
                let arr = { value: item.Veh_Cod, text: item.Veh_Pla + ' - ' + item.Veh_Mar, 'data-peso': item.Veh_Cap, 'data-marca': item.Veh_Mar }
                if (item.Veh_Cod == data.Veh_Cod) arr.selected = 'selected';
                $('#Veh_Cod').append($('<option>', arr));
            });
        }

        if (!$.isEmpty(r.antic)) /* Carga el anticipo actual */
            $('#saldo').val((r.antic.saldo * 1).toFixed(2));
    }, 'json')
        .fail(function (error) {
            console.log("El Servidor ha fallado en responder!");
        });
    $('#documentoSearch').moveComp('#documentoUpdate');
}
/****************************************************************/
/************************** jose cumbicos ***********************/

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

/********************* fin *******************/


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
    var inicio = selectedOption.data('inicio');
    var fin = selectedOption.data('fin');

    // SIEMPRE aplicar límites del período seleccionado (sin excepciones)
    if (inicio && fin) {
        var fechaInicio = new Date(inicio);
        var fechaFin = new Date(fin);

        // Establecer límites estrictos en ambos datepickers
        $("#txt_fec_ini").datepicker("option", {
            minDate: fechaInicio,
            maxDate: fechaFin
        });
        $("#txt_fec_fin").datepicker("option", {
            minDate: fechaInicio,
            maxDate: fechaFin
        });

        // Si las fechas actuales están fuera del rango, ajustarlas al rango
        var fechaIniActual = $("#txt_fec_ini").datepicker("getDate");
        var fechaFinActual = $("#txt_fec_fin").datepicker("getDate");

        if (fechaIniActual && (fechaIniActual < fechaInicio || fechaIniActual > fechaFin)) {
            $("#txt_fec_ini").datepicker("setDate", fechaInicio);
        }
        if (fechaFinActual && (fechaFinActual < fechaInicio || fechaFinActual > fechaFin)) {
            $("#txt_fec_fin").datepicker("setDate", fechaFin);
        }

        // También usar dateLimits si existe (método adicional de validación)
        if ($.fn.dateLimits) {
            $('#txt_fec_ini').dateLimits(inicio, fin);
            $('#txt_fec_fin').dateLimits(inicio, fin);
        }
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
    $('#Pec_Cod').on('change', function () {
        var sel_fecha = $(this).find('option:selected');
        var inicio = sel_fecha.data('inicio');
        var fin = sel_fecha.data('fin');

        // Si hay datos de período, usar esos valores
        if (inicio && fin) {
            // Convertir fechas a objetos Date para los datepickers
            var fechaInicio = new Date(inicio);
            var fechaFin = new Date(fin);

            // Establecer límites en ambos datepickers (minDate y maxDate)
            $('#txt_fec_ini').datepicker("option", {
                minDate: fechaInicio,
                maxDate: fechaFin
            });
            $('#txt_fec_fin').datepicker("option", {
                minDate: fechaInicio,
                maxDate: fechaFin
            });

            // También usar dateLimits si existe (método adicional de validación)
            if ($.fn.dateLimits) {
                $('#txt_fec_ini').dateLimits(inicio, fin);
                $('#txt_fec_fin').dateLimits(inicio, fin);
            }

            // Establecer fecha inicial: día y mes actual, año del período (parsear 'hoy' como fecha local)
            var partesHoy2 = String(hoy).split('-');
            let fecha = partesHoy2.length === 3
                ? new Date(parseInt(partesHoy2[0], 10), parseInt(partesHoy2[1], 10) - 1, parseInt(partesHoy2[2], 10))
                : new Date();
            var diaActual = fecha.getDate();
            var mesActual = fecha.getMonth(); // 0-11

            // Parsear fechas del período para obtener el año
            var partesInicio = inicio.split('-');
            var partesFin = fin.split('-');
            var añoPeriodo = parseInt(partesInicio[0]);

            // Crear fecha con día y mes actual, pero año del período
            var fechaInicial = new Date(añoPeriodo, mesActual, diaActual);

            // Crear objetos Date para comparación (usando componentes locales)
            var fechaInicioObj = new Date(parseInt(partesInicio[0]), parseInt(partesInicio[1]) - 1, parseInt(partesInicio[2]));
            var fechaFinObj = new Date(parseInt(partesFin[0]), parseInt(partesFin[1]) - 1, parseInt(partesFin[2]));

            // Verificar que la fecha esté dentro del rango del período
            if (fechaInicial < fechaInicioObj) {
                fechaInicial = new Date(fechaInicioObj);
            } else if (fechaInicial > fechaFinObj) {
                fechaInicial = new Date(fechaFinObj);
            }

            // Formatear fecha para el input (YYYY-MM-DD)
            var año = fechaInicial.getFullYear();
            var mes = String(fechaInicial.getMonth() + 1).padStart(2, '0');
            var dia = String(fechaInicial.getDate()).padStart(2, '0');
            var fechaFormato = año + '-' + mes + '-' + dia;

            // Establecer las fechas
            // Primero establecer el valor del input
            $("#txt_fec_ini").val(fechaFormato);
            $("#txt_fec_fin").val(fechaFormato);

            // Luego establecer la fecha en el datepicker
            try {
                $('#txt_fec_ini').datepicker("setDate", fechaInicial);
                $('#txt_fec_fin').datepicker("setDate", fechaInicial);
            } catch(e) {
                // Si falla, intentar con el formato de string
                $('#txt_fec_ini').datepicker("setDate", fechaFormato);
                $('#txt_fec_fin').datepicker("setDate", fechaFormato);
            }

            // Forzar actualización del valor del input después de setDate
            setTimeout(function() {
                var fechaIni = $('#txt_fec_ini').datepicker("getDate");
                var fechaFin = $('#txt_fec_fin').datepicker("getDate");
                if (fechaIni) {
                    var año = fechaIni.getFullYear();
                    var mes = String(fechaIni.getMonth() + 1).padStart(2, '0');
                    var dia = String(fechaIni.getDate()).padStart(2, '0');
                    $("#txt_fec_ini").val(año + '-' + mes + '-' + dia);
                }
                if (fechaFin) {
                    var año = fechaFin.getFullYear();
                    var mes = String(fechaFin.getMonth() + 1).padStart(2, '0');
                    var dia = String(fechaFin.getDate()).padStart(2, '0');
                    $("#txt_fec_fin").val(año + '-' + mes + '-' + dia);
                }
            }, 100);

            $('#txt_fec_ini').trigger('change');
            $('#txt_fec_fin').trigger('change');
        }
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
            { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } }
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


function busquedaAjax() {
    if (!gridManifiestoInicializado) {
        createGrid();
        gridManifiestoInicializado = true;
    }
    //manifiestoAjax
    //searchGrid
    $('#searchGrid').Search('#searchManifiesto', 'manifiestoAjax');
}

// Función para buscar y actualizar saldos
function buscarYActualizarSaldos() {
    // Ejecutar la búsqueda
    busquedaAjax();
    // Actualizar los saldos después de un breve delay para asegurar que la búsqueda se complete
    setTimeout(function() {
        obtenerSaldos();
    }, 500);
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



$('#cliDialog').createDialog({
    icon: 'search',
    title: "Busqueda Cliente",
    width: 500,
    height: 430,
    autoOpen: false,
    modal: true,
});

$(function () {
    $.createSearchDialog('cliDialog', [
        { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
        { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Cliente', name: 'nombre', width: 100 },
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        {
            label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton',
            formatoptions: {
                action: selectCliente
            }
        }
    ], null, null, null, { headertitles: true },
        { title: 'Cliente', text: 'searchCli' });
});




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

/****************************************************************/
/****************** FUNCIONES PARA TURNOS ***********************/
/****************************************************************/

var limitePorTurno = 25; // Valor por defecto, se actualiza desde el servidor

// Obtener fecha actual en formato YYYY-MM-DD
function getFechaActual(){
    let hoy = new Date();
    let año = hoy.getFullYear();
    let mes = String(hoy.getMonth() + 1).padStart(2, '0');
    let dia = String(hoy.getDate()).padStart(2, '0');
    return año + '-' + mes + '-' + dia;
}

// Abrir modal de selección de turno
function abrirModalTurno(){
    turnoSeleccionado = null;
    let fechaActual =hoy;

    // Siempre usar fecha actual
    $('#turno_fecha').val(fechaActual);

    $('#turnoDialog').dialog('open');
    cargarTurnosDisponibles();
}

// Cargar turnos disponibles (siempre usa fecha actual)
function cargarTurnosDisponibles(){
    let fechaActual = hoy;
    $('#turno_fecha').val(fechaActual);

    // Mostrar loading
    $('#turnosContainer').html('<div class="text-center" style="padding: 30px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Cargando turnos disponibles...</p></div>');

    $.get('', { turnosDisponiblesAjax: true, fecha: fechaActual }, function(r){
        if(r.success === true){
            renderizarTurnos(r.turnos, r.fecha, r.hora_actual);
        } else if(r.sin_turnos === true){
            // No hay turnos planificados - mostrar alerta y cerrar modal
            $.alert(r.mensaje || 'No hay turnos planificados para la fecha seleccionada. Por favor, configure los turnos primero.', function(){
                $('#turnoDialog').dialog('close');
            });
        } else {
            $('#turnosContainer').html('<div class="no-turnos"><i class="glyphicon glyphicon-warning-sign"></i><p>Error al cargar los turnos</p></div>');
        }
    }, 'json')
    .fail(function(error){
        $('#turnosContainer').html('<div class="no-turnos"><i class="glyphicon glyphicon-warning-sign"></i><p>Error de conexión con el servidor</p></div>');
    });
}

// Renderizar los turnos en el contenedor
function renderizarTurnos(turnos, fecha, horaActual){
    if(!turnos || turnos.length === 0){
        $('#turnosContainer').html('<div class="no-turnos"><i class="glyphicon glyphicon-calendar"></i><p>No hay turnos configurados para esta fecha</p></div>');
        return;
    }

    let html = '';
    let totalDisponibles = 0;
    let totalOcupados = 0;
    let turnosActivos = 0;
    let turnosLlenos = 0;
    let turnosBloqueados = 0;
    let turnoActualIdx = -1;

    turnos.forEach(function(turno, idx){
        // Usar el límite individual del turno (Tud_Cup de manifiesto_turnos_det)
        let limiteTurno = turno.limite || 25;
        // Denominador para cupos-ocupados: Tud_Cup para plantas con reservas; Tud_Cup - reservas_total para plantas sin reservas
        // cupos-ocupados: siempre Tud_Cup (no mostrar conteo de reservas a plantas sin reserva)
        let limiteOcupadosDisplay = turno.limite_ocupados_display !== undefined ? turno.limite_ocupados_display : limiteTurno;
        // Barra de progreso: plantas sin reserva usan (Tud_Cup - reservas)
        let denominadorBarra = turno.denominador_barra != null && turno.denominador_barra !== '' ? turno.denominador_barra : limiteTurno;
        let disponibles = turno.disponibles || 0;
        let disponiblesDisplay = (turno.disponibles_display != null && turno.disponibles_display !== '') ? turno.disponibles_display : (disponibles != null ? disponibles : limiteTurno);
        let ocupados = turno.ocupados || 0;
        // Plantas sin reserva: numerador = ocupados no por reserva (ocupados - reservas totales)
        let ocupadosDisplay = (turno.ocupados_display != null && turno.ocupados_display !== '') ? turno.ocupados_display : ocupados;
        let reservados = turno.reservados || 0;
        let estado = turno.estado || 'bloqueado';
        let habilitado = turno.habilitado || false;
        let turnoActivoConfig = turno.turno_activo_config !== false;

        // Solo sumar a totales si el turno está activo en configuración y no cerrado
        if(turnoActivoConfig && estado !== 'cerrado'){
            totalDisponibles += disponibles;
            totalOcupados += ocupados;
        }

        // Contar por estado
        if(estado === 'activo'){
            turnosActivos++;
            turnoActualIdx = idx;
        } else if(estado === 'lleno'){
            turnosLlenos++;
        } else if(estado === 'deshabilitado' || estado === 'cerrado'){
            // No contar ni mostrar los deshabilitados y cerrados
        } else {
            turnosBloqueados++;
        }

        // NO mostrar turnos cerrados (pero SÍ mostrar los deshabilitados)
        if(estado === 'cerrado'){
            return; // Saltar este turno
        }

        // Determinar clase y estado visual según el nuevo sistema
        let claseCard = '';
        let claseEstado = '';
        let textoEstado = '';
        let onclick = '';
        // Barra de progreso: usar ocupadosDisplay (plantas sin reserva = ocupados - reservas)
        let denominadorBarraNum = (typeof denominadorBarra === 'number' ? denominadorBarra : parseInt(denominadorBarra, 10)) || limiteTurno;
        let porcentajeOcupado = denominadorBarraNum > 0 ? Math.min(100, (ocupadosDisplay / denominadorBarraNum) * 100) : 0;

        if(estado === 'deshabilitado'){
            // Turno deshabilitado manualmente en configuración
            claseCard = 'deshabilitado';
            claseEstado = 'deshabilitado';
            textoEstado = 'DESHABILITADO';
        } else if(estado === 'activo'){
            // Turno activo - se puede seleccionar
            claseCard = 'disponible activo';
            let tudCod = turno.Tud_Cod !== undefined && turno.Tud_Cod !== null ? turno.Tud_Cod : null;
            let celCod = turno.Cel_Cod !== undefined && turno.Cel_Cod !== null ? turno.Cel_Cod : null;
            // Para pasar null en onclick, debemos usar null sin comillas, o usar 'null' como string
            let tudCodParam = tudCod !== null && tudCod !== undefined ? tudCod : 'null';
            let celCodParam = celCod !== null && celCod !== undefined ? celCod : 'null';
            onclick = 'seleccionarTurno(' + turno.id + ', \'' + turno.hora_inicio + '\', \'' + turno.hora_fin + '\', \'' + fecha + '\', ' + disponibles + ', ' + tudCodParam + ', \'' + horaActual + '\', ' + celCodParam + ')';

            if(porcentajeOcupado >= 80){
                claseEstado = 'casi-lleno';
                textoEstado = 'CASI LLENO';
            } else if(porcentajeOcupado >= 50){
                claseEstado = 'medio';
                textoEstado = 'ACTIVO';
            } else {
                claseEstado = 'libre';
                textoEstado = 'ACTIVO';
            }
        } else if(estado === 'lleno'){
            // Turno lleno
            claseCard = 'lleno';
            claseEstado = 'ocupado';
            textoEstado = 'LLENO';
        } else {
            // Turno bloqueado - esperando que se llene el anterior
            claseCard = 'bloqueado';
            claseEstado = 'bloqueado';
            textoEstado = 'EN ESPERA';
        }

        html += '<div class="turno-card ' + claseCard + '" ' + (onclick ? 'onclick="' + onclick + '"' : '') + '>';
        html += '  <div class="turno-row">';
        html += '    <div class="turno-hora"><i class="glyphicon glyphicon-time"></i> ' + turno.hora_inicio + ' - ' + turno.hora_fin + '</div>';
        html += '    <span class="turno-estado ' + claseEstado + '">' + textoEstado + '</span>';
        html += '  </div>';

        if(estado !== 'deshabilitado'){
            // Barra de progreso de ocupación
            html += '  <div class="turno-progress-container">';
            html += '    <div class="turno-progress-bar" style="width: ' + porcentajeOcupado + '%;"></div>';
            html += '  </div>';

            // Información de cupos (disponibles ya restan cupos reservados por otras plantas)
            html += '  <div class="turno-cupos">';
            html += '    <span class="cupos-disponibles" title="Cupos libres (Tud_Cup menos manifiestos creados y reservas)"><i class="glyphicon glyphicon-ok-circle"></i> ' + disponiblesDisplay + ' disponibles</span>';
            html += '    <span class="cupos-ocupados" title="Manifiestos ocupados / total configurado (Tud_Cup)"><i class="glyphicon glyphicon-user"></i> ' + ocupadosDisplay + '/' + limiteOcupadosDisplay + ' ocupados</span>';
            if (turno.mostrar_reservados === true && reservados > 0) {
                html += '    <span class="cupos-reservados" title="Reservas pendientes de tu planta"><i class="glyphicon glyphicon-bookmark"></i> ' + reservados + ' reserv. pend.</span>';
            }
            html += '  </div>';
        }

        // Mensaje según estado
        let habilitadoPorHora = turno.habilitado_por_hora || false;

        if(estado === 'deshabilitado'){
            html += '  <div class="turno-info turno-deshabilitado"><i class="glyphicon glyphicon-ban-circle"></i> Este turno está deshabilitado</div>';
        } else if(estado === 'activo'){
            if(habilitadoPorHora){
                html += '  <div class="turno-info turno-activo"><i class="glyphicon glyphicon-hand-up"></i> <strong>TURNO EN HORARIO</strong> - Clic para seleccionar</div>';
            } else {
                html += '  <div class="turno-info turno-activo"><i class="glyphicon glyphicon-hand-up"></i> <strong>TURNO ACTUAL</strong> - Clic para seleccionar</div>';
            }
        } else if(estado === 'lleno'){
            html += '  <div class="turno-info turno-lleno"><i class="glyphicon glyphicon-ok"></i> Turno completado</div>';
        } else {
            html += '  <div class="turno-info turno-bloqueado"><i class="glyphicon glyphicon-lock"></i> El Turno se habilitará a las ' + turno.hora_inicio + ' </div>';
        }

        html += '</div>';
    });

    // Agregar resumen mejorado (encabezado fijo)
    let turnoActualTexto = turnoActualIdx >= 0 ? turnos[turnoActualIdx].hora_inicio + ' - ' + turnos[turnoActualIdx].hora_fin : 'Ninguno disponible';
    horaActual = horaActual || '--:--';

    let resumen = '<div id="turnoResumenFijo" style="padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white; position: sticky; top: 0; z-index: 10;">';
    resumen += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">';
    resumen += '  <span style="font-size: 14px; font-weight: bold;"><i class="glyphicon glyphicon-calendar"></i> ' + fecha + '</span>';
    resumen += '  <span style="font-size: 13px; background: rgba(255,255,255,0.3); padding: 4px 10px; border-radius: 15px;"><i class="glyphicon glyphicon-time"></i> ' + horaActual + '</span>';
    resumen += '</div>';
    resumen += '<div style="font-size: 13px; margin-bottom: 8px; padding: 8px; background: rgba(255,255,255,0.2); border-radius: 5px;">';
    resumen += '  <i class="glyphicon glyphicon-flash"></i> <strong>Turno Activo:</strong> ' + turnoActualTexto;
    resumen += '</div>';
    resumen += '<div style="display: flex; flex-wrap: wrap; gap: 8px; font-size: 11px;">';
    resumen += '  <span><i class="glyphicon glyphicon-play"></i> ' + turnosActivos + ' activos</span>';
    resumen += '  <span><i class="glyphicon glyphicon-lock"></i> ' + turnosBloqueados + ' en espera</span>';
    resumen += '</div>';
    resumen += '</div>';

    // Lista de turnos con scroll
    let listaTurnos = '<div id="turnoListaScroll" style="margin-top: 10px;">' + html + '</div>';

    $('#turnosContainer').html(resumen + listaTurnos);
}

function autocompletarPrefijoManGui() {
    var $manGui = $('#Man_Gui');
    if ($manGui.length === 0 || $.trim($manGui.val()) !== '') {
        return;
    }

    var plaCod = parseInt($('#Pla_Cod').val(), 10) || 0;
    if (plaCod <= 0) {
        return;
    }

    $.post('', { getPrefijoManGuiAjax: true, Pla_Cod: plaCod }, function (resp) {
        if (!resp || resp.success !== true || $.isEmpty(resp.prefijo)) {
            return;
        }
        $manGui.val(resp.prefijo + '-').trigger('input');
    }, 'json');
}

// Seleccionar un turno y continuar al formulario
function seleccionarTurno(id, horaInicio, horaFin, fecha, disponibles, tudCod, horaActual, celCod){
    turnoSeleccionado = {
        id: id,
        Tud_Cod: tudCod && tudCod !== 'null' ? tudCod : null,
        Cel_Cod: celCod && celCod !== 'null' ? celCod : null,
        hora_inicio: horaInicio,
        hora_fin: horaFin,
        fecha: fecha,
        disponibles: disponibles,
        hora_actual: horaActual || null
    };

    // Cerrar modal de turnos
    $('#turnoDialog').dialog('close');

    // Aplicar el turno seleccionado al formulario
    $('#Man_Fes').val(fecha);
    // Usar hora_fin para la hora de salida
    $('#Man_Fes_Hor').val(horaActual);

    // Guardar el ID del turno en el campo hidden
    if(tudCod && tudCod !== 'null'){
        $('#Tud_Cod').val(tudCod);
    } else {
        $('#Tud_Cod').val('');
    }

    // Guardar el Cel_Cod en el campo hidden
    // Usar la misma lógica que Tud_Cod
    if(celCod && celCod !== 'null' && celCod !== null){
        $('#Cel_Cod').val(celCod);
    } else {
        $('#Cel_Cod').val('');
    }

    // Calcular hora de llegada: sumar hora actual con Pla_Dis (distancia/duración de la planta)
    let plaDis = $('#Pla_Dis').val();
    let horaLlegada = sumarHoras(horaActual, plaDis);
    $('#Man_Fea').val(fecha);
    $('#Man_Fea_Hor').val(horaLlegada);

    // Recargar vehículos y choferes con estado de bloqueo actualizado
    //recargarVehiculosYChoferes();

    // Ir al formulario de nuevo manifiesto
    $('#documentoSearch').moveComp('#documentoUpdate');
    autocompletarPrefijoManGui();

    // Mostrar mensaje de turno seleccionado
    $.alert('<i class="glyphicon glyphicon-ok-circle" style="color: #28a745;"></i> <strong>Turno seleccionado:</strong><br>' +
            'Fecha: ' + fecha + '<br>' +
            'Horario: ' + horaInicio + ' - ' + horaFin + '<br>' +
            'Cupos disponibles: ' + disponibles);
}

/** Opciones con texto &lt;&lt; En Ruta &gt;&gt; al final del &lt;select&gt; (misma regla que en PHP). */
function ordenarOpcionesEnRutaAlFinal(lista) {
    if (!lista || !lista.length) {
        return lista;
    }
    var arriba = [];
    var abajo = [];
    $.each(lista, function (i, it) {
        var t = (it.texto != null) ? String(it.texto) : '';
        if (t.indexOf('En Ruta') >= 0) {
            abajo.push(it);
        } else {
            arriba.push(it);
        }
    });
    return arriba.concat(abajo);
}

/** Pinta de rojo las opciones cuyo texto contiene "<< En Ruta >>". */
function aplicarEstiloOpcionesEnRuta($select) {
    if (!$select || !$select.length) {
        return;
    }
    $select.find('option').each(function () {
        var t = String($(this).text() || '');
        if (t.indexOf('En Ruta') >= 0) {
            $(this).css({
                'background-color': '#f8d7da',
                'color': '#a00000',
                'font-weight': '700'
            });
        }
    });
}

// Función para recargar vehículos y choferes con estado de bloqueo actualizado
// limpiarSeleccion: si es true, no restaura la selección ni datos derivados (uso: botón refrescar listas)
function recargarVehiculosYChoferes(limpiarSeleccion) {
    limpiarSeleccion = !!limpiarSeleccion;
    return $.post('', { recargarVehiculosChoferesAjax: true }, function(resp) {
        if (resp['success']) {
            // Recargar select de vehículos
            var $selectVehiculo = $('#Veh_Cod');
            var valorSeleccionado = limpiarSeleccion ? '' : $selectVehiculo.val();
            $selectVehiculo.empty();
            $selectVehiculo.append('<option value="">Seleccione...</option>');
            
            if (resp['vehiculos'] && resp['vehiculos'].length > 0) {
                var vehiculosOrden = ordenarOpcionesEnRutaAlFinal(resp['vehiculos']);
                $.each(vehiculosOrden, function(i, vehiculo) {
                    var disabled = vehiculo.bloqueado ? 'disabled' : '';
                    var option = $('<option>', {
                        value: vehiculo.Veh_Cod,
                        text: vehiculo.texto,
                        'data-veh_cap': vehiculo.Veh_Cap,
                        'data-mat_cod': vehiculo.Mat_Cod,
                        'data-mat_des': vehiculo.Mat_Des
                    });
                    if (disabled) {
                        option.prop('disabled', true);
                    }
                    $selectVehiculo.append(option);
                });
                aplicarEstiloOpcionesEnRuta($selectVehiculo);
            }
            
            // Restaurar valor seleccionado si aún existe
            if (valorSeleccionado) {
                $selectVehiculo.val(valorSeleccionado);
            }
            if (limpiarSeleccion) {
                $selectVehiculo.val('');
                $('#Man_Des_Inf').empty();
                $('#Man_Pes').val('');
            }

            // Recargar select de choferes
            var $selectChofer = $('#Cho_Cod');
            var valorSeleccionadoChofer = limpiarSeleccion ? '' : $selectChofer.val();
            $selectChofer.empty();
            $selectChofer.append('<option value="">Seleccione...</option>');

            if (resp['choferes'] && resp['choferes'].length > 0) {
                var choferesOrden = ordenarOpcionesEnRutaAlFinal(resp['choferes']);
                $.each(choferesOrden, function(i, chofer) {
                    var disabled = chofer.bloqueado ? 'disabled' : '';
                    var option = $('<option>', {
                        value: chofer.Cho_Cod,
                        text: chofer.texto,
                        'data-lic': chofer.Cho_Tli,
                        'data-ced': chofer.Prs_Ced
                    });
                    if (disabled) {
                        option.prop('disabled', true);
                    }
                    $selectChofer.append(option);
                });
                aplicarEstiloOpcionesEnRuta($selectChofer);
            }

            // Restaurar valor seleccionado si aún existe
            if (valorSeleccionadoChofer) {
                $selectChofer.val(valorSeleccionadoChofer);
                // Actualizar campos relacionados
                var $optionSeleccionada = $selectChofer.find(':selected');
                $('#Prs_Ced_Cho').html($optionSeleccionada.data('ced') || ' - ');
                $('#lic_cho').val('TIPO ' + ($optionSeleccionada.data('lic') || ''));
            }
            if (limpiarSeleccion) {
                $selectChofer.val('');
                $('#Prs_Ced_Cho').html(' - ');
                $('#lic_cho').val('');
            }
        }
    }, 'json').fail(function() {
        console.error('Error al recargar vehículos y choferes');
    });
}
// Convertir a segundos
    function aSegundos(hora) {
        let partes = hora.split(":").map(Number);
        let h = partes[0] || 0;
        let m = partes[1] || 0;
        let s = partes[2] || 0;
        return h * 3600 + m * 60 + s;
    }
function sumarHoras(hora, horasASumar) {
    

    let totalSegundos = aSegundos(hora) + aSegundos(horasASumar);

    // Ajustar a formato 24 horas
    totalSegundos = ((totalSegundos % 86400) + 86400) % 86400;

    let h = Math.floor(totalSegundos / 3600);
    let m = Math.floor((totalSegundos % 3600) / 60);
    let s = totalSegundos % 60;

    return [
        h.toString().padStart(2, "0"),
        m.toString().padStart(2, "0")/*,
        s.toString().padStart(2, "0")*/
    ].join(":");
}

// Función auxiliar para sumar horas
function sumarHora(hora1, hora2){
    const h1 = moment.duration(hora1);
    const h2 = moment.duration(hora2);
    const total = h1.add(h2);
    const horas = Math.floor(total.asHours());
    const minutos = total.minutes();

    return (`${horas}:${minutos.toString().padStart(2, '0')}`);
}


/****************************************************************/
/***************** GENERACIÓN DE CERTIFICADO ********************/
/****************************************************************/

$(function() {
    $("#certificadoDialog").createDialog({ width: 600, height: 260, icon: 'certificate' });
});

function abrirCertificadoModal() {
    $('#certificadoForm')[0].reset();
    $('#Cert_Cli_Ced_Span').text('---');
    $('#Cert_Pla_Cod').empty().append('<option value="">Seleccione planta...</option>');
    
    // Establecer fechas por defecto: Mes actual
    var date = new Date();
    var firstDay = new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
    var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0];
    $('#Cert_Fec_Des').val(firstDay);
    $('#Cert_Fec_Has').val(lastDay);

    volverAmbienteCertificado(); // Asegurar que inicie en el ambiente de formulario
    $('#certificadoDialog').dialog('open');
}

function cambiarAmbienteBusqueda() {
    // Cambiar dimensiones del modal
    $("#certificadoDialog").dialog("option", "width", 800);
    $("#certificadoDialog").dialog("option", "height", 430);
    $("#certificadoDialog").dialog("option", "position", { my: "center", at: "center", of: window });

    // Cambiar visibilidad de ambientes
    $('#ambienteCertificado').hide();
    $('#ambienteBusqueda').show();

    // Inicializar el grid si no se ha hecho
    if (!$('#Cert_tableResult').hasClass('ui-jqgrid-btable')) {
        $('#Cert_tableResult').createGrid({
            height: 220,
            colModel: [
                { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
                { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Cliente', name: 'nombre', width: 90 },
                { label: 'Planta(s)', name: 'plantas', width: 80 },
                { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', 
                    formatoptions: { 
                        action: selectClienteCertificado
                    } 
                }
            ]
        }, true, '#Cert_tableResultPager', { view: false, refresh: true });
    }
}

function volverAmbienteCertificado() {
    // Revertir dimensiones del modal
    $("#certificadoDialog").dialog("option", "width", 600);
    $("#certificadoDialog").dialog("option", "height", 260);
    $("#certificadoDialog").dialog("option", "position", { my: "center", at: "center", of: window });

    // Cambiar visibilidad de ambientes
    $('#ambienteBusqueda').hide();
    $('#ambienteCertificado').show();
}

function buscarClientesCertificado() {
    var radioVal = $('input[name="Cert_op_opciones"]:checked').val();
    var searchVal = $('#Cert_search').val();
    
    $('#Cert_tableResult').jqGrid('setGridParam', {
        url: '', // El mismo archivo
        datatype: 'json',
        postData: {
            clientesAjax: true,
            op_opciones: radioVal,
            search: searchVal
        }
    }).gridUpdate(1);
}

function selectClienteCertificado(cli) {
    var data = Array.isArray(cli) ? cli[0] : cli;
    
    $('#Cert_Cli_Ced').val(data.Prs_Ced);
    $('#Cert_Cli_Ced_Span').text(data.Prs_Ced);
    $('#Cert_Cli_Nom').val(data.nombre);
    $('#Cert_Cli_Cod').val(data.Cli_Cod);
    $('#Cert_Prs_Cod').val(data.Prs_Cod);
    
    // Cargar plantas del cliente
    cargarPlantasCertificado(data.Cli_Cod);
    
    volverAmbienteCertificado();
}

function cargarPlantasCertificado(Cli_Cod) {
    $.get("", {listPlantasAjax: true, Cli_Cod: Cli_Cod}, function(r) {
        if(r['success'] === true){
            $('#Cert_Pla_Cod').empty();
            $('#Cert_Pla_Cod').append('<option value="">Seleccione planta...</option>');
            if(r.plantas && r.plantas.length > 0){
                $.each(r.plantas, function(i, item){
                    $('#Cert_Pla_Cod').append("<option value='"+ item.Pla_Cod +"'>"+ item.Pla_Nom +"</option>");
                });

                // Si solo tiene una planta, seleccionarla automáticamente
                if(r.plantas.length === 1){
                    $('#Cert_Pla_Cod').val(r.plantas[0].Pla_Cod).trigger('change');
                }
            }
        }
    }, 'json');
}

function impCertificadoRango() {
    var data = $('#certificadoForm').getData();
    if ($.isEmpty(data.Cert_Cli_Cod)) {
        $.alert('Debe seleccionar un cliente.');
        return;
    }
    if ($.isEmpty(data.Cert_Pla_Cod)) {
        $.alert('Debe seleccionar una planta.');
        return;
    }
    if ($.isEmpty(data.Cert_Fec_Des) || $.isEmpty(data.Cert_Fec_Has)) {
        $.alert('Debe seleccionar el rango de fechas.');
        return;
    }

    // Si se requiere firma digital o PDF, abrir el nuevo reporte
    if ($('#Cert_Firmar').is(':checked')) {
        window.open('man_rep_certificado_rango_pdf.php?Cli_Cod=' + data.Cert_Cli_Cod + '&Pla_Cod=' + data.Cert_Pla_Cod + '&Fec_Des=' + data.Cert_Fec_Des + '&Fec_Has=' + data.Cert_Fec_Has, '_blank');
    } else {
    // Por ahora, abrir una nueva ventana con parámetros
    window.open('man_rep_certificado_rango.php?Cli_Cod=' + data.Cert_Cli_Cod + '&Pla_Cod=' + data.Cert_Pla_Cod + '&Fec_Des=' + data.Cert_Fec_Des + '&Fec_Has=' + data.Cert_Fec_Has, '_blank');
    }
}
