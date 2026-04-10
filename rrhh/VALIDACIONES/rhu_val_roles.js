
var personal = [], fields = [], rolOmitirColsPersist = [], rolOmitirColCeroPersist = false, defaults = {}, $grid, $tipo, gridComp, gridExtras, proviDialog, detallesDialog = {},
    groupAnioArea = {
        groupField: ["Anio", "Are_Des"], groupColumnShow: [false, false],
        groupText: ["<div><span style='float:left;'><b> &nbsp;-&nbsp; Periodo {0} &nbsp;-&nbsp; </b></span><span style='float:right;'> {1} Area(s)</span></div>", "<div><span style='float:left;'> <b> &nbsp;&nbsp;Area: {0} &nbsp;&nbsp; </b> </span><span style='float:right;'> {1} Rol(es)</span></div>"],
        groupOrder: ["desc", "asc"], groupSummary: [false], groupCollapse: false
    },
    groupAnio = {
        groupField: ["Anio"], groupColumnShow: [false],
        groupText: ["<div><span style='float:left;'><b> &nbsp;-&nbsp; Periodo {0} &nbsp;-&nbsp; </b></span><span style='float:right;'> {1} Area(s)</span></div>"],
        groupOrder: ["desc"], groupSummary: [false], groupCollapse: false
    },
    groupArea = {
        groupField: ["Are_Des"], groupColumnShow: [false],
        groupText: ["<div><span style='float:left;'> <b> &nbsp;&nbsp;Area: {0} &nbsp;&nbsp; </b> </span><span style='float:right;'> {1} Rol(es)</span></div>"],
        groupOrder: ["asc"], groupSummary: [false], groupCollapse: false
    };
var formulas_base_rol = {
    global: {
        //fond_reser: { "operator": "", "operand1": { "type": "item", "value": "1", "text": "{fond_reser_val_dias}", "variable": "fond_reser_val_dias" }, "operand2": { "operator": "/", "operand1": { "operator": "", "operand1": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } }, "operand2": { "value": 0, "type": "item", "variable": "fond_porc", "text": "{fond_porc}" } }, "operand2": { "value": "100", "type": "unit" } } },
        fond_reser: { "operator": "/","operand1": {"operator": "*","operand1": {"operator": "+","operand1": {"value": 0,"type": "item","variable": "sueldo_dias","text": "{sueldo_dias}"}, "operand2": {"value": 0,"type": "item","variable": "aporte_extras_rol_p","text": "{aporte_extras_rol_p}"}}, "operand2": {"value": 0, "type": "item", "variable": "fond_porc", "text": "{fond_porc}" } }, "operand2": { "value": "100", "type": "unit" } },
        deci_terc: { 
            "operator": "/", 
            "operand1": { 
                "operator": "+", 
                "operand1": { 
                    "value": 0, 
                    "type": "item", 
                    "variable": "sueldo_dias", 
                    "text": "{sueldo_dias}" }, 
                "operand2": { 
                    "value": 0, 
                    "type": "item", 
                    "variable": "aporte_extras_rol_p", 
                    "text": "{aporte_extras_rol_p}" 
                } 
            }, 
            "operand2": { 
                "value": "12", 
                "type": "unit" 
            } 
        },
        total_rol: { operator: '-', operand1: { type: 'item', value: 0, text: '(total_ing)', variable: 'total_ingr' }, operand2: { type: 'item', value: 0, text: '(total_egr)', variable: 'total_egr' } },
        anti_util: { "operator": "=", "operand1": { "type": "item", "value": "1", "text": "{dias}", "variable": "dias" }, "operand2": { "operator": "?", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "operator": "-", "operand1": { "type": "item", "value": "0", "text": "{sueldo_neto_calc}", "variable": "sueldo_neto_calc" }, "operand2": { "type": "item", "value": "0", "text": "{fond_reser_provi}", "variable": "fond_reser_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_cuar_provi}", "variable": "deci_cuar_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_terc_provi}", "variable": "deci_terc_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{sueldo_dias}", "variable": "sueldo_dias" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_terc}", "variable": "deci_terc" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_cuar}", "variable": "deci_cuar" } }, "operand2": { "type": "item", "value": "0", "text": "{fond_reser}", "variable": "fond_reser" } }, "operand2": { "type": "unit", "value": "0" } } },
        desc_util: { "operator": "=", "operand1": { "type": "item", "value": "1", "text": "{dias}", "variable": "dias" }, "operand2": { "operator": "?", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "+", "operand1": { "operator": "*", "operand1": { "type": "item", "value": "0", "text": "{sueldo_neto_calc}", "variable": "sueldo_neto_calc" }, "operand2": { "type": "unit", "value": "-1" } }, "operand2": { "type": "item", "value": "0", "text": "{fond_reser_provi}", "variable": "fond_reser_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_cuar_provi}", "variable": "deci_cuar_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_terc_provi}", "variable": "deci_terc_provi" } }, "operand2": { "type": "item", "value": "0", "text": "{sueldo_dias}", "variable": "sueldo_dias" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_terc}", "variable": "deci_terc" } }, "operand2": { "type": "item", "value": "0", "text": "{deci_cuar}", "variable": "deci_cuar" } }, "operand2": { "type": "item", "value": "0", "text": "{fond_reser}", "variable": "fond_reser" } }, "operand2": { "type": "unit", "value": "0" } } },
        tiempo_parcial: {},
        aporte_patronal: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": { "value": 0, "type": "unit" }, "operand2": { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_afiliado}", "variable": "es_afiliado" }, "operand2": { "operator": "?", "operand1": { "operator": "*", "operand1": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "iess_porc_patro", "text": "{iess_porc_patro}" }, "operand2": { "value": "100", "type": "unit" } } }, "operand2": { "type": "unit", "value": "0" } } } } },
        vacacion_rol_p: { "operator": "/", "operand1": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } }, "operand2": { "value": "24", "type": "unit" } },
        aporte_iece: { "operator": "=", "operand1": { "type": "item", "value": "1.0000", "text": "{es_afiliado}", "variable": "es_afiliado" }, "operand2": { "operator": "?", "operand1": { "operator": "*", "operand1": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "iess_porc_iece", "text": "{iess_porc_iece}" }, "operand2": { "value": "100", "type": "unit" } } }, "operand2": { "type": "unit", "value": "0" } } }
    },

    /***** ROLES MENSUALES *****/
    M: {
        deci_cuar: { "operator": "*", "operand1": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, "operand2": { "operator": "?", "operand1": { "value": "360", "type": "unit" }, "operand2": { "value": "720", "type": "unit" } } } }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } },
        sueldo_neto_calc: { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_neto", "text": "{sueldo_neto}" }, "operand2": { "value": "1", "type": "unit" } },

       /* sueldo_dias: { 
            "operator": "*", 
            "operand1": { 
                "operator": "/",
                 "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, 
                 "operand2": { 
                    "operator": "=", 
                    "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, 
                    "operand2": { 
                        "operator": "?", 
                        "operand1": { "value": "30", "type": "unit" }, 
                        "operand2": { "value": "60", "type": "unit" } 
                    } 
                } 
            }, 
            "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } 
        },*/
        /* Jose Cumbicos - Se cambio el metodo de calculo, desde ahora se tomara encuenta Numero de horas especificadas en contrato  */
        sueldo_dias: { 
            "operator": "*", 
            "operand1": { 
                "operator": "*",
                "operand1": { 
                    "operator": "/",
                    "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, 
                    "operand2": { 
                        "operator": "=", 
                        "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, 
                        "operand2": { 
                            "operator": "?", 
                            "operand1": { "value": "240", "type": "unit" }, 
                            "operand2": { "value": "240", "type": "unit" } 
                        } 
                    }
                },
                "operand2": { "value": 0, "type": "item", "variable": "Ded_Hrs", "text": "{Ded_Hrs}" }, 
            }, 
            "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" }
        },
        
        tiempo_parcial_rol: {
            "operator": "*",
            "operand1": {
                "operator": "-",
                "operand1": {
                    "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}"
                },
                "operand2": {
                    "operator": "+",
                    "operand1": {
                        "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}"
                    },
                    "operand2": {
                        "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}"
                    }
                }
            }, "operand2": {
                "operator": "/", 
                "operand1": {
                    "value": 0, "type": "item", "variable": "tie_parc_porc", "text": "{tie_parc_porc}"
                },
                "operand2": {
                    "value": "100", "type": "unit"
                }
            }
        }
    },

    Q: { /***** ROLES QUINCENA *****/
        deci_cuar: { "operator": "=", "operand1": { "value": 1, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, "operand2": { "operator": "?", "operand1": { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas_medio", "text": "{sueldo_bas_medio}" }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } }, "operand2": { "value": "360", "type": "unit" } }, "operand2": { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } }, "operand2": { "value": "360", "type": "unit" } } } },
        sueldo_neto_calc: { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "sueldo_neto", "text": "{sueldo_neto}" }, "operand2": { "value": "2", "type": "unit" } },

        // sueldo_dias:{"operator":"*","operand1":{"operator":"/","operand1":{"value":0,"type":"item","variable":"sueldo","text":"{sueldo}"},"operand2":{"value":"30","type":"unit"}},"operand2":{"value":0,"type":"item","variable":"dias","text":"{dias}"}},
        //Calcular el sueldo de la 15cena (WB)

        /*sueldo_dias: {
            "operator": "*",
            "operand1": {
                "operator": "/",
                "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" },
                "operand2": {
                    "operator": "=",
                    "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" },
                    "operand2": {
                        "operator": "?",            
                        "operand1": { "value": "30", "type": "unit" },
                        "operand2": { "value": "60", "type": "unit" }
                    }
                }
            },
            "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" }
        },*/

        sueldo_dias: { 
            "operator": "*", 
            "operand1": { 
                "operator": "*",
                "operand1": { 
                    "operator": "/",
                    "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, 
                    "operand2": { 
                        "operator": "=", 
                        "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, 
                        "operand2": { 
                            "operator": "?", 
                            "operand1": { "value": "240", "type": "unit" }, 
                            "operand2": { "value": "240", "type": "unit" } 
                        } 
                    }
                },
                "operand2": { "value": 0, "type": "item", "variable": "Ded_Hrs", "text": "{Ded_Hrs}" }, 
            }, 
            "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" }
        },

        
        tiempo_parcial_rol: { 
            "operator": "*", 
            "operand1": { 
                "operator": "-", 
                "operand1": { 
                    "operator": "/", 
                    "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, 
                    "operand2": { "value": "2", "type": "unit" } 
                }, "operand2": { 
                    "operator": "+", 
                    "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, 
                    "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } 
                } 
            }, 
            "operand2": { 
                "operator": "/", 
                "operand1": { "value": 0, "type": "item", "variable": "tie_parc_porc", "text": "{tie_parc_porc}" }, 
                "operand2": { "value": "100", "type": "unit" } 
            } 
        }
       
    },
    
    S: { /****  ROLES SEMANALES ****/
        deci_cuar: { "operator": "*", "operand1": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, "operand2": { "operator": "?", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" }, "operand2": { "value": "7", "type": "unit" } }, "operand2": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" }, "operand2": { "value": "14", "type": "unit" } } } } }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } },
        sueldo_neto_calc: { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_neto", "text": "{sueldo_neto}" }, "operand2": { "value": 12, "type": "unit" } }, "operand2": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" } },

        sueldo_dias: { 
            "operator": "*", 
            "operand1": { 
                "operator": "/", 
                "operand1": { 
                    "operator": "/", 
                    "operand1": { 
                        "operator": "*", 
                        "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, 
                        "operand2": { "value": "12", "type": "unit" } 
                    }, 
                    "operand2": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" } 
                }, 
                "operand2": { 
                    "operator": "=", 
                    "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" },
                    "operand2": { 
                        "operator": "?", 
                        "operand1": { "value": "7", "type": "unit" }, 
                        "operand2": { "value": "14", "type": "unit" }                         
                    } 
                } 
            }, 
            "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } 
        },
        
        tiempo_parcial_rol: { "operator": "*", "operand1": { "operator": "-", "operand1": { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, "operand2": { "value": "12", "type": "unit" } }, "operand2": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" } }, "operand2": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "tie_parc_porc", "text": "{tie_parc_porc}" }, "operand2": { "value": "100", "type": "unit" } } }
    },
    BS: { /****  ROLES BI-SEMANALES ****/
        deci_cuar: { "operator": "*", "operand1": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "sueldo_bas", "text": "{sueldo_bas}" }, "operand2": { "operator": "=", "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, "operand2": { "operator": "?", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" }, "operand2": { "value": "7", "type": "unit" } }, "operand2": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" }, "operand2": { "value": "14", "type": "unit" } } } } }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } },
        sueldo_neto_calc: { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo_neto", "text": "{sueldo_neto}" }, "operand2": { "value": 12, "type": "unit" } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" }, "operand2": { "value": "2", "type": "unit" } } },

        sueldo_dias: { 
            "operator": "*", 
            "operand1": { 
                "operator": "/", 
                "operand1": { 
                    "operator": "/", 
                    "operand1": { 
                        "operator": "*", 
                        "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, 
                        "operand2": { "value": "12", "type": "unit" } 
                    }, 
                    "operand2": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" } 
                }, "operand2": { 
                    "operator": "=", 
                    "operand1": { "value": 0, "type": "item", "variable": "medio_tiempo", "text": "{medio_tiempo}" }, 
                    "operand2": { 
                        "operator": "?", 
                        //"operand1": { "value": "7", "type": "unit" }, 
                        //"operand2": { "value": "14", "type": "unit" } 
                        "operand1": { "value": "14", "type": "unit" }, 
                        "operand2": { "value": "14", "type": "unit" } 
                    } 
                } 
            }, "operand2": { "value": 0, "type": "item", "variable": "dias", "text": "{dias}" } 
        },
       
        tiempo_parcial_rol: { "operator": "*", "operand1": { "operator": "-", "operand1": { "operator": "*", "operand1": { "operator": "/", "operand1": { "operator": "*", "operand1": { "value": 0, "type": "item", "variable": "sueldo", "text": "{sueldo}" }, "operand2": { "value": "12", "type": "unit" } }, "operand2": { "value": 0, "type": "item", "variable": "semanas", "text": "{semanas}" } }, "operand2": { "value": "2", "type": "unit" } }, "operand2": { "operator": "+", "operand1": { "value": 0, "type": "item", "variable": "sueldo_dias", "text": "{sueldo_dias}" }, "operand2": { "value": 0, "type": "item", "variable": "aporte_extras_rol_p", "text": "{aporte_extras_rol_p}" } } }, "operand2": { "operator": "/", "operand1": { "value": 0, "type": "item", "variable": "tie_parc_porc", "text": "{tie_parc_porc}" }, "operand2": { "value": "100", "type": "unit" } } }
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

