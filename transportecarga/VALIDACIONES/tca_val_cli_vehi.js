/*
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var cliDialog, searchGrid;
$(function(){
    searchGrid=$('#searchGrid');
    if(searchGrid.length>0){
        searchGrid.createGrid({
            caption:'Vehiculos', height: 250,
            stateCol:'Veh_Est',stateConfig:{I:'cellRed2'},
            leyenda:[{icon:'stop red',label:'Anulados/Inactivos'}],
            colModel: [
                { label: 'Cd. Int.', name: 'Veh_Cod', width: 20 ,align:"center", key:true, hidden:true},
                { label: 'Placa', name: 'Veh_Pla', width: 45,align:"center"},
                { label: 'Marca', name: 'Veh_Mar', width: 45},
                { label: 'Color', name: 'Veh_Col', width: 45},
                { label: 'C.I./Ruc', name: 'Ruc', width: 60},
                { label: 'Cliente', name: 'Cliente', width: 110 },
                $.originalRow(),
                {name:'update',label:$.createIcon('pencil'), width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'editItem', icon:'pencil', title:'Editar Item', data:function(o){ return {Veh_Cod:o.Veh_Cod}; }, conditional:function(o){ return o.Veh_Est==='A'; } }, resizable: false },
                {name:'delete',label:$.createIcon('remove'), width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'deleteItem', icon:'remove', title:'Eliminar Item', type:'danger', data:function(o){ return {Veh_Cod:o.Veh_Cod}; }, conditional:function(o){ return o.Veh_Est==='A'; } }, resizable: false }
            ]
        },true,'#searchGridPager').gridButtonsAdd([
            {caption:'Agregar Vehiculo', buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ newItem(); } }
        ]);

    }
    cliDialog=$('#cliDialog');
    if(cliDialog.length>0)
    cliDialog.createSearchDialog({colModel:[
            { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15,align:"center",hidden:true },
            { label: 'Cdula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Cliente', name: 'Cliente', width: 100},
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:'selectCliente'} }
        ]},{ title:'Cliente' });
    $('#searchForm input[type=radio][name=op_opciones]').change(function() {
        $(this.form.search).prop('disabled',this.value==='n');
        if(this.value==='n')
            this.form.search.value='';
        else setfocus(this.form.search);
    });
    $('#edit').hide();
});
 function selectCliente(cliente){
    $('#cliFormTemp').setData($.extend(cliente,{op_opciones:'c'}),'name').find('.dialogSearch').addClass('x');
    $('#cliDialog').dialog('close');
}
function saveItem(data){
    var data=$('#frm_aut').getData('saveData');
    $.createDialogConfirm('Esta seguro que desea <b class="green">GUARDAR</b> el <b class="blue">VEHICULO</b>?', data, function(){
        $.saveDataJson('',data,
            function (resp){ 
                if(resp.success) {
                    searchGrid.gridUpdate();
                }
                $('#edit').hide(); 
            }
        );
    });
}
function deleteItem(data){
    data['deleteData']=true;
    $.createDialogConfirm('Esta seguro que desea <b class="red">DESACTIVAR</b> el <b class="blue">VEHICULO</b>?', data, function(){
        $.saveDataJson('',data,
            function (resp){ searchGrid.changeRow(data.Veh_Cod,{Veh_Est:'I',update:'','delete':''}); }
        );
    });
}
function newItem(){
    $('#frm_aut').setData({});
    $('#edit').show();
}
function editItem(data){
    var dat=searchGrid.getCell(data.Veh_Cod,'OriginalData');
    $('#frm_aut').setData(dat);
    if(!$.isEmpty(dat.Cli_Cod))
        selectCliente(dat);
    $('#edit').show();
}
function saveDatos(){
    var data=$('#frm_aut').getData('saveData');
}

