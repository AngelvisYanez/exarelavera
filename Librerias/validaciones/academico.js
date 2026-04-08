//******************************************************************************
//**********Periodos semestrales y pre-universitario****************************
function validar_periodos(form)
 {
	//Validacion de definicion de fechas
	var fecha_ini = form.Per_Fea.value; 
	var fecha_fin = form.Per_Fef.value; 
	var fecha_ord = form.Pem_Ord.value; 
	var fecha_ext = form.Pem_Ext.value; 
	var fecha_exc = form.Pem_Exc.value; 
	var fecha_exc_fin = form.Pem_Fin.value; 

			if (fecha_ini < fecha_fin)
			{
				if (fecha_ord < fecha_ini)
				{
					if (fecha_ord < fecha_ext)
					{
						if (fecha_ext < fecha_exc)
						{
							if (fecha_exc < fecha_exc_fin)
							{
								if (fecha_exc_fin < fecha_fin )
								{
									confirmacion(form);
								}
								else
								{
									alert ("La fecha de cierre de matrículas debe ser MENOR a la fecha de fin de clases");
									form.Pem_Fin.value = "";
								}
							}
							else
							{
								alert ("La fecha de inicio de matrícula excepcional debe ser MENOR a la fecha del cierre de matrículas");
								form.Pem_Exc.value = "";
							}
						}
						else
						{
							alert ("La fecha de inicio de matrícula extra-ordinaria debe ser MENOR a la fecha de matrícula excepcional");
							form.Pem_Ext.value = "";
						}
					}
					else
					{
						alert ("La fecha de inicio de matrícula ordinaria debe ser MENOR a la fecha de matrícula extra-ordinaria");
						form.Pem_Ord.value = "";
					}
				}
				else
				{
					alert ("La fecha de inicio de matrícula ordinaria debe ser MENOR a la fecha de inicio a clases");
					form.Pem_Ord.value = "";
				}
			}
			else
			{
				alert ("La fecha de inicio de a clases debe ser MENOR a la fecha de fin de clases");
				form.Per_Fea.value = "";
			}
 }

function validar_periodos_pre(form)
 {
	//Validacion de definicion de fechas
	var fecha_ini = form.Per_Fea.value; 
	var fecha_fin = form.Per_Fef.value; 
	var fecha_ord = form.Pre_Ini.value; 
	var fecha_exc_fin = form.Pre_Fin.value; 

			if (fecha_ini < fecha_fin)
			{
				if (fecha_ord < fecha_ini)
				{
					if (fecha_ord < fecha_exc_fin)
					{
						confirmacion(form);
					}
					else
					{
						alert ("La fecha de inicio de matrícula debe ser MENOR a la fecha de fin matrícula");
						form.Pre_Ini.value = "";
					}
				}
				else
				{
					alert ("La fecha de inicio de matrícula debe ser MENOR a la fecha de inicio a clases");
					form.Pre_Ini.value = "";
				}
			}
			else
			{
				alert ("La fecha de inicio de a clases debe ser MENOR a la fecha de fin de clases");
				form.Per_Fea.value = "";
			}
 }

function fin_fecha(campo_ini, campo_fin)
{
	campo_fin.value = fechas_futuras(campo_ini, -1); 
}

function ocultar(tipo)
{
	switch (tipo.value)
	{
		case 'S':
			/* Ocultar y Mostrar la tabla */
			ShowHide('Sem');
			ShowHide('Pre');
			
			/* Ocultar y Mostrar el boton */
			ShowHide('Guardar');
			ShowHide('Guardar2');
			break;
		case 'P':
			/* Ocultar y Mostrar la tabla */		
			ShowHide('Pre');
			ShowHide('Sem');
			
			/* Ocultar y Mostrar el boton */
			ShowHide('Guardar2');
			ShowHide('Guardar');
			break;
		case 'O':
			//ShowHide('Sem')
			break;	
	}
}

