<?Php
//************************************N O T A S***********************************************************************
//********************************************************************************************************************
//***********Funcion que carga la configuracion del modulo de notas***************************************************
//function notas()
//{
//	$rs_notas = cargar("SELECT Asi_Nor, Exa_Sup, Asi_Arr, Asi_Rev, Not_Cre, Not_Cod FROM confinotas WHERE Con_Cod = 1");
//	return $rs_notas;
//	
//}
//***********Funcion utilizada al asignar el estado de la asignatura**************************************************
//****************************************************************************************************************************
//***************Función que devuelve el codigo del estudiante en base al codigo de la matricula******************************
function estudiante_matricula($Mat_Int)
{
	$rs_estudiante = cargar ("SELECT matriculas.Est_Int FROM matriculas WHERE matriculas.Mat_Int = $Mat_Int");
	$row_rs_estudiante = mysqli_fetch_assoc ($rs_estudiante);
	return $row_rs_estudiante['Est_Int'];
}
//***************************Funcion que verifica y actualiza los estados de notas cuando el estado de falta es R= reprobado**************************************
?>