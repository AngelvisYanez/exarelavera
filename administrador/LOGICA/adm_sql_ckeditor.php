<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de creación:	2013-02-12
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */
function sentencias_log($id,$Par_Sql)
{
	switch($id)
	{
		case 1:
		$sql = "SELECT Emp_Who FROM empresas WHERE empresas.Emp_Cod = $Par_Sql[0]";
		return $sql;
		break;

		case 2:
		$sql = "UPDATE empresas SET Emp_Who = '$Par_Sql[1]' WHERE empresas.Emp_Cod = $Par_Sql[0]";
		return $sql;
		break;
		
	}
}
?>