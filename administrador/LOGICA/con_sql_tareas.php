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
			$sql = "SELECT Emp_Cod, Emp_Nom, Emp_Cor from exa_master.empresas where Emp_Est = 'A' order by Emp_Cor Asc";
		    return $sql;
			break;


		case 3:
 		$sql = "INSERT INTO tarea (Tar_Fec, Tar_Des, Tar_Fil, Tar_Fec_Ent, Tar_Cre, Tar_Res, Emp_Cod) VALUES "
                        . "('$Par_Sql[Tar_Fec]', '$Par_Sql[Tar_Des]', '$Par_Sql[Tar_Fil]', '$Par_Sql[Tar_Fec_Ent]', '$Par_Sql[Tar_Cre]', '$Par_Sql[Tar_Res]', '$Par_Sql[Emp_Cod]')";
		return $sql;
		break;

		case 4:
			$fecha = "tarea.Tar_Fec_Ent between '$Par_Sql[fechaIni]' and '$Par_Sql[fechaFin]  23:59:59' ";
			
			if($Par_Sql['responsable'] != 'T' && $Par_Sql['estado'] != 'T'){
				$filtro = " WHERE tarea.Tar_Res = $Par_Sql[responsable] AND tarea.Tar_Est = '$Par_Sql[estado]' AND " . $fecha;
			}
			else if ($Par_Sql['responsable'] != 'T'){
				$filtro = " WHERE tarea.Tar_Res = $Par_Sql[responsable] AND " . $fecha;
			}
			else if ($Par_Sql['estado'] != 'T'){
				$filtro = " WHERE tarea.Tar_Est = '$Par_Sql[estado]' AND " . $fecha;
			}
			else{
				$filtro = " WHERE " . $fecha;
			}

			$sql = "SELECT tarea.Tar_Cod, tarea.Tar_Fec, tarea.Tar_Cre, tarea.Tar_Res, tarea.Tar_Val, tarea.Tar_Fil, tarea.Tar_Fec_Ent, tarea.Tar_Fec_Env,tarea.Tar_Des as Tar_Des,
					  IF(Tar_Est='A', 'Activo', IF(Tar_Est = 'I', 'Inactivo', IF(Tar_Est = 'E', 'Entregado', 'Validado'))) as Tar_Est,
				   	   (SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) from persona 
				        INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod) WHERE usuarios.Usu_Cod = tarea.Tar_Cre)as Tar_Cre_Nom,
				       (SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) from persona 
				        INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod) WHERE usuarios.Usu_Cod = tarea.Tar_Res)as Tar_Res_Nom,
				       (SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) from persona 
					    INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod) WHERE usuarios.Usu_Cod = tarea.Tar_Val)as Tar_Val_Nom,
					   exa_master.empresas.Emp_Nom, 
				       exa_master.empresas.Emp_Cod
					FROM tarea
					INNER JOIN exa_master.empresas ON (exa_master.empresas.Emp_Cod = tarea.Emp_Cod)
					$filtro 
					order by Tar_Fec_Ent";
			return $sql;
			break;

		case 5:
 		$sql = "UPDATE tarea SET Tar_Fec_Mod = '$Par_Sql[Tar_Fec_Mod]', Tar_Des = '$Par_Sql[Tar_Des]', Tar_Fil = '$Par_Sql[Tar_Fil]' , Tar_Fec_Ent = '$Par_Sql[Tar_Fec_Ent]', Tar_Res= '$Par_Sql[Tar_Res]', Emp_Cod= '$Par_Sql[Emp_Cod]' WHERE Tar_Cod = $Par_Sql[Tar_Cod] ";
		return $sql;
		break;

		case 6:
 		$sql = "UPDATE tarea SET Tar_Est = '$Par_Sql[Tar_Est]' WHERE Tar_Cod = $Par_Sql[Tar_Cod] ";
		return $sql;
		break;

		case 7:
			$sql = "SELECT 
			tarea.*,
			tarea_det.Tad_Cod,tarea_det.Tad_Des, tarea_det.Tad_Obs, tarea_det.Tad_Fil,
			exa_master.empresas.Emp_Nom, exa_master.empresas.Emp_Cod,
			IF(Tar_Est='A', 'Activo', IF(Tar_Est = 'I', 'Inactivo', IF(Tar_Est = 'E', 'Entregado', 'Validado'))) as Tar_Est,
			  (SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) from persona 
		      INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod) WHERE usuarios.Usu_Cod = tarea.Tar_Cre)as Tar_Cre_Nom,
		      
		      (SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) from persona 
		      INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod) WHERE usuarios.Usu_Cod = tarea.Tar_Res)as Tar_Res_Nom,
		      
		      (SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) from persona 
		      INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod) WHERE usuarios.Usu_Cod = tarea.Tar_Val)as Tar_Val_Nom
			FROM tarea
			LEFT JOIN tarea_det ON (tarea_det.Tar_Cod= tarea.Tar_Cod)
			INNER JOIN exa_master.empresas ON (exa_master.empresas.Emp_Cod = tarea.Emp_Cod) WHERE tarea.Tar_Cod = $Par_Sql[Tar_Cod]";
		    return $sql;
			break;

		case 8:
 		$sql = "INSERT INTO tarea_det (Tad_Des, Tad_Obs, Tad_Fil, Tar_Cod) VALUES "
                        . "('$Par_Sql[Tad_Des]', '$Par_Sql[Tad_Obs]', '$Par_Sql[Tad_Fil]', '$Par_Sql[Tar_Cod_Re]')";
		return $sql;
		break;

		case 9:
 		$sql = "UPDATE tarea_det, tarea SET Tad_Des = '$Par_Sql[Tad_Des]', Tad_Obs = '$Par_Sql[Tad_Obs]', Tad_Fil = '$Par_Sql[Tad_Fil]' 
				WHERE tarea_det.Tar_Cod = tarea.Tar_Cod
				AND tarea.Tar_Cod = '$Par_Sql[Tar_Cod_Re]'";
		return $sql;
		break;

		case 10:
 		$sql = "UPDATE tarea SET Tar_Fec_Env = '$Par_Sql[Tar_Fec_Env]', Tar_Est = 'E' WHERE Tar_Cod = $Par_Sql[Tar_Cod_Re] ";
		return $sql;
		break;

		case 11:
 		$sql = "SELECT MAX(tar_Cod) as Cod_Fut from tarea";
		return $sql;
		break;

		case 12:
 		$sql = "SELECT Ust_Tip from usuario_tipo WHERE Usu_Cod = $Par_Sql[0]";
		return $sql;
		break;

		case 13:
 		$sql = "UPDATE tarea SET Tar_Val = '$Par_Sql[Ses_Usu_Cod]' WHERE Tar_Cod = $Par_Sql[Tar_Cod] ";
		return $sql;
		break;

		case 14:
 		$sql = "SELECT * from tarea WHERE Tar_Cod = $Par_Sql[Tar_Cod]";
		return $sql;
		break;
      
	}
}?>