<?php
/**
 * Cálculos IVA — Formulario 104
 */

function cte_calcular_iva_mes(array $d) {
    $causado = cte_num((isset($d['601']) ? $d['601'] : 0));
    $credito = cte_num((isset($d['564']) ? $d['564'] : 0));
    $retRec = cte_num((isset($d['609']) ? $d['609'] : 0));
    $retEmit = cte_num((isset($d['ret_iva_emitidas']) ? $d['ret_iva_emitidas'] : 0));
    $totalPagado = cte_num((isset($d['999']) ? $d['999'] : 0));

    $ivaPagar = max(0, $causado - $credito - $retRec - $retEmit);

    return array(
        'iva_causado' => $causado,
        'credito_tributario' => $credito,
        'retenciones_iva' => $retRec + $retEmit,
        'iva_a_pagar' => $ivaPagar,
        'sin_valor_pagar' => $totalPagado == 0 && $causado <= $credito,
        'saldo_ct_proximo' => cte_num((isset($d['617']) ? $d['617'] : 0)),
    );
}

/** Encadenamiento 483/485 y 617→606 entre meses */
function cte_validar_encadenamiento_iva(array $meses) {
    $alertas = array();
    for ($m = 2; $m <= 12; $m++) {
        $ant = (isset($meses[$m - 1]) ? $meses[$m - 1] : array());
        $act = (isset($meses[$m]) ? $meses[$m] : array());
        $f485Ant = cte_num((isset($ant['485']) ? $ant['485'] : 0));
        $f483Act = cte_num((isset($act['483']) ? $act['483'] : 0));
        if ($f485Ant > 0 && abs($f485Ant - $f483Act) > 0.02) {
            $alertas[] = "Mes {$m}: campo 483 ({$f483Act}) difiere de 485 mes anterior ({$f485Ant})";
        }
        $f617Ant = cte_num((isset($ant['617']) ? $ant['617'] : 0));
        $f606Act = cte_num((isset($act['606']) ? $act['606'] : 0));
        if ($f617Ant > 0 && abs($f617Ant - $f606Act) > 0.02) {
            $alertas[] = "Mes {$m}: campo 606 debería ser 617 del mes anterior ({$f617Ant})";
        }
    }
    return $alertas;
}

function cte_totales_anuales_iva(array $meses) {
    $t = array('ventas' => 0, 'compras' => 0, 'iva_causado' => 0, 'credito_tributario' => 0, 'iva_a_pagar' => 0, 'iva_pagado' => 0, 'nomina' => 0, 'iess' => 0);
    foreach ($meses as $dm) {
        $t['ventas'] += $dm['ventas'];
        $t['compras'] += $dm['compras'];
        $t['iva_causado'] += $dm['iva_causado'];
        $t['credito_tributario'] += $dm['credito_tributario'];
        $t['iva_a_pagar'] += $dm['iva_a_pagar'];
        $t['iva_pagado'] += cte_num((isset($dm['999']) ? $dm['999'] : 0));
        $t['nomina'] += $dm['nomina'];
        $t['iess'] += $dm['iess'];
    }
    return $t;
}