function updateFormulasRol() {

    console.log("Rol_Tip:" + $('#Rol_Tip').val());
    console.log(formulas_base_rol[$('#Rol_Tip').val()]);

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



        //tiempo_parcial_rol:{"operator":"=","operand1":{"type":"item","value":"1.0000","text":"{es_afiliado}","variable":"es_afiliado"},"operand2":{"operator":"?","operand1":{"operator":"=","operand1":{"type":"item","value":"1.0000","text":"{tiempo_parcial}","variable":"tiempo_parcial"},"operand2":{"operator":"?","operand1":formulas_base['tiempo_parcial_rol'],"operand2":{"type":"unit","value":"0"}}},"operand2":{"type":"unit","value":"0"}}},
        //NUEVA OPERACION PARA EL FUNCIONAMIENTO DEL TIMPO PARCIAL (WB)
        tiempo_parcial_rol: {
            "operator": "=", // if (es_afiliado == 1)
            "operand1": { "type": "item", "value": "1.0000", "text": "{es_afiliado}", "variable": "es_afiliado" }, // se asigna un valor 1  o 0
            "operand2": {
                "operator": "?",        // if(tiempo_parcial==1)
                "operand1": {           // tiempo_parcial = datos[tiempo_parcial]
                    "operator": "=",
                    "operand1": {  //0
                        "type": "item", "value": "1.0000", "text": "{tiempo_parcial}", "variable": "tiempo_parcial"
                    },
                    "operand2": {
                        // "operator": "?", "operand1": "0",
                        "operator": "?",
                        "operand1": formulas_base['tiempo_parcial_rol'], //$resultado = formulas_base['tiempo_parcial_rol'];
                        // "operand2": { "type": "unit", "value": "0" } // $resultado = 0;
                        "operand2": formulas_base['tiempo_parcial_rol']
                    }
                },
                "operand2": { "type": "unit", "value": "0" } //$resultado = 0;
                //"operand2": formulas_base['tiempo_parcial_rol']
            }
        },

        aporte_iece: { "operator": "=", "operand1": { "type": "item", "value": "0.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": formulas_base['aporte_iece'], "operand2": { "value": 0, "type": "unit" } } },
        aporte_patronal: formulas_base['aporte_patronal'],
        vacacion_rol_p: { "operator": "=", "operand1": { "type": "item", "value": "0.0000", "text": "{es_empleador}", "variable": "es_empleador" }, "operand2": { "operator": "?", "operand1": formulas_base['vacacion_rol_p'], "operand2": { "value": 0, "type": "unit" } } }
    };
    // console.log(datos['tiempo_parcial']);


    $.each(fields, function (i, v) {

        if ($.vv(formulas_rol[v['Cam_Var']])) {

            //console.log( "Cam_Var:" + formulas_rol[v['Cam_Var']]);

            fields[i]['Cam_For'] = formulas_rol[v['Cam_Var']];

        } else
            if ((v['Cam_Var'] === 'total_ingr' || v['Cam_Var'] === 'total_egr') && !$.vv(v['Cam_For'])) {
                fields[i]['Cam_For'] = createFormAuto(v['Cam_Var'] === 'total_ingr' ? 'I' : 'E');
            }
    });
    //fillRoles();
}

