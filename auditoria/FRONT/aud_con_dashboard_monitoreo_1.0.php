<?php
/**
 * Dashboard Estadistico Interactivo de Monitoreo de Actividades.
 *
 * Dos pestañas:
 *  - Inicio: estadisticas interactivas (graficos + top) del periodo seleccionado
 *            con el filtro estandarizado (Hoy / Ayer / 1 Semana / 1 Mes / 3 Meses
 *            + calendario Desde-Hasta). Sin exportaciones estaticas.
 *  - Comparativo: tablero comparativo A vs B (con su propio PDF/Correo/WhatsApp)
 *            embebido mediante iframe.
 *
 * @package auditoria.FRONT
 */

if (session_id() === '' && !headers_sent()) {
	@session_start();
}

require_once dirname(__FILE__) . '/../../administrador/LOGICA/seguridad.php';

$audEmpCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
$audEmpNom = isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : 'Empresa Principal';
$audUsuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;

require_once dirname(__FILE__) . '/../LOGICA/aud_log_acceso_directorio.php';
aud_acceso_directorio_gate($audEmpCod);

// Aviso "datos desde": se consulta el MIN(Log_Fec) una sola vez por empresa (cache en sesion)
require_once dirname(__FILE__) . '/../LOGICA/aud_log_interpretar.php';
if (!class_exists('Class_Log_Datos_CfgMon')) {
	$audCfgFile = dirname(__FILE__) . '/../LOGICA/aud_log_config_monitoreo.php';
	if (file_exists($audCfgFile)) {
		require_once $audCfgFile;
	}
}
$audSesDatDis = isset($_SESSION['Ses_Dat_Dis']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']) : '';
$audDesdeFechaHtml = '';
if (class_exists('Class_Log_Datos_CfgMon')) {
	$obBD_desde1 = new Class_Log_Datos_CfgMon();
	$obBD_desdeCon = new Class_Log_Conexion_CfgMon($audSesDatDis !== '' ? $audSesDatDis : null);
	$audDesdeFechaHtml = aud_html_banner_desde(aud_fecha_registro_inicio($audEmpCod, $obBD_desde1, $obBD_desdeCon));
	$obBD_desde1->liberar();
	$obBD_desdeCon->cerrar();
} else {
	$audDesdeFechaHtml = aud_html_banner_desde(aud_fecha_registro_inicio($audEmpCod));
}

// Rango por defecto: ultimos 30 dias
$defaultIni = date('Y-m-d 00:00:00', strtotime('-30 days'));
$defaultFin = date('Y-m-d 23:59:59');

// Combos del filtro estandarizado (mismos catalogos y case numbers que Monitoreo)
// para que el Panel Estadistico soporte el mismo filtro de evento/modulo/directorio/
// proceso/usuario/sucursal/planta.
require_once dirname(__FILE__) . '/../LOGICA/aud_log_monitoreo.php';
$audObBD_conexionMon = new Class_Log_Conexion($audSesDatDis !== '' ? $audSesDatDis : null);
$audObBD_con1Mon = new Class_Log_Datos();
$Arr_Modulos = $audObBD_con1Mon->getArrayConsulta(25, array($audEmpCod), $audObBD_conexionMon);
$Arr_Directorios = $audObBD_con1Mon->getArrayConsulta(30, array($audEmpCod, 0), $audObBD_conexionMon);
$Arr_Procesos = $audObBD_con1Mon->getArrayConsulta(26, array($audEmpCod, 0, 0), $audObBD_conexionMon);
$Arr_Usuarios = $audObBD_con1Mon->getArrayConsulta(27, array($audEmpCod, 0), $audObBD_conexionMon);
$Arr_Sucursales = $audObBD_con1Mon->getArrayConsulta(28, array($audEmpCod), $audObBD_conexionMon);
$Arr_Eventos = $audObBD_con1Mon->getArrayConsulta(17, '', $audObBD_conexionMon);
$Arr_PlantasFiltro = $audObBD_con1Mon->getArrayConsulta(33, array(), $audObBD_conexionMon);
$rowSucCountMon = $audObBD_con1Mon->getRowConsulta(29, array($audEmpCod), $audObBD_conexionMon);
$hasSucursalesMon = (!empty($rowSucCountMon['count']) && (int)$rowSucCountMon['count'] > 1);
if (!is_array($Arr_Modulos)) $Arr_Modulos = array();
if (!is_array($Arr_Directorios)) $Arr_Directorios = array();
if (!is_array($Arr_Procesos)) $Arr_Procesos = array();
if (!is_array($Arr_Usuarios)) $Arr_Usuarios = array();
if (!is_array($Arr_Sucursales)) $Arr_Sucursales = array();
if (!is_array($Arr_Eventos)) $Arr_Eventos = array();
if (!is_array($Arr_PlantasFiltro)) $Arr_PlantasFiltro = array();
$audObBD_con1Mon->liberar();
$audObBD_conexionMon->cerrar();
if (!function_exists('aud_h')) {
	function aud_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<title>Panel Estadistico de Auditoria - ExaContable</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
	<?php require_once("../../mascaras/model3/estilos/estilos.php"); ?>
	<script type="text/javascript" src="../../framework/jquery/apexcharts/apexcharts.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../RECURSOS/aud_monitoreo_ui_1.0.css?v=20260923_v15" />

	<style>
		.panel-heading.exa-header, .exa-header {
			background-color: #254463 !important;
			color: #ffffff !important;
		}
		.panel-heading.exa-header .panel-title, .exa-header .panel-title,
		.panel-heading.exa-header h3, .exa-header h3 {
			color: #ffffff !important;
			font-weight: 700 !important;
		}
		.panel-heading.exa-header .panel-title i,
		.exa-header .panel-title i {
			color: #ffffff !important;
		}

		.aud-dash-tabs .nav-tabs {
			border-bottom: 2px solid #dbe3ec;
			margin-bottom: 14px;
		}
		.aud-dash-tabs .nav-tabs > li > a {
			border-radius: 4px 4px 0 0;
			font-weight: 700;
			font-size: 12px;
		}
		.aud-dash-tabs .nav-tabs > li.active > a {
			border-color: #254463 #dbe3ec #fff;
			color: #254463;
		}
		@media (max-width: 480px) {
			.aud-dash-tabs .nav-tabs { display: flex; }
			.aud-dash-tabs .nav-tabs > li { float: none; flex: 1 1 0%; }
			.aud-dash-tabs .nav-tabs > li > a { padding: 8px 4px; text-align: center; font-size: 11px; }
		}

		.period-control-card {
			background: #ffffff;
			border-radius: 4px;
			border: 1px solid #e2e8f0;
			padding: 10px 14px;
			margin-bottom: 12px;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
		}
		.preset-btn { font-size: 11px; }

		/* Barra de herramientas: rango en una sola linea */
		.aud-toolbar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: nowrap;
			gap: 10px;
			padding: 10px 14px;
		}
		.aud-toolbar-left {
			flex: 1 1 auto;
			min-width: 0;
			display: flex;
			flex-direction: row;
			align-items: center;
			flex-wrap: nowrap;
			gap: 8px;
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
			scrollbar-width: thin;
		}
		.aud-toolbar-presets {
			display: inline-flex;
			align-items: center;
			flex-wrap: nowrap;
			gap: 6px;
			flex-shrink: 0;
		}
		.aud-toolbar-label {
			font-weight: 700;
			font-size: 11px;
			letter-spacing: 0.03em;
			text-transform: uppercase;
			color: #5b6f88;
			white-space: nowrap;
			flex-shrink: 0;
		}
		.aud-toolbar-dates {
			display: inline-flex;
			align-items: center;
			flex-wrap: nowrap;
			gap: 6px;
			flex-shrink: 0;
		}
		.aud-toolbar-dates .input-group { width: auto; }
		.aud-toolbar-dates .input-group .form-control { width: 100px; }
		.aud-toolbar-right {
			flex: 0 0 auto;
			display: flex;
			align-items: center;
			gap: 6px;
			flex-shrink: 0;
		}
		.aud-acciones-menu {
			min-width: 210px;
			font-size: 12px;
		}
		.aud-acciones-menu li a {
			padding: 7px 14px;
		}
		.aud-acciones-menu li a i {
			width: 20px;
			text-align: center;
			margin-right: 4px;
		}
		@media (max-width: 767px) {
			.aud-toolbar {
				flex-wrap: wrap;
			}
			.aud-toolbar-right {
				width: 100%;
				justify-content: flex-end;
			}
			.aud-toolbar-right .dropdown { float: none; }
		}
		@media (max-width: 480px) {
			.aud-toolbar-dates .input-group .form-control { width: 86px; }
			.aud-toolbar-presets #monPeriodoPresets .btn { padding: 3px 6px; }
		}

		/* Fila de filtros avanzados usa .aud-filtros-grid del CSS compartido */
		.aud-toolbar-filtros {
			border-top: 1px solid #e2e8f0;
			padding-top: 10px;
			margin-top: 8px;
		}

		.kpi-mon-card {
			background: #ffffff;
			border-radius: 4px;
			border: 1px dashed #e2e8f0;
			border-top: 3px solid #2563eb;
			padding: 8px 12px;
			margin-bottom: 10px;
			min-height: 74px;
			display: flex;
			flex-direction: column;
			justify-content: center;
			transition: box-shadow 0.15s ease, transform 0.15s ease;
		}
		.kpi-mon-card:hover {
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
			transform: translateY(-1px);
		}
		.btn, .preset-btn, .dropdown-toggle {
			transition: background-color 0.12s ease, border-color 0.12s ease, box-shadow 0.12s ease, transform 0.05s ease;
		}
		.btn:active, .preset-btn:active {
			transform: translateY(1px);
		}
		.kpi-mon-card.green { border-top-color: #10b981; }
		.kpi-mon-card.amber { border-top-color: #f59e0b; }
		.kpi-mon-card.red { border-top-color: #ef4444; }
		.kpi-mon-card.purple { border-top-color: #8b5cf6; }
		.kpi-mon-card .kpi-label {
			font-size: 10px; font-weight: 700; text-transform: uppercase;
			letter-spacing: 0.04em; color: #64748b;
		}
		.kpi-mon-card .kpi-val { font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.1; }
		.kpi-mon-card .kpi-sub { font-size: 11px; color: #94a3b8; }

		/* KPIs configurables (mover / ocultar / anadir) */
		#dashKpiGrid {
			display: flex;
			flex-wrap: wrap;
			margin-left: -6px;
			margin-right: -6px;
		}
		.dash-kpi-card {
			flex: 1 1 0%;
			box-sizing: border-box;
			padding: 0 6px;
			min-width: 150px;
		}
		@media (max-width: 480px) {
			.dash-kpi-card, .dash-kpi-placeholder { flex: 1 1 100%; min-width: 100%; }
		}
		.dash-kpi-card .kpi-mon-card {
			cursor: move;
			margin-bottom: 10px;
		}
		.kpi-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 2px;
			user-select: none;
		}
		.kpi-head .kpi-hide {
			cursor: pointer; color: #cbd5e1; font-size: 11px;
		}
		.kpi-head .kpi-hide:hover { color: #ef4444; }
		.kpi-label { display: inline-block; }
		.dash-kpi-placeholder {
			box-sizing: border-box;
			flex: 1 1 0%;
			min-width: 150px;
			background: #eef2f7;
			border: 1px dashed #94a3b8;
			border-radius: 6px;
			min-height: 74px;
			margin-bottom: 10px;
		}
		.dash-kpis-foot {
			font-size: 11px; color: #64748b;
			margin: 0 0 14px; padding: 0 2px;
		}
		.aud-modal-sec {
			font-size: 12px; font-weight: 700; color: #334155;
			margin: 4px 0 6px; text-transform: uppercase; letter-spacing: 0.03em;
		}

		/* Widgets del dashboard de inicio (configurables de lugar) */
		#dashWidgetGrid {
			display: flex;
			flex-wrap: wrap;
			margin-left: -6px;
			margin-right: -6px;
		}
		.dash-widget { flex: 0 0 auto; box-sizing: border-box; padding: 0 6px 12px; }
		.dash-widget.w-full { width: 100%; }
		.dash-widget.w-12 { width: 50%; }
		@media (max-width: 767px) {
			.dash-widget.w-12 { width: 100%; }
		}

		.dash-widget-inner {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 6px;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
			overflow: hidden;
			height: 100%;
			transition: box-shadow 0.15s ease;
		}
		.dash-widget-inner:hover {
			box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
		}
		.dash-widget-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 8px 12px;
			background: #f8fafc;
			border-bottom: 1px solid #eef2f7;
			border-top: 3px solid #2563eb;
			cursor: move;
			user-select: none;
		}
		.dash-widget-head.ac-green { border-top-color: #10b981; }
		.dash-widget-head.ac-amber { border-top-color: #f59e0b; }
		.dash-widget-head.ac-red { border-top-color: #ef4444; }
		.dash-widget-head.ac-purple { border-top-color: #8b5cf6; }
		.dash-widget-head.ac-teal { border-top-color: #06b6d4; }
		.dash-widget-head.ac-pink { border-top-color: #f43f5e; }
		.dash-widget-head .dash-title {
			font-size: 12px; font-weight: 700; color: #334155; margin: 0;
		}
		.dash-widget-head .dash-hint { font-size: 10px; color: #94a3b8; white-space: nowrap; }
		.dash-widget-head .dash-actions {
			display: flex; align-items: center; gap: 10px;
		}
		.dash-widget-head .dash-actions .dash-hint { cursor: grab; color: #cbd5e1; }
		.dash-widget-head .dash-actions .dash-hide {
			cursor: pointer; color: #94a3b8; font-size: 12px;
		}
		.dash-widget-head .dash-actions .dash-hide:hover { color: #ef4444; }
		.dash-widget-head .dash-actions .dash-size {
			cursor: pointer; color: #94a3b8; font-size: 12px;
		}
		.dash-widget-head .dash-actions .dash-size:hover { color: #2563eb; }
		.dash-widget-head .dash-actions .dash-show {
			cursor: pointer; color: #10b981; font-size: 12px;
		}
		.dash-widget-head .dash-actions .dash-show:hover { color: #065f46; }
		.dash-widget-body { padding: 10px 12px; overflow-x: auto; }
		.dash-widget-body .chart-empty { color: #94a3b8; font-size: 12px; text-align: center; padding: 28px 10px; }
		.dash-widget-body table { min-width: 100%; }
		@media (max-width: 480px) {
			.dash-widget-head { flex-wrap: wrap; row-gap: 6px; }
			.dash-widget-head .dash-title { flex: 1 1 100%; }
			.dash-widget-body table { min-width: 420px; }
		}

		.dash-widget-placeholder {
			box-sizing: border-box;
			background: #eef2f7;
			border: 1px dashed #94a3b8;
			border-radius: 6px;
			min-height: 90px;
			margin-bottom: 12px;
		}
		.dash-widget.ui-sortable-helper .dash-widget-inner {
			box-shadow: 0 10px 28px rgba(15, 23, 42, 0.2);
			transform: rotate(1deg);
		}

		.aud-mini-list { margin-top: 8px; border-top: 1px solid #f1f5f9; padding-top: 6px; }
		.aud-mini-item {
			display: flex; align-items: center; justify-content: space-between;
			padding: 3px 2px; border-bottom: 1px dashed #f1f5f9;
			font-size: 11px; color: #475569;
		}
		.aud-mini-item b { color: #2563eb; font-size: 12px; }

		.aud-modal-ayuda { font-size: 11px; color: #64748b; margin-bottom: 8px; }
		.modal-body { max-height: 62vh; overflow-y: auto; }

		/* Cuadros configurables del modal de personalizacion */
		#kpisSorter, #widgetsSorter {
			display: flex; flex-wrap: wrap; gap: 6px;
			padding: 2px; margin-bottom: 4px;
		}
		.aud-sbox {
			display: inline-flex; align-items: center; gap: 7px;
			box-sizing: border-box; flex: 1 1 auto;
			min-width: 175px; max-width: 100%;
			padding: 8px 10px;
			background: #ffffff; border: 1px solid #dbe3ee; border-radius: 5px;
			cursor: move; font-size: 12px; color: #334155;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
		}
		.aud-sbox:hover { border-color: #2563eb; }
		.aud-sbox-off { opacity: 0.5; background: #f8fafc; border-style: dashed; }
		.aud-sbox-off .kbox-txt { text-decoration: line-through; }
		.kbox-eye { color: #94a3b8; cursor: pointer; font-size: 12px; }
		.aud-sbox-on .kbox-eye { color: #10b981; }
		.aud-sbox-off .kbox-eye { color: #ef4444; }
		.kbox-ico { color: #64748b; font-size: 12px; }
		.kbox-txt { flex: 1 1 auto; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.aud-sbox-placeholder {
			box-sizing: border-box; flex: 1 1 auto; min-width: 175px;
			border: 1px dashed #94a3b8; border-radius: 5px; background: #eef2f7;
		}
		.aud-sbox.ui-sortable-helper { box-shadow: 0 8px 20px rgba(15, 23, 42, 0.18); }

		.table-mon-top th {
			background: #f8fafc; color: #475569; font-weight: 700;
			font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;
			border-bottom: 2px solid #e2e8f0 !important; padding: 6px 10px;
		}
		.table-mon-top td {
			vertical-align: middle !important; border-top: 1px solid #f1f5f9 !important; padding: 5px 10px;
		}

		.aud-th-iframe-wrap {
			width: 100%;
			border: 1px solid #e2e8f0;
			border-radius: 6px;
			overflow: hidden;
			background: #ffffff;
		}
		.aud-th-iframe-wrap iframe {
			width: 100%;
			border: 0;
			display: block;
		}
	</style>
</head>
<body>

<div class="panel panel-default panel-main exa-ui-panel exa-ui-fill-page" style="margin-top: 0;">
	<div class="panel-heading exa-header">
		<div class="row" style="display: flex; align-items: center; justify-content: space-between;">
			<div class="col-xs-12 col-sm-12">
				<h3 class="panel-title" style="font-size: 14px; font-weight: 700; color: #ffffff !important;">
					<i class="fa fa-bar-chart" style="color: #ffffff; margin-right: 4px;"></i> Panel Estad&iacute;stico de Auditor&iacute;a
				</h3>
			</div>
		</div>
	</div>

	<div class="panel-body exa-body" style="padding: 10px 14px;">

		<div class="aud-page-hero">
			<div class="aud-page-hero-icon"><i class="fa fa-bar-chart"></i></div>
			<div class="aud-page-hero-text">
				<h4>Panel estad&iacute;stico</h4>
				<p class="aud-page-hero-sub">
					Resumen ejecutivo del periodo con gr&aacute;ficos, tops y el mismo filtro que Monitoreo.
					Exporte PDF o env&iacute;e por correo y WhatsApp.
				</p>
			</div>
			<div class="aud-page-hero-tags">
				<span class="aud-page-hero-tag"><i class="fa fa-line-chart"></i> Inicio</span>
				<span class="aud-page-hero-tag"><i class="fa fa-exchange"></i> Comparativo</span>
			</div>
		</div>

		<?php echo $audDesdeFechaHtml; ?>

		<!-- Pestañas -->
		<div class="aud-dash-tabs aud-ui-tabs">
			<ul class="nav nav-tabs" id="audDashTabs">
				<li class="active">
					<a href="#tabInicio" data-toggle="tab"><i class="fa fa-line-chart"></i> Inicio</a>
				</li>
				<li>
					<a href="#tabComparativo" data-toggle="tab"><i class="fa fa-exchange"></i> Comparativo (A vs B)</a>
				</li>
			</ul>

			<div class="tab-content">
				<!-- ============ TAB INICIO ============ -->
				<div class="tab-pane active" id="tabInicio">

					<!-- Barra de herramientas: filtros juntos + Acciones a la derecha -->
					<div class="period-control-card" style="padding: 0;">
						<div class="aud-toolbar">

							<div class="aud-toolbar-left">
								<span class="aud-toolbar-label">
									<i class="fa fa-calendar-check-o text-primary"></i> Rango de fechas
								</span>
								<div class="btn-group btn-group-xs aud-period-presets" id="monPeriodoPresets">
									<button type="button" class="btn aud-btn-preset" data-preset="ayer">Ayer</button>
									<button type="button" class="btn aud-btn-preset" data-preset="hoy">Hoy</button>
									<button type="button" class="btn aud-btn-preset" data-preset="1semana">1 Semana</button>
									<button type="button" class="btn aud-btn-preset active" data-preset="1mes">1 Mes</button>
									<button type="button" class="btn aud-btn-preset" data-preset="3meses">3 Meses</button>
								</div>
								<span id="monPresetCustomBadge" class="label label-info" style="display:none; font-size:10px; padding: 2px 6px;">Personalizado</span>
								<div class="aud-toolbar-dates">
									<div class="aud-toolbar-range">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">Desde</span>
											<input type="text" id="mon_ini" class="form-control text-center" value="<?php echo substr($defaultIni, 0, 10); ?>" readonly style="background:#fff; cursor:pointer;" placeholder="AAAA-MM-DD" />
											<span class="input-group-addon" style="cursor:pointer;" onclick="$('#mon_ini').focus().datepicker('show');"><i class="fa fa-calendar text-muted"></i></span>
										</div>
										<div class="input-group input-group-sm">
											<span class="input-group-addon">Hasta</span>
											<input type="text" id="mon_fin" class="form-control text-center" value="<?php echo substr($defaultFin, 0, 10); ?>" readonly style="background:#fff; cursor:pointer;" placeholder="AAAA-MM-DD" />
											<span class="input-group-addon" style="cursor:pointer;" onclick="$('#mon_fin').focus().datepicker('show');"><i class="fa fa-calendar text-muted"></i></span>
										</div>
									</div>
									<span id="monRangoLabel" class="label label-default" style="font-size:11px; background:#475569;"></span>
								</div>
							</div>

							<div class="aud-toolbar-right">
								<button type="button" class="btn btn-default btn-sm" id="btnMonFiltrosToggle" title="Mostrar/ocultar filtros avanzados (mismos filtros que Monitoreo)">
									<i class="fa fa-filter"></i> Filtros <span class="badge" id="monFiltrosBadge" style="display:none;">0</span>
								</button>
								<div class="dropdown">
									<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Acciones">
										<i class="fa fa-sliders"></i> Acciones <span class="caret"></span>
									</button>
									<ul class="dropdown-menu dropdown-menu-right aud-acciones-menu">
										<li><a href="javascript:void(0);" id="btnResetWidgetsMon" title="Restablecer el orden original de los widgets del tab Inicio"><i class="fa fa-undo"></i> Restablecer widgets</a></li>
										<li><a href="javascript:void(0);" id="btnCfgWidgetsMon" title="Mostrar u ocultar widgets y personalizar el tablero"><i class="fa fa-th-large"></i> Personalizar</a></li>
										<li><a href="javascript:void(0);" id="btnRecargarMon" title="Actualizar metricas del tab Inicio"><i class="fa fa-refresh"></i> Actualizar Datos</a></li>
										<li class="divider"></li>
										<li><a href="javascript:void(0);" id="btnDescargarPdfMon" title="Generar reporte formal PDF del periodo"><i class="fa fa-file-pdf-o text-danger"></i> Exportar PDF</a></li>
										<li><a href="javascript:void(0);" id="btnModalCorreoMon" title="Enviar reporte por correo"><i class="fa fa-envelope-o text-info"></i> Enviar Correo</a></li>
										<li><a href="javascript:void(0);" id="btnModalWhatsAppMon" title="Compartir informe por WhatsApp"><i class="fa fa-whatsapp text-success"></i> WhatsApp</a></li>
									</ul>
								</div>
							</div>
						</div>

						<div class="aud-toolbar aud-toolbar-filtros" id="monFiltrosRow" style="display:none;">
							<div class="aud-toolbar-left aud-filtros-grid">
								<?php if ($hasSucursalesMon) { ?>
								<div class="aud-search-cell aud-search-cell-suc">
									<label for="mon_suc">Sucursal</label>
									<select id="mon_suc" class="form-control input-sm">
										<option value="0">Todas</option>
										<?php foreach ($Arr_Sucursales as $s) { ?>
										<option value="<?php echo (int)$s['Suc_Cod']; ?>"><?php echo aud_h($s['Suc_Des']); ?></option>
										<?php } ?>
									</select>
								</div>
								<?php } ?>
								<div class="aud-search-cell aud-search-cell-mod">
									<label for="mon_org">Modulo</label>
									<select id="mon_org" class="form-control input-sm">
										<option value="0">Todos</option>
										<?php foreach ($Arr_Modulos as $m) { ?>
										<option value="<?php echo (int)$m['Org_Cod']; ?>"><?php echo aud_h($m['Org_Des']); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="aud-search-cell aud-search-cell-dir">
									<label for="mon_dir">Directorio</label>
									<select id="mon_dir" class="form-control input-sm">
										<option value="0">Todos</option>
										<?php foreach ($Arr_Directorios as $d) { ?>
										<option value="<?php echo (int)$d['Org_Cod']; ?>"><?php echo aud_h($d['Org_Des']); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="aud-search-cell aud-search-cell-pcs">
									<label for="mon_pcs">Proceso</label>
									<select id="mon_pcs" class="form-control input-sm">
										<option value="0">Todos</option>
										<?php foreach ($Arr_Procesos as $p) {
											$pl = !empty($p['Pcs_Lin']) ? $p['Pcs_Lin'] : (isset($p['Pcs_Nom']) ? $p['Pcs_Nom'] : ('Proceso '.$p['Pcs_Cod']));
										?>
										<option value="<?php echo (int)$p['Pcs_Cod']; ?>"><?php echo aud_h($pl); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="aud-search-cell aud-search-cell-pla" id="monFilPlantaWrap" style="display:none;">
									<label for="mon_pla">Planta</label>
									<select id="mon_pla" class="form-control input-sm">
										<option value="0">Todas</option>
										<?php foreach ($Arr_PlantasFiltro as $pl2) { ?>
										<option value="<?php echo (int)$pl2['Pla_Cod']; ?>"><?php echo aud_h($pl2['Pla_Nom']); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="aud-search-cell aud-search-cell-usu">
									<label for="mon_usu">Usuario</label>
									<select id="mon_usu" class="form-control input-sm">
										<option value="0">Todos</option>
										<?php foreach ($Arr_Usuarios as $u) {
											$un = trim(isset($u['Usu_Nom']) ? $u['Usu_Nom'] : '');
											if ($un === '') { $un = 'Usuario '.(int)$u['Usu_Cod']; }
											$usus = (isset($u['Usu_Cods']) && $u['Usu_Cods'] !== '') ? $u['Usu_Cods'] : (string)(int)$u['Usu_Cod'];
										?>
										<option value="<?php echo aud_h($usus); ?>"><?php echo aud_h($un); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="aud-search-cell aud-search-cell-eve">
									<label for="mon_eve">Evento</label>
									<select id="mon_eve" class="form-control input-sm">
										<option value="0">Todos</option>
										<?php foreach ($Arr_Eventos as $ev) { ?>
										<option value="<?php echo (int)$ev['Eve_Cod']; ?>"><?php echo aud_h($ev['Eve_Des']); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="aud-search-cell aud-search-cell-limpiar">
									<label>&nbsp;</label>
									<button type="button" id="btnMonFiltrosLimpiar" class="btn btn-default btn-sm btn-block" title="Quitar todos los filtros">
										<i class="fa fa-eraser"></i> Limpiar
									</button>
								</div>
							</div>
						</div>
					</div>

					<!-- KPIs configurables (mover / ocultar / anadir) -->
					<div id="dashKpiGrid"></div>
					<div class="dash-kpis-foot" id="kpiFootMon"><i class="fa fa-spinner fa-spin" style="font-size:10px;"></i> Cargando...</div>

					<!-- Widgets configurable de lugar -->
					<div id="dashWidgetGrid">

						<div class="dash-widget w-12" data-widget="tendencia">
							<div class="dash-widget-inner">
								<div class="dash-widget-head ac-blue">
									<h4 class="dash-title"><i class="fa fa-area-chart"></i> Tendencia y Evoluci&oacute;n Diaria de Actividad</h4>
									<span class="dash-actions">
										<i class="fa fa-arrows-v dash-hint" title="Arrastrar para mover"></i>
										<i class="fa fa-expand dash-size" title="Cambiar tamaño (mitad / ancho completo)"></i>
										<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
									</span>
								</div>
								<div class="dash-widget-body"><div id="chartTendenciaMon" style="min-height: 240px;"></div></div>
							</div>
						</div>

						<div class="dash-widget w-12" data-widget="horas">
							<div class="dash-widget-inner">
								<div class="dash-widget-head ac-teal">
									<h4 class="dash-title"><i class="fa fa-clock-o"></i> Distribuci&oacute;n por Franja Horaria</h4>
									<span class="dash-actions">
										<i class="fa fa-arrows-v dash-hint" title="Arrastrar para mover"></i>
										<i class="fa fa-expand dash-size" title="Cambiar tamaño (mitad / ancho completo)"></i>
										<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
									</span>
								</div>
								<div class="dash-widget-body"><div id="chartHorasMon" style="min-height: 240px;"></div></div>
							</div>
						</div>

						<div class="dash-widget w-full" data-widget="usuariosDia">
							<div class="dash-widget-inner">
								<div class="dash-widget-head ac-green">
									<h4 class="dash-title"><i class="fa fa-users"></i> Comportamiento Diario por Usuario</h4>
									<span class="dash-actions">
										<i class="fa fa-arrows-v dash-hint" title="Arrastrar para mover"></i>
										<i class="fa fa-expand dash-size" title="Cambiar tamaño (mitad / ancho completo)"></i>
										<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
									</span>
								</div>
								<div class="dash-widget-body"><div id="chartUsuariosDiaMon" style="min-height: 240px;"></div></div>
							</div>
						</div>

						<div class="dash-widget w-12" data-widget="topUsu">
							<div class="dash-widget-inner">
								<div class="dash-widget-head ac-green">
									<h4 class="dash-title"><i class="fa fa-bar-chart"></i> Top Usuarios M&aacute;s Activos</h4>
									<span class="dash-actions">
										<i class="fa fa-arrows-v dash-hint" title="Arrastrar para mover"></i>
										<i class="fa fa-expand dash-size" title="Cambiar tamaño (mitad / ancho completo)"></i>
										<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
									</span>
								</div>
								<div class="dash-widget-body"><div id="chartTopUsuMon" style="min-height: 240px;"></div></div>
							</div>
						</div>

						<div class="dash-widget w-12" data-widget="topPla">
							<div class="dash-widget-inner">
								<div class="dash-widget-head ac-amber">
									<h4 class="dash-title"><i class="fa fa-industry"></i> Top Plantas de Beneficio</h4>
									<span class="dash-actions">
										<i class="fa fa-arrows-v dash-hint" title="Arrastrar para mover"></i>
										<i class="fa fa-expand dash-size" title="Cambiar tamaño (mitad / ancho completo)"></i>
										<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
									</span>
								</div>
								<div class="dash-widget-body"><div id="chartTopPlaMon" style="min-height: 240px;"></div></div>
							</div>
						</div>

						<div class="dash-widget w-12" data-widget="modulos">
							<div class="dash-widget-inner">
								<div class="dash-widget-head ac-purple">
									<h4 class="dash-title"><i class="fa fa-database"></i> Actividad por M&oacute;dulo / Proceso</h4>
									<span class="dash-actions">
										<i class="fa fa-arrows-v dash-hint" title="Arrastrar para mover"></i>
										<i class="fa fa-expand dash-size" title="Cambiar tamaño (mitad / ancho completo)"></i>
										<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
									</span>
								</div>
								<div class="dash-widget-body">
									<div id="chartModulosMon" style="min-height: 220px;"></div>
									<div id="minListaModulos" class="aud-mini-list"></div>
								</div>
							</div>
						</div>

						<div class="dash-widget w-12" data-widget="rankUsu">
							<div class="dash-widget-inner">
								<div class="dash-widget-head ac-red">
									<h4 class="dash-title"><i class="fa fa-trophy"></i> Ranking de Usuarios</h4>
									<span class="dash-actions">
										<i class="fa fa-arrows-v dash-hint" title="Arrastrar para mover"></i>
										<i class="fa fa-expand dash-size" title="Cambiar tamaño (mitad / ancho completo)"></i>
										<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
									</span>
								</div>
								<div class="dash-widget-body">
									<table class="table table-striped table-condensed table-mon-top" id="tablaTopUsuarios">
										<thead>
											<tr><th>#</th><th>Usuario</th><th class="text-right">Ingresar</th><th class="text-right">Actualizar</th><th class="text-right">Eliminar</th><th class="text-right">Total</th></tr>
										</thead>
										<tbody><tr><td colspan="6" class="text-center text-muted" style="font-size:11px;">Sin datos en el rango</td></tr></tbody>
									</table>
								</div>
							</div>
						</div>

						<div class="dash-widget w-12" data-widget="detPla">
							<div class="dash-widget-inner">
								<div class="dash-widget-head ac-amber">
									<h4 class="dash-title"><i class="fa fa-industry"></i> Detalle por Planta de Beneficio</h4>
									<span class="dash-actions">
										<i class="fa fa-arrows-v dash-hint" title="Arrastrar para mover"></i>
										<i class="fa fa-expand dash-size" title="Cambiar tamaño (mitad / ancho completo)"></i>
										<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
									</span>
								</div>
								<div class="dash-widget-body">
									<table class="table table-striped table-condensed table-mon-top" id="tablaTopPlantas">
										<thead>
											<tr><th>#</th><th>Planta</th><th class="text-right">Usuarios</th><th class="text-right">Ingresar</th><th class="text-right">Actualizar</th><th class="text-right">Eliminar</th><th class="text-right">Total</th></tr>
										</thead>
										<tbody><tr><td colspan="7" class="text-center text-muted" style="font-size:11px;">Sin datos de plantas en el rango</td></tr></tbody>
									</table>
								</div>
							</div>
						</div>

					</div>

					<!-- Widgets ocultos se aparcan aqui (display:none) -->
					<div id="dashWidgetOcultos" style="display:none;"></div>
				</div>

				<!-- ============ TAB COMPARATIVO ============ -->
				<div class="tab-pane" id="tabComparativo">
					<div class="aud-th-iframe-wrap">
						<iframe id="iframeComparativo" src="" data-src="aud_con_dashboard_comparativo_1.0.php" style="height: 900px;" scrolling="auto"></iframe>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Modal personalizacion de widgets -->
<div class="modal fade" id="modalWidgetsMon" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" id="btnCerrarWidgetsMon" aria-hidden="true">&times;</button>
				<h4 class="modal-title"><i class="fa fa-th-large"></i> Personalizar tablero</h4>
			</div>
			<div class="modal-body">
				<p class="aud-modal-ayuda">Arrastra los cuadros para definir la posici&oacute;n en pantalla. Clic en el ojo para mostrar u ocultar cada elemento.</p>
				<h4 class="aud-modal-sec"><i class="fa fa-tachometer"></i> M&eacute;tricas (KPIs)</h4>
				<div id="kpisSorter"></div>
				<h4 class="aud-modal-sec" style="margin-top:14px;"><i class="fa fa-th-large"></i> Widgets</h4>
				<div id="widgetsSorter"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default btn-sm" id="btnCerrarWidgetsMon2">Cerrar</button>
				<button type="button" class="btn btn-primary btn-sm" id="btnGuardarWidgetsMon"><i class="fa fa-check"></i> Guardar</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal Envio de Reporte por Correo -->
<div class="modal fade" id="modalEnvioCorreoMon" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" id="btnCerrarCorreoMon" aria-hidden="true">&times;</button>
				<h4 class="modal-title"><i class="fa fa-envelope-o"></i> Enviar Reporte por Correo Electr&oacute;nico</h4>
			</div>
			<div class="modal-body" style="padding: 16px;">
				<div class="form-group">
					<label style="font-weight: 600; font-size: 12px; color: #334155;">Correo Electr&oacute;nico Destinatario:</label>
					<input type="email" id="mailDestinatarioMon" class="form-control" placeholder="ejemplo@empresa.com" required />
				</div>
				<div class="form-group">
					<label style="font-weight: 600; font-size: 12px; color: #334155;">Nombre del Destinatario (Opcional):</label>
					<input type="text" id="mailNombreMon" class="form-control" placeholder="Ej: Gerencia General" />
				</div>
				<div class="form-group">
					<label style="font-weight: 600; font-size: 12px; color: #334155;">Asunto del Mensaje:</label>
					<input type="text" id="mailAsuntoMon" class="form-control" value="Informe Estadistico de Actividad de Auditoria" />
				</div>
				<div class="alert alert-info" style="margin-bottom: 0; font-size: 11px; padding: 8px 12px;">
					<i class="fa fa-info-circle"></i> El reporte del periodo seleccionado se adjuntar&aacute; autom&aacute;ticamente en formato <strong>PDF</strong> oficial.
				</div>
			</div>
			<div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 10px 18px;">
				<button type="button" class="btn btn-default btn-sm" id="btnCerrarCorreoMon2">Cancelar</button>
				<button type="button" id="btnEnviarCorreoConfirmMon" class="btn btn-primary btn-sm" style="font-weight: 600;">
					<i class="fa fa-paper-plane"></i> Enviar Documento
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal Envio WhatsApp -->
<div class="modal fade" id="modalEnvioWhatsAppMon" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
	<div class="modal-dialog" style="max-width: 480px;">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #15803d !important;">
				<button type="button" class="close" id="btnCerrarWaMon" aria-hidden="true">&times;</button>
				<h4 class="modal-title"><i class="fa fa-whatsapp"></i> Compartir Informe por WhatsApp</h4>
			</div>
			<div class="modal-body" style="padding: 16px;">
				<div class="form-group">
					<label style="font-weight: 600; font-size: 12px; color: #334155;">N&uacute;mero de Tel&eacute;fono (con c&oacute;digo de pa&iacute;s):</label>
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-phone"></i></span>
						<input type="text" id="waTelefonoMon" class="form-control" placeholder="Ej: 593987654321" required />
					</div>
					<span class="help-block" style="font-size: 11px; margin-bottom: 0;">Ingrese el n&uacute;mero sin espacios ni s&iacute;mbolo '+'.</span>
				</div>
				<div class="form-group">
					<label style="font-weight: 600; font-size: 12px; color: #334155;">Vista Previa del Mensaje Resumido:</label>
					<textarea id="waPreviewMensajeMon" class="form-control" rows="5" readonly style="font-size: 11px; background: #f8fafc; font-family: monospace;"></textarea>
				</div>
				<div style="display: flex; gap: 8px;">
					<button type="button" id="btnEnviarWaApiMon" class="btn btn-success btn-block" style="font-weight: 700; margin-top: 0;">
						<i class="fa fa-paper-plane"></i> Enviar V&iacute;a API ERP
					</button>
					<button type="button" id="btnAbrirWaWebMon" class="btn btn-default btn-block" style="font-weight: 700; color: #16a34a; border-color: #86efac; margin-top: 0;">
						<i class="fa fa-external-link"></i> Abrir WhatsApp Web
					</button>
				</div>
			</div>
			<div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 10px 18px;">
				<button type="button" class="btn btn-default btn-sm" id="btnCerrarWaMon2">Cerrar</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
<?php echo "(function() {\n"; ?>
	var audMonEmp = <?php echo (int)$audEmpCod; ?>;
	var audMonUsu = <?php echo (int)$audUsuCod; ?>;
	var audMonCharts = {};

	function audMonFmt(d) {
		var m = '' + (d.getMonth() + 1), dia = '' + d.getDate(), y = d.getFullYear();
		if (m.length < 2) m = '0' + m;
		if (dia.length < 2) dia = '0' + dia;
		return [y, m, dia].join('-');
	}

	function audMonCalcularRango(preset) {
		var hoy = new Date();
		var vIni = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
		var vFin = vIni;
		if (preset === 'ayer') {
			vIni = new Date(vIni.getFullYear(), vIni.getMonth(), vIni.getDate() - 1);
			vFin = vIni;
		} else if (preset === '1semana') {
			vIni = new Date(vIni.getFullYear(), vIni.getMonth(), vIni.getDate() - 6);
		} else if (preset === '1mes') {
			vIni = new Date(vIni.getFullYear(), vIni.getMonth(), vIni.getDate() - 29);
		} else if (preset === '3meses') {
			vIni = new Date(vIni.getFullYear(), vIni.getMonth(), vIni.getDate() - 89);
		} else if (preset !== 'hoy') {
			return null;
		}
		return { ini: audMonFmt(vIni), fin: audMonFmt(vFin) };
	}

	function audMonSincronizarPreset() {
		var ini = $('#mon_ini').val(), fin = $('#mon_fin').val();
		var presets = ['ayer', 'hoy', '1semana', '1mes', '3meses'];
		var coincide = null;
		for (var i = 0; i < presets.length; i++) {
			var r = audMonCalcularRango(presets[i]);
			if (r && r.ini === ini && r.fin === fin) { coincide = presets[i]; break; }
		}
		$('#monPeriodoPresets .aud-btn-preset').removeClass('active');
		if (coincide) {
			$('#monPeriodoPresets .aud-btn-preset[data-preset="' + coincide + '"]').addClass('active');
			$('#monPresetCustomBadge').hide();
		} else {
			$('#monPresetCustomBadge').show();
		}
	}

	function audMonInitCalendarios() {
		if (!$.fn.datepicker) return;
		var opts = {
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			showButtonPanel: true,
			firstDay: 1,
			monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
			monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
			dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','Sa']
		};
		$('#mon_ini').datepicker($.extend({}, opts, {
			onClose: function (d) { if (d) { try { $('#mon_fin').datepicker('option', 'minDate', d); } catch (e) {} } },
			onSelect: function () { audMonSincronizarPreset(); audMonCargar(); }
		}));
		$('#mon_fin').datepicker($.extend({}, opts, {
			onClose: function (d) { if (d) { try { $('#mon_ini').datepicker('option', 'maxDate', d); } catch (e) {} } },
			onSelect: function () { audMonSincronizarPreset(); audMonCargar(); }
		}));
	}

	function audMonDestruirGraficos() {
		for (var k in audMonCharts) {
			if (audMonCharts[k] && typeof audMonCharts[k].destroy === 'function') {
				try { audMonCharts[k].destroy(); } catch (e) {}
			}
		}
		audMonCharts = {};
	}

	function audMonChart(id, cfg) {
		if (!$('#' + id).length) return;
		var el = document.getElementById(id);
		if (!window.ApexCharts || !ApexCharts) { el.innerHTML = '<div class="chart-empty">Librer&iacute;a ApexCharts no disponible.</div>'; return; }
		try {
			audMonCharts[id] = new ApexCharts(el, cfg);
			audMonCharts[id].render();
		} catch (e) {
			el.innerHTML = '<div class="chart-empty">No hay datos suficientes para el gr&aacute;fico.</div>';
		}
	}

	function audMonVaciar(id, mensaje) {
		if ($('#' + id).length) {
			$('#' + id).html('<div class="chart-empty">' + (mensaje || 'Sin datos en el rango seleccionado.') + '</div>');
		}
	}

	function audMonRenderTabla(id, filas, cols) {
		var $t = $('#' + id + ' tbody');
		if (!filas || !filas.length) {
			$t.html('<tr><td colspan="' + cols + '" class="text-center text-muted" style="font-size:11px;">Sin datos en el rango</td></tr>');
			return;
		}
		var html = '';
		for (var i = 0; i < filas.length; i++) {
			html += '<tr><td style="text-align:center;">' + (i + 1) + '</td>';
			for (var j = 0; j < filas[i].length; j++) {
				html += '<td class="' + (j === 0 ? '' : 'text-right') + '">' + (filas[i][j] == null ? '' : filas[i][j]) + '</td>';
			}
			html += '</tr>';
		}
		$t.html(html);
	}

	// ---------- Widgets (orden + ocultos configurables de lugar) ----------
	function audMonWidgetClave() {
		return 'exa_aud_dash_mon_orden_' + audMonEmp + '_' + audMonUsu;
	}

	function audMonWidgetOcultosClave() {
		return 'exa_aud_dash_mon_ocultos_' + audMonEmp + '_' + audMonUsu;
	}

	function audMonWidgetOrdenGuardar() {
		var ids = [];
		$('#dashWidgetGrid .dash-widget').each(function () { ids.push($(this).attr('data-widget')); });
		try { localStorage.setItem(audMonWidgetClave(), JSON.stringify(ids)); } catch (e) {}
	}

	function audMonWidgetOcultosLeer() {
		try {
			var v = JSON.parse(localStorage.getItem(audMonWidgetOcultosClave()) || 'null');
			return (v && v.length) ? v : [];
		} catch (e) { return []; }
	}

	function audMonWidgetOcultosGuardar() {
		var ocultos = [];
		$('#dashWidgetOcultos .dash-widget').each(function () { ocultos.push($(this).attr('data-widget')); });
		try { localStorage.setItem(audMonWidgetOcultosClave(), JSON.stringify(ocultos)); } catch (e) {}
	}

	function audMonWidgetPersistirTodo() {
		audMonWidgetOrdenGuardar();
		audMonWidgetOcultosGuardar();
		if (window.audMonModalAbierto) audMonWidgetSyncCheck();
	}

	// Tamanos: mitad (w-12) o ancho completo (w-full), persiste por widget
	var audMonWidgetTamanosDef = {
		tendencia: 'w-12', horas: 'w-12', usuariosDia: 'w-full', topUsu: 'w-12',
		topPla: 'w-12', modulos: 'w-12', rankUsu: 'w-12', detPla: 'w-12'
	};

	function audMonWidgetTamanosClave() {
		return 'exa_aud_dash_mon_tamanos_' + audMonEmp + '_' + audMonUsu;
	}

	function audMonWidgetTamanosLeer() {
		try {
			var v = JSON.parse(localStorage.getItem(audMonWidgetTamanosClave()) || 'null');
			return (v && typeof v === 'object') ? v : {};
		} catch (e) { return {}; }
	}

	function audMonWidgetTamanosGuardar(id, size) {
		var v = audMonWidgetTamanosLeer();
		v[id] = size;
		try { localStorage.setItem(audMonWidgetTamanosClave(), JSON.stringify(v)); } catch (e) {}
	}

	function audMonWidgetResizeIconos() {
		$('.dash-widget').each(function () {
			var $i = $(this).find('.dash-size').first();
			if (!$i.length) return;
			if ($(this).hasClass('w-full')) {
				$i.removeClass('fa-expand').addClass('fa-compress');
			} else {
				$i.removeClass('fa-compress').addClass('fa-expand');
			}
		});
	}

	function audMonTamanosAplicar() {
		var saved = audMonWidgetTamanosLeer();
		$('.dash-widget').each(function () {
			var id = $(this).attr('data-widget');
			var size = saved[id] || audMonWidgetTamanosDef[id] || 'w-12';
			if (size !== 'w-full') size = 'w-12';
			$(this).removeClass('w-full w-12').addClass(size);
		});
		audMonWidgetResizeIconos();
	}

	function audMonWidgetEstaOculto(id) {
		return $('#dashWidgetOcultos .dash-widget[data-widget="' + id + '"]').length > 0;
	}

	function audMonWidgetOrdenCanonico() {
		var saved = null;
		try { saved = JSON.parse(localStorage.getItem(audMonWidgetClave()) || 'null'); } catch (e) {}
		if (saved && saved.length) {
			var seen = {}, extra = [];
			for (var i = 0; i < saved.length; i++) seen[saved[i]] = 1;
			$('#dashWidgetGrid .dash-widget, #dashWidgetOcultos .dash-widget').each(function () {
				var id = $(this).attr('data-widget');
				if (!seen[id]) extra.push(id);
			});
			return saved.concat(extra);
		}
		var def = [];
		$('#dashWidgetGrid .dash-widget, #dashWidgetOcultos .dash-widget').each(function () { def.push($(this).attr('data-widget')); });
		return def;
	}

	function audMonWidgetAplicar() {
		var $grid = $('#dashWidgetGrid');
		var ocultos = audMonWidgetOcultosLeer();
		var orden = audMonWidgetOrdenCanonico();
		var todos = {};
		$($grid.find('.dash-widget').get().concat($('#dashWidgetOcultos .dash-widget').get())).each(function () {
			todos[$(this).attr('data-widget')] = $(this);
		});
		$grid.find('.dash-widget').detach();
		$('#dashWidgetOcultos .dash-widget').detach();
		for (var i = 0; i < orden.length; i++) {
			if (!todos[orden[i]]) continue;
			var el = todos[orden[i]];
			if (ocultos.indexOf(orden[i]) !== -1) {
				$('#dashWidgetOcultos').append(el);
			} else {
				$grid.append(el);
			}
		}
		$grid.find('.dash-widget').css({ display: '', 'float': '' });
		audMonTamanosAplicar();
	}

	function audMonWidgetOcultar(id) {
		var $w = $('.dash-widget[data-widget="' + id + '"]');
		$('#dashWidgetOcultos').append($w);
		audMonWidgetPersistirTodo();
	}

	function audMonWidgetMostrar(id) {
		var $w = $('#dashWidgetOcultos .dash-widget[data-widget="' + id + '"]');
		if ($w.length) $('#dashWidgetGrid').append($w);
		audMonWidgetPersistirTodo();
		audMonRedimensionar();
	}

	function audMonWidgetSbox($w, oculto, ordenId) {
		var titulo = $w.find('.dash-title').text().trim();
		var ico = 'th-large';
		var $ic = $w.find('.dash-title i').first();
		if ($ic.length) {
			var cls = $ic.attr('class') || '';
			var partes = cls.split(' ');
			for (var i = partes.length - 1; i >= 0; i--) {
				if (partes[i] && partes[i] !== 'fa') { ico = partes[i].replace(/^fa-/, ''); break; }
			}
		}
		return '<div class="aud-sbox' + (oculto ? ' aud-sbox-off' : '') + '" data-id="' + ordenId + '">'
			+ '<i class="fa ' + (oculto ? 'fa-eye-slash' : 'fa-eye') + ' kbox-eye" title="Clic para mostrar/ocultar"></i>'
			+ '<i class="fa fa-' + ico + ' kbox-ico"></i>'
			+ '<span class="kbox-txt">' + titulo + '</span>'
			+ '</div>';
	}

	function audMonWidgetSyncCheck() {
		var ocultos = audMonWidgetOcultosLeer();
		$('#widgetsSorter .aud-sbox').each(function () {
			var $b = $(this);
			var off = ocultos.indexOf($b.attr('data-id')) !== -1;
			$b.toggleClass('aud-sbox-off', off);
			$b.find('.kbox-eye').attr('class', 'fa ' + (off ? 'fa-eye-slash' : 'fa-eye') + ' kbox-eye');
		});
		audMonKpisSyncCheck();
	}

	function audMonWidgetConstruirLista() {
		var $lista = $('#widgetsSorter');
		if ($lista.length) {
			var orden = audMonWidgetOrdenCanonico();
			var ocultos = audMonWidgetOcultosLeer();
			var html = '';
			for (var i = 0; i < orden.length; i++) {
				var $w = $('#dashWidgetGrid .dash-widget[data-widget="' + orden[i] + '"], #dashWidgetOcultos .dash-widget[data-widget="' + orden[i] + '"]').first();
				if (!$w.length) continue;
				html += audMonWidgetSbox($w, ocultos.indexOf(orden[i]) !== -1, orden[i]);
			}
			$lista.html(html || '<p class="text-muted">Sin widgets.</p>');
		}
		audMonKpisConstruirLista();
	}

	function audMonRedimensionar() {
		setTimeout(function () {
			for (var k in audMonCharts) {
				if (audMonCharts[k] && typeof audMonCharts[k].resize === 'function') {
					try { audMonCharts[k].resize(); } catch (e) {}
				}
			}
		}, 40);
	}

	function audMonIniciarWidgets() {
		var $grid = $('#dashWidgetGrid');
		if ($grid.length && $.fn.sortable) {
			$grid.sortable({
				items: '.dash-widget',
				handle: '.dash-widget-head',
				placeholder: 'dash-widget-placeholder',
				forcePlaceholderSize: true,
				tolerance: 'pointer',
				helper: 'original',
				start: function (e, ui) { ui.placeholder.height(ui.item.outerHeight()); },
				stop: function () {
					audMonWidgetOrdenGuardar();
					audMonRedimensionar();
				}
			});
			$grid.disableSelection();
		}
		var $kpi = $('#dashKpiGrid');
		if ($kpi.length && $.fn.sortable) {
			$kpi.sortable({
				items: '.dash-kpi-card',
				handle: '.dash-kpi-card .kpi-mon-card',
				placeholder: 'dash-kpi-placeholder',
				forcePlaceholderSize: true,
				tolerance: 'pointer',
				helper: 'original',
				stop: function () {
					audMonKpisGuardar();
				}
			});
			$kpi.disableSelection();
		}
		var $ks = $('#kpisSorter');
		if ($ks.length && $.fn.sortable) {
			$ks.sortable({
				items: '.aud-sbox',
				placeholder: 'aud-sbox-placeholder',
				forcePlaceholderSize: true,
				tolerance: 'pointer'
			});
			$ks.disableSelection();
		}
		var $ws = $('#widgetsSorter');
		if ($ws.length && $.fn.sortable) {
			$ws.sortable({
				items: '.aud-sbox',
				placeholder: 'aud-sbox-placeholder',
				forcePlaceholderSize: true,
				tolerance: 'pointer'
			});
			$ws.disableSelection();
		}
	}

	// ---------- KPIs configurables (mover / ocultar / anadir) ----------
	var audMonLastData = null;
	var audMonKpis = [
		{ id: 'total', titulo: 'Total Movimientos', icono: 'database', color: '', sub: 'En el rango', fn: function (d) { return d.resumen ? d.resumen.total : 0; } },
		{ id: 'insert', titulo: 'Ingresar', icono: 'plus-circle', color: 'green', sub: 'Operaciones', fn: function (d) { return d.resumen ? d.resumen.insert : 0; } },
		{ id: 'update', titulo: 'Actualizar', icono: 'pencil', color: 'amber', sub: 'Operaciones', fn: function (d) { return d.resumen ? d.resumen.update : 0; } },
		{ id: 'delete', titulo: 'Eliminar', icono: 'times-circle', color: 'red', sub: 'Operaciones', fn: function (d) { return d.resumen ? d.resumen.delete : 0; } },
		{ id: 'usuarios', titulo: 'Usuarios únicos', icono: 'users', color: 'purple', sub: 'En el rango', fn: function (d) { return d.resumen ? d.resumen.usuarios_unicos : 0; } },
		{ id: 'dias', titulo: 'Días con Datos', icono: 'calendar', color: '', sub: 'En el rango', fn: function (d) { return d.tendencia && d.tendencia.categorias ? d.tendencia.categorias.length : 0; } },
		{ id: 'modulosAct', titulo: 'Módulos activos', icono: 'cubes', color: 'purple', sub: 'Con actividad', fn: function (d) { return (d.modulos || []).length; } },
		{ id: 'plantasAct', titulo: 'Plantas activas', icono: 'industry', color: 'amber', sub: 'Con movimiento', fn: function (d) { return (d.plantas_top || []).length; } },
		{ id: 'usuTop', titulo: 'Usuarios top', icono: 'star', color: 'green', sub: 'Mayor actividad', fn: function (d) { return (d.usuarios_top || []).length; } },
		{ id: 'promedio', titulo: 'Promedio diario', icono: 'tachometer', color: 'red', sub: 'Movimientos / día', fn: function (d) {
			var t = d.resumen ? (d.resumen.total || 0) : 0, dd = d.tendencia && d.tendencia.categorias ? d.tendencia.categorias.length : 0;
			return dd ? Math.round((t / dd) * 100) / 100 : 0;
		} },
		{ id: 'rango', titulo: 'Rango consultado', icono: 'calendar-o', color: '', sub: '', fn: function (d) { return d.rango || '—'; } }
	];

	function audMonKpisBuscar(id) {
		for (var i = 0; i < audMonKpis.length; i++) { if (audMonKpis[i].id === id) return audMonKpis[i]; }
		return null;
	}

	function audMonKpisClave() {
		return 'exa_aud_dash_mon_kpis_' + audMonEmp + '_' + audMonUsu;
	}

	function audMonKpisLeer() {
		try {
			var v = JSON.parse(localStorage.getItem(audMonKpisClave()) || 'null');
			if (v && v.length) {
				var valid = [], seen = {};
				for (var i = 0; i < v.length; i++) {
					if (audMonKpisBuscar(v[i]) && !seen[v[i]]) { valid.push(v[i]); seen[v[i]] = 1; }
				}
				return valid;
			}
		} catch (e) {}
		var def = [];
		for (var j = 0; j < 6 && j < audMonKpis.length; j++) def.push(audMonKpis[j].id);
		return def;
	}

	function audMonKpisGuardar() {
		var ids = [];
		$('#dashKpiGrid .dash-kpi-card').each(function () { ids.push($(this).attr('data-kpi')); });
		try { localStorage.setItem(audMonKpisClave(), JSON.stringify(ids)); } catch (e) {}
	}

	function audMonKpisFormato(k, val) {
		if (typeof val === 'number') {
			if (k.id === 'promedio') return String(Math.round(val * 100) / 100).replace('.', ',');
			return (val != null ? val : 0).toLocaleString();
		}
		return val;
	}

	function audMonKpisRender(data) {
		data = data || audMonLastData || {};
		var ids = audMonKpisLeer();
		var html = '';
		for (var i = 0; i < ids.length; i++) {
			var k = audMonKpisBuscar(ids[i]);
			if (!k) continue;
			var val = k.fn ? k.fn(data) : 0;
			html += '<div class="dash-kpi-card" data-kpi="' + k.id + '">'
				+ '<div class="kpi-mon-card ' + k.color + '">'
				+ '<div class="kpi-head"><span class="kpi-label"><i class="fa fa-' + k.icono + '"></i> ' + k.titulo + '</span>'
				+ '<i class="fa fa-eye-slash kpi-hide" title="Ocultar métrica"></i></div>'
				+ '<div class="kpi-val">' + audMonKpisFormato(k, val) + '</div>'
				+ '<div class="kpi-sub">' + (k.sub || '&nbsp;') + '</div>'
				+ '</div></div>';
		}
		$('#dashKpiGrid').html(html || '<div class="text-muted" style="font-size:12px;padding:8px 6px;">No hay métricas visibles.</div>');
		var foot = [];
		if (data.empresa) foot.push('<i class="fa fa-building-o"></i> ' + data.empresa);
		if (data.rango) foot.push('<i class="fa fa-calendar-o"></i> ' + data.rango);
		if (data.plantas_top && data.plantas_top.length) foot.push('<i class="fa fa-industry"></i> ' + data.plantas_top.length + ' planta(s)');
		$('#kpiFootMon').html(foot.join(' &nbsp;&middot;&nbsp; ') || '&nbsp;');
	}

	function audMonKpisQuitar(id) {
		var ids = audMonKpisLeer();
		var i = ids.indexOf(id);
		if (i !== -1) ids.splice(i, 1);
		try { localStorage.setItem(audMonKpisClave(), JSON.stringify(ids)); } catch (e) {}
		audMonKpisRender(audMonLastData);
	}

	function audMonKpisConstruirLista() {
		var $lista = $('#kpisSorter');
		if (!$lista.length) return;
		var ids = audMonKpisLeer();
		var canon = ids.slice();
		for (var i = 0; i < audMonKpis.length; i++) {
			if (canon.indexOf(audMonKpis[i].id) === -1) canon.push(audMonKpis[i].id);
		}
		var html = '';
		for (var j = 0; j < canon.length; j++) {
			var k = audMonKpisBuscar(canon[j]);
			if (!k) continue;
			html += audMonKpisSbox(k, ids.indexOf(k.id) === -1);
		}
		$lista.html(html || '<p class="text-muted">Sin métricas.</p>');
	}

	function audMonKpisSbox(k, oculto) {
		return '<div class="aud-sbox' + (oculto ? ' aud-sbox-off' : '') + '" data-id="' + k.id + '">'
			+ '<i class="fa ' + (oculto ? 'fa-eye-slash' : 'fa-eye') + ' kbox-eye" title="Clic para mostrar/ocultar"></i>'
			+ '<i class="fa fa-' + k.icono + ' kbox-ico"></i>'
			+ '<span class="kbox-txt">' + k.titulo + '</span>'
			+ '</div>';
	}

	function audMonKpisSyncCheck() {
		var ids = audMonKpisLeer();
		$('#kpisSorter .aud-sbox').each(function () {
			var $b = $(this);
			var off = ids.indexOf($b.attr('data-id')) === -1;
			$b.toggleClass('aud-sbox-off', off);
			$b.find('.kbox-eye').attr('class', 'fa ' + (off ? 'fa-eye-slash' : 'fa-eye') + ' kbox-eye');
		});
	}

	function audMonRender(data) {
		if (!data || !data.resumen) return;
		audMonLastData = data;
		audMonKpisRender(data);

		// Tendencia
		var tend = data.tendencia || {};
		if (tend.categorias && tend.categorias.length) {
			audMonChart('chartTendenciaMon', {
				chart: { type: 'area', toolbar: { show: false }, height: 240 },
				series: [{ name: 'Movimientos', data: tend.serie }],
				xaxis: { categories: tend.categorias, labels: { rotate: -45, style: { fontSize: '10px' } } },
				colors: ['#2563eb'],
				dataLabels: { enabled: false },
				stroke: { curve: 'smooth', width: 2 },
				fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } }
			});
		} else {
			audMonVaciar('chartTendenciaMon');
		}

		// Horas
		var horas = data.horarios || [];
		if (horas.length) {
			audMonChart('chartHorasMon', {
				chart: { type: 'bar', toolbar: { show: false }, height: 240 },
				series: [{ name: 'Movimientos', data: horas.map(function (h) { return h.total; }) }],
				xaxis: { categories: horas.map(function (h) { return h.hora; }), labels: { rotate: -45, style: { fontSize: '9px' } } },
				plotOptions: { bar: { columnWidth: '60%' } },
				colors: ['#0284c7'],
				dataLabels: { enabled: false }
			});
		} else {
			audMonVaciar('chartHorasMon');
		}

		// Comportamiento diario por usuario (apilado)
		var ud = data.usuario_dias || {};
		if (ud.categorias && ud.categorias.length && ud.series && ud.series.length) {
			audMonChart('chartUsuariosDiaMon', {
				chart: { type: 'area', stacked: true, toolbar: { show: false }, height: 240 },
				series: ud.series.map(function (s) { return { name: s.nombre, data: s.serie }; }),
				xaxis: { categories: ud.categorias, labels: { rotate: -45, style: { fontSize: '10px' } } },
				dataLabels: { enabled: false },
				stroke: { curve: 'smooth', width: 2 },
				legend: { fontSize: '10px', position: 'bottom' },
				colors: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f43f5e', '#84cc16']
			});
		} else {
			audMonVaciar('chartUsuariosDiaMon');
		}

		// Top usuarios
		var topU = data.usuarios_top || [];
		if (topU.length) {
			audMonChart('chartTopUsuMon', {
				chart: { type: 'bar', toolbar: { show: false }, height: 240 },
				series: [{ name: 'Operaciones', data: topU.map(function (u) { return u.total; }) }],
				xaxis: { categories: topU.map(function (u) { return u.nombre; }), labels: { style: { fontSize: '10px' } } },
				plotOptions: { bar: { horizontal: true, barHeight: '55%' } },
				colors: ['#10b981'],
				dataLabels: { enabled: false }
			});
		} else {
			audMonVaciar('chartTopUsuMon');
		}

		// Top plantas
		var topP = data.plantas_top || [];
		if (topP.length) {
			audMonChart('chartTopPlaMon', {
				chart: { type: 'bar', toolbar: { show: false }, height: 240 },
				series: [{ name: 'Movimientos', data: topP.map(function (p) { return p.total; }) }],
				xaxis: { categories: topP.map(function (p) { return p.planta; }), labels: { style: { fontSize: '10px' } } },
				plotOptions: { bar: { horizontal: true, barHeight: '55%' } },
				colors: ['#f59e0b'],
				dataLabels: { enabled: false }
			});
		} else {
			audMonVaciar('chartTopPlaMon');
		}

		// Modulos (grafico + lista compacta)
		var mods = data.modulos || [];
		if (mods.length) {
			audMonChart('chartModulosMon', {
				chart: { type: 'bar', toolbar: { show: false }, height: 220 },
				series: [{ name: 'Movimientos', data: mods.map(function (m) { return m.total; }) }],
				xaxis: { categories: mods.map(function (m) { return m.modulo; }), labels: { rotate: -45, style: { fontSize: '10px' } } },
				colors: ['#2563eb'],
				dataLabels: { enabled: false }
			});
			var $ml = $('#minListaModulos');
			if ($ml.length) {
				var hL = '';
				$.each(mods, function (i, m) {
					hL += '<div class="aud-mini-item"><span>' + m.modulo + '</span><b>' + (m.total == null ? 0 : m.total) + '</b></div>';
				});
				$ml.html(hL);
			}
		} else {
			audMonVaciar('chartModulosMon');
			if ($('#minListaModulos').length) $('#minListaModulos').html('');
		}

		// Tablas
		audMonRenderTabla('tablaTopUsuarios', topU.map(function (u) { return [u.nombre, u.insert, u.update, u.delete, u.total]; }), 6);
		audMonRenderTabla('tablaTopPlantas', topP.map(function (p) { return [p.planta, p.usuarios, p.insert, p.update, p.delete, p.total]; }), 7);
	}

	// ---------- Filtros avanzados (mismos filtros que Monitoreo) ----------
	function audMonFiltrosActuales() {
		return {
			eve: $('#mon_eve').length ? $('#mon_eve').val() : 0,
			org: $('#mon_org').length ? $('#mon_org').val() : 0,
			dir: $('#mon_dir').length ? $('#mon_dir').val() : 0,
			pcs: $('#mon_pcs').length ? $('#mon_pcs').val() : 0,
			usu: $('#mon_usu').length ? $('#mon_usu').val() : 0,
			suc: $('#mon_suc').length ? $('#mon_suc').val() : 0,
			pla: $('#mon_pla').length ? $('#mon_pla').val() : 0
		};
	}

	function audMonFiltrosActualizarBadge() {
		var f = audMonFiltrosActuales();
		var n = 0;
		$.each(f, function (k, v) { if (v && String(v) !== '0') n++; });
		var $b = $('#monFiltrosBadge');
		if (n > 0) { $b.text(n).show(); } else { $b.hide(); }
	}

	$('#btnMonFiltrosToggle').on('click', function () {
		$('#monFiltrosRow').slideToggle(150);
	});

	function audMonCargarDirectorios(org, dirSel) {
		$.getJSON('../LOGICA/aud_log_dashboard_monitoreo.php', { directoriosAjax: 1, org: org || 0 }, function (resp) {
			var $sel = $('#mon_dir');
			var html = '<option value="0">Todos</option>';
			$.each((resp && resp.rows) || [], function (i, d) {
				html += '<option value="' + d.Org_Cod + '">' + $('<div>').text(d.Org_Des).html() + '</option>';
			});
			$sel.html(html);
			if (dirSel) $sel.val(dirSel);
		});
	}

	function audMonCargarProcesos(dir, org, pcsSel) {
		$.getJSON('../LOGICA/aud_log_dashboard_monitoreo.php', { procesosAjax: 1, dir: dir || 0, org: org || 0 }, function (resp) {
			var $sel = $('#mon_pcs');
			var html = '<option value="0">Todos</option>';
			$.each((resp && resp.rows) || [], function (i, p) {
				var nom = p.Pcs_Lin || p.Pcs_Nom || ('Proceso ' + p.Pcs_Cod);
				html += '<option value="' + p.Pcs_Cod + '">' + $('<div>').text(nom).html() + '</option>';
			});
			$sel.html(html);
			if (pcsSel) $sel.val(pcsSel);
			audMonVerificarPlanta();
		});
	}

	function audMonVerificarPlanta() {
		var pcs = $('#mon_pcs').val();
		if (!pcs || pcs === '0') {
			$('#monFilPlantaWrap').hide();
			return;
		}
		$.getJSON('../LOGICA/aud_log_dashboard_monitoreo.php', { plantaProcesoAjax: 1, pcs: pcs }, function (resp) {
			$('#monFilPlantaWrap').toggle(!!(resp && resp.tienePlanta));
		});
	}

	$('#mon_org').on('change', function () {
		audMonCargarDirectorios($(this).val());
		audMonCargarProcesos(0, $(this).val());
	});
	$('#mon_dir').on('change', function () {
		audMonCargarProcesos($(this).val(), $('#mon_org').val());
	});
	$('#mon_pcs').on('change', function () {
		audMonVerificarPlanta();
	});
	$('#mon_eve, #mon_dir, #mon_pcs, #mon_usu, #mon_suc, #mon_pla, #mon_org').on('change', function () {
		audMonFiltrosActualizarBadge();
		audMonCargar();
	});

	$('#btnMonFiltrosLimpiar').on('click', function () {
		$('#mon_eve, #mon_org, #mon_usu, #mon_suc, #mon_pla').val('0');
		audMonCargarDirectorios(0);
		audMonCargarProcesos(0, 0);
		audMonFiltrosActualizarBadge();
		audMonCargar();
	});

	function audMonCargar() {
		var ini = $('#mon_ini').val(), fin = $('#mon_fin').val();
		$('#monRangoLabel').text('Rango seleccionado: ' + ini + ' al ' + fin);
		var params = $.extend({
			action: 'consultar',
			ini: ini + ' 00:00:00',
			fin: fin + ' 23:59:59'
		}, audMonFiltrosActuales());
		$.getJSON('../LOGICA/aud_log_dashboard_monitoreo.php', params).done(function (res) {
			if (res && res.success) {
				audMonDestruirGraficos();
				audMonRender(res);
			} else {
				alert('Error al consultar los datos del panel.');
			}
		}).fail(function () {
			alert('No se pudo conectar con el servicio de estadisticas.');
		});
	}

	// Presets
	$('#monPeriodoPresets').on('click', '.aud-btn-preset', function () {
		var preset = $(this).data('preset');
		var r = audMonCalcularRango(preset);
		if (!r) return;
		$('#mon_ini').val(r.ini);
		$('#mon_fin').val(r.fin);
		audMonSincronizarPreset();
		audMonCargar();
	});

	$('#btnRecargarMon').on('click', function () {
		audMonCargar();
	});

	$('#btnResetWidgetsMon').on('click', function () {
		try { localStorage.removeItem(audMonWidgetClave()); } catch (e) {}
		try { localStorage.removeItem(audMonWidgetOcultosClave()); } catch (e) {}
		try { localStorage.removeItem(audMonWidgetTamanosClave()); } catch (e) {}
		try { localStorage.removeItem(audMonKpisClave()); } catch (e) {}
		audMonWidgetAplicar();
		audMonWidgetSyncCheck();
		audMonKpisRender(audMonLastData);
		audMonRedimensionar();
	});

	// ---------- Ocultar widget desde su cabecera ----------
	$(document).on('mousedown', '.dash-hide', function (e) {
		e.stopPropagation();
	});
	$(document).on('click', '.dash-hide', function (e) {
		e.preventDefault();
		e.stopPropagation();
		audMonWidgetOcultar($(this).closest('.dash-widget').attr('data-widget'));
	});

	// ---------- Cambiar tamano del widget (mitad / ancho completo) ----------
	$(document).on('mousedown', '.dash-size', function (e) {
		e.stopPropagation();
	});
	$(document).on('click', '.dash-size', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $w = $(this).closest('.dash-widget');
		var id = $w.attr('data-widget');
		var size = $w.hasClass('w-full') ? 'w-12' : 'w-full';
		$w.removeClass('w-full w-12').addClass(size);
		audMonWidgetTamanosGuardar(id, size);
		audMonWidgetResizeIconos();
		audMonRedimensionar();
	});

	// ---------- Ocultar metrica (KPI) desde su cabecera ----------
	$(document).on('mousedown', '.kpi-hide', function (e) {
		e.stopPropagation();
	});
	$(document).on('click', '.kpi-hide', function (e) {
		e.preventDefault();
		e.stopPropagation();
		audMonKpisQuitar($(this).closest('.dash-kpi-card').attr('data-kpi'));
	});

	// ---------- Modal personalizacion de widgets ----------
	function audMonModalAbrir() {
		var $m = $('#modalWidgetsMon');
		$('.modal-backdrop').remove();
		$('<div class="modal-backdrop fade in"></div>').appendTo('body');
		$m.addClass('in').css('display', 'block');
		window.audMonModalAbierto = true;
		audMonWidgetConstruirLista();
	}

	function audMonModalCerrar() {
		var $m = $('#modalWidgetsMon');
		$m.removeClass('in').css('display', 'none');
		$('.modal-backdrop').remove();
		window.audMonModalAbierto = false;
	}

	$('#btnCfgWidgetsMon').on('click', function () { audMonModalAbrir(); });

	// Cerrar el dropdown de Acciones al seleccionar una opcion
	$(document).on('click', '.aud-acciones-menu a', function () {
		var $dd = $(this).closest('.dropdown');
		$dd.removeClass('open');
		$dd.find('.dropdown-toggle').attr('aria-expanded', 'false');
	});
	$('#btnCerrarWidgetsMon, #btnCerrarWidgetsMon2').on('click', function () { audMonModalCerrar(); });

	// ---------- Exportar PDF / Correo / WhatsApp del periodo ----------
	function audMonFechaParams() {
		var ini = $('#mon_ini').val(), fin = $('#mon_fin').val();
		if (!ini) { ini = audMonFmt(new Date(new Date().getFullYear(), new Date().getMonth(), new Date().getDate() - 29)); }
		if (!fin) { fin = audMonFmt(new Date()); }
		return $.extend({ ini: ini + ' 00:00:00', fin: fin + ' 23:59:59' }, audMonFiltrosActuales());
	}

	function audMonModalGenericoAbrir(id) {
		var $m = $(id);
		$('.modal-backdrop').remove();
		$('<div class="modal-backdrop fade in"></div>').appendTo('body');
		$m.addClass('in').css('display', 'block');
	}

	function audMonModalGenericoCerrar(id) {
		$(id).removeClass('in').css('display', 'none');
		$('.modal-backdrop').remove();
	}

	function audMonPreviewWhatsApp() {
		var d = audMonLastData || {};
		var txt = "*INFORME ESTADÍSTICO DE MONITOREO DE AUDITORÍA*\n" +
			"Empresa: " + (d.empresa || '') + "\n" +
			"Rango: " + (d.rango || '') + "\n\n";
		if (d.resumen) {
			txt += "• Total Movimientos: " + Number(d.resumen.total || 0).toLocaleString() + "\n";
			txt += "• Ingresar: " + Number(d.resumen.insert || 0).toLocaleString() + "\n";
			txt += "• Actualizar: " + Number(d.resumen.update || 0).toLocaleString() + "\n";
			txt += "• Eliminar: " + Number(d.resumen.delete || 0).toLocaleString() + "\n";
			txt += "• Usuarios Únicos: " + Number(d.resumen.usuarios_unicos || 0) + "\n";
		}
		if (d.modulos && d.modulos.length) {
			txt += "\nMódulos con más actividad:\n";
			for (var i = 0; i < Math.min(d.modulos.length, 5); i++) {
				txt += "• " + d.modulos[i].modulo + ": " + Number(d.modulos[i].total || 0).toLocaleString() + "\n";
			}
		}
		txt += "\n_Generado por ExaContable ERP Auditoría_";
		return txt;
	}

	// Descargar PDF del periodo
	$('#btnDescargarPdfMon').on('click', function () {
		var f = audMonFechaParams();
		var url = '../LOGICA/aud_log_dashboard_monitoreo.php?action=exportar_pdf&' + $.param(f);
		window.open(url, '_blank');
	});

	// Modal Envio Correo
	$('#btnModalCorreoMon').on('click', function () {
		audMonModalGenericoAbrir('#modalEnvioCorreoMon');
	});
	$('#btnCerrarCorreoMon, #btnCerrarCorreoMon2').on('click', function () {
		audMonModalGenericoCerrar('#modalEnvioCorreoMon');
	});

	$('#btnEnviarCorreoConfirmMon').on('click', function () {
		var correo = $('#mailDestinatarioMon').val().trim();
		if (!correo) {
			alert('Por favor ingrese el correo de destino.');
			return;
		}
		var f = audMonFechaParams();
		var $btn = $(this);
		$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');

		$.ajax({
			url: '../LOGICA/aud_log_dashboard_monitoreo.php',
			type: 'POST',
			dataType: 'json',
			data: $.extend({
				action: 'enviar_correo',
				correo: correo,
				nombre: $('#mailNombreMon').val().trim(),
				asunto: $('#mailAsuntoMon').val().trim()
			}, f),
			success: function (res) {
				$btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Documento');
				if (res && res.success) {
					audMonModalGenericoCerrar('#modalEnvioCorreoMon');
					alert(res.message || 'Reporte enviado exitosamente.');
				} else {
					alert('Error: ' + (res.message || 'No se pudo enviar el correo'));
				}
			},
			error: function () {
				$btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Documento');
				alert('Fallo de conexión al enviar correo.');
			}
		});
	});

	// Modal WhatsApp
	$('#btnModalWhatsAppMon').on('click', function () {
		$('#waPreviewMensajeMon').text(audMonPreviewWhatsApp());
		audMonModalGenericoAbrir('#modalEnvioWhatsAppMon');
	});
	$('#btnCerrarWaMon, #btnCerrarWaMon2').on('click', function () {
		audMonModalGenericoCerrar('#modalEnvioWhatsAppMon');
	});

	function audMonEnviarWhatsApp(btnId, botonHtml) {
		var tel = $('#waTelefonoMon').val().trim();
		if (!tel) {
			alert('Por favor ingrese el número de teléfono destinatario.');
			return;
		}
		var f = audMonFechaParams();
		var $btn = $(btnId);
		$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');

		$.ajax({
			url: '../LOGICA/aud_log_dashboard_monitoreo.php',
			type: 'POST',
			dataType: 'json',
			data: $.extend({
				action: 'enviar_whatsapp',
				telefono: tel
			}, f),
			success: function (res) {
				$btn.prop('disabled', false).html(botonHtml);
				if (res && res.url_whatsapp) {
					window.open(res.url_whatsapp, '_blank');
				}
				if (res && res.success) {
					audMonModalGenericoCerrar('#modalEnvioWhatsAppMon');
				} else if (res) {
					alert('Error: ' + (res.message || 'No se pudo procesar WhatsApp'));
				}
			},
			error: function () {
				$btn.prop('disabled', false).html(botonHtml);
				alert('Fallo de conexión al enviar WhatsApp.');
			}
		});
	}

	$('#btnEnviarWaApiMon').on('click', function () {
		audMonEnviarWhatsApp('#btnEnviarWaApiMon', '<i class="fa fa-paper-plane"></i> Enviar Vía API ERP');
	});

	$('#btnAbrirWaWebMon').on('click', function () {
		audMonEnviarWhatsApp('#btnAbrirWaWebMon', '<i class="fa fa-external-link"></i> Abrir WhatsApp Web');
	});

	// Toggle mostrar/ocultar de un cuadro dentro del modal
	$(document).on('mousedown', '.kbox-eye', function (e) {
		e.stopPropagation();
	});
	$(document).on('click', '.kbox-eye', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $b = $(this).closest('.aud-sbox');
		$b.toggleClass('aud-sbox-off');
		var off = $b.hasClass('aud-sbox-off');
		$b.find('.kbox-eye').attr('class', 'fa ' + (off ? 'fa-eye-slash' : 'fa-eye') + ' kbox-eye');
	});

	$('#btnGuardarWidgetsMon').on('click', function () {
		var kpiIds = [];
		$('#kpisSorter .aud-sbox').each(function () {
			var $b = $(this);
			if (!$b.hasClass('aud-sbox-off')) kpiIds.push($b.attr('data-id'));
		});
		try { localStorage.setItem(audMonKpisClave(), JSON.stringify(kpiIds)); } catch (e) {}
		audMonKpisRender(audMonLastData);

		var wOrden = [];
		var wOcultos = [];
		$('#widgetsSorter .aud-sbox').each(function () {
			var $b = $(this);
			var id = $b.attr('data-id');
			wOrden.push(id);
			if ($b.hasClass('aud-sbox-off')) wOcultos.push(id);
		});
		try { localStorage.setItem(audMonWidgetClave(), JSON.stringify(wOrden)); } catch (e) {}
		try { localStorage.setItem(audMonWidgetOcultosClave(), JSON.stringify(wOcultos)); } catch (e) {}
		audMonWidgetAplicar();
		audMonModalCerrar();
		audMonRedimensionar();
	});

	// Carga perezosa del iframe comparativo al activar la pestaña
	$('#audDashTabs a[href="#tabComparativo"]').on('shown.bs.tab', function () {
		var $if = $('#iframeComparativo');
		if ($if.attr('src') === '') {
			$if.attr('src', $if.data('src'));
		}
		setTimeout(audMonAjustarIframe, 60);
	});

	$('#audDashTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		if (e.target && $(e.target).attr('href') === '#tabInicio') {
			var $w = $('#tabInicio').width();
			for (var k in audMonCharts) {
				if (audMonCharts[k] && typeof audMonCharts[k].resize === 'function') {
					try { audMonCharts[k].resize(); } catch (er) {}
				}
			}
		}
	});

	function audMonAjustarIframe() {
		var panelTop = 0;
		var $ph = $('.panel-heading.exa-header');
		if ($ph.length) panelTop = $ph.offset().top;
		var h = Math.max(600, (window.innerHeight || $(window).height()) - panelTop - 90);
		$('#iframeComparativo').height(h);
	}

	$(window).on('resize', function () {
		audMonAjustarIframe();
	});

	$(document).ready(function () {
		audMonWidgetAplicar();
		audMonIniciarWidgets();
		audMonInitCalendarios();
		audMonSincronizarPreset();
		audMonCargar();
		audMonAjustarIframe();
	});
<?php echo "})();\n"; ?>
</script>
</body>
</html>