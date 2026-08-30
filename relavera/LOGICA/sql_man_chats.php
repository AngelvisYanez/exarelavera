<?php

/**
 * Retorna consulta sql a ejecutarse para Consulta de Chats
 *
 * @author Exa-Contable
 * @version 1.0
 * @package relavera.LOGICA
*/

/**
 * Fecha Y-m-d segura para filtro SQL (vacío si no es válida).
 *
 * @param mixed $v
 * @return string
 */
function relavera_chats_fecha_filtro_sql($v) {
	$s = trim((string) ($v === null ? '' : $v));
	if ($s === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
		return '';
	}
	$dt = DateTime::createFromFormat('Y-m-d', $s);
	return ($dt && $dt->format('Y-m-d') === $s) ? $s : '';
}

/**
 * Texto seguro para usar dentro de LIKE '%...%' (longitud acotada).
 *
 * @param mixed $v
 * @return string cadena escapada o vacío si no hay término
 */
function relavera_chats_sql_like_token($v) {
	$s = trim((string) ($v === null ? '' : $v));
	if ($s === '') {
		return '';
	}
	if (strlen($s) > 120) {
		$s = substr($s, 0, 120);
	}
	return str_replace(array('\\', "'"), array('\\\\', "''"), $s);
}

/**
 * FROM + JOINs comunes para listados y agregados de manifiesto_mensajes (m).
 *
 * @return string
 */
function relavera_chats_mensajes_from_join_sql() {
	return "FROM manifiesto_mensajes m
				LEFT JOIN manifiesto_plantas mp ON mp.Pla_Cod = m.Pla_Cod
				LEFT JOIN (
					SELECT mpp.Pla_Cod, mpp.Prs_Cod, mpp.Pep_Tel
					FROM manifiesto_personal_planta mpp
					INNER JOIN (
						SELECT Pla_Cod, MAX(Pep_Cod) AS Pep_Cod
						FROM manifiesto_personal_planta
						WHERE Pep_Tip = 'AP' AND (Pep_Est = 'A' OR Pep_Est IS NULL)
						GROUP BY Pla_Cod
					) mpp_mx ON mpp_mx.Pla_Cod = mpp.Pla_Cod AND mpp_mx.Pep_Cod = mpp.Pep_Cod
					WHERE mpp.Pep_Tip = 'AP'
				) mpp_ap ON mpp_ap.Pla_Cod = m.Pla_Cod
				LEFT JOIN persona padm ON padm.Prs_Cod = mpp_ap.Prs_Cod
				LEFT JOIN manifiesto mf ON mf.Man_Cod = m.Man_Cod
				LEFT JOIN vehiculo v ON v.Veh_Cod = m.Veh_Cod
				LEFT JOIN chofer ch ON ch.Cho_Cod = m.Cho_Cod
				LEFT JOIN persona pch ON pch.Prs_Cod = ch.Prs_Cod ";
}

/**
 * Fragmento de filtros AND ... (mismo criterio que listado case 2).
 *
 * @param mixed $Par_Sql array asociativo o índice 0/1 planta/tipo
 * @return string
 */
