<?php
/**
 * dashboard_logica.php
 * Capa de Lógica de Negocio para el Dashboard Presupuestario en EXA PPTO.
 * Procesa consultas consolidadas, semáforos, KPI e integración de producción.
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
 * Condición SQL: partidas activas e imputables (Detalle).
 *
 * @param string $alias_resumen Alias de exa_ppto_resumen (ej. r)
 * @return string
 */
function ppto_dash_sql_solo_partidas_activas($alias_resumen = 'r') {
    return "EXISTS (
        SELECT 1 FROM pre_partidas px
        WHERE (px.Ppa_Cod = {$alias_resumen}.ppa_id OR px.Ppa_Cod = {$alias_resumen}.Ppa_Cod)
          AND px.Ppa_Est = 'A'
          AND COALESCE(NULLIF(px.Ppa_Clase, ''), 'D') = 'D'
    )";
}

/**
 * Condición SQL de mes según vista de periodo del Cuadro (anual|acumulado|mes).
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
 * Resuelve vista/mes de periodo para Dashboard (default acumulado hasta último mes con real).
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
 * Carga producción física mes a mes (1-12).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $anio
 * @param string|null $proy_id
 * @return array
 */
function ppto_dash_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $proy_id) {
    return ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $proy_id);
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
 * Muestra el resumen ejecutivo general de KPI para el Dashboard Presupuestario.
 *
 * @param mysqli $mysqli Objeto de conexión.
 * @param array $filtros Filtros globales: {Emp_Cod, anio, ppe_id, proy_id, mes, periodo_vista}
 * @return array Arreglo asociativo con KPI consolidados, alertas y métricas físicas.
 */
