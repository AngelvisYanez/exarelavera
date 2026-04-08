$(function(){    
    
    /*************************************
    * ONLOAD PAGINA
    **************************************/
    function inicio()
    {
        $( "#tabsSearch" ).createTabs();
        $( "#editDialog" ).createDialog({width:500,height:250,icon:'pencil'});      
        $("#not_fei").createDatePickers();
        $("#not_fec").createDatePickers();
        fetchAllData();

        var flag_accion = 0;

    }	

    window.onload = inicio;
    var data = "";
    var tipoDoc = 0;
    
    
    
    /*
     * CHANGE FECHA INICIO
     */
    $('#not_fei').on("change",function(){
        var d = $("#not_fei").datepicker("getDate");
        $("#not_fec").datepicker("setDate",new Date(d.getFullYear(), d.getMonth()+1,d.getDate()));
    });   
   /*
    * 
    * Crea o Modifica una notificacion
    */
   $('#btnAccion').on("click", function(e){
        e.preventDefault();
      
        if ($('#not_fei').val()!=="" && $('#not_fec').val()!== "" )
        {   
            if (flag_accion === 1) // modificar
            {       
              $.saveDataJson('',$('#formDialog').getData('modifyAut'),function(re){
                    $('#editDialog').dialog('close');
                    var data=$('#formDialog').getData();
                    data['act3']='';
                    $("#tableResult").changeRow(data.not_cod,data);//actualiza el row
                    fetchAllData();                
              });              
            }
            else // nueva
            {
                $.saveDataJson('',$.extend($('#formDialog').getData('saveAut'), $('#Emp_Cod_n').val()),function(re){
                    tipoDoc = $('#Emp_Cod_n').val(); // select the code of empresa from modal                    
                    $('#editDialog').dialog('close');
                    fetchAllData();
                    
                   
              });
            }
        }
        else
        {
            $.alert("Los campos con * son requeridos");
        }
   });

   function fetchAllData()
    {
        /*
         * Fetch the Table with all data
         */

        $.getDataJson('',{searchAll:true},
            function(res){
                $('#tableResult').setRows(res.rows);
            },
            function(f){});
    }


   /*
    * On Click nueva notificacion
    */
   $('#btnNueva').on("click", function(e){
      flag_accion = 0; 
      $('#editDialog').dialog("option","title","Nueva Notificación");      
      $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Guardar");
      $('#editDialog').dialog('open');
      $("#formDialog")[0].reset(); // limpia modal
      var d = $("#not_fei").datepicker("getDate");
      $("#not_fec").datepicker("setDate",new Date(d.getFullYear(), d.getMonth()+1,d.getDate()));
     
   });
   
    //Inicio Grid para presentar la busqueda   
    $("#tableResult").createGrid({
        postData: $("#frm_bus").getData("searchFiltro"), height: 295,
        colModel: [
            {label: 'C&oacute;d. Int.', name: 'not_cod', width: 30, align: "center",key:true},
            {label: 'Fecha Inicio', name: 'not_fei', width: 40, align: "center"},
            {label: 'Fecha Fin', name: 'not_fec', width: 40, align: "center"},
            {label: 'Encabezado', name: 'not_enc', width: 60, align: "center"},  
            {label: 'Mensaje', name: 'not_msj', width: 60, align: "center"},           
            {label: 'Empresa', name: 'emp_nom', width: 60, align: "center"},
            {label: 'Estado', name: 'not_est', width: 30, align: "center"},
            {label: 'Modificar', name: 'act3', width: 30, align: 'center', viewable: false,
               formatter:function(cellvalue, options, rowObject){                   
                   return $.getGridButton(modificarAut, rowObject, 'Modificar', 'pencil');
               }
            },
            {label: '&nbsp;', name: 'act2', width: 30, align: 'center', viewable: false,
               formatter:function(cellvalue, options, rowObject){
                   if(rowObject.not_est==='Activo')
                        return $.getGridButton(setEstado, rowObject, 'Desactivar','fa fa-unlock');
                    else
                        return $.getGridButton(setEstado, rowObject, 'Activar','fa fa-lock','','danger');
              }
            }                
        ]
    }, true, "#tableResultPager",{}); //false -> paginacion | true -> una sola page    
    });


    /*
     * Estado de la notificacion
     */
    function setEstado (row)
    {
        if (row.not_est === "Activo")
        {
            row.not_est = 'I';
        }
        else
        {
            row.not_est = 'A';
        }
        $.createDialogConfirm("Esta seguro de realizar esta <b>acci&oacute;n</b> ?",row,function(){
            $.getDataJson('',{setEstado:true, not_cod: row.not_cod, not_est: row.not_est},function(response){                            
            $('#btnSearch').trigger('click');
	    })
        },function(f){           
            });        
    }
    
    function modificarAut(row)
    {
        flag_accion = 1;
        $('#formDialog').setData(row);		
    		$('#Emp_Cod_n').val(row.Emp_Cod);
        $('#editDialog').dialog("option", "title", "Editar");
        $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Modificar");
        $('#editDialog').dialog('open');
          
    }
