<?php
/**
 * Funciones auxiliares — Control Tributario EC
 */

$GLOBALS['CTE_MESES'] = array(
    1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
    5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
    9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
);

$GLOBALS['CTE_MESES_CORTO'] = array(
    1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
    7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
);

function cte_h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function cte_num($v) {
    if ($v === null || $v === '') {
        return 0.0;
    }
    if (is_string($v)) {
        $v = str_replace(array('$', ' ', ','), array('', '', '.'), $v);
        $v = preg_replace('/\.(?=.*\.)/', '', $v); // quitar miles si hay doble punto
    }
    return (float) $v;
}

function cte_format_money($n) {
    return number_format((float) $n, 2, '.', ',');
}

/** Valida RUC ecuatoriano (13 dígitos, módulo 11) */
function cte_validar_ruc($ruc) {
    $ruc = preg_replace('/\D/', '', $ruc);
    if (strlen($ruc) !== 13) {
        return false;
    }
    if (!ctype_digit($ruc)) {
        return false;
    }
    $coef = array(2, 1, 2, 1, 2, 1, 2, 1, 2);
    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $v = (int) $ruc[$i] * $coef[$i];
        if ($v >= 10) {
            $v -= 9;
        }
        $suma += $v;
    }
    $dig = (10 - ($suma % 10)) % 10;
    return (int) $ruc[9] === $dig;
}

/** Noveno dígito del RUC (índice 8, base 1) */
function cte_noveno_digito_ruc($ruc) {
    $ruc = preg_replace('/\D/', '', $ruc);
    if (strlen($ruc) < 9) {
        return 0;
    }
    return (int) $ruc[8];
}

/** Convierte período texto SRI a número de mes */
function cte_periodo_a_mes($texto, $anioRef = null) {
    $t = mb_strtoupper(trim($texto), 'UTF-8');
    foreach ($GLOBALS['CTE_MESES'] as $num => $nombre) {
        if (strpos($t, $nombre) !== false) {
            return $num;
        }
    }
    if (preg_match('/(\d{4})-(\d{2})/', $texto, $m)) {
        return (int) $m[2];
    }
    if (preg_match('/(\d{2})\/(\d{4})/', $texto, $m)) {
        return (int) $m[1];
    }
    return null;
}

function cte_anio_contribuyente() {
    return (int) ((isset($_SESSION['contribuyente']['anio']) ? $_SESSION['contribuyente']['anio'] : date('Y')));
}

