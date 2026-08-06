<?php
/**
 * Ajustes financieros: costo de capital + recuperacion GAD.
 * No modifica pdp_presupuesto_anual (partida base).
 */

require_once __DIR__ . '/ppto_format_helpers.php';
require_once __DIR__ . '/ppto_proyecto_version_logica.php';

/**
 * Asegura tablas/columnas de ajustes financieros.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_ajuste_financiero($mysqli) {
    if (!$mysqli) {
        return;
    }
    $cols = array(
        'Ppv_CostCapPct' => "ADD COLUMN Ppv_CostCapPct DECIMAL(8,4) NOT NULL DEFAULT 11.0000 COMMENT 'Porcentaje costo de capital sobre precio neto'",
        'Ppv_GadObjetivo' => "ADD COLUMN Ppv_GadObjetivo DECIMAL(14,2) NOT NULL DEFAULT 2000000.00 COMMENT 'Monto objetivo recuperacion GAD'",
        'Ppv_GadFacTon' => "ADD COLUMN Ppv_GadFacTon DECIMAL(12,6) NOT NULL DEFAULT 0.198400 COMMENT 'USD recuperacion GAD por tonelada'",
        'Ppv_GadRecAcum' => "ADD COLUMN Ppv_GadRecAcum DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Recuperacion GAD acumulada aplicada'",
        'Ppv_AjuActivo' => "ADD COLUMN Ppv_AjuActivo TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=usar partida final en cuadro'",
    );
    $res_tbl = @$mysqli->query("SHOW TABLES LIKE 'pre_proyecto_version'");
    if ($res_tbl && $res_tbl->num_rows > 0) {
        foreach ($cols as $name => $ddl) {
            $c = @$mysqli->query("SHOW COLUMNS FROM pre_proyecto_version LIKE '" . $mysqli->real_escape_string($name) . "'");
            if (!$c || $c->num_rows === 0) {
                @$mysqli->query("ALTER TABLE pre_proyecto_version " . $ddl);
            }
        }
    }

    $res_p = @$mysqli->query("SHOW TABLES LIKE 'pre_proyecto_precio_anio'");
    if (!$res_p || $res_p->num_rows === 0) {
        @$mysqli->query("CREATE TABLE IF NOT EXISTS pre_proyecto_precio_anio (
          proy_id VARCHAR(50) NOT NULL,
          Emp_Cod INT NOT NULL,
          ppe_id INT NOT NULL,
          ppa_anio INT NOT NULL,
          ppa_tarifa_ton_iva DECIMAL(12,4) NOT NULL DEFAULT 3.0000,
          ppa_fecha_registro DATETIME NOT NULL,
          Usu_Cod INT NOT NULL DEFAULT 0,
          PRIMARY KEY (proy_id, Emp_Cod, ppe_id, ppa_anio),
          KEY idx_pppa_emp (Emp_Cod),
          KEY idx_pppa_ppe (ppe_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    $res_c = @$mysqli->query("SHOW TABLES LIKE 'pre_ajuste_fin_cab'");
    if (!$res_c || $res_c->num_rows === 0) {
        @$mysqli->query("CREATE TABLE IF NOT EXISTS pre_ajuste_fin_cab (
          ajc_id INT NOT NULL AUTO_INCREMENT,
          proy_id VARCHAR(50) NOT NULL,
          Emp_Cod INT NOT NULL,
          ppe_id INT NOT NULL,
          ajc_anio INT NOT NULL,
          ajc_vista VARCHAR(20) NOT NULL DEFAULT 'anual',
          ajc_mes INT NOT NULL DEFAULT 0,
          ajc_escenario VARCHAR(20) NOT NULL DEFAULT 'esperada',
          ajc_estado VARCHAR(20) NOT NULL DEFAULT 'aplicado',
          ajc_precio_iva DECIMAL(12,4) NOT NULL DEFAULT 0,
          ajc_iva_divisor DECIMAL(8,4) NOT NULL DEFAULT 1.1500,
          ajc_precio_neto DECIMAL(14,6) NOT NULL DEFAULT 0,
          ajc_capital_pct DECIMAL(8,4) NOT NULL DEFAULT 0,
          ajc_capital_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
          ajc_capital_total DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajc_gad_factor_ton DECIMAL(12,6) NOT NULL DEFAULT 0,
          ajc_gad_toneladas DECIMAL(14,4) NOT NULL DEFAULT 0,
          ajc_gad_calculado DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajc_gad_aplicado DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajc_gad_acum_antes DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajc_gad_acum_despues DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajc_gad_saldo_despues DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajc_gad_objetivo DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajc_gasto_base DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajc_gasto_final DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajc_ingreso DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajc_utilidad_base DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajc_observacion VARCHAR(255) NULL,
          ajc_fecha_registro DATETIME NOT NULL,
          Usu_Cod INT NOT NULL DEFAULT 0,
          PRIMARY KEY (ajc_id),
          KEY idx_ajc_proy (proy_id, Emp_Cod, ppe_id),
          KEY idx_ajc_estado (ajc_estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    $res_d = @$mysqli->query("SHOW TABLES LIKE 'pre_ajuste_fin_det'");
    if (!$res_d || $res_d->num_rows === 0) {
        @$mysqli->query("CREATE TABLE IF NOT EXISTS pre_ajuste_fin_det (
          ajd_id INT NOT NULL AUTO_INCREMENT,
          ajc_id INT NOT NULL,
          grupo_cod VARCHAR(20) NOT NULL,
          grupo_nombre VARCHAR(255) NOT NULL DEFAULT '',
          ajd_partida_base DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajd_participacion_pct DECIMAL(10,6) NOT NULL DEFAULT 0,
          ajd_base_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
          ajd_capital_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
          ajd_gad_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
          ajd_ajuste_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
          ajd_final_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
          ajd_capital_monto DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajd_gad_monto DECIMAL(14,2) NOT NULL DEFAULT 0,
          ajd_partida_final DECIMAL(14,2) NOT NULL DEFAULT 0,
          PRIMARY KEY (ajd_id),
          KEY idx_ajd_cab (ajc_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }
}

/**
 * Lee configuracion de ajustes de la version.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @return array
 */
