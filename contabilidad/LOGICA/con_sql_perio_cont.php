<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author juanpuxito
 * @version 1.0
 * Fecha de actualizaci�n:	27-05-2014
 *
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package Exa.Facturacion - OFSERCONT
 */
function sentencias_con($id,$Par_Sql)
{
	switch($id)
	{
		case 111: 
		/* Insertar periodos contables */
 		$sql = "INSERT INTO perio_cont(Pec_Fei,Pec_Fef) VALUES ('$Par_Sql[0]','$Par_Sql[1]')";
		return $sql;
		break;
	
		case 112: 
		/* Consulta la informaci�n relacionada con las fechas del periodo contable */
 		$sql = "SELECT perio_cont.Pec_Cod,perio_cont.Pec_Fei, perio_cont.Pec_Fef FROM perio_cont WHERE perio_cont.Pec_Fei >= '$Par_Sql[0]' OR perio_cont.Pec_Fef >= '$Par_Sql[1]' OR perio_cont.Pec_Fef BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'";
		//echo $sql;
		return $sql;
		break;	
		
		case 113: 
		/* Consulta la informaci�n relacionada con el c�digo del periodo contable */
 		$sql =	"SELECT Pec_Cod, Pec_Fei, Pec_Fef, YEAR(Pec_Fei) as Ann, Pla_Cod FROM perio_cont WHERE Pec_Cod ='$Par_Sql[0]'";
		//echo $sql;
		return $sql;
		break;	
				
		case 114: 
		/* Consulta la informaci�n relacionada con el a�o a buscar del periodo contable */
		$sql =	"SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est from perio_cont where YEAR(Pec_Fei) = '$Par_Sql[0]' 
		OR YEAR(Pec_Fef) = '$Par_Sql[0]'";
		return $sql;
		break;	
		
		case 115: 
		/* Modifica o actualiza los cambios realizados en la tabla de periodo contable */		
		$sql = "UPDATE perio_cont SET Pec_Fei = '$Par_Sql[0]', Pec_Fef ='$Par_Sql[1]' WHERE Pec_Cod = $Par_Sql[2]";
		return $sql;
		break;
		
		case 116: 
		/* Consulta la informaci�n relacionada con las fechas del periodo contable */
 		$sql = "SELECT perio_cont.Pec_Cod,perio_cont.Pec_Fei, perio_cont.Pec_Fef FROM perio_cont WHERE perio_cont.Pec_Fef >= '$Par_Sql[1]'";
		//echo $sql;
                return $sql;
		break;	
            

	}
}
?>