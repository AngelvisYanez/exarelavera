<?php
/**
 * Servicio del Dashboard Estadistico Interactivo de Monitoreo de Actividades.
 *
 * Devuelve metricas de un unico periodo (empresa de sesion):
 * - Resumen de eventos (Ingresar/Actualizar/Eliminar)
 * - Tendencia diaria de actividad
 * - Actividad por modulo, franja horaria y proceso
 * - Top de usuarios mas activos
 * - Top de plantas de beneficio con movimiento (Pla_Cod extraido del par Log_Cam/Log_Val)
 * - Comportamiento diario por usuario (curva apilada)
 *
 * Ademas permite exportar el informe del periodo en PDF, enviarlo por correo
 * o compartirlo por WhatsApp (replicando el patron del dashboard comparativo).
 *
 * Compatible con PHP 5.6 y PHP 8.2+.
 *
 * @package auditoria.LOGICA
 */

require_once dirname(__FILE__) . '/aud_sql_dashboard.php';

if (!function_exists('aud_dash_to_utf8_deep')) {
	/**
	 * Sanitiza recursivamente valores y claves asociativas para json_encode.
	 * Definición local: el archivo aud_log_dashboard.php es un controlador separado
	 * y no debe ser incluido aqui (ejecutaria su propia accion ajax).
	 */
	function aud_dash_to_utf8_deep($data) {
		if (is_string($data)) {
			if (function_exists('mb_check_encoding') && @mb_check_encoding($data, 'UTF-8')) {
				return $data;
			}
			if (function_exists('mb_convert_encoding')) {
				return @mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
			}
			return @utf8_encode($data);
		}
		if (is_array($data)) {
			$clean = array();
			foreach ($data as $k => $v) {
				$cleanKey = is_string($k) ? aud_dash_to_utf8_deep($k) : $k;
				$clean[$cleanKey] = aud_dash_to_utf8_deep($v);
			}
			return $clean;
		}
		return $data;
	}
}

