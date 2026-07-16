<?php
// ajax/recalcular.php
error_reporting(0);
session_start();
header('Content-Type: application/json');
file_put_contents('debug_recalc.log', date('Y-m-d H:i:s') . ' SESSION NAME: ' . (isset($_SESSION['ct_nombre']) ? $_SESSION['ct_nombre'] : 'NOT SET') . "\n", FILE_APPEND);

$anio = isset($_SESSION['ct_anio']) ? $_SESSION['ct_anio'] : '2026';
$regimen = isset($_SESSION['ct_regimen']) ? $_SESSION['ct_regimen'] : 'pn';
$params = isset($_SESSION['ct_parametros']) ? $_SESSION['ct_parametros'] : include '../config/parametros.php';

// Actualizar session si vinieron por POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $raw = file_get_contents("php://input");
    $req = json_decode($raw, true);
    if (isset($req['anio'])) { $anio = $req['anio']; $_SESSION['ct_anio'] = $anio; }
    if (isset($req['regimen'])) { $regimen = $req['regimen']; $_SESSION['ct_regimen'] = $regimen; }
    if (isset($req['params'])) { $params = $req['params']; $_SESSION['ct_parametros'] = $params; }
    if (array_key_exists('gastos_personales', $req)) { if ($req['gastos_personales'] === null) unset($_SESSION['ct_gastos_personales']); else $_SESSION['ct_gastos_personales'] = floatval($req['gastos_personales']); }
    if (isset($req['gastos_personales_decl'])) { $_SESSION['ct_gastos_personales_decl'] = floatval($req['gastos_personales_decl']); }
    if (array_key_exists('gastos_adicionales', $req)) { if ($req['gastos_adicionales'] === null) unset($_SESSION['ct_gastos_adicionales']); else $_SESSION['ct_gastos_adicionales'] = floatval($req['gastos_adicionales']); }
    if (array_key_exists('rendimientos', $req)) { if ($req['rendimientos'] === null) unset($_SESSION['ct_rendimientos']); else $_SESSION['ct_rendimientos'] = floatval($req['rendimientos']); }
    if (isset($req['rendimientos_decl'])) { $_SESSION['ct_rendimientos_decl'] = floatval($req['rendimientos_decl']); }
    if (array_key_exists('sueldo_107', $req)) { if ($req['sueldo_107'] === null) unset($_SESSION['ct_sueldo_107']); else $_SESSION['ct_sueldo_107'] = floatval($req['sueldo_107']); }
    if (isset($req['sueldo_107_decl'])) { $_SESSION['ct_sueldo_107_decl'] = floatval($req['sueldo_107_decl']); }
    if (array_key_exists('depreciacion', $req)) { if ($req['depreciacion'] === null) unset($_SESSION['ct_depreciacion']); else $_SESSION['ct_depreciacion'] = floatval($req['depreciacion']); }
    if (isset($req['depreciacion_decl'])) { $_SESSION['ct_depreciacion_decl'] = floatval($req['depreciacion_decl']); }
    if (array_key_exists('gastos_nd', $req)) { if ($req['gastos_nd'] === null) unset($_SESSION['ct_gastos_nd']); else $_SESSION['ct_gastos_nd'] = floatval($req['gastos_nd']); }
    if (isset($req['gastos_nd_decl'])) { $_SESSION['ct_gastos_nd_decl'] = floatval($req['gastos_nd_decl']); }
    if (isset($req['gastos_adicionales_decl'])) { $_SESSION['ct_gastos_adicionales_decl'] = floatval($req['gastos_adicionales_decl']); }
    if (array_key_exists('credito_anterior', $req)) { if ($req['credito_anterior'] === null) unset($_SESSION['ct_credito_anterior']); else $_SESSION['ct_credito_anterior'] = floatval($req['credito_anterior']); }
    if (isset($req['credito_anterior_decl'])) { $_SESSION['ct_credito_anterior_decl'] = floatval($req['credito_anterior_decl']); }
    if (array_key_exists('anticipo_pagado', $req)) { if ($req['anticipo_pagado'] === null) unset($_SESSION['ct_anticipo_pagado']); else $_SESSION['ct_anticipo_pagado'] = floatval($req['anticipo_pagado']); }
    if (isset($req['anticipo_pagado_decl'])) { $_SESSION['ct_anticipo_pagado_decl'] = floatval($req['anticipo_pagado_decl']); }
    if (array_key_exists('perdida_amortizable', $req)) { if ($req['perdida_amortizable'] === null) unset($_SESSION['ct_perdida_amortizable']); else $_SESSION['ct_perdida_amortizable'] = floatval($req['perdida_amortizable']); }
    if (isset($req['perdida_amortizable_decl'])) { $_SESSION['ct_perdida_amortizable_decl'] = floatval($req['perdida_amortizable_decl']); }
    if (isset($req['ruc']) && !empty($req['ruc'])) { $_SESSION['ct_ruc'] = $req['ruc']; }
    if (isset($req['nombre']) && !empty($req['nombre'])) { $_SESSION['ct_nombre'] = $req['nombre']; }
    
    if (array_key_exists('ventas_estimado', $req)) { if ($req['ventas_estimado'] === null) unset($_SESSION['ct_ventas_estimado']); else $_SESSION['ct_ventas_estimado'] = floatval($req['ventas_estimado']); }
    if (array_key_exists('compras_estimado', $req)) { if ($req['compras_estimado'] === null) unset($_SESSION['ct_compras_estimado']); else $_SESSION['ct_compras_estimado'] = floatval($req['compras_estimado']); }
    if (array_key_exists('sueldos_estimado', $req)) { if ($req['sueldos_estimado'] === null) unset($_SESSION['ct_sueldos_estimado']); else $_SESSION['ct_sueldos_estimado'] = floatval($req['sueldos_estimado']); }
    if (array_key_exists('seguridad_social_estimado', $req)) { if ($req['seguridad_social_estimado'] === null) unset($_SESSION['ct_seguridad_social_estimado']); else $_SESSION['ct_seguridad_social_estimado'] = floatval($req['seguridad_social_estimado']); }
    if (array_key_exists('ret_recibidas_estimado', $req)) { if ($req['ret_recibidas_estimado'] === null) unset($_SESSION['ct_ret_recibidas_estimado']); else $_SESSION['ct_ret_recibidas_estimado'] = floatval($req['ret_recibidas_estimado']); }
}

