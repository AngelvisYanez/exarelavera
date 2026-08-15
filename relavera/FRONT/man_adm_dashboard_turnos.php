<?php

/**
 * @abstract Dashboard de Turnos - Reporte completo de configuraciones, turnos y manifiestos
 * @author Sistema EXA
 * @version 1.0
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Mani;

/* ==================== AJAX HANDLERS ==================== */

// Obtener lista de configuraciones disponibles
if (isset($getConfiguracionesDisponiblesAjax)) {
    $resultado = array('success' => true);

    $configuraciones = $obBD_con1->getArrayConsulta(
        'manifiesto_turnos_cab.selectWhere',
        array('where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Tur_Est !="I"'), 'order' => 'Tur_Fei DESC'),
        $obBD_conexion,
        true
    );

    $configsList = array();
    foreach ($configuraciones as $config) {
        $fechaInicioFormato = '';
        $fechaFinFormato = '';
        if (!empty($config['Tur_Fei'])) {
            $fechaObj = new DateTime($config['Tur_Fei']);
            $fechaInicioFormato = $fechaObj->format('d/m/Y');
        }
        if (!empty($config['Tur_Fef'])) {
            $fechaObj = new DateTime($config['Tur_Fef']);
            $fechaFinFormato = $fechaObj->format('d/m/Y');
        }

        $configsList[] = array(
            'Tur_Cod' => intval($config['Tur_Cod']),
            'texto' => 'Configuración #' . $config['Tur_Cod'] . ' (' . $fechaInicioFormato . ' - ' . $fechaFinFormato . ')'
        );
    }

    $resultado['configuraciones'] = $configsList;
    $obBD_con1->echoJson($resultado);
}

// Obtener lista de plantas disponibles
if (isset($getPlantasDisponiblesAjax)) {
    $resultado = array('success' => true);
    $sql = "SELECT manifiesto_plantas.Pla_Cod, manifiesto_plantas.Pla_Nom 
            FROM manifiesto_plantas 
            LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
            WHERE manifiesto_plantas.Pla_Est = 'A' 
            AND (cliente.Emp_Cod = " . intval($Ses_Emp_Cod) . " OR manifiesto_plantas.Cli_Cod IS NULL)
            ORDER BY manifiesto_plantas.Pla_Nom";
    $res = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    $plantas = array();
    while ($row = $obBD_con1->fetch_assoc($res)) {
        $obBD_con1->utf8_change_param($row);
        $plantas[] = array('Pla_Cod' => intval($row['Pla_Cod']), 'Pla_Nom' => $row['Pla_Nom']);
    }
    $resultado['plantas'] = $plantas;
    $obBD_con1->echoJson($resultado);
}

// Dashboard por rango de fechas (agrupado por día, sin configuraciones)
if (isset($getDashboardPorRangoAjax)) {
    $resultado = array('success' => true);

    $fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
    $Pla_Cod = isset($_GET['Pla_Cod']) ? intval($_GET['Pla_Cod']) : 0;

    if (!$fecha_inicio || !$fecha_fin) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Debe indicar el rango de fechas'));
        exit;
    }

    // Convertir fechas d/m/Y a Y-m-d si aplica
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_inicio, $m)) {
        $fecha_inicio = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_fin, $m)) {
        $fecha_fin = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }

    $fecha_inicio_esc = mysqli_real_escape_string($obBD_conexion->conexion, $fecha_inicio);
    $fecha_fin_esc = mysqli_real_escape_string($obBD_conexion->conexion, $fecha_fin);

    $condPlaActivos = ($Pla_Cod > 0) ? "(manifiesto.Man_Est = 'A' AND manifiesto.Pla_Cod = $Pla_Cod)" : "manifiesto.Man_Est = 'A'";
    $condPlaInactivos = ($Pla_Cod > 0) ? "(manifiesto.Man_Est = 'I' AND manifiesto.Pla_Cod = $Pla_Cod)" : "manifiesto.Man_Est = 'I'";

    // MAX(Tud_Cup) evita duplicar cupos cuando varias configuraciones se superponen en las mismas fechas
    // MIN(Tud_Cod) para poder consultar manifiestos del slot
    $sql = "SELECT 
                manifiesto_turnos_det.Tud_Fec,
                manifiesto_turnos_det.Tud_Hin as hora_inicio,
                manifiesto_turnos_det.Tud_Hfi as hora_fin,
                MIN(manifiesto_turnos_det.Tud_Cod) as Tud_Cod,
                MAX(manifiesto_turnos_det.Tud_Cup) as cupos,
                COALESCE(SUM(CASE WHEN $condPlaActivos THEN 1 ELSE 0 END), 0) as ocupados,
                COALESCE(SUM(CASE WHEN $condPlaInactivos THEN 1 ELSE 0 END), 0) as manifiestos_inactivos
            FROM manifiesto_turnos_cab
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tur_Cod = manifiesto_turnos_cab.Tur_Cod
            LEFT JOIN manifiesto ON manifiesto.Tud_Cod = manifiesto_turnos_det.Tud_Cod
            WHERE manifiesto_turnos_cab.Emp_Cod = $Ses_Emp_Cod 
            AND manifiesto_turnos_cab.Tur_Est != 'I'
            AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
            AND manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc'
            GROUP BY manifiesto_turnos_det.Tud_Fec, manifiesto_turnos_det.Tud_Hin, manifiesto_turnos_det.Tud_Hfi
            ORDER BY manifiesto_turnos_det.Tud_Fec ASC, manifiesto_turnos_det.Tud_Hin ASC";

    $result = $obBD_con1->consulta($sql, $obBD_conexion->conexion);

    $diasMap = array();
    while ($row = $obBD_con1->fetch_assoc($result)) {
        $obBD_con1->utf8_change_param($row);
        $fecha = $row['Tud_Fec'];
        $fechaFormato = '';
        if (!empty($fecha)) {
            $fechaObj = new DateTime($fecha);
            $fechaFormato = $fechaObj->format('d/m/Y');
        }

        if (!isset($diasMap[$fecha])) {
            $dias = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');
            $diaSemana = '';
            if (!empty($fecha)) {
                $dtDia = new DateTime($fecha);
                $diaSemana = $dias[$dtDia->format('w')];
            }
            $diasMap[$fecha] = array(
                'Tud_Fec' => $fechaFormato,
                'Tud_Fec_SQL' => $fecha,
                'dia_semana' => $diaSemana,
                'turnos_detalle' => array(),
                'total_cupos' => 0,
                'total_cupos_ocupados' => 0
            );
        }

        $cupos = isset($row['cupos']) ? intval($row['cupos']) : 0;
        $ocupados = isset($row['ocupados']) ? intval($row['ocupados']) : 0;
        $inactivos = isset($row['manifiestos_inactivos']) ? intval($row['manifiestos_inactivos']) : 0;

        $horaInicio = isset($row['hora_inicio']) ? $row['hora_inicio'] : '';
        $horaFin = isset($row['hora_fin']) ? $row['hora_fin'] : '';
        $horarioTexto = ($horaInicio && $horaFin) ? date('H:i', strtotime($horaInicio)) . ' - ' . date('H:i', strtotime($horaFin)) : '';
        $tudCod = isset($row['Tud_Cod']) ? intval($row['Tud_Cod']) : 0;

        $diasMap[$fecha]['total_cupos'] += $cupos;
        $diasMap[$fecha]['total_cupos_ocupados'] += $ocupados;
        $diasMap[$fecha]['turnos_detalle'][] = array(
            'Tud_Cod' => $tudCod,
            'Tud_Fec' => $fecha,
            'Tud_Hin' => $horaInicio,
            'Tud_Hfi' => $horaFin,
            'horario' => $horarioTexto,
            'cupos' => $cupos,
            'ocupados' => $ocupados,
            'libres' => max(0, $cupos - $ocupados),
            'porcentaje_ocupacion' => $cupos > 0 ? round(($ocupados / $cupos) * 100, 2) : 0,
            'manifiestos_inactivos' => $inactivos
        );
    }

    $diasOrdenados = array();
    foreach ($diasMap as $fec => $dia) {
        $dia['total_cupos_libres'] = max(0, $dia['total_cupos'] - $dia['total_cupos_ocupados']);
        $dia['porcentaje_ocupacion_general'] = $dia['total_cupos'] > 0 ? round(($dia['total_cupos_ocupados'] / $dia['total_cupos']) * 100, 2) : 0;
        $diasOrdenados[] = $dia;
    }

    $resultado['dias'] = $diasOrdenados;
    $resultado['fecha_inicio'] = $fecha_inicio;
    $resultado['fecha_fin'] = $fecha_fin;
    $obBD_con1->echoJson($resultado);
}

// Obtener datos del dashboard
if (isset($getDashboardTurnosAjax)) {
    $resultado = array('success' => true);

    $Tur_Cod = isset($_GET['Tur_Cod']) ? intval($_GET['Tur_Cod']) : 0;

    // Construir condición WHERE para configuraciones
    $whereCab = "manifiesto_turnos_cab.Emp_Cod = $Ses_Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I'";
    if ($Tur_Cod > 0) {
        $whereCab .= " AND manifiesto_turnos_cab.Tur_Cod = $Tur_Cod";
    }

    // UNA SOLA CONSULTA OPTIMIZADA: Obtener todas las configuraciones, turnos detalle y conteos de manifiestos
    $sql = "SELECT 
                manifiesto_turnos_cab.Tur_Cod,
                manifiesto_turnos_cab.Tur_Fei,
                manifiesto_turnos_cab.Tur_Fef,
                manifiesto_turnos_det.Tud_Cod,
                manifiesto_turnos_det.Tud_Fec,
                manifiesto_turnos_det.Tud_Hin as hora_inicio,
                manifiesto_turnos_det.Tud_Hfi as hora_fin,
                manifiesto_turnos_det.Tud_Cup,
                COALESCE(COUNT(DISTINCT manifiesto.Man_Cod), 0) as total_manifiestos,
                COALESCE(SUM(CASE WHEN manifiesto.Man_Est = 'A' THEN 1 ELSE 0 END), 0) as manifiestos_activos,
                COALESCE(SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END), 0) as manifiestos_inactivos
            FROM manifiesto_turnos_cab
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tur_Cod = manifiesto_turnos_cab.Tur_Cod
            LEFT JOIN manifiesto ON manifiesto.Tud_Cod = manifiesto_turnos_det.Tud_Cod
            WHERE $whereCab
            AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
            GROUP BY 
                manifiesto_turnos_cab.Tur_Cod,
                manifiesto_turnos_cab.Tur_Fei,
                manifiesto_turnos_cab.Tur_Fef,
                manifiesto_turnos_det.Tud_Cod,
                manifiesto_turnos_det.Tud_Fec,
                manifiesto_turnos_det.Tud_Hin,
                manifiesto_turnos_det.Tud_Hfi,
                manifiesto_turnos_det.Tud_Cup
            ORDER BY 
                manifiesto_turnos_cab.Tur_Fei DESC,
                manifiesto_turnos_det.Tud_Fec ASC,
                manifiesto_turnos_det.Tud_Hin ASC";

    $result = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    $filasTurnos = array();
    if ($result !== false) {
        while ($row = $obBD_con1->fetch_assoc($result)) {
            $obBD_con1->utf8_change_param($row);
            $filasTurnos[] = $row;
        }
    }

    // Consultar eventos Garita IN (GE) agrupados por la franja horaria real de llegada
    $garitaMap = array();
    $sqlGarita = "SELECT manifiesto.Tud_Cod, manifiesto.Man_Fes, manifiesto.Man_Fec, manifiesto.Man_Usu, manifiesto.Man_Tes
                  FROM manifiesto
                  INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
                  INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
                  WHERE $whereCab AND manifiesto.Man_Est = 'A' AND (manifiesto.Man_Usu LIKE '%GE%' OR manifiesto.Man_Tes LIKE '%GE%')";
    $resGarita = $obBD_con1->consulta($sqlGarita, $obBD_conexion->conexion);
    if ($resGarita !== false) {
        while ($gRow = $obBD_con1->fetch_assoc($resGarita)) {
            $obBD_con1->utf8_change_param($gRow);
            $fes = !empty($gRow['Man_Fes']) ? $gRow['Man_Fes'] : (!empty($gRow['Man_Fec']) ? $gRow['Man_Fec'] : '');
            $rawUsu = !empty($gRow['Man_Usu']) ? stripslashes(str_replace('&quot;', '"', $gRow['Man_Usu'])) : '';
            $fecha_ge = null;

            if ($rawUsu && strpos($rawUsu, 'GE') !== false) {
                $usu_data = @json_decode($rawUsu, true);
                if (is_array($usu_data)) {
                    $eventos = isset($usu_data[0]) && is_array($usu_data[0]) ? $usu_data : array($usu_data);
                    foreach ($eventos as $ev) {
                        $ev_tip = isset($ev['Man_Tip']) ? $ev['Man_Tip'] : (isset($ev['man_tip']) ? $ev['man_tip'] : '');
                        $ev_fec = isset($ev['Fecha']) ? $ev['Fecha'] : (isset($ev['fecha']) ? $ev['fecha'] : '');
                        if ($ev_tip === 'GE' && $ev_fec && (!$fecha_ge || $ev_fec < $fecha_ge)) $fecha_ge = $ev_fec;
                    }
                }
                if (!$fecha_ge && preg_match('/(?:GE.*?Fecha|Fecha.*?GE)["\']\s*:\s*["\']([^"\']+)/i', $rawUsu, $m)) {
                    $fecha_ge = $m[1];
                }
            }
            if (!$fecha_ge && !empty($gRow['Man_Tes']) && strpos($gRow['Man_Tes'], 'GE') !== false) {
                $fecha_ge = $fes;
            }

            if ($fecha_ge) {
                $targetTud = intval($gRow['Tud_Cod']);
                $fecGeOnly = date('Y-m-d', strtotime($fecha_ge));
                $horaGeOnly = date('H:i:s', strtotime($fecha_ge));

                foreach ($filasTurnos as $ft) {
                    if (!empty($ft['Tud_Fec']) && $ft['Tud_Fec'] == $fecGeOnly && !empty($ft['hora_inicio']) && !empty($ft['hora_fin'])) {
                        $hin = date('H:i:s', strtotime($ft['hora_inicio']));
                        $hfi = date('H:i:s', strtotime($ft['hora_fin']));
                        if ($horaGeOnly >= $hin && $horaGeOnly < $hfi) {
                            $targetTud = intval($ft['Tud_Cod']);
                            break;
                        }
                    }
                }

                if (!isset($garitaMap[$targetTud])) $garitaMap[$targetTud] = array('conteo' => 0, 'suma_minutos' => 0);
                $garitaMap[$targetTud]['conteo']++;
                if ($fes && strtotime($fecha_ge) > strtotime($fes)) {
                    $garitaMap[$targetTud]['suma_minutos'] += round((strtotime($fecha_ge) - strtotime($fes)) / 60);
                }
            }
        }
    }

    $configuracionesMap = array();
    foreach ($filasTurnos as $row) {
        $obBD_con1->utf8_change_param($row);

        $turCod = intval($row['Tur_Cod']);
        $tudCod = intval($row['Tud_Cod']);

        // Inicializar configuración si no existe
        if (!isset($configuracionesMap[$turCod])) {
            // Formatear fechas de configuración
            $fechaInicioFormato = '';
            $fechaFinFormato = '';
            if (!empty($row['Tur_Fei'])) {
                $fechaObj = new DateTime($row['Tur_Fei']);
                $fechaInicioFormato = $fechaObj->format('d/m/Y');
            }
            if (!empty($row['Tur_Fef'])) {
                $fechaObj = new DateTime($row['Tur_Fef']);
                $fechaFinFormato = $fechaObj->format('d/m/Y');
            }

            $configuracionesMap[$turCod] = array(
                'Tur_Cod' => $turCod,
                'Tur_Fei' => $fechaInicioFormato,
                'Tur_Fei_SQL' => $row['Tur_Fei'],
                'Tur_Fef' => $fechaFinFormato,
                'Tur_Fef_SQL' => $row['Tur_Fef'],
                'turnos_detalle' => array(),
                'total_cupos' => 0,
                'total_cupos_ocupados' => 0
            );
        }

        $cupos = isset($row['Tud_Cup']) ? intval($row['Tud_Cup']) : 0;
        $ocupados = isset($row['manifiestos_activos']) ? intval($row['manifiestos_activos']) : 0;
        $totalManifiestos = isset($row['total_manifiestos']) ? intval($row['total_manifiestos']) : 0;
        $inactivos = isset($row['manifiestos_inactivos']) ? intval($row['manifiestos_inactivos']) : 0;

        $configuracionesMap[$turCod]['total_cupos'] += $cupos;
        $configuracionesMap[$turCod]['total_cupos_ocupados'] += $ocupados;

        // Formatear horas (formato 24 horas)
        $horaInicio = isset($row['hora_inicio']) ? $row['hora_inicio'] : '';
        $horaFin = isset($row['hora_fin']) ? $row['hora_fin'] : '';

        $horarioTexto = '';
        if ($horaInicio && $horaFin) {
            $horaInicioFormato = date('H:i', strtotime($horaInicio));
            $horaFinFormato = date('H:i', strtotime($horaFin));
            $horarioTexto = $horaInicioFormato . ' - ' . $horaFinFormato;
        }

        // Formatear fecha
        $fechaFormato = '';
        if (!empty($row['Tud_Fec'])) {
            $fechaObj = new DateTime($row['Tud_Fec']);
            $fechaFormato = $fechaObj->format('d/m/Y');
        }

        $gConteo = isset($garitaMap[$tudCod]) ? $garitaMap[$tudCod]['conteo'] : 0;
        $gPromMin = (isset($garitaMap[$tudCod]) && $garitaMap[$tudCod]['conteo'] > 0)
            ? round($garitaMap[$tudCod]['suma_minutos'] / $garitaMap[$tudCod]['conteo'])
            : 0;

        $configuracionesMap[$turCod]['turnos_detalle'][] = array(
            'Tud_Cod' => $tudCod,
            'Tud_Fec' => $fechaFormato,
            'Tud_Fec_SQL' => $row['Tud_Fec'],
            'horario' => $horarioTexto,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'cupos' => $cupos,
            'ocupados' => $ocupados,
            'libres' => max(0, $cupos - $ocupados),
            'porcentaje_ocupacion' => $cupos > 0 ? round(($ocupados / $cupos) * 100, 2) : 0,
            'total_manifiestos' => $totalManifiestos,
            'manifiestos_activos' => $ocupados,
            'manifiestos_inactivos' => $inactivos,
            'garita_in' => $gConteo,
            'tiempo_garita' => $gPromMin
        );
    }

    // Convertir map a array y calcular totales finales
    $dashboardData = array();
    foreach ($configuracionesMap as $turCod => $config) {
        $totalCupos = $config['total_cupos'];
        $totalCuposOcupados = $config['total_cupos_ocupados'];

        $dashboardData[] = array(
            'Tur_Cod' => $config['Tur_Cod'],
            'Tur_Fei' => $config['Tur_Fei'],
            'Tur_Fei_SQL' => $config['Tur_Fei_SQL'],
            'Tur_Fef' => $config['Tur_Fef'],
            'Tur_Fef_SQL' => $config['Tur_Fef_SQL'],
            'total_turnos_detalle' => count($config['turnos_detalle']),
            'total_cupos' => $totalCupos,
            'total_cupos_ocupados' => $totalCuposOcupados,
            'total_cupos_libres' => max(0, $totalCupos - $totalCuposOcupados),
            'porcentaje_ocupacion_general' => $totalCupos > 0 ? round(($totalCuposOcupados / $totalCupos) * 100, 2) : 0,
            'turnos_detalle' => $config['turnos_detalle']
        );
    }

    $resultado['configuraciones'] = $dashboardData;
    $obBD_con1->echoJson($resultado);
}