if (!function_exists('aud_dash_monitoreo_calcular')) {
	/**
	 * Calcula todas las metricas del periodo para la empresa indicada.
	 *
	 * @param int    $audEmpCod Empresa de sesion.
	 * @param string $ini Fecha/hora inicial.
	 * @param string $fin Fecha/hora final.
	 * @param int    $lim Limite de top modulos/usuarios/plantas.
	 * @return array
	 */
	function aud_dash_monitoreo_calcular($audEmpCod, $ini, $fin, $lim = 8) {
		// Conexion a la bd de auditoria (mismo mecanismo del dashboard comparativo)
		$con = null;
		$Ses_Dat_Dis = isset($_SESSION['Ses_Dat_Dis']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']) : '';
		$cerrarCon = false;
		if (!class_exists('Class_Log_Conexion_CfgMon') && file_exists(dirname(__FILE__) . '/aud_log_config_monitoreo.php')) {
			require_once dirname(__FILE__) . '/aud_log_config_monitoreo.php';
		}
		if (class_exists('Class_Log_Conexion_CfgMon')) {
			$conObj = new Class_Log_Conexion_CfgMon($Ses_Dat_Dis !== '' ? $Ses_Dat_Dis : null);
			$con = $conObj->conexion;
		} elseif (function_exists('aud_db_connect')) {
			require_once dirname(__FILE__) . '/aud_log_auditoria.php';
			$con = aud_db_connect();
			$cerrarCon = true;
		}

		$resp = array('empresa' => 'Empresa ' . $audEmpCod, 'rango' => substr($ini, 0, 10) . ' al ' . substr($fin, 0, 10));

		if (!$con) {
			return $resp;
		}

		// Nombre empresa
		$q = mysqli_query($con, sentencias_dashboard(9, array($audEmpCod)));
		if ($q && $r = mysqli_fetch_assoc($q)) {
			$resp['empresa'] = !empty($r['Emp_Nom']) ? $r['Emp_Nom'] : $resp['empresa'];
		}

		// Resumen
		$resp['resumen'] = array('total' => 0, 'insert' => 0, 'update' => 0, 'delete' => 0, 'usuarios_unicos' => 0);
		$q = mysqli_query($con, sentencias_dashboard(1, array($audEmpCod, $ini, $fin)));
		if ($q && $r = mysqli_fetch_assoc($q)) {
			$resp['resumen'] = array(
				'total' => (int)$r['Total_Movimientos'],
				'insert' => (int)$r['Total_Insert'],
				'update' => (int)$r['Total_Update'],
				'delete' => (int)$r['Total_Delete']
			);
		}

		// Tendencia diaria
		$cats = array();
		$serie = array();
		$q = mysqli_query($con, sentencias_dashboard(5, array($audEmpCod, $ini, $fin)));
		$usuTend = 0;
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$cats[] = $r['Dia'];
			$serie[] = (int)$r['Total'];
			$usuTend += (int)$r['Usuarios_Activos'];
		}
		$resp['tendencia'] = array('categorias' => $cats, 'serie' => $serie);
		$resp['resumen']['usuarios_unicos'] = max($usuTend, 0);
		if (!empty($cats)) {
			$resp['resumen']['usuarios_unicos'] = 0;
			// Recalcular usuarios unicos reales mediante case 2 (total usuarios por modulo)
			$mapUsuB = array();
			$q2 = mysqli_query($con, sentencias_dashboard(6, array($audEmpCod, $ini, $fin, 100000)));
			while ($q2 && $f = mysqli_fetch_assoc($q2)) {
				$mapUsuB[(int)$f['Usu_Cod']] = 1;
			}
			$resp['resumen']['usuarios_unicos'] = count($mapUsuB);
		}

		// Top modulos
		$resp['modulos'] = array();
		$q = mysqli_query($con, sentencias_dashboard(2, array($audEmpCod, $ini, $fin, $lim)));
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$resp['modulos'][] = array(
				'modulo' => !empty($r['Modulo']) ? $r['Modulo'] : 'Sin Modulo',
				'total' => (int)$r['Total']
			);
		}

		// Franja horaria
		$horarios = array();
		for ($h = 0; $h < 24; $h++) $horarios[] = array('hora' => sprintf('%02d:00', $h), 'total' => 0);
		$q = mysqli_query($con, sentencias_dashboard(3, array($audEmpCod, $ini, $fin)));
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$h = (int)$r['Hora'];
			if ($h >= 0 && $h <= 23) $horarios[$h]['total'] = (int)$r['Total'];
		}
		$resp['horarios'] = $horarios;

		// Top usuarios
		$resp['usuarios_top'] = array();
		$q = mysqli_query($con, sentencias_dashboard(6, array($audEmpCod, $ini, $fin, $lim)));
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$nom = trim(trim(isset($r['Prs_Nom']) ? $r['Prs_Nom'] : ('Usuario #' . $r['Usu_Cod'])) . ' ' . (isset($r['Prs_Ape']) ? $r['Prs_Ape'] : ''));
			if ($nom === '') $nom = 'Usuario #' . $r['Usu_Cod'];
			$resp['usuarios_top'][] = array(
				'Usu_Cod' => (int)$r['Usu_Cod'],
				'nombre' => $nom,
				'total' => (int)$r['Total_Operaciones'],
				'insert' => (int)$r['Total_Inserciones'],
				'update' => (int)$r['Total_Modificaciones'],
				'delete' => (int)$r['Total_Eliminaciones']
			);
		}

		// Top plantas
		$resp['plantas_top'] = array();
		$q = mysqli_query($con, sentencias_dashboard(7, array($audEmpCod, $ini, $fin, $lim)));
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$resp['plantas_top'][] = array(
				'Pla_Cod' => (int)$r['Pla_Cod'],
				'planta' => !empty($r['Planta']) ? $r['Planta'] : ('Planta #' . $r['Pla_Cod']),
				'total' => (int)$r['Total'],
				'usuarios' => (int)$r['Usuarios'],
				'insert' => (int)$r['Inserts'],
				'update' => (int)$r['Updates'],
				'delete' => (int)$r['Deletes']
			);
		}

		// Comportamiento diario por usuario (top 8 por total del periodo)
		$mapUsu = array();
		$q = mysqli_query($con, sentencias_dashboard(8, array($audEmpCod, $ini, $fin)));
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$c = (int)$r['Usu_Cod'];
			if (!isset($mapUsu[$c])) {
				$mapUsu[$c] = array('nombre' => '', 'total' => 0, 'dias' => array());
			}
			if (trim($mapUsu[$c]['nombre']) === '') {
				$nom = trim(trim(isset($r['UsuarioNombre']) ? $r['UsuarioNombre'] : ('Usuario #' . $c)) . ' ' . (isset($r['UsuarioApellido']) ? $r['UsuarioApellido'] : ''));
				$mapUsu[$c]['nombre'] = $nom !== '' ? $nom : ('Usuario #' . $c);
			}
			$mapUsu[$c]['total'] += (int)$r['Total'];
			$mapUsu[$c]['dias'][$r['Dia']] = (int)$r['Total'];
		}
		uasort($mapUsu, function($a, $b) { return $b['total'] - $a['total']; });
		$top8 = array_slice(array_keys($mapUsu), 0, 8);
		$usuarioSerie = array();
		foreach ($top8 as $c) {
			$s = array();
			foreach ($cats as $d) {
				$s[] = isset($mapUsu[$c]['dias'][$d]) ? $mapUsu[$c]['dias'][$d] : 0;
			}
			$usuarioSerie[] = array('nombre' => $mapUsu[$c]['nombre'], 'serie' => $s);
		}
		$resp['usuario_dias'] = array('categorias' => $cats, 'series' => $usuarioSerie);

		if ($cerrarCon && $con) {
			mysqli_close($con);
		}

		return $resp;
	}
}

