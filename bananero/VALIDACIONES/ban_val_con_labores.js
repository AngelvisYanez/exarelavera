var $grid, selecSem = [],
    arraySemSel = [],
    arraySemSellVal = [],
    arraySingleWeek = [],
    arrayTrabajadoresF = [],
    arrayDetSubGrid = [],
    valorExiste = 0,
    verf = 0,
    valorTotExiste = 0,
    posCambio = 0,
    optActSem = '',
    subGridName = '',
    tab_text,
    data_type = 'data:application/vnd.ms-excel',
    valorSemanaExiste = 0;
verfiMod = false,
    selecSemF = [];
$(() => {
    $grid = $('#list');
    $('#tabsConLabores').createTabs();
    $('#Act_Sem').createChosen('input-sm');
    $.fn.fmatter.input3 = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        ////console.log(opts);
        //console.log(opts['colModel']['name'], cv);
        cv = cv || '';
        //console.log(cv);
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
                '<div><input type="text" style="text-align: center;" value="' + cv + '" id="' +
                opts['rowId'] + '_' + opts['colModel']['name'] + '_' + opts['pos'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs ret2" ' +
                set['attr'] +
                'readonly/><span class="hidden lolaflores" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '_' + opts['pos'] + '_span">' + cv + '</span></div>'
            );
        }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input3.unformat = function(cv, opts, cObjt) {
        return $(cObjt).find(':input').val();
    };
    $.fn.fmatter.inputL = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        ////console.log(opts);
        //console.log(opts['colModel']['name'], cv);
        cv = cv || '';
        //console.log(cv);

        el = $(
            '<div><input type="text" style="text-align: left;" value="' + cv + '" id="' +
            opts['rowId'] + '_' + opts['colModel']['name'] + '_' + opts['pos'] +
            '" name="' +
            opts['colModel']['name'] +
            '" class="form-control input-xs ret2" ' +
            set['attr'] +
            'readonly/><span class="hidden lolaflores" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '_' + opts['pos'] + '_span">' + cv + '</span></div>'
        );

        return el.prop('outerHTML');
    };
    $.fn.fmatter.inputL.unformat = function(cv, opts, cObjt) {
        return $(cObjt).find(':input').val();
    };
    $.fn.fmatter.inputR = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        ////console.log(opts);
        //console.log(opts['colModel']['name'], cv);
        cv = cv || '';
        //console.log(cv);

        el = $(
            '<div><input type="text" style="text-align: right;" value="' + cv + '" id="' +
            opts['rowId'] + '_' + opts['colModel']['name'] + '_' + opts['pos'] +
            '" name="' +
            opts['colModel']['name'] +
            '" class="form-control input-xs ret2" ' +
            set['attr'] +
            'readonly/><span class="hidden lolaflores" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '_' + opts['pos'] + '_span">' + cv + '</span></div>'
        );

        return el.prop('outerHTML');
    };
    $.fn.fmatter.inputR.unformat = function(cv, opts, cObjt) {
        return $(cObjt).find(':input').val();
    };




    //Metodos P1
    sFinca();
    //Metodos P2
    weekInd();


});

denySelectionOnDoubleClick = function($el) {
    // see http://stackoverflow.com/questions/2132172/disable-text-highlighting-on-double-click-in-jquery/2132230#2132230
    if ($.browser.mozilla) { //Firefox
        $el.css('MozUserSelect', 'none');
    } else if ($.browser.msie) { //IE
        $el.bind('selectstart', function() {
            return false;
        });
    } else { //Opera, etc.
        $el.mousedown(function() {
            return false;
        });
    }
}


insertColumnGroupHeader = function(mygrid, startColumnName, numberOfColumns, titleText) {
    var i, cont = 0,
        cmi, skip = 0,
        $tr, colHeader, iCol, $th, refr,
        colModel = mygrid[0].p.colModel,
        ths = mygrid[0].grid.headers,
        gview = mygrid.closest("div.ui-jqgrid-view"),
        thead = gview.find("table.ui-jqgrid-htable>thead");

    mygrid.prepend(thead);
    $tr = $("<tr>");
    for (i = 0; i < colModel.length; i++) {
        $th = $(ths[i].el);
        cmi = colModel[i];
        //console.log(cmi.name);
        //refr = startColumnName + [i];
        //console.log(refr);
        if (cmi.name !== (startColumnName + [cont])) {
            if (skip === 0) {
                $th.attr("rowspan", "2");
            } else {
                denySelectionOnDoubleClick($th);
                $th.css({ "padding-top": "2px", height: "19px" });
                $tr.append(ths[i].el);
                skip--;
            }
        } else {

            //console.log('aqui', cont);
            colHeader = $('<th class="ui-state-default ui-th-ltr" colspan="' + numberOfColumns +
                '" style="height:19px;padding-top:1px;text-align:center" role="columnheader">' + titleText + ' ' + selecSemF[cont] + '</th>');
            denySelectionOnDoubleClick($th);
            $th.before(colHeader);
            $tr.append(ths[i].el);
            skip = numberOfColumns - 1;
            cont++;
        }
    }
    mygrid.children("thead").append($tr[0]);
};

function habilitaSemana() {
    $("#f_periodo").removeAttr("disabled");
    $("#btn_limpiar").removeAttr("disabled");



}

function cambioFiltro() {
    if ($("#f_periodo").prop('checked')) {
        $("#por_peri").val("s");
        $("#sel_per").removeAttr("disabled");
    } else {
        $("#por_peri").val("n");
        $("#btn_buscar").attr("disabled", "");

    }

}

function getColumGrid2(formulario, metodo, dialogo) {

    var cont = 0,
        num = {},
        valorSemana,
        semanaArmada = [];

    //var Fnk_Cod = $('.select_finca').find('option:selected');
    //var Fec_Ini = sel_tipo.attr('data-fin');
    actFinks();

    num['Fnc_Cod'] = $(".select_finca").val();
    num['Pec_Cod'] = $(".select_perido").val();
    num['semanas'] = [{
        name: 'index',
        label: 'Index',
        width: 20,
        sorttype: 'int',
        align: 'center',
        hidden: true

    }, {
        label: 'Cod',
        name: 'Lab_Cod',
        width: 15,
        //align: center,
        sortable: false,
        hidden: true,
        formatter: 'input3',
        formatoptions: { id: '3', attr: '' }

    }, { //Lab_Des
        label: 'Labor',
        width: 45,
        name: 'Labor',
        //align: center,
        sortable: false,
        hidden: false,
        formatter: 'input3',
        formatoptions: { id: '3', attr: '' }
    }];
    num['names'] = [];
    //console.log(num['Fnc_Cod']);
    valorSemana = $(".select_semana").val();

    //console.log(valorSemana);

    for (var sem in valorSemana) {
        var sem = ((sem * 1) + 1);
        //semanaArmada.push({ codLabor: "Lab_Cod", desLabor: "Labor", semana: 'Semana#' + ((sem * 1) + 1), costoSemana: "Cst. Semana", costoHectareas: "Cst. Hectareas" });

        //console.log(semanaArmada[j].semana);

        //cuando no es un primer registro
        num['semanas'].push({
            label: 'Semana#' + sem,
            name: 'Act_Sem',
            width: 25,
            //align: center,
            sortable: true,
            hidden: true,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }


        }, {
            label: 'Cst. Semana',
            name: 'Cst_Sem',
            width: 15,
            //align: center,
            sortable: false,
            hidden: false,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }

        }, {
            label: 'Cst. Hectareas',
            name: 'Cst_Hec',
            width: 15,
            //align: center,
            sortable: false,
            hidden: false,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }

        });
        /*
                    num['names'].push(semanaArmada[j].semana);
                    num['names'].push(semanaArmada[j].costoSemana);
                    num['names'].push(semanaArmada[j].costoHectareas);*/





        /* num['semanas'].push({
            name: 'index',
            label: 'Index',
            width: 20,
            sorttype: 'int',
            align: 'center',
            hidden: true

        }, {
            label: semanaArmada[j].codLabor,
            name: 'Lab_Cod',
            width: 15,
            //align: center,
            sortable: true,
            hidden: true,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }

        }, { //Lab_Des
            label: semanaArmada[j].desLabor,
            width: 45,
            //align: center,
            sortable: true,
            hidden: false,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }
        }, {
            label: semanaArmada[j].semana,
            name: 'Act_Sem',
            width: 25,
            //align: center,
            sortable: true,
            hidden: true,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }


        }, {
            label: semanaArmada[j].costoSemana,
            name: 'Cst_Sem',
            width: 25,
            //align: center,
            sortable: true,
            hidden: false,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }

        }, {
            label: semanaArmada[j].costoHectareas,
            name: 'Cst_Hec',
            width: 25,
            //align: center,
            sortable: true,
            hidden: false,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }

        });

        num['names'].push('index');
        num['names'].push(semanaArmada[j].codLabor);
        num['names'].push(semanaArmada[j].desLabor);
        num['names'].push(semanaArmada[j].semana);
        num['names'].push(semanaArmada[j].costoSemana);
        num['names'].push(semanaArmada[j].costoHectareas);*/

    }
    //console.log(num['names']);
    createGrid(num['semanas'], num['names']);

    insertColumnGroupHeader($grid, 'Cst_Sem', 2, '<em></em>');
    //$('#list').Search('#frmLaborSearch', 'searchSemanas');


}


