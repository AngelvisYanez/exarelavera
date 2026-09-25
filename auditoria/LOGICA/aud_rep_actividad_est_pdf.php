<?php
/**
 * Generador de Reporte PDF de Estadisticas de Sesiones por Usuario
 * (estilo visual del Monitor de Actividades / Panel Estadistico).
 *
 * @package auditoria.LOGICA
 */

require_once dirname(__FILE__) . '/aud_rep_pdf_base.php';

class AudPDFActividadEst extends AudPDFAuditoriaBase
{
}

/**
 * @param array  $datos Resultado de aud_ses_estadistica_por_usuario (+ meta)
 * @param string $destino I|D|F|S
 * @param string $rutaArchivo
 * @return string|bool
 */
function aud_generar_reporte_actividad_est_pdf($datos, $destino = 'I', $rutaArchivo = '')
{
	$pdf = new AudPDFActividadEst('P', 'mm', 'A4');
	$pdf->AliasNbPages();

	$desde = isset($datos['periodo']['desde']) ? $datos['periodo']['desde'] : '';
	$hasta = isset($datos['periodo']['hasta']) ? $datos['periodo']['hasta'] : '';
	$rango = trim($desde . ($desde && $hasta ? ' a ' : '') . $hasta);
	if ($rango === '') {
		$rango = 'Sin rango especificado';
	}
	$filtroUsuNom = isset($datos['filtro_usu_nombre']) ? trim((string)$datos['filtro_usu_nombre']) : '';
	$filtroUsuCod = isset($datos['filtro_usu']) ? (int)$datos['filtro_usu'] : 0;
	$cajaDer = 'Rango Analizado: ' . $rango;
	if ($filtroUsuCod > 0) {
		$cajaDer .= '  |  Usuario: ' . ($filtroUsuNom !== '' ? $filtroUsuNom : ('#' . $filtroUsuCod));
	} else {
		$cajaDer .= '  |  Todos los usuarios';
	}

	$pdf->tituloInforme = 'INFORME DE ESTADÍSTICAS DE SESIONES';
	$pdf->empresaNombre = isset($datos['empresa']) ? $datos['empresa'] : 'EXACONTABLE ERP';
	$pdf->usuarioEmisor = isset($datos['usuario_emisor']) ? $datos['usuario_emisor'] : 'Auditor del Sistema';
	$pdf->subtituloCajaIzq = 'Empresa: ' . $pdf->empresaNombre;
	$pdf->subtituloCajaDer = $cajaDer;
	$pdf->colorCajaIzq = array(30, 58, 138);
	$pdf->colorCajaDer = array(5, 150, 105);

	$pdf->AddPage();

	$t = isset($datos['totales']) ? $datos['totales'] : array();
	$filas = isset($datos['filas']) ? $datos['filas'] : array();
	$serie = isset($datos['serie_diaria']) ? $datos['serie_diaria'] : array();

	// 1. Resumen KPI cards
	$pdf->SectionHeader('1. RESUMEN GENERAL DEL PERÍODO');
	$pdf->KpiCards(array(
		array('lbl' => 'Sesiones iniciadas', 'val' => aud_pdf_fmt_num(isset($t['iniciadas']) ? $t['iniciadas'] : 0), 'rgb' => array(37, 99, 235)),
		array('lbl' => 'Cerradas normalmente', 'val' => aud_pdf_fmt_num(isset($t['cerradas']) ? $t['cerradas'] : 0), 'rgb' => array(22, 163, 74)),
		array('lbl' => 'Por inactividad', 'val' => aud_pdf_fmt_num(isset($t['por_inactividad']) ? $t['por_inactividad'] : 0), 'rgb' => array(217, 119, 6)),
		array('lbl' => 'Forzadas (admin)', 'val' => aud_pdf_fmt_num(isset($t['forzadas']) ? $t['forzadas'] : 0), 'rgb' => array(220, 38, 38)),
		array('lbl' => 'Usuarios únicos', 'val' => aud_pdf_fmt_num(isset($t['usuarios']) ? $t['usuarios'] : 0), 'rgb' => array(124, 58, 237)),
		array('lbl' => 'Minutos promedio', 'val' => aud_pdf_fmt_num(isset($t['promedio_min']) ? $t['promedio_min'] : 0, 1), 'rgb' => array(14, 165, 233))
	));

	// Tabla resumen
	$pdf->TableHeaderRow(array(
		array(100, 'Indicador', 'L'),
		array(90, 'Valor del Período', 'C')
	));
	$pdf->SetFont('Helvetica', '', 9);
	$pdf->SetTextColor(15, 23, 42);
	$filasRes = array(
		'Total de sesiones iniciadas' => isset($t['iniciadas']) ? $t['iniciadas'] : 0,
		'Cierres normales' => isset($t['cerradas']) ? $t['cerradas'] : 0,
		'Cierres por inactividad' => isset($t['por_inactividad']) ? $t['por_inactividad'] : 0,
		'Cierres forzados por administrador' => isset($t['forzadas']) ? $t['forzadas'] : 0,
		'Usuarios con actividad' => isset($t['usuarios']) ? $t['usuarios'] : 0,
		'Minutos totales de uso' => isset($t['minutos']) ? $t['minutos'] : 0,
		'Promedio de minutos por sesión' => isset($t['promedio_min']) ? $t['promedio_min'] : 0
	);
	foreach ($filasRes as $lbl => $val) {
		$pdf->SetTextColor(15, 23, 42);
		$pdf->Cell(100, 6, utf8_decode($lbl), 1, 0, 'L');
		$pdf->SetTextColor(51, 65, 85);
		$decs = (strpos($lbl, 'Promedio') !== false) ? 1 : 0;
		$pdf->Cell(90, 6, utf8_decode(aud_pdf_fmt_num($val, $decs)), 1, 1, 'C');
	}

	// 2. Ranking por tiempo
	$pdf->SectionHeader('2. USUARIOS CON MAYOR TIEMPO DE USO');
	$pdf->TableHeaderRow(array(
		array(70, 'Usuario / Empleado', 'L'),
		array(24, 'Iniciadas', 'C'),
		array(24, 'Cerradas', 'C'),
		array(24, 'Inactividad', 'C'),
		array(24, 'Forzadas', 'C'),
		array(24, 'Total min', 'C')
	));
	$pdf->SetFont('Helvetica', '', 9);
	if (empty($filas)) {
		$pdf->EmptyRow(190, 'No hay sesiones en el período seleccionado.');
	} else {
		$porTiempo = $filas;
		usort($porTiempo, function ($a, $b) {
			return (int)(isset($b['Total_Min']) ? $b['Total_Min'] : 0) - (int)(isset($a['Total_Min']) ? $a['Total_Min'] : 0);
		});
		foreach (array_slice($porTiempo, 0, 20) as $r) {
			$nom = isset($r['Usuario_Completo']) ? $r['Usuario_Completo'] : (isset($r['Usu_Nom']) ? $r['Usu_Nom'] : 'Usuario');
			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(70, 6, utf8_decode(aud_pdf_acotar($nom, 36)), 1, 0, 'L');
			$pdf->SetTextColor(51, 65, 85);
			$pdf->Cell(24, 6, utf8_decode(aud_pdf_fmt_num(isset($r['Iniciadas']) ? $r['Iniciadas'] : 0)), 1, 0, 'C');
			$pdf->Cell(24, 6, utf8_decode(aud_pdf_fmt_num(isset($r['Cerradas']) ? $r['Cerradas'] : 0)), 1, 0, 'C');
			$ina = (int)(isset($r['Por_Inactividad']) ? $r['Por_Inactividad'] : 0);
			if ($ina > 0) {
				$pdf->SetTextColor(217, 119, 6);
				$pdf->SetFont('Helvetica', 'B', 9);
			}
			$pdf->Cell(24, 6, utf8_decode(aud_pdf_fmt_num($ina)), 1, 0, 'C');
			$pdf->SetFont('Helvetica', '', 9);
			$forz = (int)(isset($r['Forzadas']) ? $r['Forzadas'] : 0);
			if ($forz > 0) {
				$pdf->SetTextColor(185, 28, 28);
				$pdf->SetFont('Helvetica', 'B', 9);
			} else {
				$pdf->SetTextColor(100, 116, 139);
			}
			$pdf->Cell(24, 6, utf8_decode(aud_pdf_fmt_num($forz)), 1, 0, 'C');
			$pdf->SetFont('Helvetica', '', 9);
			$pdf->SetTextColor(37, 68, 99);
			$pdf->SetFont('Helvetica', 'B', 9);
			$pdf->Cell(24, 6, utf8_decode(aud_pdf_fmt_num(isset($r['Total_Min']) ? $r['Total_Min'] : 0)), 1, 1, 'C');
			$pdf->SetFont('Helvetica', '', 9);
		}
	}

	// 3. Detalle completo
	$pdf->SectionHeader('3. DETALLE POR USUARIO');
	$pdf->SetFont('Helvetica', 'B', 8);
	$pdf->SetFillColor(241, 245, 249);
	$pdf->SetTextColor(51, 65, 85);
	$pdf->Cell(52, 6, utf8_decode('Usuario'), 1, 0, 'L', true);
	$pdf->Cell(18, 6, utf8_decode('Inic.'), 1, 0, 'C', true);
	$pdf->Cell(18, 6, utf8_decode('Cerr.'), 1, 0, 'C', true);
	$pdf->Cell(18, 6, utf8_decode('Inact.'), 1, 0, 'C', true);
	$pdf->Cell(18, 6, utf8_decode('Forz.'), 1, 0, 'C', true);
	$pdf->Cell(22, 6, utf8_decode('Prom.min'), 1, 0, 'C', true);
	$pdf->Cell(22, 6, utf8_decode('Tot.min'), 1, 0, 'C', true);
	$pdf->Cell(22, 6, utf8_decode('Últ. act.'), 1, 1, 'C', true);

	$pdf->SetFont('Helvetica', '', 8);
	if (empty($filas)) {
		$pdf->EmptyRow(190, 'Sin datos para el detalle.');
	} else {
		foreach ($filas as $r) {
			if ($pdf->GetY() > 270) {
				$pdf->AddPage();
			}
			$nom = isset($r['Usuario_Completo']) ? $r['Usuario_Completo'] : 'Usuario';
			$ult = isset($r['Ultima_Actividad']) ? substr((string)$r['Ultima_Actividad'], 0, 16) : '';
			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(52, 5, utf8_decode(aud_pdf_acotar($nom, 28)), 1, 0, 'L');
			$pdf->SetTextColor(51, 65, 85);
			$pdf->Cell(18, 5, utf8_decode(aud_pdf_fmt_num(isset($r['Iniciadas']) ? $r['Iniciadas'] : 0)), 1, 0, 'C');
			$pdf->Cell(18, 5, utf8_decode(aud_pdf_fmt_num(isset($r['Cerradas']) ? $r['Cerradas'] : 0)), 1, 0, 'C');
			$pdf->Cell(18, 5, utf8_decode(aud_pdf_fmt_num(isset($r['Por_Inactividad']) ? $r['Por_Inactividad'] : 0)), 1, 0, 'C');
			$pdf->Cell(18, 5, utf8_decode(aud_pdf_fmt_num(isset($r['Forzadas']) ? $r['Forzadas'] : 0)), 1, 0, 'C');
			$pdf->Cell(22, 5, utf8_decode(aud_pdf_fmt_num(isset($r['Promedio_Min']) ? $r['Promedio_Min'] : 0, 1)), 1, 0, 'C');
			$pdf->Cell(22, 5, utf8_decode(aud_pdf_fmt_num(isset($r['Total_Min']) ? $r['Total_Min'] : 0)), 1, 0, 'C');
			$pdf->Cell(22, 5, utf8_decode(aud_pdf_acotar($ult, 16)), 1, 1, 'C');
		}
	}

	// 4. Tendencia diaria (resumen tabular)
	$cats = isset($serie['categorias']) ? $serie['categorias'] : array();
	if (!empty($cats)) {
		$pdf->SectionHeader('4. TENDENCIA DIARIA DE SESIONES');
		$pdf->TableHeaderRow(array(
			array(40, 'Día', 'L'),
			array(30, 'Iniciadas', 'C'),
			array(30, 'Cerradas', 'C'),
			array(30, 'Inactividad', 'C'),
			array(30, 'Forzadas', 'C'),
			array(30, 'Minutos', 'C')
		));
		$pdf->SetFont('Helvetica', '', 8);
		$n = count($cats);
		// Si hay muchos días, mostrar muestra (primeros/últimos) o todos si <= 40
		$indices = range(0, $n - 1);
		if ($n > 40) {
			$indices = array_merge(range(0, 19), range($n - 20, $n - 1));
		}
		$prev = -1;
		foreach ($indices as $i) {
			if ($prev >= 0 && $i > $prev + 1) {
				$pdf->SetTextColor(148, 163, 184);
				$pdf->Cell(190, 5, utf8_decode('… (' . ($i - $prev - 1) . ' días omitidos) …'), 1, 1, 'C');
			}
			$prev = $i;
			$pdf->SetTextColor(15, 23, 42);
			$pdf->Cell(40, 5, utf8_decode(isset($cats[$i]) ? $cats[$i] : ''), 1, 0, 'L');
			$pdf->SetTextColor(51, 65, 85);
			$pdf->Cell(30, 5, utf8_decode(aud_pdf_fmt_num(isset($serie['iniciadas'][$i]) ? $serie['iniciadas'][$i] : 0)), 1, 0, 'C');
			$pdf->Cell(30, 5, utf8_decode(aud_pdf_fmt_num(isset($serie['cerradas'][$i]) ? $serie['cerradas'][$i] : 0)), 1, 0, 'C');
			$pdf->Cell(30, 5, utf8_decode(aud_pdf_fmt_num(isset($serie['por_inactividad'][$i]) ? $serie['por_inactividad'][$i] : 0)), 1, 0, 'C');
			$pdf->Cell(30, 5, utf8_decode(aud_pdf_fmt_num(isset($serie['forzadas'][$i]) ? $serie['forzadas'][$i] : 0)), 1, 0, 'C');
			$pdf->Cell(30, 5, utf8_decode(aud_pdf_fmt_num(isset($serie['minutos'][$i]) ? $serie['minutos'][$i] : 0)), 1, 1, 'C');
		}
	}

	// 5. Observaciones
	$pdf->SectionHeader('5. OBSERVACIONES DE AUDITORÍA');
	$obs = array();
	$ini = (int)(isset($t['iniciadas']) ? $t['iniciadas'] : 0);
	$usu = (int)(isset($t['usuarios']) ? $t['usuarios'] : 0);
	$ina = (int)(isset($t['por_inactividad']) ? $t['por_inactividad'] : 0);
	$forz = (int)(isset($t['forzadas']) ? $t['forzadas'] : 0);
	$obs[] = 'Se registraron ' . number_format($ini) . ' sesiones iniciadas por ' . number_format($usu) . ' usuario(s) en el período analizado.';
	if ($ina > 0) {
		$obs[] = 'Hubo ' . number_format($ina) . ' cierre(s) por inactividad; conviene revisar estaciones desatendidas.';
	}
	if ($forz > 0) {
		$obs[] = 'Se ejecutaron ' . number_format($forz) . ' cierre(s) forzado(s) por el administrador del sistema.';
	}
	if ($ini === 0) {
		$obs[] = 'No se detectó actividad de sesiones en el rango seleccionado.';
	}
	$pdf->SetFont('Helvetica', '', 9);
	$pdf->SetTextColor(30, 41, 59);
	foreach ($obs as $linea) {
		$pdf->Cell(5, 5, utf8_decode('• '), 0, 0, 'L');
		$pdf->MultiCell(185, 5, utf8_decode($linea), 0, 'L');
	}

	$pdf->IntegrityHash($datos);

	if ($destino === 'F') {
		if (empty($rutaArchivo)) {
			$rutaArchivo = sys_get_temp_dir() . '/rep_est_sesiones_' . time() . '.pdf';
		}
		$pdf->Output($rutaArchivo, 'F');
		return $rutaArchivo;
	} elseif ($destino === 'S') {
		return $pdf->Output('', 'S');
	} elseif ($destino === 'D') {
		$pdf->Output('Reporte_Estadistica_Sesiones_' . date('Ymd_His') . '.pdf', 'D');
		exit;
	}
	$pdf->Output('Reporte_Estadistica_Sesiones.pdf', 'I');
	exit;
}
