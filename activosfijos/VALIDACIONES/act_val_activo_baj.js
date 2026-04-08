// JavaScript Document

//Permite ocultar el combo de busqueda de Activo por campos de activos.
function busquedaCampos()
{
	if (document.getElementById('op_cam').value == 'ns')	
	{
		document.getElementById('Cam_Cod').className = 'muestra';
	}
	else
	{
		document.getElementById('Cam_Cod').className = 'oculta';		
	}
}


function activarRadio(op_cam)
{
	if (document.getElementById('op_cam').value == 'ns')
	{
		document.getElementById('op_opciones').checked= true;
	}
}

/**
 * Funcion que permite habilitar y deshabilitar  text
 */
function habilitar_text(valor)
{
	//alert(cadena);
	if (valor == '0')
	{
		document.getElementById('Baj_Qui').focus();
		document.getElementById('Baj_Qui').value='';
		document.getElementById('Baj_Val').value='';
		document.getElementById('Baj_Val').disabled=true;		
		return cadena;
	}
	if (valor == '1')
	{
		
		document.getElementById('Baj_Qui').value='';
		document.getElementById('Baj_Val').value='';
		document.getElementById('Baj_Qui').disabled=true;
		document.getElementById('Baj_Val').disabled=true;
		return cadena;
	}
	if (valor == '2' ||valor == '3' )
	{
		
		document.getElementById('Baj_Qui').focus();
		document.getElementById('Baj_Qui').value='';
		document.getElementById('Baj_Val').value='';
		document.getElementById('Baj_Qui').disabled=false;
		document.getElementById('Baj_Val').disabled=false;
		
		return cadena;
	}
}


/**
 * Funcion que permite obtener los campos a validar segun la opcion seleccionada.
 */

function validar_opciones(cadena)
{
	//alert(cadena);
	if (cadena == '0')
	{
		cadena ='Act_Cod*Baj_Mot*Baj_Fba*Baj_Inf*Baj_Des*Baj_Qui';
		return cadena;
	}
	if (cadena == '1')
	{
		cadena ='Act_Cod*Baj_Mot*Baj_Fba*Baj_Inf*Baj_Des';
		return cadena;
	}
	if (cadena == '2' ||cadena == '3' )
	{
		cadena ='Act_Cod*Baj_Mot*Baj_Fba*Baj_Inf*Baj_Des*Baj_Qui*Baj_Val';
		return cadena;
	}
	if (cadena == '')
	{
		cadena ='Act_Cod*Baj_Mot*Baj_Fba*Baj_Inf*Baj_Des*Baj_Qui';
		return cadena;
	}
	
}
/* Permite validar una cantidad n... de campos 
confirmacion :
0=no
1=si
*/
function validar_requeridos_baja(form, campos, confir)
{
	str_campos = new String;
	str_campos.value = campos;
	var array_campos = str_campos.value.split("*");
	enviar = false;
	for (i=0; i<=array_campos.length-1; i++)
	{
		campo = document.getElementById(array_campos[i]);
		if ((requerido(campo) != false))		
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
			confirmacion_baja(form);	
		}
		else
		{
			form.submit();	
		}
	}
}//Fin del function validar_requeridos(form, campos)
/**
 * Confirmacion de validaciond de requerisdos
 */
function confirmacion_baja(form)
{
	op= confirm("¿Ud. intenta dar de baja un activo, este proceso no podrá ser revertido, está seguro de continuar?");
		  
	if (op == true)
	{
		form.submit();
	}
}

