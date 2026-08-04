<?php
/**
 * ppto_param_ejecutado_logica.php
 * Fase C: ejecutado presupuestario desde mayores contables (mapeo partida_cuenta).
 * Escribe agregados mensuales en el ledger (vista exa_ppto_ejecuciones / pre_ejecucion).
 * No toca otros tipodoc (compras, etc.): solo pej_tipo_documento = mayor_contable.
 */

require_once __DIR__ . '/ppto_param_contable_logica.php';
require_once __DIR__ . '/ppto_persistencia_logica.php';

define('PPTO_PEJ_TIPO_MAYOR', 'mayor_contable');

/**
 * Cabecera presupuesto activa/preferida para empresa+anio.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $anio
 * @param int $ppe_id_preferido
 * @return int
 */
function ppto_param_ejecutado_resolver_ppe($mysqli, $Emp_Cod, $anio, $ppe_id_preferido = 0) {
    $Emp_Cod = (int)$Emp_Cod;
    $anio = (int)$anio;
    $ppe_id_preferido = (int)$ppe_id_preferido;
    if ($ppe_id_preferido > 0) {
        $res = $mysqli->query("SELECT Ppe_Cod AS ppe_id FROM pre_presupuesto
            WHERE Ppe_Cod=$ppe_id_preferido AND Emp_Cod=$Emp_Cod LIMIT 1");
        if ($res && $res->fetch_assoc()) {
            return $ppe_id_preferido;
        }
    }
    $activo = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'ppe_anio' => $anio));
    if ($activo) {
        return (int)$activo;
    }
    $res = $mysqli->query("SELECT Ppe_Cod AS ppe_id FROM pre_presupuesto
        WHERE Emp_Cod=$Emp_Cod AND Ppe_Ani=$anio
        ORDER BY Ppe_Ver DESC LIMIT 1");
    if ($res && ($r = $res->fetch_assoc())) {
        return (int)$r['ppe_id'];
    }
    return 0;
}

/**
 * Codigo documento estable por rubro/mes/proyecto (idempotente).
 *
 * @param int $anio
 * @param int $mes
 * @param int $ppa_id
 * @param string|null $proy_id
 * @return string
 */
function ppto_param_ejecutado_doc_cod($anio, $mes, $ppa_id, $proy_id = null) {
    $proy = ($proy_id !== null && trim($proy_id) !== '')
        ? preg_replace('/[^A-Za-z0-9_-]/', '', trim($proy_id))
        : 'EMP';
    if ($proy === '') {
        $proy = 'EMP';
    }
    return sprintf('MAY-%04d%02d-%d-%s', (int)$anio, (int)$mes, (int)$ppa_id, $proy);
}

/**
 * Mapa ppa_id => proy_id cuando el rubro pertenece a un solo proyecto publicado.
 * Si el rubro esta en varios proyectos o en ninguno, no se atribuye (null).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @return array<int,string>
 */
function ppto_param_ejecutado_mapa_proy_por_ppa($mysqli, $Emp_Cod, $ppe_id) {
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $map = array();
    if ($Emp_Cod <= 0 || $ppe_id <= 0) {
        return $map;
    }

    $sql = "SELECT ppa_id, MIN(proy_id) AS proy_id, COUNT(DISTINCT proy_id) AS n_proy
        FROM exa_ppto_proyecto_detalles
        WHERE Emp_Cod = $Emp_Cod
          AND ppe_id = $ppe_id
          AND proy_id IS NOT NULL AND proy_id != ''
        GROUP BY ppa_id
        HAVING COUNT(DISTINCT proy_id) = 1";
    $res = @$mysqli->query($sql);
    if (!$res) {
        return $map;
    }
    while ($r = $res->fetch_assoc()) {
        if ((int)$r['n_proy'] === 1 && !empty($r['proy_id'])) {
            $map[(int)$r['ppa_id']] = trim($r['proy_id']);
        }
    }
    return $map;
}

/**
 * Neto contable D - H por partida mapeada y mes (comprobantes activos del periodo).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $Pla_Cod
 * @param int $pec_cod
 * @param int $anio
 * @param int $mes
 * @return array ppa_id => {ppa_id, codigo, descripcion, ppa_tipo, neto, monto, cuentas}
 */
