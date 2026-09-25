<?php
/**
 * Base visual compartida para reportes PDF de Auditoria (estilo Monitor de Actividades).
 *
 * @package auditoria.LOGICA
 */

if (!function_exists('get_magic_quotes_runtime')) {
	function get_magic_quotes_runtime() {
		return false;
	}
}
if (!function_exists('set_magic_quotes_runtime')) {
	function set_magic_quotes_runtime($new_setting) {
		return false;
	}
}

if (!class_exists('FPDF')) {
	require_once dirname(__FILE__) . '/../../Librerias/fpdf/fpdf.php';
}

if (!class_exists('AudPDFAuditoriaBase')) {
class AudPDFAuditoriaBase extends FPDF
{
	public $empresaNombre = 'EXACONTABLE ERP';
	public $usuarioEmisor = 'Administrador';
	public $tituloInforme = 'INFORME DE AUDITORÍA';
	public $subtituloCajaIzq = '';
	public $subtituloCajaDer = '';
	public $colorCajaIzq = array(30, 58, 138);
	public $colorCajaDer = array(5, 150, 105);

	public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
	{
		parent::FPDF($orientation, $unit, $size);
	}

	function Header()
	{
		// Barra superior (mismo tono del Monitor de Actividades)
		$this->SetFillColor(30, 41, 59); // Slate 800
		$this->Rect(0, 0, 210, 10, 'F');

		$this->SetY(15);
		$this->SetFont('Helvetica', 'B', 15);
		$this->SetTextColor(15, 23, 42);
		$this->Cell(125, 8, utf8_decode($this->tituloInforme), 0, 0, 'L');

		$this->SetFont('Helvetica', 'B', 9);
		$this->SetTextColor(71, 85, 105);
		$this->Cell(65, 8, utf8_decode('FECHA: ' . date('d/m/Y H:i')), 0, 1, 'R');

		$this->SetFont('Helvetica', '', 9);
		$this->SetTextColor(100, 116, 139);
		$this->Cell(190, 5, utf8_decode('Empresa: ' . $this->empresaNombre . ' | Emisor: ' . $this->usuarioEmisor), 0, 1, 'L');

		$this->Ln(3);
		$this->SetFillColor(241, 245, 249);
		$this->SetDrawColor(203, 213, 225);
		$this->Rect(10, $this->GetY(), 190, 12, 'DF');

		$this->SetY($this->GetY() + 2);
		$this->SetFont('Helvetica', 'B', 9);
		$this->SetTextColor($this->colorCajaIzq[0], $this->colorCajaIzq[1], $this->colorCajaIzq[2]);
		$this->Cell(95, 8, utf8_decode('  ' . $this->subtituloCajaIzq), 0, 0, 'L');
		$this->SetTextColor($this->colorCajaDer[0], $this->colorCajaDer[1], $this->colorCajaDer[2]);
		$this->Cell(95, 8, utf8_decode($this->subtituloCajaDer), 0, 1, 'L');

		$this->Ln(5);
	}

	function Footer()
	{
		$this->SetY(-15);
		$this->SetFont('Helvetica', 'I', 8);
		$this->SetTextColor(148, 163, 184);
		$this->Cell(100, 10, utf8_decode('Reporte confidencial generado por el Sistema de Auditoría EXA ERP RELAVERA'), 0, 0, 'L');
		$this->Cell(90, 10, utf8_decode('Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
	}

	function SectionHeader($titulo)
	{
		$this->Ln(3);
		$this->SetFont('Helvetica', 'B', 11);
		$this->SetTextColor(30, 41, 59);
		$this->SetFillColor(226, 232, 240);
		$this->Cell(190, 7, utf8_decode('  ' . $titulo), 0, 1, 'L', true);
		$this->Ln(2);
	}

	function TableHeaderRow($cols)
	{
		$this->SetFont('Helvetica', 'B', 9);
		$this->SetFillColor(241, 245, 249);
		$this->SetTextColor(51, 65, 85);
		$this->SetDrawColor(203, 213, 225);
		$n = count($cols);
		for ($i = 0; $i < $n; $i++) {
			$c = $cols[$i];
			$ln = ($i === $n - 1) ? 1 : 0;
			$this->Cell($c[0], 7, utf8_decode($c[1]), 1, $ln, isset($c[2]) ? $c[2] : 'C', true);
		}
	}

	function EmptyRow($w, $msg)
	{
		$this->SetFont('Helvetica', '', 9);
		$this->SetTextColor(148, 163, 184);
		$this->Cell($w, 6, utf8_decode($msg), 1, 1, 'C');
	}

	function IntegrityHash($datos)
	{
		$this->Ln(5);
		$hash = substr(hash('sha256', serialize($datos)), 0, 24);
		$this->SetFont('Courier', '', 8);
		$this->SetTextColor(148, 163, 184);
		$this->Cell(190, 4, utf8_decode('Certificado de Integridad de Auditoría: ' . strtoupper($hash)), 0, 1, 'R');
	}

	function KpiCards($items)
	{
		// Filas de 3 tarjetas visuales
		$w = 60;
		$h = 16;
		$gap = 5;
		$x0 = 10;
		$i = 0;
		$yStart = $this->GetY();
		foreach ($items as $it) {
			$col = $i % 3;
			$row = (int)floor($i / 3);
			$x = $x0 + $col * ($w + $gap);
			$y = $yStart + $row * ($h + 4);
			$this->SetXY($x, $y);
			$this->SetDrawColor(203, 213, 225);
			$this->SetFillColor(248, 250, 252);
			$this->Rect($x, $y, $w, $h, 'DF');
			// barra lateral de color
			$rgb = isset($it['rgb']) ? $it['rgb'] : array(37, 68, 99);
			$this->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
			$this->Rect($x, $y, 2.2, $h, 'F');
			$this->SetXY($x + 4, $y + 2);
			$this->SetFont('Helvetica', '', 7);
			$this->SetTextColor(100, 116, 139);
			$this->Cell($w - 6, 4, utf8_decode($it['lbl']), 0, 2, 'L');
			$this->SetFont('Helvetica', 'B', 12);
			$this->SetTextColor(15, 23, 42);
			$this->Cell($w - 6, 7, utf8_decode((string)$it['val']), 0, 0, 'L');
			$i++;
		}
		$rows = (int)ceil(count($items) / 3);
		$this->SetY($yStart + $rows * ($h + 4) + 2);
	}
}
}

if (!function_exists('aud_pdf_acotar')) {
	function aud_pdf_acotar($texto, $max)
	{
		$texto = trim((string)$texto);
		if (strlen($texto) > (int)$max) {
			$texto = substr($texto, 0, max(0, (int)$max - 3)) . '...';
		}
		return $texto;
	}
}

if (!function_exists('aud_pdf_fmt_num')) {
	function aud_pdf_fmt_num($v, $decs = 0)
	{
		return number_format((float)$v, $decs, ',', '.');
	}
}