//******************************************************************************
//***********************Distributivos******************************************
function validar_distributi1(form)
{
   if ((requerido(form.Pem_Tip) != false))
		{
			form.submit();
		}
}

function validar_distributi2(form)
{
   if ((requerido(form.Per_Int) != false))
		{
			form.submit();
		}
}

function hab_inab_text(check)
{
	if (check.checked)
	{
		document.form2.Dis_Sub.disabled = false;
		document.form2.Dis_Sub.focus();		
	}
	else
	{
		document.form2.Dis_Sub.disabled = true;
		document.form2.Dis_Sub.value = "";
	} 
}

function validar_distributi3(form)
{
	if ((requerido(form.Dis_Cre) != false) && (requerido(form.Per_Doc) != false) && (requerido(form.Dis_Blo) != false))
	{
		confirmacion(form);
	}
}

function validar_distributi33(form)
{
   if ((requerido(form.Asi_Int) != false) &&
		(requerido(form.Dis_Sub) != false) && (requerido(form.Dis_Cre) != false) 
		&& (requerido(form.Per_Doc) != false) && (requerido(form.Dis_Blo) != false))
		{
			confirmacion(form);
		}
}

function validar_distributi_tiempo(form, fec_ini, fec_fin)
{
	/*Valida que las fechas se encuentren dentro del rango permitido*/
	/****************************************************************/
	vea = "Vea la Distribución del tiempo para el paquete en la parte superior";
	if (form.Dis_Fip.value >= fec_ini && form.Dis_Fip.value <= fec_fin)/*Verifica que la fecha inicial se encuentre dentro del rango*/
	{
		if (form.Dis_Ffp.value >= fec_ini && form.Dis_Ffp.value <= fec_fin)/*Verifica que la fecha final se encuentre dentro del rango*/
		{		
			if (form.Dis_Exa.value >= fec_ini && form.Dis_Exa.value <= fec_fin)/*Verifica que la fecha inicial de examen se encuentre dentro del rango*/
			{		
				if (form.Dis_Exf.value >= fec_ini && form.Dis_Exf.value <= fec_fin)/*Verifica que la fecha final de examen se encuentre dentro del rango*/
				{		
					/*Valida que las fechas seleccionadas de inicio y fin del proceso, inicio y fin de examen esten correctas*/
					/*********************************************************************************************************/
					if (form.Dis_Fip.value <= form.Dis_Ffp.value)/*Verifica que la fecha de inicio de clases sea <= a la fecha final de clases*/ 
					{
						if (form.Dis_Ffp.value < form.Dis_Exa.value)/*Verifica que la fecha de fin de clases sea < a la fecha inicio de examen*/ 
						{				
							if (form.Dis_Exa.value <= form.Dis_Exf.value)
							{
								confirmacion(form);
							}
							else
							{
								alert ("¡La fecha de INICIO de examen debe ser menor o igual a la fecha de FIN de examen!");
								form.Dis_Exa.value = "";								
							}

						}
						else
						{
							alert ("¡La fecha de FIN de clases debe ser menor a la fecha de INICIO de examen!");
							form.Dis_Ffp.value = "";								
						}
					}
					else
					{
						alert ("¡La fecha de INICIO de clases debe ser menor o igual a la fecha de FIN de clases!");
						form.Dis_Fip.value = "";
					}
				}
				else
				{
					alert ("!La fecha de FIN de examen está incorrecta porque está fuera del tiempo definido!.\n" + vea);
					form.Dis_Exf.value = "";
					
				}
			}
			else
			{
				alert ("¡La fecha de INICIO de examen está incorrecta porque porque está fuera del tiempo definido!.\n" + vea);
				form.Dis_Exa.value = ""
			}
		}
		else
		{
			alert ("¡La fecha de FIN de clases está incorrecta porque porque está fuera del tiempo definido!.\n" + vea);
			form.Dis_Ffp.value = ""
		}		
	}
	else
	{
		alert ("¡La fecha de INICIO de clases está incorrecta porque está fuera del tiempo definido!.\n" + vea);
		form.Dis_Fip.value = ""
	}
}
//******************************************************************************
//****************************Pais**********************************************
function validar_pais(form)
{
	if ((requerido(form.Pas_Nom) != false) &&
	(requerido(form.Pas_Nac) != false))
	{
		confirmacion(form);
	}	
}
//******************************************************************************
//**************************Horarios********************************************
function validar_horarios(form)
{
	if (requerido(form.Dis_Cod) != false &&
	(requerido(form.Hor_Dia) != false) && (requerido(form.Hora_Ini) != false) &&
	(requerido(form.Minutos_Ini) != false) && (requerido(form.Hora_Fin) != false) &&
	(requerido(form.Minutos_Fin) != false))
	{
		confirmacion(form);
	}		
}

