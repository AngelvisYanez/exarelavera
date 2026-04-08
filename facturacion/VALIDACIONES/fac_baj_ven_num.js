var valid_fin=false,valid_inicio=false;
$(function(){
    $('.datepickers').createDatePickers({checkAvailability:true,hideMsg:false}).mask("9999-99-99",{placeholder:"_"});
    cargarDocumentos();
    $('#Tic_Cod').on('change',function(event){
        var tipo_doc=$(this).find('option:selected').data();
        cargarAutorizaciones(tipo_doc.Tic_Cod,tipo_doc.Pun_Sri);
    });
    
    $('#Tipo_Eliminacion').on('change',function(event){
        var evaluar= $(this).find('option:selected').val()*1;
        switch (evaluar) {
            case 1:
                $('#panel_secuencia').removeClass('hidden');
                $('#panel_uno_uno').addClass('hidden');
                $('[name=Secuencia_Fin]').attr('required');
                $('[name=Secuencia_Ini]').attr('required');                
                break;
            case 2:
                $('#panel_secuencia').addClass('hidden');
                $('#panel_uno_uno').removeClass('hidden');
                $('[name=Secuencia_Fin]').removeAttr('required');
                $('[name=Secuencia_Ini]').removeAttr('required'); 
                break;
        }
    }).trigger('change');
    
    $('#add_num').on('click',function(){
        var Aut=$('#Aut_Cod').find(':selected');
        var Vet_Num=$('#numero_nuevo').val();
        if(Aut.val()>0){
            if(validarNumero(Vet_Num,Aut.data('Aut_Ini'),Aut.data('Aut_Fin'))){
               listarNumero(Vet_Num,Aut.data('Aut_Sri'),Aut.data('Pun_Sri'));
               $('#numero_nuevo').val('');
            }
        }else{
            alertaAuto('seleccione una autorizacion','#Aut_Cod','right');
        }
    });
    
    $('[name=Secuencia_Ini]').on('change',function(){
        if($('#Aut_Cod').val()*1>0){
            var numero=$(this).val()*1;
            var num_ini=$('#Aut_Cod').find('option:selected').data('Aut_Ini')*1;
            var num_fin=$('#Aut_Cod').find('option:selected').data('Aut_Fin')*1;
            if(num_ini>numero || numero>num_fin){
                $('[name=Secuencia_Ini]').fieldValid(false,'Fuera de rango');
                valid_inicio=false;
            }else{
                if($('[name=Secuencia_Ini]').val()*1>$('[name=Secuencia_Fin]').val()*1&&$('[name=Secuencia_Fin]').val()*1>0){
                    $('[name=Secuencia_Ini]').fieldValid(false,'Fuera de rango');
                    valid_inicio=false;
                }else{
                    $('[name=Secuencia_Ini]').fieldValid(true);
                    valid_inicio=true;
                }
            }
        }else{
            $('[name=Secuencia_Ini]').fieldValid(false,'Seleccione una autorización');
            valid_inicio=false;
        }
    });
    $('[name=Secuencia_Fin]').on('change',function(){
        if($('#Aut_Cod').val()*1>0){
            var numero=$(this).val()*1;
            var num_ini=$('#Aut_Cod').find('option:selected').data('Aut_Ini')*1;
            var num_fin=$('#Aut_Cod').find('option:selected').data('Aut_Fin')*1;
            if(num_ini>numero || numero>num_fin){
                $('[name=Secuencia_Fin]').fieldValid(false,'Fuera de rango');
                valid_fin=false;
            }else{
                if($('[name=Secuencia_Ini]').val()*1>$('[name=Secuencia_Fin]').val()*1&&$('[name=Secuencia_Ini]').val()*1>0){
                    $('[name=Secuencia_Fin]').fieldValid(false,'Fuera de rango');
                    valid_fin=false;
                }else{
                    $('[name=Secuencia_Fin]').fieldValid(true);
                    valid_fin=true;
                }
                
            }
        }else{
            $('[name=Secuencia_Fin]').fieldValid(false,'Seleccione una autorización');
            valid_fin=false;
        }
    });
    
    $('#form_anular').on('submit',function(event){
        event.preventDefault();
        if(validaDocumento()){
            event.submit();            
        }
    });
    
    $('#Caj_Fec').on('change',function(){
        $('#Aut_Cod').trigger('change');
    });

    $('#numero_nuevo').on('keyup',function(e){
      if(e.which===32){
         $('#add_num').trigger('click');
      }
    });

   $('#Aut_Cod').on('change',function(){
       $('[name=Secuencia_Ini]').trigger('change');
       $('[name=Secuencia_Fin]').trigger('change');

       var autorizacion=$(this).find(':selected').data();
       var fecha= $('#Caj_Fec').val();
       
       if($('#Aut_Cod').val()*1>0){
           if (fecha<autorizacion.Aut_Fci || fecha>autorizacion.Aut_Cad){
                alertaAuto('fecha fuera de rango de autorizacion','#Caj_Fec','right');
                $('#span_aut').attr('title','Información de autorización');
                $('#Aut_Cod').val(0);
            }else{
                $('#span_aut').attr('title','Inicio :&emsp; <i>'+autorizacion.Aut_Fci+'</i></br>Caduca:&emsp; <i>'+autorizacion.Aut_Cad+'</i></br>Rango:&emsp;<i>'+autorizacion.Aut_Ini+' - '+autorizacion.Aut_Fin+'</i> </br><font color=' +(autorizacion.Aut_Est==="A"?'green':'red')+'>' +(autorizacion.Aut_Est==="A"?'Activo':'Inactivo')+'</font>'); 
            }
       }else{
            $('#span_aut').attr('title','Información de Autorización');           
       }
   });
   
    $('#close_button').on('click',function(){
    $('#tabs').addClass('hidden');
   });
   
   cargar_cliente();
   
   
    //$( "#acordion" ).accordion;
    //$('#tabs').addClass('hidden');
});

