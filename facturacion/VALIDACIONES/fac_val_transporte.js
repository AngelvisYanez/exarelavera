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
 if (requerido(form.Prs_Nom) != false && requerido(form.Prs_Ape) != false && requerido(form.Prs_Dir) != false)
	{
			confirmacion(form);
	}
}

/**
* valida los campos persona
* @param {Form} formulario actual
*/
function validar_persona_ced(form)
{ 
 if (requerido(form.Prs_Nom) != false && requerido(form.Prs_Ape) != false && requerido(form.Prs_Dir) != false)
	{
			confirmacion(form);
	}
}

/**
* valida los campos de cliente
* @param {Form} formulario actual
*/
function validar_transporte(form)
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