<?php
/**
 * ppto_schema_logica.php
 * Migraciones y auto-healing del esquema EXA PPTO (compatible MariaDB).
 * REGLA: CREATE/ALTER solo sobre tablas BASE pre_*. Nunca alterar vistas exa_ppto_*.
 */

/**
 * True si el objeto es tabla base (no vista).
 *
 * @param mysqli $mysqli
 * @param string $tabla
 * @return bool
 */
function ppto_schema_es_tabla_base($mysqli, $tabla) {
    if (!$mysqli || $tabla === '') {
        return false;
    }
    $t = $mysqli->real_escape_string($tabla);
    $res = @$mysqli->query(
        "SELECT TABLE_TYPE FROM information_schema.TABLES
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' LIMIT 1"
    );
    if ($res && ($row = $res->fetch_assoc())) {
        return strtoupper($row['TABLE_TYPE']) === 'BASE TABLE';
    }
    return false;
}

/**
 * True si la tabla/vista existe (cualquier TABLE_TYPE).
 *
 * @param mysqli $mysqli
 * @param string $tabla
 * @return bool
 */
function ppto_schema_tabla_existe($mysqli, $tabla) {
    if (!$mysqli || $tabla === '') {
        return false;
    }
    $t = $mysqli->real_escape_string($tabla);
    $res = @$mysqli->query("SHOW TABLES LIKE '$t'");
    return ($res && $res->num_rows > 0);
}

/**
 * True si la columna existe en la tabla base.
 *
 * @param mysqli $mysqli
 * @param string $tabla
 * @param string $columna
 * @return bool
 */
function ppto_schema_columna_existe($mysqli, $tabla, $columna) {
    if (!$mysqli || !ppto_schema_es_tabla_base($mysqli, $tabla)) {
        return false;
    }
    $t = $mysqli->real_escape_string($tabla);
    $c = $mysqli->real_escape_string($columna);
    $res = @$mysqli->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    return ($res && $res->num_rows > 0);
}

/**
 * Resuelve Emp_Cod desde filtro UI, POST o sesion.
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
 * Elige cabecera activa desde una lista ya cargada (Ppe_Est=A, si no la de mayor Ppe_Ver).
 *
 * @param array $versiones
 * @return array|null
 */
function ppto_presupuesto_pick_activo_from_list($versiones) {
    if (empty($versiones) || !is_array($versiones)) {
        return null;
    }
    foreach ($versiones as $v) {
        if (isset($v['Ppe_Est']) && $v['Ppe_Est'] === 'A') {
            return $v;
        }
    }
    return $versiones[0];
}

/**
 * Resuelve la cabecera presupuestaria activa (Ppe_Est=A) para Emp_Cod + anio.
 * Fallback: mayor Ppe_Ver del anio; si $anio es null, activa mas reciente.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int|null $anio
 * @return array|null keys Ppe_Cod, Ppe_Ver, Ppe_Des, Ppe_Est, Ppe_Ani
 */
function ppto_presupuesto_activo($mysqli, $Emp_Cod, $anio = null) {
    if (!$mysqli) {
        return null;
    }
    $Emp_Cod = (int)$Emp_Cod;
    $anio = ($anio !== null && $anio !== '') ? (int)$anio : null;
    $cols = "Ppe_Cod AS Ppe_Cod, Ppe_Ver AS Ppe_Ver, Ppe_Des AS Ppe_Des, Ppe_Est AS Ppe_Est, Ppe_Ani AS Ppe_Ani";
    if ($anio > 0) {
        $sql = "SELECT $cols FROM pre_presupuesto WHERE Emp_Cod=$Emp_Cod AND Ppe_Ani=$anio AND Ppe_Est='A' ORDER BY Ppe_Ver DESC LIMIT 1";
        $res = @$mysqli->query($sql);
        if ($res && ($row = $res->fetch_assoc())) {
            return $row;
        }
        $sql = "SELECT $cols FROM pre_presupuesto WHERE Emp_Cod=$Emp_Cod AND Ppe_Ani=$anio ORDER BY Ppe_Ver DESC LIMIT 1";
        $res = @$mysqli->query($sql);
        if ($res && ($row = $res->fetch_assoc())) {
            return $row;
        }
        return null;
    }
    $sql = "SELECT $cols FROM pre_presupuesto WHERE Emp_Cod=$Emp_Cod AND Ppe_Est='A' ORDER BY Ppe_Ani DESC, Ppe_Ver DESC LIMIT 1";
    $res = @$mysqli->query($sql);
    if ($res && ($row = $res->fetch_assoc())) {
        return $row;
    }
    $sql = "SELECT $cols FROM pre_presupuesto WHERE Emp_Cod=$Emp_Cod ORDER BY Ppe_Ani DESC, Ppe_Ver DESC LIMIT 1";
    $res = @$mysqli->query($sql);
    if ($res && ($row = $res->fetch_assoc())) {
        return $row;
    }
    return null;
}

