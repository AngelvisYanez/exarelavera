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
function sentencias_tipo_poliz($id,$Par_Sql)
{
	switch($id)
	{
		case 1: 
		/* 
		* consulta  si existe otra maquina
		*/
		$con_tipo_poliz=  "SELECT Tip_Cod, Tip_Des FROM tipo_poliza WHERE Tip_Des = '$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1]";	
		//echo $con_mar;
		return $con_tipo_poliz;
		break;

		/* 
		* Registra la marca
		*/
		case 2:
		$registra_tipo_poliz= "INSERT INTO tipo_poliza (Tip_Des, Tip_Obs) VALUES (Trim(UPPER('{$Par_Sql['Tip_Des']}')), '{$Par_Sql['Tip_Obs']}')";		
		return $registra_tipo_poliz;
		break;

		/* 
		* Busqueda de las marcas 
		*/
		case 3:
		$busca_tipo_poliz_nom= "SELECT Tip_Cod, Tip_Des FROM tipo_poliza WHERE Tip_Des LIKE '%$Par_Sql[0]%' AND Emp_Cod = $Par_Sql[1] ORDER BY Tip_Des ASC";
		//echo $busca_marca_nom;
		return $busca_tipo_poliz_nom;

		/* 
		* Carga datos de la marca 
		*/		
		case 4:
		$carga_tipo_poliz= "SELECT Tip_Cod, Tip_Des, Tip_Est FROM tipo_poliza WHERE Tip_Cod = '$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1]";
		return $carga_tipo_poliz;
		break;

		case 5: 
		/* 
		* consulta  si existe otra maquina
		*/
		$con_tipo_poliz=  "SELECT Tip_Cod, Tip_Des FROM tipo_poliza WHERE Tip_Des = '$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1] AND Tip_Cod != $Par_Sql[2]";	
		//echo $con_mar;
		return $con_tipo_poliz;
		break;

		/* 
		* Actualiza los datos de la marca 
		*/		
		case 6:
		$actualiza_tipo_poliz= "UPDATE tipo_poliza SET  Tip_Des = Trim(UPPER('$Par_Sql[0]')) WHERE Tip_Cod = $Par_Sql[1]";
		//echo $actualiza_marca;
		return $actualiza_tipo_poliz;
		break;
	}
}
?>