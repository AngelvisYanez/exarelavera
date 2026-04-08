/****************************/
var patron = new Array(4,2,2)
var patron2 = new Array(1,3,3,3,3)
/******************************/
/******************************/
var xOp7Up,xOp6Dn,xIE4Up,xIE4,xIE5,xNN4,xUA=navigator.userAgent.toLowerCase();if(window.opera){var i=xUA.indexOf('opera');if(i!=-1){var v=parseInt(xUA.charAt(i+6));xOp7Up=v>=7;xOp6Dn=v<7;}}else if(navigator.vendor!='KDE' && document.all && xUA.indexOf('msie')!=-1){xIE4Up=parseFloat(navigator.appVersion)>=4;xIE4=xUA.indexOf('msie 4')!=-1;xIE5=xUA.indexOf('msie 5')!=-1;}else if(document.layers){xNN4=true;}xMac=xUA.indexOf('mac')!=-1;function xDef(){for(var i=0; i<arguments.length; ++i){if(typeof(arguments[i])=='undefined') return false;}return true;}function xDisplay(e,s){if(!(e=xGetElementById(e))) return null;if(e.style && xDef(e.style.display)) {if (xStr(s)) e.style.display = s;return e.style.display;}return null;}function xGetElementById(e){if(typeof(e)!='string') return e;if(document.getElementById) e=document.getElementById(e);else if(document.all) e=document.all[e];else e=null;return e;}function xStr(s){for(var i=0; i<arguments.length; ++i){if(typeof(arguments[i])!='string') return false;}return true;}
/*****************************/	
	
function claveigual(campo1,campo2)
{
	if (campo1.value!=campo2.value)
	{
		alert ("Los dos campos de clave deben coincidir");
		campo2.focus()
		return false;
	}
}

function requerido(campo)
{
	//alert(campo.value);
	if (campo.value == "")	
	{
		alert ("El dato de este campo es requerido");
		campo.focus()
		return false;
	}
}

function requerido_option(campo)
{
	if (campo.value == "")	
	{
		alert ("El dato de este campo es requerido, seleccione una opcion");
		return false;
	}
}

function requerido_null(campo)
{ 
	if (campo.value.toLowerCase() == 'null')	
	{
		alert ("El dato de este campo es requerido");
		campo.focus()
		return false;
	}
}

function requerido_check(campo)
{
	if (campo.checked == false)
	 {
		alert ("El dato de este campo es requerido");
		//campo.checked = 'checked';
		campo.focus()		
		return false;
	 }	
}

function maximo(campo, valor)
{
	if (campo.value.length != valor)
	{
		alert ("El dato de este campo debe tener una longitud de " + valor + " caracteres o dígitos");
		campo.focus()
		return false;
	}
}

function minimo(campo, valor)
{
	if (campo.value.length < valor)
	{
		alert ("El dato de este campo debe tener una longitud de mínima de " + valor + " caracteres o dígitos");
		campo.focus()
		return false;
	}
}

/*Nos permite contalar q un munero que tenga un rango especifico ya sea valor1 o valor 2*/
function minimo_maximo(campo, valor1,valor2)
{       
	if (campo.value.length == valor1){return true;}
	else{
		if(campo.value.length != valor2)
		{
			alert ("El dato de este campo debe tener una longitud de " + valor1 + " o " + valor2 + "caracteres o dígitos");
			campo.focus()
			return false;
		}
	}
}

/*Nos permite contalar q un munero que tenga un rango especifico ya sea valor1 o valor 2 o valor 3*/
function maximo_AutSri(campo, valor1,valor2,valor3)
{       
	if (campo.value.length == valor1)
	{
		return true;
	}else{
		if (campo.value.length == valor2)
		{
			return true;
		}else{
			if(campo.value.length != valor3)
			{
				alert ("El dato de este campo debe tener una longitud de " + valor1 + ", " + valor2 + " o " + valor3  + " caracteres o dígitos");
				campo.focus()
				return false;
			}
		}
	}
}

function numerico(campo)
{
	if (isNaN(campo.value))
	{
		alert ("El dato de este campo debe ser numérico");
		campo.focus()
		return false;
	}
}
 
function correo(campo)
{
	if (((campo.value.indexOf('@', 0) == -1) || (campo.value.indexOf('.', 0) == -1)) 
		&& (campo.value != ""))
	{
		alert ("La dirección de e-mail en este campo es inválida");
		campo.focus()
		return false;
	}
}

/* Funcion que valida un caracter que sea requerido */
function caracter(campo, caracter)
{
	if (trim(campo.value) != "")
	{
		if (campo.value.indexOf(caracter, 0) == -1)
		 {
			alert ("El dato de este campo debe contener el siguiente caracter ["+ caracter +"]");
			campo.focus()		
			return false;
		 }	
	}
}

function cien(campo, val)
{
	if (parseFloat(campo.value) > parseFloat(val))
	{
		alert ("Solo se deben ingresar valores menores o iguales a "+val);
		campo.focus();
		return false;
	}
} 

function parametro_x(campo, par)
{
	if (((campo.value.indexOf(par, 0) == -1)) 
		&& (campo.value != ""))
	{
		alert ("La información en este campo es inválida");
		campo.focus()
		return false;
	}
}

function parametro_injection(campo)
{

	if (((campo.value.indexOf("'", 0) != -1)) 
		&& (campo.value != ""))
	{
		alert ("La información en este campo es inválida");
		var texto = campo.value;
		var texto = texto.substring(0,texto.length-1);
		campo.value = texto;		
		campo.focus()
		return false;
	}
}

function fecha_mayor_Periodo(fecha1, fecha2,aux)
{			
	
	if (fecha2.value != "" && fecha1.value != "")
	{
		if(aux==1)
		{
			if ((fecha1.value) > (fecha2.value))
			{
				alert ("!La FECHA DE INICIO debe ser menor (<) a la FECHA DE FIN!");
				fecha1.value='';
				fecha1.focus();				
				return false;
				
			}
		}
		if(aux==2)
		{
			if ((fecha2.value) < (fecha1.value))
			{
				alert ("!La FECHA DE FIN debe ser mayor (>) a la FECHA DE INICIO!");
				fecha2.value='';
				fecha2.focus();
				return false;
			}
		}
	}	
}

function numero_menor(valor1, valor2)
{
	if (parseInt(valor1.value) > parseInt(valor2.value))
	{
		alert ("No puede ingresar valores mayores al valor inicial");
		valor1.focus();
		return false;
	}
}

function numero_mayor(valor1, valor2)
{
	if (parseInt(valor1.value) < parseInt(valor2.value))
	{
		alert ("No puede ingresar valores menores que " + valor1.value);
		valor1.select();
		return false;
	}
}

function fecha_mayor(fecha1, fecha2)
{
	if (fecha2.value != "")
	{
		if ((fecha1.value) > (fecha2.value))
		{
			alert ("!La FECHA DE FIN debe ser mayor (>) a la FECHA DE INICIO!");
			fecha1.focus();
			return false;
		}
	}
}

function positivo(campo)
{
	if (parseInt(campo.value) < 0)
	{
		alert ("Solo se deben ingresar valores positivos");
		campo.focus();
		return false;
	}
}

/* Devuelve falso si la fecha se encuentra fuera de rango */
function rango_fechas(ini, fin, fecha)
{
	if (!(fecha >= ini && fecha <= fin))
	{
		alert ("Las fechas deben estar en el rango del periodo contable seleccionado \n Inicio: " + ini + " \n Fin    : " + fin);	
		return false;				
	}
}

function confirmacion(form)
{
	op= confirm("Est\u00E1 seguro de guardar los datos?");
		  
	if (op == true)
	{
		form.submit();
	}
}

function confirmacion2(form)
{
	op= confirm("Est\u00E1 seguro de realizar esta operaci\u00F3n?");
		  
	if (op == true)
	{
		form.submit();
	}
}

