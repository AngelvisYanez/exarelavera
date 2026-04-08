/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$(function () {
    $('#documentoMain').css('visibility', '').hide();
    $('#Pec_Cod').on('change',function(){limit_fec($(this).find(':selected').data())});
    $('#Pld_Cod,#periodos').createChosen('input-sm',{placeholder_text_single:'seleccione un banco',no_results_text: "Oops, sin coincidencias para"});
    $('.datepickers').createDatePickers({checkAvailability: false, hideMsg: false}).mask("9999-99-99", {placeholder: "_"});
    
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
    
    
    $('.datepicker').createDatePickers({checkAvailability: false, hideMsg: false}).mask("9999-99-99", {placeholder: "_"});
    //$('#periodos').on('change', changePerido);
    $('#documentoResult').css('visibility', '').hide();
    $('#documentoResult').show();
    $('#gestionarDialog').createDialog({icon: 'plus', width: 500, height: 356});
    $('#protestarDialog').createDialog({icon: 'plus', width: 500, height: 356});
    $('#successDialog').createDialog({width: 500, height: 200});
    $('#modelo').createDialog({width: 250, height: 125});
    $('#cheCreateDialog').createDialog({height:235,icon:'usd'});
    if(baja_che===1){
        $('#Tipo_Eliminacion').on('change',function(event){
            let evaluar= $(this).find('option:selected').val()*1;
            switch (evaluar) {
                case 1:
                    $('#panel_secuencia').removeClass('hidden');
                    $('#panel_uno_uno').addClass('hidden');
                    $('[name=Secuencia_Fin]').attr('required');
                    $('[name=Secuencia_Ini]').attr('required');                
                    break;
                case 2:
                    $('#panel_secuencia').addClass('hidden');
                    $('#panel_uno_uno').removeClass('hidden');
                    $('[name=Secuencia_Fin]').removeAttr('required');
                    $('[name=Secuencia_Ini]').removeAttr('required'); 
                    break;
            }
        }).trigger('change');
        cargarBancos();
        $('#add_num').on('click',function(){
            let num=$('#numero_nuevo').val();
            agregarNumero(num);
        });
    }else{
        crear_grid_comprobantes();
    }
    
    
    
    $('#OrderBy').on('change',function(){
        //console.log($(this).val());
        $('#Order_By').val($(this).val()); 
        $('#searchComprobantes').formSubmit(); 
    });
});

