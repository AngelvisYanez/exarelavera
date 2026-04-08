<?php
/**
 * Exportación PDF - Propuesta de Servicios Adicionales (solo servicios seleccionados)
 * POST: Con_Cod, servicios_seleccionados (JSON array de { Act_Cod, Act_Nombre, Ser_Nombre, Precio })
 */
ob_start();
require_once(__DIR__ . '/../../administrador/LOGICA/seguridad.php');
require_once(__DIR__ . '/../LOGICA/aud_log_despacho_1.0.php');
require_once(__DIR__ . '/../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Despacho($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Despacho();
$Ses_Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;
$nombreDespacho = (isset($Ses_Emp_Nom) && trim($Ses_Emp_Nom) !== '') ? trim($Ses_Emp_Nom) : (isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'DESPACHO CONTABLE');

$Con_Cod = isset($_POST['Con_Cod']) ? intval($_POST['Con_Cod']) : 0;
$jsonSeleccionados = isset($_POST['servicios_seleccionados']) ? trim($_POST['servicios_seleccionados']) : '';
$seleccionados = array();
if ($jsonSeleccionados !== '') {
    $dec = json_decode($jsonSeleccionados, true);
    if (is_array($dec)) {
        $seleccionados = $dec;
    }
}

if ($Con_Cod <= 0) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Contrato no indicado.'));
    exit;
}

$rowCliente = $obBD_con1->getRowConsulta(87, array('Con_Cod' => $Con_Cod), $obBD_conexion);
$clienteNombre = (isset($rowCliente['Cliente_Nombre']) ? trim($rowCliente['Cliente_Nombre']) : '');
$clienteRuc = (isset($rowCliente['RUC']) ? $rowCliente['RUC'] : '');

