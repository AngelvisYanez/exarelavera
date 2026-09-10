<?php
/**
 * Logica del Dashboard Estadistico Comparativo de Auditoria.
 * 
 * Permite procesar y contrastar metricas de auditoria entre dos periodos (A y B):
 * - KPIs comparativos (Total movimientos, Ingresos, Actualizaciones, Eliminaciones, Sesiones, Cierres por inactividad).
 * - Comparativa de operaciones por modulo y proceso.
 * - Comparativa de actividad por franja horaria (24 horas).
 * - Comparativa de sesiones activas, tiempos promedio y seguridad.
 * - Generacion de observaciones y conclusiones analiticas automaticas.
 * 
 * Compatible con PHP 5.6 y PHP 8.2+.
 *
 * @package auditoria.LOGICA
 */

if (!function_exists('aud_dash_to_utf8_deep')) {
	/**
	 * Sanitiza recursivamente tanto valores como claves asociativas para json_encode
	 */
	function aud_dash_to_utf8_deep($data) {
		if (is_string($data)) {
			if (function_exists('mb_check_encoding') && @mb_check_encoding($data, 'UTF-8')) {
				return $data;
			}
			if (function_exists('mb_convert_encoding')) {
				return @mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
			}
			return @utf8_encode($data);
		}
		if (is_array($data)) {
			$clean = array();
			foreach ($data as $k => $v) {
				$cleanKey = is_string($k) ? aud_dash_to_utf8_deep($k) : $k;
				$clean[$cleanKey] = aud_dash_to_utf8_deep($v);
			}
			return $clean;
		}
		return $data;
	}
}

