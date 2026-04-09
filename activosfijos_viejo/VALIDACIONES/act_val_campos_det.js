// JavaScript Document
function check_generar()
{
	if(document.getElementById('Act_Gen').checked==true)
	{
		document.getElementById('Act_Gen').value=1;
		document.getElementById('contenedorcheck').innerHTML="Genera el código del Activo";
		document.getElementById('Act_Bar').disabled=true;
	}
	else
	{ 
		document.getElementById('Act_Gen').value=0;
		document.getElementById('Act_Bar').disabled=false;		
		document.getElementById('contenedorcheck').innerHTML="Ingrese el código de barra del Activo";	
	}				
}

//Permite ocultar el combo de busqueda de Activo por campos de activos.
function busquedaCampos()
{
	if (document.getElementById('op_cam').value == 'ns')	
	{
		document.getElementById('Cam_Cod').className = 'muestra';
	}
	else
	{
		document.getElementById('Cam_Cod').className = 'oculta';		
	}
}

//Permite actualizar el codigo de barras



