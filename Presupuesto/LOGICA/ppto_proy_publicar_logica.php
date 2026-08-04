<?php
/**
 * Publicar presupuesto aprobado desde toneladas proyectadas x $/Ton base (modo Relavera / proyectos).
 */

require_once __DIR__ . '/ppto_forecast_logica.php';
require_once __DIR__ . '/ppto_format_helpers.php';

/**
 * Ultima publicacion del proyecto/version.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @return array|null
 */
function ppto_proy_publicar_ultima($mysqli, $proy_id, $Emp_Cod, $ppe_id) {
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $res = $mysqli->query("SELECT pub_id, pub_total_nuevo, pub_fecha_registro, pub_anio, Usu_Cod
        FROM exa_ppto_proyecto_publicacion
        WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id
        ORDER BY pub_fecha_registro DESC, pub_id DESC LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        return $row;
    }
    return null;
}

/**
 * Lista rubros del proyecto con metadatos para publicacion.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @return array
 */
function ppto_proy_publicar_listar_rubros($mysqli, $proy_id, $Emp_Cod, $ppe_id) {
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $rows = array();
    $sql = "SELECT d.pdp_id, d.ppa_id, d.pdp_rubro, d.pdp_factor_anual_tonelada,
            d.pdp_toneladas_base, d.pdp_presupuesto_anual, p.ppa_codigo_clasificacion
        FROM exa_ppto_proyecto_detalles d
        INNER JOIN pre_partidas p ON d.Ppa_Cod = p.Ppa_Cod
        WHERE d.proy_id='$esc' AND d.Emp_Cod=$Emp_Cod AND d.ppe_id=$ppe_id
        ORDER BY p.ppa_codigo_clasificacion, d.pdp_rubro";
    $res = $mysqli->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    return $rows;
}

/**
 * Calcula montos mensuales de un rubro segun modo publicacion.
 *
 * @param array $rubro
 * @param array $meses_prod
 * @param string $modo proyectada|cuadro
 * @return array mes => monto
 */
function ppto_proy_publicar_montos_rubro($rubro, $meses_prod, $modo = 'proyectada') {
    $factor = (float)$rubro['pdp_factor_anual_tonelada'];
    $anual_cuadro = (float)$rubro['pdp_presupuesto_anual'];
    $es_driver = ($factor > 0.0001);
    $out = array();
    $total = 0.0;

    for ($m = 1; $m <= 12; $m++) {
        $monto = 0.0;
        if ($es_driver && $modo === 'proyectada') {
            $ton = ppto_forecast_ton_proyectada_mes($meses_prod[$m]);
            $monto = ppto_forecast_pf_rubro_mes($ton, $factor);
        } elseif ($es_driver) {
            $ton_base = isset($rubro['pdp_toneladas_base']) ? (float)$rubro['pdp_toneladas_base'] : 0.0;
            if ($ton_base <= 0) {
                $monto = round($anual_cuadro / 12.0, 2);
            } else {
                $monto = ppto_forecast_pf_rubro_mes($ton_base, $factor);
            }
        } else {
            $monto = round($anual_cuadro / 12.0, 2);
        }
        $out[$m] = round($monto, 2);
        $total += $out[$m];
    }
    $out['anual'] = round($total, 2);
    return $out;
}

/**
 * Suma vigente actual (pdm) del rubro.
 *
 * @param mysqli $mysqli
 * @param int $pdp_id
 * @return float
 */