function getColumGrid(formulario, metodo, dialogo) {

    var cont = 0,
        num = {},
        valorSemana,
        semNum = [],
        semanaArmada = [];

    //var Fnk_Cod = $('.select_finca').find('option:selected');
    //var Fec_Ini = sel_tipo.attr('data-fin');
    actFinks();

    num['Fnc_Cod'] = $(".select_finca").val();
    num['Pec_Cod'] = $(".select_perido").val();
    num['semanas'] = [];
    num['names'] = [];
    //console.log(num['Fnc_Cod']);
    valorSemana = $(".select_semana").val();

    //console.log(valorSemana);


    for (var sem in valorSemana) { semanaArmada.push({ codLabor: "Lab_Cod", desLabor: "Labor", semana: 'Semana#' + ((sem * 1) + 1), costoSemana: "Cst. Semana", costoHectareas: "Cst. Hectareas" }); }
    //console.log('semanaArmada', semanaArmada);

    for (let j = 0; j < semanaArmada.length; j++) {
        //console.log(semanaArmada[j].semana);
        if (j > 0) {
            //cuando no es un primer registro
            num['semanas'].push({
                label: semanaArmada[j].semana,
                name: 'Act_Sem',
                width: 25,
                //align: center,
                sortable: true,
                hidden: true,
                formatter: 'input3',
                formatoptions: { id: '3', attr: '' }


            }, {
                label: semanaArmada[j].costoSemana,
                name: 'Cst_Sem' + cont,
                width: 15,
                //align: center,
                sortable: false,
                hidden: false,
                formatter: 'input3',
                formatoptions: { id: '3', attr: '' }

            }, {
                label: semanaArmada[j].costoHectareas,
                name: 'Cst_Hec' + cont,
                width: 15,
                //align: center,
                sortable: false,
                hidden: false,
                formatter: 'input3',
                formatoptions: { id: '3', attr: '' }

            });
            cont++;
            /*
                        num['names'].push(semanaArmada[j].semana);
                        num['names'].push(semanaArmada[j].costoSemana);
                        num['names'].push(semanaArmada[j].costoHectareas);*/

        } else {
            //primer registro
            num['semanas'].push({
                name: 'index',
                label: 'Index',
                width: 20,
                sorttype: 'int',
                align: 'center',
                hidden: true

            }, {
                label: semanaArmada[j].codLabor,
                name: 'Lab_Cod',
                width: 15,
                //align: center,
                sortable: false,
                hidden: true,
                formatter: 'input3',
                formatoptions: { id: '3', attr: '' }

            }, { //Lab_Des
                label: semanaArmada[j].desLabor,
                width: 45,
                name: 'Labor',
                //align: center,
                sortable: false,
                hidden: false,
                formatter: 'input3',
                formatoptions: { id: '3', attr: '' }
            }, {
                label: semanaArmada[j].semana,
                name: 'Act_Sem',
                width: 25,
                //align: center,
                sortable: false,
                hidden: true,
                formatter: 'input3',
                formatoptions: { id: '3', attr: '' }


            }, {
                label: semanaArmada[j].costoSemana,
                name: 'Cst_Sem' + cont,
                width: 15,
                //align: center,
                sortable: false,
                hidden: false,
                formatter: 'input3',
                formatoptions: { id: '3', attr: '' }

            }, {
                label: semanaArmada[j].costoHectareas,
                name: 'Cst_Hec' + cont,
                width: 15,
                //align: center,
                sortable: false,
                hidden: false,
                formatter: 'input3',
                formatoptions: { id: '3', attr: '' }

            });
            cont++;
            /*num['names'].push('index');
            num['names'].push(semanaArmada[j].codLabor);
            num['names'].push(semanaArmada[j].desLabor);
            num['names'].push(semanaArmada[j].semana);
            num['names'].push(semanaArmada[j].costoSemana);
            num['names'].push(semanaArmada[j].costoHectareas);*/

        }

        /* num['semanas'].push({
            name: 'index',
            label: 'Index',
            width: 20,
            sorttype: 'int',
            align: 'center',
            hidden: true

        }, {
            label: semanaArmada[j].codLabor,
            name: 'Lab_Cod',
            width: 15,
            //align: center,
            sortable: true,
            hidden: true,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }

        }, { //Lab_Des
            label: semanaArmada[j].desLabor,
            width: 45,
            //align: center,
            sortable: true,
            hidden: false,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }
        }, {
            label: semanaArmada[j].semana,
            name: 'Act_Sem',
            width: 25,
            //align: center,
            sortable: true,
            hidden: true,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }


        }, {
            label: semanaArmada[j].costoSemana,
            name: 'Cst_Sem',
            width: 25,
            //align: center,
            sortable: true,
            hidden: false,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }

        }, {
            label: semanaArmada[j].costoHectareas,
            name: 'Cst_Hec',
            width: 25,
            //align: center,
            sortable: true,
            hidden: false,
            formatter: 'input3',
            formatoptions: { id: '3', attr: '' }

        });

        num['names'].push('index');
        num['names'].push(semanaArmada[j].codLabor);
        num['names'].push(semanaArmada[j].desLabor);
        num['names'].push(semanaArmada[j].semana);
        num['names'].push(semanaArmada[j].costoSemana);
        num['names'].push(semanaArmada[j].costoHectareas);*/

    }
    //console.log(num['names']);
    createGrid(num['semanas'], num['names']);
    //console.log($grid);
    insertColumnGroupHeader($("#list"), 'Cst_Sem', 2, '<em></em>');
    //$('#list').Search('#frmLaborSearch', 'searchSemanas');
    verificaGenerar();



}


