<?php

/**
 * Retorna consulta sql a ejecutarse
 *
 * @author Erick Cordova
 * @version 1.0
 * Fecha de actualización:	2012-11-27
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 *
 * @package tesoreria.LOGICA
 */

function sentencias_manifiesto_cli($id, $Par_Sql)
{
	switch ($id) {
		//sentencia para obtener todos los proveedores registrados de la empresa
		case 1:
			if ($Par_Sql['op_opciones'] == "c")
				$search = "(persona.Prs_Ced LIKE '%$Par_Sql[searchCli]%') AND ";
			if ($Par_Sql['op_opciones'] == "d")
				$search = "(CONCAT(persona.Prs_Nom, ' ',persona.Prs_Ape)) LIKE '%$Par_Sql[searchCli]%' AND ";
			if ($Par_Sql['op_opciones'] == "cod")
				$search = "cliente.Cli_Cod=$Par_Sql[searchCli] AND ";

			$group = "";
			if (empty($Par_Sql['limits']))
				$campos = " COUNT(cliente.Cli_Cod) AS total";
			else {
				$campos = " cliente.Cli_Cod,persona.Prs_Cod,persona.Prs_Ced,IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom,concat(persona.Prs_Nom, ' ', persona.Prs_Ape)) as nombre,persona.Prs_Dir, CAST( SUM(Ant_Val) AS DECIMAL(20, 2)) AS sumaAntVal,CAST( SUM(Ant_Val) AS DECIMAL(20, 2))-IF(Ddc_Val IS NULL, 0, SUM(Ddc_Val)) AS saldo";
				$group = " group By cliente.Cli_Cod ";
			}
			$sql = "SELECT $campos
					FROM persona
						inner join cliente On persona.Prs_Cod = cliente.Prs_Cod
						left join anticipos_clientes On cliente.Cli_Cod = anticipos_clientes.Cli_Cod
						left join det_ant_cccc On anticipos_clientes.Ant_Cod = det_ant_cccc.Ant_Cod
					WHERE  $search cliente.Emp_Cod='$_SESSION[Ses_Emp_Cod]' AND cliente.Cli_Est = 'A' $group
						$Par_Sql[limits] ";
			return $sql;
			//sentencia para obtener los tipos de pago para anticipos
		case 2:
			$sql = "SELECT  CAST( SUM(Ant_Val) AS DECIMAL(20, 2)) AS sumaAntVal,CAST( SUM(Ant_Val) AS DECIMAL(20, 2))-IF(Ddc_Val IS NULL, 0, SUM(Ddc_Val)) AS saldo
					FROM persona
						inner join cliente On persona.Prs_Cod = cliente.Prs_Cod
						left join anticipos_clientes On cliente.Cli_Cod = anticipos_clientes.Cli_Cod
						left join det_ant_cccc On anticipos_clientes.Ant_Cod = det_ant_cccc.Ant_Cod
					WHERE  cliente.Cli_Cod=$Par_Sql[Cli_Cod] AND cliente.Emp_Cod='$_SESSION[Ses_Emp_Cod]' AND cliente.Cli_Est = 'A' group By cliente.Cli_Cod ";
			return $sql;
		case 3:
			// Construir condición de búsqueda
			$search = "";
			if (!empty($Par_Sql['search']) && !empty($Par_Sql['op_opciones'])) {
				$searchTerm = addslashes($Par_Sql['search']);
				if ($Par_Sql['op_opciones'] == "c") {
					// Búsqueda por cédula/RUC del cliente
					$search = "AND persona.Prs_Ced LIKE '%$searchTerm%'";
				} else if ($Par_Sql['op_opciones'] == "d") {
					// Búsqueda por nombre del cliente
					$search = "AND (CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%$searchTerm%' OR persona.Prs_Nom LIKE '%$searchTerm%' OR persona.Prs_Ape LIKE '%$searchTerm%')";
				} else if ($Par_Sql['op_opciones'] == "n") {
					// Búsqueda por nombre de la planta
					$search = "AND manifiesto_plantas.Pla_Nom LIKE '%$searchTerm%'";
				} else if ($Par_Sql['op_opciones'] == "l") {
					// Búsqueda por número de licencia
					$search = "AND manifiesto_plantas.Pla_Lic LIKE '%$searchTerm%'";
				}
			}

			if (empty($Par_Sql['limits'])) {
				// Contar total de registros
				$sql = "SELECT COUNT(*) as total
				FROM manifiesto_plantas 
				LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
				LEFT JOIN persona ON persona.Prs_Cod = cliente.Prs_Cod
				WHERE (cliente.Emp_Cod='$_SESSION[Ses_Emp_Cod]' OR manifiesto_plantas.Cli_Cod IS NULL) 
				AND (cliente.Cli_Est = 'A' OR manifiesto_plantas.Cli_Cod IS NULL) 
				AND manifiesto_plantas.Pla_Est = 'A'
				$search";
			} else {
				// Obtener registros con límites
				$sql = "SELECT manifiesto_plantas.*, ciudad.Ciu_Des, 
				cliente.Cli_Cod as Cli_Cod_Cliente,
				IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) as Cliente,
				persona.Prs_Ced
				FROM manifiesto_plantas 
				LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
				LEFT JOIN persona ON persona.Prs_Cod = cliente.Prs_Cod
				INNER JOIN ciudad ON ciudad.Ciu_Cod = manifiesto_plantas.Ciu_Cod
				WHERE (cliente.Emp_Cod='$_SESSION[Ses_Emp_Cod]' OR manifiesto_plantas.Cli_Cod IS NULL) 
				AND (cliente.Cli_Est = 'A' OR manifiesto_plantas.Cli_Cod IS NULL) 
				AND manifiesto_plantas.Pla_Est = 'A'
				$search
				$Par_Sql[limits]";
			}
			return $sql;


		case 4:
			// Construir condición de búsqueda
			$search = "";
			if (!empty($Par_Sql['search']) && !empty($Par_Sql['op_opciones'])) {
				$searchTerm = addslashes($Par_Sql['search']);
				if ($Par_Sql['op_opciones'] == "n") {
					// Búsqueda por nombre de la empresa
					$search = "AND manifiesto_transporte.Mat_Des LIKE '%$searchTerm%'";
				} else if ($Par_Sql['op_opciones'] == "m") {
					// Búsqueda por licencia MAE
					$search = "AND manifiesto_transporte.Mat_Mae LIKE '%$searchTerm%'";
				}
			}

			if (empty($Par_Sql['limits'])) {
				// Contar total de registros
				$sql = "SELECT COUNT(*) as total
				FROM manifiesto_transporte
				WHERE manifiesto_transporte.Emp_Cod = '$Par_Sql[0]' AND manifiesto_transporte.Mat_Est = 'A'
				$search";
			} else {
				// Obtener registros con límites
				$sql = "SELECT manifiesto_transporte.*
				FROM manifiesto_transporte
				WHERE manifiesto_transporte.Emp_Cod = '$Par_Sql[0]' AND manifiesto_transporte.Mat_Est = 'A'
				$search
				$Par_Sql[limits]";
			}

			return $sql;

		case 5:
			// Construir condición de búsqueda
			$search = "";
			if (!empty($Par_Sql['search']) && !empty($Par_Sql['op_opciones'])) {
				$searchTerm = addslashes($Par_Sql['search']);
				if ($Par_Sql['op_opciones'] == "c") {
					// Búsqueda por cédula
					$search = "AND persona.Prs_Ced LIKE '%$searchTerm%'";
				} else if ($Par_Sql['op_opciones'] == "d") {
					// Búsqueda por nombre
					$search = "AND (CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%$searchTerm%' OR persona.Prs_Nom LIKE '%$searchTerm%' OR persona.Prs_Ape LIKE '%$searchTerm%')";
				} else if ($Par_Sql['op_opciones'] == "pn") {
					// Búsqueda por nombre de planta
					$search = "AND manifiesto_plantas.Pla_Nom LIKE '%$searchTerm%'";
				} else if ($Par_Sql['op_opciones'] == "pl") {
					// Búsqueda por licencia de planta
					$search = "AND manifiesto_plantas.Pla_Lic LIKE '%$searchTerm%'";
				}
			}

			$where = "chofer.Emp_Cod = '$Par_Sql[0]' AND manifiesto_plantas.Pla_Est = 'A' AND chofer.Cho_Est = 'A'";
			if (!empty($Par_Sql['Pla_Cod'])) {
				$plaCod = (int) $Par_Sql['Pla_Cod'];
				$where .= " AND manifiesto_chofer.Pla_Cod = $plaCod";
			}
			if (!empty($Par_Sql['Cli_Cod'])) {
				$cliCod = (int) $Par_Sql['Cli_Cod'];
				$where .= " AND manifiesto_plantas.Cli_Cod = $cliCod";
			}
			// Detectar si hay límites: pueden estar en Par_Sql['limits'] o en Par_Sql[1] si comienza con "LIMIT"
			$hasLimits = !empty($Par_Sql['limits']) || (isset($Par_Sql[1]) && strpos($Par_Sql[1], 'LIMIT') === 0);

			if (!$hasLimits) {
				// Contar total de registros
				$sql = "SELECT COUNT(*) as total
			FROM chofer
			INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
			INNER JOIN manifiesto_chofer ON manifiesto_chofer.Cho_Cod = chofer.Cho_Cod
			INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto_chofer.Pla_Cod
			WHERE $where $search";
			} else {
				// Obtener registros con límites (incluye cant_sanciones)
				$limits = !empty($Par_Sql['limits']) ? $Par_Sql['limits'] : (isset($Par_Sql[1]) ? $Par_Sql[1] : '');
				$sql = "SELECT chofer.*, 
			persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced,
			concat(persona.Prs_Nom, ' ', persona.Prs_Ape) as nombre,
			manifiesto_chofer.Pla_Cod,
			manifiesto_plantas.Pla_Nom as planta,
			(SELECT COUNT(*) FROM manifiesto_sanciones ms WHERE ms.Cho_Cod = chofer.Cho_Cod AND ms.Msa_Tip='CH' AND ms.Msa_Est='A' AND (ms.Msa_Fef IS NULL OR NOW() < ms.Msa_Fef)) as cant_sanciones
			FROM chofer
			INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
			INNER JOIN manifiesto_chofer ON manifiesto_chofer.Cho_Cod = chofer.Cho_Cod
			INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto_chofer.Pla_Cod
			WHERE $where $search
			$limits";
			}

			return $sql;

		case 6:
			// Listar campos explícitamente para evitar conflictos con Veh_Tip
			$sql = "SELECT manifiesto.*, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) AS cliente, persona.Prs_Ced AS Cli_Prs_Ced , 
               manifiesto.Man_Sys, TIME(manifiesto.Man_Sys) AS Man_Hor,
		       DAY(manifiesto.Man_Sys) AS Rec_Dia, MONTH(manifiesto.Man_Sys) AS Rec_Mes, YEAR(manifiesto.Man_Sys) AS Rec_Ano, 
				-- Hora de llegada a la relavera
				DAY(manifiesto.Man_Fea) AS Man_Fea_Dia, MONTH(manifiesto.Man_Fea) AS Man_Fea_Mes, YEAR(manifiesto.Man_Fea) AS Man_Fea_Ano, 
				TIME(manifiesto.Man_Fea) AS Man_Fea_Hor, 
				-- Hora de salida a la relavera 
				DAY(manifiesto.Man_Fes) AS Man_Fes_Dia, MONTH(manifiesto.Man_Fes) AS Man_Fes_Mes, YEAR(manifiesto.Man_Fes) AS Man_Fes_Ano, 
				TIME(manifiesto.Man_Fes) AS Man_Fes_Hor, 
				persona.Prs_Tel, persona.Prs_Cor,
				persona.Prs_Dir, ciudad.Ciu_Des , Pla_Crd, Pla_Nom, Tde_Des, Tde_Cas, Tde_Cde,  vehiculo.Veh_Cap, 
				vehiculo.Veh_Pla  ,provincia.Pro_Nom, 
					manifiesto_transporte.*, Cho_Mae,  persona_chofer.Prs_Ced, persona_chofer.Prs_Dir AS Prs_Dir_Chofer, 
					persona_chofer.Prs_Cor AS Cho_Cor, chofer.Cho_Tli,
				CONCAT(persona_chofer.Prs_Nom, ' ', persona_chofer.Prs_Ape) AS chofer,
				CONCAT(   COALESCE(provincia_chofer.Pro_Nom, ''), ' / ', COALESCE(  ciudad_chofer.Ciu_Des, ''), ' / ', COALESCE( ciudad_chofer.Ciu_Des, '')   ) AS cho_ciu_prov,
				Pla_Rut, Pla_Nom, CONCAT(COALESCE(persona_usr.Prs_Nom, ''), ' ', COALESCE(persona_usr.Prs_Ape, '')) AS usuario_nombre, 
				COALESCE(persona_usr.Prs_Ced, '') AS usuario_cedula,
				COALESCE(persona_usr.Prs_Cor, '') AS usuario_correo, COALESCE(persona_usr.Prs_Tel, '') AS usuario_telefono,
				CASE 
					WHEN UPPER(TRIM(COALESCE(vehiculo.Veh_Tit, ''))) = 'V' THEN 'Volqueta'
					WHEN UPPER(TRIM(COALESCE(vehiculo.Veh_Tit, ''))) = 'B' THEN 'Bus'
					WHEN UPPER(TRIM(COALESCE(vehiculo.Veh_Tit, ''))) = 'C' THEN 'Camioneta'
					ELSE COALESCE(vehiculo.Veh_Tit, '')
				END AS Veh_Tit,
				manifiesto_tecnico.Mat_Dna, manifiesto_tecnico.Mat_Tra, manifiesto_tecnico.Mat_Ear, 
				manifiesto_tecnico.Mat_Eae, manifiesto_tecnico.Mat_Oce,
				CASE 
					WHEN manifiesto_tecnico.Mat_Tra = 'AT' THEN 'Almacenamiento Temporal'
					WHEN manifiesto_tecnico.Mat_Tra = 'DF' THEN 'Disposicion Final'
					ELSE manifiesto_tecnico.Mat_Tra
				END AS Mat_Tra_Des,
				CASE 
					WHEN manifiesto_tecnico.Mat_Ear = 'TR' THEN 'Transporte'
					WHEN manifiesto_tecnico.Mat_Ear = 'AT' THEN 'Almacenamiento Temporal'
					WHEN manifiesto_tecnico.Mat_Ear = 'EL' THEN 'Eliminacion'
					WHEN manifiesto_tecnico.Mat_Ear = 'DF' THEN 'Disposicion Final'
					WHEN manifiesto_tecnico.Mat_Ear = 'CT' THEN 'Cierre Tecnico'
					ELSE manifiesto_tecnico.Mat_Ear
				END AS Mat_Ear_Des,
				CASE 
					WHEN manifiesto_tecnico.Mat_Eae = 'A' THEN 'Aceptado'
					WHEN manifiesto_tecnico.Mat_Eae = 'R' THEN 'Rechazado'
					WHEN manifiesto_tecnico.Mat_Eae = 'AC' THEN 'Aceptado con Condicion'
					ELSE manifiesto_tecnico.Mat_Eae
				END AS Mat_Eae_Des,
				CONCAT(persona_tec.Prs_Nom, ' ', persona_tec.Prs_Ape) AS tecnico_nombre,
				manifiesto_nivel_humedad.Hum_Des, manifiesto_nivel_humedad.Hum_Rie,
				celv.Cel_Num AS Mat_Nce, celv.Cel_Nom AS Mat_Cce, grupov.Cel_Nom AS Mat_Dce
				FROM manifiesto 
					INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
					INNER JOIN persona ON persona.Prs_Cod = cliente.Prs_Cod
					INNER JOIN ciudad ON ciudad.Ciu_Cod = persona.Ciu_Cod
					INNER JOIN provincia ON provincia.Pro_Cod = ciudad.Pro_Cod
					INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
					INNER JOIN manifiesto_desechos ON manifiesto_desechos.Tde_Cod = manifiesto.Tde_Cod
					INNER JOIN manifiesto_vehiculo ON manifiesto_vehiculo.Veh_Cod = manifiesto.Veh_Cod
					INNER JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto_vehiculo.Veh_Cod
					INNER JOIN manifiesto_transporte ON manifiesto_transporte.Mat_Cod = vehiculo.Mat_Cod
					INNER JOIN manifiesto_chofer ON manifiesto_chofer.Cho_Cod = manifiesto.Cho_Cod
				INNER JOIN chofer ON chofer.Cho_Cod = manifiesto_chofer.Cho_Cod
				INNER JOIN persona AS persona_chofer ON persona_chofer.Prs_Cod = chofer.Prs_Cod
				LEFT JOIN ciudad AS ciudad_chofer ON ciudad_chofer.Ciu_Cod = persona_chofer.Ciu_Cod
				LEFT JOIN provincia AS provincia_chofer ON provincia_chofer.Pro_Cod = ciudad_chofer.Pro_Cod
				LEFT JOIN manifiesto_tecnico ON manifiesto_tecnico.Man_Cod = manifiesto.Man_Cod
				LEFT JOIN manifiesto_nivel_humedad ON manifiesto_tecnico.Hum_Cod = manifiesto_nivel_humedad.Hum_Cod
				LEFT JOIN manifiesto_celdas AS celv ON manifiesto.Cel_Cod = celv.Cel_Cod
				LEFT JOIN manifiesto_celdas AS grupov ON celv.Cel_Rec = grupov.Cel_Cod
				LEFT JOIN usuarios ON usuarios.Usu_Cod = manifiesto.Usu_Cod
				LEFT JOIN persona AS persona_usr ON usuarios.Prs_Cod = persona_usr.Prs_Cod
				LEFT JOIN usuarios AS usuarios_tec ON manifiesto_tecnico.Usu_Cod = usuarios_tec.Usu_Cod
				LEFT JOIN persona AS persona_tec ON usuarios_tec.Prs_Cod = persona_tec.Prs_Cod
				WHERE manifiesto.Man_Cod = '$Par_Sql[Man_Cod]'";
			return $sql;		
		case 7:
			// Determinar el tipo de búsqueda
			$op_opciones = isset($Par_Sql['op_opciones']) ? $Par_Sql['op_opciones'] : '';
			$searchCli = isset($Par_Sql['searchCli']) ? addslashes($Par_Sql['searchCli']) : '';
			
			// Construir la condición de búsqueda según el tipo
			if ($op_opciones == "c") {
				// Búsqueda por cédula/RUC
				$search = $searchCli != '' ? "persona.Prs_Ced LIKE '%$searchCli%'" : "1=1";
			} elseif ($op_opciones == "p") {
				// Búsqueda por nombre de planta
				$search = $searchCli != '' ? "mp.Pla_Nom LIKE '%$searchCli%'" : "1=1";
			} else {
				// Búsqueda por nombre del cliente
				$search = $searchCli != '' ? "(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%$searchCli%' OR persona.Prs_Nom LIKE '%$searchCli%' OR persona.Prs_Ape LIKE '%$searchCli%')" : "1=1";
			}
			
			// Si no hay limits, es para contar registros
			if (empty($Par_Sql['limits'])) {
				$campos = "COUNT(DISTINCT cliente.Cli_Cod) AS total";
				$limits = "";
			} else {
				// Si hay limits, es para obtener los datos
				$campos = "cliente.Cli_Cod, persona.Prs_Cod, persona.Prs_Ced, 
							IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS nombre, 
							persona.Prs_Dir,
							GROUP_CONCAT(mp.Pla_Nom ORDER BY mp.Pla_Nom SEPARATOR ', ') AS plantas";
				$limits = $Par_Sql['limits'];
			}
			
			$sql = "SELECT $campos
					FROM persona
						INNER JOIN cliente ON cliente.Prs_Cod = persona.Prs_Cod
						INNER JOIN manifiesto_plantas mp ON mp.Cli_Cod = cliente.Cli_Cod
					WHERE $search 
						AND cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
						AND cliente.Cli_Est = 'A'
					GROUP BY cliente.Cli_Cod
					$limits";
			return $sql;
		case 8: // Datos de cabecera para el certificado
			$sql = "SELECT persona.Prs_Cod, persona.Prs_Ced, IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS Representante,
					mp.Pla_Nom, mp.Pla_Car
					FROM manifiesto_plantas mp
					INNER JOIN cliente ON mp.Cli_Cod = cliente.Cli_Cod
					INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
					WHERE mp.Pla_Cod = '$Par_Sql[Pla_Cod]' AND cliente.Cli_Cod = '$Par_Sql[Cli_Cod]'";
			return $sql;

		case 9: // Listado de manifiestos para el certificado
			$sql = "SELECT m.Man_Cod, m.Pla_Cod, DATE(m.Man_Fes) as Fecha, TIME(m.Man_Fea) as Llegada, m.Man_Num,
					CONCAT('M', m.Pla_Cod, '-', LPAD(m.Man_Num, 4, '0')) as Man_Num_Full,
					m.Man_Pes, 
					COALESCE(ventas.Vet_Num, 'S/F') as Factura, vehiculo.Veh_Pla, 
					CAST((m.Man_Pes * (m.Man_Pun / 1000)) AS DECIMAL(10,2)) as Valor,
					IF(m.Vet_Cod IS NOT NULL AND m.Vet_Cod > 0, 1, 0) as Facturado
					FROM manifiesto m
					LEFT JOIN vehiculo ON m.Veh_Cod = vehiculo.Veh_Cod
					LEFT JOIN ventas ON m.Vet_Cod = ventas.Vet_Cod
					WHERE m.Cli_Cod = '$Par_Sql[Cli_Cod]' AND m.Pla_Cod = '$Par_Sql[Pla_Cod]'
					AND DATE(m.Man_Fes) BETWEEN '$Par_Sql[Fec_Des]' AND '$Par_Sql[Fec_Has]'
					AND m.Man_Est = 'A'
					ORDER BY m.Man_Fes ASC";
			return $sql;
			
		case 10: // Datos completos para el certificado de gestión de desechos
			$sql = "SELECT 
						m.Man_Cod,
						m.Man_Num,
						m.Pla_Cod,
						CONCAT('M', m.Pla_Cod, '-', LPAD(m.Man_Num, 4, '0')) as Man_Num_Full,
						DATE(m.Man_Fec) as Man_Fec,
						TIME(m.Man_Fec) as Man_Hor,
						YEAR(m.Man_Fec) as Man_Fec_Ano,
						-- Generador
						CONCAT(prs_cli.Prs_Nom, ' ', prs_cli.Prs_Ape) as cliente,
						mp.Pla_Car,
						mp.Pla_Lic as Des_Lic,
						prs_cli.Prs_Dir,
						prs_cli.Prs_Tel,
						-- Responsable Técnico
						CONCAT(prs_tec.Prs_Nom, ' ', prs_tec.Prs_Ape) as usuario,
						-- Transportista
						mt.Mat_Des,
						mt.Mat_Mae,
						CONCAT(prs_cho.Prs_Nom, ' ', prs_cho.Prs_Ape) as chofer,
						prs_cho.Prs_Cor as Cho_Cor,
						-- Desechos
						td.Tde_Des,
						td.Tde_Cde,
						m.Man_Pes,
						-- Recepción
						CONCAT(prs_usu.Prs_Nom, ' ', prs_usu.Prs_Ape) as Rec_Nom,
						-- Ubicación (Sucursal)
						s.Suc_Dir
					FROM manifiesto m
					INNER JOIN cliente c ON c.Cli_Cod = m.Cli_Cod
					INNER JOIN persona prs_cli ON prs_cli.Prs_Cod = c.Prs_Cod
					INNER JOIN manifiesto_plantas mp ON mp.Pla_Cod = m.Pla_Cod
					INNER JOIN manifiesto_desechos td ON td.Tde_Cod = m.Tde_Cod
					INNER JOIN manifiesto_vehiculo mv ON mv.Veh_Cod = m.Veh_Cod
					INNER JOIN vehiculo v ON v.Veh_Cod = mv.Veh_Cod
					INNER JOIN manifiesto_transporte mt ON mt.Mat_Cod = v.Mat_Cod
					INNER JOIN manifiesto_chofer mc ON mc.Cho_Cod = m.Cho_Cod
					INNER JOIN chofer cho ON cho.Cho_Cod = mc.Cho_Cod
					INNER JOIN persona prs_cho ON prs_cho.Prs_Cod = cho.Prs_Cod
					LEFT JOIN manifiesto_tecnico mtec ON mtec.Man_Cod = m.Man_Cod AND mtec.Mat_Est = 'A'
					LEFT JOIN usuarios u_tec ON u_tec.Usu_Cod = mtec.Usu_Cod
					LEFT JOIN persona prs_tec ON prs_tec.Prs_Cod = u_tec.Prs_Cod
					LEFT JOIN usuarios u_crea ON u_crea.Usu_Cod = m.Usu_Cod
					LEFT JOIN persona prs_usu ON prs_usu.Prs_Cod = u_crea.Prs_Cod
					LEFT JOIN sucursal s ON s.Suc_Cod = '$_SESSION[Ses_Suc_Cod]'
					WHERE m.Man_Cod = '$Par_Sql[Man_Cod]'";
			// echo $sql;
			return $sql;
		case 11:
			// Listar vehículos por planta con cant_sanciones (usado en man_pri_vehiculos_choferes)
			$empCod = isset($_SESSION['Ses_Emp_Cod']) ? (int) $_SESSION['Ses_Emp_Cod'] : 0;
			$where = "vehiculo.Veh_Est = 'A' AND vehiculo.Emp_Cod = $empCod";
			if (!empty($Par_Sql['where']['manifiesto_vehiculo.Pla_Cod'])) {
				$where .= " AND manifiesto_vehiculo.Pla_Cod = " . (int) $Par_Sql['where']['manifiesto_vehiculo.Pla_Cod'];
			}
			$search = "";
			if (!empty($Par_Sql['search']) && !empty($Par_Sql['op_opciones'])) {
				$searchTerm = addslashes($Par_Sql['search']);
				if ($Par_Sql['op_opciones'] == 'p') {
					$search = " AND vehiculo.Veh_Pla LIKE '%$searchTerm%'";
				}
			}
			if (empty($Par_Sql['limits'])) {
				$sql = "SELECT COUNT(*) as total
				FROM manifiesto_vehiculo
				INNER JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto_vehiculo.Veh_Cod
				LEFT JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto_vehiculo.Pla_Cod
				WHERE $where $search";
			} else {
				$limits = $Par_Sql['limits'];
				$sql = "SELECT vehiculo.Veh_Cod, vehiculo.Veh_Pla, vehiculo.Veh_Mar, vehiculo.Veh_Col, vehiculo.Veh_Cap, vehiculo.Veh_Tit,
				manifiesto_plantas.Pla_Nom,
				(SELECT COUNT(*) FROM manifiesto_sanciones ms WHERE ms.Veh_Cod = vehiculo.Veh_Cod AND ms.Msa_Tip='VE' AND ms.Msa_Est='A' AND (ms.Msa_Fef IS NULL OR NOW() < ms.Msa_Fef)) as cant_sanciones
				FROM manifiesto_vehiculo
				INNER JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto_vehiculo.Veh_Cod
				LEFT JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto_vehiculo.Pla_Cod
				WHERE $where $search
				$limits";
			}
			return $sql;	
	}
}
