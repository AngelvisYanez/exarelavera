<?php

/**
 * Listado de mensajes manifiesto_mensajes (BD) y grid local.
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_chats.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

if (!function_exists('relavera_chats_h')) {
    /**
     * @param mixed $s
     * @return string
     */
    function relavera_chats_h($s)
    {
        return htmlspecialchars((string) ($s === null ? '' : $s), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Parámetros de filtro mensajes desde $_GET (listado y gráficos).
 *
 * @return array
 */
function relavera_chats_lista_param_desde_get()
{
    $param = array();
    $pla = isset($_GET['Pla_Cod']) ? (int) $_GET['Pla_Cod'] : 0;
    if ($pla > 0) {
        $param['Pla_Cod'] = $pla;
    }
    $tiposMsjPermitidos = array('SCH', 'SVH', 'SPL', 'DAN', 'CAN', 'TRE');
    $msjTip = isset($_GET['Msj_Tip']) ? strtoupper(trim((string) $_GET['Msj_Tip'])) : '';
    if ($msjTip !== '' && in_array($msjTip, $tiposMsjPermitidos, true)) {
        $param['Msj_Tip'] = $msjTip;
    }
    $fecDesde = relavera_chats_fecha_filtro_sql(isset($_GET['Msj_Fec_Desde']) ? $_GET['Msj_Fec_Desde'] : '');
    $fecHasta = relavera_chats_fecha_filtro_sql(isset($_GET['Msj_Fec_Hasta']) ? $_GET['Msj_Fec_Hasta'] : '');
    if ($fecDesde !== '') {
        $param['Msj_Fec_Desde'] = $fecDesde;
    }
    if ($fecHasta !== '') {
        $param['Msj_Fec_Hasta'] = $fecHasta;
    }
    $tipBus = isset($_GET['Msj_Prs_Bus_Tip']) ? strtoupper(trim((string) $_GET['Msj_Prs_Bus_Tip'])) : '';
    $prsTex = isset($_GET['Msj_Prs_Bus_Tex']) ? trim((string) $_GET['Msj_Prs_Bus_Tex']) : '';
    if (strlen($prsTex) > 120) {
        $prsTex = substr($prsTex, 0, 120);
    }
    if ($prsTex !== '') {
        $param['Msj_Prs_Bus_Tex'] = $prsTex;
        if ($tipBus !== 'CHO' && $tipBus !== 'AP') {
            $tipBus = 'CHO';
        }
        $param['Msj_Prs_Bus_Tip'] = $tipBus;
    }
    return $param;
}

if (!empty($_GET['listManifiestoMensajesAjax'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $param = relavera_chats_lista_param_desde_get();
    $obBD_conexion = new Class_Log_Conexion_Chats($Ses_Dat_Dis);
    $obBD_con1 = new Class_Log_Datos_Chats();
    $rows = $obBD_con1->getArrayConsulta(2, $param, $obBD_conexion);
    if (!is_array($rows)) {
        $rows = array();
    }
    $obBD_con1->echoJson(array(
        'success' => true,
        'rows' => $rows,
        'total' => count($rows),
    ));
}

if (!empty($_GET['listManifiestoMensajesChartAjax'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $param = relavera_chats_lista_param_desde_get();
    $obBD_conexion = new Class_Log_Conexion_Chats($Ses_Dat_Dis);
    $obBD_con1 = new Class_Log_Datos_Chats();
    $porPlanta = $obBD_con1->getArrayConsulta(4, $param, $obBD_conexion);
    $porTipo = $obBD_con1->getArrayConsulta(5, $param, $obBD_conexion);
    if (!is_array($porPlanta)) {
        $porPlanta = array();
    }
    if (!is_array($porTipo)) {
        $porTipo = array();
    }
    $obBD_con1->echoJson(array(
        'success' => true,
        'porPlanta' => $porPlanta,
        'porTipo' => $porTipo,
        'rows' => $porPlanta,
        'total' => count($porPlanta),
    ));
}

$obBD_conexion_pla = new Class_Log_Conexion_Chats($Ses_Dat_Dis);
$obBD_pla = new Class_Log_Datos_Chats();
$lista_plantas = $obBD_pla->getArrayConsulta(3, array(), $obBD_conexion_pla);
if (!is_array($lista_plantas)) {
    $lista_plantas = array();
}
$obBD_pla->utf8_change_param($lista_plantas);

$obBD_conexion_rep = new Class_Log_Conexion_Chats($Ses_Dat_Dis);
$obBD_rep = new Class_Log_Datos_Chats();

/**
 * Formulario de filtros (listado o gráficos). $sufijo en ids: lista | graficos.
 *
 * @param string $sufijo
 * @param array $lista_plantas
 * @param string $idBotonBuscar
 * @param string $tituloFieldset
 * @return void
 */
function relavera_chats_lista_html_formulario_filtros($sufijo, $lista_plantas, $idBotonBuscar, $tituloFieldset)
{
    $s = preg_replace('/[^a-z0-9_]/i', '', $sufijo);
    if ($s === '') {
        $s = 'lista';
    }
    $idForm = 'form_filtros_mensajes_' . $s;
    $idPla = 'filtro_pla_cod_' . $s;
    $idTip = 'filtro_msj_tip_' . $s;
    $idCho = 'filtro_prs_bus_cho_' . $s;
    $idAp = 'filtro_prs_bus_ap_' . $s;
    $idTex = 'filtro_msj_prs_bus_tex_' . $s;
    $idFd = 'filtro_msj_fec_desde_' . $s;
    $idFh = 'filtro_msj_fec_hasta_' . $s;
    $nameRadio = 'Msj_Prs_Bus_Tip_' . $s;
    $fecDesdeDef = date('Y-m-01');
    $fecHastaDef = date('Y-m-t');
    ?>
    <div class="filtros-bar">
        <p style="margin: 0 0 10px; font-size: 12px; color: #6c757d; font-weight: 600;">
            <i class="glyphicon glyphicon-filter"></i> <?php echo relavera_chats_h($tituloFieldset); ?>
        </p>
        <form class="chats-filtros-form" id="<?php echo relavera_chats_h($idForm); ?>" action="javascript:void(0)"
            onsubmit="return false;">
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-3">
                    <label for="<?php echo relavera_chats_h($idFd); ?>">Fecha envío desde</label>
                    <input type="date" id="<?php echo relavera_chats_h($idFd); ?>" name="Msj_Fec_Desde"
                        class="form-control input-sm" autocomplete="off"
                        value="<?php echo relavera_chats_h($fecDesdeDef); ?>" />
                </div>
                <div class="col-xs-12 col-sm-6 col-md-3">
                    <label for="<?php echo relavera_chats_h($idFh); ?>">Fecha envío hasta</label>
                    <input type="date" id="<?php echo relavera_chats_h($idFh); ?>" name="Msj_Fec_Hasta"
                        class="form-control input-sm" autocomplete="off"
                        value="<?php echo relavera_chats_h($fecHastaDef); ?>" />
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <label for="<?php echo relavera_chats_h($idPla); ?>">Planta</label>
                    <select id="<?php echo relavera_chats_h($idPla); ?>" name="Pla_Cod"
                        class="form-control input-sm chats-sel-planta" style="width:100%;">
                        <option value="">Todas</option>
                        <?php
                        foreach ($lista_plantas as $p) {
                            $cod = isset($p['Pla_Cod']) ? (int) $p['Pla_Cod'] : 0;
                            if ($cod <= 0) {
                                continue;
                            }
                            $nom = relavera_chats_h(isset($p['Pla_Nom']) ? $p['Pla_Nom'] : '');
                            echo '<option value="' . $cod . '">' . $nom . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-2">
                    <label for="<?php echo relavera_chats_h($idTip); ?>">Tipo mensaje</label>
                    <select id="<?php echo relavera_chats_h($idTip); ?>" name="Msj_Tip" class="form-control input-sm">
                        <option value="">Todos</option>
                        <option value="SCH">SCH — Sanción chofer</option>
                        <option value="SVH">SVH — Sanción vehículo</option>
                        <option value="SPL">SPL — Sanción planta</option>
                        <option value="DAN">DAN — Depósito anticipo</option>
                        <option value="CAN">CAN — Confirmación anticipo</option>
                        <option value="TRE">TRE — Tiempo Relavera</option>
                    </select>
                </div>
            </div>
            <div class="row" style="margin-top: 10px;">
                <div class="col-xs-12 col-md-10">
                    <label>Buscar persona</label>
                    <div class="chats-radio-inline">
                        <label class="radio-inline">
                            <input id="<?php echo relavera_chats_h($idCho); ?>"
                                name="<?php echo relavera_chats_h($nameRadio); ?>" type="radio" value="CHO"
                                checked="checked" /> Chofer (nombre o cédula)
                        </label>
                        <label class="radio-inline">
                            <input id="<?php echo relavera_chats_h($idAp); ?>"
                                name="<?php echo relavera_chats_h($nameRadio); ?>" type="radio" value="AP" /> Administrador
                            de planta
                        </label>
                    </div>
                    <input type="text" id="<?php echo relavera_chats_h($idTex); ?>" name="Msj_Prs_Bus_Tex" maxlength="120"
                        placeholder="Texto de búsqueda…" class="form-control input-sm" style="margin-top: 6px;"
                        autocomplete="off" />
                </div>
                <div class="col-xs-12 col-md-2 chats-filtros-buscar-cell">
                    <button type="button" id="<?php echo relavera_chats_h($idBotonBuscar); ?>"
                        class="btn btn-success btn-sm" title="Aplicar filtros" tabindex="-1">
                        <i class="glyphicon glyphicon-search"></i> Buscar
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Mensajes manifiesto (BD)</title>
    <meta charset="UTF-8">
    <?php require_once('../../mascaras/model1/estilos/jqgrid5.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
    <style>
        /* Alineado con dashboard_relavera.php */
        .panel-main {
            margin: 24px;
        }

        .panel-main .exa-body {
            padding: 24px 30px !important;
        }

        .bd-msj-wrap .exa-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%);
            color: #fff;
            padding: 8px 15px;
            border-radius: 4px 4px 0 0;
        }

        .bd-msj-wrap .exa-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .bd-msj-wrap .exa-header .glyphicon {
            margin-right: 8px;
            color: #fff;
        }

        .filtros-bar {
            background: #f8f9fa;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        }

        .filtros-bar label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 4px;
        }

        .filtros-bar .form-control {
            border-radius: 6px;
            border: 1px solid #ced4da;
            box-shadow: none;
            transition: all 0.2s ease-in-out;
            height: 32px;
        }

        .filtros-bar .form-control:focus {
            border-color: #2C5D94;
            box-shadow: 0 0 0 3px rgba(44, 93, 148, 0.15) !important;
            outline: 0;
        }

        .chats-radio-inline {
            display: flex;
            gap: 20px;
            align-items: center;
            margin: 5px 0 8px;
        }

        .chats-radio-inline .radio-inline {
            display: inline-flex !important;
            align-items: center;
            margin: 0 !important;
            padding-left: 0;
            font-weight: 500;
            cursor: pointer;
            color: #495057;
            font-size: 13px;
        }

        .chats-radio-inline .radio-inline input[type="radio"] {
            margin: 0 6px 0 0 !important;
            position: relative;
            top: auto;
        }

        .chats-filtros-buscar-cell {
            padding-top: 22px;
        }

        @media (min-width: 992px) {
            .chats-filtros-form .row {
                display: flex;
                align-items: flex-end;
            }

            .chats-filtros-buscar-cell {
                padding-top: 0 !important;
                margin-bottom: 0;
            }

            .chats-filtros-buscar-cell .btn {
                width: 100%;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
            }
        }

        @media (max-width: 767px) {
            .chats-filtros-buscar-cell {
                padding-top: 8px;
                text-align: center;
            }
        }

        .chats-radio-inline {
            margin-bottom: 6px;
        }

        .chats-radio-inline .radio-inline {
            margin-right: 12px;
            font-size: 12px;
            font-weight: normal;
        }

        .chats-tabs-strip {
            background: #f8f9fa;
            padding: 0 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
        }

        .chats-tabs-strip .nav-tabs {
            border-bottom: none;
            margin-bottom: 0;
        }

        .chats-tabs-strip .nav-tabs>li>a {
            border-radius: 4px 4px 0 0;
            font-weight: 600;
            font-size: 13px;
            color: #495057;
        }

        .chats-tabs-strip .nav-tabs>li.active>a,
        .chats-tabs-strip .nav-tabs>li.active>a:hover,
        .chats-tabs-strip .nav-tabs>li.active>a:focus {
            color: #2C5D94;
            border: 1px solid #dee2e6;
            border-bottom-color: transparent;
            background: #fff;
        }

        .bd-msj-tex {
            max-width: 420px;
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 11px;
        }

        .bd-msj-wrap {
            margin-bottom: 18px;
        }

        #gbox_gridMensajesManifiesto .ui-jqgrid-titlebar {
            font-size: 12px;
        }

        .chats-tab-content {
            padding-top: 0;
        }

        .chats-tabs-graficos {
            min-height: 120px;
            padding-top: 10px;
        }

        /* Estilos y espaciados premium para los paneles de gráficos y tablas */
        .chats-graficos-charts-row .panel,
        .chats-resumen-tablas-wrap .panel {
            margin-bottom: 24px;
            border-color: #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            background: #ffffff;
            transition: all 0.3s ease;
        }

        .chats-graficos-charts-row .panel:hover,
        .chats-resumen-tablas-wrap .panel:hover {
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        .chats-graficos-charts-row .panel-heading,
        .chats-resumen-tablas-wrap .panel-heading {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 600;
            color: #2C5D94;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .chats-graficos-charts-row .panel-body {
            padding: 20px 20px 15px 20px !important;
        }

        .chats-chart-wrap {
            position: relative;
            height: 380px;
            max-width: 100%;
            padding: 12px;
            box-sizing: border-box;
        }

        .chats-chart-wrap--tipo {
            height: 380px;
            padding: 25px;
            /* Más padding para el gráfico circular para evitar que se vea apretado */
        }

        .chats-meta-line {
            font-size: 12px;
            color: #6c757d;
            margin: 8px 0 12px;
            padding: 8px 0 0;
            border-top: 1px solid #dee2e6;
        }

        .chats-resumen-tablas-wrap .panel-body {
            padding: 18px 20px !important;
        }

        .chats-resumen-tabla {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
            margin-bottom: 0;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            overflow: hidden;
        }

        .chats-resumen-tabla th {
            background: #2C5D94;
            color: #fff;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .chats-resumen-tabla th.text-right,
        .chats-resumen-tabla td.text-right {
            text-align: right;
        }

        .chats-resumen-tabla td {
            padding: 12px 16px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
            background: #fff;
        }

        .chats-resumen-tabla tbody tr:hover {
            background: #f8f9fa;
        }

        .chats-resumen-tabla tfoot td {
            background: #e9ecef;
            border-top: 2px solid #dee2e6;
            font-size: 12px;
        }

        /* Scrollbar elegante para la tabla */
        .chats-table-scroll {
            max-height: 310px;
            overflow-y: auto;
            position: relative;
        }

        .chats-table-scroll tfoot td {
            position: -webkit-sticky;
            position: sticky;
            bottom: 0;
            z-index: 10;
            background: #e9ecef !important;
            border-top: 2px solid #dee2e6 !important;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.05);
        }

        .chats-table-scroll thead th {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            z-index: 10;
            background: #2C5D94 !important;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .chats-table-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .chats-table-scroll::-webkit-scrollbar-track {
            background: #f8f9fa;
            border-radius: 4px;
        }

        .chats-table-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .chats-table-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Tarjetas KPI para tipos de mensajes */
        .chats-kpi-grid {
            margin: -5px;
        }

        .chats-kpi-col {
            padding: 5px;
        }

        .chats-kpi-card {
            border-radius: 8px;
            padding: 15px 10px;
            text-align: center;
            color: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            min-height: 105px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .chats-kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
        }

        .chats-kpi-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.85;
            margin-bottom: 2px;
        }

        .chats-kpi-value {
            font-size: 26px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 3px;
        }

        .chats-kpi-desc {
            font-size: 10px;
            opacity: 0.9;
            font-weight: 600;
            line-height: 1.2;
        }

        /* Colores degradados modernos de acuerdo a los tipos */
        .chats-kpi-card--sch {
            background: linear-gradient(135deg, #d9534f 0%, #b52b27 100%);
        }

        .chats-kpi-card--svh {
            background: linear-gradient(135deg, #f0ad4e 0%, #d58512 100%);
        }

        .chats-kpi-card--spl {
            background: linear-gradient(135deg, #9370db 0%, #6f42c1 100%);
        }

        .chats-kpi-card--dan {
            background: linear-gradient(135deg, #337ab7 0%, #204d74 100%);
        }

        .chats-kpi-card--can {
            background: linear-gradient(135deg, #5cb85c 0%, #398439 100%);
        }

        .chats-kpi-card--tre {
            background: linear-gradient(135deg, #5bc0de 0%, #269abc 100%);
        }

        /* Tarjeta de Total General */
        .chats-kpi-total-card {
            background: linear-gradient(135deg, #2C5D94 0%, #1a3c61 100%);
            border-radius: 8px;
            padding: 16px 20px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(44, 93, 148, 0.25);
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .chats-kpi-total-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(44, 93, 148, 0.35);
        }

        .chats-kpi-total-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chats-kpi-total-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }

        .chats-kpi-total-label .glyphicon {
            margin-right: 6px;
        }

        .chats-kpi-total-value {
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
        }

        .chats-resumen-tfoot-total td {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .chats-chart-meta-row {
            margin: 0;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="panel panel-main bd-msj-wrap">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">
                <i class="glyphicon glyphicon-envelope"></i> Mensajes de WhatsApp
            </h3>
        </div>

        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">

            <div class="chats-tabs-strip">
                <ul class="nav nav-tabs chats-tabs-nav" role="tablist" id="chats_mensajes_tabs">
                    <li role="presentation" class="active">
                        <a href="#tab_chats_listado" aria-controls="tab_chats_listado" role="tab"
                            data-toggle="tab">Listado</a>
                    </li>
                    <li role="presentation">
                        <a href="#tab_chats_graficos" aria-controls="tab_chats_graficos" role="tab"
                            data-toggle="tab">Gráficos</a>
                    </li>
                </ul>
            </div>

            <div class="tab-content chats-tab-content">
                <div role="tabpanel" class="tab-pane active" id="tab_chats_listado">
                    <?php relavera_chats_lista_html_formulario_filtros('lista', $lista_plantas, 'btn_buscar_mensajes_lista', 'Filtros del envio de mensajes por WhatsApp'); ?>
                    <p class="chats-meta-line" id="bd_mensajes_meta">—</p>
                    <table id="gridMensajesManifiesto"></table>
                    <table id="gridMensajesManifiestoPager"></table>

                    <div id="imprimirMensajesManifiesto" style="display:none;">
                        <div style="width:1030px;">
                            <?php echo $obBD_rep->getReportHeader($Ses_Suc_Cod, 'MENSAJES MANIFIESTO (WHATSAPP)', '<span class="subtitle">Listado manifiesto mensajes</span>', $obBD_conexion_rep); ?>
                            <table id="tablaReporteMensajesManifiesto" cellspacing="0" cellpadding="0"
                                style="width:100%;border-collapse:collapse;table-layout:auto;font-size:11px;"></table>
                            <?php echo $obBD_rep->getReportFooter($Ses_Suc_Cod, (isset($Ses_Usu_Cod) ? $Ses_Usu_Cod : 0), $obBD_conexion_rep); ?>
                        </div>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane chats-tabs-graficos" id="tab_chats_graficos">
                    <?php relavera_chats_lista_html_formulario_filtros('graficos', $lista_plantas, 'btn_buscar_mensajes_graficos', 'Filtros de los gráficos'); ?>
                    <div style="margin: 0 0 12px;">
                        <button type="button" id="btn_imprimir_pdf_graficos_chats" class="btn btn-default btn-sm"
                            title="Abre el cuadro de impresión del navegador; elija «Guardar como PDF» si aplica">
                            <i class="glyphicon glyphicon-print"></i> Imprimir PDF (gráfico y tablas)
                        </button>
                    </div>
                    <div class="row chats-graficos-charts-row" style="margin-top: 15px; margin-bottom: 10px;">
                        <div class="col-md-12 col-xs-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <strong>Mensajes por planta</strong>
                                    <span class="text-muted small">(según filtros de esta pestaña)</span>
                                </div>
                                <div class="panel-body">
                                    <div class="chats-chart-wrap">
                                        <canvas id="chartMsjPorPlanta"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row chats-graficos-charts-row" style="margin-bottom: 15px;">
                        <div class="col-md-6 col-xs-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <strong>Distribución por tipo</strong>
                                    <span class="text-muted small">(gráfico circular: % y cantidad en leyenda y al pasar
                                        el ratón)</span>
                                </div>
                                <div class="panel-body">
                                    <div class="chats-chart-wrap chats-chart-wrap--tipo">
                                        <canvas id="chartMsjPorTipo"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xs-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <strong>Cantidad de mensajes por tipo</strong>
                                    <span class="text-muted small">(KPIs resumen rápidos)</span>
                                </div>
                                <div class="panel-body" style="padding: 20px;">
                                    <div class="row chats-kpi-grid">
                                        <!-- SCH Card -->
                                        <div class="col-xs-6 col-sm-4 chats-kpi-col">
                                            <div class="chats-kpi-card chats-kpi-card--sch">
                                                <div class="chats-kpi-title">SCH</div>
                                                <div class="chats-kpi-value" id="kpi_cnt_sch">0</div>
                                                <div class="chats-kpi-desc">Sanción Chofer</div>
                                            </div>
                                        </div>
                                        <!-- SVH Card -->
                                        <div class="col-xs-6 col-sm-4 chats-kpi-col">
                                            <div class="chats-kpi-card chats-kpi-card--svh">
                                                <div class="chats-kpi-title">SVH</div>
                                                <div class="chats-kpi-value" id="kpi_cnt_svh">0</div>
                                                <div class="chats-kpi-desc">Sanción Vehículo</div>
                                            </div>
                                        </div>
                                        <!-- SPL Card -->
                                        <div class="col-xs-6 col-sm-4 chats-kpi-col">
                                            <div class="chats-kpi-card chats-kpi-card--spl">
                                                <div class="chats-kpi-title">SPL</div>
                                                <div class="chats-kpi-value" id="kpi_cnt_spl">0</div>
                                                <div class="chats-kpi-desc">Sanción Planta</div>
                                            </div>
                                        </div>
                                        <!-- DAN Card -->
                                        <div class="col-xs-6 col-sm-4 chats-kpi-col">
                                            <div class="chats-kpi-card chats-kpi-card--dan">
                                                <div class="chats-kpi-title">DAN</div>
                                                <div class="chats-kpi-value" id="kpi_cnt_dan">0</div>
                                                <div class="chats-kpi-desc">Depósito de anticipo</div>
                                            </div>
                                        </div>
                                        <!-- CAN Card -->
                                        <div class="col-xs-6 col-sm-4 chats-kpi-col">
                                            <div class="chats-kpi-card chats-kpi-card--can">
                                                <div class="chats-kpi-title">CAN</div>
                                                <div class="chats-kpi-value" id="kpi_cnt_can">0</div>
                                                <div class="chats-kpi-desc">Confirmación de anticipo</div>
                                            </div>
                                        </div>
                                        <!-- TRE Card -->
                                        <div class="col-xs-6 col-sm-4 chats-kpi-col">
                                            <div class="chats-kpi-card chats-kpi-card--tre">
                                                <div class="chats-kpi-title">TRE</div>
                                                <div class="chats-kpi-value" id="kpi_cnt_tre">0</div>
                                                <div class="chats-kpi-desc">Tiempo Relavera</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tarjeta de Total General -->
                                    <div class="chats-kpi-total-card">
                                        <div class="chats-kpi-total-inner">
                                            <div class="chats-kpi-total-label">
                                                <i class="glyphicon glyphicon-stats"></i> TOTAL MENSAJES ENVIADOS
                                            </div>
                                            <div class="chats-kpi-total-value" id="kpi_cnt_total">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row chats-resumen-tablas-wrap" style="margin-top: 15px;">
                        <div class="col-md-6 col-xs-12">
                            <div class="panel panel-default">
                                <div class="panel-heading"><strong>Resumen por planta</strong></div>
                                <div class="panel-body">
                                    <div class="table-responsive chats-table-scroll">
                                        <table class="chats-resumen-tabla" id="tablaMsjResumenPlanta">
                                            <thead>
                                                <tr>
                                                    <th>Planta</th>
                                                    <th class="text-right">Mensajes enviados</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                            <tfoot>
                                                <tr class="chats-resumen-tfoot-total">
                                                    <td><strong>Total (suma por planta)</strong></td>
                                                    <td class="text-right"><strong
                                                            id="tablaMsjResumenPlantaTotal">0</strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xs-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <strong>Mensajes por tipo</strong>
                                    <span class="text-muted small">(sanciones SCH/SVH/SPL, anticipos DAN/CAN, tiempo
                                        TRE, etc.)</span>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="chats-resumen-tabla" id="tablaMsjResumenTipo">
                                            <thead>
                                                <tr>
                                                    <th style="width:88px;">Código</th>
                                                    <th>Descripción</th>
                                                    <th class="text-right" style="min-width:120px;">Mensajes enviados
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                            <tfoot>
                                                <tr class="chats-resumen-tfoot-total">
                                                    <td colspan="2"><strong>Total (suma por tipo)</strong></td>
                                                    <td class="text-right"><strong
                                                            id="tablaMsjResumenTipoTotal">0</strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="chats-chart-meta-row text-muted" id="bd_mensajes_chart_meta">—</p>
                </div>

                <div id="imprimirMensajesGraficos" style="display:none;">
                    <style type="text/css">
                        #imprimirMensajesGraficos .chats-print-resumen-tabla {
                            width: 100%;
                            border-collapse: collapse;
                            font-size: 10px;
                            margin: 6px 0 14px;
                        }

                        #imprimirMensajesGraficos .chats-print-resumen-tabla th,
                        #imprimirMensajesGraficos .chats-print-resumen-tabla td {
                            border: 1px solid #333;
                            padding: 5px 7px;
                            vertical-align: top;
                        }

                        #imprimirMensajesGraficos .chats-print-resumen-tabla th {
                            background: #e8e8e8;
                            font-weight: bold;
                            text-align: left;
                        }

                        #imprimirMensajesGraficos .chats-print-resumen-tabla td.text-right,
                        #imprimirMensajesGraficos .chats-print-resumen-tabla th.text-right {
                            text-align: right;
                        }

                        #imprimirMensajesGraficos .chats-print-resumen-tabla tfoot td {
                            background: #f0f0f0;
                            font-weight: bold;
                        }

                        #imprimirMensajesGraficos .chats-print-subtit {
                            font-family: Arial, Helvetica, sans-serif;
                            font-size: 11px;
                            margin: 8px 0 12px;
                            color: #000;
                        }

                        #imprimirMensajesGraficos .chats-print-seccion {
                            font-family: Arial, Helvetica, sans-serif;
                            font-size: 12px;
                            font-weight: bold;
                            margin: 14px 0 6px;
                            text-transform: uppercase;
                        }

                        #imprimirMensajesGraficos .chats-print-meta {
                            font-family: Arial, Helvetica, sans-serif;
                            font-size: 10px;
                            margin-top: 10px;
                            color: #333;
                        }
                    </style>
                    <div style="width:1030px;">
                        <?php echo $obBD_rep->getReportHeader($Ses_Suc_Cod, 'MENSAJES MANIFIESTO (WHATSAPP)', '<span class="subtitle">Gráficos: mensajes por planta y resúmenes</span>', $obBD_conexion_rep); ?>
                        <p id="imprimirGraficos_filtros" class="chats-print-subtit Texto_Listados"></p>
                        <div id="imprimirGraficos_chart_wrap" style="display:none;">
                            <p class="chats-print-seccion">Gráfico — mensajes por planta</p>
                            <p style="margin:0 0 10px;"><img id="imprimirGraficos_chart_img" src=""
                                    alt="Gráfico mensajes por planta" style="max-width:100%;height:auto;border:0;" />
                            </p>
                        </div>
                        <div id="imprimirGraficos_chart_tipo_wrap" style="display:none;">
                            <p class="chats-print-seccion">Gráfico circular — mensajes por tipo</p>
                            <p style="margin:0 0 10px;"><img id="imprimirGraficos_chart_tipo_img" src=""
                                    alt="Gráfico circular por tipo"
                                    style="max-width:100%;max-height:420px;height:auto;border:0;" /></p>
                        </div>
                        <p class="chats-print-seccion">Tabla — resumen por planta</p>
                        <div id="imprimirGraficos_tabla_planta"></div>
                        <p class="chats-print-seccion">Tabla — mensajes por tipo</p>
                        <div id="imprimirGraficos_tabla_tipo"></div>
                        <p id="imprimirGraficos_meta" class="chats-print-meta Texto_Listados"></p>
                        <?php echo $obBD_rep->getReportFooter($Ses_Suc_Cod, (isset($Ses_Usu_Cod) ? $Ses_Usu_Cod : 0), $obBD_conexion_rep); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js"></script>
    <script type="text/ecmascript" src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/man_val_chats.js?x=37"></script>
</body>

</html>