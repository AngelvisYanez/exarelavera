// JavaScript Document
/**
* Validacion para crear nuevas filas 
*/
function valida_fechas_pdf(fec_ini,fec_fin,op)
{
	var FecIni= document.getElementById(fec_ini);	
	var FecFin= document.getElementById(fec_fin);	

	if((Date.parse(FecFin.value)) < (Date.parse(FecIni.value)) && op=='1')
	{   
		FecIni.value=FecFin.value;		
	}
	if((Date.parse(FecIni.value)) > (Date.parse(FecFin.value)) && op=='2')
	{   
		FecFin.value=FecIni.value;		
	}	
}