function ppto_param_ejecutado_calcular_mes($mysqli, $Emp_Cod, $Pla_Cod, $pec_cod, $anio, $mes) {
    $Emp_Cod = (int)$Emp_Cod;
    $Pla_Cod = (int)$Pla_Cod;
    $pec_cod = (int)$pec_cod;
    $anio = (int)$anio;
    $mes = (int)$mes;
    $out = array();
    if ($Emp_Cod <= 0 || $Pla_Cod <= 0 || $pec_cod <= 0 || $mes < 1 || $mes > 12) {
        return $out;
    }

    $sql = "SELECT ppc.ppa_id, p.ppa_codigo_clasificacion, p.ppa_descripcion, p.ppa_tipo,
            a.Pld_Cod,
            SUM(CASE WHEN a.Asi_Deh='D' THEN a.Asi_Val WHEN a.Asi_Deh='H' THEN -a.Asi_Val ELSE 0 END) AS neto
        FROM exa_ppto_partida_cuenta ppc
        INNER JOIN pre_partidas p ON (p.Ppa_Cod = ppc.ppa_id OR p.Ppa_Cod = ppc.Ppa_Cod) AND p.Emp_Cod = ppc.Emp_Cod
        INNER JOIN asientos a ON a.Pld_Cod = ppc.Pld_Cod
        INNER JOIN comprobantes c ON c.Com_Cod = a.Com_Cod
        WHERE ppc.Emp_Cod = $Emp_Cod
          AND ppc.Pla_Cod = $Pla_Cod
          AND ppc.ppc_estado = 'A'
          AND p.ppa_estado = 'A'
          AND COALESCE(NULLIF(p.ppa_clase,''),'D') = 'D'
          AND c.Pec_Cod = $pec_cod
          AND c.Com_Est = 'A'
          AND YEAR(c.Com_Fec) = $anio
          AND MONTH(c.Com_Fec) = $mes
        GROUP BY ppc.ppa_id, p.ppa_codigo_clasificacion, p.ppa_descripcion, p.ppa_tipo, a.Pld_Cod";

    $res = @$mysqli->query($sql);
    if (!$res) {
        return $out;
    }

    while ($r = $res->fetch_assoc()) {
        $ppa = (int)$r['ppa_id'];
        if (!isset($out[$ppa])) {
            $out[$ppa] = array(
                'ppa_id' => $ppa,
                'codigo' => $r['ppa_codigo_clasificacion'],
                'descripcion' => $r['ppa_descripcion'],
                'ppa_tipo' => $r['ppa_tipo'],
                'neto' => 0.0,
                'monto' => 0.0,
                'cuentas' => 0,
            );
        }
        $out[$ppa]['neto'] += (float)$r['neto'];
        $out[$ppa]['cuentas']++;
    }

    foreach ($out as $ppa => &$row) {
        $neto = round($row['neto'], 2);
        $row['neto'] = $neto;
        // I = ingreso (Haber neto); G/V = gasto/inversion (Debe neto).
        $tipo = isset($row['ppa_tipo']) ? $row['ppa_tipo'] : 'G';
        if ($tipo === 'I') {
            $monto = -$neto;
        } else {
            $monto = $neto;
        }
        if ($monto < 0) {
            $monto = 0.0;
        }
        $row['monto'] = round($monto, 2);
    }
    unset($row);

    return $out;
}

/**
 * Vista previa de sincronizacion (sin escribir).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $anio
 * @param int $mes_desde
 * @param int $mes_hasta
 * @param int $ppe_id
 * @return array
 */
