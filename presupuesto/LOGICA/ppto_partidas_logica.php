<?php
/**
 * ppto_partidas_logica.php
 * Listado centralizado y operaciones de estado para pre_partidas.
 */

require_once __DIR__ . '/ppto_schema_logica.php';

/**
 * Asegura columna Ppa_Clase en pre_partidas.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_partida_clase($mysqli) {
    if (!$mysqli) {
        return;
    }
    // Solo ALTER si es tabla base (nunca tocar vistas).
    if (!ppto_schema_es_tabla_base($mysqli, 'pre_partidas')) {
        return;
    }
    $res = @$mysqli->query("SHOW COLUMNS FROM pre_partidas LIKE 'Ppa_Clase'");
    if (!$res || $res->num_rows === 0) {
        @$mysqli->query("ALTER TABLE pre_partidas ADD COLUMN Ppa_Clase CHAR(1) NOT NULL DEFAULT 'D' COMMENT 'G=Grupo, D=Detalle' AFTER Ppa_Niv");
        @$mysqli->query("UPDATE pre_partidas p
            INNER JOIN (
                SELECT DISTINCT Ppa_Pad AS pad_id
                FROM pre_partidas
                WHERE Ppa_Pad IS NOT NULL
            ) h ON h.pad_id = p.Ppa_Cod
            SET p.Ppa_Clase = 'G'");
    }
}

/**
 * Asegura columna Ppa_Pct en partidas Grupo.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_partida_porcentaje($mysqli) {
    if (!$mysqli) {
        return;
    }
    if (!ppto_schema_es_tabla_base($mysqli, 'pre_partidas')) {
        return;
    }
    $res_pre = @$mysqli->query("SHOW COLUMNS FROM pre_partidas LIKE 'Ppa_Pct'");
    if (!$res_pre || $res_pre->num_rows === 0) {
        @$mysqli->query("ALTER TABLE pre_partidas ADD COLUMN Ppa_Pct DECIMAL(10,4) NULL DEFAULT NULL AFTER Ppa_Clase");
    } else {
        $col = $res_pre->fetch_assoc();
        if (isset($col['Type']) && preg_match('/decimal\(\d+,(\d+)\)/i', $col['Type'], $m) && (int)$m[1] < 4) {
            @$mysqli->query("ALTER TABLE pre_partidas MODIFY COLUMN Ppa_Pct DECIMAL(10,4) NULL DEFAULT NULL");
        }
    }
}

/**
 * Asegura columna Ppa_Meses en partidas Grupo.
 *
 * @param mysqli $mysqli
 */
function ppto_schema_ensure_partida_meses_prorrateo($mysqli) {
    if (!$mysqli) {
        return;
    }
    if (!ppto_schema_es_tabla_base($mysqli, 'pre_partidas')) {
        return;
    }
    $res_pre = @$mysqli->query("SHOW COLUMNS FROM pre_partidas LIKE 'Ppa_Meses'");
    if (!$res_pre || $res_pre->num_rows === 0) {
        @$mysqli->query("ALTER TABLE pre_partidas ADD COLUMN Ppa_Meses INT NULL DEFAULT NULL AFTER Ppa_Pct");
    }
}

/**
 * RecreaciÃ³n de vista desactivada (evitando vistas en BD).
 *
 * @param mysqli $mysqli
 */
function ppto_schema_recrear_vista_partidas_legacy($mysqli) {
    // No-op: pre_partidas es tabla base (no vista). No dropear nada.
    return;
}

/**
 * Meses de prorrateo configurados para un grupo (default 12).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param string $grupo_cod
 * @return int
 */
function ppto_proy_grupo_meses_prorrateo($mysqli, $Emp_Cod, $grupo_cod) {
    $Emp_Cod = (int)$Emp_Cod;
    $grupo_cod = trim($grupo_cod);
    if ($Emp_Cod <= 0 || $grupo_cod === '') {
        return 12;
    }
    $gc = $mysqli->real_escape_string($grupo_cod);
    $res = $mysqli->query("SELECT COALESCE(NULLIF(Ppa_Meses, 0), 12) AS m
        FROM pre_partidas
        WHERE Emp_Cod = $Emp_Cod
          AND Ppa_Cla = '$gc'
          AND COALESCE(NULLIF(Ppa_Clase, ''), 'D') = 'G'
        LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $m = (int)$row['m'];
        return $m > 0 ? $m : 12;
    }
    return 12;
}

/**
 * Etiqueta legible para Ppa_Clase.
 *
 * @param string $clase
 * @return string
 */
