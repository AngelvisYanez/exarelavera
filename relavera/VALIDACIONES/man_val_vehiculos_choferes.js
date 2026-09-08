/**
 * Validación y grids para man_pri_vehiculos_choferes - Vehículos y Choferes por Planta
 */
$(function () {
    createGridChoferesPlanta();
    createGridVehiculosPlanta();

    // Botón buscar choferes
    $('#btnBuscarChofer').on('click', function () {
        actualizarGridChoferesPlanta();
    });
    $('#searchChofer').on('keypress', function (e) {
        if (e.which === 13) actualizarGridChoferesPlanta();
    });

    // Botón buscar vehículos
    $('#btnBuscarVehiculo').on('click', function () {
        actualizarGridVehiculosPlanta();
    });
    $('#searchVehiculo').on('keypress', function (e) {
        if (e.which === 13) actualizarGridVehiculosPlanta();
    });
});

function createGridChoferesPlanta() {
    $('#gridChoferes').createGrid({
        caption: '',
        url: window.location.href,
        postData: { listChoferesPlantaGridAjax: 1 },
        height: 350,
        rowNum: 50,
        rowList: [10, 25, 50, 100, -1],
        colModel: [
            { label: 'Código', name: 'Cho_Cod', key: true, hidden: true, width: 50, align: 'center' },
            { label: 'Cédula', name: 'Prs_Ced', width: 60, align: 'center' },
            { label: 'Nombre', name: 'nombre', width: 100 },
            { label: 'Planta', name: 'planta', width: 100 },
            {
                label: 'Sanciones',
                name: 'cant_sanciones',
                width: 70,
                align: 'center',
                formatter: function (v, o, r) {
                    var n = parseInt(v || 0, 10);
                    if (n > 0) {
                        return '<button type="button" class="btn btn-xs btn-warning btnVerSancionesChofer" data-cho_cod="' + (r.Cho_Cod || '') + '" title="Ver sanciones"><i class="glyphicon glyphicon-alert"></i> ' + n + '</button>';
                    }
                    return '0';
                }
            }
        ],
        viewrecords: true,
        jsonReader: { root: "rows", page: "page", total: "total", records: "records", repeatitems: false },
        loadComplete: function () {
            customizarTextoVerTodos('#pagerChoferes');
        }
    }, false, '#pagerChoferes', { refresh: true, view: false }).gridButtonsAdd([
        {
            caption: 'Exportar Excel',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                $('#gridChoferes').jqGrid('exportGridExcel', {
                    nombre: 'Choferes_Planta',
                    hoja: 'HOJA 1',
                    footer: true,
                    removeHiddens: true,
                    removeCols: [5]
                });
            }
        }
    ]);

    // Delegar clic en botón de sanciones (el grid carga después)
    $(document).off('click', '.btnVerSancionesChofer').on('click', '.btnVerSancionesChofer', function () {
        var Cho_Cod = $(this).data('cho_cod');
        if (!Cho_Cod) return;
        cargarSancionesChofer(Cho_Cod);
    });

    $('#btnExcelChoferes').on('click', function () {
        $('#gridChoferes').jqGrid('exportGridExcel', {
            nombre: 'Choferes_Planta',
            hoja: 'HOJA 1',
            footer: true,
            removeHiddens: true,
            removeCols: [5]
        });
    });

    $('#btnImprimirChoferes').on('click', function () {
        imprimirChoferes();
    });
}

