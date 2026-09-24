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
			// Procesos que cuelgan directo del modulo (sin directorio intermedio),
			// sin importar si estan envueltos en un wrapper visual (grid/lista).
			$mod.find('.aud-cfg-pcs').filter(function () {
				return $(this).closest('.aud-cfg-dir').length === 0;
			}).find('.aud-cfg-pcs-chk:checked').each(function () {
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
		$box.toggleClass('aud-cfg-collapsed', !!collapsed);
		$box.find('> .aud-cfg-mod-head .aud-cfg-toggle .glyphicon, > .aud-cfg-dir-head .aud-cfg-toggle .glyphicon')
			.toggleClass('glyphicon-chevron-right', !!collapsed)
			.toggleClass('glyphicon-chevron-down', !collapsed);
		var $folder = $box.find('> .aud-cfg-dircard-head .aud-cfg-dircard-icon');
		if ($folder.length) {
			$folder.toggleClass('glyphicon-folder-close', !!collapsed)
				.toggleClass('glyphicon-folder-open', !collapsed);
		}
	}

	/** Conteo de procesos de un directorio (para el badge de su tarjeta en vista Grid) */
	function dirStats(dir, selected, fullDir) {
		var procesos = dir.procesos || [];
		var total = procesos.length;
		var checked = 0;
		$.each(procesos, function (i, p) {
			if (fullDir || !!selected[dir.Org_Cod + '_' + p.Pcs_Cod]) checked++;
		});
		return { total: total, checked: checked };
	}

	function switchInputHtml(chkClass, valueAttr, checked, disAttr) {
		return '<span class="aud-cfg-sw-ctrl"><input type="checkbox" class="' + chkClass + '"' + valueAttr + (checked ? ' checked="checked"' : '') + disAttr + ' />'
			+ '<span class="aud-cfg-sw-track"><span class="aud-cfg-sw-thumb"></span></span></span>';
	}

	/** Proceso individual como "chip" compacto (usado dentro de una tarjeta de directorio en vista Grid) */
	function gridProcChipHtml(p, chk, disAttr) {
		var name = esc(p.Pcs_Lin || ('Proceso ' + p.Pcs_Cod));
		return '<div class="aud-cfg-pcs aud-cfg-chip" data-pcs="' + p.Pcs_Cod + '">'
			+ '<label class="aud-cfg-pcs-label aud-cfg-switch aud-cfg-chip-label" title="' + name + '">'
			+ switchInputHtml('aud-cfg-pcs-chk', ' value="' + p.Pcs_Cod + '"', chk, disAttr)
			+ ' <span class="aud-cfg-chip-name">' + name + '</span></label>'
			+ '</div>';
	}

	/**
	 * Vista Grid: los directorios se muestran como tarjetas (con icono) organizadas
	 * en una cuadricula (aud-cfg-dircards-grid); cada tarjeta arranca colapsada y,
	 * al desplegarla, lista sus procesos en una mini-grilla compacta de chips.
	 * Los procesos que cuelgan directo del modulo (sin directorio) se listan aparte
	 * como chips sueltos bajo el titulo del modulo.
	 */
	function renderGrid(mods, selected, modFull, dirFull, disAttr) {
		var html = '';
		$.each(mods, function (i, m) {
			var org = m.Org_Cod;
			var fullMod = !!modFull[org];
			var dirs = m.directorios || [];

			var directos = []; // procesos sueltos del modulo: { dirOrg, p }
			var reales = [];   // directorios reales (tarjetas)
			$.each(dirs, function (j, dir) {
				if (dir.es_modulo) {
					$.each(dir.procesos || [], function (k, p) {
						directos.push({ dirOrg: dir.Org_Cod, p: p });
					});
				} else {
					reales.push(dir);
				}
			});

			html += '<div class="aud-cfg-mod aud-cfg-grid-mod" data-org="' + org + '">';
			html += '<div class="aud-cfg-mod-head aud-cfg-grid-modhead">'
				+ '<a href="#" class="aud-cfg-toggle"><span class="glyphicon glyphicon-chevron-down"></span></a> '
				+ '<label class="aud-cfg-switch">' + switchInputHtml('aud-cfg-mod-chk', '', fullMod, disAttr) + '</label> '
				+ '<span class="glyphicon glyphicon-th-large aud-cfg-mod-icon"></span> '
				+ '<strong>' + esc(m.Org_Des) + '</strong>'
				+ countHtml(modStats(m, selected, modFull, dirFull))
				+ '</div>';
			html += '<div class="aud-cfg-mod-body">';

			if (directos.length) {
				html += '<div class="aud-cfg-grid-generales-label"><span class="glyphicon glyphicon-flash"></span> Procesos generales del modulo</div>';
				html += '<div class="aud-cfg-grid-chiprow">';
				$.each(directos, function (k, item) {
					var selKey = item.dirOrg + '_' + item.p.Pcs_Cod;
					var chk = fullMod || !!selected[selKey];
					html += gridProcChipHtml(item.p, chk, disAttr);
				});
				html += '</div>';
			}

			if (reales.length) {
				html += '<div class="aud-cfg-dircards-grid">';
				$.each(reales, function (j, dir) {
					var dirOrg = dir.Org_Cod;
					var fullDir = fullMod || !!dirFull[dirOrg];
					var procesos = dir.procesos || [];
					html += '<div class="aud-cfg-dir aud-cfg-dircard aud-cfg-collapsed" data-org="' + dirOrg + '">';
					html += '<div class="aud-cfg-dir-head aud-cfg-dircard-head">'
						+ '<a href="#" class="aud-cfg-toggle aud-cfg-dircard-toggle"><span class="glyphicon glyphicon-chevron-right"></span></a>'
						+ '<span class="glyphicon glyphicon-folder-close aud-cfg-dircard-icon"></span>'
						+ '<span class="aud-cfg-dircard-name" title="' + esc(dir.Org_Des) + '">' + esc(dir.Org_Des) + '</span>'
						+ countHtml(dirStats(dir, selected, fullDir))
						+ '<label class="aud-cfg-switch aud-cfg-dircard-switch">' + switchInputHtml('aud-cfg-dir-chk', '', fullDir, disAttr) + '</label>'
						+ '</div>';
					html += '<div class="aud-cfg-dir-body aud-cfg-dircard-body">';
					if (procesos.length) {
						html += '<div class="aud-cfg-grid-chiprow">';
						$.each(procesos, function (k, p) {
							var selKey = dirOrg + '_' + p.Pcs_Cod;
							var chk = fullDir || !!selected[selKey];
							html += gridProcChipHtml(p, chk, disAttr);
						});
						html += '</div>';
					} else {
						html += '<p class="aud-cfg-dircard-empty">Sin procesos registrados.</p>';
					}
					html += '</div></div>';
				});
				html += '</div>';
			}

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

	// ---------------------------------------------------------------
	// Clave de acceso al directorio + Notificaciones por correo
	// ---------------------------------------------------------------
	var orgLookup = {};   // Org_Cod -> "Modulo > Directorio"
	var pcsLookup = {};   // Pcs_Cod -> { des, org, dirLabel }
	var usuariosConCorreo = [];
	/** Alcances confirmados en el campo: [{ key, org, pcs, label }] */
	var notifAlcances = [];
	/** Borrador dentro del modal (mismas claves) */
	var notifAlcanceDraft = {};

	function notifAlcanceKey(org, pcs) {
		org = parseInt(org, 10) || 0;
		pcs = parseInt(pcs, 10) || 0;
		return pcs > 0 ? (org + ':' + pcs) : String(org);
	}

	function notifAlcanceLabel(org, pcs) {
		pcs = parseInt(pcs, 10) || 0;
		org = parseInt(org, 10) || 0;
		if (pcs > 0 && pcsLookup[pcs]) {
			return pcsLookup[pcs].dirLabel + ' > ' + pcsLookup[pcs].des;
		}
		if (orgLookup[org]) {
			return orgLookup[org] + ' (todo)';
		}
		return 'Org #' + org;
	}

	/** Reconstruye lookups desde el arbol de modulos (ya no llena un <select>) */
	function poblarNotifOrg(mods) {
		orgLookup = {};
		pcsLookup = {};
		$.each(mods || [], function (i, m) {
			orgLookup[m.Org_Cod] = m.Org_Des || ('Modulo ' + m.Org_Cod);
			$.each(m.directorios || [], function (j, dir) {
				var esModulo = !!dir.es_modulo;
				var dirLabel = esModulo ? m.Org_Des : (m.Org_Des + ' > ' + dir.Org_Des);
				orgLookup[dir.Org_Cod] = dirLabel;
				$.each(dir.procesos || [], function (k, p) {
					var pDes = p.Pcs_Lin || ('Proceso ' + p.Pcs_Cod);
					pcsLookup[p.Pcs_Cod] = { des: pDes, org: dir.Org_Cod, dirLabel: dirLabel };
				});
			});
		});
		renderNotifAlcanceChips();
	}

	function renderNotifAlcanceChips() {
		var $box = $('#audNotifAlcanceChips');
		if (!$box.length) return;
		if (!notifAlcances.length) {
			$box.html('<span class="aud-notif-alcance-placeholder">Ning&uacute;n alcance seleccionado. Clic para elegir&hellip;</span>');
			return;
		}
		var html = '';
		$.each(notifAlcances, function (i, a) {
			html += '<span class="aud-notif-alcance-chip" data-key="' + esc(a.key) + '" title="' + esc(a.label) + '">'
				+ '<span class="glyphicon glyphicon-ok-circle"></span> ' + esc(a.label)
				+ ' <button type="button" class="aud-notif-alcance-chip-x" title="Quitar" data-key="' + esc(a.key) + '">&times;</button>'
				+ '</span>';
		});
		$box.html(html);
	}

	function setNotifAlcancesFromPairs(pairs) {
		notifAlcances = [];
		$.each(pairs || [], function (i, p) {
			var org = parseInt(p.org, 10) || 0;
			var pcs = parseInt(p.pcs, 10) || 0;
			if (org <= 0) return;
			var key = notifAlcanceKey(org, pcs);
			notifAlcances.push({ key: key, org: org, pcs: pcs, label: notifAlcanceLabel(org, pcs) });
		});
		renderNotifAlcanceChips();
	}

	function notifEventosHintText() {
		var parts = [];
		if ($('#audNotifEveU').is(':checked')) parts.push('Actualizar');
		if ($('#audNotifEveD').is(':checked')) parts.push('Eliminar');
		return parts.length ? parts.join(' / ') : 'Ninguno marcado';
	}

	function renderNotifAlcanceTree() {
		var mods = (ultimoData && ultimoData.modules) ? ultimoData.modules : [];
		var $tree = $('#audNotifAlcanceTree');
		if (!$tree.length) return;
		if (!mods.length) {
			$tree.html('<p class="text-muted text-center" style="padding:20px;">No hay m&oacute;dulos disponibles.</p>');
			actualizarNotifAlcanceCount();
			return;
		}
		var html = '';
		$.each(mods, function (i, m) {
			var modKey = notifAlcanceKey(m.Org_Cod, 0);
			var modOn = !!notifAlcanceDraft[modKey];
			html += '<div class="aud-notif-alcance-mod" data-mod="' + m.Org_Cod + '">';
			html += '<div class="aud-notif-alcance-row is-mod" data-search="' + esc((m.Org_Des || '').toLowerCase()) + '">';
			html += '<label class="aud-cfg-switch">'
				+ '<span class="aud-notif-alcance-name">' + esc(m.Org_Des || ('Modulo ' + m.Org_Cod)) + '</span>'
				+ switchInputHtml('aud-notif-alc-chk', ' data-org="' + m.Org_Cod + '" data-pcs="0" data-kind="mod"', modOn, '')
				+ '</label></div>';

			$.each(m.directorios || [], function (j, dir) {
				var esModulo = !!dir.es_modulo;
				var dirLabel = esModulo ? m.Org_Des : dir.Org_Des;
				var dirKey = notifAlcanceKey(dir.Org_Cod, 0);
				var dirOn = modOn || !!notifAlcanceDraft[dirKey];
				if (!esModulo) {
					html += '<div class="aud-notif-alcance-row is-dir" data-search="' + esc((m.Org_Des + ' ' + dirLabel).toLowerCase()) + '">';
					html += '<label class="aud-cfg-switch">'
						+ '<span class="aud-notif-alcance-name">' + esc(dirLabel) + ' <small class="text-muted">(todo)</small></span>'
						+ switchInputHtml('aud-notif-alc-chk', ' data-org="' + dir.Org_Cod + '" data-pcs="0" data-kind="dir" data-mod="' + m.Org_Cod + '"' + (modOn ? ' disabled="disabled"' : ''), dirOn, '')
						+ '</label></div>';
				}
				$.each(dir.procesos || [], function (k, p) {
					var pcsKey = notifAlcanceKey(dir.Org_Cod, p.Pcs_Cod);
					var pcsOn = modOn || dirOn || !!notifAlcanceDraft[pcsKey];
					var pName = p.Pcs_Lin || ('Proceso ' + p.Pcs_Cod);
					var search = (m.Org_Des + ' ' + dirLabel + ' ' + pName).toLowerCase();
					html += '<div class="aud-notif-alcance-row is-pcs" data-search="' + esc(search) + '">';
					html += '<label class="aud-cfg-switch">'
						+ '<span class="aud-notif-alcance-name">' + esc(pName) + '</span>'
						+ switchInputHtml('aud-notif-alc-chk', ' data-org="' + dir.Org_Cod + '" data-pcs="' + p.Pcs_Cod + '" data-kind="pcs" data-mod="' + m.Org_Cod + '" data-dir="' + dir.Org_Cod + '"' + ((modOn || (!esModulo && dirOn)) ? ' disabled="disabled"' : ''), pcsOn, '')
						+ '</label></div>';
				});
			});
			html += '</div>';
		});
		$tree.html(html);
		actualizarNotifAlcanceCount();
	}

	function compactarNotifAlcanceDraft() {
		/* Si un modulo o directorio esta activo, no guardar procesos hijos */
		var mods = (ultimoData && ultimoData.modules) ? ultimoData.modules : [];
		var keep = {};
		$.each(mods, function (i, m) {
			var modKey = notifAlcanceKey(m.Org_Cod, 0);
			if (notifAlcanceDraft[modKey]) {
				keep[modKey] = {
					key: modKey,
					org: parseInt(m.Org_Cod, 10),
					pcs: 0,
					label: notifAlcanceLabel(m.Org_Cod, 0)
				};
				return;
			}
			$.each(m.directorios || [], function (j, dir) {
				var esModulo = !!dir.es_modulo;
				var dirKey = notifAlcanceKey(dir.Org_Cod, 0);
				if (!esModulo && notifAlcanceDraft[dirKey]) {
					keep[dirKey] = {
						key: dirKey,
						org: parseInt(dir.Org_Cod, 10),
						pcs: 0,
						label: notifAlcanceLabel(dir.Org_Cod, 0)
					};
					return;
				}
				$.each(dir.procesos || [], function (k, p) {
					var pcsKey = notifAlcanceKey(dir.Org_Cod, p.Pcs_Cod);
					if (notifAlcanceDraft[pcsKey]) {
						keep[pcsKey] = {
							key: pcsKey,
							org: parseInt(dir.Org_Cod, 10),
							pcs: parseInt(p.Pcs_Cod, 10),
							label: notifAlcanceLabel(dir.Org_Cod, p.Pcs_Cod)
						};
					}
				});
			});
		});
		return keep;
	}

	function actualizarNotifAlcanceCount() {
		var n = 0;
		var compacted = compactarNotifAlcanceDraft();
		for (var k in compacted) {
			if (compacted.hasOwnProperty(k)) n++;
		}
		$('#audNotifAlcanceCount').text(n + ' seleccionado' + (n === 1 ? '' : 's'));
	}

	function abrirModalNotifAlcance() {
		if (!$('#modalAudNotifAlcance').length) return;
		notifAlcanceDraft = {};
		$.each(notifAlcances, function (i, a) {
			notifAlcanceDraft[a.key] = a;
		});
		$('#audNotifModalEveHint').text(notifEventosHintText());
		$('#audNotifAlcanceBuscar').val('');
		renderNotifAlcanceTree();
		$('#modalAudNotifAlcance').modal('show');
	}

	function notifOrgDes(orgCod, pcsCod) {
		pcsCod = parseInt(pcsCod, 10) || 0;
		orgCod = parseInt(orgCod, 10) || 0;
		if (pcsCod > 0 && pcsLookup[pcsCod]) {
			return pcsLookup[pcsCod].dirLabel + ' <span class="aud-notif-org-pcs">&gt; ' + esc(pcsLookup[pcsCod].des) + '</span>';
		}
		if (orgLookup[orgCod]) {
			return esc(orgLookup[orgCod]) + ' <span class="aud-notif-org-pcs">(todo)</span>';
		}
		return 'Org #' + orgCod;
	}

	function eventosBadgesHtml(csv) {
		var out = '';
		$.each(String(csv || '').split(','), function (i, e) {
			e = $.trim(e);
			if (e === 'U') out += '<span class="aud-notif-eve-badge aud-notif-eve-u">Actualizar</span>';
			if (e === 'D') out += '<span class="aud-notif-eve-badge aud-notif-eve-d">Eliminar</span>';
			if (e === 'I') out += '<span class="aud-notif-eve-badge">Insertar</span>';
		});
		return out;
	}

	function usuariosNombresHtml(csv) {
		var out = [];
		$.each(String(csv || '').split(','), function (i, u) {
			u = parseInt(u, 10);
			if (!u) return;
			var found = null;
			$.each(usuariosConCorreo, function (j, us) {
				if (parseInt(us.Usu_Cod, 10) === u) { found = us; return false; }
			});
			out.push('<span class="aud-notif-usu-chip" title="' + esc(found ? (found.Usu_Nom || ('Usuario ' + u)) : ('Usuario ' + u)) + '"><span class="glyphicon glyphicon-user"></span> ' + esc(found ? (found.Usu_Nom || ('Usuario ' + u)) : ('Usuario ' + u)) + '</span>');
		});
		return out.join(' ');
	}

	function correosHtml(csv) {
		var out = [];
		$.each(String(csv || '').split(','), function (i, c) {
			c = $.trim(c);
			if (!c) return;
			out.push('<span class="aud-notif-mail-chip" title="' + esc(c) + '"><span class="glyphicon glyphicon-envelope"></span> ' + esc(c) + '</span>');
		});
		return out.join(' ') || '<span class="text-muted">&mdash;</span>';
	}

	function notifEmptyRow(msg) {
		return '<tr><td colspan="5" class="text-center text-muted aud-notif-empty-row"><span class="glyphicon glyphicon-inbox"></span>' + msg + '</td></tr>';
	}

	function setNotifEditingUi(editing, notCod) {
		var $badge = $('.aud-notif-form-badge');
		var $card = $('.aud-notif-form-card');
		$('#audNotifTbody tr').removeClass('aud-notif-row-editing');
		if (editing) {
			$badge.addClass('is-editing').text('Editando #' + notCod);
			$card.addClass('is-editing');
			$('#audNotifTbody tr[data-notcod="' + notCod + '"]').addClass('aud-notif-row-editing');
		} else {
			$badge.removeClass('is-editing').text('Nueva regla');
			$card.removeClass('is-editing');
		}
	}

	function cargarNotifReglas() {
		var $tbody = $('#audNotifTbody');
		if (!$tbody.length) return;
		var editingCod = $('#audNotifCod').val() || '0';
		$.getJSON(window.location.pathname, { listNotifReglasAjax: 1 }, function (resp) {
			if (!resp || !resp.success) {
				$tbody.html(notifEmptyRow('No se pudieron cargar las reglas.'));
				return;
			}
			var rows = resp.rows || [];
			if (!rows.length) {
				$tbody.html(notifEmptyRow('A&uacute;n no hay reglas. Cree la primera con el formulario de arriba.'));
				return;
			}
			var html = '';
			$.each(rows, function (i, r) {
				html += '<tr data-notcod="' + r.Not_Cod + '" data-org="' + r.Org_Cod + '" data-pcs="' + r.Pcs_Cod + '" data-eventos="' + esc(r.Not_Eventos) + '" data-correos="' + esc(r.Not_Correos || '') + '" data-usuarios="' + esc(r.Not_Usuarios || '') + '">';
				html += '<td>' + notifOrgDes(r.Org_Cod, r.Pcs_Cod) + '</td>';
				html += '<td>' + eventosBadgesHtml(r.Not_Eventos) + '</td>';
				html += '<td>' + correosHtml(r.Not_Correos) + '</td>';
				html += '<td>' + (usuariosNombresHtml(r.Not_Usuarios) || '<span class="text-muted">&mdash;</span>') + '</td>';
				if (esAdmin) {
					html += '<td class="text-center"><span class="aud-notif-actions">'
						+ '<button type="button" class="btn btn-default btn-xs aud-notif-edit" title="Editar"><span class="glyphicon glyphicon-pencil"></span></button>'
						+ '<button type="button" class="btn btn-danger btn-xs aud-notif-del" title="Eliminar"><span class="glyphicon glyphicon-trash"></span></button>'
						+ '</span></td>';
				}
				html += '</tr>';
			});
			$tbody.html(html);
			if (parseInt(editingCod, 10) > 0) {
				setNotifEditingUi(true, editingCod);
			}
		}).fail(function () {
			$tbody.html(notifEmptyRow('Error al consultar las reglas.'));
		});
	}

	function cargarCorreosUsuario() {
		var $selNotif = $('#audNotifUsuarios');
		var $selCorreo = $('#audCorreoUsuSel');
		if (!$selNotif.length && !$selCorreo.length) return;
		$.getJSON(window.location.pathname, { listCorreosUsuarioAjax: 1 }, function (resp) {
			if (!resp || !resp.success) return;
			usuariosConCorreo = resp.rows || [];

			var optsNotif = '';
			var optsCorreo = '<option value="">Seleccione usuario...</option>';
			$.each(usuariosConCorreo, function (i, u) {
				var nombre = u.Usu_Nom || ('Usuario ' + u.Usu_Cod);
				optsCorreo += '<option value="' + u.Usu_Cod + '" data-correo="' + esc(u.Correo || '') + '">' + esc(nombre) + (u.Correo ? ' (' + esc(u.Correo) + ')' : '') + '</option>';
				if (u.Correo) {
					optsNotif += '<option value="' + u.Usu_Cod + '">' + esc(nombre) + ' (' + esc(u.Correo) + ')</option>';
				}
			});
			if ($selNotif.length) $selNotif.html(optsNotif || '<option value="" disabled="disabled">Ningun usuario tiene correo registrado</option>');
			if ($selCorreo.length) $selCorreo.html(optsCorreo);
			// Reordenar la tabla de reglas si ya estaba cargada (para mostrar nombres de usuario)
			if ($('#audNotifTbody tr[data-notcod]').length) {
				cargarNotifReglas();
			}
		});
	}

	function cargarAccesoEstado() {
		var $txt = $('#audAccEstadoTxt');
		if (!$txt.length) return;
		$.getJSON(window.location.pathname, { getAccesoEstadoAjax: 1 }, function (resp) {
			if (resp && resp.success && resp.configurada) {
				$txt.removeClass('aud-acc-off').addClass('aud-acc-on')
					.html('<span class="glyphicon glyphicon-ok-sign"></span> Clave configurada'
					+ (resp.fecha ? ' (desde ' + esc(resp.fecha) + ')' : '')
					+ '. Los usuarios deben ingresarla para entrar al directorio de auditoria.');
			} else {
				$txt.removeClass('aud-acc-on').addClass('aud-acc-off')
					.html('<span class="glyphicon glyphicon-info-sign"></span> Sin clave configurada: cualquier usuario con acceso al modulo puede ingresar libremente.');
			}
		}).fail(function () {
			$txt.removeClass('aud-acc-on').addClass('aud-acc-off')
				.text('No se pudo consultar el estado de la clave de acceso.');
		});
	}

	$('#btnAudAccGuardar').on('click', function () {
		var $btn = $(this).prop('disabled', true);
		var clave = $('#audAccClaveNueva').val() || '';
		var confirma = $('#audAccClaveConfirma').val() || '';
		var $st = $('#audAccStatus');
		if (clave.length < 4) {
			$st.text('La clave debe tener al menos 4 caracteres.').css('color', '#c62828');
			$btn.prop('disabled', false);
			return;
		}
		if (clave !== confirma) {
			$st.text('Las claves no coinciden.').css('color', '#c62828');
			$btn.prop('disabled', false);
			return;
		}
		$.ajax({
			url: window.location.pathname,
			type: 'POST',
			dataType: 'json',
			data: { setAccesoClaveAjax: 1, clave: clave },
			success: function (resp) {
				$st.text((resp && resp.message) || 'Clave actualizada.').css('color', (resp && resp.success) ? '#2e7d32' : '#c62828');
				if (resp && resp.success) {
					$('#audAccClaveNueva').val('');
					$('#audAccClaveConfirma').val('');
					cargarAccesoEstado();
				}
			},
			error: function (xhr) {
				var msg = 'Error al guardar la clave.';
				if (xhr && xhr.responseText) {
					try {
						var parsed = $.parseJSON(xhr.responseText);
						if (parsed && parsed.message) {
							msg = parsed.message;
						}
					} catch (eParse) {
						if (xhr.status) {
							msg += ' (HTTP ' + xhr.status + ')';
						}
					}
				}
				$st.text(msg).css('color', '#c62828');
			},
			complete: function () { $btn.prop('disabled', false); }
		});
	});

	$('#btnAudAccDesactivar').on('click', function () {
		if (typeof $.confirm === 'function') {
			$.confirm('Desea desactivar la clave de acceso? Cualquier usuario podra ingresar al directorio sin restriccion.', function () {
				desactivarClaveAcceso();
			});
		} else if (window.confirm('Desea desactivar la clave de acceso?')) {
			desactivarClaveAcceso();
		}
	});

	function desactivarClaveAcceso() {
		var $st = $('#audAccStatus');
		$.ajax({
			url: window.location.pathname,
			type: 'POST',
			dataType: 'json',
			data: { desactivarAccesoClaveAjax: 1 },
			success: function (resp) {
				$st.text((resp && resp.message) || 'Clave desactivada.').css('color', (resp && resp.success) ? '#2e7d32' : '#c62828');
				cargarAccesoEstado();
			},
			error: function () {
				$st.text('Error al desactivar la clave.').css('color', '#c62828');
			}
		});
	}

	function limpiarFormNotif() {
		$('#audNotifCod').val('0');
		notifAlcances = [];
		renderNotifAlcanceChips();
		$('#audNotifEveU').prop('checked', true);
		$('#audNotifEveD').prop('checked', true);
		$('#audNotifUsuarios').val([]);
		$('#audNotifCorreos').val('');
		$('#audNotifStatus').text('');
		setNotifEditingUi(false);
	}

	$('#btnAudNotifLimpiar').on('click', function () {
		limpiarFormNotif();
	});

	$('#audNotifAlcanceBox').on('click', function (e) {
		if ($(e.target).closest('.aud-notif-alcance-chip-x').length) return;
		abrirModalNotifAlcance();
	}).on('keydown', function (e) {
		if (e.which === 13 || e.which === 32) {
			e.preventDefault();
			abrirModalNotifAlcance();
		}
	});

	$('#audNotifAlcanceChips').on('click', '.aud-notif-alcance-chip-x', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var key = String($(this).attr('data-key') || '');
		notifAlcances = $.grep(notifAlcances, function (a) { return a.key !== key; });
		renderNotifAlcanceChips();
	});

	$('#audNotifAlcanceTree').on('change', '.aud-notif-alc-chk', function () {
		var $chk = $(this);
		var org = parseInt($chk.attr('data-org'), 10) || 0;
		var pcs = parseInt($chk.attr('data-pcs'), 10) || 0;
		var kind = $chk.attr('data-kind') || '';
		var key = notifAlcanceKey(org, pcs);
		var on = $chk.is(':checked');
		var mods = (ultimoData && ultimoData.modules) ? ultimoData.modules : [];

		if (on) {
			notifAlcanceDraft[key] = { key: key, org: org, pcs: pcs, label: notifAlcanceLabel(org, pcs) };
			if (kind === 'mod') {
				$.each(mods, function (i, m) {
					if (parseInt(m.Org_Cod, 10) !== org) return;
					$.each(m.directorios || [], function (j, dir) {
						delete notifAlcanceDraft[notifAlcanceKey(dir.Org_Cod, 0)];
						$.each(dir.procesos || [], function (k, p) {
							delete notifAlcanceDraft[notifAlcanceKey(dir.Org_Cod, p.Pcs_Cod)];
						});
					});
				});
			} else if (kind === 'dir') {
				$.each(mods, function (i, m) {
					$.each(m.directorios || [], function (j, dir) {
						if (parseInt(dir.Org_Cod, 10) !== org) return;
						$.each(dir.procesos || [], function (k, p) {
							delete notifAlcanceDraft[notifAlcanceKey(dir.Org_Cod, p.Pcs_Cod)];
						});
					});
				});
			}
		} else {
			delete notifAlcanceDraft[key];
		}
		var q = $.trim($('#audNotifAlcanceBuscar').val() || '').toLowerCase();
		renderNotifAlcanceTree();
		if (q) {
			$('#audNotifAlcanceBuscar').val(q);
			$('#audNotifAlcanceTree .aud-notif-alcance-row').each(function () {
				var s = String($(this).attr('data-search') || '');
				$(this).toggleClass('is-hidden', s.indexOf(q) === -1);
			});
		}
	});

	$('#audNotifAlcanceBuscar').on('keyup input', function () {
		var q = $.trim($(this).val() || '').toLowerCase();
		$('#audNotifAlcanceTree .aud-notif-alcance-row').each(function () {
			var s = String($(this).attr('data-search') || '');
			$(this).toggleClass('is-hidden', q !== '' && s.indexOf(q) === -1);
		});
	});

	$('#btnAudNotifAlcanceGuardar').on('click', function () {
		var compacted = compactarNotifAlcanceDraft();
		notifAlcances = [];
		$.each(compacted, function (k, a) { notifAlcances.push(a); });
		renderNotifAlcanceChips();
		$('#modalAudNotifAlcance').modal('hide');
		$('#audNotifStatus').text(notifAlcances.length
			? ('Alcance listo: ' + notifAlcances.length + ' elemento(s). Complete destinatarios y guarde la regla.')
			: 'Sin alcance seleccionado.').css('color', notifAlcances.length ? '#1f5f9a' : '#c62828');
	});

	function guardarUnaNotifRegla(payload) {
		return $.ajax({
			url: window.location.pathname,
			type: 'POST',
			dataType: 'json',
			data: payload
		});
	}

	$('#btnAudNotifGuardar').on('click', function () {
		var $btn = $(this).prop('disabled', true);
		var $st = $('#audNotifStatus');
		if (!notifAlcances.length) {
			$st.text('Seleccione al menos un alcance (modulo, directorio o proceso).').css('color', '#c62828');
			$btn.prop('disabled', false);
			abrirModalNotifAlcance();
			return;
		}
		var eventos = [];
		if ($('#audNotifEveU').is(':checked')) eventos.push('U');
		if ($('#audNotifEveD').is(':checked')) eventos.push('D');
		if (!eventos.length) {
			$st.text('Marque al menos un evento (Actualizar o Eliminar).').css('color', '#c62828');
			$btn.prop('disabled', false);
			return;
		}
		var usuarios = $('#audNotifUsuarios').val() || [];
		var correos = $.trim($('#audNotifCorreos').val() || '');
		if (!usuarios.length && !correos) {
			$st.text('Indique al menos un correo o un usuario destino.').css('color', '#c62828');
			$btn.prop('disabled', false);
			return;
		}

		var notCodBase = parseInt($('#audNotifCod').val(), 10) || 0;
		var pendientes = notifAlcances.slice();
		var okCount = 0;
		var errMsg = '';

		function siguiente() {
			if (!pendientes.length) {
				$st.text(okCount > 1
					? ('Se guardaron ' + okCount + ' reglas de notificacion.')
					: (errMsg || 'Regla guardada.')).css('color', errMsg && !okCount ? '#c62828' : '#2e7d32');
				if (okCount > 0) {
					limpiarFormNotif();
					cargarNotifReglas();
				}
				$btn.prop('disabled', false);
				return;
			}
			var a = pendientes.shift();
			var notCod = (notCodBase > 0 && okCount === 0) ? notCodBase : 0;
			guardarUnaNotifRegla({
				saveNotifReglaAjax: 1,
				notCod: notCod,
				org: a.org,
				pcs: a.pcs,
				eventos: eventos.join(','),
				correos: correos,
				usuarios: usuarios.join(',')
			}).done(function (resp) {
				if (resp && resp.success) {
					okCount++;
				} else {
					errMsg = (resp && resp.message) || 'No se pudo guardar una de las reglas.';
				}
				siguiente();
			}).fail(function () {
				errMsg = 'Error al guardar la regla.';
				siguiente();
			});
		}
		$st.text('Guardando...').css('color', '#1f5f9a');
		siguiente();
	});

	$('#audNotifTbody').on('click', '.aud-notif-edit', function () {
		var $tr = $(this).closest('tr');
		$('#audNotifCod').val($tr.attr('data-notcod'));
		var pcs = parseInt($tr.attr('data-pcs'), 10) || 0;
		var org = parseInt($tr.attr('data-org'), 10) || 0;
		setNotifAlcancesFromPairs([{ org: org, pcs: pcs }]);
		var eventos = String($tr.attr('data-eventos') || '').split(',');
		$('#audNotifEveU').prop('checked', eventos.indexOf('U') !== -1);
		$('#audNotifEveD').prop('checked', eventos.indexOf('D') !== -1);
		$('#audNotifUsuarios').val(String($tr.attr('data-usuarios') || '').split(',').filter(function (v) { return v; }));
		$('#audNotifCorreos').val($tr.attr('data-correos') || '');
		$('#audNotifStatus').text('Editando regla #' + $tr.attr('data-notcod') + '.').css('color', '#1f5f9a');
		setNotifEditingUi(true, $tr.attr('data-notcod'));
		var $form = $('.aud-notif-form-card');
		if ($form.length) {
			$('html, body').animate({ scrollTop: $form.offset().top - 80 }, 300);
		}
	});

	$('#audNotifTbody').on('click', '.aud-notif-del', function () {
		var $tr = $(this).closest('tr');
		var notCod = $tr.attr('data-notcod');
		function eliminar() {
			$.ajax({
				url: window.location.pathname,
				type: 'POST',
				dataType: 'json',
				data: { deleteNotifReglaAjax: 1, notCod: notCod },
				success: function (resp) {
					if (resp && resp.success) {
						cargarNotifReglas();
					} else if (typeof $.alert === 'function') {
						$.alert((resp && resp.message) || 'No se pudo eliminar.');
					}
				}
			});
		}
		if (typeof $.confirm === 'function') {
			$.confirm('Desea eliminar esta regla de notificacion?', eliminar);
		} else if (window.confirm('Desea eliminar esta regla de notificacion?')) {
			eliminar();
		}
	});

	$('#audCorreoUsuSel').on('change', function () {
		var correo = $(this).find('option:selected').attr('data-correo') || '';
		$('#audCorreoUsuValor').val(correo);
	});

	$('#btnAudCorreoUsuGuardar').on('click', function () {
		var $btn = $(this).prop('disabled', true);
		var $st = $('#audCorreoUsuStatus');
		var usu = $('#audCorreoUsuSel').val();
		var correo = $.trim($('#audCorreoUsuValor').val() || '');
		if (!usu) {
			$st.text('Seleccione un usuario.').css('color', '#c62828');
			$btn.prop('disabled', false);
			return;
		}
		$.ajax({
			url: window.location.pathname,
			type: 'POST',
			dataType: 'json',
			data: { saveCorreoUsuarioAjax: 1, usu: usu, correo: correo },
			success: function (resp) {
				$st.text((resp && resp.message) || 'Correo actualizado.').css('color', (resp && resp.success) ? '#2e7d32' : '#c62828');
				if (resp && resp.success) {
					cargarCorreosUsuario();
				}
			},
			error: function () {
				$st.text('Error al guardar el correo.').css('color', '#c62828');
			},
			complete: function () { $btn.prop('disabled', false); }
		});
	});

	function render(data) {
		ultimoData = data;
		var mods = (data && data.modules) ? data.modules : [];
		var selected = (data && data.selected) ? data.selected : {};
		var modFull = (data && data.modFull) ? data.modFull : {};
		var dirFull = (data && data.dirFull) ? data.dirFull : {};
		if (typeof data.esAdmin !== 'undefined') {
			esAdmin = !!data.esAdmin;
		}
		poblarNotifOrg(mods);
		if ($('#audNotifTbody').length && !$('#audNotifTbody tr[data-notcod]').length) {
			cargarNotifReglas();
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

		$mod.find('.aud-cfg-pcs').filter(function () {
			return $(this).closest('.aud-cfg-dir').length === 0;
		}).each(function () {
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

	/* Panel "Filtrar por rol/usuario": colapsado por defecto para no
	   saturar la vista principal; se expande a demanda. */
	$('#btnCfgFiltroToggle').on('click', function () {
		var $btn = $(this);
		var $panel = $('#audCfgFilterCard');
		$panel.slideToggle(150);
		$btn.toggleClass('active');
		$btn.find('.glyphicon-chevron-down, .glyphicon-chevron-up')
			.toggleClass('glyphicon-chevron-down glyphicon-chevron-up');
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
	cargarAccesoEstado();
	cargarCorreosUsuario();

})(jQuery);