function ppto_proy_publicar_vigente_rubro($mysqli, $pdp_id) {
    $pdp_id = (int)$pdp_id;
    $res = $mysqli->query("SELECT COALESCE(SUM(pdm_presupuesto_mensual),0) AS s
        FROM exa_ppto_proyecto_detalles_mes WHERE pdp_id=$pdp_id");
    if ($res && ($r = $res->fetch_assoc())) {
        return round((float)$r['s'], 2);
    }
    return 0.0;
}

/**
 * Preview de publicacion: totales cuadro vs proyectado a publicar.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param int $anio
 * @return array
 */
function ppto_proy_publicar_preview($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio) {
    $rubros = ppto_proy_publicar_listar_rubros($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    if (empty($rubros)) {
        return array('ok' => false, 'message' => 'No hay rubros en este proyecto y version.');
    }

    $meses_prod = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $proy_id);
    $total_vigente = 0.0;
    $total_publicar = 0.0;
    $driver = 0;
    $fijo = 0;
    $warnings = array();
    $detalle = array();
    $meses_sin_proy = 0;

    for ($m = 1; $m <= 12; $m++) {
        if ((float)$meses_prod[$m]['proyectada'] <= 0.0001 && (float)$meses_prod[$m]['esperada'] <= 0.0001) {
            $meses_sin_proy++;
        }
    }

    foreach ($rubros as $rubro) {
        $pdp_id = (int)$rubro['pdp_id'];
        $factor = (float)$rubro['pdp_factor_anual_tonelada'];
        $es_driver = ($factor > 0.0001);
        if ($es_driver) {
            $driver++;
        } else {
            $fijo++;
        }
        $vig = ppto_proy_publicar_vigente_rubro($mysqli, $pdp_id);
        $montos = ppto_proy_publicar_montos_rubro($rubro, $meses_prod, 'proyectada');
        $total_vigente += $vig;
        $total_publicar += (float)$montos['anual'];
        $detalle[] = array(
            'pdp_id' => $pdp_id,
            'codigo' => $rubro['ppa_codigo_clasificacion'],
            'rubro' => $rubro['pdp_rubro'],
            'es_driver' => $es_driver,
            'vigente' => $vig,
            'publicar' => (float)$montos['anual'],
            'delta' => round((float)$montos['anual'] - $vig, 2),
        );
    }

    if ($driver > 0 && $meses_sin_proy > 0) {
        $warnings[] = 'Hay ' . $meses_sin_proy . ' mes(es) sin tonelada proyectada ni esperada; revise Produccion antes de publicar.';
    }

    $ultima = ppto_proy_publicar_ultima($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    $es_republicacion = ($ultima !== null);

    return array(
        'ok' => true,
        'total_vigente' => round($total_vigente, 2),
        'total_publicar' => round($total_publicar, 2),
        'delta' => round($total_publicar - $total_vigente, 2),
        'rubros_driver' => $driver,
        'rubros_fijo' => $fijo,
        'rubros_total' => count($rubros),
        'warnings' => $warnings,
        'detalle' => $detalle,
        'es_republicacion' => $es_republicacion,
        'ultima_publicacion' => $ultima,
        'ton_proyectada_anual' => round(ppto_proy_publicar_sumar_ton_proyectada($meses_prod), 2),
    );
}

/**
 * @param array $meses_prod
 * @return float
 */
function ppto_proy_publicar_sumar_ton_proyectada($meses_prod) {
    $t = 0.0;
    for ($m = 1; $m <= 12; $m++) {
        $t += ppto_forecast_ton_proyectada_mes($meses_prod[$m]);
    }
    return $t;
}

/**
 * Valida que no se reduzca por debajo de comprometido+ejecutado.
 *
 * @param mysqli $mysqli
 * @param int $pdp_id
 * @param array $montos_mes
 * @return array|null null si ok, array error si bloquea
 */
function ppto_proy_publicar_validar_piso_movimiento($mysqli, $pdp_id, $montos_mes) {
    $pdp_id = (int)$pdp_id;
    $res = $mysqli->query("SELECT pdm_mes, pdm_comprometido, pdm_ejecutado
        FROM exa_ppto_proyecto_detalles_mes WHERE pdp_id=$pdp_id");
    if (!$res) {
        return null;
    }
    while ($row = $res->fetch_assoc()) {
        $mes = (int)$row['pdm_mes'];
        $piso = round((float)$row['pdm_comprometido'] + (float)$row['pdm_ejecutado'], 2);
        $nuevo = isset($montos_mes[$mes]) ? (float)$montos_mes[$mes] : 0.0;
        if ($piso > 0.0001 && $nuevo + 0.009 < $piso) {
            return array(
                'pdp_id' => $pdp_id,
                'mes' => $mes,
                'piso' => $piso,
                'nuevo' => $nuevo,
            );
        }
    }
    return null;
}

/**
 * Actualiza pdm de un rubro y recalcula disponible.
 *
 * @param mysqli $mysqli
 * @param int $pdp_id
 * @param array $montos_mes
 * @return float anual
 */
function ppto_proy_publicar_aplicar_pdm($mysqli, $pdp_id, $montos_mes) {
    $pdp_id = (int)$pdp_id;
    $anual = 0.0;
    for ($m = 1; $m <= 12; $m++) {
        $monto = isset($montos_mes[$m]) ? round((float)$montos_mes[$m], 2) : 0.0;
        $anual += $monto;
        $mysqli->query("INSERT INTO exa_ppto_proyecto_detalles_mes
            (pdp_id, pdm_mes, pdm_dias_laborables, pdm_factor_mensual, pdm_presupuesto_mensual, pdm_ejecutado, pdm_comprometido, pdm_disponible)
            VALUES ($pdp_id, $m, 22, 0.0833, $monto, 0, 0, $monto)
            ON DUPLICATE KEY UPDATE
                pdm_presupuesto_mensual=$monto,
                pdm_disponible=GREATEST(0, $monto - pdm_ejecutado - pdm_comprometido)");
    }
    return round($anual, 2);
}

/**
 * Consolida por partida en exa_ppto_detalles (VA empresa sin proy_id).
 * DESACTIVADO por defecto: contaminaba el plan estandar con montos de proyectos (RCET).
 * El presupuesto de proyecto vive en exa_ppto_proyecto_detalles / _mes.
 *
 * @param mysqli $mysqli
 * @param int $ppe_id
 * @param array $agg ppa_id => mes => monto
 */
function ppto_proy_publicar_sync_detalles($mysqli, $ppe_id, $agg) {
    // No-op intencional: no mezclar plan estandar (empresa) con publicacion de proyectos.
    return;
}

/**
 * Limpia de exa_ppto_detalles montos de partidas que ya viven en proyectos presupuestarios.
 * Restaura el "plan estandar" sin duplicar Relavera/RCET.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @return array
 */
function ppto_proy_limpiar_detalles_contaminados_proyecto($mysqli, $Emp_Cod, $ppe_id) {
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    if ($Emp_Cod <= 0 || $ppe_id <= 0) {
        return array('ok' => false, 'message' => 'Parametros invalidos.', 'eliminados' => 0);
    }
    $sql = "DELETE d FROM pre_detalle d
        INNER JOIN (
            SELECT DISTINCT Ppa_Cod
            FROM pre_proyecto_detalles
            WHERE Emp_Cod=$Emp_Cod AND Ppe_Cod=$ppe_id
              AND Pro_Cod IS NOT NULL
        ) px ON px.Ppa_Cod = d.Ppa_Cod
        WHERE d.Ppe_Cod=$ppe_id";
    $ok = $mysqli->query($sql);
    $n = $ok ? (int)$mysqli->affected_rows : 0;
    return array(
        'ok' => (bool)$ok,
        'message' => $ok
            ? ('Plan estandar depurado: ' . $n . ' fila(s) de proyecto quitadas de exa_ppto_detalles.')
            : ('Error al depurar: ' . $mysqli->error),
        'eliminados' => $n,
    );
}

/**
 * Ejecuta publicacion completa.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param int $anio
 * @param int $Usu_Cod
 * @param bool $forzar_republicacion
 * @param bool $sync_detalles
 * @return array
 */
function ppto_proy_publicar_ejecutar($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio, $Usu_Cod, $forzar_republicacion = false, $sync_detalles = false) {
    $preview = ppto_proy_publicar_preview($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio);
    if (empty($preview['ok'])) {
        return $preview;
    }

    if (!empty($preview['es_republicacion']) && !$forzar_republicacion) {
        return array(
            'ok' => false,
            'needs_confirm' => true,
            'message' => 'Ya existe una publicacion previa. Confirme republicar para sobrescribir el presupuesto aprobado.',
            'preview' => $preview,
        );
    }

    $rubros = ppto_proy_publicar_listar_rubros($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    $meses_prod = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $proy_id);
    $bloqueos = array();
    $plan = array();

    foreach ($rubros as $rubro) {
        $pdp_id = (int)$rubro['pdp_id'];
        $montos = ppto_proy_publicar_montos_rubro($rubro, $meses_prod, 'proyectada');
        unset($montos['anual']);
        $bloqueo = ppto_proy_publicar_validar_piso_movimiento($mysqli, $pdp_id, $montos);
        if ($bloqueo !== null) {
            $bloqueos[] = $bloqueo;
        }
        $plan[] = array('rubro' => $rubro, 'montos' => $montos);
    }

    if (!empty($bloqueos)) {
        return array(
            'ok' => false,
            'message' => 'No se publico: hay rubros cuyo nuevo presupuesto queda por debajo de comprometido+ejecutado.',
            'bloqueos' => $bloqueos,
        );
    }

    $agg_partida = array();
    $actualizados = 0;

    foreach ($plan as $item) {
        $rubro = $item['rubro'];
        $montos = $item['montos'];
        $pdp_id = (int)$rubro['pdp_id'];
        $ppa_id = (int)$rubro['ppa_id'];

        $anual_aplicado = ppto_proy_publicar_aplicar_pdm($mysqli, $pdp_id, $montos);
        $mysqli->query("UPDATE exa_ppto_proyecto_detalles
            SET pdp_presupuesto_anual=$anual_aplicado
            WHERE pdp_id=$pdp_id");

        for ($m = 1; $m <= 12; $m++) {
            if (!isset($agg_partida[$ppa_id])) {
                $agg_partida[$ppa_id] = array();
            }
            if (!isset($agg_partida[$ppa_id][$m])) {
                $agg_partida[$ppa_id][$m] = 0.0;
            }
            $agg_partida[$ppa_id][$m] += isset($montos[$m]) ? (float)$montos[$m] : 0.0;
        }
        $actualizados++;
    }

    if ($sync_detalles && !empty($agg_partida)) {
        ppto_proy_publicar_sync_detalles($mysqli, $ppe_id, $agg_partida);
    }

    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $Usu_Cod = (int)$Usu_Cod;
    $anio = (int)$anio;
    $tot_ant = (float)$preview['total_vigente'];
    $tot_new = (float)$preview['total_publicar'];
    $drv = (int)$preview['rubros_driver'];
    $fij = (int)$preview['rubros_fijo'];
    $notas = 'Publicado desde ton proyectada x $/Ton';

    $mysqli->query("INSERT INTO exa_ppto_proyecto_publicacion
        (proy_id, Emp_Cod, ppe_id, pub_anio, pub_total_anterior, pub_total_nuevo,
         pub_rubros_driver, pub_rubros_fijo, pub_modo, pub_notas, pub_fecha_registro, Usu_Cod)
        VALUES ('$esc', $Emp_Cod, $ppe_id, $anio, $tot_ant, $tot_new, $drv, $fij, 'proyectada', '$notas', NOW(), $Usu_Cod)");

    return array(
        'ok' => true,
        'message' => 'Presupuesto publicado como aprobado (' . $actualizados . ' rubros). Total: ' . number_format($tot_new, 2, '.', ','),
        'total_anterior' => $tot_ant,
        'total_nuevo' => $tot_new,
        'delta' => round($tot_new - $tot_ant, 2),
        'rubros_actualizados' => $actualizados,
        'pub_id' => (int)$mysqli->insert_id,
        'preview' => $preview,
    );
}

/**
 * Monto de un rubro para un mes (proyectada x $/Ton).
 *
 * @param array $rubro
 * @param array $meses_prod
 * @param int $mes
 * @return float
 */
function ppto_proy_publicar_monto_rubro_mes($rubro, $meses_prod, $mes) {
    $mes = (int)$mes;
    if ($mes < 1 || $mes > 12) {
        return 0.0;
    }
    $factor = (float)$rubro['pdp_factor_anual_tonelada'];
    $anual_cuadro = (float)$rubro['pdp_presupuesto_anual'];
    if ($factor > 0.0001) {
        $ton = ppto_forecast_ton_proyectada_mes($meses_prod[$mes]);
        return ppto_forecast_pf_rubro_mes($ton, $factor);
    }
    return round($anual_cuadro / 12.0, 2);
}

/**
 * Vigente actual (pdm) de un rubro en un mes.
 *
 * @param mysqli $mysqli
 * @param int $pdp_id
 * @param int $mes
 * @return float
 */
function ppto_proy_publicar_vigente_rubro_mes($mysqli, $pdp_id, $mes) {
    $pdp_id = (int)$pdp_id;
    $mes = (int)$mes;
    $res = $mysqli->query("SELECT pdm_presupuesto_mensual FROM exa_ppto_proyecto_detalles_mes
        WHERE pdp_id=$pdp_id AND pdm_mes=$mes LIMIT 1");
    if ($res && ($r = $res->fetch_assoc())) {
        return round((float)$r['pdm_presupuesto_mensual'], 2);
    }
    return 0.0;
}

/**
 * Ultima aprobacion por mes (1-12) del anio.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param int $anio
 * @return array mes => row
 */
function ppto_proy_publicar_aprobaciones_mes($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio) {
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $anio = (int)$anio;
    $out = array();
    $res = $mysqli->query("SELECT pub_id, pub_mes, pub_total_nuevo, pub_total_anterior, pub_notas, pub_modo, pub_fecha_registro, Usu_Cod
        FROM exa_ppto_proyecto_publicacion
        WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id AND pub_anio=$anio
          AND pub_mes IS NOT NULL AND pub_mes BETWEEN 1 AND 12
        ORDER BY pub_mes ASC, pub_fecha_registro DESC, pub_id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $m = (int)$row['pub_mes'];
            if (!isset($out[$m])) {
                $out[$m] = $row;
                $out[$m]['veces'] = 1;
            } else {
                $out[$m]['veces']++;
            }
        }
    }
    return $out;
}

/**
 * Historial completo de aprobaciones de un mes (todas las reaprobaciones).
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param int $anio
 * @param int $mes
 * @return array filas ordenadas de la mas reciente a la mas antigua
 */
function ppto_proy_publicar_historial_mes($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio, $mes) {
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $anio = (int)$anio;
    $mes = (int)$mes;
    $out = array();
    $res = $mysqli->query("SELECT pub_id, pub_mes, pub_total_nuevo, pub_total_anterior, pub_notas, pub_modo, pub_fecha_registro, Usu_Cod
        FROM exa_ppto_proyecto_publicacion
        WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id AND pub_anio=$anio AND pub_mes=$mes
        ORDER BY pub_fecha_registro DESC, pub_id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
    }
    return $out;
}

/**
 * Preview aprobacion de un mes.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param int $anio
 * @param int $mes
 * @param float|null $ton_proyectada_override
 * @return array
 */
function ppto_proy_publicar_preview_mes($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio, $mes, $ton_proyectada_override = null) {
    $mes = (int)$mes;
    if ($mes < 1 || $mes > 12) {
        return array('ok' => false, 'message' => 'Mes invalido.');
    }
    $rubros = ppto_proy_publicar_listar_rubros($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    if (empty($rubros)) {
        return array('ok' => false, 'message' => 'No hay rubros en este proyecto. Definalos en Proyectos primero.');
    }

    $meses_prod = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $proy_id);
    if ($ton_proyectada_override !== null && (float)$ton_proyectada_override > 0.0001) {
        $meses_prod[$mes]['proyectada'] = (float)$ton_proyectada_override;
    }
    $ton = ppto_forecast_ton_proyectada_mes($meses_prod[$mes]);
    if ($ton <= 0.0001) {
        return array('ok' => false, 'message' => 'No hay tonelada proyectada (ni esperada) para el mes ' . $mes . '.');
    }

    $total_vigente = 0.0;
    $total_publicar = 0.0;
    $detalle = array();
    foreach ($rubros as $rubro) {
        $pdp_id = (int)$rubro['pdp_id'];
        $vig = ppto_proy_publicar_vigente_rubro_mes($mysqli, $pdp_id, $mes);
        $pub = ppto_proy_publicar_monto_rubro_mes($rubro, $meses_prod, $mes);
        $total_vigente += $vig;
        $total_publicar += $pub;
        $detalle[] = array(
            'pdp_id' => $pdp_id,
            'codigo' => $rubro['ppa_codigo_clasificacion'],
            'rubro' => $rubro['pdp_rubro'],
            'vigente' => $vig,
            'publicar' => $pub,
            'delta' => round($pub - $vig, 2),
        );
    }

    $aprob = ppto_proy_publicar_aprobaciones_mes($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio);
    $ya_aprobado = isset($aprob[$mes]);

    return array(
        'ok' => true,
        'mes' => $mes,
        'ton_proyectada' => round($ton, 4),
        'total_vigente' => round($total_vigente, 2),
        'total_publicar' => round($total_publicar, 2),
        'delta' => round($total_publicar - $total_vigente, 2),
        'rubros_total' => count($rubros),
        'detalle' => $detalle,
        'es_reaprobacion' => $ya_aprobado,
        'ultima_aprobacion' => $ya_aprobado ? $aprob[$mes] : null,
    );
}

/**
 * Valida piso comprometido+ejecutado para un mes.
 *
 * @param mysqli $mysqli
 * @param int $pdp_id
 * @param int $mes
 * @param float $monto
 * @return array|null
 */
function ppto_proy_publicar_validar_piso_mes($mysqli, $pdp_id, $mes, $monto) {
    $pdp_id = (int)$pdp_id;
    $mes = (int)$mes;
    $monto = round((float)$monto, 2);
    $res = $mysqli->query("SELECT pdm_comprometido, pdm_ejecutado FROM exa_ppto_proyecto_detalles_mes
        WHERE pdp_id=$pdp_id AND pdm_mes=$mes LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $piso = round((float)$row['pdm_comprometido'] + (float)$row['pdm_ejecutado'], 2);
        if ($piso > 0.0001 && $monto + 0.009 < $piso) {
            return array('pdp_id' => $pdp_id, 'mes' => $mes, 'piso' => $piso, 'nuevo' => $monto);
        }
    }
    return null;
}

/**
 * Aplica monto aprobado a un mes del rubro.
 *
 * @param mysqli $mysqli
 * @param int $pdp_id
 * @param int $mes
 * @param float $monto
 */
function ppto_proy_publicar_aplicar_pdm_mes($mysqli, $pdp_id, $mes, $monto) {
    $pdp_id = (int)$pdp_id;
    $mes = (int)$mes;
    $monto = round((float)$monto, 2);
    $mysqli->query("INSERT INTO exa_ppto_proyecto_detalles_mes
        (pdp_id, pdm_mes, pdm_dias_laborables, pdm_factor_mensual, pdm_presupuesto_mensual, pdm_ejecutado, pdm_comprometido, pdm_disponible)
        VALUES ($pdp_id, $mes, 22, 0.0833, $monto, 0, 0, $monto)
        ON DUPLICATE KEY UPDATE
            pdm_presupuesto_mensual=$monto,
            pdm_disponible=GREATEST(0, $monto - pdm_ejecutado - pdm_comprometido)");
}

/**
 * Recalcula pdp_presupuesto_anual desde suma de pdm.
 *
 * @param mysqli $mysqli
 * @param int $pdp_id
 */
function ppto_proy_publicar_recalc_anual_rubro($mysqli, $pdp_id) {
    $pdp_id = (int)$pdp_id;
    $res = $mysqli->query("SELECT COALESCE(SUM(pdm_presupuesto_mensual),0) AS s
        FROM exa_ppto_proyecto_detalles_mes WHERE pdp_id=$pdp_id");
    $anual = 0.0;
    if ($res && ($r = $res->fetch_assoc())) {
        $anual = round((float)$r['s'], 2);
    }
    $mysqli->query("UPDATE exa_ppto_proyecto_detalles SET pdp_presupuesto_anual=$anual WHERE pdp_id=$pdp_id");
}

/**
 * Sincroniza exa_ppto_detalles para un mes (suma rubros por partida).
 * DESACTIVADO: no contaminar plan estandar con montos de proyecto.
 *
 * @param mysqli $mysqli
 * @param int $ppe_id
 * @param int $mes
 * @param array $agg ppa_id => monto
 */
function ppto_proy_publicar_sync_detalles_mes($mysqli, $ppe_id, $mes, $agg) {
    return;
}

/**
 * Aprueba presupuesto de un mes (proyectada x $/Ton).
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param int $anio
 * @param int $mes
 * @param int $Usu_Cod
 * @param float|null $ton_proyectada_override
 * @param bool $confirmar_reaprobacion
 * @return array
 */
function ppto_proy_publicar_ejecutar_mes($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio, $mes, $Usu_Cod, $ton_proyectada_override = null, $confirmar_reaprobacion = false) {
    $preview = ppto_proy_publicar_preview_mes($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio, $mes, $ton_proyectada_override);
    if (empty($preview['ok'])) {
        return $preview;
    }

    if (!empty($preview['es_reaprobacion']) && !$confirmar_reaprobacion) {
        return array(
            'ok' => false,
            'needs_confirm' => true,
            'message' => 'Este mes ya fue aprobado. Confirme para reaprobar con la proyectada actual.',
            'preview' => $preview,
        );
    }

    $mes = (int)$mes;
    $meses_prod = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $proy_id);
    if ($ton_proyectada_override !== null && (float)$ton_proyectada_override > 0.0001) {
        $meses_prod[$mes]['proyectada'] = (float)$ton_proyectada_override;
    }

    $rubros = ppto_proy_publicar_listar_rubros($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    $bloqueos = array();
    $plan = array();

    foreach ($rubros as $rubro) {
        $pdp_id = (int)$rubro['pdp_id'];
        $monto = ppto_proy_publicar_monto_rubro_mes($rubro, $meses_prod, $mes);
        $bloqueo = ppto_proy_publicar_validar_piso_mes($mysqli, $pdp_id, $mes, $monto);
        if ($bloqueo !== null) {
            $bloqueos[] = $bloqueo;
        }
        $plan[] = array('rubro' => $rubro, 'monto' => $monto);
    }

    if (!empty($bloqueos)) {
        return array(
            'ok' => false,
            'message' => 'No se aprobo: el presupuesto del mes queda por debajo de comprometido+ejecutado en algun rubro.',
            'bloqueos' => $bloqueos,
        );
    }

    $agg_partida = array();
    foreach ($plan as $item) {
        $rubro = $item['rubro'];
        $pdp_id = (int)$rubro['pdp_id'];
        $ppa_id = (int)$rubro['ppa_id'];
        $monto = (float)$item['monto'];
        ppto_proy_publicar_aplicar_pdm_mes($mysqli, $pdp_id, $mes, $monto);
        ppto_proy_publicar_recalc_anual_rubro($mysqli, $pdp_id);
        if (!isset($agg_partida[$ppa_id])) {
            $agg_partida[$ppa_id] = 0.0;
        }
        $agg_partida[$ppa_id] += $monto;
    }

    if (!empty($agg_partida)) {
        ppto_proy_publicar_sync_detalles_mes($mysqli, $ppe_id, $mes, $agg_partida);
    }

    $esc = $mysqli->real_escape_string(trim($proy_id));
    $tot_ant = (float)$preview['total_vigente'];
    $tot_new = (float)$preview['total_publicar'];
    $notas = 'Aprobacion mes ' . $mes . ' desde ton proyectada x $/Ton';

    $ins = $mysqli->query("INSERT INTO exa_ppto_proyecto_publicacion
        (proy_id, Emp_Cod, ppe_id, pub_anio, pub_mes, pub_total_anterior, pub_total_nuevo,
         pub_rubros_driver, pub_rubros_fijo, pub_modo, pub_notas, pub_fecha_registro, Usu_Cod)
        VALUES ('$esc', " . (int)$Emp_Cod . ", " . (int)$ppe_id . ", " . (int)$anio . ", $mes,
         $tot_ant, $tot_new, 0, 0, 'proyectada_mes', '$notas', NOW(), " . (int)$Usu_Cod . ")");
    if (!$ins) {
        return array(
            'ok' => false,
            'message' => 'No se pudo registrar la aprobacion del mes: ' . $mysqli->error,
        );
    }

    return array(
        'ok' => true,
        'message' => 'Presupuesto de ' . ppto_nombre_mes($mes) . ' aprobado: ' . number_format($tot_new, 2, '.', ',') . ' (' . number_format($preview['ton_proyectada'], 2) . ' ton x $/Ton).',
        'mes' => $mes,
        'total_anterior' => $tot_ant,
        'total_nuevo' => $tot_new,
        'delta' => round($tot_new - $tot_ant, 2),
        'ton_proyectada' => $preview['ton_proyectada'],
        'pub_id' => (int)$mysqli->insert_id,
        'preview' => $preview,
    );
}
