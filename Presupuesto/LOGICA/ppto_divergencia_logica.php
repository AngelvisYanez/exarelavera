<?php
/**
 * ppto_divergencia_logica.php - Alerta D2: prd_proyectada vs prd_real (Idea A).
 *
 * Semantica (flujo aprobacion mes a mes):
 * - pre_prod_periodos.prd_proyectada = toneladas proyectadas con las que se
 *   aprueba el presupuesto en $ del mes (proyectada x $/Ton).
 * - pre_prod_periodos.prd_real = toneladas reales al cerrar el mes.
 * Comparacion: SUM(prd_proyectada) vs SUM(prd_real) sobre meses con real > 0.
 * Si el real supera a la proyectada, la proyectada se quedo corta y debe
 * reajustarse para cumplir las partidas presupuestarias.
 */

if (!defined('PPTO_DIVERGENCIA_UMBRAL_DEFAULT')) {
    define('PPTO_DIVERGENCIA_UMBRAL_DEFAULT', 5.0);
}

require_once(__DIR__ . '/ppto_proyecto_version_logica.php');

/**
 * Rubro driver de volumen de produccion para D2.
 * Prioridad: nombre relav/procesad > primer rubro PDF (ton>0, factor>0) > fallback.
 */
function ppto_divergencia_obtener_rubro_driver($mysqli, $proy_id, $Emp_Cod, $ppe_id = null) {
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $sql = "SELECT d.Pdp_Cod AS pdp_id, d.Pdp_Rubro AS pdp_rubro, d.Pdp_TonBase AS pdp_toneladas_base, d.Pdp_FacAnualTon AS pdp_factor_anual_tonelada,
                   p.Ppa_Cla AS ppa_codigo_clasificacion, COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') AS ppa_clase
            FROM pre_proyecto_detalles d
            INNER JOIN pre_partidas p ON (p.Ppa_Cod = d.Ppa_Cod OR p.Ppa_Cod = d.ppa_id) AND p.Emp_Cod = d.Emp_Cod
            WHERE (d.Pro_Cod = '$esc' OR d.proy_id = '$esc') AND d.Emp_Cod = $Emp_Cod
              AND COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') = 'D'";
    if ($ppe_id !== null && (int)$ppe_id > 0) {
        $sql .= ' AND d.ppe_id = ' . (int)$ppe_id;
    }
    $sql .= " ORDER BY p.ppa_codigo_clasificacion ASC, d.pdp_id ASC";
    $res = $mysqli->query($sql);
    if (!$res) {
        return null;
    }

    $filas = array();
    while ($row = $res->fetch_assoc()) {
        $filas[] = $row;
    }
    if (empty($filas)) {
        return null;
    }

    foreach ($filas as $row) {
        $rubro = strtolower(trim($row['pdp_rubro']));
        if (strpos($rubro, 'relav') !== false || strpos($rubro, 'procesad') !== false) {
            return ppto_divergencia_format_driver_row($row);
        }
    }

    foreach ($filas as $row) {
        if ((float)$row['pdp_toneladas_base'] > 0.0001 && (float)$row['pdp_factor_anual_tonelada'] > 0.0001) {
            return ppto_divergencia_format_driver_row($row);
        }
    }

    return ppto_divergencia_format_driver_row($filas[0]);
}

/**
 * @param array $row
 * @return array
 */
function ppto_divergencia_format_driver_row($row) {
    return array(
        'pdp_id' => (int)$row['pdp_id'],
        'pdp_rubro' => $row['pdp_rubro'],
        'pdp_toneladas_base' => (float)$row['pdp_toneladas_base'],
        'ppa_codigo_clasificacion' => isset($row['ppa_codigo_clasificacion']) ? $row['ppa_codigo_clasificacion'] : '',
    );
}

/**
 * Compara la proyectada contra el real de los meses ya cerrados (con real > 0).
 *
 * Semantica nueva (flujo Idea A - aprobacion mes a mes):
 * - prd_proyectada = base con la que se aprueba el presupuesto en $ de cada mes.
 * - prd_real = produccion realmente ocurrida al cerrar el mes.
 * Si el real supera a la proyectada, la proyectada (y por tanto el presupuesto
 * aprobado) se quedo corta y debe reajustarse para cubrir los rubros.
 * Solo se comparan meses con real > 0 (cerrados / con dato real).
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $anio
 * @param int|null $ppe_id
 * @param float|null $umbral_pct default 5%
 * @return array
 */
