/*****LLAMADO A LA FUNCIÓN PRINCIPAL DE VALIDACIONES DEL SISTEMA GINUS **************************************************/

/* Permite mostrar y ocultar el constenido de botones inferiores del organigrama */
function botones_org(boton)
{
 switch(boton){
     case 1 : /* Boton organigrama */
        document.getElementById('id_departamento').className = "muestra";
        document.getElementById('id_cargo').className = "oculta";
        document.getElementById('id_seccion').className = "oculta";
  break;
 case 2 : /* Boton seccion */
  document.getElementById('id_departamento').className = "oculta";
  document.getElementById('id_seccion').className = "muestra";
  document.getElementById('id_cargo').className = "oculta";
  break;
    case 3 : /* Boton personal */
        document.getElementById('id_departamento').className = "oculta";
  document.getElementById('id_seccion').className = "oculta";
  document.getElementById('id_cargo').className = "muestra";
        break;
  }
}