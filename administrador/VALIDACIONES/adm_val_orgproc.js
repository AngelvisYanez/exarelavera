// JavaScript Document

/* Permite mostrar y ocultar el contenido de botones del registro de procesos */
function botones_dic(boton)
{
	switch(boton){
     case 1 : /* Boton deudas */
        document.getElementById('id_directorio').className = "muestra";
        document.getElementById('id_proceso').className = "oculta";
        break;
     case 2 : /* Boton buscar */
        document.getElementById('id_directorio').className = "oculta";
        document.getElementById('id_proceso').className = "muestra";
        break;
  	}
}

function botones_dic2(boton)
{
	switch(boton){
     case 1 : /* Boton deudas */
        document.getElementById('id_directorio').className = "muestra";
        document.getElementById('id_proceso').className = "oculta";
        break;
     case 2 : /* Boton buscar */
        document.getElementById('id_directorio').className = "oculta";
        document.getElementById('id_proceso').className = "muestra";
        break;
  	}
}