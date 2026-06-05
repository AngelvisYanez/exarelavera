<?Php
 //require_once('../academico/LOGICA/logica.php');
//************************************A C A D E M I C O************************************************************
//*****************************************************************************************************************
// E S P E J O    D E    L A   B D D
//***********Funcion que selecciona todas las carreras activas en la bbd*******************************************
function carreras()
{
	$rs_carreras = cargar ("SELECT Car_Int, Car_Nom FROM carreras WHERE Car_Est = 'A' ORDER BY Car_Nom");
	return $rs_carreras;
	mysqli_free_result ($rs_carreras);
}

//***********Funcion que devuelve las ciudades*********************************************************************
function ciudad()
{
	$rs_ciudad = cargar ("SELECT Ciu_Cod, Ciu_Des FROM ciudad ORDER BY Ciu_Des");
	return $rs_ciudad;
	mysqli_free_result ($rs_ciudad);
}

//***********Funcion que devuelve las religiones*********************************************************************
function religion()
{
	$rs_religion = cargar ("SELECT Rel_Cod, Rel_Des FROM religion ORDER BY Rel_Des");
	return $rs_religion;
	mysqli_free_result ($rs_religion);
}

//***********Funcion que devuelve los paises***********************************************************************
function pais()
{
	$rs_pais = cargar ("SELECT Pas_Cod, Pas_Nac FROM pais ORDER BY Pas_Nac");
	return $rs_pais;
	mysqli_free_result ($rs_pais);
}

//***********Funcion que devuelve los paises (ORDER BY Codigo *******************************************************************
function pais_cod()
{
	$rs_pais = cargar ("SELECT Pas_Cod, Pas_Nac FROM pais ORDER BY Pas_Cod");
	return $rs_pais;
	mysqli_free_result ($rs_pais);
}

//***********Consulta de las profesiones***************************************************************************
function profesion()
{
	$rs_propad = cargar ("SELECT Prf_Cod, Prf_Des FROM profesion ORDER BY Prf_Des");
	return $rs_propad;
	mysqli_free_result ($rs_propad);
}


//***********Funcion que selecciona todas las carreras activas en la bbd*******************************************
function escuelas()
{
	$rs_escuelas = cargar ("SELECT Esc_Int, Esc_Nom FROM escuelas WHERE Esc_Est = 'A' ORDER BY Esc_Nom");
	return $rs_escuelas;
	mysqli_free_result ($rs_escuelas);
}
//**********Funcion que devuelve los docentes activos en la univerisidad*******************************************

//***********Funcion que carga la configuracion del modulo academico************************************************
function academico()
{
	$rs_academico = cargar("SELECT Hor_Min, Por_Fa1, Por_Fa2, Not_Fa2, Dis_Doc FROM confiacade WHERE Con_Cod = 1");
	return $rs_academico;
	mysqli_free_result ($rs_academico);
}
//***********Funcion que carga la modalidad de estudio**************************************************************
function modalidad()
{
	$rs_modalidad = cargar("SELECT Mod_Cod, Mod_Des FROM modalidad");
	return $rs_modalidad;
	mysqli_free_result ($rs_modalidad);
}

//******************************************************************************************************************
//***********Devuelve la consulta de la malla actual (Solo debe ser una)********************************************
function malla_actual($Car_Int)
{
	$rs_mallacurri = cargar ("SELECT Mal_Cod FROM mallacurri WHERE Mal_Act = 'A' AND mallacurri.Car_Int = '$Car_Int'");
	$row_rs_mallacurri = mysqli_fetch_assoc($rs_mallacurri);
	$total_rs_mallacurri = mysqli_num_rows ($rs_mallacurri);
	
	if ($total_rs_mallacurri > 0)
	{
		return $row_rs_mallacurri['Mal_Cod'];
	}
	else
	{
		return 0;
	}
	mysqli_free_result ($rs_mallacurri);
}

