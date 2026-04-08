<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2012-05-11
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package contabilidad.LOGICA
 */
function sentencias_con($id,$Par_Sql)
{
	switch($id)
	{
		/*
		* Carga las sucursales de la empresa
		*/
		case 1:
		$Sql_1="SELECT Suc_Cod, Suc_Sri, Suc_Des FROM sucursal WHERE Suc_Est = 'A' AND Emp_Cod = $Par_Sql[0]";
		//echo $Sql_1;
		return $Sql_1;
		break;
		
		/*
		* Consulta los puntos de impresion por la descripcion
		*/
		case 2:
		$Sql_2="SELECT Pun_Cod FROM puntos_imp WHERE Pun_Des = '$Par_Sql[0]' AND Suc_Cod = $Par_Sql[1]";
		//echo $Sql_2;
		return $Sql_2;
		break;

		/*
		* Insertar puntos de impresión
		*/
		case 3:
		$Sql_3="INSERT INTO puntos_imp (Suc_Cod, Pun_Des, Pun_Ubi) VALUES ($Par_Sql[0], '$Par_Sql[1]', '$Par_Sql[2]')";
		//echo $Sql_3;
		return $Sql_3;
		break;

		/*
		* Consultar puntos de impresión
		*/
		case 4:
		$Sql_4="SELECT Pun_Cod, Pun_Des, Pun_Ubi, Pun_Est FROM puntos_imp WHERE Pun_Des LIKE '%$Par_Sql[1]%' AND Suc_Cod = $Par_Sql[0]";
		//echo $Sql_4;
		return $Sql_4;
		break;

		/*
		* Consulta un puntos de impresión específico
		*/
		case 5:
		$Sql_5="SELECT Suc_Cod, Pun_Cod, Pun_Des, Pun_Ubi, Pun_Est FROM puntos_imp WHERE Pun_Cod = $Par_Sql[0]";
		//echo $Sql_5;
		return $Sql_5;
		break;

		/*
		* Consulta los puntos de impresion por la descripcion
		*/
		case 6:
		$Sql_6="SELECT Pun_Cod FROM puntos_imp WHERE Pun_Des = '$Par_Sql[0]' AND Suc_Cod = $Par_Sql[1] AND Pun_Cod != $Par_Sql[2]";
		//echo $Sql_6;
		return $Sql_6;
		break;

		/*
		* Actualizar puntos de impresión
		*/
		case 7:
		$Sql_7="UPDATE puntos_imp SET Suc_Cod = $Par_Sql[0], Pun_Des = '$Par_Sql[1]', Pun_Ubi = '$Par_Sql[2]' WHERE Pun_Cod = $Par_Sql[3]";
		//echo $Sql_7;
		return $Sql_7;
		break;

		
	}
}?>