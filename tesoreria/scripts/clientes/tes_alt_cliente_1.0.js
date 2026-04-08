$(function(){
    $('#Ciu_Cod').createChosen('input-xs',{tabIndex:6, width:'100%',template:function (t,d){ return '<div class="over"><b>'+t+'</b></div><div class="over desc" style="font-size:11px;"><b>Provincia:</b> '+d['prov']+' <b>Pa&iacute;s:</b> '+d['pais']+'</div>';}});

    $("#radioec").change(function(){
        $('#Prs_Ced').attr('onchange','validar(1)');habilitar('ec',1);
        $('#lb_ec').attr('class','btn btn-success btn-xs');
        $('#lb_ex').attr('class','btn btn-default btn-xs');
        $('#spanec').show();$('#spanex').hide();clear();
    });

    $("#radioex").change(function(){
        clear();habilitar('ex',7);
        $('#Prs_Ced').attr('onchange','validar(2)');
        $('#lb_ex').attr('class','btn btn-success btn-xs');
        $('#lb_ec').attr('class','btn btn-default btn-xs');
        $('#spanex').show();$('#spanec').hide();
    });

    $('#Ide_Cod').change(function(){
        $('#Prs_Ced').val('').focus();
        if(this.value*1===1){
            $('#Prs_Ced').attr('onchange','validar(2)');
        }else{
            $('#Prs_Ced').attr('onchange','validar(3)');
        }
        habilitar('ex',this.value);
    });
});

var err=0;
function validar(op){
    var cedula=$('#Prs_Ced').val();
    switch(op){
        case 1:
            if(validaNoIdentif(cedula)['success']){  err=0; $('#Ide_Cod').val(cedula.length===10?2:1); $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ec'); }else{ err=1; $('#Ide_Cod').val(''); $('#Prs_Ced').fieldValid(false,validaNoIdentif(cedula)['message']); }
            break;
        case 2:
            if(cedula.length===13 && validaNoIdentif(cedula)['success']){ err=0; $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ec');}else{ err=1; $('#Ide_Cod').val(1); $('#Prs_Ced').fieldValid(false,validaNoIdentif(cedula)['message']); }
            break;
        case 3:
            err=0;
            $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ex');
            break;
    }
}

function habilitar(op,val){
    var lon_ced=$('#Prs_Ced').val().length; $('#Prs_Ced').fieldValid('');
    if(op==='ec'){
        $('#Ide_Cod').find('option').show();
        $('#Ide_Cod').attr('disabled',true);
        $('#Ide_Cod').val(lon_ced===10?2:1);
    }else{
        $('#Ide_Cod').find('option').hide().end().find('option[data-tipo="Ex"]').show();
        $('#Ide_Cod').val(val);
        $('#Ide_Cod').attr('disabled',false);
    }
}

function searchCliente(ced,tipo){
    (tipo==='ec')?ced=ced.substring(0,10):ced;
    $.post("",{searchCliente:true,Prs_Ced:ced}, function( response ) {
        console.log('SEARCHCLIENTE', response)
        if(response['existe']===true){
            $.alert('El cliente '+ced+' ya se encuentra registrado..!!');
            clear();
        }else{
            $('#Ciu_Cod').val(response['Ciu_Cod']).trigger('chosen:updated');
            $.extend(response,{Prs_Ced:$('#Prs_Ced').val(),Ide_Cod:$('#Ide_Cod').val()});
            $('#formCliente').setData(response,false);
        }
    },'json').fail(function (){$.alert();});
}

function clear(){
    $('#formCliente').setData({Cli_Tic:'N',Prs_Ciu:'Ec',Prs_Sex:'M'});
    $('#Prs_Ced').val('').focus();
    $('.juridico').hide();$('.natural').show();
}

function guardarCliente(){
    if(err===1){$.alert('Debe ingresar un n&uacute;mero de identificaci&oacute;n v&aacute;lido'); return false;}
    $.saveDataJson(
        "",
        $('#formCliente').getData('guardarCliente'),
        function( resp ){
             $("#radioec").trigger('change'); clear(); 
            console.log(resp)
            });
}
