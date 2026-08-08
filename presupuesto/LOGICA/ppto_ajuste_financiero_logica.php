<?php
/**
 * Ajustes financieros: costo de capital + recuperacion GAD.
 * No modifica Pdp_PreAnual (partida base).
 *
 * Lote A: SQL contra tablas pre_* (mapa_exa_a_pre.md).
 * SELECT usa alias legacy (ajc_id, proy_id, ...) para no romper FRONT/JS.
 * Vistas exa_ppto_* se mantienen activas como red de seguridad.
 */

require_once __DIR__ . '/ppto_format_helpers.php';
require_once __DIR__ . '/ppto_proyecto_version_logica.php';

/**
 * True si el nombre es tabla base (no vista) en la BD actual.
 *
 * @param mysqli $mysqli
 * @param string $table
 * @return bool
 */
function ppto_ajuste_es_tabla_base($mysqli, $table) {
    $t = $mysqli->real_escape_string($table);
    $r = @$mysqli->query(
        "SELECT TABLE_TYPE FROM information_schema.TABLES
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' LIMIT 1"
    );
    if ($r && ($row = $r->fetch_assoc())) {
        return strtoupper($row['TABLE_TYPE']) === 'BASE TABLE';
    }
    return false;
}

/**
 * Columnas de cabecera con alias legacy (consumo JS/historial).
 *
 * @param string $a alias SQL
 * @return string
 */
function ppto_ajuste_sql_cab_cols($a = 'c') {
    return "$a.Ajc_Cod AS ajc_id, $a.Pro_Cod AS proy_id, $a.Emp_Cod AS Emp_Cod, $a.Ppe_Cod AS ppe_id,
            $a.Ajc_Anio AS ajc_anio, $a.Ajc_Vista AS ajc_vista, $a.Ajc_Mes AS ajc_mes,
            $a.Ajc_Escenario AS ajc_escenario, $a.Ajc_Est AS ajc_estado,
            $a.Ajc_PreIva AS ajc_precio_iva, $a.Ajc_IvaDivisor AS ajc_iva_divisor, $a.Ajc_PreNeto AS ajc_precio_neto,
            $a.Ajc_CapPct AS ajc_capital_pct, $a.Ajc_CapPorTon AS ajc_capital_por_ton, $a.Ajc_CapTotal AS ajc_capital_total,
            $a.Ajc_GadFacTon AS ajc_gad_factor_ton, $a.Ajc_GadTon AS ajc_gad_toneladas,
            $a.Ajc_GadCalc AS ajc_gad_calculado, $a.Ajc_GadApli AS ajc_gad_aplicado,
            $a.Ajc_GadAcumAnt AS ajc_gad_acum_antes, $a.Ajc_GadAcumDes AS ajc_gad_acum_despues,
            $a.Ajc_GadSalDes AS ajc_gad_saldo_despues, $a.Ajc_GadObjetivo AS ajc_gad_objetivo,
            $a.Ajc_GasBase AS ajc_gasto_base, $a.Ajc_GasFinal AS ajc_gasto_final,
            $a.Ajc_Ingreso AS ajc_ingreso, $a.Ajc_UtiBase AS ajc_utilidad_base,
            $a.Ajc_Obs AS ajc_observacion, $a.Ajc_FecReg AS ajc_fecha_registro, $a.Usu_Cod AS Usu_Cod";
}

/**
 * Columnas de detalle con alias legacy.
 *
 * @param string $a alias SQL
 * @return string
 */
function ppto_ajuste_sql_det_cols($a = 'd') {
    return "$a.Ajd_Cod AS ajd_id, $a.Ajc_Cod AS ajc_id, $a.Ajd_GrpCod AS grupo_cod, $a.Ajd_GrpNom AS grupo_nombre,
            $a.Ajd_ParBase AS ajd_partida_base, $a.Ajd_PartPct AS ajd_participacion_pct,
            $a.Ajd_BasePorTon AS ajd_base_por_ton, $a.Ajd_CapPorTon AS ajd_capital_por_ton,
            $a.Ajd_GadPorTon AS ajd_gad_por_ton, $a.Ajd_AjuPorTon AS ajd_ajuste_por_ton,
            $a.Ajd_FinPorTon AS ajd_final_por_ton, $a.Ajd_CapMonto AS ajd_capital_monto,
            $a.Ajd_GadMonto AS ajd_gad_monto, $a.Ajd_ParFinal AS ajd_partida_final";
}

