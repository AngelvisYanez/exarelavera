/**
 * Formato num�rico unificado EXA Presupuesto (2 decimales, separador de miles).
 */
function pptoParseNumber(val) {
    if (val === null || val === undefined || val === '') {
        return 0;
    }
    if (typeof val === 'number') {
        return isNaN(val) ? 0 : val;
    }
    var s = String(val).replace(/\$/g, '').replace(/\+/g, '').replace(/\s/g, '').trim();
    if (s === '') {
        return 0;
    }
    // 1.234.567,89 (es) o 1,234,567.89 (en)
    var lastComma = s.lastIndexOf(',');
    var lastDot = s.lastIndexOf('.');
    if (lastComma > lastDot) {
        s = s.replace(/\./g, '').replace(',', '.');
    } else {
        s = s.replace(/,/g, '');
    }
    var n = parseFloat(s);
    return isNaN(n) ? 0 : n;
}

function formatNumber(val, decimals) {
    decimals = (typeof decimals === 'undefined') ? 2 : decimals;
    var num = pptoParseNumber(val);
    var sign = num < 0 ? '-' : '';
    num = Math.abs(num);
    return sign + num.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function formatCurrency(val) {
    var num = pptoParseNumber(val);
    var sign = num < 0 ? '-' : '';
    num = Math.abs(num);
    return sign + '$' + num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

var PPTO_MESES_NOM = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

function pptoNombreMes(n) {
    var m = parseInt(n, 10);
    return (m >= 1 && m <= 12) ? PPTO_MESES_NOM[m] : String(n);
}