function alertaAuto(mensaje,componente,direccion){
    $(componente).flyout('hide');
    $(componente).createFlyout(mensaje,{icon:'exclamation',placement:direccion,timeDismis:2000});
    $(componente).flyout('show');
}

function cargarDocumentos(){
    var elemento=$('[name=Tic_Cod]');
    elemento.empty();
    elemento.append($('<option/>').attr({ 'value':''}).text('Seleccione...').data('Tic_Cod',''));
    $.getDataJson('',{'getDocuments':true},function(resp){
        //console.log(resp);
        $(resp.documents).each(function(i,doc){
            //console.log(doc);
            var option = $('<option/>'); 
            option.attr({ 'value': doc.Tic_Cod }).text(doc.Tic_Sri+' -'+doc.Tic_Des).data(doc);
            elemento.append(option);
        });
        
    },function(err){
        console.log(err);
    });
}

function cargarAutorizaciones(Tic_Cod,Pun_Sri){
    //console.log(Tic_Cod,Pun_Sri);
    var elemento=$('[name=Aut_Cod]');   
    elemento.empty();
    elemento.append($('<option/>').attr({ 'value':0}).text('Seleccione...').data('Aut_Cod',''));
    if(Tic_Cod!==''){
        $.getDataJson('',{'Tic_Cod':Tic_Cod,'autorizaAjax':true},function(resp){
            $(resp.autorizaciones).each(function(i,aut){
               // console.log(aut);
                var option = $('<option/>'); 
                option.attr({ 'value': aut.Aut_Cod }).addClass(aut.Aut_Est==='A'?'activo':'inactivo').text(aut.Aut_Sri).data(aut);
                elemento.append(option);
            });
        },function(err){
            console.log(err);
        });
    }
    
}

function listarNumero(Vet_Num,Aut_Sri,Pun_Sri){
    if(Vet_Num*1>=0){
        $.getDataJson('',{'Vet_Num':Vet_Num,'Aut_Sri':Aut_Sri,'Pun_Sri':Pun_Sri,'existeNumdoc':true},function(resp){
            //console.log('existe',resp['existe']);
            if(resp['existe']===false){
                agregarNumero(Vet_Num);
            }else{
                alertaAuto('ya se encuentra registrado','#numero_nuevo','right_top');
            }
        },function(err){
            console.log(err);
        });
    }else{
        alertaAuto('No puede agregar este número','#numero_nuevo','right_top');
    }
}

