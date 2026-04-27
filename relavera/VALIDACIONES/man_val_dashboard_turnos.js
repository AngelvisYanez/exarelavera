var todasConfiguraciones = [];
var modoVistaBarras = false; // false = tabla, true = barras (tab Por Configuración)
var modoVistaBarrasRango = false; // false = tabla, true = barras (tab Por Rango de Fechas)
var datosDashboardActual = null; // Almacenar datos del dashboard actual para filtrado por fecha
var datosDashboardRango = null; // Datos del tab Por Rango de Fechas
var datosModalManifiestos = null; // Manifiestos del modal actual para exportar a Excel
var cargandoManifiestos = false; // Evitar múltiples peticiones concurrentes al ver manifiestos
var datosDashboardChoferPlaca = null; // { agrupado, tipo_vista } para tab Por Chofer/Placa
var datosModalManifiestosChoferPlaca = null; // modal Ver Manifiestos Chofer/Placa
var chartParetoChoferPlaca = null; // instancia Chart.js Pareto
var datosModalManifiestosChoferPlaca = null; // { manifiestos, turnoInfo, tipoVista } para modal Ver Manifiestos
var datosRankingPlantasChoferPlaca = null; // [ { Pla_Cod, Pla_Nom, total } ] para imprimir/Excel
var fechaInicioChoferPlaca = '';
var fechaFinChoferPlaca = '';
var contextoDashboardTurnos = window.dashboardTurnosContext || { plaCodAsignada: 0, plaNomAsignada: '' };
var datosDashboardEjecutivo = null; // Datos del tab Vista Ejecutiva (CEO) para imprimir informe gerencial
var datosTurnosPorDia = {}; // { fechaId: turnosFecha[] } para gráfico tendencia por día
var modoVistaConfig = 'tabla'; // 'tabla' | 'barras' | 'tendencia' - aplica a todos los días
var primeraCargaConfig = true; // true = solo día actual expandido; false = al hacer clic en Tabla/Barras/Tendencia se expande todo

$(function () {
    aplicarRestriccionTabsPlantero();
    cargarConfiguraciones();
    inicializarFechasChoferPlacaSiAplica();
    
    // Cargar plantas cuando se muestra el tab de rango; inicializar mes actual si no hay fechas
    $('a[href="#tab_rango"]').on('shown.bs.tab', function() {
        cargarPlantasRango();
        if (!$('#fecha_inicio_rango').val() && !$('#fecha_fin_rango').val()) {
            var now = new Date();
            var mesVal = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
            $('#mes_rango').val(mesVal).trigger('change');
        }
    });
    
    // Selector de mes: al elegir un mes, autocompletar Desde/Hasta (el usuario puede modificarlas después)
    $(document).on('change', '#mes_rango', function() {
        var val = $(this).val();
        if (!val) return;
        var parts = val.split('-');
        var year = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10);
        var lastDay = new Date(year, month, 0).getDate();
        var mesStr = String(month).padStart(2, '0');
        $('#fecha_inicio_rango').val(year + '-' + mesStr + '-01');
        $('#fecha_fin_rango').val(year + '-' + mesStr + '-' + String(lastDay).padStart(2, '0'));
    });
    
    // Re-render al cambiar checkbox omitir días sin manifiestos (tab rango)
    $(document).on('change', '#omitir_dias_sin_manifiestos', function() {
        if (datosDashboardRango && datosDashboardRango.dias) {
            mostrarDashboardRango(datosDashboardRango);
        }
    });
    
    // Re-render al cambiar checkbox omitir días sin manifiestos (tab configuración)
    $(document).on('change', '#omitir_dias_sin_manifiestos_config', function() {
        if (datosDashboardActual && datosDashboardActual.length > 0) {
            mostrarDashboard(datosDashboardActual);
        }
    });
    
    // Tab Por Chofer/Placa: al mostrar, sincronizar fechas desde el mes (default: mes vigente)
    $('a[href="#tab_chofer_placa"]').on('shown.bs.tab', function() {
        var mesVal = $('#mes_chofer_placa').val();
        if (!mesVal) {
            var now = new Date();
            mesVal = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
            $('#mes_chofer_placa').val(mesVal);
        }
        sincronizarFechasDesdeMesChoferPlaca(mesVal);
        mostrarAvisoPlantaPlanteroChoferPlaca();
    });
    
    // Mes Chofer/Placa: al elegir mes, autocompletar Desde y Hasta (1º al último día del mes)
    $(document).on('change', '#mes_chofer_placa', function() {
        var val = $(this).val();
        if (val) sincronizarFechasDesdeMesChoferPlaca(val);
    });
    
    // Tab Vista Ejecutiva Global: al mostrar, sincronizar fechas desde el mes (default: mes vigente)
    $('a[href="#tab_ejecutivo"]').on('shown.bs.tab', function() {
        var mesVal = $('#mes_ejecutivo').val();
        if (!mesVal) {
            var now = new Date();
            mesVal = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
            $('#mes_ejecutivo').val(mesVal);
        }
        sincronizarFechasDesdeMesEjecutivo(mesVal);
    });
    
    // Mes ejecutivo: al elegir mes, autocompletar Desde y Hasta (1º al último día del mes)
    $(document).on('change', '#mes_ejecutivo', function() {
        var val = $(this).val();
        if (val) sincronizarFechasDesdeMesEjecutivo(val);
    });
    
    // Botón Buscar del tab Por Chofer/Placa (delegación para evitar dependencia del onclick global)
    $(document).on('click', '#btnBuscarChoferPlaca', function() { cargarDashboardChoferPlaca(); });
});

function inicializarFechasChoferPlacaSiAplica() {
    var $tabChoferPlaca = $('#tab_chofer_placa');
    if (!$tabChoferPlaca.length) return;
    var tabActiva = $tabChoferPlaca.hasClass('active') || $tabChoferPlaca.hasClass('in');
    if (!tabActiva) return;
    if ($('#fecha_inicio_chofer_placa').val() && $('#fecha_fin_chofer_placa').val()) return;
    var mesVal = $('#mes_chofer_placa').val();
    if (!mesVal) {
        var now = new Date();
        mesVal = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
        $('#mes_chofer_placa').val(mesVal);
    }
    sincronizarFechasDesdeMesChoferPlaca(mesVal);
}

function aplicarRestriccionTabsPlantero() {
    if (!contextoDashboardTurnos || !contextoDashboardTurnos.soloTabChoferPlaca) return;
    var $tabs = $('ul.nav.nav-tabs[role="tablist"]');
    if (!$tabs.length) return;

    $tabs.find('li').removeClass('active');
    $tabs.find('a[href="#tab_configuracion"]').closest('li').hide();
    $tabs.find('a[href="#tab_rango"]').closest('li').hide();
    $tabs.find('a[href="#tab_ejecutivo"]').closest('li').hide();
    $tabs.find('a[href="#tab_chofer_placa"]').closest('li').show().addClass('active');

    $('#tab_configuracion, #tab_rango, #tab_ejecutivo').removeClass('active in').hide();
    $('#tab_chofer_placa').show().addClass('active in');
    $('#tipo_vista_chofer_placa option[value="planta"]').remove();
    if ($('#tipo_vista_chofer_placa').val() === 'planta') {
        $('#tipo_vista_chofer_placa').val('chofer');
    }
}

function limpiarOpcionesPlanteroEnModales() {
    if (!contextoDashboardTurnos || !contextoDashboardTurnos.soloTabChoferPlaca) return;
    $('#ordenManifiestosDetalle option[value="plantero"]').remove();
    $('#ordenManifiestosCPDetalle option[value="plantero"]').remove();
}

function sincronizarFechasDesdeMesEjecutivo(mesVal) {
    if (!mesVal) return;
    var parts = mesVal.split('-');
    var year = parseInt(parts[0], 10);
    var month = parseInt(parts[1], 10);
    var mesStr = String(month).padStart(2, '0');
    var lastDay = new Date(year, month, 0).getDate();
    $('#fecha_inicio_ejecutivo').val(year + '-' + mesStr + '-01');
    $('#fecha_fin_ejecutivo').val(year + '-' + mesStr + '-' + String(lastDay).padStart(2, '0'));
}

function sincronizarFechasDesdeMesChoferPlaca(mesVal) {
    if (!mesVal) return;
    var parts = mesVal.split('-');
    var year = parseInt(parts[0], 10);
    var month = parseInt(parts[1], 10);
    var mesStr = String(month).padStart(2, '0');
    var lastDay = new Date(year, month, 0).getDate();
    $('#fecha_inicio_chofer_placa').val(year + '-' + mesStr + '-01');
    $('#fecha_fin_chofer_placa').val(year + '-' + mesStr + '-' + String(lastDay).padStart(2, '0'));
}

function formatearFechaRango(f) {
    if (!f) return '';
    var m = f.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) return m[3] + '/' + m[2] + '/' + m[1];
    return f;
}

function parsearFecha(f) {
    if (!f || f === '-') return null;
    var partes = f.indexOf('-') >= 0 ? f.split('-') : f.split('/');
    if (partes.length !== 3) return null;
    if (f.indexOf('-') >= 0) return new Date(partes[0], partes[1] - 1, partes[2]);
    return new Date(partes[2], partes[1] - 1, partes[0]);
}

function formatearFechaParaMostrar(f) {
    var d = parsearFecha(f);
    if (!d) return f;
    return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear();
}

function cargarPlantasRango() {
    $.get('', { getPlantasDisponiblesAjax: true }, function(response) {
        if (response.success && response.plantas) {
            var $select = $('#select_planta_rango');
            $select.find('option:not(:first)').remove();
            response.plantas.forEach(function(pla) {
                $select.append($('<option>', { value: pla.Pla_Cod, text: pla.Pla_Nom }));
            });
            if ($.fn.chosen) {
                try { $select.chosen('destroy'); } catch(e) {}
                $select.chosen({ width: '250px' });
            }
        }
    }, 'json');
}

function cargarConfiguraciones() {
    $.get('', { getConfiguracionesDisponiblesAjax: true }, function(response) {
        if (response.success && response.configuraciones) {
            todasConfiguraciones = response.configuraciones;
            
            var $select = $('#select_configuracion');
            $select.empty();
            
            response.configuraciones.forEach(function(config, index) {
                $select.append($('<option>', {
                    value: config.Tur_Cod,
                    text: config.texto,
                    selected: index === 0 // Seleccionar la primera por defecto
                }));
            });
            
            // Aplicar Chosen si está disponible
            if ($.fn.chosen) {
                $select.chosen('destroy');
                $select.chosen({ width: '400px' });
            }
            
            // Cargar dashboard con la primera configuración seleccionada
            if (response.configuraciones.length > 0) {
                cargarDashboard();
            }
        }
    }, 'json').fail(function() {
        console.error('Error al cargar las configuraciones');
    });
}

function cargarDashboard() {
    var Tur_Cod = $('#select_configuracion').val() || '';
    
    var postData = { getDashboardTurnosAjax: true };
    if (Tur_Cod) {
        postData.Tur_Cod = Tur_Cod;
    }
    
    $('#dashboardContent').html('<div class="loading"><i class="fa fa-spinner fa-spin"></i><p>Cargando dashboard...</p></div>');
    
    $.get('', postData, function(response) {
        if (response.success && response.configuraciones) {
            mostrarDashboard(response.configuraciones);
        } else {
            $('#dashboardContent').html('<div class="alert alert-danger">Error al cargar el dashboard: ' + (response.message || 'Error desconocido') + '</div>');
        }
    }, 'json').fail(function() {
        $('#dashboardContent').html('<div class="alert alert-danger">Error al cargar el dashboard. Por favor, intente nuevamente.</div>');
    });
}

function getTurnosFiltradosPorFechaConfig(turnosDetalle) {
    if (!$('#omitir_dias_sin_manifiestos_config').is(':checked') || !turnosDetalle || turnosDetalle.length === 0) {
        return turnosDetalle || [];
    }
    // Suprimir del reporte todos los turnos que NO poseen manifiestos (solo mostrar turnos con al menos un manifiesto)
    return turnosDetalle.filter(function(t) {
        return (t.ocupados || 0) > 0;
    });
}

function construirTendenciaDesdeTurnos(turnosDetalle) {
    if (!turnosDetalle || turnosDetalle.length === 0) return { tendencia: [], promedio: 0 };
    var porFecha = {};
    turnosDetalle.forEach(function(t) {
        var fechaKey = t.Tud_Fec_SQL || t.Tud_Fec || '';
        if (!fechaKey) return;
        if (fechaKey.indexOf('/') >= 0) {
            var p = fechaKey.split('/');
            if (p.length === 3) fechaKey = p[2] + '-' + ('0' + p[1]).slice(-2) + '-' + ('0' + p[0]).slice(-2);
        }
        porFecha[fechaKey] = (porFecha[fechaKey] || 0) + (t.ocupados || 0);
    });
    var tendencia = Object.keys(porFecha).sort().map(function(f) { return { fecha: f, total: porFecha[f] }; });
    var suma = tendencia.reduce(function(s, d) { return s + d.total; }, 0);
    var promedio = tendencia.length > 0 ? (suma / tendencia.length).toFixed(1) : 0;
    return { tendencia: tendencia, promedio: promedio };
}

function mostrarDashboard(configuraciones) {
    datosDashboardActual = configuraciones;
    datosTurnosPorDia = {};
    
    var html = '';
    
    if (configuraciones.length === 0) {
        html = '<div class="alert alert-info"><i class="fa fa-info-circle"></i> No se encontraron configuraciones de turnos con los filtros seleccionados.</div>';
        $('#dashboardContent').html(html);
        return;
    }
    
    configuraciones.forEach(function(config) {
        var cardId = 'config-card-' + config.Tur_Cod;
        html += '<div class="config-card" id="' + cardId + '">';
        
        // Header de configuración
        html += '<div class="config-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">';
        html += '<div>';
        html += '<h4>Configuración de Turnos #' + config.Tur_Cod + '</h4>';
        html += '<p style="margin: 5px 0 0 0; font-size: 14px;">Período: ' + config.Tur_Fei + ' - ' + config.Tur_Fef + '</p>';
        html += '</div>';
        html += '<div style="flex-shrink: 0; display: flex; align-items: center; gap: 6px;">';
        html += '<button class="btn btn-sm btn-info" onclick="verManifiestosConfiguracion(' + config.Tur_Cod + ', \'' + config.Tur_Fei + '\', \'' + config.Tur_Fef + '\');" title="Ver todos los manifiestos de la configuración" style="padding: 5px 10px; font-size: 12px;">';
        html += '<i class="fa fa-list"></i> Ver Manifiesto';
        html += '</button>';
        html += '</div>';
        html += '</div>';
        
        // Estadísticas generales
        html += '<div class="config-stats">';
        html += '<div class="stat-card total-turnos">';
        html += '<div class="stat-label">Total Turnos</div>';
        html += '<div class="stat-value">' + config.total_turnos_detalle + '</div>';
        html += '</div>';
        
        html += '<div class="stat-card total-cupos">';
        html += '<div class="stat-label">Total Cupos</div>';
        html += '<div class="stat-value">' + config.total_cupos + '</div>';
        html += '</div>';
        
        html += '<div class="stat-card cupos-ocupados">';
        html += '<div class="stat-label">Cupos Ocupados</div>';
        html += '<div class="stat-value">' + config.total_cupos_ocupados + '</div>';
        html += '</div>';
        
        html += '<div class="stat-card cupos-libres">';
        html += '<div class="stat-label">Cupos Libres</div>';
        html += '<div class="stat-value">' + config.total_cupos_libres + '</div>';
        html += '</div>';
        
        html += '<div class="stat-card ocupacion">';
        html += '<div class="stat-label">% Ocupación</div>';
        html += '<div class="stat-value">' + config.porcentaje_ocupacion_general + '%</div>';
        html += '</div>';
        html += '</div>';
        
        // Detalle por día con 3 opciones: Tabla, Barras, Tendencia (cada día tiene su propio toggle)
        var turnosFiltrados = getTurnosFiltradosPorFechaConfig(config.turnos_detalle);
        if (turnosFiltrados && turnosFiltrados.length > 0) {
            html += generarVistaDiasConTresOpciones(turnosFiltrados, false);
        } else {
            var msg = $('#omitir_dias_sin_manifiestos_config').is(':checked') && config.turnos_detalle && config.turnos_detalle.length > 0
                ? 'Ningún turno posee manifiestos en esta configuración.'
                : 'No hay turnos detalle para esta configuración.';
            html += '<div class="alert alert-warning">' + msg + '</div>';
        }
        
        html += '</div>'; // Cierre config-card
    });
    
    $('#dashboardContent').html(html);
    
    // Crear gráficos de tendencia para días expandidos cuando la vista global es tendencia
    if (modoVistaConfig === 'tendencia') {
        Object.keys(datosTurnosPorDia).forEach(function(fechaId) {
            var $content = $('#' + fechaId);
            if ($content.length && $content.is(':visible')) {
                crearChartTendenciaDia(fechaId);
            }
        });
    }
}

function cambiarVistaGlobalConfig(vista) {
    primeraCargaConfig = false; // usuario hizo clic → expandir todo
    modoVistaConfig = vista;
    $('#btnVistaTablaConfig').toggleClass('btn-warning', vista === 'tabla').toggleClass('btn-default', vista !== 'tabla');
    $('#btnVistaBarrasConfig').toggleClass('btn-warning', vista === 'barras').toggleClass('btn-default', vista !== 'barras');
    $('#btnVistaTendenciaConfig').toggleClass('btn-warning', vista === 'tendencia').toggleClass('btn-default', vista !== 'tendencia');
    cargarDashboard();
}

function generarVistaDiasConTresOpciones(turnosDetalle, forPrint) {
    var html = '';
    var turnosPorFecha = {};
    turnosDetalle.forEach(function(turno) {
        var fecha = turno.Tud_Fec || '-';
        if (!turnosPorFecha[fecha]) turnosPorFecha[fecha] = [];
        turnosPorFecha[fecha].push(turno);
    });
    var fechasOrdenadas = Object.keys(turnosPorFecha).sort(function(a, b) {
        if (a === '-') return 1;
        if (b === '-') return -1;
        var fechaA = parsearFecha(a);
        var fechaB = parsearFecha(b);
        return (fechaA || 0) - (fechaB || 0);
    });
    
    var hoy = new Date();
    var fechaHoyStr = ('0' + hoy.getDate()).slice(-2) + '/' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '/' + hoy.getFullYear();
    var fechaHoyAlt = hoy.getFullYear() + '-' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '-' + ('0' + hoy.getDate()).slice(-2);
    
    fechasOrdenadas.forEach(function(fecha) {
        var turnosFecha = turnosPorFecha[fecha];
        var diaSemana = '';
        var fechaObj = parsearFecha(fecha);
        if (fechaObj) {
            var diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            diaSemana = diasSemana[fechaObj.getDay()];
        }
        var totalCuposOcupados = 0;
        turnosFecha.forEach(function(t) { totalCuposOcupados += t.ocupados || 0; });
        var esHoy = (fecha === fechaHoyStr || fecha === fechaHoyAlt);
        var expandirTodo = (modoVistaConfig === 'barras' || modoVistaConfig === 'tendencia') || (modoVistaConfig === 'tabla' && !primeraCargaConfig);
        var fechaId = 'fecha_' + fecha.replace(/[\/\-]/g, '_');
        var displayStyle = (forPrint || esHoy || expandirTodo) ? 'display: block;' : 'display: none;';
        var iconoToggle = (forPrint || esHoy || expandirTodo) ? 'fa-chevron-up' : 'fa-chevron-down';
        var fechaEsc = (fecha || '').replace(/'/g, "\\'");
        var tudCodVer = turnosFecha[0] && turnosFecha[0].Tud_Cod ? turnosFecha[0].Tud_Cod : 0;
        
        html += '<div class="fecha-header" style="margin-top: 15px; margin-bottom: 10px; padding: 8px 12px; background-color: #e9ecef; border-left: 4px solid #2C5D94; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleFechaContent(\'' + fechaId + '\', this);">';
        html += '<h5 style="margin: 0; font-size: 16px; font-weight: 600; color: #2C5D94; flex: 1;">';
        html += '<i class="fa ' + iconoToggle + ' toggle-icon" style="margin-right: 8px;"></i>';
        html += '<i class="fa fa-calendar"></i> ' + formatearFechaParaMostrar(fecha);
        if (diaSemana) html += ' - ' + diaSemana;
        html += ' <span style="margin-left: 15px; font-size: 14px; color: rgb(172, 108, 202);">Total Cupos Ocupados: <strong>' + totalCuposOcupados + '</strong></span>';
        html += '</h5>';
        html += '<div style="display: flex; gap: 5px; margin-left: 10px;" onclick="event.stopPropagation();">';
        if (tudCodVer && !forPrint) html += '<button class="btn btn-sm btn-info" onclick="verManifiestosDiaDirecto(' + tudCodVer + ');" title="Ver manifiestos del día" style="padding: 4px 8px; font-size: 11px;"><i class="fa fa-list"></i> Ver Manifiesto</button>';
        html += '<button class="btn btn-sm btn-primary" onclick="imprimirReporteFecha(\'' + fechaEsc + '\');" title="Imprimir" style="padding: 4px 8px; font-size: 11px;"><i class="fa fa-print"></i> Imprimir</button>';
        html += '<button class="btn btn-sm btn-success" onclick="exportarExcelFecha(\'' + fechaEsc + '\');" title="Excel" style="padding: 4px 8px; font-size: 11px;"><i class="fa fa-file-excel-o"></i> Excel</button>';
        html += '</div></div>';
        
        html += '<div id="' + fechaId + '" class="fecha-content" style="' + displayStyle + '">';
        
        var vistaActiva = forPrint ? 'tabla' : modoVistaConfig;
        var showTabla = vistaActiva === 'tabla';
        var showBarras = vistaActiva === 'barras';
        var showTendencia = vistaActiva === 'tendencia';
        
        html += '<div id="' + fechaId + '_tabla" class="vista-dia-panel" style="display: ' + (showTabla ? 'block' : 'none') + ';">';
        html += generarTablaParaDia(turnosFecha);
        html += '</div>';
        
        html += '<div id="' + fechaId + '_barras" class="vista-dia-panel" style="display: ' + (showBarras ? 'block' : 'none') + ';">';
        html += generarBarrasParaDia(turnosFecha, forPrint);
        html += '</div>';
        
        html += '<div id="' + fechaId + '_tendencia" class="vista-dia-panel" style="display: ' + (showTendencia ? 'block' : 'none') + ';">';
        html += '<div class="ceo-chart-wrap" style="min-height: 220px;"><canvas id="chartTendencia_' + fechaId + '"></canvas></div>';
        html += '<p style="margin-top: 8px; font-size: 12px;">Manifiestos por franja horaria (promedio del día: <strong id="promTendencia_' + fechaId + '">0</strong>)</p>';
        html += '</div>';
        if (!forPrint) datosTurnosPorDia[fechaId] = turnosFecha;
        
        html += '</div>';
    });
    
    return html;
}

function cambiarVistaDia(fechaId, vista, btnElement) {
    $('#' + fechaId + '_tabla').hide();
    $('#' + fechaId + '_barras').hide();
    $('#' + fechaId + '_tendencia').hide();
    $('#' + fechaId + '_' + vista).show();
    $(btnElement).siblings('.btn-vista-dia').removeClass('btn-warning').addClass('btn-default');
    $(btnElement).removeClass('btn-default').addClass('btn-warning');
    if (vista === 'tendencia') crearChartTendenciaDia(fechaId);
}

function crearChartTendenciaDia(fechaId) {
    var canvasId = 'chartTendencia_' + fechaId;
    var $canvas = $('#' + canvasId);
    if (!$canvas.length || $canvas.data('chart-created')) return;
    var $panel = $canvas.closest('.vista-dia-panel');
    if (!$panel.is(':visible')) return;
    var turnosData = datosTurnosPorDia[fechaId];
    if (!turnosData || !turnosData.length) return;
    
    var labels = turnosData.map(function(t) {
        var h = t.horario || '';
        return h.replace(/\s*-\s*/g, '–').replace(/:\d{2}\s*[-–]\s*/g, '–').replace(/:\d{2}/g, '') || h;
    });
    var datos = turnosData.map(function(t) { return t.ocupados || 0; });
    var prom = datos.length > 0 ? (datos.reduce(function(a,b){return a+b;},0) / datos.length).toFixed(1) : 0;
    $('#promTendencia_' + fechaId).text(prom);
    
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById(canvasId);
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Manifiestos por franja', data: datos, borderColor: '#2C5D94', backgroundColor: 'rgba(44, 93, 148, 0.1)', fill: true, tension: 0.2 },
                        { label: 'Promedio', data: datos.map(function() { return prom; }), borderColor: '#94a3b8', borderDash: [4, 2], fill: false }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: {
                        legend: { display: true }
                    }
                },
                plugins: [{
                    id: 'lineDataLabelsTendencia',
                    afterDatasetsDraw: function(chart) {
                        var meta = chart.getDatasetMeta(0);
                        if (!meta || !meta.data || !datos.length) return;
                        var ctx = chart.ctx;
                        var scaleY = chart.scales.y;
                        ctx.save();
                        ctx.font = 'bold 11px sans-serif';
                        ctx.textAlign = 'center';
                        ctx.fillStyle = '#2C5D94';
                        for (var i = 0; i < meta.data.length; i++) {
                            var point = meta.data[i];
                            var val = datos[i];
                            if (val == null) continue;
                            var y = point.y - 10;
                            if (scaleY && y < scaleY.top) y = scaleY.top + 8;
                            ctx.fillText(String(val), point.x, y);
                        }
                        ctx.restore();
                    }
                }]
            });
            $canvas.data('chart-created', true);
        }
    }
}

function generarTablaParaDia(turnosFecha) {
    var html = '<table class="turnos-table"><thead><tr>';
    html += '<th>Horario</th><th>Cupos Program.</th><th>Manif. Creados</th><th>Inactivos</th><th>Cupos Libres</th><th>% Ocupación</th><th>Barra Ocupación</th><th>Acciones</th></tr></thead><tbody>';
    turnosFecha.forEach(function(turno) {
        var ocupacionClass = turno.porcentaje_ocupacion >= 80 ? 'bajo' : (turno.porcentaje_ocupacion >= 50 ? 'medio' : 'alto');
        var anchoBarra = Math.min(turno.porcentaje_ocupacion, 100);
        html += '<tr>';
        html += '<td>' + (turno.horario || '') + '</td>';
        html += '<td><strong>' + (turno.cupos || 0) + '</strong></td>';
        html += '<td><strong style="color:rgb(172, 108, 202);">' + (turno.ocupados || 0) + '</strong></td>';
        html += '<td><strong style="color: #dc3545;">' + (turno.manifiestos_inactivos || 0) + '</strong></td>';
        html += '<td><strong style="color: #17a2b8;">' + (turno.libres || 0) + '</strong></td>';
        html += '<td><span class="badge-ocupacion ' + ocupacionClass + '">' + (turno.porcentaje_ocupacion || 0) + '%</span></td>';
        html += '<td><div class="barra-progreso-tabla"><div class="barra-progreso-fondo"><div class="barra-progreso-relleno ' + ocupacionClass + '" style="width: ' + anchoBarra + '%;"></div></div></div></td>';
        html += '<td><button class="btn btn-primary btn-xs btn-ver-manifiestos" onclick="verManifiestos(' + (turno.Tud_Cod || 0) + ');"><i class="fa fa-list"></i> Ver Manifiestos</button></td>';
        html += '</tr>';
    });
    html += '</tbody></table>';
    return html;
}

function generarBarrasParaDia(turnosFecha, forPrint) {
    var globalMaxCupos = 1;
    turnosFecha.forEach(function(t) {
        var ocupados = t.ocupados || 0;
        var libres = t.libres !== undefined ? t.libres : Math.max(0, (t.cupos || 0) - ocupados);
        globalMaxCupos = Math.max(globalMaxCupos, t.cupos || 0, ocupados + libres);
    });
    var yStep = 10;
    var yMaxFijo = Math.max(10, Math.ceil(globalMaxCupos / 10) * 10) + 10;
    var chartHeight = forPrint ? 180 : 280;
    var yTicksFijos = [0];
    for (var v = yStep; v <= yMaxFijo; v += yStep) { yTicksFijos.push(v); }
    if (yTicksFijos[yTicksFijos.length - 1] !== yMaxFijo) yTicksFijos.push(yMaxFijo);
    
    var totalCuposOcupados = 0, totalCuposLibres = 0;
    turnosFecha.forEach(function(t) {
        totalCuposOcupados += t.ocupados || 0;
        totalCuposLibres += (t.libres !== undefined ? t.libres : Math.max(0, (t.cupos || 0) - (t.ocupados || 0)));
    });
    
    var html = '<div class="chart-dashboard-container">';
    html += '<div class="chart-leyenda"><span class="chart-leyenda-item"><i class="chart-leyenda-color" style="background:#2C5D94;"></i> Ocupados: <strong style="color: rgb(172, 108, 202);">' + totalCuposOcupados + '</strong></span>';
    html += '<span class="chart-leyenda-item"><i class="chart-leyenda-color" style="background:#fd7e14;"></i> Libres: <strong style="color: #17a2b8;">' + totalCuposLibres + '</strong></span></div>';
    html += '<div class="chart-with-axes"><div class="chart-y-axis"><div class="chart-y-label">Cupos</div>';
    html += '<div class="chart-y-ticks" style="height: ' + chartHeight + 'px;">';
    for (var i = yTicksFijos.length - 1; i >= 0; i--) html += '<div class="chart-y-tick">' + yTicksFijos[i] + '</div>';
    html += '</div></div><div class="chart-main-area"><div class="chart-grid" style="height: ' + chartHeight + 'px;"><div class="chart-x-axis-line"></div>';
    yTicksFijos.forEach(function(tick) {
        if (tick > 0) html += '<div class="chart-grid-line" style="bottom: ' + (tick / yMaxFijo) * chartHeight + 'px;"></div>';
    });
    html += '</div>';
    var numBarras = turnosFecha.length;
    var barCountClass = 'chart-bars-n-' + (numBarras <= 5 ? 'pocas' : (numBarras <= 10 ? 'medias' : 'muchas'));
    html += '<div class="chart-bars-area"><div class="chart-bars-row ' + barCountClass + '" style="height: ' + chartHeight + 'px;">';
    
    turnosFecha.forEach(function(turno) {
        var cupos = turno.cupos || 0;
        var ocupados = turno.ocupados || 0;
        var libres = Math.max(0, turno.libres !== undefined ? turno.libres : cupos - ocupados);
        var totalMostrado = ocupados + libres;
        var barHeightPx = totalMostrado > 0 ? (totalMostrado / yMaxFijo) * chartHeight : 0;
        var ocupadosH = totalMostrado > 0 ? (ocupados / totalMostrado) * barHeightPx : 0;
        var libresH = totalMostrado > 0 ? (libres / totalMostrado) * barHeightPx : 0;
        if (ocupados > 0 && ocupadosH < 18) ocupadosH = 18;
        if (libres > 0 && libresH < 18) libresH = 18;
        if (barHeightPx < 20 && totalMostrado > 0) barHeightPx = 20;
        var totalH = ocupadosH + libresH;
        if (totalH > barHeightPx && barHeightPx > 0) {
            var scale = barHeightPx / totalH;
            ocupadosH = Math.round(ocupadosH * scale);
            libresH = Math.round(libresH * scale);
            if (ocupadosH + libresH > barHeightPx) libresH = Math.max(1, barHeightPx - ocupadosH);
        }
        var horarioCompact = (turno.horario || '').replace(/\s*-\s*/g, '–').replace(/:\d{2}\s*[-–]\s*/g, '–').replace(/:\d{2}/g, '') || turno.horario || '';
        html += '<div class="chart-bar-wrapper"><div class="chart-bar-total">' + totalMostrado + '</div><div class="chart-bar-spacer"></div>';
        html += '<div class="chart-bar-columna" style="height: ' + barHeightPx + 'px;">';
        html += '<div class="chart-segmento chart-segmento-ocupados' + (ocupadosH < 20 ? ' chart-segmento-pequeno' : '') + '" style="height: ' + ocupadosH + 'px;"><span class="chart-segmento-valor">' + ocupados + '</span></div>';
        html += '<div class="chart-segmento chart-segmento-libres' + (libresH < 20 ? ' chart-segmento-pequeno' : '') + '" style="height: ' + libresH + 'px;"><span class="chart-segmento-valor">' + libres + '</span></div>';
        html += '</div></div>';
    });
    html += '</div><div class="chart-labels-row ' + barCountClass + '">';
    turnosFecha.forEach(function(turno) {
        var horarioCompact = (turno.horario || '').replace(/\s*-\s*/g, '–').replace(/:\d{2}\s*[-–]\s*/g, '–').replace(/:\d{2}/g, '') || turno.horario || '';
        html += '<div class="chart-label-cell"><div class="chart-bar-label chart-x-label">' + horarioCompact + '</div><div class="chart-bar-porcentaje">(' + (turno.porcentaje_ocupacion || 0) + '%)</div></div>';
    });
    html += '</div></div></div></div></div>';
    return html;
}

