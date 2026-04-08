
//funciones para los filtros 
function setSemanasAnio(tipo) {
    var Rol_I = $('#Rol_I');
    var Rol_F = $('#Rol_F');
    var des = '';

    switch (tipo.value) {
        case 'M':
            des = 'Mes ';
            break;
        case 'Q':
            des = 'Quincena ';
            break;
        default:
            des = 'Semana ';
            break;
    }

    if (tipo.value != "") {
        var frecuencia = calcRolNum(tipo.value);
        Rol_I.html('<option value="" selected="">Seleccione...</option>');
        $.each(frecuencia, function (i, v) { Rol_I.append($('<option value="' + v['Rol_Num'] + '">' + des + v['Rol_Nom'] + '</option>').data(v)); });
        Rol_F.html('<option value="" selected="">Seleccione...</option>');
        $.each(frecuencia, function (i, v) { Rol_F.append($('<option value="' + v['Rol_Num'] + '">' + des + v['Rol_Nom'] + '</option>').data(v)); });
    }
    else {
        Rol_I.html('<option value="" selected="">Seleccione...</option>');
        Rol_F.html('<option value="" selected="">Seleccione...</option>');
    }
};

function calcRolNum(Rol_Tip) {
    var frecuencia = [], year = $('#Pec_Cod option:selected').data('year');
    switch (Rol_Tip) {
        case 'M':
            for (var i = 1, z = 12; i <= z; i++) {
                frecuencia.push({ Rol_Num: i, Rol_Nom: i, Rol_Fei: year + '-' + i.padLeft(2) + '-' + '01', Rol_Fef: year + '-' + i.padLeft(2) + '-' + new Date(year, i, 0).getDate() });
            }
            break;
        case 'Q':
            for (var i = 1, z = 12; i <= z; i++) {
                frecuencia.push({ Rol_Fei: year + '-' + i.padLeft(2) + '-' + '01', Rol_Fef: year + '-' + i.padLeft(2) + '-' + '15' });
                frecuencia.push({ Rol_Fei: year + '-' + i.padLeft(2) + '-' + '16', Rol_Fef: year + '-' + i.padLeft(2) + '-' + new Date(year, i, 0).getDate() });
            }
            $.each(frecuencia, function (i, v) { v['Rol_Num'] = v['Rol_Nom'] = i + 1; });
            break;
        case 'BS':
            var d = isoWeeks(year), j = 1;
            for (var i = 1, z = d.isoWeeks(); i <= z; i = i + 2) {
                frecuencia.push({ Rol_Num: j, Rol_Nom: i + ' - ' + (i + 1), Rol_Fei: d.isoWeek(i).startOf('week').format('YYYY-MM-DD'), Rol_Fef: d.isoWeek(i + 1).endOf('week').format('YYYY-MM-DD') });
                j++;
            }
            break;
        case 'S':
            var d = isoWeeks(year);
            for (var i = 1, z = d.isoWeeks(); i <= z; i++) {
                frecuencia.push({ Rol_Num: i, Rol_Nom: i, Rol_Fei: d.isoWeek(i).startOf('week').format('YYYY-MM-DD'), Rol_FeiF: d.isoWeek(i).startOf('week').format('YYYY-MM-DD'), Rol_Fef: d.isoWeek(i).endOf('week').format('YYYY-MM-DD'), Rol_FefF: d.isoWeek(i).endOf('week').format('YYYY-MM-DD') });
            }
            break;
    }
    return frecuencia;
}

function isoWeeks(year) {
    var d; for (var i = 31; i >= 0; i--) { d = moment(year + '-12-' + i); if (d.isoWeeks() > 10) break; }
    return d;
};

//function validarRango(element) {
    //var inicio = parseInt($('#Rol_I').val());
    //var fin = parseInt($('#Rol_F').val());
    //if (!isNaN(inicio) && !isNaN(fin)) {
        //if (inicio >= fin) {
            //$(element).val('');
        //}
    //}
//}
function validarRango(element) {
    var inicio = parseInt($('#Rol_I').val());
    var fin = parseInt($('#Rol_F').val());
    if (!isNaN(inicio) && !isNaN(fin)) {
        if (inicio > fin) {
            $(element).val('');
        }
    }
}


