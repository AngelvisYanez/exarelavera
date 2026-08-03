<?php
/**
 * ppto_param_contable_logica.php
 * Parametrizacion Contable: puente partida D <-> cuenta contable D.
 * Simplicidad: una tabla, validaciones en PHP, sin triggers.
 */

require_once __DIR__ . '/ppto_schema_logica.php';
require_once __DIR__ . '/ppto_partidas_logica.php';

/**
 * Asegura schema puente.
 *
 * @param mysqli $mysqli
 */
function ppto_param_contable_boot($mysqli) {
    ppto_schema_ensure_partida_cuenta($mysqli);
}

/**
 * Plan de cuentas activo de la empresa (Pla_Cod).
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $anio preferido (via perio_cont)
 * @return array {pla_cod, pec_cod, anio}|null
 */
function ppto_param_contable_plan_empresa($mysqli, $emp_id, $anio = 0) {
    $emp_id = (int)$emp_id;
    $anio = (int)$anio;
    if ($emp_id <= 0) {
        return null;
    }

    if ($anio > 0) {
        $sql = "SELECT pc.Pla_Cod AS pla_cod, pe.Pec_Cod AS pec_cod, YEAR(pe.Pec_Fei) AS anio
            FROM plan_cuenta pc
            INNER JOIN perio_cont pe ON pe.Pla_Cod = pc.Pla_Cod
            WHERE pc.Emp_Cod = $emp_id AND pc.Pla_Est = 'A'
              AND YEAR(pe.Pec_Fei) = $anio
            ORDER BY pe.Pec_Fei DESC
            LIMIT 1";
        $res = $mysqli->query($sql);
        if ($res && ($row = $res->fetch_assoc())) {
            return array(
                'pla_cod' => (int)$row['pla_cod'],
                'pec_cod' => (int)$row['pec_cod'],
                'anio' => (int)$row['anio'],
            );
        }
    }

    $sql = "SELECT pc.Pla_Cod AS pla_cod, pe.Pec_Cod AS pec_cod, YEAR(pe.Pec_Fei) AS anio
        FROM plan_cuenta pc
        LEFT JOIN perio_cont pe ON pe.Pla_Cod = pc.Pla_Cod
        WHERE pc.Emp_Cod = $emp_id AND pc.Pla_Est = 'A'
        ORDER BY pe.Pec_Fei DESC, pc.Pla_Cod DESC
        LIMIT 1";
    $res = $mysqli->query($sql);
    if ($res && ($row = $res->fetch_assoc())) {
        return array(
            'pla_cod' => (int)$row['pla_cod'],
            'pec_cod' => isset($row['pec_cod']) ? (int)$row['pec_cod'] : 0,
            'anio' => isset($row['anio']) ? (int)$row['anio'] : 0,
        );
    }
    return null;
}

/**
 * Naturaleza contable desde campos ERP (Pld_Deb / Pld_Cre) cuando existan.
 *
 * @param array $cta
 * @return string
 */
function ppto_param_contable_naturaleza_cuenta($cta) {
    $deb = isset($cta['Pld_Deb']) ? trim((string)$cta['Pld_Deb']) : '';
    $cre = isset($cta['Pld_Cre']) ? trim((string)$cta['Pld_Cre']) : '';
    if ($deb !== '' && $cre === '') {
        return 'Deudora';
    }
    if ($cre !== '' && $deb === '') {
        return 'Acreedora';
    }
    if ($deb !== '' && $cre !== '') {
        return 'Deudora/Acreedora';
    }
    return 'N/D';
}

/**
 * Grupos raiz configurados en Contabilidad > Configurar Balances.
 * Preferencia: "Balance de sumas y saldos"; si no hay, union de todos los balances del plan.
 *
 * @param mysqli $mysqli
 * @param int $pla_cod
 * @return array [{pld_cod, codigo, descripcion, est_cod, est_des}]
 */
function ppto_param_contable_grupos_balance($mysqli, $pla_cod) {
    $pla_cod = (int)$pla_cod;
    $out = array();
    if ($pla_cod <= 0) {
        return $out;
    }

    $est_cod = 0;
    $res = @$mysqli->query("SELECT Est_Cod, Est_Des FROM estado_fin
        WHERE Est_Des LIKE '%sumas%saldo%' OR Est_Des LIKE '%Sumas%Saldos%'
        ORDER BY Est_Cod ASC LIMIT 1");
    if ($res && ($r = $res->fetch_assoc())) {
        $est_cod = (int)$r['Est_Cod'];
    }
    if ($est_cod <= 0) {
        $res = @$mysqli->query("SELECT Est_Cod FROM estado_fin ORDER BY Est_Cod DESC LIMIT 1");
        if ($res && ($r = $res->fetch_assoc())) {
            $est_cod = (int)$r['Est_Cod'];
        }
    }

    $sql = "SELECT d.Pld_Cod, d.Pld_Cdc, d.Pld_Des, ef.Est_Cod, ef.Est_Des
        FROM det_estado de
        INNER JOIN det_plan d ON d.Pld_Cod = de.Pld_Cod
        INNER JOIN estado_fin ef ON ef.Est_Cod = de.Est_Cod
        WHERE d.Pla_Cod = $pla_cod AND d.Pld_Est = 'A'";
    if ($est_cod > 0) {
        $sql .= " AND de.Est_Cod = $est_cod";
    }
    $sql .= " ORDER BY d.Pld_Cdc ASC";

    $seen = array();
    $res = @$mysqli->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $pld = (int)$r['Pld_Cod'];
            if (isset($seen[$pld])) {
                continue;
            }
            $seen[$pld] = true;
            $out[] = array(
                'pld_cod' => $pld,
                'codigo' => $r['Pld_Cdc'],
                'descripcion' => $r['Pld_Des'],
                'est_cod' => (int)$r['Est_Cod'],
                'est_des' => $r['Est_Des'],
            );
        }
    }
    return $out;
}

/**
 * Resuelve el grupo raiz de balance (configurado) al que pertenece un codigo de cuenta.
 *
 * @param string $pld_cdc
 * @param array $grupos salida de ppto_param_contable_grupos_balance
 * @return array|null
 */
function ppto_param_contable_resolver_grupo_balance($pld_cdc, $grupos) {
    $pld_cdc = trim((string)$pld_cdc);
    if ($pld_cdc === '' || empty($grupos)) {
        return null;
    }
    // Preferir el grupo de codigo mas especifico / mas largo que sea prefijo.
    $best = null;
    $best_len = -1;
    foreach ($grupos as $g) {
        $gc = isset($g['codigo']) ? (string)$g['codigo'] : '';
        if ($gc === '') {
            continue;
        }
        if ($pld_cdc === $gc || strpos($pld_cdc, $gc . '.') === 0) {
            $len = strlen($gc);
            if ($len > $best_len) {
                $best = $g;
                $best_len = $len;
            }
        }
    }
    return $best;
}

