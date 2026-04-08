var btn_carg = false,
    btn_selc = false;
var arrayViajes = new Array();

$(function() {
    //Inicio Grid para presentar plantilla para reguistrar viajes de un cliente determinado
    $('#Viajes_Grid').createGrid({
        caption: 'REGISTRO DE VIAJES',
        height: '350',
        colModel: [
            { label: 'Cod', name: 'Via_Cod', key: true, hidden: true },
            { label: 'Cho_Cod', name: 'Cho_Cod', hidden: true, formatter: 'input2', formatoptions: { id: '2' } },
            { label: '<span class="required"></span> Conductor', name: 'Con_Duc', width: 80, align: 'center', title: true, formatter: 'input2', /*formatoptions:{class:'chofer',title:'Agregar Conductor',id:'2', attr: 'readonly' }*/ },
            { label: 'Veh_Cod', name: 'Veh_Cod', hidden: true, formatter: 'input2', formatoptions: { id: '2', attr: '' } },
            { label: '<span class="required"></span> Automotor', name: 'Aut_Mot', width: 40, align: 'center', title: false, formatter: 'input2', /*formatoptions:{class:'vehiculo',title:'Agregar Veh&iacute;culo',id:'2',attr: 'readonly' }*/ },
            { label: '<span class="required"></span> Cargamento*', hidden: true, name: 'Car_Cod', width: 65, align: 'center', title: false, formatter: 'select1', /*formatoptions:{class:'select_carga',id:'car_cod',title:'Agregar Cargamento', attr: 'readonly' }*/ },
            { label: '<span class="required"></span> Cargamento*', name: 'Car_Des', width: 65, align: 'center', title: false, formatter: 'select1', /*formatoptions:{class:'select_carga',id:'car_cod',title:'Agregar Cargamento', attr: 'readonly' }*/ },
            { label: '<span class="required"></span> Modo Trabajo*', hidden: true, name: 'Mot_Cod', width: 55, align: 'center', title: false, formatter: 'select1', /* formatoptions:{class:'select_modo',id:'mot_cod',title:'Agregar Modo Trabajo',action:"$('#modoDialog').dialog('open');"}*/ },
            { label: '<span class="required"></span> Modo Trabajo*', name: 'Mot_Des', width: 55, align: 'center', title: false, formatter: 'select1', /* formatoptions:{class:'select_modo',id:'mot_cod',title:'Agregar Modo Trabajo',action:"$('#modoDialog').dialog('open');"}*/ },
            { label: '<span class="required"></span> Fecha', name: 'Via_Fec', width: 40, align: 'center', title: false, formatter: 'input2', /*formatoptions:{id:'2',attr:'readonly'}*/ },
            { label: '<span class="required"></span> Origen', name: 'Via_Ded', width: 80, align: 'center', title: false, formatter: 'input2', /*formatoptions:{id:'2',attr:'readonly'}*/ },
            { label: '<span class="required"></span> Destino', name: 'Via_Has', width: 80, align: 'center', title: false, formatter: 'input2', /*formatoptions:{id:'2',attr:'readonly'}*/ },
            { label: '<span class="required"></span> Cant.', name: 'Via_Can', width: 30, align: 'center', title: false, formatter: 'input2', /*formatoptions:{id:'2',attr:'readonly'}*/ },
            { label: '<span class="required"></span> P.U.', name: 'Via_Pru', width: 30, align: 'center', title: false, formatter: 'input2', /*formatoptions:{id:'2',attr:'readonly'}*/ },
            { label: 'Total', name: 'Via_Tot', width: 30, align: 'center', title: false, formatter: 'input2', /*formatoptions:{id:'2',attr:'readonly'}*/ },
            { label: 'Via_Est', name: 'Via_Est', hidden: true, formatter: 'input2', /*formatoptions:{id:'2',attr:''}*/ },
            { label: 'Via_Aux', name: 'Via_Aux', hidden: true, formatter: 'input2', /*formatoptions:{id:'2',attr:''}*/ },
            /*{label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    return $.getGridButton(quitarViaje, rowObject,'Eliminar','glyphicon glyphicon-remove','','danger');
                }
            }*/
        ],
        pgbuttons: false,
        pgtext: null,
        footerrow: false,
        multiselect: true,
        multiPageSelection: true,
        beforeSelectRow: function(rowid, e) { return false; }
    }, true, '#Viajes_Page', { view: false, refresh: false }).gridButtonAdd({
        caption: "Agregar campo",
        id: 'btn_agr',
        buttonicon: "glyphicon glyphicon-plus",
        title: 'Agregar',
        onClickButton: function() { agregarFila(0); }
    });
    $.fn.fmatter.select1 = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            op = $('#' + set['id']).html(),
            el = $('<select  id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs ' + set['class'] + '" >' + op + '</select>');
        return el.prop('outerHTML');
    };
    $.fn.fmatter.select1.unformat = function(cv, opts, cObjt) { return $(cObjt).find(':input').val(); };

    $.fn.fmatter.input2 = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        if (set['id'] === '1') { el = $('<input type="text" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs ' + set['class'] + '" />'); } else { el = $('<input type="text" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs" ' + set['attr'] + '/>'); }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input2.unformat = function(cv, opts, cObjt) { return $(cObjt).find(':input').val(); };
    $('#btn_agr').hide();

});

