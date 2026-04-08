$(function(){    
    
    /*************************************
    * ONLOAD PAGINA
    **************************************/
    function inicio()
    {
        $("#txt_fec_ini").createDatePickers();
        $("#txt_fec_fin").createDatePickers();

        $( "#verDetalleNota" ).createDialog({width:700,height:435,icon:'info-sign'});
        $("#tabs_abo_det").tabs();
        $("#verDetalleNota").hide();

        $('#showProductosNota').createGrid({viewrecords:false,
        data:[], rowNum: 100, height: 250, width: 650, responsive:false,
        footerrow: true,
        userDataOnFooter: true,
        onSelectRow: function(rowid, e) { $(this).resetSelection();},
        colModel:[
          { label: 'index', name: 'index',hidden:true, classes:'bgNoRight' },
          { label: 'Codigo', name: 'Pld_Cod', width: 10, align:"left"},
          { label: 'Cuenta', name: 'Pld_Des', width: 40, align:"left"},
          { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter:'currency', editable:true,
            formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},
            summaryTpl: "Sum: {0}",
            summaryType: "sum"
          },
          { label: 'Haber.', name: 'Haber', width: 10, align: 'right', formatter:'currency', editable:true,
            formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},
            summaryTpl: "Sum: {0}",
            summaryType: "sum"
          }
        ],
        loadComplete: function(){
            var debe=  $(this).jqGrid('getCol', 'Debe', false, 'sum');
            $(this).jqGrid('footerData', 'set', {Debe: debe});
            var haber=  $(this).jqGrid('getCol', 'Haber', false, 'sum');
            $(this).jqGrid('footerData', 'set', {Haber: haber});
        }
        },true,'',{view:false});

    }	

    window.onload = inicio;
    $('#txt_fec_ini').on("change",function(){
        var d = $("#txt_fec_ini").datepicker("getDate");
    }); 

    $('#txt_fec_fin').on("change",function(){
        var d = $("#txt_fec_fin").datepicker("getDate");
    });


    $('#btnDelete').on("click",function(){
        var ini = $('#txt_fec_ini').val();
        var fin = $('#txt_fec_fin').val();
        
        $.post( "",{deleteComprobantes:true, Fec_Ini:ini, Fec_Fin:fin}, function(response) 
        {
            const respuesta = JSON.parse(response);
            $.alert(respuesta.message);
            
            if(respuesta.error){
                console.log(respuesta.error);
            }
            else{
                $('#tableResult').jqGrid('clearGridData');
                $('#tableResult').jqGrid('footerData', 'set', {Com_Val: ''});
            }
                                              
        }).fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });
    });

    $("#tableResult").createGrid({
        caption : 'Comprobantes de ventas contabilizadas',
        autowidth : true, 
        shrinkToFit: true, 
        height: 270,
        responsive:true,
        footerrow: true,
        userDataOnFooter: true,
        autoheight: true,
        colModel: [
            {label: 'C&oacute;d.', name: 'Com_Cod', width: 20, align: "center",key:true},
            {label: 'Gen.', name: 'Com_Gen', width: 20, align: "center"},
            {label: 'Comp.', name: 'Tipo', width: 30, align: "center"},
            {label: 'Asiento', name: 'Codigo', width: 30, align: "center"},
            {label: 'Fecha', name: 'Com_Fec', width: 30, align: "left"},
            {label: 'Concepto', name: 'Com_Con', width: 80, align: "center"},
            {label: 'Doc.', name: 'Doc', width: 30, align: "left"},
            {label: 'Doc. Fec', name: 'Doc_Fec', width: 30, align: "left"},
            {label: 'Doc. Num', name: 'Doc_Num', width: 40, align: "left"},
            {label: 'Diferencia', name: 'Diferencia', width: 30, align: 'right', hidden:true, formatter: 'currency',
                formatoptions:{prefix: '$ ',thousandsSeparator: ',',decimalSeparator: '.', defaultValue: ''},
                summaryTpl: "Sum: {0}",
                summaryType: "sum"
            },
            {label: 'Valor', name: 'Com_Val', width: 30, align: 'right',formatter: 'currency',
                formatoptions:{prefix: '$ ',thousandsSeparator: ',',decimalSeparator: '.', defaultValue: ''},
                summaryTpl: "Sum: {0}",
                summaryType: "sum"
            },
            {label: 'Est.', name: 'Com_Est', width: 20, align: "center"},
            {label:'<center><i class="ui-icon ui-icon-info"></i></center>', name: 'btn_detalle', width: 15, align: 'center',viewable: false,
                formatter:function (cellvalue, options, rowObject) {
                  return  $.getGridButton(verDetalle, rowObject, 'Ver Detalle', 'info-sign','','info')+"&nbsp;";
                }
            },
            {label:'<center><i class="ui-icon ui-icon-trash"></i></center>', name: 'btn_eliminar', width: 15, align: 'center',viewable: false,
                formatter:function (cellvalue, options, rowObject) {
                  return  $.getGridButton(eliminarComp, rowObject, 'Eliminar', 'remove-sign','','danger', 'xs')+"&nbsp;";
                }
            }
        ],
        loadComplete: function(data){
            var parseTotal=  $(this).jqGrid('getCol', 'Com_Val', false, 'sum');
            $(this).jqGrid('footerData', 'set', {Com_Val: parseTotal});

            if($.varValid(data.rows))
                for(var i=0,z=data.rows.length;i<z;i++){
                    if(data.rows[i]['Diferencia'] != 0) $("#"+data.rows[i].Com_Cod+' td:not(.jqgrid-rownum)').addClass('cellRed2');
                }
        }

    }, true, "#tableResultPager",{}); 

});