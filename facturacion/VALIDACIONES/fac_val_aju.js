// JavaScript Document
/**
* Validacion para crear nuevas filas 
*/
function nueva_fila_ajuste(contenido,Gas_Cod,Gas_Des,Gas_Max,Cja_Tra)
{
  var columna1 = document.createElement("td");//Cantidad
  var columna2 = document.createElement("td");//Descripcion
  var columna3 = document.createElement("td");//Precio Unitario
  var columna4 = document.createElement("td");//Importe
  var columna5 = document.createElement("td");//Boton eliminar
 
  var fila = document.createElement("tr");
  fila.className= 'Fondo'
  var cuerpo = document.getElementById (contenido);

  /**
  * Alineacion de columnas - cabecera
  */
  columna1.setAttribute('align','center');  
  columna2.setAttribute('left','center');
  columna3.setAttribute('align','center');
  columna4.setAttribute('align','right');
  columna5.setAttribute('align','right');
  
  /**
  * Lectura del nuevo valor 
  */
  var total = document.getElementById('nfilas');
         
  /**
  * Aumentar en uno la cantidad de filas 
  */
  cont_filas('nfilas');
  
  /**
  * Cuadro que muestra cantidad del producto
  */
  var Caj_Can=create_input('datos['+ total.value +','+ 1 +']',6,6 ,'text',false, 'center')
  Caj_Can.onkeyup= function() {
	  numerico(this);
  importeAjuste(Gas_Max,auxCant.value);
	  
  total_ajuste(Cja_Tra,4,1)};
  columna1.appendChild(Caj_Can);
  
  /**
  * Cuadro que muestra detalle del producto
  */
  var Caj_Des=create_input('datos['+ total.value +','+ 2 +']',79, 2,'text',true, 'left')   
  var Gax_Cod=create_input('datos['+ total.value +','+ 5 +']', 6, 2,'hidden',true, 'right')
  Gax_Cod.value=Gas_Cod;
  Caj_Des.value= Gas_Des;
  columna2.appendChild(Caj_Des);
  columna2.appendChild(Gax_Cod);
  
  /** 
  * Cuadro que muestra precio unitario del producto
  */
  var Caj_Pre=create_input('datos['+ total.value +','+ 3 +']', 6, 6,'text',false, 'right')
  Caj_Pre.value=Gas_Max;
  Caj_Pre.onkeyup= function(){numerico(this);importeAjuste(Gas_Max,auxCant.value);total_ajuste(Cja_Tra,4,1)}; 
  columna3.appendChild(Caj_Pre);
 
  /** 
  * Cuadro que muestra importe del producto
  */
  var Caj_Imp=create_input('datos['+ total.value +','+ 4 +']', 6, 2,'text',true, 'right') 
  var auxCant=create_input('hdd_aux'+total.value, 6, 2,'hidden',false, 'right')
  auxCant.value=total.value
  columna4.appendChild(Caj_Imp);
  
  var Btn_Eli=create_button('quitar_fila','X',function(){ quitar_fila_recur(auxCant.value);total_ajuste(Cja_Tra,4,1)});
  Btn_Eli.title="Eliminar";
  columna5.appendChild(Btn_Eli);
  
  fila.appendChild(columna1);
  fila.appendChild(columna2);
  fila.appendChild(columna3);
  fila.appendChild(columna4);
  fila.appendChild(columna5);
  cuerpo.appendChild(fila);
}

/**
* Funcion para calcular el importe del ajuste
*/
function importeAjuste(Gas_Max,index)
{
	var cant=0;
	var pre_unit=0;
	var valor=0;	
	cant=document.getElementById('datos['+ index +','+ 1 +']').value;		
	pre_unit=document.getElementById('datos['+ index +','+ 3 +']').value;			
	valor = parseFloat(pre_unit) * parseInt(cant);	
	document.getElementById('datos['+ index +','+ 4 +']').value = redondear(valor,2);
}

/**
* Calculo del descuento por importe 
*/
function total_ajuste(Cja_Tra,colum_imp, colum_cant)
{
	var filas = document.getElementById ('nfilas');	
	var total_caja = 0;
	
	for (var j = 1; j <= filas.value; j++)
	{
		importe = document.getElementById ('datos['+ j +','+ colum_imp +']'); //Aqui estaba antes 6		
		if ((importe) && (importe.value.length > 0))
		{
			if (isNaN(importe.value)) { importe.value=0; } 
			total_caja = parseFloat(total_caja) + parseFloat(importe.value);			 
		}
	}
	document.getElementById('txt_total').value= redondear(total_caja,2);
	document.getElementById('txt_total').style.background="";
	document.getElementById ('btn_guardar').style.display="block";
	document.getElementById('txt_total').style.color="";		
}

function cont_filas(contador)
{
	var total = document.getElementById(contador);
	var totaln = (document.getElementById(contador).value -1)+ 2;
	total.value = totaln;
}

/**
* Elimina una fila recursiva 
*/
function quitar_fila_recur(fila)
{
	var padre = document.getElementById('datos['+ fila +','+ 1 +']').parentNode.parentNode;
	padre.parentNode.removeChild (padre);
}