/* 
 * author: Asael Tello 06-09-2017
 */
    $(function(){
        
        window.onload = inicio;        
        /*==========
         * Beginning
         ===========*/
        function inicio()
        {
            $("#tabsSearch").createTabs();
            $('#Cch_Fec').createDatePickers(new Date());
            $( "#modalCaja" ).createDialog({width:800,height:550,icon:'pencil'});            
            fetchAllData();
            // Get Pld_Cod for cheque debe
            $.postDataJson('',{getPldCaja:true},function(res){
                Pld_Des = res[0].Pld_Cdc+"  -  "+res[0].Pld_Des;
                $("#Pld_Cod_Des").val(Pld_Des);
                Pld_Cod = res[0].Pld_Cod;
                $("#Pld_Cod").val(Pld_Cod);
				return false;
            });
            
            // Get Pld_Cod for efectivo haber
            $.postDataJson('',{getPldEfectivo:true},function(res){
                var data="";
                $.each(res.row, function(i,item){
                    if (i === 0)
                    {
                        data+= "<option value=0> Seleccionar ... </option>";
                    }
                    data += "<option value= "+item.Pld_Cod+">"+item.Pld_Cdc+"  -  "+item.Pld_Des+"</option>";
                });
                $("#Pld_Efe").html(data);
            });
            //Get Prv_Code
            $.postDataJson('',{getPrv:true},function(res){                
                $("#Prv_Cod").val(res[0].Prv_Cod);
            });
        }

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
					return false;
                },
                function(err){});
        }
        
        /*=========================
         * GUARDAR CAJA O MODIFICAR
         ==========================*/
        $('#btnAccion').on("click", function(){
            if (flag_guardar === 0) //guarda
            {
                var data = $('#frmModCaja').getData('save'); 
                
                if (data.opn === "C") // cheque
                {
                    if ( $('#Cch_Val').val() !== "" && $("#Tia_Cod").val() !== "0" && $("#Che_Ben") !== "" && $("#Ban_Cod").val() !== "0")
                    {
                        guardar(data,"Está seguro ?");
                    }
                    else
                    {
                        $.alert("Los campos marcados con * son requeridos");
                    }
                }
                else //efectivo
                {
                    if ($("#Pld_Efe").val() !== "0" && $("#Tia_Cod").val() !== "0" && $("#Cch_Val").val() !== "")
                    {
                        guardar(data,"Está seguro ?");
                    }
                    else
                    {
                        $.alert("Los campos marcados con * son requeridos");
                    }
                }
            }
            if (flag_guardar === 1)//modifica
            {
                var dataMod = $.extend({data:$('#frmModCaja').getData()}, {modify:true});
                var mensaje = "";
                if (chequeEstado === "C" && dataMod.opn === "E"){mensaje = "Se ha detectado el cheque #"+chequeNumero+" con estado cobrado, si confirma se eliminará el cheque, desea proseguir ?";}else{mensaje = "Está seguro?";}
                guardar(dataMod,mensaje);
            }            
            if (flag_guardar === 3) // validaciones
            {
                $.alert("Escriba un # de cheque válido");
            }
            if (flag_guardar === -1) // validaciones
            {
                $.alert("Escoja Cheque o Efectivo");
            }
        });
        
        function guardar(data,msg)
        {
            $.createDialogConfirm(msg,data,function(){
                $.saveDataJson('',data,function(res){
                    $("#modalCaja").dialog('close');
                    fetchAllData();
                });
            });
        }
        
        /*===========================
         * CREA TABLA DE BUSQUEDA
         ============================*/
        $("#tableResult").createGrid({
            height: 295,
            colModel: [
                {label: 'C&oacute;d. Int.', name: 'Cch_Cod', width: 50, align: "center",key:true},
                {label: 'Fecha', name: 'Cch_Fec', width: 50, align: "left"},
                {label: 'Estado', name: 'Cch_Est', width: 50, align: "center"},
                {label: 'Valor', name: 'Cch_Val', width: 50, align: "right", formatter: 'currency'},                
                {label: 'Creado por', name: 'persona', width: 100, align: "left"},
                {label: 'Observaci&oacute;n', name: 'Cch_Obs', width: 50, align: "center"},
                {label: 'Modificar', name: 'act3', width: 30, align: 'center', viewable: false,
                    formatter:function(cellvalue, options, rowObject){                   
                        return $.getGridButton(modificarUser, rowObject, 'Modificar', 'pencil');
                }
            },
            ]
        }, true, "#tableResultPager",{}); //false -> paginacion | true -> una sola page        
        
        /*============================
         * EVENTO AL CERRAR EL DIALOG
         *  - limpia todos los checks
         *  - bandera para los checks
         =============================*/
        $("#modalCaja").on("dialogclose", function(event, ui){
            clearForm();
        });
        
        /*===========================
        * On Click nueva autorizacion
        =============================*/
       $('#btnNueva').on("click", function(e){
            flag_guardar = 0;
            $('#modalCaja').dialog("option","title","Nuevo");
            $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Guardar");
            $('#modalCaja').dialog('open');
            $("#Pld_Cod_Des").val(Pld_Des);
       });
       
       /*================================
        * Evento Change en ComboBox Banco
        =================================*/
       $('#Ban_Cod').on("change", function(){
            var bancod = $('#Ban_Cod').val().split("*",2);
            if (bancod[0] !== "0")
            {
                $.postDataJson('',{getCheque:true, Ban_Cod:bancod[0]},function(res){
                    if (res[0].Che_Num !== null)
                    {
                        $("#Che_Num").val(res[0].Che_Num);
                    }
                    else
                    {
                        $("#Che_Num").val("1");
                    }
                    $("#Che_Num").focus();
                }, function(err){
                });
            }
       });
       
       /*=======================================
        * Evento Blur en Cheque Numero
        * Valida que el # de cheque no se repita
        ========================================*/    
       $("#Che_Num").on("blur", function(){
           if ($(this).val() !== "")
           {
               if ($(this).val() !== chequeNumero)
               {
                    $.postDataJson('',{validaCheque:true, Ban_Cod:$("#Ban_Cod").val().split("*",2)[0], Che_Num: $(this).val()},
                    function(res)
                    {
                        $("#Che_Num").fieldValid(true);                    
                    },
                    function(err)
                    {                    
                        $("#Che_Num").fieldValid(false,err.message);
                        $("#Che_Num").val("");
                        flag_guardar = 3;
                        return false;
                    });
               }
               else
               {
                    $(this).fieldValid(true);
               }
           }
        });
        
        /*
        *   Click on radio button Efectivo
        */
        $("#opm").on("click", function(){
            if(flag_guardar !== 1)
            {
                $('#Cch_Val').val("");
                $('#Cch_Obs').val("");
                $('#Tia_Cod option:eq(0)').prop('selected', true);
                $('#Pld_Efe option:eq(0)').prop('selected', true);
            }            
        });
        
        /*
        *   Click on radio button Cheque
        */
        $("#opn").on("click", function(){
            if(flag_guardar !== 1)
            {
                $('#Ban_Cod option:eq(0)').prop('selected', true);
                $('#Tia_Cod option:eq(0)').prop('selected', true);
                $('#Pld_Efe option:eq(0)').prop('selected', true);
                $('#Che_Num').val("");
                $('#Che_Ben').val("");
                $('#Cch_Val').val("");
                $('#Cch_Obs').val("");
                $('#Che_Num').fieldValid("");
            }
        });
        
        function clearForm()
        {
            $('#opn').prop("checked",false);
            $('#opm').prop("checked",false);
            $('#chequeG').css('display','none'); 
            $('#bancosG').css('display','none'); 
            $('#btnBusca').css('display','none');
            $('#beneG').css('display','none');
            $('#Ban_Cod option:eq(0)').prop('selected', true);
            $('#Tia_Cod option:eq(0)').prop('selected', true);
            $('#Pld_Efe option:eq(0)').prop('selected', true);
            $('#Che_Num').val("");
            $('#Che_Ben').val("");
            $('#Cch_Val').val("");
            $('#Cch_Obs').val("");
            $('#Che_Num').fieldValid("");
        }
           
    });
    
    var flag_guardar = -1;
    var Pld_Des = "";
    var Pld_Cod = 0;
    var chequeEstado = "", chequeNumero = "";
    
    /*
     * Modificar Usuario
     */
    function modificarUser(row)
    {        
        
        $.getDataJson('',{get:true, Com_Cod:row.Com_Cod},function(res)
        {
            console.log(res);
            flag_guardar = 1;
            $('#modalCaja').dialog("option","title","Modificar");
            $('#modalCaja').dialog('open');
            $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Modificar");
            $('#frmModCaja').setData(res[1]);
            $("#Pld_Cod_Des").val(Pld_Des);            
            $('#Pld_Cod').val(Pld_Cod);            
            if (res[1].Che_Cod !== null) //cheque
            {
                $('#chequeG').css('display','');$('#bancosG').css('display','');$('#cuentaG').css('display','');$('#beneG').css('display','');
                $('#PldEfeG').css('display','none');
                $('#bancos').attr('required');
                $('#Che_Num').attr('required');
                $('#docu').attr('required');
                $('#opn').prop("checked",true);
                $('#Che_Num').fieldValid(true);
                chequeEstado = res[1].Che_Est;
                chequeNumero = res[1].Che_Num;
                var bancodigo = res[1].banco.split("*",1);
                $("#Ban_Cod option").filter(function(){
                    return bancodigo[0] === $(this)[0].value.split("*",1)[0]
                }).prop('selected',true);
            }
            else //efectivo
            {
                $('#chequeG').css('display','none'); 
                $('#beneG').css('display','none');
                $('#bancosG').css('display','none'); 
                $('#btnBusca').css('display','none');
                $('#cuentaG').css('display','');
                $('#PldEfeG').css('display','');
                $('#bancos').removeAttr('required');
                $('#Che_Num').removeAttr('required');
                $('#docu').removeAttr('required');
                $('#opm').prop("checked",true);
                $('#Pld_Efe').val(res[1].Pld_Cod);
            }          
        });   
    }