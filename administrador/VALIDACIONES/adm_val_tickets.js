$(function(){    
    
    
    /*************************************
    * ONLOAD PAGINA
    **************************************/
    function inicio()
    {
        $("#tabsSearch").createTabs();
        $("#createModal").createDialog({width:500,height:470,icon:'pencil'});
        $("#Tic_Fec_Ent").createDatePickers();
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
     * Fecha de entrega del ticket
     */
    $('#Tic_Fec_Ent').on("change",function(){
        var d = $("#Tic_Fec_Ent").datepicker("getDate");
    });   

    $('input[type=file]').change(function(){
        var t = $(this).val();
        var labelText = 'Nuevo Archivo: ' + t.substr(12, t.length);
        $(this).prev('label').text(labelText);
      })

    /*
    * OnClick para buscar con el boton
    */
   $('#btnSearch').on("click",function(e){
      e.preventDefault();
      var fecIni = $('#txt_fec_ini').val();
      var fecFin = $('#txt_fec_fin').val();

      $('#tableResult').trigger('reloadGrid');
        $.getDataJson('/administrador/api/tickets.php',{searchFiltro:true, fechaIni:fecIni, fechaFin:fecFin},function(res){
              $("#tableResult").setRows(res['rows']);
          },function(f){            
          });
   });

  /*
  * On Click nueva ticket
  */
   $('#btnNueva').on("click", function(e){
        // document.getElementById('modeTar').value = 0;     
        $('#createModal').dialog("option","title","Nuevo Ticket");      
        $('#btnCreate').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Guardar");
        $('#createModal').dialog('open');
        $("#createForm")[0].reset(); 
        var d = $("#Tic_Fec_Ent").datepicker("getDate");
   });

    //Inicio Grid para presentar la busqueda   
    $("#tableResult").createGrid({
        autowidth : false, shrinkToFit: true, height: 270,responsive:false,footerRow:true,
        autoheight: true,
        colModel: [
            {label: 'C&oacute;d.', name: 'Tic_Cod', width: 20, align: "center",key:true},
            {label: 'Fecha', name: 'Tic_Fec_Cre', width: 50, align: "center"},

            {label: 'Emp_Cod', name: 'Emp_Cod', hidden:true, width: 70, align: "center"},
            {label: 'Empresa', name: 'Emp_Nom', width: 70, align: "center"},

            {label: 'Usu_Cod', name: 'Usu_Cod', hidden:true, width: 50, align: "center"},
            {label: 'Usuario', name: 'Prs_Nom', width: 50, align: "center"},
            {label: 'Apellido',name:'Prs_Ape', hidden:true, width: 50, align: "center"},
        
            {label: 'Tema', name: 'Tic_Tem', width: 150, align: "center"},
            {label: 'Descripcion', name: 'Tic_Des', hidden:true, width: 150, align: "center"},

            {label: 'Fecha Soluci&oacute;n', name: 'Tic_Fec_Ter', width: 50, align: "center"},  
            
            { label: 'Enviar a WhatsApp', name: 'act5', width: 50, align: 'center',viewable: false, formatter:function(cellvalue, options, rowObject){
            	if(rowObject.Tic_Est=='Pendiente'){
                    return $.getGridButton(enviarTicketWhatsApp, rowObject, 'Enviar', 'fa fa-whatsapp', '', 'success');
                }
                else if(rowObject.Tic_Est=='En Proceso'){
                   	return $.getGridButton(enviarTicketWhatsApp, rowObject, 'Enviar', 'fa fa-whatsapp', '', 'success');
                 }
                else if(rowObject.Tic_Est=='Solucionado'){
                    return "";
                }
                else{                   
                   return $.getGridButton(enviarTicketWhatsApp, rowObject, 'Enviar', 'fa fa-whatsapp','', 'success');
                  }
               }
            },

            {label: 'Estado', name: 'Tic_Est', width: 40, align: "center"},

            {label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false,
               formatter:function(cellvalue, options, rowObject){
                   if(rowObject.Tic_Est=='Solucionado'){
                   		return '<i title="Solucionado" class="fa fa-check-circle green"></i>';
                   }else if(rowObject.Tic_Est=='Pendiente'){
                   		return '<i title="Pendiente" class="fa fa-hourglass-start orange"></i>';
                   }else if(rowObject.Tic_Est=='En Proceso' ){
                   		 return '<i title="En Proceso" class="fa fa-hourglass-half yellow"></i>';
                   }else{
                       // Reabierto
                   		return '<i title="Reabierto" class="fa fa-info-circle red"></i>';
                   }
              }
            },
            { label: '&nbsp;', name: 'act4', width: 20, align: 'center',viewable: false, formatter:function(cellvalue, options, rowObject){
            	if(rowObject.Tic_Est=='Pendiente'){
                    return $.getGridButton(verTicket, rowObject, 'Ver', 'glyphicon glyphicon-arrow-right', '', 'warning');
                }
                else if(rowObject.Tic_Est=='En Proceso'){
                   	return $.getGridButton(verTicket, rowObject, 'Ver', 'glyphicon glyphicon-arrow-right', '', 'default');
                 }
                else if(rowObject.Tic_Est=='Solucionado'){
                    return $.getGridButton(verTicket, rowObject, 'Ver', 'glyphicon glyphicon-arrow-right', '', 'success');
                }
                else{                   
                   return $.getGridButton(verTicket, rowObject, 'Realizar', 'glyphicon glyphicon-arrow-right','', 'danger');
                  }
               }
            } 
        ],
        	loadComplete: function(data){
                if($.varValid(data.rows))
                for(var i=0,z=data.rows.length;i<z;i++){
                    if(data.rows[i]['Tic_Est'] ==='Pendiente') $("#"+data.rows[i].Tic_Cod+' td:not(.jqgrid-rownum)').addClass('cellRed2');
                    if(data.rows[i]['Tic_Est'] ==='En Proceso') $("#"+data.rows[i].Tic_Cod+' td:not(.jqgrid-rownum)').addClass('cellBlue2');
                    if(data.rows[i]['Tic_Est'] ==='Reabierto') $("#"+data.rows[i].Tic_Cod+' td:not(.jqgrid-rownum)').addClass('cellOrange2');
                    if(data.rows[i]['Tic_Est'] ==='Solucionado') $("#"+data.rows[i].Tic_Cod+' td:not(.jqgrid-rownum)').addClass('');
                }
            }
    }, true, "#tableResultPager",{}); 

    // CREAR TICKET - SELECT DEL MODULO RELACIONADO AL SOPORTE
    $('#Tic_Mod').on('change', async function (e) {
        const procesos = await $.ajax({
            url: '/administrador/api/tickets.php', 
            type: 'POST', 
            data: {
                getProcesses:true,
                Org_Niv: this.value
            }, 
            dataType: "json",
        })

        $("#Tic_Pro option").remove();

        $.each(procesos, function (i, item) {
            $('#Tic_Pro').append($('<option>', { 
                value: item.Org_Cod,
                text : item.Org_Des 
            }));
        });

        const acciones = await $.ajax({
            url: '/administrador/api/tickets.php', 
            type: 'POST', 
            data: {
                getActions:true,
                Org_Cod:$('#Tic_Pro').val()
            }, 
            dataType: "json",
        })
    
        $("#Tic_Acc option").remove();
        $.each(acciones, function (i, item) {
            $('#Tic_Acc').append($('<option>', { 
                value: item.Pcs_Cod,
                text : item.Pcs_Lin 
            }));
        });
    });

    // CREAR TICKET - SELECT DEL PROCESO RELACIONADO AL SOPORTE
    $('#Tic_Pro').on('change', async function (e) {

        const acciones = await $.ajax({
            url: '/administrador/api/tickets.php', 
            type: 'POST', 
            data: {
                getActions:true,
                Org_Cod:this.value
            }, 
            dataType: "json",
        })
    
        $("#Tic_Acc option").remove();
        $.each(acciones, function (i, item) {
            $('#Tic_Acc').append($('<option>', { 
                value: item.Pcs_Cod,
                text : item.Pcs_Lin 
            }));
        });
    });

    // MODIFICAR TICKET - MODULO RELACIONADO AL TICKET
    $('#Mod_Tic_Mod').on('change', async function (e) {
        const procesos = await $.ajax({
            url: '/administrador/api/tickets.php', 
            type: 'POST', 
            data: {
                getProcesses:true,
                Org_Niv: this.value
            }, 
            dataType: "json",
        })

        $("#Mod_Tic_Pro option").remove();

        $.each(procesos, function (i, item) {
            $('#Mod_Tic_Pro').append($('<option>', { 
                value: item.Org_Cod,
                text : item.Org_Des 
            }));
        });

        const acciones = await $.ajax({
            url: '/administrador/api/tickets.php', 
            type: 'POST', 
            data: {
                getActions:true,
                Org_Cod:$('#Mod_Tic_Pro').val()
            }, 
            dataType: "json",
        })
    
        $("#Mod_Tic_Acc option").remove();
        $.each(acciones, function (i, item) {
            $('#Mod_Tic_Acc').append($('<option>', { 
                value: item.Pcs_Cod,
                text : item.Pcs_Lin 
            }));
        });
    });

    // MODIFICAR TICKET - ACCION RELACIONADA AL TICKET
    $('#Mod_Tic_Pro').on('change', async function (e) {

        const acciones = await $.ajax({
            url: '/administrador/api/tickets.php', 
            type: 'POST', 
            data: {
                getActions:true,
                Org_Cod:this.value
            }, 
            dataType: "json",
        })
    
        $("#Mod_Tic_Acc option").remove();
        $.each(acciones, function (i, item) {
            $('#Mod_Tic_Acc').append($('<option>', { 
                value: item.Pcs_Cod,
                text : item.Pcs_Lin 
            }));
        });
    });
    var holder = document.getElementById('Mod_Tic_Evi_Pro');
    holder.onchange = function (e) {
        // this.className = 'hidden';
        e.preventDefault();
        var file = e.target.files[0];
        var reader = new FileReader();
        reader.onload = function (event) {
            document.getElementById('Img-Evi').className='visible'
            $('#Img-Evi').attr('src', event.target.result);
        }
        reader.readAsDataURL(file);
      };

      var createFileInput = document.getElementById('Tic_Evi_Pro');
      createFileInput.onchange = function (e) {
        e.preventDefault();
        console.log('File Name',createFileInput.files[0].name)
        $("#Tic_Evi_Pro_Txt").text(createFileInput.files[0].name.substring(0, 35))
      };
    
});
    var loadFile = function(event) {
        var output = document.getElementById('Img-Evi');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.onload = function() {
        URL.revokeObjectURL(output.src) // free memory
        }
    };

    function validar (row)
    {
        var estado = 'V';
        $.createDialogConfirm("Esta seguro de realizar esta <b>acci&oacute;n</b> ?",row,function(){
            $.getDataJson('',{setEstado:true, Tic_Cod: row.Tic_Cod, Tic_Est: estado, Tic_Validar:true},function(response){            
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
            $.getDataJson('',{setEstado:true, Tic_Cod: row.Tic_Cod, Tic_Est: estado},function(response){            
            $('#btnSearch').trigger('click');
            })
        },function(f){           
            });        
    }



    function enviarTicketWhatsApp(row){
        window.open(`https://api.whatsapp.com/send?phone=593978835575&text=Saludos, necesito ayuda con este ticket ${row.Tic_Cod}.  Titulo: ${row.Tic_Tem}  Descripción: ${row.Tic_Des}`);
    }

     function verTicket(row){
    	$('#documentoSearch').hide();
    	$('#documentoMain').show();

    	$('#Tad_Des').prop('disabled', true);
    	$('#Tic_Obs').prop('disabled', true);
        $('#saveButton').show();
        $('#Tic_Evi_Sol').hide();
        /* $('#Tad_Fil').val('');
        $('#Tad_Fil_Sub').hide();
        $('#Tic_Fil_Re').hide(); */

        // $('input[name=rating]:checked').val()
        
        $(`#star${row.Tic_Cal}`).prop('checked', true);
        console.log('CALIFICACION!!!', $('input[name=rating]:checked').val())
        $('#Tic_Cal_Des').val(row['Tic_Cal_Des'])

        if(row['Tic_Est'] ==='Solucionado'){
            $('#Cal_Fields').show();
            if(row['Tic_Cal']!=='0'){
                $('#Cal_Text').hide()
                $('#btnCalificar').hide()
                $('#Tic_Cal_Des').prop('disabled', true);
                $(`input:radio[name=rating]`).prop('disabled',true)
            }else{
                $('#Cal_Text').show()
                $('#btnCalificar').show()
                $('#Tic_Cal_Des').prop('disabled', false);
                $(`input:radio[name=rating]`).prop('disabled',false)
            }
        }else{
            $('#Cal_Fields').hide();
        }

        let resp 
        $.ajax({
            url: '/administrador/api/tickets.php', 
            type: 'GET', 
            data: {
                verTicket:true,
                Tic_Cod:row['Tic_Cod']
            }, 
            success: (res) =>{
                resp = res
            },
            dataType: "json",
            async: false,
           
        })
        var info = resp['rows'];
        $('#saveButton').text('Guardar');
        $('#Tad_Cod_Mod').val('0');

        $('#saveButton').text('Guardar');
        $('#Tic_Cod_Mod').val('0');

        $('#Tic_Cod').val(info['Tic_Cod']);
        $('#Tic_Est').text(info['Tic_Est']);
        // $(`select option[value="${info['Tic_Est']}"]`).prop('selected', true);
        $('#Mod_Tic_Tel').val(info['Tic_Tel']);
        $('#Mod_Tic_Mod').val(info['Org_Mod']);

        // Modificar Ticket
        let modProcesos 
        $.ajax({
            url: '/administrador/api/tickets.php', 
            type: 'POST', 
            data: {
                getProcesses:true,
                Org_Niv:$("#Mod_Tic_Mod").val()
            }, 
            dataType: "json",
            async: false,
            success: (res) => {
                modProcesos = res
            }
        })

        $("#Mod_Tic_Pro option").remove();
        $.each(modProcesos, function (i, item) {
            $('#Mod_Tic_Pro').append($('<option>', { 
                value: item.Org_Cod,
                text : item.Org_Des 
            }));
        });

        $('#Mod_Tic_Pro').val(info['Org_Sec']);

        // Modificar Ticket
        let modAcciones 
        $.ajax({
            url: '/administrador/api/tickets.php', 
            type: 'POST', 
            data: {
                getActions:true,
                Org_Cod:$("#Mod_Tic_Pro").val()
            }, 
            dataType: "json",
            async: false,
            success: (res) => {
                modAcciones = res
            }
        })

        $("#Mod_Tic_Acc option").remove();
        $.each(modAcciones, function (i, item) {
            $('#Mod_Tic_Acc').append($('<option>', { 
                value: item.Pcs_Cod,
                text : item.Pcs_Lin 
            }));
        });

        $('#Mod_Tic_Acc').val(info['Pcs_Cod']);
        // $("#Mod_Tic_Evi_Pro").text(info['Tic_Evi_Pro']);
        // $("#Tex_Tic_Evi_Pro").text(info['Tic_Evi_Pro']);
        $("#Img-Evi").attr('src', info['Tic_Evi_Pro']);
        // $('#Mod_Tic_Evi_Pro').val(info['Tic_Evi_Pro']);
        $('#Tic_Fec_Cre').text(info['Tic_Fec_Cre']);
        $('#Tic_Fec_Ter').text(info['Tic_Fec_Ter']);
        $('#Prs_Nom').text(info['Prs_Nom']);
        $('#Tic_Tem').text(info['Tic_Tem']);
        $('#Tic_Cal_Des').text(info['Tic_Cal_Des']);

        if(info['Tic_Val_Nom'] != null){
            $('#Tic_Val_Re').text(info['Tic_Val_Nom']);
        }else{
            $('#Tic_Val_Re').text('No Validado');
        }

        $('#Emp_Cod_Re').text(info['Emp_Nom']);
        /* $('#Tic_Fec_Ent_Re').text(info['Tic_Fec_Ent']);
        $('#Tic_Des_Re').text(info['Tic_Tem'] +'\n'+ info['Tic_Des']); */

        if(!(info['Tic_Evi_Sol']=="" || info['Tic_Evi_Sol'] == null)){
            $('#Tic_Evi_Sol').show();
            $("#Tic_Evi_Sol").attr('href', info['Tic_Evi_Sol']);
        }

        if(info['Tic_Cod']!=null){
            $('#Mod_Tic_Des').val(info['Tic_Des']);
            $('#Mod_Tic_Tem').val(info['Tic_Tem']);
            $('#Tic_Obs').val(info['Tic_Obs']);

            $('#Tic_Cod_Mod').val('1');
            $('#saveButton').text('Modificar');
        }else{
            $('#Mod_Tic_Des').val('');
            $('#Mod_Tic_Tem').val('');
            $('#Tic_Obs').val('');
        }

        if(info['Tic_Est'] != 'Pendiente'){
            $('#Mod_Tic_Des').prop('disabled', true);
            $('#Mod_Tic_Tem').prop('disabled', true);
            $('#Mod_Tic_Tel').prop('disabled', true);
            $('#Mod_Tic_Mod').prop('disabled', true);
            $('#Mod_Tic_Pro').prop('disabled', true);
            $('#Mod_Tic_Acc').prop('disabled', true);
            $('#drag_drop').prop('hidden', true);
            $('#drag_drop').prop('disabled', true);
            $('#saveButton').hide();
        }else{
            $('#Mod_Tic_Des').prop('disabled', false);
            $('#Mod_Tic_Tem').prop('disabled', false); 
            $('#Mod_Tic_Tel').prop('disabled', false);
            $('#Mod_Tic_Mod').prop('disabled', false);
            $('#Mod_Tic_Pro').prop('disabled', false);
            $('#Mod_Tic_Acc').prop('disabled', false);
            $('#drag_drop').prop('hidden', false);
            $('#drag_drop').prop('disabled', false);
        }


        /* $.getDataJson('/administrador/api/tickets.php',{'verTicket':true,'Tic_Cod':row['Tic_Cod']},function(resp){
            
        });    */
	}

    function atras(){
        $(`input:radio[name=rating]`).prop("checked",false)
    	 $('#documentoMain').hide();
    	 $('#documentoSearch').show();
       // $('#tableResult').trigger('reloadGrid');
       loadTasks(false)
       $("#Tic_Cal span").remove();
    }

    function subirImagen(){
        var formData = new FormData($('#editForm')[0]);
        /* $.ajax({
                url: '/administrador/api/tickets.php', 
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
                    $('#createModal').dialog('close');
                     loadTasks();
                     $('#tableResult').trigger('reloadGrid');
                }else{
                    $.alert('No se pudo realizar la accion!');
                }
        }).fail(function(err){
            console.log('ERROR',err) 
            $.alert(); 
        }); */
    }

    //Realizar y Modificar Ticket
    function editarTicket(){
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
                    $(`input:radio[name=rating]`).prop("checked",false)
                    $("#Tic_Cal span").remove();
                    $('#documentoMain').hide();
                    $('#documentoSearch').show();
                    loadTasks();
                    $('#Tad_Des').val('');
                    $('#Tic_Obs').val('');
                }else{
                    $.alert('No se pudo realizar la accion!');
                }
        }).fail(function(){ $.alert(); });
    }

    //Crear Ticket
    function crearTicket(){
        var formData = new FormData($('#createForm')[0]);
        
        $.ajax({
                url: '/administrador/api/tickets.php', 
                type: 'POST', 
                data: formData, 
                dataType: "json", 
                async: true, 
                cache: false, 
                contentType: false, 
                processData: false
            }).done(function (re){
                console.log(re);
                if(re.success===true){
                   // console.log("ID", re.id)
                    $.alert(`El ticket ${re.id} ha sido generado con &eacute;xito! <br> <a style="display:flex" target="_blank"  href="https://api.whatsapp.com/send?phone=593978835575&text=Se ha generado el ticket ${re.id}.  Titulo: ${re.titulos}  Descripción: ${re.descripcion}" > <i style="color:#037a03;font-size:20px" class="ace-icon fa fa-whatsapp"> </i> Envía tu ticket a WhatsApp: +593 978835575, haz clic aquí <a>`);
                    $('#createModal').dialog('close');
                    socket.emit('newData',{})
                     loadTasks();
                     $('#tableResult').trigger('reloadGrid');
                }else{
                    $.alert('No se pudo realizar la accion!');
                }
        }).fail(function(err){
            console.log('ERROR',err) 
            $.alert(); 
        });
    }

  //Modificar Ticket
  function modificarTicket()
    {
        var formData = new FormData($('#editForm')[0]);
        $.ajax({
            url: '/administrador/api/tickets.php', 
            type: 'POST', 
            data: formData, 
            dataType: "json", 
            async: true, 
            cache: false, 
            contentType: false, 
            processData: false
        }).done(function (re){
            if(re.success===true){
                $.alert('Se ha modificado con &eacute;xito!');
                // $('#createModal').dialog('close');
                socket.emit('newData',{})
                 loadTasks();
                 $('#tableResult').trigger('reloadGrid');
            }else{
                $.alert('No se pudo realizar la accion!');
            }
        }).fail(function(err){
            console.log('ERROR',err) 
            $.alert(); 
        });
    }

   /*
    * Metodo para cargar tickets
    */