if (!function_exists('aud_dash_calcular_comparativa')) {
	/**
	 * Calcula el conjunto integral de metricas comparativas entre Periodo A y Periodo B
	 * 
	 * @param int $empCod Codigo de la empresa
	 * @param string $pA_ini Fecha/hora inicio Periodo A (YYYY-MM-DD HH:II:SS)
	 * @param string $pA_fin Fecha/hora fin Periodo A (YYYY-MM-DD HH:II:SS)
	 * @param string $pB_ini Fecha/hora inicio Periodo B (YYYY-MM-DD HH:II:SS)
	 * @param string $pB_fin Fecha/hora fin Periodo B (YYYY-MM-DD HH:II:SS)
	 * @param mixed $con Conexion MySQL / MySQLi
	 * @return array Metricas estructuradas para KPIs, graficos y reportes
	 */
	function aud_dash_calcular_comparativa($empCod, $pA_ini, $pA_fin, $pB_ini, $pB_fin, $con = null)
	{
		$empCod = (int)$empCod;
		if ($empCod <= 0 && isset($_SESSION['Ses_Emp_Cod'])) {
			$empCod = (int)$_SESSION['Ses_Emp_Cod'];
		}

		// Obtener conexion si no fue provista
		$cerrarConAlFinal = false;
		if (!$con) {
			require_once dirname(__FILE__) . '/../LOGICA/aud_log_auditoria.php';
			if (function_exists('aud_db_connect')) {
				$con = aud_db_connect();
				$cerrarConAlFinal = true;
			}
		}

		require_once dirname(__FILE__) . '/aud_sql_dashboard.php';

		// 1. Obtener nombre de la empresa para el encabezado
		$empNombre = 'Empresa ' . $empCod;
		if ($con) {
			$sqlEmp = sentencias_dashboard(9, array($empCod));
			$resEmp = mysqli_query($con, $sqlEmp);
			if ($resEmp && $rEmp = mysqli_fetch_assoc($resEmp)) {
				$empNombre = !empty($rEmp['Emp_Nom']) ? $rEmp['Emp_Nom'] : $empNombre;
			}
		}

		// Helper para extraer metricas de un periodo
		$obtenerDatosPeriodo = function($fIni, $fFin) use ($con, $empCod) {
			$res = array(
				'total_movimientos' => 0,
				'eventos' => array('I' => 0, 'U' => 0, 'D' => 0, 'otros' => 0),
				'modulos' => array(),
				'horarios' => array_fill(0, 24, 0),
				'sesiones' => array('total' => 0, 'usuarios_unicos' => 0, 'promedio_min' => 0, 'timeout' => 0, 'forzadas' => 0),
				'usuarios' => array()
			);

			if (!$con) return $res;

			// Resumen general de logs y eventos
			$sqlRes = sentencias_dashboard(1, array($empCod, $fIni, $fFin));
			$qRes = mysqli_query($con, $sqlRes);
			if ($qRes && $f = mysqli_fetch_assoc($qRes)) {
				$res['total_movimientos'] = (int)$f['Total_Movimientos'];
				$res['eventos']['I'] = (int)$f['Total_Insert'];
				$res['eventos']['U'] = (int)$f['Total_Update'];
				$res['eventos']['D'] = (int)$f['Total_Delete'];
				$res['eventos']['otros'] = max(0, $res['total_movimientos'] - ($res['eventos']['I'] + $res['eventos']['U'] + $res['eventos']['D']));
			}

			// Movimientos por Modulo
			$sqlMod = sentencias_dashboard(2, array($empCod, $fIni, $fFin));
			$qMod = mysqli_query($con, $sqlMod);
			while ($qMod && $r = mysqli_fetch_assoc($qMod)) {
				$modNom = !empty($r['Modulo']) ? $r['Modulo'] : 'Sin Modulo';
				$res['modulos'][$modNom] = (int)$r['Total'];
			}

			// Distribucion por Hora
			$sqlHor = sentencias_dashboard(3, array($empCod, $fIni, $fFin));
			$qHor = mysqli_query($con, $sqlHor);
			while ($qHor && $r = mysqli_fetch_assoc($qHor)) {
				$h = (int)$r['Hora'];
				if ($h >= 0 && $h <= 23) {
					$res['horarios'][$h] = (int)$r['Total'];
				}
			}

			// Sesiones de Usuarios
			$sqlSes = sentencias_dashboard(4, array($empCod, $fIni, $fFin));
			$qSes = mysqli_query($con, $sqlSes);
			if ($qSes && $f = mysqli_fetch_assoc($qSes)) {
				$res['sesiones']['total'] = (int)$f['Total_Sesiones'];
				$res['sesiones']['usuarios_unicos'] = (int)$f['Usuarios_Unicos'];
				$res['sesiones']['promedio_min'] = round((float)$f['Promedio_Minutos'], 1);
				$res['sesiones']['timeout'] = (int)$f['Cierres_Inactividad'];
				$res['sesiones']['forzadas'] = (int)$f['Cierres_Forzados'];
			}

			// Top Usuarios
			$sqlUsu = sentencias_dashboard(6, array($empCod, $fIni, $fFin));
			$qUsu = mysqli_query($con, $sqlUsu);
			while ($qUsu && $f = mysqli_fetch_assoc($qUsu)) {
				$res['usuarios'][] = array(
					'Usuario_Id' => (int)$f['Usu_Cod'],
					'UsuarioNombre' => isset($f['Prs_Nom']) ? $f['Prs_Nom'] : ('Usuario ' . $f['Usu_Cod']),
					'UsuarioApellido' => isset($f['Prs_Ape']) ? $f['Prs_Ape'] : '',
					'Total_Operaciones' => (int)$f['Total_Operaciones'],
					'Total_Inserciones' => (int)$f['Total_Inserciones'],
					'Total_Modificaciones' => (int)$f['Total_Modificaciones'],
					'Total_Eliminaciones' => (int)$f['Total_Eliminaciones']
				);
			}

			return $res;
		};

		$dataA = $obtenerDatosPeriodo($pA_ini, $pA_fin);
		$dataB = $obtenerDatosPeriodo($pB_ini, $pB_fin);

		// Helper para calcular variacion porcentual con casos limite
		$calcPct = function($valA, $valB) {
			if ($valA == 0 && $valB == 0) return 0.0;
			if ($valA == 0) return 100.0;
			if ($valB == 0) return -100.0;
			return round((($valB - $valA) / (float)$valA) * 100, 1);
		};

		// 1. Metricas Principales (KPI Cards) - Términos: Ingresar, Actualizar, Eliminar
		$kpisComparativa = array(
			array(
				'clave' => 'total_movimientos',
				'titulo' => 'Total Movimientos',
				'icono' => 'fa-database',
				'color' => 'primary',
				'valor_a' => $dataA['total_movimientos'],
				'valor_b' => $dataB['total_movimientos'],
				'pct_cambio' => $calcPct($dataA['total_movimientos'], $dataB['total_movimientos'])
			),
			array(
				'clave' => 'inserciones',
				'titulo' => 'Ingresos (Ingresar)',
				'icono' => 'fa-plus-circle',
				'color' => 'success',
				'valor_a' => $dataA['eventos']['I'],
				'valor_b' => $dataB['eventos']['I'],
				'pct_cambio' => $calcPct($dataA['eventos']['I'], $dataB['eventos']['I'])
			),
			array(
				'clave' => 'modificaciones',
				'titulo' => 'Actualizaciones (Actualizar)',
				'icono' => 'fa-pencil',
				'color' => 'warning',
				'valor_a' => $dataA['eventos']['U'],
				'valor_b' => $dataB['eventos']['U'],
				'pct_cambio' => $calcPct($dataA['eventos']['U'], $dataB['eventos']['U'])
			),
			array(
				'clave' => 'eliminaciones',
				'titulo' => 'Eliminaciones (Eliminar)',
				'icono' => 'fa-trash',
				'color' => 'danger',
				'valor_a' => $dataA['eventos']['D'],
				'valor_b' => $dataB['eventos']['D'],
				'pct_cambio' => $calcPct($dataA['eventos']['D'], $dataB['eventos']['D'])
			),
			array(
				'clave' => 'sesiones_totales',
				'titulo' => 'Sesiones Iniciadas',
				'icono' => 'fa-users',
				'color' => 'info',
				'valor_a' => $dataA['sesiones']['total'],
				'valor_b' => $dataB['sesiones']['total'],
				'pct_cambio' => $calcPct($dataA['sesiones']['total'], $dataB['sesiones']['total'])
			),
			array(
				'clave' => 'promedio_min_uso',
				'titulo' => 'Promedio Minutos de Uso',
				'icono' => 'fa-clock-o',
				'color' => 'purple',
				'valor_a' => $dataA['sesiones']['promedio_min'],
				'valor_b' => $dataB['sesiones']['promedio_min'],
				'pct_cambio' => $calcPct($dataA['sesiones']['promedio_min'], $dataB['sesiones']['promedio_min'])
			),
			array(
				'clave' => 'cierres_inactividad',
				'titulo' => 'Cierres por Inactividad',
				'icono' => 'fa-hourglass-end',
				'color' => 'orange',
				'valor_a' => $dataA['sesiones']['timeout'],
				'valor_b' => $dataB['sesiones']['timeout'],
				'pct_cambio' => $calcPct($dataA['sesiones']['timeout'], $dataB['sesiones']['timeout'])
			),
			array(
				'clave' => 'cierres_forzados',
				'titulo' => 'Cierres Forzados Admin',
				'icono' => 'fa-ban',
				'color' => 'dark',
				'valor_a' => $dataA['sesiones']['forzadas'],
				'valor_b' => $dataB['sesiones']['forzadas'],
				'pct_cambio' => $calcPct($dataA['sesiones']['forzadas'], $dataB['sesiones']['forzadas'])
			)
		);

		// 2. Modulos Comparativa
		$todosModulos = array_unique(array_merge(array_keys($dataA['modulos']), array_keys($dataB['modulos'])));
		$modulosComparativa = array();
		foreach ($todosModulos as $mod) {
			$totA = isset($dataA['modulos'][$mod]) ? $dataA['modulos'][$mod] : 0;
			$totB = isset($dataB['modulos'][$mod]) ? $dataB['modulos'][$mod] : 0;
			$modulosComparativa[] = array(
				'modulo' => $mod,
				'total_a' => $totA,
				'total_b' => $totB,
				'pct_cambio' => $calcPct($totA, $totB)
			);
		}
		usort($modulosComparativa, function($x, $y) {
			return $y['total_b'] - $x['total_b'];
		});

		// 3. Horarios Comparativa (24 Horas)
		$horariosComparativa = array();
		for ($h = 0; $h < 24; $h++) {
			$horariosComparativa[] = array(
				'hora' => sprintf('%02d:00', $h),
				'total_a' => $dataA['horarios'][$h],
				'total_b' => $dataB['horarios'][$h]
			);
		}

		// 4. Comparativa Temporal Diaria
		$serieTempA = array();
		$serieTempB = array();
		$catsTemp = array();
		if ($con) {
			$sqlDiaA = sentencias_dashboard(5, array($empCod, $pA_ini, $pA_fin));
			$qDiaA = mysqli_query($con, $sqlDiaA);
			$mapA = array();
			while ($qDiaA && $r = mysqli_fetch_assoc($qDiaA)) {
				$mapA[$r['Dia']] = (int)$r['Total'];
			}

			$sqlDiaB = sentencias_dashboard(5, array($empCod, $pB_ini, $pB_fin));
			$qDiaB = mysqli_query($con, $sqlDiaB);
			$mapB = array();
			while ($qDiaB && $r = mysqli_fetch_assoc($qDiaB)) {
				$mapB[$r['Dia']] = (int)$r['Total'];
			}

			$todosDias = array_unique(array_merge(array_keys($mapA), array_keys($mapB)));
			sort($todosDias);
			foreach ($todosDias as $dia) {
				$catsTemp[] = $dia;
				$serieTempA[] = isset($mapA[$dia]) ? $mapA[$dia] : 0;
				$serieTempB[] = isset($mapB[$dia]) ? $mapB[$dia] : 0;
			}
		}
		$temporalComp = array(
			'categorias' => $catsTemp,
			'serie_a' => $serieTempA,
			'serie_b' => $serieTempB
		);

		// 5. Comparativa de Operaciones para Graficos (Ingresar, Actualizar, Eliminar)
		$eventosComp = array(
			'categorias' => array('Ingresar', 'Actualizar', 'Eliminar', 'Otros / Consultas'),
			'serie_a' => array(
				(int)$dataA['eventos']['I'],
				(int)$dataA['eventos']['U'],
				(int)$dataA['eventos']['D'],
				(int)$dataA['eventos']['otros']
			),
			'serie_b' => array(
				(int)$dataB['eventos']['I'],
				(int)$dataB['eventos']['U'],
				(int)$dataB['eventos']['D'],
				(int)$dataB['eventos']['otros']
			)
		);

		// 6. Comparativa de Sesiones
		$sesionesComp = array(
			'categorias' => array('Sesiones Totales', 'Usuarios Unicos', 'Promedio Minutos', 'Cierres Timeout', 'Cierres Forzados'),
			'serie_a' => array(
				(int)$dataA['sesiones']['total'],
				(int)$dataA['sesiones']['usuarios_unicos'],
				(float)$dataA['sesiones']['promedio_min'],
				(int)$dataA['sesiones']['timeout'],
				(int)$dataA['sesiones']['forzadas']
			),
			'serie_b' => array(
				(int)$dataB['sesiones']['total'],
				(int)$dataB['sesiones']['usuarios_unicos'],
				(float)$dataB['sesiones']['promedio_min'],
				(int)$dataB['sesiones']['timeout'],
				(int)$dataB['sesiones']['forzadas']
			)
		);

		// 7. Comparativa de Usuarios Top
		$mapUsuA = array();
		foreach ($dataA['usuarios'] as $u) {
			$nom = trim($u['UsuarioNombre'] . ' ' . $u['UsuarioApellido']);
			if ($nom === '') $nom = 'Usuario ' . $u['Usuario_Id'];
			$mapUsuA[$nom] = (int)$u['Total_Operaciones'];
		}
		$mapUsuB = array();
		foreach ($dataB['usuarios'] as $u) {
			$nom = trim($u['UsuarioNombre'] . ' ' . $u['UsuarioApellido']);
			if ($nom === '') $nom = 'Usuario ' . $u['Usuario_Id'];
			$mapUsuB[$nom] = (int)$u['Total_Operaciones'];
		}
		$topNombres = array_slice(array_unique(array_merge(array_keys($mapUsuB), array_keys($mapUsuA))), 0, 8);
		$sUsuA = array();
		$sUsuB = array();
		foreach ($topNombres as $n) {
			$sUsuA[] = isset($mapUsuA[$n]) ? $mapUsuA[$n] : 0;
			$sUsuB[] = isset($mapUsuB[$n]) ? $mapUsuB[$n] : 0;
		}
		$usuariosComp = array(
			'categorias' => $topNombres,
			'serie_a' => $sUsuA,
			'serie_b' => $sUsuB
		);

		// 8. Generacion de Observaciones Automatizadas
		$observaciones = array();

		// Variacion general
		$pctMov = $calcPct($dataA['total_movimientos'], $dataB['total_movimientos']);
		if ($pctMov > 0) {
			$observaciones[] = "Se observa un incremento general de actividad transaccional del +{$pctMov}% respecto al período base comparado.";
		} elseif ($pctMov < 0) {
			$observaciones[] = "Se registra una reducción transaccional del {$pctMov}% en el volumen total de operaciones auditadas.";
		} else {
			$observaciones[] = "El volumen general de operaciones auditadas se mantuvo estable entre ambos períodos analizados.";
		}

		// Alerta eliminaciones (Eliminar)
		$delA = $dataA['eventos']['D'];
		$delB = $dataB['eventos']['D'];
		if ($delB > $delA && ($delB - $delA) >= 5) {
			$pctDel = $calcPct($delA, $delB);
			$observaciones[] = "ALERTA DE SEGURIDAD: Las operaciones de eliminación (Eliminar) crecieron un +{$pctDel}% (pasando de {$delA} a {$delB}). Se recomienda auditar los registros borrados.";
		} elseif ($delB === 0) {
			$observaciones[] = "Excelente disciplina operativa: 0 operaciones de eliminación (Eliminar) registradas en el período evaluado.";
		}

		// Modulo dominante
		if (!empty($modulosComparativa)) {
			$topM = $modulosComparativa[0];
			if ($dataB['total_movimientos'] > 0) {
				$pctTop = round(($topM['total_b'] / $dataB['total_movimientos']) * 100, 1);
				$observaciones[] = "El módulo '{$topM['modulo']}' concentró la mayor proporción del trabajo con el {$pctTop}% de las operaciones.";
			}
		}

		// Inactividad y cierres
		if ($dataB['sesiones']['timeout'] > 0) {
			$observaciones[] = "Se cerraron {$dataB['sesiones']['timeout']} sesiones por inactividad prolongada (> 15 minutos), protegiendo estaciones de trabajo desatendidas.";
		}
		if ($dataB['sesiones']['forzadas'] > 0) {
			$observaciones[] = "Se ejecutaron {$dataB['sesiones']['forzadas']} cierres de sesión forzados por el Administrador del Sistema.";
		}

		if ($cerrarConAlFinal && $con) {
			mysqli_close($con);
		}

		return array(
			'empresa_nombre' => $empNombre,
			'periodo_a_label' => substr($pA_ini, 0, 10) . ' al ' . substr($pA_fin, 0, 10),
			'periodo_b_label' => substr($pB_ini, 0, 10) . ' al ' . substr($pB_fin, 0, 10),
			'periodo_a_fechas' => array('inicio' => $pA_ini, 'fin' => $pA_fin),
			'periodo_b_fechas' => array('inicio' => $pB_ini, 'fin' => $pB_fin),
			'kpis_comparativa' => $kpisComparativa,
			'modulos_comparativa' => $modulosComparativa,
			'horarios_comparativa' => $horariosComparativa,
			'temporal_comparativa' => $temporalComp,
			'eventos_comparativa' => $eventosComp,
			'sesiones_comparativa' => $sesionesComp,
			'usuarios_comparativa' => $usuariosComp,
			'usuarios_top_b' => $dataB['usuarios'],
			'observaciones' => $observaciones
		);
	}
}

