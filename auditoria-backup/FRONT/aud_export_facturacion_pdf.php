<?php
/**
 * Exportación PDF Reporte Facturación (archivo dedicado para evitar salida previa/BOM)
 * Uso: tipo=completo|contratadas|extras y Tar_Periodo=YYYY-MM o FechaDesde+FechaHasta; Criterio=A
 */
ob_start();
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/aud_log_despacho_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Despacho($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Despacho();
$Ses_Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;

$per = trim(isset($_GET['Tar_Periodo']) ? $_GET['Tar_Periodo'] : '');
$fdesde = trim(isset($_GET['FechaDesde']) ? $_GET['FechaDesde'] : '');
$fhasta = trim(isset($_GET['FechaHasta']) ? $_GET['FechaHasta'] : '');
$criterio = isset($_GET['Criterio']) ? $_GET['Criterio'] : 'A';
$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'completo';
$tienePeriodo = ($per !== '' || ($fdesde !== '' && $fhasta !== ''));
$etiquetaPer = $per ? $per : ($fdesde && $fhasta ? $fdesde . '_' . $fhasta : '');

if (!$tienePeriodo && $tipo !== 'contratadas') {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Indique periodo.'));
    exit;
}
$par41 = array('Emp_Cod' => $Ses_Emp_Cod, 'Criterio' => $criterio);
if ($fdesde !== '' && $fhasta !== '') { $par41['FechaDesde'] = $fdesde; $par41['FechaHasta'] = $fhasta; } elseif ($per !== '') { $par41['Tar_Periodo'] = $per; }

$rutaFpdf = realpath(dirname(__FILE__) . '/../../Librerias/fpdf/fpdf.php');
if (!file_exists($rutaFpdf)) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Libreria PDF no disponible.'));
    exit;
}
require_once($rutaFpdf);

$pdf = new FPDF($tipo === 'completo' ? 'L' : 'P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Helvetica', '', 10);

