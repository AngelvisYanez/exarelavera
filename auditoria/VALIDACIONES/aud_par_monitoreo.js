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
			return;
		}
		$.getJSON(window.location.pathname, { plantaProcesoAjax: 1, pcs: pcs }, function (resp) {
			if (resp && resp.success && resp.tiene) {
				$wrap.show();
			} else {
				$wrap.hide();
				$sel.val('0');
			}
		}).fail(function () {
			$wrap.hide();
			$sel.val('0');
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
		var from = $('#from').val() || '';
		var to = $('#to').val() || '';
		var usuTxt = $('#usu option:selected').text() || 'Todos';
		var orgTxt = $('#org option:selected').text() || 'Todos';
		var dirTxt = $('#dir option:selected').text() || 'Todos';
		var pcsTxt = $('#pcs option:selected').text() || 'Todos';
		var eveTxt = $('#eve option:selected').text() || 'Todos';
		var periodo = (from && to) ? (from + ' a ' + to) : 'sin periodo';
		var html = 'Filtros activos: <strong>' + periodo + '</strong>' +
			' &middot; Usuario: <strong>' + $.trim(usuTxt) + '</strong>';
		if (hasSucursales) {
			var sucTxt = $('#suc option:selected').text() || 'Todas';
			html += ' &middot; Sucursal: <strong>' + $.trim(sucTxt) + '</strong>';
		}
		html += ' &middot; Modulo: <strong>' + $.trim(orgTxt) + '</strong>' +
			' &middot; Directorio: <strong>' + $.trim(dirTxt) + '</strong>' +
			' &middot; Proceso: <strong>' + $.trim(pcsTxt) + '</strong>' +
			' &middot; Evento: <strong>' + $.trim(eveTxt) + '</strong>';
		$('#audSearchHint').html(html);
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
			if ($.fn.chosen) {
				$usu.trigger('chosen:updated');
			}
			if (typeof done === 'function') {
				done();
			}
		}).fail(function () {
			if ($.fn.chosen) {
				$usu.trigger('chosen:updated');
			}
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
				return;
			}
			var cards = '';
			function card(num, lbl, cls, tip) {
				return '<div class="aud-resumen-card ' + (cls || '') + '" title="' + (tip || lbl) + '">' +
					'<span class="aud-rc-num">' + num + '</span><span class="aud-rc-lbl">' + lbl + '</span></div>';
			}
			var pe = r.por_evento || {};
			cards += card(r.total || 0, 'Actividades', 'aud-rc-total', 'Actividades registradas en el periodo');
			cards += card(pe.I || 0, 'Insertados', 'aud-rc-i', 'Registros insertados');
			cards += card(pe.U || 0, 'Actualizados', 'aud-rc-u', 'Registros actualizados');
			cards += card(pe.D || 0, 'Eliminados', 'aud-rc-d', 'Registros eliminados o anulados');
			cards += card(pe.F || 0, 'Otras', 'aud-rc-f', 'Otras actividades (no I/U/D)');
			cards += card(r.usuarios_distintos || 0, 'Usuarios', '', 'Usuarios distintos con actividad');
			var lists = '<div class="aud-resumen-cols">';
			lists += '<div class="aud-resumen-col"><strong>Top modulos</strong><ul>';
			if (r.top_modulos) {
				$.each(r.top_modulos, function (k, v) { lists += '<li>' + $.trim(k) + ' (' + v + ')</li>'; });
			} else if (r.total > 0) {
				lists += '<li class="text-muted">Sin modulo</li>';
			}
			lists += '</ul></div><div class="aud-resumen-col"><strong>Top usuarios</strong><ul>';
			if (r.top_usuarios) {
				$.each(r.top_usuarios, function (k, v) { lists += '<li>' + $.trim(k) + ' (' + v + ')</li>'; });
			} else if (r.total > 0) {
				lists += '<li class="text-muted">-</li>';
			}
			lists += '</ul></div><div class="aud-resumen-col"><strong>Top procesos</strong><ul>';
			if (r.top_procesos) {
				$.each(r.top_procesos, function (k, v) { lists += '<li>' + $.trim(k) + ' (' + v + ')</li>'; });
			} else if (r.total > 0) {
				lists += '<li class="text-muted">-</li>';
			}
			lists += '</ul></div></div>';
			var obs = '<p class="aud-resumen-obs">' +
				((r.observaciones && r.observaciones.length) ? r.observaciones.join(' ') : 'Sin observaciones.') +
				'</p>';
			$body.html('<div class="aud-resumen-cards">' + cards + '</div>' + lists + obs);
			$wrap.show();
		}).fail(function () {
			$wrap.hide();
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
		if ($.fn.chosen) {
			$('#usu').trigger('chosen:updated');
		}
		if (hasSucursales) {
			$('#suc').val('0');
		}
		cargarDirectorios(0, function () {
			cargarProcesos(0, 0, function () {
				$('#pcs').val('0');
				if (typeof audFiltrosBadgeActualizar === 'function') {
					audFiltrosBadgeActualizar();
				}
				buscar();
			});
		});
	}

	function verDetalle(logCod) {
		$('#detalleContenido').html('<p class="text-muted" style="padding:12px 4px;">Cargando detalle...</p>');
		if (!$('#detalleDialog').hasClass('ui-dialog-content')) {
			$('#detalleDialog').dialog({
				autoOpen: false,
				modal: true,
				resizable: false,
				width: Math.min(760, $(window).width() - 32),
				maxHeight: Math.min(620, $(window).height() - 40),
				height: 'auto',
				appendTo: '.exa-ui-panel',
				dialogClass: 'exa-ui-panel exa-ui-dialog',
				buttons: [{ text: 'Cerrar', click: function () { $(this).dialog('close'); } }]
			});
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
				$('#detalleContenido').html('<div class="alert alert-danger" style="margin:8px 0;">No se pudo cargar el detalle.</div>');
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

	/* ---- Chosen buscador para usuario ---- */
	function initChosen() {
		var $usu = $('#usu');
		if (!$usu.length || !$.fn.chosen) {
			return;
		}
		try {
			$usu.chosen('destroy');
		} catch (e) {}

		$usu.chosen({
			width: '100%',
			search_contains: true,
			no_results_text: 'No se encontraron usuarios'
		});
	}
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
		var $body = $('#audResumenContenido');
		var ocultar = $body.is(':visible');
		$body.toggle();
		$btn.text(ocultar ? 'Mostrar' : 'Ocultar');
		if (typeof exaUiFitJqGrid === 'function') {
			exaUiFitJqGrid('#gridMonitoreo', '#lista .exa-ui-grid-host');
		}
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

	/* ---- Filtros avanzados colapsables (Sucursal/Modulo/Directorio/Proceso/Planta/Evento) ---- */
	function audFiltrosBadgeActualizar() {
		var campos = ['#suc', '#org', '#dir', '#pcs', '#eve', '#filPlanta'];
		var n = 0;
		$.each(campos, function (i, sel) {
			var $el = $(sel);
			if (!$el.length) {
				return;
			}
			if (sel === '#filPlanta' && !$('#audFilPlantaWrap').is(':visible')) {
				return;
			}
			var v = $el.val();
			if (v && String(v) !== '0') {
				n++;
			}
		});
		var $b = $('#filtrosBadge');
		if (n > 0) {
			$b.text(n).show();
		} else {
			$b.hide();
		}
	}

	$('#btnFiltrosToggle').on('click', function () {
		$('#audFiltrosRow').slideToggle(150);
	});

	$('#suc, #org, #dir, #pcs, #eve, #filPlanta').on('change', function () {
		audFiltrosBadgeActualizar();
	});

	/* Si la pagina se sirvio con filtros avanzados ya aplicados (enlace
	   compartido o recarga), se muestra la fila expandida desde el inicio */
	if (($('#suc').val() && $('#suc').val() !== '0') ||
		($('#org').val() && $('#org').val() !== '0') ||
		($('#dir').val() && $('#dir').val() !== '0') ||
		($('#pcs').val() && $('#pcs').val() !== '0') ||
		($('#eve').val() && $('#eve').val() !== '0')) {
		$('#audFiltrosRow').show();
	}
	audFiltrosBadgeActualizar();

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
