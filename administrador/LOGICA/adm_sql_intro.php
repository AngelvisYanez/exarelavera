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
		$sql = "SELECT Upl_Rut, Upl_Des, Upl_Cod, Upl_Url FROM upload, tipo_archivo WHERE upload.Tia_Cod = tipo_archivo.Tia_Cod AND upload.Upl_Cad >= '$Par_Sql[0]'
		AND tipo_archivo.Emp_Cod = $Par_Sql[1] ORDER BY Upl_Sys DESC";
		return $sql;
		break;
	}
}
?>