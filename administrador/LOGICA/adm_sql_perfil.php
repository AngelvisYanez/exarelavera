<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualizaci�n:	2012-04-18
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */
	function sentencias_admp($id,$Par_Sql)
	{
		switch($id)
		{	
			case 1:
			/* Inserta nuevos perfiles */
			$sql = "INSERT INTO perfiles(Per_Des,Emp_Cod) VALUES ('$Par_Sql[0]',$Par_Sql[1])";
			//echo $sql."<br>";
			return $sql;
			break;
			
			case 2:
			/* Inserta los procesos de un perfil */
			$sql = "INSERT INTO perfiorgan(Per_Cod,Pcs_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
			//echo $sql."<br>";
			return $sql;
			break;
			
			case 3:
			/* Elimina los procesos de un perfil */
			$sql = "DELETE FROM perfiorgan WHERE Per_Cod = $Par_Sql[0]";
			//echo $sql."<br>";
			return $sql;
			break;
			
			case 4:
			/* Consulta los perfiles de manera individual */
			$sql = "SELECT perfiles.Per_Cod, perfiles.Per_Des, perfiles.Per_Obs, perfiles.Per_Est FROM perfiles WHERE perfiles.Per_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;
			
			case 5:
			/* Consulta los perfiles por empresa */
			$sql = "SELECT perfiles.Per_Cod, perfiles.Per_Des, perfiles.Per_Est FROM perfiles WHERE perfiles.Emp_Cod = $Par_Sql[0] ORDER BY perfiles.Per_Des";
	  		//echo $sql;
			return $sql;
			break;
			
			case 6:
			/* Da de baja el perfil */
			$sql = "UPDATE perfiles SET Per_Est='$Par_Sql[0]' WHERE Per_Cod = $Par_Sql[1]";
	  		//echo $sql;
			return $sql;
			break;
			
			case 7:
			/* Consulta el proceso de manera individual */
			$sql = "SELECT perfiorgan.Per_Cod, perfiorgan.Pcs_Cod FROM perfiorgan WHERE perfiorgan.Per_Cod = $Par_Sql[0] AND perfiorgan.Pcs_Cod = $Par_Sql[1]";
			//echo $sql."<br>";
			return $sql;
			break;
			
			case 8:
			/* Consulta los organizados */
			$sql = "SELECT Org_Niv, Org_Cod, Org_Des, Org_Mod, Org_Img, Org_Ime FROM organizado WHERE Org_Niv=$Par_Sql[0]";
			//echo $sql."<br>";
			return $sql;
			break;
			
			case 9:
			/* Consulta los organizados en base al codigo */
			$sql = "SELECT Org_Cod, Org_Des, Org_Mod, Org_Det, Org_Niv FROM organizado WHERE Org_Cod=$Par_Sql[0]";
			//echo $sql."<br>";
			return $sql;
			break;
			
			case 10:
			/* Consulta los procesos de cada organizador para administracion */
			$sql = "SELECT Org_Cod, Pcs_Cod,Pcs_Lin,Rut_Des,Pcs_Nom,Pcs_Tip,Pcs_Det, IF (Pcs_Tip = 'P', 'PROCESO', IF (Pcs_Tip = 'R', 'REPORTE', '')) as Tipo, Pcs_Ord, Rut_Des, Pcs_Det FROM procesos, rutas WHERE procesos.Rut_Cod=rutas.Rut_Cod AND Pcs_Est='A' AND Org_Cod=".$Par_Sql[0]." ORDER BY Pcs_Ord";
			//echo $sql."<br>";
			return $sql;
			break;
			
			case 11:
			/* Elimina los perfiles que contengan un perfil */
			$sql = "DELETE FROM perfiorgan WHERE Pcs_Cod = $Par_Sql[0]";
				return $sql;
			break;
		}
	}
?>