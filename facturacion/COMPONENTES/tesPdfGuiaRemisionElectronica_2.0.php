<?php
/*
 * $datoXml contiene el array xml
 */
$documento = "GUIA  DE  REMISIÓN";
include dirname(__file__) . '/../COMPONENTES/tesPdfGlobal.php';

// Función para corregir la codificación de caracteres especiales
function fixEncoding($text)
{
    if (empty($text)) return $text;
    // Detectar si el texto está en UTF-8 y convertir a ISO-8859-1
    if (mb_detect_encoding($text, 'UTF-8', true) === 'UTF-8') {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    }
    return $text;
}

/* Inicio Datos Dedicados */
$infoGuia = $datoXml['infoGuiaRemision'];
$pdf->setField(128, 25, $documento, 'B', 12);
$pdf->setField(38, 75, issetText($infoGuia, 'dirEstablecimiento'), '', 8, null, 81);
$pdf->setField(70, 80, issetText($infoGuia, 'contribuyenteEspecial', 'NO'), '', 8);
$pdf->setField(70, 85, issetText($infoGuia, 'obligadoContabilidad', 'NO'), '', 8);

/* RECTANGULO INFO TRANSPORTISTA*/
$pdf->RoundedRect(10, 98, 194, 27, 0, '');
/* DATOS INFO TRANSPORTISTA */
$pdf->setField(13, 103, "IDENTIFICACIÓN (TRANSPORTÍSTA):", 'B', 8);
$pdf->setField(75, 103, $infoGuia['rucTransportista'], '', 8);
$pdf->setField(13, 108, "RAZÓN SOCIAL / NOMBRES Y APELLIDOS:", 'B', 8);
$pdf->setField(75, 108, $infoGuia['razonSocialTransportista'], '', 8);
$pdf->setField(135, 108, "FECHA EMISION:", 'B', 8);
$pdf->setField(165, 108, $infoGuia['fechaIniTransporte'], '', 8);
$pdf->setField(13, 113, "PLACA:", 'B', 8);
$pdf->setField(75, 113, $infoGuia['placa'], '', 8);
$pdf->setField(13, 118, "PUNTO DE PARTIDA:", 'B', 8);
$pdf->setField(75, 118, $infoGuia['dirPartida'], '', 8, null, 127);
$pdf->setField(13, 123, "FECHA SALIDA TRANSPORTE:", 'B', 8);
$pdf->setField(115, 123, "FECHA LLEGADA TRANSPORTE:", 'B', 8);
$pdf->setField(75, 123, $infoGuia['fechaIniTransporte'], '', 8);
$pdf->setField(166, 123, $infoGuia['fechaFinTransporte'], '', 8);


