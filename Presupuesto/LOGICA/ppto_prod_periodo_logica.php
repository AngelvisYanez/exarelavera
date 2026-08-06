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
 * @param int $Emp_Cod
 * @param int $anio
 * @param int $mes
 * @return array {pf: float, motor: string}
 */
function ppto_prod_pf_snapshot($mysqli, $proy_id, $Emp_Cod, $anio, $mes) {
    $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => (int)$Emp_Cod, 'ppe_anio' => (int)$anio));
    $filtros = array(
        'Emp_Cod'  => (int)$Emp_Cod,
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
 * @param int $Emp_Cod
 * @param int $anio
 * @param int $mes
 * @param bool $for_update
 * @return array|null
 */
function ppto_prod_periodo_obtener_fila($mysqli, $proy_id, $Emp_Cod, $anio, $mes, $for_update = false) {
    $clean = $mysqli->real_escape_string(trim($proy_id));
    $lock = $for_update ? ' FOR UPDATE' : '';
    $sql = "SELECT Prd_Cod AS prd_id, Pro_Cod AS proy_id, Emp_Cod, Prd_Anio AS prd_anio, Prd_Mes AS prd_mes,
            Prd_Esperada AS prd_esperada, Prd_Real AS prd_real, Prd_Proyectada AS prd_proyectada, Prd_Est AS prd_estado,
            Prd_FecCierre AS prd_fecha_cierre
            FROM pre_prod_periodos
            WHERE (Pro_Cod='$clean' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean')) AND Emp_Cod=" . (int)$Emp_Cod . "
              AND Prd_Anio=" . (int)$anio . " AND Prd_Mes=" . (int)$mes . "
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
 * @param int $Usu_Cod
 * @return void
 */
function ppto_prod_periodo_guardar_auditoria($obDatos, $Ses_Dat_Aut, $request_uri, $Usu_Cod) {
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
    $obDatos->saveAuditoria($Ses_Dat_Dis, $request_uri, (int)$Usu_Cod, $obBD_audit);
}

/**
 * @param mysqli $mysqli
 * @param Class_Log_Datos_Con $obDatos
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $anio
 * @param int $mes
 * @param string $tipo
 * @param string $origen
 * @param int $Usu_Cod
 * @param float $real_antes
 * @param float $real_despues
 * @param float $pf_antes
 * @param float $pf_despues
 * @param string|null $motivo
 * @param string $motor_version
 * @return bool
 */
function ppto_prod_periodo_insert_evento_log($mysqli, $obDatos, $proy_id, $Emp_Cod, $anio, $mes,
    $tipo, $origen, $Usu_Cod, $real_antes, $real_despues, $pf_antes, $pf_despues, $motivo, $motor_version) {

    $clean_proy = $mysqli->real_escape_string(trim($proy_id));
    $clean_tipo = $mysqli->real_escape_string($tipo);
    $clean_origen = $mysqli->real_escape_string($origen);
    $clean_motivo = $motivo !== null ? "'" . $mysqli->real_escape_string($motivo) . "'" : 'NULL';
    $clean_motor = $mysqli->real_escape_string($motor_version);

    $sql = "INSERT INTO pre_prod_evento_log
            (Pro_Cod, Emp_Cod, Pel_Anio, Pel_Mes, Pel_Tipo, Pel_Origen, Usu_Cod, Pel_FecReg,
             Pel_RealAntes, Pel_RealDespues, Pel_PfAntes, Pel_PfDespues, Pel_Motivo, Pel_MotorVersion)
            VALUES
            (COALESCE((SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy' OR Pro_Cod='$clean_proy' LIMIT 1), 0), " . (int)$Emp_Cod . ", " . (int)$anio . ", " . (int)$mes . ",
             '$clean_tipo', '$clean_origen', " . (int)$Usu_Cod . ", NOW(),
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
function ppto_prod_periodo_cerrar($mysqli, $obDatos, $proy_id, $Emp_Cod, $anio, $mes, $Usu_Cod, $real_val, $Ses_Dat_Aut, $request_uri) {
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

    $fila = ppto_prod_periodo_obtener_fila($mysqli, $proy_id, $Emp_Cod, $anio, $mes, true);
    if ($fila && isset($fila['prd_estado']) && $fila['prd_estado'] === 'cerrado') {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => true, 'message' => 'Periodo ya estaba cerrado.');
    }

    $pf_antes = ppto_prod_pf_snapshot($mysqli, $proy_id, $Emp_Cod, $anio, $mes);

    if ($fila) {
        $sql = "UPDATE pre_prod_periodos
                SET Prd_Real = $real_val,
                    Prd_Est = 'cerrado',
                    Prd_FecCierre = NOW(),
                    Prd_FecReg = NOW(),
                    Usu_Cod = " . (int)$Usu_Cod . "
                WHERE (Pro_Cod='$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Emp_Cod=" . (int)$Emp_Cod . "
                  AND Prd_Anio=" . (int)$anio . " AND Prd_Mes=" . (int)$mes;
        if (!ppto_prod_periodo_ejecutar_audit($obDatos, $mysqli, $sql)) {
            $obDatos->fin_transaccion($mysqli, 0);
            return array('ok' => false, 'message' => 'No se pudo cerrar el periodo: ' . $mysqli->error);
        }
    } else {
        $sql = "INSERT INTO pre_prod_periodos
                (Pro_Cod, Emp_Cod, Prd_Anio, Prd_Mes, Prd_Esperada, Prd_Real, Prd_Proyectada,
                 Prd_Est, Prd_FecCierre, Prd_FecReg, Usu_Cod)
                VALUES
                (COALESCE((SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy' OR Pro_Cod='$clean_proy' LIMIT 1), 0), " . (int)$Emp_Cod . ", " . (int)$anio . ", " . (int)$mes . ",
                 0, $real_val, 0, 'cerrado', NOW(), NOW(), " . (int)$Usu_Cod . ")";
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

    ppto_prod_periodo_guardar_auditoria($obDatos, $Ses_Dat_Aut, $request_uri, $Usu_Cod);

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
function ppto_prod_periodo_reabrir($mysqli, $obDatos, $proy_id, $Emp_Cod, $anio, $mes, $Usu_Cod, $motivo, $Ses_Dat_Aut, $request_uri) {
    $obDatos->sentencias = '';
    $obDatos->codigos = '';
    $obDatos->inicio_transaccion($mysqli);

    $fila = ppto_prod_periodo_obtener_fila($mysqli, $proy_id, $Emp_Cod, $anio, $mes, true);
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
    $pf_antes = ppto_prod_pf_snapshot($mysqli, $proy_id, $Emp_Cod, $anio, $mes);
    $clean_proy = $mysqli->real_escape_string(trim($proy_id));

    $sql = "UPDATE pre_prod_periodos
            SET Prd_Est = 'en_curso',
                Prd_FecCierre = NULL,
                Prd_FecReg = NOW(),
                Usu_Cod = " . (int)$Usu_Cod . "
            WHERE (Pro_Cod='$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Emp_Cod=" . (int)$Emp_Cod . "
              AND Prd_Anio=" . (int)$anio . " AND Prd_Mes=" . (int)$mes;
    if (!ppto_prod_periodo_ejecutar_audit($obDatos, $mysqli, $sql)) {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => false, 'message' => 'No se pudo reabrir el periodo: ' . $mysqli->error);
    }

    $pf_despues = ppto_prod_pf_snapshot($mysqli, $proy_id, $Emp_Cod, $anio, $mes);

    if (!ppto_prod_periodo_insert_evento_log(
        $mysqli, $obDatos, $proy_id, $Emp_Cod, $anio, $mes,
        'reapertura', 'manual', $Usu_Cod,
        $real_antes, $real_antes,
        $pf_antes['pf'], $pf_despues['pf'],
        $motivo, $pf_antes['motor']
    )) {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => false, 'message' => 'No se pudo registrar evento de reapertura.');
    }

    ppto_prod_periodo_guardar_auditoria($obDatos, $Ses_Dat_Aut, $request_uri, $Usu_Cod);

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
function ppto_prod_periodo_corregir_real_cerrado($mysqli, $obDatos, $proy_id, $Emp_Cod, $anio, $mes, $nuevo_real, $origen, $Usu_Cod, $Ses_Dat_Aut, $request_uri) {
    $nuevo_real = (float)$nuevo_real;
    $origen = ($origen === 'sync') ? 'sync' : 'manual';

    $obDatos->sentencias = '';
    $obDatos->codigos = '';
    $obDatos->inicio_transaccion($mysqli);

    $fila = ppto_prod_periodo_obtener_fila($mysqli, $proy_id, $Emp_Cod, $anio, $mes, true);
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

    $pf_antes = ppto_prod_pf_snapshot($mysqli, $proy_id, $Emp_Cod, $anio, $mes);
    $clean_proy = $mysqli->real_escape_string(trim($proy_id));

    $sql = "UPDATE pre_prod_periodos
            SET Prd_Real = $nuevo_real,
                Prd_FecReg = NOW(),
                Usu_Cod = " . (int)$Usu_Cod . "
            WHERE (Pro_Cod='$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Emp_Cod=" . (int)$Emp_Cod . "
              AND Prd_Anio=" . (int)$anio . " AND Prd_Mes=" . (int)$mes . "
              AND Prd_Est = 'cerrado'";
    if (!ppto_prod_periodo_ejecutar_audit($obDatos, $mysqli, $sql)) {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => false, 'message' => 'No se pudo corregir el real: ' . $mysqli->error);
    }

    $pf_despues = ppto_prod_pf_snapshot($mysqli, $proy_id, $Emp_Cod, $anio, $mes);

    if (!ppto_prod_periodo_insert_evento_log(
        $mysqli, $obDatos, $proy_id, $Emp_Cod, $anio, $mes,
        'correccion_sync_cerrado', $origen, $Usu_Cod,
        $real_antes, $nuevo_real,
        $pf_antes['pf'], $pf_despues['pf'],
        'Sync tardio sobre periodo cerrado', $pf_antes['motor']
    )) {
        $obDatos->fin_transaccion($mysqli, 0);
        return array('ok' => false, 'message' => 'No se pudo registrar evento de correccion.');
    }

    ppto_prod_periodo_guardar_auditoria($obDatos, $Ses_Dat_Aut, $request_uri, $Usu_Cod);

    if (!$obDatos->fin_transaccion($mysqli, 0)) {
        return array('ok' => false, 'message' => 'Error al confirmar correccion.');
    }

    if (function_exists('ppto_integracion_variacion_calcular')) {
        ppto_integracion_variacion_calcular($mysqli, $proy_id, $mes, $anio);
    }

    return array('ok' => true, 'message' => 'Real corregido en periodo cerrado.');
}
