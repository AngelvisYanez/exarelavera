var $treeview, grid, gridGroup, selected;
$(function(){
    $treeview=$('#directorios');
    grid=$('#perfilesGrid');
    gridGroup=$('#perfilesGroupGrid');
    $('#perfilDialog').createDialog({height:175, width:500});
    var opt={caption:"Listado de Perfiles", height:265, stateCol:'Per_Est', leyenda:[{label:'Anulado/Inactivo',icon:'remove red'}], rowNum:10000000, pgbuttons:false, pgtext:null}, 
    model=[
        { label: 'C&oacute;d. Int.', name: 'Per_Cod', width: 15,key:true, hidden:false },   
        { label: 'Perfil', name: 'Per_Des', width: 50, align: "left", classes:'bgNoRight'},
        { label: 'Estado', name: 'Per_Est', width: 10 ,align:"center", formatter:"estado", formatoptions:{full:false} }
    ];  
    //$treeview.on("beforeLoad.jstree",function(){ console.log('before'); });
    $treeview.jstree({        
        core : {
            data: {'url': urlTree,"dataType": "json" },
            multiple: true, check_callback: true
        }, types : {
            Org: {"icon" : "glyphicon glyphicon-folder-open yellow"},        
            Pcs: {"icon" : "glyphicon glyphicon-chevron-right blue"}
        }, node_customize:{
            types:{
                Org:function(el,node){
                    var o=node.original;
                    node.text='<i class="grey">Dir.:</i> '+o.Org_Des+($.isEmpty(o.Org_Det)?'':' <i class="glyphicon glyphicon-info-sign blue" title="'+o.Org_Det+'"></i>');
                    node.icon=$.isEmpty(o.Org_Ico)?node.icon:o.Org_Ico+" orange";
                },
                Pcs:function(el,node){
                    var o=node.original;
                    node.text='<b> '+o.Pcs_Lin+'</b> ('+o.Pcs_Nom+')'+($.isEmpty(o.Pcs_Det)?'':' <i class="glyphicon glyphicon-info-sign purple" title="'+o.Pcs_Det+'"></i>');
                    node.icon=o.Pcs_Tip!=='P'?"glyphicon glyphicon-print brown":($.isEmpty(o.Pcs_Ico)?node.icon:o.Pcs_Ico+" green");
                }
            }
        }, plugins: ["types","checkbox","node_customize","search"]
    }).on('refresh.jstree loaded.jstree',function(e,data){ var tab=$('#tabsMain').find('.ui-tabs-nav .ui-tabs-active').text(); $('.group')[tab==='Individuales'?'hide':'show'](); });
    $("#tabsMain").createTabs({
        beforeActivate:function(e,ui){ clean(); 
            var tab=ui.newTab.text();
            $('#plan-tittle').html(tab==='Individuales'?'':tab).show();
            $('.group')[tab==='Individuales'?'hide':'show']();
        },init:{
            'tabs-1':function(){
                grid.createGrid($.extend({url:"?perfilAjax=true",colModel:model.concat(
                    { label: $.createIcon('pencil'), name: 'actPermiso', align: "center", width: 10, formatter: 'gridButton', formatoptions: { action: 'editarPerfil', conditional: function (o) { return o.Per_Est !== 'I'; }, title: 'Editar Perfil', icon:'pencil', type:'primary' } },
                    { label: $.createIcon('trash'), name: 'actPermiso', align: "center", width: 10, formatter: 'gridButton', formatoptions: { action: 'anularPerfil', conditional: function (o) { return o.Per_Est !== 'I'; }, title: 'Anular Perfil', icon:'trash', type:'danger' } },
                    { label: $.createIcon('arrow-right'), name: 'actPermiso', align: "center", width: 10, formatter: 'gridButton', formatoptions: { action: 'editarPermisos', conditional: function (o) { return o.Per_Est !== 'I'; }, title: 'Editar Permisos', caseFalse:$.createIcon('remove red') } 
                })},opt),false,'#perfilesPager').gridButtonsAdd([ null, { caption: 'Agregar Perfil', buttonicon: 'plus', onClickButton: function(){ editarPerfil({Per_Est:'A'}); } } ]);
            },'tabs-2':function(){
                gridGroup.createGrid($.extend({url:"?perfilGroupAjax=true",colModel:model.concat(
                    { label:$.createIcon('ok'), name:'actSelect', formatter:'checkboxExa', width:10, align:'center', title:false }
                )},opt),false,'#perfilesGroupPager');
            }
        }
    });
    $('.none').hide();
});
function selectProc(Pcs_Cod){ $treeview.jstree('select_node', 'P_'+Pcs_Cod,false,true); }
function unSelectProcs(){  $treeview.jstree().deselect_all(true); $treeview.find('.jstree-node .jstree-checkbox').removeClass('jstree-undetermined'); }
function getSelectProcs(){ 
    var sels=$.arraySpliceWhere($treeview.jstree("get_selected"), function(o){ return o.substring(0, 1)==='G'; });
    $.each(sels, function(i,v){ sels[i]=v.substring(2); }); return sels;
}
function updateTree(){ $treeview.jstree(true).refresh(); }
function clean(){ $('.none').hide(); unSelectProcs(); var tab=$('#tabsMain').find('.ui-tabs-nav .ui-tabs-active').text(); $('#plan-tittle').html(tab==='Individuales'?'':tab).show(); }
function searchNode(val){
    this.val('').removeClass('clearable');    
    var node=$treeview.searchString(val);    
    setTimeout(function(){ $treeview.scrollFocusNode(node); },100);
}
function editarPerfil(data){
    //console.log(data);
    $('#perfilForm').setData(data);
    $('#perfilDialog').dialog('open');
}
function editarPermisos(data){      
    if($treeview.jstree('is_loading','#')) return $.alert("Aun no se ha cargado el listado de procesos!");
    $('.individual').show();
    $('#plan-tittle').html(data.Per_Des);
    unSelectProcs();
    $('#permisosForm').setData(data);
    $.getDataJson('',{getPermisos:true, Per_Cod:data.Per_Cod},function(r){ 
        //var opens=[]; $treeview.find('.jstree-open').each(function(i,e){ opens.push(e.id); });
        if($.isArray(r.permisos)) $.each(r.permisos,function(i,v){ selectProc(v.Pcs_Cod); });        
        //$treeview.jstree('close_all');
        //$.each(opens,function(i,v){ $treeview.jstree("open_node", $("#"+v)); });
    });
    
}
function validaPermisos(type){
    var tab=$('#tabsMain').find('.ui-tabs-nav .ui-tabs-active').text(), msg='', 
        data={savePermisos:tab, permisos:getSelectProcs()||[], action:type};
    if(tab!=='Individuales'&&data['permisos'].length===0) return $.alert("Debe seleccionar al menos un proceso!");
    switch(tab){
        case 'Individuales':
            $.extend(data,$('#permisosForm').getData());
            msg='¿Est&aacute; seguro que desea guardar los Permisos de Usuario de <b class=\'green\'>'+data['Per_Des']+'</b>?';
            break;
        case 'Grupales':
            data['perfiles']=gridGroup.getSelectedByCol('actSelect','S');
            if(data['perfiles'].length===0) return $.alert("Debe seleccionar al menos un perfil!");           
            msg='¿Est&aacute; seguro que desea '+(type==='remove'?'<b class="red">Remover</b>':'<b class="green">Agregar</b>')+' los <b class="blue">Permisos de Usuario</b> del <b class="blue">Grupo de Perfiles</b> seleccionado?';
            break;
    }    
    console.log(data);
    $.createDialogConfirm(msg,data,savePermisos);
}
function savePermisos(data){ 
    var tab=data['savePermisos'];
    $.saveDataJson("", data, function (r){  
        if(tab!=='Individuales'){
            unSelectProcs();
        }
    });
}
function saveForm(data) {
    $.saveDataJson("", data, function (r){        
        grid.gridUpdate(); clean();
        $('#perfilDialog').dialog('close');
    });
}
function anularPerfil(data){
    $.createDialogConfirm('¿Est&aacute; seguro que desea desactivar el Perfil, no se podra reversar?',{anulaData:true, id:data.Per_Cod},function(data){
        $.saveDataJson("", data, function (r){
            grid.gridUpdate(); clean();
        });
    });
}
