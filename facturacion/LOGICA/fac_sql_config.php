<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Jose Cumbicos
 * @version 1.0
 * Fecha de creacion:	2015-03-30
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package facturacion.LOGICA
 */
function sentencias_cfg($id,$Par_Sql)
{
	switch($id)
	{
		case 1: 
		/* 
		* consulta  si existe otra maquina
		*/
		$sql=  "SELECT Emp_Cod,Cof_Cod,Cof_Con,Cof_Gce,Cof_Fac,Cof_Fte,Cof_Clv FROM confi_fact WHERE Emp_Cod = '$Par_Sql[0]'";	
		//echo $sql;
		return $sql;
		break;

		/* 
		* Registra configuración
		*/
		case 2:
		$sql= "INSERT INTO confi_fact (Emp_Cod,Cof_Con,Cof_Gce,Cof_Fac,Cof_Fte) VALUES ('$Par_Sql[0]', '$Par_Sql[1]','$Par_Sql[2]','1','1')";		
		return $sql;
		break;
		
		/* 
		* Actualiza los datos de la marca 
		*/		
		case 3:
		$sql= "UPDATE confi_fact SET Cof_Con ='$Par_Sql[0]',Cof_Gce='$Par_Sql[1]' WHERE Emp_Cod = '$Par_Sql[2]'";
		//echo $sql;
		return $sql;
		break;
	}
}
?>