<?php
/**
 * ppto_forecast_logica.php
 * Motor unico de forecast presupuestario (Fase 2B — D1).
 */

require_once __DIR__ . '/ppto_proy_perfil_logica.php';

/**
 * Carga produccion mes a mes incluyendo prd_estado para ton_ref.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $anio
 * @param string|null $proy_id
 * @return array
 */
function ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $proy_id) {
    $meses = array();
    for ($m = 1; $m <= 12; $m++) {
        $meses[$m] = array(
            'esperada'   => 0.0,
            'real'       => 0.0,
            'proyectada' => 0.0,
            'estado'     => 'sin_dato',
        );
    }
    if ($proy_id === null || trim($proy_id) === '') {
        return $meses;
    }
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $res = $mysqli->query("SELECT prd_mes, prd_esperada, prd_real, prd_proyectada,
            COALESCE(prd_estado, 'sin_dato') AS prd_estado
        FROM exa_ppto_prod_periodos
        WHERE Emp_Cod = " . (int)$Emp_Cod . " AND prd_anio = " . (int)$anio . " AND proy_id = '$esc'");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $m = (int)$r['prd_mes'];
            if ($m >= 1 && $m <= 12) {
                $meses[$m] = array(
                    'esperada'   => (float)$r['prd_esperada'],
                    'real'       => (float)$r['prd_real'],
                    'proyectada' => (float)$r['prd_proyectada'],
                    'estado'     => $r['prd_estado'],
                );
            }
        }
    }
    return $meses;
}

/**
 * Ton proyectada para control presupuestario (columna PROYECTADA en produccion).
 *
 * @param array $md
 * @return float
 */
function ppto_forecast_ton_proyectada_mes($md) {
    if ((float)$md['proyectada'] > 0.0001) {
        return (float)$md['proyectada'];
    }
    return (float)$md['esperada'];
}

/**
 * Ton real del mes (columna REAL en produccion).
 *
 * @param array $md
 * @return float
 */
function ppto_forecast_ton_real_mes($md) {
    return (float)$md['real'];
}

/**
 * PF rubro periodo = suma toneladas proyectadas x ($/Ton anual / 12).
 *
 * @param array $meses_prod
 * @param int|null $mes_filtro
 * @param float $factor_anual
 * @return float
 */
function ppto_forecast_pf_rubro_periodo_proyectada($meses_prod, $mes_filtro, $factor_anual) {
    $total = 0.0;
    if ($mes_filtro !== null) {
        $m = (int)$mes_filtro;
        if ($m >= 1 && $m <= 12) {
            return ppto_forecast_pf_rubro_mes(
                ppto_forecast_ton_proyectada_mes($meses_prod[$m]),
                $factor_anual
            );
        }
        return 0.0;
    }
    for ($m = 1; $m <= 12; $m++) {
        $total += ppto_forecast_pf_rubro_mes(
            ppto_forecast_ton_proyectada_mes($meses_prod[$m]),
            $factor_anual
        );
    }
    return round($total, 2);
}

/**
 * PF rubro periodo = suma toneladas reales x ($/Ton anual / 12).
 *
 * @param array $meses_prod
 * @param int|null $mes_filtro
 * @param float $factor_anual
 * @return float
 */
function ppto_forecast_pf_rubro_periodo_real($meses_prod, $mes_filtro, $factor_anual) {
    $total = 0.0;
    if ($mes_filtro !== null) {
        $m = (int)$mes_filtro;
        if ($m >= 1 && $m <= 12) {
            return ppto_forecast_pf_rubro_mes(
                ppto_forecast_ton_real_mes($meses_prod[$m]),
                $factor_anual
            );
        }
        return 0.0;
    }
    for ($m = 1; $m <= 12; $m++) {
        $total += ppto_forecast_pf_rubro_mes(
            ppto_forecast_ton_real_mes($meses_prod[$m]),
            $factor_anual
        );
    }
    return round($total, 2);
}

/**
 * ton_ref(m): cerrado -> real (incluye 0 legitimo); si no -> proyectada; si no -> esperada.
 *
 * @param array $md
 * @return float
 */
function ppto_forecast_ton_ref_mes($md) {
    $estado = isset($md['estado']) ? $md['estado'] : 'sin_dato';
    if ($estado === 'cerrado') {
        return (float)$md['real'];
    }
    if ((float)$md['proyectada'] > 0.0001) {
        return (float)$md['proyectada'];
    }
    return (float)$md['esperada'];
}

