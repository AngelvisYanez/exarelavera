<?php
/**
 * dashboard_logica.php
 * Capa de L�gica de Negocio para el Dashboard Presupuestario en EXA PPTO.
 * Procesa consultas consolidadas, sem�foros, KPI e integraci�n de producci�n.
 */

include_once(__DIR__ . '/ppto_persistencia_logica.php');
include_once(__DIR__ . '/ppto_motor_logica.php');
include_once(__DIR__ . '/ppto_motor_calculo.php');
include_once(__DIR__ . '/ppto_forecast_logica.php');
include_once(__DIR__ . '/ppto_divergencia_logica.php');
include_once(__DIR__ . '/ppto_alerta_pf_logica.php');
include_once(__DIR__ . '/ppto_proy_perfil_logica.php');
include_once(__DIR__ . '/ppto_partidas_logica.php');

if (file_exists(__DIR__ . '/ppto_reajustes_logica.php')) {
    include_once(__DIR__ . '/ppto_reajustes_logica.php');
}

/**
 * Condicion SQL: partidas activas e imputables (Detalle).
 *
 * @param string $alias_resumen Alias de exa_ppto_resumen (ej. r)
 * @return string
 */
function ppto_dash_sql_solo_partidas_activas($alias_resumen = 'r') {
    return "EXISTS (
        SELECT 1 FROM exa_ppto_partidas px
        WHERE px.ppa_id = {$alias_resumen}.ppa_id
          AND px.ppa_estado = 'A'
          AND COALESCE(NULLIF(px.ppa_clase, ''), 'D') = 'D'
    )";
}

/**
 * Condicion SQL de mes segun vista de periodo del Cuadro (anual|acumulado|mes).
 *
 * @param string $alias Alias tabla resumen
 * @param string $periodo_vista
 * @param int|null $mes
 * @return string
 */
function ppto_dash_sql_filtro_periodo($alias, $periodo_vista, $mes) {
    $vista = ppto_forecast_cuadro_vista_sanitize($periodo_vista);
    if ($vista === 'mes' && $mes !== null) {
        return $alias . '.mes = ' . (int)$mes;
    }
    if ($vista === 'acumulado' && $mes !== null) {
        return $alias . '.mes >= 1 AND ' . $alias . '.mes <= ' . (int)$mes;
    }
    return $alias . '.mes <= 12';
}

/**
 * Resuelve vista/mes de periodo para Dashboard (default acumulado hasta ultimo mes con real).
 *
 * @param array $filtros
 * @param array $meses_prod
 * @return array {periodo_vista, mes, label}
 */
function ppto_dash_periodo_resuelto($filtros, $meses_prod) {
    $vista = isset($filtros['periodo_vista'])
        ? ppto_forecast_cuadro_vista_sanitize($filtros['periodo_vista'])
        : 'acumulado';
    $mes = isset($filtros['mes']) && $filtros['mes'] !== null
        ? ppto_forecast_cuadro_mes_sanitize($filtros['mes'])
        : null;
    if ($vista !== 'anual' && $mes === null) {
        $mes = ppto_forecast_ultimo_mes_con_real($meses_prod);
    }
    if ($vista === 'anual') {
        $mes = null;
    }
    return array(
        'periodo_vista' => $vista,
        'mes' => $mes,
        'label' => ppto_forecast_cuadro_periodo_label($vista, $mes !== null ? $mes : (int)date('n')),
    );
}

/**
 * Carga produccion fisica mes a mes (1-12).
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $anio
 * @param string|null $proy_id
 * @return array
 */
function ppto_dash_cargar_produccion_meses($mysqli, $emp_id, $anio, $proy_id) {
    return ppto_forecast_cargar_produccion_meses($mysqli, $emp_id, $anio, $proy_id);
}

/**
 * Tonelada efectiva del mes: real, si no proyectada, si no esperada.
 *
 * @param array $md
 * @return float
 */
function ppto_dash_ton_efectiva_mes($md) {
    if ((float)$md['real'] > 0.0001) {
        return (float)$md['real'];
    }
    if ((float)$md['proyectada'] > 0.0001) {
        return (float)$md['proyectada'];
    }
    return (float)$md['esperada'];
}

/**
 * Referencia proyectada para comparar con el real (mes).
 *
 * @param array $md
 * @return float
 */
function ppto_dash_ton_proy_referencia_mes($md) {
    if ((float)$md['proyectada'] > 0.0001) {
        return (float)$md['proyectada'];
    }
    return (float)$md['esperada'];
}

/**
 * Tonelada de referencia para escalar presupuesto (proyectada, si no esperada).
 * No usa el real: el presupuesto se reajusta hacia adelante segun la proyeccion.
 *
 * @param array $md
 * @return float
 */
function ppto_dash_ton_para_presupuesto_mes($md) {
    return ppto_forecast_ton_ref_mes($md);
}

/**
 * Rubros tonelada-metrica del proyecto indexados por ppa_id.
 *
 * @param mysqli $mysqli
 * @param int $ppe_id
 * @param string|null $proy_id
 * @param int $emp_id
 * @return array
 */
