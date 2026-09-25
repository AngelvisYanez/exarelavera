/**
 * Personalizador de widgets/KPIs del Tablero Analitico Comparativo.
 * Depende de jQuery + jQuery UI Sortable ya cargados en la pagina.
 */
(function (window, $) {
	'use strict';

	var cmpEmp = window.audCmpEmp || 0;
	var cmpUsu = window.audCmpUsu || 0;
	var cmpCharts = window.audCmpCharts || {};
	var cmpLastKpis = [];
	var cmpWidgetOrdenDef = ['tendencia', 'modulos', 'eventos', 'horarios', 'sesiones', 'usuarios', 'plantas', 'variacion', 'mixOps', 'tablaMod', 'tablaUsu'];
	var cmpWidgetTamanosDef = {
		tendencia: 'w-full', modulos: 'w-12', eventos: 'w-12', horarios: 'w-12', sesiones: 'w-12',
		usuarios: 'w-12', plantas: 'w-12', variacion: 'w-12', mixOps: 'w-12', tablaMod: 'w-12', tablaUsu: 'w-12'
	};
	var cmpKpiDefKeys = ['total_movimientos', 'inserciones', 'modificaciones', 'eliminaciones', 'sesiones_totales', 'usuarios_unicos'];

	function k(pref) { return 'exa_aud_cmp_' + pref + '_' + cmpEmp + '_' + cmpUsu; }
	function lsGet(key, fallback) {
		try {
			var raw = localStorage.getItem(key);
			if (raw === null || raw === undefined) return fallback;
			return JSON.parse(raw);
		} catch (e) { return fallback; }
	}
	function lsSet(key, val) {
		try { localStorage.setItem(key, JSON.stringify(val)); } catch (e) {}
	}
	function lsDel(key) {
		try { localStorage.removeItem(key); } catch (e) {}
	}

	function cmpSortableRefresh($solo) {
		if (!$.fn.sortable) return;
		($solo && $solo.length ? $solo : $('#cmpWidgetGrid, #cmpKpiGrid, #cmpKpisSorter, #cmpWidgetsSorter')).each(function () {
			var $el = $(this);
			if ($el.hasClass('ui-sortable')) {
				try { $el.sortable('refresh'); } catch (e) {}
			}
		});
	}

	function cmpSortableInit($el, opts) {
		if (!$el.length || !$.fn.sortable) return;
		if ($el.hasClass('ui-sortable')) {
			try { $el.sortable('destroy'); } catch (e) {}
		}
		$el.sortable(opts);
	}

	function cmpRedimensionar() {
		setTimeout(function () {
			for (var id in cmpCharts) {
				if (cmpCharts[id] && typeof cmpCharts[id].resize === 'function') {
					try { cmpCharts[id].resize(); } catch (e) {}
				}
			}
			if (typeof window.audCmpResizeCharts === 'function') window.audCmpResizeCharts();
		}, 40);
	}

	/* ---------- Widgets ---------- */
	function cmpWidgetOrdenGuardar() {
		var ids = [];
		$('#cmpWidgetGrid .dash-widget').each(function () { ids.push($(this).attr('data-widget')); });
		lsSet(k('orden'), ids);
	}

	function cmpWidgetOcultosLeer() {
		var v = lsGet(k('ocultos'), []);
		return ($.isArray(v) ? v : []);
	}

	function cmpWidgetOcultosGuardar() {
		var ocultos = [];
		$('#cmpWidgetOcultos .dash-widget').each(function () { ocultos.push($(this).attr('data-widget')); });
		lsSet(k('ocultos'), ocultos);
	}

	function cmpTamanosLeer() {
		var v = lsGet(k('tamanos'), {});
		return (v && typeof v === 'object') ? v : {};
	}

	function cmpTamanosAplicar() {
		var saved = cmpTamanosLeer();
		$('.dash-widget').each(function () {
			var id = $(this).attr('data-widget');
			var size = saved[id] || cmpWidgetTamanosDef[id] || 'w-12';
			if (size !== 'w-full') size = 'w-12';
			$(this).removeClass('w-full w-12').addClass(size);
			var $i = $(this).find('.dash-size').first();
			if ($i.length) {
				if (size === 'w-full') $i.removeClass('fa-expand').addClass('fa-compress');
				else $i.removeClass('fa-compress').addClass('fa-expand');
			}
		});
	}

	function cmpWidgetOrdenCanonico() {
		var saved = lsGet(k('orden'), null);
		var existentes = {};
		$('#cmpWidgetGrid .dash-widget, #cmpWidgetOcultos .dash-widget').each(function () {
			existentes[$(this).attr('data-widget')] = 1;
		});
		var orden = [], seen = {}, fuente = ($.isArray(saved) && saved.length) ? saved : cmpWidgetOrdenDef;
		var i, id;
		for (i = 0; i < fuente.length; i++) {
			id = fuente[i];
			if (existentes[id] && !seen[id]) { orden.push(id); seen[id] = 1; }
		}
		for (i = 0; i < cmpWidgetOrdenDef.length; i++) {
			id = cmpWidgetOrdenDef[i];
			if (existentes[id] && !seen[id]) { orden.push(id); seen[id] = 1; }
		}
		return orden;
	}

	function cmpWidgetAplicar() {
		var $grid = $('#cmpWidgetGrid');
		var ocultos = cmpWidgetOcultosLeer();
		var orden = cmpWidgetOrdenCanonico();
		var todos = {};
		$($grid.find('.dash-widget').get().concat($('#cmpWidgetOcultos .dash-widget').get())).each(function () {
			todos[$(this).attr('data-widget')] = $(this);
		});
		$grid.find('.dash-widget').detach();
		$('#cmpWidgetOcultos .dash-widget').detach();
		for (var i = 0; i < orden.length; i++) {
			if (!todos[orden[i]]) continue;
			if (ocultos.indexOf(orden[i]) !== -1) $('#cmpWidgetOcultos').append(todos[orden[i]]);
			else $grid.append(todos[orden[i]]);
		}
		$grid.find('.dash-widget').css({ display: '', float: 'left', position: '', top: '', left: '', width: '' });
		cmpTamanosAplicar();
		cmpSortableRefresh($('#cmpWidgetGrid'));
		cmpRedimensionar();
	}

	function cmpWidgetOcultar(id) {
		var $w = $('.dash-widget[data-widget="' + id + '"]');
		$('#cmpWidgetOcultos').append($w);
		cmpWidgetOrdenGuardar();
		cmpWidgetOcultosGuardar();
	}

	/* ---------- KPIs ---------- */
	function cmpKpisDefecto(all) {
		if (!all || !all.length) return cmpKpiDefKeys.slice();
		var out = [], i, j;
		for (i = 0; i < cmpKpiDefKeys.length; i++) {
			for (j = 0; j < all.length; j++) {
				if (all[j].clave === cmpKpiDefKeys[i]) { out.push(all[j].clave); break; }
			}
		}
		if (!out.length) {
			for (j = 0; j < Math.min(6, all.length); j++) out.push(all[j].clave);
		}
		return out;
	}

	function cmpKpisLeer(all) {
		var raw = null;
		try { raw = localStorage.getItem(k('kpis')); } catch (e) {}
		if (raw === null || raw === undefined) return cmpKpisDefecto(all);
		try {
			var v = JSON.parse(raw);
			if (!$.isArray(v)) return cmpKpisDefecto(all);
			var map = {}, valid = [], i;
			for (i = 0; i < (all || []).length; i++) map[all[i].clave] = all[i];
			for (i = 0; i < v.length; i++) if (map[v[i]]) valid.push(v[i]);
			return valid;
		} catch (e2) { return cmpKpisDefecto(all); }
	}

	function cmpKpiBuscar(all, clave) {
		for (var i = 0; i < all.length; i++) if (all[i].clave === clave) return all[i];
		return null;
	}

	window.audCmpRenderKPIs = function (kpis, numFmt) {
		cmpLastKpis = kpis || [];
		numFmt = numFmt || function (x) { return x; };
		var ids = cmpKpisLeer(cmpLastKpis);
		var html = '';
		for (var i = 0; i < ids.length; i++) {
			var kitem = cmpKpiBuscar(cmpLastKpis, ids[i]);
			if (!kitem) continue;
			var pct = (typeof kitem.pct_cambio === 'number' && !isNaN(kitem.pct_cambio)) ? kitem.pct_cambio : null;
			var favor = kitem.favorable_subida !== false;
			var badgeClass = 'badge-neutral', icono = 'fa-minus', txtPct = 'N/D';
			if (pct === 0) txtPct = '0%';
			else if (pct !== null) {
				var sube = pct > 0;
				var bueno = favor ? sube : !sube;
				badgeClass = bueno ? 'badge-up' : 'badge-down';
				icono = sube ? 'fa-arrow-up' : 'fa-arrow-down';
				txtPct = (sube ? '+' : '') + pct.toFixed(1) + '%';
			}
			var borderCol = '#2563eb';
			if (kitem.color === 'success') borderCol = '#10b981';
			else if (kitem.color === 'warning') borderCol = '#f59e0b';
			else if (kitem.color === 'danger') borderCol = '#ef4444';
			else if (kitem.color === 'info') borderCol = '#0284c7';
			else if (kitem.color === 'purple') borderCol = '#8b5cf6';
			else if (kitem.color === 'orange') borderCol = '#ea580c';

			html += '<div class="cmp-kpi-wrap" data-kpi="' + kitem.clave + '">';
			html += '<div class="aud-cmp-kpi" style="border-top-color:' + borderCol + ';" title="' + (kitem.titulo || '') + '">';
			html += '<i class="fa fa-eye-slash kpi-hide" title="Ocultar metrica"></i>';
			html += '<div class="kpi-title"><i class="fa ' + kitem.icono + '"></i> <span>' + kitem.titulo + '</span></div>';
			html += '<div class="kpi-values-row"><div class="kpi-main-val">' + numFmt(kitem.valor_b) + '</div>';
			html += '<span class="kpi-badge ' + badgeClass + '"><i class="fa ' + icono + '"></i> ' + txtPct + '</span></div>';
			html += '<div class="kpi-compare">';
			html += '<div><span class="kpi-tag a">A</span><strong>' + numFmt(kitem.valor_a) + '</strong><span class="kpi-rate">' + numFmt(kitem.promedio_diario_a, 1) + '/dia</span></div>';
			html += '<div><span class="kpi-tag b">B</span><strong>' + numFmt(kitem.valor_b) + '</strong><span class="kpi-rate">' + numFmt(kitem.promedio_diario_b, 1) + '/dia</span></div>';
			html += '</div></div></div>';
		}
		$('#cmpKpiGrid').html(html || '<div class="text-muted" style="font-size:12px;padding:8px 6px;clear:both;">No hay metricas visibles. Use Personalizar para mostrarlas.</div>');
		cmpSortableRefresh($('#cmpKpiGrid'));
	};

	function cmpKpisGuardarDesdeGrid() {
		var ids = [];
		$('#cmpKpiGrid .cmp-kpi-wrap').each(function () { ids.push($(this).attr('data-kpi')); });
		lsSet(k('kpis'), ids);
	}

	function cmpKpisQuitar(id) {
		var ids = cmpKpisLeer(cmpLastKpis);
		var i = ids.indexOf(id);
		if (i !== -1) ids.splice(i, 1);
		lsSet(k('kpis'), ids);
		if (typeof window.audCmpNumFmt === 'function') window.audCmpRenderKPIs(cmpLastKpis, window.audCmpNumFmt);
		else window.audCmpRenderKPIs(cmpLastKpis);
	}

	/* ---------- Modal listas ---------- */
	function cmpSbox(id, titulo, ico, oculto) {
		return '<div class="aud-sbox' + (oculto ? ' aud-sbox-off' : '') + '" data-id="' + id + '">'
			+ '<i class="fa ' + (oculto ? 'fa-eye-slash' : 'fa-eye') + ' kbox-eye" title="Mostrar/ocultar"></i>'
			+ '<i class="fa fa-' + ico + ' kbox-ico"></i>'
			+ '<span class="kbox-txt">' + titulo + '</span></div>';
	}

	function cmpConstruirListas() {
		var ocultos = cmpWidgetOcultosLeer();
		var orden = cmpWidgetOrdenCanonico();
		var whtml = '';
		for (var i = 0; i < orden.length; i++) {
			var $w = $('#cmpWidgetGrid .dash-widget[data-widget="' + orden[i] + '"], #cmpWidgetOcultos .dash-widget[data-widget="' + orden[i] + '"]').first();
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
			whtml += cmpSbox(orden[i], titulo, ico, ocultos.indexOf(orden[i]) !== -1);
		}
		$('#cmpWidgetsSorter').html(whtml || '<p class="text-muted">Sin widgets.</p>');

		var ids = cmpKpisLeer(cmpLastKpis);
		var canon = ids.slice();
		for (var j = 0; j < cmpLastKpis.length; j++) {
			if (canon.indexOf(cmpLastKpis[j].clave) === -1) canon.push(cmpLastKpis[j].clave);
		}
		var khtml = '';
		for (var n = 0; n < canon.length; n++) {
			var item = cmpKpiBuscar(cmpLastKpis, canon[n]);
			if (!item) continue;
			var kico = (item.icono || 'fa-circle').replace(/^fa-/, '');
			khtml += cmpSbox(item.clave, item.titulo, kico, ids.indexOf(item.clave) === -1);
		}
		$('#cmpKpisSorter').html(khtml || '<p class="text-muted">Sin metricas.</p>');
		cmpSortableRefresh($('#cmpKpisSorter, #cmpWidgetsSorter'));
	}

	function cmpModalAbrir() {
		var $m = $('#modalWidgetsCmp');
		$('.modal-backdrop').remove();
		$('<div class="modal-backdrop fade in"></div>').appendTo('body');
		$m.addClass('in').css('display', 'block');
		window.audCmpModalAbierto = true;
		cmpConstruirListas();
	}

	function cmpModalCerrar() {
		$('#modalWidgetsCmp').removeClass('in').css('display', 'none');
		$('.modal-backdrop').remove();
		window.audCmpModalAbierto = false;
	}

	function cmpPersonalizacionGuardar() {
		var kpiIds = [];
		$('#cmpKpisSorter .aud-sbox').each(function () {
			if (!$(this).hasClass('aud-sbox-off')) kpiIds.push($(this).attr('data-id'));
		});
		lsSet(k('kpis'), kpiIds);

		var wOrden = [], wOcultos = [];
		$('#cmpWidgetsSorter .aud-sbox').each(function () {
			var id = $(this).attr('data-id');
			if (!id) return;
			wOrden.push(id);
			if ($(this).hasClass('aud-sbox-off')) wOcultos.push(id);
		});
		for (var i = 0; i < cmpWidgetOrdenDef.length; i++) {
			if (wOrden.indexOf(cmpWidgetOrdenDef[i]) === -1) wOrden.push(cmpWidgetOrdenDef[i]);
		}
		lsSet(k('orden'), wOrden);
		lsSet(k('ocultos'), wOcultos);
		cmpWidgetAplicar();
		if (typeof window.audCmpNumFmt === 'function') window.audCmpRenderKPIs(cmpLastKpis, window.audCmpNumFmt);
		else window.audCmpRenderKPIs(cmpLastKpis);
	}

	function cmpRestablecer(desdeModal) {
		lsDel(k('orden')); lsDel(k('ocultos')); lsDel(k('tamanos')); lsDel(k('kpis'));
		lsSet(k('orden'), cmpWidgetOrdenDef.slice());
		lsSet(k('ocultos'), []);
		lsSet(k('kpis'), cmpKpisDefecto(cmpLastKpis));
		cmpWidgetAplicar();
		if (typeof window.audCmpNumFmt === 'function') window.audCmpRenderKPIs(cmpLastKpis, window.audCmpNumFmt);
		else window.audCmpRenderKPIs(cmpLastKpis);
		if (desdeModal || window.audCmpModalAbierto) cmpConstruirListas();
	}

	function cmpIniciarSortables() {
		cmpSortableInit($('#cmpWidgetGrid'), {
			items: '> .dash-widget',
			handle: '.dash-widget-head',
			cancel: '.dash-hide, .dash-size, a, button, input',
			placeholder: 'dash-widget-placeholder',
			forcePlaceholderSize: true,
			tolerance: 'pointer',
			opacity: 0.92,
			zIndex: 10050,
			distance: 4,
			start: function (e, ui) {
				$('body').addClass('aud-cmp-sorting');
				ui.placeholder.removeClass('w-full w-12').addClass(ui.item.hasClass('w-full') ? 'w-full' : 'w-12');
				ui.placeholder.css({ width: ui.item.outerWidth(), height: Math.max(90, ui.item.outerHeight()), visibility: 'visible' });
				ui.item.css('width', ui.item.outerWidth());
			},
			stop: function (e, ui) {
				$('body').removeClass('aud-cmp-sorting');
				ui.item.css({ width: '', height: '', position: '', top: '', left: '' });
				cmpWidgetOrdenGuardar();
				cmpRedimensionar();
			}
		});

		cmpSortableInit($('#cmpKpiGrid'), {
			items: '> .cmp-kpi-wrap',
			handle: '.aud-cmp-kpi',
			cancel: '.kpi-hide, a, button',
			placeholder: 'cmp-kpi-placeholder',
			forcePlaceholderSize: true,
			tolerance: 'pointer',
			opacity: 0.92,
			distance: 4,
			start: function (e, ui) {
				$('body').addClass('aud-cmp-sorting');
				ui.placeholder.css({ width: ui.item.outerWidth(), height: Math.max(90, ui.item.outerHeight()), visibility: 'visible' });
				ui.item.css('width', ui.item.outerWidth());
			},
			stop: function (e, ui) {
				$('body').removeClass('aud-cmp-sorting');
				ui.item.css({ width: '', height: '', position: '', top: '', left: '' });
				cmpKpisGuardarDesdeGrid();
			}
		});

		var sorterOpts = {
			items: '> .aud-sbox',
			cancel: '.kbox-eye, a, button',
			placeholder: 'aud-sbox-placeholder',
			forcePlaceholderSize: true,
			tolerance: 'pointer',
			opacity: 0.92,
			zIndex: 10060,
			distance: 3,
			appendTo: 'body',
			helper: 'clone',
			start: function (e, ui) {
				$('body').addClass('aud-cmp-sorting');
				ui.placeholder.css({ width: ui.item.outerWidth(), height: ui.item.outerHeight(), visibility: 'visible' });
				ui.helper.css({ width: ui.item.outerWidth(), 'z-index': 10060 });
			},
			stop: function () { $('body').removeClass('aud-cmp-sorting'); }
		};
		cmpSortableInit($('#cmpKpisSorter'), sorterOpts);
		cmpSortableInit($('#cmpWidgetsSorter'), sorterOpts);
	}

	$(document).on('mousedown', '#cmpWidgetGrid .dash-hide, #cmpWidgetGrid .dash-size, #cmpKpiGrid .kpi-hide', function (e) {
		e.stopPropagation();
	});
	$(document).on('click', '#cmpWidgetGrid .dash-hide', function (e) {
		e.preventDefault(); e.stopPropagation();
		cmpWidgetOcultar($(this).closest('.dash-widget').attr('data-widget'));
	});
	$(document).on('click', '#cmpWidgetGrid .dash-size', function (e) {
		e.preventDefault(); e.stopPropagation();
		var $w = $(this).closest('.dash-widget');
		var id = $w.attr('data-widget');
		var size = $w.hasClass('w-full') ? 'w-12' : 'w-full';
		$w.removeClass('w-full w-12').addClass(size);
		var saved = cmpTamanosLeer();
		saved[id] = size;
		lsSet(k('tamanos'), saved);
		cmpTamanosAplicar();
		cmpRedimensionar();
	});
	$(document).on('click', '#cmpKpiGrid .kpi-hide', function (e) {
		e.preventDefault(); e.stopPropagation();
		cmpKpisQuitar($(this).closest('.cmp-kpi-wrap').attr('data-kpi'));
	});
	$(document).on('mousedown', '#modalWidgetsCmp .kbox-eye', function (e) { e.stopPropagation(); });
	$(document).on('click', '#modalWidgetsCmp .kbox-eye', function (e) {
		e.preventDefault(); e.stopPropagation();
		var $b = $(this).closest('.aud-sbox');
		$b.toggleClass('aud-sbox-off');
		var off = $b.hasClass('aud-sbox-off');
		$b.find('.kbox-eye').attr('class', 'fa ' + (off ? 'fa-eye-slash' : 'fa-eye') + ' kbox-eye');
	});

	$('#btnCfgWidgetsCmp').on('click', function () { cmpModalAbrir(); });
	$('#btnCerrarWidgetsCmp, #btnCerrarWidgetsCmp2').on('click', function () { cmpModalCerrar(); });
	$('#btnGuardarWidgetsCmp').on('click', function () {
		cmpPersonalizacionGuardar();
		cmpModalCerrar();
	});
	$('#btnRestablecerWidgetsCmp').on('click', function () {
		if (!window.confirm('Se restablecera el orden, la visibilidad y el tamano de metricas y widgets. Continuar?')) return;
		cmpRestablecer(true);
	});
	$('#btnResetWidgetsCmp').on('click', function () { cmpRestablecer(false); });

	window.audCmpWidgetsReady = function () {
		cmpEmp = window.audCmpEmp || 0;
		cmpUsu = window.audCmpUsu || 0;
		cmpCharts = window.audCmpCharts || {};
		cmpWidgetAplicar();
		cmpIniciarSortables();
	};
})(window, window.jQuery);
