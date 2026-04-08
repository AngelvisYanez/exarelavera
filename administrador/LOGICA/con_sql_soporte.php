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


function sentencias_con($id,$Par_Sql) {
	switch($id) {

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
		$sql = "INSERT INTO tickets (Tic_Fec, Tic_Des, Tic_Fil, Tic_Fec_Ent, Tic_Cre, Tic_Res, Emp_Cod) VALUES "
                        . "('$Par_Sql[Tic_Fec]', '$Par_Sql[Tic_Des]', '$Par_Sql[Tic_Fil]', '$Par_Sql[Tic_Fec_Ent]', '$Par_Sql[Tic_Cre]', '$Par_Sql[Tic_Res]', '$Par_Sql[Emp_Cod]')";
		return $sql;
		break;

		case 4:
			// $hoy = date("Y-m-d H:i:s");
			$sql = '';
			$filtro = '';
			$dbs = array('1'=>'servicios','2'=>'gsl_chavez','3'=>'coopsb','4'=>'agrofertil','5'=>'agronuevo','6'=>'exa');
			
			foreach ($dbs as $key => $value) {
		
				$filtro = '';
				if($Par_Sql['Ase_Cod'] == 1){
					$filtro = "WHERE Tic_Fec_Cre between '$Par_Sql[fechaIni]' AND '$Par_Sql[fechaFin] 23:59:59'";
				}else{
					$filtro = "WHERE Tic_Fec_Cre between '$Par_Sql[fechaIni]' AND '$Par_Sql[fechaFin] 23:59:59' AND ($value.tickets.Ase_Cod = '$Par_Sql[Ase_Cod]' OR $value.tickets.Ase_Cod IS NULL)";
				}
				
				if($Par_Sql['estado'] != '9'){
					$filtro = $filtro." AND $value.tickets.Tic_Est = '$Par_Sql[estado]' ";
				}

				if($Par_Sql['modCod'] != 'T'){
					// Escapar el valor para manejar comillas y caracteres especiales
					$modCodEscapado = addslashes($Par_Sql['modCod']);
					// Comparar con Org_Des del módulo padre (donde Org_Cod = Org_Niv del registro actual)
					$filtro = $filtro." AND EXISTS (
						SELECT 1 FROM $value.organizado AS org_mod 
						WHERE org_mod.Org_Cod = $value.organizado.Org_Niv 
						AND org_mod.Org_Des = '$modCodEscapado'
					) ";
				}
				
				$sql .= "SELECT 
					$value.tickets.*,
					$value.tickets.Tic_Des,
					exa_master.empresas.Emp_Nom,
					exa_master.empresas.Emp_Cod,
					IF(Tic_Est='0', 'Pendiente', IF(Tic_Est = '1', 'En Proceso', IF(Tic_Est = '2', 'Reabierto', 'Solucionado'))) as Tic_Est,
					IF(Tic_Est='0', 'Pendiente', IF(Tic_Est = '1', 'En Proceso', IF(Tic_Est = '2', 'Reabierto', 'Solucionado'))) as Tic_Est,
					$value.organizado.Org_Des as Org_Sec,
					$value.organizado.Org_Niv as codigo,
					(SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) from $value.persona INNER JOIN $value.usuarios ON ($value.persona.Prs_Cod = $value.usuarios.Prs_Cod) WHERE $value.usuarios.Usu_Cod = $value.tickets.Usu_Cod)as Prs_Nom,
					IF($value.tickets.Ase_Cod IS NOT NULL, (SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) from exa.persona INNER JOIN exa.usuarios ON (exa.persona.Prs_Cod = exa.usuarios.Prs_Cod) WHERE exa.usuarios.Usu_Cod = $value.tickets.Ase_Cod), NULL) as Ase_Cod,
					(SELECT Org_Des from $value.organizado Where Org_Cod = codigo) as Org_Mod
					FROM $value.tickets
					INNER JOIN exa_master.empresas ON (exa_master.empresas.Emp_Cod = $value.tickets.Emp_Cod)
					INNER JOIN $value.organizado ON ($value.organizado.Org_Cod = $value.tickets.Org_Cod)
					$filtro
					UNION ";
					
			}
			$sql = substr($sql, 0, -6);
			$sql .= ' order by Tic_Fec_Cre';
			return $sql;
			break;

		case 5:
 		$sql = "UPDATE $Par_Sql[db].tickets SET Tic_Fec_Ter = '$Par_Sql[Tic_Fec_Ter]', Tic_Est = '$Par_Sql[Tic_Est]',Tic_Obs = '$Par_Sql[Tic_Obs]', Tic_Tip = '$Par_Sql[Tic_Tip]' WHERE Tic_Cod = '$Par_Sql[Tic_Cod]' ";
		return $sql;
		break;

		case 6:
 		$sql = "UPDATE tickets SET Tic_Est = '$Par_Sql[Tic_Est]' WHERE Tic_Cod = $Par_Sql[Tic_Cod] ";
		return $sql;
		break;

		case 7:
			$sql = "SELECT 
			$Par_Sql[db].tickets.*,
			$Par_Sql[db].tickets.Tic_Des,
			exa_master.empresas.Emp_Nom,
			exa_master.empresas.Emp_Cod,
			IF(Tic_Est='0', 'Pendiente', IF(Tic_Est = '1', 'En Proceso', IF(Tic_Est = '2', 'Reabierto', 'Solucionado'))) as Tic_Est,
			IF(Tic_Tip='0', 'Tecnico', IF(Tic_Tip = 'Uso', 'Uso', 'Acceso')) as Tic_Tip,
			$Par_Sql[db].organizado.Org_Des as Org_Sec,
			$Par_Sql[db].organizado.Org_Niv as codigo,
			(SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) from $Par_Sql[db].persona INNER JOIN $Par_Sql[db].usuarios ON ($Par_Sql[db].persona.Prs_Cod = $Par_Sql[db].usuarios.Prs_Cod) WHERE $Par_Sql[db].usuarios.Usu_Cod = $Par_Sql[db].tickets.Usu_Cod)as Prs_Nom,
			(SELECT Org_Des from $Par_Sql[db].organizado Where Org_Cod = codigo) as Org_Mod
			FROM $Par_Sql[db].tickets
			INNER JOIN exa_master.empresas ON (exa_master.empresas.Emp_Cod = $Par_Sql[db].tickets.Emp_Cod)
			INNER JOIN $Par_Sql[db].organizado ON ($Par_Sql[db].organizado.Org_Cod = $Par_Sql[db].tickets.Org_Cod)
			WHERE $Par_Sql[db].tickets.Tic_Cod = $Par_Sql[Tic_Cod] ";
			//ChromePhp::log($sql);
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
 		$sql = "SELECT MAX(tar_Cod) as Cod_Fut from tickets";
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
      
		case 18:
			$sql = "UPDATE $Par_Sql[db].tickets SET Tic_Est = 1, Ase_Cod = $Par_Sql[Ase_Cod] WHERE Tic_Cod = $Par_Sql[Tic_Cod]";
			//ChromePhp::log($sql);
			return $sql;
			break;
		// CONSULTAR BDD
		case 19:
			$sql = "SELECT DISTINCT Dat_Dis from exa_master.data ORDER BY Dat_Dis ASC";
			return $sql;
			break;
		case 20:
			$sql = "SELECT Dat_Dis from exa_master.data WHERE Emp_Cod = $Par_Sql[Emp_Cod]";
			return $sql;
			break;
		
	}
}?>