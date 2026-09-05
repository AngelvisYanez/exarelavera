<?php
/**
 * Pruebas unitarias de la cola de auditoria (sin bloquear el request).
 *
 * @package auditoria.TEST
 */

require_once dirname(__FILE__) . '/aud_test_lib.php';

function aud_run_unit_tests()
{
	$failed = 0;
	$passed = 0;
	echo "\n== Pruebas unitarias AuditQueue ==\n";

	$cases = array(
		'aud_unit_deshabilitada',
		'aud_unit_captura_cualquier_modulo',
		'aud_unit_ignora_tablas_internas',
		'aud_unit_ignora_auditoria',
		'aud_unit_ignora_select',
		'aud_unit_captura_modulos',
		'aud_unit_captura_venta',
		'aud_unit_captura_caja',
		'aud_unit_captura_sin_mysql',
		'aud_unit_flush_silencioso',
		'aud_unit_tope_cola',
		'aud_unit_rendimiento_captura',
		'aud_unit_transaccion_rollback',
		'aud_unit_transaccion_commit',
		'aud_unit_transaccion_anidada'
	);
	foreach ($cases as $fn) {
		try {
			AuditQueue::resetForTests();
			$fn();
			$passed++;
		} catch (Exception $e) {
			$failed++;
			echo "  FAIL  " . $fn . ": " . $e->getMessage() . "\n";
		}
		AuditQueue::resetForTests();
	}

	echo "Unitarias: " . $passed . " ok, " . $failed . " fallidas\n";
	return $failed;
}

function aud_unit_deshabilitada()
{
	aud_test_putenv('AUDIT_ENABLED', 'false');
	AuditQueue::resetForTests();
	aud_assert(AuditQueue::enabled() === false, 'AUDIT_ENABLED=false desactiva la cola');
	AuditQueue::capture("INSERT INTO manifiesto (Man_Est) VALUES ('A')");
	aud_assert(AuditQueue::queueCount() === 0, 'No encola nada si esta desactivada');
	aud_test_putenv('AUDIT_ENABLED', 'true');
	AuditQueue::resetForTests();
	aud_assert(AuditQueue::enabled() === true, 'AUDIT_ENABLED=true activa la cola');
}

function aud_unit_captura_cualquier_modulo()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	AuditQueue::resetForTests();
	AuditQueue::capture("INSERT INTO clientes (Cli_Nom) VALUES ('ACME')");
	AuditQueue::capture("UPDATE facturas SET Fac_Est='A' WHERE Fac_Cod=1");
	AuditQueue::capture("DELETE FROM compras WHERE Com_Cod=1");
	AuditQueue::capture("INSERT INTO rol_pagos (Rol_Est) VALUES ('A')");
	AuditQueue::capture("UPDATE inventario SET Inv_Can=10 WHERE Inv_Cod=1");
	aud_assert(AuditQueue::queueCount() === 5, 'Encola actividad de cualquier modulo; el filtro de config aplica al persistir');
}

function aud_unit_ignora_tablas_internas()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	AuditQueue::resetForTests();
	AuditQueue::capture("INSERT INTO logs (Usu_Cod) VALUES (1)");
	AuditQueue::capture("INSERT INTO sesion (Usu_Cod) VALUES (1)");
	AuditQueue::capture("UPDATE cfg_monitoreo SET Cfg_Est='A' WHERE Cfg_Cod=1");
	AuditQueue::capture("INSERT INTO tmp_work (X) VALUES (1)");
	aud_assert(AuditQueue::queueCount() === 0, 'No encola tablas internas ni temporales');
}

function aud_unit_ignora_auditoria()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	AuditQueue::resetForTests();
	AuditQueue::capture("INSERT INTO `auditoria`.`logs` (Usu_Cod) VALUES (1)");
	aud_assert(AuditQueue::queueCount() === 0, 'No se audita a si misma (auditoria.logs)');
}

function aud_unit_ignora_select()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	AuditQueue::resetForTests();
	AuditQueue::capture("SELECT * FROM manifiesto WHERE Man_Cod=1");
	aud_assert(AuditQueue::queueCount() === 0, 'SELECT no entra a la cola');
}

