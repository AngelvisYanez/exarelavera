/**
 * Configuracion de monitoreo - modulo / directorio / proceso.
 * Seleccion jerarquica y guardado, con filtro interactivo por Rol, por Usuario,
 * Modo Estricto y Estado de Auditoria. Solo el Administrador de Sistemas edita.
 * @package auditoria.VALIDACIONES
 */
(function ($) {
	'use strict';

	var $list = $('#audCfgList');
	var $status = $('#audCfgStatus');
	var esAdmin = (typeof window.audEsAdminSistemas !== 'undefined') ? !!window.audEsAdminSistemas : true;

	// Estado de filtros
	var filtroPcsMap = null; // { pcsId: true } o null si no hay filtro activo
	var filtroTipo = '';     // 'Rol' o 'Usuario'
	var filtroNombre = '';   // Nombre del rol o usuario seleccionado

	// Estado de vista: 'lista' (arbol colapsable) o 'grid' (tabla plana por modulo)
	var vista = 'lista';
	var ultimoData = null;

	// Hay cambios sin guardar en la seleccion
	var dirty = false;

	function setStatus(msg, ok) {
		$status.text(msg).css('color', ok === false ? '#c62828' : '#2e7d32');
	}

	function esc(s) {
		if (s == null) return '';
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	/** Texto util para busqueda: quita toggles, contadores y distintivos */
	function nodeText($el) {
		var $c = $el.clone();
		$c.find('.aud-cfg-toggle, .aud-cfg-count, .aud-badge-assigned').remove();
		return $.trim($c.text() || '').toLowerCase();
	}

	/** Clave estable de un checkbox para conservar el estado entre vistas */
	function checkKey($c) {
		var modOrg = $c.closest('.aud-cfg-mod').attr('data-org') || '0';
		if ($c.hasClass('aud-cfg-mod-chk')) return modOrg + '|m|0';
		if ($c.hasClass('aud-cfg-dir-chk')) {
			return modOrg + '|d|' + ($c.closest('.aud-cfg-dir').attr('data-org') || modOrg);
		}
		return modOrg + '|p|' + ($c.val() || '0');
	}

	function snapshotChecks() {
		var st = {};
		$list.find('.aud-cfg-pcs-chk, .aud-cfg-dir-chk, .aud-cfg-mod-chk').each(function () {
			var $c = $(this);
			st[checkKey($c)] = { c: $c.is(':checked'), i: !!$c.prop('indeterminate') };
		});
		return st;
	}

	function applyChecks(st) {
		if (!st) return;
		$list.find('.aud-cfg-pcs-chk, .aud-cfg-dir-chk, .aud-cfg-mod-chk').each(function () {
			var $c = $(this);
			if (st[checkKey($c)]) {
				$c.prop('checked', st[checkKey($c)].c).prop('indeterminate', st[checkKey($c)].i);
			}
		});
		$list.find('.aud-cfg-mod').each(function () {
			syncModCheckbox($(this));
		});
	}

	/** Conteo de procesos de un modulo (total y marcados) para el resumen */
	function modStats(mod, selected, modFull, dirFull) {
		var total = 0;
		var checked = 0;
		var fullMod = !!modFull[mod.Org_Cod];
		$.each(mod.directorios || [], function (j, dir) {
			var fullDir = fullMod || !!dirFull[dir.Org_Cod];
			var procesos = dir.procesos || [];
			$.each(procesos, function (k, p) {
				total++;
				if (fullDir || !!selected[dir.Org_Cod + '_' + p.Pcs_Cod]) {
					checked++;
				}
			});
		});
		return { total: total, checked: checked };
	}

	function countHtml(stats) {
		if (!stats.total) return '';
		var cls = (stats.checked === stats.total) ? ' aud-cfg-count-on'
			: (stats.checked > 0 ? ' aud-cfg-count-part' : '');
		return '<span class="aud-cfg-count' + cls + '" title="' + stats.checked + ' de ' + stats.total + ' procesos auditados">' + stats.checked + '/' + stats.total + '</span>';
	}

	/** Marca que hay cambios sin guardar y avisa/refuerza el boton Guardar */
	function marcarDirty() {
		if (!esAdmin) return;
		dirty = true;
		$('#audCfgDirty').toggle(true);
		$('#btnCfgGuardar').addClass('btn-warning').removeClass('btn-success');
	}

	/** Vista/servidor y UI quedan en paz: se limpia el aviso de cambios */
	function limpiarDirty() {
		dirty = false;
		$('#audCfgDirty').toggle(false);
		if (esAdmin) {
			$('#btnCfgGuardar').addClass('btn-success').removeClass('btn-warning');
		}
	}

	function collectItems() {
		var items = [];
		$list.children('.aud-cfg-mod').each(function () {
			var $mod = $(this);
			var org = parseInt($mod.attr('data-org'), 10) || 0;
			if (org <= 0) return;
			var $modChk = $mod.find('.aud-cfg-mod-chk').first();
			if ($modChk.length && $modChk.is(':checked') && !$modChk.prop('indeterminate')) {
				items.push({ org: org, pcs: 0 });
				return;
			}
			var $allPcs = $mod.find('.aud-cfg-pcs-chk');
			var total = $allPcs.length;
			var checked = $allPcs.filter(':checked').length;
			if (total > 0 && checked === total) {
				items.push({ org: org, pcs: 0 });
				return;
			}
			$mod.find('.aud-cfg-dir').each(function () {
				var $dir = $(this);
				var dirOrg = parseInt($dir.attr('data-org'), 10) || 0;
				if (dirOrg <= 0) {
					return;
				}
				var $pcs = $dir.find('.aud-cfg-pcs-chk');
				var t = $pcs.length;
				var c = $pcs.filter(':checked').length;
				if (t > 0 && c === t) {
					items.push({ org: dirOrg, pcs: 0 });
					return;
				}
				$pcs.filter(':checked').each(function () {
					items.push({
						org: dirOrg,
						pcs: parseInt($(this).val(), 10) || 0
					});
				});
			});
			$mod.children('.aud-cfg-mod-body').children('.aud-cfg-pcs').find('.aud-cfg-pcs-chk:checked').each(function () {
				items.push({
					org: org,
					pcs: parseInt($(this).val(), 10) || 0
				});
			});
		});
		return items;
	}

	function refreshSwitchUi($chk) {
		var $sw = $chk.closest('.aud-cfg-switch');
		if ($sw.length) {
			$sw.toggleClass('aud-cfg-sw-part', !!$chk.prop('indeterminate'));
		}
	}

	function syncDirCheckbox($dir) {
		var $pcs = $dir.find('.aud-cfg-pcs-chk');
		var total = $pcs.length;
		var checked = $pcs.filter(':checked').length;
		var $chk = $dir.find('.aud-cfg-dir-chk');
		$chk.prop('checked', total > 0 && checked === total);
		$chk.prop('indeterminate', checked > 0 && checked < total);
		refreshSwitchUi($chk);
	}

	function syncModCheckbox($mod) {
		$mod.find('.aud-cfg-dir').each(function () {
			syncDirCheckbox($(this));
		});
		var $pcs = $mod.find('.aud-cfg-pcs-chk');
		var total = $pcs.length;
		var checked = $pcs.filter(':checked').length;
		var $modChk = $mod.find('.aud-cfg-mod-chk');
		$modChk.prop('checked', total > 0 && checked === total);
		$modChk.prop('indeterminate', checked > 0 && checked < total);
		refreshSwitchUi($modChk);
	}

	function setCollapsed($box, collapsed) {
		if (vista === 'grid') collapsed = false;
		$box.toggleClass('aud-cfg-collapsed', !!collapsed);
		$box.find('> .aud-cfg-mod-head .aud-cfg-toggle .glyphicon, > .aud-cfg-dir-head .aud-cfg-toggle .glyphicon')
			.toggleClass('glyphicon-chevron-right', !!collapsed)
			.toggleClass('glyphicon-chevron-down', !collapsed);
	}

	function switchInputHtml(chkClass, valueAttr, checked, disAttr) {
		return '<span class="aud-cfg-sw-ctrl"><input type="checkbox" class="' + chkClass + '"' + valueAttr + (checked ? ' checked="checked"' : '') + disAttr + ' />'
			+ '<span class="aud-cfg-sw-track"><span class="aud-cfg-sw-thumb"></span></span></span>';
	}

	function gridPcsRowHtml(p, chk, disAttr, dirName) {
		var name = esc(p.Pcs_Lin || ('Proceso ' + p.Pcs_Cod));
		var estado = chk ? 'Auditar' : 'Inactivo';
		return '<div class="aud-cfg-pcs aud-cfg-grid-row" data-pcs="' + p.Pcs_Cod + '">'
			+ '<span class="aud-cfg-g-dircell">' + (dirName ? esc(dirName) : '&mdash;') + '</span>'
			+ '<span class="aud-cfg-g-pcscell"><label class="aud-cfg-pcs-label aud-cfg-switch">' + switchInputHtml('aud-cfg-pcs-chk', ' value="' + p.Pcs_Cod + '"', chk, disAttr) + ' '
			+ name + '</label></span>'
			+ '<span class="aud-cfg-g-statecell aud-cfg-g-state-' + (chk ? 'on' : 'off') + '">' + estado + '</span>'
			+ '</div>';
	}

	function renderGrid(mods, selected, modFull, dirFull, disAttr) {
		var html = '';
		$.each(mods, function (i, m) {
			var org = m.Org_Cod;
			var fullMod = !!modFull[org];
			var dirs = m.directorios || [];
			html += '<div class="aud-cfg-mod aud-cfg-grid-mod" data-org="' + org + '">';
			html += '<div class="aud-cfg-mod-head aud-cfg-grid-modhead"><label class="aud-cfg-switch">'
				+ switchInputHtml('aud-cfg-mod-chk', '', fullMod, disAttr) + '</label> ';
			html += '<strong>' + esc(m.Org_Des) + '</strong>';
			html += countHtml(modStats(m, selected, modFull, dirFull)) + '</div>';
			html += '<div class="aud-cfg-mod-body">';
			html += '<div class="aud-cfg-grid-head"><span class="aud-cfg-g-dircell">Directorio</span><span class="aud-cfg-g-pcscell">Proceso</span><span class="aud-cfg-g-statecell">Auditar</span></div>';

			$.each(dirs, function (j, dir) {
				var dirOrg = dir.Org_Cod;
				var fullDir = fullMod || !!dirFull[dirOrg];
				var procesos = dir.procesos || [];
				if (dir.es_modulo) {
					$.each(procesos, function (k, p) {
						var selKey = dirOrg + '_' + p.Pcs_Cod;
						var chk = fullDir || !!selected[selKey];
						html += gridPcsRowHtml(p, chk, disAttr, null);
					});
					return;
				}
				html += '<div class="aud-cfg-dir aud-cfg-grid-dir" data-org="' + dirOrg + '">';
				html += '<div class="aud-cfg-dir-head aud-cfg-grid-dirhead"><label class="aud-cfg-switch">'
					+ switchInputHtml('aud-cfg-dir-chk', '', fullDir, disAttr) + '</label> ';
				html += esc(dir.Org_Des) + ' <span class="aud-cfg-count">(' + procesos.length + ')</span></div>';
				html += '<div class="aud-cfg-dir-body">';
				$.each(procesos, function (k, p) {
					var selKey = dirOrg + '_' + p.Pcs_Cod;
					var chk = fullDir || !!selected[selKey];
					html += gridPcsRowHtml(p, chk, disAttr, dir.Org_Des);
				});
				html += '</div></div>';
			});

			html += '</div></div>';
		});
		return html;
	}

	function renderLista(mods, selected, modFull, dirFull, disAttr) {
		var html = '';
		$.each(mods, function (i, m) {
			var org = m.Org_Cod;
			var fullMod = !!modFull[org];
			var dirs = m.directorios || [];
			html += '<div class="aud-cfg-mod aud-cfg-collapsed" data-org="' + org + '">';
			html += '<div class="aud-cfg-mod-head"><a href="#" class="aud-cfg-toggle"><span class="glyphicon glyphicon-chevron-right"></span></a> ';
			html += '<label class="aud-cfg-switch aud-cfg-nivel-modulo"><span class="aud-cfg-sw-ctrl"><input type="checkbox" class="aud-cfg-mod-chk"' + (fullMod ? ' checked="checked"' : '') + disAttr + ' />'
				+ '<span class="aud-cfg-sw-track"><span class="aud-cfg-sw-thumb"></span></span></span> ';
			html += '<strong>' + esc(m.Org_Des) + '</strong>';
			html += countHtml(modStats(m, selected, modFull, dirFull)) + '</label></div>';
			html += '<div class="aud-cfg-mod-body">';

			$.each(dirs, function (j, dir) {
				var dirOrg = dir.Org_Cod;
				var fullDir = fullMod || !!dirFull[dirOrg];
				var procesos = dir.procesos || [];
				if (dir.es_modulo) {
					$.each(procesos, function (k, p) {
						var selKey = dirOrg + '_' + p.Pcs_Cod;
						var chk = fullDir || !!selected[selKey];
						html += '<div class="aud-cfg-pcs" data-pcs="' + p.Pcs_Cod + '">';
						html += '<label class="aud-cfg-pcs-label aud-cfg-switch"><span class="aud-cfg-sw-ctrl"><input type="checkbox" class="aud-cfg-pcs-chk" value="' + p.Pcs_Cod + '"' + (chk ? ' checked="checked"' : '') + disAttr + ' />'
							+ '<span class="aud-cfg-sw-track"><span class="aud-cfg-sw-thumb"></span></span></span> ';
						html += esc(p.Pcs_Lin || ('Proceso ' + p.Pcs_Cod)) + '</label></div>';
					});
					return;
				}
				html += '<div class="aud-cfg-dir aud-cfg-collapsed" data-org="' + dirOrg + '">';
				html += '<div class="aud-cfg-dir-head"><a href="#" class="aud-cfg-toggle"><span class="glyphicon glyphicon-chevron-right"></span></a> ';
				html += '<label class="aud-cfg-switch aud-cfg-nivel-directorio"><span class="aud-cfg-sw-ctrl"><input type="checkbox" class="aud-cfg-dir-chk"' + (fullDir ? ' checked="checked"' : '') + disAttr + ' />'
					+ '<span class="aud-cfg-sw-track"><span class="aud-cfg-sw-thumb"></span></span></span> ';
				html += esc(dir.Org_Des) + ' <span class="aud-cfg-count">(' + procesos.length + ')</span></label></div>';
				html += '<div class="aud-cfg-dir-body">';
				$.each(procesos, function (k, p) {
					var selKey = dirOrg + '_' + p.Pcs_Cod;
					var chk = fullDir || !!selected[selKey];
					html += '<div class="aud-cfg-pcs" data-pcs="' + p.Pcs_Cod + '">';
					html += '<label class="aud-cfg-pcs-label aud-cfg-switch"><span class="aud-cfg-sw-ctrl"><input type="checkbox" class="aud-cfg-pcs-chk" value="' + p.Pcs_Cod + '"' + (chk ? ' checked="checked"' : '') + disAttr + ' />'
						+ '<span class="aud-cfg-sw-track"><span class="aud-cfg-sw-thumb"></span></span></span> ';
					html += esc(p.Pcs_Lin || ('Proceso ' + p.Pcs_Cod)) + '</label></div>';
				});
				html += '</div></div>';
			});

			html += '</div></div>';
		});
		return html;
	}

	function render(data) {
		ultimoData = data;
		var mods = (data && data.modules) ? data.modules : [];
		var selected = (data && data.selected) ? data.selected : {};
		var modFull = (data && data.modFull) ? data.modFull : {};
		var dirFull = (data && data.dirFull) ? data.dirFull : {};
		if (typeof data.esAdmin !== 'undefined') {
			esAdmin = !!data.esAdmin;
		}

		if (!mods.length) {
			$list.html('<p class="aud-cfg-empty">No hay modulos, directorios o procesos disponibles.</p>');
			return;
		}

		var disAttr = esAdmin ? '' : ' disabled="disabled" title="Modo solo lectura"';
		var html = (vista === 'grid')
			? renderGrid(mods, selected, modFull, dirFull, disAttr)
			: renderLista(mods, selected, modFull, dirFull, disAttr);

		$list.toggleClass('aud-cfg-view-grid', vista === 'grid');
		$list.html(html);

		$list.find('.aud-cfg-mod').each(function () {
			syncModCheckbox($(this));
		});

		var nRules = 0;
		if (data && data.hasConfig) {
			$.each(modFull, function () { nRules++; });
			$.each(dirFull, function () { nRules++; });
			$.each(selected, function () { nRules++; });
		}
		var totalPcs = 0;
		var checkedPcs = 0;
		$.each(mods, function (i, m) {
			var st = modStats(m, selected, modFull, dirFull);
			totalPcs += st.total;
			checkedPcs += st.checked;
		});
		setStatus(data && data.hasConfig
			? ('Configuracion activa: ' + nRules + ' regla(s), ' + checkedPcs + '/' + totalPcs + ' procesos auditados. El monitor registra la actividad de los seleccionados en todo el sistema.')
			: 'Sin configuracion: no se registrara actividad (' + checkedPcs + '/' + totalPcs + ' procesos auditados). Marque los modulos para cubrir el sistema.', true);

		if (filtroPcsMap !== null) {
			aplicarFiltros();
		}
	}

	function cargar() {
		$list.html('<p class="aud-cfg-empty">Cargando...</p>');
		$.getJSON(window.location.pathname, { listConfigAjax: 1 }, function (data) {
			render(data);
			limpiarDirty();
		}).fail(function () {
			$list.html('<p class="aud-cfg-empty">No se pudo cargar la configuracion.</p>');
			setStatus('Error al cargar.', false);
		});
	}

	/** Cargar combo de roles */
	function cargarRoles() {
		var $sel = $('#audCfgFiltroRol');
		$.getJSON(window.location.pathname, { listRolesAjax: 1 }, function (resp) {
			if (resp && resp.success && resp.rows) {
				var opts = '<option value="">-- Todos los roles --</option>';
				$.each(resp.rows, function (i, r) {
					opts += '<option value="' + r.Per_Cod + '">' + esc(r.Per_Des) + '</option>';
				});
				$sel.html(opts);
			}
		});
	}

	/** Cargar combo de usuarios (agrupados por persona, sin repetidos) */
	function cargarUsuarios() {
		var $sel = $('#audCfgFiltroUsu');
		$.getJSON(window.location.pathname, { listUsuariosAjax: 1 }, function (resp) {
			if (resp && resp.success && resp.rows) {
				var opts = '<option value="">-- Todos los usuarios --</option>';
				$.each(resp.rows, function (i, u) {
					var val = ($.trim(u.Usu_Cods || '') !== '') ? u.Usu_Cods : (u.Usu_Cod || '');
					var nombre = u.Usu_Nom || 'Usuario #' + (u.Usu_Cod || '');
					if (u.N_Ctas > 1) {
						nombre += ' (' + u.N_Ctas + ' cuentas)';
					}
					if (u.Roles) {
						nombre += ' (' + u.Roles + ')';
					}
					opts += '<option value="' + val + '">' + esc(nombre) + '</option>';
				});
				$sel.html(opts);
			}
		});
	}

	/** Aplica: busqueda de texto, filtro rol/usuario, estado de auditoria y modo estricto */
	function aplicarFiltros() {
		var q = $.trim($('#audCfgFilter').val() || '').toLowerCase();
		var hasPcsFilter = (filtroPcsMap !== null);
		var estadoAudit = $('#audCfgFiltroAudit').val() || 'todos';
		var modoEstricto = $('#audCfgModoEstricto').is(':checked');

		$list.find('.aud-badge-assigned').remove();
		$list.find('.aud-cfg-empty-filter').remove();

		var conteoTotalAsignados = 0;
		var conteoAuditados = 0;
		var conteoPendientes = 0;

		$list.find('.aud-cfg-mod').each(function () {
			var $mod = $(this);
			var modTxt = nodeText($mod.find('.aud-cfg-mod-head'));
			var modHitText = !q || (modTxt.indexOf(q) !== -1);
			var modHasVisiblePcs = false;
			var modHasRolePcs = false;

			$mod.find('.aud-cfg-dir').each(function () {
				var $dir = $(this);
				var dirTxt = nodeText($dir.find('.aud-cfg-dir-head'));
				var dirHitText = modHitText || (dirTxt.indexOf(q) !== -1);
				var dirHasVisiblePcs = false;
				var dirHasRolePcs = false;

				$dir.find('.aud-cfg-pcs').each(function () {
					var $pcs = $(this);
					var $chk = $pcs.find('.aud-cfg-pcs-chk');
					var pcsId = parseInt($pcs.attr('data-pcs'), 10) || parseInt($chk.val(), 10) || 0;
					var isChecked = $chk.is(':checked');
					var t = nodeText($pcs);

					var hitText = dirHitText || (t.indexOf(q) !== -1);

					if (hasPcsFilter && filtroPcsMap[pcsId] === true) {
						$pcs.attr('data-in-filtro', '1');
						conteoTotalAsignados++;
						dirHasRolePcs = true;
						modHasRolePcs = true;
						if (isChecked) {
							conteoAuditados++;
						} else {
							conteoPendientes++;
						}
					} else {
						$pcs.removeAttr('data-in-filtro');
					}

					var hitEstado = true;
					if (estadoAudit === 'marcados') {
						hitEstado = isChecked;
					} else if (estadoAudit === 'no_marcados') {
						hitEstado = !isChecked;
					}

					// Todos los modulos/procesos/directorios se listan para cualquier rol;
					// los asignados al rol/usuario quedan resaltados con su distintivo.
					var visible = hitText && hitEstado;
					$pcs.toggle(visible);
					if (visible) {
						dirHasVisiblePcs = true;
						if (hasPcsFilter && filtroPcsMap[pcsId] === true) {
							$pcs.find('.aud-cfg-pcs-label').append('<span class="aud-badge-assigned"><span class="glyphicon glyphicon-ok"></span> Asignado</span>');
						}
					}
				});

				var dirVisible = dirHasVisiblePcs;
				if (hasPcsFilter && modoEstricto && !dirHasRolePcs) {
					dirVisible = false;
				}
				$dir.toggle(dirVisible);
				if (dirVisible) {
					modHasVisiblePcs = true;
					if (hasPcsFilter || q || estadoAudit !== 'todos') {
						setCollapsed($dir, false);
					}
				}
			});

			$mod.children('.aud-cfg-mod-body').children('.aud-cfg-pcs').each(function () {
				var $pcs = $(this);
				var $chk = $pcs.find('.aud-cfg-pcs-chk');
				var pcsId = parseInt($pcs.attr('data-pcs'), 10) || parseInt($chk.val(), 10) || 0;
				var isChecked = $chk.is(':checked');
				var t = nodeText($pcs);

				var hitText = modHitText || (t.indexOf(q) !== -1);

				if (hasPcsFilter && filtroPcsMap[pcsId] === true) {
					$pcs.attr('data-in-filtro', '1');
					conteoTotalAsignados++;
					modHasRolePcs = true;
					if (isChecked) {
						conteoAuditados++;
					} else {
						conteoPendientes++;
					}
				} else {
					$pcs.removeAttr('data-in-filtro');
				}

				var hitEstado = true;
				if (estadoAudit === 'marcados') {
					hitEstado = isChecked;
				} else if (estadoAudit === 'no_marcados') {
					hitEstado = !isChecked;
				}

				// Todos los modulos/procesos/directorios se listan para cualquier rol;
				// los asignados al rol/usuario quedan resaltados con su distintivo.
				var visible = hitText && hitEstado;
				$pcs.toggle(visible);
				if (visible) {
					modHasVisiblePcs = true;
					if (hasPcsFilter && filtroPcsMap[pcsId] === true) {
						$pcs.find('.aud-cfg-pcs-label').append('<span class="aud-badge-assigned"><span class="glyphicon glyphicon-ok"></span> Asignado</span>');
					}
				}
			});

			var modVisible = modHasVisiblePcs;
			if (hasPcsFilter && modoEstricto && !modHasRolePcs) {
				modVisible = false;
			}
			$mod.toggle(modVisible);
			if (modVisible && (hasPcsFilter || q || estadoAudit !== 'todos')) {
				setCollapsed($mod, false);
			}
		});

		if (hasPcsFilter) {
			actualizarMensajeFiltro(conteoTotalAsignados, conteoAuditados, conteoPendientes);
		}

		var visMods = $list.find('.aud-cfg-mod:visible').length;
		if (visMods === 0 && (q || hasPcsFilter || estadoAudit !== 'todos')) {
			$list.prepend('<p class="aud-cfg-empty aud-cfg-empty-filter">Ningun modulo coincide con la busqueda o los filtros activos.</p>');
		}
	}

	$list.on('click', '.aud-cfg-toggle', function (e) {
		e.preventDefault();
		var $box = $(this).closest('.aud-cfg-dir, .aud-cfg-mod');
		setCollapsed($box, !$box.hasClass('aud-cfg-collapsed'));
	});

	$list.on('change', '.aud-cfg-mod-chk', function () {
		if (!esAdmin) {
			$(this).prop('checked', !$(this).is(':checked'));
			return;
		}
		var on = $(this).is(':checked');
		var $mod = $(this).closest('.aud-cfg-mod');
		$mod.find('.aud-cfg-pcs-chk, .aud-cfg-dir-chk').prop('checked', on).prop('indeterminate', false);
		$(this).prop('indeterminate', false);
		if (on) {
			setCollapsed($mod, false);
		}
		syncModCheckbox($mod);
		if (filtroPcsMap !== null) aplicarFiltros();
	});

	$list.on('change', '.aud-cfg-dir-chk', function () {
		if (!esAdmin) {
			$(this).prop('checked', !$(this).is(':checked'));
			return;
		}
		var on = $(this).is(':checked');
		var $dir = $(this).closest('.aud-cfg-dir');
		$dir.find('.aud-cfg-pcs-chk').prop('checked', on);
		$(this).prop('indeterminate', false);
		if (on) {
			setCollapsed($dir, false);
			setCollapsed($dir.closest('.aud-cfg-mod'), false);
		}
		syncModCheckbox($dir.closest('.aud-cfg-mod'));
		if (filtroPcsMap !== null) aplicarFiltros();
	});

	$list.on('change', '.aud-cfg-pcs-chk', function () {
		if (!esAdmin) {
			$(this).prop('checked', !$(this).is(':checked'));
			return;
		}
		var $mod = $(this).closest('.aud-cfg-mod');
		if ($(this).is(':checked')) {
			setCollapsed($mod, false);
			var $dir = $(this).closest('.aud-cfg-dir');
			if ($dir.length) {
				setCollapsed($dir, false);
			}
		}
		syncModCheckbox($mod);
		if (filtroPcsMap !== null) aplicarFiltros();
	});

	$list.on('change', '.aud-cfg-pcs-chk, .aud-cfg-dir-chk, .aud-cfg-mod-chk', marcarDirty);

	$('#btnCfgTodos').on('click', function () {
		if (!esAdmin) return;
		$list.find('.aud-cfg-pcs-chk, .aud-cfg-mod-chk, .aud-cfg-dir-chk').prop('checked', true).prop('indeterminate', false);
		$list.find('.aud-cfg-mod').each(function () {
			syncModCheckbox($(this));
		});
		marcarDirty();
		if (filtroPcsMap !== null) aplicarFiltros();
	});

	$('#btnCfgNinguno').on('click', function () {
		if (!esAdmin) return;
		$list.find('.aud-cfg-pcs-chk, .aud-cfg-mod-chk, .aud-cfg-dir-chk').prop('checked', false).prop('indeterminate', false);
		$list.find('.aud-cfg-mod').each(function () {
			syncModCheckbox($(this));
		});
		marcarDirty();
		if (filtroPcsMap !== null) aplicarFiltros();
	});

	$('#btnCfgExpandir').on('click', function () {
		$list.find('.aud-cfg-mod, .aud-cfg-dir').each(function () {
			setCollapsed($(this), false);
		});
	});

	$('#btnCfgContraer').on('click', function () {
		$list.find('.aud-cfg-mod, .aud-cfg-dir').each(function () {
			setCollapsed($(this), true);
		});
	});

	/** Cambio de vista: lista (arbol) o grid (tabla) */
	function setVista(v) {
		if (v !== 'lista' && v !== 'grid') return;
		if (v === vista) return;
		var st = snapshotChecks();
		vista = v;
		$('#btnCfgVistaLista').toggleClass('active', vista === 'lista');
		$('#btnCfgVistaGrid').toggleClass('active', vista === 'grid');
		if (ultimoData) {
			render(ultimoData);
			applyChecks(st);
		}
	}

	$('#btnCfgVistaLista').on('click', function () {
		setVista('lista');
	});

	$('#btnCfgVistaGrid').on('click', function () {
		setVista('grid');
	});

	$('#btnCfgRecargar').on('click', function () {
		cargar();
	});

	$('#audCfgFilter').on('keyup', function () {
		aplicarFiltros();
	});

	$('#audCfgFiltroAudit').on('change', function () {
		aplicarFiltros();
	});

	$('#audCfgModoEstricto').on('change', function () {
		aplicarFiltros();
	});

	/** Filtro por Rol */
	$('#audCfgFiltroRol').on('change', function () {
		var rolVal = $(this).val();
		if (!rolVal) {
			if ($('#audCfgFiltroUsu').val() === '') {
				limpiarFiltroAsignacion();
			}
			return;
		}
		$('#audCfgFiltroUsu').val('');
		var rolTxt = $(this).find('option:selected').text();
		filtroTipo = 'Rol';
		filtroNombre = rolTxt;

		setStatus('Consultando procesos asignados al rol...', true);
		$.getJSON(window.location.pathname, { listProcesosFiltroAjax: 1, rol: rolVal }, function (resp) {
			if (resp && resp.success) {
				filtroPcsMap = {};
				$.each(resp.pcs || [], function (i, pcsId) {
					filtroPcsMap[pcsId] = true;
				});
				aplicarFiltros();
				setStatus('Filtro por Rol "' + rolTxt + '" aplicado (' + resp.total + ' procesos asignados).', true);
			} else {
				setStatus('No se pudieron obtener los procesos del rol.', false);
			}
		}).fail(function () {
			setStatus('Error al consultar procesos del rol.', false);
		});
	});

	/** Filtro por Usuario */
	$('#audCfgFiltroUsu').on('change', function () {
		var usuVal = $(this).val();
		if (!usuVal) {
			if ($('#audCfgFiltroRol').val() === '') {
				limpiarFiltroAsignacion();
			}
			return;
		}
		$('#audCfgFiltroRol').val('');
		var usuTxt = $(this).find('option:selected').text();
		filtroTipo = 'Usuario';
		filtroNombre = usuTxt;

		setStatus('Consultando procesos asignados al usuario...', true);
		$.getJSON(window.location.pathname, { listProcesosFiltroAjax: 1, usu: usuVal }, function (resp) {
			if (resp && resp.success) {
				filtroPcsMap = {};
				$.each(resp.pcs || [], function (i, pcsId) {
					filtroPcsMap[pcsId] = true;
				});
				aplicarFiltros();
				setStatus('Filtro por Usuario "' + usuTxt + '" aplicado (' + resp.total + ' procesos asignados).', true);
			} else {
				setStatus('No se pudieron obtener los procesos del usuario.', false);
			}
		}).fail(function () {
			setStatus('Error al consultar procesos del usuario.', false);
		});
	});

	function actualizarMensajeFiltro(total, auditados, pendientes) {
		var msg = '<span class="glyphicon glyphicon-filter"></span> Filtro por '
			+ '<strong>' + esc(filtroTipo) + ': ' + esc(filtroNombre) + '</strong> &mdash; '
			+ 'se listan todos los modulos, directorios y procesos; los <strong>' + total + '</strong> autorizados de este rol/usuario quedan resaltados ('
			+ '<span style="color:#0f7b3d;"><strong>' + auditados + '</strong> marcados en monitoreo</span>, '
			+ '<span style="color:#b91c1c;"><strong>' + pendientes + '</strong> sin auditar</span>).';
		$('#audCfgFilterMsg').html(msg).show();
		if (esAdmin) {
			$('#btnCfgMarcarFiltro').show();
			$('#btnCfgDesmarcarFiltro').show();
		}
	}

	function limpiarFiltroAsignacion() {
		$('#audCfgFiltroRol').val('');
		$('#audCfgFiltroUsu').val('');
		$('#audCfgFiltroAudit').val('todos');
		$('#audCfgFilterMsg').hide().html('');
		$('#btnCfgMarcarFiltro').hide();
		$('#btnCfgDesmarcarFiltro').hide();
		filtroPcsMap = null;
		filtroTipo = '';
		filtroNombre = '';
		aplicarFiltros();
		setStatus('Filtro de rol/usuario desactivado. Mostrando todos los procesos.', true);
	}

	$('#btnCfgLimpiarFiltro').on('click', function () {
		limpiarFiltroAsignacion();
	});

	/** Marcar unicamente los procesos visibles/asignados para ese rol o usuario */
	$('#btnCfgMarcarFiltro').on('click', function () {
		if (!esAdmin) return;
		if (!filtroPcsMap) return;

		var marcados = 0;
		$list.find('.aud-cfg-pcs:visible[data-in-filtro="1"]').each(function () {
			var $chk = $(this).find('.aud-cfg-pcs-chk');
			if ($chk.length && !$chk.is(':checked')) {
				$chk.prop('checked', true);
				marcados++;
			}
		});

		$list.find('.aud-cfg-mod').each(function () {
			syncModCheckbox($(this));
		});

		aplicarFiltros();
		marcarDirty();
		var aviso = 'Se marcaron ' + marcados + ' procesos para ' + filtroTipo + ': ' + filtroNombre + '. Presione "Guardar" para confirmar los cambios.';
		setStatus(aviso, true);
		if (typeof $.alert === 'function') {
			$.alert(aviso);
		}
	});

	/** Desmarcar unicamente los procesos visibles/asignados para ese rol o usuario */
	$('#btnCfgDesmarcarFiltro').on('click', function () {
		if (!esAdmin) return;
		if (!filtroPcsMap) return;

		var desmarcados = 0;
		$list.find('.aud-cfg-pcs:visible[data-in-filtro="1"]').each(function () {
			var $chk = $(this).find('.aud-cfg-pcs-chk');
			if ($chk.length && $chk.is(':checked')) {
				$chk.prop('checked', false);
				desmarcados++;
			}
		});

		$list.find('.aud-cfg-mod').each(function () {
			syncModCheckbox($(this));
		});

		aplicarFiltros();
		marcarDirty();
		var aviso = 'Se desmarcaron ' + desmarcados + ' procesos de ' + filtroTipo + ': ' + filtroNombre + '. Presione "Guardar" para confirmar los cambios.';
		setStatus(aviso, true);
		if (typeof $.alert === 'function') {
			$.alert(aviso);
		}
	});

	$('#btnCfgGuardar').on('click', function () {
		if (!esAdmin) {
			setStatus('Acceso denegado: Solo el Administrador de Sistemas puede guardar la configuracion.', false);
			if (typeof $.alert === 'function') {
				$.alert('Acceso denegado: Solo el Administrador de Sistemas puede guardar la configuracion.');
			}
			return;
		}
		var items = collectItems();
		var payload = JSON.stringify(items);
		var $btn = $(this).prop('disabled', true);
		setStatus('Guardando ' + items.length + ' regla(s)...', true);
		$.ajax({
			url: window.location.pathname,
			type: 'POST',
			dataType: 'json',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
			data: {
				saveConfigAjax: 1,
				items: payload
			},
			success: function (resp) {
				if (resp && resp.success) {
					setStatus(resp.message || 'Configuracion guardada.', true);
					if (typeof $.alert === 'function') {
						$.alert(resp.message || 'Configuracion guardada.');
					}
					limpiarDirty();
					cargar();
				} else {
					setStatus((resp && resp.message) || 'No se pudo guardar.', false);
				}
			},
			error: function (xhr) {
				var msg = 'Error al guardar.';
				if (xhr && xhr.responseText) {
					try {
						var parsed = $.parseJSON(xhr.responseText);
						if (parsed && parsed.message) {
							msg = parsed.message;
						}
					} catch (ignore) {}
				}
				setStatus(msg, false);
			},
			complete: function () {
				$btn.prop('disabled', false);
			}
		});
	});

	$(window).on('beforeunload', function () {
		if (dirty) {
			return 'Hay cambios sin guardar en la configuracion de monitoreo.';
		}
	});

	// Inicializacion
	cargar();
	cargarRoles();
	cargarUsuarios();

})(jQuery);