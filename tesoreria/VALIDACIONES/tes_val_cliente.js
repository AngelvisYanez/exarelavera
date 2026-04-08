/**
* @fileoverview Libreria con funciones de validaciones
*
* @author car.87cod :)
* @version 0.1
*/

/**
* valida los campos persona
* @param {Form} formulario actual
*/
function validar_persona(form)
{
 if (requerido(form.Prs_Ape) != false && requerido(form.Prs_Sex) != false && requerido(form.Prs_Dir) != false && requerido(form.Ciu_Cod) != false   && requerido(form.Ide_Cod) != false)
	{
			confirmacion(form);
	}
}

// valida cedula
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

/**
* valida los campos persona
* @param {Form} formulario actual
*/
function validar_persona_ced(form)
{
 if (requerido(form.Prs_Ced) != false && requerido(form.Prs_Ape) != false && requerido(form.Prs_Sex) != false && requerido(form.Prs_Dir) != false && requerido(form.Ciu_Cod) != false  && requerido(form.Ide_Cod) != false)
	{
			confirmacion(form);
	}
}

/**
* valida los campos de cliente
* @param {Form} formulario actual
*/
function validar_cliente(form)
{
  if (requerido(form.Cli_Tip)!= false)
  {
   confirmacion(form);
  }
}

/**
* oculta campos segun la seleccion del combo
* @param {Form} formulario actual
*/
function MostrarNJ(form)
{
	if(document.getElementById('Cli_Tic').value == "N")
	{
		document.getElementById('Juridico').className = 'oculta';
		document.getElementById('Natural').className = 'muestra';
		document.getElementById('Natural_a').className = 'muestra';
		document.getElementById('sexo').className = 'muestra';
		document.getElementById('tipo_pr').className = 'oculta';
	}
	
	if(document.getElementById('Cli_Tic').value == "J")
	{
		document.getElementById('Natural').className = 'oculta';
		document.getElementById('Natural_a').className = 'oculta';
		document.getElementById('Juridico').className = 'muestra';
		document.getElementById('sexo').className = 'oculta';
		document.getElementById('tipo_pr').className = 'muestra';
	}
}