function ppto_partida_etiqueta_clase($clase) {
    return ($clase === 'G') ? 'Grupo' : 'Detalle';
}

/**
 * Listado unificado de partidas presupuestarias.
 *
 * Opciones:
 * - Emp_Cod (requerido)
 * - incluir_inactivas: incluye inactivas al final del listado
 * - solo_activas: default true salvo incluir_inactivas
 * - clase: 'G', 'D' o null (todas)
 * - nivel: int (filtra Ppa_Niv)
 *
 * @param mysqli $mysqli
 * @param array $opts
 * @return array
 */
function ppto_partidas_listar($mysqli, $opts = array()) {
    if (!$mysqli) {
        return array();
    }
    $Emp_Cod = isset($opts['Emp_Cod']) ? (int)$opts['Emp_Cod'] : 0;
    if ($Emp_Cod <= 0) {
        return array();
    }

    $incluir_inactivas = !empty($opts['incluir_inactivas']);
    $solo_activas = isset($opts['solo_activas'])
        ? (bool)$opts['solo_activas']
        : !$incluir_inactivas;

    $sql = "SELECT 
        Ppa_Cod AS Ppa_Cod, Ppa_Cod,
        Emp_Cod,
        Ppa_Cla AS Ppa_Cla, Ppa_Cla,
        Ppa_Des AS Ppa_Des, Ppa_Des,
        Ppa_Tip AS Ppa_Tip, Ppa_Tip,
        Ppa_Nat AS Ppa_Nat, Ppa_Nat,
        Ppa_Pad AS Ppa_Pad, Ppa_Pad,
        Ppa_Niv AS Ppa_Niv, Ppa_Niv,
        COALESCE(NULLIF(Ppa_Clase, ''), 'D') AS Ppa_Clase, Ppa_Clase,
        Ppa_Pct AS Ppa_Pct, Ppa_Pct,
        Ppa_Meses AS Ppa_Meses, Ppa_Meses,
        Ppa_Est AS Ppa_Est, Ppa_Est,
        Ppa_Fec AS Ppa_Fec, Ppa_Fec,
        Usu_Cod
      FROM pre_partidas WHERE Emp_Cod = $Emp_Cod";

    if (!$incluir_inactivas && $solo_activas) {
        $sql .= " AND Ppa_Est = 'A'";
    }

    if (isset($opts['clase']) && ($opts['clase'] === 'G' || $opts['clase'] === 'D')) {
        $cl = $mysqli->real_escape_string($opts['clase']);
        $sql .= " AND Ppa_Clase = '$cl'";
    }

    if (isset($opts['nivel']) && $opts['nivel'] !== '' && $opts['nivel'] !== null) {
        $sql .= ' AND Ppa_Niv = ' . (int)$opts['nivel'];
    }

    $sql .= " ORDER BY (Ppa_Est = 'A') DESC, Ppa_Cla ASC";

    $res = $mysqli->query($sql);
    $data = array();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['Ppa_Clase']) || $row['Ppa_Clase'] === '') {
                $row['Ppa_Clase'] = 'D';
            }
            $row['Ppa_Cod'] = $row['Ppa_Cod'];
            $row['ppa_codigo_clasificacion'] = $row['Ppa_Cla'];
            $row['ppa_descripcion'] = $row['Ppa_Des'];
            $row['ppa_tipo'] = $row['Ppa_Tip'];
            $row['ppa_naturaleza'] = $row['Ppa_Nat'];
            $row['ppa_padre_id'] = $row['Ppa_Pad'];
            $row['ppa_nivel'] = $row['Ppa_Niv'];
            $row['ppa_clase'] = $row['Ppa_Clase'];
            $row['ppa_porcentaje_tope'] = $row['Ppa_Pct'];
            $row['ppa_meses_prorrateo'] = $row['Ppa_Meses'];
            $row['ppa_estado'] = $row['Ppa_Est'];
            $row['ppa_fecha_registro'] = $row['Ppa_Fec'];
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Nivel jerarquico a partir del codigo (03=1, 03.01=2, 03.01.01=3).
 *
 * @param string $codigo
 * @return int
 */
function ppto_partida_nivel_desde_codigo($codigo) {
    $codigo = trim((string)$codigo);
    if ($codigo === '') {
        return 1;
    }
    $partes = array_filter(explode('.', $codigo), function ($s) {
        return $s !== '';
    });
    return max(1, count($partes));
}