function createGridVehiculosPlanta() {
    $('#gridVehiculos').createGrid({
        caption: '',
        url: window.location.href,
        postData: { listVehiculosPlantaGridAjax: 1 },
        height: 350,
        rowNum: 50,
        rowList: [10, 25, 50, 100, -1],
        colModel: [
            { label: 'Código', name: 'Veh_Cod', key: true, hidden: true, width: 50, align: 'center' },
            { label: 'Placa', name: 'Veh_Pla', width: 50, align: 'center' },
            { label: 'Marca', name: 'Veh_Mar', width: 100 },
            { label: 'Color', name: 'Veh_Col', width: 70, align: 'center' },
            { label: 'Capacidad (Kg)', name: 'Veh_Cap', width: 60, align: 'right' },
            { label: 'Planta', name: 'Pla_Nom', width: 140 },
            {
                label: 'Sanciones',
                name: 'cant_sanciones',
                width: 70,
                align: 'center',
                formatter: function (v, o, r) {
                    var n = parseInt(v || 0, 10);
                    if (n > 0) {
                        return '<button type="button" class="btn btn-xs btn-warning btnVerSancionesVehiculo" data-veh_cod="' + (r.Veh_Cod || '') + '" title="Ver sanciones"><i class="glyphicon glyphicon-alert"></i> ' + n + '</button>';
                    }
                    return '0';
                }
            }
        ],
        viewrecords: true,
        jsonReader: { root: "rows", page: "page", total: "total", records: "records", repeatitems: false },
        loadComplete: function () {
            customizarTextoVerTodos('#pagerVehiculos');
        }
    }, false, '#pagerVehiculos', { refresh: true, view: false }).gridButtonsAdd([
        {
            caption: 'Exportar Excel',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                $('#gridVehiculos').jqGrid('exportGridExcel', {
                    nombre: 'Vehiculos_Planta',
                    hoja: 'HOJA 1',
                    footer: true,
                    removeHiddens: true,
                    removeCols: [7]
                });
            }
        }
    ]);

    $(document).off('click', '.btnVerSancionesVehiculo').on('click', '.btnVerSancionesVehiculo', function () {
        var Veh_Cod = $(this).data('veh_cod');
        if (!Veh_Cod) return;
        cargarSancionesVehiculo(Veh_Cod);
    });

    $('#btnExcelVehiculos').on('click', function () {
        $('#gridVehiculos').jqGrid('exportGridExcel', {
            nombre: 'Vehiculos_Planta',
            hoja: 'HOJA 1',
            footer: true,
            removeHiddens: true,
            removeCols: [7]
        });
    });

    $('#btnImprimirVehiculos').on('click', function () {
        imprimirVehiculos();
    });
}

function actualizarGridChoferesPlanta() {
    var op_opciones = $('#opChofer').val() || 'd';
    var search = $('#searchChofer').val() || '';
    $('#gridChoferes').jqGrid('setGridParam', {
        postData: {
            listChoferesPlantaGridAjax: 1,
            op_opciones: op_opciones,
            search: search
        },
        page: 1
    }).trigger('reloadGrid');
}

function actualizarGridVehiculosPlanta() {
    var op_opciones = $('#opVehiculo').val() || 'p';
    var search = $('#searchVehiculo').val() || '';
    $('#gridVehiculos').jqGrid('setGridParam', {
        postData: {
            listVehiculosPlantaGridAjax: 1,
            op_opciones: op_opciones,
            search: search
        },
        page: 1
    }).trigger('reloadGrid');
}

function imprimirChoferes() {
    var tablaHtml = $('#gridChoferes').jqGrid('exportGridHTML', {
        footer: true,
        generated: false,
        removeHiddens: true,
        removeCols: [5] // Ocultar columna Sanciones
    });
    $('#tablaReporteChoferes').html(tablaHtml);
    normalizarRutasImagenesReporte($('#imprimirChoferes'));
    $('#imprimirChoferes').printElement();
} 

function imprimirVehiculos() {
    var tablaHtml = $('#gridVehiculos').jqGrid('exportGridHTML', {
        footer: true,
        generated: false,
        removeHiddens: true,
        removeCols: [7] // Ocultar columna Sanciones
    });
    $('#tablaReporteVehiculos').html(tablaHtml);
    normalizarRutasImagenesReporte($('#imprimirVehiculos'));
    $('#imprimirVehiculos').printElement();
}

