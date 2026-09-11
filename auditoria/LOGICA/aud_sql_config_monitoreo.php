<?php
/**
 * SQL configuracion de monitoreo (modulos/procesos a auditar).
 * @package auditoria.LOGICA
 */
if (!function_exists('aud_sql_db_dis')) {
	function aud_sql_db_dis()
	{
		$db = isset($_SESSION['Ses_Dat_Dis']) ? trim((string)$_SESSION['Ses_Dat_Dis']) : '';
		if ($db === '' && isset($GLOBALS['Ses_Dat_Dis'])) {
			$db = trim((string)$GLOBALS['Ses_Dat_Dis']);
		}
		$db = preg_replace('/[^a-zA-Z0-9_]/', '', $db);
		return ($db !== '') ? "`{$db}`" : "`servicios`";
	}
}

function sentencias_cfg_monitoreo($id, $Par_Sql)
{
	$dbDis = aud_sql_db_dis();
	switch ($id) {
		/** Modulos con procesos activos */
		case 1:
			$sql = "SELECT o.`Org_Cod`, o.`Org_Des`, o.`Org_Ord`, o.`Org_Niv`
			FROM {$dbDis}.`organizado` o
			WHERE EXISTS (
				SELECT 1 FROM {$dbDis}.`procesos` p
				WHERE p.`Org_Cod` = o.`Org_Cod` AND IFNULL(p.`Pcs_Est`,'A') = 'A'
			)
			ORDER BY o.`Org_Niv` ASC, o.`Org_Ord` ASC, o.`Org_Des` ASC";
			return $sql;
		break;

		/** Procesos de un modulo */
		case 2:
			$org = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT p.`Pcs_Cod`, p.`Pcs_Lin`, p.`Pcs_Nom`, p.`Pcs_Det`, p.`Org_Cod`
			FROM {$dbDis}.`procesos` p
			WHERE p.`Org_Cod` = {$org} AND IFNULL(p.`Pcs_Est`,'A') = 'A'
			ORDER BY p.`Pcs_Ord` ASC, IFNULL(p.`Pcs_Lin`, p.`Pcs_Nom`) ASC";
			return $sql;
		break;

		/** Config guardada de la empresa */
		case 3:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT `Cfg_Cod`, `Emp_Cod`, `Org_Cod`, `Pcs_Cod`, `Cfg_Est`
			FROM `auditoria`.`cfg_monitoreo`
			WHERE `Emp_Cod` = {$emp} AND `Cfg_Est` = 'A'";
			return $sql;
		break;

		/** Borrar config empresa */
		case 4:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "DELETE FROM `auditoria`.`cfg_monitoreo` WHERE `Emp_Cod` = {$emp}";
			return $sql;
		break;

		/** Insertar fila config */
		case 5:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$org = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$pcs = isset($Par_Sql[2]) ? (int)$Par_Sql[2] : 0;
			$usu = isset($Par_Sql[3]) ? (int)$Par_Sql[3] : 0;
			$fec = date('Y-m-d H:i:s');
			$sql = "INSERT INTO `auditoria`.`cfg_monitoreo`
				(`Emp_Cod`,`Org_Cod`,`Pcs_Cod`,`Cfg_Est`,`Cfg_Fec`,`Usu_Cod`)
				VALUES ({$emp},{$org},{$pcs},'A','{$fec}',{$usu})
				ON DUPLICATE KEY UPDATE `Cfg_Est`='A', `Cfg_Fec`='{$fec}', `Usu_Cod`={$usu}";
			return $sql;
		break;

		/** Conteo config activa empresa */
		case 6:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT COUNT(*) AS `count` FROM `auditoria`.`cfg_monitoreo`
			WHERE `Emp_Cod` = {$emp} AND `Cfg_Est` = 'A'";
			return $sql;
		break;

		/** Arbol: proceso + directorio + modulo raiz (Org_Niv=0) */
		case 7:
			$sql = "SELECT t.* FROM (
				SELECT p.`Pcs_Cod`, p.`Pcs_Lin`, p.`Pcs_Nom`, p.`Pcs_Ord`,
					o.`Org_Cod` AS `Dir_Cod`,
					o.`Org_Des` AS `Dir_Des`,
					o.`Org_Niv` AS `Dir_Niv`,
					o.`Org_Ord` AS `Dir_Ord`,
					CASE
						WHEN IFNULL(o.`Org_Niv`,0) = 0 THEN o.`Org_Cod`
						WHEN IFNULL(op.`Org_Niv`,0) = 0 THEN op.`Org_Cod`
						WHEN IFNULL(oa.`Org_Niv`,0) = 0 THEN oa.`Org_Cod`
						WHEN IFNULL(ob.`Org_Niv`,0) = 0 THEN ob.`Org_Cod`
						ELSE NULL
					END AS `Mod_Cod`,
					CASE
						WHEN IFNULL(o.`Org_Niv`,0) = 0 THEN o.`Org_Des`
						WHEN IFNULL(op.`Org_Niv`,0) = 0 THEN op.`Org_Des`
						WHEN IFNULL(oa.`Org_Niv`,0) = 0 THEN oa.`Org_Des`
						WHEN IFNULL(ob.`Org_Niv`,0) = 0 THEN ob.`Org_Des`
						ELSE NULL
					END AS `Mod_Des`
				FROM {$dbDis}.`procesos` p
				LEFT JOIN {$dbDis}.`organizado` o ON p.`Org_Cod` = o.`Org_Cod`
				LEFT JOIN {$dbDis}.`organizado` op ON op.`Org_Cod` = o.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` oa ON oa.`Org_Cod` = op.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` ob ON ob.`Org_Cod` = oa.`Org_Niv`
				WHERE IFNULL(p.`Pcs_Est`,'A') = 'A'
			) t
			WHERE t.`Mod_Cod` IS NOT NULL
			ORDER BY t.`Mod_Des` ASC, t.`Dir_Ord` ASC, t.`Dir_Des` ASC, t.`Pcs_Ord` ASC, IFNULL(t.`Pcs_Lin`, t.`Pcs_Nom`) ASC";
			return $sql;
		break;

		/** Traza de cambio de configuracion en el historial */
		case 8:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$usu = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$suc = isset($Par_Sql[2]) ? (int)$Par_Sql[2] : 0;
			$n = isset($Par_Sql[3]) ? (int)$Par_Sql[3] : 0;
			$fec = date('Y-m-d H:i:s');
			$sql = "INSERT INTO `auditoria`.`logs`
				(`Usu_Cod`,`Pcs_Cod`,`Tab_Cod`,`Log_Fec`,`Eve_Cod`,`Log_Cam`,`Log_Val`,`Log_Int`,`Emp_Cod`,`Suc_Cod`)
				SELECT {$usu},
					IFNULL((SELECT `Pcs_Cod` FROM {$dbDis}.`procesos` WHERE `Pcs_Nom` LIKE '%aud_adm_config_monitoreo%' LIMIT 1), 0),
					IFNULL((SELECT `Tab_Cod` FROM `auditoria`.`tablas` WHERE `Tab_Nom`='cfg_monitoreo' LIMIT 1), 0),
					'{$fec}',
					IFNULL((SELECT `Eve_Cod` FROM `auditoria`.`eventos` WHERE `Eve_Ini`='U' LIMIT 1), 3),
					'Cfg_Reglas',
					'{$n}',
					'{$n} reglas',
					{$emp},
					{$suc}";
			return $sql;
		break;
	}
	return '';
}
?>