function aud_unit_captura_modulos()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	AuditQueue::resetForTests();
	aud_session_user(900001);
	$sqls = aud_test_sqls();
	foreach ($sqls as $k => $sql) {
		AuditQueue::capture($sql);
	}
	aud_assert(AuditQueue::queueCount() === count($sqls), 'Encola I/U/D de manifiestos, turnos, visitantes, eventos, contabilidad y ventas');
}

function aud_unit_captura_venta()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	AuditQueue::resetForTests();
	aud_session_user(900001);
	AuditQueue::capture("INSERT INTO ventas (Tic_Cod, Cli_Cod, Vet_Num) VALUES (1, 1, '001-001-000000099')");
	AuditQueue::capture("INSERT INTO ventas_det SET Vet_Cod=99, Pro_Cod=1, Vet_Can=1, Vet_Pru=5.50, Vet_Imp=5.50");
	AuditQueue::capture("UPDATE ventas SET Vet_Obs='ANULADA' WHERE Vet_Cod=99");
	aud_assert(AuditQueue::queueCount() === 3, 'Registrar venta (cabecera, detalle y update) entra a la cola');
}

function aud_unit_captura_caja()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	AuditQueue::resetForTests();
	aud_session_user(900001);
	AuditQueue::capture(" INSERT INTO caja_aper (Pun_Cod, Caj_Fec, Caj_Hoi, Caj_Obs, Caj_Exi, Caj_Est, Caj_Gen) VALUES( 1, '2026-08-17', CURTIME(), 'APERTURA', 50.00, 'A', 'N') ");
	AuditQueue::capture("UPDATE caja_aper SET Caj_Fef = CURDATE() , Caj_Hof = CURTIME(), Caj_Est = 'C'  WHERE Caj_Cod = 9");
	aud_assert(AuditQueue::queueCount() === 2, 'Caja registrar/cuadrar entra a la cola y se persiste sin romper JSON AJAX');
}

function aud_unit_captura_sin_mysql()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	$oldHost = getenv('DB_HOST');
	$oldPort = getenv('DB_PORT');
	aud_test_putenv('DB_HOST', '127.0.0.1');
	aud_test_putenv('DB_PORT', '1');
	AuditQueue::resetForTests();
	$t0 = microtime(true);
	AuditQueue::capture("INSERT INTO manifiesto (Man_Est) VALUES ('A')");
	$ms = aud_ms($t0);
	aud_assert(AuditQueue::queueCount() === 1, 'La captura no necesita MySQL');
	aud_assert($ms < 50, 'Capturar un movimiento tarda menos de 50 ms (fue ' . $ms . ' ms)');
	$threw = false;
	try {
		AuditQueue::flush();
	} catch (Exception $e) {
		$threw = true;
	}
	aud_assert($threw === false, 'Flush con MySQL caido no lanza excepcion al usuario');
	if ($oldHost !== false && $oldHost !== '') {
		aud_test_putenv('DB_HOST', $oldHost);
	} else {
		putenv('DB_HOST=');
	}
	if ($oldPort !== false && $oldPort !== '') {
		aud_test_putenv('DB_PORT', $oldPort);
	} else {
		putenv('DB_PORT=');
	}
}

function aud_unit_flush_silencioso()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	$oldHost = getenv('DB_HOST');
	$oldPort = getenv('DB_PORT');
	aud_test_putenv('DB_HOST', '127.0.0.1');
	aud_test_putenv('DB_PORT', '1');
	AuditQueue::resetForTests();
	AuditQueue::capture("INSERT INTO manifiesto (Man_Est) VALUES ('A')");
	ob_start();
	echo '{"success":true}';
	AuditQueue::flush(false);
	$out = ob_get_clean();
	aud_assert($out === '{"success":true}', 'Flush silencioso no ensucia el JSON (fue: ' . substr($out, 0, 120) . ')');
	aud_assert(AuditQueue::queueCount() === 0, 'Flush silencioso vacia la cola');
	if ($oldHost !== false && $oldHost !== '') {
		aud_test_putenv('DB_HOST', $oldHost);
	} else {
		putenv('DB_HOST=');
	}
	if ($oldPort !== false && $oldPort !== '') {
		aud_test_putenv('DB_PORT', $oldPort);
	} else {
		putenv('DB_PORT=');
	}
}

