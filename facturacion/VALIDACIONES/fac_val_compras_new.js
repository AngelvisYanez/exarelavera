/**
* Ajax para la multicapa del modal
*/
function nuevoAjax(){
	var xmlhttp=false;
 	try {
 		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
 	} catch (e) {
 		try {
 			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
 		} catch (E) {
 			xmlhttp = false;
 		}
  	}

	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
 		xmlhttp = new XMLHttpRequest();
	}
	return xmlhttp;
}

/**
* Funcion que rrecorre un arreglo de objetos para dar lectura
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
		/*****************************************************************************/
		/*****************************************************************************/		
		if ((dato) && recursividad == 0){
			lectura('datos['+ i +','+ colum +']', bool);	
		}
	}
}


/**
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

/**
* function para habilitar un cuadro de texto desde un checkbox
*/
function validar_text() 
{ 
    var total = document.getElementById('nfilas');
	if (form2.activar1.checked == true)
	{
		lectura('Vet_Des', false);
		recorre_input(7, true);		
		encerar_input(7, '');
	}
	else 
	{
		lectura('Vet_Des', true);
		recorre_input(7, false);
		document.getElementById('Vet_Des').value='';
	}
	
	cal_sub_total(6);
	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
		cal_des_total('Vet_Des'); 
	} else {
	    cal_des_importe(); 
	}
    /**
	* Llamado del calculo de tarifas
	*/
	cal_tarifas(8,6);
	/**
	* Calculo del iva	
	*/
 	cal_iva_importe('Vet_Des',8,7,6); 
	/**
	* Calculo del total	
	*/
    cal_total()
}

/**
* Eliminar las filas
*/
function quitar_fila(boton,cantrecur)
{
	var padre = boton.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	
	/**
	* Control de borrado de filas de recusividad 
	*/
	campo = boton.parentNode.parentNode.childNodes(4).firstChild;
	var texto = campo.name;
	var arreglo = texto.split(',');
	var fila = arreglo[0].substring(6,texto.length-1);

	for (x=parseInt(fila)+1; x<=(parseInt(fila))+cantrecur; x++)
	{
		dato = document.getElementById('datos['+ x +','+ 1 +']');
		if ((dato))
		{
			quitar_fila_recur(x);												
		}
	}
	/**
	* Calculo del subtotal
	*/
	cal_sub_total(6);
	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
		/**
		* Calculo del descuento total
		*/
		cal_des_total('Vet_Des'); 
	} else {
		/**
		* Calculo del descuento individual
		*/
		cal_des_importe(); 
	}
	/** 
	* Calculo de las tarifas
	*/
	cal_tarifas(8,6);
	/** 
	* Calculo del iva importe 
	*/
	cal_iva_importe('Vet_Des',8,7,6);
	/**
	* Calculo del total	
	*/
	cal_total()
}

/**
* Elimina una fila recursiva 
*/
function quitar_fila_recur(fila)
{
	var padre = document.getElementById('datos['+ fila +','+ 1 +']').parentNode.parentNode;
	padre.parentNode.removeChild (padre);
}

/**
* Elimina una fila recursiva en la modificacion
*/
function quitar_fila_mod(boton,cantrecur)
{
	var padre = boton.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	/**
	* Control de borrado de filas de recusividad 
	*/
	campo = boton.parentNode.parentNode.childNodes(4).firstChild;
	var texto = campo.name;
	var arreglo = texto.split(',');
	var fila = arreglo[0].substring(6,texto.length-1);

	for (x=parseInt(fila)+1; x<=(parseInt(fila))+cantrecur; x++)
	{
		quitar_fila_recur(x);												
	}
	/**
	* Calculo del subtotal
	*/
	cal_sub_total(6);
	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
		/**
		* Calculo del descuento total
		*/
		cal_des_total('Vet_Des'); 
	} else {
		/** 
		* Calculo del descuento individual
		*/
		cal_des_importe(6,7); 
	}
	/**
	* Calculo de las tarifas
	*/
	cal_tarifas(8,6); 
	/**
	* Calculo del iva importe
	*/
	cal_iva_importe('Vet_Des',8,7,6);
	/**
	* Calculo del total	
	*/
	cal_total();
}

/**
* Funcion utilizada para almacenar temporalmente las filas eliminadas 
*/
function array_elim(objeto)
{
	/**
	* Devuelve la fila del objeto datos HTML
	*/
	var fila=objeto.id.substring(6,7);
	/**
	* Aumentar en uno la cantidad de filas 
	*/
	cont_filas('nfilas_elim');
	/**
	* Lectura del nuevo valor de filas eliminadas
	*/
	var total = document.getElementById('nfilas_elim');
	/**
	* Columnas de la nueva tabla 
	*/
	var columnas = new Array();
	/**
	* Filas de la nueva tabla 
	*/
	var filas = document.createElement("tr");
	/**
	* Nombre del body de la nueva tabla 
	*/
	var cuerpo = document.getElementById ('e_contenido');
	
	for (i=1;i<=11;i++)
	{
		columnas[i] = document.createElement("td"); 
		var Eliminado=create_input('elim['+ total.value +','+ i +']', 15, 15,'hidden',false,'left');
		Eliminado.value = document.getElementById('datos['+ fila +','+ i +']').value;
		columnas[i].appendChild(Eliminado);
		filas.appendChild(columnas[i]);	
		cuerpo.appendChild(filas);
	}
}

/**
* Cuenta las filas registradas 
*/
function cont_filas(contador)
{
	var total = document.getElementById(contador);
	var totaln = (document.getElementById(contador).value -1)+ 2;
	total.value = totaln;
}

/**
* Calculo del subtotal
*/
function cal_sub_total(colum)
{
	var filas = document.getElementById('nfilas');		
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
	document.getElementById('t_subtotal').value=redondear(sub_total,2);
}

/**
* Calculo del importe - FUNCION PRINCIPAL
*/
function cal_importe(n1,n2,total)
{
	total.value = parseFloat(n1.value)*parseFloat(n2.value);
	if (isNaN(total.value)) 
		{ total.value=0; } 		
	/**
	* Llamado del calculo del sub-total
	*/
	cal_sub_total(6);
	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
			cal_des_total('Vet_Des'); 
		    } else {
			         cal_des_importe(6,7); 
		    }       
		 /**
		 * Llamado del calculo de tarifas	
		 */
         cal_tarifas(8,6);
		 /**
		 * Calculo del iva	
		 */
 		 cal_iva_importe('Vet_Des',8,7,6); 
		 /**
		 * Calculo del total
		 */
         cal_total()
}