/**
 * Asegura columnas de ajustes en tablas pre_* (nunca altera vistas exa_ppto_*).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_ajuste_financiero($mysqli) {
    if (!$mysqli) {
        return;
    }
    // Columnas reales en pre_proyecto_version
    $cols = array(
        'Ppv_CostCapPct' => "ADD COLUMN Ppv_CostCapPct DECIMAL(8,4) NOT NULL DEFAULT 11.0000 COMMENT '% costo capital sobre neto' AFTER Ppv_IvaDivisor",
        'Ppv_GadObjetivo' => "ADD COLUMN Ppv_GadObjetivo DECIMAL(14,2) NOT NULL DEFAULT 2000000.00 COMMENT 'Objetivo recuperacion GAD' AFTER Ppv_CostCapPct",
        'Ppv_GadFacTon' => "ADD COLUMN Ppv_GadFacTon DECIMAL(12,6) NOT NULL DEFAULT 0.198400 COMMENT 'USD GAD por tonelada' AFTER Ppv_GadObjetivo",
        'Ppv_GadRecAcum' => "ADD COLUMN Ppv_GadRecAcum DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'GAD acumulado aplicado' AFTER Ppv_GadFacTon",
        'Ppv_AjuActivo' => "ADD COLUMN Ppv_AjuActivo TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=usar partida final en cuadro' AFTER Ppv_GadRecAcum",
    );
    if (ppto_ajuste_es_tabla_base($mysqli, 'pre_proyecto_version')) {
        foreach ($cols as $name => $ddl) {
            $c = @$mysqli->query("SHOW COLUMNS FROM pre_proyecto_version LIKE '" . $mysqli->real_escape_string($name) . "'");
            if (!$c || $c->num_rows === 0) {
                @$mysqli->query("ALTER TABLE pre_proyecto_version " . $ddl);
            }
        }
    }

    // Las tablas pre_* ya existen por migracion; solo crear si faltan (instalacion nueva).
    if (!ppto_ajuste_es_tabla_base($mysqli, 'pre_proyecto_precio_anio')) {
        @$mysqli->query("CREATE TABLE IF NOT EXISTS pre_proyecto_precio_anio (
          Ppr_Cod BIGINT(20) NOT NULL AUTO_INCREMENT,
          Pro_Cod BIGINT(20) NOT NULL,
          Emp_Cod BIGINT(20) NOT NULL,
          Ppe_Cod BIGINT(20) NOT NULL,
          Ppr_Anio INT NOT NULL,
          Ppr_TarifaTonIva DECIMAL(12,4) NOT NULL DEFAULT 3.0000,
          Ppr_FecReg DATETIME NOT NULL,
          Usu_Cod BIGINT(20) DEFAULT NULL,
          PRIMARY KEY (Ppr_Cod),
          UNIQUE KEY uq_ppr_pro_emp_ppe_anio (Pro_Cod, Emp_Cod, Ppe_Cod, Ppr_Anio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    if (!ppto_ajuste_es_tabla_base($mysqli, 'pre_ajuste_fin_cab')) {
        @$mysqli->query("CREATE TABLE IF NOT EXISTS pre_ajuste_fin_cab (
          Ajc_Cod BIGINT(20) NOT NULL AUTO_INCREMENT,
          Pro_Cod BIGINT(20) NOT NULL,
          Emp_Cod BIGINT(20) NOT NULL,
          Ppe_Cod BIGINT(20) NOT NULL,
          Ajc_Anio INT NOT NULL,
          Ajc_Vista VARCHAR(20) NOT NULL DEFAULT 'anual',
          Ajc_Mes INT NOT NULL DEFAULT 0,
          Ajc_Escenario VARCHAR(20) NOT NULL DEFAULT 'esperada',
          Ajc_Est VARCHAR(20) NOT NULL DEFAULT 'aplicado',
          Ajc_PreIva DECIMAL(12,4) NOT NULL DEFAULT 0,
          Ajc_IvaDivisor DECIMAL(8,4) NOT NULL DEFAULT 1.1500,
          Ajc_PreNeto DECIMAL(14,6) NOT NULL DEFAULT 0,
          Ajc_CapPct DECIMAL(8,4) NOT NULL DEFAULT 0,
          Ajc_CapPorTon DECIMAL(14,6) NOT NULL DEFAULT 0,
          Ajc_CapTotal DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajc_GadFacTon DECIMAL(12,6) NOT NULL DEFAULT 0,
          Ajc_GadTon DECIMAL(14,4) NOT NULL DEFAULT 0,
          Ajc_GadCalc DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajc_GadApli DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajc_GadAcumAnt DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajc_GadAcumDes DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajc_GadSalDes DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajc_GadObjetivo DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajc_GasBase DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajc_GasFinal DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajc_Ingreso DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajc_UtiBase DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajc_Obs VARCHAR(255) NULL,
          Ajc_FecReg DATETIME NOT NULL,
          Usu_Cod BIGINT(20) DEFAULT NULL,
          PRIMARY KEY (Ajc_Cod),
          KEY idx_ajc_proy (Pro_Cod, Emp_Cod, Ppe_Cod),
          KEY idx_ajc_estado (Ajc_Est)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    if (!ppto_ajuste_es_tabla_base($mysqli, 'pre_ajuste_fin_det')) {
        @$mysqli->query("CREATE TABLE IF NOT EXISTS pre_ajuste_fin_det (
          Ajd_Cod BIGINT(20) NOT NULL AUTO_INCREMENT,
          Ajc_Cod BIGINT(20) NOT NULL,
          Ajd_GrpCod VARCHAR(20) NOT NULL,
          Ajd_GrpNom VARCHAR(255) NOT NULL DEFAULT '',
          Ajd_ParBase DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajd_PartPct DECIMAL(10,6) NOT NULL DEFAULT 0,
          Ajd_BasePorTon DECIMAL(14,6) NOT NULL DEFAULT 0,
          Ajd_CapPorTon DECIMAL(14,6) NOT NULL DEFAULT 0,
          Ajd_GadPorTon DECIMAL(14,6) NOT NULL DEFAULT 0,
          Ajd_AjuPorTon DECIMAL(14,6) NOT NULL DEFAULT 0,
          Ajd_FinPorTon DECIMAL(14,6) NOT NULL DEFAULT 0,
          Ajd_CapMonto DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajd_GadMonto DECIMAL(14,2) NOT NULL DEFAULT 0,
          Ajd_ParFinal DECIMAL(14,2) NOT NULL DEFAULT 0,
          PRIMARY KEY (Ajd_Cod),
          KEY idx_ajd_cab (Ajc_Cod)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }
}

/**
 * Lee configuracion de ajustes de la version.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @return array
 */