function createGrid(listColumnModels, listColumnNames) {
    $("#list").createGrid({
            height: 400,
            datatype: "local",
            regional: 'es',
            shrinkToFit: true,
            //colNames: listColumnNames,
            colModel: listColumnModels,
            pager: "#listPager",
            rownumbers: true,
            rowNum: 10000,
            gridview: false,
            viewrecords: false,
            sortable: false,
            footerrow: true,
            userDataOnFooter: true,
            loadComplete: function(data) {
                //console.log('lola');
                //metodo que me devuelve el grid con los datos buscados
                //actualizarValoresFooter();
                //console.log('lolita 2');
            }

        }, false, "#listPager", { view: false, refresh: false })
        .gridButtonsAdd([
            { id: 'btnExel', caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function() { $("#list").jqGrid('exportGridExcel', { nombre: 'Resumen', hoja: 'HOJA 1' }); } }
        ]);
    $('#listPager').find('.ui-pg-table table.navtable').find('td.ui-pg-button.ui-corner-all').unbind('mouseenter mouseleave').removeClass('ui-pg-button').addClass('btn btn-xs btn-success').find('.ui-pg-div span').removeClass('ui-icon').addClass('glyphicon');



}



function generarSemanas() {
    const contenido = 'Semana ';
    for (var i = 1; i < 53; i++) {
        $('.select_semna').append($('<option>', { value: i, text: contenido + '-' + i }));
    }
}

function searchSemanas() {
    //console.log('change');
    $('.select_semana').removeAttr("disabled");
    $('.select_semana').trigger('chosen:updated');
    //llamar metodo de busqueda de semanas por finca y anho
    sWeek();

}

function actualizarValoresFooter() {
    //console.log('object', 'object');
    $('#list').startGridEdit();
    let ids = $('#list').jqGrid('getDataIDs');
    var cont = 0,
        verifica = 0,
        testo = '',
        testo2 = '',
        totales = [],
        hecta = [],
        esCost = '',
        esHect = '',
        numSemanas = $(".select_semana").val();
    for (let i = 0; i < ids.length; i++) {
        let cost_sem = $('#list').jqGrid('getRowData', ids[i]);
        if (i >= numSemanas.length && numSemanas.length > 1) { cont = numSemanas.length - 1; } else { cont = i; }
        //console.log(cost_sem);
        var cost = '';
        for (let j = 0; j < numSemanas.length; j++) {
            //cost = cost_sem['Cst_Sem' + [j]];
            cost = parseFloat(cost_sem['Cst_Sem' + [j]].replace(/[^0-9-.]/g, ''));
            hect = parseFloat(cost_sem['Cst_Hec' + [j]].replace(/[^0-9-.]/g, ''));
            //hect = cost_sem['Cst_Hec' + [j]];

            if (cost === "" || isNaN(cost)) {
                esCost = 'C';
                encerarCelda(i + 1, j, (j + 1), esCost);
            }
            if (hect === "" || isNaN(hect)) {
                esHect = 'H';
                encerarCelda(i + 1, j, (j + 1), esHect);
            }
            //console.log("Contador 1: " + cont + ">---->" + hect);
            //console.log("Contador 2: " + cont + ">---->" + cost);
            if (cont <= 0) {
                totales.push({
                    id: j,
                    valor: cost

                });
                hecta.push({
                    id: j,
                    valor: hect

                });
            } else {
                //console.log(cost);
                if (isNaN(cost)) { cost = 0; }
                if (isNaN(hect)) { hect = 0; }
                addSemanas(totales, j, (cost * 1));
                addSemanas(hecta, j, (hect * 1));
                cost = '';
                hect = '';
            }

        }

        /* cost_sem['Cst_Sem' + [cont]]
        var cost = cost_sem['Cst_Sem' + [cont]];
        console.log("Contador: " + cont + "---->" + cost); */

    }
    //console.log(totales);
    $('#list').jqGrid('footerData', 'set', { Labor: "TOTALES:" });
    for (let h = 0; h < totales.length; h++) {
        testo = "Cst_Sem" + h;
        var footer = {};
        footer[testo] = formatMoney(totales[h]['valor']);

        //console.log(totales[h]['valor']);
        $('#list').jqGrid('footerData', 'set', footer);
    }
    for (let h = 0; h < hecta.length; h++) {
        testo2 = "Cst_Hec" + h;
        var footer2 = {};
        footer2[testo2] = formatMoney(hecta[h]['valor']);

        //console.log(hecta[h]['valor']);
        $('#list').jqGrid('footerData', 'set', footer2);
    }

}

function encerarCelda(posColumna, posCampo, semana, variable) {
    //console.log(posColumna);
    //console.log(posCampo);
    var $this = $('#list'),
        incr = 5,
        celda;
    let ids = $('#list').jqGrid('getDataIDs');
    let cost_semEd = $('#list').jqGrid('getRowData', posColumna);
    //console.log(cost_semEd);
    $this.jqGrid('editRow', posColumna);
    if (semana > 1) { incr = incr + (3 * (semana - 1)) }
    if (variable === 'C') { celda = '_Cst_Sem'; }
    if (variable === 'H') {
        celda = '_Cst_Hec';
        incr = incr + 1;
    }
    $this.find('#' + posColumna + celda + posCampo + "_" + (incr)).val('0');
    $this.find('#' + posColumna + celda + posCampo + "_" + (incr) + '_span').html('0');
}

function addSemanas(array, id, valor) {
    //console.log(array);
    //console.log(id);
    //console.log(valor);
    for (let k = 0; k < array.length; k++) {
        if (isNaN(array[k]['valor'])) { array[k]['valor'] = 0; }
        if (array[k]['id'] === id) {
            //console.log(array[k]['valor']);
            //console.log(valor);
            var cal = ((array[k]['valor']) * 1) + ((valor) * 1);
            array[k]['valor'] = cal;
            break;
        }
    }
}

//Promesa de Semanas por finca y anho
function allweek(sel_tipo, Fnk_Cod) {
    var Fec_Ini = sel_tipo.attr('data-fin');
    var Fec_Fin = sel_tipo.attr('data-inicio');
    return new Promise((resolve, reject) => {
        $.getDataJson("", { weekAjax: true, Fnc_Cod: Fnk_Cod.val(), Fec_Ini: Fec_Ini, Fec_Fin: Fec_Fin }, (result) => {
            resolve(result.listWeek);
        }, (err) => {
            reject(err);
        });
    });
}
//Promesa de actividades x Cod de finca
function allActFink(Cod_Fink, semanas) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { searchSemanas: true, Fnc_Cod: Cod_Fink, semnas: semanas }, (result) => {
            resolve(result.encabezadosPorFinca);
        }, (err) => {
            reject(err);
        });
    });
}

//Promesa Detalle por Codigo de Per_Cod y Act_Cod
function detalleSubGrid(Per_Cod, Act_Cod) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { detallePorGrid: true, Per_Cod: Per_Cod, Act_Cod: Act_Cod }, (result) => {
            //console.log(result.detSubGrid);
            resolve(result.detSubGrid);
        }, (err) => {
            reject(err);
        });
    });
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

function habilitaExportar() {
    $("#btnExel2").removeAttr("disabled");
    $("#btnExel2").on('click', function() {
        fnExcelReport();
    });
}
async function getDetSubGrid(CodPer, CodAct) {
    verf = 0;
    const gDS = await detalleSubGrid(CodPer, CodAct);
    for (let g = 0; g < gDS.length; g++) {
        arrayDetSubGrid.push(gDS[g]);
    }
    verf++;
    //console.log(verf);
    if (arrayTrabajadoresF.length === verf) { habilitaExportar(); }



}

