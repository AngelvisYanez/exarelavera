/**
 * Validaciones del módulo Dashboard de Tareas (Control de Personal)
 * Creación de tarea, asignación y registro de avances.
 * Compatible con o sin jQuery.
 * @version 1.0
 */
(function () {
    var $ = window.jQuery || window.$;
    var trim = function (s) { return (s == null) ? '' : String(s).replace(/^\s+|\s+$/g, ''); };
    var findVal = function (form, name) {
        var el = form && form.querySelector ? form.querySelector('[name="' + name + '"]') : null;
        return el ? (el.value || '') : '';
    };
    var run = function () {

    /**
     * Valida el formulario de creación de tarea
     * Campos requeridos: título, fecha inicio. La fecha de fin la establece el usuario asignado.
     */
    window.aud_validaCrearTarea = function (form) {
        var titulo = trim(findVal(form, 'Tar_Titulo'));
        var fecIni = trim(findVal(form, 'Tar_Fecha_Inicio'));
        if (titulo.length === 0) {
            alert('El título de la tarea es obligatorio.');
            var el = form.querySelector('[name="Tar_Titulo"]'); if (el) el.focus();
            return false;
        }
        if (fecIni.length === 0) {
            alert('La fecha de inicio es obligatoria (la registra quien asigna la tarea).');
            var el = form.querySelector('[name="Tar_Fecha_Inicio"]'); if (el) el.focus();
            return false;
        }
        return true;
    };

    /**
     * Valida la asignación de tarea a empleado
     */
    window.aud_validaAsignacion = function (tarCod, usuCod) {
        if (!tarCod || tarCod === '' || tarCod === '0') {
            alert('Debe seleccionar una tarea.');
            return false;
        }
        if (!usuCod || usuCod === '' || usuCod === '0') {
            alert('Debe seleccionar un empleado.');
            return false;
        }
        return true;
    };

    /**
     * Valida el registro de avance (porcentaje 0-100 y descripción recomendada)
     */
    window.aud_validaAvance = function (form) {
        var porc = trim(findVal(form, 'Ava_Porcentaje'));
        var desc = trim(findVal(form, 'Ava_Descripcion'));
        var tarCod = findVal(form, 'Tar_Cod');
        if (!tarCod || tarCod === '') {
            alert('No se ha indicado la tarea.');
            return false;
        }
        if (porc === '') {
            alert('El porcentaje de avance es obligatorio.');
            var el = form.querySelector('[name="Ava_Porcentaje"]'); if (el) el.focus();
            return false;
        }
        var n = parseInt(porc, 10);
        if (isNaN(n) || n < 0 || n > 100) {
            alert('El porcentaje debe ser un número entre 0 y 100.');
            var el = form.querySelector('[name="Ava_Porcentaje"]'); if (el) el.focus();
            return false;
        }
        if (desc.length === 0) {
            if (!confirm('¿Desea guardar el avance sin descripción?')) {
                return false;
            }
        }
        return true;
    };

    /**
     * Muestra mensaje claro de éxito o error (helper)
     */
    window.aud_mensaje = function (texto, esError) {
        if (esError) {
            alert(texto);
        } else {
            if (typeof alert === 'function') alert(texto);
        }
    };
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