$iapos = 129;
/* recorro los destinatarios */
foreach ($pdf->formatArray($datoXml['destinatarios']['destinatario']) as $desti) {

    if ($pdf->CheckPageBreak(54)) {
        $pdf->newMargin();
        $iapos = 10;
    }
    $pdf->setY($iapos);

    $detalles = $pdf->formatArray($desti['detalles']['detalle']);
    $intemPos = (count($detalles) + 1) * 5;
    $pdf->RectCof(10, $iapos - 2, 194, 54, 'TLR');
    ///$pdf->RoundedRect(10, $iapos-2 , 194, 53+$intemPos, 0, '');

    $pdf->setField(13, $iapos + 2, "COMPROBANTE DE VENTA:", 'B', 8);
    $pdf->setField(132, $iapos + 2, "FECHA DE EMISIÓN:", 'B', 8);
    $pdf->setField(13, $iapos + 7, "NÚMERO DE AUTORIZACIÓN:", 'B', 8);

    $pdf->setField(75, $iapos + 2, (!isset($desti['numDocSustento']) || empty($desti['numDocSustento'])) ? '' : selectDoc($desti['codDocSustento']) . " - " . $desti['numDocSustento'], '', 8);
    $pdf->setField(166, $iapos + 2, issetText($desti, 'fechaEmisionDocSustento'));
    $pdf->setField(75, $iapos + 7,  issetText($desti, 'numAutDocSustento'));

    $pdf->setField(13, $iapos + 12, "MOTIVO DE TRASLADO:", 'B', 8);
    $pdf->setField(75, $iapos + 12,/*strtoupper*/ ($desti['motivoTraslado']), '', 8, null, 127);

    $pdf->setField(13, $iapos + 17, "DESTINO (PUNTO DE LLEGADA):", 'B', 8);
    $pdf->setField(75, $iapos + 17,/*strtoupper*/ ($desti['dirDestinatario']), '', 8, null, 127);

    $pdf->setField(13, $iapos + 22, "IDENTIFICACIÓN (DESTINATARIO):", 'B', 8);
    $pdf->setField(75, $iapos + 22,/*strtoupper*/ ($desti['identificacionDestinatario']), '', 8);

    $pdf->setField(13, $iapos + 27, "RAZÓN SOCIAL / NOMBRES Y APELLIDOS:", 'B', 8);
    $pdf->setField(75, $iapos + 27,/*strtoupper*/ ($desti['razonSocialDestinatario']), '', 8);

    $pdf->setField(13, $iapos + 32, "DOCUMENTO ADUANERO:", 'B', 8);
    $pdf->setField(75, $iapos + 32,/*strtoupper*/ (issetText($desti, 'docAduaneroUnico')), '', 8);

    $pdf->setField(13, $iapos + 37, "CÓDIGO ESTABLECIMIENTO DESTINO:", 'B', 8);
    $pdf->setField(75, $iapos + 37,/*strtoupper*/ (issetText($desti, 'codEstabDestino')), '', 8);

    $pdf->setField(13, $iapos + 42, "RUTA:", 'B', 8);
    $pdf->setField(75, $iapos + 42,/*strtoupper*/ (issetText($desti, 'ruta')), '', 8, null, 127);

    /* Tabla Itemas */
    $pdf->setY($iapos + 47);
    $pdf->setX(12);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetCWidths(array(8, 22, 28, 28, 104));
    $pdf->SetCAligns(array());
    $pdf->Row(array('No', 'COD. PRINCIPAL', 'CANTIDAD', 'UNIDAD', 'DESCRIPCIÓN'), true, false);
    $pdf->SetCAligns(array("L", 'C', 'C', 'C', "L"));
    $pdf->SetFont('Arial', '', 8);
    /* recorro los items */
    $pdf->setX(12);
    /* foreach ($detalles as $i => $deta) {
        //$pdf->setX(12);
        $pdf->RectCof(10, $pdf->GetY(), 194, 5, 'LR');
        // $pdf->Row(array($i + 1, $deta['codigoInterno'], $deta['cantidad'], detallesAdi($pdf, $deta), $deta['descripcion'] . detallesAdi($pdf, $deta)), true, false);
        $pdf->Row(array($i + 1, $deta['codigoInterno'], $deta['cantidad'], obtenerUnidad_guia($deta), $deta['descripcion']), true, false);
    }*/
    foreach ($detalles as $i => $deta) {
        $description = fixEncoding($deta['descripcion']);
        $cellHeight = 5;
        $descWidth = 104;
        $startY = $pdf->GetY();
        $descStringWidth = $pdf->GetStringWidth($description);
        $lineCount = ceil($descStringWidth / $descWidth);
        $maxHeight = $lineCount * $cellHeight;
        $pdf->RectCof(10, $pdf->GetY(), 194, $maxHeight, 'LR');
        $cellHeight = $maxHeight;
        $pdf->setXY(12, $startY);
        $pdf->MultiCell(8, $cellHeight, $i + 1, 1, 'C', false);
        $pdf->setXY(20, $startY);
        $pdf->MultiCell(22, $cellHeight, $deta['codigoInterno'], 1, 'C', false);
        $pdf->setXY(42, $startY);
        $pdf->MultiCell(28, $cellHeight, $deta['cantidad'], 1, 'C', false);
        $pdf->setXY(70, $startY);
        $pdf->MultiCell(28, $cellHeight, obtenerUnidad_guia($deta), 1, 'C', false);
        $pdf->setXY(98, $startY);
        $pdf->MultiCell(104, 5, $description, 1, 'L', false);
        $pdf->SetY($startY + $cellHeight);
    }


    $pdf->RectCof(10, $pdf->GetY(), 194, 3, 'LRB');
    $iapos = $pdf->GetY() + 10;
}

/* Info Adicional */
if (isset($datoXml['infoAdicional'])) {
    infoAddi($pdf, $iapos, $datoXml['infoAdicional']['campoAdicional']);
}

/* Líneas para firmar */
$pdf->setY($iapos + 30);
$pdf->setY($pdf->GetY() + 8);
$pdf->setX(12);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(90, 5, '_______________________________', 0, 0, 'C');
$pdf->Cell(90, 5, '_______________________________', 0, 1, 'C');
$pdf->setX(12);
$pdf->Cell(90, 4, 'Recibido por', '', 0, 'C');
$pdf->Cell(90, 4, 'Transportista', '', 1, 'C');
$pdf->setX(12);
$pdf->Cell(90, 4, fixEncoding($desti['razonSocialDestinatario']), '', 0, 'C');
$pdf->Cell(90, 4, fixEncoding($infoGuia['razonSocialTransportista']), '', 1, 'C');


$pdf->Output($ruta . $code . '.pdf', $opPdf);
if ($opPdf != 'F' && $opPdf != 'S') exit();
