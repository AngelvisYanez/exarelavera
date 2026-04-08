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
            leyenda:[{icon:'stop red',label:'Anulados/Inactivos'},{icon:'stop green',label:'Contenedores Asignados'}],
            colModel: [
                {label: 'C&oacute;d. Int.', name: 'Pln_Cod', width: 25,key:true}, 
                {label: 'C&oacute;d. Int.', name: 'Cli_Cod', width: 15, hidden:true}, 
                {label: 'C&oacute;d. Int.', name: 'Bam_Cod', width: 15, hidden:true}, 
                {label: 'C&oacute;d. Int.', name: 'Exd_Cod', width: 15, hidden:true}, 
                {label: 'Fecha', name: 'Pln_Fec', width: 150, align: "left"}, 
                {label: 'Marca', name: 'Marca', width: 80, align: "left", formatter:'union', formatoptions:{sep:' - ', cols:['Bam_Nom','Bam_Tam']} }, 
                {label: 'Ruc', name: 'Ruc', width: 100, align: "left"},
                {label: 'Cliente', name: 'Cliente', width: 150, align: "left"}, 
                {label: 'Destino', name: 'Exd_Nom', width: 150, align: "left"}, 
                {label: 'Pais', name: 'Pas_Nom', width: 150, align: "left"},
                {label: 'Año', name: 'Pln_Ano', width: 50, align: "center" },
                {label: 'Semana', name: 'Pln_Sem', width: 50, align: "center" },   
                {label: 'Cantidad', name: 'Pln_Can', width: 70, align: "right", classes:'columnHighlight1' },    
                {label: $.createIcon('pencil'), name: 'act1', width: 50, formatter:'gridButton', formatoptions:{action:'editarDato',title:'Editar Pedido',data:'Pln_Cod', icon:'pencil' } },          
                {label: $.createIcon('plus'), name: 'act2', width: 50, formatter:'gridButton', formatoptions:{action:'agregarDetalle',title:'Agregar Planificacion',data:'Pln_Cod', icon:'plus' } } 
            ],
            subGrid:true, multiselect:false,
            subGridRowExpanded: function(subgrid_id, row_id) {                         
                $("#"+subgrid_id).html("<div class='condensed-header jqFirst'><table id='"+subgrid_id+"_t' class='scroll'></table></div>");
                $("#"+subgrid_id+"_t").createGrid({
                  url:"?"+$.param({pedidosDetAjax:true,where:{'exporta_planif_det.Pln_Cod':row_id}}), datatype:"json", height:'auto',selectGridRows:false,
                  stateCol:'Pln_Est',stateConfig:{I:'cellRed2',Contenedores:'cellGreen2'},
                  stateCondition: function(row){ if(row['Contenedores']>0) return "Contenedores";  },
                  colModel: [    
                    {label: 'C&oacute;d. Int.', name: 'Pde_Cod', width: 10,key:true}, 
                    {label: 'C&oacute;d. Int.', name: 'Pln_Cod', width: 15, hidden:true},
                    {label: 'AUCP', name: 'Pln_Auc', width: 30}, 
                    {label: 'DAE', name: 'Pln_Dae', width: 30}, 
                    {label: 'Booking', name: 'Pln_Boo', width: 30}, 
                    {label: 'Tipo', name: 'Pln_Tip', width: 30}, 
                    {label: 'Conte.', name: 'Contenedores', width: 10, align:"center", formatter:'title', formatoptions:{title:function(o){ return (o.Contenedores||'0')+' Contenedores Asignados'; }}, title:false, classes:'columnHighlight1'}, 
                    {label: 'Total', name: 'Total', width: 10, align:"right", formatter:'title', formatoptions:{title:function(o){ return (o.Total||'0')+' Cajas'; }}, title:false, classes:'columnHighlight1'}, 
                    {label: 'Estado', name: 'Pln_Est', width: 10, align:"center", formatter:'estado', title:false}, 
                    {label: 'Obs.', name: 'Pln_Obs', width: 10, align: "center", formatter:'truefalse', formatoptions:{ yesMsg:function(o){ return o.Pln_Obs; }, noMsg:' ', yesIcon:'info-sign', noIcon:' ', yesColor:'blue', noText:true }, title:false },
                    {label: $.createIcon('remove')+'/'+$.createIcon('ok'), name: 'act1', width: 10, formatter:'gridButton', formatoptions:{action:'activarDato',title:'Activar Detalle Planif.', data:function(o){ return {grid:subgrid_id,Pde_Cod:o.Pde_Cod}; }, icon:'ok',type:'info',conditional:function(o){ return o.Pln_Est!=='A'&&o.Contenedores*1===0; },caseFalse:function(o){ return o.Contenedores*1===0?$.getGridButton('anulaDato',{grid:subgrid_id,Pde_Cod:o.Pde_Cod},'Desactivar Detalle Planif.','remove',null,'danger'):''; }  } },
                    {label: $.createIcon('pencil'), name: 'act2', width: 10, formatter:'gridButton', formatoptions:{action:'editarDetalle',title:'Editar Detalle Planif.', data:function(o){ return {grid:subgrid_id,Pde_Cod:o.Pde_Cod}; }, conditional:function(o){ return o.Pln_Est==='A'; }, icon:'pencil' } },
                    $.originalRow()
                  ],rowNum:100000, pager: ""
                });
              }
        }, false, "#searchGridPager").gridButtonsAdd([
            null,{caption:'Agregar Pedido Exterior', buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ createFormPedido.setData({}); $('#divSearch').moveComp('#divPedido'); } }            
        ]);
    }
    if(containers.length>0){
        containers.createGrid({           
            datatype:'local', height: 260, selectGridRows:false, rownumbers:false,
            footerrow:true, totalCols:['Nco_Can'], totalDefault:{Nco_Nom:'<div class="txtRight">TOTAL:</div>'},
            colModel: [
                {label: 'C&oacute;d. Int.', name: 'Nco_Cod', width: 25,key:true, hidden:true },   
                {label: 'Nave/Vapor', name: 'Vap_Nom', width: 50, align: "left", classes:'bgNoRight'},
                {label: 'Cod.Viaje.', name: 'Vap_Via', width: 50, align: "left", classes:'bgNoRight'},
                {label: 'Descr.', name: 'Nco_Nom', width: 50},                 
                {label: 'Cantidad', name: 'Nco_Can', width: 30, align:"right", classes:'columnHighlight3'}, 
                {label: 'Sellos', name: 'Nco_Sel', width: 15, align: "center", formatter:'truefalse', formatoptions:{ yesMsg:function(o){ return '<span class="green"><b class="red">[</b>'+o.Nco_Sel.replace(/,/g, '<b class="red">], [</b>')+'<b class="red">]</b></span>'; }, noMsg:' ', yesIcon:'info-sign', noIcon:' ', yesColor:'blue', noText:true }, title:false }
            ]
        },true);
    }
    if($('#provDialog').length>0)
    $('#provDialog').createSearchDialog({colModel:[
        { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15,align:"center",hidden:true }, 
        { label: 'Cédula/RUC', name: 'Ruc', width: 50 },                      
        { label: 'Cliente', name: 'Cliente', width: 100},
        { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:'selectProvee'} }
    ]},{ title:'Cliente' }); 
    setSemanas('#Prt_Sem');
    setSemanas('#Prt_Sem_Ped');
    $.createDatePickers('.isFecha');
    $('#Pln_Fec').on('change',function(){
        if(this.value.trim()==="") return;
        $('#Pln_Ano').val(this.value.substring(0,4));
        $('#Prt_Sem_Ped').val(moment(this.value).isoWeek());
    });
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
function editarDato(Cod){ 
    var dato=searchGrid.getRowData(Cod);
    createFormPedido.setData(dato);
    $('#provFormTemp').setData($.extend(dato,{op_opciones:'c'}),'name');
    $('#divSearch').moveComp('#divPedido');
}
function editarDetalle(Cod){
    var dato=$('#'+Cod.grid+'_t').getCell(Cod.Pde_Cod,'OriginalData');
    $('#planifTmp').setData(searchGrid.getRowData(dato.Pln_Cod),false);
    createFormDetalle.setData(dato);   
    containers.Search({loadContainers:true,where:{Pde_Cod:$('#Pde_Cod').val()},setWhere:['setEmpCod','setVapor']});
    $('#divSearch').moveComp('#divDetalle').updateGridsSizes();
}
function agregarDetalle(Cod){
    var dato=searchGrid.getRowData(Cod);
    $('#planifTmp').setData(dato,false);
    createFormDetalle.setData({Pln_Cod:dato['Pln_Cod']});
    containers.clearGrid();
    $('#divSearch').moveComp('#divDetalle').updateGridsSizes();
}
function validaPedido(){
    var data={savePedido:true,dato:createFormPedido.getData()};
    if($.isEmpty(data['dato']['Cli_Cod'])) return $.alert("Debe Selecionar el Cliente!",null,'alert');
    if($.isEmpty(data['dato']['Pln_Cod'])) data['dato']['Pln_Cod']=undefined;
    $.createDialogConfirm('¿Esta seguro de guardar la informacion del <u>PEDIDO</u>?', data, saveDocument);
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
    var data={saveDetalle:true,dato:createFormDetalle.getData()};
    if($.isEmpty(data['dato']['Pde_Cod'])) data['dato']['Pde_Cod']=undefined;
    $.createDialogConfirm('¿Esta seguro de guardar la informacion del <u>PEDIDO</u>?', data, function (){
        $.saveDataJson('',data,
            function (resp){
                $('#divDetalle').moveComp('#divSearch').updateGridsSizes();
                $('#searchGrid_'+data['dato'].Pln_Cod+'_t').trigger("reloadGrid");
            }
        );
    });
}

function activarDato(Cod){
    //var row=$('#'+Cod.grid+'_t').jqGrid('getRowData',Cod.Pde_Cod);
    $.createDialogConfirm('¿Esta seguro de <u class="green"><b>ACTIVAR</b></u> la <b>PLANIFICACION</b>?', {changeEstado:true, Pde_Cod:Cod.Pde_Cod, Pln_Est:'A', grid:Cod.grid}, changeEstado);
}
function anulaDato(Cod){
    //var row=$('#'+Cod.grid+'_t').jqGrid('getRowData',Cod.Pde_Cod);
    $.createDialogConfirm('¿Esta seguro de <u class="red"><b>DESACTIVAR</b></u> la <b>PLANIFICACION</b>?', {changeEstado:true, Pde_Cod:Cod.Pde_Cod, Pln_Est:'I', grid:Cod.grid}, changeEstado);
}
function changeEstado(data){
    $.saveDataJson("", data ,function (r) {        
        $('#'+data.grid+'_t').changeRow(data['Pde_Cod'],{Pln_Est:data['Pln_Est']});
    });
}