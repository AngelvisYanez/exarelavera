<?php
/**
 * Dashboard Estadistico Comparativo de Auditoria con Emision de Reportes PDF
 * y Envio Directo a Correo y WhatsApp (API ERP + Web).
 *
 * @package auditoria.FRONT
 */

if (session_id() === '' && !headers_sent()) {
	@session_start();
}

require_once dirname(__FILE__) . '/../../administrador/LOGICA/seguridad.php';

$audEmpCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 1;
$audEmpNom = isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : 'Empresa Principal';
$audUsuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;
$audUsuNom = isset($_SESSION['Ses_Usu_Nom']) ? $_SESSION['Ses_Usu_Nom'] : 'Administrador';

require_once dirname(__FILE__) . '/../LOGICA/aud_log_acceso_directorio.php';
aud_acceso_directorio_gate($audEmpCod);

// Valores por defecto: Ultimo mes vs mes previo
$defaultPaIni = date('Y-m-d 00:00:00', strtotime('-60 days'));
$defaultPaFin = date('Y-m-d 23:59:59', strtotime('-31 days'));
$defaultPbIni = date('Y-m-d 00:00:00', strtotime('-30 days'));
$defaultPbFin = date('Y-m-d 23:59:59');
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<title>Tablero Analitico Comparativo - ExaContable</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

	<!-- Estilos Oficiales ExaContable ERP -->
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
	<?php require_once("../../mascaras/model4/estilos/estilos.php"); ?>
	<script type="text/javascript" src="../../framework/jquery/apexcharts/apexcharts.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../RECURSOS/aud_monitoreo_ui_1.0.css?v=20260924_desk19c" />

	<style>
		/* Estilos armonizados con el tema visual de ExaContable */
		body {
			background-color: #f1f5f9;
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

		/* Card de Control de Periodos */
		.period-control-card {
			background: #ffffff;
			border-radius: 4px;
			padding: 12px 16px;
			border: 1px solid #d0dbe5;
			margin-bottom: 12px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.03);
			overflow: visible;
		}
		.preset-btn {
			font-size: 11px;
			padding: 3px 9px;
			font-weight: 600;
			border-radius: 3px;
			margin-right: 3px;
			margin-bottom: 3px;
			transition: all 0.15s;
		}

		/* Barra de filtros en una sola linea (como Monitoreo / Panel estadistico) */
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
		.aud-toolbar-left > * {
			flex-shrink: 0;
		}
		.aud-toolbar-label {
			font-weight: 700;
			font-size: 11px;
			letter-spacing: 0.03em;
			text-transform: uppercase;
			color: #5b6f88;
			white-space: nowrap;
		}
		.aud-toolbar-dates {
			display: inline-flex;
			align-items: center;
			flex-wrap: nowrap;
			gap: 6px;
		}
		.aud-cmp-inline {
			display: inline-flex;
			align-items: center;
			flex-wrap: nowrap;
			gap: 4px;
		}
		.aud-cmp-inline .aud-cmp-tag {
			display: inline-block;
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			padding: 3px 7px;
			border-radius: 3px;
			white-space: nowrap;
			line-height: 1.2;
		}
		.aud-cmp-inline.a .aud-cmp-tag {
			background: #64748b;
			color: #fff;
		}
		.aud-cmp-inline.b .aud-cmp-tag {
			background: #2563eb;
			color: #fff;
		}
		.aud-cmp-inline .aud-toolbar-range .form-control {
			width: 96px;
			text-align: center;
			font-size: 11px;
			height: 28px;
			padding: 2px 6px;
		}
		.aud-cmp-inline .aud-toolbar-range .input-group-addon {
			padding: 2px 6px;
			font-size: 11px;
			font-weight: 600;
		}
		.dash-vs-pill {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: #e2e8f0;
			color: #475569;
			font-size: 10px;
			font-weight: 800;
			padding: 4px 8px;
			border-radius: 12px;
			letter-spacing: 0.04em;
			white-space: nowrap;
			flex-shrink: 0;
		}
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
				flex-wrap: nowrap;
				overflow-x: auto;
			}
			.aud-toolbar-right {
				width: auto;
				justify-content: flex-end;
				flex-shrink: 0;
			}
			.aud-toolbar-right .dropdown { float: none; }
		}
		@media (max-width: 480px) {
			.aud-acciones-menu { max-width: 90vw; min-width: 0; }
			.aud-cmp-inline .aud-toolbar-range .form-control { width: 84px; }
		}

		/* Conservado por si se usa en otros bloques del tablero */
		.period-box-a {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-left: 4px solid #64748b;
			border-radius: 4px;
			padding: 10px 12px;
		}
		.period-box-b {
			background: #f0f7ff;
			border: 1px solid #dbeafe;
			border-left: 4px solid #2563eb;
			border-radius: 4px;
			padding: 10px 12px;
		}

		#ui-datepicker-div {
			z-index: 99999 !important;
			box-shadow: 0 4px 16px rgba(0,0,0,0.18);
			border: 1px solid #cbd5e1;
			border-radius: 4px;
			padding: 6px;
			background: #ffffff;
		}

		/* KPIs comparativos (scoped: no heredar .kpi-card horizontal del CSS global) */
		.aud-cmp-kpi-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
			gap: 8px;
			margin-bottom: 10px;
		}
		.aud-cmp-kpi {
			display: flex;
			flex-direction: column;
			align-items: stretch;
			gap: 0;
			background: #ffffff;
			border-radius: 8px;
			padding: 7px 10px 8px;
			box-shadow: 0 1px 2px rgba(0,0,0,0.04);
			border: 1px solid #d0dbe5;
			border-top: 2px solid #2563eb;
			border-left-width: 1px;
			min-width: 0;
			min-height: 68px;
			transition: box-shadow 0.15s ease;
		}
		.aud-cmp-kpi:hover {
			transform: none;
			box-shadow: 0 2px 8px rgba(37, 68, 99, 0.08);
		}
		.aud-cmp-kpi .kpi-title {
			display: flex;
			align-items: center;
			gap: 4px;
			font-size: 9px;
			font-weight: 700;
			text-transform: uppercase;
			color: #64748b;
			margin: 0 0 3px;
			letter-spacing: 0.03em;
			line-height: 1.2;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.aud-cmp-kpi .kpi-title .fa {
			flex-shrink: 0;
			opacity: 0.85;
		}
		.aud-cmp-kpi .kpi-values-row {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 6px;
			margin-bottom: 4px;
		}
		.aud-cmp-kpi .kpi-main-val {
			font-size: 16px;
			font-weight: 800;
			color: #0f172a;
			line-height: 1.05;
			letter-spacing: -0.01em;
			min-width: 0;
		}
		.aud-cmp-kpi .kpi-badge {
			font-size: 10px;
			font-weight: 700;
			padding: 2px 5px;
			border-radius: 3px;
			display: inline-flex;
			align-items: center;
			gap: 2px;
			flex-shrink: 0;
			white-space: nowrap;
			line-height: 1.2;
		}
		.aud-cmp-kpi .kpi-compare {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 4px 8px;
			padding-top: 6px;
			border-top: 1px solid #f1f5f9;
			font-size: 11px;
			line-height: 1.3;
			color: #64748b;
		}
		.aud-cmp-kpi .kpi-compare > div {
			min-width: 0;
		}
		.aud-cmp-kpi .kpi-compare .kpi-tag {
			display: inline-block;
			font-size: 9px;
			font-weight: 800;
			letter-spacing: 0.04em;
			padding: 1px 5px;
			border-radius: 2px;
			margin-right: 4px;
			vertical-align: middle;
		}
		.aud-cmp-kpi .kpi-compare .kpi-tag.a {
			background: #e2e8f0;
			color: #475569;
		}
		.aud-cmp-kpi .kpi-compare .kpi-tag.b {
			background: #dbeafe;
			color: #1d4ed8;
		}
		.aud-cmp-kpi .kpi-compare strong {
			color: #334155;
			font-weight: 700;
		}
		.aud-cmp-kpi .kpi-compare .kpi-rate {
			display: block;
			margin-top: 1px;
			font-size: 10px;
			color: #94a3b8;
		}
		.aud-cmp-kpi .badge-up { background: #dcfce7; color: #15803d; }
		.aud-cmp-kpi .badge-down { background: #fee2e2; color: #b91c1c; }
		.aud-cmp-kpi .badge-neutral { background: #f1f5f9; color: #64748b; }
		@media (max-width: 480px) {
			.aud-cmp-kpi-grid {
				grid-template-columns: 1fr;
			}
		}

		/* Widgets personalizables (mismo patron que Panel estadistico) */
		#cmpWidgetGrid {
			display: block;
			margin-left: -6px;
			margin-right: -6px;
			overflow: hidden;
		}
		#cmpWidgetGrid:after { content: ''; display: table; clear: both; }
		.dash-widget {
			float: left;
			box-sizing: border-box;
			padding: 0 6px 12px;
		}
		 .dash-widget.w-full { width: 100% !important; }
		 .dash-widget.w-12 { width: 50% !important; }
		@media (max-width: 767px) {
			 .dash-widget.w-12 { width: 100% !important; }
		}
		.dash-widget-inner {
			background: #fff;
			border: 1px solid #e2e8f0;
			border-radius: 6px;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
			overflow: hidden;
			height: 100%;
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
		.dash-widget-head .dash-title { font-size: 12px; font-weight: 700; color: #334155; margin: 0; }
		.dash-widget-head .dash-actions { display: flex; align-items: center; gap: 10px; }
		.dash-widget-head .dash-actions .dash-hint { cursor: grab; color: #cbd5e1; font-size: 11px; }
		.dash-widget-head .dash-actions .dash-hide,
		.dash-widget-head .dash-actions .dash-size { cursor: pointer; color: #94a3b8; font-size: 12px; }
		.dash-widget-head .dash-actions .dash-hide:hover { color: #ef4444; }
		.dash-widget-head .dash-actions .dash-size:hover { color: #2563eb; }
		.dash-widget-body { padding: 10px 12px; overflow-x: auto; }
		.dash-widget-placeholder {
			float: left; box-sizing: border-box;
			background: #eef2f7; border: 1px dashed #94a3b8; border-radius: 6px;
			min-height: 90px; margin-bottom: 12px; padding: 0 6px;
			visibility: visible !important;
		}
		 .dash-widget-placeholder.w-12 { width: 50% !important; }
		 .dash-widget-placeholder.w-full { width: 100% !important; }
		@media (max-width: 767px) { .dash-widget-placeholder.w-12 { width: 100%; } }
		.dash-widget.ui-sortable-helper { z-index: 10050 !important; }
		body.aud-cmp-sorting { cursor: move !important; user-select: none; }

		#cmpKpiGrid {
			display: block;
			margin: 0 -6px 10px;
			overflow: hidden;
		}
		#cmpKpiGrid:after { content: ''; display: table; clear: both; }
		.cmp-kpi-wrap {
			float: left;
			box-sizing: border-box;
			padding: 0 6px 10px;
			width: 20%;
			min-width: 160px;
		}
		@media (max-width: 991px) { .cmp-kpi-wrap { width: 33.333%; } }
		@media (max-width: 767px) { .cmp-kpi-wrap { width: 50%; } }
		@media (max-width: 480px) { .cmp-kpi-wrap { width: 100%; } }
		.cmp-kpi-wrap .aud-cmp-kpi { margin: 0; min-height: 68px; position: relative; cursor: move; }
		.cmp-kpi-wrap .kpi-hide {
			position: absolute; top: 8px; right: 8px;
			color: #cbd5e1; cursor: pointer; font-size: 11px;
		}
		.cmp-kpi-wrap .kpi-hide:hover { color: #ef4444; }
		.cmp-kpi-placeholder {
			float: left; box-sizing: border-box;
			width: 20%; min-width: 160px; min-height: 98px;
			margin-bottom: 10px; padding: 0 6px;
			background: #eef2f7; border: 1px dashed #94a3b8; border-radius: 4px;
		}

		#cmpKpisSorter, #cmpWidgetsSorter {
			display: block; overflow: hidden; padding: 2px; margin-bottom: 4px; min-height: 40px;
		}
		#cmpKpisSorter:after, #cmpWidgetsSorter:after { content: ''; display: table; clear: both; }
		.aud-sbox {
			float: left; display: flex; align-items: center; gap: 7px;
			box-sizing: border-box; width: calc(50% - 6px); margin: 0 6px 6px 0;
			min-width: 160px; padding: 8px 10px;
			background: #fff; border: 1px solid #dbe3ee; border-radius: 5px;
			cursor: move; font-size: 12px; color: #334155;
		}
		.aud-sbox:hover { border-color: #2563eb; }
		.aud-sbox-off { opacity: 0.5; background: #f8fafc; border-style: dashed; }
		.aud-sbox-off .kbox-txt { text-decoration: line-through; }
		.kbox-eye { color: #94a3b8; cursor: pointer; font-size: 12px; flex-shrink: 0; }
		.aud-sbox-off .kbox-eye { color: #ef4444; }
		.kbox-ico { color: #64748b; font-size: 12px; flex-shrink: 0; }
		.kbox-txt { flex: 1 1 auto; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.aud-sbox-placeholder {
			float: left; box-sizing: border-box; width: calc(50% - 6px);
			margin: 0 6px 6px 0; min-width: 160px; min-height: 38px;
			border: 1px dashed #94a3b8; border-radius: 5px; background: #eef2f7;
			visibility: visible !important;
		}
		.aud-modal-ayuda { font-size: 11px; color: #64748b; margin-bottom: 8px; }
		.aud-modal-sec {
			font-size: 12px; font-weight: 700; color: #334155;
			margin: 4px 0 6px; text-transform: uppercase; letter-spacing: 0.03em;
		}
		#modalWidgetsCmp .modal-body { max-height: 62vh; overflow-y: auto; }

		.chart-panel {
			background: #ffffff;
			border-radius: 4px;
			padding: 12px 14px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.04);
			border: 1px solid #d0dbe5;
			margin-bottom: 12px;
		}
		/* chart-panel ya no se usa como contenedor externo; se mantiene por compat */
		.chart-panel h4 {
			margin: 0 0 10px 0;
			font-size: 13px;
			font-weight: 700;
			color: #1e3a5f;
			display: flex;
			align-items: center;
			justify-content: space-between;
			border-bottom: 1px solid #f1f5f9;
			padding-bottom: 8px;
		}

		/* Banner de Observaciones */
		.obs-card {
			background: #f0f7ff;
			border: 1px solid #bfdbfe;
			border-left: 4px solid #2563eb;
			border-radius: 4px;
			padding: 10px 14px;
			margin-bottom: 12px;
		}
		.obs-card h4 {
			margin: 0 0 6px 0;
			font-size: 13px;
			font-weight: 700;
			color: #1e40af;
			display: flex;
			align-items: center;
			gap: 6px;
		}
		.obs-list {
			margin: 0;
			padding-left: 18px;
			font-size: 12px;
			color: #1e3a8a;
			line-height: 1.5;
		}

		/* Tablas resumen */
		.table-comp {
			margin-bottom: 0;
			font-size: 12px;
		}
		.table-comp th {
			background: #f8fafc;
			color: #475569;
			font-weight: 700;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.3px;
			border-bottom: 2px solid #e2e8f0 !important;
			padding: 6px 10px;
		}
		.table-comp td {
			vertical-align: middle !important;
			border-top: 1px solid #f1f5f9 !important;
			padding: 6px 10px;
		}
		.badge-eve-ins { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; padding: 2px 6px; border-radius: 3px; font-weight: 600; font-size: 11px; }
		.badge-eve-upd { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; padding: 2px 6px; border-radius: 3px; font-weight: 600; font-size: 11px; }
		.badge-eve-del { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 2px 6px; border-radius: 3px; font-weight: 600; font-size: 11px; }

		/* Modales ERP */
		.modal-header.exa-modal-header {
			background-color: #254463 !important;
			color: #ffffff !important;
			padding: 12px 16px;
		}
		.modal-header.exa-modal-header .modal-title {
			color: #ffffff !important;
			font-size: 14px;
			font-weight: 700;
		}
		.modal-header.exa-modal-header .close {
			color: #ffffff !important;
			opacity: 0.85;
		}
	</style>
</head>
<body>

<div class="panel panel-default panel-main exa-ui-panel exa-ui-fill-page" style="margin-top: 0;">
	<div class="panel-heading exa-header">
		<h3 class="panel-title" style="margin:0;">
			<i class="fa fa-exchange"></i> Tablero anal&iacute;tico comparativo
		</h3>
	</div>
	<div class="panel-body exa-body" style="padding: 10px 14px;">

		<div class="aud-page-hero m4-hero">
			<div class="aud-page-hero-icon m4-hero-icon"><i class="fa fa-exchange"></i></div>
			<div class="aud-page-hero-text m4-hero-text">
				<h4 class="m4-hero-title">Comparativa de periodos</h4>
				<p class="aud-page-hero-sub m4-hero-sub">
					Compare dos rangos de fechas (A vs B), revise variaciones por m&oacute;dulo, hora y usuario,
					y emita el informe por PDF, correo o WhatsApp.
				</p>
			</div>
			<div class="aud-page-hero-tags m4-hero-tags">
				<span class="aud-page-hero-tag m4-hero-tag"><i class="fa fa-calendar"></i> Periodo A / B</span>
				<span class="aud-page-hero-tag m4-hero-tag"><i class="fa fa-file-pdf-o"></i> PDF / WA</span>
			</div>
		</div>

		<!-- Barra de herramientas: filtros juntos + Acciones a la derecha -->
		<div class="period-control-card m4-filters m4-card" style="padding: 0;">
			<div class="aud-toolbar m4-toolbar">

				<div class="aud-toolbar-left">
					<span class="aud-toolbar-label">
						<i class="fa fa-calendar-check-o text-primary"></i> Rango
					</span>
					<span id="dashPresetCustomBadge" class="label label-info m4-badge m4-badge-info" style="display:none; font-size:10px; padding: 2px 6px;">Personalizado</span>
					<div class="btn-group btn-group-xs aud-period-presets" id="dashPeriodoPresets">
						<button type="button" class="btn aud-btn-preset" data-preset="ayer" title="Ayer">Ayer</button>
						<button type="button" class="btn aud-btn-preset" data-preset="hoy" title="Hoy">Hoy</button>
						<button type="button" class="btn aud-btn-preset" data-preset="1semana" title="1 Semana">1 Semana</button>
						<button type="button" class="btn aud-btn-preset active" data-preset="1mes" title="1 Mes">1 Mes</button>
						<button type="button" class="btn aud-btn-preset" data-preset="3meses" title="3 Meses">3 Meses</button>
					</div>

					<div class="aud-toolbar-dates">
						<div class="aud-cmp-inline a" title="Periodo A (Base historica)">
							<span class="aud-cmp-tag">A</span>
							<div class="aud-toolbar-range">
								<div class="input-group input-group-sm">
									<span class="input-group-addon">Desde</span>
									<input type="text" id="pa_ini" class="form-control text-center" value="<?php echo substr($defaultPaIni, 0, 10); ?>" readonly style="background:#fff; cursor:pointer;" placeholder="AAAA-MM-DD" />
									<span class="input-group-addon" style="cursor:pointer;" onclick="$('#pa_ini').focus().datepicker('show');"><i class="fa fa-calendar text-muted"></i></span>
								</div>
								<div class="input-group input-group-sm">
									<span class="input-group-addon">Hasta</span>
									<input type="text" id="pa_fin" class="form-control text-center" value="<?php echo substr($defaultPaFin, 0, 10); ?>" readonly style="background:#fff; cursor:pointer;" placeholder="AAAA-MM-DD" />
									<span class="input-group-addon" style="cursor:pointer;" onclick="$('#pa_fin').focus().datepicker('show');"><i class="fa fa-calendar text-muted"></i></span>
								</div>
							</div>
						</div>

						<span class="dash-vs-pill"><i class="fa fa-exchange"></i> VS</span>

						<div class="aud-cmp-inline b" title="Periodo B (Evaluado / Actual)">
							<span class="aud-cmp-tag">B</span>
							<div class="aud-toolbar-range">
								<div class="input-group input-group-sm">
									<span class="input-group-addon">Desde</span>
									<input type="text" id="pb_ini" class="form-control text-center" value="<?php echo substr($defaultPbIni, 0, 10); ?>" readonly style="background:#fff; cursor:pointer;" placeholder="AAAA-MM-DD" />
									<span class="input-group-addon" style="cursor:pointer;" onclick="$('#pb_ini').focus().datepicker('show');"><i class="fa fa-calendar text-primary"></i></span>
								</div>
								<div class="input-group input-group-sm">
									<span class="input-group-addon">Hasta</span>
									<input type="text" id="pb_fin" class="form-control text-center" value="<?php echo substr($defaultPbFin, 0, 10); ?>" readonly style="background:#fff; cursor:pointer;" placeholder="AAAA-MM-DD" />
									<span class="input-group-addon" style="cursor:pointer;" onclick="$('#pb_fin').focus().datepicker('show');"><i class="fa fa-calendar text-primary"></i></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="aud-toolbar-right">
					<div class="dropdown">
						<button type="button" class="btn btn-primary btn-sm dropdown-toggle m4-btn m4-btn-primary" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Acciones">
							<i class="fa fa-sliders"></i> Acciones <span class="caret"></span>
						</button>
						<ul class="dropdown-menu dropdown-menu-right aud-acciones-menu">
							<li><a href="javascript:void(0);" id="btnRecargarDash" title="Actualizar metricas"><i class="fa fa-refresh"></i> Actualizar Datos</a></li>
							<li><a href="javascript:void(0);" id="btnCfgWidgetsCmp" title="Mostrar u ocultar widgets y personalizar"><i class="fa fa-th-large"></i> Personalizar</a></li>
							<li><a href="javascript:void(0);" id="btnResetWidgetsCmp" title="Restablecer orden y visibilidad"><i class="fa fa-undo"></i> Restablecer widgets</a></li>
							<li class="divider"></li>
							<li><a href="javascript:void(0);" id="btnDescargarPdf" title="Generar reporte formal PDF"><i class="fa fa-file-pdf-o text-danger"></i> Exportar PDF</a></li>
							<li><a href="javascript:void(0);" id="btnModalCorreo" title="Enviar reporte por email"><i class="fa fa-envelope-o text-info"></i> Enviar Correo</a></li>
							<li><a href="javascript:void(0);" id="btnModalWhatsApp" title="Compartir informe por WhatsApp"><i class="fa fa-whatsapp text-success"></i> WhatsApp</a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<!-- Banner de Observaciones y Alertas Inteligentes -->
		<div class="obs-card" id="boxObservaciones">
			<h4><i class="fa fa-shield"></i> Diagn&oacute;stico Automatizado y Observaciones de Auditor&iacute;a</h4>
			<ul class="obs-list" id="listaObservaciones">
				<li><i class="fa fa-spinner fa-spin"></i> Cargando diagn&oacute;stico comparativo...</li>
			</ul>
		</div>

		<!-- KPIs Comparativos (personalizables) -->
		<div id="cmpKpiGrid">
			<div class="text-center text-muted" style="padding: 20px 0; clear:both;">
				<i class="fa fa-spinner fa-spin fa-2x"></i>
				<div style="margin-top: 6px; font-size: 12px;">Cargando indicadores comparativos...</div>
			</div>
		</div>
		<div class="dash-kpis-foot" id="cmpKpiFoot" style="font-size:11px;color:#64748b;margin:0 0 12px;clear:both;">&nbsp;</div>

		<!-- Widgets del tablero comparativo -->
		<div id="cmpWidgetGrid">

			<div class="dash-widget w-full" data-widget="tendencia">
				<div class="dash-widget-inner">
					<div class="dash-widget-head">
						<h5 class="dash-title"><i class="fa fa-area-chart"></i> Tendencia diaria (A vs B)</h5>
						<div class="dash-actions">
							<i class="fa fa-arrows dash-hint" title="Arrastrar"></i>
							<i class="fa fa-expand dash-size" title="Tamano"></i>
							<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
						</div>
					</div>
					<div class="dash-widget-body"><div id="chartTendencia" style="min-height: 260px;"></div></div>
				</div>
			</div>

			<div class="dash-widget w-12" data-widget="modulos">
				<div class="dash-widget-inner">
					<div class="dash-widget-head ac-purple">
						<h5 class="dash-title"><i class="fa fa-cubes"></i> Actividad por modulo</h5>
						<div class="dash-actions">
							<i class="fa fa-arrows dash-hint" title="Arrastrar"></i>
							<i class="fa fa-expand dash-size" title="Tamano"></i>
							<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
						</div>
					</div>
					<div class="dash-widget-body"><div id="chartModulos" style="min-height: 260px;"></div></div>
				</div>
			</div>

			<div class="dash-widget w-12" data-widget="eventos">
				<div class="dash-widget-inner">
					<div class="dash-widget-head ac-green">
						<h5 class="dash-title"><i class="fa fa-bar-chart"></i> Operaciones (Ingresar / Actualizar / Eliminar)</h5>
						<div class="dash-actions">
							<i class="fa fa-arrows dash-hint" title="Arrastrar"></i>
							<i class="fa fa-expand dash-size" title="Tamano"></i>
							<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
						</div>
					</div>
					<div class="dash-widget-body"><div id="chartEventos" style="min-height: 260px;"></div></div>
				</div>
			</div>

			<div class="dash-widget w-12" data-widget="horarios">
				<div class="dash-widget-inner">
					<div class="dash-widget-head ac-amber">
						<h5 class="dash-title"><i class="fa fa-clock-o"></i> Franja horaria (00-23)</h5>
						<div class="dash-actions">
							<i class="fa fa-arrows dash-hint" title="Arrastrar"></i>
							<i class="fa fa-expand dash-size" title="Tamano"></i>
							<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
						</div>
					</div>
					<div class="dash-widget-body"><div id="chartHorarios" style="min-height: 250px;"></div></div>
				</div>
			</div>

			<div class="dash-widget w-12" data-widget="sesiones">
				<div class="dash-widget-inner">
					<div class="dash-widget-head ac-red">
						<h5 class="dash-title"><i class="fa fa-user-secret"></i> Sesiones y seguridad</h5>
						<div class="dash-actions">
							<i class="fa fa-arrows dash-hint" title="Arrastrar"></i>
							<i class="fa fa-expand dash-size" title="Tamano"></i>
							<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
						</div>
					</div>
					<div class="dash-widget-body"><div id="chartSesiones" style="min-height: 250px;"></div></div>
				</div>
			</div>

			<div class="dash-widget w-12" data-widget="usuarios">
				<div class="dash-widget-inner">
					<div class="dash-widget-head ac-teal">
						<h5 class="dash-title"><i class="fa fa-users"></i> Top usuarios (A vs B)</h5>
						<div class="dash-actions">
							<i class="fa fa-arrows dash-hint" title="Arrastrar"></i>
							<i class="fa fa-expand dash-size" title="Tamano"></i>
							<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
						</div>
					</div>
					<div class="dash-widget-body"><div id="chartUsuarios" style="min-height: 260px;"></div></div>
				</div>
			</div>

			<div class="dash-widget w-12" data-widget="plantas">
				<div class="dash-widget-inner">
					<div class="dash-widget-head ac-amber">
						<h5 class="dash-title"><i class="fa fa-industry"></i> Plantas (A vs B)</h5>
						<div class="dash-actions">
							<i class="fa fa-arrows dash-hint" title="Arrastrar"></i>
							<i class="fa fa-expand dash-size" title="Tamano"></i>
							<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
						</div>
					</div>
					<div class="dash-widget-body"><div id="chartPlantas" style="min-height: 260px;"></div></div>
				</div>
			</div>

			<div class="dash-widget w-12" data-widget="variacion">
				<div class="dash-widget-inner">
					<div class="dash-widget-head ac-pink">
						<h5 class="dash-title"><i class="fa fa-percent"></i> Variacion % por modulo</h5>
						<div class="dash-actions">
							<i class="fa fa-arrows dash-hint" title="Arrastrar"></i>
							<i class="fa fa-expand dash-size" title="Tamano"></i>
							<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
						</div>
					</div>
					<div class="dash-widget-body"><div id="chartVariacion" style="min-height: 260px;"></div></div>
				</div>
			</div>

			<div class="dash-widget w-12" data-widget="mixOps">
				<div class="dash-widget-inner">
					<div class="dash-widget-head ac-green">
						<h5 class="dash-title"><i class="fa fa-pie-chart"></i> Mix de operaciones A / B</h5>
						<div class="dash-actions">
							<i class="fa fa-arrows dash-hint" title="Arrastrar"></i>
							<i class="fa fa-expand dash-size" title="Tamano"></i>
							<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
						</div>
					</div>
					<div class="dash-widget-body">
						<div class="row">
							<div class="col-xs-6"><div id="chartMixA" style="min-height: 220px;"></div></div>
							<div class="col-xs-6"><div id="chartMixB" style="min-height: 220px;"></div></div>
						</div>
					</div>
				</div>
			</div>

			<div class="dash-widget w-12" data-widget="tablaMod">
				<div class="dash-widget-inner">
					<div class="dash-widget-head">
						<h5 class="dash-title"><i class="fa fa-table"></i> Detalle por modulo</h5>
						<div class="dash-actions">
							<i class="fa fa-arrows dash-hint" title="Arrastrar"></i>
							<i class="fa fa-expand dash-size" title="Tamano"></i>
							<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
						</div>
					</div>
					<div class="dash-widget-body">
						<div class="table-responsive">
							<table class="table table-hover table-comp">
								<thead>
									<tr>
										<th>Modulo</th>
										<th style="text-align: right;">Base (A)</th>
										<th style="text-align: right;">Comp (B)</th>
										<th style="text-align: right;">Variacion</th>
									</tr>
								</thead>
								<tbody id="tbodyModulos">
									<tr><td colspan="4" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando datos...</td></tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

			<div class="dash-widget w-12" data-widget="tablaUsu">
				<div class="dash-widget-inner">
					<div class="dash-widget-head ac-teal">
						<h5 class="dash-title"><i class="fa fa-list"></i> Usuarios mas activos (Periodo B)</h5>
						<div class="dash-actions">
							<i class="fa fa-arrows dash-hint" title="Arrastrar"></i>
							<i class="fa fa-expand dash-size" title="Tamano"></i>
							<i class="fa fa-eye-slash dash-hide" title="Ocultar widget"></i>
						</div>
					</div>
					<div class="dash-widget-body">
						<div class="table-responsive">
							<table class="table table-hover table-comp">
								<thead>
									<tr>
										<th>Usuario</th>
										<th style="text-align: right;">Total</th>
										<th style="text-align: right;">Ingresar</th>
										<th style="text-align: right;">Actualizar</th>
										<th style="text-align: right;">Eliminar</th>
									</tr>
								</thead>
								<tbody id="tbodyUsuarios">
									<tr><td colspan="5" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando datos...</td></tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

		</div>
		<div id="cmpWidgetOcultos" style="display:none;"></div>

	</div>
</div>


<!-- Modal personalizacion comparativo -->
<div class="modal fade" id="modalWidgetsCmp" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" id="btnCerrarWidgetsCmp" aria-hidden="true">&times;</button>
				<h4 class="modal-title"><i class="fa fa-th-large"></i> Personalizar tablero comparativo</h4>
			</div>
			<div class="modal-body">
				<p class="aud-modal-ayuda">Arrastra para ordenar. Clic en el ojo para mostrar u ocultar. <strong>Guardar</strong> aplica; <strong>Restablecer</strong> vuelve al original.</p>
				<h4 class="aud-modal-sec"><i class="fa fa-tachometer"></i> Metricas (KPIs)</h4>
				<div id="cmpKpisSorter"></div>
				<h4 class="aud-modal-sec" style="margin-top:14px;"><i class="fa fa-th-large"></i> Widgets</h4>
				<div id="cmpWidgetsSorter"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default btn-sm" id="btnCerrarWidgetsCmp2">Cerrar</button>
				<button type="button" class="btn btn-warning btn-sm" id="btnRestablecerWidgetsCmp"><i class="fa fa-undo"></i> Restablecer</button>
				<button type="button" class="btn btn-primary btn-sm" id="btnGuardarWidgetsCmp"><i class="fa fa-check"></i> Guardar</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal Envio Correo -->
<div id="modalEnvioCorreo" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
		<div class="modal-content" style="border-radius: 6px; overflow: hidden; border: 1px solid #cbd5e1;">
			<div class="modal-header exa-modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">
					<i class="fa fa-envelope-o"></i> Enviar Reporte por Correo Electr&oacute;nico
				</h4>
			</div>
			<div class="modal-body" style="padding: 16px;">
				<div class="form-group">
					<label style="font-weight: 600; font-size: 12px; color: #334155;">Correo Electr&oacute;nico Destinatario:</label>
					<input type="email" id="mailDestinatario" class="form-control" placeholder="ejemplo@empresa.com" required />
				</div>
				<div class="form-group">
					<label style="font-weight: 600; font-size: 12px; color: #334155;">Nombre del Destinatario (Opcional):</label>
					<input type="text" id="mailNombre" class="form-control" placeholder="Ej: Gerencia General" />
				</div>
				<div class="form-group">
					<label style="font-weight: 600; font-size: 12px; color: #334155;">Asunto del Mensaje:</label>
					<input type="text" id="mailAsunto" class="form-control" value="Informe Estadistico Comparativo de Auditoria" />
				</div>
				<div class="alert alert-info" style="margin-bottom: 0; font-size: 11px; padding: 8px 12px;">
					<i class="fa fa-info-circle"></i> El reporte comparativo generado se adjuntar&aacute; autom&aacute;ticamente en formato <strong>PDF</strong> oficial.
				</div>
			</div>
			<div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 10px 18px;">
				<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancelar</button>
				<button type="button" id="btnEnviarCorreoConfirm" class="btn btn-primary btn-sm" style="font-weight: 600;">
					<i class="fa fa-paper-plane"></i> Enviar Documento
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal Envio WhatsApp -->
<div id="modalEnvioWhatsApp" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
		<div class="modal-content" style="border-radius: 6px; overflow: hidden; border: 1px solid #cbd5e1;">
			<div class="modal-header exa-modal-header" style="background-color: #15803d !important;">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">
					<i class="fa fa-whatsapp"></i> Compartir Informe por WhatsApp
				</h4>
			</div>
			<div class="modal-body" style="padding: 16px;">
				<div class="form-group">
					<label style="font-weight: 600; font-size: 12px; color: #334155;">N&uacute;mero de Tel&eacute;fono (con c&oacute;digo de pa&iacute;s):</label>
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-phone"></i></span>
						<input type="text" id="waTelefono" class="form-control" placeholder="Ej: 593987654321" required />
					</div>
					<span class="help-block" style="font-size: 11px; margin-bottom: 0;">Ingrese el n&uacute;mero sin espacios ni s&iacute;mbolo '+'.</span>
				</div>
				<div class="form-group">
					<label style="font-weight: 600; font-size: 12px; color: #334155;">Vista Previa del Mensaje Resumido:</label>
					<textarea id="waPreviewMensaje" class="form-control" rows="5" readonly style="font-size: 11px; background: #f8fafc; font-family: monospace;"></textarea>
				</div>
				<div style="display: flex; gap: 8px;">
					<button type="button" id="btnEnviarWaApi" class="btn btn-success btn-block" style="font-weight: 700; margin-top: 0;">
						<i class="fa fa-paper-plane"></i> Enviar V&iacute;a API ERP
					</button>
					<button type="button" id="btnAbrirWaWeb" class="btn btn-default btn-block" style="font-weight: 700; color: #16a34a; border-color: #86efac; margin-top: 0;">
						<i class="fa fa-external-link"></i> Abrir WhatsApp Web
					</button>
				</div>
			</div>
			<div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 10px 18px;">
				<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	window.audCmpEmp = <?php echo (int)$audEmpCod; ?>;
	window.audCmpUsu = <?php echo (int)$audUsuCod; ?>;
	window.audCmpCharts = {};
</script>
<script type="text/javascript" src="../VALIDACIONES/aud_par_dashboard_comparativo_widgets.js?v=20260924_v1"></script>
<script>
(function (window, $) {
	'use strict';

	var chartTendenciaInstance = null;
	var chartModulosInstance = null;
	var chartEventosInstance = null;
	var chartHorariosInstance = null;
	var chartSesionesInstance = null;
	var chartUsuariosInstance = null;
	var chartPlantasInstance = null;
	var chartVariacionInstance = null;
	var chartMixAInstance = null;
	var chartMixBInstance = null;
	var cacheDatosComparativa = null;
	window.audCmpNumFmt = null;

	function fmt(d) {
		var m = '' + (d.getMonth() + 1), dia = '' + d.getDate(), y = d.getFullYear();
		if (m.length < 2) m = '0' + m;
		if (dia.length < 2) dia = '0' + dia;
		return [y, m, dia].join('-');
	}

	function calcularRangoPresetDash(preset) {
		var hoy = new Date();
		var pa_ini, pa_fin, pb_ini, pb_fin;

		if (preset === 'hoy') {
			var dHoy = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
			var dAyer = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 1);
			pa_ini = fmt(dAyer);
			pa_fin = fmt(dAyer);
			pb_ini = fmt(dHoy);
			pb_fin = fmt(dHoy);
		} else if (preset === 'ayer') {
			var dAyer = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 1);
			var dAnteayer = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 2);
			pa_ini = fmt(dAnteayer);
			pa_fin = fmt(dAnteayer);
			pb_ini = fmt(dAyer);
			pb_fin = fmt(dAyer);
		} else if (preset === '1semana') {
			var s1 = new Date(); s1.setDate(hoy.getDate() - 14);
			var s2 = new Date(); s2.setDate(hoy.getDate() - 8);
			var s3 = new Date(); s3.setDate(hoy.getDate() - 7);
			pa_ini = fmt(s1);
			pa_fin = fmt(s2);
			pb_ini = fmt(s3);
			pb_fin = fmt(hoy);
		} else if (preset === '1mes') {
			var m1 = new Date(); m1.setDate(hoy.getDate() - 60);
			var m2 = new Date(); m2.setDate(hoy.getDate() - 31);
			var m3 = new Date(); m3.setDate(hoy.getDate() - 30);
			pa_ini = fmt(m1);
			pa_fin = fmt(m2);
			pb_ini = fmt(m3);
			pb_fin = fmt(hoy);
		} else if (preset === '3meses') {
			var q1 = new Date(); q1.setDate(hoy.getDate() - 180);
			var q2 = new Date(); q2.setDate(hoy.getDate() - 91);
			var q3 = new Date(); q3.setDate(hoy.getDate() - 90);
			pa_ini = fmt(q1);
			pa_fin = fmt(q2);
			pb_ini = fmt(q3);
			pb_fin = fmt(hoy);
		} else {
			return null;
		}

		return { pa_ini: pa_ini, pa_fin: pa_fin, pb_ini: pb_ini, pb_fin: pb_fin };
	}

	function sincronizarPresetActivoDash() {
		var pa_ini = $('#pa_ini').val();
		var pa_fin = $('#pa_fin').val();
		var pb_ini = $('#pb_ini').val();
		var pb_fin = $('#pb_fin').val();

		var presets = ['ayer', 'hoy', '1semana', '1mes', '3meses'];
		var coincidencia = null;
		for (var i = 0; i < presets.length; i++) {
			var r = calcularRangoPresetDash(presets[i]);
			if (r && r.pa_ini === pa_ini && r.pa_fin === pa_fin && r.pb_ini === pb_ini && r.pb_fin === pb_fin) {
				coincidencia = presets[i];
				break;
			}
		}

		$('.aud-btn-preset').removeClass('active');
		if (coincidencia) {
			$('.aud-btn-preset[data-preset="' + coincidencia + '"]').addClass('active');
			$('#dashPresetCustomBadge').hide();
		} else {
			$('#dashPresetCustomBadge').show();
		}
	}

	function initCalendariosDash() {
		if (!$.fn.datepicker) return;
		if ($.datepicker) {
			$.datepicker.regional['es'] = {
				closeText: 'Cerrar',
				prevText: '&#x3C;Ant',
				nextText: 'Sig&#x3E;',
				currentText: 'Hoy',
				monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
				monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
				dayNames: ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'],
				dayNamesShort: ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'],
				dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','Sá'],
				weekHeader: 'Sm',
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: ''
			};
			$.datepicker.setDefaults($.datepicker.regional['es']);
		}

		var baseOpts = {
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			showButtonPanel: true,
			firstDay: 1,
			monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
			monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
			dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','Sá']
		};

		$('#pa_ini').datepicker($.extend({}, baseOpts, {
			onClose: function (selectedDate) {
				if (selectedDate) {
					try { $('#pa_fin').datepicker('option', 'minDate', selectedDate); } catch(e) {}
				}
			},
			onSelect: function () {
				sincronizarPresetActivoDash();
				consultarDashboard();
			}
		}));

		$('#pa_fin').datepicker($.extend({}, baseOpts, {
			onClose: function (selectedDate) {
				if (selectedDate) {
					try { $('#pa_ini').datepicker('option', 'maxDate', selectedDate); } catch(e) {}
				}
			},
			onSelect: function () {
				sincronizarPresetActivoDash();
				consultarDashboard();
			}
		}));

		$('#pb_ini').datepicker($.extend({}, baseOpts, {
			onClose: function (selectedDate) {
				if (selectedDate) {
					try { $('#pb_fin').datepicker('option', 'minDate', selectedDate); } catch(e) {}
				}
			},
			onSelect: function () {
				sincronizarPresetActivoDash();
				consultarDashboard();
			}
		}));

		$('#pb_fin').datepicker($.extend({}, baseOpts, {
			onClose: function (selectedDate) {
				if (selectedDate) {
					try { $('#pb_ini').datepicker('option', 'maxDate', selectedDate); } catch(e) {}
				}
			},
			onSelect: function () {
				sincronizarPresetActivoDash();
				consultarDashboard();
			}
		}));
	}

	function obtenerFechasInput() {
		return {
			pa_ini: $('#pa_ini').val() + ' 00:00:00',
			pa_fin: $('#pa_fin').val() + ' 23:59:59',
			pb_ini: $('#pb_ini').val() + ' 00:00:00',
			pb_fin: $('#pb_fin').val() + ' 23:59:59'
		};
	}

	function consultarDashboard() {
		var fechas = obtenerFechasInput();
		fechas.action = 'consultar';

		$('#listaObservaciones').html('<li><i class="fa fa-spinner fa-spin"></i> Calculando diagn&oacute;stico comparativo...</li>');

		$.ajax({
			url: '../LOGICA/aud_log_dashboard.php',
			type: 'GET',
			dataType: 'json',
			data: fechas,
			success: function (res) {
				if (res && res.success && res.data) {
					cacheDatosComparativa = res.data;
					if (window.audCmpRenderKPIs) window.audCmpRenderKPIs(res.data.kpis_comparativa || [], numFmt);
					else renderizarKPIs(res.data.kpis_comparativa || []);
					renderizarObservaciones(res.data.observaciones || []);
					renderizarGraficoTendencia(res.data.temporal_comparativa || {});
					renderizarGraficoModulos(res.data.modulos_comparativa || []);
					renderizarGraficoEventosComp(res.data.eventos_comparativa || {});
					renderizarGraficoHorarios(res.data.horarios_comparativa || []);
					renderizarGraficoSesiones(res.data.sesiones_comparativa || {});
					renderizarGraficoUsuarios(res.data.usuarios_comparativa || {});
					renderizarGraficoPlantas(res.data.plantas_comparativa || []);
					renderizarGraficoVariacion(res.data.variacion_modulos || []);
					renderizarGraficoMix(res.data.mix_operaciones || {});
					renderizarTablaModulos(res.data.modulos_comparativa || []);
					renderizarTablaUsuarios(res.data.usuarios_top_b || []);
					var foot = [];
					if (res.data.empresa_nombre) foot.push('<i class="fa fa-building-o"></i> ' + res.data.empresa_nombre);
					if (res.data.periodo_a_label) foot.push('A: ' + res.data.periodo_a_label);
					if (res.data.periodo_b_label) foot.push('B: ' + res.data.periodo_b_label);
					$('#cmpKpiFoot').html(foot.join(' &nbsp;&middot;&nbsp; ') || '&nbsp;');
				} else {
					alert('Error al consultar datos comparativos.');
				}
			},
			error: function () {
				alert('No se pudo conectar con el servicio de estadisticas.');
			}
		});
	}

	function numFmt(x, maxDecs) {
		maxDecs = (maxDecs === undefined) ? 0 : maxDecs;
		var n = parseFloat(x) || 0;
		return n.toLocaleString(undefined, { maximumFractionDigits: maxDecs });
	}
	window.audCmpNumFmt = numFmt;

	function renderizarKPIs(kpis) {
		var html = '';
		kpis.forEach(function (k) {
			var pct = (typeof k.pct_cambio === 'number' && !isNaN(k.pct_cambio)) ? k.pct_cambio : null;
			var favor = k.favorable_subida !== false;
			var badgeClass = 'badge-neutral';
			var icono = 'fa-minus';
			var txtPct = 'N/D';

			if (pct === 0) {
				txtPct = '0%';
			} else if (pct !== null) {
				var sube = pct > 0;
				var bueno = favor ? sube : !sube;
				badgeClass = bueno ? 'badge-up' : 'badge-down';
				icono = sube ? 'fa-arrow-up' : 'fa-arrow-down';
				txtPct = (sube ? '+' : '') + pct.toFixed(1) + '%';
			}

			var borderCol = '#2563eb';
			if (k.color === 'success') borderCol = '#10b981';
			else if (k.color === 'warning') borderCol = '#f59e0b';
			else if (k.color === 'danger') borderCol = '#ef4444';
			else if (k.color === 'info') borderCol = '#0284c7';
			else if (k.color === 'purple') borderCol = '#8b5cf6';
			else if (k.color === 'orange') borderCol = '#ea580c';

			var tituloTip = k.titulo;
			if (k.dias_a && k.dias_b) tituloTip += ' (A: ' + k.dias_a + ' dias · B: ' + k.dias_b + ' dias)';

			html += '<div class="aud-cmp-kpi" style="border-top-color: ' + borderCol + ';" title="' + tituloTip + '">';
			html += '  <div class="kpi-title"><i class="fa ' + k.icono + '"></i> <span>' + k.titulo + '</span></div>';
			html += '  <div class="kpi-values-row">';
			html += '    <div class="kpi-main-val">' + numFmt(k.valor_b) + '</div>';
			html += '    <span class="kpi-badge ' + badgeClass + '"><i class="fa ' + icono + '"></i> ' + txtPct + '</span>';
			html += '  </div>';
			html += '  <div class="kpi-compare">';
			html += '    <div><span class="kpi-tag a">A</span><strong>' + numFmt(k.valor_a) + '</strong><span class="kpi-rate">' + numFmt(k.promedio_diario_a, 1) + '/d&iacute;a</span></div>';
			html += '    <div><span class="kpi-tag b">B</span><strong>' + numFmt(k.valor_b) + '</strong><span class="kpi-rate">' + numFmt(k.promedio_diario_b, 1) + '/d&iacute;a</span></div>';
			html += '  </div>';
			html += '</div>';
		});
		$('#cmpKpiGrid').html(html || '<div class="text-muted" style="font-size:12px;padding:8px;">Sin metricas.</div>');
	}

	function renderizarObservaciones(obs) {
		var html = '';
		if (!obs || obs.length === 0) {
			html = '<li><i class="fa fa-check-circle text-success" style="margin-right:4px;"></i> No se registraron anomal&iacute;as o variaciones cr&iacute;ticas en este per&iacute;odo.</li>';
		} else {
			obs.forEach(function (o) {
				var iconBullet = 'fa-info-circle text-primary';
				if (o.indexOf('incremento') !== -1 || o.indexOf('aumento') !== -1) {
					iconBullet = 'fa-arrow-circle-up text-warning';
				} else if (o.indexOf('cerraron') !== -1 || o.indexOf('inactividad') !== -1) {
					iconBullet = 'fa-clock-o text-muted';
				} else if (o.indexOf('forzado') !== -1 || o.indexOf('alerta') !== -1) {
					iconBullet = 'fa-exclamation-triangle text-danger';
				}
				html += '<li><i class="fa ' + iconBullet + '" style="margin-right:4px;"></i> ' + o + '</li>';
			});
		}
		$('#listaObservaciones').html(html);
	}

	// Grafico 1: Tendencia Diaria Comparativa
	function renderizarGraficoTendencia(tempComp) {
		var cats = tempComp.categorias || [];
		var sA = tempComp.serie_a || [];
		var sB = tempComp.serie_b || [];

		if (cats.length === 0) {
			cats = ['Día 1'];
			sA = [0];
			sB = [0];
		}

		var options = {
			series: [
				{ name: 'Período A (Base)', data: sA },
				{ name: 'Período B (Comparado)', data: sB }
			],
			chart: {
				type: 'area',
				height: 260,
				fontFamily: "'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
				toolbar: { show: false }
			},
			colors: ['#94a3b8', '#2563eb'],
			stroke: { curve: 'smooth', width: 2.5 },
			fill: {
				type: 'gradient',
				gradient: { opacityFrom: 0.45, opacityTo: 0.05 }
			},
			dataLabels: { enabled: false },
			xaxis: { categories: cats },
			yaxis: { title: { text: 'Movimientos' } },
			tooltip: { shared: true, intersect: false },
			grid: { borderColor: '#f1f5f9' }
		};

		try {
			if (chartTendenciaInstance) chartTendenciaInstance.destroy();
			chartTendenciaInstance = new ApexCharts(document.querySelector("#chartTendencia"), options);
			chartTendenciaInstance.render();
			window.audCmpCharts.tendencia = chartTendenciaInstance;
		} catch (e) {
			$('#chartTendencia').html('<div class="text-muted text-center" style="padding:40px 10px;font-size:12px;">No hay datos suficientes para el gr&aacute;fico.</div>');
		}
	}

	// Grafico 2: Modulos Comparativo
	function renderizarGraficoModulos(modulos) {
		var topM = (modulos || []).slice(0, 8);
		if (!topM.length) {
			topM = [{ modulo: 'Sin datos', total_a: 0, total_b: 0 }];
		}
		var categorias = topM.map(function (m) { return m.modulo; });
		var serieA = topM.map(function (m) { return m.total_a; });
		var serieB = topM.map(function (m) { return m.total_b; });

		var options = {
			series: [
				{ name: 'Período A (Base)', data: serieA },
				{ name: 'Período B (Comparado)', data: serieB }
			],
			chart: {
				type: 'bar',
				height: 260,
				fontFamily: "'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
				toolbar: { show: false }
			},
			plotOptions: {
				bar: { horizontal: false, columnWidth: '45%', borderRadius: 3 }
			},
			dataLabels: { enabled: false },
			colors: ['#94a3b8', '#3b82f6'],
			xaxis: { categories: categorias },
			yaxis: { title: { text: 'Movimientos' } },
			tooltip: { shared: true, intersect: false },
			grid: { borderColor: '#f1f5f9' }
		};

		try {
			if (chartModulosInstance) chartModulosInstance.destroy();
			chartModulosInstance = new ApexCharts(document.querySelector("#chartModulos"), options);
			chartModulosInstance.render();
			window.audCmpCharts.modulos = chartModulosInstance;
		} catch (e) {
			$('#chartModulos').html('<div class="text-muted text-center" style="padding:40px 10px;font-size:12px;">No hay datos suficientes para el gr&aacute;fico.</div>');
		}
	}

	// Grafico 3: Comparativa de Operaciones (Ingresar, Actualizar, Eliminar)
	function renderizarGraficoEventosComp(eveComp) {
		var cats = eveComp.categorias || ['Ingresar', 'Actualizar', 'Eliminar', 'Otros / Consultas'];
		var sA = eveComp.serie_a || [0, 0, 0, 0];
		var sB = eveComp.serie_b || [0, 0, 0, 0];

		var options = {
			series: [
				{ name: 'Período A (Base)', data: sA },
				{ name: 'Período B (Comparado)', data: sB }
			],
			chart: {
				type: 'bar',
				height: 260,
				fontFamily: "'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
				toolbar: { show: false }
			},
			plotOptions: {
				bar: { horizontal: false, columnWidth: '45%', borderRadius: 3 }
			},
			colors: ['#94a3b8', '#10b981'],
			dataLabels: { enabled: false },
			xaxis: { categories: cats },
			yaxis: { title: { text: 'Operaciones' } },
			tooltip: { shared: true, intersect: false },
			grid: { borderColor: '#f1f5f9' }
		};

		if (chartEventosInstance) chartEventosInstance.destroy();
		chartEventosInstance = new ApexCharts(document.querySelector("#chartEventos"), options);
		chartEventosInstance.render();
		window.audCmpCharts.eventos = chartEventosInstance;
	}

	// Grafico 4: Horarios Comparativo
	function renderizarGraficoHorarios(horarios) {
		var categorias = horarios.map(function (h) { return h.hora; });
		var serieA = horarios.map(function (h) { return h.total_a; });
		var serieB = horarios.map(function (h) { return h.total_b; });

		var options = {
			series: [
				{ name: 'Período A (Base)', data: serieA },
				{ name: 'Período B (Comparado)', data: serieB }
			],
			chart: {
				type: 'area',
				height: 250,
				fontFamily: "'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
				toolbar: { show: false }
			},
			colors: ['#94a3b8', '#f59e0b'],
			stroke: { curve: 'smooth', width: 2 },
			fill: {
				type: 'gradient',
				gradient: { opacityFrom: 0.35, opacityTo: 0.05 }
			},
			dataLabels: { enabled: false },
			xaxis: { categories: categorias },
			yaxis: { title: { text: 'Operaciones' } },
			tooltip: { shared: true, intersect: false },
			grid: { borderColor: '#f1f5f9' }
		};

		if (chartHorariosInstance) chartHorariosInstance.destroy();
		chartHorariosInstance = new ApexCharts(document.querySelector("#chartHorarios"), options);
		chartHorariosInstance.render();
		window.audCmpCharts.horarios = chartHorariosInstance;
	}

	// Grafico 5: Metricas de Sesiones y Seguridad
	function renderizarGraficoSesiones(sesComp) {
		var cats = sesComp.categorias || [];
		var sA = sesComp.serie_a || [];
		var sB = sesComp.serie_b || [];

		var options = {
			series: [
				{ name: 'Período A (Base)', data: sA },
				{ name: 'Período B (Comparado)', data: sB }
			],
			chart: {
				type: 'bar',
				height: 250,
				fontFamily: "'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
				toolbar: { show: false }
			},
			plotOptions: {
				bar: { horizontal: true, barHeight: '50%', borderRadius: 3 }
			},
			colors: ['#94a3b8', '#ef4444'],
			dataLabels: { enabled: false },
			xaxis: { categories: cats },
			tooltip: { shared: true, intersect: false },
			grid: { borderColor: '#f1f5f9' }
		};

		if (chartSesionesInstance) chartSesionesInstance.destroy();
		chartSesionesInstance = new ApexCharts(document.querySelector("#chartSesiones"), options);
		chartSesionesInstance.render();
		window.audCmpCharts.sesiones = chartSesionesInstance;
	}

	function cmpDualBar(cats, serieA, serieB, horizontal) {
		return {
			series: [
				{ name: 'Periodo A (Base)', data: serieA },
				{ name: 'Periodo B (Comp)', data: serieB }
			],
			chart: { type: 'bar', height: horizontal ? Math.max(220, cats.length * 28) : 260, toolbar: { show: false }, fontFamily: 'inherit' },
			plotOptions: { bar: { horizontal: !!horizontal, columnWidth: '55%', borderRadius: 2 } },
			colors: ['#64748b', '#2563eb'],
			dataLabels: { enabled: false },
			xaxis: { categories: cats },
			legend: { position: 'top', fontSize: '11px' },
			grid: { borderColor: '#eef2f7' },
			tooltip: { shared: true, intersect: false }
		};
	}

	function cmpEmpty(sel, msg) {
		$(sel).html('<div class="text-muted text-center" style="padding:40px 10px;font-size:12px;">' + (msg || 'No hay datos suficientes para el grafico.') + '</div>');
	}

	function renderizarGraficoUsuarios(usuComp) {
		var cats = usuComp.categorias || [];
		var a = usuComp.serie_a || [];
		var b = usuComp.serie_b || [];
		if (chartUsuariosInstance) { try { chartUsuariosInstance.destroy(); } catch (e) {} chartUsuariosInstance = null; }
		if (!cats.length) { cmpEmpty('#chartUsuarios'); return; }
		chartUsuariosInstance = new ApexCharts(document.querySelector('#chartUsuarios'), cmpDualBar(cats, a, b, true));
		chartUsuariosInstance.render();
		window.audCmpCharts.usuarios = chartUsuariosInstance;
	}

	function renderizarGraficoPlantas(plantas) {
		var top = (plantas || []).slice(0, 8);
		var cats = top.map(function (p) { return p.planta; });
		var a = top.map(function (p) { return p.total_a; });
		var b = top.map(function (p) { return p.total_b; });
		if (chartPlantasInstance) { try { chartPlantasInstance.destroy(); } catch (e) {} chartPlantasInstance = null; }
		if (!cats.length) { cmpEmpty('#chartPlantas', 'Sin actividad por planta en los periodos.'); return; }
		chartPlantasInstance = new ApexCharts(document.querySelector('#chartPlantas'), cmpDualBar(cats, a, b, true));
		chartPlantasInstance.render();
		window.audCmpCharts.plantas = chartPlantasInstance;
	}

	function renderizarGraficoVariacion(rows) {
		var cats = (rows || []).map(function (r) { return r.modulo; });
		var data = (rows || []).map(function (r) { return r.pct; });
		if (chartVariacionInstance) { try { chartVariacionInstance.destroy(); } catch (e) {} chartVariacionInstance = null; }
		if (!cats.length) { cmpEmpty('#chartVariacion'); return; }
		var colors = data.map(function (v) { return v >= 0 ? '#10b981' : '#ef4444'; });
		chartVariacionInstance = new ApexCharts(document.querySelector('#chartVariacion'), {
			series: [{ name: 'Variacion %', data: data }],
			chart: { type: 'bar', height: Math.max(240, cats.length * 26), toolbar: { show: false }, fontFamily: 'inherit' },
			plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 2 } },
			colors: colors,
			dataLabels: { enabled: true, formatter: function (v) { return (v > 0 ? '+' : '') + v + '%'; }, style: { fontSize: '10px' } },
			xaxis: { categories: cats },
			legend: { show: false },
			grid: { borderColor: '#eef2f7' },
			tooltip: { y: { formatter: function (v) { return (v > 0 ? '+' : '') + v + '%'; } } }
		});
		chartVariacionInstance.render();
		window.audCmpCharts.variacion = chartVariacionInstance;
	}

	function renderizarGraficoMix(mix) {
		var labels = mix.labels || ['Ingresar', 'Actualizar', 'Eliminar', 'Otros'];
		var a = mix.serie_a || [0, 0, 0, 0];
		var b = mix.serie_b || [0, 0, 0, 0];
		if (chartMixAInstance) { try { chartMixAInstance.destroy(); } catch (e) {} chartMixAInstance = null; }
		if (chartMixBInstance) { try { chartMixBInstance.destroy(); } catch (e) {} chartMixBInstance = null; }
		var colors = ['#10b981', '#f59e0b', '#ef4444', '#94a3b8'];
		function pieOpts(title, serie) {
			return {
				series: serie,
				labels: labels,
				chart: { type: 'donut', height: 220, toolbar: { show: false }, fontFamily: 'inherit' },
				colors: colors,
				legend: { position: 'bottom', fontSize: '10px' },
				title: { text: title, align: 'center', style: { fontSize: '12px', fontWeight: 700, color: '#334155' } },
				dataLabels: { enabled: true, style: { fontSize: '10px' } },
				plotOptions: { pie: { donut: { size: '55%' } } }
			};
		}
		if (!document.querySelector('#chartMixA') || !document.querySelector('#chartMixB')) return;
		chartMixAInstance = new ApexCharts(document.querySelector('#chartMixA'), pieOpts('Periodo A', a));
		chartMixBInstance = new ApexCharts(document.querySelector('#chartMixB'), pieOpts('Periodo B', b));
		chartMixAInstance.render();
		chartMixBInstance.render();
		window.audCmpCharts.mixA = chartMixAInstance;
		window.audCmpCharts.mixB = chartMixBInstance;
	}

	window.audCmpResizeCharts = function () {
		var list = [chartTendenciaInstance, chartModulosInstance, chartEventosInstance, chartHorariosInstance, chartSesionesInstance, chartUsuariosInstance, chartPlantasInstance, chartVariacionInstance, chartMixAInstance, chartMixBInstance];
		for (var i = 0; i < list.length; i++) {
			if (list[i] && typeof list[i].resize === 'function') {
				try { list[i].resize(); } catch (e) {}
			}
		}
	};

	function renderizarTablaModulos(modulos) {
		var html = '';
		if (!modulos || modulos.length === 0) {
			html = '<tr><td colspan="4" class="text-center text-muted">Sin datos de m&oacute;dulos en los per&iacute;odos seleccionados.</td></tr>';
		} else {
			modulos.forEach(function (m) {
				var diff = (m.total_b - m.total_a);
				var color = diff > 0 ? '#166534' : (diff < 0 ? '#991b1b' : '#64748b');
				var pref = diff > 0 ? '+' : '';
				html += '<tr>';
				html += '  <td><strong>' + m.modulo + '</strong></td>';
				html += '  <td style="text-align: right; color:#64748b;">' + Number(m.total_a).toLocaleString() + '</td>';
				html += '  <td style="text-align: right; font-weight: 700; color:#1e293b;">' + Number(m.total_b).toLocaleString() + '</td>';
				html += '  <td style="text-align: right; color: ' + color + '; font-weight: 700;">' + pref + Number(diff).toLocaleString() + '</td>';
				html += '</tr>';
			});
		}
		$('#tbodyModulos').html(html);
	}

	function renderizarTablaUsuarios(usuarios) {
		var html = '';
		if (!usuarios || usuarios.length === 0) {
			html = '<tr><td colspan="5" class="text-center text-muted">Sin datos de usuarios en este per&iacute;odo.</td></tr>';
		} else {
			usuarios.forEach(function (u) {
				var nom = u.UsuarioNombre ? (u.UsuarioNombre + ' ' + (u.UsuarioApellido || '')) : ('Usuario #' + u.Usuario_Id);
				html += '<tr>';
				html += '  <td><strong>' + nom + '</strong></td>';
				html += '  <td style="text-align: right; font-weight: 700;">' + Number(u.Total_Operaciones).toLocaleString() + '</td>';
				html += '  <td style="text-align: right;"><span class="badge-eve-ins">' + Number(u.Total_Inserciones).toLocaleString() + '</span></td>';
				html += '  <td style="text-align: right;"><span class="badge-eve-upd">' + Number(u.Total_Modificaciones).toLocaleString() + '</span></td>';
				html += '  <td style="text-align: right;"><span class="badge-eve-del">' + Number(u.Total_Eliminaciones).toLocaleString() + '</span></td>';
				html += '</tr>';
			});
		}
		$('#tbodyUsuarios').html(html);
	}

	// Descargar PDF
	$('#btnDescargarPdf').on('click', function () {
		var f = obtenerFechasInput();
		var url = '../LOGICA/aud_log_dashboard.php?action=exportar_pdf&pa_ini=' + encodeURIComponent(f.pa_ini) +
			'&pa_fin=' + encodeURIComponent(f.pa_fin) +
			'&pb_ini=' + encodeURIComponent(f.pb_ini) +
			'&pb_fin=' + encodeURIComponent(f.pb_fin);
		window.open(url, '_blank');
	});

	// Modal Envio Correo
	$('#btnModalCorreo').on('click', function () {
		$('#modalEnvioCorreo').modal('show');
	});

	$('#btnEnviarCorreoConfirm').on('click', function () {
		var correo = $('#mailDestinatario').val().trim();
		if (!correo) {
			alert('Por favor ingrese el correo de destino.');
			return;
		}
		var f = obtenerFechasInput();
		var $btn = $(this);
		$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');

		$.ajax({
			url: '../LOGICA/aud_log_dashboard.php',
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'enviar_correo',
				correo: correo,
				nombre: $('#mailNombre').val().trim(),
				asunto: $('#mailAsunto').val().trim(),
				pa_ini: f.pa_ini,
				pa_fin: f.pa_fin,
				pb_ini: f.pb_ini,
				pb_fin: f.pb_fin
			},
			success: function (res) {
				$btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Documento');
				if (res && res.success) {
					$('#modalEnvioCorreo').modal('hide');
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
	$('#btnModalWhatsApp').on('click', function () {
		if (cacheDatosComparativa) {
			var preview = "*INFORME COMPARATIVO DE AUDITORÍA ERP*\n" +
				"Empresa: " + cacheDatosComparativa.empresa_nombre + "\n" +
				"Base (A): " + cacheDatosComparativa.periodo_a_label + "\n" +
				"Comparado (B): " + cacheDatosComparativa.periodo_b_label + "\n" +
				"Días analizados: A (" + (cacheDatosComparativa.periodo_a_dias || '?') + ") ? B (" + (cacheDatosComparativa.periodo_b_dias || '?') + ")\n\n";
			if (cacheDatosComparativa.kpis_comparativa) {
				cacheDatosComparativa.kpis_comparativa.forEach(function (k) {
					var pctV = (typeof k.pct_cambio === 'number' && !isNaN(k.pct_cambio)) ? k.pct_cambio : null;
					var sPct = (pctV === null) ? 'N/D (sin base)' : ((pctV > 0 ? '+' : '') + pctV.toFixed(1) + '%');
					preview += "• " + k.titulo + ": " + Number(k.valor_b).toLocaleString() + " (" + sPct + ")\n";
				});
			}
			$('#waPreviewMensaje').text(preview);
		}
		$('#modalEnvioWhatsApp').modal('show');
	});

	// Cerrar el dropdown de Acciones al seleccionar una opcion
	$(document).on('click', '.aud-acciones-menu a', function () {
		var $dd = $(this).closest('.dropdown');
		$dd.removeClass('open');
		$dd.find('.dropdown-toggle').attr('aria-expanded', 'false');
	});

	$('#btnEnviarWaApi').on('click', function () {
		var tel = $('#waTelefono').val().trim();
		if (!tel) {
			alert('Por favor ingrese el número de teléfono destinatario.');
			return;
		}
		var f = obtenerFechasInput();
		var $btn = $(this);
		$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');

		$.ajax({
			url: '../LOGICA/aud_log_dashboard.php',
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'enviar_whatsapp',
				telefono: tel,
				pa_ini: f.pa_ini,
				pa_fin: f.pa_fin,
				pb_ini: f.pb_ini,
				pb_fin: f.pb_fin
			},
			success: function (res) {
				$btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Vía API ERP');
				if (res && res.success) {
					alert('Se generó el despacho para WhatsApp.');
					if (res.url_whatsapp) {
						window.open(res.url_whatsapp, '_blank');
					}
					$('#modalEnvioWhatsApp').modal('hide');
				} else {
					alert('Error: ' + (res.message || 'No se pudo procesar WhatsApp'));
				}
			},
			error: function () {
				$btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Vía API ERP');
				alert('Fallo de conexión al enviar WhatsApp.');
			}
		});
	});

	$('#btnAbrirWaWeb').on('click', function () {
		var tel = $('#waTelefono').val().trim();
		var f = obtenerFechasInput();

		$.ajax({
			url: '../LOGICA/aud_log_dashboard.php',
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'enviar_whatsapp',
				telefono: tel,
				pa_ini: f.pa_ini,
				pa_fin: f.pa_fin,
				pb_ini: f.pb_ini,
				pb_fin: f.pb_fin
			},
			success: function (res) {
				if (res && res.url_whatsapp) {
					window.open(res.url_whatsapp, '_blank');
				}
			}
		});
	});

	// Manejo de presets
	$('.aud-btn-preset').on('click', function (e) {
		e.preventDefault();
		var preset = $(this).data('preset');
		var r = calcularRangoPresetDash(preset);
		if (r) {
			$('.aud-btn-preset').removeClass('active');
			$(this).addClass('active');
			$('#dashPresetCustomBadge').hide();

			$('#pa_ini').val(r.pa_ini);
			$('#pa_fin').val(r.pa_fin);
			$('#pb_ini').val(r.pb_ini);
			$('#pb_fin').val(r.pb_fin);

			try {
				$('#pa_ini').datepicker('setDate', r.pa_ini);
				$('#pa_fin').datepicker('setDate', r.pa_fin);
				$('#pb_ini').datepicker('setDate', r.pb_ini);
				$('#pb_fin').datepicker('setDate', r.pb_fin);
			} catch(eSet) {}

			consultarDashboard();
		}
	});

	$('#btnRecargarDash').on('click', function () {
		consultarDashboard();
	});

	$('#pa_ini, #pa_fin, #pb_ini, #pb_fin').on('change', function () {
		sincronizarPresetActivoDash();
		consultarDashboard();
	});

	$(document).ready(function () {
		initCalendariosDash();
		sincronizarPresetActivoDash();
		if (typeof window.audCmpWidgetsReady === 'function') window.audCmpWidgetsReady();
		consultarDashboard();
	});

})(window, window.jQuery);
</script>
</body>
</html>
