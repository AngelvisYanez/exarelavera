/**
 * @fileoverview Libreria con funciones de validaciones
 *
 * @author Erick Cordova
 * @version 1.0
 */
/**
 * Validar la modificacion de comprobantes libro banco
 */
var lis_che=[];

$(function () {
    
    $('#chequeDialog').createDialogDetail({                                         
        caption:'Cheques Asociados al Comprobante',
        cmTemplate: {sortable:false},colModel: [
            { label: 'C&oacute;d.Int.', name: 'Che_Cod', key: true,viewable: true,width:40 },                                
            { label: 'Fecha', name: 'Che_Fec', width: 100 },
            { label: 'Num.', name: 'Che_Num', width: 50,align:"center" },                        
            { label: 'Banco', name: 'Pld_Des', width: 150,title:'Cuenta Bancaria' },
            { label: 'Beneficiario', name: 'Che_Ben', width: 175 },
            { label: 'Estado', name: 'Che_Est', width: 70,align:"center",formatter: 'select', edittype: "select", editoptions: {value: "A:Activo;I:Inactivo;C:Cobrado"}, align: "center"},
            { label: 'Valor', name: 'Che_Val', key: true, width: 70,align:"right" , formatter:'currency', decimalPlaces: '2', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}}
        ],loadComplete:function(data){


            if ($.varValid(data.rows))
                for (var i = 0, z = data.rows.length; i < z; i++) {
                    if (data.rows[i]['Che_Est'] === 'I')
                        $("#" + data.rows[i].Che_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
                }


        }
    },{icon:'usd'});
    
    
    $('#documentoMain').css('visibility', '').hide();
    $('#Pec_Cod').on('change',function(){limit_fec($(this).find(':selected').data())});
    $('#Pld_Cod,#periodos').chosen({no_results_text: "Oops, sin coincidencias para"});
    $('#perio_cont').on('change', function () {
        //console.log(this);
        $('input[name=Pec_Cod]').val($(this).val());
        $('input[name=periodo]').val($(this).find(':selected').data('Periodo'));
    });
    //$('#periodos').chosen({no_results_text: "Oops, sin coincidencias para"});
    cargarPeriodos();
    $('#Pec_Cod').on('change', function () {
        cargarBancos();
    });
    crear_grid_comprobantes();
    createGridCheques();
    //gridStartMovimiento();
    $('.datepicker').createDatePickers({checkAvailability: false, hideMsg: false}).mask("9999-99-99", {placeholder: "_"});
    //$('#periodos').on('change', changePerido);
    $('#TipBus').chosen();
    $('#Bak_Cod_Selec').chosen({no_results_text: "Oops, sin coincidencias!"});
    $('#documentoResult').css('visibility', '').hide();
    $('#documentoResult').show();
    $('#gestionarDialog').createDialog({icon: 'plus', width: 500, height: 356});
    $('#protestarDialog').createDialog({icon: 'plus', width: 500, height: 356});
    $('#successDialog').createDialog({width: 500, height: 200});
    $('#cheCreateDialog').createDialog({height:235,icon:'usd'});
    
    
    
    $('#OrderBy').on('change',function(){
        $('#Order_By').val($(this).val()); 
        $('#searchComprobantes').formSubmit(); 
    });
});


