$(function(){
                
    //Inicio Grid para presentar el detalle de factura
    $("#Lis_Cli").createGrid({
        postData: $("#frm_bus").getData("clientesAjax"), height: 295,
        colModel: [
            {label: 'C&oacute;d. Int.', name: 'Cli_Cod', width: 50, align: "left"},
            {label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50, align: "left"},
            {label: 'Cliente', name: 'cliente', width: 150, align: "left"},
            {label: 'Correo', name: 'Prs_Cor', width: 150, align: "left"},
            {label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                formatter:function(cellvalue, options, rowObject){
                    return $.getGridButton(cargarCliente, rowObject, 'Editar Cliente');
                }
            }
        ]
    }, false, "#Pag_Cli");
    
    
    $('#Ciu_Cod').createChosen('input-xs',{tabIndex:6, width:'100%',template:function (t,d){ return '<div class="over"><b>'+t+'</b></div><div class="over desc" style="font-size:11px;"><b>Provincia:</b> '+d['prov']+' <b>Pa&iacute;s:</b> '+d['pais']+'</div>';}});
    
    $("#radioec").change(function(){ 
        habilitar('ec',1);
        $('#Prs_Ced').attr('onchange','validar(1)');
        $('#lb_ec').attr('class','btn btn-success btn-xs');
        $('#lb_ex').attr('class','btn btn-default btn-xs');
        $('#spanec').show();$('#spanex').hide();
    });
    
    $("#radioex").change(function(){ 
        habilitar('ex',1);
        $('#Prs_Ced').attr('onchange','validar(2)');
        $('#lb_ex').attr('class','btn btn-success btn-xs');
        $('#lb_ec').attr('class','btn btn-default btn-xs');
        $('#spanex').show();$('#spanec').hide();
    });
    
    $('#Ide_Cod').change(function(){
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
            if(validaNoIdentif(cedula)['success']){ err=0; $('#Ide_Cod').val(cedula.length===10?2:1); $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ec'); }else{ err=1; $('#Ide_Cod').val(''); $('#Prs_Ced').fieldValid(false,validaNoIdentif(cedula)['message']); }
            break;
        case 2:
            if(cedula.length===13 && validaNoIdentif(cedula)['success']){err=0; $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ec');}else{ err=1; $('#Ide_Cod').val(1); $('#Prs_Ced').fieldValid(false,validaNoIdentif(cedula)['message']); }
            break;
        case 3:
            $('#Prs_Ced').fieldValid(true); err=0;
            break;
    }
}

function habilitar(op,val){
    $('#Prs_Ced').val('').focus();
    if(op==='ec'){
        $('#Ide_Cod').find('option').show();
        $('#Ide_Cod').attr('disabled',true);
        $('#Ide_Cod').val('');
    }else{
        $('#Prs_Ced').fieldValid('');
        $('#Ide_Cod').find('option').hide().end().find('option[data-tipo="Ex"]').show();
        $('#Ide_Cod').val(val);
        $('#Ide_Cod').attr('disabled',false);
    }
}

function searchCliente(ced,tipo){
    (tipo==='ec')?ced=ced.substring(0,10):ced;
    var oldced=$('#oldcedula').val().substring(0,10);
    if(ced!==oldced){
        $.post("",{searchCliente:true,Prs_Ced:ced}, function( response ) {
            if(response['exisCli']===true){
                $.alert('El n&uacute;mero de identificaci&oacute;n ingresado('+ced+') ya se encuentra registrado..!!');
                $('#Prs_Ced').val('').focus();
                $('#Ide_Cod').val('');
            }else{
                if(response['exisPer']===true){
                    $.createDialogConfirm('Desea sustituir los datos del cliente actual..!!',null,function(){
                        $('#formCliente').setData(response,false);
                        $('#Ciu_Cod').val(response['Ciu_Cod']).trigger('chosen:updated');
                    },function(){$('#Prs_Ced').val(oldced).focus(); $('#Ide_Cod').val(ide_cod); });
                    
                }
            }                         
        },'json').fail(function (){$.alert();});
    }
}

var pasaporte='',ide_cod=0;
function cargarCliente(cliente){
    $('#lista').moveComp('#modificar');
    if(cliente['Cli_Tic']==='J'){ $('.juridico').show();$('.natural').hide(); }
    if(cliente['Ide_Sri']==='P'){ pasaporte='P'; $("#radioex").trigger('change').prop("checked", true); $('#Prs_Ced').attr('onchange','validar(3)'); }else{ pasaporte='O'; $("#radioec").trigger('change').prop("checked", true); }
    $('#Ciu_Cod').val(cliente['Ciu_Cod']).trigger('chosen:updated');
    $('#oldcedula').val(cliente['Prs_Ced']);
    $('#formCliente').setData(cliente);
    $('#Prs_Ced').fieldValid(true);
    ide_cod=cliente['Ide_Cod'];
    (pasaporte!=='P')?validar(1):'';
    ($('#isRuc').parent())[pasaporte=='P'?'hide':'show']();					
    $('#isRuc').prop('checked',pasaporte!='P'&&cliente['Prs_Ced'].length===13);
}

function guardarCliente(){  
    if(err===1){$.alert('Debe ingresar un n&uacute;mero de identificaci&oacute;n v&aacute;lido'); return false;}
    $.saveDataJson("",$('#formCliente').getData('guardarCliente'), function( resp ){ $('#Lis_Cli').trigger('reloadGrid');});
}
function setTipoDoc(){
    var $Prs_Ced=$('#Prs_Ced'), Prs_Ced=$Prs_Ced.val(), isRuc=$('#isRuc').is(':checked');
    
    if(Prs_Ced.length>=10 && $.isNum(Prs_Ced)){
        Prs_Ced=Prs_Ced.substring(0,10);
        $Prs_Ced.val(isRuc?Prs_Ced+'001':Prs_Ced);
        $Prs_Ced.trigger('change');
    }else{
        $.alert("El numero "+Prs_Ced+" no puede convertirse en RUC!");
    }
}