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
/** Base maestra de catalogo (usuarios, persona, empresas, sucursal, procesos, organizado). */
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
		if (class_exists('Env')) {
			$db = \Env::get('DB_DATABASE_CORP', '');
			if (is_string($db) && $db !== '' && $db !== 'exa_master') {
				return preg_replace('/[^a-zA-Z0-9_]/', '', $db);
			}
			$db2 = \Env::get('DB_DATABASE', '');
			if (is_string($db2) && $db2 !== '' && $db2 !== 'exa_master') {
				return preg_replace('/[^a-zA-Z0-9_]/', '', $db2);
			}
		}
		return 'exa';
	}
}
function sentencias($id,$Par_Sql){
	$mdb = aud_master_db();
	switch($id){
		/**
		 * Busqueda de un usuario por sucursal y coincidencias en su apellido
		 */
		case 1:
			$sql = "SELECT `persona`.`Prs_Cod`,`persona`.`Prs_Nom`,`persona`.`Prs_Ape`,`persona`.`Prs_Ced`,`usuarios`.`Usu_Cod`,`usuarios`.`Usu_Est`
			FROM
			`{$mdb}`.`usuarios`
			INNER JOIN `{$mdb}`.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
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
			`{$mdb}`.`usuarios`
			INNER JOIN `{$mdb}`.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
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
			`{$mdb}`.`usuarios`
			INNER JOIN `{$mdb}`.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
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
			`{$mdb}`.`usuarios`
			INNER JOIN `auditoria`.`sesion` ON `usuarios`.`Usu_Cod` = `sesion`.`Usu_Cod`
			INNER JOIN `{$mdb}`.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
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
			`{$mdb}`.`usuarios`
			INNER JOIN `{$mdb}`.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
			INNER JOIN `auditoria`.`sesion` ON `sesion`.`Usu_Cod` = `usuarios`.`Usu_Cod`
			WHERE
			`usuarios`.`Usu_Cod` = $Par_Sql[0] AND
			`sesion`.`Ses_Cod` = $Par_Sql[1]";
			return $sql;
		break;
		
		/**
		 * Obtener las actividades durante esa sessi�n del usuario 
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
			LEFT JOIN `{$mdb}`.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
			LEFT JOIN `{$mdb}`.`empresas` ON `logs`.`Emp_Cod` = `empresas`.`Emp_Cod`
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
			LEFT JOIN `{$mdb}`.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
			LEFT JOIN `{$mdb}`.`empresas` ON `logs`.`Emp_Cod` = `empresas`.`Emp_Cod`
			WHERE `logs`.`Usu_Cod` = $Par_Sql[0] AND (`logs`.`Log_Fec` BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]') AND `tablas`.`Tab_Cod` = '$tabCod' $empFiltro
			ORDER BY `Log_Fec` DESC";
			return $sql;
		break;
		
		/**
		 * Obtener el conteo de registro segun la sesi�n
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
			LEFT JOIN `{$mdb}`.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
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
			FROM `{$mdb}`.`procesos` p
			LEFT JOIN `{$mdb}`.`organizado` o ON p.`Org_Cod` = o.`Org_Cod`
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
			// Modulos raiz (Org_Niv=0) presentes en logs
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$empF = aud_sql_emp_eq($emp);
			$sql = "SELECT DISTINCT t.`Org_Cod`, t.`Org_Des` FROM (
				SELECT ".aud_sql_expr_modulo_cod()." AS `Org_Cod`,
					".aud_sql_expr_modulo_des()." AS `Org_Des`
				FROM `auditoria`.`logs`
				INNER JOIN `{$mdb}`.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
				".aud_sql_org_tree_joins('`procesos`')."
				WHERE 1=1 {$empF}
			) t
			WHERE t.`Org_Cod` IS NOT NULL AND t.`Org_Des` IS NOT NULL AND TRIM(t.`Org_Des`)<>''
			ORDER BY t.`Org_Des` ASC";
			return $sql;
		break;

		case 26:
			// Procesos: 0 emp, 1 directorio, 2 modulo (opcional)
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$dir = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$mod = isset($Par_Sql[2]) ? (int)$Par_Sql[2] : 0;
			$empF = aud_sql_emp_eq($emp);
			$dirF = $dir > 0 ? " AND p.`Org_Cod`={$dir}" : '';
			$modF = '';
			if ($mod > 0 && $dir <= 0) {
				$modF = " AND (".aud_sql_expr_modulo_cod(array(
					'org' => 'o',
					'padre' => 'op',
					'abuelo' => 'oa',
					'bis' => 'ob'
				))."={$mod})";
			}
			$sql = "SELECT DISTINCT p.`Pcs_Cod`, p.`Pcs_Lin`, p.`Pcs_Nom`, o.`Org_Des`
			FROM `auditoria`.`logs`
			INNER JOIN `{$mdb}`.`procesos` p ON `logs`.`Pcs_Cod` = p.`Pcs_Cod`
			LEFT JOIN `{$mdb}`.`organizado` o ON p.`Org_Cod` = o.`Org_Cod`
			LEFT JOIN `{$mdb}`.`organizado` op ON op.`Org_Cod` = o.`Org_Niv`
			LEFT JOIN `{$mdb}`.`organizado` oa ON oa.`Org_Cod` = op.`Org_Niv`
			LEFT JOIN `{$mdb}`.`organizado` ob ON ob.`Org_Cod` = oa.`Org_Niv`
			WHERE p.`Pcs_Cod` > 0 {$empF} {$dirF} {$modF}
			ORDER BY IFNULL(p.`Pcs_Lin`, p.`Pcs_Nom`) ASC";
			return $sql;
		break;

		/**
		 * Usuarios de la empresa activa (combo filtro).
		 * 0 emp, 1 suc (opcional: si >0 lista solo usuarios de esa sucursal)
		 */
		case 27:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$suc = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$empF = $emp > 0 ? " AND s.`Emp_Cod`={$emp}" : ' AND 1=0';
			$sucF = $suc > 0 ? " AND u.`Suc_Cod`={$suc}" : '';
			$sql = "SELECT DISTINCT u.`Usu_Cod`,
				TRIM(CONCAT(IFNULL(`persona`.`Prs_Ape`,''),' ',IFNULL(`persona`.`Prs_Nom`,''))) AS `Usu_Nom`
			FROM `{$mdb}`.`usuarios` u
			INNER JOIN `{$mdb}`.`sucursal` s ON u.`Suc_Cod` = s.`Suc_Cod`
			LEFT JOIN `{$mdb}`.`persona` ON u.`Prs_Cod` = `persona`.`Prs_Cod`
			WHERE u.`Usu_Cod` > 0 {$empF} {$sucF}
			ORDER BY `Usu_Nom` ASC, u.`Usu_Cod` ASC";
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
			FROM `{$mdb}`.`sucursal` s
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
			$sql = "SELECT COUNT(*) AS `count` FROM `{$mdb}`.`sucursal` WHERE `Emp_Cod`=".(int)$emp;
			return $sql;
		break;

		/**
		 * Directorios (organizado inmediato) presentes en logs
		 * 0 emp, 1 modulo raiz (opcional)
		 */
		case 30:
			$emp = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$mod = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$empF = aud_sql_emp_eq($emp);
			$modF = '';
			if ($mod > 0) {
				$modF = " AND (".aud_sql_expr_modulo_cod()."={$mod})";
			}
			$sql = "SELECT DISTINCT `organizado`.`Org_Cod`, `organizado`.`Org_Des`
			FROM `auditoria`.`logs`
			INNER JOIN `{$mdb}`.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
			".aud_sql_org_tree_joins('`procesos`')."
			WHERE `organizado`.`Org_Des` IS NOT NULL AND TRIM(`organizado`.`Org_Des`)<>'' {$empF} {$modF}
			ORDER BY `organizado`.`Org_Des` ASC";
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

		/** KPI 1: Conteo por Tipo de Evento (Insert, Update, Delete) */
		case 33:
			$where = aud_logs_filtro($Par_Sql);
			$sql = "SELECT `eventos`.`Eve_Ini`, `eventos`.`Eve_Des`, COUNT(`logs`.`Log_Cod`) AS `total`
			FROM `auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			".aud_logs_joins()."
			$where
			GROUP BY `eventos`.`Eve_Cod`
			ORDER BY `total` DESC";
			return $sql;
		break;

		/** KPI 2: Conteo por Fecha (Tendencia temporal) */
		case 34:
			$where = aud_logs_filtro($Par_Sql);
			$sql = "SELECT DATE(`logs`.`Log_Fec`) AS `fecha`, COUNT(`logs`.`Log_Cod`) AS `total`
			FROM `auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			".aud_logs_joins()."
			$where
			GROUP BY DATE(`logs`.`Log_Fec`)
			ORDER BY `fecha` ASC
			LIMIT 60";
			return $sql;
		break;

		/** KPI 3: Top Modulos mas activos */
		case 35:
			$where = aud_logs_filtro($Par_Sql);
			$sql = "SELECT IFNULL(".aud_sql_expr_modulo_des().", 'Sin modulo') AS `modulo`, COUNT(`logs`.`Log_Cod`) AS `total`
			FROM `auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			".aud_logs_joins()."
			$where
			GROUP BY `modulo`
			ORDER BY `total` DESC
			LIMIT 7";
			return $sql;
		break;

		/** KPI 4: Top Usuarios con mas movimientos */
		case 36:
			$where = aud_logs_filtro($Par_Sql);
			$sql = "SELECT IFNULL(NULLIF(TRIM(CONCAT(IFNULL(`persona`.`Prs_Ape`,''),' ',IFNULL(`persona`.`Prs_Nom`,''))),''), CONCAT('Usuario ', `logs`.`Usu_Cod`)) AS `usuario`, COUNT(`logs`.`Log_Cod`) AS `total`
			FROM `auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			".aud_logs_joins()."
			$where
			GROUP BY `logs`.`Usu_Cod`
			ORDER BY `total` DESC
			LIMIT 7";
			return $sql;
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
	$mdb = aud_master_db();
	return "LEFT JOIN `{$mdb}`.`organizado` ON {$fromProcesos}.`Org_Cod` = `organizado`.`Org_Cod`
			LEFT JOIN `{$mdb}`.`organizado` `org_padre` ON `org_padre`.`Org_Cod` = `organizado`.`Org_Niv`
			LEFT JOIN `{$mdb}`.`organizado` `org_abuelo` ON `org_abuelo`.`Org_Cod` = `org_padre`.`Org_Niv`
			LEFT JOIN `{$mdb}`.`organizado` `org_bisabuelo` ON `org_bisabuelo`.`Org_Cod` = `org_abuelo`.`Org_Niv`";
}

/** Joins comunes listado/conteo (directorio + padres para modulo) */
function aud_logs_joins(){
	$mdb = aud_master_db();
	return "LEFT JOIN `{$mdb}`.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
			".aud_sql_org_tree_joins('`procesos`')."
			LEFT JOIN `{$mdb}`.`empresas` ON `logs`.`Emp_Cod` = `empresas`.`Emp_Cod`
			LEFT JOIN `{$mdb}`.`sucursal` ON `logs`.`Suc_Cod` = `sucursal`.`Suc_Cod`
			LEFT JOIN `{$mdb}`.`usuarios` ON `logs`.`Usu_Cod` = `usuarios`.`Usu_Cod`
			LEFT JOIN `{$mdb}`.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`";
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
	$mdb = aud_master_db();
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
	FROM `{$mdb}`.`procesos` p
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
	if (isset($Par_Sql[7]) && $Par_Sql[7] !== '' && (int)$Par_Sql[7] > 0) {
		$w .= ' AND `logs`.`Usu_Cod`='.(int)$Par_Sql[7];
	}
	// Par_Sql[10]: Suc_Cod (sucursal)
	if (isset($Par_Sql[10]) && $Par_Sql[10] !== '' && (int)$Par_Sql[10] > 0) {
		$w .= ' AND `logs`.`Suc_Cod`='.(int)$Par_Sql[10];
	}
	// Par_Sql[11]: Directorio (organizado inmediato del proceso)
	if (isset($Par_Sql[11]) && $Par_Sql[11] !== '' && (int)$Par_Sql[11] > 0) {
		$w .= ' AND `procesos`.`Org_Cod`='.(int)$Par_Sql[11];
	}
	// Par_Sql[12]: Busqueda de texto libre en Log_Val, Log_Cam, Log_Int, Tab_Ali, Tab_Nom, Pcs_Lin, Persona
	if (isset($Par_Sql[12]) && trim((string)$Par_Sql[12]) !== '') {
		$q = addslashes(trim((string)$Par_Sql[12]));
		$w .= " AND (
			`logs`.`Log_Val` LIKE '%{$q}%'
			OR `logs`.`Log_Int` LIKE '%{$q}%'
			OR `logs`.`Log_Cam` LIKE '%{$q}%'
			OR `tablas`.`Tab_Ali` LIKE '%{$q}%'
			OR `tablas`.`Tab_Nom` LIKE '%{$q}%'
			OR `procesos`.`Pcs_Lin` LIKE '%{$q}%'
			OR `persona`.`Prs_Ape` LIKE '%{$q}%'
			OR `persona`.`Prs_Nom` LIKE '%{$q}%'
		)";
	}
	return $w;
}
?>