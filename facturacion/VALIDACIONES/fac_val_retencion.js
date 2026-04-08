
/**
*  funcion que oculta los rangos de fechas 
*/
function ocultas_rango_fecha(campo)
{   
    if(campo.checked==true)
	{	document.getElementById('capa_rango_fec').className = "muestra";
    	document.getElementById('capa_fecha').className = "oculta";
	}else
	{	document.getElementById('capa_rango_fec').className = "oculta";
    	document.getElementById('capa_fecha').className = "muestra";
	}
		
}

function setearfecha()
{	
	document.getElementById('ini').value=document.getElementById('cmb_anio').value+"-01-01";
	document.getElementById('fin').value=document.getElementById('cmb_anio').value+"-01-01";
}