/**
 * Toneladas reales solo de meses cerrados (base techo formal relavera).
 *
 * @param array $md
 * @return float
 */
function ppto_forecast_ton_real_cerrado_mes($md) {
    $estado = isset($md['estado']) ? $md['estado'] : 'sin_dato';
    if ($estado === 'cerrado') {
        return (float)$md['real'];
    }
    return 0.0;
}

/**
 * Cuenta meses con prd_estado=cerrado.
 *
 * @param array $meses_prod
 * @return int
 */
function ppto_forecast_contar_meses_cerrados($meses_prod) {
    $n = 0;
    for ($m = 1; $m <= 12; $m++) {
        if (isset($meses_prod[$m]['estado']) && $meses_prod[$m]['estado'] === 'cerrado') {
            $n++;
        }
    }
    return $n;
}

/**
 * PF rubro periodo usando solo toneladas reales de meses cerrados.
 *
 * @param array $meses_prod
 * @param int|null $mes_filtro
 * @param float $factor_anual
 * @return float
 */
function ppto_forecast_pf_rubro_periodo_solo_real($meses_prod, $mes_filtro, $factor_anual) {
    $total = 0.0;
    if ($mes_filtro !== null) {
        $m = (int)$mes_filtro;
        if ($m >= 1 && $m <= 12) {
            return ppto_forecast_pf_rubro_mes(
                ppto_forecast_ton_real_cerrado_mes($meses_prod[$m]),
                $factor_anual
            );
        }
        return 0.0;
    }
    for ($m = 1; $m <= 12; $m++) {
        $total += ppto_forecast_pf_rubro_mes(
            ppto_forecast_ton_real_cerrado_mes($meses_prod[$m]),
            $factor_anual
        );
    }
    return round($total, 2);
}

/**
 * PF rubro mensual = ton_ref x (factor_anual / 12).
 *
 * @param float $ton_ref
 * @param float $factor_anual
 * @return float
 */
function ppto_forecast_pf_rubro_mes($ton_ref, $factor_anual) {
    return round((float)$ton_ref * ((float)$factor_anual / 12.0), 2);
}

/**
 * PF anual de un rubro segun escenario de toneladas.
 * - 'esperada'   : suma de esperada[mes] (base PDF).
 * - 'proyectada' : suma de proyectada[mes] (proyectada||esperada).
 * - 'real'       : suma de real[mes] donde real>0, si no proyectada[mes] (real + proyectado).
 * - 'solo_real'  : solo toneladas reales (sin completar con proyectada; formalizacion).
 *
 * @param array $meses_prod
 * @param float $factor_anual
 * @param string $escenario esperada|proyectada|real|solo_real
 * @return float
 */
function ppto_forecast_pf_rubro_anual_escenario($meses_prod, $factor_anual, $escenario) {
    $factor_anual = (float)$factor_anual;
    if ($factor_anual <= 0.0001) {
        return 0.0;
    }
    $total = 0.0;
    for ($m = 1; $m <= 12; $m++) {
        $md = isset($meses_prod[$m]) ? $meses_prod[$m] : array('esperada' => 0.0, 'proyectada' => 0.0, 'real' => 0.0);
        if ($escenario === 'esperada') {
            $ton = (float)$md['esperada'];
        } elseif ($escenario === 'solo_real') {
            $ton = (float)$md['real'];
        } elseif ($escenario === 'real') {
            $ton = ((float)$md['real'] > 0.0001) ? (float)$md['real'] : ppto_forecast_ton_proyectada_mes($md);
        } else {
            $ton = ppto_forecast_ton_proyectada_mes($md);
        }
        $total += ppto_forecast_pf_rubro_mes($ton, $factor_anual);
    }
    return round($total, 2);
}

/**
 * Tonelada de un mes segun escenario.
 *
 * @param array $md
 * @param string $escenario esperada|proyectada|real
 * @return float
 */
function ppto_forecast_ton_mes_escenario_val($md, $escenario) {
    if ($escenario === 'esperada') {
        return (float)$md['esperada'];
    }
    if ($escenario === 'solo_real') {
        return (float)$md['real'];
    }
    if ($escenario === 'real') {
        return ((float)$md['real'] > 0.0001) ? (float)$md['real'] : ppto_forecast_ton_proyectada_mes($md);
    }
    return ppto_forecast_ton_proyectada_mes($md);
}

