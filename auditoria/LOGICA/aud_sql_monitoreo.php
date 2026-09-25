<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author car.87cod :)
 * @version 1.0
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package auditoria.LOGICA
 */
if (!function_exists('aud_sql_db_dis')) {
	function aud_sql_db_dis()
	{
		static $resolved = null;
		$db = isset($_SESSION['Ses_Dat_Dis']) ? trim((string)$_SESSION['Ses_Dat_Dis']) : '';
		if ($db === '' && isset($GLOBALS['Ses_Dat_Dis'])) {
			$db = trim((string)$GLOBALS['Ses_Dat_Dis']);
		}
		if ($db === '' && class_exists('Env')) {
			$db = trim((string)\Env::get('AUDIT_DB_DIS', ''));
		}
		$db = preg_replace('/[^a-zA-Z0-9_]/', '', $db);
		if ($db !== '') {
			return "`{$db}`";
		}
		if ($resolved !== null) {
			return $resolved;
		}
		// Sin sesion: tomar primera BD distribuida activa del master (evita `servicios`).
		$resolved = '`ecoparkmining`';
		if (class_exists('Env')) {
			$host = \Env::get('DB_HOST', '127.0.0.1');
			$user = \Env::get('DB_USERNAME', 'root');
			$pass = \Env::get('DB_PASSWORD', '');
			$port = (int)\Env::get('DB_PORT', 3306);
			$master = preg_replace('/[^a-zA-Z0-9_]/', '', (string)\Env::get('DB_DATABASE', 'exa_master'));
			if ($master === '') {
				$master = 'exa_master';
			}
			$con = @mysqli_connect($host, $user, $pass, $master, $port);
			if ($con) {
				$r = @mysqli_query($con, "SELECT `Dat_Dis` FROM `data` WHERE IFNULL(`Dat_Est`,'A')='A' AND `Dat_Dis`<>'' ORDER BY `Dat_Cod` ASC LIMIT 1");
				if ($r && ($row = mysqli_fetch_assoc($r))) {
					$cand = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$row['Dat_Dis']);
					if ($cand !== '') {
						$resolved = "`{$cand}`";
					}
				}
				@mysqli_close($con);
			}
		}
		return $resolved;
	}
}