/**
 * @deprecated Usar grupos de Configurar Balances. Se mantiene solo como fallback.
 * @param string $pld_cdc
 * @return string
 */
function ppto_param_contable_clasif_codigo($pld_cdc) {
    $d = substr(preg_replace('/[^0-9]/', '', (string)$pld_cdc), 0, 1);
    $map = array(
        '1' => 'activo',
        '2' => 'pasivo',
        '3' => 'patrimonio',
        '4' => 'ingresos',
        '5' => 'gastos',
        '6' => 'costos',
    );
    return isset($map[$d]) ? $map[$d] : 'otros';
}

/**
 * KPIs del centro de parametrizacion.
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $pla_cod
 * @return array
 */
function ppto_param_contable_kpis($mysqli, $emp_id, $pla_cod) {
    $emp_id = (int)$emp_id;
    $pla_cod = (int)$pla_cod;
    $out = array(
        'rubros_detalle' => 0,
        'rubros_parametrizados' => 0,
        'rubros_pendientes' => 0,
        'cuentas_asignadas' => 0,
        'cuentas_sin_rubro' => 0,
        'pct_parametrizacion' => 0.0,
    );

    $res = $mysqli->query("SELECT COUNT(*) AS n FROM exa_ppto_partidas
        WHERE emp_id=$emp_id AND ppa_estado='A'
          AND COALESCE(NULLIF(ppa_clase,''),'D')='D'");
    if ($res && ($r = $res->fetch_assoc())) {
        $out['rubros_detalle'] = (int)$r['n'];
    }

    $res = $mysqli->query("SELECT COUNT(DISTINCT ppc.ppa_id) AS n
        FROM exa_ppto_partida_cuenta ppc
        INNER JOIN exa_ppto_partidas p ON p.ppa_id=ppc.ppa_id AND p.emp_id=ppc.emp_id
        WHERE ppc.emp_id=$emp_id AND ppc.pla_cod=$pla_cod AND ppc.ppc_estado='A'
          AND p.ppa_estado='A' AND COALESCE(NULLIF(p.ppa_clase,''),'D')='D'");
    if ($res && ($r = $res->fetch_assoc())) {
        $out['rubros_parametrizados'] = (int)$r['n'];
    }
    $out['rubros_pendientes'] = max(0, $out['rubros_detalle'] - $out['rubros_parametrizados']);

    $res = $mysqli->query("SELECT COUNT(*) AS n FROM exa_ppto_partida_cuenta
        WHERE emp_id=$emp_id AND pla_cod=$pla_cod AND ppc_estado='A'");
    if ($res && ($r = $res->fetch_assoc())) {
        $out['cuentas_asignadas'] = (int)$r['n'];
    }

    $res = $mysqli->query("SELECT COUNT(*) AS n FROM det_plan d
        WHERE d.Pla_Cod=$pla_cod AND d.Pld_Tip='D' AND d.Pld_Est='A'
          AND NOT EXISTS (
            SELECT 1 FROM exa_ppto_partida_cuenta ppc
            WHERE ppc.pld_cod=d.Pld_Cod AND ppc.emp_id=$emp_id
              AND ppc.pla_cod=$pla_cod AND ppc.ppc_estado='A'
          )");
    if ($res && ($r = $res->fetch_assoc())) {
        $out['cuentas_sin_rubro'] = (int)$r['n'];
    }

    if ($out['rubros_detalle'] > 0) {
        $out['pct_parametrizacion'] = round(($out['rubros_parametrizados'] / $out['rubros_detalle']) * 100, 1);
    }
    return $out;
}

/**
 * Contadores de cuentas por ppa_id.
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $pla_cod
 * @return array ppa_id => count
 */
function ppto_param_contable_conteo_por_rubro($mysqli, $emp_id, $pla_cod) {
    $map = array();
    $emp_id = (int)$emp_id;
    $pla_cod = (int)$pla_cod;
    $res = $mysqli->query("SELECT ppa_id, COUNT(*) AS n FROM exa_ppto_partida_cuenta
        WHERE emp_id=$emp_id AND pla_cod=$pla_cod AND ppc_estado='A'
        GROUP BY ppa_id");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $map[(int)$r['ppa_id']] = (int)$r['n'];
        }
    }
    return $map;
}

/**
 * Arbol de partidas para el panel izquierdo (reutiliza listado + indent helpers).
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $pla_cod
 * @param string $filtro todos|parametrizados|pendientes
 * @return array
 */
function ppto_param_contable_arbol_partidas($mysqli, $emp_id, $pla_cod, $filtro = 'todos') {
    $emp_id = (int)$emp_id;
    $pla_cod = (int)$pla_cod;
    $filtro = strtolower(trim($filtro));
    if (!in_array($filtro, array('todos', 'parametrizados', 'pendientes'), true)) {
        $filtro = 'todos';
    }

    $partidas = ppto_partidas_listar($mysqli, array('emp_id' => $emp_id, 'solo_activas' => true));
    $conteo = ppto_param_contable_conteo_por_rubro($mysqli, $emp_id, $pla_cod);

    $visibles = array();
    foreach ($partidas as $p) {
        $clase = isset($p['ppa_clase']) && $p['ppa_clase'] !== '' ? $p['ppa_clase'] : 'D';
        $ppa_id = (int)$p['ppa_id'];
        $n_cta = isset($conteo[$ppa_id]) ? $conteo[$ppa_id] : 0;
        $es_detalle = ($clase === 'D');

        if ($es_detalle) {
            if ($filtro === 'parametrizados' && $n_cta < 1) {
                continue;
            }
            if ($filtro === 'pendientes' && $n_cta > 0) {
                continue;
            }
        }

        $visibles[] = array(
            'ppa_id' => $ppa_id,
            'ppa_codigo_clasificacion' => $p['ppa_codigo_clasificacion'],
            'ppa_descripcion' => $p['ppa_descripcion'],
            'ppa_tipo' => $p['ppa_tipo'],
            'ppa_naturaleza' => $p['ppa_naturaleza'],
            'ppa_clase' => $clase,
            'ppa_nivel' => (int)$p['ppa_nivel'],
            'ppa_padre_id' => !empty($p['ppa_padre_id']) ? (int)$p['ppa_padre_id'] : 0,
            'ppa_estado' => $p['ppa_estado'],
            'cuentas' => $n_cta,
            'estado_param' => !$es_detalle ? 'grupo' : ($n_cta > 0 ? 'completo' : 'pendiente'),
            'indent_px' => ppto_partida_indent_px((int)$p['ppa_nivel']),
            'tree_prefix' => ppto_partida_tree_prefix_html((int)$p['ppa_nivel']),
        );
    }

    // Ocultar grupos sin hijos visibles al filtrar
    if ($filtro !== 'todos') {
        $codigos_detalle = array();
        foreach ($visibles as $v) {
            if ($v['ppa_clase'] === 'D') {
                $codigos_detalle[] = $v['ppa_codigo_clasificacion'];
            }
        }
        $filtrados = array();
        foreach ($visibles as $v) {
            if ($v['ppa_clase'] === 'D') {
                $filtrados[] = $v;
                continue;
            }
            $pref = $v['ppa_codigo_clasificacion'] . '.';
            $tiene = false;
            foreach ($codigos_detalle as $cd) {
                if ($cd === $v['ppa_codigo_clasificacion'] || strpos($cd, $pref) === 0) {
                    $tiene = true;
                    break;
                }
            }
            if ($tiene) {
                $filtrados[] = $v;
            }
        }
        $visibles = $filtrados;
    }

    return $visibles;
}

/**
 * Cuentas asignadas a un rubro (sin movimiento; lazy aparte).
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $pla_cod
 * @param int $ppa_id
 * @return array
 */
function ppto_param_contable_cuentas_rubro($mysqli, $emp_id, $pla_cod, $ppa_id) {
    $emp_id = (int)$emp_id;
    $pla_cod = (int)$pla_cod;
    $ppa_id = (int)$ppa_id;
    $rows = array();
    $sql = "SELECT ppc.ppc_id, ppc.pld_cod, ppc.ppc_estado, ppc.ppc_fecha_registro,
            d.Pld_Cdc, d.Pld_Des, d.Pld_Tip, d.Pld_Est, d.Pld_Deb, d.Pld_Cre
        FROM exa_ppto_partida_cuenta ppc
        INNER JOIN det_plan d ON d.Pld_Cod = ppc.pld_cod
        WHERE ppc.emp_id=$emp_id AND ppc.pla_cod=$pla_cod AND ppc.ppa_id=$ppa_id
          AND ppc.ppc_estado='A'
        ORDER BY d.Pld_Cdc ASC";
    $res = $mysqli->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = array(
                'ppc_id' => (int)$r['ppc_id'],
                'pld_cod' => (int)$r['pld_cod'],
                'codigo' => $r['Pld_Cdc'],
                'descripcion' => $r['Pld_Des'],
                'tipo' => $r['Pld_Tip'],
                'estado' => $r['Pld_Est'],
                'naturaleza' => ppto_param_contable_naturaleza_cuenta($r),
                'clasif_codigo' => ppto_param_contable_clasif_codigo($r['Pld_Cdc']),
            );
        }
    }
    return $rows;
}

/**
 * Detalle de rubro + cuentas (movimientos bajo demanda).
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $pla_cod
 * @param int $ppa_id
 * @return array
 */
function ppto_param_contable_rubro_detalle($mysqli, $emp_id, $pla_cod, $ppa_id) {
    $p = ppto_partida_obtener($mysqli, (int)$ppa_id, (int)$emp_id);
    if (!$p) {
        return array('ok' => false, 'message' => 'Rubro no encontrado.');
    }
    $clase = isset($p['ppa_clase']) && $p['ppa_clase'] !== '' ? $p['ppa_clase'] : 'D';
    if ($clase !== 'D') {
        return array('ok' => false, 'message' => 'Solo rubros de tipo Detalle pueden parametrizarse.');
    }
    $cuentas = ppto_param_contable_cuentas_rubro($mysqli, $emp_id, $pla_cod, $ppa_id);
    return array(
        'ok' => true,
        'rubro' => array(
            'ppa_id' => (int)$p['ppa_id'],
            'codigo' => $p['ppa_codigo_clasificacion'],
            'descripcion' => $p['ppa_descripcion'],
            'tipo' => $p['ppa_tipo'],
            'naturaleza' => $p['ppa_naturaleza'],
            'clase' => $clase,
            'estado' => $p['ppa_estado'],
            'cuentas' => count($cuentas),
            'estado_param' => count($cuentas) > 0 ? 'completo' : 'pendiente',
        ),
        'cuentas' => $cuentas,
    );
}

/**
 * Movimiento acumulado y ultimo mov. (lazy) para una lista de Pld_Cod.
 *
 * @param mysqli $mysqli
 * @param int $pec_cod
 * @param array $pld_cods
 * @return array pld_cod => {acumulado, ultimo_mov}
 */
function ppto_param_contable_movimientos_cuentas($mysqli, $pec_cod, $pld_cods) {
    $out = array();
    $pec_cod = (int)$pec_cod;
    $ids = array();
    foreach ((array)$pld_cods as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $ids[$id] = true;
            $out[$id] = array('acumulado' => 0.0, 'ultimo_mov' => null);
        }
    }
    if ($pec_cod <= 0 || empty($ids)) {
        return $out;
    }
    $in = implode(',', array_keys($ids));
    $sql = "SELECT a.Pld_Cod AS pld_cod,
            SUM(CASE WHEN a.Asi_Deh='D' THEN a.Asi_Val WHEN a.Asi_Deh='H' THEN -a.Asi_Val ELSE 0 END) AS acum,
            MAX(c.Com_Fec) AS ultimo_mov
        FROM asientos a
        INNER JOIN comprobantes c ON c.Com_Cod = a.Com_Cod
        WHERE c.Pec_Cod = $pec_cod AND c.Com_Est = 'A' AND a.Pld_Cod IN ($in)
        GROUP BY a.Pld_Cod";
    $res = @$mysqli->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $pid = (int)$r['pld_cod'];
            $out[$pid] = array(
                'acumulado' => round((float)$r['acum'], 2),
                'ultimo_mov' => $r['ultimo_mov'] ? $r['ultimo_mov'] : null,
            );
        }
    }
    return $out;
}