/**
 * Promedio mensual de toneladas del escenario (suma 12 meses / 12).
 *
 * @param array $meses_prod
 * @param string $escenario esperada|proyectada|real
 * @return float
 */
function ppto_forecast_ton_mes_escenario($meses_prod, $escenario) {
    $sum = 0.0;
    for ($m = 1; $m <= 12; $m++) {
        $md = isset($meses_prod[$m]) ? $meses_prod[$m] : array('esperada' => 0.0, 'proyectada' => 0.0, 'real' => 0.0);
        $sum += ppto_forecast_ton_mes_escenario_val($md, $escenario);
    }
    return round($sum / 12.0, 2);
}

/**
 * Suma anual de toneladas del escenario (12 meses).
 *
 * @param array $meses_prod
 * @param string $escenario esperada|proyectada|real
 * @return float
 */
function ppto_forecast_ton_anual_escenario($meses_prod, $escenario) {
    $sum = 0.0;
    for ($m = 1; $m <= 12; $m++) {
        $md = isset($meses_prod[$m]) ? $meses_prod[$m] : array('esperada' => 0.0, 'proyectada' => 0.0, 'real' => 0.0);
        $sum += ppto_forecast_ton_mes_escenario_val($md, $escenario);
    }
    return round($sum, 2);
}

/**
 * Vista del cuadro presupuestario: anual | acumulado | mes.
 *
 * @param string $vista
 * @return string
 */
function ppto_forecast_cuadro_vista_sanitize($vista) {
    $v = strtolower(trim((string)$vista));
    if (!in_array($v, array('anual', 'acumulado', 'mes'), true)) {
        return 'anual';
    }
    return $v;
}

/**
 * Mes de corte (1-12) para vista acumulado o mes.
 *
 * @param mixed $mes
 * @return int
 */
function ppto_forecast_cuadro_mes_sanitize($mes) {
    $m = (int)$mes;
    if ($m < 1) {
        $m = (int)date('n');
    }
    if ($m > 12) {
        $m = 12;
    }
    return $m;
}

/**
 * Ultimo mes con produccion real cargada (defecto para acumulado YTD).
 *
 * @param array $meses_prod
 * @return int
 */
function ppto_forecast_ultimo_mes_con_real($meses_prod) {
    $last = 0;
    for ($m = 1; $m <= 12; $m++) {
        if (isset($meses_prod[$m]) && (float)$meses_prod[$m]['real'] > 0.0001) {
            $last = $m;
        }
    }
    if ($last > 0) {
        return $last;
    }
    return ppto_forecast_cuadro_mes_sanitize((int)date('n'));
}

/**
 * Rango de meses segun vista del cuadro.
 *
 * @param string $vista
 * @param int $mes
 * @return array {ini, fin}
 */
function ppto_forecast_cuadro_rango_meses($vista, $mes) {
    $vista = ppto_forecast_cuadro_vista_sanitize($vista);
    $mes = ppto_forecast_cuadro_mes_sanitize($mes);
    if ($vista === 'mes') {
        return array('ini' => $mes, 'fin' => $mes);
    }
    if ($vista === 'acumulado') {
        return array('ini' => 1, 'fin' => $mes);
    }
    return array('ini' => 1, 'fin' => 12);
}

/**
 * Toneladas del periodo seleccionado en el cuadro.
 *
 * @param array $meses_prod
 * @param string $escenario
 * @param string $vista
 * @param int $mes
 * @param float $ton_base_pdf
 * @return float
 */
function ppto_forecast_ton_periodo_escenario($meses_prod, $escenario, $vista, $mes, $ton_base_pdf = 0.0) {
    $rango = ppto_forecast_cuadro_rango_meses($vista, $mes);
    $sum = 0.0;
    for ($m = $rango['ini']; $m <= $rango['fin']; $m++) {
        if ($escenario === 'esperada' && $ton_base_pdf > 0.0001) {
            $sum += (float)$ton_base_pdf;
            continue;
        }
        $md = isset($meses_prod[$m]) ? $meses_prod[$m] : array('esperada' => 0.0, 'proyectada' => 0.0, 'real' => 0.0);
        $sum += ppto_forecast_ton_mes_escenario_val($md, $escenario);
    }
    return round($sum, 2);
}

