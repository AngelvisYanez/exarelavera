<?php
/**
 * Carga de datos del cuadro presupuestario (listado + export Excel).
 */

require_once __DIR__ . '/ppto_forecast_logica.php';
require_once __DIR__ . '/ppto_proyecto_version_logica.php';
require_once __DIR__ . '/ppto_format_helpers.php';
require_once __DIR__ . '/ppto_partidas_logica.php';

/**
 * Carga rubros y escenarios del cuadro presupuestario.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param string $proy_id
 * @param int $ppe_id
 * @param string $cuadro_vista
 * @param int|string|null $cuadro_mes
 * @param int|null $anio_precio Anio de proyeccion de tarifa (null = anio de la version)
 * @return array
 */
function ppto_proy_cuadro_cargar($mysqli, $Emp_Cod, $proy_id, $ppe_id, $cuadro_vista = 'anual', $cuadro_mes = null, $anio_precio = null) {
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $proy_id = trim((string)$proy_id);
    $proy_esc = $mysqli->real_escape_string($proy_id);

    $rows = array();
    $sql_rub = "SELECT d.*, p.ppa_codigo_clasificacion, p.ppa_descripcion,
        SUBSTRING_INDEX(p.ppa_codigo_clasificacion, '.', 1) AS grupo_cod,
        pg.ppa_descripcion AS grupo_descripcion,
        COALESCE(pg.ppa_porcentaje_tope, 0) AS grupo_porcentaje_tope,
        COALESCE(pg.ppa_id, 0) AS grupo_ppa_id,
        CASE
            WHEN (LENGTH(p.ppa_codigo_clasificacion) - LENGTH(REPLACE(p.ppa_codigo_clasificacion, '.', ''))) >= 2
            THEN SUBSTRING_INDEX(p.ppa_codigo_clasificacion, '.', 2)
            ELSE ''
        END AS subgrupo_cod,
        ps.ppa_descripcion AS subgrupo_descripcion,
        COALESCE(ps.ppa_porcentaje_tope, 0) AS subgrupo_porcentaje_tope,
        COALESCE(ps.ppa_id, 0) AS subgrupo_ppa_id
        FROM pre_proyecto_detalles d
        INNER JOIN pre_partidas p ON d.Ppa_Cod = p.Ppa_Cod
        LEFT JOIN pre_partidas pg ON pg.Emp_Cod = d.Emp_Cod
            AND pg.Ppa_Cla = SUBSTRING_INDEX(p.Ppa_Cla, '.', 1)
        LEFT JOIN pre_partidas ps ON ps.Emp_Cod = d.Emp_Cod
            AND ps.Ppa_Cla = CASE
                WHEN (LENGTH(p.Ppa_Cla) - LENGTH(REPLACE(p.Ppa_Cla, '.', ''))) >= 2
                THEN SUBSTRING_INDEX(p.Ppa_Cla, '.', 2)
                ELSE ''
            END
        WHERE d.proy_id='$proy_esc' AND d.Emp_Cod=$Emp_Cod";
    if ($ppe_id > 0) {
        $sql_rub .= " AND d.ppe_id = $ppe_id";
    }
    $sql_rub .= " ORDER BY p.ppa_codigo_clasificacion, d.pdp_rubro";
    $res = $mysqli->query($sql_rub);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }

    $anio_esc = (int)date('Y');
    if ($ppe_id > 0) {
        $r_anio = $mysqli->query("SELECT Ppe_Ani AS ppe_anio FROM pre_presupuesto WHERE Ppe_Cod=$ppe_id LIMIT 1");
        if ($r_anio && ($ra = $r_anio->fetch_assoc())) {
            $anio_esc = (int)$ra['ppe_anio'];
        }
    }
    $anio_proy = ($anio_precio !== null && (int)$anio_precio > 0) ? (int)$anio_precio : $anio_esc;

    $cfg_ver = ppto_proy_version_config($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    $tarifa_version = (float)$cfg_ver['tarifa_ton_iva'];
    $iva_ing = (float)$cfg_ver['iva_divisor'];
    if ($iva_ing <= 0) {
        $iva_ing = 1.15;
    }

    // Precio del anio seleccionado (proyeccion) o fallback a tarifa de version
    require_once __DIR__ . '/ppto_ajuste_financiero_logica.php';
    $precio_anio = ppto_ajuste_precio_para_anio($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio_proy);
    $tarifa_ing = (float)$precio_anio['tarifa_ton_iva'];
    if ($tarifa_ing <= 0) {
        $tarifa_ing = $tarifa_version > 0 ? $tarifa_version : 3.0;
    }

    // Precio base del anio de la version (ej. 2026): ancla para proyectar gastos
    $precio_base = ppto_ajuste_precio_para_anio($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio_esc);
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
        $ton_base_pdf = ppto_proy_version_ton_base($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    }
    $ton_esc_gasto_mes = ppto_proy_ton_escenario_gasto_mes($ton_base_pdf);
    $ton_costo_mes = ppto_proy_version_ton_costo($mysqli, $proy_id, $Emp_Cod, $ppe_id);

    $meses_prod_esc = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio_esc, $proy_id);
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
        $factor = (float)$r['pdp_factor_anual_tonelada'];
        $anual_actual = round((float)$r['pdp_presupuesto_anual'], 2);
        if ($factor > 0.0001) {
            $esc_esperada_anual = round($ton_costo_mes * $factor, 2);
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
        'precios_proyeccion' => ppto_ajuste_precios_list($mysqli, $proy_id, $Emp_Cod, $ppe_id),
        'grupos_tope' => ppto_proy_grupos_resumen_tope($mysqli, $Emp_Cod, $proy_id, $ppe_id, $rows),
        'version_cfg' => array_merge($cfg_ver, array('ton_costo_mes' => round($ton_costo_mes, 4))),
    );
}
