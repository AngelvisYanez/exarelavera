$(function(){    
    
    /*************************************
    * ONLOAD PAGINA
    **************************************/
    function inicio()
    {

        $( "#verDetalleNota" ).createDialog({width:700,height:435,icon:'info-sign'});
        $("#tabs_abo_det").tabs();

        $("#txt_fec_ini").createDatePickers();
        $("#txt_fec_fin").createDatePickers();
        $("#verDetalleNota").hide();
        validarParametros();

        $('#showProductosNota').createGrid({viewrecords:false,
        data:[], rowNum: 100, height: 250, width: 650, footerrow:true,responsive:false,
        onSelectRow: function(rowid, e) { $(this).resetSelection();},
        colModel:[
          { label: 'index', name: 'index',hidden:true, classes:'bgNoRight' },
          { label: 'Descripcion', name: 'Ite_Lar', width: 20, align:"left"},
          { label: 'Cant.', name: 'Vet_Can', width: 5, align: 'right', formatter:'number', editable:true,},
          { label: 'P.V.P', name: 'Vet_Pru', width: 5, align: 'right', formatter:'currency', editable:true,
            formatoptions: {
              prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:'' 
            }
          },
          { label: 'Vta.Total', name: 'Vet_Imp', width: 5, align: 'right', formatter:'currency', editable:true,
            formatoptions: {
              prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
            }

          },
          { label: 'C.U', name: 'Promedio', width: 5, align: 'right', formatter:'currency', editable:true,
            formatoptions: {
              prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
            }
          
          },
          { label: 'Cost.Total', name: 'Costo', width: 5, align: 'right', formatter:'currency', editable:true,
            formatoptions: {
              prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
            }
          },
          { label: 'Util.', name: 'Utilidad', width: 5, align: 'right', formatter:'currency', editable:true,
            formatoptions: {
              prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
            }
          }
        ]
        },true,'',{view:false});

        $("#tableResult").createGrid({
        caption : 'Detalle de ventas no contabilizadas',
        autowidth : true, 
        shrinkToFit: true, 
        height: 270,
        responsive:true,
        footerrow: true,
        userDataOnFooter: true,
        autoheight: true,
        colModel: [
            {label: 'C&oacute;d.', name: 'Vet_Cod', width: 20, align: "center",key:true},
            {label: 'Tic_Cod', name: 'Tic_Cod', hidden:true, width: 40, align: "center"},
            {label: 'Tipo Documento', name: 'Tic_Des', width: 40, align: "center"},
            {label: 'Nro. Documento', name: 'Vet_Num', width: 40, align: "center"},
            {label: 'Cliente', name: 'Cliente', width: 80, align: "left"},
            {label: 'Fecha', name: 'Caj_Fec', width: 40, align: "center"},
            {label: 'Costo', name: 'Costo', width: 30, align: 'right',formatter: 'currency',
                formatoptions:{prefix: '$ ',thousandsSeparator: ',',decimalSeparator: '.', defaultValue: ''},
                summaryTpl: "Sum: {0}",
                summaryType: "sum"
            },
            {label:'<center><i class="ui-icon ui-icon-info"></i></center>', name: 'btn_detalle', width: 15, align: 'center',viewable: false,
                formatter:function (cellvalue, options, rowObject) {
                  return  $.getGridButton(verDetalle, rowObject, 'Ver Detalle', 'info-sign','','info')+"&nbsp;";
                }
            }
        ],
        loadComplete: function(){
            var parseTotal=  $(this).jqGrid('getCol', 'Costo', false, 'sum');
            $(this).jqGrid('footerData', 'set', {Costo: parseTotal});
        }
        }, true, "#tableResultPager",{}); 

    }	

    window.onload = inicio;
    $('#txt_fec_ini').on("change",function(){
        var d = $("#txt_fec_ini").datepicker("getDate");
    }); 

    $('#txt_fec_fin').on("change",function(){
        var d = $("#txt_fec_fin").datepicker("getDate");
    });

    function validarParametros(){
        $.post( "",{validar:true}, function(response) 
        {
            const respuesta = JSON.parse(response);
            if(respuesta.contar != 0){
                $.alert("Falta parametrizar productos para Compra y Costo en Parametrizacion->Productos->Productos!");
            }

        }).fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });
    }

    $('#btnUpdate').on("click",function(){
        $.post( "",{updateKardex:true}, function(response) 
        {
            const respuesta = JSON.parse(response);
            $.alert(respuesta.message);
            if(respuesta.error){
                console.log(respuesta.error);
            }

        }).fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });
    });

    $('#btnSave').on("click",function(){
        var tipo = $('#tipoCom').val();
        var ini = $('#txt_fec_ini').val();
        var fin = $('#txt_fec_fin').val();
        var fecha = $('#txt_fec_fin').val();

        if(tipo == 'D'){
            $.post( "",{saveComprobantes:true, Fec_Ini:ini, Fec_Fin:fin}, function(response) 
            {
                const respuesta = JSON.parse(response);
                $.alert(respuesta.message);
                
                if(respuesta.error){
                    console.log(respuesta.error);
                }
                else{
                    $('#tableResult').jqGrid('clearGridData');
                    $('#tableResult').jqGrid('footerData', 'set', {Costo: ''});
                }

            }).fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });
        }
        else{
            $.post( "",{saveComprobantesPeriodo:true, Caj_Fec:fecha, Fec_Ini:ini, Fec_Fin:fin}, function(response) 
            {
                const respuesta = JSON.parse(response);
                $.alert(respuesta.message);
                
                if(respuesta.error){
                    console.log(respuesta.error);
                }
                else{
                    $('#tableResult').jqGrid('clearGridData');
                    $('#tableResult').jqGrid('footerData', 'set', {Costo: ''});
                }

            }).fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });

        }

    });


});