function ppto_dash_kpi_obtener_resumen($mysqli, $filtros = array()) {
    $Emp_Cod = isset($filtros['Emp_Cod']) ? (int)$filtros['Emp_Cod'] : 1;
    $anio = isset($filtros['anio']) ? (int)$filtros['anio'] : (int)date('Y');
    $proy_id = isset($filtros['proy_id']) && !empty($filtros['proy_id']) ? $mysqli->real_escape_string($filtros['proy_id']) : null;
    $ppe_id = isset($filtros['ppe_id']) && (int)$filtros['ppe_id'] > 0 ? (int)$filtros['ppe_id'] : null;

    if (!$ppe_id) {
        $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'ppe_anio' => $anio));
    }

    $meses_prod = ppto_dash_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $proy_id);
    $pr = ppto_dash_periodo_resuelto($filtros, $meses_prod);
    $periodo_vista = $pr['periodo_vista'];
    $mes = $pr['mes'];

    // 1. Inicialización de contadores y montos KPI
    $kpis = array(
        'periodo_vista'       => $periodo_vista,
        'mes_evaluado'        => $mes,
        'periodo_label'       => $pr['label'],
        'ppe_id'              => $ppe_id,
        'total_vigente'       => 0.00,
        'total_ejecutado'     => 0.00,
        'total_comprometido'  => 0.00,
        'total_disponible'    => 0.00,
        'pct_ejecucion'       => 0.00,
        'semaforo_global'     => 'verde',
        'costo_por_tonelada'  => 0.00,
        'toneladas_totales'   => 0.00,
        'toneladas_referencia' => 0.00,
        'variacion_toneladas_pct' => 0.00,
        'semaforo_toneladas'  => 'verde',
        'alertas_criticas'    => 0,
        'alertas_advertencia' => 0,
        'alertas_recientes'   => array()
    );

    if (!$ppe_id) {
        return $kpis;
    }

    require_once __DIR__ . '/ppto_format_helpers.php';

    // 2. Construir la consulta dinámica sobre el subquery dinámico
    $cond_proy = "";
    if ($proy_id) {
        $cond_proy = " AND r.proy_id = '$proy_id' ";
    } else {
        $cond_proy = " AND (
            (r.proy_id IS NULL OR r.proy_id = '') OR
            r.ppa_id IN (
                SELECT DISTINCT pd.Ppa_Cod
                FROM pre_proyecto_detalles pd
                WHERE pd.Emp_Cod = $Emp_Cod AND pd.Ppe_Cod = $ppe_id
                  AND pd.Pro_Cod IS NOT NULL
            )
        ) ";
    }

    $cond_mes = ppto_dash_sql_filtro_periodo('r', $periodo_vista, $mes);
    $solo_act = ppto_dash_sql_solo_partidas_activas('r');

    $sql = "SELECT 
                COALESCE(SUM(r.vigente), 0.00) AS total_vigente,
                COALESCE(SUM(r.ejecutado), 0.00) AS total_ejecutado,
                COALESCE(SUM(r.comprometido), 0.00) AS total_comprometido,
                COALESCE(SUM(r.disponible), 0.00) AS total_disponible
            FROM (" . ppto_sql_resumen_subquery() . ") r
            WHERE r.Emp_Cod = $Emp_Cod 
              AND r.ppe_id = $ppe_id 
              AND $cond_mes
              AND $solo_act
              $cond_proy";

    $res = $mysqli->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        $kpis['total_vigente']      = round((float)$row['total_vigente'], 2);
        $kpis['total_ejecutado']    = round((float)$row['total_ejecutado'], 2);
        $kpis['total_comprometido'] = round((float)$row['total_comprometido'], 2);
        $kpis['total_disponible']   = round((float)$row['total_disponible'], 2);

        if ($kpis['total_vigente'] > 0.0001) {
            $kpis['pct_ejecucion'] = round(($kpis['total_ejecutado'] / $kpis['total_vigente']) * 100, 2);
        }
    }

    // Determinar el semáforo financiero global según el consumo del presupuesto
    if ($kpis['pct_ejecucion'] >= 100) {
        $kpis['semaforo_global'] = 'rojo';
    } elseif ($kpis['pct_ejecucion'] >= 85) {
        $kpis['semaforo_global'] = 'amarillo';
    } else {
        $kpis['semaforo_global'] = 'verde';
    }

    // 3. Obtener métricas de producción física (pre_prod_periodos)
    $ton_acum = 0.0;
    $ton_ref_acum = 0.0;
    for ($m = 1; $m <= 12; $m++) {
        if ($periodo_vista === 'mes' && $mes !== null && $m !== (int)$mes) {
            continue;
        }
        if ($periodo_vista === 'acumulado' && $mes !== null && $m > (int)$mes) {
            continue;
        }
        $md = $meses_prod[$m];
        $ton_acum += ppto_dash_ton_efectiva_mes($md);
        $ton_ref_acum += ppto_dash_ton_proy_referencia_mes($md);
    }

    $kpis['toneladas_totales'] = round($ton_acum, 2);
    $kpis['toneladas_referencia'] = round($ton_ref_acum, 2);

    if ($kpis['toneladas_totales'] > 0.0001) {
        $kpis['costo_por_tonelada'] = round($kpis['total_ejecutado'] / $kpis['toneladas_totales'], 2);
    }

    if ($kpis['toneladas_referencia'] > 0.0001) {
        $var_ton = $kpis['toneladas_totales'] - $kpis['toneladas_referencia'];
        $kpis['variacion_toneladas_pct'] = round(($var_ton / $kpis['toneladas_referencia']) * 100, 2);
    }

    if ($kpis['variacion_toneladas_pct'] < -15.0) {
        $kpis['semaforo_toneladas'] = 'rojo';
    } elseif ($kpis['variacion_toneladas_pct'] < -5.0) {
        $kpis['semaforo_toneladas'] = 'amarillo';
    } else {
        $kpis['semaforo_toneladas'] = 'verde';
    }

    // 4. Obtener alertas presupuestarias del período
    $alertas = ppto_persistencia_consultar($mysqli, 9, array('Emp_Cod' => $Emp_Cod, 'ppe_id' => $ppe_id));
    if (!empty($alertas)) {
        foreach ($alertas as $al) {
            $umb = (int)$al['Pal_Umb'];
            if ($umb >= 100) {
                $kpis['alertas_criticas']++;
            } else {
                $kpis['alertas_advertencia']++;
            }
        }
        $kpis['alertas_recientes'] = array_slice($alertas, 0, 5); // Últimas 5
    }

    return $kpis;
}

