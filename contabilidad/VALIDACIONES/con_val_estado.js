function PonerDatos(form,cod,des){
	form.Est_Cod.value=cod;
	form.Est_Des.value=des;
}

function validar(form)
{
	var cont = 0;
	for(var i = 0; i < form.elements.length; i++)
	{
		if(form.elements[i].type=='checkbox')
		{
			if(form.elements[i].checked)
		   {
			cont++;
		   }
		}
		
	}
	
	if(cont == 0)
	{
		$.alert('Alerta:<br> Debe Chequear por lo menos una opción',null,'warning');
	}
	
	return cont > 0;
}


function Marcar(chkTodos,form)
{
	for(var i = 0; i < form.elements.length; i++)
	{
		if(form.elements[i].type=='checkbox')
			form.elements[i].checked = chkTodos.checked;
	}
}
/**
 * aparece la seccion de codigo correspondiente del modal actual oculto
 */
function modalAparecer()
{
	var ancho = 700; 
	var alto = 500;

	$('#bgmodal').fadeIn(1000);
      document.getElementById("bgtransparent").style.display=""; 
	  document.getElementById("bgmodal").style.display="";
	 
	 
		var wscr = $(window).width();
		var hscr = $(window).height();
				
		$('#bgtransparent').css("width", wscr);
		$('#bgtransparent').css("height", hscr);
		
		$(window).resize();

	$(window).resize(function(){
		// dimensiones de la ventana
		var wscr = $(window).width();
		var hscr = $(window).height();
      //  $('#bgtransparent').style.display ='block';
		// estableciendo dimensiones de background
		$('#bgtransparent').css("width", wscr);
		$('#bgtransparent').css("height", hscr); 
		
		
		// definiendo tama�o del contenedor
		$('#bgmodal').css("width", ancho+'px');
		$('#bgmodal').css("height", alto+'px');
		
		// obtiendo tama�o de contenedor
		var wcnt = $('#bgmodal').width();
		var hcnt = $('#bgmodal').height();
		
		// obtener posicion central
		var mleft = ( wscr - wcnt ) / 2;
		var mtop = ( hscr - hcnt ) / 2;
	

		// estableciendo posicion
		$('#bgmodal').css("left", mleft+'px');
		$('#bgmodal').css("top", mtop+'px');
		 
	});
	
	$(window).keyup(function(event){
   		if (event.keyCode == 27) {
        	//falta implementar
   		}
	});
}
// JavaScript Document
//Parametros iniciales de configuraci�n
/* INICIO J Q U E R Y    T E X T     B O X */
asignarClassFocoText();
//variables globales
//var searchBoxes = $(".text"); //Variable para enfocar las cajas de texto 
var searchBox1 = $("#txt_busqueda"); //Variable para iniciar el foco en una caja de texto al cargar
var searchBox2 = $("#txt_busqueda"); //Variable para poner un texto inicial y borrarlo al poner el foco*
var defaultText = "Buscar...";
/* FIN    J Q U E R Y    T E X T     B O X */// JavaScript Document// JavaScript Document