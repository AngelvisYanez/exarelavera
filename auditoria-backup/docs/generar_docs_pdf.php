<?php
/**
 * Genera la documentacion del modulo de auditoria (USO_AUDITORIA.md) como PDF.
 *
 * Uso:
 *   php auditoria/docs/generar_docs_pdf.php
 *
 * Salida:
 *   auditoria/docs/USO_AUDITORIA.pdf
 *
 * @package auditoria.docs
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

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

require_once dirname(__FILE__) . '/../../Librerias/fpdf/fpdf.php';

/**
 * Clase PDF para USO_AUDITORIA.md.
 *
 * Renderiza un subconjunto de Markdown: titulos, parrafos, listas,
 * bloques de codigo y tablas.
 */
class AudDocsPDF extends FPDF
{
	/** Margen izquierdo/derecho (mm). */
	private $mL = 14;
	/** Margen superior base (mm). */
	private $mT = 16;
	/** Azul corporativo exa-header (#254463). */
	private $blue = array(37, 68, 99);
	/** Azul oscuro de titulos. */
	private $blueDark = array(30, 58, 138);
	/** Gris de texto. */
	private $gray = array(71, 85, 105);
	/** Gris claro de borde de tabla. */
	private $border = array(203, 213, 225);
	/** Fondo de cabecera de tabla. */
	private $headFill = array(241, 245, 249);
	/** Fondo de bloques de codigo. */
	private $codeFill = array(248, 250, 252);

	public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
	{
		parent::FPDF($orientation, $unit, $size);
		$this->SetAutoPageBreak(true, 16);
		$this->SetMargins($this->mL, $this->mT, $this->mL);
		$this->SetTitle('Auditoria y Monitoreo en Exa Contable - RCET', true);
		$this->AliasNbPages();
	}

	function Header()
	{
		if ($this->PageNo() <= 1) {
			return;
		}
		$this->SetFillColor($this->blue[0], $this->blue[1], $this->blue[2]);
		$this->Rect(0, 0, $this->w, 9, 'F');
		$this->SetFont('helvetica', 'B', 9);
		$this->SetTextColor(255, 255, 255);
		$this->SetY(2.5);
		$this->Cell(0, 5, utf8_decode('Auditoria y Monitoreo en Exa Contable - RCET'), 0, 0, 'C');
		$this->SetY($this->mT);
	}

	function Footer()
	{
		$this->SetY(-13);
		$this->SetFont('helvetica', '', 8);
		$this->SetTextColor(120, 130, 145);
		$this->Cell(0, 5, utf8_decode('Pagina ') . $this->PageNo() . ' de {nb}', 0, 0, 'C');
	}

	/** Convierte texto UTF-8 a latin-1 y normaliza caracteres fuera de ISO-8859-1. */
	private function enc($txt)
	{
		$txt = str_replace(array("\xe2\x86\x92", "\xe2\x80\x94", "\xe2\x80\x93"), array('->', '-', '-'), $txt);
		return utf8_decode($txt);
	}

	/** Quita backticks y marca de negrita del texto plano de celda/parrafo. */
	private function clean($txt)
	{
		$txt = preg_replace('/`([^`]*)`/', '$1', $txt);
		$txt = preg_replace('/\*\*(.+?)\*\*/', '$1', $txt);
		return trim($txt);
	}