//tabla del rol en base a plantilla 
$(function () {
    $grid = $('#rol'); $tipo = $('#Rol_Tip');
    proviDialog = $('#proviDetaDialog').createDialogDetail({
        footerrow: true, caption: 'Detalle Provisión ',
        colModel: [
            { label: 'Cód.Int.', name: 'Cam_Cod', key: true, width: 25, align: "center", hidden: true },
            { label: 'Provision ', name: 'Cam_Des', width: 75, classes: 'bgNoColor' },
            { label: 'Valor', name: 'Cam_Val', width: 30, align: 'right', formatter: 'currency', formatoptions: { defaultValue: '' }, summaryType: "sum", summaryRound: 2 }
        ],
        loadComplete: function () { $(this).setGridSummary(['Cam_Val'], { Cam_Val: $.round($(this).jqGrid('getCol', 'Cam_Val', false, 'sum')), Cam_Des: "<div style='text-align:right;'>TOTAL:</div>" }); }
    });
});

var personal = [], detallesDialog = {};
var formulas_base_rol = {
    global: {
        fond_reser: { "operator": "*", "operand1": { "type": "item", "value": "1", "text": "{fond_reser_val_dias}", "variable": "fond_reser_val_dias" }, "operand2": { "operator": "/", "operand1": { "operator": "*", "operand1": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } }, "operand2": { "value": 0, "type": "item", "variable": "fond_porc", "text": "{fond_porc}" } }, "operand2": { "value": "100", "type": "unit" } } },
        deci_terc: { "operator": "/", "operand1": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } }, "operand2": { "value": "12", "type": "unit" } },
        total_rol: { operator: '-', operand1: { type: 'item', value: 0, text: '(total_ing)', variable: 'total_ingr' }, operand2: { type: 'item', value: 0, text: '(total_egr)', variable: 'total_egr' } },
        anti_util: { "operator": "=", "operand1": { "type": "item", "value": "1", "text": "{dias}", "variable": "dias" }, "operand2": { "operator": "?", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "type": "item", "value": "0", "text": "{sueldo_neto_calc}", "variable": "sueldo_neto_calc" }, "operand2": { "type": "item", "value": "0", "text": "{fond_reser_provi}", "variable": "fond_reser_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_cuar_provi}", "variable": "deci_cuar_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_terc_provi}", "variable": "deci_terc_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{sueldo_dias}", "variable": "sueldo_dias" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_terc}", "variable": "deci_terc" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_cuar}", "variable": "deci_cuar" } }, "operand2": { "type": "item", "value": "0", "text": "{fond_reser}", "variable": "fond_reser" } }, "operand2": { "type": "unit", "value": "0" } } },
        desc_util: { "operator": "=", "operand1": { "type": "item", "value": "1", "text": "{dias}", "variable": "dias" }, "operand2": { "operator": "?", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "*", "operand1": { "type": "item", "value": "0", "text": "{sueldo_neto_calc}", "variable": "sueldo_neto_calc" }, "operand2": { "type": "unit", "value": "-1" } }, "operand2": { "type": "item", "value": "0", "text": "{fond_reser_provi}", "variable": "fond_reser_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_cuar_provi}", "variable": "deci_cuar_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_terc_provi}", "variable": "deci_terc_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{sueldo_dias}", "variable": "sueldo_dias" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_terc}", "variable": "deci_terc" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_cuar}", "variable": "deci_cuar" } }, "operand2": { "type": "item", "value": "0", "text": "{fond_reser}", "variable": "fond_reser" } }, "operand2": { "type": "unit", "value": "0" } } },
        tiempo_parcial: {},
        aporte_patronal: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": { "value": 0, "type": "unit" }, "operand2": { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_afiliado}", "variable": "es_afiliado" }, "operand2": { "operator": "?", "operand1": { "operator": "*", "operand1": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "iess_porc_patro", "text": "{iess_porc_patro}" }, "operand2": { "value": "100", "type": "unit" } } }, "operand2": { "type": "unit", "value": "0" } } } } },
        vacacion_rol_p: { "operator": "/", "operand1": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } }, "operand2": { "value": "24", "type": "unit" } },
        aporte_iece: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_afiliado}", "variable": "es_afiliado" }, "operand2": { "operator": "?", "operand1": { "operator": "*", "operand1": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "iess_porc_iece", "text": "{iess_porc_iece}" }, "operand2": { "value": "100", "type": "unit" } } }, "operand2": { "type": "unit", "value": "0" } } }
    },
    M: {
        deci_cuar: { "operator": "*", "operand1": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, "operand2": { "operator": "?", "operand1": { "value": "360", "type": "unit" }, "operand2": { "value": "720", "type": "unit" } } } }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } },
        sueldo_neto_calc: { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_neto", "text": "{sueldo_neto}" }, "operand2": { "value": "1", "type": "unit" } },
        sueldo_dias: { "operator": "*", "operand1": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, "operand2": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, "operand2": { "operator": "?", "operand1": { "value": "30", "type": "unit" }, "operand2": { "value": "60", "type": "unit" } } } }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } },
        tiempo_parcial_rol: { "operator": "*", "operand1": { "operator": "-", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "tie_parc_porc", "text": "{tie_parc_porc}" }, "operand2": { "value": "100", "type": "unit" } } }

    },
    Q: {
        deci_cuar: { "operator": "=", "operand1": { "value": 1, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, "operand2": { "operator": "?", "operand1": { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas_medio", "text": "{sueldo_bas_medio}" }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } }, "operand2": { "value": "360", "type": "unit" } }, "operand2": { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } }, "operand2": { "value": "360", "type": "unit" } } } },
        sueldo_dias: { "operator": "*", "operand1": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, "operand2": { "value": "30", "type": "unit" } }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } },
        sueldo_neto_calc: { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "sueldo_neto", "text": "{sueldo_neto}" }, "operand2": { "value": "2", "type": "unit" } },
        tiempo_parcial_rol: { "operator": "*", "operand1": { "operator": "-", "operand1": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "value": "2", "type": "unit" } }, "operand2": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "tie_parc_porc", "text": "{tie_parc_porc}" }, "operand2": { "value": "100", "type": "unit" } } }
    },
    S: {
        deci_cuar: { "operator": "*", "operand1": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, "operand2": { "operator": "?", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" }, "operand2": { "value": "7", "type": "unit" } }, "operand2": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" }, "operand2": { "value": "14", "type": "unit" } } } } }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } },
        sueldo_dias: { "operator": "*", "operand1": { "operator": "/", "operand1": { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, "operand2": { "value": "12", "type": "unit" } }, "operand2": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" } }, "operand2": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, "operand2": { "operator": "?", "operand1": { "value": "7", "type": "unit" }, "operand2": { "value": "14", "type": "unit" } } } }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } },
        sueldo_neto_calc: { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_neto", "text": "{sueldo_neto}" }, "operand2": { "value": 12, "type": "unit" } }, "operand2": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" } },
        tiempo_parcial_rol: { "operator": "*", "operand1": { "operator": "-", "operand1": { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "value": "12", "type": "unit" } }, "operand2": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" } }, "operand2": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "tie_parc_porc", "text": "{tie_parc_porc}" }, "operand2": { "value": "100", "type": "unit" } } }
    },
    BS: {
        deci_cuar: { "operator": "*", "operand1": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, "operand2": { "operator": "?", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" }, "operand2": { "value": "7", "type": "unit" } }, "operand2": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" }, "operand2": { "value": "14", "type": "unit" } } } } }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } },
        sueldo_dias: { "operator": "*", "operand1": { "operator": "/", "operand1": { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, "operand2": { "value": "12", "type": "unit" } }, "operand2": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" } }, "operand2": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, "operand2": { "operator": "?", "operand1": { "value": "7", "type": "unit" }, "operand2": { "value": "14", "type": "unit" } } } }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } },
        sueldo_neto_calc: { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_neto", "text": "{sueldo_neto}" }, "operand2": { "value": 12, "type": "unit" } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" }, "operand2": { "value": "2", "type": "unit" } } },
        tiempo_parcial_rol: { "operator": "*", "operand1": { "operator": "-", "operand1": { "operator": "*", "operand1": { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "value": "12", "type": "unit" } }, "operand2": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" } }, "operand2": { "value": "2", "type": "unit" } }, "operand2": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "tie_parc_porc", "text": "{tie_parc_porc}" }, "operand2": { "value": "100", "type": "unit" } } }
    }
};
function createFormAuto(tipo) {
    var formula;
    $.each(fields, function (i, v) {
        if (v['Cam_Tip'] === tipo) {
            if (!$.isEmpty(formula)) {
                formula = { operator: '+', operand1: { type: "item", variable: v['Cam_Var'] }, operand2: formula };
            } else formula = { operator: '+', operand1: { type: "item", variable: v['Cam_Var'] }, operand2: { type: "unit", value: 0 } };
        }
    });
    return formula;
}
function updateTotales(edit) {
    var gridR = $('#rol'), cols = [], inputs = [], def = { saldo: '' }, provi = [];
    $.each(fields, function (i, v) {
        if (edit === true) fields[i]['edit'] = true;
        if (v['Cam_Tip'] === 'P') provi.push({ Cam_Cod: v['Cam_Cod'], Cam_Var: v['Cam_Var'], Cam_Des: v['Cam_Des'] });
        if (v['Cam_Req'] === 'S' && (edit === true || v['edit'] === true)) { inputs.push(v['Cam_Var']); def[v['Cam_Var']] = 0; }
        else cols.push(v['Cam_Var']);
    });
    $.each(inputs, function (i, v) {
        if ($.varValid(personal)) $.each(personal, function (j, w) { def[v] = def[v] + gridR.find('tr#' + w['Con_Cod']).find('input#' + w['Con_Cod'] + '_' + v).val() * 1; });
    }); def['dias'] = '';
    cols.push('saldo_rol');
    $.each(cols, function (i, v) { def[v] = $.round(gridR.jqGrid('getCol', v, false, 'sum')); });
    if (proviDialog.length === 1) {
        for (var i = 0, z = provi.length; i < z; i++)
            provi[i]['Cam_Val'] = def[provi[i]['Cam_Var']];
        proviDialog.getDialogGrid().setRows(provi);
    }
    gridR.jqGrid('footerData', 'set', $.extend({ Tic_Des: '<div style="text-align:right">TOTALES:</div>' }, def)); delete def['dias'];
    if ($.isset('updateTotalesExtras')) updateTotalesExtras();
    return def;
}
function setRolGridCaption(caption) { return caption; }