$gastos_personales_decl = isset($_SESSION['ct_gastos_personales_decl']) ? $_SESSION['ct_gastos_personales_decl'] : 0;
$gastos_personales = isset($_SESSION['ct_gastos_personales']) ? $_SESSION['ct_gastos_personales'] : $gastos_personales_decl;

$gastos_adicionales_decl = isset($_SESSION['ct_gastos_adicionales_decl']) ? $_SESSION['ct_gastos_adicionales_decl'] : 0;
$gastos_adicionales = isset($_SESSION['ct_gastos_adicionales']) ? $_SESSION['ct_gastos_adicionales'] : $gastos_adicionales_decl;

$rendimientos_decl = isset($_SESSION['ct_rendimientos_decl']) ? $_SESSION['ct_rendimientos_decl'] : 0;
$rendimientos = isset($_SESSION['ct_rendimientos']) ? $_SESSION['ct_rendimientos'] : $rendimientos_decl;

$sueldo_107_decl = isset($_SESSION['ct_sueldo_107_decl']) ? $_SESSION['ct_sueldo_107_decl'] : 0;
$sueldo_107 = isset($_SESSION['ct_sueldo_107']) ? $_SESSION['ct_sueldo_107'] : $sueldo_107_decl;

$depreciacion_decl = isset($_SESSION['ct_depreciacion_decl']) ? $_SESSION['ct_depreciacion_decl'] : 0;
$depreciacion = isset($_SESSION['ct_depreciacion']) ? $_SESSION['ct_depreciacion'] : $depreciacion_decl;

$gastos_nd_decl = isset($_SESSION['ct_gastos_nd_decl']) ? $_SESSION['ct_gastos_nd_decl'] : 0;
$gastos_nd = isset($_SESSION['ct_gastos_nd']) ? $_SESSION['ct_gastos_nd'] : $gastos_nd_decl;

$credito_anterior_decl = isset($_SESSION['ct_credito_anterior_decl']) ? $_SESSION['ct_credito_anterior_decl'] : 0;
$credito_anterior = isset($_SESSION['ct_credito_anterior']) ? $_SESSION['ct_credito_anterior'] : $credito_anterior_decl;

$anticipo_pagado_decl = isset($_SESSION['ct_anticipo_pagado_decl']) ? $_SESSION['ct_anticipo_pagado_decl'] : 0;
$anticipo_pagado = isset($_SESSION['ct_anticipo_pagado']) ? $_SESSION['ct_anticipo_pagado'] : $anticipo_pagado_decl;

