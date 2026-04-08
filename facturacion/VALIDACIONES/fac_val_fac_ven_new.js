// JavaScript Document
// validad campos de facturacion 
function validar_facturacion(form)
{
	var entrada1 = true;
	var entrada2 = true; 
	var entrada3 = false;/* Inicia en falso por que no hay ningun rubro */
	var entrada4 = true;
	var filas = document.getElementById ('nfilas');	
	var banco1 = true;
	var banco2 = true;
	
	/* 
	* entrada1 = cabecera de la factura
	* entrada2 = rubros de la factura - cantidad y precio unitario 
	* entrada3 = rubros de la factura 
	*/

	/* 
	* Verifica que no este activado OTROS BANCOS 
	*/	
//	if (document.getElementById('chk_bancos').checked != true)
//	{
		if (requerido_null(form.Ban_Cod) === false)
		{
			banco1 = false;	
		}//FIn del if (requerido_null(form.Ban_Cod) == false)		
//	}//Fin del if (document.getElementById('chk_bancos').checked != true)*/

	/* 
	* Verifica que este activado el tipo de pago 2 
	*/
	if (document.getElementById('detalle').checked === true)
	{
		/* 
		* Verifica que no este activado OTROS BANCOS 2 
		*/	
//		if (document.getElementById('chk_bancos2').checked != true)
//		{
			if (banco1 === true)
			{
				if (requerido_null(form.Ban_Cod2) === false)
				{
					banco2 = false;	
				}//FIn del if (requerido_null(form.Ban_Cod) == false)		
			}//Fin del if (banco1 == true)
//		}//Fin del if (document.getElementById('chk_bancos').checked != true)
	}//Fin del if (document.getElementById('detalle').checked ==true)

	if (banco1 == true && banco2 == true)
	{
		/* 
		* Verifica que tenga datos el numero de la factura y el total de la cabecera de la factura 
		*/
		if (requerido(form.Vet_Num)!=false && requerido(form.Vet_Tot)!=false && requerido(form.Vet_Che)!=false)
		{
			/* 
			* Verifica que este activado el tipo de pago 2 
			*/
			if (document.getElementById('detalle').checked ==true)
			{
				/* 
				* Valor para el tipo de pago 2 
				*/
				if (requerido(form.Vet_Tot2) == false  && requerido(form.Vet_Che2)!=false)/* requerido_null(form.Ban_Cod) != false && */
				{
					entrada1 = false;
				}
			}//Fin del if (document.getElementById('detalle').checked ==true)			

			/* 
			* Recorre los rubros de la factura 
			*/
			for (var j = 1; j <= filas.value; j++)
			{
				entrada3=true;
				dato = document.getElementById ('datos['+ j +','+ 3 +']'); /* Cantidad */
				dato_imp = document.getElementById ('datos['+ j +','+ 5 +']');/* Precio unitario */
				dato_uni = document.getElementById ('datos['+ j +','+ 15 +']');/* Unidad de medida */				
				/**
				* Control para validar la cantidad 
				*/
				if ((dato) && (trim(dato.value) =="" || parseFloat(dato.value)==0))
				{ 
					entrada2 = false;
					campo = dato;					
					break; 
				}//Fin del if ((dato) && (trim(dato.value) =="" || parseFloat(dato.value)==0))
				
				/* 
				* Control para validar el precio unitario 
				*/
				if ((dato_imp) && (trim(dato_imp.value) =="" || parseFloat(dato_imp.value)==0))
				{ 
					entrada2 = false;
					campo = dato_imp;
					break; 
				}//Fin del if ((dato_imp) && (trim(dato_imp.value) =="" || parseFloat(dato_imp.value)==0))	

				/* 
				* Control para validar la unidad de medidad
				*/
				if ((dato_uni) && (trim(dato_uni.value) =="" || parseFloat(dato_uni.value)==0))
				{ 
					entrada2 = false;
					campo = dato_uni;
					break; 
				}//Fin del if ((dato_uni) && (trim(dato_uni.value) =="" || parseFloat(dato_uni.value)==0))	
							
			}//Fin del for (var j = 1; j <= filas.value; j++)

			if (entrada1==true)/* Cabecera de la factura */
		    {
				if (entrada3==true)/* Cantidad ded rubros registrados */
				{
					if (entrada2==true) /* Datos en la cantidad y precio unitario */
					{
						valor1 = document.getElementById ('Vet_Tot'); /* Total del tipo de pago 1 */
						valor2 = document.getElementById ('Vet_Tot2'); /* Total del tipo de pago 2 */		
						total = document.getElementById ('Val_Pcc'); /* Total del tipo de pago 2 */		

						/* Control para la forma de pago 2 */
						if (isNaN(parseFloat(valor2.value)))
						{
							valor2.value = 0;	
						}//FIn del if (isNaN(parseFloat(valor2.value)))				

						var total_valor = parseFloat(valor1.value) + parseFloat(valor2.value);	
				
						if (redondear(total_valor,2) == redondear(parseFloat(total.value),2))
						{
							confirmacion(form);				 	
						}//FIn del if (redondear(total_valor,2) == redondear(parseFloat(total.value),2))
						else
						{
							alert("El TOTAL de la factura debe ser igual al(los) valor(es) del TIPO DE PAGO.");
							valor1.select();
						}//FIn del else if (redondear(total_valor,2) == redondear(parseFloat(total.value),2))
					}//Fin del if (entrada2==true)
					else
					{
						alert("Falta llenar Informacion necesaria.");
						campo.focus();
					}//Fin del else if (entrada2==true)
				}//FIn del if (entrada2==true)
				else
				{
					alert("Debe ingresar al menos un rubro.");
				}//Fin del else if (entrada2==true)
	 		}//FIn del if (entrada1==true)
		}//Fin del if (requerido(form.Vet_Num) != false && requerido(form.Vet_Tot) != false)
	}//Fin del if (banco1 == true)
}//Fin del validar_facturacion(form)

/* 
* Funcion que blanquea el detalle del tipo de pago 
*/
function blanquear_pago()
{
	if (document.getElementById('detalle').checked == false /*|| document.getElementById('chk_bancos').checked == false*/)
	{
		/* Seleciona la primera opcion de combo */
		//document.getElementById('Bak_Cod').selectedIndex=0;
		document.getElementById('Ban_Cod').selectedIndex=0;
	}
}

/* 
* Funcion que blanquea el detalle del tipo de pago 
*/
function blanquear_pago2()
{
	if (document.getElementById('detalle').checked == false /*|| document.getElementById('chk_bancos2').checked == false*/)
	{
		document.getElementById('Vet_Cue2').value = "";
		document.getElementById('Vet_Che2').value = "";

		/* Seleciona la primera opcion de combo */
		//document.getElementById('Bak_Cod2').selectedIndex=0;
		document.getElementById('Ban_Cod2').selectedIndex=0;
		/* Limpia el cuadro de texto del valor 2 de la segunda formade pago */
		document.getElementById('Vet_Tot2').value="";
	}
}

/* 
* Permite mostrar y ocultar el constenido de botones inferiores de la factura 
*/
function botones_fac(boton)
{
	switch(boton){
     case 1 : /* Boton deudas */
        document.getElementById('deudas_table').className = "muestra";
        document.getElementById('rubros_table').className = "oculta";
        break;
     case 2 : /* Boton buscar */
        document.getElementById('deudas_table').className = "oculta";
        document.getElementById('rubros_table').className = "muestra";
        break;
     //case 3 : /* Boton item en blanco */
       // document.getElementById('deudas_table').className = "oculta";
        //document.getElementById('rubros_table').className = "oculta";
        //break;
  }
}

