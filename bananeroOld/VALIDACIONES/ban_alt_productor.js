$(function(){

	$('#ProductorForm').submit(function(){
		$.saveDataJson('',$.extend($('#ProductorForm').data(),$('#ProductorForm').getData(),{'saveProductor':true}),function(res){
			if ($('#ProductorForm').data('Prd_Cod')*1>0){
        $('#searchGrid').Search('#searchProductor','searchProductor');
        showSearch();
      }
		})
		return false;
	});

	$('#provCreateDialog').createDialog({icon:'plus', width:500, height:430});
	$.createSearchDialog('provDialog',[
        { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15,align:"center",hidden:true },                                
        { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },                      
        { label: 'Proveedor', name: 'proveedor', width: 100},
        { label: 'Cont.', name: 'Prv_Con', width: 20,align:"center", labelLong:'Obligado a Llevar Contabilidad', formatter:'truefalse', formatoptions:{msg:false}  }, 
        { label: 'Espe.', name: 'Prv_Esp', width: 20,align:"center", labelLong:'Contribuyente Especial', formatter:'truefalse', formatoptions:{msg:false} }, 
        { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, 
        	formatter: 'gridButton',formatoptions:{action:selectProvee, title:'Cargar Proveedor', icon:'arrow-right', type:'success' } 
    	}
    ],null,null,null,{headertitles:true},{ title:'Proveedor', text:'Prs_Ced' }); 

	cargarCiudades().then(ciudades=>{ciudades.map(ciu=>{let option=$(`<option value=${ciu.Ciu_Cod}>${ciu.Ciu_Des}</option>`);$('select[name="Ciu_Cod"]').append(option)})}).catch(msg=>console.log(msg));
	cargarDocumentos().then(docs=>{docs.map(doc=>{let option=$(`<option value=${doc.Ide_Cod}>${doc.Ide_Des}</option>`);$('#Ide_Cod').append(option)})}).catch(msg=>console.log(msg));

});


function validaNoIdentif(number){
        var digitos = number.split(""), dto=digitos.length, acu=0, resp={success:false,message:''}, 
        coef={'NA':[2,1,2,1,2,1,2,1,2],'PU':[3,2,7,6,5,4,3,2,0],'PR':[4,3,2,7,6,5,4,3,2]}, modulo, acum=0;
        if(dto===0) resp['message']='No has ingresado ning\u00fan dato!'; 
        else{
         for(var i=0; i<dto; i++) if(!isNaN(digitos[i])){ digitos[i]=digitos[i]*1; acu = acu+1; }
         if(acu===dto){
          var tipo = digitos[2];
          if (tipo===7||tipo===8) resp['message']='"El tercer d\u00edgito ingresado es inv\u00e1lido"'; else{ tipo=(tipo<6?'NA':(tipo===6?'PU':(tipo===9?'PR':''))); modulo=(tipo==='NA'?10:11); resp['tipo_abrev']=tipo; resp['tipo']=(tipo==='NA'?'Natural':(tipo==='PR'?'Privada':(tipo==='PU'?'P\u00fablica':''))); }
              if(dto!==10&&dto!==13){ resp['message']='La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; }else{ resp['doc_abr']=(dto===10?'C':(dto===13?'R':'')); resp['doc']=(dto===10?'C\u00E9dula':(dto===13?'R.U.C.':'')); }   
              if(number.substring(0,2)*1>24) resp['message']='Los dos primeros d\u00EDgitos no pueden ser mayores a 24.';	
              if(dto===13){
                      if(number.substring(10,13)!=='001') resp['message']='Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.'; 	
                      if(tipo==='PU'&&number.substring(9,13)!=='0001') resp['message']='El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
              }else if((tipo==='PU'||tipo==='PR')) resp['message']='El R.U.C. de las empresas '+resp['tipo']+'s deben tener 13 digitos!';
              if(resp['message'].length>0) return resp; 

              for(var a=0;a<9;a++){
                      var resul=digitos[a]*coef[tipo][a];
                      acum+=(resul-(tipo==='NA'&&resul>=10?9:0));
              }	
              var residuo=acum%modulo, digitoVerificador = residuo===0 ? 0: modulo - residuo;
              if(digitos[(tipo==='PU'?8:9)]!==digitoVerificador) resp['message'] = 'El n\u00famero de '+resp['doc']+' de la '+(tipo==='NA'?'Persona Natural':'Empresa '+resp['tipo'])+' ingresado es inv\u00E1lido!';

              if(resp['message'].length===0) resp['success']=true;
         }else resp['message']="ERROR: Solo debe contener d\u00EDgitos!";
        }
        return resp;
    }

function cargarCiudades(){
	return new Promise((resolve,reject)=>{
		$.getDataJson('',{'cargarCiudades':true},function(resp){
			resolve(resp.data);
		},function(err){
			reject(err);
		});
	});
}

function cargarDocumentos(){
	return new Promise((resolve,reject)=>{
		$.getDataJson('',{'cargarDocumentos':true},function(resp){
			resolve(resp.data);
		},function(err){
			reject(err);
		});
	});
}

// Revisa si existe el proveedor
// buscar una persona
function searchProvee(ced){            
    $.post("",{'provAjax2':true,'Prs_Ced':ced.substring(0,10)}, function( response ) {
        if(response['total']*1===1){                     
           if(!$.varValid(response['rows'][0]['Prv_Cod'])||response['rows'][0]['Prv_Cod'].length===0){
               $('#provCreateForm').setData(response['rows'][0]);    
               $('#Prv_Tic').val(validaNoIdentif(response['rows'][0]['Prs_Ced'])['tipo_abrev']==='NA'?'N':'J').trigger('change'); 
           }else{
               selectProvee(response['rows'][0]);
               $('#provCreateDialog').dialog('close');
           }
        }
    },'json').fail(function (){ $('#provCreateForm').setData({}); }).always(function (){});
 }



function selectProvee(provee){
    var reset=($('#reset').val()!=='0');
    $('#provFormTemp').setData($.extend(provee,{op_opciones:'c'})).find('.dialogSearch').addClass('x');
    $('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-'+(provee['Prv_Con']==='S'?'ok green':'remove blue'));
    $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-'+(provee['Prv_Esp']==='S'?'ok green':'remove blue'));
    $('#provDialog').dialog('close');
    
    $('#docuFormTemp').setData({For_Cod:1,Tri_Cod:2},reset).find(':input').removeAttr('readonly');
    $('#Ciu_Cod').trigger('chosen:updated');
    $('.validate:not(.ret_num)').find('i').removeAttr('class');
    $('#For_Cod').val(1).removeAttr('disabled').trigger('change');
    $('.pagoCredito').hide();
    $('#Cpp_Ven').removeAttr('required');
    $('#Pag_Pld').removeAttr('disabled'); 
}

// guardar un proveedor
function guardaProvee(){            
    $.saveDataJson("",$('#provCreateForm').getData('guardaProvAjax'), function( resp ){ selectProvee(resp['prov']); $('#provCreateDialog').dialog('close'); return false; });
 }