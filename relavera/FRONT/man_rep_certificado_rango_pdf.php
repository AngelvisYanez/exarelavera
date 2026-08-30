<?php
if (!is_object($obBD_con1)) return;
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/TCPDF/MYPDF.php');

$obBD_conexion = new Class_Log_Conexion_Mani($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Mani;

$Cli_Cod = $_GET['Cli_Cod'];
$Pla_Cod = $_GET['Pla_Cod'];
$Fec_Des = $_GET['Fec_Des'];
$Fec_Has = $_GET['Fec_Has'];

// 1. Obtener datos de cabecera
$cabecera = $obBD_con1->getRowConsulta(8, array('Cli_Cod' => $Cli_Cod, 'Pla_Cod' => $Pla_Cod), $obBD_conexion);

// 2. Obtener listado de manifiestos
$listado = $obBD_con1->getArrayConsulta(9, array('Cli_Cod' => $Cli_Cod, 'Pla_Cod' => $Pla_Cod, 'Fec_Des' => $Fec_Des, 'Fec_Has' => $Fec_Has), $obBD_conexion);

$facturados = 0;
$no_facturados = 0;
$suma_total = 0;
foreach($listado as $l) {
    if($l['Facturado'] == 1) $facturados++;
    else $no_facturados++;
    $suma_total += $l['Valor'];
}
$total_entregados = count($listado);

$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$firma_fue_leida = false;

// 3. Obtener firma electrónica (Siempre se intenta en este archivo)
$sql_llave = "SELECT Lla_Rut, Lla_Cla, Lla_Cad FROM llave_elect WHERE Lla_Est = 'A' AND Emp_Cod = $Ses_Emp_Cod";
$res_llave = $obBD_con1->consulta($sql_llave, $obBD_conexion->conexion);
$llave = $obBD_con1->fetch_assoc($res_llave);

// $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// $firma_fue_leida = false;
// Configurar firma si existe
if ($llave && !empty($llave['Lla_Rut'])) {
    $ruta_p12 = $APP_REAL_PATH . "/facturacion/FRONT/$Ses_Emp_Cod/" . $llave['Lla_Rut'];
    $password = $llave['Lla_Cla'];
    
    if (file_exists($ruta_p12)) {
        $p12_data = file_get_contents($ruta_p12);
        $certs = array();
        if (openssl_pkcs12_read($p12_data, $certs, $password)) {
            // Datos de la firma
            $info = array(
                'Name'        => $cabecera['Representante'],
                'Location'    => 'Ecuador',
                'Reason'      => 'Certificado de Manifiestos',
                'ContactInfo' => '',
            );
            
            // Establecer firma
            $pdf->setSignature($certs['cert'], $certs['pkey'], $password, '', 2, $info);
            $firma_fue_leida = true;
        }
    }
}

// Iniciar PDF
$pdf->SetCreator('EXA Software');
$pdf->SetAuthor('EXA');
$pdf->SetTitle('Certificado de Manifiestos');
$pdf->setPrintHeader(false); // Usaremos diseño manual similar al HTML
$pdf->AddPage();

// Logo dinámico
$logo_path = "../../imagenes/$Ses_Emp_Cod/relavera.png"; 
if (!file_exists($logo_path)) {
    $logo_path = "../../imagenes/620/relavera.png"; // Fallback
}

// Títulos (Sin espacio inicial innecesario)
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 7, 'Proyecto ambiental asociativo Relavera Comunitaria "EL TABLON"', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(0, 7, "CERTIFICADO DE MANIFIESTOS UNICO EN LA FASE DE DISPOSICION FINAL\nDE DESECHOS PELIGROSOS Y/O ESPECIALES B.07.01", 0, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'ECOPARKMINING S.A. operador asociado del Gobierno Autonomo Provincial de El Oro', 0, 1, 'C');

// Ahora dibujamos el logo en la posición lateral que indicaste
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 160, 35, 35);
}