function agregarNumero(Vet_Num){
    var elemento=$('#list_numeros');
    //console.log(Vet_Num);
    var option = $('<span/>');
    
    option.attr({ 'value': Vet_Num }).text(Vet_Num).data('Vet_Num',Vet_Num).addClass('btn btn-danger').attr('title','Quitar').on('click',function(){
        $(this).remove();
    }).css('display','inline-block');
    elemento.append(option);
}

function validarNumero(Vet_Num,inicio,fin){
    if(inicio*1<=Vet_Num*1&&Vet_Num*1<=fin*1){
        if(getNumerosLista().indexOf(Vet_Num*1)>=0){
            alertaAuto('ya ha sido agregado','#numero_nuevo','right');
            return false;
        }else{
            return true;        
        }
    }else{
        
        alertaAuto('Fuera de rango de autorización','#numero_nuevo','right');
        return false;
    }
}

function validaDocumento(){
    var Aut_Cod=$('#Aut_Cod').val()*1;
    var Vet_Num_Array=[];
    if(Aut_Cod>0){
        Vet_Num_Array=generaVet_Num($('#Tipo_Eliminacion').val()*1);
        if(Vet_Num_Array===false){
            return false;
        }else{
            $('#tabs').find('.form-group').empty();
            $.createDialogConfirm('¿Desea anular estos Documentos?',Vet_Num_Array,saveDocument);
        }
    }else{
        $.alert('Elija una Autorización');
        return false;
    }
    
}

function saveDocument(numeros){
    var Aut=$('#Aut_Cod').find(':selected');
     $.saveDataJson('',{'Vet_Num_Array':numeros,'Aut_Sri':Aut.data('Aut_Sri'),'Pun_Sri':Aut.data('Pun_Sri'),'Caj_Fec':$('#Caj_Fec').val(),'Tic_Cod':Aut.data('Tic_Cod'),'Aut_Cod':Aut.data('Aut_Cod'),'saveDocument':true},function(resp){
                if(resp['success']){
                    $('#tabs').removeClass('hidden');
                    if(resp['no_registrados'].length>0){
                        $('#tabs-2').html('<textarea rows="4" class="form-control" readOnly>'+resp['no_registrados']+'</textarea>');                        
                    }else{
                        $('#tabs-2').html('<textarea rows="2" class="form-control" readOnly>SE ANULARON TODOS LOS NÚMEROS ESPECIFICADOS</textarea>');
                    }
                    if(resp['anulados'].length>0){
                        $('#tabs-1').html('<textarea rows="4" class="form-control" readOnly>'+resp['anulados']+'</textarea>');                        
                    }else{
                         $('#tabs-1').html('<textarea rows="2" class="form-control" readOnly>NO SE ANULARON LOS NÚMEROS ESPECIFICADOS</textarea>');        
                    }
                }
                
            },function(err){
                console.log(err);
            });
}

function generaVet_Num(tipo){
    
    var inicio,fin;
    inicio=$('[name=Secuencia_Ini]').val()*1;
    fin=$('[name=Secuencia_Fin]').val()*1;
    var arr=[];
    if(tipo===1){
        if(valid_inicio&&valid_fin){
            for (var i = inicio, max = fin; i <= max; i++) {
                arr.push(i);
            }            
        }else{
            $.alert('Revise los números de Documentos');
            return false;
        }
        
    }else{
        $('#list_numeros').find('span').each(function(){
            //console.log('lista de uno a uno: ',$(this).html()*1);
            if($(this).html()*1>0){
                arr.push($(this).html()*1);                
            }else{
                return false;
            }
        });
    }
    if(arr.length<=0){
        
        return false;
    }
    return arr;
}

function getNumerosLista(){
    var arr=[];
    $('#list_numeros').find('span').each(function(){
        if($(this).html()*1>0){
            arr.push($(this).html()*1);
        }
    });
    
    return arr;
}

function cargar_cliente(){
    $.getDataJson('',{getCliente:true},function(resp){
        if($(resp['cliente']).length>0){
            $('#Cliente_Nombre').val(resp['cliente'].Prs_Ape);
        }else{
            $('#Cliente_Nombre').val('No Parametrizado Consumidor Final');
        }
    },function(resp){
        
    }); 
}