// --- Auto-fill from F101 if no manual override ---
// cas.870 Saldo a favor → crédito tributario año anterior (= cas.861 nuevo F101)
$data = isset($_SESSION['ct_data']) ? $_SESSION['ct_data'] : array('104'=>array(), '103'=>array(), 'iess'=>array());
$f101campos = (isset($data['renta']) && is_array($data['renta'])) ? (isset($data['renta']['campos']) ? $data['renta']['campos'] : (isset($data['renta']['datos']) ? $data['renta']['datos'] : array())) : array();
$f101_credito  = isset($f101campos['870']) ? floatval($f101campos['870']) : (isset($f101campos['861']) ? floatval($f101campos['861']) : 0);
$f101_anticipo = isset($f101campos['871']) ? floatval($f101campos['871']) : 0;
$f101_perdida  = isset($f101campos['837']) ? floatval($f101campos['837']) : 0;

$perdida_amortizable_decl = isset($_SESSION['ct_perdida_amortizable_decl']) ? $_SESSION['ct_perdida_amortizable_decl'] : $f101_perdida;
$perdida_amortizable = isset($_SESSION['ct_perdida_amortizable']) ? $_SESSION['ct_perdida_amortizable'] : $perdida_amortizable_decl;

$meses_nombres = array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');

$maestra = array();
$totales = array(
    'v_401' => 0, 'v_403' => 0, 'nc_15' => 0, 'nc_0' => 0, 'v_429' => 0, 'tot_v' => 0,
    'c_500' => 0, 'c_510' => 0, 'nc_c_15' => 0, 'c_507' => 0, 'c_517' => 0, 'nc_c_0' => 0, 'c_508' => 0, 'c_518' => 0, 'nc_c_rise' => 0, 'c_529' => 0, 'tot_c' => 0,
    'l_601' => 0, 'l_606' => 0, 'l_617' => 0, 'l_485' => 0,
    'l_902' => 0, 'l_903' => 0, 'l_904' => 0, 'l_999' => 0,
    'l_721' => 0, 'l_723' => 0, 'l_725' => 0, 'l_727' => 0, 'l_729' => 0, 'l_731' => 0, 'l_799' => 0, 'l_801' => 0,
    'ret_20' => 0, 'ret_50' => 0, 'ret_70' => 0, 'ret_100' => 0, 'tot_ret_iva' => 0,
    'n_bruta' => 0, 'n_pat' => 0, 'n_ind' => 0, 'n_ccc' => 0, 'n_prov1314' => 0, 'n_vac' => 0,
    'v_c' => 0, 'tot_pag' => 0, 'tot_f103' => 0
);

$f103_cols = array();
if (in_array($regimen, array('pn', 'soc'))) {
    if (isset($data['103'])) {
        foreach($data['103'] as $m => $d) {
            if (is_array($d)) {
                foreach($d as $k => $v) {
                    // Validar estrictamente que sea un código de casillero (inicia con número o es numérico) y excluir metadatos
                    if (preg_match('/^[0-9]/', (string)$k) && is_numeric($v) && $v > 0 && !in_array($k, $f103_cols) && $k !== 'total' && $k !== '999') {
                        $f103_cols[] = $k;
                    }
                }
            }
        }
    }
}
sort($f103_cols);