function confirmacion3(form)
{
	op= confirm("Est\u00E1 seguro de realizar esta operaci\u00F3n?");
		  
	if (op == true)
	{
		return(true);
	}
}

function confirmacion_print(form)
{
	//op= confirm("�Esta seguro de imprimir esta informaci�n?");
		  
	//if (op == true)
	//{
		form.submit();
	//}
}

function direccion(url)
{
	if (confirm("Est\u00E1 seguro de realizar esta operaci\u00F3n?")) //Esta eliminar este registro
	{
		location=url;
	}		
}

function ir(url)
{
	location=url;		
}

function contar_check_prueba(form)
{
	var checkboxes = form.checkbox;
	var cont = 0;
	for (var x=0; x < checkboxes.length; x++) 
	{
		 if (checkboxes[x].checked) 
		 {
		    cont = cont + 1;
		  }
	}
	return cont;
}

function todo_check(form,i,check,name)
{	
	if (check.checked)
	{
		valor_booleano = true;
	}
	else
	{
		valor_booleano = false;
	}
	
	for (var x=1; x <= i; x++) 
	{
		 form.elements[name+'['+ x +']'].checked = valor_booleano;
	}
}

/* Chequea o Deschequea todo check ACTUAL */
function checkear(check, i, name)
{	
	if (check.checked)
	{
		valor_booleano = true;
	}
	else
	{
		valor_booleano = false;
	}
	
	for (var x=1; x <= i; x++) 
	{
		var dato = document.getElementById(name+'['+ x +']');                		
                if (dato)
		{		
		 	dato.checked = valor_booleano;
		}//Fin del if (dato)
	}
}

/* Chequea o Deschequea todo check ACTUAL */
function checkear_xml(check, i, obj)
{	
	if (check.checked)
	{
		valor_booleano = true;
	}
	else
	{
		valor_booleano = false;
	}	
	for (var x=0; x <= obj.length; x++) 
	{		            		                
                if (obj[x])
		{		
			obj[x].checked= valor_booleano;
		}//Fin del if (dato)
	}
}

/* Verifica array de checkes si estan seleccionado por lo menos 1*/
function count_check_mensaje(form,obj,msg)
{	 
	/*Cuenta cuantos checkboxes se encuantran selecionados*/
	var cont = 0;	
	var objChek = document.getElementById(form)[obj];
	
        for (var x=0; x < objChek.length; x++) 
	{		            		                
	  if (objChek[x].checked)
	  {		
	     cont=1;                       
	  }
	}		       
        if(cont != 0)
	{
		return true;
	}
	else
	{       
		alert(msg +"...!");
		return false;
	}
}

/* Cuenta los checkes ACTUAL */
function count_check(form, i, name, confir)
{
	/*Cuenta cuantos checkboxes se encuantran selecionados*/
	var cont = 0;
	for (var x=1; x <= i; x++) 
	{
		dato = document.getElementById(name+'['+ x +']');
		if (dato)
		{
			 if (dato.checked) 
			 {
			    cont = cont + 1;
			 }
		}//Fin del if (dato)
	}
	/* Condicion para mostrar el envio del formulario */
	if (cont > 0)
	{
		if (confir == 1)
		{
			confirmacion2(form);	
		}//FIn del if (confir == 1)
		else
		{
			if (confir == 0)
			{
				form.submit();	
			}	
		}//FIn del else if (confir == 1)		
	}
	else
	{
		alert ("!Es necesario que seleccione al menos una opción!");
		return false;
	}
}

//Validacion de un campo requerido
function nle (form, campo)
{
 if (document.getElementById(campo).value != "")
 {
  
  form.submit();
 }
 else
 {
  alert ("!Debe ingresar datos!");
  document.getElementById(campo).focus()
 }
}

//Activa y desactiva el readonly a true o false
function lectura(campo, valor)
{ 
	if ((campo)){
		document.getElementById(campo).readOnly = valor;
	}
} 

/*function deshabilita(form, campo){ 
    document.form.campo.disabled = true; 
  }*/ 

/*Funcion que devuelve el formato de fecha en a�o-mes-dia*/
function formato_fecha(fech)
{
		ann = parseInt(fech.getYear());
		mes = parseInt(fech.getMonth()) ;
		dia = parseInt(fech.getDate());

		if (mes < 10)
		{
			mes = "0" + mes;	
		}
		
		if (dia < 10)
		{
			dia = "0" + dia;	
		}

		return ann + "-" + mes + "-" + dia;
}

function fechas_futuras(fecha, incremento)
{
	/*Divide la fecha inicial en dia, mes y a�o*/
	fech = new String (fecha.value);
	
	var ini_dia = parseInt(fech.substr(8, 2));
	var ini_mes = parseInt(fech.substr(6, 2));
	var ini_ann = parseInt(fech.substr(0, 4));

	fecha_fut = new Date (ini_ann, ini_mes, ini_dia + incremento)
	return formato_fecha(fecha_fut);
}

function limpiar_combo(form, ini)
{
	for (i=form.options.length-1;i>=ini;i--)
	{
		form.options[i]=null;
	}
	
}

function combo_listar(ini, fin, form, op)
{
	for (i=form.options.length-1;i>=0;i--)
	{
		form.options[i]=null;
	}
	
	form.options[form.options.length] = new Option('',0);	
	for (i=parseInt(ini)+1; i<=parseInt(fin); i++)
	{	
		//Este control es para mostrar doble ceros
		//o mostrar por ejemplo 00, 01, 02, 03, 04
		if (op == 1)
		{
			if (i <= 9)
			{
				i = "0" + i;
			}
		}
		form.options[form.options.length] = new Option(i,i);				
	}
}

function validar_subir(archivo, paso)
{	
	if (archivo.value != "" || paso.value != "")
	{
		confirmacion(document.form2);
	}
	else
	{
		alert("Debe seleccionar una archivo de Word. Presione el boton Examinar...");
	}
}

function validar_buscar()
{
	if (document.form1.txt_busqueda.value != "")
	{
		document.form1.submit();
	}
	else
	{
		alert ("!Debe ingresar datos!");
		document.form1.txt_busqueda.focus()
	}
}

function validar_buscar2()
{
	if (document.form1.txt_busqueda.value != "")
	{
		if (document.form1.txt_busqueda2.value != "")
		{
			document.form1.submit();
		}
		else
		{
			alert ("!Debe seleccionar una opción!");
			document.form1.txt_busqueda2.focus()
		}
	}
	else
	{
		alert ("�Debe seleccionar opci�n!");
		document.form1.txt_busqueda.focus()
	}
}

function ShowHide(id) 
{
	
    if(document.getElementById(id).className != "oculta"){
        document.getElementById(id).className = "oculta";
    } else {
        document.getElementById(id).className = "muestra";
    }
}

function set_estilo(componente,estilo)
{
	var navegador = navigator.userAgent.toLowerCase();
	//if (navegador.indexOf('gecko')!=-1) antes
	if (navegador.indexOf('gecko')!=1) 
	{
			componente.setAttribute('class',estilo); //Siempre entra por aqui
	}
	else
	{
			componente.setAttribute('className',estilo);
	}
}

function create_input(nombre,size,maxlength,type,lock,alinear)
{
	var texto = document.createElement('input');
	texto.setAttribute('name',nombre);
	texto.setAttribute('id',nombre);	
	texto.setAttribute('size',size);
	texto.setAttribute('type',type);
	texto.setAttribute('maxlength',maxlength);
	/* Control para no utilizar este parametro y funcione en el mozilla */
	/********************************************************************/
	if (lock==true)
	{
		texto.setAttribute('readOnly',lock);
	}
	/********************************************************************/	
	//texto.setAttribute('align',alinear);		
	texto.style.textAlign = alinear;
	set_estilo(texto,'');
	return texto;
}