/** Mes consolidado con declaración + manual + IESS */
function cte_datos_mes($mes) {
    $decl = (isset($_SESSION['declaraciones'][$mes]) ? $_SESSION['declaraciones'][$mes] : array());
    $manual = (isset($_SESSION['datos_manuales']['meses'][$mes]) ? $_SESSION['datos_manuales']['meses'][$mes] : array());
    $iessMes = cte_iess_resumen_mes($mes);

    $f411 = cte_num((isset($decl['411']) ? $decl['411'] : 0));
    $f421 = cte_num((isset($decl['421']) ? $decl['421'] : 0));
    $f422 = cte_num((isset($decl['422']) ? $decl['422'] : 0));
    $f423 = cte_num((isset($decl['423']) ? $decl['423'] : 0));
    $f424 = cte_num((isset($decl['424']) ? $decl['424'] : 0));
    $f425 = cte_num((isset($decl['425']) ? $decl['425'] : 0));
    $f426 = cte_num((isset($decl['426']) ? $decl['426'] : 0));
    $f427 = cte_num((isset($decl['427']) ? $decl['427'] : 0));
    $f428 = cte_num((isset($decl['428']) ? $decl['428'] : 0));
    $f429 = cte_num((isset($decl['429']) ? $decl['429'] : 0));
    $f403 = cte_num((isset($decl['403']) ? $decl['403'] : (isset($decl['413']) ? $decl['413'] : 0)));
    $f510 = cte_num((isset($decl['510']) ? $decl['510'] : 0));
    $f529 = cte_num((isset($decl['529']) ? $decl['529'] : 0));
    $f564 = cte_num((isset($decl['564']) ? $decl['564'] : 0));
    $f601 = cte_num((isset($decl['601']) ? $decl['601'] : 0));
    $f609 = cte_num((isset($decl['609']) ? $decl['609'] : 0));
    $f617 = cte_num((isset($decl['617']) ? $decl['617'] : 0));
    $f999 = cte_num((isset($decl['999']) ? $decl['999'] : 0));
    $f483 = cte_num((isset($decl['483']) ? $decl['483'] : 0));
    $f485 = cte_num((isset($decl['485']) ? $decl['485'] : 0));

    $ventas = $f411 + cte_num((isset($manual['ventas_0']) ? $manual['ventas_0'] : 0)) - cte_num((isset($manual['nc_ventas_15']) ? $manual['nc_ventas_15'] : 0));
    $f500_sum = cte_num(isset($decl['500'])?$decl['500']:0) + cte_num(isset($decl['501'])?$decl['501']:0) + cte_num(isset($decl['502'])?$decl['502']:0) + cte_num(isset($decl['503'])?$decl['503']:0) + cte_num(isset($decl['504'])?$decl['504']:0) + cte_num(isset($decl['505'])?$decl['505']:0);
    $f510_sum = cte_num(isset($decl['510'])?$decl['510']:0) + cte_num(isset($decl['511'])?$decl['511']:0) + cte_num(isset($decl['512'])?$decl['512']:0) + cte_num(isset($decl['513'])?$decl['513']:0) + cte_num(isset($decl['514'])?$decl['514']:0) + cte_num(isset($decl['515'])?$decl['515']:0);
    $f500 = $f500_sum;
    
    $f507_sum = cte_num(isset($decl['506'])?$decl['506']:0) + cte_num(isset($decl['507'])?$decl['507']:0);
    $f517_sum = cte_num(isset($decl['516'])?$decl['516']:0) + cte_num(isset($decl['517'])?$decl['517']:0);
    $f507 = $f507_sum;
    
    $f508 = cte_num((isset($decl['508']) ? $decl['508'] : 0));
    $nc_c_15 = cte_num((isset($manual['nc_c_15']) ? $manual['nc_c_15'] : 0));
    $nc_c_0 = cte_num((isset($manual['nc_c_0_rise']) ? $manual['nc_c_0_rise'] : 0));
    $m_comp0 = cte_num((isset($manual['compras_0']) ? $manual['compras_0'] : 0));
    $m_act_fij = cte_num((isset($manual['activos_fijos']) ? $manual['activos_fijos'] : 0));
    $m_import = cte_num((isset($manual['importaciones']) ? $manual['importaciones'] : 0));

    $compras = $f500 + $f507 + $f508 + $m_comp0 + $m_act_fij + $m_import - $nc_c_15 - $nc_c_0;

    $retIvaEmit = cte_num((isset($manual['ret_iva_20']) ? $manual['ret_iva_20'] : 0)) + cte_num((isset($manual['ret_iva_30']) ? $manual['ret_iva_30'] : 0))
        + cte_num((isset($manual['ret_iva_70']) ? $manual['ret_iva_70'] : 0)) + cte_num((isset($manual['ret_iva_100']) ? $manual['ret_iva_100'] : 0));

    $iva = cte_calcular_iva_mes(array(
        '601' => $f601,
        '564' => $f564,
        '609' => $f609,
        '617' => $f617,
        '999' => $f999,
        'ret_iva_emitidas' => $retIvaEmit,
    ));

    $retIr = 0;
    foreach (array('303', '303a', '304', '307', '310', '312', '322', '332', '343', '344', '346') as $c) {
        $retIr += cte_num((isset($manual['ret_ir_' . $c]) ? $manual['ret_ir_' . $c] : 0));
    }

    $nomina = (isset($iessMes['total_sueldos']) ? $iessMes['total_sueldos'] : 0);
    $iessTotal = (isset($iessMes['total_aportes']) ? $iessMes['total_aportes'] : 0);

    return array_merge($decl, $manual, array(
        'mes' => $mes,
        'mes_label' => isset($GLOBALS['CTE_MESES'][$mes]) ? $GLOBALS['CTE_MESES'][$mes] : '',
        '411' => $f411, '421' => $f421, '422' => $f422, '423' => $f423, '424' => $f424, '425' => $f425, '426' => $f426, '427' => $f427, '428' => $f428, '429' => $f429, '403' => $f403,
        '510' => $f510, '529' => $f529, '564' => $f564,
        '601' => $f601, '609' => $f609, '617' => $f617, '999' => $f999,
        '483' => $f483, '485' => $f485,
        'ventas' => $ventas,
        'compras' => $compras,
        'iva_causado' => $iva['iva_causado'],
        'credito_tributario' => $iva['credito_tributario'],
        'iva_a_pagar' => $iva['iva_a_pagar'],
        'sin_valor_pagar' => $iva['sin_valor_pagar'],
        'nomina' => $nomina,
        'iess' => $iessTotal,
        'ret_ir_total' => $retIr,
        'estado_f104' => (isset($decl['estado']) ? $decl['estado'] : ''),
        'presentado_f104' => !empty($decl['fecha_recaudacion']) || !empty($decl['numero_serie']),
        'presentado_f103' => !empty($manual['form_103_presentado']),
        'presentado_ats' => !empty($manual['ats_presentado']),
        'presentado_iess' => ((isset($iessMes['empleados']) ? $iessMes['empleados'] : 0)) > 0,
    ));
}

