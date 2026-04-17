<?php
/**
 * Dashboard Operativo RELAVERA
 * Flujo: Anticipo → Turno → Manifiesto → Facturación Mensual
 * @author Sistema EXA
 * @version 1.0
 * Compatible PHP 5.3.8
 * Sin auto-refresh ni AJAX en intervalos. Botón "Actualizar Datos" manual.
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../LOGICA/dashboard_relavera_data.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Mani;

/* ==================== AJAX: OBTENER SOLO MONITOR (para botón Actualizar - solo día actual) ==================== */
if (isset($_GET['getMonitorOnly'])) {
    header('Content-Type: application/json; charset=utf-8');
    $Pla_Cod = isset($_GET['Pla_Cod']) ? intval($_GET['Pla_Cod']) : 0;
    $Cli_Cod = isset($_GET['Cli_Cod']) ? intval($_GET['Cli_Cod']) : 0;
    $Man_Tip = isset($_GET['Man_Tip']) ? trim($_GET['Man_Tip']) : '';
    $params_hoy = array(
        'Emp_Cod' => $Ses_Emp_Cod,
        'Fec_Ini' => date('Y-m-d'),
        'Fec_Fin' => date('Y-m-d'),
        'Pla_Cod' => $Pla_Cod,
        'Cli_Cod' => $Cli_Cod,
        'Man_Tip' => $Man_Tip
    );
    $con = $obBD_conexion->conexion;
    try {
        $sql14h = sentencias_dashboard_relavera(14, $params_hoy);
        $r14h = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql14h, $con));
        $sql7h = sentencias_dashboard_relavera(7, $params_hoy);
        $res7h = $obBD_con1->consulta($sql7h, $con);
        $sql13h = sentencias_dashboard_relavera(13, $params_hoy);
        $r13h = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql13h, $con));
        $sql20h = sentencias_dashboard_relavera(20, $params_hoy);
        $r20h = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql20h, $con));
        $sql17h = sentencias_dashboard_relavera(17, $params_hoy);
        $r17h = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql17h, $con));
        $monitor_hoy = array(
            'total_turnos' => isset($r17h['total_turnos']) ? intval($r17h['total_turnos']) : 0,
            'turnos_pendientes' => isset($r14h['turnos_pendientes']) ? intval($r14h['turnos_pendientes']) : 0,
            'garita_in' => 0,
            'aprobados' => isset($r20h['aprobados_tecnico']) ? intval($r20h['aprobados_tecnico']) : 0,
            'garita_out' => 0,
            'turnos_anulados' => isset($r13h['turnos_anulados']) ? intval($r13h['turnos_anulados']) : 0
        );
        while ($row = $obBD_con1->fetch_assoc($res7h)) {
            if ($row['estado'] == 'GE') $monitor_hoy['garita_in'] = intval($row['cantidad']);
            if ($row['estado'] == 'GS') $monitor_hoy['garita_out'] = intval($row['cantidad']);
        }
        $obBD_con1->echoJson(array('success' => true, 'monitor_hoy' => $monitor_hoy));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