/**
 * Prepara la matriz de distribución mensual (1 a 12) del presupuesto para gráficos.
 *
 * @param mysqli $mysqli
 * @param array $filtros
 * @return array Arreglo con etiquetas de meses y arreglos de presupuestado vs ejecutado.
 */
function ppto_dash_grafico_mensual_obtener($mysqli, $filtros = array()) {
    $Emp_Cod = isset($filtros['Emp_Cod']) ? (int)$filtros['Emp_Cod'] : 1;
    $anio = isset($filtros['anio']) ? (int)$filtros['anio'] : (int)date('Y');
    $proy_id = isset($filtros['proy_id']) && !empty($filtros['proy_id']) ? $mysqli->real_escape_string($filtros['proy_id']) : null;
    $ppe_id = isset($filtros['ppe_id']) && (int)$filtros['ppe_id'] > 0 ? (int)$filtros['ppe_id'] : null;

    if (!$ppe_id) {
        $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'ppe_anio' => $anio));
    }

    $meses_nombres = array(1=>'Ene', 2=>'Feb', 3=>'Mar', 4=>'Abr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Ago', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dic');
    $datos_mensuales = array(
        'labels'        => array_values($meses_nombres),
        'presupuestado' => array_fill(0, 12, 0.00),
        'ejecutado'     => array_fill(0, 12, 0.00),
        'comprometido'  => array_fill(0, 12, 0.00),
        'disponible'    => array_fill(0, 12, 0.00),
        'produccion'    => array_fill(0, 12, 0.00)
    );

    if (!$ppe_id) {
        return $datos_mensuales;
    }

    require_once __DIR__ . '/ppto_format_helpers.php';

    // 1. Obtener la evolución del presupuesto desde subquery dinámico
    $cond_proy = "";
    if ($proy_id) {
        $cond_proy = " AND r.proy_id = '$proy_id' ";
    } else {
        $cond_proy = " AND (
            (r.proy_id IS NULL OR r.proy_id = '') OR
            r.ppa_id IN (
                SELECT DISTINCT pd.Ppa_Cod
                FROM pre_proyecto_detalles pd
                WHERE pd.Emp_Cod = $Emp_Cod AND pd.Ppe_Cod = $ppe_id
                  AND pd.Pro_Cod IS NOT NULL
            )
        ) ";
    }

    $solo_act = ppto_dash_sql_solo_partidas_activas('r');

    $sql = "SELECT 
                r.mes,
                COALESCE(SUM(r.vigente), 0.00) AS presupuestado,
                COALESCE(SUM(r.ejecutado), 0.00) AS ejecutado,
                COALESCE(SUM(r.comprometido), 0.00) AS comprometido,
                COALESCE(SUM(r.disponible), 0.00) AS disponible
            FROM (" . ppto_sql_resumen_subquery() . ") r
            WHERE r.Emp_Cod = $Emp_Cod 
              AND r.ppe_id = $ppe_id 
              AND $solo_act
              $cond_proy
            GROUP BY r.mes
            ORDER BY r.mes ASC";

    $res = $mysqli->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $m = (int)$row['mes'] - 1; // Índice 0 a 11
            if ($m >= 0 && $m < 12) {
                $datos_mensuales['presupuestado'][$m] = round((float)$row['presupuestado'], 2);
                $datos_mensuales['ejecutado'][$m]     = round((float)$row['ejecutado'], 2);
                $datos_mensuales['comprometido'][$m]  = round((float)$row['comprometido'], 2);
                $datos_mensuales['disponible'][$m]    = round((float)$row['disponible'], 2);
            }
        }
    }

    // 2. Obtener la evolución física de la producción desde pre_prod_periodos
    $meses_prod = ppto_dash_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $proy_id);
    for ($m = 1; $m <= 12; $m++) {
        $md = $meses_prod[$m];
        $datos_mensuales['produccion'][$m - 1] = round(ppto_dash_ton_efectiva_mes($md), 2);
    }

    return $datos_mensuales;
}