$(function () {
    $grid = $('#rol'); $tipo = $('#Rol_Tip'); gridComp = $("#comp"); gridExtras = $("#compextra");

    proviDialog = $('#proviDetaDialog').createDialogDetail({
        footerrow: true, caption: 'Detalle Provisión ',
        colModel: [
            { label: 'Cód.Int.', name: 'Cam_Cod', key: true, width: 25, align: "center", hidden: true },
            { label: 'Provision ', name: 'Cam_Des', width: 75, classes: 'bgNoColor' },
            { label: 'Valor', name: 'Cam_Val', width: 30, align: 'right', formatter: 'currency', formatoptions: { defaultValue: '' }, summaryType: "sum", summaryRound: 2 }
        ],
        loadComplete: function () { $(this).setGridSummary(['Cam_Val'], { Cam_Val: $.round($(this).jqGrid('getCol', 'Cam_Val', false, 'sum')), Cam_Des: "<div style='text-align:right;'>TOTAL:</div>" }); }
    });
    if ($('#detallesRol').length > 0) {
        $("#detalleRolTabs").tabs();
        detallesDialog['dialog'] = $('#detallesRol').createDialog({ title: 'Detalle Rol', icon: 'info-sign', width: 500, height: 300 });
        var anti = {
            footerrow: true, responsive: false, width: 485, height: 140,
            colModel: [
                { label: 'Cód.Int.', name: 'Ant_Cod', key: true, width: 25, align: "center", hidden: true },
                { label: 'Fecha', name: 'Ant_Fec', width: 30, align: "center", classes: 'bgNoColor bgNoRight' },
                { label: 'Observacion ', name: 'Ant_Obs', width: 75, classes: 'bgNoColor' },
                { label: 'Valor', name: 'Ant_Val', width: 30, align: 'right', formatter: 'currency', formatoptions: { defaultValue: '' }, summaryType: "sum", summaryRound: 2 },
                { label: $.createIcon("print"), name: 'Act', width: 10, align: 'center', formatter: 'gridButton', formatoptions: { action: imprAnt, type: 'info', icon: 'print', data: 'Ant_Cod', title: 'Imprimir' }, title: false }
            ],
            loadComplete: function () { $(this).setGridSummary(['Ant_Val'], { Cam_Des: "<div style='text-align:right;'>TOTAL:</div>" }); }
        };
        if ($('#anticipos_rol_p').length > 0) detallesDialog['anticipos_rol_p'] = $('#anticipos_rol_p').createGrid(anti, true);
        if ($('#abonos_rol_p').length > 0) detallesDialog['abonos_rol_p'] = $('#abonos_rol_p').createGrid(anti, true);
        if ($('#descuentos_rol_p').length > 0) detallesDialog['descuentos_rol_p'] = $('#descuentos_rol_p').createGrid(anti, true);
    }
    if ($('#successDialog').length === 1) $('#successDialog').createDialog({ width: 500, height: 150, icon: 'ok' });
});
function imprAnt(Ant_Cod) {
    fecha_rol = $('span[name="Rol_Fei"]').text();
   
    //console.log(Ant_Cod); 
    //$.imprimirUrl("../../rrhh/FRONT/rhu_pri_anticipo.php?Ant_Cod=" + Ant_Cod);
    $.imprimirUrl("../../rrhh/FRONT/rhu_pri_anticipo.php?Ant_Cod=" + Ant_Cod + "&fecha_ant=" + fecha_rol);

}
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
/*$.fn.fmatter.anticiposRol=function(cv,opts,cObjt){
    var o=$.cloneCO(opts,cObjt), ant=o['anticipos_rol_p'], tot=0;    
    if($.varValid(ant)&&Array.isArray(ant)&&ant.length>0) $.each(ant, function(i,v){ tot+=(v['Ant_Val']*1); });
    return $.numFormat(tot,'number');
};
$.fn.fmatter.anticiposRol.unformat=$.numberCellUnformat;*/
$.fn.fmatter.numeric = function (cv, opts, cObjt) {
    if (Array.isArray(cv)) {
        //console.log('es array');
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


function diasInput(e, obj, opt) {

    e.style.textAlign = 'right';
    $(e).attr({ type: 'number', max: $tipo.find('option:selected').data('dias'), min: 0, step: 1, onkeypress: 'return validar_numeric(event);' }).addClass('dias nospin')
        .on('keyup', function () {

            if (isNaN(this.value) || this.value === '') { $(this).val('').focus(); return false; }
            else {
                var val = $.round(this.value, 0);

                // console.log(this.value+"  max:"+this.max+"  min:"+this.min);


                if (val > this.max * 1 || val < this.min * 1) this.value = this.max;
                updateEditableFields(obj);
            }
        })
        .on('change', function () { if (!isNaN(this.value) && this.value !== '') this.value = $.toFixed('0' + this.value, 0); else $(this).val(this.max).trigger('keyup'); });
}


function styleInput(e, obj, opt) {
    $(e).attr({ placeholder: '0.00', onkeypress: 'return validar_decimal(event);' }).css('textAlign', 'right')
        .on('keyup', function () {
            if (isNaN('0' + this.value) || this.value === '') { $(this).val('').focus(); return false; }
            else updateEditableFields(obj);
        })
        .on('change', function () { if (!isNaN(this.value)) $(this).val($.toFixed('0' + this.value)).trigger('keyup'); });
}

function updateEditableFields(obj) {
    console.log("---update editeable filesd");
    var Afi_Fei = $('span[name=Rol_Fei]').html() || $('input[name=Rol_Fei]').val();
    $.each(personal, function (i, v) {
        console.log(v['Con_Cod'] + "===" + obj['rowId']);
        if (v['Con_Cod'] === obj['rowId']) {
            var fila = calcCampo($.extend(true, { edit: 'S' }, v, setFondoReservaConfig(v), defaults, $('#rol').find('tr#' + obj['rowId']).getDataForced()));
            $.each(fields, function (i, v) { if (v['Cam_Req'] === 'S') delete fila[v['Cam_Var']]; });
            $('#rol').changeRow(obj['rowId'], fila);
            updateTotales(true);
            return false;
        }
    });
}
function updateTotales(edit) {

    var gridR = $('#rol'), cols = [], inputs = [], def = { saldo: '' }, provi = [];

    //console.log(provi);
    //console.log("----::---::---");
    $.each(fields, function (i, v) {
        if (edit === true) fields[i]['edit'] = true;
        if (v['Cam_Tip'] === 'P') provi.push({ Cam_Cod: v['Cam_Cod'], Cam_Var: v['Cam_Var'], Cam_Des: v['Cam_Des'] });
        if (v['Cam_Req'] === 'S' && (edit === true || v['edit'] === true)) { inputs.push(v['Cam_Var']); def[v['Cam_Var']] = 0; }
        else cols.push(v['Cam_Var']);
        //if(v['Cam_Var']==='extension_conyugal') console.log(edit,v);
    });

    $.each(inputs, function (i, v) {
        if ($.varValid(personal)) $.each(personal, function (j, w) { def[v] = def[v] + gridR.find('tr#' + w['Con_Cod']).find('input#' + w['Con_Cod'] + '_' + v).val() * 1; });
    }); def['dias'] = '';
    cols.push('saldo_rol');
    //console.log(cols);
    $.each(cols, function (i, v) { def[v] = $.round(gridR.jqGrid('getCol', v, false, 'sum')); });
    if (proviDialog.length === 1) {
        for (var i = 0, z = provi.length; i < z; i++)
            provi[i]['Cam_Val'] = def[provi[i]['Cam_Var']];
        proviDialog.getDialogGrid().setRows(provi);
    }
    //console.log(def);
    gridR.jqGrid('footerData', 'set', $.extend({ Tic_Des: '<div style="text-align:right">TOTALES:</div>' }, def)); delete def['dias'];
    if ($.isset('updateTotalesExtras')) updateTotalesExtras();
    //console.log(def);
    return def;
}
function setRolGridCaption(caption) { return caption; }
function createGrid(grid, header) {
    grid['caption'] = setRolGridCaption(grid['caption']);
    if ($('#rol')[0].grid) { $.jgrid.gridUnload('#rol'); }
    $grid = $("#rol").createGrid($.extend({ height: 400, pager: '#rolPager' }, grid), true);
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
function recreateGrid(id) {
    $.getDataJson("", { getPlantilla: true, Map_Cod: id }, function (response) {
        createGrid(response['grid'], response['header']);
        fields = response['rol'];
        if ($.vv(response['rol_config'])) $('#Rol_Tip').val(response['rol_config']['Map_Tip']).trigger('change');
        fillRoles();
        if (response['grid']['footerrow']) updateTotales();
    });
}
function getDataGrid(Are_Cod) {
    setRoles();
    $.getDataJson("", { getData: true, Are_Cod: Are_Cod }, function (response) {
        personal = response['rows']; defaults = response['defaults'];
        setRange();
        //fillRoles(); 
        return false;
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
        //console.log(fond);
    }
    return fond;
}
function fillRoles() {
    updateFormulasRol();
    var val = $tipo.find('option:selected').data('dias'), Afi_Fei = $('input[name=Rol_Fei]').val(), Afi_Fef = $('input[name=Rol_Fef]').val(), roles = $.extend(true, [], personal);
    for (var j = 0, z = roles.length; j < z; j++) {
        roles[j] = $.extend(roles[j], setFondoReservaConfig(roles[j]), defaults, { dias: val * 1 });
        roles[j] = calcCampo(roles[j]);
        roles[j]['anticipo_val'] = roles[j]['total_rol'];//-($.isNum(roles[j]['abonos_rol_p'])?roles[j]['abonos_rol_p']:0);//{efectivo: true, Ant_Val: roles[j]['total_rol'], Ant_Det: Array(0)};
        //console.log(roles[j]);
    }
    if (roles.length > 0) $('#rol').setRows(roles).startGridEdit(); else $('#rol').clearGrid();
    updateTotales();
}
var rrr;
function calcCampo(rol) {
    var depende = [];
    //console.log(rol.Personal);
    $.each(fields, function (i, v) { 
        if (v['Cam_Cal'] === 'N' || (v['Cam_Req'] === 'S' && v['Cam_Var'] === 'sueldo_neto_calc')) { 
            if (!$.varValid(rol[v['Cam_Var']])) 
                rol[v['Cam_Var']] = ($.varValid(v['Cam_Por']) ? v['Cam_Por'] : 0); 
            else { 
                if (!isNaN(rol[v['Cam_Var']])) rol[v['Cam_Var']] = rol[v['Cam_Var']] * 1; 
                else { 
                    rol[v['Cam_Var']] = Array.isArray(rol[v['Cam_Var']]) ? rol[v['Cam_Var']] : 0; 
                } 
            } 
        } 
    });
    $.each(fields, function (i, v) { 
        if (v['Cam_Cal'] === 'S' && v['Cam_Tip'] !== 'T' && v['Cam_Req'] === 'N') { 
            if(v['Cam_Des']==='TIEMPO PARCIAL') if(rol['Con_Tpa']=="0") return; // control para evitar que se haga el calculo TIEMPO PARCIAL a contratos tipo Tiempo Completo
            var val = calcVal(rol, v['Cam_For']); 
            if ($.varValid(val)) 
                rol[v['Cam_Var']] = $.round(val); 
            else 
                depende.push(v); 
        } 
    });
    $.each(fields, function (i, v) { 
        if (v['Cam_Cal'] === 'S' && v['Cam_Tip'] === 'T') { 
            var val = calcVal(rol, v['Cam_For']); 
            if ($.varValid(val)) rol[v['Cam_Var']] = $.round(val); else depende.push(v); } 
    });
    $.each(depende, function (i, v) { rol[v['Cam_Var']] = $.round(calcVal(rol, v['Cam_For'])); });
    return rol;
}
function setToCero(val) { return ($.isNum(val) && val * 1 < 0) ? 0 : val; }
function calcVal(rol, formula) { return setToCero(calcFormul(rol, formula)); }
function calcFormul(data, formula) {
    if (formula['operator'] === '=') {
        if ($.toBool(data[formula['operand1']['variable']]) === $.toBool(formula['operand1']['value'])) 
            formula = formula['operand2']['operand1'];
        else {
            formula = formula['operand2']['operand2'];
        }
        return calcFormulType(data, formula);
    }
    var val1 = calcFormulType(data, formula['operand1']), val2 = calcFormulType(data, formula['operand2']);
    if (!$.varValid(val1) || !$.varValid(val2)) return null;
    else {
        //var tot_calc=calcMath(formula['operator'],($.isNum(val1)&&val1*1>0?val1*1:0),($.isNum(val2)&&val2*1>0?val2*1:0));
        var tot_calc = calcMath(formula['operator'], ($.isNum(val1) ? val1 * 1 : 0), ($.isNum(val2) ? val2 * 1 : 0));       
        console.log(($.isNum(val1) ? val1 * 1 : 0) +" "+ formula['operator'] +" "+ ($.isNum(val2) ? val2 * 1 : 0) +" = "+ tot_calc)
        //return (tot_calc>0)?tot_calc:0;
        return tot_calc;
    }
}
function calcFormulType(data, item) {
    if ($.varValid(item['operator'])) {
        return calcFormul(data, item);
    } else {
        if (item['type'] === 'unit') {
            return ($.varValid(item['value']) ? item['value'] : 0);
        } else {
            var val = null; $.each(data, function (k, v) { if (k === item['variable']) { val = Array.isArray(v) ? $.arraySumVal(v, campoGlobal(k)) : v; return false; } }); return val;
        }
    }
}
function calcMath(oper, val1, val2) {
    switch (oper) {
        case '+': return val1 + val2;
        case '-': return val1 - val2;
        case '*': return val1 * val2;
        case '/': return val1 / val2;
        default: return null;
    }
}
function campoGlobal(cam_var) { var gl = { anticipos_rol_p: 'Ant_Val', descuentos_rol_p: 'Ant_Val', prestamos_rol_p: 'Pre_Val', abonos_rol_p: 'Ant_Val' }; return $.isString(cam_var) ? gl[cam_var] : gl; }
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
function setSemana() {
    var semana = $('#Rol_S').find('option:selected').data();
    $('.Rol_Range').setData(semana);
    getDefaults();
}

function setSemanaF() {
    var semana = $('#Rol_F').find('option:selected').data();
    $('.Rol_RangeF').setData(semana);
    getDefaults();
}

function getDefaults() {
    var anio = $('#Pec_Cod option:selected').data('year'), semanas = semanas = ($.vv(anio) ? isoWeeks(anio).isoWeeks() : 52);
    $.getDataJson("", $('#formRol').getData('getDefaults'), function (response) {
        defaults = response['defaults'];
        if ($.vv(response['personal'])) personal = response['personal'];
        //if(){
        $.each(personal, function (i, v) {
            $.each(campoGlobal(), function (k, w) {
                personal[i][k] = Array.isArray(response[k]) ? $.cloneData($.arrayGetItems(response[k], 'Con_Cod', v['Con_Cod'])) : [];
            });

            if (personal[i]['labores_total'] != null) {
                if (personal[i]['medio_tiempo'] == '1') {
                    var diferencia = (personal[i]['labores_total'] - (((personal[i]['sueldo'] * 12) / semanas) / 2)).toFixed(2);
                }
                else {
                    var diferencia = (personal[i]['labores_total'] - ((personal[i]['sueldo'] * 12) / semanas)).toFixed(2);
                }

                if (diferencia > 0) {
                    personal[i]['labores_ingreso'] = diferencia;
                }

                if (diferencia < 0) {
                    personal[i]['labores_egreso'] = diferencia * -1;
                }
            }

            personal[i]['semanas'] = semanas;

        });
        if ($.vv(response['totales']) && $.isArray(response['totales']))
            $.each(personal, function (i, v) {
                personal[i]['total_rol'] = ($.arrayGetItems(response['totales'], 'Con_Cod', v['Con_Cod']))['total_rol'];
            });
        //}
        fillRoles(); return false;
    });
}
var numQuincenas = [];
function setRange() {
    var mes = $('#Month').monthpicker('getMonth'), Rol_Tip = $tipo.val(), range = calcRolNum(Rol_Tip);
    if (Rol_Tip === 'Q') { mes = mes * 2 - (3) + ($('#Rol_Q').val() * 1); } else mes--;
    if (Rol_Tip === 'Q') {
        var mesAux = $('#Month').monthpicker('getMonth');
        $('#Rol_Q').find('option').show();
        if (numQuincenas.includes(mesAux * 2 - (3) + 2))
            $('#Rol_Q').find('option[value=1]').hide();
        if (numQuincenas.includes(mesAux * 2 - (3) + 3))
            $('#Rol_Q').find('option[value=2]').hide();
        //console.log(mesAux*2-(3)+1,mesAux*2-(3)+2);
        //console.log(range);
    }
    //$('#Mes').html($('#Month').monthpicker('getMonthLong'));         
    $('.Rol_Range').setData($('#Rol_Q').val() * 1 === 0 && Rol_Tip === 'Q' ? {} : range[mes]);
    if (!!$('#Ant_Fec').length) $('#Ant_Fec').dateLimits($('input[name=Rol_Fei]').val(), $('input[name=Rol_Fef]').val());
    //console.log("qincenal");
    getDefaults();
}
function setRoles() {

    var data = $('#formRol').getData('getRoles'), Rol_S = $('#Rol_S'), semanas = calcRolNum($('#Rol_Tip').val() === 'BS' ? 'BS' : 'S');
    if (!$.isUnd($('#Month').length) && $('#Month').length > 0)
        $('#Month').monthpicker('setMonthActive', 0);
    $('#Rol_Q').val(0);
    $('.Rol_Range').setData({});
    Rol_S.html('<option value="" selected="">Seleccione...</option>'); $.each(semanas, function (i, v) { Rol_S.append($('<option value="' + v['Rol_Num'] + '" >Semana ' + v['Rol_Nom'] + '</option>').data(v)); });

    if ($.isUnd(data['Map_Cod']) || $.isUnd(data['Are_Cod']) || data['Map_Cod'].length === 0 || data['Are_Cod'].length === 0) return;
    $.getDataJson("", data, function (r) {
        if (r['Rol_Tip'] === 'M') {
            var month = $('#Month');
            month.monthpicker('disableMonths', r['numbers']);
            month.monthpicker('setMonthActive', 0);
            return false;
        }
        if (r['Rol_Tip'] === 'Q') {
            numQuincenas = r['numbers'];
            var month = $('#Month');
            month.monthpicker('disableMonths', []);
            month.monthpicker('setMonthActive', 0);
        }
        if (r['Rol_Tip'] === 'S' || r['Rol_Tip'] === "BS") {
            var weeks = $('#Rol_S');
            $.each(r['numbers'], function (i, v) { weeks.find('option[value=' + v + ']').attr('disabled', 'disabled').hide(); });
        }
    });

}
function updateDias() {
    var opt = $tipo.find('option:selected').data(), val = $tipo.val();
    if (!$.varValid(opt) || opt === '') return;
    $('.dias').val(opt['dias']).attr('max', opt['dias'] === '' ? 30 : opt['dias']).trigger('change');

    $('.ranges').hide();
    $('.' + val).show();
    if (!$.isUnd($('#Month').length) && $('#Month').length > 0)
        $('#Month').monthpicker('setMonthActive', 0);
    setRoles();
    if (val === 'Q') getDefaults();
    //            switch(val){
    //                case 'M': 
    //                    setRoles();                    
    //                    break;
    //                case 'Q': 
    //                    break;
    //                case 'S': 
    //                    
    //                    break;    
    //            }

}

/* Busquedas */
function createSearchGrid(extras) {
    extras = extras || [];
    var model = [
        { label: 'Cód.Int.', name: 'Rol_Cod', key: true, width: 25, align: "center" },
        { label: 'Año', name: 'Anio', width: 50, hidden: true, sorttype: 'number', formatter: "interger" },
        { label: 'Area', name: 'Are_Des', width: 50, hidden: true },
        { label: 'Número', name: 'Rol_Num', width: 30, hidden: false, sorttype: 'number', formatter: "interger" },
        { label: 'Plantilla', name: 'Map_Des', width: 50 },
        { label: 'Descripción', name: 'Rol_Con', width: 250 },
        { label: 'Tipo', name: 'Rol_Tip', width: 50, align: "center", formatter: "estado", formatoptions: { full: true, types: { M: "MENSUAL", Q: "QUINCENAL", S: "SEMANAL", BS: "BISEMANAL" } } },
        { label: 'F. Inicio', name: 'Rol_Fei', width: 50, align: "center" },
        { label: 'F. Fin', name: 'Rol_Fef', width: 50, align: "center" },
        { label: 'Usuario', name: 'Usuario', width: 50 },
        { label: 'Estado', name: 'Rol_Est', width: 50, align: "center", formatter: "estado", formatoptions: { full: true } }
    ];
    $.each(extras, function (i, v) { model.push(v); });
    gridComp.createGrid({
        stateCol: 'Rol_Est', stateConfig: { I: 'cellRed2', Pagos: 'cellGreen2' }, stateCondition: function (row) { if (row['Pagos'] === 'S') return "Pagos"; },
        leyenda: [{ icon: 'stop green', label: 'Contiene Pagos' }, { icon: 'lock orange', label: 'Contiene Pagos' }, { icon: 'remove red', label: 'Anulados/Inactivos' }],
        height: 250, caption: '&nbsp;', rowNum: 10000000, rownumbers: false, sortname: 'Rol_Num', sortorder: 'desc', colModel: model, groupingView: groupAnioArea, grouping: true
    }, true, "listPager", { refresh: false, view: true }).setRows([]).resizeGrid();
    $.createDateRange('#ini', '#fin');
    $('#Pec_Cod').on('change', function () { if ($(this).val() === 'RANGE') { $('.date-ranges :input').removeAttr('disabled', 'disabled'); } else { $('.date-ranges :input').attr('disabled', 'disabled'); } });
}
function searchRoles() {
    $.getDataJson(gridComp, $('#formSearchRol').getData('rolesAjax'), function (r) {
        var area = $('#Are_Cod').val(), periodo = $('#Pec_Cod').val();
        if (area !== '' && periodo !== 'ALL' && periodo !== 'RANGE')
            gridComp.jqGrid('groupingRemove', true);
        else if (area === '' && periodo !== 'ALL' && periodo !== 'RANGE')
            gridComp.jqGrid('groupingGroupBy', 'Are_Des', groupArea);
        else if (area !== '' && (periodo === 'ALL' || periodo === 'RANGE'))
            gridComp.jqGrid('groupingGroupBy', 'Anio', groupAnio);
        else
            gridComp.jqGrid('groupingGroupBy', ['Anio', 'Are_Des'], groupAnioArea);
        gridComp.setRows(r['rows']).jqGrid('setCaption', 'ROLES ' + (periodo === 'RANGE' ? ' - DESDE ' + $('#ini').val() + ' HASTA ' + $('#fin').val() : (periodo !== 'ALL' ? ' - PERIODO: ' + $('#Pec_Cod option:selected').data('year') : '')) + (area !== '' ? ' - AREA: ' + $('#Are_Cod option:selected').text() : ''));
    });
};
function detallarRoles(data, edit) {
    $('.exportRoles').data('originaldata', data);
    $('.detalle').setData(data);
    var semanas = semanas = ($.vv(data['anio']) ? isoWeeks(data['anio']).isoWeeks() : 52);
    data['semanas'] = semanas;
    $.getDataJson("", $.extend(data, { getRolDetail: true }), function (response) {
        $('#listaVariablesRol').empty();
        createGrid(response['grid'], response['header']);
        $grid.setRows(response['personal']);
        fields = response['rol'];
        updateFormulasRol();
        if (response['edit'] !== false) $grid.startGridEdit();
        personal = response['personal'];
        $.each(personal, function (i, v) {

            personal[i]['semanas'] = semanas;
        });

        $('#main-search').moveComp('#rol-sdetail').updateGridsSizes();
        if ($.isset('detallarExtras')) detallarExtras(data, response);
        updateTotales(response['edit'] !== false);

    });
}

/** Lista en modal todas las columnas del grid #rol, sombreadas por bloque INGRESOS / EGRESOS (según `fields` y misma lógica que PHP). */
function abrirModalVariablesRol() {
    var $g = $('#rol');
    if (!$g.length || !$g[0].grid) {
        $.alert('Abra primero el detalle de un rol para cargar el grid.');
        return;
    }
    var colModel = $g.jqGrid('getGridParam', 'colModel') || [];
    var fieldByName = {};
    if (fields && fields.length) {
        $.each(fields, function (i, f) {
            var n = f.Cam_Var || f.name;
            if (n) fieldByName[n] = f;
        });
    }
    var esc = function (s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };
    var tipLabel = { I: 'Ingreso', E: 'Egreso', D: 'Defecto', T: 'Total', P: 'Parámetro' };
    var personalNames = { Prs_Cod: 1, Per_Cod: 1, Con_Tpa: 1, Con_Cod: 1, Prs_Ced: 1, Prs_Ape: 1, Prs_Nom: 1, Tic_Des: 1 };
    function stripLabel(html) {
        if (!html) return '';
        var t = $('<div>').html(html).text();
        return $.trim(t) || String(html).replace(/<[^>]+>/g, '');
    }
    /** Alineado a rhu_log_roles.php: T con Cam_Ord 1 -> ingr; resto T y P -> egr. */
    function grupoBloque(name, f) {
        if (!f) {
            if (name === 'anticipos_ant' || name === 'act1' || name === 'act2') return 'acciones';
            if (personalNames[name]) return 'personal';
            return 'neutro';
        }
        var tip = f.Cam_Tip || '';
        if (tip === 'D') return 'defecto';
        if (tip === 'I') return 'ingreso';
        if (tip === 'E') return 'egreso';
        if (tip === 'T') return (f.Cam_Ord * 1 === 1) ? 'ingreso' : 'egreso';
        if (tip === 'P') return 'egreso';
        return 'neutro';
    }
    function rowStyle(bloque) {
        if (bloque === 'ingreso') return 'background:#d9efd9;';
        if (bloque === 'egreso') return 'background:#f8dede;';
        if (bloque === 'defecto') return 'background:#e8eef5;';
        if (bloque === 'personal') return 'background:#f5f5f5;';
        if (bloque === 'acciones') return 'background:#f0f0f0;';
        return 'background:#fafafa;';
    }
    function grupoTexto(bloque) {
        if (bloque === 'ingreso') return 'INGRESOS';
        if (bloque === 'egreso') return 'EGRESOS';
        if (bloque === 'defecto') return 'Defecto';
        if (bloque === 'personal') return 'Personal';
        if (bloque === 'acciones') return 'Acciones';
        return '';
    }
    var rows = [];
    $.each(colModel, function (i, col) {
        var varName = col.name;
        if (!varName || varName === 'cb' || varName === 'subgrid') return;
        var f = fieldByName[varName];
        var bloque = grupoBloque(varName, f);
        var desc = f ? (f.Cam_Des || f.labelLong || f.Cam_Dec || f.label || '') : stripLabel(col.label);
        if (col.hidden) desc = (desc ? desc + ' ' : '') + '(oculta)';
        var tip = f && f.Cam_Tip ? tipLabel[f.Cam_Tip] || f.Cam_Tip : '';
        var chk = rolOmitirColsPersist.indexOf(varName) >= 0 ? ' checked' : '';
        rows.push(
            '<tr style="' + rowStyle(bloque) + '"><td style="width:36px;text-align:center"><input type="checkbox" class="chk-var-rol" name="rol_var_sel[]" value="' + esc(varName) + '"' + chk + '/></td>' +
            '<td><code>' + esc(varName) + '</code></td><td>' + esc(desc) + '</td><td>' + esc(grupoTexto(bloque)) + '</td><td>' + esc(tip) + '</td></tr>'
        );
    });
    var html = '<table class="table table-condensed table-bordered" style="margin:0;font-size:12px"><thead><tr><th></th><th>ID (name)</th><th>Descripción</th><th>Bloque</th><th>Tipo campo</th></tr></thead><tbody>' + rows.join('') + '</tbody></table>';
    $('#listaVariablesRol').html(html);
    var $chkCero = $('#modalOmitirColCero');
    if ($chkCero.length) {
        $chkCero.prop('checked', rolOmitirColCeroPersist);
    }
    $('#listaVariablesRol').off('change.rolomit').on('change.rolomit', 'input.chk-var-rol', function () {
        var v = this.value, i = rolOmitirColsPersist.indexOf(v);
        if (this.checked) {
            if (i < 0) rolOmitirColsPersist.push(v);
        } else if (i >= 0) {
            rolOmitirColsPersist.splice(i, 1);
        }
    });
    var $dlg = $('#modalVariablesRol');
    if (!$dlg.length) {
        $.alert('No se encontró el contenedor del modal (#modalVariablesRol).');
        return;
    }
    if (!$dlg.data('ui-dialog')) {
        $dlg.dialog({
            modal: true,
            width: Math.min(820, $(window).width() - 48),
            maxHeight: $(window).height() - 48,
            open: function () {
                $('#listaVariablesRol').css({ maxHeight: Math.min(380, $(window).height() - 220), overflowY: 'auto' });
            },
            buttons: { Cerrar: function () { $(this).dialog('close'); } }
        });
        $(document).off('change.rolomitcero', '#modalOmitirColCero').on('change.rolomitcero', '#modalOmitirColCero', function () {
            rolOmitirColCeroPersist = this.checked;
        });
        $('#modalVariablesRolTodos').on('click', function (e) {
            e.preventDefault();
            $('#listaVariablesRol input.chk-var-rol').each(function () {
                this.checked = true;
                var v = this.value;
                if (rolOmitirColsPersist.indexOf(v) < 0) rolOmitirColsPersist.push(v);
            });
        });
        $('#modalVariablesRolNinguno').on('click', function (e) {
            e.preventDefault();
            $('#listaVariablesRol input.chk-var-rol').each(function () {
                this.checked = false;
                var v = this.value, i = rolOmitirColsPersist.indexOf(v);
                if (i >= 0) rolOmitirColsPersist.splice(i, 1);
            });
        });
    }
    $dlg.dialog('option', 'title', 'Variables del grid Rol');
    $dlg.dialog('open');
}

function detallarRolesFilter(data, edit) {
    var rubros = []; // Array para almacenar los valores de los checkboxes marcados
    var checkboxes = document.getElementsByName("opciones[]");

    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            rubros.push(checkboxes[i].value);
        }
    }
    $('.exportRoles').data('originaldata', data);
    $('.detalle').setData(data);
    //console.log(data);
    var semanas = semanas = ($.vv(data['anio']) ? isoWeeks(data['anio']).isoWeeks() : 52);
    $.getDataJson("", $.extend(data, { getRolDetailFilter: true, Rubros: rubros }), function (response) {
        $('#listaVariablesRol').empty();
        createGrid(response['grid'], response['header']);
        $grid.setRows(response['personal']);
        fields = response['rol'];
        updateFormulasRol();
        if (response['edit'] !== false) $grid.startGridEdit();
        personal = response['personal'];
        $.each(personal, function (i, v) {
            //            personal[i]['anticipo_val']=personal[i]['saldo_ant'];
            personal[i]['semanas'] = semanas;
        });

        $('#main-search').moveComp('#rol-sdetail').updateGridsSizes();
        if ($.isset('detallarExtras')) detallarExtras(data, response);
        updateTotales(response['edit'] !== false);

    });
}

function getDataGridFilterAlt(data) {
    var Rol_cod = $("#Rol_Cod").val();
    var Map_Cod = $("#Map_Cod").val();
    var Map_Des = document.querySelector('#Map_Cod option:checked').textContent;
    var Are_Cod = document.querySelector('#Are_Cod option:checked').textContent;
    //alert(selectedText);
    $.post("", { 'getDatoRol': true, 'Rol_cod': Rol_cod }, function (responce) {
        if (responce['success'] === true) {
            var valor = responce['value'];
            try {
                const objeto = JSON.parse(valor);
                const selectedYear = document.getElementById("Pec_Cod").options[document.getElementById("Pec_Cod").selectedIndex].getAttribute("data-year");
                objeto.Anio = selectedYear;
                objeto.Map_Cod = Map_Cod;
                objeto.Map_Des = Map_Des;
                objeto.Are_Des = Are_Cod;
                // console.log(objeto);
                detallarRolesFilter(objeto, true);
            } catch (error) {
                console.error("Error al analizar el JSON:", error);
            }
        }
    }, 'json');

    /*$.post("../LOGICA/getDatoRol.php", { : Rol_cod }, function(data) {
        try {
            const objeto = JSON.parse(data);
            const selectedYear = document.getElementById("Pec_Cod").options[document.getElementById("Pec_Cod").selectedIndex].getAttribute("data-year");
            objeto.Anio = selectedYear;
            objeto.Map_Cod = Map_Cod;
            objeto.Map_Des = Map_Des;
            objeto.Are_Des = Are_Cod;
           // console.log(objeto);
            detallarRolesFilter(objeto,true);
        } catch (error) {
            console.error("Error al analizar el JSON:", error);
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error("Error en la solicitud:", textStatus, errorThrown);
    });*/
}


function mostrarChecksRol(id_plantilla) {
    //alert(id_plantilla);
    $.post("", { 'mostrarChecksRol': true, 'Map_Cod': id_plantilla }, function (responce) {
        if (responce['success'] === true) {
            var valor = responce['value'];
            $("#divChecks").html(valor);
        }
    }, 'json');
}
function detallarRoles2() {
    var Rol_cod = $("#Rol_Cod").val();
    var Map_Cod = $("#Map_Cod").val();
    var Map_Des = document.querySelector('#Map_Cod option:checked').textContent;
    var Are_Cod = document.querySelector('#Are_Cod option:checked').textContent;

    $.post("", { 'getDatoRol': true, 'Rol_cod': Rol_cod }, function (responce) {
        if (responce['success'] === true) {
            var valor = responce['value'];
            //alert(valor);
            try {
                const objeto = JSON.parse(valor);
                const selectedYear = document.getElementById("Pec_Cod").options[document.getElementById("Pec_Cod").selectedIndex].getAttribute("data-year");
                objeto.Anio = selectedYear;
                objeto.Map_Cod = Map_Cod;
                objeto.Map_Des = Map_Des;
                objeto.Are_Des = Are_Cod;
                // console.log(objeto);
                detallarRoles(objeto, true);
                mostrarChecksRol(Map_Cod);
            } catch (error) {
                console.error("Error al analizar el JSON:", error);
            }
        }
    }, 'json');
    //alert(selectedText);
    /*$.post("../LOGICA/getDatoRol.php", { Rol_cod: Rol_cod }, function(data) {
        try {
            const objeto = JSON.parse(data);
            const selectedYear = document.getElementById("Pec_Cod").options[document.getElementById("Pec_Cod").selectedIndex].getAttribute("data-year");
            objeto.Anio = selectedYear;
            objeto.Map_Cod = Map_Cod;
            objeto.Map_Des = Map_Des;
            objeto.Are_Des = Are_Cod;
           // console.log(objeto);
            detallarRoles(objeto,true);
            mostrarChecksRol(Map_Cod);
        } catch (error) {
            console.error("Error al analizar el JSON:", error);
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error("Error en la solicitud:", textStatus, errorThrown);
    });*/
}

function extraData(data) { return data; }
function validaExtra(data) { return true; }
function validaRol() {
    var f = []; $.each(fields, function (i, v) { f.push({ Cam_Cod: v['Cam_Cod'], Cam_Var: v['Cam_Var'] }); });
    var data = { saveRol: true, rol: $('#formRol').getData(), totales: updateTotales(), data: $grid.getGridBatch(), fields: f, t_provi: (proviDialog.length === 1 ? proviDialog.getDialogGrid().jqGrid('getCol', 'Cam_Val', false, 'sum') : 0) };
    $grid.startGridEdit();
    //console.log(data);
    if (data['rol']['Are_Cod'] === '') return $.alert('Seleccione el <u>Area</u> de la empresa!');
    if (data['rol']['Map_Cod'] === '') return $.alert('Seleccione la <u>Plantilla Rol</u> que se usara en el <i>Rol de Pagos</i>!');
    if (data['rol']['Rol_Num'] === '') return $.alert('Seleccione el <u>Rango de Fechas</u> del <i>Rol de Pagos</i>!');
    if (data['data'].length === 0) return $.alert('El <u>Rol de Pagos</u> no puede estar vacio.!');
    $.each($grid[0].p.data, function (i, v) {
        $.each(data['data'], function (j, d) {
            if (d['Con_Cod'] === v['Con_Cod']) {
                $.each(campoGlobal(), function (k, c) { data['data'][j][k + '_data'] = v[k]; });
            }
        });
    });
    data = extraData(data);
    if (!validaExtra(data)) return;
    //console.log(data);
    $.createDialogConfirm('Est&aacute; seguro que desea guardar el <u>Rol de Pagos</u>?', data, saveRol);
}

function saveRol(data) {
    $.arraySpliceFields(data.data, ['Prs_Cod', 'prestamos_rol_p_data']);
    data.data = $.jsonParser(data.data);

    $.saveDataJson("", data,
        function (resp) {
            if (resp['edit']) { searchRoles(); $('#rol-sdetail').moveComp('#main-search').updateGridsSizes(); }
            else { }
            if ($.varValid(resp['Com_Cod_Provi']) && resp['Com_Cod_Provi'] !== '')
                $('#impComprProv').show().data('url', resp['Com_Link'] + resp['Com_Cod_Provi']);
            else $('#impComprProv').hide();
            $('#impRoles').data('url', resp['Rol_Link']);
            $('#impRolesInd').data('url', resp['Rol_Ind_Link']);
            //console.log();
            $('#impCompr').data('url', resp['Com_Link'] + resp['Com_Cod']);
            resetAll(resp['Rol_Ind_Link']);
            $('#successDialog').dialog('open');
            return false;
        }
    );
}
function anulaRol() {
    var data = { deleteRol: true, rol: $('#formRol').getData() };
    $.createDialogConfirm('¿Est&aacute; seguro que desea anular el <u>Rol de Pagos</u>?<br/><b class="red">NOTA:</b> <font class="red">Esta accion no se podra deshacer.</font>', data, function () {
        $.saveDataJson("", data, function (resp) {
            searchRoles();
            $('#rol-sdetail').moveComp('#main-search').updateGridsSizes();
            $.alert("El <u class='green'>Rol de Pagos</u> se anulo con Exito!");
            return false;
        });
    });
}
function resetAll() {
    $('#formRol').setData({});
    $('#rol').clearGrid(true);
    $("#Pec_Cod").val($("#Pec_Cod option:first").val());
    //updateTotales(true);
}

function validarSemana() {
    if (parseInt($('#Rol_S').val()) > parseInt($('#Rol_F').val())) { $.alert('La semana de inicio no puede ser superior a la semana de fin.', null, 'alert'); return; }
    console.log($('input[name=Rol_Fei]').val());
    console.log('hola');
    console.log($('input[name=Rol_FefF]').val())
};

function setSemanasExtra() {

    var data = Rol_S = $('#Rol_S'), Rol_F = $('#Rol_F'), semanas = calcRolNum('S');
    if (!$.isUnd($('#Month').length) && $('#Month').length > 0)
        $('#Month').monthpicker('setMonthActive', 0);
    $('#Rol_Q').val(0);
    $('.Rol_Range').setData({});
    Rol_S.html('<option value="" selected="">Seleccione...</option>'); $.each(semanas, function (i, v) { Rol_S.append($('<option value="' + v['Rol_Num'] + '" >Semana ' + v['Rol_Nom'] + '</option>').data(v)); });
    Rol_F.html('<option value="" selected="">Seleccione...</option>'); $.each(semanas, function (i, v) { Rol_F.append($('<option value="' + v['Rol_Num'] + '" >Semana ' + v['Rol_Nom'] + '</option>').data(v)); });

    if ($.isUnd(data['Map_Cod']) || $.isUnd(data['Are_Cod']) || data['Map_Cod'].length === 0 || data['Are_Cod'].length === 0) return;
    $.getDataJson("", data, function (r) {
        if (r['Rol_Tip'] === 'M') {
            var month = $('#Month');
            month.monthpicker('disableMonths', r['numbers']);
            month.monthpicker('setMonthActive', 0);
            return false;
        }
        if (r['Rol_Tip'] === 'Q') {
            numQuincenas = r['numbers'];
            var month = $('#Month');
            month.monthpicker('disableMonths', []);
            month.monthpicker('setMonthActive', 0);
        }
        if (r['Rol_Tip'] === 'S' || r['Rol_Tip'] === "BS") {
            var weeks = $('#Rol_S');
            $.each(r['numbers'], function (i, v) { weeks.find('option[value=' + v + ']').attr('disabled', 'disabled').hide(); });
        }
    });

};

function createSearchExtra() {
    var model = [
        { label: 'Ruc Compañia', name: 'EMP_RUC', width: 30, align: "center" },
        { label: 'Sucursal IESS', name: 'SUCURSAL_IESS', width: 20, align: "center" },
        { label: 'Año', name: 'ANIO', width: 15, align: "center" },
        { label: 'Periodo', name: 'PERIODO', width: 20, align: "center" },
        { label: 'Tipo', name: 'TIPO', width: 25, align: "center" },
        { label: 'Cédula Afiliado', name: 'PRS_CED', key: true, width: 30, align: "center" },
        {
            label: 'Valor Variable', name: 'ROL_VAL', width: 30, align: "center", formatter: 'currency', decimalPlaces: '2',
            formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.' }
        },
        { label: 'O', name: 'O', width: 10, align: "center" }

    ];
    gridExtras.createGrid({
        stateCol: 'PRS_CED', height: 250, caption: '&nbsp;', rowNum: 10000000, rownumbers: false, sortname: 'PRS_CED', sortorder: 'desc', colModel: model
    }, true, "listPager", { refresh: false, view: true }).setRows([]).resizeGrid();
    $.createDateRange('#ini', '#fin');
}

function searchExtra() {
    if (parseInt($('#Rol_S').val()) > parseInt($('#Rol_F').val())) { $.alert('La semana de inicio no puede ser superior a la semana de fin.', null, 'alert'); return; } else {
        $.getDataJson(gridExtras, $('#formRol').getData('aportesAjax'), function (r) {
            gridExtras.setRows(r['rows']).jqGrid('setCaption', 'APORTES EXTRAS');
            if (parseInt(r['defaults'][0]['REPORTE_COD']) > 0) { $.alert('Ya se generó un archivo para las semanas elegidas!', null, 'alert'); return; }
        });
    }


}

function validarAportExtra() {
    var data = { saveAportes: true, rol: $('#formRol').getData(), data: $("#compextra").getGridBatch() };
    if (data['rol']['Are_Cod'] === '') return $.alert('Seleccione el <u>Area</u> de la empresa!');
    if (data['rol']['Map_Cod'] === '') return $.alert('Seleccione la <u>Plantilla Rol</u> que se usara en el <i>Rol de Pagos</i>!');
    if (data['rol']['Rol_Num'] === '') return $.alert('Seleccione el <u>Rango de Fechas</u> del <i>Rol de Pagos</i>!');
    if (data['data'].length === 0) return $.alert('No existe información para generar el archivo!');
    if (!validaExtra(data)) return;
    $.createDialogConfirm('¿Est&aacute; seguro que desea generar el archivo?', data, saveAportes);
}

function saveAportes(data) {
    $.saveDataJson("", data,
        function (resp) {
            $('#successDialog').dialog('open');
            $.alert("Archivo generado con éxito!");
            download();
            return false;
        }
    );
}


/** Columnas a omitir en Rol Grupal (persistente hasta desmarcar en el modal). */
function getOmitirColsRolModal() {
    return rolOmitirColsPersist.slice();
}

/** Opciones comunes informe Rol grupal / individual (pantalla + modal Variables). */
function dataInformeRolComun(base) {
    var data = $.extend(true, {}, base || {});
    var checkboxCargo = document.getElementById('check_box_cargo');
    data.estaMarcado = checkboxCargo ? checkboxCargo.checked : false;
    var imprimi_col_cero = document.getElementById('imprimi_col_cero');
    data.imprimirAll = (imprimi_col_cero && imprimi_col_cero.checked) || rolOmitirColCeroPersist;
    var is_col_hs = document.getElementById('check_col_hs');
    data.check_col_hs = is_col_hs ? is_col_hs.checked : false;
    var is_col_jorn = document.getElementById('check_col_jorn');
    data.check_col_jorn = is_col_jorn ? is_col_jorn.checked : false;
    data.omitirColsRol = JSON.stringify(getOmitirColsRolModal());
    return data;
}

function printRoles(data) {
    $.getDataJson('', $.extend(true, {}, dataInformeRolComun(data), {
        printAjax: true, print: true
    }), function (r) {
        $('#imprimirRoles').html(r['tabla']).printElement({ pageTitle: 'EXA - Sofware Contable', printMode: 'iframe', leaveOpen: true });
    });
}



function exportRoles(data) {
    $.getDataJson('', $.extend(true, {}, dataInformeRolComun(data), {
        printAjax: true
    }), function (r) {

        $.downloadFile($(r['tabla']).exportarExcelBlob('Roles'), 'ROLES-' + $.getDate() + '.xls');
    });
}





function printRolDetailIndiv(data) { $.getDataJson('', $.extend(true, {}, dataInformeRolComun(data), { printRolIndAjax: true, print: true }), function (r) { $('#imprimirRoles').html(r['tabla']).printElement({ pageTitle: 'EXA - Sofware Contable' }); }); }
function exportRolDetailIndiv(data) { $.getDataJson('', $.extend(true, {}, dataInformeRolComun(data), { printRolIndAjax: true }), function (r) { $.exportBooksExcel($(r['tabla']).tableToXlsWorksheetsArray({ nombre: 'ROL_IND' })); }); }
function exportRolesIndiv(data) { $.getDataJson('', $.extend(true, {}, dataInformeRolComun(data), { printRolIndAjax: true }), function (r) { $.exportBooksExcel($(r['tabla']).tableToXlsWorksheetsArray({ nombre: 'ROLES_IND' })); }); }
$.fn.fmatter.printRolIndFormater = function (cellvalue, options, rowObject) {
    return $.getGridButton(printRolDetailIndiv, { Con_Cod: rowObject.Con_Cod, Rol_Cod: rowObject.Rol_Cod }, 'Imprimir Rol Ind.', 'print', null, 'info');
};
$.fn.fmatter.descargarRolIndFormater = function (cellvalue, options, rowObject) {
    return $.getGridButton(exportRolDetailIndiv, { Con_Cod: rowObject.Con_Cod, Rol_Cod: rowObject.Rol_Cod }, 'Descargar Excel', 'download', null, 'info');
};

function getDataGridFilter(arrayItems) {
    var Map_Cod = $("#Map_Cod").val();
    var rubros = []; // Array para almacenar los valores de los checkboxes marcados
    var checkboxes = document.getElementsByName("opciones[]");

    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            rubros.push(checkboxes[i].value);
        }
    }
    //alert(rubros);
    $.getDataJson("", { getFilters: true, Map_Cod: Map_Cod, Rubros: rubros }, function (response) {
        createGrid(response['grid'], response['header']);
        fields = response['rol'];
        if ($.vv(response['rol_config'])) $('#Rol_Tip').val(response['rol_config']['Map_Tip']).trigger('change');
        fillRoles();
        if (response['grid']['footerrow']) updateTotales();
    });
}