$repLegalNombre = '';
$repLegalDoc = '';
if ($Ses_Emp_Cod > 0) {
    $obBD_con1->setError(0, '');
    $rowRep = $obBD_con1->getRowConsulta(88, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    if (!empty($rowRep) && $obBD_con1->Error == 0) {
        $repLegalNombre = isset($rowRep['Representante_Nombre']) ? trim($rowRep['Representante_Nombre']) : '';
        $repLegalDoc = isset($rowRep['Representante_Identificacion']) ? trim($rowRep['Representante_Identificacion']) : '';
    }
}

$actividadesContrato = $obBD_con1->getArrayConsulta(16, array('Con_Cod' => $Con_Cod), $obBD_conexion);
if (!is_array($actividadesContrato)) $actividadesContrato = array();

try {
    $actividadesPrecios = $obBD_con1->getArrayConsulta(67, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
} catch (Exception $e) {
    $actividadesPrecios = array();
}
if (!is_array($actividadesPrecios)) $actividadesPrecios = array();

$actIdsEnContrato = array();
foreach ($actividadesContrato as $a) {
    $actIdsEnContrato[(int)$a['Act_Cod']] = true;
}

$porServicio = array();
$norm = function ($v) { return trim((string)(isset($v) ? $v : '')); };
foreach ($actividadesContrato as $a) {
    $serNom = $norm($a['Ser_Nombre']) !== '' ? $norm($a['Ser_Nombre']) : 'Otros';
    if (!isset($porServicio[$serNom])) $porServicio[$serNom] = array('incluidas' => array(), 'noIncluidas' => array());
    $nom = $norm($a['Act_Nombre']);
    if ($nom !== '') $porServicio[$serNom]['incluidas'][] = $nom;
}
foreach ($actividadesPrecios as $a) {
    $serNom = $norm($a['Ser_Nombre']) !== '' ? $norm($a['Ser_Nombre']) : 'Otros';
    if (!isset($porServicio[$serNom])) $porServicio[$serNom] = array('incluidas' => array(), 'noIncluidas' => array());
    $enContrato = isset($actIdsEnContrato[(int)$a['Act_Cod']]);
    $nom = $norm($a['Act_Nombre']);
    if ($enContrato) {
        if ($nom !== '' && !in_array($nom, $porServicio[$serNom]['incluidas'])) {
            $porServicio[$serNom]['incluidas'][] = $nom;
        }
    } else {
        if ($nom !== '') $porServicio[$serNom]['noIncluidas'][] = $nom;
    }
}

$fechaStr = date('d/m/Y');

$rutaFpdf = realpath(__DIR__ . '/../../Librerias/fpdf/fpdf.php');
if (!file_exists($rutaFpdf)) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Libreria PDF no disponible.'));
    exit;
}
require_once($rutaFpdf);

function pdf_enc($s) {
    if ($s === null || $s === '') return '';
    $s = (string)$s;
    if (function_exists('mb_convert_encoding')) {
        if (strpos($s, "\xC3\x83") !== false) {
            $fixed = @mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
            if ($fixed !== false) $s = $fixed;
        } elseif (function_exists('mb_check_encoding') && !@mb_check_encoding($s, 'UTF-8')) {
            $fixed = @mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
            if ($fixed !== false) $s = $fixed;
        }
    }
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
    return ($out !== false) ? $out : $s;
}

/** Calcula la altura que ocuparía un MultiCell sin dibujar (para evitar duplicar texto). */
function multicell_height(FPDF $pdf, $w, $lineH, $txt) {
    if ($txt === null || $txt === '') return $lineH;
    $n = 0;
    foreach (explode("\n", $txt) as $line) {
        $n += max(1, (int)ceil($pdf->GetStringWidth($line) / max(1, $w)));
    }
    return $n * $lineH;
}

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetMargins(18, 15, 18);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();
$pdf->SetFont('Helvetica', 'B', 14);
$pdf->SetTextColor(44, 93, 148);
$pdf->Cell(0, 8, pdf_enc($nombreDespacho), 0, 1, 'C');
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(0, 6, 'PROPUESTA DE SERVICIOS ADICIONALES', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Ln(2);
$pdf->Cell(0, 6, 'Cliente: ' . pdf_enc($clienteNombre), 0, 1);
$pdf->Cell(0, 6, 'RUC: ' . pdf_enc($clienteRuc), 0, 1);
$pdf->Cell(0, 6, 'Fecha: ' . $fechaStr, 0, 1);
$pdf->Ln(4);

$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(44, 93, 148);
$pdf->Cell(0, 6, '1. SERVICIOS CONTRATADOS VS SERVICIOS ADICIONALES', 0, 1);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 5, pdf_enc('A continuacion se detalla la comparacion entre los servicios incluidos en su contrato actual y los servicios adicionales disponibles:'), 0, 'L');
$pdf->Ln(2);

$w1 = 42;
$w2 = 72;
$w3 = 72;
$pdf->SetFillColor(44, 93, 148);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Helvetica', 'B', 8);
$pdf->Cell($w1, 7, pdf_enc('CATEGORIA'), 1, 0, 'L', true);
$pdf->Cell($w2, 7, pdf_enc('INCLUIDAS EN CONTRATO'), 1, 0, 'L', true);
$pdf->Cell($w3, 7, pdf_enc('NO INCLUIDAS'), 1, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', '', 8);
$lineH = 4;
$minRowH = 10;
ksort($porServicio);
foreach ($porServicio as $serNom => $inv) {
    $inclList = array_filter($inv['incluidas']);
    $noInclList = array_filter($inv['noIncluidas']);
    $incl = $inclList ? implode("\n", array_map(function ($n) { return '* ' . $n; }, $inclList)) : '-';
    $noIncl = $noInclList ? implode("\n", $noInclList) : '-';
    $encIncl = pdf_enc($incl);
    $encNoIncl = pdf_enc($noIncl);
    $y0 = $pdf->GetY();
    // Calcular altura del contenido sin dibujar (evita duplicar texto)
    $hIncl = multicell_height($pdf, $w2, $lineH, $encIncl);
    $hNoIncl = multicell_height($pdf, $w3, $lineH, $encNoIncl);
    $h = max($hIncl, $hNoIncl, $minRowH);
    // Bordes de la fila (las tres celdas con la misma altura)
    $pdf->SetXY(18, $y0);
    $pdf->Cell($w1, $h, '', 1, 0);
    $pdf->Cell($w2, $h, '', 1, 0);
    $pdf->Cell($w3, $h, '', 1, 1);
    // Categoría: centrada horizontal y verticalmente
    $pdf->SetXY(18, $y0 + ($h - $lineH) / 2);
    $pdf->Cell($w1, $lineH, pdf_enc($serNom), 0, 0, 'C');
    // INCLUIDAS: centrada verticalmente, texto a la izquierda (solo se dibuja una vez)
    $pdf->SetXY(18 + $w1, $y0 + ($h - $hIncl) / 2);
    $pdf->MultiCell($w2, $lineH, $encIncl, 0, 'L');
    // NO INCLUIDAS: centrada de alto, alineada a la izquierda (solo se dibuja una vez)
    $pdf->SetXY(18 + $w1 + $w2, $y0 + ($h - $hNoIncl) / 2);
    $pdf->MultiCell($w3, $lineH, $encNoIncl, 0, 'L');
    $pdf->SetXY(18, $y0 + $h);
}
$pdf->Ln(6);

$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(44, 93, 148);
$pdf->Cell(0, 6, '2. CATALOGO DE SERVICIOS ADICIONALES (SELECCIONADOS)', 0, 1);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 5, pdf_enc('Los siguientes servicios adicionales han sido seleccionados en esta propuesta. Los precios estan calculados segun el regimen de su empresa.'), 0, 'L');
$pdf->Ln(2);

if (empty($seleccionados)) {
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 6, pdf_enc('Ningun servicio adicional seleccionado.'), 0, 1);
} else {
    $pdf->SetFillColor(44, 93, 148);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->Cell(120, 7, pdf_enc('SERVICIO ADICIONAL'), 1, 0, 'L', true);
    $pdf->Cell(50, 7, 'PRECIO USD', 1, 1, 'R', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', '', 9);
    $ultimoSer = '';
    foreach ($seleccionados as $item) {
        $serNom = isset($item['Ser_Nombre']) ? $item['Ser_Nombre'] : '';
        if ($serNom !== '' && $serNom !== $ultimoSer) {
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(226, 232, 240);
            $pdf->Cell(170, 6, pdf_enc(strtoupper($serNom) . ' ADICIONALES'), 1, 1, 'L', true);
            $pdf->SetFont('Helvetica', '', 9);
            $ultimoSer = $serNom;
        }
        $nom = isset($item['Act_Nombre']) ? $item['Act_Nombre'] : '';
        $precio = isset($item['Precio']) ? floatval($item['Precio']) : 0;
        $pdf->Cell(120, 6, pdf_enc($nom), 1, 0, 'L');
        $pdf->Cell(50, 6, '$' . number_format($precio, 2), 1, 1, 'R');
    }
}

$pdf->Ln(6);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(90, 155, 212);
$pdf->Cell(0, 6, pdf_enc('CONDICIONES GENERALES'), 0, 1);
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 5, pdf_enc('* Los precios mostrados son referenciales y estan sujetos a la complejidad especifica de cada caso.'), 0, 'L');
$pdf->MultiCell(0, 5, pdf_enc('* Los servicios adicionales se facturaran unicamente cuando sean solicitados y previamente aprobados por el cliente.'), 0, 'L');
$pdf->MultiCell(0, 5, pdf_enc('* Este tarifario tiene vigencia durante el ano ' . date('Y') . ' y podra ser actualizado previa notificacion.'), 0, 'L');
$pdf->MultiCell(0, 5, pdf_enc('* Los precios no incluyen IVA.'), 0, 'L');
$pdf->MultiCell(0, 5, pdf_enc('* Para solicitar cualquiera de estos servicios, puede comunicarse con nosotros a traves de los canales habituales.'), 0, 'L');
$pdf->Ln(4);