// Obtener manifiestos de un turno específico
if (isset($getManifiestosDashboardAjax)) {
    $resultado = array('success' => true);

    $Tud_Cod = isset($_GET['Tud_Cod']) ? intval($_GET['Tud_Cod']) : 0;

    if ($Tud_Cod <= 0) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Turno no válido'));
        exit;
    }

    // Obtener información del turno
    $sqlTurno = "SELECT 
                    manifiesto_turnos_det.Tud_Fec,
                    manifiesto_turnos_det.Tud_Hin,
                    manifiesto_turnos_det.Tud_Hfi
                FROM manifiesto_turnos_det
                WHERE manifiesto_turnos_det.Tud_Cod = $Tud_Cod
                LIMIT 1";

    $resultTurno = $obBD_con1->consulta($sqlTurno, $obBD_conexion->conexion);
    $infoTurno = $obBD_con1->fetch_assoc($resultTurno);

    $turnoInfo = array();
    if ($infoTurno) {
        $obBD_con1->utf8_change_param($infoTurno);

        // Formatear fecha
        $fechaFormato = '';
        if (!empty($infoTurno['Tud_Fec'])) {
            $fechaObj = new DateTime($infoTurno['Tud_Fec']);
            $fechaFormato = $fechaObj->format('d/m/Y');
        }

        // Formatear horas (formato 24 horas)
        $horaInicio = isset($infoTurno['Tud_Hin']) ? $infoTurno['Tud_Hin'] : '';
        $horaFin = isset($infoTurno['Tud_Hfi']) ? $infoTurno['Tud_Hfi'] : '';

        $horarioTexto = '';
        if ($horaInicio && $horaFin) {
            $horaInicioFormato = date('H:i', strtotime($horaInicio));
            $horaFinFormato = date('H:i', strtotime($horaFin));
            $horarioTexto = $horaInicioFormato . ' - ' . $horaFinFormato;
        }

        $turnoInfo = array(
            'fecha' => $fechaFormato,
            'horario' => $horarioTexto
        );
    }

    // Obtener manifiestos
    $sql = "SELECT 
                manifiesto.Man_Cod,
                manifiesto.Man_Num,
                manifiesto.Man_Usu,
                DATE(manifiesto.Man_Fec) as Man_Fec,
                DATE_FORMAT(manifiesto.Man_Fec, '%H:%i') as Man_Hor,
                DATE(manifiesto.Man_Fes) as Man_Fes,
                CONCAT('M', manifiesto_plantas.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, 0)) as ManNum,
                CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as Cliente,
                manifiesto_plantas.Pla_Nom,
                manifiesto.Man_Est,
				if(LOCATE('GE', Man_Tes) > 0,'GE','')as Man_Tip_1,
				if(LOCATE('A', Man_Tes) > 0,'A','')as Man_Tip_2,
				if(LOCATE('GS', Man_Tes) > 0,'GS','')as Man_Tip_3,				
				if(LOCATE('F', Man_Tes) > 0,'F','')as Man_Tip_4,
				if(LOCATE('R', Man_Tes) > 0,'R','')as Man_Tip_5,
                IF(manifiesto.Man_Est='A','ACTIVO','INACTIVO') as estado_texto
            FROM manifiesto
            INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
            INNER JOIN persona as persona_cli ON persona_cli.Prs_Cod = cliente.Prs_Cod
            INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
            WHERE manifiesto.Tud_Cod = $Tud_Cod
            AND manifiesto.Man_Est = 'A'
            AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
            AND cliente.Emp_Cod = $Ses_Emp_Cod
            ORDER BY manifiesto.Man_Fes DESC, manifiesto.Man_Fec DESC";

    $result = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    $manifiestos = array();

    while ($row = $obBD_con1->fetch_assoc($result)) {
        $obBD_con1->utf8_change_param($row);
        $garitaIng = '';
        if (!empty($row['Man_Usu']) && strpos($row['Man_Usu'], 'GE') !== false) {
            $jsonArr = @json_decode($row['Man_Usu'], true);
            if (is_array($jsonArr)) {
                foreach ($jsonArr as $ev) {
                    if (isset($ev['Man_Tip']) && $ev['Man_Tip'] === 'GE' && !empty($ev['Fecha'])) {
                        $garitaIng = $ev['Fecha'];
                        break;
                    }
                }
            }
        }
        $row['garita_ingreso'] = $garitaIng;
        $manifiestos[] = $row;
    }

    $resultado['manifiestos'] = $manifiestos;
    $resultado['turnoInfo'] = $turnoInfo;
    $obBD_con1->echoJson($resultado);
}

// Obtener manifiestos acumulados del día (todas las horas)
if (isset($getManifiestosDiaAjax)) {
    $resultado = array('success' => true);

    $Tud_Cod = isset($_GET['Tud_Cod']) ? intval($_GET['Tud_Cod']) : 0;

    if ($Tud_Cod <= 0) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Turno no válido'));
        exit;
    }

    $sqlTurno = "SELECT manifiesto_turnos_det.Tur_Cod, manifiesto_turnos_det.Tud_Fec
                FROM manifiesto_turnos_det
                WHERE manifiesto_turnos_det.Tud_Cod = $Tud_Cod
                LIMIT 1";

    $resultTurno = $obBD_con1->consulta($sqlTurno, $obBD_conexion->conexion);
    $infoTurno = $obBD_con1->fetch_assoc($resultTurno);

    if (!$infoTurno) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Turno no encontrado'));
        exit;
    }

    $obBD_con1->utf8_change_param($infoTurno);
    $Tur_Cod = intval($infoTurno['Tur_Cod']);
    $Tud_Fec = $infoTurno['Tud_Fec'];

    $fechaFormato = '';
    if (!empty($Tud_Fec)) {
        $fechaObj = new DateTime($Tud_Fec);
        $fechaFormato = $fechaObj->format('d/m/Y');
    }

    $turnoInfo = array(
        'fecha' => $fechaFormato,
        'horario' => 'Acumulado del día'
    );

    $sql = "SELECT 
                manifiesto.Man_Cod,
                manifiesto.Man_Num,
                manifiesto.Man_Usu,
                DATE(manifiesto.Man_Fec) as Man_Fec,
                DATE_FORMAT(manifiesto.Man_Fec, '%H:%i') as Man_Hor,
                DATE(manifiesto.Man_Fes) as Man_Fes,
                CONCAT('M', manifiesto_plantas.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, 0)) as ManNum,
                CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as Cliente,
                manifiesto_plantas.Pla_Nom,
                manifiesto.Man_Est,
                manifiesto_turnos_det.Tud_Hin,
                manifiesto_turnos_det.Tud_Hfi,
				if(LOCATE('GE', manifiesto.Man_Tes) > 0,'GE','')as Man_Tip_1,
				if(LOCATE('A', manifiesto.Man_Tes) > 0,'A','')as Man_Tip_2,
				if(LOCATE('GS', manifiesto.Man_Tes) > 0,'GS','')as Man_Tip_3,				
				if(LOCATE('F', manifiesto.Man_Tes) > 0,'F','')as Man_Tip_4,
				if(LOCATE('R', manifiesto.Man_Tes) > 0,'R','')as Man_Tip_5,
                IF(manifiesto.Man_Est='A','ACTIVO','INACTIVO') as estado_texto
            FROM manifiesto
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
            INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
            INNER JOIN persona as persona_cli ON persona_cli.Prs_Cod = cliente.Prs_Cod
            INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
            WHERE manifiesto_turnos_det.Tur_Cod = $Tur_Cod
            AND manifiesto_turnos_det.Tud_Fec = '" . mysqli_real_escape_string($obBD_conexion->conexion, $Tud_Fec) . "'
            AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
            AND manifiesto.Man_Est = 'A'
            AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
            AND cliente.Emp_Cod = $Ses_Emp_Cod
            ORDER BY manifiesto.Man_Fes DESC, manifiesto.Man_Fec DESC";

    $result = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    $manifiestos = array();

    while ($row = $obBD_con1->fetch_assoc($result)) {
        $obBD_con1->utf8_change_param($row);
        $garitaIng = '';
        if (!empty($row['Man_Usu'])) {
            $rawUsu = stripslashes(str_replace('&quot;', '"', $row['Man_Usu']));
            $jsonArr = @json_decode($rawUsu, true);
            if (!is_array($jsonArr)) {
                $jsonArr = @json_decode($row['Man_Usu'], true);
            }
            if (is_array($jsonArr)) {
                foreach ($jsonArr as $ev) {
                    if (isset($ev['Man_Tip']) && $ev['Man_Tip'] === 'GE' && !empty($ev['Fecha'])) {
                        $garitaIng = $ev['Fecha'];
                        break;
                    }
                }
            }
            if (empty($garitaIng)) {
                if (preg_match('/GE["\']\s*,\s*["\']Fecha["\']\s*:\s*["\']([^"\']+)["\']/i', $rawUsu, $m)) {
                    $garitaIng = $m[1];
                } elseif (preg_match('/["\']Man_Tip["\']\s*:\s*["\']GE["\'].*?["\']Fecha["\']\s*:\s*["\']([^"\']+)["\']/i', $rawUsu, $m)) {
                    $garitaIng = $m[1];
                }
            }
        }
        $row['garita_ingreso'] = $garitaIng;
        $horaInicio = isset($row['Tud_Hin']) ? $row['Tud_Hin'] : '';
        $horaFin = isset($row['Tud_Hfi']) ? $row['Tud_Hfi'] : '';
        $row['horario_turno'] = ($horaInicio && $horaFin) ? date('H:i', strtotime($horaInicio)) . ' - ' . date('H:i', strtotime($horaFin)) : '';
        unset($row['Tud_Hin']);
        unset($row['Tud_Hfi']);
        $manifiestos[] = $row;
    }

    $resultado['manifiestos'] = $manifiestos;
    $resultado['turnoInfo'] = $turnoInfo;
    $obBD_con1->echoJson($resultado);
}

// Obtener manifiestos acumulados de toda la configuración
if (isset($getManifiestosConfiguracionAjax)) {
    $resultado = array('success' => true);

    $Tur_Cod = isset($_GET['Tur_Cod']) ? intval($_GET['Tur_Cod']) : 0;

    if ($Tur_Cod <= 0) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Configuración no válida'));
        exit;
    }

    $turnoInfo = array(
        'fecha' => '',
        'horario' => 'Acumulado de la configuración'
    );

    $sql = "SELECT 
                manifiesto.Man_Cod,
                manifiesto.Man_Num,
                manifiesto.Man_Usu,
                DATE(manifiesto.Man_Fec) as Man_Fec,
                DATE_FORMAT(manifiesto.Man_Fec, '%H:%i') as Man_Hor,
                DATE(manifiesto.Man_Fes) as Man_Fes,
                manifiesto_turnos_det.Tud_Fec,
                manifiesto_turnos_det.Tud_Hin,
                manifiesto_turnos_det.Tud_Hfi,
                CONCAT('M', manifiesto_plantas.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, 0)) as ManNum,
                CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as Cliente,
                manifiesto_plantas.Pla_Nom,
                manifiesto.Man_Est,
				if(LOCATE('GE', manifiesto.Man_Tes) > 0,'GE','')as Man_Tip_1,
				if(LOCATE('A', manifiesto.Man_Tes) > 0,'A','')as Man_Tip_2,
				if(LOCATE('GS', manifiesto.Man_Tes) > 0,'GS','')as Man_Tip_3,				
				if(LOCATE('F', manifiesto.Man_Tes) > 0,'F','')as Man_Tip_4,
				if(LOCATE('R', manifiesto.Man_Tes) > 0,'R','')as Man_Tip_5,
                IF(manifiesto.Man_Est='A','ACTIVO','INACTIVO') as estado_texto
            FROM manifiesto
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
            INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
            INNER JOIN persona as persona_cli ON persona_cli.Prs_Cod = cliente.Prs_Cod
            INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
            WHERE manifiesto_turnos_det.Tur_Cod = $Tur_Cod
            AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
            AND manifiesto.Man_Est = 'A'
            AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
            AND cliente.Emp_Cod = $Ses_Emp_Cod
            ORDER BY manifiesto_turnos_det.Tud_Fec ASC, manifiesto.Man_Fes DESC, manifiesto.Man_Fec DESC";

    $result = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    $manifiestos = array();

    while ($row = $obBD_con1->fetch_assoc($result)) {
        $obBD_con1->utf8_change_param($row);
        $garitaIng = '';
        if (!empty($row['Man_Usu'])) {
            $rawUsu = stripslashes(str_replace('&quot;', '"', $row['Man_Usu']));
            $jsonArr = @json_decode($rawUsu, true);
            if (!is_array($jsonArr)) {
                $jsonArr = @json_decode($row['Man_Usu'], true);
            }
            if (is_array($jsonArr)) {
                foreach ($jsonArr as $ev) {
                    if (isset($ev['Man_Tip']) && $ev['Man_Tip'] === 'GE' && !empty($ev['Fecha'])) {
                        $garitaIng = $ev['Fecha'];
                        break;
                    }
                }
            }
            if (empty($garitaIng)) {
                if (preg_match('/GE["\']\s*,\s*["\']Fecha["\']\s*:\s*["\']([^"\']+)["\']/i', $rawUsu, $m)) {
                    $garitaIng = $m[1];
                } elseif (preg_match('/["\']Man_Tip["\']\s*:\s*["\']GE["\'].*?["\']Fecha["\']\s*:\s*["\']([^"\']+)["\']/i', $rawUsu, $m)) {
                    $garitaIng = $m[1];
                }
            }
        }
        $row['garita_ingreso'] = $garitaIng;
        $fechaFormato = '';
        if (!empty($row['Tud_Fec'])) {
            $fechaObj = new DateTime($row['Tud_Fec']);
            $fechaFormato = $fechaObj->format('d/m/Y');
        }
        $horaInicio = isset($row['Tud_Hin']) ? $row['Tud_Hin'] : '';
        $horaFin = isset($row['Tud_Hfi']) ? $row['Tud_Hfi'] : '';
        $row['fecha_dia'] = $fechaFormato;
        $row['horario_turno'] = ($horaInicio && $horaFin) ? date('H:i', strtotime($horaInicio)) . ' - ' . date('H:i', strtotime($horaFin)) : '';
        unset($row['Tud_Hin']);
        unset($row['Tud_Hfi']);
        unset($row['Tud_Fec']);
        $manifiestos[] = $row;
    }

    $resultado['manifiestos'] = $manifiestos;
    $resultado['turnoInfo'] = $turnoInfo;
    $obBD_con1->echoJson($resultado);
}

// Obtener manifiestos por rango: slot específico (fecha + horario)
if (isset($getManifiestosPorRangoSlotAjax)) {
    $resultado = array('success' => true);
    $Tud_Fec = isset($_GET['Tud_Fec']) ? trim($_GET['Tud_Fec']) : '';
    $Tud_Hin = isset($_GET['Tud_Hin']) ? trim($_GET['Tud_Hin']) : '';
    $Tud_Hfi = isset($_GET['Tud_Hfi']) ? trim($_GET['Tud_Hfi']) : '';
    $Pla_Cod = isset($_GET['Pla_Cod']) ? intval($_GET['Pla_Cod']) : 0;

    if (!$Tud_Fec || !$Tud_Hin || !$Tud_Hfi) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Parámetros incompletos'));
        exit;
    }
    $Tud_Fec_esc = mysqli_real_escape_string($obBD_conexion->conexion, $Tud_Fec);
    $Tud_Hin_esc = mysqli_real_escape_string($obBD_conexion->conexion, $Tud_Hin);
    $Tud_Hfi_esc = mysqli_real_escape_string($obBD_conexion->conexion, $Tud_Hfi);
    $condPla = ($Pla_Cod > 0) ? " AND manifiesto.Pla_Cod = $Pla_Cod" : "";

    $fechaFormato = '';
    if (!empty($Tud_Fec)) {
        $fechaObj = new DateTime($Tud_Fec);
        $fechaFormato = $fechaObj->format('d/m/Y');
    }
    $horarioTexto = ($Tud_Hin && $Tud_Hfi) ? date('H:i', strtotime($Tud_Hin)) . ' - ' . date('H:i', strtotime($Tud_Hfi)) : '';
    $turnoInfo = array('fecha' => $fechaFormato, 'horario' => $horarioTexto);

    $sql = "SELECT manifiesto.Man_Cod, manifiesto.Man_Num, manifiesto.Man_Usu, DATE(manifiesto.Man_Fec) as Man_Fec, DATE_FORMAT(manifiesto.Man_Fec, '%H:%i') as Man_Hor, DATE(manifiesto.Man_Fes) as Man_Fes,
                CONCAT('M', manifiesto_plantas.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, 0)) as ManNum,
                CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as Cliente, manifiesto_plantas.Pla_Nom, manifiesto.Man_Est,
                if(LOCATE('GE', manifiesto.Man_Tes) > 0,'GE','') as Man_Tip_1, if(LOCATE('A', manifiesto.Man_Tes) > 0,'A','') as Man_Tip_2,
                if(LOCATE('GS', manifiesto.Man_Tes) > 0,'GS','') as Man_Tip_3, if(LOCATE('F', manifiesto.Man_Tes) > 0,'F','') as Man_Tip_4, if(LOCATE('R', manifiesto.Man_Tes) > 0,'R','') as Man_Tip_5,
                IF(manifiesto.Man_Est='A','ACTIVO','INACTIVO') as estado_texto
            FROM manifiesto
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
            INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
            INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
            INNER JOIN persona as persona_cli ON persona_cli.Prs_Cod = cliente.Prs_Cod
            INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
            WHERE manifiesto_turnos_det.Tud_Fec = '$Tud_Fec_esc' AND manifiesto_turnos_det.Tud_Hin = '$Tud_Hin_esc' AND manifiesto_turnos_det.Tud_Hfi = '$Tud_Hfi_esc'
            AND manifiesto_turnos_cab.Emp_Cod = $Ses_Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I' AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
            AND manifiesto.Man_Est = 'A' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
            AND cliente.Emp_Cod = $Ses_Emp_Cod $condPla
            ORDER BY manifiesto.Man_Fes DESC, manifiesto.Man_Fec DESC";

    $result = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    $manifiestos = array();
    while ($row = $obBD_con1->fetch_assoc($result)) {
        $obBD_con1->utf8_change_param($row);
        $garitaIng = '';
        if (!empty($row['Man_Usu']) && strpos($row['Man_Usu'], 'GE') !== false) {
            $jsonArr = @json_decode($row['Man_Usu'], true);
            if (is_array($jsonArr)) {
                foreach ($jsonArr as $ev) {
                    if (isset($ev['Man_Tip']) && $ev['Man_Tip'] === 'GE' && !empty($ev['Fecha'])) {
                        $garitaIng = $ev['Fecha'];
                        break;
                    }
                }
            }
        }
        $row['garita_ingreso'] = $garitaIng;
        $manifiestos[] = $row;
    }
    $resultado['manifiestos'] = $manifiestos;
    $resultado['turnoInfo'] = $turnoInfo;
    $obBD_con1->echoJson($resultado);
}

// Obtener manifiestos por rango: día completo
if (isset($getManifiestosPorRangoDiaAjax)) {
    $resultado = array('success' => true);
    $Tud_Fec = isset($_GET['Tud_Fec']) ? trim($_GET['Tud_Fec']) : '';
    $Pla_Cod = isset($_GET['Pla_Cod']) ? intval($_GET['Pla_Cod']) : 0;

    if (!$Tud_Fec) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Fecha requerida'));
        exit;
    }
    $Tud_Fec_esc = mysqli_real_escape_string($obBD_conexion->conexion, $Tud_Fec);
    $condPla = ($Pla_Cod > 0) ? " AND manifiesto.Pla_Cod = $Pla_Cod" : "";

    $fechaFormato = '';
    if (!empty($Tud_Fec)) {
        $fechaObj = new DateTime($Tud_Fec);
        $fechaFormato = $fechaObj->format('d/m/Y');
    }
    $turnoInfo = array('fecha' => $fechaFormato, 'horario' => 'Acumulado del día');

    $sql = "SELECT manifiesto.Man_Cod, manifiesto.Man_Num, manifiesto.Man_Usu, DATE(manifiesto.Man_Fec) as Man_Fec, DATE_FORMAT(manifiesto.Man_Fec, '%H:%i') as Man_Hor, DATE(manifiesto.Man_Fes) as Man_Fes,
                manifiesto_turnos_det.Tud_Hin, manifiesto_turnos_det.Tud_Hfi,
                CONCAT('M', manifiesto_plantas.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, 0)) as ManNum,
                CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as Cliente, manifiesto_plantas.Pla_Nom, manifiesto.Man_Est,
                if(LOCATE('GE', manifiesto.Man_Tes) > 0,'GE','') as Man_Tip_1, if(LOCATE('A', manifiesto.Man_Tes) > 0,'A','') as Man_Tip_2,
                if(LOCATE('GS', manifiesto.Man_Tes) > 0,'GS','') as Man_Tip_3, if(LOCATE('F', manifiesto.Man_Tes) > 0,'F','') as Man_Tip_4, if(LOCATE('R', manifiesto.Man_Tes) > 0,'R','') as Man_Tip_5,
                IF(manifiesto.Man_Est='A','ACTIVO','INACTIVO') as estado_texto
            FROM manifiesto
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
            INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
            INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
            INNER JOIN persona as persona_cli ON persona_cli.Prs_Cod = cliente.Prs_Cod
            INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
            WHERE manifiesto_turnos_det.Tud_Fec = '$Tud_Fec_esc'
            AND manifiesto_turnos_cab.Emp_Cod = $Ses_Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I' AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
            AND manifiesto.Man_Est = 'A' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
            AND cliente.Emp_Cod = $Ses_Emp_Cod $condPla
            ORDER BY manifiesto_turnos_det.Tud_Hin ASC, manifiesto.Man_Fes DESC, manifiesto.Man_Fec DESC";

    $result = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    $manifiestos = array();
    while ($row = $obBD_con1->fetch_assoc($result)) {
        $obBD_con1->utf8_change_param($row);
        $garitaIng = '';
        if (!empty($row['Man_Usu']) && strpos($row['Man_Usu'], 'GE') !== false) {
            $jsonArr = @json_decode($row['Man_Usu'], true);
            if (is_array($jsonArr)) {
                foreach ($jsonArr as $ev) {
                    if (isset($ev['Man_Tip']) && $ev['Man_Tip'] === 'GE' && !empty($ev['Fecha'])) {
                        $garitaIng = $ev['Fecha'];
                        break;
                    }
                }
            }
        }
        $row['garita_ingreso'] = $garitaIng;
        $horaInicio = isset($row['Tud_Hin']) ? $row['Tud_Hin'] : '';
        $horaFin = isset($row['Tud_Hfi']) ? $row['Tud_Hfi'] : '';
        $row['horario_turno'] = ($horaInicio && $horaFin) ? date('H:i', strtotime($horaInicio)) . ' - ' . date('H:i', strtotime($horaFin)) : '';
        unset($row['Tud_Hin']);
        unset($row['Tud_Hfi']);
        $manifiestos[] = $row;
    }
    $resultado['manifiestos'] = $manifiestos;
    $resultado['turnoInfo'] = $turnoInfo;
    $obBD_con1->echoJson($resultado);
}

