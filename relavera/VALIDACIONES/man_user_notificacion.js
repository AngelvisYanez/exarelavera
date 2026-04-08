/**
 * Listado usuarios Relavera — man_user_notificacion.php (tabla solo lectura + modal edición)
 */
(function () {
    var tbody = document.getElementById('tbody_usu_notif');
    var btn = document.getElementById('btn_filtrar_usu_notif');
    var elNom = document.getElementById('filtro_usu_nombre');
    var elCed = document.getElementById('filtro_usu_cedula');
    var btnModalGuardar = document.getElementById('btn_modal_guardar_usu_notif');

    function escapeHtml(s) {
        if (s == null || s === '') return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function escapeAttr(s) {
        if (s == null || s === '') return '';
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    /** Solo dígitos 0-9; permite empezar con 0. */
    function soloDigitosTel(str) {
        return String(str || '').replace(/\D/g, '');
    }

    function enlazarInputTelefonoModal() {
        var inp = document.getElementById('modal_prs_tel');
        if (!inp || inp.getAttribute('data-digitos-only') === '1') return;
        inp.setAttribute('data-digitos-only', '1');
        inp.addEventListener('input', function () {
            var v = soloDigitosTel(this.value);
            if (v !== this.value) this.value = v;
        });
        inp.addEventListener('paste', function () {
            var self = this;
            setTimeout(function () {
                self.value = soloDigitosTel(self.value);
            }, 0);
        });
    }

    function buildQuery() {
        var p = [];
        if (elNom && elNom.value.trim()) p.push('filtro_nombre=' + encodeURIComponent(elNom.value.trim()));
        if (elCed && elCed.value.trim()) p.push('filtro_cedula=' + encodeURIComponent(elCed.value.trim()));
        return p.length ? '&' + p.join('&') : '';
    }

    function abrirModalDesdeBoton(btnEl) {
        if (!btnEl || !window.jQuery) return;
        var usu = btnEl.getAttribute('data-usu-cod') || '';
        var prs = btnEl.getAttribute('data-prs-cod') || '';
        var nom = btnEl.getAttribute('data-usuario') || '';
        var tel = btnEl.getAttribute('data-prs-tel') || '';
        var ntf = (btnEl.getAttribute('data-usu-ntf') || 'N').toUpperCase();
        if (ntf !== 'S' && ntf !== 'N') ntf = 'N';

        document.getElementById('modal_usu_cod').value = usu;
        document.getElementById('modal_prs_cod').value = prs;
        document.getElementById('modal_prs_tel').value = soloDigitosTel(tel);
        document.getElementById('modal_usu_ntf').value = ntf;
        document.getElementById('modal_usuario_lbl').innerHTML = '<strong>' + escapeHtml(nom) + '</strong>';
        window.jQuery('#modal_edit_usu_notif').modal('show');
    }

    function guardarModal() {
        if (!window.jQuery) return;
        var usu = document.getElementById('modal_usu_cod').value;
        var prs = document.getElementById('modal_prs_cod').value;
        var tel = soloDigitosTel(document.getElementById('modal_prs_tel').value);
        var ntf = document.getElementById('modal_usu_ntf').value;

        var fd = new FormData();
        fd.append('guardarUsuarioNotifModalAjax', '1');
        fd.append('Usu_Cod', usu);
        fd.append('Prs_Cod', prs);
        fd.append('Prs_Tel', tel);
        fd.append('Usu_Ntf', ntf);

        var $btn = window.jQuery('#btn_modal_guardar_usu_notif');
        $btn.prop('disabled', true);

        fetch('man_user_notificacion.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.success) {
                    window.jQuery('#modal_edit_usu_notif').modal('hide');
                    cargarLista();
                } else {
                    alert((data && data.message) ? data.message : 'No se pudo guardar.');
                }
            })
            .catch(function () {
                alert('Error de red o del servidor.');
            })
            .then(function () {
                $btn.prop('disabled', false);
            });
    }

    function cargarLista() {
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Cargando…</td></tr>';
        var q = buildQuery();
        fetch('man_user_notificacion.php?cargarListaUserNotifAjax=1' + q, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var rows = (data && data.rows) ? data.rows : [];
                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No hay registros para mostrar.</td></tr>';
                    return;
                }
                var html = '';
                for (var j = 0; j < rows.length; j++) {
                    var u = rows[j];
                    var suc = (u.Sucursal != null) ? String(u.Sucursal).trim() : '';
                    var prsCod = u.Prs_Cod != null ? String(u.Prs_Cod) : '';
                    var prsTel = (u.Prs_Tel != null) ? String(u.Prs_Tel).trim() : '';
                    var telOtro = (u.Telefono != null) ? String(u.Telefono).trim() : '';
                    var usuCod = u.Usu_Cod != null ? String(u.Usu_Cod) : '';
                    var usuNtf = u.Usu_Ntf != null ? String(u.Usu_Ntf).trim().toUpperCase() : 'N';
                    if (usuNtf !== 'S' && usuNtf !== 'N') usuNtf = 'N';
                    var nom = u.Usuario != null ? String(u.Usuario) : '';

                    var telCell = '';
                    if (prsTel) {
                        telCell = escapeHtml(prsTel);
                    } else if (telOtro) {
                        telCell = '<span class="tel-alt-muted">' + escapeHtml(telOtro) + '</span> <span class="text-muted">(otro en ficha)</span>';
                    } else {
                        telCell = '<span class="sin-tel">Sin tel&eacute;fono</span>';
                    }

                    var ntfCell = (usuNtf === 'S')
                        ? '<span class="badge-ntf-s">S&iacute;</span>'
                        : '<span class="badge-ntf-n">No</span>';

                    html += '<tr>';
                    html += '<td class="text-center text-muted">' + (j + 1) + '</td>';
                    html += '<td>' + escapeHtml(u.Usuario || '') + '</td>';
                    html += '<td>' + escapeHtml(u.Prs_Ced || '') + '</td>';
                    html += '<td>' + (suc ? escapeHtml(suc) : '<span class="text-muted">—</span>') + '</td>';
                    html += '<td class="text-center">' + ntfCell + '</td>';
                    html += '<td>' + telCell + '</td>';
                    html += '<td class="text-center">';
                    html += '<button type="button" class="btn btn-primary btn-xs btn-editar-usu-notif"';
                    html += ' data-usu-cod="' + escapeAttr(usuCod) + '"';
                    html += ' data-prs-cod="' + escapeAttr(prsCod) + '"';
                    html += ' data-prs-tel="' + escapeAttr(prsTel) + '"';
                    html += ' data-usu-ntf="' + escapeAttr(usuNtf) + '"';
                    html += ' data-usuario="' + escapeAttr(nom) + '"';
                    html += ' title="Editar tel&eacute;fono y notificaci&oacute;n">';
                    html += '<span class="glyphicon glyphicon-pencil"></span> Editar';
                    html += '</button>';
                    html += '</td>';
                    html += '</tr>';
                }
                tbody.innerHTML = html;
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar la lista.</td></tr>';
            });
    }

    if (btn) btn.addEventListener('click', cargarLista);

    if (tbody) {
        tbody.addEventListener('click', function (ev) {
            var b = ev.target.closest('.btn-editar-usu-notif');
            if (!b) return;
            abrirModalDesdeBoton(b);
        });
    }

    if (btnModalGuardar) {
        btnModalGuardar.addEventListener('click', guardarModal);
    }

    function bindEnter(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter' || ev.keyCode === 13) {
                ev.preventDefault();
                cargarLista();
            }
        });
    }
    bindEnter('filtro_usu_nombre');
    bindEnter('filtro_usu_cedula');

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            enlazarInputTelefonoModal();
            cargarLista();
        });
    } else {
        enlazarInputTelefonoModal();
        cargarLista();
    }
})();
