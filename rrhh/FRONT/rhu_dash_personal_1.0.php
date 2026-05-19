<?php
/**
 * Dashboard resumido de personal: género y nivel de estudio.
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_personal.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_rrhh($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_rrhh;

if (isset($dashPersonalAjax)) {
    header('Content-Type: application/json; charset=UTF-8');
    $bySex = $obBD_con1->getArrayConsulta(18, $Ses_Emp_Cod, $obBD_conexion);
    $byTit = $obBD_con1->getArrayConsulta(19, $Ses_Emp_Cod, $obBD_conexion);
    $byCiu = $obBD_con1->getArrayConsulta(20, $Ses_Emp_Cod, $obBD_conexion);
    $byMov = $obBD_con1->getArrayConsulta(21, $Ses_Emp_Cod, $obBD_conexion);
    $byTac = $obBD_con1->getArrayConsulta(22, $Ses_Emp_Cod, $obBD_conexion);
    $byRso = $obBD_con1->getArrayConsulta(23, $Ses_Emp_Cod, $obBD_conexion);
    $byIng = $obBD_con1->getArrayConsulta(24, $Ses_Emp_Cod, $obBD_conexion);
    $ultRol = $obBD_con1->getRowConsultaSql(
        "SELECT rp.Rol_Cod, rp.Rol_Num, rp.Rol_Fef, rp.Rol_Fei, rp.Rol_Con
         FROM rol_pagos rp
         INNER JOIN areas_rrhh ar ON ar.Are_Cod = rp.Are_Cod
         INNER JOIN det_rpagos dr ON dr.Rol_Cod = rp.Rol_Cod
         INNER JOIN campo_rol cr ON cr.Cam_Cod = dr.Cam_Cod
             AND cr.Cam_Var IN ('total_ingr', 'total_ing')
         WHERE ar.Emp_Cod = " . intval($Ses_Emp_Cod) . " AND rp.Rol_Est = 'A'
         ORDER BY IFNULL(rp.Rol_Fef, rp.Rol_Fei) DESC, rp.Rol_Num DESC, rp.Rol_Cod DESC
         LIMIT 1",
        $obBD_conexion
    );
    utf8_encode_deep($bySex);
    utf8_encode_deep($byTit);
    utf8_encode_deep($byCiu);
    utf8_encode_deep($byMov);
    utf8_encode_deep($byTac);
    utf8_encode_deep($byRso);
    utf8_encode_deep($byIng);
    if (is_array($ultRol)) {
        utf8_encode_deep($ultRol);
    }
    $rowTotal = $obBD_con1->getArrayConsulta(25, $Ses_Emp_Cod, $obBD_conexion);
    $total = 0;
    if (!empty($rowTotal[0]['total'])) {
        $total = (int) $rowTotal[0]['total'];
    } else {
        foreach ($bySex as $r) {
            $total += (int) $r['total'];
        }
    }
    $totalProv = 0;
    foreach ($byTac as $r) {
        $totalProv += (int) $r['total'];
    }
    echo json_encode(array(
        'success' => true,
        'totalPersonal' => $total,
        'totalProveedores' => $totalProv,
        'bySex' => $bySex,
        'byTit' => $byTit,
        'byCiu' => $byCiu,
        'byMov' => $byMov,
        'byTac' => $byTac,
        'byRso' => $byRso,
        'byIng' => $byIng,
        'ultimoRol' => $ultRol ? $ultRol : array(),
    ));
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard personal [EXA]</title>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <style>
        .dash-card { background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 16px; margin-bottom: 16px; min-height: auto; }
        .dash-card h4 { margin-top: 0; margin-bottom: 16px; color: #333; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
        .dash-kpi-row { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; padding: 18px 0 24px; }
        .dash-kpi-box { background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%); border: 1px solid #e0e0e0; border-radius: 8px; padding: 18px 32px; text-align: center; min-width: 200px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .dash-kpi-box .kpi-value { font-size: 32px; font-weight: 700; line-height: 1.2; }
        .dash-kpi-box .kpi-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; margin-top: 4px; }
        .dash-kpi-box.kpi-personal .kpi-value { color: #2e7d32; }
        .dash-kpi-box.kpi-proveedores .kpi-value { color: #1565c0; }
        .dash-chart-host { position: relative; margin: 0; margin-right: auto; max-width: 100%; }
        .dash-chart-host canvas { width: 100% !important; height: 100% !important; display: block; }
        .dash-card-compact { padding: 12px 10px 14px; margin-left: 0; margin-right: auto; }
        .dash-ing-table { width: 100%; max-width: 420px; margin: 0 0 12px 0; border-collapse: collapse; font-size: 12px; }
        .dash-ing-table thead th { background: #c8e6c9; color: #1b5e20; font-weight: 700; text-align: center; padding: 6px 8px; border: 1px solid #a5d6a7; }
        .dash-ing-table tbody td { padding: 5px 8px; border: 1px solid #e0e0e0; color: #2e7d32; }
        .dash-ing-table tbody td:last-child { text-align: center; font-weight: 600; }
        .dash-ing-table tbody td:first-child { text-align: left; }
        .dash-rol-ref { font-size: 11px; color: #666; margin: -8px 0 10px; text-align: left; }
        .btn-print-dash { margin: 0 0 0 auto; display: block; }
        .dash-print-header { display: none; }

        @media print {
            @page {
                margin: 10mm 12mm;
            }
            html, body {
                margin: 0;
                padding: 0;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .panel-heading,
            .btn-print-dash {
                display: none !important;
            }
            .panel-main {
                border: none !important;
                box-shadow: none !important;
            }
            .exa-body {
                padding: 0 !important;
            }
            .dash-print-header {
                display: block !important;
                margin-bottom: 14px;
                padding-bottom: 10px;
                border-bottom: 3px solid #1565c0;
            }
            .dash-print-header h1 {
                margin: 0 0 4px;
                font-size: 18px;
                font-weight: 700;
                color: #1a237e;
                letter-spacing: 0.2px;
            }
            .dash-print-header .dash-print-meta {
                margin: 0;
                font-size: 10px;
                color: #546e7a;
            }
            .dash-kpi-row {
                display: flex;
                justify-content: center;
                gap: 20px;
                margin-bottom: 14px;
                padding: 10px 0 12px;
                border-bottom: 1px solid #e0e0e0;
            }
            .dash-kpi-box {
                min-width: 160px;
                padding: 10px 22px;
                border: 1px solid #c5cae9;
                border-radius: 6px;
                box-shadow: none;
                background: #f5f7ff !important;
            }
            .dash-kpi-box .kpi-value { font-size: 24px; }
            .dash-kpi-box .kpi-label { font-size: 9px; }
            .row {
                display: block !important;
                margin: 0 0 6px !important;
                page-break-inside: auto;
            }
            .col-md-4,
            .col-md-6,
            .col-sm-12 {
                width: 100% !important;
                max-width: 100% !important;
                float: none !important;
                display: block !important;
                flex: none !important;
                padding: 0 0 16px !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .dash-card {
                height: auto !important;
                min-height: 0 !important;
                margin-bottom: 0;
                padding: 12px 12px 10px;
                border: 1px solid #dde3ef;
                border-radius: 6px;
                background: #fff !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .dash-card h4 {
                margin-bottom: 8px;
                padding-bottom: 6px;
                font-size: 10px;
                font-weight: 700;
                color: #1565c0;
                text-transform: uppercase;
                letter-spacing: 0.35px;
                border-bottom: 2px solid #e8eaf6;
            }
            .dash-card-compact {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
            }
            body.dash-printing .dash-chart-host {
                width: 100% !important;
                max-width: 100% !important;
                height: 250px !important;
                min-height: 250px !important;
                margin: 0 !important;
            }
            body.dash-printing #dashSexHost.dash-chart-host {
                width: 240px !important;
                max-width: 240px !important;
                height: 240px !important;
                min-height: 240px !important;
            }
            .dash-chart-host canvas {
                max-width: none !important;
                max-height: none !important;
            }
            .dash-ing-table {
                max-width: 100%;
                font-size: 10px;
            }
            .dash-rol-ref { font-size: 9px; margin-bottom: 6px; }
        }
    </style>
</head>
<body>
<div class="panel panel-main">
    <div class="panel-heading exa-header">
        <h3 class="panel-title">&raquo; Dashboard Socioeconomico RCET</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="dash-print-header">
            <h1>Dashboard general &mdash; Personal</h1>
            <p class="dash-print-meta">
                <span id="dashPrintDate"></span>
                &nbsp;&bull;&nbsp;
                <span id="dashPrintKpi"></span>
            </p>
        </div>
        <button type="button" class="btn btn-default btn-sm btn-print-dash" onclick="printDashboard()"><i class="fa fa-print"></i> Imprimir</button>
        <div class="dash-kpi-row">
            <div class="dash-kpi-box kpi-personal">
                <div class="kpi-value" id="kpiTotal">&mdash;</div>
                <div class="kpi-label"><i class="fa fa-users"></i> Personal activo</div>
            </div>
            <div class="dash-kpi-box kpi-proveedores">
                <div class="kpi-value" id="kpiProveedores">&mdash;</div>
                <div class="kpi-label"><i class="fa fa-truck"></i> Proveedores activos</div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 col-sm-12">
                <div class="dash-card">
                    <h4><i class="fa fa-pie-chart"></i> Distribuci&oacute;n por g&eacute;nero</h4>
                    <div id="dashSexHost" class="dash-chart-host"><canvas id="chartSex"></canvas></div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="dash-card">
                    <h4><i class="fa fa-bar-chart"></i> Total por nivel de estudio</h4>
                    <div id="dashTitHost" class="dash-chart-host"><canvas id="chartTit"></canvas></div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="dash-card">
                    <h4><i class="fa fa-bar-chart"></i> Personal por ciudad</h4>
                    <div id="dashCiuHost" class="dash-chart-host"><canvas id="chartCiu"></canvas></div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-sm-12">
                <div class="dash-card dash-card-compact">
                    <h4><i class="fa fa-bar-chart"></i> Personal por tipo de movilizaci&oacute;n</h4>
                    <div id="dashMovHost" class="dash-chart-host"><canvas id="chartMov"></canvas></div>
                </div>
            </div>
            <div class="col-md-6 col-sm-12">
                <div class="dash-card">
                    <h4><i class="fa fa-bar-chart"></i> Proveedores por actividad</h4>
                    <div id="dashTacHost" class="dash-chart-host"><canvas id="chartTac"></canvas></div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-sm-12">
                <div class="dash-card dash-card-compact">
                    <h4><i class="fa fa-bar-chart"></i> Personal por riesgo social</h4>
                    <div id="dashRsoHost" class="dash-chart-host"><canvas id="chartRso"></canvas></div>
                </div>
            </div>
            <div class="col-md-6 col-sm-12">
                <div class="dash-card">
                    <h4><i class="fa fa-bar-chart"></i> Personal por ingreso mensual (&uacute;ltimo rol)</h4>
                    <p id="dashIngRolRef" class="dash-rol-ref"></p>
                    <div id="dashIngTableWrap"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
Chart.register(ChartDataLabels);
(function () {
    var chartSex = null;
    var chartTit = null;
    var chartCiu = null;
    var chartMov = null;
    var chartTac = null;
    var chartRso = null;
    var CHART_SIZE = {
        minBarH: 220,
        maxBarH: 620,
        pxPerBarH: 38,
        padH: 72,
        minVertH: 260,
        maxVertH: 420,
        minBarW: 300,
        maxBarW: 900,
        compactPxPerCat: 98,
        compactPadW: 88,
        doughnutH: 280,
        doughnutMinW: 260,
        printBarH: 250,
        printDoughnutH: 240,
        printDoughnutW: 240
    };
    var printState = null;

    function allChartInstances() {
        return [chartSex, chartTit, chartCiu, chartMov, chartTac, chartRso].filter(function (c) {
            return !!c;
        });
    }

    function captureHostPrintState(hostId) {
        var $host = $('#' + hostId);
        var $compact = $host.closest('.dash-card-compact');
        var state = {
            hostId: hostId,
            width: $host[0].style.width,
            height: $host[0].style.height,
            minHeight: $host[0].style.minHeight,
            maxWidth: $host[0].style.maxWidth
        };
        if ($compact.length) {
            state.compact = {
                maxWidth: $compact[0].style.maxWidth,
                width: $compact[0].style.width
            };
        }
        return state;
    }

    function applyHostPrintState(state) {
        var $host = $('#' + state.hostId);
        $host.css({
            width: state.width,
            height: state.height,
            minHeight: state.minHeight,
            maxWidth: state.maxWidth
        });
        if (state.compact) {
            $host.closest('.dash-card-compact').css(state.compact);
        }
    }

    function printContentWidth() {
        var $body = $('.exa-body');
        var w = $body.length ? $body.innerWidth() : $(window).width();
        return Math.max(280, Math.min(CHART_SIZE.maxBarW, (w || 700) - 32));
    }

    function preparePrintCharts() {
        var hostIds = ['dashSexHost', 'dashTitHost', 'dashCiuHost', 'dashMovHost', 'dashTacHost', 'dashRsoHost'];
        printState = hostIds.map(captureHostPrintState);
        var barHosts = ['dashTitHost', 'dashCiuHost', 'dashMovHost', 'dashTacHost', 'dashRsoHost'];
        var printW = printContentWidth();
        var i;
        $('.dash-card-compact').css({ maxWidth: '100%', width: '100%' });
        setChartHostSize('dashSexHost', CHART_SIZE.printDoughnutW, CHART_SIZE.printDoughnutH, false);
        for (i = 0; i < barHosts.length; i++) {
            setChartHostSize(barHosts[i], printW, CHART_SIZE.printBarH, true);
        }
        allChartInstances().forEach(function (chart) {
            chart.resize();
            chart.update('none');
        });
    }

    function restorePrintCharts() {
        var i;
        if (!printState) {
            return;
        }
        for (i = 0; i < printState.length; i++) {
            applyHostPrintState(printState[i]);
        }
        printState = null;
        allChartInstances().forEach(function (chart) {
            chart.resize();
        });
    }

    function finishPrintMode() {
        restorePrintCharts();
        document.body.classList.remove('dash-printing');
    }

    function printDashboard() {
        document.body.classList.add('dash-printing');
        var now = new Date();
        $('#dashPrintDate').text(
            'Impreso: ' + now.toLocaleDateString('es-EC') + ' ' +
            now.toLocaleTimeString('es-EC', { hour: '2-digit', minute: '2-digit' })
        );
        $('#dashPrintKpi').text(
            ($('#kpiTotal').text() || '0') + ' personal activo  |  ' +
            ($('#kpiProveedores').text() || '0') + ' proveedores activos'
        );
        preparePrintCharts();
        setTimeout(function () {
            preparePrintCharts();
            setTimeout(function () {
                window.print();
            }, 350);
        }, 200);
    }

    window.printDashboard = printDashboard;

    function hostParentWidth(hostId) {
        var $col = $('#' + hostId).closest('[class*="col-"]');
        var w = $col.length ? $col.innerWidth() : $('#' + hostId).parent().width();
        return Math.max(160, Math.min(CHART_SIZE.maxBarW, (w || 320) - 24));
    }

    function maxLabelChars(labels) {
        var m = 0;
        for (var i = 0; i < labels.length; i++) {
            m = Math.max(m, String(labels[i] || '').length);
        }
        return m;
    }

    /** Barras: usar siempre el ancho de la columna para que las etiquetas no se monten */
    function barChartWidth(categoryCount, horizontal, labels, parentMaxW) {
        return parentMaxW || CHART_SIZE.maxBarW;
    }

    function doughnutChartWidth(sliceCount, parentMaxW) {
        parentMaxW = parentMaxW || 360;
        return Math.min(parentMaxW, Math.max(CHART_SIZE.doughnutMinW, Math.round(parentMaxW * 0.88)));
    }

    function barChartHeight(categoryCount, horizontal, labels) {
        var n = Math.max(categoryCount, 1);
        var longLbl = maxLabelChars(labels) > 22;
        if (horizontal) {
            return Math.min(CHART_SIZE.maxBarH, Math.max(CHART_SIZE.minBarH, n * CHART_SIZE.pxPerBarH + CHART_SIZE.padH));
        }
        var extra = longLbl ? 40 : 0;
        return Math.min(CHART_SIZE.maxVertH, Math.max(CHART_SIZE.minVertH, 240 + Math.min(n, 10) * 14 + extra));
    }

    function barChartLayout(categoryCount, labels) {
        var n = Math.max(categoryCount, 1);
        var longLbl = maxLabelChars(labels) > 20;
        /* Horizontal solo con muchas categorías; si no, barras verticales a ancho completo */
        var horizontal = n > 8 || (n > 6 && longLbl);
        var barPct = 0.45;
        var catPct = 0.65;
        if (n <= 3) {
            barPct = 0.5;
            catPct = 0.7;
        } else if (n <= 8) {
            barPct = 0.6;
            catPct = 0.75;
        } else if (horizontal) {
            barPct = 0.72;
            catPct = 0.88;
        } else {
            barPct = 0.55;
            catPct = 0.8;
        }
        return { horizontal: horizontal, barPercentage: barPct, categoryPercentage: catPct };
    }

    function setChartHostSize(hostId, widthPx, heightPx, fullWidth) {
        var css = {
            maxWidth: '100%',
            height: heightPx + 'px',
            minHeight: heightPx + 'px',
            margin: '0',
            marginRight: 'auto'
        };
        if (fullWidth) {
            css.width = '100%';
        } else {
            css.width = widthPx + 'px';
        }
        $('#' + hostId).css(css);
    }

    function resetChartHost(hostId, canvasId, widthPx, heightPx, fullWidth) {
        setChartHostSize(hostId, widthPx, heightPx, fullWidth !== false);
        $('#' + hostId).html('<canvas id="' + canvasId + '"></canvas>');
        return document.getElementById(canvasId);
    }

    function truncateLabel(text, maxLen) {
        text = String(text || '');
        if (text.length <= maxLen) {
            return text;
        }
        return text.substring(0, maxLen - 1) + '\u2026';
    }

    function destroyAllCharts() {
        [chartSex, chartTit, chartCiu, chartMov, chartTac, chartRso].forEach(function (c) {
            if (c) {
                c.destroy();
            }
        });
        chartSex = chartTit = chartCiu = chartMov = chartTac = chartRso = null;
    }

    function fitCompactCard(hostId, categoryCount) {
        var parentW = hostParentWidth(hostId);
        var n = Math.max(categoryCount, 1);
        var cardW = Math.min(parentW, Math.max(300, n * CHART_SIZE.compactPxPerCat + CHART_SIZE.compactPadW));
        $('#' + hostId).closest('.dash-card-compact').css({
            maxWidth: cardW + 'px',
            width: cardW + 'px'
        });
    }

    function createBarChart(hostId, canvasId, labels, data, palette, datasetLabel, chartOpts) {
        chartOpts = chartOpts || {};
        var n = labels.length;
        var layout = barChartLayout(n, labels);
        var parentW = hostParentWidth(hostId);
        var w = barChartWidth(n, layout.horizontal, labels, parentW);
        if (chartOpts.compact) {
            w = Math.min(parentW, Math.max(300, n * CHART_SIZE.compactPxPerCat + CHART_SIZE.compactPadW));
            if (!layout.horizontal && n <= 8) {
                layout.barPercentage = 0.42;
                layout.categoryPercentage = 0.62;
            }
            fitCompactCard(hostId, n);
        }
        var h = barChartHeight(n, layout.horizontal, labels);
        var longLbl = maxLabelChars(labels) > 18;
        var ctx = resetChartHost(hostId, canvasId, w, h, true);
        var displayLabels = labels.map(function (lb) {
            if (layout.horizontal) {
                return truncateLabel(lb, 42);
            }
            return longLbl ? truncateLabel(lb, 28) : lb;
        });
        var opts = {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: layout.horizontal ? 'y' : 'x',
            layout: {
                padding: {
                    top: 10,
                    right: chartOpts.compact ? 6 : 14,
                    bottom: layout.horizontal ? 10 : (longLbl ? 28 : 16),
                    left: chartOpts.compact ? 6 : (layout.horizontal ? 8 : 10)
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: function (items) {
                            if (!items.length) {
                                return '';
                            }
                            var i = items[0].dataIndex;
                            return labels[i] != null ? labels[i] : '';
                        }
                    }
                },
                datalabels: {
                    anchor: layout.horizontal ? 'end' : 'end',
                    align: layout.horizontal ? 'end' : 'end',
                    offset: chartOpts.showPct ? 4 : 0,
                    font: { weight: 'bold', size: n > 12 ? 10 : 11 },
                    color: '#333',
                    formatter: chartOpts.showPct ? function (value, ctx) {
                        if (!value) {
                            return '';
                        }
                        return value + ' (' + pctOfTotal(value, ctx.dataset.data, chartOpts.pctTotal) + '%)';
                    } : undefined,
                    display: chartOpts.showPct ? function (ctx) {
                        return (ctx.dataset.data[ctx.dataIndex] || 0) > 0;
                    } : true
                }
            },
            scales: {}
        };
        if (chartOpts.showPct) {
            opts.plugins.tooltip.callbacks.label = function (ctx) {
                var v = ctx.raw || 0;
                var pct = pctOfTotal(v, ctx.dataset.data, chartOpts.pctTotal);
                return (ctx.dataset.label || '') + ': ' + v + ' (' + pct + '%)';
            };
            if (!layout.horizontal) {
                opts.layout.padding.top = 22;
            } else {
                opts.layout.padding.right = 48;
            }
        }
        if (layout.horizontal) {
            opts.scales.x = { beginAtZero: true, ticks: { precision: 0, padding: 4 } };
            opts.scales.y = {
                ticks: {
                    autoSkip: false,
                    font: { size: 11 },
                    padding: 8,
                    callback: function (val, idx) {
                        return displayLabels[idx] != null ? displayLabels[idx] : val;
                    }
                }
            };
        } else {
            opts.scales.y = { beginAtZero: true, ticks: { precision: 0, padding: 4 } };
            opts.scales.x = {
                ticks: {
                    autoSkip: n > 14,
                    maxTicksLimit: n > 14 ? 14 : undefined,
                    maxRotation: longLbl || n > 5 ? 50 : 35,
                    minRotation: longLbl || n > 5 ? 32 : 0,
                    font: { size: 11 },
                    padding: 6,
                    callback: function (val, idx) {
                        return displayLabels[idx] != null ? displayLabels[idx] : val;
                    }
                }
            };
        }
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: datasetLabel,
                    data: data,
                    backgroundColor: labels.map(function (_, i) { return palette[i % palette.length]; }),
                    borderWidth: 1,
                    barPercentage: layout.barPercentage,
                    categoryPercentage: layout.categoryPercentage
                }]
            },
            options: opts
        });
    }

    function labelSex(code) {
        if (code === 'M') return 'Masculino';
        if (code === 'F') return 'Femenino';
        if (code === '?') return 'Sin indicar';
        return code || 'Otro';
    }

    function pctOfTotal(value, dataArr, fixedTotal) {
        var sum = fixedTotal > 0 ? fixedTotal : 0;
        if (!sum) {
            for (var i = 0; i < dataArr.length; i++) {
                sum += dataArr[i] || 0;
            }
        }
        if (!sum || !value) {
            return 0;
        }
        return Math.round(value * 1000 / sum) / 10;
    }

    var ING_RANGES_DEFAULT = [
        { rango_ord: 1, rango_des: '< $450', total: 0 },
        { rango_ord: 2, rango_des: '$450 - $600', total: 0 },
        { rango_ord: 3, rango_des: '$601 - $800', total: 0 },
        { rango_ord: 4, rango_des: '> $800', total: 0 }
    ];

    function mergeIngRanges(rows) {
        var map = {};
        ING_RANGES_DEFAULT.forEach(function (r) {
            map[r.rango_ord] = { rango_ord: r.rango_ord, rango_des: r.rango_des, total: 0 };
        });
        (rows || []).forEach(function (row) {
            var ord = parseInt(row.rango_ord, 10);
            if (map[ord]) {
                map[ord].total = parseInt(row.total, 10) || 0;
                if (row.rango_des) {
                    map[ord].rango_des = row.rango_des;
                }
            }
        });
        return ING_RANGES_DEFAULT.map(function (r) {
            return map[r.rango_ord];
        });
    }

    function renderIngTable(rows) {
        var html = '<table class="dash-ing-table"><thead><tr><th>RANGO MENSUAL</th><th>TOTAL</th></tr></thead><tbody>';
        rows.forEach(function (row) {
            html += '<tr><td>' + (row.rango_des || '') + '</td><td>' + (parseInt(row.total, 10) || 0) + '</td></tr>';
        });
        html += '</tbody></table>';
        $('#dashIngTableWrap').html(html);
    }

    function setUltimoRolRef(rol) {
        var $ref = $('#dashIngRolRef');
        if (!rol || !rol.Rol_Cod) {
            $ref.text('Sin rol de pagos activo con total de ingresos.');
            return;
        }
        var parts = [];
        if (rol.Rol_Num) {
            parts.push('Rol #' + rol.Rol_Num);
        }
        if (rol.Rol_Fef) {
            parts.push('cierre ' + rol.Rol_Fef);
        } else if (rol.Rol_Fei) {
            parts.push('desde ' + rol.Rol_Fei);
        }
        if (rol.Rol_Con) {
            parts.push(rol.Rol_Con);
        }
        $ref.text(parts.length ? ('Referencia: ' + parts.join(' · ')) : '');
    }

    function loadDashboard() {
        $.get(UrlSaveJson, { dashPersonalAjax: 1 }, function (res) {
            if (!res || !res.success) {
                if (typeof $.alert === 'function') $.alert('No se pudo cargar el dashboard.'); else alert('No se pudo cargar el dashboard.');
                return;
            }
            var tot = res.totalPersonal != null ? res.totalPersonal : 0;
            var totProv = res.totalProveedores != null ? res.totalProveedores : 0;
            $('#kpiTotal').text(tot);
            $('#kpiProveedores').text(totProv);

            if (tot === 0) {
                destroyAllCharts();
                var msg0 = '<p class="text-muted text-center" style="padding:40px 10px;">No hay personal activo para esta empresa.</p>';
                $('#dashSexHost').html(msg0);
                $('#dashTitHost').html(msg0);
                $('#dashCiuHost').html(msg0);
                $('#dashMovHost').html(msg0);
                $('#dashTacHost').html(msg0);
                $('#dashRsoHost').html(msg0);
                $('#dashIngTableWrap').empty();
                $('#dashIngRolRef').text('');
                return;
            }

            destroyAllCharts();

            var sexRows = res.bySex || [];
            var labelsS = [];
            var dataS = [];
            var colorsS = ['#5cb85c', '#5bc0de', '#f0ad4e', '#d9534f'];
            sexRows.forEach(function (row) {
                labelsS.push(labelSex(row.Prs_Sex));
                dataS.push(parseInt(row.total, 10) || 0);
            });
            if (labelsS.length === 0) {
                labelsS.push('Sin clasificar');
                dataS.push(tot);
            }

            var wSex = doughnutChartWidth(labelsS.length, hostParentWidth('dashSexHost'));
            var ctxS = resetChartHost('dashSexHost', 'chartSex', wSex, CHART_SIZE.doughnutH, false);
            chartSex = new Chart(ctxS, {
                type: 'doughnut',
                data: {
                    labels: labelsS,
                    datasets: [{
                        data: dataS,
                        backgroundColor: labelsS.map(function (_, i) { return colorsS[i % colorsS.length]; }),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        datalabels: {
                            display: function (ctx) {
                                var v = ctx.dataset.data[ctx.dataIndex] || 0;
                                return pctOfTotal(v, ctx.dataset.data) >= 4;
                            },
                            color: '#fff',
                            font: { weight: 'bold', size: 12 },
                            anchor: 'center',
                            align: 'center',
                            textStrokeColor: 'rgba(0,0,0,0.4)',
                            textStrokeWidth: 2,
                            formatter: function (value, ctx) {
                                return pctOfTotal(value, ctx.dataset.data) + '%';
                            }
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                generateLabels: function (chart) {
                                    var ds = chart.data.datasets[0];
                                    var data = chart.data.labels || [];
                                    return data.map(function (label, i) {
                                        var value = ds.data[i] || 0;
                                        var pct = pctOfTotal(value, ds.data);
                                        return {
                                            text: label + ' — ' + value + ' (' + pct + '%)',
                                            fillStyle: ds.backgroundColor[i],
                                            strokeStyle: ds.borderColor ? ds.borderColor[i] : '#fff',
                                            lineWidth: ds.borderWidth || 1,
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    var v = ctx.raw || 0;
                                    var pct = pctOfTotal(v, ctx.dataset.data);
                                    return ctx.label + ': ' + v + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });

            var titRows = res.byTit || [];
            var labelsT = [];
            var dataT = [];
            titRows.forEach(function (row) {
                labelsT.push(row.titulo_des || row.Per_Tit_Cod || '');
                dataT.push(parseInt(row.total, 10) || 0);
            });
            if (labelsT.length === 0) {
                labelsT.push('Sin datos');
                dataT.push(0);
            }
            var palette = ['#439943', '#31708f', '#8a6d3b', '#a94442', '#777', '#337ab7', '#5cb85c', '#f0ad4e', '#5bc0de', '#d9534f', '#9b59b6'];
            chartTit = createBarChart('dashTitHost', 'chartTit', labelsT, dataT, palette, 'Empleados', {
                showPct: true,
                pctTotal: tot
            });

            // Gráfico por ciudad
            var ciuRows = res.byCiu || [];
            var labelsC = [];
            var dataC = [];
            ciuRows.forEach(function (row) {
                labelsC.push(row.Ciu_Des || '(Sin ciudad)');
                dataC.push(parseInt(row.total, 10) || 0);
            });
            if (labelsC.length === 0) {
                labelsC.push('Sin datos');
                dataC.push(0);
            }
            var paletteCiu = ['#337ab7', '#5cb85c', '#f0ad4e', '#5bc0de', '#d9534f', '#439943', '#9b59b6', '#31708f', '#8a6d3b', '#a94442', '#777'];
            chartCiu = createBarChart('dashCiuHost', 'chartCiu', labelsC, dataC, paletteCiu, 'Empleados');

            // Gráfico por tipo de movilización
            var movRows = res.byMov || [];
            var labelsM = [];
            var dataM = [];
            movRows.forEach(function (row) {
                labelsM.push(row.mov_des || row.Per_Mov_Cod || '(Sin definir)');
                dataM.push(parseInt(row.total, 10) || 0);
            });
            if (labelsM.length === 0) {
                labelsM.push('Sin datos');
                dataM.push(0);
            }
            var paletteMov = ['#5bc0de', '#439943', '#f0ad4e', '#d9534f', '#337ab7', '#9b59b6', '#31708f', '#8a6d3b', '#a94442', '#777', '#5cb85c'];
            chartMov = createBarChart('dashMovHost', 'chartMov', labelsM, dataM, paletteMov, 'Empleados', { compact: true });

            // Gráfico proveedores por actividad
            var tacRows = res.byTac || [];
            var labelsTac = [];
            var dataTac = [];
            tacRows.forEach(function (row) {
                labelsTac.push(row.actividad || '(Sin actividad)');
                dataTac.push(parseInt(row.total, 10) || 0);
            });
            if (labelsTac.length === 0) {
                labelsTac.push('Sin datos');
                dataTac.push(0);
            }
            var paletteTac = ['#337ab7', '#5cb85c', '#f0ad4e', '#d9534f', '#5bc0de', '#9b59b6', '#31708f', '#8a6d3b', '#a94442', '#439943', '#777'];
            chartTac = createBarChart('dashTacHost', 'chartTac', labelsTac, dataTac, paletteTac, 'Proveedores');

            // Gráfico por riesgo social
            var rsoRows = res.byRso || [];
            var labelsR = [];
            var dataR = [];
            rsoRows.forEach(function (row) {
                labelsR.push(row.rso_des || row.Per_Rso_Cod || '(Sin definir)');
                dataR.push(parseInt(row.total, 10) || 0);
            });
            if (labelsR.length === 0) {
                labelsR.push('Sin datos');
                dataR.push(0);
            }
            var paletteRso = ['#d9534f', '#f0ad4e', '#5cb85c', '#337ab7', '#777', '#9b59b6'];
            chartRso = createBarChart('dashRsoHost', 'chartRso', labelsR, dataR, paletteRso, 'Empleados', { compact: true });

            setUltimoRolRef(res.ultimoRol || {});
            renderIngTable(mergeIngRanges(res.byIng || []));
        }, 'json').fail(function () {
            if (typeof $.alert === 'function') $.alert('Error de comunicaci&oacute;n al cargar datos.'); else alert('Error de comunicacion al cargar datos.');
        });
    }

    $(function () {
        loadDashboard();
        var resizeTimer;
        $(window).on('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                allChartInstances().forEach(function (c) {
                    c.resize();
                });
            }, 200);
        });
        window.addEventListener('afterprint', finishPrintMode);
        if (window.matchMedia) {
            window.matchMedia('print').addListener(function (mq) {
                if (!mq.matches) {
                    finishPrintMode();
                }
            });
        }
    });
})();
</script>
</body>
</html>
