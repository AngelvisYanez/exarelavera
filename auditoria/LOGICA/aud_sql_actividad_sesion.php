<?php
/**
 * Sentencias SQL para el monitor de actividad de usuarios, tracking de sesiones,
 * tiempos de uso, IP, ubicacion, navegadores y control de inactividad.
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

function sentencias_actividad_sesion($tipo, $Par_Sql = array())
{
	$masterDb = aud_sql_db_dis();

	switch ($tipo) {
		/**
		 * Case 1: Asegurar columnas e indices en auditoria.sesion
		 */
		case 1:
			return "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = 'auditoria' AND TABLE_NAME = 'sesion'";

		/**
		 * Case 2: Obtener datos de la ultima sesion de un usuario
		 */
		case 2:
			$usuCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			return "SELECT * FROM `auditoria`.`sesion` 
				WHERE `Usu_Cod` = {$usuCod} 
				ORDER BY `Ses_Cod` DESC LIMIT 1";

		/**
		 * Case 3: Insertar registro de inicio de sesion con IP, Ubicacion, Navegador y Token
		 */
		case 3:
			$sesCod  = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$usuCod  = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$sesInt  = isset($Par_Sql[2]) ? addslashes($Par_Sql[2]) : date('Y-m-d H:i:s');
			$empCod  = isset($Par_Sql[3]) ? (int)$Par_Sql[3] : 0;
			$sucCod  = isset($Par_Sql[4]) ? (int)$Par_Sql[4] : 0;
			$sesIp   = isset($Par_Sql[5]) ? addslashes($Par_Sql[5]) : '';
			$sesUbi  = isset($Par_Sql[6]) ? addslashes($Par_Sql[6]) : '';
			$sesNav  = isset($Par_Sql[7]) ? addslashes($Par_Sql[7]) : '';
			$sesTok  = isset($Par_Sql[8]) ? addslashes($Par_Sql[8]) : '';
			$sesDev  = isset($Par_Sql[9]) ? addslashes(trim($Par_Sql[9])) : '';
			$sesMac  = isset($Par_Sql[10]) ? addslashes(trim($Par_Sql[10])) : '';
			$sesOau  = isset($Par_Sql[11]) ? addslashes(trim($Par_Sql[11])) : '';
			$sesFp   = isset($Par_Sql[12]) ? addslashes(trim($Par_Sql[12])) : '';

			// Si el esquema no tiene las columnas OAuth (produccion sin privilegios
			// DDL para crearlas), el INSERT debe omitirlas para no fallar con 1054.
			$tieneOauth = isset($GLOBALS['AUD_SES_OAUTH_SCHEMA']) ? (bool)$GLOBALS['AUD_SES_OAUTH_SCHEMA'] : true;
			$colsOauth = $tieneOauth ? ", `Ses_Dev_Cod`, `Ses_Mac`, `Ses_OAuth_Tok`" : "";
			$valsOauth = $tieneOauth ? ", '{$sesDev}', '{$sesMac}', '{$sesOau}'" : "";

			// Ses_Fingerprint: respaldo de auditoria (huella del navegador) solo
			// cuando la MAC real no fue detectable. Flag independiente del anterior
			// para no depender de que ambas columnas se hayan podido crear a la vez.
			$tieneFp = isset($GLOBALS['AUD_SES_FP_SCHEMA']) ? (bool)$GLOBALS['AUD_SES_FP_SCHEMA'] : true;
			$colFp = $tieneFp ? ", `Ses_Fingerprint`" : "";
			$valFp = $tieneFp ? ", '{$sesFp}'" : "";

			return "INSERT INTO `auditoria`.`sesion` 
				(`Ses_Cod`, `Usu_Cod`, `Ses_Int`, `Emp_Cod`, `Suc_Cod`, `Ses_Ip`, `Ses_Ubi`, `Ses_Nav`, `Ses_Ult_Act`, `Ses_Min_Uso`, `Ses_Est`, `Ses_Token`{$colsOauth}{$colFp}) 
				VALUES 
				({$sesCod}, {$usuCod}, '{$sesInt}', {$empCod}, {$sucCod}, '{$sesIp}', '{$sesUbi}', '{$sesNav}', '{$sesInt}', 0, 'A', '{$sesTok}'{$valsOauth}{$valFp})";

		/**
		 * Case 4: Heartbeat ping - Actualiza ultima actividad y minutos de uso
		 *
		 * Ses_Ult_Act usa NOW() (reloj del servidor MySQL) porque el monitor en
		 * vivo compara siempre la ultima actividad contra NOW() de la base; usar
		 * date() de PHP era fragil si PHP y MySQL tienen distinto reloj/zona.
		 */
		case 4:
			$sesCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$usuCod = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$ahora  = date('Y-m-d H:i:s');
			// Solo sesiones ACTIVAS: nunca reactivar cierres por inactividad (I),
			// forzados (F) ni cerrados (C).
			return "UPDATE `auditoria`.`sesion` 
				SET `Ses_Ult_Act` = NOW(), 
				    `Ses_Min_Uso` = GREATEST(1, TIMESTAMPDIFF(MINUTE, `Ses_Int`, '{$ahora}'))
				WHERE `Ses_Cod` = {$sesCod} AND `Usu_Cod` = {$usuCod} AND `Ses_Est` = 'A'";

		/**
		 * Case 5: Verificar si la sesion fue forzada a cerrar o sigue valida
		 */
		case 5:
			$sesCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$usuCod = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			return "SELECT `Ses_Cod`, `Usu_Cod`, `Ses_Est`, `Ses_Ult_Act`, `Ses_Min_Uso` 
				FROM `auditoria`.`sesion` 
				WHERE `Ses_Cod` = {$sesCod} AND `Usu_Cod` = {$usuCod} LIMIT 1";

		/**
		 * Case 6: Cierre voluntario de sesion (Logout formal)
		 */
		case 6:
			$sesCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$usuCod = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$ahora  = date('Y-m-d H:i:s');
			return "UPDATE `auditoria`.`sesion` 
				SET `Ses_Out` = '{$ahora}',
				    `Ses_Ult_Act` = '{$ahora}',
				    `Ses_Min_Uso` = GREATEST(1, TIMESTAMPDIFF(MINUTE, `Ses_Int`, '{$ahora}')),
				    `Ses_Est` = 'C'
				WHERE `Ses_Cod` = {$sesCod} AND `Usu_Cod` = {$usuCod} AND `Ses_Est` = 'A'";

		/**
		 * Case 7: Cierre de sesion por inactividad (Timeout)
		 * Solo marca sesiones activas (A) como I para que Estadisticas las cuente.
		 */
		case 7:
			$sesCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$usuCod = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$ahora  = date('Y-m-d H:i:s');
			return "UPDATE `auditoria`.`sesion` 
				SET `Ses_Out` = '{$ahora}',
				    `Ses_Ult_Act` = '{$ahora}',
				    `Ses_Min_Uso` = GREATEST(0, TIMESTAMPDIFF(MINUTE, `Ses_Int`, '{$ahora}')),
				    `Ses_Est` = 'I'
				WHERE `Ses_Cod` = {$sesCod} AND `Usu_Cod` = {$usuCod} AND `Ses_Est` = 'A'";

		/**
		 * Case 8: Cierre forzado de sesion por el Administrador de Sistemas
		 */
		case 8:
			$sesCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$ahora  = date('Y-m-d H:i:s');
			return "UPDATE `auditoria`.`sesion` 
				SET `Ses_Out` = '{$ahora}',
				    `Ses_Ult_Act` = '{$ahora}',
				    `Ses_Min_Uso` = GREATEST(0, TIMESTAMPDIFF(MINUTE, `Ses_Int`, '{$ahora}')),
				    `Ses_Est` = 'F'
				WHERE `Ses_Cod` = {$sesCod}";

		/**
		 * Case 16: Ultima sesion activa (A) de un usuario (fallback inactividad).
		 * (0 usuCod, 1 sesCodPreferido opcional)
		 */
		case 16:
			$usuCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$sesPref = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			if ($sesPref > 0 && $usuCod > 0) {
				return "SELECT `Ses_Cod`, `Usu_Cod`, `Ses_Est` FROM `auditoria`.`sesion`
					WHERE `Usu_Cod` = {$usuCod} AND `Ses_Est` = 'A'
					ORDER BY (`Ses_Cod` = {$sesPref}) DESC, `Ses_Cod` DESC
					LIMIT 1";
			}
			return "SELECT `Ses_Cod`, `Usu_Cod`, `Ses_Est` FROM `auditoria`.`sesion`
				WHERE `Usu_Cod` = {$usuCod} AND `Ses_Est` = 'A'
				ORDER BY `Ses_Cod` DESC
				LIMIT 1";

		/**
		 * Case 17: Cerrar por inactividad TODAS las sesiones activas del usuario.
		 * (0 usuCod) — respaldo si no hay Ses_Cod en la sesion PHP.
		 */
		case 17:
			$usuCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$ahora  = date('Y-m-d H:i:s');
			return "UPDATE `auditoria`.`sesion`
				SET `Ses_Out` = '{$ahora}',
				    `Ses_Ult_Act` = '{$ahora}',
				    `Ses_Min_Uso` = GREATEST(0, TIMESTAMPDIFF(MINUTE, `Ses_Int`, '{$ahora}')),
				    `Ses_Est` = 'I'
				WHERE `Usu_Cod` = {$usuCod} AND `Ses_Est` = 'A'";

		/**
		 * Case 9: Listar actividad de sesiones con detalles de usuario, perfiles, ip y estado
		 */
		case 9:
			$empCod  = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$estado  = isset($Par_Sql[1]) ? addslashes($Par_Sql[1]) : '';
			$rolCod  = isset($Par_Sql[2]) ? (int)$Par_Sql[2] : 0;
			$limite  = isset($Par_Sql[3]) ? (int)$Par_Sql[3] : 100;
			if ($limite <= 0 || $limite > 500) $limite = 100;
			$desde   = isset($Par_Sql[4]) ? addslashes(trim($Par_Sql[4])) : '';
			$hasta   = isset($Par_Sql[5]) ? addslashes(trim($Par_Sql[5])) : '';

			// Columnas OAuth solo si existen en el esquema (evita ERROR 1054 en
			// produccion cuando el usuario de la BD no puede ejecutar el ALTER).
			$tieneOauth = isset($GLOBALS['AUD_SES_OAUTH_SCHEMA']) ? (bool)$GLOBALS['AUD_SES_OAUTH_SCHEMA'] : true;
			$camposOauth = $tieneOauth
				? "				IFNULL(s.`Ses_Dev_Cod`, '') AS `Ses_Dev_Cod`,\n				IFNULL(s.`Ses_Mac`, '') AS `Ses_Mac`,\n				IFNULL(s.`Ses_OAuth_Tok`, '') AS `Ses_OAuth_Tok`,\n"
				: "";
			// Ses_Fingerprint: respaldo de auditoria (huella del navegador) cuando
			// la MAC no fue detectable (acceso remoto/VPN/Internet fuera de la LAN).
			$tieneFp = isset($GLOBALS['AUD_SES_FP_SCHEMA']) ? (bool)$GLOBALS['AUD_SES_FP_SCHEMA'] : true;
			$camposOauth .= $tieneFp
				? "				IFNULL(s.`Ses_Fingerprint`, '') AS `Ses_Fingerprint`,\n"
				: "";

			$where = "WHERE 1=1";
			if ($empCod > 0) {
				$where .= " AND (sx.`Emp_Cod` = {$empCod} OR sx.`Emp_Cod` IS NULL OR sx.`Emp_Cod` = 0)";
			}
			if ($desde !== '' && $hasta !== '') {
				$where .= " AND DATE(sx.`Ses_Int`) BETWEEN '{$desde}' AND '{$hasta}'";
			} elseif ($desde !== '') {
				$where .= " AND DATE(sx.`Ses_Int`) >= '{$desde}'";
			} elseif ($hasta !== '') {
				$where .= " AND DATE(sx.`Ses_Int`) <= '{$hasta}'";
			}
			if ($estado === 'en_linea') {
				$where .= " AND sx.`Ses_Est` = 'A' AND TIMESTAMPDIFF(MINUTE, IFNULL(sx.`Ses_Ult_Act`, sx.`Ses_Int`), NOW()) < 5";
			} elseif ($estado === 'ausente') {
				$where .= " AND sx.`Ses_Est` = 'A' AND TIMESTAMPDIFF(MINUTE, IFNULL(sx.`Ses_Ult_Act`, sx.`Ses_Int`), NOW()) BETWEEN 5 AND 14";
			} elseif ($estado === 'inactiva') {
				$where .= " AND (sx.`Ses_Est` = 'I' OR (sx.`Ses_Est` = 'A' AND TIMESTAMPDIFF(MINUTE, IFNULL(sx.`Ses_Ult_Act`, sx.`Ses_Int`), NOW()) >= 15))";
			} elseif ($estado === 'cerrada') {
				$where .= " AND sx.`Ses_Est` = 'C'";
			} elseif ($estado === 'forzada') {
				$where .= " AND sx.`Ses_Est` = 'F'";
			}

