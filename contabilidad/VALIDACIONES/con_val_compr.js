// JavaScript Document
/* Validacion indivual*/
function validar_cuentas(form, campo)
{
	if (parametro_x(campo, '.') != false)
		{
			form.submit;
		}
}

/* Validacion de la modificacion de comprobantes */ 
function validar_inscomp(form, hoy)
{
	var flag=0;
	var ndatos=0;
	var bad_cta=0;
	
	for (i=0;i<form.elements.length;i++)
	{		
		var dato=form.elements[i];
		if (((dato.type=="text") || (dato.type=="textarea") || (dato.type=="select-one")) && (dato.value=="") && (dato.id!="buscta") && (dato.id!="Com_Obs") && (dato.id.substring(dato.id.length-2,dato.id.length-1)!="6"))
		{
			flag=1;					
			alert("Falta llenar Informacion necesaria.");
			dato.focus();
			break;
		}
				
		if ((dato.id.substring(0,5)=="datos") && (dato.id.substring(dato.id.length-2,dato.id.length-1)=="1"))
		{
			ndatos++;
			if (dato.value=="0") { bad_cta++; }
		}
	}
	
	if (flag==0)
	{
		if (ndatos < 2)
		{
			alert("Debe tener por lo menos dos asientos");
		}
		else
		{
			if (bad_cta > 0)
			{
				alert("Ingrese cuentas existentes en el Plan de Cuenta");
			}
			else
			{
				if (parseFloat(document.getElementById('t_debe').value)!=parseFloat(document.getElementById('t_haber').value))
					{
						alert("Los totales del DEBE y el HABER deben coincidir");
					}
					else
					{	
						if (parseFloat(document.getElementById('Com_Val').value) > parseFloat(document.getElementById('t_debe').value))
							{
								alert("El VALOR del Comprobante debe ser menor o igual al Total del DEBE o HABER");
							}
							else
							{
								if(rango_fechas(document.getElementById('Pec_Fei').value, document.getElementById('Pec_Fef').value
										, document.getElementById('Com_Fec').value) != false)
								{
									confirmacion(form);
								}
							}
					}
			}
		}
	}
}

/* *********************************** CREACION DE COMPROBANTES DE INGRESO ************************************************ */
function nueva_fila(contenido,tipocuenta,pagina, Pld_Cod, Pld_Cdc, Pld_Des)
{
	var columna = document.createElement("td");
	var columna2 = document.createElement("td");
	var columna3 = document.createElement("td");
	var columna4 = document.createElement("td");
	var columna5 = document.createElement("td");
	var columna6 = document.createElement("td");
	var columna7 = document.createElement("td");	
	var fila = document.createElement("tr");

	var cuerpo = document.getElementById (contenido);
	
	// Alineacion de columnas (DEBE - HABER)
	columna3.setAttribute('align','left');
	columna4.setAttribute('align','right');
	columna5.setAttribute('align','right');	
	columna6.setAttribute('align','center');
	
	// Aumentar en uno la cantidad de filas
	cont_filas('nfilas');
	
	// Lectura del nuevo valor
	var total = document.getElementById('nfilas');
	
	// Cuadro oculto que guarda el codigo de la cuenta de la transacción
	var cuadroh=create_input('datos['+ total.value +','+ 1 +']',10,10,'hidden',false,'left');
	columna.appendChild(cuadroh);
	cuadroh.value = Pld_Cod;

	// Cuadro que muestra el codigo de la busqueda
	var cuadro=create_input('datos['+ total.value +','+ 2 +']',7,50,'text',false,'left');
	// Cuadro que muestra la descripcion de la cuenta en base a la busqueda hecha con el codigo
	var cuadro2=create_input('datos['+ total.value +','+ 3 +']',20,100,'text',true,'left');
	
	// Cuadro que guardar la glosa del asiento
	var cuadro3=create_input('datos['+ total.value +','+ 6 +']',23,100,'text',false,'left');
	
	// Asignacion del evento al cuadro de busqueda
	cuadro.onkeyup = function() {cargar_cuenta(pagina + '&codigo=',this,cuadro2,cuadroh)};
	cuadro.value = Pld_Cdc;
	// Asignacion del evento al cuadro de la descripción para que no pueda ser editado (no repercute en nada)
	//cuadro2.onkeyup = function() {cargar_cuenta(pagina + '&codigo=',cuadro,cuadro2,cuadroh)};
	cuadro2.value = Pld_Des;
	
	columna2.appendChild(cuadro);
	columna3.appendChild(cuadro2);
	columna7.appendChild(cuadro3);
	
	// Cuadros que contienen los valores del DEBE o del HABER
	if (tipocuenta=='debe')
		{
			var c_debe=create_input('datos['+ total.value +','+ 4 +']',7,10,'text',false,'right');
			c_debe.onkeyup= function() {sumar_totales()};
			c_debe.onblur= function() {numerico(this)};
			columna4.appendChild(c_debe);
		}else{
			var c_haber=create_input('datos['+ total.value +','+ 5 +']',7,10,'text',false,'right');
			c_haber.onkeyup= function() {sumar_totales()};
			c_haber.onblur= function() {numerico(this)};
			columna5.appendChild(c_haber);
		}
	
	// Creación del botón de eliminación
	columna6.appendChild(create_button('quitar_fila','X',quitar_fila));

	fila.appendChild(columna);
	fila.appendChild(columna2);
	fila.appendChild(columna3);
	fila.appendChild(columna7);
	fila.appendChild(columna4);
	fila.appendChild(columna5);
	fila.appendChild(columna6);
	
	cuerpo.appendChild(fila);
	
//	sumar_totales();
}

