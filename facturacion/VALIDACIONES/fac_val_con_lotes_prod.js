var arrayMes = [],
    arrayDia = [],
    arrayAlarm = [],
    dias = false,
    meses = false;
$(function() {

    var grid = $('#lotesConGrid');
    /**Val Cabecera */
    llenarArrayMeses();
    llenarArrayDias();
    verficaClick();

    /** End Val Cabecera */
    $('#notificacionesDialog').createDialog({ height: 273, width: 600, noTitleStuff: false, noBorder: true, noOverflow: true, extraClass: 'noMargin' });

    var optsN = {
        height: 75,
        caption: 'Lotes con Notificación',
        sortable: true,
        colModel: [
            { label: 'Cód. Int', name: 'Lte_Cod', width: 7, align: 'right', sorttype: 'int', hidden: false },
            { name: 'Pro_Cod', width: 10, sorttype: 'int', align: 'center', hidden: true, formatter: 'textboxExa' },
            { name: 'Prv_Cod', width: 10, sorttype: 'int', align: 'center', hidden: true, formatter: 'textboxExa' },
            /* { label: 'Producto', name: 'Ite_Cor', width: 20, align: 'center' },
            { label: 'Marca', name: 'Mar_Des', width: 15, align: 'center' },
            { label: 'Proveedor', name: 'Prv_Com', width: 25, align: 'center' }, */
            { label: 'Serie Lote', name: 'Lte_Ser', width: 20, align: 'center' },
            { label: 'F. Elaboración', name: 'Lte_Alt', width: 10, align: 'right', sortable: false, hidden: false },
            { label: 'F. Caducidad', name: 'Lte_Cad', width: 10, align: 'right', sortable: false, hidden: false },
            /* { label: ' # Dias', name: 'dias', width: 7, align: 'center' },
            { label: 'Notificarme', name: 'Lte_Nti', width: 7, align: 'center' } */
        ]
    };
    $('#notiGrid').createGrid($.extend(optsN, { height: 219, width: 593, responsive: false, caption: 'Lotes con Notificación <button type="button" role="button" tabindex="-1" class="ui-button ui-widget ui-state-default ui-corner-all pull-right" title="Cerrar Ventana" onclick="$(\'#notificacionesDialog\').dialog(\'close\')"><span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span></button>' }), true);

    grid.createGrid({
            caption: 'Lotes',
            height: '500',
            colModel: [
                { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', hidden: true, key: true },
                { label: 'Cód. Int', name: 'Lte_Cod', width: 7, sorttype: 'int', hidden: false, /* formatter: 'textboxExa', formatoptions: { attr: { readonly: 'readonly', align: 'center' } } */ },
                { name: 'Pro_Cod', width: 10, sorttype: 'int', align: 'center', hidden: true, formatter: 'textboxExa' },
                { name: 'Prv_Cod', width: 10, sorttype: 'int', align: 'center', hidden: true, formatter: 'textboxExa' },
                { label: 'Producto', name: 'Ite_Cor', width: 20, align: 'center' /* , formatter: 'textboxExa', formatoptions: { attr: { readonly: 'readonly' } } */ },
                { label: 'Marca', name: 'Mar_Des', width: 15, align: 'center' /* , formatter: 'textboxExa', formatoptions: { attr: { readonly: 'readonly' } } */ },
                { label: 'Proveedor', name: 'Prv_Com', width: 25, align: 'center' /* , formatter: 'textboxExa', formatoptions: { attr: { readonly: 'readonly' } } */ },
                { label: 'Serie Lote', name: 'Lte_Ser', width: 20, align: 'center' /* , formatter: 'textboxExa', formatoptions: { attr: { readonly: 'readonly' } } */ },
                { label: 'F. Elaboración', name: 'Lte_Alt', width: 10, align: 'right', sortable: false, hidden: false, /* formatter: 'textboxExa', formatoptions: { afterInit: function(el) { $.createDatePickers(el); }, attr: { readonly: 'readonly' } } */ },
                { label: 'F. Caducidad', name: 'Lte_Cad', width: 10, align: 'right', sortable: false, hidden: false, /* formatter: 'textboxExa', formatoptions: { afterInit: function(el) { $.createDatePickers(el); }, attr: { readonly: 'readonly' } } */ },
                { label: ' # Dias', name: 'dias', width: 7, align: 'center' /* , formatter: 'textboxExa', formatoptions: { attr: { readonly: 'readonly' } } */ },
                { label: 'Notificarme', name: 'Lte_Nti', width: 7, align: 'center' },
                { name: 'delete', label: '<i class="glyphicon glyphicon-trash"></i>', width: 6, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: quitarLote, icon: 'trash', type: 'danger', title: 'Dar de Baja el Lote', data: function(o) { return o; } }, resizable: false }


            ],
            loadComplete: function(data) {
                if ($.varValid(data.rows)) {
                    for (var i = 0, z = data.rows.length; i < z; i++) {
                        //console.log(data.rows[i]['dias']);
                        //console.log(data.rows[i].Lte_Cod); cellPurple1
                        if (data.rows[i]['dias'] === 'CADUCADO') {
                            $("#" + (i + 1) + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
                        } else if (data.rows[i]['dias'] === '0') { $("#" + (i + 1) + ' td:not(.jqgrid-rownum)').addClass('cellOrange2'); } else if (data.rows[i]['dias'] === data.rows[i]['Lte_Nti']) { $("#" + (i + 1) + ' td:not(.jqgrid-rownum)').addClass('cellPurple1'); } else {
                            $("#" + (i + 1) + ' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                        }
                    }
                }
                alarmaNotificacion();

            }

        },
        true,
        '#lotesGridPager', { refresh: false, view: false });
    buscarAJax();


});

function alarmaNotificacion() {
    arrayAlarm.length = 0;
    var notificaciones = $('#lotesConGrid').getGridBatch();
    $.each(notificaciones, function(pos, valor) {
        if ((valor['dias'] * 1) <= (valor['Lte_Nti'] * 1)) {
            arrayAlarm.push(valor);
        }
    });
    $('#notiGrid').setRows(arrayAlarm);

    setTimeout(function() {
        if (arrayAlarm.length > 0) {
            $('#notificacionesDialog').dialog('open');
        }
    }, 2500);

    /* arrayAlarm.forEach(function(valor) {
        $.alert(`El Lote ${valor['Lte_Ser']} debe devolverse hoy.`);
    }); */


}


function buscarAJax() {

    $('#lotesConGrid').Search('#frm_con_activ', 'ajaxLotes');
}

function quitarLote(row) {
    //console.log(row);
    $.createDialogConfirm('Desea Eliminar el lote seleccionado..!!', null, function() {
        $.saveDataJson("", { deleteLote: true, Lte_Cod: row['Lte_Cod'], Pro_Cod: row['Pro_Cod'] }, (respuesta) => {
            if (respuesta['success']) {
                $('#lotesConGrid').jqGrid('delRowData', row.id);
                $('#lotesConGrid').trigger("reloadGrid");
                $.alert('La transacci&oacute;n se realizo con exito.');
                return false;
            }
        });
    });

}

function llenarArrayMeses() {
    arrayMes.length = 0;
    var tx = ' - Mes',
        stx = ' - Meses',
        testo = '';
    for (let i = 0; i < 12; i++) {
        if ((i + 1) > 1) { testo = stx; } else { testo = tx; }
        arrayMes.push({ id: i + 1, texto: 'A ' + (i + 1) + testo })
    }
    setSelectMeses();
}

function llenarArrayDias() {
    arrayDia.length = 0;
    var tx = ' - Día',
        stx = ' - Días',
        testo = '';
    for (let i = 0; i < 31; i++) {
        if ((i) > 1 || i === 0) { testo = stx; } else { testo = tx; }
        arrayDia.push({ id: i, texto: 'A ' + (i) + testo })
    }
    setSelectDias();
}

function setSelectMeses() {
    for (let j = 0; j < arrayMes.length; j++) {
        if (j > 0) {
            $('.select_meses').append($('<option>', { value: arrayMes[j]['id'], text: arrayMes[j]['texto'] }));
        }

    }

}

function setSelectDias() {
    for (let k = 0; k < arrayDia.length; k++) {
        $('.select_dias').append($('<option>', { value: arrayDia[k]['id'], text: arrayDia[k]['texto'] }));
    }

}

function limpiarTF() {
    $('#chkM').attr('disabled', 'disabled');
    $('#chkD').attr('disabled', 'disabled');
    $('#chkM').prop('checked', false);
    $('#chkD').prop('checked', false);
    $('.select_dias').attr('disabled', 'disabled');
    $('.select_meses').attr('disabled', 'disabled');
    $("#stdD").val("n");
    $("#stdM").val("n");
}

function limpiarPF() {
    $('#search').attr('disabled', 'disabled');
    $('#chkM').removeAttr('disabled');
    $('#chkD').removeAttr('disabled');
    $('#chkM').prop('checked', true);
    $('#chkD').prop('checked', false);
    $("#stdD").val("n");
    $("#stdM").val("s");
}

function verficaClick() {
    $("#rad_ba1").on("click", () => {
        $('#search').removeAttr('disabled');
        limpiarTF();
    });

    $("#rad_ba2").on("click", () => {
        $('#search').removeAttr('disabled');
        limpiarTF();
    });

    $("#rad_ba3").on("click", () => {
        $('.select_meses').removeAttr('disabled');
        $('.select_dias').attr('disabled', 'disabled');
        dias = false;
        meses = true;
        limpiarPF();
    });
    $("#rad_ba4").on("click", () => {
        $('.select_dias').removeAttr('disabled');
        $('.select_meses').attr('disabled', 'disabled');
        dias = true;
        meses = false;
        limpiarPF();

    });
}

function verificaCheckM() {
    if ($('#chkM').prop('checked')) {
        if (meses) { $('.select_meses').removeAttr('disabled'); }
        if (dias) { $('.select_dias').removeAttr('disabled'); }
        $("#stdM").val("s");
        $('#chkD').prop('checked', false);
        $("#stdD").val("n");
    } else { $("#stdM").val("n"); }


}

function verificaCheckD() {
    if ($('#chkD').prop('checked')) {
        $('.select_meses').attr('disabled', 'disabled');
        $('.select_dias').attr('disabled', 'disabled');
        $("#stdD").val("s");
        $('#chkM').prop('checked', false);
        $("#stdM").val("n");
    } else { $("#stdD").val("n"); }

}