for ($i = 1; $i <= 12; $i++) {
    $f104 = isset($data['104'][$i]) ? $data['104'][$i] : array();
    $f103 = isset($data['103'][$i]) ? $data['103'][$i] : array();
    
    $iess = isset($data['iess'][$i]) ? $data['iess'][$i] : array();
    
        $v_401 = isset($f104['401']) ? $f104['401'] : 0;
        $v_411 = isset($f104['411']) ? $f104['411'] : 0;
        $v_403 = isset($f104['403']) ? $f104['403'] : 0;
        $v_413 = isset($f104['413']) ? $f104['413'] : 0;
        $v_405 = isset($f104['405']) ? $f104['405'] : 0;
        $v_415 = isset($f104['415']) ? $f104['415'] : 0;

        $c_500_sum = (isset($f104['500'])?$f104['500']:0) + (isset($f104['501'])?$f104['501']:0) + (isset($f104['502'])?$f104['502']:0) + (isset($f104['503'])?$f104['503']:0) + (isset($f104['504'])?$f104['504']:0) + (isset($f104['505'])?$f104['505']:0);
        $c_510_sum = (isset($f104['510'])?$f104['510']:0) + (isset($f104['511'])?$f104['511']:0) + (isset($f104['512'])?$f104['512']:0) + (isset($f104['513'])?$f104['513']:0) + (isset($f104['514'])?$f104['514']:0) + (isset($f104['515'])?$f104['515']:0);
        $c_507_sum = (isset($f104['506'])?$f104['506']:0) + (isset($f104['507'])?$f104['507']:0);
        $c_517_sum = (isset($f104['516'])?$f104['516']:0) + (isset($f104['517'])?$f104['517']:0);
        
        $c_529_sum = (isset($f104['520'])?$f104['520']:0) + (isset($f104['521'])?$f104['521']:0) + (isset($f104['522'])?$f104['522']:0) + (isset($f104['523'])?$f104['523']:0) + (isset($f104['524'])?$f104['524']:0) + (isset($f104['525'])?$f104['525']:0);

        $row = array(
            'mes' => $i,
            'mes_nombre' => $meses_nombres[$i-1],
            'v_401' => $v_401,
            'v_403' => $v_403 + $v_405,
            'nc_15' => $v_401 - $v_411,
            'nc_0' => ($v_403 + $v_405) - ($v_413 + $v_415),
            'v_429' => isset($f104['429']) ? $f104['429'] : 0,
            'tot_v_netas' => $v_411 + $v_413 + $v_415,
            
            'c_500' => $c_500_sum,
            'c_510' => $c_510_sum,
            'c_507' => $c_507_sum,
            'c_517' => $c_517_sum,
            'c_508' => isset($f104['508']) ? $f104['508'] : 0,
            'c_518' => isset($f104['518']) ? $f104['518'] : (isset($f104['508']) ? $f104['508'] : 0),
            'c_529' => isset($f104['529']) ? $f104['529'] : ($c_529_sum > 0 ? $c_529_sum : 0),
        
        'l_601' => isset($f104['601']) ? $f104['601'] : 0,
        'l_606' => isset($f104['606']) ? $f104['606'] : 0,
        'l_617' => isset($f104['617']) ? $f104['617'] : 0,
        'l_485' => isset($f104['485']) ? $f104['485'] : 0,
        'l_902' => isset($f104['902']) ? $f104['902'] : 0,
        'l_903' => isset($f104['903']) ? $f104['903'] : 0,
        'l_904' => isset($f104['904']) ? $f104['904'] : 0,
        'l_999' => isset($f104['999']) ? $f104['999'] : 0,
        
        'l_721' => isset($f104['721']) ? $f104['721'] : 0,
        'l_723' => isset($f104['723']) ? $f104['723'] : 0,
        'l_725' => isset($f104['725']) ? $f104['725'] : 0,
        'l_727' => isset($f104['727']) ? $f104['727'] : 0,
        'l_729' => isset($f104['729']) ? $f104['729'] : 0,
        'l_731' => isset($f104['731']) ? $f104['731'] : 0,
        'l_799' => isset($f104['799']) ? $f104['799'] : 0,
        'l_801' => isset($f104['801']) ? $f104['801'] : 0,
        
        'ret_20' => 0, 'ret_50' => 0, 'ret_70' => 0, 'ret_100' => 0,
        
        'n_bruta' => isset($iess['n_bruta']) ? $iess['n_bruta'] : 0,
        'n_pat' => isset($iess['n_pat']) ? $iess['n_pat'] : 0,
        'n_ind' => isset($iess['n_ind']) ? $iess['n_ind'] : 0,
        'n_ccc' => isset($iess['n_ccc']) ? $iess['n_ccc'] : 0,
        'n_prov1314' => isset($iess['n_prov1314']) ? $iess['n_prov1314'] : 0,
        'n_vac' => isset($iess['n_vac']) ? $iess['n_vac'] : 0,
        
        'f103' => array(),
        'tot_f103' => 0
    );
    
    $row['tot_v'] = $row['tot_v_netas'];
    $row['nc_c_15'] = $row['c_500'] - $row['c_510'];
    $row['nc_c_0'] = $row['c_507'] - $row['c_517'];
    $row['nc_c_rise'] = $row['c_508'] - $row['c_518'];

    $row['tot_c'] = $row['c_510'] + $row['c_517'] + $row['c_518'];
    $row['tot_ret_iva'] = $row['ret_20'] + $row['ret_50'] + $row['ret_70'] + $row['ret_100'];
    $row['v_c'] = $row['tot_v'] - $row['tot_c'];
    $row['tot_pag'] = $row['l_999'] + $row['n_pat'] + $row['n_ind']; 
    
    foreach($f103_cols as $c) {
        $v = (isset($f103[$c]) && is_numeric($f103[$c])) ? (float)$f103[$c] : 0;
        $row['f103'][$c] = $v;
    }
    $row['tot_f103'] = (isset($f103['total']) && is_numeric($f103['total'])) ? (float)$f103['total'] : 0;
    
    if (empty($f104)) {
        $row['estado'] = 'Falta PDF';
    } else {
        $row['estado'] = 'Cumplida';
        $row['tipo_declaracion'] = isset($f104['tipo_declaracion']) ? $f104['tipo_declaracion'] : 'ORIGINAL';
        $row['fecha_presentacion'] = isset($f104['fecha_presentacion']) ? $f104['fecha_presentacion'] : '';
        $row['numero_serial'] = isset($f104['numero_serial']) ? $f104['numero_serial'] : '';
        $row['codigo_verificador'] = isset($f104['codigo_verificador']) ? $f104['codigo_verificador'] : '';
    }
    
    if (!empty($f103)) {
        $row['f103_tipo_declaracion'] = isset($f103['tipo_declaracion']) ? $f103['tipo_declaracion'] : 'ORIGINAL';
        $row['f103_fecha_presentacion'] = isset($f103['fecha_presentacion']) ? $f103['fecha_presentacion'] : '';
    }
    
    foreach($totales as $k => $v) {
        if(isset($row[$k])) $totales[$k] += $row[$k];
    }
    foreach($f103_cols as $c) {
        if(!isset($totales['f103_'.$c])) $totales['f103_'.$c] = 0;
        $totales['f103_'.$c] += (is_numeric($row['f103'][$c]) ? (float)$row['f103'][$c] : 0);
    }

    $maestra[] = $row;
}