function relavera_chats_mensajes_where_fragment($Par_Sql) {
	$plaF = 0;
	$tipF = '';
	$tiposOk = array('SCH', 'SVH', 'SPL', 'DAN', 'CAN', 'TRE');
	if (!is_array($Par_Sql)) {
		$Par_Sql = explode('*', (string) $Par_Sql);
	}
	if (isset($Par_Sql['Pla_Cod']) && $Par_Sql['Pla_Cod'] !== '' && $Par_Sql['Pla_Cod'] !== null) {
		$plaF = (int) $Par_Sql['Pla_Cod'];
	} elseif (isset($Par_Sql[0]) && $Par_Sql[0] !== '' && $Par_Sql[0] !== null) {
		$plaF = (int) $Par_Sql[0];
	}
	if (isset($Par_Sql['Msj_Tip']) && $Par_Sql['Msj_Tip'] !== '' && $Par_Sql['Msj_Tip'] !== null) {
		$t = strtoupper(trim((string) $Par_Sql['Msj_Tip']));
		if (in_array($t, $tiposOk, true)) {
			$tipF = $t;
		}
	} elseif (isset($Par_Sql[1]) && $Par_Sql[1] !== '' && $Par_Sql[1] !== null) {
		$t = strtoupper(trim((string) $Par_Sql[1]));
		if (in_array($t, $tiposOk, true)) {
			$tipF = $t;
		}
	}
	$fecDesde = '';
	$fecHasta = '';
	if (isset($Par_Sql['Msj_Fec_Desde'])) {
		$fecDesde = relavera_chats_fecha_filtro_sql($Par_Sql['Msj_Fec_Desde']);
	}
	if (isset($Par_Sql['Msj_Fec_Hasta'])) {
		$fecHasta = relavera_chats_fecha_filtro_sql($Par_Sql['Msj_Fec_Hasta']);
	}
	if ($fecDesde !== '' && $fecHasta !== '' && $fecDesde > $fecHasta) {
		$aux = $fecDesde;
		$fecDesde = $fecHasta;
		$fecHasta = $aux;
	}
	$fFec = '';
	if ($fecDesde !== '' && $fecHasta !== '') {
		$fFec = " AND DATE(m.Msj_Fec) >= '" . $fecDesde . "' AND DATE(m.Msj_Fec) <= '" . $fecHasta . "' ";
	} elseif ($fecDesde !== '') {
		$fFec = " AND DATE(m.Msj_Fec) >= '" . $fecDesde . "' ";
	} elseif ($fecHasta !== '') {
		$fFec = " AND DATE(m.Msj_Fec) <= '" . $fecHasta . "' ";
	}
	$busTip = '';
	$busTok = '';
	if (isset($Par_Sql['Msj_Prs_Bus_Tip'])) {
		$bt = strtoupper(trim((string) $Par_Sql['Msj_Prs_Bus_Tip']));
		if ($bt === 'CHO' || $bt === 'AP') {
			$busTip = $bt;
		}
	}
	if (isset($Par_Sql['Msj_Prs_Bus_Tex'])) {
		$busTok = relavera_chats_sql_like_token($Par_Sql['Msj_Prs_Bus_Tex']);
	}
	$fPers = '';
	if ($busTok !== '' && $busTip !== '') {
		$u = strtoupper($busTok);
		if ($busTip === 'CHO') {
			$fPers = " AND pch.Prs_Cod IS NOT NULL AND (
						UPPER(TRIM(IFNULL(pch.Prs_Ced,''))) LIKE '%" . $u . "%'
						OR UPPER(CONCAT(IFNULL(pch.Prs_Nom,''),' ',IFNULL(pch.Prs_Ape,''))) LIKE '%" . $u . "%'
						OR UPPER(IFNULL(pch.Prs_Nom,'')) LIKE '%" . $u . "%'
						OR UPPER(IFNULL(pch.Prs_Ape,'')) LIKE '%" . $u . "%'
					) ";
		} else {
			$fPers = " AND padm.Prs_Cod IS NOT NULL AND (
						UPPER(TRIM(IFNULL(padm.Prs_Ced,''))) LIKE '%" . $u . "%'
						OR UPPER(CONCAT(IFNULL(padm.Prs_Nom,''),' ',IFNULL(padm.Prs_Ape,''))) LIKE '%" . $u . "%'
						OR UPPER(IFNULL(padm.Prs_Nom,'')) LIKE '%" . $u . "%'
						OR UPPER(IFNULL(padm.Prs_Ape,'')) LIKE '%" . $u . "%'
					) ";
		}
	}
	$fPla = ($plaF > 0) ? " AND m.Pla_Cod = " . $plaF . " " : '';
	$fTip = ($tipF !== '') ? " AND UPPER(TRIM(m.Msj_Tip)) = '" . $tipF . "' " : '';
	return $fPla . $fTip . $fFec . $fPers;
}