/**
 * Resuelve Pro_Cod INT desde id numerico o Pro_Ide (ej. RCET-01).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param mixed $Pro_Cod
 * @return int
 */
function ppto_resolve_proy_id($mysqli, $Emp_Cod, $Pro_Cod) {
    $raw = trim((string)$Pro_Cod);
    if ($raw === '') {
        return 0;
    }
    if (preg_match('/^\d+$/', $raw)) {
        return (int)$raw;
    }
    $Emp_Cod = (int)$Emp_Cod;
    $esc = $mysqli->real_escape_string($raw);
    $res = $mysqli->query("SELECT Pro_Cod FROM pre_proyectos WHERE Emp_Cod=$Emp_Cod AND Pro_Ide='$esc' LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        return (int)$row['Pro_Cod'];
    }
    return 0;
}

/**
 * Resuelve Emp_Cod de un proyecto registrado.
 *
 * @param mysqli $mysqli
 * @param mixed $Pro_Cod Pro_Cod o Pro_Ide
 * @param int|null $fallback
 * @return int
 */
function ppto_resolve_emp_id_proyecto($mysqli, $Pro_Cod, $fallback = null) {
    $Emp_Cod = ($fallback !== null) ? (int)$fallback : ppto_resolve_emp_id();
    $proy_id = ppto_resolve_proy_id($mysqli, $Emp_Cod, $Pro_Cod);
    if ($proy_id > 0) {
        $res = $mysqli->query("SELECT Emp_Cod FROM pre_proyectos WHERE Pro_Cod=$proy_id LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            return (int)$row['Emp_Cod'];
        }
    }
    $esc = $mysqli->real_escape_string(trim((string)$Pro_Cod));
    $res = $mysqli->query("SELECT Emp_Cod FROM pre_proyectos WHERE Pro_Ide='$esc' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['Emp_Cod'];
    }
    return $Emp_Cod;
}

/**
 * Ejecuta un archivo SQL dividiendo por punto y coma.
 * Omite sentencias que intenten ALTER/DROP sobre vistas exa_ppto_*.
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
        if ($stmt === '') {
            continue;
        }
        // Blindaje: no ejecutar DDL contra vistas de compatibilidad
        $pref_vista = 'exa' . '_ppto_';
        if (preg_match('/\b(ALTER|DROP)\s+(TABLE|VIEW)\s+[`"\']?' . preg_quote($pref_vista, '/') . '/i', $stmt)) {
            continue;
        }
        @$mysqli->query($stmt);
    }
}

/**
 * Fija charset UTF-8 en la conexion (el default MySQL/XAMPP es latin1).
 * Sin esto, tildes del Excel (UTF-8) se guardan como Ã¡ / Ã³ / Ã©.
 *
 * @param mysqli $mysqli
 */
function ppto_db_set_utf8($mysqli) {
    if (!$mysqli || !is_object($mysqli) || !method_exists($mysqli, 'set_charset')) {
        return;
    }
    if (@$mysqli->set_charset('utf8mb4')) {
        return;
    }
    @$mysqli->set_charset('utf8');
}

/**
 * Repara texto UTF-8 de tildes guardado como Latin-1 (ViÃ¡ticos -> Viaticos con tilde).
 *
 * @param string $s
 * @return string
 */
function ppto_texto_reparar_mojibake($s) {
    $s = (string)$s;
    if ($s === '') {
        return '';
    }
    if (strpos($s, "\xC3\x83") === false && strpos($s, "\xC3\x82") === false) {
        return $s;
    }
    $fixed = false;
    if (function_exists('utf8_decode')) {
        $fixed = utf8_decode($s);
    } elseif (function_exists('mb_convert_encoding')) {
        $fixed = @mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
    }
    if ($fixed === false || $fixed === '') {
        return $s;
    }
    if (function_exists('mb_check_encoding') && !mb_check_encoding($fixed, 'UTF-8')) {
        return $s;
    }
    return $fixed;
}