function ppto_divergencia_comparar_toneladas($mysqli, $proy_id, $Emp_Cod, $anio, $ppe_id = null, $umbral_pct = null) {
    if ($umbral_pct === null) {
        $umbral_pct = PPTO_DIVERGENCIA_UMBRAL_DEFAULT;
    }
    $umbral_pct = (float)$umbral_pct;
    $anio = (int)$anio;
    $Emp_Cod = (int)$Emp_Cod;

    $driver = ppto_divergencia_obtener_rubro_driver($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    $ton_base_mes = 0.0;
    $rubro_ref = '';
    if ($ppe_id !== null && (int)$ppe_id > 0) {
        $ton_pv = ppto_proy_version_ton_base($mysqli, $proy_id, $Emp_Cod, (int)$ppe_id);
        if ($ton_pv > 0.0001) {
            $ton_base_mes = $ton_pv;
            $rubro_ref = 'Base PDF proyecto/version';
        }
    }
    if ($ton_base_mes <= 0.0001 && $driver !== null) {
        $ton_base_mes = (float)$driver['pdp_toneladas_base'];
        $rubro_ref = $driver['pdp_rubro'];
    }
    if ($rubro_ref === '' && $driver !== null) {
        $rubro_ref = $driver['pdp_rubro'];
    }

    $pdp_id_ref = ($driver !== null) ? (int)$driver['pdp_id'] : null;
    $partida_cod = ($driver !== null && isset($driver['ppa_codigo_clasificacion'])) ? $driver['ppa_codigo_clasificacion'] : '';

    $base = array(
        'alineado' => true,
        'warning' => false,
        'requiere_reajuste' => false,
        'mensaje' => '',
        // Claves legacy (dashboard/front): esperada->proyectada, base->real
        'ton_esperada_anual' => 0.0,
        'ton_base_anual' => 0.0,
        // Claves nuevas explicitas
        'ton_proyectada_periodo' => 0.0,
        'ton_real_periodo' => 0.0,
        'ton_base_mensual' => round($ton_base_mes, 2),
        'pct_diferencia' => 0.0,
        'umbral_pct' => $umbral_pct,
        'pdp_id' => $pdp_id_ref,
        'rubro_driver' => $rubro_ref,
        'partida_driver_codigo' => $partida_cod,
        'meses_con_real' => 0,
        'meses_con_plan' => 0,
    );

    $esc = $mysqli->real_escape_string(trim($proy_id));
    $res = $mysqli->query("SELECT
                COALESCE(SUM(CASE WHEN prd_real > 0 THEN prd_proyectada ELSE 0 END), 0) AS proy_periodo,
                COALESCE(SUM(prd_real), 0) AS real_periodo,
                COALESCE(SUM(CASE WHEN prd_real > 0 THEN 1 ELSE 0 END), 0) AS meses_real,
                COUNT(*) AS meses
            FROM pre_prod_periodos
            WHERE proy_id = '$esc' AND Emp_Cod = $Emp_Cod AND prd_anio = $anio");
    $proy_periodo = 0.0;
    $real_periodo = 0.0;
    $meses_real = 0;
    $meses = 0;
    if ($res && ($row = $res->fetch_assoc())) {
        $proy_periodo = (float)$row['proy_periodo'];
        $real_periodo = (float)$row['real_periodo'];
        $meses_real = (int)$row['meses_real'];
        $meses = (int)$row['meses'];
    }

    $base['ton_proyectada_periodo'] = round($proy_periodo, 2);
    $base['ton_real_periodo'] = round($real_periodo, 2);
    $base['ton_esperada_anual'] = round($proy_periodo, 2);
    $base['ton_base_anual'] = round($real_periodo, 2);
    $base['meses_con_real'] = $meses_real;
    $base['meses_con_plan'] = $meses;

    if ($meses_real <= 0) {
        $base['mensaje'] = 'Aun no hay meses cerrados con produccion real para comparar contra la proyectada.';
        return $base;
    }

    $pct = 0.0;
    if ($real_periodo > 0.0001) {
        $pct = round((abs($real_periodo - $proy_periodo) / $real_periodo) * 100.0, 2);
    }
    $base['pct_diferencia'] = $pct;

    $alineado = ($pct <= $umbral_pct);
    $base['alineado'] = $alineado;
    $base['warning'] = !$alineado;
    // La proyectada quedo corta: el real supera a la proyectada por encima del umbral.
    $proyectada_corta = (($real_periodo - $proy_periodo) > 0.0001) && !$alineado;
    $base['requiere_reajuste'] = $proyectada_corta;

    if (!$alineado) {
        if ($proyectada_corta) {
            $base['mensaje'] = sprintf(
                'La proyectada se quedo corta: real %.2f Ton vs proyectada %.2f Ton en %d mes(es) cerrado(s) (+%.2f%%, umbral %.1f%%). Reajuste la proyectada para cubrir los rubros presupuestarios. Rubro driver: %s.',
                $real_periodo,
                $proy_periodo,
                $meses_real,
                $pct,
                $umbral_pct,
                $rubro_ref
            );
        } else {
            $base['mensaje'] = sprintf(
                'La proyectada supera al real: proyectada %.2f Ton vs real %.2f Ton en %d mes(es) cerrado(s) (-%.2f%%, umbral %.1f%%). Presupuesto posiblemente sobreestimado. Rubro driver: %s.',
                $proy_periodo,
                $real_periodo,
                $meses_real,
                $pct,
                $umbral_pct,
                $rubro_ref
            );
        }
    }

    return $base;
}