/* 
* CREACION DE FACTURACION 
*/
function nueva_fila(contenido,codigoide,codigo,descripcion,precio,iva,codigoiva,codigonota,codigorec,pagina,codigoasi,ccxcc,botonelim, cantrecur)
{	
	//if (verificar_prod_deuda(codigo, codigonota, codigoasi, codigorec)== true){
		//var columna = document.createElement("td"); //Codigo del producto
		var columna2 = document.createElement("td");//Codigo del producto manual
		var columna3 = document.createElement("td");//Cantidad
		var columna4 = document.createElement("td");//Descripcion
		var columna5 = document.createElement("td");//Precio Unitario
		var columna6 = document.createElement("td");//Importe
		var columna7 = document.createElement("td");//Descuento
		var columna8 = document.createElement("td");//Iva
		var columna9 = document.createElement("td");//Boton Eliminar
		var columna10 = document.createElement("td");//Codigo del Iva		

		var fila = document.createElement("tr");

		var cuerpo = document.getElementById (contenido);
	
		/* 
		* Alineacion de columnas - cabecera 
		*/
		columna2.setAttribute('align','center');		
		columna3.setAttribute('align','center');
		columna4.setAttribute('align','left');
		columna5.setAttribute('align','center');
		columna6.setAttribute('align','center');
		columna7.setAttribute('align','center');
		columna8.setAttribute('align','center');
		columna9.setAttribute('align','center');
		columna10.setAttribute('align','center');
		
		/*
		* Aumentar en uno la cantidad de filas
		*/
		cont_filas('nfilas');
	
		/*
		* Lectura del nuevo valor
		*/
		var total = document.getElementById('nfilas');

		/* 
		* Control saber si bloque los cuadros de texto 
		*/
		if (ccxcc == 'si')
		{
			solo_lect = true;
		}
		else
		{
			solo_lect = false;			
		}
		
		/* 
		* Control para bloquear el precio unitario 
		*/
		if (cantrecur == -1)
		{
			solo_lect_pre = true;
		}
		else
		{
			solo_lect_pre = false;			
		}
		
		/*
		* Cuadro oculto que guarda el codigo del producto
		*/
		var Cod_Pro=create_input('datos['+ total.value +','+ 1 +']', 20, 20,'hidden',false,'left');
		columna2.appendChild(Cod_Pro);//antes columna
		Cod_Pro.value = codigo;

		/*
		* Cuadro que muestra codigo manuel del producto
		*/
		var Pro_Ide=create_input('datos['+ total.value +','+ 2 +']', 2, 2,'hidden',solo_lect,'left')
		Pro_Ide.value = codigoide;
		columna2.appendChild(Pro_Ide);

		/*
		* Cuadro que muestra cantidad del producto
		*/
		var Vet_Can=create_input('datos['+ total.value +','+ 3 +']', 4, 4,'text',solo_lect,'right')
		Vet_Can.onblur= function() {numerico(this)};	
		columna3.appendChild(Vet_Can);
	
		/*
		* Cuadro que muestra la descripcion del producto en base a la busqueda hecha con el codigo
		*/
		var Ite_Lar=create_input('datos['+ total.value +','+ 4 +']', 30, 30,'text',true,'left');
		Ite_Lar.value = descripcion;
		columna4.appendChild(Ite_Lar);

		/*
		* Cuadro que muestra el precio unitario
		*/
		var Vet_Pru=create_input('datos['+ total.value +','+ 5 +']', 15,8,'text',solo_lect_pre,'right');
		Vet_Pru.value = precio;
		Vet_Pru.onblur= function() {numerico(this); cien(this, precio); positivo(this); };
		columna5.appendChild(Vet_Pru);
			
		var Vet_Imp=create_input('datos['+ total.value +','+ 6 +']', 15, 8,'text',true,'right');
		Vet_Imp.onblur= function() {numerico(this)};
		columna6.appendChild(Vet_Imp);
			
		if (document.getElementById('activar1').checked == true)
		{ bool = true;	}
		else { bool = false }
	
		/* 
		* Cuadro que muestra el porcentaje de descuento invidividual
		*/
		var Vet_Dec=create_input('datos['+ total.value +','+ 7 +']',2,2,'text',bool,'right');		
		Vet_Dec.onblur= function() {numerico(this)};
		columna7.appendChild(Vet_Dec);
		
		/*
		* Cuadro que muestra el porcentaje del Iva
		*/
		var Vet_Iva=create_input('datos['+ total.value +','+ 8 +']',2,2,'text',true,'right');		
		Vet_Iva.value = iva;
		columna8.appendChild(Vet_Iva);
	
		/*
		* Cuadro que muestra el codigo del iva
		*/
		var Iva_Cod=create_input('datos['+ total.value +','+ 9 +']', 10, 10,'hidden',false,'left');
		columna10.appendChild(Iva_Cod);
		Iva_Cod.value = codigoiva;
		
		/*
		* Codigo de la notageneral
		*/
		var Nge_Cod=create_input('datos['+ total.value +','+ 10 +']', 20, 20,'hidden',false,'left');
		columna10.appendChild(Nge_Cod);
		Nge_Cod.value = codigonota;

		var Deu_Rec=create_input('datos['+ total.value +','+ 11 +']', 20, 20,'hidden',false,'left');
		columna10.appendChild(Deu_Rec);
		Deu_Rec.value = codigorec;
		
		var Asi_Int=create_input('datos['+ total.value +','+ 12 +']', 5, 5,'hidden',false,'left');
		columna10.appendChild(Asi_Int);	
		Asi_Int.value = codigoasi;
		
		/*
		*  Asignacion del evento al cuadro de cantidad
		*/
		Vet_Can.onkeyup = function() {cal_importe(this, Vet_Pru, Vet_Imp); asignar_total_fac()};
	
		/*
		* Asignacion del evento al cuadro de precio unitario
		*/
		Vet_Pru.onkeyup = function() {cal_importe(this, Vet_Can, Vet_Imp); asignar_total_fac()}; 

		/*
		* Asignacion del evento al cuadro de descuento
		*/
		Vet_Dec.onkeyup = function() {cal_importe(Vet_Pru, Vet_Can, Vet_Imp); asignar_total_fac()}; 

		if (ccxcc == 'no')
		{
			/*
			* Asignacion del evento al cuadro de busqueda
			*/
			Pro_Ide.onkeyup = function() {ajax_xml_factura(pagina + '?codigopro=' + escape(this.value) + '&codigonota=' 
							+ codigonota + '&codigo=' + codigo, this);};		
		}//Fin del if (ccxcc == 'no')

		if (botonelim == 'si')
		{
			/*
			* Creación del botón de eliminación
			*/
			columna9.appendChild(create_button('quitar_fila','X',function(){ quitar_fila(this,cantrecur); asignar_total_fac()}));
			
			/* 
			* Si es mayor a cero significa que tiene recursividad 
			*/
		}
		
		//fila.appendChild(columna);
		fila.appendChild(columna2);
		fila.appendChild(columna3);
		fila.appendChild(columna4);
		fila.appendChild(columna5);
		fila.appendChild(columna6);
		fila.appendChild(columna7);
		fila.appendChild(columna8);
		fila.appendChild(columna9);
		fila.appendChild(columna10);		
	
		cuerpo.appendChild(fila);
		/* 
		* Control saber si bloque los cuadros de texto 
		*/
		//if (ccxcc == 'si')
		//{
			Vet_Can.value = 1;
			/* Cargado cuando esta bloqueado el campo de cantidad */
			cal_importe(Vet_Pru, Vet_Can, Vet_Imp);
		//}
		
	//}
	//else
	//{
		//alert ("El item ya se encuentra ingresado!");	
	//}
}//Fin del function nueva_fila

/* 
* Toma el total de la factura y lo asigna al valor1 de la cabecera de la factura 
*/
function asignar_total_fac()
{
	total_cab = document.getElementById ('Vet_Tot');
	total_fac = document.getElementById ('t_rubros');
	if (total_fac.value != 0)
	{
		total_cab.value = total_fac.value;
	}
	else
	{
		total_cab.value = "";	
	}
}

/*
* Calculo del subtotal
*/
function cal_sub_total(colum)
{
	var filas = document.getElementById ('nfilas');	
	var sub_total=0;
	for (var j = 1; j <= filas.value; j++)
	{
		dato = document.getElementById ('datos['+ j +','+ colum +']');//Aqui estaba la columna 6

			if ((dato) && (dato.value.length > 0))
				{
					sub_total = sub_total + parseFloat(dato.value);
				}	
	}
	if (isNaN(sub_total)) 
		{ sub_total=0; } 		
	document.getElementById('t_subtotal').value=redondear(sub_total,4);
}

