/**
 * Logica de seleccion jerarquica y guardado para aud_adm_config_monitoreo.
 * Permite marcar/desmarcar modulos, directorios y procesos para auditoria.
 * Incluye filtro visual interactivo por Rol, por Usuario, Modo Estricto y Estado de Auditoria.
 * Restringe la modificacion y guardado exclusivamente al Administrador de Sistemas.
 */
(function ($) {
	'use strict';

	var $list = $('#audCfgList');
	var $status = $('#audCfgStatus');
	var esAdmin = (typeof window.audEsAdminSistemas !== 'undefined') ? !!window.audEsAdminSistemas : true;

	// Variables para estado de filtros
	var filtroPcsMap = null; // Map { pcsId: true } o null si no hay filtro activo
	var filtroTipo = '';     // 'Rol' o 'Usuario'
	var filtroNombre = '';   // Nombre del rol o usuario seleccionado

	function setStatus(msg, ok) {
		$status.text(msg).css('color', ok ? '#2e7d32' : '#c62828');
	}

	function esc(s) {
		if (s == null) return '';
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function collectItems() {
		var items = [];
		$list.children('.aud-cfg-mod').each(function () {
			var $mod = $(this);
			var org = parseInt($mod.attr('data-org'), 10) || 0;
			if (org <= 0) return;
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
		if (typeof data.esAdmin !== 'undefined') {
			esAdmin = !!data.esAdmin;
		}

		if (!mods.length) {
			$list.html('<p class="aud-cfg-empty">No hay modulos, directorios o procesos disponibles.</p>');
			return;
		}

		var disAttr = esAdmin ? '' : ' disabled="disabled" title="Modo solo lectura"';
		var html = '';

		$.each(mods, function (i, mod) {
			var modOrg = mod.Org_Cod;
			var isModFull = !!modFull[modOrg];
			html += '<div class="aud-cfg-mod aud-cfg-collapsed" data-org="' + modOrg + '">';
			html += '<div class="aud-cfg-mod-head">';
			html += '<a href="#" class="aud-cfg-toggle"><span class="glyphicon glyphicon-chevron-right"></span></a> ';
			html += '<label><input type="checkbox" class="aud-cfg-mod-chk"' + (isModFull ? ' checked="checked"' : '') + disAttr + ' /> ';
			html += '<strong>' + esc(mod.Org_Des) + '</strong></label>';
			html += '</div>';
			html += '<div class="aud-cfg-mod-body">';

			var dirs = mod.directorios || [];
			$.each(dirs, function (j, dir) {
				var dirOrg = dir.Org_Cod;
				var isDirFull = isModFull || !!dirFull[dirOrg];
				var pcsList = dir.procesos || [];

				if (dir.es_modulo) {
					$.each(pcsList, function (k, pcs) {
						var selKey = dirOrg + '_' + pcs.Pcs_Cod;
						var chk = isDirFull || !!selected[selKey];
						html += '<div class="aud-cfg-pcs" data-pcs="' + pcs.Pcs_Cod + '">';
						html += '<label class="aud-cfg-pcs-label"><input type="checkbox" class="aud-cfg-pcs-chk" value="' + pcs.Pcs_Cod + '"' + (chk ? ' checked="checked"' : '') + disAttr + ' /> ';
						html += esc(pcs.Pcs_Lin) + '</label>';
						html += '</div>';
					});
					return;
				}

				html += '<div class="aud-cfg-dir aud-cfg-collapsed" data-org="' + dirOrg + '">';
				html += '<div class="aud-cfg-dir-head">';
				html += '<a href="#" class="aud-cfg-toggle"><span class="glyphicon glyphicon-chevron-right"></span></a> ';
				html += '<label><input type="checkbox" class="aud-cfg-dir-chk"' + (isDirFull ? ' checked="checked"' : '') + disAttr + ' /> ';
				html += esc(dir.Org_Des) + ' <span class="aud-cfg-count">(' + pcsList.length + ')</span></label>';
				html += '</div>';
				html += '<div class="aud-cfg-dir-body">';

				$.each(pcsList, function (k, pcs) {
					var selKey = dirOrg + '_' + pcs.Pcs_Cod;
					var chk = isDirFull || !!selected[selKey];
					html += '<div class="aud-cfg-pcs" data-pcs="' + pcs.Pcs_Cod + '">';
					html += '<label class="aud-cfg-pcs-label"><input type="checkbox" class="aud-cfg-pcs-chk" value="' + pcs.Pcs_Cod + '"' + (chk ? ' checked="checked"' : '') + disAttr + ' /> ';
					html += esc(pcs.Pcs_Lin) + '</label>';
					html += '</div>';
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

		// Si habia un filtro previo, reaplicar
		if (filtroPcsMap !== null) {
			aplicarFiltros();
		}
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

	/** Cargar combo de usuarios */
	function cargarUsuarios() {
		var $sel = $('#audCfgFiltroUsu');
		$.getJSON(window.location.pathname, { listUsuariosAjax: 1 }, function (resp) {
			if (resp && resp.success && resp.rows) {
				var opts = '<option value="">-- Todos los usuarios --</option>';
				$.each(resp.rows, function (i, u) {
					var extra = u.Roles ? (' (' + u.Roles + ')') : '';
					opts += '<option value="' + u.Usu_Cod + '">' + esc(u.Usu_Nom + extra) + '</option>';
				});
				$sel.html(opts);
			}
		});
	}

	/** Aplica simultaneamente: busqueda de texto, filtro por rol/usuario, estado de auditoria y modo estricto */
	function aplicarFiltros() {
		var q = $.trim($('#audCfgFilter').val() || '').toLowerCase();
		var hasPcsFilter = (filtroPcsMap !== null);
		var estadoAudit = $('#audCfgFiltroAudit').val() || 'todos';
		var modoEstricto = $('#audCfgModoEstricto').is(':checked');

		// Limpiar badges previos
		$list.find('.aud-badge-assigned').remove();

		var conteoTotalAsignados = 0;
		var conteoAuditados = 0;
		var conteoPendientes = 0;

		$list.find('.aud-cfg-mod').each(function () {
			var $mod = $(this);
			var modTxt = $.trim($mod.find('.aud-cfg-mod-head').text() || '').toLowerCase();
			var modHitText = !q || (modTxt.indexOf(q) !== -1);
			var modHasVisiblePcs = false;
			var modHasRolePcs = false;

			$mod.find('.aud-cfg-dir').each(function () {
				var $dir = $(this);
				var dirTxt = $.trim($dir.find('.aud-cfg-dir-head').text() || '').toLowerCase();
				var dirHitText = modHitText || (dirTxt.indexOf(q) !== -1);
				var dirHasVisiblePcs = false;
				var dirHasRolePcs = false;

				$dir.find('.aud-cfg-pcs').each(function () {
					var $pcs = $(this);
					var $chk = $pcs.find('.aud-cfg-pcs-chk');
					var pcsId = parseInt($pcs.attr('data-pcs'), 10) || parseInt($chk.val(), 10) || 0;
					var isChecked = $chk.is(':checked');
					var t = $.trim($pcs.text() || '').toLowerCase();

					var hitText = dirHitText || (t.indexOf(q) !== -1);
					var hitRoleUser = !hasPcsFilter || (filtroPcsMap[pcsId] === true);

					if (hasPcsFilter && filtroPcsMap[pcsId] === true) {
						conteoTotalAsignados++;
						dirHasRolePcs = true;
						modHasRolePcs = true;
						if (isChecked) {
							conteoAuditados++;
						} else {
							conteoPendientes++;
						}
					}

					var hitEstado = true;
					if (estadoAudit === 'marcados') {
						hitEstado = isChecked;
					} else if (estadoAudit === 'no_marcados') {
						hitEstado = !isChecked;
					}

					var visible = hitText && hitRoleUser && hitEstado;
					$pcs.toggle(visible);
					if (visible) {
						dirHasVisiblePcs = true;
						if (hasPcsFilter && filtroPcsMap[pcsId] === true) {
							$pcs.find('.aud-cfg-pcs-label').append('<span class="aud-badge-assigned"><span class="glyphicon glyphicon-ok"></span> Asignado</span>');
						}
					}
				});

				// En modo estricto con filtro de rol activo, si el directorio no tiene procesos del rol, no se muestra
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

			// Procesos directamente hijos del modulo
			$mod.children('.aud-cfg-mod-body').children('.aud-cfg-pcs').each(function () {
				var $pcs = $(this);
				var $chk = $pcs.find('.aud-cfg-pcs-chk');
				var pcsId = parseInt($pcs.attr('data-pcs'), 10) || parseInt($chk.val(), 10) || 0;
				var isChecked = $chk.is(':checked');
				var t = $.trim($pcs.text() || '').toLowerCase();

				var hitText = modHitText || (t.indexOf(q) !== -1);
				var hitRoleUser = !hasPcsFilter || (filtroPcsMap[pcsId] === true);

				if (hasPcsFilter && filtroPcsMap[pcsId] === true) {
					conteoTotalAsignados++;
					modHasRolePcs = true;
					if (isChecked) {
						conteoAuditados++;
					} else {
						conteoPendientes++;
					}
				}

				var hitEstado = true;
				if (estadoAudit === 'marcados') {
					hitEstado = isChecked;
				} else if (estadoAudit === 'no_marcados') {
					hitEstado = !isChecked;
				}

				var visible = hitText && hitRoleUser && hitEstado;
				$pcs.toggle(visible);
				if (visible) {
					modHasVisiblePcs = true;
					if (hasPcsFilter && filtroPcsMap[pcsId] === true) {
						$pcs.find('.aud-cfg-pcs-label').append('<span class="aud-badge-assigned"><span class="glyphicon glyphicon-ok"></span> Asignado</span>');
					}
				}
			});

			// En modo estricto con filtro de rol activo, si el modulo no tiene procesos del rol, no se muestra
			var modVisible = modHasVisiblePcs;
			if (hasPcsFilter && modoEstricto && !modHasRolePcs) {
				modVisible = false;
			}
			$mod.toggle(modVisible);
			if (modVisible && (hasPcsFilter || q || estadoAudit !== 'todos')) {
				setCollapsed($mod, false);
			}
		});

		// Actualizar contadores y aviso informativo si hay filtro
		if (hasPcsFilter) {
			actualizarMensajeFiltro(conteoTotalAsignados, conteoAuditados, conteoPendientes);
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

	$('#btnCfgTodos').on('click', function () {
		if (!esAdmin) return;
		$list.find('.aud-cfg-pcs-chk, .aud-cfg-mod-chk, .aud-cfg-dir-chk').prop('checked', true).prop('indeterminate', false);
		if (filtroPcsMap !== null) aplicarFiltros();
	});

	$('#btnCfgNinguno').on('click', function () {
		if (!esAdmin) return;
		$list.find('.aud-cfg-pcs-chk, .aud-cfg-mod-chk, .aud-cfg-dir-chk').prop('checked', false).prop('indeterminate', false);
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

	/** Evento Filtro por Rol */
	$('#audCfgFiltroRol').on('change', function () {
		var rolVal = $(this).val();
		if (!rolVal) {
			if ($('#audCfgFiltroUsu').val() === '') {
				limpiarFiltroAsignacion();
			}
			return;
		}
		$('#audCfgFiltroUsu').val(''); // Limpiar selector usuario
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

	/** Evento Filtro por Usuario */
	$('#audCfgFiltroUsu').on('change', function () {
		var usuVal = $(this).val();
		if (!usuVal) {
			if ($('#audCfgFiltroRol').val() === '') {
				limpiarFiltroAsignacion();
			}
			return;
		}
		$('#audCfgFiltroRol').val(''); // Limpiar selector rol
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
		var msg = '<span class="glyphicon glyphicon-filter"></span> Mostrando procesos asignados a '
			+ '<strong>' + esc(filtroTipo) + ': ' + esc(filtroNombre) + '</strong> &mdash; '
			+ '<strong>' + total + '</strong> proceso(s) autorizados ('
			+ '<span style="color:#0f7b3d;"><strong>' + auditados + '</strong> marcados en monitoreo</span>, '
			+ '<span style="color:#b91c1c;"><strong>' + pendientes + '</strong> sin auditar</span>). '
			+ 'Los modulos y procesos ajenos se encuentran filtrados.';
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
		$list.find('.aud-cfg-pcs:visible').each(function () {
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
		$list.find('.aud-cfg-pcs:visible').each(function () {
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
					cargar();
				} else {
					setStatus((resp && resp.message) ? resp.message : 'Error al guardar.', false);
				}
			},
			error: function () {
				setStatus('Error de conexion al guardar.', false);
			},
			complete: function () {
				$btn.prop('disabled', false);
			}
		});
	});

	// Inicializacion
	cargar();
	cargarRoles();
	cargarUsuarios();

})(jQuery);
