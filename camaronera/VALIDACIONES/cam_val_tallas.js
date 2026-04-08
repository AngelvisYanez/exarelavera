var container = $("#container");
$(function () {
    armarGridPrincipal();
});
// armar gridPrincipal
function armarGridPrincipal() {
    container.createGrid({
        caption: 'Tallas de Camarón',
        height: 200, autowidth: true, shrinkToFit: false,
        colModel: [
            { label: 'Código', name: 'Cod_Tall', width: 100, key: true, align: 'left', viewable: false },
            { label: 'Talla', name: 'Talla', width: 150 },
            { label: 'Tipo', name: 'Tip', width: 150 },
            { label: 'Medida', name: 'Tip_Med', width: 85, hidden: false },
            { label: 'Estado', name: 'Tall_Est', width: 85, hidden: false },
            { label: '&nbsp;', name: 'act1', width: 40, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selecNeg } },
            { name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 40, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: anularFila, icon: 'remove', type: 'danger', title: 'Eliminar Item', data: function (o) { return o; } }, resizable: false },
        ],
        subGrid: false,
        multiselect: false,
        footerrow: false,
    }, true, '#containerPager', { refresh: false, view: false })
}

function selecNeg(data) {

    $("#Cod_Tall").val(data.Cod_Tall);
    $("#talla").val(data.Talla);
    $("#Tip").val(data.Tip).trigger('change');
    $("#Tip_Med").val(data.Tip_Med).trigger('change');
}

function anularFila(data) {
    $("<div></div>").dialog({
        modal: true, title: "Confirmación de Anulación",
        open: function () {
            var markup = "¿Estás seguro de que deseas anular este dato?";
            $(this).html(markup);
        }, buttons: {
            "Anular": function () {
                $.post('', { anularTallasAjax: true, Cod_Tall: data.Cod_Tall }, function (response) {
                    if (response.success) {
                        $("#container").jqGrid('delRowData', data.id);
                        $.alert(response.message);
                        $('#frm_talla')[0].reset();
                    } else {
                        $.alert(response.message);
                    }
                }, 'json').fail(function () { $.alert("Error en la comunicación con el servidor.") });
                $(this).dialog("close");
            }, "Cancelar": function () { $(this).dialog("close"); }
        },
        close: function () { $(this).remove(); }
    });
}

function validaDocumentTalla() {
    $.saveDataJson('', $('#frm_talla').getData('saveTallas'), function (resp) {
        $('#container').Search('#frm_prod_tall', 'loadTallasAjax')
        $("#Cod_Tall").val("");
        $('#frm_talla')[0].reset();
    });
}

function nuevoTalla() {
    $("#Cod_Tall").val("");
    $('#frm_talla')[0].reset();
}