async function actFinks() {
    arraySemSel.length = 0;
    var Cod_Fink = $(".select_finca").val();
    var numSemanas = $(".select_semana").val();
    const actF = await allActFink(Cod_Fink, numSemanas);
    for (let k = 0; k < actF.length; k++) {
        for (let l = 0; l < numSemanas.length; l++) {
            if (actF[k].Act_Cod === numSemanas[l]) {
                //console.log('lola 1', actF[k]);
                arraySemSel.push(actF[k]);
            }
        }
    }

    //console.log(arraySemSel);
    //console.log(arraySemSellVal);
    //console.log(numSemanas.length);

    filtroArrayWeek();
    //getDataGrid();
    mejoraGetDataGrid();

}
// Array filtrado por semanas/labor
function filtroArrayWeek() {

    for (let m = 0; m < arraySemSel.length; m++) {
        if (arraySemSellVal.length <= 0) {
            arraySemSel[m]['totalSem'] = ((arraySemSel[m]['Det_Val'] * 1) * (arraySemSel[m]['Det_Can'] * 1));
            arraySemSellVal.push(arraySemSel[m]);
        } else {
            //buscar si ya existe esa labor en esa
            for (let n = 0; n < arraySemSellVal.length; n++) {
                var verifica = verificarSiExiste(arraySemSellVal, arraySemSel[m]['Lab_Des'], arraySemSel[m]['Act_Sem']);
                if (verifica && arraySemSellVal[n]['Act_Cod'] === arraySemSel[m]['Act_Cod'] && valorSemanaExiste === arraySemSel[m]['Act_Sem']) {
                    var newTotal = ((arraySemSel[m]['Det_Val'] * 1) * (arraySemSel[m]['Det_Can'] * 1));
                    //console.log(arraySemSel[n]['totalSem']);
                    modificaExistente(arraySemSellVal, valorExiste, newTotal, 'totalSem', arraySemSel[m]['Lab_Des'], arraySemSellVal[n]['Act_Sem'], arraySemSel[m]);
                    valorExiste = 0;
                    valorSemanaExiste = '';
                    break;
                } else {
                    //console.log('lola', arraySemSel[m]);
                    arraySemSel[m]['totalSem'] = ((arraySemSel[m]['Det_Val'] * 1) * (arraySemSel[m]['Det_Can'] * 1));
                    arraySemSellVal.unshift(arraySemSel[m]);
                    break;
                }


            }
        }

    }
    calHEctareas();
}

function calHEctareas() {

    var hectareas = $('.select_finca').find('option:selected').attr('hectareas');
    //console.log(arraySemSellVal.length);
    //console.log(arraySemSellVal);
    for (let h = 0; h < arraySemSellVal.length; h++) {
        //console.log('CALCULO HECTAREAS');
        //console.log(arraySemSellVal[h]['totalSem']);
        //console.log(hectareas);
        arraySemSellVal[h]['totalHect'] = (arraySemSellVal[h]['totalSem'] * 1) / (hectareas * 1);
    }

}


function verificarSiExiste(lista, campo, semana) {
    var verifica = false;
    //console.log(lista);
    //console.log(campo);
    for (var i = 0; i < lista.length; i++) {
        //console.log(lista[i]);
        if (lista[i]['Lab_Des'] === campo && lista[i]['Act_Sem'] === semana) {
            valorExiste = lista[i]['totalSem'];
            valorSemanaExiste = lista[i]['Act_Sem'];
            verifica = true;
            break;
        }

    }
    return verifica;
}

function verificarLaborArmado(objeto) {
    var verifica = false,
        numSemanas = $(".select_semana").val();
    $('#list').startGridEdit();
    let ids = $('#list').jqGrid('getDataIDs');
    for (let i = 0; i < ids.length; i++) {
        let lab_sem = $('#list').jqGrid('getRowData', ids[i]);
        //for (let j = 0; j < numSemanas.length; j++) {}
        if (lab_sem['Labor'] === objeto['Lab_Des']) {
            verifica = true;
            posCambio = i + 1;
        }
    }
    //optActSem = objeto['Act_Sem'];
    return verifica;

}

function modificaExistente(lista, valorActual, valorNew, campo, campoRef, semana, objeto) {
    verfiMod = false;
    //console.log('lista:', lista);
    //console.log('campo:', campo);
    //console.log('valorActual:', valorActual);
    //console.log('valorNew:', valorNew);
    var valorGeneral = 0;
    valorGeneral = valorActual + valorNew;

    for (var i = 0; i < lista.length; i++) {
        if (lista[i]['Lab_Des'] === campoRef && parseInt(lista[i]['Act_Sem'] * 1) === parseInt(semana) * 1) {
            lista[i][campo] = valorGeneral;
            verfiMod = true;
        }
    }
    if (!verfiMod && parseInt(objeto['Act_Sem']) * 1 === parseInt(semana) * 1) {
        objeto['totalSem'] = ((objeto['Det_Val'] * 1) * (objeto['Det_Can'] * 1));
        arraySemSellVal.unshift(objeto);
    }
    //console.log(lista);

}

function mejoraGetDataGrid() {
    var $this = $('#list');
    var cont = 0,
        descLabor = '',
        numSemanas = $(".select_semana").val(),
        incr = 0;
    //console.log(arraySemSellVal);
    arraySemSellVal.reverse().forEach((respuesta) => {
        //console.log(respuesta);
        var next = $this.jqGrid('getCol', 'index', false, 'max');
        next = (isNaN(next) ? 1 : next + 1);
        if (cont <= 0) {
            descLabor = respuesta['Lab_Des'];
            optActSem = respuesta['Act_Sem'];
            valorWeek = ((respuesta['Det_Val'] * 1) * (respuesta['Det_Can'] * 1));
            $this.jqGrid('addRowData', next, $.extend(respuesta, { index: next, Det_Cod: respuesta['Det_Cod'], Act_Cod: respuesta['Act_Cod'], Lab_Cod: respuesta['Lab_Cod'], Per_Cod: respuesta['Per_Cod'], Det_Can_Mod: parseFloat(respuesta['Det_Can']).toFixed(2), Det_Obs: respuesta['Det_Obs'] }), 'last');
            $this.jqGrid('editRow', next);
            $('#formDatosConsultarLabor').updateGridsSizes();

            incr = 5;
            /* $this.find('#' + next + "_Labor_" + (3)).val(respuesta['Lab_Des']);
            $this.find('#' + next + "_Labor_" + (3) + '_span').html(respuesta['Lab_Des']);
            $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr)).val(roundN(respuesta['totalSem'], 2));
            $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr) + '_span').html(roundN(respuesta['totalSem'], 2));
            //Cst_Hec0
            $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1)).val(roundN(respuesta['totalHect'], 2));
            $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1) + '_span').html(roundN(respuesta['totalHect'], 2)); */
            putDataGrid($('#list'), next, cont, incr, respuesta);
            cont++;

        } else {
            //Verificar que ese registro no este
            var verficaArmado = verificarLaborArmado(respuesta);
            if (cont >= 1) {
                if (verficaArmado) {
                    if (respuesta['Act_Sem'] !== optActSem) {
                        incr = incr + 3;
                        //cont = (cont - 1);
                    } else {
                        incr = incr;
                        cont = (cont - 1);;
                    }

                } else {
                    if (respuesta['Act_Sem'] === optActSem) {
                        incr = incr;
                        cont = (cont - 1);
                    } else { incr = incr + 3; }
                }

            } else { incr = incr + 4; }
            if (verficaArmado) {
                //si existe
                optActSem = '';
                optActSem = respuesta['Act_Sem'];
                next = posCambio;
                posCambio = 0;
                $this.jqGrid('editRow', next);
                $('#formDatosConsultarLabor').updateGridsSizes();
                $this.find('#' + next + "_Labor_" + (3) + '_span').html(respuesta['Lab_Des']);
                $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr)).val(roundN(respuesta['totalSem'], 2));
                $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr) + '_span').html(roundN(respuesta['totalSem'], 2));
                $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1)).val(roundN(respuesta['totalHect'], 2));
                $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1) + '_span').html(roundN(respuesta['totalHect'], 2));
                cont++;

            } else {
                //no existe debe ir el flujo normal
                optActSem = '';
                optActSem = respuesta['Act_Sem'];
                valorWeek = ((respuesta['Det_Val'] * 1) * (respuesta['Det_Can'] * 1));
                $this.jqGrid('addRowData', next, $.extend(respuesta, { index: next, Det_Cod: respuesta['Det_Cod'], Act_Cod: respuesta['Act_Cod'], Lab_Cod: respuesta['Lab_Cod'], Per_Cod: respuesta['Per_Cod'], Det_Can_Mod: parseFloat(respuesta['Det_Can']).toFixed(2), Det_Obs: respuesta['Det_Obs'] }), 'last');
                $this.jqGrid('editRow', next);
                $('#formDatosConsultarLabor').updateGridsSizes();

                /* $this.find('#' + next + "_Labor_" + (3)).val(respuesta['Lab_Des']);
                $this.find('#' + next + "_Labor_" + (3) + '_span').html(respuesta['Lab_Des']);
                $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr)).val(roundN(respuesta['totalSem'], 2));
                $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr) + '_span').html(roundN(respuesta['totalSem'], 2));
                $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1)).val(roundN(respuesta['totalHect'], 2));
                //$this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1) + '_span').html(formatMoney(respuesta['totalHect']));
                $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1) + '_span').html(roundN(respuesta['totalHect'], 2)); */
                putDataGrid($('#list'), next, cont, incr, respuesta);
                cont++;
            }

        }

    });
    actualizarValoresFooter();

}

