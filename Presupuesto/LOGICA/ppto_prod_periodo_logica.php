<?php
/**
 * ppto_prod_periodo_logica.php
 * Cierre, reapertura y correccion sync sobre periodos de produccion (Fase 2A).
 */

require_once __DIR__ . '/../../contabilidad/LOGICA/con_log_balances2.php';
require_once __DIR__ . '/../VALIDACIONES/ppto_prod_validaciones.php';
require_once __DIR__ . '/ppto_persistencia_logica.php';
require_once __DIR__ . '/dashboard_logica.php';

/**
 * Snapshot de presupuesto forecast (PF) usando motor actual de dashboard.
 * pel_motor_version 'v2' = motor unico Fase 2B (ton_ref por prd_estado).
 * Filas historicas v1_legacy conservadas en evento_log pre-2B.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $emp_id
 * @param int $anio
 * @param int $mes
 * @return array {pf: float, motor: string}
 */
function ppto_prod_pf_snapshot($mysqli, $proy_id, $emp_id, $anio, $mes) {
    $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('emp_id' => (int)$emp_id, 'ppe_anio' => (int)$anio));
    $filtros = array(
        'emp_id'  => (int)$emp_id,
        'anio'    => (int)$anio,
        'mes'     => (int)$mes,
        'ppe_id'  => $ppe_id ? (int)$ppe_id : null,
        'proy_id' => $proy_id,
        'ppa_id'  => null,
    );
    $kpis = ppto_dash_kpis($mysqli, $filtros);
    return array(
        'pf'     => isset($kpis['presupuesto_proyectado']) ? (float)$kpis['presupuesto_proyectado'] : 0.0,
        'motor'  => 'v2',
    );
}

/**
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $emp_id
 * @param int $anio
 * @param int $mes
 * @param bool $for_update
 * @return array|null
 */
function ppto_prod_periodo_obtener_fila($mysqli, $proy_id, $emp_id, $anio, $mes, $for_update = false) {
    $clean = $mysqli->real_escape_string(trim($proy_id));
    $lock = $for_update ? ' FOR UPDATE' : '';
    $sql = "SELECT * FROM exa_ppto_prod_periodos
            WHERE proy_id='$clean' AND emp_id=" . (int)$emp_id . "
              AND prd_anio=" . (int)$anio . " AND prd_mes=" . (int)$mes . "
            LIMIT 1" . $lock;
    $res = $mysqli->query($sql);
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    return null;
}

/**
 * @param Class_Log_Datos_Con $obDatos
 * @param mysqli $mysqli
 * @param string $sql
 * @return bool
 */
function ppto_prod_periodo_ejecutar_audit($obDatos, $mysqli, $sql) {
    $ok = $obDatos->grabarv_registros($sql, $mysqli);
    if ($ok) {
        $obDatos->sentencias .= $sql . '*';
        $obDatos->codigos .= '0*';
    }
    return (bool)$ok;
}

/**
 * @param Class_Log_Datos_Con $obDatos
 * @param string|null $Ses_Dat_Aut
 * @param string $request_uri
 * @param int $usu_id
 * @return void
 */
function ppto_prod_periodo_guardar_auditoria($obDatos, $Ses_Dat_Aut, $request_uri, $usu_id) {
    if (empty($Ses_Dat_Aut) || trim($obDatos->sentencias) === '') {
        return;
    }
    $Ses_Dat_Dis = null;
    if (isset($_SESSION['Ses_Dat_Dis']) && $_SESSION['Ses_Dat_Dis'] !== '') {
        $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
    }
    if (empty($Ses_Dat_Dis)) {
        return;
    }
    if (!class_exists('Class_Log_Conexion')) {
        $aud_mon = __DIR__ . '/../../auditoria/LOGICA/aud_log_monitoreo.php';
        if (file_exists($aud_mon)) {
            require_once $aud_mon;
        }
    }
    if (!class_exists('Class_Log_Conexion')) {
        return;
    }
    $obBD_audit = new Class_Log_Conexion($Ses_Dat_Aut);
    $obDatos->saveAuditoria($Ses_Dat_Dis, $request_uri, (int)$usu_id, $obBD_audit);
}

