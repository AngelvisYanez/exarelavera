<?php
/**
 * Toneladas base PDF por proyecto/version (pre_proyecto_version).
 * Lote B: SQL contra pre_*; SELECT con alias legacy donde aplica.
 */

if (!function_exists('ppto_proy_version_ton_base')) {
    function ppto_proy_version_ton_base($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod) {
        $esc = $mysqli->real_escape_string(trim($Pro_Cod));
        $Emp_Cod = (int)$Emp_Cod;
        $Ppe_Cod = (int)$Ppe_Cod;
        if ($Ppe_Cod <= 0) {
            return 0.0;
        }
        $res = $mysqli->query(
            "SELECT Ppv_TonBaseMes AS pv_toneladas_base_mes
             FROM pre_proyecto_version
             WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod
             LIMIT 1"
        );
        if ($res && ($row = $res->fetch_assoc())) {
            return (float)$row['pv_toneladas_base_mes'];
        }
        $res2 = $mysqli->query(
            "SELECT MAX(d.Pdp_TonBase) AS ton
             FROM pre_proyecto_detalles d
             WHERE d.Pro_Cod='$esc' AND d.Emp_Cod=$Emp_Cod AND d.Ppe_Cod=$Ppe_Cod
               AND d.Pdp_TonBase > 0"
        );
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
     * @param string $Pro_Cod
     * @param int $Emp_Cod
     * @param int $Ppe_Cod
     * @return float
     */
    function ppto_proy_version_ton_costo($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod) {
        $esc = $mysqli->real_escape_string(trim($Pro_Cod));
        $Emp_Cod = (int)$Emp_Cod;
        $Ppe_Cod = (int)$Ppe_Cod;
        if ($Ppe_Cod <= 0) {
            return ppto_rubro_ton_mes_operativa();
        }
        $res = $mysqli->query(
            "SELECT Ppv_TonCostoMes AS pv_toneladas_costo_mes
             FROM pre_proyecto_version
             WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod
             LIMIT 1"
        );
        if ($res && ($row = $res->fetch_assoc()) && (float)$row['pv_toneladas_costo_mes'] > 0) {
            return (float)$row['pv_toneladas_costo_mes'];
        }
        $res2 = $mysqli->query(
            "SELECT d.Pdp_TonBase AS ton, COUNT(*) AS c
             FROM pre_proyecto_detalles d
             WHERE d.Pro_Cod='$esc' AND d.Emp_Cod=$Emp_Cod AND d.Ppe_Cod=$Ppe_Cod
               AND d.Pdp_TonBase >= 70000 AND d.Pdp_TonBase < 95000
             GROUP BY d.Pdp_TonBase
             ORDER BY c DESC
             LIMIT 1"
        );
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
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @return array
 */
function ppto_proy_version_config($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod) {
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $cfg = array(
        'ton_mes' => 0.0,
        'ton_anual' => 0.0,
        'tarifa_ton_iva' => 3.0,
        'iva_divisor' => 1.15,
        'tarifa_ton_neta' => 3.0 / 1.15,
    );
    if ($Ppe_Cod <= 0) {
        return $cfg;
    }
    $res = $mysqli->query(
        "SELECT Ppv_TonBaseMes AS pv_toneladas_base_mes,
                Ppv_TarifaTonIva AS pv_tarifa_ton_iva,
                Ppv_IvaDivisor AS pv_iva_divisor
         FROM pre_proyecto_version
         WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod
         LIMIT 1"
    );
    if ($res && ($row = $res->fetch_assoc())) {
        $cfg['ton_mes'] = (float)$row['pv_toneladas_base_mes'];
        if (isset($row['pv_tarifa_ton_iva']) && (float)$row['pv_tarifa_ton_iva'] > 0) {
            $cfg['tarifa_ton_iva'] = (float)$row['pv_tarifa_ton_iva'];
        }
        if (isset($row['pv_iva_divisor']) && (float)$row['pv_iva_divisor'] > 0) {
            $cfg['iva_divisor'] = (float)$row['pv_iva_divisor'];
        }
    } else {
        $cfg['ton_mes'] = ppto_proy_version_ton_base($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
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
 * Resuelve Ppe_Cod activo para un anio presupuestario.
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
    $Ppe_Cod = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'Ppe_Ani' => $anio));
    return $Ppe_Cod ? (int)$Ppe_Cod : 0;
}

/**
 * Copia la tonelada base PDF (Ton/mes) al plan de produccion esperada.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $anio
 * @param int $Ppe_Cod 0 = resolver por anio
 * @param array $opts solo_vacios, preservar_cerrados (default true)
 * @return array
 */
function ppto_prod_sync_esperada_desde_ton_base($mysqli, $Pro_Cod, $Emp_Cod, $anio, $Ppe_Cod = 0, $opts = array()) {
    $solo_vacios = !empty($opts['solo_vacios']);
    $preservar_cerrados = !array_key_exists('preservar_cerrados', $opts) || !empty($opts['preservar_cerrados']);
    $Emp_Cod = (int)$Emp_Cod;
    $anio = (int)$anio;
    $Ppe_Cod = (int)$Ppe_Cod;

    if (!$mysqli || trim($Pro_Cod) === '' || $Emp_Cod <= 0 || $anio <= 0) {
        return array('ok' => false, 'message' => 'Datos incompletos para sincronizar esperada.');
    }

    if ($Ppe_Cod <= 0) {
        $Ppe_Cod = ppto_proy_version_ppe_id_por_anio($mysqli, $Emp_Cod, $anio);
    }
    if ($Ppe_Cod <= 0) {
        return array('ok' => false, 'message' => 'No hay version presupuestaria activa para el anio ' . $anio . '.');
    }

    $ton = ppto_proy_version_ton_base($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    if ($ton <= 0.0001) {
        return array('ok' => false, 'message' => 'No hay tonelada base PDF. Definala en Proyectos (Ton base PDF) o importe el presupuesto.');
    }

    require_once __DIR__ . '/ppto_integracion_motor.php';
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $meses_ok = 0;
    $meses_saltados = 0;

    for ($mes = 1; $mes <= 12; $mes++) {
        $res = $mysqli->query(
            "SELECT Prd_Esperada AS prd_esperada, Prd_Est AS prd_estado
             FROM pre_prod_periodos
             WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Prd_Anio=$anio AND Prd_Mes=$mes
             LIMIT 1"
        );
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

        if (ppto_integracion_produccion_registrar($mysqli, $Pro_Cod, $mes, $ton, 'esperada', $anio, $Emp_Cod)) {
            $meses_ok++;
        }
    }

    return array(
        'ok' => true,
        'message' => 'Esperada sincronizada desde PDF: ' . number_format($ton, 2, '.', ',') . ' Ton/mes (' . $meses_ok . ' meses).',
        'ton_mes' => round($ton, 4),
        'meses_actualizados' => $meses_ok,
        'meses_saltados' => $meses_saltados,
        'Ppe_Cod' => $Ppe_Cod,
    );
}
