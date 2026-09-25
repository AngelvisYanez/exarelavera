/**
 * Monitoreo de actividades - jqGrid Model3.
 * Conserva: calendario Desde/Hasta, filtro columnas, detalle, simulacion.
 * @package auditoria.VALIDACIONES
 */
$(function () {
	var $grid = $('#gridMonitoreo');
	if (!$grid.length) {
		return;
	}

	var storageKey = 'aud_monitoreo_cols_v7';
	var hasSucursales = (typeof AUD_HAS_SUCURSALES !== 'undefined') ? !!AUD_HAS_SUCURSALES : ($('#suc').length > 0);

	/** Chosen con buscador en todos los selects del filtro */
	function refreshSelectSearch($sel) {
		if (!$sel || !$sel.length || !$.fn.chosen) {
			return;
		}
		$sel.each(function () {
			var $el = $(this);
			if (!$el.is('select')) {
				return;
			}
			var $dups = $el.nextAll('.chosen-container');
			if ($dups.length > 1) {
				$dups.slice(1).remove();
			}
			if ($el.data('chosen') || $el.next('.chosen-container').length) {
				try {
					$el.trigger('chosen:updated');
					$el.trigger('chosen:close');
				} catch (eUp) {}
				$el.next('.chosen-container').removeClass('chosen-with-drop chosen-container-active');
				$el.hide();
				return;
			}
			var ph = $el.attr('data-placeholder') || 'Buscar...';
			var label = $.trim($el.closest('.aud-search-cell').find('label').first().text() || 'opciones');
			try {
				$el.chosen({
					width: '100%',
					search_contains: true,
					inherit_select_classes: false,
					disable_search_threshold: 0,
					placeholder_text_single: ph,
					no_results_text: 'Sin coincidencias en ' + label.toLowerCase()
				});
			} catch (eCh) {}
			$el.hide();
			$el.next('.chosen-container').removeClass('chosen-with-drop chosen-container-active');
			$el.off('chosen:showing_dropdown.audMon chosen:hiding_dropdown.audMon')
				.on('chosen:showing_dropdown.audMon', function () {
					$el.next('.chosen-container').css('z-index', 1060);
					$el.closest('.aud-mon-filters, .aud-search-cell, .aud-mon-block').css({ overflow: 'visible', zIndex: 55 });
				})
				.on('chosen:hiding_dropdown.audMon', function () {
					$el.next('.chosen-container').removeClass('chosen-with-drop chosen-container-active').css('z-index', 40);
				});
		});
	}

	function initChosen() {
		refreshSelectSearch($('#frmFiltros select.aud-select-search'));
	}

	function filtrosPost() {
		var data = {
			listMonitoreoGridAjax: 1,
			from: $('#from').val() || '',
			to: $('#to').val() || '',
			usu: $('#usu').val() || 0,
			org: $('#org').val() || 0,
			dir: $('#dir').val() || 0,
			pcs: $('#pcs').val() || 0,
			eve: $('#eve').val() || 0,
			pla: ($('#filPlanta').length && $('#audFilPlantaWrap').is(':visible')) ? ($('#filPlanta').val() || 0) : 0
		};
		if (hasSucursales) {
			data.suc = $('#suc').val() || 0;
		}
		return data;
	}

	/** Muestra el filtro de Planta solo si el proceso seleccionado tiene que ver con plantas */
	function actualizarPlantaSelect() {
		var $wrap = $('#audFilPlantaWrap');
		var $sel = $('#filPlanta');
		if (!$wrap.length || !$sel.length) {
			return;
		}
		var pcs = parseInt($('#pcs').val(), 10) || 0;
		if (pcs <= 0) {
			$wrap.hide();
			$sel.val('0');
			refreshSelectSearch($sel);
			return;
		}
		$.getJSON(window.location.pathname, { plantaProcesoAjax: 1, pcs: pcs }, function (resp) {
			if (resp && resp.success && resp.tiene) {
				$wrap.show();
				refreshSelectSearch($sel);
			} else {
				$wrap.hide();
				$sel.val('0');
				refreshSelectSearch($sel);
			}
		}).fail(function () {
			$wrap.hide();
			$sel.val('0');
			refreshSelectSearch($sel);
		});
	}

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
		return { from: fmtYmd(dDesde), to: fmtYmd(dHasta) };
	}

	function sincronizarPresetActivo(vFrom, vTo) {
		var presets = ['ayer', 'hoy', '1semana', '1mes', '3meses'];
		var coincidencia = null;
		var i, r;
		for (i = 0; i < presets.length; i++) {
			r = calcularRangoPreset(presets[i]);
			if (r && r.from === vFrom && r.to === vTo) {
				coincidencia = presets[i];
				break;
			}
		}
		$('#audMonPeriodoPresets .aud-btn-preset').removeClass('active');
		if (coincidencia) {
			$('#audMonPeriodoPresets .aud-btn-preset[data-preset="' + coincidencia + '"]').addClass('active');
		}
	}

	function aplicarRangoPreset(preset, autoBuscar) {
		var r = calcularRangoPreset(preset);
		if (!r) {
			return;
		}
		$('#from').val(r.from);
		$('#to').val(r.to);
		try {
			$('#from').datepicker('setDate', r.from);
			$('#to').datepicker('setDate', r.to);
			$('#to').datepicker('option', 'minDate', r.from);
			$('#from').datepicker('option', 'maxDate', r.to);
		} catch (eSet) {}
		sincronizarPresetActivo(r.from, r.to);
		actualizarHint();
		if (autoBuscar) {
			buscar();
		}
	}

	function actualizarHint() {
		/* Hint de filtros activos eliminado de la UI */
	}

	function cargarDirectorios(org, done) {
		$.getJSON(window.location.pathname, { listDirectoriosAjax: 1, org: org || 0 }, function (data) {
			var $dir = $('#dir');
			var cur = $dir.val();
			$dir.empty().append('<option value="0">Todos</option>');
			if (data && data.rows) {
				$.each(data.rows, function (i, r) {
					$dir.append($('<option/>').val(r.Org_Cod).text(r.Org_Des));
				});
			}
			if (cur && $dir.find('option[value="' + cur + '"]').length) {
				$dir.val(cur);
			} else {
				$dir.val('0');
			}
			refreshSelectSearch($dir);
			if (typeof done === 'function') {
				done();
			}
		}).fail(function () {
			if (typeof done === 'function') {
				done();
			}
		});
	}

	function cargarProcesos(org, dir, done) {
		$.getJSON(window.location.pathname, {
			listProcesosAjax: 1,
			org: org || 0,
			dir: dir || 0
		}, function (data) {
			var $pcs = $('#pcs');
			var cur = $pcs.val();
			$pcs.empty().append('<option value="0">Todos</option>');
			if (data && data.rows) {
				$.each(data.rows, function (i, r) {
					$pcs.append($('<option/>').val(r.Pcs_Cod).text(r.Pcs_Lin));
				});
			}
			if (cur && $pcs.find('option[value="' + cur + '"]').length) {
				$pcs.val(cur);
			} else {
				$pcs.val('0');
			}
			refreshSelectSearch($pcs);
			if (typeof done === 'function') {
				done();
			}
		}).fail(function () {
			if (typeof done === 'function') {
				done();
			}
		});
	}

	function cargarUsuarios(suc, done) {
		$.getJSON(window.location.pathname, { listUsuariosAjax: 1, suc: suc || 0 }, function (data) {
			var $usu = $('#usu');
			var cur = $usu.val();
			$usu.empty().append('<option value="0">Todos</option>');
			if (data && data.rows) {
				$.each(data.rows, function (i, r) {
					var val = ($.trim(r.Usu_Cods || '') !== '')
						? r.Usu_Cods
						: (r.Usu_Cod || '0');
					$usu.append($('<option/>').val(val).text(r.Usu_Nom));
				});
			}
			if (cur && $usu.find('option[value="' + cur + '"]').length) {
				$usu.val(cur);
			} else {
				$usu.val('0');
			}
			refreshSelectSearch($usu);
			if (typeof done === 'function') {
				done();
			}
		}).fail(function () {
			refreshSelectSearch($('#usu'));
			if (typeof done === 'function') {
				done();
			}
		});
	}

	function buscar() {
		var from = $.trim($('#from').val() || '');
		var to = $.trim($('#to').val() || '');
		if (from && to && from > to) {
			if (typeof $.alert === 'function') {
				$.alert('La fecha Desde no puede ser mayor que Hasta.');
			} else {
				alert('La fecha Desde no puede ser mayor que Hasta.');
			}
			return;
		}
		actualizarHint();
		cargarResumen();
		$grid.jqGrid('setGridParam', {
			datatype: 'json',
			postData: filtrosPost(),
			page: 1
		}).trigger('reloadGrid');
	}

	/* ---- Resumen automatizado del filtro actual (misma consulta que el PDF) ---- */
	function audResumenFitGrid() {
		if (typeof exaUiFitJqGrid === 'function') {
			try { exaUiFitJqGrid('#gridMonitoreo', '#lista .exa-ui-grid-host'); } catch (eFit) {}
		}
	}

	function cargarResumen() {
		var $wrap = $('#audResumenWrap');
		var $body = $('#audResumenContenido');
		if (!$wrap.length || !$body.length) {
			return;
		}
		var data = filtrosPost();
		data.resumenMonitoreoAjax = 1;
		$.getJSON(window.location.pathname, data, function (resp) {
			var r = resp && resp.resumen;
			if (!r) {
				$wrap.hide();
				audResumenFitGrid();
				return;
			}
			function card(num, lbl, cls, tip) {
				return '<div class="aud-resumen-card ' + (cls || '') + '" title="' + (tip || lbl) + '">' +
					'<span class="aud-rc-num">' + num + '</span><span class="aud-rc-lbl">' + lbl + '</span></div>';
			}
			function topList(map, emptyLbl, maxN) {
				var items = [];
				var n = 0;
				maxN = maxN || 3;
				if (map) {
					$.each(map, function (k, v) {
						if (n >= maxN) { return false; }
						items.push('<li>' + $.trim(k) + ' <b>(' + v + ')</b></li>');
						n++;
					});
				}
				if (!items.length && r.total > 0) {
					items.push('<li class="text-muted">' + (emptyLbl || '-') + '</li>');
				}
				return items.join('');
			}
			var pe = r.por_evento || {};
			var cards = '';
			cards += card(r.total || 0, 'Total', 'aud-rc-total', 'Actividades registradas en el periodo');
			cards += card(pe.I || 0, 'Ins.', 'aud-rc-i', 'Registros insertados');
			cards += card(pe.U || 0, 'Act.', 'aud-rc-u', 'Registros actualizados');
			cards += card(pe.D || 0, 'Eli.', 'aud-rc-d', 'Registros eliminados o anulados');
			cards += card(pe.F || 0, 'Otras', 'aud-rc-f', 'Otras actividades (no I/U/D)');
			cards += card(r.usuarios_distintos || 0, 'Ususuarios', '', 'Usuarios distintos con actividad');

			var detail = '<div class="aud-resumen-detail">';
			detail += '<div class="aud-resumen-cols">';
			detail += '<div class="aud-resumen-col"><strong>Top modulos</strong><ul>' + topList(r.top_modulos, 'Sin modulo') + '</ul></div>';
			detail += '<div class="aud-resumen-col"><strong>Top usuarios</strong><ul>' + topList(r.top_usuarios) + '</ul></div>';
			detail += '<div class="aud-resumen-col"><strong>Top procesos</strong><ul>' + topList(r.top_procesos) + '</ul></div>';
			detail += '</div>';
			detail += '<p class="aud-resumen-obs" title="' +
				((r.observaciones && r.observaciones.length) ? $('<div/>').text(r.observaciones.join(' ')).html() : 'Sin observaciones.') +
				'">' +
				((r.observaciones && r.observaciones.length) ? r.observaciones.join(' ') : 'Sin observaciones.') +
				'</p></div>';

			$body.html('<div class="aud-resumen-cards">' + cards + '</div>' + detail);
			$wrap.addClass('is-compact').show();
			$('#btnToggleResumen').text('Detalle');
			audResumenFitGrid();
		}).fail(function () {
			$wrap.hide();
			audResumenFitGrid();
		});
	}

	function limpiarFiltros() {
		aplicarRangoPreset('1mes', false);
		$('#org').val('0');
		$('#dir').val('0');
		$('#eve').val('0');
		$('#usu').val('0');
		$('#audFilPlantaWrap').hide();
		$('#filPlanta').val('0');
		if (hasSucursales) {
			$('#suc').val('0');
		}
		refreshSelectSearch($('#frmFiltros select.aud-select-search'));
		cargarDirectorios(0, function () {
			cargarProcesos(0, 0, function () {
				$('#pcs').val('0');
				refreshSelectSearch($('#pcs'));
				buscar();
			});
		});
	}

	function audDetalleDialogWidth() {
		var vw = $(window).width();
		if (vw < 520) { return Math.max(280, vw - 24); }
		if (vw < 900) { return Math.min(720, vw - 40); }
		return Math.min(860, vw - 48);
	}

	function verDetalle(logCod) {
		var loadingHtml = ''
			+ '<div class="aud-det-loading">'
			+ '<span class="aud-det-loading-spin"></span>'
			+ '<span>Cargando detalle de la actividad...</span>'
			+ '</div>';
		$('#detalleContenido').html(loadingHtml);
		if (!$('#detalleDialog').hasClass('ui-dialog-content')) {
			$('#detalleDialog').dialog({
				autoOpen: false,
				modal: true,
				resizable: true,
				draggable: true,
				width: audDetalleDialogWidth(),
				minWidth: 320,
				maxWidth: 960,
				maxHeight: Math.min(680, $(window).height() - 32),
				height: 'auto',
				appendTo: '.exa-ui-panel',
				dialogClass: 'exa-ui-panel exa-ui-dialog',
				position: { my: 'center', at: 'center', of: window },
				buttons: [{ text: 'Cerrar', class: 'btn btn-primary', click: function () { $(this).dialog('close'); } }]
			});
			$(window).on('resize.audDetalleDlg', function () {
				try {
					if ($('#detalleDialog').dialog('isOpen')) {
						$('#detalleDialog').dialog('option', 'width', audDetalleDialogWidth());
						$('#detalleDialog').dialog('option', 'position', { my: 'center', at: 'center', of: window });
					}
				} catch (eR) {}
			});
		} else {
			try {
				$('#detalleDialog').dialog('option', 'width', audDetalleDialogWidth());
			} catch (eW) {}
		}
		$('#detalleDialog').dialog('open');
		$.ajax({
			url: window.location.pathname,
			type: 'POST',
			data: { ajax: 1, logCodigo: logCod },
			success: function (html) {
				$('#detalleContenido').html(html);
				try {
					$('#detalleDialog').dialog('option', 'position', { my: 'center', at: 'center', of: window });
				} catch (ePos) {}
			},
			error: function () {
				$('#detalleContenido').html(
					'<div class="aud-det-empty"><i class="fa fa-exclamation-triangle"></i> No se pudo cargar el detalle.</div>'
				);
			}
		});
	}
	window.audVerDetalle = verDetalle;

	/* ---- Wrapper para envio de formularios con confirmacion estilizada ----
	 * Recibe (form, event): muestra el dialogo exa-ui de Confirmar/Cancelar;
	 * si se confirma hace submit nativo (sin re-disparar onsubmit). */
	window.audConfirmarDemoForm = function (form, evt) {
		if (evt && evt.preventDefault) {
			evt.preventDefault();
		}
		var $f = $(form);
		audConfirmarDemo('Confirmar la accion?', function () {
			var f = $f.get(0);
			if (f && f.submit) {
				try { f.submit(); } catch (e9) {
					if (typeof alert === 'function') { alert('No se pudo enviar el formulario.'); }
				}
			}
		});
		return false;
	};

	/* Dialogo estilizado de confirmacion/cancelacion (patron detalleDialog) */
	var $audConfirm = null;
	function audConfirmarDemo(msj, alConfirmar) {
		if (!$('#audConfirmDemo').length) {
			$('body').append('<div id="audConfirmDemo" title="Confirmar accion" style="display:none;"><p style="padding:14px 8px 4px;"></p></div>');
		}
		var $dlg = $('#audConfirmDemo');
		$dlg.find('p').html(msj || 'Confirmar la accion?');
		if (!$dlg.hasClass('ui-dialog-content')) {
			$dlg.dialog({
				autoOpen: false,
				modal: true,
				resizable: false,
				width: Math.min(420, $(window).width() - 20),
				height: 'auto',
				appendTo: '.exa-ui-panel',
				dialogClass: 'exa-ui-panel exa-ui-dialog',
				buttons: [
					{ text: 'Confirmar', class: 'btn btn-primary', click: function () {
						$(this).dialog('close');
						if (typeof alConfirmar === 'function') {
							alConfirmar();
						}
					} },
					{ text: 'Cancelar', class: 'btn btn-default', click: function () {
						$(this).dialog('close');
					} }
				]
			});
		}
		$dlg.dialog('open');
	}
	window.audConfirmarDemo = audConfirmarDemo;

	/* ---- Calendario (yy-mm-dd; locale es fuerza dd/mm/yy) ---- */
	function initCalendarios() {
		var $from = $('#from');
		var $to = $('#to');
		if (!$from.length || !$.fn.datepicker) {
			return;
		}
		var vFrom = $from.val();
		var vTo = $to.val();
		try {
			if ($from.hasClass('hasDatepicker')) { $from.datepicker('destroy'); }
			if ($to.hasClass('hasDatepicker')) { $to.datepicker('destroy'); }
		} catch (e0) {}

		// Mismo helper del ERP (framework/jquery.basics-1.0.js)
		if (typeof $.createDateRange === 'function') {
			$.createDateRange($from, $to, 30);
		} else if (typeof $.fn.createDatePickers === 'function') {
			$from.createDatePickers({
				clean: true,
				onClose: function (sd) { $to.datepicker('option', 'minDate', sd || null); }
			});
			$to.createDatePickers({
				clean: true,
				onClose: function (sd) { $from.datepicker('option', 'maxDate', sd || null); }
			});
		} else {
			$from.datepicker({
				changeMonth: true, changeYear: true, dateFormat: 'yy-mm-dd', firstDay: 1,
				onClose: function (sd) { $to.datepicker('option', 'minDate', sd || null); }
			});
			$to.datepicker({
				changeMonth: true, changeYear: true, dateFormat: 'yy-mm-dd', firstDay: 1,
				onClose: function (sd) { $from.datepicker('option', 'maxDate', sd || null); }
			});
		}

		$from.datepicker('option', 'dateFormat', 'yy-mm-dd');
		$to.datepicker('option', 'dateFormat', 'yy-mm-dd');

		// Restaurar valores del servidor (createDateRange pone hoy / hoy-30)
		if (vFrom) {
			$from.val(vFrom);
			try { $to.datepicker('option', 'minDate', vFrom); } catch (e1) {}
		}
		if (vTo) {
			$to.val(vTo);
			try { $from.datepicker('option', 'maxDate', vTo); } catch (e2) {}
		}

		$('#btnFromCal').off('click.audCal').on('click.audCal', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$from.focus().datepicker('show');
		});
		$('#btnToCal').off('click.audCal').on('click.audCal', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$to.focus().datepicker('show');
		});

		sincronizarPresetActivo($from.val(), $to.val());
		$from.add($to).off('change.audPreset').on('change.audPreset', function () {
			sincronizarPresetActivo($from.val(), $to.val());
		});
	}
	initCalendarios();

	$('#audMonPeriodoPresets').off('click.audMonPreset', '.aud-btn-preset').on('click.audMonPreset', '.aud-btn-preset', function (e) {
		e.preventDefault();
		aplicarRangoPreset($(this).attr('data-preset'), true);
	});

	/* ---- Chosen buscador en todos los selects del filtro ---- */
	initChosen();

	/* ---- Filtro de columnas (localStorage) ---- */
	function applyCols() {
		$('.aud-col-toggle').each(function () {
			var col = $(this).attr('data-col');
			var on = $(this).is(':checked');
			try {
				if (on) {
					$grid.jqGrid('showCol', col);
				} else {
					$grid.jqGrid('hideCol', col);
				}
			} catch (e3) {}
		});
		if (!hasSucursales) {
			try { $grid.jqGrid('hideCol', 'Sucursal'); } catch (eSuc) {}
		}
		try {
			$grid.jqGrid('setGridWidth', $grid.closest('.exa-ui-grid-host').width() || $grid.parent().width());
		} catch (e4) {}
	}

	function saveCols() {
		var map = {};
		$('.aud-col-toggle').each(function () {
			map[$(this).attr('data-col')] = $(this).is(':checked');
		});
		try {
			if (window.localStorage) {
				localStorage.setItem(storageKey, JSON.stringify(map));
			}
		} catch (e5) {}
	}

	function loadCols() {
		var saved = null;
		try {
			if (window.localStorage) {
				saved = localStorage.getItem(storageKey);
			}
		} catch (e6) {}
		if (!saved) {
			return;
		}
		var map = {};
		try {
			map = $.parseJSON(saved);
		} catch (e7) {
			map = {};
		}
		$('.aud-col-toggle').each(function () {
			var col = $(this).attr('data-col');
			if (typeof map[col] !== 'undefined') {
				this.checked = !!map[col];
			}
		});
	}

	function toggleColPanel(show) {
		var $p = $('#aud-col-panel');
		if (show === false) {
			$p.hide();
			return;
		}
		if (show === true) {
			$p.show();
			return;
		}
		$p.toggle();
	}

	$('#aud-col-btn').on('click', function (e) {
		e.preventDefault();
		e.stopPropagation();
		toggleColPanel();
	});
	$(document).on('click', function (e) {
		var t = e.target;
		if ($(t).closest('#aud-col-wrap').length) {
			return;
		}
		toggleColPanel(false);
	});

	/* ---- Grilla ---- */
	loadCols();

	var colNames = ['Id', 'Fecha', 'Hora', 'Empresa', 'Sucursal', 'Usuario', 'Modulo', 'Directorio', 'Proceso', 'Actividad', 'Detalle', ''];
	var colModel = [
		{ name: 'Log_Cod', index: 'Log_Cod', width: 55, align: 'center', sortable: false },
		{ name: 'Fecha', index: 'Fecha', width: 90, align: 'center', sortable: false },
		{ name: 'Hora', index: 'Hora', width: 70, align: 'center', sortable: false },
		{ name: 'Empresa', index: 'Empresa', width: 120, align: 'left', sortable: false },
		{ name: 'Sucursal', index: 'Sucursal', width: 110, align: 'left', hidden: !hasSucursales, sortable: false },
		{ name: 'Usuario', index: 'Usuario', width: 120, align: 'left', sortable: false },
		{ name: 'Modulo', index: 'Modulo', width: 100, align: 'left', sortable: false },
		{ name: 'Directorio', index: 'Directorio', width: 110, align: 'left', sortable: false },
		{ name: 'Proceso', index: 'Proceso', width: 110, align: 'left', sortable: false },
		{ name: 'Actividad', index: 'Actividad', width: 150, align: 'left', sortable: false },
		{ name: 'Detalle', index: 'Detalle', width: 200, align: 'left', sortable: false },
		{
			name: 'acciones',
			index: 'acciones',
			width: 48,
			align: 'center',
			sortable: false,
			title: false,
			formatter: function (cellvalue, options, rowObject) {
				var id = rowObject.Log_Cod || rowObject.id || options.rowId;
				return '<button type="button" class="btn btn-success btn-xs" title="Detalle" onclick="audVerDetalle(' + id + ')"><span class="glyphicon glyphicon-chevron-right"></span></button>';
			}
		}
	];

	function ajustarPaginacion() {
		var curRows = parseInt($grid.jqGrid('getGridParam', 'rowNum'), 10) || 0;
		if (curRows <= 0) {
			var selVal = $('#gridMonitoreoPager .ui-pg-selbox').val();
			curRows = parseInt(selVal, 10) || 0;
		}
		var $pgCenter = $('#gridMonitoreoPager_center');
		if (curRows >= 10000000) {
			// Modo "Todos": se quita la paginacion
			$pgCenter.css('visibility', 'hidden');
		} else {
			$pgCenter.css('visibility', 'visible');
		}
	}

	$grid.jqGrid({
		url: window.location.pathname,
		mtype: 'POST',
		datatype: 'json',
		postData: filtrosPost(),
		colNames: colNames,
		colModel: colModel,
		cmTemplate: { sortable: false },
		viewsortcols: [false, 'vertical', false],
		jsonReader: {
			root: 'rows',
			page: 'page',
			total: 'total',
			records: 'records',
			repeatitems: false,
			id: 'Log_Cod'
		},
		pager: '#gridMonitoreoPager',
		rowNum: 250,
		rowList: [250, 500, 1000, 5000, '10000000:Todos'],
		sortname: '',
		sortorder: '',
		viewrecords: true,
		rownumbers: false,
		autowidth: true,
		shrinkToFit: true,
		height: 360,
		altRows: true,
		caption: 'Resultados de la busqueda',
		loadComplete: function () {
			applyCols();
			ajustarPaginacion();
			if (typeof exaUiFitJqGrid === 'function') {
				exaUiFitJqGrid('#gridMonitoreo', '#lista .exa-ui-grid-host');
			}
		},
		loadError: function (xhr) {
			if (window.console && console.log) {
				console.log('Monitoreo grid error', xhr && xhr.status, xhr && xhr.responseText);
			}
		}
	});

	$grid.jqGrid('navGrid', '#gridMonitoreoPager', {
		edit: false, add: false, del: false, search: false, refresh: true, view: false
	});

	$(document).on('change', '#gridMonitoreoPager .ui-pg-selbox', function () {
		ajustarPaginacion();
	});

	applyCols();

	$('.aud-col-toggle').on('click', function () {
		saveCols();
		applyCols();
	});

	$('#btnBuscar').on('click', function () {
		buscar();
	});

	$('#btnLimpiar').on('click', function () {
		limpiarFiltros();
	});

	$('#btnToggleResumen').on('click', function () {
		var $btn = $(this);
		var $wrap = $('#audResumenWrap');
		var compact = $wrap.hasClass('is-compact');
		if (compact) {
			$wrap.removeClass('is-compact');
			$btn.text('Compactar');
		} else {
			$wrap.addClass('is-compact');
			$btn.text('Detalle');
		}
		audResumenFitGrid();
	});

	$('#frmFiltros').on('keydown', 'input, select', function (e) {
		if (e.keyCode === 13) {
			e.preventDefault();
			buscar();
		}
	});

	$('#org').on('change', function () {
		var org = $(this).val() || 0;
		cargarDirectorios(org, function () {
			cargarProcesos(org, $('#dir').val() || 0, function () {
				actualizarHint();
				actualizarPlantaSelect();
			});
		});
	});

	$('#dir').on('change', function () {
		var org = $('#org').val() || 0;
		var dir = $(this).val() || 0;
		cargarProcesos(org, dir, function () {
			actualizarHint();
			actualizarPlantaSelect();
		});
	});

	$('#pcs, #eve, #usu, #from, #to').on('change', function () {
		actualizarHint();
		if (this.id === 'pcs') {
			actualizarPlantaSelect();
		}
	});

	$('#suc').on('change', function () {
		cargarUsuarios($(this).val() || 0, actualizarHint);
	});

	function exportOpts() {
		return {
			nombre: 'Monitoreo_actividades',
			hoja: 'Monitoreo',
			caption: true,
			footer: false,
			removeHiddens: true,
			removeCols: ['acciones']
		};
	}

	$('#btnExportExcel').on('click', function () {
		var $frm = $('#frmFiltros');
		if (!$frm.length) {
			return;
		}
		var total = 0;
		try {
			total = parseInt($grid.jqGrid('getGridParam', 'records'), 10) || 0;
		} catch (eRec) {}
		if (total <= 0) {
			if (typeof $.alert === 'function') {
				$.alert('No hay datos para exportar. Realice una busqueda primero.');
			} else {
				alert('No hay datos para exportar.');
			}
			return;
		}
		window.location = window.location.pathname + '?' + $frm.serialize() + '&exportMonitoreoCsv=1';
	});

	$('#btnExportPdf').on('click', function () {
		var $frm = $('#frmFiltros');
		if (!$frm.length) {
			return;
		}
		var total = 0;
		try {
			total = parseInt($grid.jqGrid('getGridParam', 'records'), 10) || 0;
		} catch (eRec2) {}
		if (total <= 0) {
			if (typeof $.alert === 'function') {
				$.alert('No hay datos para exportar. Realice una busqueda primero.');
			} else {
				alert('No hay datos para exportar. Realice una busqueda primero.');
			}
			return;
		}
		/* Informe PDF generado en servidor (FPDF): descarga directa */
		window.location = window.location.pathname + '?' + $frm.serialize() + '&exportMonitoreoPdf=1';
	});

	actualizarHint();
	actualizarPlantaSelect();
	cargarResumen();
	setTimeout(function () {
		if (typeof exaUiAfterViewChange === 'function') {
			exaUiAfterViewChange('.exa-ui-panel');
		}
		if (typeof exaUiFitJqGrid === 'function') {
			exaUiFitJqGrid('#gridMonitoreo', '#lista .exa-ui-grid-host');
		}
		applyCols();
	}, 300);

	$(window).on('resize', function () {
		if (typeof exaUiFitJqGrid === 'function') {
			exaUiFitJqGrid('#gridMonitoreo', '#lista .exa-ui-grid-host');
		} else {
			try {
				$grid.jqGrid('setGridWidth', $grid.closest('.exa-ui-grid-host').width());
			} catch (e8) {}
		}
	});
});
