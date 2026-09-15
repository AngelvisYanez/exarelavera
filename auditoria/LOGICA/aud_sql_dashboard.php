<?php
/**
 * Sentencias SQL para el Dashboard Estadistico Comparativo de Auditoria.
 * Permite comparar dos periodos (A vs B) en volumen de eventos,
 * modulos con mas cambios, usuarios mas activos, horarios y sesiones.
 *
 * @package auditoria.LOGICA
 */

if (!function_exists('sentencias_dashboard')) {
	/**
	 * Retorna la consulta SQL segun la transaccion solicitada para el Dashboard de Auditoria.
	 *
	 * @param int $transaccion ID de transaccion SQL
	 * @param array $opciones Parametros para la sentencia
	 * @return string Consulta SQL
	 */
	function sentencias_dashboard($transaccion, $opciones = array())
	{
		$masterDb = '`exa`';
		if (function_exists('aud_sql_db_dis')) {
			$db = preg_replace('/[^a-zA-Z0-9_]/', '', (string)aud_sql_db_dis());
			if ($db !== '' && $db !== 'exa_master') {
				$masterDb = "`{$db}`";
			}
		} elseif (function_exists('aud_master_db')) {
			$dbMaster = preg_replace('/[^a-zA-Z0-9_]/', '', (string)aud_master_db());
			if ($dbMaster !== '' && $dbMaster !== 'exa_master') {
				$masterDb = "`{$dbMaster}`";
			}
		} elseif (isset($_SESSION['Ses_Dat_Dis']) && trim($_SESSION['Ses_Dat_Dis']) !== '') {
			$dbClean = preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']);
			if ($dbClean !== '' && $dbClean !== 'exa_master') {
				$masterDb = "`{$dbClean}`";
			}
		} elseif (!empty($GLOBALS['Ses_Dat_Dis'])) {
			$dbClean = preg_replace('/[^a-zA-Z0-9_]/', '', $GLOBALS['Ses_Dat_Dis']);
			if ($dbClean !== '' && $dbClean !== 'exa_master') {
				$masterDb = "`{$dbClean}`";
			}
		}

		$emp = isset($opciones[0]) ? (int)$opciones[0] : 0;
		$ini = isset($opciones[1]) ? preg_replace('/[^0-9\- :]/', '', $opciones[1]) : '1970-01-01 00:00:00';
		$fin = isset($opciones[2]) ? preg_replace('/[^0-9\- :]/', '', $opciones[2]) : '2099-12-31 23:59:59';
		$lim = isset($opciones[3]) ? (int)$opciones[3] : 8;
		if ($lim <= 0) $lim = 8;

		switch ((int)$transaccion) {
			case 1:
				// Resumen global de eventos en rango para la empresa
				return "SELECT 
							COUNT(*) AS Total_Movimientos,
							SUM(CASE WHEN l.Eve_Cod = 'I' THEN 1 ELSE 0 END) AS Total_Insert,
							SUM(CASE WHEN l.Eve_Cod = 'U' THEN 1 ELSE 0 END) AS Total_Update,
							SUM(CASE WHEN l.Eve_Cod = 'D' THEN 1 ELSE 0 END) AS Total_Delete
						FROM `auditoria`.`logs` l
						WHERE l.Emp_Cod = {$emp}
						  AND l.Log_Fec >= '{$ini}' AND l.Log_Fec <= '{$fin}'";

			case 2:
				// Actividad agrupada por modulo
				$modDesExpr = function_exists('aud_sql_expr_modulo_des') ? aud_sql_expr_modulo_des() : "COALESCE(`organizado`.`Org_Des`, 'Sin Módulo')";
				$treeJoins = function_exists('aud_sql_org_tree_joins') ? aud_sql_org_tree_joins('p') : "LEFT JOIN {$masterDb}.`organizado` ON p.`Org_Cod` = `organizado`.`Org_Cod`";
				return "SELECT 
							COALESCE({$modDesExpr}, 'Sin Módulo') AS Modulo,
							COUNT(*) AS Total,
							COUNT(*) AS Total_Movimientos,
							COUNT(DISTINCT l.Usu_Cod) AS Total_Usuarios
						FROM `auditoria`.`logs` l
						LEFT JOIN {$masterDb}.`procesos` p ON l.Pcs_Cod = p.Pcs_Cod
						{$treeJoins}
						WHERE l.Emp_Cod = {$emp}
						  AND l.Log_Fec >= '{$ini}' AND l.Log_Fec <= '{$fin}'
						GROUP BY Modulo
						ORDER BY Total DESC
						LIMIT {$lim}";

			case 3:
				// Distribucion por franja horaria (0 a 23 horas)
				return "SELECT 
							HOUR(l.Log_Fec) AS Hora,
							COUNT(*) AS Total,
							COUNT(*) AS Total_Movimientos
						FROM `auditoria`.`logs` l
						WHERE l.Emp_Cod = {$emp}
						  AND l.Log_Fec >= '{$ini}' AND l.Log_Fec <= '{$fin}'
						GROUP BY HOUR(l.Log_Fec)
						ORDER BY Hora ASC";

			case 4:
				// Metricas de sesiones y tiempo de uso en el periodo
				return "SELECT 
							COUNT(*) AS Total_Sesiones,
							COUNT(DISTINCT s.Usu_Cod) AS Usuarios_Unicos,
							COUNT(DISTINCT s.Usu_Cod) AS Usuarios_Distintos,
							COALESCE(AVG(s.Ses_Min_Uso), 0) AS Promedio_Minutos,
							COALESCE(SUM(s.Ses_Min_Uso), 0) AS Total_Minutos,
							SUM(CASE WHEN s.Ses_Est = 'I' THEN 1 ELSE 0 END) AS Cierres_Inactividad,
							SUM(CASE WHEN s.Ses_Est = 'F' THEN 1 ELSE 0 END) AS Cierres_Forzados
						FROM `auditoria`.`sesion` s
						WHERE s.Emp_Cod = {$emp}
						  AND s.Ses_Int >= '{$ini}' AND s.Ses_Int <= '{$fin}'";

			case 5:
				// Distribucion temporal por dia para curva de comparacion diaria
				return "SELECT 
							DATE(l.Log_Fec) AS Dia,
							DATE(l.Log_Fec) AS Fecha,
							COUNT(*) AS Total,
							COUNT(*) AS Total_Movimientos,
							COUNT(DISTINCT l.Usu_Cod) AS Usuarios_Activos
						FROM `auditoria`.`logs` l
						WHERE l.Emp_Cod = {$emp}
						  AND l.Log_Fec >= '{$ini}' AND l.Log_Fec <= '{$fin}'
						GROUP BY DATE(l.Log_Fec)
						ORDER BY Dia ASC";

			case 6:
				// Top usuarios con mayor numero de operaciones en el periodo
				return "SELECT 
							l.Usu_Cod,
							l.Usu_Cod AS Usuario_Id,
							COALESCE(p.Prs_Nom, CONCAT('Usuario #', l.Usu_Cod)) AS Prs_Nom,
							COALESCE(p.Prs_Nom, CONCAT('Usuario #', l.Usu_Cod)) AS UsuarioNombre,
							COALESCE(p.Prs_Ape, '') AS Prs_Ape,
							COALESCE(p.Prs_Ape, '') AS UsuarioApellido,
							COALESCE(p.Prs_Ced, CONCAT('ID ', l.Usu_Cod)) AS Login,
							COUNT(*) AS Total_Operaciones,
							SUM(CASE WHEN l.Eve_Cod = 'D' THEN 1 ELSE 0 END) AS Total_Eliminaciones,
							SUM(CASE WHEN l.Eve_Cod = 'U' THEN 1 ELSE 0 END) AS Total_Modificaciones,
							SUM(CASE WHEN l.Eve_Cod = 'I' THEN 1 ELSE 0 END) AS Total_Inserciones
						FROM `auditoria`.`logs` l
						LEFT JOIN {$masterDb}.`usuarios` u ON l.Usu_Cod = u.Usu_Cod
						LEFT JOIN {$masterDb}.`persona` p ON u.Prs_Cod = p.Prs_Cod
						WHERE l.Emp_Cod = {$emp}
						  AND l.Log_Fec >= '{$ini}' AND l.Log_Fec <= '{$fin}'
						GROUP BY l.Usu_Cod, Prs_Nom, Prs_Ape, Login
						ORDER BY Total_Operaciones DESC
						LIMIT {$lim}";

			case 7:
				// Top plantas de beneficio con actividad en el periodo.
				// El valor de Pla_Cod se extrae del par Log_Cam/Log_Val por posicion.
				$posPla = "LENGTH(SUBSTRING_INDEX(l.`Log_Cam`, 'Pla_Cod', 1)) - LENGTH(REPLACE(SUBSTRING_INDEX(l.`Log_Cam`, 'Pla_Cod', 1), ',', '')) + 1";
				$valPla = "CAST(REPLACE(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(l.`Log_Val`, ',', {$posPla}), ',', -1)), '~', '') AS UNSIGNED)";
				return "SELECT
							{$valPla} AS Pla_Cod,
							COALESCE(mp.`Pla_Nom`, CONCAT('Planta #', {$valPla})) AS Planta,
							COUNT(*) AS Total,
							COUNT(DISTINCT l.Usu_Cod) AS Usuarios,
							SUM(CASE WHEN l.Eve_Cod = 'I' THEN 1 ELSE 0 END) AS Inserts,
							SUM(CASE WHEN l.Eve_Cod = 'U' THEN 1 ELSE 0 END) AS Updates,
							SUM(CASE WHEN l.Eve_Cod = 'D' THEN 1 ELSE 0 END) AS Deletes
						FROM `auditoria`.`logs` l
						LEFT JOIN {$masterDb}.`manifiesto_plantas` mp ON mp.`Pla_Cod` = {$valPla}
						WHERE l.Emp_Cod = {$emp}
						  AND l.Log_Fec >= '{$ini}' AND l.Log_Fec <= '{$fin}'
						  AND l.`Log_Cam` LIKE '%Pla_Cod%'
						  AND {$valPla} > 0
						GROUP BY Pla_Cod, Planta
						ORDER BY Total DESC
						LIMIT {$lim}";

			case 8:
				// Comportamiento diario por usuario (para curva apilada de ultimos dias)
				return "SELECT
							l.Usu_Cod,
							COALESCE(p.`Prs_Nom`, CONCAT('Usuario #', l.Usu_Cod)) AS UsuarioNombre,
							COALESCE(p.`Prs_Ape`, '') AS UsuarioApellido,
							DATE(l.Log_Fec) AS Dia,
							COUNT(*) AS Total
						FROM `auditoria`.`logs` l
						LEFT JOIN {$masterDb}.`usuarios` u ON l.Usu_Cod = u.`Usu_Cod`
						LEFT JOIN {$masterDb}.`persona` p ON u.`Prs_Cod` = p.`Prs_Cod`
						WHERE l.Emp_Cod = {$emp}
						  AND l.Log_Fec >= '{$ini}' AND l.Log_Fec <= '{$fin}'
						GROUP BY l.Usu_Cod, UsuarioNombre, UsuarioApellido, Dia
						ORDER BY Dia ASC, Total DESC";

			case 9:
				// Datos de la empresa
				return "SELECT Emp_Cod, Emp_Nom, Emp_Ruc 
						FROM {$masterDb}.`empresas` 
						WHERE Emp_Cod = {$emp} 
						LIMIT 1";

			default:
				return "";
		}
	}
}

if (!function_exists('sentencias_dashboard_comparativo')) {
	function sentencias_dashboard_comparativo($transaccion, $opciones = array()) {
		return sentencias_dashboard($transaccion, $opciones);
	}
}
