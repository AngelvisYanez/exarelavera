<?php
/**
 * Worker de un usuario simulado (un proceso = un request concurrente).
 *
 * Uso:
 *   php aud_concurrent_worker.php --user=1 --ops=12 --mode=capture
 *   php aud_concurrent_worker.php --user=1 --ops=12 --mode=flush
 *
 * @package auditoria.TEST
 */

require_once dirname(__FILE__) . '/aud_test_lib.php';

$user = 1;
$ops = 12;
$mode = 'capture';
foreach ($argv as $a) {
	if (strpos($a, '--user=') === 0) {
		$user = max(1, (int)substr($a, 7));
	} elseif (strpos($a, '--ops=') === 0) {
		$ops = max(1, (int)substr($a, 6));
	} elseif (strpos($a, '--mode=') === 0) {
		$mode = substr($a, 7);
	}
}

aud_test_putenv('AUDIT_ENABLED', 'true');
aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
$usuCod = 900000 + $user;
aud_session_user($usuCod);
AuditQueue::resetForTests();

/**
 * Movimientos de procesos realmente registrados en exa.procesos.
 * Solo sobre estos procesos puede resolverse el Pcs_Cod y persistir sin fallback.
 */
function aud_worker_sql($i)
{
	$sql = array(
		"INSERT INTO comprobantes (Com_Num, Com_Val, Com_Con) VALUES ('001-DEMO', 150.00, 'COMPROBANTE')",
		"DELETE FROM asientos WHERE Asi_Cod=9",
		"INSERT INTO ventas (Tic_Cod, Cli_Cod, Caj_Cod, Vnd_Cod, Vet_Num, Vet_Des, Vet_Hor) VALUES (1, 1, 1, 1, '001-001-000000001', '2026-08-17', '08:30:00')",
		"INSERT INTO ventas_det SET Vet_Cod=1, Pro_Cod=1, Vet_Can=2, Vet_Pru=10.00, Vet_Imp=20.00"
	);
	return $sql[$i % count($sql)];
}

function aud_worker_request_uri($i)
{
	$urls = array(
		'/contabilidad/comprobantes/con_alt_compr_1.1.php',
		'/contabilidad/comprobantes/con_alt_compr_1.1.php',
		'/facturacion/ventas/fac_alt_fac_ven_3.2.php',
		'/facturacion/ventas/fac_alt_fac_ven_3.2.php'
	);
	return $urls[$i % count($urls)];
}

$err = '';
$queued = 0;
$flushed = 0;
$captureMs = 0;
$flushMs = 0;
$ok = 1;

try {
	$t0 = microtime(true);
	for ($i = 0; $i < $ops; $i++) {
		$_SERVER['REQUEST_URI'] = aud_worker_request_uri($i + $user);
		AuditQueue::capture(aud_worker_sql($i + $user));
	}
	$captureMs = aud_ms($t0);
	$queued = AuditQueue::queueCount();

	if ($mode === 'flush') {
		$t1 = microtime(true);
		AuditQueue::flush();
		$flushMs = aud_ms($t1);
		$flushed = $queued;
		AuditQueue::resetForTests();
	} else {
		AuditQueue::resetForTests();
	}
} catch (Exception $e) {
	$ok = 0;
	$err = $e->getMessage();
	AuditQueue::resetForTests();
}

$out = array(
	'user' => $user,
	'usu' => $usuCod,
	'ok' => $ok,
	'ops' => $ops,
	'queued' => $queued,
	'flushed' => $flushed,
	'capture_ms' => $captureMs,
	'flush_ms' => $flushMs,
	'error' => $err
);
echo json_encode($out);