function ppto_dash_cargar_rubros_proyecto($mysqli, $ppe_id, $proy_id, $emp_id) {
    $rubros = array();
    if (!$ppe_id || $proy_id === null || trim($proy_id) === '') {
        return $rubros;
    }
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $res = $mysqli->query("SELECT ppa_id, pdp_factor_anual_tonelada, pdp_toneladas_base
        FROM exa_ppto_proyecto_detalles
        WHERE emp_id = " . (int)$emp_id . " AND ppe_id = " . (int)$ppe_id . " AND proy_id = '$esc'
          AND pdp_factor_anual_tonelada > 0 AND pdp_toneladas_base > 0");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rubros[(int)$r['ppa_id']] = array(
                'factor'   => (float)$r['pdp_factor_anual_tonelada'],
                'ton_base' => (float)$r['pdp_toneladas_base']
            );
        }
    }
    return $rubros;
}

/**
 * Reescribe factores del mapa rubro para modo Relavera con la misma regla del Cuadro:
 * Base PDF = ton_costo × factor_BD; factor_escenario = Base PDF / ton_ingresos_mes.
 * Así Proyectada/Real del Dashboard coinciden con Gastos presup. del Cuadro.
 *
 * @param mysqli $mysqli
 * @param array $rubros_map
 * @param int $ppe_id
 * @param string $proy_id
 * @param int $emp_id
 * @return array
 */
function ppto_dash_rubros_map_factor_escenario_cuadro($mysqli, $rubros_map, $ppe_id, $proy_id, $emp_id) {
    if (empty($rubros_map) || $proy_id === null || trim($proy_id) === '') {
        return $rubros_map;
    }
    require_once __DIR__ . '/ppto_format_helpers.php';
    require_once __DIR__ . '/ppto_proyecto_version_logica.php';

    $cfg = ppto_proy_version_config($mysqli, trim($proy_id), (int)$emp_id, (int)$ppe_id);
    $ton_base_pdf = (float)$cfg['ton_mes'];
    if ($ton_base_pdf <= 0) {
        $ton_base_pdf = ppto_proy_version_ton_base($mysqli, trim($proy_id), (int)$emp_id, (int)$ppe_id);
    }
    $ton_esc_gasto_mes = ppto_proy_ton_escenario_gasto_mes($ton_base_pdf);
    $ton_costo_mes = ppto_proy_version_ton_costo($mysqli, trim($proy_id), (int)$emp_id, (int)$ppe_id);

    foreach ($rubros_map as $ppa_id => $info) {
        $factor_bd = (float)$info['factor'];
        if ($factor_bd <= 0.0001) {
            continue;
        }
        $esc_esperada_anual = round($ton_costo_mes * $factor_bd, 2);
        $rubros_map[$ppa_id]['factor_bd'] = $factor_bd;
        $rubros_map[$ppa_id]['factor'] = ppto_proy_factor_escenario_gasto($esc_esperada_anual, $ton_esc_gasto_mes);
    }
    return $rubros_map;
}

/**
 * Presupuesto mensual de un rubro segun toneladas proyectadas.
 *
 * @param float $ton_mes
 * @param float $factor_anual
 * @return float
 */
function ppto_dash_ppto_rubro_mes($ton_mes, $factor_anual) {
    return ppto_forecast_pf_rubro_mes($ton_mes, $factor_anual);
}

/**
 * Presupuesto proyectado de un rubro para el periodo filtrado.
 *
 * @param array $meses_prod
 * @param int|null $mes_filtro
 * @param float $factor_anual
 * @return float
 */
function ppto_dash_ppto_rubro_periodo($meses_prod, $mes_filtro, $factor_anual) {
    return ppto_forecast_pf_rubro_periodo($meses_prod, $mes_filtro, $factor_anual);
}

/**
 * USD/mes por tonelada = suma de factores anuales de rubros / 12.
 *
 * @param mysqli $mysqli
 * @param int $ppe_id
 * @param string|null $proy_id
 * @param int $emp_id
 * @return float
 */
