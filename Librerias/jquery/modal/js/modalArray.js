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
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
	Ajuste Array
	- Se debe definir un hidden que se llame "cantmodal" para varios modales
	Todos los posibles botones, cargan en mismo bgtransparent y bgmodal	*/
	
$(document).ready(function(){
	//parametros principales
		
	var ancho = 700; 
	var alto = 400;
	
	/*cantmodal = ;
alert(cantmodal.value);*/
	/* Verifica la validacion de un hidden de la cantidad de botones */
	if (document.getElementById("cantmodal"))
	{
		/* For para crear varios botones */
		for (i=0; i<=parseInt(document.getElementById("cantmodal").value)-1; i++)
		{	
			$('#button'+i).click(function(){	
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
		}//Fin del for (i=1; i<=cantmodal; i++)	
	}//Fin del if (cantmodal)
	
	$('#button2').click(function(){	
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

	function closeModal(index){
		  document.getElementById("bgtransparent").style.display="none"; 
		  document.getElementById("bgmodal").style.display="none";
	}		