if (!function_exists('aud_dash_preparar_whatsapp_monitoreo')) {
	/**
	 * Prepara el mensaje y enlace de despacho para WhatsApp Web del monitoreo.
	 *
	 * @param array  $datos Calculo del monitoreo.
	 * @param string $telefono Telefono destino con codigo de pais.
	 * @return array
	 */
	function aud_dash_preparar_whatsapp_monitoreo($datos, $telefono) {
		$cleanTel = preg_replace('/[^0-9]/', '', (string)$telefono);
		if ($cleanTel === '' || strlen($cleanTel) < 8) {
			return array(
				'success' => false,
				'message' => 'Debe proporcionar un numero telefonico valido (minimo 8 digitos).'
			);
		}

		$empresa = isset($datos['empresa']) ? $datos['empresa'] : 'ExaContable';
		$rango = isset($datos['rango']) ? $datos['rango'] : '';

		$texto = "*INFORME ESTADÍSTICO DE MONITOREO DE AUDITORÍA*\n";
		$texto .= "🏢 Empresa: " . $empresa . "\n";
		if ($rango !== '') $texto .= "📅 Rango: " . $rango . "\n\n";

		if (!empty($datos['resumen']) && is_array($datos['resumen'])) {
			$texto .= "📊 *Métricas Destacadas:*\n";
			$texto .= "• Total Movimientos: " . number_format((int)$datos['resumen']['total']) . "\n";
			$texto .= "• Ingresar: " . number_format((int)$datos['resumen']['insert']) . "\n";
			$texto .= "• Actualizar: " . number_format((int)$datos['resumen']['update']) . "\n";
			$texto .= "• Eliminar: " . number_format((int)$datos['resumen']['delete']) . "\n";
			$texto .= "• Usuarios Únicos: " . ((int)$datos['resumen']['usuarios_unicos']) . "\n";
		}

		if (!empty($datos['modulos']) && is_array($datos['modulos'])) {
			$texto .= "\n🗂️ *Módulos con más actividad:*\n";
			$top = array_slice($datos['modulos'], 0, 5);
			foreach ($top as $m) {
				$texto .= "• " . $m['modulo'] . ": " . number_format((int)$m['total']) . "\n";
			}
		}

		$texto .= "\n_Generado por ExaContable ERP Auditoría_";
		$urlWa = "https://api.whatsapp.com/send?phone=" . urlencode($cleanTel) . "&text=" . urlencode($texto);

		return array(
			'success' => true,
			'telefono' => $cleanTel,
			'url_web' => $urlWa,
			'url_whatsapp' => $urlWa,
			'mensaje' => $texto,
			'mensaje_previo' => $texto
		);
	}
}

if (!function_exists('aud_dash_procesar_whatsapp_monitoreo')) {
	function aud_dash_procesar_whatsapp_monitoreo($datos, $telefono) {
		return aud_dash_preparar_whatsapp_monitoreo($datos, $telefono);
	}
}