/**
 * @param mysqli $mysqli
 * @param Class_Log_Datos_Con $obDatos
 * @param string $proy_id
 * @param int $emp_id
 * @param int $anio
 * @param int $mes
 * @param string $tipo
 * @param string $origen
 * @param int $usu_id
 * @param float $real_antes
 * @param float $real_despues
 * @param float $pf_antes
 * @param float $pf_despues
 * @param string|null $motivo
 * @param string $motor_version
 * @return bool
 */
function ppto_prod_periodo_insert_evento_log($mysqli, $obDatos, $proy_id, $emp_id, $anio, $mes,
    $tipo, $origen, $usu_id, $real_antes, $real_despues, $pf_antes, $pf_despues, $motivo, $motor_version) {

    $clean_proy = $mysqli->real_escape_string(trim($proy_id));
    $clean_tipo = $mysqli->real_escape_string($tipo);
    $clean_origen = $mysqli->real_escape_string($origen);
    $clean_motivo = $motivo !== null ? "'" . $mysqli->real_escape_string($motivo) . "'" : 'NULL';
    $clean_motor = $mysqli->real_escape_string($motor_version);

    $sql = "INSERT INTO exa_ppto_prod_evento_log
            (proy_id, emp_id, pel_anio, pel_mes, pel_tipo, pel_origen, pel_usu_id, pel_fecha,
             pel_real_antes, pel_real_despues, pel_pf_antes, pel_pf_despues, pel_motivo, pel_motor_version)
            VALUES
            ('$clean_proy', " . (int)$emp_id . ", " . (int)$anio . ", " . (int)$mes . ",
             '$clean_tipo', '$clean_origen', " . (int)$usu_id . ", NOW(),
             " . (float)$real_antes . ", " . (float)$real_despues . ",
             " . (float)$pf_antes . ", " . (float)$pf_despues . ",
             $clean_motivo, '$clean_motor')";
    return ppto_prod_periodo_ejecutar_audit($obDatos, $mysqli, $sql);
}

/**
 * Cierra un periodo de produccion.
 *
 * @return array {ok: bool, message: string}
 */