function ppto_dash_usd_por_ton_mes($mysqli, $ppe_id, $proy_id, $emp_id) {
    if (!$ppe_id || $proy_id === null || trim($proy_id) === '') {
        return 0.0;
    }
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $res = $mysqli->query("SELECT COALESCE(SUM(pdp_factor_anual_tonelada / 12), 0) AS usd_ton
        FROM exa_ppto_proyecto_detalles
        WHERE emp_id = " . (int)$emp_id . " AND ppe_id = " . (int)$ppe_id . " AND proy_id = '$esc'");
    if ($res && ($row = $res->fetch_assoc())) {
        return (float)$row['usd_ton'];
    }
    return 0.0;
}

/**
 * Ultimo mes con produccion real > 0.
 *
 * @param array $meses
 * @return int
 */
function ppto_dash_ultimo_mes_real($meses) {
    $ult = 0;
    for ($m = 1; $m <= 12; $m++) {
        if ((float)$meses[$m]['real'] > 0.0001) {
            $ult = $m;
        }
    }
    return $ult;
}

/**
 * Consolida KPIs de produccion segun toneladas proyectadas.
 *
 * @param array $meses
 * @param int|null $mes_filtro
 * @return array
 */
function ppto_dash_prod_kpis_desde_meses($meses, $mes_filtro) {
    $out = array(
        'prod_esperada'       => 0.0,
        'prod_real'           => 0.0,
        'prod_proyectada'     => 0.0,
        'prod_proy_referencia'=> 0.0,
        'prod_var_absoluta'   => 0.0,
        'prod_var_porcentual' => 0.0,
        'prod_modo'           => 'anual'
    );

    if ($mes_filtro !== null) {
        $m = (int)$mes_filtro;
        if ($m < 1 || $m > 12) {
            return $out;
        }
        $md = $meses[$m];
        $proy_ref = ppto_dash_ton_proy_referencia_mes($md);
        $out['prod_modo'] = 'mes';
        $out['prod_esperada'] = round((float)$md['esperada'], 4);
        $out['prod_real'] = round((float)$md['real'], 4);
        $out['prod_proyectada'] = round($proy_ref, 4);
        $out['prod_proy_referencia'] = round($proy_ref, 4);
        $out['prod_var_absoluta'] = round((float)$md['real'] - $proy_ref, 4);
        if ($proy_ref > 0.0001) {
            $out['prod_var_porcentual'] = round(($out['prod_var_absoluta'] / $proy_ref) * 100.0, 2);
        } elseif ((float)$md['real'] > 0.0001) {
            $out['prod_var_porcentual'] = 100.0;
        }
        return $out;
    }

    $mes_ult_real = ppto_dash_ultimo_mes_real($meses);
    $esp_total = 0.0;
    $real_total = 0.0;
    $real_ytd = 0.0;
    $proy_ytd = 0.0;
    $cierre_anual = 0.0;

    for ($m = 1; $m <= 12; $m++) {
        $esp_total += (float)$meses[$m]['esperada'];
        $real_total += (float)$meses[$m]['real'];
        $cierre_anual += ppto_dash_ton_efectiva_mes($meses[$m]);
        if ($mes_ult_real > 0 && $m <= $mes_ult_real) {
            $real_ytd += (float)$meses[$m]['real'];
            $proy_ytd += ppto_dash_ton_proy_referencia_mes($meses[$m]);
        }
    }

    $out['prod_modo'] = 'anual';
    $out['prod_esperada'] = round($esp_total, 4);
    $out['prod_real'] = round($real_total, 4);
    $out['prod_proyectada'] = round($cierre_anual, 4);
    $out['prod_proy_referencia'] = round($proy_ytd, 4);
    $out['prod_var_absoluta'] = round($real_ytd - $proy_ytd, 4);
    if ($proy_ytd > 0.0001) {
        $out['prod_var_porcentual'] = round(($out['prod_var_absoluta'] / $proy_ytd) * 100.0, 2);
    } elseif ($real_ytd > 0.0001) {
        $out['prod_var_porcentual'] = 100.0;
    }

    return $out;
}

/**
 * KPIs de produccion acumulados ene..mes_corte (vista Acumulado del Cuadro).
 *
 * @param array $meses
 * @param int $mes_corte
 * @return array
 */
function ppto_dash_prod_kpis_acumulado($meses, $mes_corte) {
    $mes_corte = ppto_forecast_cuadro_mes_sanitize($mes_corte);
    $out = array(
        'prod_esperada' => 0.0,
        'prod_real' => 0.0,
        'prod_proyectada' => 0.0,
        'prod_proy_referencia' => 0.0,
        'prod_var_absoluta' => 0.0,
        'prod_var_porcentual' => 0.0,
        'prod_modo' => 'acumulado',
    );
    $esp = 0.0;
    $real = 0.0;
    $proy_ref = 0.0;
    $cierre = 0.0;
    for ($m = 1; $m <= $mes_corte; $m++) {
        $md = isset($meses[$m]) ? $meses[$m] : array('esperada' => 0.0, 'real' => 0.0, 'proyectada' => 0.0);
        $esp += (float)$md['esperada'];
        $real += (float)$md['real'];
        $proy_ref += ppto_dash_ton_proy_referencia_mes($md);
        $cierre += ppto_dash_ton_efectiva_mes($md);
    }
    $out['prod_esperada'] = round($esp, 4);
    $out['prod_real'] = round($real, 4);
    $out['prod_proyectada'] = round($cierre, 4);
    $out['prod_proy_referencia'] = round($proy_ref, 4);
    $out['prod_var_absoluta'] = round($real - $proy_ref, 4);
    if ($proy_ref > 0.0001) {
        $out['prod_var_porcentual'] = round(($out['prod_var_absoluta'] / $proy_ref) * 100.0, 2);
    } elseif ($real > 0.0001) {
        $out['prod_var_porcentual'] = 100.0;
    }
    return $out;
}

/**
 * Obtiene el resumen de KPIs consolidados del presupuesto y la producci�n f�sica.
 *
 * @param mysqli $mysqli Conexi�n activa a la BD.
 * @param array $filtros Filtros validados de entrada.
 * @return array Arreglo de KPIs consolidados con sus variaciones y sem�foros.
 */
function ppto_dash_kpis($mysqli, $filtros) {
    $emp_id  = $filtros['emp_id'];
    $anio    = $filtros['anio'];
    $mes     = $filtros['mes'];
    $ppe_id  = $filtros['ppe_id'];
    $proy_id = $filtros['proy_id'];
    $ppa_id  = $filtros['ppa_id'];

    // 1. Si no hay ppe_id especificado, resolvemos el activo para ese a�o
    if (!$ppe_id) {
        $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('emp_id' => $emp_id, 'ppe_anio' => $anio));
    }

    $kpis = array(
        'presupuesto_inicial'   => 0.00,
        'total_reajustes'       => 0.00,
        'presupuesto_vigente'   => 0.00,
        'comprometido'          => 0.00,
        'ejecutado'             => 0.00,
        'disponible'            => 0.00,
        'disponible_porcentaje' => 0.00,
        'semaforo'              => 'verde', // verde, amarillo, rojo
        'prod_esperada'         => 0.00,
        'prod_real'             => 0.00,
        'prod_proyectada'       => 0.00,
        'prod_unidad'           => 'Ton',
        'prod_var_absoluta'     => 0.00,
        'prod_var_porcentual'   => 0.00,
        'prod_modo'             => 'anual',
        'presupuesto_proyectado'=> 0.00,
        'disponible_plan'       => 0.00,
        'disponible_plan_porcentaje' => 0.00,
        'disponible_forecast'   => 0.00,
        'disponible_forecast_porcentaje' => 0.00,
        'semaforo_plan'         => 'verde',
        'semaforo_forecast'     => 'verde',
        'forecast_motor_version'=> 'v2'
    );

    if (!$ppe_id) {
        return $kpis; // Sin presupuesto cargado, retornamos ceros seguros
    }

    // 2. Construir la consulta dinamica sobre la vista consolidada exa_ppto_resumen
    $meses_prod_tmp = ppto_dash_cargar_produccion_meses($mysqli, $emp_id, $anio, $proy_id);
    $periodo = ppto_dash_periodo_resuelto($filtros, $meses_prod_tmp);
    $mes = $periodo['mes'];
    $periodo_vista = $periodo['periodo_vista'];

    $condiciones = array("r.emp_id = $emp_id", "r.ppe_id = $ppe_id");
    $condiciones[] = ppto_dash_sql_filtro_periodo('r', $periodo_vista, $mes);
    if ($proy_id !== null) {
        $condiciones[] = "r.proy_id = '" . $mysqli->real_escape_string($proy_id) . "'";
    } else {
        $condiciones[] = "(r.proy_id IS NULL OR r.proy_id = '')";
    }
    if ($ppa_id !== null) {
        $condiciones[] = "r.ppa_id = $ppa_id";
    }
    $condiciones[] = ppto_dash_sql_solo_partidas_activas('r');

    $where_sql = implode(" AND ", $condiciones);
    $sql_res = "SELECT 
                    SUM(r.inicial) AS total_inicial,
                    SUM(r.reajustes) AS total_reajustes,
                    SUM(r.vigente) AS total_vigente,
                    SUM(r.comprometido) AS total_comprometido,
                    SUM(r.ejecutado) AS total_ejecutado,
                    SUM(r.disponible) AS total_disponible
                FROM exa_ppto_resumen r
                WHERE $where_sql";

    $res = $mysqli->query($sql_res);
    if ($res && $row = $res->fetch_assoc()) {
        $kpis['presupuesto_inicial'] = round((float)$row['total_inicial'], 2);
        $kpis['total_reajustes']     = round((float)$row['total_reajustes'], 2);
        $kpis['presupuesto_vigente'] = round((float)$row['total_vigente'], 2);
        $kpis['comprometido']        = round((float)$row['total_comprometido'], 2);
        $kpis['ejecutado']           = round((float)$row['total_ejecutado'], 2);
        $kpis['disponible']          = round((float)$row['total_disponible'], 2);
    }

    // 3. Produccion fisica segun periodo
    $meses_prod = $meses_prod_tmp;
    $prod_k = ppto_dash_prod_kpis_desde_meses($meses_prod, ($periodo_vista === 'mes') ? $mes : null);
    if ($periodo_vista === 'acumulado' && $mes !== null) {
        $prod_k = ppto_dash_prod_kpis_acumulado($meses_prod, $mes);
    }
    $kpis['prod_esperada'] = $prod_k['prod_esperada'];
    $kpis['prod_real'] = $prod_k['prod_real'];
    $kpis['prod_proyectada'] = $prod_k['prod_proyectada'];
    $kpis['prod_var_absoluta'] = $prod_k['prod_var_absoluta'];
    $kpis['prod_var_porcentual'] = $prod_k['prod_var_porcentual'];
    $kpis['prod_modo'] = $prod_k['prod_modo'];
    $kpis['periodo_vista'] = $periodo_vista;
    $kpis['periodo_mes'] = $mes;
    $kpis['periodo_label'] = $periodo['label'];

    // 4. Motor unico forecast (Fase 2B): plan y forecast separados en servidor
    $kpis = ppto_forecast_calcular_global($mysqli, $kpis, $ppe_id, $emp_id, $anio, ($periodo_vista === 'mes') ? $mes : null, $proy_id);

    // 5. Divergencia D2: plan produccion vs toneladas base proyecto (Fase 3)
    $kpis['divergencia_d2'] = array(
        'alineado' => true,
        'warning' => false,
        'mensaje' => '',
        'ton_esperada_anual' => 0.0,
        'ton_base_mensual' => 0.0,
        'ton_base_anual' => 0.0,
        'pct_diferencia' => 0.0,
        'umbral_pct' => PPTO_DIVERGENCIA_UMBRAL_DEFAULT,
        'rubro_driver' => '',
        'alineado_sn' => 'S',
    );
    if ($proy_id !== null && trim($proy_id) !== '') {
        $d2 = ppto_divergencia_comparar_toneladas($mysqli, $proy_id, $emp_id, $anio, $ppe_id);
        $kpis['divergencia_d2'] = array(
            'alineado' => $d2['alineado'],
            'warning' => $d2['warning'],
            'mensaje' => $d2['mensaje'],
            'ton_esperada_anual' => $d2['ton_esperada_anual'],
            'ton_base_mensual' => $d2['ton_base_mensual'],
            'ton_base_anual' => $d2['ton_base_anual'],
            'pct_diferencia' => $d2['pct_diferencia'],
            'umbral_pct' => $d2['umbral_pct'],
            'rubro_driver' => $d2['rubro_driver'],
            'alineado_sn' => $d2['alineado'] ? 'S' : 'N',
        );
    }

    return $kpis;
}

