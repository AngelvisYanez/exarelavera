<?php

/**
 * EXA Adquisiciones - Listado general de solicitudes
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');
require_once('../LOGICA/adq_adquisiciones_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$obBD_adq = new adq_adquisiciones_log($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);

$ajax_get_solicitud_flow = isset($_GET['ajax_get_solicitud_flow']) ? $_GET['ajax_get_solicitud_flow'] : null;

if (!$wf_mgr->verificarAccesoVentana('bandeja')) {
    if (isset($ajax_get_solicitud_flow)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado.'));
        exit;
    }
    echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
    exit;
}

function adqListaEsc($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function adqListaEtiquetaAccion($h) {
    if (!empty($h['Fin_Pendiente'])) {
        return array('Pendiente cierre', 'info', 'active');
    }
    if (!empty($h['Pendiente_Aprobacion']) || (isset($h['Isn_Acc']) && $h['Isn_Acc'] === 'PENDIENTE')) {
        $txt = 'Pendiente de aprobacion';
        if (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'TAREA') {
            $txt = 'Tarea pendiente';
        } elseif (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'FIN') {
            $txt = 'Pendiente cierre';
        } elseif (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'AVANCE') {
            $txt = 'Pendiente de avance';
        } elseif (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'FISCALIZACION') {
            $txt = 'Pendiente de fiscalizaci&oacute;n';
        }
        return array($txt, 'primary', 'active');
    }
    $acc = isset($h['Isn_Acc']) ? $h['Isn_Acc'] : '';
    $map = array(
        'CREAR' => array('Inicio pedido', 'secondary', 'active'),
        'APROBAR' => array('Aprobado', 'success', 'success'),
        'COMPLETAR' => array('Tarea completada', 'success', 'success'),
        'OBSERVAR' => array('Observado', 'warning', 'warning'),
        'DEVOLVER' => array('Devuelto', 'secondary', 'active'),
        'RECHAZAR' => array('Rechazado', 'danger', 'danger'),
        'REENVIAR' => array('Reenvio correccion', 'info', 'active'),
        'AVANCE' => array('Documentos cargados', 'info', 'active'),
        'FISCALIZACION' => array('Fiscalizaci&oacute;n', 'secondary', 'active'),
        'COTIZAR' => array('Proformas cargadas', 'primary', 'active')
    );
    if (isset($map[$acc])) {
        return $map[$acc];
    }
    return array($acc !== '' ? $acc : 'Movimiento', 'secondary', 'active');
}

function adqListaConstruirDetalleNodos($wf_mgr, $obBD_adq, $obBD_con1, $obBD_conexion, $sol_cod, $ins_cod, $flow_visual) {
    $sol_cod = intval($sol_cod);
    $ins_cod = intval($ins_cod);
    $nodos_detalle = array();
    if ($sol_cod <= 0 || $ins_cod <= 0) {
        return $nodos_detalle;
    }

    $inst = $obBD_con1->getRowConsultaSql(
        "SELECT Ins_Est, Nod_Act FROM wf_instancias WHERE Ins_Cod = $ins_cod LIMIT 1;",
        $obBD_conexion
    );
    $historial = $obBD_con1->getArrayConsultaSql("
        SELECT h.*,
               COALESCE(n.Nod_Nom, CONCAT('Nodo #', h.Nod_Cod)) AS Nod_Nom,
               COALESCE(n.Nod_Tip, 'PASO') AS Nod_Tip,
               TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Usuario_Nom,
               p.Prs_Nom, p.Prs_Ape,
               d.Wde_Des AS Dep_Des
        FROM wf_instancias_nodos h
        LEFT JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
        LEFT JOIN usuarios u ON u.Usu_Cod = h.Usu_Cod
        LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
        LEFT JOIN wf_departamentos d ON d.Dep_Cod = h.Dep_Cod
        WHERE h.Ins_Cod = $ins_cod
        ORDER BY h.Isn_Fec ASC, h.Isn_Cod ASC;", $obBD_conexion);
    if ($historial === false || $historial === null) {
        $historial = array();
    }

    $historial = $wf_mgr->normalizarHistorialFirmas(
        $historial,
        isset($inst['Ins_Est']) ? $inst['Ins_Est'] : '',
        isset($inst['Nod_Act']) ? intval($inst['Nod_Act']) : 0
    );
    $historial = $wf_mgr->agregarNodoPendienteHistorial(
        $historial,
        isset($inst['Ins_Est']) ? $inst['Ins_Est'] : '',
        isset($inst['Nod_Act']) ? intval($inst['Nod_Act']) : 0,
        0
    );
    $historial = $wf_mgr->agregarRechazoHistorialSiFalta(
        $historial,
        $ins_cod,
        '',
        isset($inst['Ins_Est']) ? $inst['Ins_Est'] : ''
    );
    $historial = $obBD_adq->enriquecerHistorialConArchivos($historial, $sol_cod);

    $orden_nodo = array();
    $idx = 0;
    if (!empty($flow_visual['nodos']) && is_array($flow_visual['nodos'])) {
        foreach ($flow_visual['nodos'] as $node) {
            $nid = intval($node['id']);
            if ($nid <= 0) {
                continue;
            }
            $idx++;
            $orden_nodo[$nid] = $idx;
            $nodos_detalle[(string)$nid] = array(
                'orden' => $idx,
                'nombre' => isset($node['nombre']) ? $node['nombre'] : ('Nodo #' . $nid),
                'tipo' => isset($node['tipo']) ? $node['tipo'] : 'PASO',
                'movimientos' => array()
            );
        }
    }

    foreach ($historial as $h) {
        $nid = intval(isset($h['Etapa_Nod_Cod']) ? $h['Etapa_Nod_Cod'] : (isset($h['Nod_Cod']) ? $h['Nod_Cod'] : 0));
        if ($nid <= 0) {
            continue;
        }
        $key = (string)$nid;
        if (!isset($nodos_detalle[$key])) {
            $orden = isset($orden_nodo[$nid]) ? $orden_nodo[$nid] : $nid;
            $nodos_detalle[$key] = array(
                'orden' => $orden,
                'nombre' => isset($h['Nod_Nom']) ? $h['Nod_Nom'] : ('Nodo #' . $nid),
                'tipo' => isset($h['Nod_Tip']) ? $h['Nod_Tip'] : 'PASO',
                'movimientos' => array()
            );
        }
        list($acc_lbl, $acc_badge, $acc_class) = adqListaEtiquetaAccion($h);
        $actor = !empty($h['Actor_Nom']) ? $h['Actor_Nom'] : (!empty($h['Usuario_Nom']) ? $h['Usuario_Nom'] : (!empty($h['Dep_Des']) ? $h['Dep_Des'] : 'Sistema'));
        $archivos = array();
        if (!empty($h['archivos']) && is_array($h['archivos'])) {
            foreach ($h['archivos'] as $arch) {
                if (empty($arch['path'])) {
                    continue;
                }
                $archivos[] = array(
                    'path' => $arch['path'],
                    'label' => !empty($arch['label']) ? $arch['label'] : 'Archivo',
                    'es_expediente' => !empty($arch['es_expediente']) ? 1 : 0,
                    'es_expediente_firmado' => !empty($arch['es_expediente_firmado']) ? 1 : 0
                );
            }
        }
        $facturas = array();
        if (!empty($h['facturas']) && is_array($h['facturas'])) {
            foreach ($h['facturas'] as $f) {
                $facturas[] = array(
                    'numero' => isset($f['numero']) ? $f['numero'] : '',
                    'proveedor' => isset($f['proveedor']) ? $f['proveedor'] : '',
                    'fecha' => isset($f['fecha']) ? $f['fecha'] : '',
                    'total' => isset($f['total']) ? $f['total'] : 0,
                    'link' => isset($f['link']) ? $f['link'] : '',
                    'des' => isset($f['des']) ? $f['des'] : '',
                    'comprobantes' => !empty($f['comprobantes']) && is_array($f['comprobantes']) ? $f['comprobantes'] : array()
                );
            }
        }
        $fec = !empty($h['Isn_Fec']) ? $h['Isn_Fec'] : '';
        $fec_fmt = $fec !== '' ? date('d/m/Y H:i', strtotime($fec)) : 'Sin movimiento';
        $nodos_detalle[$key]['movimientos'][] = array(
            'accion' => isset($h['Isn_Acc']) ? $h['Isn_Acc'] : '',
            'accion_label' => $acc_lbl,
            'badge' => $acc_badge,
            'item_class' => $acc_class,
            'actor' => $actor,
            'actor_modo' => !empty($h['Actor_Modo']) ? $h['Actor_Modo'] : 'Por',
            'fecha' => $fec_fmt,
            'comentario' => isset($h['Isn_Com']) ? $h['Isn_Com'] : '',
            'archivos' => $archivos,
            'facturas' => $facturas
        );
    }

    return $nodos_detalle;
}

if (isset($ajax_get_solicitud_flow)) {
    $sol_cod = intval($_GET['sol_cod']);
    if ($sol_cod <= 0) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Codigo de solicitud invalido.'));
        exit;
    }

    $sol = $obBD_con1->getRowConsultaSql("
        SELECT s.*, tr.Trq_Des,
               IFNULL(wfm.Wfm_Nom, 'Sin flujo') AS Wfm_Nom,
               CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Solicitante_Nom,
               d.Dep_Des,
               i.Ins_Cod, n.Nod_Nom AS Etapa_Actual
        FROM adq_solicitudes s
        INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
        INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
        INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
        LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
        LEFT JOIN wf_instancias i ON i.Ins_Cod = (
            SELECT MAX(i2.Ins_Cod)
            FROM wf_instancias i2
            WHERE i2.Ins_Ent_Typ = 'adq_solicitudes' AND i2.Ins_Ent_Cod = s.Sol_Cod
        )
        LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
        LEFT JOIN wf_flujos_modelos wfm ON wfm.Wfm_Cod = COALESCE(i.Wfm_Cod, tr.Wfm_Cod)
        WHERE s.Sol_Cod = $sol_cod AND s.Emp_Cod = " . intval($Ses_Emp_Cod) . "
        LIMIT 1;", $obBD_conexion);

    if (empty($sol)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'No se encontro la solicitud.'));
        exit;
    }

    list($est_label, $est_badge) = adqListaEtiquetaEstado($sol['Sol_Est']);
    $sol['Estado_Label'] = $est_label;
    $sol['Estado_Badge'] = $est_badge;

    $flow_visual = array('nodos' => array());
    if (!empty($sol['Ins_Cod'])) {
        $flow_visual = $wf_mgr->getVisualFlowData(intval($sol['Ins_Cod']));
    }

    $nodos_detalle = adqListaConstruirDetalleNodos(
        $wf_mgr,
        $obBD_adq,
        $obBD_con1,
        $obBD_conexion,
        $sol_cod,
        !empty($sol['Ins_Cod']) ? intval($sol['Ins_Cod']) : 0,
        $flow_visual
    );

    $obBD_con1->echoJson(array(
        'success' => true,
        'solicitud' => $sol,
        'flow_visual' => $flow_visual,
        'nodos_detalle' => $nodos_detalle
    ));
    exit;
}

$emp_cod = intval($Ses_Emp_Cod);

$todas_solicitudes = $obBD_con1->getArrayConsultaSql("
    SELECT s.*, tr.Trq_Des,
           IFNULL(wfm.Wfm_Nom, 'Sin flujo') AS Wfm_Nom,
           COALESCE(wfm.Wfm_Fam_Cod, wfm.Wfm_Cod, tr.Wfm_Cod) AS Wfm_Fam_Cod,
           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Solicitante_Nom,
           d.Dep_Des,
           i.Ins_Cod, i.Ins_Est AS Ins_Est_Act,
           n.Nod_Nom AS Etapa_Actual
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    LEFT JOIN wf_instancias i ON i.Ins_Cod = (
        SELECT MAX(i2.Ins_Cod)
        FROM wf_instancias i2
        WHERE i2.Ins_Ent_Typ = 'adq_solicitudes' AND i2.Ins_Ent_Cod = s.Sol_Cod
    )
    LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    LEFT JOIN wf_flujos_modelos wfm ON wfm.Wfm_Cod = COALESCE(i.Wfm_Cod, tr.Wfm_Cod)
    WHERE s.Emp_Cod = $emp_cod
    ORDER BY s.Sol_Fec DESC
    LIMIT 200;", $obBD_conexion);
if ($todas_solicitudes === false || $todas_solicitudes === null) {
    $todas_solicitudes = array();
}

$flujos_opciones = array();
foreach ($wf_mgr->listarFlujosPublicados($emp_cod) as $f) {
    $fam = !empty($f['Wfm_Fam_Cod']) ? intval($f['Wfm_Fam_Cod']) : intval($f['Wfm_Cod']);
    $flujos_opciones[$fam] = $f['Wfm_Nom'];
}
foreach ($todas_solicitudes as $row) {
    if (!empty($row['Wfm_Fam_Cod']) && !empty($row['Wfm_Nom'])) {
        $flujos_opciones[intval($row['Wfm_Fam_Cod'])] = $row['Wfm_Nom'];
    }
}
asort($flujos_opciones, SORT_NATURAL | SORT_FLAG_CASE);
$filtro_wfm_fam = isset($_GET['filtro_wfm']) ? intval($_GET['filtro_wfm']) : 0;
if (array_key_exists('filtro_estado', $_GET)) {
    $filtro_estado = trim($_GET['filtro_estado']);
} else {
    $filtro_estado = 'E';
}
$estados_opciones = array(
    'P' => 'Borrador',
    'E' => 'En proceso',
    'A' => 'Aprobada',
    'R' => 'Rechazada',
    'O' => 'Observada',
);
if ($filtro_estado !== '' && !isset($estados_opciones[$filtro_estado])) {
    $filtro_estado = '';
}

$filtro_fec_desde = isset($_GET['filtro_fec_desde']) ? trim($_GET['filtro_fec_desde']) : '';
$filtro_fec_hasta = isset($_GET['filtro_fec_hasta']) ? trim($_GET['filtro_fec_hasta']) : '';
if ($filtro_fec_desde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtro_fec_desde)) {
    $filtro_fec_desde = '';
}
if ($filtro_fec_hasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtro_fec_hasta)) {
    $filtro_fec_hasta = '';
}

$stats_estado = array('P' => 0, 'E' => 0, 'A' => 0, 'R' => 0, 'O' => 0);
foreach ($todas_solicitudes as $row_stat) {
    $cod = isset($row_stat['Sol_Est']) ? $row_stat['Sol_Est'] : '';
    if (isset($stats_estado[$cod])) {
        $stats_estado[$cod]++;
    }
}

function adqListaEtiquetaEstado($sol_est)
{
    switch ($sol_est) {
        case 'P':
            return array('Borrador', 'secondary');
        case 'E':
            return array('En proceso', 'primary');
        case 'A':
            return array('Aprobada', 'success');
        case 'R':
            return array('Rechazada', 'danger');
        case 'O':
            return array('Observada', 'warning text-dark');
        default:
            return array('Desconocido', 'secondary');
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">

<head>
    <meta charset="UTF-8">
    <title>Todas las Solicitudes - Adquisiciones</title>
    <?php require_once('adq_model3_assets.php'); ?>
    <style>
        .adq-lista-page.exa-ui-panel>.panel-heading {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            border-color: #1e40af;
        }

        .adq-lista-page.exa-ui-panel>.panel-heading .panel-title {
            font-size: 18px;
            color: #ffffff;
        }

        .adq-lista-page.exa-ui-panel>.panel-heading .panel-title i {
            margin-right: 8px;
        }

        .adq-lista-page .exa-header-flex .btn-default {
            background: rgba(255, 255, 255, 0.95);
            border-color: transparent;
            color: #1e3a8a;
            font-weight: 600;
        }

        .adq-lista-page .exa-header-flex .btn-default:hover {
            background: #ffffff;
            color: #1d4ed8;
        }

        .adq-lista-intro {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .adq-lista-kpis {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .adq-lista-kpi {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
        }

        .adq-lista-kpi:hover {
            border-color: #93c5fd;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.1);
            transform: translateY(-1px);
        }

        .adq-lista-kpi.active {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
            background: #eff6ff;
        }

        .adq-lista-kpi .kpi-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 4px;
        }

        .adq-lista-kpi .kpi-value {
            font-size: 22px;
            font-weight: 800;
            line-height: 1.1;
            color: #0f172a;
        }

        .adq-lista-kpi.kpi-P .kpi-value {
            color: #64748b;
        }

        .adq-lista-kpi.kpi-E .kpi-value {
            color: #2563eb;
        }

        .adq-lista-kpi.kpi-A .kpi-value {
            color: #059669;
        }

        .adq-lista-kpi.kpi-R .kpi-value {
            color: #dc2626;
        }

        .adq-lista-kpi.kpi-O .kpi-value {
            color: #d97706;
        }

        .adq-lista-filter-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .adq-lista-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: nowrap;
        }

        .adq-lista-filters {
            display: flex;
            align-items: center;
            gap: 12px 20px;
            flex-wrap: nowrap;
            flex: 1 1 auto;
            min-width: 0;
        }

        .adq-lista-filter-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 0 1 auto;
            min-width: 0;
            white-space: nowrap;
        }

        .adq-lista-filters label {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            white-space: nowrap;
        }

        .adq-lista-filters label i {
            color: #2563eb;
            margin-right: 2px;
        }

        .adq-lista-filters select {
            width: 200px;
            max-width: 200px;
            min-width: 140px;
            font-size: 13px;
            height: 34px;
            border-radius: 8px;
            border-color: #cbd5e1;
        }

        .adq-lista-filters input[type="date"] {
            width: 150px;
            max-width: 150px;
            min-width: 130px;
            font-size: 13px;
            height: 34px;
            border-radius: 8px;
            border-color: #cbd5e1;
        }

        .adq-lista-filters select:focus,
        .adq-lista-filters input[type="date"]:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        #lblTotalSolicitudes {
            flex: 0 0 auto;
            white-space: nowrap;
            font-size: 13px;
            padding: 8px 12px;
            border-radius: 8px;
            background: #1e3a8a !important;
        }

        .adq-lista-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }

        .adq-lista-page .exa-adq-table-wrap {
            margin: 0;
            border: none;
        }

        .adq-lista-page .exa-adq-table {
            margin-bottom: 0;
        }

        .adq-lista-page .exa-adq-table>thead>tr>th {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #475569;
            padding: 12px 14px !important;
            background: #f1f5f9;
            border-bottom: 2px solid #cbd5e1 !important;
            vertical-align: middle;
        }

        .adq-lista-page .exa-adq-table>tbody>tr>td {
            font-size: 13px;
            padding: 11px 14px !important;
            vertical-align: middle;
            border-color: #f1f5f9 !important;
        }

        .adq-lista-page .exa-adq-table>tbody>tr.adq-row-solicitud:hover {
            background-color: #f8fafc;
        }

        .adq-lista-page .exa-adq-table>tbody>tr.adq-row-solicitud:nth-child(even) {
            background-color: #fcfdfe;
        }

        .adq-lista-page .adq-sol-num {
            display: inline-block;
            min-width: 52px;
            padding: 4px 10px;
            border-radius: 6px;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 800;
            font-size: 13px;
        }

        .adq-lista-page .adq-flujo-nom {
            font-weight: 600;
            color: #1e293b;
        }

        .adq-lista-page .adq-solicitante {
            font-weight: 500;
            color: #334155;
        }

        .adq-lista-page .adq-fecha {
            font-size: 12px;
            color: #64748b;
            white-space: nowrap;
        }

        .adq-lista-page .exa-adq-table .badge {
            font-size: 11px;
            padding: 5px 9px;
            font-weight: 700;
            border-radius: 6px;
        }

        .adq-lista-page .adq-col-acciones {
            white-space: nowrap;
            vertical-align: middle !important;
        }

        .adq-lista-page .adq-acciones-row {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .adq-lista-page .adq-btn-ver-flujo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            padding: 0;
            border-radius: 8px;
            border: 1px solid #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
            transition: all 0.2s;
        }

        .adq-lista-page .adq-btn-ver-flujo:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        .adq-lista-page .adq-btn-ver-flujo i {
            font-size: 17px;
        }

        .adq-lista-empty {
            padding: 48px 20px !important;
        }

        .adq-lista-empty i {
            font-size: 36px;
            color: #cbd5e1;
            display: block;
            margin-bottom: 10px;
        }

        @media (max-width: 1100px) {
            .adq-lista-kpis {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .adq-lista-toolbar {
                flex-wrap: wrap;
            }

            .adq-lista-filters {
                flex-wrap: wrap;
            }

            .adq-lista-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        #mdlFlujoSolicitud .modal-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #ffffff;
            border-radius: 6px 6px 0 0;
        }

        #mdlFlujoSolicitud .modal-header .close {
            color: #ffffff;
            opacity: 0.85;
        }

        #mdlFlujoSolicitud .modal-title {
            font-weight: 700;
        }

        #mdlFlujoSolicitud .modal-body {
            padding: 20px 22px;
            background: #f8fafc;
        }

        #mdlFlujoResumen {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 16px;
            font-size: 13px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        #mdlFlujoResumen .adq-kv-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px 16px;
            margin-bottom: 0;
        }

        #mdlFlujoResumen .adq-kv-item {
            padding: 8px 10px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
        }

        #mdlFlujoResumen .adq-kv-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 3px;
        }

        #mdlFlujoResumen .adq-kv-value {
            font-weight: 600;
            color: #0f172a;
        }

        #mdlFlujoTrackerWrap {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px 16px;
            overflow-x: auto;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        #mdlFlujoTrackerWrap .tracker-wrapper {
            justify-content: flex-start;
            min-height: 120px;
            gap: 14px;
        }

        #mdlFlujoTrackerWrap .tracker-node {
            min-width: 170px;
            max-width: 230px;
            font-size: 14px;
            padding: 12px 14px;
            border-width: 3px;
            border-radius: 14px;
        }

        #mdlFlujoTrackerWrap .tracker-node>i.bi-circle-fill {
            font-size: 13px;
        }

        #mdlFlujoTrackerWrap .tracker-node .tracker-node-tipo {
            display: block;
            font-size: 11px;
            font-weight: normal;
            opacity: 0.85;
            margin-top: 2px;
        }

        #mdlFlujoTrackerWrap .tracker-actor {
            font-size: 11px;
            margin-top: 6px;
        }

        #mdlFlujoTrackerWrap .tracker-arrow {
            font-size: 26px;
        }

        #mdlFlujoTrackerWrap .tracker-node-clickable {
            cursor: pointer;
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }

        #mdlFlujoTrackerWrap .tracker-node-clickable:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.14);
        }

        #mdlFlujoTrackerWrap .tracker-node-selected {
            outline: 2px solid #1e3a8a;
            outline-offset: 2px;
            box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.15);
        }

        #mdlFlujoTrackerHint {
            margin-top: 10px;
            font-size: 12px;
            color: #64748b;
        }

        #mdlSegNodoDetalle {
            z-index: 1060;
        }

        #mdlSegNodoDetalle .modal-dialog {
            margin-top: 60px;
        }

        #mdlSegNodoDetalle .adq-lista-nodo-header {
            background: linear-gradient(180deg, #5f7ea3 0%, #4b678a 100%);
            color: #ffffff;
            border-bottom: 1px solid #3a516e;
        }

        #mdlSegNodoDetalle .adq-lista-nodo-header .modal-title {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
        }

        #mdlSegNodoDetalle .adq-lista-nodo-header .text-muted {
            color: #dbeafe !important;
        }

        #mdlSegNodoDetalle .adq-lista-nodo-header .close {
            color: #ffffff;
            opacity: 0.85;
            text-shadow: none;
        }

        #mdlSegNodoDetalle .modal-body {
            background: #f8fafc;
            padding: 14px 16px;
            max-height: 65vh;
            overflow-y: auto;
        }

        body.modal-open .modal-backdrop.adq-lista-nodo-backdrop {
            z-index: 1055;
        }

        .adq-lista-mov {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #94a3b8;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 10px;
        }

        .adq-lista-mov.is-success { border-left-color: #059669; }
        .adq-lista-mov.is-danger { border-left-color: #dc2626; }
        .adq-lista-mov.is-warning { border-left-color: #d97706; }
        .adq-lista-mov.is-active { border-left-color: #2563eb; }

        .adq-lista-mov-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }

        .adq-lista-mov-title {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .adq-lista-mov-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 24px;
            padding: 0 6px;
            border-radius: 6px;
            background: #4b678a;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        .adq-lista-mov-fecha {
            font-size: 11px;
            color: #64748b;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 2px 8px;
        }

        .adq-lista-mov-actor {
            font-size: 12px;
            color: #334155;
            margin-bottom: 6px;
        }

        .adq-lista-mov-actor strong {
            color: #0f172a;
        }

        .adq-lista-mov-com {
            font-size: 12px;
            color: #475569;
            background: #f8fafc;
            border-left: 3px solid #94a3b8;
            padding: 8px 10px;
            margin: 6px 0;
            border-radius: 0 6px 6px 0;
        }

        .adq-lista-archivos {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .adq-lista-archivos .btn {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 6px;
        }
    </style>
</head>

<body class="exa-ui-fill-root">
    <div class="panel panel-main exa-ui-panel exa-ui-fill-page adq-lista-page">
        <div class="panel-heading exa-header exa-header-flex">
            <h3 class="panel-title"><i class="bi bi-collection"></i> Todas las Solicitudes</h3>
            <a href="adq_bandeja.php" class="btn btn-default btn-sm"><i class="bi bi-inboxes"></i> Volver a Bandeja</a>
        </div>
        <div class="panel-body exa-body">
            <div class="exa-ui-page-view">
                <p class="adq-lista-intro">Consulte todas las solicitudes de adquisicion. Use los filtros o las tarjetas de estado para acotar la busqueda.</p>

                <div class="adq-lista-kpis">
                    <?php foreach ($estados_opciones as $cod => $label) { ?>
                        <div class="adq-lista-kpi kpi-<?php echo htmlspecialchars($cod); ?><?php echo ($filtro_estado === $cod) ? ' active' : ''; ?>" data-estado="<?php echo htmlspecialchars($cod); ?>" title="Filtrar por <?php echo htmlspecialchars($label); ?>">
                            <span class="kpi-label"><?php echo htmlspecialchars($label); ?></span>
                            <span class="kpi-value"><?php echo intval($stats_estado[$cod]); ?></span>
                        </div>
                    <?php } ?>
                </div>

                <div class="adq-lista-filter-card">
                    <div class="adq-lista-toolbar">
                        <div class="adq-lista-filters">
                            <div class="adq-lista-filter-item">
                                <label for="filtroFlujo"><i class="bi bi-diagram-3"></i> Flujo</label>
                                <select id="filtroFlujo" class="form-control input-sm">
                                    <option value="">Todos</option>
                                    <?php foreach ($flujos_opciones as $fam_cod => $wfm_nom) { ?>
                                        <option value="<?php echo intval($fam_cod); ?>" <?php echo ($filtro_wfm_fam === intval($fam_cod)) ? ' selected' : ''; ?>><?php echo htmlspecialchars($wfm_nom); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="adq-lista-filter-item">
                                <label for="filtroEstado"><i class="bi bi-flag"></i> Estado</label>
                                <select id="filtroEstado" class="form-control input-sm">
                                    <option value="">Todos</option>
                                    <?php foreach ($estados_opciones as $cod => $label) { ?>
                                        <option value="<?php echo htmlspecialchars($cod); ?>" <?php echo ($filtro_estado === $cod) ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="adq-lista-filter-item">
                                <label for="filtroFecDesde"><i class="bi bi-calendar-event"></i> Desde</label>
                                <input type="date" id="filtroFecDesde" class="form-control input-sm" value="<?php echo htmlspecialchars($filtro_fec_desde); ?>">
                            </div>
                            <div class="adq-lista-filter-item">
                                <label for="filtroFecHasta"><i class="bi bi-calendar-check"></i> Hasta</label>
                                <input type="date" id="filtroFecHasta" class="form-control input-sm" value="<?php echo htmlspecialchars($filtro_fec_hasta); ?>">
                            </div>
                            <button type="button" class="btn btn-default btn-sm" id="btnLimpiarFiltros" title="Quitar filtros"><i class="bi bi-x-circle"></i> Limpiar</button>
                        </div>
                        <span class="badge bg-secondary" id="lblTotalSolicitudes"><?php echo count($todas_solicitudes); ?> solicitudes</span>
                    </div>
                </div>

                <div class="adq-lista-table-card">
                    <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table">
                            <thead>
                                <tr>
                                    <th style="width: 90px;">N&ordm; Sol.</th>
                                    <th>Flujo</th>
                                    <th style="width: 140px;">Fecha</th>
                                    <th>Solicitante</th>
                                    <th>Tipo Pedido</th>
                                    <th style="width: 90px;">Prioridad</th>
                                    <th style="width: 110px;">Estado</th>
                                    <th style="width: 80px;">Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($todas_solicitudes)) { ?>
                                    <tr class="text-center">
                                        <td colspan="8" class="text-muted adq-lista-empty"><i class="bi bi-inbox"></i> No hay solicitudes registradas.</td>
                                    </tr>
                                    <?php } else {
                                    foreach ($todas_solicitudes as $t) {
                                        list($est, $badge) = adqListaEtiquetaEstado($t['Sol_Est']);
                                    ?>
                                        <tr class="text-center adq-row-solicitud" data-wfm-fam="<?php echo intval($t['Wfm_Fam_Cod']); ?>" data-sol-est="<?php echo htmlspecialchars($t['Sol_Est']); ?>" data-sol-fecha="<?php echo date('Y-m-d', strtotime($t['Sol_Fec'])); ?>">
                                            <td><span class="adq-sol-num"><?php echo $t['Sol_Num']; ?></span></td>
                                            <td class="text-start adq-flujo-nom"><?php echo htmlspecialchars($t['Wfm_Nom']); ?></td>
                                            <td class="adq-fecha"><?php echo date('Y-m-d H:i', strtotime($t['Sol_Fec'])); ?></td>
                                            <td class="text-start adq-solicitante"><?php echo htmlspecialchars($t['Solicitante_Nom']); ?></td>
                                            <td class="text-start"><?php echo htmlspecialchars($t['Trq_Des']); ?></td>
                                            <td><span class="badge badge-<?php echo strtolower($t['Sol_Pri']); ?>"><?php echo $t['Sol_Pri']; ?></span></td>
                                            <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $est; ?></span></td>
                                            <td class="adq-col-acciones">
                                                <div class="adq-acciones-row">
                                                    <button type="button" class="btn btn-sm adq-btn-ver-flujo" title="Ver flujo del workflow" onclick="abrirModalFlujo(<?php echo intval($t['Sol_Cod']); ?>)"><i class="bi bi-eye"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                <?php }
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mdlFlujoSolicitud" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" style="width: 95%; max-width: 1200px;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="mdlFlujoTitle"><i class="bi bi-diagram-3"></i> Flujo del Workflow</h4>
                </div>
                <div class="modal-body">
                    <div id="mdlFlujoResumen"></div>
                    <label class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.04em;"><i class="bi bi-activity"></i> Progreso del Workflow</label>
                    <div id="mdlFlujoTrackerWrap">
                        <div id="mdlFlujoTracker" class="text-center text-muted py-3">
                            <i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Cargando flujo...
                        </div>
                    </div>
                    <div id="mdlFlujoTrackerHint" class="adq-lista-hint" style="display:none;"><i class="bi bi-hand-index-thumb"></i> Haga clic en un nodo para ver su historial y archivos.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mdlSegNodoDetalle" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" style="width:90%;max-width:900px;">
            <div class="modal-content">
                <div class="modal-header adq-lista-nodo-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">
                        <i class="bi bi-list-task"></i>
                        <span id="listaNodoTitulo">Detalle de etapa</span>
                        <small class="text-muted" id="listaNodoSub"></small>
                    </h4>
                </div>
                <div class="modal-body">
                    <div id="listaNodoBody"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal"><i class="bi bi-x-lg"></i> Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentNodosDetalle = {};

        function escHtmlLista(text) {
            return String(text == null ? '' : text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function adqUrlDocLista(path) {
            path = String(path || '').replace(/\\/g, '/').replace(/^\/+/, '');
            if (!path) return '';
            if (path.indexOf('documentos_flujo/') === 0) return '../' + path;
            if (path.indexOf('DATA/') === 0) return '../../' + path;
            if (path.indexOf('adquisiciones_sustentos/') === 0) return '../../DATA/' + path;
            return '../../DATA/' + path;
        }

        function renderTrackerHtml(flowVisual) {
            const flowNodos = (flowVisual && flowVisual.nodos) ? flowVisual.nodos : [];
            if (!flowNodos.length) {
                return '<span class="text-muted small">Sin workflow</span>';
            }
            let html = '<div class="tracker-wrapper adq-lista-flow-tracker">';
            flowNodos.forEach(function(node, index) {
                if (index > 0) {
                    html += '<div class="tracker-arrow"><i class="bi bi-arrow-right-short"></i></div>';
                }
                let actorLine = '';
                if (node.pendiente_meta) {
                    const pm = node.pendiente_meta;
                    let lines = ['<span><i class="bi bi-hourglass-split"></i> ' + (node.tipo === 'TAREA' ? 'Tarea pendiente' : 'Pendiente de aprobacion') + '</span>'];
                    if (pm.depto) lines.push('<span>Depto: ' + escHtmlLista(pm.depto) + '</span>');
                    if (pm.asignados) lines.push('<span>Asignado: ' + escHtmlLista(pm.asignados) + '</span>');
                    if (pm.enviado_por) lines.push('<span>Enviado por: ' + escHtmlLista(pm.enviado_por) + '</span>');
                    actorLine = '<br><span class="tracker-actor tracker-pendiente">' + lines.join('') + '</span>';
                } else if (node.actor_label) {
                    actorLine = '<br><span class="tracker-actor"><i class="bi bi-person-check"></i> ' + escHtmlLista(node.actor_label) + '</span>';
                }
                const orden = index + 1;
                html += '<div class="tracker-node tracker-node-clickable color-' + escHtmlLista(node.color) + '"'
                    + ' data-nod-id="' + parseInt(node.id, 10) + '"'
                    + ' data-nod-orden="' + orden + '"'
                    + ' data-nod-nom="' + escHtmlLista(node.nombre) + '"'
                    + ' data-nod-tip="' + escHtmlLista(node.tipo) + '"'
                    + ' title="Ver historial y archivos de esta etapa">'
                    + '<i class="bi bi-circle-fill"></i> <strong>' + orden + '.</strong> ' + escHtmlLista(node.nombre)
                    + '<span class="tracker-node-tipo">[' + escHtmlLista(node.tipo) + ']</span>'
                    + actorLine
                    + '</div>';
            });
            html += '</div>';
            return html;
        }

        function renderArchivosNodo(archivos) {
            if (!archivos || !archivos.length) {
                return '';
            }
            let html = '<div class="adq-lista-archivos">';
            archivos.forEach(function(a) {
                if (!a.path) return;
                const firmado = parseInt(a.es_expediente_firmado || 0, 10) === 1;
                const exp = firmado || parseInt(a.es_expediente || 0, 10) === 1;
                let btn = 'btn-outline-primary';
                let icon = 'bi-file-earmark-pdf';
                if (firmado) {
                    btn = 'btn-outline-success';
                    icon = 'bi-file-earmark-check';
                } else if (exp) {
                    btn = 'btn-outline-secondary';
                    icon = 'bi-file-earmark-lock2';
                }
                html += '<a href="' + adqUrlDocLista(a.path) + '" target="_blank" class="btn btn-xs ' + btn + '">'
                    + '<i class="bi ' + icon + '"></i> ' + escHtmlLista(a.label || 'Archivo')
                    + '</a>';
            });
            html += '</div>';
            return html;
        }

        function renderFacturasNodo(facturas) {
            if (!facturas || !facturas.length) {
                return '';
            }
            let html = '';
            facturas.forEach(function(f) {
                const numero = escHtmlLista(f.numero || '');
                const proveedor = escHtmlLista(f.proveedor || 'Proveedor');
                const pdf = f.link
                    ? '<a href="' + escHtmlLista(f.link) + '" target="_blank" class="btn btn-xs btn-outline-primary" style="margin-left:6px;"><i class="bi bi-file-earmark-pdf"></i> Ver PDF</a>'
                    : '';
                html += '<div class="border rounded p-2 mb-1 bg-white small" style="margin-top:6px;">'
                    + '<strong><i class="bi bi-receipt-cutoff"></i> Factura # ' + numero + '</strong> - ' + proveedor + pdf
                    + '</div>';
            });
            return html;
        }

        function renderDetalleNodoHtml(detalle, ordenFallback) {
            if (!detalle || !detalle.movimientos || !detalle.movimientos.length) {
                return '<div class="text-center text-muted py-3 small">No hay movimientos ni archivos registrados en esta etapa.</div>';
            }
            const ordenBase = parseInt(detalle.orden || ordenFallback || 0, 10) || 0;
            const total = detalle.movimientos.length;
            let html = '';
            detalle.movimientos.forEach(function(m, idx) {
                const num = total > 1 ? (ordenBase + '.' + (idx + 1)) : String(ordenBase || (idx + 1));
                const cls = m.item_class ? (' is-' + m.item_class) : '';
                html += '<div class="adq-lista-mov' + cls + '">'
                    + '<div class="adq-lista-mov-head">'
                    + '<div class="adq-lista-mov-title">'
                    + '<span class="adq-lista-mov-num">' + escHtmlLista(num) + '</span>'
                    + '<span class="badge bg-' + escHtmlLista(m.badge || 'secondary') + '">' + escHtmlLista(m.accion_label || m.accion || '') + '</span>'
                    + '<strong>' + escHtmlLista(detalle.nombre || 'Etapa') + '</strong>'
                    + '</div>'
                    + '<span class="adq-lista-mov-fecha"><i class="bi bi-calendar3"></i> ' + escHtmlLista(m.fecha || '') + '</span>'
                    + '</div>'
                    + '<div class="adq-lista-mov-actor">' + escHtmlLista(m.actor_modo || 'Por') + ': <strong>' + escHtmlLista(m.actor || 'Sistema') + '</strong></div>'
                    + (m.comentario ? ('<div class="adq-lista-mov-com">' + escHtmlLista(m.comentario) + '</div>') : '')
                    + renderFacturasNodo(m.facturas)
                    + renderArchivosNodo(m.archivos)
                    + '</div>';
            });
            return html;
        }

        function mostrarDetalleNodo(nodId, $nodeEl) {
            nodId = String(nodId);
            const detalle = currentNodosDetalle[nodId] || null;
            const orden = detalle && detalle.orden
                ? detalle.orden
                : ($nodeEl ? parseInt($nodeEl.data('nod-orden'), 10) : 0);
            const nom = (detalle && detalle.nombre)
                ? detalle.nombre
                : ($nodeEl ? $nodeEl.data('nod-nom') : 'Etapa');
            const tip = (detalle && detalle.tipo)
                ? detalle.tipo
                : ($nodeEl ? $nodeEl.data('nod-tip') : '');

            $('#mdlFlujoTrackerWrap .tracker-node-clickable').removeClass('tracker-node-selected');
            if ($nodeEl && $nodeEl.length) {
                $nodeEl.addClass('tracker-node-selected');
            }

            $('#listaNodoTitulo').text((orden ? (orden + '. ') : '') + nom);
            $('#listaNodoSub').text(tip ? (' [' + tip + ']') : '');
            $('#listaNodoBody').html(renderDetalleNodoHtml(detalle, orden));

            const $modal = $('#mdlSegNodoDetalle');
            $modal.off('shown.bs.modal.listaNodo hidden.bs.modal.listaNodo');
            $modal.on('shown.bs.modal.listaNodo', function() {
                $('.modal-backdrop').not('.adq-lista-nodo-backdrop').last().addClass('adq-lista-nodo-backdrop');
            });
            $modal.on('hidden.bs.modal.listaNodo', function() {
                $('#mdlFlujoTrackerWrap .tracker-node-clickable').removeClass('tracker-node-selected');
                $('body').addClass('modal-open');
            });
            $modal.modal('show');
        }

        function initNodosClickables() {
            $('#mdlFlujoTrackerWrap .tracker-node-clickable').off('click.listaNodo').on('click.listaNodo', function() {
                mostrarDetalleNodo($(this).data('nod-id'), $(this));
            });
        }

        function abrirModalFlujo(solCod) {
            currentNodosDetalle = {};
            $('#mdlSegNodoDetalle').modal('hide');
            $('#mdlFlujoResumen').empty();
            $('#mdlFlujoTrackerHint').hide();
            $('#mdlFlujoTracker').html('<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Cargando flujo...');
            $('#mdlFlujoSolicitud').modal('show');

            $.getJSON('adq_lista_solicitud.php', {
                ajax_get_solicitud_flow: 1,
                sol_cod: solCod
            }, function(res) {
                if (!res.success) {
                    $('#mdlFlujoTracker').html('<span class="text-danger">' + escHtmlLista(res.message || 'Error al cargar') + '</span>');
                    return;
                }
                const s = res.solicitud;
                currentNodosDetalle = res.nodos_detalle || {};
                $('#mdlFlujoTitle').html('<i class="bi bi-diagram-3"></i> Solicitud N&ordm; ' + escHtmlLista(s.Sol_Num) + ' &mdash; Flujo del Workflow');

                const fecha = s.Sol_Fec ? String(s.Sol_Fec).replace('T', ' ').substring(0, 16) : '';
                $('#mdlFlujoResumen').html(
                    '<div class="adq-kv-row">' +
                    '<div class="adq-kv-item"><span class="adq-kv-label">Solicitante</span><span class="adq-kv-value">' + escHtmlLista(s.Solicitante_Nom || 'N/D') + '</span></div>' +
                    '<div class="adq-kv-item"><span class="adq-kv-label">Flujo</span><span class="adq-kv-value">' + escHtmlLista(s.Wfm_Nom || 'Sin flujo') + '</span></div>' +
                    '<div class="adq-kv-item"><span class="adq-kv-label">Fecha</span><span class="adq-kv-value">' + escHtmlLista(fecha) + '</span></div>' +
                    '<div class="adq-kv-item"><span class="adq-kv-label">Tipo pedido</span><span class="adq-kv-value">' + escHtmlLista(s.Trq_Des || '') + '</span></div>' +
                    '<div class="adq-kv-item"><span class="adq-kv-label">Prioridad</span><span class="adq-kv-value">' + escHtmlLista(s.Sol_Pri || '') + '</span></div>' +
                    '<div class="adq-kv-item"><span class="adq-kv-label">Estado</span><span class="adq-kv-value"><span class="badge bg-' + escHtmlLista(s.Estado_Badge) + '">' + escHtmlLista(s.Estado_Label) + '</span></span></div>' +
                    (s.Etapa_Actual ? '<div class="adq-kv-item"><span class="adq-kv-label">Etapa actual</span><span class="adq-kv-value">' + escHtmlLista(s.Etapa_Actual) + '</span></div>' : '') +
                    '</div>'
                );

                $('#mdlFlujoTracker').html(renderTrackerHtml(res.flow_visual));
                if (res.flow_visual && res.flow_visual.nodos && res.flow_visual.nodos.length) {
                    $('#mdlFlujoTrackerHint').show();
                }
                initNodosClickables();
            }).fail(function() {
                $('#mdlFlujoTracker').html('<span class="text-danger">Error de red al cargar el flujo.</span>');
            });
        }

        function aplicarFiltros() {
            const fam = $('#filtroFlujo').val();
            const est = $('#filtroEstado').val();
            const fecDesde = $('#filtroFecDesde').val();
            const fecHasta = $('#filtroFecHasta').val();
            let visibles = 0;
            $('.adq-row-solicitud').each(function() {
                const rowFam = String($(this).data('wfm-fam') || '');
                const rowEst = String($(this).data('sol-est') || '');
                const rowFec = String($(this).data('sol-fecha') || '');
                const okFlujo = !fam || rowFam === fam;
                const okEstado = !est || rowEst === est;
                const okDesde = !fecDesde || (rowFec && rowFec >= fecDesde);
                const okHasta = !fecHasta || (rowFec && rowFec <= fecHasta);
                const mostrar = okFlujo && okEstado && okDesde && okHasta;
                $(this).toggle(mostrar);
                if (mostrar) {
                    visibles++;
                }
            });
            const total = $('.adq-row-solicitud').length;
            if (fam || est || fecDesde || fecHasta) {
                $('#lblTotalSolicitudes').text(visibles + ' de ' + total + ' solicitudes');
            } else {
                $('#lblTotalSolicitudes').text(total + ' solicitudes');
            }
            const params = new URLSearchParams(window.location.search);
            if (fam) {
                params.set('filtro_wfm', fam);
            } else {
                params.delete('filtro_wfm');
            }
            if (est) {
                params.set('filtro_estado', est);
            } else {
                params.set('filtro_estado', '');
            }
            if (fecDesde) {
                params.set('filtro_fec_desde', fecDesde);
            } else {
                params.delete('filtro_fec_desde');
            }
            if (fecHasta) {
                params.set('filtro_fec_hasta', fecHasta);
            } else {
                params.delete('filtro_fec_hasta');
            }
            const qs = params.toString();
            window.history.replaceState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
            actualizarKpisActivos();
        }

        function actualizarKpisActivos() {
            const est = $('#filtroEstado').val();
            $('.adq-lista-kpi').removeClass('active');
            if (est) {
                $('.adq-lista-kpi[data-estado="' + est + '"]').addClass('active');
            }
        }

        $(document).ready(function() {
            $('#filtroFlujo, #filtroEstado, #filtroFecDesde, #filtroFecHasta').on('change', aplicarFiltros);
            $('#btnLimpiarFiltros').on('click', function() {
                $('#filtroFlujo, #filtroEstado, #filtroFecDesde, #filtroFecHasta').val('');
                aplicarFiltros();
            });
            $('.adq-lista-kpi').on('click', function() {
                const est = $(this).data('estado');
                const actual = $('#filtroEstado').val();
                $('#filtroEstado').val(actual === est ? '' : est);
                aplicarFiltros();
            });
            aplicarFiltros();
        });
    </script>
</body>

</html>