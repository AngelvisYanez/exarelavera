<?php
/**
 * Pruebas unitarias para el Dashboard Estadistico Comparativo,
 * generacion de Reportes PDF y envios WhatsApp / Correo (Fase 3).
 *
 * @package auditoria.TEST
 */

require_once dirname(__FILE__) . '/aud_test_lib.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_sql_dashboard.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_log_dashboard.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_rep_comparativa_pdf.php';

function aud_unit_dash_sentencias_sql()
{
	for ($t = 1; $t <= 6; $t++) {
		$sql = sentencias_dashboard_comparativo($t, array(1, '2025-01-01 00:00:00', '2025-01-15 23:59:59', 8));
		aud_assert(is_string($sql) && strlen(trim($sql)) >= 10, "sentencias_dashboard_comparativo({$t}) no devolvio un SQL valido");
	}
}

function aud_unit_dash_calculo_comparativo()
{
	$con = aud_db_connect();
	if (!$con) {
		return;
	}

	$pA_ini = '2025-01-01 00:00:00';
	$pA_fin = '2025-01-15 23:59:59';
	$pB_ini = '2025-01-16 00:00:00';
	$pB_fin = '2025-01-31 23:59:59';

	$res = aud_dash_calcular_comparativa(1, $pA_ini, $pA_fin, $pB_ini, $pB_fin, $con);

	aud_assert(is_array($res), 'aud_dash_calcular_comparativa retorno un arreglo');
	aud_assert(!empty($res['kpis_comparativa']), 'Se calcularon los KPIs comparativos');
	aud_assert(is_array($res['modulos_comparativa']), 'modulos_comparativa es un arreglo');
	aud_assert(is_array($res['observaciones']), 'observaciones es un arreglo');
	aud_assert(count($res['horarios_comparativa']) === 24, 'horarios_comparativa cubre 24 franjas');

	@mysqli_close($con);
}

function aud_unit_dash_generacion_pdf()
{
	$mockData = array(
		'empresa_nombre' => 'EMPRESA PRUEBAS S.A.',
		'periodo_a_label' => '01/01/2025 a 15/01/2025',
		'periodo_b_label' => '16/01/2025 a 31/01/2025',
		'usuario_emisor' => 'Auditor Master',
		'kpis_comparativa' => array(
			array('titulo' => 'Total Movimientos', 'valor_a' => 1200, 'valor_b' => 1450, 'pct_cambio' => 20.8),
			array('titulo' => 'Inserciones (INSERT)', 'valor_a' => 800, 'valor_b' => 950, 'pct_cambio' => 18.8),
			array('titulo' => 'Modificaciones (UPDATE)', 'valor_a' => 380, 'valor_b' => 480, 'pct_cambio' => 26.3),
			array('titulo' => 'Eliminaciones (DELETE)', 'valor_a' => 20, 'valor_b' => 20, 'pct_cambio' => 0.0),
			array('titulo' => 'Sesiones Iniciadas', 'valor_a' => 45, 'valor_b' => 52, 'pct_cambio' => 15.6),
			array('titulo' => 'Tiempo Promedio Uso (min)', 'valor_a' => 38.5, 'valor_b' => 42.1, 'pct_cambio' => 9.4),
			array('titulo' => 'Cierres por Inactividad', 'valor_a' => 4, 'valor_b' => 2, 'pct_cambio' => -50.0),
			array('titulo' => 'Cierres Forzados Admin', 'valor_a' => 0, 'valor_b' => 1, 'pct_cambio' => 100.0)
		),
		'modulos_comparativa' => array(
			array('modulo' => 'Facturación', 'total_a' => 600, 'total_b' => 750, 'pct_cambio' => 25.0),
			array('modulo' => 'Contabilidad', 'total_a' => 400, 'total_b' => 500, 'pct_cambio' => 25.0),
			array('modulo' => 'Relavera / Transportes', 'total_a' => 200, 'total_b' => 200, 'pct_cambio' => 0.0)
		),
		'usuarios_top_b' => array(
			array(
				'UsuarioNombre' => 'Carlos',
				'UsuarioApellido' => 'Mendoza',
				'Total_Operaciones' => 450,
				'Total_Inserciones' => 300,
				'Total_Modificaciones' => 145,
				'Total_Eliminaciones' => 5
			),
			array(
				'UsuarioNombre' => 'Ana',
				'UsuarioApellido' => 'Gomez',
				'Total_Operaciones' => 320,
				'Total_Inserciones' => 200,
				'Total_Modificaciones' => 120,
				'Total_Eliminaciones' => 0
			)
		),
		'observaciones' => array(
			'Se detectó un incremento de actividad global del +20.8% en el período comparado.',
			'El módulo Facturación concentró el 51.7% del total de las operaciones.',
			'Se cerraron 2 sesiones por inactividad prolongada (> 15 minutos).'
		)
	);

	$tmpPdf = sys_get_temp_dir() . '/rep_aud_test_' . time() . '.pdf';
	aud_generar_reporte_comparativo_pdf($mockData, 'F', $tmpPdf);

	aud_assert(file_exists($tmpPdf), 'El archivo PDF fue creado en disco temporal');
	$size = filesize($tmpPdf);
	aud_assert($size > 1024, "El archivo PDF generado tiene un tamaño valido ({$size} bytes)");

	// Validar que comienza con cabecera %PDF
	$fh = fopen($tmpPdf, 'rb');
	$magic = fread($fh, 4);
	fclose($fh);
	@unlink($tmpPdf);

	aud_assert($magic === '%PDF', 'El archivo generado es un PDF válido');
}

function aud_unit_dash_preparar_whatsapp()
{
	$mockData = array(
		'empresa_nombre' => 'EMPRESA PRUEBAS',
		'periodo_a_label' => '01/01 al 15/01',
		'periodo_b_label' => '16/01 al 31/01',
		'raw_a' => array('total_movimientos' => 100),
		'raw_b' => array(
			'total_movimientos' => 150,
			'eventos' => array('D' => 2),
			'sesiones' => array('total' => 10)
		),
		'observaciones' => array('Incremento general del 50%.')
	);

	$res = aud_dash_preparar_whatsapp($mockData, '593978835575', false);
	aud_assert($res['success'] === true, 'aud_dash_preparar_whatsapp retorno success');
	aud_assert($res['telefono'] === '593978835575', 'Telefono normalizado correctamente');
	aud_assert(strpos($res['mensaje'], 'INFORME COMPARATIVO DE AUDITORÍA ERP') !== false, 'Mensaje contiene titulo esperado');
	aud_assert(strpos($res['url_web'], 'https://api.whatsapp.com/send?') !== false, 'URL de WhatsApp Web generada correctamente');
}

function aud_run_dashboard_tests()
{
	$failed = 0;
	$passed = 0;
	echo "\n== Pruebas dashboard comparativo, PDF y WhatsApp/Email (Fase 3) ==\n";

	$cases = array(
		'aud_unit_dash_sentencias_sql',
		'aud_unit_dash_calculo_comparativo',
		'aud_unit_dash_generacion_pdf',
		'aud_unit_dash_preparar_whatsapp'
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

	echo "Dashboard: " . $passed . " ok, " . $failed . " fallidas\n";
	return $failed;
}
