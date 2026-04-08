/*
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var liquidaciones;
var cajasban=$('#cajasBan');
var camiones=$('#camiones');
var prods=$('#prods');
$(function() {
    cajasban=$('#cajasBan');
    camiones=$('#camiones');
    prods=$('#prods');
    liquidaciones=$('#liquidaciones');

    if(liquidaciones.length>0)
    liquidaciones.createGrid({
        caption:'Tarjas',
        orderOptions:[{label:'Semana',value:'Prt_Sem DESC',def:true},{label:'Productor',value:'Prs_Ape'},{label:'Fecha',value:'Prt_Fec DESC'},{label:'Numero',value:'Prt_Num DESC'}],orderMirror:'input[name=order]',orderId:'OrderBy',
        height: 250, datatype: "local", selectGridRows:true, bindKeys:false,
        stateCol:'Prt_Est', stateConfig:{I:'cellRed2',Lib_Cod:'cellGreen2'},
        stateCondition: function(row){ if(!$.isEmpty(row['Lib_Cod'])) return "Lib_Cod";  },
        footerrow:true,totalCols:['Prt_Cad','Prt_Car','Prt_Cah','Prt_Caf','Prt_Caj'],totalDefault:{Magap:'<div class="txtRight">TOTALES:<div>'},
        colModel: [
            { label: 'C�d. Int.', name: 'Prt_Cod', width: 15 ,align:"center", key:true, hidden:true},
            { label: 'Periodo', name: 'Prt_Ano', width: 35, align:"center", classes:'bgNoRight bgNoColor'},
            { label: 'Semana', name: 'Prt_Sem', width: 35, align:"center", classes:'bgNoRight bgNoColor'},
            { label: 'Num.', name: 'Prt_Num', width: 35,align:"center", classes:'bgNoRight bgNoColor'},
            { label: 'Fecha', name: 'Prt_Fec', width: 50, align:"center", classes:'bgNoRight bgNoColor'},
            { label: 'Marca', name: 'Bam_Nom', width: 100, classes:'bgNoRight bgNoColor'},
            { label: 'Ruc', name: 'Prs_Ced', width: 90, classes:'bgNoRight bgNoColor'},
            { label: 'Productor', name: 'Productor', width: 140, classes:"bgNoRight bgNoColor"},
            { label: 'Magap/Hacienda', name: 'Magap', width: 100, formatter:'union', formatoptions:{cols:['Prh_Mag','Prh_Nom'],sep:' - '}, classes:'bgNoColor'},
            { label: 'Declaradas', name: 'Prt_Cad', width: 35, align:"right", classes:'columnHighlight11' },
            { label: 'Recibidas', name: 'Prt_Car', width: 35, align:"right", classes:'columnHighlight10'  },
            { label: 'Rechazadas', name: 'Prt_Cah', width: 35, align:"right", classes:'columnHighlight1'  },
            { label: 'Faltantes', name: 'Prt_Caf', width: 35, align:"right", classes:'columnHighlight1'  },
            { label: 'Caidas', name: 'Prt_Caj', width: 35, align:"right", classes:'columnHighlight1'  },
            { label: $.createIcon('calendar'), name: 'Prt_Sys', width: 25, align: "center", formatter:'truefalse', formatoptions:{ yesMsg:function(o){ return o.Prt_Sys; }, noMsg:' ', yesIcon:'info-sign', noIcon:' ', yesColor:'blue', noText:true }, title:false }
            //{ label: 'Num. Compra', name: 'Cop_Num', width: 100 }
        ].concat($.isset('buttonExtra')?buttonExtra:[])
    },false,'#liquidacionesPager');
    if(cajasban.length>0)
    cajasban.createGrid({
        caption:'Cajas',height: 125, datatype: "local",footerrow:true, selectGridRows:false, bindKeys:false,
        colModel: [
            { label: 'C�d. Int.', name: 'Index', width: 15 ,align:"center", key:true, hidden:true},
            { label: 'Abr.', name: 'Abr', width: 15,align:"center", hidden:true},
            { label: 'Descripcion', name: 'Nom', width: 75, classes:'bgNoColor'},
            { label: 'Cantidad', name: 'Can', width: 35, align:'right', formatter:'textboxExa', formatoptions:{dataInit:function(el,op,obj){ el.style.textAlign='right'; $(el).attr('data-cajas',"Prt_"+obj.Abr); }, dataEvents:{ keypress:'return validar_numeric(event);', change:"updateTotalCajas($(this));"} } }
        ]
    },true);
    if(prods.length>0)
    prods.createGrid({
        caption:'Cartones',height: 70, datatype: "local",footerrow:true, selectGridRows:false, bindKeys:false,
        colModel: [
            { label: 'C�d. Int.', name: 'Pro_Cod', width: 15 ,align:"center", key:true, hidden:true},
            { label: 'Descripcion', name: 'Producto', width: 75, classes:'bgNoColor'},
            { label: 'Cantidad', name: 'Ptd_Can', width: 35, align:'right', formatter:'textboxExa', formatoptions:{dataInit:function(el){ el.style.textAlign='right';}, dataEvents:{ keypress:'return validar_numeric(event);', change:"updateTotalCajasCartones();"} } }
        ]
    },true);
    if(camiones.length>0)
    camiones.createGrid({
        caption:'Camiones',height: 122, datatype: "local", recordtext: "{0} - {1} of {2}", emptyrecords: "Sin Registros", footerrow:true, selectGridRows:false,bindKeys:false,
        colModel: [
            { label: 'C�d. Int.', name: 'Index', width: 15 ,align:"center", key:true, hidden:true},
            { label: 'Placa', name: 'Nom', width: 75, formatter:'textboxExa', classes:'bgNoColor'},
            { label: 'Cantidad', name: 'Can', width: 30, align:'right', formatter:'textboxExa', formatoptions:{dataInit:function(el){ el.style.textAlign='right';}, dataEvents:{ keypress:'return validar_numeric(event);', change:"updateTotalCajasCamiones();"} } },
            { name:'delete', label:'<i class="glyphicon glyphicon-remove"></i>', width: 20, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'deleteCamion', icon:'remove', title:'Eliminar Camion', type:'danger', data:function(o){ return o.Index; } }, resizable: false, classes:'bgNoColor' }
        ]
    },true,'#camionesPager').gridButtonsAdd([
        {caption:'Agregar Camion', buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ camiones.setRow({Index:camiones.nextIndex('Index')}); } }
    ]);
    if($('#provDialog').length>0){
        $('#provDialog').createSearchDialog({colModel:[
            { label: 'C&oacute;d.Int.', name: 'Prd_Cod', key: true, width: 15,align:"center",hidden:true },
            { label: 'C&oacute;d.Int.', name: 'Prv_Cod', width: 15,align:"center",hidden:true },
            { label: 'C�dula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Productor', name: 'Productor', width: 100},
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:'selectProvee'} }
        ]},{ title:'Productor' });

    }
    setSemanas();
});
function selectProvee(provee){
    $('#provFormTemp').setData($.extend(provee,{op_opciones:'c'}),'name').find('.dialogSearch').addClass('x');
    $('#provDialog').dialog('close');
}
function setSemanas(){
    var sem= $('#Prt_Sem'), html='<option value="">Seleccione Semana...</option>';
    if(!sem.length) return;
    for(var i=1;i<=52;i++) html+=('<option value="'+i+'">'+i.ordinal(true)+' Semana</option>');
    sem.html(html);
}
function setSemanas2(){
    var sem= $('#Prt_Sem2'), html='<option value="">Seleccione Semana...</option>';
    if(!sem.length) return;
    for(var i=1;i<=52;i++) html+=('<option value="'+i+'">'+i.ordinal(true)+' Semana</option>');
    sem.html(html);
}
function setHaciendas(haciendas){
    var selHac=$('#Prh_Cod').html('<option value="">Seleccione Hacienda...</option>');
    $.each(haciendas,function (i,v){
        if(v.Prh_Est==='A')
        selHac.append('<option value="'+v['Prh_Cod']+'" data--prh_-cod="'+v['Prh_Cod']+'" data--prh_-mag="'+v['Prh_Mag']+'">'+v['Prh_Nom']+' - '+v['Prh_Mag']+'</option>');
    });
}
// show edicion
function editarTarja(Prt_Cod){
    $.getDataJson("", {getDetalle:true,Prt_Cod:Prt_Cod} ,function (re) {
        console.log("wilson");
        console.log(re['dato']);

        clearForm(false);
        setContainers(re.containers);
        setHaciendas(re.haciendas);
        $('#formDocumento').setData(re['dato']);
        $('#formDocumento').setData(re['dato'],'cajas');
        updateMarca();
        camiones.setRows(re['dato']['Prt_Cam']);
        prods.setRows(re.productos);
        $.each(re.tarja_det,function(i,v){ prods.jqGrid('setCell', v['Pro_Cod'], 'Ptd_Can', v['Ptd_Can']); });
        updateTotalCajas();
        updateTotalProductos();
        $('#Prt_Num').data('Prt_Num',re['dato']['Prt_Num']);
        $('#searchTarjas').moveComp('#editTarja').updateGridsSizes();
    });
}

//eliminar
function validaEliminacion(Prt_Cod){
    var row=liquidaciones.jqGrid('getRowData',Prt_Cod);
    $.createDialogConfirm('�Esta seguro de <u class="red"><b>ANULAR</b></u> la <b>Tarja de Fruta No. '+row['Prt_Num']+'</b> al Productor <b><i>'+row['Productor']+'</i></b>?', {deleteLib:true, Prt_Cod:Prt_Cod}, eliminaLiquidacion);
}
function eliminaLiquidacion(data){
    //console.log(data);
    $.saveDataJson("", data ,function (responce) {
        liquidaciones.trigger("reloadGrid");
    });
}
 // registrar
 function updateTotalCajas(input){
    if(!$.isUnd(input)&&input.data('rowId')===1){
        updateTotalProductos();
    }
    var data=cajasban.getGridBatch();
    var acum=0;
    for(var i=1;i<data.length;i++){
        acum+=("0"+data[i]['Can'])*1;
    }
    cajasban.setGridSummary(['Index'],{Nom:'<div style="text-align:right;">TOTAL:</div>',Can:acum},false);
}
function deleteCamion(index){ camiones.delRowData(index); updateTotalCajasCamiones(); }
function updateTotalCajasCamiones(){ camiones.setGridSummary(['Can'],{Nom:'<div style="text-align:right;">TOTAL:</div>'},false); }
function updateTotalCajasCartones(){ prods.setGridSummary(['Ptd_Can'],{Producto:'<div style="text-align:right;">TOTAL:</div>'},false); }

function updateMagap(){
    var Prh_Cod=$('#Prh_Cod');
    var data=Prh_Cod.find('option:selected').data();
    $('#Magap').html(Prh_Cod.val()!==''?data['Prh_Mag']:'');
}
function updateMarca(){
    var Prh_Cod=$('#Bam_Cod');
    var data=Prh_Cod.find('option:selected').data();
    var des=Prh_Cod.val()!==''?data['Bam_Des']:'';
    $('#DescrMarca').html(des).attr('title',des);
    /*if(Prh_Cod.val()!==''){
        getProductosMarca(Prh_Cod.val());
    }*/
}
function getProductosMarca(Bam_Cod){
    if(Bam_Cod!=='')
    $.getDataJson('',{getProductos:true, where:{Mes_Tip:'C',"mesclas.Bam_Cod":Bam_Cod}},function(r){
        prods.setRows(r.productos);
        updateTotalProductos();
    });
}
function updateTotalProductos(){ if(prods.jqGrid('getCol','Ptd_Can',false,'count')===1) prods.find('tr td[aria-describedby=prods_Ptd_Can] input').val(cajasban.find('tr#1 td[aria-describedby=cajasBan_Can] input').val()); updateTotalCajasCartones(); }