/**
 * Busca cuentas del plan (grupos + detalle) para asignar a un rubro.
 * Los grupos guian el arbol; solo Detalle es asignable.
 * Incluye ya parametrizadas (marcadas) para mostrarlas deshabilitadas.
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $pla_cod
 * @param string $q Texto codigo/descripcion (vacio = navegar por grupo)
 * @param int $limit
 * @param string $grupo Prefijo grupo balance (ej. 5) o "todas"
 * @param string $filtro libres|todas
 * @return array
 */
function ppto_param_contable_buscar_cuentas($mysqli, $emp_id, $pla_cod, $q = '', $limit = 80, $grupo = 'todas', $filtro = 'todas') {
    $emp_id = (int)$emp_id;
    $pla_cod = (int)$pla_cod;
    $limit = max(10, min(800, (int)$limit));
    $q = trim($q);
    $grupo = trim((string)$grupo);
    if ($grupo === '' || strtolower($grupo) === 'todas') {
        $grupo = 'todas';
    }
    $filtro = (strtolower(trim($filtro)) === 'libres') ? 'libres' : 'todas';

    // Sin grupo ni texto: no volcar todo el plan (el front guia al usuario).
    if ($q === '' && $grupo === 'todas') {
        return array();
    }

    $where_q = '';
    if ($q !== '') {
        $terms = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
        if (!$terms) {
            $terms = array($q);
        }
        foreach ($terms as $term) {
            $esc = $mysqli->real_escape_string($term);
            $where_q .= " AND (d.Pld_Cdc LIKE '$esc%' OR d.Pld_Cdc LIKE '%$esc%' OR d.Pld_Des LIKE '%$esc%')";
        }
    }

    $where_grupo = '';
    if ($grupo !== 'todas') {
        $esc_g = $mysqli->real_escape_string($grupo);
        $where_grupo = " AND (d.Pld_Cdc = '$esc_g' OR d.Pld_Cdc LIKE '" . $esc_g . ".%')";
    }

    // Grupos siempre visibles (contexto del plan); libres aplica solo a detalle.
    $where_filtro = '';
    if ($filtro === 'libres') {
        $where_filtro = " AND (d.Pld_Tip = 'G' OR ppc.ppa_id IS NULL)";
    }

    $sql = "SELECT d.Pld_Cod, d.Pld_Cdc, d.Pld_Des, d.Pld_Tip, d.Pld_Est, d.Pld_Deb, d.Pld_Cre,
            ppc.ppa_id AS ppa_asignado,
            p.ppa_codigo_clasificacion AS rubro_codigo,
            p.ppa_descripcion AS rubro_descripcion
        FROM det_plan d
        LEFT JOIN exa_ppto_partida_cuenta ppc
            ON ppc.pld_cod = d.Pld_Cod AND ppc.emp_id = $emp_id
           AND ppc.pla_cod = $pla_cod AND ppc.ppc_estado = 'A'
        LEFT JOIN exa_ppto_partidas p ON p.ppa_id = ppc.ppa_id AND p.emp_id = $emp_id
        WHERE d.Pla_Cod = $pla_cod AND d.Pld_Est = 'A' AND d.Pld_Tip IN ('G','D')
        $where_q
        $where_grupo
        $where_filtro
        ORDER BY d.Pld_Cdc ASC
        LIMIT $limit";
    $rows = array();
    $res = $mysqli->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $tip = isset($r['Pld_Tip']) ? $r['Pld_Tip'] : 'D';
            $es_grupo = ($tip === 'G');
            $asignado = !$es_grupo && !empty($r['ppa_asignado']);
            $codigo = (string)$r['Pld_Cdc'];
            $nivel = substr_count($codigo, '.');
            $rows[] = array(
                'pld_cod' => (int)$r['Pld_Cod'],
                'codigo' => $codigo,
                'descripcion' => $r['Pld_Des'],
                'tipo' => $tip,
                'es_grupo' => $es_grupo,
                'asignable' => !$es_grupo,
                'estado' => $r['Pld_Est'],
                'clasif_codigo' => ppto_param_contable_clasif_codigo($r['Pld_Cdc']),
                'nivel' => $nivel,
                'asignada' => $asignado,
                'rubro_codigo' => $asignado ? $r['rubro_codigo'] : null,
                'rubro_descripcion' => $asignado ? $r['rubro_descripcion'] : null,
                'ppa_asignado' => $asignado ? (int)$r['ppa_asignado'] : 0,
            );
        }
    }
    return $rows;
}