// Obtener manifiestos por rango: rango completo de fechas
if (isset($getManifiestosPorRangoCompletoAjax)) {
    $resultado = array('success' => true);
    $fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
    $Pla_Cod = isset($_GET['Pla_Cod']) ? intval($_GET['Pla_Cod']) : 0;

    if (!$fecha_inicio || !$fecha_fin) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Rango de fechas requerido'));
        exit;
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_inicio, $m)) {
        $fecha_inicio = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_fin, $m)) {
        $fecha_fin = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    $fecha_inicio_esc = mysqli_real_escape_string($obBD_conexion->conexion, $fecha_inicio);
    $fecha_fin_esc = mysqli_real_escape_string($obBD_conexion->conexion, $fecha_fin);
    $condPla = ($Pla_Cod > 0) ? " AND manifiesto.Pla_Cod = $Pla_Cod" : "";

    $turnoInfo = array('fecha' => $fecha_inicio . ' - ' . $fecha_fin, 'horario' => 'Acumulado del rango');

    $sql = "SELECT manifiesto.Man_Cod, manifiesto.Man_Num, DATE(manifiesto.Man_Fec) as Man_Fec, DATE_FORMAT(manifiesto.Man_Fec, '%H:%i') as Man_Hor, DATE(manifiesto.Man_Fes) as Man_Fes,
                manifiesto_turnos_det.Tud_Fec, manifiesto_turnos_det.Tud_Hin, manifiesto_turnos_det.Tud_Hfi,
                CONCAT('M', manifiesto_plantas.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, 0)) as ManNum,
                CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as Cliente, manifiesto_plantas.Pla_Nom, manifiesto.Man_Est,
                if(LOCATE('GE', manifiesto.Man_Tes) > 0,'GE','') as Man_Tip_1, if(LOCATE('A', manifiesto.Man_Tes) > 0,'A','') as Man_Tip_2,
                if(LOCATE('GS', manifiesto.Man_Tes) > 0,'GS','') as Man_Tip_3, if(LOCATE('F', manifiesto.Man_Tes) > 0,'F','') as Man_Tip_4, if(LOCATE('R', manifiesto.Man_Tes) > 0,'R','') as Man_Tip_5,
                IF(manifiesto.Man_Est='A','ACTIVO','INACTIVO') as estado_texto
            FROM manifiesto
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
            INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
            INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
            INNER JOIN persona as persona_cli ON persona_cli.Prs_Cod = cliente.Prs_Cod
            INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
            WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc'
            AND manifiesto_turnos_cab.Emp_Cod = $Ses_Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I' AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
            AND manifiesto.Man_Est = 'A' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
            AND cliente.Emp_Cod = $Ses_Emp_Cod $condPla
            ORDER BY manifiesto_turnos_det.Tud_Fec ASC, manifiesto_turnos_det.Tud_Hin ASC, manifiesto.Man_Fes DESC, manifiesto.Man_Fec DESC";

    $result = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    $manifiestos = array();
    while ($row = $obBD_con1->fetch_assoc($result)) {
        $obBD_con1->utf8_change_param($row);
        $fechaFormato = '';
        if (!empty($row['Tud_Fec'])) {
            $fechaObj = new DateTime($row['Tud_Fec']);
            $fechaFormato = $fechaObj->format('d/m/Y');
        }
        $horaInicio = isset($row['Tud_Hin']) ? $row['Tud_Hin'] : '';
        $horaFin = isset($row['Tud_Hfi']) ? $row['Tud_Hfi'] : '';
        $row['fecha_dia'] = $fechaFormato;
        $row['horario_turno'] = ($horaInicio && $horaFin) ? date('H:i', strtotime($horaInicio)) . ' - ' . date('H:i', strtotime($horaFin)) : '';
        unset($row['Tud_Hin']);
        unset($row['Tud_Hfi']);
        unset($row['Tud_Fec']);
        $manifiestos[] = $row;
    }
    $resultado['manifiestos'] = $manifiestos;
    $resultado['turnoInfo'] = $turnoInfo;
    $obBD_con1->echoJson($resultado);
}

// Ranking de plantas que más manifiestos generan (rango de fechas)
if (isset($getPlantasRankingAjax)) {
    $resultado = array('success' => true);
    $fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
    if (!$fecha_inicio || !$fecha_fin) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Rango de fechas requerido'));
        exit;
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_inicio, $m)) {
        $fecha_inicio = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_fin, $m)) {
        $fecha_fin = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    $fecha_inicio_esc = mysqli_real_escape_string($obBD_conexion->conexion, $fecha_inicio);
    $fecha_fin_esc = mysqli_real_escape_string($obBD_conexion->conexion, $fecha_fin);
    // $sql = "SELECT manifiesto.Pla_Cod, manifiesto_plantas.Pla_Nom, COUNT(*) as total
    $sql = "SELECT manifiesto.Pla_Cod, manifiesto_plantas.Pla_Nom, manifiesto.Man_Usu
            FROM manifiesto
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
            INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
            INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
            INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
            WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc'
            AND manifiesto_turnos_cab.Emp_Cod = $Ses_Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I'
            AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
            AND manifiesto.Man_Est = 'A' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
            AND cliente.Emp_Cod = $Ses_Emp_Cod
            GROUP BY manifiesto.Pla_Cod, manifiesto_plantas.Pla_Nom
            ORDER BY total DESC";
    $result = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    // $plantas = array();
    $agrup = array();
    while ($row = $obBD_con1->fetch_assoc($result)) {
        $obBD_con1->utf8_change_param($row);
        //     $plantas[] = array('Pla_Cod' => intval($row['Pla_Cod']), 'Pla_Nom' => $row['Pla_Nom'], 'total' => intval($row['total']));
        // }
        $key = 'p_' . $row['Pla_Cod'];
        if (!isset($agrup[$key])) {
            $agrup[$key] = array('Pla_Cod' => intval($row['Pla_Cod']), 'Pla_Nom' => $row['Pla_Nom'], 'total' => 0, 'total_minutos' => 0, 'conteo_tiempos' => 0);
        }
        $agrup[$key]['total']++;
        if (!empty($row['Man_Usu'])) {
            $usu_data = json_decode($row['Man_Usu'], true);
            if (is_array($usu_data)) {
                $f_entrada = null;
                $f_salida = null;
                $eventos = isset($usu_data[0]) && is_array($usu_data[0]) ? $usu_data : array($usu_data);
                foreach ($eventos as $ev) {
                    $ev_tip = isset($ev['Man_Tip']) ? $ev['Man_Tip'] : (isset($ev['man_tip']) ? $ev['man_tip'] : '');
                    $ev_fec = isset($ev['Fecha']) ? $ev['Fecha'] : (isset($ev['fecha']) ? $ev['fecha'] : '');

                    if ($ev_tip === 'GE' && (!$f_entrada || $ev_fec < $f_entrada)) {
                        $f_entrada = $ev_fec;
                    }
                    if ($ev_tip === 'GS' && (!$f_salida || $ev_fec > $f_salida)) {
                        $f_salida = $ev_fec;
                    }
                }
                if ($f_entrada && $f_salida) {
                    $t1 = strtotime($f_entrada);
                    $t2 = strtotime($f_salida);
                    if ($t1 && $t2 && $t2 > $t1) {
                        $agrup[$key]['total_minutos'] += ($t2 - $t1) / 60;
                        $agrup[$key]['conteo_tiempos']++;
                    }
                }
            }
        }
    }
    $plantas = array();
    foreach ($agrup as $g) {
        $g['tiempo_promedio'] = ($g['conteo_tiempos'] > 0) ? ($g['total_minutos'] / $g['conteo_tiempos']) : 0;
        $plantas[] = $g;
    }
    usort($plantas, function ($a, $b) {
        return $b['total'] - $a['total'];
    });
    $resultado['plantas'] = $plantas;
    $obBD_con1->echoJson($resultado);
}

// Dashboard manifiestos por chofer o por placa (rango de fechas)
if (isset($getDashboardManifiestosAjax)) {
    $resultado = array('success' => true);
    $fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
    $tipo_vista = isset($_GET['tipo_vista']) ? trim($_GET['tipo_vista']) : 'chofer';
    if ($tipo_vista !== 'chofer' && $tipo_vista !== 'placa' && $tipo_vista !== 'planta') {
        $tipo_vista = 'chofer';
    }
    if (!$fecha_inicio || !$fecha_fin) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Rango de fechas requerido'));
        exit;
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_inicio, $m)) {
        $fecha_inicio = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_fin, $m)) {
        $fecha_fin = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    $fecha_inicio_esc = mysqli_real_escape_string($obBD_conexion->conexion, $fecha_inicio);
    $fecha_fin_esc = mysqli_real_escape_string($obBD_conexion->conexion, $fecha_fin);
    $sql = "SELECT manifiesto.Man_Cod, manifiesto.Man_Num, manifiesto.Cho_Cod, manifiesto.Veh_Cod, manifiesto.Pla_Cod, manifiesto.Man_Usu, manifiesto.Man_Tes,
                DATE(manifiesto.Man_Fec) as Man_Fec, COALESCE(DATE_FORMAT(manifiesto.Man_Fea, '%H:%i'), DATE_FORMAT(manifiesto.Man_Fes, '%H:%i'), DATE_FORMAT(manifiesto.Man_Sys, '%H:%i'), DATE_FORMAT(manifiesto.Man_Fec, '%H:%i')) as Man_Hor,
                CONCAT('M', manifiesto_plantas.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, 0)) as ManNum,
                manifiesto_plantas.Pla_Nom,
                COALESCE(CONCAT(persona_chofer.Prs_Nom, ' ', persona_chofer.Prs_Ape), '') as chofer_nombre,
                COALESCE(persona_chofer.Prs_Ced, '') as chofer_cedula,
                COALESCE(vehiculo.Veh_Pla, '') as Veh_Pla,
                IF(LOCATE('GE', manifiesto.Man_Tes) > 0,'GE','') as Man_Tip_1,
                IF(LOCATE('A', manifiesto.Man_Tes) > 0,'A','') as Man_Tip_2,
                IF(LOCATE('GS', manifiesto.Man_Tes) > 0,'GS','') as Man_Tip_3,
                IF(LOCATE('F', manifiesto.Man_Tes) > 0,'F','') as Man_Tip_4,
                IF(LOCATE('R', manifiesto.Man_Tes) > 0,'R','') as Man_Tip_5
            FROM manifiesto
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
            INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
            INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
            INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
            LEFT JOIN chofer ON chofer.Cho_Cod = manifiesto.Cho_Cod
            LEFT JOIN persona AS persona_chofer ON persona_chofer.Prs_Cod = chofer.Prs_Cod
            LEFT JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto.Veh_Cod
            WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc'
            AND manifiesto_turnos_cab.Emp_Cod = $Ses_Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I'
            AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
            AND manifiesto.Man_Est = 'A' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
            AND cliente.Emp_Cod = $Ses_Emp_Cod
            ORDER BY " . ($tipo_vista === 'chofer' ? "chofer_nombre ASC, manifiesto.Man_Fec ASC" : ($tipo_vista === 'placa' ? "Veh_Pla ASC, manifiesto.Man_Fec ASC" : "manifiesto_plantas.Pla_Nom ASC, manifiesto.Man_Fec ASC"));
    $result = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    $filas = array();
    while ($row = $obBD_con1->fetch_assoc($result)) {
        $obBD_con1->utf8_change_param($row);
        $fechaFormato = '';
        if (!empty($row['Man_Fec'])) {
            $fechaObj = new DateTime($row['Man_Fec']);
            $fechaFormato = $fechaObj->format('d/m/Y');
        }
        $minutos = 0;
        if (!empty($row['Man_Usu'])) {
            $usu_data = json_decode($row['Man_Usu'], true);
            if (is_array($usu_data)) {
                $f_entrada = null;
                $f_salida = null;
                $eventos = isset($usu_data[0]) && is_array($usu_data[0]) ? $usu_data : array($usu_data);
                foreach ($eventos as $ev) {
                    $ev_tip = isset($ev['Man_Tip']) ? $ev['Man_Tip'] : (isset($ev['man_tip']) ? $ev['man_tip'] : '');
                    $ev_fec = isset($ev['Fecha']) ? $ev['Fecha'] : (isset($ev['fecha']) ? $ev['fecha'] : '');

                    if ($ev_tip === 'GE' && (!$f_entrada || $ev_fec < $f_entrada)) {
                        $f_entrada = $ev_fec;
                    }
                    if ($ev_tip === 'GS' && (!$f_salida || $ev_fec > $f_salida)) {
                        $f_salida = $ev_fec;
                    }
                }
                if ($f_entrada && $f_salida) {
                    $t1 = strtotime($f_entrada);
                    $t2 = strtotime($f_salida);
                    if ($t1 && $t2 && $t2 > $t1) $minutos = ($t2 - $t1) / 60;
                }
            }
        }
        $filas[] = array(
            'Man_Cod' => intval($row['Man_Cod']),
            'Man_Num' => $row['Man_Num'],
            'Man_Fec' => $fechaFormato,
            'Man_Hor' => $row['Man_Hor'],
            'ManNum' => $row['ManNum'],
            'Pla_Cod' => isset($row['Pla_Cod']) ? intval($row['Pla_Cod']) : null,
            'Pla_Nom' => $row['Pla_Nom'],
            'chofer_nombre' => $row['chofer_nombre'],
            'chofer_cedula' => $row['chofer_cedula'],
            'Veh_Pla' => $row['Veh_Pla'],
            'Cho_Cod' => $row['Cho_Cod'] !== null && $row['Cho_Cod'] !== '' ? intval($row['Cho_Cod']) : null,
            'Veh_Cod' => $row['Veh_Cod'] !== null && $row['Veh_Cod'] !== '' ? intval($row['Veh_Cod']) : null,
            'minutos_estancia' => $minutos,
            'Man_Tip_1' => $row['Man_Tip_1'],
            'Man_Tip_2' => $row['Man_Tip_2'],
            'Man_Tip_3' => $row['Man_Tip_3'],
            'Man_Tip_4' => $row['Man_Tip_4'],
            'Man_Tip_5' => $row['Man_Tip_5']
        );
    }
    if ($tipo_vista === 'chofer') {
        $agrupado = array();
        foreach ($filas as $f) {
            $cedula = isset($f['chofer_cedula']) ? trim($f['chofer_cedula']) : '';
            $nombre = isset($f['chofer_nombre']) ? trim($f['chofer_nombre']) : '';
            if ($nombre === '') $nombre = 'Sin asignar';
            $plaCod = isset($f['Pla_Cod']) ? $f['Pla_Cod'] : 0;
            $plaNom = isset($f['Pla_Nom']) ? trim($f['Pla_Nom']) : '';
            if ($plaNom === '') $plaNom = 'Sin planta';
            // Un registro por chofer + planta
            if ($cedula !== '') {
                $key = 'cedula_' . $cedula . '_pla_' . $plaCod;
            } else {
                $key = 'nombre_' . $nombre . '_pla_' . $plaCod;
            }
            if (!isset($agrupado[$key])) {
                $agrupado[$key] = array(
                    'Cho_Cod' => $f['Cho_Cod'],
                    'chofer_nombre' => $nombre,
                    'chofer_cedula' => $cedula,
                    'Pla_Cod' => $plaCod,
                    'Pla_Nom' => $plaNom,
                    'plantas_nombres' => $plaNom,
                    'total_plantas' => 1,
                    'total_manifiestos' => 0,
                    'manifiestos' => array(),
                    'total_minutos' => 0,
                    'conteo_tiempos' => 0
                );
            }
            $agrupado[$key]['total_manifiestos']++;
            $agrupado[$key]['manifiestos'][] = $f;
            if ($f['minutos_estancia'] > 0) {
                $agrupado[$key]['total_minutos'] += $f['minutos_estancia'];
                $agrupado[$key]['conteo_tiempos']++;
            }
        }
        foreach ($agrupado as $k => $grupo) {
            $agrupado[$k]['tiempo_promedio'] = ($grupo['conteo_tiempos'] > 0) ? ($grupo['total_minutos'] / $grupo['conteo_tiempos']) : 0;
        }
        $resultado['agrupado'] = array_values($agrupado);
    } elseif ($tipo_vista === 'placa') {
        $agrupado = array();
        foreach ($filas as $f) {
            $placa = isset($f['Veh_Pla']) ? trim($f['Veh_Pla']) : '';
            if ($placa === '') $placa = 'Sin placa';
            // Agrupar por placa (texto), no solo por Veh_Cod, para que una misma placa no se repita
            $key = 'placa_' . $placa;
            if (!isset($agrupado[$key])) {
                $agrupado[$key] = array('Veh_Cod' => $f['Veh_Cod'], 'Veh_Pla' => $placa, 'total_manifiestos' => 0, 'manifiestos' => array(), 'total_minutos' => 0, 'conteo_tiempos' => 0);
            }
            $agrupado[$key]['total_manifiestos']++;
            $agrupado[$key]['manifiestos'][] = $f;
            if ($f['minutos_estancia'] > 0) {
                $agrupado[$key]['total_minutos'] += $f['minutos_estancia'];
                $agrupado[$key]['conteo_tiempos']++;
            }
        }
        // Conteo de plantas distintas por placa + nombres
        foreach ($agrupado as $k => $grupo) {
            $plantas_unicas = array();
            $nombres_plantas = array();
            foreach ($grupo['manifiestos'] as $m) {
                $pk = isset($m['Pla_Cod']) ? $m['Pla_Cod'] : '';
                $pn = isset($m['Pla_Nom']) ? trim($m['Pla_Nom']) : '';
                if ($pk !== '' && $pk !== null) {
                    $plantas_unicas[$pk] = ($pn !== '') ? $pn : ('Planta ' . $pk);
                } elseif ($pn !== '') {
                    $plantas_unicas[$pn] = $pn;
                }
            }
            $agrupado[$k]['total_plantas'] = count($plantas_unicas);
            $agrupado[$k]['plantas_nombres'] = implode(', ', array_values($plantas_unicas));
            $agrupado[$k]['tiempo_promedio'] = ($grupo['conteo_tiempos'] > 0) ? ($grupo['total_minutos'] / $grupo['conteo_tiempos']) : 0;
        }
        $resultado['agrupado'] = array_values($agrupado);
    } else {
        // tipo_vista === 'planta'
        $agrupado = array();
        foreach ($filas as $f) {
            $key = 'p_' . $f['Pla_Cod'];
            $plantaNom = $f['Pla_Nom'] !== '' ? $f['Pla_Nom'] : 'Sin planta';
            if (!isset($agrupado[$key])) {
                $agrupado[$key] = array('Pla_Cod' => $f['Pla_Cod'], 'Pla_Nom' => $plantaNom, 'total_manifiestos' => 0, 'manifiestos' => array(), 'total_minutos' => 0, 'conteo_tiempos' => 0);
            }
            $agrupado[$key]['total_manifiestos']++;
            $agrupado[$key]['manifiestos'][] = $f;
            if ($f['minutos_estancia'] > 0) {
                $agrupado[$key]['total_minutos'] += $f['minutos_estancia'];
                $agrupado[$key]['conteo_tiempos']++;
            }
        }
        foreach ($agrupado as $k => $grupo) {
            $agrupado[$k]['tiempo_promedio'] = ($grupo['conteo_tiempos'] > 0) ? ($grupo['total_minutos'] / $grupo['conteo_tiempos']) : 0;
        }
        $resultado['agrupado'] = array_values($agrupado);
    }
    if ($tipo_vista === 'chofer') {
        // Orden alfabético por chofer y, si se repite, por planta.
        usort($resultado['agrupado'], function ($a, $b) {
            $comparacion = strcasecmp($a['chofer_nombre'], $b['chofer_nombre']);
            if ($comparacion !== 0) {
                return $comparacion;
            }
            return strcasecmp($a['Pla_Nom'], $b['Pla_Nom']);
        });
    } else {
        // Para las demás vistas, conservar el orden por cantidad.
        usort($resultado['agrupado'], function ($a, $b) {
            return ($b['total_manifiestos'] - $a['total_manifiestos']);
        });
    }
    $resultado['tipo_vista'] = $tipo_vista;
    $obBD_con1->echoJson($resultado);
}

/**
 * Dashboard Ejecutivo CEO - Vista Ejecutiva Global
 * Retorna KPIs estratégicos, concentración, capital humano, activos, alertas y tendencia 30 días.
 * Parámetros: fecha_inicio, fecha_fin (Y-m-d o d/m/Y)
 */
