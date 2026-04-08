//****************************M O D U L O    D E    M A T R I C U L A S*********************
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
//Inscripciones
function validar_inscritos1(form)
{
	if (requerido(form.Prs_Ced) != false)
	{
		form.submit();
	}	
}

function validar_inscritos2(form)
{
	if ((requerido(form.Prs_Ced) != false) && (requerido(form.Int_Cod) != false)
	 && (requerido(form.Est_Rep) != false))
	{
		confirmacion(form);
	}		
}

function validar_inscritos4(form)
{
	if ((requerido(form.Prs_Ced) != false) && (requerido(form.Prs_Nom) != false) 
	&& (requerido(form.Prs_Ape) != false)  && (requerido(form.Prs_Sex) != false)	
	//&& (requerido(form.mes_ini) != false)  && (requerido(form.dia_ini) != false)
	&& (requerido(form.Prs_Esc) != false)  && (requerido(form.Prs_Dir) != false)
	&& (requerido(form.Ciu_Cod) != false)  && (requerido(form.Int_Cod) != false)	
	&& (requerido(form.Est_Rep) != false))
	{
		confirmacion(form);
	}		
}

function validar_inscritos3(form)
{
	if (requerido(form.Car_Int) != false)
	{
		confirmacion(form);
	}		
}
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
//Matrículas pre-universitario
/*function validar_estudiante(form)//ojo revisar
{
	if ((requerido(form.Prs_Ced) != false) && (requerido(form.Prs_Nom) != false) 
	&& (requerido(form.Prs_Ape) != false)  && (requerido(form.Prs_Sex) != false)
	//&& (requerido(form.ann_ini) != false)	
	//&& (requerido(form.mes_ini) != false)  && (requerido(form.dia_ini) != false)
	&& (requerido(form.Prs_Esc) != false)  && (requerido(form.Prs_Dir) != false)
	&& (requerido(form.Ciu_Cod) != false)  //&& (requerido(form.Int_Cod) != false)
	)		
		{
			confirmacion(form);
		}
}*/

function matriculas(form)
{
	if (requerido(form.Sem_Cod) != false) 
	{
			confirmacion(form);
	}
}
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
//Matriculas semestrales
function validar_matricu1as1(form)
{
	if (requerido(form.Prs_Ced) != false)
	{
		form.submit();
	}	
}

function validar_matriculas2(form)
{
	if (requerido(form.Sem_Cod) != false)
	  {
		  	confirmacion(form);
	  }
}

function validar_matriculas3(form)
{
	//alert("Hola");	
	/*if (requerido(form.Mod_Cod) != false)
	{*/
		if (requerido(form.Car_Int) != false)
		{
			form.submit();
		}		
	//}		
}

function validar_matriculas4(form, cant)
{
	if (requerido(form.Sem_Cod) != false)
	  {			  		
		  	confirmacion(form);
	  }
}


function validar_matriculas7(form)
{   alert("Hola");
	if (requerido(form.Car_Int) != false)
	{
	  form.submit();
	}
}



function validar_matriculas5(form)
{
	if (requerido(form.Sem_Cod) != false)
	  {			
	  		//alert (document.form.array_sem_cod[1].value);
		  	confirmacion(form);
	  }
}


function validar_matriculas6(form)
{
	if (requerido(form.Eta_Cod) != false)
	  {
		 if (requerido(form.Mod_Cod) != false)
	  		{ 
				if (requerido(form.Car_Int) != false)
	  				{
		  				form.submit();
	  				}	
	  		}
	  }
}



//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
//Certificados de promoción
function contar_check(form, i)
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
	//*****************************************************
	if (cont > 0)
	{
		 if (document.form1.elements['Niv_Cod[1]'].value != "")//Este codigo es utilizado cuando no hay semestres que mostrar
		 {//Este codigo es utilizado cuando no hay semestres que mostrar
			form.submit();
		 }//Este codigo es utilizado cuando no hay semestres que mostrar
		 else//Este codigo es utilizado cuando no hay semestres que mostrar
		 {//Este codigo es utilizado cuando no hay semestres que mostrar
		 	alert ("No existen semestres abiertos para esta carrera");//Este codigo es utilizado cuando no hay semestres que mostrar
		 }//Este codigo es utilizado cuando no hay semestres que mostrar
	}
	else
	{
		alert ("Es necesario que selecciones al menos una opción");
	}
}

function todo_check(form, i, check)
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
		 document.form1.elements['Niv_Cod['+ x +']'].checked = valor_booleano;
	}
}
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
//Estudiantes
function validar_estudiante(form)
{
	if ((requerido(form.Prs_Ced) != false)  && (requerido(form.Int_Cod) != false))		
		{
			confirmacion(form);
		}
}

function validar_estudiante2(form)
{
	if ((requerido(form.Prs_Ced) != false) && (requerido(form.Prs_Nom) != false) 				   
	&& (requerido(form.Prs_Ape) != false)  && (requerido(form.Prs_Sex) != false)
	//&& (requerido(form.ann_ini) != false) && (requerido(form.mes_ini) != false)
	//&& (requerido(form.dia_ini) != false) 
	&& (requerido(form.Prs_Esc) != false)  
	&& (requerido(form.Prs_Dir) != false) && (requerido(form.Ciu_Cod) != false) 
	&& (requerido(form.Int_Cod) != false))		
		{
			confirmacion(form);
		}
}

function validar_estudiante_baja(form)
{
		confirmacion(form);						
}
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
//Transición
function validar_buscar_ced(form)
{
	if (requerido(form.Est_Ced) != false)		
		{
			form.submit();
		}	
}

function validar_transicion(form)
{
	if (requerido(form.Asi_Tra) != false)		
		{
			confirmacion(form);
		}	
}
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------

