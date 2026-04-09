<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author José Ambuludí
 * @version 1.0
 * Fecha de actualización:	2016-06-03
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */
function sentencias_tipo_mante($id,$Par_Sql)
{
	switch($id)
	{
		case 1: 
		/* 
		* consulta  si existe otra maquina
		*/
		$con_tipo_poliz=  "SELECT Mar_Cod, Mar_Des FROM marca WHERE Mar_Des = '$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1]";	
		//echo $con_mar;
		return $con_tipo_poliz;
		break;

		/* 
		* Registra la marca
		*/
		case 2:
		$registra_tipo_poliz= "INSERT INTO tipo_mante (Tma_Des, Tma_Obs) VALUES (Trim(UPPER('$Par_Sql[Tma_Des]')), '$Par_Sql[Tma_Obs]')";		
		return $registra_tipo_poliz;
		break;

		/* 
		* Busqueda de las marcas 
		*/
		case 3:
		$busca_tipo_poliz_nom= "SELECT Mar_Cod, Mar_Des FROM marca WHERE Mar_Des LIKE '%$Par_Sql[0]%' AND Emp_Cod = $Par_Sql[1] ORDER BY Mar_Des ASC";
		//echo $busca_marca_nom;
		return $busca_tipo_poliz_nom;

		/* 
		* Carga datos de la marca 
		*/		
		case 4:
		$carga_tipo_poliz= "SELECT marca.Mar_Cod, marca.Mar_Des, marca.Mar_Est FROM marca WHERE marca.Mar_Cod = '$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1]";
		return $carga_tipo_poliz;
		break;

		case 5: 
		/* 
		* consulta  si existe otra maquina
		*/
		$con_tipo_poliz=  "SELECT Mar_Cod, Mar_Des FROM marca WHERE Mar_Des = '$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1] AND Mar_Cod != $Par_Sql[2]";	
		//echo $con_mar;
		return $con_tipo_poliz;
		break;

		/* 
		* Actualiza los datos de la marca 
		*/		
		case 6:
		$actualiza_tipo_poliz= "UPDATE marca SET  Mar_Des = Trim(UPPER('$Par_Sql[0]')) WHERE Mar_Cod = $Par_Sql[1]";
		//echo $actualiza_marca;
		return $actualiza_tipo_poliz;
		break;
	}
}
?>