function create_button(nombre,value,metodo,onclick)
{ 
 var boton = document.createElement('input');
 boton.setAttribute('name',nombre);
 boton.setAttribute('id',nombre); 
 boton.setAttribute('value',value);
 boton.setAttribute('type','button'); 
 boton.onclick = metodo;
 set_estilo(boton,'BotonEliminar');
 return boton;
}

function create_button_elim(nombre,value,metodo,onclick)
{ 
 var boton = document.createElement('button');
 boton.setAttribute('name',nombre);
 boton.setAttribute('id',nombre);
 var i = document.createElement('i');
 set_estilo(i,'icon-trash icon-white');
 var t = document.createTextNode(" "+value);       // Create a text node
 boton.appendChild(i);  
 boton.appendChild(t);  
 boton.createTextNode==value;
 boton.setAttribute('type','button'); 
 boton.onclick = metodo;
 set_estilo(boton,'btn btn-danger delete');
 return boton;
}

function create_combo(nombre,valores,texto,alinear)
{
	var combo = document.createElement('select');
	combo.setAttribute('name',nombre);
	combo.setAttribute('id',nombre);
	combo.setAttribute('align',alinear);

	var opcion = new Option('',0,false,false);
	combo.options[0]=opcion;
	combo.options[0].value='0*0';

	for (var i=0;i<=valores.length-1;i++)
	{
		var mayor = parseInt(combo.length);
		var opcion = new Option(texto[i],mayor,false,false);
		combo.options[mayor]=opcion;
		combo.options[mayor].value=valores[i];
	}
	return combo;
}

function create_image(nombre,ruta,ancho,alto,tooltip,alinear,borde)
{
	var imagen=document.createElement("img");
	imagen.setAttribute('name',nombre);
	imagen.setAttribute('id',nombre);
	imagen.setAttribute('src',ruta);
	imagen.setAttribute('width',ancho);
	imagen.setAttribute('height',alto);
	imagen.setAttribute('alt',tooltip);
	imagen.setAttribute('align',alinear);
	imagen.setAttribute('border',borde);
	return imagen;
}
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
// Funcion que detecta el navegador y crea el objeto
function getHTTPObject() {
  var xmlhttp;
  /*@cc_on
  @if (@_jscript_version >= 5)
    try {
      xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (E) {
        xmlhttp = false;
      }
    }
  @else
  xmlhttp = false;
  @end @*/
  if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
    try {
      xmlhttp = new XMLHttpRequest();
	  xmlhttp.overrideMimeType("text/xml"); 
    } catch (e) {
      xmlhttp = false;
    }
  }
  return xmlhttp;
}

function validar_fecha2(cuadro){
//	Foramto para 2006/12/12
//	var formato=/^\d{4}\/\d{2}\/\d{2}$/
	var formato=/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/
	if (cuadro.value != "")
	{
			var flag=false
			if (!formato.test(cuadro.value))
			{
				alert("Formato incorrecto de la Fecha. Formato correcto: aaaa-mm-dd. A�o-Mes-D�a");
			}
			else{
				var anio=cuadro.value.split("-")[0]
				var mes=cuadro.value.split("-")[1]
				var dia=cuadro.value.split("-")[2]
				var fecha = new Date(anio, mes-1, dia)
				if ((fecha.getMonth()+1!=mes) || (fecha.getDate()!=dia) || (fecha.getFullYear()!=anio)){  
					alert("Rango de Fecha no v�lido. Formato correcto: aaaa-mm-dd. A�o-Mes-D�a");
					 }
				else {
					flag=true;
				}
			}
		
		//	var mydate=new Date();
		//	var year=mydate.getYear();
		//	if (year < 1000)
		//		year+=1900;
		//	var day=mydate.getDay();
		//	var month=mydate.getMonth()+1;
		//	if (month<10)
		//		month="0"+month;
		//	var daym=mydate.getDate();
		//	if (daym<10)
		//		daym="0"+daym;
		//
		//	var hoy=year+"-"+month+"-"+daym;
		//	
		//	alert(cuadro.value);
		//	alert(hoy);
		//	if (Date.parse(cuadro.value) < Date.parse(hoy))
		//		{
		//			alert("La fecha de cobro no debe ser anterior al dia de emision del cheque");
		//			flag=false;
		//		}
			
			if (flag==false) cuadro.select()
			return flag
	}//Fin del if (cuadro.value != "")
}

/* Permite cargar una ventana personalizada */
function windows(url, nombre, ancho, alto, menu, bar, scrollb, resibl)
{
window.open(url,nombre,"width=" + ancho + ",height=" + alto + ",menubar=" + menu + ",toolbar=" + bar+",scrollbars="+scrollb+",resizable="+resibl);		
}
/* Permite contar y verificar que al menos un check este selecionado */
function contar_checks(form, i)
{
	//Cuenta cuantos checkboxes se encuantran selecionados	
	var cont = 0;
	for (var x=1; x <= i; x++) 
	{
		 if (document.form1.elements['Niv_Cod['+ x +']'].checked) 
		 {
		    cont = cont + 1;
		 }
	}
	/******************************************************/
	if (cont > 0)
	{
		 if (document.form1.elements['Niv_Cod[1]'].value != "")//Este codigo es utilizado cuando no hay semestres que mostrar
		 {//Este codigo es utilizado cuando no hay semestres que mostrar
 	 	    return true;
		 }//Este codigo es utilizado cuando no hay semestres que mostrar
		 //else//Este codigo es utilizado cuando no hay semestres que mostrar
		 //{//Este codigo es utilizado cuando no hay semestres que mostrar
		// 	alert ("No existen semestres abiertos para esta carrera");//Este codigo es utilizado cuando no hay semestres que mostrar
		 //}//Este codigo es utilizado cuando no hay semestres que mostrar
	}
	else
	{
		alert ("Es necesario que selecciones al menos una opci�n");
		return false;
	}	
}

function redondear(cantidad, decimales) {
	var cantidad = parseFloat(cantidad);
	var decimales = parseFloat(decimales);
	decimales = (!decimales ? 2 : decimales);
	return Math.round(cantidad * Math.pow(10, decimales)) / Math.pow(10, decimales);
}

/* Formato sencillo de numeros sin decimales */
function formatNmb(nNmb){ 
    var sRes = ""; 
    for (var j, i = nNmb.length - 1, j = 0; i >= 0; i--, j++) 
     sRes = nNmb.charAt(i) + ((j > 0) && (j % 3 == 0)? ".": "") + sRes; 
    return sRes; 
 } 
 
//Mas en: http://javascript.espaciolatino.com/
//Objeto oNumero
function oNumero(numero)
{
//Propiedades 
this.valor = numero || 0
this.dec = -1;
//M�todos 
this.formato = numFormat;
this.ponValor = ponValor;
//Definici�n de los m�todos 
function ponValor(cad)
{
if (cad =='-' || cad=='+') return
if (cad.length ==0) return
if (cad.indexOf('.') >=0)
    this.valor = parseFloat(cad);
else 
    this.valor = parseInt(cad);
} 
function numFormat(dec, miles)
{
var num = this.valor, signo=3, expr;
var cad = ""+this.valor;
var ceros = "", pos, pdec, i;
for (i=0; i < dec; i++)
ceros += '0';
pos = cad.indexOf('.')
if (pos < 0)
    cad = cad+"."+ceros;
else
    {
    pdec = cad.length - pos -1;
    if (pdec <= dec)
        {
        for (i=0; i< (dec-pdec); i++)
            cad += '0';
        }
    else
        {
        num = num*Math.pow(10, dec);
        num = Math.round(num);
        num = num/Math.pow(10, dec);
        cad = new String(num);
        }
    }
pos = cad.indexOf('.')
if (pos < 0) pos = cad.lentgh
if (cad.substr(0,1)=='-' || cad.substr(0,1) == '+') 
       signo = 4;
if (miles && pos > signo)
    do{
        expr = /([+-]?\d)(\d{3}[\.\,]\d*)/
        cad.match(expr)
        cad=cad.replace(expr, RegExp.$1+','+RegExp.$2)
        }
while (cad.indexOf(',') > signo)
    if (dec<0) cad = cad.replace(/\./,'')
        return cad;
}
}//Fin del objeto oNumero:

/* Controles para Tabs */
/***********************/
/* Cambia los estilos de los Tabs */
function CambiarEstilo(id) {
	var elementosMenu = getElementsByClassName(document, "li", "activo");
	for (k = 0; k< elementosMenu.length; k++) {
	elementosMenu[k].className = "inactivo";
	}
	var identity=document.getElementById(id);
	identity.className="activo";
}

/*
    function getElementsByClassName
    Written by Jonathan Snook, http://www.snook.ca/jonathan
    Add-ons by Robert Nyman, http://www.robertnyman.com
*/

function getElementsByClassName(oElm, strTagName, strClassName){
    var arrElements = (strTagName == "*" && document.all)? document.all : oElm.getElementsByTagName(strTagName);
    var arrReturnElements = new Array();
    strClassName = strClassName.replace(/\-/g, "\\-");
    var oRegExp = new RegExp("(^|\\s)" + strClassName + "(\\s|$)");
    var oElement;
    for(var i=0; i<arrElements.length; i++){
        oElement = arrElements[i];      
        if(oRegExp.test(oElement.className)){
            arrReturnElements.push(oElement);
        }   
    }
    return (arrReturnElements)
}

/**
 * @var url pagina donde se traera el contenido ajax anidado con sus respectivas variables y valores
 * nombre del id del div donde se pegara el nuevo contenido
 */
function ajax_datos(url, objeto) 
{ //alert(url);
 /* var http = getHTTPObject();
  var isWorking = false;
  var contenido = document.getElementById(objeto);

  if (!isWorking && http) {
 http.open("GET", url, true);
    http.onreadystatechange = function () {

     if (http.readyState == 4)
     {
      if (http.responseText.indexOf('invalid') == -1)
      {
        contenido.innerHTML = http.responseText;        
      }
     } }
    isWorking = true;
    http.send(null);
  }*/  
 $("#"+objeto).load(encodeURI(url),function() 
 {  
  
  
  $( "#txt_busqueda" ).focus(function(){
   if($(this).attr("value") == defaultText) $(this).attr("value", "");
   $(this).attr("class", "text active");});
  
  
  
  $( "#txt_busqueda" ).blur(function(){
   if($(this).attr("value") == "") $(this).attr("value", defaultText);
   $(this).attr("class", "text");});
  
  var forms = document.forms;
  
  for (var i=0; i<= forms.length-1; i++)
  {
   if(forms[i].name != ''){
    $("#"+forms[i].name).find(':input:text').each(function() {
      $(this).focus(function(){
      $(this).attr("class", "text active");});
      
      $(this).blur(function(){
      $(this).attr("class", "text");});
      
      if($(this).attr("onKeyUp") == "mascara(this,'-',patron,true)"){
       $(this).datepicker({dateFormat: "yy-mm-dd"});
      }	  
    });
   }
  }
  
  if (document.getElementById('bgmodal'))
  {
   $('#bgmodal *').tooltip({showURL: false});
  
   $('.fixedHeader01').fixedHeaderTable({ height: '250', footer: false, cloneHeadToFoot: true, altClass: 'odd', themeClass: 'fancyTable', autoShow: false });    
   $('.fixedHeader01').fixedHeaderTable('show', 1000); 
   $('.fixedHeader02').fixedHeaderTable({ height: '250', footer: true, altClass: 'odd', themeClass: 'fancyTable' });
  }
 }); 
}

/* Paleta de colores - Asigna el color al campo */
function showColor(campo, color) {
	document.getElementById(campo).value = color;
	document.getElementById(campo).style.background = color;
	document.getElementById(campo).style.color = color;
	document.getElementById(campo).style.border = color;	
}

// Validaci�n de c�dula, ruc y pasaporte ecuatorianos.
//
// Este es un ejemplo de la invocaci�n del m�todo para validar un documento
// El usuario debe ajustarlo adecuadamente a su c�digo.
//
// Creado por: Carlos Julio P�rez Quizhpe
// Fono: 093-208265
// cjperez@espol.edu.ec
// carlosjulioperez@gmail.com
// carlosjulio3ech@hotmail.com

/*Esto valida:
1. El Ruc
2. C�dula de identidad
3. Pasaporte.*/

function validarDocumento( campo ) {
numero = campo.value;
var suma = 0;
var p1=0;
var residuo = 0;
var pri = false;
var pub = false;
var nat = false;
var numeroProvincias = 22;
var modulo = 11;

/* Verifico que el campo no contenga letras */
var ok=1;
for (i=0; i; numeroProvincias){
alert('El código de la provincia (dos primeros dígitos) es inválido'); 
campo.focus();
return false;
}

/* Aqui almacenamos los digitos de la cedula en variables. */
d1 = numero.substr(0,1);
d2 = numero.substr(1,1);
d3 = numero.substr(2,1);
d4 = numero.substr(3,1);
d5 = numero.substr(4,1);
d6 = numero.substr(5,1);
d7 = numero.substr(6,1);
d8 = numero.substr(7,1);
d9 = numero.substr(8,1);
d10 = numero.substr(9,1); 

/* El tercer digito es: */
/* 9 para sociedades privadas y extranjeros */
/* 6 para sociedades publicas */
/* menor que 6 (0,1,2,3,4,5) para personas naturales */ 

if (d3==7 || d3==8){
alert('El tercer dígito ingresado es inválido');
campo.focus();
return false;
} 

/* Solo para personas naturales (modulo 10) */
if (d3 < 6){
nat = true;
p1 = d1 * 2; if (p1 >= 10) p1 -= 9;
p2 = d2 * 1; if (p2 >= 10) p2 -= 9;
p3 = d3 * 2; if (p3 >= 10) p3 -= 9;
p4 = d4 * 1; if (p4 >= 10) p4 -= 9;
p5 = d5 * 2; if (p5 >= 10) p5 -= 9;
p6 = d6 * 1; if (p6 >= 10) p6 -= 9;
p7 = d7 * 2; if (p7 >= 10) p7 -= 9;
p8 = d8 * 1; if (p8 >= 10) p8 -= 9;
p9 = d9 * 2; if (p9 >= 10) p9 -= 9;
modulo = 10;
} 

/* Solo para sociedades publicas (modulo 11) */
/* Aqui el digito verficador esta en la posicion 9, en las otras 2 en la pos. 10 */
else if(d3 == 6){
pub = true;
p1 = d1 * 3;
p2 = d2 * 2;
p3 = d3 * 7;
p4 = d4 * 6;
p5 = d5 * 5;
p6 = d6 * 4;
p7 = d7 * 3;
p8 = d8 * 2;
p9 = 0;
} 

/* Solo para entidades privadas (modulo 11) */
else if(d3 == 9) {
pri = true;
p1 = d1 * 4;
p2 = d2 * 3;
p3 = d3 * 2;
p4 = d4 * 7;
p5 = d5 * 6;
p6 = d6 * 5;
p7 = d7 * 4;
p8 = d8 * 3;
p9 = d9 * 2;
}

suma = p1 + p2 + p3 + p4 + p5 + p6 + p7 + p8 + p9;
residuo = suma % modulo; 

/* Si residuo=0, dig.ver.=0, caso contrario 10 - residuo*/
digitoVerificador = residuo==0 ? 0: modulo - residuo; 

/* ahora comparamos el elemento de la posicion 10 con el dig. ver.*/
if (pub==true){
if (digitoVerificador != d9){
alert('El ruc de la empresa del sector público es incorrecto.');
campo.focus();
return false;
}
/* El ruc de las empresas del sector publico terminan con 0001*/
if ( numero.substr(9,4) != '0001' ){
alert('El ruc de la empresa del sector público debe terminar con 0001');
campo.focus();
return false;
}
}
else if(pri == true){
if (digitoVerificador != d10){
alert('El ruc de la empresa del sector privado es incorrecto.');
campo.focus();
return false;
}
if ( numero.substr(10,3) != '001' ){
alert('El ruc de la empresa del sector privado debe terminar con 001');
campo.focus();
return false;
}
} 

else if(nat == true){
if (digitoVerificador != d10){
alert('El número de cédula de la persona natural es incorrecto.');
campo.focus();
return false;
}
if (numero.length >10 && numero.substr(10,3) != '001' ){
alert('El ruc de la persona natural debe terminar con 001');
campo.focus();
return false;
}
}
return true;
}

