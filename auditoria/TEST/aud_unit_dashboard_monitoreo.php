<?php
/**
 * Pruebas unitarias del Dashboard Estadistico de Monitoreo: calculo del periodo,
 * generacion de Reporte PDF y envios WhatsApp / Correo (patron del comparativo).
 *
 * @package auditoria.TEST
 */

require_once dirname(__FILE__) . '/aud_test_lib.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_sql_dashboard.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_log_dashboard_monitoreo.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_rep_monitoreo_pdf.php';

function aud_dashmon_mock_datos()
{
	$horarios = array();
	for ($h = 0; $h < 24; $h++) {
		$horarios[] = array('hora' => sprintf('%02d:00', $h), 'total' => ($h % 3 === 0) ? $h * 3 : 0);
	}

	return array(
		'empresa' => 'EMPRESA PRUEBAS S.A.',
		'rango' => '01/08/2026 a 31/08/2026',
		'usuario_emisor' => 'Auditor Master',
		'resumen' => array(
			'total' => 115,
			'insert' => 80,
			'update' => 30,
			'delete' => 5,
			'usuarios_unicos' => 7
		),
		'modulos' => array(
			array('modulo' => 'Relavera / Transportes', 'total' => 60),
			array('modulo' => 'Contabilidad', 'total' => 35),
			array('modulo' => 'Facturación', 'total' => 20)
		),
		'horarios' => $horarios,
		'usuarios_top' => array(
			array('nombre' => 'Carlos Mendoza', 'total' => 45, 'insert' => 30, 'update' => 12, 'delete' => 3),
			array('nombre' => 'Ana Gomez', 'total' => 30, 'insert' => 20, 'update' => 10, 'delete' => 0)
		),
		'plantas_top' => array(
			array('planta' => 'PLANTA A', 'total' => 50, 'usuarios' => 4, 'insert' => 30, 'update' => 15, 'delete' => 5)
		)
	);
}

function aud_unit_dashmon_sentencias_sql()
{
	for ($t = 1; $t <= 9; $t++) {
		$sql = sentencias_dashboard($t, array(281, '2026-08-01 00:00:00', '2026-08-31 23:59:59', 8));
		aud_assert(is_string($sql) && strlen(trim($sql)) >= 10, "sentencias_dashboard({$t}) no devolvio un SQL valido");
	}
}

function aud_unit_dashmon_generacion_pdf()
{
	$tmpPdf = sys_get_temp_dir() . '/rep_mon_test_' . time() . '.pdf';
	aud_generar_reporte_monitoreo_pdf(aud_dashmon_mock_datos(), 'F', $tmpPdf);

	aud_assert(file_exists($tmpPdf), 'El archivo PDF del monitoreo fue creado en disco temporal');
	$size = filesize($tmpPdf);
	aud_assert($size > 1024, "El PDF del monitoreo tiene un tamaño valido ({$size} bytes)");

	$fh = fopen($tmpPdf, 'rb');
	$magic = fread($fh, 4);
	fclose($fh);
	@unlink($tmpPdf);

	aud_assert($magic === '%PDF', 'El PDF del monitoreo es un archivo valido');
}

function aud_unit_dashmon_pdf_memoria()
{
	$pdf = aud_generar_reporte_monitoreo_pdf(aud_dashmon_mock_datos(), 'S');
	aud_assert(is_string($pdf) && strlen($pdf) > 1024, 'Modo S devuelve el PDF en memoria');
	aud_assert(substr($pdf, 0, 4) === '%PDF', 'El PDF en memoria comienza con cabecera %PDF');
}

