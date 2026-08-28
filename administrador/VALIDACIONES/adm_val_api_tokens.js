/**
 * Validaciones y Funcionalidades AJAX para el Módulo de API Tokens
 * 
 * @package administrador.VALIDACIONES
 * @author EXA Contable
 * @version 2.0
 */

var APITokensModule = (function () {
    var state = {
        tokens: [],
        empresas: [],
        rutasDisponibles: [
            // Directorio Operativo & ERP Locator
            { mod: 'contactos', ruta: '/v1/contactos', nombre: 'Directorio de Contactos de Notificación', grupo: 'Directorio Operativo (ERP Locator)' },
            { mod: 'plantas', ruta: '/v1/plantas', nombre: 'Directorio de Plantas de Beneficio', grupo: 'Directorio Operativo (ERP Locator)' },
            { mod: 'choferes', ruta: '/v1/choferes', nombre: 'Directorio de Choferes por Planta', grupo: 'Directorio Operativo (ERP Locator)' },
            { mod: 'vehiculos', ruta: '/v1/vehiculos', nombre: 'Directorio de Vehículos y Volquetas', grupo: 'Directorio Operativo (ERP Locator)' },
            // Contabilidad & Facturación
            { mod: 'contabilidad', ruta: '/v1/contabilidad/plan-cuentas', nombre: 'Plan de Cuentas Contable', grupo: 'Contabilidad & Finanzas' },
            { mod: 'contabilidad', ruta: '/v1/contabilidad/asientos', nombre: 'Asientos y Comprobantes Contables', grupo: 'Contabilidad & Finanzas' },
            { mod: 'facturacion', ruta: '/v1/facturacion/comprobantes', nombre: 'Comprobantes Electrónicos SRI', grupo: 'Facturación & Ventas' },
            { mod: 'facturacion', ruta: '/v1/facturacion/emitir', nombre: 'Emisión de Facturas Electrónicas', grupo: 'Facturación & Ventas' },
            // Tesorería & Adquisiciones
            { mod: 'tesoreria', ruta: '/v1/tesoreria/clientes', nombre: 'Directorio de Clientes', grupo: 'Tesorería & Bancos' },
            { mod: 'tesoreria', ruta: '/v1/tesoreria/bancos', nombre: 'Cuentas Bancarias y Saldos', grupo: 'Tesorería & Bancos' },
            { mod: 'adquisiciones', ruta: '/v1/adquisiciones/proveedores', nombre: 'Directorio de Proveedores', grupo: 'Compras & Adquisiciones' },
            { mod: 'compras', ruta: '/v1/compras/ordenes', nombre: 'Órdenes de Compra', grupo: 'Compras & Adquisiciones' },
            // Inventario & Bodega
            { mod: 'inventario', ruta: '/v1/inventario/productos', nombre: 'Catálogo de Productos', grupo: 'Inventario & Logística' },
            { mod: 'inventario', ruta: '/v1/inventario/categorias', nombre: 'Categorías de Inventario', grupo: 'Inventario & Logística' },
            { mod: 'inventario', ruta: '/v1/inventario/marcas', nombre: 'Marcas de Productos', grupo: 'Inventario & Logística' },
            { mod: 'bodega', ruta: '/v1/bodega/movimientos', nombre: 'Movimientos y Kárdex de Bodega', grupo: 'Inventario & Logística' },
            // Operaciones & Minería
            { mod: 'relavera', ruta: '/v1/relavera/manifiestos', nombre: 'Manifiestos Mineros y Relaveras', grupo: 'Minería & Relavera' },
            { mod: 'transportecarga', ruta: '/v1/transportecarga/viajes', nombre: 'Guías y Viajes de Carga', grupo: 'Transporte & Carga' },
            // RRHH & Auditoría
            { mod: 'rrhh', ruta: '/v1/rrhh/empleados', nombre: 'Personal y Nómina', grupo: 'Talento Humano (RRHH)' },
            { mod: 'auditoria', ruta: '/v1/auditoria/tareas', nombre: 'Bitácora y Auditoría de Tareas', grupo: 'Seguridad & Auditoría' }
        ],
        currentEditTokId: null,
        currentEditPermisos: []
    };

    function notificar(msg, tipo) {
        var cls = tipo === 'success' ? 'alert-success' : (tipo === 'warning' ? 'alert-warning' : 'alert-danger');
        var $toast = $('#toast-container');
        if (!$toast.length) {
            $('body').append('<div id="toast-container" style="position:fixed;top:20px;right:20px;z-index:999999;min-width:320px;"></div>');
            $toast = $('#toast-container');
        }
        var icon = tipo === 'success' ? 'fa-check-circle' : (tipo === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle');
        var item = $('<div class="alert ' + cls + ' alert-dismissible" style="box-shadow:0 4px 12px rgba(0,0,0,0.18);margin-bottom:10px;animation:fadeIn 0.3s;">' +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
            '<i class="fa ' + icon + '"></i> ' + $('<div>').text(msg).html() +
            '</div>');
        $toast.append(item);
        setTimeout(function () {
            item.fadeOut(400, function () { $(this).remove(); });
        }, 3500);
    }

    function apiCall(method, path, body) {
        return $.ajax({
            url: path,
            method: method,
            headers: { 'Content-Type': 'application/json' },
            data: body ? JSON.stringify(body) : undefined,
            xhrFields: { withCredentials: true }
        }).fail(function (xhr) {
            if (xhr.status === 401) {
                window.location.href = '../../index.php';
            }
        });
    }

    function cargarEmpresas() {
        return apiCall('GET', '/v1/admin/api-tokens/empresas').then(function (res) {
            if (res && res.success && Array.isArray(res.data)) {
                state.empresas = res.data;
                var html = '<option value="">-- Seleccionar Empresa --</option>';
                res.data.forEach(function (e) {
                    var sel = (typeof SES_EMP_COD !== 'undefined' && SES_EMP_COD && e.Emp_Cod == SES_EMP_COD) ? ' selected' : '';
                    html += '<option value="' + e.Emp_Cod + '"' + sel + '>[' + e.Emp_Cod + '] ' + $('<div>').text(e.Emp_Nom).html() + ' (' + (e.Bdd || 'Sin BDD') + ')</option>';
                });
                $('#tok_empresa').html(html);
            }
        });
    }

    function cargarTokens() {
        $('#tabla-tokens-loading').show();
        $('#tabla-tokens-body').empty();
        $('#lista-vacia').hide();

        return apiCall('GET', '/v1/admin/api-tokens').then(function (res) {
            $('#tabla-tokens-loading').hide();
            if (res && res.success && Array.isArray(res.data)) {
                state.tokens = res.data;
                renderTablaTokens();
            } else {
                state.tokens = [];
                $('#lista-vacia').show();
            }
        }).catch(function (xhr) {
            $('#tabla-tokens-loading').hide();
            notificar('Error al cargar tokens: ' + (xhr.responseJSON ? xhr.responseJSON.error : xhr.statusText), 'error');
        });
    }

    function renderTablaTokens() {
        var query = ($('#buscar-token').val() || '').toLowerCase().trim();
        var filtroEstado = $('#filtro-estado').val() || 'ALL';

        var filtrados = state.tokens.filter(function (t) {
            var matchQuery = !query ||
                (t.Tok_Nombre && t.Tok_Nombre.toLowerCase().indexOf(query) !== -1) ||
                (t.Tok_Resumen && t.Tok_Resumen.toLowerCase().indexOf(query) !== -1) ||
                (t.Tok_Bdd && t.Tok_Bdd.toLowerCase().indexOf(query) !== -1) ||
                (t.Emp_Nom && t.Emp_Nom.toLowerCase().indexOf(query) !== -1) ||
                (String(t.Emp_Cod).indexOf(query) !== -1);

            var matchEstado = filtroEstado === 'ALL' || t.Tok_Est === filtroEstado;
            return matchQuery && matchEstado;
        });

        var $tbody = $('#tabla-tokens-body');
        $tbody.empty();

        if (filtrados.length === 0) {
            $('#lista-vacia').show();
            return;
        }
        $('#lista-vacia').hide();

        filtrados.forEach(function (t) {
            var badgeEstado = t.Tok_Est === 'A'
                ? '<span class="label label-success"><i class="fa fa-check"></i> Activo</span>'
                : '<span class="label label-default"><i class="fa fa-ban"></i> Inactivo</span>';

            var cuota = parseInt(t.Tok_Cuota || 0, 10);
            var usadas = parseInt(t.Tok_Usadas || 0, 10);
            var badgeCuota = cuota <= 0
                ? '<span class="label label-info"><i class="fa fa-infinity"></i> Ilimitado</span>'
                : '<span class="label label-' + (usadas >= cuota ? 'danger' : 'primary') + '">' + usadas + ' / ' + cuota + '</span>';

            var periodo = t.Tok_Periodo === 'M' ? 'Mensual' : 'Diario';
            var expira = t.Tok_Expira ? t.Tok_Expira.substring(0, 10) : '<span class="text-muted">Nunca</span>';
            var creador = t.Tok_Creado_Por ? $('<div>').text(t.Tok_Creado_Por).html() : 'Sistema';
            var fechaCreado = t.Tok_Creado ? t.Tok_Creado.substring(0, 10) : '-';

            var row = $('<tr>' +
                '<td>' +
                    '<strong>' + $('<div>').text(t.Tok_Nombre || 'Token #' + t.Tok_Id).html() + '</strong>' +
                    '<br><span class="text-muted" style="font-family:monospace;font-size:11px;"><i class="fa fa-hashtag"></i> ...' + (t.Tok_Resumen || '') + '</span>' +
                '</td>' +
                '<td>' +
                    '<span>' + $('<div>').text(t.Emp_Nom || 'Empresa #' + t.Emp_Cod).html() + '</span>' +
                    '<br><span class="text-muted" style="font-size:11px;"><i class="fa fa-database"></i> ' + (t.Tok_Bdd || 'N/A') + ' (ID: ' + t.Emp_Cod + ')</span>' +
                '</td>' +
                '<td>' + badgeCuota + '<br><small class="text-muted">' + periodo + '</small></td>' +
                '<td>' + expira + '</td>' +
                '<td>' + badgeEstado + '</td>' +
                '<td><small class="text-muted">' + fechaCreado + '<br>por ' + creador + '</small></td>' +
                '<td class="text-right" style="white-space:nowrap;">' +
                    '<button class="btn btn-xs btn-default btn-permisos" data-id="' + t.Tok_Id + '" data-nom="' + $('<div>').text(t.Tok_Nombre).html() + '" title="Configurar Permisos"><i class="fa fa-shield text-primary"></i> Permisos</button> ' +
                    '<button class="btn btn-xs btn-default btn-probar" data-hash="' + t.Tok_Hash + '" data-nom="' + $('<div>').text(t.Tok_Nombre).html() + '" title="Probar Token"><i class="fa fa-play text-success"></i> Probar</button> ' +
                    (t.Tok_Est === 'A'
                        ? '<button class="btn btn-xs btn-danger btn-revocar" data-id="' + t.Tok_Id + '" data-nom="' + $('<div>').text(t.Tok_Nombre).html() + '" title="Revocar Token"><i class="fa fa-power-off"></i></button>'
                        : '') +
                '</td>' +
            '</tr>');

            $tbody.append(row);
        });
    }

    function renderPermisosCheckboxTree(containerId, permisosAsignados) {
        var $container = $(containerId);
        $container.empty();

        // Agrupar rutas por grupo
        var grupos = {};
        state.rutasDisponibles.forEach(function (r) {
            if (!grupos[r.grupo]) {
                grupos[r.grupo] = [];
            }
            grupos[r.grupo].push(r);
        });

        var asignadosMap = {};
        if (Array.isArray(permisosAsignados)) {
            permisosAsignados.forEach(function (p) {
                var ruta = typeof p === 'string' ? p : (p.Tip_Ruta || '');
                if (ruta) asignadosMap[ruta] = true;
            });
        }

        Object.keys(grupos).forEach(function (grupoNombre, idx) {
            var $grupoDiv = $('<div class="perm-grupo-block" style="margin-bottom:14px;border:1px solid #e9ecef;border-radius:6px;background:#fff;padding:10px 14px;">' +
                '<div style="font-weight:600;color:#801326;margin-bottom:8px;border-bottom:1px solid #f1f3f5;padding-bottom:4px;display:flex;justify-content:space-between;align-items:center;">' +
                    '<span><i class="fa fa-folder-open-o"></i> ' + grupoNombre + '</span>' +
                    '<button type="button" class="btn btn-xs btn-link btn-marcar-grupo" style="font-size:11px;padding:0;">Marcar grupo</button>' +
                '</div>' +
                '<div class="perm-items-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:6px 16px;"></div>' +
            '</div>');

            var $itemsGrid = $grupoDiv.find('.perm-items-grid');
            grupos[grupoNombre].forEach(function (item) {
                var checked = asignadosMap[item.ruta] ? ' checked' : '';
                var $itemLabel = $('<label class="checkbox-inline perm-item-label" style="font-weight:normal;margin-left:0;margin-right:10px;cursor:pointer;display:flex;align-items:center;gap:6px;">' +
                    '<input type="checkbox" class="perm-check" value="' + item.ruta + '" data-mod="' + item.mod + '" data-nombre="' + item.nombre + '"' + checked + '> ' +
                    '<span><b>' + item.nombre + '</b> <code style="font-size:11px;">' + item.ruta + '</code></span>' +
                '</label>');
                $itemsGrid.append($itemLabel);
            });

            $grupoDiv.find('.btn-marcar-grupo').on('click', function () {
                var inputs = $grupoDiv.find('.perm-check');
                var allChecked = inputs.filter(':checked').length === inputs.length;
                inputs.prop('checked', !allChecked);
            });

            $container.append($grupoDiv);
        });
    }

    function abrirModalNuevo() {
        $('#form-nuevo-token')[0].reset();
        if (typeof SES_EMP_COD !== 'undefined' && SES_EMP_COD) {
            $('#tok_empresa').val(SES_EMP_COD);
        }
        $('#tok_cuota').val('0');
        $('#tok_periodo').val('D');
        renderPermisosCheckboxTree('#contenedor-permisos-nuevo', [
            '/v1/contactos',
            '/v1/plantas',
            '/v1/choferes',
            '/v1/vehiculos'
        ]);
        $('#modal-nuevo-token').modal('show');
    }

    function guardarNuevoToken() {
        var nombre = $('#tok_nombre').val().trim();
        var empCod = $('#tok_empresa').val();
        var cuota = parseInt($('#tok_cuota').val() || 0, 10);
        var periodo = $('#tok_periodo').val();
        var expira = $('#tok_expira').val();

        if (!nombre) {
            notificar('Por favor ingrese un nombre para identificar el token.', 'warning');
            $('#tok_nombre').focus();
            return;
        }
        if (!empCod) {
            notificar('Seleccione la empresa para este token.', 'warning');
            $('#tok_empresa').focus();
            return;
        }

        var permisosSeleccionados = [];
        $('#contenedor-permisos-nuevo .perm-check:checked').each(function () {
            permisosSeleccionados.push({
                modulo: $(this).data('mod'),
                ruta: $(this).val(),
                nombre: $(this).data('nombre')
            });
        });

        var payload = {
            nombre: nombre,
            Emp_Cod: parseInt(empCod, 10),
            cuota: cuota,
            periodo: periodo,
            expira: expira || null,
            creadoPor: 'admin:panel',
            permisos: permisosSeleccionados
        };

        $('#btn-guardar-nuevo').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generando...');

        apiCall('POST', '/v1/admin/api-tokens/generar', payload).then(function (res) {
            $('#btn-guardar-nuevo').prop('disabled', false).html('<i class="fa fa-check"></i> Generar Token');
            $('#modal-nuevo-token').modal('hide');

            if (res && res.success && res.data && res.data.token) {
                $('#modal-token-generado-valor').text(res.data.token);
                $('#modal-token-generado-nombre').text(nombre);
                $('#modal-token-generado').modal('show');
                cargarTokens();
            } else {
                notificar('Token creado correctamente.', 'success');
                cargarTokens();
            }
        }).catch(function (xhr) {
            $('#btn-guardar-nuevo').prop('disabled', false).html('<i class="fa fa-check"></i> Generar Token');
            notificar('Error al generar token: ' + (xhr.responseJSON ? xhr.responseJSON.error : xhr.statusText), 'error');
        });
    }

    function abrirModalPermisos(tokId, tokNombre) {
        state.currentEditTokId = tokId;
        $('#modal-permisos-nombre').text(tokNombre);
        $('#modal-permisos-loading').show();
        $('#contenedor-permisos-editar').empty();
        $('#modal-permisos').modal('show');

        apiCall('GET', '/v1/admin/api-tokens/' + tokId + '/permisos').then(function (res) {
            $('#modal-permisos-loading').hide();
            var asignados = (res && res.success && Array.isArray(res.data)) ? res.data : [];
            renderPermisosCheckboxTree('#contenedor-permisos-editar', asignados);
        }).catch(function () {
            $('#modal-permisos-loading').hide();
            renderPermisosCheckboxTree('#contenedor-permisos-editar', []);
        });
    }

    function guardarPermisosEdicion() {
        if (!state.currentEditTokId) return;

        var permisosSeleccionados = [];
        $('#contenedor-permisos-editar .perm-check:checked').each(function () {
            permisosSeleccionados.push({
                modulo: $(this).data('mod'),
                ruta: $(this).val(),
                nombre: $(this).data('nombre')
            });
        });

        var payload = {
            id: state.currentEditTokId,
            permisos: permisosSeleccionados
        };

        $('#btn-guardar-permisos').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        apiCall('POST', '/v1/admin/api-tokens/permisos', payload).then(function (res) {
            $('#btn-guardar-permisos').prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Permisos');
            $('#modal-permisos').modal('hide');
            notificar('Permisos actualizados con éxito.', 'success');
            cargarTokens();
        }).catch(function (xhr) {
            $('#btn-guardar-permisos').prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Permisos');
            notificar('Error al guardar permisos: ' + (xhr.responseJSON ? xhr.responseJSON.error : xhr.statusText), 'error');
        });
    }

    function revocarToken(tokId, tokNombre) {
        if (!confirm('¿Está seguro de revocar el token "' + tokNombre + '"? Las aplicaciones que lo utilicen dejarán de tener acceso.')) {
            return;
        }

        apiCall('POST', '/v1/admin/api-tokens/revocar', { id: tokId }).then(function (res) {
            notificar('Token revocado correctamente.', 'success');
            cargarTokens();
        }).catch(function (xhr) {
            notificar('Error al revocar token: ' + (xhr.responseJSON ? xhr.responseJSON.error : xhr.statusText), 'error');
        });
    }

    function copiarTokenGenerado() {
        var texto = $('#modal-token-generado-valor').text();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texto).then(function () {
                notificar('Token copiado al portapapeles.', 'success');
            });
        } else {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(texto).select();
            document.execCommand('copy');
            $temp.remove();
            notificar('Token copiado al portapapeles.', 'success');
        }
    }

    function initEventos() {
        $('#btn-nuevo-token').on('click', abrirModalNuevo);
        $('#btn-recargar-tokens').on('click', cargarTokens);
        $('#btn-guardar-nuevo').on('click', guardarNuevoToken);
        $('#btn-guardar-permisos').on('click', guardarPermisosEdicion);
        $('#btn-copiar-token').on('click', copiarTokenGenerado);

        $('#buscar-token').on('input', renderTablaTokens);
        $('#filtro-estado').on('change', renderTablaTokens);

        // Delegación de eventos para la tabla
        $('#tabla-tokens-body').on('click', '.btn-permisos', function () {
            abrirModalPermisos($(this).data('id'), $(this).data('nom'));
        });

        $('#tabla-tokens-body').on('click', '.btn-revocar', function () {
            revocarToken($(this).data('id'), $(this).data('nom'));
        });

        $('#tabla-tokens-body').on('click', '.btn-probar', function () {
            window.open('/v1/api-tokens-probar', '_blank');
        });

        // Filtro rápido de permisos en modales
        $('#buscar-permiso-nuevo').on('input', function () {
            var q = $(this).val().toLowerCase();
            $('#contenedor-permisos-nuevo .perm-item-label').each(function () {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(q) !== -1);
            });
        });

        $('#buscar-permiso-editar').on('input', function () {
            var q = $(this).val().toLowerCase();
            $('#contenedor-permisos-editar .perm-item-label').each(function () {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(q) !== -1);
            });
        });

        // Marcar/desmarcar todos los permisos
        $('#btn-marcar-todos-nuevo').on('click', function () {
            $('#contenedor-permisos-nuevo .perm-check').prop('checked', true);
        });
        $('#btn-desmarcar-todos-nuevo').on('click', function () {
            $('#contenedor-permisos-nuevo .perm-check').prop('checked', false);
        });

        $('#btn-marcar-todos-editar').on('click', function () {
            $('#contenedor-permisos-editar .perm-check').prop('checked', true);
        });
        $('#btn-desmarcar-todos-editar').on('click', function () {
            $('#contenedor-permisos-editar .perm-check').prop('checked', false);
        });
    }

    function init() {
        initEventos();
        cargarEmpresas();
        cargarTokens();
    }

    return {
        init: init,
        cargarTokens: cargarTokens
    };
})();

$(document).ready(function () {
    APITokensModule.init();
});