function sentencias($id,$Par_Sql){
	$dbDis = aud_sql_db_dis();
	switch($id){
		/**
		 * Busqueda de un usuario por sucursal y coincidencias en su apellido
		 */
		case 1:
			$sql = "SELECT `persona`.`Prs_Cod`,`persona`.`Prs_Nom`,`persona`.`Prs_Ape`,`persona`.`Prs_Ced`,`usuarios`.`Usu_Cod`,`usuarios`.`Usu_Est`
			FROM
			{$dbDis}.`usuarios`
			INNER JOIN {$dbDis}.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
			WHERE
			`usuarios`.`Suc_Cod` = $Par_Sql[0] AND
			`persona`.`Prs_Ape` LIKE '%$Par_Sql[1]%'";
                        //echo $sql;
			return $sql;
		break;
		/**
		 * busqueda de un usuario por sucursal y su cedula
		 */
		case 2:
			$sql = "SELECT `persona`.`Prs_Cod`,`persona`.`Prs_Nom`,`persona`.`Prs_Ape`,`persona`.`Prs_Ced`,`usuarios`.`Usu_Cod`,`usuarios`.`Usu_Est`
			FROM
			{$dbDis}.`usuarios`
			INNER JOIN {$dbDis}.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
			WHERE
			`usuarios`.`Suc_Cod` = $Par_Sql[0] AND
			`persona`.`Prs_Ced` = '$Par_Sql[1]'";
			return $sql;
		break;
		/**
		 * Obtener datos de usuario
		 */
		case 3:
			$sql = "SELECT `persona`.`Prs_Cod`,`persona`.`Prs_Nom`,`persona`.`Prs_Ape`,`persona`.`Prs_Ced`,`usuarios`.`Usu_Cod`,`usuarios`.`Usu_Est`
			FROM
			{$dbDis}.`usuarios`
			INNER JOIN {$dbDis}.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
			WHERE
			`usuarios`.`Usu_Cod` = $Par_Sql[0]";
                        //echo $sql;
			return $sql;
		break;
		/**
		 * Obtener las seciones segun el usuario y entre fechas
		 */
		case 4:
			$sql = "SELECT `sesion`.`Ses_Cod`,`sesion`.`Ses_Int`,`sesion`.`Ses_Out`
			FROM
			{$dbDis}.`usuarios`
			INNER JOIN `auditoria`.`sesion` ON `usuarios`.`Usu_Cod` = `sesion`.`Usu_Cod`
			INNER JOIN {$dbDis}.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
			WHERE
			`sesion`.`Usu_Cod` = $Par_Sql[0] AND
			(DATE_FORMAT(`Ses_Int`,'%Y-%m-%d') BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]')
			ORDER BY `Ses_Int` DESC";
                        //echo $sql;
			return $sql;
		break;
		
		/**
		 * Obtener datos de la secion seleccionada
		 */
		case 5:
			$sql="SELECT `persona`.`Prs_Cod`,`persona`.`Prs_Nom`,`persona`.`Prs_Ape`,`persona`.`Prs_Ced`,`usuarios`.`Usu_Cod`,`usuarios`.`Usu_Est`,`sesion`.`Ses_Int`,`sesion`.`Ses_Out`
			FROM
			{$dbDis}.`usuarios`
			INNER JOIN {$dbDis}.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
			INNER JOIN `auditoria`.`sesion` ON `sesion`.`Usu_Cod` = `usuarios`.`Usu_Cod`
			WHERE
			`usuarios`.`Usu_Cod` = $Par_Sql[0] AND
			`sesion`.`Ses_Cod` = $Par_Sql[1]";
			return $sql;
		break;
		
		/**
		 * Obtener las actividades durante esa sessin del usuario 
		 */
		case 6:
			$empFiltro = (isset($Par_Sql[3]) && $Par_Sql[3] !== '' && (int)$Par_Sql[3] > 0)
				? aud_sql_emp_eq($Par_Sql[3])
				: '';
			$sql = "SELECT `logs`.`Log_Fec`,`eventos`.`Eve_Des`,`tablas`.`Tab_Ali`,`procesos`.`Pcs_Det`,`procesos`.`Pcs_Lin`,`logs`.`Log_Cam`,`logs`.`Log_Val`,`logs`.`Tab_Cod`,`tablas`.`Tab_Nom`,`logs`.`Emp_Cod`,`empresas`.`Emp_Nom`
			FROM
			`auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			LEFT JOIN {$dbDis}.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
			LEFT JOIN {$dbDis}.`empresas` ON `logs`.`Emp_Cod` = `empresas`.`Emp_Cod`
			WHERE `logs`.`Usu_Cod` = $Par_Sql[0] AND (`logs`.`Log_Fec` BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]') $empFiltro
			ORDER BY `Log_Fec` DESC";
			return $sql;
		break;
		
		/**
		 * Obtener datos de una tabla
		 */
		case 7:
			$sql = "SELECT `tablas`.`Tab_Ali`,`tablas`.`Tab_Des`,`tablas`.`Tab_Nom`
			FROM `auditoria`.`tablas`
			WHERE `tablas`.`Tab_Cod` = $Par_Sql[0]";
			return $sql;
		break;
		
		/**
		 * obtener las carectaeristicas del campo
		 */
		case 8:
			$sql = "SELECT `campos`.`Cam_Ali`,`campos`.`Cam_Cod`,`campos`.`Cam_Des`
				FROM
				`auditoria`.`campos`
				WHERE
				`campos`.`Tab_Cod` = $Par_Sql[0] AND
				`campos`.`Cam_Atr` = '$Par_Sql[1]'";
			return $sql;
		break;
		
		/**
		 * Obtener nombres de las tablas involucradas en la sql 
		 */
		case 9:
			$empFiltro = (isset($Par_Sql[3]) && $Par_Sql[3] !== '' && (int)$Par_Sql[3] > 0)
				? aud_sql_emp_eq($Par_Sql[3])
				: '';
			$sql = "SELECT DISTINCT `tablas`.`Tab_Ali`,`tablas`.`Tab_Nom`,`tablas`.`Tab_Cod`
			FROM `auditoria`.`logs` 
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			WHERE `logs`.`Usu_Cod` = $Par_Sql[0] AND (`logs`.`Log_Fec` BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]') $empFiltro
			ORDER BY `Tab_Nom` ASC";
			return $sql;
		break;
		
		/**
		 * Obtener las actividades durante esa sessi�n del usuario
		 */
		case 10:
			if (isset($Par_Sql[4])) {
				$empFiltro = ((int)$Par_Sql[3] > 0)
					? aud_sql_emp_eq($Par_Sql[3])
					: '';
				$tabCod = $Par_Sql[4];
			} else {
				$empFiltro = '';
				$tabCod = isset($Par_Sql[3]) ? $Par_Sql[3] : 0;
			}
			$sql = "SELECT `logs`.`Log_Fec`,`eventos`.`Eve_Des`,`tablas`.`Tab_Ali`,`procesos`.`Pcs_Det`,`procesos`.`Pcs_Lin`,`logs`.`Log_Cam`,`logs`.`Log_Val`,`logs`.`Tab_Cod`,`tablas`.`Tab_Nom`,`logs`.`Emp_Cod`,`empresas`.`Emp_Nom`
			FROM
			`auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			LEFT JOIN {$dbDis}.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
			LEFT JOIN {$dbDis}.`empresas` ON `logs`.`Emp_Cod` = `empresas`.`Emp_Cod`
			WHERE `logs`.`Usu_Cod` = $Par_Sql[0] AND (`logs`.`Log_Fec` BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]') AND `tablas`.`Tab_Cod` = '$tabCod' $empFiltro
			ORDER BY `Log_Fec` DESC";
			return $sql;
		break;
		
		/**
		 * Obtener el conteo de registro segun la sesin
		 */
		case 11:
			$empFiltro = (isset($Par_Sql[3]) && $Par_Sql[3] !== '' && (int)$Par_Sql[3] > 0)
				? aud_sql_emp_eq($Par_Sql[3])
				: '';
			$sql = "SELECT COUNT(`logs`.`Log_Fec`)AS 'count'
			FROM
			`auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			LEFT JOIN {$dbDis}.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
			WHERE `logs`.`Usu_Cod` = $Par_Sql[0] AND (`logs`.`Log_Fec` BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]') $empFiltro";
			return $sql;
		break;

		/**
		 * Ultimos registros de auditoria (sin exigir usuario)
		 * 0 emp, 1 from, 2 to, 3 eve, 4 tab, 5 usu, 6 limit, 7 offset
		 */
		case 12:
			$where = aud_logs_filtro($Par_Sql);
			// Indices: 8=limit, 9=offset (formato nuevo con org/pcs)
			$limit = 25;
			$offset = 0;
			if (isset($Par_Sql[8]) && $Par_Sql[8] !== '' && (int)$Par_Sql[8] > 0) {
				$limit = max(1, (int)$Par_Sql[8]);
				$offset = isset($Par_Sql[9]) ? max(0, (int)$Par_Sql[9]) : 0;
			} elseif (isset($Par_Sql[6]) && (int)$Par_Sql[6] > 0 && (!isset($Par_Sql[8]))) {
				// formato antiguo: 6=limit, 7=offset
				$limit = max(1, (int)$Par_Sql[6]);
				$offset = isset($Par_Sql[7]) ? max(0, (int)$Par_Sql[7]) : 0;
			}
			$sql = aud_logs_select()."
			$where
			ORDER BY `logs`.`Log_Fec` DESC, `logs`.`Log_Cod` DESC
			LIMIT {$offset}, {$limit}";
			return $sql;
		break;

		case 13:
			$where = aud_logs_filtro($Par_Sql);
			$sql = "SELECT COUNT(`logs`.`Log_Cod`) AS `count`
			FROM `auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			".aud_logs_joins()."
			$where";
			return $sql;
		break;

		case 14:
			$usu = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$emp = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$suc = isset($Par_Sql[2]) ? (int)$Par_Sql[2] : 0;
			$pcs = isset($Par_Sql[3]) ? (int)$Par_Sql[3] : 0;
			$hoy = date('Y-m-d');
			$ahora = date('Y-m-d H:i:s');
			$sql = "INSERT INTO `auditoria`.`logs` (`Usu_Cod`,`Pcs_Cod`,`Tab_Cod`,`Log_Fec`,`Eve_Cod`,`Log_Cam`,`Log_Val`,`Log_Int`,`Emp_Cod`,`Suc_Cod`)
			SELECT {$usu}, {$pcs}, t.Tab_Cod, '{$ahora}', e.Eve_Cod,
				'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val',
				'1,~001-DEMO-0001~,~{$hoy}~,~COMPROBANTE DEMO AUDITORIA~,150.00',
				'Com_Num=001-DEMO-0001', {$emp}, {$suc}
			FROM `auditoria`.`tablas` t
			INNER JOIN `auditoria`.`eventos` e ON e.Eve_Ini='I'
			WHERE t.Tab_Nom='comprobantes' LIMIT 1";
			return $sql;
		break;

		case 15:
			$usu = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$emp = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$suc = isset($Par_Sql[2]) ? (int)$Par_Sql[2] : 0;
			$pcs = isset($Par_Sql[3]) ? (int)$Par_Sql[3] : 0;
			$hoy = date('Y-m-d');
			$ahora = date('Y-m-d H:i:s');
			$sql = "INSERT INTO `auditoria`.`logs` (`Usu_Cod`,`Pcs_Cod`,`Tab_Cod`,`Log_Fec`,`Eve_Cod`,`Log_Cam`,`Log_Val`,`Log_Int`,`Emp_Cod`,`Suc_Cod`)
			SELECT {$usu}, {$pcs}, t.Tab_Cod, '{$ahora}', e.Eve_Cod,
				'Com_Con,Com_Val',
				'~COMPROBANTE DEMO MODIFICADO~,175.50',
				'Com_Num=001-DEMO-0001', {$emp}, {$suc}
			FROM `auditoria`.`tablas` t
			INNER JOIN `auditoria`.`eventos` e ON e.Eve_Ini='U'
			WHERE t.Tab_Nom='comprobantes' LIMIT 1";
			return $sql;
		break;

		case 16:
			$usu = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$emp = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$suc = isset($Par_Sql[2]) ? (int)$Par_Sql[2] : 0;
			$pcs = isset($Par_Sql[3]) ? (int)$Par_Sql[3] : 0;
			$ahora = date('Y-m-d H:i:s');
			$sql = "INSERT INTO `auditoria`.`logs` (`Usu_Cod`,`Pcs_Cod`,`Tab_Cod`,`Log_Fec`,`Eve_Cod`,`Log_Cam`,`Log_Val`,`Log_Int`,`Emp_Cod`,`Suc_Cod`)
			SELECT {$usu}, {$pcs}, t.Tab_Cod, '{$ahora}', e.Eve_Cod,
				'Asi_Deh,Asi_Val,Asi_Con',
				'~D~,50.00,~ANULACION ASIENTO DEMO~',
				'Asi_Cod=DEMO-9', {$emp}, {$suc}
			FROM `auditoria`.`tablas` t
			INNER JOIN `auditoria`.`eventos` e ON e.Eve_Ini='D'
			WHERE t.Tab_Nom='asientos' LIMIT 1";
			return $sql;
		break;

		case 17:
			$sql = "SELECT `Eve_Cod`,`Eve_Ini`,`Eve_Des` FROM `auditoria`.`eventos` ORDER BY `Eve_Cod`";
			return $sql;
		break;

		case 18:
			$sql = "SELECT `Tab_Cod`,`Tab_Nom`,`Tab_Ali`,`Tab_Des` FROM `auditoria`.`tablas` ORDER BY `Tab_Nom`";
			return $sql;
		break;

		case 19:
			$sql = aud_sql_buscar_proceso_sim('con_alt_compr', 'ontabilid');
			return $sql;
		break;

		case 20:
			if (!is_array($Par_Sql)) {
				$logCod = (int)$Par_Sql;
				$emp = 0;
			} else {
				$logCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
				$emp = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			}
			$sql = aud_logs_select()."
			WHERE `logs`.`Log_Cod` = {$logCod}".aud_sql_emp_eq($emp)."
			LIMIT 1";
			return $sql;
		break;

		case 21:
			$sql = "SELECT p.`Pcs_Cod`, p.`Pcs_Lin`, p.`Pcs_Det`, o.`Org_Des`
			FROM {$dbDis}.`procesos` p
			LEFT JOIN {$dbDis}.`organizado` o ON p.`Org_Cod` = o.`Org_Cod`
			WHERE p.`Pcs_Nom`='aud_con_monitoreo_1.0.php'
			LIMIT 1";
			return $sql;
		break;

		case 22:
			$nom = isset($Par_Sql[0]) ? addslashes($Par_Sql[0]) : '';
			$sql = "SELECT `Tab_Cod`,`Tab_Nom` FROM `auditoria`.`tablas` WHERE `Tab_Nom`='{$nom}' LIMIT 1";
			return $sql;
		break;

		case 23:
			$nom = isset($Par_Sql[0]) ? $Par_Sql[0] : '';
			$modLike = isset($Par_Sql[1]) ? $Par_Sql[1] : '';
			$sql = aud_sql_buscar_proceso_sim($nom, $modLike);
			return $sql;
		break;

		case 24:
			$usu = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$emp = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$suc = isset($Par_Sql[2]) ? (int)$Par_Sql[2] : 0;
			$pcs = isset($Par_Sql[3]) ? (int)$Par_Sql[3] : 0;
			$tab = isset($Par_Sql[4]) ? addslashes($Par_Sql[4]) : '';
			$eve = isset($Par_Sql[5]) ? addslashes($Par_Sql[5]) : 'I';
			$cam = isset($Par_Sql[6]) ? addslashes($Par_Sql[6]) : '';
			$val = isset($Par_Sql[7]) ? addslashes($Par_Sql[7]) : '';
			$ident = isset($Par_Sql[8]) ? addslashes($Par_Sql[8]) : '';
			$fec = isset($Par_Sql[9]) && $Par_Sql[9] !== '' ? addslashes($Par_Sql[9]) : date('Y-m-d H:i:s');
			$sql = "INSERT INTO `auditoria`.`logs` (`Usu_Cod`,`Pcs_Cod`,`Tab_Cod`,`Log_Fec`,`Eve_Cod`,`Log_Cam`,`Log_Val`,`Log_Int`,`Emp_Cod`,`Suc_Cod`)
			SELECT {$usu}, {$pcs}, t.Tab_Cod, '{$fec}', e.Eve_Cod,
				'{$cam}', '{$val}', '{$ident}', {$emp}, {$suc}
			FROM `auditoria`.`tablas` t
			INNER JOIN `auditoria`.`eventos` e ON e.Eve_Ini='{$eve}'
			WHERE t.Tab_Nom='{$tab}' LIMIT 1";
			return $sql;
		break;

		case 25:
			// Modulos raiz (Org_Niv=0): desde logs (LEFT JOIN) + cfg_monitoreo.
			// Antes usaba INNER JOIN a procesos: si Pcs_Cod del log no resolvia
			// en Dat_Dis, el combo Modulo quedaba vacio aunque hubiera actividad.
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$empF = aud_sql_emp_eq($emp);
			$modExpr = aud_sql_expr_modulo_cod();
			$modDesExpr = aud_sql_expr_modulo_des();
			$modExprCfg = aud_sql_expr_modulo_cod(array(
				'org' => 'o',
				'padre' => 'op',
				'abuelo' => 'oa',
				'bis' => 'ob'
			));
			$modDesExprCfg = aud_sql_expr_modulo_des(array(
				'org' => 'o',
				'padre' => 'op',
				'abuelo' => 'oa',
				'bis' => 'ob'
			));
			$sql = "SELECT DISTINCT t.`Org_Cod`,
				IFNULL(NULLIF(TRIM(t.`Org_Des`),''), CONCAT('Modulo ', t.`Org_Cod`)) AS `Org_Des`
			FROM (
				SELECT {$modExpr} AS `Org_Cod`,
					{$modDesExpr} AS `Org_Des`
				FROM `auditoria`.`logs`
				LEFT JOIN {$dbDis}.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
				".aud_sql_org_tree_joins('`procesos`')."
				WHERE `logs`.`Pcs_Cod` > 0 {$empF}
				UNION
				SELECT {$modExprCfg} AS `Org_Cod`,
					{$modDesExprCfg} AS `Org_Des`
				FROM `auditoria`.`cfg_monitoreo` c
				LEFT JOIN {$dbDis}.`organizado` o ON o.`Org_Cod` = c.`Org_Cod`
				LEFT JOIN {$dbDis}.`organizado` op ON op.`Org_Cod` = o.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` oa ON oa.`Org_Cod` = op.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` ob ON ob.`Org_Cod` = oa.`Org_Niv`
				WHERE c.`Emp_Cod`={$emp} AND c.`Cfg_Est`='A' AND c.`Org_Cod` > 0
			) t
			WHERE t.`Org_Cod` IS NOT NULL
			ORDER BY `Org_Des` ASC";
			return $sql;
		break;

		case 26:
			// Procesos para filtro: 0 emp, 1 directorio, 2 modulo (opcional).
			// LEFT JOIN + UNION cfg: el grid usa LEFT JOIN y muestra actividad
			// aunque el catalogo no resuelva; el combo no puede exigir INNER.
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$dir = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$mod = isset($Par_Sql[2]) ? (int)$Par_Sql[2] : 0;
			$empF = aud_sql_emp_eq($emp);
			$dirF = $dir > 0 ? " AND x.`Dir_Cod`={$dir}" : '';
			$modF = '';
			if ($mod > 0 && $dir <= 0) {
				$modF = " AND x.`Mod_Cod`={$mod}";
			}
			$lblPcsLog = "IFNULL(NULLIF(TRIM(p.`Pcs_Lin`),''), IFNULL(NULLIF(TRIM(p.`Pcs_Nom`),''), CONCAT('Proceso ', `logs`.`Pcs_Cod`)))";
			$lblPcsCfg = "IFNULL(NULLIF(TRIM(p.`Pcs_Lin`),''), IFNULL(NULLIF(TRIM(p.`Pcs_Nom`),''), CONCAT('Proceso ', c.`Pcs_Cod`)))";
			$lblPcsOrg = "IFNULL(NULLIF(TRIM(p.`Pcs_Lin`),''), IFNULL(NULLIF(TRIM(p.`Pcs_Nom`),''), CONCAT('Proceso ', p.`Pcs_Cod`)))";
			$modCodP = aud_sql_expr_modulo_cod(array(
				'org' => 'o',
				'padre' => 'op',
				'abuelo' => 'oa',
				'bis' => 'ob'
			));
			$sql = "SELECT DISTINCT x.`Pcs_Cod`,
				IFNULL(NULLIF(TRIM(x.`Pcs_Lin`),''), IFNULL(NULLIF(TRIM(x.`Pcs_Nom`),''), CONCAT('Proceso ', x.`Pcs_Cod`))) AS `Pcs_Lin`,
				x.`Pcs_Nom`, x.`Org_Des`
			FROM (
				SELECT `logs`.`Pcs_Cod` AS `Pcs_Cod`,
					{$lblPcsLog} AS `Pcs_Lin`,
					p.`Pcs_Nom` AS `Pcs_Nom`,
					o.`Org_Des` AS `Org_Des`,
					p.`Org_Cod` AS `Dir_Cod`,
					{$modCodP} AS `Mod_Cod`
				FROM `auditoria`.`logs`
				LEFT JOIN {$dbDis}.`procesos` p ON `logs`.`Pcs_Cod` = p.`Pcs_Cod`
				LEFT JOIN {$dbDis}.`organizado` o ON p.`Org_Cod` = o.`Org_Cod`
				LEFT JOIN {$dbDis}.`organizado` op ON op.`Org_Cod` = o.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` oa ON oa.`Org_Cod` = op.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` ob ON ob.`Org_Cod` = oa.`Org_Niv`
				WHERE `logs`.`Pcs_Cod` > 0 {$empF}
				UNION
				SELECT c.`Pcs_Cod` AS `Pcs_Cod`,
					{$lblPcsCfg} AS `Pcs_Lin`,
					p.`Pcs_Nom` AS `Pcs_Nom`,
					o.`Org_Des` AS `Org_Des`,
					IFNULL(p.`Org_Cod`, c.`Org_Cod`) AS `Dir_Cod`,
					{$modCodP} AS `Mod_Cod`
				FROM `auditoria`.`cfg_monitoreo` c
				LEFT JOIN {$dbDis}.`procesos` p ON c.`Pcs_Cod` = p.`Pcs_Cod`
				LEFT JOIN {$dbDis}.`organizado` o ON IFNULL(p.`Org_Cod`, c.`Org_Cod`) = o.`Org_Cod`
				LEFT JOIN {$dbDis}.`organizado` op ON op.`Org_Cod` = o.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` oa ON oa.`Org_Cod` = op.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` ob ON ob.`Org_Cod` = oa.`Org_Niv`
				WHERE c.`Emp_Cod`={$emp} AND c.`Cfg_Est`='A' AND c.`Pcs_Cod` > 0
				UNION
				SELECT p.`Pcs_Cod` AS `Pcs_Cod`,
					{$lblPcsOrg} AS `Pcs_Lin`,
					p.`Pcs_Nom` AS `Pcs_Nom`,
					o.`Org_Des` AS `Org_Des`,
					p.`Org_Cod` AS `Dir_Cod`,
					{$modCodP} AS `Mod_Cod`
				FROM `auditoria`.`cfg_monitoreo` c
				INNER JOIN {$dbDis}.`procesos` p ON (
					p.`Org_Cod` = c.`Org_Cod`
					OR p.`Org_Cod` IN (
						SELECT o2.`Org_Cod` FROM {$dbDis}.`organizado` o2 WHERE o2.`Org_Niv` = c.`Org_Cod`
					)
				)
				LEFT JOIN {$dbDis}.`organizado` o ON p.`Org_Cod` = o.`Org_Cod`
				LEFT JOIN {$dbDis}.`organizado` op ON op.`Org_Cod` = o.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` oa ON oa.`Org_Cod` = op.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` ob ON ob.`Org_Cod` = oa.`Org_Niv`
				WHERE c.`Emp_Cod`={$emp} AND c.`Cfg_Est`='A' AND c.`Pcs_Cod` = 0 AND c.`Org_Cod` > 0
					AND IFNULL(p.`Pcs_Est`,'A')='A'
			) x
			WHERE x.`Pcs_Cod` > 0 {$dirF} {$modF}
			ORDER BY `Pcs_Lin` ASC";
			return $sql;
		break;

		/**
		 * Usuarios de la empresa activa (combo filtro).
		 * Agrupa cuentas por persona (nombre) para no mostrar repetidos;
		 * las cuentas de la misma persona van en Usu_Cods y N_Ctas.
		 * 0 emp, 1 suc (opcional: si >0 lista solo usuarios de esa sucursal)
		 */
		case 27:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$suc = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$empF = $emp > 0 ? " AND s.`Emp_Cod`={$emp}" : ' AND 1=0';
			$sucF = $suc > 0 ? " AND u.`Suc_Cod`={$suc}" : '';
			$apeNom = "TRIM(CONCAT(IFNULL(`persona`.`Prs_Ape`,''),' ',IFNULL(`persona`.`Prs_Nom`,'')))";
			$grupoUsu = "IF({$apeNom}='', -u.`Usu_Cod`, {$apeNom})";
			$sql = "SELECT MIN(u.`Usu_Cod`) AS `Usu_Cod`,
				GROUP_CONCAT(DISTINCT u.`Usu_Cod` ORDER BY u.`Usu_Cod` SEPARATOR ',') AS `Usu_Cods`,
				COUNT(DISTINCT u.`Usu_Cod`) AS `N_Ctas`,
				IFNULL(NULLIF({$apeNom},''), CONCAT('Usuario #', MIN(u.`Usu_Cod`))) AS `Usu_Nom`
			FROM {$dbDis}.`usuarios` u
			INNER JOIN {$dbDis}.`sucursal` s ON u.`Suc_Cod` = s.`Suc_Cod`
			LEFT JOIN {$dbDis}.`persona` ON u.`Prs_Cod` = `persona`.`Prs_Cod`
			WHERE u.`Usu_Cod` > 0 {$empF} {$sucF}
			GROUP BY {$grupoUsu}
			ORDER BY `Usu_Nom` ASC, `Usu_Cod` ASC";
			return $sql;
		break;

		/**
		 * Sucursales de la empresa (filtro monitoreo)
		 * 0 emp
		 */
		case 28:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$empF = $emp > 0 ? " WHERE s.`Emp_Cod`={$emp}" : '';
			$sql = "SELECT s.`Suc_Cod`, s.`Suc_Des`, s.`Emp_Cod`
			FROM {$dbDis}.`sucursal` s
			{$empF}
			ORDER BY s.`Suc_Des` ASC";
			return $sql;
		break;

		/**
		 * Conteo de sucursales de la empresa
		 * 0 emp
		 */
		case 29:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT COUNT(*) AS `count` FROM {$dbDis}.`sucursal` WHERE `Emp_Cod`=".(int)$emp;
			return $sql;
		break;

		/**
		 * Directorios (organizado inmediato) presentes en logs
		 * 0 emp, 1 modulo raiz (opcional)
		 */
		case 30:
			// Directorios: logs (LEFT JOIN) + cfg_monitoreo (misma logica que modulos/procesos).
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$mod = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$empF = aud_sql_emp_eq($emp);
			$modFLog = '';
			$modFCfg = '';
			if ($mod > 0) {
				$modFLog = " AND (".aud_sql_expr_modulo_cod()."={$mod})";
				$modFCfg = " AND (".aud_sql_expr_modulo_cod(array(
					'org' => 'o',
					'padre' => 'op',
					'abuelo' => 'oa',
					'bis' => 'ob'
				))."={$mod})";
			}
			$sql = "SELECT DISTINCT t.`Org_Cod`,
				IFNULL(NULLIF(TRIM(t.`Org_Des`),''), CONCAT('Directorio ', t.`Org_Cod`)) AS `Org_Des`
			FROM (
				SELECT `organizado`.`Org_Cod`, `organizado`.`Org_Des`
				FROM `auditoria`.`logs`
				LEFT JOIN {$dbDis}.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
				".aud_sql_org_tree_joins('`procesos`')."
				WHERE `logs`.`Pcs_Cod` > 0
					AND `organizado`.`Org_Cod` IS NOT NULL
					{$empF} {$modFLog}
				UNION
				SELECT o.`Org_Cod`, o.`Org_Des`
				FROM `auditoria`.`cfg_monitoreo` c
				LEFT JOIN {$dbDis}.`organizado` o ON o.`Org_Cod` = c.`Org_Cod`
				LEFT JOIN {$dbDis}.`organizado` op ON op.`Org_Cod` = o.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` oa ON oa.`Org_Cod` = op.`Org_Niv`
				LEFT JOIN {$dbDis}.`organizado` ob ON ob.`Org_Cod` = oa.`Org_Niv`
				WHERE c.`Emp_Cod`={$emp} AND c.`Cfg_Est`='A' AND c.`Org_Cod` > 0
					AND o.`Org_Cod` IS NOT NULL
					{$modFCfg}
			) t
			WHERE t.`Org_Cod` IS NOT NULL
			ORDER BY `Org_Des` ASC";
			return $sql;
		break;

		/**
		 * Exportacion: mismos filtros que el grid, tope de filas.
		 * Indices iguales a case 12.
		 */
		case 31:
			$where = aud_logs_filtro($Par_Sql);
			$limit = 5000;
			$sql = aud_logs_select()."
			$where
			ORDER BY `logs`.`Log_Fec` DESC, `logs`.`Log_Cod` DESC
			LIMIT {$limit}";
			return $sql;
		break;

		/** Conteo de reglas cfg_monitoreo de la empresa */
		case 32:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT COUNT(*) AS `count` FROM `auditoria`.`cfg_monitoreo`
			WHERE `Emp_Cod`={$emp} AND `Cfg_Est`='A'";
			return $sql;
		break;

		/**
		 * Plantas de manifiesto (Pla_Cod -> Pla_Nom) para la columna Planta del informe
		 */
		case 33:
			$sql = "SELECT `Pla_Cod`, `Pla_Nom` FROM {$dbDis}.`manifiesto_plantas` ORDER BY `Pla_Nom` ASC";
			return $sql;
		break;

		/**
		 * Alcance de la auditoria: reglas cfg_monitoreo activas con nombres resueltos.
		 * 0 emp
		 */
		case 34:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sql = "SELECT c.`Org_Cod`, c.`Pcs_Cod`, o.`Org_Des`, o.`Org_Niv`, p.`Pcs_Lin`, p.`Pcs_Nom`
			FROM `auditoria`.`cfg_monitoreo` c
			LEFT JOIN {$dbDis}.`organizado` o ON o.`Org_Cod` = c.`Org_Cod`
			LEFT JOIN {$dbDis}.`procesos` p ON p.`Pcs_Cod` = c.`Pcs_Cod`
			WHERE c.`Emp_Cod`={$emp} AND c.`Cfg_Est`='A'
			ORDER BY o.`Org_Des` ASC, p.`Pcs_Lin` ASC";
			return $sql;
		break;

		/**
		 * Un proceso tiene que ver con plantas si alguna de sus tablas registradas
		 * tiene columna Pla_Cod o algun log suyo incluyo el campo Pla_Cod.
		 * 0 pcs
		 */
		case 35:
			$pcs = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$dbSchema = trim($dbDis, '`');
			$sql = "SELECT COUNT(*) AS `count`
			FROM `auditoria`.`logs` l
			INNER JOIN `auditoria`.`tablas` t ON t.`Tab_Cod` = l.`Tab_Cod`
			WHERE l.`Pcs_Cod` = {$pcs}
			AND (
				l.`Log_Cam` LIKE '%Pla_Cod%'
				OR EXISTS (
					SELECT 1 FROM `information_schema`.`columns` c
					WHERE c.`table_schema` = '{$dbSchema}' AND c.`table_name` = t.`Tab_Nom` COLLATE utf8mb3_general_ci AND c.`column_name` = 'Pla_Cod'
				)
			)";
			return $sql;
		break;

		/**
		 * Historial de un registro: todos los movimientos del mismo tipo de tab
		 * (Tab_Cod) e identificador (Log_Int) dentro de la misma empresa.
		 * El identificador se compara con LIKE por prefijo para tolerar el
		 * sufijo " || OLD:..." que agrega el evento Actualizar.
		 * 0 tab, 1 emp, 2 identificador (base), 3 limite
		 */
		case 36:
			$tab = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$emp = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$ident = isset($Par_Sql[2]) ? trim((string)$Par_Sql[2]) : '';
			$lim = isset($Par_Sql[3]) ? max(1, (int)$Par_Sql[3]) : 100;
			if ($lim > 500) {
				$lim = 500;
			}
			$identEsc = str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), addslashes($ident));
			$empF = $emp > 0 ? " AND `logs`.`Emp_Cod`={$emp}" : '';
			if ($tab <= 0 || $ident === '') {
				return "SELECT `logs`.`Log_Cod` FROM `auditoria`.`logs` WHERE 1=0";
			}
			return aud_logs_select()."
			WHERE `logs`.`Tab_Cod` = {$tab}
			  AND `logs`.`Log_Int` LIKE '{$identEsc}%' {$empF}
			ORDER BY `logs`.`Log_Fec` ASC, `logs`.`Log_Cod` ASC
			LIMIT {$lim}";
		break;

		/** Fecha mas antigua con datos de auditoria (para el aviso "desde cuando hay datos") */
		case 37:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$empF = $emp > 0 ? " WHERE `Emp_Cod`={$emp}" : '';
			return "SELECT MIN(`Log_Fec`) AS `min_fec` FROM `auditoria`.`logs`{$empF}";
		break;
	}
}