function aud_unit_dashmon_preparar_whatsapp()
{
	$res = aud_dash_preparar_whatsapp_monitoreo(aud_dashmon_mock_datos(), '593978835575');
	aud_assert($res['success'] === true, 'aud_dash_preparar_whatsapp_monitoreo retorno success');
	aud_assert($res['telefono'] === '593978835575', 'Telefono normalizado correctamente');
	aud_assert(strpos($res['mensaje'], 'INFORME ESTAD') !== false, 'Mensaje contiene el titulo esperado');
	aud_assert(strpos($res['mensaje'], 'Total Movimientos: 115') !== false, 'Mensaje resume el total de movimientos');
	aud_assert(strpos($res['url_web'], 'https://api.whatsapp.com/send?') !== false, 'URL de WhatsApp generada correctamente');

	$bad = aud_dash_preparar_whatsapp_monitoreo(aud_dashmon_mock_datos(), '123');
	aud_assert($bad['success'] === false, 'Telefono invalido se rechaza');
	aud_assert(strpos($bad['message'], 'Debe proporcionar un numero') !== false, 'El mensaje de error avisa del numero invalido');
}

function aud_unit_dashmon_calculo_db()
{
	$con = aud_db_connect();
	if (!$con) {
		echo "  SKIP  calculo monitoreo DB (sin conexion a auditoria)\n";
		return;
	}
	$r = @mysqli_query($con, "SELECT MIN(Log_Fec) AS mn, MAX(Log_Fec) AS mx, COUNT(*) AS n FROM `auditoria`.`logs` WHERE Emp_Cod=281");
	if (!$r) {
		@mysqli_close($con);
		echo "  SKIP  calculo monitoreo DB (no se pudo leer el rango de logs)\n";
		return;
	}
	$rango = mysqli_fetch_assoc($r);
	mysqli_free_result($r);
	$n = isset($rango['n']) ? (int)$rango['n'] : 0;
	if ($n === 0 || empty($rango['mn'])) {
		@mysqli_close($con);
		echo "  SKIP  calculo monitoreo DB (no hay logs del Emp 281)\n";
		return;
	}

	$oldDis = isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null;
	$_SESSION['Ses_Dat_Dis'] = 'exa_ecopark';
	$_SESSION['Ses_Emp_Cod'] = 281;

	$res = aud_dash_monitoreo_calcular(281, $rango['mn'], $rango['mx'], 8);

	aud_assert(is_array($res), 'aud_dash_monitoreo_calcular retorno un arreglo');
	aud_assert(isset($res['resumen']) && isset($res['resumen']['total']), 'El resumen incluye el total');
	aud_assert((int)$res['resumen']['total'] === $n, 'El total coincide con los logs del periodo (' . $res['resumen']['total'] . ' vs ' . $n . ')');
	aud_assert(is_array($res['modulos']), 'modulos es un arreglo');
	aud_assert(is_array($res['plantas_top']), 'plantas_top es un arreglo');
	aud_assert(is_array($res['usuarios_top']), 'usuarios_top es un arreglo');
	aud_assert(is_array($res['horarios']) && count($res['horarios']) === 24, 'horarios cubre 24 franjas');
	aud_assert(isset($res['tendencia']['categorias']) && is_array($res['tendencia']['categorias']), 'tendencia tiene categorias');
	aud_assert(isset($res['empresa']) && is_string($res['empresa']) && $res['empresa'] !== '', 'Se resolvio el nombre de la empresa');
	aud_assert(strpos($res['rango'], substr($rango['mn'], 0, 10)) !== false && strpos($res['rango'], substr($rango['mx'], 0, 10)) !== false, 'El rango del calculo se refleja en la respuesta');

	if ($oldDis !== null) {
		$_SESSION['Ses_Dat_Dis'] = $oldDis;
	} else {
		unset($_SESSION['Ses_Dat_Dis']);
	}

	echo "       calculo monitoreo: total=" . $res['resumen']['total'] . ", modulos=" . count($res['modulos']) . ", plantas=" . count($res['plantas_top']) . "\n";
	@mysqli_close($con);
}

