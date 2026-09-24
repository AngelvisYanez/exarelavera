<?php
/**
 * Notificaciones por correo de Auditoria.
 *
 * Reglas configurables por empresa (modulo/directorio/proceso + eventos U/D/I)
 * que definen a que correos (libres o de usuarios del sistema con correo
 * registrado) se notifica cada actividad detectada por el monitor.
 *
 * Disenado para usarse tanto desde la pantalla de Configuracion de monitoreo
 * (CRUD de reglas) como desde AuditQueue (envio en tiempo real tras persistir
 * un log de tipo Actualizar/Eliminar). Trabaja con mysqli "crudo" para no
 * acoplarse a las clases Class_Log_Datos_* de cada pantalla.
 *
 * @package auditoria.LOGICA
 */

if (!function_exists('aud_notif_con')) {
	/**
	 * Normaliza el parametro de conexion: acepta mysqli o un objeto con ->conexion.
	 */
	function aud_notif_con($obBD_conexion)
	{
		if (is_object($obBD_conexion) && isset($obBD_conexion->conexion)) {
			return $obBD_conexion->conexion;
		}
		return $obBD_conexion;
	}
}

if (!function_exists('aud_notif_ensure_schema')) {
	function aud_notif_ensure_schema($obBD_conexion)
	{
		$con = aud_notif_con($obBD_conexion);
		if (!$con) {
			return;
		}
		@mysqli_query($con, "CREATE TABLE IF NOT EXISTS `auditoria`.`cfg_notificaciones` (
			`Not_Cod` INT(11) NOT NULL AUTO_INCREMENT,
			`Emp_Cod` INT(11) NOT NULL,
			`Org_Cod` INT(11) NOT NULL,
			`Pcs_Cod` INT(11) NOT NULL DEFAULT 0,
			`Not_Eventos` VARCHAR(10) NOT NULL DEFAULT 'U,D',
			`Not_Correos` TEXT NULL,
			`Not_Usuarios` VARCHAR(255) NULL,
			`Not_Est` CHAR(1) NOT NULL DEFAULT 'A',
			`Not_Fec` DATETIME DEFAULT NULL,
			`Usu_Cod` INT(11) DEFAULT NULL,
			PRIMARY KEY (`Not_Cod`),
			KEY `idx_emp_est` (`Emp_Cod`,`Not_Est`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		@mysqli_query($con, "CREATE TABLE IF NOT EXISTS `auditoria`.`cfg_correo_usuario` (
			`Usu_Cod` INT(11) NOT NULL,
			`Emp_Cod` INT(11) NOT NULL,
			`Correo` VARCHAR(150) NOT NULL,
			`Fec_Reg` DATETIME DEFAULT NULL,
			PRIMARY KEY (`Usu_Cod`,`Emp_Cod`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	}
}

if (!function_exists('aud_notif_validar_correos')) {
	/**
	 * Valida una lista de correos separados por coma.
	 * @return array array('validos'=>array, 'invalidos'=>array)
	 */
	function aud_notif_validar_correos($csv)
	{
		$validos = array();
		$invalidos = array();
		foreach (explode(',', (string)$csv) as $c) {
			$c = trim($c);
			if ($c === '') {
				continue;
			}
			if (filter_var($c, FILTER_VALIDATE_EMAIL)) {
				$low = strtolower($c);
				if (!in_array($low, $validos, true)) {
					$validos[] = $low;
				}
			} else {
				$invalidos[] = $c;
			}
		}
		return array('validos' => $validos, 'invalidos' => $invalidos);
	}
}

if (!function_exists('aud_notif_validar_eventos')) {
	/**
	 * Normaliza la lista de eventos a notificar (solo I, U, D).
	 */
	function aud_notif_validar_eventos($csv)
	{
		$out = array();
		foreach (explode(',', strtoupper((string)$csv)) as $e) {
			$e = trim($e);
			if (($e === 'I' || $e === 'U' || $e === 'D') && !in_array($e, $out, true)) {
				$out[] = $e;
			}
		}
		return $out;
	}
}

if (!function_exists('aud_notif_listar_reglas')) {
	function aud_notif_listar_reglas($obBD_conexion, $emp)
	{
		$con = aud_notif_con($obBD_conexion);
		$emp = (int)$emp;
		$out = array();
		if (!$con || $emp <= 0) {
			return $out;
		}
		$r = @mysqli_query($con, "SELECT `Not_Cod`,`Emp_Cod`,`Org_Cod`,`Pcs_Cod`,`Not_Eventos`,`Not_Correos`,`Not_Usuarios`,`Not_Fec`
			FROM `auditoria`.`cfg_notificaciones`
			WHERE `Emp_Cod`={$emp} AND `Not_Est`='A'
			ORDER BY `Not_Cod` DESC");
		if ($r) {
			while ($row = mysqli_fetch_assoc($r)) {
				$out[] = $row;
			}
			mysqli_free_result($r);
		}
		return $out;
	}
}

if (!function_exists('aud_notif_guardar_regla')) {
	/**
	 * Crea o actualiza una regla de notificacion.
	 * $data: org, pcs, eventos (csv I,U,D), correos (csv), usuarios (csv Usu_Cod), notCod (0 = nueva)
	 */
	function aud_notif_guardar_regla($obBD_conexion, $emp, $usuSesion, $data)
	{
		$con = aud_notif_con($obBD_conexion);
		$emp = (int)$emp;
		if (!$con || $emp <= 0) {
			return array('success' => false, 'message' => 'No hay conexion o empresa activa.');
		}
		aud_notif_ensure_schema($obBD_conexion);

		$org = isset($data['org']) ? (int)$data['org'] : 0;
		$pcs = isset($data['pcs']) ? (int)$data['pcs'] : 0;
		$notCod = isset($data['notCod']) ? (int)$data['notCod'] : 0;
		$eventos = aud_notif_validar_eventos(isset($data['eventos']) ? $data['eventos'] : '');
		$correosChk = aud_notif_validar_correos(isset($data['correos']) ? $data['correos'] : '');
		$usuarios = array();
		foreach (explode(',', isset($data['usuarios']) ? (string)$data['usuarios'] : '') as $u) {
			$u = (int)$u;
			if ($u > 0 && !in_array($u, $usuarios, true)) {
				$usuarios[] = $u;
			}
		}

		if ($org <= 0) {
			return array('success' => false, 'message' => 'Debe seleccionar un modulo, directorio o proceso.');
		}
		if (count($eventos) === 0) {
			return array('success' => false, 'message' => 'Debe marcar al menos un evento (Insertar, Actualizar o Eliminar).');
		}
		if (count($correosChk['validos']) === 0 && count($usuarios) === 0) {
			return array('success' => false, 'message' => 'Debe indicar al menos un correo destino o un usuario con correo registrado.');
		}

		$eveEsc = mysqli_real_escape_string($con, implode(',', $eventos));
		$correoEsc = mysqli_real_escape_string($con, implode(',', $correosChk['validos']));
		$usuEsc = mysqli_real_escape_string($con, implode(',', $usuarios));
		$fec = date('Y-m-d H:i:s');
		$usuSesion = (int)$usuSesion;

		if ($notCod > 0) {
			$sql = "UPDATE `auditoria`.`cfg_notificaciones`
				SET `Org_Cod`={$org}, `Pcs_Cod`={$pcs}, `Not_Eventos`='{$eveEsc}',
					`Not_Correos`='{$correoEsc}', `Not_Usuarios`='{$usuEsc}', `Not_Est`='A',
					`Not_Fec`='{$fec}', `Usu_Cod`={$usuSesion}
				WHERE `Not_Cod`={$notCod} AND `Emp_Cod`={$emp}";
		} else {
			$sql = "INSERT INTO `auditoria`.`cfg_notificaciones`
				(`Emp_Cod`,`Org_Cod`,`Pcs_Cod`,`Not_Eventos`,`Not_Correos`,`Not_Usuarios`,`Not_Est`,`Not_Fec`,`Usu_Cod`)
				VALUES ({$emp},{$org},{$pcs},'{$eveEsc}','{$correoEsc}','{$usuEsc}','A','{$fec}',{$usuSesion})";
		}
		$ok = @mysqli_query($con, $sql);
		if (!$ok) {
			return array('success' => false, 'message' => 'No se pudo guardar la regla de notificacion.');
		}
		$msg = 'Regla de notificacion guardada.';
		if (count($correosChk['invalidos']) > 0) {
			$msg .= ' Se ignoraron correos invalidos: ' . implode(', ', $correosChk['invalidos']) . '.';
		}
		return array('success' => true, 'message' => $msg, 'notCod' => $notCod > 0 ? $notCod : (int)mysqli_insert_id($con));
	}
}

if (!function_exists('aud_notif_eliminar_regla')) {
	function aud_notif_eliminar_regla($obBD_conexion, $emp, $notCod)
	{
		$con = aud_notif_con($obBD_conexion);
		$emp = (int)$emp;
		$notCod = (int)$notCod;
		if (!$con || $emp <= 0 || $notCod <= 0) {
			return array('success' => false, 'message' => 'Datos incompletos.');
		}
		$ok = @mysqli_query($con, "UPDATE `auditoria`.`cfg_notificaciones` SET `Not_Est`='I'
			WHERE `Not_Cod`={$notCod} AND `Emp_Cod`={$emp}");
		return array('success' => (bool)$ok, 'message' => $ok ? 'Regla eliminada.' : 'No se pudo eliminar la regla.');
	}
}

if (!function_exists('aud_notif_listar_correos_usuario')) {
	/**
	 * Lista los usuarios de la empresa con su correo registrado (si existe).
	 * Reutiliza el listado de usuarios de configuracion de monitoreo (case 10)
	 * cuando esta disponible; si no, devuelve solo lo guardado en cfg_correo_usuario.
	 */
	function aud_notif_listar_correos_usuario($obBD_conexion, $emp)
	{
		$con = aud_notif_con($obBD_conexion);
		$emp = (int)$emp;
		$mapCorreo = array();
		if ($con && $emp > 0) {
			$r = @mysqli_query($con, "SELECT `Usu_Cod`,`Correo` FROM `auditoria`.`cfg_correo_usuario` WHERE `Emp_Cod`={$emp}");
			if ($r) {
				while ($row = mysqli_fetch_assoc($r)) {
					$mapCorreo[(int)$row['Usu_Cod']] = $row['Correo'];
				}
				mysqli_free_result($r);
			}
		}
		$usuarios = array();
		if (function_exists('sentencias_cfg_monitoreo') && is_object($obBD_conexion)) {
			$sql = sentencias_cfg_monitoreo(10, array($emp));
			if ($sql !== '') {
				$r2 = @mysqli_query($con, $sql);
				if ($r2) {
					while ($row2 = mysqli_fetch_assoc($r2)) {
						$usuarios[] = $row2;
					}
					mysqli_free_result($r2);
				}
			}
		}
		$out = array();
		foreach ($usuarios as $u) {
			$cod = (int)$u['Usu_Cod'];
			$out[] = array(
				'Usu_Cod' => $cod,
				'Usu_Cods' => isset($u['Usu_Cods']) ? $u['Usu_Cods'] : (string)$cod,
				'Usu_Nom' => isset($u['Usu_Nom']) ? $u['Usu_Nom'] : ('Usuario ' . $cod),
				'Correo' => isset($mapCorreo[$cod]) ? $mapCorreo[$cod] : ''
			);
		}
		// Usuarios con correo registrado que no aparecieron en el listado (por si cambio de estado)
		foreach ($mapCorreo as $cod => $correo) {
			$existe = false;
			foreach ($out as $o) {
				if ((int)$o['Usu_Cod'] === (int)$cod) {
					$existe = true;
					break;
				}
			}
			if (!$existe) {
				$out[] = array('Usu_Cod' => (int)$cod, 'Usu_Cods' => (string)$cod, 'Usu_Nom' => 'Usuario ' . $cod, 'Correo' => $correo);
			}
		}
		return $out;
	}
}

if (!function_exists('aud_notif_guardar_correo_usuario')) {
	function aud_notif_guardar_correo_usuario($obBD_conexion, $emp, $usuCod, $correo)
	{
		$con = aud_notif_con($obBD_conexion);
		$emp = (int)$emp;
		$usuCod = (int)$usuCod;
		$correo = trim((string)$correo);
		if (!$con || $emp <= 0 || $usuCod <= 0) {
			return array('success' => false, 'message' => 'Datos incompletos.');
		}
		if ($correo === '') {
			@mysqli_query($con, "DELETE FROM `auditoria`.`cfg_correo_usuario` WHERE `Usu_Cod`={$usuCod} AND `Emp_Cod`={$emp}");
			return array('success' => true, 'message' => 'Correo del usuario eliminado.');
		}
		if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
			return array('success' => false, 'message' => 'El correo indicado no es valido.');
		}
		aud_notif_ensure_schema($obBD_conexion);
		$correoEsc = mysqli_real_escape_string($con, strtolower($correo));
		$fec = date('Y-m-d H:i:s');
		$ok = @mysqli_query($con, "INSERT INTO `auditoria`.`cfg_correo_usuario` (`Usu_Cod`,`Emp_Cod`,`Correo`,`Fec_Reg`)
			VALUES ({$usuCod},{$emp},'{$correoEsc}','{$fec}')
			ON DUPLICATE KEY UPDATE `Correo`='{$correoEsc}', `Fec_Reg`='{$fec}'");
		return array('success' => (bool)$ok, 'message' => $ok ? 'Correo del usuario registrado.' : 'No se pudo registrar el correo.');
	}
}

if (!function_exists('aud_notif_org_chain')) {
	/**
	 * Cadena de Org_Cod (directorio, modulo y ancestros) de un proceso.
	 */
	function aud_notif_org_chain($con, $datDis, $pcsCod)
	{
		$pcsCod = (int)$pcsCod;
		$datDis = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$datDis);
		if ($pcsCod <= 0 || $datDis === '' || !$con) {
			return array();
		}
		$r = @mysqli_query($con, "SELECT `Org_Cod` FROM `{$datDis}`.`procesos` WHERE `Pcs_Cod`={$pcsCod} LIMIT 1");
		$org = 0;
		if ($r) {
			$row = mysqli_fetch_assoc($r);
			mysqli_free_result($r);
			if (!empty($row['Org_Cod'])) {
				$org = (int)$row['Org_Cod'];
			}
		}
		if ($org <= 0) {
			return array();
		}
		$chain = array();
		$guard = 0;
		while ($org > 0 && $guard < 8) {
			$chain[] = $org;
			$rp = @mysqli_query($con, "SELECT `Org_Niv` FROM `{$datDis}`.`organizado` WHERE `Org_Cod`={$org} LIMIT 1");
			$parent = 0;
			if ($rp) {
				$rowp = mysqli_fetch_assoc($rp);
				mysqli_free_result($rp);
				if (!empty($rowp['Org_Niv'])) {
					$parent = (int)$rowp['Org_Niv'];
				}
			}
			if ($parent <= 0 || $parent === $org || in_array($parent, $chain, true)) {
				break;
			}
			$org = $parent;
			$guard++;
		}
		return $chain;
	}
}

if (!function_exists('aud_notif_reglas_coincidentes')) {
	/**
	 * Reglas activas de la empresa que coinciden con el proceso/evento indicado.
	 */
	function aud_notif_reglas_coincidentes($con, $emp, $eveIni, $pcsCod, $datDis)
	{
		$emp = (int)$emp;
		$pcsCod = (int)$pcsCod;
		$eveIni = strtoupper(trim((string)$eveIni));
		if ($emp <= 0 || $pcsCod <= 0 || $eveIni === '' || !$con) {
			return array();
		}
		$r = @mysqli_query($con, "SELECT `Not_Cod`,`Org_Cod`,`Pcs_Cod`,`Not_Eventos`,`Not_Correos`,`Not_Usuarios`
			FROM `auditoria`.`cfg_notificaciones`
			WHERE `Emp_Cod`={$emp} AND `Not_Est`='A'");
		if (!$r) {
			return array();
		}
		$candidatas = array();
		while ($row = mysqli_fetch_assoc($r)) {
			$eventos = explode(',', (string)$row['Not_Eventos']);
			if (!in_array($eveIni, $eventos, true)) {
				continue;
			}
			$candidatas[] = $row;
		}
		mysqli_free_result($r);
		if (count($candidatas) === 0) {
			return array();
		}
		$chain = null;
		$out = array();
		foreach ($candidatas as $row) {
			$rgOrg = (int)$row['Org_Cod'];
			$rgPcs = (int)$row['Pcs_Cod'];
			if ($rgPcs > 0) {
				if ($rgPcs === $pcsCod) {
					$out[] = $row;
				}
				continue;
			}
			if ($chain === null) {
				$chain = aud_notif_org_chain($con, $datDis, $pcsCod);
			}
			if (in_array($rgOrg, $chain, true)) {
				$out[] = $row;
			}
		}
		return $out;
	}
}

if (!function_exists('aud_notif_resolver_destinatarios')) {
	function aud_notif_resolver_destinatarios($con, $emp, $reglas)
	{
		$emp = (int)$emp;
		$out = array();
		$usuCods = array();
		foreach ($reglas as $r) {
			if (!empty($r['Not_Correos'])) {
				foreach (explode(',', (string)$r['Not_Correos']) as $c) {
					$c = strtolower(trim($c));
					if ($c !== '' && filter_var($c, FILTER_VALIDATE_EMAIL) && !in_array($c, $out, true)) {
						$out[] = $c;
					}
				}
			}
			if (!empty($r['Not_Usuarios'])) {
				foreach (explode(',', (string)$r['Not_Usuarios']) as $u) {
					$u = (int)$u;
					if ($u > 0 && !in_array($u, $usuCods, true)) {
						$usuCods[] = $u;
					}
				}
			}
		}
		if (count($usuCods) > 0 && $con && $emp > 0) {
			$rc = @mysqli_query($con, "SELECT `Usu_Cod`,`Correo` FROM `auditoria`.`cfg_correo_usuario`
				WHERE `Emp_Cod`={$emp} AND `Usu_Cod` IN (" . implode(',', $usuCods) . ")");
			if ($rc) {
				while ($rowc = mysqli_fetch_assoc($rc)) {
					$c = strtolower(trim((string)$rowc['Correo']));
					if ($c !== '' && filter_var($c, FILTER_VALIDATE_EMAIL) && !in_array($c, $out, true)) {
						$out[] = $c;
					}
				}
				mysqli_free_result($rc);
			}
		}
		return $out;
	}
}

if (!function_exists('aud_notif_enviar_correo')) {
	/**
	 * Construye el contenido interpretado del log y despacha el correo por
	 * PHPMailer (misma libreria que usan los reportes del dashboard).
	 */
	function aud_notif_enviar_correo($con, $datDis, $emp, $eveIni, $logCod, $destinatarios)
	{
		if (empty($destinatarios) || $logCod <= 0 || !$con) {
			return false;
		}
		$prevDis = isset($GLOBALS['Ses_Dat_Dis']) ? $GLOBALS['Ses_Dat_Dis'] : null;
		$GLOBALS['Ses_Dat_Dis'] = $datDis;

		$row = array();
		if (function_exists('aud_logs_select')) {
			$logCodEsc = (int)$logCod;
			$sql = aud_logs_select() . " WHERE `logs`.`Log_Cod` = {$logCodEsc} LIMIT 1";
			$r = @mysqli_query($con, $sql);
			if ($r) {
				$row = mysqli_fetch_assoc($r);
				mysqli_free_result($r);
			}
		}
		if ($prevDis !== null) {
			$GLOBALS['Ses_Dat_Dis'] = $prevDis;
		} else {
			unset($GLOBALS['Ses_Dat_Dis']);
		}
		if (!is_array($row) || empty($row)) {
			return false;
		}

		$verbo = function_exists('aud_verbo_evento') ? aud_verbo_evento($eveIni, isset($row['Eve_Des']) ? $row['Eve_Des'] : '') : ($eveIni === 'D' ? 'Elimino' : 'Modifico');
		$usuario = function_exists('aud_nombre_usuario') ? aud_nombre_usuario($row) : ('Usuario ' . (isset($row['Usu_Cod']) ? $row['Usu_Cod'] : ''));
		$modulo = function_exists('aud_nombre_modulo') ? aud_nombre_modulo($row) : (isset($row['Mod_Des']) ? $row['Mod_Des'] : '');
		$proceso = function_exists('aud_nombre_proceso') ? aud_nombre_proceso($row) : (isset($row['Pcs_Lin']) ? $row['Pcs_Lin'] : '');
		$empresa = isset($row['Emp_Nom']) ? trim($row['Emp_Nom']) : ('Empresa ' . $emp);
		$fecha = isset($row['Log_Fec']) ? $row['Log_Fec'] : date('Y-m-d H:i:s');
		$pares = function_exists('aud_pares_interpretados') ? aud_pares_interpretados($row, null, null) : array();
		$detalle = function_exists('aud_resumen_detalle') ? aud_resumen_detalle($row, $pares) : '';

		$eveTxt = ($eveIni === 'D') ? 'Eliminacion' : (($eveIni === 'I') ? 'Insercion' : 'Actualizacion');
		$asunto = '[Auditoria] ' . $eveTxt . ' en ' . ($proceso !== '' ? $proceso : $modulo);

		$cuerpo = '<h3>Notificacion de auditoria: ' . htmlspecialchars($eveTxt) . '</h3>';
		$cuerpo .= '<p><strong>Empresa:</strong> ' . htmlspecialchars($empresa) . '</p>';
		$cuerpo .= '<p><strong>Fecha:</strong> ' . htmlspecialchars($fecha) . '</p>';
		$cuerpo .= '<p><strong>Usuario:</strong> ' . htmlspecialchars($usuario) . '</p>';
		$cuerpo .= '<p><strong>Modulo:</strong> ' . htmlspecialchars($modulo) . '</p>';
		if ($proceso !== '') {
			$cuerpo .= '<p><strong>Proceso:</strong> ' . htmlspecialchars($proceso) . '</p>';
		}
		$cuerpo .= '<p><strong>' . htmlspecialchars($verbo) . ':</strong> ' . htmlspecialchars($detalle) . '</p>';
		$cuerpo .= '<hr><p style="font-size:11px;color:#777;">Notificacion automatica generada por el Modulo de Auditoria de ExaContable ERP segun las reglas configuradas en Configuracion de monitoreo.</p>';

		return aud_notif_despachar_phpmailer($destinatarios, $asunto, $cuerpo);
	}
}

if (!function_exists('aud_notif_despachar_phpmailer')) {
	function aud_notif_despachar_phpmailer($destinatarios, $asunto, $cuerpoHtml)
	{
		$phpMailerPath = dirname(__FILE__) . '/../../Librerias/PHPMailer_2023/PHPMailer.php';
		$legacy = !file_exists($phpMailerPath);
		if ($legacy) {
			$phpMailerPath = dirname(__FILE__) . '/../../Librerias/PHPMailer/class.phpmailer.php';
		}
		if (!file_exists($phpMailerPath)) {
			return false;
		}
		try {
			if (!$legacy) {
				require_once dirname(__FILE__) . '/../../Librerias/PHPMailer_2023/Exception.php';
				require_once dirname(__FILE__) . '/../../Librerias/PHPMailer_2023/PHPMailer.php';
				require_once dirname(__FILE__) . '/../../Librerias/PHPMailer_2023/SMTP.php';
				$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
			} else {
				require_once $phpMailerPath;
				$mail = new PHPMailer();
			}
			$mail->isHTML(true);
			$mail->CharSet = 'UTF-8';
			$mail->Subject = $asunto;
			$mail->Body = $cuerpoHtml;
			if (property_exists($mail, 'Timeout')) {
				$mail->Timeout = 8;
			}
			if (property_exists($mail, 'SMTPKeepAlive')) {
				$mail->SMTPKeepAlive = false;
			}
			foreach ($destinatarios as $correo) {
				$mail->addAddress($correo);
			}
			return (bool)@$mail->send();
		} catch (\Exception $e) {
			return false;
		} catch (\Throwable $e2) {
			return false;
		}
	}
}

if (!function_exists('aud_notif_procesar_evento')) {
	/**
	 * Punto de entrada llamado por AuditQueue tras persistir un log.
	 * Nunca debe romper la captura: cualquier fallo se silencia.
	 */
	function aud_notif_procesar_evento($con, $emp, $datDis, $eveIni, $pcsCod, $usuCod, $tabla, $logCod)
	{
		try {
			$eveIni = strtoupper(trim((string)$eveIni));
			if (!$con || (int)$logCod <= 0 || ($eveIni !== 'U' && $eveIni !== 'D' && $eveIni !== 'I')) {
				return false;
			}
			aud_notif_ensure_schema($con);
			$reglas = aud_notif_reglas_coincidentes($con, $emp, $eveIni, $pcsCod, $datDis);
			if (count($reglas) === 0) {
				return false;
			}
			$destinatarios = aud_notif_resolver_destinatarios($con, $emp, $reglas);
			if (count($destinatarios) === 0) {
				return false;
			}
			if (!function_exists('aud_logs_select') && file_exists(dirname(__FILE__) . '/aud_log_interpretar.php')) {
				require_once dirname(__FILE__) . '/aud_log_interpretar.php';
			}
			return aud_notif_enviar_correo($con, $datDis, $emp, $eveIni, $logCod, $destinatarios);
		} catch (\Exception $e) {
			return false;
		} catch (\Throwable $e2) {
			return false;
		}
	}
}
?>
