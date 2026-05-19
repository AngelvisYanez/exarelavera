/**
 * Grid local manifiesto_mensajes
 * Pantalla: man_adm_chats_lista.php — URL del PHP fija en JS (como fac_mod_fac_ven_3.2.php con "fac_alt_fac_ven_3.2.php").
 * Requiere: jQuery, jqGrid (createGrid)
 */
(function (window, $) {
	'use strict';

	var URL_MENSAJES_BD_AJAX = '?listManifiestoMensajesAjax=1';
	var URL_CHATS_LISTA_PHP = 'man_adm_chats_lista.php';
	var MSJ_TIP_FILTRO = ['SCH', 'SVH', 'SPL', 'DAN', 'CAN', 'TRE'];
	/** Texto para columna «Descripción» en tabla por tipo (Gráficos) */
	var MSJ_TIP_DESCRIPCION_TABLA = {
		SCH: 'Sanción al chofer (mensaje WhatsApp).',
		SVH: 'Sanción al vehículo / placa.',
		SPL: 'Sanción a la planta.',
		DAN: 'Depósito de anticipo.',
		CAN: 'Confirmación de anticipo.',
		TRE: 'Tiempo en Relavera del vehículo.'
	};
	/** Nombre legible corto para leyenda del gráfico circular */
	var MSJ_TIP_NOMBRES_CORTOS = {
		SCH: 'Sanción Chofer',
		SVH: 'Sanción Vehículo',
		SPL: 'Sanción Planta',
		DAN: 'Depósito de anticipo',
		CAN: 'Confirmación de anticipo',
		TRE: 'Tiempo Relavera'
	};
	/** Color por código de tipo (gráfico circular). */
	var MSJ_TIP_COLOR_GRAF = {
		SCH: 'rgba(217,83,79,0.92)',
		SVH: 'rgba(240,173,78,0.95)',
		SPL: 'rgba(147,112,219,0.92)',
		DAN: 'rgba(51,122,183,0.92)',
		CAN: 'rgba(92,184,92,0.92)',
		TRE: 'rgba(91,192,222,0.95)'
	};
	/** Tamaño de página del listado (paginación cliente) */
	var GRID_MSJ_PAGE_SIZE = 500;

	/** Sufijos de ids de formularios en man_adm_chats_lista.php */
	var SUF_FILTROS_LISTA = 'lista';
	var SUF_FILTROS_GRAF = 'graficos';

	function datosFiltrosMensajesDesdeSufijo(suf) {
		var data = {};
		var raw = $('#filtro_pla_cod_' + suf).val();
		var n = parseInt(raw, 10);
		if (raw && !isNaN(n) && n > 0) {
			data.Pla_Cod = n;
		}
		var tip = $('#filtro_msj_tip_' + suf).val();
		if (tip && $.inArray(tip, MSJ_TIP_FILTRO) >= 0) {
			data.Msj_Tip = tip;
		}
		var fd = fechaFiltroMsjYmd($('#filtro_msj_fec_desde_' + suf).val());
		var fh = fechaFiltroMsjYmd($('#filtro_msj_fec_hasta_' + suf).val());
		if (fd) {
			data.Msj_Fec_Desde = fd;
		}
		if (fh) {
			data.Msj_Fec_Hasta = fh;
		}
		var txPrs = String($('#filtro_msj_prs_bus_tex_' + suf).val() || '').trim();
		if (txPrs.length > 120) {
			txPrs = txPrs.substring(0, 120);
		}
		if (txPrs !== '') {
			data.Msj_Prs_Bus_Tex = txPrs;
			var tipPrs = $('#form_filtros_mensajes_' + suf + ' input[name="Msj_Prs_Bus_Tip_' + suf + '"]:checked').val();
			data.Msj_Prs_Bus_Tip = (tipPrs === 'AP') ? 'AP' : 'CHO';
		}
		return data;
	}

	function datosFiltrosMensajesAjax() {
		return datosFiltrosMensajesDesdeSufijo(SUF_FILTROS_LISTA);
	}

	/** Leyenda legible de filtros (impresión PDF gráficos). */
	function textoLeyendaFiltrosMensajesChats(suf) {
		var data = datosFiltrosMensajesDesdeSufijo(suf);
		var parts = [];
		if (data.Pla_Cod) {
			parts.push('Planta: ' + String($('#filtro_pla_cod_' + suf + ' option:selected').text() || '').trim());
		} else {
			parts.push('Planta: todas');
		}
		if (data.Msj_Tip) {
			parts.push('Tipo mensaje: ' + data.Msj_Tip);
		} else {
			parts.push('Tipo mensaje: todos');
		}
		if (data.Msj_Fec_Desde) {
			parts.push('Envío desde: ' + data.Msj_Fec_Desde);
		}
		if (data.Msj_Fec_Hasta) {
			parts.push('Envío hasta: ' + data.Msj_Fec_Hasta);
		}
		if (data.Msj_Prs_Bus_Tex) {
			parts.push('Persona (' + (data.Msj_Prs_Bus_Tip === 'AP' ? 'administrador planta' : 'chofer') + '): ' + data.Msj_Prs_Bus_Tex);
		}
		return parts.join(' · ');
	}

	function tablaResumenChatsTieneFilasDatos($tabla) {
		if (!$tabla || !$tabla.length) {
			return false;
		}
		var ok = false;
		$tabla.find('tbody tr').each(function () {
			if ($(this).find('td[colspan]').length > 0) {
				return;
			}
			ok = true;
		});
		return ok;
	}

	function clonarTablaImpresionGraficosChats(selectorTabla) {
		var $t = $(selectorTabla);
		if (!$t.length) {
			return '';
		}
		var $c = $t.clone(false, false);
		$c.removeAttr('id');
		$c.find('[id]').removeAttr('id');
		$c.removeClass('chats-resumen-tabla').addClass('chats-print-resumen-tabla');
		return $c.prop('outerHTML') || '';
	}

	function destruirChartMensajesPorPlanta() {
		if (window._chartMsjPorPlantaRelaveraChats && typeof window._chartMsjPorPlantaRelaveraChats.destroy === 'function') {
			try {
				window._chartMsjPorPlantaRelaveraChats.destroy();
			} catch (e0) { /* ignore */ }
		}
		window._chartMsjPorPlantaRelaveraChats = null;
	}

	function destruirChartMensajesPorTipo() {
		if (window._chartMsjPorTipoRelaveraChats && typeof window._chartMsjPorTipoRelaveraChats.destroy === 'function') {
			try {
				window._chartMsjPorTipoRelaveraChats.destroy();
			} catch (eT0) { /* ignore */ }
		}
		window._chartMsjPorTipoRelaveraChats = null;
	}

	function descripcionMsjTipoTabla(cod) {
		var c = String(cod == null ? '' : cod).toUpperCase().trim();
		if (c === '') {
			return 'Mensaje sin tipo asignado en base de datos.';
		}
		if (MSJ_TIP_DESCRIPCION_TABLA[c]) {
			return MSJ_TIP_DESCRIPCION_TABLA[c];
		}
		return 'Tipo «' + c + '» (sin catálogo en pantalla).';
	}

	function codigoMsjTipoTabla(cod) {
		var c = String(cod == null ? '' : cod).toUpperCase().trim();
		return c === '' ? '—' : c;
	}
	function pintarTablasResumenGraficosChats(rowsPla, rowsTip) {
		var $tbP = $('#tablaMsjResumenPlanta tbody');
		var $tbT = $('#tablaMsjResumenTipo tbody');
		if (!$tbP.length || !$tbT.length) {
			return;
		}
		$tbP.empty();
		$tbT.empty();

		// Reiniciar contadores KPI a 0
		$('#kpi_cnt_sch').text('0');
		$('#kpi_cnt_svh').text('0');
		$('#kpi_cnt_spl').text('0');
		$('#kpi_cnt_dan').text('0');
		$('#kpi_cnt_can').text('0');
		$('#kpi_cnt_tre').text('0');

		rowsPla = $.isArray(rowsPla) ? rowsPla : [];
		rowsTip = $.isArray(rowsTip) ? rowsTip : [];
		if (rowsPla.length === 0) {
			$tbP.append($('<tr/>').append($('<td colspan="2" class="text-muted"/>').text('Sin datos')));
			$('#tablaMsjResumenPlantaTotal').text('0');
		} else {
			var sumPla = 0;
			$.each(rowsPla, function (i, r) {
				if (!r) {
					return;
				}
				var nom = r.Pla_Nom != null ? String(r.Pla_Nom) : '';
				var cnt = parseInt(r.Msj_Cnt, 10);
				if (isNaN(cnt)) {
					cnt = 0;
				}
				sumPla += cnt;
				var $tr = $('<tr/>');
				$tr.append($('<td/>').text(nom || ('Planta #' + (r.Pla_Cod != null ? r.Pla_Cod : ''))));
				$tr.append($('<td class="text-right"/>').text(String(cnt)));
				$tbP.append($tr);
			});
			$('#tablaMsjResumenPlantaTotal').text(String(sumPla));
		}
		if (rowsTip.length === 0) {
			$tbT.append($('<tr/>').append($('<td colspan="3" class="text-muted"/>').text('Sin datos para los filtros actuales.')));
			$('#tablaMsjResumenTipoTotal').text('0');
			$('#kpi_cnt_total').text('0');
		} else {
			var sumTip = 0;
			$.each(rowsTip, function (i, r) {
				if (!r) {
					return;
				}
				var cod = r.Msj_Tip != null ? String(r.Msj_Tip) : '';
				var cnt = parseInt(r.Msj_Cnt, 10);
				if (isNaN(cnt)) {
					cnt = 0;
				}
				sumTip += cnt;
				var $tr = $('<tr/>');
				$tr.append($('<td/>').text(codigoMsjTipoTabla(cod)));
				$tr.append($('<td/>').text(descripcionMsjTipoTabla(cod)));
				$tr.append($('<td class="text-right"/>').text(String(cnt)));
				$tbT.append($tr);

				// Actualizar valores de los cuadros KPI correspondientes
				var c = String(cod).toUpperCase().trim();
				if (c === 'SCH') $('#kpi_cnt_sch').text(String(cnt));
				else if (c === 'SVH') $('#kpi_cnt_svh').text(String(cnt));
				else if (c === 'SPL') $('#kpi_cnt_spl').text(String(cnt));
				else if (c === 'DAN') $('#kpi_cnt_dan').text(String(cnt));
				else if (c === 'CAN') $('#kpi_cnt_can').text(String(cnt));
				else if (c === 'TRE') $('#kpi_cnt_tre').text(String(cnt));
			});
			$('#tablaMsjResumenTipoTotal').text(String(sumTip));
			$('#kpi_cnt_total').text(String(sumTip));
		}
	}

	function coloresBarrasPlanta(n) {
		var base = ['rgba(51,122,183,0.88)', 'rgba(92,184,92,0.88)', 'rgba(240,173,78,0.92)', 'rgba(217,83,79,0.88)', 'rgba(91,192,222,0.92)', 'rgba(147,112,219,0.88)'];
		var out = [];
		for (var i = 0; i < n; i++) {
			out.push(base[i % base.length]);
		}
		return out;
	}

	function colorPieSlicePorTipo(codNormalizado, indiceFallback) {
		var c = String(codNormalizado == null ? '' : codNormalizado).toUpperCase().trim();
		if (c !== '' && MSJ_TIP_COLOR_GRAF[c]) {
			return MSJ_TIP_COLOR_GRAF[c];
		}
		return coloresBarrasPlanta(12)[indiceFallback % 12];
	}

	function pintarGraficoMensajesPorTipo(rowsTip) {
		destruirChartMensajesPorTipo();
		var canvas = document.getElementById('chartMsjPorTipo');
		if (!canvas || typeof Chart === 'undefined') {
			return;
		}
		var ctx = canvas.getContext('2d');
		rowsTip = $.isArray(rowsTip) ? rowsTip : [];
		var labels = [];
		var valores = [];
		var cods = [];
		$.each(rowsTip, function (i, r) {
			if (!r) {
				return;
			}
			var cnt = parseInt(r.Msj_Cnt, 10);
			if (isNaN(cnt) || cnt <= 0) {
				return;
			}
			var cod = r.Msj_Tip != null ? String(r.Msj_Tip).toUpperCase().trim() : '';
			cods.push(cod);
			labels.push(cod === '' ? '(Sin tipo)' : cod);
			valores.push(cnt);
		});
		if (valores.length === 0) {
			try {
				ctx.clearRect(0, 0, canvas.width, canvas.height);
			} catch (eC) { /* ignore */ }
			return;
		}
		var bgColors = [];
		var vi;
		for (vi = 0; vi < cods.length; vi++) {
			bgColors.push(colorPieSlicePorTipo(cods[vi], vi));
		}
		window._chartMsjPorTipoRelaveraChats = new Chart(ctx, {
			type: 'doughnut',
			data: {
				labels: labels,
				datasets: [{
					data: valores,
					backgroundColor: bgColors,
					borderWidth: 2,
					borderColor: '#fff'
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				cutout: '52%',
				plugins: {
					legend: {
						position: 'right',
						labels: {
							boxWidth: 14,
							padding: 10,
							font: { size: 11 },
							generateLabels: function (chart) {
								var data = chart.data;
								var ds = data.datasets[0];
								var meta = chart.getDatasetMeta(0);
								if (!data.labels || !ds || !ds.data) {
									return [];
								}
								var tot = 0;
								var k;
								for (k = 0; k < ds.data.length; k++) {
									tot += Number(ds.data[k]) || 0;
								}
								return data.labels.map(function (lab, idx) {
									var v = Number(ds.data[idx]) || 0;
									var pct = tot > 0 ? ((v / tot) * 100).toFixed(1) : '0.0';
									var hidden = meta.data[idx] ? !!meta.data[idx].hidden : false;
									var fullName = MSJ_TIP_NOMBRES_CORTOS[lab] || lab;
									var labelText = fullName + ' (' + lab + ')';
									return {
										text: labelText + ': ' + v + ' (' + pct + '%)',
										fillStyle: ds.backgroundColor[idx],
										strokeStyle: ds.borderColor,
										lineWidth: typeof ds.borderWidth === 'number' ? ds.borderWidth : 2,
										hidden: hidden,
										index: idx,
										datasetIndex: 0
									};
								});
							}
						}
					},
					tooltip: {
						callbacks: {
							label: function (context) {
								var idx = context.dataIndex;
								var v = Number(context.dataset.data[idx]) || 0;
								var arr = context.dataset.data;
								var tot = 0;
								var j;
								for (j = 0; j < arr.length; j++) {
									tot += Number(arr[j]) || 0;
								}
								var pct = tot > 0 ? ((v / tot) * 100).toFixed(1) : '0.0';
								var fullName = MSJ_TIP_NOMBRES_CORTOS[context.label] || context.label;
								var labelText = fullName + ' (' + context.label + ')';
								return ' ' + labelText + ': ' + v + ' mensajes (' + pct + '%)';
							}
						}
					}
				}
			},
			plugins: [{
				id: 'sliceLabels',
				afterDatasetsDraw: function (chart) {
					var ctx = chart.ctx;
					ctx.save();
					chart.data.datasets.forEach(function (dataset, i) {
						var meta = chart.getDatasetMeta(i);
						meta.data.forEach(function (element, index) {
							var dataVal = dataset.data[index];
							if (!dataVal || dataVal === 0) return;

							// Calcular el punto medio exacto de la porción circular
							var center = typeof element.getCenterPoint === 'function' ? element.getCenterPoint() : null;
							if (!center) return;

							// Estilos de texto premium
							ctx.fillStyle = '#ffffff';
							ctx.font = 'bold 11px "Helvetica Neue", Helvetica, Arial, sans-serif';
							ctx.textAlign = 'center';
							ctx.textBaseline = 'middle';

							// Sombreado elegante para alta legibilidad sobre cualquier color
							ctx.shadowColor = 'rgba(0, 0, 0, 0.6)';
							ctx.shadowBlur = 4;
							ctx.shadowOffsetX = 0;
							ctx.shadowOffsetY = 1;

							// Pintar el número
							ctx.fillText(dataVal, center.x, center.y);
						});
					});
					ctx.restore();
				}
			}]
		});
	}

	function cargarGraficoMensajesPorPlanta() {
		var $mc = $('#bd_mensajes_chart_meta');
		if (!$mc.length) {
			return;
		}
		$mc.removeClass('text-muted text-danger').addClass('text-info').text('Cargando gráfico…');
		var data = $.extend({ listManifiestoMensajesChartAjax: 1 }, datosFiltrosMensajesDesdeSufijo(SUF_FILTROS_GRAF));
		$.ajax({
			url: URL_CHATS_LISTA_PHP,
			type: 'GET',
			dataType: 'json',
			data: data,
			success: function (d) {
				if (!d || !d.success) {
					$mc.removeClass('text-info').addClass('text-danger').text(
						d && d.message ? String(d.message) : 'La respuesta no es válida.'
					);
					destruirChartMensajesPorPlanta();
					destruirChartMensajesPorTipo();
					pintarTablasResumenGraficosChats([], []);
					return;
				}
				var rowsPla = $.isArray(d.porPlanta) ? d.porPlanta : ($.isArray(d.rows) ? d.rows : []);
				var rowsTip = $.isArray(d.porTipo) ? d.porTipo : [];
				pintarTablasResumenGraficosChats(rowsPla, rowsTip);

				var labels = [];
				var valores = [];
				$.each(rowsPla, function (i, r) {
					if (!r) {
						return;
					}
					var nom = r.Pla_Nom != null ? String(r.Pla_Nom) : '';
					var cnt = parseInt(r.Msj_Cnt, 10);
					if (isNaN(cnt)) {
						cnt = 0;
					}
					labels.push(nom || ('Planta ' + (r.Pla_Cod != null ? r.Pla_Cod : '')));
					valores.push(cnt);
				});

				var sumP = 0;
				for (var sp = 0; sp < valores.length; sp++) {
					sumP += valores[sp];
				}

				var sumTipPos = 0;
				var nTipPos = 0;
				$.each(rowsTip, function (i, rt) {
					if (!rt) {
						return;
					}
					var c0 = parseInt(rt.Msj_Cnt, 10);
					if (isNaN(c0) || c0 <= 0) {
						return;
					}
					sumTipPos += c0;
					nTipPos += 1;
				});

				if (typeof Chart === 'undefined') {
					$mc.removeClass('text-info').addClass('text-danger').text('Chart.js no está cargado.');
					destruirChartMensajesPorPlanta();
					destruirChartMensajesPorTipo();
					return;
				}

				var canvasPla = document.getElementById('chartMsjPorPlanta');
				destruirChartMensajesPorPlanta();
				if (canvasPla) {
					var ctx = canvasPla.getContext('2d');
					var n = valores.length;
					if (n === 0) {
						try {
							ctx.clearRect(0, 0, canvasPla.width, canvasPla.height);
						} catch (e1) { /* ignore */ }
					} else {
						window._chartMsjPorPlantaRelaveraChats = new Chart(ctx, {
							type: 'bar',
							data: {
								labels: labels,
								datasets: [{
									label: 'Cantidad de mensajes',
									data: valores,
									backgroundColor: coloresBarrasPlanta(n),
									borderWidth: 1,
									borderColor: 'rgba(0,0,0,0.06)'
								}]
							},
							options: {
								responsive: true,
								maintainAspectRatio: false,
								plugins: {
									legend: { display: true, position: 'top' }
								},
								scales: {
									y: {
										beginAtZero: true,
										ticks: { precision: 0 }
									},
									x: {
										ticks: {
											autoSkip: true,
											maxRotation: 50,
											minRotation: 0,
											maxTicksLimit: 28
										}
									}
								}
							}
						});
					}
				}

				pintarGraficoMensajesPorTipo(rowsTip);

				var partesMeta = [];
				if (valores.length === 0) {
					partesMeta.push('Barras por planta: sin datos');
				} else {
					partesMeta.push('Barras: ' + valores.length + ' planta(s), ' + sumP + ' mensaje(s)');
				}
				if (nTipPos === 0) {
					partesMeta.push('Circular por tipo: sin datos');
				} else {
					partesMeta.push('Circular: ' + nTipPos + ' tipo(s), ' + sumTipPos + ' mensaje(s) · % sobre ese total');
				}
				partesMeta.push(rowsTip.length + ' fila(s) en tabla por tipo');
				$mc.removeClass('text-info text-danger').addClass('text-muted').text(partesMeta.join(' · '));
			},
			error: function (xhr) {
				var msg = 'No se pudo cargar el gráfico.';
				if (xhr && xhr.status) {
					msg += ' (HTTP ' + xhr.status + ').';
				}
				$mc.removeClass('text-info').addClass('text-danger').text(msg);
				destruirChartMensajesPorPlanta();
				destruirChartMensajesPorTipo();
				pintarTablasResumenGraficosChats([], []);
			}
		});
	}

	function fechaFiltroMsjYmd(v) {
		var s = String(v == null ? '' : v).trim();
		if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) {
			return '';
		}
		var p = s.split('-');
		var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
		if (isNaN(d.getTime())) {
			return '';
		}
		if (d.getFullYear() !== parseInt(p[0], 10) || d.getMonth() !== parseInt(p[1], 10) - 1 || d.getDate() !== parseInt(p[2], 10)) {
			return '';
		}
		return s;
	}

	/**
	 * colModel del jqGrid manifiesto_mensajes (misma estructura que en pantalla)
	 */
	function escAttr(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}

	function fmtTexMsj(cv) {
		var t = cv == null ? '' : String(cv);
		if (t.length <= 140) return t;
		return t.slice(0, 140) + '\u2026';
	}

	function colModelMensajesManifiesto() {
		return [
			{ name: 'Msj_Cod', label: 'Cód.', width: 42, align: 'center', key: true, sorttype: 'integer', hidden: true },
			{ name: 'Pla_Cod', label: 'Pla', width: 36, align: 'center', sorttype: 'integer', hidden: true },
			{ name: 'Pla_Nom', label: 'Planta', width: 110 },
			{
				name: 'Pla_Admin_Nom', label: 'Admin. planta', width: 130,
				formatter: function (cv) {
					var t = cv == null ? '' : String(cv).trim();
					if (t === '') return '<span class="text-muted">—</span>';
					return $('<span/>').text(t).html();
				}
			},
			{
				name: 'Pla_Admin_Tel', label: 'Tel. admin.', width: 88, align: 'center',
				formatter: function (cv) {
					var t = cv == null ? '' : String(cv).trim();
					if (t === '') return '<span class="text-muted">—</span>';
					return $('<span/>').text(t).html();
				}
			},
			{ name: 'Man_Cod', label: 'Man', width: 36, align: 'center', sorttype: 'integer', hidden: true },
			{ name: 'Man_Num', label: 'N° man.', width: 52, align: 'center' },
			{ name: 'Man_Fec', label: 'F. manif.', width: 72, align: 'center' },
			{ name: 'Veh_Cod', label: 'Veh', width: 36, align: 'center', sorttype: 'integer' },
			{ name: 'Veh_Pla', label: 'Placa', width: 62, align: 'center' },
			{ name: 'Cho_Cod', label: 'Cho', width: 36, align: 'center', sorttype: 'integer', hidden: true },
			{ name: 'Chofer_Nom', label: 'Chofer', width: 120 },
			{ name: 'Msj_Id', label: 'Msj_Id', width: 72, align: 'center', sorttype: 'integer', hidden: true },
			{
				name: 'Msj_Tip', label: 'Tipo', width: 140,
				formatter: function (cv) {
					var cod = cv == null ? '' : String(cv).toUpperCase().trim();
					if (cod === '') return '<span class="text-muted">—</span>';
					var fullName = MSJ_TIP_NOMBRES_CORTOS[cod] || cod;
					return $('<span/>').text(fullName).html();
				}
			},
			{
				name: 'Msj_Tex',
				label: 'Texto',
				width: 200,
				classes: 'bd-msj-tex',
				cellattr: function (rowId, val, raw) {
					var full = raw && raw.Msj_Tex != null ? String(raw.Msj_Tex) : '';
					return ' title="' + escAttr(full) + '"';
				},
				formatter: function (cv) {
					return fmtTexMsj(cv == null ? '' : cv);
				}
			},
			{
				name: 'Msj_Img',
				label: 'Imagen',
				width: 44,
				align: 'center',
				formatter: function (cv) {
					if (!cv) return '<span class="text-muted">—</span>';
					return '<a href="' + escAttr(cv) + '" target="_blank" rel="noopener">ver</a>';
				}
			},
			{ name: 'Msj_Fec', label: 'F. envío', width: 72, align: 'center', hidden: true },
			{ name: 'Msj_Sys', label: 'Fecha Envio', width: 130, align: 'center' }
		];
	}

	function pintarEstadoMensajes($g) {
		var data = $g.jqGrid('getGridParam', 'data') || [];
		$.each(data, function (i, v) {
			if (v && String(v.Msj_Est) === 'I') {
				$('#' + v.Msj_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
			}
		});
	}

	function gridMensajesYaInicializado($g) {
		/* jqGrid 5.x no usa data('jqGrid'); el objeto interno está en el DOM (como en facturación / basics) */
		return $g.length > 0 && $g[0].grid != null;
	}

	function adjuntarBotonesExportacionMensajes($g) {
		if ($g.data('chatsExportNav') || typeof $g.gridButtonsAdd !== 'function') {
			return;
		}
		$g.gridButtonsAdd([
			{
				caption: 'Exportar Excel',
				buttonicon: 'glyphicon glyphicon-download',
				onClickButton: function () {
					exportarMensajesExcel($g);
				}
			},
			{
				caption: 'Exportar PDF',
				buttonicon: 'glyphicon glyphicon-download',
				onClickButton: function () {
					imprimirMensajesManifiesto($g);
				}
			}
		]);
		$g.data('chatsExportNav', true);

		// Transformar programáticamente a los estilos nativos ui-corner-all btn btn-xs btn-success
		$('#gridMensajesManifiestoPager_left').find('td.ui-pg-button.ui-corner-all')
			.unbind('mouseenter mouseleave')
			.removeClass('ui-pg-button')
			.addClass('btn btn-xs btn-success')
			.find('.ui-pg-div span')
			.removeClass('ui-icon')
			.addClass('glyphicon');
	}

	function contarFilasGridMensajes($g) {
		var d = $g.jqGrid('getGridParam', 'data');
		return $.isArray(d) ? d.length : 0;
	}

	function exportarMensajesExcel($g) {
		if (!gridMensajesYaInicializado($g)) {
			return;
		}
		if (contarFilasGridMensajes($g) === 0) {
			if ($.alert) {
				$.alert('No hay datos para exportar.');
			} else {
				window.alert('No hay datos para exportar.');
			}
			return;
		}
		try {
			$g.jqGrid('exportGridExcel', {
				nombre: 'Mensajes_manifiesto',
				hoja: 'mensajes',
				footer: true,
				removeHiddens: true,
				removeCols: []
			});
		} catch (e1) {
			if ($.alert) {
				$.alert('No se pudo generar el Excel.');
			} else {
				window.alert('No se pudo generar el Excel.');
			}
		}
	}

	function imprimirMensajesManifiesto($g) {
		if (!gridMensajesYaInicializado($g)) {
			return;
		}
		if (contarFilasGridMensajes($g) === 0) {
			if ($.alert) {
				$.alert('No hay datos para imprimir.');
			} else {
				window.alert('No hay datos para imprimir.');
			}
			return;
		}
		try {
			$('#tablaReporteMensajesManifiesto').html($g.jqGrid('exportGridInnerHTML', {
				footer: true,
				generated: false,
				removeHiddens: true,
				removeCols: []
			}));
			$('#imprimirMensajesManifiesto').printElement({
				pageTitle: 'Mensajes_manifiesto',
				printMode: 'iframe',
				overrideElementCSS: [{ href: '../../mascaras/model1/estilos/print.css', media: 'print' }]
			});
		} catch (e2) {
			if ($.alert) {
				$.alert('No se pudo preparar la impresión / PDF.');
			} else {
				window.alert('No se pudo preparar la impresión / PDF.');
			}
		}
	}

	function imprimirMensajesGraficosChats() {
		var $box = $('#imprimirMensajesGraficos');
		if (!$box.length) {
			return;
		}
		var $pla = $('#tablaMsjResumenPlanta');
		var $tip = $('#tablaMsjResumenTipo');
		var hasPla = tablaResumenChatsTieneFilasDatos($pla);
		var hasTip = tablaResumenChatsTieneFilasDatos($tip);
		var ch = window._chartMsjPorPlantaRelaveraChats;
		var hasChart = !!(ch && typeof ch.toBase64Image === 'function');
		var chTipo = window._chartMsjPorTipoRelaveraChats;
		var hasChartTipo = !!(chTipo && typeof chTipo.toBase64Image === 'function');
		if (!hasPla && !hasTip && !hasChart && !hasChartTipo) {
			if ($.alert) {
				$.alert('No hay datos para imprimir. Pulse Buscar en la pestaña Gráficos y espere a que carguen el resumen y el gráfico.');
			} else {
				window.alert('No hay datos para imprimir. Pulse Buscar en la pestaña Gráficos y espere a que carguen el resumen y el gráfico.');
			}
			return;
		}
		if (typeof $.fn.printElement !== 'function') {
			if ($.alert) {
				$.alert('La utilidad de impresión no está disponible (printElement).');
			} else {
				window.alert('La utilidad de impresión no está disponible (printElement).');
			}
			return;
		}

		$('#imprimirGraficos_filtros').text('Filtros aplicados: ' + textoLeyendaFiltrosMensajesChats(SUF_FILTROS_GRAF));
		$('#imprimirGraficos_meta').text(String($('#bd_mensajes_chart_meta').text() || ''));

		var $wrap = $('#imprimirGraficos_chart_wrap');
		var $img = $('#imprimirGraficos_chart_img');
		if (hasChart) {
			try {
				var url = ch.toBase64Image('image/png', 1);
				if (url) {
					$img.attr('src', url);
					$wrap.show();
				} else {
					$img.attr('src', '');
					$wrap.hide();
				}
			} catch (eImg) {
				$img.attr('src', '');
				$wrap.hide();
			}
		} else {
			$img.attr('src', '');
			$wrap.hide();
		}

		var $wrapT = $('#imprimirGraficos_chart_tipo_wrap');
		var $imgT = $('#imprimirGraficos_chart_tipo_img');
		if (hasChartTipo) {
			try {
				var urlT = chTipo.toBase64Image('image/png', 1);
				if (urlT) {
					$imgT.attr('src', urlT);
					$wrapT.show();
				} else {
					$imgT.attr('src', '');
					$wrapT.hide();
				}
			} catch (eImgT) {
				$imgT.attr('src', '');
				$wrapT.hide();
			}
		} else {
			$imgT.attr('src', '');
			$wrapT.hide();
		}

		$('#imprimirGraficos_tabla_planta').html(clonarTablaImpresionGraficosChats('#tablaMsjResumenPlanta'));
		$('#imprimirGraficos_tabla_tipo').html(clonarTablaImpresionGraficosChats('#tablaMsjResumenTipo'));

		try {
			$box.printElement({
				pageTitle: 'Mensajes_manifiesto_graficos',
				printMode: 'iframe',
				overrideElementCSS: [{ href: '../../mascaras/model1/estilos/print.css', media: 'print' }]
			});
		} catch (eGraf) {
			if ($.alert) {
				$.alert('No se pudo preparar la impresión / PDF.');
			} else {
				window.alert('No se pudo preparar la impresión / PDF.');
			}
		}
	}

	function armarGridMensajes($g, rows) {
		rows = $.isArray(rows) ? rows : [];
		if (gridMensajesYaInicializado($g)) {
			/* No usar setRows(): fuerza total:1 y anula la paginación local. */
			$g.jqGrid('setGridParam', { datatype: 'local', data: rows });
			$g.jqGrid('clearGridData');
			$g.jqGrid('setGridParam', { data: rows, page: 1, records: rows.length }).trigger('reloadGrid', [{ page: 1 }]);
			setTimeout(function () { pintarEstadoMensajes($g); }, 0);
			return;
		}
		$g.createGrid({
			caption: 'Resultados <div class="pull-right"><b>Envio de mensajes por WhatsApp</b></div>',
			height: 320,
			datatype: 'local',
			data: rows,
			headertitles: true,
			colModel: colModelMensajesManifiesto(),
			rowNum: GRID_MSJ_PAGE_SIZE,
			rowList: [GRID_MSJ_PAGE_SIZE],
			pgbuttons: true,
			pginput: true,
			pgtext: 'Pág. {0} de {1}',
			loadComplete: function () {
				setTimeout(function () { pintarEstadoMensajes($g); }, 0);
			}
		}, true, 'gridMensajesManifiestoPager', {
			refresh: true
		});
		adjuntarBotonesExportacionMensajes($g);
	}

	function cargarMensajesBd($g) {
		var $m = $('#bd_mensajes_meta');
		$m.removeClass('text-muted text-danger').addClass('text-info').text('Cargando…');
		var data = $.extend({ listManifiestoMensajesAjax: 1 }, datosFiltrosMensajesDesdeSufijo(SUF_FILTROS_LISTA));
		$.ajax({
			url: URL_CHATS_LISTA_PHP,
			type: 'GET',
			dataType: 'json',
			data: data,
			success: function (d) {
				if (!d || !d.success) {
					$m.removeClass('text-info').addClass('text-danger').text(
						d && d.message ? String(d.message) : 'La respuesta no es válida.'
					);
					return;
				}
				var rows = d.rows == null ? [] : ($.isArray(d.rows) ? d.rows : []);
				armarGridMensajes($g, rows);
				$m.removeClass('text-info').addClass('text-muted').text(rows.length + ' registro(s)');
			},
			error: function (xhr) {
				var msg = 'No se pudo cargar el listado.';
				if (xhr && xhr.status) {
					msg += ' (HTTP ' + xhr.status + ').';
				}
				var txt = xhr && xhr.responseText ? String(xhr.responseText).trim() : '';
				if (txt.charAt(0) === '{') {
					try {
						var d = JSON.parse(txt);
						if (d && d.message) {
							msg = String(d.message);
						} else if (d && d.error) {
							msg = String(d.error);
						}
					} catch (e1) { /* ignore */ }
				} else if (txt.indexOf('<') === 0) {
					msg = 'Sesión vencida o respuesta HTML; recargue la página e intente de nuevo.';
				}
				$m.removeClass('text-info').addClass('text-danger').text(msg);
			}
		});
	}

	function initSelect2PlantasMensajesChats() {
		if (!$.fn.select2) {
			return;
		}
		$('.chats-sel-planta').each(function () {
			var $el = $(this);
			if ($el.data('select2')) {
				return;
			}
			$el.select2({
				placeholder: 'Todas las plantas',
				allowClear: true,
				width: '100%'
			});
		});
	}

	function initGridMensajesManifiesto() {
		var $g = $('#gridMensajesManifiesto');
		if (!$g.length) return;

		initSelect2PlantasMensajesChats();

		cargarMensajesBd($g);

		$('#btn_buscar_mensajes_lista').on('click', function () {
			cargarMensajesBd($g);
		});
		$('#btn_buscar_mensajes_graficos').on('click', function () {
			cargarGraficoMensajesPorPlanta();
		});
		$('#btn_imprimir_pdf_graficos_chats').on('click', function () {
			imprimirMensajesGraficosChats();
		});

		$('#filtro_msj_prs_bus_tex_lista').on('keydown', function (e) {
			if (e.keyCode === 13) {
				e.preventDefault();
				cargarMensajesBd($g);
			}
		});
		$('#filtro_msj_prs_bus_tex_graficos').on('keydown', function (e) {
			if (e.keyCode === 13) {
				e.preventDefault();
				cargarGraficoMensajesPorPlanta();
			}
		});

		$('#form_filtros_mensajes_lista').on('change', '#filtro_pla_cod_lista, #filtro_msj_tip_lista, #filtro_msj_fec_desde_lista, #filtro_msj_fec_hasta_lista, input[name="Msj_Prs_Bus_Tip_lista"]', function () {
			cargarMensajesBd($g);
		});
		$('#form_filtros_mensajes_graficos').on('change', '#filtro_pla_cod_graficos, #filtro_msj_tip_graficos, #filtro_msj_fec_desde_graficos, #filtro_msj_fec_hasta_graficos, input[name="Msj_Prs_Bus_Tip_graficos"]', function () {
			cargarGraficoMensajesPorPlanta();
		});

		$('#chats_mensajes_tabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			var href = $(e.target).attr('href') || '';
			if (href === '#tab_chats_graficos') {
				cargarGraficoMensajesPorPlanta();
			}
		});
	}

	function init() {
		if ($ && $.fn.jqGrid) {
			initGridMensajesManifiesto();
		}
	}

	window.manValChats = {
		init: init,
		colModelMensajesManifiesto: colModelMensajesManifiesto,
		armarGridMensajes: armarGridMensajes,
		exportarMensajesExcel: exportarMensajesExcel,
		imprimirMensajesManifiesto: imprimirMensajesManifiesto,
		imprimirMensajesGraficosChats: imprimirMensajesGraficosChats,
		datosFiltrosMensajesAjax: datosFiltrosMensajesAjax,
		datosFiltrosMensajesDesdeSufijo: datosFiltrosMensajesDesdeSufijo,
		cargarGraficoMensajesPorPlanta: cargarGraficoMensajesPorPlanta,
		URL_MENSAJES_BD_AJAX: URL_MENSAJES_BD_AJAX,
		URL_CHATS_LISTA_PHP: URL_CHATS_LISTA_PHP,
		MSJ_TIP_FILTRO: MSJ_TIP_FILTRO
	};

	if ($) {
		$(function () { init(); });
	} else {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', init);
		} else {
			init();
		}
	}
})(window, window.jQuery);
