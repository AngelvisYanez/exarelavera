
//////////////////////////////////////////////////////////////////////////////////////////
function inicio (){
	$('#Prd_Imp').createDatePickers();
	$('#Prd_Cad').createDatePickers();
}

window.onload =inicio;

function validar_cal(form,div_1,div_2,valor_1,valor_2,confir)
{
	var enviar = false;
	if(document.getElementById(div_1).className != 'oculta')
	{
		if(document.getElementById(valor_1).value == "")
		{
			$.alert("Debe seleccionar un item del Combo",null,'warning');
		}
		else
		{
			enviar = true;
		}
	}
	
	if(document.getElementById(div_2).className != 'oculta')
	{
		if(document.getElementById(valor_2).value == "")
		{
			$.alert("Debe seleccionar un item del Combo",null,'warning');
		}
		else
		{
			enviar = true;
		}
	}
	if(enviar == true)
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
}

/**
* oculta campos segun la seleccion del combo
* @param {Form} formulario actual
*/
function MostrarNJ(form)
{
	if(document.getElementById('Prv_Tic').value == "N")
	{
		document.getElementById('Juridico').className = 'oculta';
		document.getElementById('Natural').className = 'muestra';
		document.getElementById('Natural_a').className = 'muestra';
		document.getElementById('sexo').className = 'muestra';
	}
	
	if(document.getElementById('Prv_Tic').value == "J")
	{
		document.getElementById('Natural').className = 'oculta';
		document.getElementById('Natural_a').className = 'oculta';
		document.getElementById('Juridico').className = 'muestra';
		document.getElementById('sexo').className = 'oculta';
	}
}

/**
* valida los campos persona
* @param {Form} formulario actual
*/
function validar_persona(form)
{
	if (requerido(form.Prs_Ced) != false && requerido(form.Prs_Ape) != false && requerido(form.Prv_Com) != false && requerido(form.Prv_Fin) != false  && requerido(form.Ciu_Cod) != false && requerido(form.Prs_Dir) != false && requerido(form.Prs_Tel) != false && requerido(form.Prv_Esp) != false && requerido(form.Prv_Con) != false)
	{
			confirmacion(form);
	}
}

/**
* valida los campos persona
* @param {Form} formulario actual
*/
function validar_proveedor(form)
{
	if (requerido(form.Prs_Ced) != false && requerido(form.Prv_Com) != false && requerido(form.Prv_Fin) != false  && requerido(form.Prv_Esp) != false && requerido(form.Prv_Con) != false)
	{
			confirmacion(form);
	}
}