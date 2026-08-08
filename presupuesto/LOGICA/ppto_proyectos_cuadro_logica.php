<?php
/**
 * Carga de datos del cuadro presupuestario (listado + export Excel).
 */

require_once __DIR__ . '/ppto_forecast_logica.php';
require_once __DIR__ . '/ppto_proyecto_version_logica.php';
require_once __DIR__ . '/ppto_format_helpers.php';
require_once __DIR__ . '/ppto_partidas_logica.php';

/**
 * Resuelve proy_id INT desde codigo o id.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param string|int $Pro_Cod
 * @return string
 */
function ppto_proy_cuadro_resolve_proy($mysqli, $Emp_Cod, $Pro_Cod) {
    if (!function_exists('ppto_resolve_proy_id')) {
        require_once __DIR__ . '/ppto_schema_logica.php';
    }
    $proy_id = ppto_resolve_proy_id($mysqli, (int)$Emp_Cod, $Pro_Cod);
    if ($proy_id <= 0) {
        $proy_id = (int)$Pro_Cod;
    }
    return (string)$proy_id;
}

/**
 * Listado liviano de rubros (sin forecast ni ajuste financiero).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param string $Pro_Cod
 * @param int $Ppe_Cod
 * @return array
 */
function ppto_proy_rubros_listar_simple($mysqli, $Emp_Cod, $Pro_Cod, $Ppe_Cod) {
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $Pro_Cod = ppto_proy_cuadro_resolve_proy($mysqli, $Emp_Cod, $Pro_Cod);
    $proy_esc = $mysqli->real_escape_string($Pro_Cod);

    $rows = array();
    $sql_rub = "SELECT
        d.Pdp_Cod AS Pdp_Cod, d.Pdp_Cod AS pdp_id,
        d.Ppe_Cod AS Ppe_Cod, d.Ppe_Cod AS ppe_id,
        d.Ppa_Cod AS Ppa_Cod, d.Ppa_Cod AS ppa_id,
        d.Pro_Cod AS Pro_Cod, d.Pro_Cod AS proy_id,
        d.Pdp_Rubro AS Pdp_Rubro, d.Pdp_Rubro AS pdp_rubro,
        d.Pdp_TonBase AS Pdp_TonBase, d.Pdp_TonBase AS pdp_toneladas_base,
        d.Pdp_FacAnualTon AS Pdp_FacAnualTon, d.Pdp_FacAnualTon AS pdp_factor_anual_tonelada,
        d.Pdp_PreAnual AS Pdp_PreAnual, d.Pdp_PreAnual AS pdp_presupuesto_anual,
        p.Ppa_Cla, p.Ppa_Des
        FROM pre_proyecto_detalles d
        INNER JOIN pre_partidas p ON d.Ppa_Cod = p.Ppa_Cod
        WHERE d.Pro_Cod='$proy_esc' AND d.Emp_Cod=$Emp_Cod";
    if ($Ppe_Cod > 0) {
        $sql_rub .= " AND d.Ppe_Cod = $Ppe_Cod";
    }
    $sql_rub .= " ORDER BY p.Ppa_Cla, d.Pdp_Rubro";
    $res = $mysqli->query($sql_rub);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }

    $cfg_ver = ppto_proy_version_config($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    $ton_costo_mes = ppto_proy_version_ton_costo($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);

    return array(
        'status' => 'success',
        'modo' => 'simple',
        'rows' => $rows,
        'version_cfg' => array_merge($cfg_ver, array('ton_costo_mes' => round($ton_costo_mes, 4))),
        'grupos_tope' => array(),
    );
}

/**
 * Mapa de partidas por codigo (una query indexada Emp_Cod).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @return array Ppa_Cla => row
 */
