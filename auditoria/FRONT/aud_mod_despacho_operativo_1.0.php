<?php
ob_start();
/**
 * Gestión Operativa del Despacho - Operativo
 * Dashboard KPI, Reglas de asignación, Reportes, Facturación
 * @author Sistema EXA | @version 1.0
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/aud_log_despacho_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Despacho($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Despacho();
$Ses_Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;
$Ses_Usu_Cod = isset($Ses_Usu_Cod) ? intval($Ses_Usu_Cod) : 0;

// Ajax: Listar personal (para filtro de usuario en tareas)
if (!empty($_REQUEST['listarPersonalOperativo'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $arr = $obBD_con1->getArrayConsulta(35, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    echo json_encode(array('rows' => is_array($arr) ? $arr : array()));
    exit;
}

// Ajax: Grid tareas (formato cliente x actividad con colores por estado)
if (!empty($_REQUEST['tareasDespachoGridAjax'])) {
    $data = array_merge($_GET, array('Emp_Cod' => $Ses_Emp_Cod));
    $arr = $obBD_con1->getArrayConsulta(53, $data, $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $obBD_con1->setError(0, '');
        $arr = $obBD_con1->getArrayConsulta(54, $data, $obBD_conexion);
    }
    echo json_encode(array('rows' => $arr));
    exit;
}

// Helper: obtener datos tareas para export (mismo filtro que grid). Acepta Tar_Periodo o FechaDesde+FechaHasta.
function aud_operativo_datos_tareas_export($obBD_con1, $obBD_conexion, $Ses_Emp_Cod) {
    $data = array('Emp_Cod' => $Ses_Emp_Cod);
    if (!empty($_REQUEST['Tar_Periodo'])) $data['Tar_Periodo'] = trim($_REQUEST['Tar_Periodo']);
    $fdesde = trim(isset($_REQUEST['FechaDesde']) ? $_REQUEST['FechaDesde'] : '');
    $fhasta = trim(isset($_REQUEST['FechaHasta']) ? $_REQUEST['FechaHasta'] : '');
    if ($fdesde !== '' && $fhasta !== '') { $data['FechaDesde'] = $fdesde; $data['FechaHasta'] = $fhasta; }
    if (!empty($_REQUEST['search'])) $data['search'] = trim($_REQUEST['search']);
    if (!empty($_REQUEST['Per_Cod'])) $data['Per_Cod'] = intval($_REQUEST['Per_Cod']);
    $arr = $obBD_con1->getArrayConsulta(53, $data, $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $obBD_con1->setError(0, '');
        $arr = $obBD_con1->getArrayConsulta(54, $data, $obBD_conexion);
    }
    return is_array($arr) ? $arr : array();
}

// Ajax: Tareas despacho - Exportar Excel (acepta Tar_Periodo o FechaDesde+FechaHasta)
if (!empty($_REQUEST['tareasDespachoExcel'])) {
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $fdesde = trim(isset($_REQUEST['FechaDesde']) ? $_REQUEST['FechaDesde'] : '');
    $fhasta = trim(isset($_REQUEST['FechaHasta']) ? $_REQUEST['FechaHasta'] : '');
    $tienePeriodo = ($per !== '') || ($fdesde !== '' && $fhasta !== '');
    if (!$tienePeriodo) { header('Content-Type: application/json'); echo json_encode(array('success' => false, 'message' => 'Indique período.')); exit; }
    $arr = aud_operativo_datos_tareas_export($obBD_con1, $obBD_conexion, $Ses_Emp_Cod);
    $etiquetaPer = $per ? $per : ($fdesde && $fhasta ? $fdesde . '_' . $fhasta : date('Y-m-d_His'));
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Tareas_Despacho_' . $etiquetaPer . '_' . date('Y-m-d_His') . '.xls"');
    echo "\xEF\xBB\xBF";
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<h2>Tareas del despacho - Período ' . htmlspecialchars($per ? $per : ($fdesde && $fhasta ? $fdesde . ' / ' . $fhasta : $etiquetaPer)) . '</h2>';
    echo '<table border="1" cellpadding="4" cellspacing="0"><thead><tr style="background:#72A1CF; color:white;">';
    echo '<th>Cliente</th><th>Día Decl.</th><th>Servicio</th><th>Actividad</th><th>Estado</th><th>Responsable(s)</th><th>Avance</th><th>Fecha límite</th></tr></thead><tbody>';
    foreach ($arr as $r) {
        $fec = isset($r['Tar_Fecha_Limite']) && $r['Tar_Fecha_Limite'] !== '0000-00-00' ? $r['Tar_Fecha_Limite'] : '';
        $tarCod = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
        // Obtener responsables (nombres) desde la consulta de asignados
        $responsables = '-';
        if ($tarCod > 0) {
            $asig = $obBD_con1->getArrayConsulta(72, array('Tar_Cod' => $tarCod), $obBD_conexion);
            if (is_array($asig) && count($asig) > 0) {
                $nombres = array();
                foreach ($asig as $a) {
                    $nom = isset($a['Personal_Nombre']) ? trim($a['Personal_Nombre']) : '';
                    if ($nom !== '') $nombres[$nom] = true;
                }
                if (!empty($nombres)) $responsables = implode(', ', array_keys($nombres));
            }
        }
        // Calcular % de avance
        $est = isset($r['Tar_Est']) ? $r['Tar_Est'] : '';
        $estUp = strtoupper($est);
        $pct = 0;
        if ($estUp === 'FINALIZADA') {
            $pct = 100;
        } elseif (isset($r['Tar_Avance'])) {
            $pct = intval($r['Tar_Avance']);
            if ($pct < 0) $pct = 0;
            if ($pct > 100) $pct = 100;
        }
        // Color de la barra según estado
        $color = '#93c5fd'; // azul por defecto (en proceso)
        if ($estUp === 'FINALIZADA') $color = '#4ade80';       // verde
        elseif ($estUp === 'VENCIDA') $color = '#fca5a5';      // rojo claro
        elseif ($estUp === 'PENDIENTE') $color = '#fde68a';    // amarillo
        // Barra de avance con % dentro
        $barraAvance = '<div style="position:relative;width:100px;height:14px;background:#e2e8f0;border-radius:7px;">'
            . '<div style="position:absolute;left:0;top:0;height:100%;width:' . $pct . '%;background:' . $color . ';border-radius:7px;"></div>'
            . '<div style="position:absolute;left:0;top:0;width:100%;height:100%;text-align:center;font-size:10px;font-weight:bold;">' . $pct . '%</div>'
            . '</div>';
        echo '<tr><td>' . htmlspecialchars(isset($r['Cliente_Nombre']) ? $r['Cliente_Nombre'] : '') . '</td><td>' . htmlspecialchars(isset($r['Ruc_Dia_Declaracion']) ? $r['Ruc_Dia_Declaracion'] : '') . '</td><td>' . htmlspecialchars(isset($r['Ser_Nombre']) ? $r['Ser_Nombre'] : '') . '</td><td>' . htmlspecialchars(isset($r['Act_Nombre']) ? $r['Act_Nombre'] : '') . '</td><td>' . htmlspecialchars($est) . '</td><td>' . htmlspecialchars($responsables) . '</td><td>' . $barraAvance . '</td><td>' . htmlspecialchars($fec) . '</td></tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

// Ajax: Detalle de tarea para modal (tarea + asignados + archivos adjuntos)
if (!empty($_REQUEST['detalleTareaDespacho'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    $out = array('tarea' => null, 'asignados' => array(), 'adjuntos' => array());
    if ($tarCod > 0) {
        $out['tarea'] = $obBD_con1->getRowConsulta(55, array('Tar_Cod' => $tarCod), $obBD_conexion);
        $out['asignados'] = $obBD_con1->getArrayConsulta(72, array('Tar_Cod' => $tarCod), $obBD_conexion);
        if (!is_array($out['asignados'])) $out['asignados'] = array();
        $out['adjuntos'] = $obBD_con1->getArrayConsulta(40, array('Tar_Cod' => $tarCod), $obBD_conexion);
        if (!is_array($out['adjuntos'])) $out['adjuntos'] = array();
    }
    echo json_encode($out);
    exit;
}

// Ajax: KPI dashboard (acepta Tar_Periodo o FechaDesde+FechaHasta)
if (!empty($_REQUEST['kpiDespacho'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $fdesde = trim(isset($_REQUEST['FechaDesde']) ? $_REQUEST['FechaDesde'] : '');
    $fhasta = trim(isset($_REQUEST['FechaHasta']) ? $_REQUEST['FechaHasta'] : '');
    $par = array('Emp_Cod' => $Ses_Emp_Cod);
    if ($fdesde !== '' && $fhasta !== '') { $par['FechaDesde'] = $fdesde; $par['FechaHasta'] = $fhasta; }
    elseif ($per !== '') $par['Tar_Periodo'] = $per;
    $row = $obBD_con1->getRowConsulta(36, $par, $obBD_conexion);
    echo json_encode($row ?: array());
    exit;
}

// Ajax: Dashboard completo (KPI + por servicio + top clientes). Acepta Tar_Periodo o FechaDesde+FechaHasta
if (!empty($_REQUEST['dashboardDespachoCompleto'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $fdesde = trim(isset($_REQUEST['FechaDesde']) ? $_REQUEST['FechaDesde'] : '');
    $fhasta = trim(isset($_REQUEST['FechaHasta']) ? $_REQUEST['FechaHasta'] : '');
    $par = array('Emp_Cod' => $Ses_Emp_Cod);
    if ($fdesde !== '' && $fhasta !== '') {
        $par['FechaDesde'] = $fdesde;
        $par['FechaHasta'] = $fhasta;
    } elseif ($per !== '') {
        $par['Tar_Periodo'] = $per;
    }
    $kpi = $obBD_con1->getRowConsulta(36, $par, $obBD_conexion);
    $porServicio = $obBD_con1->getArrayConsulta(85, $par, $obBD_conexion);
    $topClientes = $obBD_con1->getArrayConsulta(86, $par, $obBD_conexion);
    echo json_encode(array(
        'kpi' => $kpi ?: array(),
        'porServicio' => is_array($porServicio) ? $porServicio : array(),
        'topClientes' => is_array($topClientes) ? $topClientes : array()
    ));
    exit;
}

// Ajax: Listar reglas
if (!empty($_REQUEST['listarReglas'])) {
    $arr = $obBD_con1->getArrayConsulta(21, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    echo json_encode(array('rows' => $arr));
    exit;
}

// Ajax: Vista previa reporte facturación (JSON). Acepta Tar_Periodo o FechaDesde+FechaHasta. Opcional Cli_Cod.
if (!empty($_REQUEST['reporteFacturacionPreview'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $fdesde = trim(isset($_REQUEST['FechaDesde']) ? $_REQUEST['FechaDesde'] : '');
    $fhasta = trim(isset($_REQUEST['FechaHasta']) ? $_REQUEST['FechaHasta'] : '');
    $criterio = isset($_REQUEST['Criterio']) ? $_REQUEST['Criterio'] : 'A';
    $out = array('rows' => array(), 'periodo' => $per ?: ($fdesde && $fhasta ? $fdesde . ' / ' . $fhasta : ''), 'criterio' => $criterio);
    if ($per !== '' || ($fdesde !== '' && $fhasta !== '')) {
        $par = array('Emp_Cod' => $Ses_Emp_Cod, 'Criterio' => $criterio);
        if ($fdesde !== '' && $fhasta !== '') {
            $par['FechaDesde'] = $fdesde;
            $par['FechaHasta'] = $fhasta;
        } else {
            $par['Tar_Periodo'] = $per;
        }
        if (!empty($_REQUEST['Cli_Cod'])) $par['Cli_Cod'] = intval($_REQUEST['Cli_Cod']);
        $arr = $obBD_con1->getArrayConsulta(41, $par, $obBD_conexion);
        $out['rows'] = is_array($arr) ? $arr : array();
    }
    echo json_encode($out);
    exit;
}

// Ajax: Reporte Contratadas (clientes con valor del contrato vigente)
if (!empty($_REQUEST['reporteContratadas'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $par = array('Emp_Cod' => $Ses_Emp_Cod);
    if ($per !== '') $par['Tar_Periodo'] = $per;
    $arr = $obBD_con1->getArrayConsulta(84, $par, $obBD_conexion);
    echo json_encode(array('rows' => is_array($arr) ? $arr : array(), 'periodo' => $per));
    exit;
}

// Ajax: Reporte facturación Excel (tipo: contratadas | extras | completo). Acepta Tar_Periodo o FechaDesde+FechaHasta para extras/completo.
if (!empty($_REQUEST['reporteFacturacionExcel'])) {
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $fdesde = trim(isset($_REQUEST['FechaDesde']) ? $_REQUEST['FechaDesde'] : '');
    $fhasta = trim(isset($_REQUEST['FechaHasta']) ? $_REQUEST['FechaHasta'] : '');
    $criterio = isset($_REQUEST['Criterio']) ? $_REQUEST['Criterio'] : 'A';
    $tipo = isset($_REQUEST['tipo']) ? trim($_REQUEST['tipo']) : 'completo';
    $tienePeriodo = ($per !== '' || ($fdesde !== '' && $fhasta !== ''));
    if (!$tienePeriodo && $tipo !== 'contratadas') { echo json_encode(array('success' => false, 'message' => 'Indique período.')); exit; }
    $sufijo = $tipo === 'contratadas' ? 'Contratadas' : ($tipo === 'extras' ? 'Extras' : 'Completo');
    $par41 = array('Emp_Cod' => $Ses_Emp_Cod, 'Criterio' => $criterio);
    if ($fdesde !== '' && $fhasta !== '') { $par41['FechaDesde'] = $fdesde; $par41['FechaHasta'] = $fhasta; } elseif ($per !== '') { $par41['Tar_Periodo'] = $per; }
    $etiquetaPer = $per ? $per : ($fdesde && $fhasta ? $fdesde . '_' . $fhasta : '');
    if ($tipo === 'contratadas') {
        $par = array('Emp_Cod' => $Ses_Emp_Cod); if ($per !== '') $par['Tar_Periodo'] = $per;
        $arr = $obBD_con1->getArrayConsulta(84, $par, $obBD_conexion);
        $arr = is_array($arr) ? $arr : array();
        $tot = 0; foreach ($arr as $r) { $tot += floatval(isset($r['Valor_Contrato']) ? $r['Valor_Contrato'] : 0); }
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="Facturacion_' . $sufijo . '_' . ($per ?: date('Y-m')) . '_' . date('Y-m-d_His') . '.xls"');
        echo "\xEF\xBB\xBF";
        echo '<html><head><meta charset="UTF-8"></head><body><h2>Contratadas (Incluidas) - Período ' . htmlspecialchars($per ?: $etiquetaPer) . '</h2>';
        echo '<table border="1" cellpadding="4" cellspacing="0"><tr style="background:#72A1CF; color:white;"><th>Cliente</th><th>Valor contrato acordado</th><th>Nº contrato</th></tr>';
        foreach ($arr as $r) { echo '<tr><td>' . htmlspecialchars(isset($r['Cliente_Nombre']) ? $r['Cliente_Nombre'] : '') . '</td><td>' . (isset($r['Valor_Contrato']) ? $r['Valor_Contrato'] : 0) . '</td><td>' . htmlspecialchars(isset($r['Con_Numero']) ? $r['Con_Numero'] : '') . '</td></tr>'; }
        echo '<tr style="background:#f1f5f9; font-weight:bold;"><td>TOTAL</td><td>' . number_format($tot, 2) . '</td><td></td></tr></table></body></html>';
        exit;
    }
    if ($tipo === 'extras') {
        $arr = $obBD_con1->getArrayConsulta(41, $par41, $obBD_conexion);
        $arr = is_array($arr) ? $arr : array();
        $arr = array_filter($arr, function ($r) { return (isset($r['Act_Facturable']) && strtoupper($r['Act_Facturable']) === 'S') && (empty($r['Incluida_Contrato']) || strtoupper($r['Incluida_Contrato']) !== 'S'); });
        $totAct = 0; foreach ($arr as $r) { $totAct += floatval(isset($r['Act_Valor']) ? $r['Act_Valor'] : 0); }
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="Facturacion_Extras_' . $etiquetaPer . '_' . date('Y-m-d_His') . '.xls"');
        echo "\xEF\xBB\xBF";
        echo '<html><head><meta charset="UTF-8"></head><body><h2>Extras (Actividades facturables) - Período ' . htmlspecialchars($etiquetaPer) . '</h2>';
        echo '<table border="1" cellpadding="4" cellspacing="0"><tr style="background:#72A1CF; color:white;"><th>Cliente</th><th>Servicio</th><th>Actividad</th><th>% Avance</th><th>Valor Act.</th></tr>';
        foreach ($arr as $r) {
            $est = isset($r['Tar_Est']) ? $r['Tar_Est'] : '';
            $estUp = strtoupper($est);
            $pct = ($estUp === 'FINALIZADA') ? 100 : min(100, max(0, intval(isset($r['Tar_Avance']) ? $r['Tar_Avance'] : 0)));
            $color = '#93c5fd';
            if ($estUp === 'FINALIZADA') $color = '#4ade80';
            elseif ($estUp === 'VENCIDA') $color = '#fca5a5';
            elseif ($estUp === 'PENDIENTE') $color = '#fde68a';
            $barraAvance = '<div style="position:relative;width:100px;height:14px;background:#e2e8f0;border-radius:7px;">'
                . '<div style="position:absolute;left:0;top:0;height:100%;width:' . $pct . '%;background:' . $color . ';border-radius:7px;"></div>'
                . '<div style="position:absolute;left:0;top:0;width:100%;height:100%;text-align:center;font-size:10px;font-weight:bold;">' . $pct . '%</div></div>';
            echo '<tr><td>' . htmlspecialchars(isset($r['Cliente_Nombre']) ? $r['Cliente_Nombre'] : '') . '</td><td>' . htmlspecialchars(isset($r['Ser_Nombre']) ? $r['Ser_Nombre'] : '') . '</td><td>' . htmlspecialchars(isset($r['Act_Nombre']) ? $r['Act_Nombre'] : '') . '</td><td>' . $barraAvance . '</td><td>' . (isset($r['Act_Valor']) ? $r['Act_Valor'] : 0) . '</td></tr>';
        }
        echo '<tr style="background:#f1f5f9; font-weight:bold;"><td colspan="4">TOTAL</td><td>' . number_format($totAct, 2) . '</td></tr></table></body></html>';
        exit;
    }
    $arr = $obBD_con1->getArrayConsulta(41, $par41, $obBD_conexion);
    $totAct = 0; $totSer = 0;
    foreach ($arr as $r) { $totAct += floatval(isset($r['Act_Valor']) ? $r['Act_Valor'] : 0); $totSer += floatval(isset($r['Ser_Valor']) ? $r['Ser_Valor'] : 0); }
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Facturacion_Completo_' . $etiquetaPer . '_' . date('Y-m-d_His') . '.xls"');
    echo "\xEF\xBB\xBF";
    echo '<html><head><meta charset="UTF-8"></head><body><h2>Listado completo - Período ' . htmlspecialchars($etiquetaPer) . '</h2>';
    echo '<table border="1" cellpadding="4" cellspacing="0"><thead><tr style="background:#72A1CF; color:white;">';
    echo '<th>Cliente</th><th>Servicio</th><th>Actividad</th><th>Facturable Act.</th><th>Valor Act.</th><th>Facturable Ser.</th><th>Valor Ser.</th></tr></thead><tbody>';
    foreach ($arr as $r) {
        echo '<tr><td>' . htmlspecialchars(isset($r['Cliente_Nombre']) ? $r['Cliente_Nombre'] : '') . '</td><td>' . htmlspecialchars(isset($r['Ser_Nombre']) ? $r['Ser_Nombre'] : '') . '</td><td>' . htmlspecialchars(isset($r['Act_Nombre']) ? $r['Act_Nombre'] : '') . '</td>';
        echo '<td>' . (isset($r['Act_Facturable']) ? $r['Act_Facturable'] : 'N') . '</td><td>' . (isset($r['Act_Valor']) ? $r['Act_Valor'] : 0) . '</td><td>' . (isset($r['Ser_Facturable']) ? $r['Ser_Facturable'] : 'N') . '</td><td>' . (isset($r['Ser_Valor']) ? $r['Ser_Valor'] : 0) . '</td></tr>';
    }
    echo '<tr style="background:#f1f5f9; font-weight:bold;"><td colspan="4">TOTAL</td><td>' . number_format($totAct, 2) . '</td><td></td><td>' . number_format($totSer, 2) . '</td></tr></tbody></table></body></html>';
    exit;
}

$kpi = $obBD_con1->getRowConsulta(36, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
$total = isset($kpi['Total_Tareas']) ? intval($kpi['Total_Tareas']) : 0;
$completadas = isset($kpi['Completadas']) ? intval($kpi['Completadas']) : 0;
$vencidas = isset($kpi['Vencidas']) ? intval($kpi['Vencidas']) : 0;
$atrasadas = isset($kpi['Atrasadas']) ? intval($kpi['Atrasadas']) : 0;
$pct_compl = $total > 0 ? round(100 * $completadas / $total, 1) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'EXA'; ?> - Operativo Despacho</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <?php require_once('../../mascaras/model1/estilos/estilos.php'); ?>
    <link href="aud_zoom.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <?php require_once('../../mascaras/model1/estilos/jqgrid5.php'); ?>
    <style>
        .operativo-modulo { font-size: 10px; }
        .exa-header { background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 50%, #5A9BD4 100%); color: white; padding: 10px 20px; border-radius: 10px; box-shadow: 0 4px 14px rgba(44,93,148,0.3); margin-bottom: 20px; }
        .exa-header h3 { margin: 0; font-size: 20px; font-weight: 600; }
        .config-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .config-header { background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white; padding: 8px 14px; border-radius: 10px 10px 0 0; margin: -20px -20px 20px -20px; font-weight: 500; font-size: 15px; }
        .config-header h4 { margin: 0; font-size: 15px; font-weight: 600; }
        .tabs-wrapper { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04); overflow: hidden; }
        .nav-tabs { border-bottom: 2px solid #cbd5e1; margin: 0; padding: 10px 16px 0 16px; background: #DEE7EF; display: flex; flex-wrap: nowrap; list-style: none; }
        .nav-tabs > li { margin-bottom: -2px; margin-right: 4px; flex-shrink: 0; }
        .nav-tabs > li > a { display: inline-block; color: #475569; font-weight: 600; font-size: 14px; padding: 8px 16px; border: 1px solid #e2e8f0; border-bottom: none; border-radius: 8px 8px 0 0; background: #e2e8f0; transition: all 0.2s ease; text-decoration: none; }
        .nav-tabs > li > a:hover { background: #DEE7EF; color: #2C5D94; border-color: #cbd5e1; }
        .nav-tabs > li.active > a, .nav-tabs > li.active > a:hover, .nav-tabs > li.active > a:focus { background: #3d7bb8; color: white; border-color: #2C5D94; border-bottom: 2px solid #2C5D94; margin-bottom: -2px; }
        .tab-content { padding: 20px; background: #E8F0F7; }
        .tab-pane { background: transparent; display: none; }
        .tab-pane.active { display: block; }
        .stat-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; transition: box-shadow 0.2s, transform 0.2s; font-size: 13px; }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .stat-card .stat-label { font-size: 13px; }
        .stat-card .stat-value { font-size: 13px; font-weight: 700; }
        .operativo-modulo .config-card label,
        .operativo-modulo .config-card .form-control,
        .operativo-modulo .config-card .btn,
        .operativo-modulo .config-card .text-muted,
        .operativo-modulo .config-card p { font-size: 13px !important; }
        .aud-tabla { width: 100%; border-collapse: collapse; font-size: 10px; }
        .aud-tabla thead th { background: linear-gradient(135deg, #72A1CF 0%, #8EB7DD 100%); color: white; padding: 8px 10px; font-size: 13px; }
        .aud-tabla tbody td { padding: 6px 10px; border-bottom: 1px solid #dee2e6; }
        /* Colores por estado de tarea - estilo chip moderno, texto más oscuro */
        .tarea-pendiente { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important; color: #78350f; box-shadow: 0 1px 2px rgba(146,64,14,0.15); }
        .tarea-asignada { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important; color: #1e3a8a; box-shadow: 0 1px 2px rgba(30,64,175,0.15); }
        .tarea-en-proceso { background: linear-gradient(135deg, #93c5fd 0%, #60a5fa 100%) !important; color: #172554; box-shadow: 0 1px 2px rgba(30,58,138,0.2); }
        .tarea-finalizada { background: linear-gradient(135deg, #86efac 0%, #4ade80 100%) !important; color: #14532d; box-shadow: 0 1px 2px rgba(22,101,52,0.15); }
        .tarea-vencida { background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%) !important; color: #7f1d1d; box-shadow: 0 1px 2px rgba(153,27,27,0.15); }
        .tarea-observada { background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%) !important; color: #7c2d12; box-shadow: 0 1px 2px rgba(154,52,18,0.15); }
        /* Grid tareas - diseño moderno - 1 tarea por línea */
        .aud-grid-tareas { border-collapse: separate; border-spacing: 0; font-size: 10px; width: 100%; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .aud-grid-tareas th, .aud-grid-tareas td { padding: 10px 12px; border: none; }
        .aud-grid-tareas thead th { background: linear-gradient(180deg, #72A1CF 0%, #8EB7DD 100%); color: white; font-weight: 600; font-size: 13px; letter-spacing: 0.5px; text-transform: uppercase; text-align: center; }
        .aud-grid-tareas thead th:first-child { border-radius: 12px 0 0 0; }
        .aud-grid-tareas thead th:last-child { border-radius: 0 12px 0 0; }
        .aud-grid-tareas thead th.th-servicio { width: 125px; max-width: 125px; min-width: 125px; }
        .aud-grid-tareas thead th.th-cliente { width: 110px; max-width: 110px; min-width: 80px; }
        .aud-grid-tareas thead th.th-dia-decl { width: 15px; max-width: 15px; min-width: 15px; }
        .aud-grid-tareas tbody tr { transition: background 0.15s ease; }
        .aud-grid-tareas tbody td { border-bottom: 2px solid #94a3b8; }
        .aud-grid-tareas tbody tr:last-child td { border-bottom: none; }
        .aud-grid-tareas tbody tr:nth-child(even) { background: #f8fafc; }
        .aud-grid-tareas tbody tr:nth-child(odd) { background: #fff; }
        .aud-grid-tareas tbody tr:hover { background: #f1f5f9 !important; }
        .aud-grid-tareas .col-cliente { background: linear-gradient(90deg, #e2e8f0 0%, #f1f5f9 100%) !important; font-weight: 700; min-width: 80px; max-width: 110px; font-size: 10px; overflow: hidden; text-overflow: ellipsis; color: #0f172a; }
        .aud-grid-tareas .col-dia-decl { background: #cbd5e1 !important; font-weight: 700; text-align: center; width: 30px; min-width: 15px; font-size: 10px; color: #0f172a; border-left: 1px solid #94a3b8; padding: 10px 4px; }
        .aud-grid-tareas .celda-servicio { width: 125px; max-width: 125px; min-width: 125px; padding: 8px; vertical-align: top; border-left: 1px solid #e2e8f0; }
        .aud-grid-tareas .actividad-mini { font-size: 10px; padding: 4px 8px; margin-bottom: 4px; border-radius: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; font-weight: 600; transition: transform 0.15s; display: block; }
        .aud-grid-tareas .actividad-mini:hover { transform: translateX(2px); }
        .aud-grid-tareas .actividad-mini:last-child { margin-bottom: 0; }
        .aud-grid-tareas .actividad-mini[data-tar-cod] { cursor: pointer; }
        #modalDetalleTarea .modal-body { font-size: 13px; }
        #modalDetalleTarea .detalle-seccion { margin-bottom: 18px; }
        #modalDetalleTarea .detalle-seccion h5 { margin: 0 0 10px 0; font-size: 14px; color: #2C5D94; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; }
        #modalDetalleTarea .tabla-asignados { width: 100%; border-collapse: collapse; font-size: 12px; }
        #modalDetalleTarea .tabla-asignados th, #modalDetalleTarea .tabla-asignados td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        #modalDetalleTarea .tabla-asignados th { background: #f1f5f9; font-weight: 600; }
        /* Barra de avance en modal detalle contrato - porcentaje dentro de la barra */
        .barra-avance-wrap { position: relative; width: 100%; max-width: 120px; height: 22px; background: #e2e8f0; border-radius: 11px; overflow: hidden; display: inline-block; vertical-align: middle; }
        .barra-avance-wrap .barra-avance-fill { height: 100%; border-radius: 11px; min-width: 0; transition: width 0.25s ease; }
        .barra-avance-wrap .barra-avance-text { position: absolute; left: 0; right: 0; top: 0; bottom: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: #0f172a; text-shadow: 0 0 1px #fff, 0 1px 2px rgba(255,255,255,0.8); pointer-events: none; }
        .aud-leyenda { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 16px; padding: 12px 16px; background: #f8fafc; border-radius: 10px; font-size: 10px; }
        .aud-leyenda strong { margin-right: 6px; color: #334155; }
        .aud-leyenda .leyenda-item { padding: 4px 10px; border-radius: 6px; font-weight: 500; }
    </style>
</head>
<body>
<div class="container-fluid operativo-modulo" style="padding: 20px; background: #E8F0F7; min-height: 100vh;">
    <div class="exa-header"><h3><span class="glyphicon glyphicon-stats"></span> Operativo del Despacho - Dashboard y Reportes</h3></div>

    <div class="tabs-wrapper">
    <ul class="nav nav-tabs" role="tablist">
        <li class="active"><a href="#tab-dashboard" data-toggle="tab"><span class="glyphicon glyphicon-stats"></span> Dashboard</a></li>
        <li><a href="#tab-tareas" data-toggle="tab"><span class="glyphicon glyphicon-tasks"></span> Tareas</a></li>
        <li><a href="#tab-facturacion" data-toggle="tab"><span class="glyphicon glyphicon-usd"></span> Reporte Facturación</a></li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="tab-dashboard">
            <div class="config-card">
                <div class="config-header"><h4>Indicadores por período</h4></div>
                <div style="margin-bottom: 15px;" class="form-inline">
                    <label style="margin-right: 8px;">Período:</label>
                    <select id="filtroTipoPeriodoKpi" class="form-control input-sm" style="width: 160px; display: inline-block;">
                        <option value="todo">Todo</option>
                        <option value="semana">Esta semana</option>
                        <option value="mes" selected>Este mes</option>
                        <option value="anio">Este año</option>
                        <option value="rango">Rango de fecha</option>
                    </select>
                    <span id="dashboardRangoFechaWrap" style="display: none; margin-left: 12px;">
                        <label style="margin: 0 4px 0 8px;">Desde:</label>
                        <input type="date" id="filtroFechaDesdeKpi" class="form-control input-sm" style="width: 140px; display: inline-block;" />
                        <label style="margin: 0 4px 0 8px;">Hasta:</label>
                        <input type="date" id="filtroFechaHastaKpi" class="form-control input-sm" style="width: 140px; display: inline-block;" />
                    </span>
                    <button type="button" class="btn btn-sm btn-primary" id="btnActualizarKpi" style="margin-left: 10px;">Actualizar</button>
                </div>
                <div class="row" style="margin-bottom: 8px;">
                    <div class="col-sm-2"><div class="stat-card"><div class="stat-label">Total tareas</div><div class="stat-value" id="kpi-total"><?php echo $total; ?></div></div></div>
                    <div class="col-sm-2"><div class="stat-card"><div class="stat-label">Completadas</div><div class="stat-value" style="color:#10b981;" id="kpi-completadas"><?php echo $pct_compl; ?>%</div></div></div>
                    <div class="col-sm-2"><div class="stat-card"><div class="stat-label">Vencidas / Atrasadas</div><div class="stat-value" style="color:#ef4444;" id="kpi-vencidas"><?php echo $vencidas + $atrasadas; ?></div></div></div>
                    <div class="col-sm-2"><div class="stat-card"><div class="stat-label">Pendientes</div><div class="stat-value" style="color:#6366f1;" id="kpi-pendientes">0</div></div></div>
                    <div class="col-sm-2"><div class="stat-card"><div class="stat-label">En proceso</div><div class="stat-value" style="color:#f59e0b;" id="kpi-en-proceso">0</div></div></div>
                    <div class="col-sm-2"><div class="stat-card"><div class="stat-label">Observadas</div><div class="stat-value" style="color:#ea580c;" id="kpi-observadas">0</div></div></div>
                </div>
                <div class="row">
                    <div class="col-sm-4"><div class="stat-card" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);"><div class="stat-label">Tasa de cumplimiento</div><div class="stat-value" style="color:#15803d; font-size: 1.1em;" id="kpi-cumplimiento">—</div></div></div>
                    <div class="col-sm-4"><div class="stat-card" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);"><div class="stat-label">Tareas con asignación</div><div class="stat-value" style="color:#1d4ed8;" id="kpi-con-asignacion">—</div></div></div>
                    <div class="col-sm-4"><div class="stat-card" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);"><div class="stat-label">Riesgo (vencidas + atrasadas)</div><div class="stat-value" style="color:#b45309;" id="kpi-riesgo">0</div></div></div>
                </div>
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-5">
                        <div class="config-card" style="margin-bottom: 0;">
                            <div class="config-header" style="font-size: 13px;"><h4 style="margin: 0;">Distribución por estado</h4></div>
                            <div style="padding: 10px; min-height: 220px;"><canvas id="chartEstado" width="280" height="200"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="config-card" style="margin-bottom: 0;">
                            <div class="config-header" style="font-size: 13px;"><h4 style="margin: 0;">Tareas por servicio</h4></div>
                            <div style="padding: 10px; min-height: 220px;"><canvas id="chartServicio" width="400" height="200"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="row" style="margin-top: 12px;">
                    <div class="col-md-12">
                        <div class="config-card" style="margin-bottom: 0;">
                            <div class="config-header" style="font-size: 13px;"><h4 style="margin: 0;">Top 10 clientes por carga de tareas</h4></div>
                            <div id="dashboardTopClientes" style="padding: 12px; min-height: 80px;">
                                <p class="text-muted small">Actualice el período para ver el ranking.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="tab-tareas">
            <div class="config-card">
                <div class="config-header"><h4>Tareas del despacho</h4></div>
                <div class="form-inline" style="margin-bottom: 8px; gap: 10px;">
                    <label style="margin-right:4px;">Período:</label>
                    <select id="filtroTipoPeriodoTareas" class="form-control input-sm" style="width: 150px; border-radius: 8px; display:inline-block;">
                        <option value="semana">Esta semana</option>
                        <option value="mes" selected>Este mes</option>
                        <option value="rango">Rango de fecha</option>
                    </select>
                    <span id="wrapperRangoTareas" style="display:none; margin-left:8px;">
                        <label style="margin:0 4px 0 0;">Desde:</label>
                        <input type="date" id="filtroFechaDesdeTareas" class="form-control input-sm" style="width: 140px; border-radius: 8px; display:inline-block;" />
                        <label style="margin:0 4px 0 8px;">Hasta:</label>
                        <input type="date" id="filtroFechaHastaTareas" class="form-control input-sm" style="width: 140px; border-radius: 8px; display:inline-block;" />
                    </span>
                    <input type="text" id="filtroPeriodoTareas" placeholder="Período 2026-01" class="form-control input-sm" style="width: 110px; border-radius: 8px; display:none;" />
                    <input type="text" id="filtroSearchTareas" placeholder="Buscar cliente..." class="form-control input-sm" style="width: 180px; border-radius: 8px;" />
                </div>
                <div class="form-inline" style="margin-bottom: 16px; gap: 10px;">
                    <label class="control-label" style="margin:0 4px 0 0;">Usuario:</label>
                    <select id="filtroUsuarioTareas" class="form-control input-sm" style="width: 220px; border-radius: 8px;">
                        <option value="">Todos</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" id="btnBuscarTareas" style="border-radius: 8px; padding: 5px 16px; margin-left:10px;">Buscar</button>
                    <button type="button" class="btn btn-sm btn-success" id="btnExportarTareasExcel" style="margin-left:10px;"><i class="glyphicon glyphicon-download"></i> Exportar Excel</button>
                    <button type="button" class="btn btn-sm btn-danger" id="btnExportarTareasPdf"><i class="glyphicon glyphicon-file"></i> Exportar PDF</button>
                </div>
                <p class="text-muted small" id="mensajePeriodoTareas" style="margin: 0 0 10px 0; font-weight: 600;"></p>
                <div id="gridTareasDespachoContainer" style="overflow-x: auto; border-radius: 12px;"></div>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="tab-facturacion">
            <div class="config-card">
                <div class="config-header"><h4>Listado de Facturación</h4></div>
                <p class="text-muted">Genera listado de actividades facturables vs incluidas por período. Use las subpestañas para Contratadas (incluidas) y Extras (facturables).</p>
                <div class="form-inline" style="margin-bottom: 12px;">
                    <label style="margin-right:4px;">Período:</label>
                    <select id="filtroTipoPeriodoFact" class="form-control input-sm" style="width: 150px; display:inline-block;">
                        <option value="mes" selected>Este mes</option>
                        <option value="anio">Este año</option>
                        <option value="rango">Rango de fecha</option>
                    </select>
                    <span id="wrapperRangoFact" style="display:none; margin-left:8px;">
                        <label style="margin:0 4px 0 0;">Desde:</label>
                        <input type="date" id="filtroFechaDesdeFact" class="form-control input-sm" style="width: 140px; display:inline-block;" />
                        <label style="margin:0 4px 0 8px;">Hasta:</label>
                        <input type="date" id="filtroFechaHastaFact" class="form-control input-sm" style="width: 140px; display:inline-block;" />
                    </span>
                    <input type="text" id="periodoFacturacion" class="form-control input-sm" placeholder="2026-01" style="width: 100px; display:none;" />
                    <label style="margin-left:15px;">Criterio:</label>
                    <select id="criterioFacturacion" class="form-control input-sm">
                        <option value="A">Solo finalizadas</option>
                        <option value="B">Todas generadas</option>
                    </select>
                    <button type="button" class="btn btn-primary btn-sm" id="btnVistaPreviaFacturacion" style="margin-left:15px;"><i class="glyphicon glyphicon-eye-open"></i> Vista previa</button>
                </div>
                <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 10px;">
                    <li role="presentation" class="active"><a href="#subtab-contratadas" aria-controls="subtab-contratadas" role="tab" data-toggle="tab">Contratadas (Incluidas en el servicio)</a></li>
                    <li role="presentation"><a href="#subtab-extras" aria-controls="subtab-extras" role="tab" data-toggle="tab">Extras (Actividades facturables)</a></li>
                    <li role="presentation"><a href="#subtab-listado" aria-controls="subtab-listado" role="tab" data-toggle="tab">Listado completo</a></li>
                </ul>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="subtab-contratadas">
                        <div id="reporteContratadasContainer" style="overflow-x: auto; max-height: 380px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 8px;">
                            <p class="text-muted small">Presione el botón <strong>Vista previa</strong> para cargar la lista de clientes con valor del contrato acordado.</p>
                        </div>
                        <div class="export-botones-tabla" style="margin-top:10px;">
                            <button type="button" class="btn btn-success btn-sm btnExportarTablaExcel" data-tipo="contratadas"><i class="glyphicon glyphicon-download"></i> Exportar Excel</button>
                            <button type="button" class="btn btn-danger btn-sm btnExportarTablaPdf" data-tipo="contratadas" style="margin-left:8px;"><i class="glyphicon glyphicon-file"></i> Exportar PDF</button>
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="subtab-extras">
                        <div id="reporteExtrasContainer" style="overflow-x: auto; max-height: 380px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 8px;">
                            <p class="text-muted small">Solo actividades no incluidas en el contrato (extras facturables). Indique período y criterio y cambie a esta pestaña.</p>
                        </div>
                        <div class="export-botones-tabla" style="margin-top:10px;">
                            <button type="button" class="btn btn-success btn-sm btnExportarTablaExcel" data-tipo="extras"><i class="glyphicon glyphicon-download"></i> Exportar Excel</button>
                            <button type="button" class="btn btn-danger btn-sm btnExportarTablaPdf" data-tipo="extras" style="margin-left:8px;"><i class="glyphicon glyphicon-file"></i> Exportar PDF</button>
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="subtab-listado">
                        <div id="reporteFacturacionPreviewContainer" style="overflow-x: auto; max-height: 380px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 8px;">
                            <p class="text-muted small">Use el botón <strong>Vista previa</strong> para cargar el listado completo de actividades (facturables e incluidas).</p>
                        </div>
                        <div class="export-botones-tabla" style="margin-top:10px;">
                            <button type="button" class="btn btn-success btn-sm btnExportarTablaExcel" data-tipo="completo"><i class="glyphicon glyphicon-download"></i> Exportar Excel</button>
                            <button type="button" class="btn btn-danger btn-sm btnExportarTablaPdf" data-tipo="completo" style="margin-left:8px;"><i class="glyphicon glyphicon-file"></i> Exportar PDF</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- Modal actividades del contrato (Ver Detalle en Contratadas) -->
<div id="modalDetalleContrato" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalDetalleContratoTitle">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color: white; opacity: 0.9;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalDetalleContratoTitle"><span class="glyphicon glyphicon-list"></span> Actividades generadas en el contrato</h4>
            </div>
            <div class="modal-body" id="modalDetalleContratoBody">
                <p class="text-muted"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal detalle tarea (administrador: info completa, asignados, % avance) -->
<div id="modalDetalleTarea" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalDetalleTareaTitle">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color: white; opacity: 0.9;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalDetalleTareaTitle"><span class="glyphicon glyphicon-tasks"></span> Detalle de la tarea</h4>
            </div>
            <div class="modal-body" id="modalDetalleTareaBody">
                <p class="text-muted"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script type="text/javascript">
(function () {
    var urlBase = <?php echo json_encode($_SERVER['PHP_SELF']); ?>;
    var chartEstado = null, chartServicio = null;

    function pad(n) { return n < 10 ? '0' + n : n; }
    function primerDiaMes(d) { d = d || new Date(); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-01'; }
    function ultimoDiaMes(d) { d = d || new Date(); var m = d.getMonth() + 1; d.setMonth(m); d.setDate(0); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function inicioSemana(d) { d = new Date(d || Date.now()); var day = d.getDay(), diff = d.getDate() - day + (day === 0 ? -6 : 1); d.setDate(diff); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function finSemana(d) { d = new Date(d || Date.now()); var day = d.getDay(), diff = d.getDate() + (day === 0 ? 0 : 7 - day); d.setDate(diff); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function inicioAnio(d) { d = d || new Date(); return d.getFullYear() + '-01-01'; }
    function finAnio(d) { d = d || new Date(); return d.getFullYear() + '-12-31'; }

    $('#filtroTipoPeriodoKpi').on('change', function () {
        var v = $(this).val();
        if (v === 'rango') {
            $('#dashboardRangoFechaWrap').show();
            var hoy = new Date();
            if (!$('#filtroFechaDesdeKpi').val()) { $('#filtroFechaDesdeKpi').val(primerDiaMes(hoy)); $('#filtroFechaHastaKpi').val(ultimoDiaMes(hoy)); }
        } else {
            $('#dashboardRangoFechaWrap').hide();
        }
    });

    function paramsDashboardKpi() {
        var tipo = $('#filtroTipoPeriodoKpi').val();
        var params = { dashboardDespachoCompleto: 1 };
        if (tipo === 'todo') return params;
        if (tipo === 'semana') { params.FechaDesde = inicioSemana(); params.FechaHasta = finSemana(); return params; }
        if (tipo === 'mes') { params.FechaDesde = primerDiaMes(); params.FechaHasta = ultimoDiaMes(); return params; }
        if (tipo === 'anio') { params.FechaDesde = inicioAnio(); params.FechaHasta = finAnio(); return params; }
        if (tipo === 'rango') {
            var desde = ($('#filtroFechaDesdeKpi').val() || '').trim();
            var hasta = ($('#filtroFechaHastaKpi').val() || '').trim();
            if (!desde) { var hoy = new Date(); desde = primerDiaMes(hoy); $('#filtroFechaDesdeKpi').val(desde); }
            if (!hasta) { var hoy = new Date(); hasta = ultimoDiaMes(hoy); $('#filtroFechaHastaKpi').val(hasta); }
            params.FechaDesde = desde;
            params.FechaHasta = hasta;
            return params;
        }
        return params;
    }

    function actualizarKpi() {
        var params = paramsDashboardKpi();
        $.get(urlBase, params, function (data) {
            var r = data.kpi || {};
            var tot = parseInt(r.Total_Tareas || 0, 10);
            var comp = parseInt(r.Completadas || 0, 10);
            var venc = parseInt(r.Vencidas || 0, 10) + parseInt(r.Atrasadas || 0, 10);
            var pend = parseInt(r.Pendientes || 0, 10);
            var enProceso = parseInt(r.En_Proceso || 0, 10);
            var observ = parseInt(r.Observadas || 0, 10);
            var conAsig = parseInt(r.Con_Asignacion || 0, 10);

            $('#kpi-total').text(tot);
            $('#kpi-completadas').text(tot > 0 ? Math.round(100 * comp / tot) + '%' : '0%');
            $('#kpi-vencidas').text(venc);
            $('#kpi-pendientes').text(pend);
            $('#kpi-en-proceso').text(enProceso);
            $('#kpi-observadas').text(observ);
            $('#kpi-cumplimiento').text(tot > 0 ? Math.round(100 * comp / tot) + '%' : '—');
            $('#kpi-con-asignacion').text(tot > 0 ? conAsig + ' (' + Math.round(100 * conAsig / tot) + '%)' : '—');
            $('#kpi-riesgo').text(venc);

            if (chartEstado) chartEstado.destroy();
            var ctxEstado = document.getElementById('chartEstado');
            if (ctxEstado && typeof Chart !== 'undefined') {
                var datosEstado = [
                    { label: 'Finalizadas', value: comp, color: '#10b981' },
                    { label: 'Pendientes', value: pend, color: '#6366f1' },
                    { label: 'En proceso', value: enProceso, color: '#f59e0b' },
                    { label: 'Observadas', value: observ, color: '#ea580c' },
                    { label: 'Vencidas', value: parseInt(r.Vencidas || 0, 10) + parseInt(r.Atrasadas || 0, 10), color: '#ef4444' }
                ].filter(function (d) { return d.value > 0; });
                chartEstado = new Chart(ctxEstado, {
                    type: 'doughnut',
                    data: {
                        labels: datosEstado.map(function (d) { return d.label; }),
                        datasets: [{ data: datosEstado.map(function (d) { return d.value; }), backgroundColor: datosEstado.map(function (d) { return d.color; }) }]
                    },
                    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
                });
            }

            if (chartServicio) chartServicio.destroy();
            var porServicio = data.porServicio || [];
            var ctxServicio = document.getElementById('chartServicio');
            if (ctxServicio && typeof Chart !== 'undefined') {
                chartServicio = new Chart(ctxServicio, {
                    type: 'bar',
                    data: {
                        labels: porServicio.map(function (s) { return (s.Ser_Nombre || '').substring(0, 25); }),
                        datasets: [{ label: 'Tareas', data: porServicio.map(function (s) { return parseInt(s.Cnt || 0, 10); }), backgroundColor: 'rgba(44,93,148,0.7)' }]
                    },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
                });
            }

            var topClientes = data.topClientes || [];
            var html = topClientes.length === 0 ? '<p class="text-muted small">Sin datos para el período.</p>' : '<table class="table table-condensed table-striped" style="margin:0; font-size: 12px;"><thead><tr><th>Cliente</th><th style="text-align:right;">Tareas</th></tr></thead><tbody>';
            topClientes.forEach(function (c) { html += '<tr><td>' + (c.Cliente_Nombre || '').replace(/</g, '&lt;') + '</td><td style="text-align:right;">' + (c.Cnt || 0) + '</td></tr>'; });
            html += '</tbody></table>';
            $('#dashboardTopClientes').html(html);
        }, 'json');
    }

    $('#btnActualizarKpi').on('click', actualizarKpi);
    actualizarKpi();

    var diasDeclaracionRuc = { 0: 28, 1: 10, 2: 12, 3: 14, 4: 16, 5: 18, 6: 20, 7: 22, 8: 24, 9: 26 };
    function diaDeclaracionDesdeRuc(ruc) {
        if (!ruc) return null;
        var soloDigitos = String(ruc).replace(/\D/g, '');
        if (soloDigitos.length < 9) return null;
        var dig9 = soloDigitos.charAt(8);
        if (dig9 >= '0' && dig9 <= '9') return diasDeclaracionRuc[parseInt(dig9, 10)];
        return null;
    }

    function esTareaVencida(tarEst, fechaLimite) {
        var est = (tarEst || '').toUpperCase();
        if (est === 'FINALIZADA') return false;
        var fl = (fechaLimite || '').trim();
        if (!fl || fl === '0000-00-00') return false;
        var hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        var parts = fl.split('-');
        if (parts.length < 3) return false;
        var fLim = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        fLim.setHours(0, 0, 0, 0);
        return fLim < hoy;
    }
    function clasePorEstado(tarEst, cntUsuarios, fechaLimite) {
        var est = (tarEst || '').toUpperCase();
        var cnt = parseInt(cntUsuarios || 0, 10);
        if (est === 'FINALIZADA') return 'tarea-finalizada';
        if (est === 'VENCIDA' || esTareaVencida(tarEst, fechaLimite)) return 'tarea-vencida';
        if (est === 'OBSERVADA') return 'tarea-observada';
        if (est === 'EN_PROCESO') return 'tarea-en-proceso';
        if (est === 'PENDIENTE' && cnt > 0) return 'tarea-asignada';
        return 'tarea-pendiente';
    }

    // Lista de meses YYYY-MM entre dos fechas (incluidas)
    function mesesEntreFechas(desdeYmd, hastaYmd) {
        if (!desdeYmd || !hastaYmd) return [];
        var d = new Date(desdeYmd + 'T12:00:00');
        var h = new Date(hastaYmd + 'T12:00:00');
        if (d > h) return [];
        var out = [];
        var y = d.getFullYear(), m = d.getMonth();
        var hy = h.getFullYear(), hm = h.getMonth();
        while (y < hy || (y === hy && m <= hm)) {
            out.push(y + '-' + pad(m + 1));
            m++;
            if (m > 11) { m = 0; y++; }
        }
        return out;
    }

    // Genérico para facturación: Tar_Periodo (un mes), FechaDesde/FechaHasta (rango) y mesesFact (lista de meses para Contratadas mes a mes)
    function paramsPeriodoGenerico(tipoSel, fechaDesdeId, fechaHastaId, periodoId) {
        var tipo = $(tipoSel).val();
        var params = {};
        var baseDate = new Date();
        var per = baseDate.getFullYear() + '-' + pad(baseDate.getMonth() + 1);
        var fdesde = '', fhasta = '';
        var mesesFact = [per];

        if (tipo === 'mes') {
            fdesde = primerDiaMes(baseDate);
            fhasta = ultimoDiaMes(baseDate);
            mesesFact = [per];
            params.Tar_Periodo = per;
            params.FechaDesde = fdesde;
            params.FechaHasta = fhasta;
            params.mesesFact = mesesFact;
            $(periodoId).val(per);
            return params;
        }
        if (tipo === 'anio') {
            var y = baseDate.getFullYear();
            fdesde = y + '-01-01';
            fhasta = y + '-12-31';
            mesesFact = [];
            for (var i = 1; i <= 12; i++) mesesFact.push(y + '-' + pad(i));
            params.FechaDesde = fdesde;
            params.FechaHasta = fhasta;
            params.mesesFact = mesesFact;
            $(periodoId).val(y + '-01');
            return params;
        }
        if (tipo === 'rango') {
            fdesde = ($(fechaDesdeId).val() || '').trim();
            fhasta = ($(fechaHastaId).val() || '').trim();
            if (!fdesde || !fhasta) {
                fdesde = primerDiaMes(baseDate);
                fhasta = ultimoDiaMes(baseDate);
                $(fechaDesdeId).val(fdesde);
                $(fechaHastaId).val(fhasta);
            }
            mesesFact = mesesEntreFechas(fdesde, fhasta);
            if (mesesFact.length > 0) params.Tar_Periodo = mesesFact[0];
            params.FechaDesde = fdesde;
            params.FechaHasta = fhasta;
            params.mesesFact = mesesFact;
            $(periodoId).val(mesesFact[0] || per);
            return params;
        }
        params.Tar_Periodo = per;
        params.FechaDesde = fdesde || primerDiaMes(baseDate);
        params.FechaHasta = fhasta || ultimoDiaMes(baseDate);
        params.mesesFact = mesesFact;
        $(periodoId).val(per);
        return params;
    }

    // Específico para Tareas: filtra realmente por rango de fechas
    function paramsPeriodoTareas() {
        var tipo = $('#filtroTipoPeriodoTareas').val();
        var params = {};
        if (tipo === 'semana') {
            params.FechaDesde = inicioSemana();
            params.FechaHasta = finSemana();
            return params;
        }
        if (tipo === 'mes') {
            params.FechaDesde = primerDiaMes();
            params.FechaHasta = ultimoDiaMes();
            return params;
        }
        if (tipo === 'rango') {
            var desde = ($('#filtroFechaDesdeTareas').val() || '').trim();
            var hasta = ($('#filtroFechaHastaTareas').val() || '').trim();
            if (!desde || !hasta) {
                var hoy = new Date();
                desde = primerDiaMes(hoy);
                hasta = ultimoDiaMes(hoy);
                $('#filtroFechaDesdeTareas').val(desde);
                $('#filtroFechaHastaTareas').val(hasta);
            }
            params.FechaDesde = desde;
            params.FechaHasta = hasta;
            return params;
        }
        // por seguridad, si algo falla, usar mes actual
        params.FechaDesde = primerDiaMes();
        params.FechaHasta = ultimoDiaMes();
        return params;
    }

    var mesesEs = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    function fechaATexto(ymd) {
        if (!ymd) return '';
        var p = String(ymd).split('-');
        if (p.length < 3) return ymd;
        var d = parseInt(p[2], 10);
        var m = parseInt(p[1], 10) - 1;
        var y = p[0];
        var mes = mesesEs[m] || (m + 1);
        return d + ' de ' + mes + ' de ' + y;
    }
    function actualizarMensajePeriodoTareas() {
        var p = paramsPeriodoTareas();
        var desde = p.FechaDesde || '';
        var hasta = p.FechaHasta || '';
        var msg = '';
        if (desde && hasta) {
            var tipo = $('#filtroTipoPeriodoTareas').val();
            if (tipo === 'semana') msg = 'Esta semana: del ' + fechaATexto(desde) + ' al ' + fechaATexto(hasta);
            else if (tipo === 'mes') msg = 'Este mes: del ' + fechaATexto(desde) + ' al ' + fechaATexto(hasta);
            else msg = 'Rango: del ' + fechaATexto(desde) + ' al ' + fechaATexto(hasta);
        }
        $('#mensajePeriodoTareas').text(msg);
    }

    // Inicializar períodos por defecto para Tareas y Facturación (mes actual)
    (function initPeriodosSecundarios() {
        var hoy = new Date();
        var per = hoy.getFullYear() + '-' + pad(hoy.getMonth() + 1);
        $('#filtroPeriodoTareas').val(per);
        $('#periodoFacturacion').val(per);
    })();

    $('#filtroTipoPeriodoTareas').on('change', function () {
        var v = $(this).val();
        if (v === 'rango') {
            $('#wrapperRangoTareas').show();
            var hoy = new Date();
            $('#filtroFechaDesdeTareas').val(primerDiaMes(hoy));
            $('#filtroFechaHastaTareas').val(ultimoDiaMes(hoy));
        } else {
            $('#wrapperRangoTareas').hide();
        }
        actualizarMensajePeriodoTareas();
    });
    $('#filtroFechaDesdeTareas, #filtroFechaHastaTareas').on('change', function () {
        if ($('#filtroTipoPeriodoTareas').val() === 'rango') actualizarMensajePeriodoTareas();
    });

    $('#filtroTipoPeriodoFact').on('change', function () {
        var v = $(this).val();
        if (v === 'rango') {
            $('#wrapperRangoFact').show();
            var hoy = new Date();
            $('#filtroFechaDesdeFact').val(primerDiaMes(hoy));
            $('#filtroFechaHastaFact').val(ultimoDiaMes(hoy));
        } else {
            $('#wrapperRangoFact').hide();
        }
    });

    function cargarTareasDespacho() {
        var perCod = ($('#filtroUsuarioTareas').val() || '').trim();
        var params = paramsPeriodoTareas();
        params.tareasDespachoGridAjax = 1;
        params.search = ($('#filtroSearchTareas').val() || '').trim();
        if (perCod) params.Per_Cod = perCod;
        $('#gridTareasDespachoContainer').html('<p class="text-muted"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando tareas...</p>');
        $.get(urlBase, params, function (r) {
            var rows = r.rows || [];
            actualizarMensajePeriodoTareas();
            if (rows.length === 0) {
                $('#gridTareasDespachoContainer').html('<p class="text-muted">No hay tareas para el período seleccionado. Ejecute el <strong>Generador de tareas</strong> (módulo Generador) si aplica.</p>');
                return;
            }
            var clientesMap = {}, serviciosMap = {}, mapTareas = {}, diaDeclMap = {};
            $.each(rows, function (i, row) {
                var cli = parseInt(row.Cli_Cod, 10), act = parseInt(row.Act_Cod, 10), ser = parseInt(row.Ser_Cod, 10);
                if (!clientesMap[cli]) clientesMap[cli] = row.Cliente_Nombre || '';
                if (diaDeclMap[cli] === undefined) {
                    var diaDb = (row.Ruc_Dia_Declaracion !== undefined && row.Ruc_Dia_Declaracion !== null && row.Ruc_Dia_Declaracion !== '') ? row.Ruc_Dia_Declaracion : null;
                    var diaCalc = diaDeclaracionDesdeRuc(row.Ruc_Str);
                    diaDeclMap[cli] = diaDb !== null ? diaDb : diaCalc;
                }
                if (!serviciosMap[ser]) serviciosMap[ser] = { Ser_Cod: ser, Ser_Nombre: row.Ser_Nombre || '' };
                mapTareas[cli + '_' + act] = row;
            });
            var serviciosArr = [];
            $.each(serviciosMap, function (cod, s) { serviciosArr.push(s); });
            serviciosArr.sort(function(a,b){ return (a.Ser_Nombre||'').localeCompare(b.Ser_Nombre||''); });
            var clientesOrden = Object.keys(clientesMap).sort(function(a,b){
                var diaA = diaDeclMap[a], diaB = diaDeclMap[b];
                var numA = (diaA === '-' || diaA === undefined || diaA === null || diaA === '') ? 99 : parseInt(diaA, 10);
                var numB = (diaB === '-' || diaB === undefined || diaB === null || diaB === '') ? 99 : parseInt(diaB, 10);
                if (numA !== numB) return numA - numB;
                return (clientesMap[a]||'').localeCompare(clientesMap[b]||'');
            });

            var html = '<table class="aud-grid-tareas"><thead><tr><th class="th-cliente">CLIENTES</th><th class="th-dia-decl" title="Día de declaración según 9no dígito del RUC">DÍA DECL.</th>';
            $.each(serviciosArr, function (i, ser) {
                html += '<th class="th-servicio" title="' + (ser.Ser_Nombre || '') + '">' + (ser.Ser_Nombre || '').toUpperCase() + '</th>';
            });
            html += '</tr></thead><tbody>';
            $.each(clientesOrden, function (i, cliCod) {
                var diaDecl = (diaDeclMap[cliCod] !== undefined && diaDeclMap[cliCod] !== null) ? diaDeclMap[cliCod] : '-';
                var cliNom = clientesMap[cliCod] || '';
                html += '<tr><td class="col-cliente" title="' + cliNom + '">' + cliNom + '</td><td class="col-dia-decl">' + diaDecl + '</td>';
                $.each(serviciosArr, function (j, ser) {
                    var tareasEnCelda = [];
                    $.each(rows, function (k, row) {
                        if (parseInt(row.Cli_Cod, 10) == cliCod && parseInt(row.Ser_Cod, 10) == ser.Ser_Cod) {
                            tareasEnCelda.push(row);
                        }
                    });
                    html += '<td class="celda-servicio">';
                    $.each(tareasEnCelda, function (k, t) {
                        var cls = clasePorEstado(t.Tar_Est, t.Cnt_Usuarios, t.Tar_Fecha_Limite);
                        var estMostrar = esTareaVencida(t.Tar_Est, t.Tar_Fecha_Limite) ? 'VENCIDA' : (t.Tar_Est || '');
                        var tit = (t.Act_Nombre || '') + ': ' + estMostrar + (t.Tar_Fecha_Limite ? ' - Límite ' + t.Tar_Fecha_Limite : '');
                        html += '<div class="actividad-mini ' + cls + '" data-tar-cod="' + (t.Tar_Cod || '') + '" title="' + (tit.replace(/"/g, '&quot;')) + ' (clic para ver detalle)">' + (t.Act_Nombre || '') + '</div>';
                    });
                    html += '</td>';
                });
                html += '</tr>';
            });
            html += '</tbody></table>';
            html += '<div class="aud-leyenda"><strong>Leyenda:</strong> <span class="leyenda-item tarea-pendiente">Pendiente</span> <span class="leyenda-item tarea-asignada">Asignada</span> <span class="leyenda-item tarea-en-proceso">En proceso</span> <span class="leyenda-item tarea-finalizada">Finalizada</span> <span class="leyenda-item tarea-vencida">Vencida</span></div>';
            $('#gridTareasDespachoContainer').html(html);
        }, 'json').fail(function () {
            $('#gridTareasDespachoContainer').html('<p class="text-danger">Error al cargar las tareas.</p>');
        });
    }

    $(document).on('click', '.actividad-mini[data-tar-cod]', function () {
        var tarCod = $(this).data('tar-cod');
        if (!tarCod) return;
        public $modal = $('#modalDetalleTarea');
        public $body = $('#modalDetalleTareaBody');
        $body.html('<p class="text-muted"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando...</p>');
        $modal.modal('show');
        var urlAdjuntos = '../adjuntos/despacho/';
        $.get(urlBase, { detalleTareaDespacho: 1, Tar_Cod: tarCod }, function (r) {
            var t = r.tarea;
            var asignados = r.asignados || [];
            var adjuntos = r.adjuntos || [];
            if (!t) {
                $body.html('<p class="text-danger">No se encontr\u00f3 la tarea.</p>');
                return;
            }
            var estBase = (t.Tar_Est || '').toUpperCase();
            var fecLim = (t.Tar_Fecha_Limite || '').trim();
            if (fecLim === '0000-00-00') fecLim = '';
            var esVencida = false;
            if (estBase !== 'FINALIZADA' && fecLim) {
                var hoy = new Date();
                hoy.setHours(0, 0, 0, 0);
                var parts = fecLim.split('-');
                if (parts.length >= 3) {
                    var fLim = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                    fLim.setHours(0, 0, 0, 0);
                    esVencida = fLim < hoy;
                }
            }
            if (esVencida) estBase = 'VENCIDA';
            // Si está pendiente pero ya tiene asignados, mostrar como "Asignada" (igual que en el grid)
            var estUi = (estBase === 'PENDIENTE' && (asignados || []).length > 0) ? 'ASIGNADA' : estBase;
            var mapaEstados = {
                'PENDIENTE': 'Pendiente',
                'ASIGNADA': 'Asignada',
                'EN_PROCESO': 'En proceso',
                'FINALIZADA': 'Finalizada',
                'VENCIDA': 'Vencida',
                'OBSERVADA': 'Observada'
            };
            var estLabel = mapaEstados[estUi] || t.Tar_Est || '-';
            var pctTotal = 0;
            $.each(asignados, function (i, a) { pctTotal += parseInt(a.TarUsu_Porcentaje || 0, 10); });
            var html = '';
            html += '<div class="detalle-seccion"><h5><span class="glyphicon glyphicon-info-sign"></span> Informaci\u00f3n de la tarea</h5>';
            html += '<table class="table table-condensed" style="margin:0;"><tbody>';
            html += '<tr><td style="width:140px;"><strong>Cliente</strong></td><td>' + (t.Cliente_Nombre || '-') + '</td></tr>';
            html += '<tr><td><strong>Actividad</strong></td><td>' + (t.Act_Nombre || '-') + '</td></tr>';
            html += '<tr><td><strong>Servicio</strong></td><td>' + (t.Ser_Nombre || '-') + '</td></tr>';
            html += '<tr><td><strong>Per\u00edodo</strong></td><td>' + (t.Tar_Periodo || '-') + '</td></tr>';
            html += '<tr><td><strong>Fecha l\u00edmite</strong></td><td>' + (fecLim || '-') + '</td></tr>';
            html += '<tr><td><strong>Estado</strong></td><td>' + estLabel + '</td></tr>';
            if ((t.Tar_Observaciones || '').trim() !== '') html += '<tr><td><strong>Observaciones</strong></td><td>' + (t.Tar_Observaciones || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td></tr>';
            html += '</tbody></table></div>';
            html += '<div class="detalle-seccion"><h5><span class="glyphicon glyphicon-user"></span> Asignados y avance</h5>';
            if (asignados.length === 0) {
                html += '<p class="text-muted">Nadie asignado a esta tarea.</p>';
            } else {
                html += '<table class="tabla-asignados"><thead><tr><th>Personal</th><th>% Avance</th><th>Observaci\u00f3n</th></tr></thead><tbody>';
                $.each(asignados, function (i, a) {
                    var obs = (a.TarUsu_Observacion || '').trim();
                    var pct = a.TarUsu_Porcentaje != null ? Math.min(100, parseInt(a.TarUsu_Porcentaje, 10)) : 0;
                    var clsBarra = pct >= 100 ? 'tarea-finalizada' : (pct > 0 ? 'tarea-en-proceso' : 'tarea-pendiente');
                    var celdaAvance = '<div class="barra-avance-wrap" title="' + pct + '%"><div class="barra-avance-fill ' + clsBarra + '" style="width:' + pct + '%;"></div><span class="barra-avance-text">' + pct + '%</span></div>';
                    html += '<tr><td>' + (a.Personal_Nombre || '-') + '</td><td>' + celdaAvance + '</td><td>' + (obs ? obs.replace(/</g, '&lt;').replace(/>/g, '&gt;') : '-') + '</td></tr>';
                });
                html += '</tbody></table>';
            }
            html += '</div>';
            html += '<div class="detalle-seccion"><h5><span class="glyphicon glyphicon-paperclip"></span> Archivos adjuntos</h5>';
            if (adjuntos.length === 0) {
                html += '<p class="text-muted">No hay archivos adjuntos.</p>';
            } else {
                html += '<div class="detalle-adjuntos" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-start;">';
                for (var a = 0; a < adjuntos.length; a++) {
                    var ruta = (adjuntos[a].Adj_Ruta || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    var nombre = (adjuntos[a].Adj_Nombre || 'Archivo').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    var esImg = /\.(jpe?g|png|gif|webp|bmp)$/i.test(nombre) || (adjuntos[a].Adj_Tipo || '').toLowerCase().indexOf('image') !== -1;
                    var href = urlAdjuntos + ruta;
                    if (esImg) {
                        html += '<a href="' + href + '" target="_blank" rel="noopener" title="' + nombre + '" style="display:block;"><img src="' + href + '" alt="' + nombre + '" style="max-height:90px; max-width:140px; object-fit:contain; border:1px solid #cbd5e1; border-radius:6px;" /></a>';
                    } else {
                        html += '<a href="' + href + '" target="_blank" rel="noopener" class="btn btn-default btn-sm" title="' + nombre + '"><span class="glyphicon glyphicon-download-alt"></span> ' + nombre + '</a>';
                    }
                }
                html += '</div>';
            }
            html += '</div>';
            $body.html(html);
        }, 'json').fail(function () {
            $body.html('<p class="text-danger">Error al cargar el detalle.</p>');
        });
    });

    $('#btnBuscarTareas').on('click', function () {
        cargarTareasDespacho();
    });
    $('#btnExportarTareasExcel').on('click', function () {
        var base = paramsPeriodoTareas();
        if (!base.FechaDesde || !base.FechaHasta) { alert('Seleccione un período para exportar.'); return; }
        var q = 'tareasDespachoExcel=1';
        if (base.FechaDesde) q += '&FechaDesde=' + encodeURIComponent(base.FechaDesde);
        if (base.FechaHasta) q += '&FechaHasta=' + encodeURIComponent(base.FechaHasta);
        q += '&search=' + encodeURIComponent(($('#filtroSearchTareas').val() || '').trim());
        var pc = $('#filtroUsuarioTareas').val();
        if (pc) q += '&Per_Cod=' + encodeURIComponent(pc);
        window.location = urlBase + '?' + q;
    });
    $('#btnExportarTareasPdf').on('click', function () {
        var base = paramsPeriodoTareas();
        if (!base.FechaDesde || !base.FechaHasta) { alert('Seleccione un período para exportar.'); return; }
        var q = '';
        q += 'FechaDesde=' + encodeURIComponent(base.FechaDesde);
        q += '&FechaHasta=' + encodeURIComponent(base.FechaHasta);
        q += '&search=' + encodeURIComponent(($('#filtroSearchTareas').val() || '').trim());
        var pc = $('#filtroUsuarioTareas').val();
        if (pc) q += '&Per_Cod=' + encodeURIComponent(pc);
        var urlPdf = urlBase.replace('aud_mod_despacho_operativo_1.0.php', 'aud_export_tareas_pdf.php');
        window.location = urlPdf + '?' + q;
    });
    var select2Opts = { language: { noResults: function() { return 'No se encontraron resultados'; }, searching: function() { return 'Buscando...'; } }, allowClear: true, placeholder: 'Todos', width: '220px' };
    function initSelect2NoClickSelect($el, opts) {
        if (!$el.length || typeof $el.select2 !== 'function') return;
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        $el.select2(opts || select2Opts);
        $el.off('select2:open.s2nc select2:selecting.s2nc').on('select2:open.s2nc', function () { $el.data('select2OpenAt', Date.now()); }).on('select2:selecting.s2nc', function (ev) {
            var t = $el.data('select2OpenAt'); if (t && (Date.now() - t) < 300) ev.preventDefault();
        });
    }
    $('a[href="#tab-tareas"]').on('shown.bs.tab', function () {
        public $sel = $('#filtroUsuarioTareas');
        if ($sel.find('option').length <= 1) {
            $.get(urlBase, { listarPersonalOperativo: 1 }, function (r) {
                var rows = r.rows || [];
                $.each(rows, function (i, row) {
                    $sel.append('<option value="' + (row.Per_Cod || '') + '">' + (row.Nombre || '').replace(/</g, '&lt;') + '</option>');
                });
                if ($sel.find('option').length > 1) initSelect2NoClickSelect($sel);
            }, 'json');
        }
        actualizarMensajePeriodoTareas();
        public $c = $('#gridTareasDespachoContainer');
        if ($c.is(':empty') || $c.find('table.aud-grid-tareas').length === 0) {
            $c.html('<p class="text-muted">Seleccione el período y pulse <strong>Buscar</strong> para ver las tareas.</p>');
        }
    });

    function periodoFactParams() {
        return paramsPeriodoGenerico('#filtroTipoPeriodoFact', '#filtroFechaDesdeFact', '#filtroFechaHastaFact', '#periodoFacturacion');
    }

    function cargarReporteContratadas() {
        var p = periodoFactParams();
        public $cont = $('#reporteContratadasContainer');
        var meses = (p.mesesFact && p.mesesFact.length) ? p.mesesFact : (p.Tar_Periodo ? [p.Tar_Periodo] : []);
        if (meses.length === 0) {
            $cont.html('<p class="text-warning" style="padding:20px;">Seleccione el período y pulse Vista previa.</p>');
            return;
        }
        $cont.html('<p class="text-muted" style="padding:20px;"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando...</p>');
        if (meses.length === 1) {
            var params = { reporteContratadas: 1, Tar_Periodo: meses[0] };
            $.get(urlBase, params, function (r) {
                var rows = r.rows || [];
                if (rows.length === 0) {
                    $cont.html('<p class="text-muted" style="padding:20px;">No hay clientes con contrato vigente' + (r.periodo ? ' en el período ' + r.periodo : '') + '.</p>');
                    return;
                }
                var totalValor = 0;
                for (var i = 0; i < rows.length; i++) { totalValor += parseFloat(rows[i].Valor_Contrato) || 0; }
                var totalValorStr = totalValor.toFixed(2);
                var html = '<p class="text-muted small" style="padding:0 0 8px 0; margin:0;">' + (r.periodo ? 'Período: <strong>' + r.periodo + '</strong> &bull; ' : '') + rows.length + ' cliente(s)</p>';
                html += '<table class="aud-tabla table table-condensed table-bordered" style="margin:0; font-size:12px;"><thead><tr style="background:#72A1CF; color:white;">';
                html += '<th>Cliente</th><th>Valor contrato acordado</th><th>Nº contrato</th><th>Ver detalle</th></tr></thead><tbody>';
                for (var i = 0; i < rows.length; i++) {
                    var row = rows[i];
                    var val = row.Valor_Contrato != null ? row.Valor_Contrato : '0';
                    var nombre = (row.Cliente_Nombre || '').replace(/"/g, '&quot;').replace(/</g, '&lt;');
html += '<tr><td>' + (row.Cliente_Nombre || '').replace(/</g, '&lt;') + '</td><td>' + val + '</td><td>' + (row.Con_Numero || '').replace(/</g, '&lt;') + '</td><td><a href="#" class="ver-detalle-contrato" data-cli="' + (row.Cli_Cod || '') + '" data-nombre="' + nombre + '" data-periodo="' + (meses[0] || '') + '">Ver Detalle</a></td></tr>';
            }
            html += '<tr style="background:#f1f5f9; font-weight:700;"><td>TOTAL</td><td>' + totalValorStr + '</td><td></td><td></td></tr></tbody></table>';
            $cont.html(html);
        }, 'json').fail(function () {
            $cont.html('<p class="text-danger" style="padding:20px;">Error al cargar contratadas.</p>');
        });
        return;
    }
    // Varios meses: facturación mes a mes
        var results = [];
        var pend = meses.length;
        function renderContratadasMesAMes() {
            var html = '';
            for (var j = 0; j < meses.length; j++) {
                var rr = results[j];
                var rows = (rr && rr.rows) ? rr.rows : [];
                var periodo = (rr && rr.periodo) ? rr.periodo : meses[j];
                var parts = String(periodo).split('-');
                var y = parts[0];
                var m = parseInt(parts[1], 10) || 1;
                var nombreMes = (mesesEs[m - 1] || periodo).charAt(0).toUpperCase() + (mesesEs[m - 1] || periodo).slice(1);
                var titulo = nombreMes + ' ' + y;
                html += '<div style="margin-bottom:16px;"><h5 style="margin:12px 0 6px 0; color:#2C5D94;">' + titulo + '</h5>';
                if (rows.length === 0) {
                    html += '<p class="text-muted small">No hay clientes con contrato vigente en este mes.</p></div>';
                    continue;
                }
                var totalValor = 0;
                for (var k = 0; k < rows.length; k++) totalValor += parseFloat(rows[k].Valor_Contrato) || 0;
                html += '<table class="aud-tabla table table-condensed table-bordered" style="margin:0 0 16px 0; font-size:12px;"><thead><tr style="background:#72A1CF; color:white;">';
                html += '<th>Cliente</th><th>Valor contrato acordado</th><th>Nº contrato</th><th>Ver detalle</th></tr></thead><tbody>';
                for (var k = 0; k < rows.length; k++) {
                    var row = rows[k];
                    var val = row.Valor_Contrato != null ? row.Valor_Contrato : '0';
                    var nombre = (row.Cliente_Nombre || '').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                    html += '<tr><td>' + (row.Cliente_Nombre || '').replace(/</g, '&lt;') + '</td><td>' + val + '</td><td>' + (row.Con_Numero || '').replace(/</g, '&lt;') + '</td><td><a href="#" class="ver-detalle-contrato" data-cli="' + (row.Cli_Cod || '') + '" data-nombre="' + nombre + '" data-periodo="' + (meses[j] || '') + '">Ver Detalle</a></td></tr>';
                }
                html += '<tr style="background:#f1f5f9; font-weight:700;"><td>TOTAL</td><td>' + totalValor.toFixed(2) + '</td><td></td><td></td></tr></tbody></table></div>';
            }
            $cont.html(html);
        }
        for (var idx = 0; idx < meses.length; idx++) {
            (function (i) {
                $.get(urlBase, { reporteContratadas: 1, Tar_Periodo: meses[i] }, function (r) {
                    results[i] = r;
                    pend--;
                    if (pend === 0) renderContratadasMesAMes();
                }, 'json').fail(function () {
                    results[i] = { rows: [], periodo: meses[i] };
                    pend--;
                    if (pend === 0) renderContratadasMesAMes();
                });
            })(idx);
        }
    }

    function cargarReporteExtras() {
        var p = periodoFactParams();
        if (!p.Tar_Periodo && !p.FechaDesde) {
            $('#reporteExtrasContainer').html('<p class="text-warning" style="padding:20px;">Seleccione el período y vuelva a esta pestaña.</p>');
            return;
        }
        var criterio = $('#criterioFacturacion').val() || 'A';
        public $cont = $('#reporteExtrasContainer');
        $cont.html('<p class="text-muted" style="padding:20px;"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando...</p>');
        var params = { reporteFacturacionPreview: 1, Criterio: criterio };
        if (p.FechaDesde && p.FechaHasta) { params.FechaDesde = p.FechaDesde; params.FechaHasta = p.FechaHasta; }
        else if (p.Tar_Periodo) params.Tar_Periodo = p.Tar_Periodo;
        $.get(urlBase, params, function (r) {
            var rows = (r.rows || []).filter(function (row) {
                return (row.Act_Facturable || '').toUpperCase() === 'S' && (row.Incluida_Contrato || '').toUpperCase() !== 'S';
            });
            if (rows.length === 0) {
                $cont.html('<p class="text-muted" style="padding:20px;">No hay actividades extras (no incluidas en el contrato) para el período ' + (r.periodo || per) + '.</p>');
                return;
            }
            var totalAct = 0;
            for (var i = 0; i < rows.length; i++) { totalAct += parseFloat(rows[i].Act_Valor) || 0; }
            var critLabel = criterio === 'B' ? 'Todas generadas' : 'Solo finalizadas';
            var html = '<p class="text-muted small" style="padding:0 0 8px 0; margin:0;">Período: <strong>' + (r.periodo || $('#periodoFacturacion').val() || '') + '</strong> &bull; ' + critLabel + ' &bull; ' + rows.length + ' registro(s)</p>';
            html += '<table class="aud-tabla table table-condensed table-bordered" style="margin:0; font-size:12px;"><thead><tr style="background:#72A1CF; color:white;">';
            html += '<th>Cliente</th><th>Servicio</th><th>Actividad</th><th>% Avance</th><th>Valor Act.</th></tr></thead><tbody>';
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var pct = (row.Tar_Est || '').toUpperCase() === 'FINALIZADA'
                    ? 100
                    : Math.min(100, parseInt(row.Tar_Avance || 0, 10));
                var tieneAvance = parseInt(row.Tar_Avance || 0, 10) > 0;
                var clsBarra = clasePorEstado(row.Tar_Est, tieneAvance ? 1 : 0, row.Tar_Fecha_Limite);
                var celdaAvance = '<div class="barra-avance-wrap" title="' + pct + '%"><div class="barra-avance-fill ' + clsBarra + '" style="width:' + pct + '%;"></div><span class="barra-avance-text">' + pct + '%</span></div>';
                html += '<tr><td>' + (row.Cliente_Nombre || '').replace(/</g, '&lt;') + '</td><td>' + (row.Ser_Nombre || '').replace(/</g, '&lt;') + '</td><td>' + (row.Act_Nombre || '').replace(/</g, '&lt;') + '</td><td>' + celdaAvance + '</td><td>' + (row.Act_Valor != null ? row.Act_Valor : '0') + '</td></tr>';
            }
            html += '<tr style="background:#f1f5f9; font-weight:700;"><td colspan="4">TOTAL</td><td>' + totalAct.toFixed(2) + '</td></tr></tbody></table>';
            $cont.html(html);
        }, 'json').fail(function () {
            $cont.html('<p class="text-danger" style="padding:20px;">Error al cargar extras.</p>');
        });
    }

    $('a[href="#subtab-extras"]').on('shown.bs.tab', function () { cargarReporteExtras(); });

    $(document).on('click', '.ver-detalle-contrato', function (e) {
        e.preventDefault();
        var cli = $(this).data('cli');
        var nombre = $(this).data('nombre') || 'Cliente';
        var periodo = $(this).data('periodo');
        if (!periodo) { alert('Período no definido para este detalle.'); return; }
        var criterio = $('#criterioFacturacion').val() || 'A';
        $('#modalDetalleContratoTitle').html('<span class="glyphicon glyphicon-list"></span> Actividades del contrato: ' + nombre);
        $('#modalDetalleContratoBody').html('<p class="text-muted"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando...</p>');
        $('#modalDetalleContrato').modal('show');
        var params = { reporteFacturacionPreview: 1, Criterio: criterio, Cli_Cod: cli, Tar_Periodo: periodo };
        $.get(urlBase, params, function (r) {
            var allRows = r.rows || [];
            var rows = allRows.filter(function (row) { return (row.Incluida_Contrato || '').toUpperCase() === 'S'; });
            public $body = $('#modalDetalleContratoBody');
            if (rows.length === 0) {
                $body.html('<p class="text-muted">No hay actividades incluidas en el contrato para este cliente en ' + (r.periodo || periodo) + '. Si el mes no tiene tareas, no se mostrará ninguna actividad.</p>');
                return;
            }
            var html = '<p class="text-muted small" style="margin-bottom:10px;">Mes: <strong>' + (r.periodo || periodo) + '</strong> &bull; ' + rows.length + ' actividad(es) incluidas</p>';
            html += '<table class="table table-condensed table-bordered" style="font-size:12px;"><thead><tr style="background:#72A1CF; color:white;">';
            html += '<th>Servicio</th><th>Actividad</th><th>% Avance</th></tr></thead><tbody>';
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var pct = (row.Tar_Est || '').toUpperCase() === 'FINALIZADA' ? 100 : Math.min(100, parseInt(row.Tar_Avance || 0, 10));
                var tieneAvance = (parseInt(row.Tar_Avance || 0, 10) > 0);
                var clsBarra = clasePorEstado(row.Tar_Est, tieneAvance ? 1 : 0, row.Tar_Fecha_Limite);
                html += '<tr><td>' + (row.Ser_Nombre || '').replace(/</g, '&lt;') + '</td><td>' + (row.Act_Nombre || '').replace(/</g, '&lt;') + '</td><td>';
                html += '<div class="barra-avance-wrap" title="' + pct + '%"><div class="barra-avance-fill ' + clsBarra + '" style="width:' + pct + '%;"></div><span class="barra-avance-text">' + pct + '%</span></div></td></tr>';
            }
            html += '</tbody></table>';
            $body.html(html);
        }, 'json').fail(function () {
            $('#modalDetalleContratoBody').html('<p class="text-danger">Error al cargar las actividades.</p>');
        });
    });

    $('#btnVistaPreviaFacturacion').on('click', function () {
        var p = periodoFactParams();
        if (!p.Tar_Periodo && !p.FechaDesde) { alert('Seleccione el período.'); return; }
        var criterio = $('#criterioFacturacion').val() || 'A';
        cargarReporteContratadas();
        public $cont = $('#reporteFacturacionPreviewContainer');
        $('a[href="#subtab-contratadas"]').tab('show');
        $cont.html('<p class="text-muted" style="padding:20px;"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando...</p>');
        var params = { reporteFacturacionPreview: 1, Criterio: criterio };
        if (p.FechaDesde && p.FechaHasta) { params.FechaDesde = p.FechaDesde; params.FechaHasta = p.FechaHasta; }
        else if (p.Tar_Periodo) params.Tar_Periodo = p.Tar_Periodo;
        $.get(urlBase, params, function (r) {
            var rows = r.rows || [];
            if (rows.length === 0) {
                $cont.html('<p class="text-muted" style="padding:20px;">No hay registros para el período ' + (r.periodo || p.Tar_Periodo || '') + ' con el criterio seleccionado.</p>');
                return;
            }
            var totalAct = 0, totalSer = 0;
            for (var i = 0; i < rows.length; i++) { totalAct += parseFloat(rows[i].Act_Valor) || 0; totalSer += parseFloat(rows[i].Ser_Valor) || 0; }
            var critLabel = criterio === 'B' ? 'Todas generadas' : 'Solo finalizadas';
            var perLabel = r.periodo || (p.FechaDesde && p.FechaHasta ? p.FechaDesde + ' / ' + p.FechaHasta : '') || p.Tar_Periodo || '';
            var html = '<p class="text-muted small" style="padding:0 0 8px 0; margin:0;">Período: <strong>' + perLabel + '</strong> &bull; Criterio: ' + critLabel + ' &bull; ' + rows.length + ' registro(s)</p>';
            html += '<table class="aud-tabla table table-condensed table-bordered" style="margin:0; font-size:12px;"><thead><tr style="background:#72A1CF; color:white;">';
            html += '<th>Cliente</th><th>Servicio</th><th>Actividad</th><th>Facturable Act.</th><th>Valor Act.</th><th>Facturable Ser.</th><th>Valor Ser.</th></tr></thead><tbody>';
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                html += '<tr><td>' + (row.Cliente_Nombre || '').replace(/</g, '&lt;') + '</td><td>' + (row.Ser_Nombre || '').replace(/</g, '&lt;') + '</td><td>' + (row.Act_Nombre || '').replace(/</g, '&lt;') + '</td>';
                html += '<td>' + (row.Act_Facturable || 'N') + '</td><td>' + (row.Act_Valor != null ? row.Act_Valor : '0') + '</td>';
                html += '<td>' + (row.Ser_Facturable || 'N') + '</td><td>' + (row.Ser_Valor != null ? row.Ser_Valor : '0') + '</td></tr>';
            }
            html += '<tr style="background:#f1f5f9; font-weight:700;"><td colspan="4">TOTAL</td><td>' + totalAct.toFixed(2) + '</td><td></td><td>' + totalSer.toFixed(2) + '</td></tr></tbody></table>';
            $cont.html(html);
        }, 'json').fail(function () {
            $cont.html('<p class="text-danger" style="padding:20px;">Error al cargar la vista previa.</p>');
        });
    });

    $('.btnExportarTablaExcel').on('click', function () {
        var tipo = $(this).data('tipo');
        var p = periodoFactParams();
        if (tipo !== 'contratadas' && !p.Tar_Periodo && !p.FechaDesde) { alert('Seleccione el período.'); return; }
        var q = 'reporteFacturacionExcel=1&tipo=' + encodeURIComponent(tipo) + '&Criterio=' + ($('#criterioFacturacion').val() || 'A');
        if (p.FechaDesde && p.FechaHasta) { q += '&FechaDesde=' + encodeURIComponent(p.FechaDesde) + '&FechaHasta=' + encodeURIComponent(p.FechaHasta); }
        if (p.Tar_Periodo) q += '&Tar_Periodo=' + encodeURIComponent(p.Tar_Periodo);
        window.location = urlBase + '?' + q;
    });
    $('.btnExportarTablaPdf').on('click', function () {
        var tipo = $(this).data('tipo');
        var p = periodoFactParams();
        if (tipo !== 'contratadas' && !p.Tar_Periodo && !p.FechaDesde) { alert('Seleccione el período.'); return; }
        var q = 'tipo=' + encodeURIComponent(tipo) + '&Criterio=' + ($('#criterioFacturacion').val() || 'A');
        if (p.FechaDesde && p.FechaHasta) { q += '&FechaDesde=' + encodeURIComponent(p.FechaDesde) + '&FechaHasta=' + encodeURIComponent(p.FechaHasta); }
        if (p.Tar_Periodo) q += '&Tar_Periodo=' + encodeURIComponent(p.Tar_Periodo);
        var urlPdf = urlBase.replace('aud_mod_despacho_operativo_1.0.php', 'aud_export_facturacion_pdf.php');
        window.location = urlPdf + '?' + q;
    });
})();
</script>
</body>
</html>
