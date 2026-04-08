// JavaScript Document
//Parametros iniciales de configuración
/* INICIO J Q U E R Y    T E X T     B O X */
asignarClassFocoText();
//variables globales
var searchBoxes = $(".text"); //Variable para enfocar las cajas de texto
/**
* Variable op del menu 
*/
var op = $("#op");
if (op.attr("value") == 1)
{
	var searchBox1 = $("#txt_busqueda"); //Variable para iniciar el foco en una caja de texto al cargar
	var searchBox2 = $("#txt_busqueda"); //Variable para poner un texto inicial y borrarlo al poner el foco*
	var defaultText = "Buscar cuenta contable de detalle...";
}
else
{
	var searchBox1 = $("#grupo"); //Variable para iniciar el foco en una caja de texto al cargar
	var searchBox2 = $("#grupo"); //Variable para poner un texto inicial y borrarlo al poner el foco*
	var defaultText = "Buscar cuenta contable de grupo...";	
}
/* FIN    J Q U E R Y    T E X T     B O X */