function aud_logs_select(){
	return "SELECT `logs`.`Log_Cod`,`logs`.`Log_Fec`,`logs`.`Usu_Cod`,`logs`.`Emp_Cod`,`logs`.`Suc_Cod`,
				`logs`.`Log_Cam`,`logs`.`Log_Val`,`logs`.`Log_Int`,`logs`.`Tab_Cod`,`logs`.`Eve_Cod`,`logs`.`Pcs_Cod`,
				`eventos`.`Eve_Des`,`eventos`.`Eve_Ini`,`tablas`.`Tab_Nom`,`tablas`.`Tab_Ali`,`tablas`.`Tab_Des`,
				`procesos`.`Pcs_Lin`,`procesos`.`Pcs_Det`,`procesos`.`Pcs_Nom`,
				`organizado`.`Org_Cod` AS `Dir_Cod`,
				`organizado`.`Org_Des` AS `Dir_Des`,
				`organizado`.`Org_Des`,
				".aud_sql_expr_modulo_des()." AS `Mod_Des`,
				".aud_sql_expr_modulo_cod()." AS `Mod_Cod`,
				`empresas`.`Emp_Nom`,`sucursal`.`Suc_Des`,
				CONCAT(IFNULL(`persona`.`Prs_Ape`,''),' ',IFNULL(`persona`.`Prs_Nom`,'')) AS `Usu_Nom`
			FROM `auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			".aud_logs_joins();
}

/** Joins del arbol organizado (directorio + 3 ancestros). */
function aud_sql_org_tree_joins($fromProcesos){
	$dbDis = aud_sql_db_dis();
	return "LEFT JOIN {$dbDis}.`organizado` ON {$fromProcesos}.`Org_Cod` = `organizado`.`Org_Cod`
			LEFT JOIN {$dbDis}.`organizado` `org_padre` ON `org_padre`.`Org_Cod` = `organizado`.`Org_Niv`
			LEFT JOIN {$dbDis}.`organizado` `org_abuelo` ON `org_abuelo`.`Org_Cod` = `org_padre`.`Org_Niv`
			LEFT JOIN {$dbDis}.`organizado` `org_bisabuelo` ON `org_bisabuelo`.`Org_Cod` = `org_abuelo`.`Org_Niv`";
}

/** Joins comunes listado/conteo (directorio + padres para modulo) */
function aud_logs_joins(){
	$dbDis = aud_sql_db_dis();
	return "LEFT JOIN {$dbDis}.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
			".aud_sql_org_tree_joins('`procesos`')."
			LEFT JOIN {$dbDis}.`empresas` ON `logs`.`Emp_Cod` = `empresas`.`Emp_Cod`
			LEFT JOIN {$dbDis}.`sucursal` ON `logs`.`Suc_Cod` = `sucursal`.`Suc_Cod`
			LEFT JOIN {$dbDis}.`usuarios` ON `logs`.`Usu_Cod` = `usuarios`.`Usu_Cod`
			LEFT JOIN {$dbDis}.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`";
}

