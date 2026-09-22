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
$empCod = 999000 + $user;
aud_session_user($usuCod, $empCod);
AuditQueue::resetForTests();

$err = '';
$queued = 0;
$flushed = 0;
$captureMs = 0;
$flushMs = 0;
$ok = 1;

try {
	$t0 = microtime(true);
	for ($i = 0; $i < $ops; $i++) {
		AuditQueue::capture(aud_test_cycle_sql($i + $user));
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