/* Ubica el puntero en objeto especificado */
function setfocus(campo)
{
	if (campo)
	{
		campo.focus();
	}
}

/********* Evita el envio del formulario al dar enter en el cuadro de texto ***************************************/
function Ent_Sub() 
{    
	if (event.keyCode == 13) 
	{        
		event.cancelBubble = true;
		event.returnValue = false;
         }
} 

/********* Funcion para colocar m�scaras de fechas *************************/
function mascara(d,sep,pat,nums){
if(d.valant != d.value){
	val = d.value
	largo = val.length
	val = val.split(sep)
	val2 = ''
	for(r=0;r<val.length;r++){
		val2 += val[r]	
	}
	if(nums){
		for(z=0;z<val2.length;z++){
			if(isNaN(val2.charAt(z))){
				letra = new RegExp(val2.charAt(z),"g")
				val2 = val2.replace(letra,"")
			}
		}
	}
	val = ''
	val3 = new Array()
	for(s=0; s<pat.length; s++){
		val3[s] = val2.substring(0,pat[s])
		val2 = val2.substr(pat[s])
	}
	for(q=0;q<val3.length; q++){
		if(q ==0){
			val = val3[q]
		}
		else{
			if(val3[q] != ""){
				val += sep + val3[q]
				}
		}
	}
	d.value = val
	d.valant = val
	}
}

//Funcion que permite verificar que un producto no se ingrese nuevamente en el detalle
function verificar_prod(colum, prod)
{
	var retorno = true;
    var total = document.getElementById('nfilas');
	for (var i=1;i<=total.value;i++)
	{		
		dato = document.getElementById('datos['+ i +','+ colum +']');
		if ((dato)){
			//Valida que no se repida un rubro o producto, con excepcion de un campo vacio
			if (dato.value == prod && prod != '' && prod != 0){ 
				retorno = false;
				break;
			}
		}
	}
	return retorno;
}

/* Funcion que permita dar ENTER en los cuadros te texto con AJAX */
function enter_ajax(url, contenido)
{
	if (event.keyCode == 13) {
		ajax_datos(url, contenido);
	}
}


function trim(stringToTrim) {
	return stringToTrim.replace(/^\s+|\s+$/g,"");
}

function ltrim(stringToTrim) {
	return stringToTrim.replace(/^\s+/,"");
}

function rtrim(stringToTrim) {
	return stringToTrim.replace(/\s+$/,"");
}


/* PROBAR ESTA FUNCION NUEVA */
function validar_buscar_standar(form, campo)
{
	if (form.campo.value != "")
	{
		form.submit();
	}
	else
	{
		alert ("!Debe ingresar datos!");
		form.campo.focus()
	}
}

/* Permite mostrar y ocultar el constenido de botones inferiores de la factura-comprobantes */
function mas_menos(boton, nombre1, nombre2, indice)
{
 
 switch(boton){
     case 1 : /* Boton mas */          
  document.getElementById(nombre1).style.display = "None";
  document.getElementById(nombre2).style.display = "Inline";         
  document.getElementById('detalle['+ indice + ']').style.display = "Inline";
        break;
     case 2 : /* Boton menos */
        document.getElementById(nombre1).style.display = "Inline";
  document.getElementById(nombre2).style.display = "None";         
  document.getElementById('detalle['+ indice + ']').style.display = "None"; 
        break;
  }
}

/* Permite habilitar y deshabilitrar un objeto */
function DisabEnab(id) 
{
	if(document.getElementById(id).disabled == false){
        document.getElementById(id).disabled = true;
    } else {
        document.getElementById(id).disabled = false;
    }
}

/* Funcion que define un formato dinamico */
function validar_formato(cuadro,formato,mensaje){	
	if (cuadro.value != "")
	{
			var flag=false
			if (!formato.test(cuadro.value))
			{
				alert(mensaje);
			}
			else{
				flag=true;
			}
			if (flag==false) cuadro.select()
			return flag
	}//Fin del if (cuadro.value != "")
}

/* Funcion para crear campos y moverse hacia adelante o atras */
function campos_volver(form, campos_name, campos_val)
{
	var campos = new String;
	campos.value = campos_name;
	var a_campos = campos.value.split("*");

	var campos_v = new String;
	campos_v.value = campos_val;
	var a_campos_v = campos_v.value.split("*");

	for (i=0; i<=a_campos.length-1; i++)
	{
		var cuadro=create_input(a_campos[i],100,100,'hidden',false,'left');
		cuadro.value = a_campos_v[i]
		form.appendChild(cuadro); 
	}//Fin del for (i=1; i<=num; i++)

	var campo_volver=create_input('hdd_volver',1,1,'hidden',false,'left');
	form.appendChild(campo_volver); 

	/* Envio del formulario */
	form.submit();
}//Fin del function campos_volver(form, campos_name, campos_val)

/* Permite validar una cantidad n... de campos 
confirmacion :
0=no
1=si
*/
function validar_requeridos(form, campos, confir)
{
	str_campos = new String;
	str_campos.value = campos;
	var array_campos = str_campos.value.split("*");
	enviar = false;
	for (i=0; i<=array_campos.length-1; i++)
	{
		campo = document.getElementById(array_campos[i]);
		if ((requerido(campo) != false))		
			enviar = true;
		else
		{
			enviar = false;
			break;
		}
			
	}//Fin del for (i=0; i<=array_campos.length; i++)
	
	if (enviar == true)
	{
		if (confir == 1)
		{
			confirmacion(form);	
		}
		else
		{
			form.submit();	
		}
	}
}//Fin del function validar_requeridos(form, campos)


/* Funcion que verfica cuantos dias tiene un mes dependiendo
del a�o y el mes */
function cuantosDias(mes, anyo)
    {
        var cuantosDias = 31;
        if (mes == "Abril" || mes == "Junio" || mes == "Septiembre" || mes == "Noviembre")
      cuantosDias = 30;
        if (mes == "Febrero" && (anyo/4) != Math.floor(anyo/4))
      cuantosDias = 28;
        if (mes == "Febrero" && (anyo/4) == Math.floor(anyo/4))
      cuantosDias = 29;
        return cuantosDias;
    }

/* Funcion que verfica cuantos dias tiene un mes dependiendo
del a�o y el mes(n�meros) */
function cuantosDiasNum(mes, anyo)
    {
        var cuantosDias = 31;
        if (mes == 4 || mes == 6 || mes == 9 || mes == 11)
      cuantosDias = 30;
        if (mes == 2 && (anyo/4) != Math.floor(anyo/4))
      cuantosDias = 28;
        if (mes == 2 && (anyo/4) == Math.floor(anyo/4))
      cuantosDias = 29;	  
        return cuantosDias;
    }

/* Asigna los dias a un combo dinamicamente */
function asignaDias(comboDias, comboMeses, comboAnyos)
 {
        	Month = comboMeses[comboMeses.selectedIndex].text;
	        Year = comboAnyos.value;
	        diasEnMes = cuantosDias(Month, Year);
	        diasAhora = comboDias.length;

	        if (diasAhora - 1 > diasEnMes)//ojo
	        {
	            for (i=0; i<(diasAhora-diasEnMes)-1; i++)//ojo
	            {
	                comboDias.options[comboDias.options.length - 1] = null
	            }
	        }
	        if (diasEnMes + 1 > diasAhora)//ojo
	        {
	            for (i=0; i<(diasEnMes-diasAhora)+1; i++)//ojo
	            {
	                sumaOpcion = new Option(comboDias.options.length); 
	                comboDias.options[comboDias.options.length]=sumaOpcion; 
	            }
	        }
	        if (comboDias.selectedIndex < 0) 
	          comboDias.selectedIndex = 0;
 }

