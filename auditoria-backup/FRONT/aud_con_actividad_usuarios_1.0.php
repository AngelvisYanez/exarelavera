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

$audEmpCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
$audSucCod = isset($_SESSION['Ses_Suc_Cod']) ? (int)$_SESSION['Ses_Suc_Cod'] : 0;
$audUsuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;
$Ses_Dat_Dis = isset($_SESSION['Ses_Dat_Dis']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']) : '';

$obBD_con1 = new Class_Log_Datos_Cfg_Monitoreo();
$obBD_conexion = new Class_Log_Conexion_Cfg_Monitoreo($Ses_Dat_Dis !== '' ? $Ses_Dat_Dis : null);
aud_ses_asegurar_esquema($obBD_conexion->conexion);

// Validar si es Administrador de Sistemas
$esAdminSistemas = aud_cfg_es_admin_sistemas($audUsuCod, $obBD_con1, $obBD_conexion);

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

		/* Barra de control de periodo */
		.aud-period-strip {
			background: #fdfefe;
			border-bottom: 1px solid #e2e8f0;
			padding: 8px 14px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 10px;
		}
		.aud-period-left {
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
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
		}
		.aud-btn-preset.active {
			background: #2563eb;
			color: #ffffff;
			border-color: #1d4ed8;
		}
		.aud-date-group {
			display: flex;
			align-items: center;
			gap: 6px;
		}
		.aud-date-input-wrap {
			position: relative;
			display: inline-block;
		}
		.aud-date-input-wrap input {
			width: 105px;
			font-size: 11px;
			padding: 3px 22px 3px 8px;
			height: 26px;
			border-radius: 3px;
			border: 1px solid #cbd5e1;
			background: #ffffff;
			cursor: pointer;
			text-align: center;
		}
		.aud-date-input-wrap .cal-icon {
			position: absolute;
			right: 6px;
			top: 6px;
			font-size: 11px;
			color: #94a3b8;
			pointer-events: none;
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
	</style>
</head>
<body>

<div class="panel panel-default panel-main exa-ui-panel exa-ui-fill-page" style="margin-top: 0;">
	<div class="panel-heading exa-header">
		<div class="row" style="display: flex; align-items: center; justify-content: flex-end;">
			<div class="col-xs-12 text-right">
				<div style="display: inline-flex; align-items: center; gap: 8px;">
					<span id="audLiveIndicator" class="label label-success" style="padding: 4px 8px; font-size: 11px;">
						<i class="fa fa-circle" style="animation: blink 1.5s infinite;"></i> En Vivo
					</span>
					<button id="btnRecargarActividad" class="btn btn-sm btn-primary" style="font-weight: 600;">
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
	</div>

	<div class="panel-body exa-body" style="padding: 12px 16px;">

		<!-- Tarjetas Resumen KPIs -->
		<div class="aud-kpi-grid">
			<div class="aud-kpi-card" style="border-left: 3px solid #15803d;">
				<div class="aud-kpi-icon green"><i class="fa fa-plug"></i></div>
				<div>
					<div class="aud-kpi-val text-success" id="kpiEnLinea">0</div>
					<div class="aud-kpi-lbl">Usuarios En L&iacute;nea</div>
				</div>
			</div>
			<div class="aud-kpi-card" style="border-left: 3px solid #a16207;">
				<div class="aud-kpi-icon yellow"><i class="fa fa-clock-o"></i></div>
				<div>
					<div class="aud-kpi-val text-warning" id="kpiAusentes">0</div>
					<div class="aud-kpi-lbl">Usuarios Ausentes</div>
				</div>
			</div>
			<div class="aud-kpi-card" style="border-left: 3px solid #4338ca;">
				<div class="aud-kpi-icon blue"><i class="fa fa-sign-in"></i></div>
				<div>
					<div class="aud-kpi-val text-primary" id="kpiSesionesHoy">0</div>
					<div class="aud-kpi-lbl">Sesiones Hoy</div>
				</div>
			</div>
			<div class="aud-kpi-card" style="border-left: 3px solid #7e22ce;">
				<div class="aud-kpi-icon purple"><i class="fa fa-hourglass-start"></i></div>
				<div>
					<div class="aud-kpi-val text-info" id="kpiPromedioUso">0 min</div>
					<div class="aud-kpi-lbl">Tiempo Promedio Uso</div>
				</div>
			</div>
		</div>

		<!-- Panel Principal de Sesiones -->
		<div class="aud-main-grid">
			<div class="aud-panel aud-panel-flex">
					<!-- Selector de Periodos Rapidos y Calendario -->
					<div class="aud-period-strip">
						<div class="aud-period-left">
							<span style="font-size: 11px; font-weight: 700; color: #334155; text-transform: uppercase;">
								<i class="fa fa-calendar text-primary"></i> Per&iacute;odo:
							</span>
							<div class="btn-group btn-group-xs" id="audActPeriodoPresets">
								<button type="button" class="btn aud-btn-preset" data-preset="hoy">Hoy</button>
								<button type="button" class="btn aud-btn-preset" data-preset="ayer">Ayer</button>
								<button type="button" class="btn aud-btn-preset" data-preset="1semana">1 Semana</button>
								<button type="button" class="btn aud-btn-preset active" data-preset="1mes">1 Mes</button>
								<button type="button" class="btn aud-btn-preset" data-preset="3meses">3 Meses</button>
							</div>
							<span id="audActCustomBadge" class="label label-info" style="display: none; font-size: 10px; padding: 3px 6px;">
								<i class="fa fa-calendar"></i> Personalizado
							</span>
						</div>
						<div class="aud-date-group">
							<span style="font-size: 11px; font-weight: 600; color: #64748b;">Desde:</span>
							<div class="aud-date-input-wrap">
								<input type="text" id="fromAct" class="form-control" placeholder="aaaa-mm-dd" autocomplete="off" />
								<i class="fa fa-calendar cal-icon" id="btnFromActCal" title="Desplegar calendario Desde"></i>
							</div>
							<span style="font-size: 11px; font-weight: 600; color: #64748b;">Hasta:</span>
							<div class="aud-date-input-wrap">
								<input type="text" id="toAct" class="form-control" placeholder="aaaa-mm-dd" autocomplete="off" />
								<i class="fa fa-calendar cal-icon" id="btnToActCal" title="Desplegar calendario Hasta"></i>
							</div>
						</div>
					</div>

					<div class="aud-panel-head">
						<h4 class="aud-panel-title">
							<i class="fa fa-list text-muted"></i> Sesiones Registradas
							<span id="conteoRegistros" class="badge" style="background:#e2e8f0; color:#475569;">0</span>
						</h4>
						<div style="display: flex; gap: 8px; align-items: center;">
							<select id="filtroEstado" class="form-control input-sm" style="width: 140px; font-size: 11px;">
								<option value="">Todos los Estados</option>
								<option value="en_linea">En L&iacute;nea (&lt; 5 min)</option>
								<option value="ausente">Ausentes (5 - 15 min)</option>
								<option value="inactivo">Cerradas / Inactivas</option>
							</select>
							<?php if (!empty($roles)) { ?>
							<select id="filtroRol" class="form-control input-sm" style="width: 150px; font-size: 11px;">
								<option value="">Todos los Roles</option>
								<?php foreach ($roles as $r) { ?>
									<option value="<?php echo (int)$r['Perfiles_Id']; ?>"><?php echo htmlspecialchars($r['Perfiles_Desc']); ?></option>
								<?php } ?>
							</select>
							<?php } ?>
							<input type="text" id="filtroTexto" class="form-control input-sm" placeholder="Filtrar por usuario, IP..." style="width: 160px; font-size: 11px;" />
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
									<th>&Uacute;ltimo Latido</th>
									<th>Tiempo de Uso</th>
									<th>Estado</th>
									<?php if ($esAdminSistemas) { ?>
									<th style="text-align: right;">Acci&oacute;n</th>
									<?php } ?>
								</tr>
							</thead>
							<tbody id="tbodyActividad">
								<tr>
									<td colspan="<?php echo $esAdminSistemas ? 8 : 7; ?>" class="text-center text-muted" style="padding: 30px;">
										<i class="fa fa-spinner fa-spin fa-2x"></i><br>Cargando sesiones y actividad...
									</td>
								</tr>
							</tbody>
						</table>
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

				<div class="aud-panel" style="padding: 14px; background: #eff6ff; border-color: #bfdbfe;">
					<h5 style="margin-top:0; font-weight: 700; color: #1e40af; font-size: 13px;">
						<i class="fa fa-shield"></i> Seguridad &amp; Inactividad
					</h5>
					<p style="font-size: 11px; color: #3b82f6; line-height: 1.5; margin-bottom: 8px;">
						El sistema monitorea la inactividad de cada usuario, pero el cierre autom&aacute;tico est&aacute; <strong>desactivado</strong> en producci&oacute;n. Las sesiones inactivas se conservan; nadie es expulsado por inactividad.
					</p>
					<?php if ($esAdminSistemas) { ?>
						<span class="label label-info" style="font-size: 11px;">
							<i class="fa fa-key"></i> Modo Administrador Activo
						</span>
					<?php } ?>
				</div>
			</div>
		</div>

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

<script>
(function (window, $) {
	'use strict';

	var timerAutoRefresh = null;
	var listaActividadCache = [];
	var esAdmin = <?php echo $esAdminSistemas ? 'true' : 'false'; ?>;

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
		var presets = ['hoy', 'ayer', '1semana', '1mes', '3meses'];
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
				renderizarTabla(listaActividadCache);
				renderizarTopUsuarios(d.top_usuarios || []);
			}
		});
	}

	function renderizarTabla(sesiones) {
		var q = $.trim($('#filtroTexto').val() || '').toLowerCase();
		var filtradas = sesiones;

		if (q !== '') {
			filtradas = sesiones.filter(function (s) {
				var texto = (s.NombreCompleto + ' ' + (s.Usu_Nom || '') + ' ' + (s.Ses_Ip || '') + ' ' + (s.Perfiles_Desc || '')).toLowerCase();
				return texto.indexOf(q) !== -1;
			});
		}

		$('#conteoRegistros').text(filtradas.length);

		if (filtradas.length === 0) {
			$('#tbodyActividad').html('<tr><td colspan="' + (esAdmin ? 8 : 7) + '" class="text-center text-muted" style="padding: 24px;">No se encontraron sesiones que coincidan con los filtros.</td></tr>');
			return;
		}

		var html = '';
		filtradas.forEach(function (it) {
			var inicial = (it.Prs_Nom && it.Prs_Nom.length) ? it.Prs_Nom.charAt(0).toUpperCase() : 'U';
			var semaforo = it.Semaforo;
			var badgeClase = semaforo;

			var relativo = it.MinutosInactivo === 0 ? 'Hace un instante' : ('Hace ' + it.MinutosInactivo + ' min');
			var horaActividad = it.Ses_Ult_Act ? it.Ses_Ult_Act.substring(11, 16) : '--:--';

			var ipUbi = it.Ses_Ip || 'Desconocida';
			if (it.Ses_Ubi) {
				ipUbi += '<br><small class="text-muted"><i class="fa fa-map-marker"></i> ' + it.Ses_Ubi + '</small>';
			}

			var navOs = (it.Ses_Nav || 'Desconocido');

			html += '<tr>';
			html += '  <td>';
			html += '    <div style="display: flex; align-items: center; gap: 10px;">';
			html += '      <div class="user-avatar-badge">' + inicial + '<span class="status-dot ' + semaforo + '"></span></div>';
			html += '      <div>';
			html += '        <strong style="color: #0f172a;">' + it.NombreCompleto + '</strong>';
			html += '        <div style="font-size: 11px; color: #64748b;">' + (it.Usu_Nom || '') + (it.Usu_Ced ? ' &bull; ' + it.Usu_Ced : '') + '</div>';
			html += '      </div>';
			html += '    </div>';
			html += '  </td>';
			html += '  <td><span style="font-size: 12px; color: #334155;">' + (it.Perfiles_Desc || 'Sin perfil') + '</span></td>';
			html += '  <td>' + ipUbi + '</td>';
			html += '  <td><small style="color: #475569;">' + navOs + '</small></td>';
			html += '  <td><strong style="color: #334155;">' + relativo + '</strong><br><small class="text-muted">' + horaActividad + '</small></td>';
			html += '  <td><span class="badge" style="background: #e0e7ff; color: #3730a3; font-weight: 700;">' + (it.TiempoFormateado || '0m') + '</span></td>';
			html += '  <td><span class="badge-custom ' + badgeClase + '"><i class="fa fa-circle" style="font-size: 8px;"></i> ' + it.BadgeTexto + '</span></td>';

			if (esAdmin) {
				html += '  <td style="text-align: right;">';
				if (semaforo === 'en_linea' || semaforo === 'ausente') {
					html += '    <button type="button" class="btn-kick" onclick="confirmarExpulsion(' + it.Ses_Cod + ', \'' + (it.NombreCompleto || '').replace(/'/g, "\\'") + '\')">';
					html += '      <i class="fa fa-ban"></i> Desconectar';
					html += '    </button>';
				} else {
					html += '    <span class="text-muted" style="font-size: 11px;">Inactivo</span>';
				}
				html += '  </td>';
			}

			html += '</tr>';
		});

		$('#tbodyActividad').html(html);
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

	window.confirmarExpulsion = function (sesCod, usuNom) {
		$('#kickSesCod').val(sesCod);
		$('#kickUsuNom').text(usuNom);
		$('#modalConfirmarExpulsion').modal('show');
	};

	$('#btnEjecutarExpulsion').on('click', function () {
		var sesCod = $('#kickSesCod').val();
		if (!sesCod || sesCod === '0') return;

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