function getDataGrid() {
    var $this = $('#list');
    /* var id = $this.nextIndex();
    $this.jqGrid('addRowData', id, { index: id });
    $this.jqGrid('editRow', id); */
    var cont = 0,
        descLabor = '',
        incr = 0;
    //console.log(arraySemSellVal);

    arraySemSellVal.reverse().forEach((respuesta) => {
        //console.log(respuesta);
        var next = $this.jqGrid('getCol', 'index', false, 'max');
        next = (isNaN(next) ? 1 : next + 1);
        if (cont <= 0) {
            descLabor = respuesta['Lab_Des'];
            valorWeek = ((respuesta['Det_Val'] * 1) * (respuesta['Det_Can'] * 1));
            $this.jqGrid('addRowData', next, $.extend(respuesta, { index: next, Det_Cod: respuesta['Det_Cod'], Act_Cod: respuesta['Act_Cod'], Lab_Cod: respuesta['Lab_Cod'], Per_Cod: respuesta['Per_Cod'], Det_Can_Mod: parseFloat(respuesta['Det_Can']).toFixed(2), Det_Obs: respuesta['Det_Obs'] }), 'last');
            $this.jqGrid('editRow', next);
            $('#formDatosConsultarLabor').updateGridsSizes();

            incr = 4;
            $this.find('#' + next + "_Labor_" + (3)).val(respuesta['Lab_Des']);
            $this.find('#' + next + "_Labor_" + (3) + '_span').html(respuesta['Lab_Des']);
            $this.find('#' + next + "_Cst_Sem" + cont + "_" + (next + incr)).val(formatMoney(respuesta['totalSem']));
            $this.find('#' + next + "_Cst_Sem" + cont + "_" + (next + incr) + '_span').html(formatMoney(respuesta['totalSem']));
            //Cst_Hec0
            $this.find('#' + next + "_Cst_Hec" + cont + "_" + (next + incr + 1)).val(formatMoney(respuesta['totalHect']));
            $this.find('#' + next + "_Cst_Hec" + cont + "_" + (next + incr + 1) + '_span').html(formatMoney(respuesta['totalHect']));
            cont++;


        } else {
            if (respuesta['Lab_Des'] === descLabor) {
                var numSemanas = $(".select_semana").val();
                var verficaArmado = verificarLaborArmado(respuesta);
                if (verficaArmado) {
                    next = posCambio;
                    posCambio = 0;
                } else {
                    next -= 1;
                    descLabor = '';
                }

                if (cont > 1) {
                    if (numSemanas.length <= cont || parseInt(respuesta['Act_Sem']) * 1 === cont) {
                        incr = incr;
                        cont = (cont - 1);
                    } else { incr = incr + 3; }
                } else { incr = incr + 4; }
                descLabor = respuesta['Lab_Des'];
                $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr)).val(formatMoney(respuesta['totalSem']));
                $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr) + '_span').html(formatMoney(respuesta['totalSem']));
                $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1)).val(formatMoney(respuesta['totalHect']));
                $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1) + '_span').html(formatMoney(respuesta['totalHect']));
                cont++;

            } else {
                //console.log('otro', respuesta);
                var numSemanas = $(".select_semana").val();

                var verficaArmado = verificarLaborArmado(respuesta);
                if (verficaArmado) {
                    next = posCambio;
                    posCambio = 0;
                }
                descLabor = respuesta['Lab_Des'];
                valorWeek = ((respuesta['Det_Val'] * 1) * (respuesta['Det_Can'] * 1));
                if (!verficaArmado) { $this.jqGrid('addRowData', next, $.extend(respuesta, { index: next, Det_Cod: respuesta['Det_Cod'], Act_Cod: respuesta['Act_Cod'], Lab_Cod: respuesta['Lab_Cod'], Per_Cod: respuesta['Per_Cod'], Det_Can_Mod: parseFloat(respuesta['Det_Can']).toFixed(2), Det_Obs: respuesta['Det_Obs'] }), 'last'); }
                $this.jqGrid('editRow', next);
                $('#formDatosConsultarLabor').updateGridsSizes();
                //console.log(incr);
                if (cont > 1) {
                    if (numSemanas.length <= cont || parseInt(respuesta['Act_Sem']) * 1 === cont) {
                        incr = incr;
                        cont = (cont - 1);
                    } else { incr = incr + 3; }
                } else { incr = incr + 4; }

                $this.find('#' + next + "_Labor_" + (3)).val(respuesta['Lab_Des']);
                $this.find('#' + next + "_Labor_" + (3) + '_span').html(respuesta['Lab_Des']);
                $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr)).val(formatMoney(respuesta['totalSem']));
                $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr) + '_span').html(formatMoney(respuesta['totalSem']));
                $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1)).val(formatMoney(respuesta['totalHect']));
                $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1) + '_span').html(formatMoney(respuesta['totalHect']));
                cont++;




            }

        }

    });
    actualizarValoresFooter();

}

async function sFinca() {
    const fnks = await allFincas();
    fnks.forEach((respuesta) => {
        $('.select_finca').append(
            $('<option>', { value: respuesta['Fnc_Cod'], text: respuesta['Fnc_Des'] + ' - ' + parseInt(respuesta['Fnc_Hec']) + ' Hectareas', hectareas: parseInt(respuesta['Fnc_Hec']) })
        );
    });

}

async function sWeek() {
    var sel_tipo = $('.select_perido').find('option:selected');
    var Fnk_Cod = $('.select_finca').find('option:selected');
    const wks = await allweek(sel_tipo, Fnk_Cod);
    $('.select_semana').empty();
    $('.select_semana').val([]);
    wks.forEach((respuesta) => {
        $('.select_semana').append($('<option>', { value: respuesta['Act_Cod'], text: 'Semana#' + respuesta['Act_Sem'] + ' ', semana: parseInt(respuesta['Act_Sem']) }));
        $('.select_semana').trigger('chosen:updated');
    });
    $("#btn_buscar").removeAttr("disabled");
}

