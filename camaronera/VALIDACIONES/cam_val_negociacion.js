/*
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */


if ($('#prodDialog').length > 0)
    $('#prodDialog').createSearchDialog({
        colModel: [
            { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Productor', name: 'Proveedor', width: 100 },
            { label: 'Cont.', name: 'Prv_Con', width: 20, align: "center", labelLong: 'Obligado a Llevar Contabilidad', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: 'Espe.', name: 'Prv_Esp', width: 20, align: "center", labelLong: 'Contribuyente Especial', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectProd } }
        ]
    }, { title: 'Productor' });

function validarNum_Neg(generado = false) {
    $.post('', { 'secNegAjax': true }, function (Num_Neg) {
        $('#frm_negociacion').setData({ 'Num_Neg': Num_Neg }, false);
    }, 'json').fail(function () { $.alert(); });
}
validarNum_Neg(true);


function validaDocument() {
	$.saveDataJson('', $('#frm_negociacion').getData('saveNegociacion'), function (resp) {
    });
}

//Cancelar negociacion 

