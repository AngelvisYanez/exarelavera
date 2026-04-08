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
		* consulta la configuracion existente
		*/
		$sql=  "SELECT Emp_Cod,Cof_Cod,Cof_Con,Cof_Gce,Cof_Fac,Cof_Fte,Cof_Clv FROM confi_fact WHERE Emp_Cod = '$Par_Sql[0]'";	
		//echo $sql;
		return $sql;
		break;

		/* 
		* Actualiza el tipo de ambiente 
		*/		
		case 2:
		$sql= "UPDATE confi_fact SET Cof_Fte ='$Par_Sql[0]', Cof_Clv='$Par_Sql[1]' WHERE Emp_Cod = '$Par_Sql[2]'";
		//echo $sql;
		return $sql;
		break;
	}
}
?>