function verificaSelSem() {

    //console.log('change select');
    selecSem.length = 0;
    selecSemF.length = 0;
    var sel_semana = $('.select_semana').find('option:selected');
    var testo = sel_semana.text();
    var valor = testo.split(' ')
    selecSem.push(valor);
    //console.log(selecSem[0].length);
    for (let i = 0; i < selecSem[0].length; i++) { if (selecSem[0][i] !== '') { selecSemF.push(selecSem[0][i]); } }
    //console.log(selecSemF);

}

function limpiarTodo() {
    //getColumGrid('frmLaborSearch', 'sinMetodo', 'noEsDialog');
    $('.select_semana').trigger('chosen:updated');
    $("#btn_limpiar").attr("disabled", "");
    $.jgrid.gridUnload('#list');
    selecSem.length = 0
    arraySemSel.length = 0
    arraySemSellVal.length = 0
    selecSemF.length = 0
    $('#frmLaborSearch')[0].reset();
    $('#list').clearGrid(true);
    // $('.lolaflores').html('');
    //clearSpan();
    $('.select_semana').trigger('chosen:updated');
    $("#f_periodo").attr("disabled", "");
    $("#por_peri").attr("disabled", "");
    $("#sel_per").attr("disabled", "");

}

function clearSpan() {
    for (i = 0; i < document.getElementById('gridContainer').getElementsByTagName('span').length; i++) {
        document.getElementById('gridContainer').getElementsByTagName('span')[i].innerHTML = '';
    }
}

function putDataGrid($this, next, cont, incr, respuesta) {
    $this.find('#' + next + "_Labor_" + (3)).val(respuesta['Lab_Des']);
    $this.find('#' + next + "_Labor_" + (3) + '_span').html(respuesta['Lab_Des']);
    $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr)).val(roundN(respuesta['totalSem'], 2));
    $this.find('#' + next + "_Cst_Sem" + cont + "_" + (incr) + '_span').html(roundN(respuesta['totalSem'], 2));
    //Cst_Hec0
    $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1)).val(roundN(respuesta['totalHect'], 2));
    $this.find('#' + next + "_Cst_Hec" + cont + "_" + (incr + 1) + '_span').html(roundN(respuesta['totalHect'], 2));
}


function verificaGenerar() {
    $("#btn_limpiar").removeAttr("disabled");
    $("#btn_buscar").attr("disabled", "");
}

