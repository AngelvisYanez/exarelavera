var esMod = false;
var esCrear = false;
var arrayDetalle;
var arrayD = [];

/**
 * Funciones Centralizadas de Sanitización de Caracteres Especiales
 * Corrige mojibake, entidades HTML, tags y caracteres incompatibles
 */
function sanitizarTexto(str) {
    if (str === undefined || str === null) return '';
    var txt = String(str);

    // 1. Decodificar secuencias URI / URL encoded si existen
    if (txt.indexOf('%') !== -1) {
        try {
            txt = decodeURIComponent(txt);
        } catch(e) {
            try { txt = decodeURIComponent(escape(txt)); } catch(e2) {}
        }
    }

    // 2. Corregir cualquier mojibake / doble-codificación UTF-8 usando escapes Unicode 7-bit puros
    txt = txt
        .replace(/\u00c3\u00a1/g, '\u00e1') // á
        .replace(/\u00c3\u00a9/g, '\u00e9') // é
        .replace(/\u00c3\u00ad/g, '\u00ed') // í
        .replace(/\u00c3\u00b3/g, '\u00f3') // ó
        .replace(/\u00c3\u00ba/g, '\u00fa') // ú
        .replace(/\u00c3\u00b1/g, '\u00f1') // ñ
        .replace(/\u00c3\u0081/g, '\u00c1') // Á
        .replace(/\u00c3\u0089/g, '\u00c9') // É
        .replace(/\u00c3\u008d/g, '\u00cd') // Í
        .replace(/\u00c3\u0093/g, '\u00d3') // Ó
        .replace(/\u00c3\u009a/g, '\u00da') // Ú
        .replace(/\u00c3\u0091/g, '\u00d1') // Ñ
        .replace(/\u00dd/g, '\u00ed')       // í corrupto
        .replace(/\u00be/g, '\u00f3')       // ó corrupto
        .replace(/&oacute;/gi, '\u00f3').replace(/&#243;/g, '\u00f3')
        .replace(/&aacute;/gi, '\u00e1').replace(/&#225;/g, '\u00e1')
        .replace(/&eacute;/gi, '\u00e9').replace(/&#233;/g, '\u00e9')
        .replace(/&iacute;/gi, '\u00ed').replace(/&#237;/g, '\u00ed')
        .replace(/&uacute;/gi, '\u00fa').replace(/&#250;/g, '\u00fa')
        .replace(/&ntilde;/gi, '\u00f1').replace(/&#241;/g, '\u00f1')
        .replace(/&Oacute;/gi, '\u00d3').replace(/&#211;/g, '\u00d3')
        .replace(/&Aacute;/gi, '\u00c1').replace(/&#193;/g, '\u00c1')
        .replace(/&Eacute;/gi, '\u00c9').replace(/&#201;/g, '\u00c9')
        .replace(/&Iacute;/gi, '\u00cd').replace(/&#205;/g, '\u00cd')
        .replace(/&Uacute;/gi, '\u00da').replace(/&#218;/g, '\u00da')
        .replace(/&Ntilde;/gi, '\u00d1').replace(/&#209;/g, '\u00d1')
        .replace(/&iquest;/gi, '\u00bf')
        .replace(/&iexcl;/gi, '\u00a1');

    // 3. Eliminar tags HTML potencialmente inseguros
    txt = txt.replace(/<[^>]*>?/gm, '');

    return txt.trim();
}

function escaparHTML(str) {
    if (str === undefined || str === null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

$(() => {
    searchLaborPago();
    refreshData();
    $('#tabsLabores').createTabs();
    $('#Cod_Bus').createChosen('input-xs');
    $('#Tpg_Cod').createChosen('input-xs');
    $.createDateRange('#Fec_Ini', '#Fec_Fin');
    // arrayDetalle = async() => { arrayD = await viewActividadAll(); }
    //arrayDetalle();
    $.createDatePickers('#Act_Fec');
    generarSemanas();
    //busquedaInicial();
    var sel_fecha = $("#Pec_Cod").find('option:selected');
    $('#Act_Fec').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));

    getFincas();
    $('#Fnc_Des').prop('readonly', true);
    $('#Fnc_Des_Upd').prop('readonly', true);
    var opts = {
        height: 75,
        colModel: [
            { label: 'C&oacute;d. Int.', name: 'Lab_Cod', key: true, width: 15, align: 'center', hidden: false },
            { label: 'Descripci&oacute;n', name: 'Lab_Des', width: 45, align: 'left' },
            { label: 'Unidad', name: 'Tpg_Des', width: 20, align: 'left' },
            { label: 'Valor', name: 'Lab_Val', width: 15, align: 'center' },
            { name: 'delete', label: '<i class="glyphicon glyphicon-trash"></i>', width: 10, align: 'center', viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: delLabor,
                    /*conditional: function(o) { console.log(o); return o.tarja === 'n' && o.Prh_Est !== 'I'; },*/
                    icon: 'trash', type: 'danger',
                    title: 'Anular Labor',
                    data: function(o) {
                        return o;
                    }
                },
                resizable: false
            },
            { name: 'update', label: '<i class="glyphicon glyphicon-pencil"></i>', width: 10, align: 'center', viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: updLabor,
                    icon: 'pencil',
                    type: 'info',
                    title: 'Actualizar Labor',
                    data: function(o) {
                        return o;
                    }
                },
                resizable: false
            }
        ]
    };
    var optsFink = {
        height: 75,
        colModel: [
            { label: 'C&oacute;d. Int.', name: 'Fnc_Cod', key: true, width: 12, align: 'center', hidden: false },
            { label: 'Descripci&oacute;n', name: 'Fnc_Des', width: 35, align: 'left' },
            { label: 'Direcci&oacute;n', name: 'Fnc_Dir', width: 22, align: 'left' },
            { label: 'Hect&aacute;reas', name: 'Fnc_Hec', width: 14, align: 'center', hidden: false },
            { label: 'Ubicaci&oacute;n / Mapa', name: 'georreferencia', width: 26, align: 'center', sortable: false,
                formatter: function(cellval, options, row) {
                    var lat = parseFloat(row.Fnc_Lat);
                    var lng = parseFloat(row.Fnc_Lng);
                    if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                        var secCod = row.Fnc_Cod || row.id || 0;
                        return '<button type="button" class="btn btn-xs btn-success" style="padding:1px 6px; font-size:10px; font-weight:600;" title="Ver en mapa satelital HD (' + lat.toFixed(5) + ', ' + lng.toFixed(5) + ')" onclick="verSectorEnMapa(' + lat + ', ' + lng + ', \'' + encodeURIComponent(sanitizarTexto(row.Fnc_Des || '')).replace(/'/g, '%27') + '\', ' + secCod + ')">' +
                               '<i class="glyphicon glyphicon-map-marker"></i> ' + lat.toFixed(4) + ', ' + lng.toFixed(4) + '</button>';
                    } else {
                        return '<span class="label label-default" style="font-size:10px; font-weight:normal; opacity:0.75;" title="Sector no georreferenciado"><i class="glyphicon glyphicon-ban-circle"></i> Sin GPS</span>';
                    }
                }
            },
            { name: 'delete', label: '<i class="glyphicon glyphicon-trash"></i>', width: 10, align: 'center', viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: delFinca,
                    icon: 'trash',
                    type: 'danger',
                    title: 'Anular Sector',
                    data: function(o) {
                        return o;
                    }
                },
                resizable: false
            },
            { name: 'update', label: '<i class="glyphicon glyphicon-pencil"></i>', width: 10, align: 'center', viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: updFinca,
                    icon: 'pencil',
                    type: 'info',
                    title: 'Actualizar Sector',
                    data: function(o) {
                        return o;
                    }
                },
                resizable: false
            }
        ]
    };

    $('#unidadDialog').createDialog({ height: 420, width: 590, icon: 'glyphicon glyphicon-th-list', resizable: true });
    $('#unidadDialog').closest('.ui-dialog').addClass('exa-dialog-custom');
    $('#laborDialog').createDialog({ height: 200, width: 420, icon: 'pencil' });
    if ($('#fincaDialog').length > 0) {
        $('#fincaDialog').createDialog({ height: 280, width: 440, icon: 'pencil' });
    }
    if ($('#dialogMapeoSector').length > 0) {
        $('#dialogMapeoSector').createDialog({ height: 560, width: 860, icon: 'glyphicon glyphicon-map-marker', resizable: true });
    }

    window.redimensionarGridsLaboresSectores = function() {
        if ($('#detaLabores').length > 0) {
            var $wrapLab = $('#detaLabores').closest('.exa-ui-grid-host, .exa-grid-container-card, #formDatosLabor');
            var wLab = $wrapLab.width();
            if (wLab && wLab > 80) {
                $('#detaLabores').jqGrid('setGridWidth', Math.floor(wLab - 2), true);
            }
        }
        if ($('#detaFincas').length > 0) {
            var $wrapFnc = $('#detaFincas').closest('.exa-ui-grid-host, .exa-grid-container-card, #formDatosFinca');
            var wFnc = $wrapFnc.width();
            if (wFnc && wFnc > 80) {
                $('#detaFincas').jqGrid('setGridWidth', Math.floor(wFnc - 2), true);
            }
        }
    };

    if ($('#detaLabores').length > 0) {
        var wLabInit = Math.floor(($('#detaLabores').closest('.exa-ui-grid-host, .exa-grid-container-card, #formDatosLabor').width() || 500) - 2);
        $('#detaLabores').createGrid(
            $.extend(opts, {
                height: 'auto',
                width: wLabInit > 100 ? wLabInit : 500,
                autowidth: false,
                shrinkToFit: true,
                responsive: true,
                caption: null,
                rownumbers: false
            }),
            true
        );
    }
    if ($('#detaFincas').length > 0) {
        var wFncInit = Math.floor(($('#detaFincas').closest('.exa-ui-grid-host, .exa-grid-container-card, #formDatosFinca').width() || 500) - 2);
        $('#detaFincas').createGrid(
            $.extend(optsFink, {
                height: 'auto',
                width: wFncInit > 100 ? wFncInit : 500,
                autowidth: false,
                shrinkToFit: true,
                responsive: true,
                caption: null,
                rownumbers: false
            }),
            true
        );
    }

    $(window).off('resize.gridLaboresSectores').on('resize.gridLaboresSectores', function() {
        if (typeof window.redimensionarGridsLaboresSectores === 'function') {
            window.redimensionarGridsLaboresSectores();
        }
    });
    setTimeout(function() {
        if (typeof window.redimensionarGridsLaboresSectores === 'function') {
            window.redimensionarGridsLaboresSectores();
        }
    }, 150);

    if ($('#tableActividad').length > 0) {
        var grid = $('#tableActividad');
        grid
            .createGrid(
                { caption: 'REGISTRO DE ACTIVIDADES', height: '350',
                    colModel: [
                        { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', hidden: true },
                        { label: 'Cod', name: 'Act_Cod', key: true, hidden: true },
                        { label: '<span class="required"></span> Trabajador', name: 'Personal', width: 45, align: 'center', title: true,
                            formatter: 'input2',
                            formatoptions: {
                                id: '1',
                                title: 'Buscar Trabajador',
                                action: 'abrirDialogPersonal',
                                data: function(o) {
                                    return o;
                                }
                            },
                            resizable: false
                        },
                        { label: '<span class="required"></span>Labor', name: 'Lab_Des', width: 30, align: 'center', title: true,
                            formatter: 'input2',
                            formatoptions: {
                                id: '1',
                                title: 'Buscar Labor',
                                action: 'abrirDialogLabor',
                                data: function(o) {
                                    return o;
                                }
                            },
                            resizable: false
                        },
                        { name: 'Lab_Cod', hidden: true, formatter: 'input3', formatoptions: { id: '3', attr: '' } },
                        { name: 'Per_Cod', hidden: true, formatter: 'input3', formatoptions: { id: '3', attr: '' } },
                        { label: 'Unidad', name: 'Tpg_Des', width: 20, align: 'center', title: false,
                            formatter: 'input3',
                            formatoptions: { id: '3', attr: '' }
                        },
                        { label: '<span class="required"></span> Fecha', name: 'Det_Fec', width: 15, align: "center", title: false,
                            formatter: 'input2',
                            formatoptions: { id: '2', attr: '' }
                        },
                        { label: '<span class="required"></span> Observaci&oacute;n', name: 'Det_Obs', width: 50, align: 'center',
                            title: false,
                            formatter: 'input2',
                            formatoptions: { id: '2', attr: '' }
                        },
                        { label: 'P. Unitario', name: 'Lab_Val', width: 15, align: 'center', title: false,
                            formatter: 'input4',
                            formatoptions: { id: '2', attr: '' }
                        },
                        { label: '<span class="required"></span> Cantidad', name: 'Det_Can', width: 15, align: 'center', title: false,
                            formatter: 'inputN',
                            formatoptions: { id: '2', attr: '' }
                        },
                        { label: 'Total', name: 'Total', width: 15, align: 'right', title: false,
                            formatter: 'input4',
                            formatoptions: { id: '2', attr: '' }
                        },
                        { name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 10, align: 'center', viewable: false,
                            formatter: 'gridButton',
                            formatoptions: {
                                action: quitarActividad,
                                /*conditional: function(o) { console.log(o); return o.tarja === 'n' && o.Prh_Est !== 'I'; },*/
                                icon: 'remove',
                                type: 'danger',
                                title: 'Eliminar Item',
                                data: function(o) {
                                    return o;
                                }
                            },
                            resizable: false
                        }
                    ],
                    footerrow: true,
                    loadComplete: function () {
                        $(this).setGridSummary(['Total'],{
                            Det_Can: '<div style="text-align:right;">Total:</div>'
                        });
                    },
                    pgbuttons: false,
                    pgtext: null,
                    beforeSelectRow: function(rowid, e) {
                        return false;
                    }
                },
                true,
                '#tableActividadPager', { view: false, refresh: false }
            )
            .gridButtonAdd(
                { caption: 'Agregar Trabajador', id: 'btn_agr', buttonicon: 'glyphicon glyphicon-plus', title: 'Agregar',
                    onClickButton: function() {
                        agregarFila(0);
                    }
                }
            );
    }

    // Grid de la modificacin tableActividadMod tableActividadModPager
    if ($('#tableActividadMod').length > 0) {
        $('#tableActividadMod').createGrid({
                    caption: '*REGISTRO DE ACTIVIDADES',
                    height: '350',
                    colModel: [
                        { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', hidden: true },
                        { label: 'Cod', name: 'Act_Cod', key: true, hidden: true },
                        { label: 'Det_Cod', name: 'Det_Cod', key: true, hidden: true },
                        { label: '<span class="required"></span> Trabajador', name: 'Personal', width: 45, align: 'center', title: true,
                            formatter: 'input2',
                            formatoptions: {
                                id: '1',
                                title: 'Buscar Trabajador',
                                action: 'abrirDialogPersonal',
                                data: function(o) {
                                    return o;
                                }
                            },
                            resizable: false
                        },
                        { label: '<span class="required"></span>Labor', name: 'Lab_Des', width: 30, align: 'center', title: true,
                            formatter: 'input2',
                            formatoptions: {
                                id: '1',
                                title: 'Buscar Labor',
                                action: 'abrirDialogLabor',
                                data: function(o) {
                                    return o;
                                }
                            },
                            resizable: false
                        },
                        { name: 'Lab_Cod', hidden: true, formatter: 'input3',
                            formatoptions: { id: '3', attr: '' }
                        },
                        { name: 'Per_Cod', hidden: true, formatter: 'input3',
                            formatoptions: { id: '3', attr: '' }
                        },
                        { label: 'Unidad', name: 'Tpg_Des', width: 20, align: 'center', title: false,
                            formatter: 'input3',
                            formatoptions: { id: '3', attr: '' }
                        },
                        { label: '<span class="required"></span> Fecha', name: 'Det_Fec_Mod', width: 20, align: "center", title: false,
                            formatter: 'input2',
                            formatoptions: { id: '2', attr: '' }
                        },
                        { label: '<span class="required"></span> Observaci&oacute;n', name: 'Det_Obs', width: 45, align: 'center', title: false,
                            formatter: 'input2',
                            formatoptions: { id: '2', attr: '' }
                        },
                        { label: 'P. Unitario', name: 'Lab_Val', width: 15, align: 'center', title: false, formatter: 'input4', formatoptions: { id: '2', attr: '' } },
                        { label: '<span class="required"></span> Cantidad', name: 'Det_Can_Mod', width: 15, align: 'center', title: false,
                            formatter: 'inputN',
                            formatoptions: { id: '2', attr: '' }
                        },
                        { label: 'Total', name: 'Total', width: 15, align: 'right', title: false,
                            formatter: 'input4',
                            formatoptions: { id: '2', attr: '' }
                        },
                        { name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 10, align: 'center', viewable: false,
                            formatter: 'gridButton',
                            formatoptions: {
                                action: quitarActividadMod,
                                /*conditional: function(o) { console.log(o); return o.tarja === 'n' && o.Prh_Est !== 'I'; },*/
                                icon: 'remove',
                                type: 'danger',
                                title: 'Eliminar Item',
                                data: function(o) {
                                    return o;
                                }
                            },
                            resizable: false
                        }
                    ],footerrow: true,
                    loadComplete: function () { $(this).setGridSummary(['Total']); },
                    pgbuttons: false,
                    rowNum: 10000,
                    pgtext: null,
                    beforeSelectRow: function(rowid, e) {
                        return false;
                    }
                },
                true,
                '#tableActividadModPager', { view: false, refresh: false }
            )
            .gridButtonAdd(
                { caption: 'Agregar Trabajador', id: 'btn_agr', buttonicon: 'glyphicon glyphicon-plus', title: 'Agregar',
                    onClickButton: function() {
                        agregarFila(1);
                    }
                }
            );
    }

    //Tabla modificar actividades
    if ($('#consultarGrid').length > 0) {
        var gridModAct = $('#consultarGrid');
        gridModAct.createGrid({
            height: 300,
            datatype: "local",
            regional: 'es',
            shrinkToFit: true,
            colModel: [
                { label: 'C&oacute;d. Int.', name: 'Act_Cod', width: 10, key: true, hidden: false, align: "center", viewable: true },
                { name: "Fnc_Cod", hidden: true },
                { label: 'Trabajador', name: 'personal', width: 55, align: "center" },
                { label: 'Sector', name: 'Fnc_Des', width: 55, align: "center" },
                { label: 'Fecha', name: 'Act_Fec', width: 50, align: "left" },
                { label: 'Semana', name: 'Semana', width: 55, align: "center" },
                { label: $.createIcon('info-sign'), name: 'actInfo', align: "center", width: 7, viewable: false, formatter: 'gridButton',
                    formatoptions: { action: viewInfo, icon: 'info-sign', type: 'info', title: 'Info' },
                    title: false, resizable: false
                },
                { label: $.createIcon('glyphicon glyphicon-pencil'), name: 'actEdt', align: "center", viewable: false, width: 7, formatter: 'gridButton',
                    formatoptions: { action: editActividad, icon: 'glyphicon glyphicon-pencil', type: 'success', title: 'Modificar Actividad', resizable: false }
                }
            ],
            pager: "#cgPager",
            rownumbers: true,
            rowNum: 10000,
            gridview: false,
            viewrecords: false,
            footerrow: false,
            userDataOnFooter: false,
            loadComplete: function(data) {
                busquedaInicial();
            }
        }, false, "#cgPager", { view: false, refresh: false });
    }

    $.fn.fmatter.input2 = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '1') {
            el = $(
                '<div class="input-group input-group-xs ret"><input type="text" id="' +
                opts['rowId'] + '_' +
                opts['colModel']['name'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs ' +
                set['class'] +
                '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' +
                set['title'] +
                '" onclick="' +
                set['action'] +
                '(' +
                opts['rowId'] +
                ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'
            );
        } else {
            el = $(
                '<input type="text" id="' +
                opts['rowId'] +
                '_' +
                opts['colModel']['name'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs" ' +
                set['attr'] +
                '/>'
            );
        }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input2.unformat = function(cv, opts, cObjt) {
        return $(cObjt).find(':input').val();
    };
    $.fn.fmatter.inputN = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '1') {
            el = $(
                '<div class="input-group input-group-xs ret"><input type="number" step="0.01" min="0" id="' +
                opts['rowId'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs ' +
                set['class'] +
                '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' +
                set['title'] +
                '" onclick="' +
                set['action'] +
                '(' +
                opts['rowId'] +
                ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'
            );
        } else {
            el = $(
                '<input type="number"  style="text-align: right;" step="0.01" pattern="^\d+(?:\.\d{1,3})?$" min="0" id="' +
                opts['rowId'] +
                '_' +
                opts['colModel']['name'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs" ' +
                set['attr'] +
                '/>'
            );
        }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.inputN.unformat = function(cv, opts, cObjt) {
        return $(cObjt).find(':input').val();
    };

    $.fn.fmatter.input3 = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '2') {
            el = $(
                '<div class="input-group input-group-xs ret"><input type="text" id="' +
                opts['rowId'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs ' +
                set['class'] +
                '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' +
                set['title'] +
                '" onclick="' +
                set['action'] +
                '(' +
                opts['rowId'] +
                ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'
            );
        } else {
            el = $(
                '<input type="text" style="text-align: center;" id="' +
                opts['rowId'] +
                '_' +
                opts['colModel']['name'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs ret2" ' +
                set['attr'] +
                'readonly/>'
            );
        }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input3.unformat = function(cv, opts, cObjt) {
        return $(cObjt).find(':input').val();
    };

    $.fn.fmatter.input4 = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '1') { el = $('<div class="input-group input-group-xs ret"><input type="text" id="' + opts['rowId'] + '" class="form-control input-xs ' + set['class'] + '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' + set['title'] + '" onclick="' + set['action'] + '(' + opts['rowId'] + ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'); } else { el = $('<input type="text"   style="text-align: right;" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs" ' + set['attr'] + 'readonly />'); }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input4.unformat = function(cv, opts, cObjt) { return $(cObjt).find(':input').val(); };
    //agregarFila(0);


    if ($('#dialogInfo').length > 0)
        $('#dialogInfo').createDialog({ height: 140, width: 600, noTitleStuff: false, noBorder: true });
});
if ($('#laboresDialog').length > 0) {
    $.createSearchDialog(
        'laboresDialog', [
            { label: 'C&oacute;d. Int.', name: 'Lab_Cod', key: true, width: 15, align: 'center', hidden: true },
            { label: 'Descripci&oacute;n', name: 'Lab_Des', width: 100 },
            { label: 'Unidad', name: 'Tpg_Des', width: 60 },
            { label: 'P.Unitario', name: 'Lab_Val', width: 50 },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false,
                formatter: 'gridButton',
                formatoptions: { action: selectLabor }
            }
        ], null, null, null, { headertitles: true },
        {
            title: 'Labores',
            options: [
                { label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' },
                { label: '&nbsp;&nbsp;Unidad&nbsp;&nbsp;', value: 'c' }
            ]
        }
    );
}

if ($('#personalDialog').length > 0) {
    $.createSearchDialog(
        '#personalDialog', [
            { label: 'C&oacute;d. Int.', name: 'Per_Cod', key: true, width: 15, align: 'center', hidden: true },
            { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Personal', name: 'Personal', width: 100 },
            { label: 'Direcci&oacute;n', name: 'Prs_Dir', width: 60 },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false,
                formatter: 'gridButton',
                formatoptions: { action: selectPersonal }
            }
        ], null, null, null, { headertitles: true },
        { title: 'Personal',
            options: [
                { label: '&nbsp;&nbsp;Apellido/Nombre&nbsp;&nbsp;', value: 'd' },
                { label: '&nbsp;&nbsp;C&eacute;dula/R.U.C&nbsp;&nbsp;', value: 'c' }
            ]
        }
    );
}


function saveData(formulario, accion, dialogo) {
    var data = $('#' + formulario).getData('save');
    var vfG = false,
        selectSemana = '',
        selectFinca = '',
        responsable = '',
        cambio = '',
        indiceAct = 0;
    var shortDateFormat = 'yy-mm-dd';
    data[accion] = true;
    if (dialogo === 'actividad') {
        if (formulario === "frm_alt_actividad") {
            data['actividades'] = $('#tableActividad').getGridBatch();
            selectSemana = $('#' + formulario).find('#Act_Sem').find('option:selected');
            selectFinca = $('#' + formulario).find('#Fnc_Cod_D').find('option:selected');
            responsable = $('#' + formulario).find('#Act_Res').val();
        }
        if (formulario === "frm_mod_act_edi") {
            data['actividades'] = $('#tableActividadMod').getGridBatch();
            selectSemana = $('#' + formulario).find('#Act_Sem').find('option:selected');
            selectFinca = $('#' + formulario).find('#Fnc_Cod').find('option:selected');
            responsable = $('#' + formulario).find('#Act_Res').val();
            cambio = true;
        }

        //console.log(responsable);
        if ((selectSemana.val()) * 1 <= 0) { $.alert('Debe Seleccionar una semana!<br/>Revise los datos.', null, 'remove'); return false; }
        if ((selectFinca.val()) * 1 <= 0) { $.alert('Debe Seleccionar una finca!<br/>Revise los datos.', null, 'remove'); return false; }
        if (responsable === '') { $.alert('Asignar un responsable!<br/>Revise los datos.', null, 'remove'); return false; }
        console.log(data['actividades']);
        $.each(data['actividades'], function(pos, valor) {
            //console.log(valor['Lab_Cod']);
            //console.log(valor['Lab_Des']);
            //console.log(valor['Lab_Val']);
            //console.log(valor['Per_Cod']);
            //console.log(valor['Personal']);
            //console.log(valor['Total']);
            //console.log(valor['Tpg_Des']);


            if (valor['Lab_Cod'] === '' || valor['Lab_Des'] === '' || valor['Lab_Val'] === '' || valor['Per_Cod'] === '' || valor['Personal'] === '' || valor['Total'] === '' || valor['Tpg_Des'] === '' || parseFloat(valor['Total']) * 1 === 0) {
                if (formulario === "frm_mod_act_edi") { index = $("#tableActividadMod").jqGrid('getInd', valor['index']); }
                if (formulario === "frm_alt_actividad") { index = $("#tableActividad").jqGrid('getInd', valor['index']); }

                indiceAct = index;
                vfG = true;
                return false;
            }
        });
        if (vfG) {
            $.alert('Verifique la informaci&oacute;n en la fila: ' + indiceAct);
            return false;
            vfG = false;
        }
        if ((data['actividades'].length) < 1) { $.alert('Debe existir al menos un registro en registro de actividades..!!'); return false; }


    }
    $.arraySpliceFields(data['actividades'], ['index', 'Personal', 'Lab_Des', 'delete']);
    $.createDialogConfirm('&iquest;Est&aacute; seguro que desea guardar los cambios?', null, function() {
        $.saveDataJson('', data, function(resp) {
            if (resp['success']) {
                console.log(resp);
                if (dialogo === 'unidad') {
                    searchLaborPago(); /*$('.select_unidad').append($('<option>', { value: resp['tipoPago']['Tpg_Cod'], text: resp['tipoPago']['Tpg_Des'] }));*/
                }
                if (formulario === 'frmFinca') {
                    $('.select_finca').append($('<option>', { value: resp['finca']['Fnc_Cod'], text: resp['finca']['Fnc_Des'] }));
                }
                $('#' + formulario)[0].reset();
                if (formulario === 'frmFinca') {
                    $('#Fnc_Des').prop('readonly', true);
                    $('#Fnc_Coords_Badge').hide().html('');
                    $('#sel_ubicacion_generada').val('0');
                }
                $('#' + dialogo + 'Dialog').dialog('close');
                $('.select_unidad option:selected').removeAttr('selected');
                $('#Tpg_Cod').val('').trigger('chosen:updated');
                $('input:text[name=Act_Fec]').val($.datepicker.formatDate(shortDateFormat, new Date()));
                $('#tableActividad').clearGrid();
                $('#tableActividadMod').clearGrid();
                if (cambio) { $('#divEdic').moveComp('#tab3').updateGridsSizes(); }
                $.alert('La transacci&oacute;n se realiz&oacute; con &eacute;xito.');
                //$("#Tpg_Cod").find('option').removeAttr("selected");
                //$("#Tpg_Cod").val([]);
                refreshData();
                return false;
            }
        });
    });
}



function refreshData() {
    $('#detaLabores').clearGrid();
    $('#detaFincas').clearGrid();
    Promise.all([loadDataTable(), loadDataFincas()]);
    /* loadDataTable();
    loadDataFincas(); */

}

function resolverGridActividad(rowId) {
    var id = (rowId !== undefined && rowId !== null && rowId !== '') ? String(rowId) : '';
    var enCrear = id !== '' && $('#tableActividad').jqGrid('getInd', id) !== false;
    var enMod = id !== '' && $('#tableActividadMod').jqGrid('getInd', id) !== false;

    if (esCrear && !esMod) {
        return {
            nameGrid: 'tableActividad',
            nameForm: 'frm_alt_actividad',
            parametro: '_Det_Can',
            fecha: '_Det_Fec',
            calc: 0
        };
    }
    if (esMod && !esCrear) {
        return {
            nameGrid: 'tableActividadMod',
            nameForm: 'frm_mod_act_edi',
            parametro: '_Det_Can_Mod',
            fecha: '_Det_Fec_Mod',
            calc: 1
        };
    }
    if (enCrear && !enMod) {
        return {
            nameGrid: 'tableActividad',
            nameForm: 'frm_alt_actividad',
            parametro: '_Det_Can',
            fecha: '_Det_Fec',
            calc: 0
        };
    }
    if (enMod && !enCrear) {
        return {
            nameGrid: 'tableActividadMod',
            nameForm: 'frm_mod_act_edi',
            parametro: '_Det_Can_Mod',
            fecha: '_Det_Fec_Mod',
            calc: 1
        };
    }
    // Fallback: tab visible / crear por defecto
    if ($('#tabs-2').is(':visible') || $('#tableActividad').length) {
        return {
            nameGrid: 'tableActividad',
            nameForm: 'frm_alt_actividad',
            parametro: '_Det_Can',
            fecha: '_Det_Fec',
            calc: 0
        };
    }
    return {
        nameGrid: 'tableActividadMod',
        nameForm: 'frm_mod_act_edi',
        parametro: '_Det_Can_Mod',
        fecha: '_Det_Fec_Mod',
        calc: 1
    };
}

function abrirDialogPersonal(personal) {
    $('#personalDialog').dialog('open');
    //console.log('personal', personal);
    $('#CodFormBus').val(personal);
}

function abrirDialogLabor(labor) {
    var id = labor;
    var ctx = resolverGridActividad(id);
    var trabajador_data = $('#' + ctx.nameGrid).jqGrid('getRowData', id) || {};
    var perCod = trabajador_data['Per_Cod'] || $('#' + ctx.nameGrid).find('#' + id + '_Per_Cod').val() || '';

    if (!id || (id * 1) <= 0) {
        $.alert('Debe seleccionar un Trabajador antes.!!');
        return;
    }
    if (perCod === '' || perCod === '0' || perCod === 0) {
        $.alert('Debe seleccionar un Trabajador antes.!!');
        return;
    }

    $('#CodFormBusLab').val(labor);
    $('#laboresDialog').dialog('open');
}

function agregarFila(aux) {

    if (aux > 0) {
        esMod = true;
        esCrear = false;
        var $this = $('#tableActividadMod');
        var campoGrid = '_Det_Can_Mod';
        var fecha = '_Det_Fec_Mod';
        var $form = 'frm_mod_act_edi';
    } else {
        esCrear = true;
        esMod = false;
        var $this = $('#tableActividad');
        var campoGrid = '_Det_Can';
        var fecha = '_Det_Fec'
        var $form = 'frm_alt_actividad';
    }
    var sel_fecha = $('#' + $form).find("#Pec_Cod").find('option:selected');
    var id = $this.nextIndex();
    $this.jqGrid('addRowData', id, { index: id });
    $this.jqGrid('editRow', id);
    $.createDatePickers('#' + id + fecha);
    $this.find('tr#' + id).find('#' + id + '_Personal').focus();
    $('#' + id + fecha).dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
    $this.find('tr#' + id).find('#' + id + campoGrid).on('change', function() {
        //console.log('entro en el change');
        var vlInput = validaDecimal($this.find('tr#' + id).find('#' + id + campoGrid).val());
        if (vlInput) {
            $.alert('El valor de la cantidad debe ser mayor que 0');
        }
        makeCalculation(aux > 0 ? 1 : 0);
        $this.find('tr#' + id).find('#' + id + campoGrid).focus();

    }).trigger('change');

}

function validaDecimal(valor) {
    var verifica = false;
    if (parseFloat(valor) * 1 <= 0) {
        verifica = true;
    }
    return verifica;
}

function quitarActividad(row) {
    $.createDialogConfirm('Desea Eliminar el item seleccionado..!!', null, function() {
        $('#tableActividad').jqGrid('delRowData', row.id);
    });
}

function quitarActividadMod(row) {
    $.createDialogConfirm('Desea Eliminar el item seleccionado..!!', null, function() {
        $('#tableActividadMod').jqGrid('delRowData', row.id);
    });
}

function delLabor(row) {
    $.createDialogConfirm('&iquest;Desea eliminar la labor seleccionada?', null, function() {
        $.saveDataJson("", { elimLabor: true, Lab_Cod: row['Lab_Cod'] }, (respuesta) => {
            if (respuesta['success']) {
                $('#detaLabores').jqGrid('delRowData', row.id);
                //$('#formDatosLabor').updateGridsSizes();
                $('#detaLabores').trigger("reloadGrid");
                $.alert('La transacci&oacute;n se realiz&oacute; con &eacute;xito.');
                return false;
            }
        });

    });

}

function updLabor(row) {
    $("#Lab_Cod_Upd").val(row.id);
    $('#laborDialog').dialog('open');
    $('#Lab_Des_Upd').val(sanitizarTexto(row.Lab_Des || ''));
    $('#Tpg_Cod_Id').val(row.Tpg_Cod);
    $('#Lab_Val_Upd').val(row.Lab_Val);
}

function delFinca(row) {
    $.createDialogConfirm('&iquest;Desea anular el sector seleccionado?', null, function() {
        $.saveDataJson("", { elimFinca: true, Fnc_Cod: row['Fnc_Cod'] }, (respuesta) => {
            if (respuesta['success']) {
                $('#detaFincas').jqGrid('delRowData', row.id);
                $('#detaFincas').trigger("reloadGrid");
                $(".select_finca option[value='" + row['Fnc_Cod'] + "']").remove();
                $.alert('La transacci&oacute;n se realiz&oacute; con &eacute;xito.');
                return false;
            }
        });
    });
}

function updFinca(row) {
    if ($('#fincaDialog').length === 0) {
        $.alert('No se encontr&oacute; el formulario de edici&oacute;n de sector.');
        return;
    }
    var codFnc = row.Fnc_Cod || row.id;
    $("#Fnc_Cod_Upd").val(codFnc);
    $('#Fnc_Des_Upd').val(sanitizarTexto(row.Fnc_Des || '')).prop('readonly', true);
    $('#Fnc_Dir_Upd').val(sanitizarTexto(row.Fnc_Dir || ''));
    $('#Fnc_Hec_Upd').val((row.Fnc_Hec === undefined || row.Fnc_Hec === null || row.Fnc_Hec === '') ? 0 : row.Fnc_Hec);
    $('#Fnc_Lat_Upd').val((row.Fnc_Lat !== undefined && row.Fnc_Lat !== null && row.Fnc_Lat !== '') ? row.Fnc_Lat : '');
    $('#Fnc_Lng_Upd').val((row.Fnc_Lng !== undefined && row.Fnc_Lng !== null && row.Fnc_Lng !== '') ? row.Fnc_Lng : '');
    $('#Fnc_Geo_JSON_Upd').val((row.Fnc_Geo_JSON !== undefined && row.Fnc_Geo_JSON !== null) ? (typeof row.Fnc_Geo_JSON === 'string' ? row.Fnc_Geo_JSON : JSON.stringify(row.Fnc_Geo_JSON)) : '');
    if ($('#sel_ubicacion_generada_upd').length) {
        $('#sel_ubicacion_generada_upd').val(codFnc);
    }
    $('#fincaDialog').dialog('open');
}

var modoMapeoActual = 'nuevo'; // 'nuevo', 'editar', 'ver', 'general', 'actividad', 'actividad_mod'

function abrirModalMapeoSector(modo) {
    modoMapeoActual = modo || 'nuevo';
    var url = '../../mapeo/FRONT/map_alt_mapeo_interactivo.php?modo=selector&ts=' + new Date().getTime();
    var curLat = '', curLng = '';
    if (modo === 'editar') {
        curLat = $('#Fnc_Lat_Upd').val();
        curLng = $('#Fnc_Lng_Upd').val();
    } else if (modo === 'nuevo') {
        curLat = $('#Fnc_Lat').val();
        curLng = $('#Fnc_Lng').val();
    } else if (modo === 'actividad') {
        var opt = $('#Fnc_Cod_D option:selected');
        curLat = opt.data('lat') || '';
        curLng = opt.data('lng') || '';
    } else if (modo === 'actividad_mod') {
        var opt = $('#Fnc_Cod option:selected');
        curLat = opt.data('lat') || '';
        curLng = opt.data('lng') || '';
    }
    if (curLat && curLng) {
        url += '&lat=' + encodeURIComponent(curLat) + '&lng=' + encodeURIComponent(curLng) + '&zoom=20';
    }
    $('#dialogMapeoIndicacion').html('<i class="glyphicon glyphicon-info-sign text-info"></i> Modo Selecci&oacute;n: Haga clic en cualquier punto del mapa para capturar sus coordenadas, o elija un sector existente.');
    if ($('#dialogMapeoSector').length > 0) {
        $('#iframeMapeoSector').attr('src', url);
        $('#dialogMapeoSector').dialog('open');
    } else {
        window.open(url, '_blank');
    }
}

function abrirMapeoParaNuevaLocacion(modo) {
    abrirModalMapeoSector(modo);
}

function recargarSelectUbicaciones(callback) {
    getFincas().then(function(lista) {
        if (typeof loadDataFincas === 'function' && $('#detaFincas').length > 0) {
            loadDataFincas();
        }
        $.alert('Lista de ubicaciones georreferenciadas actualizada (' + (lista ? lista.length : 0) + ' puntos disponibles).');
        if (typeof callback === 'function') callback(lista);
    }).catch(function(err) {
        $.alert('Error al recargar las ubicaciones.');
    });
}

function abrirModalMapeoGeneral() {
    modoMapeoActual = 'general';
    var url = '../../mapeo/FRONT/map_alt_mapeo_interactivo.php?modo=selector&ts=' + new Date().getTime();
    $('#dialogMapeoIndicacion').html('<i class="glyphicon glyphicon-globe text-success"></i> Mapa Satelital HD de Sectores y Operaciones en Relavera El Tabl&oacute;n.');
    if ($('#dialogMapeoSector').length > 0) {
        $('#iframeMapeoSector').attr('src', url);
        $('#dialogMapeoSector').dialog('open');
    } else {
        window.open(url, '_blank');
    }
}

function verSectorEnMapa(lat, lng, nombreEnc, sectorId) {
    modoMapeoActual = 'ver';
    var nombre = sanitizarTexto(decodeURIComponent(nombreEnc || ''));
    var cod = sectorId || 0;
    var url = '../../mapeo/FRONT/map_alt_mapeo_interactivo.php?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng) + '&zoom=20&modo=ver&sector_id=' + encodeURIComponent(cod) + '&ts=' + new Date().getTime();
    $('#dialogMapeoIndicacion').html('<i class="glyphicon glyphicon-map-marker text-success"></i> Visualizando Sector: <b>' + escaparHTML(nombre || 'Georreferenciado') + '</b> (Lat: ' + lat.toFixed(5) + ', Lng: ' + lng.toFixed(5) + ')');
    if ($('#dialogMapeoSector').length > 0) {
        $('#iframeMapeoSector').attr('src', url);
        $('#dialogMapeoSector').dialog('open');
    } else {
        window.open(url, '_blank');
    }
}

function cerrarModalMapeoSector() {
    if ($('#dialogMapeoSector').length > 0) {
        $('#dialogMapeoSector').dialog('close');
    }
}

function recargarIframeMapeo() {
    var ifr = document.getElementById('iframeMapeoSector');
    if (ifr) ifr.src = ifr.src;
}

function limpiarCoordsSector(modo) {
    if (modo === 'editar') {
        $('#Fnc_Lat_Upd').val('');
        $('#Fnc_Lng_Upd').val('');
        $('#Fnc_Geo_JSON_Upd').val('');
    } else {
        $('#Fnc_Lat').val('');
        $('#Fnc_Lng').val('');
        $('#Fnc_Geo_JSON').val('');
        $('#Fnc_Coords_Badge').hide();
    }
}

// Listener para eventos recibidos desde el mapa interactivo (iframe)
window.addEventListener('message', function(event) {
    if (!event.data || typeof event.data !== 'object') return;
    var d = event.data;

    if (d.type === 'COORDENADA_CAPTURADA') {
        var lat = parseFloat(d.lat);
        var lng = parseFloat(d.lng);
        if (isNaN(lat) || isNaN(lng)) return;

        if (modoMapeoActual === 'editar') {
            $('#Fnc_Lat_Upd').val(lat.toFixed(8));
            $('#Fnc_Lng_Upd').val(lng.toFixed(8));
            if (d.geometria) {
                $('#Fnc_Geo_JSON_Upd').val(typeof d.geometria === 'string' ? d.geometria : JSON.stringify(d.geometria));
            }
        } else {
            $('#Fnc_Lat').val(lat.toFixed(8));
            $('#Fnc_Lng').val(lng.toFixed(8));
            if (d.geometria) {
                $('#Fnc_Geo_JSON').val(typeof d.geometria === 'string' ? d.geometria : JSON.stringify(d.geometria));
            }
            $('#Fnc_Coords_Badge').show().html('<i class="glyphicon glyphicon-ok-sign"></i> GPS: ' + lat.toFixed(5) + ', ' + lng.toFixed(5));
            if (d.nombre && !$('#Fnc_Des').val()) {
                $('#Fnc_Des').val(sanitizarTexto(d.nombre));
            }
            if (d.direccion && !$('#Fnc_Dir').val()) {
                $('#Fnc_Dir').val(sanitizarTexto(d.direccion));
            }
        }
        if ($('#dialogMapeoSector').length > 0) {
            $('#dialogMapeoSector').dialog('close');
        }
        $.alert('Coordenadas capturadas desde el mapa:<br>Lat: <b>' + lat.toFixed(6) + '</b>, Lng: <b>' + lng.toFixed(6) + '</b>');
    }

    if (d.type === 'SECTOR_SELECCIONADO') {
        var secId = d.id;
        var secNom = sanitizarTexto(d.nombre || '');
        var secDir = sanitizarTexto(d.direccion || '');
        var lat = parseFloat(d.lat);
        var lng = parseFloat(d.lng);

        if (modoMapeoActual === 'nuevo') {
            if (!$('#Fnc_Des').val()) $('#Fnc_Des').val(secNom);
            if (!$('#Fnc_Dir').val()) $('#Fnc_Dir').val(secDir);
            if (!isNaN(lat) && !isNaN(lng)) {
                $('#Fnc_Lat').val(lat.toFixed(8));
                $('#Fnc_Lng').val(lng.toFixed(8));
                $('#Fnc_Coords_Badge').show().html('<i class="glyphicon glyphicon-ok-sign"></i> GPS: ' + lat.toFixed(5) + ', ' + lng.toFixed(5));
            }
            if ($('#sel_ubicacion_generada').length) {
                $('#sel_ubicacion_generada').val(secId);
            }
        } else if (modoMapeoActual === 'editar') {
            if (!$('#Fnc_Des_Upd').val()) $('#Fnc_Des_Upd').val(secNom);
            if (!$('#Fnc_Dir_Upd').val()) $('#Fnc_Dir_Upd').val(secDir);
            if (!isNaN(lat) && !isNaN(lng)) {
                $('#Fnc_Lat_Upd').val(lat.toFixed(8));
                $('#Fnc_Lng_Upd').val(lng.toFixed(8));
            }
            if ($('#sel_ubicacion_generada_upd').length) {
                $('#sel_ubicacion_generada_upd').val(secId);
            }
        } else if (modoMapeoActual === 'actividad') {
            if ($('#Fnc_Cod_D').length) {
                $('#Fnc_Cod_D').val(secId).trigger('change');
            }
        } else if (modoMapeoActual === 'actividad_mod') {
            if ($('#Fnc_Cod').length) {
                $('#Fnc_Cod').val(secId).trigger('change');
            }
        }
        if ($('#dialogMapeoSector').length > 0) {
            $('#dialogMapeoSector').dialog('close');
        }
        $.alert('Sector seleccionado: <b>' + escaparHTML(secNom) + '</b>');
    }

    if (d.type === 'SECTOR_CREADO') {
        if (typeof refreshData === 'function') {
            refreshData();
        }
        getFincas().then(function() {
            if (typeof loadDataFincas === 'function' && $('#detaFincas').length > 0) {
                loadDataFincas();
            }
            if (d.id) {
                if (modoMapeoActual === 'actividad' && $('#Fnc_Cod_D').length) {
                    $('#Fnc_Cod_D').val(d.id).trigger('change');
                } else if (modoMapeoActual === 'actividad_mod' && $('#Fnc_Cod').length) {
                    $('#Fnc_Cod').val(d.id).trigger('change');
                } else if (modoMapeoActual === 'nuevo' && $('#sel_ubicacion_generada').length) {
                    $('#sel_ubicacion_generada').val(d.id).trigger('change');
                }
            }
        });
        if ($('#dialogMapeoSector').length > 0) {
            $('#dialogMapeoSector').dialog('close');
        }
        $.alert('Nuevo sector creado en el mapa y sincronizado en el listado.');
    }
});


function generarSemanas() {
    const contenido = 'Semana ';
    for (var i = 1; i < 53; i++) {
        $('.select_semna').append($('<option>', { value: i, text: contenido + '' + i }));
    }
}

function getFincas() {
    return new Promise((resolve, reject) => {
        $.getDataJson('', { fincasAjax: true }, (resultado) => {
            if (resultado && resultado.listaFincas) {
                $('.select_finca').each(function() {
                    var $sel = $(this);
                    var currentVal = $sel.val();
                    $sel.empty();
                    $sel.append($('<option>', { value: '0', text: '-- Seleccione Sector / Ubicaci\u00f3n --' }));
                    resultado.listaFincas.forEach((valor) => {
                        var nomFnc = sanitizarTexto(valor['Fnc_Des'] || '');
                        var dirFnc = sanitizarTexto(valor['Fnc_Dir'] || '');
                        var texto = nomFnc;
                        var lat = parseFloat(valor['Fnc_Lat']);
                        var lng = parseFloat(valor['Fnc_Lng']);
                        if (!isNaN(lat) && !isNaN(lng) && lat !== 0) {
                            texto += ' [Lat: ' + lat.toFixed(4) + ', Lng: ' + lng.toFixed(4) + ']';
                        }
                        var opt = $('<option>', { value: valor['Fnc_Cod'], text: texto });
                        opt.data('nombre', nomFnc);
                        opt.data('direccion', dirFnc);
                        opt.data('hec', (valor['Fnc_Hec'] !== undefined && valor['Fnc_Hec'] !== null) ? valor['Fnc_Hec'] : '');
                        opt.data('lat', valor['Fnc_Lat']);
                        opt.data('lng', valor['Fnc_Lng']);
                        $sel.append(opt);
                    });
                    if (currentVal && currentVal !== '0') {
                        $sel.val(currentVal);
                    }
                });
                resolve(resultado.listaFincas);
            } else {
                resolve([]);
            }
        }, (err) => {
            reject(err);
        });
    });
}

function seleccionarUbicacionGenerada(sel, modo) {
    var $opt = $(sel).find('option:selected');
    var val = $opt.val();
    if (!val || val === '0') {
        if (modo === 'editar') {
            $('#Fnc_Des_Upd').val('').prop('readonly', true);
            $('#Fnc_Dir_Upd').val('');
            $('#Fnc_Hec_Upd').val('');
            $('#Fnc_Lat_Upd').val('');
            $('#Fnc_Lng_Upd').val('');
        } else {
            $('#Fnc_Des').val('').prop('readonly', true);
            $('#Fnc_Dir').val('');
            $('#Fnc_Hec').val('');
            $('#Fnc_Lat').val('');
            $('#Fnc_Lng').val('');
            $('#Fnc_Coords_Badge').hide().html('');
        }
        return;
    }

    var nombre = sanitizarTexto($opt.data('nombre') || $opt.text().replace(/\s*\[Lat:.*$/, '').replace(/\s*\uD83D\uDCCD.*$/, '').trim());
    var direccion = sanitizarTexto($opt.data('direccion') || '');
    var hec = $opt.data('hec');
    var lat = $opt.data('lat');
    var lng = $opt.data('lng');

    if (modo === 'editar') {
        $('#Fnc_Des_Upd').val(nombre).prop('readonly', true);
        $('#Fnc_Dir_Upd').val(direccion);
        if (hec !== undefined && hec !== null && hec !== '') {
            $('#Fnc_Hec_Upd').val(hec);
        }
        if (lat && lng && lat !== '' && lng !== '') {
            $('#Fnc_Lat_Upd').val(parseFloat(lat).toFixed(8));
            $('#Fnc_Lng_Upd').val(parseFloat(lng).toFixed(8));
        }
    } else {
        $('#Fnc_Des').val(nombre).prop('readonly', true);
        $('#Fnc_Dir').val(direccion);
        if (hec !== undefined && hec !== null && hec !== '') {
            $('#Fnc_Hec').val(hec);
        }
        if (lat && lng && lat !== '' && lng !== '') {
            $('#Fnc_Lat').val(parseFloat(lat).toFixed(8));
            $('#Fnc_Lng').val(parseFloat(lng).toFixed(8));
            $('#Fnc_Coords_Badge').show().html('<i class="glyphicon glyphicon-ok-sign"></i> GPS Cargado: ' + parseFloat(lat).toFixed(5) + ', ' + parseFloat(lng).toFixed(5));
        }
    }
}

function loadDataTable() {
    var next = $("#detaLabores").jqGrid('getCol', 'index', false, 'max');
    next = (isNaN(next) ? 1 : next + 1);

    return new Promise((resolve, reject) => {
        //
        $.getDataJson(
            '', { laborAjax: true },
            function(resultado) {
                if (resultado.listLab && resultado.listLab.length > 0) {
                    var limpias = resultado.listLab.map(function(item) {
                        if (item.Lab_Des) item.Lab_Des = sanitizarTexto(item.Lab_Des);
                        if (item.Tpg_Des) item.Tpg_Des = sanitizarTexto(item.Tpg_Des);
                        return item;
                    });
                    resolve($('#detaLabores').setRows(limpias));
                    if (typeof window.redimensionarGridsLaboresSectores === 'function') {
                        setTimeout(window.redimensionarGridsLaboresSectores, 50);
                    }
                } else {
                    resolve($('#detaLabores').jqGrid('addRowData', next, $.extend({ index: next, Lab_Des: 'No se Encontraron Registros' }), 'last'));
                }

            },
            function(err) {
                reject(err);
            }
        );
    });
}

function loadDataFincas() {
    var next = $("#detaFincas").jqGrid('getCol', 'index', false, 'max');
    next = (isNaN(next) ? 1 : next + 1);
    return new Promise((resolve, reject) => {
        $.getDataJson(
            '', { fincasAjax: true },
            function(resultado) {
                if (resultado.listaFincas && resultado.listaFincas.length > 0) {
                    var limpias = resultado.listaFincas.map(function(item) {
                        if (item.Fnc_Des) item.Fnc_Des = sanitizarTexto(item.Fnc_Des);
                        if (item.Fnc_Dir) item.Fnc_Dir = sanitizarTexto(item.Fnc_Dir);
                        return item;
                    });
                    resolve($('#detaFincas').setRows(limpias));
                    if (typeof window.redimensionarGridsLaboresSectores === 'function') {
                        setTimeout(window.redimensionarGridsLaboresSectores, 50);
                    }
                } else {
                    resolve($('#detaFincas').jqGrid('addRowData', next, $.extend({ index: next, Fnc_Des: 'No se Encontraron Registros' }), 'last'));
                }

            },
            function(err) {
                reject(err);
            }
        );
    });
}

var contadorFilaUnidad = 0;
var debounceTimersUnidades = {};

function abrirModalUnidades() {
    $('#unidadDialog').dialog('open');
    if ($('#tbody_unidades_multiples tr').length === 0) {
        reiniciarTablaUnidades();
    }
}

function reiniciarTablaUnidades() {
    $('#tbody_unidades_multiples').empty();
    contadorFilaUnidad = 0;
    agregarFilaUnidad();
    agregarFilaUnidad();
}

function agregarFilaUnidad(valorInicial) {
    contadorFilaUnidad++;
    var rowId = 'uni_row_' + contadorFilaUnidad;
    var val = valorInicial || '';

    var html = '<tr id="' + rowId + '">' +
        '<td class="row-num">' + ($('#tbody_unidades_multiples tr').length + 1) + '</td>' +
        '<td>' +
            '<input type="text" class="exa-input-multi trigger" name="Tpg_Des_Multi[]" ' +
                'placeholder="Ej: DIA, HORA, METRO, VIAJE, CAJA..." ' +
                'value="' + val + '" ' +
                'oninput="this.value = this.value.toUpperCase(); validarFilaUnidad(this);" ' +
                'onblur="validarFilaUnidad(this, true);" />' +
        '</td>' +
        '<td style="text-align:center;">' +
            '<span class="exa-badge-status empty"><i class="glyphicon glyphicon-minus"></i> Pendiente</span>' +
        '</td>' +
        '<td style="text-align:center;">' +
            '<button type="button" class="exa-btn-remove-row" onclick="eliminarFilaUnidad(this)" title="Eliminar esta fila">' +
                '<i class="glyphicon glyphicon-trash"></i>' +
            '</button>' +
        '</td>' +
    '</tr>';

    $('#tbody_unidades_multiples').append(html);
    actualizarNumerosFilasUnidades();

    var $newInput = $('#' + rowId).find('input');
    if (val) {
        validarFilaUnidad($newInput[0]);
    } else {
        $newInput.focus();
    }
}

function eliminarFilaUnidad(btn) {
    var $tbody = $('#tbody_unidades_multiples');
    if ($tbody.find('tr').length <= 1) {
        var $input = $tbody.find('tr:first input');
        $input.val('').removeClass('input-error input-success');
        $tbody.find('tr:first .exa-badge-status')
            .attr('class', 'exa-badge-status empty')
            .html('<i class="glyphicon glyphicon-minus"></i> Pendiente');
        return;
    }
    $(btn).closest('tr').remove();
    actualizarNumerosFilasUnidades();
    revalidarDuplicadosLocales();
}

function actualizarNumerosFilasUnidades() {
    var total = 0;
    $('#tbody_unidades_multiples tr').each(function(i) {
        $(this).find('td.row-num').text(i + 1);
        total++;
    });
    $('#badge_total_unidades').text(total + (total === 1 ? ' modo' : ' modos'));
}

function validarFilaUnidad(input, esBlur) {
    var $tr = $(input).closest('tr');
    var rowId = $tr.attr('id');
    var $badge = $tr.find('.exa-badge-status');
    var val = (input.value || '').trim();

    if (debounceTimersUnidades[rowId]) {
        clearTimeout(debounceTimersUnidades[rowId]);
    }

    if (val === '') {
        $(input).removeClass('input-error input-success');
        $badge.attr('class', 'exa-badge-status empty')
            .html('<i class="glyphicon glyphicon-minus"></i> Pendiente');
        return;
    }

    // 1. Validar duplicados en la lista actual
    var duplicadoLocal = false;
    $('#tbody_unidades_multiples tr').each(function() {
        if ($(this).attr('id') !== rowId) {
            var otroVal = ($(this).find('input').val() || '').trim();
            if (otroVal !== '' && otroVal.toUpperCase() === val.toUpperCase()) {
                duplicadoLocal = true;
                return false;
            }
        }
    });

    if (duplicadoLocal) {
        $(input).removeClass('input-success').addClass('input-error');
        $badge.attr('class', 'exa-badge-status invalid')
            .html('<i class="glyphicon glyphicon-remove"></i> Repetido en lista');
        return;
    }

    // 2. Validar con base de datos
    var delay = esBlur ? 0 : 350;
    $badge.attr('class', 'exa-badge-status checking')
        .html('<i class="glyphicon glyphicon-refresh"></i> Verificando...');

    debounceTimersUnidades[rowId] = setTimeout(function() {
        $.getDataJson('', { verificaDesc: true, Tpg_Des: val }, function(resultado) {
            var valorActual = ($(input).val() || '').trim();
            if (valorActual.toUpperCase() !== val.toUpperCase()) return;

            if (resultado && resultado.tipPagoDesc && resultado.tipPagoDesc.length > 0) {
                $(input).removeClass('input-success').addClass('input-error');
                $badge.attr('class', 'exa-badge-status invalid')
                    .html('<i class="glyphicon glyphicon-ban-circle"></i> Ya registrado');
            } else {
                $(input).removeClass('input-error').addClass('input-success');
                $badge.attr('class', 'exa-badge-status valid')
                    .html('<i class="glyphicon glyphicon-ok"></i> Disponible');
            }
        }, function(err) {
            $badge.attr('class', 'exa-badge-status empty')
                .html('<i class="glyphicon glyphicon-warning-sign"></i> Sin verificar');
        });
    }, delay);
}

function revalidarDuplicadosLocales() {
    $('#tbody_unidades_multiples tr').each(function() {
        var input = $(this).find('input')[0];
        if (input && (input.value || '').trim() !== '') {
            validarFilaUnidad(input, true);
        }
    });
}

function guardarUnidadesMultiples() {
    var valores = [];
    var tieneErrores = false;
    var errorMsg = '';

    $('#tbody_unidades_multiples tr').each(function(idx) {
        var $input = $(this).find('input');
        var val = ($input.val() || '').trim();
        var $badge = $(this).find('.exa-badge-status');

        if (val !== '') {
            if ($badge.hasClass('invalid') || $input.hasClass('input-error')) {
                tieneErrores = true;
                errorMsg = 'Corrija los modos repetidos o ya registrados en la fila ' + (idx + 1) + ' ("' + val + '").';
                return false;
            }
            if ($badge.hasClass('checking')) {
                tieneErrores = true;
                errorMsg = 'Espere a que termine la verificaci&oacute;n de la fila ' + (idx + 1) + '.';
                return false;
            }
            if (valores.indexOf(val.toUpperCase()) === -1) {
                valores.push(val.toUpperCase());
            } else {
                tieneErrores = true;
                errorMsg = 'El modo "' + val + '" est&aacute; duplicado en la lista.';
                return false;
            }
        }
    });

    if (tieneErrores) {
        $.alert(errorMsg);
        return;
    }

    if (valores.length === 0) {
        $.alert('Debe ingresar al menos una descripci&oacute;n de modo de trabajo v&aacute;lida.');
        return;
    }

    $.createDialogConfirm('&iquest;Desea guardar los <b>' + valores.length + '</b> modos de trabajo ingresados?', null, function() {
        $('#btn_gua_unidades_multi').prop('disabled', true).html('<i class="glyphicon glyphicon-refresh"></i> Guardando...');

        $.saveDataJson('', {
            save: true,
            saveUnidad: true,
            unidades: valores
        }, function(resp) {
            $('#btn_gua_unidades_multi').prop('disabled', false).html('<span class="glyphicon glyphicon-floppy-disk"></span> Guardar Modos');

            if (resp && resp.success) {
                var total = resp.total_guardados !== undefined ? resp.total_guardados : valores.length;
                $.alert('Se registraron exitosamente <b>' + total + '</b> modos de trabajo.');

                searchLaborPago();

                if (resp.tipoPago && resp.tipoPago.Tpg_Cod) {
                    setTimeout(function() {
                        $('#Tpg_Cod').val(resp.tipoPago.Tpg_Cod).trigger('chosen:updated');
                        $('#Tpg_Cod_Id').val(resp.tipoPago.Tpg_Cod);
                    }, 350);
                }

                reiniciarTablaUnidades();
                $('#unidadDialog').dialog('close');
            } else {
                var msg = (resp && resp.error) ? resp.error : 'Ocurri&oacute; un error al guardar los modos de trabajo.';
                $.alert(msg);
            }
        }, function(err) {
            $('#btn_gua_unidades_multi').prop('disabled', false).html('<span class="glyphicon glyphicon-floppy-disk"></span> Guardar Modos');
            $.alert('Error en la comunicaci&oacute;n con el servidor.');
        });
    });
}

function validarUnidad() {
    var inputValor = $('#Tpg_Des').val() ? $('#Tpg_Des').val().replace(/ /g, '') : '';
    if (inputValor.length > 0) {
        $.getDataJson('', { verificaDesc: true, Tpg_Des: inputValor }, function(resultado) {
            if (resultado && resultado.tipPagoDesc && resultado.tipPagoDesc.length > 0) {
                $('#Tpg_Des').fieldValid(false, 'El nombre ' + inputValor + ' ya se encuentra registrado');
                $('#btn_gua').attr('disabled', 'disabled');
                $('#Tpg_Des').val('');
            } else {
                $('#Tpg_Des').fieldValid(true);
                $('#btn_gua').removeAttr('disabled');
            }
        });
    } else {
        if ($('#Tpg_Des').length) {
            $('#Tpg_Des').fieldValid(false, 'Escriba una descripcion del registro');
        }
    }
}

function verificaExistente() {
    var sel_semana = $('#frm_alt_actividad').find("#Act_Sem").find('option:selected');
    var sel_finca = $('#frm_alt_actividad').find("#Fnc_Cod_D").find('option:selected');
    var sel_periodo = $('#frm_alt_actividad').find("#Pec_Cod").find('option:selected');
    if (sel_semana.val() > 0 && sel_finca.val() > 0) {
        $.getDataJson('', { verificaFincaSemana: true, Fnc_Cod: sel_finca.val(), Act_Sem: sel_semana.val() , Pec_Cod: sel_periodo.val() }, (resultado) => {
            if (resultado.fincaSemana.length > 0) {
                $('#frm_alt_actividad').find('#Act_Sem').fieldValid(false, 'La <u>Semana ' + resultado.fincaSemana['0']['Act_Sem'] + '</u> ya se encuentra registrada');
                $('#frm_alt_actividad').find('#Fnc_Cod_D').fieldValid(false, 'La finca <u>' + resultado.fincaSemana['0']['Fnc_Des'] + '</u> ya se encuentra registrada con la <u>Semana ' + resultado.fincaSemana['0']['Act_Sem'] + '</u>');
                $('#frm_alt_actividad').find('#btn_gua_act').attr('disabled', 'disabled');
                $('.select_semna option:selected').removeAttr('selected');
                $('.select_finca option:selected').removeAttr('selected');
                //
            } else {
                $('#frm_alt_actividad').find('#Act_Sem').fieldValid(true);
                $('#frm_alt_actividad').find('#Fnc_Cod_D').fieldValid(true);
                $('#frm_alt_actividad').find('#btn_gua_act').removeAttr('disabled');
            }
        });
    }
}


function searchLaborPago() {
    $.getDataJson('', { buscarLaborPago: true }, (respuesta) => {
        $('.select_unidad').empty();
        $('.select_unidad').val([]);
        $('.select_unidad').append($('<option>', { value: '', text: 'Seleccione....' }));
        if (respuesta.tipPago && respuesta.tipPago.length > 0) {
            respuesta.tipPago.forEach((resp) => {
                $('.select_unidad').append(
                    $('<option>', { value: resp['Tpg_Cod'], text: sanitizarTexto(resp['Tpg_Des'] || '') })
                );
            });
        }
        $('.select_unidad').trigger('chosen:updated');
    });
}

function selectPersonal(row) {
    var id = $('#CodFormBus').val();
    var ctx = resolverGridActividad(id);
    var nameGrid = ctx.nameGrid;
    var parametro = ctx.parametro;

    $('#' + nameGrid).changeRow(id, row);
    $('#' + nameGrid).find('tr#' + id).setData(row, false);
    $('#' + nameGrid).find('tr#' + id + parametro).val('');
    $('#' + nameGrid).find('tr#' + id + '_Total').val('');
    $('#personalDialog').dialog('close');
    if (ctx.fecha === '_Det_Fec_Mod') {
        $.createDatePickers('#' + id + '_Det_Fec_Mod');
    }
}

function selectLabor(row) {
    var id = $('#CodFormBusLab').val();
    var ctx = resolverGridActividad(id);
    var nameGrid = ctx.nameGrid;
    var parametro = ctx.parametro;

    var trabajador_data = $('#' + nameGrid).jqGrid('getRowData', id) || {};
    var perCod = trabajador_data['Per_Cod'] || $('#' + nameGrid).find('#' + id + '_Per_Cod').val() || '';
    var personalTxt = trabajador_data['Personal'] || $('#' + nameGrid).find('#' + id + '_Personal').val() || '';

    if (perCod === '' || perCod === '0' || perCod === 0) {
        $('#laboresDialog').dialog('close');
        $.alert('Debe Seleccionar un trabajador previamente!<br/>Revise los datos.', null, 'remove');
        return false;
    }

    row['Per_Cod'] = perCod;
    row['Personal'] = personalTxt;
    $('#' + nameGrid).changeRow(id, row);
    $('#' + nameGrid).find('tr#' + id).setData(row, false);
    $('#' + nameGrid).find('#' + id + parametro).val('');
    $('#' + nameGrid).find('#' + id + '_Total').val('');
    if (ctx.fecha === '_Det_Fec_Mod') {
        $.createDatePickers('#' + id + '_Det_Fec_Mod');
    }
    $('#laboresDialog').dialog('close');

    if (nameGrid === 'tableActividadMod') {
        $('#' + nameGrid).find('#' + id + parametro).off('change.selectLabor').on('change.selectLabor', function() {
            makeCalculation(1);
            $('#btn_guardado').prop('disabled', false);
        }).trigger('change');
    } else {
        makeCalculation(0);
    }
}

function makeCalculation(aux) {
    if (aux > 0) {
        //tableActividadMod
        var gridAct = $('#tableActividadMod');
        var ids = $('#tableActividadMod').jqGrid('getDataIDs');
        var datos = $('#tableActividadMod').jqGrid('getRowData');
        var campo = "Det_Can_Mod";
    } else {
        var gridAct = $('#tableActividad');
        var ids = $('#tableActividad').jqGrid('getDataIDs');
        var datos = $('#tableActividad').jqGrid('getRowData');
        var campo = "Det_Can";
    }

    for (var i = 0; i < datos.length; i++) {
        var columna = gridAct.jqGrid('getCell', ids[i], 'index');
        var valorId = datos[i]['index'];
        if (datos[i]['' + campo] != '' && datos[i]['Lab_Val'] !== '') {
            //console.log(datos[i]['' + campo] * 1);
            //console.log(parseFloat(datos[i]['Lab_Val']));
            datos[i]['Total'] = (parseFloat(datos[i]['' + campo]) * 1) * (parseFloat(datos[i]['Lab_Val']) * 1);
            gridAct.find('#' + valorId + '_Total').val(parseFloat(datos[i]['Total']).toFixed(2));
        }
    }

    // Calcular el nuevo total general
    var sum = gridAct.jqGrid('getCol', 'Total', false, 'sum');
    // Actualizar el valor de la columna "Total" en el pie del jqGrid
    $('td[aria-describedby="tableActividad_Total"]:last').text(sum);
    $('#_Total').val(sum.toFixed(2));

}
/**
 * start de methods for modificar actividades
 */
function editActividad(row) {
    var gridAct = $('#tableActividadMod');

    arrayD.length = 0;
    const detMod = async() => { arrayD = await doSomethingAsync(row); }
    detMod().then(() => {
        $("#loader").show();

        $('#tab3').moveComp('#divEdic').updateGridsSizes();

        $('#divEdic').setData(row);
        $("#loader").show();
        var sel_fecha = $('#frm_mod_act_edi').find("#Pec_Cod").find('option:selected');
        $('#Act_Fec_Mod').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
        $.createDatePickers('#Act_Fec_Mod', '#Det_Fec_Mod');
        $('#Act_Fec_Mod').val(row['Act_Fec']);
        $('#Det_Fec_Mod').val(row['Det_Fec']);
        $('#Act_Fec_Mod').trigger('change');
        detalleMod(arrayD);
        $("#loader").hide();
        // Calcular el nuevo total general
        var sum = gridAct.jqGrid('getCol', 'Total', false, 'sum');
        // Actualizar el valor de la columna "Total" en el pie del jqGrid
        $('#_Total').val(sum.toFixed(2));
    });
}

//View info
function viewInfo(row) {
    //console.log(row);
    $('#dialogInfo').setData(row);
    $('#dialogInfo').dialog('open');
}

//hide
function clearDocument() {
    $('#divEdic').hide();
}
async function doSomethingAsync(row) {
    let result = await viewActividad(row);
    return result;
}

async function detalleMod(row) {
    //tableActividadMod
    //tableActividadModPager
    var gridAct = $('#tableActividadMod');
    $('#tableActividadMod').clearGrid(true);
    var total = 0;
    //const detMod = await viewActividad(row);
    //const detMod = await doSomethingAsync(row);

    var sel_fecha = $('#frm_mod_act_edi').find("#Pec_Cod").find('option:selected');
    $("#loader").show();
    //detMod
    row.forEach((respuesta) => {

        total = ((respuesta['Det_Val'] * 1) * (respuesta['Det_Can'] * 1));
        //console.log(respuesta);
        var next = $("#tableActividadMod").jqGrid('getCol', 'index', false, 'max');
        next = (isNaN(next) ? 1 : next + 1);
        $("#tableActividadMod").jqGrid('addRowData', next, $.extend(respuesta, { index: next, Det_Cod: respuesta['Det_Cod'], Det_Fec_Mod: respuesta['Det_Fec'], Act_Cod: respuesta['Act_Cod'], Lab_Cod: respuesta['Lab_Cod'], Per_Cod: respuesta['Per_Cod'], Det_Can_Mod: parseFloat(respuesta['Det_Can']).toFixed(2), Det_Obs: respuesta['Det_Obs'], Total: parseFloat(total).toFixed(2) }), 'last');
        $.createDatePickers('#' + next + '_Det_Fec_Mod');
        $('#' + next + '_Det_Fec_Mod').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
        $('#divEdic').updateGridsSizes();

        $("#tableActividadMod").find('#' + next + "_Personal").val(respuesta['personal']);
        $("#tableActividadMod").find('#' + next + "_Lab_Des").val(respuesta['Lab_Des']);
        $("#tableActividadMod").find('#' + next + "_Tpg_Des").val(respuesta['Tpg_Des']);
        $("#tableActividadMod").find('#' + next + "_Det_Fec_Mod").val(respuesta['Det_Fec']);
        $("#tableActividadMod").find('#' + next + "_Det_Obs").val(respuesta['Det_Obs']);
        $("#tableActividadMod").find('#' + next + "_Lab_Val").val(respuesta['Lab_Val']);
        $("#tableActividadMod").find('#' + next + "_Lab_Cod").val(respuesta['Lab_Cod']);
        $("#tableActividadMod").find('#' + next + "_Per_Cod").val(respuesta['Per_Cod']);
        $("#tableActividadMod").find('#' + next + "_Det_Can_Mod").val(respuesta['Det_Can_Mod']);
        $("#tableActividadMod").find('#' + next + "_Total").val(respuesta['Total']);
        $('#tableActividadMod').find('#' + next + '_Det_Can_Mod').on('change', function() {
            //console.log('entro en el onchange del modificar');
            var vlInput = validaDecimal($('#tableActividadMod').find('#' + next + '_Det_Can_Mod').val());
            if (vlInput) {
                $.alert('El valor de la cantidad debe ser mayor que 0');
            }
            makeCalculation(1);
            $('#btn_guardado').prop('disabled', false);
        }).trigger('change');



        //console.log(respuesta);
    });
    if (row.length > 0) { $("#loader").hide(); }

    // Calcular el nuevo total general
    var sum = gridAct.jqGrid('getCol', 'Total', false, 'sum');
    // Actualizar el valor de la columna "Total" en el pie del jqGrid
    $('#_Total').val(sum);
}


//Muestra el detalle apartir del Cod de la cabecera
function viewActividad(row) {
    //console.log(row);
    return new Promise((resolve, reject) => {
        $.getDataJson("", { searchDetActivi: true, Act_Cod: row['Act_Cod'] }, (result) => {
            resolve(result.detalleAct);
        }, (err) => {
            reject(err);
        });
    });


}
//Cargar todo el detalle existente
function viewActividadAll() {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { searchAllDetail: true }, (result) => {
            resolve(result.allDetail);
        }, (err) => {
            reject(err);
        });
    })
}



function verficarSemana() {
    var verifica = false;
    //console.log(verifica);
    var semana = $('#inlineRadio1').val();

    if ($("#inlineRadio1").prop('checked') && $("#rad_ba1").prop('checked')) {
        //$('#frm_mod_actividad').find('#search').attr('disabled', 'disabled');
        $('#selectSelmana').show();
    }
    if ($("#inlineRadio2").prop('checked')) {
        $('#frm_mod_actividad').find('#Cod_Bus').removeAttr('disabled');
        $('#selectSelmana').hide();
    }
}
async function sFinca() {
    const fnks = await allFincas();
    fnks.forEach((respuesta) => {
        var desc = sanitizarTexto(respuesta['Fnc_Des'] || '');
        $('.select_search').append(
            $('<option>', { value: desc, text: desc })
        );
    });
    $('.select_search').trigger('chosen:updated');
}
async function sTrabajadores() {
    const trbjs = await allTrabajadores();
    trbjs.forEach((respuesta) => {
        var pers = sanitizarTexto(respuesta['Personal'] || '');
        $('.select_search').append(
            $('<option>', { value: pers, text: pers })
        );
    });
    $('.select_search').trigger('chosen:updated');
}

function busquedaInicial() {
    if ($("#rad_ba1").prop('checked')) {
        sFinca();

    }
    if ($("#rad_ba2").prop('checked')) {
        //console.log('lola2');
        sTrabajadores();
    }
}
$("#rad_ba3").on('click', function() {
    $('#divFecha').show();
    $('#Fec_Ini').removeAttr('disabled');
    $('#Fec_Fin').removeAttr('disabled');
    $('#frm_mod_actividad').find('#Cod_Bus').attr('disabled', 'disabled');
    $('.select_search').empty();
    sFinca();
});
$("#rad_ba2").on('click', function() {
    //console.log('lolita');
    //$('#frm_mod_actividad').find('#Cod_Bus').attr('disabled', 'disabled');
    $('#divFecha').hide();
    $('#Fec_Ini').attr('disabled', 'disabled');
    $('#Fec_Fin').attr('disabled', 'disabled');
    $('#frm_mod_actividad').find('#Cod_Bus').removeAttr('disabled');
    //$('.select_search option:selected').removeAttr('selected');
    //$('.select_search').val([])
    //$('.select_search').empty();
    //$('.select_search').val('').trigger('chosen:updated');
    limpiarBusq();
    sTrabajadores();
});
$("#rad_ba1").on('click', function() {
    //console.log('lola');
    $('#divFecha').hide();
    $('#Fec_Ini').attr('disabled', 'disabled');
    $('#Fec_Fin').attr('disabled', 'disabled');
    $('#frm_mod_actividad').find('#Cod_Bus').removeAttr('disabled');
    //$('.select_search').val([])
    //$('.select_search').empty();
    limpiarBusq();
    sFinca();
});

$("#Pec_Cod").on('change', function() {
    var sel_fecha = $(this).find('option:selected');
    fechas(sel_fecha.data('inicio'), sel_fecha.data('fin'), sel_fecha.data('placod'));
    $('#Act_Fec').trigger('change');
});

//Funcion para setear el datepicker al periodo seleccionado
function fechas(inicio, fin, placod) {
    $('#Act_Fec').dateLimits(inicio, fin);
}

function limpiarBusq() {
    //$('.select_search option:selected').removeAttr('selected');
    $('.select_search').val([])
    $('.select_search').empty();
    $('.select_search').val('').trigger('chosen:updated');
}


// Promesa de fincas
function allFincas() {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { fincasAjax: true }, (result) => {
            resolve(result.listaFincas);
        }, (err) => {
            reject(err);
        });
    });
}
//Promesa Trabajadores
function allTrabajadores() {
    return new Promise((resolve, reject) => {
        //
        $.getDataJson("", { trabajadoresAjax: true }, (result) => {
            resolve(result.listTrabajadores);
        }, (err) => {
            reject(err);
        });
    });
}



//Fin metodos para modificar actividades