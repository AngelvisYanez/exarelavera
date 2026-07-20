// JavaScript Document
/* Validacion indivual*/

function asignar_fechas(valor)
{
	var datos=valor.split('*');
	document.getElementById('hdd_ann').value=datos[1];
	document.getElementById('Pec_Fei').value=datos[1];
	document.getElementById('Pec_Fef').value=datos[2];
}

/* Funcion que valida la busqueda del mayor en GRUPOS */
function validar_buscar_cuenta(form, campo)
{
	/* Variables del periodo contable */
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
			$.alert("Las fechas deben estar en el rango del periodo contable seleccionado <br> Inicio: " + Pec_Fei + " <br> Fin    : " + Pec_Fef,null,'warning');
		}
	}
	else
	{
		$.alert("¡Debe ingresar datos!",null,'warning');
		document.getElementById(campo).focus();
	}
}//Fin del function validar_buscar_cuenta(form, campo)