// -------------------------------------------------------------
// CONTROLADOR AJAX PARA PETICIONES DEL FRONTEND DEL DASHBOARD
// -------------------------------------------------------------
if (isset($_REQUEST['action'])) {
	@ini_set('display_errors', '0');
	$action = trim($_REQUEST['action']);

	if (session_id() === '') {
		@session_start();
	}

	$audEmpCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
	$audUsuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;

	$ini = isset($_REQUEST['ini']) ? trim($_REQUEST['ini']) : '';
	$fin = isset($_REQUEST['fin']) ? trim($_REQUEST['fin']) : '';
	$ini = $ini !== '' ? $ini : date('Y-m-d 00:00:00', strtotime('-30 days'));
	$fin = $fin !== '' ? $fin : date('Y-m-d 23:59:59');

	switch ($action) {
		case 'consultar':
			@header('Content-Type: application/json; charset=utf-8');
			$datos = aud_dash_monitoreo_calcular($audEmpCod, $ini, $fin);
			$datos = aud_dash_to_utf8_deep($datos);
			echo json_encode(array('success' => true) + $datos);
			exit();

		case 'exportar_pdf':
			require_once dirname(__FILE__) . '/aud_rep_monitoreo_pdf.php';
			$datos = aud_dash_monitoreo_calcular($audEmpCod, $ini, $fin);
			$datos = aud_dash_to_utf8_deep($datos);
			$datos['usuario_emisor'] = (isset($_SESSION['Ses_Usu_Nom']) && trim($_SESSION['Ses_Usu_Nom']) !== '')
				? $_SESSION['Ses_Usu_Nom'] : 'Auditor del Sistema';
			aud_generar_reporte_monitoreo_pdf($datos, 'I');
			exit();

		case 'enviar_correo':
			@header('Content-Type: application/json; charset=utf-8');
			$destCorreo = isset($_REQUEST['correo']) ? trim($_REQUEST['correo']) : '';
			$destNombre = isset($_REQUEST['nombre']) ? trim($_REQUEST['nombre']) : 'Destinatario';
			$asunto     = isset($_REQUEST['asunto']) ? trim($_REQUEST['asunto']) : 'Informe Estadistico de Actividad de Auditoria';

			if ($destCorreo === '' || !filter_var($destCorreo, FILTER_VALIDATE_EMAIL)) {
				echo json_encode(array('success' => false, 'message' => 'Debe proporcionar un correo electrónico válido.'));
				exit();
			}

			require_once dirname(__FILE__) . '/aud_rep_monitoreo_pdf.php';
			$datos = aud_dash_monitoreo_calcular($audEmpCod, $ini, $fin);
			$datos = aud_dash_to_utf8_deep($datos);
			$datos['usuario_emisor'] = (isset($_SESSION['Ses_Usu_Nom']) && trim($_SESSION['Ses_Usu_Nom']) !== '')
				? $_SESSION['Ses_Usu_Nom'] : 'Auditor del Sistema';

			// Generar PDF en memoria ('S')
			$pdfContenido = aud_generar_reporte_monitoreo_pdf($datos, 'S');

			// Integracion con PHPMailer del ERP
			$mailEnviado = false;
			$errMsg = '';

			$phpMailerPath = dirname(__FILE__) . '/../../Librerias/PHPMailer_2023/PHPMailer.php';
			if (!file_exists($phpMailerPath)) {
				$phpMailerPath = dirname(__FILE__) . '/../../Librerias/PHPMailer/class.phpmailer.php';
			}

			if (file_exists($phpMailerPath)) {
				try {
					if (strpos($phpMailerPath, '2023') !== false) {
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
					$mail->addAddress($destCorreo, $destNombre);

					$cuerpoHtml = "
					<h3>Informe Estadístico de Monitoreo de Auditoría</h3>
					<p>Estimado/a <strong>{$destNombre}</strong>,</p>
					<p>Adjunto a este correo encontrará el reporte formal de monitoreo de actividad de la empresa <strong>{$datos['empresa']}</strong>.</p>
					<ul>
						<li><strong>Rango analizado:</strong> {$datos['rango']}</li>
						<li><strong>Total de movimientos:</strong> " . number_format((int)$datos['resumen']['total']) . "</li>
					</ul>
					<p>Este documento contiene métricas de volumen, operaciones de ingreso, actualización y eliminación, actividad por módulo, franjas horarias y top de usuarios y plantas.</p>
					<hr>
					<p style='font-size:11px;color:#777;'>Generado automáticamente por el Módulo de Auditoría de ExaContable ERP.</p>";

					$mail->Body = $cuerpoHtml;
					$mail->addStringAttachment($pdfContenido, 'reporte_monitoreo_auditoria.pdf', 'base64', 'application/pdf');

					// Enviar correo si está configurado el transporte
					if (@$mail->send()) {
						$mailEnviado = true;
					} else {
						$errMsg = isset($mail->ErrorInfo) ? $mail->ErrorInfo : 'No se pudo despachar el mensaje.';
					}
				} catch (\Exception $eMail) {
					$errMsg = $eMail->getMessage();
				}
			} else {
				$errMsg = 'Librería PHPMailer no encontrada en el sistema.';
			}

			// Mock / Fallback de despacho
			if (!$mailEnviado) {
				echo json_encode(array(
					'success' => true,
					'simulado' => true,
					'message' => "El reporte PDF de monitoreo fue generado satisfactoriamente para '{$destCorreo}'. (Nota: {$errMsg})"
				));
			} else {
				echo json_encode(array(
					'success' => true,
					'simulado' => false,
					'message' => "El reporte PDF de monitoreo ha sido enviado exitosamente a '{$destCorreo}'."
				));
			}
			exit();

		case 'enviar_whatsapp':
			@header('Content-Type: application/json; charset=utf-8');
			$telefono = isset($_REQUEST['telefono']) ? $_REQUEST['telefono'] : '';
			$datos = aud_dash_monitoreo_calcular($audEmpCod, $ini, $fin);
			$datos = aud_dash_to_utf8_deep($datos);
			$resWa = aud_dash_preparar_whatsapp_monitoreo($datos, $telefono);
			echo json_encode($resWa);
			exit();
	}
}