$pdf->Ln(5);

// Información del Cliente
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'RUC:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, $cabecera['Prs_Ced'], 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'REPRESENTANTE:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, $cabecera['Representante'], 0, 1);

$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'NOMBRE DE PLANTA:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, $cabecera['Pla_Nom'], 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'CODIGO DE PLANTA:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, $cabecera['Pla_Car'], 0, 1);

$pdf->Ln(5);

// Rango de fechas
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(75, 8, 'FECHA DE CORTE DE MANIFIESTO', 0, 0);
// $pdf->SetFillColor(255, 255, 0); // Amarillo
// $pdf->SetFont('helvetica', '', 10);
$pdf->Cell(5, 8, '', 0, 0);
$pdf->Cell(40, 8, 'DESDE: ' . date("d/m/Y", strtotime($Fec_Des)), 1, 0, 'L');
$pdf->Cell(5, 8, '', 0, 0);
$pdf->Cell(40, 8, 'HASTA: ' . date("d/m/Y", strtotime($Fec_Has)), 1, 1, 'L');

$pdf->Ln(5);

// Resumen
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(100, 6, 'FECHA DE EMISION DEL CERTIFICADO:', 'B', 0);
$pdf->Cell(30, 6, date("d/m/Y"), 'B', 1, 'R');

$pdf->Cell(100, 6, 'CANTIDAD DE MANIFIESTOS FACTURADOS:', 'B', 0);
$pdf->Cell(30, 6, $facturados, 'B', 1, 'R');

$pdf->Cell(100, 6, 'CANTIDAD DE MANIFIESTOS NO FACTURADOS:', 'B', 0);
$pdf->Cell(30, 6, $no_facturados, 'B', 1, 'R');

$pdf->SetFont('helvetica', 'BU', 10);
$pdf->Cell(100, 8, 'TOTAL MANIFIESTOS ENTREGADOS', 0, 0);
$pdf->Cell(30, 8, $total_entregados, 0, 1, 'R');

$pdf->Ln(5);

// Cabecera de la Tabla (Definida para repetirse)
$header_tabla = function($pdf) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(10, 8, '#', 1, 0, 'C', true);
    //$pdf->Cell(18, 8, 'Cod. Int.', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'No Manif.', 1, 0, 'C', true);
    $pdf->Cell(22, 8, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(18, 8, 'H. Llega.', 1, 0, 'C', true);
    
    $pdf->Cell(22, 8, 'Peso (KG)', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Factura', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Guía', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Vehiculo', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Valor', 1, 1, 'C', true);
    $pdf->SetFont('helvetica', '', 8);
};

// Dibujar primera cabecera
$header_tabla($pdf);

$count = 1;
foreach($listado as $item) {
    // Si no hay espacio, saltar de página y repetir cabecera
    if ($pdf->GetY() > 265) {
        $pdf->AddPage();
        $header_tabla($pdf);
    }
    
    $pdf->Cell(10, 6, $count++, 1, 0, 'C');
    //$pdf->Cell(18, 6, $item['Man_Cod'], 1, 0, 'C');
    $pdf->Cell(25, 6, $item['Man_Num_Full'], 1, 0, 'C');
    $pdf->Cell(22, 6, date("d/m/Y", strtotime($item['Fecha'])), 1, 0, 'C');
    $pdf->Cell(18, 6, substr($item['Llegada'], 0, 5), 1, 0, 'C');
   
    $pdf->Cell(22, 6, number_format($item['Man_Pes'], 2, '.', ','), 1, 0, 'C');
    
    if ($item['Facturado'] == 0) {
        // $pdf->SetFillColor(247, 150, 70);
        $pdf->Cell(25, 6, '-', 1, 0, 'C');
        // $pdf->SetFillColor(255, 255, 255);
    } else {
        $pdf->Cell(25, 6, $item['Factura'], 1, 0, 'C');
    }
    $pdf->Cell(25, 6, $item['Man_Gui'], 1, 0, 'C');
    $pdf->Cell(20, 6, $item['Veh_Pla'], 1, 0, 'C');
    $pdf->Cell(25, 6, '$ ' . number_format($item['Valor'], 2, '.', ','), 1, 1, 'R');
}