function aud_unit_tope_cola()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	aud_test_putenv('AUDIT_MAX_QUEUE', '20');
	AuditQueue::resetForTests();
	for ($i = 0; $i < 35; $i++) {
		AuditQueue::capture("INSERT INTO clientes (Cli_Nom) VALUES ('U".$i."')");
	}
	aud_assert(AuditQueue::queueCount() === 20, 'El tope AUDIT_MAX_QUEUE descarta eventos extra (fue ' . AuditQueue::queueCount() . ')');
	putenv('AUDIT_MAX_QUEUE=');
}

function aud_unit_rendimiento_captura()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	aud_test_putenv('AUDIT_MAX_QUEUE', '500');
	AuditQueue::resetForTests();
	$n = 200;
	$t0 = microtime(true);
	for ($i = 0; $i < $n; $i++) {
		AuditQueue::capture(aud_test_cycle_sql($i));
	}
	$ms = aud_ms($t0);
	$avg = $ms / $n;
	aud_assert(AuditQueue::queueCount() === $n, '200 movimientos quedan en memoria');
	aud_assert($avg < 5, 'Promedio de captura < 5 ms (fue ' . round($avg, 3) . ' ms)');
	aud_assert($ms < 800, '200 capturas < 800 ms (fue ' . $ms . ' ms)');
	echo "       metricas captura: total=" . $ms . " ms, promedio=" . round($avg, 3) . " ms\n";
	putenv('AUDIT_MAX_QUEUE=');
}

function aud_unit_transaccion_rollback()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	AuditQueue::resetForTests();
	aud_assert(!AuditQueue::inTransaction(), 'No inicia en transaccion');
	AuditQueue::beginTransaction();
	aud_assert(AuditQueue::inTransaction(), 'inTransaction es true tras beginTransaction');
	AuditQueue::capture("INSERT INTO clientes (Cli_Nom) VALUES ('TEMP')");
	AuditQueue::capture("UPDATE facturas SET Fac_Est='A' WHERE Fac_Cod=1");
	aud_assert(AuditQueue::queueCount() === 0, 'Eventos en transaccion no estan en cola publica');
	aud_assert(AuditQueue::stagedCount() === 2, 'Staging contiene 2 eventos');
	AuditQueue::rollback();
	aud_assert(!AuditQueue::inTransaction(), 'inTransaction es false tras rollback');
	aud_assert(AuditQueue::queueCount() === 0, 'Rollback descarta eventos en cola');
	aud_assert(AuditQueue::stagedCount() === 0, 'Rollback vacia staging');
}

function aud_unit_transaccion_commit()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	AuditQueue::resetForTests();
	AuditQueue::beginTransaction();
	AuditQueue::capture("INSERT INTO clientes (Cli_Nom) VALUES ('COMMIT1')");
	AuditQueue::capture("UPDATE facturas SET Fac_Est='A' WHERE Fac_Cod=2");
	aud_assert(AuditQueue::queueCount() === 0, 'Eventos en transaccion esperan en staging');
	aud_assert(AuditQueue::stagedCount() === 2, 'Staging contiene 2 eventos');
	AuditQueue::commit();
	aud_assert(!AuditQueue::inTransaction(), 'inTransaction es false tras commit');
	aud_assert(AuditQueue::queueCount() === 2, 'Commit promueve eventos a la cola principal');
	aud_assert(AuditQueue::stagedCount() === 0, 'Staging vacio tras commit');
}

function aud_unit_transaccion_anidada()
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	AuditQueue::resetForTests();
	AuditQueue::beginTransaction();
	AuditQueue::capture("INSERT INTO clientes (Cli_Nom) VALUES ('N1')");
	AuditQueue::beginTransaction();
	AuditQueue::capture("INSERT INTO clientes (Cli_Nom) VALUES ('N2')");
	aud_assert(AuditQueue::stagedCount() === 2, 'Ambos niveles en staging');
	AuditQueue::rollback();
	aud_assert(AuditQueue::inTransaction(), 'Nivel padre sigue activo');
	aud_assert(AuditQueue::stagedCount() === 1, 'Hijo descartado, queda N1');
	AuditQueue::commit();
	aud_assert(!AuditQueue::inTransaction(), 'Transaccion finalizada');
	aud_assert(AuditQueue::queueCount() === 1, 'Solo N1 llego a la cola principal');
}
