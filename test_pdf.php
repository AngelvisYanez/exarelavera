<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Probando TCPDF...\n";
require_once __DIR__ . '/Librerias/TCPDF/tcpdf.php';
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Prueba de TCPDF despues de parchear each()', 0, 1);
$pdf->Output(__DIR__ . '/test_tcpdf.pdf', 'F');
echo "TCPDF generado correctamente.\n";

echo "Probando mPDF...\n";
require_once __DIR__ . '/Librerias/MPDF57/mpdf.php';
$mpdf = new mPDF();
$mpdf->WriteHTML('<h1>Prueba de mPDF despues de parchear each()</h1>');
$mpdf->Output(__DIR__ . '/test_mpdf.pdf', 'F');
echo "mPDF generado correctamente.\n";

echo "Pruebas exitosas!\n";