function createGrid(grid, header) {
    grid['caption'] = setRolGridCaption(grid['caption']);
    if ($('#rol')[0].grid) { $.jgrid.gridUnload('#rol'); }
    $grid = $("#rol").createGrid($.extend({ height: 300, pager: '#rolPager' }, grid), true);
    $grid.navGrid('#rolPager', { edit: false, add: false, del: false, search: false, refresh: false, view: true, position: "left", cloneToTop: false }, null, null, null, null, { beforeInitData: OpenViewRol, onClose: CloseViewRol, beforeShowForm: beforeViewRol, closeOnEscape: true });
    $grid.setGroupHeaders(header);
    if (grid['button_provi'] !== false) $grid.gridButtonAdd({ caption: 'Ver Provisiones', buttonicon: "glyphicon glyphicon-eye-open", onClickButton: function () { proviDialog.dialog('open'); } });
    $('#rolPager').find('.ui-pg-table table.navtable').find('td.ui-pg-button.ui-corner-all').unbind('mouseenter mouseleave').removeClass('ui-pg-button').addClass('btn btn-xs btn-success').find('.ui-pg-div span').removeClass('ui-icon').addClass('glyphicon');
}
function beforeViewRol(table) {
    $("<tr><td colspan='2' style='font-weight:bold;text-align:center;border-top: 1px solid #101010; border-bottom: 1px solid #101010;'>-- INGRESOS --</td></tr>").insertAfter(table.find("table tbody tr#trv_dias"));
    $("<tr><td colspan='2' style='font-weight:bold;text-align:center;border-top: 1px solid #101010; border-bottom: 1px solid #101010;'>-- EGRESOS --</td></tr>").insertAfter(table.find("table tbody tr#trv_total_ingr"));
    $("<tr><td colspan='2' style='font-weight:bold;text-align:center;border-top: 1px solid #101010; border-bottom: 1px solid #101010;'>-- PROVISIONES --</td></tr>").insertAfter(table.find("table tbody tr#trv_total_rol"));
    table.find("table tbody").find("tr#trv_total_ingr td,tr#trv_total_egr td").css({ "border-top": "1px solid #d39595", "background": "#eceae7" }).end().find("tr#trv_total_rol").css({ "border-top": "1px solid #101010" }).find('td').css({ "background": "#c5ffff" });
    table.css({ height: 300, "border-bottom": "1px solid #101010" }).find("table tbody").prepend($("<tr><td colspan='2' style='font-weight:bold;text-align:center;border-top: 1px solid #101010; border-bottom: 1px solid #101010;'>-- CONTRATO --</td></tr>")).end().parent().parent().find('.navButton').hide();
}
function OpenViewRol() { OpenCloseViewRol('open'); }

