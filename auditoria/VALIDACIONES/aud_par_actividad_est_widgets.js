/**
 * Personalizador de widgets/KPIs — Estadisticas por Usuario (Actividad).
 */
(function (window, $) {
	'use strict';

	var estEmp = window.audEstEmp || 0;
	var estUsu = window.audEstUsu || 0;
	var ordenDef = ['tiempo', 'sesiones', 'mixCierres', 'promedio', 'tendencia', 'comparar', 'detalle'];
	var tamanosDef = {
		tiempo: 'w-12', sesiones: 'w-12', mixCierres: 'w-12', promedio: 'w-12',
		tendencia: 'w-full', comparar: 'w-full', detalle: 'w-full'
	};
	var kpiDef = ['iniciadas', 'cerradas', 'inactividad', 'forzadas', 'promedio', 'usuarios'];

	function k(p) { return 'exa_aud_est_usu_' + p + '_' + estEmp + '_' + estUsu; }
	function lsGet(key, fb) {
		try {
			var raw = localStorage.getItem(key);
			if (raw === null || raw === undefined) return fb;
			return JSON.parse(raw);
		} catch (e) { return fb; }
	}
	function lsSet(key, val) { try { localStorage.setItem(key, JSON.stringify(val)); } catch (e) {} }
	function lsDel(key) { try { localStorage.removeItem(key); } catch (e) {} }

	function refresh($solo) {
		if (!$.fn.sortable) return;
		($solo && $solo.length ? $solo : $('#estWidgetGrid, #estKpiGrid, #estKpisSorter, #estWidgetsSorter')).each(function () {
			var $el = $(this);
			if ($el.hasClass('ui-sortable')) { try { $el.sortable('refresh'); } catch (e) {} }
		});
	}

	function initSortable($el, opts) {
		if (!$el.length || !$.fn.sortable) return;
		if ($el.hasClass('ui-sortable')) { try { $el.sortable('destroy'); } catch (e) {} }
		$el.sortable(opts);
	}

	function resizeCharts() {
		if (typeof window.audEstResizeCharts === 'function') window.audEstResizeCharts();
	}

	function ordenCanonico() {
		var saved = lsGet(k('orden'), null);
		var existentes = {};
		$('#estWidgetGrid .dash-widget, #estWidgetOcultos .dash-widget').each(function () {
			existentes[$(this).attr('data-widget')] = 1;
		});
		var orden = [], seen = {}, fuente = ($.isArray(saved) && saved.length) ? saved : ordenDef;
		var i, id;
		for (i = 0; i < fuente.length; i++) {
			id = fuente[i];
			if (existentes[id] && !seen[id]) { orden.push(id); seen[id] = 1; }
		}
		for (i = 0; i < ordenDef.length; i++) {
			id = ordenDef[i];
			if (existentes[id] && !seen[id]) { orden.push(id); seen[id] = 1; }
		}
		return orden;
	}

	function ocultosLeer() {
		var v = lsGet(k('ocultos'), []);
		return $.isArray(v) ? v : [];
	}

	function tamanosAplicar() {
		var saved = lsGet(k('tamanos'), {}) || {};
		$('#estWidgetGrid .dash-widget, #estWidgetOcultos .dash-widget').each(function () {
			var id = $(this).attr('data-widget');
			var size = saved[id] || tamanosDef[id] || 'w-12';
			if (size !== 'w-full') size = 'w-12';
			$(this).removeClass('w-full w-12').addClass(size);
			var $i = $(this).find('.dash-size').first();
			if ($i.length) {
				if (size === 'w-full') $i.removeClass('fa-expand').addClass('fa-compress');
				else $i.removeClass('fa-compress').addClass('fa-expand');
			}
		});
	}

	function widgetAplicar() {
		var $grid = $('#estWidgetGrid');
		if (!$grid.length) return;
		var ocultos = ocultosLeer();
		var orden = ordenCanonico();
		var todos = {};
		$($grid.find('.dash-widget').get().concat($('#estWidgetOcultos .dash-widget').get())).each(function () {
			todos[$(this).attr('data-widget')] = $(this);
		});
		$grid.find('.dash-widget').detach();
		$('#estWidgetOcultos .dash-widget').detach();
		for (var i = 0; i < orden.length; i++) {
			if (!todos[orden[i]]) continue;
			if (ocultos.indexOf(orden[i]) !== -1) $('#estWidgetOcultos').append(todos[orden[i]]);
			else $grid.append(todos[orden[i]]);
		}
		$grid.find('.dash-widget').css({ display: '', float: 'left', position: '', top: '', left: '', width: '' });
		tamanosAplicar();
		refresh($('#estWidgetGrid'));
		resizeCharts();
	}

	function kpisLeer() {
		var raw = null;
		try { raw = localStorage.getItem(k('kpis')); } catch (e) {}
		if (raw === null || raw === undefined) return kpiDef.slice();
		try {
			var v = JSON.parse(raw);
			return $.isArray(v) ? v : kpiDef.slice();
		} catch (e2) { return kpiDef.slice(); }
	}

	window.audEstApplyKpiVisibility = function () {
		var ids = kpisLeer();
		$('#estKpiGrid .est-kpi-card').each(function () {
			var id = $(this).attr('data-kpi');
			$(this).toggle(ids.indexOf(id) !== -1);
		});
		refresh($('#estKpiGrid'));
	};

	function sbox(id, titulo, ico, off) {
		return '<div class="aud-sbox' + (off ? ' aud-sbox-off' : '') + '" data-id="' + id + '">'
			+ '<i class="fa ' + (off ? 'fa-eye-slash' : 'fa-eye') + ' kbox-eye"></i>'
			+ '<i class="fa fa-' + ico + ' kbox-ico"></i>'
			+ '<span class="kbox-txt">' + titulo + '</span></div>';
	}

	function construirListas() {
		var ocultos = ocultosLeer();
		var orden = ordenCanonico();
		var whtml = '';
		for (var i = 0; i < orden.length; i++) {
			var $w = $('#estWidgetGrid .dash-widget[data-widget="' + orden[i] + '"], #estWidgetOcultos .dash-widget[data-widget="' + orden[i] + '"]').first();
			if (!$w.length) continue;
			var titulo = $w.find('.dash-title').text().trim();
			var ico = 'th-large';
			var $ic = $w.find('.dash-title i').first();
			if ($ic.length) {
				var cls = ($ic.attr('class') || '').split(' ');
				for (var c = cls.length - 1; c >= 0; c--) {
					if (cls[c] && cls[c] !== 'fa') { ico = cls[c].replace(/^fa-/, ''); break; }
				}
			}
			whtml += sbox(orden[i], titulo, ico, ocultos.indexOf(orden[i]) !== -1);
		}
		$('#estWidgetsSorter').html(whtml || '<p class="text-muted">Sin widgets.</p>');

		var ids = kpisLeer();
		var kpisMeta = [
			{ id: 'iniciadas', t: 'Sesiones iniciadas', i: 'sign-in' },
			{ id: 'cerradas', t: 'Cerradas', i: 'sign-out' },
			{ id: 'inactividad', t: 'Por inactividad', i: 'clock-o' },
			{ id: 'forzadas', t: 'Forzadas', i: 'ban' },
			{ id: 'promedio', t: 'Min. promedio', i: 'hourglass-half' },
			{ id: 'usuarios', t: 'Usuarios', i: 'users' },
			{ id: 'minutos', t: 'Minutos totales', i: 'database' }
		];
		var canon = ids.slice();
		for (var j = 0; j < kpisMeta.length; j++) {
			if (canon.indexOf(kpisMeta[j].id) === -1) canon.push(kpisMeta[j].id);
		}
		var khtml = '';
		for (var n = 0; n < canon.length; n++) {
			var meta = null;
			for (var m = 0; m < kpisMeta.length; m++) if (kpisMeta[m].id === canon[n]) meta = kpisMeta[m];
			if (!meta) continue;
			khtml += sbox(meta.id, meta.t, meta.i, ids.indexOf(meta.id) === -1);
		}
		$('#estKpisSorter').html(khtml || '<p class="text-muted">Sin metricas.</p>');
		refresh($('#estKpisSorter, #estWidgetsSorter'));
	}

	function modalAbrir() {
		var $m = $('#modalWidgetsEst');
		$('.modal-backdrop').remove();
		$('<div class="modal-backdrop fade in"></div>').appendTo('body');
		$m.addClass('in').css('display', 'block');
		window.audEstModalAbierto = true;
		construirListas();
	}

	function modalCerrar() {
		$('#modalWidgetsEst').removeClass('in').css('display', 'none');
		$('.modal-backdrop').remove();
		window.audEstModalAbierto = false;
	}

	function guardar() {
		var kpiIds = [];
		$('#estKpisSorter .aud-sbox').each(function () {
			if (!$(this).hasClass('aud-sbox-off')) kpiIds.push($(this).attr('data-id'));
		});
		lsSet(k('kpis'), kpiIds);

		var wOrden = [], wOcultos = [];
		$('#estWidgetsSorter .aud-sbox').each(function () {
			var id = $(this).attr('data-id');
			if (!id) return;
			wOrden.push(id);
			if ($(this).hasClass('aud-sbox-off')) wOcultos.push(id);
		});
		for (var i = 0; i < ordenDef.length; i++) {
			if (wOrden.indexOf(ordenDef[i]) === -1) wOrden.push(ordenDef[i]);
		}
		lsSet(k('orden'), wOrden);
		lsSet(k('ocultos'), wOcultos);
		widgetAplicar();
		window.audEstApplyKpiVisibility();
	}

	function restablecer(desdeModal) {
		lsDel(k('orden')); lsDel(k('ocultos')); lsDel(k('tamanos')); lsDel(k('kpis'));
		lsSet(k('orden'), ordenDef.slice());
		lsSet(k('ocultos'), []);
		lsSet(k('kpis'), kpiDef.slice());
		widgetAplicar();
		window.audEstApplyKpiVisibility();
		if (desdeModal || window.audEstModalAbierto) construirListas();
	}

	function iniciarSortables() {
		initSortable($('#estWidgetGrid'), {
			items: '> .dash-widget',
			handle: '.dash-widget-head',
			cancel: '.dash-hide, .dash-size, a, button, input, select',
			placeholder: 'dash-widget-placeholder',
			forcePlaceholderSize: true,
			tolerance: 'pointer',
			opacity: 0.92,
			distance: 4,
			start: function (e, ui) {
				$('body').addClass('aud-est-sorting');
				ui.placeholder.removeClass('w-full w-12').addClass(ui.item.hasClass('w-full') ? 'w-full' : 'w-12');
				ui.placeholder.css({ width: ui.item.outerWidth(), height: Math.max(90, ui.item.outerHeight()), visibility: 'visible' });
				ui.item.css('width', ui.item.outerWidth());
			},
			stop: function (e, ui) {
				$('body').removeClass('aud-est-sorting');
				ui.item.css({ width: '', height: '', position: '', top: '', left: '' });
				var ids = [];
				$('#estWidgetGrid .dash-widget').each(function () { ids.push($(this).attr('data-widget')); });
				lsSet(k('orden'), ids);
				resizeCharts();
			}
		});

		initSortable($('#estKpiGrid'), {
			items: '> .est-kpi-card:visible',
			handle: '.aud-kpi-card',
			placeholder: 'est-kpi-placeholder',
			forcePlaceholderSize: true,
			tolerance: 'pointer',
			opacity: 0.92,
			distance: 4,
			stop: function () {
				var ids = [];
				$('#estKpiGrid .est-kpi-card:visible').each(function () { ids.push($(this).attr('data-kpi')); });
				lsSet(k('kpis'), ids);
			}
		});

		var so = {
			items: '> .aud-sbox',
			cancel: '.kbox-eye',
			placeholder: 'aud-sbox-placeholder',
			forcePlaceholderSize: true,
			tolerance: 'pointer',
			appendTo: 'body',
			helper: 'clone',
			opacity: 0.92,
			zIndex: 10060,
			start: function (e, ui) {
				ui.placeholder.css({ width: ui.item.outerWidth(), height: ui.item.outerHeight(), visibility: 'visible' });
				ui.helper.css({ width: ui.item.outerWidth(), 'z-index': 10060 });
			}
		};
		initSortable($('#estKpisSorter'), so);
		initSortable($('#estWidgetsSorter'), so);
	}

	$(document).on('click', '#estWidgetGrid .dash-hide', function (e) {
		e.preventDefault(); e.stopPropagation();
		var id = $(this).closest('.dash-widget').attr('data-widget');
		$('#estWidgetOcultos').append($('.dash-widget[data-widget="' + id + '"]'));
		var ids = [], ocultos = [];
		$('#estWidgetGrid .dash-widget').each(function () { ids.push($(this).attr('data-widget')); });
		$('#estWidgetOcultos .dash-widget').each(function () { ocultos.push($(this).attr('data-widget')); });
		lsSet(k('orden'), ids.concat(ocultos));
		lsSet(k('ocultos'), ocultos);
	});
	$(document).on('mousedown', '#estWidgetGrid .dash-hide, #estWidgetGrid .dash-size', function (e) { e.stopPropagation(); });
	$(document).on('click', '#estWidgetGrid .dash-size', function (e) {
		e.preventDefault(); e.stopPropagation();
		var $w = $(this).closest('.dash-widget');
		var id = $w.attr('data-widget');
		var size = $w.hasClass('w-full') ? 'w-12' : 'w-full';
		$w.removeClass('w-full w-12').addClass(size);
		var saved = lsGet(k('tamanos'), {}) || {};
		saved[id] = size;
		lsSet(k('tamanos'), saved);
		tamanosAplicar();
		resizeCharts();
	});
	$(document).on('mousedown', '#modalWidgetsEst .kbox-eye', function (e) { e.stopPropagation(); });
	$(document).on('click', '#modalWidgetsEst .kbox-eye', function (e) {
		e.preventDefault(); e.stopPropagation();
		var $b = $(this).closest('.aud-sbox');
		$b.toggleClass('aud-sbox-off');
		var off = $b.hasClass('aud-sbox-off');
		$b.find('.kbox-eye').attr('class', 'fa ' + (off ? 'fa-eye-slash' : 'fa-eye') + ' kbox-eye');
	});

	$('#btnCfgWidgetsEst').on('click', modalAbrir);
	$('#btnCerrarWidgetsEst, #btnCerrarWidgetsEst2').on('click', modalCerrar);
	$('#btnGuardarWidgetsEst').on('click', function () { guardar(); modalCerrar(); });
	$('#btnRestablecerWidgetsEst').on('click', function () {
		if (!window.confirm('Se restablecera el orden y la visibilidad. Continuar?')) return;
		restablecer(true);
	});
	$('#btnResetWidgetsEst').on('click', function () { restablecer(false); });

	window.audEstWidgetsReady = function () {
		estEmp = window.audEstEmp || 0;
		estUsu = window.audEstUsu || 0;
		widgetAplicar();
		window.audEstApplyKpiVisibility();
		iniciarSortables();
	};
})(window, window.jQuery);
