<?php
/**
 * Generador de Reporte PDF Oficial para el Registro de Actividades y Transacciones de Auditoria.
 * Diseno apaisado (Landscape A4) de alta densidad y claridad ejecutiva.
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
	public $sucursalNombre = "Todas las Sucursales";
	public $periodoTexto = "";
	public $usuarioEmisor = "Administrador";
	public $filtrosTexto = "";
	public $totalRegistros = 0;
	public $kpiTotales = array('total' => 0, 'ins' => 0, 'upd' => 0, 'del' => 0);

	public function __construct($orientation = 'L', $unit = 'mm', $size = 'A4')
	{
		parent::FPDF($orientation, $unit, $size);
	}

	function Header()
	{
		// Barra superior corporativa (Azul #254463)
		$this->SetFillColor(37, 68, 99);
		$this->Rect(0, 0, 297, 10, 'F');

		$this->SetY(13);
		$this->SetFont('Helvetica', 'B', 14);
		$this->SetTextColor(30, 41, 59);
		$this->Cell(185, 7, utf8_decode('REGISTRO DE ACTIVIDADES Y TRANSACCIONES'), 0, 0, 'L');

		$this->SetFont('Helvetica', 'B', 9);
		$this->SetTextColor(100, 116, 139);
		$this->Cell(92, 7, utf8_decode('EMISI\xd3N: ' . date('d/m/Y H:i:s')), 0, 1, 'R');

		// Fila de metadatos de auditoria
		$this->SetFont('Helvetica', '', 8.5);
		$this->SetTextColor(71, 85, 105);
		$infoEmp = 'Empresa: ' . $this->empresaNombre;
		if ($this->sucursalNombre && $this->sucursalNombre !== 'Todas las Sucursales') {
			$infoEmp .= ' | Sucursal: ' . $this->sucursalNombre;
		}
		$infoEmp .= ' | Emisor: ' . $this->usuarioEmisor;
		$this->Cell(277, 5, utf8_decode($infoEmp), 0, 1, 'L');

		// Caja de rango temporal y resumen de operaciones
		$this->Ln(1);
		$this->SetFillColor(248, 250, 252);
		$this->SetDrawColor(203, 213, 225);
		$this->Rect(10, $this->GetY(), 277, 11, 'DF');

		$this->SetY($this->GetY() + 1.5);
		$this->SetFont('Helvetica', 'B', 8.5);
		$this->SetTextColor(30, 58, 138);
		$this->Cell(110, 8, utf8_decode('  Per\xedodo Consultado: ' . $this->periodoTexto), 0, 0, 'L');

		// Mini KPIs en la cabecera
		$this->SetFont('Helvetica', 'B', 8.5);
		$this->SetTextColor(15, 23, 42);
		$this->Cell(38, 8, utf8_decode('Total: ' . number_format($this->kpiTotales['total'])), 0, 0, 'C');
		$this->SetTextColor(22, 101, 52); // Verde Ingresos
		$this->Cell(42, 8, utf8_decode('Ingresar: ' . number_format($this->kpiTotales['ins'])), 0, 0, 'C');
		$this->SetTextColor(180, 83, 9); // Ambar Actualizaciones
		$this->Cell(42, 8, utf8_decode('Actualizar: ' . number_format($this->kpiTotales['upd'])), 0, 0, 'C');
		$this->SetTextColor(185, 28, 28); // Rojo Eliminaciones
		$this->Cell(45, 8, utf8_decode('Eliminar: ' . number_format($this->kpiTotales['del'])), 0, 1, 'C');

		$this->Ln(3);

		// Encabezados de la tabla
		$this->SetFont('Helvetica', 'B', 8);
		$this->SetFillColor(226, 232, 240); // Slate 200
		$this->SetDrawColor(148, 163, 184);
		$this->SetTextColor(30, 41, 59);

		// Anchos total: 14 + 18 + 15 + 32 + 45 + 23 + 35 + 95 = 277 mm
		$this->Cell(14, 6, utf8_decode('#'), 1, 0, 'C', true);
		$this->Cell(18, 6, utf8_decode('Fecha'), 1, 0, 'C', true);
		$this->Cell(15, 6, utf8_decode('Hora'), 1, 0, 'C', true);
		$this->Cell(32, 6, utf8_decode('M\xf3dulo'), 1, 0, 'L', true);
		$this->Cell(45, 6, utf8_decode('Directorio / Proceso'), 1, 0, 'L', true);
		$this->Cell(23, 6, utf8_decode('Operaci\xf3n'), 1, 0, 'C', true);
		$this->Cell(35, 6, utf8_decode('Usuario'), 1, 0, 'L', true);
		$this->Cell(95, 6, utf8_decode('Detalle / Afectaci\xf3n'), 1, 1, 'L', true);
	}

	function Footer()
	{
		$this->SetY(-13);
		$this->SetFont('Helvetica', 'I', 8);
		$this->SetTextColor(148, 163, 184);
		$this->Cell(140, 8, utf8_decode('Documento oficial de auditor\xeda y control interno - ExaContable ERP'), 0, 0, 'L');
		$this->Cell(137, 8, utf8_decode('P\xe1gina ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
	}
}

/**
 * Genera el documento PDF de actividades y transacciones.
 *
 * @param array  $filtroData Metadatos de cabecera (empresa, periodo, emisor)
 * @param array  $filas      Registros de auditoria a listar
 * @param string $destino    'I' (inline), 'D' (descarga), 'F' (disco), 'S' (string)
 * @param string $rutaSalida Ruta de destino si $destino = 'F'
 * @return string|bool
 */
