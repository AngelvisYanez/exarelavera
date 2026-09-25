/**
 * Model4 — componentes UI (filtros drawer móvil, tabs, toast)
 * Depende de jQuery. Clases m4-* en Librerias/tailwind.
 */
(function (window, document, $) {
	'use strict';
	if (!$) return;

	function moveFiltersIntoDrawer($root) {
		var $drawer = $root.find('.m4-filters-drawer').first();
		var $slot = $drawer.find('[data-m4-filters-slot]');
		var $source = $root.find('[data-m4-filters-source]').first();
		if (!$slot.length || !$source.length || $source.data('m4-in-drawer')) return;
		var $ph = $('<div class="m4-filters-placeholder" aria-hidden="true" />');
		$ph.insertBefore($source);
		$source.data('m4-placeholder', $ph);
		$source.css('display', 'block').appendTo($slot);
		$source.data('m4-in-drawer', true);
	}

	function restoreFiltersFromDrawer($root) {
		var $source = $root.find('[data-m4-filters-source]').first();
		if (!$source.length || !$source.data('m4-in-drawer')) return;
		var $ph = $source.data('m4-placeholder');
		if ($ph && $ph.length) {
			$source.insertBefore($ph);
			$ph.remove();
		}
		$source.css('display', 'none');
		$source.removeData('m4-in-drawer m4-placeholder');
	}

	function openFiltersDrawer(root) {
		var $root = $(root || document);
		moveFiltersIntoDrawer($root);
		$root.find('.m4-filters-drawer, .m4-filters-backdrop').addClass('is-open');
		$('body').addClass('m4-drawer-open');
	}

	function closeFiltersDrawer(root) {
		var $root = $(root || document);
		$root.find('.m4-filters-drawer, .m4-filters-backdrop').removeClass('is-open');
		$('body').removeClass('m4-drawer-open');
		restoreFiltersFromDrawer($root);
	}

	function initFiltersDrawer(ctx) {
		var $ctx = $(ctx || document);
		$ctx.off('click.m4filters', '[data-m4-filters-open]').on('click.m4filters', '[data-m4-filters-open]', function (e) {
			e.preventDefault();
			openFiltersDrawer($ctx);
		});
		$ctx.off('click.m4filters', '[data-m4-filters-close], .m4-filters-backdrop').on('click.m4filters', '[data-m4-filters-close], .m4-filters-backdrop', function (e) {
			e.preventDefault();
			closeFiltersDrawer($ctx);
		});
		$ctx.off('click.m4filters', '#btnBuscarDrawer').on('click.m4filters', '#btnBuscarDrawer', function (e) {
			e.preventDefault();
			var $buscar = $ctx.find('#btnBuscar');
			if ($buscar.length) $buscar.trigger('click');
			closeFiltersDrawer($ctx);
		});
	}

	function initTabs(ctx) {
		var $ctx = $(ctx || document);
		$ctx.off('click.m4tabs', '.m4-tabs [data-m4-tab]').on('click.m4tabs', '.m4-tabs [data-m4-tab]', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var target = $btn.data('m4-tab');
			var $wrap = $btn.closest('[data-m4-tabs-root]');
			if (!$wrap.length) $wrap = $btn.closest('.m4-page, .exa-body, body');
			$btn.closest('.m4-tabs').find('.m4-tab').removeClass('is-active');
			$btn.addClass('is-active');
			$wrap.find('[data-m4-tab-panel]').addClass('m4-hidden').filter('[data-m4-tab-panel="' + target + '"]').removeClass('m4-hidden');
		});
	}

	function ensureToastHost() {
		var $host = $('#m4ToastHost');
		if ($host.length) return $host;
		$host = $('<div id="m4ToastHost" class="m4-toast-host" aria-live="polite"></div>');
		$('body').append($host);
		return $host;
	}

	function toast(message, type, ms) {
		type = type || 'info';
		ms = typeof ms === 'number' ? ms : 3200;
		var $host = ensureToastHost();
		var cls = 'm4-toast m4-toast-' + type;
		var $el = $('<div class="' + cls + '" role="status"></div>').text(String(message || ''));
		$host.append($el);
		setTimeout(function () { $el.addClass('is-visible'); }, 10);
		setTimeout(function () {
			$el.removeClass('is-visible');
			setTimeout(function () { $el.remove(); }, 250);
		}, ms);
	}

	function boot(ctx) {
		initFiltersDrawer(ctx);
		initTabs(ctx);
	}

	$(function () { boot(document); });

	window.ExaUiM4 = {
		boot: boot,
		openFiltersDrawer: openFiltersDrawer,
		closeFiltersDrawer: closeFiltersDrawer,
		toast: toast
	};
})(window, document, window.jQuery);