function formatMoney(number, places, symbol, thousand, decimal) {
    number = number || 0;
    places = !isNaN(places = Math.abs(places)) ? places : 2;
    symbol = symbol !== undefined ? symbol : "$";
    thousand = thousand || ",";
    decimal = decimal || ".";
    var negative = number < 0 ? "-" : "",
        i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
    return symbol + negative + (j ? i.substr(0, j) + thousand : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
}

function roundN(num, n) {
    return parseFloat(Math.round(num * Math.pow(10, n)) / Math.pow(10, n)).toFixed(n);
}
// Metodos para  reporte individual
async function weekInd() {
    const numSem = await allFincas();
    //$('.select_finca_ind').empty();
    //$('.select_finca_ind').val([]);
    numSem.forEach((respuesta) => {
        $('.select_finca_ind').append(
            $('<option>', { value: respuesta['Fnc_Cod'], text: respuesta['Fnc_Des'] + ' - ' + parseInt(respuesta['Fnc_Hec']) + ' Hectareas', hectareas: parseInt(respuesta['Fnc_Hec']) })
        );
    });
}

function limpioTodo() {
    $('.select_semna_ind option:selected').removeAttr('selected');
    $('.select_finca_ind option:selected').removeAttr('selected');
    limpiaGrid();

}

function habilitaPeriodo() {
    /* $('.select_perido_ind').removeAttr("disabled");
    $('.select_perido_ind option:selected').removeAttr('selected');
    $('.select_semna_ind option:selected').removeAttr('selected');
    limpiaGrid(); */
    limpiaGrid();
    verficaSemanasInd();

}

async function verficaSemanasInd() {
    var sel_tipo = $('#frmLaborSearchWeeks').find('.select_perido_ind').find('option:selected');
    var Fnk_Cod = $('#frmLaborSearchWeeks').find('.select_finca_ind').find('option:selected');
    $('.select_semna_ind').empty();
    $('.select_semna_ind').val([]);
    const semanasActuales = await allweek(sel_tipo, Fnk_Cod);
    semanasActuales.forEach((respuesta) => {

        $('.select_semna_ind').append($('<option>', { value: respuesta['Act_Cod'], text: 'Semana#' + respuesta['Act_Sem'] + ' ', semana: parseInt(respuesta['Act_Sem']) }));
    });
    $('.select_semna_ind').removeAttr("disabled");
    $('#btn_buscar_ind').removeAttr("disabled");

}

function getBuildGrid(formulario, metodo, dialogo) {
    var model = {};
    laboresActives();
    model['names'] = [];
    model['colModel'] = [];
    model['colModel'].push({
        name: 'index',
        label: 'Index',
        width: 20,
        sorttype: 'int',
        align: 'center',
        hidden: true,
    }, {
        name: 'Act_Cod',
        label: 'Cod. Actividad',
        width: 20,
        sorttype: 'int',
        align: 'center',
        hidden: true,

    }, {
        label: 'Cod.Int.',
        name: 'Per_Cod',
        width: 8,
        sortable: false,
        hidden: false,
        formatter: 'input3',
        formatoptions: { id: '3', attr: '' }
    }, {
        label: 'Cedula',
        name: 'Prs_Ced',
        width: 15,
        align: 'left',
        sortable: false,
        hidden: false,
        formatter: 'inputL',
        formatoptions: { id: '3', attr: '' }


    }, {
        label: 'Trabajador',
        name: 'personal',
        width: 45,
        align: 'left',
        sortable: false,
        hidden: false,
        formatter: 'inputL',
        formatoptions: { id: '3', attr: '' }

    }, {
        label: 'Semana',
        name: 'Act_Sem',
        width: 20,
        //align: center,
        sortable: false,
        hidden: false,
        formatter: 'input3',
        formatoptions: { id: '3', attr: '' }
    }, {
        label: 'Total',
        name: 'totalSemGlobal',
        width: 10,
        align: 'right',
        sortable: false,
        hidden: false,
        formatter: 'inputR',
        formatoptions: { id: '3', attr: '' }

    });


    createGridWithSubgrid(model['colModel'], model['names']);
    disabledGenerar();

}

function disabledGenerar() {
    $("#btn_buscar_ind").attr("disabled", "");
}

async function laboresActives() {
    arraySingleWeek.length = 0;
    var Fnk_Cod = $('#frmLaborSearchWeeks').find('.select_finca_ind').find('option:selected');
    var semanaActiva = $('#frmLaborSearchWeeks').find('.select_semna_ind').find('option:selected');
    const tConSemanas = await allActFink(Fnk_Cod.val(), semanaActiva.val());
    for (let h = 0; h < tConSemanas.length; h++) {
        if (tConSemanas[h].Act_Cod === semanaActiva.val()) {
            //console.log(tConSemanas[h]);
            arraySingleWeek.push(tConSemanas[h]);
        }
    }

    calTotalLaborByTrab();
    getTabajadoresInd();

}

function getTabajadoresInd() {
    var $this = $('#listInd');
    var cont = 0;
    arrayTrabajadoresF.forEach((respuesta) => {
        var next = $this.jqGrid('getCol', 'index', false, 'max');
        next = (isNaN(next) ? 1 : next + 1);
        $this.jqGrid('addRowData', next, $.extend(respuesta, { index: next, Det_Cod: respuesta['Det_Cod'], Act_Cod: respuesta['Act_Cod'], Prs_Ced: respuesta['Prs_Ced'], Lab_Cod: respuesta['Lab_Cod'], Per_Cod: respuesta['Per_Cod'], totalSemGlobal: formatMoney(roundN(respuesta['totalSemGlobal'], 2)) }), 'last');
        $this.jqGrid('editRow', next);
        $('#formDatosConsultarLabor').updateGridsSizes();

    });
    calFooterForSubGr();
    getFinalSubGrid();
}

function getFinalSubGrid() {
    arrayTrabajadoresF.forEach((respuesta) => {
        getDetSubGrid(respuesta['Per_Cod'], respuesta['Act_Cod']);
    });
    //habilitaExportar();

}

function calFooterForSubGr() {
    $('#listInd').startGridEdit();
    let ids = $('#listInd').jqGrid('getDataIDs');
    var totG = 0,
        texto = '';
    for (let f = 0; f < ids.length; f++) {
        let valorSem = $('#listInd').jqGrid('getRowData', ids[f]);
        //console.log(valorSem);
        var valorT = parseFloat(valorSem['totalSemGlobal'].replace(/[^0-9-.]/g, ''));
        //console.log(valorT);
        totG += valorT;
    }
    texto = 'totalSemGlobal';
    var footer = {};
    footer[texto] = formatMoney(totG);
    $('#listInd').jqGrid('footerData', 'set', { Act_Sem: "TOTAL:" });
    $('#listInd').jqGrid('footerData', 'set', footer);

}

function calTotalLaborByTrab() {
    //arrayTrabajadoresF
    for (let c = 0; c < arraySingleWeek.length; c++) {
        if (arrayTrabajadoresF.length <= 0) {
            arraySingleWeek[c]['totalSem'] = ((arraySingleWeek[c]['Det_Val'] * 1) * (arraySingleWeek[c]['Det_Can'] * 1));
            arraySingleWeek[c]['totalSemGlobal'] = arraySingleWeek[c]['totalSem'];
            arrayTrabajadoresF.push(arraySingleWeek[c]);

        } else {
            //buscar si ese trabajador existe para incrementar el valor global
            var check = verificaTrabajador(arrayTrabajadoresF, arraySingleWeek[c]['Per_Cod'], arraySingleWeek[c]['Act_Sem']);
            if (check) {
                var newValor = ((arraySingleWeek[c]['Det_Val'] * 1) * (arraySingleWeek[c]['Det_Can'] * 1));
                arraySingleWeek[c]['totalSem'] = newValor;
                arraySingleWeek[c]['totalSemGlobal'] = valorTotExiste + newValor;
                modificarTotalExistente(arrayTrabajadoresF, valorTotExiste, newValor, arraySingleWeek[c]['Per_Cod'], arraySingleWeek[c]);
                valorTotExiste = 0;

            } else {
                arraySingleWeek[c]['totalSem'] = ((arraySingleWeek[c]['Det_Val'] * 1) * (arraySingleWeek[c]['Det_Can'] * 1));
                arraySingleWeek[c]['totalSemGlobal'] = arraySingleWeek[c]['totalSem'];
                arrayTrabajadoresF.push(arraySingleWeek[c]);

            }
        }

    }

}

function modificarTotalExistente(lista, valorActual, valorNew, campo, objeto) {
    var valCal = valorActual + valorNew;
    for (var e = 0; e < lista.length; e++) {
        if (lista[e]['Per_Cod'] === campo) {
            lista[e]['totalSemGlobal'] = valCal;
        }
    }
}

function verificaTrabajador(lista, codTrabajador, semana) {
    var stado = false;
    for (let d = 0; d < lista.length; d++) {
        if (lista[d]['Per_Cod'] === codTrabajador && lista[d]['Act_Sem'] === semana) {
            stado = true;
            valorTotExiste = lista[d]['totalSemGlobal'];
            break;
        }
    }
    return stado;

}

function CreateHiddenTable(ListOfMessages) {
    var vacio = '';

    var ColumnHead = ['Cod.Int', 'Cedula', 'Trabajador', 'Semana', 'Total'];
    var ColumnHeadSubGrid = ['', 'Fecha', 'Labor', 'Unidad', 'P.Unitario', 'Cantidad', 'Total'];

    var TableMarkUp = '<table id="myModifiedTable" class="visibilityHide"><thead><tr ><th><b>' + ColumnHead[0] + '</b></th><th><b>' + ColumnHead[1] + '</b></th><th><b>' + ColumnHead[2] + '</b></th><th><b>' + ColumnHead[3] + '</b></th><th><b>' + ColumnHead[4] + '</b></th>  </tr></thead><tbody>';

    for (let i = 0; i < arrayTrabajadoresF.length; i++) {
        //getDetSubGrid(arrayTrabajadoresF[i]['Per_Cod'], arrayTrabajadoresF[i]['Act_Cod']);
        TableMarkUp += '<tr bgcolor="#DFDFDF"><td>' + arrayTrabajadoresF[i]['Per_Cod'] + '</td><td>' + arrayTrabajadoresF[i]['Prs_Ced'] + '</td><td>' + arrayTrabajadoresF[i]['personal'] + '</td><td>' + arrayTrabajadoresF[i]['Act_Sem'] + '</td><td>' + arrayTrabajadoresF[i]['totalSemGlobal'] + '</td></tr>';
        if (arrayDetSubGrid.length > 0) {
            TableMarkUp += '<thead><tr bgcolor="#DFDFDF"><th><b>' + ColumnHeadSubGrid[0] + '</b></th><th><b>' + ColumnHeadSubGrid[1] + '</b></th><th><b>' + ColumnHeadSubGrid[2] + '</b></th><th><b>' + ColumnHeadSubGrid[3] + '</b></th><th><b>' + ColumnHeadSubGrid[4] + '</b></th><th><b>' + ColumnHeadSubGrid[5] + '</b></th><th><b>' + ColumnHeadSubGrid[6] + '</b></th></tr></thead>';
            for (let t = 0; t < arrayDetSubGrid.length; t++) {
                if (arrayTrabajadoresF[i]['Act_Cod'] === arrayDetSubGrid[t]['Act_Cod'] && arrayTrabajadoresF[i]['Per_Cod'] === arrayDetSubGrid[t]['Per_Cod']) {
                    //console.log(arrayDetSubGrid[t]);
                    TableMarkUp += '<tr><td>' + vacio + '</td><td>' + arrayDetSubGrid[t]['Det_Fec'] + '</td><td>' + (arrayDetSubGrid[t]['Lab_Des']) + '</td><td>' + arrayDetSubGrid[t]['Tpg_Des'] + '</td><td>' + roundN(arrayDetSubGrid[t]['Det_Val'], 2) + '</td><td>' + roundN(arrayDetSubGrid[t]['Det_Can'], 2) + '</td><td>' + arrayDetSubGrid[t]['total'] + '</td></tr>';
                }
            }

        }

    }
    TableMarkUp += "</tbody></table>";
    $('#MessageHolder').append(TableMarkUp);
}

function fnExcelReport() {
    var Messages = "\n message1.\n message2.";
    var ListOfMessages = Messages.split(".");

    CreateHiddenTable(ListOfMessages);

    tab_text = '<html xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">';
    tab_text = tab_text + '<head><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';

    tab_text = tab_text + '<x:Name>Hoja 1</x:Name>';

    tab_text = tab_text + '<x:WorksheetOptions><x:Panes></x:Panes></x:WorksheetOptions></x:ExcelWorksheet>';
    tab_text = tab_text + '</x:ExcelWorksheets></x:ExcelWorkbook></xml></head><body>';

    tab_text = tab_text + "<table border='1px'>";
    tab_text = tab_text + $('#myModifiedTable').html();;
    tab_text = tab_text + '</table></body></html>';

    data_type = 'data:application/vnd.ms-excel; charset=UTF-8';

    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE ");

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {
        if (window.navigator.msSaveBlob) {
            var blob = new Blob([tab_text], {
                type: "application/csv;charset=utf-8;"
            });
            navigator.msSaveBlob(blob, 'Test file.xls');
        }
    } else {
        //console.log(data_type);
        //console.log(tab_text);
        $('#testAnchor')[0].click()
    }
    $('#MessageHolder').html("");
}
$($("#testAnchor")[0]).click(function() {
    //console.log(data_type);
    //console.log(tab_text);
    $('#testAnchor').attr('href', data_type + ', ' + encodeURIComponent(tab_text));
    $('#testAnchor').attr('download', 'Resumen_Individual.xls');
});


var createExcelFromGrid = function(gridID, filename) {
    var grid = $('#' + gridID);
    var rowIDList = grid.getDataIDs();
    var row = grid.getRowData(rowIDList[0]);
    var colNames = [];
    var i = 0;
    for (var cName in row) {
        colNames[i++] = cName; // Capture Column Names
    }
    var html = "";
    for (var j = 0; j < rowIDList.length; j++) {
        row = grid.getRowData(rowIDList[j]); // Get Each Row
        for (var i = 0; i < colNames.length; i++) {
            html += row[colNames[i]] + ';'; // Create a CSV delimited with ;
        }
        html += '\n';
    }
    html += '\n';

    var a = document.createElement('a');
    a.id = 'ExcelDL';
    a.href = 'data:application/vnd.ms-excel,' + html;
    a.download = filename ? filename + ".xls" : 'DataList.xls';
    document.body.appendChild(a);
    a.click(); // Downloads the excel document
    document.getElementById('ExcelDL').remove();
}


function limpiaGrid() {
    $('#listInd').clearGrid(true);
    $.jgrid.gridUnload('#listInd');
    //arraySingleWeek.length = 0;
    arrayTrabajadoresF.length = 0;
    arrayDetSubGrid.length = 0;
    $("#btn_buscar_ind").removeAttr("disabled");

}


function createGridWithSubgrid(listColModel) {
    $('#listInd').createGrid({
            caption: 'REGISTRO INDIVIDUAL',
            height: 400,
            datatype: "local",
            regional: 'es',
            shrinkToFit: true,
            colModel: listColModel,
            pager: "#listPagerInd",
            rownumbers: true,
            rowNum: 10000,
            gridview: true,
            viewrecords: true,
            sortable: false,
            footerrow: true,
            userDataOnFooter: true,
            subGrid: true,
            subGridOptions: {
                "plusicon": "ui-icon-triangle-1-e",
                "minusicon": "ui-icon-triangle-1-s",
                "openicon": "ui-icon-arrowreturn-1-e",
                "reloadOnExpand": false,
                "selectOnExpand": true
            },
            subGridRowExpanded: function(subgrid_id, row_id) {
                //console.log(subgrid_id);
                //console.log(row_id);
                let subgrid_table_id = subgrid_id + "_t";
                subGridName = subgrid_table_id;
                let rowData = $("#listInd").getRowData(row_id);
                //console.log(rowData);
                $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
                $("#" + subgrid_table_id).createGrid({
                    url: "?movLabor=" + rowData['Act_Cod'] +
                        "&Per_Cod=" + rowData['Per_Cod'],
                    datatype: "json",
                    regional: 'es',
                    height: 'auto',
                    responsive: false,
                    colModel: [
                        { label: 'Fecha', name: 'Det_Fec', width: 15, align: "center" },
                        { label: 'Labor', name: 'Lab_Des', width: 45, align: "center" },
                        { label: 'Unidad', name: 'Tpg_Des', width: 20, align: "center" },
                        {
                            label: 'P.Unitario',
                            name: 'Det_Val',
                            width: 15,
                            align: "right",
                            formatter: 'currency',
                            formatoptions: {
                                prefix: '$ ',
                                thousandsSeparator: ',',
                                decimalSeparator: '.',
                                defaultValue: '',
                                decimalPlaces: 2
                            }
                        },
                        {
                            label: 'Cantidad',
                            name: 'Det_Can',
                            width: 15,
                            align: "right",
                            formatter: 'number',
                            formatoptions: {
                                thousandsSeparator: ',',
                                decimalSeparator: '.',
                                decimalPlaces: 2,
                                defaultValue: ''
                            }
                        },
                        {
                            label: 'Total',
                            name: 'total',
                            width: 15,
                            align: "right",
                            formatter: 'currency',
                            formatoptions: {
                                prefix: '$ ',
                                thousandsSeparator: ',',
                                decimalSeparator: '.',
                                defaultValue: '',
                                decimalPlaces: 2
                            }
                        },
                    ]

                });

            },
            loadComplete: function(data) {

            }

        }, false, "#listPagerInd", { view: false, refresh: false })
        .gridButtonsAdd([
            { id: 'btnExel2', caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function() { /*$("#listInd").jqGrid('exportGridExcel', { nombre: 'Resumen_Invidual', hoja: 'HOJA 1' });*/ fnExcelReport(); } }
        ]);
    $('#listPagerInd').find('.ui-pg-table table.navtable').find('td.ui-pg-button.ui-corner-all').unbind('mouseenter mouseleave').removeClass('ui-pg-button').addClass('btn btn-xs btn-success').find('.ui-pg-div span').removeClass('ui-icon').addClass('glyphicon');
    document.getElementById("btnExel2").setAttribute("disabled", "disabled");
    $("#btnExel2").off('click');

}

getRoles();
function getRoles(){
    $.post("",{'rolesAjax':true,'Pec_Cod':$("#Pec_Cod").val(),'Are_Cod':$("#Are_Cod").val(),'Rol_Tip':$("#Rol_Tip").val(),'Month':$("#Month").val() },function(responce){
        if(responce['success']===true){
            $("#Rol_Cod").empty();

            var selectElement = $("#Rol_Cod");
            var rolesWhere = '(';
            $.each(responce['rows'] , function(index, elemento){
                var nuevaOpcion1 = $("<option>", {value: elemento['Rol_Cod'],text: 'Rol #' + elemento['Rol_Num'] + ' Fecha:' + elemento['Rol_Fei'] +  ' - ' + elemento['Rol_Fef'] + ' Tipo:' + elemento['Rol_Tip'] + ' Area:' + elemento['Are_Des'] });
                selectElement.append(nuevaOpcion1);
                rolesWhere = rolesWhere + elemento['Rol_Cod'] + ',';
            });
            if (rolesWhere.endsWith(',')) {
                  rolesWhere = rolesWhere.slice(0, -1);
            }
            rolesWhere = rolesWhere + ') ';
        }
    },'json');

    $('#btn_buscar_certificado').removeAttr("disabled");
}

function printRolDetailIndiv(data){
    var Rol_Cod = $('#Rol_Cod').val(); 
    $.getDataJson('',$.extend(true,{},data,{printRolIndAjax:true,print:true, Rol_Cod: Rol_Cod}),function (r){ 
        $('#imprimirRoles').html(r['tabla']).printElement({pageTitle:'EXA - Sofware Contable'}); 
    }); 
}

$('#Month').on('change', function () {
    getRoles();
});