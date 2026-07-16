<?php
/**
 * Cálculos IESS — aportes, décimos, vacaciones
 */

function cte_iess_tasas() {
    $c = (isset($_SESSION['contribuyente']) ? $_SESSION['contribuyente'] : array());
    return array(
        'patronal' => cte_num((isset($c['tasa_patronal']) ? $c['tasa_patronal'] : 11.15)) / 100,
        'individual' => cte_num((isset($c['tasa_individual']) ? $c['tasa_individual'] : 9.45)) / 100,
        'ccc' => cte_num((isset($c['tasa_ccc']) ? $c['tasa_ccc'] : 1)) / 100,
        'sbu' => cte_num((isset($c['sbu']) ? $c['sbu'] : 460)),
    );
}

function cte_calcular_fila_iess(array $fila) {
    $tasas = cte_iess_tasas();
    $sueldo = cte_num((isset($fila['sueldo']) ? $fila['sueldo'] : 0));
    $dias = (int) ((isset($fila['dias']) ? $fila['dias'] : 30));

    $patronal = cte_num((isset($fila['aporte_patronal']) ? $fila['aporte_patronal'] : 0));
    $individual = cte_num((isset($fila['aporte_individual']) ? $fila['aporte_individual'] : 0));
    $ccc = cte_num((isset($fila['valor_ccc']) ? $fila['valor_ccc'] : 0));

    if ($patronal <= 0 && $sueldo > 0) {
        $patronal = round($sueldo * $tasas['patronal'], 2);
    }
    if ($individual <= 0 && $sueldo > 0) {
        $individual = round($sueldo * $tasas['individual'], 2);
    }
    if ($ccc <= 0 && $sueldo > 0) {
        $ccc = round($sueldo * $tasas['ccc'], 2);
    }

    $total = $patronal + $individual + $ccc;
    $liquido = $sueldo - $individual;
    $costoEmpresa = $sueldo + $patronal + $ccc;
    $dec13 = $sueldo / 12;
    $dec14 = $tasas['sbu'] / 12;
    $vacaciones = $sueldo / 24;

    return array_merge($fila, array(
        'dias' => $dias,
        'aporte_patronal' => $patronal,
        'aporte_individual' => $individual,
        'valor_ccc' => $ccc,
        'total_aporte' => $total,
        'sueldo_liquido' => $liquido,
        'costo_empresa' => $costoEmpresa,
        'decimo_tercero' => $dec13,
        'decimo_cuarto' => $dec14,
        'vacaciones' => $vacaciones,
    ));
}

function cte_iess_resumen_mes($mes) {
    $empleados = (isset($_SESSION['iess']['empleados']) ? $_SESSION['iess']['empleados'] : array());
    $totalSueldos = 0;
    $totalAportes = 0;
    $count = 0;
    $anio = cte_anio_contribuyente();

    foreach ($empleados as $emp) {
        $periodo = (isset($emp['periodo']) ? $emp['periodo'] : '');
        if (preg_match('/(\d{4})-(\d{2})/', $periodo, $m)) {
            if ((int) $m[1] === $anio && (int) $m[2] === $mes) {
                $totalSueldos += cte_num((isset($emp['sueldo']) ? $emp['sueldo'] : 0));
                $totalAportes += cte_num((isset($emp['total_aporte']) ? $emp['total_aporte'] : 0));
                $count++;
            }
        }
    }

    return array(
        'empleados' => $count,
        'total_sueldos' => $totalSueldos,
        'total_aportes' => $totalAportes,
    );
}

function cte_iess_resumen_empleados() {
    $empleados = (isset($_SESSION['iess']['empleados']) ? $_SESSION['iess']['empleados'] : array());
    $porCedula = array();

    foreach ($empleados as $emp) {
        $ced = (isset($emp['cedula']) ? $emp['cedula'] : 'SIN-CED');
        if (!isset($porCedula[$ced])) {
            $porCedula[$ced] = array(
                'cedula' => $ced,
                'nombre' => (isset($emp['nombre']) ? $emp['nombre'] : ''),
                'meses' => 0,
                'total_sueldo' => 0,
                'total_aportes' => 0,
                'total_liquido' => 0,
                'total_costo' => 0,
                'decimo_tercero' => 0,
                'decimo_cuarto' => 0,
                'vacaciones' => 0,
            );
        }
        $calc = cte_calcular_fila_iess($emp);
        $porCedula[$ced]['meses']++;
        $porCedula[$ced]['total_sueldo'] += $calc['sueldo'];
        $porCedula[$ced]['total_aportes'] += $calc['total_aporte'];
        $porCedula[$ced]['total_liquido'] += $calc['sueldo_liquido'];
        $porCedula[$ced]['total_costo'] += $calc['costo_empresa'];
        $porCedula[$ced]['decimo_tercero'] += $calc['decimo_tercero'];
        $porCedula[$ced]['decimo_cuarto'] += $calc['decimo_cuarto'];
        $porCedula[$ced]['vacaciones'] += $calc['vacaciones'];
    }

    return array_values($porCedula);
}