/**
 * PF rubro para periodo del cuadro (anual, acumulado YTD o mes unico).
 *
 * @param array $meses_prod
 * @param float $factor_anual
 * @param string $escenario
 * @param string $vista
 * @param int $mes
 * @return float
 */
function ppto_forecast_pf_rubro_periodo_escenario($meses_prod, $factor_anual, $escenario, $vista, $mes) {
    $factor_anual = (float)$factor_anual;
    if ($factor_anual <= 0.0001) {
        return 0.0;
    }
    if (ppto_forecast_cuadro_vista_sanitize($vista) === 'anual') {
        return ppto_forecast_pf_rubro_anual_escenario($meses_prod, $factor_anual, $escenario);
    }
    $rango = ppto_forecast_cuadro_rango_meses($vista, $mes);
    $total = 0.0;
    for ($m = $rango['ini']; $m <= $rango['fin']; $m++) {
        $md = isset($meses_prod[$m]) ? $meses_prod[$m] : array('esperada' => 0.0, 'proyectada' => 0.0, 'real' => 0.0);
        $ton = ppto_forecast_ton_mes_escenario_val($md, $escenario);
        $total += ppto_forecast_pf_rubro_mes($ton, $factor_anual);
    }
    return round($total, 2);
}

/**
 * Escala monto anual de rubro fijo al periodo del cuadro.
 *
 * @param float $anual
 * @param string $vista
 * @param int $mes
 * @return float
 */
function ppto_forecast_monto_fijo_periodo($anual, $vista, $mes) {
    $anual = round((float)$anual, 2);
    $vista = ppto_forecast_cuadro_vista_sanitize($vista);
    if ($vista === 'anual') {
        return $anual;
    }
    if ($vista === 'mes') {
        return round($anual / 12.0, 2);
    }
    $mes = ppto_forecast_cuadro_mes_sanitize($mes);
    return round($anual * ($mes / 12.0), 2);
}

/**
 * Etiqueta legible del periodo del cuadro.
 *
 * @param string $vista
 * @param int $mes
 * @return string
 */
function ppto_forecast_cuadro_periodo_label($vista, $mes) {
    require_once __DIR__ . '/ppto_format_helpers.php';
    $vista = ppto_forecast_cuadro_vista_sanitize($vista);
    $mes = ppto_forecast_cuadro_mes_sanitize($mes);
    if ($vista === 'anual') {
        return 'Anual completo';
    }
    if ($vista === 'mes') {
        return 'Mes: ' . ppto_nombre_mes($mes);
    }
    if ($mes <= 1) {
        return 'Acumulado: ' . ppto_nombre_mes(1);
    }
    return 'Acumulado ene–' . strtolower(ppto_nombre_mes($mes));
}

/**
 * Ingreso anual = toneladas del escenario x ($/Ton con IVA / divisor IVA).
 *
 * @param array $meses_prod
 * @param float $tarifa_ton_iva
 * @param float $iva_divisor
 * @param string $escenario esperada|proyectada|real
 * @return float
 */
function ppto_forecast_ingreso_anual_escenario($meses_prod, $tarifa_ton_iva, $iva_divisor, $escenario) {
    $tarifa_ton_iva = (float)$tarifa_ton_iva;
    $iva_divisor = (float)$iva_divisor;
    if ($tarifa_ton_iva <= 0) {
        $tarifa_ton_iva = 3.0;
    }
    if ($iva_divisor <= 0) {
        $iva_divisor = 1.15;
    }
    $ton_anual = ppto_forecast_ton_anual_escenario($meses_prod, $escenario);
    return round($ton_anual * $tarifa_ton_iva / $iva_divisor, 2);
}

/**
 * Ingreso del periodo del cuadro segun toneladas del escenario.
 *
 * @param array $meses_prod
 * @param float $tarifa_ton_iva
 * @param float $iva_divisor
 * @param string $escenario
 * @param string $vista
 * @param int $mes
 * @param float $ton_base_pdf
 * @return float
 */
function ppto_forecast_ingreso_periodo_escenario($meses_prod, $tarifa_ton_iva, $iva_divisor, $escenario, $vista, $mes, $ton_base_pdf = 0.0) {
    $tarifa_ton_iva = (float)$tarifa_ton_iva;
    $iva_divisor = (float)$iva_divisor;
    if ($tarifa_ton_iva <= 0) {
        $tarifa_ton_iva = 3.0;
    }
    if ($iva_divisor <= 0) {
        $iva_divisor = 1.15;
    }
    $ton = ppto_forecast_ton_periodo_escenario($meses_prod, $escenario, $vista, $mes, $ton_base_pdf);
    return round($ton * $tarifa_ton_iva / $iva_divisor, 2);
}