/**
* Calculo de la tarifa - Standar para Compra y Ventas
*/
function cal_tarifas(colum_iva, colum_val)
{
	var filas = document.getElementById ('nfilas');	
	var tarifa_0 = 0;
	var tarifa_12 = 0;
	//alert(document.getElementById('datos['+ 1 +','+ 6 +']').value);
	for (var j = 1; j <= filas.value; j++)
	{
		iva = document.getElementById ('datos['+ j +','+ colum_iva +']'); //Aqui estaba antes 8
		valor = document.getElementById ('datos['+ j +','+ colum_val +']') //Importe - Aqui estaba antes 6
		//alert(valor.value);
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

/**
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
	document.getElementById('t_descuento').value= redondear(total_desc,2);	
}

/**
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

/**
* Calculo del iva por importe - Standar para Compra y Ventas 		//Ojo debe modificarse - revisar
*/
function cal_iva_importe(campo, colum_iva, colum_des, colum_imp)
{
	var filas = document.getElementById ('nfilas');	
	var valor = 0;
	var desc = 0;
	var total_iva = 0;
	var iva_12 = 0;
	/**
	* Evalua para saber cuando debe calcular un iva total o invidual
	*/
	if (document.getElementById('activar1').checked == true){	
		des = document.getElementById (campo);//% de descuento - Aqui estaba antes 'Vet_Des'
		tarifa_12 = document.getElementById ('t_iva12');
		t_subtotal = document.getElementById ('t_subtotal');		

		for (var j = 1; j <= filas.value; j++)
		{
			iva = document.getElementById ('datos['+ j +','+ colum_iva +']');//Aqui estaba antes 8			
			if ((iva) && (iva.value > 0)){ //Solo entra cuando el iva sea mayor a 0				
				iva_12 = iva.value;
			}
		}
		if (isNaN(des.value)) { des.value=0; } 	
		//Descuento total de la tarifa 12, antes estaba tarifa_12, se corrigio
		desc = (t_subtotal.value * redondear(des.value,2))/100; 
		if (isNaN(desc)) { desc=0; } 	
		//alert('(('+tarifa_12.value +'-'+ desc+') * '+iva_12+')/100');
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

/**
* calculo total
*/
function cal_total()
{
	var total = 0
	sub_total = document.getElementById('t_subtotal');
	iva = document.getElementById('t_iva');	
	ice=document.getElementById('t_ice');	
	des = document.getElementById('t_descuento');
	
	total = ((parseFloat(sub_total.value) - parseFloat(des.value)) + parseFloat(iva.value) + parseFloat(ice.value)); 
	document.getElementById('t_rubros').value = redondear(total,2);	
        
}

/**
* Cheques 
*/
function nueva_fila_cheque_com(contenido,cmb_cod,cmb_des,fecha,valor)
{
		var columna1 = document.createElement("td"); // Banco
		var columna2 = document.createElement("td"); // Nº Cheque
		var columna3 = document.createElement("td"); // Valor del Cheque
		var columna4 = document.createElement("td"); // Fecha Elab
		var columna5 = document.createElement("td"); // Observacion
		var columna6 = document.createElement("td"); // Boton
		
		var fila = document.createElement("tr");

		var cuerpo = document.getElementById (contenido);
		
		/**
		* Aumentar en uno la cantidad de filas
		*/
		cont_filas('nfilas_ch');
		
		/**
		* Lectura del nuevo valor
		*/
		var total = document.getElementById('nfilas_ch');
						
		/**
		* Creación del Combo que va a almacenar los bancos
		*/
		var combo=create_combo('datos_ch['+ total.value +','+ 3 +']',cmb_cod,cmb_des,'left');
		combo.onchange= function () { secuencia_cheque(total,n_cheque,this, 'datos_ch'); };
		columna1.appendChild(combo);		
		/**
		* Cuadro que muestra el codigo del cheque
		*/
		var n_cheque=create_input('datos_ch['+ total.value +','+ 4 +']', 10, 10,'text',false,'right');
		n_cheque.onblur= function() {numerico(this)};
		columna2.appendChild(n_cheque);
		/**
		* Cuadro que muestra el valor del cheque
		*/
		var v_cheque=create_input('datos_ch['+ total.value +','+ 5 +']', 10, 10,'text',false,'right');
		v_cheque.onblur= function() {numerico(this)};
		v_cheque.onkeyup= function() {cal_total_cheques(5, 'nfilas_ch', 'datos_ch')};
		//alert(document.getElementById(valor).value);
		v_cheque.value = document.getElementById(valor).value;
		columna3.appendChild(v_cheque);				
		/**
		* Cuadro de texto que guarda la fecha de elaboracion
		*/
		var fecha_elab=create_input('datos_ch['+ total.value +','+ 6 +']', 7, 7,'text',false,'left');
		fecha_elab.onblur= function() {validar_fecha2(this)};
		var cop_fec = document.getElementById(fecha);
		fecha_elab.value = cop_fec.value;
		columna4.appendChild(fecha_elab);
		/**
		* Cuadro que guarda alguna observación del cheque
		*/
		var observacion=create_input('datos_ch['+ total.value +','+ 8 +']', 35, 35,'text',false,'left');
		columna5.appendChild(observacion);
		/**
		* Cuadro oculto que almacena el numero o cantidad de cheque 
		*/
		var che_cod=create_input('datos_ch['+ total.value +','+ 9 +']', 10, 10,'hidden',false,'left');	
		columna5.appendChild(che_cod);
		che_cod.value = total.value
		
		columna6.appendChild(create_button('quitar_fila','X',quitar_fila_che));

		fila.appendChild(columna1);
		fila.appendChild(columna2);
		fila.appendChild(columna3);
		fila.appendChild(columna4);
		fila.appendChild(columna5);
		fila.appendChild(columna6);
	
		cuerpo.appendChild(fila);		

		combo.options[0].value ='NULL';
		combo.options[0].text = '-';
		/**
		* Control para que se seleccione el banco siempre y cuando exista 1 
		*/
		if (combo.length==2)
		{
			combo.selectedIndex = 1;
		}
}//Fin del nueva_fila_cheque_com(contenido,cmb_cod,cmb_des,fecha,valor)

/*
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
	var sub_total=0;//alert('ver');
	for (var j = 1; j <= filas.value; j++)
	{
		dato = document.getElementById (datos+'['+ j +','+ colum +']');

			if ((dato) && (dato.value.length > 0))
				{
					sub_total = sub_total + parseFloat(dato.value);
				}	
	}
	if (isNaN(sub_total)) 
		{ sub_total=0; } 		
	document.getElementById('txt_total').value=redondear(sub_total,2);
}//Fin del cal_total_cheques(colum, filas, datos)

/**
* Carga los bancos en un combo
*/
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

/**
* Quita un cheque agregado
*/
function quitar_fila_che()
{
	var padre = this.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	cal_total_cheques(5, 'nfilas_ch', 'datos_ch');
}

function quitar_fila_st(fila)
{
	var padre = fila.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	cal_total_cheque(5);
}

/**
* Valida los cheques en compras 
*/
function validar_cheques_com(form)
{
	var entrada1 = true; var entrada2 = true;
	var entrada3 = true; var entrada4 = true;
	var cont = 0; var entrada5 = true;
	//alert('ver');
	var filas = document.getElementById ('nfilas_ch');	
					
	for (var j = 1; j <= filas.value; j++)
	{
		dato1 = document.getElementById ('datos_ch['+ j +','+ 3 +']');//Banco
		dato2 = document.getElementById ('datos_ch['+ j +','+ 4 +']');//No Cheque
		dato3 = document.getElementById ('datos_ch['+ j +','+ 5 +']');//Valor del Cheque
		dato4 = document.getElementById ('datos_ch['+ j +','+ 6 +']'); //Fecha Elaboración
		dato5 = document.getElementById ('datos_ch['+ j +','+ 7 +']'); //Fecha Cobro
		dato6 = document.getElementById ('datos_ch['+ j +','+ 8 +']'); //Observación
		dato7 = document.getElementById ('datos_ch['+ j +','+ 9 +']'); //Codigo del cheque		

		/**
		* Determina la cantidad de filas registradas 
		*/
		if (dato7)
		{ 
			cont++;
		}//Fin del if ((dato7) && (dato7.value ==""))		

		/**
		* Banco 
		*/
		if ((dato1) && (dato1.value =='NULL'))
		{ 
			alert("Falta llenar Informacion necesaria.");
			entrada1 = false;
			dato1.focus();
			return false;
		}//Fin del if ((dato) && (dato.value ==""))		
		
		/**
		* Control para evitar que ingrese cuando no esta creado un objeto 
		*/
		if ((dato1))
		{
			/**
			* Control solo para cuando el pago es con cheque 
			*/
			var Obj_Cadena = new String (dato1.value);
			var parametros = Obj_Cadena.split('*');
		}//Fin del if ((dato1)
		else
		{
			/**
			* Se pone el valor X solo para que se cree el arreglo 
			*/
			var parametros = new Array();			
			parametros[2] = 'X';	
		}
						
		/**
		* Evalua si se trata de un banco 
		*/
		if (parametros[2] == 'B')
		{
			/**
			* No Cheque 
			*/
			if ((dato2) && (dato2.value ==""))
			{ 
// Erik - Cambie para que se pueda padar a credito y efectivo -- revisar
//				alert("Falta llenar Informacion necesaria.");
//				entrada2 = false;
//				dato2.focus();
//				return false;								
			}//Fin del if ((dato2) && (dato2.value ==""))
		}//FIn del if (parametros[2] == 'B')

		/**
		* Valor del Cheque 
		*/
		if ((dato3) && dato3.value =="")
		{ 
			alert("Falta llenar Informacion necesaria.");
			entrada3 = false;
			dato3.focus();	
			return false;
		}//Fin del if ((dato3) && dato3.value =="")
		
		/**
		* Fecha Elaboración 
		*/
		if ((dato4) && dato4.value =="")
		{ 	
			alert("Falta llenar Informacion necesaria.");
			entrada4 = false;
			dato4.focus();	
			return false;
		}//Fin del if ((dato4) && dato4.value =='NULL')		
	}//Fin del for (var j = 1; j <= filas.value; j++)
	
	/**
	* Validación del Totales de Cheques 
	*/
	txt_compra = document.getElementById ('Val_Pcc'); //Total de la compra
	txt_cheque = document.getElementById ('txt_total'); //Total del cheque					
	
	if (parseFloat(txt_compra.value) != parseFloat(txt_cheque.value))
	{ 			
		alert("El valor del(los) Pago(s) debe ser igual al valor Total a Pagar.");
		entrada5 = false;
		return false;
	}//Fin del if ((dato4) && dato4.value =='NULL')		
	
	/**
	* Control final para el envio 
	*/
	if (entrada1==true && entrada2==true && entrada3==true && entrada4==true && cont > 0 && entrada5==true)
	{	   
            valida_num_cheque(form);
            //confirmacion(form);
	}	
	else
	{
		alert("Es necesario ingresar al menos un Cheque");
	}
}//Fin del validar_cheques_com(form)
function valida_num_cheque(form)
{
    var nums=new Array();var repetidos='',banRepe='';
    var filas = document.getElementById ('nfilas_ch');	
	for (var j = 1; j <=filas.value; j++){ 
            var box = document.getElementById('datos_ch['+ j +','+ 3 +']');           
            if(box!==null){
                var Obj_Cadena = new String (box.value);
                var parametros = Obj_Cadena.split('*');  
                var banco = box.options[box.selectedIndex].text; 
                for (var i = 0; i <  nums.length; i++)
                    if(nums[i]['cod']===parametros[0]&&nums[i]['num']===document.getElementById ('datos_ch['+ j +','+ 4 +']').value)
                        {repetidos=nums[i]['num'];banRepe=banco;break;}
                if(document.getElementById ('datos_ch['+ j +','+ 4 +']').value!==''&&parametros[2]==='B'&&repetidos==='')
                  nums.push({cod:parametros[0],ban:banco,num:document.getElementById ('datos_ch['+ j +','+ 4 +']').value}); 
            }
        }
        if(nums.length>0){
        if(repetidos===''){
            $.post('',{'valChe': nums}, function(response){
                if(response['success']===true){
                    if(response['valid']===false){                                       
                        alert(response['msg']);
                    }else {confirmacion(form);/* alert('ok'); /**/}
                }else {alert("No se logro obtener n&uacutemero del cheque");}                                
            },'json').fail(function(error) { alert("El Servidor ha fallado en responder!");});; 
        }else{alert('El cheque No. '+repetidos+' de '+banRepe+' esta repetido!');}
        }else{confirmacion(form); /*alert('ok'); /**/}
}
/**
* Calculo del importe de la factura de compra para SERVCIOS 
*/
function cal_importe_com(n1,n2,total)
{
	total.value = parseFloat(n1.value)*parseFloat(n2.value);
	if (isNaN(total.value)) 
		{ total.value=0; } 		
	/**
	* Llamado del calculo del sub-total
	*/
	cal_sub_total(4);

	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
			cal_des_total('Cop_Des'); 
		    } else {
			         cal_des_importe(4,5); 
		    }
	         
         cal_tarifas(7,4);//Llamado del calculo de tarifas
 		 cal_iva_importe('Cop_Des',7,5,4); //Calculo del iva	
         cal_total()//Calculo del total
}

/**
* function para habilitar un cuadro de texto desde un checkbox
*/
function validar_text_com() 
{ 
    var total = document.getElementById('nfilas');

	if (form2.activar1.checked == true)
	{
		lectura('Cop_Des', false);
		recorre_input(5, true);		
		encerar_input(5, '');
	}
	else 
	{
		lectura('Cop_Des', true);
		recorre_input(5, false);
		document.getElementById('Cop_Des').value='';
	}
	
	cal_sub_total(4);

	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
		cal_des_total('Cop_Des'); 
	} else {
	    cal_des_importe(4,5); 
	}
    
	cal_tarifas(7,4);//Llamado del calculo de tarifas
 	cal_iva_importe('Cop_Des',7,5,4); //Calculo del iva	
    cal_total()//Calculo del total	
}

function asignar_valor(objeto)
{
	var texto = objeto.name;	
	var arreglo = texto.split(',');		
	var fila = arreglo[0].substring(6,texto.length-1);
	document.getElementById('datos['+fila+',7]').value = objeto.options[objeto.selectedIndex].text; //Selecciona el text de un combo
}//Fin del asignar_valor(objeto)

/**
* Quitar filas de las facturas de compra 
*/
function quitar_fila_com()
{
	var padre = this.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	
	/**
	* Llamado del calculo del sub-total
	*/
	cal_sub_total(4);

	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
			cal_des_total('Cop_Des'); 
		    } else {
			         cal_des_importe(4,5); 
		    }
	         
    cal_tarifas(7,4);//Llamado del calculo de tarifas
	cal_iva_importe('Cop_Des',7,5,4); //Calculo del iva	
    cal_total()//Calculo del total
}

/**
* Quitar fila en compras
*/
function quitar_fila_compra(fila)
{
	var padre = fila.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	
	var nfil=document.getElementById('nfilas');
	cal_sub_total(4);//Calculo del subtotal
	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
		cal_des_total('Cop_Des'); //Calculo del descuento total
	} else {
		cal_des_importe(4,5); //Calculo del descuento individual
	}
	cal_tarifas(7,6); //Calculo de las tarifas
	cal_iva_importe('Cop_Des',7,5,4);//Calculo del iva importe
	cal_total()//Calculo del total	
}

function quitar_fila_com()
{
	var padre = this.parentNode.parentNode;
	padre.parentNode.removeChild (padre);
	
	var nfil=document.getElementById('nfilas');
	
	/**
	* Llamado del calculo del sub-total
	*/
	cal_sub_total(4);
	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
		cal_des_total('Cop_Des'); 
	} else {   cal_des_importe(4,5);    }      
    cal_tarifas(7,4); //Llamado del calculo de tarifas
	cal_iva_importe('Cop_Des',7,5,4); //Calculo del iva	
    cal_total() //Calculo del total
}

function cal_importe_com(n1,n2,total)
{
	total.value = parseFloat(n1.value)*parseFloat(n2.value);
	if (isNaN(total.value)) 
		{ total.value=0; } 		
	/**
	* Llamado del calculo del sub-total
	*/
	cal_sub_total(4);
	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
			cal_des_total('Cop_Des'); 
		    } else {
			         cal_des_importe(4,5); 
		    }
	         
         cal_tarifas(7,4);//Llamado del calculo de tarifas OJOOOOOOOOOOOOOOO ES 7 PARA INGRESAR
 		 cal_iva_importe('Cop_Des',7,5,4); //Calculo del iva	
         cal_total()//Calculo del total
}

function validar_text_com() 
{ 
    var total = document.getElementById('nfilas');

	if (form2.activar1.checked == true)
	{
		lectura('Cop_Des', false);
		recorre_input(5, true);		
		encerar_input(5, '');
	}
	else 
	{
		lectura('Cop_Des', true);
		recorre_input(5, false);
		document.getElementById('Cop_Des').value='';
	}
	
	cal_sub_total(4);

	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
		cal_des_total('Cop_Des'); 
	} else {
	    cal_des_importe(4,5); 
	}
    
	cal_tarifas(7,4);//Llamado del calculo de tarifas //Calculo del iva	OJO PARA REGISTRA 7
 	cal_iva_importe('Cop_Des',7,5,4); //Calculo del iva	OJO PARA REGISTRA 7
    cal_total()//Calculo del total	
}

function importe_renta_com(bas, impu, importe)
{ 	
	importe.value=redondear((bas.value*impu.value)/100);
		if (isNaN(importe.value)) 
		{ 	importe.value=0; } 
	cal_sub_total(6);
}

function empty(campo)
{
 		var x=0;
		for (i = 0; i < campo.value.length; i++)
      	{   
			var c = campo.value.charAt(i);
		
		 	if (c==" ")
   		 	{	
		 		
				x++;
			}
			else{
				return true
			    break;	
			}
	   }
}

