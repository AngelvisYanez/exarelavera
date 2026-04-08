$(function () {
    if ($('#gestionarMarca').length === 1)
        $("#gestionarMarca").createDialog({ width: 400, height: 180, icon: 'info-sign' });
    if ($('#modMarca').length === 1)
        $("#modMarca").createDialog({ width: 400, height: 180, icon: 'info-sign' });

    $("#marcas").createGrid({
        postData: $("#searchMarca").getData("marcasAjax"), height: 300,
        caption:' ',
        colModel: [
            { name: "Pld_Cod", hidden: true },
            { label: 'Marca', name: 'Bam_Nom', align: "left", width: 30 },
            { label: 'Descripci&oacute;n', name: 'Bam_Des', align: "left", width: 30 },
            { label: 'Tama&ntilde;o', name: 'Bam_Tam', align: "left", width: 25 },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_anti', width: 10, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    if(rowObject.tarja === 'n'){
                        return $.getGridButton(preDelMarca, rowObject, 'Eliminar Marca', 'remove', '', 'danger') + "&nbsp;" +
                        $.getGridButton(modMarca, rowObject, 'Modicifar Marca', 'edit', '', 'success');
                    } else {
                        return $.getGridButton(modMarca, rowObject, 'Modicifar Marca', 'edit', '', 'success');
                    }
                }
            }
        ],
        loadComplete: function (data) {
            //
        },
        pager: "#marcasPager", rownumbers: true,
        rowNum: 10000, gridview: true, viewrecords: true,
        onSelectRow: function (rowid, e) { $("#marcas").resetSelection(); },
        multiselect: false
    }, false, "#marcasPager", { view: false, refresh: false }).gridButtonsAdd([
        { caption: 'Agregar Marca', buttonicon: 'glyphicon glyphicon-plus', class: 'a', onClickButton: function () { gestionarMarca(); } }
    ]);
});

function gestionarMarca() {
    $('#gestionarMarca').dialog('open');
}

function preDelMarca(row){
    $.createDialogConfirm('¿Est&aacute; seguro que desea dar de baja esta marca?',row,delMarca);
}

function delMarca(row) {
    $.saveDataJson("", {delMarca:true,Bam_Cod:row.Bam_Cod}, function (responce) {
        $("#marcas").trigger("reloadGrid");
        return false;
    }, function (responce) {
        $.alert(responce['message']);
    });
}

function modMarca(row) {
    $("#Bam_Cod").val(row.Bam_Cod);
    $("#mod_Bam_Nom").val(row.Bam_Nom);
    $("#mod_Bam_Des").val(row.Bam_Des);
    $("#mod_Bam_Tam").val(row.Bam_Tam);
    $('#modMarca').dialog('open');
}

function limpiarFormMarca(tipo) {
    if (tipo === "add") {
        $("#Bam_Nom").val("");
        $("#Bam_Des").val("");
        $("#Bam_Tam").val("");
    } else {
        $("#Bam_Cod").val("");
        $("#mod_Bam_Nom").val("");
        $("#mod_Bam_Des").val("");
        $("#mod_Bam_Tam").val("");
    }
}

function validarFormMarca(tipo) {
    if (tipo === "add") {
        if ($("#Bam_Nom").val() === "" || $("#Bam_Des").val() === "" || $("#Bam_Tam").val() === "") { return false; } else { return true; }
    } else {
        if ($("#mod_Bam_Nom").val() === "" || $("#mod_Bam_Des").val() === "" || $("#mod_Bam_Tam").val() === "") { return false; } else { return true; }
    }
}

function saveMarca() {
    if (!validarFormMarca("add")) { return $.alert("Llene todos los campos del fromulario!"); }
    var data = $('#marcaForm').serializeObject();
    data["saveMarca"] = true;
    $.saveDataJson("", data, function (responce) {
        $('#gestionarMarca').dialog('close');
        limpiarFormMarca("add");
        $("#marcas").trigger("reloadGrid");
    }, function (responce) {
        $.alert(responce['message']);
    });
}

function modifyMarca() {
    if (!validarFormMarca("mod")) { return $.alert("Llene todos los campos del fromulario!"); }
    var data = $('#modMarcaForm').serializeObject();
    data["modMarca"] = true;
    $.saveDataJson("", data, function (responce) {
        $('#modMarca').dialog('close');
        limpiarFormMarca("mod");
        $("#marcas").trigger("reloadGrid");
        // return false;
    }, function (responce) {
        $.alert(responce['message']);
    });
}