function ppto_prod_periodo_cerrar($mysqli, $obDatos, $proy_id, $emp_id, $anio, $mes, $usu_id, $real_val, $Ses_Dat_Aut, $request_uri) {
    $val = ppto_prod_validar_cerrar(array(
        'proy_id'  => $proy_id,
        'anio'     => $anio,
        'mes'      => $mes,
        'real_val' => $real_val,
    ));
    if (!$val['ok']) {
        return $val;
    }

    $real_val = (float)$real_val;
    $clean_proy = $mysqli->real_escape_string(trim($proy_id));
    $obDatos->sentencias = '';
    $obDatos->codigos = '';

    $obDatos->inicio_transaccion($mysqli);

    $fila = ppto_prod_periodo_obtener_fila($mysqli, $proy_id, $emp_id, $anio, $mes, true);
    if ($fila && isset($fila['prd_estado']) && $fila['prd_estado'] === 'cerrado') {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => true, 'message' => 'Periodo ya estaba cerrado.');
    }

    $pf_antes = ppto_prod_pf_snapshot($mysqli, $proy_id, $emp_id, $anio, $mes);

    if ($fila) {
        $sql = "UPDATE exa_ppto_prod_periodos
                SET prd_real = $real_val,
                    prd_estado = 'cerrado',
                    prd_fecha_cierre = NOW(),
                    prd_fecha_registro = NOW(),
                    usu_id = " . (int)$usu_id . "
                WHERE proy_id='$clean_proy' AND emp_id=" . (int)$emp_id . "
                  AND prd_anio=" . (int)$anio . " AND prd_mes=" . (int)$mes;
        if (!ppto_prod_periodo_ejecutar_audit($obDatos, $mysqli, $sql)) {
            $obDatos->fin_transaccion($mysqli, 0);
            return array('ok' => false, 'message' => 'No se pudo cerrar el periodo: ' . $mysqli->error);
        }
    } else {
        $sql = "INSERT INTO exa_ppto_prod_periodos
                (proy_id, emp_id, prd_anio, prd_mes, prd_esperada, prd_real, prd_proyectada,
                 prd_estado, prd_fecha_cierre, prd_fecha_registro, usu_id)
                VALUES
                ('$clean_proy', " . (int)$emp_id . ", " . (int)$anio . ", " . (int)$mes . ",
                 0, $real_val, 0, 'cerrado', NOW(), NOW(), " . (int)$usu_id . ")";
        if (!ppto_prod_periodo_ejecutar_audit($obDatos, $mysqli, $sql)) {
            $obDatos->fin_transaccion($mysqli, 0);
            return array('ok' => false, 'message' => 'No se pudo crear y cerrar el periodo: ' . $mysqli->error);
        }
    }

    $inv = ppto_prod_validar_invariante_cierre('cerrado', date('Y-m-d H:i:s'));
    if (!$inv['ok']) {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => false, 'message' => $inv['message']);
    }

    ppto_prod_periodo_guardar_auditoria($obDatos, $Ses_Dat_Aut, $request_uri, $usu_id);

    if (!$obDatos->fin_transaccion($mysqli, 0)) {
        return array('ok' => false, 'message' => 'Error al confirmar cierre de periodo.');
    }

    if (function_exists('ppto_integracion_variacion_calcular')) {
        ppto_integracion_variacion_calcular($mysqli, $proy_id, $mes, $anio);
    }

    return array('ok' => true, 'message' => 'Periodo cerrado correctamente.', 'pf_antes' => $pf_antes['pf']);
}

/**
 * Reabre un periodo cerrado.
 *
 * @return array {ok: bool, message: string}
 */
function ppto_prod_periodo_reabrir($mysqli, $obDatos, $proy_id, $emp_id, $anio, $mes, $usu_id, $motivo, $Ses_Dat_Aut, $request_uri) {
    $obDatos->sentencias = '';
    $obDatos->codigos = '';
    $obDatos->inicio_transaccion($mysqli);

    $fila = ppto_prod_periodo_obtener_fila($mysqli, $proy_id, $emp_id, $anio, $mes, true);
    $val = ppto_prod_validar_reabrir(array(
        'proy_id' => $proy_id,
        'mes'     => $mes,
        'motivo'  => $motivo,
    ), $fila);
    if (!$val['ok']) {
        $obDatos->fin_transaccion($mysqli, 0);
        return $val;
    }

    $real_antes = (float)$fila['prd_real'];
    $pf_antes = ppto_prod_pf_snapshot($mysqli, $proy_id, $emp_id, $anio, $mes);
    $clean_proy = $mysqli->real_escape_string(trim($proy_id));

    $sql = "UPDATE exa_ppto_prod_periodos
            SET prd_estado = 'en_curso',
                prd_fecha_cierre = NULL,
                prd_fecha_registro = NOW(),
                usu_id = " . (int)$usu_id . "
            WHERE proy_id='$clean_proy' AND emp_id=" . (int)$emp_id . "
              AND prd_anio=" . (int)$anio . " AND prd_mes=" . (int)$mes;
    if (!ppto_prod_periodo_ejecutar_audit($obDatos, $mysqli, $sql)) {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => false, 'message' => 'No se pudo reabrir el periodo: ' . $mysqli->error);
    }

    $pf_despues = ppto_prod_pf_snapshot($mysqli, $proy_id, $emp_id, $anio, $mes);

    if (!ppto_prod_periodo_insert_evento_log(
        $mysqli, $obDatos, $proy_id, $emp_id, $anio, $mes,
        'reapertura', 'manual', $usu_id,
        $real_antes, $real_antes,
        $pf_antes['pf'], $pf_despues['pf'],
        $motivo, $pf_antes['motor']
    )) {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => false, 'message' => 'No se pudo registrar evento de reapertura.');
    }

    ppto_prod_periodo_guardar_auditoria($obDatos, $Ses_Dat_Aut, $request_uri, $usu_id);

    if (!$obDatos->fin_transaccion($mysqli, 0)) {
        return array('ok' => false, 'message' => 'Error al confirmar reapertura.');
    }

    return array('ok' => true, 'message' => 'Periodo reabierto correctamente.');
}