function ppto_proy_partidas_mapa_emp($mysqli, $Emp_Cod) {
    $map = array();
    $Emp_Cod = (int)$Emp_Cod;
    if ($Emp_Cod <= 0) {
        return $map;
    }
    ppto_schema_ensure_partida_meses_prorrateo($mysqli);
    $res = $mysqli->query(
        "SELECT Ppa_Cod, Ppa_Cla, Ppa_Des, COALESCE(Ppa_Pct, 0) AS Ppa_Pct,
                COALESCE(NULLIF(Ppa_Meses, 0), 12) AS Ppa_Meses
         FROM pre_partidas WHERE Emp_Cod=$Emp_Cod"
    );
    while ($res && ($row = $res->fetch_assoc())) {
        $map[$row['Ppa_Cla']] = $row;
    }
    return $map;
}

/**
 * Meses de horizonte del rubro (subgrupo, luego grupo; default 12).
 *
 * @param array $r fila enrichida con subgrupo_cod / grupo_cod
 * @param array $mapa_cla
 * @return int
 */
function ppto_proy_rubro_meses_prorrateo($r, $mapa_cla) {
    $cods = array();
    if (!empty($r['subgrupo_cod'])) {
        $cods[] = $r['subgrupo_cod'];
    }
    if (!empty($r['grupo_cod'])) {
        $cods[] = $r['grupo_cod'];
    }
    foreach ($cods as $cod) {
        if (isset($mapa_cla[$cod])) {
            $m = isset($mapa_cla[$cod]['Ppa_Meses']) ? (int)$mapa_cla[$cod]['Ppa_Meses'] : 12;
            if ($m > 0) {
                return $m;
            }
        }
    }
    return 12;
}

/**
 * Monto recalc. (ton×$/Ton) → presup. del año según meses del grupo.
 *
 * @param float $monto
 * @param int $meses
 * @return float
 */
function ppto_proy_presup_anual_desde_monto($monto, $meses) {
    $monto = (float)$monto;
    if ($monto <= 0) {
        return 0.0;
    }
    $meses = (int)$meses;
    if ($meses < 1) {
        $meses = 12;
    }
    return round($monto / ($meses / 12.0), 2);
}

/**
 * Completa grupo/subgrupo en filas de rubro usando mapa en memoria (sin JOIN por SUBSTRING_INDEX).
 *
 * @param array $rows
 * @param array $mapa_cla
 * @return array
 */
function ppto_proy_cuadro_enrich_grupos($rows, $mapa_cla) {
    foreach ($rows as $i => $r) {
        $cla = isset($r['Ppa_Cla']) ? trim($r['Ppa_Cla']) : '';
        $grupo = '';
        $sub = '';
        if ($cla !== '') {
            $parts = explode('.', $cla);
            $grupo = $parts[0];
            if (count($parts) >= 2) {
                $sub = $parts[0] . '.' . $parts[1];
            }
        }
        $pg = ($grupo !== '' && isset($mapa_cla[$grupo])) ? $mapa_cla[$grupo] : null;
        $ps = ($sub !== '' && isset($mapa_cla[$sub])) ? $mapa_cla[$sub] : null;
        $rows[$i]['grupo_cod'] = $grupo;
        $rows[$i]['grupo_descripcion'] = $pg ? $pg['Ppa_Des'] : '';
        $rows[$i]['grupo_porcentaje_tope'] = $pg ? (float)$pg['Ppa_Pct'] : 0;
        $rows[$i]['grupo_ppa_id'] = $pg ? (int)$pg['Ppa_Cod'] : 0;
        $rows[$i]['subgrupo_cod'] = $sub;
        $rows[$i]['subgrupo_descripcion'] = $ps ? $ps['Ppa_Des'] : '';
        $rows[$i]['subgrupo_porcentaje_tope'] = $ps ? (float)$ps['Ppa_Pct'] : 0;
        $rows[$i]['subgrupo_ppa_id'] = $ps ? (int)$ps['Ppa_Cod'] : 0;
        $rows[$i]['meses_prorrateo'] = ppto_proy_rubro_meses_prorrateo($rows[$i], $mapa_cla);
    }
    return $rows;
}

/**
 * Carga rubros y escenarios del cuadro presupuestario.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param string $Pro_Cod
 * @param int $Ppe_Cod
 * @param string $cuadro_vista
 * @param int|string|null $cuadro_mes
 * @param int|null $anio_precio Anio de proyeccion de tarifa (null = anio de la version)
 * @return array
 */
