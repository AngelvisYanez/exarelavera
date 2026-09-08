/**
 * Monitor de Inactividad y Heartbeat de Sesión en Tiempo Real.
 * Detecta actividad del usuario (teclado, ratón, scroll) en la ventana principal
 * y dentro de iframes. Cierra sesión tras 15 min de inactividad con advertencia de 60s.
 * Verifica si el administrador ha expulsado la sesión.
 *
 * @package auditoria.VALIDACIONES
 */

(function (window, document, $) {
    'use strict';

    if (!$) return;

    // Cierre automatico por inactividad DESACTIVADO por defecto (produccion).
    // Activar con AUDIT_IDLE_LOGOUT=true en .env (endpoint lo valida de nuevo).
    // El heartbeat se mantiene activo para el monitor "en vivo" y la expulsion forzada.
    var AUD_IDLE_TIMEOUT_HABILITADO = false;

    // Configuración en milisegundos
    var TIEMPO_INACTIVIDAD_TOTAL_MS = 15 * 60 * 1000; // 15 minutos
    var TIEMPO_ADVERTENCIA_MS = 60 * 1000;            // 60 segundos antes
    var TIEMPO_DISPARO_MODAL_MS = TIEMPO_INACTIVIDAD_TOTAL_MS - TIEMPO_ADVERTENCIA_MS; // 14 minutos
    var INTERVALO_HEARTBEAT_MS = 2 * 60 * 1000;      // Ping cada 2 minutos

    var ultimoMovimiento = Date.now();
    var modalAdvertenciaVisible = false;
    var intervaloCountdown = null;
    var tiempoRestanteSegundos = 60;
    var heartbeatTimer = null;
    var auditoriaEndpoint = '../../auditoria/LOGICA/aud_log_actividad_sesion.php';

    // Resolver ruta absoluta del endpoint independientemente de dónde se incluya
    if (typeof ace !== 'undefined' && ace.vars && ace.vars.base) {
        auditoriaEndpoint = ace.vars.base + '/../auditoria/LOGICA/aud_log_actividad_sesion.php';
    }

    /**
     * Construir e inyectar el Modal de Advertencia de Inactividad si no existe
     */
    function inyectarModalInactividad() {
        if (document.getElementById('modalInactividadAuditoria')) {
            return;
        }

        var html = [
            '<div id="modalInactividadAuditoria" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" style="z-index: 1060;">',
            '  <div class="modal-dialog modal-dialog-centered" style="max-width: 440px; margin: 100px auto;">',
            '    <div class="modal-content" style="border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.35); border: none; overflow: hidden;">',
            '      <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; padding: 18px 24px; border: none;">',
            '        <h4 class="modal-title" style="margin: 0; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px;">',
            '          <i class="ace-icon fa fa-hourglass-half" style="font-size: 22px;"></i> Advertencia de Inactividad',
            '        </h4>',
            '      </div>',
            '      <div class="modal-body" style="padding: 24px; text-align: center; background: #fff;">',
            '        <div style="width: 70px; height: 70px; margin: 0 auto 16px; border-radius: 50%; background: #fef3c7; display: flex; align-items: center; justify-content: center;">',
            '          <span id="audIdleCountSeconds" style="font-size: 28px; font-weight: 800; color: #b45309;">60</span>',
            '        </div>',
            '        <p style="font-size: 15px; color: #374151; margin-bottom: 8px; font-weight: 600;">',
            '          ¿Sigues trabajando en el sistema?',
            '        </p>',
            '        <p style="font-size: 13px; color: #6b7280; line-height: 1.5; margin: 0;">',
            '          Tu sesión ha permanecido inactiva por casi 15 minutos. Por seguridad, se cerrará automáticamente en <strong id="audIdleCountLabel" style="color: #b45309;">60 segundos</strong> si no se detecta actividad.',
            '        </p>',
            '      </div>',
            '      <div class="modal-footer" style="background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 14px 20px; display: flex; justify-content: space-between;">',
            '        <button type="button" id="btnAudCerrarSesionAhora" class="btn btn-default btn-sm" style="border-radius: 6px; font-weight: 500;">',
            '          <i class="fa fa-sign-out"></i> Salir ahora',
            '        </button>',
            '        <button type="button" id="btnAudContinuarSesion" class="btn btn-warning btn-sm" style="background: #d97706; border-color: #b45309; border-radius: 6px; font-weight: 600; color: #fff; padding: 6px 18px;">',
            '          <i class="fa fa-check"></i> Continuar trabajando',
            '        </button>',
            '      </div>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('\n');

        $('body').append(html);

        $('#btnAudContinuarSesion').on('click', function () {
            reiniciarActividad();
            enviarHeartbeat();
        });

        $('#btnAudCerrarSesionAhora').on('click', function () {
            cerrarSesionPorInactividad();
        });
    }

    /**
     * Inyectar Modal informativo cuando el Administrador expulsa la sesión
     */
    function mostrarModalExpulsionAdmin(mensaje) {
        if (modalAdvertenciaVisible) {
            $('#modalInactividadAuditoria').modal('hide');
        }

        if (document.getElementById('modalExpulsionAuditoria')) {
            $('#modalExpulsionAuditoria').modal('show');
            return;
        }

        var html = [
            '<div id="modalExpulsionAuditoria" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" style="z-index: 1070;">',
            '  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px; margin: 120px auto;">',
            '    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.4); overflow: hidden;">',
            '      <div class="modal-header" style="background: #dc2626; color: #fff; padding: 16px 20px; border: none;">',
            '        <h4 class="modal-title" style="margin: 0; font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 8px;">',
            '          <i class="fa fa-ban" style="font-size: 20px;"></i> Sesión Finalizada',
            '        </h4>',
            '      </div>',
            '      <div class="modal-body" style="padding: 24px; text-align: center;">',
            '        <i class="fa fa-lock text-danger" style="font-size: 42px; margin-bottom: 12px;"></i>',
            '        <p style="font-size: 15px; color: #1f2937; font-weight: 600; margin-bottom: 8px;">',
            mensaje || 'Su sesión ha sido finalizada por el Administrador de Sistemas.',
            '        </p>',
            '        <p style="font-size: 13px; color: #6b7280; margin: 0;">Redirigiendo a la pantalla de inicio de sesión...</p>',
            '      </div>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('\n');

        $('body').append(html);
        $('#modalExpulsionAuditoria').modal('show');

        setTimeout(function () {
            window.location.href = '../../index.php?motivo=expulsado';
        }, 3000);
    }

    /**
     * Resetea el reloj de actividad del usuario
     */
    function registrarEventoInteraccion() {
        ultimoMovimiento = Date.now();
    }

    /**
     * Escucha eventos de interacción en una ventana o documento dado (útil para iframes)
     */
    function engancharEventos(docTarget) {
        if (!docTarget) return;
        try {
            var eventos = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'];
            eventos.forEach(function (ev) {
                docTarget.addEventListener(ev, registrarEventoInteraccion, { passive: true, capture: true });
            });
        } catch (e) {
            // Ignorar errores de cross-origin si hubiera
        }
    }

    /**
     * Monitorea dinámicamente los iframes que se agreguen al DOM para capturar interacción
     */
    function vigilarIframes() {
        $('iframe').each(function () {
            try {
                if (this.contentDocument) {
                    engancharEventos(this.contentDocument);
                }
                $(this).off('load.audIdle').on('load.audIdle', function () {
                    try {
                        if (this.contentDocument) {
                            engancharEventos(this.contentDocument);
                        }
                    } catch (err) {}
                });
            } catch (err) {}
        });
    }

    /**
     * Muestra el modal con cuenta regresiva de 60 segundos
     */
    function activarModalAdvertencia() {
        if (modalAdvertenciaVisible) return;
        modalAdvertenciaVisible = true;
        tiempoRestanteSegundos = 60;

        inyectarModalInactividad();
        $('#audIdleCountSeconds').text(tiempoRestanteSegundos);
        $('#audIdleCountLabel').text(tiempoRestanteSegundos + ' segundos');
        $('#modalInactividadAuditoria').modal('show');

        if (intervaloCountdown) clearInterval(intervaloCountdown);

        intervaloCountdown = setInterval(function () {
            tiempoRestanteSegundos--;
            if (tiempoRestanteSegundos <= 0) {
                clearInterval(intervaloCountdown);
                $('#modalInactividadAuditoria').modal('hide');
                cerrarSesionPorInactividad();
            } else {
                $('#audIdleCountSeconds').text(tiempoRestanteSegundos);
                $('#audIdleCountLabel').text(tiempoRestanteSegundos + ' segundos');
            }
        }, 1000);
    }

    /**
     * Reinicia el estado cuando el usuario confirma que sigue trabajando
     */
    function reiniciarActividad() {
        modalAdvertenciaVisible = false;
        ultimoMovimiento = Date.now();
        if (intervaloCountdown) {
            clearInterval(intervaloCountdown);
            intervaloCountdown = null;
        }
        $('#modalInactividadAuditoria').modal('hide');
    }

    /**
     * Proceso de cierre de sesión por inactividad
     */
    function cerrarSesionPorInactividad() {
        if (intervaloCountdown) clearInterval(intervaloCountdown);
        if (heartbeatTimer) clearInterval(heartbeatTimer);

        $.ajax({
            url: auditoriaEndpoint,
            type: 'POST',
            dataType: 'json',
            data: { action: 'inactividad_timeout' },
            timeout: 5000
        }).always(function () {
            window.location.href = '../../index.php?motivo=inactividad';
        });
    }

    /**
     * Envía ping de heartbeat al servidor para actualizar Ses_Ult_Act y comprobar expulsión
     */
    function enviarHeartbeat() {
        $.ajax({
            url: auditoriaEndpoint,
            type: 'POST',
            dataType: 'json',
            data: { action: 'ping' },
            timeout: 5000,
            success: function (res) {
                if (res && res.forzar_logout) {
                    mostrarModalExpulsionAdmin(res.mensaje);
                }
            }
        });
    }

    /**
     * Ciclo principal de verificación periódica
     */
    function iniciarCicloVerificacion() {
        // Enganchar ventana principal
        engancharEventos(document);
        vigilarIframes();

        // Si se abre un nuevo tab o cambia el iframe, revisar nuevamente
        setInterval(vigilarIframes, 4000);

        // Verificador de inactividad cada segundo (solo si esta habilitado)
        if (AUD_IDLE_TIMEOUT_HABILITADO) {
            setInterval(function () {
                if (modalAdvertenciaVisible) return;

                var ahora = Date.now();
                var tiempoInactivo = ahora - ultimoMovimiento;

                if (tiempoInactivo >= TIEMPO_DISPARO_MODAL_MS) {
                    activarModalAdvertencia();
                }
            }, 1000);
        }

        // Heartbeat cada 2 minutos
        heartbeatTimer = setInterval(function () {
            var tiempoInactivo = Date.now() - ultimoMovimiento;
            // Solo enviar ping si el usuario ha tenido actividad en los ultimos 10 min
            if (tiempoInactivo < 10 * 60 * 1000) {
                enviarHeartbeat();
            }
        }, INTERVALO_HEARTBEAT_MS);

        // Primer heartbeat a los 5 segundos de arrancar
        setTimeout(enviarHeartbeat, 5000);
    }

    $(document).ready(function () {
        iniciarCicloVerificacion();
    });

})(window, document, window.jQuery);
