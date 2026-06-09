<?php
/**
 * Dashboard resumido de personal: perfil, laboral, movilidad, familia y salud.
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
    $byRso = $obBD_con1->getArrayConsulta(23, $Ses_Emp_Cod, $obBD_conexion);
    $byIng = $obBD_con1->getArrayConsulta(24, $Ses_Emp_Cod, $obBD_conexion);
    $byAre = $obBD_con1->getArrayConsulta(27, $Ses_Emp_Cod, $obBD_conexion);
    $byCar = $obBD_con1->getArrayConsulta(28, $Ses_Emp_Cod, $obBD_conexion);
    $byTcf = $obBD_con1->getArrayConsulta(29, $Ses_Emp_Cod, $obBD_conexion);
    $byCon = $obBD_con1->getArrayConsulta(30, $Ses_Emp_Cod, $obBD_conexion);
    $bySan = $obBD_con1->getArrayConsulta(31, $Ses_Emp_Cod, $obBD_conexion);
    $empCod = intval($Ses_Emp_Cod);
    $pecUltSql = "(SELECT pec.Pec_Cod
        FROM perio_cont pec
        INNER JOIN plan_cuenta pla ON pla.Pla_Cod = pec.Pla_Cod AND pla.Emp_Cod = $empCod AND pla.Pla_Est = 'A'
        WHERE pec.Pec_Est = 'A'
        ORDER BY pec.Pec_Fei DESC, pec.Pec_Cod DESC
        LIMIT 1)";
    $mesAreaSql = "(SELECT DATE_FORMAT(MAX(IFNULL(rpM.Rol_Fef, rpM.Rol_Fei)), '%Y-%m')
        FROM rol_pagos rpM
        INNER JOIN det_rpagos drM ON drM.Rol_Cod = rpM.Rol_Cod
        INNER JOIN campo_rol crM ON crM.Cam_Cod = drM.Cam_Cod
            AND crM.Cam_Var IN ('total_ingr', 'total_ing')
        WHERE rpM.Are_Cod = rp1.Are_Cod AND rpM.Rol_Est = 'A' AND rpM.Pec_Cod = $pecUltSql
            AND TRIM(drM.Rol_Val) <> '' AND TRIM(drM.Rol_Val) <> '0')";
    $periodoAct = $obBD_con1->getRowConsultaSql(
        "SELECT pec.Pec_Cod, pec.Pec_Fei, pec.Pec_Fef, YEAR(pec.Pec_Fei) AS Periodo
         FROM perio_cont pec
         INNER JOIN plan_cuenta pla ON pla.Pla_Cod = pec.Pla_Cod
         WHERE pla.Emp_Cod = $empCod AND pla.Pla_Est = 'A' AND pec.Pec_Est = 'A'
         ORDER BY pec.Pec_Fei DESC, pec.Pec_Cod DESC
         LIMIT 1",
        $obBD_conexion
    );
    $ingresoMeta = array('periodoAnio' => null, 'periodoRango' => '', 'refMesLabel' => '', 'totalAreas' => 0);
    if (!empty($periodoAct['Periodo'])) {
        $ingresoMeta['periodoAnio'] = (int) $periodoAct['Periodo'];
        $ingresoMeta['periodoRango'] = trim($periodoAct['Pec_Fei'] . ' - ' . $periodoAct['Pec_Fef']);
    }
    $ultRoles = $obBD_con1->getArrayConsultaSql(
        "SELECT rp.Rol_Cod, rp.Rol_Num, rp.Rol_Fef, rp.Rol_Fei, ar.Are_Des,
                DATE_FORMAT(IFNULL(rp.Rol_Fef, rp.Rol_Fei), '%Y-%m') AS ref_mes
         FROM rol_pagos rp
         INNER JOIN areas_rrhh ar ON ar.Are_Cod = rp.Are_Cod AND ar.Emp_Cod = $empCod AND ar.Are_Est = 'A'
         INNER JOIN (
             SELECT rp1.Are_Cod,
             SUBSTRING_INDEX(
                 GROUP_CONCAT(
                     rp1.Rol_Cod
                     ORDER BY IFNULL(rp1.Rol_Fef, rp1.Rol_Fei) DESC, rp1.Rol_Num DESC, rp1.Rol_Cod DESC
                 ),
                 ',', 1
             ) AS Rol_Cod
             FROM rol_pagos rp1
             INNER JOIN areas_rrhh ar1 ON ar1.Are_Cod = rp1.Are_Cod
                 AND ar1.Emp_Cod = $empCod AND ar1.Are_Est = 'A'
             INNER JOIN det_rpagos drx ON drx.Rol_Cod = rp1.Rol_Cod
             INNER JOIN campo_rol crx ON crx.Cam_Cod = drx.Cam_Cod
                 AND crx.Cam_Var IN ('total_ingr', 'total_ing')
             WHERE rp1.Rol_Est = 'A' AND rp1.Pec_Cod = $pecUltSql
                 AND TRIM(drx.Rol_Val) <> '' AND TRIM(drx.Rol_Val) <> '0'
                 AND DATE_FORMAT(IFNULL(rp1.Rol_Fef, rp1.Rol_Fei), '%Y-%m') = $mesAreaSql
             GROUP BY rp1.Are_Cod
         ) ult_rol ON ult_rol.Rol_Cod = rp.Rol_Cod
         WHERE rp.Rol_Est = 'A' AND rp.Pec_Cod = $pecUltSql
         ORDER BY ar.Are_Des",
        $obBD_conexion
    );
    $mesesEs = array('01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun',
        '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic');
    $mesSet = array();
    if (is_array($ultRoles)) {
        foreach ($ultRoles as $r) {
            if (!empty($r['ref_mes']) && preg_match('/^(\d{4})-(\d{2})$/', $r['ref_mes'], $mRef)) {
                $mesSet[$r['ref_mes']] = (isset($mesesEs[$mRef[2]]) ? $mesesEs[$mRef[2]] : $mRef[2]) . ' ' . $mRef[1];
            }
        }
    }
    $mesKeys = array_keys($mesSet);
    sort($mesKeys);
    if (count($mesKeys) === 1) {
        $ingresoMeta['refMesLabel'] = $mesSet[$mesKeys[0]];
    } elseif (count($mesKeys) > 1) {
        $ingresoMeta['refMesLabel'] = $mesSet[$mesKeys[0]] . ' - ' . $mesSet[$mesKeys[count($mesKeys) - 1]];
    }
    $ingresoMeta['totalAreas'] = is_array($ultRoles) ? count($ultRoles) : 0;
    utf8_encode_deep($bySex);
    utf8_encode_deep($byTit);
    utf8_encode_deep($byCiu);
    utf8_encode_deep($byMov);
    utf8_encode_deep($byRso);
    utf8_encode_deep($byIng);
    utf8_encode_deep($byAre);
    utf8_encode_deep($byCar);
    utf8_encode_deep($byTcf);
    utf8_encode_deep($byCon);
    utf8_encode_deep($bySan);
    if (is_array($ultRoles)) {
        utf8_encode_deep($ultRoles);
    }
    if (is_array($ingresoMeta)) {
        utf8_encode_deep($ingresoMeta);
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
    echo json_encode(array(
        'success' => true,
        'totalPersonal' => $total,
        'bySex' => $bySex,
        'byTit' => $byTit,
        'byCiu' => $byCiu,
        'byMov' => $byMov,
        'byRso' => $byRso,
        'byIng' => $byIng,
        'byAre' => $byAre,
        'byCar' => $byCar,
        'byTcf' => $byTcf,
        'byCon' => $byCon,
        'bySan' => $bySan,
        'ultimosRoles' => $ultRoles ? $ultRoles : array(),
        'ingresoMeta' => $ingresoMeta,
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --dash-bg: #eef1f6;
            --dash-card: #ffffff;
            --dash-text: #1e293b;
            --dash-muted: #64748b;
            --dash-border: #e8ecf3;
            --dash-teal: #26a69a;
            --dash-purple: #7e57c2;
            --dash-pink: #ec407a;
            --dash-blue: #42a5f5;
            --dash-orange: #ffa726;
            --dash-shadow: 0 4px 18px rgba(15, 23, 42, 0.07);
            --dash-radius: 14px;
        }
        body.dash-modern-body { background: var(--dash-bg) !important; }
        .panel-main.dash-panel {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
            margin: 0 !important;
        }
        .panel-main.dash-panel > .panel-heading.exa-header {
            display: none;
        }
        .dash-app {
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
            color: var(--dash-text);
            padding: 8px 4px 28px;
        }
        .dash-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .dash-topbar-title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: var(--dash-text);
            letter-spacing: -0.02em;
        }
        .dash-topbar-sub {
            margin: 4px 0 0;
            font-size: 12px;
            color: var(--dash-muted);
        }
        .btn-print-dash {
            margin: 0;
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            background: var(--dash-purple);
            color: #fff;
            font-weight: 600;
            font-size: 12px;
            box-shadow: 0 4px 12px rgba(126, 87, 194, 0.35);
        }
        .btn-print-dash:hover { background: #6a4aad; color: #fff; }
        .dash-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }
        .dash-kpi-card {
            background: var(--dash-card);
            border-radius: var(--dash-radius);
            padding: 18px 20px;
            box-shadow: var(--dash-shadow);
            border: 1px solid var(--dash-border);
            position: relative;
            overflow: hidden;
        }
        .dash-kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
        }
        .dash-kpi-card--teal::before { background: linear-gradient(90deg, var(--dash-teal), #4db6ac); }
        .dash-kpi-card--purple::before { background: linear-gradient(90deg, var(--dash-purple), #9575cd); }
        .dash-kpi-card--pink::before { background: linear-gradient(90deg, var(--dash-pink), #f48fb1); }
        .dash-kpi-card--hero { grid-column: span 1; min-height: 100px; }
        @media (min-width: 992px) {
            .dash-kpi-card--hero { grid-column: span 1; }
        }
        .dash-kpi-card .kpi-icon {
            float: right;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #fff;
        }
        .dash-kpi-card--teal .kpi-icon { background: var(--dash-teal); }
        .dash-kpi-card--purple .kpi-icon { background: var(--dash-purple); }
        .dash-kpi-card--pink .kpi-icon { background: var(--dash-pink); }
        .dash-kpi-card .kpi-value {
            font-size: 34px;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -0.03em;
            color: var(--dash-text);
        }
        .dash-kpi-card--hero .kpi-value { font-size: 42px; }
        .dash-kpi-card .kpi-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--dash-muted);
            margin-top: 6px;
        }
        .dash-section {
            margin-bottom: 22px;
        }
        .dash-section-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--dash-muted);
            margin: 0 0 12px 4px;
        }
        .dash-section-label .fa { margin-right: 6px; color: var(--dash-purple); }
        .dash-row-cards {
            display: flex;
            flex-wrap: wrap;
            margin-left: -8px;
            margin-right: -8px;
        }
        .dash-row-cards > [class*="col-"] {
            display: flex;
            flex-direction: column;
            padding-left: 8px;
            padding-right: 8px;
            margin-bottom: 16px;
        }
        .dash-row-cards > .dash-col-fullrow {
            flex: 0 0 100%;
            max-width: 100%;
            width: 100%;
        }
        .dash-card-modern {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: var(--dash-radius);
            box-shadow: var(--dash-shadow);
            padding: 0;
            margin-bottom: 0;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .dash-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            padding: 16px 18px 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .dash-card-eyebrow {
            display: block;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--dash-muted);
            margin-bottom: 2px;
        }
        .dash-card-title {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: var(--dash-text);
            line-height: 1.3;
        }
        .dash-card-icon {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 15px;
        }
        .dash-icon-teal { background: linear-gradient(135deg, #26a69a, #00897b); }
        .dash-icon-purple { background: linear-gradient(135deg, #7e57c2, #5e35b1); }
        .dash-icon-pink { background: linear-gradient(135deg, #ec407a, #d81b60); }
        .dash-icon-blue { background: linear-gradient(135deg, #42a5f5, #1e88e5); }
        .dash-icon-orange { background: linear-gradient(135deg, #ffa726, #fb8c00); }
        .dash-icon-green { background: linear-gradient(135deg, #66bb6a, #43a047); }
        .dash-card-body {
            padding: 8px 14px 16px;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }
        .dash-chart-host {
            position: relative;
            margin: 0 auto;
            width: 100%;
            max-width: 100%;
            flex: 0 1 auto;
            min-height: 0;
        }
        .dash-chart-host canvas { width: 100% !important; height: 100% !important; display: block; }
        .dash-print-data-table {
            display: none;
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 8px;
        }
        .dash-print-data-table th,
        .dash-print-data-table td {
            border: 1px solid var(--dash-border);
            padding: 5px 8px;
            text-align: left;
        }
        .dash-print-data-table th {
            background: #f1f5f9;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
        }
        .dash-print-data-table td:last-child {
            text-align: center;
            font-weight: 700;
            width: 70px;
        }
        .dash-chart-host--compact {
            flex: 0 0 auto !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        #dashSexHost.dash-chart-host--compact,
        #dashConPieHost.dash-chart-host--compact { max-width: none; }
        #dashSexHost:not(.dash-chart-host--compact),
        #dashConPieHost:not(.dash-chart-host--compact) {
            max-width: 280px;
            margin-left: auto;
            margin-right: auto;
            min-height: 240px;
        }
        .dash-rol-ref {
            font-size: 11px;
            color: var(--dash-muted);
            margin: 0 0 10px;
            line-height: 1.45;
        }
        .dash-ing-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
            border-radius: 10px;
            overflow: hidden;
        }
        .dash-ing-table thead th {
            background: linear-gradient(135deg, #7e57c2, #9575cd);
            color: #fff;
            font-weight: 600;
            text-align: center;
            padding: 10px 8px;
            border: none;
        }
        .dash-ing-table tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--dash-border);
            color: var(--dash-text);
            background: #fff;
        }
        .dash-ing-table tbody tr:last-child td { border-bottom: none; }
        .dash-ing-table tbody td:last-child {
            text-align: center;
            font-weight: 700;
            color: var(--dash-purple);
        }
        .dash-print-header { display: none; }
        .exa-body.dash-app-wrap { background: var(--dash-bg) !important; padding: 16px 20px 24px !important; }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 11mm;
            }
            html, body {
                margin: 0;
                padding: 0;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .panel-heading,
            .panel-heading.exa-header,
            .btn-print-dash,
            .dash-topbar {
                display: none !important;
                height: 0 !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                border: none !important;
            }
            .panel-main {
                border: none !important;
                box-shadow: none !important;
            }
            .exa-body,
            .dash-app {
                padding: 0 !important;
            }
            .dash-print-header {
                display: block !important;
                margin-bottom: 10px;
                padding-bottom: 8px;
                border-bottom: 2px solid #1565c0;
            }
            .dash-print-header h1 {
                margin: 0 0 3px;
                font-size: 16px;
                font-weight: 700;
                color: #1a237e;
            }
            .dash-print-header .dash-print-meta {
                margin: 0;
                font-size: 9px;
                color: #546e7a;
            }
            .dash-kpi-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                margin: 0 0 8px !important;
                padding-bottom: 8px;
                border-bottom: 1px solid #e0e0e0;
                page-break-after: avoid;
            }
            .dash-kpi-card {
                padding: 8px 10px;
                box-shadow: none;
                border: 1px solid #dde3ef;
            }
            .dash-kpi-card::before { height: 3px; }
            .dash-kpi-card .kpi-icon { display: none; }
            .dash-kpi-card .kpi-value { font-size: 20px; }
            .dash-kpi-card--hero .kpi-value { font-size: 22px; }
            .dash-kpi-card .kpi-label { font-size: 7px; }
            .dash-section {
                margin: 0 0 10px !important;
                padding: 0 !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            .dash-section-label {
                font-size: 9px;
                margin: 0 0 5px !important;
                padding-left: 2px;
                page-break-after: avoid;
            }
            .dash-row-cards,
            .dash-row-cards.row {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 0;
                line-height: 0;
            }
            .dash-row-cards > [class*="col-"] {
                float: none !important;
                display: inline-block !important;
                vertical-align: top !important;
                box-sizing: border-box;
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
                padding: 0 5px 8px !important;
                margin: 0 !important;
                font-size: 10px;
                line-height: normal;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            .dash-row-cards > .col-md-4 {
                width: 33.333% !important;
                max-width: 33.333% !important;
            }
            .dash-row-cards > .col-md-6 {
                width: 50% !important;
                max-width: 50% !important;
            }
            .dash-row-cards > .col-md-8 {
                width: 100% !important;
                max-width: 100% !important;
            }
            .dash-row-cards > .col-md-6.col-md-offset-3 {
                width: 50% !important;
                max-width: 50% !important;
                margin-left: 25% !important;
            }
            .dash-row-cards > .dash-col-fullrow {
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                display: block !important;
            }
            .dash-card-modern {
                display: block !important;
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
                border: 1px solid #dde3ef;
                border-radius: 6px;
                background: #fff !important;
                page-break-inside: avoid;
                break-inside: avoid-page;
            }
            .dash-card-body {
                display: block !important;
                height: auto !important;
                min-height: 0 !important;
                flex: none !important;
                padding: 4px 8px 8px;
            }
            .dash-card-head {
                padding: 8px 10px 4px;
                border-bottom: 1px solid #e8ecf3;
            }
            .dash-card-eyebrow { font-size: 7px; }
            .dash-card-title {
                font-size: 9px;
                font-weight: 700;
                color: #1565c0;
                text-transform: uppercase;
                line-height: 1.25;
            }
            .dash-card-icon { display: none; }
            body.dash-printing #dashSexHost,
            body.dash-printing #dashConPieHost,
            body.dash-printing .dash-chart-host {
                max-height: none !important;
                overflow: visible !important;
            }
            body.dash-printing .dash-chart-host {
                margin-left: auto !important;
                margin-right: auto !important;
                flex: none !important;
            }
            body.dash-printing .dash-chart-host--compact {
                width: 100% !important;
                max-width: 100% !important;
            }
            body.dash-printing .dash-chart-host--doughnut-print {
                max-width: 100% !important;
            }
            body.dash-printing .dash-chart-host--bar-print {
                width: 100% !important;
                max-width: 100% !important;
            }
            body.dash-printing .dash-chart-host.dash-chart-frozen {
                overflow: visible !important;
            }
            body.dash-printing .dash-chart-host.dash-chart-frozen canvas {
                display: none !important;
                visibility: hidden !important;
            }
            body.dash-printing .dash-print-snapshot {
                display: block !important;
                max-width: 100% !important;
                margin: 0 auto !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body.dash-printing .dash-print-data-table {
                display: table !important;
                font-size: 8px !important;
                margin-top: 4px !important;
            }
            body.dash-printing .dash-print-data-table th {
                padding: 4px 6px !important;
                font-size: 7px !important;
            }
            body.dash-printing .dash-print-data-table td {
                padding: 3px 6px !important;
            }
            body.dash-printing .dash-chart-host canvas {
                visibility: visible !important;
                display: block !important;
                max-width: 100% !important;
            }
            body.dash-printing .dash-chart-host.dash-chart-frozen canvas {
                display: none !important;
            }
            .dash-chart-host canvas {
                max-width: 100% !important;
            }
            .dash-ing-table {
                max-width: 100%;
                font-size: 9px;
            }
            .dash-ing-table thead th { padding: 6px 4px; }
            .dash-ing-table tbody td { padding: 5px 6px; }
            .dash-rol-ref {
                font-size: 7px;
                margin-bottom: 4px;
                line-height: 1.35;
            }
        }
    </style>
</head>
<body class="dash-modern-body">
<div class="panel panel-main dash-panel">
    <div class="panel-heading exa-header">
        <h3 class="panel-title">&raquo; Dashboard Socioeconomico RCET</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body dash-app-wrap">
        <div class="dash-app">
        <div class="dash-print-header">
            <h1>Dashboard general &mdash; Personal</h1>
            <p class="dash-print-meta">
                <span id="dashPrintDate"></span>
                &nbsp;&bull;&nbsp;
                <span id="dashPrintKpi"></span>
            </p>
        </div>
        <div class="dash-topbar">
            <div>
                <h1 class="dash-topbar-title">Dashboard Socioecon&oacute;mico RCET</h1>
                <p class="dash-topbar-sub">Resumen anal&iacute;tico del personal activo</p>
            </div>
            <button type="button" class="btn btn-sm btn-print-dash" id="btnPrintDash"><i class="fa fa-print"></i> Imprimir</button>
        </div>
        <div class="dash-kpi-grid">
            <div class="dash-kpi-card dash-kpi-card--hero dash-kpi-card--teal">
                <span class="kpi-icon"><i class="fa fa-users"></i></span>
                <div class="kpi-value" id="kpiTotal">&mdash;</div>
                <div class="kpi-label">Personal activo</div>
            </div>
            <div class="dash-kpi-card dash-kpi-card--purple">
                <span class="kpi-icon"><i class="fa fa-file-text-o"></i></span>
                <div class="kpi-value" id="kpiConIndef">&mdash;</div>
                <div class="kpi-label">Contratos indefinidos</div>
            </div>
            <div class="dash-kpi-card dash-kpi-card--pink">
                <span class="kpi-icon"><i class="fa fa-clock-o"></i></span>
                <div class="kpi-value" id="kpiConAprob">&mdash;</div>
                <div class="kpi-label">En aprobaci&oacute;n</div>
            </div>
        </div>

        <div class="dash-section">
            <p class="dash-section-label"><i class="fa fa-id-card"></i> Perfil del personal</p>
            <div class="row dash-row-cards">
                <div class="col-md-4 col-sm-12">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">Demograf&iacute;a</span><h4 class="dash-card-title">Distribuci&oacute;n por g&eacute;nero</h4></div>
                            <span class="dash-card-icon dash-icon-teal"><i class="fa fa-pie-chart"></i></span>
                        </div>
                        <div class="dash-card-body"><div id="dashSexHost" class="dash-chart-host"><canvas id="chartSex"></canvas></div></div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">Formaci&oacute;n</span><h4 class="dash-card-title">Nivel de estudio</h4></div>
                            <span class="dash-card-icon dash-icon-purple"><i class="fa fa-graduation-cap"></i></span>
                        </div>
                        <div class="dash-card-body"><div id="dashTitHost" class="dash-chart-host"><canvas id="chartTit"></canvas></div></div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">Ubicaci&oacute;n</span><h4 class="dash-card-title">Personal por ciudad</h4></div>
                            <span class="dash-card-icon dash-icon-pink"><i class="fa fa-map-marker"></i></span>
                        </div>
                        <div class="dash-card-body"><div id="dashCiuHost" class="dash-chart-host"><canvas id="chartCiu"></canvas></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-section">
            <p class="dash-section-label"><i class="fa fa-briefcase"></i> Informaci&oacute;n laboral</p>
            <div class="row dash-row-cards">
                <div class="col-md-8 col-sm-12">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">Organizaci&oacute;n</span><h4 class="dash-card-title">Personal por &aacute;rea de trabajo</h4></div>
                            <span class="dash-card-icon dash-icon-blue"><i class="fa fa-building"></i></span>
                        </div>
                        <div class="dash-card-body"><div id="dashAreHost" class="dash-chart-host"><canvas id="chartAre"></canvas></div></div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">N&oacute;mina</span><h4 class="dash-card-title">Ingreso mensual</h4></div>
                            <span class="dash-card-icon dash-icon-green"><i class="fa fa-money"></i></span>
                        </div>
                        <div class="dash-card-body">
                            <p id="dashIngRolRef" class="dash-rol-ref"></p>
                            <div id="dashIngTableWrap"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-section">
            <p class="dash-section-label"><i class="fa fa-file-text-o"></i> Contratos laborales</p>
            <div class="row dash-row-cards">
                <div class="col-md-6 col-sm-12">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">Vigentes</span><h4 class="dash-card-title">Tipo de contrato activo</h4></div>
                            <span class="dash-card-icon dash-icon-teal"><i class="fa fa-bar-chart"></i></span>
                        </div>
                        <div class="dash-card-body">
                            <p class="dash-rol-ref">Indefinidos: fin 31/12/9999. En aprobaci&oacute;n: inicio hasta hoy sin afiliaci&oacute;n IESS activa. Culminados: fecha fin anterior a hoy.</p>
                            <div id="dashConHost" class="dash-chart-host"><canvas id="chartCon"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">Proporci&oacute;n</span><h4 class="dash-card-title">Distribuci&oacute;n de contratos</h4></div>
                            <span class="dash-card-icon dash-icon-purple"><i class="fa fa-pie-chart"></i></span>
                        </div>
                        <div class="dash-card-body"><div id="dashConPieHost" class="dash-chart-host"><canvas id="chartConPie"></canvas></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-section">
            <p class="dash-section-label"><i class="fa fa-road"></i> Movilidad y riesgo social</p>
            <div class="row dash-row-cards">
                <div class="col-md-6 col-sm-12">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">Traslado</span><h4 class="dash-card-title">Tipo de movilizaci&oacute;n</h4></div>
                            <span class="dash-card-icon dash-icon-blue"><i class="fa fa-bus"></i></span>
                        </div>
                        <div class="dash-card-body"><div id="dashMovHost" class="dash-chart-host"><canvas id="chartMov"></canvas></div></div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">Factor social</span><h4 class="dash-card-title">Riesgo social</h4></div>
                            <span class="dash-card-icon dash-icon-orange"><i class="fa fa-exclamation-triangle"></i></span>
                        </div>
                        <div class="dash-card-body"><div id="dashRsoHost" class="dash-chart-host"><canvas id="chartRso"></canvas></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-section">
            <p class="dash-section-label"><i class="fa fa-child"></i> Carga familiar</p>
            <div class="row dash-row-cards">
                <div class="col-md-6 col-sm-12 col-md-offset-3">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">Dependientes</span><h4 class="dash-card-title">Carga familiar declarada</h4></div>
                            <span class="dash-card-icon dash-icon-pink"><i class="fa fa-users"></i></span>
                        </div>
                        <div class="dash-card-body"><div id="dashCarHost" class="dash-chart-host"><canvas id="chartCar"></canvas></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-section">
            <p class="dash-section-label"><i class="fa fa-medkit"></i> Condici&oacute;n m&eacute;dica</p>
            <div class="row dash-row-cards">
                <div class="col-md-6 col-sm-12">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">Cl&iacute;nico</span><h4 class="dash-card-title">Tipo de sangre</h4></div>
                            <span class="dash-card-icon dash-icon-pink"><i class="fa fa-tint"></i></span>
                        </div>
                        <div class="dash-card-body"><div id="dashSanHost" class="dash-chart-host"><canvas id="chartSan"></canvas></div></div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="dash-card-modern">
                        <div class="dash-card-head">
                            <div><span class="dash-card-eyebrow">Evaluaci&oacute;n</span><h4 class="dash-card-title">Tipo condici&oacute;n m&eacute;dica</h4></div>
                            <span class="dash-card-icon dash-icon-teal"><i class="fa fa-stethoscope"></i></span>
                        </div>
                        <div class="dash-card-body"><div id="dashTcfHost" class="dash-chart-host"><canvas id="chartTcf"></canvas></div></div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
<script type="text/javascript">
Chart.register(ChartDataLabels);
(function () {
    var DASH_THEME = {
        teal: '#26a69a',
        purple: '#7e57c2',
        pink: '#ec407a',
        blue: '#42a5f5',
        orange: '#ffa726',
        green: '#66bb6a',
        palette: ['#26a69a', '#7e57c2', '#ec407a', '#42a5f5', '#ffa726', '#66bb6a', '#4db6ac', '#9575cd', '#f48fb1', '#64b5f6', '#ffb74d'],
        sex: ['#26a69a', '#ec407a', '#7e57c2', '#42a5f5'],
        con: ['#7e57c2', '#ec407a', '#94a3b8'],
        tcf: {
            'APTO': '#26a69a',
            'APTO CON OBSERVACION': '#ffa726',
            'APTO CON LIMITACIONES': '#ec407a',
            'NO APTO': '#ef5350',
            '(Sin definir)': '#94a3b8'
        }
    };
    if (Chart.defaults) {
        Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
        Chart.defaults.color = '#64748b';
    }
    function themePalette(n) {
        var out = [];
        for (var i = 0; i < n; i++) {
            out.push(DASH_THEME.palette[i % DASH_THEME.palette.length]);
        }
        return out;
    }
    var chartSex = null;
    var chartTit = null;
    var chartCiu = null;
    var chartMov = null;
    var chartRso = null;
    var chartAre = null;
    var chartCar = null;
    var chartSan = null;
    var chartTcf = null;
    var chartCon = null;
    var chartConPie = null;
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
        compactMaxCategories: 6,
        compactMinW: 280,
        compactMinBarH: 200,
        compactMaxBarH: 360,
        compactPxPerBarV: 52,
        compactPxPerBarH: 34,
        compactPadBar: 56,
        doughnutH: 280,
        doughnutMinW: 260,
        printBarH: 200,
        printDoughnutH: 200,
        printDoughnutW: 200,
        printPageMaxW: 820,
        printSnapshotScale: 2
    };
    var printState = null;
    var printBusy = false;
    var PRINT_HOST_IDS = [
        'dashSexHost', 'dashTitHost', 'dashCiuHost', 'dashMovHost', 'dashRsoHost',
        'dashAreHost', 'dashCarHost', 'dashSanHost', 'dashTcfHost', 'dashConHost', 'dashConPieHost'
    ];
    var PRINT_DOUGHNUT_HOSTS = { dashSexHost: 1, dashConPieHost: 1 };

    var PRINT_CHART_BY_HOST = {
        dashSexHost: function () { return chartSex; },
        dashTitHost: function () { return chartTit; },
        dashCiuHost: function () { return chartCiu; },
        dashMovHost: function () { return chartMov; },
        dashRsoHost: function () { return chartRso; },
        dashAreHost: function () { return chartAre; },
        dashCarHost: function () { return chartCar; },
        dashSanHost: function () { return chartSan; },
        dashTcfHost: function () { return chartTcf; },
        dashConHost: function () { return chartCon; },
        dashConPieHost: function () { return chartConPie; }
    };

    function allChartInstances() {
        return [chartSex, chartTit, chartCiu, chartMov, chartRso, chartAre, chartCar, chartSan, chartTcf, chartCon, chartConPie].filter(function (c) {
            return !!c;
        });
    }

    function captureHostPrintState(hostId) {
        var $host = $('#' + hostId);
        var el = $host[0];
        if (!el) {
            return {
                hostId: hostId,
                width: '',
                height: '',
                minHeight: '',
                maxWidth: '',
                marginLeft: '',
                marginRight: '',
                compact: false,
                doughnutPrint: false,
                barPrint: false,
                frozen: false
            };
        }
        return {
            hostId: hostId,
            width: el.style.width,
            height: el.style.height,
            minHeight: el.style.minHeight,
            maxWidth: el.style.maxWidth,
            marginLeft: el.style.marginLeft,
            marginRight: el.style.marginRight,
            compact: $host.hasClass('dash-chart-host--compact'),
            doughnutPrint: $host.hasClass('dash-chart-host--doughnut-print'),
            barPrint: $host.hasClass('dash-chart-host--bar-print'),
            frozen: $host.hasClass('dash-chart-frozen')
        };
    }

    function applyHostPrintState(state) {
        var $host = $('#' + state.hostId);
        $host.css({
            width: state.width,
            height: state.height,
            minHeight: state.minHeight,
            maxWidth: state.maxWidth,
            marginLeft: state.marginLeft,
            marginRight: state.marginRight
        });
        $host.toggleClass('dash-chart-host--compact', !!state.compact);
        $host.toggleClass('dash-chart-host--doughnut-print', !!state.doughnutPrint);
        $host.toggleClass('dash-chart-host--bar-print', !!state.barPrint);
        $host.toggleClass('dash-chart-frozen', !!state.frozen);
    }

    function escHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function printDisplaySize(hostId) {
        var isDoughnut = !!PRINT_DOUGHNUT_HOSTS[hostId];
        if (isDoughnut) {
            var dSz = printDoughnutDimensions(hostId);
            return { w: dSz.w, h: dSz.h, fullWidth: false };
        }
        return {
            w: printHostColumnWidth(hostId),
            h: printBarHeightForHost(hostId),
            fullWidth: true
        };
    }

    function buildPrintDataTable(chart, pctTotal) {
        if (!chart || !chart.data || !chart.data.labels || !chart.data.datasets.length) {
            return '';
        }
        var labels = chart.data.labels;
        var data = chart.data.datasets[0].data || [];
        var sum = pctTotal > 0 ? pctTotal : 0;
        if (!sum) {
            data.forEach(function (v) { sum += v || 0; });
        }
        var html = '<table class="dash-print-data-table"><thead><tr><th>Categor&iacute;a</th><th>Total</th></tr></thead><tbody>';
        labels.forEach(function (lb, i) {
            var v = parseInt(data[i], 10) || 0;
            var pct = sum ? Math.round(v * 1000 / sum) / 10 : 0;
            var cell = v + (pct ? ' (' + pct + '%)' : '');
            html += '<tr><td>' + escHtml(lb) + '</td><td>' + cell + '</td></tr>';
        });
        html += '</tbody></table>';
        return html;
    }

    function getPrintTableWrapId(hostId) {
        return hostId + 'PrintTable';
    }

    function renderPrintDataTable(hostId, chart) {
        var wrapId = getPrintTableWrapId(hostId);
        var $wrap = $('#' + wrapId);
        if (!$wrap.length) {
            $('#' + hostId).after('<div id="' + wrapId + '" class="dash-print-table-wrap"></div>');
            $wrap = $('#' + wrapId);
        }
        $wrap.html(buildPrintDataTable(chart, 0));
    }

    function clearPrintDataTables() {
        PRINT_HOST_IDS.forEach(function (hostId) {
            $('#' + getPrintTableWrapId(hostId)).empty();
        });
    }

    function captureChartSnapshot(hostId, chart, displayW, displayH, fullWidth) {
        var $host = $('#' + hostId);
        var scale = CHART_SIZE.printSnapshotScale;
        var renderW = Math.max(120, Math.round(displayW * scale));
        var renderH = Math.max(100, Math.round(displayH * scale));
        var dataUrl;

        setCompactHost(hostId, false);
        setChartHostSize(hostId, renderW, renderH, fullWidth);
        chart.resize();

        try {
            dataUrl = chart.toBase64Image('image/png', 1);
        } catch (e) {
            dataUrl = chart.canvas.toDataURL('image/png');
        }

        $host.css({
            width: fullWidth ? '100%' : (displayW + 'px'),
            height: displayH + 'px',
            minHeight: displayH + 'px',
            maxWidth: '100%',
            marginLeft: fullWidth ? '' : 'auto',
            marginRight: fullWidth ? '' : 'auto',
            position: 'relative'
        });

        var $img = $host.find('img.dash-print-snapshot');
        if (!$img.length) {
            $host.append('<img class="dash-print-snapshot" alt="" />');
            $img = $host.find('img.dash-print-snapshot');
        }
        $img.attr('src', dataUrl).css({
            display: 'block',
            width: displayW + 'px',
            height: displayH + 'px',
            maxWidth: '100%',
            margin: '0 auto'
        });

        $host.addClass('dash-chart-frozen');
        $host.find('canvas').css('visibility', 'hidden');
        renderPrintDataTable(hostId, chart);
    }

    function unfreezeChartSnapshots() {
        PRINT_HOST_IDS.forEach(function (hostId) {
            var $host = $('#' + hostId);
            $host.removeClass('dash-chart-frozen');
            $host.find('img.dash-print-snapshot').remove();
            $host.find('canvas').css('visibility', '');
        });
        clearPrintDataTables();
    }

    function printPageWidth() {
        var w = document.documentElement.clientWidth || window.innerWidth || 794;
        return Math.max(620, Math.min(CHART_SIZE.printPageMaxW, w - 24));
    }

    function printHostColumnWidth(hostId) {
        var $col = $('#' + hostId).closest('[class*="col-"]');
        var pageW = printPageWidth();
        if ($col.hasClass('dash-col-fullrow') || $col.hasClass('col-md-8')) {
            return pageW - 16;
        }
        if ($col.hasClass('col-md-4')) {
            return Math.floor(pageW * 0.31) - 12;
        }
        if ($col.hasClass('col-md-6')) {
            return Math.floor(pageW * 0.48) - 12;
        }
        return Math.floor(pageW * 0.48) - 12;
    }

    function chartNeedsFullRow(hostId) {
        if (PRINT_DOUGHNUT_HOSTS[hostId]) {
            var chartD = getChartForHost(hostId);
            if (!chartD || !chartD.data || !chartD.data.labels) {
                return false;
            }
            return chartD.data.labels.length > 5;
        }
        var chart = getChartForHost(hostId);
        if (!chart || !chart.data || !chart.data.labels) {
            return false;
        }
        var labels = chart.data.labels;
        var n = labels.length;
        var $col = $('#' + hostId).closest('[class*="col-"]');
        if ($col.hasClass('col-md-8')) {
            return true;
        }
        var layout = barChartLayout(n, labels, hostId);
        if (layout.horizontal) {
            return true;
        }
        if (n > 6) {
            return true;
        }
        if (maxLabelChars(labels) > 14 && n > 3) {
            return true;
        }
        return false;
    }

    function applyFullRowLayout() {
        PRINT_HOST_IDS.forEach(function (hostId) {
            var $col = $('#' + hostId).closest('[class*="col-"]');
            if (!$col.length) {
                return;
            }
            $col.toggleClass('dash-col-fullrow', chartNeedsFullRow(hostId));
        });
    }

    function resizeChartsForFullRow() {
        applyFullRowLayout();
        PRINT_HOST_IDS.forEach(function (hostId) {
            var chart = getChartForHost(hostId);
            var $host = $('#' + hostId);
            if (!chart || !$host.length) {
                return;
            }
            if (!chartNeedsFullRow(hostId)) {
                return;
            }
            var labels = chart.data.labels || [];
            var n = labels.length;
            var layout = barChartLayout(n, labels, hostId);
            var parentW = hostParentWidth(hostId);
            var h;
            if (PRINT_DOUGHNUT_HOSTS[hostId]) {
                var wD = Math.min(parentW, Math.max(220, Math.round(parentW * 0.45)));
                h = wD;
                setCompactHost(hostId, false);
                setChartHostSize(hostId, wD, h, false);
            } else {
                h = layout.horizontal
                    ? Math.min(620, Math.max(220, n * 38 + 80))
                    : barChartHeight(n, layout.horizontal, labels);
                setCompactHost(hostId, false);
                setChartHostSize(hostId, parentW, h, true);
            }
            try {
                chart.resize();
            } catch (e) {}
        });
    }

    function getChartForHost(hostId) {
        var fn = PRINT_CHART_BY_HOST[hostId];
        return fn ? fn() : null;
    }

    function printBarHeightForHost(hostId) {
        var chart = getChartForHost(hostId);
        if (!chart || !chart.data || !chart.data.labels) {
            return CHART_SIZE.printBarH;
        }
        var labels = chart.data.labels;
        var n = labels.length;
        var layout = barChartLayout(n, labels, hostId);
        var fullRow = $('#' + hostId).closest('.dash-col-fullrow').length > 0;
        if (layout.horizontal || fullRow) {
            return Math.min(fullRow ? 420 : 360, Math.max(180, n * (fullRow ? 36 : 34) + 64));
        }
        if (n <= 2) {
            return 170;
        }
        if (n <= 4) {
            return 195;
        }
        if (n <= 8) {
            return 220;
        }
        return Math.min(280, 185 + n * 10);
    }

    function printDoughnutDimensions(hostId) {
        var $col = $('#' + hostId).closest('[class*="col-"]');
        var colW = printHostColumnWidth(hostId);
        var side = $col.hasClass('col-md-4') ? 188 : 205;
        side = Math.min(side, colW - 8);
        return { w: side, h: side };
    }

    function hostIdForChart(chart) {
        var found = null;
        Object.keys(PRINT_CHART_BY_HOST).forEach(function (id) {
            if (PRINT_CHART_BY_HOST[id]() === chart) {
                found = id;
            }
        });
        return found;
    }

    function resetPrintLayoutHeights() {
        $('.dash-row-cards > [class*="col-"], .dash-card-modern, .dash-card-body').css({
            height: 'auto',
            minHeight: '0',
            maxHeight: 'none'
        });
    }

    function preparePrintCharts() {
        if (printBusy) {
            return;
        }
        printBusy = true;
        try {
            if (!printState) {
                printState = PRINT_HOST_IDS.map(captureHostPrintState);
            }
            resetPrintLayoutHeights();
            applyFullRowLayout();
            $('.dash-topbar').css({ display: 'none', height: 0, margin: 0, padding: 0 });
            $('.dash-row-cards .dash-card-modern').css({ maxWidth: '100%', width: '100%' });
            PRINT_HOST_IDS.forEach(function (hostId) {
                var chart = getChartForHost(hostId);
                var $host = $('#' + hostId);
                if (!chart || !$host.length) {
                    return;
                }
                var isDoughnut = !!PRINT_DOUGHNUT_HOSTS[hostId];
                var size = printDisplaySize(hostId);
                $host.toggleClass('dash-chart-host--doughnut-print', isDoughnut);
                $host.toggleClass('dash-chart-host--bar-print', !isDoughnut);
                if (isDoughnut) {
                    $host.css({ marginLeft: 'auto', marginRight: 'auto' });
                }
                try {
                    captureChartSnapshot(hostId, chart, size.w, size.h, size.fullWidth);
                } catch (e) {
                    if (typeof console !== 'undefined' && console.error) {
                        console.error('captureChartSnapshot', hostId, e);
                    }
                }
            });
        } finally {
            printBusy = false;
        }
    }

    function restorePrintCharts() {
        var i;
        unfreezeChartSnapshots();
        if (printState) {
            for (i = 0; i < printState.length; i++) {
                applyHostPrintState(printState[i]);
            }
            printState = null;
        }
        $('.dash-row-cards > [class*="col-"], .dash-card-modern, .dash-card-body, .dash-topbar').css({
            height: '',
            minHeight: '',
            maxHeight: '',
            display: '',
            margin: '',
            padding: ''
        });
        allChartInstances().forEach(function (chart) {
            try {
                chart.resize();
            } catch (e) {}
        });
        applyFullRowLayout();
        resizeChartsForFullRow();
    }

    function finishPrintMode() {
        if (printBusy) {
            return;
        }
        restorePrintCharts();
        document.body.classList.remove('dash-printing');
    }

    function printDashboard() {
        try {
            printState = null;
            printBusy = false;
            document.body.classList.add('dash-printing');
            var now = new Date();
            $('#dashPrintDate').text(
                'Impreso: ' + now.toLocaleDateString('es-EC') + ' ' +
                now.toLocaleTimeString('es-EC', { hour: '2-digit', minute: '2-digit' })
            );
            $('#dashPrintKpi').text(
                ($('#kpiTotal').text() || '0') + ' personal activo · ' +
                ($('#kpiConIndef').text() || '0') + ' indefinidos · ' +
                ($('#kpiConAprob').text() || '0') + ' en aprobación'
            );
            preparePrintCharts();
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    window.print();
                });
            });
        } catch (err) {
            finishPrintMode();
            if (typeof console !== 'undefined' && console.error) {
                console.error('printDashboard', err);
            }
            if (typeof $.alert === 'function') {
                $.alert('No se pudo abrir la impresi&oacute;n.');
            } else {
                alert('No se pudo abrir la impresion.');
            }
        }
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

    /** Ancho del canvas según cantidad de categorías (pocos datos = más compacto) */
    function compactChartWidth(categoryCount, parentMaxW) {
        var n = Math.max(categoryCount, 1);
        parentMaxW = parentMaxW || CHART_SIZE.maxBarW;
        return Math.min(parentMaxW, Math.max(CHART_SIZE.compactMinW, n * CHART_SIZE.compactPxPerCat + CHART_SIZE.compactPadW));
    }

    function compactBarHeight(categoryCount, horizontal, labels) {
        var n = Math.max(categoryCount, 1);
        if (horizontal) {
            return Math.min(CHART_SIZE.compactMaxBarH, Math.max(CHART_SIZE.compactMinBarH, n * CHART_SIZE.compactPxPerBarH + CHART_SIZE.compactPadBar));
        }
        var longLbl = maxLabelChars(labels) > 18;
        var extra = longLbl ? 24 : 0;
        return Math.min(CHART_SIZE.compactMaxBarH, Math.max(CHART_SIZE.compactMinBarH, 150 + n * CHART_SIZE.compactPxPerBarV + extra));
    }

    function shouldAutoCompact(categoryCount, layout, chartOpts) {
        if (chartOpts.forceFull) {
            return false;
        }
        if (chartOpts.compact === true) {
            return true;
        }
        if (chartOpts.compact === false) {
            return false;
        }
        var n = Math.max(categoryCount, 1);
        if (layout && layout.horizontal) {
            return false;
        }
        return n <= CHART_SIZE.compactMaxCategories;
    }

    /** Barras: ancho completo solo con muchas categorías */
    function barChartWidth(categoryCount, horizontal, labels, parentMaxW, compact) {
        if (compact) {
            return compactChartWidth(categoryCount, parentMaxW);
        }
        return parentMaxW || CHART_SIZE.maxBarW;
    }

    function doughnutChartWidth(sliceCount, parentMaxW, compact) {
        parentMaxW = parentMaxW || 360;
        if (compact) {
            return Math.min(parentMaxW, Math.max(200, sliceCount * 72 + 120));
        }
        return Math.min(parentMaxW, Math.max(CHART_SIZE.doughnutMinW, Math.round(parentMaxW * 0.88)));
    }

    function doughnutChartHeight(sliceCount, widthPx, compact) {
        if (compact) {
            return Math.max(200, Math.min(widthPx, 260));
        }
        return CHART_SIZE.doughnutH;
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

    function barChartLayout(categoryCount, labels, hostId) {
        var n = Math.max(categoryCount, 1);
        var longLbl = maxLabelChars(labels) > 20;
        var parentW = hostId ? hostParentWidth(hostId) : 900;
        var narrow = parentW < 420;
        /* Horizontal en columnas estrechas o con muchas categorías: etiquetas legibles */
        var horizontal = n > 8 || (n > 6 && longLbl) || (narrow && n > 2);
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
            marginLeft: 'auto',
            marginRight: 'auto'
        };
        if (fullWidth) {
            css.width = '100%';
        } else {
            css.width = widthPx + 'px';
        }
        $('#' + hostId).css(css);
    }

    function setCompactHost(hostId, compact) {
        $('#' + hostId).toggleClass('dash-chart-host--compact', !!compact);
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
        [chartSex, chartTit, chartCiu, chartMov, chartRso, chartAre, chartCar, chartSan, chartTcf, chartCon, chartConPie].forEach(function (c) {
            if (c) {
                c.destroy();
            }
        });
        chartSex = chartTit = chartCiu = chartMov = chartRso = chartAre = chartCar = chartSan = chartTcf = chartCon = chartConPie = null;
    }

    function createBarChart(hostId, canvasId, labels, data, palette, datasetLabel, chartOpts) {
        chartOpts = chartOpts || {};
        var n = labels.length;
        var layout = barChartLayout(n, labels, hostId);
        var parentW = hostParentWidth(hostId);
        var compact = shouldAutoCompact(n, layout, chartOpts);
        var w = barChartWidth(n, layout.horizontal, labels, parentW, compact);
        if (compact) {
            if (!layout.horizontal && n <= 8) {
                layout.barPercentage = 0.42;
                layout.categoryPercentage = 0.62;
            }
        }
        var h = compact ? compactBarHeight(n, layout.horizontal, labels) : barChartHeight(n, layout.horizontal, labels);
        setCompactHost(hostId, compact);
        var longLbl = maxLabelChars(labels) > 18;
        var ctx = resetChartHost(hostId, canvasId, w, h, !compact);
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
                    right: compact ? 6 : 14,
                    bottom: layout.horizontal ? 10 : (longLbl ? 28 : 16),
                    left: compact ? 6 : (layout.horizontal ? 8 : 10)
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
                    font: { weight: '600', size: n > 12 ? 10 : 11, family: "'Inter', sans-serif" },
                    color: '#475569',
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
            opts.scales.x = {
                beginAtZero: true,
                ticks: { precision: 0, padding: 4, color: '#94a3b8' },
                grid: { color: '#f1f5f9' }
            };
            opts.scales.y = {
                ticks: {
                    autoSkip: false,
                    font: { size: 11 },
                    padding: 8,
                    color: '#64748b',
                    callback: function (val, idx) {
                        return displayLabels[idx] != null ? displayLabels[idx] : val;
                    }
                },
                grid: { color: '#f1f5f9' }
            };
        } else {
            opts.scales.y = {
                beginAtZero: true,
                ticks: { precision: 0, padding: 4, color: '#94a3b8' },
                grid: { color: '#f1f5f9' }
            };
            opts.scales.x = {
                ticks: {
                    autoSkip: n > 14,
                    maxTicksLimit: n > 14 ? 14 : undefined,
                    maxRotation: longLbl || n > 6 ? 40 : 0,
                    minRotation: longLbl || n > 6 ? 30 : 0,
                    font: { size: 11, weight: '600' },
                    padding: 6,
                    color: '#475569',
                    callback: function (val, idx) {
                        return displayLabels[idx] != null ? displayLabels[idx] : val;
                    }
                },
                grid: { display: false }
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
                    borderWidth: 0,
                    borderRadius: layout.horizontal ? 4 : 6,
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

    var CON_TYPES_DEFAULT = [
        { con_tipo: 'indefinido', con_des: 'Contratos indefinidos', total: 0 },
        { con_tipo: 'aprobacion', con_des: 'En aprobaci\u00f3n', total: 0 },
        { con_tipo: 'culminado', con_des: 'Contratos culminados', total: 0 }
    ];

    var SAN_ORDER = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', '(Sin definir)'];

    function sortSanRows(rows) {
        function rank(label) {
            var lb = String(label || '').trim();
            var i, idx = SAN_ORDER.indexOf(lb);
            if (idx >= 0) {
                return idx;
            }
            var norm = lb.toUpperCase().replace(/\s+/g, '');
            for (i = 0; i < SAN_ORDER.length; i++) {
                if (SAN_ORDER[i].toUpperCase() === norm) {
                    return i;
                }
            }
            return SAN_ORDER.length + 1;
        }
        return (rows || []).slice().sort(function (a, b) {
            var ra = rank(a.san_des);
            var rb = rank(b.san_des);
            if (ra !== rb) {
                return ra - rb;
            }
            return String(a.san_des || '').localeCompare(String(b.san_des || ''));
        });
    }

    function mergeConTypes(rows) {
        var map = {};
        CON_TYPES_DEFAULT.forEach(function (r) {
            map[r.con_tipo] = { con_tipo: r.con_tipo, con_des: r.con_des, total: 0 };
        });
        (rows || []).forEach(function (row) {
            var key = row.con_tipo || '';
            if (map[key]) {
                map[key].total = parseInt(row.total, 10) || 0;
                if (row.con_des) {
                    map[key].con_des = row.con_des;
                }
            }
        });
        return CON_TYPES_DEFAULT.map(function (r) {
            return map[r.con_tipo];
        });
    }

    function createDoughnutChart(hostId, canvasId, labels, data, colors) {
        var compact = labels.length <= CHART_SIZE.compactMaxCategories;
        var w = doughnutChartWidth(labels.length, hostParentWidth(hostId), compact);
        var h = doughnutChartHeight(labels.length, w, compact);
        setCompactHost(hostId, compact);
        var ctx = resetChartHost(hostId, canvasId, w, h, false);
        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: labels.map(function (_, i) { return colors[i % colors.length]; }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: {
                    datalabels: {
                        display: function (ctx) {
                            var v = ctx.dataset.data[ctx.dataIndex] || 0;
                            return pctOfTotal(v, ctx.dataset.data) >= 4;
                        },
                        color: '#fff',
                        font: { weight: '600', size: 12, family: "'Inter', sans-serif" },
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
                                        text: label + ' \u2014 ' + value + ' (' + pct + '%)',
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
    }

    function renderIngTable(rows) {
        var html = '<table class="dash-ing-table"><thead><tr><th>RANGO MENSUAL</th><th>TOTAL</th></tr></thead><tbody>';
        rows.forEach(function (row) {
            html += '<tr><td>' + (row.rango_des || '') + '</td><td>' + (parseInt(row.total, 10) || 0) + '</td></tr>';
        });
        html += '</tbody></table>';
        $('#dashIngTableWrap').html(html);
    }

    function setUltimoRolRef(roles, meta) {
        var $ref = $('#dashIngRolRef');
        meta = meta || {};
        var parts = [];
        if (meta.periodoAnio) {
            parts.push('Periodo ' + meta.periodoAnio);
        }
        if (meta.refMesLabel) {
            parts.push(meta.refMesLabel);
        }
        if (meta.totalAreas > 0) {
            parts.push(meta.totalAreas + ' \u00e1rea' + (meta.totalAreas === 1 ? '' : 's'));
        }
        var list = Array.isArray(roles) ? roles : (roles && roles.Rol_Cod ? [roles] : []);
        list = list.filter(function (rol) { return rol && rol.Rol_Cod; });
        if (!parts.length && !list.length) {
            $ref.text('Sin datos de n\u00f3mina en el \u00faltimo periodo.');
            return;
        }
        if (!list.length) {
            $ref.text(parts.join(' \u00b7 ') + '. Sin roles con total de ingresos.');
            return;
        }
        $ref.text(parts.join(' \u00b7 ') + '.');
    }

    function loadDashboard() {
        $.get(UrlSaveJson, { dashPersonalAjax: 1 }, function (res) {
            if (!res || !res.success) {
                if (typeof $.alert === 'function') $.alert('No se pudo cargar el dashboard.'); else alert('No se pudo cargar el dashboard.');
                return;
            }
            var tot = res.totalPersonal != null ? res.totalPersonal : 0;
            var conKpi = mergeConTypes(res.byCon || []);
            $('#kpiTotal').text(tot);
            $('#kpiConIndef').text(conKpi[0] ? (parseInt(conKpi[0].total, 10) || 0) : 0);
            $('#kpiConAprob').text(conKpi[1] ? (parseInt(conKpi[1].total, 10) || 0) : 0);

            if (tot === 0) {
                destroyAllCharts();
                $('#kpiConIndef').text('0');
                $('#kpiConAprob').text('0');
                var msg0 = '<p class="text-muted text-center" style="padding:40px 10px;">No hay personal activo para esta empresa.</p>';
                ['dashSexHost', 'dashTitHost', 'dashCiuHost', 'dashMovHost', 'dashRsoHost', 'dashAreHost', 'dashCarHost', 'dashSanHost', 'dashTcfHost', 'dashConHost', 'dashConPieHost'].forEach(function (id) {
                    $('#' + id).removeClass('dash-chart-host--compact').html(msg0);
                });
                $('#dashIngTableWrap').empty();
                $('#dashIngRolRef').text('');
                return;
            }

            destroyAllCharts();

            var sexRows = res.bySex || [];
            var labelsS = [];
            var dataS = [];
            var colorsS = DASH_THEME.sex;
            sexRows.forEach(function (row) {
                labelsS.push(labelSex(row.Prs_Sex));
                dataS.push(parseInt(row.total, 10) || 0);
            });
            if (labelsS.length === 0) {
                labelsS.push('Sin clasificar');
                dataS.push(tot);
            }

            var wSex = doughnutChartWidth(labelsS.length, hostParentWidth('dashSexHost'), labelsS.length <= CHART_SIZE.compactMaxCategories);
            var hSex = doughnutChartHeight(labelsS.length, wSex, labelsS.length <= CHART_SIZE.compactMaxCategories);
            setCompactHost('dashSexHost', labelsS.length <= CHART_SIZE.compactMaxCategories);
            var ctxS = resetChartHost('dashSexHost', 'chartSex', wSex, hSex, false);
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
                    cutout: '58%',
                    plugins: {
                        datalabels: {
                            display: function (ctx) {
                                var v = ctx.dataset.data[ctx.dataIndex] || 0;
                                return pctOfTotal(v, ctx.dataset.data) >= 4;
                            },
                            color: '#fff',
                            font: { weight: '600', size: 12, family: "'Inter', sans-serif" },
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
            chartTit = createBarChart('dashTitHost', 'chartTit', labelsT, dataT, themePalette(labelsT.length), 'Empleados', {
                showPct: true,
                pctTotal: tot,
                forceFull: labelsT.length > CHART_SIZE.compactMaxCategories
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
            chartCiu = createBarChart('dashCiuHost', 'chartCiu', labelsC, dataC, themePalette(labelsC.length), 'Empleados', {
                forceFull: labelsC.length > CHART_SIZE.compactMaxCategories
            });

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
            chartMov = createBarChart('dashMovHost', 'chartMov', labelsM, dataM, themePalette(labelsM.length), 'Empleados');

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
            chartRso = createBarChart('dashRsoHost', 'chartRso', labelsR, dataR, themePalette(labelsR.length), 'Empleados');

            var areRows = res.byAre || [];
            var labelsA = [];
            var dataA = [];
            areRows.forEach(function (row) {
                labelsA.push(row.are_des || '(Sin area)');
                dataA.push(parseInt(row.total, 10) || 0);
            });
            if (labelsA.length === 0) {
                labelsA.push('Sin datos');
                dataA.push(0);
            }
            chartAre = createBarChart('dashAreHost', 'chartAre', labelsA, dataA, themePalette(labelsA.length), 'Empleados', {
                forceFull: labelsA.length > CHART_SIZE.compactMaxCategories
            });

            var conRows = mergeConTypes(res.byCon || []);
            var labelsCon = [];
            var dataCon = [];
            var totCon = 0;
            conRows.forEach(function (row) {
                labelsCon.push(row.con_des);
                var n = parseInt(row.total, 10) || 0;
                dataCon.push(n);
                totCon += n;
            });
            chartCon = createBarChart('dashConHost', 'chartCon', labelsCon, dataCon, DASH_THEME.con, 'Contratos', {
                showPct: true,
                pctTotal: totCon
            });
            chartConPie = createDoughnutChart('dashConPieHost', 'chartConPie', labelsCon, dataCon, DASH_THEME.con);

            var carRows = res.byCar || [];
            var labelsCar = [];
            var dataCar = [];
            carRows.forEach(function (row) {
                labelsCar.push(row.car_des || row.Per_Car_Cod || '(Sin definir)');
                dataCar.push(parseInt(row.total, 10) || 0);
            });
            if (labelsCar.length === 0) {
                labelsCar.push('Sin datos');
                dataCar.push(0);
            }
            chartCar = createBarChart('dashCarHost', 'chartCar', labelsCar, dataCar, themePalette(labelsCar.length), 'Empleados');

            var sanRows = sortSanRows(res.bySan || []);
            var labelsSan = [];
            var dataSan = [];
            var totSan = 0;
            sanRows.forEach(function (row) {
                labelsSan.push(row.san_des || '(Sin definir)');
                var n = parseInt(row.total, 10) || 0;
                dataSan.push(n);
                totSan += n;
            });
            if (labelsSan.length === 0) {
                labelsSan.push('Sin datos');
                dataSan.push(0);
            }
            chartSan = createBarChart('dashSanHost', 'chartSan', labelsSan, dataSan, themePalette(labelsSan.length), 'Empleados', {
                showPct: true,
                pctTotal: totSan
            });

            var tcfRows = res.byTcf || [];
            var labelsTcf = [];
            var dataTcf = [];
            var totTcf = 0;
            var paletteTcf = [];
            tcfRows.forEach(function (row) {
                var lb = row.tcf_des || row.Per_Tcf_Cod || '(Sin definir)';
                labelsTcf.push(lb);
                var n = parseInt(row.total, 10) || 0;
                dataTcf.push(n);
                totTcf += n;
                paletteTcf.push(DASH_THEME.tcf[lb] || DASH_THEME.purple);
            });
            if (labelsTcf.length === 0) {
                labelsTcf.push('Sin datos');
                dataTcf.push(0);
                paletteTcf.push('#94a3b8');
            }
            chartTcf = createBarChart('dashTcfHost', 'chartTcf', labelsTcf, dataTcf, paletteTcf, 'Empleados', {
                showPct: true,
                pctTotal: totTcf
            });

            setUltimoRolRef(res.ultimosRoles || res.ultimoRol || [], res.ingresoMeta || null);
            renderIngTable(mergeIngRanges(res.byIng || []));
            resizeChartsForFullRow();
        }, 'json').fail(function () {
            if (typeof $.alert === 'function') $.alert('Error de comunicaci&oacute;n al cargar datos.'); else alert('Error de comunicacion al cargar datos.');
        });
    }

    $(function () {
        loadDashboard();
        $('#btnPrintDash').on('click', function (e) {
            e.preventDefault();
            printDashboard();
        });
        var resizeTimer;
        $(window).on('resize', function () {
            if (document.body.classList.contains('dash-printing')) {
                return;
            }
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                allChartInstances().forEach(function (c) {
                    c.resize();
                });
            }, 200);
        });
        window.addEventListener('afterprint', finishPrintMode);
    });
})();
</script>
</body>
</html>
