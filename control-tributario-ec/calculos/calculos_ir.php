<?php
/**
 * Impuesto a la Renta — personas naturales, tabla progresiva
 */

function cte_tabla_progresiva_default($anio) {
    // Fracciones básicas aproximadas SRI (actualizar según año)
    $tablas = array(
        2024 => array(
            array('hasta' => 11212, 'imp' => 0, 'exced' => 0, 'pct' => 0),
            array('hasta' => 14285, 'imp' => 0, 'exced' => 11212, 'pct' => 5),
            array('hasta' => 17854, 'imp' => 154, 'exced' => 14285, 'pct' => 10),
            array('hasta' => 21442, 'imp' => 511, 'exced' => 17854, 'pct' => 12),
            array('hasta' => 42874, 'imp' => 941, 'exced' => 21442, 'pct' => 15),
            array('hasta' => 64297, 'imp' => 4156, 'exced' => 42874, 'pct' => 20),
            array('hasta' => 85729, 'imp' => 8440, 'exced' => 64297, 'pct' => 25),
            array('hasta' => 114288, 'imp' => 13798, 'exced' => 85729, 'pct' => 30),
            array('hasta' => PHP_INT_MAX, 'imp' => 22348, 'exced' => 114288, 'pct' => 35),
        ),
        2025 => array(
            array('hasta' => 11902, 'imp' => 0, 'exced' => 0, 'pct' => 0),
            array('hasta' => 15190, 'imp' => 0, 'exced' => 11902, 'pct' => 5),
            array('hasta' => 19994, 'imp' => 164, 'exced' => 15190, 'pct' => 10),
            array('hasta' => 26464, 'imp' => 644, 'exced' => 19994, 'pct' => 12),
            array('hasta' => 34770, 'imp' => 1418, 'exced' => 26464, 'pct' => 15),
            array('hasta' => 46089, 'imp' => 2664, 'exced' => 34770, 'pct' => 20),
            array('hasta' => 61359, 'imp' => 4928, 'exced' => 46089, 'pct' => 25),
            array('hasta' => 81819, 'imp' => 8745, 'exced' => 61359, 'pct' => 30),
            array('hasta' => PHP_INT_MAX, 'imp' => 14882, 'exced' => 81819, 'pct' => 35),
        ),
        2026 => array(
            array('hasta' => 12682, 'imp' => 0, 'exced' => 0, 'pct' => 0),
            array('hasta' => 16180, 'imp' => 0, 'exced' => 12682, 'pct' => 5),
            array('hasta' => 21290, 'imp' => 175, 'exced' => 16180, 'pct' => 10),
            array('hasta' => 28190, 'imp' => 686, 'exced' => 21290, 'pct' => 12),
            array('hasta' => 37030, 'imp' => 1514, 'exced' => 28190, 'pct' => 15),
            array('hasta' => 49050, 'imp' => 2840, 'exced' => 37030, 'pct' => 20),
            array('hasta' => 65310, 'imp' => 5244, 'exced' => 49050, 'pct' => 25),
            array('hasta' => 87120, 'imp' => 9309, 'exced' => 65310, 'pct' => 30),
            array('hasta' => PHP_INT_MAX, 'imp' => 15852, 'exced' => 87120, 'pct' => 35),
        ),
    );
    return (isset($tablas[$anio]) ? $tablas[$anio] : $tablas[2025]);
}

function cte_ir_desde_tabla($base, array $tabla) {
    $base = max(0, $base);
    foreach ($tabla as $tramo) {
        if ($base <= $tramo['hasta']) {
            $exc = max(0, $base - $tramo['exced']);
            return $tramo['imp'] + $exc * ($tramo['pct'] / 100);
        }
    }
    return 0;
}

function cte_conciliacion_ir() {
    $meses = cte_todos_meses();
    $tot = cte_totales_anuales_iva($meses);
    $irAnual = (isset($_SESSION['datos_manuales']['ir_anual']) ? $_SESSION['datos_manuales']['ir_anual'] : array());
    $c = (isset($_SESSION['contribuyente']) ? $_SESSION['contribuyente'] : array());
    $anio = (int) ((isset($c['anio']) ? $c['anio'] : date('Y')));

    $ingresos = $tot['ventas']
        + cte_num((isset($irAnual['rendimientos_financieros']) ? $irAnual['rendimientos_financieros'] : 0))
        + cte_num((isset($irAnual['otros_ingresos']) ? $irAnual['otros_ingresos'] : 0));

    $gastosDed = $tot['compras'] + $tot['nomina'] + $tot['iess']
        + cte_num((isset($irAnual['otros_gastos_deducibles']) ? $irAnual['otros_gastos_deducibles'] : 0))
        + cte_num((isset($irAnual['intereses_bancarios']) ? $irAnual['intereses_bancarios'] : 0))
        + cte_num((isset($irAnual['otros_servicios']) ? $irAnual['otros_servicios'] : 0));

    $gastosND = 0;
    foreach ($meses as $dm) {
        $gastosND += cte_num((isset($dm['gastos_no_deducibles']) ? $dm['gastos_no_deducibles'] : 0));
    }

    $utilidad = $ingresos - $gastosDed;
    $participacion = 0; // sociedades
    $base = max(0, $utilidad - $participacion + $gastosND);

    $tabla = (isset($_SESSION['datos_manuales']['tabla_progresiva']) ? $_SESSION['datos_manuales']['tabla_progresiva'] : array());
    if (empty($tabla)) {
        $tabla = cte_tabla_progresiva_default($anio);
    }

    $irCausado = cte_ir_desde_tabla($base, $tabla);
    $gastosPers = cte_num((isset($irAnual['gastos_personales']) ? $irAnual['gastos_personales'] : 0));
    $retenciones = 0;
    if (isset($_SESSION['ct_data']['retenciones_rec'])) {
        foreach ($_SESSION['ct_data']['retenciones_rec'] as $mesNum => $items) {
            foreach ($items as $clave => $info) {
                if (isset($info['tipo']) && $info['tipo'] === 'RENTA') {
                    $retenciones += (float)$info['valorRetenido'];
                }
            }
        }
    }
    $ctAnterior = cte_num((isset($irAnual['credito_tributario_anterior']) ? $irAnual['credito_tributario_anterior'] : 0));

    $irPagar = $irCausado - $gastosPers - $retenciones - $ctAnterior;
    $creditoSiguiente = $irPagar < 0 ? abs($irPagar) : 0;
    $irPagar = max(0, $irPagar);

    return array(
        'ingresos' => $ingresos,
        'gastos_deducibles' => $gastosDed,
        'utilidad' => $utilidad,
        'participacion' => $participacion,
        'gastos_no_deducibles' => $gastosND,
        'base_imponible' => $base,
        'ir_causado' => $irCausado,
        'gastos_personales' => $gastosPers,
        'retenciones' => $retenciones,
        'credito_anterior' => $ctAnterior,
        'ir_a_pagar' => $irPagar,
        'credito_siguiente_anio' => $creditoSiguiente,
        'tabla_progresiva' => $tabla,
    );
}
