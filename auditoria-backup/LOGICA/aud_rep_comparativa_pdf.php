<?php
/**
 * Generador de Reporte PDF Comparativo de Auditoria y Actividad.
 * Utiliza FPDF para generar un informe profesional apto para visualizacion,
 * descarga, adjunto por correo y envio por WhatsApp.
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

require_once dirname(__FILE__) . "/../../Librerias/fpdf/fpdf.php";

class AudPDFComparativa extends FPDF
{
	public $empresaNombre = "EXACONTABLE ERP";
	public $periodoATexto = "";
	public $periodoBTexto = "";
	public $usuarioEmisor = "Administrador";

	public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
	{
		parent::FPDF($orientation, $unit, $size);
	}

	function Header()
	{
		// Barra superior
		$this->SetFillColor(30, 41, 59); // Slate 800
		$this->Rect(0, 0, 210, 10, 'F');

		$this->SetY(15);
		$this->SetFont('Helvetica', 'B', 15);
		$this->SetTextColor(15, 23, 42);
		$this->Cell(130, 8, utf8_decode('INFORME COMPARATIVO DE AUDITORÍA'), 0, 0, 'L');

		$this->SetFont('Helvetica', 'B', 9);
		$this->SetTextColor(71, 85, 105);
		$this->Cell(60, 8, utf8_decode('FECHA: ' . date('d/m/Y H:i')), 0, 1, 'R');

		$this->SetFont('Helvetica', '', 9);
		$this->SetTextColor(100, 116, 139);
		$this->Cell(190, 5, utf8_decode('Empresa: ' . $this->empresaNombre . ' | Emisor: ' . $this->usuarioEmisor), 0, 1, 'L');

		// Caja resumen de periodos
		$this->Ln(3);
		$this->SetFillColor(241, 245, 249);
		$this->SetDrawColor(203, 213, 225);
		$this->Rect(10, $this->GetY(), 190, 12, 'DF');

		$this->SetY($this->GetY() + 2);
		$this->SetFont('Helvetica', 'B', 9);
		$this->SetTextColor(30, 58, 138); // Azul oscuro
		$this->Cell(95, 8, utf8_decode('  Período A (Base): ' . $this->periodoATexto), 0, 0, 'L');
		$this->SetTextColor(5, 150, 105); // Verde esmeralda
		$this->Cell(95, 8, utf8_decode('Período B (Comparado): ' . $this->periodoBTexto), 0, 1, 'L');

		$this->Ln(5);
	}

	function Footer()
	{
		$this->SetY(-15);
		$this->SetFont('Helvetica', 'I', 8);
		$this->SetTextColor(148, 163, 184);
		$this->Cell(100, 10, utf8_decode('Reporte confidencial generado por el Sistema de Auditoría ERP'), 0, 0, 'L');
		$this->Cell(90, 10, utf8_decode('Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
	}

	function SectionHeader($titulo, $icono = '')
	{
		$this->Ln(3);
		$this->SetFont('Helvetica', 'B', 11);
		$this->SetTextColor(30, 41, 59);
		$this->SetFillColor(226, 232, 240);
		$this->Cell(190, 7, utf8_decode('  ' . ($icono ? $icono . ' ' : '') . $titulo), 0, 1, 'L', true);
		$this->Ln(2);
	}
}

/**
 * Funcion principal que compila y genera el documento PDF comparativo.
 *
 * @param array  $datos Comparativa calculada
 * @param string $destino 'I' (inline), 'D' (descarga), 'F' (archivo en disco), 'S' (string)
 * @param string $rutaArchivo Ruta absoluta de destino cuando $destino = 'F'
 * @return string|bool
 */
