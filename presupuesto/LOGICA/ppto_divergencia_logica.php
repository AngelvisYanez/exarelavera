<?php
/**
 * ppto_divergencia_logica.php - Alerta D2: Prd_Proyectada vs Prd_Real (Idea A).
 *
 * Semantica (flujo aprobacion mes a mes):
 * - pre_prod_periodos.Prd_Proyectada = toneladas proyectadas con las que se
 *   aprueba el presupuesto en $ del mes (proyectada x $/Ton).
 * - pre_prod_periodos.Prd_Real = toneladas reales al cerrar el mes.
 * Comparacion: SUM(Prd_Proyectada) vs SUM(Prd_Real) sobre meses con real > 0.
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
function ppto_divergencia_obtener_rubro_driver($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod = null) {
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $sql = "SELECT d.Pdp_Cod AS Pdp_Cod, d.Pdp_Rubro AS Pdp_Rubro, d.Pdp_TonBase AS Pdp_TonBase, d.Pdp_FacAnualTon AS Pdp_FacAnualTon,
                   p.Ppa_Cla AS Ppa_Cla, COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') AS Ppa_Clase
            FROM pre_proyecto_detalles d
            INNER JOIN pre_partidas p ON (p.Ppa_Cod = d.Ppa_Cod OR p.Ppa_Cod = d.Ppa_Cod) AND p.Emp_Cod = d.Emp_Cod
            WHERE (d.Pro_Cod = '$esc' OR d.Pro_Cod = '$esc') AND d.Emp_Cod = $Emp_Cod
              AND COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') = 'D'";
    if ($Ppe_Cod !== null && (int)$Ppe_Cod > 0) {
        $sql .= ' AND d.Ppe_Cod = ' . (int)$Ppe_Cod;
    }
    $sql .= " ORDER BY p.Ppa_Cla ASC, d.Pdp_Cod ASC";
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
        $rubro = strtolower(trim($row['Pdp_Rubro']));
        if (strpos($rubro, 'relav') !== false || strpos($rubro, 'procesad') !== false) {
            return ppto_divergencia_format_driver_row($row);
        }
    }

    foreach ($filas as $row) {
        if ((float)$row['Pdp_TonBase'] > 0.0001 && (float)$row['Pdp_FacAnualTon'] > 0.0001) {
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
        'Pdp_Cod' => (int)$row['Pdp_Cod'],
        'Pdp_Rubro' => $row['Pdp_Rubro'],
        'Pdp_TonBase' => (float)$row['Pdp_TonBase'],
        'Ppa_Cla' => isset($row['Ppa_Cla']) ? $row['Ppa_Cla'] : '',
    );
}

/**
 * Compara la proyectada contra el real de los meses ya cerrados (con real > 0).
 *
 * Semantica nueva (flujo Idea A - aprobacion mes a mes):
 * - Prd_Proyectada = base con la que se aprueba el presupuesto en $ de cada mes.
 * - Prd_Real = produccion realmente ocurrida al cerrar el mes.
 * Si el real supera a la proyectada, la proyectada (y por tanto el presupuesto
 * aprobado) se quedo corta y debe reajustarse para cubrir los rubros.
 * Solo se comparan meses con real > 0 (cerrados / con dato real).
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $anio
 * @param int|null $Ppe_Cod
 * @param float|null $umbral_pct default 5%
 * @return array
 */
function ppto_divergencia_comparar_toneladas($mysqli, $Pro_Cod, $Emp_Cod, $anio, $Ppe_Cod = null, $umbral_pct = null) {
    if ($umbral_pct === null) {
        $umbral_pct = PPTO_DIVERGENCIA_UMBRAL_DEFAULT;
    }
    $umbral_pct = (float)$umbral_pct;
    $anio = (int)$anio;
    $Emp_Cod = (int)$Emp_Cod;

    $driver = ppto_divergencia_obtener_rubro_driver($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    $ton_base_mes = 0.0;
    $rubro_ref = '';
    if ($Ppe_Cod !== null && (int)$Ppe_Cod > 0) {
        $ton_pv = ppto_proy_version_ton_base($mysqli, $Pro_Cod, $Emp_Cod, (int)$Ppe_Cod);
        if ($ton_pv > 0.0001) {
            $ton_base_mes = $ton_pv;
            $rubro_ref = 'Base PDF proyecto/version';
        }
    }
    if ($ton_base_mes <= 0.0001 && $driver !== null) {
        $ton_base_mes = (float)$driver['Pdp_TonBase'];
        $rubro_ref = $driver['Pdp_Rubro'];
    }
    if ($rubro_ref === '' && $driver !== null) {
        $rubro_ref = $driver['Pdp_Rubro'];
    }

    $pdp_id_ref = ($driver !== null) ? (int)$driver['Pdp_Cod'] : null;
    $partida_cod = ($driver !== null && isset($driver['Ppa_Cla'])) ? $driver['Ppa_Cla'] : '';

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
        'Pdp_Cod' => $pdp_id_ref,
        'rubro_driver' => $rubro_ref,
        'partida_driver_codigo' => $partida_cod,
        'meses_con_real' => 0,
        'meses_con_plan' => 0,
    );

    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $res = $mysqli->query("SELECT
                COALESCE(SUM(CASE WHEN Prd_Real > 0 THEN Prd_Proyectada ELSE 0 END), 0) AS proy_periodo,
                COALESCE(SUM(Prd_Real), 0) AS real_periodo,
                COALESCE(SUM(CASE WHEN Prd_Real > 0 THEN 1 ELSE 0 END), 0) AS meses_real,
                COUNT(*) AS meses
            FROM pre_prod_periodos
            WHERE Pro_Cod = '$esc' AND Emp_Cod = $Emp_Cod AND Prd_Anio = $anio");
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
