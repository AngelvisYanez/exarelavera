<?php

/**
 * Retorna consulta sql a ejecutarse para Manifiesto Técnico
 *
 * @author Exa-Contable
 * @version 1.0
 * @package manifiesto.LOGICA
 */

function sentencias_manif_tec_camp($id, $Par_Sql)
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
						Mat_Fde, Mat_Eae, Mat_Ear, Mat_Oce, Mat_Tra, Mat_Est, Mat_Sys
					) VALUES (
						'$Mat_Cod', '$Man_Cod', '$Usu_Cod', '$Hum_Cod', '$Mat_Dna',
						'$Mat_Fde', '$Mat_Eae', '$Mat_Ear', '$Mat_Oce', '$Mat_Tra', 'A', NOW()
					);";
			return $sql;
			// break;
		case 4:
			// Listar manifiestos con filtros (Base tabla manifiesto)
			$wherefiltro = '';
			$wherefecha = '';
			$orderby = 'manifiesto.Man_Cod DESC';

			if (isset($Par_Sql['filtro'])) {
				$val = isset($Par_Sql['search']) ? addslashes($Par_Sql['search']) : '';
				switch ($Par_Sql['filtro']) {
					case 'c': // Por código (Manifiesto Tecnico)
						if ($val !== '') $wherefiltro = " AND manifiesto_tecnico.Mat_Cod LIKE '%$val%'";
						break;
					case 'n': // Por número de certificado
						if ($val !== '') $wherefiltro = " AND manifiesto_tecnico.Mat_Nce LIKE '%$val%'";
						break;
					case 'd': // Por descripción
						if ($val !== '') $wherefiltro = " AND (manifiesto_tecnico.Mat_Dna LIKE '%$val%' OR manifiesto_tecnico.Mat_Oce LIKE '%$val%')";
						break;
					case 'p': // Por Placa
						if ($val !== '') $wherefiltro = " AND vehiculo.Veh_Pla LIKE '%$val%'";
						break;
					case 'q': // Por QR (Man_Cod)
						if ($val !== '') $wherefiltro = " AND manifiesto.Man_Cod = '$val'";
						break;
					case 'm': // Por N°. Manifiesto (Pla_Cod y Man_Num)
						$Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
						$Man_Num = isset($Par_Sql['Man_Num']) ? addslashes($Par_Sql['Man_Num']) : '';

						if ($Pla_Cod !== '') {
							$wherefiltro .= " AND manifiesto.Pla_Cod LIKE '%$Pla_Cod%'";
						}
						if ($Man_Num !== '') {
							// Convertir a entero para buscar (ej: 0017 -> 17)
							$Man_Num_Int = (int)$Man_Num;
							$wherefiltro .= " AND manifiesto.Man_Num = '$Man_Num_Int'";
						}
						break;
				}
			}

			// Filtrar por Man_Tip (Estado)
			if (isset($Par_Sql['Man_Tip']) && trim($Par_Sql['Man_Tip']) !== '' && trim($Par_Sql['Man_Tip']) !== 'T') {
				$man_tip = addslashes(trim($Par_Sql['Man_Tip']));
				$wherefiltro .= " AND manifiesto.Man_Tip = '$man_tip'";
			}

			// Filtrar por fechas (Usar fecha de manifiesto si no hay tecnico, o preferencia por fecha de manifiesto para listado general)
			if (
				isset($Par_Sql['Fec_IniM']) && isset($Par_Sql['Fec_FinM']) &&
				$Par_Sql['Fec_IniM'] !== '' && $Par_Sql['Fec_FinM'] !== ''
			) {
				$wherefecha = " AND manifiesto.Man_Fec BETWEEN '" . addslashes($Par_Sql['Fec_IniM']) . "' AND '" . addslashes($Par_Sql['Fec_FinM']) . "'";
			}

			// Determinar el ordenamiento según el parámetro recibido
			if (isset($Par_Sql['ordenar']) && $Par_Sql['ordenar'] !== '') {
				$ordenar = addslashes($Par_Sql['ordenar']);
				switch ($ordenar) {
					case 'fecha_asc':
						$orderby = 'manifiesto.Man_Fec ASC, manifiesto.Man_Cod ASC';
						break;
					case 'fecha_desc':
						$orderby = 'manifiesto.Man_Fec DESC, manifiesto.Man_Cod DESC';
						break;
					case 'codigo_asc':
						$orderby = 'manifiesto.Man_Cod ASC';
						break;
					case 'codigo_desc':
						$orderby = 'manifiesto.Man_Cod DESC';
						break;
					default:
						$orderby = 'manifiesto.Man_Cod DESC';
						break;
				}
			}

			$sql = "SELECT manifiesto.Man_Cod, manifiesto.Man_Num, manifiesto.Man_Fec,
						manifiesto.Man_Tip, manifiesto.Pla_Cod,
						manifiesto_tecnico.Mat_Cod, manifiesto_tecnico.Mat_Fde,
						vehiculo.Veh_Pla,
						usuarios.Usu_Cod, usuarios.Usu_Ced,
						CONCAT(persona_usr.Prs_Nom, ' ', persona_usr.Prs_Ape) as usuario,
						CONCAT(persona_cho.Prs_Nom, ' ', persona_cho.Prs_Ape) as chofer_nombre,
						manifiesto_celdas.Cel_Num,
						manifiesto_celdas.Cel_Nom,
						manifiesto_celdas.Cel_Rec,
						grupo_celda.Cel_Nom as Cel_Nom_Grupo
					FROM manifiesto
						INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
						LEFT JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto.Veh_Cod
						LEFT JOIN chofer ON manifiesto.Cho_Cod = chofer.Cho_Cod
						LEFT JOIN persona AS persona_cho ON chofer.Prs_Cod = persona_cho.Prs_Cod
						LEFT JOIN manifiesto_tecnico ON manifiesto.Man_Cod = manifiesto_tecnico.Man_Cod
						LEFT JOIN usuarios ON manifiesto_tecnico.Usu_Cod = usuarios.Usu_Cod
						LEFT JOIN persona AS persona_usr ON usuarios.Prs_Cod = persona_usr.Prs_Cod
						INNER JOIN manifiesto_celdas ON manifiesto.Cel_Cod = manifiesto_celdas.Cel_Cod
						LEFT JOIN manifiesto_celdas AS grupo_celda ON manifiesto_celdas.Cel_Rec = grupo_celda.Cel_Cod
					WHERE cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
						$wherefecha
						$wherefiltro
						AND manifiesto.Man_Est= 'A'
						-- AND manifiesto.Man_Fec >= CURDATE()
						-- AND manifiesto.Man_Fec < CURDATE() + INTERVAL 1 DAY
						AND (manifiesto_tecnico.Mat_Est = 'A' OR manifiesto_tecnico.Mat_Est IS NULL)
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

			// Filtrar por Man_Tip
			if (isset($Par_Sql['Man_Tip']) && trim($Par_Sql['Man_Tip']) !== '' && trim($Par_Sql['Man_Tip']) !== 'T') {
				$man_tip = addslashes(trim($Par_Sql['Man_Tip']));
				$whereman_tip = " AND manifiesto.Man_Tip = '$man_tip'";
			}

			// Filtrar por estado
			if (isset($Par_Sql['estado']) && $Par_Sql['estado'] !== '' && $Par_Sql['estado'] !== 'T') {
				$whereestado = " AND manifiesto.Man_Est = '" . addslashes($Par_Sql['estado']) . "'";
			} else {
				$whereestado = " AND manifiesto.Man_Est = 'A'";
			}

			$sql = "SELECT manifiesto.Man_Cod, CONCAT(month(Man_Fes),'-',year(Man_Fes),'-',LPAD(Man_Num,4,0)) as Man_Num, manifiesto.Man_Num as Man_Num_Raw, date(manifiesto.Man_Fec) as Man_Fec,
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

			$sql = "SELECT manifiesto.Man_Cod, CONCAT(month(Man_Fes),'-',year(Man_Fes),'-',LPAD(Man_Num,4,0)) as Man_Num, manifiesto.Man_Num as Man_Num_Raw, date(manifiesto.Man_Fec) as Man_Fec,
						CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as cliente,time(Man_Fea) as Man_Fea_Hor, 
						vehiculo.Veh_Pla, manifiesto.Man_Est, manifiesto.Man_Tip, manifiesto.Pla_Cod,
						IF(manifiesto.Man_Tip='P','PENDIENTE',
							IF(manifiesto.Man_Tip='A','APROBADO',
								IF(manifiesto.Man_Tip='F','FACTURADO',
									IF(manifiesto.Man_Tip='GE','GARITA IN',
										IF(manifiesto.Man_Tip='GS','GARITA OUT','RECHAZADO'))))) as estado,
						manifiesto_celdas.Cel_Num, manifiesto_celdas.Cel_Nom, manifiesto_celdas.Cel_Rec,
						grupo_celda.Cel_Nom as Cel_Nom_Grupo,
						manifiesto_tecnico.Mat_Cod
					FROM manifiesto
						INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
						INNER JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
						LEFT JOIN vehiculo ON manifiesto.Veh_Cod = vehiculo.Veh_Cod
						INNER JOIN manifiesto_celdas ON manifiesto.Cel_Cod = manifiesto_celdas.Cel_Cod
						LEFT JOIN manifiesto_celdas AS grupo_celda ON manifiesto_celdas.Cel_Rec = grupo_celda.Cel_Cod
						LEFT JOIN manifiesto_tecnico ON manifiesto.Man_Cod = manifiesto_tecnico.Man_Cod
					WHERE manifiesto.Man_Cod = '$man_cod'
						AND cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
						AND manifiesto.Man_Est = 'A'
					LIMIT 1;";
			return $sql;
			// break;
		case 12:
			// Seleccionar datos del manifiesto para actualización de estados
			$Man_Cod = '';
			if (isset($Par_Sql['where']) && isset($Par_Sql['where']['Man_Cod'])) {
				$Man_Cod = addslashes($Par_Sql['where']['Man_Cod']);
			}
			$sql = "SELECT Man_Tes, Man_Usu FROM manifiesto WHERE Man_Cod = '$Man_Cod';";
			return $sql;

		case 13:
			// Actualizar manifiesto (Estados y Historial)
			$Man_Cod = '';
			if (isset($Par_Sql['where']) && isset($Par_Sql['where']['Man_Cod'])) {
				$Man_Cod = addslashes($Par_Sql['where']['Man_Cod']);
			}

			$Man_Tip = isset($Par_Sql['Man_Tip']) ? addslashes($Par_Sql['Man_Tip']) : '';
			$Man_Tes = isset($Par_Sql['Man_Tes']) ? addslashes($Par_Sql['Man_Tes']) : '';
			// Man_Usu ya viene con addslashes desde PHP
			$Man_Usu = isset($Par_Sql['Man_Usu']) ? $Par_Sql['Man_Usu'] : '';

			$extra_update = "";
			if (isset($Par_Sql['Man_Est'])) {
				$Man_Est = addslashes($Par_Sql['Man_Est']);
				$extra_update = ", Man_Est = '$Man_Est'";
			}

			$sql = "UPDATE manifiesto 
					SET Man_Tip = '$Man_Tip', 
						Man_Tes = '$Man_Tes', 
						Man_Usu = '$Man_Usu'
						$extra_update
					WHERE Man_Cod = '$Man_Cod';";
			return $sql;
		case 14:
			// Inactivar manifiestos técnicos previos para un Man_Cod
			$Man_Cod = isset($Par_Sql['Man_Cod']) ? addslashes($Par_Sql['Man_Cod']) : '';
			$sql = "UPDATE manifiesto_tecnico SET Mat_Est = 'I' WHERE Man_Cod = '$Man_Cod';";
			return $sql;

		case 15:
			$Man_Cod = isset($Par_Sql['Man_Cod']) ? addslashes($Par_Sql['Man_Cod']) : '';
			$sql = 'SELECT manifiesto.Man_Usu,
			CASE 
			WHEN manifiesto.Man_Usu LIKE \'%"Man_Tip":"GE"%\'
			THEN SUBSTRING_INDEX(
					SUBSTRING_INDEX(manifiesto.Man_Usu,\'"Man_Tip":"GE","Fecha":"\',-1),\'"\',1	)
			ELSE NULL
			END AS fecha_ge,
            CONCAT(persona_cho.Prs_Nom," ",persona_cho.Prs_Ape) AS chofer_nombre , chofer.Cho_Tel /*persona_cho.Prs_Tel*/ AS tel_chofer , persona_pla.Prs_Tel AS tel_admin_planta,Pla_Wat
			FROM manifiesto
			LEFT JOIN chofer ON manifiesto.Cho_Cod = chofer.Cho_Cod
			LEFT JOIN persona AS persona_cho ON chofer.Prs_Cod = persona_cho.Prs_Cod		
            INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod  =  manifiesto.Pla_Cod     
			INNER JOIN manifiesto_personal_planta AS mpp  ON mpp.Pla_Cod = manifiesto.Pla_Cod   
			LEFT JOIN persona AS persona_pla ON persona_pla.Prs_Cod  = mpp.Prs_Cod  
			WHERE manifiesto.Man_Cod = "' . $Man_Cod . '"
			LIMIT 1';
			return $sql;
	}
}
