<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Alejandro Camacho
 * @version 2.0
 * Fecha de actualizaci�n:	2021/03/30
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administracion.LOGICA
 */


function sentencias_con($id,$Par_Sql)
{
	switch($id)
	{

		case 1:
		$sql = "SELECT usuarios.Usu_Cod,
			CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) as persona, 
			 usuarios.Suc_Cod, 
			 persona.Prs_Cod
			 FROM persona INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod)  
			 INNER JOIN sucursal as suc on suc.Suc_Cod = usuarios.Suc_Cod and suc.Emp_Cod= $Par_Sql[0]
			 WHERE usuarios.Usu_Cod IN (SELECT max(usuarios.Usu_Cod) FROM usuarios,sucursal WHERE usuarios.Suc_Cod=sucursal.Suc_Cod AND Emp_Cod =$Par_Sql[0] GROUP BY usuarios.Prs_Cod )
			 AND usuarios.Usu_Est = 'A'
			 ORDER BY persona ASC";

	    return $sql;
		break;

		case 2:
			$sql = "SELECT Emp_Cod, Emp_Nom from exa_master.empresas where Emp_Est = 'A' order by Emp_Nom Asc";
		    return $sql;
			break;


		case 3:
			$now = date("Y-m-d H:i:s");
 			$sql = "INSERT INTO tickets ( Tic_Tem, Tic_Des, Tic_Evi_Pro, Tic_Fec_Cre, Tic_Fec_Ter, Tic_Tel, Emp_Cod, Usu_Cod, Org_Cod, Pcs_Cod) VALUES "
                   . "('$Par_Sql[Tic_Tem]', '$Par_Sql[Tic_Des]', '$Par_Sql[Tic_Evi_Pro]', '$now', '$now','$Par_Sql[Tic_Tel]', '$Par_Sql[Emp_Cod]', '$Par_Sql[Usu_Cod]','$Par_Sql[Tic_Pro]','$Par_Sql[Tic_Acc]')";
				   //ChromePhp::log($sql);
				   return $sql;
		break;

		case 4:
			// $hoy = date("Y-m-d H:i:s");
			$filtro = "WHERE Tic_Fec_Cre between '$Par_Sql[fechaIni]' AND '$Par_Sql[fechaFin] 23:59:59' AND tickets.Emp_Cod = $Par_Sql[Emp_Cod] ";
			$sql = "SELECT 
					tickets.*,
					tickets.Tic_Des,
					exa_master.empresas.Emp_Nom,
					exa_master.empresas.Emp_Cod,
					IF(Tic_Est='0', 'Pendiente', IF(Tic_Est = '1', 'En Proceso', IF(Tic_Est = '2', 'Reabierto', 'Solucionado'))) as Tic_Est,
					organizado.Org_Des as Org_Sec,
					organizado.Org_Niv as codigo,
					(SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) from persona INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod) WHERE usuarios.Usu_Cod = tickets.Usu_Cod)as Prs_Nom,
					(SELECT Org_Des from organizado Where Org_Cod = codigo) as Org_Mod
					FROM tickets
					INNER JOIN exa_master.empresas ON (exa_master.empresas.Emp_Cod = tickets.Emp_Cod)
					INNER JOIN organizado ON (organizado.Org_Cod = tickets.Org_Cod)
					$filtro
					order by Tic_Fec_Cre desc";
				// $sql = "SELECT * FROM tickets";
			return $sql;
			break;

		case 5:
 		$sql = "UPDATE tickets SET Tic_Fec_Ter = '$Par_Sql[Tic_Fec_Ter]', Tic_Est = '$Par_Sql[Tic_Est]',Tic_Obs = '$Par_Sql[Tic_Obs]' WHERE Tic_Cod = '$Par_Sql[Tic_Cod]' ";
		return $sql;
		break;

		case 6:
 		$sql = "UPDATE tickets SET Tic_Est = '$Par_Sql[Tic_Est]' WHERE Tic_Cod = $Par_Sql[Tic_Cod] ";
		return $sql;
		break;

		case 7:
			$sql = "SELECT 
			tickets.*,
			tickets.Tic_Des,
			exa_master.empresas.Emp_Nom,
			exa_master.empresas.Emp_Cod,
			IF(Tic_Est='0', 'Pendiente', IF(Tic_Est = '1', 'En Proceso', IF(Tic_Est = '2', 'Reabierto', 'Solucionado'))) as Tic_Est,
			organizado.Org_Cod as Org_Sec,
			organizado.Org_Niv as codigo,
			(SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) from persona INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod) WHERE usuarios.Usu_Cod = tickets.Usu_Cod) as Prs_Nom,
			(SELECT Pcs_Lin from procesos Where procesos.Pcs_Cod = tickets.Pcs_Cod) as Pcs_Nom,
			(SELECT Org_Cod from organizado Where Org_Cod = codigo) as Org_Mod
			FROM tickets
			INNER JOIN exa_master.empresas ON (exa_master.empresas.Emp_Cod = tickets.Emp_Cod)
			INNER JOIN organizado ON (organizado.Org_Cod = tickets.Org_Cod)
			WHERE tickets.Tic_Cod = $Par_Sql[Tic_Cod] ";
		    return $sql;
			break;

		case 8:
 		$sql = "INSERT INTO tickets (Tic_Des, Tic_Obs, Tic_Fil, Tic_Cod) VALUES "
                        . "('$Par_Sql[Tic_Des]', '$Par_Sql[Tic_Obs]', '$Par_Sql[Tic_Fil]', '$Par_Sql[Tic_Cod_Re]')";
		return $sql;
		break;

		case 9:
 		$sql = "UPDATE tickets, tickets SET Tic_Des = '$Par_Sql[Tic_Des]', Tic_Obs = '$Par_Sql[Tic_Obs]', Tic_Fil = '$Par_Sql[Tic_Fil]' 
				WHERE tickets.Tic_Cod = tickets.Tic_Cod
				AND tickets.Tic_Cod = '$Par_Sql[Tic_Cod_Re]'";
		return $sql;
		break;

		case 10:
 		$sql = "UPDATE tickets SET Tic_Fec_Env = '$Par_Sql[Tic_Fec_Env]', Tic_Est = 'E' WHERE Tic_Cod = $Par_Sql[Tic_Cod_Re] ";
		return $sql;
		break;

		case 11:
 		$sql = "SELECT MAX(Tic_Cod) as Cod_Fut from tickets";
		 //ChromePhp::log($sql);
		return $sql;
		break;

		case 12:
 		$sql = "SELECT Ust_Tip from usuario_tipo WHERE Usu_Cod = $Par_Sql[0]";
		return $sql;
		break;

		case 13:
 		$sql = "UPDATE tickets SET Tic_Val = '$Par_Sql[Ses_Usu_Cod]' WHERE Tic_Cod = $Par_Sql[Tic_Cod] ";
		return $sql;
		break;

		case 14:
 		$sql = "SELECT * from tickets WHERE Tic_Cod = $Par_Sql[Tic_Cod]";
		return $sql;
		break;

		// TRAE LOS HIJOS DE LOS PROCESOS AL CUAL EL USUARIO TIENE PERMISO
		case 15:
			$sql = "SELECT organizado.Org_Des, organizado.Org_Cod from usuarperfi 
				INNER JOIN 	perfiorgan ON perfiorgan.Per_Cod = usuarperfi.Per_Cod
				INNER JOIN 	procesos ON procesos.Pcs_Cod = perfiorgan.Pcs_Cod
				INNER JOIN 	organizado ON organizado.Org_Cod = procesos.Org_Cod	
				WHERE usuarperfi.Usu_Cod = $Par_Sql[Usu_Cod] AND organizado.Org_Niv = $Par_Sql[Org_Niv]
        		GROUP BY organizado.Org_Cod
				ORDER BY organizado.Org_Des asc";
		   return $sql;
		   break;

		// TRAER LOS PROCESOS PADRES A LOS QUE TIENE PERMISO EL USUARIO
		case 16:
			
			$sql = "SELECT  organizado.Org_Niv,
					(SELECT org.Org_Des from organizado as org WHERE org.Org_Cod = organizado.Org_niv) as Des_Pad
					from usuarperfi
					INNER JOIN 	perfiorgan ON perfiorgan.Per_Cod = usuarperfi.Per_Cod
					INNER JOIN 	procesos ON procesos.Pcs_Cod = perfiorgan.Pcs_Cod
					INNER JOIN 	organizado ON organizado.Org_Cod = procesos.Org_Cod	
					WHERE usuarperfi.Usu_Cod = $Par_Sql[Usu_Cod]
					GROUP BY organizado.Org_Niv
					ORDER BY Des_Pad asc";
		   return $sql;
		   break;
		
		case 17:
			$sql = "SELECT procesos.Pcs_Cod, Pcs_Lin from usuarperfi 
					INNER JOIN 	perfiorgan ON perfiorgan.Per_Cod = usuarperfi.Per_Cod
					INNER JOIN 	procesos ON procesos.Pcs_Cod = perfiorgan.Pcs_Cod
					WHERE usuarperfi.Usu_Cod = $Par_Sql[Usu_Cod] AND Org_Cod = $Par_Sql[Org_Cod]
					ORDER BY Pcs_Lin asc";
			return $sql;
			break;

		case 20:
				$now = date("Y-m-d H:i:s");
				$shouldUpdateFilePath='';
				if(!($Par_Sql['Mod_Tic_Evi_Pro']=='')){
					$shouldUpdateFilePath=", Tic_Evi_Pro = '$Par_Sql[Mod_Tic_Evi_Pro]'";
				}
				$sql = "UPDATE tickets 
						SET Tic_Tem = '$Par_Sql[Mod_Tic_Tem]', Tic_Des = '$Par_Sql[Mod_Tic_Des]', Tic_Tel = '$Par_Sql[Mod_Tic_Tel]', Pcs_Cod = $Par_Sql[Mod_Tic_Acc], Org_Cod = $Par_Sql[Mod_Tic_Pro]".$shouldUpdateFilePath."
						WHERE Tic_Cod = $Par_Sql[Tic_Cod] AND Usu_Cod = $Par_Sql[Usu_Cod] AND Emp_Cod = $Par_Sql[Emp_Cod] AND Tic_Est = 0";
			return $sql;
		break;

		// CALIFICAR
		case 21:
			$sql = "UPDATE tickets SET Tic_Cal = $Par_Sql[Tic_Cal], Tic_Cal_Des = '$Par_Sql[Tic_Cal_Des]' WHERE Tic_Cod = $Par_Sql[Tic_Cod] ";
		return $sql;
	break;
	}
}?>