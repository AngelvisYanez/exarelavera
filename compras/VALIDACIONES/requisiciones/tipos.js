$(function(){    
    
    /*************************************
    * ONLOAD PAGINA
    **************************************/
    function inicio()
    {
        $( "#tabsSearch" ).createTabs();
        $( "#editDialog" ).createDialog({width:500,height:110,icon:'pencil'});
        $( "#editModal" ).createDialog({width:500,height:150,icon:'pencil'});
    }	

    window.onload = inicio;
    var data = "";
    var tipoDoc = 0;


    /*
    * OnClick para buscar con el boton
    */
   $('#btnSearch').on("click",function(e){
      e.preventDefault();
      var respon = $('#Tar_Res_Filtro').val();
      var est = $('#Tar_Est_Filtro').val();
      var fecIni = $('#txt_fec_ini').val();
      var fecFin = $('#txt_fec_fin').val();

      $('#tableResult').trigger('reloadGrid');
        $.getDataJson('',{searchFiltro:true, responsable: respon, estado: est, fechaIni:fecIni, fechaFin:fecFin},function(res){
              $("#tableResult").setRows(res['rows']);
          },function(f){            
          });
   });


  /*
  * On Click nueva tarea
  */
   $('#btnNueva').on("click", function(e){
        document.getElementById('guardarTipo').value = 0;     
        $('#editDialog').dialog("option","title","Nuevo Tipo de Requisicion");      
        $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Guardar");
        $('#editDialog').dialog('open');
        $("#formDialog")[0].reset(); 
   });

   

    //Inicio Grid para presentar la busqueda   
    $("#tableResult").createGrid({
        autowidth : false, shrinkToFit: true, height: 270,responsive:false,footerRow:true,
        autoheight: true,
        colModel: [
            {label: 'C&oacute;d.', name: 'Rtp_Cod',  hidden:true,width: 20, align: "center",key:true},
            {label: 'Descripcion', name: 'Rtp_Des', width: 150, align: "center"},
            {label: 'Estado', name: 'Rtp_Est', width: 50, align: "center"},

        
            { label: '&nbsp;', name: 'act4', width: 20, align: 'center',viewable: false, 
                formatter:function(cellvalue, options, rowObject){
                   	return $.getGridButton(showEditarModal, rowObject, 'Editar', 'glyphicon glyphicon-edit', '', 'primary');
               }
            } 
        ]
    }, true, "#tableResultPager",{}); 
    });

    function showEditarModal(row) {
        console.log("SHOWEDITMODAL", row)
        document.getElementById('guardarTipo').value = 0;
        $('#editModal').dialog("option","title","Editar Tipo de Requisicion");      
        $('#btnEditar').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Editar");
        $('#editModal').dialog('open');
        $.get( "",
            {Rtp_Cod: row.Rtp_Cod, consultarTipo: true}, 
            function(response){
                console.log("RESPONSE",response)
                $('#Rtp_Des_Edit').val(response.data.Rtp_Des)
                $('#Rtp_Cod').val(response.data.Rtp_Cod)
                $('#Rtp_Est_Edit').val(response.data.Rtp_Est)                                
            },
            'json')
            .fail(
                function(error) {
                    $.alert("El Servidor ha fallado en responder!");
                    console.log(error); 
                }
            );
   } 
   $("#btnEditar").on("click",function(e){
        e.preventDefault();
        var Rtp_Des = $('#Rtp_Des_Edit').val();
        var Rtp_Cod = $('#Rtp_Cod').val();
        var Rtp_Est = $('#Rtp_Est_Edit').val();
        $.post( 
            "",
            {Rtp_Cod, Rtp_Des, Rtp_Est,editarTipo: true}, 
            function(response){
                console.log("RESPONSE",response)
                loadTasks()
                $('#editModal').dialog('close');
                $.alert("Datos actualizados exitosamente")                               
            },
        'json')
        .fail(
            function(error) {
                $.alert("El Servidor ha fallado en responder!");
                console.log(error); 
            }
        );
        
    });


    function validar (row)
    {
        var estado = 'V';
        $.createDialogConfirm("Esta seguro de realizar esta <b>acci&oacute;n</b> ?",row,function(){
            $.getDataJson('',{setEstado:true, Tar_Cod: row.Tar_Cod, Tar_Est: estado, Tar_Validar:true},function(response){            
            $('#btnSearch').trigger('click');
            $('#tableResult').trigger('reloadGrid');
            })
        },function(f){           
            });        
    }

    function anular (row)
    {
        var estado = 'I';
        $.createDialogConfirm("Esta seguro de realizar esta <b>acci&oacute;n</b> ?",row,function(){
            $.getDataJson('',{setEstado:true, Tar_Cod: row.Tar_Cod, Tar_Est: estado},function(response){            
            $('#btnSearch').trigger('click');
            })
        },function(f){           
            });        
    }

    function cargarDoc(row){
    	 $('#documentoSearch').hide();
    	 $('#documentoMain').show();

    	 $('#Tad_Des').prop('disabled', false);
    	 $('#Tad_Obs').prop('disabled', false);
       $('#saveButton').show();
       $('#Tad_Fil').val('');
       $('#Tad_Fil_Sub').hide();
       $('#Tar_Fil_Re').hide();

    	 $.getDataJson('',{'cargarDoc':true,'Tar_Cod':row['Tar_Cod']},function(resp){
    	 		var info = resp['rows'];

           $('#saveButton').text('Guardar');
           $('#Tad_Cod_Mod').val('0');

    	 		 $('#Tar_Cod_Re').val(info['Tar_Cod']);
    	 		 $('#Tar_Fec_Cre').text(info['Tar_Fec']);
    	 		 $('#Tar_Cre_Re').text(info['Tar_Cre_Nom']);
    	 		 $('#Tar_Res_Re').text(info['Tar_Res_Nom']);

    	 		 if(info['Tar_Val_Nom'] != null){	$('#Tar_Val_Re').text(info['Tar_Val_Nom']);}
    	 		 else{	$('#Tar_Val_Re').text('No Validado');}

    	 		 $('#Emp_Cod_Re').text(info['Emp_Nom']);
    	 		 $('#Tar_Fec_Ent_Re').text(info['Tar_Fec_Ent']);
    	 		 $('#Tar_Des_Re').text(info['Tar_Des']);

           if(info['Tar_Fil']!=""){
            $('#Tar_Fil_Re').show();
            $("#Tar_Fil_Re").attr('href', info['Tar_Fil']);
           }

           if(info['Tad_Cod']!=null){
               $('#Tad_Des').val(info['Tad_Des']);
               $('#Tad_Obs').val(info['Tad_Obs']);
               $('#Tad_Cod_Mod').val('1');
               $('#saveButton').text('Modificar');
               
               if(info['Tad_Fil']!=""){
                 $('#Tad_Fil_Sub').show();
                 $("#Tad_Fil_Sub").attr('href', info['Tad_Fil']);
               }
           }else{
              $('#Tad_Des').val('');
              $('#Tad_Obs').val('');
           }

           if(info['Tar_Est'] == 'Validado' || row.Tar_Res != jsUsuario){
              $('#Tad_Des').prop('disabled', true);
              $('#Tad_Obs').prop('disabled', true);
              $('#saveButton').hide();
           }

    	 });
	}

    function atras(){
    	 $('#documentoMain').hide();
    	 $('#documentoSearch').show();
       $('#tableResult').trigger('reloadGrid');
    }

    //Realizar y Modificar Tarea
    function guardar(){
        var formData = new FormData($('#formDialogRe')[0]);
        $.ajax({
                url: window.location.pathname, 
                type: 'POST', 
                data: formData, 
                dataType: "json", 
                async: true, 
                cache: false, 
                contentType: false, 
                processData: false
            }).done(function (re){
                if(re.success===true){
                    $.alert('Se ha registrado con �xito!');
                    $('#documentoMain').hide();
                    $('#documentoSearch').show();
                    loadTasks();
                    $('#Tad_Des').val('');
                    $('#Tad_Obs').val('');
                }else{
                    $.alert('No se pudo realizar la accion!');
                }
        }).fail(function(){ $.alert(); });
    }

    //Crear y modificar Tarea
    function guardarTarea(){
        var formData = new FormData($('#formDialog')[0]);
        $.ajax({
                url: window.location.pathname, 
                type: 'POST', 
                data: formData, 
                dataType: "json", 
                async: true, 
                cache: false, 
                contentType: false, 
                processData: false
            }).done(function (re){
                if(re.success===true){
                    $.alert('Se ha registrado con �xito!');
                    $('#editDialog').dialog('close');
                     loadTasks();
                     $('#tableResult').trigger('reloadGrid');
                }else{
                    $.alert('No se pudo realizar la accion!');
                }
        }).fail(function(){ $.alert(); });
    }



  //Cargar los datos en el dialog para modificar
  function modificarTarea(row)
    {
        $('#Tar_Fil').val('');
        document.getElementById('guardarTipo').value = 1;

        $.getDataJson('',{'cargarEditar':true,'Tar_Cod':row['Tar_Cod']},function(resp){
          document.getElementById('Tar_Cod').value = resp['rows']['Tar_Cod'];
          document.getElementById('Rtp_Des').value = resp['rows']['Rtp_Des'];
          document.getElementById('Tar_Res').value = resp['rows']['Tar_Res'];
          document.getElementById('Emp_Cod').value = resp['rows']['Emp_Cod'];
          document.getElementById('Tar_Fec_Ent').value = resp['rows']['Tar_Fec_Ent'];
          document.getElementById('Tar_Des').value = resp['rows']['Tar_Des'];
        });

        $('#editDialog').dialog("option", "title", "Editar");
        $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Modificar");
        $('#editDialog').dialog('open');
    }

   /*
    * Metodo para cargar tareas
    */
   function loadTasks(){

      $('#tableResult').trigger('reloadGrid');
        $.getDataJson('',{ searchFiltro:true },function(res){
              $("#tableResult").setRows(res['rows']);
      },function(f){ });
   }