/* ==================== AJAX: MANIFIESTOS DEL MONITOR (modal) ==================== */
if (isset($_GET['getManifiestosMonitor'])) {
    header('Content-Type: application/json; charset=utf-8');
    $Tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
    $Pla_Cod = isset($_GET['Pla_Cod']) ? intval($_GET['Pla_Cod']) : 0;
    $Cli_Cod = isset($_GET['Cli_Cod']) ? intval($_GET['Cli_Cod']) : 0;
    $params = array(
        'Emp_Cod' => $Ses_Emp_Cod,
        'Fec_Ini' => date('Y-m-d'),
        'Fec_Fin' => date('Y-m-d'),
        'Pla_Cod' => $Pla_Cod,
        'Cli_Cod' => $Cli_Cod,
        'Tipo' => $Tipo
    );
    $con = $obBD_conexion->conexion;
    try {
        $sql23 = sentencias_dashboard_relavera(23, $params);
        $res23 = $obBD_con1->consulta($sql23, $con);
        $manifiestos = array();
        while ($row = $obBD_con1->fetch_assoc($res23)) {
            $obBD_con1->utf8_change_param($row);
            $horaInicio = isset($row['Tud_Hin']) ? $row['Tud_Hin'] : '';
            $horaFin = isset($row['Tud_Hfi']) ? $row['Tud_Hfi'] : '';
            $horario_turno = ($horaInicio && $horaFin) ? date('H:i', strtotime($horaInicio)) . ' - ' . date('H:i', strtotime($horaFin)) : '';
            $manifiestos[] = array(
                'Man_Cod' => $row['Man_Cod'],
                'Man_Num' => $row['Man_Num'],
                'ManNum' => isset($row['ManNum']) ? $row['ManNum'] : '',
                'Man_Fec' => isset($row['Man_Fec']) ? date('Y-m-d', strtotime($row['Man_Fec'])) : '',
                'Cliente' => isset($row['Cliente']) ? $row['Cliente'] : '',
                'Pla_Nom' => isset($row['Pla_Nom']) ? $row['Pla_Nom'] : '',
                'Veh_Pla' => isset($row['Veh_Pla']) ? $row['Veh_Pla'] : '',
                'chofer_nombre' => isset($row['chofer_nombre']) ? $row['chofer_nombre'] : '',
                'horario_turno' => $horario_turno,
                'Man_Tip_1' => isset($row['Man_Tip_1']) ? $row['Man_Tip_1'] : '',
                'Man_Tip_2' => isset($row['Man_Tip_2']) ? $row['Man_Tip_2'] : '',
                'Man_Tip_3' => isset($row['Man_Tip_3']) ? $row['Man_Tip_3'] : '',
                'Man_Tip_4' => isset($row['Man_Tip_4']) ? $row['Man_Tip_4'] : '',
                'Man_Tip_5' => isset($row['Man_Tip_5']) ? $row['Man_Tip_5'] : ''
            );
        }
        $fechaFormato = date('d/m/Y');
        $obBD_con1->echoJson(array('success' => true, 'manifiestos' => $manifiestos, 'tipo' => $Tipo, 'fecha' => $fechaFormato));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

/* ==================== AJAX: OBTENER DATOS DEL DASHBOARD ==================== */
if (isset($_GET['getDashboardRelaveraAjax'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : date('Y-m-d');
    $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : date('Y-m-d');
    $Pla_Cod = isset($_GET['Pla_Cod']) ? intval($_GET['Pla_Cod']) : 0;
    $Cli_Cod = isset($_GET['Cli_Cod']) ? intval($_GET['Cli_Cod']) : 0;
    $Man_Tip = isset($_GET['Man_Tip']) ? trim($_GET['Man_Tip']) : '';
    
    // Convertir fechas d/m/Y a Y-m-d si aplica
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_inicio, $m)) {
        $fecha_inicio = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_fin, $m)) {
        $fecha_fin = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    
    $params = array(
        'Emp_Cod' => $Ses_Emp_Cod,
        'Fec_Ini' => $fecha_inicio,
        'Fec_Fin' => $fecha_fin,
        'Pla_Cod' => $Pla_Cod,
        'Cli_Cod' => $Cli_Cod,
        'Man_Tip' => $Man_Tip
    );
    
    $resultado = array('success' => true);
    $con = $obBD_conexion->conexion;
    
    try {
        // 1. Anticipo Total Activo
        $sql1 = sentencias_dashboard_relavera(1, $params);
        $r1 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql1, $con));
        $resultado['anticipo_total'] = isset($r1['anticipo_total']) ? floatval($r1['anticipo_total']) : 0;
        
        // 2. Saldo Disponible Total
        $sql2 = sentencias_dashboard_relavera(2, $params);
        $r2 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql2, $con));
        $resultado['saldo_disponible'] = isset($r2['saldo_disponible']) ? floatval($r2['saldo_disponible']) : 0;
        
        // 3. Clientes en Riesgo
        $sql3 = sentencias_dashboard_relavera(3, $params);
        $r3 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql3, $con));
        $resultado['clientes_riesgo'] = isset($r3['cantidad']) ? intval($r3['cantidad']) : 0;
        
        // 4. Turnos Generados Hoy
        $sql4 = sentencias_dashboard_relavera(4, $params);
        $r4 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql4, $con));
        $resultado['turnos_hoy'] = isset($r4['turnos_hoy']) ? intval($r4['turnos_hoy']) : 0;
        
        // 5. Manifiestos en Proceso
        $sql5 = sentencias_dashboard_relavera(5, $params);
        $r5 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql5, $con));
        $resultado['manifiestos_proceso'] = isset($r5['en_proceso']) ? intval($r5['en_proceso']) : 0;
        
        // 6. Manifiestos Pendientes de Facturación
        $sql6 = sentencias_dashboard_relavera(6, $params);
        $r6 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql6, $con));
        $resultado['pendientes_fact'] = isset($r6['pendientes_fact']) ? intval($r6['pendientes_fact']) : 0;
        
        // 13. Turnos Anulados Hoy
        $sql13 = sentencias_dashboard_relavera(13, $params);
        $r13 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql13, $con));
        $resultado['turnos_anulados'] = isset($r13['turnos_anulados']) ? intval($r13['turnos_anulados']) : 0;
        
        // 14. Turnos Pendientes (Man_Tip P, sin Garita IN, rango fechas)
        $sql14 = sentencias_dashboard_relavera(14, $params);
        $r14 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql14, $con));
        $resultado['turnos_pendientes'] = isset($r14['turnos_pendientes']) ? intval($r14['turnos_pendientes']) : 0;
        
        // 21. Garita IN (GE) - para KPI Turnos Pendientes = Pendientes + Garita IN
        $sql21 = sentencias_dashboard_relavera(21, $params);
        $r21 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql21, $con));
        $resultado['garita_in_rango'] = isset($r21['garita_in']) ? intval($r21['garita_in']) : 0;
        
        // 22. Garita OUT (GS,F) - para KPI Aprobados = Garita OUT
        $sql22 = sentencias_dashboard_relavera(22, $params);
        $r22 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql22, $con));
        $resultado['garita_out_rango'] = isset($r22['garita_out']) ? intval($r22['garita_out']) : 0;
        
        // Monitor Operativo - solo día actual
        $params_hoy = array(
            'Emp_Cod' => $Ses_Emp_Cod,
            'Fec_Ini' => date('Y-m-d'),
            'Fec_Fin' => date('Y-m-d'),
            'Pla_Cod' => $Pla_Cod,
            'Cli_Cod' => $Cli_Cod,
            'Man_Tip' => $Man_Tip
        );
        $sql14h = sentencias_dashboard_relavera(14, $params_hoy);
        $r14h = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql14h, $con));
        $sql7h = sentencias_dashboard_relavera(7, $params_hoy);
        $res7h = $obBD_con1->consulta($sql7h, $con);
        $sql13h = sentencias_dashboard_relavera(13, $params_hoy);
        $r13h = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql13h, $con));
        $sql20h = sentencias_dashboard_relavera(20, $params_hoy);
        $r20h = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql20h, $con));
        $sql17h = sentencias_dashboard_relavera(17, $params_hoy);
        $r17h = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql17h, $con));
        $monitor_hoy = array(
            'total_turnos' => isset($r17h['total_turnos']) ? intval($r17h['total_turnos']) : 0,
            'turnos_pendientes' => isset($r14h['turnos_pendientes']) ? intval($r14h['turnos_pendientes']) : 0,
            'garita_in' => 0,
            'aprobados' => isset($r20h['aprobados_tecnico']) ? intval($r20h['aprobados_tecnico']) : 0,
            'garita_out' => 0,
            'turnos_anulados' => isset($r13h['turnos_anulados']) ? intval($r13h['turnos_anulados']) : 0
        );
        while ($row = $obBD_con1->fetch_assoc($res7h)) {
            if ($row['estado'] == 'GE') $monitor_hoy['garita_in'] = intval($row['cantidad']);
            if ($row['estado'] == 'GS') $monitor_hoy['garita_out'] = intval($row['cantidad']);
        }
        $resultado['monitor_hoy'] = $monitor_hoy;
        
        // 7. Monitor Operativo (Estado | Cantidad | Tiempo Promedio) - rango fechas para KPIs
        $sql7 = sentencias_dashboard_relavera(7, $params);
        $res7 = $obBD_con1->consulta($sql7, $con);
        $monitor = array();
        $estados_nom = array('GE' => 'Garita IN', 'A' => 'En Planta', 'GS' => 'Garita OUT');
        while ($row = $obBD_con1->fetch_assoc($res7)) {
            $obBD_con1->utf8_change_param($row);
            $tiempo = floatval($row['tiempo_prom_min']);
            if ($tiempo < 0) $tiempo = 0;
            $monitor[] = array(
                'estado' => $row['estado'],
                'estado_nom' => isset($estados_nom[$row['estado']]) ? $estados_nom[$row['estado']] : $row['estado'],
                'cantidad' => intval($row['cantidad']),
                'tiempo_prom_min' => round($tiempo, 1)
            );
        }
        $resultado['monitor'] = $monitor;
        
        // 8. Proyección consumo anticipo (semáforo)
        $sql8 = sentencias_dashboard_relavera(8, $params);
        $res8 = $obBD_con1->consulta($sql8, $con);
        $proyeccion = array();
        while ($row = $obBD_con1->fetch_assoc($res8)) {
            $obBD_con1->utf8_change_param($row);
            $dias = floatval($row['dias_estimados']);
            $prom = floatval($row['promedio_diario']);
            /* Evitar división por cero: si prom=0, dias_estimados ya viene 9999 del SQL */
            $semaf = 'verde';
            if ($dias < 3) $semaf = 'rojo';
            elseif ($dias < 5) $semaf = 'amarillo';
            $proyeccion[] = array(
                'cliente' => $row['cliente'],
                'planta' => $row['planta'],
                'saldo_actual' => floatval($row['saldo_actual']),
                'promedio_diario' => $prom,
                'dias_estimados' => ($prom > 0) ? round($dias, 1) : 9999,
                'semaf' => $semaf
            );
        }
        $resultado['proyeccion'] = $proyeccion;
        
        // 9. Plantas para filtro
        $sql9 = sentencias_dashboard_relavera(9, $params);
        $res9 = $obBD_con1->consulta($sql9, $con);
        $plantas = array();
        while ($row = $obBD_con1->fetch_assoc($res9)) {
            $obBD_con1->utf8_change_param($row);
            $plantas[] = array('Pla_Cod' => intval($row['Pla_Cod']), 'Pla_Nom' => $row['Pla_Nom'], 'Pla_Pfa' => $row['Pla_Pfa']);
        }
        $resultado['plantas'] = $plantas;
        
        // 10. Clientes para filtro
        $sql10 = sentencias_dashboard_relavera(10, $params);
        $res10 = $obBD_con1->consulta($sql10, $con);
        $clientes = array();
        while ($row = $obBD_con1->fetch_assoc($res10)) {
            $obBD_con1->utf8_change_param($row);
            $clientes[] = array('Cli_Cod' => intval($row['Cli_Cod']), 'Cliente' => $row['Cliente']);
        }
        $resultado['clientes'] = $clientes;
        
        $sql11 = sentencias_dashboard_relavera(11, $params);
        $res11 = $obBD_con1->consulta($sql11, $con);
        $alertas = array();
        while ($row = $obBD_con1->fetch_assoc($res11)) {
            $obBD_con1->utf8_change_param($row);
            $alertas[] = array('cliente' => $row['cliente'], 'planta' => $row['Pla_Nom'], 'saldo' => floatval($row['saldo']));
        }
        $resultado['alertas'] = $alertas;

        // Facturación por Planta (Mensual vs Diario)
        $sql26 = sentencias_dashboard_relavera(26, $params);
        $res26 = $obBD_con1->consulta($sql26, $con);
        $facturacion_summary = array();
        while ($row = $obBD_con1->fetch_assoc($res26)) {
            $obBD_con1->utf8_change_param($row);
            $facturacion_summary[] = array(
                'modo' => $row['modo'],
                'planta' => $row['planta'],
                'cliente' => $row['cliente'],
                'cantidad' => intval($row['cantidad'])
            );
        }
        $resultado['facturacion_summary'] = $facturacion_summary;

        // 12. Resumen del Período - Anticipos por tipo, consumo, saldo final
        // Valores siempre positivos para visualización; signo/color se aplica en frontend
        $sql12 = sentencias_dashboard_relavera(12, $params);
        $r12 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql12, $con));
        $saldo_ini = isset($r12['saldo_inicial']) ? floatval($r12['saldo_inicial']) : 0;
        $ant_fin = isset($r12['anticipo_financiero']) ? floatval($r12['anticipo_financiero']) : 0;
        $ant_ret = isset($r12['anticipo_retencion']) ? floatval($r12['anticipo_retencion']) : 0;
        $ant_aprobado = 0;
        $ant_por_aprobar = 0;
        $sql24 = sentencias_dashboard_relavera(24, $params);
        $r24 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql24, $con));
        $ant_aprobado = isset($r24['anticipo_aprobado']) ? floatval($r24['anticipo_aprobado']) : 0;
        $sql25 = sentencias_dashboard_relavera(25, $params);
        $r25 = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql25, $con));
        $ant_por_aprobar = isset($r25['anticipo_por_aprobar']) ? floatval($r25['anticipo_por_aprobar']) : 0;
        $cons_fac = isset($r12['consumo_facturado']) ? abs(floatval($r12['consumo_facturado'])) : 0;
        $cons_pend = isset($r12['consumo_pendiente']) ? abs(floatval($r12['consumo_pendiente'])) : 0;
        /* Validación: total_anticipo = aprobados + por aprobar + retenciones; consumo_total = facturado + pendiente
         * Saldo Final = saldo_inicial + total_anticipo - consumo_total (coherencia contable) */
        $total_anticipo = $ant_aprobado + $ant_por_aprobar + $ant_ret;
        $consumo_total = $cons_fac + $cons_pend;
        $saldo_final = $saldo_ini + $total_anticipo - $consumo_total;
        $resultado['resumen'] = array(
            'saldo_inicial' => $saldo_ini,
            'anticipo_financiero' => $ant_fin,
            'anticipo_aprobado' => $ant_aprobado,
            'anticipo_por_aprobar' => $ant_por_aprobar,
            'anticipo_retencion' => $ant_ret,
            'total_anticipo_generado' => $total_anticipo,
            'consumo_facturado' => $cons_fac,
            'consumo_pendiente' => $cons_pend,
            'consumo_total' => $consumo_total,
            'saldo_final' => $saldo_final
        );

        // 27. Tiempos en Relavera (Entrada vs Salida general de Man_Usu JSON)
        $sql27 = sentencias_dashboard_relavera(27, $params);
        $res27 = $obBD_con1->consulta($sql27, $con);
        $tiempos = array();
        if ($res27 !== false) {
            while ($r = $obBD_con1->fetch_assoc($res27)) {
                $log = json_decode($r['Man_Usu'], true);
                if (is_array($log)) {
                    $f_entrada = null;
                    $f_salida = null;
                    foreach ($log as $entry) {
                        if (isset($entry['Man_Tip']) && isset($entry['Fecha'])) {
                            if ($entry['Man_Tip'] === 'GE' && (!$f_entrada || $entry['Fecha'] < $f_entrada)) {
                                $f_entrada = $entry['Fecha'];
                            }
                            if ($entry['Man_Tip'] === 'GS' && (!$f_salida || $entry['Fecha'] > $f_salida)) {
                                $f_salida = $entry['Fecha'];
                            }
                        }
                    }
                    if ($f_entrada && $f_salida) {
                        $t1 = strtotime($f_entrada);
                        $t2 = strtotime($f_salida);
                        if ($t2 > $t1) {
                            $tiempos[] = ($t2 - $t1) / 60; // minutos
                        }
                    }
                }
            }
        }
        $resultado['tiempo_relavera_prom'] = (count($tiempos) > 0) ? round(array_sum($tiempos) / count($tiempos), 1) : 0;


        $resultado['fecha_inicio'] = $fecha_inicio;
        $resultado['fecha_fin'] = $fecha_fin;
        
    } catch (Exception $e) {
        $resultado['success'] = false;
        $resultado['message'] = $e->getMessage();
    }
    
    $obBD_con1->echoJson($resultado);
    exit;
}

