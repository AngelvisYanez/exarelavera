// JavaScript Document
//Parametros iniciales de configuración
/* INICIO J Q U E R Y    T E X T     B O X */
/* Script que asigna a todos los elementos input una nueva clase */
var forms = document.forms;
for (i=0; i<= forms.length-1; i++)
{
	var texts = document.forms.item(i).getElementsByTagName('input');
	for (j=0; j<= texts.length-1; j++)
	{
		if (texts.item(j).type == "text")
		{			
			input = texts.item(j);	
			set_estilo(input,'text');
		}
	}
}
//variables globales
var searchBoxes = $(".text"); //Variable para enfocar las cajas de texto
var searchBox1 = $("#txt_busqueda"); //Variable para iniciar el foco en una caja de texto al cargar
var searchBox2 = $("#txt_busqueda"); //Variable para poner un texto inicial y borrarlo al poner el foco*
var defaultText = "Buscar cliente ...";
/* FIN    J Q U E R Y    T E X T     B O X */