/**
* CREACION DE FACTURACION DE COMPRA 
*/
/**
* Crea nueva filas en las liquidaciones de compra 
*/
function nueva_fila_com(contenido,iva,codigoiva, dq, dq_cod, pagina)
{			
		var columna = document.createElement("td");//Cantidad
		var columna2 = document.createElement("td");//Descripcion
		var columna3 = document.createElement("td");//Precio Unitario
		var columna4 = document.createElement("td");//Importe
		var columna5 = document.createElement("td");//Descuento
		var columna6 = document.createElement("td");//Iva
		var columna7 = document.createElement("td");//Adquisión
		var columna8 = document.createElement("td");//Boton Eliminar
		var columna9 = document.createElement("td");//Codigo del Iva
		var columna10 = document.createElement("td");//codigo de los items de la factura de compra	
		
		var fila = document.createElement("tr");

		var cuerpo = document.getElementById (contenido);
	
		/**
		* Alineacion de columnas - cabecera
		*/
		columna.setAttribute('align','center');		
		columna2.setAttribute('align','center');
		columna3.setAttribute('align','center');
		columna4.setAttribute('align','center');
		columna5.setAttribute('align','center');
		columna6.setAttribute('align','center');
		columna9.setAttribute('align','right');
		columna10.setAttribute('align','right');
		
		/**
		* Aumentar en uno la cantidad de filas
		*/
		cont_filas('nfilas');
	
		/**
		* Lectura del nuevo valor
		*/
		var total = document.getElementById('nfilas');
	
		/**
		* Cuadro que muestra cantidad del producto
		*/
		var Cop_Can=create_input('datos['+ total.value +','+ 1 +']', 2, 2,'text',false, 'right')
		Cop_Can.onblur= function() {numerico(this)};	
		columna.appendChild(Cop_Can);
	
		/**
		* Cuadro que muestra la descripcion del producto en base a la busqueda hecha con el codigo
		*/
		var Ite_Lar=create_input('datos['+ total.value +','+ 2 +']', 40, 40,'text',false, 'left');
		columna2.appendChild(Ite_Lar);

		/**
		* Cuadro que muestra el precio unitario
		*/
		var Cop_Pru=create_input('datos['+ total.value +','+ 3 +']', 15,8,'text',false,'right');
		Cop_Pru.onblur= function() {numerico(this)};
		columna3.appendChild(Cop_Pru);
			
		var Cop_Imp=create_input('datos['+ total.value +','+ 4 +']', 15, 8,'text',true,'right');
		Cop_Imp.onblur= function() {numerico(this)};
		columna4.appendChild(Cop_Imp);

		/**
		* Cuadro que muestra el porcentaje de descuento invidividual	
		*/
		if (document.getElementById('activar1').checked == true)
		{ bool = true;	}
		else { bool = false }
	
		var Cop_Dec=create_input('datos['+ total.value +','+ 5 +']',2,2,'text',bool,'right');		
		Cop_Dec.onblur= function() {numerico(this)};
		columna5.appendChild(Cop_Dec);
	
		/**
		* Cuadro que muestra el porcentaje del Iva
		*/
		var Cop_Iva=create_combo('datos['+ total.value +','+ 6 +']',iva,codigoiva,'right');		
		//Cop_Iva.value = iva;
		columna6.appendChild(Cop_Iva);
						
		/**
		* Código porcenatje iva
		*/
		var Iva_Por=create_input('datos['+ total.value +','+ 7 +']', 5, 5,'hidden',false,'right');		
		//Cop_Iva.value = iva;
		columna8.appendChild(Iva_Por);

		var Cop_Int=create_input('datos['+ total.value +','+ 8 +']', 5, 5,'hidden',false,'left');
		columna10.appendChild(Cop_Int);

		var Adq=create_combo('datos['+ total.value +','+ 9 +']',dq ,dq_cod,'right');
		columna7.appendChild(Adq);

		/**
		* Asignacion del evento al cuadro de cantidad
		*/
		Cop_Can.onkeyup = function() {cal_importe_com(this, Cop_Pru, Cop_Imp); };
	
		/**
		* Asignacion del evento al cuadro de precio unitario
		*/
		Cop_Pru.onkeyup = function() {cal_importe_com(this, Cop_Can, Cop_Imp); }; 

		/**
		* Asignacion del evento al cuadro de descuento
		*/
		Cop_Dec.onkeyup = function() {cal_importe_com(Cop_Pru, Cop_Can, Cop_Imp);}; 
		
		/**
		* Asignacion del evento al cuadro de descuento
		*/
		Cop_Iva.onchange = function() {asignar_valor(this); cal_importe_com(Cop_Pru, Cop_Can, Cop_Imp); }; 
		
		/**
		* Creación del botón de eliminación
		*/
		columna9.appendChild(create_button('quitar_fila','X',quitar_fila_com));
		
		fila.appendChild(columna);
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
		/**
		* Asigna un valor inicial a la primera posicion del iva 
		*/
		Cop_Iva.options[0].value ='NULL';
		Cop_Iva.options[0].text = '-';
		/**
		* Asigna un valor inicial a la primera posicion de la adquisicion 
		*/
		Adq.options[0].value ='NULL';
		Adq.options[0].text = '-';		
}//Fin del function nueva_fila_com(contenido,iva,codigoiva, dq, dq_cod, pagina)

function chec_retencion(campo, i)
{
	if(campo.checked)
	{
	    for (var x=1; x < i; x++) 
		{
			form1.elements['Ren_Iva['+ x +']'].value="";
			form1.elements['Ren_Iva['+ x +']'].disabled=true;
		 	
		}
		form1.Ren_All.disabled=false;
	}
	else{
		for (var x=1; x < i; x++) 
		{	form1.elements['Ren_Iva['+ x +']'].disabled=false;
		}
		form1.Ren_All.value='';
		form1.Ren_All.disabled=true;
	}
}

function porcen_rete(form, i)
{
	if(!form.Chk_reten.checked)
	{
		for (var x=1; x < i; x++) 
		{
			if (requerido (form1.elements['Ren_Iva['+ x +']']) == false)
		 	{
			 form1.elements['Ren_Iva['+ x +']'].focus();
		     return false;
			 break;
		 	}
		}
	}else
	{
	  if(requerido(form1.Ren_All)==false){
		        form1.Ren_All.focus()
				return false;		   
				   }
	}
	confirmacion2(form);
}

function comparafecha(fecha1, fecha2) 
{ 
String1 = fecha1;
String2 = fecha2;
dia1=String1.substring(8,10);
mes1=String1.substring(5,7);
anyo1=String1.substring(0,4);
dia2=String2.substring(8,10);
mes2=String2.substring(5,7);
anyo2=String2.substring(0,4);

if (dia1 == "08") 
dia1 = "8";
if (dia1 == '09') 
dia1 = "9";
if (mes1 == "08") 
mes1 = "8";
if (mes1 == "09") 
mes1 = "9";
if (dia2 == "08") 
dia2 = "8";
if (dia2 == '09') 
dia2 = "9";
if (mes2 == "08") 
mes2 = "8";
if (mes2 == "09") 
mes2 = "9";

dia1=parseInt(dia1);
dia2=parseInt(dia2);
mes1=parseInt(mes1);
mes2=parseInt(mes2);
anyo1=parseInt(anyo1);
anyo2=parseInt(anyo2);

if (anyo1>anyo2)
{
return false;
}

if ((anyo1==anyo2) && (mes1>mes2))
{
return false;
}

if ((anyo1==anyo2) && (mes1==mes2) && (dia1>=dia2))
{
    console.log(dia1,' >= ',dia2);
    return false;
} 
return true;
}

/**
* Validacion del guardado de las facturas de compras 
*/
function validar_facturacion_compra(form)
{	
	var entrada1 = true; var entrada2 = true; var entrada3 = false; var entrada4 = false; 
	var entrada5 = false; var entrada6 = false; var entrada7 = false;
	var entrada8 = true;
	var filas = document.getElementById ('nfilas');	

	/* Control para omitir el escoger una cuenta contable si la empresa no lleva contabilidad  N=no lleva contabilidad   S=si lleva contabilidad*/
	var variable=document.getElementById ('confi_fact').value;
	
	if (requerido(form.Tic_Cod) != false && requerido(form.Tri_Cod) != false && requerido(form.Cop_Num) != false && requerido(form.Cop_Aut) != false 
	&& requerido(form.Cop_Fec) != false && requerido(form.Cop_Imp) != false && requerido(form.Cop_Cad) != false && requerido(form.Ciu_Cod) != false )
		{			
			/**
			* Año del periodo actual 
			*/
			Pec_Ann = document.getElementById ('Pec_Ann');	
			
			/**
			* Control para validar la fecha dentro del periodo contable 
			*/
			if ((form.Cop_Fec.value < Pec_Ann.value + "-01-01") || (form.Cop_Fec.value > Pec_Ann.value + "-12-31"))
			{
				alert("La fecha de emisión de la factura de compra debe estar dentro del periodo " + Pec_Ann.value);
				form.Cop_Fec.focus();
				return false;
			}

			if(variable=='S')
			{
				/**
				* Control para validar la fecha del comprobante dentro del periodo contable 
				*/
				if ((form.Com_Fec.value < Pec_Ann.value + "-01-01") || (form.Com_Fec.value > Pec_Ann.value + "-12-31"))
				{				
					alert("La fecha de registro de la comprobante contable debe estar dentro del periodo " + Pec_Ann.value);
					form.Com_Fec.focus();
					return false;
				}
			}
			
			if (comparafecha(form.Cop_Fec.value, form.Cop_Imp.value)){
			alert("La fecha de impresión debe ser menor a la fecha de emisión");
			return false};

			if (comparafecha(form.Cop_Cad.value,form.Cop_Imp.value)){
			alert("La fecha de caducidad no debe ser menor a la fecha de impresión");
			return false};

			if(variable=='S')
			{	
				/**
				* Control para validar que la fecha del comprobante sea mayor o igual a la fecha de emisión 
				*/
				if(comparafecha(form.Com_Fec.value, form.Cop_Fec.value))
				{	alert("La fecha de registro del comprobante contable debe ser mayor o igual a la fecha de emisión");
					form.Com_Fec.focus();
					return false;
				}
			}
						
			for (var j = 1; j <= filas.value; j++)
			{	dato = document.getElementById ('datos['+ j +','+ 1 +']');//Cantidad
				dato1 = document.getElementById ('datos['+ j +','+ 2 +']');//Descripcion
				dato3 = document.getElementById ('datos['+ j +','+ 3 +']');//Precio Unitario	
				dato4 = document.getElementById ('datos['+ j +','+ 6 +']'); //Iva
				dato5 = document.getElementById ('datos['+ j +','+ 11 +']'); //Adquisicion
				Pld_Cdc = document.getElementById ('datos['+ j +','+ 13 +']'); //Codigo de la cuenta contable
				dato6 = document.getElementById ('datos['+ j +','+ 15 +']'); //Codigo de la cuenta contable
				dato7=document.getElementById ('datos['+ j +','+ 16 +']'); //Codigo de la adquisición
				/**
				* Cantidad 
				*/
				if ((dato) && (dato.value ==""))
				{ 	alert("Falta llenar Informacion necesaria.");
					entrada2 = false;
					dato.focus();
					return false;
				}//Fin del if ((dato) && (dato.value ==""))
				/**
				* Descripción 
				*/
				if ((dato1) && (dato1.value ==""))
				{	alert("Falta llenar Informacion necesaria.");
					entrada3 = false;
					dato1.focus();
					return false;
										
				}//Fin del if ((dato) && (dato1.value ==""))
				else
				{	entrada3 = true;  	}//Fin del else if ((dato) && (dato1.value ==""))
				/**
				* Precio Unitario 
				*/
				if ((dato3) && dato3.value =="")
				{ 	alert("Falta llenar Informacion necesaria.");
					entrada4 = false;
					dato3.focus();	
					return false;
				}//Fin del if ((dato3) && dato3.value =="")
				else
				{ 
					entrada4= true;  
				}//Fin del else if ((dato3) && dato3.value =="")
				
				/**
				* Iva 
				*/
				if ((dato4) && dato4.value =='NULL')
				{ 	
					alert("Falta llenar Informacion necesaria.");
					entrada5 = false;
					dato4.focus();	
					return false;
				}//Fin del if ((dato4) && dato4.value =='NULL')
				else
				{ 
					entrada5= true;  
				}//Fin del if ((dato4) && dato4.value =='NULL')
				
				/**
				* Adquisicion 
				*/
				if ((dato5) && dato5.value =='NULL')
				{ 	
					alert("Falta llenar Informacion necesaria.");
					entrada6 = false;
					dato5.focus();	
					return false;
				}//Fin del if ((dato5) && dato5.value =='NULL')
				else
				{ 
					entrada6= true;  
				}//Fin del if ((dato5) && dato5.value =='NULL')
				/**
				* Adquisición 
				*/
				if ((dato7) && (dato7.value =='NULL'))
				{ 
					alert("Falta llenar Informacion necesaria.");
					entrada8 = false;
					dato7.focus();
					return false;
				}//Fin del if ((dato) && (dato.value ==""))
				else
				{
				    entrada8 = true;	
				}
				/**
				* Codigo de la cuenta contable 
				*/
								
				//if ((dato6) && dato6.value == 0)    Reemplazar el if
				if(variable=='N')
				{
					if ((dato6) && 0 != 0)
					{ 	alert("Falta llenar Informacion necesaria.");
						entrada7 = false;
						Pld_Cdc.focus();	
						return false;
					}//Fin del if ((dato6) && dato6.value =='')
					else
					{ 
						entrada7= true;  
					}//Fin del if ((dato6) && dato6.value =='')
				}else{
					if ((dato6) && dato6.value == 0)
					{ 	alert("Falta llenar Informacion necesaria.");
						entrada7 = false;
						Pld_Cdc.focus();	
						return false;
					}//Fin del if ((dato6) && dato6.value =='')
					else
					{ 
						entrada7= true;  
					}//Fin del if ((dato6) && dato6.value =='')
				}
			}
			For_Cod = document.getElementById ('For_Cod');
			
			/**
			* Evalua si es compra a CREDITO 
			*/
			if (For_Cod.value == 2)
			{		
			   var Fec_Ven = form.Cpp_Ven;
  			  /**
			  * Control para validad solo cuando exista en campo de fecha de vencimiento 
			  */			
			  if (Fec_Ven)
		  	  {
				/**
				* Control para el campo requerido 
				*/
				if(requerido(form.Cpp_Ven) == false)				
				{					
					form.Cpp_Ven.focus();
					return false;					
				}//Fin del if (requerido(form.Cpp_Ven) != false)

				if(requerido(form.ccpp_prove) == false)	
				{
					form.ccpp_prove.focus();
					return false;	
				}

				/**
				* Control para validar la fecha de vencimiento 
				*/
				if (trim(form.Cpp_Ven.value) !=	"")
				{	if (form.Cop_Fec.value >= form.Cpp_Ven.value)
					{
						alert("La Fecha de Vencimiento debe ser MAYOR a la Fecha de Emisión en Compras a Crédito");
						form.Cpp_Ven.focus();
						return false;											
					}//FIn del if (form.Cop_Fec.value > form.Cpp_Ven.value)
				}//Fin del if (trim(form.Cpp_Ven.value) !=	"")
			}//Fin del if (Fec_Ven)
				
			}//FIn del if (For_Cod.value == 2)
			/**
			* Control final para el envio 
			*/
			if (entrada1==true && entrada2==true && entrada3==true && entrada4==true && entrada5==true && entrada6==true && entrada7==true && entrada8==true)
			{	
				if(requerido(form.Ret_Int) != false){	
					/**
					* Control para validar cheques Solo valida cuando la forma de pago es Contado y no se genera Retención 
					*/
					if (document.getElementById('For_Cod').value == 1 && document.getElementById('Hdd_Ret').value == 'N')
					{ 
						validar_cheques_com(form);		
						//confirmacion(form);
					}else
					{
						confirmacion(form);
					}
				}
			}	
			else
			{
				alert("Es necesario ingresar al menos un Item en la Factura de Compra");
			}
		}

}//Fin del function validar_facturacion_compra(form) 