if (isset($getDashboardEjecutivoAjax)) {
    $resultado = array('success' => true);
    $fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
    if (!$fecha_inicio || !$fecha_fin) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Rango de fechas requerido'));
        exit;
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_inicio, $m)) {
        $fecha_inicio = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_fin, $m)) {
        $fecha_fin = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    $conexion = isset($obBD_conexion->conexion) ? $obBD_conexion->conexion : null;
    if (!$conexion) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Error de conexión a base de datos'));
        exit;
    }
    $fecha_inicio_esc = mysqli_real_escape_string($conexion, $fecha_inicio);
    $fecha_fin_esc = mysqli_real_escape_string($conexion, $fecha_fin);
    $Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;

    $base_where = "manifiesto_turnos_cab.Emp_Cod = $Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I'
        AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
        AND manifiesto.Man_Est = 'A' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
        AND cliente.Emp_Cod = $Emp_Cod";
    $base_where_inactivos = "manifiesto_turnos_cab.Emp_Cod = $Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I'
        AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
        AND manifiesto.Man_Est = 'I' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
        AND cliente.Emp_Cod = $Emp_Cod";
    $base_where_plantas_ai = "manifiesto_turnos_cab.Emp_Cod = $Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I'
        AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
        AND (manifiesto.Man_Est = 'A' OR manifiesto.Man_Est = 'I') AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
        AND cliente.Emp_Cod = $Emp_Cod";
    // Total turnos (sin filtrar por Man_Est) para % = inactivos / total generados. Incluir Tud_Est S si tiene A o I.
    $base_where_todos_turnos = "manifiesto_turnos_cab.Emp_Cod = $Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I'
        AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND (m2.Man_Est = 'A' OR m2.Man_Est = 'I'))))
        AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
        AND cliente.Emp_Cod = $Emp_Cod";

    try {
        // Período anterior (misma cantidad de días)
        $d1 = new DateTime($fecha_inicio);
        $d2 = new DateTime($fecha_fin);
        $dias_periodo = $d1->diff($d2)->days + 1;
        $d_ant_fin = clone $d1;
        $d_ant_fin->modify("-1 day");
        $d_ant_ini = clone $d_ant_fin;
        $d_ant_ini->modify("-" . ($dias_periodo - 1) . " days");
        $fecha_ant_ini = $d_ant_ini->format('Y-m-d');
        $fecha_ant_fin = $d_ant_fin->format('Y-m-d');
        $fecha_ant_ini_esc = mysqli_real_escape_string($conexion, $fecha_ant_ini);
        $fecha_ant_fin_esc = mysqli_real_escape_string($conexion, $fecha_ant_fin);

        // 1) Total manifiestos período actual
        $sql_total = "SELECT COUNT(*) as total FROM manifiesto
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where";
        $res = $obBD_con1->consulta($sql_total, $conexion);
        if ($res === false) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'Error SQL (total): ' . (is_object($conexion) ? mysqli_error($conexion) : 'sin conexión')));
            exit;
        }
        $row = $obBD_con1->fetch_assoc($res);
        $total_manifiestos = isset($row['total']) ? intval($row['total']) : 0;

        // 2) Total período anterior (variación %)
        $sql_ant = "SELECT COUNT(*) as total FROM manifiesto
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_ant_ini_esc' AND '$fecha_ant_fin_esc' AND $base_where";
        $res_ant = $obBD_con1->consulta($sql_ant, $conexion);
        if ($res_ant === false) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'Error SQL (anterior): ' . (is_object($conexion) ? mysqli_error($conexion) : '')));
            exit;
        }
        $row_ant = $obBD_con1->fetch_assoc($res_ant);
        $total_anterior = isset($row_ant['total']) ? intval($row_ant['total']) : 0;
        $variacion_pct = $total_anterior > 0 ? (($total_manifiestos - $total_anterior) / $total_anterior) * 100 : ($total_manifiestos > 0 ? 100 : 0);

        // 2.0) Tonelaje
        $sql_toneladas = "SELECT 
        COALESCE(SUM(manifiesto.Man_Pes/1000), 0) as total_recibidas,
        COALESCE(SUM(CASE WHEN manifiesto.Man_Tip = 'F' THEN manifiesto.Man_Pes/1000 ELSE 0 END), 0) as total_facturadas,
        COALESCE(SUM(CASE WHEN (manifiesto.Man_Tip != 'F' OR manifiesto.Man_Tip IS NULL) AND (manifiesto.Vet_Cod IS NULL OR manifiesto.Vet_Cod = 0) THEN manifiesto.Man_Pes/1000 ELSE 0 END), 0) as total_por_facturar
        FROM manifiesto 
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where";
        $res_ton = $obBD_con1->consulta($sql_toneladas, $conexion);
        $total_toneladas_recibidas = 0;
        $total_toneladas_facturadas = 0;
        $total_toneladas_por_facturar = 0;

        if ($res_ton !== false) {
            if ($row_ton = $obBD_con1->fetch_assoc($res_ton)) {
                $total_toneladas_recibidas = isset($row_ton['total_recibidas']) ? floatval($row_ton['total_recibidas']) : 0;
                $total_toneladas_facturadas = isset($row_ton['total_facturadas']) ? floatval($row_ton['total_facturadas']) : 0;
                $total_toneladas_por_facturar = isset($row_ton['total_por_facturar']) ? floatval($row_ton['total_por_facturar']) : 0;
            }
        }
        $promedio_tonelaje_diario = $dias_periodo > 0 ? ($total_toneladas_recibidas / $dias_periodo) : 0;


        // 2.1) Tiempo promedio global (Relavera)
        $sql_tiempo_prom = "SELECT manifiesto.Man_Usu FROM manifiesto
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where";
        $res_t = $obBD_con1->consulta($sql_tiempo_prom, $conexion);
        $total_minutos_global = 0;
        $conteo_tiempos_global = 0;
        if ($res_t !== false) {
            while ($row_t = $obBD_con1->fetch_assoc($res_t)) {
                if (!empty($row_t['Man_Usu'])) {
                    $usu_data = json_decode($row_t['Man_Usu'], true);
                    if (is_array($usu_data)) {
                        $ge_time = '';
                        $gs_time = '';
                        $eventos = isset($usu_data[0]) ? $usu_data : array($usu_data);
                        foreach ($eventos as $ev) {
                            if (isset($ev['Man_Tip']) && isset($ev['Fecha'])) {
                                if ($ev['Man_Tip'] == 'GE') $ge_time = $ev['Fecha'];
                                if ($ev['Man_Tip'] == 'GS') $gs_time = $ev['Fecha'];
                            }
                        }
                        if (!empty($ge_time) && !empty($gs_time)) {
                            $t1 = strtotime($ge_time);
                            $t2 = strtotime($gs_time);
                            if ($t2 > $t1) {
                                $total_minutos_global += ($t2 - $t1) / 60;
                                $conteo_tiempos_global++;
                            }
                        }
                    }
                }
            }
        }
        $tiempo_relavera_prom = ($conteo_tiempos_global > 0) ? ($total_minutos_global / $conteo_tiempos_global) : 0;

        // 3) Top plantas (para concentración Top 3 y Top 2)
        $sql_plantas = "SELECT manifiesto.Pla_Cod, manifiesto_plantas.Pla_Nom, COUNT(*) as total
        FROM manifiesto
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where
        GROUP BY manifiesto.Pla_Cod, manifiesto_plantas.Pla_Nom ORDER BY total DESC";
        $res_pla = $obBD_con1->consulta($sql_plantas, $conexion);
        if ($res_pla === false) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'Error SQL (plantas): ' . (is_object($conexion) ? mysqli_error($conexion) : '')));
            exit;
        }
        $plantas_list = array();
        while ($r = $obBD_con1->fetch_assoc($res_pla)) {
            $obBD_con1->utf8_change_param($r);
            $plantas_list[] = array('Pla_Cod' => intval($r['Pla_Cod']), 'Pla_Nom' => $r['Pla_Nom'], 'total' => intval($r['total']));
        }
        $sum_top3 = 0;
        $sum_top2 = 0;
        foreach (array_slice($plantas_list, 0, 3) as $p) $sum_top3 += $p['total'];
        foreach (array_slice($plantas_list, 0, 2) as $p) $sum_top2 += $p['total'];
        $indice_concentracion_top3 = $total_manifiestos > 0 ? ($sum_top3 / $total_manifiestos) * 100 : 0;
        $indice_dependencia_top2 = $total_manifiestos > 0 ? ($sum_top2 / $total_manifiestos) * 100 : 0;
        $top10_plantas = array_slice($plantas_list, 0, 10);

        // 4) Choferes: totales por chofer para promedio, desviación, % sobre/bajo promedio y 80%
        $sql_chofer = "SELECT COALESCE(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape), 'Sin asignar') as nombre, COUNT(*) as total
        FROM manifiesto
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        LEFT JOIN chofer ON chofer.Cho_Cod = manifiesto.Cho_Cod
        LEFT JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where
        GROUP BY COALESCE(chofer.Prs_Cod, 0), COALESCE(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape), 'Sin asignar') ORDER BY total DESC";
        $res_ch = $obBD_con1->consulta($sql_chofer, $conexion);
        if ($res_ch === false) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'Error SQL (choferes): ' . (is_object($conexion) ? mysqli_error($conexion) : '')));
            exit;
        }
        $choferes_list = array();
        while ($r = $obBD_con1->fetch_assoc($res_ch)) {
            $obBD_con1->utf8_change_param($r);
            $choferes_list[] = array('nombre' => $r['nombre'], 'total' => intval($r['total']));
        }
        $n_choferes = count($choferes_list);
        $suma_ch = 0;
        foreach ($choferes_list as $c) {
            $suma_ch += isset($c['total']) ? (int)$c['total'] : 0;
        }
        $promedio_chofer = $n_choferes > 0 ? $suma_ch / $n_choferes : 0;
        $desv_chofer = 0;
        if ($n_choferes > 1) {
            $sum_cuad = 0;
            foreach ($choferes_list as $c) $sum_cuad += pow($c['total'] - $promedio_chofer, 2);
            $desv_chofer = sqrt($sum_cuad / $n_choferes);
        }
        $sobre_promedio = 0;
        $bajo_promedio = 0;
        foreach ($choferes_list as $c) {
            if ($c['total'] > $promedio_chofer) $sobre_promedio++;
            else $bajo_promedio++;
        }
        $pct_choferes_sobre = $n_choferes > 0 ? ($sobre_promedio / $n_choferes) * 100 : 0;
        $pct_choferes_bajo = $n_choferes > 0 ? ($bajo_promedio / $n_choferes) * 100 : 0;
        $acu = 0;
        $n_choferes_80 = 0;
        foreach ($choferes_list as $c) {
            $acu += ($suma_ch > 0 ? ($c['total'] / $suma_ch) * 100 : 0);
            $n_choferes_80++;
            if ($acu >= 80) break;
        }

        // 5) Placas: totales por placa, baja actividad, concentración
        $sql_placa = "SELECT COALESCE(vehiculo.Veh_Pla, 'Sin placa') as placa, COUNT(*) as total
        FROM manifiesto
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        LEFT JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto.Veh_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where
        GROUP BY COALESCE(manifiesto.Veh_Cod, 0), COALESCE(vehiculo.Veh_Pla, 'Sin placa') ORDER BY total DESC";
        $res_pl = $obBD_con1->consulta($sql_placa, $conexion);
        if ($res_pl === false) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'Error SQL (placas): ' . (is_object($conexion) ? mysqli_error($conexion) : '')));
            exit;
        }
        $placas_list = array();
        while ($r = $obBD_con1->fetch_assoc($res_pl)) {
            $obBD_con1->utf8_change_param($r);
            $placas_list[] = array('placa' => $r['placa'], 'total' => intval($r['total']));
        }
        $n_placas = count($placas_list);
        $suma_pla = 0;
        foreach ($placas_list as $p) {
            $suma_pla += isset($p['total']) ? (int)$p['total'] : 0;
        }
        $promedio_placa = $n_placas > 0 ? $suma_pla / $n_placas : 0;
        $umbral_bajo = $promedio_placa * 0.7;
        $placas_baja_actividad = 0;
        foreach ($placas_list as $p) {
            if ($p['total'] < $umbral_bajo) $placas_baja_actividad++;
        }
        $pct_placas_baja = $n_placas > 0 ? ($placas_baja_actividad / $n_placas) * 100 : 0;
        $n20_pla = max(1, (int)floor($n_placas * 0.2));
        $sum_top20_pla = 0;
        foreach (array_slice($placas_list, 0, $n20_pla) as $p) $sum_top20_pla += $p['total'];
        $indice_concentracion_flota = $suma_pla > 0 ? ($sum_top20_pla / $suma_pla) * 100 : 0;

        // Total placas registradas (vehículos con al menos un manifiesto en el período = activas; sin otra tabla usamos activas como flota)
        $total_placas_registradas = $n_placas;
        $utilizacion_flota = $total_placas_registradas > 0 ? 100 : 0;

        // 6) Tendencia por día en el rango de fechas seleccionado (no últimos 30 días)
        $sql_dias = "SELECT DATE(manifiesto_turnos_det.Tud_Fec) as dia, COUNT(*) as total
        FROM manifiesto
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc'
        AND $base_where
        GROUP BY DATE(manifiesto_turnos_det.Tud_Fec) ORDER BY dia ASC";
        $res_d = $obBD_con1->consulta($sql_dias, $conexion);
        if ($res_d === false) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'Error SQL (días): ' . (is_object($conexion) ? mysqli_error($conexion) : '')));
            exit;
        }
        $por_dia = array();
        while ($r = $obBD_con1->fetch_assoc($res_d)) {
            $por_dia[$r['dia']] = intval($r['total']);
        }
        $dias_30 = array();
        $dt_ini = new DateTime($fecha_inicio);
        $dt_fin = new DateTime($fecha_fin);
        $dias_periodo = $dt_ini->diff($dt_fin)->days + 1;
        $dt_cur = clone $dt_ini;
        for ($i = 0; $i < $dias_periodo; $i++) {
            $d = $dt_cur->format('Y-m-d');
            $dias_30[] = array('fecha' => $d, 'total' => isset($por_dia[$d]) ? $por_dia[$d] : 0);
            $dt_cur->modify('+1 day');
        }
        $promedio_diario = $dias_periodo > 0 ? ($total_manifiestos / $dias_periodo) : 0;
        $desv_diaria = 0;
        if (count($dias_30) > 1) {
            $s = 0;
            foreach ($dias_30 as $dd) $s += pow($dd['total'] - $promedio_diario, 2);
            $desv_diaria = sqrt($s / count($dias_30));
        }
        $umbral_pico = $promedio_diario + 2 * $desv_diaria;

        // 6b) Manifiestos inactivos (turnos generados que no realizaron el viaje)
        $sql_inactivos = "SELECT COUNT(*) as total FROM manifiesto
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where_inactivos";
        $res_inac = $obBD_con1->consulta($sql_inactivos, $conexion);
        if ($res_inac === false) {
            $total_inactivos = 0;
            $total_inactivos_ant = 0;
            $top_plantas_inactivos = array();
        } else {
            $row_inac = $obBD_con1->fetch_assoc($res_inac);
            $total_inactivos = isset($row_inac['total']) ? intval($row_inac['total']) : 0;
            $sql_inactivos_ant = "SELECT COUNT(*) as total FROM manifiesto
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
            INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
            INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
            WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_ant_ini_esc' AND '$fecha_ant_fin_esc' AND $base_where_inactivos";
            $res_inac_ant = $obBD_con1->consulta($sql_inactivos_ant, $conexion);
            $total_inactivos_ant = 0;
            if ($res_inac_ant !== false) {
                $row_ant_inac = $obBD_con1->fetch_assoc($res_inac_ant);
                $total_inactivos_ant = isset($row_ant_inac['total']) ? intval($row_ant_inac['total']) : 0;
            }
            $sql_plantas_inac = "SELECT manifiesto.Pla_Cod, manifiesto_plantas.Pla_Nom,
            SUM(CASE WHEN manifiesto.Man_Est = 'A' THEN 1 ELSE 0 END) AS total_activos,
            SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) AS total_inactivos
            FROM manifiesto
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
            INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
            INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
            INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
            WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where_plantas_ai
            GROUP BY manifiesto.Pla_Cod, manifiesto_plantas.Pla_Nom
            HAVING SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) > 0
            ORDER BY (SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) / GREATEST(SUM(CASE WHEN manifiesto.Man_Est = 'A' THEN 1 ELSE 0 END) + SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END), 1)) DESC, SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) DESC
            LIMIT 10";
            $res_pla_inac = $obBD_con1->consulta($sql_plantas_inac, $conexion);
            $top_plantas_inactivos = array();
            $top_choferes_inactivos = array();
            $top_placas_inactivos = array();
            $pareto_choferes_inactivos = array();
            $insight_responsabilidad = '';
            if ($res_pla_inac !== false) {
                while ($r = $obBD_con1->fetch_assoc($res_pla_inac)) {
                    $obBD_con1->utf8_change_param($r);
                    $act = isset($r['total_activos']) ? intval($r['total_activos']) : 0;
                    $inac = isset($r['total_inactivos']) ? intval($r['total_inactivos']) : 0;
                    $total_pla = $act + $inac;
                    $tasa_pla = $total_pla > 0 ? ($inac / $total_pla) * 100 : 0;
                    $top_plantas_inactivos[] = array(
                        'Pla_Cod' => intval($r['Pla_Cod']),
                        'Pla_Nom' => $r['Pla_Nom'],
                        'total_activos' => $act,
                        'total_inactivos' => $inac,
                        'tasa_pct' => round($tasa_pla, 1)
                    );
                }
            }
            // Responsabilidad operativa: Top 5 choferes y Top 5 placas con más inactivos; Pareto por chofer; insight
            $top_choferes_inactivos = array();
            $top_placas_inactivos = array();
            $pareto_choferes_inactivos = array();
            $insight_responsabilidad = '';
            if ($total_inactivos > 0) {
                $sql_chofer_inac = "SELECT COALESCE(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape), 'Sin asignar') as chofer_nombre,
                COUNT(*) as total_turnos,
                SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) as inactivos
                FROM manifiesto
                INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
                INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
                INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
                LEFT JOIN chofer ON chofer.Cho_Cod = manifiesto.Cho_Cod
                LEFT JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where_todos_turnos
                GROUP BY COALESCE(chofer.Prs_Cod, 0), COALESCE(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape), 'Sin asignar')
                HAVING SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) > 0
                ORDER BY SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) DESC
                LIMIT 5";
                $res_ch_inac = $obBD_con1->consulta($sql_chofer_inac, $conexion);
                if ($res_ch_inac !== false) {
                    while ($r = $obBD_con1->fetch_assoc($res_ch_inac)) {
                        $obBD_con1->utf8_change_param($r);
                        $inac_ch = isset($r['inactivos']) ? intval($r['inactivos']) : 0;
                        $tot_ch = isset($r['total_turnos']) ? intval($r['total_turnos']) : 0;
                        $tasa_ch = $tot_ch > 0 ? ($inac_ch / $tot_ch) * 100 : 0;
                        $riesgo = $tasa_ch < 5 ? 'verde' : ($tasa_ch <= 10 ? 'amarillo' : 'rojo');
                        $top_choferes_inactivos[] = array(
                            'chofer_nombre' => $r['chofer_nombre'],
                            'inactivos' => $inac_ch,
                            'total_turnos' => $tot_ch,
                            'tasa_pct' => round($tasa_ch, 1),
                            'riesgo' => $riesgo
                        );
                    }
                }
                $sql_placa_inac = "SELECT COALESCE(vehiculo.Veh_Pla, 'Sin placa') as placa,
                COUNT(*) as total_turnos,
                SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) as inactivos
                FROM manifiesto
                INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
                INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
                INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
                LEFT JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto.Veh_Cod
                WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where_todos_turnos
                GROUP BY COALESCE(vehiculo.Veh_Pla, 'Sin placa')
                HAVING SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) > 0
                ORDER BY SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) DESC
                LIMIT 5";
                $res_pla_inac = $obBD_con1->consulta($sql_placa_inac, $conexion);
                if ($res_pla_inac !== false) {
                    while ($r = $obBD_con1->fetch_assoc($res_pla_inac)) {
                        $obBD_con1->utf8_change_param($r);
                        $inac_pl = isset($r['inactivos']) ? intval($r['inactivos']) : 0;
                        $tot_pl = isset($r['total_turnos']) ? intval($r['total_turnos']) : 0;
                        $tasa_pl = $tot_pl > 0 ? ($inac_pl / $tot_pl) * 100 : 0;
                        $top_placas_inactivos[] = array(
                            'placa' => $r['placa'],
                            'inactivos' => $inac_pl,
                            'total_turnos' => $tot_pl,
                            'tasa_pct' => round($tasa_pl, 1)
                        );
                    }
                }
                // Pareto: todos los choferes con inactivos (ordenados por inactivos desc) para gráfico hasta 70% acumulado; incluir placas y plantas para tooltip
                $sql_pareto_ch = "SELECT COALESCE(chofer.Prs_Cod, 0) as Prs_Cod,
                COALESCE(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape), 'Sin asignar') as chofer_nombre,
                SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) as inactivos
                FROM manifiesto
                INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
                INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
                INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
                LEFT JOIN chofer ON chofer.Cho_Cod = manifiesto.Cho_Cod
                LEFT JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where_inactivos
                GROUP BY COALESCE(chofer.Prs_Cod, 0), COALESCE(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape), 'Sin asignar')
                ORDER BY SUM(CASE WHEN manifiesto.Man_Est = 'I' THEN 1 ELSE 0 END) DESC
                LIMIT 50";
                $res_pareto = $obBD_con1->consulta($sql_pareto_ch, $conexion);
                $detalle_plantas_por_chofer = array();
                $detalle_placas_por_chofer = array();
                $sql_pareto_placas = "SELECT COALESCE(chofer.Prs_Cod, 0) as Prs_Cod,
                COALESCE(vehiculo.Veh_Pla, 'Sin placa') as Veh_Pla,
                COUNT(*) as inactivos
                FROM manifiesto
                INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
                INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
                INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
                LEFT JOIN chofer ON chofer.Cho_Cod = manifiesto.Cho_Cod
                LEFT JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto.Veh_Cod
                WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where_inactivos
                GROUP BY COALESCE(chofer.Prs_Cod, 0), COALESCE(vehiculo.Veh_Pla, 'Sin placa')
                ORDER BY COALESCE(chofer.Prs_Cod, 0), inactivos DESC";
                $res_pla_det = $obBD_con1->consulta($sql_pareto_placas, $conexion);
                if ($res_pla_det !== false) {
                    while ($row = $obBD_con1->fetch_assoc($res_pla_det)) {
                        $obBD_con1->utf8_change_param($row);
                        $prs = isset($row['Prs_Cod']) ? intval($row['Prs_Cod']) : 0;
                        if (!isset($detalle_placas_por_chofer[$prs])) {
                            $detalle_placas_por_chofer[$prs] = array();
                        }
                        $detalle_placas_por_chofer[$prs][] = array(
                            'Veh_Pla' => isset($row['Veh_Pla']) ? trim($row['Veh_Pla']) : 'Sin placa',
                            'inactivos' => isset($row['inactivos']) ? intval($row['inactivos']) : 0
                        );
                    }
                }
                $sql_pareto_plantas = "SELECT COALESCE(chofer.Prs_Cod, 0) as Prs_Cod,
                COALESCE(manifiesto_plantas.Pla_Nom, 'Sin planta') as Pla_Nom,
                COUNT(*) as inactivos
                FROM manifiesto
                INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
                INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
                INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
                LEFT JOIN chofer ON chofer.Cho_Cod = manifiesto.Cho_Cod
                LEFT JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
                WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where_inactivos
                GROUP BY COALESCE(chofer.Prs_Cod, 0), manifiesto.Pla_Cod, COALESCE(manifiesto_plantas.Pla_Nom, 'Sin planta')
                ORDER BY COALESCE(chofer.Prs_Cod, 0), inactivos DESC";
                $res_pla_det = $obBD_con1->consulta($sql_pareto_plantas, $conexion);
                if ($res_pla_det !== false) {
                    while ($row = $obBD_con1->fetch_assoc($res_pla_det)) {
                        $obBD_con1->utf8_change_param($row);
                        $prs = isset($row['Prs_Cod']) ? intval($row['Prs_Cod']) : 0;
                        if (!isset($detalle_plantas_por_chofer[$prs])) {
                            $detalle_plantas_por_chofer[$prs] = array();
                        }
                        $detalle_plantas_por_chofer[$prs][] = array(
                            'Pla_Nom' => isset($row['Pla_Nom']) ? trim($row['Pla_Nom']) : 'Sin planta',
                            'inactivos' => isset($row['inactivos']) ? intval($row['inactivos']) : 0
                        );
                    }
                }
                if ($res_pareto !== false) {
                    while ($r = $obBD_con1->fetch_assoc($res_pareto)) {
                        $obBD_con1->utf8_change_param($r);
                        $prs = isset($r['Prs_Cod']) ? intval($r['Prs_Cod']) : 0;
                        $pareto_choferes_inactivos[] = array(
                            'chofer_nombre' => $r['chofer_nombre'],
                            'inactivos' => isset($r['inactivos']) ? intval($r['inactivos']) : 0,
                            'placas_detalle' => isset($detalle_placas_por_chofer[$prs]) ? $detalle_placas_por_chofer[$prs] : array(),
                            'plantas_detalle' => isset($detalle_plantas_por_chofer[$prs]) ? $detalle_plantas_por_chofer[$prs] : array()
                        );
                    }
                }
                // Insight: % de choferes que concentra ~70% de los inactivos
                $n_ch_total = count($pareto_choferes_inactivos);
                if ($n_ch_total > 0) {
                    $acum = 0;
                    $n_ch_70 = 0;
                    foreach ($pareto_choferes_inactivos as $pc) {
                        $acum += isset($pc['inactivos']) ? (int)$pc['inactivos'] : 0;
                        $n_ch_70++;
                        if ($total_inactivos > 0 && ($acum / $total_inactivos) >= 0.70) {
                            break;
                        }
                    }
                    $pct_choferes = $n_ch_total > 0 ? round(($n_ch_70 / $n_ch_total) * 100) : 0;
                    $pct_inactivos = $total_inactivos > 0 ? round(($acum / $total_inactivos) * 100) : 0;
                    $insight_responsabilidad = "El " . $pct_choferes . "% de los choferes concentra el " . $pct_inactivos . "% de los inactivos. Posible patrón operativo.";
                }
            }
        }
        $total_generados = $total_manifiestos + $total_inactivos;
        $tasa_inactivos_pct = $total_manifiestos > 0 ? ($total_inactivos / $total_manifiestos) * 100 : ($total_inactivos > 0 ? 100 : 0);
        $variacion_inactivos_pct = $total_inactivos_ant > 0 ? (($total_inactivos - $total_inactivos_ant) / $total_inactivos_ant) * 100 : ($total_inactivos > 0 ? 100 : 0);

        // 7) Alertas automáticas
        $alertas = array();
        if ($indice_dependencia_top2 > 65) {
            $alertas[] = array('nivel' => 'alto', 'texto' => 'Alta concentración: Top 2 plantas superan el 65%.');
        } elseif ($indice_dependencia_top2 > 50) {
            $alertas[] = array('nivel' => 'atencion', 'texto' => 'Concentración moderada: Top 2 plantas superan el 50%.');
        }
        if ($indice_concentracion_flota > 70) {
            $alertas[] = array('nivel' => 'atencion', 'texto' => 'El 20% de la flota concentra más del 70% de la operación.');
        }
        if ($desv_chofer > $promedio_chofer * 0.8 && $n_choferes > 5) {
            $alertas[] = array('nivel' => 'atencion', 'texto' => 'Alta dispersión entre choferes (desigualdad operativa).');
        }
        if ($variacion_pct < -15) {
            $alertas[] = array('nivel' => 'alto', 'texto' => 'Caída significativa vs período anterior (' . number_format($variacion_pct, 1) . '%).');
        } elseif ($variacion_pct < 0) {
            $alertas[] = array('nivel' => 'atencion', 'texto' => 'Variación negativa vs período anterior (' . number_format($variacion_pct, 1) . '%).');
        }
        if ($tasa_inactivos_pct > 25) {
            $alertas[] = array('nivel' => 'alto', 'texto' => 'Tasa de manifiestos inactivos alta: ' . number_format($tasa_inactivos_pct, 1) . '% de turnos no realizados.');
        } elseif ($tasa_inactivos_pct > 15) {
            $alertas[] = array('nivel' => 'atencion', 'texto' => 'Tasa de manifiestos inactivos: ' . number_format($tasa_inactivos_pct, 1) . '% de turnos no realizados.');
        }
        if ($total_inactivos > 0 && count($top_plantas_inactivos) > 0) {
            $primera_planta = $top_plantas_inactivos[0];
            $tasa_primera = isset($primera_planta['tasa_pct']) ? floatval($primera_planta['tasa_pct']) : 0;
            if ($tasa_primera > 20) {
                $alertas[] = array('nivel' => 'atencion', 'texto' => 'Planta "' . $primera_planta['Pla_Nom'] . '" tiene tasa de inactivos del ' . number_format($tasa_primera, 1) . '% sobre realizados.');
            }
        }
        if (count($alertas) === 0) {
            $alertas[] = array('nivel' => 'estable', 'texto' => 'Sin alertas críticas en el período.');
        }

        $resultado['kpis'] = array(
            'total_manifiestos' => $total_manifiestos,
            'variacion_pct' => round($variacion_pct, 1),
            'indice_concentracion_top3' => round($indice_concentracion_top3, 1),
            'indice_dependencia_top2' => round($indice_dependencia_top2, 1),
            'desviacion_estandar' => round($desv_chofer, 1),
            'utilizacion_flota' => round($utilizacion_flota, 1),
            'tiempo_relavera_prom' => round($tiempo_relavera_prom, 1),
            'total_toneladas_recibidas' => round($total_toneladas_recibidas, 2),
            'total_toneladas_facturadas' => round($total_toneladas_facturadas, 2),
            'total_toneladas_por_facturar' => round($total_toneladas_por_facturar, 2),
            'promedio_tonelaje_diario' => round($promedio_tonelaje_diario, 2)
        );
        $resultado['top10_plantas'] = $top10_plantas;
        $sum_top10 = 0;
        foreach ($top10_plantas as $p) {
            $sum_top10 += isset($p['total']) ? (int)$p['total'] : 0;
        }
        $resultado['concentracion_pct'] = $total_manifiestos > 0 ? round(($sum_top3 / $total_manifiestos) * 100, 1) : 0;
        $resultado['concentracion_top10_pct'] = $total_manifiestos > 0 ? round(($sum_top10 / $total_manifiestos) * 100, 1) : 0;
        $resultado['choferes'] = array(
            'pct_sobre_promedio' => round($pct_choferes_sobre, 1),
            'pct_bajo_promedio' => round($pct_choferes_bajo, 1),
            'n_choferes_80' => $n_choferes_80,
            'total_choferes' => $n_choferes,
            'distribucion' => $choferes_list,
            'promedio' => round($promedio_chofer, 1)
        );
        $resultado['placas'] = array(
            'pct_baja_actividad' => round($pct_placas_baja, 1),
            'indice_concentracion' => round($indice_concentracion_flota, 1),
            'n_placas' => $n_placas,
            'distribucion' => array_slice($placas_list, 0, 20)
        );
        $resultado['alertas'] = $alertas;
        $resultado['inactivos'] = array(
            'total_activos' => $total_manifiestos,
            'total_inactivos' => $total_inactivos,
            'total_generados' => $total_generados,
            'tasa_pct' => round($tasa_inactivos_pct, 1),
            'variacion_vs_anterior_pct' => round($variacion_inactivos_pct, 1),
            'top_plantas' => $top_plantas_inactivos
        );
        $resultado['responsabilidad'] = array(
            'top_choferes' => isset($top_choferes_inactivos) ? $top_choferes_inactivos : array(),
            'top_placas' => isset($top_placas_inactivos) ? $top_placas_inactivos : array(),
            'pareto_choferes' => isset($pareto_choferes_inactivos) ? $pareto_choferes_inactivos : array(),
            'insight' => isset($insight_responsabilidad) ? $insight_responsabilidad : ''
        );
        $resultado['tendencia_30'] = $dias_30;
        $resultado['tendencia_promedio'] = round($promedio_diario, 1);
        $resultado['fecha_ini'] = $fecha_inicio;
        $resultado['fecha_fin'] = $fecha_fin;
        $obBD_con1->echoJson($resultado);
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Error: ' . $e->getMessage()));
    }
}