/**
 * PF rubro para periodo (mes unico o anual acumulado).
 *
 * @param array $meses_prod
 * @param int|null $mes_filtro
 * @param float $factor_anual
 * @return float
 */
function ppto_forecast_pf_rubro_periodo($meses_prod, $mes_filtro, $factor_anual) {
    $total = 0.0;
    if ($mes_filtro !== null) {
        $m = (int)$mes_filtro;
        if ($m >= 1 && $m <= 12) {
            $total = ppto_forecast_pf_rubro_mes(
                ppto_forecast_ton_ref_mes($meses_prod[$m]),
                $factor_anual
            );
        }
        return $total;
    }
    for ($m = 1; $m <= 12; $m++) {
        $total += ppto_forecast_pf_rubro_mes(
            ppto_forecast_ton_ref_mes($meses_prod[$m]),
            $factor_anual
        );
    }
    return round($total, 2);
}

/**
 * Sem�foro unificado (umbrales dashboard).
 *
 * @param float $vigente
 * @param float $disponible
 * @return array {disponible_porcentaje, semaforo}
 */
function ppto_forecast_pf_rubro_periodo_ton_fija($meses_prod, $mes_filtro, $factor_anual, $ton_fija) {
    $ton_fija = (float)$ton_fija;
    if ($ton_fija <= 0.0001) {
        return ppto_forecast_pf_rubro_periodo($meses_prod, $mes_filtro, $factor_anual);
    }
    $total = 0.0;
    if ($mes_filtro !== null) {
        $m = (int)$mes_filtro;
        if ($m >= 1 && $m <= 12) {
            return ppto_forecast_pf_rubro_mes($ton_fija, $factor_anual);
        }
        return 0.0;
    }
    for ($m = 1; $m <= 12; $m++) {
        $total += ppto_forecast_pf_rubro_mes($ton_fija, $factor_anual);
    }
    return round($total, 2);
}

function ppto_forecast_semaforo($vigente, $disponible) {
    $disponible_pct = 0.00;
    if ($vigente > 0) {
        $disponible_pct = round(($disponible / $vigente) * 100.00, 2);
    } else {
        $disponible_pct = ($disponible > 0) ? 100.00 : 0.00;
    }

    if ($disponible < 0) {
        $semaforo = 'rojo';
    } elseif ($disponible_pct > 20.00) {
        $semaforo = 'verde';
    } elseif ($disponible_pct >= 5.00) {
        $semaforo = 'amarillo';
    } else {
        $semaforo = 'rojo';
    }

    return array(
        'disponible_porcentaje' => $disponible_pct,
        'semaforo'              => $semaforo,
    );
}

/**
 * Resuelve metodo forecast del proyecto (solo produccion_proyectada activo en 2B).
 *
 * @param mysqli $mysqli
 * @param string|null $proy_id
 * @param int $Emp_Cod
 * @return string
 */
function ppto_forecast_metodo_proyecto($mysqli, $proy_id, $Emp_Cod) {
    if ($proy_id === null || trim($proy_id) === '') {
        return 'produccion_proyectada';
    }
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $res = $mysqli->query("SELECT pco_metodo_forecast FROM exa_ppto_prod_config
        WHERE proy_id='$esc' AND Emp_Cod=" . (int)$Emp_Cod . " LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $m = $row['pco_metodo_forecast'];
        if ($m === 'produccion_proyectada') {
            return $m;
        }
        // promedio_historico | manual: ENUM sin logica � fallback produccion_proyectada
    }
    return 'produccion_proyectada';
}

/**
 * Calcula PF global (suma rubros driver) y KPIs plan/forecast separados.
 *
 * @param mysqli $mysqli
 * @param array $kpis Base desde exa_ppto_resumen (disponible = plan)
 * @param int $ppe_id
 * @param int $Emp_Cod
 * @param int $anio
 * @param int|null $mes
 * @param string|null $proy_id
 * @return array KPIs enriquecidos
 */
