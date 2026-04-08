// JavaScript Document
function validar_usuarios_inicio()
{
	if ((requerido(document.getElementById('Usu_Pal')) != false) && (document.getElementById('Usu_Pal2') != false)
		&& (claveigual(document.getElementById('Usu_Pal'),document.getElementById('Usu_Pal2')) != false)
		&& (clave_inicial() != false))
	{
		confirmacion(document.form2);
	}
}

function validar_usuarios()
{
	if ((claveigual(document.getElementById('Usu_Pal'),document.getElementById('Usu_Pal2')) != false) && (clave_inicial() != false))
	{
		confirmacion(document.form2);
	}
}

function clave_inicial()
{	
	if (document.form2.Usu_Pal.value==document.form2.Usu_Ced2.value)
	{
		alert ("¡Ingrese una contraseña diferente al usuario!");
		document.form2.Usu_Pal.focus()
		return false;
	}
}

/* Funcion para ver el nivel d seguridad de las claves de acceso*/
function seguridad_clave(clave){
   var seguridad = 0;
   if (clave.length!=0){
      if (tiene_numeros(clave) && tiene_letras(clave)){
         seguridad += 30;
      }
      if (tiene_minusculas(clave) && tiene_mayusculas(clave)){
         seguridad += 30;
      }
      if (clave.length >= 4 && clave.length <= 5){
         seguridad += 10;
      }else{
         if (clave.length >= 6 && clave.length <= 8){
            seguridad += 30;
         }else{
            if (clave.length > 8){
               seguridad += 40;
            }
         }
      }
   }
   
   if(seguridad == 0)  
   {  document.getElementById('niv1').style.backgroundColor = "#FFFFFF"; 
  document.getElementById('niv2').style.backgroundColor = "#FFFFFF";
  document.getElementById('niv3').style.backgroundColor = "#FFFFFF";
  document.getElementById('niv4').style.backgroundColor = "#FFFFFF";
   }
   if(seguridad > 0 && seguridad <= 10)  
   {           
  document.getElementById('niv1').style.backgroundColor = "#953C1E"; 
  document.getElementById('niv2').style.backgroundColor = "#FFFFFF";
  document.getElementById('niv3').style.backgroundColor = "#FFFFFF";
  document.getElementById('niv4').style.backgroundColor = "#FFFFFF";
   }
   else
   {
    if(seguridad > 10 && seguridad <= 39)
    {       
   document.getElementById('niv1').style.backgroundColor = "#953C1E"; 
   document.getElementById('niv2').style.backgroundColor = "#FF9900";
   document.getElementById('niv3').style.backgroundColor = "#FFFFFF";
   document.getElementById('niv4').style.backgroundColor = "#FFFFFF";
    }
    else
    {
    if(seguridad >= 40 && seguridad < 70)
    {      
    document.getElementById('niv1').style.backgroundColor = "#953C1E"; 
    document.getElementById('niv2').style.backgroundColor = "#FF9900";
    document.getElementById('niv3').style.backgroundColor = "#FFCC00";
    document.getElementById('niv4').style.backgroundColor = "#FFFFFF";
    }   
    else
    {
     if(seguridad >= 70 && seguridad <= 100)
     {    
     document.getElementById('niv1').style.backgroundColor = "#953C1E"; 
     document.getElementById('niv2').style.backgroundColor = "#FF9900";
     document.getElementById('niv3').style.backgroundColor = "#FFCC00";
     document.getElementById('niv4').style.backgroundColor = "#FFFF00";
     }   
    }
  }
 }
 //document.getElementById('porcentaje').value = seguridad;
}

function tiene_numeros(texto){
   var numeros="0123456789";  
   for(i=0; i<texto.length; i++){
      if (numeros.indexOf(texto.charAt(i),0)!=-1){
         return 1;
      }
   }
   return 0;
}

function tiene_letras(texto){
   var letras="abcdefghyjklmnñopqrstuvwxyz";
   texto = texto.toLowerCase();
   for(i=0; i<texto.length; i++){
      if (letras.indexOf(texto.charAt(i),0)!=-1){
         return 1;
      }
   }
   return 0;
} 

function tiene_minusculas(texto){
   var letras="abcdefghyjklmnñopqrstuvwxyz"; 
   for(i=0; i<texto.length; i++){
      if (letras.indexOf(texto.charAt(i),0)!=-1){
         return 1;
      }
   }
   return 0;
} 

function tiene_mayusculas(texto){
   var letras_mayusculas="ABCDEFGHYJKLMNÑOPQRSTUVWXYZ"; 
   for(i=0; i<texto.length; i++){
      if (letras_mayusculas.indexOf(texto.charAt(i),0)!=-1){
         return 1;
      }
   }
   return 0;
}

/* Permite mostrar y ocultar el contenido de botones del registro de procesos */
function botones_dic(boton)
{
	switch(boton){
     case 1 : /* Boton deudas */
        document.getElementById('id_directorio').className = "muestra";
        document.getElementById('id_proceso').className = "oculta";
        break;
     case 2 : /* Boton buscar */
        document.getElementById('id_directorio').className = "oculta";
        document.getElementById('id_proceso').className = "muestra";
        break;
  	}
}

function IsChk(form)
{
	var found = false;
	
	var chk = document.getElementsByName('perfil[]');
	
	for (var i=0 ; i < chk.length ; i++)
	{
		found = chk[i].checked ? true : found;
	}
	
	if (found)
	{
		op= confirm("¿Está seguro de realizar esta operación?");
		  
		if (op == true)
		{
			form.submit();
		}
	} 
	else 
	{
		alert ("¡Es necesario que seleccione al menos una opción!");
		return false;
	}
}

function IsChk2(form)
{
 if(requerido(document.getElementById('Usu_Pal')) != false)
 {
  var found = false;
  
  var chk = document.getElementsByName('perfil[]');
  
  for (var i=0 ; i < chk.length ; i++)
  {
   found = chk[i].checked ? true : found;
  }
  
  if (found)
  {
   op= confirm("¿Está seguro de realizar esta operación?");
     
   if (op == true)
   {
    form.submit();
   }
  } 
  else 
  {
   alert ("¡Es necesario que seleccione al menos una opción!");
   return false;
  }
 }
}