function CloseViewRol() { OpenCloseViewRol('close'); }

function OpenCloseViewRol(tipo) {
    var fId = $grid[0].p.selrow, row = $grid.find("tr[role=row]#" + fId);
    if (tipo === 'open') {
        if (!!row.attr('editable')) { row.attr('estabaEditando', 1); $grid.jqGrid('saveRow', fId, false, 'clientArray'); }
    } else {
        if (!!row.attr('estabaEditando')) { row.removeAttr('estabaEditando'); $grid.jqGrid('editRow', fId); }
    }
}

function updateFormulasRol() {
    if (!$.vv($('#Rol_Tip').val())) return;
    var formulas_base = $.extend(true, [], formulas_base_rol['global'], formulas_base_rol[$('#Rol_Tip').val()]);
    var formulas_rol = {
        anti_util: formulas_base['anti_util'],
        desc_util: formulas_base['desc_util'],
        sueldo_neto_calc: formulas_base['sueldo_neto_calc'],
        sueldo_dias: formulas_base['sueldo_dias'],
        deci_terc: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": { "value": 0, "type": "unit" }, "operand2": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "deci_terc_acum", "text": "{deci_terc_acum}" }, "operand2": { "operator": "?", "operand1": formulas_base['deci_terc'], "operand2": { "value": 0, "type": "unit" } } } } },
        deci_cuar: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": { "value": 0, "type": "unit" }, "operand2": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "deci_cuar_acum", "text": "{deci_cuar_acum}" }, "operand2": { "operator": "?", "operand1": formulas_base['deci_cuar'], "operand2": { "value": 0, "type": "unit" } } } } },
        fond_reser: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": { "value": 0, "type": "unit" }, "operand2": { "operator": "=", "operand1": { "value": 1, "type": "item", "variable": "fond_reser_anio", "text": "{fond_reser_anio}" }, "operand2": { "operator": "?", "operand1": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "fond_reser_acum", "text": "{fond_reser_acum}" }, "operand2": { "operator": "?", "operand1": formulas_base['fond_reser'], "operand2": { "value": 0, "type": "unit" } } }, "operand2": { "value": 0, "type": "unit" } } } } },
        deci_terc_provi: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": { "value": 0, "type": "unit" }, "operand2": { "operator": "=", "operand1": { "value": 1, "type": "item", "variable": "deci_terc_acum", "text": "{deci_terc_acum}" }, "operand2": { "operator": "?", "operand1": formulas_base['deci_terc'], "operand2": { "value": 0, "type": "unit" } } } } },
        deci_cuar_provi: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": { "value": 0, "type": "unit" }, "operand2": { "operator": "=", "operand1": { "value": 1, "type": "item", "variable": "deci_cuar_acum", "text": "{deci_cuar_acum}" }, "operand2": { "operator": "?", "operand1": formulas_base['deci_cuar'], "operand2": { "value": 0, "type": "unit" } } } } },
        fond_reser_provi: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": { "value": 0, "type": "unit" }, "operand2": { "operator": "=", "operand1": { "value": 1, "type": "item", "variable": "fond_reser_anio", "text": "{fond_reser_anio}" }, "operand2": { "operator": "?", "operand1": { "operator": "=", "operand1": { "value": 1, "type": "item", "variable": "fond_reser_acum", "text": "{fond_reser_acum}" }, "operand2": { "operator": "?", "operand1": formulas_base['fond_reser'], "operand2": { "value": 0, "type": "unit" } } }, "operand2": { "value": 0, "type": "unit" } } } } },
        iess: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_afiliado}", "variable": "es_afiliado" }, "operand2": { "operator": "?", "operand1": { "operator": "/", "operand1": { "operator": "*", "operand1": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } }, "operand2": { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": { "type": "item", "value": "0", "text": "{iess_porc_emple}", "variable": "iess_porc_emple" }, "operand2": { "type": "item", "value": "0", "text": "{iess_porc}", "variable": "iess_porc" } } } }, "operand2": { "type": "unit", "value": "100" } }, "operand2": { "type": "unit", "value": "0" } } },
        total_rol: formulas_base['total_rol'],
        tiempo_parcial_rol: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_afiliado}", "variable": "es_afiliado" }, "operand2": { "operator": "?", "operand1": { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{tiempo_parcial}", "variable": "tiempo_parcial" }, "operand2": { "operator": "?", "operand1": formulas_base['tiempo_parcial_rol'], "operand2": { "type": "unit", "value": "0" } } }, "operand2": { "type": "unit", "value": "0" } } },
        aporte_iece: { "operator": "=", "operand1": { "type": "item", "value": "0.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": formulas_base['aporte_iece'], "operand2": { "value": 0, "type": "unit" } } },
        aporte_patronal: formulas_base['aporte_patronal'],
        vacacion_rol_p: { "operator": "=", "operand1": { "type": "item", "value": "0.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": formulas_base['vacacion_rol_p'], "operand2": { "value": 0, "type": "unit" } } }
    };
    $.each(fields, function (i, v) {
        if ($.vv(formulas_rol[v['Cam_Var']])) {
            fields[i]['Cam_For'] = formulas_rol[v['Cam_Var']];
        } else
            if ((v['Cam_Var'] === 'total_ingr' || v['Cam_Var'] === 'total_egr') && !$.vv(v['Cam_For'])) {
                fields[i]['Cam_For'] = createFormAuto(v['Cam_Var'] === 'total_ingr' ? 'I' : 'E');
            }
    });
}