function aud_sql_org_alias($a){
	$d = array(
		'org' => '`organizado`',
		'padre' => '`org_padre`',
		'abuelo' => '`org_abuelo`',
		'bis' => '`org_bisabuelo`'
	);
	if (!is_array($a)) {
		return $d;
	}
	return array_merge($d, $a);
}

/** Nombre del modulo raiz (Org_Niv=0). Nunca un directorio intermedio. */
function aud_sql_expr_modulo_des($alias = null){
	$a = aud_sql_org_alias($alias);
	return "CASE
		WHEN IFNULL({$a['org']}.`Org_Niv`,0) = 0 THEN {$a['org']}.`Org_Des`
		WHEN IFNULL({$a['padre']}.`Org_Niv`,0) = 0 THEN {$a['padre']}.`Org_Des`
		WHEN IFNULL({$a['abuelo']}.`Org_Niv`,0) = 0 THEN {$a['abuelo']}.`Org_Des`
		WHEN IFNULL({$a['bis']}.`Org_Niv`,0) = 0 THEN {$a['bis']}.`Org_Des`
		ELSE NULL
	END";
}

function aud_sql_expr_modulo_cod($alias = null){
	$a = aud_sql_org_alias($alias);
	return "CASE
		WHEN IFNULL({$a['org']}.`Org_Niv`,0) = 0 THEN {$a['org']}.`Org_Cod`
		WHEN IFNULL({$a['padre']}.`Org_Niv`,0) = 0 THEN {$a['padre']}.`Org_Cod`
		WHEN IFNULL({$a['abuelo']}.`Org_Niv`,0) = 0 THEN {$a['abuelo']}.`Org_Cod`
		WHEN IFNULL({$a['bis']}.`Org_Niv`,0) = 0 THEN {$a['bis']}.`Org_Cod`
		ELSE NULL
	END";
}