function aud_generar_reporte_monitoreo_pdf($filtroData, $filas, $destino = 'I', $rutaSalida = '')
{
	$pdf = new AudPDFMonitoreo('L', 'mm', 'A4');
	$pdf->AliasNbPages();

	$pdf->empresaNombre = isset($filtroData['empresa_nombre']) ? $filtroData['empresa_nombre'] : 'EXACONTABLE ERP';
	$pdf->sucursalNombre = isset($filtroData['sucursal_nombre']) ? $filtroData['sucursal_nombre'] : 'Todas las Sucursales';
	$pdf->periodoTexto = isset($filtroData['periodo_label']) ? $filtroData['periodo_label'] : 'Hist\xf3rico';
	$pdf->usuarioEmisor = isset($filtroData['usuario_emisor']) ? $filtroData['usuario_emisor'] : 'Administrador';
	$pdf->totalRegistros = count($filas);

	// Calcular conteos de eventos
	$ins = 0; $upd = 0; $del = 0;
	if (is_array($filas)) {
		foreach ($filas as $r) {
			$e = strtoupper(trim(isset($r['Eve_Ini']) ? $r['Eve_Ini'] : (isset($r['Eve_Nom']) ? substr($r['Eve_Nom'], 0, 1) : '')));
			if ($e === 'I') $ins++;
			elseif ($e === 'U') $upd++;
			elseif ($e === 'D') $del++;
		}
	}
	$pdf->kpiTotales = array(
		'total' => count($filas),
		'ins' => $ins,
		'upd' => $upd,
		'del' => $del
	);

	$pdf->SetMargins(10, 10, 10);
	$pdf->SetAutoPageBreak(true, 15);
	$pdf->AddPage();

	$pdf->SetFont('Helvetica', '', 7.5);
	$fill = false;
	$pdf->SetDrawColor(226, 232, 240);

	if (empty($filas)) {
		$pdf->SetTextColor(100, 116, 139);
		$pdf->Cell(277, 10, utf8_decode('No se encontraron registros de auditor\xeda para los filtros seleccionados.'), 1, 1, 'C');
	} else {
		foreach ($filas as $r) {
			// Alternar color de fondo
			if ($fill) {
				$pdf->SetFillColor(248, 250, 252);
			} else {
				$pdf->SetFillColor(255, 255, 255);
			}

			$id = isset($r['Log_Cod']) ? (string)$r['Log_Cod'] : '';
			$fecHora = isset($r['Log_Fec']) ? $r['Log_Fec'] : '';
			$fecParts = explode(' ', $fecHora);
			$fec = isset($fecParts[0]) ? $fecParts[0] : '';
			$hor = isset($fecParts[1]) ? $fecParts[1] : (isset($r['Log_Hor']) ? $r['Log_Hor'] : '');

			$mod = isset($r['Mod_Nom']) ? $r['Mod_Nom'] : (function_exists('aud_nombre_modulo') ? aud_nombre_modulo($r) : '');
			$dir = isset($r['Dir_Nom']) ? $r['Dir_Nom'] : (function_exists('aud_nombre_directorio') ? aud_nombre_directorio($r) : '');
			$pcs = isset($r['Pcs_Nom']) ? $r['Pcs_Nom'] : (function_exists('aud_nombre_proceso') ? aud_nombre_proceso($r) : '');
			$dirPcs = ($dir !== '' ? ($dir . ' > ') : '') . ($pcs !== '' ? $pcs : 'General');

			// Operacion normalizada: Ingresar, Actualizar, Eliminar
			$eveIni = strtoupper(trim(isset($r['Eve_Ini']) ? $r['Eve_Ini'] : (isset($r['Eve_Nom']) ? substr($r['Eve_Nom'], 0, 1) : '')));
			$eveNom = 'Otro';
			$colorOp = array(71, 85, 105);
			if ($eveIni === 'I') {
				$eveNom = 'Ingresar';
				$colorOp = array(22, 101, 52);
			} elseif ($eveIni === 'U') {
				$eveNom = 'Actualizar';
				$colorOp = array(180, 83, 9);
			} elseif ($eveIni === 'D') {
				$eveNom = 'Eliminar';
				$colorOp = array(185, 28, 28);
			}

			$usu = isset($r['Usu_Nom']) ? $r['Usu_Nom'] : (function_exists('aud_nombre_usuario') ? aud_nombre_usuario($r) : '');
			$det = isset($r['Log_Des']) ? $r['Log_Des'] : (isset($r['Log_Val']) ? $r['Log_Val'] : '');
			$det = str_replace(array("\r\n", "\r", "\n"), ' ', strip_tags($det));

			// Imprimir celdas de la fila
			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(14, 5.5, utf8_decode($id), 1, 0, 'C', true);
			$pdf->Cell(18, 5.5, utf8_decode($fec), 1, 0, 'C', true);
			$pdf->Cell(15, 5.5, utf8_decode($hor), 1, 0, 'C', true);

			// Truncado seguro para textos que puedan exceder el ancho
			$pdf->Cell(32, 5.5, utf8_decode(substr($mod, 0, 22)), 1, 0, 'L', true);
			$pdf->Cell(45, 5.5, utf8_decode(substr($dirPcs, 0, 32)), 1, 0, 'L', true);

			// Color distintivo para la operacion
			$pdf->SetTextColor($colorOp[0], $colorOp[1], $colorOp[2]);
			$pdf->SetFont('Helvetica', 'B', 7.5);
			$pdf->Cell(23, 5.5, utf8_decode($eveNom), 1, 0, 'C', true);
			$pdf->SetFont('Helvetica', '', 7.5);

			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(35, 5.5, utf8_decode(substr($usu, 0, 24)), 1, 0, 'L', true);
			$pdf->Cell(95, 5.5, utf8_decode(substr($det, 0, 72)), 1, 1, 'L', true);

			$fill = !$fill;
		}
	}

	if ($destino === 'F') {
		$pdf->Output($rutaSalida, 'F');
		return true;
	} elseif ($destino === 'S') {
		return $pdf->Output('', 'S');
	} else {
		$nombreArchivo = 'auditoria_transacciones_' . date('Ymd_His') . '.pdf';
		$pdf->Output($nombreArchivo, $destino);
		return true;
	}
}
