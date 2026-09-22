/**
 * Parámetros y constantes del módulo Dashboard de Tareas (Control de Personal)
 * Estados, prioridades, constantes globales y helpers.
 * @version 1.0
 */
$(function () {

    // --- Estados de tarea ---
    window.AUD_TAR_ESTADOS = {
        'Pendiente': 'Pendiente',
        'En Proceso': 'En Proceso',
        'Pausada': 'Pausada',
        'Finalizada': 'Finalizada'
    };

    // --- Prioridades ---
    window.AUD_TAR_PRIORIDADES = {
        'Alta': 'Alta',
        'Media': 'Media',
        'Baja': 'Baja'
    };

    // --- Constantes ---
    window.AUD_TAR_EST_ACTIVO = 'A';
    window.AUD_AVA_PORC_MIN = 0;
    window.AUD_AVA_PORC_MAX = 100;

    /**
     * Devuelve opciones HTML para select de estados
     */
    window.aud_getOptionsEstados = function (seleccionado) {
        var opts = '<option value="">-- Estado --</option>';
        $.each(AUD_TAR_ESTADOS, function (k, v) {
            opts += '<option value="' + v + '"' + (v === seleccionado ? ' selected' : '') + '>' + v + '</option>';
        });
        return opts;
    };

    /**
     * Devuelve opciones HTML para select de prioridades
     */
    window.aud_getOptionsPrioridades = function (seleccionado) {
        var opts = '<option value="">-- Prioridad --</option>';
        $.each(AUD_TAR_PRIORIDADES, function (k, v) {
            opts += '<option value="' + v + '"' + (v === seleccionado ? ' selected' : '') + '>' + v + '</option>';
        });
        return opts;
    };

    /**
     * Valida que el porcentaje de avance esté entre 0 y 100
     */
    window.aud_validaPorcentaje = function (valor) {
        var n = parseInt(valor, 10);
        if (isNaN(n)) return false;
        return n >= AUD_AVA_PORC_MIN && n <= AUD_AVA_PORC_MAX;
    };

    /**
     * Formatea fecha para mostrar (yyyy-mm-dd -> dd/mm/yyyy si se desea)
     */
    window.aud_formatoFecha = function (fecha) {
        if (!fecha) return '';
        return fecha;
    };
});
