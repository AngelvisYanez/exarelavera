$(function () {
    $('#tableVentas').createGrid({
        caption: 'Ventas',
        datatype: "local",
        height: 430,
        stateConfig: { Inactivo: 'cellRed2', Detalle: 'cellGreen2' }, stateCondition: function (row) { if (row['Detalle'] === 'S') return "Detalle"; if (row['Vet_Est'] !== 'A') return 'Inactivo'; },
        leyenda: [{ icon: 'stop green', label: 'Contiene Pagos' }, { icon: 'lock orange', label: 'Contiene Pagos' }, { icon: 'remove red', label: 'Anulados/Inactivos' }],
        colModel: [
            { label: 'C&oacute;d. Int.', name: 'VetCod', key: true, width: 5, align: "center", hidden: false, },
            { label: 'Fecha', name: 'Caj_Fec', width: 5, align: "center", hidden: false },
            { label: 'RUC', name: 'Ruc', width: 5, align: "center", hidden: false },
            { name: 'Cli_Cod', width: 20, align: "center", hidden: true },
            { name: 'Vet_Est', width: 20, align: "center", hidden: true },
            { name: 'Caj_Cod', width: 20, align: "center", hidden: true },
            { name: 'Vnd_Cod', width: 20, align: "center", hidden: true },
            { name: 'Tpc_Cod', width: 20, align: "center", hidden: true },
            { name: 'Ide_Cod', width: 20, align: "center", hidden: true },
            { name: 'Tic_Cod', width: 20, align: "center", hidden: true },
            { label: 'Cliente', name: 'Cliente', width: 15, align: "left", hidden: false },
            { label: 'Tipo Documento', name: 'Tic_Des', width: 5, align: "center", hidden: false },
            { label: 'No. Documento', name: 'Vet_Num', width: 5, align: "center", hidden: false },
            //{ label: 'Total', name: 'Total', width: 5, hidden: false, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }, summaryType: "sum" },
            {
                name: 'delete', label: $.createIcon('remove'), width: 3, align: 'center', viewable: false, formatter: 'gridButton',
                formatoptions: { action: delFila, conditional: function (o) { return o.Detalle !== 'S' && o.Vet_Est === 'A'; }, caseFalse: function (o) { if (o.Vet_Est !== "A") return $.createIcon('remove red', false, 'title="Inactivo/Anulado!"'); return $.createIcon('lock orange', false, 'title="Contiene Pagos!"'); }, icon: 'trash', type: 'danger', title: 'Anular Venta', data: function (o) { return o; } }, resizable: false
            }
            /* {
                 name: 'delete', label: $.createIcon('remove'), width: 3, align: 'center', viewable: false,
                 formatter: 'gridButton',
                 formatoptions: {
                     action: delFila,
                     conditional: function (o) {
                         // Si Detalle !== 'S' y Vet_Est === 'A' y no está bloqueado por SRI
                         if ((o.Tic_Cod === '4' || o.Tic_Cod === '5') && String(o.Vet_Aut).toUpperCase() === 'S') {
                             return false;
                         }
                         return o.Detalle !== 'S' && o.Vet_Est === 'A';
                     },
                     caseFalse: function (o) {
                         if ((o.Tic_Cod === '4' || o.Tic_Cod === '5') && String(o.Vet_Aut).toUpperCase() === 'S') {
                             return $.createIcon('lock orange', false, 'title="Bloqueado por Normativa SRI, solo se puede anular este documento hasta el 10 del siguiente mes."');
                         }
                         if (o.Vet_Est !== "A") {
                             return $.createIcon('remove red', false, 'title="Inactivo/Anulado!"');
                         }
                         return $.createIcon('lock orange', false, 'title="Contiene Pagos!"');
                     },
                     icon: 'trash', type: 'danger', title: 'Anular Venta',
                     data: function (o) { return o; }
                 },
                 resizable: false
             }*/
        ],
        /*loadComplete: function (data) {
            if ($.varValid(data.rows)) {
                for (var i = 0, z = data.rows.length; i < z; i++) {
                    console.log(data.rows[i]);
                    if (data.rows[i]['Vet_Est'] === 'I') { $("#" + data.rows[i].Vet_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2'); }
                    if (data.rows[i]['Detalle'] === 'S') { $("#" + data.rows[i].Vet_Cod + ' td:not(.jqgrid-rownum)').addClass('cellGreen2'); }
                }
            }

        }*/
    }, false, '#tableVentasPager', {});

    $('.search_pec[name=Pec_Cod]').on('change', function () {
        $('#frm_venta').find('[name=fecha_inicio]').val($(this).find('option:selected').data('inicio'));
        $('#frm_venta').find('[name=fecha_fin]').val($(this).find('option:selected').data('fin'));

    });
    $("#rad_ba3").on("click", function () { $('#Pec_Cod').attr('disabled', 'disabled'); $('#Cmb_Mes').attr('disabled', 'disabled'); });
    $("#rad_ba1").on("click", function () { $('#Pec_Cod').removeAttr('disabled'); $('#Cmb_Mes').removeAttr('disabled'); });
    $("#rad_ba2").on("click", function () { $('#Pec_Cod').removeAttr('disabled'); $('#Cmb_Mes').removeAttr('disabled'); });

});

