/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var kardexGrid;
$(function() {
    $('#Tia_IoE').on('change',function (){
        $('#Tia_Cod').html('');
        $.get("",{TiaSelect:this.value}, function( response ) {
            $('#Tia_Cod').html(response);
            $('#proGrid').trigger('reloadGrid', [{ page: 1 }]);
            kardexGrid.clearGrid();
        }).fail(function(error) { $('.btn-frm').removeAttr('disabled');$.alert("El Servidor ha fallado en responder!");$("#prods").startGridEdit(); })
                .always(function() {});   
    });

    kardexGrid=$("#prods");
    $(document).ready(function () {
        $.createDialog('#successDialog',150,550);
        kardexGrid.createGrid({
            height: 270, caption:'Listado de Productos', footerrow:true, userDataOnFooter: false,
            onSelectRow: function(){$(this).resetSelection();}, pgbuttons: false,pgtext: null,
            colModel: [                               
                { label: 'Cód.Int.', name: 'Pro_Cod', key: true, hidden:false,viewable:true, width: 25,align:'center' }, 
                { label: 'Cód.Int.', name: 'Iva_Cod',hidden:true,viewable:false, width: 0,align:'center' }, 
                { label: 'Detalle',name: 'Ite_Lar', width: 150},                                        
                { label: 'Stock',name: 'Stk_Can', width: 40,classes:'columnHighlight3',align:'center'},
                { label: 'Cant.',name: 'Aju_Can', width: 40,classes:'columnHighlight2',editable:true,align:'center',editoptions: {dataInit:function(e){ e.style.textAlign = 'right';e.style.paddingRight = '5px';e.type='number'; e.className +=' nospin'; e.onkeyup=function(){updateSubt($(this).attr('rowId'));};}}},
                { label: 'Unid.',name: 'Uni_Des', width: 30},    
                { label: 'C. Unit.',name: 'Aju_Pru', width: 50,classes:'columnHighlight2 Tot',align:'right',editable:true,editoptions: {dataInit:function(e){ e.style.textAlign = 'right';e.style.paddingRight = '5px';e.type='number'; e.className +=' nospin'; e.onkeyup=function(){updateSubt($(this).attr('rowId'));}; }}},
                { label: 'C. Total',name: 'Aju_Imp', width: 50,classes:'columnHighlight2 Tot Total',align:'right',formatter:'currency', formatoptions: {prefix:'$', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum"},
                { label:'&nbsp;', name: 'act1', width: 15, align: 'center',viewable: false, formatter:'gridButton',formatoptions:{action:eliminarFila,title:'Quitar',data:function(o){ return o.Pro_Cod; }, type:'danger', icon:'trash'} }

            ]                                    
        },true,"#prodsPager",{view:false}).gridButtonAdd({buttonicon:'glyphicon glyphicon-plus', caption:"Agregar Producto", onClickButton:function(){ $('#proDialog').dialog('open'); },classes:'btn-frm' }); ; 
        var $footRow = $("#gbox_prods #gview_prods .ui-jqgrid-sdiv .footrow");
        $footRow.find('>td:not(:last-child,:first-child,.Tot)').css("border-right-color", "transparent");
        $footRow.find('>td:not(.Total)').addClass("whiteI");
        $("#Aju_Fec").createDatePickers();
    });    
    // DIALOG BUSCAR CUENTAS     
    if($('#proDialog').length===1)
    $.createSearchDialog('proDialog',[
        { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 15,align:"center",hidden:true },                                
        { label: 'Producto', name: 'Ite_Lar', width: 100, classes:'highlightSearch' },    
        { label: 'Descripción', name: 'Pro_Obs', width: 60, classes:'highlightSearch' },    
        { label: 'Marca', name: 'Mar_Des', width: 40},
        { label: 'Tipo', name: 'Cat_Des', width: 110,align:"center" },
        { label: 'Costo', name: 'Aju_Imp', width: 30,align:"center" },
        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:'addFilaMat'} }
    ],400,800);
});