var nViaje = {
    obj: null,
    set_viaje: function(viaj) { this.obj = nViaje },
    get_viaje: function() { return this.obj },
    update_viaje: function(viaj) { this.obj = $.extend(this.get_viaje(), viaj) },
    consultar_cliente: function(cli_cod) {
        return new Promise((resolve, reject) => {
            $.getDataJson("", { cargmentoAjax: true, Car_Cod: cli_cod }, function(buscar) {
                if (buscar['success']) {
                    var carg = buscar.cargamentoEncontrado;
                    console.log(carg['Car_Des']);
                    resolve(carg);
                }
                reject('error al cargar cargamento');
            }, function(err) {
                reject(err);
            });
        });
    }
}


$(function() {
    if ($('#viajeDialog').length > 0)
    //Inicio de diálogo para presentar los viajes de un determinado cliente
        $.createSearchDialog('viajeDialog', [
        { label: 'Cód.Int.', name: 'Prs_Cod', key: true, hidden: true },
        { label: 'C&eacute;dula', name: 'Prs_Ced', width: 30 },
        { label: 'Cliente(s)', name: 'cliente', width: 70 },
        { label: 'Nro. Viajes', name: 'viajes', width: 70, align: 'center' },
        {
            label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
            name: 'act1',
            width: 18,
            align: 'center',
            viewable: false,
            formatter: function(cellvalue, options, rowObject) {
                return $.getGridButton(datosViaje, rowObject);
            }
        }
    ], null, null, null, null, {
        title: 'Viajes por clientes',
        options: [{ label: '&nbsp;&nbsp;Apellido&nbsp;&nbsp;', value: 'd' },
            { label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c' }
        ]
    });

});

//Estilo cantidad
function styleCant(e, obj, opt) {
    e.style.textAlign = 'right';
    e.placeholder = '0';
    $(e).on('keyup', function() {
        if (isNaN(this.value)) { $(this).val('1').focus(); } else if (this.value % 1 !== 0) { var dec = String(this.value).split("."); if (typeof dec[1] !== 'undefined' && dec[1].length > 2) this.value = $.toFixed(this.value); }
        updateRowItem(obj);
    });
}

// Actualiza los valores de la fila
function updateRowItem(obj) {
    var row = $.extend({}, $('#Viajes_Grid').jqGrid('getRowData', obj['rowId']), $('#Viajes_Grid').find('tr#' + obj['rowId']).getDataForced());
    row['Via_Tot'] = row['Via_Can'] * (0 + row['Via_Pru']) * 1;
    $("#Viajes_Grid").jqGrid("setCell", obj['rowId'], "Via_Tot", row['Via_Tot']);
    $('#' + obj['rowId'] + '_Via_Tot').val($.toFixed(row['Via_Tot']));
}


//$(document).ready(function () { $('#btnCarga').click(function () { console.log('holis'); }); $('#btnSelecciona').on('click', function () { console.log('hola'); }); }
$(document).on('click', '#btnCarga', function() { btn_carg = true; });
$(document).on('click', '#btnSelecciona', function() { btn_selc = true; });


//Función para cargar datos de los viajes pertenecientes a un cliente
function datosViaje(objeto) {
    $('#viajeDialog').dialog('close');
    //.trigger('reloadGrid');
    //console.log(objeto);
    //console.log(btn_carg);
    ///console.log(btn_selc);
    if (btn_carg) {
        obtenerViajes(objeto);
        btn_carg = false;
        $('#Viajes_Grid').jqGrid('clearGridData', true);
        /*$.post("",{cargarViajes:true,Cli_Cod:objeto.Cli_Cod, Fecha:$('#por_fecha').is(':checked')?'S':'N', Fec_Ini:$('#Fec_Ini').val(), Fec_Fin:$('#Fec_Fin').val()},function(response){
           $.each(response,function(i,v){
              agregarFila(v['Via_Cod']);
              $('#Viajes_Grid').find('tr#'+v['Via_Cod']).setData(v,false);
              $('#frm_viajes').setData(objeto, false);

           });
         }, 'json').fail(function () { $.alert(); }); */

    }
    if (btn_selc) {
        $('input:text[name=Cli_Cod_Dest]').val(objeto['Cli_Cod']);
        $('input:text[name=cliente_Dest]').val(objeto['cliente']);
        $('input:text[name=Prs_Ced_Dest]').val(objeto['Prs_Ced']);
        $('input:text[name=Prs_Dir_Dest]').val(objeto['Prs_Dir']);
        btn_selc = false;
    }

}

function obtenerCarga(id) {
    // sola la fila
    // nViaje.consultar_cliente(id).then((result) => {
    //     console.log(result);
    // });

    $.getDataJson("", { cargmentoAjax: true, Car_Cod: id }, function(buscar) {
        var carg = buscar.cargamentoEncontrado;
        console.log(carg['Car_Des']);
        return carg;
    });

}


function obtenerViajes(objeto) {
    //console.log(objeto);
    var next = $("#Viajes_Grid").jqGrid('getCol', 'index', false, 'max');
    $.getDataJson("", { bucarAjax: true, Cli_Cod: objeto.Cli_Cod }, function(busqueda) {
        busqueda.todosViajes.forEach(function(valor) {
            //console.log(valor);
            $("#Viajes_Grid").jqGrid('addRowData', next, $.extend(valor, { index: next, Via_Cod: valor['Via_Cod'], Cho_Cod: valor['Cho_Cod'], Con_Duc: valor['Con_Duc'], Veh_Cod: valor['Veh_Cod'], Aut_Mot: valor['Aut_Mot'], Car_Cod: valor['Car_Cod'], Mot_Cod: valor['Mot_Cod'], Via_Fec: valor['Via_Fec'], Via_Ded: valor['Via_Ded'], Via_Has: valor['Via_Has'], Via_Can: valor['Via_Can'], Via_Pru: valor['Via_Pru'], Via_Est: valor['Via_Est'], Via_Tot: parseFloat(parseFloat(valor['Via_Can']) * parseFloat(valor['Via_Pru'])).toFixed(2) }), 'last');
            next = (isNaN(next) ? 1 : next + 1);
            //obtenerCarga(valor['Car_Cod']);
            //nViaje.consultar_cliente(valor['Car_Cod']).then((result) => { console.log(result); });
            //agregarFila(valor['Via_Cod']);
            //$('#Viajes_Grid').find('tr#'+valor['Via_Cod']).setData(valor,false);
            $('#frm_viajes').setData(objeto, false);
        });

    });
    /*$.getDataJson("", { cargarViajes: true, Cli_Cod: objeto.Cli_Cod, Fecha: $('#por_fecha').is(':checked') ? 'S' : 'N', Fec_Ini: $('#Fec_Ini').val(), Fec_Fin: $('#Fec_Fin').val() }, function (response) {
       console.log(response);
    });*/
}

function getSelectedRows() {
    $('input[type=checkbox]:checked').each(function() {
        var cadena = $(this).prop("id"),
            separador = "_",
            arregloDeSubCadenas = cadena.split(separador);
        //console.log(cadena);
        console.log(arregloDeSubCadenas[3]);
        var rowData = jQuery("#Viajes_Grid").getRowData(arregloDeSubCadenas[3]);
        //console.log(rowData);
        arrayViajes.push(rowData);
    });
    //console.log(arrayViajes);
    //console.log(arrayViajes.length);
}


//Estilo precio unitario
function stylePru(e, obj, opt) {
    e.style.textAlign = 'right';
    e.placeholder = '0.00';
    $(e).on('keyup', function() {
        if (isNaN(this.value)) { $(this).val('').focus();; } else if (this.value % 1 !== 0) { var dec = String(this.value).split("."); if (typeof dec[1] !== 'undefined' && dec[1].length > 8) this.value = $.toFixed(this.value, 8); }
        updateRowItem(obj);
    });
}

//Función para eliminar un registro del grid
function quitarViaje(viaje) {
    $.createDialogConfirm('Desea Eliminar el item seleccionado..!!', null, function() {
        var aux = $('#' + viaje.Via_Cod + '_Via_Aux').val();
        if (aux === 'N') {
            $("#Viajes_Grid").jqGrid('delRowData', viaje.Via_Cod);
        } else {
            $('#' + viaje.Via_Cod + '_Via_Est').val('I');
            $('#' + viaje.Via_Cod).hide();
        }
    });
}

/*** FUNCIONES PARA EL MANEJO DE DATOS ***/

//Función que agrega una fila al grid=>Via_Grid
function agregarFila(aux) {
    $('#Viajes_Grid').jqGrid('resizeGrid');
    var $this = $('#Viajes_Grid'),
        id, nuevo;
    if (aux < 1) {
        id = ($this.jqGrid('getCol', 'Via_Cod', false, 'max') + 1) || 0;
        nuevo = 'N';
    } else {
        id = aux;
        nuevo = 'A';
    }
    //console.log(id);
    //console.log('estamos en el agregarFila');
    $this.jqGrid('addRowData', id, { 'Via_Cod': id });
    //$this.jqGrid('editRow',id);
    //$('#'+id+'_Via_Aux').val(nuevo);

    /*$('#'+id+'_Aut_Mot').focus(function(){
        crear(id,this.name);
    });

    $('#'+id+'_Con_Duc').focus(function(){
        crear(id,this.name);
    });

    $.createDatePickers('#'+id+'_Via_Fec');  */
}

/*function crear(id,name){
      $('#'+id+'_'+name).autocomplete({
          source:(name==='Aut_Mot')?arr_aut:arr_cho,
          response: function(event, ui) {
              if (!ui.content.length) {
                  if(name==='Aut_Mot'){
                      var noResult = { label:"Vehículo NO Registrado" };
                  }else{
                      var noResult = { label:"Conductor NO Registrado" };
                  }
                  ui.content.push(noResult);
              }
          },
          select: function( event, ui ) {
              if(name==='Aut_Mot'){
                  $('#'+id+'_Veh_Cod').val(ui.item.Veh_Cod);
              }else{
                  $('#'+id+'_Cho_Cod').val(ui.item.Cho_Cod);
                  $('#'+id+'_Con_Duc').val(ui.item.label);
                  $('#'+id+'_Aut_Mot').val(ui.item.Veh_Pla);
                  $('#'+id+'_Veh_Cod').val(ui.item.Veh_Cod);
              }
          }
      });
}*/


//Función para guardar datos
/*   function saveDatos(frm,save,dialogo){
      var data=$('#'+frm).getData('save');data[save]=true;
      if(dialogo==='cargamento'){if($('#Ite_Lar').val()===''){$.alert('Debe seleccionar un producto..!!');return;}}
      $.post("",data,function(response){
          if(response.success===true){
              if(dialogo==='cargamento'){
                  $('.select_carga').append($('<option>', {value: response['registro']['Car_Cod'],text: response['registro']['Car_Des']}));
              }
              if(dialogo==='modo'){
                  $('.select_modo').append($('<option>', {value: response['registro']['Mot_Cod'],text: response['registro']['Mot_Des']}));
              }
              if(dialogo==='chofer'){
                  arr_cho.push(response['registro']);
              }
              if(dialogo==='automotor'){
                  arr_aut.push(response['registro']);
              }
              $('#'+frm)[0].reset();$('#'+dialogo+'Dialog').dialog('close'); return;
          }
      },'json').fail(function(){$.alert();});
}
  */

function confirmaGuardado() {
    $.createDialogConfirm(`¿Est&aacute; seguro que desea guardar?`, null, saveViaje);
}

function saveViaje() {
    getSelectedRows();
    var index;
    var data = $('#frm_viajes').getData('saveViaje');
    //console.log(data['Cli_Cod_Dest']);
    //data['campos'] = $("#Viajes_Grid").getGridBatch();
    //console.log(data['campos']);
    if (data['Cli_Cod_Dest'] === '' || data['Cli_Cod_Dest'] === data['Cli_Cod']) {
        $.alert('Debe revisar la información en la Cliente Destino. ');
        $('#Viajes_Grid').startGridEdit();
        return false;
    } else {
        data['campos'] = arrayViajes;
        arrayViajes = [];
    }
    //console.log(data['campos']);
    $.each(data['campos'], function(i, v) {
        if (v['Con_Duc'] === '' || v['Aut_Mot'] === '' || v['Via_Fec'] === '' || v['Via_Ded'] === '' || v['Via_Has'] === '' || v['Via_Can'] === '' || v['Via_Pru'] === '') {
            index = $("#Viajes_Grid").jqGrid('getInd', v['Via_Cod']);
            $.alert('Debe completar información en la fila: ' + index);
            $('#Viajes_Grid').startGridEdit();
            return false;
        }
    });

    if (index * 1 > 0) return false;

    if ($('#cliente').val() === '') { $.alert('Debe seleccionar un cliente..!!'); return; }

    if ((data['campos'].length) < 1) { $.alert('Debe existir al menos un registro de viaje seleccionado..!!'); return false; }

    //console.log(data);
    //console.log(data['Cho_Cod'].length);
    if (data['Cho_Cod'].length > 0) {
        for (var i = 0; i <= data['Cho_Cod'].length; i++) {
            //data['Aut_Mot'].splice(0, data['Aut_Mot'].length);
            //data['Car_Cod'].splice(0, data['Car_Cod'].length);
            data['Cho_Cod'].splice(0, data['Cho_Cod'].length);
            //data['Con_Duc'].splice(0, data['Con_Duc'].length);
            //data['Mot_Cod'].splice(0, data['Mot_Cod'].length);
            data['Veh_Cod'].splice(0, data['Veh_Cod'].length);
            //data['Via_Aux'].splice(0, data['Via_Aux'].length);
            // data['Via_Can'].splice(0, data['Via_Can'].length);
            //data['Via_Ded'].splice(0, data['Via_Ded'].length);
            //data['Via_Est'].splice(0, data['Via_Est'].length);
            //data['Via_Fec'].splice(0, data['Via_Fec'].length);
            //data['Via_Has'].splice(0, data['Via_Has'].length);
            //data['Via_Pru'].splice(0, data['Via_Pru'].length);
            // data['Via_Tot'].splice(0, data['Via_Tot'].length);
        }
    }

    $.post("", data, function(response) {
        if (response.success === true) {
            $.alert("Transaccion Realizada con &Eacute;xito!");
            $('#frm_viajes')[0].reset();
            $('#Viajes_Grid').jqGrid('clearGridData', true).trigger('reloadGrid');
            $("#viajeDialog").getDialogGrid().trigger('reloadGrid', [{ page: 1 }]);
        }
    }, 'json').fail(function() { $.alert(); });
}