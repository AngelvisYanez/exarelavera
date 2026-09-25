/**

 * Ajusta jqGrid al espacio del .exa-ui-grid-host (model3).

 * Ancho: reparte solo si las columnas caben; si no, scroll horizontal (sin solapar texto).

 */

(function (window, $) {

	'use strict';



	function exaUiViewportHeight() {
		var vv = window.visualViewport;
		return (vv && vv.height > 0) ? vv.height : window.innerHeight;
	}

	function exaUiEnableFillRoot() {
		if (!document.querySelector('.exa-ui-fill-page')) {
			return;
		}
		document.documentElement.classList.add('exa-ui-fill-root');
		document.body.classList.add('exa-ui-fill-root');
		exaUiBindViewportReflow();
	}

	function exaUiBindViewportReflow() {
		if (window._exaUiViewportReflow) {
			return;
		}
		window._exaUiViewportReflow = true;
		var tick;
		var run = function () {
			if (tick) {
				return;
			}
			tick = window.requestAnimationFrame(function () {
				tick = 0;
				$('.exa-ui-fill-page .exa-ui-grid-host table[id]').each(function () {
					if (this.grid && window.exaUiSyncGridWidth) {
						var $g = $(this);
						if ($g.is(':visible')) {
							exaUiSyncGridWidth($g, exaUiResolveGridHost($g));
						}
					}
				});
				window._exaUiVpReflowBusy = true;
				$(window).trigger('resize');
				window._exaUiVpReflowBusy = false;
			});
		};
		$(window).on('resize.exaUiVpReflow', function () {
			if (window._exaUiVpReflowBusy) {
				return;
			}
			run();
		});
		if (window.visualViewport) {
			window.visualViewport.addEventListener('resize', run);
			window.visualViewport.addEventListener('scroll', run);
		}
	}



	function exaUiRectH($el) {

		if (!$el || !$el.length) {

			return 0;

		}

		var r = $el[0].getBoundingClientRect();

		return r.height > 0 ? r.height : ($el.outerHeight(true) || 0);

	}



	function exaUiScrollbarGutter($el) {
		if (!$el || !$el.length) {
			return 0;
		}
		var el = $el[0];
		return Math.max(0, (el.offsetWidth || 0) - (el.clientWidth || 0));
	}

	function exaUiHostWidth($host, $g) {
		if (!$host || !$host.length) {
			return 0;
		}
		var el = $host[0];
		var w = el.clientWidth || el.offsetWidth;
		if (w < 80) {
			w = el.getBoundingClientRect().width;
		}
		var $dialogContent = $host.closest('.ui-dialog-content');
		if ($dialogContent.length) {
			var cw = $dialogContent[0].clientWidth || $dialogContent.innerWidth();
			if (cw > 80) {
				w = Math.max(w, cw - 12);
			}
		}
		var gutter = 10;
		if ($g && $g.length && $g[0].grid) {
			var $gbox = $('#gbox_' + $g.attr('id'));
			var $bdiv = $gbox.children('.ui-jqgrid-view').children('.ui-jqgrid-bdiv').first();
			gutter += exaUiScrollbarGutter($bdiv);
			/* No usar bdiv colapsado (modal recien abierto): dejaba la grilla en ~10px */
			if (!$dialogContent.length && $bdiv.length && $bdiv[0].clientWidth > 60) {
				var bw = $bdiv[0].clientWidth + exaUiScrollbarGutter($bdiv);
				if (bw >= w * 0.45) {
					w = Math.min(w, bw);
				}
			}
		}
		return Math.max(80, Math.floor(w) - gutter);
	}

	function exaUiClampGridAfterWidth($g, w, doShrink) {
		var $gbox = $('#gbox_' + $g.attr('id'));
		var $view = $gbox.children('.ui-jqgrid-view').first();
		var $hdiv = $view.children('.ui-jqgrid-hdiv').first();
		var $bdiv = $view.children('.ui-jqgrid-bdiv').first();
		if (!$bdiv.length) {
			return;
		}

		if (doShrink) {
			var cw = $bdiv[0].clientWidth;
			if (cw > 60 && cw < w) {
				w = cw;
			}
			$g.jqGrid('setGridWidth', w, true);
			$view.find('.ui-jqgrid-htable, .ui-jqgrid-btable, .ui-jqgrid-ftable').css({
				width: w + 'px',
				maxWidth: w + 'px'
			});
			$hdiv.css({ overflow: 'hidden', width: '100%', maxWidth: '100%' });
			$bdiv.css({ overflowX: 'hidden', overflowY: 'auto', width: '100%', maxWidth: '100%' });
			if ($bdiv[0].scrollWidth > $bdiv[0].clientWidth + 2) {
				w = $bdiv[0].clientWidth;
				$g.jqGrid('setGridWidth', w, true);
				$view.find('.ui-jqgrid-htable, .ui-jqgrid-btable, .ui-jqgrid-ftable').css({
					width: w + 'px',
					maxWidth: w + 'px'
				});
			}
		} else {
			$bdiv.css('overflowX', 'auto');
		}
	}



	function exaUiGridColSum($g) {

		if (!$g.length || !$g[0].grid) {

			return 0;

		}

		var cm = $g.jqGrid('getGridParam', 'colModel') || [];

		var sum = 0;

		var i;

		for (i = 0; i < cm.length; i++) {

			if (cm[i].hidden) {

				continue;

			}

			sum += parseInt(cm[i].width, 10) || 80;

		}

		if ($g.jqGrid('getGridParam', 'rownumbers')) {

			sum += 35;

		}

		if ($g.jqGrid('getGridParam', 'subGrid')) {

			sum += 18;

		}

		if ($g.jqGrid('getGridParam', 'multiselect')) {

			sum += 25;

		}

		return sum;

	}



	/** true = forzar shrink; false = sin shrink; omitir = autom�tico seg�n ancho de columnas */

	function exaUiShouldShrink($g, $host, shrinkPref) {

		if (shrinkPref === true) {

			return true;

		}

		if (shrinkPref === false) {

			return false;

		}

		var w = exaUiHostWidth($host);

		var colSum = exaUiGridColSum($g);

		return colSum > 0 && colSum <= w;

	}

	function exaUiResolveShrink($g, $host, shrinkPref) {
		if (shrinkPref === true) {
			return true;
		}
		if (shrinkPref === false) {
			return false;
		}
		if ($g.jqGrid('getGridParam', 'shrinkToFit') === true) {
			return true;
		}
		return exaUiShouldShrink($g, $host, shrinkPref);
	}

	function exaUiGridChromeHeight($g, $host) {

		var gridId = $g.attr('id');

		var $gbox = $('#gbox_' + gridId);

		var chromeH = 0;



		if ($gbox.length) {

			$gbox.find('.ui-jqgrid-titlebar, .ui-jqgrid-hdiv, .ui-jqgrid-sdiv, .ui-jqgrid-toppager').filter(':visible').each(function () {

				chromeH += exaUiRectH($(this));

			});

		}



		$host.children(':visible').each(function () {

			var $el = $(this);

			if ($el.is($gbox) || $el.is($g) || $el.is('table')) {

				return;

			}

			chromeH += exaUiRectH($el);

		});



		var pagerSel = $g.jqGrid('getGridParam', 'pager');

		if (pagerSel) {

			var $pager = $(pagerSel);

			if ($pager.length && $pager.is(':visible') && !$pager.closest($gbox).length) {

				var inHost = false;

				$host.children(':visible').each(function () {

					if (this === $pager[0]) {

						inHost = true;

					}

				});

				if (!inHost) {

					chromeH += exaUiRectH($pager);

				}

			}

		}



		return Math.ceil(chromeH);

	}



	function exaUiResolveHostHeight($host, gap) {
		gap = typeof gap === 'number' ? gap : 2;

		if ($host.closest('.exa-ui-fill-page').length) {
			var hostRect = $host[0].getBoundingClientRect();
			var reserveBelow = 0;
			var $list = $host.closest('.exa-ui-fill-list');
			if ($list.length) {
				var hostBottom = hostRect.bottom;
				$list.children(':visible').each(function () {
					var $el = $(this);
					if ($el.is($host) || $el.find($host).length) {
						return;
					}
					var r = this.getBoundingClientRect();
					if (r.top >= hostBottom - 2) {
						reserveBelow += exaUiRectH($el);
					}
				});
			}
			var avail = exaUiViewportHeight() - hostRect.top - reserveBelow - gap;
			if (avail >= 80) {
				return avail;
			}
		}

		var hostRectH = exaUiRectH($host);
		if (hostRectH >= 80) {
			return hostRectH - gap;
		}

		var $scope = $host.closest('.exa-ui-fill-list, .exa-ui-page-view, .exa-body');
		if ($scope.length) {
			var scopeH = exaUiRectH($scope);
			var hostTop = $host.position().top || 0;
			var reserveBelow = 0;
			$scope.children(':visible').each(function () {
				var $el = $(this);
				if ($el.is($host) || $el.find($host).length) {
					return;
				}
				if (($el.position().top || 0) > hostTop + 1) {
					reserveBelow += exaUiRectH($el);
				}
			});
			return scopeH - hostTop - reserveBelow - gap;
		}

		return Math.max(100, exaUiViewportHeight() - ($host[0].getBoundingClientRect().top || 0) - gap);
	}



	function exaUiResolveGridHost($g) {

		var $host = $g.closest('.exa-ui-grid-host');

		if ($host.length) {

			return $host;

		}

		var $parent = $g.parent();

		if ($parent.length && !$parent.is('body, html')) {

			if (!$parent.hasClass('exa-ui-grid-host')) {

				$parent.addClass('exa-ui-grid-host');

			}

			return $parent;

		}

		return $g.closest('.exa-ui-panel, .exa-body, .panel-body').first();

	}



	function exaUiSyncGridWidth($g, $host, shrink) {

		if (!$g.length || !$g[0].grid) {

			return;

		}

		if (!$host || !$host.length) {

			$host = exaUiResolveGridHost($g);

		}

		var w = exaUiHostWidth($host, $g);

		if (w < 80) {

			return;

		}

		var doShrink = exaUiResolveShrink($g, $host, shrink);

		$g.jqGrid('setGridWidth', w, doShrink);

		var $gbox = $('#gbox_' + $g.attr('id'));

		$gbox.css({ width: '100%', maxWidth: '100%', boxSizing: 'border-box' });
		$gbox.toggleClass('exa-ui-grid-shrink-fit', doShrink);

		$gbox.find('.ui-jqgrid-view, .ui-jqgrid-hdiv, .ui-jqgrid-bdiv, .ui-jqgrid-sdiv').css({
			width: '100%',
			maxWidth: '100%',
			boxSizing: 'border-box'
		});

		exaUiClampGridAfterWidth($g, w, doShrink);

	}



	function exaUiObserveHost(hostEl, fitFn) {

		if (!hostEl || typeof window.ResizeObserver === 'undefined') {

			return null;

		}

		var ro = new window.ResizeObserver(function () {

			window.requestAnimationFrame(fitFn);

		});

		ro.observe(hostEl);

		return ro;

	}



	function exaUiSubgridPanelHtml(subgridId, tableId, title) {

		var t = title ? String(title).replace(/"/g, '&quot;') : '';

		var attr = t ? ' data-exa-subgrid-title="' + t + '"' : '';

		return '<div class="exa-ui-subgrid-panel lab-ind-detail-panel"' + attr + '>' +

			'<table id="' + tableId + '" class="scroll"></table></div>';

	}



	function exaUiWrapSubgrid(subgridId, tableId, title) {

		$('#' + subgridId).html(exaUiSubgridPanelHtml(subgridId, tableId, title));

	}



	/** Altura del cuerpo del subgrid seg�n filas visibles (0 filas = solo cabecera). */
	function exaUiFitSubgridHeight($subGrid, opts) {
		if (!$subGrid.length || !$subGrid[0].grid) {
			return;
		}
		var o = $.extend({ maxBody: 132, rowH: 29, emptyBody: 0 }, opts || {});
		var gridId = $subGrid.attr('id');
		var $gbox = $('#gbox_' + gridId);
		var $view = $gbox.children('.ui-jqgrid-view').first();
		var $bdiv = $view.children('.ui-jqgrid-bdiv').first();
		if (!$bdiv.length) {
			$bdiv = $gbox.find('.ui-jqgrid-bdiv').first();
		}

		var $rows = $bdiv.find('tr.jqgrow:visible');
		var bodyH = o.emptyBody;
		if ($rows.length) {
			bodyH = 0;
			$rows.each(function () {
				var h = exaUiRectH($(this));
				bodyH += h > 0 ? h : o.rowH;
			});
		}
		if (bodyH > o.maxBody) {
			bodyH = o.maxBody;
		}

		$subGrid.jqGrid('setGridHeight', bodyH, false);
		$bdiv.css({
			height: bodyH + 'px',
			maxHeight: o.maxBody + 'px',
			minHeight: 0,
			overflowY: ($rows.length && bodyH >= o.maxBody) ? 'auto' : 'hidden'
		});
		$view.css({ height: 'auto', minHeight: 0 });
		$gbox.css({ height: 'auto', minHeight: 0 });

		var $subRow = $subGrid.closest('tr.ui-subgrid');
		if ($subRow.length) {
			$subRow.css('height', 'auto');
			$subRow.children('td').css('height', 'auto');
		}
	}

	function exaUiFitSubgrid($subGrid, shrink) {
		if (!$subGrid.length || !$subGrid[0].grid) {
			return;
		}
		var $panel = $subGrid.closest('.exa-ui-subgrid-panel, .lab-ind-detail-panel');
		var $host = $panel.length ? $panel : $subGrid.closest('.ui-subgrid');
		exaUiSyncGridWidth($subGrid, $host, shrink);
		exaUiFitSubgridHeight($subGrid);
	}



	function exaUiFitGridsIn(scopeSelector) {

		var $scope = scopeSelector ? $(scopeSelector) : $('.exa-ui-panel');

		$scope.find('table[id]').each(function () {

			if (!this.grid) {

				return;

			}

			var $g = $(this);

			if (!$g.is(':visible')) {

				return;

			}

			exaUiSyncGridWidth($g, exaUiResolveGridHost($g));

		});

	}

	/** Ajusta grillas dentro de un jQuery UI dialog (modal Pagos, etc.). */
	function exaUiFitDialogGrids(dialogSelector) {
		var $dlg = $(dialogSelector).closest('.ui-dialog');
		var $content = $(dialogSelector).closest('.ui-dialog-content');
		if (!$content.length && $dlg.length) {
			$content = $dlg.find('.ui-dialog-content').first();
		}
		if (!$content.length) {
			$content = $(dialogSelector);
		}
		var run = function () {
			if (!$content.is(':visible') && !$dlg.is(':visible')) {
				return;
			}
			$content.find('table[id]').each(function () {
				if (!this.grid) {
					return;
				}
				var $g = $(this);
				if (!$g.is(':visible')) {
					return;
				}
				exaUiSyncGridWidth($g, exaUiResolveGridHost($g), false);
			});
		};
		run();
		setTimeout(run, 0);
		setTimeout(run, 120);
		setTimeout(run, 350);
	}



	function exaUiAfterViewChange(scopeSelector) {

		var run = function () {

			exaUiFitGridsIn(scopeSelector || '.exa-ui-panel');

			if ($('#listar_cccc').is(':visible') && $('#searchGrid')[0] && $('#searchGrid')[0].grid) {

				exaUiFitJqGrid('#searchGrid', '#listar_cccc .exa-ui-grid-host');

			}

		};

		setTimeout(run, 0);

		setTimeout(run, 120);

		setTimeout(run, 350);

	}



	function exaUiFitJqGrid(gridSelector, hostSelector, bottomGap) {

		var $g = $(gridSelector);

		var $host = hostSelector ? $(hostSelector) : exaUiResolveGridHost($g);

		var gap = typeof bottomGap === 'number' ? bottomGap : 2;

		var ns = '.exaUiFit_' + String(gridSelector).replace(/[^a-z0-9_-]/gi, '');



		function fit() {

			if (!$g.length || !$host.length || !$g[0].grid) {

				return;

			}

			if (!$host.is(':visible') && !$g.is(':visible')) {

				return;

			}



			exaUiSyncGridWidth($g, $host);



			if (!$host.closest('.exa-ui-fill-list, .exa-ui-fill-page').length) {

				return;

			}



			var totalH = exaUiResolveHostHeight($host, gap);

			var chromeH = exaUiGridChromeHeight($g, $host);

			var bodyH = Math.floor(totalH - chromeH);

			if (bodyH < 80) {

				bodyH = Math.max(80, Math.floor(totalH * 0.65));

			}

			if ($host.hasClass('exa-ui-grid-host--compact')) {

				bodyH = Math.max(80, Math.floor(bodyH * 0.9));

			}

			$g.jqGrid('setGridHeight', bodyH);

		}



		exaUiEnableFillRoot();

		fit();

		setTimeout(fit, 0);

		setTimeout(fit, 150);

		setTimeout(fit, 400);



		$(window).off('resize' + ns).on('resize' + ns, function () {

			window.requestAnimationFrame(fit);

		});



		if ($host.length && $host[0]._exaUiRo) {

			try {

				$host[0]._exaUiRo.disconnect();

			} catch (e) { /* ignore */ }

		}

		if ($host.length) {

			$host[0]._exaUiRo = exaUiObserveHost($host[0], fit);

		}



		return fit;

	}



	window.exaUiEnableFillRoot = exaUiEnableFillRoot;
	window.exaUiViewportHeight = exaUiViewportHeight;
	window.exaUiBindViewportReflow = exaUiBindViewportReflow;

	window.exaUiFitJqGrid = exaUiFitJqGrid;

	window.exaUiSyncGridWidth = exaUiSyncGridWidth;

	window.exaUiGridColSum = exaUiGridColSum;

	window.exaUiGridChromeHeight = exaUiGridChromeHeight;

	window.exaUiSubgridPanelHtml = exaUiSubgridPanelHtml;

	window.exaUiWrapSubgrid = exaUiWrapSubgrid;

	window.exaUiFitSubgrid = exaUiFitSubgrid;
	window.exaUiFitSubgridHeight = exaUiFitSubgridHeight;

	window.exaUiFitGridsIn = exaUiFitGridsIn;
	window.exaUiFitDialogGrids = exaUiFitDialogGrids;

	window.exaUiAfterViewChange = exaUiAfterViewChange;

	window.exaUiResolveGridHost = exaUiResolveGridHost;

	/**
	 * Boton de accion en celda jqGrid (estilo model3: v2-grid-act-info / v2-grid-act-edit / v2-grid-act-del).
	 * @param {Function|string} action
	 * @param {*} data - argumento para onclick (data-originaldata)
	 * @param {string} title
	 * @param {string} [kind='edit'] - edit | delete | info | remove
	 * @param {string} [icon] - glyphicon sin prefijo (pencil, trash, ...)
	 */
	function exaUiGridActButton(action, data, title, kind, icon) {
		if (typeof $ === 'undefined' || !$.getGridButton) {
			return '';
		}
		var k = String(kind || 'edit').toLowerCase();
		var isDel = k === 'delete' || k === 'del' || k === 'danger' || k === 'trash' || k === 'anular';
		var isRemove = k === 'remove';
		var isInfo = k === 'info' || k === 'view' || k === 'ver';
		var iconName = icon || (isRemove ? 'remove' : (isDel ? 'trash' : (isInfo ? 'info-sign' : 'pencil')));
		var cls = 'btn btn-xs v2-grid-act-btn';
		if (isDel || isRemove) {
			cls += ' v2-grid-act-del';
		} else if (isInfo) {
			cls += ' v2-grid-act-info';
		} else {
			cls += ' v2-grid-act-edit';
		}
		return $.getGridButton(
			action,
			data,
			title,
			iconName,
			'',
			isInfo ? 'info' : (isDel || isRemove ? 'danger' : 'info'),
			'xs',
			{ 'class': cls }
		);
	}

	window.exaUiGridActButton = exaUiGridActButton;

	/**
	 * Boton de accion con icono ui-icon-gear (mismo estilo que cabecera subgrilla).
	 */
	function exaUiGridGearButton(action, data, title) {
		if (typeof $ === 'undefined' || !$.getGridButton) {
			return '';
		}
		var html = $.getGridButton(
			action,
			data,
			title || 'Acci�n',
			'',
			'',
			'info',
			'xs',
			{ 'class': 'btn btn-xs v2-grid-act-btn v2-grid-act-gear' }
		);
		if (html && html.indexOf('ui-icon-gear') < 0) {
			html = html.replace(/<\/button>/i, '<i class="ui-icon ui-icon-gear"></i></button>');
		}
		return html;
	}

	window.exaUiGridGearButton = exaUiGridGearButton;

	/**
	 * Colorea filas jqGrid segun texto de una columna (compatible con model3).
	 * @param {string} gridSelector - ej. '#searchGrid'
	 * @param {object} [options]
	 * @param {string} [options.column='vencimiento'] - name de colModel
	 * @param {Array} [options.rules] - { match: 'Vencido', cellClass: 'cellRed2', rowClass: 'opcional' }
	 */
	function exaUiColorGridRows(gridSelector, options) {
		options = $.extend({
			column: 'vencimiento',
			rules: [
				{ match: 'Vencido', cellClass: 'cellRed2', rowClass: 'exa-ui-row-vencido' },
				{ match: 'Pagado', cellClass: 'cellGreen2', rowClass: 'exa-ui-row-pagado' }
			]
		}, options || {});
		var $g = $(gridSelector);
		if (!$g.length) {
			return;
		}
		var stateCell = 'cellRed2 cellGreen2 cellBlue2 cellOrange2 cellGray cellPurple1 cellRed1 cellGreen1 cellBlue1';
		var colSel = 'td[aria-describedby$="_' + options.column + '"]';
		$g.find('tr.jqgrow').each(function () {
			var $tr = $(this);
			var $tds = $tr.find('td:not(.jqgrid-rownum)');
			$tds.removeClass(stateCell);
			$tr.removeClass('exa-ui-row-vencido exa-ui-row-pagado myAltRowClass');
			$tr.css('background', '');
			var txt = ($tr.find(colSel).text() || '').replace(/\u00a0/g, ' ').trim();
			var i, r;
			for (i = 0; i < options.rules.length; i++) {
				r = options.rules[i];
				if (txt === r.match || (r.match instanceof RegExp && r.match.test(txt))) {
					if (r.rowClass) {
						$tr.addClass(r.rowClass + ' myAltRowClass');
					}
					if (r.cellClass) {
						$tds.addClass(r.cellClass);
					}
					break;
				}
			}
		});
	}

	window.exaUiColorGridRows = exaUiColorGridRows;

	$(exaUiEnableFillRoot);

})(window, jQuery);

