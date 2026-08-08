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
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @return array|null
 */
function ppto_proy_publicar_ultima($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod) {
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $res = $mysqli->query(
        "SELECT Pub_Cod AS pub_id, Pub_Cod,
                Pub_TotNuevo AS pub_total_nuevo, Pub_TotNuevo,
                Pub_FecReg AS pub_fecha_registro, Pub_FecReg,
                Pub_Anio AS pub_anio, Pub_Anio,
                Usu_Cod
         FROM pre_proyecto_publicacion
         WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod
         ORDER BY Pub_FecReg DESC, Pub_Cod DESC
         LIMIT 1"
    );
    if ($res && ($row = $res->fetch_assoc())) {
        return $row;
    }
    return null;
}

/**
 * Lista rubros del proyecto con metadatos para publicacion.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @return array
 */
function ppto_proy_publicar_listar_rubros($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod) {
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $rows = array();
    $sql = "SELECT d.Pdp_Cod AS Pdp_Cod, d.Pdp_Cod, d.Ppa_Cod AS Ppa_Cod, d.Ppa_Cod,
            d.Pdp_Rubro AS Pdp_Rubro, d.Pdp_Rubro,
            d.Pdp_FacAnualTon AS Pdp_FacAnualTon, d.Pdp_FacAnualTon,
            d.Pdp_TonBase AS Pdp_TonBase, d.Pdp_TonBase,
            d.Pdp_PreAnual AS Pdp_PreAnual, d.Pdp_PreAnual,
            p.Ppa_Cla
        FROM pre_proyecto_detalles d
        INNER JOIN pre_partidas p ON d.Ppa_Cod = p.Ppa_Cod
        WHERE d.Pro_Cod='$esc' AND d.Emp_Cod=$Emp_Cod AND d.Ppe_Cod=$Ppe_Cod
        ORDER BY p.Ppa_Cla, d.Pdp_Rubro";
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
    $factor = (float)$rubro['Pdp_FacAnualTon'];
    $anual_cuadro = (float)$rubro['Pdp_PreAnual'];
    $es_driver = ($factor > 0.0001);
    $out = array();
    $total = 0.0;

    for ($m = 1; $m <= 12; $m++) {
        $monto = 0.0;
        if ($es_driver && $modo === 'proyectada') {
            $ton = ppto_forecast_ton_proyectada_mes($meses_prod[$m]);
            $monto = ppto_forecast_pf_rubro_mes($ton, $factor);
        } elseif ($es_driver) {
            $ton_base = isset($rubro['Pdp_TonBase']) ? (float)$rubro['Pdp_TonBase'] : 0.0;
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
 * @param int $Pdp_Cod
 * @return float
 */
function ppto_proy_publicar_vigente_rubro($mysqli, $Pdp_Cod) {
    $Pdp_Cod = (int)$Pdp_Cod;
    $res = $mysqli->query("SELECT COALESCE(SUM(Pdm_PreMensual),0) AS s
        FROM pre_proyecto_detalles_mes WHERE Pdp_Cod=$Pdp_Cod");
    if ($res && ($r = $res->fetch_assoc())) {
        return round((float)$r['s'], 2);
    }
    return 0.0;
}

/**
 * Preview de publicacion: totales cuadro vs proyectado a publicar.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param int $anio
 * @return array
 */
function ppto_proy_publicar_preview($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio) {
    $rubros = ppto_proy_publicar_listar_rubros($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    if (empty($rubros)) {
        return array('ok' => false, 'message' => 'No hay rubros en este proyecto y version.');
    }

    $meses_prod = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $Pro_Cod);
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
        $Pdp_Cod = (int)$rubro['Pdp_Cod'];
        $factor = (float)$rubro['Pdp_FacAnualTon'];
        $es_driver = ($factor > 0.0001);
        if ($es_driver) {
            $driver++;
        } else {
            $fijo++;
        }
        $vig = ppto_proy_publicar_vigente_rubro($mysqli, $Pdp_Cod);
        $montos = ppto_proy_publicar_montos_rubro($rubro, $meses_prod, 'proyectada');
        $total_vigente += $vig;
        $total_publicar += (float)$montos['anual'];
        $detalle[] = array(
            'Pdp_Cod' => $Pdp_Cod,
            'codigo' => $rubro['Ppa_Cla'],
            'rubro' => $rubro['Pdp_Rubro'],
            'es_driver' => $es_driver,
            'vigente' => $vig,
            'publicar' => (float)$montos['anual'],
            'delta' => round((float)$montos['anual'] - $vig, 2),
        );
    }

    if ($driver > 0 && $meses_sin_proy > 0) {
        $warnings[] = 'Hay ' . $meses_sin_proy . ' mes(es) sin tonelada proyectada ni esperada; revise Produccion antes de publicar.';
    }

    $ultima = ppto_proy_publicar_ultima($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
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
 * @param int $Pdp_Cod
 * @param array $montos_mes
 * @return array|null null si ok, array error si bloquea
 */
function ppto_proy_publicar_validar_piso_movimiento($mysqli, $Pdp_Cod, $montos_mes) {
    $Pdp_Cod = (int)$Pdp_Cod;
    $res = $mysqli->query("SELECT Pdm_Mes, Pdm_Comprometido, Pdm_Ejecutado
        FROM pre_proyecto_detalles_mes WHERE Pdp_Cod=$Pdp_Cod");
    if (!$res) {
        return null;
    }
    while ($row = $res->fetch_assoc()) {
        $mes = (int)$row['Pdm_Mes'];
        $piso = round((float)$row['Pdm_Comprometido'] + (float)$row['Pdm_Ejecutado'], 2);
        $nuevo = isset($montos_mes[$mes]) ? (float)$montos_mes[$mes] : 0.0;
        if ($piso > 0.0001 && $nuevo + 0.009 < $piso) {
            return array(
                'Pdp_Cod' => $Pdp_Cod,
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
 * @param int $Pdp_Cod
 * @param array $montos_mes
 * @return float anual
 */
function ppto_proy_publicar_aplicar_pdm($mysqli, $Pdp_Cod, $montos_mes) {
    $Pdp_Cod = (int)$Pdp_Cod;
    $anual = 0.0;
    for ($m = 1; $m <= 12; $m++) {
        $monto = isset($montos_mes[$m]) ? round((float)$montos_mes[$m], 2) : 0.0;
        $anual += $monto;
        $mysqli->query("INSERT INTO pre_proyecto_detalles_mes
            (Pdp_Cod, Pdm_Mes, Pdm_DiasLab, Pdm_FacMensual, Pdm_PreMensual, Pdm_Ejecutado, Pdm_Comprometido, Pdm_Disponible)
            VALUES ($Pdp_Cod, $m, 22, 0.0833, $monto, 0, 0, $monto)
            ON DUPLICATE KEY UPDATE
                Pdm_PreMensual=$monto,
                Pdm_Disponible=GREATEST(0, $monto - Pdm_Ejecutado - Pdm_Comprometido)");
    }
    return round($anual, 2);
}

/**
 * Consolida por partida en pre_detalle (VA empresa sin Pro_Cod).
 * DESACTIVADO por defecto: contaminaba el plan estandar con montos de proyectos (RCET).
 * El presupuesto de proyecto vive en pre_proyecto_detalles / pre_proyecto_detalles_mes. Lote B.
 *
 * @param mysqli $mysqli
 * @param int $Ppe_Cod
 * @param array $agg Ppa_Cod => mes => monto
 */
function ppto_proy_publicar_sync_detalles($mysqli, $Ppe_Cod, $agg) {
    // No-op intencional: no mezclar plan estandar (empresa) con publicacion de proyectos.
    return;
}

/**
 * Limpia de pre_detalle montos de partidas que ya viven en proyectos presupuestarios.
 * Restaura el "plan estandar" sin duplicar Relavera/RCET.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @return array
 */
function ppto_proy_limpiar_detalles_contaminados_proyecto($mysqli, $Emp_Cod, $Ppe_Cod) {
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    if ($Emp_Cod <= 0 || $Ppe_Cod <= 0) {
        return array('ok' => false, 'message' => 'Parametros invalidos.', 'eliminados' => 0);
    }
    $sql = "DELETE d FROM pre_detalle d
        INNER JOIN (
            SELECT DISTINCT Ppa_Cod
            FROM pre_proyecto_detalles
            WHERE Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod
              AND Pro_Cod IS NOT NULL
        ) px ON px.Ppa_Cod = d.Ppa_Cod
        WHERE d.Ppe_Cod=$Ppe_Cod";
    $ok = $mysqli->query($sql);
    $n = $ok ? (int)$mysqli->affected_rows : 0;
    return array(
        'ok' => (bool)$ok,
        'message' => $ok
            ? ('Plan estandar depurado: ' . $n . ' fila(s) de proyecto quitadas de pre_detalle.')
            : ('Error al depurar: ' . $mysqli->error),
        'eliminados' => $n,
    );
}

/**
 * Ejecuta publicacion completa.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param int $anio
 * @param int $Usu_Cod
 * @param bool $forzar_republicacion
 * @param bool $sync_detalles
 * @return array
 */
function ppto_proy_publicar_ejecutar($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, $Usu_Cod, $forzar_republicacion = false, $sync_detalles = false) {
    $preview = ppto_proy_publicar_preview($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio);
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

    $rubros = ppto_proy_publicar_listar_rubros($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    $meses_prod = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $Pro_Cod);
    $bloqueos = array();
    $plan = array();

    foreach ($rubros as $rubro) {
        $Pdp_Cod = (int)$rubro['Pdp_Cod'];
        $montos = ppto_proy_publicar_montos_rubro($rubro, $meses_prod, 'proyectada');
        unset($montos['anual']);
        $bloqueo = ppto_proy_publicar_validar_piso_movimiento($mysqli, $Pdp_Cod, $montos);
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
        $Pdp_Cod = (int)$rubro['Pdp_Cod'];
        $Ppa_Cod = (int)$rubro['Ppa_Cod'];

        $anual_aplicado = ppto_proy_publicar_aplicar_pdm($mysqli, $Pdp_Cod, $montos);
        $mysqli->query("UPDATE pre_proyecto_detalles
            SET Pdp_PreAnual=$anual_aplicado
            WHERE Pdp_Cod=$Pdp_Cod");

        for ($m = 1; $m <= 12; $m++) {
            if (!isset($agg_partida[$Ppa_Cod])) {
                $agg_partida[$Ppa_Cod] = array();
            }
            if (!isset($agg_partida[$Ppa_Cod][$m])) {
                $agg_partida[$Ppa_Cod][$m] = 0.0;
            }
            $agg_partida[$Ppa_Cod][$m] += isset($montos[$m]) ? (float)$montos[$m] : 0.0;
        }
        $actualizados++;
    }

    if ($sync_detalles && !empty($agg_partida)) {
        ppto_proy_publicar_sync_detalles($mysqli, $Ppe_Cod, $agg_partida);
    }

    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $Usu_Cod = (int)$Usu_Cod;
    $anio = (int)$anio;
    $tot_ant = (float)$preview['total_vigente'];
    $tot_new = (float)$preview['total_publicar'];
    $drv = (int)$preview['rubros_driver'];
    $fij = (int)$preview['rubros_fijo'];
    $notas = 'Publicado desde ton proyectada x $/Ton';

    $mysqli->query("INSERT INTO pre_proyecto_publicacion
        (Pro_Cod, Emp_Cod, Ppe_Cod, Pub_Anio, Pub_TotAnterior, Pub_TotNuevo,
         Pub_RubDriver, Pub_RubFijo, Pub_Modo, Pub_Obs, Pub_FecReg, Usu_Cod)
        VALUES ('$esc', $Emp_Cod, $Ppe_Cod, $anio, $tot_ant, $tot_new, $drv, $fij, 'proyectada', '$notas', NOW(), $Usu_Cod)");

    return array(
        'ok' => true,
        'message' => 'Presupuesto publicado como aprobado (' . $actualizados . ' rubros). Total: ' . number_format($tot_new, 2, '.', ','),
        'total_anterior' => $tot_ant,
        'total_nuevo' => $tot_new,
        'delta' => round($tot_new - $tot_ant, 2),
        'rubros_actualizados' => $actualizados,
        'Pub_Cod' => (int)$mysqli->insert_id,
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
    $factor = (float)$rubro['Pdp_FacAnualTon'];
    $anual_cuadro = (float)$rubro['Pdp_PreAnual'];
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
 * @param int $Pdp_Cod
 * @param int $mes
 * @return float
 */
function ppto_proy_publicar_vigente_rubro_mes($mysqli, $Pdp_Cod, $mes) {
    $Pdp_Cod = (int)$Pdp_Cod;
    $mes = (int)$mes;
    $res = $mysqli->query("SELECT Pdm_PreMensual FROM pre_proyecto_detalles_mes
        WHERE Pdp_Cod=$Pdp_Cod AND Pdm_Mes=$mes LIMIT 1");
    if ($res && ($r = $res->fetch_assoc())) {
        return round((float)$r['Pdm_PreMensual'], 2);
    }
    return 0.0;
}

/**
 * Ultima aprobacion por mes (1-12) del anio.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param int $anio
 * @return array mes => row
 */
function ppto_proy_publicar_aprobaciones_mes($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio) {
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $anio = (int)$anio;
    $out = array();
    $res = $mysqli->query(
        "SELECT Pub_Cod AS pub_id, Pub_Cod,
                Pub_Mes AS pub_mes, Pub_Mes,
                Pub_TotNuevo AS pub_total_nuevo, Pub_TotNuevo,
                Pub_TotAnterior AS pub_total_anterior, Pub_TotAnterior,
                Pub_Obs AS pub_notas, Pub_Obs,
                Pub_Modo AS pub_modo, Pub_Modo,
                Pub_FecReg AS pub_fecha_registro, Pub_FecReg,
                Usu_Cod
         FROM pre_proyecto_publicacion
         WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod AND Pub_Anio=$anio
           AND Pub_Mes IS NOT NULL AND Pub_Mes BETWEEN 1 AND 12
         ORDER BY Pub_Mes ASC, Pub_FecReg DESC, Pub_Cod DESC"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $m = (int)$row['Pub_Mes'];
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
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param int $anio
 * @param int $mes
 * @return array filas ordenadas de la mas reciente a la mas antigua
 */
function ppto_proy_publicar_historial_mes($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, $mes) {
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $anio = (int)$anio;
    $mes = (int)$mes;
    $out = array();
    $res = $mysqli->query(
        "SELECT Pub_Cod AS pub_id, Pub_Cod,
                Pub_Mes AS pub_mes, Pub_Mes,
                Pub_TotNuevo AS pub_total_nuevo, Pub_TotNuevo,
                Pub_TotAnterior AS pub_total_anterior, Pub_TotAnterior,
                Pub_Obs AS pub_notas, Pub_Obs,
                Pub_Modo AS pub_modo, Pub_Modo,
                Pub_FecReg AS pub_fecha_registro, Pub_FecReg,
                Usu_Cod
         FROM pre_proyecto_publicacion
         WHERE Pro_Cod='$esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod AND Pub_Anio=$anio AND Pub_Mes=$mes
         ORDER BY Pub_FecReg DESC, Pub_Cod DESC"
    );
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
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param int $anio
 * @param int $mes
 * @param float|null $ton_proyectada_override
 * @return array
 */
function ppto_proy_publicar_preview_mes($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, $mes, $ton_proyectada_override = null) {
    $mes = (int)$mes;
    if ($mes < 1 || $mes > 12) {
        return array('ok' => false, 'message' => 'Mes invalido.');
    }
    $rubros = ppto_proy_publicar_listar_rubros($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    if (empty($rubros)) {
        return array('ok' => false, 'message' => 'No hay rubros en este proyecto. Definalos en Proyectos primero.');
    }

    $meses_prod = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $Pro_Cod);
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
        $Pdp_Cod = (int)$rubro['Pdp_Cod'];
        $vig = ppto_proy_publicar_vigente_rubro_mes($mysqli, $Pdp_Cod, $mes);
        $pub = ppto_proy_publicar_monto_rubro_mes($rubro, $meses_prod, $mes);
        $total_vigente += $vig;
        $total_publicar += $pub;
        $detalle[] = array(
            'Pdp_Cod' => $Pdp_Cod,
            'codigo' => $rubro['Ppa_Cla'],
            'rubro' => $rubro['Pdp_Rubro'],
            'vigente' => $vig,
            'publicar' => $pub,
            'delta' => round($pub - $vig, 2),
        );
    }

    $aprob = ppto_proy_publicar_aprobaciones_mes($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio);
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
 * @param int $Pdp_Cod
 * @param int $mes
 * @param float $monto
 * @return array|null
 */
function ppto_proy_publicar_validar_piso_mes($mysqli, $Pdp_Cod, $mes, $monto) {
    $Pdp_Cod = (int)$Pdp_Cod;
    $mes = (int)$mes;
    $monto = round((float)$monto, 2);
    $res = $mysqli->query("SELECT Pdm_Comprometido, Pdm_Ejecutado FROM pre_proyecto_detalles_mes
        WHERE Pdp_Cod=$Pdp_Cod AND Pdm_Mes=$mes LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $piso = round((float)$row['Pdm_Comprometido'] + (float)$row['Pdm_Ejecutado'], 2);
        if ($piso > 0.0001 && $monto + 0.009 < $piso) {
            return array('Pdp_Cod' => $Pdp_Cod, 'mes' => $mes, 'piso' => $piso, 'nuevo' => $monto);
        }
    }
    return null;
}

/**
 * Aplica monto aprobado a un mes del rubro.
 *
 * @param mysqli $mysqli
 * @param int $Pdp_Cod
 * @param int $mes
 * @param float $monto
 */
function ppto_proy_publicar_aplicar_pdm_mes($mysqli, $Pdp_Cod, $mes, $monto) {
    $Pdp_Cod = (int)$Pdp_Cod;
    $mes = (int)$mes;
    $monto = round((float)$monto, 2);
    $mysqli->query("INSERT INTO pre_proyecto_detalles_mes
        (Pdp_Cod, Pdm_Mes, Pdm_DiasLab, Pdm_FacMensual, Pdm_PreMensual, Pdm_Ejecutado, Pdm_Comprometido, Pdm_Disponible)
        VALUES ($Pdp_Cod, $mes, 22, 0.0833, $monto, 0, 0, $monto)
        ON DUPLICATE KEY UPDATE
            Pdm_PreMensual=$monto,
            Pdm_Disponible=GREATEST(0, $monto - Pdm_Ejecutado - Pdm_Comprometido)");
}

/**
 * Recalcula Pdp_PreAnual desde suma de pdm.
 *
 * @param mysqli $mysqli
 * @param int $Pdp_Cod
 */
function ppto_proy_publicar_recalc_anual_rubro($mysqli, $Pdp_Cod) {
    $Pdp_Cod = (int)$Pdp_Cod;
    $res = $mysqli->query("SELECT COALESCE(SUM(Pdm_PreMensual),0) AS s
        FROM pre_proyecto_detalles_mes WHERE Pdp_Cod=$Pdp_Cod");
    $anual = 0.0;
    if ($res && ($r = $res->fetch_assoc())) {
        $anual = round((float)$r['s'], 2);
    }
    $mysqli->query("UPDATE pre_proyecto_detalles SET Pdp_PreAnual=$anual WHERE Pdp_Cod=$Pdp_Cod");
}

/**
 * Sincroniza pre_detalle para un mes (suma rubros por partida).
 * DESACTIVADO: no contaminar plan estandar con montos de proyecto.
 *
 * @param mysqli $mysqli
 * @param int $Ppe_Cod
 * @param int $mes
 * @param array $agg Ppa_Cod => monto
 */
function ppto_proy_publicar_sync_detalles_mes($mysqli, $Ppe_Cod, $mes, $agg) {
    return;
}

/**
 * Aprueba presupuesto de un mes (proyectada x $/Ton).
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param int $anio
 * @param int $mes
 * @param int $Usu_Cod
 * @param float|null $ton_proyectada_override
 * @param bool $confirmar_reaprobacion
 * @return array
 */
function ppto_proy_publicar_ejecutar_mes($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, $mes, $Usu_Cod, $ton_proyectada_override = null, $confirmar_reaprobacion = false) {
    $preview = ppto_proy_publicar_preview_mes($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, $mes, $ton_proyectada_override);
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
    $meses_prod = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio, $Pro_Cod);
    if ($ton_proyectada_override !== null && (float)$ton_proyectada_override > 0.0001) {
        $meses_prod[$mes]['proyectada'] = (float)$ton_proyectada_override;
    }

    $rubros = ppto_proy_publicar_listar_rubros($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    $bloqueos = array();
    $plan = array();

    foreach ($rubros as $rubro) {
        $Pdp_Cod = (int)$rubro['Pdp_Cod'];
        $monto = ppto_proy_publicar_monto_rubro_mes($rubro, $meses_prod, $mes);
        $bloqueo = ppto_proy_publicar_validar_piso_mes($mysqli, $Pdp_Cod, $mes, $monto);
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
        $Pdp_Cod = (int)$rubro['Pdp_Cod'];
        $Ppa_Cod = (int)$rubro['Ppa_Cod'];
        $monto = (float)$item['monto'];
        ppto_proy_publicar_aplicar_pdm_mes($mysqli, $Pdp_Cod, $mes, $monto);
        ppto_proy_publicar_recalc_anual_rubro($mysqli, $Pdp_Cod);
        if (!isset($agg_partida[$Ppa_Cod])) {
            $agg_partida[$Ppa_Cod] = 0.0;
        }
        $agg_partida[$Ppa_Cod] += $monto;
    }

    if (!empty($agg_partida)) {
        ppto_proy_publicar_sync_detalles_mes($mysqli, $Ppe_Cod, $mes, $agg_partida);
    }

    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $tot_ant = (float)$preview['total_vigente'];
    $tot_new = (float)$preview['total_publicar'];
    $notas = 'Aprobacion mes ' . $mes . ' desde ton proyectada x $/Ton';

    $ins = $mysqli->query("INSERT INTO pre_proyecto_publicacion
        (Pro_Cod, Emp_Cod, Ppe_Cod, Pub_Anio, Pub_Mes, Pub_TotAnterior, Pub_TotNuevo,
         Pub_RubDriver, Pub_RubFijo, Pub_Modo, Pub_Obs, Pub_FecReg, Usu_Cod)
        VALUES ('$esc', " . (int)$Emp_Cod . ", " . (int)$Ppe_Cod . ", " . (int)$anio . ", $mes,
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
        'Pub_Cod' => (int)$mysqli->insert_id,
        'preview' => $preview,
    );
}
