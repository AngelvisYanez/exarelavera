<?php
require_once __DIR__ . '/SimpleXLSX.php';

function cte_generar_excel() {
    $c = $_SESSION['contribuyente'];
    $anio = (int) ((isset($c['anio']) ? $c['anio'] : date('Y')));
    $meses = cte_todos_meses();
    $xlsx = new SimpleXLSX();

    // HOJA 1 — CONTROL TRIBUTARIO
    $s1 = $xlsx->addSheet('CONTROL TRIBUTARIO');
    $xlsx->writeRow($s1, array('CONTROL TRIBUTARIO — ' . ((isset($c['razon_social']) ? $c['razon_social'] : '')) . ' — ' . $anio), array(1));
    $xlsx->writeRow($s1, array('RUC: ' . ((isset($c['ruc']) ? $c['ruc'] : '')), 'Régimen: ' . ((isset($c['regimen']) ? $c['regimen'] : '')), 'Período: ' . $anio));

    $cols = array();
    foreach (cte_columnas_maestras() as $gr) {
        foreach ($gr['cols'] as $col) {
            $cols[] = $col;
        }
    }

    $header1 = array('MES');
    $header2 = array('');
    $header3 = array('');
    foreach (cte_columnas_maestras() as $gr) {
        $n = count($gr['cols']);
        $header1 = array_merge($header1, array_fill(0, $n, $gr['grupo1']));
        $header2 = array_merge($header2, array_fill(0, $n, $gr['grupo2']));
        foreach ($gr['cols'] as $col) {
            $header3[] = $col['label'] . ($col['campo'] ? ' (' . $col['campo'] . ')' : '');
        }
    }
    $xlsx->writeRow($s1, $header1, array_fill(0, count($header1), 1));
    $xlsx->writeRow($s1, $header2, array_fill(0, count($header2), 1));
    $xlsx->writeRow($s1, $header3, array_fill(0, count($header3), 1));

    $totCols = array();
    for ($m = 1; $m <= 12; $m++) {
        $dm = $meses[$m];
        $row = array($GLOBALS['CTE_MESES'][$m]);
        $ci = 0;
        foreach ($cols as $col) {
            $v = cte_valor_columna_maestra($dm, $col['key']);
            $row[] = round($v, 2);
            $totCols[$ci] = ((isset($totCols[$ci]) ? $totCols[$ci] : 0)) + $v;
            $ci++;
        }
        $xlsx->writeRow($s1, $row, array_fill(0, count($row), ($m % 2) ? 2 : 0));
    }
    $totalRow = array('TOTALES ANUALES');
    foreach ($totCols as $t) {
        $totalRow[] = round($t, 2);
    }
    $xlsx->writeRow($s1, $totalRow, array_fill(0, count($totalRow), 1));

    // HOJA 2 — RESUMEN I.R.
    $s2 = $xlsx->addSheet('RESUMEN I.R.');
    $ir = cte_conciliacion_ir();
    $xlsx->writeRow($s2, array('RESUMEN IMPUESTO A LA RENTA — ' . $anio), array(1));
    foreach (array(
        array('Ingresos', $ir['ingresos']),
        array('(-) Costos y gastos deducibles', $ir['gastos_deducibles']),
        array('Utilidad', $ir['utilidad']),
        array('(+) Gastos no deducibles', $ir['gastos_no_deducibles']),
        array('Base imponible', $ir['base_imponible']),
        array('IR causado', $ir['ir_causado']),
        array('(-) Gastos personales', $ir['gastos_personales']),
        array('(-) Retenciones recibidas', $ir['retenciones']),
        array('(-) Crédito tributario anterior', $ir['credito_anterior']),
        array('IR A PAGAR', $ir['ir_a_pagar']),
    ) as $linea) {
        $xlsx->writeRow($s2, $linea);
    }
    $xlsx->writeRow($s2, array());
    $xlsx->writeRow($s2, array('Tabla progresiva', 'Hasta', 'Imp. FB', 'Excedente', '%'), array(1));
    foreach ($ir['tabla_progresiva'] as $tr) {
        $xlsx->writeRow($s2, array('', $tr['hasta'], $tr['imp'], $tr['exced'], $tr['pct']));
    }

    // HOJA 3 — IESS
    $s3 = $xlsx->addSheet('IESS PLANILLAS');
    $xlsx->writeRow($s3, array('Período', 'Cédula', 'Nombre', 'Sueldo', 'Días', 'Patronal', 'Individual', 'CCC', 'Total', 'Líquido', 'Costo empresa'), array(1));
    foreach ((isset($_SESSION['iess']['empleados']) ? $_SESSION['iess']['empleados'] : array()) as $e) {
        $calc = cte_calcular_fila_iess($e);
        $xlsx->writeRow($s3, array(
            $calc['periodo'], $calc['cedula'], $calc['nombre'],
            $calc['sueldo'], $calc['dias'], $calc['aporte_patronal'],
            $calc['aporte_individual'], $calc['valor_ccc'], $calc['total_aporte'],
            $calc['sueldo_liquido'], $calc['costo_empresa'],
        ));
    }
    $xlsx->writeRow($s3, array());
    $xlsx->writeRow($s3, array('RESUMEN POR EMPLEADO'), array(1));
    $xlsx->writeRow($s3, array('Cédula', 'Nombre', 'Meses', 'Total sueldo', 'Total IESS', '13°', '14°', 'Vacaciones'), array(1));
    foreach (cte_iess_resumen_empleados() as $r) {
        $xlsx->writeRow($s3, array(
            $r['cedula'], $r['nombre'], $r['meses'], $r['total_sueldo'],
            $r['total_aportes'], $r['decimo_tercero'], $r['decimo_cuarto'], $r['vacaciones'],
        ));
    }

    // HOJA 4 — COMPROBANTES SRI
    $s4 = $xlsx->addSheet('COMPROBANTES SRI');
    $xlsx->writeRow($s4, array('N° Serie', 'Período', 'Formulario', 'Tipo', 'Fecha', 'Valor pagado', 'Estado', 'Cód. verificador'), array(1));
    for ($m = 1; $m <= 12; $m++) {
        $d = (isset($_SESSION['declaraciones'][$m]) ? $_SESSION['declaraciones'][$m] : null);
        if (!$d) {
            $xlsx->writeRow($s4, array('—', $GLOBALS['CTE_MESES'][$m], '104', '', '', 0, 'PENDIENTE', ''));
            continue;
        }
        $xlsx->writeRow($s4, array(
            (isset($d['numero_serie']) ? $d['numero_serie'] : ''),
            (isset($d['periodo_texto']) ? $d['periodo_texto'] : $GLOBALS['CTE_MESES'][$m]),
            '104',
            (isset($d['tipo_declaracion']) ? $d['tipo_declaracion'] : ''),
            (isset($d['fecha_recaudacion']) ? $d['fecha_recaudacion'] : ''),
            (isset($d['999']) ? $d['999'] : 0),
            (isset($d['estado']) ? $d['estado'] : ''),
            (isset($d['codigo_verificador']) ? $d['codigo_verificador'] : ''),
        ));
    }

    // HOJA 5 — DETALLE F104
    $s5 = $xlsx->addSheet('DETALLE F104');
    $camposF104 = array('401', '411', '421', '422', '423', '424', '425', '426', '427', '428', '429', '403', '413', '480', '483', '485', '510', '529', '564', '601', '609', '617', '606', '999');
    $hdr = array_merge(array('Campo', 'Descripción'), $GLOBALS['CTE_MESES'], array('TOTAL'));
    $xlsx->writeRow($s5, $hdr, array(1));
    $desc = array(
        '401' => 'Ventas gravadas', '411' => 'Ventas netas 15%', '421' => 'IVA generado 15%',
        '422' => 'IVA generado 13%', '423' => 'IVA generado 8%', '424' => 'IVA generado 5%', 
        '425' => 'IVA generado ret.', '426' => 'IVA generado reg.', '427' => 'IVA 427', '428' => 'IVA 428', '429' => 'TOTAL IVA GENERADO',
        '403' => 'Ventas 0%', '510' => 'Compras 15%', '529' => 'IVA compras',
        '564' => 'Crédito tributario', '601' => 'Impuesto causado', '609' => 'Ret. IVA recibidas',
        '617' => 'Saldo CT próx. mes', '999' => 'Total pagado',
    );
    foreach ($camposF104 as $campo) {
        $row = array($campo, (isset($desc[$campo]) ? $desc[$campo] : ''));
        $sum = 0;
        for ($m = 1; $m <= 12; $m++) {
            $v = cte_num((isset($meses[$m][$campo]) ? $meses[$m][$campo] : 0));
            $row[] = $v;
            $sum += $v;
        }
        $row[] = $sum;
        $xlsx->writeRow($s5, $row);
    }

    $nombre = 'Control_Tributario_' . preg_replace('/\W+/', '_', (isset($c['ruc']) ? $c['ruc'] : 'RUC')) . '_' . $anio . '.xlsx';
    $xlsx->output($nombre);
}