/**
* Oculta el control de cheques 
*/
function compras_cheques()
{	
	
	
	For_Cod = document.getElementById ('For_Cod');
	Hdd_Ret = document.getElementById ('Hdd_Ret');
	var config = document.getElementById ('confi_fact').value;
	if(config=='S')
	{	
		ShowHide('Tbl_Cpp_Ven'); 
		/**
		* Evalua si es compra a CONTADO y No GENERA LA RETENCION 
		*/
		if (For_Cod.value == 1 && Hdd_Ret.value == 'N')
		{		
			document.getElementById('Fie_Cheques').className = "muestra";
		}
		else //Caso contrario es CREDITO
		{
			document.getElementById('Fie_Cheques').className = "oculta";
		}
	}
}//Fin del compras_cheques()

/**
* Quitar fila compras con I.C.E 
*/
function quitar_fila_compra_ice(fila)
{
	var padre = fila.parentNode.parentNode;
	var aux=0;
	aux=document.getElementById('nfilas').value;
	document.getElementById('nfilas').value = aux-1;
	
	padre.parentNode.removeChild (padre);
	cal_sub_total(4);//Calculo del subtotal
	
	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
		cal_des_total('Cop_Des'); //Calculo del descuento total
	} else {
		cal_des_importe(4,5); //Calculo del descuento individual
	}	
	cal_tarifas(8,6); //Calculo de las tarifas
	
	cal_iva_importe('Cop_Des',7,5,4);//Calculo del iva importe
	cal_ice_importe('Cop_Des',10,5,4);
	cal_total_com_ice()//Calculo del total	
}

function validar_text_com_ice() 
{ 
    var total = document.getElementById('nfilas');

	if (form2.activar1.checked == true)
	{		
		lectura('Cop_Des', false);		
		recorre_input(5, true);		
		encerar_input(5, '');
	}
	else 
	{
		lectura('Cop_Des', true);
		recorre_input(5, false);
		document.getElementById('Cop_Des').value='';
	}
	
	cal_sub_total(4);

	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true){	
		cal_des_total('Cop_Des'); 
	} else {
	    cal_des_importe(4,5); 
	}
    
	cal_tarifas(7,4);//Llamado del calculo de tarifas //Calculo del iva	OJO PARA REGISTRA 7
 	cal_iva_importe('Cop_Des',7,5,4); //Calculo del iva	OJO PARA REGISTRA 7
	cal_ice_importe('Cop_Des',10,5,4);
    cal_total_com_ice()//Calculo del total	
}

/**
* Cálculo de importe con I.C.E 
*/
function cal_ice_importe(campo, colum_ice, colum_des, colum_imp)
{
	var filas = document.getElementById ('nfilas');	
	var valor = 0;
	var desc = 0;
	var total_ice = 0;
	var tarifa_ice=0;
	var ice_imp = 0;
	/**
	* Evalua para saber cuando debe calcular un iva total o invidual
	*/
	for (var j = 1; j <= filas.value; j++)
	{
		valor=0;
		des = document.getElementById ('datos['+ j +','+ colum_des +']');			
		importe =document.getElementById ('datos['+ j +','+ colum_imp +']');
		ice =document.getElementById ('datos['+ j +','+ colum_ice +']');			
		if ((ice) && (ice.value.length > 0))
		{
			if (isNaN(ice.value)) 
			{ 
				ice.value=0; 
			} 
			desc = parseFloat((importe.value * des.value)/100);	
			valor = parseFloat(((importe.value - desc) * ice.value)/100);				
			total_ice = total_ice + valor;
		}
	}
	
	
	//if (document.getElementById('activar1').checked == true){	
//		des = document.getElementById (campo);       
//		for (var j = 1; j <= filas.value; j++)
//		{
//			ice = document.getElementById ('datos['+ j +','+ colum_ice +']');//Aqui estaba antes 8		    
//			if ((ice) && (ice.value > 0))
//			{ 
//				//Solo entra cuando el iva sea mayor a 0
//			    tarifa_ice_imp =document.getElementById ('datos['+ j +','+ colum_imp +']'); 
//				tarifa_ice=tarifa_ice + parseFloat(tarifa_ice_imp.value);
//				ice_imp = ice.value;
//			}
//			if (isNaN(des.value)) 
//			{ 
//				des.value=0;
//			}	
//			////Descuento total de la tarifa 12
//			desc = (tarifa_ice * des.value)/100;
//			total_ice = ((tarifa_ice - desc) * ice_imp)/100;
//		}
//
//				
//	} else {		
//		for (var j = 1; j <= filas.value; j++)
//		{
//			valor=0;
//			des = document.getElementById ('datos['+ j +','+ colum_des +']');			
//			importe =document.getElementById ('datos['+ j +','+ colum_imp +']');
//			ice =document.getElementById ('datos['+ j +','+ colum_ice +']');			
//			if ((ice) && (ice.value.length > 0))
//			{
//				if (isNaN(ice.value)) 
//				{ 
//					ice.value=0; 
//				} 
//				desc = parseFloat((importe.value * des.value)/100);	
//				valor = parseFloat(((importe.value - desc) * ice.value)/100);				
//				total_ice = total_ice + valor;
//			}
//		}
//	}
	document.getElementById('t_ice').value= redondear(total_ice,2);	
}

/**
* Asignar valor con I.C.E 
*/
function asignar_valor_ice(objeto)
{
	var fila=objeto.id.substring(6,7);	
	//var dato= objeto.options[objeto.selectedIndex].value; //Selecciona el text de un combo
	document.getElementById('datos['+fila+',10]').value = objeto.options[objeto.selectedIndex].value; //Selecciona el text de un combo
}

/**
* Cálculo de total compra con I.C.E 
*/
function cal_total_com_ice()
{
	var total = 0
 
	sub_total = document.getElementById('t_subtotal');
	iva = document.getElementById('t_iva');	
	des = document.getElementById('t_descuento');
	ice= document.getElementById('t_ice');
	total = ((parseFloat(sub_total.value) - parseFloat(des.value)) + parseFloat(iva.value) + parseFloat(ice.value) ); 
	document.getElementById('t_rubros').value = redondear(total,2);	
        if(total>=1000)
	{ 	
	     document.getElementById('pagoSri').style.display='block'; 
             document.getElementById('hdd_TipoSri').value="0";
             document.getElementById('TipoPag').value="";
             
	}
        else{
	    document.getElementById('pagoSri').style.display='none';
            document.getElementById('hdd_TipoSri').value="1";
            
	}
}

function cal_importe_ice_com(n1,n2,total)
{	
	total.value = redondear((parseFloat(n1.value)*parseFloat(n2.value)),2);
	if (isNaN(total.value))
	{ total.value=0; }
	/**
	* Llamado del calculo del sub-total
	*/
	cal_sub_total(4);
	
	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if(document.getElementById('activar1').checked == true){
	cal_des_total('Cop_Des'); 
	}else{
		cal_des_importe(4,5);
	}
	
    cal_tarifas(7,4);//Llamado del calculo de tarifas OJO ES 7 PARA INGRESAR
 	cal_iva_importe('Cop_Des',7,5,4); //Calculo del iva
	cal_ice_importe('Cop_Des',10,5,4);
    cal_total_com_ice();//Calculo del total
}

/**
* Asiganr adquision 
*/
function asignar_valor_adquisicion(objeto)
{
	var fila=objeto.id.substring(6,8);
	document.getElementById('datos['+fila+',10]').value = objeto.options[objeto.selectedIndex].text; //Selecciona el text de un combo
}

/**
* Asignar adquisición 
*/
function asignar_valor_adq(objeto)
{
	var fila=objeto.id.substring(6,7);
	document.getElementById('datos['+fila+',12]').value = objeto.options[objeto.selectedIndex].text; //Selecciona el text de un combo
	
}

/**
* Funcion que da el enfoque a los cuadroa de texto de comprobante, renta e iva 
*/
function lostfocus_compras(lostfocus, setfocus, bool)
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
		var input = document.getElementById(lostfocus+'['+ i +','+ 13 +']');
		var input_ren = document.getElementById(lostfocus+'['+ i +','+ 17 +']');		
		var input_iva = document.getElementById(lostfocus+'['+ i +','+ 18 +']');				

		if (input)
		{
			/**
			* Quita el color de enfoque de la cuenta 
			*/
			enfoque(input, false) 									
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
}//Fin del lostfocus_compras(lostfocus, setfocus, bool)

/**
* Checkear comprobante de retención  
*/
function Chk_Form_Ren(campo){
	if(campo.checked)
	{
		form1.Ren_Cod.value='';
		form1.Ren_Cod.disabled=true;
	}else{
		form1.Ren_Cod.disabled=false;
	}
}

/**
* Validar retencion  
*/
function Val_Ret_Con(form)
{
	if (requerido (form.ini) != false & requerido (form.fin) != false)
	{
		if(form.bcheck.checked)
		{   
			if(!form.Chk_For.checked) {
			if (requerido(form.Ren_Cod)!= false){  form.submit(); }  
				} else { form.submit(); }
        }
        else{
        form.submit();
			}
	}
}

