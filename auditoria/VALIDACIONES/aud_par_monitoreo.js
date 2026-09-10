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
			eve: $('#eve').val() || 0
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

	$('#pcs, #eve, #usu, #from, #to').on('change', function () {
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
