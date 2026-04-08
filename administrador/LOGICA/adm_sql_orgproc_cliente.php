<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualización:	2012-04-18
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */

	

	function sentencias_admo($id,$Par_Sql)
	{
		switch($id)
		{	
			/*  Insercion de los directorios */
			case 1:
			$sql="INSERT INTO organizado (Org_Niv, Org_Det, Org_Des, Org_Img, Org_Ime)  
				VALUE ($Par_Sql[0], '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]')";
			return $sql;
			break;
			
			/*  Insercion de los directorios */
			case 2:
			$sql="INSERT INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Rut_Cod, Pcs_Tip, Pcs_Det, Tpr_Cod, Pcs_Ord)  
				VALUE ($Par_Sql[0], '$Par_Sql[1]', '$Par_Sql[2]', $Par_Sql[3], '$Par_Sql[4]', '$Par_Sql[5]', $Par_Sql[6], $Par_Sql[7])";
			return $sql;
			break;
			
			case 3:
			/* Consulta los organizados */
			$sql = "SELECT Org_Niv, Org_Cod, Org_Des, Org_Mod, Org_Img, Org_Ime FROM organizado WHERE Org_Niv=$Par_Sql[0]";
			return $sql;
			break;
			
			case 4:
			/* Consulta los organizados en base al codigo */
			$sql = "SELECT Org_Cod, Org_Des, Org_Mod, Org_Det, Org_Niv FROM organizado WHERE Org_Cod=$Par_Sql[0]";
			return $sql;
			break;
			
			case 5:
			/* Consulta los procesos de cada organizador para administracion */
			$sql = "SELECT Org_Cod, Pcs_Cod,Pcs_Lin,Rut_Des,Pcs_Nom,Pcs_Tip,Pcs_Det, IF (Pcs_Tip = 'P', 'PROCESO', IF (Pcs_Tip = 'R', 'REPORTE', '')) as Tipo, Pcs_Ord, Rut_Des, Pcs_Det FROM procesos, rutas WHERE procesos.Rut_Cod=rutas.Rut_Cod AND Pcs_Est='A' AND Org_Cod=".$Par_Sql[0]." ORDER BY Pcs_Ord";
			return $sql;
			break;
			
			case 6:
			/*  Consulta las rutas activas */
			$sql="SELECT Rut_Cod, Rut_Des, Rut_De2 FROM rutas WHERE Rut_Est = 'A'";
			return $sql;
			break;
			
			case 7:
			/*  Consulta los tipos de acceso al sistema */
			$sql="SELECT tipo_proce.Tpr_Cod, tipo_proce.Tpr_Des FROM tipo_proce WHERE tipo_proce.Tpr_Est = 'A'";
			return $sql;
			break;
			
			case 8:
			/* Consulta el proceso especifico */
			$sql = "SELECT Pcs_Cod,Pcs_Lin,rutas.Rut_Cod, Rut_Des,Pcs_Nom,Pcs_Tip,Pcs_Det, IF (Pcs_Tip = 'P', 'PROCESO', IF (Pcs_Tip = 'R', 'REPORTE', '')) as Tipo, Pcs_Ord, Rut_Des, Pcs_Det, tipo_proce.Tpr_Cod, tipo_proce.Tpr_Des  FROM procesos, rutas, tipo_proce WHERE tipo_proce.Tpr_Cod = procesos.Tpr_Cod AND procesos.Rut_Cod=rutas.Rut_Cod AND Pcs_Est='A' AND Pcs_Cod =".$Par_Sql[0];
			return $sql;
			break;
			
			/*  Modificacion de los directorios */
			case 9:
			$sql="UPDATE organizado SET Org_Niv = $Par_Sql[0], Org_Det = '$Par_Sql[1]', Org_Des = '$Par_Sql[2]', Org_Img = '$Par_Sql[3]', Org_Ime = '$Par_Sql[4]' WHERE Org_Cod = $Par_Sql[5]";
			return $sql;
			break;
			
			/*  Modificaciom de los directorios */
			case 10:
			$sql="UPDATE procesos SET Pcs_Lin = '$Par_Sql[1]', Pcs_Nom = '$Par_Sql[2]', Rut_Cod = $Par_Sql[3], Pcs_Tip = '$Par_Sql[4]',
					Pcs_Det = '$Par_Sql[5]', Tpr_Cod = $Par_Sql[6], Pcs_Ord = $Par_Sql[7] WHERE Org_Cod = $Par_Sql[0] AND Pcs_Cod = $Par_Sql[8]";
			return $sql;
			break;
			case 11:
			$sql = "SELECT perfiles.Per_Cod, perfiles.Per_Des, perfiles.Per_Est FROM perfiles WHERE perfiles.Emp_Cod = $Par_Sql[0] AND perfiles.Per_Des!= 'Administrador de Sistemas' ORDER BY perfiles.Per_Cod";
			return $sql;
			break;
			case 12:
			$sql = "SELECT perfiles.Per_Cod, procesos.Pcs_Cod from perfiles 
				  inner join perfiorgan on perfiles.per_cod = perfiorgan.per_cod
				  inner join procesos on perfiorgan.pcs_cod = procesos.pcs_cod
				  inner join organizado on procesos.org_cod = organizado.org_cod
				  where perfiles.per_cod = $Par_Sql[0]";
			return $sql;
			break;
			case 13:
			$sql = "SELECT Pcs_Cod,Pcs_Tip,Pcs_Lin,Pcs_Nom,Pcs_Det,Pcs_Ico,Pcs_Est,Pcs_Int from procesos where pcs_int = 'N'";
			return $sql;
			break;
		}
	}
?>