function ppto_param_ejecutado_preview($mysqli, $Emp_Cod, $anio, $mes_desde = 1, $mes_hasta = 12, $ppe_id = 0) {
    $Emp_Cod = (int)$Emp_Cod;
    $anio = (int)$anio;
    $mes_desde = max(1, min(12, (int)$mes_desde));
    $mes_hasta = max($mes_desde, min(12, (int)$mes_hasta));

    $plan = ppto_param_contable_plan_empresa($mysqli, $Emp_Cod, $anio);
    if (!$plan || (int)$plan['Pla_Cod'] <= 0 || (int)$plan['pec_cod'] <= 0) {
        return array('ok' => false, 'message' => 'Sin plan/periodo contable para el anio.', 'meses' => array());
    }
    $ppe = ppto_param_ejecutado_resolver_ppe($mysqli, $Emp_Cod, $anio, $ppe_id);
    if ($ppe <= 0) {
        return array('ok' => false, 'message' => 'Sin cabecera de presupuesto para el anio.', 'meses' => array());
    }

    $mapa_n = 0;
    $res = $mysqli->query("SELECT COUNT(*) AS n FROM exa_ppto_partida_cuenta
        WHERE Emp_Cod=$Emp_Cod AND Pla_Cod=" . (int)$plan['Pla_Cod'] . " AND ppc_estado='A'");
    if ($res && ($r = $res->fetch_assoc())) {
        $mapa_n = (int)$r['n'];
    }
    if ($mapa_n < 1) {
        return array(
            'ok' => false,
            'message' => 'No hay parametrizacion contable (cuentas mapeadas). Complete el mapeo antes de sincronizar.',
            'meses' => array(),
            'plan' => $plan,
            'ppe_id' => $ppe,
        );
    }

    $meses = array();
    $total_monto = 0.0;
    $total_lineas = 0;
    for ($m = $mes_desde; $m <= $mes_hasta; $m++) {
        $calc = ppto_param_ejecutado_calcular_mes(
            $mysqli,
            $Emp_Cod,
            (int)$plan['Pla_Cod'],
            (int)$plan['pec_cod'],
            $anio,
            $m
        );
        $lineas = 0;
        $monto_mes = 0.0;
        $detalle = array();
        foreach ($calc as $row) {
            if ($row['monto'] <= 0) {
                continue;
            }
            $lineas++;
            $monto_mes += $row['monto'];
            if (count($detalle) < 40) {
                $detalle[] = $row;
            }
        }
        $meses[] = array(
            'mes' => $m,
            'lineas' => $lineas,
            'monto' => round($monto_mes, 2),
            'detalle' => $detalle,
        );
        $total_lineas += $lineas;
        $total_monto += $monto_mes;
    }

    return array(
        'ok' => true,
        'message' => 'Vista previa lista.',
        'plan' => $plan,
        'ppe_id' => $ppe,
        'anio' => $anio,
        'mes_desde' => $mes_desde,
        'mes_hasta' => $mes_hasta,
        'mapeos' => $mapa_n,
        'totales' => array(
            'lineas' => $total_lineas,
            'monto' => round($total_monto, 2),
        ),
        'meses' => $meses,
    );
}

/**
 * Borra agregados previos mayor_contable del rango.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param int $anio
 * @param int $mes_desde
 * @param int $mes_hasta
 * @return int filas afectadas (aprox)
 */
function ppto_param_ejecutado_limpiar_rango($mysqli, $Emp_Cod, $ppe_id, $anio, $mes_desde, $mes_hasta) {
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $anio = (int)$anio;
    $mes_desde = (int)$mes_desde;
    $mes_hasta = (int)$mes_hasta;
    $tipo = PPTO_PEJ_TIPO_MAYOR;
    $ok = $mysqli->query("DELETE FROM pre_ejecucion
        WHERE Emp_Cod=$Emp_Cod AND Ppe_Cod=$ppe_id AND Pej_Ani=$anio
          AND Pej_Mes BETWEEN $mes_desde AND $mes_hasta
          AND Pej_TipDoc='$tipo'");
    return $ok ? (int)$mysqli->affected_rows : 0;
}

/**
 * Sincroniza ejecutado mensual desde mayores al ledger presupuestario.
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $anio
 * @param int $mes_desde
 * @param int $mes_hasta
 * @param int $Usu_Cod
 * @param int $ppe_id
 * @return array
 */
function ppto_param_ejecutado_sincronizar($mysqli, $Emp_Cod, $anio, $mes_desde = 1, $mes_hasta = 12, $Usu_Cod = 0, $ppe_id = 0) {
    $prev = ppto_param_ejecutado_preview($mysqli, $Emp_Cod, $anio, $mes_desde, $mes_hasta, $ppe_id);
    if (empty($prev['ok'])) {
        return $prev;
    }

    $ppe = (int)$prev['ppe_id'];
    $Pla_Cod = (int)$prev['plan']['Pla_Cod'];
    $pec_cod = (int)$prev['plan']['pec_cod'];
    $Usu_Cod = (int)$Usu_Cod;
    $borrados = ppto_param_ejecutado_limpiar_rango($mysqli, $Emp_Cod, $ppe, $anio, $mes_desde, $mes_hasta);
    $mapa_proy = ppto_param_ejecutado_mapa_proy_por_ppa($mysqli, $Emp_Cod, $ppe);

    $insertados = 0;
    $omitidos = 0;
    $errores = 0;
    $con_proy = 0;
    $sin_proy = 0;

    for ($m = $mes_desde; $m <= $mes_hasta; $m++) {
        $calc = ppto_param_ejecutado_calcular_mes($mysqli, $Emp_Cod, $Pla_Cod, $pec_cod, $anio, $m);
        $fec = sprintf('%04d-%02d-01', $anio, $m);
        foreach ($calc as $row) {
            if ($row['monto'] <= 0) {
                $omitidos++;
                continue;
            }
            $proy = isset($mapa_proy[$row['ppa_id']]) ? $mapa_proy[$row['ppa_id']] : null;
            if ($proy) {
                $con_proy++;
            } else {
                $sin_proy++;
            }
            $doc = ppto_param_ejecutado_doc_cod($anio, $m, $row['ppa_id'], $proy);
            $id = ppto_persistencia_consultar($mysqli, 4, array(
                'ppe_id' => $ppe,
                'ppa_id' => $row['ppa_id'],
                'Emp_Cod' => $Emp_Cod,
                'proy_id' => $proy,
                'pej_mes' => $m,
                'pej_anio' => $anio,
                'pej_tipo_documento' => PPTO_PEJ_TIPO_MAYOR,
                'pej_documento_codigo' => $doc,
                'pej_monto' => $row['monto'],
                'pej_signo' => '+',
                'pej_fecha_documento' => $fec,
                'Usu_Cod' => $Usu_Cod > 0 ? $Usu_Cod : 1,
                'pej_fase' => 'E',
                'pej_rubro' => null,
                'prg_id' => null,
            ));
            if ($id) {
                $insertados++;
            } else {
                // Fallback directo a tabla fisica si la vista no es insertable
                $proy_sql = ($proy !== null && $proy !== '')
                    ? "'" . $mysqli->real_escape_string($proy) . "'"
                    : 'NULL';
                $sql = "INSERT INTO pre_ejecucion
                    (Ppe_Cod, Ppa_Cod, Emp_Cod, Proy_Cod, Pej_Mes, Pej_Ani, Pej_TipDoc, Pej_DocCod, Pej_Mon, Pej_Sig, Pej_Fec, Usu_Cod, Pej_Fase)
                    VALUES ($ppe, {$row['ppa_id']}, $Emp_Cod, $proy_sql, $m, $anio, '" . PPTO_PEJ_TIPO_MAYOR . "',
                        '" . $mysqli->real_escape_string($doc) . "', " . (float)$row['monto'] . ", '+', '$fec',
                        " . ($Usu_Cod > 0 ? $Usu_Cod : 1) . ", 'E')";
                if (@$mysqli->query($sql)) {
                    $insertados++;
                } else {
                    // Sin columna Proy_Cod en pre_ejecucion
                    $sql2 = "INSERT INTO pre_ejecucion
                        (Ppe_Cod, Ppa_Cod, Emp_Cod, Pej_Mes, Pej_Ani, Pej_TipDoc, Pej_DocCod, Pej_Mon, Pej_Sig, Pej_Fec, Usu_Cod, Pej_Fase)
                        VALUES ($ppe, {$row['ppa_id']}, $Emp_Cod, $m, $anio, '" . PPTO_PEJ_TIPO_MAYOR . "',
                            '" . $mysqli->real_escape_string($doc) . "', " . (float)$row['monto'] . ", '+', '$fec',
                            " . ($Usu_Cod > 0 ? $Usu_Cod : 1) . ", 'E')";
                    if ($mysqli->query($sql2)) {
                        $insertados++;
                    } else {
                        $errores++;
                    }
                }
            }
        }
    }

    return array(
        'ok' => $errores === 0,
        'message' => "Sincronizado $anio ($mes_desde-$mes_hasta): $insertados linea(s), $omitidos omitida(s), $borrados previa(s) reemplazada(s), $errores error(es). "
            . "Con proyecto: $con_proy / Sin proyecto (plan empresa): $sin_proy.",
        'ppe_id' => $ppe,
        'plan' => $prev['plan'],
        'mapeos' => isset($prev['mapeos']) ? $prev['mapeos'] : 0,
        'anio' => $anio,
        'mes_desde' => $mes_desde,
        'mes_hasta' => $mes_hasta,
        'insertados' => $insertados,
        'omitidos' => $omitidos,
        'borrados' => $borrados,
        'errores' => $errores,
        'con_proyecto' => $con_proy,
        'sin_proyecto' => $sin_proy,
        'totales' => $prev['totales'],
    );
}