/**
 * Listado completo de manifiestos inactivos para Excel (Vista Ejecutiva).
 * Parámetros: fecha_inicio, fecha_fin. Retorna una fila por cada manifiesto inactivo.
 */
if (isset($_GET['getListadoInactivosExcelAjax']) || isset($getListadoInactivosExcelAjax)) {
    $resultado = array('success' => true, 'listado' => array());
    $fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
    if (!$fecha_inicio || !$fecha_fin) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Rango de fechas requerido'));
        exit;
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_inicio, $m)) {
        $fecha_inicio = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_fin, $m)) {
        $fecha_fin = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    $conexion = isset($obBD_conexion->conexion) ? $obBD_conexion->conexion : null;
    if (!$conexion) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Sin conexión'));
        exit;
    }
    $Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;
    if (!$Emp_Cod) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Empresa no definida'));
        exit;
    }
    $fecha_inicio_esc = mysqli_real_escape_string($conexion, $fecha_inicio);
    $fecha_fin_esc = mysqli_real_escape_string($conexion, $fecha_fin);
    $base_where_inactivos = "manifiesto_turnos_cab.Emp_Cod = $Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I'
        AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
        AND manifiesto.Man_Est = 'I' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
        AND cliente.Emp_Cod = $Emp_Cod";
    $base_where_activos = "manifiesto_turnos_cab.Emp_Cod = $Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I'
        AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
        AND manifiesto.Man_Est = 'A' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
        AND cliente.Emp_Cod = $Emp_Cod";
    $sql_activos = "SELECT COUNT(*) as total FROM manifiesto
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where_activos";
    $res_act = $obBD_con1->consulta($sql_activos, $conexion);
    $total_activos = 0;
    if ($res_act !== false && ($row_act = $obBD_con1->fetch_assoc($res_act))) {
        $total_activos = isset($row_act['total']) ? intval($row_act['total']) : 0;
    }
    $sql = "SELECT manifiesto_plantas.Pla_Nom,
        CONCAT('M', manifiesto.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, 0)) as ManNum,
        COALESCE(vehiculo.Veh_Pla, 'Sin placa') as placa,
        COALESCE(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape), 'Sin asignar') as chofer_nombre
        FROM manifiesto
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
        LEFT JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto.Veh_Cod
        LEFT JOIN chofer ON chofer.Cho_Cod = manifiesto.Cho_Cod
        LEFT JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where_inactivos
        ORDER BY manifiesto_plantas.Pla_Nom, manifiesto.Man_Fec, manifiesto.Man_Num";
    $res = $obBD_con1->consulta($sql, $conexion);
    $listado = array();
    if ($res !== false) {
        while ($row = $obBD_con1->fetch_assoc($res)) {
            $obBD_con1->utf8_change_param($row);
            $listado[] = array(
                'Pla_Nom' => $row['Pla_Nom'],
                'ManNum' => $row['ManNum'],
                'placa' => $row['placa'],
                'chofer_nombre' => $row['chofer_nombre']
            );
        }
    }
    $total_inactivos = count($listado);
    $tasa_pct = $total_activos > 0 ? round(($total_inactivos / $total_activos) * 100, 1) : 0;
    $resultado['listado'] = $listado;
    $resultado['total_inactivos'] = $total_inactivos;
    $resultado['total_activos'] = $total_activos;
    $resultado['tasa_pct'] = $tasa_pct;
    $resultado['fecha_ini'] = date('d/m/Y', strtotime($fecha_inicio));
    $resultado['fecha_fin'] = date('d/m/Y', strtotime($fecha_fin));
    $obBD_con1->echoJson($resultado);
    exit;
}

/**
 * Drill-down Responsabilidad Operativa: detalle por planta (vehículos y choferes con inactivos).
 * Parámetros: Pla_Cod, fecha_inicio, fecha_fin
 */
if (isset($_GET['getInactivosDetallePlantaAjax']) || isset($getInactivosDetallePlantaAjax)) {
    $resultado = array('success' => true);
    $Pla_Cod = isset($_GET['Pla_Cod']) ? intval($_GET['Pla_Cod']) : 0;
    $fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
    if (!$Pla_Cod || !$fecha_inicio || !$fecha_fin) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Planta y rango de fechas requeridos'));
        exit;
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_inicio, $m)) {
        $fecha_inicio = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_fin, $m)) {
        $fecha_fin = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    $conexion = isset($obBD_conexion->conexion) ? $obBD_conexion->conexion : null;
    if (!$conexion) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Sin conexión'));
        exit;
    }
    $Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;
    if (!$Emp_Cod) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Empresa no definida'));
        exit;
    }
    $fecha_inicio_esc = mysqli_real_escape_string($conexion, $fecha_inicio);
    $fecha_fin_esc = mysqli_real_escape_string($conexion, $fecha_fin);
    $Pla_Cod_esc = intval($Pla_Cod);
    $base_where_inactivos_planta = "manifiesto_turnos_cab.Emp_Cod = $Emp_Cod AND manifiesto_turnos_cab.Tur_Est != 'I'
        AND (manifiesto_turnos_det.Tud_Est = 'A' OR (manifiesto_turnos_det.Tud_Est = 'S' AND EXISTS (SELECT 1 FROM manifiesto m2 WHERE m2.Tud_Cod = manifiesto_turnos_det.Tud_Cod AND m2.Man_Est = 'A')))
        AND manifiesto.Man_Est = 'I' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
        AND cliente.Emp_Cod = $Emp_Cod AND manifiesto.Pla_Cod = $Pla_Cod_esc";
    $sql_detalle = "SELECT COALESCE(vehiculo.Veh_Pla, 'Sin placa') as placa,
        COALESCE(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape), 'Sin asignar') as chofer_nombre,
        COUNT(*) as inactivos,
        GROUP_CONCAT(CONCAT('M', manifiesto.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, 0)) ORDER BY manifiesto.Man_Fec ASC, manifiesto.Man_Num ASC SEPARATOR ', ') as numeros_manifiesto
        FROM manifiesto
        INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
        INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
        INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
        LEFT JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto.Veh_Cod
        LEFT JOIN chofer ON chofer.Cho_Cod = manifiesto.Cho_Cod
        LEFT JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
        WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc' AND $base_where_inactivos_planta
        GROUP BY COALESCE(manifiesto.Veh_Cod, 0), COALESCE(vehiculo.Veh_Pla, 'Sin placa'), COALESCE(manifiesto.Cho_Cod, 0), COALESCE(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape), 'Sin asignar')
        ORDER BY COUNT(*) DESC";
    $res_det = $obBD_con1->consulta($sql_detalle, $conexion);
    $detalle = array();
    $total_planta = 0;
    if ($res_det !== false) {
        while ($row = $obBD_con1->fetch_assoc($res_det)) {
            $obBD_con1->utf8_change_param($row);
            $inac = isset($row['inactivos']) ? intval($row['inactivos']) : 0;
            $total_planta += $inac;
            $detalle[] = array(
                'placa' => $row['placa'],
                'chofer_nombre' => $row['chofer_nombre'],
                'inactivos' => $inac,
                'pct' => 0,
                'numeros_manifiesto' => isset($row['numeros_manifiesto']) ? $row['numeros_manifiesto'] : ''
            );
        }
    }
    if ($total_planta > 0) {
        foreach ($detalle as $i => $d) {
            $detalle[$i]['pct'] = round(($d['inactivos'] / $total_planta) * 100, 1);
        }
    }
    $resultado['planta_cod'] = $Pla_Cod;
    $resultado['total_inactivos'] = $total_planta;
    $resultado['detalle'] = $detalle;
    $obBD_con1->echoJson($resultado);
}

/**
 * Función compartida para procesar el Control de Tiempos y Arribos (SLA y Retrasos)
 */
