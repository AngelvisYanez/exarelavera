/**
* @fileoverview Libreria con funciones de validaciones
*
* @author Lewis Chimarro
* @version 0.1
*/
/**
* Validar el guardado de cheques
*/
function validar_cheques(form,asicod,asival)
{
	var j=0;
	var total=0;
	var error=0;
	var ndatos=0;
	for (j=0;j<=asicod.length-1;j++)
		{	
			var total=0;
			for (var i=0;i<form.elements.length;i++)
				{
					var dato=form.elements[i];
					
					if ((dato.id.substring(0,5)=="datos") && (dato.id.substring(dato.id.length-2,dato.id.length-1)=="1"))
					{
						ndatos++;											
					}
					
					if (((dato.type=="text") || (dato.type=="hidden")) && ((dato.value=="") || (dato.value==0)) && (dato.id!="buscta")  && (dato.id!="nfilas")	&& (dato.id!="cmb_mes")
						&& (dato.id.substring(dato.id.length-2,dato.id.length-1)!="6") && (dato.id.substring(dato.id.length-2,dato.id.length-1)!="7") && (ndatos > 0))
					{
						error=1;
 						if (dato.type=="hidden") { mensaje="Escoja el Banco que le corresponde a cada cheque"; } else { mensaje=''; dato.focus(); }
						alert("Falta llenar Informacion necesaria...\n" + mensaje);
						mensaje='';
						return;
					}
					/*if ((dato.tagName=="INPUT") && (dato.id.substring(0,5)=="datos"))
					{
							alert(dato.id.substring(dato.id.length-2,dato.id.length-1));
					}*/
					if ((dato.tagName=="INPUT") && (dato.id.substring(0,5)=="datos") && ((dato.id.substring(dato.id.length-2,dato.id.length-1)=="2") /*|| (dato.id.substring(dato.id.length-2,dato.id.length-1)=="2")*/) && dato.value==asicod[j])
					{
						//alert(dato.id.substring(dato.id.length-2,dato.id.length-1));
						var valorch=document.getElementById(dato.id.substring(0,dato.id.search(',')) + ',5]');
						var comboban=document.getElementById(dato.id.substring(0,dato.id.search(',')) + ',10]');
						var indice = comboban.selectedIndex;
						total=total + parseFloat(valorch.value);
					}
				}
			if (redondear(total,2) != redondear(asival[j],2) && (ndatos > 0))
				{
				alert("El valor de los cheques de la cuenta: " + comboban.options[indice].text.toUpperCase() + ", es mayor o menor al total de su respectivo asiento contable.\nEl total máximo es: $" + redondear(asival[j],2) + "\nValor Total de los cheques: $" + redondear(total,2));
				error=1;
				}
			total=0;
		}

	if (ndatos < 1)
	{
		alert("Ingrese por lo menos un cheque...");
		error=1;
	}
	if (error!=1)
	{	
		confirmacion(form);
	}	
}

