// JavaScript Document
/* Validacion indivual*/

/*-----------------  periodo contable-------*/
function validar_perio_cont(form)
{
	var fecha_ini = form.Pec_Fei.value; 
	var fecha_fin = form.Pec_Fef.value; 
	
	if (fecha_ini < fecha_fin)
    {
	  confirmacion(form);	  
    }
	else
	{
		$.alert("La fecha de inicio debe ser menor a la fecha de fin del periodo contable",null,'warning');
	}
}