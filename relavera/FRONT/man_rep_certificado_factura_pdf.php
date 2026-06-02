<?php
/**
 * Certificado B.07.01 por factura con firma electrónica (TCPDF).
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_fac_1.0.php');
require_once('../../Librerias/TCPDF/MYPDF.php');

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
foreach ($listado as $l) {
    if (!empty($l['Facturado'])) {
        $facturados++;
    }
    $suma_total += (float)$l['Valor'];
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
        if (openssl_pkcs12_read($p12_data, $certs, $password)) {
            $info = array(
                'Name' => isset($cabecera['Representante']) ? $cabecera['Representante'] : '',
                'Location' => 'Ecuador',
                'Reason' => 'Certificado de Manifiestos por Factura',
                'ContactInfo' => '',
            );
            $pdf->setSignature($certs['cert'], $certs['pkey'], $password, '', 2, $info);
            $firma_fue_leida = true;
        }
    }
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
$txt_cert = 'Certifico que el valor total reflejado en la presente factura corresponde a los manifiestos de transporte generados por cada viaje con cargamento de relave, documentos en los cuales constan el peso transportado y el valor economico correspondiente a cada operacion realizada.';
$pdf->MultiCell(0, 4, $txt_cert, 0, 'J');
$pdf->Ln(2);

$header_tabla = function ($pdf) {
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(8, 7, '#', 1, 0, 'C', true);
    $pdf->Cell(14, 7, 'Cod.', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(14, 7, 'H.Lleg.', 1, 0, 'C', true);
    $pdf->Cell(28, 7, 'No Manif.', 1, 0, 'C', true);
    $pdf->Cell(18, 7, 'Peso KG', 1, 0, 'C', true);
    $pdf->Cell(28, 7, 'Factura', 1, 0, 'C', true);
    $pdf->Cell(22, 7, 'Vehiculo', 1, 0, 'C', true);
    $pdf->Cell(22, 7, 'Valor', 1, 1, 'C', true);
    $pdf->SetFont('helvetica', '', 7);
};

$header_tabla($pdf);
$count = 1;
foreach ($listado as $item) {
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $header_tabla($pdf);
    }
    $pdf->Cell(8, 5, (string)$count++, 1, 0, 'C');
    $pdf->Cell(14, 5, (string)$item['Man_Cod'], 1, 0, 'C');
    $pdf->Cell(20, 5, date('d/m/Y', strtotime($item['Fecha'])), 1, 0, 'C');
    $pdf->Cell(14, 5, substr((string)$item['Llegada'], 0, 5), 1, 0, 'C');
    $pdf->Cell(28, 5, $item['Man_Num_Full'], 1, 0, 'C');
    $pdf->Cell(18, 5, number_format((float)$item['Man_Pes'], 2, '.', ','), 1, 0, 'R');
    $pdf->Cell(28, 5, ((int)$item['Facturado'] === 1) ? $item['Factura'] : '-', 1, 0, 'C');
    $pdf->Cell(22, 5, $item['Veh_Pla'], 1, 0, 'C');
    $pdf->Cell(22, 5, '$ ' . number_format((float)$item['Valor'], 2, '.', ','), 1, 1, 'R');
}

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(152, 6, 'TOTAL:', 0, 0, 'R');
$pdf->Cell(22, 6, '$ ' . number_format($suma_total, 2, '.', ','), 1, 1, 'R');

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

$pdf->SetY(-15);
$pdf->SetFont('helvetica', 'I', 7);
$nombre_usuario = $_SESSION['Ses_Prs_Nom'] . ' ' . $_SESSION['Ses_Prs_Ape'];
$pdf->Cell(95, 10, 'Generado por: ' . $nombre_usuario, 0, 0, 'L');
$pdf->Cell(95, 10, 'Generado el ' . date('d-m-Y') . ' en EXA [Software Contable]', 0, 0, 'R');

$pdf->Output('Certificado_Factura_' . $Vet_Cod . '_' . date('YmdHis') . '.pdf', 'I');
