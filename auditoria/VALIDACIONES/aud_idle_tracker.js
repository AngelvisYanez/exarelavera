/**
 * Monitor de Inactividad y Heartbeat de Sesión en Tiempo Real.
 * Detecta actividad (teclado, ratón, scroll) en la ventana e iframes.
 * Carga tiempos y textos desde Configuración de monitoreo (cfg_inactividad).
 * Mantiene heartbeat para el monitor en vivo y expulsión forzada por admin.
 *
 * @package auditoria.VALIDACIONES
 */
(function (window, document, $) {
	'use strict';

	if (!$) return;
	/* Solo en ventana top: cada iframe con jqgrid5 no debe abrir otro heartbeat
	 * (satura PHP-FPM y los procesos del menu dejan de cargar). */
	try {
		if (window.top && window.top !== window) {
			return;
		}
	} catch (eTop) {
		/* cross-origin: seguir en este frame */
	}
	/* Evitar doble arranque (home + procesos con jqgrid) */
	if (window.__AUD_IDLE_STARTED) return;
	window.__AUD_IDLE_STARTED = true;

	var cfg = {
		activo: true,
		minutos: 15,
		advertencia_seg: 60,
		titulo: 'Advertencia de Inactividad',
		texto: 'Tu sesión ha permanecido inactiva por casi {minutos} minutos. Por seguridad, se cerrará automáticamente en {segundos} segundos si no se detecta actividad.',
		pregunta: '¿Sigues trabajando en el sistema?',
		plantilla: 'clasico'
	};

	var TIEMPO_INACTIVIDAD_TOTAL_MS = cfg.minutos * 60 * 1000;
	var TIEMPO_ADVERTENCIA_MS = cfg.advertencia_seg * 1000;
	var TIEMPO_DISPARO_MODAL_MS = TIEMPO_INACTIVIDAD_TOTAL_MS - TIEMPO_ADVERTENCIA_MS;
	/* Heartbeat: detectar expulsion del admin sin saturar FPM */
	var INTERVALO_HEARTBEAT_MS = 90 * 1000;
	var STORAGE_LOGOUT_KEY = 'aud_force_logout_v1';
	var saliendo = false;

	var ultimoMovimiento = Date.now();
	var modalAdvertenciaVisible = false;
	var intervaloCountdown = null;
	var intervaloIdleCheck = null;
	var tiempoRestanteSegundos = cfg.advertencia_seg;
	var heartbeatTimer = null;

	function metaIdle() {
		return (typeof window.AUD_IDLE_META === 'object' && window.AUD_IDLE_META) ? window.AUD_IDLE_META : {};
	}

	function resolveAppRoot() {
		var meta = metaIdle();
		if (meta.app_root) {
			return String(meta.app_root).replace(/\/$/, '');
		}
		var path = window.location.pathname || '/';
		var m = path.match(/^(.*)\/(administrador|auditoria|relavera|facturacion|tesoreria|mascaras|Librerias|skins|framework)\//i);
		if (m) {
			return window.location.origin + (m[1] || '');
		}
		return window.location.origin;
	}

	function resolveLoginUrl(motivo) {
		var meta = metaIdle();
		var base = meta.login_url ? String(meta.login_url) : (resolveAppRoot() + '/index.php');
		base = base.split('?')[0];
		var q = motivo ? ('?motivo=' + encodeURIComponent(motivo)) : '';
		return base + q;
	}

	function resolveEndpoint() {
		var meta = metaIdle();
		if (meta.endpoint) {
			return String(meta.endpoint);
		}
		if (typeof ace !== 'undefined' && ace.vars && ace.vars.base) {
			return ace.vars.base + '/../auditoria/LOGICA/aud_log_actividad_sesion.php';
		}
		return resolveAppRoot() + '/auditoria/LOGICA/aud_log_actividad_sesion.php';
	}

	var auditoriaEndpoint = resolveEndpoint();

	function targetWindow() {
		try {
			if (window.top && window.top.location) {
				return window.top;
			}
		} catch (e) {}
		return window;
	}

	/**
	 * Cierra sesion PHP (si aplica) y manda TODA la ventana/pestaña al index.
	 */
	function forzarSalidaAlIndex(motivo, mensaje) {
		if (saliendo) return;
		saliendo = true;

		try {
			localStorage.setItem(STORAGE_LOGOUT_KEY, JSON.stringify({
				ts: Date.now(),
				motivo: motivo || 'expulsado',
				mensaje: mensaje || ''
			}));
		} catch (e) {}

		var loginUrl = resolveLoginUrl(motivo || 'expulsado');
		var meta = metaIdle();
		var payload = { action: 'ack_expulsion', motivo: motivo || 'expulsado' };
		if (meta.ses_cod) payload.ses_cod = meta.ses_cod;
		if (meta.usu_cod) payload.usu_cod = meta.usu_cod;

		var go = function () {
			try {
				targetWindow().location.replace(loginUrl);
			} catch (err) {
				try {
					window.location.replace(loginUrl);
				} catch (err2) {
					window.location.href = loginUrl;
				}
			}
		};

		$.ajax({
			url: resolveEndpoint(),
			type: 'POST',
			dataType: 'json',
			data: payload,
			timeout: 4000
		}).done(function (res) {
			if (res && res.redirect) {
				loginUrl = String(res.redirect);
			}
		}).always(go);

		/* Fallback duro por si el ajax se cuelga */
		setTimeout(go, 4500);
	}

	function escucharLogoutOtrasPestanas() {
		try {
			window.addEventListener('storage', function (ev) {
				if (!ev || ev.key !== STORAGE_LOGOUT_KEY || !ev.newValue) return;
				var data = null;
				try { data = JSON.parse(ev.newValue); } catch (e) { data = {}; }
				forzarSalidaAlIndex((data && data.motivo) || 'expulsado', (data && data.mensaje) || '');
			});
		} catch (e) {}
	}
	escucharLogoutOtrasPestanas();

	function aplicarConfig(nueva) {
		if (!nueva || typeof nueva !== 'object') return;
		if (typeof nueva.activo !== 'undefined') cfg.activo = !!nueva.activo;
		if (nueva.minutos) cfg.minutos = Math.max(2, parseInt(nueva.minutos, 10) || 15);
		if (nueva.advertencia_seg) cfg.advertencia_seg = Math.max(15, parseInt(nueva.advertencia_seg, 10) || 60);
		if (nueva.titulo) cfg.titulo = String(nueva.titulo);
		if (nueva.texto) cfg.texto = String(nueva.texto);
		if (nueva.pregunta) cfg.pregunta = String(nueva.pregunta);
		if (nueva.plantilla) {
			cfg.plantilla = String(nueva.plantilla).toLowerCase().replace(/[^a-z0-9_]/g, '') || 'clasico';
		}

		TIEMPO_INACTIVIDAD_TOTAL_MS = cfg.minutos * 60 * 1000;
		TIEMPO_ADVERTENCIA_MS = cfg.advertencia_seg * 1000;
		if (TIEMPO_ADVERTENCIA_MS >= TIEMPO_INACTIVIDAD_TOTAL_MS) {
			TIEMPO_ADVERTENCIA_MS = Math.min(60 * 1000, Math.max(15 * 1000, TIEMPO_INACTIVIDAD_TOTAL_MS - 30000));
			cfg.advertencia_seg = Math.round(TIEMPO_ADVERTENCIA_MS / 1000);
		}
		TIEMPO_DISPARO_MODAL_MS = Math.max(30 * 1000, TIEMPO_INACTIVIDAD_TOTAL_MS - TIEMPO_ADVERTENCIA_MS);
		aplicarPlantillaAlModal();
	}

	function aplicarPlantillaAlModal() {
		var $m = $('#modalInactividadAuditoria');
		if (!$m.length) return;
		$m.removeClass(function (i, c) {
			return (c.match(/(^|\s)aud-idle-tpl--\S+/g) || []).join(' ');
		});
		$m.addClass('aud-idle-tpl--' + (cfg.plantilla || 'clasico'));
	}

	function textoConPlaceholders(tpl, segs) {
		return String(tpl || '')
			.replace(/\{minutos\}/g, String(cfg.minutos))
			.replace(/\{segundos\}/g, String(segs != null ? segs : cfg.advertencia_seg));
	}

	function inyectarModalInactividad() {
		if (document.getElementById('modalInactividadAuditoria')) {
			aplicarPlantillaAlModal();
			actualizarTextosModal(cfg.advertencia_seg);
			return;
		}

		var tplClass = 'aud-idle-tpl--' + (cfg.plantilla || 'clasico');
		var html = [
			'<div id="modalInactividadAuditoria" class="modal fade aud-idle-modal m4-modal ' + tplClass + '" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="audIdleTitulo">',
			'  <div class="modal-dialog modal-dialog-centered">',
			'    <div class="modal-content">',
			'      <div class="modal-header aud-idle-header aud-idle-header--warning m4-modal-header">',
			'        <h4 class="modal-title m4-modal-title" id="audIdleTitulo">',
			'          <i class="ace-icon fa fa-hourglass-half"></i> <span id="audIdleTituloTxt"></span>',
			'        </h4>',
			'        <div class="aud-idle-badge"><span class="aud-idle-badge-dot"></span> Sesión por cerrar</div>',
			'      </div>',
			'      <div class="modal-body aud-idle-body m4-modal-body">',
			'        <div class="aud-idle-ring-wrap" id="audIdleRingWrap">',
			'          <svg class="aud-idle-ring" viewBox="0 0 108 108" aria-hidden="true">',
			'            <circle class="aud-idle-ring-bg" cx="54" cy="54" r="48"></circle>',
			'            <circle id="audIdleRingFg" class="aud-idle-ring-fg" cx="54" cy="54" r="48"></circle>',
			'          </svg>',
			'          <div class="aud-idle-countdown-core">',
			'            <span id="audIdleCountSeconds" class="aud-idle-countdown-num m4-countdown-num">60</span>',
			'            <span class="aud-idle-countdown-unit">seg</span>',
			'          </div>',
			'        </div>',
			'        <p id="audIdlePregunta" class="aud-idle-pregunta"></p>',
			'        <p id="audIdleTexto" class="aud-idle-texto"></p>',
			'      </div>',
			'      <div class="modal-footer aud-idle-footer m4-modal-footer">',
			'        <button type="button" id="btnAudCerrarSesionAhora" class="btn btn-default btn-sm aud-idle-btn aud-idle-btn-ghost m4-btn m4-btn-ghost">',
			'          <i class="fa fa-sign-out"></i> Salir ahora',
			'        </button>',
			'        <button type="button" id="btnAudContinuarSesion" class="btn btn-primary btn-sm aud-idle-btn aud-idle-btn-primary m4-btn m4-btn-primary">',
			'          <i class="fa fa-check"></i> Continuar trabajando',
			'        </button>',
			'      </div>',
			'    </div>',
			'  </div>',
			'</div>'
		].join('\n');

		$('body').append(html);

		$('#btnAudContinuarSesion').on('click', function () {
			reiniciarActividad();
			enviarHeartbeat();
		});

		$('#btnAudCerrarSesionAhora').on('click', function () {
			cerrarSesionPorInactividad();
		});

		actualizarTextosModal(cfg.advertencia_seg);
	}

	var AUD_IDLE_RING_LEN = 301.6; /* 2 * PI * 48 */

	function actualizarAnilloCountdown(segs) {
		var total = Math.max(1, parseInt(cfg.advertencia_seg, 10) || 60);
		var left = Math.max(0, parseInt(segs, 10) || 0);
		var pct = left / total;
		var offset = AUD_IDLE_RING_LEN * (1 - pct);
		var $fg = $('#audIdleRingFg');
		if ($fg.length) {
			$fg.css('stroke-dashoffset', offset);
		}
		var $wrap = $('#audIdleRingWrap');
		if ($wrap.length) {
			$wrap.toggleClass('is-urgent', left <= Math.max(10, Math.floor(total * 0.25)));
		}
	}

	function actualizarTextosModal(segs) {
		$('#audIdleTituloTxt').text(cfg.titulo || 'Advertencia de Inactividad');
		$('#audIdlePregunta').text(cfg.pregunta || '¿Sigues trabajando en el sistema?');
		var cuerpo = textoConPlaceholders(cfg.texto, segs);
		// Si el texto no menciona el contador, anexar etiqueta dinámica
		if (cuerpo.indexOf(String(segs)) === -1 && cuerpo.indexOf('{segundos}') === -1) {
			cuerpo += ' <strong id="audIdleCountLabel" class="aud-idle-count-label">' + segs + ' segundos</strong>.';
			$('#audIdleTexto').html(cuerpo);
		} else {
			$('#audIdleTexto').html(cuerpo.replace(
				new RegExp('(' + segs + '\\s*segundos?)', 'i'),
				'<strong id="audIdleCountLabel" class="aud-idle-count-label">$1</strong>'
			));
			if (!$('#audIdleCountLabel').length) {
				$('#audIdleTexto').append(' <strong id="audIdleCountLabel" class="aud-idle-count-label">' + segs + ' segundos</strong>');
			}
		}
		$('#audIdleCountSeconds').text(segs);
		actualizarAnilloCountdown(segs);
	}

	function mostrarModalExpulsionAdmin(mensaje) {
		if (modalAdvertenciaVisible) {
			$('#modalInactividadAuditoria').modal('hide');
		}

		if (document.getElementById('modalExpulsionAuditoria')) {
			$('#modalExpulsionAuditoria').modal('show');
			return;
		}

		var html = [
			'<div id="modalExpulsionAuditoria" class="modal fade aud-idle-modal m4-modal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog">',
			'  <div class="modal-dialog modal-dialog-centered">',
			'    <div class="modal-content">',
			'      <div class="modal-header aud-idle-header aud-idle-header--danger m4-modal-header m4-modal-header--danger">',
			'        <h4 class="modal-title m4-modal-title">',
			'          <i class="fa fa-ban"></i> Sesión Finalizada',
			'        </h4>',
			'      </div>',
			'      <div class="modal-body aud-idle-body aud-idle-expulsion-body m4-modal-body">',
			'        <div class="aud-idle-expulsion-icon-wrap"><i class="fa fa-lock aud-idle-expulsion-icon"></i></div>',
			'        <p class="aud-idle-expulsion-title">',
			mensaje || 'Su sesión ha sido finalizada por el Administrador de Sistemas.',
			'        </p>',
			'        <p class="aud-idle-expulsion-sub">Redirigiendo a la pantalla de inicio de sesión...</p>',
			'        <div class="aud-idle-expulsion-bar"><span></span></div>',
			'      </div>',
			'    </div>',
			'  </div>',
			'</div>'
		].join('\n');

		$('body').append(html);
		try {
			$('#modalExpulsionAuditoria').modal('show');
		} catch (e) {}

		setTimeout(function () {
			forzarSalidaAlIndex('expulsado', mensaje);
		}, 1200);
	}

	function registrarEventoInteraccion() {
		ultimoMovimiento = Date.now();
	}

	function engancharEventos(docTarget) {
		if (!docTarget) return;
		try {
			var eventos = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'];
			eventos.forEach(function (ev) {
				docTarget.addEventListener(ev, registrarEventoInteraccion, { passive: true, capture: true });
			});
		} catch (e) {}
	}

	function vigilarIframes() {
		$('iframe').each(function () {
			try {
				if (this.contentDocument) {
					engancharEventos(this.contentDocument);
				}
				$(this).off('load.audIdle').on('load.audIdle', function () {
					try {
						if (this.contentDocument) {
							engancharEventos(this.contentDocument);
						}
					} catch (err) {}
				});
			} catch (err) {}
		});
	}

	function activarModalAdvertencia() {
		if (modalAdvertenciaVisible || !cfg.activo) return;
		modalAdvertenciaVisible = true;
		tiempoRestanteSegundos = cfg.advertencia_seg;

		inyectarModalInactividad();
		actualizarTextosModal(tiempoRestanteSegundos);
		$('#modalInactividadAuditoria').modal('show');

		if (intervaloCountdown) clearInterval(intervaloCountdown);

		intervaloCountdown = setInterval(function () {
			tiempoRestanteSegundos--;
			if (tiempoRestanteSegundos <= 0) {
				clearInterval(intervaloCountdown);
				$('#modalInactividadAuditoria').modal('hide');
				cerrarSesionPorInactividad();
			} else {
				$('#audIdleCountSeconds').text(tiempoRestanteSegundos);
				actualizarTextosModal(tiempoRestanteSegundos);
			}
		}, 1000);
	}

	function reiniciarActividad() {
		modalAdvertenciaVisible = false;
		ultimoMovimiento = Date.now();
		if (intervaloCountdown) {
			clearInterval(intervaloCountdown);
			intervaloCountdown = null;
		}
		$('#modalInactividadAuditoria').modal('hide');
	}

	function cerrarSesionPorInactividad() {
		if (!cfg.activo) {
			reiniciarActividad();
			return;
		}
		if (intervaloCountdown) clearInterval(intervaloCountdown);
		if (heartbeatTimer) clearInterval(heartbeatTimer);
		if (intervaloIdleCheck) clearInterval(intervaloIdleCheck);

		var meta = metaIdle();
		var payload = { action: 'inactividad_timeout' };
		if (meta.ses_cod) payload.ses_cod = meta.ses_cod;
		if (meta.usu_cod) payload.usu_cod = meta.usu_cod;

		$.ajax({
			url: resolveEndpoint(),
			type: 'POST',
			dataType: 'json',
			data: payload,
			timeout: 8000
		}).always(function () {
			forzarSalidaAlIndex('inactividad', '');
		});
	}

	function enviarHeartbeat() {
		if (saliendo) return;
		var meta = metaIdle();
		var payload = { action: 'ping' };
		if (meta.ses_cod) payload.ses_cod = meta.ses_cod;
		if (meta.usu_cod) payload.usu_cod = meta.usu_cod;

		$.ajax({
			url: resolveEndpoint(),
			type: 'POST',
			dataType: 'json',
			data: payload,
			timeout: 5000,
			success: function (res) {
				if (res && res.forzar_logout) {
					var motivo = (res.motivo === 'inactividad') ? 'inactividad'
						: (res.motivo === 'cerrada' ? 'cerrada' : 'expulsado');
					mostrarModalExpulsionAdmin(res.mensaje);
					/* Asegurar salida aunque el modal falle (iframe / sin bootstrap) */
					setTimeout(function () {
						forzarSalidaAlIndex(motivo, res.mensaje || '');
					}, 1500);
				}
			}
		});
	}

	function iniciarChequeoIdle() {
		if (intervaloIdleCheck) {
			clearInterval(intervaloIdleCheck);
			intervaloIdleCheck = null;
		}
		if (!cfg.activo) return;

		intervaloIdleCheck = setInterval(function () {
			if (modalAdvertenciaVisible) return;
			var tiempoInactivo = Date.now() - ultimoMovimiento;
			if (tiempoInactivo >= TIEMPO_DISPARO_MODAL_MS) {
				activarModalAdvertencia();
			}
		}, 1000);
	}

	function cargarConfigYArrancar() {
		auditoriaEndpoint = resolveEndpoint();
		$.ajax({
			url: resolveEndpoint(),
			type: 'GET',
			dataType: 'json',
			data: { action: 'idle_config' },
			timeout: 6000
		}).done(function (res) {
			if (res && res.success && res.config) {
				aplicarConfig(res.config);
			}
		}).always(function () {
			iniciarCicloVerificacion();
		});
	}

	function iniciarCicloVerificacion() {
		engancharEventos(document);
		vigilarIframes();
		setInterval(vigilarIframes, 4000);

		iniciarChequeoIdle();

		/* Siempre hacer ping: aunque este ausente, debe detectar expulsion del admin */
		heartbeatTimer = setInterval(enviarHeartbeat, INTERVALO_HEARTBEAT_MS);
		setTimeout(enviarHeartbeat, 2000);
	}

	$(document).ready(function () {
		cargarConfigYArrancar();
	});

	// Exponer para pruebas / recarga manual desde consola
	window.AudIdleTracker = {
		getConfig: function () { return cfg; },
		reload: cargarConfigYArrancar,
		reset: reiniciarActividad
	};

})(window, document, window.jQuery);
