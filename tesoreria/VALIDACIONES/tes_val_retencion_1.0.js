/* 
 * author: Asael Tello 04-10-2017
 */
            
    /*=====================
     * LLENA TABLA DE DATOS
     ======================*/
    function fetchAllData()
    {
        /*
         * Fetch the Table with all data
         */        
        $.getDataJson('',{searchAll:true, filtro:"0"},
            function(res){
                $('#tableResult').setRows(res.rows);
            },
            function(err){});
    }
    
    function printR(grid) 
    {
        $('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false, removeHiddens:true,removeCols:[6]}));
        $('#titleReporte').html($(grid).getCaption());
        $('#formatoReporte').printElement({pageTitle:" ",overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});
    }
        
    function exportR(grid) 
    {
        var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
        temp.append($(grid).jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true}));                
        $.downloadFile($.exportarExcelBlob(temp.html(),'Retenciones'),'retenciones_'+$.getDate()+'.xls');    
    }
    
    function ver(row)
    {
        $('#modalRetencion').dialog("option","title","Información");
        $("#modalRetencion").dialog('open');
        $('#vendedor').html("</b>Vendedor: </b>"+row.vendedor);
    }

    $(function(){
        
        $("#tabsSearch").createTabs();
        $("#modalRetencion").createDialog({width:300,height:100,icon:'info-sign'});
        $('#numero').fadeOut('fast');        
        fetchAllData();
        /*===========================
         * CREA TABLA DE BUSQUEDA
         ============================*/
        $("#tableResult").createGrid({
            height: 295,
            colModel: [
                {label: 'C&oacute;d. Int.', name: 'Ret_Cod', width: 10, align: "center",key:true},
                {label: 'Numero Ret.', name: 'Ret_Num', width: 10, align: "center"},
                {label: 'Fecha', name: 'Ret_Fec', width: 10, align: "center"},
                {label: 'Proveedor', name: 'Prv_Com', width: 50, align: "left"},                
                {label: 'Estado', name: 'Ret_Est', width: 10, align: "center",},
                {label: 'Informaci&oacute;n', name: 'act3', width: 10, align: 'center', viewable: false,
                    formatter:function(cellvalue, options, rowObject)
                    {
                       //return '<i class="glyphicon glyphicon-info-sign blue" title= "'+rowObject.vendedor+'"></i>';
                       return $.getGridButton(ver, rowObject, 'Info Vendedor', 'info-sign');
                    }
                },
            ], loadComplete: function (data){
                    if((data.rows.length > 0))
                    {
                        for(var i=0,z=data.rows.length;i<z;i++)
                        {            
                            if(data.rows[i]['Ret_Est'] ==='Inactivo') $("#tableResult tr#"+data.rows[i].Ret_Cod+' td:not(.jqgrid-rownum)').addClass('cellRed2');                            
                        }
                    }
            }
        }, true, "#tableResultPager",{}).gridButtonsAdd([
	{buttonicon:'print',caption:'Imprimir',onClickButton:function(){ printR('#tableResult'); }},
	{buttonicon:'download-alt',caption:'Descargar',onClickButton:function(){ exportR('#tableResult'); }}
        ]);; //false -> paginacion | true -> una sola page
        
        $('#btnSearch').on("click",function()
        {
           var d = $('#Ret_Est').val();           
           $.getDataJson('',{searchAll:true, filtro:d, numero:$('#Ret_Num').val()},
                function(res){
                    $('#tableResult').setRows(res.rows);
                    return false;
                },
                function(err){});
        });
        
        $('#Ret_Est').on("change", function()
        {
           if ( $(this).val() === "N")           
           {
               $('#numero').fadeIn('fast');
               $('#Ret_Num').focus();
           }
           else
           {
               $('#numero').fadeOut('fast');
               $('#Ret_Num').val("");
           }
        });
    });