function generarVistaTabla(turnosDetalle, forPrint) {
    var html = '';
    
    // Agrupar turnos por fecha
    var turnosPorFecha = {};
    turnosDetalle.forEach(function(turno) {
        var fecha = turno.Tud_Fec || '-';
        if (!turnosPorFecha[fecha]) {
            turnosPorFecha[fecha] = [];
        }
        turnosPorFecha[fecha].push(turno);
    });
    
    // Ordenar fechas (soporta DD/MM/YYYY y YYYY-MM-DD)
    var fechasOrdenadas = Object.keys(turnosPorFecha).sort(function(a, b) {
        if (a === '-') return 1;
        if (b === '-') return -1;
        var fechaA = parsearFecha(a);
        var fechaB = parsearFecha(b);
        return (fechaA || 0) - (fechaB || 0);
    });
    
    // Generar HTML para cada grupo de fecha
    fechasOrdenadas.forEach(function(fecha) {
        var turnosFecha = turnosPorFecha[fecha];
        
        // Calcular día de la semana (soporta DD/MM/YYYY y YYYY-MM-DD)
        var diaSemana = '';
        var fechaObj = parsearFecha(fecha);
        if (fechaObj) {
            var diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            diaSemana = diasSemana[fechaObj.getDay()];
        }
        
        // Calcular total de cupos ocupados para esta fecha
        var totalCuposOcupados = 0;
        turnosFecha.forEach(function(turno) {
            totalCuposOcupados += turno.ocupados || 0;
        });
        
        // Verificar si es la fecha actual (o si es para impresión, siempre mostrar)
        var hoy = new Date();
        var fechaHoyStr = ('0' + hoy.getDate()).slice(-2) + '/' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '/' + hoy.getFullYear();
        var fechaHoyAlt = hoy.getFullYear() + '-' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '-' + ('0' + hoy.getDate()).slice(-2);
        var esHoy = (fecha === fechaHoyStr || fecha === fechaHoyAlt);
        var fechaId = 'fecha_' + fecha.replace(/[\/\-]/g, '_');
        var displayStyle = (forPrint || esHoy) ? 'display: block;' : 'display: none;';
        var iconoToggle = esHoy ? 'fa-chevron-up' : 'fa-chevron-down';
        
        // Encabezado de fecha
        html += '<div class="fecha-header" style="margin-top: 15px; margin-bottom: 10px; padding: 8px 12px; background-color: #e9ecef; border-left: 4px solid #2C5D94; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleFechaContent(\'' + fechaId + '\', this);">';
        html += '<h5 style="margin: 0; font-size: 16px; font-weight: 600; color: #2C5D94; flex: 1;">';
        html += '<i class="fa ' + iconoToggle + ' toggle-icon" style="margin-right: 8px;"></i>';
        html += '<i class="fa fa-calendar"></i> ' + formatearFechaParaMostrar(fecha);
        if (diaSemana) {
            html += ' - ' + diaSemana;
        }
        html += ' <span style="margin-left: 15px; font-size: 14px; color: rgb(172, 108, 202);">Total Cupos Ocupados: <strong>' + totalCuposOcupados + '</strong></span>';
        html += '</h5>';
        html += '<div style="display: flex; gap: 5px; margin-left: 10px;" onclick="event.stopPropagation();">';
        html += '<button class="btn btn-sm btn-info" onclick="verManifiestosDiaDirecto(' + turnosFecha[0].Tud_Cod + ');" title="Ver todos los manifiestos del día" style="padding: 4px 8px; font-size: 11px;">';
        html += '<i class="fa fa-list"></i> Ver Manifiesto';
        html += '</button>';
        html += '<button class="btn btn-sm btn-primary" onclick="imprimirReporteFecha(\'' + fecha + '\');" title="Imprimir reporte de esta fecha" style="padding: 4px 8px; font-size: 11px;">';
        html += '<i class="fa fa-print"></i> Imprimir';
        html += '</button>';
        html += '<button class="btn btn-sm btn-success" onclick="exportarExcelFecha(\'' + fecha + '\');" title="Exportar a Excel" style="padding: 4px 8px; font-size: 11px;">';
        html += '<i class="fa fa-file-excel-o"></i> Excel';
        html += '</button>';
        html += '</div>';
        html += '</div>';
        
        // Contenedor colapsable para la tabla
        html += '<div id="' + fechaId + '" class="fecha-content" style="' + displayStyle + '">';
        
        // Tabla para esta fecha
        html += '<table class="turnos-table">';
        html += '<thead>';
        html += '<tr>';
        html += '<th>Horario</th>';
        html += '<th>Cupos Program.</th>';
        html += '<th>Manif. Creados</th>';
        html += '<th>Inactivos</th>';
        html += '<th>Cupos Libres</th>';
        html += '<th>% Ocupación</th>';
        html += '<th>Barra Ocupación</th>';
        html += '<th>Acciones</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';
        
        turnosFecha.forEach(function(turno) {
            // Invertir lógica: rojo para bajos, verde para altos
            var ocupacionClass = 'alto'; // Por defecto es bajo (rojo)
            if (turno.porcentaje_ocupacion >= 80) {
                ocupacionClass = 'bajo'; // Alto porcentaje = verde
            } else if (turno.porcentaje_ocupacion >= 50) {
                ocupacionClass = 'medio';
            }
            
            var anchoBarra = Math.min(turno.porcentaje_ocupacion, 100);
            
            html += '<tr>';
            html += '<td>' + turno.horario + '</td>';
            html += '<td><strong>' + turno.cupos + '</strong></td>';
            html += '<td><strong style="color:rgb(172, 108, 202);">' + turno.ocupados + '</strong></td>';
            html += '<td><strong style="color: #dc3545;">' + turno.manifiestos_inactivos + '</strong></td>';
            html += '<td><strong style="color: #17a2b8;">' + turno.libres + '</strong></td>';
            html += '<td><span class="badge-ocupacion ' + ocupacionClass + '">' + turno.porcentaje_ocupacion + '%</span></td>';
            html += '<td>';
            html += '<div class="barra-progreso-tabla">';
            html += '<div class="barra-progreso-fondo">';
            html += '<div class="barra-progreso-relleno ' + ocupacionClass + '" style="width: ' + anchoBarra + '%;"></div>';
            html += '</div>';
            html += '</div>';
            html += '</td>';
            html += '<td>';
            html += '<button class="btn btn-primary btn-xs btn-ver-manifiestos" onclick="verManifiestos(' + turno.Tud_Cod + ');">';
            html += '<i class="fa fa-list"></i> Ver Manifiestos';
            html += '</button>';
            html += '</td>';
            html += '</tr>';
        });
        
        html += '</tbody>';
        html += '</table>';
        html += '</div>'; // Cerrar fecha-content
    });
    
    return html;
}