/**
 * Prefijo del codigo padre (03.01.02 -> 03.01) o null si es raiz.
 *
 * @param string $codigo
 * @return string|null
 */
function ppto_partida_prefijo_padre_codigo($codigo) {
    $codigo = trim((string)$codigo);
    if ($codigo === '' || strpos($codigo, '.') === false) {
        return null;
    }
    $partes = explode('.', $codigo);
    array_pop($partes);
    return implode('.', $partes);
}

/**
 * Nivel efectivo para UI (max entre BD y segmentos del codigo).
 *
 * @param array $part
 * @return int
 */
function ppto_partida_nivel_visual($part) {
    $niv_db = isset($part['Ppa_Niv']) ? (int)$part['Ppa_Niv'] : 1;
    $cod = isset($part['Ppa_Cla']) ? $part['Ppa_Cla'] : '';
    $niv_cod = ppto_partida_nivel_desde_codigo($cod);
    return max($niv_db, $niv_cod);
}

/**
 * Sangria en px por nivel (28px por profundidad).
 *
 * @param int $nivel
 * @return int
 */
function ppto_partida_indent_px($nivel) {
    return 10 + max(0, (int)$nivel - 1) * 28;
}

/**
 * Prefijo HTML arbol (guia por cada nivel ancestro).
 *
 * @param int $nivel
 * @return string
 */
function ppto_partida_tree_prefix_html($nivel) {
    $nivel = (int)$nivel;
    if ($nivel <= 1) {
        return '';
    }
    $html = '';
    for ($d = 2; $d < $nivel; $d++) {
        $html .= '<span class="ppto-tree-gutter" aria-hidden="true"></span>';
    }
    $html .= '<span class="ppto-tree-branch" aria-hidden="true">&#9492;&#9472;</span>';
    return $html;
}

/**
 * Sugiere el siguiente codigo hermano bajo un padre (o raiz si padre_id null).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int|null $padre_id
 * @return string
 */
function ppto_partida_sugerir_codigo($mysqli, $Emp_Cod, $padre_id = null) {
    $Emp_Cod = (int)$Emp_Cod;
    $todas = ppto_partidas_listar($mysqli, array('Emp_Cod' => $Emp_Cod, 'solo_activas' => true));

    $prefijo = '';
    $nivel_esperado = 1;
    if ($padre_id !== null && (int)$padre_id > 0) {
        $padre = ppto_partida_obtener($mysqli, (int)$padre_id, $Emp_Cod);
        if (!$padre) {
            return '';
        }
        $prefijo = $padre['Ppa_Cla'] . '.';
        $nivel_esperado = (int)$padre['Ppa_Niv'] + 1;
    }

    $max_seg = 0;
    foreach ($todas as $p) {
        if ((int)$p['Ppa_Niv'] !== $nivel_esperado) {
            continue;
        }
        $cod = $p['Ppa_Cla'];
        if ($padre_id) {
            if (strpos($cod, $prefijo) !== 0) {
                continue;
            }
            $resto = substr($cod, strlen($prefijo));
            if ($resto === '' || strpos($resto, '.') !== false) {
                continue;
            }
            $seg = (int)$resto;
        } else {
            if (strpos($cod, '.') !== false) {
                continue;
            }
            $seg = (int)$cod;
        }
        if ($seg > $max_seg) {
            $max_seg = $seg;
        }
    }

    $sig = $max_seg + 1;
    $seg_str = ($sig < 10) ? ('0' . $sig) : (string)$sig;
    return $padre_id ? ($prefijo . $seg_str) : $seg_str;
}

/**
 * Obtiene una partida por ID y empresa.
 *
 * @param mysqli $mysqli
 * @param int $Ppa_Cod
 * @param int $Emp_Cod
 * @return array|null
 */