// Retenciones recibidas (Unificadas, columnas dinamicas)
$retenciones_rec = isset($_SESSION['ct_data']['retenciones_rec']) ? $_SESSION['ct_data']['retenciones_rec'] : array();
$columnas_renta = array();
$columnas_iva = array();

foreach ($retenciones_rec as $mes => $datos) {
    foreach ($datos as $col => $info) {
        if ($info['tipo'] == 'RENTA') {
            if (!in_array($col, $columnas_renta)) $columnas_renta[] = $col;
        } elseif ($info['tipo'] == 'IVA') {
            if (!in_array($col, $columnas_iva)) $columnas_iva[] = $col;
        }
    }
}
sort($columnas_renta);
sort($columnas_iva);

$ret_rec_tot = array();
$tot_renta_global = 0;
$tot_iva_global = 0;
$tot_ret_global = 0;

foreach($maestra as $i => &$row) {
    $numMes = $i + 1;
    $row['ret_dinamicas'] = array();
    $row['tot_renta'] = 0;
    $row['tot_iva'] = 0;
    $row['tot_ret_rec'] = 0;
    
    // Renta
    foreach ($columnas_renta as $col) {
        $val = isset($retenciones_rec[$numMes][$col]) ? $retenciones_rec[$numMes][$col]['valorRetenido'] : 0;
        $row['ret_dinamicas'][$col] = $val;
        $row['tot_renta'] += $val;
        if(!isset($ret_rec_tot[$col])) $ret_rec_tot[$col] = 0;
        $ret_rec_tot[$col] += $val;
    }
    
    // Iva
    foreach ($columnas_iva as $col) {
        $val = isset($retenciones_rec[$numMes][$col]) ? $retenciones_rec[$numMes][$col]['valorRetenido'] : 0;
        $row['ret_dinamicas'][$col] = $val;
        $row['tot_iva'] += $val;
        if(!isset($ret_rec_tot[$col])) $ret_rec_tot[$col] = 0;
        $ret_rec_tot[$col] += $val;
    }
    
    $row['tot_ret_rec'] = $row['tot_renta'] + $row['tot_iva'];
    $tot_renta_global += $row['tot_renta'];
    $tot_iva_global += $row['tot_iva'];
    $tot_ret_global += $row['tot_ret_rec'];
}

$totales['tot_iva'] = $tot_iva_global;
$totales['tot_renta'] = $tot_renta_global;