/* 
* Calculo del importe - FUNCION PRINCIPAL
*/
function cal_importe(n1,n2,total,unidad)
{
	total.value = redondear((parseFloat(n1.value)* parseFloat(unidad.value))*parseFloat(n2.value),2);
	if (isNaN(total.value)) 
		{ total.value=0; } 		
	//Llamado del calculo del sub-total
	cal_sub_total(6);

	/*
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
			cal_des_total('Vet_Des'); 
		    } else {
			         cal_des_importe(6,7); 
		    }
	         
         cal_tarifas(8,6);//Llamado del calculo de tarifas
 		 cal_iva_importe('Vet_Des',8,7,6); //Calculo del iva	
         cal_total()//Calculo del total
}

/*
* Calculo del descuento por importe - Standar para Compra y Ventas
*/
function cal_des_importe(colum_imp, colum_des)
{
	var filas = document.getElementById ('nfilas');	
	var valor = 0;
	var total_desc = 0
	
	for (var j = 1; j <= filas.value; j++)
	{
		importe = document.getElementById ('datos['+ j +','+ colum_imp +']'); //Aqui estaba antes 6
		desc = document.getElementById ('datos['+ j +','+ colum_des +']'); //Aqui estaba antes 7
		if ((importe) && (importe.value.length > 0)){
			if (isNaN(desc.value)) { desc.value=0; } 
			valor = (importe.value * desc.value)/100;
			total_desc = total_desc + valor;
			}
	}
	document.getElementById('t_descuento').value= redondear(total_desc,2)
}

/*
* Calculo del descuento por subtotal - Standar para Compra y Ventas
*/
function cal_des_total(campo)  
{
	var total_desc = 0
	sub_total = document.getElementById ('t_subtotal');
	desc = document.getElementById (campo);//Aqui estaba antes 'Vet_Des'
	total_desc = (sub_total.value * desc.value)/100;
	document.getElementById('t_descuento').value= redondear(total_desc,2);	
}

/*
* Calculo del iva por importe - Standar para Compra y Ventas 		//Ojo debe modificarse - revisar
*/
function cal_iva_importe(campo, colum_iva, colum_des, colum_imp)
{
	var filas = document.getElementById ('nfilas');	
	var valor = 0;
	var desc = 0;
	var total_iva = 0;
	var iva_12 = 0;
	//Evalua para saber cuando debe calcular un iva total o invidual
	if (document.getElementById('activar1').checked == true){	
		des = document.getElementById (campo);//% de descuento - Aqui estaba antes 'Vet_Des'
		tarifa_12 = document.getElementById ('t_iva12');

		for (var j = 1; j <= filas.value; j++)
		{
			iva = document.getElementById ('datos['+ j +','+ colum_iva +']');//Aqui estaba antes 8
			if ((iva) && (iva.value > 0)){ //Solo entra cuando el iva sea mayor a 0
					iva_12 = iva.value;
				}
		}

			if (isNaN(des.value)) { des.value=0; } 
			
			desc = (tarifa_12.value * des.value)/100;// Descuento total de la tarifa 12	
			total_iva = ((tarifa_12.value - desc) * iva_12)/100;		

	} else {
		for (var j = 1; j <= filas.value; j++)
		{
			des = document.getElementById ('datos['+ j +','+ colum_des +']');//Porcenjate de descuento individual - Antes estaba 7
			importe = document.getElementById ('datos['+ j +','+ colum_imp +']');//Antes estaba 6
			iva = document.getElementById ('datos['+ j +','+ colum_iva +']');//Antes estaba aqui 8
			if ((iva) && (iva.value.length > 0)){
				if (isNaN(iva.value)) { iva.value=0; } 
					desc = (importe.value * des.value)/100;	
					valor = ((importe.value - desc) * iva.value)/100;
					total_iva = total_iva + valor;
				}
		}
	}
	document.getElementById('t_iva').value= redondear(total_iva,2);	
}

/*
* Calculo de la tarifa - Standar para Compra y Ventas
*/
function cal_tarifas(colum_iva, colum_val)
{
	var filas = document.getElementById ('nfilas');	
	var tarifa_0 = 0;
	var tarifa_12 = 0;
	
	for (var j = 1; j <= filas.value; j++)
	{
		iva = document.getElementById ('datos['+ j +','+ colum_iva +']'); //Aqui estaba antes 8
		valor = document.getElementById ('datos['+ j +','+ colum_val +']') //Importe - Aqui estaba antes 6
		if ((iva) && (iva.value.length > 0)){

			if (iva.value == 0){
				tarifa_0 = tarifa_0 + parseFloat(valor.value);
			} else {
				tarifa_12 = tarifa_12 + parseFloat(valor.value);
			}
		}
	}
	document.getElementById('t_iva0').value= redondear(tarifa_0,2);	
	document.getElementById('t_iva12').value= redondear(tarifa_12,2);	
}

/*
* Calculo del iva por importe - Standar para Compra y Ventas 		//Ojo debe modificarse - revisar
*/
function cal_iva_importe(campo, colum_iva, colum_des, colum_imp)
{
	var filas = document.getElementById ('nfilas');	
	var valor = 0;
	var desc = 0;
	var total_iva = 0;
	var iva_12 = 0;
	//Evalua para saber cuando debe calcular un iva total o invidual
	if (document.getElementById('activar1').checked == true){	
		des = document.getElementById (campo);//% de descuento - Aqui estaba antes 'Vet_Des'
		tarifa_12 = document.getElementById ('t_iva12');

		for (var j = 1; j <= filas.value; j++)
		{
			iva = document.getElementById ('datos['+ j +','+ colum_iva +']');//Aqui estaba antes 8
			if ((iva) && (iva.value > 0)){ //Solo entra cuando el iva sea mayor a 0
					iva_12 = iva.value;
				}
		}

			if (isNaN(des.value)) { des.value=0; } 
			
			desc = (tarifa_12.value * des.value)/100;// Descuento total de la tarifa 12	
			total_iva = ((tarifa_12.value - desc) * iva_12)/100;		

	} else {
		for (var j = 1; j <= filas.value; j++)
		{
			des = document.getElementById ('datos['+ j +','+ colum_des +']');//Porcenjate de descuento individual - Antes estaba 7
			importe = document.getElementById ('datos['+ j +','+ colum_imp +']');//Antes estaba 6
			iva = document.getElementById ('datos['+ j +','+ colum_iva +']');//Antes estaba aqui 8
			if ((iva) && (iva.value.length > 0)){
				if (isNaN(iva.value)) { iva.value=0; } 
					desc = (importe.value * des.value)/100;	
					valor = ((importe.value - desc) * iva.value)/100;
					total_iva = total_iva + valor;
				}
		}
	}
	document.getElementById('t_iva').value= redondear(total_iva,2);	
}

function cal_total()
{
	var total = 0
 
	sub_total = document.getElementById('t_subtotal');
	iva = document.getElementById('t_iva');	
	des = document.getElementById('t_descuento');
	
	total = ((parseFloat(sub_total.value) - parseFloat(des.value)) + parseFloat(iva.value)); 
	document.getElementById('t_rubros').value = redondear(total,2);		
}

/* Elimina una fila recursiva */
function quitar_fila_recur(fila)
{
	var padre = document.getElementById('datos['+ fila +','+ 1 +']').parentNode.parentNode;
	padre.parentNode.removeChild (padre);
}

function quitar_fila(boton,cantrecur)
{
	var padre = boton.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	
	/* 
	* Control de borrado de filas de recusividad 
	*/	
	//campo = boton.parentNode.childNodes(4).firstChild;
	//var texto = campo.name;
	var texto = boton.name;
	var arreglo = texto.split(']');
	var fila = arreglo[0].substring(12,texto.length-1);

	for (x=parseInt(fila)+1; x<=(parseInt(fila))+parseInt(cantrecur); x++)
	{
		dato = document.getElementById('datos['+ x +','+ 1 +']');

		if ((dato))
		{
			quitar_fila_recur(x);												
		}
	}
	
	/*
	* Calculo del subtotal
	*/
	cal_sub_total(6);
	//Evalua para saber cuando debe calcular un descuento total o general
	if (document.getElementById('activar1').checked == true){	
		cal_des_total('Vet_Des'); //Calculo del descuento total
	} else {
		cal_des_importe(); //Calculo del descuento individual
	}
	cal_tarifas(8,6); //Calculo de las tarifas
	cal_iva_importe('Vet_Des',8,7,6);//Calculo del iva importe
	cal_total()//Calculo del total	
}

/*
* Funcion que permite verificar que un producto no se ingrese nuevamente en el detalle
*/
function verificar_prod_deuda(prod, nota, asig, rec)
{
	var retorno = true;
    var total = document.getElementById('nfilas');
	
	for (var i=1;i<=total.value;i++)
	{		
	
		var_prod = document.getElementById('datos['+ i +','+ 1 +']');
		var_nota = document.getElementById('datos['+ i +','+ 10 +']');
		var_asig = document.getElementById('datos['+ i +','+ 12 +']');
		var_recu = document.getElementById('datos['+ i +','+ 11 +']');
			
		if ((var_prod))
		{
			dato = var_prod.value*1 + var_nota.value*1 + var_asig.value*1 + var_recu.value*1;
		
		}

		if ((dato))
		{		
			datop = prod + nota + asig + rec;

			//Valida que no se repida un rubro o producto, con excepcion de un campo vacio
			if (dato == datop && prod != '' && prod != 0)
			{ 
				retorno = false;				
				break;
			}
		}
	}
	return retorno;
}

function cont_filas(contador)
{
	var total = document.getElementById(contador);
	var totaln = (document.getElementById(contador).value -1)+ 2;
	total.value = totaln;
}

/* 
* function para habilitar un cuadro de texto desde un checkbox
*/
function validar_text() 
{ 

    var total = document.getElementById('nfilas');
	if (document.getElementById('activar1').checked == true)
	{
		lectura('Vet_Des', false);
                lectura('Vet_Des_Val', false);
		recorre_input(7, true);		
		encerar_input(7, '');
	}
	else 
	{
		lectura('Vet_Des', true);
                lectura('Vet_Des_Val', true);
		recorre_input(7, false);
		document.getElementById('Vet_Des').value='';
                document.getElementById('Vet_Des_Val').value='';
	}
	

	cal_sub_total(6);

	/*
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
		cal_des_total('Vet_Des'); 
	} else {
	    cal_des_importe(); 
	}
    
	cal_tarifas(8,6);//Llamado del calculo de tarifas
 	cal_iva_importe('Vet_Des',8,7,6); //Calculo del iva	
    cal_total()//Calculo del total	
}

/*
* Funcion que rrecorre un arreglo de objetos para dat lectura
*/
function recorre_input(colum, bool)
{
    var total = document.getElementById('nfilas');
	for (var i=1;i<=total.value;i++)
	{
		dato = document.getElementById('datos['+ i +','+ colum +']');
		recur = document.getElementById('datos['+ i +','+ 11 +']'); //Codigo de recursividad
		/*****************************************************************************/		
		/* Este codigo se agrego para que no afecte a la parte de facturas de compras */
		/*****************************************************************************/
		if ((recur))
		{
			var recursividad = 	recur.value;
		}
		else
		{
			var recursividad = 0;	
		}

		if ((dato) && recursividad  == 0 || (dato)){
			lectura('datos['+ i +','+ colum +']', bool);	
		}
	}
}

/*
* Funcion que encera (0) un arreglo de inputs
*/
function encerar_input(colum, valor)
{
    var total = document.getElementById('nfilas');
	for (var i=1;i<=total.value;i++)
	{		
		dato = document.getElementById('datos['+ i +','+ colum +']');
		if ((dato)){
			document.getElementById('datos['+ i +','+ colum +']').value = valor;
		}
	}
}

function quitar_fila_mod(boton,cantrecur)
{
	var padre = boton.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	
	/* 
	* Control de borrado de filas de recusividad 
	*/
	//campo = boton.parentNode.parentNode.childNodes[4].firstChild;

	//var texto = campo.name;

	//var arreglo = texto.split(',');
	//var fila = arreglo[0].substring(6,texto.length-1);

	//for (x=parseInt(fila)+1; x<=(parseInt(fila))+cantrecur; x++)
	//{
	     //quitar_fila_recur(x);												
	//}

	cal_sub_total(6);//Calculo del subtotal
	/* 
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
		cal_des_total('Vet_Des'); //Calculo del descuento total
	} else {
		cal_des_importe(6,7); //Calculo del descuento individual
	}
	cal_tarifas(8,6); //Calculo de las tarifas
	cal_iva_importe('Vet_Des',8,7,6);//Calculo del iva importe
	cal_total();//Calculo del total	
}

/* 
* Funcion que valida en envio de la informacion en la consulta de facturas 
*/
function validar_con_facturas(form)
{
	var carrera = true;
	
	/* 
	* Evalua si esta seleccionado el check de Opciones Avanzadas 
	*/
	if (document.getElementById('escu').checked==true)
	{
		if (requerido (form.Suc_Cod) == false || requerido(form.Mod_Cod) == false || requerido(form.Eta_Cod) == false || requerido(form.Car_Int) == false)
		{
			carrera = false;
		}					
	}//Fin del if (document.getElementById('escu').checked ==true)
	
	if (carrera == true)
	{
		if (requerido (form.txt_fec_ini) != false && requerido(form.txt_fec_fin) != false)
		{
			form.submit();
		}//Fin del if (requerido (form.ini) != false && requerido(form.fin) != false && carrera == false)
	}//Fin del if (carrera != false)	
}

/** 
* CREACION DE FACTURACION CON SOPORTE PARA SERVICIOS
* contenido: ID de la tabla donde se crean las filas
* codigo: Código interno del producto
* descripción: Descripción larga del producto
* precio: Precio del rubro
* iva: Porcentaje del iva
* codigoiva: Código interno del iva
* codigonota: Código interno del acta genera de notas
* codigorec: Código interno del rubro recursivo, utilizado para el interes
* codigoasi: Código interno de la asignatura
* ccxcc: Indica si se trata de cuentas por cobrar
* botonelim: Indica si hay botón eliminar
* cantrecur: Indica la cantidad de elementos recursivos para un rubro
* codigocnt: Código del contrato
* indice: Código autonumérico
*/
function nuevaFila(contenido,codigo,descripcion,precio,iva,codigoiva,codigonota,codigorec,codigoasi,ccxcc,botonelim,cantrecur,codigocnt,indice, unidad)
{	
	//if (verificar_prod_deuda(codigo, codigonota, codigoasi, codigorec, codigocnt, indice)==true)
	//{
		/**
		* Definición de variables para columnas
		*/
		var columna2 = document.createElement("td");//Codigo del producto manual
		var columna3 = document.createElement("td");//Cantidad
		var columna4 = document.createElement("td");//Descripcion
		var columna5 = document.createElement("td");//Precio Unitario
		var columna6 = document.createElement("td");//Importe
		var columna7 = document.createElement("td");//Descuento
		var columna8 = document.createElement("td");//Iva
		var columna9 = document.createElement("td");//Unidad
		var columna10 = document.createElement("td");//Boton Eliminar		
		/**
		* Definición de variable para fila
		*/	
		var fila = document.createElement("tr");
		/**
		* Definición del cuerpo de la tabla
		*/
		var cuerpo = document.getElementById (contenido);	
		/**
		* Alineacion de columnas - cabecera 
		*/
		columna2.setAttribute('align','center');		
		columna3.setAttribute('align','center');
		columna4.setAttribute('align','left');
		columna5.setAttribute('align','center');
		columna6.setAttribute('align','center');
		columna7.setAttribute('align','center');
		columna8.setAttribute('align','center');
		columna9.setAttribute('align','center');
		columna10.setAttribute('align','center');		
		/**
		* Aumentar en uno la cantidad de filas
		*/
		cont_filas('nfilas');	
		/**
		* Lectura del nuevo valor
		*/
		var total = document.getElementById('nfilas');
		/** 
		* Control saber si bloquea los cuadros de texto 
		*/
		if (ccxcc == 'si')
		{ solo_lect = true; }
		else
		{ solo_lect = false; }	
			
		/** 
		* Control para bloquear el precio unitario 
		*/
		if (cantrecur == -1)
		{ solo_lect_pre = true; }
		else
		{ solo_lect_pre = false; }
		
		/**
		* Cuadro oculto que guarda el codigo del producto
		*/
		var Cod_Pro=create_input('datos['+ total.value +','+ 1 +']', 2, 2,'text',false,'left');
		columna2.appendChild(Cod_Pro);
		Cod_Pro.value = codigo;
		/**
		* Cuadro que muestra codigo manual del producto
		*/
		var Pro_Ide=create_input('datos['+ total.value +','+ 2 +']', 2, 2,'hidden',solo_lect,'left')
		Pro_Ide.value = 0;
		columna2.appendChild(Pro_Ide);
		/**
		* Cuadro que muestra cantidad del producto
		*/
		var Vet_Can=create_input('datos['+ total.value +','+ 3 +']', 4, 8,'text',solo_lect,'right')
		Vet_Can.onblur= function() {numerico(this)};	
		columna3.appendChild(Vet_Can);	
		/**
		* Cuadro que muestra la descripcion del producto 
		*/
		var Ite_Lar=create_input('datos['+ total.value +','+ 4 +']', 40, 60,'text',true,'left');
		var etiqueta = "";
		if (unidad>1)
		{	etiqueta = "         calc=(cant x unidad::" + redondear(unidad,2) + ") x p.unitario"; }

		Ite_Lar.value = descripcion + etiqueta;
		columna4.appendChild(Ite_Lar);
		/**
		* Cuadro que muestra el precio unitario
		*/
		var Vet_Pru=create_input('datos['+ total.value +','+ 5 +']', 8,8,'text',solo_lect_pre,'right');
		Vet_Pru.value = precio;
		Vet_Pru.onblur= function() {numerico(this); positivo(this); };
		columna5.appendChild(Vet_Pru);
		/**
		* Cuadro para el importe
		*/			
		var Vet_Imp=create_input('datos['+ total.value +','+ 6 +']', 8, 8,'text',true,'right');
		Vet_Imp.onblur= function() {numerico(this)};
		columna6.appendChild(Vet_Imp);
		
		/**
		* Control para activar o desactivar la caja de texto del % de descuento
		*/			
		if (document.getElementById('activar1').checked == true)
		{ bool = true;	}
		else { bool = false }
		
		/** 
		* Cuadro que muestra el porcentaje de descuento invidividual en la fila
		*/
		var Vet_Dec=create_input('datos['+ total.value +','+ 7 +']',2,2,'text',bool,'right');		
		Vet_Dec.onblur= function() {numerico(this)};
		columna7.appendChild(Vet_Dec);		
		/**
		* Cuadro que muestra el porcentaje del Iva
		*/
		var Vet_Iva=create_input('datos['+ total.value +','+ 8 +']',2,2,'text',true,'right');		
		Vet_Iva.value = iva;
		columna8.appendChild(Vet_Iva);
		
		
		
		/**
		* Cuadro que muestra el codigo del iva
		*/
		var Iva_Cod=create_input('datos['+ total.value +','+ 9 +']', 20, 20,'hidden',false,'left');		
		Iva_Cod.value = codigoiva;		
		columna8.appendChild(Iva_Cod);
		/**
		* Codigo del acta de nota general
		*/
		var Nge_Cod=create_input('datos['+ total.value +','+ 10 +']', 20, 20,'hidden',false,'left');		
		Nge_Cod.value = codigonota;
		columna8.appendChild(Nge_Cod);
		/**
		* Codigo de la recursividad, especialmente para interes
		*/
		var Deu_Rec=create_input('datos['+ total.value +','+ 11 +']', 20, 20,'hidden',false,'left');		
		Deu_Rec.value = codigorec;
		columna8.appendChild(Deu_Rec);
		/**
		* Codigo de la asignatura (Actualmente no se utiliza)
		*/		
		var Asi_Int=create_input('datos['+ total.value +','+ 12 +']', 20, 20,'hidden',false,'left');		
		Asi_Int.value = codigoasi;		
		columna8.appendChild(Asi_Int);	
		/**
		* Codigo del contrato
		*/		
		var Cnt_Cod=create_input('datos['+ total.value +','+ 13 +']', 20, 20,'hidden',false,'left');			
		Cnt_Cod.value = codigocnt;
		columna8.appendChild(Cnt_Cod);
		/**
		* Codigo del indice autoincrementado
		*/		
		var Vet_Int=create_input('datos['+ total.value +','+ 14 +']', 20, 20,'hidden',false,'left');		
		Vet_Int.value = indice;
		columna8.appendChild(Vet_Int);	
		
		/**
		*  Asignacion del evento al cuadro de cantidad para calculos
		*/
		Vet_Can.onkeyup = function() {cal_importe(this, Vet_Pru, Vet_Imp, Pro_Uni); asignar_total_fac()};	
		/**
		* Asignacion del evento al cuadro de precio unitario para calculos
		*/
		Vet_Pru.onkeyup = function() {cal_importe(this, Vet_Can, Vet_Imp, Pro_Uni); asignar_total_fac()}; 
		/**
		* Asignacion del evento al cuadro de descuento para calculos
		*/
		Vet_Dec.onkeyup = function() {cal_importe(Vet_Pru, Vet_Can, Vet_Imp, Pro_Uni); asignar_total_fac()}; 

		if (botonelim == 'si')
		{
			/**
			* Creación del botón de eliminación
			*/
			columna10.appendChild(create_button('quitar_fila['+ total.value +']','X',function(){ quitar_fila(this,cantrecur); asignar_total_fac()}));						
		}

		/**
		* Valor de la unidad del producto
		*/		
		var Pro_Uni=create_input('datos['+ total.value +','+ 15 +']', 4, 10,'text',false,'right');		
		Pro_Uni.value = unidad;
		columna9.appendChild(Pro_Uni);	

		/**
		* Asignacion del evento al cuadro unidad para calculos
		*/
		Pro_Uni.onkeyup = function() {cal_importe(Vet_Pru, Vet_Can, Vet_Imp, Pro_Uni); asignar_total_fac()}; 
		
				
		fila.appendChild(columna2);
		fila.appendChild(columna3);
		fila.appendChild(columna4);
		fila.appendChild(columna5);
		fila.appendChild(columna6);
		fila.appendChild(columna7);
		fila.appendChild(columna8);
		fila.appendChild(columna9);
		fila.appendChild(columna10);			
		cuerpo.appendChild(fila);

		Vet_Can.value = 1;
		/**
		* Cargado cuando esta bloqueado el campo de cantidad 
		*/
		cal_importe(Vet_Pru, Vet_Can, Vet_Imp, Pro_Uni);				
	//}
	//else
	//{
		//alert ("El item ya se encuentra ingresado!");	
	//}
}//Fin del function nueva_fila

/*
* Funcion que permite verificar que un producto no se ingrese nuevamente en el detalle 
* tomando en cuenta: notasgener, contrato e indice
* prod: código del producto
* nota: código de la nota general
* asig: código de la asigantura
* rec: código recursivo del producto
* cnt: código del contrato
* ind: indice autoincrementado
*/
function verificarProdDeuda(prod, nota, asig, rec, cnt, ind)
{
	var retorno = true;
    var total = document.getElementById('nfilas');
	
	for (var i=1;i<=total.value;i++)
	{		
	
		var_prod = document.getElementById('datos['+ i +','+ 1 +']');
		var_nota = document.getElementById('datos['+ i +','+ 10 +']');
		var_asig = document.getElementById('datos['+ i +','+ 12 +']');
		var_recu = document.getElementById('datos['+ i +','+ 11 +']');
		var_cnt = document.getElementById('datos['+ i +','+ 13 +']');
		var_ind = document.getElementById('datos['+ i +','+ 14 +']');			
		
		if ((var_prod))
		{
			dato = var_prod.value + var_nota.value + var_asig.value + var_recu.value + var_cnt.value + var_ind.value;
		}

		if ((dato))
		{		
			datop = prod + nota + asig + rec + cnt + ind;

			/**
			* Valida que no se repida un rubro o producto, con excepcion de un campo vacio
			*/
			if (dato == datop && prod != '' && prod != 0)
			{ 
				retorno = false;				
				break;
			}
		}
	}
	return retorno;
}

function nuevaFila_ventaManual(contenido,codigo,descripcion,precio,iva,codigoiva,codigonota,codigorec,codigoasi,ccxcc,botonelim,cantrecur,codigocnt,indice, unidad)
{	
	//if (verificar_prod_deuda(codigo, codigonota, codigoasi, codigorec, codigocnt, indice)==true)
	//{
		/**
		* Definición de variables para columnas
		*/
		var columna2 = document.createElement("td");//Codigo del producto manual
		var columna3 = document.createElement("td");//Cantidad
		var columna4 = document.createElement("td");//Descripcion
		var columna5 = document.createElement("td");//Precio Unitario
		var columna6 = document.createElement("td");//Importe
		var columna7 = document.createElement("td");//Descuento
		var columna8 = document.createElement("td");//Iva
		var columna9 = document.createElement("td");//Unidad
		
		var columna10 = document.createElement("td");//Renta
		var columna11 = document.createElement("td");//+
		var columna12 = document.createElement("td");//-
		var columna13 = document.createElement("td");//Iva
		var columna14 = document.createElement("td");//+
		var columna15 = document.createElement("td");//-
		
		var columna16 = document.createElement("td");//Boton Eliminar		
		/**
		* Definición de variable para fila
		*/	
		var fila = document.createElement("tr");
		/**
		* Definición del cuerpo de la tabla
		*/
		var cuerpo = document.getElementById (contenido);	
		/**
		* Alineacion de columnas - cabecera 
		*/
		columna2.setAttribute('align','center');		
		columna3.setAttribute('align','center');
		columna4.setAttribute('align','left');
		columna5.setAttribute('align','center');
		columna6.setAttribute('align','center');
		columna7.setAttribute('align','center');
		columna8.setAttribute('align','center');
		columna9.setAttribute('align','center');
		columna10.setAttribute('align','center');		
		/**
		* Aumentar en uno la cantidad de filas
		*/
		cont_filas('nfilas');	
		/**
		* Lectura del nuevo valor
		*/
		var total = document.getElementById('nfilas');
		/** 
		* Control saber si bloquea los cuadros de texto 
		*/
		if (ccxcc == 'si')
		{ solo_lect = true; }
		else
		{ solo_lect = false; }	
			
		/** 
		* Control para bloquear el precio unitario 
		*/
		if (cantrecur == -1)
		{ solo_lect_pre = true; }
		else
		{ solo_lect_pre = false; }
		
		/**
		* Cuadro oculto que guarda el codigo del producto
		*/
		var Cod_Pro=create_input('datos['+ total.value +','+ 1 +']', 2, 2,'text',false,'left');
		columna2.appendChild(Cod_Pro);
		Cod_Pro.value = codigo;
		/**
		* Cuadro que muestra codigo manual del producto
		*/
		var Pro_Ide=create_input('datos['+ total.value +','+ 2 +']', 2, 2,'hidden',solo_lect,'left')
		Pro_Ide.value = 0;
		columna2.appendChild(Pro_Ide);
		/**
		* Cuadro que muestra cantidad del producto
		*/
		var Vet_Can=create_input('datos['+ total.value +','+ 3 +']', 4, 8,'text',solo_lect,'right')
		Vet_Can.onblur= function() {numerico(this)};	
		columna3.appendChild(Vet_Can);	
		/**
		* Cuadro que muestra la descripcion del producto 
		*/
		var Ite_Lar=create_input('datos['+ total.value +','+ 4 +']', 45, 60,'text',true,'left');
		var etiqueta = "";
		if (unidad>1)
		{	etiqueta = " calc=(cant x unidad::" + redondear(unidad,2) + ") x p.unitario"; }

		Ite_Lar.value = descripcion + etiqueta;
		columna4.appendChild(Ite_Lar);
		/**
		* Cuadro que muestra el precio unitario
		*/
		var Vet_Pru=create_input('datos['+ total.value +','+ 5 +']', 10,10,'text',solo_lect_pre,'right');
		Vet_Pru.value = precio;
		Vet_Pru.onblur= function() {numerico(this); valor_renta_compra(); positivo(this);  };
		columna5.appendChild(Vet_Pru);
		/**
		* Cuadro para el importe
		*/			
		var Vet_Imp=create_input('datos['+ total.value +','+ 6 +']', 8, 8,'text',true,'right');
		Vet_Imp.onblur= function() {numerico(this)};
		columna6.appendChild(Vet_Imp);
		
		/**
		* Control para activar o desactivar la caja de texto del % de descuento
		*/			
		if (document.getElementById('activar1').checked == true)
		{ bool = true;	}
		else { bool = false }
		
		/** 
		* Cuadro que muestra el porcentaje de descuento invidividual en la fila
		*/
		var Vet_Dec=create_input('datos['+ total.value +','+ 7 +']',2,2,'text',bool,'right');		
		Vet_Dec.onblur= function() {numerico(this);  valor_renta_compra()};
		columna7.appendChild(Vet_Dec);		
		/**
		* Cuadro que muestra el porcentaje del Iva
		*/
		var Vet_Iva=create_input('datos['+ total.value +','+ 8 +']',2,2,'text',true,'right');		
		Vet_Iva.value = iva;
		columna8.appendChild(Vet_Iva);
		
		/**
		* Cuadro que muestra el codigo del iva
		*/
		var Iva_Cod=create_input('datos['+ total.value +','+ 9 +']', 20, 20,'hidden',false,'left');		
		Iva_Cod.value = codigoiva;		
		columna8.appendChild(Iva_Cod);
		/**
		* Codigo del acta de nota general
		*/
		var Nge_Cod=create_input('datos['+ total.value +','+ 10 +']', 20, 20,'hidden',false,'left');		
		Nge_Cod.value = codigonota;
		columna8.appendChild(Nge_Cod);
		/**
		* Codigo de la recursividad, especialmente para interes
		*/
		var Deu_Rec=create_input('datos['+ total.value +','+ 11 +']', 20, 20,'hidden',false,'left');		
		Deu_Rec.value = codigorec;
		columna8.appendChild(Deu_Rec);
		/**
		* Codigo de la asignatura (Actualmente no se utiliza)
		*/		
		var Asi_Int=create_input('datos['+ total.value +','+ 12 +']', 20, 20,'hidden',false,'left');		
		Asi_Int.value = codigoasi;		
		columna8.appendChild(Asi_Int);	
		/**
		* Codigo del contrato
		*/		
		var Cnt_Cod=create_input('datos['+ total.value +','+ 13 +']', 20, 20,'hidden',false,'left');			
		Cnt_Cod.value = codigocnt;
		columna8.appendChild(Cnt_Cod);
		/**
		* Codigo del indice autoincrementado
		*/		
		var Vet_Int=create_input('datos['+ total.value +','+ 14 +']', 20, 20,'hidden',false,'left');		
		Vet_Int.value = indice;
		columna8.appendChild(Vet_Int);
		
		/**
		* Valor de la unidad del producto
		*/		
		var Pro_Uni=create_input('datos['+ total.value +','+ 15 +']', 4, 10,'text',false,'right');		
		Pro_Uni.value = unidad;
		columna9.appendChild(Pro_Uni);	

		/**
		* Asignacion del evento al cuadro unidad para calculos
		*/
		Pro_Uni.onkeyup = function() {cal_importe(Vet_Pru, Vet_Can, Vet_Imp, Pro_Uni); asignar_total_fac()}; 
		
		
		/*BOTONES RENTA -IVA*/
		/**
		* Caja de texto para el codigo de RENTA - Antes 2 
		*/
		var Ren_Con=create_input('datos['+ total.value +','+ 16 +']', 1, 2,'text',true,'left');
		columna10.appendChild(Ren_Con);
		/**
		* Creación del botón de busqueda de renta.
		*/
		var boton_renta_mas=create_button('Btn_RentaMas['+total.value+']','+',' ');
		columna11.appendChild(boton_renta_mas);		
			boton_renta_mas.onclick = function()
			{  
				/*
				* Asigna el valor del la fila 
				*/
				var indicer = this.name.split("[");
				var indicer = indicer[1].split("]")	
				document.getElementById('Hdd_Tip_Rta').value='R';
				//document.getElementById('Tbl_Cuentas').className = 'oculta'; 
				document.getElementById('Tbl_Rentas').className = 'muestra'; 
				document.getElementById('busrta').value="";
				document.getElementById('busrta').focus(); 
				/* 
				* Color de enfoque de la cuenta 
				*/
				lostfocus_ventas('datos', Ren_Con.name, true);	
				/*
				* Asigno el valor de fila 
				*/
				document.getElementById('Hdd_Txt_Ide').value=indicer[0];
			    /*
				* Variables que reciben los nombres  
				*/
				document.getElementById('Hdd_Ren_Con').value="datos["+ indicer[0] +",16]"; 
				document.getElementById('Hdd_Ren_Ide').value="datos["+ indicer[0] +",18]"; 
				document.getElementById('Hdd_Ren_Por').value="datos["+ indicer[0] +",20]"; 		
				multiple_capa('',600,300,'cont_fon_iva','cont_cua_iva','busqueda de retención','cont_cua_iva_titu'); 
				/*
				* Llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
				*/
				xDisplay('Tbl_Rencon', 'none');
			}
		
		/**
		* Creación del botón de busqueda de renta.
		*/
		var boton_renta_menos=create_button('Btn_RentaMas['+total.value+']','-',' ');		
		boton_renta_menos.onclick = function()
				{  	
					/**
					* Asigna el valor del la fila 
					*/
					var indicerm = this.name.split("[");
					var indicerm = indicerm[1].split("]")	
					//document.getElementById('Tbl_Cuentas').className = 'oculta'; 
					document.getElementById('Tbl_Rentas').className = 'oculta'; 			
					/**
					* Limpiar datos I.V.A. 
					*/ 
					document.getElementById("datos["+ indicerm[0] +",16]").value = ''; 
					document.getElementById("datos["+ indicerm[0] +",18]").value = ''; 
					document.getElementById("datos["+ indicerm[0] +",20]").value = '';
					/**
					* llamado a la función de cálculo de valores retenidos
					*/
					valor_renta_compra();
					/**
					* Color de enfoque de la cuenta 
					*/
					lostfocus_ventas('datos', '', false); 
				}
		columna12.appendChild(boton_renta_menos);
		
		/**
		* Caja de texto para el codigo de IVA  
		*/
		var Iva_Con=create_input('datos['+ total.value +','+ 17 +']', 1, 2,'text',true,'left');
				/**
				* Creo los 2 campos necesarios para el envio de códigos de retención 
				*/
				var Int_Rer=create_input('datos['+ total.value +','+ 18 +']',20,100,'hidden',true,'left');  /* Código interno RENTA */
				var Int_Rei=create_input('datos['+ total.value +','+ 19 +']',20,100,'hidden',true,'left');  /* Código interno IVA */
				var Int_Rpc=create_input('datos['+ total.value +','+ 20 +']',20,100,'hidden',false,'right');  /*Porcentaje retención */
				var Int_Riv=create_input('datos['+ total.value +','+ 21 +']',20,100,'hidden',true,'right');  /*Porcentaje retención IVA */
				columna13.appendChild(Iva_Con);
				columna13.appendChild(Int_Rer);
				columna13.appendChild(Int_Rei);
				columna13.appendChild(Int_Rpc);
				columna13.appendChild(Int_Riv);						
		/**
		* Creación del botón IVA +
		*/
		var boton_iva_mas=create_button('Btn_IvaMas['+total.value+']','+',' ');		
		boton_iva_mas.onclick = function()
				{ 
					/**
					* Asigna el valor del la fila 
					*/
					var indicei = this.name.split("[");
					var indicei = indicei[1].split("]")	
					document.getElementById('Hdd_Tip_Rta').value='I'; 
					//document.getElementById('Tbl_Cuentas').className = 'oculta'; 
					document.getElementById('Tbl_Rentas').className = 'muestra'; 
					document.getElementById('busrta').value="";
					//document.getElementById('busrta').focus();
					/**
					* asigno el valor de fila 
					*/
					document.getElementById('Hdd_Txt_Ide').value=indicei[0];
					/**
					* Color de enfoque de la cuenta 
					*/
					lostfocus_ventas('datos', Iva_Con.name, true)			
					/**
					* variables que reciben los nombres  
					*/
					document.getElementById('Hdd_Ren_Con').value="datos["+ indicei[0] +",17]";
					document.getElementById('Hdd_Ren_Ide').value="datos["+ indicei[0] +",19]";
					document.getElementById('Hdd_Ren_Por').value="datos["+ indicei[0] +",21]";
					/**
					* Llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
					*/
					xDisplay('Tbl_Rencon', 'none');
					multiple_capa('',600,300,'cont_fon_iva','cont_cua_iva','busqueda de retención','cont_cua_iva_titu');
				}	
		columna14.appendChild(boton_iva_mas);
				
		/**
		* Creación del botón IVA -
		*/
		var boton_iva_menos=create_button('Btn_IvaMenos['+total.value+']','-',' ');
		boton_iva_menos.onclick = function()
				{	
					/**
					* Asigna el valor del la fila 
					*/
					var indiceim = this.name.split("[");
					var indiceim = indiceim[1].split("]")	
					//document.getElementById('Tbl_Cuentas').className = 'oculta'; 
					document.getElementById('Tbl_Rentas').className = 'oculta'; 			
					/**
					* Limpiar datos I.V.A. 
					*/
					document.getElementById("datos["+ indiceim[0] +",17]").value = ''; 
					document.getElementById("datos["+ indiceim[0] +",19]").value = ''; 
					document.getElementById("datos["+ indiceim[0] +",21]").value = ''; 
					/**
					* llamado a la función de cálculo de valores retenidos
					*/
					valor_renta_compra();
					/**
					* Color de enfoque de la cuenta 
					*/
					lostfocus_ventas('datos', '', false); 
				}
		columna15.appendChild(boton_iva_menos);
															
		/**
		*  Asignacion del evento al cuadro de cantidad para calculos
		*/
		Vet_Can.onkeyup = function() {cal_importe(this, Vet_Pru, Vet_Imp, Pro_Uni); asignar_total_fac()};	
		/**
		* Asignacion del evento al cuadro de precio unitario para calculos
		*/
		Vet_Pru.onkeyup = function() {cal_importe(this, Vet_Can, Vet_Imp, Pro_Uni); asignar_total_fac()}; 
		/**
		* Asignacion del evento al cuadro de descuento para calculos
		*/
		Vet_Dec.onkeyup = function() {cal_importe(Vet_Pru, Vet_Can, Vet_Imp, Pro_Uni); asignar_total_fac()}; 

		if (botonelim == 'si')
		{
			/**
			* Creación del botón de eliminación
			*/
			columna16.appendChild(create_button('quitar_fila['+ total.value +']','X',function(){ quitar_fila(this,cantrecur); asignar_total_fac(); valor_renta_compra()}));						
		}

		
		
				
		fila.appendChild(columna2);
		fila.appendChild(columna3);
		fila.appendChild(columna4);
		fila.appendChild(columna5);
		fila.appendChild(columna6);
		fila.appendChild(columna7);
		fila.appendChild(columna8);
		fila.appendChild(columna9);
		fila.appendChild(columna10);
		fila.appendChild(columna11);
		fila.appendChild(columna12);
		fila.appendChild(columna13);
		fila.appendChild(columna14);
		fila.appendChild(columna15);
		fila.appendChild(columna16);			
		cuerpo.appendChild(fila);

		Vet_Can.value = 1;
		/**
		* Cargado cuando esta bloqueado el campo de cantidad 
		*/
		cal_importe(Vet_Pru, Vet_Can, Vet_Imp, Pro_Uni);				
	//}
	//else
	//{
		//alert ("El item ya se encuentra ingresado!");	
	//}
}//Fin del function nueva_fila

/**
* Valida los valores de la retencion 
*/
function valor_renta_compra()
{  
	var renta_grav=0;
	var renta_ret=0;
	var des_indivi=0;
	var iva_renta=0;
	var iva_suma=0;
	var total = document.getElementById('nfilas');
	var desctotal=document.getElementById('Vet_Des').value;
	
	for (j=1; j<=total.value; j++) /* inicio for (j=1; j<=total.value; j++)  */
	{	
	var dato = document.getElementById ('datos['+ j +','+ 4 +']');
	if (dato)
	{ 
			var importe = document.getElementById ('datos['+ j +','+ 6 +']').value;
			var iva  = document.getElementById ('datos['+ j +','+ 8 +']').value;
			var descindiv = document.getElementById ('datos['+ j +','+ 7 +']').value;
			var renpor= document.getElementById ('datos['+ j +','+ 20 +']').value;
			var reniva=document.getElementById ('datos['+ j +','+ 21 +']').value; 
			/**
			* Preguntamos si existe un descuento al total del importe 
			*/
			if(desctotal>0){ /* inicio if($desc_total>0){ */
				/**
				* Calculamos el porcentaje de descuento total 
				*/
				des_indivi=(importe*desctotal)/100;
			} /* fin if($desc_total>0){ */
			else
			{   /**
				* Calculamos el porcentaje de descuento individual 
				*/
				des_indivi=(importe*descindiv)/100;
			}
			if(isNaN(des_indivi)){ des_indivi=0; }
			/* Dismunicón de descuento individual al importe */	
			renta_ret=importe-des_indivi;
			renta_grav=renta_grav + redondear((renta_ret * renpor)/100,4); 
			/**
			* Calculo del I.V.A. 
			*/
			if(iva>0)
			{
				iva_renta=((renta_ret*iva)/100);
				iva_suma=iva_suma + redondear((iva_renta*reniva)/100,4);
			}
			}
		}//Fin del for (i=1; i<=total.value; i++)
	//alert("Hola");
	/**
	* Valor de la suma de las rentas 
	*/
	document.getElementById('Ren_Ren').value=redondear(parseFloat(renta_grav),2);
	/**
	* Valor de las sumas del I.V.A. 
	*/
	document.getElementById('Rei_Iva').value=redondear(parseFloat(iva_suma),2);
	/**
	* Valor de la sumatoria  
	*/
	document.getElementById('Riv_Tot').value=redondear(parseFloat(renta_grav),2)+redondear(parseFloat(iva_suma),2);
	/**
	* Valor total a pagar  
	*/
	document.getElementById('Val_Pcc').value=redondear(parseFloat(document.getElementById('t_rubros').value)-parseFloat(document.getElementById('Riv_Tot').value));
	document.getElementById ('Vet_Tot').value=document.getElementById ('Val_Pcc').value;	
	
	
}//Fin del valor_renta_compra()

/**
* función chekear renta 
*/
function todo_check_renta(ren_cod, ren_sri, ren_por)
{
 // alert(document.getElementById('check_renta_iva').checked);	
  var filas = document.getElementById ('nfilas');	
  if(document.getElementById('check_renta_iva').checked)
  {
  		for (var j = 1; j <= filas.value; j++)
		{
			renta = document.getElementById('datos['+ j +','+ 16 +']'); /* Ren_Con */
			if (renta)
			{	
				if(document.getElementById('Hdd_Tip_Rta').value=='R')  
				{
					document.getElementById('datos['+ j +','+ 16 +']').value=ren_sri; /* Ren_Con */
 					document.getElementById('datos['+ j +','+ 18 +']').value=ren_cod; /* Código interno RENTA */
					document.getElementById('datos['+ j +','+ 20 +']').value=ren_por; /* Porcentaje retención  */
					valor_renta_compra();
				}else
		  		{
					document.getElementById('datos['+ j +','+ 17 +']').value=ren_sri; /* Iva_Con */
					document.getElementById('datos['+ j +','+ 19 +']').value=ren_cod;  /* Código interno IVA */
					document.getElementById('datos['+ j +','+ 21 +']').value=ren_por; /*Porcentaje retención IVA */
					valor_renta_compra();
			   	}
		    }
       }
  }else
  {
		valor_renta_compra();  
  }
}//Fin del todo_check_renta(ren_cod, ren_sri, ren_por)

/**
* Funcion que da el enfoque a los cuadroa de texto de comprobante, renta e iva 
*/
function lostfocus_ventas(lostfocus, setfocus, bool)
{
	/**
	* name = nombre del texto al que se quiere quitar el foco 
    * sifoco = nombre del texto que se quiere mantener el quitar foco 
	*/	

	var total = document.getElementById('nfilas');	
	/**
	* Campo al que no se debe quitar el foco 
	*/
    
	if (setfocus != "")
	{
		var sifoco = document.getElementById(setfocus);
	}//Fin del if (setfocus != "")
	//alert(setfocus);
	for (i=1; i<=total.value; i++)
	{				
		var input_ren = document.getElementById(lostfocus+'['+ i +','+ 16 +']');		
		var input_iva = document.getElementById(lostfocus+'['+ i +','+ 17 +']');				

		if (input_ren)
		{
			/**
			* Quita el color de enfoque de la cuenta 
			*/
			//enfoque(input, false) 									
			/**
			* Quita el color de enfoque de la renta 
			*/
			enfoque(input_ren, false) 									
			/**
			* Quita el color de enfoque del iva 
			*/
			enfoque(input_iva, false) 												
		}//Fin del if (input)	
	}//Fin del for (i=1; i<=total.value; i++)
	
	/**
	* Pone el foco al campo que se seleccion en caso de existir 
	*/
	if (sifoco)
	{		
		enfoque(sifoco, bool) 	
	}//Fin del if (setfocus)
}//Fin del lostfocus_ventas(lostfocus, setfocus, bool)

/**
* funcion para mostra el componente que muestra la busqueda de renta 
*/
function busca_renta_btn(form, campo)
{   			
	/*
	* Asigna el valor del la fila 
	*/
	var indicer = campo.name.split("[");
	var indicer = indicer[1].split("]")	
	document.getElementById('Hdd_Tip_Rta').value='R'; 
	//document.getElementById('Tbl_Cuentas').className = 'oculta'; 
	document.getElementById('Tbl_Rentas').className = 'muestra'; 
	document.getElementById('busrta').value="";
	document.getElementById('busrta').focus();
	/* 
	* Color de enfoque de la cuenta 
	*/ 
	lostfocus_ventas('datos', document.getElementById('datos["+ indicer[0] +",16]'), true);	
	/*
	* Asigno el valor de fila 
	*/
	document.getElementById('Hdd_Txt_Ide').value=indicer[0];
	/*
	* Variables que reciben los nombres  
	*/
	document.getElementById('Hdd_Ren_Con').value="datos["+ indicer[0] +",16]"; 
	document.getElementById('Hdd_Ren_Ide').value="datos["+ indicer[0] +",18]"; 
	document.getElementById('Hdd_Ren_Por').value="datos["+ indicer[0] +",20]";		
	multiple_capa('',600,300,'cont_fon_iva','cont_cua_iva','busqueda de retención','cont_cua_iva_titu'); 
	/*
	* Llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
	*/
	xDisplay('Tbl_Rencon', 'none');			
}//Fin del busca_renta_btn(form, campo)	

/**
* funcion que permite quitar la tabla de renta 
*/
function busca_renta_quita_btn(form, campo)
{				
	/**
	* Asigna el valor del la fila 
	*/
	var indicerm = campo.name.split("[");
	var indicerm = indicerm[1].split("]")	
	//document.getElementById('Tbl_Cuentas').className = 'oculta'; 
	document.getElementById('Tbl_Rentas').className = 'oculta'; 			
	/**
	* Limpiar datos I.V.A. 
	*/
	document.getElementById("datos["+ indicerm[0] +",16]").value = ''; 
	document.getElementById("datos["+ indicerm[0] +",18]").value = ''; 
	document.getElementById("datos["+ indicerm[0] +",20]").value = ''; 
	/**
	* llamado a la función de cálculo de valores retenidos
	*/
	valor_renta_compra();
	/**
	* Color de enfoque de la cuenta 
	*/
	lostfocus_compras('datos', '', false); 
}//Fin del busca_renta_quita_btn(form, campo)

/**
* funcion que permite mostrar la tabla con parametro I.V.A 
*/
function busca_iva_btn(form, campo)
{ 
		/**
		* Asigna el valor del la fila 
		*/
		var indicei = campo.name.split("[");
		var indicei = indicei[1].split("]")	
		document.getElementById('Hdd_Tip_Rta').value='I'; 
		//document.getElementById('Tbl_Cuentas').className = 'oculta'; 
		document.getElementById('Tbl_Rentas').className = 'muestra'; 
		document.getElementById('busrta').value="";
		//document.getElementById('busrta').focus();
		/**
		* asigno el valor de fila 
		*/
		document.getElementById('Hdd_Txt_Ide').value=indicei[0];
		/**
		* Color de enfoque de la cuenta 
		*/
		lostfocus_ventas('datos', document.getElementById('datos["+ indicer[0] +",17]'), true)			
		/**
		* variables que reciben los nombres  
		*/
		document.getElementById('Hdd_Ren_Con').value="datos["+ indicei[0] +",17]";
		document.getElementById('Hdd_Ren_Ide').value="datos["+ indicei[0] +",19]";
		document.getElementById('Hdd_Ren_Por').value="datos["+ indicei[0] +",21]";
		/**
		* Llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
		*/
		xDisplay('Tbl_Rencon', 'none');
		multiple_capa('',600,300,'cont_fon_iva','cont_cua_iva','busqueda de retención','cont_cua_iva_titu');
}//Fin del busca_iva_btn(form, campo)

function busca_iva_quita_btn(form, campo)
{		
	/**
	* Asigna el valor del la fila 
	*/
	var indiceim = campo.name.split("[");
	var indiceim = indiceim[1].split("]")	
	//document.getElementById('Tbl_Cuentas').className = 'oculta'; 
	document.getElementById('Tbl_Rentas').className = 'oculta'; 			
	/**
	* Limpiar datos I.V.A. 
	*/
	document.getElementById("datos["+ indiceim[0] +",17]").value = ''; 
	document.getElementById("datos["+ indiceim[0] +",19]").value = ''; 
	document.getElementById("datos["+ indiceim[0] +",21]").value = ''; 
	/**
	* llamado a la función de cálculo de valores retenidos
	*/
	valor_renta_compra();
	/**
	* Color de enfoque de la cuenta 
	*/
	lostfocus_compras('datos', '', false); 
}//FIn del busca_iva_quita_btn(form, campo)

/**
* Funcion para crear un modal
*/
function multiple_capa(pagina,ancho,alto, transparente,contenedor,titulo,contenedor_titu)
{							
	$('#'+contenedor).fadeIn(1000);
	var table = document.createElement("table"); 
	table.width='100%';
	table.border=0;
	table.cellPadding=0;
	table.cellSpacing=0;
	var tr = document.createElement("tr"); 
	var td = document.createElement("td"); 
	td.border=0;
	//var img =document.createElement("img");
	//img.src="../../Librerias/jquery/modal/img/btn_close.png"; Comentado porque no existe el archivo de imagen
	//img.style.cursor="pointer";
	//img.onclick=function() antes se usaba el boton cerrar de la barra de titulo
	//{
	document.getElementById(transparente).onclick=function()
	{	
		  document.getElementById(transparente).style.display="none";
		  document.getElementById(contenedor).style.display="none";
		  //document.getElementById(contenedor_titu).removeChild(table); se elimino porque ya no se usa titulos
	}
	var td1 = document.createElement("td"); 
	//td1.appendChild(img);
	td1.align='right';
	tr.appendChild(td); 
	tr.className='BarraTitulo';
	tr.innerHTML=titulo;
	tr.appendChild(td1);
	table.appendChild(tr); 
	
		//document.getElementById(contenedor_titu).appendChild(table); comentado para omitir la barra de titulo en el 
		document.getElementById(transparente).style.display=""; 
		document.getElementById(contenedor).style.display="";
		var wscr = $(window).width();
		var hscr = $(window).height();
		$('#'+transparente).css("width", wscr);
		$('#'+transparente).css("height", hscr);
		/**¨
		* ventana flotante
		*/
		$(window).resize();
		/**
		* dimensiones de la ventana
		*/
		var wscr = $(window).width();
		var hscr = $(window).height();
	  	//  $('#bgtransparent').style.display ='block';
		/**
		* estableciendo dimensiones de background
		*/
		$('#'+transparente).css("width", wscr);
		$('#'+transparente).css("height", hscr); 
		/**
		* definiendo tamaño del contenedor
		*/
		$('#'+contenedor).css("width", ancho+'px');
		$('#'+contenedor).css("height", alto+'px');
		/**
		* obtiendo tamaño de contenedor
		*/
		var wcnt = $('#'+contenedor).width();
		var hcnt = $('#'+contenedor).height();
		/**
		* obtener posicion central
		*/
		var mleft = ( wscr - wcnt ) / 2;
		var mtop = ( hscr - hcnt ) / 2;
		/**
		* estableciendo posicion
		*/
		$('#'+contenedor).css("left", mleft+'px');
		$('#'+contenedor).css("top", mtop+'px');
}

/*
* Recalcula cantidades Retencion de la Venta  Renta-Iva
*/
function cal_retencionVenta()
{   
	var filas = document.getElementById ('nfilas');	
	var sub_total=0;
        var TotRenta=0;
	var TotIva=0;
       
	for (var x = 1; x <= filas.value; x++)
	{
		var iva12=0;
		imp = document.getElementById ('datos['+ x +',6]');  //Columna importe
		iva = document.getElementById ('datos['+ x +',8]');  //Columna IVA
		renta_por = document.getElementById ('datos['+ x +',20]');  // hidden contiene % de renta
		iva_por = document.getElementById ('datos['+ x +',21]');  // hidden contiene % de iva
		
		/* Calculo de la retencion(renta) */
		TotRenta=TotRenta+((imp.value*renta_por.value)/100);
		
		/* Calculo de la retencion(iva) */
		if(iva.value!="0")
		{
			iva12=(imp.value*iva.value)/100;
			TotIva=TotIva+((iva12*iva_por)/100);
		}
	}	
	document.getElementById ('Ren_Ren').value=redondear(TotRenta,2);	
	document.getElementById ('Rei_Iva').value=redondear(TotIva,2);
	document.getElementById ('Riv_Tot').value=redondear(TotRenta+TotIva,2);	
	document.getElementById ('Val_Pcc').value=document.getElementById ('t_rubros').value - document.getElementById ('Riv_Tot').value;
	document.getElementById ('Vet_Tot').value=document.getElementById ('Val_Pcc').value;
}