function ppto_forecast_calcular_global($mysqli, $kpis, $ppe_id, $Emp_Cod, $anio, $mes, $proy_id) {
    $comprometido = (float)$kpis['comprometido'];
    $ejecutado    = (float)$kpis['ejecutado'];
    $vigente      = (float)$kpis['presupuesto_vigente'];

    $disp_plan = round($vigente - $comprometido - $ejecutado, 2);
    $sem_plan  = ppto_forecast_semaforo($vigente, $disp_plan);

    $kpis['disponible_plan']             = $disp_plan;
    $kpis['disponible_plan_porcentaje']  = $sem_plan['disponible_porcentaje'];
    $kpis['semaforo_plan']               = $sem_plan['semaforo'];
    $kpis['presupuesto_proyectado']      = 0.00;
    $kpis['disponible_forecast']         = $disp_plan;
    $kpis['disponible_forecast_porcentaje'] = $sem_plan['disponible_porcentaje'];
    $kpis['semaforo_forecast']           = $sem_plan['semaforo'];
    $kpis['forecast_motor_version']      = 'v2';

    // disponible / semaforo legacy: plan (no se sobrescribe con forecast � D4)
    $kpis['disponible']            = $disp_plan;
    $kpis['disponible_porcentaje'] = $sem_plan['disponible_porcentaje'];
    $kpis['semaforo']              = $sem_plan['semaforo'];

    if (!$ppe_id || $proy_id === null || trim($proy_id) === '') {
        return $kpis;
    }

    $metodo = ppto_forecast_metodo_proyecto($mysqli, $proy_id, $Emp_Cod);
    if ($metodo !== 'produccion_proyectada') {
        return $kpis;
    }

    $meses = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $proy_id);

    if (!function_exists('ppto_dash_usd_por_ton_mes')) {
        return $kpis;
    }
    $usd_por_ton = ppto_dash_usd_por_ton_mes($mysqli, $ppe_id, $proy_id, $Emp_Cod);
    if ($usd_por_ton <= 0.0001) {
        return $kpis;
    }

    $factor_total = $usd_por_ton * 12.0;
    $ppto_pf = 0.0;
    if ($mes !== null) {
        $m = (int)$mes;
        if ($m >= 1 && $m <= 12) {
            $ppto_pf = ppto_forecast_pf_rubro_mes(
                ppto_forecast_ton_ref_mes($meses[$m]),
                $factor_total
            );
        }
    } else {
        for ($m = 1; $m <= 12; $m++) {
            $ppto_pf += ppto_forecast_pf_rubro_mes(
                ppto_forecast_ton_ref_mes($meses[$m]),
                $factor_total
            );
        }
        $ppto_pf = round($ppto_pf, 2);
    }

    $kpis['presupuesto_proyectado'] = round($ppto_pf, 2);
    $disp_fc = round($ppto_pf - $comprometido - $ejecutado, 2);
    $sem_fc  = ppto_forecast_semaforo($ppto_pf, $disp_fc);

    $kpis['disponible_forecast']             = $disp_fc;
    $kpis['disponible_forecast_porcentaje']  = $sem_fc['disponible_porcentaje'];
    $kpis['semaforo_forecast']               = $sem_fc['semaforo'];

    return $kpis;
}

/**
 * Tipo driver/fijo por partida y proyecto (sin columna ppa_driver_tipo en catalogo).
 * driver = rubro en exa_ppto_proyecto_detalles con factor; fijo = resto.
 *
 * @param int $ppa_id
 * @param array $rubros_map
 * @param bool $usa_toneladas
 * @return string driver|fijo
 */
function ppto_forecast_driver_tipo_partida($ppa_id, $rubros_map, $usa_toneladas) {
    if ($usa_toneladas && isset($rubros_map[(int)$ppa_id])) {
        return 'driver';
    }
    return 'fijo';
}

/**
 * Suma hijos detalle y clasifica rollup D7 (driver / fijo / mixto).
 *
 * @param array $detalle_ids
 * @param array $detalle_por_id
 * @return array
 */
