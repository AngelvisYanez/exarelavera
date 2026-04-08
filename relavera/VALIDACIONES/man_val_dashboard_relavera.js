/**
 * Dashboard Operativo RELAVERA - Validaciones y lógica cliente
 * Sin setInterval ni auto-refresh. Actualización manual con botón.
 * @author Sistema EXA
 * @version 1.0
 */
(function($) {
    'use strict';

    var DASHBOARD_URL = 'dashboard_relavera.php';

    /**
     * Obtiene parámetros de filtro del formulario
     */
    function getFiltros() {
        return {
            getDashboardRelaveraAjax: 1,
            fecha_inicio: $('#fechaInicio').val() || '',
            fecha_fin: $('#fechaFin').val() || '',
            Pla_Cod: $('#selPlanta').val() || '',
            Cli_Cod: $('#selCliente').val() || '',
            Man_Tip: $('#selEstado').val() || ''
        };
    }

    /**
     * Formatea número como moneda (siempre valor absoluto para visualización)
     */
    function fmtMoneda(val) {
        if (val === null || val === undefined || isNaN(val)) return '0,00';
        var p = parseFloat(val);
        var s = (p < 0 ? '-' : '');
        return s + Math.abs(p).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Carga los datos del dashboard y renderiza el HTML
     */
    function cargarDashboard() {
        var $cont = $('#contenedorDashboard');
        $cont.html('<div style="text-align: center; padding: 40px;"><i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size: 24px;"></i><p>Cargando datos...</p></div>');

        $.ajax({
            url: DASHBOARD_URL,
            type: 'GET',
            data: getFiltros(),
            dataType: 'json',
            cache: false
        }).done(function(r) {
            if (!r || !r.success) {
                $cont.html('<div class="alert alert-danger"><i class="glyphicon glyphicon-alert"></i> Error: ' + (r && r.message ? r.message : 'No se pudieron cargar los datos') + '</div>');
                return;
            }
            renderizarDashboard($cont, r);
        }).fail(function(xhr, status, err) {
            $cont.html('<div class="alert alert-danger"><i class="glyphicon glyphicon-alert"></i> Error de conexión: ' + (err || status) + '</div>');
        });
    }

    /**
     * Renderiza el HTML del dashboard con los datos recibidos
     */
    function renderizarDashboard($cont, d) {
        var html = '';
        var r = d.resumen || {};
        var consumoTotal = (r.consumo_total !== undefined) ? r.consumo_total : (parseFloat(r.consumo_facturado || 0) + parseFloat(r.consumo_pendiente || 0));
        var periodoLabel = (d.fecha_inicio && d.fecha_fin) ? (d.fecha_inicio + ' a ' + d.fecha_fin) : '';
        var hayFiltroPeriodo = !!(d.fecha_inicio && d.fecha_fin);
        var saldoInicial = parseFloat(r.saldo_inicial || 0);

        // Etiqueta período
        html += '<p class="dashboard-periodo-label"><i class="glyphicon glyphicon-calendar"></i> Datos correspondientes al período seleccionado' + (periodoLabel ? ': ' + periodoLabel : '') + '</p>';

        /* ========== RESUMEN FINANCIERO DEL PERÍODO ========== */
        html += '<div class="dashboard-seccion-titulo"><i class="glyphicon glyphicon-usd"></i> RESUMEN FINANCIERO DEL PERÍODO</div>';
        /* Fila 1 – RESULTADO FINANCIERO: Saldo Inicial, Total Anticipo, Consumo, Saldo Final */
        html += '<div class="row">';
        html += '<div class="col-xs-12 col-sm-6 col-md-3"><div class="card-financiero kpi-card card-fin-inicio">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-folder-open"></i></div>';
        html += '<h5>Saldo Inicial</h5><h3>USD ' + fmtMoneda(saldoInicial) + '</h3>';
        html += '<small>Inicio del periodo</small></div></div>';
        html += '<div class="col-xs-12 col-sm-6 col-md-3"><div class="card-financiero kpi-card card-fin-amarillo">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-plus"></i></div>';
        html += '<h5>Total Anticipo Generado</h5><h3>USD ' + fmtMoneda(r.total_anticipo_generado || 0) + '</h3>';
        html += '<small>Aprobados + Pendientes + Retenciones</small></div></div>';
        html += '<div class="col-xs-12 col-sm-6 col-md-3"><div class="card-financiero kpi-card card-fin-rosa">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-shopping-cart"></i></div>';
        html += '<h5>Consumo Total</h5><h3>USD ' + fmtMoneda(consumoTotal) + '</h3>';
        html += '<small>Uso del anticipo</small></div></div>';
        html += '<div class="col-xs-12 col-sm-6 col-md-3"><div class="card-financiero kpi-card card-fin-morado">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-ok-circle"></i></div>';
        html += '<h5>Saldo Final</h5><h3>USD ' + fmtMoneda(r.saldo_final || 0) + '</h3>';
        html += '<small>Disponible</small></div></div>';
        html += '</div>';
        /* Fila 2 – COMPOSICIÓN DEL ANTICIPO GENERADO */
        html += '<div class="dashboard-subseccion-titulo"><i class="glyphicon glyphicon-list-alt"></i> COMPOSICIÓN DEL ANTICIPO GENERADO</div>';
        html += '<div class="row">';
        html += '<div class="col-xs-12 col-sm-6 col-md-3"><div class="card-financiero kpi-card-detalle card-fin-verde">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-ok-sign"></i></div>';
        html += '<h5>Aprobados</h5><h3>USD ' + fmtMoneda(r.anticipo_aprobado || 0) + '</h3>';
        html += '<small>Anticipos aprobados</small></div></div>';
        html += '<div class="col-xs-12 col-sm-6 col-md-3"><div class="card-financiero kpi-card-detalle card-fin-naranja">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-time"></i></div>';
        html += '<h5>Por Aprobar</h5><h3>USD ' + fmtMoneda(r.anticipo_por_aprobar || 0) + '</h3>';
        html += '<small>Pendientes</small></div></div>';
        html += '<div class="col-xs-12 col-sm-6 col-md-3"><div class="card-financiero kpi-card-detalle card-fin-azul">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-pause"></i></div>';
        html += '<h5>Retenciones</h5><h3>USD ' + fmtMoneda(r.anticipo_retencion || 0) + '</h3>';
        html += '<small>Retenciones</small></div></div>';
        html += '<div class="col-xs-12 col-sm-6 col-md-3"></div>';
        html += '</div>';

        /* ========== INDICADORES OPERATIVOS - 4 KPIs ========== */
        var turnosPendientesKpi = (d.turnos_pendientes || 0) + (d.garita_in_rango || 0); /* Pendientes + Garita IN */
        var turnosAprobadosKpi = d.garita_out_rango || 0; /* Garita OUT */
        html += '<div class="dashboard-seccion-titulo"><i class="glyphicon glyphicon-dashboard"></i> INDICADORES OPERATIVOS</div>';
        html += '<p class="dashboard-periodo-label" style="margin-top:-5px; margin-bottom:12px;"><i class="glyphicon glyphicon-info-sign"></i> KPIs según rango de fechas seleccionado</p>';
        html += '<div class="row">';
        html += '<div class="col-xs-12 col-sm-6 col-md-4 col-lg-2"><div class="dashboard-card card-azul" style="min-height:110px;">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-time"></i></div>';
        html += '<div class="card-title">Turnos</div>';
        html += '<div class="card-value">' + (d.turnos_hoy || 0) + '</div>';
        html += '<div class="card-detail">en rango de fechas</div></div></div>';
        html += '<div class="col-xs-12 col-sm-6 col-md-4 col-lg-2"><div class="dashboard-card card-morado" style="min-height:110px;">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-file"></i></div>';
        html += '<div class="card-title">Turnos Pendientes</div>';
        html += '<div class="card-value">' + turnosPendientesKpi + '</div>';
        html += '<div class="card-detail">Pendientes + Garita IN</div></div></div>';
        html += '<div class="col-xs-12 col-sm-6 col-md-4 col-lg-2"><div class="dashboard-card card-verde" style="min-height:110px;">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-ok"></i></div>';
        html += '<div class="card-title">Aprobados</div>';
        html += '<div class="card-value">' + turnosAprobadosKpi + '</div>';
        html += '<div class="card-detail">Garita OUT</div></div></div>';
        html += '<div class="col-xs-12 col-sm-6 col-md-4 col-lg-2"><div class="dashboard-card card-rojo" style="min-height:110px;">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-remove"></i></div>';
        html += '<div class="card-title">Turnos Anulados</div>';
        html += '<div class="card-value">' + (d.turnos_anulados || 0) + '</div>';
        html += '<div class="card-detail">en rango</div></div></div>';

        /* ========== NUEVA TARJETA: FACTURACIÓN ========== */
        var factSummary = d.facturacion_summary || [];
        var factHTML = '';
        var tooltipHtml = '';
        var diarioTotal = 0;
        
        factSummary.forEach(function(it) {
            if (it.modo === 'D') {
                diarioTotal += parseInt(it.cantidad);
                tooltipHtml += "Planta: " + it.planta + ", Cliente: " + it.cliente + ", Manifiestos: " + it.cantidad + "\n";
            }
        });

        factHTML += '<div class="card-title">Facturación Diaria Pendiente</div>';
        factHTML += '<div class="card-value">' + (diarioTotal > 0 ? 'SI' : 'NO') + '</div>';
        factHTML += '<div class="card-detail">Pendientes de facturar</div>';

        html += '<div class="col-xs-12 col-sm-6 col-md-4 col-lg-2"><div class="dashboard-card" style="min-height:110px; background: linear-gradient(135deg, #0097a7 0%, #00acc1 100%);" title="' + (tooltipHtml.trim() || 'No hay facturación diaria pendiente') + '">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-list-alt"></i></div>';
        html += factHTML;
        html += '</div></div>';

        // KPI Tiempo Relavera (Entrada vs Salida)
        var tRel = d.tiempo_relavera_prom || 0;
        var tRelFmt = tRel >= 60 ? (Math.floor(tRel / 60) + 'h ' + Math.round(tRel % 60) + 'm') : (Math.round(tRel) + ' min');
        html += '<div class="col-xs-12 col-sm-6 col-md-4 col-lg-2"><div class="dashboard-card" style="min-height:110px; background: linear-gradient(135deg, #512da8 0%, #673ab7 100%);">';
        html += '<div class="card-icon"><i class="glyphicon glyphicon-time"></i></div>';
        html += '<div class="card-title">Tiempo Relavera</div>';
        html += '<div class="card-value">' + tRelFmt + '</div>';
        html += '<div class="card-detail">Promedio Entrada vs Salida</div></div></div>';

        html += '</div>';

        /* ========== Monitor Operativo - Solo día actual + botón Actualizar ========== */
        var mh = d.monitor_hoy || {};
        html += '<div class="panel panel-default" style="margin-top: 35px; margin-bottom: 25px;">';
        html += '<div class="panel-heading" style="background: #2C5D94; color: white;">';
        html += '<strong><i class="glyphicon glyphicon-dashboard"></i> Monitor Operativo</strong>';
        html += ' <span style="font-size:11px; opacity:0.9;">(solo día actual)</span>';
        html += ' <button type="button" id="btnActualizarMonitor" class="btn btn-sm btn-default pull-right" style="margin-top:-3px;" title="Actualizar solo esta tabla (día actual)"><i class="glyphicon glyphicon-refresh"></i> Actualizar</button>';
        html += '</div>';
        html += '<div class="panel-body"><table class="monitor-table table table-bordered"><thead><tr><th>Estado</th><th>Cantidad</th><th>Descripción</th></tr></thead>';
        var monitorTipos = [
            { tipo: 'total_turnos', titulo: 'Total turnos', cant: mh.total_turnos || 0, desc: 'Activos + inactivos (solo día actual)' },
            { tipo: 'turnos_pendientes', titulo: 'Turnos pendientes', cant: mh.turnos_pendientes || 0, desc: 'Manifiestos generados que no han llegado a la relavera' },
            { tipo: 'garita_in', titulo: 'Garita IN', cant: mh.garita_in || 0, desc: 'Manifiestos que están dentro de la relavera' },
            { tipo: 'aprobados', titulo: 'Aprobados', cant: mh.aprobados || 0, desc: 'Aprobados por técnico validador' },
            { tipo: 'garita_out', titulo: 'Garita OUT', cant: mh.garita_out || 0, desc: 'Manifiestos que ya han salido de la relavera' },
            { tipo: 'turnos_anulados', titulo: 'Turnos anulados', cant: mh.turnos_anulados || 0, desc: 'Manifiestos anulados o rechazados' }
        ];
        html += '<tbody id="monitorOperativoTbody">';
        for (var mi = 0; mi < monitorTipos.length; mi++) {
            var mt = monitorTipos[mi];
            var tdCant = '<td class="monitor-cantidad-click" data-tipo="' + mt.tipo + '" data-titulo="' + (mt.titulo || '').replace(/"/g, '&quot;') + '" title="Clic para ver detalle">' + mt.cant + '</td>';
            html += '<tr><td>' + mt.titulo + '</td>' + tdCant + '<td>' + mt.desc + '</td></tr>';
        }
        html += '</tbody></table></div></div>';

        /* ========== Clientes sin saldo suficiente - lista desplegable (arriba de Proyección) ========== */
        if (d.alertas && d.alertas.length > 0) {
            html += '<div class="panel panel-danger" style="margin-top: 28px; margin-bottom: 20px;">';
            html += '<div class="panel-heading" style="cursor: pointer; padding: 10px 15px;" data-toggle="collapse" data-target="#collapseAlertas" aria-expanded="false">';
            html += '<strong><i class="glyphicon glyphicon-exclamation-sign"></i> Clientes sin saldo suficiente para generar turno</strong>';
            html += ' <span class="badge" style="margin-left: 8px;">' + d.alertas.length + '</span>';
            html += ' <i class="glyphicon glyphicon-chevron-down pull-right" style="font-size: 12px; margin-top: 2px;"></i>';
            html += '</div>';
            html += '<div id="collapseAlertas" class="panel-collapse collapse">';
            html += '<div class="panel-body"><table class="monitor-table table table-bordered table-condensed">';
            html += '<thead><tr><th>Cliente</th><th>Planta</th><th>Saldo</th></tr></thead><tbody>';
            for (var i = 0; i < d.alertas.length; i++) {
                var a = d.alertas[i];
                var sFinal = Math.max(0, parseFloat(a.saldo) || 0);
                html += '<tr><td>' + (a.cliente || '') + '</td><td>' + (a.planta || '') + '</td><td>USD ' + fmtMoneda(a.saldo) + '</td></tr>';
            }
            html += '</tbody></table></div></div></div>';
        }

        // Proyección consumo anticipo - ejecutiva, compacta
        var proy = d.proyeccion || [];
        var sortedProy = proy.slice().sort(function(a, b) { return (parseFloat(a.dias_estimados) || 9999) - (parseFloat(b.dias_estimados) || 9999); });
        var criticos = 0, riesgo = 0, sumaDias = 0, totalConDias = 0;
        for (var i = 0; i < sortedProy.length; i++) {
            var dias = parseFloat(sortedProy[i].dias_estimados) || 9999;
            if (dias < 3) criticos++;
            else if (dias < 5) riesgo++;
            if (dias < 9999) { sumaDias += dias; totalConDias++; }
        }
        var promDias = totalConDias > 0 ? (sumaDias / totalConDias).toFixed(1) : '0';
        var visible = 10;
        var totalProy = sortedProy.length;
        function formatearDiasEstimados(p) {
            var dias = parseFloat(p.dias_estimados);
            var saldo = parseFloat(p.saldo_actual) || 0;
            var prom = parseFloat(p.promedio_diario) || 0;
            var diasExactos = (prom > 0) ? (saldo / prom) : null;
            var tooltip = '';
            if (dias >= 9999 || dias === null || isNaN(dias)) {
                tooltip = 'Sin datos de consumo. Saldo USD ' + fmtMoneda(saldo);
                return { text: 'N/A', tdClass: '', rowClass: '', tooltip: tooltip };
            }
            tooltip = 'Saldo USD ' + fmtMoneda(saldo) + ' / Promedio USD ' + fmtMoneda(prom) + ' = ' + (diasExactos !== null ? diasExactos.toFixed(3) : '0') + ' días';
            if (dias < 0.5) {
                return { text: 'HOY', tdClass: 'dias-hoy', rowClass: 'fila-hoy', tooltip: tooltip };
            }
            if (dias < 1) {
                return { text: '1 día', tdClass: 'dias-rojo', rowClass: '', tooltip: tooltip };
            }
            if (dias < 2) {
                return { text: dias.toFixed(1) + ' días', tdClass: 'dias-rojo', rowClass: '', tooltip: tooltip };
            }
            if (dias <= 5) {
                return { text: dias.toFixed(1) + ' días', tdClass: 'dias-naranja', rowClass: '', tooltip: tooltip };
            }
            return { text: dias.toFixed(1) + ' días', tdClass: 'dias-verde', rowClass: '', tooltip: tooltip };
        }
        function filaProyeccion(p) {
            var badge = p.semaf === 'rojo' ? '<span class="badge-critico">CRÍTICO</span>' : (p.semaf === 'amarillo' ? '<span class="badge-atencion">ATENCIÓN</span>' : '<span class="badge-estable">ESTABLE</span>');
            var fmt = formatearDiasEstimados(p);
            var tdClass = fmt.tdClass ? ' class="' + fmt.tdClass + '"' : '';
            var rowClass = fmt.rowClass ? ' class="' + fmt.rowClass + '"' : '';
            var titleAttr = fmt.tooltip ? ' title="' + fmt.tooltip.replace(/"/g, '&quot;') + '"' : '';
            var sActual = Math.max(0, parseFloat(p.saldo_actual) || 0);
            return '<tr' + rowClass + '><td>' + (p.cliente || '') + '</td><td>' + (p.planta || '') + '</td><td>USD ' + fmtMoneda(p.saldo_actual) + '</td><td>USD ' + fmtMoneda(p.promedio_diario) + '</td><td' + tdClass + titleAttr + '>' + fmt.text + '</td><td>' + badge + '</td></tr>';
        }
        var filasVisibles = '', filasOcultas = '';
        for (var k = 0; k < totalProy; k++) {
            var row = filaProyeccion(sortedProy[k]);
            if (k < visible) filasVisibles += row; else filasOcultas += row;
        }
        html += '<div class="panel panel-default seccion-proyeccion">';
        html += '<div class="panel-heading" style="background: #2C5D94; color: white;"><strong><i class="glyphicon glyphicon-tasks"></i> Proyección Consumo Anticipo</strong></div>';
        html += '<div class="panel-body">';
        html += '<div class="proyeccion-mini-kpis">';
        html += '<div class="proyeccion-mini-kpi kpi-critico"><span class="kpi-valor">' + criticos + '</span> Clientes críticos (0-3 días)</div>';
        html += '<div class="proyeccion-mini-kpi kpi-riesgo"><span class="kpi-valor">' + riesgo + '</span> Clientes en riesgo (3-5 días)</div>';
        html += '<div class="proyeccion-mini-kpi kpi-promedio"><span class="kpi-valor">' + promDias + '</span> Promedio general días restantes</div>';
        html += '</div>';
        html += '<table class="monitor-table table table-bordered"><thead><tr><th>Cliente</th><th>Planta</th><th>Saldo Actual</th><th>Prom. Diario</th><th>Días Estimados</th><th>Estado</th></tr></thead><tbody id="proyeccionTbody" class="proyeccion-tbody">';
        html += totalProy > 0 ? filasVisibles : '<tr><td colspan="6" style="text-align: center;">Sin datos de proyección</td></tr>';
        html += '</tbody></table>';
        if (totalProy > visible) {
            html += '<div style="margin-top: 12px; text-align: center;">';
            html += '<button type="button" id="btnVerTodosProyeccion" class="btn btn-default btn-sm">Ver todos los clientes (' + totalProy + ')</button>';
            html += '</div>';
        }
        html += '</div></div>';

        $cont.html(html);

        /* Botón Ver todos - Proyección */
        $cont.find('#btnVerTodosProyeccion').on('click', function() {
            var $btn = $(this);
            var $tbody = $cont.find('#proyeccionTbody');
            if ($tbody.data('expandido')) {
                var filasIniciales = $tbody.data('filas-iniciales');
                $tbody.html(filasIniciales);
                $btn.text('Ver todos los clientes (' + totalProy + ')');
                $tbody.data('expandido', false);
            } else {
                $tbody.data('filas-iniciales', $tbody.html());
                $tbody.html(filasVisibles + filasOcultas);
                $btn.text('Ver solo los 10 más críticos');
                $tbody.data('expandido', true);
            }
        });

        /* Toggle chevron en lista desplegable de alertas */
        $cont.find('#collapseAlertas').on('show.bs.collapse', function() {
            $(this).prev().find('.glyphicon-chevron-down').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
        }).on('hide.bs.collapse', function() {
            $(this).prev().find('.glyphicon-chevron-up').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
        });
    }

    /**
     * Actualizar datos: recarga el contenedor vía AJAX (sin recargar página completa)
     */
    function actualizarDatos() {
        cargarDashboard();
    }

    /**
     * Actualizar solo la sección Monitor Operativo (mismo rango de fechas que KPIs)
     */
    function actualizarMonitorOperativo() {
        var $tbody = $('#monitorOperativoTbody');
        var $btn = $('#btnActualizarMonitor');
        if ($tbody.length === 0) return;
        $btn.prop('disabled', true).find('i').addClass('glyphicon-spin');
        $.ajax({
            url: DASHBOARD_URL,
            type: 'GET',
            data: {
                getMonitorOnly: 1,
                Pla_Cod: $('#selPlanta').val() || '',
                Cli_Cod: $('#selCliente').val() || '',
                Man_Tip: $('#selEstado').val() || ''
            },
            dataType: 'json',
            cache: false
        }).done(function(r) {
            if (r && r.success && r.monitor_hoy) {
                var mh = r.monitor_hoy;
                var monitorTipos = [
                    { tipo: 'total_turnos', titulo: 'Total turnos', cant: mh.total_turnos || 0, desc: 'Activos + inactivos (solo día actual)' },
                    { tipo: 'turnos_pendientes', titulo: 'Turnos pendientes', cant: mh.turnos_pendientes || 0, desc: 'Manifiestos generados que no han llegado a la relavera' },
                    { tipo: 'garita_in', titulo: 'Garita IN', cant: mh.garita_in || 0, desc: 'Manifiestos que están dentro de la relavera' },
                    { tipo: 'aprobados', titulo: 'Aprobados', cant: mh.aprobados || 0, desc: 'Aprobados por técnico validador' },
                    { tipo: 'garita_out', titulo: 'Garita OUT', cant: mh.garita_out || 0, desc: 'Manifiestos que ya han salido de la relavera' },
                    { tipo: 'turnos_anulados', titulo: 'Turnos anulados', cant: mh.turnos_anulados || 0, desc: 'Manifiestos anulados o rechazados' }
                ];
                var rows = '';
                for (var mi = 0; mi < monitorTipos.length; mi++) {
                    var mt = monitorTipos[mi];
                    var tdCant = '<td class="monitor-cantidad-click" data-tipo="' + mt.tipo + '" data-titulo="' + (mt.titulo || '').replace(/"/g, '&quot;') + '" title="Clic para ver detalle">' + mt.cant + '</td>';
                    rows += '<tr><td>' + mt.titulo + '</td>' + tdCant + '<td>' + mt.desc + '</td></tr>';
                }
                $tbody.html(rows);
            }
        }).always(function() {
            $btn.prop('disabled', false).find('i').removeClass('glyphicon-spin');
        });
    }

    // Inicialización al cargar la página
    $(document).ready(function() {
        cargarDashboard();

        $('#btnActualizar').on('click', function() {
            $(this).prop('disabled', true).find('i').addClass('glyphicon-spin');
            actualizarDatos();
            setTimeout(function() {
                $('#btnActualizar').prop('disabled', false).find('i').removeClass('glyphicon-spin');
            }, 500);
        });

        $('#btnFiltrar').on('click', function() {
            actualizarDatos();
        });

        /* Botón Actualizar del Monitor Operativo: solo actualiza esa sección */
        $(document).on('click', '#btnActualizarMonitor', function() {
            actualizarMonitorOperativo();
        });

        /* Clic en cantidad del Monitor: abrir modal con manifiestos del día */
        $(document).on('click', '.monitor-cantidad-click', function() {
            var tipo = $(this).data('tipo');
            var titulo = $(this).data('titulo') || 'Manifiestos';
            abrirModalManifiestos(tipo, titulo);
        });
    });

    /**
     * Ordena manifiestos para la tabla (fecha o plantero)
     */
    function contarVistos(man) {
        var n = 0;
        if (man.Man_Tip_1) n++;
        if (man.Man_Tip_2) n++;
        if (man.Man_Tip_3) n++;
        if (man.Man_Tip_4) n++;
        if (man.Man_Tip_5) n++;
        return n;
    }

    function ordenarManifiestosParaTabla(manifiestos, tipo) {
        if (!manifiestos || manifiestos.length === 0) return manifiestos;
        var copia = manifiestos.slice();
        if (tipo === 'vistos') {
            copia.sort(function(a, b) {
                var vistosA = contarVistos(a);
                var vistosB = contarVistos(b);
                if (vistosA !== vistosB) return vistosA - vistosB;
                var fechaA = (a.Man_Fec || '').toString();
                var fechaB = (b.Man_Fec || '').toString();
                return fechaA.localeCompare(fechaB) || ((a.ManNum || '').localeCompare(b.ManNum || ''));
            });
        } else if (tipo === 'plantero') {
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
                return fechaA.localeCompare(fechaB) || ((a.ManNum || '').localeCompare(b.ManNum || ''));
            });
        } else {
            copia.sort(function(a, b) {
                var fechaA = (a.Man_Fec || '').toString();
                var fechaB = (b.Man_Fec || '').toString();
                return fechaA.localeCompare(fechaB) || ((a.ManNum || '').localeCompare(b.ManNum || ''));
            });
        }
        return copia;
    }

    /**
     * Abre el modal de manifiestos y carga vía AJAX según tipo
     */
    function abrirModalManifiestos(tipo, titulo) {
        var $loading = $('<div class="modal fade" id="modalManifiestosMonitorLoading" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content"><div class="modal-body" style="text-align: center; padding: 30px;"><i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size: 24px;"></i><p style="margin-top: 10px;">Cargando...</p></div></div></div></div>');
        $('body').append($loading);
        var $loadingModal = $('#modalManifiestosMonitorLoading');
        $loadingModal.modal('show');
        $loadingModal.on('hidden.bs.modal', function() { $(this).remove(); });

        function ocultarCargando() {
            if ($loadingModal.length) {
                $loadingModal.modal('hide');
            }
        }

        $.ajax({
            url: DASHBOARD_URL,
            type: 'GET',
            data: {
                getManifiestosMonitor: 1,
                tipo: tipo,
                Pla_Cod: $('#selPlanta').val() || '',
                Cli_Cod: $('#selCliente').val() || ''
            },
            dataType: 'json',
            cache: false,
            timeout: 15000
        }).done(function(r) {
            if (!r || !r.success) {
                alert(r && r.message ? r.message : 'Error al cargar los manifiestos.');
                return;
            }
            var manifiestos = r.manifiestos || [];
            var fecha = r.fecha || '';
            var tituloModal = 'Manifiestos del día - ' + fecha;
            if (manifiestos.length === 0) {
                var htmlVacio = '<div class="modal fade" id="modalManifiestosMonitor" tabindex="-1"><div class="modal-dialog modal-manifiestos-relavera"><div class="modal-content"><div class="modal-header" style="background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white; padding: 8px 15px;"><button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;">&times;</button><h4 class="modal-title" style="font-size: 14px; margin: 0;">' + tituloModal + '</h4></div><div class="modal-body"><div class="alert alert-info">No hay manifiestos en este estado.</div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button></div></div></div></div>';
                $('#modalManifiestosMonitor').remove();
                $('body').append(htmlVacio);
                $('#modalManifiestosMonitor').modal('show').on('hidden.bs.modal', function() { $(this).remove(); });
                return;
            }
            datosModalManifiestosRelavera = { manifiestos: manifiestos, titulo: tituloModal, tipo: tipo };
            var mostrarHorario = (manifiestos.length > 0 && manifiestos[0].horario_turno);
            var ordenados = ordenarManifiestosParaTabla(manifiestos, 'vistos'); var html = '';
            html += '<div class="modal fade" id="modalManifiestosMonitor" tabindex="-1" role="dialog">';
            html += '<div class="modal-dialog modal-manifiestos-relavera" role="document">';
            html += '<div class="modal-content" style="font-size: 11px;">';
            html += '<div class="modal-header" style="background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white; padding: 8px 15px;">';
            html += '<button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;">&times;</button>';
            html += '<h4 class="modal-title" style="font-size: 14px; margin: 0; line-height: 1.2;"><i class="glyphicon glyphicon-calendar"></i> ' + tituloModal + '</h4></div>';
            html += '<div class="modal-body" style="font-size: 11px; padding-top: 10px;">';
            html += '<ul class="nav nav-tabs" role="tablist" style="margin-bottom: 12px;">';
            html += '<li role="presentation" class="active"><a href="#tabManifRelaveraDetalle" aria-controls="tabManifRelaveraDetalle" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-list-alt"></i> Detallado</a></li>';
            html += '<li role="presentation"><a href="#tabManifRelaveraPlanta" aria-controls="tabManifRelaveraPlanta" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-tasks"></i> Por Planta</a></li></ul>';
            html += '<div class="tab-content">';
            html += '<div role="tabpanel" class="tab-pane active" id="tabManifRelaveraDetalle">';
            html += '<div class="no-print" style="margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">';
            html += '<div style="display: flex; align-items: center; gap: 8px;"><label style="margin: 0; font-weight: normal;">Ordenar por:</label>';
            html += '<select id="ordenManifRelavera" class="form-control input-sm" style="width: auto; display: inline-block;" onchange="aplicarOrdenManifiestosRelavera(this.value);">';
            html += '<option value="vistos" selected>Por vistos (0→3)</option><option value="fecha">Por fecha</option><option value="plantero">Por plantero</option></select></div>';
            html += '<div><button type="button" class="btn btn-primary btn-sm" onclick="imprimirModalManifiestosRelavera();" style="margin-right: 5px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>';
            html += '<button type="button" class="btn btn-success btn-sm" onclick="exportModalManifiestosExcelRelavera(\'detallado\');" style="margin-right: 5px; background: linear-gradient(135deg, #28a745 0%, #218838 100%); border-color: #1e7e34;"><i class="glyphicon glyphicon-export"></i> Excel</button>';
            html += '<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button></div></div>';
            html += '<table class="table table-bordered table-striped table-condensed tabla-modal-manifiestos" style="font-size: 11px;"><thead><tr>';
            html += '<th style="width: 40px;">#</th><th>No. Manifiesto</th><th>Fecha</th>';
            if (mostrarHorario) html += '<th>Horario</th>';
            html += '<th>Cliente</th><th>Planta</th><th>Placa</th><th>Chofer</th>';
            html += '<th><i class="glyphicon glyphicon-log-in" style="color: #ffc107;" title="Garita In"></i></th>';
            html += '<th><i class="glyphicon glyphicon-ok-circle" style="color: #28a745;" title="Aprobado"></i></th>';
            html += '<th><i class="glyphicon glyphicon-log-out" style="color: #17a2b8;" title="Garita Out"></i></th>';
            html += '<th><i class="glyphicon glyphicon-file" style="color: #007bff;" title="Facturado"></i></th>';
            html += '<th><i class="glyphicon glyphicon-remove-circle" style="color: #dc3545;" title="Rechazado"></i></th></tr></thead><tbody id="tbodyManifRelaveraDetalle">';
            ordenados.forEach(function(man, index) {
                html += '<tr><td style="text-align: center; font-weight: bold;">' + (index + 1) + '</td><td>' + (man.ManNum || '') + '</td><td>' + (man.Man_Fec || '') + '</td>';
                if (mostrarHorario) html += '<td>' + (man.horario_turno || '') + '</td>';
                html += '<td>' + (man.Cliente || '') + '</td><td>' + (man.Pla_Nom || '') + '</td><td>' + (man.Veh_Pla || '') + '</td><td>' + (man.chofer_nombre || '') + '</td>';
                html += '<td>' + (man.Man_Tip_1 === 'GE' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Entrada a Garita"></i>' : '') + '</td>';
                html += '<td>' + (man.Man_Tip_2 === 'A' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Aprobacion del Tecnico"></i>' : '') + '</td>';
                html += '<td>' + (man.Man_Tip_3 === 'GS' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Salida de Garita"></i>' : '') + '</td>';
                html += '<td>' + (man.Man_Tip_4 === 'F' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Facturado"></i>' : '') + '</td>';
                html += '<td>' + (man.Man_Tip_5 === 'R' ? '<i class="glyphicon glyphicon-ok" style="color: #dc3545; font-size: 16px;" title="Rechazado"></i>' : '') + '</td></tr>';
            });
            html += '</tbody></table></div>';
            var porPlantaCliente = {}; manifiestos.forEach(function(man) {
                var planta = man.Pla_Nom || '(Sin planta)'; var cliente = man.Cliente || '(Sin cliente)'; var key = planta + '\u0001' + cliente;
                if (!porPlantaCliente[key]) porPlantaCliente[key] = { planta: planta, cliente: cliente, cantidad: 0 }; porPlantaCliente[key].cantidad++;
            });
            var filas = Object.keys(porPlantaCliente).map(function(k) { return porPlantaCliente[k]; }); filas.sort(function(a, b) { return (b.cantidad || 0) - (a.cantidad || 0); });
            html += '<div role="tabpanel" class="tab-pane" id="tabManifRelaveraPlanta">';
            html += '<div class="no-print" style="margin-bottom: 10px; text-align: right;">';
            html += '<button type="button" class="btn btn-primary btn-sm" onclick="imprimirModalManifiestosRelavera();" style="margin-right: 5px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>';
            html += '<button type="button" class="btn btn-success btn-sm" onclick="exportModalManifiestosExcelRelavera(\'planta\');" style="margin-right: 5px; background: linear-gradient(135deg, #28a745 0%, #218838 100%); border-color: #1e7e34;"><i class="glyphicon glyphicon-export"></i> Excel</button>';
            html += '<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button></div>';
            html += '<table class="table table-bordered table-striped table-condensed" style="font-size: 11px;"><thead><tr><th style="width: 40px;">#</th><th>Planta</th><th>Cliente</th><th style="width: 120px; text-align: center;">Cantidad Manifiestos</th><th style="width: 100px; text-align: center;">% Participación</th></tr></thead><tbody>';
            var totalMan = filas.reduce(function(s, f) { return s + (f.cantidad || 0); }, 0);
            filas.forEach(function(item, index) {
                var pct = totalMan > 0 ? ((item.cantidad / totalMan) * 100).toFixed(2) : '0.00';
                html += '<tr><td style="text-align: center; font-weight: bold;">' + (index + 1) + '</td><td>' + item.planta + '</td><td>' + item.cliente + '</td><td style="text-align: center;"><strong>' + item.cantidad + '</strong></td><td style="text-align: center;">' + pct + '%</td></tr>';
            });
            html += '</tbody><tfoot><tr style="background-color: #2C5D94; color: white; font-weight: bold;"><td colspan="3" style="text-align: right; padding-right: 15px;">TOTAL:</td><td style="text-align: center;">' + totalMan + '</td><td style="text-align: center;">100%</td></tr></tfoot></table></div></div></div>';
            html += '</div><div class="modal-footer">';
            html += '<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button></div></div></div></div>';
            $('#modalManifiestosMonitor').remove();
            $('body').append(html);
            $('#modalManifiestosMonitor').modal('show').on('hidden.bs.modal', function() { datosModalManifiestosRelavera = null; $(this).remove(); });
        }).fail(function(xhr, status, err) {
            var msg = 'Error al cargar los manifiestos.';
            if (status === 'timeout') msg = 'La solicitud tardó demasiado. Intente de nuevo.';
            else if (xhr && xhr.responseText) {
                try {
                    var j = JSON.parse(xhr.responseText);
                    if (j && j.message) msg = j.message;
                } catch (e) {}
            }
            alert(msg);
        }).always(function() {
            ocultarCargando();
        });
    }

    window.ordenarManifiestosParaTabla = ordenarManifiestosParaTabla;

})(jQuery);

var datosModalManifiestosRelavera = null;

function aplicarOrdenManifiestosRelavera(tipo) {
    if (!datosModalManifiestosRelavera || !datosModalManifiestosRelavera.manifiestos) return;
    var ordenados = (typeof window.ordenarManifiestosParaTabla === 'function') ? window.ordenarManifiestosParaTabla(datosModalManifiestosRelavera.manifiestos, tipo) : datosModalManifiestosRelavera.manifiestos;
    var manifiestos = datosModalManifiestosRelavera.manifiestos;
    var mostrarHorario = (manifiestos.length > 0 && manifiestos[0].horario_turno);
    var $tbody = $('#tbodyManifRelaveraDetalle');
    if (!$tbody.length) return;
    var html = '';
    ordenados.forEach(function(man, index) {
        html += '<tr><td style="text-align: center; font-weight: bold;">' + (index + 1) + '</td><td>' + (man.ManNum || '') + '</td><td>' + (man.Man_Fec || '') + '</td>';
        if (mostrarHorario) html += '<td>' + (man.horario_turno || '') + '</td>';
        html += '<td>' + (man.Cliente || '') + '</td><td>' + (man.Pla_Nom || '') + '</td><td>' + (man.Veh_Pla || '') + '</td><td>' + (man.chofer_nombre || '') + '</td>';
        html += '<td>' + (man.Man_Tip_1 === 'GE' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Entrada a Garita"></i>' : '') + '</td>';
        html += '<td>' + (man.Man_Tip_2 === 'A' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Aprobacion del Tecnico"></i>' : '') + '</td>';
        html += '<td>' + (man.Man_Tip_3 === 'GS' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Salida de Garita"></i>' : '') + '</td>';
        html += '<td>' + (man.Man_Tip_4 === 'F' ? '<i class="glyphicon glyphicon-ok" style="color: #28a745; font-size: 16px;" title="Facturado"></i>' : '') + '</td>';
        html += '<td>' + (man.Man_Tip_5 === 'R' ? '<i class="glyphicon glyphicon-ok" style="color: #dc3545; font-size: 16px;" title="Rechazado"></i>' : '') + '</td></tr>';
    });
    $tbody.html(html);
}

function imprimirModalManifiestosRelavera() {
    var $modal = $('#modalManifiestosMonitor');
    if (!$modal.length) return;
    var $titulo = $modal.find('.modal-title');
    var encabezado = $titulo.length ? '<div style="background:#2C5D94;color:white;padding:10px 15px;margin-bottom:12px;font-size:14px;font-weight:bold;">' + ($titulo.text() || $titulo.html() || 'Manifiestos').trim() + '</div>' : '';
    var $activo = $modal.find('.tab-pane.active');
    var contenido = $activo.length ? $activo.clone() : $modal.find('.modal-body').clone();
    contenido.find('.no-print').remove();
    var ventana = window.open('', '_blank', 'width=800,height=600');
    ventana.document.write('<html><head><title>Manifiestos</title><link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"></head><body style="padding:15px;">' + encabezado + contenido.prop('outerHTML') + '</body></html>');
    ventana.document.close();
    ventana.focus();
    ventana.print();
    ventana.close();
}

function exportModalManifiestosExcelRelavera(tipo) {
    if (!datosModalManifiestosRelavera || !datosModalManifiestosRelavera.manifiestos) { alert('No hay datos para exportar.'); return; }
    var manifiestos = datosModalManifiestosRelavera.manifiestos;
    var titulo = datosModalManifiestosRelavera.titulo || 'Manifiestos';
    var nombre = 'manifiestos_relavera_' + (new Date().toISOString().slice(0,10));
    var html = '', hoja = 'Manifiestos';
    if (tipo === 'planta') {
        var porPlantaCliente = {}; manifiestos.forEach(function(man) {
            var planta = man.Pla_Nom || '(Sin planta)'; var cliente = man.Cliente || '(Sin cliente)'; var key = planta + '\u0001' + cliente;
            if (!porPlantaCliente[key]) porPlantaCliente[key] = { planta: planta, cliente: cliente, cantidad: 0 }; porPlantaCliente[key].cantidad++;
        });
        var filas = Object.keys(porPlantaCliente).map(function(k) { return porPlantaCliente[k]; }); filas.sort(function(a, b) { return (b.cantidad || 0) - (a.cantidad || 0); });
        var totalMan = filas.reduce(function(s, f) { return s + (f.cantidad || 0); }, 0);
        html = '<table border="1" cellpadding="3" style="border-collapse:collapse;">';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td colspan="5" style="padding:8px;text-align:center;">' + titulo + ' - Por Planta</td></tr>';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td>#</td><td>Planta</td><td>Cliente</td><td style="text-align:center;">Cantidad</td><td style="text-align:center;">% Participación</td></tr>';
        filas.forEach(function(item, index) {
            var pct = totalMan > 0 ? ((item.cantidad / totalMan) * 100).toFixed(2) : '0.00';
            html += '<tr><td>' + (index + 1) + '</td><td>' + item.planta + '</td><td>' + item.cliente + '</td><td style="text-align:center;">' + item.cantidad + '</td><td style="text-align:center;">' + pct + '%</td></tr>';
        });
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td colspan="3" style="text-align:right;padding-right:10px;">TOTAL</td><td style="text-align:center;">' + totalMan + '</td><td style="text-align:center;">100%</td></tr></table>';
        hoja = 'Manifiestos Por Planta';
    } else {
        var ordenActual = ($('#ordenManifRelavera').val() || 'vistos');
        var ordenados = (typeof window.ordenarManifiestosParaTabla === 'function') ? window.ordenarManifiestosParaTabla(manifiestos, ordenActual) : manifiestos;
        var mostrarHorario = (manifiestos.length > 0 && manifiestos[0].horario_turno);
        html = '<table border="1" cellpadding="3" style="border-collapse:collapse;">';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td colspan="' + (mostrarHorario ? 12 : 11) + '" style="padding:8px;text-align:center;">' + titulo + ' - Detallado</td></tr>';
        html += '<tr style="background:#2C5D94;color:white;font-weight:bold;"><td>#</td><td>No. Manifiesto</td><td>Fecha</td>';
        if (mostrarHorario) html += '<td>Horario</td>';
        html += '<td>Cliente</td><td>Planta</td><td>Placa</td><td>Chofer</td><td>GE</td><td>A</td><td>GS</td><td>F</td><td>R</td></tr>';
        ordenados.forEach(function(man, i) {
            html += '<tr><td>' + (i + 1) + '</td><td>' + (man.ManNum || '') + '</td><td>' + (man.Man_Fec || '') + '</td>';
            if (mostrarHorario) html += '<td>' + (man.horario_turno || '') + '</td>';
            html += '<td>' + (man.Cliente || '') + '</td><td>' + (man.Pla_Nom || '') + '</td><td>' + (man.Veh_Pla || '') + '</td><td>' + (man.chofer_nombre || '') + '</td>';
            html += '<td>' + (man.Man_Tip_1 === 'GE' ? 'Sí' : '') + '</td><td>' + (man.Man_Tip_2 === 'A' ? 'Sí' : '') + '</td><td>' + (man.Man_Tip_3 === 'GS' ? 'Sí' : '') + '</td><td>' + (man.Man_Tip_4 === 'F' ? 'Sí' : '') + '</td><td>' + (man.Man_Tip_5 === 'R' ? 'Sí' : '') + '</td></tr>';
        });
        html += '</table>';
        hoja = 'Manifiestos Detallado';
    }
    var form = $('<form>', { method: 'POST', action: '../../Librerias/exportar/ficheroExcel.php', target: '_blank' });
    form.append($('<input>', { type: 'hidden', name: 'datos_a_enviar', value: html }));
    form.append($('<input>', { type: 'hidden', name: 'nombre', value: nombre }));
    form.append($('<input>', { type: 'hidden', name: 'hoja', value: hoja }));
    $('body').append(form);
    form.submit();
    form.remove();
}
