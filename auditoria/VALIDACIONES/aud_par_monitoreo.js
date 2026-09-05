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
			q: $.trim($('#fil_q').val() || '')
		};
		if (hasSucursales) {
			data.suc = $('#suc').val() || 0;
		}
		return data;
	}

	function fmtYmd(d) {
		var y = d.getFullYear();
		var m = ('0' + (d.getMonth() + 1)).slice(-2);
		var day = ('0' + d.getDate()).slice(-2);
		return y + '-' + m + '-' + day;
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
			' · Usuario: <strong>' + $.trim(usuTxt) + '</strong>';
		if (hasSucursales) {
			var sucTxt = $('#suc option:selected').text() || 'Todas';
			html += ' · Sucursal: <strong>' + $.trim(sucTxt) + '</strong>';
		}
		html += ' · Modulo: <strong>' + $.trim(orgTxt) + '</strong>' +
			' · Directorio: <strong>' + $.trim(dirTxt) + '</strong>' +
			' · Proceso: <strong>' + $.trim(pcsTxt) + '</strong>' +
			' · Evento: <strong>' + $.trim(eveTxt) + '</strong>';
		var qTxt = $.trim($('#fil_q').val() || '');
		if (qTxt) {
			html += ' · Buscar: <strong>' + $('<div>').text(qTxt).html() + '</strong>';
		}
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
					$usu.append($('<option/>').val(r.Usu_Cod).text(r.Usu_Nom));
				});
			}
			if (cur && $usu.find('option[value="' + cur + '"]').length) {
				$usu.val(cur);
			} else {
				$usu.val('0');
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
		$grid.jqGrid('setGridParam', {
			datatype: 'json',
			postData: filtrosPost(),
			page: 1
		}).trigger('reloadGrid');
		if ($('#aud-kpi-panel').is(':visible')) {
			cargarKpi();
		}
	}

	function limpiarFiltros() {
		var hoy = new Date();
		var desde = new Date(hoy.getTime() - (30 * 24 * 3600 * 1000));
		var vFrom = fmtYmd(desde);
		var vTo = fmtYmd(hoy);
		$('#from').val(vFrom);
		$('#to').val(vTo);
		try {
			$('#to').datepicker('option', 'minDate', vFrom);
			$('#from').datepicker('option', 'maxDate', vTo);
		} catch (eClr) {}
		$('#fil_q').val('');
		$('#org').val('0');
		$('#dir').val('0');
		$('#eve').val('0');
		$('#usu').val('0');
		if (hasSucursales) {
			$('#suc').val('0');
		}
		cargarDirectorios(0, function () {
			cargarProcesos(0, 0, function () {
				$('#pcs').val('0');
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

	
	/* ---- Dashboard y Graficos KPI ---- */
	var kpiStorageKey = 'aud_monitoreo_kpi_open_v1';

	function renderSparkline(fechas) {
		var $host = $('#chart-fechas-host');
		if (!fechas || !fechas.length) {
			$host.html('<p class="text-muted" style="margin:0; font-size:11px; padding:30px 0; text-align:center;">Sin actividad en el periodo</p>');
			return;
		}
		var maxVal = 1;
		$.each(fechas, function(i, f) {
			var v = parseInt(f.total, 10) || 0;
			if (v > maxVal) maxVal = v;
		});

		var svgWidth = 420;
		var svgHeight = 90;
		var padLeft = 10;
		var padRight = 10;
		var padTop = 15;
		var padBottom = 20;
		var effW = svgWidth - padLeft - padRight;
		var effH = svgHeight - padTop - padBottom;
		var step = fechas.length > 1 ? effW / (fechas.length - 1) : effW / 2;

		var points = [];
		var dots = '';
		$.each(fechas, function(i, f) {
			var v = parseInt(f.total, 10) || 0;
			var x = fechas.length > 1 ? (padLeft + i * step) : (svgWidth / 2);
			var y = padTop + effH - ((v / maxVal) * effH);
			points.push(x.toFixed(1) + ',' + y.toFixed(1));
			dots += '<circle cx="' + x.toFixed(1) + '" cy="' + y.toFixed(1) + '" r="3.5" fill="#3b82f6" stroke="#fff" stroke-width="1.5"><title>' + f.fecha + ': ' + v + ' eventos</title></circle>';
		});

		var polyline = '<polyline fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="' + points.join(' ') + '" />';
		
		// Fill area
		var areaPoints = points.slice();
		var lastX = fechas.length > 1 ? (padLeft + (fechas.length - 1) * step) : (svgWidth / 2);
		var firstX = fechas.length > 1 ? padLeft : (svgWidth / 2);
		areaPoints.push(lastX.toFixed(1) + ',' + (padTop + effH));
		areaPoints.push(firstX.toFixed(1) + ',' + (padTop + effH));
		var polygon = '<polygon fill="rgba(59, 130, 246, 0.12)" points="' + areaPoints.join(' ') + '" />';

		// Labels for first and last
		var firstDate = fechas[0].fecha ? fechas[0].fecha.substring(5) : '';
		var lastDate = fechas[fechas.length - 1].fecha ? fechas[fechas.length - 1].fecha.substring(5) : '';
		var lbls = '<text x="' + padLeft + '" y="' + (svgHeight - 4) + '" font-size="9" fill="#94a3b8">' + firstDate + '</text>';
		if (fechas.length > 1) {
			lbls += '<text x="' + (svgWidth - padRight) + '" y="' + (svgHeight - 4) + '" text-anchor="end" font-size="9" fill="#94a3b8">' + lastDate + '</text>';
		}

		var svg = '<svg viewBox="0 0 ' + svgWidth + ' ' + svgHeight + '" class="aud-sparkline-svg" preserveAspectRatio="none">' +
			polygon + polyline + dots + lbls + '</svg>';
		$host.html(svg);
	}

	function renderBarList($host, list, keyName, valColor) {
		if (!list || !list.length) {
			$host.html('<p class="text-muted" style="margin:0; font-size:11px; padding:20px 0; text-align:center;">Sin datos disponibles</p>');
			return;
		}
		var maxVal = 1;
		$.each(list, function(i, item) {
			var v = parseInt(item.total, 10) || 0;
			if (v > maxVal) maxVal = v;
		});

		var html = '';
		$.each(list.slice(0, 5), function(i, item) {
			var name = item[keyName] || 'Sin nombre';
			var v = parseInt(item.total, 10) || 0;
			var pct = Math.max(4, Math.round((v / maxVal) * 100));
			html += '<div class="aud-bar-item">' +
				'<div class="aud-bar-lbl clearfix">' +
					'<span style="float:left; max-width:70%; overflow:hidden; text-overflow:ellipsis;" title="' + $('<div>').text(name).html() + '">' + $('<div>').text(name).html() + '</span>' +
					'<span style="float:right; font-weight:700; color:#475569;">' + v + '</span>' +
				'</div>' +
				'<div class="aud-bar-track"><div class="aud-bar-fill" style="width:' + pct + '%; background:' + (valColor || '#3b82f6') + ';"></div></div>' +
			'</div>';
		});
		$host.html(html);
	}

	function cargarKpi() {
		var postData = filtrosPost();
		postData.listMonitoreoKpiAjax = 1;
		delete postData.listMonitoreoGridAjax;

		$.ajax({
			url: window.location.pathname,
			type: 'POST',
			dataType: 'json',
			data: postData,
			success: function (res) {
				if (!res) return;
				var tot = parseInt(res.total, 10) || 0;
				$('#kpi-total').text(tot);
				
				var ins = 0, upd = 0, del = 0;
				if (res.eventos && res.eventos.length) {
					$.each(res.eventos, function (i, ev) {
						var ini = (ev.Eve_Ini || '').toUpperCase();
						var cnt = parseInt(ev.total, 10) || 0;
						if (ini === 'I') ins += cnt;
						else if (ini === 'U') upd += cnt;
						else if (ini === 'D') del += cnt;
					});
				}
				$('#kpi-ins').text(ins);
				$('#kpi-upd').text(upd);
				$('#kpi-del').text(del);

				var insPct = tot > 0 ? Math.round((ins / tot) * 100) : 0;
				var updPct = tot > 0 ? Math.round((upd / tot) * 100) : 0;
				var delPct = tot > 0 ? Math.round((del / tot) * 100) : 0;
				$('#kpi-ins-pct').text(insPct + '% del total');
				$('#kpi-upd-pct').text(updPct + '% del total');
				$('#kpi-del-pct').text(delPct + '% del total');

				renderSparkline(res.fechas || []);
				renderBarList($('#chart-modulos-host'), res.modulos || [], 'modulo', '#6366f1');
				renderBarList($('#chart-usuarios-host'), res.usuarios || [], 'usuario', '#0ea5e9');
			}
		});
	}

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
	}
	initCalendarios();

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
		{ name: 'Log_Cod', index: 'Log_Cod', width: 55, align: 'center', sorttype: 'int' },
		{ name: 'Fecha', index: 'Fecha', width: 90, align: 'center' },
		{ name: 'Hora', index: 'Hora', width: 70, align: 'center' },
		{ name: 'Empresa', index: 'Empresa', width: 120, align: 'left' },
		{ name: 'Sucursal', index: 'Sucursal', width: 110, align: 'left', hidden: !hasSucursales },
		{ name: 'Usuario', index: 'Usuario', width: 120, align: 'left' },
		{ name: 'Modulo', index: 'Modulo', width: 100, align: 'left' },
		{ name: 'Directorio', index: 'Directorio', width: 110, align: 'left' },
		{ name: 'Proceso', index: 'Proceso', width: 110, align: 'left' },
		{ name: 'Actividad', index: 'Actividad', width: 150, align: 'left' },
		{ name: 'Detalle', index: 'Detalle', width: 200, align: 'left' },
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

	$grid.jqGrid({
		url: window.location.pathname,
		mtype: 'POST',
		datatype: 'json',
		postData: filtrosPost(),
		colNames: colNames,
		colModel: colModel,
		jsonReader: {
			root: 'rows',
			page: 'page',
			total: 'total',
			records: 'records',
			repeatitems: false,
			id: 'Log_Cod'
		},
		pager: '#gridMonitoreoPager',
		rowNum: 25,
		rowList: [10, 25, 50, 100],
		sortname: 'Log_Cod',
		sortorder: 'desc',
		viewrecords: true,
		rownumbers: false,
		autowidth: true,
		shrinkToFit: true,
		height: 360,
		altRows: true,
		caption: 'Resultados de la busqueda',
		loadComplete: function () {
			applyCols();
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

	applyCols();

	$('.aud-col-toggle').on('click', function () {
		saveCols();
		applyCols();
	});

	$('#btnToggleKpi').on('click', function () {
		var $p = $('#aud-kpi-panel');
		if ($p.is(':visible')) {
			$p.slideUp(180, function () {
				if (typeof exaUiFitJqGrid === 'function') {
					exaUiFitJqGrid('#gridMonitoreo', '#lista .exa-ui-grid-host');
				}
			});
			try { localStorage.setItem(kpiStorageKey, '0'); } catch(eKpi) {}
		} else {
			$p.slideDown(180, function () {
				cargarKpi();
				if (typeof exaUiFitJqGrid === 'function') {
					exaUiFitJqGrid('#gridMonitoreo', '#lista .exa-ui-grid-host');
				}
			});
			try { localStorage.setItem(kpiStorageKey, '1'); } catch(eKpi) {}
		}
	});

	$('#btnBuscar').on('click', function () {
		buscar();
	});

	$('#btnLimpiar').on('click', function () {
		limpiarFiltros();
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
			cargarProcesos(org, $('#dir').val() || 0, actualizarHint);
		});
	});

	$('#dir').on('change', function () {
		var org = $('#org').val() || 0;
		var dir = $(this).val() || 0;
		cargarProcesos(org, dir, actualizarHint);
	});

	$('#pcs, #eve, #usu, #from, #to, #fil_q').on('change input', function () {
		actualizarHint();
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
		var total = 0;
		try {
			total = parseInt($grid.jqGrid('getGridParam', 'records'), 10) || 0;
		} catch (eRec2) {}
		if (total <= 0) {
			if (typeof $.alert === 'function') {
				$.alert('No hay datos para exportar. Realice una busqueda primero.');
			} else {
				alert('No hay datos para exportar.');
			}
			return;
		}
		if (typeof $grid.jqGrid === 'function' && typeof $grid.jqGrid('printGrid') === 'function') {
			$grid.jqGrid('printGrid', {
				nombre: 'Monitoreo de actividades',
				removeHiddens: true,
				removeCols: ['acciones'],
				caption: true
			});
			return;
		}
		/* Fallback: ventana imprimible (Guardar como PDF) */
		try {
			var html = $grid.jqGrid('exportGridHTML', {
				caption: true,
				removeHiddens: true,
				removeCols: ['acciones'],
				footer: false,
				generated: true
			});
			var w = window.open('', '_blank');
			if (!w) {
				alert('Permita ventanas emergentes para exportar a PDF.');
				return;
			}
			w.document.write('<!DOCTYPE html><html><head><title>Monitoreo de actividades</title>');
			w.document.write('<style>body{font-family:Arial,sans-serif;font-size:11px;padding:12px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #999;padding:4px 6px;} th{background:#eef3f8;} h3{margin:0 0 10px 0;}</style>');
			w.document.write('</head><body>');
			w.document.write('<h3>Monitoreo de actividades</h3>');
			w.document.write(html);
			w.document.write('</body></html>');
			w.document.close();
			w.focus();
			setTimeout(function () { w.print(); }, 300);
		} catch (ePdf) {
			alert('No se pudo generar el PDF.');
		}
	});

	actualizarHint();
	try {
		if (localStorage.getItem(kpiStorageKey) === '1') {
			$('#aud-kpi-panel').show();
			cargarKpi();
		}
	} catch(eKpiInit) {}
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