function setFondoReservaConfig(rol) {
    var dias = $tipo.find('option:selected').data('dias');
    var fond = { fond_reser_anio: 0, fond_reser_val_dias: 1 };
    if (dias == undefined) return fond;
    var Rol_Fei = $('input[name=Rol_Fei]').val(), Rol_Fef = $('input[name=Rol_Fef]').val()
    Afi_Fei = rol['Afi_Fei'], Afi_Fef = rol['Afi_Fef'], Afi_Anio_Real = moment(Afi_Fei).add(365, "days").format("YYYY-MM-DD");

    if (Rol_Fef.length === 10 && $.varValid(rol['Afi_Fei']) && rol['Afi_Fei'].length === 10) {
        fond['fond_reser_anio'] = 0;
        if (moment(Rol_Fef).diff(moment(Afi_Fei), 'days') >= 365) {
            fond['fond_reser_anio'] = 1;
            if (Afi_Anio_Real > Rol_Fei && Afi_Anio_Real < Rol_Fef) {
                fond['fond_reser_val_dias'] = (dias - moment(Afi_Anio_Real).diff(Rol_Fei, 'days')) / dias;
            } else
                if (Afi_Fef > Rol_Fei && Afi_Fef < Rol_Fef) {
                    fond['fond_reser_val_dias'] = (dias - moment(Afi_Fef).diff(Rol_Fei, 'days')) / dias;
                }
        }
    }
    return fond;
}