function getDataGrid2(Are_Cod) {
    setRoles();
    for (var i = 1; i <= 176; i++) {
        $.getDataJson("", { getData: true, Are_Cod: i }, function (response) {
            personal = response['rows']; defaults = response['defaults'];
            setRange();
            //fillRoles(); 
            return false;
        });
    }

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
        //console.log(fond);
    }
    return fond;
}

function isoWeeks(year) { var d; for (var i = 31; i >= 0; i--) { d = moment(year + '-12-' + i); if (d.isoWeeks() > 10) break; } return d; };

function actualizarLabores() {
    $("#gridContainer").find("tr.ui-row-ltr").each(function () {
        // Obtener el rowId de la fila
        var rowId = $(this).attr("id");
        // Crear el objeto con la estructura deseada
        var objeto = {
            "id": rowId + "_CANT_HREX",
            "name": "CANT_HREX",
            "rowId": rowId,
            "oper": "edit"
        };
        var objeto2 = {
            "id": rowId + "_fond_reser",
            "name": "fond_reser",
            "rowId": rowId,
            "oper": "edit"
        };
        updateEditableFields(objeto);
        updateEditableFields(objeto2);
    });
    $.alert('Valores actualizados!');
}
/*function days_360(fecha1,fecha2,europeo) {
  europeo=$.isBool(europeo)?europeo:true;
  if(!$.dateValid(fecha1)||!$.dateValid(fecha2)) {return(-1);}  // no date

  if( fecha1 > fecha2 ) { var temf = fecha1;  fecha1 = fecha2; fecha2 = temf; } //try switch dates: min to max
  var listFec1=fecha1.split("-"), $yy1=listFec1[0]*1, $mm1=listFec1[1]*1, $dd1=listFec1[2]*1 ;
  var listFec2=fecha2.split("-"), $yy2=listFec2[0]*1, $mm2=listFec2[1]*1, $dd2=listFec2[2]*1 ;

  //if (!checkdate($mm1,$dd1,$yy1)||!checkdate($mm2,$dd2,$yy2)){return(-1);} // invalid date

  if( $dd1==31) { $dd1 = 30; }
  if(!europeo) { //checks according standars: 30E/360 or 30/360.
    if( ($dd1==30) && ($dd2==31) ) {  $dd2=30; }
    else { if( $dd2==31 ) { $dd2=30; } }
  }
  if( ($dd1<1) || ($dd2<1) || ($dd1>30) || ($dd2>31) ||
      ($mm1<1) || ($mm2<1) || ($mm1>12) || ($mm2>12) ||
      ($yy1>$yy2) ) { return(-1); } //check for invalid
  if( ($yy1==$yy2) && ($mm1>$mm2) ) { return(-1); } // error
  if( ($yy1==$yy2) && ($mm1==$mm2) && ($dd1>$dd2) ) { return(-1); } // error

  $yy = $yy2-$yy1; //Calc
  $mm = $mm2-$mm1; //
  $dd = $dd2-$dd1; //
  return( ($yy*360)+($mm*30)+$dd );
};*/

//NUEVAS FUNCIONES