// Total Final
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(167, 8, 'TOTAL:', 0, 0, 'R');
$pdf->Cell(25, 8, '$ ' . number_format($suma_total, 2, '.', ','), 1, 1, 'R');

// --- BLOQUE DE FIRMA ---
// Desactivar salto automático momentáneamente para evitar la hoja extra al final
$pdf->SetAutoPageBreak(false);

if ($pdf->GetY() > 215) {
    $pdf->AddPage();
    $anchor_y = 30; // Si es hoja nueva, empezamos arriba
} else {
    $pdf->Ln(5);
    $anchor_y = $pdf->GetY() + 20;
}

$anchor_x = 20;

// 1. Dibujar línea y nombre de empresa
$pdf->SetXY($anchor_x, $anchor_y);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(80, 0, '', 'T', 1, 'C');
$pdf->SetX($anchor_x);
$pdf->Cell(80, 5, 'ECOPARKMINING S.A.', 0, 1, 'C');

// 2. Dibujar Firma Digital
if ($firma_fue_leida) {
    $cert_data = openssl_x509_parse($certs['cert']);
    $nombre_firmante = $cert_data['subject']['CN'];
    
    $pdf->setSignatureAppearance($anchor_x, $anchor_y - 18, 100, 18);
    
    // Generar datos para el QR
    $qr_data = "Firmado por: $nombre_firmante\nFecha: " . date("Y-m-d H:i:s") . "\nEntidad: " . $cert_data['issuer']['O'] . "\nValidar en: www.firmadigital.gob.ec";
    
    require_once('../../Librerias/TCPDF/include/barcodes/qrcode.php');
    $qr = new QRcode($qr_data, 'L');
    $barcode_array = $qr->getBarcodeArray();
    
    if (!empty($barcode_array) && isset($barcode_array['bcode'])) {
        $qr_x = $anchor_x;
        $qr_y = $anchor_y - 17;
        $qr_w = 15;
        $num_rows = $barcode_array['num_rows'];
        $num_cols = $barcode_array['num_cols'];
        $module_size = $qr_w / $num_cols;
        
        $pdf->SetFillColor(0,0,0);
        foreach ($barcode_array['bcode'] as $r => $row) {
            foreach ($row as $c => $val) {
                if ($val) $pdf->Rect($qr_x + ($c * $module_size), $qr_y + ($r * $module_size), $module_size, $module_size, 'F');
            }
        }
    }

    $pdf->SetXY($anchor_x + 18, $anchor_y - 17);
    $pdf->SetFont('helvetica', '', 5);
    $pdf->Cell(80, 3, 'Firmado electrónicamente por:', 0, 1, 'L');
    $pdf->SetX($anchor_x + 18);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->MultiCell(75, 3, strtoupper($nombre_firmante), 0, 'L');
    $pdf->SetX($anchor_x + 18);
    $pdf->SetFont('helvetica', '', 5);
    $pdf->Cell(80, 3, 'Validar documento con FirmaEC', 0, 1, 'L');
}

// Footer (Dibujado mientras el salto automático sigue desactivado)
$pdf->SetY(-15);
$pdf->SetFont('helvetica', 'I', 7);

$nombre_usuario = $_SESSION['Ses_Prs_Nom'] . ' ' . $_SESSION['Ses_Prs_Ape'];
$pdf->Cell(95, 10, 'Generado por: ' . mb_convert_encoding($nombre_usuario, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$pdf->Cell(95, 10, 'Generado el ' . date("d-m-Y") . ' en EXA [Software Contable]', 0, 0, 'R');

// Salida
$pdf->Output('Certificado_Manifiestos_' . date('YmdHis') . '.pdf', 'I');
