<?php
/**
 * PERITO
 */
function sentencias_per($id,$Par_Sql)
{
	switch($id)
	{	
		/**
		 * Comprobar la existencia de un perito
		 */
		case 601:
			$sql = "SELECT Prs_Cod, Prs_Ced, Prs_Nom, Prs_Ape, ciudad.Ciu_Des
					FROM persona,ciudad
					WHERE Prs_Ced=$Par_Sql[0] AND persona.Ciu_Cod=ciudad.Ciu_Cod";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Registrar peritaje
		 */
		case 602:
			$sql = "INSERT INTO perito(Pri_Esp,Pri_Obs,Prs_Cod,Emp_Cod)
                    VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]');";
			//echo $sql;
			return $sql;
		break;
	}
}
?>