/* Funcion para crear campos dinamicamente */
function campos_hide(form, campos_name, campos_val)
{
	var campos = new String;
	campos.value = campos_name;
	var a_campos = campos.value.split("*");

	var campos_v = new String;
	campos_v.value = campos_val;
	var a_campos_v = campos_v.value.split("*");

	for (i=0; i<=a_campos.length-1; i++)
	{
		var cuadro=create_input(a_campos[i],100,100,'hidden',false,'left');
		cuadro.value = a_campos_v[i]
		form.appendChild(cuadro); 
	}//Fin del for (i=1; i<=num; i++)

	/* Envio del formulario */
	form.submit();
}//Fin del function campos_volver(form, campos_name, campos_val)

/* Pone de color azul el fondo y blanca la letra de la caja de texto*/
function enfoque(input, onoff)
{
	if (onoff == true)
	{
		/* Pone el foco */
		input.style.backgroundColor="#003366";	//Fondo["background-color"]="#
		input.style.color="#FFFFFF"; //Letra
	}//Fin del if (onoff == true)
	else
	{
		/* Quita el foco */
		input.style.backgroundColor="";	//Fondo
		input.style.color="#000000"; //Letra	
	}//Fin del if (onoff == true)
}

/* Funcion que evitar el subimit */
function stopRKey(evt) {
var evt = (evt) ? evt : ((event) ? event : null);
var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
if ((evt.keyCode == 13) && (node.type=="text")) {return false;}
}

/* Funcione que permiter ingresar entre un intervalo */
function entre(val_ini, val_fin, campo)
{
	var num1=parseFloat(val_ini);
	var num2=parseFloat(val_fin);

	if (!((campo.value >=num1) && (campo.value <= num2)))
	{
		alert ("<<Debe ingresar valores entre " + num1 + " y " + num2 + " >>" );
		campo.focus();
		return false;		
	}
}//FIn del entre(val_ini, val_fin, campo)

/* Suma los valores de un arreglo de objetos HTML */
function SumaArray(cant, campos, total) 
{
 var suma=0;
 for (var i=1;i<=cant;i++) 
  {
	 valor = document.getElementById(campos+'['+ i +']');
	 if (valor)
	 {
		if (parseFloat(valor.value) > 0)
		{
			suma=suma + parseFloat(valor.value);
		}
	 }
  }
  if (isNaN(suma)) 
		{ suma=0; } 		
  document.getElementById(total).value =redondear(suma,2); 
}//FIn del SumaArray(cant, campos, total)

/* funciona que permite validar el ingreso de solo letras en cuadros de texto
e=variable del evento
*/
function validar_string(e) { 
    tecla = (document.all) ? e.keyCode : e.which; 
    if (tecla==13 || tecla==8) return true; 
    patron =/[A-Za-z��\s]/; 
    te = String.fromCharCode(tecla); 	
    return patron.test(te); 
}

/* funcion que permita validar el ingreso de solo numeros en cuadros de texto*/
function validar_numeric(e) { 
    tecla = (document.all) ? e.keyCode : e.which; 
    if (tecla==13 || tecla==8) return true; 
    patron =/^[0-9]*$/; // 4
    te = String.fromCharCode(tecla); 	
    return patron.test(te); 
}

/* funcion que permita el ingreso de caracteres especiales*/
function validar_injections(e) { 
    tecla = (document.all) ? e.keyCode : e.which; 
    if (tecla==13 || tecla==8) return true; 
    patron=/[A-Za-z��0-9\_\-\.\:\$\%\ \[\]\(\)]/
    te = String.fromCharCode(tecla); 	
    return patron.test(te); 
}

/* funcion que permita validar el ingreso de solo numeros decimales en cuadros de texto*/
function validar_decimal(e) { 
    tecla = (document.all) ? e.keyCode : e.which;  
    if (tecla==13 || tecla==8) return true; 
    patron =/^[0-9\.]*$/; // 4
    te = String.fromCharCode(tecla); 	
    return patron.test(te); 
}

function numeroMenor_Msn(valor1, valor2, Msn)
{	
	if (parseInt(valor1.value) > parseInt(valor2.value))
	{
		alert (Msn +" "+ valor2.value);
		valor1.value='';
		valor1.focus();
		return false;		
	}
}

function numeroMayor_Msn(valor1, valor2, Msn)
{
	if (parseInt(valor1.value) < parseInt(valor2.value))
	{
		alert (Msn +" "+ valor2.value);
		valor1.value='';
		valor1.focus();
		return false;
	}
}

 function valida_horas(edit){
      if(event.keyCode<48 || event.keyCode>57){
        event.returnValue=false;
      }
      if(edit.value.length==2 || edit.value.length==5){
        edit.value+=":";}
}

function DataHora(evento,objeto){
	var keypress=(window.event)?event.keyCode:evento.which;
	campo = eval (objeto);
	if (campo.value == '00:00:00')
	{
		campo.value=""
	}
	caracteres = '0123456789';
	separacao1 = '/';
	separacao2 = ' ';
	separacao3 = ':';
	conjunto1 = 2;
	conjunto2 = 5;
	conjunto3 = 10;
	conjunto4 = 13;
	conjunto5 = 16;
	if ((caracteres.search(String.fromCharCode (keypress))!=-1) && campo.value.length < (8))
	{
		if (campo.value.length == conjunto1)
		campo.value = campo.value + separacao3;
		else if (campo.value.length == conjunto2)
		campo.value = campo.value + separacao3;
		else if (campo.value.length == conjunto3)
		campo.value = campo.value + separacao3;
	}
	else
	event.returnValue = false;
}

function create_text(txtPrint)
{
	var objTxt = document.createElement("objTxt");
	cadTxt = document.createTextNode(txtPrint);
	objTxt.appendChild(cadTxt);
	//set_estilo(objTxt,);
	return objTxt;	
}

/* Funcion para generar un timer que ejecuta en un tiempo determinado 
Puede ser llamado en el evento onLoad del BODY 
metodo = funcion que deseamos que se ejecute
tiempo = tiempo dado en segundos para la ejecuci�n de metodo */
function timer(metodo, tiempo)
{
	setInterval(metodo, 1000 * tiempo);	
}//Fin del function timer(metodo, tiempo)

/* Funcion para generar un timerOut que ejecuta en un tiempo determinado 
Puede ser llamado en el evento onLoad del BODY 
metodo = funcion que deseamos que se ejecute
tiempo = tiempo dado en segundos para la ejecuci�n de metodo */
function timerOut(metodo, tiempo)
{
	setTimeout(metodo, 1000 * tiempo);	
}//Fin del function timerOut(metodo, tiempo)

/* Permite validar una cantidad n... de campos 
confirmacion :
0=no
1=si
*/
function validar_requeridos_check(form, campos, confir)
{
	str_campos = new String;
	str_campos.value = campos;
	var array_campos = str_campos.value.split("*");
	enviar = false;
	for (i=0; i<=array_campos.length-1; i++)
	{
		campo = document.getElementById(array_campos[i]);
		
		if (requerido_check(campo) != false)
			enviar = true;
		else
		{
			enviar = false;
			break;
		}
			
	}//Fin del for (i=0; i<=array_campos.length; i++)
	
	if (enviar == true)
	{
		if (confir == 1)
		{
			confirmacion(form);	
		}
		else
		{
			form.submit();	
		}
	}
}//Fin del function validar_requeridos_check(form, campos)