//********************Horarios de examen****************************************
function validar_horarios_examen(form)
{
	if (requerido(form.Dis_Cod) != false &&
	(requerido(form.Hor_Fec) != false) && (requerido(form.Hora_Ini) != false) &&
	(requerido(form.Minutos_Ini) != false) && (requerido(form.Hora_Fin) != false) &&
	(requerido(form.Minutos_Fin) != false) && (requerido(form.Hor_Tip) != false))
	{
		confirmacion(form);
	}		
}

function listar_combo_hora(hora_ini, hora_fin)
{
		combo_listar (hora_ini.value-1, 23, hora_fin, 1);				
}

function listar_combo_minutos_fin(hora_ini, hora_fin)
{
	if (hora_ini.value == hora_fin.value)
	{
		combo_listar (document.form2.Minutos_Ini.value, 59, document.form2.Minutos_Fin, 1);
	}
	else
	{
		combo_listar (-1, 59, document.form2.Minutos_Fin, 1);			
	}
}

function listar_combo_minutos(minutos_ini, minutos_fin)
{
		/*Cuando la hora inicial y la final son iguales se debe cargar los minutos
		/finales en base a los minutos inicales */
		if (document.form2.Hora_Ini.value == document.form2.Hora_Fin.value)
		{
			combo_listar (minutos_ini.value, 59, minutos_fin, 1);
		}
		else
		{
			combo_listar (-1, 59, minutos_fin, 1);			
		}
}
//********************Control de faltas*****************************************
function enviar(form)
{
	form.hdd_save.disabled = true;
	form.submit();	
}
//********************Cursos Pre-universitarios*********************************
function validar_pre()
{
	if ((requerido(document.form2.Niv_Cod) != false) && (requerido(document.form2.Mod_Cod) != false)	
		&& (requerido(document.form2.Sem_Sec) != false) && (requerido(document.form2.Sem_Par) != false))
	{
		confirmacion(document.form2);
	}
}

//******************Control de personal*****************************************
//***********************Personal******************************************
function validar_personal(form)
{
	if ((requerido(form.Prs_Ced) != false) && (requerido(form.Per_Tip) != false) && (requerido(form.Per_Car) != false)	
	&& (requerido(form.Ide_Cod) != false)  && (requerido(form.Esc_Int) != false) && (requerido(form.Per_Tit) != false))
		{
			confirmacion(form);
		}
}

function validar_personal2(form)
{
	if ((requerido(form.Prs_Ced) != false) && (requerido(form.Prs_Nom) != false) 
		&& (requerido(form.Prs_Ape) != false) && (requerido(form.Prs_Sex) != false)	
		&& (requerido(form.Prs_Esc) != false) && (requerido(form.Prs_Dir) != false)	
		&& (requerido(form.Ciu_Cod) != false) && (requerido(form.Per_Tip) != false)	&& (requerido(form.Per_Car) != false)
		&& (requerido(form.Ide_Cod) != false)
		&& (requerido(form.Esc_Int) != false) && (requerido(form.Per_Tit) != false))
		{
			confirmacion(form);
		}
}


function validar_personal_baj(form)
{
		confirmacion(form);
}
