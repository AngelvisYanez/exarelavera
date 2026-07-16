<?php $fpdfPaths = array(
    CTE_ROOT . '/libs/fpdf/fpdf.php',
    dirname(CTE_ROOT) . '/Librerias/fpdf/fpdf.php',
);
$loaded = false;
foreach ($fpdfPaths as $p) {
    if (is_file($p)) {
        require_once $p;
        $loaded = true;
        break;
    }
}
if (!$loaded) {
    die('FPDF no encontrado. Copie fpdf.php a libs/fpdf/');
}

function cte_generar_pdf() {
    $c = $_SESSION['contribuyente'];
    $anio = (int) ((isset($c['anio']) ? $c['anio'] : date('Y')));
    $meses = cte_todos_meses();
    $tot = cte_totales_anuales_iva($meses);
    $ir = cte_conciliacion_ir();
    $semaforo = cte_semaforo_obligaciones();

    $pdf = new FPDF();
    $pdf->SetAutoPageBreak(true, 15);

    // Portada
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 22);
    $pdf->SetTextColor(192, 57, 43);
    $pdf->Cell(0, 12, 'EXA Software', 0, 1, 'C');
    $pdf->SetTextColor(26, 42, 64);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'CONTROL TRIBUTARIO ECUADOR', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Ln(8);
    $pdf->Cell(0, 8, utf8_decode((isset($c['razon_social']) ? $c['razon_social'] : '')), 0, 1, 'C');
    $pdf->Cell(0, 8, 'RUC: ' . ((isset($c['ruc']) ? $c['ruc'] : '')), 0, 1, 'C');
    $pdf->Cell(0, 8, 'Período fiscal: ' . $anio, 0, 1, 'C');
    $pdf->Cell(0, 8, 'Generado: ' . date('d/m/Y H:i'), 0, 1, 'C');
    if (!empty($c['contador'])) {
        $pdf->Cell(0, 8, utf8_decode('Contador: ' . $c['contador']), 0, 1, 'C');
    }

    // Página 1 — Resumen mensual landscape
    $pdf->AddPage('L', 'A4');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Resumen mensual', 0, 1);
    $pdf->SetFont('Arial', '', 8);
    $w = array(25, 28, 28, 28, 28, 28, 28, 28, 28);
    $headers = array('Mes', 'Ventas', 'Compras', 'IVA Caus.', 'Créd.Trib.', 'IVA Pagar', 'Nomina', 'IESS', 'Saldo CT');
    foreach ($headers as $i => $h) {
        $pdf->Cell($w[$i], 6, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();
    foreach ($meses as $dm) {
        $pdf->Cell($w[0], 5, substr($dm['mes_label'], 0, 3), 1);
        $pdf->Cell($w[1], 5, number_format($dm['ventas'], 2), 1, 0, 'R');
        $pdf->Cell($w[2], 5, number_format($dm['compras'], 2), 1, 0, 'R');
        $pdf->Cell($w[3], 5, number_format($dm['iva_causado'], 2), 1, 0, 'R');
        $pdf->Cell($w[4], 5, number_format($dm['credito_tributario'], 2), 1, 0, 'R');
        $pdf->Cell($w[5], 5, number_format($dm['iva_a_pagar'], 2), 1, 0, 'R');
        $pdf->Cell($w[6], 5, number_format($dm['nomina'], 2), 1, 0, 'R');
        $pdf->Cell($w[7], 5, number_format($dm['iess'], 2), 1, 0, 'R');
        $pdf->Cell($w[8], 5, number_format($dm['617'], 2), 1, 1, 'R');
    }
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell($w[0], 5, 'TOTAL', 1);
    $pdf->Cell($w[1], 5, number_format($tot['ventas'], 2), 1, 0, 'R');
    $pdf->Cell($w[2], 5, number_format($tot['compras'], 2), 1, 0, 'R');
    $pdf->Cell($w[3], 5, number_format($tot['iva_causado'], 2), 1, 0, 'R');
    $pdf->Cell($w[4], 5, number_format($tot['credito_tributario'], 2), 1, 0, 'R');
    $pdf->Cell($w[5], 5, number_format($tot['iva_a_pagar'], 2), 1, 0, 'R');
    $pdf->Cell($w[6], 5, number_format($tot['nomina'], 2), 1, 0, 'R');
    $pdf->Cell($w[7], 5, number_format($tot['iess'], 2), 1, 0, 'R');
    $pdf->Cell($w[8], 5, '', 1, 1);

    // Página 2 — Semáforo (muestra parcial)
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode('Semáforo de obligaciones'), 0, 1);
    $pdf->SetFont('Arial', '', 7);
    $shown = 0;
    foreach ($semaforo as $s) {
        if ($shown++ > 60) {
            break;
        }
        $est = strtoupper($s['estado']);
        $pdf->Cell(25, 4, substr($s['mes_label'], 0, 3), 1);
        $pdf->Cell(55, 4, utf8_decode(substr($s['obligacion'], 0, 28)), 1);
        $pdf->Cell(25, 4, $s['vencimiento'], 1);
        $pdf->Cell(25, 4, $est, 1, 1);
    }

    // Página 3 — IR
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Resumen Impuesto a la Renta', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $lineas = array(
        'Ingresos' => $ir['ingresos'],
        'Gastos deducibles' => $ir['gastos_deducibles'],
        'Utilidad' => $ir['utilidad'],
        'Base imponible' => $ir['base_imponible'],
        'IR causado' => $ir['ir_causado'],
        'IR a pagar' => $ir['ir_a_pagar'],
    );
    foreach ($lineas as $lbl => $val) {
        $pdf->Cell(100, 7, utf8_decode($lbl), 0, 0);
        $pdf->Cell(0, 7, number_format($val, 2), 0, 1, 'R');
    }

    // Página 4 — IESS
    $pdf->AddPage('L', 'A4');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Resumen IESS por empleado', 0, 1);
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(25, 5, utf8_decode('Cédula'), 1);
    $pdf->Cell(70, 5, 'Nombre', 1);
    $pdf->Cell(15, 5, 'Meses', 1, 0, 'C');
    $pdf->Cell(30, 5, 'Total sueldo', 1, 0, 'R');
    $pdf->Cell(30, 5, 'Total IESS', 1, 0, 'R');
    $pdf->Cell(30, 5, 'Costo total', 1, 1, 'R');
    foreach (cte_iess_resumen_empleados() as $r) {
        $pdf->Cell(25, 4, $r['cedula'], 1);
        $pdf->Cell(70, 4, utf8_decode(substr($r['nombre'], 0, 40)), 1);
        $pdf->Cell(15, 4, $r['meses'], 1, 0, 'C');
        $pdf->Cell(30, 4, number_format($r['total_sueldo'], 2), 1, 0, 'R');
        $pdf->Cell(30, 4, number_format($r['total_aportes'], 2), 1, 0, 'R');
        $pdf->Cell(30, 4, number_format($r['total_costo'], 2), 1, 1, 'R');
    }

    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetY(-15);
    $pdf->Cell(0, 8, utf8_decode('Generado por EXA Sistema Contable'), 0, 0, 'C');

    $nombre = 'Control_Tributario_' . ((isset($c['ruc']) ? $c['ruc'] : 'doc')) . '_' . $anio . '.pdf';
    $pdf->Output('D', $nombre);
}
