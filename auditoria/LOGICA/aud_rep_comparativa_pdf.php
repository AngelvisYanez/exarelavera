<?php
/**
 * Generador de Reporte PDF Comparativo de Auditoria (estilo Monitor de Actividades).
 *
 * @package auditoria.LOGICA
 */

require_once dirname(__FILE__) . '/aud_rep_pdf_base.php';

class AudPDFComparativa extends AudPDFAuditoriaBase
{
}

/**
 * @param array  $datos Comparativa calculada
 * @param string $destino I|D|F|S
 * @param string $rutaArchivo
 * @return string|bool
 */
function aud_generar_reporte_comparativo_pdf($datos, $destino = 'I', $rutaArchivo = '')
{
	$pdf = new AudPDFComparativa('P', 'mm', 'A4');
	$pdf->AliasNbPages();

	$pdf->tituloInforme = 'INFORME COMPARATIVO DE AUDITORÍA';
	$pdf->empresaNombre = isset($datos['empresa_nombre']) ? $datos['empresa_nombre'] : 'EXACONTABLE ERP';
	$pdf->usuarioEmisor = isset($datos['usuario_emisor']) ? $datos['usuario_emisor'] : 'Auditor del Sistema';
	$pdf->subtituloCajaIzq = 'Período A (Base): ' . (isset($datos['periodo_a_label']) ? $datos['periodo_a_label'] : '-');
	$pdf->subtituloCajaDer = 'Período B (Comparado): ' . (isset($datos['periodo_b_label']) ? $datos['periodo_b_label'] : '-');
	$pdf->colorCajaIzq = array(71, 85, 105);
	$pdf->colorCajaDer = array(37, 99, 235);

	$pdf->AddPage();

	$fmtVal = function ($v) {
		$v = (float)$v;
		return number_format($v, ($v == floor($v)) ? 0 : 1, ',', '.');
	};
	$fmtNum = function ($v, $decs = 0) {
		return number_format((float)$v, $decs, ',', '.');
	};

	// --- 1. KPIs en tarjetas ---
	$pdf->SectionHeader('1. RESUMEN EJECUTIVO');
	$kpis = isset($datos['kpis_comparativa']) ? $datos['kpis_comparativa'] : array();
	$cards = array();
	$mapaRgb = array(
		'primary' => array(37, 68, 99),
		'success' => array(22, 163, 74),
		'warning' => array(217, 119, 6),
		'danger' => array(220, 38, 38),
		'info' => array(2, 132, 199),
		'purple' => array(124, 58, 237),
		'orange' => array(234, 88, 12),
		'dark' => array(51, 65, 85)
	);
	$topKpis = array_slice($kpis, 0, 6);
	foreach ($topKpis as $k) {
		$pct = (isset($k['pct_cambio']) && $k['pct_cambio'] !== null) ? (float)$k['pct_cambio'] : null;
		$txtPct = ($pct === null) ? 'N/D' : ((($pct > 0) ? '+' : '') . number_format($pct, 1, ',', '.') . '%');
		$col = isset($k['color']) && isset($mapaRgb[$k['color']]) ? $mapaRgb[$k['color']] : array(37, 68, 99);
		$cards[] = array(
			'lbl' => $k['titulo'] . '  ·  ' . $txtPct,
			'val' => $fmtVal($k['valor_b']),
			'rgb' => $col
		);
	}
	if (!empty($cards)) {
		$pdf->KpiCards($cards);
	}

	// Tabla detalle de variación
	$pdf->SectionHeader('2. VARIACIÓN DETALLADA DE INDICADORES');
	$pdf->TableHeaderRow(array(
		array(58, 'Métrica / Indicador', 'L'),
		array(26, 'Período A', 'C'),
		array(26, 'Período B', 'C'),
		array(25, 'Ritmo A/día', 'C'),
		array(25, 'Ritmo B/día', 'C'),
		array(30, 'Variación', 'C')
	));
	$pdf->SetFont('Helvetica', '', 9);
	foreach ($kpis as $k) {
		$pdf->SetTextColor(15, 23, 42);
		$pdf->Cell(58, 6, utf8_decode(aud_pdf_acotar($k['titulo'], 34)), 1, 0, 'L');
		$pdf->SetTextColor(51, 65, 85);
		$pdf->Cell(26, 6, utf8_decode($fmtVal($k['valor_a'])), 1, 0, 'C');
		$pdf->Cell(26, 6, utf8_decode($fmtVal($k['valor_b'])), 1, 0, 'C');
		$pdf->Cell(25, 6, utf8_decode($fmtNum(isset($k['promedio_diario_a']) ? $k['promedio_diario_a'] : 0, 1)), 1, 0, 'C');
		$pdf->Cell(25, 6, utf8_decode($fmtNum(isset($k['promedio_diario_b']) ? $k['promedio_diario_b'] : 0, 1)), 1, 0, 'C');

		$pct = (isset($k['pct_cambio']) && $k['pct_cambio'] !== null) ? (float)$k['pct_cambio'] : null;
		$favor = isset($k['favorable_subida']) ? (bool)$k['favorable_subida'] : true;
		if ($pct === null) {
			$pdf->SetTextColor(148, 163, 184);
			$txtPct = 'N/D';
		} elseif ($pct == 0) {
			$pdf->SetTextColor(100, 116, 139);
			$txtPct = '=';
		} else {
			$bueno = $favor ? ($pct > 0) : ($pct < 0);
			$pdf->SetTextColor($bueno ? 22 : 185, $bueno ? 101 : 28, $bueno ? 52 : 28);
			$txtPct = (($pct > 0) ? '+' : '') . number_format($pct, 1, ',', '.') . ' %';
		}
		$pdf->SetFont('Helvetica', 'B', 9);
		$pdf->Cell(30, 6, utf8_decode($txtPct), 1, 1, 'C');
		$pdf->SetFont('Helvetica', '', 9);
	}
	$pdf->Ln(1);
	$pdf->SetFont('Helvetica', 'I', 7.5);
	$pdf->SetTextColor(100, 116, 139);
	$nota = 'Verde: variación favorable · Rojo: desfavorable · N/D: sin base. ';
	if (isset($datos['periodo_a_dias']) && isset($datos['periodo_b_dias'])) {
		$nota = 'Variación por ritmo diario (A: ' . $datos['periodo_a_dias'] . ' d · B: ' . $datos['periodo_b_dias'] . ' d). ' . $nota;
	}
	$pdf->MultiCell(190, 4, utf8_decode($nota), 0, 'L');

	// --- 3. Modulos ---
	$pdf->SectionHeader('3. ACTIVIDAD COMPARADA POR MÓDULO');
	$pdf->TableHeaderRow(array(
		array(58, 'Módulo del Sistema', 'L'),
		array(26, 'Movs. A', 'C'),
		array(26, 'Movs. B', 'C'),
		array(25, 'Ritmo A/día', 'C'),
		array(25, 'Ritmo B/día', 'C'),
		array(30, 'Variación', 'C')
	));
	$modulos = isset($datos['modulos_comparativa']) ? $datos['modulos_comparativa'] : array();
	$pdf->SetFont('Helvetica', '', 9);
	if (empty($modulos)) {
		$pdf->EmptyRow(190, 'Sin registros de actividad en los períodos analizados.');
	} else {
		foreach (array_slice($modulos, 0, 25) as $m) {
			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(58, 6, utf8_decode(aud_pdf_acotar($m['modulo'], 34)), 1, 0, 'L');
			$pdf->SetTextColor(51, 65, 85);
			$pdf->Cell(26, 6, utf8_decode($fmtNum($m['total_a'])), 1, 0, 'C');
			$pdf->Cell(26, 6, utf8_decode($fmtNum($m['total_b'])), 1, 0, 'C');
			$pdf->Cell(25, 6, utf8_decode($fmtNum(isset($m['promedio_diario_a']) ? $m['promedio_diario_a'] : 0, 1)), 1, 0, 'C');
			$pdf->Cell(25, 6, utf8_decode($fmtNum(isset($m['promedio_diario_b']) ? $m['promedio_diario_b'] : 0, 1)), 1, 0, 'C');
			$pctM = (isset($m['pct_cambio']) && $m['pct_cambio'] !== null) ? (float)$m['pct_cambio'] : null;
			if ($pctM === null) {
				$pdf->SetTextColor(148, 163, 184);
				$txtPctM = 'N/D';
			} elseif ($pctM == 0) {
				$pdf->SetTextColor(100, 116, 139);
				$txtPctM = '=';
			} else {
				$pdf->SetTextColor($pctM > 0 ? 22 : 185, $pctM > 0 ? 101 : 28, $pctM > 0 ? 52 : 28);
				$txtPctM = (($pctM > 0) ? '+' : '') . number_format($pctM, 1, ',', '.') . ' %';
			}
			$pdf->SetFont('Helvetica', 'B', 9);
			$pdf->Cell(30, 6, utf8_decode($txtPctM), 1, 1, 'C');
			$pdf->SetFont('Helvetica', '', 9);
		}
	}

	// --- 4. Operaciones ---
	$eve = isset($datos['eventos_comparativa']) ? $datos['eventos_comparativa'] : array();
	if (!empty($eve) && !empty($eve['categorias'])) {
		$pdf->SectionHeader('4. COMPARATIVA DE OPERACIONES (INGRESAR / ACTUALIZAR / ELIMINAR)');
		$pdf->TableHeaderRow(array(
			array(70, 'Operación', 'L'),
			array(60, 'Período A', 'C'),
			array(60, 'Período B', 'C')
		));
		$pdf->SetFont('Helvetica', '', 9);
		for ($i = 0; $i < count($eve['categorias']); $i++) {
			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(70, 6, utf8_decode($eve['categorias'][$i]), 1, 0, 'L');
			$pdf->SetTextColor(51, 65, 85);
			$pdf->Cell(60, 6, utf8_decode($fmtNum(isset($eve['serie_a'][$i]) ? $eve['serie_a'][$i] : 0)), 1, 0, 'C');
			$pdf->Cell(60, 6, utf8_decode($fmtNum(isset($eve['serie_b'][$i]) ? $eve['serie_b'][$i] : 0)), 1, 1, 'C');
		}
	}

	// --- 5. Sesiones ---
	$ses = isset($datos['sesiones_comparativa']) ? $datos['sesiones_comparativa'] : array();
	if (!empty($ses) && !empty($ses['categorias'])) {
		$pdf->SectionHeader('5. COMPARATIVA DE SESIONES Y SEGURIDAD');
		$pdf->TableHeaderRow(array(
			array(70, 'Indicador', 'L'),
			array(60, 'Período A', 'C'),
			array(60, 'Período B', 'C')
		));
		$pdf->SetFont('Helvetica', '', 9);
		for ($i = 0; $i < count($ses['categorias']); $i++) {
			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(70, 6, utf8_decode($ses['categorias'][$i]), 1, 0, 'L');
			$pdf->SetTextColor(51, 65, 85);
			$pdf->Cell(60, 6, utf8_decode($fmtVal(isset($ses['serie_a'][$i]) ? $ses['serie_a'][$i] : 0)), 1, 0, 'C');
			$pdf->Cell(60, 6, utf8_decode($fmtVal(isset($ses['serie_b'][$i]) ? $ses['serie_b'][$i] : 0)), 1, 1, 'C');
		}
	}

	// --- 6. Usuarios ---
	$pdf->SectionHeader('6. USUARIOS CON MAYOR VOLUMEN (PERÍODO B)');
	$pdf->TableHeaderRow(array(
		array(65, 'Usuario / Empleado', 'L'),
		array(35, 'Total', 'C'),
		array(30, 'Ingresar', 'C'),
		array(30, 'Actualizar', 'C'),
		array(30, 'Eliminar', 'C')
	));
	$usuarios = isset($datos['usuarios_top_b']) ? $datos['usuarios_top_b'] : array();
	$pdf->SetFont('Helvetica', '', 9);
	if (empty($usuarios)) {
		$pdf->EmptyRow(190, 'Sin actividad de usuarios registrada.');
	} else {
		foreach (array_slice($usuarios, 0, 15) as $u) {
			$nom = trim((isset($u['UsuarioNombre']) ? $u['UsuarioNombre'] : '') . ' ' . (isset($u['UsuarioApellido']) ? $u['UsuarioApellido'] : ''));
			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(65, 6, utf8_decode(aud_pdf_acotar($nom, 32)), 1, 0, 'L');
			$pdf->SetTextColor(51, 65, 85);
			$pdf->Cell(35, 6, utf8_decode($fmtNum($u['Total_Operaciones'])), 1, 0, 'C');
			$pdf->Cell(30, 6, utf8_decode($fmtNum($u['Total_Inserciones'])), 1, 0, 'C');
			$pdf->Cell(30, 6, utf8_decode($fmtNum($u['Total_Modificaciones'])), 1, 0, 'C');
			if ((int)$u['Total_Eliminaciones'] > 0) {
				$pdf->SetTextColor(185, 28, 28);
				$pdf->SetFont('Helvetica', 'B', 9);
			} else {
				$pdf->SetTextColor(100, 116, 139);
			}
			$pdf->Cell(30, 6, utf8_decode($fmtNum($u['Total_Eliminaciones'])), 1, 1, 'C');
			$pdf->SetFont('Helvetica', '', 9);
		}
	}

	// --- 7. Plantas ---
	$plantas = isset($datos['plantas_comparativa']) ? $datos['plantas_comparativa'] : array();
	if (!empty($plantas)) {
		$pdf->SectionHeader('7. PLANTAS DE BENEFICIO (A vs B)');
		$pdf->TableHeaderRow(array(
			array(80, 'Planta', 'L'),
			array(35, 'Total A', 'C'),
			array(35, 'Total B', 'C'),
			array(40, 'Variación', 'C')
		));
		$pdf->SetFont('Helvetica', '', 9);
		foreach (array_slice($plantas, 0, 15) as $p) {
			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(80, 6, utf8_decode(aud_pdf_acotar(isset($p['planta']) ? $p['planta'] : '-', 40)), 1, 0, 'L');
			$pdf->SetTextColor(51, 65, 85);
			$pdf->Cell(35, 6, utf8_decode($fmtNum($p['total_a'])), 1, 0, 'C');
			$pdf->Cell(35, 6, utf8_decode($fmtNum($p['total_b'])), 1, 0, 'C');
			$pctP = (isset($p['pct_cambio']) && $p['pct_cambio'] !== null) ? (float)$p['pct_cambio'] : null;
			if ($pctP === null) {
				$pdf->SetTextColor(148, 163, 184);
				$txt = 'N/D';
			} else {
				$pdf->SetTextColor($pctP >= 0 ? 22 : 185, $pctP >= 0 ? 101 : 28, $pctP >= 0 ? 52 : 28);
				$txt = (($pctP > 0) ? '+' : '') . number_format($pctP, 1, ',', '.') . ' %';
			}
			$pdf->SetFont('Helvetica', 'B', 9);
			$pdf->Cell(40, 6, utf8_decode($txt), 1, 1, 'C');
			$pdf->SetFont('Helvetica', '', 9);
		}
	}

	// --- Observaciones ---
	$pdf->SectionHeader('8. OBSERVACIONES Y ALERTAS DE AUDITORÍA');
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

	$pdf->IntegrityHash($datos);

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
	}
	$pdf->Output('Reporte_Auditoria_Comparativa.pdf', 'I');
	exit;
}