/**
 * Calcula semaforo y porcentaje disponible de una fila presupuestaria.
 *
 * @param float $vigente_ctrl
 * @param float $disp_ctrl
 * @return array
 */
function ppto_dash_partida_semaforo($vigente_ctrl, $disp_ctrl) {
    return ppto_forecast_semaforo($vigente_ctrl, $disp_ctrl);
}

/**
 * Enriquece una fila de partida con proyeccion por toneladas y semaforo.
 *
 * @param array $row
 * @param array $meses_prod
 * @param int|null $mes
 * @param array $rubros_map
 * @param bool $usa_toneladas
 * @param bool $modo_reinversion
 * @return array
 */
function ppto_dash_partida_enriquecer($row, $meses_prod, $mes, $rubros_map, $usa_toneladas, $modo_reinversion = false, $periodo_vista = null) {
    return ppto_forecast_calcular_partida($row, $meses_prod, $mes, $rubros_map, $usa_toneladas, $modo_reinversion, $periodo_vista);
}

/**
 * IDs de partidas Detalle descendientes de un nodo Grupo.
 *
 * @param int $ppa_id
 * @param array $children_by_parent
 * @param array $clase_by_id
 * @return array
 */
function ppto_dash_descendientes_detalle_ids($ppa_id, $children_by_parent, $clase_by_id) {
    $ids = array();
    if (!isset($children_by_parent[$ppa_id])) {
        return $ids;
    }
    foreach ($children_by_parent[$ppa_id] as $child_id) {
        $child_id = (int)$child_id;
        $clase = isset($clase_by_id[$child_id]) ? $clase_by_id[$child_id] : 'D';
        if ($clase === 'D') {
            $ids[] = $child_id;
        } else {
            $ids = array_merge($ids, ppto_dash_descendientes_detalle_ids($child_id, $children_by_parent, $clase_by_id));
        }
    }
    return $ids;
}

