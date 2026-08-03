/**
 * ppto_param_contable_js.js
 * UI Parametrizacion Contable (Fase A) - tab Admin.
 */
(function () {
    function boot() {
        var root = document.getElementById('ppto_pc_root');
        if (!root || root.getAttribute('data-pc-ready') === '1') {
            return;
        }
        root.setAttribute('data-pc-ready', '1');

        var EMP = parseInt(root.getAttribute('data-emp'), 10) || 0;
        var ANIO = parseInt(root.getAttribute('data-anio'), 10) || (new Date()).getFullYear();
        var MES = parseInt(root.getAttribute('data-mes'), 10) || 12;
        if (MES < 1) { MES = 1; }
        if (MES > 12) { MES = 12; }
        var URL = 'ppto_param_contable_ajax.php';
        var selectedPpa = 0;
        var selectedRubroLbl = '';
        var debounceTimer = null;

        (function initPeriodoHint() {
            var rangoEl = document.getElementById('pc_ejec_kpi_rango');
            if (rangoEl) {
                rangoEl.textContent = 'Enero a ' + [
                    '', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
                ][MES] + ' / ' + ANIO;
            }
        })();

        function money(n) {
            var x = Number(n) || 0;
            try {
                return x.toLocaleString('es-EC', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } catch (e) {
                return x.toFixed(2);
            }
        }

        function esc(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function showMsgIn(elId, ok, text, scroll) {
            var el = document.getElementById(elId);
            if (!el) {
                return;
            }
            if (!text) {
                el.className = 'alert ppto-pc-msg';
                el.textContent = '';
                return;
            }
            el.className = 'alert ppto-pc-msg show alert-' + (ok ? 'success' : 'danger');
            el.textContent = text;
            if (scroll && el.scrollIntoView) {
                try {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } catch (e) {
                    el.scrollIntoView(true);
                }
            }
            setTimeout(function () { el.classList.remove('show'); }, 6000);
        }

        function showMsg(ok, text) {
            showMsgIn('pc_alert', ok, text, true);
        }

        function showMsgPend(ok, text) {
            showMsgIn('pc_pend_alert', ok, text, true);
        }

        function api(action, data, method) {
            method = method || 'GET';
            var params = { action: action, emp_id: EMP, anio: ANIO };
            var k;
            data = data || {};
            for (k in data) {
                if (Object.prototype.hasOwnProperty.call(data, k)) {
                    params[k] = data[k];
                }
            }
            function parseJsonResponse(r) {
                return r.text().then(function (txt) {
                    var data;
                    try {
                        data = JSON.parse(txt);
                    } catch (e) {
                        if (r.status === 403 || /forbidden|403/i.test(txt)) {
                            throw new Error('Sin permiso para el endpoint AJAX (403).');
                        }
                        throw new Error('Respuesta no JSON (HTTP ' + r.status + ').');
                    }
                    if (!r.ok && (!data || data.ok === undefined)) {
                        throw new Error('HTTP ' + r.status);
                    }
                    return data;
                });
            }
            if (method === 'GET') {
                var qs = Object.keys(params).map(function (key) {
                    return encodeURIComponent(key) + '=' + encodeURIComponent(params[key] == null ? '' : params[key]);
                }).join('&');
                return fetch(URL + '?' + qs, { credentials: 'same-origin' })
                    .then(parseJsonResponse);
            }
            var fd = new FormData();
            Object.keys(params).forEach(function (key) {
                var v = params[key];
                if (Object.prototype.toString.call(v) === '[object Array]') {
                    v.forEach(function (item) { fd.append(key + '[]', item); });
                } else {
                    fd.append(key, v == null ? '' : v);
                }
            });
            return fetch(URL, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(parseJsonResponse);
        }

        function paintKpis(kpis, plan) {
            kpis = kpis || {};
            var pct = (kpis.pct_parametrizacion != null ? kpis.pct_parametrizacion : 0) + '%';
            document.getElementById('pc_kpi_pct').textContent = pct;
            document.getElementById('pc_kpi_ok').textContent = kpis.rubros_parametrizados != null ? kpis.rubros_parametrizados : '-';
            document.getElementById('pc_kpi_pend').textContent = kpis.rubros_pendientes != null ? kpis.rubros_pendientes : '-';
            document.getElementById('pc_kpi_cta').textContent = kpis.cuentas_asignadas != null ? kpis.cuentas_asignadas : '-';
            document.getElementById('pc_kpi_detalle').textContent = 'de ' + (kpis.rubros_detalle || 0) + ' detalle';
            document.getElementById('pc_kpi_sin').textContent = (kpis.cuentas_sin_rubro || 0) + ' sin rubro';
            if (plan && plan.pla_cod) {
                document.getElementById('pc_kpi_plan').textContent = 'Plan ' + plan.pla_cod + (plan.anio ? ' / ' + plan.anio : '');
            }
            var escP = document.getElementById('pc_esc_param_val');
            if (escP) {
                escP.textContent = pct;
            }
            if (kpis.cuentas_asignadas != null) {
                var mapEl = document.getElementById('pc_ejec_kpi_mapeos');
                if (mapEl && (mapEl.textContent === '-' || mapEl.textContent === '')) {
                    mapEl.textContent = kpis.cuentas_asignadas;
                }
            }
        }

        function paintSelectGrupos(selId, grupos, optTodas) {
            var sel = document.getElementById(selId);
            if (!sel) {
                return;
            }
            var cur = sel.value || 'todas';
            var html = '<option value="todas">' + esc(optTodas || '- Elija un grupo -') + '</option>';
            (grupos || []).forEach(function (g) {
                html += '<option value="' + esc(g.codigo) + '">'
                    + esc(g.codigo) + ' - ' + esc(g.descripcion)
                    + '</option>';
            });
            sel.innerHTML = html;
            sel.value = cur;
            if (sel.value !== cur) {
                sel.value = 'todas';
            }
        }

        function paintGruposBalance(grupos) {
            paintSelectGrupos('pc_pend_grupo', grupos, 'Grupo (balances): todos');
            paintSelectGrupos('pc_busca_grupo', grupos, '- Elija un grupo -');
        }

        function loadMeta() {
            return api('meta').then(function (res) {
                if (!res.ok) {
                    showMsg(false, res.message || 'No se pudo cargar la parametrizacion.');
                    return;
                }
                paintKpis(res.kpis, res.plan);
                paintGruposBalance(res.grupos_balance || []);
                if (!res.plan || !res.plan.pla_cod) {
                    showMsg(false, 'Sin plan de cuentas activo para la empresa/anio del filtro.');
                }
            }).catch(function (err) {
                showMsg(false, (err && err.message) ? err.message : 'Error de red al cargar metadatos.');
            });
        }

        var pcArbolCollapsed = {};
        var pcBuscaCollapsed = {};
        var pcPendCollapsed = {};

        function badgeHtml(row) {
            if (row.estado_param === 'grupo') {
                return '';
            }
            if (row.estado_param === 'completo') {
                return '<span class="ppto-pc-badge ok">' + esc(row.cuentas) + '</span>';
            }
            return '<span class="ppto-pc-badge pend">0</span>';
        }

        function pcArbolHasChildren(rows, ppaId) {
            for (var i = 0; i < rows.length; i++) {
                if (parseInt(rows[i].ppa_padre_id, 10) === ppaId) {
                    return true;
                }
            }
            return false;
        }

        function pcArbolApplyCollapsed(box) {
            var rows = box.querySelectorAll('.ppto-pc-tree-row');
            var byId = {};
            for (var i = 0; i < rows.length; i++) {
                byId[rows[i].getAttribute('data-ppa')] = rows[i];
            }
            for (var j = 0; j < rows.length; j++) {
                var el = rows[j];
                var padre = el.getAttribute('data-padre') || '0';
                var hidden = false;
                var walk = padre;
                while (walk && walk !== '0') {
                    if (pcArbolCollapsed[walk]) {
                        hidden = true;
                        break;
                    }
                    var pEl = byId[walk];
                    walk = pEl ? (pEl.getAttribute('data-padre') || '0') : '0';
                }
                el.classList.toggle('is-hidden', hidden);
            }
            var toggles = box.querySelectorAll('.ppto-pc-tree-toggle[data-toggle-ppa]');
            for (var t = 0; t < toggles.length; t++) {
                var tid = toggles[t].getAttribute('data-toggle-ppa');
                var collapsed = !!pcArbolCollapsed[tid];
                toggles[t].textContent = collapsed ? '\u25B6' : '\u25BC';
                toggles[t].setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                toggles[t].title = collapsed ? 'Expandir' : 'Minimizar';
            }
        }

        function loadArbol() {
            var filtro = document.getElementById('pc_filtro_arbol').value || 'todos';
            var box = document.getElementById('pc_arbol');
            box.innerHTML = '<div class="ppto-pc-empty">Cargando...</div>';
            return api('arbol', { filtro: filtro }).then(function (res) {
                if (!res.ok) {
                    box.innerHTML = '<div class="ppto-pc-empty">' + esc(res.message || 'Error') + '</div>';
                    return;
                }
                var rows = res.rows || [];
                if (!rows.length) {
                    box.innerHTML = '<div class="ppto-pc-empty">Sin rubros para el filtro.</div>';
                    return;
                }
                var html = '';
                rows.forEach(function (row) {
                    var isG = row.ppa_clase === 'G';
                    var pad = parseInt(row.indent_px, 10) || 0;
                    var padre = parseInt(row.ppa_padre_id, 10) || 0;
                    var hasKids = isG && pcArbolHasChildren(rows, row.ppa_id);
                    var toggle = hasKids
                        ? '<button type="button" class="ppto-pc-tree-toggle" data-toggle-ppa="' + row.ppa_id + '" aria-expanded="true" title="Minimizar">\u25BC</button>'
                        : '<span class="ppto-pc-tree-toggle is-leaf" aria-hidden="true"></span>';
                    html += '<div class="ppto-pc-tree-row' + (isG ? ' is-grupo' : '') + (selectedPpa === row.ppa_id ? ' active' : '') + '"'
                        + ' data-ppa="' + row.ppa_id + '" data-padre="' + padre + '" data-clase="' + esc(row.ppa_clase) + '"'
                        + ' style="padding-left:' + (8 + pad) + 'px;">'
                        + toggle
                        + badgeHtml(row)
                        + '<span class="pc-label">'
                        + '<strong>' + esc(row.ppa_codigo_clasificacion) + '</strong> '
                        + esc(row.ppa_descripcion)
                        + '</span></div>';
                });
                box.innerHTML = html;
                pcArbolApplyCollapsed(box);
            }).catch(function (err) {
                box.innerHTML = '<div class="ppto-pc-empty">' + esc((err && err.message) ? err.message : 'Error al cargar arbol.') + '</div>';
                showMsg(false, (err && err.message) ? err.message : 'Error al cargar arbol.');
            });
        }

        function loadDetalle(ppaId) {
            selectedPpa = ppaId;
            var box = document.getElementById('pc_detalle');
            var btnAdd = document.getElementById('pc_btn_agregar');
            var btnSug = document.getElementById('pc_btn_sugerir');
            box.innerHTML = '<div class="ppto-pc-empty">Cargando detalle...</div>';
            btnAdd.disabled = true;
            if (btnSug) {
                btnSug.disabled = true;
            }

            var nodes = document.querySelectorAll('#pc_arbol .ppto-pc-tree-row');
            for (var i = 0; i < nodes.length; i++) {
                nodes[i].classList.toggle('active', parseInt(nodes[i].getAttribute('data-ppa'), 10) === ppaId);
            }

            return api('rubro', { ppa_id: ppaId }).then(function (res) {
                if (!res.ok) {
                    box.innerHTML = '<div class="ppto-pc-empty">' + esc(res.message || 'Error') + '</div>';
                    return;
                }
                var r = res.rubro;
                selectedRubroLbl = r.codigo + ' - ' + r.descripcion;
                btnAdd.disabled = false;
                if (btnSug) {
                    btnSug.disabled = false;
                }
                var cuentas = res.cuentas || [];
                var html = '<div class="ppto-pc-rubro-title">' + esc(r.codigo) + ' - ' + esc(r.descripcion) + '</div>'
                    + '<div class="ppto-pc-rubro-meta">Estado param.: <strong>'
                    + (r.estado_param === 'completo' ? 'Parametrizado' : 'Pendiente')
                    + '</strong> / ' + cuentas.length + ' cuenta(s)</div>';

                if (!cuentas.length) {
                    html += '<div class="ppto-pc-empty">Sin cuentas. Use <em>Sugerir cuentas</em> o <em>Agregar cuentas</em>.</div>';
                    box.innerHTML = html;
                    return;
                }

                html += '<div class="exa-adq-table-wrap"><table class="table table-bordered exa-adq-table table-condensed" id="pc_tabla_cuentas">'
                    + '<thead><tr>'
                    + '<th>Codigo</th><th>Cuenta</th><th>Naturaleza</th>'
                    + '<th class="pc-col-mov text-right" style="display:none;">Mov. acum.</th>'
                    + '<th class="pc-col-mov" style="display:none;">Ultimo mov.</th>'
                    + '<th style="width:70px;"></th>'
                    + '</tr></thead><tbody>';

                cuentas.forEach(function (c) {
                    html += '<tr data-pld="' + c.pld_cod + '" data-ppc="' + c.ppc_id + '">'
                        + '<td><strong>' + esc(c.codigo) + '</strong></td>'
                        + '<td>' + esc(c.descripcion) + '</td>'
                        + '<td>' + esc(c.naturaleza || 'N/D') + '</td>'
                        + '<td class="pc-col-mov text-right" style="display:none;">-</td>'
                        + '<td class="pc-col-mov" style="display:none;">-</td>'
                        + '<td><button type="button" class="btn btn-danger btn-xs pc-btn-quitar" data-ppc="' + c.ppc_id + '">Quitar</button></td>'
                        + '</tr>';
                });
                html += '</tbody></table></div>';
                box.innerHTML = html;

                if (document.getElementById('pc_lazy_mov').checked) {
                    loadMovimientos(cuentas.map(function (c) { return c.pld_cod; }));
                }
            });
        }

        function loadMovimientos(pldCods) {
            if (!pldCods || !pldCods.length) {
                return;
            }
            var cols = document.querySelectorAll('.pc-col-mov');
            for (var i = 0; i < cols.length; i++) {
                cols[i].style.display = '';
            }
            api('movimientos', { pld_cods: pldCods.join(',') }).then(function (res) {
                var mov = res.movimientos || {};
                var rows = document.querySelectorAll('#pc_tabla_cuentas tbody tr');
                for (var j = 0; j < rows.length; j++) {
                    var tr = rows[j];
                    var id = tr.getAttribute('data-pld');
                    var m = mov[id] || mov[parseInt(id, 10)];
                    var cells = tr.querySelectorAll('.pc-col-mov');
                    if (!cells.length) {
                        continue;
                    }
                    if (m) {
                        cells[0].textContent = money(m.acumulado);
                        cells[1].textContent = m.ultimo_mov || '-';
                    }
                }
            });
        }

        function openModal(id) {
            var el = document.getElementById(id);
            if (el) {
                el.style.display = 'flex';
            }
        }

        function closeModal(id) {
            var el = document.getElementById(id);
            if (el) {
                el.style.display = 'none';
            }
        }

        function buscarCuentasModal() {
            var q = (document.getElementById('pc_busca_q').value || '').trim();
            var grupoEl = document.getElementById('pc_busca_grupo');
            var filtroEl = document.getElementById('pc_busca_filtro');
            var grupo = grupoEl ? (grupoEl.value || 'todas') : 'todas';
            var filtro = filtroEl ? (filtroEl.value || 'todas') : 'todas';
            var box = document.getElementById('pc_busca_results');
            var summary = document.getElementById('pc_busca_summary');

            if (!q && grupo === 'todas') {
                if (summary) {
                    summary.innerHTML = '';
                }
                box.innerHTML = '<div class="ppto-pc-empty">'
                    + 'Elija un <strong>grupo del plan</strong> (Activo, Gastos, etc.) o escriba parte del codigo/nombre para ver cuentas en orden.'
                    + '</div>';
                return;
            }

            box.innerHTML = '<div class="ppto-pc-empty">Buscando...</div>';
            if (summary) {
                summary.innerHTML = '';
            }

            api('buscar_cuentas', { q: q, grupo: grupo, filtro: filtro, limit: 600 }).then(function (res) {
                if (res.grupos_balance) {
                    paintGruposBalance(res.grupos_balance);
                    if (grupoEl) {
                        grupoEl.value = grupo;
                        if (grupoEl.value !== grupo) {
                            grupoEl.value = 'todas';
                        }
                    }
                }

                var rows = res.rows || [];
                if (!rows.length) {
                    box.innerHTML = '<div class="ppto-pc-empty">'
                        + esc(res.hint || 'Sin resultados. Pruebe otro grupo o texto (ej. 5.2, sueldos).')
                        + '</div>';
                    return;
                }

                var libres = 0;
                var propias = 0;
                var ocupadas = 0;
                var gruposN = 0;
                var html = '';
                var codigos = rows.map(function (r) { return String(r.codigo || ''); });
                function tieneHijosCodigo(cod) {
                    var pref = cod + '.';
                    for (var h = 0; h < codigos.length; h++) {
                        if (codigos[h].indexOf(pref) === 0) {
                            return true;
                        }
                    }
                    return false;
                }

                rows.forEach(function (c) {
                    var esGrupo = !!c.es_grupo || c.tipo === 'G';
                    var codigo = String(c.codigo || '');
                    var nivel = parseInt(c.nivel, 10);
                    if (isNaN(nivel)) {
                        nivel = (codigo.match(/\./g) || []).length;
                    }
                    var pad = Math.min(56, nivel * 14);
                    var depthAttr = ' data-cta-codigo="' + esc(codigo) + '" data-cta-depth="' + nivel + '"';

                    if (esGrupo) {
                        gruposN++;
                        var kids = tieneHijosCodigo(codigo);
                        var tog = kids
                            ? '<button type="button" class="pc-cta-toggle" data-cta-toggle="' + esc(codigo) + '" aria-expanded="true" title="Minimizar">\u25BC</button>'
                            : '<span class="pc-cta-toggle" style="visibility:hidden;" aria-hidden="true"></span>';
                        html += '<div class="pc-cta-row-grupo" style="padding-left:' + (10 + pad) + 'px;"' + depthAttr + '>'
                            + tog
                            + '<span><strong>' + esc(codigo) + '</strong> - ' + esc(c.descripcion)
                            + '</span></div>';
                        return;
                    }

                    var ppa = parseInt(c.ppa_asignado, 10) || 0;
                    var propia = ppa > 0 && ppa === selectedPpa;
                    var ocupada = ppa > 0 && ppa !== selectedPpa;
                    var bloqueada = propia || ocupada;

                    if (propia) {
                        propias++;
                    } else if (ocupada) {
                        ocupadas++;
                    } else {
                        libres++;
                    }

                    var badge = '<span class="pc-cta-badge libre">Libre</span>';
                    var cls = '';
                    var tipRubro = '';
                    if (propia) {
                        tipRubro = 'Ya parametrizada en este rubro';
                        badge = '<span class="pc-cta-badge propia" title="' + esc(tipRubro) + '">En este rubro</span>';
                        cls = 'disabled is-propia';
                    } else if (ocupada) {
                        tipRubro = (c.rubro_codigo || '') + (c.rubro_descripcion ? (' - ' + c.rubro_descripcion) : '');
                        if (!tipRubro) {
                            tipRubro = 'Asignada a otro rubro';
                        }
                        badge = '<span class="pc-cta-badge ocupada" title="' + esc(tipRubro) + '">En ' + esc(c.rubro_codigo || 'otro rubro') + '</span>';
                        cls = 'disabled is-ocupada';
                    }

                    html += '<label class="' + cls + '" style="padding-left:' + (10 + pad) + 'px;"' + depthAttr
                        + (tipRubro ? ' title="' + esc(tipRubro) + '"' : '') + '>'
                        + '<input type="checkbox" value="' + c.pld_cod + '"'
                        + (bloqueada ? ' disabled' : '') + ' />'
                        + '<span><strong>' + esc(codigo) + '</strong> - ' + esc(c.descripcion) + ' ' + badge
                        + '</span></label>';
                });

                if (summary) {
                    summary.innerHTML = '<span><strong>' + gruposN + '</strong> grupos</span>'
                        + '<span><strong>' + libres + '</strong> libres</span>'
                        + '<span><strong>' + propias + '</strong> en este rubro</span>'
                        + '<span><strong>' + ocupadas + '</strong> en otros rubros</span>';
                }
                box.innerHTML = html;
                pcBuscaApplyCollapsed(box, pcBuscaCollapsed);
            }).catch(function (err) {
                box.innerHTML = '<div class="ppto-pc-empty">'
                    + esc((err && err.message) ? err.message : 'Error al buscar.')
                    + '</div>';
            });
        }

        function pcBuscaApplyCollapsed(box, collapsedMap) {
            var nodes = box.querySelectorAll('[data-cta-codigo]');
            for (var i = 0; i < nodes.length; i++) {
                var el = nodes[i];
                var cod = el.getAttribute('data-cta-codigo') || '';
                var hide = false;
                for (var key in collapsedMap) {
                    if (!collapsedMap.hasOwnProperty(key) || !collapsedMap[key]) {
                        continue;
                    }
                    if (cod.indexOf(key + '.') === 0) {
                        hide = true;
                        break;
                    }
                }
                el.style.display = hide ? 'none' : '';
            }
            var toggles = box.querySelectorAll('.pc-cta-toggle[data-cta-toggle]');
            for (var t = 0; t < toggles.length; t++) {
                var tk = toggles[t].getAttribute('data-cta-toggle');
                var collapsed = !!collapsedMap[tk];
                toggles[t].textContent = collapsed ? '\u25B6' : '\u25BC';
                toggles[t].setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                toggles[t].title = collapsed ? 'Expandir' : 'Minimizar';
                var rowG = toggles[t].closest ? toggles[t].closest('.pc-cta-row-grupo') : null;
                if (rowG) {
                    rowG.classList.toggle('is-collapsed', collapsed);
                }
            }
        }

        function pcPendApplyCollapsed(tbody) {
            var nodes = tbody.querySelectorAll('tr[data-pend-codigo]');
            for (var i = 0; i < nodes.length; i++) {
                var el = nodes[i];
                var cod = el.getAttribute('data-pend-codigo') || '';
                var hide = false;
                for (var key in pcPendCollapsed) {
                    if (!pcPendCollapsed.hasOwnProperty(key) || !pcPendCollapsed[key]) {
                        continue;
                    }
                    if (cod.indexOf(key + '.') === 0) {
                        hide = true;
                        break;
                    }
                }
                el.classList.toggle('is-hidden', hide);
            }
            var toggles = tbody.querySelectorAll('.pc-pend-toggle[data-pend-toggle]');
            for (var t = 0; t < toggles.length; t++) {
                var tk = toggles[t].getAttribute('data-pend-toggle');
                var collapsed = !!pcPendCollapsed[tk];
                toggles[t].textContent = collapsed ? '\u25B6' : '\u25BC';
                toggles[t].setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                toggles[t].title = collapsed ? 'Expandir' : 'Minimizar';
            }
        }

        function loadPendientes() {
            var q = document.getElementById('pc_pend_q').value || '';
            var grupoEl = document.getElementById('pc_pend_grupo');
            var grupo = grupoEl ? (grupoEl.value || 'todas') : 'todas';
            var tbody = document.querySelector('#pc_tabla_pendientes tbody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Cargando...</td></tr>';
            api('cuentas_pendientes', { q: q, grupo: grupo }).then(function (res) {
                if (res.grupos_balance) {
                    paintGruposBalance(res.grupos_balance);
                    if (grupoEl) {
                        grupoEl.value = grupo;
                    }
                }
                var rows = res.rows || [];
                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin cuentas para mostrar.</td></tr>';
                    return;
                }
                var codigos = rows.map(function (r) { return String(r.codigo || ''); });
                function tieneHijos(cod) {
                    var pref = cod + '.';
                    for (var h = 0; h < codigos.length; h++) {
                        if (codigos[h].indexOf(pref) === 0) {
                            return true;
                        }
                    }
                    return false;
                }

                var libres = 0;
                var asignadas = 0;
                var gruposN = 0;
                var html = '';
                rows.forEach(function (c) {
                    var esGrupo = !!c.es_grupo || c.tipo === 'G';
                    var codigo = String(c.codigo || '');
                    var pad = parseInt(c.indent_px, 10) || 0;
                    var grupoLbl = c.grupo_descripcion || c.clasif_codigo || '';
                    var rowAttr = ' data-pend-codigo="' + esc(codigo) + '"';

                    if (esGrupo) {
                        gruposN++;
                        var kids = tieneHijos(codigo);
                        var tog = kids
                            ? '<button type="button" class="pc-pend-toggle" data-pend-toggle="' + esc(codigo) + '" aria-expanded="true" title="Minimizar">\u25BC</button>'
                            : '';
                        html += '<tr class="pc-row-grupo"' + rowAttr + '>'
                            + '<td class="text-center">' + tog + '</td>'
                            + '<td style="padding-left:' + (8 + pad) + 'px;"><strong>' + esc(codigo) + '</strong></td>'
                            + '<td>' + esc(c.descripcion) + '</td>'
                            + '<td></td>'
                            + '<td>' + esc(grupoLbl) + '</td>'
                            + '<td></td>'
                            + '</tr>';
                        return;
                    }

                    var asignada = !!c.asignada || (parseInt(c.ppa_asignado, 10) || 0) > 0;
                    var asignable = c.asignable !== false && !asignada;
                    if (asignada) {
                        asignadas++;
                    } else {
                        libres++;
                    }

                    var rubroTip = '';
                    if (asignada) {
                        rubroTip = (c.rubro_codigo || '') + (c.rubro_descripcion ? (' - ' + c.rubro_descripcion) : '');
                        if (!rubroTip) {
                            rubroTip = 'Cuenta ya asignada a un rubro';
                        }
                    }
                    var badge = asignada
                        ? '<span class="pc-pend-badge" title="' + esc(rubroTip) + '">En ' + esc(c.rubro_codigo || 'rubro') + '</span>'
                        : '';
                    var accion = asignable
                        ? '<button type="button" class="btn btn-default btn-xs pc-btn-asignar-desde-pend" data-pld="' + c.pld_cod + '">Asignar...</button>'
                        : '<span class="text-muted" style="font-size:11px;" title="' + esc(rubroTip) + '">Bloqueada</span>';

                    html += '<tr class="' + (asignada ? 'pc-row-asignada' : '') + '"' + rowAttr
                        + (asignada ? ' title="' + esc(rubroTip) + '"' : '') + '>'
                        + '<td></td>'
                        + '<td style="padding-left:' + (8 + pad) + 'px;"><strong>' + esc(codigo) + '</strong></td>'
                        + '<td>' + esc(c.descripcion) + badge + '</td>'
                        + '<td></td>'
                        + '<td>' + esc(grupoLbl) + '</td>'
                        + '<td>' + accion + '</td>'
                        + '</tr>';
                });
                tbody.innerHTML = html;
                pcPendApplyCollapsed(tbody);
                var foot = document.getElementById('pc_pend_footer');
                if (foot) {
                    foot.innerHTML = 'Mostrando <strong>' + rows.length + '</strong> '
                        + '(<strong>' + gruposN + '</strong> grupos, <strong>' + libres + '</strong> libres, '
                        + '<strong>' + asignadas + '</strong> asignadas bloqueadas). '
                        + 'Clic en el grupo para expandir/contraer. Solo detalle libre se puede asignar.';
                }
            }).catch(function (err) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">'
                    + esc((err && err.message) ? err.message : 'Error al cargar.') + '</td></tr>';
            });
        }

        function runAuditoria() {
            var box = document.getElementById('pc_audit_resultado');
            box.innerHTML = '<div class="ppto-pc-empty">Auditando...</div>';
            var btns = document.querySelectorAll('.ppto-pc-subtabs .btn');
            for (var i = 0; i < btns.length; i++) {
                var on = btns[i].getAttribute('data-pc-pane') === 'auditoria';
                btns[i].classList.toggle('active', on);
                btns[i].classList.toggle('btn-primary', on);
                btns[i].classList.toggle('btn-default', !on);
            }
            var panes = document.querySelectorAll('.ppto-pc-pane');
            for (var p = 0; p < panes.length; p++) {
                panes[p].style.display = 'none';
            }
            document.getElementById('pc_pane_auditoria').style.display = '';

            api('auditar').then(function (res) {
                if (!res.ok) {
                    box.innerHTML = '<div class="alert alert-danger">' + esc(res.message || 'Error') + '</div>';
                    return;
                }
                var hallazgos = res.hallazgos || [];
                var total = 0;
                hallazgos.forEach(function (h) { total += parseInt(h.total, 10) || 0; });
                var html = '<div class="alert alert-' + (total ? 'warning' : 'success') + '" style="font-size:12px;">'
                    + (total ? ('Auditoria: ' + total + ' hallazgo(s) en ' + hallazgos.length + ' categoria(s).') : 'Sin inconsistencias relevantes.')
                    + '</div>';
                hallazgos.forEach(function (h) {
                    html += '<div style="margin-bottom:12px;">'
                        + '<strong style="font-size:12px;">' + esc(h.titulo || h.codigo) + '</strong>'
                        + ' <span class="text-muted" style="font-size:11px;">(' + (h.total || 0) + ' / ' + esc(h.severidad || '') + ')</span>';
                    if (h.items && h.items.length) {
                        html += '<ul>';
                        h.items.slice(0, 25).forEach(function (it) {
                            html += '<li>' + esc(typeof it === 'string' ? it : JSON.stringify(it)) + '</li>';
                        });
                        html += '</ul>';
                    } else {
                        html += '<p style="font-size:11px;color:#718096;margin:4px 0 0;">Sin items.</p>';
                    }
                    html += '</div>';
                });
                box.innerHTML = html;
            });
        }

        var subBtns = document.querySelectorAll('.ppto-pc-subtabs .btn');
        for (var s = 0; s < subBtns.length; s++) {
            subBtns[s].addEventListener('click', function () {
                var pane = this.getAttribute('data-pc-pane');
                var all = document.querySelectorAll('.ppto-pc-subtabs .btn');
                for (var i = 0; i < all.length; i++) {
                    var on = all[i] === this;
                    all[i].classList.toggle('active', on);
                    all[i].classList.toggle('btn-primary', on);
                    all[i].classList.toggle('btn-default', !on);
                }
                var panes = document.querySelectorAll('.ppto-pc-pane');
                for (var p = 0; p < panes.length; p++) {
                    panes[p].style.display = 'none';
                }
                var target = document.getElementById('pc_pane_' + pane);
                if (target) {
                    target.style.display = '';
                }
                if (pane === 'pendientes') {
                    loadPendientes();
                }
                if (pane === 'mapa') {
                    loadMapa();
                }
                if (pane === 'auditoria') {
                    runAuditoria();
                }
            });
        }

        document.getElementById('pc_filtro_arbol').addEventListener('change', loadArbol);
        document.getElementById('pc_btn_reload_arbol').addEventListener('click', loadArbol);

        document.getElementById('pc_arbol').addEventListener('click', function (ev) {
            var toggle = ev.target.closest ? ev.target.closest('.ppto-pc-tree-toggle[data-toggle-ppa]') : null;
            var row = ev.target.closest ? ev.target.closest('.ppto-pc-tree-row') : null;
            if (!row) {
                return;
            }
            if (row.getAttribute('data-clase') === 'G') {
                var gid = (toggle && toggle.getAttribute('data-toggle-ppa'))
                    ? toggle.getAttribute('data-toggle-ppa')
                    : row.getAttribute('data-ppa');
                var btn = row.querySelector('.ppto-pc-tree-toggle[data-toggle-ppa]');
                if (gid && btn) {
                    pcArbolCollapsed[gid] = !pcArbolCollapsed[gid];
                    pcArbolApplyCollapsed(document.getElementById('pc_arbol'));
                }
                return;
            }
            var id = parseInt(row.getAttribute('data-ppa'), 10);
            if (id > 0) {
                loadDetalle(id);
            }
        });

        document.getElementById('pc_detalle').addEventListener('click', function (ev) {
            var btn = ev.target.closest ? ev.target.closest('.pc-btn-quitar') : null;
            if (!btn) {
                return;
            }
            var ppc = parseInt(btn.getAttribute('data-ppc'), 10);
            if (!ppc || !window.confirm('Quitar esta cuenta del rubro?')) {
                return;
            }
            api('quitar', { ppc_id: ppc }, 'POST').then(function (res) {
                showMsg(!!res.ok, res.message || (res.ok ? 'Cuenta quitada.' : 'No se pudo quitar.'));
                if (res.kpis) {
                    paintKpis(res.kpis);
                }
                if (res.ok) {
                    loadArbol();
                    if (selectedPpa) {
                        loadDetalle(selectedPpa);
                    }
                }
            });
        });

        document.getElementById('pc_lazy_mov').addEventListener('change', function () {
            if (!this.checked) {
                var cols = document.querySelectorAll('.pc-col-mov');
                for (var i = 0; i < cols.length; i++) {
                    cols[i].style.display = 'none';
                }
                return;
            }
            var ids = [];
            var rows = document.querySelectorAll('#pc_tabla_cuentas tbody tr');
            for (var j = 0; j < rows.length; j++) {
                ids.push(rows[j].getAttribute('data-pld'));
            }
            if (ids.length) {
                loadMovimientos(ids);
            }
        });

        document.getElementById('pc_btn_agregar').addEventListener('click', function () {
            if (!selectedPpa) {
                return;
            }
            document.getElementById('pc_modal_rubro_lbl').textContent = selectedRubroLbl;
            document.getElementById('pc_busca_q').value = '';
            var filtroEl = document.getElementById('pc_busca_filtro');
            if (filtroEl) {
                filtroEl.value = 'todas';
            }
            var grupoEl = document.getElementById('pc_busca_grupo');
            if (grupoEl) {
                grupoEl.value = 'todas';
            }
            var summary = document.getElementById('pc_busca_summary');
            if (summary) {
                summary.innerHTML = '';
            }
            document.getElementById('pc_busca_results').innerHTML = '<div class="ppto-pc-empty">'
                + 'Elija un <strong>grupo del plan</strong> o escriba codigo/nombre para guiarse.</div>';
            openModal('modal_pc_agregar');
            // Carga grupos si aun no estan, y deja listo el filtro "todas" (ocupadas inactivas).
            api('buscar_cuentas', { q: '', grupo: 'todas', filtro: 'todas', limit: 1 }).then(function (res) {
                if (res.grupos_balance) {
                    paintGruposBalance(res.grupos_balance);
                }
            });
        });

        document.getElementById('pc_modal_agregar_cerrar').addEventListener('click', function () { closeModal('modal_pc_agregar'); });
        document.getElementById('pc_modal_agregar_cancel').addEventListener('click', function () { closeModal('modal_pc_agregar'); });
        document.getElementById('pc_busca_btn').addEventListener('click', buscarCuentasModal);
        document.getElementById('pc_busca_q').addEventListener('keyup', function (ev) {
            clearTimeout(debounceTimer);
            if (ev.key === 'Enter') {
                buscarCuentasModal();
                return;
            }
            debounceTimer = setTimeout(buscarCuentasModal, 400);
        });
        var pcBuscaResults = document.getElementById('pc_busca_results');
        if (pcBuscaResults) {
            pcBuscaResults.addEventListener('click', function (ev) {
                var tog = ev.target.closest ? ev.target.closest('.pc-cta-toggle[data-cta-toggle]') : null;
                var rowG = ev.target.closest ? ev.target.closest('.pc-cta-row-grupo') : null;
                if (!tog && rowG) {
                    tog = rowG.querySelector('.pc-cta-toggle[data-cta-toggle]');
                }
                if (!tog) {
                    return;
                }
                ev.preventDefault();
                var key = tog.getAttribute('data-cta-toggle');
                if (!key) {
                    return;
                }
                pcBuscaCollapsed[key] = !pcBuscaCollapsed[key];
                pcBuscaApplyCollapsed(pcBuscaResults, pcBuscaCollapsed);
            });
        }
        var pcBuscaGrupo = document.getElementById('pc_busca_grupo');
        if (pcBuscaGrupo) {
            pcBuscaGrupo.addEventListener('change', function () {
                pcBuscaCollapsed = {};
                buscarCuentasModal();
            });
        }
        var pcBuscaFiltro = document.getElementById('pc_busca_filtro');
        if (pcBuscaFiltro) {
            pcBuscaFiltro.addEventListener('change', function () {
                pcBuscaCollapsed = {};
                buscarCuentasModal();
            });
        }
        var pcBuscaLimpiar = document.getElementById('pc_busca_limpiar');
        if (pcBuscaLimpiar) {
            pcBuscaLimpiar.addEventListener('click', function () {
                document.getElementById('pc_busca_q').value = '';
                if (pcBuscaGrupo) {
                    pcBuscaGrupo.value = 'todas';
                }
                if (pcBuscaFiltro) {
                    pcBuscaFiltro.value = 'todas';
                }
                buscarCuentasModal();
            });
        }

        document.getElementById('pc_modal_agregar_ok').addEventListener('click', function () {
            if (!selectedPpa) {
                showMsg(false, 'Seleccione un rubro detalle.');
                return;
            }
            var ids = [];
            var cbs = document.querySelectorAll('#pc_busca_results input[type=checkbox]:checked');
            for (var i = 0; i < cbs.length; i++) {
                ids.push(cbs[i].value);
            }
            if (!ids.length) {
                showMsg(false, 'Seleccione al menos una cuenta.');
                return;
            }
            api('asignar_multi', { ppa_id: selectedPpa, pld_cods: ids }, 'POST').then(function (res) {
                showMsg(!!res.ok, res.message || '');
                if (res.kpis) {
                    paintKpis(res.kpis);
                }
                if (res.ok) {
                    closeModal('modal_pc_agregar');
                    loadArbol();
                    loadDetalle(selectedPpa);
                }
            });
        });

        document.getElementById('pc_btn_copiar').addEventListener('click', function () {
            openModal('modal_pc_copiar');
        });
        document.getElementById('pc_modal_copiar_cerrar').addEventListener('click', function () { closeModal('modal_pc_copiar'); });
        document.getElementById('pc_modal_copiar_cancel').addEventListener('click', function () { closeModal('modal_pc_copiar'); });
        document.getElementById('pc_modal_copiar_ok').addEventListener('click', function () {
            var o = parseInt(document.getElementById('pc_copiar_origen').value, 10);
            var d = parseInt(document.getElementById('pc_copiar_destino').value, 10);
            if (!o || !d) {
                showMsg(false, 'Indique anio origen y destino.');
                return;
            }
            if (o === d) {
                showMsg(false, 'Origen y destino deben ser anios distintos.');
                return;
            }
            if (!window.confirm('Copiar parametrizacion de ' + o + ' a ' + d + '?')) {
                return;
            }
            var over = document.getElementById('pc_copiar_overwrite').checked ? 1 : 0;
            api('copiar', { anio_origen: o, anio_destino: d, sobreescribir: over }, 'POST').then(function (res) {
                showMsg(!!res.ok, res.message || '');
                if (res.kpis) {
                    paintKpis(res.kpis);
                }
                if (res.ok) {
                    closeModal('modal_pc_copiar');
                    loadArbol();
                    if (selectedPpa) {
                        loadDetalle(selectedPpa);
                    }
                }
            });
        });

        document.getElementById('pc_btn_auditar').addEventListener('click', runAuditoria);
        document.getElementById('pc_btn_pend_buscar').addEventListener('click', function () {
            pcPendCollapsed = {};
            loadPendientes();
        });
        var pcPendGrupoSel = document.getElementById('pc_pend_grupo');
        if (pcPendGrupoSel) {
            pcPendGrupoSel.addEventListener('change', function () {
                pcPendCollapsed = {};
                loadPendientes();
            });
        }
        var pcPendQ = document.getElementById('pc_pend_q');
        if (pcPendQ) {
            pcPendQ.addEventListener('keyup', function (ev) {
                if (ev.key === 'Enter') {
                    pcPendCollapsed = {};
                    loadPendientes();
                }
            });
        }

        function loadMapa() {
            var filtro = document.getElementById('pc_mapa_filtro').value || 'todos';
            var conMov = document.getElementById('pc_mapa_mov').checked ? 1 : 0;
            var tbody = document.querySelector('#pc_tabla_mapa tbody');
            var tot = document.getElementById('pc_mapa_totales');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Cargando mapa...</td></tr>';
            api('mapa', { filtro: filtro, con_movimientos: conMov }).then(function (res) {
                if (!res.ok) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">' + esc(res.message || 'Error') + '</td></tr>';
                    return;
                }
                var t = res.totales || {};
                if (tot) {
                    tot.textContent = 'Rubros: ' + (t.rubros || 0)
                        + ' | Parametrizados: ' + (t.parametrizados || 0)
                        + ' | Pendientes: ' + (t.pendientes || 0)
                        + ' | Cuentas: ' + (t.cuentas || 0);
                }
                var rows = res.rows || [];
                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin datos para el filtro.</td></tr>';
                    return;
                }
                var html = '';
                rows.forEach(function (r) {
                    var estado = r.estado_param === 'completo' ? 'Parametrizado' : 'Pendiente';
                    var ctas = r.cuentas || [];
                    if (!ctas.length) {
                        html += '<tr>'
                            + '<td><strong>' + esc(r.codigo) + '</strong><br/>' + esc(r.descripcion) + '</td>'
                            + '<td>' + estado + '</td>'
                            + '<td class="text-muted">Sin cuentas</td>'
                            + '<td class="text-right">-</td><td>-</td></tr>';
                        return;
                    }
                    ctas.forEach(function (c, idx) {
                        html += '<tr>'
                            + '<td>' + (idx === 0 ? ('<strong>' + esc(r.codigo) + '</strong><br/>' + esc(r.descripcion)) : '') + '</td>'
                            + '<td>' + (idx === 0 ? estado : '') + '</td>'
                            + '<td><strong>' + esc(c.codigo) + '</strong> - ' + esc(c.descripcion) + '</td>'
                            + '<td class="text-right">' + (c.acumulado == null ? '-' : money(c.acumulado)) + '</td>'
                            + '<td>' + (c.ultimo_mov || '-') + '</td>'
                            + '</tr>';
                    });
                });
                tbody.innerHTML = html;
            }).catch(function (err) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">'
                    + esc((err && err.message) ? err.message : 'Error') + '</td></tr>';
            });
        }

        function exportMapaExcel() {
            var filtro = document.getElementById('pc_mapa_filtro').value || 'todos';
            var conMov = document.getElementById('pc_mapa_mov').checked ? 1 : 0;
            var url = 'ppto_param_contable_export.php?emp_id=' + encodeURIComponent(EMP)
                + '&anio=' + encodeURIComponent(ANIO)
                + '&filtro=' + encodeURIComponent(filtro)
                + '&con_movimientos=' + encodeURIComponent(conMov);
            window.open(url, '_blank');
        }

        function showPane(pane) {
            var all = document.querySelectorAll('.ppto-pc-subtabs .btn');
            for (var i = 0; i < all.length; i++) {
                var on = all[i].getAttribute('data-pc-pane') === pane;
                all[i].classList.toggle('active', on);
                all[i].classList.toggle('btn-primary', on);
                all[i].classList.toggle('btn-default', !on);
            }
            var panes = document.querySelectorAll('.ppto-pc-pane');
            for (var p = 0; p < panes.length; p++) {
                panes[p].style.display = 'none';
            }
            var target = document.getElementById('pc_pane_' + pane);
            if (target) {
                target.style.display = '';
            }
        }

        function setEscActive(which) {
            var cards = document.querySelectorAll('#pc_bloque_ejecutado .esc-btn');
            for (var i = 0; i < cards.length; i++) {
                cards[i].classList.toggle('active', cards[i].getAttribute('data-esc') === which);
            }
        }

        function mesSyncRango() {
            return { desde: 1, hasta: MES };
        }

        function nombreMes(n) {
            var nombres = [
                '', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];
            var m = parseInt(n, 10) || 0;
            return nombres[m] || ('Mes ' + n);
        }

        function paintEjecutadoKpis(res) {
            var mapeosEl = document.getElementById('pc_ejec_kpi_mapeos');
            var montoEl = document.getElementById('pc_ejec_kpi_monto');
            var lineasEl = document.getElementById('pc_ejec_kpi_lineas');
            var rangoEl = document.getElementById('pc_ejec_kpi_rango');
            var escPrev = document.getElementById('pc_esc_preview_val');
            var escSync = document.getElementById('pc_esc_sync_val');
            if (!mapeosEl) {
                return;
            }
            if (!res || !res.ok) {
                montoEl.textContent = '-';
                lineasEl.textContent = '-';
                if (rangoEl) {
                    rangoEl.textContent = (res && res.message) ? String(res.message).slice(0, 42) : 'Sin datos';
                }
                if (escPrev) {
                    escPrev.textContent = '-';
                }
                return;
            }
            var t = res.totales || {};
            if (res.mapeos != null) {
                mapeosEl.textContent = res.mapeos;
            }
            montoEl.textContent = '$' + money(t.monto);
            lineasEl.textContent = t.lineas != null ? t.lineas : '-';
            if (rangoEl) {
                rangoEl.textContent = nombreMes(res.mes_desde || 1) + ' a '
                    + nombreMes(res.mes_hasta || MES) + ' / ' + (res.anio || ANIO);
            }
            if (escPrev) {
                escPrev.textContent = '$' + money(t.monto);
            }
            if (escSync) {
                escSync.textContent = (t.lineas || 0) + ' rubros';
            }
        }

        function paintEjecutadoPreview(res) {
            var box = document.getElementById('pc_ejec_resultado');
            paintEjecutadoKpis(res);
            if (!box) {
                return;
            }
            if (!res || !res.ok) {
                box.innerHTML = '<div class="exa-ppto-cuadro-empty">'
                    + esc((res && res.message) || 'Error en vista previa')
                    + '</div>';
                return;
            }
            setEscActive('preview');

            var desdeNom = nombreMes(res.mes_desde || 1);
            var hastaNom = nombreMes(res.mes_hasta || MES);
            var anioLbl = esc(res.anio || ANIO);
            var mapeos = res.mapeos || 0;
            var lineas = (res.totales && res.totales.lineas) || 0;
            var monto = (res.totales && res.totales.monto) || 0;
            var mesesConMonto = 0;
            (res.meses || []).forEach(function (m) {
                if ((m.monto || 0) > 0) {
                    mesesConMonto++;
                }
            });

            var html = '<h6><i class="bi bi-pie-chart"></i> Resumen del ejecutado contable</h6>'
                + '<p class="help" style="margin:0 0 10px;">'
                + 'Periodo: <strong>' + esc(desdeNom) + '</strong> a <strong>' + esc(hastaNom)
                + '</strong> de <strong>' + anioLbl + '</strong>. '
                + 'Montos tomados de los asientos de las cuentas ya mapeadas a rubros.</p>'
                + '<table class="esc-resumen-table">'
                + '<thead><tr><th>Que se midio</th><th>Resultado</th></tr></thead><tbody>';
            html += '<tr><td>Cuentas contables vinculadas a un rubro</td><td><strong>'
                + esc(mapeos) + '</strong></td></tr>';
            html += '<tr><td>Rubros con ejecutado en el periodo</td><td><strong>'
                + esc(lineas) + '</strong></td></tr>';
            html += '<tr><td>Meses con movimiento</td><td><strong>'
                + esc(mesesConMonto) + '</strong></td></tr>';
            html += '<tr style="background:#f7fafc;"><td>Total a cargar en el presupuesto (ledger)</td>'
                + '<td class="eco-pos">$' + money(monto) + '</td></tr>';
            html += '</tbody></table>';

            html += '<h6 style="margin-top:14px;">Detalle por mes</h6>'
                + '<table class="esc-resumen-table">'
                + '<thead><tr><th>Mes</th><th>Rubros con monto</th><th>Total del mes</th></tr></thead><tbody>';
            (res.meses || []).forEach(function (m) {
                var tiene = (m.monto || 0) > 0;
                html += '<tr' + (tiene ? '' : ' style="color:#a0aec0;"') + '>'
                    + '<td><strong>' + esc(nombreMes(m.mes)) + '</strong></td>'
                    + '<td>' + esc(m.lineas) + '</td>'
                    + '<td>' + (tiene ? ('$' + money(m.monto)) : 'Sin movimiento') + '</td>'
                    + '</tr>';
            });
            html += '</tbody></table>';

            var top = [];
            (res.meses || []).forEach(function (m) {
                (m.detalle || []).forEach(function (d) {
                    top.push({ mes: m.mes, codigo: d.codigo, descripcion: d.descripcion, monto: d.monto });
                });
            });
            top.sort(function (a, b) { return (b.monto || 0) - (a.monto || 0); });
            top = top.slice(0, 8);
            if (top.length) {
                html += '<h6 style="margin-top:14px;">Rubros con mayor ejecutado</h6>'
                    + '<table class="esc-resumen-table">'
                    + '<thead><tr><th>Mes</th><th>Rubro presupuestario</th><th>Monto</th></tr></thead><tbody>';
                top.forEach(function (d) {
                    html += '<tr>'
                        + '<td>' + esc(nombreMes(d.mes)) + '</td>'
                        + '<td><strong>' + esc(d.codigo) + '</strong> - ' + esc(d.descripcion) + '</td>'
                        + '<td class="eco-pos">$' + money(d.monto) + '</td>'
                        + '</tr>';
                });
                html += '</tbody></table>';
            }
            box.innerHTML = html;
        }

        function loadEjecutadoPreview() {
            var rango = mesSyncRango();
            var desde = rango.desde;
            var hasta = rango.hasta;
            var box = document.getElementById('pc_ejec_resultado');
            setEscActive('preview');
            box.innerHTML = '<div class="exa-ppto-cuadro-empty">Calculando...</div>';
            return api('ejecutado_preview', { mes_desde: desde, mes_hasta: hasta }).then(function (res) {
                paintEjecutadoPreview(res);
                return res;
            }).catch(function (err) {
                box.innerHTML = '<div class="exa-ppto-cuadro-empty">'
                    + esc((err && err.message) || 'Error') + '</div>';
            });
        }

        function syncEjecutado() {
            var rango = mesSyncRango();
            var desde = rango.desde;
            var hasta = rango.hasta;
            if (desde > hasta) {
                showMsg(false, 'Mes desde no puede ser mayor que mes hasta.');
                return;
            }
            if (!window.confirm('Sincronizar ejecutado ' + ANIO + ' (meses ' + desde + '-' + hasta
                + ')? Se reemplazaran solo filas mayor_contable del rango.')) {
                return;
            }
            var box = document.getElementById('pc_ejec_resultado');
            var btn = document.getElementById('pc_ejec_sync');
            if (btn) {
                btn.disabled = true;
            }
            setEscActive('sync');
            box.innerHTML = '<div class="exa-ppto-cuadro-empty">Sincronizando...</div>';
            api('ejecutado_sync', { mes_desde: desde, mes_hasta: hasta }, 'POST').then(function (res) {
                if (res.ok) {
                    var syncVal = document.getElementById('pc_esc_sync_val');
                    if (syncVal) {
                        syncVal.textContent = (res.insertados || 0) + ' OK';
                    }
                    showMsg(true, res.message || 'Sincronizado.');
                    return loadEjecutadoPreview().then(function () {
                        var box2 = document.getElementById('pc_ejec_resultado');
                        if (box2 && res.message) {
                            box2.insertAdjacentHTML('afterbegin',
                                '<div class="help" style="margin-bottom:10px;color:#276749;font-weight:600;">'
                                + '<i class="bi bi-check2-circle"></i> ' + esc(res.message)
                                + '</div>');
                        }
                        setEscActive('sync');
                    });
                }
                paintEjecutadoPreview(res);
                showMsg(false, res.message || 'No se sincronizo.');
            }).catch(function (err) {
                box.innerHTML = '<div class="exa-ppto-cuadro-empty">'
                    + esc((err && err.message) || 'Error') + '</div>';
                showMsg(false, (err && err.message) || 'Error');
            }).then(function () {
                if (btn) {
                    btn.disabled = false;
                }
            });
        }
        var escCardPrev = document.getElementById('pc_esc_preview');
        if (escCardPrev) {
            escCardPrev.addEventListener('click', function () {
                setEscActive('preview');
                loadEjecutadoPreview();
            });
        }
        var escCardSync = document.getElementById('pc_esc_sync');
        if (escCardSync) {
            escCardSync.addEventListener('click', function () {
                setEscActive('sync');
            });
        }
        var escCardParam = document.getElementById('pc_esc_param');
        if (escCardParam) {
            escCardParam.addEventListener('click', function () {
                setEscActive('param');
            });
        }
        var btnEjecPrev = document.getElementById('pc_ejec_preview');
        if (btnEjecPrev) {
            btnEjecPrev.addEventListener('click', loadEjecutadoPreview);
        }
        var btnEjecSync = document.getElementById('pc_ejec_sync');
        if (btnEjecSync) {
            btnEjecSync.addEventListener('click', syncEjecutado);
        }
        var btnMapa = document.getElementById('pc_btn_mapa');
        if (btnMapa) {
            btnMapa.addEventListener('click', function () {
                showPane('mapa');
                loadMapa();
            });
        }
        var btnMapaReload = document.getElementById('pc_btn_mapa_reload');
        if (btnMapaReload) {
            btnMapaReload.addEventListener('click', loadMapa);
        }
        var btnMapaExcel = document.getElementById('pc_btn_mapa_excel');
        if (btnMapaExcel) {
            btnMapaExcel.addEventListener('click', exportMapaExcel);
        }

        function abrirSugerir() {
            if (!selectedPpa) {
                showMsg(false, 'Seleccione un rubro detalle.');
                return;
            }
            document.getElementById('pc_sugerir_rubro_lbl').textContent = selectedRubroLbl;
            var box = document.getElementById('pc_sugerir_results');
            box.innerHTML = '<div class="ppto-pc-empty">Analizando coincidencias...</div>';
            openModal('modal_pc_sugerir');
            api('sugerir', { ppa_id: selectedPpa, top: 15 }).then(function (res) {
                if (!res.ok) {
                    box.innerHTML = '<div class="ppto-pc-empty">' + esc(res.message || 'Error') + '</div>';
                    return;
                }
                var rows = res.sugerencias || [];
                if (!rows.length) {
                    box.innerHTML = '<div class="ppto-pc-empty">' + esc(res.message || 'Sin sugerencias.') + '</div>';
                    return;
                }
                var html = '';
                rows.forEach(function (c) {
                    html += '<label>'
                        + '<input type="checkbox" value="' + c.pld_cod + '" />'
                        + '<span><strong>' + esc(c.codigo) + '</strong> - ' + esc(c.descripcion)
                        + '<br/><span style="color:#718096;font-size:11px;">Score ' + esc(c.score)
                        + (c.razones && c.razones.length ? ' / ' + esc(c.razones.join(', ')) : '')
                        + '</span></span></label>';
                });
                box.innerHTML = html;
            }).catch(function (err) {
                box.innerHTML = '<div class="ppto-pc-empty">' + esc((err && err.message) ? err.message : 'Error') + '</div>';
            });
        }

        var btnSugerir = document.getElementById('pc_btn_sugerir');
        if (btnSugerir) {
            btnSugerir.addEventListener('click', abrirSugerir);
        }
        var btnSugCerrar = document.getElementById('pc_modal_sugerir_cerrar');
        if (btnSugCerrar) {
            btnSugCerrar.addEventListener('click', function () { closeModal('modal_pc_sugerir'); });
        }
        var btnSugCancel = document.getElementById('pc_modal_sugerir_cancel');
        if (btnSugCancel) {
            btnSugCancel.addEventListener('click', function () { closeModal('modal_pc_sugerir'); });
        }
        var btnSugOk = document.getElementById('pc_modal_sugerir_ok');
        if (btnSugOk) {
            btnSugOk.addEventListener('click', function () {
                if (!selectedPpa) {
                    return;
                }
                var ids = [];
                var cbs = document.querySelectorAll('#pc_sugerir_results input[type=checkbox]:checked');
                for (var i = 0; i < cbs.length; i++) {
                    ids.push(cbs[i].value);
                }
                if (!ids.length) {
                    showMsg(false, 'Seleccione al menos una sugerencia.');
                    return;
                }
                api('asignar_multi', { ppa_id: selectedPpa, pld_cods: ids }, 'POST').then(function (res) {
                    showMsg(!!res.ok, res.message || '');
                    if (res.kpis) {
                        paintKpis(res.kpis);
                    }
                    if (res.ok) {
                        closeModal('modal_pc_sugerir');
                        loadArbol();
                        loadDetalle(selectedPpa);
                    }
                });
            });
        }

        document.querySelector('#pc_tabla_pendientes').addEventListener('click', function (ev) {
            var tog = ev.target.closest ? ev.target.closest('.pc-pend-toggle[data-pend-toggle]') : null;
            var rowG = ev.target.closest ? ev.target.closest('tr.pc-row-grupo') : null;
            if (!tog && rowG) {
                tog = rowG.querySelector('.pc-pend-toggle[data-pend-toggle]');
            }
            if (tog) {
                ev.preventDefault();
                var key = tog.getAttribute('data-pend-toggle');
                if (key) {
                    pcPendCollapsed[key] = !pcPendCollapsed[key];
                    pcPendApplyCollapsed(document.querySelector('#pc_tabla_pendientes tbody'));
                }
                return;
            }

            var btn = ev.target.closest ? ev.target.closest('.pc-btn-asignar-desde-pend') : null;
            if (!btn) {
                return;
            }
            var pld = parseInt(btn.getAttribute('data-pld'), 10);
            if (!pld) {
                return;
            }
            var tr = btn.closest ? btn.closest('tr') : null;
            var cod = tr ? (tr.getAttribute('data-pend-codigo') || '') : '';
            var descCell = tr ? tr.querySelector('td:nth-child(3)') : null;
            var descTxt = '';
            if (descCell) {
                descTxt = (descCell.childNodes[0] && descCell.childNodes[0].textContent)
                    ? descCell.childNodes[0].textContent.trim()
                    : (descCell.textContent || '').trim();
            }
            abrirAsignarDesdePendiente(pld, cod, descTxt);
        });

        var pendingAssignPld = 0;
        var pendingAssignRubros = [];
        var pendingAssignSelected = 0;
        var pendingAssignSelectedLbl = '';
        var pcAsignarPendCollapsed = {};

        function pcAsignarPendTieneHijos(ppaId) {
            for (var i = 0; i < pendingAssignRubros.length; i++) {
                if (parseInt(pendingAssignRubros[i].padre_id, 10) === ppaId) {
                    return true;
                }
            }
            return false;
        }

        function pcAsignarPendUpdateSelLbl() {
            var lbl = document.getElementById('pc_asignar_pend_sel_lbl');
            if (!lbl) {
                return;
            }
            lbl.textContent = pendingAssignSelected
                ? ('Seleccionado: ' + pendingAssignSelectedLbl)
                : 'Ningun rubro detalle seleccionado.';
        }

        function paintAsignarPendRubros(filtro) {
            var box = document.getElementById('pc_asignar_pend_tree');
            if (!box) {
                return;
            }
            filtro = (filtro || '').toLowerCase().trim();
            var matchIds = {};
            var keepIds = {};

            if (filtro) {
                pendingAssignRubros.forEach(function (r) {
                    var lbl = ((r.codigo || '') + ' ' + (r.descripcion || '')).toLowerCase();
                    if (lbl.indexOf(filtro) !== -1) {
                        matchIds[r.ppa_id] = true;
                        keepIds[r.ppa_id] = true;
                        var walk = r.padre_id;
                        var guard = 0;
                        while (walk > 0 && guard < 40) {
                            keepIds[walk] = true;
                            var parent = null;
                            for (var p = 0; p < pendingAssignRubros.length; p++) {
                                if (pendingAssignRubros[p].ppa_id === walk) {
                                    parent = pendingAssignRubros[p];
                                    break;
                                }
                            }
                            walk = parent ? parent.padre_id : 0;
                            guard++;
                        }
                    }
                });
            }

            var html = '';
            var nDet = 0;
            pendingAssignRubros.forEach(function (r) {
                if (filtro && !keepIds[r.ppa_id]) {
                    return;
                }
                var isG = r.clase === 'G';
                var pad = parseInt(r.indent_px, 10) || 0;
                var hasKids = isG && pcAsignarPendTieneHijos(r.ppa_id);
                if (isG) {
                    var tog = hasKids
                        ? '<button type="button" class="pc-apr-toggle" data-apr-toggle="' + r.ppa_id + '" aria-expanded="true" title="Minimizar">\u25BC</button>'
                        : '<span class="pc-apr-toggle" style="visibility:hidden;" aria-hidden="true"></span>';
                    html += '<div class="pc-apr-row is-grupo" data-apr-ppa="' + r.ppa_id + '" data-apr-padre="' + r.padre_id + '"'
                        + ' style="padding-left:' + (8 + pad) + 'px;">'
                        + tog
                        + '<span><strong>' + esc(r.codigo) + '</strong> ' + esc(r.descripcion) + '</span></div>';
                    return;
                }
                nDet++;
                var checked = pendingAssignSelected === r.ppa_id
                    || (!pendingAssignSelected && selectedPpa === r.ppa_id);
                if (checked && !pendingAssignSelected) {
                    pendingAssignSelected = r.ppa_id;
                    pendingAssignSelectedLbl = r.codigo + ' - ' + r.descripcion;
                }
                var meta = r.cuentas > 0
                    ? ('<span class="pc-apr-meta"> � ' + r.cuentas + ' cuenta(s)</span>')
                    : '<span class="pc-apr-meta"> � sin cuentas</span>';
                html += '<label class="pc-apr-row is-detalle' + (checked ? ' is-selected' : '') + '"'
                    + ' data-apr-ppa="' + r.ppa_id + '" data-apr-padre="' + r.padre_id + '"'
                    + ' style="padding-left:' + (8 + pad) + 'px;">'
                    + '<span class="pc-apr-toggle" style="visibility:hidden;" aria-hidden="true"></span>'
                    + '<input type="radio" name="pc_apr_rubro" value="' + r.ppa_id + '"'
                    + (checked ? ' checked' : '') + ' />'
                    + '<span><strong>' + esc(r.codigo) + '</strong> ' + esc(r.descripcion) + meta + '</span></label>';
            });

            if (!html) {
                box.innerHTML = '<div class="ppto-pc-empty">Sin rubros para el filtro.</div>';
                pcAsignarPendUpdateSelLbl();
                return;
            }
            box.innerHTML = html;
            if (!filtro) {
                pcAsignarPendApplyCollapsed(box);
            } else {
                // Con filtro: expandir todo lo que queda visible.
                var toggles = box.querySelectorAll('.pc-apr-toggle[data-apr-toggle]');
                for (var t = 0; t < toggles.length; t++) {
                    toggles[t].textContent = '\u25BC';
                    toggles[t].setAttribute('aria-expanded', 'true');
                }
            }
            pcAsignarPendUpdateSelLbl();
            if (filtro && nDet === 0) {
                showMsgIn('pc_asignar_pend_alert', false, 'No hay rubros detalle que coincidan. Pruebe otro texto.', false);
            } else {
                showMsgIn('pc_asignar_pend_alert', true, '', false);
            }
        }

        function pcAsignarPendApplyCollapsed(box) {
            var rows = box.querySelectorAll('[data-apr-ppa]');
            var byId = {};
            for (var i = 0; i < rows.length; i++) {
                byId[rows[i].getAttribute('data-apr-ppa')] = rows[i];
            }
            for (var j = 0; j < rows.length; j++) {
                var el = rows[j];
                var padre = el.getAttribute('data-apr-padre') || '0';
                var hidden = false;
                var walk = padre;
                while (walk && walk !== '0') {
                    if (pcAsignarPendCollapsed[walk]) {
                        hidden = true;
                        break;
                    }
                    var pEl = byId[walk];
                    walk = pEl ? (pEl.getAttribute('data-apr-padre') || '0') : '0';
                }
                el.classList.toggle('is-hidden', hidden);
            }
            var toggles = box.querySelectorAll('.pc-apr-toggle[data-apr-toggle]');
            for (var t = 0; t < toggles.length; t++) {
                var tid = toggles[t].getAttribute('data-apr-toggle');
                var collapsed = !!pcAsignarPendCollapsed[tid];
                toggles[t].textContent = collapsed ? '\u25B6' : '\u25BC';
                toggles[t].setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                toggles[t].title = collapsed ? 'Expandir' : 'Minimizar';
            }
        }

        function abrirAsignarDesdePendiente(pld, codigo, descripcion) {
            pendingAssignPld = pld;
            pendingAssignSelected = selectedPpa || 0;
            pendingAssignSelectedLbl = selectedRubroLbl || '';
            pcAsignarPendCollapsed = {};
            var cuentaEl = document.getElementById('pc_asignar_pend_cuenta');
            if (cuentaEl) {
                cuentaEl.textContent = (codigo || '') + (descripcion ? (' - ' + descripcion) : '');
            }
            var filtroEl = document.getElementById('pc_asignar_pend_filtro');
            if (filtroEl) {
                filtroEl.value = '';
            }
            showMsgIn('pc_asignar_pend_alert', true, '', false);
            var box = document.getElementById('pc_asignar_pend_tree');
            if (box) {
                box.innerHTML = '<div class="ppto-pc-empty">Cargando arbol de rubros...</div>';
            }
            pcAsignarPendUpdateSelLbl();
            openModal('modal_pc_asignar_pend');
            api('arbol', { filtro: 'todos' }).then(function (res) {
                var rows = res.rows || [];
                pendingAssignRubros = [];
                rows.forEach(function (r) {
                    pendingAssignRubros.push({
                        ppa_id: parseInt(r.ppa_id, 10) || 0,
                        padre_id: parseInt(r.ppa_padre_id, 10) || 0,
                        codigo: r.ppa_codigo_clasificacion,
                        descripcion: r.ppa_descripcion,
                        clase: r.ppa_clase || 'D',
                        cuentas: parseInt(r.cuentas, 10) || 0,
                        indent_px: parseInt(r.indent_px, 10) || 0
                    });
                });
                paintAsignarPendRubros('');
            }).catch(function (err) {
                showMsgIn('pc_asignar_pend_alert', false, (err && err.message) ? err.message : 'No se pudieron cargar los rubros.', true);
            });
        }

        var btnAsignarPendCerrar = document.getElementById('pc_modal_asignar_pend_cerrar');
        var btnAsignarPendCancel = document.getElementById('pc_modal_asignar_pend_cancel');
        var btnAsignarPendOk = document.getElementById('pc_modal_asignar_pend_ok');
        var filtAsignarPend = document.getElementById('pc_asignar_pend_filtro');
        var treeAsignarPend = document.getElementById('pc_asignar_pend_tree');
        if (btnAsignarPendCerrar) {
            btnAsignarPendCerrar.addEventListener('click', function () { closeModal('modal_pc_asignar_pend'); });
        }
        if (btnAsignarPendCancel) {
            btnAsignarPendCancel.addEventListener('click', function () { closeModal('modal_pc_asignar_pend'); });
        }
        if (filtAsignarPend) {
            filtAsignarPend.addEventListener('keyup', function () {
                paintAsignarPendRubros(filtAsignarPend.value || '');
            });
        }
        if (treeAsignarPend) {
            treeAsignarPend.addEventListener('click', function (ev) {
                var tog = ev.target.closest ? ev.target.closest('.pc-apr-toggle[data-apr-toggle]') : null;
                var rowG = ev.target.closest ? ev.target.closest('.pc-apr-row.is-grupo') : null;
                if (!tog && rowG) {
                    tog = rowG.querySelector('.pc-apr-toggle[data-apr-toggle]');
                }
                if (tog) {
                    ev.preventDefault();
                    var gid = tog.getAttribute('data-apr-toggle');
                    if (gid) {
                        pcAsignarPendCollapsed[gid] = !pcAsignarPendCollapsed[gid];
                        pcAsignarPendApplyCollapsed(treeAsignarPend);
                    }
                    return;
                }
                var rowD = ev.target.closest ? ev.target.closest('.pc-apr-row.is-detalle') : null;
                if (rowD) {
                    var radio = rowD.querySelector('input[type=radio]');
                    if (radio) {
                        radio.checked = true;
                        pendingAssignSelected = parseInt(radio.value, 10) || 0;
                        var span = rowD.querySelector('span:last-child');
                        pendingAssignSelectedLbl = span ? span.textContent.replace(/\s*�.*$/, '').trim() : '';
                        var all = treeAsignarPend.querySelectorAll('.pc-apr-row.is-detalle');
                        for (var i = 0; i < all.length; i++) {
                            all[i].classList.toggle('is-selected', all[i] === rowD);
                        }
                        pcAsignarPendUpdateSelLbl();
                    }
                }
            });
        }
        if (btnAsignarPendOk) {
            btnAsignarPendOk.addEventListener('click', function () {
                var radio = document.querySelector('#pc_asignar_pend_tree input[name=pc_apr_rubro]:checked');
                var ppa = radio ? parseInt(radio.value, 10) : (pendingAssignSelected || 0);
                if (!pendingAssignPld) {
                    showMsgIn('pc_asignar_pend_alert', false, 'Cuenta no valida.', true);
                    return;
                }
                if (!ppa) {
                    showMsgIn('pc_asignar_pend_alert', false, 'Seleccione un rubro detalle en el arbol.', true);
                    return;
                }
                var lbl = pendingAssignSelectedLbl || '';
                if (radio) {
                    var lab = radio.closest ? radio.closest('label') : null;
                    var sp = lab ? lab.querySelector('span:last-child') : null;
                    if (sp) {
                        lbl = sp.textContent.replace(/\s*�.*$/, '').trim();
                    }
                }
                btnAsignarPendOk.disabled = true;
                api('asignar', { ppa_id: ppa, pld_cod: pendingAssignPld }, 'POST').then(function (res) {
                    btnAsignarPendOk.disabled = false;
                    if (!res.ok) {
                        showMsgIn('pc_asignar_pend_alert', false, res.message || 'No se pudo asignar.', true);
                        return;
                    }
                    selectedPpa = ppa;
                    selectedRubroLbl = lbl;
                    closeModal('modal_pc_asignar_pend');
                    showMsgPend(true, res.message || ('Cuenta asignada a ' + selectedRubroLbl));
                    if (res.kpis) {
                        paintKpis(res.kpis);
                    }
                    loadPendientes();
                    loadArbol();
                }).catch(function (err) {
                    btnAsignarPendOk.disabled = false;
                    showMsgIn('pc_asignar_pend_alert', false, (err && err.message) ? err.message : 'Error al asignar.', true);
                });
            });
        }

        loadMeta().then(loadArbol);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