//******************************************************************************************************************
//******************Consulta de los semestres en el periodo de una escuela especifica*******************************
function semestres_actual($Esc_Int, $Per_Int)
{
	$rs_semestres =  cargar("SELECT semestres.Sem_Cod, niveles.Niv_Des, semestres.Sem_Par, IF (Sem_Sec = 'D', 'Diurna', IF (Sem_Sec = 'V', 'Vespertina', 
								'Nocturna')) as Sem_Sec, modalidad.Mod_Des FROM semestres, niveles, modalidad, promocione, carreras, escuelas, periodos WHERE 
								semestres.Niv_Cod = niveles.Niv_Cod AND periodos.Mod_Cod = modalidad.Mod_Cod AND promocione.Pro_Cod = semestres.Pro_Cod AND 
								carreras.Car_Int = promocione.Car_Int AND carreras.Esc_Int =  escuelas.Esc_Int AND escuelas.Esc_Int = $Esc_Int AND semestres.Per_Int = 
								periodos.Per_Int AND semestres.Per_Int = $Per_Int ORDER BY semestres.Niv_Cod, semestres.Sem_Par, Sem_Sec");
	return $rs_semestres;
	mysqli_num_rows ($rs_semestres);					
}

//******************************************************************************************************************
//******************Consulta de los semestres en el periodo de una carrera especifica*******************************
function semestres_actual_car($Car_Int, $Per_Int)
{
	$rs_semestres =  cargar("SELECT semestres.Sem_Cod, niveles.Niv_Des, semestres.Sem_Par, IF (Sem_Sec = 'D', 'Diurna', IF (Sem_Sec = 'V', 'Vespertina', 
								'Nocturna')) as Sem_Sec, modalidad.Mod_Des FROM semestres, niveles, modalidad, promocione, carreras, periodos WHERE 
								semestres.Niv_Cod = niveles.Niv_Cod AND periodos.Mod_Cod = modalidad.Mod_Cod AND promocione.Pro_Cod = semestres.Pro_Cod AND 
								carreras.Car_Int = promocione.Car_Int AND carreras.Car_Int = $Car_Int AND semestres.Per_Int = 
								periodos.Per_Int AND semestres.Per_Int = $Per_Int ORDER BY semestres.Niv_Cod, semestres.Sem_Par, Sem_Sec");
	return $rs_semestres;
	mysqli_num_rows ($rs_semestres);					
}

