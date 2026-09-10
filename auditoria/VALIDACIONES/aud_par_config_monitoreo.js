/**
 * Configuracion de monitoreo - modulo / directorio / proceso.
 * @package auditoria.VALIDACIONES
 */
$(function () {
	var $list = $('#audCfgList');
	if (!$list.length) {
		return;
	}

	function setStatus(msg, ok) {
		var $s = $('#audCfgStatus');
		$s.css('color', ok === false ? '#a12b22' : '#1f7a45').text(msg || '');
	}

	function flagOn(map, key) {
		if (!map) {
			return false;
		}
		return !!(map[key] || map[String(key)]);
	}

	function esc(s) {
		return $('<div/>').text(s || '').html();
	}

	function chevron(collapsed) {
		return '<a href="#" class="aud-cfg-toggle" title="Expandir/contraer">'
			+ '<span class="glyphicon ' + (collapsed ? 'glyphicon-chevron-right' : 'glyphicon-chevron-down') + '"></span></a>';
	}

	function pcsHtml(dirOrg, p, on) {
		return '<div class="aud-cfg-pcs"><label>'
			+ '<input type="checkbox" class="aud-cfg-pcs-chk" value="' + p.Pcs_Cod + '"' + (on ? ' checked="checked"' : '') + ' />'
			+ esc($.trim(p.Pcs_Lin || ('Proceso ' + p.Pcs_Cod)))
			+ '</label></div>';
	}

	function dirHasSelection(dir, modFull, dirFull, selected) {
		if (modFull || flagOn(dirFull, dir.Org_Cod)) {
			return true;
		}
		var hit = false;
		$.each(dir.procesos || [], function (i, p) {
			if (flagOn(selected, dir.Org_Cod + '_' + p.Pcs_Cod)) {
				hit = true;
				return false;
			}
		});
		return hit;
	}

	function collectItems() {
		var items = [];
		$list.find('.aud-cfg-mod').each(function () {
			var $mod = $(this);
			var org = parseInt($mod.attr('data-org'), 10) || 0;
			if (org <= 0) {
				return;
			}
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

	function syncDirCheckbox($dir) {
		var $pcs = $dir.find('.aud-cfg-pcs-chk');
		var total = $pcs.length;
		var checked = $pcs.filter(':checked').length;
		var $chk = $dir.find('.aud-cfg-dir-chk');
		$chk.prop('checked', total > 0 && checked === total);
		$chk.prop('indeterminate', checked > 0 && checked < total);
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
	}

	function setCollapsed($box, collapsed) {
		$box.toggleClass('aud-cfg-collapsed', !!collapsed);
		$box.find('> .aud-cfg-mod-head .aud-cfg-toggle .glyphicon, > .aud-cfg-dir-head .aud-cfg-toggle .glyphicon')
			.toggleClass('glyphicon-chevron-right', !!collapsed)
			.toggleClass('glyphicon-chevron-down', !collapsed);
	}

	function render(data) {
		var mods = (data && data.modules) ? data.modules : [];
		var selected = (data && data.selected) ? data.selected : {};
		var modFull = (data && data.modFull) ? data.modFull : {};
		var dirFull = (data && data.dirFull) ? data.dirFull : {};
		if (!mods.length) {
			$list.html('<p class="aud-cfg-empty">No hay modulos, directorios o procesos disponibles.</p>');
			return;
		}
		var html = '';
		$.each(mods, function (i, m) {
			var org = m.Org_Cod;
			var fullMod = flagOn(modFull, org);
			var dirs = m.directorios || [];
			var nPcs = 0;
			$.each(dirs, function (d, dir) {
				nPcs += (dir.procesos || []).length;
			});
			var anySel = fullMod;
			if (!anySel) {
				$.each(dirs, function (d, dir) {
					if (dirHasSelection(dir, fullMod, dirFull, selected)) {
						anySel = true;
						return false;
					}
				});
			}
			html += '<div class="aud-cfg-mod' + (anySel ? '' : ' aud-cfg-collapsed') + '" data-org="' + org + '">';
			html += '<div class="aud-cfg-mod-head">' + chevron(!anySel) + '<label>';
			html += '<input type="checkbox" class="aud-cfg-mod-chk" ' + (fullMod ? 'checked="checked"' : '') + ' />';
			html += esc($.trim(m.Org_Des || ('Modulo ' + org)));
			html += '</label><span class="aud-cfg-count">(' + dirs.length + ' dir. / ' + nPcs + ' proc.)</span></div>';
			html += '<div class="aud-cfg-mod-body">';
			$.each(dirs, function (j, dir) {
				var dirOrg = dir.Org_Cod;
				var fullDir = fullMod || flagOn(dirFull, dirOrg);
				var procesos = dir.procesos || [];
				if (dir.es_modulo) {
					$.each(procesos, function (k, p) {
						var key = dirOrg + '_' + p.Pcs_Cod;
						html += pcsHtml(dirOrg, p, fullDir || flagOn(selected, key));
					});
					return;
				}
				var dirSel = fullDir;
				if (!dirSel) {
					$.each(procesos, function (k, p) {
						if (flagOn(selected, dirOrg + '_' + p.Pcs_Cod)) {
							dirSel = true;
							return false;
						}
					});
				}
				html += '<div class="aud-cfg-dir' + (dirSel ? '' : ' aud-cfg-collapsed') + '" data-org="' + dirOrg + '">';
				html += '<div class="aud-cfg-dir-head">' + chevron(!dirSel) + '<label>';
				html += '<input type="checkbox" class="aud-cfg-dir-chk" ' + (fullDir ? 'checked="checked"' : '') + ' />';
				html += esc($.trim(dir.Org_Des || ('Directorio ' + dirOrg)));
				html += '</label><span class="aud-cfg-count">(' + procesos.length + ')</span></div>';
				html += '<div class="aud-cfg-dir-body">';
				$.each(procesos, function (k, p) {
					var key = dirOrg + '_' + p.Pcs_Cod;
					html += pcsHtml(dirOrg, p, fullDir || flagOn(selected, key));
				});
				html += '</div></div>';
			});
			html += '</div></div>';
		});
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
		setStatus(data && data.hasConfig
			? ('Configuracion activa: ' + nRules + ' regla(s). El monitor registra la actividad de los seleccionados en todo el sistema.')
			: 'Sin configuracion: se registran los eventos de las tablas por defecto (AUDIT_TABLES).', true);
	}

	function cargar() {
		$list.html('<p class="aud-cfg-empty">Cargando...</p>');
		$.getJSON(window.location.pathname, { listConfigAjax: 1 }, function (data) {
			render(data);
		}).fail(function () {
			$list.html('<p class="aud-cfg-empty">No se pudo cargar la configuracion.</p>');
			setStatus('Error al cargar.', false);
		});
	}

	$list.on('click', '.aud-cfg-toggle', function (e) {
		e.preventDefault();
		var $box = $(this).closest('.aud-cfg-dir, .aud-cfg-mod');
		setCollapsed($box, !$box.hasClass('aud-cfg-collapsed'));
	});

	$list.on('change', '.aud-cfg-mod-chk', function () {
		var on = $(this).is(':checked');
		var $mod = $(this).closest('.aud-cfg-mod');
		$mod.find('.aud-cfg-pcs-chk, .aud-cfg-dir-chk').prop('checked', on).prop('indeterminate', false);
		$(this).prop('indeterminate', false);
		if (on) {
			setCollapsed($mod, false);
		}
		syncModCheckbox($mod);
	});

	$list.on('change', '.aud-cfg-dir-chk', function () {
		var on = $(this).is(':checked');
		var $dir = $(this).closest('.aud-cfg-dir');
		$dir.find('.aud-cfg-pcs-chk').prop('checked', on);
		$(this).prop('indeterminate', false);
		if (on) {
			setCollapsed($dir, false);
			setCollapsed($dir.closest('.aud-cfg-mod'), false);
		}
		syncModCheckbox($dir.closest('.aud-cfg-mod'));
	});

	$list.on('change', '.aud-cfg-pcs-chk', function () {
		var $mod = $(this).closest('.aud-cfg-mod');
		if ($(this).is(':checked')) {
			setCollapsed($mod, false);
			var $dir = $(this).closest('.aud-cfg-dir');
			if ($dir.length) {
				setCollapsed($dir, false);
			}
		}
		syncModCheckbox($mod);
	});

	$('#btnCfgTodos').on('click', function () {
		$list.find('.aud-cfg-pcs-chk, .aud-cfg-mod-chk, .aud-cfg-dir-chk').prop('checked', true).prop('indeterminate', false);
	});

	$('#btnCfgNinguno').on('click', function () {
		$list.find('.aud-cfg-pcs-chk, .aud-cfg-mod-chk, .aud-cfg-dir-chk').prop('checked', false).prop('indeterminate', false);
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

	$('#btnCfgRecargar').on('click', function () {
		cargar();
	});

	$('#audCfgFilter').on('keyup', function () {
		var q = $.trim($(this).val() || '').toLowerCase();
		$list.find('.aud-cfg-mod').each(function () {
			var $mod = $(this);
			if (!q) {
				$mod.show();
				$mod.find('.aud-cfg-dir, .aud-cfg-pcs').show();
				return;
			}
			var modTxt = $.trim($mod.find('.aud-cfg-mod-head').text() || '').toLowerCase();
			var modHit = modTxt.indexOf(q) !== -1;
			var any = modHit;
			$mod.find('.aud-cfg-dir').each(function () {
				var $dir = $(this);
				var dirTxt = $.trim($dir.find('.aud-cfg-dir-head').text() || '').toLowerCase();
				var dirHit = modHit || dirTxt.indexOf(q) !== -1;
				var pcsHit = false;
				$dir.find('.aud-cfg-pcs').each(function () {
					var t = $.trim($(this).text() || '').toLowerCase();
					var hit = dirHit || t.indexOf(q) !== -1;
					$(this).toggle(hit);
					if (hit) {
						pcsHit = true;
					}
				});
				var showDir = dirHit || pcsHit;
				$dir.toggle(showDir);
				if (showDir) {
					any = true;
					setCollapsed($dir, false);
				}
			});
			$mod.children('.aud-cfg-mod-body').children('.aud-cfg-pcs').each(function () {
				var t = $.trim($(this).text() || '').toLowerCase();
				var hit = modHit || t.indexOf(q) !== -1;
				$(this).toggle(hit);
				if (hit) {
					any = true;
				}
			});
			$mod.toggle(any);
			if (any) {
				setCollapsed($mod, false);
			}
		});
	});

	$('#btnCfgGuardar').on('click', function () {
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
					setStatus(resp.message || 'Guardado.', true);
					if (typeof $.alert === 'function') {
						$.alert(resp.message || 'Configuracion guardada.');
					}
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

	cargar();
});