/**
 * Listado informativo: grupos (contexto) + detalles sin rubro.
 * Los grupos raiz y el filtro salen de Configurar Balances (det_estado).
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $pla_cod
 * @param string $grupo_filtro todas|codigo Pld_Cdc del grupo configurado
 * @param string $q
 * @param int $limit
 * @return array
 */
function ppto_param_contable_cuentas_pendientes($mysqli, $emp_id, $pla_cod, $grupo_filtro = 'todas', $q = '', $limit = 0) {
    $emp_id = (int)$emp_id;
    $pla_cod = (int)$pla_cod;
    $grupo_filtro = trim((string)$grupo_filtro);
    if ($grupo_filtro === '' || strtolower($grupo_filtro) === 'todas') {
        $grupo_filtro = 'todas';
    }
    // Plan completo ~800-1000 cuentas: sin tope bajo. "todos" no debe cortarse en Activo/Pasivo.
    if ((int)$limit <= 0) {
        $limit = ($grupo_filtro === 'todas') ? 2500 : 1500;
    }
    $limit = max(50, min(3000, (int)$limit));
    $q = trim($q);
    $grupos = ppto_param_contable_grupos_balance($mysqli, $pla_cod);

    $where_q = '';
    if ($q !== '') {
        $esc = $mysqli->real_escape_string($q);
        $where_q = " AND (d.Pld_Cdc LIKE '%$esc%' OR d.Pld_Des LIKE '%$esc%')";
    }

    // Prefijo del grupo de balance seleccionado.
    $where_grupo = '';
    if ($grupo_filtro !== 'todas') {
        $esc_g = $mysqli->real_escape_string($grupo_filtro);
        $where_grupo = " AND (d.Pld_Cdc = '$esc_g' OR d.Pld_Cdc LIKE '" . $esc_g . ".%')";
    } elseif (!empty($grupos)) {
        $parts = array();
        foreach ($grupos as $g) {
            $esc_g = $mysqli->real_escape_string($g['codigo']);
            $parts[] = "(d.Pld_Cdc = '$esc_g' OR d.Pld_Cdc LIKE '" . $esc_g . ".%')";
        }
        if (!empty($parts)) {
            $where_grupo = ' AND (' . implode(' OR ', $parts) . ')';
        }
    }

    $sql = "SELECT d.Pld_Cod, d.Pld_Cdc, d.Pld_Des, d.Pld_Tip, d.Pld_Est, d.Pld_Deb, d.Pld_Cre,
            ppc.ppa_id AS ppa_asignado,
            p.ppa_codigo_clasificacion AS rubro_codigo,
            p.ppa_descripcion AS rubro_descripcion
        FROM det_plan d
        LEFT JOIN exa_ppto_partida_cuenta ppc
            ON ppc.pld_cod = d.Pld_Cod AND ppc.emp_id = $emp_id
           AND ppc.pla_cod = $pla_cod AND ppc.ppc_estado = 'A'
        LEFT JOIN exa_ppto_partidas p ON p.ppa_id = ppc.ppa_id AND p.emp_id = $emp_id
        WHERE d.Pla_Cod = $pla_cod AND d.Pld_Est = 'A' AND d.Pld_Tip IN ('G','D')
        $where_q
        $where_grupo
        ORDER BY d.Pld_Cdc ASC
        LIMIT $limit";

    $out = array();
    $res = $mysqli->query($sql);
    if (!$res) {
        return $out;
    }

    while ($r = $res->fetch_assoc()) {
        $tip = isset($r['Pld_Tip']) ? $r['Pld_Tip'] : 'D';
        $es_grupo = ($tip === 'G');
        $asignado = !$es_grupo && !empty($r['ppa_asignado']);

        $raiz = ppto_param_contable_resolver_grupo_balance($r['Pld_Cdc'], $grupos);
        $codigo = (string)$r['Pld_Cdc'];
        $nivel = substr_count($codigo, '.');
        $out[] = array(
            'pld_cod' => (int)$r['Pld_Cod'],
            'codigo' => $codigo,
            'descripcion' => $r['Pld_Des'],
            'tipo' => $tip,
            'es_grupo' => $es_grupo,
            'estado' => $r['Pld_Est'],
            'naturaleza' => ppto_param_contable_naturaleza_cuenta($r),
            'grupo_codigo' => $raiz ? $raiz['codigo'] : '',
            'grupo_descripcion' => $raiz ? $raiz['descripcion'] : '',
            'clasif_codigo' => $raiz ? $raiz['descripcion'] : ppto_param_contable_clasif_codigo($r['Pld_Cdc']),
            'asignada' => $asignado,
            'rubro_codigo' => $asignado ? $r['rubro_codigo'] : null,
            'rubro_descripcion' => $asignado ? $r['rubro_descripcion'] : null,
            'ppa_asignado' => $asignado ? (int)$r['ppa_asignado'] : 0,
            'nivel' => $nivel,
            'indent_px' => max(0, $nivel * 14),
            'asignable' => !$es_grupo && !$asignado,
        );
    }
    return $out;
}