function obtenerDatosControlTiemposArribos($obBD_con1, $conexion, $Emp_Cod, $fecha_inicio, $fecha_fin, $Pla_Cod = 0, $estado_filtro = 'todos') {
    $fecha_inicio_esc = mysqli_real_escape_string($conexion, $fecha_inicio);
    $fecha_fin_esc = mysqli_real_escape_string($conexion, $fecha_fin);
    $condPla = ($Pla_Cod > 0) ? "AND manifiesto.Pla_Cod = " . intval($Pla_Cod) : "";

    $sql = "SELECT 
                manifiesto.Man_Cod, manifiesto.Man_Num, manifiesto.Man_Fec, manifiesto.Man_Fes, manifiesto.Man_Fea,
                manifiesto.Man_Usu, manifiesto.Pla_Cod, manifiesto_plantas.Pla_Nom, manifiesto_plantas.Pla_Dis,
                COALESCE(CONCAT(persona_chofer.Prs_Nom, ' ', persona_chofer.Prs_Ape), 'Sin asignar') as chofer_nombre,
                COALESCE(vehiculo.Veh_Pla, 'Sin placa') as Veh_Pla,
                manifiesto_turnos_det.Tud_Fec, manifiesto_turnos_det.Tud_Hin, manifiesto_turnos_det.Tud_Hfi,
                CONCAT('M', manifiesto_plantas.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, 0)) as ManNum
            FROM manifiesto
            INNER JOIN manifiesto_turnos_det ON manifiesto_turnos_det.Tud_Cod = manifiesto.Tud_Cod
            INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_cab.Tur_Cod = manifiesto_turnos_det.Tur_Cod
            INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
            INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
            LEFT JOIN chofer ON chofer.Cho_Cod = manifiesto.Cho_Cod
            LEFT JOIN persona AS persona_chofer ON persona_chofer.Prs_Cod = chofer.Prs_Cod
            LEFT JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto.Veh_Cod
            WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$fecha_inicio_esc' AND '$fecha_fin_esc'
            AND manifiesto_turnos_cab.Emp_Cod = $Emp_Cod 
            AND manifiesto_turnos_cab.Tur_Est != 'I'
            AND manifiesto_turnos_det.Tud_Est IN ('A', 'S')
            AND manifiesto.Man_Est = 'A' 
            AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes) = 0)
            AND cliente.Emp_Cod = $Emp_Cod $condPla
            ORDER BY manifiesto_turnos_det.Tud_Fec DESC, manifiesto.Man_Fes DESC, manifiesto.Man_Num DESC";

    $res = $obBD_con1->consulta($sql, $conexion);
    $listado = array();
    $totales = array(
        'total' => 0, 'cumplidos' => 0, 'anticipados' => 0, 'excedidos' => 0, 'modificados' => 0,
        'en_transito' => 0, 'suma_duracion_real' => 0, 'conteo_duracion_real' => 0,
        'suma_retraso_min' => 0, 'conteo_retrasos' => 0, 'suma_holgura_min' => 0, 'conteo_holguras' => 0
    );
    $plantasMap = array();
    $tendenciaMap = array();

    if ($res !== false) {
        while ($row = $obBD_con1->fetch_assoc($res)) {
            $obBD_con1->utf8_change_param($row);
            $fes = !empty($row['Man_Fes']) ? $row['Man_Fes'] : (!empty($row['Man_Fec']) ? $row['Man_Fec'] : '');
            $fea = !empty($row['Man_Fea']) ? $row['Man_Fea'] : '';
            $pla_dis = !empty($row['Pla_Dis']) ? $row['Pla_Dis'] : '02:00';

            $minutos_pla_dis = 120;
            if (preg_match('/^(\d{1,2}):(\d{1,2})(:(\d{1,2}))?$/', $pla_dis, $pm)) {
                $minutos_pla_dis = (intval($pm[1]) * 60) + intval($pm[2]);
            }

            $ts_salida = strtotime($fes);
            $ts_arribo_teorico = ($ts_salida && $ts_salida > 0) ? ($ts_salida + ($minutos_pla_dis * 60)) : null;
            $fecha_arribo_teorico = $ts_arribo_teorico ? date('Y-m-d H:i:s', $ts_arribo_teorico) : '';

            $ts_fea = !empty($fea) ? strtotime($fea) : null;
            $es_modificado = ($ts_fea && $ts_arribo_teorico && abs($ts_fea - $ts_arribo_teorico) > 120);

            // Extraer GE de Man_Usu
            $fecha_entrada_real = null;
            if (!empty($row['Man_Usu']) && strpos($row['Man_Usu'], 'GE') !== false) {
                $rawUsu = stripslashes(str_replace('&quot;', '"', $row['Man_Usu']));
                $usu_data = @json_decode($rawUsu, true);
                if (is_array($usu_data)) {
                    $eventos = isset($usu_data[0]) && is_array($usu_data[0]) ? $usu_data : array($usu_data);
                    foreach ($eventos as $ev) {
                        $ev_tip = isset($ev['Man_Tip']) ? $ev['Man_Tip'] : (isset($ev['man_tip']) ? $ev['man_tip'] : '');
                        $ev_fec = isset($ev['Fecha']) ? $ev['Fecha'] : (isset($ev['fecha']) ? $ev['fecha'] : '');
                        if ($ev_tip === 'GE' && $ev_fec && (!$fecha_entrada_real || $ev_fec < $fecha_entrada_real)) {
                            $fecha_entrada_real = $ev_fec;
                        }
                    }
                }
                if (!$fecha_entrada_real && preg_match('/(?:GE.*?Fecha|Fecha.*?GE)["\']\s*:\s*["\']([^"\']+)/i', $rawUsu, $m)) {
                    $fecha_entrada_real = $m[1];
                }
            }

            $duracion_real_min = 0;
            $retraso_min = 0;
            $estado = 'en_transito';

            if ($fecha_entrada_real) {
                $ts_ge = strtotime($fecha_entrada_real);
                if ($ts_salida && $ts_ge > $ts_salida) {
                    $duracion_real_min = round(($ts_ge - $ts_salida) / 60);
                }
                $ts_target_arribo = $ts_fea ? $ts_fea : $ts_arribo_teorico;
                if ($ts_target_arribo) {
                    $retraso_min = round(($ts_ge - $ts_target_arribo) / 60);
                    $estado = ($retraso_min > 5) ? 'excedido' : (($retraso_min < -5) ? 'anticipado' : 'cumplido');
                } else {
                    $estado = 'cumplido';
                }
            }

            if ($estado_filtro !== 'todos') {
                if ($estado_filtro === 'modificado' && !$es_modificado) continue;
                if ($estado_filtro !== 'modificado' && $estado !== $estado_filtro) continue;
            }

            $totales['total']++;
            if ($es_modificado) $totales['modificados']++;
            if ($estado === 'cumplido') $totales['cumplidos']++;
            elseif ($estado === 'anticipado') {
                $totales['anticipados']++;
                if ($retraso_min < 0) { $totales['suma_holgura_min'] += abs($retraso_min); $totales['conteo_holguras']++; }
            } elseif ($estado === 'excedido') {
                $totales['excedidos']++;
                if ($retraso_min > 0) { $totales['suma_retraso_min'] += $retraso_min; $totales['conteo_retrasos']++; }
            } elseif ($estado === 'en_transito') {
                $totales['en_transito']++;
            }

            if ($duracion_real_min > 0) {
                $totales['suma_duracion_real'] += $duracion_real_min;
                $totales['conteo_duracion_real']++;
            }

            $plaCod = intval($row['Pla_Cod']);
            $plaNom = $row['Pla_Nom'];
            if (!isset($plantasMap[$plaCod])) {
                $plantasMap[$plaCod] = array(
                    'Pla_Cod' => $plaCod, 'Pla_Nom' => $plaNom, 'tiempo_configurado_min' => $minutos_pla_dis,
                    'total' => 0, 'cumplidos' => 0, 'anticipados' => 0, 'excedidos' => 0, 'modificados' => 0,
                    'en_transito' => 0, 'suma_duracion' => 0, 'conteo_duracion' => 0, 'suma_holgura' => 0, 'conteo_holgura' => 0
                );
            }
            $plantasMap[$plaCod]['total']++;
            if ($es_modificado) $plantasMap[$plaCod]['modificados']++;
            if ($estado === 'cumplido') $plantasMap[$plaCod]['cumplidos']++;
            if ($estado === 'anticipado') {
                $plantasMap[$plaCod]['anticipados']++;
                if ($retraso_min < 0) { $plantasMap[$plaCod]['suma_holgura'] += abs($retraso_min); $plantasMap[$plaCod]['conteo_holgura']++; }
            }
            if ($estado === 'excedido') $plantasMap[$plaCod]['excedidos']++;
            if ($estado === 'en_transito') $plantasMap[$plaCod]['en_transito']++;
            if ($duracion_real_min > 0) {
                $plantasMap[$plaCod]['suma_duracion'] += $duracion_real_min;
                $plantasMap[$plaCod]['conteo_duracion']++;
            }

            $fecha_dia = !empty($fes) ? date('Y-m-d', strtotime($fes)) : (!empty($row['Tud_Fec']) ? $row['Tud_Fec'] : '');
            if (!empty($fecha_dia)) {
                if (!isset($tendenciaMap[$fecha_dia])) {
                    $tendenciaMap[$fecha_dia] = array(
                        'fecha' => date('d/m/Y', strtotime($fecha_dia)), 'fecha_sql' => $fecha_dia,
                        'total' => 0, 'cumplidos' => 0, 'anticipados' => 0, 'excedidos' => 0, 'modificados' => 0, 'en_transito' => 0
                    );
                }
                $tendenciaMap[$fecha_dia]['total']++;
                if ($es_modificado) $tendenciaMap[$fecha_dia]['modificados']++;
                if ($estado === 'cumplido') $tendenciaMap[$fecha_dia]['cumplidos']++;
                if ($estado === 'anticipado') $tendenciaMap[$fecha_dia]['anticipados']++;
                if ($estado === 'excedido') $tendenciaMap[$fecha_dia]['excedidos']++;
                if ($estado === 'en_transito') $tendenciaMap[$fecha_dia]['en_transito']++;
            }

            $listado[] = array(
                'Man_Cod' => intval($row['Man_Cod']),
                'ManNum' => $row['ManNum'],
                'Pla_Nom' => $row['Pla_Nom'],
                'chofer_nombre' => $row['chofer_nombre'],
                'Veh_Pla' => $row['Veh_Pla'],
                'fecha_salida' => $fes ? date('d/m/Y H:i', strtotime($fes)) : '-',
                'fecha_arribo_planificada' => $fea ? date('d/m/Y H:i', strtotime($fea)) : ($fecha_arribo_teorico ? date('d/m/Y H:i', strtotime($fecha_arribo_teorico)) : '-'),
                'fecha_arribo_teorica' => $fecha_arribo_teorico ? date('d/m/Y H:i', strtotime($fecha_arribo_teorico)) : '-',
                'fecha_arribo_real' => $fecha_entrada_real ? date('d/m/Y H:i', strtotime($fecha_entrada_real)) : 'En tránsito',
                'tiempo_configurado_min' => $minutos_pla_dis,
                'tiempo_real_min' => $duracion_real_min,
                'retraso_min' => $retraso_min,
                'es_modificado' => $es_modificado,
                'estado' => $estado
            );
        }
    }

    $finalizados = $totales['cumplidos'] + $totales['anticipados'] + $totales['excedidos'];
    $a_tiempo_total = $totales['cumplidos'] + $totales['anticipados'];

    $plantas_resumen = array();
    foreach ($plantasMap as $p) {
        $p_fin = $p['cumplidos'] + $p['anticipados'] + $p['excedidos'];
        $p_a_tiempo = $p['cumplidos'] + $p['anticipados'];
        $p['cumplieron_sla'] = $p_a_tiempo;
        $p['pct_cumplimiento'] = $p_fin > 0 ? round(($p_a_tiempo / $p_fin) * 100, 1) : 0;
        $p['tiempo_promedio_min'] = $p['conteo_duracion'] > 0 ? round($p['suma_duracion'] / $p['conteo_duracion']) : 0;
        $p['holgura_promedio_min'] = $p['conteo_holgura'] > 0 ? round($p['suma_holgura'] / $p['conteo_holgura']) : 0;
        $plantas_resumen[] = $p;
    }

    ksort($tendenciaMap);
    $tendencia_diaria = array();
    foreach ($tendenciaMap as $t) {
        $t_fin = $t['cumplidos'] + $t['anticipados'] + $t['excedidos'];
        $t_a_tiempo = $t['cumplidos'] + $t['anticipados'];
        $t['pct_cumplimiento'] = $t_fin > 0 ? round(($t_a_tiempo / $t_fin) * 100, 1) : 0;
        $tendencia_diaria[] = $t;
    }

    return array(
        'success' => true,
        'kpis' => array(
            'total' => $totales['total'],
            'cumplidos' => $totales['cumplidos'],
            'anticipados' => $totales['anticipados'],
            'excedidos' => $totales['excedidos'],
            'modificados' => $totales['modificados'],
            'en_transito' => $totales['en_transito'],
            'pct_cumplimiento' => $finalizados > 0 ? round(($a_tiempo_total / $finalizados) * 100, 1) : 0,
            'pct_excedidos' => $finalizados > 0 ? round(($totales['excedidos'] / $finalizados) * 100, 1) : 0,
            'pct_anticipados' => $finalizados > 0 ? round(($totales['anticipados'] / $finalizados) * 100, 1) : 0,
            'promedio_duracion_real_min' => $totales['conteo_duracion_real'] > 0 ? round($totales['suma_duracion_real'] / $totales['conteo_duracion_real']) : 0,
            'promedio_retraso_min' => $totales['conteo_retrasos'] > 0 ? round($totales['suma_retraso_min'] / $totales['conteo_retrasos']) : 0,
            'promedio_holgura_min' => $totales['conteo_holguras'] > 0 ? round($totales['suma_holgura_min'] / $totales['conteo_holguras']) : 0
        ),
        'listado' => $listado,
        'plantas_resumen' => $plantas_resumen,
        'tendencia_diaria' => $tendencia_diaria
    );
}

/**
 * Tab Control de Tiempos y Arribos (Endpoint AJAX JSON)
 */
if (isset($_GET['getDashboardTiemposArribosAjax']) || isset($getDashboardTiemposArribosAjax)) {
    @set_time_limit(300);
    @ini_set('memory_limit', '512M');
    $fi = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
    $ff = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fi, $m)) $fi = "$m[3]-$m[2]-$m[1]";
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $ff, $m)) $ff = "$m[3]-$m[2]-$m[1]";
    if (!$fi || !$ff) { $obBD_con1->echoJson(array('success' => false, 'message' => 'Rango de fechas requerido')); exit; }
    $data = obtenerDatosControlTiemposArribos($obBD_con1, $obBD_conexion->conexion, intval(isset($Ses_Emp_Cod) ? $Ses_Emp_Cod : 0), $fi, $ff, intval(isset($_GET['Pla_Cod']) ? $_GET['Pla_Cod'] : 0), isset($_GET['estado_filtro']) ? trim($_GET['estado_filtro']) : 'todos');
    $obBD_con1->echoJson($data);
    exit;
}

/**
 * Exportación Directa a Excel para Control de Tiempos y Arribos
 */
