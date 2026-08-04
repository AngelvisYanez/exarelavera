<?php
/**
 * ppto_schema_logica.php
 * Migraciones y auto-healing del esquema EXA PPTO (compatible MariaDB).
 */

/**
 * Resuelve Emp_Cod desde filtro UI, POST o sesi�n.
 *
 * @param array|null $request
 * @return int
 */
function ppto_resolve_emp_id($request = null) {
    if ($request === null) {
        $request = $_REQUEST;
    }
    // Nomenclatura EXA: Emp_Cod. Se aceptan alias legacy emp_cod / emp_id.
    if (isset($request['Emp_Cod']) && $request['Emp_Cod'] !== '') {
        return (int)$request['Emp_Cod'];
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
 * Resuelve Emp_Cod de un proyecto registrado.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int|null $fallback
 * @return int
 */
function ppto_resolve_emp_id_proyecto($mysqli, $proy_id, $fallback = null) {
    $clean = $mysqli->real_escape_string(trim($proy_id));
    $res = $mysqli->query("SELECT Emp_Cod FROM exa_ppto_proyectos WHERE proy_id = '$clean' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['Emp_Cod'];
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

    // Eliminado uso de vistas en la BD (las consultas ahora usan subconsultas dinámicas o tablas físicas directas)
    @$mysqli->query('DROP VIEW IF EXISTS exa_ppto_resumen');
    @$mysqli->query('DROP VIEW IF EXISTS pre_resumen');
    @$mysqli->query('DROP VIEW IF EXISTS exa_ppto_cabeceras');
    @$mysqli->query('DROP VIEW IF EXISTS exa_ppto_partidas');
    @$mysqli->query('DROP VIEW IF EXISTS exa_ppto_detalles');
    @$mysqli->query('DROP VIEW IF EXISTS exa_ppto_ejecuciones');

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
    ppto_schema_ensure_nomenclatura_exa($mysqli);
}

/**
 * Alinea FK ERP a nomenclatura EXA (Emp_Cod, Usu_Cod, Suc_Cod, Dep_Cod, Pla_Cod, Pld_Cod).
 * Idempotente: solo actúa si aún existen columnas legacy *_id.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_nomenclatura_exa($mysqli) {
    if (!$mysqli) {
        return;
    }
    $sql_file = __DIR__ . '/../SQL/fase14_rename_fk_nomenclatura_exa.sql';
    if (!file_exists($sql_file)) {
        return;
    }
    // Señal: proyectos físicos con emp_id legacy, o vista partidas alias emp_id.
    $needs = false;
    $c1 = @$mysqli->query("SHOW COLUMNS FROM pre_proyectos LIKE 'emp_id'");
    if ($c1 && $c1->num_rows > 0) {
        $needs = true;
    }
    if (!$needs) {
        $c2 = @$mysqli->query("SHOW COLUMNS FROM pre_partidas LIKE 'emp_id'");
        if ($c2 && $c2->num_rows > 0) {
            $needs = true;
        }
    }
    if ($needs) {
        ppto_schema_ejecutar_sql($mysqli, $sql_file);
    }
}

/**
 * Tabla puente partida presupuestaria <-> cuenta contable (fase 12).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_partida_cuenta($mysqli) {
    $res = @$mysqli->query("SHOW TABLES LIKE 'pre_partida_cuenta'");
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
    $res = @$mysqli->query("SHOW TABLES LIKE 'pre_proyecto_version'");
    if (!$res || $res->num_rows === 0) {
        ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase7_proyecto_version_ton.sql');
    }
    $res_col = @$mysqli->query("SHOW COLUMNS FROM pre_proyecto_version LIKE 'Ppv_TarifaTonIva'");
    if (!$res_col || $res_col->num_rows === 0) {
        ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase10_proyecto_version_tarifa.sql');
    }
    $res_costo = @$mysqli->query("SHOW COLUMNS FROM pre_proyecto_version LIKE 'Ppv_TonCostoMes'");
    if (!$res_costo || $res_costo->num_rows === 0) {
        @$mysqli->query("ALTER TABLE pre_proyecto_version
            ADD COLUMN Ppv_TonCostoMes DECIMAL(12,4) NOT NULL DEFAULT 77000.0000
            COMMENT 'Ton/mes costo egreso Excel (3500x22)'
            AFTER Ppv_TonBaseMes");
    }
}

/**
 * Tabla auditoria publicar presupuesto aprobado (fase 11).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_proyecto_publicacion($mysqli) {
    $res = @$mysqli->query("SHOW TABLES LIKE 'pre_proyecto_publicacion'");
    if (!$res || $res->num_rows === 0) {
        ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase11_proyecto_publicacion.sql');
    }
    $res_col = @$mysqli->query("SHOW COLUMNS FROM pre_proyecto_publicacion LIKE 'Ppu_Mes'");
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
    $res = @$mysqli->query("SHOW TABLES LIKE 'pre_umbral_pf'");
    if ($res && $res->num_rows > 0) {
        return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS pre_umbral_pf (
        Ubp_Cod INT NOT NULL AUTO_INCREMENT,
        Emp_Cod INT NOT NULL,
        Ppa_Cod INT NULL DEFAULT NULL,
        Ubp_UmbralPct DECIMAL(5,2) NOT NULL DEFAULT 5.00,
        Ubp_FecReg TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        Usu_Cod INT NULL DEFAULT NULL,
        PRIMARY KEY (Ubp_Cod),
        UNIQUE KEY uk_ubp_emp_ppa (Emp_Cod, Ppa_Cod),
        KEY idx_ubp_emp (Emp_Cod)
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
    $res_col = @$mysqli->query("SHOW COLUMNS FROM pre_prod_periodos LIKE 'Prd_Est'");
    if (!$res_col || $res_col->num_rows === 0) {
        $needs_fase8 = true;
    }
    $res_evt = @$mysqli->query("SHOW TABLES LIKE 'pre_prod_evento_log'");
    if (!$res_evt || $res_evt->num_rows === 0) {
        $needs_fase8 = true;
    }

    if ($needs_fase8 && file_exists($fase8_file)) {
        ppto_schema_ejecutar_sql($mysqli, $fase8_file);
    }
}