/**
* Habilita para buscar porcentajes en la retención 
*/
function Busavpor(campo)
{   
if(campo.checked)
	{
		xDisplay('busren', 'none');
        xDisplay('buspor', 'block');

form1.Ren_Por.value="";
        form1.Ren_Cod.disabled=false;
        form1.Chk_For.checked=false;		
	}else
	{
 		form1.Ren_Cod.value="";
		xDisplay('busren', 'block');
        xDisplay('buspor', 'none');

	}
}

/**
* funcion para limpiar los valores de renta del GRID
*/
function limpiar_renta_grid()
{
		/**
		* asigno el valor del código de rentencion 
		*/
		document.getElementById('datos['+  document.getElementById('nfilas').value +','+ 17 +']').value="";
		/**
		* asigno el valor del código interno de la retención 
		*/
		document.getElementById('datos['+  document.getElementById('nfilas').value +','+ 19 +']').value="";
}//Fin del limpiar_renta_grid()

/**
* funcion para limpiar los valores de I.V.A. del GRID
*/
function limpiar_iva_grid()
{
		/**
		* asigno el valor del código de rentencion 
		*/
		document.getElementById('datos['+  document.getElementById('nfilas').value +','+ 18 +']').value="";
		/**
		* asigno el valor del código interno de la retención 
		*/
		document.getElementById('datos['+  document.getElementById('nfilas').value +','+ 20 +']').value="";
}//Fin del limpiar_iva_grid()

/**
* funcion para mostrar el componente que busca la cuenta 
*/
function busca_cuenta_btn(form, campo)
{   			
	/**
	* Asigna el valor del la fila 
	*/
	var indice = campo.name.split("[");
	var indice = indice[1].split("]")	
	/**
	* Asigna el indice del cuadro de texto seleccionado 
	*/
	document.getElementById('Hdd_Fila').value = indice[0];		
	document.getElementById('Tbl_Rentas').className = 'oculta';
	document.getElementById('Tbl_Cuentas').className = 'muestra'; 
	/**
	* Variables que reciben los nombres de los campos a los cuales se les asigna las cuentas 
	*/
	document.getElementById('Hdd_Pld_Cod').value = "datos["+indice[0]+",15]";
	document.getElementById('Hdd_Pld_Cdc').value = "datos["+indice[0]+",13]";
	document.getElementById('Hdd_Pld_Des').value = "datos["+indice[0]+",14]";
	document.getElementById('buscta').focus();
	/**
	* llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
	*/
	xDisplay('Tbl_Cuencont', 'none');
	/**
	* Color de enfoque de la cuenta 
	*/
	lostfocus_compras('datos', document.getElementById('datos["+indice[0]+",13]'), true) 
}//FIn del busca_cuenta_btn(form, campo)	 

/**
* funcion para mostra el componente que muestra la busqueda de ice 
*/
function busca_ice_btn(form, campo)
{   			
	/**
	* Asigna el valor del la fila 
	*/
	var indicer = campo.name.split("[");
	var indicer = indicer[1].split("]");
	   
	if(document.getElementById("datos["+ indicer[0] +",9]").value=='')
	{	
		document.getElementById('Tbl_Ice').className = 'muestra';
		document.getElementById('busrtaIce').value="";				
		document.getElementById('busrtaIce').focus();
		document.getElementById('hdd_Ice_Por').value="datos["+ indicer[0] +",10]";
		document.getElementById('hdd_Ice_Sri').value="datos["+ indicer[0] +",9]";
		document.getElementById('hdd_Ice_Int').value="datos["+ indicer[0] +",24]";
		/**
		* Color de enfoque de la cuenta 
		*/
		lostfocus_compras('datos', document.getElementById('datos["+ indicer[0] +",9]'), true);		
		multiple_capa('',600,300,'cont_fon_ice','cont_cua_ice','busqueda de los Rubros Ice','cont_cua_det_ice');	
		/**
		* Llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
		*/
		xDisplay('tbl_resultados', 'none');					
	}else{
		document.getElementById("datos["+ indicer[0] +",9]").value='';
		document.getElementById("datos["+ indicer[0] +",10]").value='';
		document.getElementById("datos["+ indicer[0] +",24]").value='';
		validar_text_com_ice();
		cal_importe_ice_com(document.getElementById('datos["+ indicer[0] +",3]'), document.getElementById('datos["+ indicer[0] +",1]'), document.getElementById('datos["+ indicer[0] +",4]'));		
		/**
		* Color de enfoque de la cuenta 
		*/
		lostfocus_compras('datos', document.getElementById('datos["+ indicer[0] +",17]'), false);
		
	}		
}//Fin del busca_ice_btn(form, campo)

/**
* funcion para mostra el componente que muestra la busqueda de renta 
*/
function busca_renta_btn(form, campo)
{   			
	/**
	* Asigna el valor del la fila 
	*/
	var indicer = campo.name.split("[");
	var indicer = indicer[1].split("]")	
	document.getElementById('Hdd_Tip_Rta').value='R';
	document.getElementById('Tbl_Cuentas').className = 'oculta'; 
	document.getElementById('Tbl_Rentas').className = 'muestra'; 
	document.getElementById('busrta').value="";
	document.getElementById('busrta').focus();
	/**
	* Color de enfoque de la cuenta 
	*/
	lostfocus_compras('datos', document.getElementById('datos["+ indicer[0] +",17]'), true);	
	
	/**
	* asigno el valor de fila 
	*/
	document.getElementById('Hdd_Txt_Ide').value=indicer[0];
	/**
	* variables que reciben los nombres  
	*/
	document.getElementById('Hdd_Ren_Con').value="datos["+ indicer[0] +",17]";
	document.getElementById('Hdd_Ren_Ide').value="datos["+ indicer[0] +",19]";
	document.getElementById('Hdd_Ren_Por').value="datos["+ indicer[0] +",21]";
	/**
	* llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
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
	document.getElementById('Tbl_Cuentas').className = 'oculta'; 
	document.getElementById('Tbl_Rentas').className = 'oculta'; 			
	/**
	* Limpiar datos I.V.A. 
	*/
	document.getElementById("datos["+ indicerm[0] +",17]").value = ''; 
	document.getElementById("datos["+ indicerm[0] +",19]").value = ''; 
	document.getElementById("datos["+ indicerm[0] +",21]").value = ''; 
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
		document.getElementById('Tbl_Cuentas').className = 'oculta'; 
		document.getElementById('Tbl_Rentas').className = 'muestra'; 
		document.getElementById('busrta').value="";
		document.getElementById('busrta').focus();
		/**
		* asigno el valor de fila 
		*/
		document.getElementById('Hdd_Txt_Ide').value=indicei[0];
		/**
		* Color de enfoque de la cuenta 
		*/
		lostfocus_compras('datos', document.getElementById('datos['+ indicei[0] +','+ 18 +']'), true)			
		/**
		* variables que reciben los nombres  
		*/
		document.getElementById('Hdd_Ren_Con').value="datos["+ indicei[0] +",18]";
		document.getElementById('Hdd_Ren_Ide').value="datos["+ indicei[0] +",20]";
		document.getElementById('Hdd_Ren_Por').value="datos["+ indicei[0] +",22]";
		/**
		* llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
		*/
		xDisplay('Tbl_Rencon', 'none');
}//Fin del busca_iva_btn(form, campo)	

