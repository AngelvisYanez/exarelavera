<?php
/**
 * ppto_schema_logica.php
 * Migraciones y auto-healing del esquema EXA PPTO (compatible MariaDB).
 */

/**
 * Resuelve emp_id desde filtro UI, POST o sesi�n.
 *
 * @param array|null $request
 * @return int
 */
function ppto_resolve_emp_id($request = null) {
    if ($request === null) {
        $request = $_REQUEST;
    }
    if (isset($request['emp_cod']) && $request['emp_cod'] !== '') {
        return (int)$request['emp_cod'];
    }
    if (isset($request['emp_id']) && $request['emp_id'] !== '') {
        return (int)$request['emp_id'];
    }
    if (isset($_SESSION['Ses_Emp_Cod'])) {
        return (int)$_SESSION['Ses_Emp_Cod'];
    }
    return 1;
}

/**
 * Resuelve emp_id de un proyecto registrado.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int|null $fallback
 * @return int
 */
function ppto_resolve_emp_id_proyecto($mysqli, $proy_id, $fallback = null) {
    $clean = $mysqli->real_escape_string(trim($proy_id));
    $res = $mysqli->query("SELECT emp_id FROM exa_ppto_proyectos WHERE proy_id = '$clean' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['emp_id'];
    }
    return ($fallback !== null) ? (int)$fallback : ppto_resolve_emp_id();
}

/**
 * Ejecuta un archivo SQL dividiendo por punto y coma.
 *
 * @param mysqli $mysqli
 * @param string $path
 */
function ppto_schema_ejecutar_sql($mysqli, $path) {
    if (!file_exists($path)) {
        return;
    }
    $sql_content = file_get_contents($path);
    $sql_content = preg_replace('/^\s*--.*\n/m', '', $sql_content);
    $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
    foreach (explode(';', $sql_content) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            @$mysqli->query($stmt);
        }
    }
}

