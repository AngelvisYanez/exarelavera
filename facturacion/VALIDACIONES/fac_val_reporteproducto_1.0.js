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
        $.getDataJson('',{searchAll:true, desde:"0", hasta:"0"},
            function(res){
                $('#tableResult').setRows(res.rows);
            },
            function(err){});
    }
    
    function printR(grid) {
                $('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false}));
                $('#titleReporte').html($(grid).getCaption());
                $('#formatoReporte').printElement({pageTitle:"",overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});                
            }
            function exportR(grid) {
                var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
                temp.append($(grid).jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true}));                
                $.downloadFile($.exportarExcelBlob(temp.html(),'Digitacion'),'digitacion_'+$.getDate()+'.xls');    
            }
   
        
    $(function(){
        
        $("#tabsSearch").createTabs();
        $('#desde').createDatePickers(new Date());
        $('#hasta').createDatePickers(new Date());
        /*===========================
         * CREA TABLA DE BUSQUEDA
         ============================*/
        $("#tableResult").createGrid({
            height: 295,footerrow:true,
            colModel: [
                {label: 'C&oacute;d. Producto', name: 'Pro_Cod', width: 10, align: "center",key:true},
                {label: 'Descripci&oacute;n', name: 'Ite_Lar', width: 50, align: "left"},
                {label: 'Cantidad', name: 'Cant', width: 10, align: "right"},
                {label: 'Precio Promedio', name: 'Pro_Prp', width: 10, align: "right"},                
                //{label: 'Stock', name: 'Stk_Can', width: 10, align: "right",},
                {label: 'Importe', name: 'mult', width: 10, align: "right",formatter:'number'},                
            ], loadComplete: function (){
                $(this).setGridSummary(['mult'],{Stk_Can:'TOTAL:'});
            }
        }, true, "#tableResultPager",{}).gridButtonsAdd([
	{buttonicon:'print',caption:'Imprimir',onClickButton:function(){ printR('#tableResult'); }},
	{buttonicon:'download-alt',caption:'Descargar',onClickButton:function(){ exportR('#tableResult'); }}
]); ; //false -> paginacion | true -> una sola page        
        
        /*===========================
        * On Click Reporte
        =============================*/
       $('#btnReporte').on("click", function(){
            
       });
        
        $('#btnSearch').on("click",function()
        {
           var d = $('#desde').val();
           var h = $('#hasta').val();
           $.getDataJson('',{searchAll:true, desde:d, hasta:h},
                function(res){
                    $('#tableResult').setRows(res.rows);
                    return false;
                },
                function(err){});
        });
        
        
    });