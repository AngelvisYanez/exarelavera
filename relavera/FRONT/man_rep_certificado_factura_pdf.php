<?php
/**
 * Certificado B.07.01 por factura con firma electr?nica (TCPDF).
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_fac_1.0.php');
require_once('../../Librerias/TCPDF/MYPDF.php');
require_once('../LOGICA/man_cert_firma_helper.php');

$obBD_conexion = new Class_Log_Conexion_manifiesto($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_manifiesto;

$Vet_Cod = isset($_GET['Vet_Cod']) ? intval($_GET['Vet_Cod']) : 0;
if ($Vet_Cod <= 0) {
    die('Factura no valida');
}

$params = array('Vet_Cod' => $Vet_Cod);
$Usu_Cod = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;
$pla_asignada = $obBD_con1->getArrayConsulta(75, array('Usu_Cod' => $Usu_Cod), $obBD_conexion);
$Pla_Cod_Asignada = (is_array($pla_asignada) && count($pla_asignada) > 0) ? intval($pla_asignada[0]['Pla_Cod']) : 0;
if ($Pla_Cod_Asignada > 0) {
    $params['Pla_Cod_Usuario'] = $Pla_Cod_Asignada;
} elseif (!empty($_GET['Pla_Cod_Usuario']) && intval($_GET['Pla_Cod_Usuario']) > 0) {
    $params['Pla_Cod_Usuario'] = intval($_GET['Pla_Cod_Usuario']);
}

$cabecera = $obBD_con1->getRowConsulta(89, $params, $obBD_conexion);
$factura = $obBD_con1->getRowConsulta(87, $params, $obBD_conexion);
$listado = $obBD_con1->getArrayConsulta(88, $params, $obBD_conexion);
if (!is_array($listado)) {
    $listado = array();
}
if (!$cabecera || count($listado) === 0) {
    die('No hay datos para imprimir');
}

$facturados = 0;
$Fec_Des = null;
$Fec_Has = null;
$suma_total = 0;
$suma_peso = 0;
foreach ($listado as $l) {
    if (!empty($l['Facturado'])) {
        $facturados++;
    }
    $suma_total += (float)$l['Valor'];
    $suma_peso += (float)$l['Man_Pes'];
    $f = isset($l['Fecha']) ? substr((string)$l['Fecha'], 0, 10) : null;
    if ($f) {
        if ($Fec_Des === null || $f < $Fec_Des) {
            $Fec_Des = $f;
        }
        if ($Fec_Has === null || $f > $Fec_Has) {
            $Fec_Has = $f;
        }
    }
}

$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$firma_fue_leida = false;
$certs = array();

$sql_llave = "SELECT Lla_Rut, Lla_Cla, Lla_Cad FROM llave_elect WHERE Lla_Est = 'A' AND Emp_Cod = $Ses_Emp_Cod";
$res_llave = $obBD_con1->consulta($sql_llave, $obBD_conexion->conexion);
$llave = $obBD_con1->fetch_assoc($res_llave);

if ($llave && !empty($llave['Lla_Rut'])) {
    $ruta_p12 = $APP_REAL_PATH . "/facturacion/FRONT/$Ses_Emp_Cod/" . $llave['Lla_Rut'];
    $password = $llave['Lla_Cla'];
    if (file_exists($ruta_p12)) {
        $p12_data = file_get_contents($ruta_p12);
        if (@openssl_pkcs12_read($p12_data, $certs, $password)) {
            $cert_info = openssl_x509_parse($certs['cert']);
            $nombre_firmante_cert = isset($cert_info['subject']['CN']) ? $cert_info['subject']['CN'] : '';
            $info = array(
                'Name' => $nombre_firmante_cert !== '' ? $nombre_firmante_cert : (isset($cabecera['Representante']) ? $cabecera['Representante'] : ''),
                'Location' => 'Ecuador',
                'Reason' => 'Certificado de Manifiestos por Factura',
                'ContactInfo' => '',
            );
            $pdf->setSignature($certs['cert'], $certs['pkey'], $password, '', 2, $info);
            $firma_fue_leida = true;
        }
    }
}

if (!$firma_fue_leida) {
    header('Content-Type: text/html; charset=UTF-8');
    die(
        '<p style="font-family:sans-serif;padding:20px;"><strong>No se pudo firmar el certificado.</strong></p>'
        . '<p>Verifique que exista una llave electr&oacute;nica activa (<code>llave_elect</code>) '
        . 'y el archivo .p12 en <code>facturacion/FRONT/' . (int)$Ses_Emp_Cod . '/</code> con la clave correcta.</p>'
        . '<p><a href="javascript:history.back();">Volver</a></p>'
    );
}

$pdf->SetCreator('EXA Software');
$pdf->SetAuthor('EXA');
$pdf->SetTitle('Certificado de Manifiestos');
$pdf->setPrintHeader(false);
$pdf->AddPage();

$logo_path = "../../imagenes/$Ses_Emp_Cod/relavera.png";
if (!file_exists($logo_path)) {
    $logo_path = "../../imagenes/620/relavera.png";
}

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 7, 'Proyecto ambiental asociativo Relavera Comunitaria "EL TABLON"', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 11);
$pdf->MultiCell(0, 6, "CERTIFICADO DE MANIFIESTOS UNICO EN LA FASE DE DISPOSICION FINAL\nDE DESECHOS PELIGROSOS Y/O ESPECIALES B.07.01", 0, 'C');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, 'ECOPARKMINING S.A. operador asociado del Gobierno Autonomo Provincial de El Oro', 0, 1, 'C');

if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 160, 28, 35);
}

$pdf->Ln(4);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'DATOS DEL DOCUMENTO', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$vet_num = isset($factura['Vet_Num_Completo']) ? $factura['Vet_Num_Completo'] : '';
$vet_fec = isset($factura['Vet_Fec']) ? $factura['Vet_Fec'] : '';
$pdf->Cell(45, 5, 'No. factura:', 0, 0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, $vet_num, 0, 1);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(45, 5, 'Fecha factura:', 0, 0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, $vet_fec, 0, 1);

$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(40, 5, 'RUC:', 0, 0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, $cabecera['Prs_Ced'], 0, 1);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(40, 5, 'REPRESENTANTE:', 0, 0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, $cabecera['Representante'], 0, 1);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(40, 5, 'NOMBRE DE PLANTA:', 0, 0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, $cabecera['Pla_Nom'], 0, 1);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(40, 5, 'CODIGO DE PLANTA:', 0, 0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, $cabecera['Pla_Car'], 0, 1);

$pdf->Ln(3);
$fec_des_txt = $Fec_Des ? date('d/m/Y', strtotime($Fec_Des)) : '-';
$fec_has_txt = $Fec_Has ? date('d/m/Y', strtotime($Fec_Has)) : '-';
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, 'RANGO DE FECHA DE MANIFIESTOS FACTURADOS: Desde ' . $fec_des_txt . '  Hasta ' . $fec_has_txt, 0, 1);

$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(100, 5, 'FECHA DE EMISION DEL CERTIFICADO:', 'B', 0);
$pdf->Cell(30, 5, date('d/m/Y'), 'B', 1, 'R');
$pdf->Cell(100, 5, 'CANTIDAD DE MANIFIESTOS FACTURADOS:', 'B', 0);
$pdf->Cell(30, 5, (string)(int)$facturados, 'B', 1, 'R');

$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 8);
$txt_cert = 'EL presente certificado detalla los manifiestos emitidos por la entrega de Desechos Peligrosos B.07.01 al proyecto ambiental asociativo "EL TABLON", por parte del generador de Desechos Peligrosos.';
$pdf->MultiCell(0, 4, $txt_cert, 0, 'J');
$pdf->Ln(2);

// Anchos de columna proporcionales al ancho útil de la página (sin espacio vacío a la derecha)
$cert_margins = $pdf->getMargins();
$cert_table_w = $pdf->getPageWidth() - $cert_margins['left'] - $cert_margins['right'];
$cert_base_widths = array(8, 18, 46, 18, 26, 18, 14, 14, 18); // Peso KG = Valor; No Manif. más estrecho
$cert_scale = $cert_table_w / array_sum($cert_base_widths);
$cert_col_widths = array();
foreach ($cert_base_widths as $bw) {
    $cert_col_widths[] = round($bw * $cert_scale, 2);
}
$cert_width_fix = $cert_table_w - array_sum($cert_col_widths);
$cert_col_widths[count($cert_col_widths) - 1] += $cert_width_fix;
$pdf->SetX($cert_margins['left']);
$cert_col_aligns = array('C', 'C', 'L', 'C', 'C', 'R', 'C', 'C', 'R');
$cert_line_h = 4;

$cert_print_row = function ($pdf, $cells, $widths, $aligns, $lineH) {
    $nb = 1;
    foreach ($cells as $i => $text) {
        $n = $pdf->getNumLines((string)$text, $widths[$i]);
        if ($n > $nb) {
            $nb = $n;
        }
    }
    $h = $lineH * $nb;
    $x0 = $pdf->GetX();
    $y = $pdf->GetY();
    // Bordes unificados por fila (evita líneas horizontales desalineadas)
    $xBorder = $x0;
    foreach ($widths as $w) {
        $pdf->Rect($xBorder, $y, $w, $h);
        $xBorder += $w;
    }
    // Texto sin borde propio, centrado verticalmente en la celda
    $x = $x0;
    foreach ($cells as $i => $text) {
        $pdf->MultiCell($widths[$i], $lineH, (string)$text, 0, $aligns[$i], false, 0, $x, $y, true, 0, false, true, $h, 'M');
        $x += $widths[$i];
    }
    $pdf->SetXY($x0, $y + $h);
};

$header_tabla = function ($pdf) use ($cert_col_widths, $cert_margins) {
    $pdf->SetX($cert_margins['left']);
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell($cert_col_widths[0], 7, '#', 1, 0, 'C', true);
    $pdf->Cell($cert_col_widths[1], 7, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell($cert_col_widths[2], 7, 'Chofer', 1, 0, 'C', true);
    $pdf->Cell($cert_col_widths[3], 7, 'No Manif.', 1, 0, 'C', true);
    $pdf->Cell($cert_col_widths[4], 7, 'Guia', 1, 0, 'C', true);
    $pdf->Cell($cert_col_widths[5], 7, 'Peso KG', 1, 0, 'C', true);
    $pdf->Cell($cert_col_widths[6], 7, 'Factura', 1, 0, 'C', true);
    $pdf->Cell($cert_col_widths[7], 7, 'Vehiculo', 1, 0, 'C', true);
    $pdf->Cell($cert_col_widths[8], 7, 'Valor', 1, 1, 'C', true);
    $pdf->SetFont('helvetica', '', 7);
};

$header_tabla($pdf);
$count = 1;
foreach ($listado as $item) {
    $chofer_txt = isset($item['chofer']) ? trim($item['chofer']) : '';
    $cells = array(
        (string)$count++,
        date('d/m/Y', strtotime($item['Fecha'])),
        $chofer_txt,
        $item['Man_Num_Full'],
        isset($item['Man_Gui']) ? $item['Man_Gui'] : '',
        number_format((float)$item['Man_Pes'], 2, '.', ','),
        ((int)$item['Facturado'] === 1) ? $item['Factura'] : '-',
        isset($item['Veh_Pla']) ? $item['Veh_Pla'] : '',
        '$ ' . number_format((float)$item['Valor'], 2, '.', ',')
    );
    $rowLines = 1;
    foreach ($cells as $i => $text) {
        $rowLines = max($rowLines, $pdf->getNumLines((string)$text, $cert_col_widths[$i]));
    }
    $rowH = $cert_line_h * $rowLines;
    if ($pdf->GetY() + $rowH > 250) {
        $pdf->AddPage();
        $pdf->SetX($cert_margins['left']);
        $header_tabla($pdf);
    }
    $cert_print_row($pdf, $cells, $cert_col_widths, $cert_col_aligns, $cert_line_h);
}

$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetX($cert_margins['left']);
$pdf->Cell(array_sum(array_slice($cert_col_widths, 0, 5)), 6, 'TOTALES', 0, 0, 'R');
$pdf->Cell($cert_col_widths[5], 6, number_format($suma_peso, 2, '.', ','), 1, 0, 'R');
$pdf->Cell($cert_col_widths[6], 6, '', 1, 0, 'C');
$pdf->Cell($cert_col_widths[7], 6, '', 1, 0, 'C');
$pdf->Cell($cert_col_widths[8], 6, '$ ' . number_format($suma_total, 2, '.', ','), 1, 1, 'R');

$pdf->SetAutoPageBreak(false);
if ($pdf->GetY() > 215) {
    $pdf->AddPage();
    $anchor_y = 30;
} else {
    $pdf->Ln(5);
    $anchor_y = $pdf->GetY() + 15;
}
$anchor_x = 20;

$pdf->SetXY($anchor_x, $anchor_y);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(80, 0, '', 'T', 1, 'C');
$pdf->SetX($anchor_x);
$pdf->Cell(80, 5, 'ECOPARKMINING S.A.', 0, 1, 'C');

if ($firma_fue_leida && !empty($certs['cert'])) {
    $cert_data = openssl_x509_parse($certs['cert']);
    $nombre_firmante = isset($cert_data['subject']['CN']) ? $cert_data['subject']['CN'] : 'Firmante';
    $pdf->setSignatureAppearance($anchor_x, $anchor_y - 18, 100, 18);
    $qr_data = "Firmado por: $nombre_firmante\nFecha: " . date('Y-m-d H:i:s') . "\nEntidad: " . (isset($cert_data['issuer']['O']) ? $cert_data['issuer']['O'] : '') . "\nValidar en: www.firmadigital.gob.ec";
    require_once('../../Librerias/TCPDF/include/barcodes/qrcode.php');
    $qr = new QRcode($qr_data, 'L');
    $barcode_array = $qr->getBarcodeArray();
    if (!empty($barcode_array) && isset($barcode_array['bcode'])) {
        $qr_x = $anchor_x;
        $qr_y = $anchor_y - 17;
        $qr_w = 15;
        $num_cols = $barcode_array['num_cols'];
        $module_size = $qr_w / max(1, $num_cols);
        $pdf->SetFillColor(0, 0, 0);
        foreach ($barcode_array['bcode'] as $r => $row) {
            foreach ($row as $c => $val) {
                if ($val) {
                    $pdf->Rect($qr_x + ($c * $module_size), $qr_y + ($r * $module_size), $module_size, $module_size, 'F');
                }
            }
        }
    }
    $pdf->SetXY($anchor_x + 18, $anchor_y - 17);
    $pdf->SetFont('helvetica', '', 5);
    $pdf->Cell(80, 3, 'Firmado electronicamente por:', 0, 1, 'L');
    $pdf->SetX($anchor_x + 18);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->MultiCell(75, 3, strtoupper($nombre_firmante), 0, 'L');
    $pdf->SetX($anchor_x + 18);
    $pdf->SetFont('helvetica', '', 5);
    $pdf->Cell(80, 3, 'Validar documento con FirmaEC', 0, 1, 'L');
}

$emp_cod_verf = isset($Ses_Emp_Cod) ? (int)$Ses_Emp_Cod : 0;
if ($pdf->GetY() > 230) {
    $pdf->AddPage();
}
man_cert_verificacion_qr_tcpdf($pdf, $Vet_Cod, $emp_cod_verf);

$pdf->SetY(-15);
$pdf->SetFont('helvetica', 'I', 7);
$nombre_usuario = $_SESSION['Ses_Prs_Nom'] . ' ' . $_SESSION['Ses_Prs_Ape'];
$pdf->Cell(95, 10, 'Generado por: ' . $nombre_usuario, 0, 0, 'L');
$pdf->Cell(95, 10, 'Generado el ' . date('d-m-Y') . ' en EXA [Software Contable]', 0, 0, 'R');

$pdf->Output('Certificado_Factura_' . $Vet_Cod . '_' . date('YmdHis') . '.pdf', 'I');