function ppto_forecast_rollup_sumar_detalle($detalle_ids, $detalle_por_id) {
    $sum = array(
        'inicial' => 0.00,
        'reajustes' => 0.00,
        'vigente' => 0.00,
        'comprometido' => 0.00,
        'ejecutado' => 0.00,
        'disponible' => 0.00,
        'vigente_proyectado' => 0.00,
        'disponible_proyectado' => 0.00,
        'vigente_por_real' => 0.00,
        'disponible_por_real' => 0.00,
        'por_formalizar' => 0.00,
        'tiene_driver' => false,
        'tiene_fijo' => false,
    );

    foreach ($detalle_ids as $did) {
        if (!isset($detalle_por_id[$did])) {
            continue;
        }
        $d = $detalle_por_id[$did];
        $dt = isset($d['driver_tipo']) ? $d['driver_tipo'] : (empty($d['es_tonelada']) ? 'fijo' : 'driver');
        if ($dt === 'driver') {
            $sum['tiene_driver'] = true;
        } else {
            $sum['tiene_fijo'] = true;
        }
        $sum['inicial'] += (float)$d['inicial'];
        $sum['reajustes'] += (float)$d['reajustes'];
        $sum['vigente'] += (float)$d['vigente'];
        $sum['comprometido'] += (float)$d['comprometido'];
        $sum['ejecutado'] += (float)$d['ejecutado'];
        $sum['disponible'] += (float)$d['disponible'];
        $sum['vigente_proyectado'] += (float)$d['vigente_proyectado'];
        $sum['disponible_proyectado'] += (float)$d['disponible_proyectado'];
        $sum['vigente_por_real'] += (float)(isset($d['vigente_por_real']) ? $d['vigente_por_real'] : $d['vigente']);
        $sum['disponible_por_real'] += (float)(isset($d['disponible_por_real']) ? $d['disponible_por_real'] : $d['disponible']);
        $sum['por_formalizar'] += (float)(isset($d['por_formalizar']) ? $d['por_formalizar'] : 0);
    }

    foreach ($sum as $k => $v) {
        if ($k !== 'tiene_driver' && $k !== 'tiene_fijo') {
            $sum[$k] = round((float)$v, 2);
        }
    }

    return ppto_forecast_rollup_finalizar($sum);
}

/**
 * Aplica reglas D7 al total de capítulo: PF fijo=VA; mixto=suma hijos; % sobre totales.
 *
 * @param array $sum
 * @return array
 */
function ppto_forecast_rollup_finalizar($sum) {
    if (!empty($sum['tiene_driver']) && !empty($sum['tiene_fijo'])) {
        $rollup_tipo = 'mixto';
    } elseif (!empty($sum['tiene_driver'])) {
        $rollup_tipo = 'driver';
    } else {
        $rollup_tipo = 'fijo';
    }

    if ($rollup_tipo === 'fijo') {
        $sum['vigente_proyectado'] = $sum['vigente'];
        $sum['disponible_proyectado'] = $sum['disponible'];
        $sum['vigente_por_real'] = $sum['vigente'];
        $sum['disponible_por_real'] = $sum['disponible'];
        $sum['por_formalizar'] = 0.0;
    }

    $sum['por_formalizar'] = round(max(0.0, (float)$sum['vigente_por_real'] - (float)$sum['vigente']), 2);

    $sum['rollup_tipo'] = $rollup_tipo;
    $sum['rollup_mixto'] = ($rollup_tipo === 'mixto');
    $sum['es_tonelada'] = ($rollup_tipo !== 'fijo');
    $sum['muestra_pf'] = true;

    return $sum;
}

/**
 * % disponible consolidado sobre totales (nunca promedio de % hijos).
 *
 * @param float $vigente_ctrl
 * @param float $disp_ctrl
 * @return float
 */
function ppto_forecast_pct_sobre_total($vigente_ctrl, $disp_ctrl) {
    $sem = ppto_forecast_semaforo((float)$vigente_ctrl, (float)$disp_ctrl);
    return $sem['disponible_porcentaje'];
}

/**
 * Enriquece fila partida con PF rubro (driver) o VA (fijo).
 *
 * @param array $row
 * @param array $meses_prod
 * @param int|null $mes
 * @param array $rubros_map
 * @param bool $usa_toneladas
 * @param bool $modo_reinversion Si true: proyectado=ton proyectada x $/Ton; real formalizar=solo ton real
 * @param string|null $periodo_vista anual|acumulado|mes (Cuadro)
 * @return array
 */
