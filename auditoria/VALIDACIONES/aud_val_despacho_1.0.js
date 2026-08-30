/**
 * Validaciones del módulo Gestión Operativa del Despacho
 * @version 1.0
 */
(function () {
    var $ = window.jQuery || window.$;
    var trim = function (s) { return (s == null) ? '' : String(s).replace(/^\s+|\s+$/g, ''); };

    window.aud_desp_validaServicio = function (form) {
        var nom = trim($(form).find('[name="Ser_Nombre"]').val());
        if (nom.length === 0) {
            $.alert('El nombre del servicio es obligatorio.',null,'warning');
            return false;
        }
        return true;
    };

    window.aud_desp_validaActividad = function (form) {
        var ser = $(form).find('[name="Ser_Cod"]').val();
        var nom = trim($(form).find('[name="Act_Nombre"]').val());
        if (!ser || ser === '') {
            $.alert('Debe seleccionar un servicio.',null,'warning');
            return false;
        }
        if (nom.length === 0) {
            $.alert('El nombre de la actividad es obligatorio.',null,'warning');
            return false;
        }
        return true;
    };

    window.aud_desp_validaContrato = function (form) {
        var dcl = $(form).find('[name="Dcl_Cod"]').val();
        var fecIni = trim($(form).find('[name="Con_Fecha_Inicio"]').val());
        var fecFin = trim($(form).find('[name="Con_Fecha_Fin"]').val());
        if (!dcl || dcl === '') {
            $.alert('Debe seleccionar un cliente del despacho.',null,'warning');
            return false;
        }
        if (fecIni.length === 0) {
            $.alert('La fecha de inicio es obligatoria.',null,'warning');
            return false;
        }
        if (fecFin.length > 0 && fecFin < fecIni) {
            $.alert('La fecha de fin debe ser posterior a la de inicio.',null,'warning');
            return false;
        }
        return true;
    };

    window.aud_desp_validaPeriodo = function (valor) {
        if (!valor) return false;
        var v = trim(String(valor));
        if (v.length === 4) return /^\d{4}$/.test(v);
        if (v.length === 7) return /^\d{4}-\d{2}$/.test(v);
        return false;
    };
})();
