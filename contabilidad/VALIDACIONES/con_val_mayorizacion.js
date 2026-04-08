// JavaScript Document
/**
* Balance General 
*/
function validar_balance(form, campo)
{
	/**
	* Variables del periodo contable 
	*/
	var Pec_Fei = document.getElementById('Pec_Fei').value;
	var Pec_Fef = document.getElementById('Pec_Fef').value;
	//var Periodo = document.getElementById('Pec_Cod').text;	
	/**
	* Variables de las fechas seleccionadas 
	*/
	var ini = document.getElementById('txt_fec_ini').value;
	var fin = document.getElementById('txt_fec_fin').value;

	if (requerido(campo) != false)
	{
		if (ini >= Pec_Fei && fin <= Pec_Fef)
		{
			form.submit();
		}
		else
		{
			alert ("Las fechas deben estar en el rango del periodo contable seleccionado \n Inicio: " + Pec_Fei + " \n Fin    : " + Pec_Fef);	
		}
	}
}//Fin del function validar_balance(form)

/**
* Funcion que valida la busqueda del mayor en GRUPOS 
*/
function validar_buscar_cuenta(form, campo)
{
	/**
	* Variables del periodo contable 
	*/
	var Pec_Fei = document.getElementById('Pec_Fei').value;
	var Pec_Fef = document.getElementById('Pec_Fef').value;
	var Periodo = document.getElementById('Pec_Cod').text;	
	/* Variables de las fechas seleccionadas */
	var ini = document.getElementById('txt_fec_ini').value;
	var fin = document.getElementById('txt_fec_fin').value;

	if (document.getElementById(campo).value != "")
	{
		if (ini >= Pec_Fei && fin <= Pec_Fef)
		{
			form.submit();
		}
		else
		{
			alert ("Las fechas deben estar en el rango del periodo contable seleccionado \n Inicio: " + Pec_Fei + " \n Fin    : " + Pec_Fef);	
		}
	}
	else
	{
		alert ("¡Debe ingresar datos!");
		document.getElementById(campo).focus();
	}
}//Fin del function validar_buscar_cuenta(form, campo)

function asignar_fechas(valor)
{
	arreglo = valor.split("*");
	document.getElementById('Pec_Fei').value = arreglo[1];
	document.getElementById('Pec_Fef').value = arreglo[2];
}

/**
* Validacion indivual
*/
function validar_cuentas(form, campo)
{
	if (parametro_x(campo, '.') != false)
		{
			form.submit;
		}
}