function ppto_ajuste_cfg_get($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod) {
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $cfg = array(
        'costo_capital_pct' => 11.0,
        'gad_monto_objetivo' => 2000000.0,
        'gad_factor_ton' => 0.1984,
        'gad_recuperado_acum' => 0.0,
        'ajuste_activo' => 0,
        'iva_divisor' => 1.15,
        'tarifa_ton_iva' => 3.0,
    );
    $res = $mysqli->query(
        "SELECT Ppv_CostCapPct AS pv_costo_capital_pct,
                Ppv_GadObjetivo AS pv_gad_monto_objetivo,
                Ppv_GadFacTon AS pv_gad_factor_ton,
                Ppv_GadRecAcum AS pv_gad_recuperado_acum,
                Ppv_AjuActivo AS pv_ajuste_activo,
                Ppv_TarifaTonIva AS pv_tarifa_ton_iva,
                Ppv_IvaDivisor AS pv_iva_divisor
         FROM pre_proyecto_version
         WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod
         LIMIT 1"
    );
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
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param array $data
 * @param int $Usu_Cod
 * @return array
 */
function ppto_ajuste_cfg_save($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $data, $Usu_Cod) {
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $Usu_Cod = (int)$Usu_Cod;
    if ($Pro_Cod === '' || $Ppe_Cod <= 0) {
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

    $base = ppto_proy_version_config($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    $ton = (float)$base['ton_mes'];
    $tarifa = (float)$base['tarifa_ton_iva'];
    $iva = (float)$base['iva_divisor'];
    $ton_costo = ppto_proy_version_ton_costo($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);

    $acum_sql = '';
    if ($acum !== null) {
        if ($acum < 0) {
            $acum = 0;
        }
        if ($obj > 0 && $acum > $obj) {
            $acum = $obj;
        }
        $acum_sql = ', Ppv_GadRecAcum=' . round($acum, 2);
    }

    $sql = "INSERT INTO pre_proyecto_version
            (Pro_Cod, Emp_Cod, Ppe_Cod, Ppv_TonBaseMes, Ppv_TonCostoMes, Ppv_TarifaTonIva, Ppv_IvaDivisor,
             Ppv_CostCapPct, Ppv_GadObjetivo, Ppv_GadFacTon, Ppv_AjuActivo, Ppv_FecReg, Usu_Cod)
            VALUES ('$esc', $Emp_Cod, $Ppe_Cod, $ton, $ton_costo, $tarifa, $iva,
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
    return array('ok' => true, 'message' => 'Configuracion de ajustes guardada.', 'cfg' => ppto_ajuste_cfg_get($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod));
}

/**
 * Lista precios por anio.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @return array
 */
function ppto_ajuste_precios_list($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod) {
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $rows = array();
    $res = $mysqli->query(
        "SELECT Ppr_Anio AS ppa_anio, Ppr_TarifaTonIva AS ppa_tarifa_ton_iva
         FROM pre_proyecto_precio_anio
         WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod
         ORDER BY Ppr_Anio ASC"
    );
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
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param array $precios array of {anio, tarifa_ton_iva}
 * @param int $Usu_Cod
 * @return array
 */
function ppto_ajuste_precios_save($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $precios, $Usu_Cod) {
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $Usu_Cod = (int)$Usu_Cod;
    if ($Pro_Cod === '' || $Ppe_Cod <= 0) {
        return array('ok' => false, 'message' => 'Proyecto y version requeridos.');
    }
    if (!is_array($precios)) {
        $precios = array();
    }
    $mysqli->query(
        "DELETE FROM pre_proyecto_precio_anio
         WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod"
    );
    foreach ($precios as $p) {
        $anio = isset($p['anio']) ? (int)$p['anio'] : 0;
        $tarifa = isset($p['tarifa_ton_iva']) ? (float)$p['tarifa_ton_iva'] : 0;
        if ($anio < 2000 || $anio > 2100 || $tarifa <= 0) {
            continue;
        }
        $mysqli->query(
            "INSERT INTO pre_proyecto_precio_anio
                (Pro_Cod, Emp_Cod, Ppe_Cod, Ppr_Anio, Ppr_TarifaTonIva, Ppr_FecReg, Usu_Cod)
             VALUES ('$esc', $Emp_Cod, $Ppe_Cod, $anio, $tarifa, NOW(), $Usu_Cod)"
        );
    }
    return array('ok' => true, 'message' => 'Proyeccion de precios guardada.', 'precios' => ppto_ajuste_precios_list($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod));
}

/**
 * Resuelve tarifa con IVA para un anio (proyeccion o fallback version).
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param int $anio
 * @return array
 */
function ppto_ajuste_precio_para_anio($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio) {
    $cfg = ppto_ajuste_cfg_get($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    $anio = (int)$anio;
    $tarifa = (float)$cfg['tarifa_ton_iva'];
    $fuente = 'version';
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    if ($anio > 0) {
        $res = $mysqli->query(
            "SELECT Ppr_TarifaTonIva AS ppa_tarifa_ton_iva
             FROM pre_proyecto_precio_anio
             WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod AND Ppr_Anio=$anio
             LIMIT 1"
        );
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
            $cod = isset($r['Ppa_Cla']) ? $r['Ppa_Cla'] : '00';
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
        $monto = isset($r[$key]) ? (float)$r[$key] : (float)$r['Pdp_PreAnual'];
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
 * Calcula el amortizado GAD tras Aplicar: una sola cuota por anio (reemplaza si ya hubo).
 *
 * @return array{nuevo_acum:float,saldo_despues:float,reemplazo_anio:bool,suma_cuotas_anio:float}
 */
function ppto_ajuste_gad_acum_tras_aplicar($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, $acum_antes, $gad_aplicado, $gad_objetivo) {
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $anio = (int)$anio;
    $acum_antes = round((float)$acum_antes, 2);
    $gad_aplicado = round((float)$gad_aplicado, 2);
    $gad_objetivo = round((float)$gad_objetivo, 2);
    $suma_cuotas_anio = 0.0;
    $rq = $mysqli->query(
        "SELECT COALESCE(SUM(Ajc_GadApli),0) AS suma_cuotas
         FROM pre_ajuste_fin_cab
         WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod
           AND Ajc_Anio=$anio AND Ajc_Est='aplicado'"
    );
    if ($rq && ($rowSum = $rq->fetch_assoc())) {
        $suma_cuotas_anio = round((float)$rowSum['suma_cuotas'], 2);
    }
    $reemplazo_anio = ($suma_cuotas_anio > 0);
    if ($reemplazo_anio) {
        $nuevo_acum = round($acum_antes - $suma_cuotas_anio + $gad_aplicado, 2);
        if ($nuevo_acum < 0) {
            $nuevo_acum = $gad_aplicado;
        }
    } else {
        $nuevo_acum = round($acum_antes + $gad_aplicado, 2);
    }
    if ($gad_objetivo > 0 && $nuevo_acum > $gad_objetivo) {
        $nuevo_acum = $gad_objetivo;
    }
    return array(
        'nuevo_acum' => $nuevo_acum,
        'saldo_despues' => max(0.0, round($gad_objetivo - $nuevo_acum, 2)),
        'reemplazo_anio' => $reemplazo_anio,
        'suma_cuotas_anio' => $suma_cuotas_anio,
    );
}

/**
 * Simula ajustes financieros (no persiste).
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param array $cuadro resultado de ppto_proy_cuadro_cargar
 * @param array $opts overrides opcionales
 * @return array
 */
function ppto_ajuste_simular($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $cuadro, $opts = array()) {
    $cfg = ppto_ajuste_cfg_get($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
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

    $precio = ppto_ajuste_precio_para_anio($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio);
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

    $proyAcum = ppto_ajuste_gad_acum_tras_aplicar(
        $mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, $acum_gad, $gad_total_real, $obj_gad
    );
    $acum_despues = $proyAcum['nuevo_acum'];
    $saldo_despues = $proyAcum['saldo_despues'];
    $anios_est = ($gad_calculado > 0.0001 && $saldo > 0.0001)
        ? round($saldo / $gad_calculado, 2)
        : (($saldo <= 0.0001) ? 0.0 : null);

    $utilidad_base = round($ingreso - $gasto_base, 2);
    // Utilidad economica coherente: ingresos - base (= ingresos - final - capital - gad)
    $utilidad_coherente = round($ingreso - $gasto_final - $capital_total_real - $gad_total_real, 2);

    return array(
        'ok' => true,
        'meta' => array(
            'Pro_Cod' => $Pro_Cod,
            'Ppe_Cod' => (int)$Ppe_Cod,
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
            'reemplazo_anio' => $proyAcum['reemplazo_anio'] ? 1 : 0,
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
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param array $sim resultado de ppto_ajuste_simular
 * @param int $Usu_Cod
 * @param string $obs
 * @return array
 */
function ppto_ajuste_aplicar($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $sim, $Usu_Cod, $obs = '') {
    if (empty($sim['ok'])) {
        return array('ok' => false, 'message' => 'Simulacion invalida.');
    }
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $Usu_Cod = (int)$Usu_Cod;
    $meta = $sim['meta'];
    $precio = $sim['precio'];
    $cap = $sim['capital'];
    $gad = $sim['gad'];
    $res = $sim['resumen'];
    $obs_esc = $mysqli->real_escape_string(substr(trim($obs), 0, 250));
    $anio = (int)$meta['anio'];
    $gad_aplicado = round((float)$gad['aplicado'], 2);
    $gad_acum_antes = round((float)$gad['recuperado_acum'], 2);
    $gad_objetivo = round((float)$gad['monto_objetivo'], 2);

    // Una sola cuota GAD por anio: si ya hubo Aplicar en el mismo anio, se reemplaza (no se suma de nuevo).
    $proyAcum = ppto_ajuste_gad_acum_tras_aplicar(
        $mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, $gad_acum_antes, $gad_aplicado, $gad_objetivo
    );
    $reemplazo_anio = !empty($proyAcum['reemplazo_anio']);
    $nuevo_acum = $proyAcum['nuevo_acum'];
    $saldo_despues = $proyAcum['saldo_despues'];

    $sql = "INSERT INTO pre_ajuste_fin_cab
        (Pro_Cod, Emp_Cod, Ppe_Cod, Ajc_Anio, Ajc_Vista, Ajc_Mes, Ajc_Escenario, Ajc_Est,
         Ajc_PreIva, Ajc_IvaDivisor, Ajc_PreNeto,
         Ajc_CapPct, Ajc_CapPorTon, Ajc_CapTotal,
         Ajc_GadFacTon, Ajc_GadTon, Ajc_GadCalc, Ajc_GadApli,
         Ajc_GadAcumAnt, Ajc_GadAcumDes, Ajc_GadSalDes, Ajc_GadObjetivo,
         Ajc_GasBase, Ajc_GasFinal, Ajc_Ingreso, Ajc_UtiBase,
         Ajc_Obs, Ajc_FecReg, Usu_Cod)
        VALUES (
         '$esc', $Emp_Cod, $Ppe_Cod, $anio,
         '" . $mysqli->real_escape_string($meta['vista']) . "', " . (int)$meta['mes'] . ",
         '" . $mysqli->real_escape_string($meta['escenario']) . "', 'aplicado',
         " . (float)$precio['tarifa_ton_iva'] . ", " . (float)$precio['iva_divisor'] . ", " . (float)$precio['precio_neto'] . ",
         " . (float)$cap['pct'] . ", " . (float)$cap['por_ton'] . ", " . (float)$cap['total'] . ",
         " . (float)$gad['factor_ton'] . ", " . (float)$gad['toneladas'] . ", " . (float)$gad['calculado'] . ", " . $gad_aplicado . ",
         " . $gad_acum_antes . ", " . $nuevo_acum . ", " . $saldo_despues . ", " . $gad_objetivo . ",
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
        $mysqli->query(
            "INSERT INTO pre_ajuste_fin_det
                (Ajc_Cod, Ajd_GrpCod, Ajd_GrpNom, Ajd_ParBase, Ajd_PartPct,
                 Ajd_BasePorTon, Ajd_CapPorTon, Ajd_GadPorTon, Ajd_AjuPorTon, Ajd_FinPorTon,
                 Ajd_CapMonto, Ajd_GadMonto, Ajd_ParFinal)
             VALUES (
                 $ajc_id, '$gc', '$gn', " . (float)$d['partida_base'] . ", " . (float)$d['participacion_pct'] . ",
                 " . (float)$d['base_por_ton'] . ", " . (float)$d['capital_por_ton'] . ", " . (float)$d['gad_por_ton'] . ",
                 " . (float)$d['ajuste_por_ton'] . ", " . (float)$d['final_por_ton'] . ",
                 " . (float)$d['capital_monto'] . ", " . (float)$d['gad_monto'] . ", " . (float)$d['partida_final'] . "
             )"
        );
    }

    $mysqli->query(
        "UPDATE pre_proyecto_version
         SET Ppv_GadRecAcum=$nuevo_acum, Ppv_AjuActivo=1, Ppv_FecReg=NOW(), Usu_Cod=$Usu_Cod
         WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod"
    );

    $msg = 'Ajuste aplicado. Partidas base conservadas. GAD acumulado actualizado.';
    if ($reemplazo_anio) {
        $msg = 'Ajuste aplicado. Ya existia una aplicacion en ' . $anio
            . ': se reemplazo la cuota GAD del anio (no se acumula dos veces).';
    }

    return array(
        'ok' => true,
        'message' => $msg,
        'ajc_id' => $ajc_id,
        'gad_reemplazo_anio' => $reemplazo_anio ? 1 : 0,
        'cfg' => ppto_ajuste_cfg_get($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod),
        'sim' => $sim,
    );
}

/**
 * Historial de aplicaciones.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param int $limit
 * @return array
 */
function ppto_ajuste_historial($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $limit = 20) {
    ppto_schema_ensure_ajuste_financiero($mysqli);
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $limit = max(1, min(100, (int)$limit));
    $rows = array();
    // Incluye nombre del usuario que aplico el ajuste (auditoria).
    $cols = ppto_ajuste_sql_cab_cols('c');
    $sql = "SELECT $cols,
            TRIM(CONCAT(COALESCE(p.Prs_Ape,''), ' ', COALESCE(p.Prs_Nom,''))) AS usuario_nombre,
            u.Usu_Ced AS usuario_cedula
        FROM pre_ajuste_fin_cab c
        LEFT JOIN usuarios u ON u.Usu_Cod = c.Usu_Cod
        LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
        WHERE c.Pro_Cod='$esc' AND c.Emp_Cod=$Emp_Cod AND c.Ppe_Cod=$Ppe_Cod
        ORDER BY c.Ajc_Cod DESC
        LIMIT $limit";
    $res = $mysqli->query($sql);
    if (!$res) {
        $res = $mysqli->query(
            "SELECT $cols FROM pre_ajuste_fin_cab c
             WHERE c.Pro_Cod='$esc' AND c.Emp_Cod=$Emp_Cod AND c.Ppe_Cod=$Ppe_Cod
             ORDER BY c.Ajc_Cod DESC LIMIT $limit"
        );
    }
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $nom = isset($r['usuario_nombre']) ? trim($r['usuario_nombre']) : '';
            if ($nom === '' && !empty($r['usuario_cedula'])) {
                $nom = $r['usuario_cedula'];
            }
            if ($nom === '' && !empty($r['Usu_Cod'])) {
                $nom = 'Usuario ' . (int)$r['Usu_Cod'];
            }
            if ($nom === '') {
                $nom = '—';
            }
            $r['usuario_nombre'] = $nom;
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
    $colsCab = ppto_ajuste_sql_cab_cols('c');
    $res = $mysqli->query(
        "SELECT $colsCab FROM pre_ajuste_fin_cab c
         WHERE c.Ajc_Cod=$ajc_id AND c.Emp_Cod=$Emp_Cod LIMIT 1"
    );
    if ($res) {
        $cab = $res->fetch_assoc();
    }
    if (!$cab) {
        return null;
    }
    $det = array();
    $colsDet = ppto_ajuste_sql_det_cols('d');
    $rd = $mysqli->query(
        "SELECT $colsDet FROM pre_ajuste_fin_det d
         WHERE d.Ajc_Cod=$ajc_id ORDER BY d.Ajd_GrpCod"
    );
    if ($rd) {
        while ($d = $rd->fetch_assoc()) {
            $det[] = $d;
        }
    }
    return array('cab' => $cab, 'detalle' => $det);
}
