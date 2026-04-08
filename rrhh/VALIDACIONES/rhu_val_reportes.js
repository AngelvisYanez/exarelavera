$(function() {


	//Inicio Grid
    $("#Lis_Cli").createGrid({
        //postData: $("#frm_bus").getData("clientesAjax"), 
        height: 450,
        colModel: [
            {label: 'Tipo Doc.', name: 'tipo_documento', width: 20, align: "left"},
            {label: 'Num.Identificación', name: 'identificacion', width: 25, align: "left",cellattr:function(){return 'style="'+excelFormats.text+'"';}},
            {label: 'Beneficiario', name: 'beneficiario', width: 150, align: "left"},
            {label: 'Concepto', name: 'concepto', width: 30, align: "left"},
            {label: 'Forma Pag/Cob', name: 'forma_pago', width: 30, align: "left"},
            {label: 'Banco', name: 'banco', width: 20, align: "left"},
            {label: 'Tipo Cta/Che', name: 'tipo_cuenta', width: 20, align: "left"},
            {label: 'Num.Cta/Che', name: 'numero_cuenta', width: 40, align: "left",cellattr:function(){return 'style="'+excelFormats.text+'"';}},
            {label: 'Valor', name: 'valor', width: 40, align: "left"},
            {label: 'Submotivo', name: 'submotivo', width: 30, align: "left"}
            //{label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
               // formatter:function(cellvalue, options, rowObject){
                    //return $.getGridButton(cargarCliente, rowObject, 'Editar Cliente');
                //}
            //}
        ]
    }, true, "#Pag_Cli");


    getRoles();

    var opcionSeleccionada = $('#Ban_Cod').find("option:selected");
    var valorDataPla = opcionSeleccionada.data("pla");
    $("#Pld_Cod").val(valorDataPla);
    var valorDataCue = opcionSeleccionada.data("cue");
    $("#Ban_Cue").val(valorDataCue);

    $("#Ban_Cod").change(function() {
        var opcionSeleccionada = $(this).find("option:selected");
        var valorDataPla = opcionSeleccionada.data("pla");
        var valorDataCue = opcionSeleccionada.data("cue");
        $("#Pld_Cod").val(valorDataPla);
        $("#Ban_Cue").val(valorDataCue);
    });


});


function getRoles(){
    $.post("",{'rolesAjax':true,'Are_Cod':$("#Are_Cod").val(),'Map_Cod':$("#Map_Cod").val(),'Pec_Cod':$("#Pec_Cod").val(),'Rol_Tip':$("#Rol_Tip").val(),'Month':$("#Month").val(),'Rol_S':$("#Rol_S").val()},function(responce){
        if(responce['success']===true){
            $("#Rol_Cod").empty();

            var selectElement = $("#Rol_Cod");
            var todas = $("<option>", {value: 'ALL',text:'TODOS'});
            selectElement.append(todas);
            var rolesWhere = '(';
            $.each(responce['rows'] , function(index, elemento){
                var nuevaOpcion1 = $("<option>", {value: elemento['Rol_Cod'],text: 'Rol #' + elemento['Rol_Num'] + ' Fecha:' + elemento['Rol_Fei'] +  ' - ' + elemento['Rol_Fef'] + ' Tipo:' + elemento['Rol_Tip'] + ' Area:' + elemento['Are_Des'] });
                selectElement.append(nuevaOpcion1);
                rolesWhere = rolesWhere + elemento['Rol_Cod'] + ',';
            });
            if (rolesWhere.endsWith(',')) {
                  rolesWhere = rolesWhere.slice(0, -1);
            }
            rolesWhere = rolesWhere + ') ';
            $("#Roles_Where").val(rolesWhere);
        }
    },'json');
}

function exportar(){
    $('#tablaExporta').html($('#Lis_Cli').jqGrid('exportGridInnerHTML',{footer:true,bodyBorder:false,removeHiddens:true,removeCols:[0]}));
    $.downloadFile($.exportarExcelBlob($('#exportar').html(), 'Costos'), 'reporteBanco_' + $.getDate() + '.xls');
}

function isoWeeks(year){ var d; for(var i=31;i>=0;i--){ d=moment(year+'-12-'+i); if(d.isoWeeks()>10) break; } return d; };

function fillWeeks(){
    var anio=$('#Pec_Cod option:selected').data('year'), 
    semanas=semanas=($.vv(anio)?isoWeeks(anio).isoWeeks():52);

    var option = $('<option>', {
            value: 0,
            text: 'Seleccione... '
        });
        
    $('#Rol_S').append(option);

    for (var i = 1; i <= semanas; i++) {
        var option = $('<option>', {
            value: i,
            text: 'Semana ' + i
        });
        
    $('#Rol_S').append(option);
    }
}
    