// JavaScript Document
function check_generar()
{
	if(document.getElementById('Act_Gen').checked==true)
	{
		document.getElementById('Act_Gen').value=1;
		document.getElementById('contenedorcheck').innerHTML="Genera el código del Activo";
		document.getElementById('Act_Bar').disabled=true;
	}
	else
	{ 
		document.getElementById('Act_Gen').value=0;
		document.getElementById('Act_Bar').disabled=false;		
		document.getElementById('contenedorcheck').innerHTML="Ingrese el código de barra del Activo";	
	}				
}

//Crea la lista de activos seleccionados para la transferencia
function Crear_lista_Activos(cant){
			var miArray = new Array(cant)	
			j = 0;
			str="";
			for (i=1;i<=cant;i++){
				if (document.getElementById('activo['+i+']').value == '1'){
					str= str+"*"+ document.getElementById('Act_Cod['+i+']').value;
					j++;
				}
			} 
			document.getElementById('arr').value=str;
}

//Permite ocultar los activos que se van a transferir
function Pasar_Activo(i,cant)
{	
		document.getElementById('AcOld['+i+']').className = 'muestra';
		document.getElementById('Ac['+i+']').className = 'oculta';
		document.getElementById('selec['+i+']').className = 'oculta';
		document.getElementById('selecback['+i+']').className = 'muestra';
		document.getElementById('selecback['+i+']').className = 'btn btn-danger btn-mini';
		Crear_lista_Activos(cant);
}
function Regresar_Activo(i,cant)
{	
		document.getElementById('AcOld['+i+']').className = 'oculta';
		document.getElementById('Ac['+i+']').className = 'muestra';	
		document.getElementById('selecback['+i+']').className = 'oculta';
		document.getElementById('selec['+i+']').className = 'muestra';
		document.getElementById('selec['+i+']').className = 'btn btn-success btn-mini';
		Crear_lista_Activos(cant);
}

//Crea la lista de activos seleccionados para la transferencia
function crear_lista_activos_check(cant){				
			//var miArray = new Array(cant)	
			j = 0;
			str="";
			for (i=1;i<=cant;i++){
				//alert(document.getElementById('sel['+i+']').checked);
				if (document.getElementById('sel['+i+']').checked){
					str= str+"*"+ document.getElementById('Act_Cod['+i+']').value;
					j++;
				}
			} 
			document.getElementById('arr').value=str;
}

/* Permite validar los campos y si se ha seleccionado un activo
	confirmacion :
	0=no
	1=si
*/
function validar_transfe(form, campos, confir)
{
	str_campos = new String;
	str_campos.value = campos;
	var array_campos = str_campos.value.split("*");
	enviar = false;
	for (i=0; i<=array_campos.length-1; i++)
	{
		campo = document.getElementById(array_campos[i]);
		if ((validar_seleccion_activo(campo) != false))		
			enviar = true;
		else
		{
			enviar = false;
			break;
		}
			
	}//Fin del for (i=0; i<=array_campos.length; i++)
	
	if (enviar == true)
	{
		if (confir == 1)
		{
			confirmacion(form);	
		}
		else
		{
			form.submit();	
		}
	}
}//Fin del function validar_requeridos(form, campos)
//VAlida si se ha seleccionado un activo para la transferencia.
function validar_seleccion_activo(campo)
{
	//alert(campo.value);
	if (campo.name== "arr")	
	{
		if(campo.value==""){
		alert ("Debe seleccionar al menos un activo");
		//campo.focus()
		return false;
		}
	}
	else
	{
		if(campo.value==""){
		alert ("El dato de este campo es requerido");
		campo.focus()
		return false;
		}
	}
	
}

//Check para seleccionar o deseleccionar todos los activos
function seleccionar_todos(cant,campo)
{
			j = 0;
			str="";
			if (document.getElementById(campo).checked){
				for (i=1;i<=cant;i++){
					//alert(document.getElementById('sel['+i+']').checked);					
						document.getElementById('sel['+i+']').checked=true;
						str= str+"*"+ document.getElementById('Act_Cod['+i+']').value;
				}				
			}
			else{
				for (i=1;i<=cant;i++){
					//alert(document.getElementById('sel['+i+']').checked);					
						document.getElementById('sel['+i+']').checked=false;
						j++;
				}	
				
				
			}
			 document.getElementById('arr').value=str;
			//document.getElementById('arr').value=str;
	
}