/* Play de Parpadeo */
function Intermitencia() 
{
	if (!blink.style.color) 
	{
		blink.style.color="#ff0000"
	}
	else
		if (blink.style.color=="#ff0000") 
		{
			blink.style.color="#ff3333"
		}
		else
			if (blink.style.color=="#ff3333") 
			{
				blink.style.color="#ff6666"
			}
			else
				if (blink.style.color=="#ff6666") 
				{
					blink.style.color="#ff9999"
				}
				else
					if (blink.style.color=="#ff9999") 
					{
						blink.style.color="#ffcccc"
					}
					else
						if (blink.style.color=="#ffcccc") 
						{
							blink.style.color="#ffffff"
						}
						else 
						{
							blink.style.color="#ff0000"
						}
  tiempo=setTimeout("Intermitencia()",100)
}

/* Stop del Parpadeo*/
function pararParpadeo()
{
	clearTimeout(tiempo);	
}

/* Valida los options */
function validar_options(form)
{
	for (i=0;i<form.elements.length;i++)
	{
		if ((form.elements[i].type=="radio") && (form.elements[i].checked))
		{
			 return true;
		}
	}
	alert("Debe escoger una opcion"); 
}

var tooltip=function(){
	var id = 'tt';
	var top = 3;
	var left = 3;
	var maxw = 300;
	var speed = 10;
	var timer = 10;
	var endalpha = 95;
	var alpha = 0;
	var tt,t,c,b,h;
	var ie = document.all ? true : false;
	return{
		show:function(v,w){
			if(tt == null){
				tt = document.createElement('div');
				tt.setAttribute('id',id);
				t = document.createElement('div');
				t.setAttribute('id',id + 'top');
				c = document.createElement('div');
				c.setAttribute('id',id + 'cont');
				b = document.createElement('div');
				b.setAttribute('id',id + 'bot');
				tt.appendChild(t);
				tt.appendChild(c);
				tt.appendChild(b);
				document.body.appendChild(tt);
				tt.style.opacity = 0;
				tt.style.filter = 'alpha(opacity=0)';
				document.onmousemove = this.pos;
			}
			tt.style.display = 'block';
			c.innerHTML = v;
			tt.style.width = w ? w + 'px' : 'auto';
			if(!w && ie){
				t.style.display = 'none';
				b.style.display = 'none';
				tt.style.width = tt.offsetWidth;
				t.style.display = 'block';
				b.style.display = 'block';
			}
			if(tt.offsetWidth > maxw){tt.style.width = maxw + 'px'}
			h = parseInt(tt.offsetHeight) + top;
			clearInterval(tt.timer);
			tt.timer = setInterval(function(){tooltip.fade(1)},timer);
		},
		pos:function(e){
			
			var u = event.clientY ;
			var l = event.clientX ;
			tt.style.top = (u - h) + 'px';
			tt.style.left = (l + left) + 'px';
		},
		fade:function(d){
			var a = alpha;
			if((a != endalpha && d == 1) || (a != 0 && d == -1)){
				var i = speed;
				if(endalpha - a < speed && d == 1){
					i = endalpha - a;
				}else if(alpha < speed && d == -1){
					i = a;
				}
				alpha = a + (i * d);
				tt.style.opacity = alpha * .01;
				tt.style.filter = 'alpha(opacity=' + alpha + ')';
			}else{
				clearInterval(tt.timer);
				if(d == -1){tt.style.display = 'none'}
			}
		},
		hide:function(){
			clearInterval(tt.timer);
			tt.timer = setInterval(function(){tooltip.fade(-1)},timer);
		}
	};
}();

function asignarClassFocoText()
{
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

	/*document.write("var forms = document.forms; for (i=0; i<= forms.length-1; i++) { var texts = document.forms.item(i).getElementsByTagName('input'); for (j=0; j<= texts.length-1; j++) { if (texts.item(j).type == 'text') {		input = texts.item(j);	set_estilo(input,'text'); } } }");*/
}

/**
 * aparece la seccion de codigo correspondiente del modal actual oculto
 */
