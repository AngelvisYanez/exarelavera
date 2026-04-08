/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */

var searchGrid;
var createDialog,createForm;
$(function(){
    searchGrid=$('#searchGrid');
    createDialog=$('#createDialog');
    createForm=$('#createForm');
    if(searchGrid.length>0){
        $("#searchGrid").createGrid({
            datatype:'local', height: 295,
            stateCol:'Nav_Est',stateConfig:{I:'cellRed2'},
            leyenda:[{icon:'stop red',label:'Anulados/Inactivos'}],
            colModel: [
                {label: 'C&oacute;d. Int.', name: 'Nav_Cod', width: 50, align: "left", key:true},               
                {label: 'Naviera', name: 'Nav_Nom', width: 150, align: "left"}, 
                {label: 'Tipo', name: 'Nav_Tip', width: 50, formatter:'estado', formatoptions:{full:true,types:{N:'NAVIERA',A:'AGENTE NAVIERO'}}, align:'center'},
                {label: 'Estado', name: 'Nav_Est', width: 50, formatter:'estado', formatoptions:{full:true}, align:'center'},
                {label: $.createIcon('remove')+'/'+$.createIcon('ok'), name: 'act1', width: 30, formatter:'gridButton', formatoptions:{action:'activarDato',title:'Activar Naviera',data:'Nav_Cod',icon:'ok',type:'info',conditional:function(o){ return o.Nav_Est!=='A'; },caseFalse:function(o){ return $.getGridButton('anulaDato',o.Nav_Cod,'Desactivar Naviera','remove',null,'danger'); }  } },
                {label: $.createIcon('pencil'), name: 'act2', width: 30, formatter:'gridButton', formatoptions:{action:'editarDato',title:'Editar Naviera',data:'Nav_Cod', conditional:function(o){return o.Nav_Est==='A';}, icon:'pencil' } }           
            ]
        }, false, "#searchGridPager").gridButtonsAdd([
            null,{caption:'Agregar Naviera', buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ createForm.setData({}); createDialog.dialog('option',{'icon':'plus','title':'Registrar Naviera'}).dialog('open'); } }            
        ]);
    }
    if(createDialog.length>0)
        createDialog.createDialog({height:180, width:340, icon:'plus'});
});
           
function editarDato(Cod){ 
    createForm.setData(searchGrid.getRowData(Cod));
    createDialog.dialog('option',{'icon':'pencil','title':'Editar Naviera'}).dialog('open');
}
function validaDocument(){
    var data={saveDocumento:true,dato:createForm.getData()};
    if($.isEmpty(data['dato']['Nav_Cod'])) data['dato']['Nav_Cod']=undefined;
    $.createDialogConfirm('¿Esta seguro de guardar la informacion de la <u>NAVIERA</u>?', data, saveDocument);
}
function saveDocument(data){ //console.log(data); console.log(data['rets']);
    $.saveDataJson('',data,
        function (resp){
            createDialog.dialog('close');
            searchGrid.trigger("reloadGrid");
        }
    ); 
}


function activarDato(Cod){
    var row=searchGrid.jqGrid('getRowData',Cod);
    $.createDialogConfirm('¿Esta seguro de <u class="green"><b>ACTIVAR</b></u> <b>'+row['Nav_Nom']+'</b>?', {changeEstado:true, Nav_Cod:Cod, Nav_Est:'A'}, changeEstado);
}
function anulaDato(Cod){
    var row=searchGrid.jqGrid('getRowData',Cod);
    $.createDialogConfirm('¿Esta seguro de <u class="red"><b>DESACTIVAR</b></u> <b>'+row['Nav_Nom']+'</b>?', {changeEstado:true, Nav_Cod:Cod, Nav_Est:'I'}, changeEstado);
}
function changeEstado(data){
    $.saveDataJson("", data ,function (r) {
        searchGrid.changeRow(data['Nav_Cod'],{Nav_Est:data['Nav_Est']});
    });
}