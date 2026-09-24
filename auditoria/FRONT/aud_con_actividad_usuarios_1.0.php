<?php
/**
 * Monitor de Actividad en Tiempo Real y Control de Sesiones.
 * 
 * Permite supervisar en vivo usuarios conectados, tiempos de permanencia,
 * equipos, direcciones IP y forzar desconexiones por parte del Administrador.
 *
 * @package auditoria.FRONT
 */

if (session_id() === '' && !headers_sent()) {
	@session_start();
}

require_once dirname(__FILE__) . '/../../administrador/LOGICA/seguridad.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_log_actividad_sesion.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_log_config_monitoreo.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_log_interpretar.php';

$audEmpCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
$audSucCod = isset($_SESSION['Ses_Suc_Cod']) ? (int)$_SESSION['Ses_Suc_Cod'] : 0;
$audUsuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;
$Ses_Dat_Dis = isset($_SESSION['Ses_Dat_Dis']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']) : '';

require_once dirname(__FILE__) . '/../LOGICA/aud_log_acceso_directorio.php';
aud_acceso_directorio_gate($audEmpCod);

$obBD_con1 = new Class_Log_Datos_CfgMon();
$obBD_conexion = new Class_Log_Conexion_CfgMon($Ses_Dat_Dis !== '' ? $Ses_Dat_Dis : null);
aud_ses_asegurar_esquema($obBD_conexion->conexion);

// Validar si es Administrador de Sistemas
$esAdminSistemas = aud_cfg_es_admin_sistemas($obBD_con1, $obBD_conexion, $audUsuCod);

// Roles para el combo filtro
$roles = $obBD_con1->getArrayConsulta(9, array($audEmpCod), $obBD_conexion);
if (!is_array($roles)) $roles = array();
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<title>Actividad de Usuarios y Sesiones - Auditoria</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

	<!-- Estilos Oficiales ExaContable ERP -->
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
	<?php require_once("../../mascaras/model3/estilos/estilos.php"); ?>
	<script type="text/javascript" src="../../framework/jquery/apexcharts/apexcharts.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../RECURSOS/aud_monitoreo_ui_1.0.css?v=20260923_v14" />

	<style>
		/* Estilos armonizados con el tema visual de ExaContable */
		.aud-kpi-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 12px;
			margin-bottom: 14px;
		}

		/* Cuadricula principal: tabla de sesiones + barra lateral */
		.aud-main-grid {
			display: grid;
			grid-template-columns: minmax(0, 1fr) 300px;
			grid-template-rows: minmax(0, 1fr);
			gap: 14px;
			flex: 1 1 auto;
			min-height: 0;
			width: 100%;
		}
		@media (max-width: 991px) {
			.aud-main-grid {
				grid-template-columns: minmax(0, 1fr);
				grid-template-rows: auto auto;
			}
		}
		.aud-main-grid .aud-panel {
			margin-bottom: 0;
		}
		.aud-panel-flex {
			display: flex;
			flex-direction: column;
			min-height: 0;
		}
		.aud-table-wrap {
			flex: 1 1 auto;
			overflow-y: auto;
			min-height: 120px;
		}

		/* Paginador estilo jqGrid (igual al monitor de actividades) */
		.aud-grid-pager {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 8px;
			flex-wrap: wrap;
			background: #f7f9fc;
			border-top: 1px solid #e2e8f0;
			padding: 6px 10px;
			font-size: 11px;
			color: #334155;
		}
		.aud-pag-left,
		.aud-pag-right {
			display: inline-flex;
			align-items: center;
			gap: 6px;
		}
		.aud-pag-left .btn,
		.aud-pag-right .btn {
			padding: 3px 7px;
			font-size: 11px;
			line-height: 1.4;
		}
		.aud-pag-txt {
			white-space: nowrap;
			color: #475569;
		}
		.aud-pag-input,
		.aud-pag-sel {
			width: 46px;
			height: 26px;
			padding: 2px 4px;
			text-align: center;
			font-size: 11px;
			display: inline-block;
			border-radius: 3px;
			border: 1px solid #cbd5e1;
			color: #0f172a;
		}
		.aud-pag-sel {
			width: auto;
			text-align: left;
		}
		.aud-side-col {
			display: flex;
			flex-direction: column;
			gap: 14px;
			min-width: 0;
		}
		.aud-side-col .aud-panel {
			margin-bottom: 0;
		}
		.aud-kpi-card {
			background: #ffffff;
			border-radius: 4px;
			padding: 12px 16px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.04);
			border: 1px solid #d0dbe5;
			display: flex;
			align-items: center;
			gap: 14px;
			transition: box-shadow 0.15s ease, transform 0.15s ease;
		}
		.aud-kpi-card:hover {
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
			transform: translateY(-1px);
		}
		.aud-kpi-icon {
			width: 42px;
			height: 42px;
			border-radius: 4px;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 18px;
			flex-shrink: 0;
		}
		.aud-kpi-icon.green { background: #dcfce7; color: #15803d; }
		.aud-kpi-icon.yellow { background: #fef9c3; color: #a16207; }
		.aud-kpi-icon.blue { background: #e0e7ff; color: #4338ca; }
		.aud-kpi-icon.purple { background: #f3e8ff; color: #7e22ce; }
		.aud-kpi-icon.red { background: #fee2e2; color: #dc2626; }
		.aud-kpi-icon.gray { background: #f1f5f9; color: #334155; }

		/* Graficos de ranking y comparacion de usuarios (pestana Estadisticas) */
		.aud-chart-panel-body { padding: 12px 14px; }
		.aud-chart-panel-body .chart-empty {
			color: #94a3b8; font-size: 12px; text-align: center; padding: 34px 10px;
		}
		.aud-compare-row {
			display: flex;
			align-items: flex-end;
			gap: 12px;
			flex-wrap: wrap;
		}
		.aud-compare-box {
			flex: 1 1 220px;
			min-width: 200px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 4px;
			padding: 10px 12px;
		}
		.aud-compare-box.a { border-left: 4px solid #4338ca; }
		.aud-compare-box.b { border-left: 4px solid #2563eb; }
		.aud-compare-box label {
			font-size: 11px; font-weight: 700; text-transform: uppercase;
			color: #64748b; margin-bottom: 4px; display: block;
		}
		.aud-compare-vs {
			display: inline-block;
			background: #e2e8f0;
			color: #475569;
			font-size: 11px;
			font-weight: 800;
			padding: 5px 10px;
			border-radius: 12px;
			letter-spacing: 0.5px;
			margin-bottom: 6px;
			flex: 0 0 auto;
		}
		.aud-cmp-kpi-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
			gap: 10px;
			margin-top: 14px;
		}
		.aud-cmp-kpi-card {
			background: #ffffff;
			border: 1px solid #d0dbe5;
			border-radius: 4px;
			padding: 10px 12px;
		}
		.aud-cmp-kpi-lbl {
			font-size: 10px; font-weight: 700; text-transform: uppercase;
			color: #64748b; letter-spacing: 0.3px; margin-bottom: 4px;
		}
		.aud-cmp-kpi-vals { display: flex; align-items: baseline; justify-content: space-between; }
		.aud-cmp-val-a { font-size: 15px; font-weight: 800; color: #4338ca; }
		.aud-cmp-val-b { font-size: 15px; font-weight: 800; color: #2563eb; }
		.aud-cmp-badge {
			font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 3px;
			display: inline-flex; align-items: center; gap: 3px; margin-top: 4px;
		}
		.aud-cmp-badge-a { background: #ede9fe; color: #4338ca; }
		.aud-cmp-badge-b { background: #dbeafe; color: #1d4ed8; }
		.aud-cmp-badge-tie { background: #f1f5f9; color: #64748b; }
		@media (max-width: 480px) {
			.aud-compare-row { flex-direction: column; align-items: stretch; }
			.aud-compare-vs { align-self: center; }
		}

		.aud-kpi-val {
			font-size: 20px;
			font-weight: 800;
			color: #0f172a;
			line-height: 1.1;
		}
		.aud-kpi-lbl {
			font-size: 11px;
			font-weight: 600;
			color: #64748b;
			text-transform: uppercase;
			letter-spacing: 0.4px;
			margin-top: 2px;
		}

		/* Enlaces clicables IP / MAC / huella → modal de detalle */
		.aud-ses-link {
			color: #1d4ed8;
			cursor: pointer;
			text-decoration: none;
			border-bottom: 1px dashed rgba(29, 78, 216, 0.45);
			font-weight: 600;
		}
		.aud-ses-link:hover,
		.aud-ses-link:focus {
			color: #1e3a8a;
			border-bottom-color: #1e3a8a;
			text-decoration: none;
		}
		.aud-ses-link .fa {
			margin-right: 3px;
			font-size: 11px;
			opacity: 0.85;
		}
		.aud-ses-detail-dl {
			margin: 0;
		}
		.aud-ses-detail-dl dt {
			font-size: 10px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: #64748b;
			margin: 10px 0 2px;
		}
		.aud-ses-detail-dl dt:first-child { margin-top: 0; }
		.aud-ses-detail-dl dd {
			margin: 0;
			font-size: 13px;
			color: #0f172a;
			word-break: break-all;
			font-family: Consolas, "Courier New", monospace;
			line-height: 1.4;
		}
		.aud-ses-detail-dl dd.aud-ses-detail-plain {
			font-family: inherit;
			font-size: 13px;
		}
		.aud-ses-detail-hint {
			margin: 12px 0 0;
			font-size: 11px;
			color: #64748b;
			line-height: 1.4;
		}
		.aud-ses-detail-user {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 10px 12px;
			margin: 0 0 12px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 4px;
		}
		.aud-ses-detail-user strong {
			display: block;
			color: #0f172a;
			font-size: 13px;
		}
		.aud-ses-detail-user span {
			font-size: 11px;
			color: #64748b;
		}

		/* Panel de sesiones */
		.aud-panel {
			background: #ffffff;
			border-radius: 4px;
			border: 1px solid #d0dbe5;
			box-shadow: 0 1px 3px rgba(0,0,0,0.04);
			margin-bottom: 14px;
			overflow: hidden;
		}
		.aud-panel-head {
			padding: 10px 14px;
			background: #f8fafc;
			border-bottom: 1px solid #e2e8f0;
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 8px;
		}
		.aud-panel-title {
			margin: 0;
			font-size: 13px;
			font-weight: 700;
			color: #1e3a5f;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		/* Barra de control de periodo: una sola linea (presets + Desde/Hasta pegados) */
		.aud-period-strip {
			background: #fdfefe;
			border-bottom: 1px solid #e2e8f0;
			padding: 8px 14px;
			display: flex;
			flex-direction: row;
			align-items: center;
			flex-wrap: nowrap;
			gap: 8px;
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
			scrollbar-width: thin;
		}
		.aud-period-strip > * {
			flex-shrink: 0;
		}
		.aud-period-left {
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: nowrap;
		}
		.aud-period-left > span,
		.aud-period-left > #audActPeriodoPresets,
		.aud-period-left > #audActCustomBadge {
			flex-shrink: 0;
		}
		.aud-btn-preset {
			border-radius: 3px;
			font-size: 11px;
			padding: 3px 8px;
			font-weight: 600;
			border: 1px solid #cbd5e1;
			background: #ffffff;
			color: #475569;
			cursor: pointer;
			white-space: nowrap;
		}
		.aud-btn-preset.active {
			background: #2563eb;
			color: #ffffff;
			border-color: #1d4ed8;
		}
		.aud-date-group {
			display: inline-flex;
			align-items: stretch;
			flex-wrap: nowrap;
			gap: 0;
		}
		.aud-date-input-wrap {
			position: relative;
			display: inline-flex;
			align-items: stretch;
			flex-shrink: 0;
		}
		.aud-date-input-wrap .aud-date-addon {
			display: inline-flex;
			align-items: center;
			padding: 0 6px;
			font-size: 11px;
			font-weight: 600;
			color: #5b6f88;
			background: #f1f5f9;
			border: 1px solid #cbd5e1;
			border-right: none;
			white-space: nowrap;
		}
		.aud-date-input-wrap input {
			width: 105px;
			font-size: 11px;
			padding: 3px 22px 3px 8px;
			height: 26px;
			border-radius: 0;
			border: 1px solid #cbd5e1;
			background: #ffffff;
			cursor: pointer;
			text-align: center;
		}
		.aud-date-group > .aud-date-input-wrap:first-child .aud-date-addon {
			border-radius: 3px 0 0 3px;
		}
		.aud-date-group > .aud-date-input-wrap:last-child {
			margin-left: -1px;
		}
		.aud-date-group > .aud-date-input-wrap:last-child input {
			border-radius: 0 3px 3px 0;
		}
		.aud-date-input-wrap .cal-icon {
			position: absolute;
			right: 6px;
			top: 6px;
			font-size: 11px;
			color: #94a3b8;
			pointer-events: none;
		}
		.aud-panel-head-filters {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
			gap: 8px;
			align-items: end;
			min-width: 0;
			flex: 1 1 320px;
		}
		.aud-panel-head-filters .form-control {
			font-size: 11px;
			width: 100%;
			min-width: 0;
		}
		@media (max-width: 600px) {
			.aud-panel-head-filters { width: 100%; flex: 1 1 100%; }
		}
		@media (max-width: 480px) {
			.aud-period-strip { gap: 6px; }
			.aud-date-input-wrap input { width: 88px; }
			#audActTabs { display: flex; }
			#audActTabs > li { float: none; flex: 1 1 0%; }
			#audActTabs > li > a { padding: 8px 4px; text-align: center; font-size: 11px; }
		}

		/* Tabla de sesiones */
		.table-sesiones {
			margin-bottom: 0;
			font-size: 12px;
		}
		.table-sesiones th {
			background: #f8fafc;
			color: #475569;
			font-weight: 700;
			border-bottom: 2px solid #e2e8f0 !important;
			padding: 8px 10px;
			font-size: 11px;
			text-transform: uppercase;
		}
		.table-sesiones td {
			vertical-align: middle !important;
			padding: 8px 10px;
			border-top: 1px solid #f1f5f9 !important;
		}
		.table-sesiones tr:hover {
			background-color: #f8fafc;
		}

		.user-avatar-badge {
			width: 32px;
			height: 32px;
			border-radius: 50%;
			background: #e2e8f0;
			color: #334155;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: 12px;
			position: relative;
			flex-shrink: 0;
		}
		.status-dot {
			position: absolute;
			bottom: -1px;
			right: -1px;
			width: 10px;
			height: 10px;
			border-radius: 50%;
			border: 2px solid #ffffff;
		}
		.status-dot.en_linea { background: #22c55e; }
		.status-dot.ausente { background: #eab308; }
		.status-dot.inactivo { background: #94a3b8; }

		.badge-custom {
			font-size: 11px;
			font-weight: 600;
			padding: 2px 7px;
			border-radius: 3px;
			display: inline-flex;
			align-items: center;
			gap: 4px;
		}
		.badge-custom.en_linea { background: #dcfce7; color: #15803d; }
		.badge-custom.ausente { background: #fef9c3; color: #a16207; }
		.badge-custom.inactivo { background: #f1f5f9; color: #475569; }
		.badge-custom.timeout { background: #ffedd5; color: #c2410c; }
		.badge-custom.forzada { background: #fee2e2; color: #b91c1c; }

		.btn-kick {
			background: #fee2e2;
			color: #b91c1c;
			border: 1px solid #fca5a5;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.15s;
		}
		.btn-kick:hover {
			background: #b91c1c;
			color: #ffffff;
			border-color: #991b1b;
		}
		.btn-kick-icon {
			padding: 3px 7px;
			font-size: 12px;
			line-height: 1;
		}

		/* Barra lateral top usuarios */
		.top-user-item {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 6px 0;
			border-bottom: 1px solid #f1f5f9;
			font-size: 12px;
		}
		.top-user-item:last-child {
			border-bottom: none;
		}

		/* Tarjeta informativa compacta (Seguridad e Inactividad): reemplaza
		   parrafos largos por una lista corta y escaneable */
		.aud-info-card {
			padding: 12px 14px;
			background: #eff6ff;
			border-color: #bfdbfe;
		}
		.aud-info-card-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			font-weight: 700;
			color: #1e40af;
			font-size: 12.5px;
			margin-bottom: 8px;
		}
		.aud-info-card-badge {
			font-size: 10px;
		}
		.aud-info-card-list {
			list-style: none;
			margin: 0;
			padding: 0;
		}
		.aud-info-card-list li {
			display: flex;
			align-items: flex-start;
			gap: 6px;
			font-size: 11px;
			color: #3b5c8c;
			line-height: 1.45;
			margin-bottom: 6px;
		}
		.aud-info-card-list li:last-child {
			margin-bottom: 0;
		}
		.aud-info-card-list li i {
			margin-top: 2px;
			color: #2563eb;
			flex-shrink: 0;
		}

		/* Transicion suave al cambiar de pestana */
		.tab-content > .tab-pane.active {
			animation: audFadeIn 0.18s ease;
		}
		@keyframes audFadeIn {
			from { opacity: 0; }
			to { opacity: 1; }
		}

		/* Botones e interacciones */
		.btn, .aud-btn-preset, #btnRecargarActividad {
			transition: background-color 0.12s ease, border-color 0.12s ease, box-shadow 0.12s ease, transform 0.05s ease;
		}
		.btn:active, .aud-btn-preset:active {
			transform: translateY(1px);
		}
		.aud-panel-head .form-control:focus,
		#filtroTexto:focus {
			border-color: #2563eb;
			box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
		}

		@keyframes blink {
			0%, 100% { opacity: 1; }
			50% { opacity: 0.3; }
		}
			/* Regla visual ERP: Si el fondo donde estan los titulos es azul, el titulo debe ser blanco */
		.panel-heading.exa-header, .exa-header {
			background-color: #254463 !important;
			color: #ffffff !important;
		}
		.panel-heading.exa-header .panel-title, .exa-header .panel-title,
		.panel-heading.exa-header h3, .exa-header h3 {
			color: #ffffff !important;
			font-weight: 700 !important;
		}
		.panel-heading.exa-header .panel-title i, .exa-header .panel-title i,
		.panel-heading.exa-header .panel-title span, .exa-header .panel-title span {
			color: #ffffff !important;
		}
		.aud-header-actions {
			display: flex;
			align-items: center;
			justify-content: flex-end;
			flex-wrap: wrap;
			gap: 8px;
		}
		@media (max-width: 480px) {
			.aud-header-actions { justify-content: flex-start; }
			.aud-header-actions #audAutoRefresh { width: 130px !important; }
		}

		/* Ajustes locales: evitar que el CSS compartido rompa esta pantalla */
		.aud-act-page .exa-body {
			display: flex;
			flex-direction: column;
			min-height: 0;
		}
		.aud-act-page .aud-page-hero {
			margin-bottom: 12px;
			padding: 10px 14px;
		}
		.aud-act-page .aud-page-hero-icon {
			width: 38px;
			height: 38px;
			font-size: 15px;
			border-radius: 8px;
		}
		.aud-act-page .aud-page-hero-text h4 {
			font-size: 14px;
		}
		.aud-act-page .aud-ui-tabs {
			display: flex;
			flex-direction: column;
			flex: 1 1 auto;
			min-height: 0;
		}
		.aud-act-page .aud-ui-tabs > .tab-content {
			flex: 1 1 auto;
			min-height: 0;
		}
		.aud-act-page .aud-ui-tabs > .tab-content > .tab-pane.active {
			display: flex;
			flex-direction: column;
			min-height: 0;
		}
		.aud-act-page #tabActividadVivo.active > .aud-main-grid {
			flex: 1 1 auto;
			min-height: 380px;
		}
		.aud-act-page .aud-panel {
			overflow: hidden;
		}
		.aud-act-page .aud-panel-head {
			background: #f8fafc;
		}
		.aud-act-page .aud-header-bar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 10px;
			width: 100%;
		}
		.aud-act-page .aud-header-bar .panel-title {
			margin: 0;
			flex: 1 1 auto;
			min-width: 0;
		}
	</style>
</head>
<body>

<div class="panel panel-default panel-main exa-ui-panel exa-ui-fill-page aud-act-page" style="margin-top: 0;">
	<div class="panel-heading exa-header">
		<div class="aud-header-bar">
			<h3 class="panel-title">
				<i class="fa fa-users"></i> Actividad de usuarios y sesiones
			</h3>
			<div class="aud-header-actions">
				<span id="audLiveIndicator" class="label label-success" style="padding: 4px 8px; font-size: 11px;">
					<i class="fa fa-circle" style="animation: blink 1.5s infinite;"></i> En Vivo
				</span>
				<button type="button" id="btnRecargarActividad" class="btn btn-sm btn-primary" style="font-weight: 600;">
					<i class="fa fa-refresh"></i> Actualizar
				</button>
				<select id="audAutoRefresh" class="form-control input-sm" style="width: 140px; display: inline-block;">
					<option value="15000">Auto: cada 15s</option>
					<option value="30000" selected>Auto: cada 30s</option>
					<option value="60000">Auto: cada 1 min</option>
					<option value="0">Desactivado</option>
				</select>
			</div>
		</div>
	</div>

	<div class="panel-body exa-body">

		<div class="aud-page-hero">
			<div class="aud-page-hero-icon"><i class="fa fa-bolt"></i></div>
			<div class="aud-page-hero-text">
				<h4>Sesiones en tiempo real</h4>
				<p class="aud-page-hero-sub">
					Supervise qui&eacute;n est&aacute; conectado, detecte accesos m&uacute;ltiples y consulte
					estad&iacute;sticas de uso por usuario.
				</p>
			</div>
			<div class="aud-page-hero-tags">
				<span class="aud-page-hero-tag"><i class="fa fa-circle"></i> En vivo</span>
				<span class="aud-page-hero-tag"><i class="fa fa-ban"></i> Cierre forzado</span>
			</div>
		</div>

		<div class="aud-kpi-grid">
			<div class="aud-kpi-card" style="border-left-color: #15803d;">
				<div class="aud-kpi-icon green"><i class="fa fa-plug"></i></div>
				<div>
					<div class="aud-kpi-val text-success" id="kpiEnLinea">0</div>
					<div class="aud-kpi-lbl">Usuarios En L&iacute;nea</div>
				</div>
			</div>
			<div class="aud-kpi-card" style="border-left-color: #a16207;">
				<div class="aud-kpi-icon yellow"><i class="fa fa-clock-o"></i></div>
				<div>
					<div class="aud-kpi-val text-warning" id="kpiAusentes">0</div>
					<div class="aud-kpi-lbl">Usuarios Ausentes</div>
				</div>
			</div>
			<div class="aud-kpi-card" style="border-left-color: #4338ca;">
				<div class="aud-kpi-icon blue"><i class="fa fa-sign-in"></i></div>
				<div>
					<div class="aud-kpi-val text-primary" id="kpiSesionesHoy">0</div>
					<div class="aud-kpi-lbl">Sesiones Hoy</div>
				</div>
			</div>
			<div class="aud-kpi-card" style="border-left-color: #7e22ce;">
				<div class="aud-kpi-icon purple"><i class="fa fa-hourglass-start"></i></div>
				<div>
					<div class="aud-kpi-val text-info" id="kpiPromedioUso">0 min</div>
					<div class="aud-kpi-lbl">Tiempo Promedio Uso</div>
				</div>
			</div>
		</div>

		<?php echo aud_html_banner_desde(aud_fecha_registro_inicio($audEmpCod, $obBD_con1, $obBD_conexion)); ?>

		<div class="aud-ui-tabs">
			<ul class="nav nav-tabs" id="audActTabs" role="tablist">
				<li class="active" role="presentation">
					<a href="#tabActividadVivo" data-toggle="tab" role="tab"><i class="fa fa-bolt"></i> Actividad en Vivo</a>
				</li>
				<li role="presentation">
					<a href="#tabEstSesUsu" data-toggle="tab" role="tab"><i class="fa fa-bar-chart"></i> Estad&iacute;sticas por Usuario</a>
				</li>
			</ul>

			<div class="tab-content">
				<div class="tab-pane active" id="tabActividadVivo" role="tabpanel">

		<div class="aud-main-grid">
			<div class="aud-panel aud-panel-flex">
					<div class="aud-period-strip">
						<span class="aud-toolbar-label">
							<i class="fa fa-calendar text-primary"></i> Per&iacute;odo
						</span>
						<div class="btn-group btn-group-xs aud-period-presets" id="audActPeriodoPresets" role="group">
							<button type="button" class="btn aud-btn-preset" data-preset="ayer">Ayer</button>
							<button type="button" class="btn aud-btn-preset" data-preset="hoy">Hoy</button>
							<button type="button" class="btn aud-btn-preset" data-preset="1semana">1 Semana</button>
							<button type="button" class="btn aud-btn-preset active" data-preset="1mes">1 Mes</button>
							<button type="button" class="btn aud-btn-preset" data-preset="3meses">3 Meses</button>
						</div>
						<span id="audActCustomBadge" class="label label-info" style="display: none; font-size: 10px; padding: 3px 6px;">
							<i class="fa fa-calendar"></i> Personalizado
						</span>
						<div class="aud-toolbar-range aud-date-group">
							<div class="aud-date-input-wrap">
								<span class="aud-date-addon">Desde</span>
								<input type="text" id="fromAct" class="form-control" placeholder="aaaa-mm-dd" autocomplete="off" />
								<i class="fa fa-calendar cal-icon" id="btnFromActCal" title="Desplegar calendario Desde"></i>
							</div>
							<div class="aud-date-input-wrap">
								<span class="aud-date-addon">Hasta</span>
								<input type="text" id="toAct" class="form-control" placeholder="aaaa-mm-dd" autocomplete="off" />
								<i class="fa fa-calendar cal-icon" id="btnToActCal" title="Desplegar calendario Hasta"></i>
							</div>
						</div>
					</div>

					<div id="audAlertasTiempoReal" class="alert alert-warning" style="display: none; margin: 0; border-radius: 0; border-left: 4px solid #d97706; font-size: 12px; padding: 8px 14px;">
						<i class="fa fa-exclamation-triangle"></i> <strong>Detectado acceso m&uacute;ltiple:</strong>
						<span id="audAlertasTiempoRealTxt"></span>
					</div>

					<div class="aud-panel-head">
						<h4 class="aud-panel-title">
							<i class="fa fa-list text-muted"></i> Sesiones Registradas
							<span id="conteoRegistros" class="badge" style="background:#e2e8f0; color:#475569;">0</span>
						</h4>
						<div class="aud-panel-head-filters">
							<select id="filtroEstado" class="form-control input-sm">
								<option value="">Todos los Estados</option>
								<option value="en_linea">En L&iacute;nea (&lt; 5 min)</option>
								<option value="ausente">Ausentes (5 - 15 min)</option>
								<option value="inactivo">Cerradas / Inactivas</option>
							</select>
							<?php if (!empty($roles)) { ?>
							<select id="filtroRol" class="form-control input-sm">
								<option value="">Todos los Roles</option>
								<?php foreach ($roles as $r) { ?>
									<?php $_rolCod = isset($r['Per_Cod']) ? $r['Per_Cod'] : (isset($r['Perfiles_Id']) ? $r['Perfiles_Id'] : 0); ?>
									<?php $_rolDes = isset($r['Per_Des']) ? $r['Per_Des'] : (isset($r['Perfiles_Desc']) ? $r['Perfiles_Desc'] : 'Rol #' . $_rolCod); ?>
									<option value="<?php echo (int)$_rolCod; ?>"><?php echo htmlspecialchars((string)$_rolDes); ?></option>
								<?php } ?>
							</select>
							<?php } ?>
							<input type="text" id="filtroTexto" class="form-control input-sm" placeholder="Filtrar por usuario, IP..." />
						</div>
					</div>

					<div class="table-responsive aud-table-wrap" style="margin-bottom: 0;">
					<table class="table table-sesiones">
							<thead>
								<tr>
									<th>Usuario</th>
									<th>Rol / Perfil</th>
									<th>Direcci&oacute;n IP / Ubicaci&oacute;n</th>
									<th>Navegador / SO</th>
									<th>Dispositivo / MAC
										<i class="fa fa-info-circle text-muted" style="cursor: help;" title="La MAC solo se detecta cuando el usuario ingresa desde la misma red local (LAN) del servidor (limitacion tecnica de ARP, protocolo de capa 2 que no atraviesa routers). En accesos remotos, por VPN o por Internet se muestra en su lugar una huella digital del navegador (icono de huella) como identificador de respaldo del equipo."></i>
									</th>
									<th>&Uacute;ltima Actividad</th>
									<th>Tiempo de Uso</th>
									<th>Estado</th>
									<th>Sesiones Activas</th>
									<?php if ($esAdminSistemas) { ?>
									<th style="text-align: right;">Acci&oacute;n</th>
									<?php } ?>
								</tr>
							</thead>
							<tbody id="tbodyActividad">
								<tr>
									<td colspan="<?php echo $esAdminSistemas ? 10 : 9; ?>" class="text-center text-muted" style="padding: 30px;">
										<i class="fa fa-spinner fa-spin fa-2x"></i><br>Cargando sesiones y actividad...
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<!-- Paginador estilo jqGrid (igual al monitor de actividades) -->
					<div class="aud-grid-pager" id="audPaginador">
						<div class="aud-pag-left">
							<button type="button" class="btn btn-default" id="pagPrimera" title="Primera p&aacute;gina">
								<i class="fa fa-step-backward"></i>
							</button>
							<button type="button" class="btn btn-default" id="pagAnterior" title="P&aacute;gina anterior">
								<i class="fa fa-chevron-left"></i>
							</button>
							<span class="aud-pag-txt">P&aacute;gina</span>
							<input type="text" class="aud-pag-input" id="pagIr" value="1" title="N&uacute;mero de p&aacute;gina (Enter)" />
							<span class="aud-pag-txt">de <span id="pagTotal">1</span></span>
							<button type="button" class="btn btn-default" id="pagSiguiente" title="P&aacute;gina siguiente">
								<i class="fa fa-chevron-right"></i>
							</button>
							<button type="button" class="btn btn-default" id="pagUltima" title="&Uacute;ltima p&aacute;gina">
								<i class="fa fa-step-forward"></i>
							</button>
						</div>
						<div class="aud-pag-right">
							<span class="aud-pag-txt" id="pagInfo">Ver 0 a 0 de 0</span>
							<select class="aud-pag-sel" id="audFilasPagina" title="Registros por p&aacute;gina">
								<option value="25" selected>25 / p&aacute;gina</option>
								<option value="50">50 / p&aacute;gina</option>
								<option value="100">100 / p&aacute;gina</option>
								<option value="250">250 / p&aacute;gina</option>
								<option value="500">500 / p&aacute;gina</option>
								<option value="0">Todos</option>
							</select>
						</div>
					</div>
				</div>

			<!-- Panel Lateral: Top Usuarios -->
			<div class="aud-side-col">
				<div class="aud-panel">
					<div class="aud-panel-head">
						<h4 class="aud-panel-title">
							<i class="fa fa-trophy text-warning"></i> Mayor Tiempo de Uso
						</h4>
					</div>
					<div style="padding: 14px;" id="boxTopUsuarios">
						<p class="text-muted text-center" style="font-size: 12px;">Cargando estad&iacute;sticas...</p>
					</div>
				</div>

				<div class="aud-panel aud-info-card">
					<div class="aud-info-card-head">
						<span><i class="fa fa-shield"></i> Seguridad &amp; Inactividad</span>
						<?php if ($esAdminSistemas) { ?>
						<span class="label label-info aud-info-card-badge"><i class="fa fa-key"></i> Admin</span>
						<?php } ?>
					</div>
					<ul class="aud-info-card-list">
						<li><i class="fa fa-check-circle"></i> El cierre autom&aacute;tico por inactividad est&aacute; <strong>desactivado</strong>; nadie es expulsado por estar inactivo.</li>
						<li><i class="fa fa-fingerprint"></i> Sin MAC (fuera de la LAN) se usa la huella del navegador como respaldo del equipo <span class="glyphicon glyphicon-info-sign" style="font-size:10px;" title="La MAC solo se detecta dentro de la misma red local del servidor (protocolo ARP). En accesos remotos, VPN o Internet se completa con la huella digital del navegador."></span>.</li>
					</ul>
				</div>
			</div>
		</div>

				</div><!-- /#tabActividadVivo -->

				<div class="tab-pane" id="tabEstSesUsu" role="tabpanel">
			<!-- Estadisticas de Sesiones por Usuario (periodo: filtros Desde/Hasta de la pestaña Actividad en Vivo) -->
			<div class="aud-panel" style="margin-top: 0;">
				<div class="aud-panel-head">
					<h4 class="aud-panel-title">
						<i class="fa fa-bar-chart text-primary"></i> Estad&iacute;sticas de Sesiones por Usuario
						<span class="text-muted" style="font-size: 11px; font-weight: 400;">
							&nbsp;&middot;&nbsp;Periodo: <span id="totEstSesUsu">--</span>
						</span>
					</h4>
				</div>
				<div style="padding: 12px 16px;" id="boxEstSesUsu">
					<p class="text-muted text-center" style="font-size: 12px; margin: 0;">
						<i class="fa fa-spinner fa-spin"></i> Cargando estad&iacute;sticas...
					</p>
				</div>
			</div>

			<!-- Rankings graficos (mismo estilo del Panel Estadistico) -->
			<div class="row" id="rowEstUsuCharts" style="display: none;">
				<div class="col-xs-12 col-md-6">
					<div class="aud-panel">
						<div class="aud-panel-head">
							<h4 class="aud-panel-title"><i class="fa fa-clock-o text-primary"></i> Tiempo Conectado por Usuario</h4>
						</div>
						<div class="aud-chart-panel-body"><div id="chartTiempoUsu" style="min-height: 260px;"></div></div>
					</div>
				</div>
				<div class="col-xs-12 col-md-6">
					<div class="aud-panel">
						<div class="aud-panel-head">
							<h4 class="aud-panel-title"><i class="fa fa-sign-in text-primary"></i> Sesiones por Usuario (Top 10)</h4>
						</div>
						<div class="aud-chart-panel-body"><div id="chartSesionesUsu" style="min-height: 260px;"></div></div>
					</div>
				</div>
			</div>

			<!-- Comparar dos usuarios entre si -->
			<div class="aud-panel" id="panelCompararUsu" style="display: none;">
				<div class="aud-panel-head">
					<h4 class="aud-panel-title"><i class="fa fa-exchange text-primary"></i> Comparar Usuarios</h4>
				</div>
				<div class="aud-chart-panel-body">
					<div class="aud-compare-row">
						<div class="aud-compare-box a">
							<label><i class="fa fa-user"></i> Usuario A</label>
							<select id="cmpUsuA" class="form-control input-sm">
								<option value="">Seleccione...</option>
							</select>
						</div>
						<span class="aud-compare-vs"><i class="fa fa-exchange"></i> VS</span>
						<div class="aud-compare-box b">
							<label><i class="fa fa-user"></i> Usuario B</label>
							<select id="cmpUsuB" class="form-control input-sm">
								<option value="">Seleccione...</option>
							</select>
						</div>
					</div>
					<p class="text-muted text-center" id="cmpHint" style="font-size: 12px; margin: 12px 0 0;">
						Seleccione dos usuarios para comparar sus m&eacute;tricas del periodo.
					</p>
					<div id="cmpResultado" style="display: none;">
						<div class="aud-cmp-kpi-grid" id="cmpKpiGrid"></div>
						<div id="chartComparativoUsu" style="min-height: 260px; margin-top: 14px;"></div>
					</div>
				</div>
			</div>

			<!-- Detalle tabular por usuario -->
			<div class="aud-panel">
				<div class="aud-panel-head">
					<h4 class="aud-panel-title"><i class="fa fa-table text-primary"></i> Detalle por Usuario</h4>
				</div>
				<div class="aud-chart-panel-body" id="boxDetalleUsuarios">
					<p class="text-muted text-center" style="font-size: 12px; margin: 0;">Sin datos todav&iacute;a.</p>
				</div>
			</div>
				</div><!-- /#tabEstSesUsu -->

			</div><!-- /.tab-content -->
		</div><!-- /.aud-ui-tabs -->

	</div>
</div>

<!-- Modal Confirmar Cierre Forzado -->
<div id="modalConfirmarExpulsion" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
		<div class="modal-content" style="border-radius: 4px; overflow: hidden; border: 1px solid #cbd5e1;">
			<div class="modal-header" style="background: #ef4444; color: #ffffff; padding: 12px 16px;">
				<h4 class="modal-title" style="margin: 0; font-weight: 700; font-size: 14px;">
					<i class="fa fa-exclamation-triangle"></i> Forzar Cierre de Sesi&oacute;n
				</h4>
			</div>
			<div class="modal-body" style="padding: 16px;">
				<p style="font-size: 13px; color: #1e293b; margin-bottom: 8px;">
					&iquest;Est&aacute; seguro de que desea desconectar inmediatamente al usuario <strong id="kickUsuNom"></strong>?
				</p>
				<p style="font-size: 11px; color: #64748b; margin: 0;">
					El usuario ser&aacute; deslogueado de forma inmediata en su pr&oacute;ximo intento o en su siguiente latido de actividad.
				</p>
				<input type="hidden" id="kickSesCod" value="0" />
			</div>
			<div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 10px 16px;">
				<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancelar</button>
				<button type="button" id="btnEjecutarExpulsion" class="btn btn-danger btn-sm" style="font-weight: 600;">
					<i class="fa fa-ban"></i> Desconectar Usuario
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal detalle IP / MAC / dispositivo -->
<div id="modalDetalleConexion" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
		<div class="modal-content" style="border-radius: 4px; overflow: hidden; border: 1px solid #cbd5e1;">
			<div class="modal-header" style="background: #1e3a5f; color: #ffffff; padding: 12px 16px;">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color:#fff;opacity:0.85;"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="modalDetalleConexionTitulo" style="margin: 0; font-weight: 700; font-size: 14px;">
					<i class="fa fa-info-circle"></i> Detalle de conexi&oacute;n
				</h4>
			</div>
			<div class="modal-body" style="padding: 16px;" id="modalDetalleConexionBody">
				<!-- Contenido inyectado por JS -->
			</div>
			<div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 10px 16px;">
				<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
			</div>
		</div>
	</div>
</div>

<script>
(function (window, $) {
	'use strict';

	var timerAutoRefresh = null;
	var listaActividadCache = [];
	var estUsuFilasCache = [];
	var estUsuCharts = {};
	var esAdmin = <?php echo $esAdminSistemas ? 'true' : 'false'; ?>;
	var sesionActualSesCod = <?php echo isset($_SESSION['Ses_Ses_Cod']) ? (int)$_SESSION['Ses_Ses_Cod'] : 0; ?>;
	var paginaActividad = 1;
	var tamPaginaActividad = 25;

	function escHtml(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	function buscarSesionCache(sesCod) {
		sesCod = parseInt(sesCod, 10) || 0;
		var found = null;
		$.each(listaActividadCache, function (i, it) {
			if (parseInt(it.Ses_Cod, 10) === sesCod) { found = it; return false; }
		});
		return found;
	}

	function mostrarDetalleConexion(sesCod, foco) {
		var it = buscarSesionCache(sesCod);
		if (!it) return;
		foco = foco || 'ip';

		var titulo = foco === 'dispositivo'
			? '<i class="fa fa-desktop"></i> Detalle de dispositivo / MAC'
			: '<i class="fa fa-globe"></i> Detalle de IP / ubicaci&oacute;n';
		$('#modalDetalleConexionTitulo').html(titulo);

		var mac = $.trim(it.Ses_Mac || '');
		var fp = $.trim(it.Ses_Fingerprint || '');
		var dev = $.trim(it.Ses_Dev_Cod || '');
		var ip = $.trim(it.Ses_Ip || '');
		var ubi = $.trim(it.Ses_Ubi || '');
		var nav = $.trim(it.Ses_Nav || '');
		var oauth = $.trim(it.Ses_OAuth_Tok || '');

		var html = '';
		html += '<div class="aud-ses-detail-user">';
		html += '  <div class="user-avatar-badge" style="width:36px;height:36px;font-size:14px;">' + escHtml((it.Prs_Nom && it.Prs_Nom.length) ? it.Prs_Nom.charAt(0).toUpperCase() : 'U') + '</div>';
		html += '  <div><strong>' + escHtml(it.NombreCompleto || 'Usuario') + '</strong>';
		html += '  <span>' + escHtml(it.Usu_Nom || '') + (it.Perfiles_Desc ? ' &middot; ' + escHtml(it.Perfiles_Desc) : '') + '</span></div>';
		html += '</div>';

		html += '<dl class="aud-ses-detail-dl">';
		html += '<dt><i class="fa fa-globe"></i> Direcci&oacute;n IP</dt>';
		html += '<dd>' + escHtml(ip || 'Desconocida') + '</dd>';
		html += '<dt><i class="fa fa-map-marker"></i> Ubicaci&oacute;n</dt>';
		html += '<dd class="aud-ses-detail-plain">' + escHtml(ubi || 'No determinada') + '</dd>';
		html += '<dt><i class="fa fa-laptop"></i> Navegador / SO</dt>';
		html += '<dd class="aud-ses-detail-plain">' + escHtml(nav || 'Desconocido') + '</dd>';
		html += '<dt><i class="fa fa-hdd-o"></i> Direcci&oacute;n MAC</dt>';
		if (mac) {
			html += '<dd>' + escHtml(mac) + '</dd>';
		} else {
			html += '<dd class="aud-ses-detail-plain text-muted">No detectada (t&iacute;pico en acceso remoto, VPN o Internet fuera de la LAN)</dd>';
		}
		html += '<dt><i class="fa fa-fingerprint"></i> Huella digital del navegador</dt>';
		if (fp) {
			html += '<dd>' + escHtml(fp) + '</dd>';
		} else {
			html += '<dd class="aud-ses-detail-plain text-muted">No registrada</dd>';
		}
		if (dev) {
			html += '<dt><i class="fa fa-mobile"></i> C&oacute;digo de dispositivo</dt>';
			html += '<dd>' + escHtml(dev) + '</dd>';
		}
		html += '<dt><i class="fa fa-shield"></i> Token OAuth</dt>';
		html += '<dd class="aud-ses-detail-plain">' + (oauth
			? '<span class="text-success"><i class="fa fa-check-circle"></i> Activo</span>'
			: '<span class="text-muted">Sin token OAuth</span>') + '</dd>';
		if (it.Emp_Nom || it.Suc_Nom) {
			html += '<dt><i class="fa fa-building"></i> Empresa / Sucursal</dt>';
			html += '<dd class="aud-ses-detail-plain">' + escHtml([it.Emp_Nom, it.Suc_Nom].filter(Boolean).join(' · ') || '—') + '</dd>';
		}
		html += '</dl>';

		if (foco === 'dispositivo' && !mac && fp) {
			html += '<p class="aud-ses-detail-hint"><i class="fa fa-info-circle"></i> La MAC solo se obtiene en la misma red local del servidor (ARP). Fuera de la LAN se usa la huella del navegador como identificador de respaldo.</p>';
		} else if (foco === 'ip') {
			html += '<p class="aud-ses-detail-hint"><i class="fa fa-info-circle"></i> La ubicaci&oacute;n es aproximada a partir de la IP. En redes privadas se indica LAN; la geo por IP p&uacute;blica depende de la configuraci&oacute;n del entorno.</p>';
		}

		$('#modalDetalleConexionBody').html(html);
		$('#modalDetalleConexion').modal('show');
	}

	window.mostrarDetalleConexion = mostrarDetalleConexion;

	function fmtYmd(d) {
		var y = d.getFullYear();
		var m = ('0' + (d.getMonth() + 1)).slice(-2);
		var day = ('0' + d.getDate()).slice(-2);
		return y + '-' + m + '-' + day;
	}

	function calcularRangoPreset(preset) {
		var hoy = new Date();
		var y = hoy.getFullYear(), m = hoy.getMonth(), d = hoy.getDate();
		var dDesde, dHasta;

		switch (preset) {
			case 'hoy':
				dDesde = new Date(y, m, d);
				dHasta = new Date(y, m, d);
				break;
			case 'ayer':
				dDesde = new Date(y, m, d - 1);
				dHasta = new Date(y, m, d - 1);
				break;
			case '1semana':
				dDesde = new Date(y, m, d - 7);
				dHasta = new Date(y, m, d);
				break;
			case '15dias':
				dDesde = new Date(y, m, d - 15);
				dHasta = new Date(y, m, d);
				break;
			case '1mes':
				dDesde = new Date(y, m, d - 30);
				dHasta = new Date(y, m, d);
				break;
			case '3meses':
				dDesde = new Date(y, m, d - 90);
				dHasta = new Date(y, m, d);
				break;
			default:
				return null;
		}
		return {
			from: fmtYmd(dDesde),
			to: fmtYmd(dHasta)
		};
	}

	function sincronizarPresetActivo(vFrom, vTo) {
		var presets = ['ayer', 'hoy', '1semana', '1mes', '3meses'];
		var coincidencia = null;
		for (var i = 0; i < presets.length; i++) {
			var r = calcularRangoPreset(presets[i]);
			if (r && r.from === vFrom && r.to === vTo) {
				coincidencia = presets[i];
				break;
			}
		}
		$('.aud-btn-preset').removeClass('active');
		if (coincidencia) {
			$('.aud-btn-preset[data-preset="' + coincidencia + '"]').addClass('active');
			$('#audActCustomBadge').hide();
		} else {
			$('#audActCustomBadge').show();
		}
	}

	function initCalendariosActividad() {
		var $from = $('#fromAct');
		var $to = $('#toAct');
		if (!$from.length || !$.fn.datepicker) return;

		if ($.datepicker) {
			$.datepicker.regional['es'] = {
				closeText: 'Cerrar',
				prevText: '&#x3C;Ant',
				nextText: 'Sig&#x3E;',
				currentText: 'Hoy',
				monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
				monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
				dayNames: ['Domingo','Lunes','Martes','Mi\u00e9rcoles','Jueves','Viernes','S\u00e1bado'],
				dayNamesShort: ['Dom','Lun','Mar','Mi\u00e9','Jue','Vie','S\u00e1b'],
				dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','S\u00e1'],
				weekHeader: 'Sm',
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: ''
			};
			$.datepicker.setDefaults($.datepicker.regional['es']);
		}

		var calBaseOpts = {
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			showButtonPanel: true,
			firstDay: 1,
			monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
			monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
			dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','S\u00e1']
		};

		$from.datepicker($.extend({}, calBaseOpts, {
			onClose: function (sel) {
				if (sel) {
					try { $to.datepicker('option', 'minDate', sel); } catch(e) {}
				}
			},
			onSelect: function () {
				sincronizarPresetActivo($from.val(), $to.val());
				cargarDatosActividad();
			}
		}));

		$to.datepicker($.extend({}, calBaseOpts, {
			onClose: function (sel) {
				if (sel) {
					try { $from.datepicker('option', 'maxDate', sel); } catch(e) {}
				}
			},
			onSelect: function () {
				sincronizarPresetActivo($from.val(), $to.val());
				cargarDatosActividad();
			}
		}));

		$('#btnFromActCal').off('click.audAct').on('click.audAct', function (e) {
			e.preventDefault();
			$from.focus().datepicker('show');
		});
		$('#btnToActCal').off('click.audAct').on('click.audAct', function (e) {
			e.preventDefault();
			$to.focus().datepicker('show');
		});

		$('.aud-btn-preset').off('click.audActPreset').on('click.audActPreset', function (e) {
			e.preventDefault();
			var preset = $(this).attr('data-preset');
			var r = calcularRangoPreset(preset);
			if (r) {
				$('.aud-btn-preset').removeClass('active');
				$(this).addClass('active');
				$('#audActCustomBadge').hide();

				$from.val(r.from);
				$to.val(r.to);
				try {
					$from.datepicker('setDate', r.from);
					$to.datepicker('setDate', r.to);
					$to.datepicker('option', 'minDate', r.from);
					$from.datepicker('option', 'maxDate', r.to);
				} catch (eSet) {}

				cargarDatosActividad();
			}
		});

		$from.add($to).on('input change', function () {
			sincronizarPresetActivo($from.val(), $to.val());
		});
	}

	function cargarDatosActividad() {
		var fFrom = $('#fromAct').val() || '';
		var fTo = $('#toAct').val() || '';

		$.ajax({
			url: '../LOGICA/aud_log_actividad_sesion.php',
			type: 'GET',
			dataType: 'json',
			data: {
				action: 'consultar_actividad',
				from: fFrom,
				to: fTo,
				rol: $('#filtroRol').val() || 0,
				estado: $('#filtroEstado').val() || ''
			},
			success: function (res) {
				if (!res || !res.success) {
					return;
				}
				var d = res.data;

				$('#kpiEnLinea').text(d.kpis.en_linea || 0);
				$('#kpiAusentes').text(d.kpis.ausentes || 0);
				$('#kpiSesionesHoy').text(d.kpis.total_hoy || 0);
				$('#kpiPromedioUso').text((d.kpis.promedio_minutos || 0) + ' min');

				listaActividadCache = d.sesiones || [];
				paginaActividad = 1;
				renderizarTabla(listaActividadCache);
				renderizarTopUsuarios(d.top_usuarios || []);
				renderizarValidaciones(d.validaciones || {});
				cargarEstadisticaUsuarios();
			}
		});
	}

	/* ---- Estadisticas de sesiones por usuario (periodo) ---- */
	function cargarEstadisticaUsuarios() {
		var $box = $('#boxEstSesUsu');
		if (!$box.length) {
			return;
		}
		var fFrom = $('#fromAct').val() || '';
		var fTo = $('#toAct').val() || '';
		var periodoTxt = '--';
		if (fFrom && fTo && fFrom === fTo) {
			periodoTxt = fFrom;
		} else if (fFrom && fTo) {
			periodoTxt = fFrom + ' a ' + fTo;
		} else if (fFrom) {
			periodoTxt = 'desde ' + fFrom;
		} else if (fTo) {
			periodoTxt = 'hasta ' + fTo;
		}
		$('#totEstSesUsu').text(periodoTxt);
		$box.html('<p class="text-muted text-center" style="font-size: 12px; margin: 0;"><i class="fa fa-spinner fa-spin"></i> Cargando estad&iacute;sticas...</p>');
		$('#rowEstUsuCharts, #panelCompararUsu').hide();
		$.getJSON('../LOGICA/aud_log_actividad_sesion.php', {
			action: 'estadistica_usuarios',
			from: fFrom,
			to: fTo
		}, function (res) {
			if (!res || !res.success) {
				$box.html('<p class="text-muted text-center" style="font-size: 12px; margin: 0;">Sin datos disponibles.</p>');
				estUsuFilasCache = [];
				renderTablaDetalleUsuarios([]);
				return;
			}
			var d = res.data || {};
			var t = d.totales || {};
			var filas = d.filas || [];
			estUsuFilasCache = filas;
			if (filas.length === 0) {
				$box.html('<p class="text-muted text-center" style="font-size: 12px; margin: 0;">No hay sesiones en el periodo seleccionado.</p>');
				renderTablaDetalleUsuarios([]);
				return;
			}
			var cards = '';
			cards += '<div class="aud-kpi-card" style="border-left: 3px solid #4338ca; min-width: 120px;">' +
				'<div class="aud-kpi-icon blue"><i class="fa fa-sign-in"></i></div>' +
				'<div><div class="aud-kpi-val text-primary">' + (t.iniciadas || 0) + '</div><div class="aud-kpi-lbl">Sesiones iniciadas</div></div></div>';
			cards += '<div class="aud-kpi-card" style="border-left: 3px solid #15803d; min-width: 120px;">' +
				'<div class="aud-kpi-icon green"><i class="fa fa-sign-out"></i></div>' +
				'<div><div class="aud-kpi-val text-success">' + (t.cerradas || 0) + '</div><div class="aud-kpi-lbl">Cerradas</div></div></div>';
			cards += '<div class="aud-kpi-card" style="border-left: 3px solid #a16207; min-width: 120px;">' +
				'<div class="aud-kpi-icon yellow"><i class="fa fa-clock-o"></i></div>' +
				'<div><div class="aud-kpi-val text-warning">' + (t.por_inactividad || 0) + '</div><div class="aud-kpi-lbl">Por inactividad</div></div></div>';
			cards += '<div class="aud-kpi-card" style="border-left: 3px solid #dc2626; min-width: 120px;">' +
				'<div class="aud-kpi-icon red"><i class="fa fa-ban"></i></div>' +
				'<div><div class="aud-kpi-val text-danger">' + (t.forzadas || 0) + '</div><div class="aud-kpi-lbl">Forzadas</div></div></div>';
			cards += '<div class="aud-kpi-card" style="border-left: 3px solid #7e22ce; min-width: 120px;">' +
				'<div class="aud-kpi-icon purple"><i class="fa fa-hourglass-half"></i></div>' +
				'<div><div class="aud-kpi-val text-info">' + (Math.round(t.promedio_min * 10) / 10 || 0) + '</div><div class="aud-kpi-lbl">Min. promedio</div></div></div>';
			cards += '<div class="aud-kpi-card" style="border-left: 3px solid #334155; min-width: 120px;">' +
				'<div class="aud-kpi-icon gray"><i class="fa fa-users"></i></div>' +
				'<div><div class="aud-kpi-val">' + (t.usuarios || 0) + '</div><div class="aud-kpi-lbl">Usuarios</div></div></div>';
			$box.html('<div class="aud-kpi-grid" style="flex-wrap: wrap;">' + cards + '</div>');

			renderRankingUsuarios(filas);
			poblarSelectsComparar(filas);
			renderTablaDetalleUsuarios(filas);
			$('#rowEstUsuCharts, #panelCompararUsu').show();
		}).fail(function () {
			$box.html('<p class="text-muted text-center" style="font-size: 12px; margin: 0;">No se pudo cargar la estad&iacute;stica.</p>');
			estUsuFilasCache = [];
			renderTablaDetalleUsuarios([]);
		});
	}

	/* ---- Graficos de ranking (tiempo conectado / sesiones) por usuario ---- */
	function estUsuChart(id, cfg) {
		if (estUsuCharts[id] && typeof estUsuCharts[id].destroy === 'function') {
			try { estUsuCharts[id].destroy(); } catch (e) {}
		}
		delete estUsuCharts[id];
		if (!$('#' + id).length) return;
		var el = document.getElementById(id);
		if (!window.ApexCharts) {
			el.innerHTML = '<div class="chart-empty">Librer&iacute;a de gr&aacute;ficos no disponible.</div>';
			return;
		}
		try {
			estUsuCharts[id] = new ApexCharts(el, cfg);
			estUsuCharts[id].render();
		} catch (e) {
			el.innerHTML = '<div class="chart-empty">No hay datos suficientes para el gr&aacute;fico.</div>';
		}
	}

	function estUsuNombreCorto(r) {
		var n = r.Usuario_Completo || r.Usu_Nom || ('Usuario #' + r.Usu_Cod);
		return n.length > 22 ? n.substring(0, 20) + '\u2026' : n;
	}

	function renderRankingUsuarios(filas) {
		var top = filas.slice().sort(function (a, b) {
			return (parseInt(b.Total_Min, 10) || 0) - (parseInt(a.Total_Min, 10) || 0);
		}).slice(0, 10);

		estUsuChart('chartTiempoUsu', {
			chart: { type: 'bar', toolbar: { show: false }, height: 260 },
			series: [{ name: 'Minutos conectado', data: top.map(function (r) { return parseInt(r.Total_Min, 10) || 0; }) }],
			xaxis: { categories: top.map(estUsuNombreCorto), labels: { style: { fontSize: '10px' } } },
			plotOptions: { bar: { horizontal: true, barHeight: '60%' } },
			colors: ['#4338ca'],
			dataLabels: { enabled: false }
		});

		var topSes = filas.slice().sort(function (a, b) {
			return (parseInt(b.Iniciadas, 10) || 0) - (parseInt(a.Iniciadas, 10) || 0);
		}).slice(0, 10);

		estUsuChart('chartSesionesUsu', {
			chart: { type: 'bar', stacked: true, toolbar: { show: false }, height: 260 },
			series: [
				{ name: 'Cerradas', data: topSes.map(function (r) { return parseInt(r.Cerradas, 10) || 0; }) },
				{ name: 'Por inactividad', data: topSes.map(function (r) { return parseInt(r.Por_Inactividad, 10) || 0; }) },
				{ name: 'Forzadas', data: topSes.map(function (r) { return parseInt(r.Forzadas, 10) || 0; }) }
			],
			xaxis: { categories: topSes.map(estUsuNombreCorto), labels: { rotate: -45, style: { fontSize: '9px' } } },
			plotOptions: { bar: { columnWidth: '55%' } },
			colors: ['#15803d', '#a16207', '#dc2626'],
			legend: { fontSize: '11px', position: 'top' },
			dataLabels: { enabled: false }
		});
	}

	/* ---- Comparar dos usuarios entre si ---- */
	function poblarSelectsComparar(filas) {
		var prevA = $('#cmpUsuA').val();
		var prevB = $('#cmpUsuB').val();
		var opts = '<option value="">Seleccione...</option>';
		filas.forEach(function (r) {
			opts += '<option value="' + r.Usu_Cod + '">' + (r.Usuario_Completo || r.Usu_Nom) + '</option>';
		});
		$('#cmpUsuA, #cmpUsuB').html(opts);
		if (prevA && filas.some(function (r) { return String(r.Usu_Cod) === String(prevA); })) {
			$('#cmpUsuA').val(prevA);
		}
		if (prevB && filas.some(function (r) { return String(r.Usu_Cod) === String(prevB); })) {
			$('#cmpUsuB').val(prevB);
		}
		renderizarComparacionUsuarios();
	}

	function estUsuBadge(valA, valB, invertido) {
		if (valA === valB) return '<span class="aud-cmp-badge aud-cmp-badge-tie"><i class="fa fa-minus"></i> Igual</span>';
		var aGana = invertido ? (valA < valB) : (valA > valB);
		return aGana
			? '<span class="aud-cmp-badge aud-cmp-badge-a"><i class="fa fa-caret-left"></i> Usuario A</span>'
			: '<span class="aud-cmp-badge aud-cmp-badge-b"><i class="fa fa-caret-right"></i> Usuario B</span>';
	}

	function renderizarComparacionUsuarios() {
		var codA = $('#cmpUsuA').val();
		var codB = $('#cmpUsuB').val();
		if (!codA || !codB || codA === codB) {
			$('#cmpResultado').hide();
			$('#cmpHint').text(
				(!codA || !codB)
					? 'Seleccione dos usuarios para comparar sus m\u00e9tricas del periodo.'
					: 'Seleccione dos usuarios diferentes para compararlos.'
			).show();
			return;
		}
		var rA = estUsuFilasCache.filter(function (r) { return String(r.Usu_Cod) === String(codA); })[0];
		var rB = estUsuFilasCache.filter(function (r) { return String(r.Usu_Cod) === String(codB); })[0];
		if (!rA || !rB) return;

		$('#cmpHint').hide();
		$('#cmpResultado').show();

		var metricas = [
			{ lbl: 'Sesiones iniciadas', a: parseInt(rA.Iniciadas, 10) || 0, b: parseInt(rB.Iniciadas, 10) || 0, invertido: false },
			{ lbl: 'Cerradas normalmente', a: parseInt(rA.Cerradas, 10) || 0, b: parseInt(rB.Cerradas, 10) || 0, invertido: false },
			{ lbl: 'Por inactividad', a: parseInt(rA.Por_Inactividad, 10) || 0, b: parseInt(rB.Por_Inactividad, 10) || 0, invertido: true },
			{ lbl: 'Forzadas por admin', a: parseInt(rA.Forzadas, 10) || 0, b: parseInt(rB.Forzadas, 10) || 0, invertido: true },
			{ lbl: 'Promedio de uso (min)', a: Math.round((parseFloat(rA.Promedio_Min) || 0) * 10) / 10, b: Math.round((parseFloat(rB.Promedio_Min) || 0) * 10) / 10, invertido: false },
			{ lbl: 'Tiempo total (min)', a: parseInt(rA.Total_Min, 10) || 0, b: parseInt(rB.Total_Min, 10) || 0, invertido: false }
		];

		var html = '';
		metricas.forEach(function (m) {
			html += '<div class="aud-cmp-kpi-card">' +
				'<div class="aud-cmp-kpi-lbl">' + m.lbl + '</div>' +
				'<div class="aud-cmp-kpi-vals"><span class="aud-cmp-val-a">' + m.a + '</span><span class="text-muted" style="font-size:10px;">vs</span><span class="aud-cmp-val-b">' + m.b + '</span></div>' +
				estUsuBadge(m.a, m.b, m.invertido) +
				'</div>';
		});
		$('#cmpKpiGrid').html(html);

		estUsuChart('chartComparativoUsu', {
			chart: { type: 'bar', toolbar: { show: false }, height: 260 },
			series: [
				{ name: estUsuNombreCorto(rA), data: metricas.map(function (m) { return m.a; }) },
				{ name: estUsuNombreCorto(rB), data: metricas.map(function (m) { return m.b; }) }
			],
			xaxis: { categories: metricas.map(function (m) { return m.lbl; }), labels: { style: { fontSize: '10px' } } },
			plotOptions: { bar: { horizontal: true, barHeight: '65%' } },
			colors: ['#4338ca', '#2563eb'],
			dataLabels: { enabled: false },
			legend: { fontSize: '11px', position: 'top' }
		});
	}

	$('#cmpUsuA, #cmpUsuB').on('change', renderizarComparacionUsuarios);

	/* ---- Tabla de detalle por usuario ---- */
	function renderTablaDetalleUsuarios(filas) {
		var $box = $('#boxDetalleUsuarios');
		if (!$box.length) return;
		if (!filas || filas.length === 0) {
			$box.html('<p class="text-muted text-center" style="font-size: 12px; margin: 0;">No hay sesiones en el periodo seleccionado.</p>');
			return;
		}
		var table = '<div class="table-responsive" style="max-height: 420px; overflow: auto;">';
		table += '<table class="table table-bordered table-condensed table-striped" style="margin: 0; font-size: 12px;">';
		table += '<thead><tr><th>Usuario</th><th>Iniciadas</th><th>Cerradas</th><th>Por inactividad</th><th>Forzadas</th><th>Promedio de uso</th><th>Tiempo total</th><th>&Uacute;ltima actividad</th></tr></thead><tbody>';
		filas.forEach(function (r) {
			var ult = r.Ultima_Actividad || '';
			if (ult !== '') {
				ult = ult.substring(0, 10) + ' ' + ult.substring(11, 16);
			}
			table += '<tr>' +
				'<td><strong>' + (r.Usuario_Completo || 'Usuario') + '</strong><br><small class="text-muted">' + (r.Usu_Nom || '') + '</small></td>' +
				'<td class="text-center"><strong>' + (r.Iniciadas || 0) + '</strong></td>' +
				'<td class="text-center text-success">' + (r.Cerradas || 0) + '</td>' +
				'<td class="text-center text-warning">' + (r.Por_Inactividad || 0) + '</td>' +
				'<td class="text-center text-danger">' + (r.Forzadas || 0) + '</td>' +
				'<td class="text-center">' + (Math.round((parseFloat(r.Promedio_Min) || 0) * 10) / 10) + ' min</td>' +
				'<td class="text-center">' + (r.TiempoFormateado || '0m') + '</td>' +
				'<td class="text-center text-muted"><small>' + (ult || '&mdash;') + '</small></td>' +
				'</tr>';
		});
		table += '</tbody></table></div>';
		$box.html(table);
	}

	function fmtRelativo(min) {
		if (min === 0) return 'Hace un instante';
		if (min <= 0) return 'Hace un instante';
		if (min >= 1440) {
			var dias = Math.floor(min / 1440);
			return 'Hace ' + dias + (dias === 1 ? ' d&iacute;a' : ' d&iacute;as');
		}
		if (min >= 60) {
			return 'Hace ' + Math.floor(min / 60) + ' h';
		}
		return 'Hace ' + min + ' min';
	}

	function renderizarValidaciones(v) {
		var $b = $('#audAlertasTiempoReal');
		var multi = (v && v.usuarios_con_multiples_sesiones) ? v.usuarios_con_multiples_sesiones : [];
		if (multi.length === 0) {
			$b.hide().find('#audAlertasTiempoRealTxt').text('');
			return;
		}
		var nombres = multi.map(function (m) {
			return m.nombre + ' (<strong>' + m.total + ' sesiones</strong>)';
		}).join(', ');
		$('#audAlertasTiempoRealTxt').html(nombres + '. Verifique credenciales compartidas o duplicadas en tiempo real.');
		$b.show();
	}

	function renderizarTabla(sesiones) {
		var q = $.trim($('#filtroTexto').val() || '').toLowerCase();
		var filtradas = sesiones;

		if (q !== '') {
			filtradas = sesiones.filter(function (s) {
				var texto = (s.NombreCompleto + ' ' + (s.Usu_Nom || '') + ' ' + (s.Ses_Ip || '') + ' ' + (s.Perfiles_Desc || '') + ' ' + (s.Ses_Mac || '') + ' ' + (s.Ses_Dev_Cod || '') + ' ' + (s.Ses_Fingerprint || '')).toLowerCase();
				return texto.indexOf(q) !== -1;
			});
		}

		$('#conteoRegistros').text(filtradas.length);

		if (filtradas.length === 0) {
			paginaActividad = 1;
			$('#tbodyActividad').html('<tr><td colspan="' + (esAdmin ? 10 : 9) + '" class="text-center text-muted" style="padding: 24px;">No se encontraron sesiones que coincidan con los filtros.</td></tr>');
			renderizarPaginador(0);
			return;
		}

		var pageSize = tamPaginaActividad > 0 ? tamPaginaActividad : filtradas.length;
		var totalPages = Math.ceil(filtradas.length / pageSize);
		if (paginaActividad > totalPages) paginaActividad = totalPages;
		if (paginaActividad < 1) paginaActividad = 1;
		var inicio = (paginaActividad - 1) * pageSize;
		var visibles = filtradas.slice(inicio, inicio + pageSize);

		var html = '';
		visibles.forEach(function (it) {
			var inicial = (it.Prs_Nom && it.Prs_Nom.length) ? it.Prs_Nom.charAt(0).toUpperCase() : 'U';
			var semaforo = it.Semaforo;
			var badgeClase = semaforo;
			var esMia = !!it.es_mi_sesion;
			var sesAct = parseInt(it.sesiones_activas || 0, 10);

			var relativo = fmtRelativo(it.MinutosInactivo);
			var horaActividad = it.Ses_Ult_Act ? it.Ses_Ult_Act.substring(11, 16) : '--:--';

			var ipVal = it.Ses_Ip || 'Desconocida';
			var ipUbi = '<a href="javascript:void(0);" class="aud-ses-link" title="Ver detalle completo de IP y ubicaci&oacute;n" onclick="mostrarDetalleConexion(' + it.Ses_Cod + ', \'ip\')">'
				+ '<i class="fa fa-globe"></i>' + escHtml(ipVal) + '</a>';
			if (it.Ses_Ubi) {
				ipUbi += '<br><small class="text-muted"><i class="fa fa-map-marker"></i> ' + escHtml(it.Ses_Ubi) + '</small>';
			}

			var navOs = (it.Ses_Nav || 'Desconocido');
			var dispHtml;
			if (it.Ses_Mac) {
				dispHtml = '  <td style="white-space: nowrap;"><a href="javascript:void(0);" class="aud-ses-link" title="Ver detalle completo de dispositivo / MAC" onclick="mostrarDetalleConexion(' + it.Ses_Cod + ', \'dispositivo\')">'
					+ '<i class="fa fa-hdd-o"></i>' + escHtml(it.Ses_Mac) + '</a>';
			} else if (it.Ses_Fingerprint) {
				dispHtml = '  <td style="white-space: nowrap;"><a href="javascript:void(0);" class="aud-ses-link" title="Ver huella digital completa del navegador" onclick="mostrarDetalleConexion(' + it.Ses_Cod + ', \'dispositivo\')">'
					+ '<i class="fa fa-fingerprint"></i> ' + escHtml(String(it.Ses_Fingerprint).substring(0, 12)) + '&hellip;</a>';
			} else if (it.Ses_Dev_Cod) {
				dispHtml = '  <td style="white-space: nowrap;"><a href="javascript:void(0);" class="aud-ses-link" title="Ver detalle completo del dispositivo" onclick="mostrarDetalleConexion(' + it.Ses_Cod + ', \'dispositivo\')">'
					+ '<i class="fa fa-mobile"></i>' + escHtml(it.Ses_Dev_Cod) + '</a>';
			} else {
				dispHtml = '  <td style="white-space: nowrap;"><small style="color: #475569;">&mdash;</small>';
			}
			if (it.Ses_OAuth_Tok) {
				dispHtml += '<br><span class="text-success" style="font-size: 10px;"><i class="fa fa-shield"></i> Token OAuth activo</span>';
			}
			dispHtml += '</td>';

			html += '<tr>';
			html += '  <td>';
			html += '    <div style="display: flex; align-items: center; gap: 10px;">';
			html += '      <div class="user-avatar-badge">' + inicial + '<span class="status-dot ' + semaforo + '"></span></div>';
			html += '      <div style="min-width: 0;">';
			html += '        <strong style="color: #0f172a;">' + it.NombreCompleto + '</strong>';
			html += '        <div style="font-size: 11px; color: #64748b;">' + (it.Usu_Nom || '') + '</div>';
			html += '      </div>';
			html += '    </div>';
			html += '  </td>';
			html += '  <td><span style="font-size: 12px; color: #334155;">' + (it.Perfiles_Desc || 'Sin perfil') + '</span></td>';
			html += '  <td>' + ipUbi + '</td>';
			html += '  <td><small style="color: #475569;">' + navOs + '</small></td>';
			html += dispHtml;
			html += '  <td><strong style="color: #334155;">' + relativo + '</strong><br><small class="text-muted">' + horaActividad + '</small></td>';
			html += '  <td><span class="badge" style="background: #e0e7ff; color: #3730a3; font-weight: 700;">' + (it.TiempoFormateado || '0m') + '</span></td>';
			html += '  <td><span class="badge-custom ' + badgeClase + '"><i class="fa fa-circle" style="font-size: 8px;"></i> ' + it.BadgeTexto + '</span></td>';
			html += '  <td style="text-align: center; white-space: nowrap;">';
			if (sesAct > 1) {
				html += '    <span class="badge-custom ausente" title="Este usuario mantiene ' + sesAct + ' sesiones activas simult&aacute;neas en tiempo real (posible credencial compartida)."><i class="fa fa-exclamation-triangle"></i> ' + sesAct + ' activas</span>';
			} else if (esMia) {
				html += '    <span class="badge-custom en_linea" title="Esta es la sesi&oacute;n que usted est&aacute; usando ahora."><i class="fa fa-user"></i> Tu sesi&oacute;n</span>';
			} else if (sesAct === 1) {
				html += '    <span class="badge-custom inactivo" title="Sesiones activas en este momento."><i class="fa fa-check-circle"></i> 1</span>';
			} else {
				html += '    <span class="text-muted" style="font-size: 11px;">&mdash;</span>';
			}
			html += '  </td>';

			if (esAdmin) {
				html += '  <td style="text-align: right; white-space: nowrap;">';
				if (esMia) {
					html += '    <span class="text-muted muted-icon" title="No puede desconectar su propia sesi&oacute;n desde el monitor."><i class="fa fa-lock"></i></span>';
				} else if (semaforo === 'en_linea' || semaforo === 'ausente') {
					html += '    <button type="button" class="btn-kick btn-kick-icon" title="Desconectar a ' + (it.NombreCompleto || '').replace(/"/g, '&quot;') + '" onclick="confirmarExpulsion(' + it.Ses_Cod + ', \'' + (it.NombreCompleto || '').replace(/'/g, "\\'") + '\')"><i class="fa fa-ban"></i></button>';
				} else {
					html += '    <span class="text-muted">&mdash;</span>';
				}
				html += '  </td>';
			}

			html += '</tr>';
		});

		$('#tbodyActividad').html(html);
		renderizarPaginador(filtradas.length);
	}

	function renderizarPaginador(total) {
		var pageSize = tamPaginaActividad > 0 ? tamPaginaActividad : (total || 1);
		var totalPages = total > 0 ? Math.ceil(total / pageSize) : 1;
		if (paginaActividad > totalPages) paginaActividad = totalPages;
		if (paginaActividad < 1) paginaActividad = 1;
		var desde = total === 0 ? 0 : ((paginaActividad - 1) * pageSize) + 1;
		var hasta = Math.min(total, paginaActividad * pageSize);

		$('#pagTotal').text(totalPages);
		$('#pagInfo').text('Ver ' + desde + ' a ' + hasta + ' de ' + total);
		$('#pagIr').val(paginaActividad).prop('disabled', totalPages <= 1);
		$('#pagPrimera').prop('disabled', paginaActividad <= 1 || totalPages <= 1);
		$('#pagAnterior').prop('disabled', paginaActividad <= 1 || totalPages <= 1);
		$('#pagSiguiente').prop('disabled', paginaActividad >= totalPages || totalPages <= 1);
		$('#pagUltima').prop('disabled', paginaActividad >= totalPages || totalPages <= 1);
	}

	function irPaginaActividad(pag) {
		var total = listaActividadCache.length || 0;
		var pageSize = tamPaginaActividad > 0 ? tamPaginaActividad : (total || 1);
		var totalPages = total > 0 ? Math.ceil(total / pageSize) : 1;
		pag = parseInt(pag, 10) || 1;
		if (pag < 1) pag = 1;
		if (pag > totalPages) pag = totalPages;
		if (pag !== paginaActividad) {
			paginaActividad = pag;
			renderizarTabla(listaActividadCache);
		}
	}

	function renderizarTopUsuarios(top) {
		if (!top || top.length === 0) {
			$('#boxTopUsuarios').html('<p class="text-muted text-center" style="font-size: 12px;">Sin registros en el per\u00edodo.</p>');
			return;
		}

		var html = '';
		top.forEach(function (u, idx) {
			var nombre = (u.Prs_Nom ? u.Prs_Nom + ' ' + (u.Prs_Ape || '') : u.Usu_Nom) || 'Usuario #' + u.Usu_Cod;
			var medalla = (idx === 0) ? '1. ' : ((idx === 1) ? '2. ' : ((idx === 2) ? '3. ' : (idx + 1) + '. '));

			html += '<div class="top-user-item">';
			html += '  <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 170px;">';
			html += '    <span style="font-weight: 700; color: #1e293b;">' + medalla + nombre + '</span>';
			html += '    <div style="font-size: 11px; color: #64748b;">' + (u.Total_Sesiones || 1) + ' conexiones</div>';
			html += '  </div>';
			html += '  <span class="badge" style="background: #f1f5f9; color: #0f172a; font-weight: 700;">' + u.TiempoFormateado + '</span>';
			html += '</div>';
		});

		$('#boxTopUsuarios').html(html);
	}

	window.confirmarExpulsion = function (sesCod, usuNom, esMia) {
		if (esMia || parseInt(sesCod, 10) === sesionActualSesCod) {
			alert('No puede desconectar su propia sesi\u00f3n desde el monitor.');
			return;
		}
		$('#kickSesCod').val(sesCod);
		$('#kickUsuNom').text(usuNom);
		$('#modalConfirmarExpulsion').modal('show');
	};

	$('#btnEjecutarExpulsion').on('click', function () {
		var sesCod = $('#kickSesCod').val();
		if (!sesCod || sesCod === '0') return;
		if (parseInt(sesCod, 10) === sesionActualSesCod) {
			$('#modalConfirmarExpulsion').modal('hide');
			alert('No puede desconectar su propia sesi\u00f3n desde el monitor.');
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Desconectando...');

		$.ajax({
			url: '../LOGICA/aud_log_actividad_sesion.php',
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'cerrar_forzada',
				ses_cod: sesCod
			},
			success: function (res) {
				$('#modalConfirmarExpulsion').modal('hide');
				$btn.prop('disabled', false).html('<i class="fa fa-ban"></i> Desconectar Usuario');
				if (res && res.success) {
					cargarDatosActividad();
				} else {
					alert('No se pudo desconectar al usuario: ' + (res.error || 'Error desconocido'));
				}
			},
			error: function () {
				$('#modalConfirmarExpulsion').modal('hide');
				$btn.prop('disabled', false).html('<i class="fa fa-ban"></i> Desconectar Usuario');
				alert('Error al comunicar con el servidor.');
			}
		});
	});

	// Filtros interactivos
	$('#filtroEstado, #filtroRol').on('change', cargarDatosActividad);
	$('#filtroTexto').on('keyup', function () {
		renderizarTabla(listaActividadCache);
	});

	// Paginador (estilo monitor de actividades)
	$('#pagPrimera').on('click', function () { irPaginaActividad(1); });
	$('#pagAnterior').on('click', function () { irPaginaActividad(paginaActividad - 1); });
	$('#pagSiguiente').on('click', function () { irPaginaActividad(paginaActividad + 1); });
	$('#pagUltima').on('click', function () {
		var total = listaActividadCache.length || 0;
		var pageSize = tamPaginaActividad > 0 ? tamPaginaActividad : (total || 1);
		irPaginaActividad(total > 0 ? Math.ceil(total / pageSize) : 1);
	});
	$('#pagIr').on('keydown', function (e) {
		if (e.keyCode === 13) {
			irPaginaActividad($(this).val());
			$(this).blur();
		}
	}).on('change', function () { irPaginaActividad($(this).val()); });
	$('#audFilasPagina').on('change', function () {
		var v = $(this).val();
		tamPaginaActividad = (v === '0') ? 0 : (parseInt(v, 10) || 25);
		paginaActividad = 1;
		renderizarTabla(listaActividadCache);
	});

	$('#btnRecargarActividad').on('click', cargarDatosActividad);

	// Manejo de autorefresco
	function configurarAutoRefresh() {
		if (timerAutoRefresh) clearInterval(timerAutoRefresh);
		var ms = parseInt($('#audAutoRefresh').val(), 10);
		if (ms > 0) {
			timerAutoRefresh = setInterval(cargarDatosActividad, ms);
		}
	}
	$('#audAutoRefresh').on('change', configurarAutoRefresh);

	// Los graficos ApexCharts se inicializan aunque la pestana este oculta
	// (display:none), por lo que deben re-dimensionarse cuando se muestran.
	$('#audActTabs a[href="#tabEstSesUsu"]').on('shown.bs.tab', function () {
		setTimeout(function () {
			for (var k in estUsuCharts) {
				if (estUsuCharts[k] && typeof estUsuCharts[k].resize === 'function') {
					try { estUsuCharts[k].resize(); } catch (e) {}
				}
			}
		}, 30);
	});

	$(document).ready(function () {
		var initR = calcularRangoPreset('1mes');
		if (initR) {
			$('#fromAct').val(initR.from);
			$('#toAct').val(initR.to);
		}
		initCalendariosActividad();
		cargarDatosActividad();
		configurarAutoRefresh();
	});

})(window, window.jQuery);
</script>
</body>
</html>