//******************************************************************************************************************
//******************Consulta de las asignaturas de un semestre y su respectivo calculo de horas*********************
function asignaturas_horas($Sem_Cod)
{
	$rs_asignaturas = cargar ("SELECT distributi.Dis_Cod, distributi.Asi_Int, asignatura.Asi_Des, distributi.Dis_Hoi, distributi.Dis_Hol, 
						distributi.Dis_Cre, distributi.Dis_Est FROM distributi, asignatura WHERE asignatura.Asi_Int = distributi.Asi_Int 
						AND distributi.Sem_Cod = $Sem_Cod ORDER BY distributi.Dis_Blo, asignatura.Asi_Int");
	return ($rs_asignaturas);
	mysqli_free_result($rs_asignaturas);
}
//*******Funcion que devuelve el Calculo de horas academicas, horas de investigacion, horas de practica laboral*****
//*******Devuelve los datos principales de un semestre**************************************************************
function detalle_semestre ($Sem_Cod,$Est_Int)
{
	$rs_semestre =  cargar("SELECT  estado_mtr.Esm_Tip,matriculas.Esm_Int,semestres.Sem_Cod, niveles.Niv_Des, semestres.Sem_Par, IF (Sem_Sec = 'D', 'Diurna', IF (Sem_Sec = 'V', 'Vespertina',
							'Nocturna')) as Sem_Sec, modalidad.Mod_Des, carreras.Car_Nom 
							FROM semestres, niveles, modalidad, promocione, carreras, periodos, matriculas, estado_mtr WHERE 
							semestres.Pro_Cod = 
							promocione.Pro_Cod AND promocione.Car_Int = carreras.Car_Int AND semestres.Niv_Cod = niveles.Niv_Cod AND periodos.Mod_Cod = 
							modalidad.Mod_Cod AND semestres.Sem_Cod = $Sem_Cod AND semestres.Per_Int = periodos.Per_Int AND matriculas.Sem_Cod=semestres.Sem_Cod AND estado_mtr.Esm_Int=matriculas.Esm_Int AND matriculas.Est_Int=$Est_Int ");

       
	
	return $rs_semestre;
	mysqli_num_rows ($rs_semestre);						
}
//***************Devuelve las asignaturas de un nivel y malla especifica, esto se utiliza para el distributivo******
function asignaturas_distributivo($Mal_Cod, $Niv_Cod)
{
	$rs_asignaturas = cargar ("SELECT asignatura.Asi_Int, asignatura.Asi_Des, asignatura.Asi_Cre, asignatura.Asi_Hoi, asignatura.Asi_Hol FROM 
							asignatura, disciplina, mallacurri WHERE asignatura.Dip_Cod = disciplina.Dip_Cod AND disciplina.Mal_Cod = 
							mallacurri.Mal_Cod AND mallacurri.Mal_Cod = $Mal_Cod AND asignatura.Asi_Est = 'A' AND asignatura.Niv_Cod = $Niv_Cod");
	return $rs_asignaturas;
	mysqli_free_result ($rs_asignaturas);
}
//**************Funcion que devuelve el numero de bloques q posee el distributivo de un semestre********************
function numero_bloques($Sem_Cod)
{
	$rs_bloques = cargar("SELECT max(distributi.Dis_Blo) as bloques FROM distributi, semestres WHERE distributi.Sem_Cod = semestres.Sem_Cod
						AND distributi.Sem_Cod = $Sem_Cod");
	$row_rs_bloques = mysqli_fetch_assoc($rs_bloques);
	return $row_rs_bloques['bloques'];
	mysqli_free_result($rs_bloques);
}
//**************Funcion que devuelve el numero de bloques q posee un periodo de manera general**********************
function numero_bloques_per($Per_Int)
{
	$rs_bloques = cargar("SELECT max(distributi.Dis_Blo) as bloques FROM distributi, semestres WHERE distributi.Sem_Cod = semestres.Sem_Cod
						AND semestres.Per_Int = $Per_Int");
	$row_rs_bloques = mysqli_fetch_assoc($rs_bloques);
	return $row_rs_bloques['bloques'];
	mysqli_free_result($rs_bloques);
}
//***********Funcion que devuelve los colegios y los institutos******************************************************
function colegios()
{
	$rs_institucio = cargar ("SELECT Int_Cod, Ins_Des, Ciu_Des FROM institucio, ciudad WHERE institucio.Ciu_Cod = ciudad.Ciu_Cod  ORDER BY Ciu_Des, Ins_Des"); //OR Ins_Des LIKE 'Ins%'
	return $rs_institucio;
	mysqli_free_result ($rs_institucio);
}

/* Funcion que devuelve la cantidad de horas entre dos horas */
function sumar_horas($hora1, $hora2, $op)
{
	$hora1=explode(":",$hora1);
	$hora2=explode(":",$hora2);
	$horas=(int)$hora1[0]+(int)$hora2[0];
	$minutos=(int)$hora1[1]+(int)$hora2[1];
	$horas+=(int)($minutos/60);
	$minutos=$minutos%60;
	if($minutos<10)
		$minutos="0".$minutos ;
	
	switch ($op){
		case 1: //Opcion para enviar el valor en numeros
			return round($horas.".".$minutos);
			break;
		case 2: //Opcion para enviar el valor en tiempo
			return $horas.".".$minutos;
			break;
	}
}

//***************Devuelve el periodo del semestre en uso*************************************************************
function periodo_semestre($Sem_Cod)
{
	$rs_periodo = cargar ("SELECT semestres.Per_Int FROM semestres WHERE semestres.Sem_Cod = $Sem_Cod");
	$row_rs_periodo = mysqli_fetch_assoc ($rs_periodo);

	if ($row_rs_periodo['Per_Int'] != "")
	{
		return $row_rs_periodo['Per_Int'];
	}
	else
	{
		return 0;
	}
	mysqli_free_result ($rs_periodo);
}

//Funcion utilizada para devolver si existen horas principal, atrasada o de supletorio menores a una fecha determinada
function orden_examenes_menor($paq, $ini, $fin, $Hor_Fec, $Tip)
{
	$rs_existe = cargar ("SELECT horarios.Hor_Fec FROM horarios, distributi WHERE horarios.Dis_Cod = distributi.Dis_Cod AND 
					distributi.Dis_Blo = $paq AND horarios.Hor_Ini = '$ini' AND horarios.Hor_Fin = '$fin' AND horarios.Hor_Fec <= '$Hor_Fec' 
					AND horarios.Hor_Tip = '$Tip'");
	$total_rs_existe = mysqli_num_rows ($rs_existe);				
	return $total_rs_existe;
	mysqli_free_result($rs_existe);
}
//Funcion utilizada para devolver si existen horas principal, atrasada o de supletorio mayores a una fecha determinada
function orden_examenes_mayor($paq, $ini, $fin, $Hor_Fec, $Tip)
{
	$rs_existe = cargar ("SELECT horarios.Hor_Fec FROM horarios, distributi WHERE horarios.Dis_Cod = distributi.Dis_Cod AND distributi.Dis_Blo = $paq AND 
					horarios.Hor_Ini = $ini AND horarios.Hor_Fin = $fin AND horarios.Hor_Fec >= '$Hor_Fec' AND horarios.Hor_Tip = '$Tip'");
	$total_rs_existe = mysqli_num_rows ($rs_existe);				
	return $total_rs_existe;
	mysqli_free_result($rs_existe);
}

//Funcion que devuelve una cadena de parametros para iniciar la busqueda de un sql
/*function envio_parametros($cant, $Niv_Cod)
{
	for ($i=1;$i<=$cant;$i++)
	{
		if (isset($Niv_Cod[$i]))//Verifica si esta seteada una posicion del arreglo
		{
			$cod = $Niv_Cod[$i]."-"; 
		 	$cod2 = "$cod2$cod";					
		}
	}
	return $cod2;
	
}*/


//*****************Funcion que devuelve el total de las faltas obtenidas por un estudiante en un distributivo******************************************************
function total_faltas($Dis_Cod, $Mat_Int)
{
	$rs_faltas = cargar("SELECT sum(faltasdet.Fal_Can) as Fal_Can FROM faltas, faltasdet WHERE faltas.Fal_Cod = faltasdet.Fal_Cod AND faltas.Dis_Cod = $Dis_Cod AND faltasdet.Mat_Int = $Mat_Int");
	$row_rs_faltas = mysqli_fetch_assoc ($rs_faltas);
	if ($row_rs_faltas['Fal_Can'] != "")
	{
		return $row_rs_faltas['Fal_Can'];
	}
	else
	{
		return 0;
	}
	mysqli_free_result($rs_faltas);
}
//*********************Funcion que devuelve el estado de la asignatura, segun las faltas**************************************************************************
function estado_faltas($total_faltas, $porcentaje1, $porcentaje2)
{
	$row_rs_academico = mysqli_fetch_assoc ($rs_academico = academico());
	$puntaje_minimo = $row_rs_academico['Not_Fa2'];

	if ($total_faltas >= $porcentaje2)
	{
		$estado = "R"; //Reprobado por faltas
	}
	else
	{
		if ($total_faltas >= $porcentaje1 && $total_faltas <= $porcentaje2)
		{
			$estado = "O";//Reprobado por faltas, con opci�n a acogerse al reglamento
		}
		else
		{
			if ($total_faltas >= round($porcentaje1 / 2) && $total_faltas < $porcentaje1)
			{
				$estado = "P";//Con problemas de asistencia
			}
			else
			{
				$estado = "";
			}			
		}
	}
	return $estado;
	mysqli_free_result ($rs_academico); 
}
//************************Funcion que devuelve el comentario del estado de las faltas****************************************************************************
function comentario_faltas($estado)
{
	if ($estado == 'R')
	{
		$comentario = "Reprobado por faltas";
	}
	else
	{
		if ($estado == 'O')
		{
			$comentario = "Reprobado por faltas, con opci�n <br> de acogerse al reglamento";
		}
		else
		{
			if ($estado == 'P')
			{
				$comentario = "Con problemas de asistencia";
			}
			else
			{
				$comentario = "&#8212;";
			}
		}
	}
	return $comentario;
}
//***********************Funcion que devuelve el detalle de un semestre especifico mas la carrera****************************
function semestre_carrera($Sem_Cod)
{
	$rs_semestre = cargar ("SELECT promocione.Car_Int FROM promocione, semestres WHERE promocione.Pro_Cod = semestres.Pro_Cod AND semestres.Sem_Cod = $Sem_Cod");
	$row_rs_semestre = mysqli_fetch_assoc ($rs_semestre);
	$semestre = $row_rs_semestre['Car_Int'];
	return $semestre;
	mysqli_free_result ($rs_semestre);
}

//******************************Funcion que divide el nombre de la carrera******************************
function divide_carrera($nombre_carrera)
{
	$detalle="";
	$carrera = explode(' ', $nombre_carrera);
	for ($x=0; $x<= count($carrera) - 1; $x++)
	{
		$text = substr($carrera[$x], 0, 3).'.';
		if (strlen($text) == 4)
		{
			$detalle = $detalle.' '.$text;
		}
	}
	return $detalle;
}
?>