async function loadTasks(resetDates){
    if(resetDates){
        $("#txt_fec_ini").createDatePickers();
        $("#txt_fec_fin").createDatePickers();
    }

    let isTableViewHidden = $('#documentoSearch').is(":hidden");
    if(isTableViewHidden) return null
    
    const modulos = await $.ajax({
        url: '/administrador/api/tickets.php', 
        type: 'POST', 
        data: {
            getModules:true
        }, 
        dataType: "json",
    })

    $.each(modulos, function (i, item) {
       // console.log("MODULOS",i,item)
        if(!(i===0 || item.Des_Pad === null || item.Des_Pad === '')){
            $('#Tic_Mod').append($('<option>', { 
                value: item.Org_Niv,
                text : item.Des_Pad 
            }));
            $('#Mod_Tic_Mod').append($('<option>', { 
                value: item.Org_Niv,
                text : item.Des_Pad 
            }));
        }
    });

    // Crear Ticket
    const procesos = await $.ajax({
        url: '/administrador/api/tickets.php', 
        type: 'POST', 
        data: {
            getProcesses:true,
            Org_Niv:$("#Tic_Mod").val()
        }, 
        dataType: "json",
    })

    $("#Tic_Pro option").remove();
    $.each(procesos, function (i, item) {
        $('#Tic_Pro').append($('<option>', { 
            value: item.Org_Cod,
            text : item.Org_Des 
        }));
        $('#Mod_Tic_Pro').append($('<option>', { 
            value: item.Org_Cod,
            text : item.Org_Des 
        }));
    });

    

    // Crear Ticket

    const acciones = await $.ajax({
        url: '/administrador/api/tickets.php', 
        type: 'POST', 
        data: {
            getActions:true,
            Org_Cod:$("#Tic_Pro").val()
        }, 
        dataType: "json",
    })

    $("#Tic_Acc option").remove();
    $.each(acciones, function (i, item) {
        $('#Tic_Acc').append($('<option>', { 
            value: item.Pcs_Cod,
            text : item.Pcs_Lin 
        }));
    });

    

    // Cargar datos
    
      var fecIni = $('#txt_fec_ini').val();
      var fecFin = $('#txt_fec_fin').val();
      $('#tableResult').trigger('reloadGrid');
        $.getDataJson('/administrador/api/tickets.php',{searchFiltro:true, fechaIni:fecIni, fechaFin:fecFin},function(res){
              $("#tableResult").setRows(res['rows']);
      },function(f){ });
}

