/**
* @fileoverview Libreria con funciones de validaciones
*
* @author Lewis Chimarro
* @version 0.1
*/
/* Funcion que validan los datos de productos y envia el ajax para el modal */
function  ajax_datos_tesoreria(url,objeto,form, campos, confir)
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
			ajax_datos(url, objeto)
		}
		else
		{
			
		}
	}
}//Fin del function validar_requeridos(form, campos)

/* Funcion que envia del modal a la pagina principal */
function ponPrefijo(pref,aux,Ite_Lar,Cat_Cod,Cat_Cdc)
{
	document.getElementById('Ite_Cod').value=pref;
	document.getElementById('Ite_Cod2').value=pref;
    document.getElementById('Ite_Cor').value=aux;
	document.getElementById('Ite_Lar').value=Ite_Lar;
	document.getElementById('Cat_Cod1').value=Cat_Cod;
	document.getElementById('Cat_Cod12').value=Cat_Cod;
	document.getElementById('Cat_Cdc').value=Cat_Cdc;
	closeModal();				
}

/* Valida si genera o no codigo de barra */
function check_generar()
{
	if(document.getElementById('Pro_Gen').checked==true)
	{
		document.getElementById('Pro_Gen').value=1;
		document.getElementById('contenedorcheck').innerHTML="Genera el código del producto";
		document.getElementById('Pro_Bar').disabled=true;

	}
	else
	{ 
		document.getElementById('Pro_Gen').value=0;
		document.getElementById('Pro_Bar').disabled=false;		
		document.getElementById('contenedorcheck').innerHTML="Ingrese el código de barra del producto";	
	}				
}

function check_generar_empresa()
{
	if(document.getElementById('Pro_Gen_Emp').checked==true)
	{
		document.getElementById('Pro_Gen_Emp').value=1;
		document.getElementById('contenedorcheckempresa').innerHTML="Genera el codigo de empresa del producto";
		document.getElementById('Pro_Bar_Emp').disabled=true;
	}
	else
	{ 
		document.getElementById('Pro_Gen_Emp').value=0;
		document.getElementById('Pro_Bar_Emp').disabled=false;		
		document.getElementById('contenedorcheckempresa').innerHTML="Ingrese el codigo de empresa del producto";		
	}				
}