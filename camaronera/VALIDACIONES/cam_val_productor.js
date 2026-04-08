/*
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var searchGrid;
var haciendas,haciendasCreateDlg,haciendasCreateForm;

$(function(){
    searchGrid=$('#searchGrid');
    haciendas=$('#haciendas');

    if(searchGrid.length>0){
        $("#searchGrid").createGrid({
            datatype:'local', height: 295,
            stateCol:'Prd_Est',stateConfig:{I:'cellRed2'},
            leyenda:[{icon:'remove red',label:'Anulados/Inactivos'}],
            colModel: [
                {label: 'C&oacute;d. Int.', name: 'Prd_Cod', width: 25, align: "center", key:true},
                {label: 'Cod. Aux.', name: 'Prd_Cau', width: 25, align: "center"},
                {label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50, align: "left"},
                {label: 'Productor', name: 'Productor', width: 150, align: "left"},
                {label: 'C&eacute;dula/R.U.C.', name: 'Prd_Est', width: 50, formatter:'estado', align:'center'},
                {label: $.createIcon('remove')+'/'+$.createIcon('ok'), name: 'act1', width: 30, formatter:'gridButton', formatoptions:{action:'activarDato',title:'Activar Productor',data:'Prd_Cod',icon:'ok',type:'info',conditional:function(o){ return o.Prd_Est!=='A'; },caseFalse:function(o){ return $.getGridButton('anulaDato',o.Prd_Cod,'Desactivar Productor','remove',null,'danger'); }  } },
                {label: $.createIcon('pencil'), name: 'act2', width: 30, formatter:'gridButton', formatoptions:{action:'editarDato',title:'Editar Productor',data:'Prd_Cod', conditional:function(o){return o.Prd_Est==='A';} } }
            ]
        }, false, "#searchGridPager");
    }
    if(haciendas.length>0){
        haciendasCreateDlg=$('#haciCreateDialog');
        haciendasCreateForm=$('#haciCreateForm');
        haciendas.createGrid({
            caption:'Sector Productor',height: 150, datatype: "local",
            stateCol:'Sec_Est',stateConfig:{I:'cellRed2'},
            leyenda:[{icon:'stop red',label:'Anulados/Inactivos'}],
            colModel: [
                //{ label: 'C�d. Int.', name: 'Prh_Cod', width: 30 ,align:"center", key:true},
                { label: 'Cód. Int.', name: 'Index', width: 20 ,align:"center", key:true, hidden:true},
                { label: 'Cod. Sec.', name: 'Sec_Cod', width: 45,align:"center", hidden:true},
                { label: 'Sector', name: 'Sec_Nom', width: 45,align:"center"},
                { label: 'Encargado', name: 'Sec_Encargado', width: 100},
                { label: 'Descripción', name: 'Sec_Desc', width: 45,align:"right"},
                { label: 'Estado', name: 'Sec_Est', width: 45,align:"right", hidden:true},
                $.originalRow(),
                {name:'update',label:$.createIcon('pencil'), width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'updateHacienda', icon:'pencil', title:'Editar Item', data:function(o){ return {Index:o.Index, Prh_Cod:o.Prh_Cod}; }, conditional:function(o){ return o.Prh_Est==='A'; } }, resizable: false },
                {name:'delete',label:$.createIcon('remove'), width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'deleteHacienda', icon:'remove', title:'Eliminar Item', type:'danger', data:function(o){ return {Index:o.Index, Prh_Cod:o.Prh_Cod}; }, conditional:function(o){ return o.Prh_Est==='A'; } }, resizable: false }
            ]
        },true,'#haciendasPager').gridButtonsAdd([
            {caption:'Agregar Sector', buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ haciendasCreateForm.setData({}); haciendasCreateDlg.dialog('open'); } }
        ]);
        haciendasCreateDlg.createDialog({height:260, width:600, icon:'plus'});
    }

    if($('#provDialog').length>0)
    $('#provDialog').createSearchDialog({colModel:[
            { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15,align:"center",hidden:true },
            { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Proveedor', name: 'Proveedor', width: 100},
            { label: 'Cont.', name: 'Prv_Con', width: 20,align:"center", labelLong:'Obligado a Llevar Contabilidad', formatter:'truefalse', formatoptions:{msg:false}  },
            { label: 'Espe.', name: 'Prv_Esp', width: 20,align:"center", labelLong:'Contribuyente Especial', formatter:'truefalse', formatoptions:{msg:false} },
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectProvee} }
        ]},{ title:'Proveedor' });

    if($('#cuenDialog').length>0)
    $('#cuenDialog').createSearchDialog({
          datatype: 'local',
          colModel:[
            { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center", hidden:false },
            { label: 'Codigo', name: 'Pld_Cdc', width: 45 },
            { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
            { label: 'Estado', name: 'Pld_Est', width: 30,align:"center", formatter:'estado', title:false},
            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false, formatter:function (cv, opts, rObj) { return  $.getGridButton(SelectCta,{Pld_Cod:rObj.Pld_Cod, tipo:'D'},'Seleccione Cuentas'); } }
        ]},{ title:'Cuenta', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] })
        .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:&nbsp;</label><input id="Index" name="Index" type="hidden" value="" /><input data-name="Pec_Cod" name="Pec_Cod" type="hidden" /><input data-name="Year" name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>');

});
function activarDato(Prd_Cod){
    var row=searchGrid.jqGrid('getRowData',Prd_Cod);
    $.createDialogConfirm('¿Esta seguro de <u class="green"><b>ACTIVAR</b></u> el Productor <b>'+row['Productor']+'</b>?', {changeEstado:true, Prd_Cod:Prd_Cod, Prd_Est:'A'}, changeEstado);
}
function anulaDato(Prd_Cod){
    var row=searchGrid.jqGrid('getRowData',Prd_Cod);
    $.createDialogConfirm('¿Esta seguro de <u class="red"><b>DESACTIVAR</b></u> el Productor <b>'+row['Productor']+'</b>?', {changeEstado:true, Prd_Cod:Prd_Cod, Prd_Est:'I'}, changeEstado);
}
function changeEstado(data){
    $.saveDataJson("", data ,function (r) {
        //searchGrid.trigger("reloadGrid");
        searchGrid.changeRow(data['Prd_Cod'],{Prd_Est:data['Prd_Est']});
    });
}
function setCuenta(form,data){
    $('#'+form).setData(data,'name');
}
function editarDato(Prd_Cod){
    $.getDataJson("", {getDatoDetalle:true, Prd_Cod:Prd_Cod} ,function (r) {
        $('#formDocumento').setData(r.dato);
        haciendas.setRowsByIndex(r.haciendas,'Index');
        setCuenta('ctaCC1FormTemp',r.CxC);
        setCuenta('ctaCC2FormTemp',r.Inv);
        setCuenta('ctaCC3FormTemp',r.Liq);
        $('#lista').moveComp('#editarDato').updateGridsSizes();
    });
}
function setMagap(){
    var val=$('#MagapCod').val();
    if($.isEmpty(val)) return;
    $('.magap').hide();
    $('.magap').setData({});
    $('.magap.'+(val==='S'?'propio':'prestado')).show();
}