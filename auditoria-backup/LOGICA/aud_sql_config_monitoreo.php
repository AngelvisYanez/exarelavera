<?php
/**
 * SQL configuracion de monitoreo (modulos/procesos a auditar).
 * @package auditoria.LOGICA
 */
if (!function_exists('aud_master_db')) {
	function aud_master_db()
	{
		if (session_id() !== '' && !empty($_SESSION['Ses_Dat_Dis'])) {
			$db = preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']);
			if ($db !== '' && $db !== 'exa_master') {
				return $db;
			}
		}
		if (!empty($GLOBALS['Ses_Dat_Dis'])) {
			$db = preg_replace('/[^a-zA-Z0-9_]/', '', $GLOBALS['Ses_Dat_Dis']);
			if ($db !== '' && $db !== 'exa_master') {
				return $db;
			}
		}
		return 'servicios';
	}
}

function sentencias_cfg_monitoreo($id, $Par_Sql)
{
	$mdb = aud_master_db();
	switch ($id) {
		/** Modulos con procesos activos */
		case 1:
			$sql = "SELECT DISTINCT o.`Org_Cod`, o.`Org_Des`, o.`Org_Ord`
				FROM `{$mdb}`.`organizado` o
				INNER JOIN `{$mdb}`.`procesos` p ON p.`Org_Cod` = o.`Org_Cod`
				WHERE (o.`Org_Niv` = 0 OR o.`Org_Niv` IS NULL)
				  AND IFNULL(p.`Pcs_Est`,'A') = 'A'
				ORDER BY o.`Org_Ord` ASC, o.`Org_Des` ASC";
			return $sql;
		break;

		/** Directorios/hijos bajo un modulo */
		case 2:
			$mod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT DISTINCT o.`Org_Cod`, o.`Org_Des`, o.`Org_Ord`, o.`Org_Niv`
				FROM `{$mdb}`.`organizado` o
				WHERE o.`Org_Niv` = {$mod}
				ORDER BY o.`Org_Ord` ASC, o.`Org_Des` ASC";
			return $sql;
		break;

		/** Procesos activos bajo un directorio o modulo */
		case 3:
			$org = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT p.`Pcs_Cod`, p.`Pcs_Nom`, p.`Pcs_Lin`, p.`Pcs_Ord`
				FROM `{$mdb}`.`procesos` p
				WHERE p.`Org_Cod` = {$org}
				  AND IFNULL(p.`Pcs_Est`,'A') = 'A'
				ORDER BY p.`Pcs_Ord` ASC, p.`Pcs_Lin` ASC";
			return $sql;
		break;

		/** Limpiar configuracion anterior de la empresa */
		case 4:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "DELETE FROM `auditoria`.`cfg_monitoreo` WHERE `Emp_Cod` = {$emp}";
			return $sql;
		break;

		/** Insertar regla activa (Pcs_Cod = 0 para modulo/directorio completo) */
		case 5:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$org = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$pcs = isset($Par_Sql[2]) ? (int)$Par_Sql[2] : 0;
			$usu = isset($Par_Sql[3]) ? (int)$Par_Sql[3] : 0;
			$fec = date('Y-m-d H:i:s');
			$sql = "INSERT INTO `auditoria`.`cfg_monitoreo`
				(`Emp_Cod`,`Org_Cod`,`Pcs_Cod`,`Cfg_Est`,`Cfg_Fec`,`Usu_Cod`)
				VALUES ({$emp}, {$org}, {$pcs}, 'A', '{$fec}', {$usu})
				ON DUPLICATE KEY UPDATE `Cfg_Est`='A', `Cfg_Fec`='{$fec}', `Usu_Cod`={$usu}";
			return $sql;
		break;

		/** Reglas activas de una empresa */
		case 6:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT `Cfg_Cod`, `Emp_Cod`, `Org_Cod`, `Pcs_Cod`, `Cfg_Est`, COUNT(*) OVER() as count
				FROM `auditoria`.`cfg_monitoreo`
				WHERE `Emp_Cod` = {$emp} AND `Cfg_Est` = 'A'";
			return $sql;
		break;

		/**
		 * Arbol completo: modulos raiz -> subdirectorios -> procesos activos.
		 */
		case 7:
			$sql = "SELECT t.* FROM (\n\t\t\t\tSELECT p.`Pcs_Cod`, p.`Pcs_Lin`, p.`Pcs_Nom`, p.`Pcs_Ord`,
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
				FROM `{$mdb}`.`procesos` p
				LEFT JOIN `{$mdb}`.`organizado` o ON p.`Org_Cod` = o.`Org_Cod`
				LEFT JOIN `{$mdb}`.`organizado` op ON op.`Org_Cod` = o.`Org_Niv`
				LEFT JOIN `{$mdb}`.`organizado` oa ON oa.`Org_Cod` = op.`Org_Niv`
				LEFT JOIN `{$mdb}`.`organizado` ob ON ob.`Org_Cod` = oa.`Org_Niv`
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
					IFNULL((SELECT `Pcs_Cod` FROM `{$mdb}`.`procesos` WHERE `Pcs_Nom` LIKE '%aud_adm_config_monitoreo%' LIMIT 1), 0),
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

		/** Roles / Perfiles activos de la empresa */
		case 9:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$filtroEmp = ($emp > 0) ? "WHERE (p.`Emp_Cod` = {$emp} OR p.`Emp_Cod` IS NULL OR p.`Emp_Cod` = 0)" : "";
			$sql = "SELECT p.`Per_Cod`, p.`Per_Des`
				FROM `{$mdb}`.`perfiles` p
				{$filtroEmp}
				ORDER BY p.`Per_Des` ASC";
			return $sql;
		break;

		/** Usuarios activos de la empresa (con roles asociados) */
		case 10:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT u.`Usu_Cod`, 
					IFNULL(u.`Usu_Ced`, CONCAT('Usuario #', u.`Usu_Cod`)) AS `Usu_Nom`,
					u.`Usu_Ced`,
					TRIM(CONCAT(IFNULL(pr.`Prs_Nom`,''), ' ', IFNULL(pr.`Prs_Ape`,''))) AS `Prs_Nom_Completo`,
					(
						SELECT GROUP_CONCAT(DISTINCT pf.`Per_Des` SEPARATOR ', ')
						FROM `{$mdb}`.`usuarperfi` up
						INNER JOIN `{$mdb}`.`perfiles` pf ON up.`Per_Cod` = pf.`Per_Cod`
						WHERE up.`Usu_Cod` = u.`Usu_Cod`
					) AS `Perfiles_Desc`
				FROM `{$mdb}`.`usuarios` u
				LEFT JOIN `{$mdb}`.`persona` pr ON u.`Prs_Cod` = pr.`Prs_Cod`
				WHERE IFNULL(u.`Usu_Est`, 'A') = 'A'
				ORDER BY `Prs_Nom_Completo` ASC, u.`Usu_Cod` ASC";
			return $sql;
		break;

		/** Procesos asignados a un Rol / Perfil */
		case 11:
			$per = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT DISTINCT po.`Pcs_Cod`
				FROM `{$mdb}`.`perfiorgan` po
				WHERE po.`Per_Cod` = {$per}";
			return $sql;
		break;

		/** Procesos asignados a un Usuario por sus roles */
		case 12:
			$usu = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT DISTINCT po.`Pcs_Cod`
				FROM `{$mdb}`.`perfiorgan` po
				INNER JOIN `{$mdb}`.`usuarperfi` up ON po.`Per_Cod` = up.`Per_Cod`
				WHERE up.`Usu_Cod` = {$usu}";
			return $sql;
		break;

		/** Verificar si un usuario tiene perfil Administrador de Sistemas */
		case 13:
			$usu = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT 1 AS `is_admin`
				FROM `{$mdb}`.`usuarperfi` up
				INNER JOIN `{$mdb}`.`perfiles` p ON up.`Per_Cod` = p.`Per_Cod`
				WHERE up.`Usu_Cod` = {$usu}
				  AND (p.`Per_Des` LIKE '%Administrador de Sistemas%' OR UPPER(TRIM(p.`Per_Des`)) = 'ADMINISTRADOR' OR p.`Per_Des` LIKE '%Sistemas%' OR p.`Per_Cod` = 1)
				LIMIT 1";
			return $sql;
		break;

		/** Arbol filtrado por permisos de un Usuario o Rol especifico */
		case 14:
			$usu = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$per = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			if ($per > 0) {
				$permFilter = "INNER JOIN (
					SELECT DISTINCT po.`Pcs_Cod`
					FROM `{$mdb}`.`perfiorgan` po
					WHERE po.`Per_Cod` = {$per}
				) perm ON p.`Pcs_Cod` = perm.`Pcs_Cod`";
			} else {
				$permFilter = "INNER JOIN (
					SELECT DISTINCT po.`Pcs_Cod`
					FROM `{$mdb}`.`perfiorgan` po
					INNER JOIN `{$mdb}`.`usuarperfi` up ON po.`Per_Cod` = up.`Per_Cod`
					WHERE up.`Usu_Cod` = {$usu}
				) perm ON p.`Pcs_Cod` = perm.`Pcs_Cod`";
			}
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
				FROM `{$mdb}`.`procesos` p
				{$permFilter}
				LEFT JOIN `{$mdb}`.`organizado` o ON p.`Org_Cod` = o.`Org_Cod`
				LEFT JOIN `{$mdb}`.`organizado` op ON op.`Org_Cod` = o.`Org_Niv`
				LEFT JOIN `{$mdb}`.`organizado` oa ON oa.`Org_Cod` = op.`Org_Niv`
				LEFT JOIN `{$mdb}`.`organizado` ob ON ob.`Org_Cod` = oa.`Org_Niv`
				WHERE IFNULL(p.`Pcs_Est`,'A') = 'A'
			) t
			WHERE t.`Mod_Cod` IS NOT NULL
			ORDER BY t.`Mod_Des` ASC, t.`Dir_Ord` ASC, t.`Dir_Des` ASC, t.`Pcs_Ord` ASC, IFNULL(t.`Pcs_Lin`, t.`Pcs_Nom`) ASC";
			return $sql;
		break;

		default:
			return "";
	}
}
?>
