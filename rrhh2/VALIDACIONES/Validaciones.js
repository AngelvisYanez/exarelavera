/*****LLAMADO A LA FUNCIÓN PRINCIPAL DE VALIDACIONES DEL SISTEMA GINUS **************************************************/
/**/
function validar_hors(form)
{
	
			form.submit();
		
}
/**/
function validar_areper(form)
{
	if (requerido(form.Tip_Cod)!= false)
		{
			form.submit();
		}
}

/*validar envio*/
function validar_envio(form)
{
			formimg.submit();
}

function validar_buscar(form)
{
	if(requerido(form.txt_Busqueda)!=false)
	{
		form.submit();
	}
	
}

/*validar boton para imprimir los reportes antes de crear el horario*/
function validar_lista(form)
{
	//if (document.getElementById('Chk_Por').checked)
	//{
		var enviar=true;
		var c=0;
		for (i=1; i<=document.getElementById('hhdd_var').value; i++)
		{
			/* En caso de estar chequeado el producto, le quita el readOnly */
			if (document.getElementById('Lst_Per['+ i +']').checked)
			{
				c=c+1;
							
			} 
		}
		if (c==0)
		{
		       alert("Debes seleccionar al menos una opcion");
				return false;
		}
		else
		{
			form.submit();
		}
		
}

/*********************/

/*validar boton para guradar cabecera horario*/
function validar_cabecera(form)
{
	if(requerido(form.boton_cabec)!=false)
	{
		form.submit();
	}
}
/*********************/
function validar_tipo_periodo(form)
{
	if(requerido(form.Tip_Peri)!=false)
	{
		form.submit();
	}
}

function validar_tipo_rec_lab(form)
{	if(requerido(form.Tir_Des)!=false)
	{
	
		confirmacion(form);
	}
}

function validar_mod_tirhh(form)
{
		if(requerido(form.Tir_Des)!=false)
	{
	
		confirmacion(form);
	}
}
function validar_Anio_Laborable(form)
{
	if(requerido(form.Ann_Lab)!=false)
	{
		
		confirmacion(form);
	}
}

function validar_Tipo_Receso(form)
{
	if(requerido(form.Tip_Rec)!=false)
	{
		confirmacion(form);
	}
}
function validar_horario(form)
{
	var c=0;
	for (i=1; i<=document.getElementById('hhdd_cant').value; i++)
		{
			/* En caso de estar chequeado el producto, le quita el readOnly */
			if (document.getElementById('Det_Dia['+ i +']').checked)
			{
				c=c+1;
							
			} 
		}
	if (requerido(form.Tih_Cod) != false)
		{
			if (c==0)
			{
				   alert("Debes seleccionar al menos una opcion");
					return false;
			}
			else
			{
				
			 if ((requerido(form.Hor_Ini) != false)&&(requerido(form.Min_Ini) != false) && (requerido(form.Hor_Fin) != false) && 	
					(requerido(form.Min_Fin) != false))
				{
					
					form.submit();				
				}
			}
		}
}


/**** seleccionar todos checks ************/
/**** Seleccionar todos los checks *********************************/
/*No utilizadas*/
/* Activa o inactiva el modo lectura de un cuadro de texto */
function activar_lectura(form, check)
{
	//if (!(document.getElementById('Bec_Por['+ indice +']').disabled))
	//{
		/* Entra en caso de estar chequeado */
		if (check.checked)
		{
			bool_ind = false;
		}
		else
		{
			bool_ind = true;
		}
		//document.getElementById('Bec_Por['+ indice +']').readOnly=bool_ind;	
		//document.getElementById('Bec_Por['+ indice +']').focus();
	//}//Fin del if (!(document.getElementById('Bec_Por['+ indice +']').disabled))
}
//
///* Activo o desactiva el modo lectura de todos los cuadros de texto */
function activar_lectura_todo(form, check)
{
	/* Entra en caso de estar chequeado */
	if (check.checked)
	{
		bool_ind = false;
	}
	else
	{
		bool_ind = true;
	}

}

function todo_check_dia(form, i, check)
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
		 document.form2.elements['Det_Dia['+ x +']'].checked = valor_booleano;
	}
}
//funcion que me sirve para contar checks seleccionados
//
function cuenta_check(form,i,check,name)
{
	//Cuenta cuantos checkboxes se encuantran selecionados	
	var cont = 0;
	for (var x=1; x <= i; x++) 
	{
		 if (form.elements[name+'['+ x +']'].checked) 
		 {
		    cont = cont + 1;
		 }
	}
	if(cont>0)
	{
		form.submit();
	}
	else
	{
		alert('Debe seleccionar al menos una opción');
	}
}

function validar_etamod(form)
{
	if (requerido(form.Mod_Cod) != false)
			{
				if (requerido(form.Eta_Cod) != false)
				{
					if (requerido(form.Car_Int) != false)
					{
						form.submit();
					}
				}
			}
}

/**/
function todomasmenos(form,i,check) 
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
		if(document.getElementById('lista['+ x + ']').className != "oculta"){
        document.getElementById('lista['+ x + ']').className = "oculta";
		document.getElementById('kate['+ x + ']').className = "muestra";
   		 } 
		else {
		  document.getElementById('lista['+ x + ']').className = "muestra";
		  document.getElementById('kate['+ x + ']').className = "oculta";
		}
	}
}


/**/
function validar_horas(form)
 {
	//Validacion de definicion de fechas
	
	var hora_ini = parseInt(form.Hor_Ini.value); 
	var hora_fin = parseInt(form.Hor_Fin.value); 
	var min_ini = parseInt(form.Min_Ini.value); 
	var min_fin = parseInt(form.Min_Fin.value); 
	
			
	if (hora_fin > hora_ini )
	{
		
		confirmacion(form);
	}
	else
	{
		if(min_fin > min_ini){
		confirmacion(form);
		
		}
		else{
			alert ("La hora de salida debe ser mayor a la hora de entrada");
			form.Min_Ini.focus()
			}
	}
			
				
 }
/**/
// funcion para validar contratos pagina rgu_alt contratos
 function validar_contratos(form)
 {  	
	 if (requerido(form.Are_Cod) != false)
			{ 
				if (requerido(form.Dep_Cod) != false)
				{
					if (requerido(form.Tic_Cod) != false)
					{
						if(requerido(form.Ded_Cod) != false)
						{
							if(requerido(form.Reb_Cod) != false)
							{
								if(requerido(form.Con_Ini) != false)
								{
									
									if(requerido(form.Sue_Val) != false)
									{
										
											var f_fin= document.getElementById('Con_Fin');
												
											if(f_fin.value!="")
											{
											
												if (document.getElementById('Con_Fin').value > f_fin.value)
													{
															alert ("!La FECHA DE FIN debe ser mayor (>) a la FECHA DE INICIO¡");
															form.Dis_Fip.focus();
															return false;
													}  
													else{
														   confirmacion(form);
														   form.submit();
													}
											}
											else{
																																																						
												confirmacion(form);
												form.submit();
									
												}
											
									}
											
								}
							}
						}
					}
				}
			}
 }

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