// IR Calculations
  $v_estimado = isset($_SESSION['ct_ventas_estimado']) ? (float)$_SESSION['ct_ventas_estimado'] : $totales['tot_v'];
  file_put_contents('debug.txt', "v_est=$v_estimado, tot_v=" . $totales['tot_v'] . "\n", FILE_APPEND);
  
  $compras_netas_base = ($totales['c_500'] + $totales['c_507'] + $totales['c_508']) - ($totales['nc_c_15'] + $totales['nc_c_0']);
  $c_estimado = isset($_SESSION['ct_compras_estimado']) ? (float)$_SESSION['ct_compras_estimado'] : $compras_netas_base;
  
  $s_estimado = isset($_SESSION['ct_sueldos_estimado']) ? (float)$_SESSION['ct_sueldos_estimado'] : $totales['n_bruta'];
  $ss_estimado = isset($_SESSION['ct_seguridad_social_estimado']) ? (float)$_SESSION['ct_seguridad_social_estimado'] : ($totales['n_pat'] + $totales['n_ccc']);
  $ret_rec_estimado = isset($_SESSION['ct_ret_recibidas_estimado']) ? (float)$_SESSION['ct_ret_recibidas_estimado'] : $tot_renta_global;

  $ir = array(
      'ingresos_411' => $totales['v_401'],
      'ingresos_403' => $totales['v_403'],
      'nc_443' => $totales['nc_15'] + $totales['nc_0'],
      'compras_500' => $totales['c_500'],
      'nc_c_15' => $totales['nc_c_15'],
      'nc_c_0' => $totales['nc_c_0_rise'],
      'compras_510' => $totales['c_510'],
      'compras_508' => $totales['c_508'],
      'compras_507' => $totales['c_507'],
      'sueldos' => $totales['n_bruta'],
      'patronal' => $totales['n_pat'],
      'ccc' => $totales['n_ccc'],
      'ret_recibidas' => $ret_rec_estimado,
      'ret_recibidas_decl' => $tot_renta_global,
      'ventas_estimado' => $v_estimado,
      'compras_estimado' => $c_estimado,
      'sueldos_estimado' => $s_estimado,
      'seguridad_social_estimado' => $ss_estimado
  );

  $ingresos_gravados = $v_estimado + $rendimientos + $sueldo_107;
  $costos_deducibles = $c_estimado + $s_estimado + $ss_estimado + $depreciacion + $gastos_adicionales;
  $utilidad_antes_part = $ingresos_gravados - $costos_deducibles;
  $utilidad = $utilidad_antes_part; // alias

  $base_imponible = 0;
  $ir_causado = 0;
  $calc_details = array();
  
  $participacion_15 = max(0, $utilidad_antes_part * 0.15);

  if ($regimen == 'pn') {
      $base_imponible = max(0, $utilidad_antes_part - $participacion_15 + $gastos_nd - $gastos_personales - $perdida_amortizable);
      $tabla_anio = isset($params['tablas_ir'][$anio]) ? $anio : max(array_keys($params['tablas_ir']));
      $tabla = $params['tablas_ir'][$tabla_anio];
      $ir_causado = 0;
      foreach($tabla as $r) {
          if ($base_imponible > $r[0]) {
              if ($base_imponible <= $r[1]) {
                  $impuesto_fb = $r[2];
                  $excedente = $base_imponible - $r[0];
                  $impuesto_fe = $excedente * $r[3];
                  $ir_causado = $impuesto_fb + $impuesto_fe;
                  $calc_details = array(
                      'tipo' => 'tabla',
                      'fb' => $r[0],
                      'imp_fb' => $impuesto_fb,
                      'fe' => $excedente,
                      'porc' => $r[3],
                      'imp_fe' => $impuesto_fe
                  );
                  break;
              }
          }
      }
  } elseif ($regimen == 'soc') {
      $base_imponible = max(0, $utilidad_antes_part - $participacion_15 + $gastos_nd - $perdida_amortizable);
      $tarifa = isset($params['tarifa_sociedad']) ? $params['tarifa_sociedad'] : 0.25;
      $ir_causado = $base_imponible * $tarifa;
      $calc_details = array(
          'tipo' => 'flat',
          'porc' => $tarifa,
          'imp_fe' => $ir_causado
      );
  } elseif ($regimen == 'rimpe-e') {
    $base_imponible = $totales['tot_v']; 
    $ir_causado = 0;
    
    if (isset($params['tablas_rimpe_e'][$anio])) {
        $tabla = $params['tablas_rimpe_e'][$anio];
    } else {
        $years = array_keys($params['tablas_rimpe_e']);
        rsort($years);
        $tabla = $params['tablas_rimpe_e'][$years[0]];
    }

    if ($base_imponible > 0) {
        foreach($tabla as $r) {
            if ($base_imponible > $r[0] && $base_imponible <= $r[1]) {
                $ir_causado = $r[2] + (($base_imponible - $r[0]) * $r[3]);
                break;
            }
        }
    }
} elseif ($regimen == 'rimpe-np') {
    $base_imponible = $totales['tot_v'];
    $cuotas = $params['cuotas_rimpe_np'];
    foreach($cuotas as $c) {
        if ($base_imponible >= $c[0] && $base_imponible <= $c[1]) {
            $ir_causado = $c[2] * 2; 
            break;
        }
    }
}

