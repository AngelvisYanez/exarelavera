$(function(){    
    
    /*************************************
    * ONLOAD PAGINA
    **************************************/
    function inicio()
    {
        $( "#tabsSearch" ).createTabs();
        $( "#editDialog" ).createDialog({width:500,height:450,icon:'pencil'});
        $("#Tar_Fec_Ent").createDatePickers();
        $("#txt_fec_ini").createDatePickers();
        $("#txt_fec_fin").createDatePickers();
        $("#Aut_Cad").createDatePickers();
    }	

    window.onload = inicio;
    var data = "";
    var tipoDoc = 0;
    
    $('#txt_fec_ini').on("change",function(){
        var d = $("#txt_fec_ini").datepicker("getDate");
    }); 

    $('#txt_fec_fin').on("change",function(){
        var d = $("#txt_fec_fin").datepicker("getDate");
    });

    /*
     * Fecha de entrega de la tarea
     */
    $('#Tar_Fec_Ent').on("change",function(){
        var d = $("#Tar_Fec_Ent").datepicker("getDate");
    });   

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
        document.getElementById('modeTar').value = 0;     
        $('#editDialog').dialog("option","title","Nueva Tarea");      
        $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Guardar");
        $('#editDialog').dialog('open');
        $("#formDialog")[0].reset(); 
        var d = $("#Tar_Fec_Ent").datepicker("getDate");
   });

    //Inicio Grid para presentar la busqueda   
    $("#tableResult").createGrid({
        autowidth : false, shrinkToFit: true, height: 270,responsive:false,footerRow:true,
        autoheight: true,
        colModel: [
            {label: 'C&oacute;d.', name: 'Tar_Cod', width: 20, align: "center",key:true},
            {label: 'Fecha', name: 'Tar_Fec', width: 50, align: "center"},
			
      			{label: 'Creador', name: 'Tar_Cre_Nom', width: 50, align: "center"},
      			{label: 'Cre', name: 'Tar_Cre', hidden:true, width: 50, align: "center"},

            {label: 'Tar_Fil', name: 'Tar_Fil', hidden:true, width: 50, align: "center"},

            {label: 'Empresa', name: 'Emp_Nom', width: 70, align: "center"},
            {label: 'Emp', name: 'Emp_Cod', hidden:true, width: 50, align: "center"},

            {label: 'Descripcion', name: 'Tar_Des', width: 150, align: "center"},
            
            {label: 'Responsable', name: 'Tar_Res_Nom', width: 50, align: "center"},
            {label: 'Respo', name: 'Tar_Res', hidden:true, width: 50, align: "center"},
            {label: 'Val', name: 'Tar_Val', hidden:true, width: 50, align: "center"},

            {label: 'Fecha Entrega', name: 'Tar_Fec_Ent', width: 50, align: "center"},
            {label: 'Fecha Envio', name: 'Tar_Fec_Env', width: 50, align: "center"},            
            {label: 'Estado', name: 'Tar_Est', width: 40, align: "center"},

            {label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false,
               formatter:function(cellvalue, options, rowObject){
                   if(rowObject.Tar_Est=='Validado'){
                   		return '<i title="Tarea Validada" class="fa fa-check-circle green"></i>';
                   }
                   else if(rowObject.Tar_Est=='Inactivo'){
                   		return '';
                   }
                   else if(rowObject.Tar_Est=='Entregado' && (jsUsuarioTipo == "V" || jsUsuarioTipo == "T") && rowObject.Tar_Res != jsUsuario){
                   		 return $.getGridButton(validar, rowObject, 'Validar','glyphicon glyphicon-ok');
                   }
                   else{
                   		return '';
                   }
              }
            },
            {label: '&nbsp;', name: 'act2', width: 20, align: 'center', viewable: false,
               formatter:function(cellvalue, options, rowObject){
               	  if(rowObject.Tar_Est=='Inactivo' || rowObject.Tar_Est=='Validado' || rowObject.Tar_Est=='Entregado' || rowObject.Tar_Cre != jsUsuario){
                   		return '';
                  }
                  else{
                  	return $.getGridButton(modificarTarea, rowObject, 'Modificar', 'glyphicon glyphicon-pencil','','primary');
                  }                   
                   
               }
            },
            {label: '&nbsp;', name: 'act3', width: 20, align: 'center', viewable: false,
               formatter:function(cellvalue, options, rowObject){
                   if(rowObject.Tar_Est=='Inactivo'){
                   		return '<i title="Tarea Anulada" class="fa fa-close red"></i>';
                   }
                   else if(rowObject.Tar_Est=='Validado' || rowObject.Tar_Est=='Entregado' || rowObject.Tar_Cre != jsUsuario){
                   		return '';
                   }
                   else{
                   		 return $.getGridButton(anular, rowObject, 'Anular','glyphicon glyphicon-remove','','danger');
                   }
              }
            },
            { label: '&nbsp;', name: 'act4', width: 20, align: 'center',viewable: false, formatter:function(cellvalue, options, rowObject){
            	if(rowObject.Tar_Est=='Inactivo'){
                   		return '';
                }
                else if(rowObject.Tar_Est=='Validado'  || rowObject.Tar_Res != jsUsuario){
                   	return $.getGridButton(cargarDoc, rowObject, 'Ver', 'glyphicon glyphicon-arrow-right', '', 'info');
                 }
                else{                   
                   return $.getGridButton(cargarDoc, rowObject, 'Realizar', 'glyphicon glyphicon-arrow-right');
                  }
               }
            } 
        ],
        	loadComplete: function(data){
                if($.varValid(data.rows))
                for(var i=0,z=data.rows.length;i<z;i++){
                    if(data.rows[i]['Tar_Est'] ==='Inactivo') $("#"+data.rows[i].Tar_Cod+' td:not(.jqgrid-rownum)').addClass('cellRed2');
                    if(data.rows[i]['Tar_Est'] ==='Validado') $("#"+data.rows[i].Tar_Cod+' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                    if(data.rows[i]['Tar_Est'] ==='Entregado') $("#"+data.rows[i].Tar_Cod+' td:not(.jqgrid-rownum)').addClass('cellBlue2');
                }
            }

    }, true, "#tableResultPager",{}); 
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
        document.getElementById('modeTar').value = 1;

        $.getDataJson('',{'cargarEditar':true,'Tar_Cod':row['Tar_Cod']},function(resp){
          document.getElementById('Tar_Cod').value = resp['rows']['Tar_Cod'];
          document.getElementById('Tar_Cre').value = resp['rows']['Tar_Cre'];
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
    $("#txt_fec_ini").createDatePickers();
    $("#txt_fec_fin").createDatePickers();
    
      var respon = $('#Tar_Res_Filtro').val();
      var est = $('#Tar_Est_Filtro').val();
      var fecIni = $('#txt_fec_ini').val();
      var fecFin = $('#txt_fec_fin').val();

      $('#tableResult').trigger('reloadGrid');
        $.getDataJson('',{searchFiltro:true, responsable: respon, estado: est, fechaIni:fecIni, fechaFin:fecFin},function(res){
              $("#tableResult").setRows(res['rows']);
      },function(f){ });
   }


   function exportar(banTipo)
   {
        var respon = $('#Tar_Res_Filtro').val();
        var est = $('#Tar_Est_Filtro').val();
        var fecIni = $('#txt_fec_ini').val();
        var fecFin = $('#txt_fec_fin').val();
        $.post( "",{reporte:true, responsable: respon, estado: est, fechaIni:fecIni, fechaFin:fecFin}, function(response) 
        {
            if(response['success']===true)
            {
                if(banTipo)
                {
                    $(response['html']).printElement({pageTitle:'<?Php echo $Ses_Sys_Nom; ?>'});
                }   
                else
                {
                    $.downloadFile($.exportarExcelBlob(response['html'],'TAREAS'),'ReporteTareas'+$.getDate()+'.xls');
                }     
            }
            else
            {
                $.alert(response['message']);
            }                                   
        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });

    }