function aud_unit_dashmon_servicio_actions()
{
	$svc = file_get_contents(dirname(__FILE__) . '/../LOGICA/aud_log_dashboard_monitoreo.php');
	aud_assert(strpos($svc, "case 'exportar_pdf'") !== false, 'El servicio soporta exportar_pdf');
	aud_assert(strpos($svc, "case 'enviar_correo'") !== false, 'El servicio soporta enviar_correo');
	aud_assert(strpos($svc, "case 'enviar_whatsapp'") !== false, 'El servicio soporta enviar_whatsapp');
	aud_assert(strpos($svc, 'aud_dash_monitoreo_calcular') !== false, 'El servicio expone el calculo del periodo');
	aud_assert(strpos($svc, 'aud_generar_reporte_monitoreo_pdf') !== false, 'El servicio genera el reporte PDF');
	aud_assert(strpos($svc, 'aud_dash_preparar_whatsapp_monitoreo') !== false, 'El servicio prepara el mensaje de WhatsApp');
	aud_assert(strpos($svc, 'PHPMailer') !== false, 'El envio de correo integra PHPMailer');

	$pdf = file_get_contents(dirname(__FILE__) . '/../LOGICA/aud_rep_monitoreo_pdf.php');
	aud_assert(strpos($pdf, 'class AudPDFMonitoreo') !== false, 'El generador PDF define su clase FPDF');
	aud_assert(strpos($pdf, 'function aud_generar_reporte_monitoreo_pdf') !== false, 'El generador PDF expone su funcion principal');
}

function aud_unit_dashmon_front_export_ui()
{
	$front = file_get_contents(dirname(__FILE__) . '/../FRONT/aud_con_dashboard_monitoreo_1.0.php');
	aud_assert(strpos($front, 'btnDescargarPdfMon') !== false, 'FRONT ofrece Exportar PDF');
	aud_assert(strpos($front, 'btnModalCorreoMon') !== false, 'FRONT ofrece Enviar Correo');
	aud_assert(strpos($front, 'btnModalWhatsAppMon') !== false, 'FRONT ofrece WhatsApp');
	aud_assert(strpos($front, 'modalEnvioCorreoMon') !== false, 'FRONT tiene el modal de correo');
	aud_assert(strpos($front, 'modalEnvioWhatsAppMon') !== false, 'FRONT tiene el modal de WhatsApp');
	aud_assert(strpos($front, 'mailDestinatarioMon') !== false, 'El modal de correo tiene el campo destinatario');
	aud_assert(strpos($front, 'waTelefonoMon') !== false, 'El modal de WhatsApp tiene el campo telefono');
	aud_assert(strpos($front, 'waPreviewMensajeMon') !== false, 'El modal de WhatsApp tiene vista previa');
	aud_assert(strpos($front, 'function audMonFechaParams') !== false, 'FRONT calcula los parametros del periodo');
	aud_assert(strpos($front, 'action=exportar_pdf') !== false, 'El boton PDF apunta al servicio correcto');
	aud_assert(strpos($front, 'action: \'enviar_correo\'') !== false || strpos($front, 'action: "enviar_correo"') !== false, 'El boton correo envia la accion correcta');
	aud_assert(strpos($front, 'action: \'enviar_whatsapp\'') !== false || strpos($front, 'action: "enviar_whatsapp"') !== false, 'El boton WhatsApp envia la accion correcta');
}

function aud_run_dashboard_monitoreo_tests()
{
	$failed = 0;
	$passed = 0;
	echo "\n== Pruebas dashboard monitoreo: calculo, PDF y WhatsApp/Correo ==\n";

	$cases = array(
		'aud_unit_dashmon_sentencias_sql',
		'aud_unit_dashmon_generacion_pdf',
		'aud_unit_dashmon_pdf_memoria',
		'aud_unit_dashmon_preparar_whatsapp',
		'aud_unit_dashmon_servicio_actions',
		'aud_unit_dashmon_front_export_ui',
		'aud_unit_dashmon_calculo_db'
	);

	foreach ($cases as $fn) {
		try {
			$fn();
			$passed++;
		} catch (Exception $e) {
			$failed++;
			echo "  FAIL  " . $fn . ": " . $e->getMessage() . "\n";
		}
	}

	echo "Dashboard Monitoreo: " . $passed . " ok, " . $failed . " fallidas\n";
	return $failed;
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
	$fails = aud_run_dashboard_monitoreo_tests();
	echo "\n========================================\n";
	echo $fails === 0 ? "DASHBOARD MONITOREO OK\n" : ("FALLOS: " . $fails . "\n");
	echo "========================================\n";
	exit($fails === 0 ? 0 : 1);
}