/**
 * Busca un proceso por nombre de pagina (Pcs_Nom), opcionalmente filtrado por modulo raiz.
 * No usa Pcs_Lin ni LIKE '%texto%' (evita cruzar modulos).
 */
function aud_sql_buscar_proceso_sim($nom, $modLike = ''){
	$dbDis = aud_sql_db_dis();
	$nom = addslashes(trim((string)$nom));
	$modLike = addslashes(trim((string)$modLike));
	$modF = '';
	if ($modLike !== '') {
		$modF = " AND (".aud_sql_expr_modulo_des()." LIKE '%{$modLike}%')";
	}
	return "SELECT p.`Pcs_Cod`, p.`Pcs_Lin`, p.`Pcs_Det`, p.`Pcs_Nom`,
		`organizado`.`Org_Des`,
		`organizado`.`Org_Cod` AS `Dir_Cod`,
		`organizado`.`Org_Niv` AS `Dir_Niv`,
		".aud_sql_expr_modulo_des()." AS `Mod_Des`,
		".aud_sql_expr_modulo_cod()." AS `Mod_Cod`
	FROM {$dbDis}.`procesos` p
	".aud_sql_org_tree_joins('p')."
	WHERE IFNULL(p.`Pcs_Est`,'A')='A' AND (
		p.`Pcs_Nom` = '{$nom}'
		OR p.`Pcs_Nom` = '{$nom}.php'
		OR p.`Pcs_Nom` LIKE '{$nom}_%'
		OR p.`Pcs_Nom` LIKE '{$nom}.%'
	) {$modF}
	ORDER BY CASE
		WHEN p.`Pcs_Nom` = '{$nom}' OR p.`Pcs_Nom` = '{$nom}.php' THEN 0
		ELSE 1
	END, p.`Pcs_Cod` ASC
	LIMIT 1";
}

