<?php
/**
 * Generador de Reporte PDF del Tablero Estadistico de Monitoreo de Auditoria.
 * Reutiliza el estilo del reporte comparativo (FPDF) para un unico periodo.
 * Aptos para visualizacion, descarga, adjunto por correo y envio por WhatsApp.
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

class AudPDFMonitoreo extends FPDF
{
	public $empresaNombre = "EXACONTABLE ERP";
	public $rangoTexto = "";
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
		$this->Cell(125, 8, utf8_decode('INFORME ESTADÍSTICO DE MONITOREO'), 0, 0, 'L');

		$this->SetFont('Helvetica', 'B', 9);
		$this->SetTextColor(71, 85, 105);
		$this->Cell(65, 8, utf8_decode('FECHA: ' . date('d/m/Y H:i')), 0, 1, 'R');

		$this->SetFont('Helvetica', '', 9);
		$this->SetTextColor(100, 116, 139);
		$this->Cell(190, 5, utf8_decode('Empresa: ' . $this->empresaNombre . ' | Emisor: ' . $this->usuarioEmisor), 0, 1, 'L');

		// Caja resumen de rango analizado
		$this->Ln(3);
		$this->SetFillColor(241, 245, 249);
		$this->SetDrawColor(203, 213, 225);
		$this->Rect(10, $this->GetY(), 190, 12, 'DF');

		$this->SetY($this->GetY() + 2);
		$this->SetFont('Helvetica', 'B', 9);
		$this->SetTextColor(30, 58, 138); // Azul oscuro
		$this->Cell(95, 8, utf8_decode('  Empresa: ' . $this->empresaNombre), 0, 0, 'L');
		$this->SetTextColor(5, 150, 105); // Verde esmeralda
		$this->Cell(95, 8, utf8_decode('Rango Analizado: ' . $this->rangoTexto), 0, 1, 'L');

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
 * Funcion principal que compila y genera el documento PDF del monitoreo.
 *
 * @param array  $datos Calculo del periodo (resumen, modulos, horarios,
 *                      usuarios_top, plantas_top, empresa, rango, usuario_emisor)
 * @param string $destino 'I' (inline), 'D' (descarga), 'F' (archivo en disco), 'S' (string)
 * @param string $rutaArchivo Ruta absoluta de destino cuando $destino = 'F'
 * @return string|bool
 */