// printElement puede perder rutas relativas de imágenes (logo); convertirlas a absolutas.
function normalizarRutasImagenesReporte($contenedor) {
    if (!$contenedor || !$contenedor.length) return;
    $contenedor.find('img').each(function () {
        var src = $(this).attr('src');
        if (!src) return;
        // Saltar data URI y rutas ya absolutas
        if (/^(data:|https?:|\/\/)/i.test(src)) return;
        try {
            var abs = new URL(src, window.location.href).href;
            $(this).attr('src', abs);
        } catch (e) {
            // no-op
        }
    });
}

function customizarTextoVerTodos(pagerId) {
    var $select = $(pagerId).find('.ui-pg-selbox');
    if (!$select.length) return;
    $select.find('option[value="-1"]').text('Todos');
}

function formatearFechaSancion(f) {
    if (!f) return '-';
    var d = new Date(f.replace(/ /g, 'T'));
    if (isNaN(d.getTime())) return f;
    var dia = ('0' + d.getDate()).slice(-2);
    var mes = ('0' + (d.getMonth() + 1)).slice(-2);
    var anio = d.getFullYear();
    var h = ('0' + d.getHours()).slice(-2);
    var m = ('0' + d.getMinutes()).slice(-2);
    return dia + '/' + mes + '/' + anio + ' ' + h + ':' + m;
}

function renderizarSanciones(r, defTitulo) {
    $('#modalSancionesTitulo').text(r.identificador || defTitulo);
    $('#contenidoSanciones').show();
    $('#sinSancionesMsg').hide();
    $('#tablaSanciones tbody').empty();
    if (r.sanciones && r.sanciones.length > 0) {
        $.each(r.sanciones, function (i, s) {
            var fei = formatearFechaSancion(s.Msa_Fei);
            var fef = formatearFechaSancion(s.Msa_Fef);
            var obs = (s.Msa_Obs || '').trim();
            var obsEsc = $('<div>').text(obs).html();
            var obsShort = obs.length > 100 ? obs.substring(0, 100) + '...' : obs;
            var obsShortEsc = $('<div>').text(obsShort).html();
            var titleAttr = obs.length > 100 ? ' title="' + obsEsc.replace(/"/g, '&quot;') + '"' : '';
            $('#tablaSanciones tbody').append(
                '<tr><td>' + fei + '</td><td>' + fef + '</td><td class="obs-cell"' + titleAttr + '>' + obsShortEsc + '</td></tr>'
            );
        });
    } else {
        $('#contenidoSanciones').hide();
        $('#sinSancionesMsg').show();
    }
}

function cargarSancionesChofer(Cho_Cod) {
    $('#modalSancionesTitulo').text('Chofer');
    $('#contenidoSanciones').show();
    $('#sinSancionesMsg').hide();
    $('#tablaSanciones tbody').empty();
    $('#modalSanciones').modal('show');
    $.get(window.location.href, { getSancionesChoferAjax: 1, Cho_Cod: Cho_Cod }, function (r) {
        renderizarSanciones(r || {}, 'Chofer');
    }, 'json').fail(function () {
        $('#tablaSanciones tbody').append('<tr><td colspan="3" class="text-danger"><i class="glyphicon glyphicon-warning-sign"></i> Error al cargar sanciones</td></tr>');
    });
}

function cargarSancionesVehiculo(Veh_Cod) {
    $('#modalSancionesTitulo').text('Vehículo');
    $('#contenidoSanciones').show();
    $('#sinSancionesMsg').hide();
    $('#tablaSanciones tbody').empty();
    $('#modalSanciones').modal('show');
    $.get(window.location.href, { getSancionesVehiculoAjax: 1, Veh_Cod: Veh_Cod }, function (r) {
        renderizarSanciones(r || {}, 'Vehículo');
    }, 'json').fail(function () {
        $('#tablaSanciones tbody').append('<tr><td colspan="3" class="text-danger"><i class="glyphicon glyphicon-warning-sign"></i> Error al cargar sanciones</td></tr>');
    });
}
