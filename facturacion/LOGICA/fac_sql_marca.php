<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2012-06-01
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */
function sentencias_mar($id,$Par_Sql)
{
	switch($id)
	{
		case 1: 
		/* 
		* consulta  si existe otra maquina
		*/
		$con_mar=  "SELECT Mar_Cod, Mar_Des FROM marca WHERE Mar_Des = '$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1]";	
		//echo $con_mar;
		return $con_mar;
		break;

		/* 
		* Registra la marca
		*/
		case 2:
		$registra_marca= "INSERT INTO marca (Mar_Des, Emp_Cod) VALUES (Trim(UPPER('$Par_Sql[0]')), $Par_Sql[1])";		
		return $registra_marca;
		break;

		/* 
		* Busqueda de las marcas 
		*/
		case 3:
		$busca_marca_nom= "SELECT Mar_Cod, Mar_Des FROM marca WHERE Mar_Des LIKE '%$Par_Sql[0]%' AND Emp_Cod = $Par_Sql[1] ORDER BY Mar_Des ASC";
		//echo $busca_marca_nom;
		return $busca_marca_nom;

		/* 
		* Carga datos de la marca 
		*/		
		case 4:
		$carga_marca= "SELECT marca.Mar_Cod, marca.Mar_Des, marca.Mar_Est FROM marca WHERE marca.Mar_Cod = '$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1]";
		return $carga_marca;
		break;

		case 5: 
		/* 
		* consulta  si existe otra maquina
		*/
		$con_mar=  "SELECT Mar_Cod, Mar_Des FROM marca WHERE Mar_Des = '$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1] AND Mar_Cod != $Par_Sql[2]";	
		//echo $con_mar;
		return $con_mar;
		break;

		/* 
		* Actualiza los datos de la marca 
		*/		
		case 6:
		$actualiza_marca= "UPDATE marca SET  Mar_Des = Trim(UPPER('$Par_Sql[0]')) WHERE Mar_Cod = $Par_Sql[1]";
		//echo $actualiza_marca;
		return $actualiza_marca;
		break;
	}
}
?>