function ppto_proy_cuadro_cargar($mysqli, $Emp_Cod, $Pro_Cod, $Ppe_Cod, $cuadro_vista = 'anual', $cuadro_mes = null, $anio_precio = null) {
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $Pro_Cod = ppto_proy_cuadro_resolve_proy($mysqli, $Emp_Cod, $Pro_Cod);
    $proy_esc = $mysqli->real_escape_string($Pro_Cod);

    $rows = array();
    $sql_rub = "SELECT
        d.Pdp_Cod AS Pdp_Cod, d.Pdp_Cod AS pdp_id,
        d.Ppe_Cod AS Ppe_Cod, d.Ppe_Cod AS ppe_id,
        d.Ppa_Cod AS Ppa_Cod, d.Ppa_Cod AS ppa_id,
        d.Pro_Cod AS Pro_Cod, d.Pro_Cod AS proy_id,
        d.Pdp_Rubro AS Pdp_Rubro, d.Pdp_Rubro AS pdp_rubro,
        d.Pdp_TonBase AS Pdp_TonBase, d.Pdp_TonBase AS pdp_toneladas_base,
        d.Pdp_FacAnualTon AS Pdp_FacAnualTon, d.Pdp_FacAnualTon AS pdp_factor_anual_tonelada,
        d.Pdp_PreAnual AS Pdp_PreAnual, d.Pdp_PreAnual AS pdp_presupuesto_anual,
        p.Ppa_Cla, p.Ppa_Des
        FROM pre_proyecto_detalles d
        INNER JOIN pre_partidas p ON d.Ppa_Cod = p.Ppa_Cod
        WHERE d.Pro_Cod='$proy_esc' AND d.Emp_Cod=$Emp_Cod";
    if ($Ppe_Cod > 0) {
        $sql_rub .= " AND d.Ppe_Cod = $Ppe_Cod";
    }
    $sql_rub .= " ORDER BY p.Ppa_Cla, d.Pdp_Rubro";
    $res = $mysqli->query($sql_rub);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    $mapa_cla = array();
    if (!empty($rows)) {
        $mapa_cla = ppto_proy_partidas_mapa_emp($mysqli, $Emp_Cod);
        $rows = ppto_proy_cuadro_enrich_grupos($rows, $mapa_cla);
    }

    $anio_esc = (int)date('Y');
    if ($Ppe_Cod > 0) {
        $r_anio = $mysqli->query("SELECT Ppe_Ani AS Ppe_Ani FROM pre_presupuesto WHERE Ppe_Cod=$Ppe_Cod LIMIT 1");
        if ($r_anio && ($ra = $r_anio->fetch_assoc())) {
            $anio_esc = (int)$ra['Ppe_Ani'];
        }
    }
    $anio_proy = ($anio_precio !== null && (int)$anio_precio > 0) ? (int)$anio_precio : $anio_esc;

    $cfg_ver = ppto_proy_version_config($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    $tarifa_version = (float)$cfg_ver['tarifa_ton_iva'];
    $iva_ing = (float)$cfg_ver['iva_divisor'];
    if ($iva_ing <= 0) {
        $iva_ing = 1.15;
    }

    // Precio del anio seleccionado (proyeccion) o fallback a tarifa de version
    require_once __DIR__ . '/ppto_ajuste_financiero_logica.php';
    $precio_anio = ppto_ajuste_precio_para_anio($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio_proy);
    $tarifa_ing = (float)$precio_anio['tarifa_ton_iva'];
    if ($tarifa_ing <= 0) {
        $tarifa_ing = $tarifa_version > 0 ? $tarifa_version : 3.0;
    }

    // Precio base del anio de la version (ej. 2026): ancla para proyectar gastos
    $precio_base = ppto_ajuste_precio_para_anio($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio_esc);
    $tarifa_base = (float)$precio_base['tarifa_ton_iva'];
    if ($tarifa_base <= 0.0001) {
        $tarifa_base = $tarifa_version > 0 ? $tarifa_version : 3.0;
    }
    // Si sube el PVP, los gastos se proyectan en la misma proporcion para conservar ~12% utilidad/ingreso
    $factor_precio_gasto = ($tarifa_base > 0.0001) ? ($tarifa_ing / $tarifa_base) : 1.0;
    if ($factor_precio_gasto <= 0) {
        $factor_precio_gasto = 1.0;
    }

    $ton_base_pdf = (float)$cfg_ver['ton_mes'];
    if ($ton_base_pdf <= 0) {
        $ton_base_pdf = ppto_proy_version_ton_base($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    }
    $ton_esc_gasto_mes = ppto_proy_ton_escenario_gasto_mes($ton_base_pdf);
    $ton_costo_mes = ppto_proy_version_ton_costo($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);

    $meses_prod_esc = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio_esc, $Pro_Cod);
    $meses_con_real = 0;
    for ($mm = 1; $mm <= 12; $mm++) {
        if (isset($meses_prod_esc[$mm]) && (float)$meses_prod_esc[$mm]['real'] > 0.0001) {
            $meses_con_real++;
        }
    }

    $cuadro_vista = ppto_forecast_cuadro_vista_sanitize($cuadro_vista);
    $mes_default = ppto_forecast_ultimo_mes_con_real($meses_prod_esc);
    $cuadro_mes = ($cuadro_mes !== null && $cuadro_mes !== '')
        ? ppto_forecast_cuadro_mes_sanitize($cuadro_mes)
        : $mes_default;

    $meses_esperada_pdf = array();
    for ($m = 1; $m <= 12; $m++) {
        $meses_esperada_pdf[$m] = array(
            'esperada' => $ton_base_pdf,
            'proyectada' => $ton_base_pdf,
            'real' => 0.0,
            'estado' => 'sin_dato',
        );
    }

    $ton_esp_anual = round($ton_base_pdf * 12.0, 2);
    $ton_esp_period = ppto_forecast_ton_periodo_escenario($meses_esperada_pdf, 'esperada', $cuadro_vista, $cuadro_mes, $ton_base_pdf);
    $ton_esp_ratio = ($ton_esp_anual > 0.0001) ? ($ton_esp_period / $ton_esp_anual) : 1.0;

    foreach ($rows as $idx => $r) {
        $factor = (float)$r['Pdp_FacAnualTon'];
        $anual_actual = round((float)$r['Pdp_PreAnual'], 2);
        if ($factor > 0.0001) {
            // ton×$/Ton = total del horizonte; presup. anual = proyección de 1 año (meses del grupo).
            $meses_rubro = isset($r['meses_prorrateo']) ? (int)$r['meses_prorrateo'] : 12;
            $monto_recalc = $ton_costo_mes * $factor;
            $esc_esperada_anual = ppto_proy_presup_anual_desde_monto($monto_recalc, $meses_rubro);
            $esc_esperada_val = ($cuadro_vista === 'anual')
                ? $esc_esperada_anual
                : round($esc_esperada_anual * $ton_esp_ratio, 2);
            $factor_esc = ppto_proy_factor_escenario_gasto($esc_esperada_anual, $ton_esc_gasto_mes);
            $rows[$idx]['esc_esperada'] = $esc_esperada_val;
            $rows[$idx]['esc_proyectada'] = ppto_forecast_pf_rubro_periodo_escenario($meses_prod_esc, $factor_esc, 'proyectada', $cuadro_vista, $cuadro_mes);
            $rows[$idx]['esc_real'] = ppto_forecast_pf_rubro_periodo_escenario($meses_prod_esc, $factor_esc, 'real', $cuadro_vista, $cuadro_mes);
        } else {
            $rows[$idx]['esc_esperada'] = ppto_forecast_monto_fijo_periodo($anual_actual, $cuadro_vista, $cuadro_mes);
            $rows[$idx]['esc_proyectada'] = $rows[$idx]['esc_esperada'];
            $rows[$idx]['esc_real'] = $rows[$idx]['esc_esperada'];
        }
        // Proyeccion de gastos segun aumento/disminucion del PVP vs anio base
        if (abs($factor_precio_gasto - 1.0) > 0.00001) {
            $rows[$idx]['esc_esperada'] = round((float)$rows[$idx]['esc_esperada'] * $factor_precio_gasto, 2);
            $rows[$idx]['esc_proyectada'] = round((float)$rows[$idx]['esc_proyectada'] * $factor_precio_gasto, 2);
            $rows[$idx]['esc_real'] = round((float)$rows[$idx]['esc_real'] * $factor_precio_gasto, 2);
        }
        $rows[$idx]['factor_precio_gasto'] = round($factor_precio_gasto, 6);
    }

    $escenarios_ton_period = array(
        'esperada' => $ton_esp_period,
        'proyectada' => ppto_forecast_ton_periodo_escenario($meses_prod_esc, 'proyectada', $cuadro_vista, $cuadro_mes, 0.0),
        'real' => ppto_forecast_ton_periodo_escenario($meses_prod_esc, 'real', $cuadro_vista, $cuadro_mes, 0.0),
    );
    $escenarios_ingreso = array(
        'esperada' => ppto_forecast_ingreso_periodo_escenario($meses_esperada_pdf, $tarifa_ing, $iva_ing, 'esperada', $cuadro_vista, $cuadro_mes, $ton_base_pdf),
        'proyectada' => ppto_forecast_ingreso_periodo_escenario($meses_prod_esc, $tarifa_ing, $iva_ing, 'proyectada', $cuadro_vista, $cuadro_mes, 0.0),
        'real' => ppto_forecast_ingreso_periodo_escenario($meses_prod_esc, $tarifa_ing, $iva_ing, 'real', $cuadro_vista, $cuadro_mes, 0.0),
    );

    return array(
        'status' => 'success',
        'rows' => $rows,
        'escenarios_anio' => $anio_esc,
        'anio_proyeccion' => $anio_proy,
        'escenarios_meses_con_real' => $meses_con_real,
        'cuadro_periodo' => array(
            'vista' => $cuadro_vista,
            'mes' => $cuadro_mes,
            'mes_default' => $mes_default,
            'label' => ppto_forecast_cuadro_periodo_label($cuadro_vista, $cuadro_mes),
            'anio' => $anio_proy,
        ),
        'escenarios_ton_mes' => array(
            'esperada' => round($ton_base_pdf, 2),
            'proyectada' => ppto_forecast_ton_mes_escenario($meses_prod_esc, 'proyectada'),
            'real' => ppto_forecast_ton_mes_escenario($meses_prod_esc, 'real'),
        ),
        'escenarios_ton_anual' => array(
            'esperada' => $ton_esp_anual,
            'proyectada' => ppto_forecast_ton_anual_escenario($meses_prod_esc, 'proyectada'),
            'real' => ppto_forecast_ton_anual_escenario($meses_prod_esc, 'real'),
        ),
        'escenarios_ton_periodo' => $escenarios_ton_period,
        'escenarios_ingreso' => $escenarios_ingreso,
        'ingreso_cfg' => array(
            'tarifa_ton_iva' => round($tarifa_ing, 4),
            'iva_divisor' => round($iva_ing, 4),
            'tarifa_ton_neta' => round($tarifa_ing / $iva_ing, 6),
            'tarifa_version' => round($tarifa_version, 4),
            'tarifa_base' => round($tarifa_base, 4),
            'anio_base' => $anio_esc,
            'factor_precio_gasto' => round($factor_precio_gasto, 6),
            'anio' => $anio_proy,
            'fuente_precio' => isset($precio_anio['fuente']) ? $precio_anio['fuente'] : 'version',
        ),
        'precio_anio' => $precio_anio,
        'precios_proyeccion' => ppto_ajuste_precios_list($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod),
        'grupos_tope' => ppto_proy_grupos_resumen_tope($mysqli, $Emp_Cod, $Pro_Cod, $Ppe_Cod, $rows),
        'version_cfg' => array_merge($cfg_ver, array('ton_costo_mes' => round($ton_costo_mes, 4))),
    );
}