function validaDocument(){
    var data=$('#formDocumento').getData('saveDocumento');
    if($.isEmpty(data.Prd_Cod)) return $.alert("Debe escoger un productor!",null,'alert');
    if($.isEmpty(data.Exc_Cod)) return $.alert("Debe escoger un Container/Nave!",null,'alert');

    var cam=camiones.getGridBatch();
    $.arraySpliceFields(cam,['Index','delete']);
    data['Prt_Cam']=$.jsonParser(cam);
    data['cartones']=prods.getGridBatch();
    $.arraySpliceFields(data['cartones'],['Producto']);
    var acum=0;
    var cajas=cajasban.getGridBatch();
    for(var i=0;i<cajas.length;i++){
        if(i>0) acum+=("0"+cajas[i]['Can'])*1;
        data['Prt_'+cajas[i]['Abr']]=("0"+cajas[i]['Can'])*1;
    }
    if(("0"+cajas[0]['Can'])*1===0) return $.alert("Las <u>CAJAS DECLARADAS</u> no pueden estar en cero!",null,'alert');
    if(("0"+cajas[0]['Can'])*1!==acum)  return $.alert("Las <u>CAJAS DECLARADAS</u> no coinciden con el <u>TOTAL</u> en la tabla <u class='green'>CAJAS</u>!",null,'alert');
    if(("0"+cajas[1]['Can'])*1!==prods.jqGrid('getCol','Ptd_Can',false,'sum')) return $.alert("Las <u>CAJAS RECIBIDAS</u> no coinciden con el <u>TOTAL</u> en la tabla <u class='blue'>CARTONES</u>!",null,'alert');

    data['Prt_Mag']=$.isEmpty($('#Magap').html())?'N':'S';
    console.log(data);
    $.createDialogConfirm('�Esta seguro de guardar la tarja(Comprobante de Recepci�n)?', data, saveDocument);
}
function saveDocument(data){ //console.log(data); console.log(data['rets']);
    $.saveDataJson('',data,
        function (resp){
            if(liquidaciones.length>0){
                liquidaciones.trigger("reloadGrid");
                $('#editTarja').moveComp('#searchTarjas').updateGridsSizes();
            }else{
                clearForm();
            }
        }
    );
}
function clearForm(next){
    $('#formDocumento').setData({Prt_Por:100, Prt_Fec:$.hoy()});
    $('#formDocumento').setData({},'name');
    cajasban.setRowsByIndex(cajas,'Index');
    cajasban.find('tr#0 td:not(.jqgrid-rownum)').addClass('cellGreen1');
    camiones.clearGrid();
    updateTotalCajas();
    updateTotalCajasCamiones();
    updateTotalCajasCartones();
    $('#Magap').html("");
    $('#DescrMarca').html("");
    $("#Prh_Cod").html('<option value="">Seleccione Hacienda..</option>');
    if(next!==false)validatNum();
}
function validatNum(){
   var Prt_Cod=$('#Prt_Cod') ;
   $('#Prt_Num').getValidationJson('',{validaNum:true, Prt_Num:$('#Prt_Num').val(), Prt_Cod:Prt_Cod.length>0?Prt_Cod.val():undefined},function(r){
        var rnum=$('#Prt_Num');
        if(!r['valid']){
            rnum.fieldValid(false,r['message']);
            if($.vv(rnum.data('Prt_Num')))
                r['Prt_Num']=rnum.data('Prt_Num');
        }
        rnum.val(r['Prt_Num']);
        return false;
    });
}
function setContainers(containers){

console.log(containers);

    var Exc_Cod=$('#Exc_Cod');
    Exc_Cod.html('<option valuie="">Selecione Container..</option>');
    $.each(containers, function(i,v){
        var aux=$('<option value="'+v.Exc_Cod+'">'+v.Exc_Con+'</option>');
        aux.data(v);
        Exc_Cod.append(aux);
    });
}
function loadContainers(){
    $('#Exc_Cod').html('<option valuie="">Selecione Container..</option>');
    var Prt_Ano=$('#Prt_Ano').val(), Prt_Sem=$('#Prt_Sem').val();
    if($.isEmpty(Prt_Ano)||$.isEmpty(Prt_Sem)) return;
    $.getDataJson('',{getContainers:true, where:{Exc_Sem:Prt_Sem,Exc_Ano:Prt_Ano}, setWhere:'setEmpCod'},function(r){
       setContainers(r.containers);

    });

}