/** Filtro estricto por empresa de sesion (sin Emp_Cod nulo: no mezcla al cambiar de empresa). */
function aud_sql_emp_eq($emp, $alias = '`logs`'){
	$emp = (int)$emp;
	if ($emp <= 0) {
		return '';
	}
	return " AND {$alias}.`Emp_Cod`={$emp}";
}

function aud_logs_filtro($Par_Sql){
	$w = ' WHERE 1=1 ';
	if (isset($Par_Sql[0]) && $Par_Sql[0] !== '' && (int)$Par_Sql[0] > 0) {
		$w .= aud_sql_emp_eq($Par_Sql[0]);
	}
	if (isset($Par_Sql[1]) && $Par_Sql[1] !== '' && isset($Par_Sql[2]) && $Par_Sql[2] !== '') {
		$from = addslashes($Par_Sql[1]);
		$to = addslashes($Par_Sql[2]);
		if (strlen($from) <= 10) $from .= ' 00:00:00';
		if (strlen($to) <= 10) $to .= ' 23:59:59';
		$w .= " AND (`logs`.`Log_Fec` BETWEEN '{$from}' AND '{$to}')";
	}
	if (isset($Par_Sql[3]) && $Par_Sql[3] !== '' && (int)$Par_Sql[3] > 0) {
		$w .= ' AND `logs`.`Eve_Cod`='.(int)$Par_Sql[3];
	}
	// Par_Sql[4]: Modulo raiz (Org_Niv=0)
	if (isset($Par_Sql[4]) && $Par_Sql[4] !== '' && (int)$Par_Sql[4] > 0) {
		$mod = (int)$Par_Sql[4];
		$w .= " AND (".aud_sql_expr_modulo_cod()."={$mod})";
	}
	// Par_Sql[5]: Pcs_Cod (proceso)
	if (isset($Par_Sql[5]) && $Par_Sql[5] !== '' && (int)$Par_Sql[5] > 0) {
		$w .= ' AND `logs`.`Pcs_Cod`='.(int)$Par_Sql[5];
	}
	// Opcional: Tab_Cod en [6] y Usu_Cod en [7]
	if (isset($Par_Sql[6]) && $Par_Sql[6] !== '' && (int)$Par_Sql[6] > 0) {
		$w .= ' AND `logs`.`Tab_Cod`='.(int)$Par_Sql[6];
	}
	if (isset($Par_Sql[7]) && trim((string)$Par_Sql[7]) !== '') {
		$usus = array();
		foreach (explode(',', trim((string)$Par_Sql[7])) as $us) {
			$us = (int)$us;
			if ($us > 0) {
				$usus[] = $us;
			}
		}
		if (count($usus) === 1) {
			$w .= ' AND `logs`.`Usu_Cod`='.$usus[0];
		} elseif (count($usus) > 1) {
			$w .= ' AND `logs`.`Usu_Cod` IN ('.implode(',', $usus).')';
		}
	}
	// Par_Sql[10]: Suc_Cod (sucursal)
	if (isset($Par_Sql[10]) && $Par_Sql[10] !== '' && (int)$Par_Sql[10] > 0) {
		$w .= ' AND `logs`.`Suc_Cod`='.(int)$Par_Sql[10];
	}
	// Par_Sql[11]: Directorio (organizado inmediato del proceso)
	if (isset($Par_Sql[11]) && $Par_Sql[11] !== '' && (int)$Par_Sql[11] > 0) {
		$w .= ' AND `procesos`.`Org_Cod`='.(int)$Par_Sql[11];
	}
	// Par_Sql[12]: Planta (valor de Pla_Cod en la posicion del par Log_Cam/Log_Val)
	if (isset($Par_Sql[12]) && $Par_Sql[12] !== '' && (int)$Par_Sql[12] > 0) {
		$pla = (int)$Par_Sql[12];
		$posPla = "LENGTH(SUBSTRING_INDEX(`logs`.`Log_Cam`, 'Pla_Cod', 1)) - LENGTH(REPLACE(SUBSTRING_INDEX(`logs`.`Log_Cam`, 'Pla_Cod', 1), ',', '')) + 1";
		$valPla = "REPLACE(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(`logs`.`Log_Val`, ',', {$posPla}), ',', -1)), '~', '')";
		$w .= " AND `logs`.`Log_Cam` LIKE '%Pla_Cod%' AND CAST({$valPla} AS UNSIGNED) = {$pla}";
	}
	return $w;
}
?>