/**
 * Suma montos de filas detalle para construir totales de grupo.
 *
 * @param array $detalle_ids
 * @param array $detalle_por_id
 * @return array
 */
function ppto_dash_sumar_filas_detalle($detalle_ids, $detalle_por_id) {
    return ppto_forecast_rollup_sumar_detalle($detalle_ids, $detalle_por_id);
}

/**
 * Arma filas jerarquicas: Grupo (rollup) + Detalle hijas.
 *
 * @param array|null $parent_id
 * @param int $depth
 * @param array $children_by_parent
 * @param array $meta_by_id
 * @param array $clase_by_id
 * @param array $detalle_por_id
 * @param array $roots
 * @return array
 */
function ppto_dash_emitir_filas_arbol($parent_id, $depth, $children_by_parent, $meta_by_id, $clase_by_id, $detalle_por_id, $roots) {
    $out = array();
    $nodes = ($parent_id === null) ? $roots : (isset($children_by_parent[$parent_id]) ? $children_by_parent[$parent_id] : array());

    usort($nodes, function ($a, $b) use ($meta_by_id) {
        $ca = isset($meta_by_id[$a]['codigo']) ? $meta_by_id[$a]['codigo'] : '';
        $cb = isset($meta_by_id[$b]['codigo']) ? $meta_by_id[$b]['codigo'] : '';
        return strcmp($ca, $cb);
    });

    foreach ($nodes as $ppa_id) {
        $ppa_id = (int)$ppa_id;
        if (!isset($meta_by_id[$ppa_id])) {
            continue;
        }
        $meta = $meta_by_id[$ppa_id];
        $clase = isset($clase_by_id[$ppa_id]) ? $clase_by_id[$ppa_id] : 'D';

        if ($clase === 'G') {
            $hijos_det = ppto_dash_descendientes_detalle_ids($ppa_id, $children_by_parent, $clase_by_id);
            $sum = ppto_dash_sumar_filas_detalle($hijos_det, $detalle_por_id);
            $tiene_mov = (
                abs($sum['inicial']) + abs($sum['reajustes']) + abs($sum['vigente'])
                + abs($sum['comprometido']) + abs($sum['ejecutado'])
            ) > 0.0001;

            if ($tiene_mov || !empty($children_by_parent[$ppa_id])) {
                $sem_plan = ppto_forecast_semaforo($sum['vigente'], $sum['disponible']);
                $vig_fc = $sum['vigente_proyectado'];
                $disp_fc = $sum['disponible_proyectado'];
                $sem_fc = ppto_forecast_semaforo($vig_fc, $disp_fc);

                $row = array(
                    'ppa_id' => $ppa_id,
                    'codigo' => $meta['codigo'],
                    'descripcion' => $meta['descripcion'],
                    'inicial' => $sum['inicial'],
                    'reajustes' => $sum['reajustes'],
                    'vigente' => $sum['vigente'],
                    'comprometido' => $sum['comprometido'],
                    'ejecutado' => $sum['ejecutado'],
                    'disponible' => $sum['disponible'],
                    'vigente_plan' => $sum['vigente'],
                    'disponible_plan' => $sum['disponible'],
                    'vigente_proyectado' => $sum['vigente_proyectado'],
                    'disponible_proyectado' => $sum['disponible_proyectado'],
                    'vigente_por_real' => isset($sum['vigente_por_real']) ? $sum['vigente_por_real'] : $sum['vigente'],
                    'disponible_por_real' => isset($sum['disponible_por_real']) ? $sum['disponible_por_real'] : $sum['disponible'],
                    'por_formalizar' => isset($sum['por_formalizar']) ? $sum['por_formalizar'] : 0.0,
                    'ton_proyectada' => null,
                    'factor_mensual' => null,
                    'es_tonelada' => $sum['es_tonelada'],
                    'driver_tipo' => null,
                    'rollup_tipo' => $sum['rollup_tipo'],
                    'rollup_mixto' => $sum['rollup_mixto'],
                    'muestra_pf' => $sum['muestra_pf'],
                    'disponible_porcentaje' => $sem_fc['disponible_porcentaje'],
                    'semaforo' => $sem_fc['semaforo'],
                    'semaforo_plan' => $sem_plan['semaforo'],
                    'semaforo_forecast' => $sem_fc['semaforo'],
                    'es_grupo' => true,
                    'nivel_indent' => $depth
                );
                $out[] = $row;
            }

            $out = array_merge(
                $out,
                ppto_dash_emitir_filas_arbol($ppa_id, $depth + 1, $children_by_parent, $meta_by_id, $clase_by_id, $detalle_por_id, $roots)
            );
        } elseif (isset($detalle_por_id[$ppa_id])) {
            $row = $detalle_por_id[$ppa_id];
            $row['es_grupo'] = false;
            $row['nivel_indent'] = $depth;
            $out[] = $row;
        }
    }

    return $out;
}