function ppto_forecast_calcular_partida($row, $meses_prod, $mes, $rubros_map, $usa_toneladas, $modo_reinversion = false, $periodo_vista = null) {
    $ppa_key = (int)$row['ppa_id'];
    $row['vigente_plan'] = $row['vigente'];
    $row['disponible_plan'] = $row['disponible'];
    $row['vigente_proyectado'] = $row['vigente'];
    $row['disponible_proyectado'] = $row['disponible'];
    $row['vigente_por_real'] = $row['vigente'];
    $row['disponible_por_real'] = $row['disponible'];
    $row['por_formalizar'] = 0.0;
    $row['meses_cerrados'] = ppto_forecast_contar_meses_cerrados($meses_prod);
    $row['ton_proyectada'] = null;
    $row['factor_mensual'] = null;
    $row['es_tonelada'] = false;
    $row['driver_tipo'] = ppto_forecast_driver_tipo_partida($ppa_key, $rubros_map, $usa_toneladas);
    $row['rollup_tipo'] = null;
    $row['rollup_mixto'] = false;
    $row['muestra_pf'] = true;
    $row['semaforo_plan'] = 'verde';
    $row['semaforo_forecast'] = 'verde';

    $sem_plan = ppto_forecast_semaforo($row['vigente'], $row['disponible']);
    $row['semaforo_plan'] = $sem_plan['semaforo'];

    if ($usa_toneladas && isset($rubros_map[$ppa_key])) {
        $factor = $rubros_map[$ppa_key]['factor'];

        if ($modo_reinversion) {
            $vista_esc = ppto_forecast_cuadro_vista_sanitize($periodo_vista !== null ? $periodo_vista : 'anual');
            if ($vista_esc === 'anual') {
                $mes_esc = 1;
            } else {
                $mes_esc = ($mes !== null)
                    ? ppto_forecast_cuadro_mes_sanitize($mes)
                    : ppto_forecast_ultimo_mes_con_real($meses_prod);
            }
            // Proyectada = brújula. Formalizar = solo toneladas reales del periodo.
            $row['vigente_proyectado'] = ppto_forecast_pf_rubro_periodo_escenario(
                $meses_prod, $factor, 'proyectada', $vista_esc, $mes_esc
            );
            $row['vigente_por_real'] = ppto_forecast_pf_rubro_periodo_escenario(
                $meses_prod, $factor, 'solo_real', $vista_esc, $mes_esc
            );
        } else {
            $ton_base = isset($rubros_map[$ppa_key]['ton_base']) ? (float)$rubros_map[$ppa_key]['ton_base'] : 0.0;
            $pf_prod = ppto_forecast_pf_rubro_periodo($meses_prod, $mes, $factor);
            $pf_plan = ppto_forecast_pf_rubro_periodo_ton_fija($meses_prod, $mes, $factor, $ton_base);
            if ($pf_plan > 0.0001 && (float)$row['vigente'] > 0.0001) {
                $row['vigente_proyectado'] = round((float)$row['vigente'] * ($pf_prod / $pf_plan), 2);
            } else {
                $row['vigente_proyectado'] = $pf_prod;
            }
            $row['vigente_por_real'] = ppto_forecast_pf_rubro_periodo_solo_real($meses_prod, $mes, $factor);
        }

        $row['disponible_proyectado'] = round(
            $row['vigente_proyectado'] - $row['comprometido'] - $row['ejecutado'],
            2
        );
        $row['disponible_por_real'] = round(
            $row['vigente_por_real'] - $row['comprometido'] - $row['ejecutado'],
            2
        );
        $row['por_formalizar'] = ppto_reinversion_por_formalizar($row['vigente_por_real'], $row['vigente']);
        $row['factor_mensual'] = round($factor / 12.0, 4);
        $row['es_tonelada'] = true;
        $row['driver_tipo'] = 'driver';
        if ($mes !== null) {
            $m = (int)$mes;
            if ($m >= 1 && $m <= 12) {
                if ($modo_reinversion) {
                    $row['ton_proyectada'] = round(ppto_forecast_ton_proyectada_mes($meses_prod[$m]), 4);
                } else {
                    $row['ton_proyectada'] = round(ppto_forecast_ton_ref_mes($meses_prod[$m]), 4);
                }
            }
        }
        $sem_fc = ppto_forecast_semaforo($row['vigente_proyectado'], $row['disponible_proyectado']);
        $row['semaforo_forecast'] = $sem_fc['semaforo'];
        $row['disponible_porcentaje'] = $sem_fc['disponible_porcentaje'];
        $row['semaforo'] = $sem_fc['semaforo'];
    } else {
        $row['disponible_porcentaje'] = $sem_plan['disponible_porcentaje'];
        $row['semaforo'] = $sem_plan['semaforo'];
        $row['semaforo_forecast'] = $sem_plan['semaforo'];
    }

    return $row;
}
