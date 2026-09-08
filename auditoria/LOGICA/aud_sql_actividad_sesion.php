<?php
/**
 * Sentencias SQL para el monitor de actividad de usuarios, tracking de sesiones,
 * tiempos de uso, IP, ubicacion, navegadores y control de inactividad.
 *
 * @package auditoria.LOGICA
 */

function sentencias_actividad_sesion($tipo, $Par_Sql = array())
{
	$masterDb = function_exists('aud_master_db') ? aud_master_db() : 'servicios';

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

			return "INSERT INTO `auditoria`.`sesion` 
				(`Ses_Cod`, `Usu_Cod`, `Ses_Int`, `Emp_Cod`, `Suc_Cod`, `Ses_Ip`, `Ses_Ubi`, `Ses_Nav`, `Ses_Ult_Act`, `Ses_Min_Uso`, `Ses_Est`, `Ses_Token`) 
				VALUES 
				({$sesCod}, {$usuCod}, '{$sesInt}', {$empCod}, {$sucCod}, '{$sesIp}', '{$sesUbi}', '{$sesNav}', '{$sesInt}', 0, 'A', '{$sesTok}')";

		/**
		 * Case 4: Heartbeat ping - Actualiza ultima actividad y minutos de uso
		 */
		case 4:
			$sesCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$usuCod = isset($Par_Sql[1]) ? (int)$Par_Sql[1] : 0;
			$ahora  = date('Y-m-d H:i:s');
			return "UPDATE `auditoria`.`sesion` 
				SET `Ses_Ult_Act` = '{$ahora}', 
				    `Ses_Min_Uso` = GREATEST(1, TIMESTAMPDIFF(MINUTE, `Ses_Int`, '{$ahora}')),
				    `Ses_Est` = IF(`Ses_Est` = 'I', 'A', `Ses_Est`)
				WHERE `Ses_Cod` = {$sesCod} AND `Usu_Cod` = {$usuCod} AND `Ses_Est` IN ('A', 'I')";

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
				WHERE `Ses_Cod` = {$sesCod} AND `Usu_Cod` = {$usuCod}";

		/**
		 * Case 7: Cierre de sesion por inactividad (Timeout de 15 min)
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
				WHERE `Ses_Cod` = {$sesCod} AND `Usu_Cod` = {$usuCod}";

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

			$where = "WHERE 1=1";
			if ($empCod > 0) {
				$where .= " AND (s.`Emp_Cod` = {$empCod} OR s.`Emp_Cod` IS NULL OR s.`Emp_Cod` = 0)";
			}
			if ($desde !== '' && $hasta !== '') {
				$where .= " AND DATE(s.`Ses_Int`) BETWEEN '{$desde}' AND '{$hasta}'";
			} elseif ($desde !== '') {
				$where .= " AND DATE(s.`Ses_Int`) >= '{$desde}'";
			} elseif ($hasta !== '') {
				$where .= " AND DATE(s.`Ses_Int`) <= '{$hasta}'";
			}
			if ($estado === 'en_linea') {
				$where .= " AND s.`Ses_Est` = 'A' AND s.`Ses_Ult_Act` >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)";
			} elseif ($estado === 'ausente') {
				$where .= " AND s.`Ses_Est` = 'A' AND s.`Ses_Ult_Act` < DATE_SUB(NOW(), INTERVAL 3 MINUTE) AND s.`Ses_Ult_Act` >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
			} elseif ($estado === 'inactiva') {
				$where .= " AND (s.`Ses_Est` = 'I' OR (s.`Ses_Est` = 'A' AND s.`Ses_Ult_Act` < DATE_SUB(NOW(), INTERVAL 15 MINUTE)))";
			} elseif ($estado === 'cerrada') {
				$where .= " AND s.`Ses_Est` = 'C'";
			} elseif ($estado === 'forzada') {
				$where .= " AND s.`Ses_Est` = 'F'";
			}

			if ($rolCod > 0) {
				$where .= " AND u.`Usu_Cod` IN (SELECT DISTINCT up.`Usu_Cod` FROM `{$masterDb}`.`usuarperfi` up WHERE up.`Per_Cod` = {$rolCod})";
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
					IFNULL(s.`Ses_Est`, 'A') AS `Ses_Est`,
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
						FROM `{$masterDb}`.`usuarperfi` up2
						INNER JOIN `{$masterDb}`.`perfiles` perf ON up2.`Per_Cod` = perf.`Per_Cod`
						WHERE up2.`Usu_Cod` = s.`Usu_Cod`
					) AS `Perfiles_Desc`
				FROM `auditoria`.`sesion` s
				LEFT JOIN `{$masterDb}`.`usuarios` u ON s.`Usu_Cod` = u.`Usu_Cod`
				LEFT JOIN `{$masterDb}`.`persona` p ON u.`Prs_Cod` = p.`Prs_Cod`
				LEFT JOIN `{$masterDb}`.`empresas` e ON s.`Emp_Cod` = e.`Emp_Cod`
				LEFT JOIN `{$masterDb}`.`sucursal` suc ON s.`Suc_Cod` = suc.`Suc_Cod`
				{$where}
				ORDER BY s.`Ses_Cod` DESC
				LIMIT {$limite}";

		/**
		 * Case 10: Metricas / KPIs de Sesiones y Actividad
		 */
		case 10:
			$empCod = isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0;
			$filtroEmp = ($empCod > 0) ? "AND (`Emp_Cod` = {$empCod} OR `Emp_Cod` IS NULL OR `Emp_Cod` = 0)" : "";

			return "SELECT 
					COUNT(CASE WHEN `Ses_Est` = 'A' AND `Ses_Ult_Act` >= DATE_SUB(NOW(), INTERVAL 3 MINUTE) THEN 1 END) AS `En_Linea`,
					COUNT(CASE WHEN `Ses_Est` = 'A' AND `Ses_Ult_Act` < DATE_SUB(NOW(), INTERVAL 3 MINUTE) AND `Ses_Ult_Act` >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 END) AS `Ausentes`,
					COUNT(CASE WHEN DATE(`Ses_Int`) = CURDATE() THEN 1 END) AS `Sesiones_Hoy`,
					COUNT(CASE WHEN `Ses_Est` = 'F' AND DATE(`Ses_Out`) = CURDATE() THEN 1 END) AS `Expulsados_Hoy`,
					IFNULL(ROUND(AVG(CASE WHEN DATE(`Ses_Int`) = CURDATE() THEN GREATEST(1, `Ses_Min_Uso`) END), 1), 0) AS `Promedio_Min_Uso`
				FROM `auditoria`.`sesion`
				WHERE DATE(`Ses_Int`) >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
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
				LEFT JOIN `{$masterDb}`.`usuarios` u ON s.`Usu_Cod` = u.`Usu_Cod`
				LEFT JOIN `{$masterDb}`.`persona` p ON u.`Prs_Cod` = p.`Prs_Cod`
				WHERE s.`Ses_Int` >= DATE_SUB(NOW(), INTERVAL 7 DAY)
				{$filtroEmp}
				GROUP BY s.`Usu_Cod`, u.`Usu_Ced`, p.`Prs_Nom`, p.`Prs_Ape`
				ORDER BY `Total_Minutos_Uso` DESC
				LIMIT 10";

		default:
			return "";
	}
}