function createGridCheques() {
    let gridChe = $("#chequesGrid");
    gridChe.createGrid({
        colModel: [
            {label: 'C&oacute;d.Int.', name: 'Index', width: 50, align: "center",key: true,hidden:true},
            {label: 'N&uacute;mero', name: 'Che_Num', editable: true, align: 'center', width: 90, align: "center"},
            {label: 'asi_cod.', name: 'Asi_Cod', width: 50,hidden:true, align: "center"},
            {label: 'Fecha', name: 'Che_Fec', editable: true, align: 'center', width: 90},
            {label: 'Banco', name: 'Ban_Cod', align: 'center',hidden:true, width: 90},
            {label: 'Observaci&oacute;n', name: 'Che_Obs', align: 'center',hidden:true, width: 90},
            {label: 'Beneficiario', name: 'Che_Ben', align: 'center', editable: true, width: 180},
            {label: 'Valor', name: 'Che_Val', width: 100, editable: true, formatter: 'currency', align: "right"},
            {label: '&nbsp;', name: 'act1', width: 40, align: 'center', viewable: false, formatter: function (cv, opts, rObj) {
                    return $.getGridButton(editFilaCheque, rObj, 'Modificar', 'fa-pencil', null, 'info');
                }
            },
            {label: '&nbsp;', name: 'act2', width: 30, align: 'center', viewable: false, formatter: function (cv, opts, rObj) {
                    return $.getGridButton(deleteFilaCheque, rObj, 'Quitar', 'remove', null, 'danger');

                }
            }
        ], height: 'auto', caption: "Egreso de Cheques", footerrow: true, userDataOnFooter: false // set a footer row
    }, true, "#chequesPager", {view: false}).gridButtonAdd({
        caption: "Agregar Cheque", buttonicon: "glyphicon glyphicon-plus", title: 'Agregar Cuenta', id: "add_cuenta", onClickButton: function () {
            if(getBanco()['Pld_Cod']*1>0){
                $('#cheForm').setData({'Che_Ben':$('#lblProvee').val(),'Che_Fec':$('#confec').val()});
                $('#cheCreateDialog').dialog('open');
            }else{
                $.alert('Seleccione el banco banco');
            }
        }
    });
}


function cargarCheques(Com_Cod){
    $('#chequeGrid').Search({'searchCheque':true,'Com_Cod':Com_Cod});
    $('#chequeDialog').dialog('open');
}