function fillRoles() {
    updateFormulasRol();
    var val = $tipo.find('option:selected').data('dias'), Afi_Fei = $('input[name=Rol_Fei]').val(), Afi_Fef = $('input[name=Rol_Fef]').val(), roles = $.extend(true, [], personal);
    for (var j = 0, z = roles.length; j < z; j++) {
        roles[j] = $.extend(roles[j], setFondoReservaConfig(roles[j]), defaults, { dias: val * 1 });
        roles[j] = calcCampo(roles[j]);
        roles[j]['anticipo_val'] = roles[j]['total_rol'];
    }
    if (roles.length > 0) $('#rol').setRows(roles).startGridEdit(); else $('#rol').clearGrid();
    updateTotales();
}

function recreateGrid(id) {
    $.getDataJson("", { getPlantilla: true, Map_Cod: id }, function (response) {
        createGrid(response['grid'], response['header']);
        fields = response['rol'];
        //if($.vv(response['rol_config']))$('#Rol_Tip').val(response['rol_config']['Map_Tip']).trigger('change');
        fillRoles();
        if (response['grid']['footerrow']) updateTotales();
    });
}


function detallarRoles() {

    var formDataArray = $('#formRol').serializeArray();
    var data = {};
    formDataArray.forEach(function (item) {
        data[item.name] = item.value;
    });
    //$('.exportRoles').data('originaldata',data);             
    //$('.detalle').setData(data);
    var semanas = semanas = ($.vv(data['anio']) ? isoWeeks(data['anio']).isoWeeks() : 52);
    data['semanas'] = semanas;
    $.getDataJson("", $.extend(data, { getRolDetail: true }), function (response) {
        createGrid(response['grid'], response['header']);
        $grid.setRows(response['personal']);
        fields = response['rol'];
        updateFormulasRol();
        if (response['edit'] !== false) $grid.startGridEdit();
        personal = response['personal'];
        $.each(personal, function (i, v) {
            personal[i]['semanas'] = semanas;
        });

        //$('#main-search').moveComp('#rol-sdetail').updateGridsSizes();   
        //if($.isset('detallarExtras')) detallarExtras(data,response);
        updateTotales(response['edit'] !== false);

    });
}

