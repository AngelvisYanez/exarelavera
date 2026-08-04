<?php
/**
 * dashboard_ajax.php
 * Endpoint de Rutas AJAX y Procesamiento para el Dashboard Presupuestario (EXA PPTO).
 * Retorna respuestas estrictas en formato JSON {status, kpis, partidas, mensual} o catálogos dinámicos.
 */

// Iniciar almacenamiento en búfer de salida inmediatamente para prevenir cualquier fuga de texto/advertencias
if (!ob_get_level()) {
    @ob_start();
}

// Desactivar DebugBar si está cargado para que no inyecte scripts/HTML en el JSON
if (class_exists('DebugBar', false) && method_exists('DebugBar', 'setDebugBar')) {
    DebugBar::setDebugBar(null);
}

// Evitar que DebugBar o errores alteren o corrompan el formato del payload JSON
@ini_set('display_errors', '0');
if (function_exists('error_reporting')) {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once('../VALIDACIONES/dashboard_validaciones.php');
require_once('../LOGICA/dashboard_logica.php');
require_once('../LOGICA/ppto_schema_logica.php');

if (!isset($Ses_Dat_Dis) && isset($_SESSION['Ses_Dat_Dis'])) {
    $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
}

$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$mysqli_conn = $obBD_conexion->conexion;

if ($mysqli_conn) {
    ppto_schema_ensure($mysqli_conn);
}

// Función de respuesta JSON unificada que limpia el búfer de salida antes de enviar los datos
function ppto_ajax_responder_json($data) {
    if (class_exists('DebugBar', false) && method_exists('DebugBar', 'setDebugBar')) {
        DebugBar::setDebugBar(null);
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data);
    exit();
}

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

if ($action === 'catalogos') {
    $Emp_Cod = isset($_GET['Emp_Cod']) ? (int)$_GET['Emp_Cod'] : 1;
    $anio   = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

    // 1. Proyectos activos de la empresa
    $res_proy = $mysqli_conn->query("SELECT Pro_Cod AS proy_id, Pro_Nom AS proy_nombre FROM pre_proyectos WHERE Emp_Cod = $Emp_Cod AND Pro_Est = 'A' ORDER BY Pro_Nom");
    $proyectos = array();
    if ($res_proy) {
        while ($row = $res_proy->fetch_assoc()) {
            $proyectos[] = $row;
        }
    }

    // 2. Versiones presupuestarias para ese año y empresa
    $res_ver = $mysqli_conn->query("SELECT Ppe_Cod AS ppe_id, Ppe_Ver AS ppe_version, Ppe_Des AS ppe_descripcion, Ppe_Est AS ppe_estado FROM pre_presupuesto WHERE Emp_Cod = $Emp_Cod AND Ppe_Ani = $anio ORDER BY Ppe_Ver DESC");
    $versiones = array();
    if ($res_ver) {
        while ($row = $res_ver->fetch_assoc()) {
            $versiones[] = $row;
        }
    }

    ppto_ajax_responder_json(array(
        'status' => 'success',
        'proyectos' => $proyectos,
        'versiones' => $versiones
    ));
}

if ($action === 'fetch') {
    // Validar y limpiar filtros de entrada
    $filtros = ppto_dashboard_validar_filtros($_REQUEST);

    try {
        // Ejecutar consultas de KPIs, desglose por partidas y evolución mensual
        $kpis     = ppto_dash_kpis($mysqli_conn, $filtros);
        $partidas = ppto_dash_resumen_partidas($mysqli_conn, $filtros);
        $mensual  = ppto_dash_evolucion_mensual($mysqli_conn, $filtros);

        $alertas_d8 = array();
        $alertas_formalizar = array();
        $modo_reinversion = false;
        $reinversion_totales = null;

        if ($filtros['ppe_id'] && $filtros['proy_id'] !== null && trim($filtros['proy_id']) !== '') {
            $modo_reinversion = ppto_proy_es_modo_reinversion(
                $mysqli_conn,
                $filtros['proy_id'],
                (int)$filtros['Emp_Cod']
            );

            if ($modo_reinversion) {
                $alertas_formalizar = ppto_alerta_reinversion_procesar_partidas(
                    $mysqli_conn,
                    (int)$filtros['Emp_Cod'],
                    $partidas,
                    (int)$filtros['ppe_id'],
                    $filtros['proy_id'],
                    isset($kpis['periodo_mes']) ? $kpis['periodo_mes'] : $filtros['mes']
                );
                $partidas = ppto_alerta_reinversion_marcar_partidas($partidas, $alertas_formalizar);
                $reinversion_totales = ppto_reinversion_totales_partidas($partidas);
                $comp = (float)$kpis['comprometido'];
                $eje = (float)$kpis['ejecutado'];
                $costo = $comp + $eje;

                if (!empty($reinversion_totales['sobrepasa_tope'])) {
                    $alertas_d8[] = array(
                        'nivel' => 'rojo',
                        'tipo' => 'modo_reinversion_tope',
                        'mensaje' => 'Reinversion excede tope autorizable (' . number_format($reinversion_totales['exceso_monto'], 2) . ' USD de exceso sobre tope general de ' . number_format($reinversion_totales['tope_monto'], 2) . ' USD).'
                    );
                }
                if ($reinversion_totales['formalizar_monto'] > 0) {
                    $alertas_d8[] = array(
                        'nivel' => 'amarillo',
                        'tipo' => 'modo_reinversion_formalizar',
                        'mensaje' => 'Existen ' . count($alertas_formalizar) . ' partida(s) con requerimiento de formalizar por un total de ' . number_format($reinversion_totales['formalizar_monto'], 2) . ' USD.'
                    );
                }
                if ($reinversion_totales['vigente_monto'] > 0) {
                    $porc_comp = round(($costo / $reinversion_totales['vigente_monto']) * 100, 2);
                    if ($porc_comp >= 100.0) {
                        $alertas_d8[] = array(
                            'nivel' => 'rojo',
                            'tipo' => 'reinversion_porcentaje_monto',
                            'mensaje' => 'Costo acumulado (' . number_format($costo, 2) . ' USD) alcanzo o supero el presupuesto vigente (' . number_format($reinversion_totales['vigente_monto'], 2) . ' USD).'
                        );
                    } elseif ($porc_comp >= 80.0) {
                        $alertas_d8[] = array(
                            'nivel' => 'amarillo',
                            'tipo' => 'reinversion_porcentaje_monto',
                            'mensaje' => 'Costo acumulado alcanza el ' . $porc_comp . '% del presupuesto vigente en modo reinversion.'
                        );
                    }
                }
            }
        }

        if (!$modo_reinversion) {
            $alertas_d8 = ppto_alerta_evaluar_resumen(
                $mysqli_conn,
                (int)$filtros['Emp_Cod'],
                $partidas,
                $filtros['ppe_id'] ? (int)$filtros['ppe_id'] : null,
                $filtros['proy_id']
            );
        }

        ppto_ajax_responder_json(array(
            'status' => 'success',
            'filtros_aplicados' => $filtros,
            'kpis'     => $kpis,
            'partidas' => $partidas,
            'mensual'  => $mensual,
            'alertas_d8' => $alertas_d8,
            'modo_reinversion' => $modo_reinversion,
            'reinversion_totales' => $reinversion_totales,
        ));
    } catch (Exception $e) {
        ppto_ajax_responder_json(array(
            'status'  => 'error',
            'message' => $e->getMessage()
        ));
    }
}

// Si la accion recibida no coincide con ninguna ruta valida
ppto_ajax_responder_json(array(
    'status'  => 'error',
    'message' => 'Accion no valida.'
));
