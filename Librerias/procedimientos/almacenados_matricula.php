<?php
//************************************M A T R I C U L A************************************************************
//*****************************************************************************************************************
//***********Funcion que carga la configuracion del modulo de matriculas*******************************************
function matriculas()
{
	$rs_matriculas = cargar("SELECT Num_Asi, Num_Mat FROM confimatri WHERE Con_Cod = 1");
	return $rs_matriculas;
	mysqli_free_result ($rs_matriculas);
}
//***********Funcion utilizada al momento de presentar datos, como referencia de un estudiante*********************
//function estudiante($codigo)
//{	
//	$rs_estudiante = cargar("SELECT Est_Int, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape FROM estudiante, persona WHERE persona.Prs_Cod = 
//							estudiante.Prs_Cod AND persona.Prs_Ced = '$codigo'");
//	return $rs_estudiante;
//	mysqli_free_result ($rs_estudiante);
//}

function estudiante($codigo,$obBD_conexion,$obBD_con1)
{	/**************** ********************************************************************/
	$rs_estudiante = $obBD_con1->consulta(sentencias_mat(179, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);
	return $rs_estudiante;
	mysqli_free_result ($rs_estudiante);
	/**************** ********************************************************************/
}


//***********Funcion utilizada al momento de presentar datos, como referencia de un estudiante*********************
//***********con el código interno del estudiante******************************************************************
function estudiante_int($codigo)
{	
	$rs_estudiante = cargar("SELECT estudiante.Est_Int, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape FROM estudiante, persona 
						WHERE persona.Prs_Cod = estudiante.Prs_Cod AND estudiante.Est_Int = '$codigo'");
	return $rs_estudiante;
	mysqli_free_result ($rs_estudiante);
}
//************Cuenta el numero de matriculas reprobadas que tiene una determinada asignatura***********************
function contar_matricula($Prs_Cod, $Asi_Int)
{
	$rs_numero_matricula = cargar("SELECT count(notasgedet.Asi_Int) as Num_Matri FROM notasgedet, asignatura, 
						notasgener, matriculas, estudiante, persona WHERE notasgedet.Asi_Int = asignatura.Asi_Int AND 
						notasgener.Nge_Cod = notasgedet.Nge_Cod AND notasgener.Mat_Int = matriculas.Mat_Int AND persona.Prs_Cod = estudiante.Prs_Cod AND
						matriculas.Est_Int = estudiante.Est_Int AND persona.Prs_Cod = '$Prs_Cod' AND 
						notasgedet.Nge_Est = 'R' AND notasgedet.Asi_Int = $Asi_Int AND matriculas.Mat_Est = 'A'");
	$row_rs_numero_matricula = mysqli_fetch_assoc ($rs_numero_matricula);
	return $row_rs_numero_matricula['Num_Matri'];
	mysqli_free_result ($rs_numero_matricula);
}

//************Cuenta el numero de matriculas que tiene una determinada asignatura**********************************
function contar_matricula_todas($Est_Ced, $Asi_Int)
{
	$rs_numero_matricula = cargar("SELECT count(notasgedet.Asi_Int) as Num_Matri FROM notasgedet, asignatura, 
						notasgener, matriculas, estudiante, persona WHERE notasgedet.Asi_Int = asignatura.Asi_Int AND 
						notasgener.Nge_Cod = notasgedet.Nge_Cod AND notasgener.Mat_Int = matriculas.Mat_Int AND persona.Prs_Cod = estudiante.Prs_Cod AND
						matriculas.Est_Int = estudiante.Est_Int AND persona.Prs_Ced = '$Est_Ced' 
						AND notasgedet.Asi_Int = $Asi_Int");
	$row_rs_numero_matricula = mysqli_fetch_assoc ($rs_numero_matricula);
	return $row_rs_numero_matricula['Num_Matri'];
	mysqli_free_result ($rs_numero_matricula);
}
//************Devuelve el estado de la asignatura evaluando si tiene prerequisitos**********************************
function estado_prerequisito($Asi_Int, $array_arrastre)
{
	if (count($array_arrastre)==0)//Esto es en caso de no existir asignaturas con arrastre
	{
		$estado='P';
	}
	else
	{
		for ($i=1;$i<=count($array_arrastre);$i++)
		{
			//******************************Consulta de los Pre-requisito*************************************************************
			$rs_prerequisi = cargar ("SELECT prerequisi.Asi_Int, prerequisi.Pre_Req FROM asignatura, prerequisi WHERE
							asignatura.Asi_Int = prerequisi.Asi_Int AND prerequisi.Asi_Int = $Asi_Int AND prerequisi.Pre_Req
							= $array_arrastre[$i]");
			$total_rs_prerequisi = mysqli_num_rows($rs_prerequisi);
			if ($total_rs_prerequisi > 0)
			{
				$estado='I';
				break;
			}
			else
			{
				$estado='P';
			}
		}
	}
	return $estado;
	mysqli_free_result ($rs_prerequisi);
}	  
//Devuelve los semestres de periodo ACTUAL y nivel (1ro, 2do, 3ero....10mo) y con el mismo código de malla curricular***********************
function semestres_periodo($Per_Int, $Niv_Cod, $Mal_Cod)
{
	$rs_semestre = cargar ("SELECT semestres.Sem_Cod, niveles.Niv_Des, semestres.Sem_Par, IF (Sem_Sec = 'D', 'Diurna',
						IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as Sem_Sec, modalidad.Mod_Des FROM semestres, niveles, modalidad, 
						periodos, promocione WHERE semestres.Niv_Cod = niveles.Niv_Cod AND periodos.Mod_Cod = modalidad.Mod_Cod AND 
						periodos.Per_Int = semestres.Per_Int AND semestres.Per_Int = $Per_Int AND niveles.Niv_Cod = $Niv_Cod AND semestres.Pro_Cod = 
						promocione.Pro_Cod AND promocione.Mal_Cod = $Mal_Cod AND semestres.Sem_Est = 'A' ORDER BY semestres.Niv_Cod, semestres.Sem_Par, Sem_Sec");
	

	return $rs_semestre;
	
	mysqli_free_result ($rs_semestre);
}
//Devuelve los semestres del periodo actual, nivel y PROMOCION******************************************************
function semestres_promocione($Per_Int, $Niv_Cod, $Pro_Cod)
{
	$rs_semestre = cargar ("SELECT semestres.Sem_Cod, niveles.Niv_Des, semestres.Sem_Par, IF (Sem_Sec = 'D', 'Diurna',
							IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as Sem_Sec, modalidad.Mod_Des
							FROM semestres, niveles, modalidad, periodos WHERE semestres.Niv_Cod = niveles.Niv_Cod AND periodos.Mod_Cod
							= modalidad.Mod_Cod AND periodos.Per_Int = semestres.Per_Int AND semestres.Per_Int = $Per_Int AND niveles.Niv_Cod
							= $Niv_Cod AND semestres.Pro_Cod = $Pro_Cod AND semestres.Sem_Est = 'A' ORDER BY semestres.Niv_Cod, semestres.Sem_Par, Sem_Sec");
	return $rs_semestre;
	mysqli_free_result ($rs_semestres);
}
//Devuelve la promocion de un estudiante****************************************************************************
function estudiante_promocion($Est_Ced)
{
	$rs_promocione = cargar ("SELECT semestres.Pro_Cod FROM promocione, matriculas, semestres, estudiante, persona WHERE matriculas.Est_Int = estudiante.Est_Int
						AND persona.Prs_Ced = $Est_Ced AND matriculas.Sem_Cod = semestres.Sem_Cod AND semestres.Pro_Cod = promocione.Pro_Cod");
	$row_rs_promocione = mysqli_fetch_assoc ($rs_promocione);					
	return $row_rs_promocione['Pro_Cod'];
	mysqli_free_result ($rs_promocione);					
}
//Funcion que devuelve el periodo actual en base a parametros: Sea de clases o de matricula
function periodo_actual($ini, $fin, $tipo)
{	
	$rs_periodo = cargar("SELECT Per_Int FROM periodos WHERE now() >= $ini AND now() <= $fin AND Pem_Tip = '$tipo' AND Per_Est='A' ORDER BY $ini");
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

//Funcion que devuelve el nivel en el que se encuentra el estudiante, en una determinada carrera
function nivel_actual($Prs_Cod, $Car_Int)
{
	$rs_existe = cargar("SELECT semestres.Niv_Cod as nivel, promocione.Mal_Cod FROM semestres, matriculas, estudiante,
						promocione, carreras, persona WHERE semestres.Sem_Cod = matriculas.Sem_Cod AND matriculas.Est_Int
						= estudiante.Est_Int AND persona.Prs_Cod = estudiante.Prs_Cod AND promocione.Pro_Cod = semestres.Pro_Cod AND carreras.Car_Int =
						promocione.Car_Int AND persona.Prs_Cod = '$Prs_Cod' AND promocione.Car_Int = $Car_Int ORDER BY 
						semestres.Niv_Cod DESC"); //Se utiliza ORDER BY DESC para poder tener en el primer registro el codigo
						// de la ultima malla en el ultimo semetre
	
	return $rs_existe;
	mysqli_free_result ($rs_existe);		
}
//Funcion que devuelve el numero de asignaturas en proceso o  supletorio, en un determinado semestre

//function asignaturas_proceso ($Prs_Cod, $Car_Int) //OJO SE DEBE ARREGLAR ESTA FUNCION PARA QUE BUSCA ASIGNATURA EN PROCESOS HACIENDO DISTINCION DE LA CARRERA
//{
//	$rs_existe = cargar("SELECT count(notasgedet.Asi_Int) as cant FROM notasgener, notasgedet, asignatura, matriculas, estudiante,
//			semestres, promocione, persona
//			WHERE notasgener.Nge_Cod = notasgedet.Nge_Cod AND notasgedet.Asi_Int = asignatura.Asi_Int 
//			AND notasgener.Mat_Int = matriculas.Mat_Int AND persona.Prs_Cod = estudiante.Prs_Cod AND
//			matriculas.Sem_Cod = semestres.Sem_Cod AND semestres.Pro_Cod = promocione.Pro_Cod AND promocione.Car_Int = $Car_Int
//			AND matriculas.Est_Int = estudiante.Est_Int AND persona.Prs_Cod ='$Prs_Cod' AND (notasgedet.Nge_Est = 'P' OR notasgedet.Nge_Est = 'S')");			
//	$row_rs_existe = mysqli_fetch_assoc ($rs_existe);		
//	return 	$row_rs_existe['cant'];
//	mysqli_free_result ($rs_existe);
//}




//**************Devuelve el detalle del un periodo actual***********************
function detalle_periodo($Per_Int)//, $Tipo
{
	$rs_periodo = cargar("select Per_Int, YEAR(Per_Fea) as Ann_Ini, MONTH(Per_Fea) AS Mes_Numi, MONTH(Per_Fef) AS Mes_Numf, IF (MONTH(Per_Fea)=1,'Enero', IF (MONTH(Per_Fea)=2, 'Febrero', IF (
				MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', IF (MONTH(Per_Fea)=5, 'Mayo', IF(MONTH(Per_Fea)=6, 'Junio',
				IF (MONTH(Per_Fea)=7,'Julio', IF (MONTH(Per_Fea)=8, 'Agosto', IF (MONTH(Per_Fea)=9, 'Septiembre', IF (MONTH(Per_Fea)=10,
				'Octubre', IF (MONTH(Per_Fea)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Ini, YEAR(Per_Fef) as Ann_Fin,
				IF (MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 'Febrero', IF (
				MONTH(Per_Fef)=3, 'Marzo', IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', IF(MONTH(Per_Fef)=6, 'Junio',
				IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF (MONTH(Per_Fef)=9, 'Septiembre', IF (MONTH(Per_Fef)=10,
				'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Fin, Pem_Ord, Pem_Ext, Pem_Exc, Pem_Fin FROM periodos
				WHERE Per_Int = $Per_Int AND Per_Est = 'A'  ORDER BY Per_Fea");	//AND Pem_Tip = '$Tipo'							
	return $rs_periodo;
	mysqli_free_result ($rs_periodo);
}
//**************Devuelve todos los periodos de un tipo especifico Pem_Tip = Semestral o Pre-Universitario******************
function todos_periodos($Tipo)
{
	$rs_periodo = cargar("select modalidad.Mod_Des,Per_Int, YEAR(Per_Fea) as Ann_Ini, IF (MONTH(Per_Fea)=1,'Enero', IF (MONTH(Per_Fea)=2, 'Febrero', IF (
					MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', IF (MONTH(Per_Fea)=5, 'Mayo', IF(MONTH(Per_Fea)=6, 'Junio',
					IF (MONTH(Per_Fea)=7,'Julio', IF (MONTH(Per_Fea)=8, 'Agosto', IF (MONTH(Per_Fea)=9, 'Septiembre', IF (MONTH(Per_Fea)=10,
					'Octubre', IF (MONTH(Per_Fea)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Ini, YEAR(Per_Fef) as Ann_Fin,	
					IF (MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 'Febrero', IF (
					MONTH(Per_Fef)=3, 'Marzo', IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', IF(MONTH(Per_Fef)=6, 'Junio',
					IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF (MONTH(Per_Fef)=9, 'Septiembre', IF (MONTH(Per_Fef)=10,
					'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Fin FROM periodos, modalidad
					WHERE Per_Est = 'A' AND periodos.Mod_Cod=modalidad.Mod_Cod  AND Pem_Tip = '$Tipo' ORDER BY Per_Fea DESC");
	return $rs_periodo;
	mysqli_free_result ($rs_periodo);			
}	
	
	//****************Consulta de los datos de la ficha de matricula del estudiante********************************************
	//function detalle_matricula($Mat_Int)
	//{
	//	$rs_matriculas = cargar ("SELECT  modalidad.Mod_Des, matriculas.Mat_Cod, matriculas.Mat_Int, matriculas.Mat_Fol, semestres.Sem_Obs, matriculas.Mat_Fec, FLOOR(DATEDIFF(now(), persona.Prs_Fec)/360)as Edad, IF (matriculas.Mat_Tip = 'O', 'Ordinaria', IF (matriculas.Mat_Tip 
	//				= 'E', 'Extra-ordinaria', 'Excepcional')) as Mat_Tip, persona.Prs_Ced, persona.Prs_Cod, estudiante.Est_Int, persona.Prs_Nom, persona.Prs_Ape, IF 
	//				(persona.Prs_Esc = 'S', 'Soltero/a', IF (persona.Prs_Esc = 'C', 'Casado/a', IF (persona.Prs_Esc = 'D', 'Divorciado/a', IF (persona.Prs_Esc = 'V', 
	//				'Viudo/a', IF (persona.Prs_Esc = 'U','Union libre', ' '))))) as Prs_Esc, persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Fec, Pad_Nom, Pad_Ape, 
	//				Mad_Nom, Mad_Ape, niveles.Niv_Des, persona.Prs_San, persona.Prs_Cel, estudiante.Pad_Cor, estudiante.Mad_Cor, estudiante.Est_Rep, if 		
	//				(estudiante.Est_Par = 'P', 'Papá', if (estudiante.Est_Par = 'M', 'Mamá', if (estudiante.Est_Par = 'H', 'Hermano/a', if (estudiante.Est_Par = 'T', 
	//				'Tío/a', if (estudiante.Est_Par = 'O', 'Otro', ''))))) as Est_Par, Pad_Dir, Mad_Dir, Pad_Tel, Mad_Tel, Pad_Tel, Rep_Dir, Rep_Tel, Pad_Dit, Mad_Dit, Rep_Dit, Pad_Teo, Mad_Teo, Rep_Teo, Pad_Cel, Mad_Cel, Rep_Cel, Rep_Cor, 
	//				IF (semestres.Sem_Sec = 'D', 'Diurna', IF (semestres.Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as Sem_Sec, Sem_Par, 
	//				carreras.Car_Nom, Mat_Est FROM  matriculas, estudiante, semestres, niveles, promocione, carreras, persona, periodos, modalidad WHERE modalidad.Mod_Cod=periodos.Mod_Cod AND periodos.Per_Int=semestres.Per_Int AND matriculas.Est_Int = 
	//				estudiante.Est_Int AND matriculas.Sem_Cod = semestres.Sem_Cod AND semestres.Niv_Cod = niveles.Niv_Cod AND persona.Prs_Cod = estudiante.Prs_Cod AND
	//				promocione.Pro_Cod = semestres.Pro_Cod AND carreras.Car_Int = promocione.Car_Int AND matriculas.Mat_Int = $Mat_Int");	
	//	return $rs_matriculas;
	//	mysqli_free_result ($rs_matriculas);
	//}



//*****************Consulta del detalle de las asignaturas en la ficha de matricula********************************
function detalle_asignaturas($Mat_Int)
{
	$rs_asignaturas = cargar ("SELECT notasgedet.Asi_Int, asignatura.Asi_Des, niveles.Niv_Des, semestres.Sem_Par, IF (Sem_Sec = 'D', 'Diurna',
					IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as Sem_Sec, modalidad.Mod_Des, IF (notasgedet.Nge_His = 'I', 
					'Inhabilitada por arrastre', IF (notasgedet.Nge_His = 'H', 'Inhabilitada por pendiente', IF (notasgedet.Nge_His = 'P', 
					'Habilitada', IF (notasgedet.Nge_His = 'E', 'Pendiente', ' ')))) as Nge_His FROM notasgener, notasgedet, asignatura, semestres, niveles, modalidad, periodos WHERE 
					notasgener.Nge_Cod = notasgedet.Nge_Cod AND notasgedet.Asi_Int = asignatura.Asi_Int AND notasgener.Sem_Cod = 
					semestres.Sem_Cod AND semestres.Niv_Cod = niveles.Niv_Cod AND periodos.Mod_Cod = modalidad.Mod_Cod AND 
					notasgener.Mat_Int = $Mat_Int AND semestres.Per_Int = periodos.Per_Int");
	return $rs_asignaturas;
	mysqli_free_result ($rs_asignaturas);
}


//*******************Devuelve el codigo de la matricula automáticamente generado**********************************************************
//function codigo_matricula($Per_Int)
//{
	//*************Consulta para calcular el maximo número de matricula en el periodo vigente*****************************
	//$rs_consulta = cargar ("SELECT max(Mat_Cod) as Mat_Cod FROM matriculas, semestres, periodos WHERE matriculas.Sem_Cod = 
	//				semestres.Sem_Cod AND periodos.Per_Int = semestres.Per_Int AND semestres.Per_Int = $Per_Int");
	//$row_rs_consulta = mysqli_fetch_assoc ($rs_consulta);
	//$codigo = $row_rs_consulta['Mat_Cod'] + 1;
	//return $codigo;
	//mysqli_free_result ($rs_consulta);
//}

/* Devuelve el codigo del folio automáticamente generado */
function codigo_folio($Per_Int, $Esc_Int)
{
	//*************Consulta para calcular el maximo número del folio en el periodo vigente y la carrera********************
	
	$rs_consulta = cargar ("SELECT max(Mat_Fol) as Mat_Fol FROM matriculas, semestres, periodos, promocione, carreras WHERE matriculas.Sem_Cod = 
					semestres.Sem_Cod AND periodos.Per_Int = semestres.Per_Int AND semestres.Pro_Cod = promocione.Pro_Cod AND promocione.Car_Int =
					carreras.Car_Int AND semestres.Per_Int = $Per_Int AND carreras.Esc_Int = $Esc_Int");
	$row_rs_consulta = mysqli_fetch_assoc ($rs_consulta);
	$codigo = $row_rs_consulta['Mat_Fol'] + 1;
	return $codigo;
	mysqli_free_result ($rs_consulta);
}

//****************Devuelve el tipo de matrícula dependiendo de la fecha actual***********************************************************
function tipo_matricula($Per_Int)
{
	$rs_tipomatri = cargar ("SELECT IF (now() >= Pem_Exc, 'X', IF (now() >= Pem_Ext, 'E',
					IF (now() >= Pem_Ord, 'O', ''))) as Mat_Tip FROM periodos
					WHERE Per_Int = $Per_Int");
	$row_rs_tipomatri = mysqli_fetch_assoc ($rs_tipomatri);
	return $row_rs_tipomatri['Mat_Tip'];
	mysqli_free_result ($rs_tipomatri);				
}

//***************Devuelve "true" si la signatura ha sido vista y "false" si la asignatura no ha sido vista********************************
function asignatura_vista($Asi_Int, $Prs_Cod)
{	
	$rs_vista = cargar ("SELECT notasgedet.Asi_Int FROM notasgedet, notasgener, matriculas, estudiante, persona WHERE notasgener.Nge_Cod =
						notasgedet.Nge_Cod AND matriculas.Mat_Int = notasgener.Mat_Int AND estudiante.Est_Int = matriculas.Est_Int AND 
						persona.Prs_Cod = estudiante.Prs_Cod AND
						persona.Prs_Cod='$Prs_Cod' AND notasgedet.Asi_Int = $Asi_Int");
	$total_rs_vista = mysqli_num_rows($rs_vista);
	if ($total_rs_vista > 0)
	{
		return true;
	}
	else
	{
		return false;
	}
	mysqli_free_result ($rs_vista);
}

/* Devuelve el estado de la asignatura cuando se trata de una asignatura reprobada 'R' o pendiente 'E' */
function estado_asignatura($Asi_Int, $Est_Int, $Estados)
{
	/* Los estados pueden ser:
	E = pendiente, H = inhabilidao por pendiente
	R = reprobado, I = inhabilitado por arrastre */
	$Nge_Est = explode('*', $Estados);
	//***************************************************************************************************************************
	//***************Consulta todas las asignaturas que son pre-requisitos*******************************************************
	$rs_prerequisi = cargar("SELECT prerequisi.Pre_Req FROM asignatura, prerequisi WHERE asignatura.Asi_Int = 
							prerequisi.Asi_Int AND prerequisi.Asi_Int = $Asi_Int"); 														
							/*echo "SELECT prerequisi.Pre_Req FROM asignatura, prerequisi WHERE asignatura.Asi_Int = 
							prerequisi.Asi_Int AND prerequisi.Asi_Int = $Asi_Int";*/
	$row_rs_prerequisi = mysqli_fetch_assoc ($rs_prerequisi);
	$total_rs_prerequisi = mysqli_num_rows ($rs_prerequisi);
	//***************************************************************************************************************************
	if ($total_rs_prerequisi == 0)
	{
		$estado = 'P'; 
	}
	else //En caso de existir pre-requisitos, verifica que el pre-requisito este con estado pendiente para inhabilitarlo por pendiente
	{
		do{
			$Pre_Req = $row_rs_prerequisi['Pre_Req'];
			$rs_pendiente = cargar ("SELECT notasgedet.Nge_Est FROM notasgedet, notasgener, matriculas, estudiante
								WHERE notasgedet.Nge_Cod = notasgener.Nge_Cod AND notasgener.Mat_Int = matriculas.Mat_Int AND 		
								matriculas.Est_Int = estudiante.Est_Int AND matriculas.Est_Int = $Est_Int AND notasgedet.Asi_Int = 
								$Pre_Req AND (notasgedet.Nge_Est = '$Nge_Est[0]' OR notasgedet.Nge_Est = '$Nge_Est[1]')"); // Ojo se debe controlar q solo se puede inhabilitar por 
								/*echo "SELECT notasgedet.Nge_Est FROM notasgedet, notasgener, matriculas, estudiante
								WHERE notasgedet.Nge_Cod = notasgener.Nge_Cod AND notasgener.Mat_Int = matriculas.Mat_Int AND 		
								matriculas.Est_Int = estudiante.Est_Int AND matriculas.Est_Int = $Est_Int AND notasgedet.Asi_Int = 
								$Pre_Req AND (notasgedet.Nge_Est = '$Nge_Est[0]' OR notasgedet.Nge_Est = '$Nge_Est[1]')";*/
								//pendiente 1 asignatura y 1 por secuencia ojo
			$row_rs_pendiente = mysqli_fetch_assoc ($rs_pendiente);
			
			if ($row_rs_pendiente['Nge_Est'] == $Nge_Est[0] or $row_rs_pendiente['Nge_Est'] == $Nge_Est[1])//en caso de no servir borrar or $row_rs_pendiente['Nge_Est'] == 'H'
			{
				$estado = $Nge_Est[1];
				break;
			}
			else
			{
				$estado = 'P';
			}			
		}while ($row_rs_prerequisi = mysqli_fetch_assoc($rs_prerequisi));				
	}
	return $estado;	
	
	@mysqli_free_result ($rs_prerequisi);
	@mysqli_free_result ($rs_pendiente);
}//Fin del function estado_asignatura($Asi_Int, $Est_Int, $Estados)

//****************Devuelve el estado de la asignatura cuando se trata de una asignatura pendiente 'E'**************************************
function estado_pendiente($Asi_Int, $Est_Int)
{
	//***************************************************************************************************************************
	//***************Consulta todas las asignaturas que son pre-requisitos*******************************************************
	$rs_prerequisi = cargar("SELECT prerequisi.Pre_Req FROM asignatura, prerequisi WHERE asignatura.Asi_Int = 
							prerequisi.Asi_Int AND prerequisi.Asi_Int = $Asi_Int"); 							
	$row_rs_prerequisi = mysqli_fetch_assoc ($rs_prerequisi);
	$total_rs_prerequisi = mysqli_num_rows ($rs_prerequisi);
	//***************************************************************************************************************************
	if ($total_rs_prerequisi == 0)
	{
		$estado = 'P'; 
	}
	else //En caso de existir pre-requisitos, verifica que el pre-requisito este con estado pendiente para inhabilitarlo por pendiente
	{
		do{
			$Pre_Req = $row_rs_prerequisi['Pre_Req'];
			$rs_pendiente = cargar ("SELECT notasgedet.Nge_Est FROM notasgedet, notasgener, matriculas, estudiante
								WHERE notasgedet.Nge_Cod = notasgener.Nge_Cod AND notasgener.Mat_Int = matriculas.Mat_Int AND 		
								matriculas.Est_Int = estudiante.Est_Int AND matriculas.Est_Int = $Est_Int AND notasgedet.Asi_Int = 
								$Pre_Req AND (notasgedet.Nge_Est = 'E' OR notasgedet.Nge_Est = 'H')"); // Ojo se debe controlar q solo se puede inhabilitar por 
								//pendiente 1 asignatura y 1 por secuencia ojo
			$row_rs_pendiente = mysqli_fetch_assoc ($rs_pendiente);
			
			if ($row_rs_pendiente['Nge_Est'] == 'E' or $row_rs_pendiente['Nge_Est'] == 'H')//en caso de no servir borrar or $row_rs_pendiente['Nge_Est'] == 'H'
			{
				$estado = 'H';
				break;
			}
			else
			{
				$estado = 'P';
			}			
		}while ($row_rs_prerequisi = mysqli_fetch_assoc($rs_prerequisi));				
	}
	return $estado;	
	mysqli_free_result ($rs_prerequisi);
	
	if (isset($rs_pendiente))
	{
		mysqli_free_result ($rs_pendiente);
	}		
}

//********************Funcion que devuelve los colores de los estados de una asignatura*********************************************
function color_estado($estado)
{
	switch ($estado)
	{
		case 'P': //Habilitada - En proceso de estudio
			$color = '#0000FF'; //Azul
			break;
		case 'A': //Aprobada
			$color = '#006666'; //Verde
			break;
		case 'S': //Supletorio 
			$color = '#FF6633'; //Naranja
			break;
		case 'R': //Reprobada
			$color = '#FF0000'; //Rojo
			break;
		case 'I': //Inhabilitada 
			$color = '#333333';//Negra
			break;
		case 'E': //Pendiente
			$color = '#000066'; //Azul obscuro
			break;
		case 'H': //Inhabilitada por pendiente 
			$color = '#000066'; //Azul obscuro
			break;
	}
	return $color;
}

// Devuelve los semestres de periodo ACTUAL y nivel (1ro, 2do, 3ero....10mo) y con el mismo código de malla curricular, pero sobre todo que las asignatura
// function semestres_arrastre($Per_Int, $Niv_Cod, $Car_Int)
// {
//		$rs_semestre = cargar ("SELECT semestres.Sem_Cod, niveles.Niv_Des, semestres.Sem_Par, IF (Sem_Sec = 'D', 'Diurna', 
//						IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as Sem_Sec, modalidad.Mod_Des, promocione.Mal_Cod FROM semestres, niveles, modalidad, 
//						periodos, promocione WHERE semestres.Niv_Cod = niveles.Niv_Cod AND periodos.Mod_Cod = modalidad.Mod_Cod AND 
//						promocione.Car_Int='$Car_Int' AND
//						periodos.Per_Int = semestres.Per_Int AND semestres.Per_Int ='$Per_Int' AND niveles.Niv_Cod='$Niv_Cod' AND semestres.Pro_Cod = 
//						promocione.Pro_Cod AND semestres.Sem_Est = 'A' ORDER BY semestres.Niv_Cod, semestres.Sem_Par, Sem_Sec");
//	return $rs_semestre;
//	mysqli_free_result ($rs_semestre);
// }


//*************Funcion que vuelve true si la asignatura X esta actualmente activa en la malla curricular en su respectivo nivel**************************
function asignatura_vigente($Asi_Int, $Niv_Cod, $Mal_Cod)
{
	$rs_asignatura = cargar ("SELECT asignatura.Asi_Des FROM asignatura, disciplina, mallacurri WHERE asignatura.Dip_Cod = disciplina.Dip_Cod 
							AND disciplina.Mal_Cod = mallacurri.Mal_Cod AND asignatura.Niv_Cod = $Niv_Cod AND mallacurri.Mal_Cod = $Mal_Cod AND 
							asignatura.Asi_Int = $Asi_Int AND asignatura.Asi_Est = 'A'");
	$total_rs_asignatura = mysqli_num_rows ($rs_asignatura);
	
	if ($total_rs_asignatura > 0)
	{
		return "true";
	}
	else
	{
		return "false";
	}
	mysqli_free_result ($rs_asignatura);
}
//Funcion que devuelve el si el estudiante se encuentra en un semestres por arrastre
function listado_estudiante ($Mat_Int, $Sem_Cod)
{
	$rs_asterisco = cargar("SELECT notasgedet.Nge_Tip FROM notasgedet, notasgener WHERE notasgener.Nge_Cod = notasgedet.Nge_Cod AND 
						notasgener.Mat_Int = $Mat_Int AND notasgener.Sem_Cod = $Sem_Cod GROUP BY notasgedet.Nge_Tip");
	$row_rs_asterisco = mysqli_fetch_assoc ($rs_asterisco);
	$total_rs_asterisco = mysqli_num_rows ($rs_asterisco);
	
	if ($total_rs_asterisco == 1)
	{
		switch ($row_rs_asterisco['Nge_Tip']){
			case 'N':
				$asterisco = '';
				break;
			case 'A':
				$asterisco = '*';
				break;
			case 'R':
				$asterisco = '#';
		}
	}
	elseif ($total_rs_asterisco > 1)
	{
		$asterisco = '';	
	}
	
	return $asterisco;
	mysqli_free_result ($rs_asterisco);
}
//Funcion que devuelve el si el estudiante se encuentra en una asignatura de un semestres por arrastre
function listado_estudiante_asi ($Mat_Int, $Sem_Cod, $Asi_Int)
{
	$rs_asterisco = cargar("SELECT if (notasgedet.Nge_Tip = 'R', '#', if (notasgedet.Nge_Tip = 'A', '*', if (notasgedet.Nge_Tip = 'N', ' ', ' '))) 
						as Nge_Tip FROM notasgedet, notasgener WHERE notasgener.Nge_Cod = notasgedet.Nge_Cod AND notasgener.Mat_Int = $Mat_Int AND 
						notasgener.Sem_Cod = $Sem_Cod AND notasgedet.Asi_Int = $Asi_Int GROUP by notasgedet.Nge_Tip");
	$row_rs_asterisco = mysqli_fetch_assoc ($rs_asterisco);
	$total_rs_asterisco = mysqli_num_rows ($rs_asterisco);
	
	return $row_rs_asterisco['Nge_Tip'];
	mysqli_free_result ($rs_asterisco);
}

?>