function campoGlobal(cam_var) { var gl = { anticipos_rol_p: 'Ant_Val', descuentos_rol_p: 'Ant_Val', prestamos_rol_p: 'Pre_Val', abonos_rol_p: 'Ant_Val' }; return $.isString(cam_var) ? gl[cam_var] : gl; }
function verDetalleInfos(v) {
    var data = $.arrayGetItem(personal, 'Con_Cod', v['Con_Cod']), tipos = campoGlobal();
    $.each(tipos, function (k, v) {
        if ($.vv(detallesDialog[k]) && detallesDialog[k].length > 0) {
            detallesDialog[k].setRows(data[k]);
        }
    });
    $('#detaPersona').val(v['persona']);
    detallesDialog['dialog'].dialog('open');
}

$.moneyCellUnformat = function (cv, opts, el) { return $.numUnformat($(el).text(), 'currency'); };
$.numberCellUnformat = function (cv, opts, el) { return $.numUnformat($(el).text(), 'number'); };
$.fn.fmatter.noNegative = function (cv, opts, cObjt) { if (cv * 1 < 0) return '0.00'; else return cv; };
$.fn.fmatter.saldos = function (cv, opts, cObjt) { return $.numFormat($.varValid(cv) ? cv : cObjt['total_rol'], 'currency'); };
$.fn.fmatter.saldos.unformat = $.moneyCellUnformat;
$.fn.fmatter.numeric = function (cv, opts, cObjt) {
    if (Array.isArray(cv)) {
        var ant = cObjt[opts['colModel']['name']], cam = campoGlobal(opts['colModel']['name']), tot = 0;
        if (!$.varValid(cam)) return '0.00';
        $.each(ant, function (i, v) { tot += (v[cam] * 1); });
        return $.numFormat(tot, 'number');
    } return $.numFormat(cv, 'number');
};
$.fn.fmatter.numeric.unformat = $.numberCellUnformat;
$.fn.fmatter.gridButtonInfos = function (cv, opts, cObjt) {
    var o = $.cloneCO(opts, cObjt), ban = false, tipos = campoGlobal(), data = {}, ant = o['anticipos_rol_p'], tot = 0, f = opts.colModel.formatoptions || {};
    $.each(tipos, function (k, v) {
        if ($.isArray(o[k]) && o[k].length > 0) {
            if (!ban) { ban = true; return false; }
        }
    });
    if (ban) {
        return $.getGridButton(verDetalleInfos, { Con_Cod: cObjt['Con_Cod'], persona: cObjt['Prs_Ape'] + ' ' + cObjt['Prs_Nom'] }, 'Ver Detalles', 'glyphicon glyphicon-eye-open', null, 'info', null, { tabindex: '-1' });
    } else return '<i class="glyphicon glyphicon-remove blue" title="No Tiene Registros"></i>';
};
$.fn.fmatter.gridButtonInfos.unformat = $.unformatCellHtml;
$.fn.fmatter.printRolIndFormater = function (cellvalue, options, rowObject) {
    return $.getGridButton(printRolDetailIndiv, { Con_Cod: rowObject.Con_Cod, Rol_Cod: rowObject.Rol_Cod }, 'Imprimir Rol Ind.', 'print', null, 'info');
};
$.fn.fmatter.descargarRolIndFormater = function (cellvalue, options, rowObject) {
    return $.getGridButton(exportRolDetailIndiv, { Con_Cod: rowObject.Con_Cod, Rol_Cod: rowObject.Rol_Cod }, 'Descargar Excel', 'download', null, 'info');
};