/**
 * Repara rubros/partidas ya guardados con tildes corruptas (una pasada, idempotente).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_reparar_mojibake_nombres($mysqli) {
    if (!$mysqli) {
        return;
    }
    $specs = array(
        array('pre_proyecto_detalles', 'Pdp_Rubro', 'Pdp_Cod'),
        array('pre_partidas', 'Ppa_Des', 'Ppa_Cod'),
    );
    foreach ($specs as $spec) {
        $tabla = $spec[0];
        $col = $spec[1];
        $pk = $spec[2];
        if (!function_exists('ppto_schema_es_tabla_base') || !ppto_schema_es_tabla_base($mysqli, $tabla)) {
            continue;
        }
        if (!function_exists('ppto_schema_columna_existe') || !ppto_schema_columna_existe($mysqli, $tabla, $col)) {
            continue;
        }
        $sql = "SELECT `$pk` AS id, `$col` AS txt FROM `$tabla`
                WHERE `$col` LIKE CONCAT('%', CONVERT(UNHEX('C383') USING utf8), '%')
                   OR `$col` LIKE CONCAT('%', CONVERT(UNHEX('C382') USING utf8), '%')";
        $res = @$mysqli->query($sql);
        if (!$res) {
            continue;
        }
        while ($row = $res->fetch_assoc()) {
            $orig = isset($row['txt']) ? (string)$row['txt'] : '';
            $fixed = ppto_texto_reparar_mojibake($orig);
            if ($fixed === '' || $fixed === $orig) {
                continue;
            }
            $id = (int)$row['id'];
            $esc = $mysqli->real_escape_string($fixed);
            @$mysqli->query("UPDATE `$tabla` SET `$col`='$esc' WHERE `$pk`=$id LIMIT 1");
        }
    }
}

/**
 * Asegura tablas pre_* (nunca crea/altera vistas exa_ppto_*).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure($mysqli) {
    if (!$mysqli) {
        return;
    }
    ppto_db_set_utf8($mysqli);

    $sql_dir = __DIR__ . '/../SQL';
    // Canonico EXA: tablas fisicas pre_*. Las vistas exa_ppto_* son compatibilidad.
    $tablas = array(
        'pre_proyectos'             => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_bases'                 => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_prod_config'           => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_prod_periodos'         => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_reajustes'             => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_movimientos'           => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_reglas'                => 'exa_ppto_schema_completo_Emp_Cod.sql',
    );

    $ejecutados = array();
    foreach ($tablas as $tabla => $file) {
        if (!ppto_schema_es_tabla_base($mysqli, $tabla)) {
            if (!isset($ejecutados[$file])) {
                ppto_schema_ejecutar_sql($mysqli, $sql_dir . '/' . $file);
                $ejecutados[$file] = true;
            }
        }
    }

    // No borrar vistas/tablas pre_* ni vistas de compatibilidad exa_ppto_*.

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
    ppto_schema_reparar_mojibake_nombres($mysqli);
}

/**
 * Alinea FK ERP a nomenclatura EXA (Emp_Cod, Usu_Cod, Suc_Cod, Dep_Cod, Pla_Cod, Pld_Cod).
 * Idempotente: solo actua si aun existen columnas legacy *_id en tablas base.
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
    $needs = false;
    if (ppto_schema_es_tabla_base($mysqli, 'pre_proyectos')
        && ppto_schema_columna_existe($mysqli, 'pre_proyectos', 'emp_id')
    ) {
        $needs = true;
    }
    if (!$needs && ppto_schema_es_tabla_base($mysqli, 'pre_partidas')
        && ppto_schema_columna_existe($mysqli, 'pre_partidas', 'emp_id')
    ) {
        $needs = true;
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
    if (ppto_schema_es_tabla_base($mysqli, 'pre_partida_cuenta')) {
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
    if (!ppto_schema_es_tabla_base($mysqli, 'pre_proyecto_version')) {
        ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase7_proyecto_version_ton.sql');
    }
    if (ppto_schema_es_tabla_base($mysqli, 'pre_proyecto_version')
        && !ppto_schema_columna_existe($mysqli, 'pre_proyecto_version', 'Ppv_TarifaTonIva')
    ) {
        ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase10_proyecto_version_tarifa.sql');
    }
    if (ppto_schema_es_tabla_base($mysqli, 'pre_proyecto_version')
        && !ppto_schema_columna_existe($mysqli, 'pre_proyecto_version', 'Ppv_TonCostoMes')
    ) {
        @$mysqli->query("ALTER TABLE `pre_proyecto_version`
            ADD COLUMN `Ppv_TonCostoMes` DECIMAL(12,4) NOT NULL DEFAULT 77000.0000
            COMMENT 'Ton/mes costo egreso Excel (3500x22)'
            AFTER `Ppv_TonBaseMes`");
    }
}

/**
 * Tabla auditoria publicar presupuesto aprobado (fase 11).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_proyecto_publicacion($mysqli) {
    if (!ppto_schema_es_tabla_base($mysqli, 'pre_proyecto_publicacion')) {
        ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase11_proyecto_publicacion.sql');
    }
    if (ppto_schema_es_tabla_base($mysqli, 'pre_proyecto_publicacion')
        && !ppto_schema_columna_existe($mysqli, 'pre_proyecto_publicacion', 'Ppu_Mes')
    ) {
        ppto_schema_ejecutar_sql($mysqli, __DIR__ . '/../SQL/fase11_publicacion_mes.sql');
    }
}

/**
 * Tabla umbral PF vs VA (D8) — solo pre_umbral_pf (tabla base).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_fase4_d8($mysqli) {
    if (ppto_schema_es_tabla_base($mysqli, 'pre_umbral_pf')) {
        return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS `pre_umbral_pf` (
        `Ubp_Cod` BIGINT(20) NOT NULL AUTO_INCREMENT,
        `Emp_Cod` BIGINT(20) NOT NULL,
        `Ppa_Cod` BIGINT(20) NULL DEFAULT NULL,
        `Ubp_UmbralPct` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
        `Ubp_FecReg` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `Usu_Cod` BIGINT(20) NULL DEFAULT NULL,
        PRIMARY KEY (`Ubp_Cod`),
        UNIQUE KEY `uk_ubp_emp_ppa` (`Emp_Cod`, `Ppa_Cod`),
        KEY `idx_ubp_emp` (`Emp_Cod`)
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
    if (!ppto_schema_es_tabla_base($mysqli, 'pre_prod_periodos')
        || !ppto_schema_columna_existe($mysqli, 'pre_prod_periodos', 'Prd_Est')
    ) {
        $needs_fase8 = true;
    }
    if (!ppto_schema_es_tabla_base($mysqli, 'pre_prod_evento_log')) {
        $needs_fase8 = true;
    }

    if ($needs_fase8 && file_exists($fase8_file)) {
        ppto_schema_ejecutar_sql($mysqli, $fase8_file);
    }
}

/**
 * Indices de rendimiento (import por codigo, listados por proyecto+version).
 * Idempotente: ignora si el indice ya existe. Solo tablas base.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_indexes_perf($mysqli) {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $checks = array(
        array('pre_partidas', 'idx_ppa_emp_cla', 'ALTER TABLE `pre_partidas` ADD INDEX `idx_ppa_emp_cla` (`Emp_Cod`, `Ppa_Cla`)'),
        array('pre_proyecto_detalles', 'idx_pdp_emp_proy_ppe', 'ALTER TABLE `pre_proyecto_detalles` ADD INDEX `idx_pdp_emp_proy_ppe` (`Emp_Cod`, `Pro_Cod`, `Ppe_Cod`)'),
        array('pre_proyecto_version', 'idx_pv_emp_proy_ppe', 'ALTER TABLE `pre_proyecto_version` ADD INDEX `idx_pv_emp_proy_ppe` (`Emp_Cod`, `Pro_Cod`, `Ppe_Cod`)'),
    );
    foreach ($checks as $c) {
        $tabla = $c[0];
        $idx = $c[1];
        $sql = $c[2];
        if (!ppto_schema_es_tabla_base($mysqli, $tabla)) {
            continue;
        }
        $i = @$mysqli->query(
            "SHOW INDEX FROM `" . $mysqli->real_escape_string($tabla) . "`
             WHERE Key_name='" . $mysqli->real_escape_string($idx) . "'"
        );
        if ($i && $i->num_rows > 0) {
            continue;
        }
        @$mysqli->query($sql);
    }
}
