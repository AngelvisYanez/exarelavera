$(function(){
    
    /*************************************
    * ONLOAD PAGINA
    **************************************/
    function inicio()
    {   
        $('#Caj_Fec').createDatePickers();
        $('#Caj_Fec').dateLimits(null,new Date());
        $('#Caj_Fec_d').createDatePickers();
        $('#Caj_Fec_h').createDatePickers();
    }	

    window.onload = inicio;       
    var flag_validacion = 0;
    /**
     * GUARDAR CAJA
     */
    $('#frm_alt_caja').on("submit",function(e){
        e.preventDefault();
        if (flag_validacion == 0)
        {
            if ($('#Caj_Exi').val() != "")
            {
                $.saveDataJson('',$.extend($('#frm_alt_caja').getData('guardaCaja'),$('#cmbUser option:selected').data()),function(re){
                    $('#Caj_Exi').val("0");
                    $('#Caj_Obs').val("");                    
                });
            }
            else
            {
                $.alert("Asignar dinero");
            }
        }
        else
        {
            $.alert("Fecha escogida ya ha sido asignada");
        }
    });
    
    /*
     * Validacion Fecha
     */
    $('#Caj_Fec').on("change",function(e){         
        $.getDataJson('',{searchValidacion:true, Pun_Cod: $('#cmbUser option:selected').data('Pun_Cod'), Caj_Fec:$('#Caj_Fec').val()},function(response){
            flag_validacion = 0;
            $('#Caj_Exi').focus();
        },function(f){
            flag_validacion = 1;
        })
    });
    
    /*
     * Busqueda con Filtros
     */
    $('#btnBuscar').on("click",function(e){
        e.preventDefault();
        $.getDataJson('',{searchFiltro:true, Pun_Cod: $('#cmbUserB option:selected').data('Pun_Cod'), desde:$('#Caj_Fec_d').val(), hasta:$('#Caj_Fec_h').val()},function(res){
            $("#tableResult").setRows(res['rows']);
        },function(f){            
        });
    });   
    
    $( "#tabsSearch" ).createTabs();
    //Inicio Grid para presentar la busqueda
    $("#tableResult").createGrid({
        height: 295,
        colModel: [
            {label: 'C&oacute;d. Int.', name: 'Caj_Cod', width: 50, align: "center",key:true},
            {label: 'Apertura', name: 'apertura', width: 100, align: "center"},
            {label: 'Cierre', name: 'cierre', width: 100, align: "center"},
            {label: 'Monto Inicial', name: 'Caj_Exi', width: 100, align: "center"},
            {label: 'Observacion', name: 'Caj_Obs', width: 150, align: "left"},
            {label: 'Estado', name: 'Caj_Est', width: 50, align: "center"},
            {label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
               formatter:function(cellvalue, options, rowObject){
                   if(rowObject.Caj_Est==='Activo')
                        return $.getGridButton(cerrarCaja, rowObject, 'Cerrar Caja','fa fa-unlock');
                    else
                        return '<i class="fa fa-lock orange"> </i>';
              }
            }
        ]
    }, true, "#tableResultPager",{}); //false -> paginacion | true -> una sola page
});
    /*
     * Cierra Caja
     */
    function cerrarCaja (row)
    {
        $.createDialogConfirm("Esta seguro de realizar esta <b>acci&oacute;n</b> ?",row,function(){
            $.getDataJson('',{closeCaja:true, Caj_Cod: row.Caj_Cod},function(response){            
            $('#tableResult').changeRow(response['fila']['Caj_Cod'],response['fila']);
            },function(f){            
            })
        },"");        
    }