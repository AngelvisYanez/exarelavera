/**
 * COMPONENTE MODULAR DE GESTIÓN OFFLINE & MAPA LOCAL (JSON)
 * Módulo de Mapeo Interactivo Georreferenciado - Relavera Comunitaria El Tablón
 * 
 * Funcionalidades:
 * 1. Detección automática de conectividad (Eventos de red + Heartbeat activo).
 * 2. Banner prominente visual en pantalla cuando se entra en MODO OFFLINE.
 * 3. Almacenamiento local estructurado en JSON (Actividades, Sectores, Fotos comprimidas y GPS).
 * 4. Cuadro de Diálogo Interactivo al restablecerse la conexión a Internet para confirmar sincronización.
 * 5. Descarga física del mapa y datos a archivo local (.json) estilo "Google Maps Offline".
 * 6. Importación / Carga de archivo de mapa local (.json) para operar sin cobertura.
 * 
 * @package mapeo.COMPONENTES
 * @version 1.0
 */

var OfflineManager = (function() {
    'use strict';

    var STORAGE_KEY = 'relavera_offline_queue_v2';
    var HEARTBEAT_INTERVAL_MS = 25000;
    var _heartbeatTimer = null;
    var _isOnline = navigator.onLine;

    var state = {
        actividades: [],
        sectores: [],
        gps: []
    };

    /**
     * Inicializar el Gestor Offline
     */
    function init() {
        cargarColaLocal();
        registrarEventosRed();
        iniciarHeartbeat();
        actualizarUI();
    }

    /**
     * Cargar cola pendiente desde localStorage
     */
    function cargarColaLocal() {
        try {
            var guardado = localStorage.getItem(STORAGE_KEY);
            if (guardado) {
                var parsed = JSON.parse(guardado);
                if (parsed && typeof parsed === 'object') {
                    state.actividades = Array.isArray(parsed.actividades) ? parsed.actividades : [];
                    state.sectores = Array.isArray(parsed.sectores) ? parsed.sectores : [];
                    state.gps = Array.isArray(parsed.gps) ? parsed.gps : [];
                }
            }
        } catch (e) {
            console.warn('Error al leer cola offline:', e);
        }
    }

    /**
     * Guardar cola en localStorage
     */
    function guardarColaLocal() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            console.error('Error al persistir en localStorage:', e);
        }
        actualizarUI();
    }

    /**
     * Registro de listeners de eventos de red del navegador
     */
    function registrarEventosRed() {
        window.addEventListener('online', function() {
            verificarConectividadReal(function(conectado) {
                if (conectado) {
                    setEstadoRed(true);
                    if (totalPendientes() > 0) {
                        mostrarDialogoSincronizacion();
                    } else if (typeof mostrarToast === 'function') {
                        mostrarToast('🌐 Conexión a internet restablecida.');
                    }
                }
            });
        });

        window.addEventListener('offline', function() {
            setEstadoRed(false);
            if (typeof mostrarToast === 'function') {
                mostrarToast('⚠️ Modo Offline activado. Operando con memoria local JSON.');
            }
        });
    }

    /**
     * Heartbeat activo para comprobar acceso real al servidor (evita falsos positivos de Wi-Fi sin internet)
     */
    function verificarConectividadReal(callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '../LOGICA/map_log_mapeo.php?action=get_emergencias&_t=' + Date.now(), true);
        xhr.timeout = 3500;
        xhr.onload = function() {
            var ok = (xhr.status >= 200 && xhr.status < 300);
            if (typeof callback === 'function') callback(ok);
        };
        xhr.onerror = function() {
            if (typeof callback === 'function') callback(false);
        };
        xhr.ontimeout = function() {
            if (typeof callback === 'function') callback(false);
        };
        try {
            xhr.send();
        } catch (e) {
            if (typeof callback === 'function') callback(false);
        }
    }

    function iniciarHeartbeat() {
        if (_heartbeatTimer) clearInterval(_heartbeatTimer);
        _heartbeatTimer = setInterval(function() {
            verificarConectividadReal(function(conectado) {
                if (conectado !== _isOnline) {
                    var antesEstabaOffline = !_isOnline;
                    setEstadoRed(conectado);
                    if (conectado && antesEstabaOffline && totalPendientes() > 0) {
                        mostrarDialogoSincronizacion();
                    }
                }
            });
        }, HEARTBEAT_INTERVAL_MS);
    }

    function setEstadoRed(online) {
        _isOnline = online;
        actualizarUI();
    }

    function totalPendientes() {
        return state.actividades.length + state.sectores.length;
    }

    /**
     * Actualizar elementos visuales de la interfaz
     */
    function actualizarUI() {
        var banner = document.getElementById('offline-floating-banner');
        var bannerCount = document.getElementById('offline-banner-count');
        var pill = document.getElementById('offline-pill');
        var dot = document.getElementById('offline-dot');
        var text = document.getElementById('offline-text');

        var numPend = totalPendientes();

        // 1. Banner Flotante Prominente
        if (banner) {
            if (!_isOnline) {
                banner.style.display = 'flex';
                banner.className = 'offline-floating-banner offline-mode-active';
                if (bannerCount) {
                    bannerCount.textContent = numPend > 0 
                        ? (numPend + (numPend === 1 ? ' registro pendiente' : ' registros pendientes'))
                        : '0 pendientes';
                }
            } else if (numPend > 0) {
                banner.style.display = 'flex';
                banner.className = 'offline-floating-banner online-with-pending';
                if (bannerCount) {
                    bannerCount.textContent = numPend + ' pendientes por sincronizar';
                }
            } else {
                banner.style.display = 'none';
            }
        }

        // 2. Pill del Topbar
        if (pill && dot && text) {
            if (_isOnline) {
                pill.classList.remove('offline');
                dot.className = 'fa fa-circle text-success';
                text.textContent = numPend > 0 ? ('En Línea (' + numPend + ' pend.)') : 'En Línea';
            } else {
                pill.classList.add('offline');
                dot.className = 'fa fa-circle text-danger';
                text.textContent = 'Modo Offline (' + numPend + ' pend.)';
            }
        }
    }

    /**
     * Guardar registro en la cola local offline
     */
    function guardarRegistro(tipo, datos) {
        datos.sincronizado_offline = true;
        datos.fecha_registro_local = new Date().toISOString().replace('T', ' ').substr(0, 19);

        if (tipo === 'actividad') {
            if (!datos.id) datos.id = 'ACT-OFF-' + Date.now();
            state.actividades.unshift(datos);
        } else if (tipo === 'sector') {
            if (!datos.id) datos.id = 'SEC-OFF-' + Date.now();
            state.sectores.unshift(datos);
        } else if (tipo === 'gps') {
            state.gps.push(datos);
            if (state.gps.length > 250) state.gps = state.gps.slice(-250);
        }

        guardarColaLocal();
    }

    /**
     * Mostrar Cuadro de Diálogo Modal de Sincronización
     */
    function mostrarDialogoSincronizacion(forzar) {
        var modal = document.getElementById('modal-sincronizacion-offline');
        if (!modal) return;

        var numPend = totalPendientes();
        if (numPend === 0 && !forzar) {
            if (typeof mostrarToast === 'function') mostrarToast('No hay registros pendientes por sincronizar.');
            return;
        }

        var tbody = document.getElementById('offline-sync-table-body');
        var resText = document.getElementById('offline-sync-resumen-text');

        if (resText) {
            resText.innerHTML = 'Se detectaron <b>' + state.actividades.length + ' actividades/eventos</b> y <b>' + state.sectores.length + ' sectores/ubicaciones</b> registrados en este dispositivo durante el Modo Offline.';
        }

        if (tbody) {
            var rowsHtml = '';
            // Sectores
            state.sectores.forEach(function(sec, i) {
                var secNom = sec.nombre || 'Sector';
                rowsHtml += '<tr>' +
                    '<td><span class="badge-act info"><i class="fa ' + (sec.icono || 'fa-map-marker') + '"></i> Sector</span></td>' +
                    '<td><b>' + secNom + '</b><br><small style="color:#64748b;">' + (sec.direccion || 'Ubicación') + '</small></td>' +
                    '<td>' + (sec.fecha_registro_local || 'Hoy') + '</td>' +
                    '<td>' + parseFloat(sec.lat).toFixed(5) + ', ' + parseFloat(sec.lng).toFixed(5) + '</td>' +
                    '<td><button type="button" class="btn btn-xs btn-danger" onclick="OfflineManager.eliminarDeCola(\'sector\', ' + i + ')"><i class="fa fa-trash"></i></button></td>' +
                '</tr>';
            });

            // Actividades
            state.actividades.forEach(function(act, i) {
                var numEvi = (Array.isArray(act.evidencias)) ? act.evidencias.length : 0;
                var eviBadge = numEvi > 0 ? (' <span class="badge-act success" style="font-size:9px;"><i class="fa fa-camera"></i> ' + numEvi + '</span>') : '';
                rowsHtml += '<tr>' +
                    '<td><span class="badge-act success"><i class="fa ' + (act.icono || 'fa-truck') + '"></i> Evento</span></td>' +
                    '<td><b>' + (act.tipo_label || act.tipo || 'Actividad') + '</b>' + eviBadge + '<br><small style="color:#0f766e;">' + (act.ubicacion_nombre || 'Relavera') + '</small></td>' +
                    '<td>' + (act.fecha || act.fecha_registro_local || 'Hoy') + '</td>' +
                    '<td>' + parseFloat(act.lat).toFixed(5) + ', ' + parseFloat(act.lng).toFixed(5) + '</td>' +
                    '<td><button type="button" class="btn btn-xs btn-danger" onclick="OfflineManager.eliminarDeCola(\'actividad\', ' + i + ')"><i class="fa fa-trash"></i></button></td>' +
                '</tr>';
            });

            if (!rowsHtml) {
                rowsHtml = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;">No hay registros pendientes en la cola local.</td></tr>';
            }
            tbody.innerHTML = rowsHtml;
        }

        modal.classList.add('active');
    }

    function cerrarDialogoSincronizacion() {
        var modal = document.getElementById('modal-sincronizacion-offline');
        if (modal) modal.classList.remove('active');
    }

    function eliminarDeCola(tipo, index) {
        if (tipo === 'sector' && index >= 0 && index < state.sectores.length) {
            state.sectores.splice(index, 1);
        } else if (tipo === 'actividad' && index >= 0 && index < state.actividades.length) {
            state.actividades.splice(index, 1);
        }
        guardarColaLocal();
        mostrarDialogoSincronizacion(true);
    }

    /**
     * Ejecutar sincronización masiva hacia el backend MySQL
     */
    function ejecutarSincronizacion() {
        var numPend = totalPendientes();
        if (numPend === 0) {
            alert('No hay elementos pendientes para sincronizar.');
            return;
        }

        var btnSync = document.getElementById('btn-ejecutar-sync-offline');
        if (btnSync) {
            btnSync.disabled = true;
            btnSync.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sincronizando con MySQL...';
        }

        var payload = {
            actividades: state.actividades,
            sectores: state.sectores,
            gps_puntos: state.gps
        };

        fetch('../LOGICA/map_log_mapeo.php?action=sincronizar_lote_offline', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (btnSync) {
                btnSync.disabled = false;
                btnSync.innerHTML = '<i class="fa fa-cloud-upload"></i> Sincronizar Ahora con MySQL';
            }

            if (res.success) {
                state.actividades = [];
                state.sectores = [];
                state.gps = [];
                guardarColaLocal();
                cerrarDialogoSincronizacion();

                if (typeof cargarActividades === 'function') cargarActividades();
                if (typeof cargarSectores === 'function') cargarSectores();

                if (typeof mostrarToast === 'function') {
                    mostrarToast('✅ ' + (res.message || 'Sincronización completada exitosamente.'));
                } else {
                    alert(res.message || 'Sincronización completada con éxito.');
                }
            } else {
                alert('Fallo en sincronización: ' + res.message);
            }
        })
        .catch(function(err) {
            if (btnSync) {
                btnSync.disabled = false;
                btnSync.innerHTML = '<i class="fa fa-cloud-upload"></i> Sincronizar Ahora con MySQL';
            }
            alert('Error de conexión al sincronizar. Se mantendrán los registros guardados en este dispositivo.');
            console.error('Error en sincronización:', err);
        });
    }

    /**
     * 📥 Descargar mapa y datos actuales a un archivo JSON local en el dispositivo
     */
    function descargarMapaOfflineJSON() {
        var ahora = new Date();
        var yyyymmdd = ahora.getFullYear() +
            String(ahora.getMonth() + 1).padStart(2, '0') +
            String(ahora.getDate()).padStart(2, '0') + '_' +
            String(ahora.getHours()).padStart(2, '0') +
            String(ahora.getMinutes()).padStart(2, '0');

        var nombreArchivo = 'mapa_relavera_offline_' + yyyymmdd + '.json';

        var exportData = {
            sistema: 'ERP Ecopark Mining - Relavera El Tablón',
            modulo: 'Mapeo Interactivo Georreferenciado',
            version: '3.0',
            fecha_exportacion: ahora.toISOString(),
            sectores: (typeof MapeoState !== 'undefined' && Array.isArray(MapeoState.sectores)) ? MapeoState.sectores : [],
            actividades: (typeof MapeoState !== 'undefined' && Array.isArray(MapeoState.actividades)) ? MapeoState.actividades : [],
            cola_offline_pendiente: state
        };

        var jsonString = JSON.stringify(exportData, null, 2);
        var blob = new Blob([jsonString], { type: 'application/json;charset=utf-8;' });
        var url = URL.createObjectURL(blob);

        var a = document.createElement('a');
        a.href = url;
        a.download = nombreArchivo;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        if (typeof mostrarToast === 'function') {
            mostrarToast('📥 Archivo "' + nombreArchivo + '" descargado correctamente.');
        }
    }

    /**
     * 📤 Importar / Cargar un archivo JSON local en el mapa
     */
    function cargarMapaOfflineDesdeInput(fileInput) {
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) return;
        var file = fileInput.files[0];

        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var data = JSON.parse(e.target.result);
                if (!data || typeof data !== 'object') {
                    throw new Error('Formato JSON inválido.');
                }

                var numSecCargados = 0;
                var numActCargados = 0;

                // Restaurar Sectores
                if (Array.isArray(data.sectores) && data.sectores.length > 0) {
                    if (typeof MapeoState !== 'undefined') {
                        MapeoState.sectores = data.sectores;
                        if (typeof renderSectoresMapa === 'function') renderSectoresMapa();
                        if (typeof renderSectoresLista === 'function') renderSectoresLista();
                        numSecCargados = data.sectores.length;
                    }
                }

                // Restaurar Actividades
                if (Array.isArray(data.actividades) && data.actividades.length > 0) {
                    if (typeof MapeoState !== 'undefined') {
                        MapeoState.actividades = data.actividades;
                        if (typeof aplicarFiltrosActividades === 'function') aplicarFiltrosActividades();
                        numActCargados = data.actividades.length;
                    }
                }

                // Si contiene cola pendiente
                if (data.cola_offline_pendiente && typeof data.cola_offline_pendiente === 'object') {
                    if (Array.isArray(data.cola_offline_pendiente.actividades)) {
                        data.cola_offline_pendiente.actividades.forEach(function(a) {
                            if (!state.actividades.some(function(ea) { return ea.id === a.id; })) {
                                state.actividades.push(a);
                            }
                        });
                    }
                    if (Array.isArray(data.cola_offline_pendiente.sectores)) {
                        data.cola_offline_pendiente.sectores.forEach(function(s) {
                            if (!state.sectores.some(function(es) { return es.id === s.id; })) {
                                state.sectores.push(s);
                            }
                        });
                    }
                    guardarColaLocal();
                }

                if (typeof mostrarToast === 'function') {
                    mostrarToast('📤 Mapa restaurado: ' + numSecCargados + ' sectores y ' + numActCargados + ' actividades cargadas desde JSON.');
                } else {
                    alert('Mapa cargado con éxito desde archivo local.');
                }
            } catch (err) {
                alert('No se pudo leer el archivo JSON: ' + err.message);
            }
        };
        reader.readAsText(file);
        fileInput.value = '';
    }

    return {
        init: init,
        isOnline: function() { return _isOnline; },
        guardarRegistro: guardarRegistro,
        mostrarDialogoSincronizacion: mostrarDialogoSincronizacion,
        cerrarDialogoSincronizacion: cerrarDialogoSincronizacion,
        eliminarDeCola: eliminarDeCola,
        ejecutarSincronizacion: ejecutarSincronizacion,
        descargarMapaOfflineJSON: descargarMapaOfflineJSON,
        cargarMapaOfflineDesdeInput: cargarMapaOfflineDesdeInput,
        totalPendientes: totalPendientes
    };
})();

// Inicializar al cargar el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', OfflineManager.init);
} else {
    OfflineManager.init();
}
