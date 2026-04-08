
$(function(){    
    
    /*************************************
    * ONLOAD PAGINA
    **************************************/
    function inicio() {
        $( "#tabsSearch" ).createTabs();
        $( "#editDialog" ).createDialog({width:500,height:450,icon:'pencil'});
        $("#Tic_Fec_Ent").createDatePickers();
        $("#Aut_Cad").createDatePickers();
        $("#Tic_Fech_Cre_Sch").createDatePickers();
        $("#Tic_Fech_Ter_Sch").createDatePickers();
        
        // var d = $("#Tic_Fech_Cre_Sch").datepicker("getDate");
        // $('.datepickers').createDatePickers({checkAvailability:true,hideMsg:false,clean:true});
    }

    
    /* $("#Tic_Fech_Cre_Sch").createDatePickers();
        $("#Tic_Fech_Ter_Sch").createDatePickers(); */
    window.onload = inicio;
    var data = "";
    var tipoDoc = 0;
    /*
     * Fecha de entrega del ticket
     */
    /* $('#Tic_Fec_Ent').on("change",function(){
        var d = $("#Tic_Fec_Ent").datepicker("getDate");
    }); */
    
    $('#Tic_Fech_Cre_Sch').on("change",function(){
        var d = $("#Tic_Fech_Cre_Sch").datepicker("getDate");
    });

    $('#Tic_Fech_Ter_Sch').on("change",function(){
        var d = $("#Tic_Fech_Ter_Sch").datepicker("getDate");
    });

    /* OnClick para buscar con el boton */
    $('#btnSearch').on("click",function(e){
        e.preventDefault();
        let estado = $('#Tic_Est_Sch').val();
        let fechaIni = $('#Tic_Fech_Cre_Sch').val();
        let fechaFin = $('#Tic_Fech_Ter_Sch').val();
        let modCod = $('#Org_Mod_Sch').val();
        
        // console.log( 'HOLAAAAAA',estado, fechaIni, fechaFin)

        $('#tableResult').trigger('reloadGrid');
            $.getDataJson('/administrador/api/soporte.php',{searchFiltro:true, estado: estado, fechaIni: fechaIni, fechaFin: fechaFin, modCod: modCod},function(res){
                $("#tableResult").setRows(res['rows']);
            },function(f){            
            });
    });

  /* On Click nueva tarea */
  /*  $('#btnNueva').on("click", function(e){
        document.getElementById('modeTar').value = 0;     
        $('#editDialog').dialog("option","title","Nueva Tarea");      
        $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Guardar");
        $('#editDialog').dialog('open');
        $("#formDialog")[0].reset(); 
        var d = $("#Tic_Fec_Ent").datepicker("getDate");
   }); */

    //Inicio Grid para presentar la busqueda   
    $("#tableResult").createGrid({
        autowidth : false, shrinkToFit: true, height: 270,responsive:false,footerRow:true,
        autoheight: true,
        colModel: [
            { label: 'C&oacute;d.', name: 'Tic_Cod', width: 20, align: "center",key:true},
            { label: 'Fecha/Hora', name: 'Tic_Fec_Cre', width: 50, align: "center"},
            { label: 'Emp_Cod', name: 'Emp_Cod', hidden:true, width: 70, align: "center"},
            { label: 'Empresa', name: 'Emp_Nom', width: 70, align: "center"},
            { label: 'Usu_Cod', name: 'Usu_Cod', hidden:true, width: 50, align: "center"},
            { label: 'Usuario', name: 'Prs_Nom', width: 50, align: "center"},
            { label: 'Apellido', name: 'Prs_Ape', hidden:true, width: 50, align: "center"},
            { label: 'Modulo', name: 'Org_Mod', width: 50, align: "center"},
            { label: 'Módulo / Sección', name: 'Org_Mod', width: 80, align: "center", hidden: true ,
                formatter: function(cellvalue, options, rowObject){
                    var mod = cellvalue || '';
                    var sec = rowObject.Org_Sec || '';
                    return sec ? (mod + ' / ' + sec) : mod;
                }
            },
            { label: 'Seccion', name: 'Org_Sec', width: 50, align: "center"},    
            { label: 'Tema', name: 'Tic_Tem', width: 100, align: "center"},
            { label: 'Descripcion', name: 'Tic_Des', hidden:true, width: 150, align: "center"},
            { label: 'Descripcion', name: 'Tic_Des', hidden:true, width: 150, align: "center"},
            { label: 'Encargado', name: 'Ase_Cod', width: 50, align: "center"},
            { label: 'Fecha Soluci&oacute;n', name: 'Tic_Fec_Ter', width: 50, align: "center"},            
            { label: 'Estado', name: 'Tic_Est', width: 40, align: "center"},
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false,
                formatter:function(cellvalue, options, rowObject){
                    if(rowObject.Tic_Est=='Solucionado'){
                            return '<i title="Solucionado" class="fa fa-check-circle green"></i>';
                    } else if(rowObject.Tic_Est=='Pendiente'){
                            return '<i title="Pendiente" class="fa fa-hourglass-start orange"></i>';
                    } else if(rowObject.Tic_Est=='En Proceso' ){
                            return '<i title="En Proceso" class="fa fa-hourglass-half yellow"></i>';
                    } else{
                        // Reabierto
                            return '<i title="Reabierto" class="fa fa-info-circle red"></i>';
                    }
                }
            },
            { label: 'Calificaci&oacute;n', name: 'Tic_Cal', width: 10, align: "center", hidden: true},
            { label: '&nbsp;', name: 'act4', width: 20, align: 'center',viewable: false, formatter:function(cellvalue, options, rowObject){
                if(rowObject.Tic_Est=='Pendiente'){
                    return $.getGridButton(tomarTicket, rowObject, 'Tomar', 'glyphicon glyphicon-arrow-right', '', 'warning');
                } else if(rowObject.Tic_Est=='En Proceso'){
                    return $.getGridButton(cargarDoc, rowObject, 'Ver', 'glyphicon glyphicon-arrow-right', '', 'default');
                } else if(rowObject.Tic_Est=='Solucionado'){
                    return $.getGridButton(cargarDoc, rowObject, 'Ver', 'glyphicon glyphicon-arrow-right', '', 'success');
                } else{                   
                    return $.getGridButton(cargarDoc, rowObject, 'Realizar', 'glyphicon glyphicon-arrow-right','', 'danger');
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
                // --- AGREGAR MÓDULOS AL SELECT SIN DUPLICAR ---
                let select = $("#Org_Mod_Sch");
                // Crear un set de módulos existentes (solo nombres)
                let existentes = new Set();

                select.find("option").each(function(){
                    existentes.add($(this).text().trim());
                });

                if($.varValid(data.rows)){
                    $.each(data.rows, function(i, row){
                        // let cod = row.Org_Cod || ""; // puede venir vacío
                        let mod = (row.Org_Mod || "").trim();

                        // Si ya existe el módulo por nombre → no lo agregamos
                        if (mod !== "" && !existentes.has(mod)) {
                            existentes.add(mod);
                            select.append("<option value='" + mod + "'>" + mod + "</option>");
                        }
                    });
                }
            }

    }, true, "#tableResultPager",{}); 

    $("#btnExcel").on("click", function(){
        // Timeout de seguridad para ocultar el loader si el sistema de cookies/iframe falla
        var loaderTimeout = setTimeout(function(){
            if($('#loader').is(':visible')) {
                $('#loader').fadeOut("slow");
            }
            // Limpiar el intervalo de verificación de cookies si existe
            if(typeof fileDownloadCheckTimer !== 'undefined') {
                window.clearInterval(fileDownloadCheckTimer);
            }
        }, 3000); // 3 segundos como timeout de seguridad
        
        // Llamar a la exportación
        $("#tableResult").jqGrid('exportGridExcel',{nombre:'Soporte Tecnico',hoja:'Soporte',caption:true});
        // Si el loader se oculta normalmente antes del timeout, cancelar el timeout
        var checkLoader = setInterval(function(){
            if(!$('#loader').is(':visible')) {
                clearTimeout(loaderTimeout);
                clearInterval(checkLoader);
            }
        }, 500);
    })

});

// SOCKETS

var socket = io.connect("http://test.ofsercont.com:3001")
socket.emit('joinTicket', {})
//console.log('SOCKETIO')
socket.on('newData', cargarDatos);

function cargarDatos(data){
    console.log('CARGANDO DATOS SOCKET...',data.msg)
    var est = $('#Tic_Est_Sch').val();
    let fechaIni = $('#Tic_Fech_Cre_Sch').val();
    let fechaFin = $('#Tic_Fech_Ter_Sch').val();
    let modCod = $('#Org_Mod_Sch').val();
    let isTableViewHidden = $('#documentoSearch').is(":hidden");
    // console.log('IS TABLE VIEW HIDDEN', isTableViewHidden)
    if(!isTableViewHidden){
        $('#tableResult').trigger('reloadGrid');
            $.getDataJson('/administrador/api/soporte.php',{searchFiltro:true, fechaIni: fechaIni, fechaFin: fechaFin, estado: est, modCod: modCod},function(res){
                $("#tableResult").setRows(res['rows']);
        },function(f){ });
    }
    
}


function validar (row) {
    var estado = 'V';
    $.createDialogConfirm("¿Esta seguro de realizar esta <b>acci&oacute;n</b> ?",row,function(){
        $.getDataJson('/administrador/api/soporte.php',{setEstado:true, Tic_Cod: row.Tic_Cod, Tic_Est: estado, Tic_Validar:true},function(response){
        $('#btnSearch').trigger('click');
        // $('#tableResult').trigger('reloadGrid');
        })
    },function(f){
    });
}

function anular (row) {
    var estado = 'I';
    $.createDialogConfirm("Esta seguro de realizar esta <b>acci&oacute;n</b> ?",row,function(){
        $.getDataJson('/administrador/api/soporte.php',{setEstado:true, Tic_Cod: row.Tic_Cod, Tic_Est: estado},function(response){            
        $('#btnSearch').trigger('click');
        })
    },function(f){           
        });        
}

function tomarTicket(row){
    console.log("TOMANDO TICKET...")
    $.ajax({
        url: '/administrador/api/soporte.php', 
        type: 'POST', 
        data: {
            takeTicket:true,
            Tic_Cod: row['Tic_Cod'],
            Emp_Cod: row['Emp_Cod']
        }, 
        dataType: "json",
    }).done(function (re){
        if(re.success===true){
            $.alert('Se te ha asignado el ticket. Eres el responsable de cumplir con el soporte!');
            $('#editDialog').dialog('close');
            // console.log("EMITIENDO newData")
            socket.emit('newData', {});
                loadTasks(false);
                // $('#tableResult').trigger('reloadGrid');
        }else{
            $.alert('No se pudo realizar la acci&oacute;n!');
        }
}).fail(function(e){ 
    console.log(e)
    $.alert(); });
}

function cargarDoc(row){
        $('#documentoSearch').hide();
        $('#documentoMain').show();

        $('#Tic_Des').prop('disabled', false);
    $('#Tic_Obs').prop('disabled', false);
    $('#saveButton').show();
    // $('#Tic_Evi_Sol_Arc').val('');
    // $('#Tic_Evi_Sol').hide();
    $('#Tic_Evi_Pro').hide();
    
        $.getDataJson('/administrador/api/soporte.php',{'cargarDoc':true,'Tic_Cod':row['Tic_Cod'], 'Emp_Cod': row['Emp_Cod']},function(resp){
            var info = resp['rows'];
            console.log('TICKET',info)
            for (let index = 0; index < info['Tic_Cal']; index++) {
                $('#Tic_Cal').append('<span class="fa fa-star checked"></span>');
            }
            for (let index = 0; index < 5 - info['Tic_Cal']; index++) {
                $('#Tic_Cal').append('<span class="fa fa-star"></span>');
            }

            $('#saveButton').text('Guardar');
            $('#Tic_Cod_Mod').val('0');

            $('#Tic_Cod').val(info['Tic_Cod']);
            $('#Emp_Cod').val(info['Emp_Cod']);
            $('#Tic_Est').val(info['Tic_Est']);
            $(`select option[value="${info['Tic_Est']}"]`).prop('selected', true);
            $('#Tic_Tip').val(info['Tic_Tip']);
            $(`select option[value="${info['Tic_Tip']}"]`).prop('selected', true);
            $('#Tic_Tel').text(info['Tic_Tel']);
            $('#Org_Mod').text(info['Org_Mod']);
            $('#Org_Sec').text(info['Org_Sec']);
            $('#Tic_Fec_Cre').text(info['Tic_Fec_Cre']);
            $('#Tic_Fec_Ter').text(info['Tic_Fec_Ter']);
            $('#Prs_Nom').text(info['Prs_Nom']);
            $('#Tic_Tem').text(info['Tic_Tem']);
            $('#Tic_Cal_Des').text(info['Tic_Cal_Des']);

            if(info['Tic_Val_Nom'] != null){	$('#Tic_Val_Re').text(info['Tic_Val_Nom']);}
            else{	$('#Tic_Val_Re').text('No Validado');}

            $('#Emp_Cod_Re').text(info['Emp_Nom']);
            $('#Tic_Fec_Ent_Re').text(info['Tic_Fec_Ent']);
            $('#Tic_Des_Re').text(info['Tic_Tem'] +'\n'+ info['Tic_Des']);

        if(!(info['Tic_Evi_Pro']=="" || info['Tic_Evi_Pro'] == null)){
            $('#Tic_Evi_Pro').show();
            $("#Tic_Evi_Pro").attr('href', info['Tic_Evi_Pro']);
        }

        if(info['Tic_Cod']!=null){
            $('#Tic_Des').val(info['Tic_Tem'] +'\n'+ info['Tic_Des']);
            $('#Tic_Obs').val(info['Tic_Obs']);
            $('#Tic_Cod_Mod').val('1');
            $('#saveButton').text('Modificar');
            
            /* if(!(info['Tic_Evi_Sol_Arc']=="" || info['Tic_Evi_Sol_Arc']==null)){
                $('#Tic_Evi_Sol').show();
                $("#Tic_Evi_Sol").attr('href', info['Tic_Evi_Sol_Arc']);
            } */

        }else{
            $('#Tic_Des').val('');
            $('#Tic_Obs').val('');
        }

        if(info['Tic_Est'] == 'Solucionado'){
            $('#Tic_Des').prop('disabled', true);
            $('#Tic_Obs').prop('disabled', true);
            $('#saveButton').hide();
        }

        });
}

function atras(){
    $('#documentoMain').hide();
    $('#documentoSearch').show();
    // $('#tableResult').trigger('reloadGrid');
    loadTasks(false)
    $("#Tic_Cal span").remove();
}

//Realizar y Modificar Tarea
function guardar(){
    let formObj = {
        Tic_Cod: $('#Tic_Cod').val(),
        Tic_Est: $('#Tic_Est').val() === 'Pendiente' ? '0' : ($('#Tic_Est').val() === 'En Proceso' ? '1' : ($('#Tic_Est').val() === 'Reabierto' ? '2' : '3')),
        Tic_Obs: $('#Tic_Obs').val(),
        Tic_Tip: $('#Tic_Tip').val() === 'Tecnico' ? '0' : '1',
        Emp_Cod: $('#Emp_Cod').val(),
        save:true,
    }
    
    $.ajax({
            url: '/administrador/api/soporte.php', 
            type: 'POST', 
            data: formObj, 
            dataType: "json", 
            /* async: false, 
            cache: false, 
            contentType: false, 
            processData: false */
        }).done(function (re){
            if(re.success===true){
                $.alert('Se ha registrado con &eacute;xito!');
                $("#Tic_Cal span").remove();
                $('#documentoMain').hide();
                $('#documentoSearch').show();
                socket.emit('newData', {});
                loadTasks(false);
                $('#Tic_Des').val('');
                $('#Tic_Obs').val('');
            }else{
                $.alert('No se pudo realizar la acci&oacute;n!');
            }
    }).fail(function(e){
        console.log('ERROR',e)
        $.alert(); 
    });
}

//Crear y modificar Tarea
function guardarTarea(){
    
    var formData = new FormData($('#formDialog')[0]);
    $.ajax({
            url: '/administrador/api/soporte.php', 
            type: 'POST', 
            data: formData, 
            dataType: "json", 
            async: true, 
            cache: false, 
            contentType: false, 
            processData: false
        }).done(function (re){
            if(re.success===true){
                $.alert('Se ha registrado con &eacute;xito!');
                $('#editDialog').dialog('close');
                    loadTasks(false);
                    // $('#tableResult').trigger('reloadGrid');
            }else{
                $.alert('No se pudo realizar la acci&oacute;n!');
            }
    }).fail(function(){ $.alert(); });
}



//Cargar los datos en el dialog para modificar
function modificarTarea(row)
{
    // $('#Tic_Evi_Sol_Arc').val('');
    document.getElementById('modeTar').value = 1;

    $.getDataJson('/administrador/api/soporte.php',{'cargarEditar':true,'Tic_Cod':row['Tic_Cod']},function(resp){
        document.getElementById('Tic_Cod').value = resp['rows']['Tic_Cod'];
        document.getElementById('Tic_Cre').value = resp['rows']['Tic_Cre'];
        document.getElementById('Tic_Res').value = resp['rows']['Tic_Res'];
        document.getElementById('Emp_Cod').value = resp['rows']['Emp_Cod'];
        document.getElementById('Tic_Fec_Ent').value = resp['rows']['Tic_Fec_Ent'];
        document.getElementById('Tic_Des').value = resp['rows']['Tic_Des'];
    });

    $('#editDialog').dialog("option", "title", "Editar");
    $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Modificar");
    $('#editDialog').dialog('open');
}

/*
* Metodo para cargar tareas
*/
function loadTasks(resetDates){
    if(resetDates){
        $("#Tic_Fech_Cre_Sch").createDatePickers();
        $("#Tic_Fech_Ter_Sch").createDatePickers();
    }
    var est = $('#Tic_Est_Sch').val();
    let fechaIni = $('#Tic_Fech_Cre_Sch').val();
    let fechaFin = $('#Tic_Fech_Ter_Sch').val();
    let modCod = $('#Org_Mod_Sch').val();

    $('#tableResult').trigger('reloadGrid');
    $.getDataJson('/administrador/api/soporte.php',{searchFiltro:true, fechaIni: fechaIni, fechaFin: fechaFin, estado: est, modCod: modCod},function(res){
        // console.log("RESPONSE",res)
        $("#tableResult").setRows(res['rows']);
    },function(e){ console.log('ERROR',e)});
}


/* window.addEventListener('load', function () {
alert("It's loaded!")
}) */

    