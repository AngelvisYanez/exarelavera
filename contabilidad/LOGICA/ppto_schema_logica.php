<?php
/**
 * ppto_schema_logica.php
 * Migraciones y auto-healing del esquema EXA PPTO (compatible MariaDB).
 */

/**
 * Resuelve Emp_Cod desde filtro UI, POST o sesión.
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
    $res = $mysqli->query("SELECT Emp_Cod FROM pre_proyectos WHERE Pro_Cod = '$clean' OR Pro_Ide = '$clean' LIMIT 1");
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
        'pre_proyectos'        => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_bases'            => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_prod_config'      => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_prod_periodos'    => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_reajustes'        => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_movimientos'      => 'exa_ppto_schema_completo_Emp_Cod.sql',
        'pre_reglas'           => 'exa_ppto_schema_completo_Emp_Cod.sql',
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
}

/**
 * Fase 8: FK Emp_Cod en pre_proyectos y pre_partidas.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_fase8($mysqli) {
    $res1 = @$mysqli->query("SHOW COLUMNS FROM pre_proyectos LIKE 'emp_id'");
    if ($res1 && $res1->num_rows > 0) {
        @$mysqli->query("ALTER TABLE pre_proyectos CHANGE COLUMN emp_id Emp_Cod INT NOT NULL");
    }

    $res2 = @$mysqli->query("SHOW COLUMNS FROM pre_partidas LIKE 'emp_id'");
    if ($res2 && $res2->num_rows > 0) {
        @$mysqli->query("ALTER TABLE pre_partidas CHANGE COLUMN emp_id Emp_Cod INT NOT NULL");
    }
}

/**
 * Fase 4 / D8: ensure pre_partida_cuenta.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_partida_cuenta($mysqli) {
    $res = @$mysqli->query("SHOW TABLES LIKE 'pre_partida_cuenta'");
    if (!$res || $res->num_rows === 0) {
        $sql = "CREATE TABLE IF NOT EXISTS `pre_partida_cuenta` (
          `Ppc_Cod` INT AUTO_INCREMENT PRIMARY KEY,
          `Emp_Cod` INT NOT NULL,
          `Ppa_Cod` INT NOT NULL,
          `Pld_Cod` BIGINT NOT NULL,
          `Ppc_Est` CHAR(1) NOT NULL DEFAULT 'A',
          `Ppc_FecReg` DATETIME NOT NULL,
          `Usu_Cod` INT NOT NULL,
          UNIQUE KEY `idx_ppc_emp_ppa` (`Emp_Cod`, `Ppa_Cod`),
          INDEX `idx_ppc_pld` (`Pld_Cod`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Relacion 1:1 entre partida de presupuesto y cuenta contable del plan'";
        @$mysqli->query($sql);
    }
}

/**
 * Fase 4 / D8: ensure columnas prd_estado, prd_fecha_cierre, prd_cerrado_usu_id en pre_prod_periodos.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_fase4_d8($mysqli) {
    $res = @$mysqli->query("SHOW TABLES LIKE 'pre_prod_periodos'");
    if (!$res || $res->num_rows === 0) {
        return;
    }
    $c1 = @$mysqli->query("SHOW COLUMNS FROM pre_prod_periodos LIKE 'prd_estado'");
    if (!$c1 || $c1->num_rows === 0) {
        @$mysqli->query("ALTER TABLE pre_prod_periodos ADD COLUMN prd_estado VARCHAR(20) NOT NULL DEFAULT 'abierto'");
    }
    $c2 = @$mysqli->query("SHOW COLUMNS FROM pre_prod_periodos LIKE 'prd_fecha_cierre'");
    if (!$c2 || $c2->num_rows === 0) {
        @$mysqli->query("ALTER TABLE pre_prod_periodos ADD COLUMN prd_fecha_cierre DATETIME NULL");
    }
    $c3 = @$mysqli->query("SHOW COLUMNS FROM pre_prod_periodos LIKE 'prd_cerrado_usu_id'");
    if (!$c3 || $c3->num_rows === 0) {
        @$mysqli->query("ALTER TABLE pre_prod_periodos ADD COLUMN prd_cerrado_usu_id INT NULL");
    }
    $c4 = @$mysqli->query("SHOW COLUMNS FROM pre_prod_periodos LIKE 'prd_fecha_reapertura'");
    if (!$c4 || $c4->num_rows === 0) {
        @$mysqli->query("ALTER TABLE pre_prod_periodos ADD COLUMN prd_fecha_reapertura DATETIME NULL");
    }
    $c5 = @$mysqli->query("SHOW COLUMNS FROM pre_prod_periodos LIKE 'prd_reabierto_usu_id'");
    if (!$c5 || $c5->num_rows === 0) {
        @$mysqli->query("ALTER TABLE pre_prod_periodos ADD COLUMN prd_reabierto_usu_id INT NULL");
    }
}

/**
 * D1: ensure pre_proyecto_version.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_proyecto_version($mysqli) {
    $res = @$mysqli->query("SHOW TABLES LIKE 'pre_proyecto_version'");
    if (!$res || $res->num_rows === 0) {
        $sql = "CREATE TABLE IF NOT EXISTS `pre_proyecto_version` (
          `Ppv_Cod` INT AUTO_INCREMENT PRIMARY KEY,
          `Pro_Cod` VARCHAR(50) NOT NULL,
          `Emp_Cod` INT NOT NULL,
          `Ppv_VerNom` VARCHAR(100) NOT NULL,
          `Ppv_Est` VARCHAR(20) NOT NULL DEFAULT 'borrador',
          `Ppv_Ani` INT NOT NULL,
          `Ppv_MesIni` INT NOT NULL DEFAULT 1,
          `Ppv_MesFin` INT NOT NULL DEFAULT 12,
          `Ppv_TonAnual` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          `Ppv_TarifaTonIva` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          `Ppv_TonCostoMes` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          `Ppv_FecReg` DATETIME NOT NULL,
          `Usu_Cod` INT NOT NULL DEFAULT 0,
          INDEX `idx_pv_proy_emp` (`Pro_Cod`, `Emp_Cod`, `Ppv_Ani`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Versiones de presupuesto de proyecto (Base A o simulaciones)'";
        @$mysqli->query($sql);
    } else {
        $c1 = @$mysqli->query("SHOW COLUMNS FROM pre_proyecto_version LIKE 'Ppv_TarifaTonIva'");
        if (!$c1 || $c1->num_rows === 0) {
            @$mysqli->query("ALTER TABLE pre_proyecto_version ADD COLUMN Ppv_TarifaTonIva DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER Ppv_TonAnual");
        }
        $c2 = @$mysqli->query("SHOW COLUMNS FROM pre_proyecto_version LIKE 'Ppv_TonCostoMes'");
        if (!$c2 || $c2->num_rows === 0) {
            @$mysqli->query("ALTER TABLE pre_proyecto_version ADD COLUMN Ppv_TonCostoMes DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER Ppv_TarifaTonIva");
        }
    }
}

/**
 * D6: ensure pre_proyecto_publicacion.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_proyecto_publicacion($mysqli) {
    $res = @$mysqli->query("SHOW TABLES LIKE 'pre_proyecto_publicacion'");
    if (!$res || $res->num_rows === 0) {
        $sql = "CREATE TABLE IF NOT EXISTS `pre_proyecto_publicacion` (
          `Ppu_Cod` INT AUTO_INCREMENT PRIMARY KEY,
          `Pro_Cod` VARCHAR(50) NOT NULL,
          `Emp_Cod` INT NOT NULL,
          `Ppe_Cod` INT NOT NULL,
          `Ppv_Cod` INT NULL,
          `Ppu_Ani` INT NOT NULL,
          `Ppu_Mes` INT NOT NULL,
          `Ppu_Modo` VARCHAR(20) NOT NULL DEFAULT 'publicado',
          `Ppu_FecReg` DATETIME NOT NULL,
          `Usu_Cod` INT NOT NULL,
          UNIQUE KEY `idx_pub_proy_mes` (`Pro_Cod`, `Emp_Cod`, `Ppe_Cod`, `Ppu_Ani`, `Ppu_Mes`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Publicacion oficial del presupuesto de proyecto hacia el plano general de la empresa'";
        @$mysqli->query($sql);
    } else {
        $c1 = @$mysqli->query("SHOW COLUMNS FROM pre_proyecto_publicacion LIKE 'Ppu_Mes'");
        if (!$c1 || $c1->num_rows === 0) {
            @$mysqli->query("ALTER TABLE pre_proyecto_publicacion ADD COLUMN Ppu_Mes INT NOT NULL DEFAULT 1 AFTER Ppu_Ani");
            @$mysqli->query("ALTER TABLE pre_proyecto_publicacion DROP INDEX idx_pub_proy_mes");
            @$mysqli->query("ALTER TABLE pre_proyecto_publicacion ADD UNIQUE KEY idx_pub_proy_mes (Pro_Cod, Emp_Cod, Ppe_Cod, Ppu_Ani, Ppu_Mes)");
        }
    }
}

/**
 * D7: ensure pre_umbral_pf.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_umbral_pf($mysqli) {
    $res = @$mysqli->query("SHOW TABLES LIKE 'pre_umbral_pf'");
    if (!$res || $res->num_rows === 0) {
        $sql = "CREATE TABLE IF NOT EXISTS `pre_umbral_pf` (
          `Pum_Cod` INT AUTO_INCREMENT PRIMARY KEY,
          `Emp_Cod` INT NOT NULL,
          `Pum_Modulo` VARCHAR(50) NOT NULL DEFAULT 'general',
          `Pum_PorcAmarillo` DECIMAL(5,2) NOT NULL DEFAULT 80.00,
          `Pum_PorcRojo` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
          `Pum_FecReg` DATETIME NOT NULL,
          `Usu_Cod` INT NULL,
          UNIQUE KEY `idx_umb_emp_mod` (`Emp_Cod`, `Pum_Modulo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Umbrales globales de semaforo de desviacion Presupuesto vs Real (PF)'";
        @$mysqli->query($sql);
    }
}