$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(90, 155, 212);
$pdf->Cell(0, 6, pdf_enc('CONFIRMACION DE RECEPCION'), 0, 1);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 5, pdf_enc('Por favor, firme este documento como constancia de que ha recibido y conoce el listado de servicios adicionales y sus tarifas vigentes:'), 0, 'L');
$pdf->Ln(8);
$pdf->Cell(80, 1, '', 0, 0);
$pdf->Cell(80, 1, '', 0, 1);
$pdf->Cell(80, 6, '_________________________', 0, 0);
$pdf->Cell(80, 6, '_________________________', 0, 1);
$pdf->SetFont('Helvetica', '', 8);
$pdf->Cell(80, 5, pdf_enc('Firma del Cliente'), 0, 0);
$pdf->Cell(80, 5, pdf_enc('Firma del Despacho'), 0, 1);
$pdf->Cell(80, 5, pdf_enc($clienteNombre), 0, 0);
$pdf->Cell(80, 5, $repLegalNombre !== '' ? pdf_enc($repLegalNombre) : pdf_enc('[NOMBRE DEL REPRESENTANTE]'), 0, 1);
$pdf->Cell(80, 5, $clienteRuc !== '' ? pdf_enc('RUC: ' . $clienteRuc) : '', 0, 0);
$pdf->Cell(80, 5, $repLegalDoc !== '' ? pdf_enc('C.I./RUC: ' . $repLegalDoc) : '', 0, 1);

$nombreArchivo = 'Propuesta_Servicios_Adicionales_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $clienteNombre) . '_' . date('Y-m-d_His') . '.pdf';
$nombreArchivo = substr($nombreArchivo, 0, 80) . '.pdf';

while (ob_get_level()) ob_end_clean();
$pdf->Output($nombreArchivo, 'D');
