<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-11-25
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
		* Consulta la cantidad de usuarios por empresa en la base de datos MASTER
		*/
		$sql = "SELECT 
  sucursal.Suc_Cod,
  sucursal.Suc_Des,		
  sucursal.Emp_Cod,
  access.Dat_Cod,
  empresas.Emp_Nom,
  empresas.Emp_Cor
FROM
  access
  INNER JOIN sucursal ON (access.Suc_Cod = sucursal.Suc_Cod)
  INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
WHERE
  Acc_Usr = '$Par_Sql[0]' AND 
  empresas.Emp_Est = 'A' AND access.Acc_Est='A' order by empresas.Emp_Cor Asc";
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