function sentencias_consulta_chats($id, $Par_Sql) {
	switch ($id) {
		case 1:
			
			$sql = "SELECT manifiesto_mensajes.*, 
						ciudad.Ciu_Des,
						cliente.Cli_Cod,
						persona_cli.Prs_Ced as Cli_Ced,
						CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as Cliente
					FROM manifiesto_plantas
						LEFT JOIN ciudad ON ciudad.Ciu_Cod = manifiesto_plantas.Ciu_Cod
						LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
						LEFT JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
					WHERE manifiesto_plantas.Pla_Cod = '$Par_Sql[0]'
						AND manifiesto_plantas.Pla_Est = 'A';";
			return $sql;

		case 2:
			/*
			 * Filtros: Msj_Tip, Pla_Cod, Msj_Fec_*, búsqueda persona Msj_Prs_Bus_Tip (CHO|AP) + Msj_Prs_Bus_Tex.
			 * $Par_Sql: string "5*SCH" / "*SCH" / "5", o array asociativo.
			 */
			$w = relavera_chats_mensajes_where_fragment($Par_Sql);
			$sql = "SELECT m.Msj_Cod, m.Pla_Cod, mp.Pla_Nom, m.Man_Cod, mf.Man_Num, mf.Man_Fec AS Man_Fec,
					m.Veh_Cod, v.Veh_Pla, m.Cho_Cod,
					TRIM(CONCAT(COALESCE(pch.Prs_Nom,''),' ',COALESCE(pch.Prs_Ape,''))) AS Chofer_Nom,
					TRIM(CONCAT(COALESCE(padm.Prs_Nom,''),' ',COALESCE(padm.Prs_Ape,''))) AS Pla_Admin_Nom,
					IFNULL(NULLIF(TRIM(COALESCE(mpp_ap.Pep_Tel, padm.Prs_Tel, '')), ''), '') AS Pla_Admin_Tel,
					m.Msj_Id, m.Msj_Tip,
					IFNULL(CAST(m.Msj_Tex AS CHAR(16000)), '') AS Msj_Tex,
					m.Msj_Img, m.Msj_Fec, m.Msj_Est, m.Msj_Sys
				" . relavera_chats_mensajes_from_join_sql() . "
				WHERE 1=1 " . $w . "
				ORDER BY m.Msj_Cod DESC";

			return $sql;

		case 4:
			/*
			 * Agregado: cantidad de mensajes por planta (mismos filtros que case 2).
			 */
			$w = relavera_chats_mensajes_where_fragment($Par_Sql);
			$sql = "SELECT m.Pla_Cod,
					COALESCE(NULLIF(TRIM(MAX(mp.Pla_Nom)),''), CONCAT('Planta #', m.Pla_Cod)) AS Pla_Nom,
					COUNT(*) AS Msj_Cnt
				" . relavera_chats_mensajes_from_join_sql() . "
				WHERE 1=1 " . $w . "
				GROUP BY m.Pla_Cod
				ORDER BY Msj_Cnt DESC, Pla_Nom ASC";

			return $sql;

		case 5:
			/*
			 * Agregado: cantidad de mensajes por tipo Msj_Tip (mismos filtros que case 2).
			 */
			$w = relavera_chats_mensajes_where_fragment($Par_Sql);
			$sql = "SELECT UPPER(TRIM(IFNULL(m.Msj_Tip,''))) AS Msj_Tip,
					COUNT(*) AS Msj_Cnt
				" . relavera_chats_mensajes_from_join_sql() . "
				WHERE 1=1 " . $w . "
				GROUP BY UPPER(TRIM(IFNULL(m.Msj_Tip,'')))
				ORDER BY Msj_Cnt DESC, Msj_Tip ASC";

			return $sql;

		case 3:
			$sql = "SELECT mp.Pla_Cod, mp.Pla_Nom
				FROM manifiesto_plantas mp
				WHERE mp.Pla_Est = 'A'
				ORDER BY mp.Pla_Nom";
			return $sql;
	}
}
?>