function ppto_ajuste_cfg_get($mysqli, $proy_id, $Emp_Cod, $ppe_id) {
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $cfg = array(
        'costo_capital_pct' => 11.0,
        'gad_monto_objetivo' => 2000000.0,
        'gad_factor_ton' => 0.1984,
        'gad_recuperado_acum' => 0.0,
        'ajuste_activo' => 0,
        'iva_divisor' => 1.15,
        'tarifa_ton_iva' => 3.0,
    );
    $res = $mysqli->query("SELECT Ppv_CostCapPct AS pv_costo_capital_pct, Ppv_GadObjetivo AS pv_gad_monto_objetivo, Ppv_GadFacTon AS pv_gad_factor_ton,
            Ppv_GadRecAcum AS pv_gad_recuperado_acum, Ppv_AjuActivo AS pv_ajuste_activo, Ppv_TarifaTonIva AS pv_tarifa_ton_iva, Ppv_IvaDivisor AS pv_iva_divisor
        FROM pre_proyecto_version
        WHERE (Pro_Cod='$esc' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$esc')) AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$ppe_id LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        if (isset($row['pv_costo_capital_pct'])) {
            $cfg['costo_capital_pct'] = (float)$row['pv_costo_capital_pct'];
        }
        if (isset($row['pv_gad_monto_objetivo'])) {
            $cfg['gad_monto_objetivo'] = (float)$row['pv_gad_monto_objetivo'];
        }
        if (isset($row['pv_gad_factor_ton'])) {
            $cfg['gad_factor_ton'] = (float)$row['pv_gad_factor_ton'];
        }
        if (isset($row['pv_gad_recuperado_acum'])) {
            $cfg['gad_recuperado_acum'] = (float)$row['pv_gad_recuperado_acum'];
        }
        if (isset($row['pv_ajuste_activo'])) {
            $cfg['ajuste_activo'] = (int)$row['pv_ajuste_activo'] ? 1 : 0;
        }
        if (isset($row['pv_tarifa_ton_iva']) && (float)$row['pv_tarifa_ton_iva'] > 0) {
            $cfg['tarifa_ton_iva'] = (float)$row['pv_tarifa_ton_iva'];
        }
        if (isset($row['pv_iva_divisor']) && (float)$row['pv_iva_divisor'] > 0) {
            $cfg['iva_divisor'] = (float)$row['pv_iva_divisor'];
        }
    }
    $obj = (float)$cfg['gad_monto_objetivo'];
    $acum = (float)$cfg['gad_recuperado_acum'];
    $cfg['gad_saldo_pendiente'] = max(0.0, round($obj - $acum, 2));
    return $cfg;
}

