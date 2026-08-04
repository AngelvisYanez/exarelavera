<?php
/**
 * Toneladas base PDF por proyecto/version (pre_proyecto_version).
 */

if (!function_exists('ppto_proy_version_ton_base')) {
    function ppto_proy_version_ton_base($mysqli, $proy_id, $Emp_Cod, $ppe_id) {
        $esc = $mysqli->real_escape_string(trim($proy_id));
        $Emp_Cod = (int)$Emp_Cod;
        $ppe_id = (int)$ppe_id;
        if ($ppe_id <= 0) {
            return 0.0;
        }
        $res = $mysqli->query("SELECT pv_toneladas_base_mes FROM pre_proyecto_version
            WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id LIMIT 1");
        if ($res && ($row = $res->fetch_assoc())) {
            return (float)$row['pv_toneladas_base_mes'];
        }
        $res2 = $mysqli->query("SELECT MAX(d.pdp_toneladas_base) AS ton
            FROM pre_proyecto_detalles d
            WHERE d.proy_id='$esc' AND d.Emp_Cod=$Emp_Cod AND d.ppe_id=$ppe_id AND d.pdp_toneladas_base > 0");
        if ($res2 && ($row2 = $res2->fetch_assoc()) && (float)$row2['ton'] > 0) {
            return (float)$row2['ton'];
        }
        return 0.0;
    }
}

if (!function_exists('ppto_proy_version_ton_costo')) {
    /**
     * Ton/mes costo egreso guardada en version o inferida de rubros driver.
     *
     * @param mysqli $mysqli
     * @param string $proy_id
     * @param int $Emp_Cod
     * @param int $ppe_id
     * @return float
     */
    function ppto_proy_version_ton_costo($mysqli, $proy_id, $Emp_Cod, $ppe_id) {
        $esc = $mysqli->real_escape_string(trim($proy_id));
        $Emp_Cod = (int)$Emp_Cod;
        $ppe_id = (int)$ppe_id;
        if ($ppe_id <= 0) {
            return ppto_rubro_ton_mes_operativa();
        }
        $res = $mysqli->query("SELECT pv_toneladas_costo_mes FROM pre_proyecto_version
            WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id LIMIT 1");
        if ($res && ($row = $res->fetch_assoc()) && (float)$row['pv_toneladas_costo_mes'] > 0) {
            return (float)$row['pv_toneladas_costo_mes'];
        }
        $res2 = $mysqli->query("SELECT d.pdp_toneladas_base AS ton, COUNT(*) AS c
            FROM pre_proyecto_detalles d
            WHERE d.proy_id='$esc' AND d.Emp_Cod=$Emp_Cod AND d.ppe_id=$ppe_id
              AND d.pdp_toneladas_base >= 70000 AND d.pdp_toneladas_base < 95000
            GROUP BY d.pdp_toneladas_base
            ORDER BY c DESC
            LIMIT 1");
        if ($res2 && ($row2 = $res2->fetch_assoc()) && (float)$row2['ton'] > 0) {
            return (float)$row2['ton'];
        }
        return ppto_rubro_ton_mes_operativa();
    }
}

/**
 * Configuracion de version proyecto (toneladas + tarifa IVA).
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @return array
 */
function ppto_proy_version_config($mysqli, $proy_id, $Emp_Cod, $ppe_id) {
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $cfg = array(
        'ton_mes' => 0.0,
        'ton_anual' => 0.0,
        'tarifa_ton_iva' => 3.0,
        'iva_divisor' => 1.15,
        'tarifa_ton_neta' => 3.0 / 1.15,
    );
    if ($ppe_id <= 0) {
        return $cfg;
    }
    $res = $mysqli->query("SELECT pv_toneladas_base_mes, pv_tarifa_ton_iva, pv_iva_divisor
        FROM pre_proyecto_version
        WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $cfg['ton_mes'] = (float)$row['pv_toneladas_base_mes'];
        if (isset($row['pv_tarifa_ton_iva']) && (float)$row['pv_tarifa_ton_iva'] > 0) {
            $cfg['tarifa_ton_iva'] = (float)$row['pv_tarifa_ton_iva'];
        }
        if (isset($row['pv_iva_divisor']) && (float)$row['pv_iva_divisor'] > 0) {
            $cfg['iva_divisor'] = (float)$row['pv_iva_divisor'];
        }
    } else {
        $cfg['ton_mes'] = ppto_proy_version_ton_base($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    }
    $cfg['ton_anual'] = round($cfg['ton_mes'] * 12, 4);
    $cfg['tarifa_ton_neta'] = $cfg['iva_divisor'] > 0
        ? round($cfg['tarifa_ton_iva'] / $cfg['iva_divisor'], 6)
        : 0.0;
    return $cfg;
}

/**
 * Tope anual de grupo: % x (tarifa con IVA / divisor IVA) x toneladas anuales.
 *
 * @param float $pct
 * @param array $cfg
 * @return float
 */
function ppto_proy_grupo_tope_anual($pct, $cfg) {
    $pct = (float)$pct;
    if ($pct <= 0.0001) {
        return 0.0;
    }
    $ton_anual = isset($cfg['ton_anual']) ? (float)$cfg['ton_anual'] : 0.0;
    $tarifa = isset($cfg['tarifa_ton_iva']) ? (float)$cfg['tarifa_ton_iva'] : 0.0;
    $iva = isset($cfg['iva_divisor']) ? (float)$cfg['iva_divisor'] : 1.15;
    if ($ton_anual <= 0.0001 || $tarifa <= 0.0001 || $iva <= 0.0001) {
        return 0.0;
    }
    return round(($pct / 100.0) * ($tarifa / $iva) * $ton_anual, 2);
}

/**
 * Texto legible de la formula de tope.
 *
 * @param float $pct
 * @param array $cfg
 * @return string
 */
function ppto_proy_grupo_tope_formula_txt($pct, $cfg) {
    $tope = ppto_proy_grupo_tope_anual($pct, $cfg);
    return number_format($pct, 4, '.', ',') . '% x ($'
        . number_format($cfg['tarifa_ton_iva'], 2, '.', ',') . '/'
        . number_format($cfg['iva_divisor'], 2, '.', ',') . ') x '
        . number_format($cfg['ton_anual'], 0, '.', ',') . ' Ton/a = $'
        . number_format($tope, 2, '.', ',');
}

/**
 * Resuelve ppe_id activo para un anio presupuestario.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $anio
 * @return int
 */
function ppto_proy_version_ppe_id_por_anio($mysqli, $Emp_Cod, $anio) {
    $Emp_Cod = (int)$Emp_Cod;
    $anio = (int)$anio;
    if (!$mysqli || $Emp_Cod <= 0 || $anio <= 0) {
        return 0;
    }
    require_once __DIR__ . '/ppto_persistencia_logica.php';
    $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'ppe_anio' => $anio));
    return $ppe_id ? (int)$ppe_id : 0;
}

/**
 * Copia la tonelada base PDF (Ton/mes) al plan de produccion esperada.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $anio
 * @param int $ppe_id 0 = resolver por anio
 * @param array $opts solo_vacios, preservar_cerrados (default true)
 * @return array
 */
function ppto_prod_sync_esperada_desde_ton_base($mysqli, $proy_id, $Emp_Cod, $anio, $ppe_id = 0, $opts = array()) {
    $solo_vacios = !empty($opts['solo_vacios']);
    $preservar_cerrados = !array_key_exists('preservar_cerrados', $opts) || !empty($opts['preservar_cerrados']);
    $Emp_Cod = (int)$Emp_Cod;
    $anio = (int)$anio;
    $ppe_id = (int)$ppe_id;

    if (!$mysqli || trim($proy_id) === '' || $Emp_Cod <= 0 || $anio <= 0) {
        return array('ok' => false, 'message' => 'Datos incompletos para sincronizar esperada.');
    }

    if ($ppe_id <= 0) {
        $ppe_id = ppto_proy_version_ppe_id_por_anio($mysqli, $Emp_Cod, $anio);
    }
    if ($ppe_id <= 0) {
        return array('ok' => false, 'message' => 'No hay version presupuestaria activa para el anio ' . $anio . '.');
    }

    $ton = ppto_proy_version_ton_base($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    if ($ton <= 0.0001) {
        return array('ok' => false, 'message' => 'No hay tonelada base PDF. Definala en Proyectos (Ton base PDF) o importe el presupuesto.');
    }

    require_once __DIR__ . '/ppto_integracion_motor.php';
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $meses_ok = 0;
    $meses_saltados = 0;

    for ($mes = 1; $mes <= 12; $mes++) {
        $res = $mysqli->query("SELECT prd_esperada, prd_estado FROM pre_prod_periodos
            WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod AND prd_anio=$anio AND prd_mes=$mes LIMIT 1");
        if ($res && ($row = $res->fetch_assoc())) {
            if ($preservar_cerrados && isset($row['prd_estado']) && $row['prd_estado'] === 'cerrado') {
                $meses_saltados++;
                continue;
            }
            if ($solo_vacios && (float)$row['prd_esperada'] > 0.0001) {
                $meses_saltados++;
                continue;
            }
        } elseif ($solo_vacios) {
            // sin fila: aplicar
        }

        if (ppto_integracion_produccion_registrar($mysqli, $proy_id, $mes, $ton, 'esperada', $anio, $Emp_Cod)) {
            $meses_ok++;
        }
    }

    return array(
        'ok' => true,
        'message' => 'Esperada sincronizada desde PDF: ' . number_format($ton, 2, '.', ',') . ' Ton/mes (' . $meses_ok . ' meses).',
        'ton_mes' => round($ton, 4),
        'meses_actualizados' => $meses_ok,
        'meses_saltados' => $meses_saltados,
        'ppe_id' => $ppe_id,
    );
}