/**
 * Asegura tablas, reglas y vista exa_ppto_resumen (fix MariaDB).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure($mysqli) {
    if (!$mysqli) {
        return;
    }

    $sql_dir = __DIR__ . '/../SQL';
    $tablas = array(
        'exa_ppto_proyectos'        => 'fase1_arquitectura_ppto_proyectos.sql',
        'exa_ppto_bases'            => 'fase2_motor_calculo_formulas.sql',
        'exa_ppto_prod_config'      => 'fase3_origenes_produccion.sql',
        'exa_ppto_prod_periodos'    => 'fase4_integracion_presupuesto_produccion.sql',
        'exa_ppto_reajustes'        => 'fase5_motor_reajustes.sql',
        'exa_ppto_movimientos'      => 'fase6_movimientos_fix.sql',
        'exa_ppto_reglas'           => 'fase6_reglas_fix.sql',
    );

    $ejecutados = array();
    foreach ($tablas as $tabla => $file) {
        $res = @$mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($tabla) . "'");
        if (!$res || $res->num_rows === 0) {
            if (!isset($ejecutados[$file])) {
                ppto_schema_ejecutar_sql($mysqli, $sql_dir . '/' . $file);
                $ejecutados[$file] = true;
            }
        }
    }

    $res_view = @$mysqli->query("SHOW FULL TABLES LIKE 'exa_ppto_resumen'");
    $needs_view = true;
    if ($res_view && $row = $res_view->fetch_array()) {
        $needs_view = (isset($row[1]) && strtoupper($row[1]) !== 'VIEW');
    }
    if ($needs_view) {
        @$mysqli->query('DROP VIEW IF EXISTS exa_ppto_resumen');
        ppto_schema_ejecutar_sql($mysqli, $sql_dir . '/fase6_vista_resumen_fix.sql');
    }

    require_once __DIR__ . '/ppto_partidas_logica.php';
    ppto_schema_ensure_partida_clase($mysqli);
    ppto_schema_ensure_partida_porcentaje($mysqli);
    ppto_schema_ensure_partida_meses_prorrateo($mysqli);

    ppto_schema_ensure_fase8($mysqli);
    ppto_schema_ensure_fase4_d8($mysqli);
    ppto_schema_ensure_proyecto_version($mysqli);
    ppto_schema_ensure_proyecto_publicacion($mysqli);
    ppto_schema_ensure_partida_cuenta($mysqli);
    require_once __DIR__ . '/ppto_ajuste_financiero_logica.php';
    ppto_schema_ensure_ajuste_financiero($mysqli);
}

/**
 * Tabla puente partida presupuestaria <-> cuenta contable (fase 12).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_partida_cuenta($mysqli) {
    $res = @$mysqli->query("SHOW TABLES LIKE 'exa_ppto_partida_cuenta'");
    if ($res && $res->num_rows > 0) {
        return;
    }
    ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase12_partida_cuenta.sql');
}

/**
 * Tabla toneladas base por proyecto/version (fase 7 UX).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_proyecto_version($mysqli) {
    $res = @$mysqli->query("SHOW TABLES LIKE 'exa_ppto_proyecto_version'");
    if (!$res || $res->num_rows === 0) {
        ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase7_proyecto_version_ton.sql');
    }
    $res_col = @$mysqli->query("SHOW COLUMNS FROM exa_ppto_proyecto_version LIKE 'pv_tarifa_ton_iva'");
    if (!$res_col || $res_col->num_rows === 0) {
        ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase10_proyecto_version_tarifa.sql');
    }
    $res_costo = @$mysqli->query("SHOW COLUMNS FROM exa_ppto_proyecto_version LIKE 'pv_toneladas_costo_mes'");
    if (!$res_costo || $res_costo->num_rows === 0) {
        @$mysqli->query("ALTER TABLE exa_ppto_proyecto_version
            ADD COLUMN pv_toneladas_costo_mes DECIMAL(12,4) NOT NULL DEFAULT 77000.0000
            COMMENT 'Ton/mes costo egreso Excel (3500x22)'
            AFTER pv_toneladas_base_mes");
    }
}

/**
 * Tabla auditoria publicar presupuesto aprobado (fase 11).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_proyecto_publicacion($mysqli) {
    $res = @$mysqli->query("SHOW TABLES LIKE 'exa_ppto_proyecto_publicacion'");
    if (!$res || $res->num_rows === 0) {
        ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase11_proyecto_publicacion.sql');
    }
    $res_col = @$mysqli->query("SHOW COLUMNS FROM exa_ppto_proyecto_publicacion LIKE 'pub_mes'");
    if (!$res_col || $res_col->num_rows === 0) {
        ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase11_publicacion_mes.sql');
    }
}

/**
 * Tabla umbral PF vs VA (D8).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_fase4_d8($mysqli) {
    $res = @$mysqli->query("SHOW TABLES LIKE 'exa_ppto_umbral_pf'");
    if ($res && $res->num_rows > 0) {
        return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS exa_ppto_umbral_pf (
        ubp_id INT NOT NULL AUTO_INCREMENT,
        emp_id INT NOT NULL,
        ppa_id INT NULL DEFAULT NULL,
        ubp_umbral_pct DECIMAL(5,2) NOT NULL DEFAULT 5.00,
        ubp_fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        usu_id INT NULL DEFAULT NULL,
        PRIMARY KEY (ubp_id),
        UNIQUE KEY uk_ubp_emp_ppa (emp_id, ppa_id),
        KEY idx_ubp_emp (emp_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    @$mysqli->query($sql);
}

/**
 * Aplica migracion fase8 (estado periodo, evento_log) si aun no existe.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_fase8($mysqli) {
    $sql_dir = __DIR__ . '/../SQL';
    $fase8_file = $sql_dir . '/fase8_prod_periodo_estado.sql';

    $needs_fase8 = false;
    $res_col = @$mysqli->query("SHOW COLUMNS FROM exa_ppto_prod_periodos LIKE 'prd_estado'");
    if (!$res_col || $res_col->num_rows === 0) {
        $needs_fase8 = true;
    }
    $res_evt = @$mysqli->query("SHOW TABLES LIKE 'exa_ppto_prod_evento_log'");
    if (!$res_evt || $res_evt->num_rows === 0) {
        $needs_fase8 = true;
    }

    if ($needs_fase8 && file_exists($fase8_file)) {
        ppto_schema_ejecutar_sql($mysqli, $fase8_file);
    }
}