if (isset($_GET['exportarExcelTiemposAjax']) || isset($exportarExcelTiemposAjax)) {
    @set_time_limit(300);
    @ini_set('memory_limit', '512M');
    @ini_set('display_errors', '0');
    if (class_exists('DebugBar', false) && method_exists('DebugBar', 'setDebugBar')) @DebugBar::setDebugBar(null);
    if (class_exists('\\DebugBar\\DebugHelper', false) && method_exists('\\DebugBar\\DebugHelper', 'disable')) @\DebugBar\DebugHelper::disable();
    while (ob_get_level() > 0) { @ob_end_clean(); }
    ob_start();

    $fi = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
    $ff = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fi, $m)) $fi = "$m[3]-$m[2]-$m[1]";
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $ff, $m)) $ff = "$m[3]-$m[2]-$m[1]";
    $Pla_Cod = intval(isset($_GET['Pla_Cod']) ? $_GET['Pla_Cod'] : 0);
    $estado_filtro = isset($_GET['estado_filtro']) ? trim($_GET['estado_filtro']) : 'todos';

    $data = obtenerDatosControlTiemposArribos($obBD_con1, $obBD_conexion->conexion, intval(isset($Ses_Emp_Cod) ? $Ses_Emp_Cod : 0), $fi, $ff, $Pla_Cod, $estado_filtro);

    $filename = "control_tiempos_arribos_" . ($Pla_Cod > 0 ? "planta_{$Pla_Cod}_" : "resumen_plantas_") . str_replace('-', '_', $fi) . "_" . str_replace('-', '_', $ff) . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<style>table { border-collapse: collapse; } td, th { border: 1px solid #2C5D94; padding: 4px 8px; font-size: 11px; } th { background-color: #2C5D94; color: #ffffff; font-weight: bold; }</style></head><body>';
    echo '<h3>Reporte Control de Tiempos y Arribos (Acuerdo de Nivel de Servicio de Tránsito)</h3>';
    echo '<p><strong>Rango:</strong> ' . htmlspecialchars($fi) . ' al ' . htmlspecialchars($ff) . ' | <strong>Filtro:</strong> ' . ($Pla_Cod > 0 ? 'Planta Específica' : 'Todas las plantas (Resumen por Planta)') . '</p>';

    if ($Pla_Cod == 0) {
        echo '<table><thead><tr><th>Planta</th><th>Total Viajes</th><th>Viajes a Tiempo</th><th>Con Holgura</th><th>Con Retraso</th><th>En Tránsito</th><th>% Puntualidad SLA</th><th>Tiempo Configurado</th><th>Tiempo Prom. Real</th><th>% Consumido</th><th>Diferencia Promedio</th></tr></thead><tbody>';
        foreach ($data['plantas_resumen'] as $p) {
            $hReal = floor($p['tiempo_promedio_min'] / 60);
            $mReal = $p['tiempo_promedio_min'] % 60;
            $tStr = ($p['conteo_duracion'] > 0) ? (($hReal > 0 ? $hReal . 'h ' : '') . $mReal . 'm') : '-';
            $cfgMin = $p['tiempo_configurado_min'];
            $hCfg = floor($cfgMin / 60);
            $mCfg = $cfgMin % 60;
            $cfgStr = ($cfgMin > 0) ? (($hCfg > 0 ? $hCfg . 'h ' : '') . $mCfg . 'm') : '-';
            $pctConsumido = $cfgMin > 0 ? round(($p['tiempo_promedio_min'] / $cfgMin) * 100) : 0;
            $difMin = $p['tiempo_promedio_min'] - $cfgMin;
            $hDif = floor(abs($difMin) / 60);
            $mDif = abs($difMin) % 60;
            $difStr = ($difMin > 0 ? '+' : ($difMin < 0 ? '-' : '')) . ($hDif > 0 ? $hDif . 'h ' : '') . $mDif . 'm';

            echo '<tr><td>' . htmlspecialchars($p['Pla_Nom']) . '</td><td style="text-align:center;">' . $p['total'] . '</td><td style="text-align:center; color:#198754; font-weight:bold;">' . $p['cumplieron_sla'] . '</td><td style="text-align:center; color:#0284c7; font-weight:bold;">' . $p['anticipados'] . '</td><td style="text-align:center; color:#dc3545; font-weight:bold;">' . $p['excedidos'] . '</td><td style="text-align:center; color:#6c757d;">' . $p['en_transito'] . '</td><td style="text-align:center; font-weight:bold;">' . $p['pct_cumplimiento'] . '%</td><td style="text-align:center; color:#2C5D94; font-weight:bold;">' . $cfgStr . '</td><td style="text-align:center;">' . $tStr . '</td><td style="text-align:center;">' . $pctConsumido . '%</td><td style="text-align:center; font-weight:bold;">' . $difStr . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<table><thead><tr><th>N° Manifiesto</th><th>Planta</th><th>Chofer</th><th>Placa</th><th>Hora Salida</th><th>Hora Arribo Planificada</th><th>Hora Arribo Real (GE)</th><th>Tiempo Configurado (min)</th><th>Tiempo Real (min)</th><th>Diferencia de Tiempo (min)</th><th>Arribo Modificado</th><th>Estado SLA</th></tr></thead><tbody>';
        $labelsEstado = array('cumplido' => 'A TIEMPO', 'anticipado' => 'ANTICIPADO', 'excedido' => 'EXCEDIDO', 'en_transito' => 'EN TRÁNSITO');
        foreach ($data['listado'] as $v) {
            $txtEstado = isset($labelsEstado[$v['estado']]) ? $labelsEstado[$v['estado']] : strtoupper($v['estado']);
            echo '<tr><td>' . htmlspecialchars($v['ManNum']) . '</td><td>' . htmlspecialchars($v['Pla_Nom']) . '</td><td>' . htmlspecialchars($v['chofer_nombre']) . '</td><td>' . htmlspecialchars($v['Veh_Pla']) . '</td><td>' . $v['fecha_salida'] . '</td><td>' . $v['fecha_arribo_planificada'] . '</td><td>' . $v['fecha_arribo_real'] . '</td><td style="text-align:center;">' . $v['tiempo_configurado_min'] . '</td><td style="text-align:center;">' . $v['tiempo_real_min'] . '</td><td style="text-align:center;">' . $v['retraso_min'] . '</td><td style="text-align:center;">' . ($v['es_modificado'] ? 'SI' : 'NO') . '</td><td style="text-align:center;">' . $txtEstado . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</body></html>';
    @ob_end_flush();
    exit;
}

?>
<!DOCTYPE HTML>
<HTML>

<HEAD>
    <TITLE>Reporte Manifiesto</TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
    <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3.0.1/dist/chartjs-plugin-annotation.min.js"></script>
    <style>
        .panel-main {
            margin: 20px;
        }

        /* Tabs principales: resaltar el tab activo */
        .panel-main .nav-tabs>li>a {
            color: #2C5D94;
            font-weight: 500;
            border: 1px solid transparent;
            border-radius: 4px 4px 0 0;
            padding: 10px 18px;
        }

        .panel-main .nav-tabs>li.active>a,
        .panel-main .nav-tabs>li.active>a:hover,
        .panel-main .nav-tabs>li.active>a:focus {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%);
            color: white !important;
            border-color: #2C5D94;
            font-weight: 600;
        }

        .panel-main .nav-tabs>li:not(.active)>a:hover {
            background-color: #E8F0F7;
            border-color: #dee2e6 #dee2e6 #2C5D94;
        }

        .exa-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 4px 4px 0 0;
        }

        .exa-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .dashboard-container {
            padding: 10px;
        }

        .config-card {
            background: white;
            border: 1px solid #2C5D94;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .config-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .config-header h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
        }

        .config-header p {
            margin: 3px 0 0 0;
            font-size: 11px;
        }

        .config-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .stat-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px 10px;
            min-width: 100px;
            text-align: center;
        }

        .stat-card .stat-label {
            font-size: 10px;
            color: #6c757d;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .stat-card .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #2C5D94;
        }

        .stat-card.total-turnos .stat-value {
            color: #17a2b8;
        }

        .stat-card.total-cupos .stat-value {
            color: #28a745;
        }

        .stat-card.cupos-ocupados .stat-value {
            color: #ffc107;
        }

        .stat-card.cupos-libres .stat-value {
            color: #17a2b8;
        }

        .stat-card.ocupacion .stat-value {
            color: #dc3545;
        }

        .turnos-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        .turnos-table thead th {
            background: #2C5D94;
            color: white;
            padding: 6px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .turnos-table tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
        }

        .turnos-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge-ocupacion {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }

        .badge-ocupacion.bajo {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-ocupacion.medio {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-ocupacion.alto {
            background-color: #d4edda;
            color: #155724;
        }

        /* Barra de progreso en tabla */
        .barra-progreso-tabla {
            width: 100%;
            min-width: 120px;
        }

        .barra-progreso-fondo {
            position: relative;
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .barra-progreso-relleno {
            height: 100%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 11px;
        }

        .barra-progreso-relleno.bajo {
            background: #dc3545;
        }

        .barra-progreso-relleno.medio {
            background: #ffc107;
            color: #333;
        }

        .barra-progreso-relleno.alto {
            background: #28a745;
        }

        .btn-ver-manifiestos {
            padding: 2px 8px;
            font-size: 10px;
        }

        /* Gráfico único de barras apiladas - estilo dashboard */
        .chart-dashboard-container {
            width: 100%;
            max-width: 100%;
            padding: 20px 15px;
            background: #fafbfc;
            border-radius: 6px;
            border: 1px solid #e2e8ec;
            margin: 0 auto;
            box-sizing: border-box;
            overflow-x: auto;
            overflow-y: visible;
        }

        .chart-leyenda {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #6c757d;
        }

        .chart-leyenda-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .chart-leyenda-color {
            display: inline-block;
            width: 14px;
            height: 14px;
            border-radius: 3px;
        }

        .chart-with-axes {
            display: flex;
            align-items: flex-start;
            min-width: min-content;
            width: 100%;
            margin-top: 10px;
        }

        .chart-y-axis {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            padding-right: 10px;
            border-right: 1px solid #dee2e6;
            position: relative;
            overflow: visible;
        }

        .chart-y-label {
            font-size: 11px;
            font-weight: 600;
            color: #2C5D94;
            position: absolute;
            top: -26px;
            right: 0;
            margin: 0;
        }

        .chart-y-ticks {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            opacity: 1;
            visibility: visible;
        }

        .chart-y-tick {
            font-size: 10px;
            color: #6c757d;
            line-height: 1;
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
        }

        .chart-main-area {
            flex: 1;
            position: relative;
            min-width: min-content;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            overflow: visible;
        }

        .chart-grid {
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            pointer-events: none;
        }

        .chart-grid-line {
            position: absolute;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(44, 93, 148, 0.2);
            opacity: 1;
            visibility: visible;
        }

        .chart-x-axis-line {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 1px;
            background: #adb5bd;
            opacity: 0.8;
        }

        .chart-bars-area {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            min-width: min-content;
            width: 100%;
            padding: 0 0 0 12px;
            margin: 0;
        }

        .chart-bars-row {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            flex-wrap: nowrap;
            position: relative;
            z-index: 1;
            padding: 0;
            margin: 0;
            width: 100%;
            box-sizing: border-box;
            overflow: visible;
        }

        .chart-labels-row {
            display: flex;
            justify-content: center;
            flex-wrap: nowrap;
            width: 100%;
            padding: 0 0 0 12px;
            margin-top: 6px;
            box-sizing: border-box;
            overflow: visible;
        }

        .chart-label-cell {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            flex: 1 1 0;
            min-width: 0;
        }

        .chart-labels-row .chart-x-label {
            margin: 0;
        }

        .chart-bar-total {
            font-size: 11px;
            font-weight: 600;
            color: #2C5D94;
            margin-bottom: 4px;
            text-align: center;
            flex-shrink: 0;
        }

        .chart-label-cell .chart-bar-porcentaje {
            margin-top: 1px;
            margin-bottom: 0;
            font-size: 9px;
            color: #6c757d;
        }

        /* Barras: separación = ancho de barra (como si hubiera otra entre cada una) */
        .chart-bars-n-pocas.chart-bars-row,
        .chart-bars-n-pocas.chart-labels-row {
            gap: 25px;
        }

        .chart-bars-n-pocas .chart-bar-wrapper,
        .chart-bars-n-pocas .chart-label-cell {
            flex: 0 0 auto;
            width: 32px;
            max-width: 50px;
        }

        .chart-bars-n-pocas .chart-bar-columna {
            width: 100%;
            max-width: 50px;
        }

        .chart-bars-n-medias.chart-bars-row,
        .chart-bars-n-medias.chart-labels-row {
            gap: 20px;
        }

        .chart-bars-n-medias .chart-bar-wrapper,
        .chart-bars-n-medias .chart-label-cell {
            flex: 0 0 auto;
            width: 28px;
            max-width: 40px;
        }

        .chart-bars-n-medias .chart-bar-columna {
            width: 100%;
            max-width: 40px;
        }

        .chart-bars-n-muchas.chart-bars-row,
        .chart-bars-n-muchas.chart-labels-row {
            gap: 25px;
        }

        .chart-bars-n-muchas .chart-bar-wrapper,
        .chart-bars-n-muchas .chart-label-cell {
            flex: 0 0 auto;
            width: 25px;
        }

        .chart-bars-n-muchas .chart-bar-columna {
            width: 100%;
            max-width: 25px;
        }

        .chart-bars-n-muchas .chart-segmento-valor {
            font-size: 9px;
        }

        .chart-bars-n-muchas .chart-x-label {
            font-size: 9px;
        }

        /* >20 barras: mismo aspecto que muchas en pantalla (reducido solo al imprimir) */
        .chart-bars-n-super.chart-bars-row,
        .chart-bars-n-super.chart-labels-row {
            gap: 25px;
        }

        .chart-bars-n-super .chart-bar-wrapper,
        .chart-bars-n-super .chart-label-cell {
            flex: 0 0 auto;
            width: 25px;
        }

        .chart-bars-n-super .chart-bar-columna {
            width: 100%;
            max-width: 25px;
        }

        .chart-bars-n-super .chart-segmento-valor {
            font-size: 9px;
        }

        .chart-bars-n-super .chart-x-label {
            font-size: 9px;
        }

        .chart-x-label {
            font-size: 10px;
            white-space: nowrap;
            overflow: visible;
            text-align: center;
            width: 100%;
            line-height: 1.2;
            box-sizing: border-box;
        }

        .chart-por-dias {
            width: 100%;
            max-width: 100%;
        }

        .chart-por-dias .chart-label-cell {
            justify-content: flex-start;
        }

        .chart-por-dias .chart-x-label {
            text-align: left;
        }

        .chart-bar-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 0 0 auto;
            height: 100%;
            padding: 0;
            margin: 0;
        }

        .chart-bar-spacer {
            flex: 1 1 0;
            min-height: 0;
        }

        .chart-bar-porcentaje {
            font-size: 10px;
            font-weight: 600;
            color: #2C5D94;
            margin: 0;
            text-align: center;
        }

        .chart-bar-columna {
            width: 100%;
            max-width: 100%;
            flex-shrink: 0;
            display: flex;
            flex-direction: column-reverse;
            border-radius: 4px 4px 0 0;
            overflow: hidden;
            background: #e9ecef;
        }

        .chart-segmento {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 0;
            flex-shrink: 0;
            width: 100%;
            text-align: center;
        }

        .chart-segmento-ocupados {
            background: linear-gradient(180deg, #2C5D94 0%, #3d7bb8 100%);
            color: white;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .chart-segmento-libres {
            background: linear-gradient(180deg, #fd7e14 0%, #e96b0a 100%);
            color: white;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .chart-segmento-valor {
            font-size: 10px;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
            line-height: 1;
            width: 100%;
            text-align: center;
            display: block;
        }

        .chart-segmento-pequeno {
            min-height: 18px !important;
        }

        .chart-segmento-pequeno .chart-segmento-valor {
            font-size: 9px;
        }

        .chart-bar-label {
            font-size: 11px;
            font-weight: 600;
            color: #6c757d;
            margin-top: 6px;
            text-align: left;
        }

        .loading {
            text-align: center;
            padding: 40px;
        }

        .loading i {
            font-size: 48px;
            color: #2C5D94;
        }

        .filtros-container {
            background-color: #f9f9f9;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 15px;
        }

        .filtros-container .form-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 0;
        }

        .filtros-container label {
            margin: 0;
            font-weight: 600;
            color: #2C5D94;
            white-space: nowrap;
        }

        .btn-acciones {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }

        .fecha-header {
            transition: background-color 0.2s ease;
        }

        .fecha-header:hover {
            background-color: #dde2e6 !important;
        }

        .fecha-content {
            overflow: hidden;
        }

        .chart-fecha-content {
            overflow: visible !important;
        }

        .toggle-icon {
            transition: transform 0.2s ease;
        }

        #modalManifiestos .nav-tabs {
            margin-bottom: 12px;
            border-bottom-color: #2C5D94;
        }

        #modalManifiestos .nav-tabs>li>a {
            font-size: 12px;
            padding: 8px 14px;
            color: #2C5D94;
        }

        #modalManifiestos .nav-tabs>li.active>a,
        #modalManifiestos .nav-tabs>li.active>a:hover,
        #modalManifiestos .nav-tabs>li.active>a:focus {
            background-color: #2C5D94;
            color: white;
            border-color: #2C5D94;
        }

        #modalManifiestos .nav-tabs>li>a:hover {
            border-color: #e9ecef #e9ecef #ddd;
            background-color: #e9ecef;
        }

        #modalManifiestos .tab-content {
            padding-top: 5px;
        }

        #modalManifiestos .tabla-modal-manifiestos,
        #modalManifiestos .tabla-modal-planta {
            margin-bottom: 0;
        }

        /* Gráficos Por Chofer / Placa (pantalla) - Barras horizontales */
        .chart-cp-wrap {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(44, 93, 148, 0.08);
            page-break-inside: avoid;
        }

        .chart-cp-title {
            margin: 0 0 6px 0;
            font-size: 16px;
            color: #2C5D94;
            font-weight: 600;
        }

        .chart-cp-subtitle {
            margin: 0 0 14px 0;
            font-size: 12px;
            color: #6c757d;
        }

        .chart-cp-horizontal {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .chart-cp-row {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 32px;
        }

        .chart-cp-label {
            flex: 0 0 200px;
            min-width: 140px;
            font-size: 12px;
            color: #212529;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chart-cp-bar-track {
            flex: 1;
            height: 24px;
            min-width: 80px;
            background: #e9ecef;
            border-radius: 12px;
            overflow: hidden;
        }

        .chart-cp-bar-fill {
            height: 100%;
            min-width: 4px;
            border-radius: 12px;
            background: linear-gradient(90deg, #3d7bb8 0%, #2C5D94 100%);
            transition: width 0.4s ease;
        }

        .chart-cp-val {
            flex: 0 0 52px;
            text-align: right;
            font-size: 13px;
            font-weight: 700;
            color: #2C5D94;
        }

        .chart-cp-pct {
            flex: 0 0 48px;
            text-align: right;
            font-size: 11px;
            color: #6c757d;
        }

        .chart-cp-leyenda {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #e9ecef;
            font-size: 12px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-cp-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #2C5D94;
            margin-right: 4px;
            vertical-align: middle;
        }

        .chart-cp-leyenda-colores {
            margin: 10px 0 0 0;
            font-size: 11px;
            color: #6c757d;
        }

        .leyenda-color {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 3px;
            margin-right: 4px;
            vertical-align: middle;
        }

        .leyenda-color.leyenda-verde {
            background: rgba(25, 135, 84, 0.5);
            border: 1px solid #198754;
        }

        .leyenda-color.leyenda-gris {
            background: rgba(108, 117, 125, 0.4);
            border: 1px solid #6c757d;
        }

        .leyenda-color.leyenda-naranja {
            background: rgba(253, 126, 20, 0.5);
            border: 1px solid #fd7e14;
        }

        /* Franja KPI estratégico (Planta / Chofer / Placa) */
        .dashboard-kpi-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 20px;
            padding: 18px 20px;
            background: linear-gradient(135deg, #f0f4f8 0%, #e8eef5 100%);
            border: 1px solid #cbd6e2;
            border-radius: 10px;
            animation: dashboardStripFadeIn 0.5s ease forwards;
        }

        .dashboard-kpi-item {
            flex: 1 1 140px;
            min-width: 120px;
            padding: 10px 14px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(44, 93, 148, 0.1);
            border-left: 4px solid #2C5D94;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .dashboard-kpi-label {
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-weight: 600;
        }

        .dashboard-kpi-value {
            font-size: 20px;
            font-weight: 700;
            color: #2C5D94;
            line-height: 1.2;
        }

        .dashboard-alert-riesgo {
            margin-top: 14px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: dashboardStripFadeIn 0.4s ease;
        }

        .dashboard-alert-amarilla {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }

        .dashboard-alert-roja {
            background: #f8d7da;
            border: 1px solid #dc3545;
            color: #721c24;
        }

        @keyframes dashboardStripFadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Filas ranking por desempeño (chofer: verde/gris/naranja; placa: baja actividad) */
        .chart-cp-row.chart-cp-sobre-promedio {
            background: rgba(25, 135, 84, 0.08);
            border-radius: 6px;
            border-left: 3px solid #198754;
            padding: 4px 8px;
            margin: 0 -8px;
        }

        .chart-cp-row.chart-cp-promedio {
            background: rgba(108, 117, 125, 0.06);
            border-radius: 6px;
            border-left: 3px solid #6c757d;
            padding: 4px 8px;
            margin: 0 -8px;
        }

        .chart-cp-row.chart-cp-bajo-promedio {
            background: rgba(253, 126, 20, 0.1);
            border-radius: 6px;
            border-left: 3px solid #fd7e14;
            padding: 4px 8px;
            margin: 0 -8px;
        }

        .chart-cp-row.chart-cp-baja-actividad {
            background: rgba(253, 126, 20, 0.12);
            border-radius: 6px;
            border-left: 3px solid #fd7e14;
            padding: 4px 8px;
            margin: 0 -8px;
        }

        /* Tarjetas KPI Top 5 y Gráfico Pareto (Chofer/Placa) */
        .pareto-kpi-wrap {
            margin-top: 24px;
            padding: 0;
        }

        .pareto-kpi-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            justify-content: center;
        }

        .pareto-kpi-card {
            flex: 1 1 160px;
            min-width: 140px;
            max-width: 220px;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 14px 16px;
            box-shadow: 0 2px 8px rgba(44, 93, 148, 0.12);
            transition: box-shadow 0.2s ease;
        }

        .pareto-kpi-card:hover {
            box-shadow: 0 4px 14px rgba(44, 93, 148, 0.18);
        }

        .pareto-kpi-card .kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .pareto-kpi-card .kpi-name {
            font-size: 12px;
            font-weight: 600;
            color: #212529;
            line-height: 1.3;
            margin-bottom: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pareto-kpi-card .kpi-cantidad {
            font-size: 20px;
            font-weight: 700;
            color: #2C5D94;
            line-height: 1.2;
        }

        .pareto-kpi-card .kpi-pct {
            font-size: 11px;
            color: #6c757d;
            margin-top: 2px;
        }

        .pareto-chart-wrap {
            background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(44, 93, 148, 0.08);
            margin-top: 8px;
            position: relative;
        }

        .pareto-chart-wrap h5 {
            margin: 0 0 16px 0;
            font-size: 15px;
            color: #2C5D94;
            font-weight: 600;
        }

        .pareto-mode-switch {
            display: flex;
            gap: 0;
            margin-bottom: 16px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #cbd6e2;
            background: #f0f4f8;
        }

        .pareto-mode-btn {
            flex: 1;
            padding: 10px 18px;
            border: none;
            background: transparent;
            color: #6c757d;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .pareto-mode-btn:hover {
            background: rgba(44, 93, 148, 0.08);
            color: #2C5D94;
        }

        .pareto-mode-btn.active {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%);
            color: white;
        }

        .pareto-chart-container {
            position: relative;
            height: 420px;
            width: 100%;
        }

        .pareto-chart-scroll-wrap {
            overflow-x: hidden;
            width: 100%;
            margin-bottom: 0;
        }

        .pareto-chart-scroll-wrap .pareto-chart-container {
            width: 100%;
        }

        .pareto-insight {
            margin-top: 16px;
            padding: 14px 18px;
            background: linear-gradient(135deg, #e8f0f7 0%, #f0f4f8 100%);
            border-left: 4px solid #2C5D94;
            border-radius: 6px;
            font-size: 14px;
            color: #212529;
            line-height: 1.5;
        }

        .pareto-insight strong {
            color: #2C5D94;
        }

        /* Tarjetas KPI mejoradas: medallas, barra, indicador promedio, animación */
        .pareto-kpi-card .kpi-medal {
            font-size: 24px;
            line-height: 1;
            margin-bottom: 6px;
            min-height: 30px;
            display: block;
        }

        .pareto-kpi-card .kpi-progress-wrap {
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            margin-top: 10px;
            overflow: hidden;
        }

        .pareto-kpi-card .kpi-progress-bar {
            height: 100%;
            border-radius: 3px;
            transition: width 0.6s ease;
        }

        .pareto-kpi-card .kpi-indicator {
            font-size: 11px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .pareto-kpi-card .kpi-indicator.sobre-promedio {
            color: #198754;
        }

        .pareto-kpi-card .kpi-indicator.bajo-promedio {
            color: #dc3545;
        }

        .pareto-kpi-card.animate-in {
            animation: kpiCardFadeIn 0.4s ease forwards;
        }

        .pareto-kpi-card.animate-in:nth-child(1) {
            animation-delay: 0.05s;
        }

        .pareto-kpi-card.animate-in:nth-child(2) {
            animation-delay: 0.1s;
        }

        .pareto-kpi-card.animate-in:nth-child(3) {
            animation-delay: 0.15s;
        }

        .pareto-kpi-card.animate-in:nth-child(4) {
            animation-delay: 0.2s;
        }

        .pareto-kpi-card.animate-in:nth-child(5) {
            animation-delay: 0.25s;
        }

        @keyframes kpiCardFadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .dashboard-kpi-strip {
                flex-direction: column;
            }

            .dashboard-kpi-item {
                min-width: 100%;
            }

            .pareto-kpi-cards {
                flex-direction: column;
                justify-content: flex-start;
            }

            .pareto-kpi-card {
                min-width: 100%;
                max-width: none;
            }

            .pareto-chart-container {
                height: 420px;
            }

            .pareto-insight {
                font-size: 13px;
                padding: 12px;
            }
        }

        @media print {
            @page {
                margin: 0.5cm;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }

            /* Ocultar elementos de navegacion, tabs inactivos, busqueda, botones y alertas */
            .nav-tabs,
            .nav-tabs>li,
            .tab-pane:not(.active),
            .filtros-container,
            #filtro_tabla_tiempos,
            .panel-heading input,
            .btn-acciones,
            .btn-ver-manifiestos,
            .exa-header,
            .modal,
            button,
            .btn,
            .alert-info,
            .alert-warning,
            .barra-detalle-item:last-child {
                display: none !important;
            }

            /* Mostrar encabezado de impresion */
            .header-print-tiempos-only {
                display: block !important;
            }

            /* Formatear tarjetas de KPIs en una franja horizontal de 6 columnas en la misma linea */
            .dashboard-kpi-strip,
            .dashboard-kpis-container {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                justify-content: space-between !important;
                align-items: stretch !important;
                gap: 6px !important;
                margin-bottom: 12px !important;
                padding: 6px 8px !important;
                background: #f8f9fa !important;
                border: 1px solid #cbd6e2 !important;
                border-radius: 6px !important;
                page-break-inside: avoid !important;
            }

            .dashboard-kpi-strip .dashboard-kpi-item,
            .dashboard-kpis-container .dashboard-kpi-item {
                flex: 1 1 0 !important;
                width: 16% !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 5px 6px !important;
                background: #ffffff !important;
                border-radius: 4px !important;
                box-sizing: border-box !important;
                page-break-inside: avoid !important;
            }

            .dashboard-kpi-label {
                font-size: 9px !important;
                font-weight: bold !important;
                line-height: 1.1 !important;
            }

            .dashboard-kpi-value {
                font-size: 15px !important;
                font-weight: bold !important;
                line-height: 1.1 !important;
            }

            /* Formatear el grafico para impresion dividida fila por fila (como una tabla) */
            .panel-chart-tiempos-print {
                margin-bottom: 12px !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }

            .chart-trend-container {
                display: none !important;
            }

            .chart-print-rows-container {
                display: block !important;
                width: 100% !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }

            .chart-print-row {
                display: flex !important;
                align-items: center !important;
                margin-bottom: 5px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* Para la vista Tendencia en el tab Por Configuracion: ocultar canvas y mostrar imagen de alta resolucion en impresion */
            .vista-dia-panel .ceo-chart-wrap canvas {
                display: none !important;
            }

            .vista-dia-panel .ceo-chart-wrap .print-tendencia-img {
                display: block !important;
                width: 100% !important;
                height: auto !important;
                max-width: 100% !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .vista-dia-panel {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* Expandir tablas para impresion completa sin scrollbar ni limite de altura */
            .table-responsive {
                max-height: none !important;
                height: auto !important;
                overflow: visible !important;
            }

            table {
                page-break-inside: auto !important;
            }

            tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }

            thead {
                display: table-header-group !important;
            }

            /* Eliminar margenes inferiores finales para prevenir hojas en blanco al imprimir */
            html,
            body {
                height: auto !important;
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
            }

            #dashboardContentTiempos,
            #dashboardContentTiempos>.panel:last-child,
            .panel:last-child,
            .panel:last-of-type {
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
            }

            .chart-print-rows-container .chart-print-row:last-child {
                margin-bottom: 0 !important;
            }

            body.print-solo-resumen .panel-detalle-tiempos-print {
                display: none !important;
            }

            .config-card {
                page-break-inside: avoid;
                margin-bottom: 10px;
                padding: 5px;
                page-break-before: auto;
            }

            .config-card:first-child {
                page-break-before: auto;
                margin-top: 0 !important;
            }

            .config-header {
                padding: 5px 8px;
                margin-bottom: 5px;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .config-header h4 {
                font-size: 12px;
            }

            .config-header p {
                font-size: 10px;
            }

            .config-stats {
                gap: 5px;
                margin-bottom: 5px;
            }

            .stat-card {
                padding: 5px 8px;
                min-width: 80px;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .stat-card .stat-label {
                font-size: 9px;
                margin-bottom: 2px;
            }

            .stat-card .stat-value {
                font-size: 14px;
            }

            .turnos-table {
                font-size: 10px;
                margin-top: 5px;
            }

            .turnos-table thead th {
                padding: 4px 5px;
                font-size: 10px;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .turnos-table tbody td {
                padding: 4px 5px;
                font-size: 10px;
            }

            .turnos-table th:nth-child(8),
            .turnos-table td:nth-child(8) {
                display: none !important;
            }

            .barra-progreso-tabla {
                min-width: 100px;
            }

            .barra-progreso-fondo {
                height: 18px;
            }

            .barra-progreso-relleno {
                font-size: 9px;
            }

            .fecha-header {
                margin-top: 10px !important;
                margin-bottom: 5px !important;
                padding: 5px 8px !important;
            }

            .fecha-header h5 {
                font-size: 12px !important;
            }

            .chart-dashboard-container {
                padding: 12px;
            }

            .chart-with-axes {
                opacity: 1 !important;
                visibility: visible !important;
            }

            .chart-y-axis,
            .chart-y-ticks,
            .chart-y-tick,
            .chart-grid-line,
            .chart-x-label {
                opacity: 1 !important;
                visibility: visible !important;
            }

            .chart-bars-row {
                min-height: 140px;
            }

            .chart-bar-wrapper {
                min-width: 40px;
                max-width: 60px;
            }

            .chart-bar-porcentaje {
                font-size: 9px;
            }

            .chart-bar-columna {
                max-width: 40px;
            }

            .chart-segmento-valor {
                font-size: 9px;
            }

            .chart-segmento-pequeno .chart-segmento-valor {
                font-size: 8px;
            }

            .chart-bar-label {
                font-size: 9px;
            }

            .barra-progress .barra-porcentaje {
                font-size: 9px;
            }

            .barra-detalle {
                font-size: 9px;
            }

            /* Gráficos Por Chofer / Placa */
            .chart-cp-wrap {
                margin-top: 25px;
                padding: 15px;
                background: #fafbfc !important;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                page-break-inside: avoid;
            }

            .chart-cp-title {
                margin: 0 0 6px 0;
                font-size: 15px;
                color: #2C5D94;
                font-weight: 600;
            }

            .chart-cp-horizontal {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .chart-cp-row {
                display: flex;
                align-items: center;
                gap: 10px;
                min-height: 28px;
            }

            .chart-cp-label {
                flex: 0 0 180px;
                font-size: 11px;
            }

            .chart-cp-bar-track {
                flex: 1;
                height: 20px;
                background: #e9ecef !important;
                border-radius: 10px;
                overflow: hidden;
            }

            .chart-cp-bar-fill {
                background: #2C5D94 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .chart-cp-val {
                flex: 0 0 48px;
                font-size: 12px;
                font-weight: 700;
            }

            .chart-cp-pct {
                flex: 0 0 44px;
                font-size: 10px;
            }

            .chart-cp-leyenda {
                margin-top: 12px;
                padding-top: 10px;
                border-top: 1px solid #dee2e6;
                font-size: 11px;
            }

            .chart-cp-dot {
                background: #2C5D94 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .pareto-kpi-wrap {
                page-break-inside: avoid;
            }

            .pareto-chart-wrap {
                page-break-inside: avoid;
            }
        }

        /* Vista Ejecutiva Global (CEO) */
        .dashboard-ejecutivo {
            background: #f5f6f8;
        }

        .ceo-kpi-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .ceo-kpi-card {
            flex: 1 1 140px;
            min-width: 140px;
            background: #fff;
            border: 1px solid #e0e4e8;
            border-radius: 6px;
            padding: 12px 14px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
        }

        .ceo-kpi-card .ceo-kpi-icon {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .ceo-kpi-card .ceo-kpi-label {
            font-size: 11px;
            color: #5c6370;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin-bottom: 4px;
        }

        .ceo-kpi-card .ceo-kpi-value {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
        }

        .ceo-kpi-card .ceo-kpi-tendencia {
            font-size: 11px;
            margin-top: 4px;
        }

        .ceo-kpi-card.semaforo-verde {
            border-left: 4px solid #22c55e;
        }

        .ceo-kpi-card.semaforo-amarillo {
            border-left: 4px solid #eab308;
        }

        .ceo-kpi-card.semaforo-rojo {
            border-left: 4px solid #dc2626;
        }

        .ceo-bloque {
            background: #fff;
            border: 1px solid #e0e4e8;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
        }

        .ceo-bloque h4 {
            margin: 0 0 12px 0;
            font-size: 14px;
            color: #2C5D94;
            font-weight: 600;
        }

        .ceo-insight {
            font-size: 12px;
            color: #374151;
            margin-top: 10px;
            padding: 8px 10px;
            background: #f8fafc;
            border-radius: 4px;
            border-left: 3px solid #94a3b8;
        }

        .ceo-insight.alerta-roja {
            border-left-color: #dc2626;
            background: #fef2f2;
        }

        .ceo-alertas-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .ceo-alertas-list li {
            padding: 8px 10px;
            margin-bottom: 6px;
            border-radius: 4px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ceo-alertas-list li.nivel-alto {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
        }

        .ceo-alertas-list li.nivel-atencion {
            background: #fffbeb;
            border-left: 4px solid #eab308;
        }

        .ceo-alertas-list li.nivel-estable {
            background: #f0fdf4;
            border-left: 4px solid #22c55e;
        }

        .ceo-chart-wrap {
            position: relative;
            height: 280px;
            margin-top: 10px;
        }

        .ceo-bloque .ceo-chart-wrap:first-of-type {
            min-height: 320px;
        }

        /* Modal inactivos por planta: columna Chofer más ancha para nombres completos */
        .modal-inactivos-detalle-tabla .col-chofer-ancho {
            min-width: 260px;
            max-width: 380px;
            white-space: normal;
        }

        .modal-inactivos-detalle-tabla .col-num-manifiesto-inac {
            min-width: 140px;
            white-space: normal;
        }

        /* Badges de Control de Tiempos y Arribos */
        .badge-sla {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-sla-cumplido {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .badge-sla-anticipado {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #7dd3fc;
        }

        .badge-sla-excedido {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .badge-sla-modificado {
            background-color: #fff3cd;
            color: #664d03;
            border: 1px solid #ffecb5;
        }

        .badge-sla-transito {
            background-color: #e2e3e5;
            color: #41464b;
            border: 1px solid #d3d6d8;
        }
    </style>
</HEAD>

<BODY>
    <div class="panel panel-default panel-main">
        <div class="exa-header">
            <h3><i class="fa fa-dashboard"></i> Reporte de Manifiestos</h3>
        </div>
        <div class="panel-body">

            <!-- Tabs -->
            <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 15px; border-bottom: 2px solid #2C5D94;">
                <li role="presentation" class="active">
                    <a href="#tab_configuracion" aria-controls="tab_configuracion" role="tab" data-toggle="tab">
                        <i class="fa fa-cog"></i> Por Configuración
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab_rango" aria-controls="tab_rango" role="tab" data-toggle="tab">
                        <i class="fa fa-calendar"></i> Por Rango de Fechas
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab_chofer_placa" aria-controls="tab_chofer_placa" role="tab" data-toggle="tab">
                        <i class="fa fa-users"></i> Por Chofer / Placa
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab_ejecutivo" aria-controls="tab_ejecutivo" role="tab" data-toggle="tab">
                        <i class="fa fa-line-chart"></i> Vista Ejecutiva Global
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab_tiempos" aria-controls="tab_tiempos" role="tab" data-toggle="tab">
                        <i class="fa fa-clock-o"></i> Control de Tiempos y Arribos
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Tab 1: Por Configuración -->
                <div role="tabpanel" class="tab-pane active" id="tab_configuracion">
                    <div class="filtros-container">
                        <div class="form-group">
                            <label class="control-label">Configuración de Turnos:</label>
                            <select id="select_configuracion" name="select_configuracion" class="form-control" style="width: 400px;">
                            </select>
                            <button type="button" class="btn btn-primary" onclick="cargarDashboard();">
                                <span class="glyphicon glyphicon-search"></span> Buscar
                            </button>
                            <button type="button" class="btn btn-default" onclick="cargarConfiguraciones();">
                                <span class="glyphicon glyphicon-refresh"></span> Actualizar
                            </button>
                            <div class="btn-group" role="group" style="margin-left: 8px;">
                                <button type="button" class="btn btn-sm btn-vista-global-config btn-warning" id="btnVistaTablaConfig" onclick="cambiarVistaGlobalConfig('tabla');"><i class="fa fa-table"></i> Tabla</button>
                                <button type="button" class="btn btn-sm btn-vista-global-config btn-default" id="btnVistaBarrasConfig" onclick="cambiarVistaGlobalConfig('barras');"><i class="fa fa-bar-chart"></i> Barras</button>
                                <button type="button" class="btn btn-sm btn-vista-global-config btn-default" id="btnVistaTendenciaConfig" onclick="cambiarVistaGlobalConfig('tendencia');"><i class="fa fa-line-chart"></i> Tendencia</button>
                            </div>
                            <button type="button" class="btn btn-success" onclick="imprimirReporte();">
                                <span class="glyphicon glyphicon-print"></span> Imprimir
                            </button>
                            <button type="button" class="btn btn-info" onclick="exportarExcel();">
                                <span class="glyphicon glyphicon-download-alt"></span> Excel
                            </button>
                        </div>
                        <div class="form-group" style="margin-top: 8px; width: 100%;">
                            <label class="checkbox-inline" style="margin-left: 0;">
                                <input type="checkbox" id="omitir_dias_sin_manifiestos_config" value="1"> Omitir días sin manifiestos
                            </label>
                            <label class="checkbox-inline" id="wrap_chk_vs_garita_in_config" style="margin-left: 15px; display: none;">
                                <input type="checkbox" id="chk_vs_garita_in_config" value="1"> vs Garita IN
                            </label>
                        </div>
                    </div>
                    <div class="dashboard-container">
                        <div id="dashboardContent">
                            <div class="loading">
                                <i class="fa fa-spinner fa-spin"></i>
                                <p>Cargando dashboard...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Por Rango de Fechas -->
                <div role="tabpanel" class="tab-pane" id="tab_rango">
                    <div class="filtros-container">
                        <div class="form-group">
                            <label class="control-label">Mes:</label>
                            <input type="month" id="mes_rango" class="form-control" style="width: 150px; display: inline-block;" title="Seleccione un mes para autocompletar las fechas">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Desde:</label>
                            <input type="date" id="fecha_inicio_rango" class="form-control" style="width: 140px; display: inline-block;">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Hasta:</label>
                            <input type="date" id="fecha_fin_rango" class="form-control" style="width: 140px; display: inline-block;">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Planta:</label>
                            <select id="select_planta_rango" name="select_planta_rango" class="form-control" style="width: 250px;">
                                <option value="">Todas las plantas</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="button" class="btn btn-primary" onclick="cargarDashboardRango();">
                                <span class="glyphicon glyphicon-search"></span> Buscar
                            </button>
                            <button type="button" class="btn btn-warning" id="btnVistaBarrasRango" onclick="alternarVistaRango();">
                                <span class="glyphicon glyphicon-signal"></span> <span id="textoVistaRango">Vista Barras</span>
                            </button>
                            <button type="button" class="btn btn-success" onclick="imprimirReporteRango();">
                                <span class="glyphicon glyphicon-print"></span> Imprimir
                            </button>
                            <button type="button" class="btn btn-info" onclick="exportarExcelRango();">
                                <span class="glyphicon glyphicon-download-alt"></span> Excel
                            </button>
                        </div>
                        <div class="form-group" style="margin-top: 8px; width: 100%;">
                            <label class="checkbox-inline" style="margin-left: 0;">
                                <input type="checkbox" id="omitir_dias_sin_manifiestos" value="1"> Omitir días sin manifiestos
                            </label>
                        </div>
                    </div>
                    <div class="dashboard-container">
                        <div id="dashboardContentRango">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Seleccione el rango de fechas y haga clic en Buscar para ver el reporte.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Por Chofer / Placa -->
                <div role="tabpanel" class="tab-pane" id="tab_chofer_placa">
                    <div class="filtros-container">
                        <div class="form-group">
                            <label class="control-label">Mes:</label>
                            <input type="month" id="mes_chofer_placa" class="form-control" style="width: 150px; display: inline-block;" value="<?php echo date('Y-m'); ?>" title="Seleccione el mes para el reporte">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Desde:</label>
                            <input type="date" id="fecha_inicio_chofer_placa" class="form-control" style="width: 140px; display: inline-block;">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Hasta:</label>
                            <input type="date" id="fecha_fin_chofer_placa" class="form-control" style="width: 140px; display: inline-block;">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Vista:</label>
                            <select id="tipo_vista_chofer_placa" class="form-control" style="width: 180px; display: inline-block;">
                                <option value="chofer">Por chofer</option>
                                <option value="placa">Por placa</option>
                                <option value="planta">Por planta</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="button" class="btn btn-primary" id="btnBuscarChoferPlaca" onclick="typeof cargarDashboardChoferPlaca === 'function' && cargarDashboardChoferPlaca();">
                                <span class="glyphicon glyphicon-search"></span> Buscar
                            </button>
                            <button type="button" class="btn btn-success" onclick="imprimirReporteChoferPlaca();">
                                <span class="glyphicon glyphicon-print"></span> Imprimir
                            </button>
                            <button type="button" class="btn btn-info" onclick="exportarExcelChoferPlaca();">
                                <span class="glyphicon glyphicon-download-alt"></span> Excel
                            </button>
                        </div>
                    </div>
                    <div class="dashboard-container" id="dashboardChoferPlacaContainer">
                        <div id="dashboardContentChoferPlaca">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Seleccione el rango de fechas y la vista (chofer o placa), luego haga clic en Buscar.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Vista Ejecutiva Global (CEO) -->
                <div role="tabpanel" class="tab-pane" id="tab_ejecutivo">
                    <div class="filtros-container">
                        <div class="form-group">
                            <label class="control-label">Mes:</label>
                            <input type="month" id="mes_ejecutivo" class="form-control" style="width: 150px; display: inline-block;" value="<?php echo date('Y-m'); ?>" title="Seleccione el mes para el reporte">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Desde:</label>
                            <input type="date" id="fecha_inicio_ejecutivo" class="form-control" style="width: 140px; display: inline-block;">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Hasta:</label>
                            <input type="date" id="fecha_fin_ejecutivo" class="form-control" style="width: 140px; display: inline-block;">
                        </div>
                        <div class="form-group">
                            <button type="button" class="btn btn-primary" id="btnBuscarEjecutivo" onclick="typeof cargarDashboardEjecutivo === 'function' && cargarDashboardEjecutivo();">
                                <span class="glyphicon glyphicon-dashboard"></span> Generar tablero
                            </button>
                            <button type="button" class="btn btn-success" onclick="typeof imprimirReporteEjecutivo === 'function' && imprimirReporteEjecutivo();">
                                <span class="glyphicon glyphicon-print"></span> Imprimir informe gerencial
                            </button>
                        </div>
                    </div>
                    <div class="dashboard-container dashboard-ejecutivo" id="dashboardEjecutivoContainer">
                        <div id="dashboardContentEjecutivo">
                            <div class="alert alert-info">
                                <i class="fa fa-line-chart"></i> Seleccione el rango de fechas y haga clic en <strong>Generar tablero</strong> para ver el tablero ejecutivo.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 5: Control de Tiempos y Arribos -->
                <div role="tabpanel" class="tab-pane" id="tab_tiempos">
                    <div class="header-print-tiempos-only" style="display:none; margin-bottom: 15px;" id="header_print_tiempos"></div>
                    <div class="filtros-container" style="display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 10px;">
                        <div style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px;">
                            <div class="form-group">
                                <label class="control-label">Mes:</label>
                                <input type="month" id="mes_tiempos" class="form-control" style="width: 150px; display: inline-block;" value="<?php echo date('Y-m'); ?>" title="Seleccione el mes para el reporte">
                            </div>
                            <div class="form-group">
                                <label class="control-label">Desde:</label>
                                <input type="date" id="fecha_inicio_tiempos" class="form-control" style="width: 140px; display: inline-block;">
                            </div>
                            <div class="form-group">
                                <label class="control-label">Hasta:</label>
                                <input type="date" id="fecha_fin_tiempos" class="form-control" style="width: 140px; display: inline-block;">
                            </div>
                            <div class="form-group">
                                <label class="control-label">Planta:</label>
                                <select id="select_planta_tiempos" class="form-control" style="width: 220px;">
                                    <option value="">Todas las plantas</option>
                                </select>
                            </div>
                            <!-- <div class="form-group">
                                <label class="control-label">Estado:</label>
                                <select id="select_estado_tiempos" class="form-control" style="width: 180px;">
                                    <option value="todos">Todos los viajes</option>
                                    <option value="cumplido">🟢 A Tiempo (Puntual)</option>
                                    <option value="anticipado">🔵 Con Holgura (Anticipado)</option>
                                    <option value="excedido">🔴 Tiempo Excedido</option>
                                    <option value="modificado">🟡 Arribo Modificado</option>
                                    <option value="en_transito">⚪ En Tránsito</option>
                                </select>
                            </div> -->
                        </div>
                        <div class="form-group" style="margin-left: auto;">
                            <button type="button" class="btn btn-primary" id="btnBuscarTiempos" onclick="typeof cargarDashboardTiempos === 'function' && cargarDashboardTiempos();">
                                <span class="glyphicon glyphicon-search"></span> Buscar
                            </button>
                            <button type="button" class="btn btn-info" onclick="typeof imprimirReporteTiempos === 'function' && imprimirReporteTiempos();">
                                <span class="glyphicon glyphicon-print"></span> Imprimir
                            </button>
                            <button type="button" class="btn btn-success" onclick="typeof exportarExcelTiempos === 'function' && exportarExcelTiempos();">
                                <span class="glyphicon glyphicon-download-alt"></span> Excel
                            </button>
                        </div>
                    </div>
                    <div class="dashboard-container" id="dashboardTiemposContainer">
                        <div id="dashboardContentTiempos">
                            <div class="alert alert-info">
                                <i class="fa fa-clock-o"></i> Seleccione el rango de fechas y haga clic en <strong>Buscar</strong> para evaluar el cumplimiento de tiempos de llegada y alertas de retraso.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal drill-down Responsabilidad Operativa (inactivos por planta) -->
    <div class="modal fade" id="modalInactivosDetallePlanta" tabindex="-1" role="dialog" aria-labelledby="modalInactivosDetallePlantaLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modalInactivosDetallePlantaLabel"><i class="fa fa-building-o"></i> <span id="modalInactivosDetallePlantaTitulo">Detalle inactivos por planta</span></h4>
                </div>
                <div class="modal-body">
                    <div id="modalInactivosDetallePlantaCuerpo">
                        <p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <script>
        window.dashboardTurnosContext = {
            plaCodAsignada: <?php echo isset($Pla_Cod_Asignada) ? intval($Pla_Cod_Asignada) : 0; ?>,
            plaNomAsignada: <?php echo json_encode(isset($Pla_Nom_Asignada) ? $Pla_Nom_Asignada : ''); ?>,
            soloTabChoferPlaca: <?php echo (!empty($soloTabChoferPlaca)) ? 'true' : 'false'; ?>
        };
    </script>
    <script src="../VALIDACIONES/man_val_dashboard_turnos.js?a=102"></script>
</BODY>

</HTML>