function ppto_partida_obtener($mysqli, $Ppa_Cod, $Emp_Cod) {
    $Ppa_Cod = (int)$Ppa_Cod;
    $Emp_Cod = (int)$Emp_Cod;
    if (!$mysqli || $Ppa_Cod <= 0 || $Emp_Cod <= 0) {
        return null;
    }
    $res = $mysqli->query("SELECT 
        Ppa_Cod AS Ppa_Cod, Ppa_Cod,
        Emp_Cod,
        Ppa_Cla AS Ppa_Cla, Ppa_Cla,
        Ppa_Des AS Ppa_Des, Ppa_Des,
        Ppa_Tip AS Ppa_Tip, Ppa_Tip,
        Ppa_Nat AS Ppa_Nat, Ppa_Nat,
        Ppa_Pad AS Ppa_Pad, Ppa_Pad,
        Ppa_Niv AS Ppa_Niv, Ppa_Niv,
        COALESCE(NULLIF(Ppa_Clase, ''), 'D') AS Ppa_Clase, Ppa_Clase,
        Ppa_Pct AS Ppa_Pct, Ppa_Pct,
        Ppa_Meses AS Ppa_Meses, Ppa_Meses,
        Ppa_Est AS Ppa_Est, Ppa_Est,
        Ppa_Fec AS Ppa_Fec, Ppa_Fec,
        Usu_Cod
      FROM pre_partidas WHERE Ppa_Cod = $Ppa_Cod AND Emp_Cod = $Emp_Cod LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        return $row;
    }
    return null;
}

/**
 * Cuenta reglas activas vinculadas a una partida destino.
 *
 * @param mysqli $mysqli
 * @param int $Ppa_Cod
 * @param int $Emp_Cod
 * @return int
 */
function ppto_partida_contar_reglas_activas($mysqli, $Ppa_Cod, $Emp_Cod) {
    $Ppa_Cod = (int)$Ppa_Cod;
    $Emp_Cod = (int)$Emp_Cod;
    if (!$mysqli || $Ppa_Cod <= 0) {
        return 0;
    }
    $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM pre_reglas WHERE Ppa_Cod = $Ppa_Cod AND Emp_Cod = $Emp_Cod AND Prg_Est = 'A'");
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['cnt'];
    }
    return 0;
}

/**
 * Cambia estado de partida; al anular inactiva reglas activas vinculadas.
 *
 * @param mysqli $mysqli
 * @param int $Ppa_Cod
 * @param int $Emp_Cod
 * @param string $nuevo_est A|I
 * @return array reglas_inactivadas
 */
function ppto_partida_cambiar_estado($mysqli, $Ppa_Cod, $Emp_Cod, $nuevo_est) {
    $Ppa_Cod = (int)$Ppa_Cod;
    $Emp_Cod = (int)$Emp_Cod;
    $nuevo_est = ($nuevo_est === 'A') ? 'A' : 'I';
    $reglas_inactivadas = 0;

    if (!$mysqli || $Ppa_Cod <= 0 || $Emp_Cod <= 0) {
        return array('reglas_inactivadas' => 0);
    }

    $mysqli->query("UPDATE pre_partidas SET Ppa_Est = '$nuevo_est' WHERE Ppa_Cod = $Ppa_Cod AND Emp_Cod = $Emp_Cod");

    if ($nuevo_est === 'I') {
        $mysqli->query("UPDATE pre_reglas SET Prg_Est = 'I' WHERE Ppa_Cod = $Ppa_Cod AND Emp_Cod = $Emp_Cod AND Prg_Est = 'A'");
        $reglas_inactivadas = (int)$mysqli->affected_rows;
        // Evita montos fantasma en semaforo / plan mensual.
        $mysqli->query("DELETE FROM pre_detalle WHERE Ppa_Cod = $Ppa_Cod");
    }

    return array('reglas_inactivadas' => $reglas_inactivadas);
}

/**
 * Valida que una partida pueda ser destino de regla (Detalle + Activo).
 *
 * @param mysqli $mysqli
 * @param int $Ppa_Cod
 * @param int $Emp_Cod
 * @return bool
 */
function ppto_partida_es_destino_regla($mysqli, $Ppa_Cod, $Emp_Cod) {
    $part = ppto_partida_obtener($mysqli, $Ppa_Cod, $Emp_Cod);
    return ($part && $part['Ppa_Est'] === 'A' && $part['Ppa_Clase'] === 'D');
}

/**
 * Partidas que pueden ser padre en el arbol (solo clase Grupo).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @return array
 */