function cte_todos_meses() {
    $out = array();
    for ($m = 1; $m <= 12; $m++) {
        $out[$m] = cte_datos_mes($m);
    }
    return $out;
}

function cte_redirect($paso, $msg = null, $tipo = 'ok') {
    $q = http_build_query(array_filter(array('paso' => $paso, 'msg' => $msg, 'tipo' => $tipo)));
    header('Location: index.php?' . $q);
    exit;
}

function cte_flash() {
    if (!empty($_GET['msg'])) {
        return array('tipo' => (isset($_GET['tipo']) ? $_GET['tipo'] : 'ok'), 'texto' => $_GET['msg']);
    }
    return null;
}

/** Columnas maestras para Excel (estructura Guido Espinoza) */
function cte_columnas_maestras() {
    return array(
        array('grupo1' => 'FORM. 104 VENTAS', 'grupo2' => 'VENTAS', 'cols' => array(
            array('key' => '401', 'label' => 'Ventas brutas 15%', 'campo' => '401'),
            array('key' => '403', 'label' => 'Ventas brutas 0%', 'campo' => '403'),
            array('key' => 'nc_15', 'label' => 'Notas Crédito 15%', 'campo' => ''),
            array('key' => 'nc_0', 'label' => 'Notas Crédito 0%', 'campo' => ''),
            array('key' => '429', 'label' => 'TOTAL IVA GENERADO', 'campo' => '429'),
            array('key' => 'ventas_0', 'label' => 'Ventas 0% manual', 'campo' => ''),
        )),
        array('grupo1' => 'FORM. 104 COMPRAS', 'grupo2' => 'COMPRAS', 'cols' => array(
            array('key' => 'c_500', 'label' => 'TARIFA 15%', 'campo' => '500'),
            array('key' => 'c_507_508', 'label' => 'TARIFA 0%', 'campo' => '507+508'),
            array('key' => 'nc_c_15', 'label' => 'N/C 15%', 'campo' => ''),
            array('key' => 'nc_c_0_rise', 'label' => 'N/C 0%', 'campo' => ''),
            array('key' => 'c_529', 'label' => 'I.V.A.', 'campo' => '529'),
            array('key' => 'tot_c', 'label' => 'TOTAL NETO', 'campo' => ''),
        )),
        array('grupo1' => 'RESULTADO', 'grupo2' => 'RESULTADO', 'cols' => array(
            array('key' => 'v_c', 'label' => 'V - C', 'campo' => ''),
        )),
        array('grupo1' => 'RET. IVA', 'grupo2' => 'RECIBIDAS', 'cols' => array(
            array('key' => '609', 'label' => 'Ret. IVA recibidas', 'campo' => '609'),
            array('key' => '617', 'label' => 'Saldo CT próx. mes', 'campo' => '617'),
        )),
        array('grupo1' => 'FORM. 103 RET. IR', 'grupo2' => 'EMITIDAS', 'cols' => array(
            array('key' => 'ret_ir_total', 'label' => 'Total ret. IR', 'campo' => ''),
        )),
        array('grupo1' => 'OTROS GASTOS/NÓMINA', 'grupo2' => 'NÓMINA', 'cols' => array(
            array('key' => 'nomina', 'label' => 'Sueldos', 'campo' => ''),
            array('key' => 'iess', 'label' => 'IESS', 'campo' => ''),
            array('key' => 'gastos_no_deducibles', 'label' => 'Gastos ND', 'campo' => ''),
        )),
        array('grupo1' => 'IVA CAUSADO', 'grupo2' => 'LIQUIDACIÓN', 'cols' => array(
            array('key' => '601', 'label' => 'Impuesto causado', 'campo' => '601'),
            array('key' => 'iva_a_pagar', 'label' => 'IVA a pagar', 'campo' => ''),
            array('key' => '999', 'label' => 'Total pagado', 'campo' => '999'),
        )),
        array('grupo1' => 'RESULTADO', 'grupo2' => 'RESUMEN', 'cols' => array(
            array('key' => 'ventas', 'label' => 'Total ventas', 'campo' => ''),
            array('key' => 'compras', 'label' => 'Total compras', 'campo' => ''),
        )),
    );
}

