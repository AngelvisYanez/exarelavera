<?php
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_compras.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Comt($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Comt;

$hoy = date('Y-m-d');
$inicioAnio = date('Y-01-01');

function dashProvNormalizarFecha($valor, $defecto) {
    $valor = trim((string) $valor);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        return $valor;
    }
    return $defecto;
}

if (isset($dashProveedoresAjax)) {
    header('Content-Type: application/json; charset=UTF-8');

    $desde = dashProvNormalizarFecha(isset($_REQUEST['desde']) ? $_REQUEST['desde'] : '', $inicioAnio);
    $hasta = dashProvNormalizarFecha(isset($_REQUEST['hasta']) ? $_REQUEST['hasta'] : '', $hoy);
    if ($desde > $hasta) {
        $tmp = $desde;
        $desde = $hasta;
        $hasta = $tmp;
    }

    $param = $Ses_Emp_Cod . '*' . $desde . '*' . $hasta;

    $topProv = $obBD_con1->getArrayConsulta(1200, $param, $obBD_conexion);
    $provCiudad = $obBD_con1->getArrayConsulta(1201, $param, $obBD_conexion);
    $topProd = $obBD_con1->getArrayConsulta(1202, $param, $obBD_conexion);
    $creditos = $obBD_con1->getArrayConsulta(1203, $param, $obBD_conexion);

    utf8_encode_deep($topProv);
    utf8_encode_deep($provCiudad);
    utf8_encode_deep($topProd);
    utf8_encode_deep($creditos);

    echo json_encode(array(
        'success' => true,
        'desde' => $desde,
        'hasta' => $hasta,
        'topProveedores' => $topProv,
        'proveedoresPorCiudad' => $provCiudad,
        'topProductos' => $topProd,
        'creditosProveedores' => $creditos
    ));
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard proveedores [EXA]</title>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .dash-wrap { padding: 12px 10px 8px; }
        .dash-card {
            background: #fff;
            border: 1px solid #e8ecf1;
            border-radius: 10px;
            margin-bottom: 18px;
            padding: 16px 18px;
            min-height: 360px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06);
        }
        .dash-card h4 {
            margin: 0 0 12px 0;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 10px;
        }
        .dash-card h4 i { color: #3b82f6; margin-right: 6px; }
        .dash-host { position: relative; width: 100%; height: 300px; }
        .dash-empty { text-align: center; color: #94a3b8; padding: 80px 10px; font-size: 13px; }
        .dash-kpi { font-size: 12px; color: #64748b; margin-bottom: 8px; font-weight: 500; }

        /* Barra de filtros */
        .dash-filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 14px 20px;
            margin-bottom: 22px;
            padding: 18px 22px;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 55%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.07);
        }
        .dash-filter-head {
            flex: 1 1 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 2px;
        }
        .dash-filter-title {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .dash-filter-title i {
            color: #3b82f6;
            margin-right: 8px;
            font-size: 14px;
        }
        .dash-filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            min-height: 28px;
        }
        .dash-filter-badge:empty { display: none; }
        .dash-filter-badge i { font-size: 12px; opacity: 0.85; }
        .dash-filter-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 160px;
        }
        .dash-filter-field label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin: 0;
        }
        .dash-filter-input-wrap {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .dash-filter-input-wrap:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .dash-filter-input-wrap .input-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 36px;
            color: #94a3b8;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .dash-filter-input-wrap input {
            flex: 1;
            border: none !important;
            box-shadow: none !important;
            height: 36px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 500;
            color: #0f172a;
            background: transparent !important;
        }
        .dash-filter-actions {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-dash-filter {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 36px;
            padding: 0 18px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .btn-dash-filter:hover {
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.45);
            color: #fff;
        }
        .btn-dash-filter:active { transform: translateY(1px); }
        .btn-dash-preset {
            height: 36px;
            padding: 0 12px;
            font-size: 12px;
            font-weight: 500;
            color: #475569;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .btn-dash-preset:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }
        @media (max-width: 768px) {
            .dash-filter-bar { padding: 14px 16px; }
            .dash-filter-field { flex: 1 1 100%; min-width: 0; }
            .dash-filter-actions { width: 100%; }
            .btn-dash-filter { flex: 1; justify-content: center; }
        }
    </style>
</head>
<body>
<div class="panel panel-main">
    <div class="panel-heading ui-widget-header ui-corner-top exa-head">
        <h3 class="panel-title">&raquo; Dashboard de proveedores</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body dash-wrap">
        <form id="formDashProv" onsubmit="return false;">
            <div class="dash-filter-bar">
                <div class="dash-filter-head">
                    <h4 class="dash-filter-title"><i class="fa fa-sliders"></i> Periodo de consulta</h4>
                    <span id="dashRangoRef" class="dash-filter-badge"></span>
                </div>
                <div class="dash-filter-field">
                    <label for="dashDesde">Desde</label>
                    <div class="dash-filter-input-wrap">
                        <span class="input-icon"><i class="fa fa-calendar"></i></span>
                        <input type="text" id="dashDesde" name="desde" class="datepickers" value="<?php echo $inicioAnio; ?>" placeholder="AAAA-MM-DD" autocomplete="off" />
                    </div>
                </div>
                <div class="dash-filter-field">
                    <label for="dashHasta">Hasta</label>
                    <div class="dash-filter-input-wrap">
                        <span class="input-icon"><i class="fa fa-calendar"></i></span>
                        <input type="text" id="dashHasta" name="hasta" class="datepickers" value="<?php echo $hoy; ?>" placeholder="AAAA-MM-DD" autocomplete="off" />
                    </div>
                </div>
                <div class="dash-filter-actions">
                    <button type="button" class="btn-dash-preset" data-preset="mes" title="Mes en curso">Mes actual</button>
                    <button type="button" class="btn-dash-preset" data-preset="anio" title="A&ntilde;o en curso">A&ntilde;o actual</button>
                    <button type="button" id="btnDashProvBuscar" class="btn-dash-filter" title="Aplicar filtro">
                        <i class="glyphicon glyphicon-search"></i> Aplicar
                    </button>
                </div>
            </div>
        </form>

        <div class="row">
            <div class="col-md-6 col-sm-12">
                <div class="dash-card">
                    <h4><i class="fa fa-line-chart"></i> Proveedores a los que m&aacute;s compro</h4>
                    <div id="kpiTopProv" class="dash-kpi"></div>
                    <div id="hostTopProv" class="dash-host"><canvas id="chartTopProv"></canvas></div>
                </div>
            </div>
            <div class="col-md-6 col-sm-12">
                <div class="dash-card">
                    <h4><i class="fa fa-map-marker"></i> Proveedores por ciudad</h4>
                    <div id="kpiCiudad" class="dash-kpi"></div>
                    <div id="hostCiudad" class="dash-host"><canvas id="chartCiudad"></canvas></div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-sm-12">
                <div class="dash-card">
                    <h4><i class="fa fa-cubes"></i> Productos m&aacute;s comprados a proveedores</h4>
                    <div id="kpiTopProd" class="dash-kpi"></div>
                    <div id="hostTopProd" class="dash-host"><canvas id="chartTopProd"></canvas></div>
                </div>
            </div>
            <div class="col-md-6 col-sm-12">
                <div class="dash-card">
                    <h4><i class="fa fa-credit-card"></i> Cr&eacute;ditos otorgados por proveedores</h4>
                    <div id="kpiCreditos" class="dash-kpi"></div>
                    <div id="hostCreditos" class="dash-host"><canvas id="chartCreditos"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function () {
    var chartTopProv = null;
    var chartCiudad = null;
    var chartTopProd = null;
    var chartCreditos = null;

    function destroyCharts() {
        [chartTopProv, chartCiudad, chartTopProd, chartCreditos].forEach(function (c) {
            if (c && typeof c.destroy === 'function') c.destroy();
        });
        chartTopProv = chartCiudad = chartTopProd = chartCreditos = null;
    }

    var LABEL_MAX_PROVEEDOR = 28;
    var LABEL_MAX_CIUDAD = 22;
    var LABEL_MAX_PRODUCTO = 32;

    function formatMoney(v) {
        var n = parseFloat(v || 0);
        return n.toLocaleString('es-EC', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function truncateLabel(text, maxLen) {
        var s = String(text || '').trim();
        if (!s) return '(Sin dato)';
        maxLen = maxLen || 28;
        if (s.length <= maxLen) return s;
        return s.substring(0, maxLen - 1).trim() + '\u2026';
    }

    function paintEmpty(hostId, msg) {
        $('#' + hostId).html('<div class="dash-empty">' + msg + '</div>');
    }

    function resetHost(hostId, canvasId) {
        $('#' + hostId).html('<canvas id="' + canvasId + '"></canvas>');
        return document.getElementById(canvasId).getContext('2d');
    }

    function createHorizontalBar(ctx, labels, fullLabels, data, color, titleLabel, formatValue) {
        fullLabels = fullLabels || labels;
        formatValue = formatValue || function (v) { return v; };
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: titleLabel,
                    data: data,
                    backgroundColor: color,
                    borderColor: color,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            title: function (items) {
                                if (!items.length) return '';
                                var i = items[0].dataIndex;
                                return fullLabels[i] || labels[i] || '';
                            },
                            label: function (ctx) {
                                return titleLabel + ': ' + formatValue(ctx.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: { beginAtZero: true },
                    y: {
                        ticks: {
                            autoSkip: false,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }

    function renderBarChart(hostId, canvasId, kpiId, rows, labelField, valueField, color, titleLabel, emptyMsg, kpiFormatter, maxLabelLen, formatValue) {
        if (!rows.length) {
            paintEmpty(hostId, emptyMsg);
            $('#' + kpiId).text('');
            return null;
        }
        var labels = [];
        var fullLabels = [];
        var data = [];
        var kpiExtra = 0;
        rows.forEach(function (r) {
            var full = r[labelField] || '(Sin dato)';
            fullLabels.push(full);
            labels.push(truncateLabel(full, maxLabelLen));
            var val = parseFloat(r[valueField] || 0);
            data.push(val);
            kpiExtra += val;
        });
        $('#' + kpiId).text(kpiFormatter(kpiExtra, rows));
        return createHorizontalBar(resetHost(hostId, canvasId), labels, fullLabels, data, color, titleLabel, formatValue);
    }

    function loadDashboard() {
        var params = {
            dashProveedoresAjax: 1,
            desde: $('#dashDesde').val(),
            hasta: $('#dashHasta').val()
        };

        $.get(UrlSaveJson, params, function (res) {
            if (!res || !res.success) {
                if (typeof $.alert === 'function') $.alert('No se pudo cargar el dashboard de proveedores.');
                return;
            }

            if (res.desde) $('#dashDesde').val(res.desde);
            if (res.hasta) $('#dashHasta').val(res.hasta);
            $('#dashRangoRef').html('<i class="fa fa-clock-o"></i> ' + res.desde + ' &mdash; ' + res.hasta);

            destroyCharts();

            chartTopProv = renderBarChart(
                'hostTopProv', 'chartTopProv', 'kpiTopProv',
                res.topProveedores || [], 'proveedor', 'monto_total',
                '#2e86de', 'Monto de compra',
                'Sin compras en el rango seleccionado.',
                function (total) { return 'Monto acumulado TOP: $ ' + formatMoney(total); },
                LABEL_MAX_PROVEEDOR,
                function (v) { return '$ ' + formatMoney(v); }
            );

            chartCiudad = renderBarChart(
                'hostCiudad', 'chartCiudad', 'kpiCiudad',
                res.proveedoresPorCiudad || [], 'ciudad', 'total_proveedores',
                '#27ae60', 'Proveedores',
                'Sin proveedores con compras en el rango.',
                function (total) { return 'Total proveedores en TOP ciudades: ' + parseInt(total, 10); },
                LABEL_MAX_CIUDAD,
                function (v) { return parseInt(v, 10); }
            );

            chartTopProd = renderBarChart(
                'hostTopProd', 'chartTopProd', 'kpiTopProd',
                res.topProductos || [], 'producto', 'cantidad_total',
                '#f39c12', 'Cantidad comprada',
                'Sin productos comprados en el rango.',
                function (total) { return 'Cantidad acumulada TOP: ' + total.toFixed(2); },
                LABEL_MAX_PRODUCTO,
                function (v) { return parseFloat(v).toFixed(2); }
            );

            chartCreditos = renderBarChart(
                'hostCreditos', 'chartCreditos', 'kpiCreditos',
                res.creditosProveedores || [], 'proveedor', 'monto_credito',
                '#8e44ad', 'Monto a credito',
                'Sin creditos otorgados (ccpp_pagar) en el rango.',
                function (total, rows) {
                    var docs = 0;
                    rows.forEach(function (r) { docs += parseInt(r.total_creditos || 0, 10); });
                    return 'Creditos TOP: $ ' + formatMoney(total) + ' (' + docs + ' documentos)';
                },
                LABEL_MAX_PROVEEDOR,
                function (v) { return '$ ' + formatMoney(v); }
            );
        }, 'json');
    }

    function pad2(n) { return (n < 10 ? '0' : '') + n; }
    function fmtDate(d) {
        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
    }
    function aplicarPreset(tipo) {
        var hoy = new Date();
        var desde, hasta = fmtDate(hoy);
        if (tipo === 'mes') {
            desde = hoy.getFullYear() + '-' + pad2(hoy.getMonth() + 1) + '-01';
        } else {
            desde = hoy.getFullYear() + '-01-01';
        }
        $('#dashDesde').val(desde);
        $('#dashHasta').val(hasta);
        loadDashboard();
    }

    if ($.fn.createDatePickers) {
        $('#dashDesde').createDatePickers({
            clean: true,
            checkAvailability: true,
            onClose: function (sd) { $('#dashHasta').datepicker('option', 'minDate', sd); }
        });
        $('#dashHasta').createDatePickers({
            clean: true,
            checkAvailability: true,
            onClose: function (sd) { $('#dashDesde').datepicker('option', 'maxDate', sd); }
        });
        var vDesde = $('#dashDesde').val();
        var vHasta = $('#dashHasta').val();
        if (vDesde) $('#dashHasta').datepicker('option', 'minDate', vDesde);
        if (vHasta) $('#dashDesde').datepicker('option', 'maxDate', vHasta);
    }
    $('.btn-dash-preset').on('click', function () {
        aplicarPreset($(this).data('preset'));
    });
    $('#btnDashProvBuscar').on('click', loadDashboard);
    $('#formDashProv').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            loadDashboard();
        }
    });
    $(loadDashboard);
})();
</script>
</body>
</html>