function ppto_partidas_pool_padre($mysqli, $Emp_Cod) {
    $Emp_Cod = (int)$Emp_Cod;
    if (!$mysqli || $Emp_Cod <= 0) {
        return array();
    }

    $sql = "SELECT p.Ppa_Cod AS Ppa_Cod, p.Ppa_Cla AS Ppa_Cla, p.Ppa_Des AS Ppa_Des, p.Ppa_Niv AS Ppa_Niv,
                   p.Ppa_Tip AS Ppa_Tip, p.Ppa_Nat AS Ppa_Nat,
                   COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') AS Ppa_Clase,
                   CASE WHEN EXISTS (
                       SELECT 1 FROM pre_partidas h
                       WHERE h.Ppa_Pad = p.Ppa_Cod AND h.Emp_Cod = p.Emp_Cod
                   ) THEN 1 ELSE 0 END AS tiene_hijos
            FROM pre_partidas p
            WHERE p.Emp_Cod = $Emp_Cod
              AND p.Ppa_Est = 'A'
              AND COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') = 'G'
            ORDER BY p.Ppa_Niv ASC, p.Ppa_Cla ASC";

    $res = $mysqli->query($sql);
    $data = array();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Partidas con rubro en proyectos activos (misma empresa y version).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int|null $Ppe_Cod Version concreta; null = cualquier version (tab Cargar)
 * @return array Ppa_Cod => lista de rubros
 */
function ppto_partidas_map_rubro_proyecto($mysqli, $Emp_Cod, $Ppe_Cod = null) {
    $map = array();
    $Emp_Cod = (int)$Emp_Cod;
    if (!$mysqli || $Emp_Cod <= 0) {
        return $map;
    }

    $sql = "SELECT d.Ppa_Cod AS Ppa_Cod, d.Pro_Cod AS Pro_Cod, d.Pdp_Rubro AS Pdp_Rubro, pr.Pro_Nom AS Pro_Nom
            FROM pre_proyecto_detalles d
            INNER JOIN pre_proyectos pr
                ON pr.Pro_Cod = d.Pro_Cod AND pr.Emp_Cod = d.Emp_Cod
            WHERE d.Emp_Cod = $Emp_Cod
              AND pr.Pro_Est = 'A'";
    if ($Ppe_Cod !== null && (int)$Ppe_Cod > 0) {
        $sql .= ' AND d.Ppe_Cod = ' . (int)$Ppe_Cod;
    }
    $sql .= ' ORDER BY d.Pro_Cod ASC, d.Ppa_Cod ASC';

    $res = $mysqli->query($sql);
    if (!$res) {
        return $map;
    }

    while ($row = $res->fetch_assoc()) {
        $Ppa_Cod = (int)$row['Ppa_Cod'];
        if (!isset($map[$Ppa_Cod])) {
            $map[$Ppa_Cod] = array();
        }
        $map[$Ppa_Cod][] = array(
            'Pro_Cod' => $row['Pro_Cod'],
            'Pro_Nom' => $row['Pro_Nom'],
            'Pdp_Rubro' => $row['Pdp_Rubro'],
        );
    }
    return $map;
}

/**
 * Partidas detalle sin rubro en proyecto (tabs Mensual y Cargar presupuesto).
 *
 * @param array $partidas_detalle
 * @param array $map_rubro_proyecto
 * @return array
 */
function ppto_partidas_filtrar_mensual_libres($partidas_detalle, $map_rubro_proyecto) {
    if (empty($map_rubro_proyecto)) {
        return $partidas_detalle;
    }
    $out = array();
    foreach ($partidas_detalle as $part) {
        if (!isset($map_rubro_proyecto[(int)$part['Ppa_Cod']])) {
            $out[] = $part;
        }
    }
    return $out;
}

/**
 * Obtiene partida por codigo de clasificacion.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param string $codigo
 * @return array|null
 */
function ppto_partida_obtener_por_codigo($mysqli, $Emp_Cod, $codigo) {
    $Emp_Cod = (int)$Emp_Cod;
    $codigo = trim($codigo);
    if (!$mysqli || $Emp_Cod <= 0 || $codigo === '') {
        return null;
    }
    $esc = $mysqli->real_escape_string($codigo);
    $res = $mysqli->query("SELECT 
        Ppa_Cod AS Ppa_Cod, Ppa_Cod,
        Emp_Cod,
        Ppa_Cla AS Ppa_Cla, Ppa_Cla,
        Ppa_Des AS Ppa_Des, Ppa_Des,
        Ppa_Tip AS Ppa_Tip, Ppa_Tip,
        Ppa_Nat AS Ppa_Nat, Ppa_Nat,
        Ppa_Pad AS Ppa_Pad, Ppa_Pad,
        Ppa_Niv AS Ppa_Niv, Ppa_Niv,
        COALESCE(NULLIF(Ppa_Clase, ''), 'D') AS Ppa_Clase, Ppa_Clase,
        Ppa_Pct AS Ppa_Pct, Ppa_Pct,
        Ppa_Meses AS Ppa_Meses, Ppa_Meses,
        Ppa_Est AS Ppa_Est, Ppa_Est,
        Ppa_Fec AS Ppa_Fec, Ppa_Fec,
        Usu_Cod
      FROM pre_partidas WHERE Emp_Cod=$Emp_Cod AND Ppa_Cla='$esc' LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        return $row;
    }
    return null;
}

/**
 * Crea o actualiza una partida del catalogo (uso AJAX desde proyectos).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $Usu_Cod
 * @param array $data
 * @return array
 */
function ppto_partida_guardar($mysqli, $Emp_Cod, $Usu_Cod, $data) {
    ppto_schema_ensure_partida_clase($mysqli);
    $Emp_Cod = (int)$Emp_Cod;
    $Usu_Cod = (int)$Usu_Cod;

    $codigo = isset($data['Ppa_Cla']) ? trim($data['Ppa_Cla']) : '';
    $descripcion = isset($data['Ppa_Des']) ? trim($data['Ppa_Des']) : '';
    $tipo = isset($data['Ppa_Tip']) ? trim($data['Ppa_Tip']) : 'G';
    $naturaleza = isset($data['Ppa_Nat']) ? trim($data['Ppa_Nat']) : 'OPE';
    $clase = (isset($data['Ppa_Clase']) && $data['Ppa_Clase'] === 'G') ? 'G' : 'D';
    $padre_id = isset($data['Ppa_Pad']) && (int)$data['Ppa_Pad'] > 0 ? (int)$data['Ppa_Pad'] : 0;

    if ($codigo === '' || $descripcion === '') {
        return array('ok' => false, 'message' => 'Codigo y descripcion son requeridos.');
    }
    if (!preg_match('/^[0-9]+(\.[0-9]+)*$/', $codigo)) {
        return array('ok' => false, 'message' => 'Codigo invalido. Use formato 05 o 05.01.01');
    }

    $nivel = (int)ppto_partida_nivel_desde_codigo($codigo);
    if ($nivel <= 0) {
        return array('ok' => false, 'message' => 'No se pudo determinar el nivel del codigo.');
    }

    $exist = ppto_partida_obtener_por_codigo($mysqli, $Emp_Cod, $codigo);
    if ($exist) {
        return array('ok' => false, 'message' => 'Ya existe la partida ' . $codigo . '.');
    }

    if ($nivel === 1) {
        $padre_id = 0;
        if ($clase !== 'G') {
            return array('ok' => false, 'message' => 'El capitulo principal debe ser clase Grupo.');
        }
    } else {
        if ($padre_id <= 0) {
            $padre_cod = ppto_partida_prefijo_padre_codigo($codigo);
            $padre_row = ppto_partida_obtener_por_codigo($mysqli, $Emp_Cod, $padre_cod);
            if (!$padre_row) {
                return array('ok' => false, 'message' => 'No existe la partida padre ' . $padre_cod . '. Creela primero.');
            }
            $padre_id = (int)$padre_row['Ppa_Cod'];
        }
        $padre = ppto_partida_obtener($mysqli, $padre_id, $Emp_Cod);
        if (!$padre || $padre['Ppa_Est'] !== 'A' || $padre['Ppa_Clase'] !== 'G') {
            return array('ok' => false, 'message' => 'La partida padre debe ser un Grupo activo.');
        }
        $prefijo_padre = $padre['Ppa_Cla'] . '.';
        if (strpos($codigo, $prefijo_padre) !== 0) {
            return array('ok' => false, 'message' => 'El codigo debe colgar bajo ' . $padre['Ppa_Cla'] . '.');
        }
        if ($clase === 'G' && $nivel >= 3) {
            return array('ok' => false, 'message' => 'Los subgrupos intermedios usan clase Grupo en nivel 2.');
        }
    }

    $cod_esc = $mysqli->real_escape_string($codigo);
    $des_esc = $mysqli->real_escape_string($descripcion);
    $tip_esc = $mysqli->real_escape_string($tipo);
    $nat_esc = $mysqli->real_escape_string($naturaleza);
    $cla_esc = $mysqli->real_escape_string($clase);
    $pad_sql = $padre_id > 0 ? (string)$padre_id : 'NULL';

    $sql = "INSERT INTO pre_partidas
        (Emp_Cod, Ppa_Cla, Ppa_Des, Ppa_Tip, Ppa_Nat, Ppa_Pad, Ppa_Niv, Ppa_Clase, Ppa_Est, Ppa_Fec, Usu_Cod)
        VALUES ($Emp_Cod, '$cod_esc', '$des_esc', '$tip_esc', '$nat_esc', $pad_sql, $nivel, '$cla_esc', 'A', CURDATE(), $Usu_Cod)";
    if (!$mysqli->query($sql)) {
        return array('ok' => false, 'message' => $mysqli->error);
    }

    $Ppa_Cod = (int)$mysqli->insert_id;
    if ($Ppa_Cod <= 0) {
        $row = ppto_partida_obtener_por_codigo($mysqli, $Emp_Cod, $codigo);
        $Ppa_Cod = $row ? (int)$row['Ppa_Cod'] : 0;
    }

    return array(
        'ok' => true,
        'message' => 'Partida creada.',
        'Ppa_Cod' => $Ppa_Cod,
        'Ppa_Cla' => $codigo,
        'Ppa_Des' => $descripcion,
        'Ppa_Niv' => $nivel,
        'Ppa_Clase' => $clase,
    );
}

/**
 * Grupo ancestro con % tope mas especifico para una partida detalle.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param string $codigo_detalle
 * @return array|null
 */
function ppto_partida_grupo_controlador($mysqli, $Emp_Cod, $codigo_detalle) {
    $codigo_detalle = trim($codigo_detalle);
    if (!$mysqli || $Emp_Cod <= 0 || $codigo_detalle === '') {
        return null;
    }
    $parts = explode('.', $codigo_detalle);
    $n = count($parts);
    if ($n < 2) {
        return null;
    }
    for ($l = $n - 1; $l >= 1; $l--) {
        $anc = implode('.', array_slice($parts, 0, $l));
        $row = ppto_partida_obtener_por_codigo($mysqli, $Emp_Cod, $anc);
        if (!$row || $row['Ppa_Est'] !== 'A') {
            continue;
        }
        $clase = isset($row['Ppa_Clase']) && $row['Ppa_Clase'] !== '' ? $row['Ppa_Clase'] : 'D';
        if ($clase !== 'G') {
            continue;
        }
        $pct = isset($row['Ppa_Pct']) ? (float)$row['Ppa_Pct'] : 0.0;
        if ($pct > 0.0001) {
            return $row;
        }
    }
    return null;
}

/**
 * Suma presupuesto anual de rubros bajo un codigo grupo (prefijo).
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param string $grupo_cod
 * @param int $excluir_pdp_id
 * @return float
 */
function ppto_proy_sumar_rubros_prefijo($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $grupo_cod, $excluir_pdp_id = 0) {
    $proy_esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $grupo_esc = $mysqli->real_escape_string(trim($grupo_cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $excluir_pdp_id = (int)$excluir_pdp_id;
    if ($proy_esc === '' || $grupo_esc === '' || $Ppe_Cod <= 0) {
        return 0.0;
    }
    $excl = $excluir_pdp_id > 0 ? " AND d.Pdp_Cod <> $excluir_pdp_id" : '';
    $sql = "SELECT COALESCE(SUM(d.Pdp_PreAnual), 0) AS total
        FROM pre_proyecto_detalles d
        INNER JOIN pre_partidas p ON d.Ppa_Cod = p.Ppa_Cod
        WHERE d.Pro_Cod='$proy_esc' AND d.Emp_Cod=$Emp_Cod AND d.Ppe_Cod=$Ppe_Cod
          AND (p.Ppa_Cla = '$grupo_esc'
               OR p.Ppa_Cla LIKE '$grupo_esc.%')$excl";
    $res = $mysqli->query($sql);
    if ($res && ($row = $res->fetch_assoc())) {
        return (float)$row['total'];
    }
    return 0.0;
}

/**
 * Total presupuesto anual del proyecto/version.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param int $excluir_pdp_id
 * @return float
 */
function ppto_proy_total_presupuesto_anual($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $excluir_pdp_id = 0) {
    $proy_esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $excluir_pdp_id = (int)$excluir_pdp_id;
    if ($proy_esc === '' || $Ppe_Cod <= 0) {
        return 0.0;
    }
    $excl = $excluir_pdp_id > 0 ? " AND Pdp_Cod <> $excluir_pdp_id" : '';
    $res = $mysqli->query("SELECT COALESCE(SUM(Pdp_PreAnual), 0) AS total
        FROM pre_proyecto_detalles
        WHERE Pro_Cod='$proy_esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod$excl");
    if ($res && ($row = $res->fetch_assoc())) {
        return (float)$row['total'];
    }
    return 0.0;
}

/**
 * Valida que un rubro no supere el % tope de su grupo controlador.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param string $ppa_codigo
 * @param float $nuevo_anual
 * @param int $excluir_pdp_id
 * @return array ok, message, detalle
 */
function ppto_proy_validar_tope_grupo_rubro($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $ppa_codigo, $nuevo_anual, $excluir_pdp_id = 0) {
    require_once __DIR__ . '/ppto_proyecto_version_logica.php';
    $grupo = ppto_partida_grupo_controlador($mysqli, $Emp_Cod, $ppa_codigo);
    if (!$grupo) {
        return array('ok' => true);
    }
    $pct = (float)$grupo['Ppa_Pct'];
    $grupo_cod = $grupo['Ppa_Cla'];
    $sum_grupo = ppto_proy_sumar_rubros_prefijo($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $grupo_cod, $excluir_pdp_id);
    $nuevo_grupo = $sum_grupo + (float)$nuevo_anual;
    $cfg = ppto_proy_version_config($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    $tope = ppto_proy_grupo_tope_anual($pct, $cfg);
    if ($tope <= 0.0001) {
        return array('ok' => true);
    }
    if ($nuevo_grupo > $tope + 0.02) {
        $usado_pct = round(($nuevo_grupo / $tope) * 100, 2);
        return array(
            'ok' => false,
            'message' => 'El grupo ' . $grupo_cod . ' (' . $grupo['Ppa_Des'] . ') excede el tope: '
                . ppto_proy_grupo_tope_formula_txt($pct, $cfg)
                . '. Presupuesto del grupo: $' . number_format($nuevo_grupo, 2, '.', ',')
                . ' (' . number_format($usado_pct, 2, '.', ',') . '% del tope).',
            'grupo_cod' => $grupo_cod,
            'tope_pct' => $pct,
            'tope_anual' => $tope,
            'usado_anual' => $nuevo_grupo,
            'usado_pct' => $usado_pct,
            'formula' => ppto_proy_grupo_tope_formula_txt($pct, $cfg),
        );
    }
    return array(
        'ok' => true,
        'grupo_cod' => $grupo_cod,
        'tope_pct' => $pct,
        'tope_anual' => $tope,
        'usado_anual' => $nuevo_grupo,
        'usado_pct' => round(($nuevo_grupo / $tope) * 100, 2),
    );
}

/**
 * Resumen de topes por grupo para el cuadro presupuestario.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param string $Pro_Cod
 * @param int $Ppe_Cod
 * @param array $rows rubros list_rubros
 * @return array keyed by grupo_cod
 */
function ppto_proy_grupos_resumen_tope($mysqli, $Emp_Cod, $Pro_Cod, $Ppe_Cod, $rows) {
    require_once __DIR__ . '/ppto_proyecto_version_logica.php';
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $cfg = ppto_proy_version_config($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    $out = array();
    $res = $mysqli->query("SELECT Ppa_Cod AS Ppa_Cod, Ppa_Cla AS Ppa_Cla, Ppa_Des AS Ppa_Des,
            COALESCE(Ppa_Pct, 0) AS Ppa_Pct,
            COALESCE(NULLIF(Ppa_Meses, 0), 12) AS Ppa_Meses
        FROM pre_partidas
        WHERE Emp_Cod = $Emp_Cod AND Ppa_Est = 'A' AND COALESCE(NULLIF(Ppa_Clase, ''), 'D') = 'G'");
    if (!$res) {
        return $out;
    }
    while ($g = $res->fetch_assoc()) {
        $cod = $g['Ppa_Cla'];
        $pct = (float)$g['Ppa_Pct'];
        $sum = ppto_proy_sumar_rubros_prefijo($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $cod);
        $tope = ppto_proy_grupo_tope_anual($pct, $cfg);
        $usado_pct = ($tope > 0) ? round(($sum / $tope) * 100, 2) : 0.0;
        $meses = (int)$g['Ppa_Meses'];
        if ($meses <= 0) {
            $meses = 12;
        }
        $out[$cod] = array(
            'Ppa_Cod' => (int)$g['Ppa_Cod'],
            'codigo' => $cod,
            'descripcion' => $g['Ppa_Des'],
            'tope_pct' => $pct,
            'meses_prorrateo' => $meses,
            'tope_anual' => $tope,
            'usado_anual' => round($sum, 2),
            'usado_pct' => $usado_pct,
            'excedido' => ($tope > 0 && $sum > $tope + 0.02),
            'formula' => ($pct > 0 && $tope > 0) ? ppto_proy_grupo_tope_formula_txt($pct, $cfg) : '',
        );
    }
    return $out;
}