/**
* Agregar fila de Cheques 
*/
function nueva_fila_cheque(contenido,cod_prv,nom_prv,cmb_cod,cmb_des,unicoprv,com_fec,asi_val,varios_prov)
{
	/**
	* Control para emitir varios cheques a un proveedor 
	*/
	variosprv=varios_prov.split('*');
	cheques = false;
	for (i=1; i<=variosprv.length-1;i++)
	{
		if (unicoprv ==variosprv[i])
		{
			cheques = true;
			break;
		}
	}

	if ((cod_prv==unicoprv) || cheques == true)
	{
		//var columna = document.createElement("td"); // Oculta
		var columna2 = document.createElement("td"); // Banco
		var columna3 = document.createElement("td"); // Proveedor
		var columna4 = document.createElement("td"); // Nº Cheque
		var columna8 = document.createElement("td"); // Valor del Cheque
		var columna9 = document.createElement("td"); // Fecha Elab
		var columna5 = document.createElement("td"); // Fecha Cob
		var columna6 = document.createElement("td"); // Observacion
		var columna7 = document.createElement("td"); // Boton
		
		var fila = document.createElement("tr");
	
		var cuerpo = document.getElementById (contenido);
		
		/**
		* Aumentar en uno la cantidad de filas
		*/
		cont_filas('nfilas');
		
		/**
		* Lectura del nuevo valor
		*/
		var total = document.getElementById('nfilas');
		/**
		* Cuadro oculto que guarda el codigo del banco		
		*/
		var cod_ban=create_input('datos['+ total.value +','+ 1 +']', 10, 10,'hidden',false,'left');
		var cod_asi=create_input('datos['+ total.value +','+ 2 +']', 10, 10,'hidden',false,'left');
		columna2.appendChild(cod_ban);
		columna2.appendChild(cod_asi);	
		/**
		* Creación del Combo que va a almacenar los bancos
		*/
		var combo=create_combo('datos['+ total.value +','+ 10 +']',cmb_cod,cmb_des,'left');
		combo.onchange= function () { cargar_codban(this,cod_ban,cod_asi); 
										if (cheques == false)	
										{
											seleccionar_valor(this,asi_val,v_cheque);	
										}
											secuencia_cheque(total,n_cheque,this, 'datos'); };
		columna2.appendChild(combo);
		/**
		* Cuadro oculto que guarda el codigo del proveedor
		*/
		var codigo_prv=create_input('datos['+ total.value +','+ 3 +']', 10, 10,'hidden',false,'left');
		codigo_prv.value=cod_prv;
		/**
		* Cuadro que muestra el nombre del proveedor
		*/
		var nombre_prv=create_input('nombre_prv', 30, 30,'text',true,'left');
		nombre_prv.value=nom_prv;
		
		columna3.appendChild(codigo_prv);
		columna3.appendChild(nombre_prv);
		/**
		* Cuadro que muestra el codigo del cheque
		*/
		var n_cheque=create_input('datos['+ total.value +','+ 4 +']', 10, 10,'text',false,'left');
		n_cheque.onblur= function() {numerico(this)};
		columna4.style.textAlign = "right";
		columna4.appendChild(n_cheque);	
		/**
		* Cuadro que muestra el valor del cheque
		*/
		var v_cheque=create_input('datos['+ total.value +','+ 5 +']', 6, 10,'text',false,'right');
		v_cheque.onblur= function() {numerico(this)};
		v_cheque.onkeyup= function() {cal_total_cheque(5)};
		columna8.style.textAlign = "right";
		columna8.appendChild(v_cheque);
		/**
		* Cuadro de texto que guarda la fecha de elaboracion
		*/
		var fecha_elab=create_input('datos['+ total.value +','+ 8 +']', 7, 10,'text',false,'left');
		fecha_elab.onblur= function() {validar_fecha2(this)};
		fecha_elab.onKeyUp= function() {mascara(this,'-',patron,true);}
		fecha_elab.value = com_fec;
		columna9.appendChild(fecha_elab);
		/**
		* Cuadro de texto que guarda la fecha de cobro
		*/
		var fecha_cob=create_input('datos['+ total.value +','+ 6 +']', 7, 10,'hidden',false,'left');
		fecha_cob.onblur= function() {validar_fecha2(this)};
		fecha_cob.onKeyUp= function() {mascara(this,'-',patron,true);}

		columna5.appendChild(fecha_cob);
		$(columna5).hide();
		/**
		* Cuadro que guarda alguna observación del cheque
		*/
		var observacion=create_input('datos['+ total.value +','+ 7 +']', 7, 7,'text',false,'left');
		/**
		* Cuadro oculto que almacena el numero o cantidad de cheque 
		*/
		var che_cod=create_input('datos['+ total.value +','+ 9 +']', 10, 10,'hidden',false,'left');	

		columna6.appendChild(observacion);
		columna6.appendChild(che_cod);
		che_cod.value = total.value
		
		columna7.appendChild(create_button_elim('quitar_fila','Eliminar',quitar_fila_che));
		columna7.setAttribute('align','center');
		
		//fila.appendChild(columna);
		fila.appendChild(columna2);
		fila.appendChild(columna3);
		fila.appendChild(columna4);
		fila.appendChild(columna8);
		fila.appendChild(columna9);
		fila.appendChild(columna5);
		fila.appendChild(columna6);
		fila.appendChild(columna7);
	
		cuerpo.appendChild(fila);		

		/**
		* Control para que se seleccione el banco siempre y cuando exista 1 
		*/
		if (combo.length==2)
		{
			seleccionar_valor_uno(combo,cod_ban,cod_asi,asi_val,v_cheque,total,n_cheque,cheques);
		}
	} else { alert("Los Cheques deben ser emitidos unicamente al Proveedor titular en el Comprobante"); }
}

