// Creación de una instancia a partir del la función getHTTPObject()
var http = getHTTPObject();

var isWorking = false;

// Funcion de parseo del XML y presentación de los datos
function cargar_cuenta(url,codigo,dest,dest2) {
  var http = getHTTPObject();
  if (!isWorking && http) {
	 
    http.open("GET", url + escape(codigo.value), true);
    http.onreadystatechange = function () {
					if (http.readyState == 4)
					{
						if (http.responseText.indexOf('invalid') == -1)
						{
						  var xmlDocument = http.responseXML;
						  var dato = xmlDocument.getElementsByTagName('descripcion').item(0).firstChild.data;
						  var dato2 = xmlDocument.getElementsByTagName('codigo').item(0).firstChild.data;
						  dest.value = dato;
						  dest2.value = dato2;
						  /* Consulta si el codigo NO ha sido encontrado */
						  if (dato2 == 0)
						  {
						  	dest.style.color="#FF0000";
						  }
						  else
						  {
							dest.style.color="#000000";  
						  }
						  isWorking = false;
						}
					} }
    isWorking = true;
    http.send(null);
  }
}

function buscar_xml(url,codigo,codigo2,dest) {
  var http = getHTTPObject();
  var isWorking = false;
  var contenido = document.getElementById(dest);
  var codigo = document.getElementById(codigo);
  var codigo2 = document.getElementById(codigo2);
  var tipo='';
  
  if(codigo2.checked)
  	{ tipo='d'; } else { tipo='c'; }

  if (!isWorking && http) {
    http.open("GET", url + escape(codigo.value) + '&op_opciones=' + escape(tipo), true);
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