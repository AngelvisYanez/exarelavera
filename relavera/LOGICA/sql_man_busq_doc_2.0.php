<?php

/**
 * Retorna consulta sql a ejecutarse para búsqueda de documentos
 *
 * @author Exa-Contable
 * @version 1.0
 * @package manifiesto.LOGICA
 */

function sentencias_busq_doc($id, $Par_Sql)
{
	switch ($id) {
		case 1:
			// Búsqueda por código QR - Solo busca por Man_Cod
			$man_cod = isset($Par_Sql['man_cod']) ? addslashes($Par_Sql['man_cod']) : '';
			$codigo_qr = isset($Par_Sql['codigo_qr']) ? addslashes($Par_Sql['codigo_qr']) : '';

			// Buscar solo por Man_Cod
			$sql = "SELECT 
						manifiesto.Man_Cod,
						manifiesto.Man_Num,
						manifiesto.Pla_Cod,
						manifiesto.Veh_Cod,
						manifiesto.Cho_Cod,
						vehiculo.Veh_Pla,
						CONCAT(persona_cho.Prs_Nom, ' ', persona_cho.Prs_Ape) as chofer,
						persona_cho.Prs_Ced as chofer_cedula,
						manifiesto.Man_Fec,
						manifiesto.Man_Fea,
						manifiesto.Man_Tip
					FROM manifiesto
						INNER JOIN vehiculo ON manifiesto.Veh_Cod = vehiculo.Veh_Cod
						INNER JOIN chofer ON manifiesto.Cho_Cod = chofer.Cho_Cod
						INNER JOIN persona AS persona_cho ON chofer.Prs_Cod = persona_cho.Prs_Cod
						INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
					WHERE 
						manifiesto.Man_Cod = '$man_cod'
						AND cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
					ORDER BY manifiesto.Man_Fec DESC
					LIMIT 1;";
			// ChromePhp::log($sql);
			return $sql;

		case 2:
			// Búsqueda por número de manifiesto - Consulta en la tabla manifiesto usando Man_Num y Pla_Cod
			$numero_manifiesto = isset($Par_Sql['numero_manifiesto']) ? addslashes($Par_Sql['numero_manifiesto']) : '';
			$pla_cod = isset($Par_Sql['pla_cod']) ? addslashes($Par_Sql['pla_cod']) : '';

			// Validar que se proporcionen ambos valores
			if (empty($numero_manifiesto) || empty($pla_cod)) {
				return ""; // Retornar SQL vacío si faltan datos
			}

			// Filtro de estado opcional
			$man_tip = isset($Par_Sql['man_tip']) ? addslashes($Par_Sql['man_tip']) : '';
			$status_filter = "";
			if (!empty($man_tip) && $man_tip != 'T') {
				$status_filter = " AND manifiesto.Man_Tip = '$man_tip'";
			}

			// Construir condición WHERE: buscar por Pla_Cod y Man_Num
			$where_conditions = "manifiesto.Pla_Cod = '$pla_cod' AND CAST(manifiesto.Man_Num AS UNSIGNED) = '$numero_manifiesto'$status_filter";

			$sql = "SELECT 
						manifiesto.Man_Cod,
						manifiesto.Man_Num,
						manifiesto.Pla_Cod,
						manifiesto.Veh_Cod,
						manifiesto.Cho_Cod,
						vehiculo.Veh_Pla,
						CONCAT(persona_cho.Prs_Nom, ' ', persona_cho.Prs_Ape) as chofer,
						persona_cho.Prs_Ced as chofer_cedula,
						manifiesto.Man_Fec,
						manifiesto.Man_Fea,
						manifiesto.Man_Tip
					FROM manifiesto
						INNER JOIN vehiculo ON manifiesto.Veh_Cod = vehiculo.Veh_Cod
						INNER JOIN chofer ON manifiesto.Cho_Cod = chofer.Cho_Cod
						INNER JOIN persona AS persona_cho ON chofer.Prs_Cod = persona_cho.Prs_Cod
						INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
					WHERE 
						$where_conditions
						AND cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
					ORDER BY manifiesto.Man_Fec DESC
					LIMIT 1;";
			return $sql;

		case 3:
			// Actualizar estado del manifiesto (Man_Tip)
			$man_cod = isset($Par_Sql['man_cod']) ? addslashes($Par_Sql['man_cod']) : '';
			$nuevo_estado = isset($Par_Sql['nuevo_estado']) ? addslashes($Par_Sql['nuevo_estado']) : '';

			if (empty($man_cod) || empty($nuevo_estado)) {
				return "";
			}

			// Validar que el estado actual coincida con el esperado antes de actualizar
			$estado_actual = isset($Par_Sql['estado_actual']) ? addslashes($Par_Sql['estado_actual']) : '';

			if (!empty($estado_actual)) {
				$sql = "UPDATE manifiesto 
						SET Man_Tip = '$nuevo_estado',
							Man_Tes = CONCAT(IFNULL(Man_Tes, ''), '-', '$nuevo_estado')
						WHERE Man_Cod = '$man_cod'
						AND Man_Tip = '$estado_actual';";
			} else {
				$sql = "UPDATE manifiesto 
						SET Man_Tip = '$nuevo_estado',
							Man_Tes = CONCAT(IFNULL(Man_Tes, ''), '-', '$nuevo_estado')
						WHERE Man_Cod = '$man_cod'
						AND Man_Tip IN ('P', 'A');";
			}
			return $sql;

		case 4:
			// Búsqueda por placa - Consulta en la tabla vehiculo usando Veh_Pla
			$veh_pla = isset($Par_Sql['veh_pla']) ? addslashes($Par_Sql['veh_pla']) : '';

			// Validar que se proporcione el valor
			if (empty($veh_pla)) {
				return ""; // Retornar SQL vacío si faltan datos
			}

			// Filtro de estado
			$man_tip = isset($Par_Sql['man_tip']) ? addslashes($Par_Sql['man_tip']) : '';
			$status_condition = ""; // Por defecto busca todos (el último registrado)

			if (!empty($man_tip)) {
				if ($man_tip == 'T') {
					$status_condition = ""; // Todos los estados
				} else {
					$status_condition = "AND manifiesto.Man_Tip = '$man_tip'";
				}
			}

			$sql = "SELECT 
							manifiesto.Man_Cod,
							manifiesto.Man_Num,
							manifiesto.Pla_Cod,
							manifiesto.Veh_Cod,
							manifiesto.Cho_Cod,
							vehiculo.Veh_Pla,
							CONCAT(persona_cho.Prs_Nom, ' ', persona_cho.Prs_Ape) as chofer,
							persona_cho.Prs_Ced as chofer_cedula,
							manifiesto.Man_Fec,
							manifiesto.Man_Fea,
							manifiesto.Man_Tip
						FROM manifiesto
							INNER JOIN vehiculo ON manifiesto.Veh_Cod = vehiculo.Veh_Cod
							INNER JOIN chofer ON manifiesto.Cho_Cod = chofer.Cho_Cod
							INNER JOIN persona AS persona_cho ON chofer.Prs_Cod = persona_cho.Prs_Cod
							INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
						WHERE 
							vehiculo.Veh_Pla = '$veh_pla'
							AND cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
							$status_condition
						ORDER BY manifiesto.Man_Fec DESC
						LIMIT 1;";
			return $sql;

		case 5:
			// Listar manifiestos por estado (para Grid)
			$man_tip = isset($Par_Sql['man_tip']) ? addslashes($Par_Sql['man_tip']) : '';
			$status_condition = "";

			if (!empty($man_tip) && $man_tip != 'T') {
				$status_condition = "AND manifiesto.Man_Tip = '$man_tip'";

				// Si es Pendiente, filtrar solo los de la fecha actual
				if ($man_tip == 'P') {
					$status_condition .= " AND DATE(manifiesto.Man_Fec) = CURDATE()";
				}
			}

			$sql = "SELECT 
						manifiesto.Man_Cod,
						manifiesto.Man_Num,
						manifiesto.Pla_Cod,
						manifiesto.Veh_Cod,
						manifiesto.Cho_Cod,
						vehiculo.Veh_Pla,
						CONCAT(persona_cho.Prs_Nom, ' ', persona_cho.Prs_Ape) as chofer,
						persona_cho.Prs_Ced as chofer_cedula,
						manifiesto.Man_Fec,
						manifiesto.Man_Fea,
						manifiesto.Man_Tip
					FROM manifiesto
						INNER JOIN vehiculo ON manifiesto.Veh_Cod = vehiculo.Veh_Cod
						INNER JOIN chofer ON manifiesto.Cho_Cod = chofer.Cho_Cod
						INNER JOIN persona AS persona_cho ON chofer.Prs_Cod = persona_cho.Prs_Cod
						INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
					WHERE 
						cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
						$status_condition
					ORDER BY manifiesto.Man_Fec DESC
					LIMIT 50;";
			return $sql;

		default:
			return "";
	}
}
