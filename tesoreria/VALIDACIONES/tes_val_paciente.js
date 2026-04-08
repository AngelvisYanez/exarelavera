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
var arrayPersona = [];
var valorBusqueda = 'CONSUMIDOR FINAL';
var valorBusqueda2 = 'VARIOS INGRESOS';
var bCf = false;
var bVi = false;
var contador = 0;
var stadoC= false,stadoV = false;


function validar_persona(form)
{
 if (requerido(form.Prs_Ape) != false && requerido(form.Prs_Sex) != false && requerido(form.Prs_Dir) != false && requerido(form.Ciu_Cod) != false   && requerido(form.Ide_Cod) != false)
	{
			confirmacion(form);
	}
}

function inicio(){
  $("#Pac_Fna").createDatePickers();
}

    window.onload = inicio;
    var data = "";
    var tipoDoc = 0;

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
//$.saveDataJson('',$('#clieCreateForm').getData('guardaClieAjax'), function( resp ){ selectCliente(resp['clie']); $('#clieCreateDialog').dialog('close'); return false; });

function crearConsumidorFinal() {
   console.log('crear consumidor final');
   var nuevoCliente = new Cliente('9999999999', 4, 'N', 'CONSUMIDOR FINAL', 'CONSUMIDOR FINAL', 'M', 0, '(NINGUNA)', '(NINGUNA)', '(NINGUNA)');   
   $.createDialogConfirm(`¿Est&aacute; seguro que desea guardar los cambios?`, nuevoCliente, guardaConsumidorfinal);
   //$('#btnC').attr('disabled', 'disabled');
}
function crearVariosIngresos() {
   console.log('crear varios ingresos');
   var nuevoCliente = new Cliente('9999999999', 1, 'N', 'VARIOS INGRESOS', 'VARIOS INGRESOS', 'M', 0, '(NINGUNA)', '(NINGUNA)', '(NINGUNA)');   
   $.createDialogConfirm(`¿Est&aacute; seguro que desea guardar?`, nuevoCliente, guardaConsumidorfinal);
   //$('#btnV').attr('disabled', 'disabled');
}

function guardaConsumidorfinal(consumidorF) {
   console.log(consumidorF);
   console.log(stadoC);
   console.log(stadoV);
   if (stadoC) { $.saveDataJson("", { guardarConsumidorFnal: true, consumidorF }, function (resp) { if (resp['success']) {/*verificaCliente();*/ $('#btnC').attr('disabled', 'disabled'); return false; } }); }
   if(stadoV){$.saveDataJson("", {guardarConsumidorFnal:true, consumidorF}, function (resp) { if (resp['success']) {/*verificaCliente();*/$('#btnV').attr('disabled', 'disabled'); return false;} });}
   
   
}
function guardaCfinal(personas) {   
   console.log('guardaCfinal');
   console.log(contador);
   console.log(persona);
   var persona;
   if (contador > 0) {
      for (var i = 0; i < personas.length; i++) { 
         if (personas[i]['Prs_Ape'] == valorBusqueda ) { 
            persona = personas[i];
         }
      }
      $.saveDataJson("", { guardarCfPersona: true, persona }, function (resp) {
         if (resp['success']) { 
            refresh();
            bCf = false;            
            return false;
         }
      });
            
   } else if (contador < 0) {
      for (var i = 0; i < personas.length; i++) { 
         if (personas[i]['Prs_Ape'] == valorBusqueda2 ) { 
            persona = personas[i];
         }
      }
      $.saveDataJson("", { guardarVIPersona: true, persona }, function (resp) {
         if (resp['success']) { 
            refresh();            
            bVi = false;
            return false;
         }
      });
            
   }
   

}


function crearCFConPersona() { 
   var nuevoCliente;
   console.log(arrayPersona[0]['Prs_Ape']);
   console.log(contador);
   /*for (var i = 0; i < arrayPersona.length; i++) {
      console.log(arrayPersona[i]['Prs_Ape']);
      console.log(valorBusqueda);
      console.log(bCf);
      console.log(bVi);
      if (arrayPersona[i]['Prs_Ape'] == valorBusqueda && contador > 0 ) {
         nuevoCliente = arrayPersona[i];         
         console.log(nuevoCliente);
      } else if (arrayPersona[i]['Prs_Ape'] == valorBusqueda2 && contador < 0) {
         nuevoCliente = arrayPersona[i];         
         console.log(nuevoCliente);
      }     
   }  */   
   //arrayPersona.splice(0, 1);   
   $.createDialogConfirm(`¿Est&aacute; seguro que desea guardar?`, arrayPersona, guardaCfinal);
   
   
}

function verificaCliente() {
   //console.log('verifica paciente');   
   let stado = false;
   $.getDataJson("", { verificaAjax: true }, function (busqueda) {
      let tmn = busqueda.consumidorFinal.length;
      let ptmn = busqueda.consumidorFinalPersona.length;
      let vtmn = busqueda.variosIngresos.length;
      let pvtmn = busqueda.variosIngresosPersona.length;     
           
      if (tmn <= 0) {
         if (ptmn <= 0) {
            $('#btnsCF').show();
         } else {
            if (ptmn > 0) {                
               busqueda.consumidorFinalPersona.forEach(function (persona) {                  
                  if (persona['Prs_Ape'].trim() === valorBusqueda) {if (!stado) { console.log('guardoPersona', persona); arrayPersona.push(persona);stado = true;$('#btnsCFd').show(); bCf = true; } }
               });
            }
            
         }
         
      }
      if (vtmn <= 0) {
         if (pvtmn <= 0) {
            $('#btnsCF').show();            
         } else {
            if (pvtmn > 0) { 
               busqueda.variosIngresosPersona.forEach(function (persona) {
                  if (persona['Prs_Ape'].trim() === valorBusqueda2) { 
                     console.log('guardoPersona', persona); arrayPersona.push(persona);$('#btnsCFdv').show(); bVi = true;
                  }
               });               
            }
         }         
      }
   });
   
}

function Cliente(Prs_Ced, Ide_Cod, Cli_Con, Prs_Ape, Prs_Nom, Prs_Sex, Ciu_Cod, Prs_Dir, Prs_Tel, Prs_Cor) {
   this.Prs_Ced = Prs_Ced;
   this.Ide_Cod = Ide_Cod;
   this.Cli_Con = Cli_Con;
   this.Prs_Ape = Prs_Ape;
   this.Prs_Nom = Prs_Nom;
   this.Prs_Sex = Prs_Sex;
   this.Ciu_Cod = Ciu_Cod;
   this.Prs_Dir = Prs_Dir;
   this.Prs_Tel = Prs_Tel;
   this.Prs_Cor = Prs_Cor;
}

$(document).on('click', '#btnCVI', function() {
   //$("#guardar").removeAttr("disabled"); btnsCF
   console.log('funka vi');
   //bVi = true;
});
$( document ).ready(function() {
   //console.log('funka 2');
   verificaCliente();
   $('#btn_Cns').on('click', function () { contador += 1; });
   $('#btnCVI').on('click', function () { contador -= 1; });
   $('#btnC').on('click', function () { stadoC = true; });
   $('#btnV').on('click', function () { stadoV = true; });
});
function refresh(){ 
   location.reload(true);
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