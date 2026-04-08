/*
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */

var searchGrid;
var createFormPedido, createFormDetalle,containers;
$(function(){
    searchGrid=$('#searchGrid');
    containers=$('#containers');
    createFormPedido=$('#formDocumentoPedido');
    createFormDetalle=$('#formDocumentoDetalle');
    if(searchGrid.length>0){
        searchGrid.createGrid({
            postData:$('#searchForm').getData('searchAjax'),
            datatype:'local', height: 295,selectGridRows:false,
            stateCol:'Vap_Est',stateConfig:{I:'cellRed2'},
            leyenda:[{icon:'stop red',label:'Anulados/Inactivos'},{icon:'ok green',label:'Contenedores Asignados'}],
            colModel: [
                {label: 'C&oacute;d. Int.', name: 'Vap_Cod', width: 25,key:true},
                {label: 'C&oacute;d. Int.', name: 'Nav_Cod', width: 15, hidden:true},
                {label: 'C&oacute;d. Int.', name: 'Edi_Cod', width: 15, hidden:true},
                {label: 'C&oacute;d. Int.', name: 'Exd_Cod', width: 15, hidden:true},
                {label: 'Naviera', name: 'Nav_Nom', width: 75, align: "left"},
                {label: 'Tipo', name: 'Nav_Tip', width: 15, formatter:'estado', formatoptions:{types:{N:'NAVIERA',A:'AGENTE NAVIERO'}}, align:'center'},
                {label: 'Nave/Vapor', name: 'Vap_Nom', width: 75, align: "left"},
                {label: 'Cod.Viaje.', name: 'Vap_Via', width: 75, align: "left"},
                {label: 'Año', name: 'Vap_Ano', width: 50, align: "center" },
                {label: 'Semana', name: 'Vap_Sem', width: 50, align: "center" },
                {label: 'Salida', name: 'Edi_Nom', width: 75, align: "left"},
                {label: 'Destino', name: 'Exd_Nom', width: 75, align: "left"},
                {label: 'Pais', name: 'Pas_Nom', width: 50, align: "left"},
                {label: 'Cut Off', name: 'Vap_Cof', width: 50, align: "center"},
                {label: 'Conte.', name: 'Contenedores', width: 30, align:"center", formatter:'title', formatoptions:{title:function(o){ return (o.Contenedores||'0')+' Contenedor(es)'; }}, title:false, classes:'columnHighlight1'},
                {label: 'Estado', name: 'Vap_Est', width: 25, align:"center", formatter:'estado', title:false},
                {label: $.createIcon('pencil'), name: 'act1', width: 50, formatter:'gridButton', formatoptions:{action:'editarDato',title:'Editar Nave/Viaje',data:'Vap_Cod', icon:'pencil' } },
                {label: $.createIcon('plus'), name: 'act2', width: 50, formatter:'gridButton', formatoptions:{action:'agregarDetalle',title:'Agregar Container',data:'Vap_Cod', icon:'plus' } }
            ],
            subGrid:true, multiselect:false,
            subGridRowExpanded: function(subgrid_id, row_id) {
                $("#"+subgrid_id).html("<div class='condensed-header jqFirst'><table id='"+subgrid_id+"_t' class='scroll'></table></div>");
                $("#"+subgrid_id+"_t").createGrid({
                  url:"?"+$.param({pedidosDetAjax:true,where:{'naviera_container.Vap_Cod':row_id}}), datatype:"json", height:'auto',selectGridRows:false,
                  stateCol:'Nco_Est',stateConfig:{I:'cellRed2'},
                  colModel: [
                    {label: 'C&oacute;d. Int.', name: 'Nco_Cod', width: 10,key:true},
                    {label: 'C&oacute;d. Int.', name: 'Vap_Cod', width: 15, hidden:true},
                    {label: 'Descr.', name: 'Nco_Nom', width: 40},
                    {label: 'Día', name: 'Nco_Dia', width: 30, align:"center"},
                    {label: 'Sellos', name: 'Nco_Sel', width: 75, formatter:'tags',formatoptions:{type:'warning'}},
                    {label: 'Cantidad', name: 'Nco_Can', width: 30, align:"right", classes:'columnHighlight3'},
                    {label: 'Termog.', name: 'Nco_Ter', width: 30},
                    {label: 'Asig.', name: 'Asignado', width: 20, align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Asignado a Planificacion',noMsg:'No Asignado'}, title:false, classes:'columnHighlight8'},
                    {label: 'Estado', name: 'Nco_Est', width: 10, align:"center", formatter:'estado', title:false},
                    {label: 'Obs.', name: 'Nco_Obs', width: 10, align: "center", formatter:'truefalse', formatoptions:{ yesMsg:function(o){ return o.Nco_Obs; }, noMsg:' ', yesIcon:'info-sign', noIcon:' ', yesColor:'blue', noText:true }, title:false },
                    {label: $.createIcon('remove')+'/'+$.createIcon('ok'), name: 'act1', width: 20, formatter:'gridButton', formatoptions:{action:'activarDato',title:'Activar Container', data:function(o){ return {grid:subgrid_id,Nco_Cod:o.Nco_Cod}; }, icon:'ok',type:'info',conditional:function(o){ return o.Nco_Est!=='A'; },caseFalse:function(o){ return o.Asignado==='N'?$.getGridButton('anulaDato',{grid:subgrid_id,Nco_Cod:o.Nco_Cod},'Desactivar Container','remove',null,'danger'):''; }  } },
                    {label: $.createIcon('pencil'), name: 'act2', width: 20, formatter:'gridButton', formatoptions:{action:'editarDetalle',title:'Editar Container', data:function(o){ return {grid:subgrid_id,Nco_Cod:o.Nco_Cod}; }, conditional:function(o){ return o.Nco_Est==='A'; }, icon:'pencil' } },
                    $.originalRow()
                  ],rowNum:100000, pager: ""
                });
              }
        }, false, "#searchGridPager").gridButtonsAdd([
            null,{caption:'Agregar Nave/Viaje', buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ createFormPedido.setData({}); $('#divSearch').moveComp('#divPedido'); } }
        ]);
    }
    setSemanas('#Prt_Sem');
    setSemanas('#Prt_Sem_Ped');
    $.createDatePickers('.isFecha');
});
function setSemanas(id){
    var sem= $(id), html='<option value="">Seleccione Semana...</option>';
    if(!sem.length) return;
    for(var i=1;i<=52;i++) html+=('<option value="'+i+'">Semana '+i+'</option>');
    sem.html(html);
}
function selectProvee(provee){
    $('#provFormTemp').setData($.extend(provee,{op_opciones:'c'}),'name').find('.dialogSearch').addClass('x');
    $('#provDialog').dialog('close');
}
function setPlanifData(elee){
    var datos=elee.value===''?{}:$(elee).find('option:selected').data();
    $('#planificacion').setData(datos);
    if($.vv(datos['Pln_Boo']))
        $('#formDocumentoDetalle').setData({Nco_Bkg:datos['Pln_Boo']},false);
}
function editarDato(Cod){
    var dato=searchGrid.getRowData(Cod);
    createFormPedido.setData(dato);
    $('#provFormTemp').setData($.extend(dato,{op_opciones:'c'}),'name');
    $('#divSearch').moveComp('#divPedido');
}
function getFilaRow(id){
    var fila=searchGrid.getRowData(id);
    fila['Nav_Tipo']=fila['Nav_Tipo']==='N'?'NAVIERA':'AGENTE NAVIERO';
    return fila;
}
function editarDetalle(Cod){
    var dato=$('#'+Cod.grid+'_t').getCell(Cod.Nco_Cod,'OriginalData'),
        parent=getFilaRow(dato.Vap_Cod);
    $.getDataJson('',{getPlanif:true,dato:parent },
        function (resp){
            setPlanificacion(resp.planif);
            $('#viajeTmp').setData(parent,false);
            createFormDetalle.setData(dato);
            $('#Pde_Cod').val(dato['Pde_Cod']).trigger('change');
            $('#divSearch').moveComp('#divDetalle');
        }
    );
}
function agregarDetalle(Cod){
    var dato=getFilaRow(Cod);
    $.getDataJson('',{getPlanif:true,dato:dato },
        function (resp){
            setPlanificacion(resp.planif);
            $('#viajeTmp').setData(dato,false);
            createFormDetalle.setData({Vap_Cod:dato['Vap_Cod'],Nco_Can:1080});
            $('#Pde_Cod').val('').trigger('change');
            $('#divSearch').moveComp('#divDetalle');
        }
    );
}
function setPlanificacion(rows){
    var planif= $('#Pde_Cod');
    if(!planif.length) return;
    planif.html('<option value="" selected="">Seleccione Semana...</option>');
    $.each(rows, function(i,v){
        var Pde_Cod=v['Pde_Cod'];
        delete v['Pde_Cod'];
        var opt=$("<option value='"+Pde_Cod+"'>"+v['Ruc']+" - "+($.isEmpty(v['Pln_Auc'])?'AUCP Pendiente':v['Pln_Auc'])+"</option>");
        opt.data(v);
        opt.appendTo(planif);
    });
}
function validaPedido(){
    var data={savePedido:true,dato:createFormPedido.getData()};
    if($.isEmpty(data['dato']['Vap_Cod'])) data['dato']['Vap_Cod']=undefined;
    $.createDialogConfirm('¿Esta seguro de guardar la informacion del <u>VAPOR/VIAJE</u>?', data, saveDocument);
}
function saveDocument(data){ //console.log(data); console.log(data['rets']);
    $.saveDataJson('',data,
        function (resp){
            $('#divPedido').moveComp('#divSearch').updateGridsSizes();
            searchGrid.trigger("reloadGrid");
        }
    );
}
function validaDetalle(){
    var data={saveDetalle:true,dato:createFormDetalle.getData(),Pde_Cod:$('#Pde_Cod').val()};
    if($.isEmpty(data['dato']['Nco_Cod'])){
        data['dato']['Nco_Cod']=undefined;
    }
    $.createDialogConfirm('¿Esta seguro de guardar la informacion del <u>CONTAINER</u>?', data, function (){
        $.saveDataJson('',data,
            function (resp){
                var fila=getFilaRow(data['dato'].Vap_Cod);
                searchGrid.changeRow(fila.Vap_Cod,{Contenedores:fila.Contenedores*1+1});
                $('#searchGrid_'+data['dato'].Vap_Cod+'_t').trigger("reloadGrid");
                $('#divDetalle').moveComp('#divSearch').updateGridsSizes();
            }
        );
    });
}

function activarDato(Cod){
    //var row=$('#'+Cod.grid+'_t').jqGrid('getRowData',Cod.Pde_Cod);
    $.createDialogConfirm('¿Esta seguro de <u class="green"><b>ACTIVAR</b></u> la <b>CONTAINER</b>?', {changeEstado:true, Nco_Cod:Cod.Nco_Cod, Nco_Est:'A', grid:Cod.grid}, changeEstado);
}
function anulaDato(Cod){
    //var row=$('#'+Cod.grid+'_t').jqGrid('getRowData',Cod.Pde_Cod);
    $.createDialogConfirm('¿Esta seguro de <u class="red"><b>DESACTIVAR</b></u> el <b>CONTAINER</b>?', {changeEstado:true, Nco_Cod:Cod.Nco_Cod, Nco_Est:'I', grid:Cod.grid}, changeEstado);
}
function changeEstado(data){
    $.saveDataJson("", data ,function (r) {
        $('#'+data.grid+'_t').changeRow(data['Nco_Cod'],{Nco_Est:data['Nco_Est']});
    });
}