function busca_iva_quita_btn(form, campo)
{		
	/**
	* Asigna el valor del la fila 
	*/
	var indiceim = campo.name.split("[");
	var indiceim = indiceim[1].split("]")	
	document.getElementById('Tbl_Cuentas').className = 'oculta'; 
	document.getElementById('Tbl_Rentas').className = 'oculta'; 			
	/**
	* Limpiar datos I.V.A. 
	*/
	document.getElementById("datos["+ indiceim[0] +",18]").value = ''; 
	document.getElementById("datos["+ indiceim[0] +",20]").value = ''; 
	document.getElementById("datos["+ indiceim[0] +",22]").value = ''; 
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
* función limpia campos cuando seleccione otros tipos de adquisición 
*/
function limpia_adq_cbm(form, campo,campo1,campo2,campo3,campo4,campo5,campo6) 
{		asignar_valor_adq(campo); 
		valor_renta_compra(); 
		campo1.value='';  
		campo2.value=''; 
		campo3.value='';  
		campo4.value=''; 
		campo5.value=''; 
		campo6.value='';	 
		document.getElementById('Tbl_Rentas').className = 'oculta';   
		valor_renta_compra();    
}//Fin del limpia_adq_cbm(form, campo,campo1,campo2,campo3,campo4,campo5,campo6) 

/**
* funcion limpia campos cuando seleccione otro porcentaje de I.V.A. 
*/
function limpia_iva_cbm(campo,Cop_Pru,Cop_Can,Cop_Imp, btn_ivamas,btn_ivamenos,Int_Rei,Int_Riv,Iva_Con)
{	asignar_valor(campo); 
	valor_renta_compra(); 
	(Cop_Pru, Cop_Can, Cop_Imp); 
	if(campo.value==5){
			btn_ivamas.disabled=true;
			btn_ivamenos.disabled=true;
			Int_Rei.value='';
			Int_Riv.value='';
			Iva_Con.value='';	
	}else{ 
			btn_ivamas.disabled=false;
			btn_ivamenos.disabled=false;
	}
}//Fin del limpia_iva_cbm(campo,Cop_Pru,Cop_Can,Cop_Imp, btn_ivamas,btn_ivamenos,Int_Rei,Int_Riv,Iva_Con)

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
	var desctotal=document.getElementById('Cop_Des').value;
	
	for (j=1; j<=total.value; j++) /* inicio for (j=1; j<=total.value; j++)  */
	{	
	var dato = document.getElementById ('datos['+ j +','+ 4 +']');
	if (dato)
	{ 		
			var importe = document.getElementById ('datos['+ j +','+ 4 +']').value;
			var iva  = document.getElementById ('datos['+ j +','+ 7 +']').value;
			var descindiv = document.getElementById ('datos['+ j +','+ 5 +']').value;
			var renpor= document.getElementById ('datos['+ j +','+ 21 +']').value;
			var reniva=document.getElementById ('datos['+ j +','+ 22 +']').value; 
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
			//renta_grav=renta_grav + redondear((renta_ret * renpor)/100,2);
			renta_grav=renta_grav + (renta_ret * renpor)/100;
			/**
			* Calculo del I.V.A. 
			*/
			if(iva>0)
			{
				iva_renta=((renta_ret*iva)/100);
				//iva_suma=iva_suma + redondear((iva_renta*reniva)/100,2);
				iva_suma=iva_suma + (iva_renta*reniva)/100;
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
	var val_pagar=redondear(parseFloat(document.getElementById('t_rubros').value)-parseFloat(document.getElementById('Riv_Tot').value));
	if(typeof($('#Ret_Asu'))!=='undefined' &&$('#Ret_Asu').is(':checked') )
            val_pagar=redondear(parseFloat(document.getElementById('t_rubros').value));
        
	document.getElementById('Val_Pcc').value=val_pagar;
        
        if($('#For_Cod').val()*1===1&&$('#nfilas_ch').val()*1===1){
            $('#datos_ch\\[1\\,5\\]').val(val_pagar);            
            cal_total_cheques(5, 'nfilas_ch', 'datos_ch');
        }  
	
	
}//Fin del valor_renta_compra()

/**
* Quita una fila de la compra 
*/
function quitar_fila_compr_x(boton)
{	
	var padre = boton.parentNode.parentNode;
	var aux=0;
	aux=document.getElementById('nfilas').value;
	//document.getElementById('nfilas').value = aux-1;
	padre.parentNode.removeChild (padre);
	/*recalcula el ice*/
	cal_ice_importe('Cop_Des',10,5,4);
	/**
	* Llamado del calculo del sub-total
	*/
	cal_sub_total(4);

	/**
	* Evalua para saber cuando debe calcular un descuento total o general
	*/
	if (document.getElementById('activar1').checked == true)
	{
		cal_des_total('Cop_Des'); 
    } 
	else 
	{
        cal_des_importe(4,5); 
    }
         
    cal_tarifas(7,4);//Llamado del calculo de tarifas
	cal_iva_importe('Cop_Des',7,5,4); //Calculo del iva
    cal_total()//Calculo del total
}//Fin del quitar_fila_compr_x(boton)

/**
* funcion para validar la modificación de los clientes 
*/
function val_mod_compra(form)
{
var filas = document.getElementById ('nfilas');
var entrada=false;
if (requerido(document.getElementById ('Tic_Cod')) != false && requerido(document.getElementById ('Tri_Cod')) != false && requerido(document.getElementById ('Cop_Num')) != false && requerido(document.getElementById ('Cop_Aut')) != false && requerido(document.getElementById ('Cop_Fec')) != false && requerido(document.getElementById ('Cop_Imp')) != false && requerido(document.getElementById ('Cop_Cad')) != false && requerido(document.getElementById('Ciu_Cod')) != false )
{
/**
* Año del periodo actual 
*/
Pec_Ann = document.getElementById ('Pec_Ann');
/**
* Control para validar la fecha dentro del periodo contable 
*/
if ((form.Cop_Fec.value < Pec_Ann.value + "-01-01") || (form.Cop_Fec.value > Pec_Ann.value + "-12-31"))
{alert("La fecha de emisión de la factura de compra debe estar dentro del periodo " + Pec_Ann.value);
form.Cop_Fec.focus();
return false;
}
/**
* Control para validar la fecha del comprobante dentro del periodo contable 
*/
if ((form.Com_Fec.value < Pec_Ann.value + "-01-01") || (form.Com_Fec.value > Pec_Ann.value + "-12-31"))
{alert("La fecha de registro de la comprobante contable debe estar dentro del periodo " + Pec_Ann.value);
form.Com_Fec.focus();
return false;
}
if (comparafecha(form.Cop_Fec.value, form.Cop_Imp.value))
{alert("La fecha de impresión debe ser menor a la fecha de emisión");
return false;
}
if (comparafecha(form.Cop_Cad.value,form.Cop_Imp.value))
{
alert("La fecha de caducidad no debe ser menor a la fecha de impresión");
return false;
}
for (var j = 1; j <= filas.value; j++)
{
dato1 = document.getElementById ('datos['+ j +','+ 2 +']');//Descripcion
/**
* Descripción 
*/
if ((dato1) && (dato1.value ==""))
{
alert("Falta llenar Informacion necesaria.");

entrada = false;

dato1.focus();

return false;
}//Fin del if ((dato) && (dato1.value ==""))
else
{
entrada = true;  }//Fin del else if ((dato) && (dato1.value ==""))
}//FIn del if (For_Cod.value == 2)

/**
* Control final para el envio 
*/
if(entrada==true)
{ if(requerido(form.Ret_Int) != false)
{
	/**
	* Control para validar cheques Solo valida cuando la forma de pago es Contado y no se genera Retención 
	*/
	confirmacion(form);
}
}
}
}

/**
* function para obtner el saldo que queda de las facturas pagadas
*/
function saldos(cant, campos, saldo,abono)
{
 var suma=0;
 var sumabono=0;
 var total=0;
 for (var i=1;i<=cant;i++) 
  {
	  valorabono = document.getElementById(abono+'['+ i +']');
	  valor = document.getElementById(campos+'['+ i +']');
	  if (valor)
	  {
		  if (parseFloat(valor.value) > 0)
		  {
	   		suma=suma + parseFloat(valor.value);
		  }
	  }//Fin del if (valor)
  
	 if (valorabono)
	 {
	 	 if (parseFloat(valorabono.value) > 0)
	     {
		   sumabono=sumabono + parseFloat(valorabono.value);
		 }
	 }//FIn del if (valorabono)
  }//Fin del for (var i=1;i<=cant;i++)

	total=suma - sumabono;
   document.getElementById(saldo).value =redondear(total,2); 
}//Fin del saldos(cant, campos, saldo,abono)

/**
* function para activar los pagos automaticamente
*/
function activar_pagos(cant, campos, abono,check)
{
  for (var i=1;i<=cant;i++) 
   {
	   valor = document.getElementById(campos+'['+ i +']');
	   if (valor)
	   {
		   if (parseFloat(valor.value) > 0)
		   {
			    if (pasar.Todos.checked == true)
			    {  
			      document.getElementById(abono+'['+ i +']').value = redondear(valor.value,2);
			    }
			    else
			    {
			      document.getElementById(abono+'['+ i +']').value ="";
			    }//FIn del else if (pasar.Todos.checked == true)
		   }//FIn del if (parseFloat(valor.value) > 0)
	   }//FIn del if (valor)
   }//Fin del for (var i=1;i<=cant;i++)
}//FIn del activar_pagos(cant, campos, abono,check)

/**
* function pra obtner la suma de procentaje del componente ajax_con_copstos
*/
function suma_porcentaje(cant, campos, total)
{
  //alert(cant);
  var suma=0;
	 /**
	 * recoore el vector de campo de textos para poder sumar el porcentaje
	 */
	 for (var i=1;i<=cant;i++) 
	  {
		 valor = document.getElementById(campos+'['+ i +']');
		 //alert(campos);
		 /**
		 * prgeunta si valor existe
		 */
		 if (valor)
		 {
			if (parseFloat(valor.value) > 0)
			{
				suma=suma + parseFloat(valor.value);
			}// fin del if (parseFloat(valor.value) > 0)
		 }// fin de if (valor)
	  }// fin del for(var i=1;i<=cant;i++) 
		document.getElementById(total).value =redondear(suma,2); 
}// fin dela funcion function suma_porc

/**
* Agrega dinamicamente nuevas filas, segun el tipo de sdistribucion de los costos y gastos 
*/
function cargar_filas_distri(c_contenido, iva_cod, iva_por, ice_cod, ice_por, ad_cod, ad_por,url,campo,monto)
{
	var v_prc;
	var c = document.getElementById('cont');
	document.getElementById('nfilas').value = 0;
	var valor_monto = document.getElementById(monto);
	var valor_total = document.getElementById('suma_porc');
	if ((requerido(valor_monto)!= false))
	{
		if ((numerico(valor_monto)!= false))	
		{
			if((valor_total.value==100.00) )
			{
				for(var j = 1; j <= c.value; j++)
				{
					v_prc=0;
					
					var porcent = document.getElementById(campo+'['+ j +']');
					/**
					* objeto que almacena el contenido de Codigo de la cuenta segun el plan de cuentas
					*/
					var cuenta = document.getElementById('hdd_cnt['+ j +']');
					/**
					* objeto que almacena el contenido de Descripción de la cuenta)
					*/
					var descrip = document.getElementById('hdd_Pld['+ j +']');
					/**
					* objeto que almacena el contenido de Pld_Cod(Codigo de la cuenta)
					*/
					var codigo = document.getElementById('Pld_Cod['+ j +']');
					/**
					* objeto que almacena el monto que sera dividido segun la ditribucion de los costos
					*/
					var valor = document.getElementById(monto);
					dato = document.getElementById('datos['+ j +','+ 1 +']');
					if (dato)
					{
						quitar_fila_compr_x(dato);
					}
					/**
					* llamado al al función que agrega nueva fila
					*/
					nueva_fila_com_ice(c_contenido, iva_cod, iva_por, ice_cod, ice_por, ad_cod, ad_por, url, '', '')
					/**
					* asignación de los objetos para realizar respectivos calculos
					*/
					/**
					* cantidad
					*/
					Cop_Can = document.getElementById('datos['+ j +','+ 1 +']');
					/**
					* Precio unitario
					*/
					Cop_Pru = document.getElementById('datos['+ j +','+ 3 +']');
					/**
					* Importe
					*/
					Cop_Imp = document.getElementById('datos['+ j +','+ 4 +']');
					/**
					* Codigo de la cuenta segun plan de cuentas
					*/
					Pld_Cdc = document.getElementById('datos['+ j +','+ 13 +']');
					/**
					* Descripción de la cuenta
					*/
					Pld_Des = document.getElementById('datos['+ j +','+ 14 +']');
					/**
					* codigo de la cuenta
					*/
					Pld_Cod = document.getElementById('datos['+ j +','+ 15 +']');
					/**
					* Calculo del porcentaje de la cantidad asigando ene l monto
					*/
					v_prc= (parseFloat(porcent.value) * parseFloat(valor.value))/100;
					Cop_Pru.value = redondear(v_prc,2);
					Cop_Can.value = 1;
					Pld_Cdc.value = cuenta.value;
					Pld_Des.value= descrip.value;
					Pld_Cod.value= codigo.value;
					cal_importe_ice_com(Cop_Pru, Cop_Can, Cop_Imp); 
					valor_renta_compra();
				}//fin del for(var j = 1; j <= c.value; j++)
			}// fin del if(valor_total==100)
			else
			{
				alert("<< La suma total de la columna porcentaje debe ser igual al  100 % por favor verifique >>");
			}
		} // fin if ((numerico(valor_monto) != false))
	}// fin if ((requerido(valor_monto) != false))	
}//Fin del cargar_filas_distri(c_contenido, iva_cod, iva_por, ice_cod, ice_por, ad_cod, ad_por,url,campo,monto)

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
			renta = document.getElementById('datos['+ j +','+ 17 +']'); /* Ren_Con */
			if (renta)
			{	
				if(document.getElementById('Hdd_Tip_Rta').value=='R')  
				{
					document.getElementById('datos['+ j +','+ 17 +']').value=ren_sri; /* Ren_Con */
 					document.getElementById('datos['+ j +','+ 19 +']').value=ren_cod; /* Código interno RENTA */
					document.getElementById('datos['+ j +','+ 21 +']').value=ren_por; /* Porcentaje retención  */
					valor_renta_compra();
				}else
		  		{
					document.getElementById('datos['+ j +','+ 18 +']').value=ren_sri; /* Iva_Con */
					document.getElementById('datos['+ j +','+ 20 +']').value=ren_cod;  /* Código interno IVA */
					document.getElementById('datos['+ j +','+ 22 +']').value=ren_por; /*Porcentaje retención IVA */
					valor_renta_compra();
			   	}
		    }
       }
  }
}//Fin del todo_check_renta(ren_cod, ren_sri, ren_por)

/**
* funcion que asigna los valores y calculos en modificar 
*/
function asi_iva_modi(campo, fila)
{ asignar_valor(campo);  
 cal_importe_ice_com(document.getElementById('datos['+ fila +','+ 3 +']'), document.getElementById('datos['+ fila +','+ 1 +']') , document.getElementById('datos['+ fila +','+ 4 +']')); 
 valor_renta_compra();
 if(campo.value==5){
  document.getElementById('Btn_IvaMas['+ fila +']').disabled=true;
  document.getElementById('Btn_IvaMenos['+ fila +']').disabled=true; 
 }else{ 
  document.getElementById('Btn_IvaMas['+ fila +']').disabled=false;
  document.getElementById('Btn_IvaMenos['+ fila +']').disabled=false; 
 } 
}//FIn del asi_iva_modi(campo, fila)

/**
* Permite mostrar y ocultar el constenido de botones inferiores de la factura de compra 
*/
function botones_opcion_compra(boton, tablas)
{
	var Obj_Tablas = new String (tablas);
	parametros = Obj_Tablas.split('*');

	switch(boton){
     case 1 : /* Boton deudas */
        document.getElementById(parametros[0]).className = "muestra";
        document.getElementById(parametros[1]).className = "oculta";
		document.getElementById(parametros[2]).className = "oculta";
        break;
     case 2 : /* Boton buscar */
        document.getElementById(parametros[0]).className = "oculta";
        document.getElementById(parametros[1]).className = "muestra";
		document.getElementById(parametros[2]).className = "oculta";		
        break;
     case 3 : /* Boton Anticipos */
        document.getElementById(parametros[0]).className = "oculta";
        document.getElementById(parametros[1]).className = "oculta";
		document.getElementById(parametros[2]).className = "muestra";
        break;		
  }//Fin del switch(boton)
}//Fin del botones_opcion(boton, tablas)

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

function busca_cuenta_btn(form, campo)
{   			
				/**
				* VENTANA TIPO MODAL 
				*/
				multiple_capa('',600,300,'cont_fon_cta','cont_cua_cta','busqueda de cuenta','cont_cua_cta_titu');
				/**
				* Asigna el valor del la fila 
				*/
				var indice = campo.name.split("[");
				var indice = indice[1].split("]")	
				/**
				* Asigna el indice del cuadro de texto seleccionado 
				*/
				document.getElementById('Hdd_Fila').value = indice[0];		
				document.getElementById('Tbl_Rentas').className = 'oculta';
				document.getElementById('Tbl_Cuentas').className = 'muestra'; 
				/**
				* Variables que reciben los nombres de los campos a los cuales se les asigna las cuentas 
				*/
				document.getElementById('Hdd_Pld_Cod').value = "datos["+indice[0]+",15]";
				document.getElementById('Hdd_Pld_Cdc').value = "datos["+indice[0]+",13]";
				document.getElementById('Hdd_Pld_Des').value = "datos["+indice[0]+",14]";
				document.getElementById('buscta').focus();
				/**
				* llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
				*/
				xDisplay('Tbl_Cuencont', 'none');
				/**
				* Color de enfoque de la cuenta 
				*/
				lostfocus_compras('datos', document.getElementById('datos["+indice[0]+",13]'), true) 
}//FIn del busca_cuenta_btn(form, campo)	 