function delFila(row) {
    // validador para no eliminar factura de consumidor final segun normativa de SRI de Agosto 2025
    // if (
    //     (String(row['Ruc']) === '9999999999' || String(row['Ruc']) === '9999999999001') &&
    //     String(row['Vet_Aut']).toUpperCase() === 'S' &&
    //     String(row['Tic_Cod']) === '1'
    // ) {
    //     $.alert('<b style="color:red">Bloqueado por Normativa SRI</b>, no se puede anular una factura con cliente <u>Consumidor Final</u>.');
    //     return;
    // }

    // // Validar si solo se puede anular hasta el 10 del siguiente mes
    // if (
    //     (row['Tic_Cod'] === '4' || row['Tic_Cod'] === '5') &&
    //     String(row['Vet_Aut']).toUpperCase() === 'S'
    // ) {
    //     // Obtener fecha de la venta
    //     var fechaVenta = row['Caj_Fec'];
    //     // Asumimos formato yyyy-mm-dd
    //     var partes = fechaVenta.split('-');
    //     var anio = parseInt(partes[0], 10);
    //     var mes = parseInt(partes[1], 10);
    //     var dia = parseInt(partes[2], 10);

    //     // Calcular fecha límite: 10 del siguiente mes
    //     var fechaLimite = new Date(anio, mes, 10); // mes+1 porque Date usa 0-index
    //     // Obtener fecha actual
    //     var hoy = new Date();

    //     // Si la fecha actual es mayor al límite, bloquear
    //     if (hoy > fechaLimite) {
    //         $.alert('<b style="color:red">Bloqueado por Normativa SRI</b>, solo se puede anular este documento hasta el 10 del siguiente mes.');
    //         return;
    //     }
    // }

    //Validar el ruc 999999  que si tiene esta repeticion sea considerado como consumidor final

    if ((String(row['Ruc']) === '9999999999' || String(row['Ruc']) === '9999999999001') && String(row['Vet_Aut']).toUpperCase() === 'S' && String(row['Tic_Cod']) === '1') {
        $.alert('<b style="color:red">Bloqueado por Normativa SRI</b>, no se puede anular una factura con cliente <u>Consumidor Final</u>.');
       // return;
    }



    if (String(row['Vet_Aut']).toUpperCase() === 'S') {
        var fechaVenta = row['Caj_Fec']; // Obtener fecha de la venta
        var partes = fechaVenta.split('-');
        var anio = parseInt(partes[0], 10);
        var mes = parseInt(partes[1], 10) - 1;
        // Fecha límite: 7 del mes siguiente, incluir el día 7
        var fechaLimite = new Date(anio, mes + 1, 7, 23, 59, 59, 999); // hasta el final del día 7
        var hoy = new Date(); // Obtener fecha actual
        if (hoy > fechaLimite) { // Si la fecha actual es mayor al límite, bloquear
            $.alert('<b style="color:red">Bloqueado por Normativa SRI</b>, La normativa del SRI Resolución NAC-DGERCGC25-00000017, establece que los documentos solo pueden ser anulados hasta el dia 07 del mes siguiente a su emisión.');
           // return;
        }
    }




    $.createDialogConfirm('<b style="color:red"> Por favor, tenga en cuenta que es necesario anular la factura en el (SRI) antes de proceder con la anulación en nuestro sistema.</b> Una vez completada esta transacción, los cambios no podrán ser revertidos a menos que se comunique con el administrador.<br/> <strong> ¿Desea continuar?</strong>', null, function () {

        // $.createDialogConfirm('Una vez realizado la transacci&oacute;n no se podra revertir los cambios<br/> <strong>¿Desea continuar?</strong>', null, function () {
        //console.log(row);

        $.saveDataJson("", { upVenta: true, Vet_Cod: row['VetCod'], Com_Cod: row['Com_Cod2'] }, function (responce) {
            if (responce['success']) {
                $('#tableVentas').changeRow(row['Vet_Cod'], { Vet_Est: 'I', actDel: '' });
                $('#tableVentas').trigger("reloadGrid");
                $.alert('La transacci&oacute;n se realiz&oacute; con &eacute;xito');
                return false;
            }
        });
        //$("#tableVentas").jqGrid('delRowData', row.id);
    });

}
