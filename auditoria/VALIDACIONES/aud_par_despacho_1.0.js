/**
 * Parámetros y constantes del módulo Gestión Operativa del Despacho
 * @version 1.0
 */
$(function () {

    window.AUD_DESP_ESTADOS_CLIENTE = {
        'ACTIVO': 'ACTIVO',
        'SUSPENDIDO': 'SUSPENDIDO',
        'FINALIZADO': 'FINALIZADO'
    };

    window.AUD_DESP_ESTADOS_TAREA = {
        'PENDIENTE': 'PENDIENTE',
        'EN_PROCESO': 'EN_PROCESO',
        'OBSERVADA': 'OBSERVADA',
        'FINALIZADA': 'FINALIZADA',
        'VENCIDA': 'VENCIDA'
    };

    window.AUD_DESP_TIPOS_ACTIVIDAD = {
        'MENSUAL': 'MENSUAL',
        'ANUAL': 'ANUAL',
        'EVENTUAL': 'EVENTUAL'
    };

    window.AUD_DESP_ALCANCE_REGLA = {
        'UNO': 'UNO',
        'VARIOS': 'VARIOS',
        'TODOS': 'TODOS'
    };

    window.aud_desp_validaPeriodo = function (valor) {
        if (!valor || typeof valor !== 'string') return false;
        var v = valor.trim();
        if (v.length === 4) return /^\d{4}$/.test(v);
        if (v.length === 7) return /^\d{4}-\d{2}$/.test(v);
        return false;
    };
});
