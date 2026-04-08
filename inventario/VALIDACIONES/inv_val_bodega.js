$(function(){    
    
    /*************************************
    * ONLOAD PAGINA
    **************************************/
    function inicio()
    {
        $( "#tabsSearch" ).createTabs();
        $( "#editDialog" ).createDialog({width:500,height:250,icon:'pencil'});      
        $('#usu_cod').createChosen();
        fetchAllData();

        var flag_accion = 0;

    }	

    window.onload = inicio;
    var data = "";
    var tipoDoc = 0;
    
    $('#not_fei').on("change",function(){
        var d = $("#not_fei").datepicker("getDate");
        $("#not_fec").datepicker("setDate",new Date(d.getFullYear(), d.getMonth()+1,d.getDate()));
    }); 


   /*
    * 
    * Crea o Modifica una bodega
    */
   var json_asig = [];
   var json_save = [];
   $('#btnAccion').on("click", function(e){
        e.preventDefault();
      
        if ($('#bod_nom').val()!=="" && $('#bod_dir').val()!== "" )
        {   
            if (flag_accion === 1) // modificar
            { 
              $.saveDataJson('',$('#formDialog').getData('modifyAut'),function(re){
                    $('#editDialog').dialog('close');
                    var data=$('#formDialog').getData();
                    data['act3']='';
                    $("#tableResult").changeRow(data.bod_cod,data);//actualiza el row
                    fetchAllData();                
              });                
            }
            else // nueva
            {
                var data = $('#formDialog').serializeArray();
                $.getDataJson('',{saveBodega:true, data:data},function(response){    
                  if(response['success']){
                    $.alert(response['message']);              
                    $('#editDialog').dialog('close');
                    fetchAllData();
                  }
                  else{
                    $.alert(response['message']); 
                  }
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

    //var json_asig = [];
   /*
    * On Click nueva bodega
    */
   $('#btnNueva').on("click", function(e){
      flag_accion = 0; 
      $('#editDialog').dialog({title:'Nueva Bodega'});      
      $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Guardar");
      $('#editDialog').dialog('open');
      $('#usu_cod option').prop('selected', false).trigger('chosen:updated');
      $("#formDialog")[0].reset();
      
     
   });
   
    //Inicio Grid para presentar la busqueda   
    $("#tableResult").createGrid({
        height: 295,
        colModel: [
            {label: 'C&oacute;d. Int.', name: 'bod_cod', width: 30, align: "center",key:true},
            {label: 'Nombre', name: 'bod_nom', width: 40, align: "center"},
            {label: 'Dirección', name: 'bod_dir', width: 40, align: "center"},
            {label: 'Tipo', name: 'bod_tip', width: 60, align: "center"},  
            {label: 'Usuarios', name: 'Perfiles', width: 60, formatter:'tags'},
            {label: 'Control de ventas', name: 'bod_cvt', width: 60, align: "center"},           
            {label: 'Modificar', name: 'act3', width: 30, align: 'center', viewable: false,
               formatter:function(cellvalue, options, rowObject){                   
                   return $.getGridButton(modificarAut, rowObject, 'Modificar', 'pencil');
               }
            },               
        ]
    }, true, "#tableResultPager",{}); //false -> paginacion | true -> una sola page    
    });

    
    function modificarAut(row)
    {
        flag_accion = 1;
        $('#formDialog').setData(row);
        console.log(row);		
        $('#bod_tip').val(row.bod_tip=='Principal'?'P':'S').trigger('change');
        $('#bod_cvt').val(row.bod_cvt=='Con control de ventas'?'S':'N').trigger('change');
        $('#editDialog').dialog("option", "title", "Editar");
        $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Modificar");
        $('#editDialog').dialog('open');

        $.getDataJson('',{getPerfilByUser:true, bod_cod:row.bod_cod},
            function(res){
                json_asig = [];
                json_asig = res.perfiles;// perfiles asignados

                $.each(res.perfiles, function(i, val){
                    $("#usu_cod option[value=" + val.usu_cod + "]").prop("selected", true);
                });

                $("#usu_cod").trigger("chosen:updated");
            },
            function(err){$.alert(err);});
          
    }
