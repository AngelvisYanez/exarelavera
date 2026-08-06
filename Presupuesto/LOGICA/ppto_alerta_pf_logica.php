<?php
/**
 * ppto_alerta_pf_logica.php � Alerta D8: PF > VA + umbral (Fase 4).
 *
 * - VA = vigente (exa_ppto_resumen, solo lectura).
 * - PF = vigente_proyectado (motor unico 2B, sin recalcular aqui).
 * - Umbral = VA * (ubp_umbral_pct / 100); alerta si PF > VA + umbral.
 * - NUNCA escribe en exa_ppto_detalles ni modifica VA.
 */

if (!defined('PPTO_UMBRAL_PF_DEFAULT')) {
    define('PPTO_UMBRAL_PF_DEFAULT', 5.0);
}

/**
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int|null $ppa_id
 * @return array {pct: float, origen: string}
 */
function ppto_umbral_pf_resolver($mysqli, $Emp_Cod, $ppa_id = null) {
    $Emp_Cod = (int)$Emp_Cod;
    $pct = PPTO_UMBRAL_PF_DEFAULT;
    $origen = 'global_default';

    $res_g = $mysqli->query("SELECT ubp_umbral_pct FROM pre_umbral_pf
        WHERE Emp_Cod = $Emp_Cod AND ppa_id IS NULL LIMIT 1");
    if ($res_g && ($rg = $res_g->fetch_assoc())) {
        $pct = (float)$rg['ubp_umbral_pct'];
        $origen = 'global_empresa';
    }

    if ($ppa_id !== null && (int)$ppa_id > 0) {
        $ppa_id = (int)$ppa_id;
        $res_p = $mysqli->query("SELECT ubp_umbral_pct FROM pre_umbral_pf
            WHERE Emp_Cod = $Emp_Cod AND ppa_id = $ppa_id LIMIT 1");
        if ($res_p && ($rp = $res_p->fetch_assoc())) {
            $pct = (float)$rp['ubp_umbral_pct'];
            $origen = 'partida';
        }
    }

    return array('pct' => $pct, 'origen' => $origen);
}

/**
 * Evalua una fila partida/capitulo ya enriquecida con PF del motor 2B.
 *
 * @param array $row debe incluir ppa_id, codigo, descripcion, vigente, vigente_proyectado
 * @param float $umbral_pct
 * @param string $umbral_origen
 * @return array|null alerta o null si no aplica
 */
function ppto_alerta_pf_evaluar_fila($row, $umbral_pct, $umbral_origen = 'global_default') {
    $va = isset($row['vigente']) ? (float)$row['vigente'] : 0.0;
    $pf = isset($row['vigente_proyectado']) ? (float)$row['vigente_proyectado'] : $va;

    if ($va <= 0.0001) {
        return null;
    }

    $umbral_monto = round($va * ((float)$umbral_pct / 100.0), 2);
    $tope = round($va + $umbral_monto, 2);
    $exceso = round($pf - $tope, 2);

    if ($pf <= $tope + 0.0001) {
        return null;
    }

    $exceso_pct = round((($pf - $va) / $va) * 100.0, 2);

    return array(
        'ppa_id' => (int)$row['ppa_id'],
        'codigo' => isset($row['codigo']) ? $row['codigo'] : '',
        'descripcion' => isset($row['descripcion']) ? $row['descripcion'] : '',
        'va' => round($va, 2),
        'pf' => round($pf, 2),
        'umbral_pct' => round((float)$umbral_pct, 2),
        'umbral_monto' => $umbral_monto,
        'tope_pf' => $tope,
        'exceso' => $exceso,
        'exceso_pct' => $exceso_pct,
        'umbral_origen' => $umbral_origen,
        'es_grupo' => !empty($row['es_grupo']),
        'es_tonelada' => !empty($row['es_tonelada']),
    );
}

/**
 * URL al modulo de reajustes con partida destino preseleccionada.
 *
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param int $ppa_id
 * @param string|null $proy_id
 * @return string
 */
function ppto_alerta_pf_url_reajuste($Emp_Cod, $ppe_id, $ppa_id, $proy_id = null, $monto = null, $origen = 'd8', $rea_mes = null) {
    $q = array(
        'emp_cod' => (int)$Emp_Cod,
        'ppe_id' => (int)$ppe_id,
        'ppa_id_destino' => (int)$ppa_id,
        'rea_tipo' => 'incremento',
        'embed' => 1,
    );
    if ($proy_id !== null && trim($proy_id) !== '') {
        $q['proy_id'] = trim($proy_id);
    }
    $monto = ($monto !== null) ? (float)$monto : 0.0;
    if ($monto > 0.0001) {
        $q['rea_monto'] = round($monto, 2);
    }
    if ($origen === 'formalizar') {
        $q['origen'] = 'formalizar';
    }
    if ($rea_mes !== null && (int)$rea_mes >= 1 && (int)$rea_mes <= 12) {
        $q['rea_mes'] = (int)$rea_mes;
    }
    return '../FRONT/ppto_reajustes_front.php?' . http_build_query($q);
}

/**
 * Procesa lista de partidas del dashboard y devuelve alertas D8.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param array $partidas
 * @param int $ppe_id
 * @param string|null $proy_id
 * @return array
 */
function ppto_alerta_pf_procesar_partidas($mysqli, $Emp_Cod, $partidas, $ppe_id, $proy_id = null) {
    $alertas = array();
    if (empty($partidas)) {
        return $alertas;
    }

    foreach ($partidas as $row) {
        if (!isset($row['ppa_id'])) {
            continue;
        }
        $umb = ppto_umbral_pf_resolver($mysqli, $Emp_Cod, (int)$row['ppa_id']);
        $ev = ppto_alerta_pf_evaluar_fila($row, $umb['pct'], $umb['origen']);
        if ($ev === null) {
            continue;
        }
        $ev['mensaje'] = sprintf(
            'Proyectado %s supera aprobado %s mas margen %s%% (%s %s). Exceso: %s.',
            number_format($ev['pf'], 2, '.', ','),
            number_format($ev['va'], 2, '.', ','),
            number_format($ev['umbral_pct'], 2, '.', ','),
            $ev['umbral_origen'] === 'partida' ? 'por partida' : 'global',
            number_format($ev['umbral_monto'], 2, '.', ','),
            number_format($ev['exceso'], 2, '.', ',')
        );
        $ev['url_reajuste'] = ppto_alerta_pf_url_reajuste(
            $Emp_Cod,
            $ppe_id,
            $ev['ppa_id'],
            $proy_id,
            isset($ev['exceso']) ? $ev['exceso'] : null,
            'd8'
        );
        $alertas[] = $ev;
    }

    return $alertas;
}

/**
 * Marca filas con flag alerta_d8 (solo lectura en respuesta JSON).
 *
 * @param array $partidas
 * @param array $alertas
 * @return array
 */
function ppto_alerta_pf_marcar_partidas($partidas, $alertas) {
    $por_id = array();
    foreach ($alertas as $a) {
        $por_id[(int)$a['ppa_id']] = $a;
    }
    foreach ($partidas as $k => $row) {
        $pid = isset($row['ppa_id']) ? (int)$row['ppa_id'] : 0;
        $partidas[$k]['alerta_d8'] = isset($por_id[$pid]);
        if (isset($por_id[$pid])) {
            $partidas[$k]['alerta_d8_det'] = $por_id[$pid];
        }
    }
    return $partidas;
}

/**
 * Evalua alerta de formalizacion (derecho por real vs asignado formal).
 * Solo rubros toneladas; no usa vigente_proyectado.
 * Misma regla que KPI/columna: Por formalizar = max(0, derecho - asignado), sin margen PF.
 *
 * @param array $row
 * @param float $umbral_pct ignorado para el umbral de disparo (compat. firma)
 * @param string $umbral_origen
 * @return array|null
 */
function ppto_alerta_reinversion_evaluar_fila($row, $umbral_pct = 0.0, $umbral_origen = 'reinversion') {
    if (empty($row['es_tonelada']) && (!isset($row['driver_tipo']) || $row['driver_tipo'] !== 'driver')) {
        return null;
    }

    $va = isset($row['vigente']) ? (float)$row['vigente'] : 0.0;
    $derecho = isset($row['vigente_por_real']) ? (float)$row['vigente_por_real'] : $va;

    if ($va <= 0.0001 && $derecho <= 0.0001) {
        return null;
    }

    // Alineado al KPI: no se aplica margen % (ese margen es solo para alerta D8 proyectada).
    if (isset($row['por_formalizar'])) {
        $por_formalizar = round(max(0.0, (float)$row['por_formalizar']), 2);
    } else {
        $por_formalizar = round(max(0.0, $derecho - $va), 2);
    }

    if ($por_formalizar <= 0.0001) {
        return null;
    }

    return array(
        'ppa_id' => (int)$row['ppa_id'],
        'codigo' => isset($row['codigo']) ? $row['codigo'] : '',
        'descripcion' => isset($row['descripcion']) ? $row['descripcion'] : '',
        'va' => round($va, 2),
        'derecho' => round($derecho, 2),
        'pf' => round($derecho, 2),
        'umbral_pct' => 0.0,
        'umbral_monto' => 0.0,
        'tope_derecho' => round($va, 2),
        'por_formalizar' => $por_formalizar,
        'exceso' => $por_formalizar,
        'exceso_pct' => ($va > 0.0001) ? round((($derecho - $va) / $va) * 100.0, 2) : 0.0,
        'umbral_origen' => 'reinversion',
        'es_grupo' => !empty($row['es_grupo']),
        'es_tonelada' => !empty($row['es_tonelada']),
    );
}

/**
 * Procesa partidas modo reinversion y devuelve alertas por formalizar.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param array $partidas
 * @param int $ppe_id
 * @param string|null $proy_id
 * @return array
 */
function ppto_alerta_reinversion_procesar_partidas($mysqli, $Emp_Cod, $partidas, $ppe_id, $proy_id = null, $rea_mes = null) {
    $alertas = array();
    if (empty($partidas)) {
        return $alertas;
    }

    foreach ($partidas as $row) {
        if (!isset($row['ppa_id'])) {
            continue;
        }
        // Solo cuentas detalle: los grupos rollup duplican el aviso.
        if (!empty($row['es_grupo'])) {
            continue;
        }
        $ev = ppto_alerta_reinversion_evaluar_fila($row);
        if ($ev === null) {
            continue;
        }
        $ev['mensaje'] = sprintf(
            'Derecho por real %s supera asignado formal %s. Por formalizar: %s.',
            number_format($ev['derecho'], 2, '.', ','),
            number_format($ev['va'], 2, '.', ','),
            number_format($ev['por_formalizar'], 2, '.', ',')
        );
        $ev['url_reajuste'] = ppto_alerta_pf_url_reajuste(
            $Emp_Cod,
            $ppe_id,
            $ev['ppa_id'],
            $proy_id,
            isset($ev['por_formalizar']) ? $ev['por_formalizar'] : null,
            'formalizar',
            $rea_mes
        );
        $alertas[] = $ev;
    }

    return $alertas;
}

/**
 * Marca filas con flag alerta_formalizar (modo reinversion).
 *
 * @param array $partidas
 * @param array $alertas
 * @return array
 */
function ppto_alerta_reinversion_marcar_partidas($partidas, $alertas) {
    $por_id = array();
    foreach ($alertas as $a) {
        $por_id[(int)$a['ppa_id']] = $a;
    }
    foreach ($partidas as $k => $row) {
        $pid = isset($row['ppa_id']) ? (int)$row['ppa_id'] : 0;
        $partidas[$k]['alerta_formalizar'] = isset($por_id[$pid]);
        if (isset($por_id[$pid])) {
            $partidas[$k]['alerta_formalizar_det'] = $por_id[$pid];
        }
    }
    return $partidas;
}
