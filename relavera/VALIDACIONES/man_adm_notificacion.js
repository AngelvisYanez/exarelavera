/**
 * Notificaciones masivas WhatsApp — man_adm_notificacion.php (plantas / choferes)
 */
(function () {
    var grupoSel = document.getElementById('grupo_notif');
    var thead = document.getElementById('thead_notif');
    var tbody = document.getElementById('tbody_notif');
    var btn = document.getElementById('btn_enviar_notif');
    var tit = document.getElementById('titulo_notif');
    var ta = document.getElementById('mensaje_notif');
    var estado = document.getElementById('notif_estado');
    var txtAyuda = document.getElementById('txt_ayuda_grupo');
    var imgInput = document.getElementById('imagen_notif');
    var imgPreview = document.getElementById('preview_imagen_notif');

    var MAX_IMG_BYTES = 16 * 1024 * 1024;

    function bindImagenPreview() {
        if (!imgInput || !imgPreview) return;
        imgInput.addEventListener('change', function () {
            imgPreview.style.display = 'none';
            imgPreview.removeAttribute('src');
            if (!imgInput.files || !imgInput.files[0]) return;
            var f = imgInput.files[0];
            if (!/^image\//.test(f.type)) return;
            var r = new FileReader();
            r.onload = function (e) {
                imgPreview.src = e.target.result;
                imgPreview.style.display = 'block';
            };
            r.readAsDataURL(f);
        });
    }
    bindImagenPreview();

    function escapeHtml(s) {
        if (s == null || s === '') return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function telPlanta(r) {
        var k = ['Pla_Wat', 'Pep_Tel_Admin', 'Prs_Tel_Admin', 'Prs_Te2_Admin'];
        for (var i = 0; i < k.length; i++) {
            if (r[k[i]] != null && String(r[k[i]]).trim() !== '') return String(r[k[i]]).trim();
        }
        return '';
    }

    function actualizarAyuda(grupo) {
        if (!txtAyuda) return;
        if (grupo === 'plantas') {
            txtAyuda.textContent = 'Plantas: se usa el primer teléfono disponible (WhatsApp de planta, luego administrador).';
        } else {
            txtAyuda.textContent = 'Choferes: teléfono en chofer o en la ficha de persona.';
        }
    }

    function numColumnas(grupo) {
        if (grupo === 'plantas') return 5;
        return 6;
    }

    function actualizarFiltrosVisibles() {
        var g = grupoSel ? grupoSel.value : 'plantas';
        var wp = document.getElementById('wrap_filtros_plantas');
        var wc = document.getElementById('wrap_filtros_choferes');
        if (wp) wp.style.display = g === 'plantas' ? 'block' : 'none';
        if (wc) wc.style.display = g === 'choferes' ? 'block' : 'none';
    }

    /** Query string con filtros según grupo (nombres GET que espera PHP). */
    function buildFiltrosQuery() {
        var g = grupoSel ? grupoSel.value : 'plantas';
        var parts = [];
        if (g === 'plantas') {
            var elN = document.getElementById('filtro_pla_nombre');
            var elC = document.getElementById('filtro_pla_cedula');
            if (elN && elN.value.trim()) parts.push('filtro_nombre=' + encodeURIComponent(elN.value.trim()));
            if (elC && elC.value.trim()) parts.push('filtro_cedula=' + encodeURIComponent(elC.value.trim()));
        } else {
            var elP = document.getElementById('filtro_cho_planta');
            var elN2 = document.getElementById('filtro_cho_nombre');
            var elC2 = document.getElementById('filtro_cho_cedula');
            if (elP && elP.value.trim()) parts.push('filtro_planta=' + encodeURIComponent(elP.value.trim()));
            if (elN2 && elN2.value.trim()) parts.push('filtro_nombre=' + encodeURIComponent(elN2.value.trim()));
            if (elC2 && elC2.value.trim()) parts.push('filtro_cedula=' + encodeURIComponent(elC2.value.trim()));
        }
        return parts.length ? '&' + parts.join('&') : '';
    }

    function renderThead(grupo) {
        var h = '';
        if (grupo === 'plantas') {
            h = '<tr><th style="width:40px;"><input type="checkbox" id="chk_maestro" title="Seleccionar / quitar todos" /></th><th style="width:46px;" class="text-center">N°</th><th>Planta</th><th>Cliente</th><th>Teléfono (envío)</th></tr>';
        } else {
            h = '<tr><th style="width:40px;"><input type="checkbox" id="chk_maestro" title="Seleccionar / quitar todos" /></th><th style="width:46px;" class="text-center">N°</th><th>Chofer</th><th>Cédula / RUC</th><th>Planta</th><th>Teléfono</th></tr>';
        }
        thead.innerHTML = h;
    }

    function renderTabla(grupo, rows) {
        renderThead(grupo);
        actualizarAyuda(grupo);
        if (!rows || !rows.length) {
            tbody.innerHTML = '<tr><td colspan="' + numColumnas(grupo) + '" class="text-center text-muted">No hay registros para mostrar.</td></tr>';
            bindMaestro();
            return;
        }
        var html = '';
        if (grupo === 'plantas') {
            for (var j = 0; j < rows.length; j++) {
                var p = rows[j];
                console.log(p);

                var idp = parseInt(p.Pla_Cod, 10) || 0;
                var tp = telPlanta(p);
                html += '<tr><td class="text-center"><input type="checkbox" class="chk-destino" value="' + idp + '" /></td>';
                html += '<td class="text-center text-muted">' + (j + 1) + '</td>';
                html += '<td>' + escapeHtml(p.Pla_Nom || '') + '</td>';
                html += '<td>' + escapeHtml((p.Cliente || '').trim()) + '</td>';
                html += '<td>' + (tp ? escapeHtml(tp) : '<span class="sin-tel">Sin teléfono</span>') + '</td></tr>';
            }
        } else {
            for (var k = 0; k < rows.length; k++) {
                var c = rows[k];
                console.log(c);
                var idc = parseInt(c.Cho_Cod, 10) || 0;
                var tc = (c.Telefono != null) ? String(c.Telefono).trim() : '';
                var plnom = (c.Pla_Nom != null) ? String(c.Pla_Nom).trim() : '';
                html += '<tr><td class="text-center"><input type="checkbox" class="chk-destino" value="' + idc + '" /></td>';
                html += '<td class="text-center text-muted">' + (k + 1) + '</td>';
                html += '<td>' + escapeHtml(c.Chofer || '') + '</td>';
                html += '<td>' + escapeHtml(c.Cho_Ced || '') + '</td>';
                html += '<td>' + (plnom ? escapeHtml(plnom) : '<span class="text-muted">—</span>') + '</td>';
                html += '<td>' + (tc ? escapeHtml(tc) : '<span class="sin-tel">Sin teléfono</span>') + '</td></tr>';
            }
        }
        tbody.innerHTML = html;
        bindMaestro();
    }

    function getChks() {
        return document.querySelectorAll('.chk-destino');
    }

    function bindMaestro() {
        var chkMaestro = document.getElementById('chk_maestro');
        var chks = getChks();
        function setTodos(val) {
            for (var i = 0; i < chks.length; i++) chks[i].checked = val;
            if (chkMaestro) chkMaestro.checked = val && chks.length > 0;
        }
        function syncMaestro() {
            if (!chks.length || !chkMaestro) return;
            var all = true;
            for (var i = 0; i < chks.length; i++) {
                if (!chks[i].checked) all = false;
            }
            chkMaestro.checked = all && chks.length > 0;
        }
        if (chkMaestro) {
            chkMaestro.onchange = function () { setTodos(chkMaestro.checked); };
        }
        for (var j = 0; j < chks.length; j++) {
            chks[j].onchange = syncMaestro;
        }
    }

    function cargarLista() {
        var g = grupoSel ? grupoSel.value : 'plantas';
        actualizarFiltrosVisibles();
        tbody.innerHTML = '<tr><td colspan="' + numColumnas(g) + '" class="text-center text-muted">Cargando…</td></tr>';
        var q = buildFiltrosQuery();
        fetch('man_adm_notificacion.php?cargarListaNotifAjax=1&grupo=' + encodeURIComponent(g) + q, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success || !data.rows) {
                    renderTabla(g, []);
                    return;
                }
                renderTabla(data.grupo || g, data.rows);
            })
            .catch(function () {
                var gc = grupoSel ? grupoSel.value : 'plantas';
                tbody.innerHTML = '<tr><td colspan="' + numColumnas(gc) + '" class="text-center text-danger">Error al cargar la lista.</td></tr>';
            });
    }

    if (grupoSel) {
        grupoSel.addEventListener('change', cargarLista);
    }

    var btnFilPla = document.getElementById('btn_filtrar_notif');
    var btnFilCho = document.getElementById('btn_filtrar_notif_cho');
    if (btnFilPla) btnFilPla.addEventListener('click', cargarLista);
    if (btnFilCho) btnFilCho.addEventListener('click', cargarLista);

    function bindEnterBuscar(ids) {
        for (var i = 0; i < ids.length; i++) {
            (function (id) {
                var el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Enter' || ev.keyCode === 13) {
                        ev.preventDefault();
                        cargarLista();
                    }
                });
            })(ids[i]);
        }
    }
    bindEnterBuscar(['filtro_pla_nombre', 'filtro_pla_cedula', 'filtro_cho_planta', 'filtro_cho_nombre', 'filtro_cho_cedula']);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', cargarLista);
    } else {
        cargarLista();
    }

    if (btn) {
        btn.addEventListener('click', function () {
            var titulo = (tit && tit.value) ? tit.value.trim() : '';
            if (!titulo) {
                alert('Escriba el título del mensaje.');
                return;
            }
            var mensaje = (ta && ta.value) ? ta.value.trim() : '';
            if (!mensaje) {
                alert('Escriba el mensaje.');
                return;
            }
            var chks = getChks();
            var ids = [];
            for (var k = 0; k < chks.length; k++) {
                if (chks[k].checked) ids.push(chks[k].value);
            }
            if (!ids.length) {
                alert('Seleccione al menos un destinatario.');
                return;
            }
            if (imgInput && imgInput.files && imgInput.files[0] && imgInput.files[0].size > MAX_IMG_BYTES) {
                alert('La imagen no puede superar 16 MB.');
                return;
            }
            estado.textContent = 'Enviando...';
            estado.className = 'text-info';
            btn.disabled = true;
            var fd = new FormData();
            fd.append('enviarNotifMasivaAjax', '1');
            fd.append('grupo', grupoSel ? grupoSel.value : 'plantas');
            fd.append('titulo', titulo);
            fd.append('mensaje', mensaje);
            if (imgInput && imgInput.files && imgInput.files[0]) {
                fd.append('imagen_notif', imgInput.files[0]);
            }
            for (var m = 0; m < ids.length; m++) {
                fd.append('ids[]', ids[m]);
            }
            fetch('man_adm_notificacion.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    estado.textContent = data.message || (data.success ? 'Listo.' : 'Error.');
                    if (!data.success) {
                        estado.className = 'text-danger';
                    } else {
                        estado.className = data.fallidos > 0 ? 'text-warning' : 'text-success';
                    }
                })
                .catch(function () {
                    estado.textContent = 'Error de red o del servidor.';
                    estado.className = 'text-danger';
                })
                .then(function () {
                    btn.disabled = false;
                });
        });
    }
})();
