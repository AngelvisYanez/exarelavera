var esMod = false;
var esCrear = false;
var arrayDetalle;
var arrayD = [];
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
    var opts = {
        height: 75,
        colModel: [
            { label: 'C�d.Int.', name: 'Lab_Cod', key: true, width: 15, align: 'center', hidden: false },
            { label: 'Descripci&oacute;n ', name: 'Lab_Des', width: 45, align: 'left' },
            { label: 'Unidad ', name: 'Tpg_Des', width: 20, align: 'left' },
            { label: 'Valor ', name: 'Lab_Val', width: 15, align: 'center' },
            {
                name: 'delete',
                label: '<i class="glyphicon glyphicon-trash"></i>',
                width: 10,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: delLabor,
                    /*conditional: function(o) { console.log(o); return o.tarja === 'n' && o.Prh_Est !== 'I'; },*/
                    icon: 'trash',
                    type: 'danger',
                    title: 'Anular Labor',
                    data: function(o) {
                        return o;
                    }
                },
                resizable: false
            },
             {
                name: 'update',
                label: '<i class="glyphicon glyphicon-pencil"></i>',
                width: 10,
                align: 'center',
                viewable: false,
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
            { label: 'C�d.Int.', name: 'Fnc_Cod', key: true, width: 15, align: 'center', hidden: false },
            { label: 'Descripci&oacute;n ', name: 'Fnc_Des', width: 45, align: 'left' },
            { label: 'Direcci&oacute;n ', name: 'Fnc_Dir', width: 20, align: 'left' },
            { label: 'Hect&aacute;reas ', name: 'Fnc_Hec', width: 15, align: 'center' }
        ]
    };

    $('#unidadDialog').createDialog({ height: 135, width: 400, icon: 'glyphicon glyphicon-plus' });
    $('#laborDialog').createDialog({ height: 200, width: 420, icon: 'pencil' });

    if ($('#detaLabores').length > 0)
        $('#detaLabores').createGrid(
            $.extend(opts, {
                height: 'auto',
                width: 550,
                responsive: false,
                caption: null,
                rownumbers: false
            }),
            true
        );
    if ($('#detaFincas').length > 0)
        $('#detaFincas').createGrid(
            $.extend(optsFink, {
                height: 'auto',
                width: 550,
                responsive: false,
                caption: null,
                rownumbers: false
            }),
            true
        );


    var grid = $('#tableActividad');
    grid
        .createGrid({
                caption: 'REGISTRO DE ACTIVIDADES',
                height: '350',
                colModel: [{
                        name: 'index',
                        label: 'Index',
                        width: 20,
                        sorttype: 'int',
                        align: 'center',
                        hidden: true
                    },
                    { label: 'Cod', name: 'Act_Cod', key: true, hidden: true },
                    {
                        label: '<span class="required"></span> Trabajador',
                        name: 'Personal',
                        width: 45,
                        align: 'center',
                        title: true,
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
                    {
                        label: '<span class="required"></span>Labor',
                        name: 'Lab_Des',
                        width: 30,
                        align: 'center',
                        title: true,
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
                    {
                        name: 'Lab_Cod',
                        hidden: true,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    },
                    {
                        name: 'Per_Cod',
                        hidden: true,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    },
                    {
                        label: 'Unidad',
                        name: 'Tpg_Des',
                        width: 20,
                        align: 'center',
                        title: false,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    }, {
                        label: '<span class="required"></span> Fecha',
                        name: 'Det_Fec',
                        width: 15,
                        align: "center",
                        title: false,
                        formatter: 'input2',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: '<span class="required"></span> Observaci&oacute;n',
                        name: 'Det_Obs',
                        width: 50,
                        align: 'center',
                        title: false,
                        formatter: 'input2',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: 'P. Unitario',
                        name: 'Lab_Val',
                        width: 15,
                        align: 'center',
                        title: false,
                        formatter: 'input4',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: '<span class="required"></span> Cantidad',
                        name: 'Det_Can',
                        width: 15,
                        align: 'center',
                        title: false,
                        formatter: 'inputN',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: 'Total',
                        name: 'Total',
                        width: 15,
                        align: 'right',
                        title: false,
                        formatter: 'input4',
                        formatoptions: { id: '2', attr: '' }
                    },

                    {
                        name: 'delete',
                        label: '<i class="glyphicon glyphicon-remove"></i>',
                        width: 10,
                        align: 'center',
                        viewable: false,
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
                loadComplete: function () { $(this).setGridSummary(['Total'],{Det_Can: '<div style="text-align:right;">Total:</div>'}); },
                pgbuttons: false,
                pgtext: null,
                beforeSelectRow: function(rowid, e) {
                    return false;
                }
            },
            true,
            '#tableActividadPager', { view: false, refresh: false }
        )
        .gridButtonAdd({
            caption: 'Agregar Trabajador',
            id: 'btn_agr',
            buttonicon: 'glyphicon glyphicon-plus',
            title: 'Agregar',
            onClickButton: function() {
                agregarFila(0);
            }
        });
    // Grid de la modificaci�n tableActividadMod tableActividadModPager
    $('#tableActividadMod').createGrid({
                caption: '*REGISTRO DE ACTIVIDADES',
                height: '350',
                colModel: [
                    { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', hidden: true },
                    { label: 'Cod', name: 'Act_Cod', key: true, hidden: true },
                    { label: 'Det_Cod', name: 'Det_Cod', key: true, hidden: true },
                    {
                        label: '<span class="required"></span> Trabajador',
                        name: 'Personal',
                        width: 45,
                        align: 'center',
                        title: true,
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
                    {
                        label: '<span class="required"></span>Labor',
                        name: 'Lab_Des',
                        width: 30,
                        align: 'center',
                        title: true,
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
                    {
                        name: 'Lab_Cod',
                        hidden: true,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    },
                    {
                        name: 'Per_Cod',
                        hidden: true,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    },
                    {
                        label: 'Unidad',
                        name: 'Tpg_Des',
                        width: 20,
                        align: 'center',
                        title: false,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    }, {
                        label: '<span class="required"></span> Fecha',
                        name: 'Det_Fec_Mod',
                        width: 20,
                        align: "center",
                        title: false,
                        formatter: 'input2',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: '<span class="required"></span> Observaci&oacute;n',
                        name: 'Det_Obs',
                        width: 45,
                        align: 'center',
                        title: false,
                        formatter: 'input2',
                        formatoptions: { id: '2', attr: '' }
                    },
                    { label: 'P. Unitario', name: 'Lab_Val', width: 15, align: 'center', title: false, formatter: 'input4', formatoptions: { id: '2', attr: '' } },
                    {
                        label: '<span class="required"></span> Cantidad',
                        name: 'Det_Can_Mod',
                        width: 15,
                        align: 'center',
                        title: false,
                        formatter: 'inputN',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: 'Total',
                        name: 'Total',
                        width: 15,
                        align: 'right',
                        title: false,
                        formatter: 'input4',
                        formatoptions: { id: '2', attr: '' }
                    },

                    {
                        name: 'delete',
                        label: '<i class="glyphicon glyphicon-remove"></i>',
                        width: 10,
                        align: 'center',
                        viewable: false,
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
        .gridButtonAdd({
            caption: 'Agregar Trabajador',
            id: 'btn_agr',
            buttonicon: 'glyphicon glyphicon-plus',
            title: 'Agregar',
            onClickButton: function() {
                agregarFila(1);
            }
        });


    //Tabla modificar actividades
    var gridModAct = $('#consultarGrid');
    gridModAct.createGrid({
        height: 300,
        datatype: "local",
        regional: 'es',
        shrinkToFit: true,
        colModel: [
            { label: 'C�d.Int.', name: 'Act_Cod', width: 10, key: true, hidden: false, align: "center", viewable: true },
            { name: "Fnc_Cod", hidden: true },
            { label: 'Trabajador', name: 'personal', width: 55, align: "center" },
            { label: 'Finca', name: 'Fnc_Des', width: 55, align: "center" },
            { label: 'Fecha', name: 'Act_Fec', width: 50, align: "left" },
            { label: 'Semana', name: 'Semana', width: 55, align: "center" },
            { label: $.createIcon('info-sign'), name: 'actInfo', align: "center", width: 7, viewable: false, formatter: 'gridButton', formatoptions: { action: viewInfo, icon: 'info-sign', type: 'info', title: 'Info' }, title: false, resizable: false },
            { label: $.createIcon('glyphicon glyphicon-pencil'), name: 'actEdt', align: "center", viewable: false, width: 7, formatter: 'gridButton', formatoptions: { action: editActividad, icon: 'glyphicon glyphicon-pencil', type: 'success', title: 'Modificar Activiad', resizable: false } }


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
        'laboresDialog', [{
                label: 'C&oacute;d.Int.',
                name: 'Lab_Cod',
                key: true,
                width: 15,
                align: 'center',
                hidden: true
            },
            { label: 'Descripc�on', name: 'Lab_Des', width: 100 },
            { label: 'Unidad', name: 'Tpg_Des', width: 60 },
            { label: 'P.Unitario', name: 'Lab_Val', width: 50 },
            {
                label: '&nbsp;',
                name: 'act1',
                width: 20,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: { action: selectLabor }
            }
        ],
        null,
        null,
        null, { headertitles: true }, {
            title: 'Labores',
            options: [
                { label: '&nbsp;&nbsp;Drescripci&oacute;n&nbsp;&nbsp;', value: 'd' },
                { label: '&nbsp;&nbsp;Unidad&nbsp;&nbsp;', value: 'c' }
            ]
        }
    );
}

if ($('#personalDialog').length > 0) {
    $.createSearchDialog(
        '#personalDialog', [{
                label: 'C&oacute;d.Int.',
                name: 'Per_Cod',
                key: true,
                width: 15,
                align: 'center',
                hidden: true
            },
            { label: 'C�dula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Personal', name: 'Personal', width: 100 },
            { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
            {
                label: '&nbsp;',
                name: 'act1',
                width: 20,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: { action: selectPersonal }
            }
        ],
        null,
        null,
        null, { headertitles: true }, {
            title: 'Personal',
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
            $.alert('Verifique la informaci�n en la fila: ' + indiceAct);
            return false;
            vfG = false;
        }
        if ((data['actividades'].length) < 1) { $.alert('Debe existir al menos un registro en registro de actividades..!!'); return false; }


    }
    $.arraySpliceFields(data['actividades'], ['index', 'Personal', 'Lab_Des', 'delete']);
    $.createDialogConfirm('�Est&aacute; seguro que desea guardar los cambios?', null, function() {
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
                $('#' + dialogo + 'Dialog').dialog('close');
                $('.select_unidad option:selected').removeAttr('selected');
                $('#Tpg_Cod').val('').trigger('chosen:updated');
                $('input:text[name=Act_Fec]').val($.datepicker.formatDate(shortDateFormat, new Date()));
                $('#tableActividad').clearGrid();
                $('#tableActividadMod').clearGrid();
                if (cambio) { $('#divEdic').moveComp('#tab3').updateGridsSizes(); }
                $.alert('La transaccii&oacute;n se realizo con exito.');
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

function abrirDialogPersonal(personal) {
    $('#personalDialog').dialog('open');
    //console.log('personal', personal);
    $('#CodFormBus').val(personal);
}

function abrirDialogLabor(labor) {
    //console.log(labor);
    var id = labor;
    if (id > 0) {
        $('#laboresDialog').dialog('open');
        var trabajador_data = $('#tableActividad').jqGrid('getRowData', id);
        //console.log(trabajador_data);
        $('#CodFormBusLab').val(labor);

    } else {
        $.alert('Debe seleccionar un Trabajador antes.!!');
    }


}

function agregarFila(aux) {

    if (aux > 0) {
        esMod = true;
        var $this = $('#tableActividadMod');
        var campoGrid = '_Det_Can_Mod';
        var fecha = '_Det_Fec_Mod';
        var $form = 'frm_mod_act_edi';
    } else {
        esCrear = true;
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
        makeCalculation(0);
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
    $.createDialogConfirm('Desea Eliminar la labor seleccionada..!!', null, function() {
        $.saveDataJson("", { elimLabor: true, Lab_Cod: row['Lab_Cod'] }, (respuesta) => {
            if (respuesta['success']) {
                $('#detaLabores').jqGrid('delRowData', row.id);
                //$('#formDatosLabor').updateGridsSizes();
                $('#detaLabores').trigger("reloadGrid");
                $.alert('La transacci&oacute;n se realizo con exito.');
                return false;
            }
        });

    });

}

function updLabor(row) {
    $("#Lab_Cod_Upd").val(row.id);
    $('#laborDialog').dialog('open');
    $('#Lab_Des_Upd').val(row.Lab_Des);
    $('#Tpg_Cod_Id').val(row.Tpg_Cod);
    $('#Lab_Val_Upd').val(row.Lab_Val);
}


function generarSemanas() {
    const contenido = 'Semana ';
    for (var i = 1; i < 53; i++) {
        $('.select_semna').append($('<option>', { value: i, text: contenido + '' + i }));
    }
}

function getFincas() {
    return new Promise((resolve, reject) => {
        $.getDataJson('', { fincasAjax: true }, (resultado) => {
            //resolve(resultado.listaFincas);
            resultado.listaFincas.forEach((valor) => {
                resolve($('.select_finca').append($('<option>', { value: valor['Fnc_Cod'], text: valor['Fnc_Des'] })));
            });
        }, (err) => {
            reject(err);
        });
    });
}

function loadDataTable() {
    var next = $("#detaLabores").jqGrid('getCol', 'index', false, 'max');
    next = (isNaN(next) ? 1 : next + 1);


    return new Promise((resolve, reject) => {
        //
        $.getDataJson(
            '', { laborAjax: true },
            function(resultado) {
                if (resultado.listLab.length > 0) {
                    resolve($('#detaLabores').setRows(resultado.listLab));
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
                if (resultado.listaFincas.length > 0) {
                    resolve($('#detaFincas').setRows(resultado.listaFincas));
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

function validarUnidad() {
    var inputValor = $('#Tpg_Des').val().replace(/ /g, '');
    if (inputValor.length > 0) {
        $.getDataJson('', { verificaDesc: true, Tpg_Des: inputValor }, function(resultado) {
            if (resultado.tipPagoDesc.length > 0) {
                $('#Tpg_Des').fieldValid(false, 'El nombre ' + inputValor + ' ya se encuentra registrado');
                $('#btn_gua').attr('disabled', 'disabled');
                $('#Tpg_Des').val('');
            } else {
                $('#Tpg_Des').fieldValid(true);
                $('#btn_gua').removeAttr('disabled');
            }
        });
    } else {
        $('#Tpg_Des').fieldValid(false, 'Escriba una Descripci�n del Registro');
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
        if (respuesta.tipPago.length > 0) {
            respuesta.tipPago.forEach((resp) => {
                $('.select_unidad').append(
                    $('<option>', { value: resp['Tpg_Cod'], text: resp['Tpg_Des'] })
                );
            });
        }
        $('.select_unidad').trigger('chosen:updated');
    });
}

function selectPersonal(row) {
    //console.log('Modificar: ', esMod); console.log('Es crear:', esCrear);  console.log(row);
    if (esMod && !esCrear || !esMod && !esCrear) {
        //esMod = false;
        nameGrid = 'tableActividadMod';
        nameForm = 'frm_mod_act_edi';
        parametro = '_Det_Can_Mod';


    }
    if (!esMod && esCrear) {
        //esCrear = false;
        nameGrid = 'tableActividad';
        nameForm = 'frm_alt_actividad';
        parametro = '_Det_Can';
    }

    var ids = $('#' + nameGrid).jqGrid('getDataIDs');
    var datose = $('#' + nameGrid).jqGrid('getRowData');

    var data = { 'items': $('#' + nameGrid).getGridBatch() };

    var change = true;
    if (change) {
        var id = $('#CodFormBus').val();
        $('#' + nameGrid).changeRow($('#CodFormBus').val(), row);
        $('#' + nameGrid).find('tr#' + id).setData(row, false);
        $('#' + nameGrid).find('tr#' + id + parametro).val('');
        $('#' + nameGrid).find('tr#' + id + '_Total').val('');
        $('#personalDialog').dialog('close');
        $.createDatePickers('#' + id + '_Det_Fec_Mod');
        /* if (nameGrid === 'tableActividadMod') {
            $('#' + nameGrid).find('#' + id + parametro).on('change', function() {
                console.log('entro en el onchange select PErsonal');
                $('#btn_guardado').prop('disabled', false);
            }).trigger('change');

        } */
    }
    //$('#tableActividad').changeRow($('#CodFormBus').val(), row); //$('#tableActividad').find('tr#' + id).setData(row, false);//$('#' + id + '_Det_Can').val('');  //$('#' + id + '_Total').val('');  //$('#personalDialog').dialog('close');
}

function selectLabor(row) {
    //console.log('Modificar: ', esMod); console.log('Es crear:', esCrear);  console.log(row);
    if (esMod && !esCrear || !esMod && !esCrear) {
        esMod = false;
        nameGrid = 'tableActividadMod';
        nameForm = 'frm_mod_act_edi';
        parametro = '_Det_Can_Mod';


    }
    if (!esMod && esCrear) {
        esCrear = false;
        nameGrid = 'tableActividad';
        nameForm = 'frm_alt_actividad';
        parametro = '_Det_Can';
    }
    var ids = $('#' + nameGrid).jqGrid('getDataIDs');

    var datose = $('#' + nameGrid).jqGrid('getRowData');

    var data = { 'items': $('#' + nameGrid).getGridBatch() };

    var change = true;

    if (change) {
        var id = $('#CodFormBusLab').val();

        var trabajador_data = $('#' + nameGrid).jqGrid('getRowData', id);
        if (trabajador_data['Per_Cod'] === '') {
            $('#laboresDialog').dialog('close');
            $.alert('Debe Seleccionar un trabajador previamente!<br/>Revise los datos.', null, 'remove');
            return false;
        } else {
            row['Per_Cod'] = trabajador_data['Per_Cod'];
            row['Personal'] = trabajador_data['Personal'];
            //console.log(row);
            $('#' + nameGrid).changeRow($('#CodFormBus').val(), row);
            $('#' + nameGrid).find('tr#' + id).setData(row, false);
            $('#' + nameGrid).find('tr#' + id + parametro).val('');
            $('#' + nameGrid).find('tr#' + id + '_Total').val('');
            $.createDatePickers('#' + id + '_Det_Fec_Mod');
            $('#laboresDialog').dialog('close');
            if (nameGrid === 'tableActividadMod') {

                $('#' + nameGrid).find('#' + id + parametro).on('change', function() {
                    //console.log('entro en el onchange select PErsonal');
                    /* var vlInput = validaDecimal($('#' + nameGrid).find('#' + id + parametro).val());
                    if (vlInput) {
                        $.alert('El valor de la cantidad debe ser mayor que 0');
                    } */
                    makeCalculation(1);
                    $('#btn_guardado').prop('disabled', false);
                }).trigger('change');

            }

        }

    }



    //console.log(row);
    //$('#tableActividad').changeRow($('#CodFormBusLab').val(), row);
    //$('#tableActividad').find('tr#' + id).setData(row, false);
    // $('#' + id + '_Det_Can').val('');
    //$('#' + id + '_Total').val('');
    //$('#laboresDialog').dialog('close');
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
        $('.select_search').append(
            $('<option>', { value: respuesta['Fnc_Des'], text: respuesta['Fnc_Des'] })
        );
    });
    $('.select_search').trigger('chosen:updated');
}
async function sTrabajadores() {
    const trbjs = await allTrabajadores();
    trbjs.forEach((respuesta) => {
        $('.select_search').append(
            $('<option>', { value: respuesta['Personal'], text: respuesta['Personal'] })
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