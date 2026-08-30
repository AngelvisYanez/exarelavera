<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2012-11-25
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
		/**
		* Consulta la cantidad de usuarios por empresa en la base de datos
		*/
		$sql = "SELECT 
  sucursal.Suc_Cod,
  sucursal.Suc_Des,		
  sucursal.Emp_Cod,
  usuarios.Usu_Cod,
  empresas.Emp_Nom,
  empresas.Emp_Cor
FROM
  usuarios
  INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
  INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
WHERE
  usuarios.Usu_Ced = '$Par_Sql[0]' AND 
  empresas.Emp_Est = 'A' AND usuarios.Usu_Est='A' ORDER BY empresas.Emp_Cor ASC";
		return $sql;
		break;
		
		case 20:
		/**
		* Consulta la cantidad de usuarios por empresa que existen 
		*/
		$sql = "SELECT 
  sucursal.Emp_Cod,
  usuarios.Usu_Cod,
  empresas.Emp_Nom, empresas.Emp_Cor
FROM
  usuarios
  INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
  INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
WHERE
  Usu_Ced = '$Par_Sql[0]' AND empresas.Emp_Est = 'A'";
		return $sql;
		break;
		
	}
}
?>
