$(function () {
    $('#tableVentas').createGrid({
        caption: 'Ventas',
        datatype: "local",
        height: 430,
        stateConfig: { Inactivo: 'cellRed2', Detalle: 'cellGreen2' }, stateCondition: function (row) { if (row['Detalle'] === 'S') return "Detalle"; if (row['Vet_Est'] == 'I') return 'Inactivo'; if (row['Vet_Est'] == 'U') return 'Detalle';},
        leyenda: [{ icon: 'stop green', label: 'Contiene Pagos' }, { icon: 'lock orange', label: 'Contiene Pagos' }, { icon: 'remove red', label: 'Anulados/Inactivos' }],
        colModel: [
            { label: 'C&oacute;d. Int.', name: 'Vet_Cod', key: true, width: 5, align: "center", hidden: false, },
            { label: 'Fecha', name: 'Vet_Fec', width: 5, align: "center", hidden: false },
            { label: 'CI/RUC', name: 'Prs_Ced', width: 5, align: "center", hidden: false },
            { name: 'Cli_Cod', width: 20, align: "center", hidden: true },
            { name: 'Vet_Est', width: 20, align: "center", hidden: true },
            { name: 'Caj_Cod', width: 20, align: "center", hidden: true },
            { name: 'Vnd_Cod', width: 20, align: "center", hidden: true },
            { name: 'Tpc_Cod', width: 20, align: "center", hidden: true },
            { name: 'Ide_Cod', width: 20, align: "center", hidden: true },
            { name: 'Tic_Cod', width: 20, align: "center", hidden: true },
            { label: 'Cliente', name: 'cliente_per', width: 15, align: "left", hidden: false },
            { label: 'Tipo Documento', name: 'Tic_Des', width: 5, align: "center", hidden: false },
            { label: 'No. Documento', name: 'Vet_Num', width: 5, align: "center", hidden: false },
            {
                name: 'delete', label: $.createIcon('remove'), width: 3, align: 'center', viewable: false, formatter: 'gridButton',
                formatoptions: { action: inactivarDoc, conditional: 
                    function (o) { 
                        return o.Detalle !== 'S' && o.Vet_Est === 'A'; }, 
                        caseFalse: 
                        function (o) { 
                            if (o.Vet_Est == "I") {
                                return $.createIcon('remove red', false, 'title="Inactivo/Anulado!"'); 
                            }else if (o.Vet_Est == "U") {
                                return $.createIcon('lock orange', false, 'title="Se encuentra facturada!"'); 
                            }
                            
                        }, icon: 'trash', type: 'danger', title: 'Anular Venta', 
                        data: function (o) { 
                            return o; 
                        } }, resizable: false
            }

        ],

    }, false, '#tableVentasPager', {});

    $('.search_pec[name=Pec_Cod]').on('change', function () {
        $('#frm_venta').find('[name=fecha_inicio]').val($(this).find('option:selected').data('inicio'));
        $('#frm_venta').find('[name=fecha_fin]').val($(this).find('option:selected').data('fin'));

    });
    $("#rad_ba3").on("click", function () { $('#Pec_Cod').attr('disabled', 'disabled'); $('#Cmb_Mes').attr('disabled', 'disabled'); });
    $("#rad_ba1").on("click", function () { $('#Pec_Cod').removeAttr('disabled'); $('#Cmb_Mes').removeAttr('disabled'); });
    $("#rad_ba2").on("click", function () { $('#Pec_Cod').removeAttr('disabled'); $('#Cmb_Mes').removeAttr('disabled'); });

});

function inactivarDoc(fila){
    $.getDataJson('',{inactivar:true, Vet_Cod:fila.Vet_Cod, Tar_Validar:true},
        function(response){ 
            if (response['success']) {
                 $('#tableVentas').trigger('reloadGrid');
                 $.alert('La transacci&oacute;n se realiz&oacute; con &eacute;xito.');
                 return false;
            }else{
                $.alert('No se logr&oacute; realizar la transacci&oacute;n.');
            }
            
        });
}