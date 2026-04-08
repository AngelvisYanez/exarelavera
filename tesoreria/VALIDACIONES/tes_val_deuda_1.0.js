/* 
 * author: Asael Tello 06-09-2017
 */
    var rowData = [];
            
    /*=====================
     * LLENA TABLA DE DATOS
     ======================*/
    function fetchAllData()
    {
        /*
         * Fetch the Table with all data
         */
        $.getDataJson('',{searchAll:true},
            function(res){
                $('#tableResult').setRows(res.rows);
            },
            function(err){});
    }
    
    function getCliente(currentRow)
    {
        $('#Cli_Cod').val(currentRow.Cli_Cod);
        $('#clienteDialog').dialog('close');
        $('#cliente').val(currentRow.cliente); 
    }
        
    $(function(){
        
        $("#tabsSearch").createTabs();
        $('#Caj_Fec').createDatePickers(new Date());
        $("#modalDeuda").createDialog({width:500,height:320,icon:'pencil'});
        $.createSearchDialog('clienteDialog',
        [
            { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15,align:"center",hidden:true },                                
            { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Cliente', name: 'cliente', width: 100},
            { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:getCliente} }
        ],null,null,null,{headertitles:true},{ title:'Cliente', text:'Prs_Ced' });
        fetchAllData();     
        
        
        
        /*=========================
         * GUARDAR CAJA O MODIFICAR
         ==========================*/
        $('#btnAccion').on("click", function()
        {
            if ( $('#cliente').val() !== "" && $('#Caj_Fec').val() !== "" && $('#Pld_Cod').val() !== "0" && $('#Caj_Exi').val() !=="")
            {
                if (flag_guardar === 0) //guarda
                {

                    $.saveDataJson('',{fecha:$('#Caj_Fec').val(), validaCaja:true},function(res){
                       if (res.success === true) 
                       {
                           var data = $('#frmModDeuda').getData('save');
                           guardar(data);
                       }
                       else
                       {
                           $.alert("Ya existe un registro con el mismo punto y fecha");
                       }
                       return false;
                    },function(res){
						if(typeof res['Caj_Cod']!=='undefined'){
							var data = $('#frmModDeuda').getData('save');
                            guardar(data);
							data['Caj_Cod']=res['Caj_Cod'];
							return false;
						}
					});

                }
                if (flag_guardar === 1)//modifica
                {
                    if ($('#Caj_Fec').val() !== rowData.Com_Fec) // valida fecha si es diferente
                    {
                        $.saveDataJson('', {fecha:$('#Caj_Fec').val(), validaCaja:true},function(res)
                        {
                            if (res.success === true)
                            {
                                var dataMod = $.extend({data:$('#frmModDeuda').getData()}, {modify:true}, {row:rowData});
                                guardar(dataMod);
                            }
                            else
                            {
                               $.alert("Ya existe un registro con el mismo punto y fecha");
                            }
                            return false;
                        });
                    }
                    else // si es la misma fecha modifica igual
                    {
                        var dataMod = $.extend({data:$('#frmModDeuda').getData()}, {modify:true}, {row:rowData});
                        guardar(dataMod);
                    }
                }
            }
            else
            {
                $.alert("Los campos con * son necesarios");
            }
        });
        
        function guardar(data)
        {
            $.createDialogConfirm("Est&aacute; seguro de realizar esta acci&oacute;n",data,function(){
                $.saveDataJson('',data,function(res){
                    $("#modalDeuda").dialog('close');
                    fetchAllData();
                });
            });
        }
        
        /*===========================
         * CREA TABLA DE BUSQUEDA
         ============================*/
        $("#tableResult").createGrid({
            height: 295, footerrow:true,
            colModel: [
                {label: 'C&oacute;d. Int.', name: 'Com_Cod', width: 10, align: "center",key:true},
                {label: 'Cliente', name: 'cliente', width: 50, align: "left"},
                {label: 'Fecha', name: 'Com_Fec', width: 50, align: "center"},                
                {label: 'Valor', name: 'Com_Val', width: 50, align: "right", summaryRound:2, formatter: 'currency'},                
                {label: 'Modificar', name: 'act3', width: 10, align: 'center', viewable: false,
                    formatter:function(cellvalue, options, rowObject)
                    {
                        return $.getGridButton(modificar, rowObject, 'Modificar', 'pencil');
                    }
                },
                {label: 'Eliminar', name: 'act2', width: 10, align: 'center', viewable: false,
                    formatter:'gridButton',formatoptions:{action:eliminar,icon:'remove',type:'danger', conditional:function(o){  return o.cont*1===0; }, caseFalse:'<i class="glyphicon glyphicon-lock orange"></i>'}
                    
                },
            ],loadComplete :function(){$(this).setGridSummary(['Com_Val'],{Com_Fec:'TOTAL:'});}
        }, true, "#tableResultPager",{}); //false -> paginacion | true -> una sola page        
        
        /*============================
         * EVENTO AL CERRAR EL DIALOG
         *  - limpia todos los checks
         *  - bandera para los checks
         =============================*/
        $("#modalDeuda").on("dialogclose", function(event, ui){
            clearForm();
        });
        
        /*===========================
        * On Click nueva autorizacion
        =============================*/
       $('#btnNueva').on("click", function(){
            flag_guardar = 0;
            $('#modalDeuda').dialog("option","title","Nuevo");
            $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Guardar");
            $('#modalDeuda').dialog('open');        
            $('#Tia_Cod option').filter(function(){
                if ( $(this).html() === "INGRESO")
                {
                    return $(this);
                }
            }).prop("selected",true);
       });

    
        function clearForm()
        {
            $('#Cli_Cod').val("");
            $('#cliente').val("");
            $('#Pld_Cod').val(0);
            $('#Caj_Exi').val("");
            $('#Vet_Obs').val("");
        }
           
    });
   
    /*
     * Modificar Usuario
     */
    function modificar(row)
    {
        flag_guardar = 1;
        $('#modalDeuda').dialog("option","title","Modificar");
        $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Modificar");
        $('#modalDeuda').dialog('open');
        $('#Pec_Cod').val(row.Pec_Cod);
        $('#Pld_Cod').val(row.Pld_Cod);
        $('#Caj_Fec').val(row.Com_Fec);
        $('#Cli_Cod').val(row.Cli_Cod);
        $('#cliente').val(row.cliente);
        $('#Caj_Exi').val(row.Com_Val);
        $('#Tia_Cod').val(row.Tia_Cod);
        $('#Vet_Obs').val(row.Vet_Obs);
        rowData = row;
    }
    
    function eliminar(row)
    {
        $.createDialogConfirm("Est&aacute; seguro de realizar esta acci&oacute;n ?",row,function()
        {
            var datos = $.extend({data: row}, {eliminar:true});
            $.saveDataJson('',datos,function(res){                    
                if (res.success === true)
                {
                    fetchAllData();
                }
            });
        });
    }