/**
 * Asigna cuenta a rubro.
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $pla_cod
 * @param int $ppa_id
 * @param int $pld_cod
 * @param int $usu_id
 * @return array
 */
function ppto_param_contable_asignar($mysqli, $emp_id, $pla_cod, $ppa_id, $pld_cod, $usu_id) {
    $emp_id = (int)$emp_id;
    $pla_cod = (int)$pla_cod;
    $ppa_id = (int)$ppa_id;
    $pld_cod = (int)$pld_cod;
    $usu_id = (int)$usu_id;

    $p = ppto_partida_obtener($mysqli, $ppa_id, $emp_id);
    if (!$p || $p['ppa_estado'] !== 'A') {
        return array('ok' => false, 'message' => 'Rubro invalido o inactivo.');
    }
    $clase = isset($p['ppa_clase']) && $p['ppa_clase'] !== '' ? $p['ppa_clase'] : 'D';
    if ($clase !== 'D') {
        return array('ok' => false, 'message' => 'Solo se pueden relacionar rubros Detalle.');
    }

    $res = $mysqli->query("SELECT Pld_Cod, Pld_Tip, Pld_Est, Pld_Cdc, Pld_Des, Pla_Cod
        FROM det_plan WHERE Pld_Cod=$pld_cod LIMIT 1");
    if (!$res || !($cta = $res->fetch_assoc())) {
        return array('ok' => false, 'message' => 'Cuenta contable no encontrada.');
    }
    if ((int)$cta['Pla_Cod'] !== $pla_cod) {
        return array('ok' => false, 'message' => 'La cuenta no pertenece al plan de la empresa.');
    }
    if ($cta['Pld_Tip'] !== 'D') {
        return array('ok' => false, 'message' => 'Solo se pueden relacionar cuentas Detalle.');
    }
    if ($cta['Pld_Est'] !== 'A') {
        return array('ok' => false, 'message' => 'La cuenta esta inactiva.');
    }

    $res = $mysqli->query("SELECT ppc.ppa_id, p.ppa_codigo_clasificacion, p.ppa_descripcion
        FROM exa_ppto_partida_cuenta ppc
        INNER JOIN exa_ppto_partidas p ON p.ppa_id=ppc.ppa_id AND p.emp_id=ppc.emp_id
        WHERE ppc.emp_id=$emp_id AND ppc.pla_cod=$pla_cod AND ppc.pld_cod=$pld_cod AND ppc.ppc_estado='A'
        LIMIT 1");
    if ($res && ($ex = $res->fetch_assoc())) {
        if ((int)$ex['ppa_id'] === $ppa_id) {
            return array('ok' => true, 'message' => 'La cuenta ya estaba asignada a este rubro.');
        }
        return array(
            'ok' => false,
            'message' => 'La cuenta ' . $cta['Pld_Cdc'] . ' ya pertenece al rubro '
                . $ex['ppa_codigo_clasificacion'] . ' - ' . $ex['ppa_descripcion'] . '.',
        );
    }

    $ok = $mysqli->query("INSERT INTO exa_ppto_partida_cuenta
        (emp_id, pla_cod, ppa_id, pld_cod, ppc_estado, ppc_fecha_registro, usu_id)
        VALUES ($emp_id, $pla_cod, $ppa_id, $pld_cod, 'A', NOW(), $usu_id)");
    if (!$ok) {
        return array('ok' => false, 'message' => 'No se pudo asignar: ' . $mysqli->error);
    }
    return array('ok' => true, 'message' => 'Cuenta asignada correctamente.', 'ppc_id' => (int)$mysqli->insert_id);
}

/**
 * Quita asignacion.
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $ppc_id
 * @return array
 */
function ppto_param_contable_quitar($mysqli, $emp_id, $ppc_id) {
    $emp_id = (int)$emp_id;
    $ppc_id = (int)$ppc_id;
    $ok = $mysqli->query("DELETE FROM exa_ppto_partida_cuenta
        WHERE ppc_id=$ppc_id AND emp_id=$emp_id LIMIT 1");
    if (!$ok || $mysqli->affected_rows < 1) {
        return array('ok' => false, 'message' => 'No se encontro la asignacion.');
    }
    return array('ok' => true, 'message' => 'Cuenta desasignada.');
}

/**
 * Auditoria on-demand.
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $pla_cod
 * @param int $pec_cod
 * @return array
 */
function ppto_param_contable_auditar($mysqli, $emp_id, $pla_cod, $pec_cod = 0) {
    $emp_id = (int)$emp_id;
    $pla_cod = (int)$pla_cod;
    $pec_cod = (int)$pec_cod;
    $hallazgos = array();

    $res = $mysqli->query("SELECT p.ppa_id, p.ppa_codigo_clasificacion, p.ppa_descripcion
        FROM exa_ppto_partidas p
        WHERE p.emp_id=$emp_id AND p.ppa_estado='A'
          AND COALESCE(NULLIF(p.ppa_clase,''),'D')='D'
          AND NOT EXISTS (
            SELECT 1 FROM exa_ppto_partida_cuenta ppc
            WHERE ppc.ppa_id=p.ppa_id AND ppc.emp_id=$emp_id
              AND ppc.pla_cod=$pla_cod AND ppc.ppc_estado='A'
          )
        ORDER BY p.ppa_codigo_clasificacion
        LIMIT 100");
    $items = array();
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $items[] = $r['ppa_codigo_clasificacion'] . ' - ' . $r['ppa_descripcion'];
        }
    }
    $hallazgos[] = array(
        'codigo' => 'rubros_sin_cuentas',
        'titulo' => 'Rubros sin cuentas',
        'severidad' => 'alta',
        'total' => count($items),
        'items' => $items,
    );

    $pend = ppto_param_contable_cuentas_pendientes($mysqli, $emp_id, $pla_cod, 'todas', '', 100);
    $items = array();
    foreach ($pend as $c) {
        $items[] = $c['codigo'] . ' - ' . $c['descripcion'];
    }
    $hallazgos[] = array(
        'codigo' => 'cuentas_sin_rubro',
        'titulo' => 'Cuentas sin rubro',
        'severidad' => 'media',
        'total' => count($items),
        'items' => array_slice($items, 0, 50),
    );

    $res = $mysqli->query("SELECT d.Pld_Cdc, d.Pld_Des, p.ppa_codigo_clasificacion
        FROM exa_ppto_partida_cuenta ppc
        INNER JOIN det_plan d ON d.Pld_Cod=ppc.pld_cod
        INNER JOIN exa_ppto_partidas p ON p.ppa_id=ppc.ppa_id AND p.emp_id=ppc.emp_id
        WHERE ppc.emp_id=$emp_id AND ppc.pla_cod=$pla_cod AND ppc.ppc_estado='A'
          AND d.Pld_Est <> 'A'
        LIMIT 50");
    $items = array();
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $items[] = $r['Pld_Cdc'] . ' -> rubro ' . $r['ppa_codigo_clasificacion'];
        }
    }
    $hallazgos[] = array(
        'codigo' => 'cuentas_inactivas',
        'titulo' => 'Cuentas inactivas asignadas',
        'severidad' => 'alta',
        'total' => count($items),
        'items' => $items,
    );

    $res = $mysqli->query("SELECT pld_cod, COUNT(*) AS n FROM exa_ppto_partida_cuenta
        WHERE emp_id=$emp_id AND pla_cod=$pla_cod AND ppc_estado='A'
        GROUP BY pld_cod HAVING n > 1");
    $dup = 0;
    if ($res) {
        while ($res->fetch_assoc()) {
            $dup++;
        }
    }
    $hallazgos[] = array(
        'codigo' => 'cuentas_duplicadas',
        'titulo' => 'Cuentas duplicadas',
        'severidad' => 'critica',
        'total' => $dup,
        'items' => $dup > 0 ? array('Existen cuentas con mas de un rubro (revisar UNIQUE).') : array(),
    );

    if ($pla_cod <= 0) {
        $hallazgos[] = array(
            'codigo' => 'config',
            'titulo' => 'Errores de configuracion',
            'severidad' => 'critica',
            'total' => 1,
            'items' => array('No hay plan de cuentas activo para la empresa.'),
        );
    }

    return array(
        'ok' => true,
        'fecha' => date('Y-m-d H:i:s'),
        'hallazgos' => $hallazgos,
    );
}

/**
 * Copia parametrizacion entre planes (a�os) de la misma empresa.
 * Empareja cuentas por Pld_Cdc cuando el plan destino es distinto.
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $anio_origen
 * @param int $anio_destino
 * @param int $usu_id
 * @param bool $sobreescribir
 * @return array
 */
function ppto_param_contable_copiar($mysqli, $emp_id, $anio_origen, $anio_destino, $usu_id, $sobreescribir = false) {
    $emp_id = (int)$emp_id;
    $anio_origen = (int)$anio_origen;
    $anio_destino = (int)$anio_destino;
    $usu_id = (int)$usu_id;

    if ($anio_origen <= 0 || $anio_destino <= 0) {
        return array('ok' => false, 'message' => 'Indique anio origen y destino.');
    }
    if ($anio_origen === $anio_destino) {
        return array('ok' => false, 'message' => 'Origen y destino deben ser anios distintos.');
    }

    $src = ppto_param_contable_plan_empresa($mysqli, $emp_id, $anio_origen);
    $dst = ppto_param_contable_plan_empresa($mysqli, $emp_id, $anio_destino);
    if (!$src || $src['pla_cod'] <= 0) {
        return array('ok' => false, 'message' => 'No hay plan contable para el anio origen ' . $anio_origen . '.');
    }
    if (!$dst || $dst['pla_cod'] <= 0) {
        return array('ok' => false, 'message' => 'No hay plan contable para el anio destino ' . $anio_destino . '.');
    }

    $pla_src = (int)$src['pla_cod'];
    $pla_dst = (int)$dst['pla_cod'];

    $res = $mysqli->query("SELECT ppc.ppa_id, ppc.pld_cod, d.Pld_Cdc
        FROM exa_ppto_partida_cuenta ppc
        INNER JOIN det_plan d ON d.Pld_Cod = ppc.pld_cod
        WHERE ppc.emp_id=$emp_id AND ppc.pla_cod=$pla_src AND ppc.ppc_estado='A'");
    if (!$res) {
        return array('ok' => false, 'message' => 'Error al leer origen: ' . $mysqli->error);
    }

    $origen_rows = array();
    while ($r = $res->fetch_assoc()) {
        $origen_rows[] = $r;
    }
    if (empty($origen_rows)) {
        return array('ok' => false, 'message' => 'El anio ' . $anio_origen . ' no tiene parametrizacion para copiar.');
    }

    $cdc_to_pld = array();
    $res = $mysqli->query("SELECT Pld_Cod, Pld_Cdc FROM det_plan
        WHERE Pla_Cod=$pla_dst AND Pld_Tip='D' AND Pld_Est='A'");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $cdc_to_pld[$r['Pld_Cdc']] = (int)$r['Pld_Cod'];
        }
    }

    $copiados = 0;
    $omitidos = 0;
    $errores = 0;

    foreach ($origen_rows as $row) {
        $ppa_id = (int)$row['ppa_id'];
        $cdc = $row['Pld_Cdc'];
        if ($pla_src === $pla_dst) {
            $pld_dst = (int)$row['pld_cod'];
        } else {
            if (!isset($cdc_to_pld[$cdc])) {
                $omitidos++;
                continue;
            }
            $pld_dst = $cdc_to_pld[$cdc];
        }

        $p = ppto_partida_obtener($mysqli, $ppa_id, $emp_id);
        if (!$p || $p['ppa_estado'] !== 'A') {
            $omitidos++;
            continue;
        }

        $existe = $mysqli->query("SELECT ppc_id, ppa_id FROM exa_ppto_partida_cuenta
            WHERE emp_id=$emp_id AND pla_cod=$pla_dst AND pld_cod=$pld_dst AND ppc_estado='A' LIMIT 1");
        if ($existe && ($ex = $existe->fetch_assoc())) {
            if ((int)$ex['ppa_id'] === $ppa_id) {
                $omitidos++;
                continue;
            }
            if (!$sobreescribir) {
                $omitidos++;
                continue;
            }
            $mysqli->query("DELETE FROM exa_ppto_partida_cuenta WHERE ppc_id=" . (int)$ex['ppc_id']);
        }

        $r = ppto_param_contable_asignar($mysqli, $emp_id, $pla_dst, $ppa_id, $pld_dst, $usu_id);
        if (!empty($r['ok'])) {
            $copiados++;
        } else {
            $errores++;
        }
    }

    return array(
        'ok' => true,
        'message' => "Copia $anio_origen -> $anio_destino: $copiados asignada(s), $omitidos omitida(s), $errores error(es).",
        'copiados' => $copiados,
        'omitidos' => $omitidos,
        'errores' => $errores,
        'pla_origen' => $pla_src,
        'pla_destino' => $pla_dst,
    );
}

/**
 * Normaliza texto para comparacion (heuristica Sugerir cuentas).
 *
 * @param string $s
 * @return string
 */
function ppto_param_contable_norm_texto($s) {
    $s = strtolower(trim((string)$s));
    if ($s === '') {
        return '';
    }
    $from = array('�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�');
    $to   = array('a','e','i','o','u','n','u','a','e','i','o','u','a','e','i','o','u','n','u');
    $s = str_replace($from, $to, $s);
    $s = preg_replace('/[^a-z0-9\s\.]+/', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

/**
 * Score 0-100 entre rubro y cuenta (heuristica; preparado para sustituir por IA).
 *
 * @param string $rubro_cod
 * @param string $rubro_des
 * @param string $cta_cod
 * @param string $cta_des
 * @return array {score, razones[]}
 */
function ppto_param_contable_score_sugerencia($rubro_cod, $rubro_des, $cta_cod, $cta_des) {
    $r_cod = ppto_param_contable_norm_texto($rubro_cod);
    $r_des = ppto_param_contable_norm_texto($rubro_des);
    $c_cod = ppto_param_contable_norm_texto($cta_cod);
    $c_des = ppto_param_contable_norm_texto($cta_des);
    $score = 0;
    $razones = array();

    if ($r_des !== '' && $c_des !== '' && $r_des === $c_des) {
        $score += 55;
        $razones[] = 'Nombre exacto';
    } elseif ($r_des !== '' && $c_des !== '') {
        $pct = 0;
        similar_text($r_des, $c_des, $pct);
        if ($pct >= 55) {
            $add = (int)round($pct * 0.45);
            $score += $add;
            $razones[] = 'Similitud nombre ' . round($pct) . '%';
        }
        $rw = array_filter(explode(' ', $r_des), function ($w) { return strlen($w) >= 4; });
        $hits = 0;
        foreach ($rw as $w) {
            if (strpos($c_des, $w) !== false) {
                $hits++;
            }
        }
        if ($hits > 0 && count($rw) > 0) {
            $add = min(25, $hits * 8);
            $score += $add;
            $razones[] = $hits . ' palabra(s) en comun';
        }
    }

    if ($r_cod !== '' && $c_cod !== '') {
        if ($r_cod === $c_cod) {
            $score += 40;
            $razones[] = 'Codigo igual';
        } elseif (strpos($c_cod, $r_cod) === 0 || strpos($r_cod, $c_cod) === 0) {
            $score += 20;
            $razones[] = 'Codigo relacionado';
        }
    }

    if ($score > 100) {
        $score = 100;
    }
    return array('score' => $score, 'razones' => $razones);
}

/**
 * Sugiere cuentas detalle libres para un rubro. NO asigna.
 * Contrato estable para futuras mejoras con IA (mismo shape de respuesta).
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $pla_cod
 * @param int $ppa_id
 * @param int $top
 * @return array
 */
function ppto_param_contable_sugerir($mysqli, $emp_id, $pla_cod, $ppa_id, $top = 12) {
    $emp_id = (int)$emp_id;
    $pla_cod = (int)$pla_cod;
    $ppa_id = (int)$ppa_id;
    $top = max(3, min(30, (int)$top));

    $p = ppto_partida_obtener($mysqli, $ppa_id, $emp_id);
    if (!$p) {
        return array('ok' => false, 'message' => 'Rubro no encontrado.', 'sugerencias' => array());
    }
    $clase = isset($p['ppa_clase']) && $p['ppa_clase'] !== '' ? $p['ppa_clase'] : 'D';
    if ($clase !== 'D') {
        return array('ok' => false, 'message' => 'Solo rubros Detalle admiten sugerencias.', 'sugerencias' => array());
    }

    $sql = "SELECT d.Pld_Cod, d.Pld_Cdc, d.Pld_Des, d.Pld_Tip, d.Pld_Est, d.Pld_Deb, d.Pld_Cre
        FROM det_plan d
        WHERE d.Pla_Cod = $pla_cod AND d.Pld_Tip = 'D' AND d.Pld_Est = 'A'
          AND NOT EXISTS (
            SELECT 1 FROM exa_ppto_partida_cuenta ppc
            WHERE ppc.pld_cod = d.Pld_Cod AND ppc.emp_id = $emp_id
              AND ppc.pla_cod = $pla_cod AND ppc.ppc_estado = 'A'
          )
        ORDER BY d.Pld_Cdc ASC
        LIMIT 1200";
    $res = $mysqli->query($sql);
    $cand = array();
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $sc = ppto_param_contable_score_sugerencia(
                $p['ppa_codigo_clasificacion'],
                $p['ppa_descripcion'],
                $r['Pld_Cdc'],
                $r['Pld_Des']
            );
            if ($sc['score'] < 28) {
                continue;
            }
            $cand[] = array(
                'pld_cod' => (int)$r['Pld_Cod'],
                'codigo' => $r['Pld_Cdc'],
                'descripcion' => $r['Pld_Des'],
                'naturaleza' => ppto_param_contable_naturaleza_cuenta($r),
                'score' => $sc['score'],
                'razones' => $sc['razones'],
                'motor' => 'heuristica_v1',
            );
        }
    }

    usort($cand, function ($a, $b) {
        if ($a['score'] === $b['score']) {
            return strcmp($a['codigo'], $b['codigo']);
        }
        return ($a['score'] > $b['score']) ? -1 : 1;
    });
    $cand = array_slice($cand, 0, $top);

    return array(
        'ok' => true,
        'message' => count($cand) ? (count($cand) . ' sugerencia(s). Revise y acepte manualmente.') : 'Sin coincidencias suficientes.',
        'motor' => 'heuristica_v1',
        'rubro' => array(
            'ppa_id' => (int)$p['ppa_id'],
            'codigo' => $p['ppa_codigo_clasificacion'],
            'descripcion' => $p['ppa_descripcion'],
        ),
        'sugerencias' => $cand,
    );
}

/**
 * Mapa Contable Presupuestario: rubro <-> cuentas (+ mov opcional).
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $pla_cod
 * @param int $pec_cod
 * @param bool $con_movimientos
 * @param string $filtro todos|parametrizados|pendientes
 * @return array
 */
function ppto_param_contable_mapa($mysqli, $emp_id, $pla_cod, $pec_cod = 0, $con_movimientos = false, $filtro = 'todos') {
    $emp_id = (int)$emp_id;
    $pla_cod = (int)$pla_cod;
    $pec_cod = (int)$pec_cod;
    $filtro = strtolower(trim($filtro));
    if (!in_array($filtro, array('todos', 'parametrizados', 'pendientes'), true)) {
        $filtro = 'todos';
    }

    $sql = "SELECT p.ppa_id, p.ppa_codigo_clasificacion, p.ppa_descripcion, p.ppa_tipo, p.ppa_naturaleza,
            COALESCE(NULLIF(p.ppa_clase,''),'D') AS ppa_clase,
            ppc.ppc_id, ppc.pld_cod, ppc.ppc_estado, ppc.ppc_fecha_registro,
            d.Pld_Cdc, d.Pld_Des, d.Pld_Tip, d.Pld_Est, d.Pld_Deb, d.Pld_Cre
        FROM exa_ppto_partidas p
        LEFT JOIN exa_ppto_partida_cuenta ppc
            ON ppc.ppa_id = p.ppa_id AND ppc.emp_id = p.emp_id
           AND ppc.pla_cod = $pla_cod AND ppc.ppc_estado = 'A'
        LEFT JOIN det_plan d ON d.Pld_Cod = ppc.pld_cod
        WHERE p.emp_id = $emp_id AND p.ppa_estado = 'A'
          AND COALESCE(NULLIF(p.ppa_clase,''),'D') = 'D'
        ORDER BY p.ppa_codigo_clasificacion ASC, d.Pld_Cdc ASC";
    $res = $mysqli->query($sql);
    $map = array();
    $pld_ids = array();

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $ppa = (int)$r['ppa_id'];
            if (!isset($map[$ppa])) {
                $map[$ppa] = array(
                    'ppa_id' => $ppa,
                    'codigo' => $r['ppa_codigo_clasificacion'],
                    'descripcion' => $r['ppa_descripcion'],
                    'tipo' => $r['ppa_tipo'],
                    'naturaleza' => $r['ppa_naturaleza'],
                    'estado_param' => 'pendiente',
                    'cuentas' => array(),
                );
            }
            if (!empty($r['pld_cod'])) {
                $pld = (int)$r['pld_cod'];
                $pld_ids[$pld] = true;
                $map[$ppa]['cuentas'][] = array(
                    'ppc_id' => (int)$r['ppc_id'],
                    'pld_cod' => $pld,
                    'codigo' => $r['Pld_Cdc'],
                    'descripcion' => $r['Pld_Des'],
                    'naturaleza' => ppto_param_contable_naturaleza_cuenta($r),
                    'pld_estado' => $r['Pld_Est'],
                    'acumulado' => null,
                    'ultimo_mov' => null,
                );
                $map[$ppa]['estado_param'] = 'completo';
            }
        }
    }

    $rows = array_values($map);
    if ($filtro === 'parametrizados') {
        $rows = array_values(array_filter($rows, function ($x) { return $x['estado_param'] === 'completo'; }));
    } elseif ($filtro === 'pendientes') {
        $rows = array_values(array_filter($rows, function ($x) { return $x['estado_param'] === 'pendiente'; }));
    }

    if ($con_movimientos && $pec_cod > 0 && !empty($pld_ids)) {
        $mov = ppto_param_contable_movimientos_cuentas($mysqli, $pec_cod, array_keys($pld_ids));
        foreach ($rows as &$rubro) {
            foreach ($rubro['cuentas'] as &$cta) {
                $mid = $cta['pld_cod'];
                if (isset($mov[$mid])) {
                    $cta['acumulado'] = $mov[$mid]['acumulado'];
                    $cta['ultimo_mov'] = $mov[$mid]['ultimo_mov'];
                } else {
                    $cta['acumulado'] = 0.0;
                    $cta['ultimo_mov'] = null;
                }
            }
            unset($cta);
        }
        unset($rubro);
    }

    $n_param = 0;
    $n_cta = 0;
    foreach ($rows as $x) {
        if ($x['estado_param'] === 'completo') {
            $n_param++;
        }
        $n_cta += count($x['cuentas']);
    }

    return array(
        'ok' => true,
        'filtro' => $filtro,
        'con_movimientos' => (bool)$con_movimientos,
        'totales' => array(
            'rubros' => count($rows),
            'parametrizados' => $n_param,
            'pendientes' => count($rows) - $n_param,
            'cuentas' => $n_cta,
        ),
        'rows' => $rows,
    );
}