function aud_generar_reporte_comparativo_pdf($datos, $destino = 'I', $rutaArchivo = '')
{
	$pdf = new AudPDFComparativa('P', 'mm', 'A4');
	$pdf->AliasNbPages();

	$pdf->empresaNombre = isset($datos['empresa_nombre']) ? $datos['empresa_nombre'] : 'EXACONTABLE ERP';
	$pdf->periodoATexto = isset($datos['periodo_a_label']) ? $datos['periodo_a_label'] : 'Período A';
	$pdf->periodoBTexto = isset($datos['periodo_b_label']) ? $datos['periodo_b_label'] : 'Período B';
	$pdf->usuarioEmisor = isset($datos['usuario_emisor']) ? $datos['usuario_emisor'] : 'Auditor del Sistema';

	$pdf->AddPage();

	// 1. Resumen Ejecutivo (KPIs Comparativos)
	$pdf->SectionHeader('1. RESUMEN EJECUTIVO Y VARIACIÓN GENERAL');

	// Cabecera tabla KPIs
	$pdf->SetFont('Helvetica', 'B', 9);
	$pdf->SetFillColor(241, 245, 249);
	$pdf->SetTextColor(51, 65, 85);
	$pdf->Cell(70, 7, utf8_decode('Métrica / Indicador'), 1, 0, 'L', true);
	$pdf->Cell(40, 7, utf8_decode('Período A (Base)'), 1, 0, 'C', true);
	$pdf->Cell(40, 7, utf8_decode('Período B (Comparado)'), 1, 0, 'C', true);
	$pdf->Cell(40, 7, utf8_decode('Variación (%)'), 1, 1, 'C', true);

	$pdf->SetFont('Helvetica', '', 9);
	$kpis = isset($datos['kpis_comparativa']) ? $datos['kpis_comparativa'] : array();

	foreach ($kpis as $k) {
		$pdf->SetTextColor(15, 23, 42);
		$pdf->Cell(70, 6, utf8_decode($k['titulo']), 1, 0, 'L');
		$pdf->Cell(40, 6, utf8_decode((string)$k['valor_a']), 1, 0, 'C');
		$pdf->Cell(40, 6, utf8_decode((string)$k['valor_b']), 1, 0, 'C');

		$pct = isset($k['pct_cambio']) ? (float)$k['pct_cambio'] : 0.0;
		if ($pct > 0) {
			$pdf->SetTextColor(22, 101, 52); // Verde
			$txtPct = '+' . number_format($pct, 1) . ' %';
		} elseif ($pct < 0) {
			$pdf->SetTextColor(185, 28, 28); // Rojo
			$txtPct = number_format($pct, 1) . ' %';
		} else {
			$pdf->SetTextColor(100, 116, 139);
			$txtPct = '0.0 %';
		}
		$pdf->SetFont('Helvetica', 'B', 9);
		$pdf->Cell(40, 6, utf8_decode($txtPct), 1, 1, 'C');
		$pdf->SetFont('Helvetica', '', 9);
	}

	// 2. Desglose de Operaciones por Módulo
	$pdf->Ln(2);
	$pdf->SectionHeader('2. ACTIVIDAD COMPARADA POR MÓDULO');

	$pdf->SetFont('Helvetica', 'B', 9);
	$pdf->SetFillColor(241, 245, 249);
	$pdf->SetTextColor(51, 65, 85);
	$pdf->Cell(70, 7, utf8_decode('Módulo del Sistema'), 1, 0, 'L', true);
	$pdf->Cell(40, 7, utf8_decode('Movimientos Período A'), 1, 0, 'C', true);
	$pdf->Cell(40, 7, utf8_decode('Movimientos Período B'), 1, 0, 'C', true);
	$pdf->Cell(40, 7, utf8_decode('Variación'), 1, 1, 'C', true);

	$pdf->SetFont('Helvetica', '', 9);
	$modulos = isset($datos['modulos_comparativa']) ? $datos['modulos_comparativa'] : array();

	if (empty($modulos)) {
		$pdf->SetTextColor(148, 163, 184);
		$pdf->Cell(190, 6, utf8_decode('Sin registros de actividad en los períodos analizados.'), 1, 1, 'C');
	} else {
		foreach ($modulos as $m) {
			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(70, 6, utf8_decode($m['modulo']), 1, 0, 'L');
			$pdf->Cell(40, 6, number_format($m['total_a']), 1, 0, 'C');
			$pdf->Cell(40, 6, number_format($m['total_b']), 1, 0, 'C');

			$pctM = isset($m['pct_cambio']) ? (float)$m['pct_cambio'] : 0.0;
			if ($pctM > 0) {
				$pdf->SetTextColor(22, 101, 52);
				$txtPctM = '+' . number_format($pctM, 1) . ' %';
			} elseif ($pctM < 0) {
				$pdf->SetTextColor(185, 28, 28);
				$txtPctM = number_format($pctM, 1) . ' %';
			} else {
				$pdf->SetTextColor(100, 116, 139);
				$txtPctM = '=';
			}
			$pdf->SetFont('Helvetica', 'B', 9);
			$pdf->Cell(40, 6, utf8_decode($txtPctM), 1, 1, 'C');
			$pdf->SetFont('Helvetica', '', 9);
		}
	}

	// 3. Top Usuarios con Mayor Actividad
	$pdf->Ln(2);
	$pdf->SectionHeader('3. USUARIOS CON MAYOR VOLUMEN DE OPERACIONES (PERÍODO B)');

	$pdf->SetFont('Helvetica', 'B', 9);
	$pdf->SetFillColor(241, 245, 249);
	$pdf->SetTextColor(51, 65, 85);
	$pdf->Cell(65, 7, utf8_decode('Usuario / Empleado'), 1, 0, 'L', true);
	$pdf->Cell(35, 7, utf8_decode('Total Operaciones'), 1, 0, 'C', true);
	$pdf->Cell(30, 7, utf8_decode('Ingresar'), 1, 0, 'C', true);
	$pdf->Cell(30, 7, utf8_decode('Actualizar'), 1, 0, 'C', true);
	$pdf->Cell(30, 7, utf8_decode('Eliminar'), 1, 1, 'C', true);

	$pdf->SetFont('Helvetica', '', 9);
	$usuarios = isset($datos['usuarios_top_b']) ? $datos['usuarios_top_b'] : array();

	if (empty($usuarios)) {
		$pdf->SetTextColor(148, 163, 184);
		$pdf->Cell(190, 6, utf8_decode('Sin actividad de usuarios registrada.'), 1, 1, 'C');
	} else {
		foreach ($usuarios as $u) {
			$nom = trim($u['UsuarioNombre'] . ' ' . (isset($u['UsuarioApellido']) ? $u['UsuarioApellido'] : ''));
			if (strlen($nom) > 32) $nom = substr($nom, 0, 29) . '...';

			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(65, 6, utf8_decode($nom), 1, 0, 'L');
			$pdf->Cell(35, 6, number_format($u['Total_Operaciones']), 1, 0, 'C');
			$pdf->Cell(30, 6, number_format($u['Total_Inserciones']), 1, 0, 'C');
			$pdf->Cell(30, 6, number_format($u['Total_Modificaciones']), 1, 0, 'C');

			if ((int)$u['Total_Eliminaciones'] > 0) {
				$pdf->SetTextColor(185, 28, 28);
				$pdf->SetFont('Helvetica', 'B', 9);
			} else {
				$pdf->SetTextColor(100, 116, 139);
			}
			$pdf->Cell(30, 6, number_format($u['Total_Eliminaciones']), 1, 1, 'C');
			$pdf->SetFont('Helvetica', '', 9);
		}
	}

	// 4. Conclusiones y Observaciones de Auditoria
	$pdf->Ln(2);
	$pdf->SectionHeader('4. OBSERVACIONES Y ALERTAS DE AUDITORÍA');

	$observaciones = isset($datos['observaciones']) ? $datos['observaciones'] : array();
	if (empty($observaciones)) {
		$observaciones[] = 'El volumen general de actividad se mantiene dentro de los rangos operacionales normales.';
	}

	$pdf->SetFont('Helvetica', '', 9);
	$pdf->SetTextColor(30, 41, 59);
	foreach ($observaciones as $obs) {
		$pdf->Cell(5, 5, utf8_decode('• '), 0, 0, 'L');
		$pdf->MultiCell(185, 5, utf8_decode($obs), 0, 'L');
	}

	// Firma y Hash de Integridad
	$pdf->Ln(5);
	$hash = substr(hash('sha256', serialize($datos)), 0, 24);
	$pdf->SetFont('Courier', '', 8);
	$pdf->SetTextColor(148, 163, 184);
	$pdf->Cell(190, 4, utf8_decode('Certificado de Integridad de Auditoría: ' . strtoupper($hash)), 0, 1, 'R');

	// Salida segun destino
	if ($destino === 'F') {
		if (empty($rutaArchivo)) {
			$rutaArchivo = sys_get_temp_dir() . '/rep_auditoria_' . time() . '.pdf';
		}
		$pdf->Output($rutaArchivo, 'F');
		return $rutaArchivo;
	} elseif ($destino === 'S') {
		return $pdf->Output('', 'S');
	} elseif ($destino === 'D') {
		$pdf->Output('Reporte_Auditoria_Comparativa_' . date('Ymd_His') . '.pdf', 'D');
		exit;
	} else {
		$pdf->Output('Reporte_Auditoria_Comparativa.pdf', 'I');
		exit;
	}
}