if ($tipo === 'contratadas') {
    $par = array('Emp_Cod' => $Ses_Emp_Cod);
    if ($per !== '') $par['Tar_Periodo'] = $per;
    $arr = $obBD_con1->getArrayConsulta(84, $par, $obBD_conexion);
    $arr = is_array($arr) ? $arr : array();
    $tot = 0;
    foreach ($arr as $r) { $tot += floatval(isset($r['Valor_Contrato']) ? $r['Valor_Contrato'] : 0); }
    $pdf->Cell(0, 8, 'Contratadas (Incluidas) - Periodo ' . ($per ?: $etiquetaPer), 0, 1);
    $pdf->Ln(2);
    $w = array(80, 45, 35);
    $heads = array('Cliente', 'Valor contrato', 'N contrato');
    for ($i = 0; $i < 3; $i++) $pdf->Cell($w[$i], 6, $heads[$i], 1, 0, 'C');
    $pdf->Ln();
    foreach ($arr as $r) {
        $c = isset($r['Cliente_Nombre']) ? $r['Cliente_Nombre'] : '';
        $pdf->Cell($w[0], 6, substr(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $c), 0, 48), 1, 0);
        $pdf->Cell($w[1], 6, isset($r['Valor_Contrato']) ? $r['Valor_Contrato'] : 0, 1, 0, 'R');
        $pdf->Cell($w[2], 6, isset($r['Con_Numero']) ? $r['Con_Numero'] : '', 1, 0, 'C');
        $pdf->Ln();
    }
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell($w[0], 6, 'TOTAL', 1, 0);
    $pdf->Cell($w[1], 6, number_format($tot, 2), 1, 0, 'R');
    $pdf->Cell($w[2], 6, '', 1, 0);
    $pdf->Ln();
} elseif ($tipo === 'extras') {
    $arr = $obBD_con1->getArrayConsulta(41, $par41, $obBD_conexion);
    $arr = is_array($arr) ? $arr : array();
    $arr = array_filter($arr, function ($r) {
        return (isset($r['Act_Facturable']) && strtoupper($r['Act_Facturable']) === 'S')
            && (empty($r['Incluida_Contrato']) || strtoupper($r['Incluida_Contrato']) !== 'S');
    });
    $totAct = 0;
    foreach ($arr as $r) { $totAct += floatval(isset($r['Act_Valor']) ? $r['Act_Valor'] : 0); }
    $pdf->Cell(0, 8, 'Extras (Actividades facturables) - Periodo ' . $etiquetaPer, 0, 1);
    $pdf->Ln(2);
    $w = array(50, 35, 60, 25);
    $heads = array('Cliente', 'Servicio', 'Actividad', 'Valor Act');
    for ($i = 0; $i < 4; $i++) $pdf->Cell($w[$i], 6, $heads[$i], 1, 0, 'C');
    $pdf->Ln();
    foreach ($arr as $r) {
        $pdf->Cell($w[0], 6, substr(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', isset($r['Cliente_Nombre']) ? $r['Cliente_Nombre'] : ''), 0, 30), 1, 0);
        $pdf->Cell($w[1], 6, substr(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', isset($r['Ser_Nombre']) ? $r['Ser_Nombre'] : ''), 0, 20), 1, 0);
        $pdf->Cell($w[2], 6, substr(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', isset($r['Act_Nombre']) ? $r['Act_Nombre'] : ''), 0, 38), 1, 0);
        $pdf->Cell($w[3], 6, isset($r['Act_Valor']) ? $r['Act_Valor'] : 0, 1, 0, 'R');
        $pdf->Ln();
    }
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell($w[0] + $w[1] + $w[2], 6, 'TOTAL', 1, 0);
    $pdf->Cell($w[3], 6, number_format($totAct, 2), 1, 0, 'R');
    $pdf->Ln();
} else {
    $arr = $obBD_con1->getArrayConsulta(41, $par41, $obBD_conexion);
    $arr = is_array($arr) ? $arr : array();
    $totAct = 0;
    $totSer = 0;
    foreach ($arr as $r) {
        $totAct += floatval(isset($r['Act_Valor']) ? $r['Act_Valor'] : 0);
        $totSer += floatval(isset($r['Ser_Valor']) ? $r['Ser_Valor'] : 0);
    }
    $pdf->Cell(0, 8, 'Listado completo - Periodo ' . $etiquetaPer, 0, 1);
    $pdf->Ln(2);
    $w = array(45, 35, 55, 18, 22, 18, 22);
    $headers = array('Cliente', 'Servicio', 'Actividad', 'Fact.Act', 'Valor Act', 'Fact.Ser', 'Valor Ser');
    for ($i = 0; $i < count($headers); $i++) $pdf->Cell($w[$i], 6, $headers[$i], 1, 0, 'C');
    $pdf->Ln();
    foreach ($arr as $r) {
        $c = isset($r['Cliente_Nombre']) ? $r['Cliente_Nombre'] : '';
        $s = isset($r['Ser_Nombre']) ? $r['Ser_Nombre'] : '';
        $a = isset($r['Act_Nombre']) ? $r['Act_Nombre'] : '';
        $fa = isset($r['Act_Facturable']) ? $r['Act_Facturable'] : 'N';
        $va = isset($r['Act_Valor']) ? $r['Act_Valor'] : 0;
        $fs = isset($r['Ser_Facturable']) ? $r['Ser_Facturable'] : 'N';
        $vs = isset($r['Ser_Valor']) ? $r['Ser_Valor'] : 0;
        $pdf->Cell($w[0], 6, substr(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $c), 0, 28), 1, 0);
        $pdf->Cell($w[1], 6, substr(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s), 0, 18), 1, 0);
        $pdf->Cell($w[2], 6, substr(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $a), 0, 32), 1, 0);
        $pdf->Cell($w[3], 6, $fa, 1, 0, 'C');
        $pdf->Cell($w[4], 6, $va, 1, 0, 'R');
        $pdf->Cell($w[5], 6, $fs, 1, 0, 'C');
        $pdf->Cell($w[6], 6, $vs, 1, 0, 'R');
        $pdf->Ln();
    }
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell($w[0] + $w[1] + $w[2] + $w[3], 6, 'TOTAL', 1, 0);
    $pdf->Cell($w[4], 6, number_format($totAct, 2), 1, 0, 'R');
    $pdf->Cell($w[5], 6, '', 1, 0);
    $pdf->Cell($w[6], 6, number_format($totSer, 2), 1, 0, 'R');
    $pdf->Ln();
}

$sufijo = $tipo === 'contratadas' ? 'Contratadas' : ($tipo === 'extras' ? 'Extras' : 'Completo');
$nombre = 'Facturacion_' . $sufijo . '_' . ($per ?: $etiquetaPer) . '_' . date('Y-m-d_His') . '.pdf';

while (ob_get_level()) ob_end_clean();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nombre . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdf->Output('S');
