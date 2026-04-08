   /* 
    modal.js ventana modal basica
	Copyright © Jesus Liñan www.ribosomatic.com
	
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.*/
	
$(document).ready(function(){
	//parametros principales
	
	
	var ancho = 700; 
	var alto = 400;

	$('#button').click(function(){
	$('#bgmodal').fadeIn(1000);
      document.getElementById("bgtransparent").style.display=""; 
	  document.getElementById("bgmodal").style.display="";
	 
	 
		var wscr = $(window).width();
		var hscr = $(window).height();
				
		$('#bgtransparent').css("width", wscr);
		$('#bgtransparent').css("height", hscr);
	
	
		// ventana flotante
	
		
	
		
		$(window).resize();
	});

	$(window).resize(function(){
		// dimensiones de la ventana
		var wscr = $(window).width();
		var hscr = $(window).height();
      //  $('#bgtransparent').style.display ='block';
		// estableciendo dimensiones de background
		$('#bgtransparent').css("width", wscr);
		$('#bgtransparent').css("height", hscr); 
		
		
		// definiendo tamaño del contenedor
		$('#bgmodal').css("width", ancho+'px');
		$('#bgmodal').css("height", alto+'px');
		
		// obtiendo tamaño de contenedor
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
	
 });

/* Actualmente no se utiliza */
/*function mostrar_capa()
{
	//parametros principales
	var ancho = 700; 
	var alto = 400;
    document.getElementById("bgtransparent").style.display=""; 
    document.getElementById("bgmodal").style.display="";
	var wscr = $(window).width();
	var hscr = $(window).height();
	document.getElementById("bgtransparent").style.width=wscr;
	document.getElementById("bgtransparent").style.height=hscr;

	// dimensiones de la ventana
	var wscr = $(window).width();
	var hscr = $(window).height();
      //  $('#bgtransparent').style.display ='block';
		// estableciendo dimensiones de background
	document.getElementById("bgtransparent").style.display=""; 
    document.getElementById("bgmodal").style.display="";
	
    document.getElementById("bgtransparent").style.width=wscr;
	document.getElementById("bgtransparent").style.height=hscr;
	
		$('#bgmodal').css("width", ancho+'px');
		$('#bgmodal').css("height", alto+'px');
		
		// obtiendo tamaño de contenedor
		var wcnt = $('#bgmodal').width();
		var hcnt = $('#bgmodal').height();
		
		// obtener posicion central
		var mleft = ( wscr - wcnt ) / 2;
		var mtop = ( hscr - hcnt ) / 2;
	

		// estableciendo posicion
		$('#bgmodal').css("left", mleft+'px');
		$('#bgmodal').css("top", mtop+'px');
}*/
	
function closeModal(){
	  document.getElementById("bgtransparent").style.display="none"; 
	  document.getElementById("bgmodal").style.display="none";
}