function generarVistaBarras(turnosDetalle, forPrint, expandAll, desdeRango) {
    var html = '';
    
    // Eje Y fijo y consistente: máximo global de TODOS los datos (cupo programado Y cupo ocupado+libres por si hay sobrecupo)
    var globalMaxCupos = 1;
    turnosDetalle.forEach(function(t) {
        var cupos = t.cupos || 0;
        var ocupados = t.ocupados || 0;
        var libres = t.libres !== undefined ? t.libres : Math.max(0, cupos - ocupados);
        var totalMostrado = ocupados + libres;
        globalMaxCupos = Math.max(globalMaxCupos, cupos, totalMostrado);
    });
    var yStep = 10;
    var yMaxFijo = Math.max(10, Math.ceil(globalMaxCupos / 10) * 10) + 10;
    var chartHeight = forPrint ? 180 : 280;
    var yTicksFijos = [0];
    for (var v = yStep; v <= yMaxFijo; v += yStep) { yTicksFijos.push(v); }
    if (yTicksFijos[yTicksFijos.length - 1] !== yMaxFijo) yTicksFijos.push(yMaxFijo);
    
    // Agrupar turnos por fecha
    var turnosPorFecha = {};
    turnosDetalle.forEach(function(turno) {
        var fecha = turno.Tud_Fec || '-';
        if (!turnosPorFecha[fecha]) {
            turnosPorFecha[fecha] = [];
        }
        turnosPorFecha[fecha].push(turno);
    });
    
    var fechasOrdenadas = Object.keys(turnosPorFecha).sort(function(a, b) {
        if (a === '-') return 1;
        if (b === '-') return -1;
        var fechaA = parsearFecha(a);
        var fechaB = parsearFecha(b);
        return (fechaA || 0) - (fechaB || 0);
    });
    
    // Generar HTML para cada grupo de fecha
    fechasOrdenadas.forEach(function(fecha) {
        var turnosFecha = turnosPorFecha[fecha];
        
        if (forPrint) {
            html += '<div class="fecha-print-block" style="page-break-inside: avoid;">';
        }
        
        // Calcular día de la semana (soporta DD/MM/YYYY y YYYY-MM-DD)
        var diaSemana = '';
        var fechaObj = parsearFecha(fecha);
        if (fechaObj) {
            var diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            diaSemana = diasSemana[fechaObj.getDay()];
        }
        
        // Calcular total de cupos ocupados y libres para esta fecha
        var totalCuposOcupados = 0;
        var totalCuposLibres = 0;
        turnosFecha.forEach(function(turno) {
            totalCuposOcupados += turno.ocupados || 0;
            var libres = turno.libres !== undefined ? turno.libres : Math.max(0, (turno.cupos || 0) - (turno.ocupados || 0));
            totalCuposLibres += libres;
        });
        
        // Verificar si es la fecha actual (comparar como fechas)
        var hoy = new Date();
        var fechaHoyStr = ('0' + hoy.getDate()).slice(-2) + '/' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '/' + hoy.getFullYear();
        var fechaHoyAlt = hoy.getFullYear() + '-' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '-' + ('0' + hoy.getDate()).slice(-2);
        var esHoy = (fecha === fechaHoyStr || fecha === fechaHoyAlt);
        var fechaId = 'fecha_barras_' + fecha.replace(/[\/\-]/g, '_');
        var expandido = forPrint || expandAll || esHoy;
        var displayStyle = expandido ? 'display: block;' : 'display: none;';
        var iconoToggle = expandido ? 'fa-chevron-up' : 'fa-chevron-down';
        
        // Encabezado de fecha (sin Ver Manifiestos)
        html += '<div class="fecha-header chart-fecha-header" style="margin-top: 15px; margin-bottom: 10px; padding: 8px 12px; background-color: #e9ecef; border-left: 4px solid #2C5D94; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleFechaContent(\'' + fechaId + '\', this);">';
        html += '<h5 style="margin: 0; font-size: 16px; font-weight: 600; color: #2C5D94; flex: 1;">';
        html += '<i class="fa ' + iconoToggle + ' toggle-icon" style="margin-right: 8px;"></i>';
        html += '<i class="fa fa-calendar"></i> ' + formatearFechaParaMostrar(fecha);
        if (diaSemana) html += ' - ' + diaSemana;
        html += ' <span style="margin-left: 15px; font-size: 14px; color: rgb(172, 108, 202);">Total Cupos Ocupados: <strong>' + totalCuposOcupados + '</strong></span>';
        html += '</h5>';
        html += '<div style="display: flex; gap: 5px; margin-left: 10px;" onclick="event.stopPropagation();">';
        var fechaEsc = (fecha || '').replace(/'/g, "\\'");
        var fnImprimir = desdeRango ? 'imprimirReporteFechaRango' : 'imprimirReporteFecha';
        var fnExcel = desdeRango ? 'exportarExcelFechaRango' : 'exportarExcelFecha';
        var tudCodVer = turnosFecha[0] && turnosFecha[0].Tud_Cod ? turnosFecha[0].Tud_Cod : 0;
        if (tudCodVer && !forPrint) html += '<button class="btn btn-sm btn-info" onclick="verManifiestosDiaDirecto(' + tudCodVer + ');" title="Ver manifiestos del día" style="padding: 4px 8px; font-size: 11px;"><i class="fa fa-list"></i> Ver Manifiesto</button>';
        html += '<button class="btn btn-sm btn-primary" onclick="' + fnImprimir + '(\'' + fechaEsc + '\');" title="Imprimir" style="padding: 4px 8px; font-size: 11px;"><i class="fa fa-print"></i> Imprimir</button>';
        html += '<button class="btn btn-sm btn-success" onclick="' + fnExcel + '(\'' + fechaEsc + '\');" title="Excel" style="padding: 4px 8px; font-size: 11px;"><i class="fa fa-file-excel-o"></i> Excel</button>';
        html += '</div>';
        html += '</div>';
        
        html += '<div id="' + fechaId + '" class="fecha-content chart-fecha-content" style="' + displayStyle + '">';
        
        // Usar eje Y fijo global (yMaxFijo, yTicksFijos) - mismo para todos los días
        var yMax = yMaxFijo;
        var yTicks = yTicksFijos;
        
        html += '<div class="chart-dashboard-container">';
        html += '<div class="chart-leyenda">';
        html += '<span class="chart-leyenda-item"><i class="chart-leyenda-color" style="background:#2C5D94;"></i> Ocupados: <strong style="color: rgb(172, 108, 202);">' + totalCuposOcupados + '</strong></span>';
        html += '<span class="chart-leyenda-item"><i class="chart-leyenda-color" style="background:#fd7e14;"></i> Libres: <strong style="color: #17a2b8;">' + totalCuposLibres + '</strong></span>';
        html += '</div>';
        html += '<div class="chart-with-axes">';
        html += '<div class="chart-y-axis">';
        html += '<div class="chart-y-label">Cupos</div>';
        html += '<div class="chart-y-ticks" style="height: ' + chartHeight + 'px;">';
        for (var i = yTicks.length - 1; i >= 0; i--) {
            html += '<div class="chart-y-tick">' + yTicks[i] + '</div>';
        }
        html += '</div>';
        html += '</div>';
        html += '<div class="chart-main-area">';
        html += '<div class="chart-grid" style="height: ' + chartHeight + 'px;">';
        html += '<div class="chart-x-axis-line"></div>';
        yTicks.forEach(function(tick) {
            if (tick > 0) {
                var bottomPx = (tick / yMax) * chartHeight;
                html += '<div class="chart-grid-line" style="bottom: ' + bottomPx + 'px;"></div>';
            }
        });
        html += '</div>';
        var numBarras = turnosFecha.length;
        var barCountClass = 'chart-bars-n-' + (numBarras <= 5 ? 'pocas' : (numBarras <= 10 ? 'medias' : 'muchas'));
        html += '<div class="chart-bars-area">';
        html += '<div class="chart-bars-row ' + barCountClass + '" style="height: ' + chartHeight + 'px;" data-bars="' + numBarras + '">';
        
        turnosFecha.forEach(function(turno) {
            var cupos = turno.cupos || 0;
            var ocupados = turno.ocupados || 0;
            var libres = Math.max(0, (turno.libres !== undefined ? turno.libres : cupos - ocupados));
            var ocupadosPct = cupos > 0 ? (Math.min(ocupados, cupos) / cupos) * 100 : 0;
            var libresPct = cupos > 0 ? (libres / cupos) * 100 : 0;
            var totalMostrado = ocupados + libres;
            var barHeightPx = totalMostrado > 0 ? (totalMostrado / yMax) * chartHeight : 0;
            var ocupadosH = totalMostrado > 0 ? (ocupados / totalMostrado) * barHeightPx : 0;
            var libresH = totalMostrado > 0 ? (libres / totalMostrado) * barHeightPx : 0;
            if (ocupados > 0 && ocupadosH < 18) ocupadosH = 18;
            if (libres > 0 && libresH < 18) libresH = 18;
            if (barHeightPx < 20 && totalMostrado > 0) barHeightPx = 20;
            var totalH = ocupadosH + libresH;
            if (totalH > barHeightPx && barHeightPx > 0) {
                var scale = barHeightPx / totalH;
                ocupadosH = Math.round(ocupadosH * scale);
                libresH = Math.round(libresH * scale);
                if (ocupados > 0 && ocupadosH < 1) ocupadosH = 1;
                if (libres > 0 && libresH < 1) libresH = 1;
                if (ocupadosH + libresH > barHeightPx) libresH = Math.max(1, barHeightPx - ocupadosH);
            }
            
            var horarioRaw = turno.horario || '';
            var horarioLabel = horarioRaw.replace(/\s*-\s*/g, '–');
            var horarioCompact = horarioRaw.replace(/:\d{2}\s*[-–]\s*/g, '–').replace(/:\d{2}/g, '') || horarioLabel;
            
            html += '<div class="chart-bar-wrapper">';
            html += '<div class="chart-bar-total" title="Total: ' + totalMostrado + '">' + totalMostrado + '</div>';
            html += '<div class="chart-bar-spacer"></div>';
            html += '<div class="chart-bar-columna" style="height: ' + barHeightPx + 'px;">';
            html += '<div class="chart-segmento chart-segmento-ocupados' + (ocupadosH < 20 ? ' chart-segmento-pequeno' : '') + '" style="height: ' + ocupadosH + 'px;">';
            html += '<span class="chart-segmento-valor">' + ocupados + '</span>';
            html += '</div>';
            html += '<div class="chart-segmento chart-segmento-libres' + (libresH < 20 ? ' chart-segmento-pequeno' : '') + '" style="height: ' + libresH + 'px;">';
            html += '<span class="chart-segmento-valor">' + libres + '</span>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        html += '<div class="chart-labels-row ' + barCountClass + '">';
        turnosFecha.forEach(function(turno) {
            var horarioRaw = turno.horario || '';
            var horarioLabel = horarioRaw.replace(/\s*-\s*/g, '–');
            var horarioCompact = horarioRaw.replace(/:\d{2}\s*[-–]\s*/g, '–').replace(/:\d{2}/g, '') || horarioLabel;
            var cupos = turno.cupos || 0;
            var ocupados = turno.ocupados || 0;
            var libres = Math.max(0, turno.libres !== undefined ? turno.libres : cupos - ocupados);
            var totalMostrado = ocupados + libres;
            html += '<div class="chart-label-cell"><div class="chart-bar-label chart-x-label" title="' + horarioLabel + '">' + horarioCompact + '</div><div class="chart-bar-porcentaje" title="% Ocupación">(' + turno.porcentaje_ocupacion + '%)</div></div>';
        });
        html += '</div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        if (forPrint) {
            html += '</div>'; // cierre fecha-print-block
        }
    });
    
    return html;
}

/**
 * Gráfico único para Por Rango de Fechas: una sola gráfica con barras por día en el eje X.
 * Cada barra = un día, apilada (Ocupados + Libres).
 */
function generarVistaBarrasUnicoPorDias(dias, forPrint) {
    if (!dias || dias.length === 0) return '';
    
    var html = '';
    var globalMaxCupos = 1;
    dias.forEach(function(dia) {
        var totalCupos = dia.total_cupos || 0;
        var ocupados = dia.total_cupos_ocupados || 0;
        var libres = Math.max(0, (dia.total_cupos_libres !== undefined ? dia.total_cupos_libres : totalCupos - ocupados));
        var totalMostrado = ocupados + libres;
        globalMaxCupos = Math.max(globalMaxCupos, totalCupos, totalMostrado);
    });
    var yStep = 50;
    var yMaxFijo = Math.max(50, Math.ceil(globalMaxCupos / 50) * 50) + 50;
    var chartHeight = 280;
    var yTicksFijos = [0];
    for (var v = yStep; v <= yMaxFijo; v += yStep) { yTicksFijos.push(v); }
    if (yTicksFijos[yTicksFijos.length - 1] !== yMaxFijo) yTicksFijos.push(yMaxFijo);
    
    var numBarras = dias.length;
    var barCountClass = 'chart-bars-n-' + (numBarras <= 5 ? 'pocas' : (numBarras <= 10 ? 'medias' : (numBarras <= 20 ? 'muchas' : 'super')));
    var totalOcupadosRango = 0;
    var totalLibresRango = 0;
    dias.forEach(function(dia) {
        totalOcupadosRango += dia.total_cupos_ocupados || 0;
        var totalCupos = dia.total_cupos || 0;
        var ocupados = dia.total_cupos_ocupados || 0;
        totalLibresRango += Math.max(0, (dia.total_cupos_libres !== undefined ? dia.total_cupos_libres : totalCupos - ocupados));
    });
    
    html += '<div class="chart-dashboard-container chart-por-dias" style="margin-top: 15px;">';
    html += '<div class="chart-leyenda">';
    html += '<span class="chart-leyenda-item"><i class="chart-leyenda-color" style="background:#2C5D94;"></i> Ocupados: <strong style="color: rgb(172, 108, 202);">' + totalOcupadosRango + '</strong></span>';
    html += '<span class="chart-leyenda-item"><i class="chart-leyenda-color" style="background:#fd7e14;"></i> Libres: <strong style="color: #17a2b8;">' + totalLibresRango + '</strong></span>';
    html += '</div>';
    html += '<div class="chart-with-axes">';
    html += '<div class="chart-y-axis">';
    html += '<div class="chart-y-label">Cupos</div>';
    html += '<div class="chart-y-ticks" style="height: ' + chartHeight + 'px;">';
    for (var i = yTicksFijos.length - 1; i >= 0; i--) {
        html += '<div class="chart-y-tick">' + yTicksFijos[i] + '</div>';
    }
    html += '</div>';
    html += '</div>';
    html += '<div class="chart-main-area">';
    html += '<div class="chart-grid" style="height: ' + chartHeight + 'px;">';
    html += '<div class="chart-x-axis-line"></div>';
    yTicksFijos.forEach(function(tick) {
        if (tick > 0) {
            var bottomPx = (tick / yMaxFijo) * chartHeight;
            html += '<div class="chart-grid-line" style="bottom: ' + bottomPx + 'px;"></div>';
        }
    });
    html += '</div>';
    html += '<div class="chart-bars-area">';
    html += '<div class="chart-bars-row ' + barCountClass + '" style="height: ' + chartHeight + 'px;" data-bars="' + numBarras + '">';
    
    dias.forEach(function(dia) {
        var totalCupos = dia.total_cupos || 0;
        var ocupados = dia.total_cupos_ocupados || 0;
        var libres = Math.max(0, (dia.total_cupos_libres !== undefined ? dia.total_cupos_libres : totalCupos - ocupados));
        var totalMostrado = ocupados + libres;
        var pctOcup = totalCupos > 0 ? ((ocupados / totalCupos) * 100).toFixed(1) : 0;
        
        var barHeightPx = totalMostrado > 0 ? (totalMostrado / yMaxFijo) * chartHeight : 0;
        var ocupadosH = totalMostrado > 0 ? (ocupados / totalMostrado) * barHeightPx : 0;
        var libresH = totalMostrado > 0 ? (libres / totalMostrado) * barHeightPx : 0;
        if (ocupados > 0 && ocupadosH < 18) ocupadosH = 18;
        if (libres > 0 && libresH < 18) libresH = 18;
        if (barHeightPx < 20 && totalMostrado > 0) barHeightPx = 20;
        var totalH = ocupadosH + libresH;
        if (totalH > barHeightPx && barHeightPx > 0) {
            var scale = barHeightPx / totalH;
            ocupadosH = Math.round(ocupadosH * scale);
            libresH = Math.round(libresH * scale);
            if (ocupados > 0 && ocupadosH < 1) ocupadosH = 1;
            if (libres > 0 && libresH < 1) libresH = 1;
            if (ocupadosH + libresH > barHeightPx) libresH = Math.max(1, barHeightPx - ocupadosH);
        }
        
        html += '<div class="chart-bar-wrapper">';
        html += '<div class="chart-bar-total" title="Total: ' + totalMostrado + '">' + totalMostrado + '</div>';
        html += '<div class="chart-bar-spacer"></div>';
        html += '<div class="chart-bar-columna" style="height: ' + barHeightPx + 'px;">';
        html += '<div class="chart-segmento chart-segmento-ocupados' + (ocupadosH < 20 ? ' chart-segmento-pequeno' : '') + '" style="height: ' + ocupadosH + 'px;">';
        html += '<span class="chart-segmento-valor">' + ocupados + '</span>';
        html += '</div>';
        html += '<div class="chart-segmento chart-segmento-libres' + (libresH < 20 ? ' chart-segmento-pequeno' : '') + '" style="height: ' + libresH + 'px;">';
        html += '<span class="chart-segmento-valor">' + libres + '</span>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
    });
    
    html += '</div>';
    html += '<div class="chart-labels-row ' + barCountClass + '">';
    dias.forEach(function(dia) {
        var labelDia = (dia.Tud_Fec || '') + (dia.dia_semana ? ' - ' + dia.dia_semana : '');
        var tudFec = dia.Tud_Fec || '';
        var labelCorto = '';
        var m1 = tudFec.match(/^(\d{1,2})\/(\d{1,2})\/\d{4}$/);
        var m2 = tudFec.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
        if (m1) labelCorto = m1[1] + '/' + m1[2];
        else if (m2) labelCorto = m2[3] + '/' + m2[2];
        else labelCorto = tudFec;
        var totalCupos = dia.total_cupos || 0;
        var ocupados = dia.total_cupos_ocupados || 0;
        var libres = Math.max(0, (dia.total_cupos_libres !== undefined ? dia.total_cupos_libres : totalCupos - ocupados));
        var totalMostrado = ocupados + libres;
        var pctOcup = totalCupos > 0 ? ((ocupados / totalCupos) * 100).toFixed(1) : 0;
        html += '<div class="chart-label-cell"><div class="chart-bar-label chart-x-label" title="' + labelDia + '">' + labelCorto + '</div><div class="chart-bar-porcentaje" title="% Ocupación">(' + pctOcup + '%)</div></div>';
    });
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    return html;
}

function toggleFechaContent(fechaId, headerElement) {
    var $content = $('#' + fechaId);
    var $icon = $(headerElement).find('.toggle-icon');
    
    if ($content.is(':visible')) {
        $content.slideUp(200);
        $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
    } else {
        $content.slideDown(200, function() {
            if (typeof modoVistaConfig !== 'undefined' && modoVistaConfig === 'tendencia' && typeof crearChartTendenciaDia === 'function') {
                crearChartTendenciaDia(fechaId);
            }
        });
        $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
    }
}

function alternarVista() {
    modoVistaBarras = !modoVistaBarras;
    
    // Actualizar texto del botón
    var $btn = $('#btnVistaBarras');
    var $texto = $('#textoVista');
    
    if (modoVistaBarras) {
        $texto.text('Vista Tabla');
        $btn.removeClass('btn-warning').addClass('btn-default');
    } else {
        $texto.text('Vista Barras');
        $btn.removeClass('btn-default').addClass('btn-warning');
    }
    
    // Recargar dashboard con el nuevo modo
    cargarDashboard();
}

function limpiarModalManifiestosAntesDeCargar() {
    var $modal = $('#modalManifiestos');
    if ($modal.length) {
        $modal.modal('hide');
        $modal.remove();
    }
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('body').css('padding-right', '');
}

function verManifiestos(Tud_Cod) {
    if (cargandoManifiestos) return;
    cargandoManifiestos = true;
    limpiarModalManifiestosAntesDeCargar();
    $('#loader').show();
    $.get('', { getManifiestosDashboardAjax: true, Tud_Cod: Tud_Cod }, function(response) {
        if (response.success && response.manifiestos) {
            mostrarManifiestosModal(response.manifiestos, Tud_Cod, response.turnoInfo);
        } else {
            alert('Error al cargar los manifiestos: ' + (response.message || 'Error desconocido'));
        }
    }, 'json').fail(function() {
        alert('Error al cargar los manifiestos. Por favor, intente nuevamente.');
    }).always(function() {
        cargandoManifiestos = false;
        $('#loader').fadeOut("slow");
    });
}

function verManifiestosConfiguracion(Tur_Cod, Tur_Fei, Tur_Fef) {
    if (cargandoManifiestos) return;
    cargandoManifiestos = true;
    limpiarModalManifiestosAntesDeCargar();
    $('#loader').show();
    $.get('', { getManifiestosConfiguracionAjax: true, Tur_Cod: Tur_Cod }, function(response) {
        if (response.success && response.manifiestos) {
            response.turnoInfo.fecha = (Tur_Fei || '') + ' - ' + (Tur_Fef || '');
            mostrarManifiestosModal(response.manifiestos, Tur_Cod, response.turnoInfo);
        } else {
            alert('Error al cargar los manifiestos: ' + (response.message || 'Error desconocido'));
        }
    }, 'json').fail(function() {
        alert('Error al cargar los manifiestos. Por favor, intente nuevamente.');
    }).always(function() {
        cargandoManifiestos = false;
        $('#loader').fadeOut("slow");
    });
}

function verManifiestosDiaDirecto(Tud_Cod) {
    if (!Tud_Cod) {
        alert('No se pudo obtener el turno.');
        return;
    }
    if (cargandoManifiestos) return;
    cargandoManifiestos = true;
    limpiarModalManifiestosAntesDeCargar();
    $('#loader').show();
    $.get('', { getManifiestosDiaAjax: true, Tud_Cod: Tud_Cod }, function(response) {
        if (response.success && response.manifiestos) {
            mostrarManifiestosModal(response.manifiestos, Tud_Cod, response.turnoInfo);
        } else {
            alert('Error al cargar los manifiestos del día: ' + (response.message || 'Error desconocido'));
        }
    }, 'json').fail(function() {
        alert('Error al cargar los manifiestos del día. Por favor, intente nuevamente.');
    }).always(function() {
        cargandoManifiestos = false;
        $('#loader').fadeOut("slow");
    });
}

function verManifiestosRangoCompleto(fechaInicio, fechaFin) {
    if (cargandoManifiestos) return;
    cargandoManifiestos = true;
    limpiarModalManifiestosAntesDeCargar();
    $('#loader').show();
    var Pla_Cod = $('#select_planta_rango').val() || '';
    var params = { getManifiestosPorRangoCompletoAjax: true, fecha_inicio: fechaInicio, fecha_fin: fechaFin };
    if (Pla_Cod) params.Pla_Cod = Pla_Cod;
    $.get('', params, function(response) {
        if (response.success && response.manifiestos) {
            mostrarManifiestosModal(response.manifiestos, 'rango', response.turnoInfo);
        } else {
            alert('No se encontraron manifiestos' + (response.message ? ': ' + response.message : ''));
        }
    }, 'json').fail(function() {
        alert('Error al cargar los manifiestos. Por favor, intente nuevamente.');
    }).always(function() {
        cargandoManifiestos = false;
        $('#loader').fadeOut("slow");
    });
}

function verManifiestosRangoDia(Tud_Fec) {
    if (!Tud_Fec) {
        alert('No se pudo obtener la fecha.');
        return;
    }
    if (cargandoManifiestos) return;
    cargandoManifiestos = true;
    limpiarModalManifiestosAntesDeCargar();
    $('#loader').show();
    var Pla_Cod = $('#select_planta_rango').val() || '';
    var params = { getManifiestosPorRangoDiaAjax: true, Tud_Fec: Tud_Fec };
    if (Pla_Cod) params.Pla_Cod = Pla_Cod;
    $.get('', params, function(response) {
        if (response.success && response.manifiestos) {
            mostrarManifiestosModal(response.manifiestos, 'dia-' + Tud_Fec, response.turnoInfo);
        } else {
            alert('No se encontraron manifiestos' + (response.message ? ': ' + response.message : ''));
        }
    }, 'json').fail(function() {
        alert('Error al cargar los manifiestos. Por favor, intente nuevamente.');
    }).always(function() {
        cargandoManifiestos = false;
        $('#loader').fadeOut("slow");
    });
}

function verManifiestosRangoSlot(Tud_Fec, Tud_Hin, Tud_Hfi) {
    if (!Tud_Fec || !Tud_Hin || !Tud_Hfi) {
        alert('No se pudo obtener el horario.');
        return;
    }
    if (cargandoManifiestos) return;
    cargandoManifiestos = true;
    limpiarModalManifiestosAntesDeCargar();
    $('#loader').show();
    var Pla_Cod = $('#select_planta_rango').val() || '';
    var params = { getManifiestosPorRangoSlotAjax: true, Tud_Fec: Tud_Fec, Tud_Hin: Tud_Hin, Tud_Hfi: Tud_Hfi };
    if (Pla_Cod) params.Pla_Cod = Pla_Cod;
    $.get('', params, function(response) {
        if (response.success && response.manifiestos) {
            mostrarManifiestosModal(response.manifiestos, 'slot', response.turnoInfo);
        } else {
            alert('No se encontraron manifiestos' + (response.message ? ': ' + response.message : ''));
        }
    }, 'json').fail(function() {
        alert('Error al cargar los manifiestos. Por favor, intente nuevamente.');
    }).always(function() {
        cargandoManifiestos = false;
        $('#loader').fadeOut("slow");
    });
}

function ordenarManifiestosParaTabla(manifiestos, tipo) {
    if (!manifiestos || manifiestos.length === 0) return manifiestos;
    var copia = manifiestos.slice();
    if (tipo === 'plantero') {
        var countByPlanta = {};
        copia.forEach(function(man) {
            var planta = man.Pla_Nom || '(Sin planta)';
            countByPlanta[planta] = (countByPlanta[planta] || 0) + 1;
        });
        copia.sort(function(a, b) {
            var plantaA = a.Pla_Nom || '(Sin planta)';
            var plantaB = b.Pla_Nom || '(Sin planta)';
            var countA = countByPlanta[plantaA] || 0;
            var countB = countByPlanta[plantaB] || 0;
            if (countB !== countA) return countB - countA;
            var fechaA = (a.Man_Fec || '').toString();
            var fechaB = (b.Man_Fec || '').toString();
            return fechaA.localeCompare(fechaB) || (plantaA.localeCompare(plantaB));
        });
    } else {
        copia.sort(function(a, b) {
            var fechaA = (a.Man_Fec || '').toString();
            var fechaB = (b.Man_Fec || '').toString();
            var cmp = fechaA.localeCompare(fechaB);
            if (cmp !== 0) return cmp;
            var plantaA = (a.Pla_Nom || '').toString();
            var plantaB = (b.Pla_Nom || '').toString();
            return plantaA.localeCompare(plantaB);
        });
    }
    return copia;
}

function aplicarOrdenManifiestosDetalle(tipo) {
    if (!datosModalManifiestos || !datosModalManifiestos.manifiestos) return;
    var manifiestos = datosModalManifiestos.manifiestos;
    var mostrarColumnaHorario = (manifiestos.length > 0 && manifiestos[0].horario_turno);
    var mostrarColumnaFechaDia = (manifiestos.length > 0 && manifiestos[0].fecha_dia);
    var ordenados = ordenarManifiestosParaTabla(manifiestos, tipo);
    var $tbody = $('#tbodyManifiestosDetalle');
    if (!$tbody.length) return;
    var html = '';
    ordenados.forEach(function(man, index) {
        html += '<tr>';
        html += '<td style="text-align: center; font-weight: bold;">' + (index + 1) + '</td>';
        html += '<td>' + (man.ManNum || '') + '</td>';
        html += '<td>' + (man.Man_Fec || '') + '</td>';
        if (mostrarColumnaFechaDia) html += '<td>' + (man.fecha_dia || '') + '</td>';
        if (mostrarColumnaHorario) html += '<td>' + (man.horario_turno || '') + '</td>';
        html += '<td>' + (man.Cliente || '') + '</td>';
        html += '<td>' + (man.Pla_Nom || '') + '</td>';
        html += '<td>' + (man.Man_Tip_1 === 'GE' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Entrada a Garita"></i>' : '') + '</td>';
        html += '<td>' + (man.Man_Tip_2 === 'A' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Aprobacion del Tecnico"></i>' : '') + '</td>';
        html += '<td>' + (man.Man_Tip_3 === 'GS' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Salida de Garita"></i>' : '') + '</td>';
        html += '<td>' + (man.Man_Tip_4 === 'F' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Facturado"></i>' : '') + '</td>';
        html += '<td>' + (man.Man_Tip_5 === 'R' ? '<i class="glyphicon glyphicon-ok" style="color: #dc3545; font-size: 16px;" title="Rechazado"></i>' : '') + '</td>';
        html += '</tr>';
    });
    $tbody.html(html);
}

function mostrarManifiestosModal(manifiestos, Tud_Cod, turnoInfo) {
    var esPlanteroRestringido = !!(contextoDashboardTurnos && contextoDashboardTurnos.soloTabChoferPlaca);
    var titulo;
    if (turnoInfo && turnoInfo.horario === 'Acumulado de la configuración') {
        titulo = '<i class="fa fa-calendar-check-o"></i> Manifiestos - Configuración completa (' + (turnoInfo.fecha || '') + ')';
    } else if (turnoInfo && turnoInfo.horario === 'Acumulado del rango') {
        titulo = '<i class="fa fa-calendar-check-o"></i> Manifiestos - Rango completo (' + (turnoInfo.fecha || '') + ')';
    } else if (turnoInfo && turnoInfo.horario === 'Acumulado del día') {
        titulo = '<i class="fa fa-calendar"></i> Manifiestos del día - ' + (turnoInfo.fecha || '');
    } else {
        titulo = '<i class="fa fa-list"></i> Manifiestos del Turno #' + Tud_Cod;
        if (turnoInfo && turnoInfo.fecha) titulo += ' - ' + turnoInfo.fecha;
        if (turnoInfo && turnoInfo.horario) titulo += ' (' + turnoInfo.horario + ')';
    }
    
    var html = '<div class="modal fade" id="modalManifiestos" tabindex="-1" role="dialog">';
    html += '<div class="modal-dialog modal-lg" role="document">';
    html += '<div class="modal-content" style="font-size: 11px;">';
    html += '<div class="modal-header" style="background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white; padding: 8px 15px;">';
    html += '<button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;">&times;</button>';
    html += '<h4 class="modal-title" style="font-size: 14px; margin: 0; line-height: 1.2;">' + titulo + '</h4>';
    html += '</div>';
    html += '<div class="modal-body" style="font-size: 11px; padding-top: 10px;">';
    
    if (manifiestos.length === 0) {
        html += '<div class="alert alert-info">No hay manifiestos para este turno.</div>';
        datosModalManifiestos = null;
    } else {
        datosModalManifiestos = { manifiestos: manifiestos, turnoInfo: turnoInfo || {} };
        // Tabs
        html += '<ul class="nav nav-tabs" role="tablist" style="margin-bottom: 12px;">';
        html += '<li role="presentation" class="active">';
        html += '<a href="#tabManifiestosDetalle" aria-controls="tabManifiestosDetalle" role="tab" data-toggle="tab">';
        html += '<i class="fa fa-list-alt"></i> Detallado</a></li>';
        if (!esPlanteroRestringido) {
            html += '<li role="presentation">';
            html += '<a href="#tabManifiestosPlanta" aria-controls="tabManifiestosPlanta" role="tab" data-toggle="tab">';
            html += '<i class="fa fa-industry"></i> Por Planta</a></li>';
        }
        html += '</ul>';
        
        html += '<div class="tab-content">';
        
        // Tab 1: Detallado
        html += '<div role="tabpanel" class="tab-pane active" id="tabManifiestosDetalle">';
        html += '<div class="no-print" style="margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">';
        html += '<div style="display: flex; align-items: center; gap: 8px;">';
        html += '<label style="margin: 0; font-weight: normal;">Ordenar por:</label>';
        html += '<select id="ordenManifiestosDetalle" class="form-control input-sm" style="width: auto; display: inline-block;" onchange="aplicarOrdenManifiestosDetalle(this.value);">';
        html += '<option value="fecha" selected>Por fecha</option>';
        if (!contextoDashboardTurnos || !contextoDashboardTurnos.soloTabChoferPlaca) {
            html += '<option value="plantero">Por plantero</option>';
        }
        html += '</select>';
        html += '</div>';
        html += '<div>';
        html += '<button type="button" class="btn btn-primary btn-sm" onclick="imprimirModalManifiestos();" style="margin-right: 5px;"><i class="fa fa-print"></i> Imprimir</button>';
        html += '<button type="button" class="btn btn-success btn-sm" onclick="exportModalManifiestosExcel(\'detallado\');" style="margin-right: 5px; background: linear-gradient(135deg, #28a745 0%, #218838 100%); border-color: #1e7e34;">';
        html += '<i class="fa fa-file-excel-o"></i> Excel</button>';
        html += '<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>';
        html += '</div>';
        html += '</div>';
        var mostrarColumnaHorario = (manifiestos.length > 0 && manifiestos[0].horario_turno);
        var mostrarColumnaFechaDia = (manifiestos.length > 0 && manifiestos[0].fecha_dia);
        var manifiestosOrdenados = ordenarManifiestosParaTabla(manifiestos, 'fecha');
        html += '<table class="table table-bordered table-striped table-condensed tabla-modal-manifiestos" style="font-size: 11px;">';
        html += '<thead>';
        html += '<tr>';
        html += '<th style="width: 40px;">#</th>';
        html += '<th>No. Manifiesto</th>';
        html += '<th>Fecha</th>';
        if (mostrarColumnaFechaDia) html += '<th>Día</th>';
        if (mostrarColumnaHorario) html += '<th>Horario</th>';
        html += '<th>Cliente</th>';
        html += '<th>Planta</th>';        
        html += '<th><i class="glyphicon glyphicon-log-in" style="color: #ffc107;" title="Garita In - Ingreso a garita"></i></th>';
        html += '<th><i class="glyphicon glyphicon-ok-circle" style="color: #28a745;" title="Aprobado - Aprobacion del Tecnico"></i></th>';
        html += '<th><i class="glyphicon glyphicon-log-out" style="color: #17a2b8;" title="Garita Out - Salida de garita"></i></th>';
        html += '<th><i class="glyphicon glyphicon-file" style="color: #007bff;" title="Facturado - Manifiesto Facturado"></i></th>';
        html += '<th><i class="glyphicon glyphicon-remove-circle" style="color: #dc3545;" title="Rechazado - Manifiesto Rechazado"></i></th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody id="tbodyManifiestosDetalle">';
        
        manifiestosOrdenados.forEach(function(man, index) {
            var estadoClass = man.Man_Est === 'A' ? 'success' : 'danger';
            html += '<tr>';
            html += '<td style="text-align: center; font-weight: bold;">' + (index + 1) + '</td>';
            html += '<td>' + (man.ManNum || '') + '</td>';
            html += '<td>' + (man.Man_Fec || '') + '</td>';
            if (mostrarColumnaFechaDia) html += '<td>' + (man.fecha_dia || '') + '</td>';
            if (mostrarColumnaHorario) html += '<td>' + (man.horario_turno || '') + '</td>';
            html += '<td>' + (man.Cliente || '') + '</td>';
            html += '<td>' + (man.Pla_Nom || '') + '</td>';
            html += '<td>' + (man.Man_Tip_1 === 'GE' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Entrada a Garita"></i>' : '') + '</td>';
            html += '<td>' + (man.Man_Tip_2 === 'A' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Aprobacion del Tecnico"></i>' : '') + '</td>';
            html += '<td>' + (man.Man_Tip_3 === 'GS' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Salida de Garita"></i>' : '') + '</td>';
            html += '<td>' + (man.Man_Tip_4 === 'F' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Facturado"></i>' : '') + '</td>';
            html += '<td>' + (man.Man_Tip_5 === 'R' ? '<i class="glyphicon glyphicon-ok" style="color: #dc3545; font-size: 16px;" title="Rechazado"></i>' : '') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody>';
        html += '</table>';
        html += '</div>';
        
        if (!esPlanteroRestringido) {
            // Tab 2: Por Planta (agrupado por Planta + Cliente)
            html += '<div role="tabpanel" class="tab-pane" id="tabManifiestosPlanta">';
            html += '<div class="no-print" style="margin-bottom: 10px; text-align: right;">';
            html += '<button type="button" class="btn btn-primary btn-sm" onclick="imprimirModalManifiestos();" style="margin-right: 5px;"><i class="fa fa-print"></i> Imprimir</button>';
            html += '<button type="button" class="btn btn-success btn-sm" onclick="exportModalManifiestosExcel(\'planta\');" style="margin-right: 5px; background: linear-gradient(135deg, #28a745 0%, #218838 100%); border-color: #1e7e34;">';
            html += '<i class="fa fa-file-excel-o"></i> Excel</button>';
            html += '<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>';
            html += '</div>';
            
            var porPlantaCliente = {};
            manifiestos.forEach(function(man) {
                var planta = man.Pla_Nom || '(Sin planta)';
                var cliente = man.Cliente || '(Sin cliente)';
                var key = planta + '\u0001' + cliente;
                if (!porPlantaCliente[key]) {
                    porPlantaCliente[key] = { planta: planta, cliente: cliente, cantidad: 0 };
                }
                porPlantaCliente[key].cantidad++;
            });
            
            var filas = Object.keys(porPlantaCliente).map(function(k) { return porPlantaCliente[k]; });
            filas.sort(function(a, b) { return (b.cantidad || 0) - (a.cantidad || 0); });
            
            html += '<table class="table table-bordered table-striped table-condensed tabla-modal-planta" style="font-size: 11px;">';
            html += '<thead>';
            html += '<tr>';
            html += '<th style="width: 40px;">#</th>';
            html += '<th>Planta</th>';
            html += '<th>Cliente</th>';
            html += '<th style="width: 120px; text-align: center;">Cantidad Manifiestos</th>';
            html += '<th style="width: 100px; text-align: center;"><span style="display: block;">%</span><span style="display: block;">Participación</span></th>';
            html += '</tr>';
            html += '</thead>';
            html += '<tbody>';
            
            var totalManifiestos = filas.reduce(function(s, f) { return s + (f.cantidad || 0); }, 0);
            filas.forEach(function(item, index) {
                var pct = totalManifiestos > 0 ? ((item.cantidad / totalManifiestos) * 100).toFixed(2) : '0.00';
                html += '<tr>';
                html += '<td style="text-align: center; font-weight: bold;">' + (index + 1) + '</td>';
                html += '<td>' + item.planta + '</td>';
                html += '<td>' + item.cliente + '</td>';
                html += '<td style="text-align: center;"><strong>' + item.cantidad + '</strong></td>';
                html += '<td style="text-align: center;">' + pct + '%</td>';
                html += '</tr>';
            });
            
            html += '</tbody>';
            html += '<tfoot>';
            html += '<tr style="background-color: #2C5D94; color: white; font-weight: bold;">';
            html += '<td colspan="3" style="text-align: right; padding-right: 15px;">TOTAL:</td>';
            html += '<td style="text-align: center;">' + totalManifiestos + '</td>';
            html += '<td style="text-align: center;">100%</td>';
            html += '</tr>';
            html += '</tfoot>';
            html += '</table>';
            html += '</div>';
        }
        
        html += '</div>';
    }
    
    html += '</div>';
    html += '<div class="modal-footer">';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    // Remover modal anterior si existe
    $('#modalManifiestos').remove();
    
    // Agregar modal al body
    $('body').append(html);
    
    // Mostrar modal
    $('#modalManifiestos').modal('show');
    limpiarOpcionesPlanteroEnModales();
    
    // Limpiar modal al cerrar
    $('#modalManifiestos').on('hidden.bs.modal', function() {
        datosModalManifiestos = null;
        $(this).remove();
    });
}

function abrirModalManifiestosChoferPlaca(manifiestos, turnoInfo, tipoVista) {
    var esPlanteroRestringido = !!(contextoDashboardTurnos && contextoDashboardTurnos.soloTabChoferPlaca);
    var titulo = '<i class="fa fa-list"></i> Manifiestos - ' + (turnoInfo.horario || '') + ' (' + (turnoInfo.fecha || '') + ')';
    datosModalManifiestosChoferPlaca = { manifiestos: manifiestos || [], turnoInfo: turnoInfo || {}, tipoVista: tipoVista || 'chofer' };
    var html = '<div class="modal fade" id="modalManifiestosChoferPlaca" tabindex="-1" role="dialog">';
    html += '<div class="modal-dialog modal-lg" role="document">';
    html += '<div class="modal-content" style="font-size: 11px;">';
    html += '<div class="modal-header" style="background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white; padding: 8px 15px;">';
    html += '<button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;">&times;</button>';
    html += '<h4 class="modal-title" style="font-size: 14px; margin: 0; line-height: 1.2;">' + titulo + '</h4>';
    html += '</div>';
    html += '<div class="modal-body" style="font-size: 11px; padding-top: 10px;">';
    if (!manifiestos || manifiestos.length === 0) {
        html += '<div class="alert alert-info">No hay manifiestos para mostrar.</div>';
        datosModalManifiestosChoferPlaca = null;
    } else {
        html += '<ul class="nav nav-tabs" role="tablist" style="margin-bottom: 12px;">';
        html += '<li role="presentation" class="active"><a href="#tabManifiestosCPDetalle" aria-controls="tabManifiestosCPDetalle" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> Detallado</a></li>';
        if (tipoVista === 'planta') {
            html += '<li role="presentation"><a href="#tabManifiestosCPChofer" aria-controls="tabManifiestosCPChofer" role="tab" data-toggle="tab"><i class="fa fa-user"></i> Por Chofer</a></li>';
            html += '<li role="presentation"><a href="#tabManifiestosCPPlaca" aria-controls="tabManifiestosCPPlaca" role="tab" data-toggle="tab"><i class="fa fa-car"></i> Por Placa</a></li>';
        } else if (!esPlanteroRestringido) {
            html += '<li role="presentation"><a href="#tabManifiestosCPPlanta" aria-controls="tabManifiestosCPPlanta" role="tab" data-toggle="tab"><i class="fa fa-industry"></i> Por Planta</a></li>';
        }
        html += '</ul>';
        html += '<div class="tab-content">';
        html += '<div role="tabpanel" class="tab-pane active" id="tabManifiestosCPDetalle">';
        html += '<div class="no-print" style="margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">';
        html += '<div style="display: flex; align-items: center; gap: 8px;">';
        html += '<label style="margin: 0; font-weight: normal;">Ordenar por:</label>';
        html += '<select id="ordenManifiestosCPDetalle" class="form-control input-sm" style="width: auto;" onchange="aplicarOrdenManifiestosChoferPlaca(this.value);">';
        html += '<option value="fecha" selected>Por fecha</option>';
        if (!contextoDashboardTurnos || !contextoDashboardTurnos.soloTabChoferPlaca) {
            html += '<option value="plantero">Por plantero</option>';
        }
        html += '</select>';
        html += '</div>';
        html += '<div>';
        html += '<button type="button" class="btn btn-primary btn-sm" onclick="imprimirModalManifiestosChoferPlaca();" style="margin-right: 5px;"><i class="fa fa-print"></i> Imprimir</button>';
        html += '<button type="button" class="btn btn-success btn-sm" onclick="exportModalManifiestosExcelChoferPlaca();" style="margin-right: 5px; background: linear-gradient(135deg, #28a745 0%, #218838 100%); border-color: #1e7e34;"><i class="fa fa-file-excel-o"></i> Excel</button>';
        html += '<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>';
        html += '</div>';
        html += '</div>';
        var ordenados = ordenarManifiestosParaTabla(manifiestos, 'fecha');
        html += '<table class="table table-bordered table-striped table-condensed" style="font-size: 11px;"><thead><tr>';
        html += '<th style="width: 40px;">#</th><th>No. Manifiesto</th><th>Fecha</th><th>Horario</th><th>Planta</th>';
        if (tipoVista === 'chofer') html += '<th style="white-space: nowrap; min-width: 90px;">Placa</th>';
        else if (tipoVista === 'placa') html += '<th>Chofer</th>';
        else html += '<th style="white-space: nowrap; min-width: 90px;">Placa</th><th>Chofer</th>';
        html += '<th>T. Estancia</th>';
        html += '</tr></thead><tbody id="tbodyManifiestosChoferPlaca">';
        ordenados.forEach(function(man, index) {
            var tProm = man.minutos_estancia || 0;
            var tFmt = tProm > 0 ? (tProm >= 60 ? (Math.floor(tProm / 60) + 'h ' + Math.round(tProm % 60) + 'm') : (Math.round(tProm) + ' min')) : '-';
            html += '<tr>';
            html += '<td style="text-align: center; font-weight: bold;">' + (index + 1) + '</td>';
            html += '<td>' + (man.ManNum || '') + '</td>';
            html += '<td>' + (man.Man_Fec || '') + '</td>';
            html += '<td>' + (man.Man_Hor || '') + '</td>';
            html += '<td>' + (man.Pla_Nom || '') + '</td>';
            if (tipoVista === 'chofer') html += '<td style="white-space: nowrap;">' + (man.Veh_Pla || '') + '</td>';
            else if (tipoVista === 'placa') html += '<td>' + (man.chofer_nombre || '') + '</td>';
            else { html += '<td style="white-space: nowrap;">' + (man.Veh_Pla || '') + '</td><td>' + (man.chofer_nombre || '') + '</td>'; }
            
            html += '<td>' + tFmt + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table>';
        html += '</div>';
        if (tipoVista === 'planta') {
            var porChoferPlaca = {};
            manifiestos.forEach(function(man) {
                var chofer = (man.chofer_nombre && man.chofer_nombre.trim() !== '') ? man.chofer_nombre.trim() : '(Sin asignar)';
                var placa = (man.Veh_Pla && man.Veh_Pla.trim() !== '') ? man.Veh_Pla.trim() : '(Sin placa)';
                var key = placa + '|' + chofer;
                if (!porChoferPlaca[key]) porChoferPlaca[key] = { placa: placa, chofer: chofer, cantidad: 0 };
                porChoferPlaca[key].cantidad += 1;
            });
            var filasChofer = Object.keys(porChoferPlaca).map(function(k) { return porChoferPlaca[k]; });
            filasChofer.sort(function(a, b) { return (b.cantidad || 0) - (a.cantidad || 0); });
            var totalMan = filasChofer.reduce(function(s, f) { return s + (f.cantidad || 0); }, 0);
            html += '<div role="tabpanel" class="tab-pane" id="tabManifiestosCPChofer">';
            html += '<div class="no-print" style="margin-bottom: 10px; text-align: right;">';
            html += '<button type="button" class="btn btn-primary btn-sm" onclick="imprimirModalManifiestosChoferPlaca();" style="margin-right: 5px;"><i class="fa fa-print"></i> Imprimir</button>';
            html += '<button type="button" class="btn btn-success btn-sm" onclick="exportModalManifiestosExcelChoferPlaca();" style="margin-right: 5px;"><i class="fa fa-file-excel-o"></i> Excel</button>';
            html += '<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>';
            html += '</div>';
            html += '<table class="table table-bordered table-striped table-condensed" style="font-size: 11px;"><thead><tr>';
            html += '<th style="width: 40px;">#</th><th style="white-space: nowrap; min-width: 90px;">Placa</th><th>Chofer</th><th style="width: 120px; text-align: center;">Cantidad Manifiestos</th><th style="width: 100px; text-align: center;">% Participación</th></tr></thead><tbody>';
            filasChofer.forEach(function(item, index) {
                var pct = totalMan > 0 ? ((item.cantidad / totalMan) * 100).toFixed(2) : '0.00';
                html += '<tr><td style="text-align: center; font-weight: bold;">' + (index + 1) + '</td><td style="white-space: nowrap;">' + item.placa + '</td><td>' + item.chofer + '</td>';
                html += '<td style="text-align: center;"><strong>' + item.cantidad + '</strong></td><td style="text-align: center;">' + pct + '%</td></tr>';
            });
            html += '</tbody><tfoot><tr style="background-color: #2C5D94; color: white; font-weight: bold;">';
            html += '<td colspan="3" style="text-align: right; padding-right: 15px;">TOTAL:</td><td style="text-align: center;">' + totalMan + '</td><td style="text-align: center;">100%</td></tr></tfoot></table>';
            html += '</div>';
            // Tab Por Placa (agrupado solo por placa, con choferes)
            var porPlaca = {};
            manifiestos.forEach(function(man) {
                var placa = (man.Veh_Pla && man.Veh_Pla.trim() !== '') ? man.Veh_Pla.trim() : '(Sin placa)';
                var chofer = (man.chofer_nombre && man.chofer_nombre.trim() !== '') ? man.chofer_nombre.trim() : '(Sin asignar)';
                if (!porPlaca[placa]) porPlaca[placa] = { cantidad: 0, choferes: {} };
                porPlaca[placa].cantidad += 1;
                porPlaca[placa].choferes[chofer] = true;
            });
            var filasPlaca = Object.keys(porPlaca).map(function(p) {
                return { placa: p, cantidad: porPlaca[p].cantidad, choferes: Object.keys(porPlaca[p].choferes).join(', ') };
            });
            filasPlaca.sort(function(a, b) { return (b.cantidad || 0) - (a.cantidad || 0); });
            var totalManPlaca = filasPlaca.reduce(function(s, f) { return s + (f.cantidad || 0); }, 0);
            html += '<div role="tabpanel" class="tab-pane" id="tabManifiestosCPPlaca">';
            html += '<div class="no-print" style="margin-bottom: 10px; text-align: right;">';
            html += '<button type="button" class="btn btn-primary btn-sm" onclick="imprimirModalManifiestosChoferPlaca();" style="margin-right: 5px;"><i class="fa fa-print"></i> Imprimir</button>';
            html += '<button type="button" class="btn btn-success btn-sm" onclick="exportModalManifiestosExcelChoferPlaca();" style="margin-right: 5px;"><i class="fa fa-file-excel-o"></i> Excel</button>';
            html += '<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>';
            html += '</div>';
            html += '<table class="table table-bordered table-striped table-condensed" style="font-size: 11px;"><thead><tr>';
            html += '<th style="width: 40px;">#</th><th style="white-space: nowrap; min-width: 90px;">Placa</th><th>Chofer</th><th style="width: 120px; text-align: center;">Cantidad Manifiestos</th><th style="width: 100px; text-align: center;">% Participación</th></tr></thead><tbody>';
            filasPlaca.forEach(function(item, index) {
                var pct = totalManPlaca > 0 ? ((item.cantidad / totalManPlaca) * 100).toFixed(2) : '0.00';
                html += '<tr><td style="text-align: center; font-weight: bold;">' + (index + 1) + '</td><td style="white-space: nowrap;">' + item.placa + '</td><td>' + (item.choferes || '') + '</td>';
                html += '<td style="text-align: center;"><strong>' + item.cantidad + '</strong></td><td style="text-align: center;">' + pct + '%</td></tr>';
            });
            html += '</tbody><tfoot><tr style="background-color: #2C5D94; color: white; font-weight: bold;">';
            html += '<td colspan="3" style="text-align: right; padding-right: 15px;">TOTAL:</td><td style="text-align: center;">' + totalManPlaca + '</td><td style="text-align: center;">100%</td></tr></tfoot></table>';
            html += '</div>';
        } else if (!esPlanteroRestringido) {
            var porPlanta = {};
            manifiestos.forEach(function(man) {
                var planta = man.Pla_Nom || '(Sin planta)';
                porPlanta[planta] = (porPlanta[planta] || 0) + 1;
            });
            var filasPlanta = Object.keys(porPlanta).map(function(p) { return { planta: p, cantidad: porPlanta[p] }; });
            filasPlanta.sort(function(a, b) { return (b.cantidad || 0) - (a.cantidad || 0); });
            var totalMan = filasPlanta.reduce(function(s, f) { return s + (f.cantidad || 0); }, 0);
            html += '<div role="tabpanel" class="tab-pane" id="tabManifiestosCPPlanta">';
            html += '<div class="no-print" style="margin-bottom: 10px; text-align: right;">';
            html += '<button type="button" class="btn btn-primary btn-sm" onclick="imprimirModalManifiestosChoferPlaca();" style="margin-right: 5px;"><i class="fa fa-print"></i> Imprimir</button>';
            html += '<button type="button" class="btn btn-success btn-sm" onclick="exportModalManifiestosExcelChoferPlaca();" style="margin-right: 5px;"><i class="fa fa-file-excel-o"></i> Excel</button>';
            html += '<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>';
            html += '</div>';
            html += '<table class="table table-bordered table-striped table-condensed" style="font-size: 11px;"><thead><tr>';
            html += '<th style="width: 40px;">#</th><th>Planta</th><th style="width: 120px; text-align: center;">Cantidad Manifiestos</th><th style="width: 100px; text-align: center;">% Participación</th></tr></thead><tbody>';
            filasPlanta.forEach(function(item, index) {
                var pct = totalMan > 0 ? ((item.cantidad / totalMan) * 100).toFixed(2) : '0.00';
                html += '<tr><td style="text-align: center; font-weight: bold;">' + (index + 1) + '</td><td>' + item.planta + '</td>';
                html += '<td style="text-align: center;"><strong>' + item.cantidad + '</strong></td><td style="text-align: center;">' + pct + '%</td></tr>';
            });
            html += '</tbody><tfoot><tr style="background-color: #2C5D94; color: white; font-weight: bold;">';
            html += '<td style="text-align: right; padding-right: 15px;">TOTAL:</td><td></td><td style="text-align: center;">' + totalMan + '</td><td style="text-align: center;">100%</td></tr></tfoot></table>';
            html += '</div>';
        }
        html += '</div>';
    }
    html += '</div><div class="modal-footer"></div></div></div></div>';
    $('#modalManifiestosChoferPlaca').remove();
    $('body').append(html);
    $('#modalManifiestosChoferPlaca').modal('show');
    limpiarOpcionesPlanteroEnModales();
    $('#modalManifiestosChoferPlaca').on('hidden.bs.modal', function() {
        datosModalManifiestosChoferPlaca = null;
        $(this).remove();
    });
}

function aplicarOrdenManifiestosChoferPlaca(tipo) {
    if (!datosModalManifiestosChoferPlaca || !datosModalManifiestosChoferPlaca.manifiestos) return;
    var ordenados = ordenarManifiestosParaTabla(datosModalManifiestosChoferPlaca.manifiestos, tipo);
    var tipoVista = datosModalManifiestosChoferPlaca.tipoVista || 'chofer';
    var $tbody = $('#tbodyManifiestosChoferPlaca');
    if (!$tbody.length) return;
    var html = '';
    ordenados.forEach(function(man, index) {
        var tProm = man.minutos_estancia || 0;
        var tFmt = tProm > 0 ? (tProm >= 60 ? (Math.floor(tProm / 60) + 'h ' + Math.round(tProm % 60) + 'm') : (Math.round(tProm) + ' min')) : '-';
        html += '<tr>';
        html += '<td style="text-align: center; font-weight: bold;">' + (index + 1) + '</td>';
        html += '<td>' + (man.ManNum || '') + '</td>';
        html += '<td>' + (man.Man_Fec || '') + '</td>';
        html += '<td>' + (man.Man_Hor || '') + '</td>';
        html += '<td>' + (man.Pla_Nom || '') + '</td>';
        if (tipoVista === 'chofer') html += '<td style="white-space: nowrap;">' + (man.Veh_Pla || '') + '</td>';
        else if (tipoVista === 'placa') html += '<td>' + (man.chofer_nombre || '') + '</td>';
        else { html += '<td style="white-space: nowrap;">' + (man.Veh_Pla || '') + '</td><td>' + (man.chofer_nombre || '') + '</td>'; }
        html += '<td>' + tFmt + '</td>';
        html += '</tr>';
    });
    $tbody.html(html);
}

function verManifiestosChoferPlaca(idx) {
    if (!datosDashboardChoferPlaca || !datosDashboardChoferPlaca.agrupado || !datosDashboardChoferPlaca.agrupado[idx]) return;
    var grupo = datosDashboardChoferPlaca.agrupado[idx];
    var tipoVista = datosDashboardChoferPlaca.tipo_vista || 'chofer';
    var tituloRango = formatearFechaRango(fechaInicioChoferPlaca) + ' - ' + formatearFechaRango(fechaFinChoferPlaca);
    var turnoInfo = {
        fecha: tituloRango,
        horario: tipoVista === 'chofer' ? ('Chofer: ' + (grupo.chofer_nombre || 'Sin asignar')) : (tipoVista === 'planta' ? ('Planta: ' + (grupo.Pla_Nom || 'Sin planta')) : ('Placa: ' + (grupo.Veh_Pla || 'Sin placa')))
    };
    abrirModalManifiestosChoferPlaca(grupo.manifiestos || [], turnoInfo, tipoVista);
}

function imprimirModalManifiestosChoferPlaca() {
    var $modal = $('#modalManifiestosChoferPlaca');
    if (!$modal.length) return;
    var $titulo = $modal.find('.modal-title');
    var encabezado = $titulo.length ? '<div class="modal-print-header" style="background:#2C5D94;color:white;padding:10px 15px;margin-bottom:12px;font-size:14px;font-weight:bold;">' + ($titulo.text() || $titulo.html() || 'Manifiestos').trim() + '</div>' : '';
    var $activo = $modal.find('.tab-pane.active');
    var contenido = $activo.length ? $activo.clone() : $modal.find('.modal-body').clone();
    contenido.find('.no-print').remove();
    abrirVentanaImpresion(encabezado + contenido.prop('outerHTML'), 'Manifiestos - Chofer/Placa');
}

function exportModalManifiestosExcelChoferPlaca() {
    if (!datosModalManifiestosChoferPlaca || !datosModalManifiestosChoferPlaca.manifiestos) {
        alert('No hay datos para exportar.');
        return;
    }
    var manifiestos = datosModalManifiestosChoferPlaca.manifiestos;
    var turnoInfo = datosModalManifiestosChoferPlaca.turnoInfo || {};
    var titulo = 'Manifiestos - ' + (turnoInfo.horario || '') + (turnoInfo.fecha ? ' (' + turnoInfo.fecha + ')' : '');
    var nombre = 'manifiestos_chofer_placa_' + (fechaInicioChoferPlaca || '').replace(/\//g, '-') + '_' + (fechaFinChoferPlaca || '').replace(/\//g, '-');
    var html = '';
    var hoja = 'Manifiestos';
    // Detectar pestaña activa del modal: exportar la vista que está viendo el usuario
    var $activo = $('#modalManifiestosChoferPlaca .tab-pane.active');
    var idActivo = $activo.attr('id') || '';
    if (idActivo === 'tabManifiestosCPChofer') {
        // Vista resumida Por Chofer (con Placa)
        var porChoferPlaca = {};
        manifiestos.forEach(function(man) {
            var chofer = (man.chofer_nombre && man.chofer_nombre.trim() !== '') ? man.chofer_nombre.trim() : '(Sin asignar)';
            var placa = (man.Veh_Pla && man.Veh_Pla.trim() !== '') ? man.Veh_Pla.trim() : '(Sin placa)';
            var key = placa + '|' + chofer;
            if (!porChoferPlaca[key]) porChoferPlaca[key] = { placa: placa, chofer: chofer, cantidad: 0 };
            porChoferPlaca[key].cantidad += 1;
        });
        var filasChofer = Object.keys(porChoferPlaca).map(function(k) { return porChoferPlaca[k]; });
        filasChofer.sort(function(a, b) { return (b.cantidad || 0) - (a.cantidad || 0); });
        var totalMan = filasChofer.reduce(function(s, f) { return s + (f.cantidad || 0); }, 0);
        html = '<table border="1" cellpadding="3" style="border-collapse:collapse;">';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td colspan="5" style="padding:8px;text-align:center;">' + titulo + ' - Por Chofer</td></tr>';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td>#</td><td>Placa</td><td>Chofer</td><td style="text-align:center;">Cantidad Manifiestos</td><td style="text-align:center;">% Participación</td></tr>';
        filasChofer.forEach(function(item, index) {
            var pct = totalMan > 0 ? ((item.cantidad / totalMan) * 100).toFixed(2) : '0.00';
            html += '<tr><td>' + (index + 1) + '</td><td>' + item.placa + '</td><td>' + item.chofer + '</td><td style="text-align:center;">' + item.cantidad + '</td><td style="text-align:center;">' + pct + '%</td></tr>';
        });
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td colspan="3" style="text-align:right;padding-right:10px;">TOTAL</td><td style="text-align:center;">' + totalMan + '</td><td style="text-align:center;">100%</td></tr>';
        html += '</table>';
        hoja = 'Manifiestos Por Chofer';
    } else if (idActivo === 'tabManifiestosCPPlaca') {
        // Vista resumida Por Placa (con choferes)
        var porPlaca = {};
        manifiestos.forEach(function(man) {
            var placa = (man.Veh_Pla && man.Veh_Pla.trim() !== '') ? man.Veh_Pla.trim() : '(Sin placa)';
            var chofer = (man.chofer_nombre && man.chofer_nombre.trim() !== '') ? man.chofer_nombre.trim() : '(Sin asignar)';
            if (!porPlaca[placa]) porPlaca[placa] = { cantidad: 0, choferes: {} };
            porPlaca[placa].cantidad += 1;
            porPlaca[placa].choferes[chofer] = true;
        });
        var filasPlaca = Object.keys(porPlaca).map(function(p) {
            return { placa: p, cantidad: porPlaca[p].cantidad, choferes: Object.keys(porPlaca[p].choferes).join(', ') };
        });
        filasPlaca.sort(function(a, b) { return (b.cantidad || 0) - (a.cantidad || 0); });
        var totalManPlaca = filasPlaca.reduce(function(s, f) { return s + (f.cantidad || 0); }, 0);
        html = '<table border="1" cellpadding="3" style="border-collapse:collapse;">';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td colspan="5" style="padding:8px;text-align:center;">' + titulo + ' - Por Placa</td></tr>';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td>#</td><td>Placa</td><td>Chofer</td><td style="text-align:center;">Cantidad Manifiestos</td><td style="text-align:center;">% Participación</td></tr>';
        filasPlaca.forEach(function(item, index) {
            var pct = totalManPlaca > 0 ? ((item.cantidad / totalManPlaca) * 100).toFixed(2) : '0.00';
            html += '<tr><td>' + (index + 1) + '</td><td>' + item.placa + '</td><td>' + (item.choferes || '') + '</td><td style="text-align:center;">' + item.cantidad + '</td><td style="text-align:center;">' + pct + '%</td></tr>';
        });
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td colspan="3" style="text-align:right;padding-right:10px;">TOTAL</td><td style="text-align:center;">' + totalManPlaca + '</td><td style="text-align:center;">100%</td></tr>';
        html += '</table>';
        hoja = 'Manifiestos Por Placa';
    } else if (idActivo === 'tabManifiestosCPPlanta') {
        // Vista resumida Por Planta
        var porPlanta = {};
        manifiestos.forEach(function(man) {
            var planta = man.Pla_Nom || '(Sin planta)';
            porPlanta[planta] = (porPlanta[planta] || 0) + 1;
        });
        var filasPlanta = Object.keys(porPlanta).map(function(p) { return { planta: p, cantidad: porPlanta[p] }; });
        filasPlanta.sort(function(a, b) { return (b.cantidad || 0) - (a.cantidad || 0); });
        var totalMan = filasPlanta.reduce(function(s, f) { return s + (f.cantidad || 0); }, 0);
        html = '<table border="1" cellpadding="3" style="border-collapse:collapse;">';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td colspan="4" style="padding:8px;text-align:center;">' + titulo + ' - Por Planta</td></tr>';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td>#</td><td>Planta</td><td style="text-align:center;">Cantidad Manifiestos</td><td style="text-align:center;">% Participación</td></tr>';
        filasPlanta.forEach(function(item, index) {
            var pct = totalMan > 0 ? ((item.cantidad / totalMan) * 100).toFixed(2) : '0.00';
            html += '<tr><td>' + (index + 1) + '</td><td>' + item.planta + '</td><td style="text-align:center;">' + item.cantidad + '</td><td style="text-align:center;">' + pct + '%</td></tr>';
        });
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td colspan="2" style="text-align:right;padding-right:10px;">TOTAL</td><td style="text-align:center;">' + totalMan + '</td><td style="text-align:center;">100%</td></tr>';
        html += '</table>';
        hoja = 'Manifiestos Por Planta';
    } else {
        // Vista Detallado (lista completa)
        var tipoVista = datosModalManifiestosChoferPlaca.tipoVista || 'chofer';
        var ordenados = ordenarManifiestosParaTabla(manifiestos, 'fecha');
        var numCols = (tipoVista === 'chofer' || tipoVista === 'placa') ? 6 : 7;
        html = '<table border="1" cellpadding="3" style="border-collapse:collapse;">';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td colspan="' + numCols + '" style="padding:8px;text-align:center;">' + titulo + '</td></tr>';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;">';
        html += '<td>#</td><td>No. Manifiesto</td><td>Fecha</td><td>Horario</td><td>Planta</td>';
        if (tipoVista === 'chofer') html += '<td>Placa</td>';
        else if (tipoVista === 'placa') html += '<td>Chofer</td>';
        else html += '<td>Placa</td><td>Chofer</td>';
        html += '<td>T. Estancia</td>';
        html += '</tr>';
        ordenados.forEach(function(man, i) {
            var tProm = man.minutos_estancia || 0;
            var tFmt = tProm > 0 ? (tProm >= 60 ? (Math.floor(tProm / 60) + 'h ' + Math.round(tProm % 60) + 'm') : (Math.round(tProm) + ' min')) : '-';
            html += '<tr>';
            html += '<td>' + (i + 1) + '</td><td>' + (man.ManNum || '') + '</td><td>' + (man.Man_Fec || '') + '</td><td>' + (man.Man_Hor || '') + '</td><td>' + (man.Pla_Nom || '') + '</td>';
            if (tipoVista === 'chofer') html += '<td>' + (man.Veh_Pla || '') + '</td>';
            else if (tipoVista === 'placa') html += '<td>' + (man.chofer_nombre || '') + '</td>';
            else { html += '<td>' + (man.Veh_Pla || '') + '</td><td>' + (man.chofer_nombre || '') + '</td>'; }
            html += '<td>' + tFmt + '</td>';
            html += '</tr>';
        });
        html += '</table>';
    }
    exportarTablaAExcel(html, nombre, hoja);
}

function exportarTablaAExcel(htmlTabla, nombreArchivo, nombreHoja) {
    if (!htmlTabla || !nombreArchivo) return;
    var form = $('<form>', { method: 'POST', action: '../../Librerias/exportar/ficheroExcel.php', target: '_blank' });
    form.append($('<input>', { type: 'hidden', name: 'datos_a_enviar', value: htmlTabla }));
    form.append($('<input>', { type: 'hidden', name: 'nombre', value: nombreArchivo }));
    form.append($('<input>', { type: 'hidden', name: 'hoja', value: nombreHoja || 'Manifiestos' }));
    $('body').append(form);
    form.submit();
    form.remove();
}

function imprimirReporte() {
    if (!datosDashboardActual || datosDashboardActual.length === 0) {
        alert('No hay datos para imprimir. Por favor, cargue el dashboard primero.');
        return;
    }
    
    // Generar HTML igual que imprimirReporteFecha (un solo gráfico) pero con todas las fechas
    // Usa abrirVentanaImpresion - la misma función que imprime bien un solo gráfico
    var html = '';
    datosDashboardActual.forEach(function(config) {
        html += '<div class="config-card">';
        html += '<div class="config-header">';
        html += '<h4>Configuración de Turnos #' + config.Tur_Cod + '</h4>';
        html += '<p style="margin: 5px 0 0 0; font-size: 14px;">Período: ' + config.Tur_Fei + ' - ' + config.Tur_Fef + '</p>';
        html += '</div>';
        html += '<div class="config-stats">';
        html += '<div class="stat-card total-turnos"><div class="stat-label">Total Turnos</div><div class="stat-value">' + (config.total_turnos_detalle || 0) + '</div></div>';
        html += '<div class="stat-card total-cupos"><div class="stat-label">Total Cupos</div><div class="stat-value">' + (config.total_cupos || 0) + '</div></div>';
        html += '<div class="stat-card cupos-ocupados"><div class="stat-label">Cupos Ocupados</div><div class="stat-value">' + (config.total_cupos_ocupados || 0) + '</div></div>';
        html += '<div class="stat-card cupos-libres"><div class="stat-label">Cupos Libres</div><div class="stat-value">' + (config.total_cupos_libres || 0) + '</div></div>';
        html += '<div class="stat-card ocupacion"><div class="stat-label">% Ocupación</div><div class="stat-value">' + (config.porcentaje_ocupacion_general || 0) + '%</div></div>';
        html += '</div>';
        var turnosPrint = getTurnosFiltradosPorFechaConfig(config.turnos_detalle);
        html += generarVistaDiasConTresOpciones(turnosPrint || [], true);
        html += '</div>';
    });
    
    abrirVentanaImpresion(html, 'Dashboard de Turnos');
}

function exportarExcel() {
    if (!datosDashboardActual || datosDashboardActual.length === 0) {
        alert('No hay datos para exportar. Por favor, cargue el dashboard primero.');
        return;
    }
    
    var Tur_Cod = $('#select_configuracion').val() || '';
    var nombreConfig = 'Todas';
    if (Tur_Cod) {
        var $select = $('#select_configuracion');
        var textoSeleccionado = $select.find('option:selected').text();
        nombreConfig = textoSeleccionado.replace(/[^a-zA-Z0-9]/g, '_');
    }
    
    // Crear tabla HTML con formato para Excel (estilos inline + style block para compatibilidad en producción)
    // table-layout:fixed y colgroup limitan a 7 columnas para evitar que Excel extienda colores a columnas vacías
    var htmlExcel = '<style type="text/css">';
    htmlExcel += 'table.xl-dashboard { border-collapse: collapse; table-layout: fixed; width: 600px; mso-displayed-decimal-separator: "\\,"; mso-displayed-thousand-separator: "\\."; }';
    htmlExcel += 'table.xl-dashboard td, table.xl-dashboard th { border: 1px solid #2C5D94; padding: 4px 8px; mso-number-format: "\\@"; }';
    htmlExcel += '.xl-header { background-color: #2C5D94 !important; color: white !important; font-weight: bold !important; }';
    htmlExcel += '.xl-stats { background-color: #f0f0f0 !important; font-weight: bold !important; }';
    htmlExcel += '.xl-fecha { background-color: #d1ecf1 !important; color: #0c5460 !important; font-weight: bold !important; }';
    htmlExcel += '.xl-col-header { background-color: #e0e0e0 !important; font-weight: bold !important; }';
    htmlExcel += '.xl-data { background-color: #ffffff !important; }';
    htmlExcel += '</style>';
    htmlExcel += '<table class="xl-dashboard" border="1" cellpadding="3" cellspacing="0" style="border-collapse: collapse; table-layout: fixed; width: 600px;">';
    htmlExcel += '<colgroup><col><col><col><col><col><col><col></colgroup>';
    
    datosDashboardActual.forEach(function(config) {
        var configHeader = 'Configuración de Turnos #' + config.Tur_Cod;
        var configPeriodo = 'Período: ' + config.Tur_Fei + ' - ' + config.Tur_Fef;
        
        htmlExcel += '<tr class="xl-header" bgcolor="#2C5D94" style="background-color: #2C5D94; color: white; font-weight: bold;">';
        htmlExcel += '<td colspan="7" bgcolor="#2C5D94" style="text-align: center; font-size: 12px; padding: 8px; color: white;">' + configHeader + ' - ' + configPeriodo + '</td>';
        htmlExcel += '</tr>';
        
        htmlExcel += '<tr class="xl-stats" bgcolor="#f0f0f0" style="background-color: #f0f0f0; font-weight: bold;">';
        htmlExcel += '<td colspan="7" bgcolor="#f0f0f0" style="padding: 5px;">Total Turnos: ' + (config.total_turnos_detalle || 0) + ' | Total Cupos: ' + (config.total_cupos || 0) + ' | Cupos Ocupados: ' + (config.total_cupos_ocupados || 0) + ' | Cupos Libres: ' + (config.total_cupos_libres || 0) + ' | % Ocupación: ' + (config.porcentaje_ocupacion_general || 0) + '%</td>';
        htmlExcel += '</tr>';
        
        // Agrupar turnos por fecha (aplicar filtro omitir días sin manifiestos)
        var turnosParaExcel = getTurnosFiltradosPorFechaConfig(config.turnos_detalle);
        var turnosPorFecha = {};
        (turnosParaExcel || []).forEach(function(turno) {
            var fecha = turno.Tud_Fec || '-';
            if (!turnosPorFecha[fecha]) turnosPorFecha[fecha] = [];
            turnosPorFecha[fecha].push(turno);
        });
        
        var fechasOrdenadas = Object.keys(turnosPorFecha).sort(function(a, b) {
            if (a === '-') return 1;
            if (b === '-') return -1;
            var partesA = a.split('/'), partesB = b.split('/');
            var fechaA = new Date(partesA[2], partesA[1] - 1, partesA[0]);
            var fechaB = new Date(partesB[2], partesB[1] - 1, partesB[0]);
            return fechaA - fechaB;
        });
        
        fechasOrdenadas.forEach(function(fecha) {
            var turnosFecha = turnosPorFecha[fecha];
            var diaSemana = '';
            if (fecha !== '-') {
                var partes = fecha.split('/');
                if (partes.length === 3) {
                    var dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                    diaSemana = ' - ' + dias[new Date(partes[2], partes[1] - 1, partes[0]).getDay()];
                }
            }
            var totalOcupados = turnosFecha.reduce(function(s, t) { return s + (t.ocupados || 0); }, 0);
            
            htmlExcel += '<tr class="xl-fecha" bgcolor="#d1ecf1" style="background-color: #d1ecf1; color: #0c5460; font-weight: bold;">';
            htmlExcel += '<td colspan="7" bgcolor="#d1ecf1" style="text-align: left; font-size: 13px; padding: 8px; color: #0c5460;">Fecha: ' + fecha + diaSemana + ' Total Cupos Ocupados: ' + totalOcupados + '</td>';
            htmlExcel += '</tr>';
            
            htmlExcel += '<tr class="xl-col-header" bgcolor="#e0e0e0" style="background-color: #e0e0e0; font-weight: bold;">';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Horario</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Cupos Program.</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Manif. Creados</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Inactivos</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Cupos Libres</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">% Ocupación</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Barra Ocupación</td>';
            htmlExcel += '</tr>';
            
            turnosFecha.forEach(function(turno) {
                var porcentaje = turno.porcentaje_ocupacion || 0;
                htmlExcel += '<tr>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + (turno.horario || '') + '</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (turno.cupos || 0) + '</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (turno.ocupados || 0) + '</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (turno.manifiestos_inactivos || 0) + '</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (turno.libres || 0) + '</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + porcentaje + '%</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + porcentaje + '%</td>';
                htmlExcel += '</tr>';
            });
            
            htmlExcel += '<tr><td colspan="7" style="height: 5px; border: none;"></td></tr>';
        });
        
        htmlExcel += '<tr><td colspan="7" style="height: 5px; border: none;"></td></tr>';
    });
    
    htmlExcel += '</table>';
    
    // Usar el método de exportación del proyecto
    var fecha = new Date();
    var fechaStr = fecha.getDate() + '-' + (fecha.getMonth() + 1) + '-' + fecha.getFullYear();
    var nombreArchivo = 'reportes_manifiestos_' + fechaStr;  
    
    // Crear formulario para enviar a ficheroExcel.php
    var form = $('<form>', {
        method: 'POST',
        action: '../../Librerias/exportar/ficheroExcel.php',
        target: '_blank'
    });
    
    form.append($('<input>', {
        type: 'hidden',
        name: 'datos_a_enviar',
        value: htmlExcel
    }));
    
    form.append($('<input>', {
        type: 'hidden',
        name: 'nombre',
        value: nombreArchivo
    }));
    
    form.append($('<input>', {
        type: 'hidden',
        name: 'hoja',
        value: 'Dashboard Turnos'
    }));
    
    $('body').append(form);
    form.submit();
    form.remove();
}

// ========== TAB POR RANGO DE FECHAS ==========
function cargarDashboardRango() {
    var fechaIni = $('#fecha_inicio_rango').val() || '';
    var fechaFin = $('#fecha_fin_rango').val() || '';
    if (!fechaIni || !fechaFin) {
        alert('Por favor seleccione el rango de fechas (desde y hasta).');
        return;
    }
    if (fechaIni > fechaFin) {
        alert('La fecha de inicio no puede ser mayor que la fecha fin.');
        return;
    }
    
    var postData = { getDashboardPorRangoAjax: true, fecha_inicio: fechaIni, fecha_fin: fechaFin };
    var Pla_Cod = $('#select_planta_rango').val() || '';
    if (Pla_Cod) postData.Pla_Cod = Pla_Cod;
    
    $('#dashboardContentRango').html('<div class="loading"><i class="fa fa-spinner fa-spin"></i><p>Cargando reporte...</p></div>');
    
    $.get('', postData, function(response) {
        if (response.success && response.dias) {
            datosDashboardRango = response;
            mostrarDashboardRango(response);
        } else {
            $('#dashboardContentRango').html('<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> ' + (response.message || 'No se encontraron datos para el rango seleccionado.') + '</div>');
            datosDashboardRango = null;
        }
    }, 'json').fail(function() {
        $('#dashboardContentRango').html('<div class="alert alert-danger">Error al cargar el reporte. Por favor, intente nuevamente.</div>');
        datosDashboardRango = null;
    });
}

function getDiasFiltradosRango(dias) {
    if (!$('#omitir_dias_sin_manifiestos').is(':checked')) {
        return dias || [];
    }
    // Suprimir del reporte días sin manifiestos: solo mostrar días que tienen al menos un cupo ocupado
    var diasConManifiestos = (dias || []).filter(function(d) { return (d.total_cupos_ocupados || 0) > 0; });
    // Dentro de cada día, suprimir turnos que no poseen manifiestos
    return diasConManifiestos.map(function(d) {
        var turnosConManifiestos = (d.turnos_detalle || []).filter(function(t) { return (t.ocupados || 0) > 0; });
        var totalCupos = 0, totalOcupados = 0;
        turnosConManifiestos.forEach(function(t) {
            totalCupos += t.cupos || 0;
            totalOcupados += t.ocupados || 0;
        });
        return {
            Tud_Fec: d.Tud_Fec,
            Tud_Fec_SQL: d.Tud_Fec_SQL,
            dia_semana: d.dia_semana,
            turnos_detalle: turnosConManifiestos,
            total_cupos: totalCupos,
            total_cupos_ocupados: totalOcupados,
            total_cupos_libres: Math.max(0, totalCupos - totalOcupados),
            porcentaje_ocupacion_general: totalCupos > 0 ? Math.round((totalOcupados / totalCupos) * 10000) / 100 : 0
        };
    });
}

function alternarVistaRango() {
    modoVistaBarrasRango = !modoVistaBarrasRango;
    var $btn = $('#btnVistaBarrasRango');
    var $texto = $('#textoVistaRango');
    if (modoVistaBarrasRango) {
        $texto.text('Vista Tabla');
        $btn.removeClass('btn-warning').addClass('btn-default');
    } else {
        $texto.text('Vista Barras');
        $btn.removeClass('btn-default').addClass('btn-warning');
    }
    if (datosDashboardRango && datosDashboardRango.dias) {
        mostrarDashboardRango(datosDashboardRango);
    }
}

function mostrarDashboardRango(response) {
    var dias = getDiasFiltradosRango(response.dias);
    var html = '';
    
    if (dias.length === 0) {
        var msg = $('#omitir_dias_sin_manifiestos').is(':checked') && (response.dias || []).length > 0
            ? 'Ningún día posee manifiestos en el rango seleccionado.'
            : 'No se encontraron turnos en el rango de fechas seleccionado.';
        html = '<div class="alert alert-info">' + msg + '</div>';
    } else {
        var fmtIni = formatearFechaRango(response.fecha_inicio);
        var fmtFin = formatearFechaRango(response.fecha_fin);
        html += '<div class="config-card" style="margin-bottom: 15px;">';
        html += '<div class="config-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">';
        html += '<div>';
        html += '<h4 style="margin: 0;">Reporte por Rango: ' + fmtIni + ' - ' + fmtFin + '</h4>';
        html += '<p style="margin: 5px 0 0 0; font-size: 14px;">' + dias.length + ' día(s) | Planta: ' + ($('#select_planta_rango option:selected').text() || 'Todas') + '</p>';
        html += '</div>';
        html += '<div style="flex-shrink: 0;">';
        html += '<button class="btn btn-sm btn-info" onclick="verManifiestosRangoCompleto(\'' + (response.fecha_inicio || '') + '\', \'' + (response.fecha_fin || '') + '\');" title="Ver todos los manifiestos del rango" style="padding: 5px 10px; font-size: 12px;">';
        html += '<i class="fa fa-list"></i> Ver Manifiestos';
        html += '</button>';
        html += '</div>';
        html += '</div>';
        
        var totalCupos = 0, totalOcupados = 0;
        dias.forEach(function(d) { totalCupos += d.total_cupos || 0; totalOcupados += d.total_cupos_ocupados || 0; });
        var totalLibres = Math.max(0, totalCupos - totalOcupados);
        var pctTotal = totalCupos > 0 ? ((totalOcupados / totalCupos) * 100).toFixed(2) : 0;
        
        html += '<div class="config-stats">';
        html += '<div class="stat-card total-turnos"><div class="stat-label">Total Días</div><div class="stat-value">' + dias.length + '</div></div>';
        html += '<div class="stat-card total-cupos"><div class="stat-label">Total Cupos</div><div class="stat-value">' + totalCupos + '</div></div>';
        html += '<div class="stat-card cupos-ocupados"><div class="stat-label">Cupos Ocupados</div><div class="stat-value">' + totalOcupados + '</div></div>';
        html += '<div class="stat-card cupos-libres"><div class="stat-label">Cupos Libres</div><div class="stat-value">' + totalLibres + '</div></div>';
        html += '<div class="stat-card ocupacion"><div class="stat-label">% Ocupación</div><div class="stat-value">' + pctTotal + '%</div></div>';
        html += '</div>';
        html += '</div>';
        
        if (modoVistaBarrasRango) {
            html += '<div class="config-card">';
            html += generarVistaBarrasUnicoPorDias(dias, false);
            html += '</div>';
        } else {
            dias.forEach(function(dia) {
                var tudFecSql = dia.Tud_Fec_SQL || (dia.Tud_Fec ? dia.Tud_Fec.replace(/(\d{2})\/(\d{2})\/(\d{4})/, '$3-$2-$1') : '');
                html += '<div class="config-card">';
                html += '<div class="fecha-header" style="margin-top: 15px; margin-bottom: 10px; padding: 8px 12px; background-color: #e9ecef; border-left: 4px solid #2C5D94; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">';
                html += '<h5 style="margin: 0; font-size: 16px; font-weight: 600; color: #2C5D94; flex: 1;">';
                html += '<i class="fa fa-calendar"></i> ' + dia.Tud_Fec + ' - ' + dia.dia_semana + ' | Total Cupos Ocupados: ' + (dia.total_cupos_ocupados || 0) + '</h5>';
                html += '<div style="flex-shrink: 0;">';
                html += '<button class="btn btn-sm btn-info" onclick="verManifiestosRangoDia(\'' + tudFecSql + '\');" title="Ver manifiestos del día" style="padding: 4px 8px; font-size: 11px;">';
                html += '<i class="fa fa-list"></i> Ver Manifiestos';
                html += '</button>';
                html += '</div>';
                html += '</div>';
                
                html += '<table class="table turnos-table table-bordered table-striped">';
                html += '<thead><tr>';
                html += '<th>Horario</th><th>Cupos Program.</th><th>Manif. Creados</th><th>Inactivos</th><th>Cupos Libres</th><th>% Ocupación</th><th>Barra Ocupación</th><th>Acciones</th>';
                html += '</tr></thead><tbody>';
                
                (dia.turnos_detalle || []).forEach(function(t) {
                    var pct = t.porcentaje_ocupacion || 0;
                    var clase = pct >= 80 ? 'bajo' : (pct >= 50 ? 'medio' : 'alto');
                    var anchoBarra = Math.min(pct, 100);
                    var tudFec = (t.Tud_Fec || tudFecSql || '').toString().replace(/'/g, "\\'");
                    var tudHin = (t.Tud_Hin || '').toString().replace(/'/g, "\\'");
                    var tudHfi = (t.Tud_Hfi || '').toString().replace(/'/g, "\\'");
                    html += '<tr>';
                    html += '<td>' + (t.horario || '') + '</td>';
                    html += '<td>' + (t.cupos || 0) + '</td>';
                    html += '<td>' + (t.ocupados || 0) + '</td>';
                    html += '<td>' + (t.manifiestos_inactivos || 0) + '</td>';
                    html += '<td>' + (t.libres || 0) + '</td>';
                    html += '<td><span class="badge-ocupacion ' + clase + '">' + pct + '%</span></td>';
                    html += '<td>';
                    html += '<div class="barra-progreso-tabla"><div class="barra-progreso-fondo">';
                    html += '<div class="barra-progreso-relleno ' + clase + '" style="width: ' + anchoBarra + '%;"></div>';
                    html += '</div></div>';
                    html += '</td>';
                    html += '<td>';
                    html += '<button class="btn btn-primary btn-xs btn-ver-manifiestos" onclick="verManifiestosRangoSlot(\'' + tudFec + '\', \'' + tudHin + '\', \'' + tudHfi + '\');" title="Ver manifiestos de este horario">';
                    html += '<i class="fa fa-list"></i> Ver Manifiestos';
                    html += '</button>';
                    html += '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                html += '</div>';
            });
        }
    }
    
    $('#dashboardContentRango').html(html);
}

function imprimirReporteRango() {
    var dias = datosDashboardRango ? getDiasFiltradosRango(datosDashboardRango.dias) : [];
    if (!datosDashboardRango || dias.length === 0) {
        alert('No hay datos para imprimir. Por favor, ejecute una búsqueda primero.');
        return;
    }
    
    var totalCupos = 0, totalOcupados = 0;
    dias.forEach(function(d) { totalCupos += d.total_cupos || 0; totalOcupados += d.total_cupos_ocupados || 0; });
    var totalLibres = Math.max(0, totalCupos - totalOcupados);
    var pctTotal = totalCupos > 0 ? ((totalOcupados / totalCupos) * 100).toFixed(2) : 0;
    var plantaNombre = $('#select_planta_rango option:selected').text() || 'Todas';
    
    var html = '<div class="config-card">';
    html += '<div class="config-header">';
    html += '<h4>Reporte por Rango: ' + formatearFechaRango(datosDashboardRango.fecha_inicio) + ' - ' + formatearFechaRango(datosDashboardRango.fecha_fin) + '</h4>';
    html += '<p style="margin: 5px 0 0 0; font-size: 14px;">' + dias.length + ' día(s) | Planta: ' + plantaNombre + '</p>';
    html += '</div>';
    html += '<div class="config-stats">';
    html += '<div class="stat-card total-turnos"><div class="stat-label">Total Días</div><div class="stat-value">' + dias.length + '</div></div>';
    html += '<div class="stat-card total-cupos"><div class="stat-label">Total Cupos</div><div class="stat-value">' + totalCupos + '</div></div>';
    html += '<div class="stat-card cupos-ocupados"><div class="stat-label">Cupos Ocupados</div><div class="stat-value">' + totalOcupados + '</div></div>';
    html += '<div class="stat-card cupos-libres"><div class="stat-label">Cupos Libres</div><div class="stat-value">' + totalLibres + '</div></div>';
    html += '<div class="stat-card ocupacion"><div class="stat-label">% Ocupación</div><div class="stat-value">' + pctTotal + '%</div></div>';
    html += '</div>';
    
    if (modoVistaBarrasRango) {
        html += generarVistaBarrasUnicoPorDias(dias, true);
    } else {
        dias.forEach(function(dia) {
            html += '<div class="fecha-header" style="margin-top: 10px; margin-bottom: 5px; padding: 5px 8px; background-color: #e9ecef; border-left: 4px solid #2C5D94; border-radius: 4px;">';
            html += '<h5 style="margin: 0; font-size: 12px; font-weight: 600; color: #2C5D94;">' + dia.Tud_Fec + ' - ' + dia.dia_semana + ' | Total Cupos Ocupados: ' + (dia.total_cupos_ocupados || 0) + '</h5>';
            html += '</div>';
            html += '<table class="turnos-table"><thead><tr>';
            html += '<th>Horario</th><th>Cupos Program.</th><th>Manif. Creados</th><th>Inactivos</th><th>Cupos Libres</th><th>% Ocupación</th><th>Barra Ocupación</th>';
            html += '</tr></thead><tbody>';
            (dia.turnos_detalle || []).forEach(function(t) {
                var pct = t.porcentaje_ocupacion || 0;
                var clase = pct >= 80 ? 'bajo' : (pct >= 50 ? 'medio' : 'alto');
                var anchoBarra = Math.min(pct, 100);
                html += '<tr>';
                html += '<td>' + (t.horario || '') + '</td>';
                html += '<td>' + (t.cupos || 0) + '</td>';
                html += '<td>' + (t.ocupados || 0) + '</td>';
                html += '<td>' + (t.manifiestos_inactivos || 0) + '</td>';
                html += '<td>' + (t.libres || 0) + '</td>';
                html += '<td><span class="badge-ocupacion ' + clase + '">' + pct + '%</span></td>';
                html += '<td><div class="barra-progreso-tabla"><div class="barra-progreso-fondo">';
                html += '<div class="barra-progreso-relleno ' + clase + '" style="width: ' + anchoBarra + '%;"></div>';
                html += '</div></div></td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
        });
    }
    html += '</div>';
    
    var estilosImpresion = `
        <style>
            @page { margin: 1.2cm; size: A4 landscape; }
            * { margin-top: 0; box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body { margin: 0; padding: 10px; font-family: Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; overflow: visible; width: 100%; }
            .config-card { width: 100% !important; max-width: 100% !important; }
            .chart-main-area { min-width: 0 !important; overflow: visible !important; }
            .config-card { margin-bottom: 10px; padding: 5px; border: 1px solid #2C5D94; border-radius: 4px; }
            .config-header { padding: 5px 8px; margin-bottom: 5px; background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%) !important; color: white !important; border-radius: 4px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .config-header h4 { font-size: 12px; margin: 0; }
            .config-header p { font-size: 10px; margin: 3px 0 0 0; }
            .config-stats { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 5px; }
            .stat-card { padding: 5px 8px; min-width: 80px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; text-align: center; }
            .stat-card .stat-label { font-size: 9px; margin-bottom: 2px; color: #6c757d; font-weight: 600; }
            .stat-card .stat-value { font-size: 14px; font-weight: bold; color: #2C5D94; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .stat-card.total-turnos .stat-value { color: #17a2b8; }
            .stat-card.total-cupos .stat-value { color: #28a745; }
            .stat-card.cupos-ocupados .stat-value { color: #ffc107; }
            .stat-card.cupos-libres .stat-value { color: #17a2b8; }
            .stat-card.ocupacion .stat-value { color: #dc3545; }
            .fecha-header { margin-top: 10px !important; margin-bottom: 5px !important; padding: 5px 8px !important; background-color: #e9ecef; border-left: 4px solid #2C5D94; border-radius: 4px; page-break-after: avoid; }
            .fecha-header h5 { font-size: 12px !important; margin: 0; color: #2C5D94; font-weight: 600; }
            .fecha-content { page-break-inside: avoid; page-break-after: always; }
            .fecha-content + .fecha-header { page-break-before: always; }
            .turnos-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 5px; }
            .turnos-table thead th { background: #2C5D94 !important; color: white !important; padding: 4px 5px; text-align: left; font-weight: 600; font-size: 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .turnos-table tbody td { padding: 4px 5px; border-bottom: 1px solid #dee2e6; font-size: 10px; }
            .barra-progreso-tabla { width: 100%; min-width: 80px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .barra-progreso-fondo { position: relative; width: 100%; height: 18px !important; min-height: 18px !important; background: #e9ecef !important; border-radius: 4px; overflow: hidden; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .barra-progreso-relleno { position: absolute !important; left: 0 !important; top: 0 !important; bottom: 0 !important; height: 100% !important; min-height: 18px !important; min-width: 3px !important; display: block !important; border-radius: 4px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .barra-progreso-relleno.bajo { background-color: #dc3545 !important; }
            .barra-progreso-relleno.medio { background-color: #ffc107 !important; }
            .barra-progreso-relleno.alto { background-color: #28a745 !important; }
            .badge-ocupacion { padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: 600; }
            .badge-ocupacion.bajo { background-color: #f8d7da; color: #721c24; }
            .badge-ocupacion.medio { background-color: #fff3cd; color: #856404; }
            .badge-ocupacion.alto { background-color: #d4edda; color: #155724; }
            .chart-dashboard-container { width: 100% !important; max-width: 100% !important; padding: 20px 15px; background: #fafbfc; border-radius: 6px; border: 1px solid #e2e8ec; margin: 0 auto; box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; page-break-inside: avoid; }
            .chart-leyenda { display: flex; justify-content: center; gap: 20px; margin-bottom: 15px; font-size: 12px; color: #6c757d; }
            .chart-leyenda-item { display: flex; align-items: center; gap: 6px; }
            .chart-leyenda-color { display: inline-block; width: 14px; height: 14px; border-radius: 3px; }
            .chart-with-axes { display: flex !important; align-items: flex-start !important; width: 100% !important; max-width: 100% !important; margin-top: 10px; page-break-inside: avoid !important; }
            .chart-y-axis { flex-shrink: 0; display: flex !important; flex-direction: column; align-items: flex-end; padding-right: 10px; border-right: 1px solid #dee2e6; position: relative; }
            .chart-y-label { font-size: 11px; font-weight: 600; color: #2C5D94; position: absolute; top: -26px; right: 0; margin: 0; }
            .chart-y-ticks { display: flex; flex-direction: column; justify-content: space-between; }
            .chart-y-tick { font-size: 10px; color: #6c757d; line-height: 1; display: flex; align-items: flex-end; justify-content: flex-end; }
            .chart-main-area { flex: 1; position: relative; min-width: 0; display: flex; flex-direction: column; align-items: stretch; }
            .chart-grid { position: absolute; left: 0; right: 0; top: 0; pointer-events: none; }
            .chart-grid-line { position: absolute; left: 0; right: 0; height: 1px; background: rgba(44, 93, 148, 0.2); }
            .chart-x-axis-line { position: absolute; left: 0; right: 0; bottom: 0; height: 1px; background: #adb5bd; opacity: 0.8; }
            .chart-bars-area { display: flex; flex-direction: column; align-items: stretch; width: 100% !important; max-width: 100% !important; padding: 0 0 0 12px; margin: 0; page-break-inside: avoid !important; }
            .chart-bars-row { display: flex !important; justify-content: center !important; align-items: flex-end; flex-wrap: nowrap; position: relative; z-index: 1; padding: 0; margin: 0; width: 100%; box-sizing: border-box; }
            .chart-labels-row { display: flex; justify-content: center; flex-wrap: nowrap; width: 100%; padding: 0 0 0 12px; margin-top: 6px; box-sizing: border-box; }
            .chart-label-cell { display: flex; flex-direction: column; justify-content: flex-start; align-items: center; flex: 0 0 auto !important; flex-shrink: 0 !important; min-width: 25px !important; }
            .chart-bar-total { font-size: 10px; font-weight: 600; color: #2C5D94; margin-bottom: 4px; text-align: center; flex-shrink: 0; }
            .chart-label-cell .chart-bar-porcentaje { margin-top: 1px; margin-bottom: 0; font-size: 8px; color: #6c757d; }
            .chart-bars-n-pocas.chart-bars-row, .chart-bars-n-pocas.chart-labels-row { gap: 25px; }
            .chart-bars-n-pocas .chart-bar-wrapper, .chart-bars-n-pocas .chart-label-cell { flex: 0 0 auto !important; flex-shrink: 0 !important; width: 32px !important; min-width: 20px !important; max-width: 50px !important; }
            .chart-bars-n-pocas .chart-bar-columna { width: 100%; max-width: 50px; }
            .chart-bars-n-medias.chart-bars-row, .chart-bars-n-medias.chart-labels-row { gap: 20px; }
            .chart-bars-n-medias .chart-bar-wrapper, .chart-bars-n-medias .chart-label-cell { flex: 0 0 auto !important; flex-shrink: 0 !important; width: 28px !important; min-width: 20px !important; max-width: 40px !important; }
            .chart-bars-n-medias .chart-bar-columna { width: 100%; max-width: 40px; }
            .chart-bars-n-muchas.chart-bars-row, .chart-bars-n-muchas.chart-labels-row { gap: 25px; }
            .chart-bars-n-muchas .chart-bar-wrapper, .chart-bars-n-muchas .chart-label-cell { flex: 0 0 auto !important; flex-shrink: 0 !important; width: 25px !important; min-width: 20px !important; }
            .chart-bars-n-muchas .chart-bar-columna { width: 100%; max-width: 25px; }
            .chart-bars-row { flex-shrink: 0 !important; }
            .chart-bar-wrapper { display: flex; flex-direction: column; align-items: center; flex: 0 0 auto !important; flex-shrink: 0 !important; min-width: 25px !important; height: 100%; padding: 0; margin: 0; }
            .chart-bar-spacer { flex: 1 1 0; min-height: 0; }
            .chart-bar-porcentaje { font-size: 9px; font-weight: 600; color: #2C5D94; margin: 0; text-align: center; }
            .chart-bar-columna { width: 100%; flex-shrink: 0; display: flex; flex-direction: column-reverse; border-radius: 4px 4px 0 0; overflow: hidden; background: #e9ecef; }
            .chart-segmento { display: flex; align-items: center; justify-content: center; min-height: 0; flex-shrink: 0; width: 100%; text-align: center; }
            .chart-segmento-ocupados { background: #2C5D94 !important; color: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .chart-segmento-libres { background: #fd7e14 !important; color: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .chart-segmento-valor { font-size: 10px; font-weight: 700; line-height: 1; width: 100%; text-align: center; display: block; }
            .chart-segmento-pequeno { min-height: 18px !important; }
            .chart-x-label { font-size: 10px; white-space: nowrap; text-align: center; width: 100%; }
            .chart-dashboard-container { width: 100% !important; max-width: 100% !important; page-break-inside: avoid; }
            .chart-por-dias { width: 100% !important; max-width: 100% !important; }
            .chart-por-dias .chart-bars-row, .chart-por-dias .chart-labels-row { flex-wrap: nowrap !important; width: 100% !important; justify-content: center !important; }
            .chart-por-dias .chart-bar-wrapper, .chart-por-dias .chart-label-cell { min-width: 12px !important; }
            .chart-por-dias .chart-bars-n-pocas.chart-bars-row, .chart-por-dias .chart-bars-n-pocas.chart-labels-row { gap: 26px !important; }
            .chart-por-dias .chart-bars-n-pocas .chart-bar-wrapper, .chart-por-dias .chart-bars-n-pocas .chart-label-cell { flex: 0 0 auto !important; width: 32px !important; min-width: 20px !important; max-width: 50px !important; }
            .chart-por-dias .chart-bars-n-pocas .chart-bar-columna { width: 100%; max-width: 50px; }
            .chart-por-dias .chart-bars-n-medias.chart-bars-row, .chart-por-dias .chart-bars-n-medias.chart-labels-row { gap: 21px !important; }
            .chart-por-dias .chart-bars-n-medias .chart-bar-wrapper, .chart-por-dias .chart-bars-n-medias .chart-label-cell { flex: 0 0 auto !important; width: 28px !important; min-width: 20px !important; max-width: 40px !important; }
            .chart-por-dias .chart-bars-n-medias .chart-bar-columna { width: 100%; max-width: 40px; }
            .chart-por-dias .chart-bars-n-muchas.chart-bars-row, .chart-por-dias .chart-bars-n-muchas.chart-labels-row { gap: 26px !important; }
            .chart-por-dias .chart-bars-n-muchas .chart-bar-wrapper, .chart-por-dias .chart-bars-n-muchas .chart-label-cell { flex: 0 0 auto !important; width: 25px !important; min-width: 20px !important; }
            .chart-por-dias .chart-bars-n-muchas .chart-bar-columna { width: 100%; max-width: 25px; }
            .chart-por-dias .chart-bars-n-super.chart-bars-row, .chart-por-dias .chart-bars-n-super.chart-labels-row { gap: 9px !important; }
            .chart-por-dias .chart-bars-n-super .chart-bar-wrapper, .chart-por-dias .chart-bars-n-super .chart-label-cell { flex: 0 0 auto !important; width: 20px !important; min-width: 16px !important; }
            .chart-por-dias .chart-bars-n-super .chart-bar-columna { width: 100%; max-width: 20px; }
            .chart-por-dias .chart-bar-columna { max-width: none !important; }
            .chart-por-dias .chart-label-cell { justify-content: center !important; }
            .chart-por-dias .chart-x-label { text-align: center !important; font-size: 8px !important; }
            .chart-por-dias .chart-bar-porcentaje { font-size: 8px !important; }
            .chart-por-dias .chart-segmento-valor { font-size: 8px !important; }
            .fecha-content { display: block !important; }
            .chart-fecha-content { display: block !important; }
            .fecha-header button, .fecha-header .toggle-icon { display: none !important; }
            .fecha-print-block { page-break-inside: avoid !important; break-inside: avoid !important; page-break-after: always !important; }
            .fecha-print-block:last-child { page-break-after: auto !important; }
            @media print {
                body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                .chart-segmento-ocupados, .chart-segmento-libres, .config-header { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                .fecha-print-block { page-break-inside: avoid !important; break-inside: avoid !important; }
            }
        </style>
    `;
    
    var ventanaImpresion = window.open('', '_blank', 'width=1200,height=800');
    ventanaImpresion.document.write('<html><head><title>Reporte por Rango de Fechas</title>' + estilosImpresion + '</head><body>');
    ventanaImpresion.document.write(html);
    ventanaImpresion.document.write('</body></html>');
    ventanaImpresion.document.close();
    ventanaImpresion.onload = function() {
        ventanaImpresion.focus();
        setTimeout(function() {
            ventanaImpresion.print();
            ventanaImpresion.onafterprint = function() { ventanaImpresion.close(); };
        }, 500);
    };
}

function exportarExcelRango() {
    var dias = datosDashboardRango ? getDiasFiltradosRango(datosDashboardRango.dias) : [];
    if (!datosDashboardRango || dias.length === 0) {
        alert('No hay datos para exportar. Por favor, ejecute una búsqueda primero.');
        return;
    }
    
    var totalCupos = 0, totalOcupados = 0;
    dias.forEach(function(d) { totalCupos += d.total_cupos || 0; totalOcupados += d.total_cupos_ocupados || 0; });
    var totalLibres = Math.max(0, totalCupos - totalOcupados);
    var pctTotal = totalCupos > 0 ? ((totalOcupados / totalCupos) * 100).toFixed(2) : 0;
    
    // table-layout:fixed y colgroup limitan a 7 columnas para evitar que Excel extienda colores a columnas vacías
    var htmlExcel = '<style type="text/css">';
    htmlExcel += 'table.xl-dashboard { border-collapse: collapse; table-layout: fixed; width: 600px; mso-displayed-decimal-separator: "\\,"; mso-displayed-thousand-separator: "\\."; }';
    htmlExcel += 'table.xl-dashboard td, table.xl-dashboard th { border: 1px solid #2C5D94; padding: 4px 8px; mso-number-format: "\\@"; }';
    htmlExcel += '.xl-header { background-color: #2C5D94 !important; color: white !important; font-weight: bold !important; }';
    htmlExcel += '.xl-stats { background-color: #f0f0f0 !important; font-weight: bold !important; }';
    htmlExcel += '.xl-fecha { background-color: #d1ecf1 !important; color: #0c5460 !important; font-weight: bold !important; }';
    htmlExcel += '.xl-col-header { background-color: #e0e0e0 !important; font-weight: bold !important; }';
    htmlExcel += '.xl-data { background-color: #ffffff !important; }';
    htmlExcel += '</style>';
    htmlExcel += '<table class="xl-dashboard" border="1" cellpadding="3" cellspacing="0" style="border-collapse: collapse; table-layout: fixed; width: 600px;">';
    htmlExcel += '<colgroup><col><col><col><col><col><col><col></colgroup>';
    
    var configHeader = 'Reporte por Rango: ' + formatearFechaRango(datosDashboardRango.fecha_inicio) + ' - ' + formatearFechaRango(datosDashboardRango.fecha_fin);
    var plantaNombre = $('#select_planta_rango option:selected').text() || 'Todas';
    
    htmlExcel += '<tr class="xl-header" bgcolor="#2C5D94" style="background-color: #2C5D94; color: white; font-weight: bold;">';
    htmlExcel += '<td colspan="7" bgcolor="#2C5D94" style="text-align: center; font-size: 12px; padding: 8px; color: white;">' + configHeader + ' - ' + dias.length + ' día(s) | Planta: ' + plantaNombre + '</td>';
    htmlExcel += '</tr>';
    
    htmlExcel += '<tr class="xl-stats" bgcolor="#f0f0f0" style="background-color: #f0f0f0; font-weight: bold;">';
    htmlExcel += '<td colspan="7" bgcolor="#f0f0f0" style="padding: 5px;">Total Días: ' + dias.length + ' | Total Cupos: ' + totalCupos + ' | Cupos Ocupados: ' + totalOcupados + ' | Cupos Libres: ' + totalLibres + ' | % Ocupación: ' + pctTotal + '%</td>';
    htmlExcel += '</tr>';
    
    dias.forEach(function(dia) {
        var turnosFecha = dia.turnos_detalle || [];
        
        htmlExcel += '<tr class="xl-fecha" bgcolor="#d1ecf1" style="background-color: #d1ecf1; color: #0c5460; font-weight: bold;">';
        htmlExcel += '<td colspan="7" bgcolor="#d1ecf1" style="text-align: left; font-size: 13px; padding: 8px; color: #0c5460;">Fecha: ' + dia.Tud_Fec + ' - ' + dia.dia_semana + ' | Total Cupos Ocupados: ' + (dia.total_cupos_ocupados || 0) + '</td>';
        htmlExcel += '</tr>';
        
        htmlExcel += '<tr class="xl-col-header" bgcolor="#e0e0e0" style="background-color: #e0e0e0; font-weight: bold;">';
        htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Horario</td>';
        htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Cupos Program.</td>';
        htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Manif. Creados</td>';
        htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Inactivos</td>';
        htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Cupos Libres</td>';
        htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">% Ocupación</td>';
        htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Barra Ocupación</td>';
        htmlExcel += '</tr>';
        
        turnosFecha.forEach(function(t) {
            var pct = t.porcentaje_ocupacion || 0;
            htmlExcel += '<tr>';
            htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + (t.horario || '') + '</td>';
            htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (t.cupos || 0) + '</td>';
            htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (t.ocupados || 0) + '</td>';
            htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (t.manifiestos_inactivos || 0) + '</td>';
            htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (t.libres || 0) + '</td>';
            htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + pct + '%</td>';
            htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + pct + '%</td>';
            htmlExcel += '</tr>';
        });
        
        htmlExcel += '<tr><td colspan="7" style="height: 5px; border: none;"></td></tr>';
    });
    
    htmlExcel += '</table>';
    
    var nombreArchivo = 'reporte_rango_' + (datosDashboardRango.fecha_inicio || '').replace(/-/g, '_') + '_' + (datosDashboardRango.fecha_fin || '').replace(/-/g, '_');
    var form = $('<form>', { method: 'POST', action: '../../Librerias/exportar/ficheroExcel.php', target: '_blank' });
    form.append($('<input>', { type: 'hidden', name: 'datos_a_enviar', value: htmlExcel }));
    form.append($('<input>', { type: 'hidden', name: 'nombre', value: nombreArchivo }));
    form.append($('<input>', { type: 'hidden', name: 'hoja', value: 'Reporte Rango' }));
    $('body').append(form);
    form.submit();
    form.remove();
}

// --- Dashboard Por Chofer / Placa ---
// Calcula KPIs estratégicos por vista.
function calcularKPIsEstrategicos(agrupado, tipoVista, plantasOptional) {
    var src = (agrupado && agrupado.length > 0) ? agrupado : (tipoVista === 'planta' && plantasOptional && plantasOptional.length > 0 ? plantasOptional : []);
    var getVal = function(g) {
        if (tipoVista === 'planta') return g.total_manifiestos != null ? g.total_manifiestos : (g.total || 0);
        return g.total_manifiestos || 0;
    };
    var valores = src.map(function(g) { return getVal(g); });
    var totalManifiestos = valores.reduce(function(s, v) { return s + v; }, 0);
    var n = valores.length;
    var promedio = n > 0 ? totalManifiestos / n : 0;
    var out = { totalManifiestos: totalManifiestos, totalActivos: n, promedio: promedio, valores: valores };
    if (tipoVista === 'planta') {
        var ordenado = valores.slice().sort(function(a, b) { return b - a; });
        var top2 = (ordenado[0] || 0) + (ordenado[1] || 0);
        var top3 = top2 + (ordenado[2] || 0);
        out.pctTop2 = totalManifiestos > 0 ? (top2 / totalManifiestos) * 100 : 0;
        out.pctTop3 = totalManifiestos > 0 ? (top3 / totalManifiestos) * 100 : 0;
        return out;
    }
    if (tipoVista === 'chofer') {
        var sumaCuadrados = valores.reduce(function(s, v) { return s + (v - promedio) * (v - promedio); }, 0);
        out.desvEst = n > 1 ? Math.sqrt(sumaCuadrados / n) : 0;
        var bajoPromedio = valores.filter(function(v) { return v < promedio; }).length;
        out.pctBajoPromedio = n > 0 ? (bajoPromedio / n) * 100 : 0;
        return out;
    }
    var ordenadoPlaca = valores.slice().sort(function(a, b) { return b - a; });
    var n20 = Math.max(1, Math.floor(n * 0.2));
    var sumaTop20 = ordenadoPlaca.slice(0, n20).reduce(function(s, v) { return s + v; }, 0);
    out.indiceConcentracion = totalManifiestos > 0 ? (sumaTop20 / totalManifiestos) * 100 : 0;
    var umbralBajo = promedio * 0.7;
    out.pctBajaActividad = n > 0 ? (valores.filter(function(v) { return v < umbralBajo; }).length / n) * 100 : 0;
    return out;
}

function generarHTMLKPIStrip(kpis, tipoVista) {
    if (!kpis || kpis.totalActivos === 0) return '';
    var html = '<div class="dashboard-kpi-strip">';
    if (tipoVista === 'planta') {
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">Total manifiestos</span><span class="dashboard-kpi-value">' + kpis.totalManifiestos + '</span></div>';
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">Plantas activas</span><span class="dashboard-kpi-value">' + kpis.totalActivos + '</span></div>';
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">Promedio por planta</span><span class="dashboard-kpi-value">' + (kpis.promedio || 0).toFixed(1) + '</span></div>';
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">% concentración Top 3</span><span class="dashboard-kpi-value">' + (kpis.pctTop3 || 0).toFixed(1) + '%</span></div>';
    } else if (tipoVista === 'chofer') {
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">Choferes activos</span><span class="dashboard-kpi-value">' + kpis.totalActivos + '</span></div>';
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">Promedio por chofer</span><span class="dashboard-kpi-value">' + (kpis.promedio || 0).toFixed(1) + '</span></div>';
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">Desv. estándar</span><span class="dashboard-kpi-value">' + (kpis.desvEst || 0).toFixed(1) + '</span></div>';
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">% bajo promedio</span><span class="dashboard-kpi-value">' + (kpis.pctBajoPromedio || 0).toFixed(1) + '%</span></div>';
    } else {
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">Placas activas</span><span class="dashboard-kpi-value">' + kpis.totalActivos + '</span></div>';
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">Promedio por placa</span><span class="dashboard-kpi-value">' + (kpis.promedio || 0).toFixed(1) + '</span></div>';
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">Índice concentración</span><span class="dashboard-kpi-value">' + (kpis.indiceConcentracion || 0).toFixed(1) + '%</span></div>';
        html += '<div class="dashboard-kpi-item"><span class="dashboard-kpi-label">% baja actividad</span><span class="dashboard-kpi-value">' + (kpis.pctBajaActividad || 0).toFixed(1) + '%</span></div>';
    }
    html += '</div>';
    return html;
}

function generarAlertaRiesgoPlanta(pctTop2) {
    if (pctTop2 == null || pctTop2 < 50) return '';
    var clase = pctTop2 >= 65 ? 'dashboard-alert-riesgo dashboard-alert-roja' : 'dashboard-alert-riesgo dashboard-alert-amarilla';
    var texto = pctTop2 >= 65 ? 'Alta concentración: las 2 primeras plantas superan el 65% de la operación. Considere diversificar.' : 'Concentración moderada: las 2 primeras plantas superan el 50% de la operación.';
    return '<div class="' + clase + '"><i class="fa fa-exclamation-triangle"></i> ' + texto + '</div>';
}

// Prepara datos para Pareto. Chofer/Placa: devuelve lista completa (itemsCompletos) para componente con switch. Planta: Top 25 + Otros.
function prepararDatosPareto(agrupado, tipoVista, plantasOptional) {
    var items = [];
    var getNombre = function(g) {
        if (tipoVista === 'chofer') return (g.chofer_nombre || '').trim() || 'Sin asignar';
        if (tipoVista === 'planta') return (g.Pla_Nom || '').trim() || '(Sin planta)';
        return (g.Veh_Pla || '').trim() || 'Sin placa';
    };
    var getCantidad = function(g) {
        if (tipoVista === 'planta') return g.total_manifiestos != null ? g.total_manifiestos : (g.total || 0);
        return g.total_manifiestos || 0;
    };
    var src = (agrupado && agrupado.length > 0) ? agrupado : (tipoVista === 'planta' && plantasOptional && plantasOptional.length > 0 ? plantasOptional : []);
    src.forEach(function(g) {
        items.push({ nombre: getNombre(g), cantidad: getCantidad(g) });
    });
    items.sort(function(a, b) { return b.cantidad - a.cantidad; });
    var total = items.reduce(function(s, i) { return s + i.cantidad; }, 0);
    var totalChoferes = items.length;
    var promedio = totalChoferes > 0 ? total / totalChoferes : 0;
    var acu = 0;
    var index80 = -1;
    items.forEach(function(i) {
        i.pct = total > 0 ? (i.cantidad / total) * 100 : 0;
        acu += i.pct;
        i.pctAcum = acu;
        if (index80 < 0 && acu >= 80) index80 = items.indexOf(i);
    });
    if (index80 < 0) index80 = items.length - 1;
    var numTop20Full = Math.max(1, Math.floor(items.length * 0.2));
    items.forEach(function(i, idx) {
        i.diffPromedio = i.cantidad - promedio;
        i.isTop20 = idx < numTop20Full;
    });

    if (tipoVista === 'chofer' || tipoVista === 'placa') {
        return {
            itemsCompletos: items,
            total: total,
            promedio: promedio,
            totalChoferes: totalChoferes,
            index80Original: index80 + 1,
            entityName: tipoVista
        };
    }

    var chartItems = [];
    var TOP_N = 25;
    if (items.length <= TOP_N) {
        chartItems = items.slice();
    } else {
        chartItems = items.slice(0, TOP_N);
        var otrosCant = items.slice(TOP_N).reduce(function(s, i) { return s + i.cantidad; }, 0);
        chartItems.push({ nombre: 'Otros', cantidad: otrosCant, esOtros: true });
    }
    var totalChart = chartItems.reduce(function(s, i) { return s + i.cantidad; }, 0);
    acu = 0;
    chartItems.forEach(function(i) {
        i.pct = totalChart > 0 ? (i.cantidad / totalChart) * 100 : 0;
        acu += i.pct;
        i.pctAcum = acu;
        i.diffPromedio = i.cantidad - promedio;
    });
    numTop20Full = Math.max(1, Math.floor(chartItems.length * 0.2));
    chartItems.forEach(function(i, idx) {
        i.isTop20 = idx < numTop20Full && !i.esOtros;
    });
    var index80Chart = -1;
    for (var j = 0; j < chartItems.length; j++) {
        if (chartItems[j].pctAcum >= 80) { index80Chart = j; break; }
    }
    if (index80Chart < 0) index80Chart = chartItems.length - 1;
    return {
        items: chartItems,
        total: total,
        promedio: promedio,
        totalChoferes: totalChoferes,
        index80Original: index80 + 1,
        index80Chart: index80Chart
    };
}

function generarHTMLParetoYTarjetas(tipoVista) {
    var etiquetaTitulo = tipoVista === 'chofer' ? 'chofer' : (tipoVista === 'planta' ? 'planta' : 'placa');
    var html = '<div class="pareto-kpi-wrap">';
    html += '<div class="pareto-kpi-cards" id="paretoKpiCardsChoferPlaca">';
    for (var i = 0; i < 5; i++) {
        html += '<div class="pareto-kpi-card" id="paretoKpiCard' + i + '">';
        html += '<div class="kpi-medal" id="kpiMedal' + i + '"></div>';
        html += '<div class="kpi-icon"><i class="fa ' + (tipoVista === 'planta' ? 'fa-industry' : 'fa-truck') + '"></i></div>';
        html += '<div class="kpi-name" id="kpiName' + i + '">—</div>';
        html += '<div class="kpi-cantidad" id="kpiCant' + i + '">0</div>';
        html += '<div class="kpi-pct" id="kpiPct' + i + '">0%</div>';
        html += '<div class="kpi-progress-wrap"><div class="kpi-progress-bar" id="kpiProgress' + i + '" style="width: 0%; background: #2C5D94;"></div></div>';
        html += '<div class="kpi-indicator" id="kpiIndicator' + i + '"></div>';
        html += '</div>';
    }
    html += '</div>';
    html += '<div class="pareto-chart-wrap"><h5><i class="fa fa-line-chart"></i> Gráfico Pareto - Manifiestos por ' + etiquetaTitulo + '</h5>';
    if (tipoVista === 'chofer' || tipoVista === 'placa') {
        html += '<div class="pareto-mode-switch" id="paretoModeSwitch">';
        html += '<button type="button" class="pareto-mode-btn active" data-mode="top20">Top 20</button>';
        html += '<button type="button" class="pareto-mode-btn" data-mode="hasta70">Hasta 70%</button>';
        html += '</div>';
    }
    html += '<div class="pareto-chart-scroll-wrap" id="paretoChartScrollWrap">';
    html += '<div class="pareto-chart-container" id="paretoChartContainer"><canvas id="canvasParetoChoferPlaca"></canvas></div>';
    html += '</div>';
    html += '<div class="pareto-insight" id="paretoInsightTexto"></div>';
    html += '</div></div>';
    return html;
}

/** Componente Pareto reutilizable para Chofer/Placa. config: { itemsCompletos, total, promedio, entityName }. mode: 'top20' | 'hasta70'. */
function initParetoChart(config, mode) {
    if (!config || !config.itemsCompletos || config.itemsCompletos.length === 0) return;
    var canvasId = 'canvasParetoChoferPlaca';
    var containerId = 'paretoChartContainer';
    var insightId = 'paretoInsightTexto';
    var colorPrincipal = config.colorPrincipal || '44, 93, 148';
    var itemsCompletos = config.itemsCompletos;
    var total = config.total;
    var promedio = config.promedio;
    var entityName = config.entityName || 'chofer';
    var chartItems = [];
    var index80Chart = -1;
    var insightText = '';

    if (mode === 'top20') {
        chartItems = itemsCompletos.slice(0, 20);
        var numTop20 = Math.max(1, Math.floor(chartItems.length * 0.2));
        chartItems.forEach(function(i, idx) { i.isTop20 = idx < numTop20; });
        var pctDelTotal = chartItems.length > 0 ? Number(chartItems[chartItems.length - 1].pctAcum.toFixed(1)) : 0;
        var primero = entityName === 'chofer' ? 'primeros' : 'primeras';
        var entidad = entityName === 'chofer' ? 'choferes' : 'placas';
        var articulo = entityName === 'chofer' ? 'Los' : 'Las';
        insightText = articulo + ' <strong>20</strong> ' + primero + ' ' + entidad + ' representan el <strong>' + pctDelTotal + '%</strong> del total.';
    } else {
        var totalGlobal = total;
        var acumulado = 0;
        var idx80 = -1;
        for (var k = 0; k < itemsCompletos.length; k++) {
            acumulado += itemsCompletos[k].cantidad;
            if (totalGlobal > 0 && acumulado / totalGlobal >= 0.7) {
                idx80 = k;
                break;
            }
        }
        if (idx80 < 0) idx80 = itemsCompletos.length - 1;
        chartItems = itemsCompletos.slice(0, idx80 + 1);
        index80Chart = chartItems.length - 1;
        chartItems.forEach(function(i, idx) { i.isTop20 = idx < Math.max(1, Math.floor(chartItems.length * 0.2)); });
        var primero = entityName === 'chofer' ? 'primeros' : 'primeras';
        var entidad = entityName === 'chofer' ? 'choferes' : 'placas';
        var articulo = entityName === 'chofer' ? 'Los' : 'Las';
        insightText = articulo + ' <strong>' + chartItems.length + '</strong> ' + primero + ' ' + entidad + ' representan el <strong>70%</strong> del total.';
    }

    var visualItems = chartItems;
    if (mode === 'hasta70' && chartItems.length > 40) {
        visualItems = chartItems.slice(0, 39);
        var otrosCant = chartItems.slice(39).reduce(function(s, i) { return s + i.cantidad; }, 0);
        var otrosRestantes = chartItems.length - 39;
        visualItems.push({
            nombre: 'Otros (' + otrosRestantes + ' restantes)',
            cantidad: otrosCant,
            isTop20: false,
            diffPromedio: otrosCant - promedio,
            esOtros: true
        });
        index80Chart = 39;
    }

    var insightEl = document.getElementById(insightId);
    if (insightEl) insightEl.innerHTML = insightText;

    var container = document.getElementById(containerId);
    if (container) {
        container.style.minWidth = '';
        container.classList.remove('pareto-chart-scroll');
    }

    if (chartParetoChoferPlaca) {
        chartParetoChoferPlaca.destroy();
        chartParetoChoferPlaca = null;
    }
    var canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;
    canvas.width = 0;
    canvas.height = 0;

    if (window.ChartAnnotation && typeof Chart.register === 'function') {
        try { Chart.register(window.ChartAnnotation); } catch (e) {}
    }

    var labels = visualItems.map(function(i) { return i.nombre; });
    var cantidades = visualItems.map(function(i) { return i.cantidad; });
    var pctAcum;
    if (mode === 'hasta70' && chartItems.length > 0) {
        var acu = 0;
        var pctAcumFull = [];
        for (var i = 0; i < chartItems.length; i++) {
            acu += (chartItems[i].cantidad / total) * 100;
            pctAcumFull.push(Number(acu.toFixed(1)));
        }
        if (pctAcumFull[pctAcumFull.length - 1] < 70) pctAcumFull[pctAcumFull.length - 1] = 70;
        if (visualItems.length === chartItems.length) {
            pctAcum = pctAcumFull.slice();
        } else {
            pctAcum = pctAcumFull.slice(0, 39);
            pctAcum.push(pctAcumFull[pctAcumFull.length - 1]);
        }
    } else {
        pctAcum = visualItems.map(function(i) { return Number(i.pctAcum.toFixed(1)); });
    }
    var coloresBarras = visualItems.map(function(i) {
        return i.isTop20 ? 'rgba(' + colorPrincipal + ', 0.9)' : 'rgba(108, 117, 125, 0.6)';
    });
    var promediosData = visualItems.map(function() { return Number(promedio.toFixed(1)); });

    var annotationPlugin = window.ChartAnnotation;
    var plugins = {
        legend: { position: 'top' },
        tooltip: {
            callbacks: {
                label: function(context) {
                    var idx = context.dataIndex;
                    var item = visualItems[idx];
                    if (context.dataset.yAxisID === 'y' && context.datasetIndex === 0) {
                        var pctInd = total > 0 ? ((item.cantidad / total) * 100) : 0;
                        var pctAcumVal = (pctAcum && pctAcum[idx] != null) ? pctAcum[idx] : (item.pctAcum != null ? Number(item.pctAcum.toFixed(1)) : 0);
                        var diffStr = (item.diffPromedio > 0 ? '+' : '') + (item.diffPromedio != null ? item.diffPromedio : (item.cantidad - promedio)).toFixed(0);
                        return [
                            'Cantidad: ' + item.cantidad,
                            '% individual: ' + pctInd.toFixed(1) + '%',
                            '% acumulado: ' + pctAcumVal.toFixed(1) + '%',
                            'Diferencia vs promedio: ' + diffStr
                        ];
                    }
                    return '';
                }
            }
        }
    };
    var annotations = {
        lineaPromedio: {
            type: 'line',
            yMin: promedio,
            yMax: promedio,
            borderColor: 'rgba(' + colorPrincipal + ', 0.9)',
            borderWidth: 3,
            borderDash: [4, 4],
            label: { display: true, content: 'Promedio', position: 'end' }
        }
    };
    if (index80Chart >= 0) {
        annotations.linea80 = {
            type: 'line',
            xMin: index80Chart - 0.5,
            xMax: index80Chart + 0.5,
            borderColor: 'rgb(200, 60, 60)',
            borderWidth: 2,
            borderDash: [6, 4],
            label: { display: true, content: 'Zona 70%', position: 'start' }
        };
    }
    if (annotationPlugin) plugins.annotation = { annotations: annotations };

    var ctx = canvas.getContext('2d');
    chartParetoChoferPlaca = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Cantidad',
                    data: cantidades,
                    backgroundColor: coloresBarras,
                    borderColor: visualItems.map(function(i) { return i.isTop20 ? 'rgb(' + colorPrincipal + ')' : 'rgb(108, 117, 125)'; }),
                    borderWidth: 1,
                    yAxisID: 'y',
                    order: 3
                },
                {
                    label: '% Acumulado',
                    data: pctAcum,
                    type: 'line',
                    borderColor: 'rgb(200, 60, 60)',
                    backgroundColor: 'rgba(200, 60, 60, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: 'rgb(200, 60, 60)',
                    yAxisID: 'y1',
                    order: 1
                },
                {
                    label: 'Promedio',
                    data: promediosData,
                    type: 'line',
                    borderColor: 'rgba(' + colorPrincipal + ', 0.9)',
                    borderWidth: 3,
                    borderDash: [4, 4],
                    fill: false,
                    pointRadius: 0,
                    yAxisID: 'y',
                    order: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: plugins,
            scales: {
                x: {
                    ticks: { autoSkip: true, maxTicksLimit: 20, maxRotation: 45, minRotation: 45, font: { size: 10 } }
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Cantidad' },
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: '% Acumulado' },
                    min: 0,
                    max: 100,
                    grid: { drawOnChartArea: false },
                    ticks: { callback: function(v) { return v + '%'; } }
                }
            }
        }
    });
    setTimeout(function() {
        if (chartParetoChoferPlaca) chartParetoChoferPlaca.resize();
    }, 0);
}

function initParetoYTarjetasChoferPlaca(agrupado, tipoVista, plantasOptional) {
    var datos = prepararDatosPareto(agrupado, tipoVista, plantasOptional);
    var total = datos.total;
    var promedio = datos.promedio;
    var totalChoferes = datos.totalChoferes;
    var medallas = ['\uD83E\uDD47', '\uD83E\uDD48', '\uD83E\uDD49', '', ''];

    if (datos.itemsCompletos) {
        var itemsCompletos = datos.itemsCompletos;
        var top5 = itemsCompletos.slice(0, 5);
        var maxCant = Math.max.apply(null, itemsCompletos.slice(0, 5).map(function(i) { return i.cantidad; })) || 1;
        for (var i = 0; i < 5; i++) {
            var card = document.getElementById('paretoKpiCard' + i);
            var nameEl = document.getElementById('kpiName' + i);
            var cantEl = document.getElementById('kpiCant' + i);
            var pctEl = document.getElementById('kpiPct' + i);
            var medalEl = document.getElementById('kpiMedal' + i);
            var progressEl = document.getElementById('kpiProgress' + i);
            var indEl = document.getElementById('kpiIndicator' + i);
            if (card) card.classList.add('animate-in');
            if (medalEl) medalEl.textContent = medallas[i] || '';
            if (nameEl) nameEl.textContent = top5[i] ? top5[i].nombre : '—';
            if (cantEl) cantEl.textContent = top5[i] ? top5[i].cantidad : '0';
            if (pctEl) pctEl.textContent = top5[i] ? (top5[i].pct.toFixed(1)) + '% del total' : '0%';
            if (progressEl && top5[i]) {
                var pctW = maxCant > 0 ? (top5[i].cantidad / maxCant) * 100 : 0;
                progressEl.style.width = pctW.toFixed(0) + '%';
                progressEl.style.background = top5[i].isTop20 ? '#2C5D94' : '#6c757d';
            }
            if (indEl && top5[i]) {
                var diff = top5[i].diffPromedio;
                if (diff > 0) {
                    indEl.textContent = '\u2191 Sobre promedio (+' + diff.toFixed(0) + ')';
                    indEl.className = 'kpi-indicator sobre-promedio';
                } else if (diff < 0) {
                    indEl.textContent = '\u2193 Bajo promedio (' + diff.toFixed(0) + ')';
                    indEl.className = 'kpi-indicator bajo-promedio';
                } else {
                    indEl.textContent = 'En promedio';
                    indEl.className = 'kpi-indicator';
                }
            }
        }
        var config = { itemsCompletos: itemsCompletos, total: total, promedio: promedio, entityName: tipoVista, colorPrincipal: '44, 93, 148' };
        window.paretoDatasetChoferPlaca = config;
        initParetoChart(config, 'top20');
        var switchEl = document.getElementById('paretoModeSwitch');
        if (switchEl) {
            switchEl.querySelectorAll('.pareto-mode-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var mode = this.getAttribute('data-mode');
                    switchEl.querySelectorAll('.pareto-mode-btn').forEach(function(b) { b.classList.remove('active'); });
                    this.classList.add('active');
                    initParetoChart(window.paretoDatasetChoferPlaca, mode);
                });
            });
        }
        return;
    }

    var items = datos.items;
    var index80Original = datos.index80Original;
    var index80Chart = datos.index80Chart;
    var top5 = items.slice(0, 5);
    var maxCant = Math.max.apply(null, items.map(function(i) { return i.cantidad; })) || 1;

    for (var i = 0; i < 5; i++) {
        var card = document.getElementById('paretoKpiCard' + i);
        var nameEl = document.getElementById('kpiName' + i);
        var cantEl = document.getElementById('kpiCant' + i);
        var pctEl = document.getElementById('kpiPct' + i);
        var medalEl = document.getElementById('kpiMedal' + i);
        var progressEl = document.getElementById('kpiProgress' + i);
        var indEl = document.getElementById('kpiIndicator' + i);
        if (card) card.classList.add('animate-in');
        if (medalEl) medalEl.textContent = medallas[i] || '';
        if (nameEl) nameEl.textContent = top5[i] ? top5[i].nombre : '—';
        if (cantEl) cantEl.textContent = top5[i] ? top5[i].cantidad : '0';
        if (pctEl) pctEl.textContent = top5[i] ? (top5[i].pct.toFixed(1)) + '% del total' : '0%';
        if (progressEl && top5[i]) {
            var pctW = maxCant > 0 ? (top5[i].cantidad / maxCant) * 100 : 0;
            progressEl.style.width = pctW.toFixed(0) + '%';
            progressEl.style.background = top5[i].isTop20 ? '#2C5D94' : '#6c757d';
        }
        if (indEl && top5[i]) {
            var diff = top5[i].diffPromedio;
            if (diff > 0) {
                indEl.textContent = '\u2191 Sobre promedio (+' + diff.toFixed(0) + ')';
                indEl.className = 'kpi-indicator sobre-promedio';
            } else if (diff < 0) {
                indEl.textContent = '\u2193 Bajo promedio (' + diff.toFixed(0) + ')';
                indEl.className = 'kpi-indicator bajo-promedio';
            } else {
                indEl.textContent = 'En promedio';
                indEl.className = 'kpi-indicator';
            }
        }
    }

    var insightEl = document.getElementById('paretoInsightTexto');
    if (insightEl && totalChoferes > 0) {
        insightEl.innerHTML = 'Las <strong>' + index80Original + '</strong> primeras plantas concentran el <strong>80%</strong> de la operación.';
    }

    if (chartParetoChoferPlaca) {
        chartParetoChoferPlaca.destroy();
        chartParetoChoferPlaca = null;
    }
    var canvas = document.getElementById('canvasParetoChoferPlaca');
    if (!canvas || typeof Chart === 'undefined' || items.length === 0) return;

    if (window.ChartAnnotation && typeof Chart.register === 'function') {
        try { Chart.register(window.ChartAnnotation); } catch (e) {}
    }

    var labels = items.map(function(i) { return i.nombre; });
    var cantidades = items.map(function(i) { return i.cantidad; });
    var pctAcum = items.map(function(i) { return Number(i.pctAcum.toFixed(1)); });
    var coloresBarras = items.map(function(i) {
        return i.isTop20 ? 'rgba(44, 93, 148, 0.9)' : 'rgba(108, 117, 125, 0.6)';
    });
    var promediosData = items.map(function() { return Number(promedio.toFixed(1)); });

    var annotationPlugin = window.ChartAnnotation;
    var plugins = {
        legend: { position: 'top' },
        tooltip: {
            callbacks: {
                label: function(context) {
                    var idx = context.dataIndex;
                    var item = items[idx];
                    if (context.dataset.yAxisID === 'y' && context.datasetIndex === 0) {
                        var diff = item.diffPromedio;
                        var diffStr = diff > 0 ? '+' + diff.toFixed(0) : diff.toFixed(0);
                        return [
                            'Cantidad: ' + item.cantidad,
                            '% individual: ' + item.pct.toFixed(1) + '%',
                            '% acumulado: ' + item.pctAcum.toFixed(1) + '%',
                            'Diferencia vs promedio: ' + diffStr
                        ];
                    }
                    return '';
                }
            }
        }
    };
    if (annotationPlugin) {
        plugins.annotation = {
            annotations: {
                linea80: {
                    type: 'line',
                    xMin: index80Chart - 0.5,
                    xMax: index80Chart + 0.5,
                    borderColor: 'rgb(200, 60, 60)',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    label: { display: true, content: 'Zona 80% productividad', position: 'start' }
                },
                lineaPromedio: {
                    type: 'line',
                    yMin: promedio,
                    yMax: promedio,
                    borderColor: 'rgba(44, 93, 148, 0.8)',
                    borderWidth: 3,
                    borderDash: [4, 4],
                    label: { display: true, content: 'Promedio', position: 'end' }
                }
            }
        };
    }

    var ctx = canvas.getContext('2d');
    chartParetoChoferPlaca = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Cantidad',
                    data: cantidades,
                    backgroundColor: coloresBarras,
                    borderColor: items.map(function(i) { return i.isTop20 ? 'rgb(44, 93, 148)' : 'rgb(108, 117, 125)'; }),
                    borderWidth: 1,
                    yAxisID: 'y',
                    order: 3
                },
                {
                    label: '% Acumulado',
                    data: pctAcum,
                    type: 'line',
                    borderColor: 'rgb(200, 60, 60)',
                    backgroundColor: 'rgba(200, 60, 60, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.2,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgb(200, 60, 60)',
                    yAxisID: 'y1',
                    order: 1
                },
                {
                    label: 'Promedio',
                    data: promediosData,
                    type: 'line',
                    borderColor: 'rgba(44, 93, 148, 0.8)',
                    borderWidth: 3,
                    borderDash: [4, 4],
                    fill: false,
                    pointRadius: 0,
                    yAxisID: 'y',
                    order: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: plugins,
            scales: {
                x: {
                    ticks: { maxRotation: 45, minRotation: 25, font: { size: 10 }, maxTicksLimit: 30 }
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Cantidad' },
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: '% Acumulado' },
                    min: 0,
                    max: 100,
                    grid: { drawOnChartArea: false },
                    ticks: { callback: function(v) { return v + '%'; } }
                }
            }
        }
    });
}

function generarGraficoChoferPlaca(tipoVista, agrupado, plantas) {
    var items = [];
    var titulo = '';
    if (tipoVista === 'planta') {
        titulo = 'Manifiestos por planta';
        var src = (agrupado && agrupado.length > 0) ? agrupado : (plantas || []);
        src.forEach(function(g) {
            items.push({ label: (g.Pla_Nom || '').trim() || '(Sin planta)', value: g.total_manifiestos != null ? g.total_manifiestos : (g.total || 0) });
        });
    } else if (tipoVista === 'chofer') {
        titulo = 'Manifiestos por chofer';
        (agrupado || []).forEach(function(g) {
            items.push({ label: (g.chofer_nombre || '').trim() || 'Sin asignar', value: g.total_manifiestos || 0 });
        });
    } else {
        titulo = 'Manifiestos por placa';
        (agrupado || []).forEach(function(g) {
            items.push({ label: (g.Veh_Pla || '').trim() || 'Sin placa', value: g.total_manifiestos || 0 });
        });
    }
    if (items.length === 0) return '';
    var total = items.reduce(function(s, i) { return s + i.value; }, 0);
    var promedio = items.length > 0 ? total / items.length : 0;
    var maxVal = Math.max.apply(null, items.map(function(i) { return i.value; }));
    if (maxVal <= 0) maxVal = 1;
    var maxBars = 18;
    var showItems = items.length > maxBars ? items.slice(0, maxBars) : items;
    var hayMas = items.length > maxBars;
    var html = '<div class="chart-cp-wrap">';
    html += '<h5 class="chart-cp-title"><i class="fa fa-bar-chart"></i> ' + titulo + '</h5>';
    if (hayMas) html += '<p class="chart-cp-subtitle">Mostrando los ' + maxBars + ' con más manifiestos (de ' + items.length + ')</p>';
    html += '<div class="chart-cp-horizontal">';
    showItems.forEach(function(item) {
        var pct = total > 0 ? ((item.value / total) * 100).toFixed(1) : '0';
        var widthPct = maxVal > 0 ? Math.min(100, (item.value / maxVal) * 100) : 0;
        var labelEsc = (item.label + '').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        var rowClass = 'chart-cp-row';
        if (tipoVista === 'chofer') {
            if (item.value > promedio * 1.05) rowClass += ' chart-cp-sobre-promedio';
            else if (item.value < promedio * 0.95) rowClass += ' chart-cp-bajo-promedio';
            else rowClass += ' chart-cp-promedio';
        } else if (tipoVista === 'placa' && item.value < promedio * 0.7) {
            rowClass += ' chart-cp-baja-actividad';
        }
        html += '<div class="' + rowClass + '" title="' + labelEsc + ': ' + item.value + ' (' + pct + '%)">';
        html += '<div class="chart-cp-label">' + item.label + '</div>';
        html += '<div class="chart-cp-bar-track"><div class="chart-cp-bar-fill" style="width: ' + widthPct + '%;"></div></div>';
        html += '<div class="chart-cp-val">' + item.value + '</div>';
        html += '<div class="chart-cp-pct">' + pct + '%</div>';
        html += '</div>';
    });
    html += '</div>';
    html += '<div class="chart-cp-leyenda"><span class="chart-cp-leyenda-item"><i class="chart-cp-dot"></i> Total manifiestos en el rango</span><strong>' + total + '</strong></div>';
    if (tipoVista === 'chofer') {
        html += '<p class="chart-cp-leyenda-colores"><span class="leyenda-color leyenda-verde"></span> Sobre promedio &nbsp; <span class="leyenda-color leyenda-gris"></span> En promedio &nbsp; <span class="leyenda-color leyenda-naranja"></span> Bajo promedio</p>';
    } else if (tipoVista === 'placa') {
        html += '<p class="chart-cp-leyenda-colores"><span class="leyenda-color leyenda-naranja"></span> Placas con baja actividad (&lt;70% del promedio)</p>';
    }
    html += '</div>';
    return html;
}

function cargarDashboardChoferPlaca() {
    var fi = $('#fecha_inicio_chofer_placa').val();
    var ff = $('#fecha_fin_chofer_placa').val();
    var tipoVista = $('#tipo_vista_chofer_placa').val() || 'chofer';
    if (!fi || !ff) {
        alert('Indique el rango de fechas (Desde y Hasta).');
        return;
    }
    fechaInicioChoferPlaca = fi;
    fechaFinChoferPlaca = ff;
    $('#dashboardContentChoferPlaca').html('<div class="loading"><i class="fa fa-spinner fa-spin"></i><p>Cargando...</p></div>');
    var params = { getPlantasRankingAjax: true, fecha_inicio: fi, fecha_fin: ff };
    if (contextoDashboardTurnos.plaCodAsignada > 0) params.Pla_Cod = contextoDashboardTurnos.plaCodAsignada;
    $.get('', params, function(resPlantas) {
        if (!resPlantas.success) {
            $('#dashboardContentChoferPlaca').html('<div class="alert alert-danger">' + (resPlantas.message || 'Error al cargar ranking de plantas') + '</div>');
            return;
        }
        datosRankingPlantasChoferPlaca = resPlantas.plantas || [];
        var paramsMan = { getDashboardManifiestosAjax: true, fecha_inicio: fi, fecha_fin: ff, tipo_vista: tipoVista };
        if (contextoDashboardTurnos.plaCodAsignada > 0) paramsMan.Pla_Cod = contextoDashboardTurnos.plaCodAsignada;
        $.ajax({ url: '', type: 'GET', data: paramsMan, dataType: 'json', cache: false,
            success: function(resMan) {
                if (!resMan.success) {
                    $('#dashboardContentChoferPlaca').html('<div class="alert alert-danger">' + (resMan.message || 'Error al cargar datos') + '</div>');
                    return;
                }
                datosDashboardChoferPlaca = { agrupado: resMan.agrupado || [], tipo_vista: resMan.tipo_vista || tipoVista };
                var tipoVistaRender = ($('#tipo_vista_chofer_placa').val() || '').toString().toLowerCase().trim();
                if (tipoVistaRender !== 'planta' && tipoVistaRender !== 'placa') tipoVistaRender = 'chofer';
                renderDashboardChoferPlaca(datosRankingPlantasChoferPlaca, datosDashboardChoferPlaca.agrupado, tipoVistaRender);
            },
            error: function() {
                $('#dashboardContentChoferPlaca').html('<div class="alert alert-danger">Error al cargar manifiestos por ' + tipoVista + '.</div>');
            }
        });
    }, 'json').fail(function() {
        $('#dashboardContentChoferPlaca').html('<div class="alert alert-danger">Error al cargar ranking de plantas.</div>');
    });
}

function mostrarAvisoPlantaPlanteroChoferPlaca() {
    $('#avisoPlantaPlanteroChoferPlaca').remove();
    if (!(contextoDashboardTurnos.plaCodAsignada > 0)) return;
    var nombrePlanta = (contextoDashboardTurnos.plaNomAsignada || '').trim();
    var textoPlanta = nombrePlanta !== '' ? nombrePlanta : ('Código ' + contextoDashboardTurnos.plaCodAsignada);
    var html = ''
        + '<div id="avisoPlantaPlanteroChoferPlaca" class="alert alert-info" style="margin-top:10px; margin-bottom:0;">'
        + '<i class="fa fa-building-o"></i> Vista restringida a la planta del usuario logueado: <strong>' + textoPlanta + '</strong>.'
        + '</div>';
    $('#tab_chofer_placa .filtros-container').append(html);
}

function renderDashboardChoferPlaca(plantas, agrupado, tipoVista) {
    var tv = (tipoVista || '').toLowerCase().trim();
    if (tv !== 'planta' && tv !== 'placa') tv = 'chofer';
    tipoVista = tv;
    var html = '';
    var tituloRango = formatearFechaRango(fechaInicioChoferPlaca) + ' - ' + formatearFechaRango(fechaFinChoferPlaca);
    // Una sola sección según la vista elegida en el combobox
    if (tipoVista === 'planta') {
        // Por planta: primero gráficos (Pareto + Top 5 + ranking), después el cuadro (tabla)
        html += '<div class="config-card">';
        html += '<div class="config-header"><h4>Plantas que más manifiestos generan</h4><p>Rango: ' + tituloRango + '</p></div>';
        html += '</div>';
        var datosParaKpiPlanta = (agrupado && agrupado.length > 0) ? agrupado : plantas;
        var kpisPlanta = calcularKPIsEstrategicos(datosParaKpiPlanta, 'planta', plantas);
        html += generarHTMLKPIStrip(kpisPlanta, 'planta');
        html += generarAlertaRiesgoPlanta(kpisPlanta.pctTop2);
        html += generarHTMLParetoYTarjetas('planta');
        html += generarGraficoChoferPlaca('planta', agrupado, plantas);
        html += '<div class="config-card" style="margin-top: 24px;">';
        html += '<div class="config-header"><h4>Listado por planta</h4><p>Rango: ' + tituloRango + '</p></div>';
        if (agrupado && agrupado.length > 0) {
            html += '<table class="turnos-table"><thead><tr><th>#</th><th>Planta</th><th>Total manifiestos</th><th>Tiempo Prom.</th><th></th></tr></thead><tbody>';
            agrupado.forEach(function(g, idx) {
                var tProm = g.tiempo_promedio || 0;
                var tFmt = tProm >= 60 ? (Math.floor(tProm / 60) + 'h ' + Math.round(tProm % 60) + 'm') : (Math.round(tProm) + ' min');
                html += '<tr><td>' + (idx + 1) + '</td><td>' + (g.Pla_Nom || '') + '</td><td>' + (g.total_manifiestos || 0) + '</td><td>' + tFmt + '</td>';
                html += '<td><button type="button" class="btn btn-xs btn-default btn-ver-manifiestos" onclick="verManifiestosChoferPlaca(' + idx + ');"><span class="glyphicon glyphicon-list"></span> Ver manifiestos</button></td></tr>';
            });
            html += '</tbody></table>';
        } else if (plantas && plantas.length > 0) {
            html += '<table class="turnos-table"><thead><tr><th>#</th><th>Planta</th><th>Total manifiestos</th><th>Tiempo Prom.</th></tr></thead><tbody>';
            plantas.forEach(function(p, idx) {
                var tProm = p.tiempo_promedio || 0;
                var tFmt = tProm > 0 ? (tProm >= 60 ? (Math.floor(tProm / 60) + 'h ' + Math.round(tProm % 60) + 'm') : (Math.round(tProm) + ' min')) : '-';
                html += '<tr><td>' + (idx + 1) + '</td><td>' + (p.Pla_Nom || '') + '</td><td>' + (p.total || 0) + '</td><td>' + tFmt + '</td></tr>';
            });
            html += '</tbody></table>';
        } else {
            html += '<p class="alert alert-warning" style="margin: 10px 0 0 0;">No hay datos de plantas en el rango seleccionado.</p>';
        }
        html += '</div>';
    } else {
        // Manifiestos por chofer o por placa: primero gráficos, después el cuadro (tabla)
        html += '<div class="config-card">';
        html += '<div class="config-header"><h4>Manifiestos por ' + (tipoVista === 'chofer' ? 'chofer' : 'placa') + '</h4><p>Rango: ' + tituloRango + '</p></div>';
        html += '</div>';
        var kpisChoferPlaca = calcularKPIsEstrategicos(agrupado, tipoVista, null);
        html += generarHTMLKPIStrip(kpisChoferPlaca, tipoVista);
        html += generarHTMLParetoYTarjetas(tipoVista);
        html += generarGraficoChoferPlaca(tipoVista, agrupado, null);
        html += '<div class="config-card" style="margin-top: 24px;">';
        html += '<div class="config-header"><h4>Listado por ' + (tipoVista === 'chofer' ? 'chofer' : 'placa') + '</h4><p>Rango: ' + tituloRango + '</p></div>';
        if (agrupado && agrupado.length > 0) {
            html += '<table class="turnos-table" id="tablaAgrupadoChoferPlaca"><thead><tr>';
            html += '<th>#</th>';
            if (tipoVista === 'chofer') {
                html += '<th>Chofer</th><th>Cédula</th><th>Total manifiestos</th><th>Plantas</th><th>Tiempo Prom.</th><th></th></tr></thead><tbody>';
            } else {
                html += '<th>Placa</th><th>Total manifiestos</th><th>Plantas</th><th>Tiempo Prom.</th><th></th></tr></thead><tbody>';
            }
            agrupado.forEach(function(g, idx) {
                var tProm = g.tiempo_promedio || 0;
                var tFmt = tProm >= 60 ? (Math.floor(tProm / 60) + 'h ' + Math.round(tProm % 60) + 'm') : (Math.round(tProm) + ' min');
                html += '<tr><td>' + (idx + 1) + '</td>';
                if (tipoVista === 'chofer') {
                    html += '<td>' + (g.chofer_nombre || 'Sin asignar') + '</td><td>' + (g.chofer_cedula || '') + '</td><td>' + (g.total_manifiestos || 0) + '</td><td>' + (g.total_plantas != null ? g.total_plantas : '0') + '</td><td>' + tFmt + '</td>';
                } else {
                    html += '<td>' + (g.Veh_Pla || 'Sin placa') + '</td><td>' + (g.total_manifiestos || 0) + '</td><td>' + (g.total_plantas != null ? g.total_plantas : '0') + '</td><td>' + tFmt + '</td>';
                }
                html += '<td><button type="button" class="btn btn-xs btn-default btn-ver-manifiestos" onclick="verManifiestosChoferPlaca(' + idx + ');"><span class="glyphicon glyphicon-list"></span> Ver manifiestos</button></td></tr>';
            });
            html += '</tbody></table>';
        } else {
            html += '<p class="alert alert-warning" style="margin: 10px 0 0 0;">No hay manifiestos en el rango seleccionado.</p>';
        }
        html += '</div>';
    }
    $('#dashboardContentChoferPlaca').html(html);
    var tieneDatos = (tipoVista === 'planta' && (agrupado && agrupado.length > 0 || plantas && plantas.length > 0)) || ((tipoVista === 'chofer' || tipoVista === 'placa') && agrupado && agrupado.length > 0);
    if (tieneDatos) {
        var datosParaPareto = tipoVista === 'planta' ? (agrupado && agrupado.length > 0 ? agrupado : plantas) : agrupado;
        var plantasParam = tipoVista === 'planta' ? plantas : null;
        setTimeout(function() { initParetoYTarjetasChoferPlaca(datosParaPareto, tipoVista, plantasParam); }, 0);
    }
}

function imprimirReporteChoferPlaca() {
    if (!datosRankingPlantasChoferPlaca && !datosDashboardChoferPlaca) {
        alert('No hay datos para imprimir. Ejecute una búsqueda en el tab Por Chofer/Placa primero.');
        return;
    }
    var tituloRango = formatearFechaRango(fechaInicioChoferPlaca) + ' - ' + formatearFechaRango(fechaFinChoferPlaca);
    var tipoVista = datosDashboardChoferPlaca ? datosDashboardChoferPlaca.tipo_vista : 'chofer';
    var html = '<div class="config-card">';
    html += '<div class="config-header"><h4>Reporte Por Chofer / Placa / Planta</h4><p>Rango: ' + tituloRango + ' | Vista: ' + (tipoVista === 'chofer' ? 'Por chofer' : tipoVista === 'placa' ? 'Por placa' : 'Por planta') + '</p></div>';
    if (tipoVista === 'planta') {
        // Solo sección Plantas que más manifiestos generan
        if (datosDashboardChoferPlaca && datosDashboardChoferPlaca.agrupado && datosDashboardChoferPlaca.agrupado.length > 0) {
            html += '<table class="turnos-table"><thead><tr><th>#</th><th>Planta</th><th>Total</th><th>Tiempo Prom.</th></tr></thead><tbody>';
            datosDashboardChoferPlaca.agrupado.forEach(function(g, i) {
                var tProm = g.tiempo_promedio || 0;
                var tFmt = tProm >= 60 ? (Math.floor(tProm / 60) + 'h ' + Math.round(tProm % 60) + 'm') : (Math.round(tProm) + ' min');
                html += '<tr><td>' + (i + 1) + '</td><td>' + (g.Pla_Nom || '') + '</td><td>' + (g.total_manifiestos || 0) + '</td><td>' + tFmt + '</td></tr>';
            });
            html += '</tbody></table>';
        } else if (datosRankingPlantasChoferPlaca && datosRankingPlantasChoferPlaca.length > 0) {
            html += '<table class="turnos-table"><thead><tr><th>#</th><th>Planta</th><th>Total</th></tr></thead><tbody>';
            datosRankingPlantasChoferPlaca.forEach(function(p, i) {
                html += '<tr><td>' + (i + 1) + '</td><td>' + (p.Pla_Nom || '') + '</td><td>' + (p.total || 0) + '</td></tr>';
            });
            html += '</tbody></table>';
        }
    } else {
        // Solo sección Manifiestos por chofer o por placa
        if (datosDashboardChoferPlaca && datosDashboardChoferPlaca.agrupado && datosDashboardChoferPlaca.agrupado.length > 0) {
            html += '<table class="turnos-table"><thead><tr>';
            if (tipoVista === 'chofer') html += '<th>Chofer</th><th>Cédula</th><th>Total</th><th>Plantas</th><th>Tiempo Prom.</th></tr></thead><tbody>';
            else html += '<th>Placa</th><th>Total</th><th>Plantas</th><th>Tiempo Prom.</th></tr></thead><tbody>';
            datosDashboardChoferPlaca.agrupado.forEach(function(g) {
                var tProm = g.tiempo_promedio || 0;
                var tFmt = tProm >= 60 ? (Math.floor(tProm / 60) + 'h ' + Math.round(tProm % 60) + 'm') : (Math.round(tProm) + ' min');
                if (tipoVista === 'chofer') html += '<tr><td>' + (g.chofer_nombre || '') + '</td><td>' + (g.chofer_cedula || '') + '</td><td>' + (g.total_manifiestos || 0) + '</td><td>' + (g.total_plantas != null ? g.total_plantas : 0) + '</td><td>' + tFmt + '</td></tr>';
                else html += '<tr><td>' + (g.Veh_Pla || '') + '</td><td>' + (g.total_manifiestos || 0) + '</td><td>' + (g.total_plantas != null ? g.total_plantas : 0) + '</td><td>' + tFmt + '</td></tr>';
            });
            html += '</tbody></table>';
        }
    }
    html += '</div>';
    var estilos = '<style>body{font-family:Arial;margin:10px}.config-card{border:1px solid #2C5D94;padding:10px}.config-header{background:#2C5D94;color:white;padding:8px;margin:-10px -10px 10px -10px}.turnos-table{width:100%;border-collapse:collapse}.turnos-table th,.turnos-table td{border:1px solid #ddd;padding:6px}.turnos-table th{background:#2C5D94;color:white}</style>';
    abrirVentanaImpresion(estilos + html, 'Reporte Chofer/Placa');
}

function exportarExcelChoferPlaca() {
    if (!datosRankingPlantasChoferPlaca && !datosDashboardChoferPlaca) {
        alert('No hay datos para exportar. Ejecute una búsqueda en el tab Por Chofer/Placa primero.');
        return;
    }
    var tituloRango = formatearFechaRango(fechaInicioChoferPlaca) + ' - ' + formatearFechaRango(fechaFinChoferPlaca);
    var tipoVista = datosDashboardChoferPlaca ? datosDashboardChoferPlaca.tipo_vista : 'chofer';
    var htmlExcel = '<style>table.xl-cp{ border-collapse:collapse } table.xl-cp td, table.xl-cp th{ border:1px solid #2C5D94; padding:4px 8px } .xl-cp-header{ background:#2C5D94; color:white; font-weight:bold }</style>';
    htmlExcel += '<table class="xl-cp"><tr class="xl-cp-header"><td colspan="6">Reporte Por Chofer/Placa/Planta - ' + tituloRango + ' - Vista: ' + (tipoVista === 'chofer' ? 'Por chofer' : tipoVista === 'placa' ? 'Por placa' : 'Por planta') + '</td></tr>';
    if (tipoVista === 'planta') {
        // Solo sección Plantas que más manifiestos generan
        if (datosDashboardChoferPlaca && datosDashboardChoferPlaca.agrupado && datosDashboardChoferPlaca.agrupado.length > 0) {
            htmlExcel += '<tr class="xl-cp-header"><td>#</td><td>Planta</td><td>Total manifiestos</td><td>Tiempo Prom.</td></tr>';
            datosDashboardChoferPlaca.agrupado.forEach(function(g, i) {
                var tProm = g.tiempo_promedio || 0;
                var tFmt = tProm >= 60 ? (Math.floor(tProm / 60) + 'h ' + Math.round(tProm % 60) + 'm') : (Math.round(tProm) + ' min');
                htmlExcel += '<tr><td>' + (i + 1) + '</td><td>' + (g.Pla_Nom || '') + '</td><td>' + (g.total_manifiestos || 0) + '</td><td>' + tFmt + '</td></tr>';
            });
        } else if (datosRankingPlantasChoferPlaca && datosRankingPlantasChoferPlaca.length > 0) {
            htmlExcel += '<tr class="xl-cp-header"><td>#</td><td>Planta</td><td>Total manifiestos</td></tr>';
            datosRankingPlantasChoferPlaca.forEach(function(p, i) {
                htmlExcel += '<tr><td>' + (i + 1) + '</td><td>' + (p.Pla_Nom || '') + '</td><td>' + (p.total || 0) + '</td></tr>';
            });
        }
    } else {
        // Solo sección Manifiestos por chofer o por placa — igual que el cuadro en pantalla (sin listados de manifiestos ni fechas/plantas)
        if (datosDashboardChoferPlaca && datosDashboardChoferPlaca.agrupado && datosDashboardChoferPlaca.agrupado.length > 0) {
            htmlExcel += '<tr class="xl-cp-header"><td colspan="' + (tipoVista === 'chofer' ? 6 : 5) + '">Manifiestos por ' + (tipoVista === 'chofer' ? 'chofer' : 'placa') + '</td></tr>';
            if (tipoVista === 'chofer') htmlExcel += '<tr class="xl-cp-header"><td>#</td><td>Chofer</td><td>Cédula</td><td>Total manifiestos</td><td>Plantas</td><td>Tiempo Prom.</td></tr>';
            else htmlExcel += '<tr class="xl-cp-header"><td>#</td><td>Placa</td><td>Total manifiestos</td><td>Plantas</td><td>Tiempo Prom.</td></tr>';
            datosDashboardChoferPlaca.agrupado.forEach(function(g, i) {
                var totalPlantas = g.total_plantas != null ? g.total_plantas : 0;
                var tProm = g.tiempo_promedio || 0;
                var tFmt = tProm >= 60 ? (Math.floor(tProm / 60) + 'h ' + Math.round(tProm % 60) + 'm') : (Math.round(tProm) + ' min');
                if (tipoVista === 'chofer') {
                    htmlExcel += '<tr><td>' + (i + 1) + '</td><td>' + (g.chofer_nombre || '') + '</td><td>' + (g.chofer_cedula || '') + '</td><td>' + (g.total_manifiestos || 0) + '</td><td>' + totalPlantas + '</td><td>' + tFmt + '</td></tr>';
                } else {
                    htmlExcel += '<tr><td>' + (i + 1) + '</td><td>' + (g.Veh_Pla || '') + '</td><td>' + (g.total_manifiestos || 0) + '</td><td>' + totalPlantas + '</td><td>' + tFmt + '</td></tr>';
                }
            });
        }
    }
    htmlExcel += '</table>';
    var nombreArchivo = 'reporte_chofer_placa_' + (fechaInicioChoferPlaca || '').replace(/-/g, '_') + '_' + (fechaFinChoferPlaca || '').replace(/-/g, '_');
    var form = $('<form>', { method: 'POST', action: '../../Librerias/exportar/ficheroExcel.php', target: '_blank' });
    form.append($('<input>', { type: 'hidden', name: 'datos_a_enviar', value: htmlExcel }));
    form.append($('<input>', { type: 'hidden', name: 'nombre', value: nombreArchivo }));
    form.append($('<input>', { type: 'hidden', name: 'hoja', value: 'Chofer Placa' }));
    $('body').append(form);
    form.submit();
    form.remove();
}

function abrirVentanaImpresion(html, titulo) {
    var ventanaImpresion = window.open('', '_blank', 'width=800,height=1000');
    if (!ventanaImpresion) {
        alert('Por favor, permita ventanas emergentes para imprimir.');
        return;
    }
    var estilosImpresion = `
        <style>
            @page { margin: 1.5cm; size: A4 portrait; }
            * { margin-top: 0; box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body { margin: 0; padding: 15px; font-family: Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; overflow: visible; width: 100%; }
            .config-card { margin-bottom: 10px; padding: 5px; border: 1px solid #2C5D94; border-radius: 4px; }
            .config-header { padding: 5px 8px; margin-bottom: 5px; background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%) !important; color: white !important; border-radius: 4px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .config-header h4 { font-size: 12px; margin: 0; }
            .config-header p { font-size: 10px; margin: 3px 0 0 0; }
            .config-stats { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 5px; page-break-after: avoid; }
            .stat-card { padding: 5px 8px; min-width: 80px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; text-align: center; }
            .stat-card .stat-label { font-size: 9px; margin-bottom: 2px; color: #6c757d; font-weight: 600; }
            .stat-card .stat-value { font-size: 14px; font-weight: bold; color: #2C5D94; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .stat-card.total-turnos .stat-value { color: #17a2b8; }
            .stat-card.total-cupos .stat-value { color: #28a745; }
            .stat-card.cupos-ocupados .stat-value { color: #ffc107; }
            .stat-card.cupos-libres .stat-value { color: #17a2b8; }
            .stat-card.ocupacion .stat-value { color: #dc3545; }
            .turnos-table th:nth-child(8), .turnos-table td:nth-child(8) { display: none !important; }
            .badge-ocupacion { padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: 600; }
            .badge-ocupacion.bajo { background-color: #f8d7da; color: #721c24; }
            .badge-ocupacion.medio { background-color: #fff3cd; color: #856404; }
            .badge-ocupacion.alto { background-color: #d4edda; color: #155724; }
            .fecha-header { margin-top: 10px !important; margin-bottom: 5px !important; padding: 5px 8px !important; background-color: #e9ecef; border-left: 4px solid #2C5D94; border-radius: 4px; }
            .fecha-header h5 { font-size: 12px !important; margin: 0; color: #2C5D94; font-weight: 600; }
            .fecha-header button, .config-header button, .fecha-header .btn, .config-header .btn, .fecha-header .toggle-icon { display: none !important; }
            .fecha-header { page-break-after: avoid; }
            .fecha-content { page-break-inside: avoid; }
            .fecha-content + .fecha-header { page-break-before: avoid; }
            .turnos-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 5px; }
            .turnos-table thead th { background: #2C5D94 !important; color: white !important; padding: 4px 5px; text-align: left; font-weight: 600; font-size: 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .turnos-table tbody td { padding: 4px 5px; border-bottom: 1px solid #dee2e6; font-size: 10px; }
            .barra-progreso-tabla { width: 100%; min-width: 80px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .barra-progreso-fondo { position: relative; width: 100%; height: 18px !important; min-height: 18px !important; background: #e9ecef !important; border-radius: 4px; overflow: hidden; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .barra-progreso-relleno { position: absolute !important; left: 0 !important; top: 0 !important; bottom: 0 !important; height: 100% !important; min-height: 18px !important; min-width: 3px !important; display: block !important; border-radius: 4px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .barra-progreso-relleno.bajo { background-color: #dc3545 !important; }
            .barra-progreso-relleno.medio { background-color: #ffc107 !important; }
            .barra-progreso-relleno.alto { background-color: #28a745 !important; }
            .chart-dashboard-container { width: 100% !important; max-width: 100% !important; padding: 20px 15px; background: #fafbfc; border-radius: 6px; border: 1px solid #e2e8ec; margin: 0 auto; box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; page-break-inside: avoid; }
            .chart-leyenda { display: flex; justify-content: center; gap: 20px; margin-bottom: 15px; font-size: 12px; color: #6c757d; }
            .chart-leyenda-item { display: flex; align-items: center; gap: 6px; }
            .chart-leyenda-color { display: inline-block; width: 14px; height: 14px; border-radius: 3px; }
            .chart-with-axes { display: flex !important; align-items: flex-start !important; width: 100% !important; max-width: 100% !important; margin-top: 10px; page-break-inside: avoid !important; }
            .chart-y-axis { flex-shrink: 0; display: flex !important; flex-direction: column; align-items: flex-end; padding-right: 10px; border-right: 1px solid #dee2e6; position: relative; }
            .chart-y-label { font-size: 11px; font-weight: 600; color: #2C5D94; position: absolute; top: -26px; right: 0; margin: 0; }
            .chart-y-ticks { display: flex; flex-direction: column; justify-content: space-between; }
            .chart-y-tick { font-size: 10px; color: #6c757d; line-height: 1; display: flex; align-items: flex-end; justify-content: flex-end; }
            .chart-main-area { flex: 1; position: relative; min-width: 0; display: flex; flex-direction: column; align-items: stretch; }
            .chart-grid { position: absolute; left: 0; right: 0; top: 0; pointer-events: none; }
            .chart-grid-line { position: absolute; left: 0; right: 0; height: 1px; background: rgba(44, 93, 148, 0.2); }
            .chart-x-axis-line { position: absolute; left: 0; right: 0; bottom: 0; height: 1px; background: #adb5bd; opacity: 0.8; }
            .chart-bars-area { display: flex; flex-direction: column; align-items: stretch; width: 100% !important; max-width: 100% !important; padding: 0 0 0 12px; margin: 0; page-break-inside: avoid !important; }
            .chart-bars-row { display: flex !important; justify-content: center !important; align-items: flex-end; flex-wrap: nowrap; position: relative; z-index: 1; padding: 0; margin: 0; width: 100%; box-sizing: border-box; }
            .chart-labels-row { display: flex; justify-content: center; flex-wrap: nowrap; width: 100%; padding: 0 0 0 12px; margin-top: 6px; box-sizing: border-box; }
            .chart-label-cell { display: flex; flex-direction: column; justify-content: flex-start; align-items: center; flex: 0 0 auto !important; flex-shrink: 0 !important; min-width: 25px !important; }
            .chart-bar-total { font-size: 10px; font-weight: 600; color: #2C5D94; margin-bottom: 4px; text-align: center; flex-shrink: 0; }
            .chart-label-cell .chart-bar-porcentaje { margin-top: 1px; margin-bottom: 0; font-size: 8px; color: #6c757d; }
            .chart-bars-n-pocas.chart-bars-row, .chart-bars-n-pocas.chart-labels-row { gap: 25px; }
            .chart-bars-n-pocas .chart-bar-wrapper, .chart-bars-n-pocas .chart-label-cell { flex: 0 0 auto !important; flex-shrink: 0 !important; width: 32px !important; min-width: 20px !important; max-width: 50px !important; }
            .chart-bars-n-pocas .chart-bar-columna { width: 100%; max-width: 50px; }
            .chart-bars-n-medias.chart-bars-row, .chart-bars-n-medias.chart-labels-row { gap: 20px; }
            .chart-bars-n-medias .chart-bar-wrapper, .chart-bars-n-medias .chart-label-cell { flex: 0 0 auto !important; flex-shrink: 0 !important; width: 28px !important; min-width: 20px !important; max-width: 40px !important; }
            .chart-bars-n-medias .chart-bar-columna { width: 100%; max-width: 40px; }
            .chart-bars-n-muchas.chart-bars-row, .chart-bars-n-muchas.chart-labels-row { gap: 25px; }
            .chart-bars-n-muchas .chart-bar-wrapper, .chart-bars-n-muchas .chart-label-cell { flex: 0 0 auto !important; flex-shrink: 0 !important; width: 25px !important; min-width: 20px !important; }
            .chart-bars-n-muchas .chart-bar-columna { width: 100%; max-width: 25px; }
            .chart-bars-row { flex-shrink: 0 !important; }
            .chart-bar-wrapper { display: flex; flex-direction: column; align-items: center; flex: 0 0 auto !important; flex-shrink: 0 !important; min-width: 25px !important; height: 100%; padding: 0; margin: 0; }
            .chart-bar-spacer { flex: 1 1 0; min-height: 0; }
            .chart-bar-porcentaje { font-size: 9px; font-weight: 600; color: #2C5D94; margin: 0; text-align: center; }
            .chart-bar-columna { width: 100%; flex-shrink: 0; display: flex; flex-direction: column-reverse; border-radius: 4px 4px 0 0; overflow: hidden; background: #e9ecef; }
            .chart-segmento { display: flex; align-items: center; justify-content: center; min-height: 0; flex-shrink: 0; width: 100%; text-align: center; }
            .chart-segmento-ocupados { background: #2C5D94 !important; color: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .chart-segmento-libres { background: #fd7e14 !important; color: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .chart-segmento-valor { font-size: 10px; font-weight: 700; line-height: 1; width: 100%; text-align: center; display: block; }
            .chart-segmento-pequeno { min-height: 18px !important; }
            .chart-x-label { font-size: 10px; white-space: nowrap; text-align: center; width: 100%; }
            .chart-por-dias { width: 100%; max-width: 100%; }
            .chart-por-dias .chart-label-cell { justify-content: flex-start; }
            .chart-por-dias .chart-x-label { text-align: left; }
            .fecha-content { display: block !important; }
            .chart-fecha-content { display: block !important; }
            .fecha-header .toggle-icon { display: none !important; }
            .fecha-print-block { page-break-inside: avoid !important; break-inside: avoid !important; }
            .fecha-print-block:nth-child(3n+2) { page-break-after: always !important; }
            .fecha-print-block:last-child { page-break-after: auto !important; }
            @media print {
                body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                .chart-segmento-ocupados, .chart-segmento-libres, .config-header { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                .fecha-print-block { page-break-inside: avoid !important; break-inside: avoid !important; }
            }
        </style>
    `;
    ventanaImpresion.document.write('<html><head><title>' + (titulo || 'Reporte') + '</title>' + estilosImpresion + '</head><body>');
    ventanaImpresion.document.write(html);
    ventanaImpresion.document.write('</body></html>');
    ventanaImpresion.document.close();
    ventanaImpresion.onload = function() {
        setTimeout(function() {
            ventanaImpresion.print();
            ventanaImpresion.onafterprint = function() { ventanaImpresion.close(); };
        }, 500);
    };
}

function imprimirReporteFechaRango(fecha) {
    var dias = datosDashboardRango ? getDiasFiltradosRango(datosDashboardRango.dias) : [];
    if (!datosDashboardRango || dias.length === 0) {
        alert('No hay datos para imprimir. Por favor, ejecute una búsqueda en Por Rango primero.');
        return;
    }
    var diaFiltrado = dias.filter(function(d) { return (d.Tud_Fec_SQL === fecha || d.Tud_Fec === fecha); })[0];
    if (!diaFiltrado || !diaFiltrado.turnos_detalle || diaFiltrado.turnos_detalle.length === 0) {
        alert('No se encontraron datos para la fecha seleccionada.');
        return;
    }
    var turnosDetalle = [];
    (diaFiltrado.turnos_detalle || []).forEach(function(t) {
        var tCopy = {};
        for (var k in t) tCopy[k] = t[k];
        tCopy.Tud_Fec = tCopy.Tud_Fec || diaFiltrado.Tud_Fec_SQL || diaFiltrado.Tud_Fec;
        turnosDetalle.push(tCopy);
    });
    var html = '<div class="config-card">';
    html += '<div class="config-header">';
    html += '<h4>Reporte por Fecha: ' + (diaFiltrado.Tud_Fec || fecha) + (diaFiltrado.dia_semana ? ' - ' + diaFiltrado.dia_semana : '') + '</h4>';
    html += '</div>';
    html += generarVistaBarras(turnosDetalle, true, false, false);
    html += '</div>';
    abrirVentanaImpresion(html, 'Reporte Turnos - ' + fecha);
}

function imprimirReporteFecha(fecha) {
    if (!datosDashboardActual || datosDashboardActual.length === 0) {
        alert('No hay datos para imprimir. Por favor, cargue el dashboard primero.');
        return;
    }
    
    // Filtrar datos por fecha
    var configuracionesFiltradas = [];
    datosDashboardActual.forEach(function(config) {
        var configFiltrada = JSON.parse(JSON.stringify(config)); // Clonar
        configFiltrada.turnos_detalle = config.turnos_detalle.filter(function(turno) {
            return turno.Tud_Fec === fecha;
        });
        
        if (configFiltrada.turnos_detalle.length > 0) {
            configuracionesFiltradas.push(configFiltrada);
        }
    });
    
    if (configuracionesFiltradas.length === 0) {
        alert('No se encontraron datos para la fecha seleccionada.');
        return;
    }
    
    // Generar HTML solo para la fecha seleccionada
    var html = '';
    configuracionesFiltradas.forEach(function(config) {
        html += '<div class="config-card">';
        html += '<div class="config-header">';
        html += '<h4>Configuración de Turnos #' + config.Tur_Cod + '</h4>';
        html += '<p style="margin: 5px 0 0 0; font-size: 14px;">Período: ' + config.Tur_Fei + ' - ' + config.Tur_Fef + '</p>';
        html += '</div>';
        
        if (modoVistaBarras) {
            html += generarVistaBarras(config.turnos_detalle, true);
        } else {
            html += generarVistaTabla(config.turnos_detalle, true);
        }
        
        html += '</div>';
    });
    
    abrirVentanaImpresion(html, 'Reporte de Turnos - ' + fecha);
}

function exportarExcelFechaRango(fecha) {
    var dias = datosDashboardRango ? getDiasFiltradosRango(datosDashboardRango.dias) : [];
    if (!datosDashboardRango || dias.length === 0) {
        alert('No hay datos para exportar. Por favor, ejecute una búsqueda en Por Rango primero.');
        return;
    }
    var diaFiltrado = dias.filter(function(d) { return (d.Tud_Fec_SQL === fecha || d.Tud_Fec === fecha); })[0];
    if (!diaFiltrado || !diaFiltrado.turnos_detalle || diaFiltrado.turnos_detalle.length === 0) {
        alert('No se encontraron datos para la fecha seleccionada.');
        return;
    }
    var turnosFecha = diaFiltrado.turnos_detalle;
    var htmlExcel = '<style type="text/css">';
    htmlExcel += 'table.xl-dashboard-fecha { border-collapse: collapse; table-layout: fixed; width: 600px; }';
    htmlExcel += 'table.xl-dashboard-fecha td, table.xl-dashboard-fecha th { border: 1px solid #2C5D94; padding: 4px 8px; }';
    htmlExcel += '.xl-data { background-color: #ffffff !important; }';
    htmlExcel += '</style>';
    htmlExcel += '<table class="xl-dashboard-fecha" border="1" cellpadding="3" cellspacing="0" style="border-collapse: collapse; table-layout: fixed; width: 600px;">';
    htmlExcel += '<colgroup><col><col><col><col><col><col><col></colgroup>';
    htmlExcel += '<tr bgcolor="#2C5D94" style="background-color: #2C5D94; color: white; font-weight: bold;">';
    htmlExcel += '<td colspan="7" bgcolor="#2C5D94" style="text-align: center; font-size: 12px; padding: 8px; color: white;">Reporte por Fecha: ' + (diaFiltrado.Tud_Fec || fecha) + (diaFiltrado.dia_semana ? ' - ' + diaFiltrado.dia_semana : '') + '</td>';
    htmlExcel += '</tr>';
    htmlExcel += '<tr bgcolor="#e0e0e0" style="background-color: #e0e0e0; font-weight: bold;">';
    htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Horario</td>';
    htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Cupos Program.</td>';
    htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Manif. Creados</td>';
    htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Inactivos</td>';
    htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Cupos Libres</td>';
    htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">% Ocupación</td>';
    htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Barra Ocupación</td>';
    htmlExcel += '</tr>';
    turnosFecha.forEach(function(t) {
        var pct = t.porcentaje_ocupacion || 0;
        htmlExcel += '<tr>';
        htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + (t.horario || '') + '</td>';
        htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (t.cupos || 0) + '</td>';
        htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (t.ocupados || 0) + '</td>';
        htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (t.manifiestos_inactivos || 0) + '</td>';
        htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (t.libres || 0) + '</td>';
        htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + pct + '%</td>';
        htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + pct + '%</td>';
        htmlExcel += '</tr>';
    });
    htmlExcel += '</table>';
    var nombreArchivo = 'Reporte_Turnos_' + (fecha || '').replace(/[\/\-]/g, '_') + '_' + new Date().getTime() + '.xls';
    var form = $('<form>', { method: 'POST', action: '../../Librerias/exportar/ficheroExcel.php', target: '_blank' });
    form.append($('<input>', { type: 'hidden', name: 'datos_a_enviar', value: htmlExcel }));
    form.append($('<input>', { type: 'hidden', name: 'nombre', value: nombreArchivo }));
    form.append($('<input>', { type: 'hidden', name: 'hoja', value: 'Reporte Fecha' }));
    $('body').append(form);
    form.submit();
    form.remove();
}

function exportarExcelFecha(fecha) {
    if (!datosDashboardActual || datosDashboardActual.length === 0) {
        alert('No hay datos para exportar. Por favor, cargue el dashboard primero.');
        return;
    }
    
    // Filtrar datos por fecha
    var configuracionesFiltradas = [];
    datosDashboardActual.forEach(function(config) {
        var configFiltrada = JSON.parse(JSON.stringify(config)); // Clonar
        configFiltrada.turnos_detalle = config.turnos_detalle.filter(function(turno) {
            return turno.Tud_Fec === fecha;
        });
        
        if (configFiltrada.turnos_detalle.length > 0) {
            configuracionesFiltradas.push(configFiltrada);
        }
    });
    
    if (configuracionesFiltradas.length === 0) {
        alert('No se encontraron datos para la fecha seleccionada.');
        return;
    }
    
    // Crear tabla HTML para Excel (table-layout:fixed y colgroup limitan columnas para evitar que Excel extienda colores)
    var htmlExcel = '<style type="text/css">';
    htmlExcel += 'table.xl-dashboard-fecha { border-collapse: collapse; table-layout: fixed; width: 600px; }';
    htmlExcel += 'table.xl-dashboard-fecha td, table.xl-dashboard-fecha th { border: 1px solid #2C5D94; padding: 4px 8px; }';
    htmlExcel += '.xl-data { background-color: #ffffff !important; }';
    htmlExcel += '</style>';
    htmlExcel += '<table class="xl-dashboard-fecha" border="1" cellpadding="3" cellspacing="0" style="border-collapse: collapse; table-layout: fixed; width: 600px;">';
    htmlExcel += '<colgroup><col><col><col><col><col><col><col></colgroup>';
    
    configuracionesFiltradas.forEach(function(config) {
        var configHeader = 'Configuración de Turnos #' + config.Tur_Cod;
        var configPeriodo = 'Período: ' + config.Tur_Fei + ' - ' + config.Tur_Fef;
        
        htmlExcel += '<tr bgcolor="#2C5D94" style="background-color: #2C5D94; color: white; font-weight: bold;">';
        htmlExcel += '<td colspan="7" bgcolor="#2C5D94" style="text-align: center; font-size: 12px; padding: 8px; color: white;">' + configHeader + ' - ' + configPeriodo + '</td>';
        htmlExcel += '</tr>';
        
        // Agrupar turnos por fecha (solo debería haber una fecha)
        var turnosPorFecha = {};
        config.turnos_detalle.forEach(function(turno) {
            var fechaTurno = turno.Tud_Fec || '-';
            if (!turnosPorFecha[fechaTurno]) {
                turnosPorFecha[fechaTurno] = [];
            }
            turnosPorFecha[fechaTurno].push(turno);
        });
        
        Object.keys(turnosPorFecha).forEach(function(fechaTurno) {
            var turnosFecha = turnosPorFecha[fechaTurno];
            
            htmlExcel += '<tr bgcolor="#d1ecf1" style="background-color: #d1ecf1; color: #0c5460; font-weight: bold;">';
            htmlExcel += '<td colspan="7" bgcolor="#d1ecf1" style="text-align: left; font-size: 13px; padding: 8px; color: #0c5460;">Fecha: ' + fechaTurno + '</td>';
            htmlExcel += '</tr>';
            
            htmlExcel += '<tr bgcolor="#e0e0e0" style="background-color: #e0e0e0; font-weight: bold;">';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Horario</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Cupos Program.</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Manif. Creados</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Inactivos</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Cupos Libres</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">% Ocupación</td>';
            htmlExcel += '<td bgcolor="#e0e0e0" style="padding: 5px; text-align: center;">Barra Ocupación</td>';
            htmlExcel += '</tr>';
            
            turnosFecha.forEach(function(turno) {
                var porcentaje = turno.porcentaje_ocupacion || 0;
                htmlExcel += '<tr>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + (turno.horario || '') + '</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (turno.cupos || 0) + '</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (turno.ocupados || 0) + '</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (turno.manifiestos_inactivos || 0) + '</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'0\'; background-color: #ffffff;">' + (turno.libres || 0) + '</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + porcentaje + '%</td>';
                htmlExcel += '<td class="xl-data" bgcolor="#ffffff" style="padding: 4px; text-align: center; mso-number-format:\'@\'; background-color: #ffffff;">' + porcentaje + '%</td>';
                htmlExcel += '</tr>';
            });
        });
    });
    
    htmlExcel += '</table>';
    
    var nombreArchivo = 'Reporte_Turnos_' + fecha.replace(/\//g, '_') + '_' + new Date().getTime() + '.xls';
    
    var form = $('<form>', {
        method: 'POST',
        action: '../../Librerias/exportar/ficheroExcel.php',
        target: '_blank'
    });
    
    form.append($('<input>', {
        type: 'hidden',
        name: 'datos_a_enviar',
        value: htmlExcel
    }));
    
    form.append($('<input>', {
        type: 'hidden',
        name: 'nombre',
        value: nombreArchivo
    }));
    
    form.append($('<input>', {
        type: 'hidden',
        name: 'hoja',
        value: 'Reporte Turnos ' + fecha
    }));
    
    $('body').append(form);
    form.submit();
    form.remove();
}

function imprimirModalManifiestos() {
    var $modal = $('#modalManifiestos');
    if ($modal.length === 0) {
        alert('No hay modal abierto para imprimir.');
        return;
    }
    
    var titulo = $modal.find('.modal-title').text();
    var $body = $modal.find('.modal-body');
    var contenido = '';
    var tabLabel = '';
    var esDetallado = false;
    
    var $tabs = $body.find('.nav-tabs');
    var $tabContent = $body.find('.tab-content');
    
    if ($tabContent.length > 0) {
        var $activePane = $tabContent.find('.tab-pane.active');
        tabLabel = $tabs.find('li.active a').text().trim() || 'Reporte';
        esDetallado = ($activePane.attr('id') === 'tabManifiestosDetalle');
        contenido = '<div class="print-tab-label" style="font-size: 12px; color: #6c757d; margin-bottom: 10px;">Vista: ' + tabLabel + '</div>';
        contenido += $activePane.length ? $activePane.html() : $tabContent.html();
    } else {
        contenido = $body.html();
    }
    
    if (!contenido || contenido.trim() === '') {
        alert('No hay contenido para imprimir.');
        return;
    }
    
    var ventanaImpresion = window.open('', '_blank', 'width=1000,height=700');
    
    var pageStyle = esDetallado ? '@page { margin: 1cm; size: landscape; }' : '@page { margin: 1cm; }';
    
    var bootstrapLink = '';
    if (esDetallado) {
        var baseUrl = (window.location.origin || '') + (window.location.pathname || '').replace(/\/[^/]*$/, '');
        bootstrapLink = '<base href="' + baseUrl + '/">' +
            '<link rel="stylesheet" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap.min.css">';
    }
    
    var estilosImpresion = `
        <style>
            ${pageStyle}
            * { margin-top: 0; box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body { margin: 0; padding: 10px; font-family: Arial, sans-serif; overflow: visible; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .modal-title-print {
                font-size: 16px;
                font-weight: bold;
                color: #2C5D94;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 2px solid #2C5D94;
            }
            .print-tab-label { font-size: 12px; color: #6c757d; margin-bottom: 10px; }
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11px;
                table-layout: auto;
                overflow: visible;
            }
            table thead th,
            table tbody td {
                display: table-cell !important;
                visibility: visible !important;
            }
            table thead th {
                background: #2C5D94 !important;
                color: white !important;
                padding: 6px 8px;
                text-align: left;
                font-weight: 600;
                font-size: 11px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table tbody td {
                padding: 5px 8px;
                border-bottom: 1px solid #dee2e6;
                font-size: 11px;
            }
            .tabla-modal-manifiestos thead th,
            .tabla-modal-manifiestos tbody td {
                display: table-cell !important;
            }
            .tabla-modal-planta thead th,
            .tabla-modal-planta tbody td {
                display: table-cell !important;
            }
            .tabla-modal-manifiestos .glyphicon {
                font-size: 14px;
            }
            .badge {
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: 600;
            }
            .badge-success { background-color: #28a745; color: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-danger { background-color: #dc3545; color: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .alert {
                padding: 10px;
                margin-bottom: 15px;
                border: 1px solid #bee5eb;
                border-radius: 4px;
                background-color: #d1ecf1;
                color: #0c5460;
            }
        </style>
    `;
    
    ventanaImpresion.document.write('<html><head><title>' + titulo + '</title>' + bootstrapLink + estilosImpresion + '</head><body>');
    ventanaImpresion.document.write('<div class="modal-title-print">' + titulo + '</div>');
    ventanaImpresion.document.write(contenido);
    ventanaImpresion.document.write('</body></html>');
    ventanaImpresion.document.close();
    
    ventanaImpresion.onload = function() {
        setTimeout(function() {
            ventanaImpresion.print();
            ventanaImpresion.onafterprint = function() {
                ventanaImpresion.close();
            };
        }, 250);
    };
}

function exportModalManifiestosExcel(tipo) {
    if (!datosModalManifiestos || !datosModalManifiestos.manifiestos) {
        alert('No hay datos para exportar.');
        return;
    }
    var manifiestos = datosModalManifiestos.manifiestos;
    var turnoInfo = datosModalManifiestos.turnoInfo || {};
    var titulo = (turnoInfo.fecha || 'Manifiestos') + (turnoInfo.horario ? ' - ' + turnoInfo.horario : '');
    
    var htmlExcel = '<style type="text/css">';
    htmlExcel += 'table.xl-modal { border-collapse: collapse; table-layout: fixed; width: 100%; }';
    htmlExcel += 'table.xl-modal td, table.xl-modal th { border: 1px solid #2C5D94; padding: 4px 8px; }';
    htmlExcel += '.xl-modal-header { background-color: #2C5D94 !important; color: white !important; font-weight: bold !important; }';
    htmlExcel += '.xl-modal-total { background-color: #2C5D94 !important; color: white !important; font-weight: bold !important; }';
    htmlExcel += '</style>';
    htmlExcel += '<table class="xl-modal" border="1" cellpadding="3" cellspacing="0">';
    
    if (tipo === 'detallado') {
        var mostrarColumnaHorario = (manifiestos.length > 0 && manifiestos[0].horario_turno);
        var mostrarColumnaFechaDia = (manifiestos.length > 0 && manifiestos[0].fecha_dia);
        var ordenActual = ($('#ordenManifiestosDetalle').length && $('#ordenManifiestosDetalle').val()) || 'fecha';
        var manifiestosParaExport = ordenarManifiestosParaTabla(manifiestos, ordenActual);
        
        var numCols = 10 + (mostrarColumnaHorario ? 1 : 0) + (mostrarColumnaFechaDia ? 1 : 0);
        htmlExcel += '<tr class="xl-modal-header"><td colspan="' + numCols + '" style="text-align: center; padding: 8px; color: white;">' + titulo + ' - Vista Detallada</td></tr>';
        htmlExcel += '<tr class="xl-modal-header">';
        htmlExcel += '<td>#</td><td>No. Manifiesto</td><td>Fecha</td>';
        if (mostrarColumnaFechaDia) htmlExcel += '<td>Día</td>';
        if (mostrarColumnaHorario) htmlExcel += '<td>Horario</td>';
        htmlExcel += '<td>Cliente</td><td>Planta</td><td>GE</td><td>A</td><td>GS</td><td>F</td><td>R</td>';
        htmlExcel += '</tr>';
        
        manifiestosParaExport.forEach(function(man, index) {
            htmlExcel += '<tr>';
            htmlExcel += '<td>' + (index + 1) + '</td>';
            htmlExcel += '<td>' + (man.ManNum || '') + '</td>';
            htmlExcel += '<td>' + (man.Man_Fec || '') + '</td>';
            if (mostrarColumnaFechaDia) htmlExcel += '<td>' + (man.fecha_dia || '') + '</td>';
            if (mostrarColumnaHorario) htmlExcel += '<td>' + (man.horario_turno || '') + '</td>';
            htmlExcel += '<td>' + (man.Cliente || '') + '</td>';
            htmlExcel += '<td>' + (man.Pla_Nom || '') + '</td>';
            htmlExcel += '<td>' + (man.Man_Tip_1 === 'GE' ? 'Sí' : '') + '</td>';
            htmlExcel += '<td>' + (man.Man_Tip_2 === 'A' ? 'Sí' : '') + '</td>';
            htmlExcel += '<td>' + (man.Man_Tip_3 === 'GS' ? 'Sí' : '') + '</td>';
            htmlExcel += '<td>' + (man.Man_Tip_4 === 'F' ? 'Sí' : '') + '</td>';
            htmlExcel += '<td>' + (man.Man_Tip_5 === 'R' ? 'Sí' : '') + '</td>';
            htmlExcel += '</tr>';
        });
    } else {
        var porPlantaCliente = {};
        manifiestos.forEach(function(man) {
            var planta = man.Pla_Nom || '(Sin planta)';
            var cliente = man.Cliente || '(Sin cliente)';
            var key = planta + '\u0001' + cliente;
            if (!porPlantaCliente[key]) {
                porPlantaCliente[key] = { planta: planta, cliente: cliente, cantidad: 0 };
            }
            porPlantaCliente[key].cantidad++;
        });
        var filas = Object.keys(porPlantaCliente).map(function(k) { return porPlantaCliente[k]; });
        filas.sort(function(a, b) { return (b.cantidad || 0) - (a.cantidad || 0); });
        
        var totalManifiestos = filas.reduce(function(s, f) { return s + (f.cantidad || 0); }, 0);
        
        htmlExcel += '<tr class="xl-modal-header"><td colspan="5" style="text-align: center; padding: 8px; color: white;">' + titulo + ' - Vista Por Planta</td></tr>';
        htmlExcel += '<tr class="xl-modal-header"><td>#</td><td>Planta</td><td>Cliente</td><td style="text-align: center;">Cantidad Manifiestos</td><td style="text-align: center;"><span style="display: block;">%</span><span style="display: block;">Participación</span></td></tr>';
        
        filas.forEach(function(item, index) {
            var pct = totalManifiestos > 0 ? (((item.cantidad || 0) / totalManifiestos) * 100).toFixed(2) : '0.00';
            htmlExcel += '<tr>';
            htmlExcel += '<td>' + (index + 1) + '</td>';
            htmlExcel += '<td>' + (item.planta || '') + '</td>';
            htmlExcel += '<td>' + (item.cliente || '') + '</td>';
            htmlExcel += '<td style="text-align: center;">' + (item.cantidad || 0) + '</td>';
            htmlExcel += '<td style="text-align: center;">' + pct + '%</td>';
            htmlExcel += '</tr>';
        });
        htmlExcel += '<tr class="xl-modal-total"><td colspan="3" style="text-align: right; padding-right: 15px;">TOTAL:</td><td style="text-align: center;">' + totalManifiestos + '</td><td style="text-align: center;">100%</td></tr>';
    }
    
    htmlExcel += '</table>';
    
    var nombreArchivo = 'manifiestos_' + (tipo === 'detallado' ? 'detallado' : 'por_planta') + '_' + new Date().getTime();
    var form = $('<form>', { method: 'POST', action: '../../Librerias/exportar/ficheroExcel.php', target: '_blank' });
    form.append($('<input>', { type: 'hidden', name: 'datos_a_enviar', value: htmlExcel }));
    form.append($('<input>', { type: 'hidden', name: 'nombre', value: nombreArchivo }));
    form.append($('<input>', { type: 'hidden', name: 'hoja', value: tipo === 'detallado' ? 'Manifiestos Detallado' : 'Manifiestos Por Planta' }));
    $('body').append(form);
    form.submit();
    form.remove();
}

// ==================== Vista Ejecutiva Global (CEO) ====================
var chartEjecutivoConcentracion = null;
var chartEjecutivoTendencia = null;
var chartEjecutivoInactivos = null;
var chartEjecutivoParetoInactivos = null;

function cargarDashboardEjecutivo() {
    var fi = $('#fecha_inicio_ejecutivo').val();
    var ff = $('#fecha_fin_ejecutivo').val();
    if (!fi || !ff) {
        alert('Indique el rango de fechas.');
        return;
    }
    $('#dashboardContentEjecutivo').html('<div class="loading"><i class="fa fa-spinner fa-spin"></i><p>Generando tablero ejecutivo...</p></div>');
    $.ajax({
        url: '',
        data: { getDashboardEjecutivoAjax: true, fecha_inicio: fi, fecha_fin: ff },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                renderDashboardEjecutivo(data);
            } else {
                $('#dashboardContentEjecutivo').html('<div class="alert alert-danger">' + (data.message || 'Error al cargar datos.') + '</div>');
            }
        },
        error: function() {
            $('#dashboardContentEjecutivo').html('<div class="alert alert-danger">Error de conexión.</div>');
        }
    });
}

function obtenerContenidoEjecutivoConGraficosParaImpresion() {
    var $cont = $('#dashboardContentEjecutivo');
    if (!$cont.length) return '';
    var canvases = $cont.find('canvas').toArray();
    var dataUrls = [];
    canvases.forEach(function(canvas) {
        try {
            if (canvas.width > 0 && canvas.height > 0) {
                dataUrls.push(canvas.toDataURL('image/png'));
            } else {
                dataUrls.push(null);
            }
        } catch (e) {
            dataUrls.push(null);
        }
    });
    var $clone = $cont.clone();
    $clone.find('.no-print, button, .btn').remove();
    $clone.find('canvas').each(function(i) {
        if (dataUrls[i]) {
            var $img = $('<img alt="Gráfico"/>').attr('src', dataUrls[i]).css({ maxWidth: '100%', height: 'auto', display: 'block' });
            $(this).replaceWith($img);
        }
    });
    $clone.find('.ceo-chart-wrap').css({ minHeight: '0', height: 'auto' });
    return $clone.html();
}

function imprimirReporteEjecutivo() {
    if (!datosDashboardEjecutivo) {
        alert('No hay datos para imprimir. Genere el tablero ejecutivo primero (seleccione fechas y pulse Generar tablero).');
        return;
    }
    var d = datosDashboardEjecutivo;
    function fmtF(str) {
        if (!str) return '';
        var m = (str + '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        return m ? m[3] + '/' + m[2] + '/' + m[1] : str;
    }
    var fechaIni = fmtF(d.fecha_ini), fechaFin = fmtF(d.fecha_fin);
    var rangoTexto = (fechaIni && fechaFin) ? (fechaIni + ' a ' + fechaFin) : 'Período seleccionado';

    var contenidoConGraficos = obtenerContenidoEjecutivoConGraficosParaImpresion();
    if (!contenidoConGraficos) {
        alert('No se pudo obtener el contenido del tablero. Asegúrese de haber generado el tablero y que los gráficos estén visibles.');
        return;
    }

    var estilos = [
        '@page { margin: 1.2cm; size: A4 portrait; }',
        'body { margin: 0; padding: 0 8px 12px; font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif; color: #1a1a1a; font-size: 12px; line-height: 1.35; -webkit-print-color-adjust: exact; print-color-adjust: exact; }',
        '.ceo-print-wrap { max-width: 100%; }',
        '.ceo-print-header { text-align: center; padding: 14px 0 10px; border-bottom: 3px solid #2C5D94; margin-bottom: 12px; }',
        '.ceo-print-title { font-size: 22px; font-weight: 700; color: #2C5D94; letter-spacing: 0.02em; margin: 0 0 4px 0; }',
        '.ceo-print-subtitle { font-size: 13px; color: #5a6c7d; font-weight: 600; margin: 0 0 4px 0; }',
        '.ceo-print-period { font-size: 12px; color: #6c757d; margin: 0; }',
        '.ceo-print-footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #e2e8ec; font-size: 10px; color: #9ca3af; text-align: center; }',
        '.ceo-print-body { margin-top: 0; }',
        '.ceo-kpi-row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; page-break-inside: avoid; }',
        '.ceo-kpi-card { flex: 1 1 140px; min-width: 140px; background: #fff; border: 1px solid #e0e4e8; border-radius: 6px; padding: 10px 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.06); }',
        '.ceo-kpi-card .ceo-kpi-icon { font-size: 18px; margin-bottom: 4px; }',
        '.ceo-kpi-card .ceo-kpi-label { font-size: 11px; color: #5c6370; text-transform: uppercase; letter-spacing: 0.02em; margin-bottom: 4px; }',
        '.ceo-kpi-card .ceo-kpi-value { font-size: 20px; font-weight: 700; color: #2d3748; }',
        '.ceo-kpi-card .ceo-kpi-tendencia { font-size: 11px; margin-top: 4px; }',
        '.ceo-kpi-card.semaforo-verde { border-left: 4px solid #22c55e; }',
        '.ceo-kpi-card.semaforo-amarillo { border-left: 4px solid #eab308; }',
        '.ceo-kpi-card.semaforo-rojo { border-left: 4px solid #dc2626; }',
        '.ceo-bloque { background: #fff; border: 1px solid #e0e4e8; border-radius: 6px; padding: 12px 14px; margin-bottom: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.06); }',
        '.ceo-bloque h4 { margin: 0 0 8px 0; font-size: 14px; color: #2C5D94; font-weight: 600; page-break-after: avoid; }',
        '.ceo-insight { font-size: 12px; color: #374151; margin-top: 8px; padding: 6px 10px; background: #f8fafc; border-radius: 4px; border-left: 3px solid #94a3b8; }',
        '.ceo-insight.alerta-roja { border-left-color: #dc2626; background: #fef2f2; }',
        '.ceo-alertas-list { list-style: none; padding: 0; margin: 0; }',
        '.ceo-alertas-list li { padding: 6px 10px; margin-bottom: 4px; border-radius: 4px; font-size: 12px; }',
        '.ceo-alertas-list li.nivel-alto { background: #fef2f2; border-left: 4px solid #dc2626; }',
        '.ceo-alertas-list li.nivel-atencion { background: #fffbeb; border-left: 4px solid #eab308; }',
        '.ceo-alertas-list li.nivel-estable { background: #f0fdf4; border-left: 4px solid #22c55e; }',
        '.ceo-chart-wrap { position: relative; min-height: 0; height: auto; margin-top: 8px; }',
        '.ceo-chart-wrap img { max-width: 100%; height: auto; display: block; vertical-align: top; }',
        '.ceo-bloque .table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 6px; }',
        '.ceo-bloque .table th, .ceo-bloque .table td { border: 1px solid #dee2e6; padding: 5px 8px; text-align: left; }',
        '.ceo-bloque .table th { background: #2C5D94; color: white; font-weight: 600; }',
        '.ceo-bloque .table tr:nth-child(even) { background: #f8f9fa; }',
        '.ceo-planta-inactivos-link { cursor: default; pointer-events: none; text-decoration: none; color: inherit; }',
        '.ceo-bloque .row { margin-bottom: 0; }',
        '@media print {',
        '  body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }',
        '  .ceo-kpi-row { page-break-inside: avoid; }',
        '  .ceo-bloque { page-break-inside: auto; }',
        '  .ceo-bloque h4 { page-break-after: avoid; }',
        '  .ceo-chart-wrap { min-height: 0 !important; height: auto !important; page-break-inside: auto; }',
        '  .ceo-chart-wrap img { page-break-inside: avoid; }',
        '  .ceo-bloque .table { page-break-inside: auto; }',
        '  .ceo-bloque .table tr { page-break-inside: avoid; }',
        '}'
    ].join('\n');

    var html = '<div class="ceo-print-wrap">';
    html += '<div class="ceo-print-header">';
    html += '<h1 class="ceo-print-title">Reporte de Manifiestos</h1>';
    html += '<p class="ceo-print-subtitle">Informe gerencial</p>';
    html += '<p class="ceo-print-period">' + rangoTexto + '</p>';
    html += '</div>';
    html += '<div class="ceo-print-body">' + contenidoConGraficos + '</div>';
    html += '<div class="ceo-print-footer">Documento generado el ' + (new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })) + ' &nbsp;|&nbsp; Reporte de Manifiestos</div>';
    html += '</div>';

    var ventanaImpresion = window.open('', '_blank', 'width=950,height=900');
    if (!ventanaImpresion) {
        alert('Por favor, permita ventanas emergentes para imprimir.');
        return;
    }
    ventanaImpresion.document.write('<html><head><meta charset="UTF-8"><title>Informe gerencial - Reporte de Manifiestos</title><style>' + estilos + '</style></head><body>');
    ventanaImpresion.document.write(html);
    ventanaImpresion.document.write('</body></html>');
    ventanaImpresion.document.close();
    ventanaImpresion.onload = function() {
        ventanaImpresion.focus();
        setTimeout(function() {
            ventanaImpresion.print();
            ventanaImpresion.onafterprint = function() { ventanaImpresion.close(); };
        }, 600);
    };
}

function semaforoKPI(opciones) {
    var v = opciones.valor;
    var verde = opciones.verde, amarillo = opciones.amarillo, rojo = opciones.rojo;
    if (rojo !== undefined && v <= rojo) return 'rojo';
    if (amarillo !== undefined && v <= amarillo) return 'amarillo';
    if (verde !== undefined) return 'verde';
    if (opciones.invertido) {
        if (verde !== undefined && v >= verde) return 'verde';
        if (amarillo !== undefined && v >= amarillo) return 'amarillo';
        return 'rojo';
    }
    return 'verde';
}

function renderDashboardEjecutivo(data) {
    datosDashboardEjecutivo = data;
    var k = data.kpis || {};
    var ch = data.choferes || {};
    var pl = data.placas || {};
    var totalMan = k.total_manifiestos || 0;
    var varPct = k.variacion_pct || 0;
    var concTop3 = k.indice_concentracion_top3 || 0;
    var depTop2 = k.indice_dependencia_top2 || 0;
    var desv = k.desviacion_estandar || 0;
    var promChofer = ch.promedio || 0;
    var utilizacion = k.utilizacion_flota || 0;
    var cocienteDesv = (promChofer > 0 && desv !== undefined) ? (desv / promChofer) : 0;

    var sVar = (varPct >= 0) ? 'verde' : (varPct > -15 ? 'amarillo' : 'rojo');
    var sConc = (concTop3 < 50) ? 'verde' : (concTop3 <= 65 ? 'amarillo' : 'rojo');
    var sDep = (depTop2 < 50) ? 'verde' : (depTop2 <= 65 ? 'amarillo' : 'rojo');
    var sDesv = (cocienteDesv < 0.6) ? 'verde' : (cocienteDesv <= 1.2 ? 'amarillo' : 'rojo');
    var sUtil = (utilizacion >= 60) ? 'verde' : (utilizacion >= 30 ? 'amarillo' : 'rojo');
    var inc = data.inactivos || {};
    var tasaInac = inc.tasa_pct != null ? inc.tasa_pct : 0;
    var sInac = (tasaInac < 5) ? 'verde' : (tasaInac <= 15 ? 'amarillo' : 'rojo');

    var tRel = k.tiempo_relavera_prom || 0;
    var sRel = (tRel < 60) ? 'verde' : (tRel <= 120 ? 'amarillo' : 'rojo');
    var tRelFmt = tRel >= 60 ? (Math.floor(tRel / 60) + 'h ' + Math.round(tRel % 60) + 'm') : (Math.round(tRel) + ' min');

    var iconVar = varPct >= 0 ? '↑' : '↓';
    var html = '';

    html += '<div class="ceo-kpi-row">';
    html += '<div class="ceo-kpi-card semaforo-verde"><div class="ceo-kpi-icon"><i class="fa fa-file-text-o"></i></div><div class="ceo-kpi-label">Total manifiestos</div><div class="ceo-kpi-value">' + totalMan + '</div><div class="ceo-kpi-tendencia">Período</div></div>';
    html += '<div class="ceo-kpi-card semaforo-' + sVar + '"><div class="ceo-kpi-icon"><i class="fa fa-line-chart"></i></div><div class="ceo-kpi-label">Variación % vs anterior</div><div class="ceo-kpi-value">' + (varPct >= 0 ? '+' : '') + varPct + '%</div><div class="ceo-kpi-tendencia">' + iconVar + '</div></div>';
    html += '<div class="ceo-kpi-card semaforo-' + sConc + '"><div class="ceo-kpi-icon"><i class="fa fa-pie-chart"></i></div><div class="ceo-kpi-label">Concentración Top 3</div><div class="ceo-kpi-value">' + concTop3 + '%</div><div class="ceo-kpi-tendencia">Plantas</div></div>';
    html += '<div class="ceo-kpi-card semaforo-' + sDep + '"><div class="ceo-kpi-icon"><i class="fa fa-exclamation-triangle"></i></div><div class="ceo-kpi-label">Dependencia Top 2</div><div class="ceo-kpi-value">' + depTop2 + '%</div><div class="ceo-kpi-tendencia">' + (depTop2 > 50 ? 'Alerta' : 'Estable') + '</div></div>';
    html += '<div class="ceo-kpi-card semaforo-' + sDesv + '"><div class="ceo-kpi-icon"><i class="fa fa-bar-chart"></i></div><div class="ceo-kpi-label">Dispersión operativa</div><div class="ceo-kpi-value">' + (typeof desv === 'number' ? desv.toFixed(1) : desv) + '</div><div class="ceo-kpi-tendencia">Desv. estándar</div></div>';
    html += '<div class="ceo-kpi-card semaforo-' + sUtil + '"><div class="ceo-kpi-icon"><i class="fa fa-truck"></i></div><div class="ceo-kpi-label">Utilización flota</div><div class="ceo-kpi-value">' + utilizacion + '%</div><div class="ceo-kpi-tendencia">Placas activas</div></div>';
    html += '<div class="ceo-kpi-card semaforo-' + sInac + '"><div class="ceo-kpi-icon"><i class="fa fa-pause-circle-o"></i></div><div class="ceo-kpi-label">% no realizados</div><div class="ceo-kpi-value">' + tasaInac + '%</div><div class="ceo-kpi-tendencia">Inactivos</div></div>';
    html += '<div class="ceo-kpi-card semaforo-' + sRel + '"><div class="ceo-kpi-icon"><i class="fa fa-clock-o"></i></div><div class="ceo-kpi-label">Tiempo Prom. Relavera</div><div class="ceo-kpi-value">' + tRelFmt + '</div><div class="ceo-kpi-tendencia">Entrada vs Salida</div></div>';
    html += '</div>';

    var tendencia30 = data.tendencia_30 || [];
    var promDia = data.tendencia_promedio || 0;
    function fmtFechaYMD(str) {
        if (!str) return '';
        var m = (str + '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        return m ? m[3] + '/' + m[2] + '/' + m[1] : str;
    }
    var txtRango = (data.fecha_ini && data.fecha_fin) ? (' (' + fmtFechaYMD(data.fecha_ini) + ' a ' + fmtFechaYMD(data.fecha_fin) + ')') : '';
    html += '<div class="ceo-bloque"><h4><i class="fa fa-calendar"></i> Tendencia temporal (período seleccionado)</h4>';
    html += '<p>Promedio diario en el rango: <strong>' + promDia + '</strong> manifiestos' + (txtRango ? '<span class="text-muted">' + txtRango + '</span>' : '') + '</p>';
    html += '<div class="ceo-chart-wrap"><canvas id="chartEjecutivoTendencia"></canvas></div></div>';

    var top10 = data.top10_plantas || [];
    var concPct = data.concentracion_top10_pct != null ? data.concentracion_top10_pct : (data.concentracion_pct || 0);
    html += '<div class="ceo-bloque"><h4><i class="fa fa-industry"></i> Concentración operativa</h4>';
    html += '<div class="ceo-chart-wrap"><canvas id="chartEjecutivoConc"></canvas></div>';
    html += '<p class="ceo-insight' + (concPct > 65 ? ' alerta-roja' : '') + '">Las ' + (top10.length >= 10 ? '10' : top10.length) + ' primeras plantas concentran el ' + concPct + '% de la operación.' + (concPct > 65 ? ' <strong>Riesgo estructural.</strong>' : '') + '</p></div>';

    var pctSobre = ch.pct_sobre_promedio || 0, pctBajo = ch.pct_bajo_promedio || 0, n80 = ch.n_choferes_80 || 0, totalCh = ch.total_choferes || 0;
    html += '<div class="ceo-bloque"><h4><i class="fa fa-users"></i> Rendimiento de capital humano</h4>';
    html += '<p><strong>' + pctSobre + '%</strong> choferes sobre promedio &nbsp;|&nbsp; <strong>' + pctBajo + '%</strong> choferes bajo promedio</p>';
    html += '<p class="ceo-insight">El <strong>' + (totalCh > 0 ? Math.round((n80 / totalCh) * 100) : 0) + '%</strong> de los choferes generan el 80% de los manifiestos.</p>';
    html += '<div class="ceo-chart-wrap"><canvas id="chartEjecutivoChofer"></canvas></div></div>';

    var pctBajaPla = pl.pct_baja_actividad || 0, idxConcPla = pl.indice_concentracion || 0;
    html += '<div class="ceo-bloque"><h4><i class="fa fa-truck"></i> Utilización de activos</h4>';
    html += '<p><strong>' + pctBajaPla + '%</strong> placas con baja actividad &nbsp;|&nbsp; Índice concentración flota: <strong>' + idxConcPla + '%</strong></p>';
    html += '<p class="ceo-insight' + (idxConcPla > 70 ? ' alerta-roja' : '') + '">El 20% de la flota concentra el ' + idxConcPla + '% de la operación.' + (idxConcPla > 70 ? ' <strong>Riesgo por sobreuso.</strong>' : '') + '</p></div>';

    html += '<div class="ceo-bloque"><h4><i class="fa fa-bell"></i> Alertas automáticas</h4><ul class="ceo-alertas-list">';
    (data.alertas || []).forEach(function(a) {
        var icono = a.nivel === 'alto' ? '🔴' : (a.nivel === 'atencion' ? '🟡' : '🟢');
        var cls = a.nivel === 'alto' ? 'nivel-alto' : (a.nivel === 'atencion' ? 'nivel-atencion' : 'nivel-estable');
        html += '<li class="' + cls + '">' + icono + ' ' + (a.texto || '') + '</li>';
    });
    html += '</ul></div>';

    var inc = data.inactivos || {};
    var totalInac = inc.total_inactivos != null ? inc.total_inactivos : 0;
    var totalAct = inc.total_activos != null ? inc.total_activos : totalMan;
    var totalGen = inc.total_generados != null ? inc.total_generados : (totalMan + totalInac);
    var tasaPct = inc.tasa_pct != null ? inc.tasa_pct : 0;
    var topPlantasInac = inc.top_plantas || [];
    html += '<div class="ceo-bloque"><h4><i class="fa fa-pause-circle-o"></i> Manifiestos inactivos (turnos no realizados)</h4>';
    if (totalInac === 0 && totalAct === 0) {
        html += '<p class="ceo-insight">No hay manifiestos inactivos en el período.</p>';
    } else {
        html += '<p>En el período hay <strong>' + totalInac + '</strong> manifiestos inactivos sobre <strong>' + totalAct + '</strong> manifiestos realizados (<strong>' + tasaPct + '%</strong> no realizados respecto a realizados).</p>';
        if (topPlantasInac.length > 0) {
            var sumTopInac = 0;
            topPlantasInac.forEach(function(p) { sumTopInac += (p.total_inactivos || 0); });
            var pctConc = totalInac > 0 ? Math.round((sumTopInac / totalInac) * 100) : 0;
            html += '<p class="ceo-insight">Las ' + topPlantasInac.length + ' plantas con mayor tasa (inactivos/realizados) concentran el ' + pctConc + '% de los no realizados. <strong>Recomendación:</strong> revisar causas (logística, cliente, capacidad) en esas plantas.</p>';
            html += '<div class="table-responsive"><table class="table table-condensed table-bordered" style="max-width: 600px;"><thead><tr><th>Planta</th><th>Activos</th><th>Inactivos</th><th>Tasa %</th></tr></thead><tbody>';
            topPlantasInac.forEach(function(p) {
                var tasaPlanta = (p.tasa_pct != null) ? p.tasa_pct : (p.total_activos > 0 ? ((p.total_inactivos || 0) / p.total_activos * 100).toFixed(1) : (p.total_inactivos > 0 ? '100' : '0'));
                html += '<tr><td><a href="#" class="ceo-planta-inactivos-link" data-pla-cod="' + (p.Pla_Cod || '') + '" data-pla-nom="' + (p.Pla_Nom || '').replace(/"/g, '&quot;') + '" title="Ver vehículos y choferes">' + (p.Pla_Nom || '') + ' <i class="fa fa-external-link" style="font-size: 10px;"></i></a></td><td>' + (p.total_activos != null ? p.total_activos : '-') + '</td><td>' + (p.total_inactivos || 0) + '</td><td>' + tasaPlanta + '%</td></tr>';
            });
            html += '</tbody></table></div>';
            html += '<div style="margin-top: 10px; margin-bottom: 12px;"><button type="button" class="btn btn-info btn-sm" onclick="exportarExcelInactivos();"><i class="fa fa-file-excel-o"></i> Excel - Listado completo de manifiestos inactivos</button></div>';
            html += '<div class="ceo-chart-wrap" style="min-height: 220px;"><canvas id="chartEjecutivoInactivos"></canvas></div>';
        }
    }
    html += '</div>';

    // Bloque RESPONSABILIDAD OPERATIVA (debajo de Manifiestos inactivos)
    var resp = data.responsabilidad || {};
    var topChoferesInac = resp.top_choferes || [];
    var topPlacasInac = resp.top_placas || [];
    var paretoChoferesInac = resp.pareto_choferes || [];
    var insightResp = resp.insight || '';
    html += '<div class="ceo-bloque"><h4><i class="fa fa-user-circle-o"></i> RESPONSABILIDAD OPERATIVA</h4>';
    html += '<p class="ceo-insight">' + (insightResp || 'Sin datos de concentración de inactivos por chofer.') + '</p>';
    html += '<div class="row">';
    html += '<div class="col-md-6"><h5 style="margin-top:0; font-size:13px; color:#2C5D94;">Top 5 Choferes con más inactivos</h5>';
    html += '<div class="table-responsive"><table class="table table-condensed table-bordered" style="max-width:100%;"><thead><tr><th>Chofer</th><th>Inactivos</th><th>Total turnos</th><th>%</th><th>Riesgo</th></tr></thead><tbody>';
    topChoferesInac.forEach(function(c) {
        var riesgoCls = (c.riesgo === 'verde') ? 'semaforo-verde' : (c.riesgo === 'amarillo' ? 'semaforo-amarillo' : 'semaforo-rojo');
        var riesgoIcon = (c.riesgo === 'verde') ? '<span style="color:#22c55e;">&#9679;</span>' : (c.riesgo === 'amarillo' ? '<span style="color:#eab308;">&#9679;</span>' : '<span style="color:#dc2626;">&#9679;</span>');
        html += '<tr><td>' + (c.chofer_nombre || '') + '</td><td>' + (c.inactivos || 0) + '</td><td>' + (c.total_turnos || 0) + '</td><td>' + (c.tasa_pct != null ? c.tasa_pct : '-') + '%</td><td class="' + riesgoCls + '">' + riesgoIcon + '</td></tr>';
    });
    if (topChoferesInac.length === 0) html += '<tr><td colspan="5">Sin datos</td></tr>';
    html += '</tbody></table></div></div>';
    html += '<div class="col-md-6"><h5 style="margin-top:0; font-size:13px; color:#2C5D94;">Top 5 Placas con más inactivos</h5>';
    html += '<div class="table-responsive"><table class="table table-condensed table-bordered" style="max-width:100%;"><thead><tr><th>Placa</th><th>Inactivos</th><th>% sobre turnos</th></tr></thead><tbody>';
    topPlacasInac.forEach(function(pl) {
        html += '<tr><td>' + (pl.placa || '') + '</td><td>' + (pl.inactivos || 0) + '</td><td>' + (pl.tasa_pct != null ? pl.tasa_pct : '-') + '%</td></tr>';
    });
    if (topPlacasInac.length === 0) html += '<tr><td colspan="3">Sin datos</td></tr>';
    html += '</tbody></table></div></div></div>';
    html += '<div class="ceo-chart-wrap" style="min-height: 260px; margin-top: 14px;"><canvas id="chartEjecutivoParetoInactivos"></canvas></div>';
    html += '</div>';

    $('#dashboardContentEjecutivo').html(html);

    if (chartEjecutivoConcentracion) { chartEjecutivoConcentracion.destroy(); chartEjecutivoConcentracion = null; }
    // Dividir nombre de planta en varias líneas (máx. maxLen por línea) para que no se corte
    function wrapPlantLabel(str, maxLen) {
        if (!str || !str.length) return str;
        maxLen = maxLen || 16;
        if (str.length <= maxLen) return str;
        var lines = [];
        var rest = str.trim();
        while (rest.length > maxLen) {
            var idx = rest.lastIndexOf(' ', maxLen);
            if (idx <= 0) idx = rest.indexOf(' ', maxLen);
            if (idx <= 0) idx = maxLen;
            lines.push(rest.substring(0, idx).trim());
            rest = rest.substring(idx).trim();
        }
        if (rest.length) lines.push(rest);
        return lines.join('\n');
    }
    var labelsConc = top10.map(function(p) { return wrapPlantLabel((p.Pla_Nom || ''), 16); });
    var totalsConc = top10.map(function(p) { return p.total; });
    var acum = 0;
    var acumulada = totalsConc.map(function(t) { acum += t; return totalMan > 0 ? Math.round((acum / totalMan) * 100) : 0; });
    var ctxConc = document.getElementById('chartEjecutivoConc');
    if (ctxConc && typeof Chart !== 'undefined') {
        chartEjecutivoConcentracion = new Chart(ctxConc, {
            type: 'bar',
            data: {
                labels: labelsConc,
                datasets: [
                    { label: 'Manifiestos', data: totalsConc, backgroundColor: 'rgba(44, 93, 148, 0.7)', order: 2 },
                    { label: '% Acumulado', data: acumulada, type: 'line', borderColor: '#2C5D94', borderWidth: 2, fill: false, yAxisID: 'y1', order: 1 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 20, bottom: 52 } },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            title: function(items) {
                                var idx = items && items.length ? items[0].dataIndex : 0;
                                var p = top10[idx] || {};
                                return p.Pla_Nom || '';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            minRotation: 0,
                            autoSkip: false,
                            font: { size: 10 },
                            callback: function(val, idx) {
                                return ''; // El plugin wrapXLabels dibuja los nombres con salto de línea
                            }
                        }
                    },
                    y: { beginAtZero: true, position: 'left' },
                    y1: { beginAtZero: true, max: 100, position: 'right', grid: { drawOnChartArea: false } }
                }
            },
            plugins: [
                {
                    id: 'wrapXLabels',
                    afterDraw: function(chart) {
                        var scale = chart.scales.x;
                        if (!scale) {
                            var keys = Object.keys(chart.scales || {});
                            for (var k = 0; k < keys.length; k++) {
                                if (chart.scales[keys[k]].position === 'bottom') {
                                    scale = chart.scales[keys[k]];
                                    break;
                                }
                            }
                        }
                        if (!scale) return;
                        var count = totalsConc.length;
                        var ctx = chart.ctx;
                        var meta0 = chart.getDatasetMeta(0);
                        var tickOpts = scale.options.ticks || {};
                        var color = tickOpts.color || 'rgba(0,0,0,0.87)';
                        var padding = tickOpts.padding !== undefined ? tickOpts.padding : 6;
                        var fontSize = (tickOpts.font && tickOpts.font.size) ? tickOpts.font.size : 10;
                        var lineHeight = fontSize * 1.2;
                        ctx.save();
                        ctx.font = fontSize + 'px ' + (tickOpts.font && tickOpts.font.family ? tickOpts.font.family : 'sans-serif');
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'top';
                        ctx.fillStyle = color;
                        for (var i = 0; i < count; i++) {
                            var label = (labelsConc[i] || '').toString();
                            if (!label) continue;
                            var x = (scale.getPixelForTick && scale.getPixelForTick(i)) || (meta0.data[i] && meta0.data[i].x);
                            if (typeof x !== 'number') continue;
                            var y = scale.bottom + padding;
                            var lines = label.split('\n');
                            for (var j = 0; j < lines.length; j++) {
                                ctx.fillText(lines[j], x, y + j * lineHeight);
                            }
                        }
                        ctx.restore();
                    }
                },
                {
                    id: 'barTotalManifiestos',
                    afterDatasetsDraw: function(chart) {
                        var meta = chart.getDatasetMeta(0);
                        if (!meta || !meta.data || !totalsConc.length) return;
                        var ctx = chart.ctx;
                        var scaleY = chart.scales.y;
                        ctx.save();
                        ctx.font = 'bold 11px sans-serif';
                        ctx.textAlign = 'center';
                        ctx.fillStyle = '#2C5D94';
                        for (var i = 0; i < meta.data.length; i++) {
                            var bar = meta.data[i];
                            var val = totalsConc[i];
                            if (val == null) continue;
                            var y = bar.y - 6;
                            if (scaleY && y < scaleY.top) y = scaleY.top + 10;
                            ctx.fillText(String(val), bar.x, y);
                        }
                        ctx.restore();
                    }
                }
            ]
        });
    }

    var distCh = ch.distribucion || [];
    var ctxCh = document.getElementById('chartEjecutivoChofer');
    if (ctxCh && typeof Chart !== 'undefined' && distCh.length) {
        var labCh = distCh.slice(0, 15).map(function(c, i) { return (c.nombre || '').substring(0, 8) || ('Ch ' + (i + 1)); });
        var datCh = distCh.slice(0, 15).map(function(c) { return c.total; });
        var choferesTop15 = distCh.slice(0, 15);
        new Chart(ctxCh, {
            type: 'bar',
            data: { labels: labCh, datasets: [{ label: 'Manifiestos', data: datCh, backgroundColor: 'rgba(44, 93, 148, 0.6)' }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(items) {
                                var idx = items && items.length ? items[0].dataIndex : 0;
                                var c = choferesTop15[idx] || {};
                                return c.nombre || labCh[idx] || '';
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'barDataLabelsChofer',
                afterDatasetsDraw: function(chart) {
                    var meta = chart.getDatasetMeta(0);
                    if (!meta || !meta.data || !datCh.length) return;
                    var ctx = chart.ctx;
                    var scaleY = chart.scales.y;
                    ctx.save();
                    ctx.font = 'bold 11px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillStyle = '#2C5D94';
                    for (var i = 0; i < meta.data.length; i++) {
                        var bar = meta.data[i];
                        var val = datCh[i];
                        if (val == null) continue;
                        var y = bar.y - 6;
                        if (scaleY && y < scaleY.top) y = scaleY.top + 10;
                        ctx.fillText(String(val), bar.x, y);
                    }
                    ctx.restore();
                }
            }]
        });
    }

    if (chartEjecutivoTendencia) { chartEjecutivoTendencia.destroy(); chartEjecutivoTendencia = null; }
    var labT = tendencia30.map(function(d) { return (d.fecha || '').substring(5); });
    var datT = tendencia30.map(function(d) { return d.total; });
    var ctxT = document.getElementById('chartEjecutivoTendencia');
    if (ctxT && typeof Chart !== 'undefined') {
        chartEjecutivoTendencia = new Chart(ctxT, {
            type: 'line',
            data: {
                labels: labT,
                datasets: [
                    { label: 'Manifiestos por día', data: datT, borderColor: '#2C5D94', backgroundColor: 'rgba(44, 93, 148, 0.1)', fill: true, tension: 0.2 },
                    { label: 'Promedio', data: datT.map(function() { return promDia; }), borderColor: '#94a3b8', borderDash: [4, 2], fill: false }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } },
            plugins: [{
                id: 'lineDataLabelsEjecutivoTendencia',
                afterDatasetsDraw: function(chart) {
                    var meta = chart.getDatasetMeta(0);
                    if (!meta || !meta.data || !datT.length) return;
                    var ctx = chart.ctx;
                    var scaleY = chart.scales.y;
                    ctx.save();
                    ctx.font = 'bold 11px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillStyle = '#2C5D94';
                    for (var i = 0; i < meta.data.length; i++) {
                        var point = meta.data[i];
                        var val = datT[i];
                        if (val == null) continue;
                        var y = point.y - 10;
                        if (scaleY && y < scaleY.top) y = scaleY.top + 8;
                        ctx.fillText(String(val), point.x, y);
                    }
                    ctx.restore();
                }
            }]
        });
    }

    if (chartEjecutivoInactivos) { chartEjecutivoInactivos.destroy(); chartEjecutivoInactivos = null; }
    var ctxInac = document.getElementById('chartEjecutivoInactivos');
    if (ctxInac && typeof Chart !== 'undefined' && topPlantasInac.length > 0) {
        // Usar nombre completo de la planta para que cada barra se distinga (no truncar)
        var labInac = topPlantasInac.map(function(p) { return (p.Pla_Nom || 'Sin nombre').trim(); });
        var datInac = topPlantasInac.map(function(p) { return p.total_inactivos || 0; });
        chartEjecutivoInactivos = new Chart(ctxInac, {
            type: 'bar',
            data: { labels: labInac, datasets: [{ label: 'Inactivos', data: datInac, backgroundColor: 'rgba(200, 80, 80, 0.7)' }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true },
                    y: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0,
                            font: { size: 11 }
                        }
                    }
                },
                layout: { padding: { left: 4 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(items) {
                                var idx = items && items.length ? items[0].dataIndex : 0;
                                var p = topPlantasInac[idx] || {};
                                return p.Pla_Nom || '';
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfico Pareto inactivos por chofer (hasta 70% acumulado)
    if (chartEjecutivoParetoInactivos) { chartEjecutivoParetoInactivos.destroy(); chartEjecutivoParetoInactivos = null; }
    var ctxPareto = document.getElementById('chartEjecutivoParetoInactivos');
    if (ctxPareto && typeof Chart !== 'undefined' && paretoChoferesInac.length > 0 && totalInac > 0) {
        var totalInacPareto = 0;
        paretoChoferesInac.forEach(function(c) { totalInacPareto += c.inactivos || 0; });
        var acumPareto = 0;
        var labelsPareto = [];
        var labelsParetoFull = [];
        var dataPareto = [];
        var dataAcumPct = [];
        function nombresApellidosAbajo(nombreCompleto) {
            var s = (nombreCompleto || '').trim();
            if (!s) return 'Chofer';
            var parts = s.split(/\s+/).filter(Boolean);
            if (parts.length <= 2) return s;
            return parts.slice(0, 2).join(' ') + '\n' + parts.slice(2).join(' ');
        }
        for (var i = 0; i < paretoChoferesInac.length; i++) {
            acumPareto += paretoChoferesInac[i].inactivos || 0;
            var nom = (paretoChoferesInac[i].chofer_nombre || '').trim() || ('Chofer ' + (i + 1));
            labelsParetoFull.push(nom);
            labelsPareto.push(nombresApellidosAbajo(nom));
            dataPareto.push(paretoChoferesInac[i].inactivos || 0);
            dataAcumPct.push(totalInacPareto > 0 ? Math.round((acumPareto / totalInacPareto) * 100) : 0);
            if (totalInacPareto > 0 && (acumPareto / totalInacPareto) >= 0.70) break;
        }
        if (labelsPareto.length > 12) {
            labelsPareto = labelsPareto.slice(0, 12);
            labelsParetoFull = labelsParetoFull.slice(0, 12);
            dataPareto = dataPareto.slice(0, 12);
            dataAcumPct = dataAcumPct.slice(0, 12);
        }
        chartEjecutivoParetoInactivos = new Chart(ctxPareto, {
            type: 'bar',
            data: {
                labels: labelsPareto,
                datasets: [
                    { label: 'Inactivos', data: dataPareto, backgroundColor: 'rgba(200, 80, 80, 0.7)', order: 2 },
                    { label: '% Acumulado', data: dataAcumPct, type: 'line', borderColor: '#2C5D94', borderWidth: 2, fill: false, yAxisID: 'y1', order: 1 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { bottom: 48 } },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            minRotation: 0,
                            font: { size: 10 },
                            maxTicksLimit: 14,
                            callback: function() { return ''; }
                        }
                    },
                    y: { beginAtZero: true, position: 'left' },
                    y1: { beginAtZero: true, max: 100, position: 'right', grid: { drawOnChartArea: false } }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            title: function(items) {
                                var idx = items && items.length ? items[0].dataIndex : 0;
                                return labelsParetoFull[idx] || '';
                            },
                            afterBody: function(items) {
                                var idx = items && items.length ? items[0].dataIndex : 0;
                                var c = paretoChoferesInac[idx] || {};
                                var lineas = [];
                                var detallePlacas = c.placas_detalle || [];
                                if (detallePlacas.length) {
                                    lineas.push('Placas:');
                                    detallePlacas.forEach(function(p) {
                                        lineas.push('  ' + (p.Veh_Pla || 'Sin placa') + ' — ' + (p.inactivos != null ? p.inactivos : 0) + ' inactivo(s)');
                                    });
                                }
                                var detalle = c.plantas_detalle || [];
                                if (detalle.length) {
                                    lineas.push('Plantas:');
                                    detalle.forEach(function(p) {
                                        lineas.push('  ' + (p.Pla_Nom || 'Sin planta') + ' — ' + (p.inactivos != null ? p.inactivos : 0) + ' inactivo(s)');
                                    });
                                }
                                return lineas;
                            },
                            afterLabel: function(ctx) {
                                if (ctx.datasetIndex === 1 && dataAcumPct[ctx.dataIndex] != null) return 'Acum: ' + dataAcumPct[ctx.dataIndex] + '%';
                                return '';
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'paretoXLabelsNombresApellidos',
                afterDraw: function(chart) {
                    var scale = chart.scales.x;
                    if (!scale) return;
                    var ctx = chart.ctx;
                    var meta0 = chart.getDatasetMeta(0);
                    if (!meta0 || !meta0.data || !meta0.data.length) return;
                    var tickOpts = scale.options.ticks || {};
                    var color = tickOpts.color || 'rgba(0,0,0,0.87)';
                    var padding = tickOpts.padding !== undefined ? tickOpts.padding : 6;
                    var fontSize = (tickOpts.font && tickOpts.font.size) ? tickOpts.font.size : 10;
                    var lineHeight = fontSize * 1.2;
                    ctx.save();
                    ctx.font = fontSize + 'px ' + (tickOpts.font && tickOpts.font.family ? tickOpts.font.family : 'sans-serif');
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'top';
                    ctx.fillStyle = color;
                    for (var i = 0; i < labelsPareto.length; i++) {
                        var label = (labelsPareto[i] || '').toString();
                        if (!label) continue;
                        var bar = meta0.data[i];
                        if (!bar || typeof bar.x !== 'number') continue;
                        var y = scale.bottom + padding;
                        var lines = label.split('\n');
                        for (var j = 0; j < lines.length; j++) {
                            ctx.fillText(lines[j].trim(), bar.x, y + j * lineHeight);
                        }
                    }
                    ctx.restore();
                }
            }]
        });
    }

    // Delegado: clic en planta del bloque inactivos -> modal drill-down
    $(document).off('click.ceoPlantaInactivos').on('click.ceoPlantaInactivos', '.ceo-planta-inactivos-link', function(e) {
        e.preventDefault();
        var plaCod = $(this).data('pla-cod');
        var plaNom = $(this).data('pla-nom');
        if (!plaCod) return;
        $('#modalInactivosDetallePlantaTitulo').text('Inactivos: ' + (plaNom || 'Planta'));
        $('#modalInactivosDetallePlantaCuerpo').html('<p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');
        $('#modalInactivosDetallePlanta').modal('show');
        var fi = $('#fecha_inicio_ejecutivo').val();
        var ff = $('#fecha_fin_ejecutivo').val();
        $.ajax({
            url: '',
            data: { getInactivosDetallePlantaAjax: true, Pla_Cod: plaCod, fecha_inicio: fi, fecha_fin: ff },
            dataType: 'json',
            success: function(res) {
                if (res.success && res.detalle && res.detalle.length) {
                    var tbl = '<p>Total inactivos en esta planta: <strong>' + (res.total_inactivos || 0) + '</strong></p>';
                    tbl += '<div class="table-responsive"><table class="table table-condensed table-bordered modal-inactivos-detalle-tabla"><thead><tr><th class="col-num-manifiesto-inac">N° Manifiesto</th><th>Vehículo (placa)</th><th class="col-chofer-ancho">Chofer</th><th>Inactivos</th><th>%</th></tr></thead><tbody>';
                    res.detalle.forEach(function(d) {
                        tbl += '<tr><td class="col-num-manifiesto-inac">' + (d.numeros_manifiesto || '-') + '</td><td>' + (d.placa || '') + '</td><td class="col-chofer-ancho">' + (d.chofer_nombre || '') + '</td><td>' + (d.inactivos || 0) + '</td><td>' + (d.pct != null ? d.pct : '-') + '%</td></tr>';
                    });
                    tbl += '</tbody></table></div>';
                    $('#modalInactivosDetallePlantaCuerpo').html(tbl);
                } else if (res.success && (!res.detalle || res.detalle.length === 0)) {
                    $('#modalInactivosDetallePlantaCuerpo').html('<p class="text-muted">No hay detalle de vehículos/choferes para esta planta en el período.</p>');
                } else {
                    $('#modalInactivosDetallePlantaCuerpo').html('<p class="text-danger">' + (res.message || 'Error al cargar.') + '</p>');
                }
            },
            error: function() {
                $('#modalInactivosDetallePlantaCuerpo').html('<p class="text-danger">Error de conexión.</p>');
            }
        });
    });
}

function exportarExcelInactivos() {
    var fi = $('#fecha_inicio_ejecutivo').val();
    var ff = $('#fecha_fin_ejecutivo').val();
    if (!fi || !ff) {
        alert('Indique el rango de fechas en la Vista Ejecutiva (Desde / Hasta) y genere el tablero primero.');
        return;
    }
    $.ajax({
        url: '',
        data: { getListadoInactivosExcelAjax: true, fecha_inicio: fi, fecha_fin: ff },
        dataType: 'json',
        success: function(res) {
            if (!res.success) {
                alert(res.message || 'Error al obtener el listado.');
                return;
            }
            var listado = res.listado || [];
            var totalInac = res.total_inactivos != null ? res.total_inactivos : listado.length;
            var totalAct = res.total_activos != null ? res.total_activos : 0;
            var tasaPct = res.tasa_pct != null ? res.tasa_pct : 0;
            var fechaIni = res.fecha_ini || '';
            var fechaFin = res.fecha_fin || '';
            var rangoFechas = (fechaIni && fechaFin) ? (fechaIni + ' - ' + fechaFin) : '';
            var estilo = 'table.xl-inac { border-collapse: collapse; } table.xl-inac td, table.xl-inac th { border: 1px solid #2C5D94; padding: 4px 8px; } .xl-inac-header { background: #2C5D94; color: white; font-weight: bold; } .xl-inac-enc { font-weight: bold; color: #2C5D94; padding: 6px 0 4px 0; } .xl-inac-resumen { padding: 2px 0 8px 0; } .xl-inac-rango { padding: 2px 0 10px 0; color: #555; }';
            var html = '<style type="text/css">' + estilo + '</style>';
            html += '<div class="xl-inac-enc">Manifiestos inactivos (turnos no realizados)</div>';
            html += '<div class="xl-inac-resumen">En el período hay <strong>' + totalInac + '</strong> manifiestos inactivos sobre <strong>' + totalAct + '</strong> manifiestos realizados (<strong>' + tasaPct + '%</strong> no realizados respecto a realizados).</div>';
            if (rangoFechas) html += '<div class="xl-inac-rango">Rango de fechas: ' + rangoFechas + '</div>';
            html += '<table class="xl-inac"><tr class="xl-inac-header"><th>PLANTA</th><th>N° Manifiesto</th><th>Vehículo (placa)</th><th>Chofer</th><th>Inactivos</th></tr>';
            listado.forEach(function(r) {
                html += '<tr>';
                html += '<td>' + (r.Pla_Nom || '') + '</td>';
                html += '<td>' + (r.ManNum || '') + '</td>';
                html += '<td>' + (r.placa || '') + '</td>';
                html += '<td>' + (r.chofer_nombre || '') + '</td>';
                html += '<td>1</td>';
                html += '</tr>';
            });
            html += '</table>';
            var nombre = 'manifiestos_inactivos_' + (fi || '').replace(/-/g, '_') + '_' + (ff || '').replace(/-/g, '_');
            var form = $('<form>', { method: 'POST', action: '../../Librerias/exportar/ficheroExcel.php', target: '_blank' });
            form.append($('<input>', { type: 'hidden', name: 'datos_a_enviar', value: html }));
            form.append($('<input>', { type: 'hidden', name: 'nombre', value: nombre }));
            form.append($('<input>', { type: 'hidden', name: 'hoja', value: 'Manifiestos inactivos' }));
            $('body').append(form);
            form.submit();
            form.remove();
        },
        error: function() {
            alert('Error de conexión al generar el Excel.');
        }
    });
}