/**
 * Corrige real en periodo cerrado (sync tardio). No cambia prd_estado.
 *
 * @return array {ok: bool, message: string}
 */
function ppto_prod_periodo_corregir_real_cerrado($mysqli, $obDatos, $proy_id, $emp_id, $anio, $mes, $nuevo_real, $origen, $usu_id, $Ses_Dat_Aut, $request_uri) {
    $nuevo_real = (float)$nuevo_real;
    $origen = ($origen === 'sync') ? 'sync' : 'manual';

    $obDatos->sentencias = '';
    $obDatos->codigos = '';
    $obDatos->inicio_transaccion($mysqli);

    $fila = ppto_prod_periodo_obtener_fila($mysqli, $proy_id, $emp_id, $anio, $mes, true);
    $val = ppto_prod_validar_corregir_sync(array(
        'proy_id'     => $proy_id,
        'nuevo_real'  => $nuevo_real,
    ), $fila);
    if (!$val['ok']) {
        $obDatos->fin_transaccion($mysqli, 0);
        return $val;
    }

    $real_antes = (float)$fila['prd_real'];
    if (abs($real_antes - $nuevo_real) < 0.0001) {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => true, 'message' => 'Sin cambios en valor real.');
    }

    $pf_antes = ppto_prod_pf_snapshot($mysqli, $proy_id, $emp_id, $anio, $mes);
    $clean_proy = $mysqli->real_escape_string(trim($proy_id));

    $sql = "UPDATE exa_ppto_prod_periodos
            SET prd_real = $nuevo_real,
                prd_fecha_registro = NOW(),
                usu_id = " . (int)$usu_id . "
            WHERE proy_id='$clean_proy' AND emp_id=" . (int)$emp_id . "
              AND prd_anio=" . (int)$anio . " AND prd_mes=" . (int)$mes . "
              AND prd_estado = 'cerrado'";
    if (!ppto_prod_periodo_ejecutar_audit($obDatos, $mysqli, $sql)) {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => false, 'message' => 'No se pudo corregir el real: ' . $mysqli->error);
    }

    $pf_despues = ppto_prod_pf_snapshot($mysqli, $proy_id, $emp_id, $anio, $mes);

    if (!ppto_prod_periodo_insert_evento_log(
        $mysqli, $obDatos, $proy_id, $emp_id, $anio, $mes,
        'correccion_sync_cerrado', $origen, $usu_id,
        $real_antes, $nuevo_real,
        $pf_antes['pf'], $pf_despues['pf'],
        'Sync tardio sobre periodo cerrado', $pf_antes['motor']
    )) {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => false, 'message' => 'No se pudo registrar evento de correccion.');
    }

    ppto_prod_periodo_guardar_auditoria($obDatos, $Ses_Dat_Aut, $request_uri, $usu_id);

    if (!$obDatos->fin_transaccion($mysqli, 0)) {
        return array('ok' => false, 'message' => 'Error al confirmar correccion.');
    }

    if (function_exists('ppto_integracion_variacion_calcular')) {
        ppto_integracion_variacion_calcular($mysqli, $proy_id, $mes, $anio);
    }

    return array('ok' => true, 'message' => 'Real corregido en periodo cerrado.');
}