/**
 * Prepara los datos para la distribución del presupuesto por Categorías o Partidas de Nivel 1.
 *
 * @param mysqli $mysqli
 * @param array $filtros
 * @return array Estructura para gráfico en dona/pastel {labels, data, colores}
 */
function ppto_dash_grafico_partidas_obtener($mysqli, $filtros = array()) {
    $Emp_Cod = isset($filtros['Emp_Cod']) ? (int)$filtros['Emp_Cod'] : 1;
    $anio = isset($filtros['anio']) ? (int)$filtros['anio'] : (int)date('Y');
    $proy_id = isset($filtros['proy_id']) && !empty($filtros['proy_id']) ? $mysqli->real_escape_string($filtros['proy_id']) : null;
    $ppe_id = isset($filtros['ppe_id']) && (int)$filtros['ppe_id'] > 0 ? (int)$filtros['ppe_id'] : null;

    if (!$ppe_id) {
        $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'ppe_anio' => $anio));
    }

    $meses_prod = ppto_dash_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $proy_id);
    $pr = ppto_dash_periodo_resuelto($filtros, $meses_prod);
    $periodo_vista = $pr['periodo_vista'];
    $mes = $pr['mes'];

    $out = array(
        'labels' => array(),
        'data'   => array(),
        'series' => array()
    );

    if (!$ppe_id) {
        return $out;
    }

    require_once __DIR__ . '/ppto_format_helpers.php';

    $cond_proy = "";
    if ($proy_id) {
        $cond_proy = " AND r.proy_id = '$proy_id' ";
    } else {
        $cond_proy = " AND (
            (r.proy_id IS NULL OR r.proy_id = '') OR
            r.ppa_id IN (
                SELECT DISTINCT pd.Ppa_Cod
                FROM pre_proyecto_detalles pd
                WHERE pd.Emp_Cod = $Emp_Cod AND pd.Ppe_Cod = $ppe_id
                  AND pd.Pro_Cod IS NOT NULL
            )
        ) ";
    }

    $cond_mes = ppto_dash_sql_filtro_periodo('r', $periodo_vista, $mes);
    $solo_act = ppto_dash_sql_solo_partidas_activas('r');

    // Agrupación por partida general
    $sql = "SELECT 
                p.Ppa_Cla AS ppa_codigo_clasificacion,
                p.Ppa_Des AS ppa_descripcion,
                COALESCE(SUM(r.ejecutado), 0.00) AS total_ejecutado
            FROM (" . ppto_sql_resumen_subquery() . ") r
            INNER JOIN pre_partidas p ON (r.ppa_id = p.Ppa_Cod OR r.Ppa_Cod = p.Ppa_Cod)
            WHERE r.Emp_Cod = $Emp_Cod 
              AND r.ppe_id = $ppe_id 
              AND $cond_mes
              AND $solo_act
              $cond_proy
            GROUP BY p.Ppa_Cod, p.Ppa_Cla, p.Ppa_Des
            HAVING total_ejecutado > 0
            ORDER BY total_ejecutado DESC
            LIMIT 7";

    $res = $mysqli->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $label = $row['ppa_codigo_clasificacion'] . ' - ' . $row['ppa_descripcion'];
            $out['labels'][] = (strlen($label) > 30) ? substr($label, 0, 27) . '...' : $label;
            $out['data'][]   = round((float)$row['total_ejecutado'], 2);
        }
    }

    return $out;
}