function eliminarFila(Pro_Cod){ $('#prods').jqGrid('delRowData',Pro_Cod); }
function updateSubt(id){                                
    var Can=$('#'+id+'_Aju_Can'),Pru=$('#'+id+'_Aju_Pru'); 
    alertStock();
    if(!isNaN(Can.val())&&!isNaN(Pru.val())) 
         kardexGrid.jqGrid("setCell", id, "Aju_Imp", (Can.val()*Pru.val()).toFixed(2));
    else kardexGrid.jqGrid("setCell", id, "Aju_Imp", 0);
    kardexGrid.setGridSummary(['Aju_Imp'],{Aju_Pru: '<div style="text-align:right;">TOTAL:</div>'});                                
}
function alertStock(){
    var ids=kardexGrid.jqGrid('getDataIDs');
    kardexGrid.find('tr td[aria-describedby=prods_Stk_Can]').removeClass("cellRed1 cellGreen1 cellBold");
    if($('#Tia_IoE').val()==='E'){                                        
        $.each(ids,function (i,v){
                if(!isNaN($('#'+v+'_Aju_Can').val())&&$('#'+v+'_Aju_Can').val()*1>kardexGrid.jqGrid("getCell", v, "Stk_Can"))
                     kardexGrid.jqGrid('setCell',v,"Stk_Can","",'cellRed1 cellBold'); 
                else kardexGrid.jqGrid('setCell',v,"Stk_Can","",'cellGreen1 cellBold'); 
        });
    }   
}
//function updateNumber(val){
//    $.post("",{Aju_Tip:val,updateNumber:true}, function(response) { 
//        if(response['success']) $('#Aju_Sec').val(response['next']['Aju_Sec']);
//    },'json');
//}


function validarForm(){
    var data=$('#formKardex').serializeObject(),ban1='',ban2=''; 
    data['saveForm']=$("#prods").getGridBatch();
    if(data['saveForm'].length===0){$.alert('Seleccione al menos un producto');$("#prods").startGridEdit();return false;}
    for(var i=0;i<data['saveForm'].length;i++){
        if(('0'+data['saveForm'][i]['Aju_Pru'])*1===0 || ('0'+data['saveForm'][i]['Aju_Can'])*1===0)
            {ban1=data['saveForm'][i]['Ite_Lar'];break;}
        if(data['Tia_IoE']==='E' && ('0'+data['saveForm'][i]['Aju_Can'])*1>('0'+data['saveForm'][i]['Stk_Can'])*1)
            {ban2=data['saveForm'][i]['Ite_Lar'];break;}
    }
    if(ban1!==''){$.alert('La <u>Cantidad/Precio</u> de <u>'+ban1+'</u> no son correctos! ');$("#prods").startGridEdit();return false;}
    if(ban2!==''){$.alert('La <u>Cantidad</u> del <u>EGRESO</u> de <u>'+ban2+'</u> no puede ser menor que el <u>STOCK</u>! ');$("#prods").startGridEdit();return false;}
    $.createDialogConfirm('Esta seguro que desea guardar el  <b>Movimiento</b>.',data,guardar,function (){$("#prods").startGridEdit();});
}
function guardar(data){
    $('.btn-frm').attr('disabled','disabled');
    //$('#loader').show();
    $.saveDataJson("", data, function( response ) {
        //console.log(response['success']);        
            $('.btn-new').removeAttr('disabled');  
            $('#proGrid').trigger('reloadGrid', [{ page: 1 }]);
            $('#impAjust').attr('href',response['linkAjust']); 
           /* <?php if($configuraciones['Cof_Con']=='S'){ ?>*/
           if($('#impCompr').length===1)
            $('#impCompr').attr('href',response['linkCompr']); 
            $('#successDialog').dialog('open');
           /* <?php } ?>*/
           return false;
        //console.log(data);
    },function(response){
        $('.btn-frm').removeAttr('disabled');
	$.alert(response['message']);
	$("#prods").startGridEdit(); 
	return false;
    },function(){
        $('.btn-frm').removeAttr('disabled'); 
	$("#prods").startGridEdit(); 
    });
    
    //,'json').fail(function(error) { $('.btn-frm').removeAttr('disabled');$.alert("El Servidor ha fallado en responder!");$("#prods").startGridEdit(); })
            //.always(function() {$("#loader").fadeOut("slow");});   

}
function resetForm(){
    $('#formKardex')[0].reset();
    $('#proGrid').trigger('reloadGrid', [{ page: 1 }]);
    $("#prods").clearGrid(true);
    $('.btn-new').attr('disabled','disabled');  
    $('.btn-frm').removeAttr('disabled');
}

