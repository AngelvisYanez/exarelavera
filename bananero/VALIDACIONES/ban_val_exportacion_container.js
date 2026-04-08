var container, gestionarContainer, containerForm;
$(function () {
    if ($('#gestionarContainer').length === 1){
        gestionarContainer=$("#gestionarContainer");
        gestionarContainer.createDialog({ width: 750, height: 300, icon: 'pencil' });
        containerForm=$('#containerForm');
    }
    //grid que contiene los contenedores
    container=$("#container");
    container.createGrid({
        postData: $("#searchContainer").getData("containerAjax"), height: 300, //caption:' ',
        stateCol:'Exc_Est',stateConfig:{I:'cellRed2'/*,A:'cellGreen2'*/},
        colModel: [
            { name: "Exc_Cod", hidden: true, key:true },            
            { label: 'A&ntilde;o', name: 'Exc_Ano', align: "center", width: 10 },
            { label: 'Semana', name: 'Exc_Sem', align: "center", width: 10 },
            { label: 'Fecha', name: 'Exc_Fec', align: "left", width: 15 },
            { label: 'Buque', name: 'Exc_Vap', align: "left", width: 20 },
            { label: 'Container', name: 'Exc_Con', align: "left", width: 20 },
            { label: 'Can.', name: 'Exc_Can', align: "left", width: 10 },
            { label: 'Bodega', name: 'Exc_Bod', align: "left", width: 25 },
            { label: 'Pto. Mar.', name: 'Exc_Pto', align: "left", width: 25 },
            { label: 'Ciu./Zon.', name: 'Exc_Zon', align: "left", width: 25 },
            { label: 'Est.', name: 'Exc_Est', align: "center", width: 10, formatter:'estado', title:false },
            { label: 'Tarjas', name: 'tarja', width: 10, hidden: true },
            { label: $.createIcon('cog'), name: 'actMod', align: "center", width: 10, formatter:'gridButton', formatoptions:{ action:modContainer, icon:'pencil',  conditional:function(o){ return o.Exc_Est!=='I'; } }, title:false },
            { label: $.createIcon('remove'), name: 'actDel', align: "center", width: 10, formatter:'gridButton', formatoptions:{ action:preDelContainer, data:'Exc_Cod', conditional:function(o){ return o.tarja === 'n'&&o.Exc_Est!=='I'; }, icon:'remove', type:'danger', title:'Eliminar Container' }, title:false }
//            {
//                label: $.createIcon('cog'), name: 'btns_anti', width: 20, align: 'center', viewable: false,
//                formatter: function (cellvalue, options, rowObject) {
//                    if(rowObject.tarja === 'n'){
//                        return $.getGridButton(preDelContainer, rowObject, 'Eliminar Container', 'remove', '', 'danger') + "&nbsp;" +
//                        $.getGridButton(modContainer, rowObject, 'Modicifar Container', 'edit', '', 'success');
//                    } else {
//                        return $.getGridButton(modContainer, rowObject, 'Modicifar Container', 'edit', '', 'success');
//                    }
//                    
//                }
//            }
        ],
        selectGridRows: false
    }, true, "#containerPager", { view: false, refresh: false }).gridButtonsAdd([
        { caption: 'Agregar Container', buttonicon: 'plus', classes: 'a', onClickButton: function() { containerForm.data({tipo:'saveContainer'}); limpiarFormContainer(); gestionarContainer.dialog('open'); } }
    ]);
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false });
});

function preDelContainer(Exc_Cod){ $.createDialogConfirm('¿Est&aacute; seguro que desea dar de baja este container?',Exc_Cod,delContainer); }
function delContainer(Exc_Cod) {
    $.saveDataJson("", {delContainer:true,Exc_Cod:Exc_Cod}, function (responce) {
        container.changeRow(Exc_Cod,{Exc_Est:'I',actDel:''});
        return false;
    });
}
function modContainer(row){
    containerForm.setData(row).data({tipo:'modContainer'});
    $("#Exc_Ano").trigger("change");
    gestionarContainer.dialog('open');
}
function limpiarFormContainer() {
    containerForm.setData({});
    $("#Exc_Ano").trigger("change");
}
function validarFormContainer(tipo) {
    var data={form:containerForm.getData()};
    data[tipo]=true;
    /* aqui puedo poner validaciones */
    console.log(data);
    $.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?',data,saveContainer);
}
function saveContainer(data) {    
    $.saveDataJson("", data, function (responce){
        container.gridUpdate().loadUpdate();
        gestionarContainer.dialog('close');
    });
}
function setFecPeriodoCom() { $("#Exc_Fec").dateLimits($("#Exc_Ano option:selected").attr("data-pec-fei"), $("#Exc_Ano option:selected").attr("data-pec-fef")); }