function sumNotAnulado(v,n,obj){ return isNaN(v)?0:(obj['Che_Est']==='I'||obj['Che_Est']==='P'?0:v); }
function crear_grid_comprobantes() {
    $('#searchGrid').createGrid({
        caption: 'Resultado de la Búsqueda', height: 270, datatype: "local", caption:'Cheques Emitidos <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="order by Che.Che_Fec DESC ">Fecha</option><option value="order by Che.Che_Num DESC">N° de Cheque</option><select>&nbsp;</div>',
        colModel: [
            {label: 'Cód. Int.', name: 'Asi_Cod', width: 30, align: "center", key: true},
            {label: 'Comprobante', name: 'Com_Num', width: 30, align: "center"},            
            {label: 'Banco', name: 'Pld_Des', align: "center"},
            {label: 'N° Cuenta', name: 'Ban_Cue', align: "center",width: 45}, 
            {label: 'Proveedor', name: 'Prov', align: "left"},
            {label: 'Proveedor', name: 'Prv_Cod', hidden: true},
            {label: 'Che_Cod', name: 'Che_Cod', hidden: true},
            {label: 'N° Cheque', name: 'Che_Num', width: 35, align: "center"},
            {label: 'Fecha', name: 'Che_Fec', width: 40, align: "center"},
            {label: 'Valor', name: 'Che_Val', width: 40, formatter: 'currency', align: "right", summaryType:"sumNotAnulado"},
            {label: 'Cheque', name: 'Che_Est', width: 40, align: "center", editable: false, viewable: false, formatter: 'select', title: false, edittype: "select", editoptions: {
                  value: "A:No Cobrado;C:Cobrado;P:Protestado;I:Anulado"}},
            {label: 'Compr.', name: 'Com_Est', width: 25, align: "center",hidden:true, formatter: 'truefalse', formatoptions: {NotValue: 'I', yesMsg: 'Comprobante Activo', noMsg: 'Comprobante Inactivo'}}, 
            {label: 'Alert.', name: 'Alert', width: 25, align: "center", formatter: 'verificarAlert',viewable: false}, 
            {label: '&nbsp;', name: 'act0', width: 20, align: 'center', viewable: false, formatter: 'imprimir', title: false}
        ],rowNum: 10000000,footerrow: true,loadComplete: function(data) {
            if ($.varValid(data.rows))
                for (var i = 0, z = data.rows.length; i < z; i++) {
                    //console.log(data.rows[i]);
                    if (data.rows[i]['Che_Est'] === "I")
                    {
                        let selector="#" + data.rows[i].Asi_Cod + ' td:not(.jqgrid-rownum)';                        
                        $(selector).addClass('cellRed2');
                    }
                        
                }
            $(this).setGridSummary(['Che_Val'], {Che_Fec: '<div style="text-align:right;">TOTAL:</div>'});
        
    }}, true, '#searchGridPager', {refresh: true}).gridButtonsAdd([null,
        
        {buttonicon: 'print', caption: 'Imprimir', onClickButton: function () {
                $('#searchGrid').getGridBatch();
                printR('#searchGrid');
                $('#searchGrid').startGridEdit();
            }},
        {buttonicon: 'download-alt', caption: 'Excel', onClickButton: function () {
                
                exportR('#searchGrid');
            }}
    ]);
}
var cargarPeriodos = () => {
    $.getDataJson('', {'cargarPeriodos': true}, function (resp) {
        $.each(resp['periodos'], function (index, periodo) {
            var option = $('<option></option>').text('Periodo ' + periodo.Periodo).attr('value', periodo.Pec_Cod).data(periodo);
            $('#Pec_Cod').append(option);
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

$.fn.fmatter.verificarAlert=function (cv, opts, cObjt){
    let component='';
    if(cObjt.Com_Est === 'I' && cObjt.Che_Est !== 'I')
        component= '<i title="El comprobante esta Inactivo" class="glyphicon glyphicon-alert orange"></i>';
    return component;
}

var limit_fec=(data)=>{
    if(!$.isEmptyObject(data)){
        rango=`${data.Periodo}:${data.Periodo}`;
        //console.log(rango);
        $( "#txt_fec_ini,#txt_fec_fin" ).datepicker( "option", "yearRange",rango);
        $( "#txt_fec_ini").datepicker( "setDate", data.Pec_Fei );
        $( "#txt_fec_fin").datepicker( "setDate", data.Pec_Fef );
    }else{
        $( "#txt_fec_ini,#txt_fec_fin" ).datepicker( "option", "yearRange",'c-10:c+10');
    }      
};
function setOpt(val){
                        if(val==='d') $('.search_pec').attr('disabled','disabled'); 
                        else $('.search_pec').removeAttr('disabled'); 
                    }
$.fn.fmatter.imprimir = function (cv, opts, cObjt) {
    return $.getGridButton(imprimir=(imp)=>
    {
        let link=`?codigo2=${imp.Che_Cod}&asi=${imp.Asi_Cod}&ban=${imp.Ban_Cod}&pro=${imp.Prv_Cod}`;
        //console.log(link);
        var html=$('#conten_bancos_imp').html();
        //console.log(html);
        html = html.replace(/{banco}/g, imp.Pld_Des);
        html = html.replace(/{link}/g, link );
        $('#printCheque').html(html);
        $('#modelo').dialog("open");
    }, cObjt,'imprimir','print',undefined,'info');
};                    
function printR(grid) {
    $('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML', {removeHiddens:true,removeCols:[1,2,10,11,12],generated: false, caption: false, footer: true, bodyBorder: false}));
    $('#titleReporte').html($(grid).getCaption());
    $('#formatoReporte').printElement({pageTitle: "Cheques Emitidos", printMode: 'popup', overrideElementCSS: [{href: '../../mascaras/model1/estilos/print.css', media: 'print'}]});
}
function exportR(grid) {
    var temp = $('<div>' + $('#formatoExportar').html() + '</div>');
    temp.append($(grid).jqGrid('exportGridHTML', {removeHiddens:true,removeCols:[1,2,10,11,12],generated: false, caption: true, bodyBorder: false, footer: true, sepEnd: true}));
    $.downloadFile($.exportarExcelBlob(temp.html(), 'Cheques Emitidos'), 'cheques_' + $.getDate() + '.xls');
}


function agregarNumero(Che_Num){
    if(en_lista(Che_Num)){
        if(Che_Num*1<=0){
            alertaAuto('Ingrese un Número válido','#numero_nuevo','right');
        }else{
            let elemento=$('#list_numeros');
            let option = $('<span/>');
            //console.log(Che_Num);
            option.attr({ 'value': Che_Num }).text(Che_Num).data('Che_Num',Che_Num).addClass('btn btn-danger').attr('title','Quitar').on('click',function(){
                $(this).hide('slow', function(){ $(this).remove(); });
            }).css('display','inline-block');
            elemento.append(option);
        }
        
    }else{
        alertaAuto('ya ha sido agregado','#numero_nuevo','right');
    }
}

function en_lista(Che_Num){
    if(getNumerosLista().indexOf(Che_Num*1)>=0){
        return false;
    }else{
        return true;        
    }
}

function save_cheques(data){
    $.saveDataJson('',data,function(ret){
        $('input[name=Secuencia_Ini]').val('');
        $('input[name=Secuencia_Fin]').val('');
        $('#Caj_Fec').val('');
        $('#list_numeros').children().remove();
        $('#numero_nuevo').val('');
    },function(err){
        console.log(err);
    });
}


function validaCampos(ev){
    let arr_cheques=[];
    if($('#Pec_Cod').val()*1<=0||$('#Pld_Cod').val()*1<=0)
    {
        $.alert('Rellene todos los campos obligatorios (*)');
        return false;
    }
    if($('#Tipo_Eliminacion').val()*1===1){
        if($('input[name=Secuencia_Ini]').val()*1>=$('input[name=Secuencia_Fin]').val()*1){
            $.alert('verifique los rangos a eliminar');
            return false;
        }else{
            for(let i=$('input[name=Secuencia_Ini]').val()*1;i<=$('input[name=Secuencia_Fin]').val()*1;i++)
                arr_cheques.push(i);
        }
    }else{
        if($('#list_numeros').children().length<=0){
            $.alert('No se han agregado números de cheques a eliminar');
            return false;
        }else{
            $('#list_numeros').children().map((pos,elem)=>{
                arr_cheques.push($(elem).data('Che_Num')*1);
            });
        }
    }
    
    if(arr_cheques.length>20){
        $.alert('Solo se puede especificar 20 cheques por transacción');
        $('input[name=Secuencia_Fin]').focus();
        return false;
    }
    let banco_sel=$('#Pld_Cod').find(':selected').data();
    let data={save_cheques:true,Pec_Cod:$('#Pec_Cod').val(),cheques:arr_cheques,Pld_Cod:banco_sel['Pld_Cod'],Ban_Cod:banco_sel['Ban_Cod'],Com_Fec:$('#Caj_Fec').val()};
    $.createDialogConfirm(`¿Est&aacute; seguro que desea Anular ${arr_cheques.length} Cheque(s)?`, data, save_cheques);
    
    
}


function getNumerosLista(){
    var arr=[];
    $('#list_numeros').find('span').each(function(){
        if($(this).html()*1>0){
            arr.push($(this).html()*1);
        }
    });
    return arr;
}

function alertaAuto(mensaje,componente,direccion){
    $(componente).flyout('hide');
    $(componente).createFlyout(mensaje,{icon:'exclamation',placement:direccion,timeDismis:2000});
    $(componente).flyout('show');
}

$.fn.fmatter.truefalse=function(cv,opts,cObjt){ var f=$.ocf(opts),yv=f['NotValue'],v=$.vv(yv)?($.isFunc(yv)?yv(cv):yv!==cv):$.toBool(cv),e=v?'yes':'no',msgVar=e+'Msg',micon=e+'Icon',mcolor=e+'Color',msg=$.funcOrVal(f[msgVar],cObjt,v?'Si':'No'), icon=$.funcOrVal(f[micon],cObjt,v?'ok':'remove'), color=$.funcOrVal(f[mcolor],cObjt,v?'green':'red'); if(!$.isBool(f['msg'])) f['msg']=true; return (!$.vv(cv)||cv===''?'':$('<div><i/>'+(!f['noText']?'<u class="hidden">'+cv+'</u>':'')+'</div>').attr({'data-originaldata':cv,title:f['msg']?msg.trim():''}).find('i').attr('class',$.createIcon(icon,true)+' '+color).end().prop('outerHTML')); }; $.fn.fmatter.truefalse.unformat=$.unformatCellData;