<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * Autor: Fabian Gallardo G.
 * Fecha: 2013-09-10
 * 
 * @package tesoreria.LOGICA
 */
function sentencias_ubi($id,$Par_Sql)
{
	switch($id)
	{
		/**
		* Tomar el codigo Ubi_Rec de un codigo determinado. Sirve para regresar de un directorio 
		*/
		case 10:
		$sql = "SELECT Ubi_Cod, Ubi_Rec, Ubi_Des, Ubi_Obs, Ubi_Est FROM ubicacion, empresas WHERE empresas.Emp_Cod = ubicacion.Emp_Cod AND empresas.Emp_Cod = $Par_Sql[0] AND Ubi_Cod = $Par_Sql[1];";
		echo $sql.'<br>';
		return $sql;
		break;	
		
		/**
		* Tomar la descripcion Ubi_Des del nivel superior. Sirve para regresar de un directorio
		*/
		case 20:
		$sql = "SELECT Ubi_Des FROM ubicacion WHERE Ubi_Cod = $Par_Sql[0];";
		return $sql;
		break;
		
		/**
		* Tomar todos los ubicaciones de un nivel, cualquiera que este sea 
		*/
		case 30:
		$sql = "SELECT Ubi_Cod, Ubi_Rec, Ubi_Des, Ubi_Obs, Ubi_Est FROM ubicacion, empresas WHERE empresas.Emp_Cod = ubicacion.Emp_Cod AND Ubi_Rec = $Par_Sql[0] AND empresas.Emp_Cod = $Par_Sql[1]";
		//echo $sql.'<br>';
		return $sql;
		break;
		
		/**
		* Insertar una nueva ubicacion
		*/
		case 40:
		$sql = "INSERT INTO ubicacion ( Ubi_Des, Ubi_Obs, Ubi_Rec, Emp_Cod) VALUES (UPPER('$Par_Sql[0]'),UPPER('$Par_Sql[1]'),$Par_Sql[2], $Par_Sql[3])";	
		//echo $sql.'<br>';		
		return $sql;
		break;
		
		/**
		* Modificar una ubicacion
		*/
		case 50:
		$sql = "UPDATE ubicacion SET Ubi_Des=UPPER('$Par_Sql[0]'), Ubi_Obs=UPPER('$Par_Sql[1]')  WHERE Ubi_Cod = $Par_Sql[2]";
		//echo $sql.'<br>';		
		return $sql;
		break;
		
		/**
		* Activa o desactiva una ubicacion
		*/
		case 60:
		$sql = "UPDATE ubicacion SET Ubi_Est=UPPER('$Par_Sql[0]') WHERE Ubi_Cod = $Par_Sql[1]";
		//echo $sql.'<br>';		
		return $sql;
		break;
	}
}
?>