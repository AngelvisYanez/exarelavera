<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-05-10
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
		case 101:
		/* 
		* Consultar autorizacion SRI en base a la descripci�n */
		$sql = "SELECT 
				  autorizaci.Tic_Cod,
				  tipo_compr.Tic_Des,
				  tipo_compr.Tic_Sri,
				  puntos_imp.Pun_Des,
				  sucursal.Suc_Des,
				  autorizaci.Aut_Cod,
				  if(Aut_Tem='E','ELECTR&Oacute;NICA','NORMAL')AS Aut_Tem,
				  autorizaci.Aut_Sri,
				  autorizaci.Aut_Est,
                  autorizaci.Aut_Fci,
				  autorizaci.Aut_Cad,
				  autorizaci.Aut_Ini,
				  autorizaci.Aut_Fin
				FROM
				  autorizaci
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
				  INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
				  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
				WHERE
				  autorizaci.Pun_Cod = '$Par_Sql[0]' AND autorizaci.Tic_Cod = '$Par_Sql[1]'";
		//echo $sql;
		return $sql;
		break;			

		case 103:
		/* 
		* Insertar autorizaci�n 
		*/
 		$autoriza = "INSERT INTO autorizaci(Pun_Cod,Tic_Cod,Aut_Sri,Pun_Sri,Aut_Fci,Aut_Cad,Aut_Ini,Aut_Fin,Aut_Adv,Aut_Ads) VALUES ('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]', $Par_Sql[8], $Par_Sql[9])";
		//echo $autoriza;
		return $autoriza;
		break;
	

        case 104: 
		/*
		* Cierre de la autorizaci�n
		*/
		$sql="UPDATE autorizaci SET Aut_Est= 'I' WHERE Tic_Cod = '$Par_Sql[0]' AND Pun_Cod = '$Par_Sql[1]'";
		//echo $sql;
		return $sql;
		break;

		case 109: 
		/* 
		* Modifica o actualiza los cambios realizados en la tabla de autorozaciones 
		*/
 		$sql = "UPDATE autorizaci SET Aut_Est='$Par_Sql[0]' WHERE Aut_Cod = '$Par_Sql[1]'";
		//echo $sql;
		return $sql;
		break;	

		/*
		* Carga las sucursales de la empresa
		*/
		case 507:
		$Sql_507="SELECT Suc_Cod, Suc_Sri, Suc_Des FROM sucursal WHERE Suc_Est = 'A' AND Emp_Cod = $Par_Sql[0]";
		//echo $Sql_507;
		return $Sql_507;
		break;
	
		// Carga las sucursales de la Utsam
		case 508:
		$Sql_508="SELECT Pun_Cod, Suc_Cod, Pun_Des FROM puntos_imp WHERE Pun_Est = 'A' AND Suc_Cod= $Par_Sql[0]";
		//echo $Sql_508;
		return $Sql_508;
		break;
	
		// Carga todos los documentos segun el Punto de impresion
		case 509:
		$Sql_509="SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Des FROM tipo_compr WHERE   	
		tipo_compr.Tic_Est='A' AND tipo_compr.Tic_Cod NOT IN (SELECT autorizaci.Tic_Cod FROM autorizaci WHERE autorizaci.Pun_Cod = $Par_Sql[0] AND 	
		autorizaci.Aut_Est = 'A')";
		//echo $Sql_509;
		return $Sql_509;
		break;

		/*
		* Carga todos los documentos segun el Punto de impresion
		*/
		case 510:
		$Sql_510="SELECT *FROM autorizaci WHERE autorizaci.Aut_Cod = $Par_Sql[0] AND autorizaci.Aut_Est = 'A'";
		//echo $Sql_510;
		return $Sql_510;
		break;
		
		/*
		* Carga todos los documentos segun el Punto de impresion
		*/
		case 511:
		$sql="SELECT DISTINCT 
				  autorizaci.Tic_Cod,
				  tipo_compr.Tic_Des,
				  tipo_compr.Tic_Sri
			  FROM
				  autorizaci
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
			  WHERE
				  autorizaci.Pun_Cod='$Par_Sql[0]'";
		//echo $sql;
		return $sql;
		break;
		
	}
}?>