if (!function_exists('aud_dash_preparar_whatsapp')) {
	/**
	 * Prepara el mensaje y enlace de despacho para WhatsApp Web.
	 *
	 * @param array $datos Datos estructurados de la comparativa.
	 * @param string $telefono Telefono destino con codigo de pais.
	 * @param bool $incluirObservaciones Si se incluyen las observaciones calculadas.
	 * @return array
	 */
	function aud_dash_preparar_whatsapp($datos, $telefono, $incluirObservaciones = true) {
		$cleanTel = preg_replace('/[^0-9]/', '', (string)$telefono);
		if ($cleanTel === '' || strlen($cleanTel) < 8) {
			return array(
				'success' => false,
				'message' => 'Debe proporcionar un numero telefonico valido (minimo 8 digitos).'
			);
		}

		$empresa = isset($datos['empresa_nombre']) ? $datos['empresa_nombre'] : 'ExaContable';
		$pa = isset($datos['periodo_a_label']) ? $datos['periodo_a_label'] : '';
		$pb = isset($datos['periodo_b_label']) ? $datos['periodo_b_label'] : '';

		$texto = "*INFORME COMPARATIVO DE AUDITORÍA ERP*\n";
		$texto .= "🏢 Empresa: " . $empresa . "\n";
		if ($pa !== '') $texto .= "📅 Período A: " . $pa . "\n";
		if ($pb !== '') $texto .= "📅 Período B: " . $pb . "\n\n";

		if (!empty($datos['kpis_comparativa']) && is_array($datos['kpis_comparativa'])) {
			$texto .= "📊 *Métricas Destacadas:*\n";
			foreach ($datos['kpis_comparativa'] as $kpi) {
				$signo = (isset($kpi['pct_cambio']) && $kpi['pct_cambio'] > 0) ? '+' : '';
				$valB = isset($kpi['valor_b']) ? number_format($kpi['valor_b']) : '0';
				$pct = isset($kpi['pct_cambio']) ? $kpi['pct_cambio'] : 0;
				$texto .= "• " . $kpi['titulo'] . ": " . $valB . " ({$signo}{$pct}%)\n";
			}
		}

		if ($incluirObservaciones && !empty($datos['observaciones']) && is_array($datos['observaciones'])) {
			$texto .= "\n🔍 *Observaciones:*\n";
			foreach ($datos['observaciones'] as $obs) {
				$texto .= "• " . $obs . "\n";
			}
		}

		$texto .= "\n_Generado por ExaContable ERP Auditoría_";
		$urlWa = "https://api.whatsapp.com/send?phone=" . urlencode($cleanTel) . "&text=" . urlencode($texto);

		return array(
			'success' => true,
			'telefono' => $cleanTel,
			'url_web' => $urlWa,
			'url_whatsapp' => $urlWa,
			'mensaje' => $texto,
			'mensaje_previo' => $texto
		);
	}
}