// Cargar datos iniciales para filtros (plantas y clientes)
$params_init = array('Emp_Cod' => $Ses_Emp_Cod, 'Fec_Ini' => date('Y-m-d'), 'Fec_Fin' => date('Y-m-d'), 'Pla_Cod' => 0, 'Cli_Cod' => 0);
$sql_plantas = sentencias_dashboard_relavera(9, $params_init);
$res_plantas = $obBD_con1->consulta($sql_plantas, $obBD_conexion->conexion);
$lista_plantas = array();
while ($r = $obBD_con1->fetch_assoc($res_plantas)) {
    $obBD_con1->utf8_change_param($r);
    $lista_plantas[] = $r;
}
$sql_clientes = sentencias_dashboard_relavera(10, $params_init);
$res_clientes = $obBD_con1->consulta($sql_clientes, $obBD_conexion->conexion);
$lista_clientes = array();
while ($r = $obBD_con1->fetch_assoc($res_clientes)) {
    $obBD_con1->utf8_change_param($r);
    $lista_clientes[] = $r;
}
?>
<!DOCTYPE HTML>
<HTML>
<HEAD>
    <TITLE>Dashboard Operativo RELAVERA</TITLE>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .panel-main { margin: 20px; }
        .exa-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 4px 4px 0 0;
        }
        .exa-header h3 { margin: 0; font-size: 16px; font-weight: 600; }
        .dashboard-card {
            border-radius: 8px;
            padding: 15px;
            min-height: 120px;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .dashboard-card .card-icon { font-size: 28px; opacity: 0.9; margin-bottom: 5px; }
        .dashboard-card .card-title { font-size: 12px; font-weight: 600; text-transform: uppercase; margin: 0; opacity: 0.95; }
        .dashboard-card .card-value { font-size: 22px; font-weight: 700; margin: 5px 0; }
        .dashboard-card .card-detail { font-size: 11px; opacity: 0.9; }
        .card-azul { background: linear-gradient(135deg, #1e88e5 0%, #42a5f5 100%); }
        .card-verde { background: linear-gradient(135deg, #43a047 0%, #66bb6a 100%); }
        .card-rojo { background: linear-gradient(135deg, #e53935 0%, #ef5350 100%); }
        .card-amarillo { background: linear-gradient(135deg, #fb8c00 0%, #ffa726 100%); color: #333; }
        .card-morado { background: linear-gradient(135deg, #8e24aa 0%, #ab47bc 100%); }
        .filtros-bar { background: #f8f9fa; padding: 12px 15px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #dee2e6; }
        .monitor-table { width: 100%; border-collapse: collapse; }
        .monitor-table th { background: #2C5D94; color: white; padding: 8px 10px; text-align: left; font-weight: 600; }
        .monitor-table td { padding: 8px 10px; border-bottom: 1px solid #dee2e6; }
        .monitor-table tr:hover { background: #f8f9fa; }
        .semaf-verde { color: #28a745; font-weight: bold; }
        .semaf-amarillo { color: #ffc107; font-weight: bold; }
        .semaf-rojo { color: #dc3545; font-weight: bold; }
        .alerta-riesgo { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 10px; margin-bottom: 10px; color: #721c24; }
        /* Proyección: mini KPIs compactos */
        .proyeccion-mini-kpis { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 15px; }
        .proyeccion-mini-kpi { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 8px 14px; font-size: 12px; display: inline-flex; align-items: center; gap: 8px; }
        .proyeccion-mini-kpi .kpi-valor { font-weight: 700; font-size: 16px; }
        .proyeccion-mini-kpi.kpi-critico { border-color: #dc3545; background: #fff5f5; color: #721c24; }
        .proyeccion-mini-kpi.kpi-riesgo { border-color: #fd7e14; background: #fff8f0; color: #856404; }
        .proyeccion-mini-kpi.kpi-promedio { border-color: #2C5D94; background: #f0f6fc; color: #1a365d; }
        .badge-critico { background: #dc3545; color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-atencion { background: #fd7e14; color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-estable { background: #28a745; color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .seccion-proyeccion { margin-top: 35px; margin-bottom: 35px; }
        /* Días Estimados - colores por urgencia */
        .dias-hoy { color: #b71c1c !important; font-weight: 700 !important; }
        .dias-rojo { color: #dc3545 !important; }
        .dias-naranja { color: #e65100 !important; }
        .dias-verde { color: #28a745 !important; }
        .proyeccion-tbody tr.fila-hoy { border-left: 4px solid #b71c1c; }
        .proyeccion-tbody td[title] { cursor: help; }
        /* Títulos de sección - mayor espaciado vertical */
        .dashboard-seccion-titulo { background: #e9ecef; padding: 10px 15px; margin: 28px 0 18px 0; border-left: 4px solid #2C5D94; font-weight: 700; font-size: 14px; color: #333; }
        .dashboard-seccion-titulo:first-of-type { margin-top: 0; }
        .dashboard-periodo-label { color: #6c757d; font-size: 12px; margin-bottom: 15px; font-style: italic; }
        /* Tarjetas financieras y operativas */
        .card-financiero, .card-operativo { padding: 15px; color: #fff; margin-bottom: 10px; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-financiero h5, .card-operativo h5 { font-size: 12px; font-weight: 600; text-transform: uppercase; margin: 0 0 8px 0; opacity: 0.95; }
        .card-financiero h3, .card-operativo h3 { font-size: 18px; font-weight: 700; margin: 5px 0; }
        .card-financiero small, .card-operativo small { font-size: 10px; opacity: 0.9; display: block; }
        .card-financiero .card-icon { font-size: 26px; opacity: 0.9; margin-bottom: 5px; }
        /* KPI Card - tarjetas principales (Fila 1) */
        .kpi-card { min-height: 120px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 6px 14px rgba(0,0,0,0.2); }
        /* KPI Card Detalle - composición del anticipo (Fila 2) */
        .kpi-card-detalle { min-height: 140px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .kpi-card-detalle:hover { transform: translateY(-3px); box-shadow: 0 6px 14px rgba(0,0,0,0.2); }
        /* Colores financieros - HEX según especificación */
        .card-fin-inicio { background: linear-gradient(135deg, #0F9D8A 0%, #26a69a 100%); } /* Saldo Inicial - Verde petróleo #0F9D8A */
        .card-fin-amarillo { background: linear-gradient(135deg, #F4B942 0%, #ffca28 100%); color: #333; } /* Total Anticipo - Ámbar/Dorado #F4B942 */
        .card-fin-rosa { background: linear-gradient(135deg, #E53935 0%, #ef5350 100%); } /* Consumo Total - Rojo profesional #E53935 */
        .card-fin-morado { background: linear-gradient(135deg, #6A1B9A 0%, #7b1fa2 100%); } /* Saldo Final - Morado financiero #6A1B9A */
        .card-fin-verde { background: linear-gradient(135deg, #34A853 0%, #66bb6a 100%); } /* Aprobados - Verde #34A853 */
        .card-fin-naranja { background: linear-gradient(135deg, #F2994A 0%, #ffb74d 100%); color: #333; } /* Por Aprobar - Naranja #F2994A */
        .card-fin-azul { background: linear-gradient(135deg, #1E88E5 0%, #42a5f5 100%); } /* Retenciones - Azul #1E88E5 */
        /* Título separador composición */
        .dashboard-subseccion-titulo { font-size: 13px; font-weight: 600; color: #495057; margin: 20px 0 12px 0; padding-left: 4px; border-left: 3px solid #6c757d; }
        /* Monitor Operativo: celdas Cantidad clickeables */
        .monitor-cantidad-click { cursor: pointer; transition: background 0.2s; }
        .monitor-cantidad-click:hover { background: #2C5D94 !important; color: white !important; }
        /* Modal Manifiestos: más grande y scroll interno */
        .modal-manifiestos-relavera { width: 95%; max-width: 1200px; margin: 30px auto; }
        #modalManifiestosMonitor .modal-body { max-height: 75vh; overflow-y: auto; }
        body.modal-open { overflow: hidden; }
    </style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title"><i class="glyphicon glyphicon-stats"></i> Dashboard Operativo RELAVERA</h3>
        </div>
        <div class="panel-body">
            <!-- Botón Actualizar Datos (manual, sin auto-refresh) -->
            <div class="filtros-bar">
                <button type="button" id="btnActualizar" class="btn btn-primary">
                    <i class="glyphicon glyphicon-refresh"></i> Actualizar Datos
                </button>
                <span style="margin-left: 15px; color: #6c757d; font-size: 12px;">
                    <i class="glyphicon glyphicon-info-sign"></i> Los datos se actualizan solo al presionar el botón.
                </span>
            </div>
            
            <!-- Filtros superiores -->
            <div class="filtros-bar">
                <div class="row">
                    <div class="col-xs-12 col-sm-6 col-md-3">
                        <label>Fecha Desde</label>
                        <input type="date" id="fechaInicio" class="form-control input-sm" value="<?php echo date('Y-01-01'); ?>">
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-3">
                        <label>Fecha Hasta</label>
                        <input type="date" id="fechaFin" class="form-control input-sm" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-4">
                        <label>Planta</label>
                        <select id="selPlanta" class="form-control input-sm" style="width: 100%;">
                            <option value="">Todas</option>
                            <?php foreach ($lista_plantas as $p): ?>
                            <option value="<?php echo intval($p['Pla_Cod']); ?>"><?php echo htmlspecialchars($p['Pla_Nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-2" style="padding-top: 22px;">
                        <button type="button" id="btnFiltrar" class="btn btn-success btn-sm">
                            <i class="glyphicon glyphicon-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Contenedor principal (se refresca con AJAX al actualizar) -->
            <div id="contenedorDashboard">
                <div style="text-align: center; padding: 40px;">
                    <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size: 24px;"></i>
                    <p>Cargando datos...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/man_val_dashboard_relavera.js?e=4"></script>
    <script>
        $(function() {
            if ($.fn.select2) {
                $('#selPlanta').select2({
                    placeholder: 'Escriba para buscar...',
                    allowClear: true,
                    width: '100%'
                });
            }
        });
    </script>
</BODY>
</HTML>