function Muestra_Aparecer()
{
	var ancho = 700; 
	var alto = 400;

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

/**
 * @var url pagina donde se traera el contenido ajax anidado con sus respectivas variables y valores
 * nombre del id del div donde se pegara el nuevo contenido
 */
function ajax_classic(url, objeto) 
{
  var http = getHTTPObject();
  var isWorking = false;
  var contenido = document.getElementById(objeto);

  if (!isWorking && http) {
 	http.open("GET", url, true);
    http.onreadystatechange = function () {

     if (http.readyState == 4)
     {
      if (http.responseText.indexOf('invalid') == -1)
      {
        contenido.innerHTML = http.responseText;        
      }
     } }
    isWorking = true;
    http.send(null);
  }
}
/*Función José Ambuludi con fundamentos de Carlos Julio PÃ©rez*/
//Validar cedula
function validar_cedula(cedula,campo)
{
	var isNotOk;
	var numero = cedula;
	
	if(numero!="")
	{
		var suma = 0;
		var p1=0;
		var residuo = 0;
		var pri = false;
		var pub = false;
		var nat = false;
		var numeroProvincias = 22;
		var modulo = 11;
		
		/* Verifico que el campo no contenga letras */
		var ok=1;
		for (i=0; i; numeroProvincias){
		$('#'+campo).focus();
		$('#'+campo).val('');
		alert("El c"+'\u00f3'+"digo de la provincia (dos primeros dÃ­gitos) es inv"+'\u00e1'+"lido"); 
		return false;
		}
		
		/* Aqui almacenamos los digitos de la cedula en variables. */
		d1 = numero.substr(0,1);
		d2 = numero.substr(1,1);
		d3 = numero.substr(2,1);
		d4 = numero.substr(3,1);
		d5 = numero.substr(4,1);
		d6 = numero.substr(5,1);
		d7 = numero.substr(6,1);
		d8 = numero.substr(7,1);
		d9 = numero.substr(8,1);
		d10 = numero.substr(9,1); 
		
		/* El tercer digito es: */
		/* 9 para sociedades privadas y extranjeros */
		/* 6 para sociedades publicas */
		/* menor que 6 (0,1,2,3,4,5) para personas naturales */ 
		
		if (d3==7 || d3==8){
		$('#'+campo).focus();
		$('#'+campo).val('');
		alert("El tercer d"+'\u00ed'+"gito ingresado es inv"+'\u00e1'+"lido");
		return false;
		} 
		
		/* Solo para personas naturales (modulo 10) */
		if (d3 < 6){
		nat = true;
		p1 = d1 * 2; if (p1 >= 10) p1 -= 9;
		p2 = d2 * 1; if (p2 >= 10) p2 -= 9;
		p3 = d3 * 2; if (p3 >= 10) p3 -= 9;
		p4 = d4 * 1; if (p4 >= 10) p4 -= 9;
		p5 = d5 * 2; if (p5 >= 10) p5 -= 9;
		p6 = d6 * 1; if (p6 >= 10) p6 -= 9;
		p7 = d7 * 2; if (p7 >= 10) p7 -= 9;
		p8 = d8 * 1; if (p8 >= 10) p8 -= 9;
		p9 = d9 * 2; if (p9 >= 10) p9 -= 9;
		modulo = 10;
		} 
		
		/* Solo para sociedades publicas (modulo 11) */
		/* Aqui el digito verficador esta en la posicion 9, en las otras 2 en la pos. 10 */
		else if(d3 == 6){
		pub = true;
		p1 = d1 * 3;
		p2 = d2 * 2;
		p3 = d3 * 7;
		p4 = d4 * 6;
		p5 = d5 * 5;
		p6 = d6 * 4;
		p7 = d7 * 3;
		p8 = d8 * 2;
		p9 = 0;
		} 
		
		/* Solo para entidades privadas (modulo 11) */
		else if(d3 == 9) {
		pri = true;
		p1 = d1 * 4;
		p2 = d2 * 3;
		p3 = d3 * 2;
		p4 = d4 * 7;
		p5 = d5 * 6;
		p6 = d6 * 5;
		p7 = d7 * 4;
		p8 = d8 * 3;
		p9 = d9 * 2;
		}
		
		suma = p1 + p2 + p3 + p4 + p5 + p6 + p7 + p8 + p9;
		residuo = suma % modulo; 
		
		/* Si residuo=0, dig.ver.=0, caso contrario 10 - residuo*/
		digitoVerificador = residuo==0 ? 0: modulo - residuo; 
		
		/* ahora comparamos el elemento de la posicion 10 con el dig. ver.*/
		if (pub==true){
		if (digitoVerificador != d9){
		$('#'+campo).focus();
		$('#'+campo).val('');
		alert("El ruc de la empresa del sector p"+'\u00fa'+"blico es incorrecto.");
		return false;
		}
		/* El ruc de las empresas del sector publico terminan con 0001*/
		if ( numero.substr(9,4) != '0001' ){
		$('#'+campo).focus();
		$('#'+campo).val('');
		alert("El ruc de la empresa del sector p"+'\u00fa'+"blico debe terminar con 0001");
		return false;
		}
		}
		else if(pri == true){
		if (digitoVerificador != d10){
		$('#'+campo).focus();
		$('#'+campo).val('');
		alert('El ruc de la empresa del sector privado es incorrecto.');
		return false;
		}
		if ( numero.substr(10,3) != '001' ){
		$('#'+campo).focus();
		$('#'+campo).val('');
		alert('El ruc de la empresa del sector privado debe terminar con 001');
		return false;
		}
		} 
		
		else if(nat == true){
			if (digitoVerificador != d10){
			$('#'+campo).focus();
			$('#'+campo).val('');
			alert("El n"+'\u00fa'+"mero de c"+'\u00e9'+"dula de la persona natural es incorrecto.");
			return false;
			isNotOk=true;
			}
		
			if (numero.length >10 && numero.substr(10,3) != '001' ){
			$('#'+campo).focus();
			$('#'+campo).val('');
			alert('El ruc de la persona natural debe terminar con 001');
			return false;
			}
		}
	}
        if(isNotOk)
        {
            return false;
        }
        else
        {
            return true;
        }
}


var md5=(function(){function e(e,t){var o=e[0],u=e[1],a=e[2],f=e[3];o=n(o,u,a,f,t[0],7,-680876936);f=n(f,o,u,a,t[1],
12,-389564586);a=n(a,f,o,u,t[2],17,606105819);u=n(u,a,f,o,t[3],22,-1044525330);o=n(o,u,a,f,t[4],7,-176418897);f=n(f,o,u,a,t[5],
12,1200080426);a=n(a,f,o,u,t[6],17,-1473231341);u=n(u,a,f,o,t[7],22,-45705983);o=n(o,u,a,f,t[8],7,1770035416);f=n(f,o,u,a,t[9],
12,-1958414417);a=n(a,f,o,u,t[10],17,-42063);u=n(u,a,f,o,t[11],22,-1990404162);o=n(o,u,a,f,t[12],7,1804603682);f=n(f,o,u,a,t[13],
12,-40341101);a=n(a,f,o,u,t[14],17,-1502002290);u=n(u,a,f,o,t[15],22,1236535329);o=r(o,u,a,f,t[1],5,-165796510);f=r(f,o,u,a,t[6],
9,-1069501632);a=r(a,f,o,u,t[11],14,643717713);u=r(u,a,f,o,t[0],20,-373897302);o=r(o,u,a,f,t[5],5,-701558691);f=r(f,o,u,a,t[10],
9,38016083);a=r(a,f,o,u,t[15],14,-660478335);u=r(u,a,f,o,t[4],20,-405537848);o=r(o,u,a,f,t[9],5,568446438);f=r(f,o,u,a,t[14],
9,-1019803690);a=r(a,f,o,u,t[3],14,-187363961);u=r(u,a,f,o,t[8],20,1163531501);o=r(o,u,a,f,t[13],5,-1444681467);f=r(f,o,u,a,t[2],
9,-51403784);a=r(a,f,o,u,t[7],14,1735328473);u=r(u,a,f,o,t[12],20,-1926607734);o=i(o,u,a,f,t[5],4,-378558);f=i(f,o,u,a,t[8],
11,-2022574463);a=i(a,f,o,u,t[11],16,1839030562);u=i(u,a,f,o,t[14],23,-35309556);o=i(o,u,a,f,t[1],4,-1530992060);f=i(f,o,u,a,t[4],
11,1272893353);a=i(a,f,o,u,t[7],16,-155497632);u=i(u,a,f,o,t[10],23,-1094730640);o=i(o,u,a,f,t[13],4,681279174);f=i(f,o,u,a,t[0],
11,-358537222);a=i(a,f,o,u,t[3],16,-722521979);u=i(u,a,f,o,t[6],23,76029189);o=i(o,u,a,f,t[9],4,-640364487);f=i(f,o,u,a,t[12],
11,-421815835);a=i(a,f,o,u,t[15],16,530742520);u=i(u,a,f,o,t[2],23,-995338651);o=s(o,u,a,f,t[0],6,-198630844);f=s(f,o,u,a,t[7],
10,1126891415);a=s(a,f,o,u,t[14],15,-1416354905);u=s(u,a,f,o,t[5],21,-57434055);o=s(o,u,a,f,t[12],6,1700485571);f=s(f,o,u,a,t[3],
10,-1894986606);a=s(a,f,o,u,t[10],15,-1051523);u=s(u,a,f,o,t[1],21,-2054922799);o=s(o,u,a,f,t[8],6,1873313359);f=s(f,o,u,a,t[15],
10,-30611744);a=s(a,f,o,u,t[6],15,-1560198380);u=s(u,a,f,o,t[13],21,1309151649);o=s(o,u,a,f,t[4],6,-145523070);f=s(f,o,u,a,t[11],
10,-1120210379);a=s(a,f,o,u,t[2],15,718787259);u=s(u,a,f,o,t[9],21,-343485551);e[0]=m(o,e[0]);e[1]=m(u,e[1]);e[2]=m(a,e[2]);e[3]=m(f,e[3])}
function t(e,t,n,r,i,s){t=m(m(t,e),m(r,s));return m(t<<i|t>>>32-i,n)}function n(e,n,r,i,s,o,u){return t(n&r|~n&i,e,n,s,o,u)}
function r(e,n,r,i,s,o,u){return t(n&i|r&~i,e,n,s,o,u)}function i(e,n,r,i,s,o,u){return t(n^r^i,e,n,s,o,u)}
function s(e,n,r,i,s,o,u){return t(r^(n|~i),e,n,s,o,u)}function o(t){var n=t.length,r=[1732584193,-271733879,-1732584194,271733878],i;
for(i=64;i<=t.length;i+=64){e(r,u(t.substring(i-64,i)))}t=t.substring(i-64);var s=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
for(i=0;i<t.length;i++)s[i>>2]|=t.charCodeAt(i)<<(i%4<<3);s[i>>2]|=128<<(i%4<<3);if(i>55){e(r,s);for(i=0;i<16;i++)s[i]=0}s[14]=n*8;e(r,s);return r}
function u(e){var t=[],n;for(n=0;n<64;n+=4){t[n>>2]=e.charCodeAt(n)+(e.charCodeAt(n+1)<<8)+(e.charCodeAt(n+2)<<16)+(e.charCodeAt(n+3)<<24)}return t}
function c(e){var t="",n=0;for(;n<4;n++)t+=a[e>>n*8+4&15]+a[e>>n*8&15];return t}
function h(e){for(var t=0;t<e.length;t++)e[t]=c(e[t]);return e.join("")}
function d(e){return h(o(unescape(encodeURIComponent(e))))}
function m(e,t){return e+t&4294967295}var a="0123456789abcdef".split("");return d})();