/**
 * Obtiene el listado consolidado de partidas presupuestarias afectadas por los filtros.
 *
 * @param mysqli $mysqli Conexi�n activa.
 * @param array $filtros Filtros validados.
 * @return array Colecci�n de partidas con balances, variaciones y sem�foros.
 */
function ppto_dash_resumen_partidas($mysqli, $filtros) {
    $emp_id  = $filtros['emp_id'];
    $anio    = $filtros['anio'];
    $mes     = $filtros['mes'];
    $ppe_id  = $filtros['ppe_id'];
    $proy_id = $filtros['proy_id'];
    $ppa_id  = $filtros['ppa_id'];

    if (!$ppe_id) {
        $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('emp_id' => $emp_id, 'ppe_anio' => $anio));
    }

    $partidas = array();
    if (!$ppe_id) {
        return $partidas;
    }

    $meses_prod = array();
    $rubros_map = array();
    $usa_toneladas = false;
    $modo_reinversion = false;
    $periodo_vista = isset($filtros['periodo_vista']) ? $filtros['periodo_vista'] : 'acumulado';
    if ($proy_id !== null && trim($proy_id) !== '') {
        $meses_prod = ppto_dash_cargar_produccion_meses($mysqli, $emp_id, $anio, $proy_id);
        $periodo = ppto_dash_periodo_resuelto($filtros, $meses_prod);
        $periodo_vista = $periodo['periodo_vista'];
        $mes = $periodo['mes'];
        $rubros_map = ppto_dash_cargar_rubros_proyecto($mysqli, $ppe_id, $proy_id, $emp_id);
        $usa_toneladas = !empty($rubros_map);
        $modo_reinversion = ppto_proy_es_modo_reinversion($mysqli, $proy_id, $emp_id);
        if ($modo_reinversion && $usa_toneladas) {
            // Misma escala $/Ton que escenario Proyectada/Real del Cuadro presupuestario.
            $rubros_map = ppto_dash_rubros_map_factor_escenario_cuadro(
                $mysqli, $rubros_map, $ppe_id, $proy_id, $emp_id
            );
        }
    } else {
        $periodo = ppto_dash_periodo_resuelto($filtros, array());
        $periodo_vista = $periodo['periodo_vista'];
        $mes = $periodo['mes'];
    }

    $condiciones = array("r.emp_id = $emp_id", "r.ppe_id = $ppe_id");
    $condiciones[] = ppto_dash_sql_filtro_periodo('r', $periodo_vista, $mes);
    if ($proy_id !== null) {
        $condiciones[] = "r.proy_id = '" . $mysqli->real_escape_string($proy_id) . "'";
    }
    if ($ppa_id !== null) {
        $condiciones[] = "r.ppa_id = $ppa_id";
    }

    $where_sql = implode(" AND ", $condiciones);
    $sql = "SELECT 
                r.ppa_id,
                p.ppa_codigo_clasificacion AS codigo,
                p.ppa_descripcion AS descripcion,
                SUM(r.inicial) AS inicial,
                SUM(r.reajustes) AS reajustes,
                SUM(r.vigente) AS vigente,
                SUM(r.comprometido) AS comprometido,
                SUM(r.ejecutado) AS ejecutado,
                SUM(r.disponible) AS disponible
            FROM exa_ppto_resumen r
            INNER JOIN exa_ppto_partidas p ON r.ppa_id = p.ppa_id
            WHERE $where_sql
              AND p.ppa_estado = 'A'
              AND COALESCE(NULLIF(p.ppa_clase, ''), 'D') = 'D'
            GROUP BY r.ppa_id, p.ppa_codigo_clasificacion, p.ppa_descripcion
            HAVING (
                ABS(SUM(r.inicial)) + ABS(SUM(r.reajustes)) + ABS(SUM(r.vigente))
                + ABS(SUM(r.comprometido)) + ABS(SUM(r.ejecutado))
            ) > 0.0001
            ORDER BY p.ppa_codigo_clasificacion ASC";

    $detalle_por_id = array();
    $res = $mysqli->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['inicial']      = round((float)$row['inicial'], 2);
            $row['reajustes']    = round((float)$row['reajustes'], 2);
            $row['vigente']      = round((float)$row['vigente'], 2);
            $row['comprometido'] = round((float)$row['comprometido'], 2);
            $row['ejecutado']    = round((float)$row['ejecutado'], 2);
            $row['disponible']   = round((float)$row['disponible'], 2);
            $row = ppto_dash_partida_enriquecer(
                $row, $meses_prod, $mes, $rubros_map, $usa_toneladas, $modo_reinversion, $periodo_vista
            );
            $detalle_por_id[(int)$row['ppa_id']] = $row;
        }
    }

    if (empty($detalle_por_id)) {
        return $partidas;
    }

    $vista = isset($filtros['vista_partidas']) ? $filtros['vista_partidas'] : 'jerarquica';
    if ($vista === 'plana') {
        $partidas = array_values($detalle_por_id);
        usort($partidas, function ($a, $b) {
            return strcmp($a['codigo'], $b['codigo']);
        });
        foreach ($partidas as $idx => $row) {
            $partidas[$idx]['es_grupo'] = false;
            $partidas[$idx]['nivel_indent'] = 0;
        }
        return $partidas;
    }

    $catalogo = ppto_partidas_listar($mysqli, array('emp_id' => $emp_id, 'solo_activas' => true));
    $children_by_parent = array();
    $meta_by_id = array();
    $clase_by_id = array();
    $roots = array();

    foreach ($catalogo as $p) {
        $pid = (int)$p['ppa_id'];
        $pad = isset($p['ppa_padre_id']) && $p['ppa_padre_id'] !== '' && $p['ppa_padre_id'] !== null
            ? (int)$p['ppa_padre_id'] : 0;
        $clase = (isset($p['ppa_clase']) && $p['ppa_clase'] === 'G') ? 'G' : 'D';

        $meta_by_id[$pid] = array(
            'codigo' => $p['ppa_codigo_clasificacion'],
            'descripcion' => $p['ppa_descripcion']
        );
        $clase_by_id[$pid] = $clase;

        if ($pad > 0) {
            if (!isset($children_by_parent[$pad])) {
                $children_by_parent[$pad] = array();
            }
            $children_by_parent[$pad][] = $pid;
        } else {
            $roots[] = $pid;
        }
    }

    usort($roots, function ($a, $b) use ($meta_by_id) {
        $ca = isset($meta_by_id[$a]['codigo']) ? $meta_by_id[$a]['codigo'] : '';
        $cb = isset($meta_by_id[$b]['codigo']) ? $meta_by_id[$b]['codigo'] : '';
        return strcmp($ca, $cb);
    });

    return ppto_dash_emitir_filas_arbol(
        null,
        0,
        $children_by_parent,
        $meta_by_id,
        $clase_by_id,
        $detalle_por_id,
        $roots
    );
}

