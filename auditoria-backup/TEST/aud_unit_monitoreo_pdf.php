<?php
/**
 * Test unitario para generador de PDF de monitoreo de actividades y transacciones.
 */

require_once dirname(__FILE__) . '/../LOGICA/aud_rep_monitoreo_pdf.php';

echo "== Pruebas unitarias Monitoreo PDF ==\n";

$filtroData = array(
	'empresa_nombre' => 'EMPRESA PRUEBA 503',
	'sucursal_nombre' => 'Casa Matriz',
	'periodo_label' => 'Desde: 2026-08-01   Hasta: 2026-09-08',
	'usuario_emisor' => 'ismael',
	'total_registros' => 3
);

$filas = array(
	array(
		'Log_Cod' => 101,
		'Log_Fec' => '2026-09-08 10:15:30',
		'Mod_Nom' => 'Facturación',
		'Dir_Nom' => 'Ventas',
		'Pcs_Nom' => 'fac_alt_fac_ven_3.2.php',
		'Eve_Ini' => 'I',
		'Usu_Nom' => 'ismael',
		'Log_Des' => 'Ingreso de comprobante de venta VTA-001 por $1,250.00'
	),
	array(
		'Log_Cod' => 102,
		'Log_Fec' => '2026-09-08 11:20:00',
		'Mod_Nom' => 'Contabilidad',
		'Dir_Nom' => 'Comprobantes',
		'Pcs_Nom' => 'con_alt_compr_1.1.php',
		'Eve_Ini' => 'U',
		'Usu_Nom' => 'contador',
		'Log_Des' => 'Actualizar asiento contable 204'
	),
	array(
		'Log_Cod' => 103,
		'Log_Fec' => '2026-09-08 12:00:15',
		'Mod_Nom' => 'Relavera',
		'Dir_Nom' => 'Manifiestos',
		'Pcs_Nom' => 'rel_alt_manifiesto_1.0.php',
		'Eve_Ini' => 'D',
		'Usu_Nom' => 'supervisor',
		'Log_Des' => 'Eliminar registro temporal de despacho'
	)
);

$tmpPdf = sys_get_temp_dir() . '/test_monitoreo_' . uniqid() . '.pdf';
$res = aud_generar_reporte_monitoreo_pdf($filtroData, $filas, 'F', $tmpPdf);

if (file_exists($tmpPdf) && filesize($tmpPdf) > 1000) {
	echo "  OK  Archivo PDF creado correctamente (" . filesize($tmpPdf) . " bytes)\n";
	
	// Validar firma del archivo PDF (%PDF)
	$handle = fopen($tmpPdf, 'rb');
	$header = fread($handle, 4);
	fclose($handle);
	if ($header === '%PDF') {
		echo "  OK  Cabecera binaria %PDF valida\n";
	} else {
		echo "  FAIL Cabecera PDF invalida\n";
	}
	@unlink($tmpPdf);
} else {
	echo "  FAIL No se pudo generar el archivo PDF\n";
	exit(1);
}

// Probar caso con lista vacia
$tmpEmpty = sys_get_temp_dir() . '/test_empty_' . uniqid() . '.pdf';
$filtroData['total_registros'] = 0;
aud_generar_reporte_monitoreo_pdf($filtroData, array(), 'F', $tmpEmpty);
if (file_exists($tmpEmpty) && filesize($tmpEmpty) > 500) {
	echo "  OK  Reporte PDF con lista vacia generado correctamente\n";
	@unlink($tmpEmpty);
} else {
	echo "  FAIL Reporte vacio fallo\n";
	exit(1);
}

echo "Monitoreo PDF: Todas las pruebas OK\n";