function printRolesIndividualGrupal(data) {
    var formDataArray = $('#formRol').serializeArray();
    var data = {};
    formDataArray.forEach(function (item) {
        data[item.name] = item.value;
    });
    $.getDataJson('', $.extend(true, {}, data, { printRolIndGrupAjax: true, print: true }), function (r) {
        $('#imprimirRoles').html(r['tabla']).printElement({ pageTitle: 'EXA - Sofware Contable', printMode: 'iframe', leaveOpen: true });
    });
}

function exportRolesIndividualGrupal(data) {
    var formDataArray = $('#formRol').serializeArray();
    var data = {};
    formDataArray.forEach(function (item) {
        data[item.name] = item.value;
    });
    $.getDataJson('', $.extend(true, {}, data, {
        printRolIndGrupAjax: true
    }), function (r) {
        $.exportBooksExcel($(r['tabla']).tableToXlsWorksheetsArray({ nombre: 'ROL_IND' }));
    });
}



function printRoles(data) {
    var formDataArray = $('#formRol').serializeArray();
    var data = {};
    formDataArray.forEach(function (item) {
        data[item.name] = item.value;
    });
    // console.log(data);
    $.getDataJson('', $.extend(true, {}, data, { printAjax: true, print: true }), function (r) {
        $('#imprimirRoles').html(r['tabla']).printElement({ pageTitle: 'EXA - Sofware Contable', printMode: 'iframe', leaveOpen: true });
    });
}



function exportRoles(data) {
    var formDataArray = $('#formRol').serializeArray();
    var data = {};
    formDataArray.forEach(function (item) {
        data[item.name] = item.value;
    });
    console.log(data);
    $.getDataJson('', $.extend(true, {}, data, { printAjax: true }), function (r) { $.downloadFile($(r['tabla']).exportarExcelBlob('Roles'), 'ROLES-' + $.getDate() + '.xls'); });
}




function printRolDetailIndiv(data) { $.getDataJson('', $.extend(true, {}, data, { printRolIndAjax: true, print: true }), function (r) { $('#imprimirRoles').html(r['tabla']).printElement({ pageTitle: 'EXA - Sofware Contable' }); }); }
function exportRolDetailIndiv(data) { $.getDataJson('', $.extend(true, {}, data, { printRolIndAjax: true }), function (r) { $.exportBooksExcel($(r['tabla']).tableToXlsWorksheetsArray({ nombre: 'ROL_IND' })); }); }
function exportRolesIndiv(data) { $.getDataJson('', $.extend(true, {}, data, { printRolIndAjax: true }), function (r) { $.exportBooksExcel($(r['tabla']).tableToXlsWorksheetsArray({ nombre: 'ROLES_IND' })); }); }


