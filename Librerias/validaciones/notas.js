//****************************M O D U L O    D E    N O T A S*******************
//*********Acta general*********************************************************
function acta_general(form)
{
	if (requerido(form.Nge_Tot) != false)		
	{
		confirmacion(form);
	}
}

function maximo_nota(form, campo)
{
	if (parseFloat(campo.value) > 10)
	{
		campo.focus()
		alert ("La nota máxima a ingresar es 10");	
		return false;
	}
	else
	{
		if (parseInt(campo.value) < 0)		
		{
			campo.focus()
			alert ("La nota mínima a ingresar es 0");
			return false;
		}
		
	}
}		

//*********Consulta del certificado de promocion*******************************
function validar_certificado1(form)
{
	if (requerido(form.Car_Int) != false)
	{
		form.submit();
	}	
}

function validar_matriculas2(form)
{
	if (requerido(form.Mat_Tip) != false && requerido(form.Mat_Cod) != false && requerido(form.Car_Int) != false)
	  {
		  	confirmacion(form);
	  }
}

function validar_matricu1as3(form)
{
	if (requerido(form.Car_Int) != false)
	{
		form.submit();
	}	
}
//*************Notas Pendientes************************************************
function validar_pendientes1(form)
{
	if (requerido(form.Est_Ced) != false)
	{
		form.submit();
	}	
}

function validar_pendientes2(form)
{
	if (requerido(form.Car_Int) != false)
	{
		form.submit();
	}	
}