/**
 * Obtiene la evoluci�n mensual del presupuesto y la producci�n f�sica (meses 1 a 12).
 *
 * @param mysqli $mysqli Conexi�n activa.
 * @param array $filtros Filtros validados.
 * @return array Listado de evoluci�n indexado por mes (1-12) con balances de presupuesto y producci�n.
 */
function ppto_dash_evolucion_mensual($mysqli, $filtros) {
    $emp_id  = $filtros['emp_id'];
    $anio    = $filtros['anio'];
    $ppe_id  = $filtros['ppe_id'];
    $proy_id = $filtros['proy_id'];
    $ppa_id  = $filtros['ppa_id'];

    if (!$ppe_id) {
        $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('emp_id' => $emp_id, 'ppe_anio' => $anio));
    }

    // Inicializar el arreglo de meses (1 a 12) con valores seguros en 0
    $mensual = array();
    $nombres_meses = array(
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    );
    for ($m = 1; $m <= 12; $m++) {
        $mensual[$m] = array(
            'mes'             => $m,
            'nombre_mes'      => $nombres_meses[$m],
            'inicial'         => 0.00,
            'reajustes'       => 0.00,
            'vigente'         => 0.00,
            'comprometido'    => 0.00,
            'ejecutado'       => 0.00,
            'disponible'      => 0.00,
            'prod_esperada'   => 0.00,
            'prod_real'       => 0.00,
            'prod_proyectada' => 0.00,
            'prod_real_acum'  => 0.00,
            'prod_proy_acum'  => 0.00,
            'prod_var_acum'   => 0.00,
            'prod_avance_pct' => null,
            'prod_var_fisica' => 0.00,
            'tiene_real'      => false,
            'ppto_proyectado' => 0.00
        );
    }

    if (!$ppe_id) {
        return array_values($mensual);
    }

    $meses_prod = ppto_dash_cargar_produccion_meses($mysqli, $emp_id, $anio, $proy_id);
    $usd_por_ton = ppto_dash_usd_por_ton_mes($mysqli, $ppe_id, $proy_id, $emp_id);
    $modo_reinversion = ($proy_id !== null && trim($proy_id) !== ''
        && ppto_proy_es_modo_reinversion($mysqli, $proy_id, $emp_id));

    // 1. Obtener la evolucion del presupuesto desde exa_ppto_resumen
    $condiciones = array("r.emp_id = $emp_id", "r.ppe_id = $ppe_id", "r.mes <= 12");
    if ($proy_id !== null) {
        $condiciones[] = "r.proy_id = '" . $mysqli->real_escape_string($proy_id) . "'";
    } else {
        $condiciones[] = "(r.proy_id IS NULL OR r.proy_id = '')";
    }
    if ($ppa_id !== null) {
        $condiciones[] = "r.ppa_id = $ppa_id";
    }
    $condiciones[] = ppto_dash_sql_solo_partidas_activas('r');
    $where_sql = implode(" AND ", $condiciones);

    $sql_ppto = "SELECT 
                    r.mes,
                    SUM(r.inicial) AS inicial,
                    SUM(r.reajustes) AS reajustes,
                    SUM(r.vigente) AS vigente,
                    SUM(r.comprometido) AS comprometido,
                    SUM(r.ejecutado) AS ejecutado,
                    SUM(r.disponible) AS disponible
                 FROM exa_ppto_resumen r
                 WHERE $where_sql
                 GROUP BY r.mes
                 ORDER BY r.mes ASC";
    $res_ppto = $mysqli->query($sql_ppto);
    if ($res_ppto) {
        while ($row = $res_ppto->fetch_assoc()) {
            $m = (int)$row['mes'];
            if (isset($mensual[$m])) {
                $mensual[$m]['inicial']      = round((float)$row['inicial'], 2);
                $mensual[$m]['reajustes']    = round((float)$row['reajustes'], 2);
                $mensual[$m]['vigente']      = round((float)$row['vigente'], 2);
                $mensual[$m]['comprometido'] = round((float)$row['comprometido'], 2);
                $mensual[$m]['ejecutado']    = round((float)$row['ejecutado'], 2);
                $mensual[$m]['disponible']   = round((float)$row['disponible'], 2);
            }
        }
    }

    // 2. Produccion fisica, acumulados y presupuesto segun toneladas proyectadas
    $mes_ult_real = ppto_dash_ultimo_mes_real($meses_prod);
    $acum_real = 0.0;
    $acum_proy = 0.0;

    for ($m = 1; $m <= 12; $m++) {
        $md = $meses_prod[$m];
        $proy_ref = ppto_dash_ton_proy_referencia_mes($md);
        $real_mes = (float)$md['real'];

        $acum_real += $real_mes;
        $acum_proy += $proy_ref;

        $mensual[$m]['prod_esperada'] = round((float)$md['esperada'], 4);
        $mensual[$m]['prod_real'] = round($real_mes, 4);
        $mensual[$m]['prod_proyectada'] = round($proy_ref, 4);
        $mensual[$m]['prod_real_acum'] = round($acum_real, 4);
        $mensual[$m]['prod_proy_acum'] = round($acum_proy, 4);

        if ($mes_ult_real > 0 && $m <= $mes_ult_real) {
            $mensual[$m]['prod_var_acum'] = round($acum_real - $acum_proy, 4);
            if ($acum_proy > 0.0001) {
                $mensual[$m]['prod_avance_pct'] = round(($acum_real / $acum_proy) * 100.0, 1);
            } else {
                $mensual[$m]['prod_avance_pct'] = null;
            }
        } else {
            $mensual[$m]['prod_var_acum'] = null;
            $mensual[$m]['prod_avance_pct'] = null;
        }

        $mensual[$m]['tiene_real'] = ($real_mes > 0.0001);
        if ($real_mes > 0.0001) {
            $mensual[$m]['prod_var_fisica'] = round($real_mes - $proy_ref, 4);
        } else {
            $mensual[$m]['prod_var_fisica'] = null;
        }

        if ($usd_por_ton > 0.0001) {
            $ton_ppto = $modo_reinversion
                ? ppto_forecast_ton_proyectada_mes($md)
                : ppto_dash_ton_para_presupuesto_mes($md);
            $mensual[$m]['ppto_proyectado'] = round(
                ppto_dash_ppto_rubro_mes($ton_ppto, $usd_por_ton * 12.0),
                2
            );
        }
    }

    return array_values($mensual);
}