if (!function_exists('aud_dash_procesar_whatsapp')) {
	function aud_dash_procesar_whatsapp($datos, $telefono) {
		return aud_dash_preparar_whatsapp($datos, $telefono, true);
	}
}

// -------------------------------------------------------------
// CONTROLADOR AJAX PARA PETICIONES DEL FRONTEND DEL DASHBOARD
// -------------------------------------------------------------
if (isset($_REQUEST['action'])) {
	@ini_set('display_errors', '0');
	$action = trim($_REQUEST['action']);

	if (session_id() === '') {
		@session_start();
	}

	$audEmpCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
	$audUsuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;

	// Capturar parametros insensible a mayusculas/minusculas
	$pa_ini = isset($_REQUEST['pa_ini']) ? $_REQUEST['pa_ini'] : (isset($_REQUEST['pA_ini']) ? $_REQUEST['pA_ini'] : '');
	$pa_fin = isset($_REQUEST['pa_fin']) ? $_REQUEST['pa_fin'] : (isset($_REQUEST['pA_fin']) ? $_REQUEST['pA_fin'] : '');
	$pb_ini = isset($_REQUEST['pb_ini']) ? $_REQUEST['pb_ini'] : (isset($_REQUEST['pB_ini']) ? $_REQUEST['pB_ini'] : '');
	$pb_fin = isset($_REQUEST['pb_fin']) ? $_REQUEST['pb_fin'] : (isset($_REQUEST['pB_fin']) ? $_REQUEST['pB_fin'] : '');

	$pa_ini = trim($pa_ini) !== '' ? trim($pa_ini) : date('Y-m-d 00:00:00', strtotime('-60 days'));
	$pa_fin = trim($pa_fin) !== '' ? trim($pa_fin) : date('Y-m-d 23:59:59', strtotime('-31 days'));
	$pb_ini = trim($pb_ini) !== '' ? trim($pb_ini) : date('Y-m-d 00:00:00', strtotime('-30 days'));
	$pb_fin = trim($pb_fin) !== '' ? trim($pb_fin) : date('Y-m-d 23:59:59');

	switch ($action) {
		case 'consultar':
			@header('Content-Type: application/json; charset=utf-8');
			$datos = aud_dash_calcular_comparativa($audEmpCod, $pa_ini, $pa_fin, $pb_ini, $pb_fin);
			$datos = aud_dash_to_utf8_deep($datos);
			echo json_encode(array('success' => true, 'data' => $datos));
			exit();

		case 'exportar_pdf':
			require_once dirname(__FILE__) . '/aud_rep_comparativa_pdf.php';
			$datos = aud_dash_calcular_comparativa($audEmpCod, $pa_ini, $pa_fin, $pb_ini, $pb_fin);
			aud_generar_reporte_comparativo_pdf($datos, 'I');
			exit();

		case 'enviar_correo':
			@header('Content-Type: application/json; charset=utf-8');
			$destCorreo = isset($_REQUEST['correo']) ? trim($_REQUEST['correo']) : '';
			$destNombre = isset($_REQUEST['nombre']) ? trim($_REQUEST['nombre']) : 'Destinatario';
			$asunto     = isset($_REQUEST['asunto']) ? trim($_REQUEST['asunto']) : 'Informe Comparativo de Auditoria';

			if ($destCorreo === '' || !filter_var($destCorreo, FILTER_VALIDATE_EMAIL)) {
				echo json_encode(array('success' => false, 'message' => 'Debe proporcionar un correo electrónico válido.'));
				exit();
			}

			require_once dirname(__FILE__) . '/aud_rep_comparativa_pdf.php';
			$datos = aud_dash_calcular_comparativa($audEmpCod, $pa_ini, $pa_fin, $pb_ini, $pb_fin);
			
			// Generar PDF en memoria ('S')
			$pdfContenido = aud_generar_reporte_comparativo_pdf($datos, 'S');

			// Integracion con PHPMailer del ERP
			$mailEnviado = false;
			$errMsg = '';

			$phpMailerPath = dirname(__FILE__) . '/../../Librerias/PHPMailer_2023/src/PHPMailer.php';
			if (!file_exists($phpMailerPath)) {
				$phpMailerPath = dirname(__FILE__) . '/../../Librerias/PHPMailer/class.phpmailer.php';
			}

			if (file_exists($phpMailerPath)) {
				try {
					if (strpos($phpMailerPath, '2023') !== false) {
						require_once dirname(__FILE__) . '/../../Librerias/PHPMailer_2023/src/Exception.php';
						require_once dirname(__FILE__) . '/../../Librerias/PHPMailer_2023/src/PHPMailer.php';
						require_once dirname(__FILE__) . '/../../Librerias/PHPMailer_2023/src/SMTP.php';
						$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
					} else {
						require_once $phpMailerPath;
						$mail = new PHPMailer();
					}

					$mail->isHTML(true);
					$mail->CharSet = 'UTF-8';
					$mail->Subject = $asunto;
					$mail->addAddress($destCorreo, $destNombre);

					$cuerpoHtml = "
					<h3>Informe Estadístico Comparativo de Auditoría</h3>
					<p>Estimado/a <strong>{$destNombre}</strong>,</p>
					<p>Adjunto a este correo encontrará el reporte formal de comparación de auditoría para la empresa <strong>{$datos['empresa_nombre']}</strong>.</p>
					<ul>
						<li><strong>Período A (Base):</strong> {$datos['periodo_a_label']}</li>
						<li><strong>Período B (Comparado):</strong> {$datos['periodo_b_label']}</li>
					</ul>
					<p>Este documento contiene métricas de volumen, operaciones de ingreso, actualización y eliminación, estadísticas de sesiones y controles de inactividad.</p>
					<hr>
					<p style='font-size:11px;color:#777;'>Generado automáticamente por el Módulo de Auditoría de ExaContable ERP.</p>";

					$mail->Body = $cuerpoHtml;
					$mail->addStringAttachment($pdfContenido, 'reporte_comparativo_auditoria.pdf', 'base64', 'application/pdf');

					// Enviar correo si está configurado el transporte
					if (@$mail->send()) {
						$mailEnviado = true;
					} else {
						$errMsg = isset($mail->ErrorInfo) ? $mail->ErrorInfo : 'No se pudo despachar el mensaje.';
					}
				} catch (\Exception $eMail) {
					$errMsg = $eMail->getMessage();
				}
			} else {
				$errMsg = 'Librería PHPMailer no encontrada en el sistema.';
			}

			// Mock / Fallback de despacho
			if (!$mailEnviado) {
				echo json_encode(array(
					'success' => true,
					'simulado' => true,
					'message' => "El reporte PDF comparativo fue generado satisfactoriamente para '{$destCorreo}'. (Nota: {$errMsg})"
				));
			} else {
				echo json_encode(array(
					'success' => true,
					'simulado' => false,
					'message' => "El reporte PDF comparativo ha sido enviado exitosamente a '{$destCorreo}'."
				));
			}
			exit();

		case 'enviar_whatsapp':
			@header('Content-Type: application/json; charset=utf-8');
			$telefono = isset($_REQUEST['telefono']) ? $_REQUEST['telefono'] : '';
			$datos = aud_dash_calcular_comparativa($audEmpCod, $pa_ini, $pa_fin, $pb_ini, $pb_fin);
			$resWa = aud_dash_preparar_whatsapp($datos, $telefono, true);
			echo json_encode($resWa);
			exit();
	}
}
