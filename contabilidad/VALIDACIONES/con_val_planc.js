// JavaScript Document
/* Validacion indivual*/
function validar_cuentas(form, campo)
{
	if (document.getElementById('np2').value ==0)
		ceros = true;
	else
		ceros = parametro_x(campo, '.');
		
	if (ceros != false)
		{
			form.submit();
		}
}