if ($rolCod > 0) {
			$where .= " AND sx.`Usu_Cod` IN (SELECT DISTINCT up.`Usu_Cod` FROM {$masterDb}.`usuarperfi` up WHERE up.`Per_Cod` = {$rolCod})";
		}

		return "SELECT 
				s.`Ses_Cod`,
				s.`Usu_Cod`,
				s.`Ses_Int`,
				s.`Ses_Out`,
				IFNULL(s.`Ses_Ult_Act`, s.`Ses_Int`) AS `Ses_Ult_Act`,
				IFNULL(s.`Ses_Ip`, '') AS `Ses_Ip`,
				IFNULL(s.`Ses_Ubi`, 'No determinada') AS `Ses_Ubi`,
				IFNULL(s.`Ses_Nav`, 'Desconocido') AS `Ses_Nav`,
				{$camposOauth}				IFNULL(s.`Ses_Est`, 'A') AS `Ses_Est`,
				IFNULL(s.`Ses_Min_Uso`, TIMESTAMPDIFF(MINUTE, s.`Ses_Int`, IFNULL(s.`Ses_Out`, NOW()))) AS `Ses_Min_Uso`,
				s.`Emp_Cod`,
				s.`Suc_Cod`,
				TIMESTAMPDIFF(MINUTE, IFNULL(s.`Ses_Ult_Act`, s.`Ses_Int`), NOW()) AS `Minutos_Desde_Actividad`,
				TIMESTAMPDIFF(MINUTE, s.`Ses_Int`, IFNULL(s.`Ses_Out`, NOW())) AS `Minutos_Total_Calculado`,
				IFNULL(u.`Usu_Ced`, CONCAT('Usuario #', s.`Usu_Cod`)) AS `Usu_Nom`,
				u.`Usu_Ced`,
				p.`Prs_Nom`,
				p.`Prs_Ape`,
				IFNULL(e.`Emp_Nom`, '') AS `Emp_Nom`,
				IFNULL(suc.`Suc_Des`, '') AS `Suc_Nom`,
				(
					SELECT GROUP_CONCAT(DISTINCT perf.`Per_Des` SEPARATOR ', ')
					FROM {$masterDb}.`usuarperfi` up2
					INNER JOIN {$masterDb}.`perfiles` perf ON up2.`Per_Cod` = perf.`Per_Cod`
					WHERE up2.`Usu_Cod` = s.`Usu_Cod`
				) AS `Perfiles_Desc`
			FROM `auditoria`.`sesion` s
			INNER JOIN (
				/* Deduplica por usuario: conserva unicamente su sesion mas reciente
				   para que no se repita el mismo usuario (RUC/cedula) varias veces */
				SELECT MAX(`Ses_Cod`) AS `Ses_Cod`
				FROM `auditoria`.`sesion` sx
				{$where}
				GROUP BY IFNULL(sx.`Usu_Cod`, 0)
			) d ON s.`Ses_Cod` = d.`Ses_Cod`
			LEFT JOIN {$masterDb}.`usuarios` u ON s.`Usu_Cod` = u.`Usu_Cod`
			LEFT JOIN {$masterDb}.`persona` p ON u.`Prs_Cod` = p.`Prs_Cod`
			LEFT JOIN {$masterDb}.`empresas` e ON s.`Emp_Cod` = e.`Emp_Cod`
			LEFT JOIN {$masterDb}.`sucursal` suc ON s.`Suc_Cod` = suc.`Suc_Cod`
			ORDER BY s.`Ses_Cod` DESC
			LIMIT {$limite}";

		/**
		 * Case 10: Metricas / KPIs de Sesiones y Actividad
		 */
		case 10:
			$empCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$filtroEmp = ($empCod > 0) ? "AND (`Emp_Cod` = {$empCod} OR `Emp_Cod` IS NULL OR `Emp_Cod` = 0)" : "";

			return "SELECT 
					COUNT(CASE WHEN `Ses_Est` = 'A' AND TIMESTAMPDIFF(MINUTE, IFNULL(`Ses_Ult_Act`, `Ses_Int`), NOW()) < 5 THEN 1 END) AS `En_Linea`,
					COUNT(CASE WHEN `Ses_Est` = 'A' AND TIMESTAMPDIFF(MINUTE, IFNULL(`Ses_Ult_Act`, `Ses_Int`), NOW()) BETWEEN 5 AND 14 THEN 1 END) AS `Ausentes`,
					COUNT(CASE WHEN DATE(`Ses_Int`) = CURDATE() THEN 1 END) AS `Sesiones_Hoy`,
					COUNT(CASE WHEN `Ses_Est` = 'F' AND DATE(`Ses_Out`) = CURDATE() THEN 1 END) AS `Expulsados_Hoy`,
					IFNULL(ROUND(AVG(CASE WHEN DATE(`Ses_Int`) = CURDATE() THEN GREATEST(1, `Ses_Min_Uso`) END), 1), 0) AS `Promedio_Min_Uso`
				FROM `auditoria`.`sesion`
				WHERE `Ses_Int` >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
				{$filtroEmp}";

		/**
		 * Case 11: Top de usuarios con mayor tiempo de uso acumulado (ultimos 7 dias)
		 */
		case 11:
			$empCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$filtroEmp = ($empCod > 0) ? "AND (s.`Emp_Cod` = {$empCod} OR s.`Emp_Cod` IS NULL OR s.`Emp_Cod` = 0)" : "";

			return "SELECT 
					s.`Usu_Cod`,
					IFNULL(u.`Usu_Ced`, CONCAT('Usuario #', s.`Usu_Cod`)) AS `Usu_Nom`,
					p.`Prs_Nom`,
					p.`Prs_Ape`,
					COUNT(s.`Ses_Cod`) AS `Total_Sesiones`,\n					SUM(GREATEST(1, s.`Ses_Min_Uso`)) AS `Total_Minutos_Uso`,
					MAX(s.`Ses_Ult_Act`) AS `Ultima_Conexion`
				FROM `auditoria`.`sesion` s
				LEFT JOIN {$masterDb}.`usuarios` u ON s.`Usu_Cod` = u.`Usu_Cod`
				LEFT JOIN {$masterDb}.`persona` p ON u.`Prs_Cod` = p.`Prs_Cod`
				WHERE s.`Ses_Int` >= DATE_SUB(NOW(), INTERVAL 7 DAY)
				{$filtroEmp}
				GROUP BY s.`Usu_Cod`, u.`Usu_Ced`, p.`Prs_Nom`, p.`Prs_Ape`
				ORDER BY `Total_Minutos_Uso` DESC
				LIMIT 10";

		/**
		 * Case 12: Detalle de una sesion unica para validar cierres forzados
		 */
		case 12:
			$sesCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			return "SELECT `Ses_Cod`, `Usu_Cod`, `Emp_Cod`, `Suc_Cod`, `Ses_Est`, `Ses_Ult_Act`
				FROM `auditoria`.`sesion`
				WHERE `Ses_Cod` = {$sesCod}
				LIMIT 1";

		/**
		 * Case 13: Conteo de sesiones activas por usuario (detecta acceso multiple)
		 */
		case 13:
			$empCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$filtroEmp = ($empCod > 0) ? "AND (`Emp_Cod` = {$empCod} OR `Emp_Cod` IS NULL OR `Emp_Cod` = 0)" : "";
			return "SELECT `Usu_Cod`, COUNT(*) AS `Total_Activas`
				FROM `auditoria`.`sesion`
				WHERE `Ses_Est` = 'A'
				{$filtroEmp}
				GROUP BY `Usu_Cod`";

		/**
		 * Case 14: Estadistica de sesiones por usuario en el periodo [desde, hasta].
		 * Agrupa por usuario: iniciadas, cerradas, por inactividad, forzadas,
		 * promedio de minutos de uso, total de minutos y ultima actividad.
		 * (0 emp, 1 desde YYYY-MM-DD, 2 hasta YYYY-MM-DD, 3 limite, 4 usuCod opcional)
		 */
		case 14:
			$empCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$desde = isset($Par_Sql[1]) ? addslashes(trim((string)$Par_Sql[1])) : '';
			$hasta = isset($Par_Sql[2]) ? addslashes(trim((string)$Par_Sql[2])) : '';
			$lim = isset($Par_Sql[3]) ? max(1, (int)$Par_Sql[3]) : 200;
			$usuFiltro = isset($Par_Sql[4]) ? (int)$Par_Sql[4] : 0;
			if ($lim > 500) {
				$lim = 500;
			}
			$filtroEmp = ($empCod > 0) ? "AND (s.`Emp_Cod` = {$empCod} OR s.`Emp_Cod` IS NULL OR s.`Emp_Cod` = 0)" : "";
			$filtroUsu = ($usuFiltro > 0) ? "AND s.`Usu_Cod` = {$usuFiltro}" : "";
			$rango = '';
			if ($desde !== '' && $hasta !== '') {
				$rango = "AND DATE(s.`Ses_Int`) BETWEEN '{$desde}' AND '{$hasta}'";
			} elseif ($desde !== '') {
				$rango = "AND DATE(s.`Ses_Int`) >= '{$desde}'";
			} elseif ($hasta !== '') {
				$rango = "AND DATE(s.`Ses_Int`) <= '{$hasta}'";
			}
			return "SELECT
					s.`Usu_Cod`,
					IFNULL(u.`Usu_Ced`, CONCAT('Usuario #', s.`Usu_Cod`)) AS `Usu_Nom`,
					IFNULL(p.`Prs_Nom`, '') AS `Prs_Nom`,
					IFNULL(p.`Prs_Ape`, '') AS `Prs_Ape`,
					COUNT(*) AS `Iniciadas`,
					SUM(CASE WHEN s.`Ses_Est` = 'C' THEN 1 ELSE 0 END) AS `Cerradas`,
					SUM(CASE WHEN s.`Ses_Est` = 'I' THEN 1 ELSE 0 END) AS `Por_Inactividad`,
					SUM(CASE WHEN s.`Ses_Est` = 'F' THEN 1 ELSE 0 END) AS `Forzadas`,
					IFNULL(ROUND(AVG(GREATEST(1, s.`Ses_Min_Uso`)), 1), 0) AS `Promedio_Min`,
					IFNULL(SUM(GREATEST(1, s.`Ses_Min_Uso`)), 0) AS `Total_Min`,
					MAX(IFNULL(s.`Ses_Ult_Act`, s.`Ses_Int`)) AS `Ultima_Actividad`
				FROM `auditoria`.`sesion` s
				LEFT JOIN {$masterDb}.`usuarios` u ON s.`Usu_Cod` = u.`Usu_Cod`
				LEFT JOIN {$masterDb}.`persona` p ON u.`Prs_Cod` = p.`Prs_Cod`
				WHERE 1=1 {$filtroEmp} {$filtroUsu} {$rango}
				GROUP BY s.`Usu_Cod`, u.`Usu_Ced`, p.`Prs_Nom`, p.`Prs_Ape`
				ORDER BY `Iniciadas` DESC, `Total_Min` DESC
				LIMIT {$lim}";

		/**
		 * Case 15: Serie diaria de sesiones en el periodo (para tendencia).
		 * (0 emp, 1 desde, 2 hasta, 3 usuCod opcional)
		 */
		case 15:
			$empCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$desde = isset($Par_Sql[1]) ? addslashes(trim((string)$Par_Sql[1])) : '';
			$hasta = isset($Par_Sql[2]) ? addslashes(trim((string)$Par_Sql[2])) : '';
			$usuFiltro = isset($Par_Sql[3]) ? (int)$Par_Sql[3] : 0;
			$filtroEmp = ($empCod > 0) ? "AND (s.`Emp_Cod` = {$empCod} OR s.`Emp_Cod` IS NULL OR s.`Emp_Cod` = 0)" : "";
			$filtroUsu = ($usuFiltro > 0) ? "AND s.`Usu_Cod` = {$usuFiltro}" : "";
			$rango = '';
			if ($desde !== '' && $hasta !== '') {
				$rango = "AND DATE(s.`Ses_Int`) BETWEEN '{$desde}' AND '{$hasta}'";
			} elseif ($desde !== '') {
				$rango = "AND DATE(s.`Ses_Int`) >= '{$desde}'";
			} elseif ($hasta !== '') {
				$rango = "AND DATE(s.`Ses_Int`) <= '{$hasta}'";
			}
			return "SELECT
					DATE(s.`Ses_Int`) AS `Dia`,
					COUNT(*) AS `Iniciadas`,
					SUM(CASE WHEN s.`Ses_Est` = 'C' THEN 1 ELSE 0 END) AS `Cerradas`,
					SUM(CASE WHEN s.`Ses_Est` = 'I' THEN 1 ELSE 0 END) AS `Por_Inactividad`,
					SUM(CASE WHEN s.`Ses_Est` = 'F' THEN 1 ELSE 0 END) AS `Forzadas`,
					IFNULL(SUM(GREATEST(1, s.`Ses_Min_Uso`)), 0) AS `Total_Min`
				FROM `auditoria`.`sesion` s
				WHERE 1=1 {$filtroEmp} {$filtroUsu} {$rango}
				GROUP BY DATE(s.`Ses_Int`)
				ORDER BY `Dia` ASC
				LIMIT 120";

		default:
			return "";
	}
}