function busca_iva_btn(form, campo)
{ 
	multiple_capa('',600,300,'cont_fon_iva','cont_cua_iva','busqueda de retención','cont_cua_iva_titu');
	/**
	* Asigna el valor del la fila 
	*/
	var indicei = campo.name.split("[");
	var indicei = indicei[1].split("]")	
	document.getElementById('Hdd_Tip_Rta').value='I'; 
	document.getElementById('Tbl_Cuentas').className = 'oculta'; 
	document.getElementById('Tbl_Rentas').className = 'muestra'; 
	document.getElementById('busrta').value="";
	document.getElementById('busrta').focus();
	/**
	* asigno el valor de fila 
	*/
	document.getElementById('Hdd_Txt_Ide').value=indicei[0];
	/**
	* Color de enfoque de la cuenta 
	*/
	lostfocus_compras('datos', document.getElementById('datos['+ indicei[0] +','+ 18 +']'), true)			
	/**
	* variables que reciben los nombres  
	*/
	document.getElementById('Hdd_Ren_Con').value="datos["+ indicei[0] +",18]";
	document.getElementById('Hdd_Ren_Ide').value="datos["+ indicei[0] +",20]";
	document.getElementById('Hdd_Ren_Por').value="datos["+ indicei[0] +",22]";
	/**
	* llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
	*/
	xDisplay('Tbl_Rencon', 'none');
}//Fin del busca_iva_btn(form, campo)	

function busca_renta_btn(form, campo)
{   			
	/**
	* Asigna el valor del la fila 
	*/
	multiple_capa('',600,300,'cont_fon_iva','cont_cua_iva','busqueda de retención','cont_cua_iva_titu');

	var indicer = campo.name.split("[");
	var indicer = indicer[1].split("]")	
	document.getElementById('Hdd_Tip_Rta').value='R';
	document.getElementById('Tbl_Cuentas').className = 'oculta'; 
	document.getElementById('Tbl_Rentas').className = 'muestra'; 
	document.getElementById('busrta').value="";
	document.getElementById('busrta').focus();
	/**
	* Color de enfoque de la cuenta 
	*/
	lostfocus_compras('datos', document.getElementById('datos["+ indicer[0] +",17]'), true);	
	/**
	* asigno el valor de fila 
	*/
	document.getElementById('Hdd_Txt_Ide').value=indicer[0];
	/**
	* variables que reciben los nombres  
	*/
	document.getElementById('Hdd_Ren_Con').value="datos["+ indicer[0] +",17]";
	document.getElementById('Hdd_Ren_Ide').value="datos["+ indicer[0] +",19]";
	document.getElementById('Hdd_Ren_Por').value="datos["+ indicer[0] +",21]";
	/**
	* llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
	*/
	xDisplay('Tbl_Rencon', 'none');
}//Fin del busca_renta_btn(form, campo)	

