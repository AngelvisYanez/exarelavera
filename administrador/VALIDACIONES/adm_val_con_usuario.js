var container;
$(function () {
   container = $("#container");
   container.createGrid({
      caption:"LISTADO DE USUARIOS", selectGridRows:false, sortname: "Usuario", sortorder: "asc",
      postData: $("#searchContainer").getData("usrAjax"), height: 300, stateCol:'Usu_Est',/* stateConfig:{Inactiva:'cellRed2'}, */
      colModel: [         
         { label:'Cod.Int', name: 'Usu_Cod', align: "center", hidden: false, key: true, width: 2 },
         { label:'C&eacute;dula/RUC', name: 'Usu_Ced', align: "center", width: 3 },
         { label:'Usuario', name: 'Usuario', width: 10 },
         { label:'Perfiles', name: 'Perfiles', width: 12, formatter:'tags', formatoptions:{label:'Per_Des',type:'purple'} },
         { label:'Estado', name: 'Usu_Est', align: "center", width: 3, formatter:'estado', formatoptions:{full:true} },
         { label:$.createIcon('cog'), name:'actReg', width:2, formatter:'gridButton', formatoptions:{ action:'changeUsuario', data:['Usu_Cod','Usuario',{Usu_Est:'I'}], icon:'ban-circle', type:'danger', title:'<u class="red">Desactivar</u> Usuario', conditional:function(o){ return o.Usu_Est==='A'; }, caseFalse:function(o){ return $.getGridButton('changeUsuario',$.newObj(['Usu_Cod','Usuario',{Usu_Est:'A'}],o),'<u class="green">Activar</u> Usuario','ok'); } } }
      ]
   }, true, "#containerPager", { view:false }).gridButtonsAdd([]);
});
function changeUsuario(usu){ 
    usu['updateUsuario']=true; //console.log(usu);
    $.createDialogConfirm(`¿Est&aacute; seguro que desea `+(usu['Usu_Est']==='A'?'<strong class="green">ACTIVAR</strong> a <u class="red">':'<strong class="red">DESACTIVAR</strong> a <u class="green">')+`${usu['Usuario']}</u>  ?`,usu,function(){
        $.saveDataJson("", usu, function(r){                 
            container.changeRowData(usu.Usu_Cod,usu);
            return false;
        });
    });
}