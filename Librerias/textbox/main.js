/* J Q U E R Y    T E X T     B O X */

//efectos en el evento focus (foto) para ambas cajas de busqueda
searchBoxes.focus(function(){
	$(this).addClass("active");
});
searchBoxes.blur(function(){
	$(this).removeClass("active");  
});

//Activamos y auto activamos el foco en la primera caja de busqueda #search1, cuando el documento esta listo
searchBox1.focus();

//Mostramos / ocultamos el texto por defecto si es necesario
searchBox2.attr("placeholder", defaultText);
/*searchBox2.focus(function(){
	if($(this).attr("value") == defaultText) $(this).attr("value", "");
});
searchBox2.blur(function(){
	if($(this).attr("value") == "") $(this).attr("value", defaultText);
});*/
/* J Q U E R Y    T E X T     B O X */