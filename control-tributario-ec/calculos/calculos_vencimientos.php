<?php
/**
 * Vencimientos declaraciones — 9no dígito RUC (mes siguiente al período)
 */

function cte_dia_vencimiento_noveno($noveno) {
    $map = array(1 => 10, 2 => 12, 3 => 14, 4 => 16, 5 => 18, 6 => 20, 7 => 22, 8 => 24, 9 => 26, 0 => 28);
    return (isset($map[$noveno % 10]) ? $map[$noveno % 10] : 28);
}

/** Fecha límite para período YYYY-MM (mes declarado) */
function cte_fecha_vencimiento($anio, $mesPeriodo, $novenoDigito) {
    $mesSig = $mesPeriodo + 1;
    $anioV = $anio;
    if ($mesSig > 12) {
        $mesSig = 1;
        $anioV++;
    }
    $dia = cte_dia_vencimiento_noveno($novenoDigito);
    $ultimo = (int) date('t', mktime(0, 0, 0, $mesSig, 1, $anioV));
    if ($dia > $ultimo) {
        $dia = $ultimo;
    }
    $dt = new DateTime(sprintf('%04d-%02d-%02d', $anioV, $mesSig, $dia));
    return cte_siguiente_habil($dt);
}

function cte_siguiente_habil(DateTime $dt) {
    $d = clone $dt;
    while ((int) $d->format('N') >= 6) {
        $d->modify('+1 day');
    }
    return $d;
}

/**
 * Estado obligación: pendiente | cumplido | tardio
 * @param $presentado
 * @param string|null $fechaDecl formato Y-m-d
 */
function cte_estado_obligacion($presentado, $fechaDecl, $anio, $mes, $noveno) {
    if (!$presentado) {
        return 'pendiente';
    }
    if (!$fechaDecl) {
        return 'cumplido';
    }
    try {
        $venc = cte_fecha_vencimiento($anio, $mes, $noveno);
        $decl = new DateTime($fechaDecl);
        if ($decl > $venc) {
            return 'tardio';
        }
    } catch (Exception $e) {
        // ignore
    }
    return 'cumplido';
}

function cte_semaforo_obligaciones() {
    $c = (isset($_SESSION['contribuyente']) ? $_SESSION['contribuyente'] : array());
    $anio = (int) ((isset($c['anio']) ? $c['anio'] : date('Y')));
    $noveno = (int) ((isset($c['noveno_digito']) ? $c['noveno_digito'] : cte_noveno_digito_ruc(isset($c['ruc']) ? $c['ruc'] : '')));
    $filas = array();

    for ($m = 1; $m <= 12; $m++) {
        $dm = cte_datos_mes($m);
        $decl = (isset($_SESSION['declaraciones'][$m]) ? $_SESSION['declaraciones'][$m] : array());
        $fechaF104 = (isset($decl['fecha_recaudacion']) ? $decl['fecha_recaudacion'] : null);
        $fechaF103 = (isset($decl['fecha_f103']) ? $decl['fecha_f103'] : null);

        $obligs = array(
            'Form. 104 IVA' => array(
                'presentado' => !empty($dm['presentado_f104']),
                'fecha' => $fechaF104,
            ),
            'Form. 103 Retenciones' => array(
                'presentado' => !empty($dm['presentado_f103']),
                'fecha' => $fechaF103,
            ),
            'ATS Anexo Transaccional' => array(
                'presentado' => !empty($dm['presentado_ats']),
                'fecha' => null,
            ),
            'Planilla IESS' => array(
                'presentado' => !empty($dm['presentado_iess']),
                'fecha' => null,
            ),
        );

        foreach ($obligs as $nombre => $info) {
            $estado = cte_estado_obligacion(
                $info['presentado'],
                $info['fecha'],
                $anio,
                $m,
                $noveno
            );
            $venc = cte_fecha_vencimiento($anio, $m, $noveno);
            $filas[] = array(
                'mes' => $m,
                'mes_label' => $GLOBALS['CTE_MESES'][$m],
                'obligacion' => $nombre,
                'estado' => $estado,
                'vencimiento' => $venc->format('Y-m-d'),
            );
        }
    }
    return $filas;
}