/**
* Funcion para agregar filas en la compra
*/
function nueva_fila_com_ice(contenido,iva,codigoiva,ice1,ice_por1,dq_des,dq_cod, pagina, cuenta, descripcion,codigopro)
{                           
		//alert(arrIce);
				if(typeof iva_select !=='undefined' && iva_select!==null && iva*1!==0){
                    iva=iva_select['Iva_Por'];
                    codigoiva=iva_select['Iva_Cod'];
                }
		var columna1 = document.createElement("td");//Cantidad
		var columna2 = document.createElement("td");//Descripcion de la compra
		var columna3 = document.createElement("td");//Precio Unitario
		var columna4 = document.createElement("td");//Importe
		var columna5 = document.createElement("td");//Descuento
		var columna6 = document.createElement("td");//I.V.A.
		var columna7 = document.createElement("td");//I.C.E
		var columna8 = document.createElement("td");//Boton ICE
		var columna9 = document.createElement("td");//Adquisión
		var columna10 = document.createElement("td");//Codigo cuenta
		var columna11 = document.createElement("td");//Boton buscador de cuenta contable
		var columna12 = document.createElement("td"); //Descripción de la cuenta contable
		var columna13 = document.createElement("td"); //Renta
		var columna14 = document.createElement("td"); //Boton busqueda renta
		var columna15 = document.createElement("td"); //Boton borrar renta
		var columna16 = document.createElement("td"); //I.V.A
		var columna17 = document.createElement("td"); //Boton busqueda I.V.A.
		var columna18 = document.createElement("td"); //Boton borrar I.V.A.
		var columna19 = document.createElement("td"); //Boton borrar cuenta
	
		var fila = document.createElement("tr");
		var cuerpo = document.getElementById (contenido);
		/**
		* Alineacion de columnas - cabecera
		*/
		columna1.setAttribute('align','center');		
		columna2.setAttribute('align','center');
		columna3.setAttribute('align','center');
		columna4.setAttribute('align','center');
		columna5.setAttribute('align','center');
		columna6.setAttribute('align','center'); 
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
		var Cop_Can=create_input('datos['+ total.value +','+ 1 +']', 3, 10,'text',false, 'right')
		Cop_Can.onblur= function() {numerico(this); };	
		columna1.appendChild(Cop_Can);
		/**
		* Codigo del producto
		*/	
		var Pro_Cod=create_input('datos['+ total.value +','+ 23 +']', 5, 5,'hidden',true,'left');
		Pro_Cod.value = codigopro;
		columna19.appendChild(Pro_Cod);
		/**
		* Cuadro que muestra la descripcion del producto en base a la busqueda hecha con el codigo - Antes 30
		*/
		var Ite_Lar=create_input('datos['+ total.value +','+ 2 +']', 25, 35,'text',false, 'left');
		Ite_Lar.value=descripcion;
		columna2.appendChild(Ite_Lar);
		/**
		* Cuadro que muestra el precio unitario
		*/
		var Cop_Pru=create_input('datos['+ total.value +','+ 3 +']', 5,10,'text',false,'right');
		Cop_Pru.onblur= function() {numerico(this); valor_renta_compra();};
		columna3.appendChild(Cop_Pru);
		var Cop_Imp=create_input('datos['+ total.value +','+ 4 +']', 8, 10,'text',true,'right');
		Cop_Imp.onblur= function() {numerico(this) };
		columna4.appendChild(Cop_Imp);
		/**
		* Cuadro que muestra el porcentaje de descuento invidividual
		*/	
		if (document.getElementById('activar1').checked == true)
		{ bool = true;	}
		else { bool = false }
		var Cop_Dec=create_input('datos['+ total.value +','+ 5 +']',2,2,'text',bool,'right');		
		Cop_Dec.onblur= function() {numerico(this); valor_renta_compra();};
		columna5.appendChild(Cop_Dec);
		/**
		* Cuadro que muestra el porcentaje del Iva
		*/
		//var Cop_Iva=create_combo('datos['+ total.value +','+ 6 +']',iva,codigoiva,'right');		
		var Cop_Iva=create_input('datos['+ total.value +','+ 6 +']', 1, 5,'hidden',true,'right');
		Cop_Iva.value = codigoiva;
		//Cop_Iva.value = iva;
		
		/**
		* Caja de texto para mostrar el porcentaje del iva (solo de lectura) 
		*/
		var ver_iva=create_input('iva', 1, 5,'text',true,'right');
		ver_iva.value = iva;
		
		columna6.appendChild(Cop_Iva);
		columna6.appendChild(ver_iva);
		/**
		* Cuadro que muestra el porcentaje del Iva
		*/	
				
		var Cop_Ice=create_input('datos['+ total.value +','+ 9 +']', 1, 5,'text',true,'center');							 		
		/**
		* Asignacion del evento al cuadro de I.C.E.
		*/		
		//Cop_Ice.onchange = function() {asignar_valor_ice(this); cal_importe_ice_com(Cop_Pru, Cop_Can, Cop_Imp); };
		/**
		* Código I.C.E 
		*/
		var Ice_Int=create_input('datos['+ total.value +','+ 24 +']', 5, 5,'hidden',false,'left');
		/**
		* Cop_Iva.value = iva;
		*/
		columna7.appendChild(Cop_Ice);
		columna19.appendChild(Ice_Int);	
                
                        /**
                        * Iva al Costo
                        */
                        var Che_Cos=create_input('chkCos['+ total.value +']', 5, 5,'checkbox',true,'left');
                        var Iva_Cos=create_input('datos['+ total.value +','+ 25 +']', 5, 5,'hidden',true,'left');
                        Iva_Cos.value='N';
                        columna6.appendChild(Che_Cos);  
                        columna19.appendChild(Iva_Cos);                    
                        if(dq_des==='A'&&iva*1>0){                            
                            //columna6.setAttribute('align','left'); 
                            Che_Cos.title='Iva al Costo'; 
                            $(Che_Cos).tooltip({showURL: false});
                            $(Che_Cos).change(function (){ if($(this).attr('checked')) $(Iva_Cos).val('S'); else $(Iva_Cos).val('N'); });
                        }else{ Che_Cos.style.visibility='hidden'; } 
				
		/**
		* Porcenatje I.C.E 
		*/
		var Ice_Por=create_input('datos['+ total.value +','+ 10 +']', 5, 5,'hidden',false,'right');		
		/**
		* Cop_Iva.value = iva;
		*/
		columna7.appendChild(Ice_Por);
		/* Boton ICE*/
		var boton_ice_mas=create_button('Btn_IceMas['+total.value+']','+/-',' ');
		boton_ice_mas.onclick = function()
		{      
		        /**
				* Asigna el valor del la fila 
				*/
				var indicer = this.name.split("[");
				var indicer = indicer[1].split("]")	   
				if(document.getElementById("datos["+ indicer[0] +",9]").value=='')
				{	
					document.getElementById('busrta').value="";				
					document.getElementById('busrta').focus();
					document.getElementById('hdd_Ice_Por').value="datos["+ indicer[0] +",10]";
					document.getElementById('hdd_Ice_Sri').value="datos["+ indicer[0] +",9]";
					document.getElementById('hdd_Ice_Int').value="datos["+ indicer[0] +",24]";
					/**
					* Color de enfoque de la cuenta 
					*/
					lostfocus_compras('datos', Cop_Ice.name, true);	
					multiple_capa('',600,300,'cont_fon_ice','cont_cua_ice','busqueda de los Rubros Ice','cont_cua_det_ice');
					/**
					* Llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
					*/
					xDisplay('tbl_resultados', 'none');					
				}else{
					document.getElementById("datos["+ indicer[0] +",9]").value='';
					document.getElementById("datos["+ indicer[0] +",10]").value='';
					document.getElementById("datos["+ indicer[0] +",24]").value='';
					/**
					* Color de enfoque de la cuenta 
					*/
					lostfocus_compras('datos', Cop_Ice.name, false);	
					cal_importe_ice_com(Cop_Pru, Cop_Can, Cop_Imp);									
				}
		}
		columna8.appendChild(boton_ice_mas);
		
				
		/**
		* Cuadro que muestra el nombre de la adquisicion
		*/
		var Adq=create_input('datos['+ total.value +','+ 16 +']', 5, 5,'hidden',false,'right');	
		Adq.value=dq_cod;	
		/**
		* Cop_Iva.value = iva;
		*/	
		columna9.appendChild(Adq);	
		
		var Adq_Des=create_input('datos['+ total.value +','+ 12 +']', 1, 5,'text',true,'left');
		Adq_Des.value=dq_des;
		columna9.appendChild(Adq_Des);			
		/**
		* Cuadro que muestra el codigo de la busqueda - Antes 7
		*/
		var Pld_Cdc=create_input('datos['+ total.value +','+ 13 +']',5,50,'text',false,'left');
                Pld_Cdc.value=(cuenta['Pld_Cdc']);
		/**
		* Asignacion del evento al cuadro de busqueda
		*///alert(Pld_Des);
		Pld_Cdc.onkeyup = function() {cargar_cuenta(pagina + '&ajax_cuenta=',this,Pld_Des,Pld_Cod); valor_renta_compra();};
		/**
		* Pld_Cdc.value = cuenta;
		*/
		columna10.appendChild(Pld_Cdc);	
		/**
		* Código porcenatje iva
		*/
		var Iva_Por=create_input('datos['+ total.value +','+ 7 +']', 5, 5,'hidden',false,'left');
		Iva_Por.value = iva;
		//columna6.appendChild(Iva_Por);
		columna11.appendChild(Iva_Por);				
		
		var Pld_Cod=create_input('datos['+ total.value +','+ 15 +']',20,100,'hidden',true,'left'); 
		Pld_Cod.value = (cuenta['Pld_Cod']);
		columna11.appendChild(Pld_Cod);
		var Cop_Int=create_input('datos['+ total.value +','+ 8 +']', 5, 5,'hidden',false,'left');
		Cop_Int.value = total.value;
		
		columna12.appendChild(Cop_Int);
		/**
		* Cuadro que muestra la descripcion de la cuenta en base a la busqueda hecha con el codigo - Antes 20
		*/
		var Pld_Des=create_input('datos['+ total.value +','+ 14 +']',10,100,'text',true,'left');
		Pld_Des.value = (cuenta['Pld_Des']);
		columna12.appendChild(Pld_Des);

		/**
		* Caja de texto para el codigo de RENTA - Antes 2 
		*/
		var Ren_Con=create_input('datos['+ total.value +','+ 17 +']', 1, 2,'text',true,'left');
		columna13.appendChild(Ren_Con);
	    /**
		* Creación del botón de busqueda de renta.
		*/
		var boton_renta_mas=create_button('Btn_RentaMas['+total.value+']','+',' ');
		boton_renta_mas.onclick = function()
			{   
				/**
				* Asigna el valor del la fila 
				*/
				var indicer = this.name.split("[");
				var indicer = indicer[1].split("]")	
				document.getElementById('Hdd_Tip_Rta').value='R';
				document.getElementById('Tbl_Cuentas').className = 'oculta'; 
				document.getElementById('Tbl_Rentas').className = 'muestra'; 
				document.getElementById('busrta').value="";
				document.getElementById('busrta').focus();
				/**
				* Color de enfoque de la cuenta 
				*/
				lostfocus_compras('datos', Ren_Con.name, true);	
				/**
				* Asigno el valor de fila 
				*/
				document.getElementById('Hdd_Txt_Ide').value=indicer[0];
			    /**
				* Variables que reciben los nombres  
				*/
				document.getElementById('Hdd_Ren_Con').value="datos["+ indicer[0] +",17]";
				document.getElementById('Hdd_Ren_Ide').value="datos["+ indicer[0] +",19]";
				document.getElementById('Hdd_Ren_Por').value="datos["+ indicer[0] +",21]";				
				multiple_capa('',600,300,'cont_fon_iva','cont_cua_iva','busqueda de retención','cont_cua_iva_titu');
				/**
				* Llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
				*/
				xDisplay('Tbl_Rencon', 'none');
			}	 
				columna14.appendChild(boton_renta_mas);
				var boton_renta_menos=create_button('Btn_RentaMenos['+total.value+']','-',' ');
				boton_renta_menos.onclick = function()
				{  	
					/**
					* Asigna el valor del la fila 
					*/
					var indicerm = this.name.split("[");
					var indicerm = indicerm[1].split("]")	
					document.getElementById('Tbl_Cuentas').className = 'oculta'; 
					document.getElementById('Tbl_Rentas').className = 'oculta'; 			
					/**
					* Limpiar datos I.V.A. 
					*/
					document.getElementById("datos["+ indicerm[0] +",17]").value = ''; 
					document.getElementById("datos["+ indicerm[0] +",19]").value = ''; 
					document.getElementById("datos["+ indicerm[0] +",21]").value = ''; 
					/**
					* llamado a la función de cálculo de valores retenidos
					*/
					valor_renta_compra();
					/**
					* Color de enfoque de la cuenta 
					*/
					lostfocus_compras('datos', '', false); 
				}
				
				columna15.appendChild(boton_renta_menos);
				/**
				* Caja de texto para el codigo de IVA - Antes 2 
				*/
				var Iva_Con=create_input('datos['+ total.value +','+ 18 +']', 1, 2,'text',true,'left');
				/**
				* Creo los 2 campos necesarios para el envio de códigos de retención 
				*/
				var Int_Rer=create_input('datos['+ total.value +','+ 19 +']',20,100,'hidden',true,'left');  /* Código interno RENTA */
				var Int_Rei=create_input('datos['+ total.value +','+ 20 +']',20,100,'hidden',true,'left');  /* Código interno IVA */
				var Int_Rpc=create_input('datos['+ total.value +','+ 21 +']',20,100,'hidden',false,'right');  /*Porcentaje retención */
				var Int_Riv=create_input('datos['+ total.value +','+ 22 +']',20,100,'hidden',true,'right');  /*Porcentaje retención IVA */
				columna16.appendChild(Iva_Con);
				columna16.appendChild(Int_Rer);
				columna16.appendChild(Int_Rei);
				columna16.appendChild(Int_Rpc);
				columna16.appendChild(Int_Riv);
				/**
				* Creación del botón IVA 
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
					document.getElementById('Tbl_Cuentas').className = 'oculta'; 
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
					lostfocus_compras('datos', Iva_Con.name, true)			
					/**
					* variables que reciben los nombres  
					*/
					document.getElementById('Hdd_Ren_Con').value="datos["+ indicei[0] +",18]";
					document.getElementById('Hdd_Ren_Ide').value="datos["+ indicei[0] +",20]";
					document.getElementById('Hdd_Ren_Por').value="datos["+ indicei[0] +",22]";
					/**
					* Llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
					*/
					xDisplay('Tbl_Rencon', 'none');
					multiple_capa('',600,300,'cont_fon_iva','cont_cua_iva','busqueda de retención','cont_cua_iva_titu');
				}	
				columna17.appendChild(boton_iva_mas);
				var boton_iva_menos=create_button('Btn_IvaMenos['+total.value+']','-',' ');
				boton_iva_menos.onclick = function()
				{	
					/**
					* Asigna el valor del la fila 
					*/
					var indiceim = this.name.split("[");
					var indiceim = indiceim[1].split("]")	
					document.getElementById('Tbl_Cuentas').className = 'oculta'; 
					document.getElementById('Tbl_Rentas').className = 'oculta'; 			
					/**
					* Limpiar datos I.V.A. 
					*/
					document.getElementById("datos["+ indiceim[0] +",18]").value = ''; 
					document.getElementById("datos["+ indiceim[0] +",20]").value = ''; 
					document.getElementById("datos["+ indiceim[0] +",22]").value = ''; 
					/**
					* llamado a la función de cálculo de valores retenidos
					*/
					valor_renta_compra();
					/**
					* Color de enfoque de la cuenta 
					*/
					lostfocus_compras('datos', '', false); 
				}
				columna18.appendChild(boton_iva_menos);
				/**
				* Asignacion del evento al cuadro de cantidad
				*/
				Ren_Con.onfocus = function(){ valor_renta_compra();};
				Iva_Con.onfocus = function(){ valor_renta_compra();};
				Cop_Can.onkeyup = function(){cal_importe_ice_com(this, Cop_Pru, Cop_Imp); valor_renta_compra();};
				
				//Cop_Ice.onfocus = function(){cal_importe_ice_com(Cop_Pru, Cop_Can, Cop_Imp);};
				/**
				* Asignacion del evento al cuadro de precio unitario
				*/
				Cop_Pru.onkeyup = function(){cal_importe_ice_com(this, Cop_Can, Cop_Imp); valor_renta_compra();};
				/**
				* Asignacion del evento al cuadro de descuento
				*/
				Cop_Dec.onkeyup = function(){cal_importe_ice_com(Cop_Pru, Cop_Can, Cop_Imp); valor_renta_compra();};
				/**
				* Asignacion del evento al cuadro de I.V.A.
				*/
				Cop_Iva.onchange = function(){
					asignar_valor(this);  valor_renta_compra(); cal_importe_ice_com(Cop_Pru, Cop_Can, Cop_Imp); 
					if(this.value==5){
						boton_iva_mas.disabled=true;
						boton_iva_menos.disabled=true;
						Int_Rei.value='';
						Int_Riv.value='';
						Iva_Con.value='';	
					}else{ 
						boton_iva_mas.disabled=false;
						boton_iva_menos.disabled=false;
					}
				}; 
				
				/*Adq.onchange = function() 
				{	
					asignar_valor_adq(this); 
					valor_renta_compra(); 
					Ren_Con.value='';  
					Int_Rpc.value=''; 
					Int_Rer.value='';  
					Int_Rei.value=''; 
					Int_Riv.value=''; 
					Iva_Con.value='';	 
					document.getElementById('Tbl_Rentas').className = 'oculta';   
					valor_renta_compra();    
				};*/
				/**
				* Creación del botón de eliminación
				*/
				columna19.appendChild(create_button('quitar_fila','X',function(){ quitar_fila_compr_x(this); valor_renta_compra()}));
				/**
				* Creación del botón de eliminación
				*/
				var boton=create_button('Btn_Buscta['+total.value+']','+',' ')
				boton.onclick = function (){ 
					/**
					* Asigna el valor del la fila 
					*/
					var indice = this.name.split("[");
					var indice = indice[1].split("]")	
					/**
					* Asigna el indice del cuadro de texto seleccionado 
					*/
					document.getElementById('Hdd_Fila').value = indice[0];						
					document.getElementById('Tbl_Rentas').className = 'oculta';
					document.getElementById('Tbl_Cuentas').className = 'muestra'; 
					/**
					* Variables que reciben los nombres de los campos a los cuales se les asigna las cuentas 
					*/
					document.getElementById('Hdd_Pld_Cod').value = "datos["+indice[0]+",15]";
					document.getElementById('Hdd_Pld_Cdc').value = "datos["+indice[0]+",13]";
					document.getElementById('Hdd_Pld_Des').value = "datos["+indice[0]+",14]";
					//document.getElementById('buscta').focus();
					/**
					* Llamado a la función que permite ocultar la tabla en caso que no hayan resultados visibles 
					*/
					xDisplay('Tbl_Cuencont', 'none');
					/**
					* Color de enfoque de la cuenta 
					*/
					lostfocus_compras('datos', Pld_Cdc.name, true) 
					multiple_capa('',600,300,'cont_fon_cta','cont_cua_cta','busqueda de cuenta','cont_cua_cta_titu');
				 }
			columna11.appendChild(boton);	
			fila.appendChild(columna1);
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
			fila.appendChild(columna17);
			fila.appendChild(columna18);
			fila.appendChild(columna19);
			cuerpo.appendChild(fila);
			Cop_Iva.options[0].value ='NULL';
			Cop_Iva.options[0].text = '-';
			
}//Fin del function nueva_fila_com_ice(contenido,iva,codigoiva,ice,ice_por,

/**
* Función que permite validar la caducidad de una factura de compra
*/
function validarCaducidad(caducidad, hoy)
{
	if (caducidad.value)
	{
		if(caducidad.value>=hoy)
		{
			document.getElementById('div_caducidad').className = 'oculta'; 
			document.getElementById('Cop_Cad').style.color = '#999';                        
		}
		else
		{
			document.getElementById('div_caducidad').className = 'muestra'; 
			document.getElementById('Cop_Cad').style.color = '#F00';                        
		}
	}
}
function updateIva(colum_iva,colum_cod){            
	if(typeof iva_select !=='undefined' && iva_select!==null){
		var filas = document.getElementById ('nfilas');
		$('.iva_por').html(iva_select['Iva_Por']); 
		for (var j = 1; j <= filas.value; j++)
		{	
			var iva_cod = document.getElementById ('datos['+ j +','+ colum_cod +']');//Antes estaba 6
			var iva = document.getElementById ('datos['+ j +','+ colum_iva +']');//Antes estaba aqui 8
			//  console.log(iva.value*1);
			if(iva.value*1!==0){
				iva_cod.value=iva_select['Iva_Cod'];
				iva.value=iva_select['Iva_Por'];
				$('input[name="iva"]').each(function (){
					if(this.value*1!==0)
						$(this).val(iva_select['Iva_Por']);
				});
			}
		}
	} 
	cal_tarifas(7,4);//Llamado del calculo de tarifas OJO ES 7 PARA INGRESAR
	cal_iva_importe('Cop_Des',7,5,4); //Calculo del iva
	cal_ice_importe('Cop_Des',10,5,4);
	cal_total_com_ice();//Calculo del total
	valor_renta_compra();
}