const onRate = () => {
    const Tic_Cal = $('input[name=rating]:checked').val()
    const Tic_Cal_Des = $('#Tic_Cal_Des').val()
    const Tic_Cod = $('#Tic_Cod').val()

    if(Tic_Cal){
        $.ajax({
            url: "/administrador/api/tickets.php", 
            type: 'POST', 
            data: {isRateAction:true, Tic_Cal, Tic_Cal_Des, Tic_Cod}, 
            dataType: "json",
        }).done(function (re){
            if(re.success===true){
                $('#Cal_Text').hide()
                $('#btnCalificar').hide()
                $('#Tic_Cal_Des').prop('disabled', true);
                $.alert('Su calificaci&oacute;n ha sido guardada');
            }else{
                $.alert('No se pudo realizar la accion!');
            }
        }).fail(function(){ $.alert(); });
    }
}

function setInputFilter(textbox, inputFilter) {
    ["input", "keydown", "keyup", "mousedown", "mouseup", "select", "contextmenu", "drop"].forEach(function(event) {
      textbox.addEventListener(event, function() {
        if (inputFilter(this.value)) {
          this.oldValue = this.value;
          this.oldSelectionStart = this.selectionStart;
          this.oldSelectionEnd = this.selectionEnd;
        } else if (this.hasOwnProperty("oldValue")) {
          this.value = this.oldValue;
          this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
        } else {
          this.value = "";
        }
      });
    });
  }
  
setInputFilter(document.getElementById("Tic_Tel"), function(value) {
    return /^\d*$/.test(value); 
});

var socket = io.connect("http://test.ofsercont.com:3001")
socket.emit('joinTicket', {})
socket.on('newData', loadTasks);
console.log('SOCKETIO')