function cont_filas(contador)
{
	var total = document.getElementById(contador);
	var totaln = (document.getElementById(contador).value -1)+ 2;
	total.value = totaln;
}

function quitar_fila()
{
	var padre = this.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	
	sumar_totales();
}

function quitar_fila_st(fila)
{
	var padre = fila.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	
	sumar_totales();
}

function sumar_totales()
{
	var filas = document.getElementById ('nfilas'),debe=0,haber=0;	
	for (var j = 4; j <= 5; j++)
	{
		var cantidad=0;
		var valor = 0;
		for (var i = 1; i <= filas.value; i++)
		{
			dato = document.getElementById ('datos['+ i +','+ j +']');
			if ((dato) && (dato.value.length > 0))
				{
					cantidad = cantidad + parseFloat(dato.value);
				}
		}
		if (isNaN(cantidad)) { cantidad=0; }
		if (j==4) { document.getElementById('t_debe').value=redondear(cantidad,2); debe=debe+cantidad;}
		if (j==5) {	document.getElementById('t_haber').value=redondear(cantidad,2); haber=haber+cantidad;}
	}
	document.getElementById('t_diff').value=redondear(Math.abs(debe-haber),2);
	if(debe===haber) document.getElementById('Com_Val').value=redondear(Math.abs(debe),2);
}

function asignar_fechas(valor)
{
	arreglo = valor.split("*");
	document.getElementById('Pec_Fei').value = arreglo[1];
	document.getElementById('Pec_Fef').value = arreglo[2];
}

/**
* Captura el Asi_Cod(Codigo del asiento) para ser eliminado
*/
function elimin_asi(Asi_Cod) {
	//alert(Asi_Cod);
	document.getElementById('oculto').value = document.getElementById('oculto').value	+ '*' + Asi_Cod;
}

/**
 * Marca o desmarca los checkbox segun el chktodos
 * @param chkTodos checkbox actual
 * @param form formulario donde estan los arrays de checkbox
 */
function Marcar(chkTodos,form)
{
	for(var i = 0; i < form.elements.length; i++)
	{
		if(form.elements[i].type=='checkbox' && form.elements[i].name != "chk_diario")
			form.elements[i].checked = chkTodos.checked;
	}
}