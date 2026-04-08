<?php

/**
 * Retorna consulta sql a ejecutarse para Manifiesto Técnico
 *
 * @author Exa-Contable
 * @version 1.0
 * @package manifiesto.LOGICA
 */

function sentencias_manifiesto_tecnico($id, $Par_Sql)
{
	switch ($id) {
		case 1:
			// Obtener lista de niveles de humedad para el select filtrado por empresa
			$sql = "SELECT Hum_Cod, Hum_Des, Hum_Rie, Hum_Est 
					FROM manifiesto_nivel_humedad 
					WHERE Hum_Est = 'A' 
						AND Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
					ORDER BY Hum_Des;";
			return $sql;
			// break;		
		case 2:
			// Obtener el siguiente código de manifiesto técnico
			$sql = "SELECT IFNULL(MAX(Mat_Cod), 0) + 1 AS sig
					FROM manifiesto_tecnico;";
			return $sql;
			// break;

		case 3:
			// Insertar nuevo manifiesto técnico
			$Mat_Cod = isset($Par_Sql['Mat_Cod']) ? addslashes($Par_Sql['Mat_Cod']) : '';
			$Man_Cod = isset($Par_Sql['Man_Cod']) ? addslashes($Par_Sql['Man_Cod']) : '';
			$Usu_Cod = isset($Par_Sql['Usu_Cod']) ? addslashes($Par_Sql['Usu_Cod']) : '';
			$Hum_Cod = isset($Par_Sql['Hum_Cod']) ? addslashes($Par_Sql['Hum_Cod']) : '';
			// $Mat_Rso = isset($Par_Sql['Mat_Rso']) ? addslashes($Par_Sql['Mat_Rso']) : '';
			$Mat_Dna = isset($Par_Sql['Mat_Dna']) ? addslashes($Par_Sql['Mat_Dna']) : '';
			$Mat_Fde = isset($Par_Sql['Mat_Fde']) ? addslashes($Par_Sql['Mat_Fde']) : '';
			$Mat_Eae = isset($Par_Sql['Mat_Eae']) ? addslashes($Par_Sql['Mat_Eae']) : '';
			$Mat_Ear = isset($Par_Sql['Mat_Ear']) ? addslashes($Par_Sql['Mat_Ear']) : '';
			$Mat_Oce = isset($Par_Sql['Mat_Oce']) ? addslashes($Par_Sql['Mat_Oce']) : '';
			$Mat_Tra = isset($Par_Sql['Mat_Tra']) ? addslashes($Par_Sql['Mat_Tra']) : '';

			$sql = "INSERT INTO manifiesto_tecnico (
						Mat_Cod, Man_Cod, Usu_Cod, Hum_Cod, Mat_Dna, 
						Mat_Fde, Mat_Eae, Mat_Ear, Mat_Oce, Mat_Tra, Mat_Sys
					) VALUES (
						'$Mat_Cod', '$Man_Cod', '$Usu_Cod', '$Hum_Cod', '$Mat_Dna',
						'$Mat_Fde', '$Mat_Eae', '$Mat_Ear', '$Mat_Oce', '$Mat_Tra', NOW()
					);";
			return $sql;
			// break;
		case 4:
			// Listar manifiestos técnicos con filtros
			$wherefiltro = '';
			$wherefecha = '';
			$orderby = 'manifiesto_tecnico.Mat_Cod DESC';

			if (isset($Par_Sql['search']) && $Par_Sql['search'] !== '') {
				$val = addslashes($Par_Sql['search']);
				$filtro_val = isset($Par_Sql['filtro']) ? $Par_Sql['filtro'] : (isset($Par_Sql['op_opciones']) ? $Par_Sql['op_opciones'] : '');
				
				switch ($filtro_val) {
					case 'u': // Por Usuario
						$wherefiltro = " AND (persona_usr.Prs_Nom LIKE '%$val%' OR persona_usr.Prs_Ape LIKE '%$val%')";
						break;
					case 'n': // Por No. Manifiesto (Formato M[Pla_Cod]-[Man_Num] con padding)
						$wherefiltro = " AND CONCAT('M', manifiesto.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, '0')) LIKE '%$val%'";
						break;
					case 'p': // Por Placa
						$wherefiltro = " AND vehiculo.Veh_Pla LIKE '%$val%'";
						break;
				}
			}

			if (
				isset($Par_Sql['Fec_IniM']) && isset($Par_Sql['Fec_FinM']) &&
				$Par_Sql['Fec_IniM'] !== '' && $Par_Sql['Fec_FinM'] !== ''
			) {
				$wherefecha = " AND date(manifiesto_tecnico.Mat_Fde) BETWEEN '" . addslashes($Par_Sql['Fec_IniM']) . "' AND '" . addslashes($Par_Sql['Fec_FinM']) . "'";
			}

			// Determinar el ordenamiento según el parámetro recibido
			if (isset($Par_Sql['ordenar']) && $Par_Sql['ordenar'] !== '') {
				$ordenar = addslashes($Par_Sql['ordenar']);
				switch ($ordenar) {
					case 'fecha_asc':
						$orderby = 'date(manifiesto_tecnico.Mat_Fde) ASC, manifiesto_tecnico.Mat_Cod ASC';
						break;
					case 'fecha_desc':
						$orderby = 'date(manifiesto_tecnico.Mat_Fde) DESC, manifiesto_tecnico.Mat_Cod DESC';
						break;
					case 'codigo_asc':
						$orderby = 'manifiesto_tecnico.Mat_Cod ASC';
						break;
					case 'codigo_desc':
						$orderby = 'manifiesto_tecnico.Mat_Cod DESC';
						break;
					case 'manifiesto':
						$orderby = 'manifiesto.Pla_Cod ASC, manifiesto.Man_Num ASC';
						break;
					case 'placa':
						$orderby = 'vehiculo.Veh_Pla ASC';
						break;
					default:
						$orderby = 'manifiesto_tecnico.Mat_Cod DESC';
						break;
				}
			}

			$sql = "SELECT manifiesto_tecnico.*,vehiculo.Veh_Cod, Veh_Pla,
						manifiesto_nivel_humedad.Hum_Des, manifiesto_nivel_humedad.Hum_Rie,
						usuarios.Usu_Cod, usuarios.Usu_Ced,
						CONCAT(persona_usr.Prs_Nom, ' ', persona_usr.Prs_Ape) as usuario,
						manifiesto.Man_Tip, manifiesto.Pla_Cod, manifiesto.Man_Num, manifiesto.Man_Usu,
						manifiesto_celdas.Cel_Num as Mat_Nce, manifiesto_celdas.Cel_Nom as Mat_Cce,
						grupo_celda.Cel_Nom as Mat_Dce
					FROM manifiesto_tecnico
						inner join manifiesto ON manifiesto.Man_Cod = manifiesto_tecnico.Man_Cod
						inner join vehiculo ON vehiculo.Veh_Cod = manifiesto.Veh_Cod
						LEFT JOIN manifiesto_nivel_humedad ON manifiesto_tecnico.Hum_Cod = manifiesto_nivel_humedad.Hum_Cod
						LEFT JOIN usuarios ON manifiesto_tecnico.Usu_Cod = usuarios.Usu_Cod
						LEFT JOIN persona AS persona_usr ON usuarios.Prs_Cod = persona_usr.Prs_Cod
						LEFT JOIN manifiesto_celdas ON manifiesto.Cel_Cod = manifiesto_celdas.Cel_Cod
						LEFT JOIN manifiesto_celdas AS grupo_celda ON manifiesto_celdas.Cel_Rec = grupo_celda.Cel_Cod
					WHERE 1=1
						AND (manifiesto_tecnico.Mat_Est = 'A' OR manifiesto_tecnico.Mat_Est IS NULL)
						$wherefecha $wherefiltro
					ORDER BY $orderby;";
			return $sql;
			// break;
		case 5:
			// Obtener un manifiesto técnico por código para editar
			$sql = "SELECT manifiesto_tecnico.*,
						manifiesto_nivel_humedad.Hum_Des, manifiesto_nivel_humedad.Hum_Rie,
						usuarios.Usu_Cod, usuarios.Usu_Ced,
						CONCAT(persona_usr.Prs_Nom, ' ', persona_usr.Prs_Ape) as usuario,
						manifiesto_celdas.Cel_Num, manifiesto_celdas.Cel_Nom, manifiesto_celdas.Cel_Rec,
						grupo_celda.Cel_Nom as Cel_Nom_Grupo
					FROM manifiesto_tecnico
						LEFT JOIN manifiesto_nivel_humedad ON manifiesto_tecnico.Hum_Cod = manifiesto_nivel_humedad.Hum_Cod
						LEFT JOIN usuarios ON manifiesto_tecnico.Usu_Cod = usuarios.Usu_Cod
						LEFT JOIN persona AS persona_usr ON usuarios.Prs_Cod = persona_usr.Prs_Cod
						LEFT JOIN manifiesto ON manifiesto_tecnico.Man_Cod = manifiesto.Man_Cod
						LEFT JOIN manifiesto_celdas ON manifiesto.Cel_Cod = manifiesto_celdas.Cel_Cod
						LEFT JOIN manifiesto_celdas AS grupo_celda ON manifiesto_celdas.Cel_Rec = grupo_celda.Cel_Cod
					WHERE manifiesto_tecnico.Mat_Cod = '" . addslashes($Par_Sql['Mat_Cod']) . "';";
			return $sql;
			// break;
		case 6:
			// Actualizar manifiesto técnico
			$Mat_Cod = addslashes($Par_Sql['Mat_Cod']);
			$Man_Cod = isset($Par_Sql['Man_Cod']) ? addslashes($Par_Sql['Man_Cod']) : '';
			$Usu_Cod = isset($Par_Sql['Usu_Cod']) ? addslashes($Par_Sql['Usu_Cod']) : '';
			$Hum_Cod = isset($Par_Sql['Hum_Cod']) ? addslashes($Par_Sql['Hum_Cod']) : '';
			// $Mat_Rso = isset($Par_Sql['Mat_Rso']) ? addslashes($Par_Sql['Mat_Rso']) : '';
			$Mat_Dna = isset($Par_Sql['Mat_Dna']) ? addslashes($Par_Sql['Mat_Dna']) : '';
			$Mat_Fde = isset($Par_Sql['Mat_Fde']) ? addslashes($Par_Sql['Mat_Fde']) : '';
			$Mat_Eae = isset($Par_Sql['Mat_Eae']) ? addslashes($Par_Sql['Mat_Eae']) : '';
			$Mat_Ear = isset($Par_Sql['Mat_Ear']) ? addslashes($Par_Sql['Mat_Ear']) : '';
			$Mat_Oce = isset($Par_Sql['Mat_Oce']) ? addslashes($Par_Sql['Mat_Oce']) : '';
			$Mat_Tra = isset($Par_Sql['Mat_Tra']) ? addslashes($Par_Sql['Mat_Tra']) : '';

			$sql = "UPDATE manifiesto_tecnico SET
						Man_Cod = '$Man_Cod',Usu_Cod = '$Usu_Cod',Hum_Cod = '$Hum_Cod',
						Mat_Dna = '$Mat_Dna',Mat_Fde = '$Mat_Fde',Mat_Eae = '$Mat_Eae',Mat_Ear = '$Mat_Ear',
						Mat_Oce = '$Mat_Oce', Mat_Tra = '$Mat_Tra',Mat_Sys = NOW()
					WHERE Mat_Cod = '$Mat_Cod';";
			return $sql;
			// break;
		case 7:
			// Eliminar/Anular manifiesto técnico (soft delete si existe campo de estado, sino DELETE)
			$sql = "DELETE FROM manifiesto_tecnico WHERE Mat_Cod = '" . addslashes($Par_Sql['Mat_Cod']) . "';";
			return $sql;
			// break;
		case 8:
			// Obtener lista de manifiestos para el select filtrado por empresa
			$sql = "SELECT manifiesto.Man_Cod, manifiesto.Man_Num, manifiesto.Man_Fec,
						CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as cliente,
						manifiesto.Man_Est
					FROM manifiesto
						INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
						INNER JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
					WHERE manifiesto.Man_Est = 'A'
						AND cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
					ORDER BY manifiesto.Man_Cod DESC;";
			return $sql;
			// break;
		case 9:
			// Obtener el siguiente código de manifiesto
			$sql = "SELECT IFNULL(MAX(Man_Cod), 0) + 1 AS sig
					FROM manifiesto;";
			return $sql;
			// break;
		case 10:
			// Buscar manifiestos para el modal de búsqueda
			$wherefiltro = '';
			$whereestado = '';
			$whereman_tip = '';
			// Obtener el valor de búsqueda, verificando tanto en el array como directamente
			$search_val = '';
			if (isset($Par_Sql['search']) && trim($Par_Sql['search']) !== '') {
				$search_val = trim($Par_Sql['search']);
			}

			// Si hay un valor de búsqueda, aplicar el filtro por placa
			if ($search_val !== '') {
				$val = addslashes($search_val);
				// Solo buscar por placa
				$wherefiltro = " AND vehiculo.Veh_Pla LIKE '%$val%'";
			}
			// Filtrar por estado
			if (isset($Par_Sql['estado']) && $Par_Sql['estado'] !== '' && $Par_Sql['estado'] !== 'T') {
				$whereestado = " AND manifiesto.Man_Est = '" . addslashes($Par_Sql['estado']) . "'";
			} else {
				$whereestado = " AND manifiesto.Man_Est = 'A'";
			}

			$sql = "SELECT manifiesto.Man_Cod, CONCAT(month(Man_Fes),'-',year(Man_Fes),'-',LPAD(Man_Num,4,0)) as Man_Num, date(manifiesto.Man_Fec) as Man_Fec,
						CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as cliente,time(Man_Fea) as Man_Fea_Hor, 
						vehiculo.Veh_Pla, manifiesto.Man_Est, manifiesto.Man_Tip, manifiesto.Pla_Cod,
						IF(manifiesto.Man_Tip='P','PENDIENTE',
							IF(manifiesto.Man_Tip='A','APROBADO',
								IF(manifiesto.Man_Tip='F','FACTURADO',
									IF(manifiesto.Man_Tip='GE','GARITA IN',
										IF(manifiesto.Man_Tip='GS','GARITA OUT','RECHAZADO'))))) as estado,
						manifiesto_celdas.Cel_Num, manifiesto_celdas.Cel_Nom, manifiesto_celdas.Cel_Rec,
						grupo_celda.Cel_Nom as Cel_Nom_Grupo
					FROM manifiesto
						INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
						INNER JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
						LEFT JOIN vehiculo ON manifiesto.Veh_Cod = vehiculo.Veh_Cod	
						INNER JOIN manifiesto_celdas ON manifiesto.Cel_Cod = manifiesto_celdas.Cel_Cod
						LEFT JOIN manifiesto_celdas AS grupo_celda ON manifiesto_celdas.Cel_Rec = grupo_celda.Cel_Cod		
					WHERE cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
						$whereestado $wherefiltro $whereman_tip
					ORDER BY manifiesto.Man_Cod DESC;";
			return $sql;
			// break;
		case 11:
			// Búsqueda por código QR - Solo busca por Man_Cod
			$man_cod = isset($Par_Sql['man_cod']) ? addslashes($Par_Sql['man_cod']) : '';

			if (empty($man_cod)) {
				return "";
			}

			$sql = "SELECT manifiesto.Man_Cod, CONCAT(month(Man_Fes),'-',year(Man_Fes),'-',LPAD(Man_Num,4,0)) as Man_Num, date(manifiesto.Man_Fec) as Man_Fec,
						CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as cliente,time(Man_Fea) as Man_Fea_Hor, 
						vehiculo.Veh_Pla, manifiesto.Man_Est, manifiesto.Man_Tip, manifiesto.Pla_Cod,
						IF(manifiesto.Man_Tip='P','PENDIENTE',
							IF(manifiesto.Man_Tip='A','APROBADO',
								IF(manifiesto.Man_Tip='F','FACTURADO',
									IF(manifiesto.Man_Tip='GE','GARITA IN',
										IF(manifiesto.Man_Tip='GS','GARITA OUT','RECHAZADO'))))) as estado,
						manifiesto_celdas.Cel_Num, manifiesto_celdas.Cel_Nom, manifiesto_celdas.Cel_Rec,
						grupo_celda.Cel_Nom as Cel_Nom_Grupo
					FROM manifiesto
						INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
						INNER JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
						LEFT JOIN vehiculo ON manifiesto.Veh_Cod = vehiculo.Veh_Cod
						INNER JOIN manifiesto_celdas ON manifiesto.Cel_Cod = manifiesto_celdas.Cel_Cod
						LEFT JOIN manifiesto_celdas AS grupo_celda ON manifiesto_celdas.Cel_Rec = grupo_celda.Cel_Cod
					WHERE manifiesto.Man_Cod = '$man_cod'
						AND cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
						AND manifiesto.Man_Est = 'A'
					LIMIT 1;";
			return $sql;
			// break;
		case 12:
			// Obtener nombres de usuarios por sus códigos
			$codes = isset($Par_Sql['codes']) ? $Par_Sql['codes'] : '';
			if (empty($codes)) return "";
			
			$sql = "SELECT u.Usu_Cod, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Usu_Nom 
					FROM usuarios u 
					INNER JOIN persona p ON u.Prs_Cod = p.Prs_Cod 
					WHERE u.Usu_Cod IN ($codes);";
			return $sql;
			// break;
	}
}