	/** Divide un texto en corridas inline: normal (N), negrita (B) o codigo (C). */
	private function inlineRuns($text)
	{
		$runs = array();
		$offset = 0;
		$re = '/\*\*(.+?)\*\*|`([^`]+)`/';
		if (preg_match_all($re, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
			foreach ($matches as $mm) {
				$start = $mm[0][1];
				if ($start > $offset) {
					$runs[] = array('t' => substr($text, $offset, $start - $offset), 's' => 'N');
				}
				if ($mm[1][0] !== '') {
					$runs[] = array('t' => $mm[1][0], 's' => 'B');
				} else {
					$runs[] = array('t' => $mm[2][0], 's' => 'C');
				}
				$offset = $start + strlen($mm[0][0]);
			}
			if ($offset < strlen($text)) {
				$runs[] = array('t' => substr($text, $offset), 's' => 'N');
			}
		} else {
			$runs[] = array('t' => $text, 's' => 'N');
		}
		return $runs;
	}

	/** Emite texto con formato inline respetando el ancho de pagina. */
	private function emitInline($text, $h = 5, $size = 10, $indent = 0)
	{
		$runs = $this->inlineRuns($text);
		$this->SetX($this->mL + $indent);
		foreach ($runs as $r) {
			if ($r['s'] === 'B') {
				$this->SetFont('helvetica', 'B', $size);
			} elseif ($r['s'] === 'C') {
				$this->SetFont('courier', '', $size - 1);
			} else {
				$this->SetFont('helvetica', '', $size);
			}
			$this->Write($h, $this->enc($r['t']));
		}
		$this->Ln($h * 1.1);
	}

	/** Numero de lineas (simulando wrap de palabras) que ocupa un texto. */
	private function countLines($txt, $w)
	{
		$words = explode(' ', $txt);
		$line = '';
		$n = 0;
		foreach ($words as $wr) {
			$test = ($line === '') ? $wr : $line . ' ' . $wr;
			if ($this->GetStringWidth($test) <= $w - 2) {
				$line = $test;
			} else {
				$n++;
				$line = $wr;
			}
		}
		if ($line !== '') {
			$n++;
		}
		return $n;
	}

	/** -- Bloques Markdown -- */

	private function bH1($text)
	{
		if ($this->GetY() > $this->PageBreakTrigger - 20) {
			$this->AddPage();
		}
		$this->Ln(2);
		$this->SetFont('helvetica', 'B', 16);
		$this->SetTextColor($this->blue[0], $this->blue[1], $this->blue[2]);
		$this->MultiCell(0, 8, $this->enc($text), 0, 'L');
		$this->SetDrawColor($this->blue[0], $this->blue[1], $this->blue[2]);
		$this->Line($this->mL, $this->GetY() + 1, $this->w - $this->mL, $this->GetY() + 1);
		$this->SetTextColor(30, 41, 59);
		$this->Ln(5);
	}

	private function bH2($text)
	{
		if ($this->GetY() > $this->PageBreakTrigger - 14) {
			$this->AddPage();
		}
		$this->Ln(3);
		$this->SetFont('helvetica', 'B', 13);
		$this->SetTextColor($this->blueDark[0], $this->blueDark[1], $this->blueDark[2]);
		$this->MultiCell(0, 7, $this->enc($text), 0, 'L');
		$this->SetDrawColor($this->border[0], $this->border[1], $this->border[2]);
		$this->Line($this->mL, $this->GetY() + 1, $this->w - $this->mL, $this->GetY() + 1);
		$this->SetTextColor(30, 41, 59);
		$this->Ln(4);
	}

	private function bH3($text)
	{
		if ($this->GetY() > $this->PageBreakTrigger - 12) {
			$this->AddPage();
		}
		$this->Ln(2);
		$this->SetFont('helvetica', 'B', 11.5);
		$this->SetTextColor($this->blueDark[0], $this->blueDark[1], $this->blueDark[2]);
		$this->MultiCell(0, 6.5, $this->enc($text), 0, 'L');
		$this->SetTextColor(30, 41, 59);
		$this->Ln(2);
	}

	private function bPar($text)
	{
		if ($this->GetY() > $this->PageBreakTrigger - 10) {
			$this->AddPage();
		}
		$this->emitInline($text, 4.8, 10);
		$this->Ln(1.5);
	}

	private function bUl($items)
	{
		foreach ($items as $it) {
			if ($this->GetY() > $this->PageBreakTrigger - 10) {
				$this->AddPage();
			}
			$txt = $this->clean($it);
			$this->emitInline('- ' . $txt, 4.8, 10, 3);
		}
		$this->Ln(1.5);
	}

	private function bOl($items)
	{
		$n = 1;
		foreach ($items as $it) {
			if ($this->GetY() > $this->PageBreakTrigger - 10) {
				$this->AddPage();
			}
			$txt = $this->clean($it);
			$this->emitInline($n . '. ' . $txt, 4.8, 10, 3);
			$n++;
		}
		$this->Ln(1.5);
	}

	private function bHr()
	{
		$this->Ln(1);
		$this->SetDrawColor(203, 213, 225);
		$this->Line($this->mL, $this->GetY(), $this->w - $this->mL, $this->GetY());
		$this->Ln(2);
	}

	private function bCode($code)
	{
		$w = $this->w - $this->mL * 2;
		$this->SetFont('courier', '', 8);
		$rawLines = preg_split('/\r\n|\n|\r/', rtrim($code, "\r\n"));
		$lines = array();
		foreach ($rawLines as $ln) {
			if ($ln === '') {
				$lines[] = ' ';
				continue;
			}
			while ($this->GetStringWidth($ln) > $w - 8) {
				$cut = $this->splitLine($ln, $w - 8);
				$lines[] = $cut['head'];
				$ln = $cut['tail'];
			}
			$lines[] = $ln;
		}
		$h = count($lines) * 4.6 + 6;
		if ($this->GetY() + $h > $this->PageBreakTrigger) {
			$this->AddPage();
		}
		$x = $this->mL;
		$y = $this->GetY();
		$this->SetFillColor($this->codeFill[0], $this->codeFill[1], $this->codeFill[2]);
		$this->Rect($x, $y, $w, $h, 'F');
		$this->SetFillColor($this->blue[0], $this->blue[1], $this->blue[2]);
		$this->Rect($x, $y, 1.6, $h, 'F');
		$this->SetTextColor(15, 23, 42);
		$this->SetXY($x + 4, $y + 3);
		foreach ($lines as $ln) {
			$this->SetFont('courier', '', 8);
			$this->Cell($w - 8, 4.6, $this->enc($ln), 0, 1, 'L');
		}
		$this->SetTextColor(30, 41, 59);
		$this->Ln(4.5);
	}

	/** Divide una linea larga a la mitad de ancho mas legible (recursivo simple). */
	private function splitLine($ln, $maxW)
	{
		$n = strlen($ln);
		$low = 1;
		$high = $n;
		while ($low < $high) {
			$mid = intval(($low + $high + 1) / 2);
			if ($this->GetStringWidth(substr($ln, 0, $mid)) <= $maxW) {
				$low = $mid;
			} else {
				$high = $mid - 1;
			}
		}
		return array('head' => substr($ln, 0, $low), 'tail' => substr($ln, $low));
	}

	private function parseRow($line)
	{
		$parts = explode('|', $line);
		array_shift($parts);
		array_pop($parts);
		$cells = array();
		foreach ($parts as $p) {
			$cells[] = $this->clean($p);
		}
		return $cells;
	}

	private function bTable($rows)
	{
		$head = $this->parseRow(array_shift($rows));
		array_shift($rows); // separador de markdown
		$cols = array(24, 84, 72);
		$lineH = 4.4;
		$usable = $this->w - $this->mL * 2;
		if (array_sum($cols) > $usable) {
			$cols = array_map(function ($c) use ($usable) {
				return $c * ($usable / array_sum($cols));
			}, $cols);
		}
		$colW = array();
		$x0 = $this->mL;
		$acc = $x0;
		foreach ($cols as $c) {
			$colW[] = $c;
		}
		$all = array_merge(array($head), array_map(array($this, 'parseRow'), $rows));

		foreach ($all as $rIdx => $cells) {
			if ($this->GetY() > $this->PageBreakTrigger - 14) {
				// repetimos cabecera si la fila cae en una pagina nueva
				$this->AddPage();
			}
			$rowH = 6;
			for ($i = 0; $i < count($cells); $i++) {
				$txt = isset($cells[$i]) ? $cells[$i] : '';
				$n = $this->countLines($txt, $colW[$i]);
				$rowH = max($rowH, $n * $lineH);
			}
			$rowH += 1.4;
			$isHead = ($rIdx === 0);
			$y0 = $this->GetY();
			$x = $x0;
			for ($i = 0; $i < count($cells); $i++) {
				$txt = utf8_decode(isset($cells[$i]) ? $cells[$i] : '');
				$this->SetXY($x, $y0);
				$this->SetFillColor($this->headFill[0], $this->headFill[1], $this->headFill[2]);
				$this->Rect($x, $y0, $colW[$i], $rowH, $isHead ? 'F' : '');
				$this->SetFont($isHead ? 'helvetica' : 'helvetica', $isHead ? 'B' : '', $isHead ? 8.5 : 8.5);
				$this->SetTextColor($isHead ? 51 : 30, $isHead ? 65 : 41, $isHead ? 85 : 59);
				$this->MultiCell($colW[$i] - 2, $lineH, $txt, 0, 'L');
				$x += $colW[$i];
			}
			$this->SetDrawColor($this->border[0], $this->border[1], $this->border[2]);
			$x = $x0;
			for ($i = 0; $i < count($cells); $i++) {
				$this->Rect($x, $y0, $colW[$i], $rowH, 'D');
				$x += $colW[$i];
			}
			$this->SetTextColor(30, 41, 59);
			$this->SetXY($x0, $y0 + $rowH);
		}
		$this->Ln(4);
	}

	/** Orquesta el parseo del Markdown. */
	public function renderMarkdown($path)
	{
		$lines = file($path);
		if ($lines === false) {
			throw new RuntimeException('No se pudo leer ' . $path);
		}
		$this->AddPage();
		$g = $this->GetY();
		$i = 0;
		$n = count($lines);
		while ($i < $n) {
			$line = rtrim($lines[$i], "\r\n");
			$trim = trim($line);
			if ($trim === '') {
				$i++;
				continue;
			}
			if (substr($line, 0, 3) === '```') {
				$code = '';
				$i++;
				while ($i < $n && substr(trim($lines[$i]), 0, 3) !== '```') {
					$code .= rtrim($lines[$i], "\r\n") . "\n";
					$i++;
				}
				$i++;
				$this->bCode($code);
				continue;
			}
			if (preg_match('/^\|.+\|\s*$/', $line)) {
				$rows = array();
				while ($i < $n && preg_match('/^\|.+\|\s*$/', rtrim($lines[$i], "\r\n"))) {
					$rows[] = rtrim($lines[$i], "\r\n");
					$i++;
				}
				$this->bTable($rows);
				continue;
			}
			if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
				$level = strlen($m[1]);
				$text = trim($m[2]);
				if ($level === 1) {
					$this->bH1($text);
				} elseif ($level === 2) {
					$this->bH2($text);
				} else {
					$this->bH3($text);
				}
				$i++;
				continue;
			}
			if (preg_match('/^-\s+(.+)$/', $line, $m)) {
				$items = array();
				while ($i < $n && preg_match('/^-\s+(.+)$/', rtrim($lines[$i], "\r\n"), $mm)) {
					$items[] = $mm[1];
					$i++;
				}
				$this->bUl($items);
				continue;
			}
			if (preg_match('/^\d+[.)]\s+(.+)$/', $line, $m)) {
				$items = array();
				while ($i < $n && preg_match('/^\d+[.)]\s+(.+)$/', rtrim($lines[$i], "\r\n"), $mm)) {
					$items[] = $mm[1];
					$i++;
				}
				$this->bOl($items);
				continue;
			}
			if ($trim === '---') {
				$this->bHr();
				$i++;
				continue;
			}
			// Parrafo: junta lineas consecutivas que no abren otro bloque
			$para = $line;
			$i++;
			while ($i < $n) {
				$nx = rtrim($lines[$i], "\r\n");
				$nt = trim($nx);
				if (
					$nt === '' ||
					preg_match('/^(#{1,6})\s/', $nx) ||
					preg_match('/^\|.+\|\s*$/', $nx) ||
					preg_match('/^-\s+/', $nx) ||
					preg_match('/^\d+[.)]\s+/', $nx) ||
					$nt === '---' ||
					substr($nx, 0, 3) === '```'
				) {
					break;
				}
				$para .= ' ' . trim($nx);
				$i++;
			}
			$this->bPar($para);
		}
	}
}

$dir = dirname(__FILE__);
$src = $dir . '/USO_AUDITORIA.md';
$out = $dir . '/USO_AUDITORIA.pdf';

$pdf = new AudDocsPDF();
$pdf->renderMarkdown($src);
$pdf->Output($out, 'F');
echo 'PDF generado: ' . realpath($out) . ' (' . number_format(filesize($out)) . ' bytes)' . PHP_EOL;