/**
 * Guarda configuracion de ajustes (sin aplicar recuperacion).
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param array $data
 * @param int $Usu_Cod
 * @return array
 */
function ppto_ajuste_cfg_save($mysqli, $proy_id, $Emp_Cod, $ppe_id, $data, $Usu_Cod) {
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $Usu_Cod = (int)$Usu_Cod;
    if ($proy_id === '' || $ppe_id <= 0) {
        return array('ok' => false, 'message' => 'Proyecto y version requeridos.');
    }

    $pct = isset($data['costo_capital_pct']) ? (float)$data['costo_capital_pct'] : 11.0;
    $obj = isset($data['gad_monto_objetivo']) ? (float)$data['gad_monto_objetivo'] : 2000000.0;
    $fac = isset($data['gad_factor_ton']) ? (float)$data['gad_factor_ton'] : 0.1984;
    $acum = isset($data['gad_recuperado_acum']) ? (float)$data['gad_recuperado_acum'] : null;
    $activo = !empty($data['ajuste_activo']) ? 1 : 0;
    if ($pct < 0) {
        $pct = 0;
    }
    if ($obj < 0) {
        $obj = 0;
    }
    if ($fac < 0) {
        $fac = 0;
    }

    $base = ppto_proy_version_config($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    $ton = (float)$base['ton_mes'];
    $tarifa = (float)$base['tarifa_ton_iva'];
    $iva = (float)$base['iva_divisor'];
    $ton_costo = ppto_proy_version_ton_costo($mysqli, $proy_id, $Emp_Cod, $ppe_id);

    $acum_sql = '';
    if ($acum !== null) {
        if ($acum < 0) {
            $acum = 0;
        }
        if ($obj > 0 && $acum > $obj) {
            $acum = $obj;
        }
        $acum_sql = ', pv_gad_recuperado_acum=' . round($acum, 2);
    }

    $sql = "INSERT INTO pre_proyecto_version
            (Pro_Cod, Emp_Cod, Ppe_Cod, Ppv_TonBaseMes, Ppv_TonCostoMes, Ppv_TarifaTonIva, Ppv_IvaDivisor,
             Ppv_CostCapPct, Ppv_GadObjetivo, Ppv_GadFacTon, Ppv_AjuActivo, Ppv_FecReg, Usu_Cod)
            VALUES (COALESCE((SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$esc' OR Pro_Cod='$esc' LIMIT 1), 0), $Emp_Cod, $ppe_id, $ton, $ton_costo, $tarifa, $iva,
             $pct, $obj, $fac, $activo, NOW(), $Usu_Cod)
            ON DUPLICATE KEY UPDATE
             Ppv_CostCapPct=$pct,
             Ppv_GadObjetivo=$obj,
             Ppv_GadFacTon=$fac,
             Ppv_AjuActivo=$activo,
             Ppv_FecReg=NOW(),
             Usu_Cod=$Usu_Cod" . $acum_sql;
    if (!$mysqli->query($sql)) {
        return array('ok' => false, 'message' => $mysqli->error);
    }
    return array('ok' => true, 'message' => 'Configuracion de ajustes guardada.', 'cfg' => ppto_ajuste_cfg_get($mysqli, $proy_id, $Emp_Cod, $ppe_id));
}

/**
 * Lista precios por anio.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @return array
 */
function ppto_ajuste_precios_list($mysqli, $proy_id, $Emp_Cod, $ppe_id) {
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $rows = array();
    $res = $mysqli->query("SELECT ppa_anio, ppa_tarifa_ton_iva
        FROM pre_proyecto_precio_anio
        WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id
        ORDER BY ppa_anio ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = array(
                'anio' => (int)$r['ppa_anio'],
                'tarifa_ton_iva' => (float)$r['ppa_tarifa_ton_iva'],
            );
        }
    }
    return $rows;
}

/**
 * Guarda/reemplaza proyeccion de precios por anio.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param array $precios array of {anio, tarifa_ton_iva}
 * @param int $Usu_Cod
 * @return array
 */
function ppto_ajuste_precios_save($mysqli, $proy_id, $Emp_Cod, $ppe_id, $precios, $Usu_Cod) {
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $Usu_Cod = (int)$Usu_Cod;
    if ($proy_id === '' || $ppe_id <= 0) {
        return array('ok' => false, 'message' => 'Proyecto y version requeridos.');
    }
    if (!is_array($precios)) {
        $precios = array();
    }
    $mysqli->query("DELETE FROM pre_proyecto_precio_anio
        WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id");
    foreach ($precios as $p) {
        $anio = isset($p['anio']) ? (int)$p['anio'] : 0;
        $tarifa = isset($p['tarifa_ton_iva']) ? (float)$p['tarifa_ton_iva'] : 0;
        if ($anio < 2000 || $anio > 2100 || $tarifa <= 0) {
            continue;
        }
        $mysqli->query("INSERT INTO pre_proyecto_precio_anio
            (proy_id, Emp_Cod, ppe_id, ppa_anio, ppa_tarifa_ton_iva, ppa_fecha_registro, Usu_Cod)
            VALUES ('$esc', $Emp_Cod, $ppe_id, $anio, $tarifa, NOW(), $Usu_Cod)");
    }
    return array('ok' => true, 'message' => 'Proyeccion de precios guardada.', 'precios' => ppto_ajuste_precios_list($mysqli, $proy_id, $Emp_Cod, $ppe_id));
}

/**
 * Resuelve tarifa con IVA para un anio (proyeccion o fallback version).
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param int $anio
 * @return array
 */
function ppto_ajuste_precio_para_anio($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio) {
    $cfg = ppto_ajuste_cfg_get($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    $anio = (int)$anio;
    $tarifa = (float)$cfg['tarifa_ton_iva'];
    $fuente = 'version';
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    if ($anio > 0) {
        $res = $mysqli->query("SELECT ppa_tarifa_ton_iva FROM pre_proyecto_precio_anio
            WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id AND ppa_anio=$anio LIMIT 1");
        if ($res && ($row = $res->fetch_assoc()) && (float)$row['ppa_tarifa_ton_iva'] > 0) {
            $tarifa = (float)$row['ppa_tarifa_ton_iva'];
            $fuente = 'proyeccion_anio';
        }
    }
    $iva = (float)$cfg['iva_divisor'];
    if ($iva <= 0) {
        $iva = 1.15;
    }
    $neto = round($tarifa / $iva, 8);
    return array(
        'anio' => $anio,
        'tarifa_ton_iva' => round($tarifa, 4),
        'iva_divisor' => round($iva, 4),
        'precio_neto' => $neto,
        'fuente' => $fuente,
    );
}

/**
 * Agrupa montos base por grupo principal.
 *
 * @param array $rows rubros con esc_*
 * @param string $escenario esperada|proyectada|real
 * @return array
 */
function ppto_ajuste_agrupar_bases($rows, $escenario) {
    $key = 'esc_' . $escenario;
    $grupos = array();
    $total = 0.0;
    foreach ($rows as $r) {
        $gk = isset($r['grupo_cod']) ? trim($r['grupo_cod']) : '';
        if ($gk === '') {
            $cod = isset($r['ppa_codigo_clasificacion']) ? $r['ppa_codigo_clasificacion'] : '00';
            $parts = explode('.', $cod);
            $gk = $parts[0];
        }
        // Solo grupos principales (sin punto)
        if (strpos($gk, '.') !== false) {
            $gk = explode('.', $gk);
            $gk = $gk[0];
        }
        if (!isset($grupos[$gk])) {
            $grupos[$gk] = array(
                'grupo_cod' => $gk,
                'grupo_nombre' => !empty($r['grupo_descripcion']) ? $r['grupo_descripcion'] : ('Grupo ' . $gk),
                'partida_base' => 0.0,
            );
        }
        $monto = isset($r[$key]) ? (float)$r[$key] : (float)$r['pdp_presupuesto_anual'];
        $grupos[$gk]['partida_base'] += $monto;
        $total += $monto;
    }
    ksort($grupos, SORT_STRING);
    return array('grupos' => array_values($grupos), 'total' => round($total, 2));
}

/**
 * Distribuye un monto total por participacion; corrige centavos en el ultimo.
 *
 * @param array $pcts
 * @param float $total
 * @param int $dec
 * @return array montos alineados a $pcts
 */
function ppto_ajuste_distribuir_monto($pcts, $total, $dec = 2) {
    $n = count($pcts);
    $out = array();
    $sum = 0.0;
    $total = round((float)$total, $dec);
    for ($i = 0; $i < $n; $i++) {
        if ($i === $n - 1) {
            $out[$i] = round($total - $sum, $dec);
        } else {
            $v = round($total * ((float)$pcts[$i] / 100.0), $dec);
            $out[$i] = $v;
            $sum += $v;
        }
    }
    return $out;
}

/**
 * Simula ajustes financieros (no persiste).
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param array $cuadro resultado de ppto_proy_cuadro_cargar
 * @param array $opts overrides opcionales
 * @return array
 */
function ppto_ajuste_simular($mysqli, $proy_id, $Emp_Cod, $ppe_id, $cuadro, $opts = array()) {
    $cfg = ppto_ajuste_cfg_get($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    $escenario = isset($opts['escenario']) ? $opts['escenario'] : 'esperada';
    if (!in_array($escenario, array('esperada', 'proyectada', 'real'), true)) {
        $escenario = 'esperada';
    }
    $anio = isset($opts['anio']) ? (int)$opts['anio'] : (isset($cuadro['escenarios_anio']) ? (int)$cuadro['escenarios_anio'] : (int)date('Y'));
    $vista = isset($cuadro['cuadro_periodo']['vista']) ? $cuadro['cuadro_periodo']['vista'] : 'anual';
    $mes = isset($cuadro['cuadro_periodo']['mes']) ? (int)$cuadro['cuadro_periodo']['mes'] : 0;

    $pct_cap = isset($opts['costo_capital_pct']) ? (float)$opts['costo_capital_pct'] : (float)$cfg['costo_capital_pct'];
    $fac_gad = isset($opts['gad_factor_ton']) ? (float)$opts['gad_factor_ton'] : (float)$cfg['gad_factor_ton'];
    $obj_gad = isset($opts['gad_monto_objetivo']) ? (float)$opts['gad_monto_objetivo'] : (float)$cfg['gad_monto_objetivo'];
    $acum_gad = isset($opts['gad_recuperado_acum']) ? (float)$opts['gad_recuperado_acum'] : (float)$cfg['gad_recuperado_acum'];

    $precio = ppto_ajuste_precio_para_anio($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio);
    if (isset($opts['tarifa_ton_iva']) && (float)$opts['tarifa_ton_iva'] > 0) {
        $precio['tarifa_ton_iva'] = (float)$opts['tarifa_ton_iva'];
        $precio['precio_neto'] = round($precio['tarifa_ton_iva'] / $precio['iva_divisor'], 8);
        $precio['fuente'] = 'override';
    }

    $capital_por_ton = round($precio['precio_neto'] * ($pct_cap / 100.0), 8);

    $ton = 0.0;
    if (isset($cuadro['escenarios_ton_periodo'][$escenario])) {
        $ton = (float)$cuadro['escenarios_ton_periodo'][$escenario];
    }
    $ingreso = 0.0;
    if (isset($cuadro['escenarios_ingreso'][$escenario])) {
        // Recalcular ingreso con precio del anio si difiere
        $iva = (float)$precio['iva_divisor'];
        $ingreso = ($iva > 0) ? round($ton * (float)$precio['tarifa_ton_iva'] / $iva, 2) : 0.0;
    }

    $agrup = ppto_ajuste_agrupar_bases(isset($cuadro['rows']) ? $cuadro['rows'] : array(), $escenario);
    $grupos = $agrup['grupos'];
    $gasto_base = $agrup['total'];

    $saldo = max(0.0, round($obj_gad - $acum_gad, 2));
    $gad_calculado = round($ton * $fac_gad, 2);
    $gad_aplicado = ($saldo <= 0.0001) ? 0.0 : min($gad_calculado, $saldo);
    $gad_por_ton_ef = ($ton > 0.0001) ? ($gad_aplicado / $ton) : 0.0;
    $capital_total = round($capital_por_ton * $ton, 2);

    $pcts = array();
    foreach ($grupos as $g) {
        $pcts[] = ($gasto_base > 0.0001)
            ? round(((float)$g['partida_base'] / $gasto_base) * 100.0, 6)
            : 0.0;
    }
    $cap_montos = ppto_ajuste_distribuir_monto($pcts, $capital_total, 2);
    $gad_montos = ppto_ajuste_distribuir_monto($pcts, $gad_aplicado, 2);

    $detalle = array();
    $gasto_final = 0.0;
    $n = count($grupos);
    for ($i = 0; $i < $n; $i++) {
        $base = round((float)$grupos[$i]['partida_base'], 2);
        $pct = $pcts[$i];
        $cap_m = isset($cap_montos[$i]) ? (float)$cap_montos[$i] : 0.0;
        $gad_m = isset($gad_montos[$i]) ? (float)$gad_montos[$i] : 0.0;
        // No permitir partida final negativa
        if ($cap_m + $gad_m > $base) {
            $overflow = ($cap_m + $gad_m) - $base;
            if ($gad_m >= $overflow) {
                $gad_m = round($gad_m - $overflow, 2);
            } else {
                $overflow -= $gad_m;
                $gad_m = 0.0;
                $cap_m = round(max(0.0, $cap_m - $overflow), 2);
            }
        }
        $final_m = round($base - $cap_m - $gad_m, 2);
        if ($final_m < 0) {
            $final_m = 0.0;
        }
        $base_ton = ($ton > 0.0001) ? round($base / $ton, 6) : 0.0;
        $cap_ton = ($ton > 0.0001) ? round($cap_m / $ton, 6) : 0.0;
        $gad_ton = ($ton > 0.0001) ? round($gad_m / $ton, 6) : 0.0;
        $fin_ton = ($ton > 0.0001) ? round($final_m / $ton, 6) : 0.0;
        $detalle[] = array(
            'grupo_cod' => $grupos[$i]['grupo_cod'],
            'grupo_nombre' => $grupos[$i]['grupo_nombre'],
            'partida_base' => $base,
            'participacion_pct' => $pct,
            'base_por_ton' => $base_ton,
            'capital_por_ton' => $cap_ton,
            'gad_por_ton' => $gad_ton,
            'ajuste_por_ton' => round($cap_ton + $gad_ton, 6),
            'final_por_ton' => $fin_ton,
            'capital_monto' => $cap_m,
            'gad_monto' => $gad_m,
            'partida_final' => $final_m,
        );
        $gasto_final += $final_m;
    }
    $gasto_final = round($gasto_final, 2);
    $capital_total_real = 0.0;
    $gad_total_real = 0.0;
    foreach ($detalle as $d) {
        $capital_total_real += $d['capital_monto'];
        $gad_total_real += $d['gad_monto'];
    }
    $capital_total_real = round($capital_total_real, 2);
    $gad_total_real = round($gad_total_real, 2);

    $acum_despues = round($acum_gad + $gad_total_real, 2);
    $saldo_despues = max(0.0, round($obj_gad - $acum_despues, 2));
    $anios_est = ($gad_calculado > 0.0001 && $saldo > 0.0001)
        ? round($saldo / $gad_calculado, 2)
        : (($saldo <= 0.0001) ? 0.0 : null);

    $utilidad_base = round($ingreso - $gasto_base, 2);
    // Utilidad economica coherente: ingresos - base (= ingresos - final - capital - gad)
    $utilidad_coherente = round($ingreso - $gasto_final - $capital_total_real - $gad_total_real, 2);

    return array(
        'ok' => true,
        'meta' => array(
            'proy_id' => $proy_id,
            'ppe_id' => (int)$ppe_id,
            'anio' => $anio,
            'vista' => $vista,
            'mes' => $mes,
            'escenario' => $escenario,
            'periodo_label' => isset($cuadro['cuadro_periodo']['label']) ? $cuadro['cuadro_periodo']['label'] : $vista,
        ),
        'precio' => $precio,
        'capital' => array(
            'pct' => round($pct_cap, 4),
            'por_ton' => round($capital_por_ton, 6),
            'total' => $capital_total_real,
        ),
        'gad' => array(
            'monto_objetivo' => round($obj_gad, 2),
            'recuperado_acum' => round($acum_gad, 2),
            'saldo_pendiente' => $saldo,
            'factor_ton' => round($fac_gad, 6),
            'toneladas' => round($ton, 4),
            'calculado' => $gad_calculado,
            'aplicado' => $gad_total_real,
            'por_ton_efectivo' => round($gad_por_ton_ef, 6),
            'acum_despues' => $acum_despues,
            'saldo_despues' => $saldo_despues,
            'anios_estimados_saldo' => $anios_est,
            'agotado' => ($saldo <= 0.0001),
        ),
        'resumen' => array(
            'ingreso' => $ingreso,
            'gasto_base' => $gasto_base,
            'gasto_final' => $gasto_final,
            'capital_total' => $capital_total_real,
            'gad_total' => $gad_total_real,
            'utilidad_base' => $utilidad_base,
            'utilidad_coherente' => $utilidad_coherente,
            'margen_pct' => ($ingreso > 0.0001) ? round(($utilidad_base / $ingreso) * 100.0, 2) : 0.0,
        ),
        'detalle' => $detalle,
        'cfg' => $cfg,
    );
}

/**
 * Aplica simulacion: guarda historial y actualiza acumulado GAD.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param array $sim resultado de ppto_ajuste_simular
 * @param int $Usu_Cod
 * @param string $obs
 * @return array
 */
function ppto_ajuste_aplicar($mysqli, $proy_id, $Emp_Cod, $ppe_id, $sim, $Usu_Cod, $obs = '') {
    if (empty($sim['ok'])) {
        return array('ok' => false, 'message' => 'Simulacion invalida.');
    }
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $Usu_Cod = (int)$Usu_Cod;
    $meta = $sim['meta'];
    $precio = $sim['precio'];
    $cap = $sim['capital'];
    $gad = $sim['gad'];
    $res = $sim['resumen'];
    $obs_esc = $mysqli->real_escape_string(substr(trim($obs), 0, 250));

    $sql = "INSERT INTO pre_ajuste_fin_cab
        (proy_id, Emp_Cod, ppe_id, ajc_anio, ajc_vista, ajc_mes, ajc_escenario, ajc_estado,
         ajc_precio_iva, ajc_iva_divisor, ajc_precio_neto,
         ajc_capital_pct, ajc_capital_por_ton, ajc_capital_total,
         ajc_gad_factor_ton, ajc_gad_toneladas, ajc_gad_calculado, ajc_gad_aplicado,
         ajc_gad_acum_antes, ajc_gad_acum_despues, ajc_gad_saldo_despues, ajc_gad_objetivo,
         ajc_gasto_base, ajc_gasto_final, ajc_ingreso, ajc_utilidad_base,
         ajc_observacion, ajc_fecha_registro, Usu_Cod)
        VALUES (
         '$esc', $Emp_Cod, $ppe_id, " . (int)$meta['anio'] . ",
         '" . $mysqli->real_escape_string($meta['vista']) . "', " . (int)$meta['mes'] . ",
         '" . $mysqli->real_escape_string($meta['escenario']) . "', 'aplicado',
         " . (float)$precio['tarifa_ton_iva'] . ", " . (float)$precio['iva_divisor'] . ", " . (float)$precio['precio_neto'] . ",
         " . (float)$cap['pct'] . ", " . (float)$cap['por_ton'] . ", " . (float)$cap['total'] . ",
         " . (float)$gad['factor_ton'] . ", " . (float)$gad['toneladas'] . ", " . (float)$gad['calculado'] . ", " . (float)$gad['aplicado'] . ",
         " . (float)$gad['recuperado_acum'] . ", " . (float)$gad['acum_despues'] . ", " . (float)$gad['saldo_despues'] . ", " . (float)$gad['monto_objetivo'] . ",
         " . (float)$res['gasto_base'] . ", " . (float)$res['gasto_final'] . ", " . (float)$res['ingreso'] . ", " . (float)$res['utilidad_base'] . ",
         " . ($obs_esc !== '' ? "'$obs_esc'" : 'NULL') . ", NOW(), $Usu_Cod
        )";
    if (!$mysqli->query($sql)) {
        return array('ok' => false, 'message' => $mysqli->error);
    }
    $ajc_id = (int)$mysqli->insert_id;
    foreach ($sim['detalle'] as $d) {
        $gc = $mysqli->real_escape_string($d['grupo_cod']);
        $gn = $mysqli->real_escape_string($d['grupo_nombre']);
        $mysqli->query("INSERT INTO pre_ajuste_fin_det
            (ajc_id, grupo_cod, grupo_nombre, ajd_partida_base, ajd_participacion_pct,
             ajd_base_por_ton, ajd_capital_por_ton, ajd_gad_por_ton, ajd_ajuste_por_ton, ajd_final_por_ton,
             ajd_capital_monto, ajd_gad_monto, ajd_partida_final)
            VALUES (
             $ajc_id, '$gc', '$gn', " . (float)$d['partida_base'] . ", " . (float)$d['participacion_pct'] . ",
             " . (float)$d['base_por_ton'] . ", " . (float)$d['capital_por_ton'] . ", " . (float)$d['gad_por_ton'] . ",
             " . (float)$d['ajuste_por_ton'] . ", " . (float)$d['final_por_ton'] . ",
             " . (float)$d['capital_monto'] . ", " . (float)$d['gad_monto'] . ", " . (float)$d['partida_final'] . "
            )");
    }

    $nuevo_acum = (float)$gad['acum_despues'];
    $mysqli->query("UPDATE pre_proyecto_version
        SET Ppv_GadRecAcum=$nuevo_acum, Ppv_AjuActivo=1, Ppv_FecReg=NOW(), Usu_Cod=$Usu_Cod
        WHERE (Pro_Cod='$esc' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$esc')) AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$ppe_id");

    return array(
        'ok' => true,
        'message' => 'Ajuste aplicado. Partidas base conservadas. GAD acumulado actualizado.',
        'ajc_id' => $ajc_id,
        'cfg' => ppto_ajuste_cfg_get($mysqli, $proy_id, $Emp_Cod, $ppe_id),
        'sim' => $sim,
    );
}

/**
 * Historial de aplicaciones.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param int $limit
 * @return array
 */
function ppto_ajuste_historial($mysqli, $proy_id, $Emp_Cod, $ppe_id, $limit = 20) {
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $limit = max(1, min(100, (int)$limit));
    $rows = array();
    $res = $mysqli->query("SELECT * FROM pre_ajuste_fin_cab
        WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id
        ORDER BY ajc_id DESC LIMIT $limit");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    return $rows;
}

/**
 * Detalle de un ajuste historico.
 *
 * @param mysqli $mysqli
 * @param int $ajc_id
 * @param int $Emp_Cod
 * @return array|null
 */
function ppto_ajuste_historial_detalle($mysqli, $ajc_id, $Emp_Cod) {
    $ajc_id = (int)$ajc_id;
    $Emp_Cod = (int)$Emp_Cod;
    $cab = null;
    $res = $mysqli->query("SELECT * FROM pre_ajuste_fin_cab WHERE ajc_id=$ajc_id AND Emp_Cod=$Emp_Cod LIMIT 1");
    if ($res) {
        $cab = $res->fetch_assoc();
    }
    if (!$cab) {
        return null;
    }
    $det = array();
    $rd = $mysqli->query("SELECT * FROM pre_ajuste_fin_det WHERE ajc_id=$ajc_id ORDER BY grupo_cod");
    if ($rd) {
        while ($d = $rd->fetch_assoc()) {
            $det[] = $d;
        }
    }
    return array('cab' => $cab, 'detalle' => $det);
}