function cte_valor_columna_maestra(array $datosMes, $key) {
    $c_500_sum = cte_num(isset($datosMes['500'])?$datosMes['500']:0) + cte_num(isset($datosMes['501'])?$datosMes['501']:0) + cte_num(isset($datosMes['502'])?$datosMes['502']:0) + cte_num(isset($datosMes['503'])?$datosMes['503']:0) + cte_num(isset($datosMes['504'])?$datosMes['504']:0) + cte_num(isset($datosMes['505'])?$datosMes['505']:0);
    $c_510_sum = cte_num(isset($datosMes['510'])?$datosMes['510']:0) + cte_num(isset($datosMes['511'])?$datosMes['511']:0) + cte_num(isset($datosMes['512'])?$datosMes['512']:0) + cte_num(isset($datosMes['513'])?$datosMes['513']:0) + cte_num(isset($datosMes['514'])?$datosMes['514']:0) + cte_num(isset($datosMes['515'])?$datosMes['515']:0);
    $c_500 = $c_500_sum;
    $c_510 = $c_510_sum;
    
    $c_507_sum = cte_num(isset($datosMes['506'])?$datosMes['506']:0) + cte_num(isset($datosMes['507'])?$datosMes['507']:0);
    $c_517_sum = cte_num(isset($datosMes['516'])?$datosMes['516']:0) + cte_num(isset($datosMes['517'])?$datosMes['517']:0);
    $c_507 = $c_507_sum;
    $c_517 = $c_517_sum;

    $c_508 = cte_num(isset($datosMes['508'])?$datosMes['508']:0);
    $c_518 = cte_num(isset($datosMes['518'])?$datosMes['518']:0);
    if ($c_518 == 0 && $c_508 > 0) $c_518 = $c_508;

    $c_529_sum = cte_num(isset($datosMes['520'])?$datosMes['520']:0) + cte_num(isset($datosMes['521'])?$datosMes['521']:0) + cte_num(isset($datosMes['522'])?$datosMes['522']:0) + cte_num(isset($datosMes['523'])?$datosMes['523']:0) + cte_num(isset($datosMes['524'])?$datosMes['524']:0) + cte_num(isset($datosMes['525'])?$datosMes['525']:0);
    $c_529 = cte_num(isset($datosMes['529'])?$datosMes['529']:0);
    if ($c_529 == 0 && $c_529_sum > 0) $c_529 = $c_529_sum;

    if ($key === 'c_500') return $c_500;
    if ($key === 'c_507_508') return $c_507 + $c_508;
    if ($key === 'nc_c_15') {
        return $c_500 - $c_510;
    }
    if ($key === 'nc_c_0_rise') {
        return ($c_507 + $c_508) - ($c_517 + $c_518);
    }
    if ($key === 'c_529') return $c_529;
    if ($key === 'tot_c') {
        return $c_510 + $c_517 + $c_518;
    }
    if ($key === 'v_c') {
        $tot_v = cte_num(isset($datosMes['411']) ? $datosMes['411'] : 0) + cte_num(isset($datosMes['413']) ? $datosMes['413'] : 0) + cte_num(isset($datosMes['415']) ? $datosMes['415'] : 0);
        return $tot_v - ($c_510 + $c_517 + $c_518);
    }
    
    return cte_num((isset($datosMes[$key]) ? $datosMes[$key] : 0));
}