function aud_generar_reporte_monitoreo_pdf($datos, $destino = 'I', $rutaArchivo = '')
{
	$pdf = new AudPDFMonitoreo('P', 'mm', 'A4');
	$pdf->AliasNbPages();

	$pdf->empresaNombre = isset($datos['empresa']) ? $datos['empresa'] : 'EXACONTABLE ERP';
	$pdf->rangoTexto = isset($datos['rango']) ? $datos['rango'] : '';
	$pdf->usuarioEmisor = isset($datos['usuario_emisor']) ? $datos['usuario_emisor'] : 'Auditor del Sistema';

	$pdf->AddPage();

	// 1. Resumen General de Actividad
	$pdf->SectionHeader('1. RESUMEN GENERAL DE ACTIVIDAD');

	$resumen = isset($datos['resumen']) ? $datos['resumen'] : array();

	$pdf->SetFont('Helvetica', 'B', 9);
	$pdf->SetFillColor(241, 245, 249);
	$pdf->SetTextColor(51, 65, 85);
	$pdf->Cell(100, 7, utf8_decode('Indicador'), 1, 0, 'L', true);
	$pdf->Cell(90, 7, utf8_decode('Valor del Período'), 1, 1, 'C', true);

	$pdf->SetFont('Helvetica', '', 9);
	$pdf->SetTextColor(15, 23, 42);
	$filasResumen = array(
		'Total de Movimientos' => isset($resumen['total']) ? $resumen['total'] : 0,
		'Operaciones de Ingresar (Insert)' => isset($resumen['insert']) ? $resumen['insert'] : 0,
		'Operaciones de Actualizar (Update)' => isset($resumen['update']) ? $resumen['update'] : 0,
		'Operaciones de Eliminar (Delete)' => isset($resumen['delete']) ? $resumen['delete'] : 0,
		'Usuarios Únicos' => isset($resumen['usuarios_unicos']) ? $resumen['usuarios_unicos'] : 0
	);
	foreach ($filasResumen as $titulo => $valor) {
		$pdf->Cell(100, 6, utf8_decode($titulo), 1, 0, 'L');
		$pdf->Cell(90, 6, utf8_decode(number_format($valor)), 1, 1, 'C');
	}

	// 2. Actividad por Módulo
	$pdf->Ln(2);
	$pdf->SectionHeader('2. ACTIVIDAD POR MÓDULO DEL SISTEMA');

	$pdf->SetFont('Helvetica', 'B', 9);
	$pdf->SetFillColor(241, 245, 249);
	$pdf->SetTextColor(51, 65, 85);
	$pdf->Cell(130, 7, utf8_decode('Módulo'), 1, 0, 'L', true);
	$pdf->Cell(60, 7, utf8_decode('Movimientos'), 1, 1, 'C', true);

	$modulos = isset($datos['modulos']) ? $datos['modulos'] : array();
	$pdf->SetFont('Helvetica', '', 9);
	if (empty($modulos)) {
		$pdf->SetTextColor(148, 163, 184);
		$pdf->Cell(190, 6, utf8_decode('Sin registros de actividad en el período analizado.'), 1, 1, 'C');
	} else {
		$pdf->SetTextColor(15, 23, 42);
		foreach ($modulos as $m) {
			$pdf->Cell(130, 6, utf8_decode($m['modulo']), 1, 0, 'L');
			$pdf->Cell(60, 6, utf8_decode(number_format($m['total'])), 1, 1, 'C');
		}
	}

	// 3. Distribución por Franja Horaria
	$pdf->Ln(2);
	$pdf->SectionHeader('3. DISTRIBUCIÓN POR FRANJA HORARIA');

	$horarios = isset($datos['horarios']) ? $datos['horarios'] : array();
	$franjasActivas = array();
	foreach ($horarios as $h) {
		if (isset($h['total']) && (int)$h['total'] > 0) {
			$franjasActivas[] = $h;
		}
	}
	$mostrarFranjas = !empty($franjasActivas) ? $franjasActivas : array_slice($horarios, 0, 8);

	$pdf->SetFont('Helvetica', 'B', 9);
	$pdf->SetFillColor(241, 245, 249);
	$pdf->SetTextColor(51, 65, 85);
	$pdf->Cell(95, 7, utf8_decode('Hora'), 1, 0, 'L', true);
	$pdf->Cell(95, 7, utf8_decode('Operaciones'), 1, 1, 'C', true);

	$pdf->SetFont('Helvetica', '', 9);
	$pdf->SetTextColor(15, 23, 42);
	if (empty($mostrarFranjas)) {
		$pdf->SetTextColor(148, 163, 184);
		$pdf->Cell(190, 6, utf8_decode('Sin actividad horaria registrada.'), 1, 1, 'C');
	} else {
		foreach ($mostrarFranjas as $h) {
			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(95, 6, utf8_decode(isset($h['hora']) ? $h['hora'] : '-'), 1, 0, 'L');
			$pdf->Cell(95, 6, utf8_decode(number_format($h['total'])), 1, 1, 'C');
		}
	}

	// 4. Usuarios con Mayor Actividad
	$pdf->Ln(2);
	$pdf->SectionHeader('4. USUARIOS CON MAYOR VOLUMEN DE OPERACIONES');

	$pdf->SetFont('Helvetica', 'B', 9);
	$pdf->SetFillColor(241, 245, 249);
	$pdf->SetTextColor(51, 65, 85);
	$pdf->Cell(65, 7, utf8_decode('Usuario / Empleado'), 1, 0, 'L', true);
	$pdf->Cell(35, 7, utf8_decode('Total Operaciones'), 1, 0, 'C', true);
	$pdf->Cell(30, 7, utf8_decode('Ingresar'), 1, 0, 'C', true);
	$pdf->Cell(30, 7, utf8_decode('Actualizar'), 1, 0, 'C', true);
	$pdf->Cell(30, 7, utf8_decode('Eliminar'), 1, 1, 'C', true);

	$pdf->SetFont('Helvetica', '', 9);
	$usuarios = isset($datos['usuarios_top']) ? $datos['usuarios_top'] : array();
	if (empty($usuarios)) {
		$pdf->SetTextColor(148, 163, 184);
		$pdf->Cell(190, 6, utf8_decode('Sin actividad de usuarios registrada.'), 1, 1, 'C');
	} else {
		foreach ($usuarios as $u) {
			$nom = trim((isset($u['nombre']) ? $u['nombre'] : 'Usuario'));
			if (strlen($nom) > 32) $nom = substr($nom, 0, 29) . '...';

			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(65, 6, utf8_decode($nom), 1, 0, 'L');
			$pdf->Cell(35, 6, utf8_decode(number_format($u['total'])), 1, 0, 'C');
			$pdf->Cell(30, 6, utf8_decode(number_format($u['insert'])), 1, 0, 'C');
			$pdf->Cell(30, 6, utf8_decode(number_format($u['update'])), 1, 0, 'C');

			if ((int)$u['delete'] > 0) {
				$pdf->SetTextColor(185, 28, 28);
				$pdf->SetFont('Helvetica', 'B', 9);
			} else {
				$pdf->SetTextColor(100, 116, 139);
			}
			$pdf->Cell(30, 6, utf8_decode(number_format($u['delete'])), 1, 1, 'C');
			$pdf->SetFont('Helvetica', '', 9);
		}
	}

	// 5. Plantas con Movimiento
	$pdf->Ln(2);
	$pdf->SectionHeader('5. PLANTAS DE BENEFICIO CON MOVIMIENTO');

	$plantas = isset($datos['plantas_top']) ? $datos['plantas_top'] : array();
	$pdf->SetFont('Helvetica', 'B', 9);
	$pdf->SetFillColor(241, 245, 249);
	$pdf->SetTextColor(51, 65, 85);
	$pdf->Cell(60, 7, utf8_decode('Planta'), 1, 0, 'L', true);
	$pdf->Cell(30, 7, utf8_decode('Usuarios'), 1, 0, 'C', true);
	$pdf->Cell(30, 7, utf8_decode('Total'), 1, 0, 'C', true);
	$pdf->Cell(25, 7, utf8_decode('Ingresar'), 1, 0, 'C', true);
	$pdf->Cell(25, 7, utf8_decode('Actualizar'), 1, 0, 'C', true);
	$pdf->Cell(20, 7, utf8_decode('Eliminar'), 1, 1, 'C', true);

	$pdf->SetFont('Helvetica', '', 9);
	if (empty($plantas)) {
		$pdf->SetTextColor(148, 163, 184);
		$pdf->Cell(190, 6, utf8_decode('Sin movimiento en plantas durante el período analizado.'), 1, 1, 'C');
	} else {
		foreach ($plantas as $p) {
			$nombreP = (isset($p['planta']) ? $p['planta'] : 'Planta');
			if (strlen($nombreP) > 26) $nombreP = substr($nombreP, 0, 23) . '...';

			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(60, 6, utf8_decode($nombreP), 1, 0, 'L');
			$pdf->Cell(30, 6, utf8_decode(number_format($p['usuarios'])), 1, 0, 'C');
			$pdf->Cell(30, 6, utf8_decode(number_format($p['total'])), 1, 0, 'C');
			$pdf->Cell(25, 6, utf8_decode(number_format($p['insert'])), 1, 0, 'C');
			$pdf->Cell(25, 6, utf8_decode(number_format($p['update'])), 1, 0, 'C');
			$pdf->Cell(20, 6, utf8_decode(number_format($p['delete'])), 1, 1, 'C');
		}
	}

	// Conclusiones
	$pdf->Ln(2);
	$pdf->SectionHeader('6. OBSERVACIONES DE AUDITORÍA');

	$obs = isset($datos['observaciones']) ? $datos['observaciones'] : array();
	if (empty($obs)) {
		$obs = array(
			'Se registró un total de ' . number_format(isset($resumen['total']) ? $resumen['total'] : 0) . ' movimientos en el período analizado.',
			'Se recomienda revisar periódicamente las operaciones de eliminación y actualización de registros críticos.'
		);
	}
	$pdf->SetFont('Helvetica', '', 9);
	$pdf->SetTextColor(30, 41, 59);
	foreach ($obs as $linea) {
		$pdf->Cell(5, 5, utf8_decode('• '), 0, 0, 'L');
		$pdf->MultiCell(185, 5, utf8_decode($linea), 0, 'L');
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
			$rutaArchivo = sys_get_temp_dir() . '/rep_monitoreo_' . time() . '.pdf';
		}
		$pdf->Output($rutaArchivo, 'F');
		return $rutaArchivo;
	} elseif ($destino === 'S') {
		return $pdf->Output('', 'S');
	} elseif ($destino === 'D') {
		$pdf->Output('Reporte_Monitoreo_Auditoria_' . date('Ymd_His') . '.pdf', 'D');
		exit;
	} else {
		$pdf->Output('Reporte_Monitoreo_Auditoria.pdf', 'I');
		exit;
	}
}