function crear_grid_comprobantes() {
    $('#searchGrid').createGrid({
        caption: 'Resultado de la B&uacute;squeda', height: 270, datatype: "local", caption:'Resultados <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="order by Com.Com_Fec DESC ">Fecha</option><option value="order by Tia_Ini DESC, Com.Com_Num ">Comprobante</option><select>&nbsp;</div>',
        colModel: [
            {label: 'C&oacute;d. Int.', name: 'Com_Cod', width: 30, align: "center", key: true},
            {label: 'Comprobante', name: 'Compr_Num', width: 45, /*formatter: format_com_num, unformat: unformat_com_num,*/ align: "center"},
            {label: 'Cliente/Proveedor', name: 'Prov_Cli', align: "left"},
            {label: 'Cliente', name: 'Cli_Cod', hidden: true},
            {label: 'Proveedor', name: 'Prv_Cod', hidden: true},
            {label: 'Usuario', name: 'Usu_Cod', hidden: true},
            {label: 'Usuario', name: 'Usuario', width: 90, align: "left"},
            {label: 'Tipo', name: 'Tia_Cod', hidden: true},
            {label: 'Tipo', name: 'Tia_Ini', formatter: 'select', edittype: "select", editoptions: {value: "I:Ingreso;E:Egreso;D:Diario"}, width: 35, align: "center"},
            {label: 'Fecha', name: 'Com_Fec', width: 30, align: "center"},
            {label: 'Valor', name: 'Com_Val', width: 30, formatter: 'currency', align: "right"},
            {label: 'Estado', name: 'Com_Est', width: 25, align: "center", formatter: 'truefalse', formatoptions: {yesValue: 'A', yesMsg: 'Comprobante Activo', noMsg: 'Comprobante Inactivo'}},
            {label: 'Generado', name: 'Com_Gen', formatter: 'select', edittype: "select", editoptions: {value: "A:Automatico;M:Manual"}, width: 35, align: "center"},
            {label: 'Cheque', name: 'Has_Cheque', width: 25, align: "center", formatter: function(cv,ops,arr){return cv==='no'?'':$.getGridButton(cargarCheques,arr.Com_Cod,'Ver Cheques Asociados','fa fa-money white',undefined,arr.alert?'warning':'info') }},
            {label: '&nbsp;', name: 'act0', width: 20, align: 'center', viewable: false, formatter: 'edicion', title: false}
        ],loadComplete:function(data){


            if ($.varValid(data.rows))
                for (var i = 0, z = data.rows.length; i < z; i++) {

                    if (data.rows[i]['Com_Est'] === 'I')
                        $("#" + data.rows[i].Com_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
                }


        }}, false, '#searchGridPager', {refresh: true});
}

var cargarPeriodos = () => {
    $.getDataJson('', {'cargarPeriodos': true}, function (resp) {
        $.each(resp['periodos'], function (index, periodo) {
            var option = $('<option></option>').text('Periodo ' + periodo.Periodo).attr('value', periodo.Pec_Cod).data(periodo);
            $('#Pec_Cod').append(option.clone(true));
            $('#perio_cont').append(option);
        });
        $('#Pec_Cod').trigger("chosen:updated");
    }, function (err) {
        console.log(err['messsage']);
    });
};
var cargarBancos = (Pec_Cod = $('#Pec_Cod').val(), element = '#Pld_Cod', Pld_Cod) => {
    //console.log('movimiento');
    $.get('', {'getBancos': true, 'Pec_Cod': Pec_Cod}, function (r) {
        if (r['success'] === true) {
            $(element).html(r['options']).val('');
            $(element).trigger("chosen:updated");
            if (Pld_Cod) {
                $(element).val(Pld_Cod).trigger('change');
            }
        }
    }, 'json').fail(function (a) {
        console.log(a);
    });
};

$.fn.fmatter.edicion = function (cv, opts, cObjt) {
//    if($.varValid(edicion_ventas)){
//        if(cObjt['Com_Edit']==='N') return '<i title="El comprobante contable es formato anterior" class="glyphicon glyphicon-lock orange"></i>';
if(cObjt['Com_Est']==='I') return '<i title="Registro Anulado/Inactivo" class="glyphicon glyphicon-remove red"></i>';
//        //if(cObjt['Cpc_Edit']==='N') return '<i title="Contiene Pagos Activos" class="fa fa-money green"></i>';
//        if(cObjt['Vet_Aut']==='S' && edit_reten===false) return '<i title="Documento Autorizado por SRI" class="fa fa-globe green"></i>'
//    }
    return $.getGridButton(cargarDoc, cObjt);
};


var cargarDoc = (obj_row) => {
    
    //$('#bancos').attr('onchange',"");
    $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
    let gridChe = $("#chequesGrid");
    gridChe.getDataIDs().map((row)=>gridChe.delRowData(row*1));
    $('#periodoForm').setData(obj_row);
    
    $('#perio_cont').removeAttr('onChange');
    $('#perio_cont').val(obj_row['Pec_Cod']).trigger('change');
    setPeriodo(obj_row['Pld_Cod']);
    remove_opt = [{'I': '#formIngreso', 'tipo': 'Ingresos'}, {'E': '#formComp', 'tipo': 'Egresos'}, {'D': '#formDiario', 'tipo': 'Diario'}];
    let rem = [], act = {indice: 0, formulario: '', tipo: ''};
    remove_opt.map((opt, ind) => {
        (Object.keys(opt)[0] !== obj_row['Tia_Ini'] ? rem.push(ind) : act = {'indice': ind, 'formulario': opt[obj_row['Tia_Ini']], 'tipo': opt['tipo']});
    });
    $("#tabs").tabs("option", "disabled", rem);
    $("#tabs").tabs("option", "active", act['indice']);
    obj_row['Num_Doc'] = obj_row['Com_Doc'];
    $(act['formulario']).setData(obj_row);
    tipo = (act['tipo']);
    if (tipo === 'Ingresos') {
        selectClie({'Cli_Cod': obj_row['Cli_Cod'], 'clientes': obj_row['Prov_Cli']});
    } else {
        selectProv({'Prv_Cod': obj_row['Prv_Cod'], 'Prs_Ced': obj_row['Prov_Cli_Ced'], 'proveedor': obj_row['Prov_Cli'], 'Prs_Ape': obj_row['Prov_Apellido'], 'Prs_Nom': obj_row['Prov_Nombre'], 'Prv_Est': obj_row['Prov_Estado']});
    }
    $('#cheques_grilla').hide();
    cargarAsientos(obj_row['Com_Cod']);
   //console.log(obj_row);
    
};


var cargarAsientos = (Com_Cod) => {
    
    $('#es_cheque').prop('checked',false);
    lis_che=[];
    let gridChe = $("#chequesGrid");
    $.get('', {'getAsientos': true, 'Com_Cod': Com_Cod}, function (r) {
        if (r['success'] === true) {
            //console.log(r['asientos']);
            let asiento_cheque=[];
            let asientos_complex=[];
            r['asientos'].filter((fila, indice) => { 
                let bool=fila['Che_Cod'] !== null;
                bool?lis_che.push(fila['Che_Num']):'';
                return bool;
            }).map((fila,ind) => {
                asiento_cheque.push({indice:ind+1,Asi_Cod:fila.Asi_Cod});
                gridChe.addRowData(ind+1, $.extend(fila,{'Index':ind+1}), 'last');
            });
            r['asientos'].map((asi)=>{asiento_cheque.map((cheque)=>{
                    if(cheque.Asi_Cod===asi.Asi_Cod){
                        asi['Che_Ind']=cheque.indice;
                        $('#es_cheque').prop('checked',true);
                        $('#cheques_grilla').show(500);
                        $('#cheques_grilla').updateGridsSizes();
                    }
                });
                asientos_complex.push(asi);
            });
            gridComp.setRowsByIndex(asientos_complex, 'Index');
            gridComp.startGridEdit().loadUpdate().updateGridDiario();
            //$('#bancos').attr('onchange',setBanco());
        }
    }, 'json').fail(function (a) {
        console.log(a);
    });
};


function addFilaCuenta(cuenta, tipo,cheque=false) {
    //console.log(cuenta);
    var setter = {Index: $('#Index').val(), Glosa: cheque?cuenta.glosa:glosa, Det_Tip: tipo, Debe: tipo === 'D' ? valor :0, Haber: tipo === 'H' ? (cheque?cuenta.Haber:valor) : 0};
    if (setter['Index'] === '') {
        var max = gridComp.jqGrid('getCol', 'Index', false, 'max'), next = (isNaN(max) ? 1 : max + 1);
        setter['Index'] = next;
        gridComp.jqGrid("addRowData", setter['Index'], $.extend(cuenta, setter), "last");
        resizeGridComp();
    } else {
        gridComp.jqGrid('saveRow', setter['Index'], false, 'clientArray');
        var old_data = gridComp.jqGrid('getRowData', setter['Index']);
        setter['Glosa'] = old_data['Glosa'];
        setter[tipo === 'D' ? 'Debe' : 'Haber'] = old_data[old_data['Det_Tip'] === 'D' ? 'Debe' : 'Haber'];
        gridComp.jqGrid('setRowData', setter['Index'], $.extend(cuenta, setter));
        $('#cuenDialog').dialog('close');
    }
    gridComp.jqGrid('editRow', setter['Index']);
    gridComp.updateGridDiario();
}

function setCheque(act) {
    let gridChe = $("#chequesGrid");
   if($(act).prop('checked')){
       $('#cheques_grilla').show(500);
       gridComp.getRowData().filter((asi)=>asi.Det_Tip==='H').map((asiento_delete)=>{gridComp.delRowData(asiento_delete.Index*1);});
    }else{
       $('#cheques_grilla').hide(500);
       gridChe.getRowData().map((cheque)=>deleteFilaCheque(cheque));
        addFilaCuenta(getBanco(), "H");
   }
}

function editFilaCheque(row){
    //console.log(row);
    $('#cheForm').setData(row);
    $('#cheCreateDialog').dialog('open');
}

function deleteFilaCheque(fila) {
    let gridChe = $("#chequesGrid");
    gridComp.getRowData().filter((asi)=>asi.Che_Ind*1===fila.Index*1).map((asiento_delete)=>{gridComp.delRowData(asiento_delete.Index*1);});
    gridChe.jqGrid('delRowData', fila.Index);
	$("#chequesGrid").getDataIDs().length>0?'':$('#es_cheque').prop('checked',false).trigger('change');
    resizeGridComp();
    gridChe.updateGridDiario();

}

function validaFormCheque(e){
    $('#Index').val('');
    let form_data=$('#cheForm').getData(),indice=form_data['Index']*1;
    form_data['Che_Ban']=$('#bancos').find(':selected').data('Ban_Cod');
    let gridChe = $("#chequesGrid");
    //console.log('indice',indice>0);
    if(indice>0){
        gridChe.changeRow(indice,form_data,{act1:'',act2:''}); 
        gridComp.stopGridEdit();
        gridComp.getRowData().filter((asi)=>asi.Che_Ind*1===indice).map((asi)=>gridComp.changeRow(asi.Index,$.extend(asi,{Haber:form_data.Che_Val})));
    }else{
        let next=gridChe.nextIndex('Index');
        let nex_asi=gridChe.nextIndex('Index');
        let banc_sel=$('#bancos').find(':selected');
        gridChe.addRowData(next,$.extend(form_data,{Index:next,Ban_Cod:banc_sel.data('Ban_Cod')}),'last');
        addFilaCuenta({Debe:0,Det_Tip:'H',Haber:form_data.Che_Val*1,Pld_Cdc:banc_sel.data('Pld_Cdc'),Pld_Cod:banc_sel.data('Pld_Cod'),Pld_Des:banc_sel.data('Pld_Des'),glosa:'Cheque N.'+form_data.Che_Num,Che_Ind:next},'H',true);
    }
    $('#cheCreateDialog').dialog('close');
    gridComp.startGridEdit().loadUpdate();
    gridComp.updateGridDiario();
    
    return false;
}

function validaCheque(e,numAnt=$('#NumChe').val()) {
    //console.log('entro');
    let arrCheNum=[];
    $("#chequesGrid").getRowData().filter((fila)=>fila.Index!==$('#cheForm').getData()['Index']).map((fila)=>arrCheNum.push(fila.Che_Num));
    if (tipo === "Egresos" && $("#bancos").val() !== '') {
        //console.log('en lista::>',$.inArray(numAnt,lis_che));
        if($.inArray(numAnt,lis_che)===-1){
            $.get("",{'Pld_Cod': getBanco()["Pld_Cod"], 'valChe':true,'numero': numAnt}, function (response) {
                if (response['success'] === true) {
                    if (response['valid'] === false) {
                        numChe = (response['Che_Num'] * 1) + 1;
                        alertaAuto('El Cheque <b>No. ' + numAnt + '</b> ya existe.','#NumChe');
                        return false;
                    } else {
                        if($.inArray(numAnt,arrCheNum)>=0){
                            alertaAuto('Ha sido utilizado','#NumChe','bottom');
                            return false;
                        }else{
                            $("#NumChe").alertMsg();
                            validaFormCheque(e);
                        } 
                    }
                } else {
                    numChe = 0;
                    $("#NumChe").val(numChe);
                    $.alert("No se logr&oacute; obtener n&uacutemero del cheque");
                    return false;
                }
            }, 'json').fail(function (error) {
                $.alert("El Servidor ha fallado en responder!");
            });  
        }else{
            if($.inArray(numAnt,arrCheNum)>=0){
                alertaAuto('est&aacute; siendo utilizado','#NumChe','bottom');
                return false;
            }else{
                validaFormCheque(e);
            }                
        }

    }
}

function alertaAuto(mensaje,componente,direccion='bottom'){
                $(componente).flyout('hide');
                $(componente).createFlyout(mensaje,{icon:'exclamation',placement:direccion,timeDismis:6000,width:90});
                $(componente).flyout('show');
            }
            
var limit_fec=(data)=>{
    if(!$.isEmptyObject(data)){
        //console.log(data);
        rango=`${data.Periodo}:${data.Periodo}`;
        //console.log(rango);
        $( "#txt_fec_ini,#txt_fec_fin" ).datepicker( "option", "yearRange",rango);
        $( "#txt_fec_ini").datepicker( "setDate", data.Pec_Fei );
        $( "#txt_fec_fin").datepicker( "setDate", data.Pec_Fef );
    }else{
        $( "#txt_fec_ini,#txt_fec_fin" ).datepicker( "option", "yearRange",'c-10:c+10');
    }      
};