function cont_filas(contador)
{
	var total = document.getElementById(contador);
	var totaln = (document.getElementById(contador).value -1)+ 2;
	total.value = totaln;
}

function cargar_codban(combo,cod_ban,codasi)
{
	var nvalor = combo.value.split("*");
	cod_ban.value=nvalor[0];
	codasi.value=nvalor[1];
}

/**
* Funcion que se encarga de seleccionar el banco y pasar el valor al cuadro de texto 
*/
function seleccionar_valor(combo,asi_val,v_cheque)
{
	if (combo.selectedIndex > 0)
	{
		v_cheque.value= asi_val[combo.selectedIndex-1];		
	}
	else
	{
		v_cheque.value="";
	}
	cal_total_cheque(5);
}

/**
* Funcion que se encarga de seleccionar el banco automaticamente cuando existe uno 
*/
function seleccionar_valor_uno(combo,cod_ban,codasi,asi_val,v_cheque,total,n_cheque,cheques)
{
	/**
	* Selecciona el banco 
	*/
	combo.selectedIndex = 1;
	/**
	* Parametros del banco 
	*/
	cargar_codban(combo,cod_ban,codasi)	
	if (cheques == false)
	{
		/**
		* Valor del cheque 
		*/
		v_cheque.value= asi_val[0];	
	}
	/**
	* Secuencia de los cheques 
	*/
	secuencia_cheque(total,n_cheque,combo, 'datos');
	/**
	* Calculo del cheque 
	*/
	cal_total_cheque(5);	
}

/**
* Funcion que incrementa automaticamente los numeros de cheque, en base a un valor inicial 
*/
function secuencia_cheque(fila,n_cheque,combo, datos)
{
	if (combo.selectedIndex > 0)
	{
		for (var j = fila.value; j >= 1; j--)
		{
			dato = document.getElementById (datos+'['+ j +','+ 4 +']');//Aqui estaba la columna 6
			if ((dato) && (dato.value.length > 0))
			{
				n_cheque.value = parseFloat(dato.value) + 1;
				break;
			}	
		}
	}
	else
	{
		n_cheque.value="";
	}
}//FIn del secuencia_cheque(fila,n_cheque,combo, datos)

function quitar_fila_che()
{
	var padre = this.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	cal_total_cheques(5, 'nfilas_ch', 'datos_ch');
}

/**
* Calculo del subtotal
*/
function cal_total_cheque(colum)
{
	var filas = document.getElementById ('nfilas');	
	var sub_total=0;
	for (var j = 1; j <= filas.value; j++)
	{
		dato = document.getElementById ('datos['+ j +','+ colum +']');

			if ((dato) && (dato.value.length > 0))
				{
					sub_total = sub_total + parseFloat(dato.value);
				}	
	}
	if (isNaN(sub_total)) 
		{ sub_total=0; } 		
	document.getElementById('txt_total').value=redondear(sub_total,2);
}//Fin del cal_total_cheque(colum)

/**
* Calculo del subtotal en base una columna fija, cantidad de filas y nombre de la matriz 
*/
function cal_total_cheques(colum, filas, datos)
{
	var filas = document.getElementById (filas);	
	var sub_total=0;
	for (var j = 1; j <= filas.value; j++)
	{
		dato = document.getElementById (datos+'['+ j +','+ colum +']');
		//alert(dato.value);
			if ((dato) && (dato.value.length > 0))
				{
					sub_total = sub_total + parseFloat(dato.value);
				}	
	}
	if (isNaN(sub_total)) 
		{ sub_total=0; } 		
	document.getElementById('txt_total').value=redondear(sub_total,2);
}//Fin del cal_total_cheques(colum, filas, datos)

function quitar_fila_st(fila)
{
	var padre = fila.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	cal_total_cheque(5);
}