// ----------------------------------------------------------------------------------
// CÁLCULO PARA EL ESCENARIO "DECLARADO"
// ----------------------------------------------------------------------------------
$ingresos_gravados_decl = $totales['tot_v'] + $rendimientos_decl + $sueldo_107_decl;
$costos_deducibles_decl = $totales['tot_c'] + $totales['n_bruta'] + $totales['n_pat'] + $totales['n_ccc'] + $depreciacion_decl + $gastos_adicionales_decl;
$utilidad_antes_part_decl = $ingresos_gravados_decl - $costos_deducibles_decl;

$base_imponible_decl = 0;
$ir_causado_decl = 0;
$calc_details_decl = array();
$participacion_15_decl = max(0, $utilidad_antes_part_decl * 0.15);

if ($regimen == 'pn') {
    $base_imponible_decl = max(0, $utilidad_antes_part_decl - $participacion_15_decl + $gastos_nd_decl - $gastos_personales_decl - $perdida_amortizable_decl);
    $tabla_anio = isset($params['tablas_ir'][$anio]) ? $anio : max(array_keys($params['tablas_ir']));
    $tabla = $params['tablas_ir'][$tabla_anio];
    foreach($tabla as $r) {
        if ($base_imponible_decl > $r[0]) {
            if ($base_imponible_decl <= $r[1]) {
                $imp_fb_decl = $r[2];
                $excedente_decl = $base_imponible_decl - $r[0];
                $imp_fe_decl = $excedente_decl * $r[3];
                $ir_causado_decl = $imp_fb_decl + $imp_fe_decl;
                $calc_details_decl = array(
                    'tipo' => 'tabla',
                    'fb' => $r[0],
                    'imp_fb' => $imp_fb_decl,
                    'fe' => $excedente_decl,
                    'porc' => $r[3],
                    'imp_fe' => $imp_fe_decl
                );
                break;
            }
        }
    }
} elseif ($regimen == 'soc') {
    $base_imponible_decl = max(0, $utilidad_antes_part_decl - $participacion_15_decl + $gastos_nd_decl - $perdida_amortizable_decl);
    $tarifa = isset($params['tarifa_sociedad']) ? $params['tarifa_sociedad'] : 0.25;
    $ir_causado_decl = $base_imponible_decl * $tarifa;
    $calc_details_decl = array(
        'tipo' => 'flat',
        'porc' => $tarifa,
        'imp_fe' => $ir_causado_decl
    );
} elseif ($regimen == 'rimpe-e' || $regimen == 'rimpe-np') {
    $base_imponible_decl = $base_imponible;
    $ir_causado_decl = $ir_causado;
    $calc_details_decl = $calc_details;
}
// ----------------------------------------------------------------------------------

$kpi_maestra = array(
    'ventas' => $totales['tot_v'],
    'pagado' => $totales['tot_pag'],
    'iva_causado' => $totales['l_601'],
    'nomina' => $totales['n_bruta'],
    'iva_pend' => isset($maestra[11]['l_485']) ? $maestra[11]['l_485'] : 0,
    'ruc' => isset($_SESSION['ruc']) ? $_SESSION['ruc'] : null
);

$meses_cargados = 0;
$chips_104 = array();
$chips_103 = array();
$chips_iess = array();
$chips_renta = array();
$chips_retenciones = array();

if (isset($data['104'])) {
    ksort($data['104']);
    foreach ($data['104'] as $m => $v) {
        if (!empty($v)) {
            $meses_cargados++;
            $tipo_decl = isset($v['tipo_declaracion']) ? $v['tipo_declaracion'] : 'ORIGINAL';
            $sust_text = $tipo_decl == 'SUSTITUTIVA' ? ' [SUST]' : ' [ORIG]';
            $chips_104[] = $meses_nombres[$m-1] . $sust_text;
        }
    }
}
if (isset($data['103'])) {
    ksort($data['103']);
    foreach ($data['103'] as $m => $v) {
        if (!empty($v) && isset($meses_nombres[$m-1])) {
            $tipo_decl = isset($v['tipo_declaracion']) ? $v['tipo_declaracion'] : 'ORIGINAL';
            $sust_text = $tipo_decl == 'SUSTITUTIVA' ? ' [SUST]' : ' [ORIG]';
            $chips_103[] = $meses_nombres[$m-1] . $sust_text;
        }
    }
}
if (isset($data['iess'])) {
    ksort($data['iess']);
    foreach ($data['iess'] as $m => $v) {
        if (!empty($v)) {
            $chips_iess[] = (isset($meses_nombres[$m-1]) ? $meses_nombres[$m-1] : 'Planilla');
        }
    }
}
if (isset($data['renta']) && is_array($data['renta']) && isset($data['renta']['status']) && $data['renta']['status'] == 'ok') {
    $tipo_form = isset($data['renta']['tipo_formulario']) ? $data['renta']['tipo_formulario'] : '102';
    $anio_form = isset($data['renta']['anio']) && $data['renta']['anio'] > 0 ? $data['renta']['anio'] : '';
    
    $tipo_decl = isset($data['renta']['tipo_declaracion']) ? $data['renta']['tipo_declaracion'] : (isset($data['renta']['es_sustitutiva']) && $data['renta']['es_sustitutiva'] ? 'SUSTITUTIVA' : 'ORIGINAL');
    $sust_text = $tipo_decl == 'SUSTITUTIVA' ? ' [SUST]' : ' [ORIG]';

    $chips_renta[] = 'F' . $tipo_form . ($anio_form ? ' ' . $anio_form : '') . $sust_text;
} elseif (isset($data['renta']) && $data['renta'] == 'OK') {
    $chips_renta[] = 'Renta OK';
}
if (isset($data['retenciones_rec']) && is_array($data['retenciones_rec'])) {
    ksort($data['retenciones_rec']);
    foreach ($data['retenciones_rec'] as $m => $v) {
        if (!empty($v) && isset($meses_nombres[$m-1])) {
            $chips_retenciones[] = $meses_nombres[$m-1];
        }
    }
}

