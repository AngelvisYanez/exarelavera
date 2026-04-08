$(function(){    
    
    /*************************************
    * ONLOAD PAGINA
    **************************************/
    function inicio()
    {

        $("#txt_fec_ini").createDatePickers();
        $("#txt_fec_fin").createDatePickers();

        $("#tableResult").createGrid({
        caption : 'DETALLE DE COMPROBANTES DE ADQUISICIONES LOCALES E IMPORTACIONES',
        autowidth : true, 
        shrinkToFit: true, 
        height: 270,
        responsive:true,
        footerrow: true,
        userDataOnFooter: true,
        autoheight: true,
        colModel: [
            {label: 'No.', name: 'Cop_Cod', width: 10, align: "center", hidden:true, key:true},
            {label: 'RUC DEL PROVEEDOR', name: 'Prs_Ced', width: 30, align: "left",cellattr:function(){return 'style="'+excelFormats.text+'"';}},
            {label: 'RAZÓN SOCIAL DEL PROVEEDOR', name: 'Proveedor', width: 80, align: "left"},
            {label: 'FECHA DE EMISIÓN (a)', name: 'Cop_Fec', width: 25, align: "center"},
            {label: 'SERIE', name: 'Serie', width: 15, align: "left"},
            {label: 'SECUENCIA (b)', name: 'Secuencia', width: 25, align: "center"},
            {label: 'AUTORIZACIÓN', name: 'Cop_Aut', width: 60, align: "center",cellattr:function(){return 'style="'+excelFormats.text+'"';}},
	    {label: 'CLAVE DE ACCESO FACTURA', name: 'Autorizacion1', width: 60, align: "center",cellattr:function(){return 'style="'+excelFormats.text+'"';}},
            {label: 'BASE IMPONIBLE', name: 'Sub_12', width: 30, align: 'right',formatter: 'currency',
                formatoptions:{prefix: '',thousandsSeparator: ',',decimalSeparator: '.', defaultValue: '0'},
                summaryTpl: "Sum: {0}",
                summaryType: "sum"
            },
            {label: 'IVA', name: 'Iva_Tot', width: 30, align: 'right',formatter: 'currency',
                formatoptions:{prefix: '',thousandsSeparator: ',',decimalSeparator: '.', defaultValue: '0'},
                summaryTpl: "Sum: {0}",
                summaryType: "sum"
            },
            {label: 'CLAVE DE ACCESO DE COMPROBANTES DE RETENCIÓN ELECTRÓNICOS (c)', name: 'Autorizacion1', width: 60, align: "center",cellattr:function(){return 'style="'+excelFormats.text+'"';}},
            {label: 'ESPECIFICAR SI SE TRATA DE ACTIVO FIJO', name: 'Activo', width: 15, align: "center"},
            {label: 'ESPECIFICAR SI SE TRATA DE REEMBOLSO DE GASTOS (4)', name: 'Reembolso', width: 15, align: "center"}
        ],
        loadComplete: function(){
            var parseTotal=  $(this).jqGrid('getCol', 'Sub_12', false, 'sum');
            $(this).jqGrid('footerData', 'set', {Sub_12: parseTotal});

            var parseIva=  $(this).jqGrid('getCol', 'Iva_Tot', false, 'sum');
            $(this).jqGrid('footerData', 'set', {Iva_Tot: parseIva});
        }
        }, true, "#tableResultPager",{}).setGroupHeaders({                                        
                                        groupHeaders: [
                                            { "numberOfColumns": 2, "titleText": "No. DEL COMPROBANTE DE VENTA", "startColumnName": "Serie" }
                                        ],useColSpanStyle: true
                                    }); 

    }	

    window.onload = inicio;

    $('#txt_fec_ini').on("change",function(){
        var d = $("#txt_fec_ini").datepicker("getDate");
    }); 

    $('#txt_fec_fin').on("change",function(){
        var d = $("#txt_fec_fin").datepicker("getDate");
    });
});