echo json_encode(array(
    'maestra' => $maestra,
    'totales' => $totales,
    'f103_cols' => $f103_cols,
    'ir' => $ir,
    'base_imponible' => $base_imponible,
    'ir_causado' => $ir_causado,
    'base_imponible_decl' => $base_imponible_decl,
    'ir_causado_decl' => $ir_causado_decl,
    'participacion_15_decl' => $participacion_15_decl,
    'utilidad_antes_part' => $utilidad_antes_part,
    'kpis' => $kpi_maestra,
    'meses_cargados' => $meses_cargados,
    'chips_104' => $chips_104,
    'chips_103' => $chips_103,
    'chips_iess' => $chips_iess,
    'chips_renta' => $chips_renta,
    'chips_retenciones' => $chips_retenciones,
    'ret_cols_renta' => $columnas_renta,
    'ret_cols_iva' => $columnas_iva,
    'ret_rec_tot' => $ret_rec_tot,
    'ret_analisis' => isset($_SESSION['ct_data']['ret_analisis']) ? $_SESSION['ct_data']['ret_analisis'] : null,
    'tot_renta_global' => $tot_renta_global,
    'tot_iva_global' => $tot_iva_global,
    'tot_ret_global' => $tot_ret_global,
    'resumen_iva' => $resumen_iva,
    'gastos_personales' => $gastos_personales,
    'gastos_personales_decl' => $gastos_personales_decl,
    'gastos_adicionales' => $gastos_adicionales,
    'gastos_adicionales_decl' => $gastos_adicionales_decl,
    'rendimientos' => $rendimientos,
    'rendimientos_decl' => $rendimientos_decl,
    'sueldo_107' => $sueldo_107,
    'sueldo_107_decl' => $sueldo_107_decl,
    'depreciacion' => $depreciacion,
    'depreciacion_decl' => $depreciacion_decl,
    'gastos_nd' => $gastos_nd,
    'gastos_nd_decl' => $gastos_nd_decl,
    'utilidad_antes_part' => $utilidad_antes_part,
    'utilidad_antes_part_decl' => $utilidad_antes_part_decl,
    'participacion_15' => $participacion_15,
    'credito_anterior'     => $credito_anterior,
    'credito_anterior_decl'=> $credito_anterior_decl,
    'anticipo_pagado'      => $anticipo_pagado,
    'anticipo_pagado_decl' => $anticipo_pagado_decl,
    'perdidas_amortizables'=> $perdida_amortizable,
    'perdidas_amortizables_decl'=> $perdida_amortizable_decl,
    'f101_credito_origen'  => $f101_credito,
    'f101_anticipo_origen' => $f101_anticipo,
    'f101_perdida_origen'  => $f101_perdida,
    'calc_details'         => $calc_details,
    'calc_details_decl'    => $calc_details_decl,
    'meses_exa' => isset($data['meses_exa']) ? $data['meses_exa'] : array(),
    'ruc' => isset($_SESSION['ct_ruc']) ? $_SESSION['ct_ruc'] : '0000000000001',
    'nombre' => isset($_SESSION['ct_nombre']) ? $_SESSION['ct_nombre'] : 'Contribuyente Ejemplo',
    'anio' => $anio,
    'regimen' => $regimen,
    'iess_detalle' => isset($data['iess']) ? $data['iess'] : array(),
    'renta_analisis' => isset($data['renta']) && is_array($data['renta']) ? $data['renta'] : null
));

