<?php
/**
 * EXA Adquisiciones - Dashboard Gerencial de Flujos
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');
require_once('../LOGICA/adq_adquisiciones_log.php');

/**
 * Calcula categoria SLA de un proceso (misma logica del semaforo de la tabla).
 * @return array{cat:string,elapsed:float,limit:?float,ratio:?float,semaforo:string,label:string}
 */
function adq_dashboard_calcular_sla_proceso($p) {
    $elapsed_days = 0.0;
    $limit_days = (isset($p['Sla_Dias']) && $p['Sla_Dias'] !== null && $p['Sla_Dias'] !== '')
        ? floatval($p['Sla_Dias'])
        : null;
    $fec_ini = !empty($p['Ins_Fec_Ini']) ? strtotime($p['Ins_Fec_Ini']) : false;
    if ($fec_ini === false) {
        return array(
            'cat' => 'sin_sla',
            'elapsed' => 0.0,
            'limit' => $limit_days,
            'ratio' => null,
            'semaforo' => 'bg-semaforo-gris',
            'label' => 'Sin SLA'
        );
    }
    if (isset($p['Ins_Est']) && $p['Ins_Est'] === 'P') {
        $elapsed_days = (time() - $fec_ini) / 86400.0;
    } else {
        $fec_fin = !empty($p['Ins_Fec_Fin']) ? strtotime($p['Ins_Fec_Fin']) : time();
        $elapsed_days = ($fec_fin - $fec_ini) / 86400.0;
    }
    if ($limit_days === null || $limit_days <= 0) {
        return array(
            'cat' => 'sin_sla',
            'elapsed' => $elapsed_days,
            'limit' => null,
            'ratio' => null,
            'semaforo' => 'bg-semaforo-gris',
            'label' => 'Sin SLA'
        );
    }
    $ratio = $elapsed_days / $limit_days;
    if ($ratio < 0.8) {
        return array(
            'cat' => 'a_tiempo',
            'elapsed' => $elapsed_days,
            'limit' => $limit_days,
            'ratio' => $ratio,
            'semaforo' => 'bg-semaforo-verde',
            'label' => 'A tiempo'
        );
    }
    if ($ratio <= 1.0) {
        return array(
            'cat' => 'en_riesgo',
            'elapsed' => $elapsed_days,
            'limit' => $limit_days,
            'ratio' => $ratio,
            'semaforo' => 'bg-semaforo-amarillo',
            'label' => 'En riesgo'
        );
    }
    return array(
        'cat' => 'vencido',
        'elapsed' => $elapsed_days,
        'limit' => $limit_days,
        'ratio' => $ratio,
        'semaforo' => 'bg-semaforo-rojo',
        'label' => 'Vencido'
    );
}

function adq_dashboard_esc($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);
$wf_mgr->ensureUtf8Charset();
$obBD_adq = new adq_adquisiciones_log($obBD_conexion);

// Inhabilitar / rehabilitar proceso (monitor Todos los Procesos)
if (isset($_POST['ajax_toggle_proceso_inhabilitado']) || isset($_GET['ajax_toggle_proceso_inhabilitado'])) {
    header('Content-Type: application/json; charset=UTF-8');
    if (!$wf_mgr->verificarAccesoVentana('dashboard', 'dashboard_general')) {
        echo json_encode(array('success' => false, 'message' => 'Acceso denegado.'));
        exit;
    }
    $sol_cod = intval(isset($_POST['sol_cod']) ? $_POST['sol_cod'] : (isset($_GET['sol_cod']) ? $_GET['sol_cod'] : 0));
    $accion = isset($_POST['accion']) ? trim((string)$_POST['accion']) : (isset($_GET['accion']) ? trim((string)$_GET['accion']) : '');
    $comentario = isset($_POST['comentario']) ? trim((string)$_POST['comentario']) : '';
    $inhabilitar = ($accion !== 'habilitar');
    $res = $obBD_adq->setProcesoInhabilitado($sol_cod, intval($Ses_Emp_Cod), $inhabilitar, $comentario);
    echo json_encode($res);
    exit;
}

// Anular flujo modelo (nodos Nod_Est = I)
if (isset($_POST['ajax_anular_flujo']) || isset($_GET['ajax_anular_flujo'])) {
    header('Content-Type: application/json; charset=UTF-8');
    if (!$wf_mgr->verificarAccesoVentana('dashboard', 'dashboard_general')) {
        echo json_encode(array('success' => false, 'message' => 'Acceso denegado.'));
        exit;
    }
    $wfm_cod = intval(isset($_POST['wfm_cod']) ? $_POST['wfm_cod'] : (isset($_GET['wfm_cod']) ? $_GET['wfm_cod'] : 0));
    $res = $wf_mgr->anularFlujoModelo($wfm_cod, intval($Ses_Emp_Cod));
    echo json_encode($res);
    exit;
}

// Descargar ZIP con todos los documentos de la solicitud (modal seguimiento)
if (isset($_GET['ajax_descargar_docs_zip'])) {
    $sol_cod = intval(isset($_GET['sol_cod']) ? $_GET['sol_cod'] : 0);
    if ($sol_cod <= 0) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array('success' => false, 'message' => 'Codigo de solicitud invalido.'));
        exit;
    }
    if (!$wf_mgr->verificarAccesoVentana('dashboard', 'dashboard_general')) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array('success' => false, 'message' => 'Acceso denegado.'));
        exit;
    }
    $sol_emp = $obBD_con1->getRowConsultaSql(
        "SELECT Emp_Cod FROM adq_solicitudes WHERE Sol_Cod = $sol_cod LIMIT 1;",
        $obBD_conexion
    );
    if (empty($sol_emp) || intval($sol_emp['Emp_Cod']) !== intval($Ses_Emp_Cod)) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array('success' => false, 'message' => 'No tiene acceso a esta solicitud.'));
        exit;
    }

    $resultado = $obBD_adq->generarZipDocumentosSolicitud($sol_cod);
    if (empty($resultado['success']) || empty($resultado['path']) || !is_file($resultado['path'])) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array(
            'success' => false,
            'message' => !empty($resultado['message']) ? $resultado['message'] : 'No se pudo generar el ZIP.'
        ));
        exit;
    }

    $filename = !empty($resultado['filename']) ? $resultado['filename'] : ('documentos_sol_' . $sol_cod . '.zip');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($resultado['path']));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    readfile($resultado['path']);
    @unlink($resultado['path']);
    exit;
}

// Verificar acceso a la ventana 'dashboard' y pesta?a 'dashboard_general'
if (!$wf_mgr->verificarAccesoVentana('dashboard', 'dashboard_general')) {
    echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
    exit;
}

// 1. Estad?sticas de Resumen
$stats = $obBD_con1->getRowConsultaSql("
    SELECT 
        COUNT(CASE WHEN Sol_Est = 'E' THEN 1 END) as Activos,
        COUNT(CASE WHEN Sol_Est = 'A' THEN 1 END) as Aprobados,
        COUNT(CASE WHEN Sol_Est = 'R' THEN 1 END) as Rechazados,
        COUNT(CASE WHEN Sol_Est = 'O' THEN 1 END) as Observados,
        IFNULL(AVG(CASE WHEN Sol_Est = 'A' THEN TIMESTAMPDIFF(HOUR, Sol_Fec, Sol_Sys) / 24.0 END), 0) as Tiempo_Promedio
    FROM adq_solicitudes 
    WHERE Emp_Cod = $Ses_Emp_Cod;", $obBD_conexion);

// 2. Cuellos de Botella: Solicitudes por etapa activa actual
$cuellos = $obBD_con1->getArrayConsultaSql("
    SELECT n.Nod_Nom, d.Wde_Des AS Dep_Des, COUNT(i.Ins_Cod) as Total
    FROM wf_instancias i
    INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    LEFT JOIN wf_departamentos d ON d.Wde_Cod = n.Dep_Cod
    WHERE i.Ins_Est = 'P' AND n.Wfm_Cod IN (SELECT Wfm_Cod FROM wf_flujos_modelos WHERE Emp_Cod = $Ses_Emp_Cod)
    GROUP BY n.Nod_Nom, d.Wde_Des
    ORDER BY Total DESC;", $obBD_conexion);

// 3. Ranking de Departamentos por SLA de atenci?n
$departamentos_ranking = $obBD_con1->getArrayConsultaSql("
    SELECT d.Wde_Des AS Dep_Des,
           COUNT(h.Isn_Cod) as Resoluciones,
           IFNULL(AVG(TIMESTAMPDIFF(HOUR, h.Isn_Fec, (SELECT MIN(h2.Isn_Fec) FROM wf_instancias_nodos h2 WHERE h2.Ins_Cod = h.Ins_Cod AND h2.Isn_Cod > h.Isn_Cod)) / 24.0), 0) as Tiempo_Atencion
    FROM wf_instancias_nodos h
    INNER JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
    INNER JOIN wf_departamentos d ON d.Wde_Cod = h.Dep_Cod
    WHERE h.Isn_Acc IN ('APROBAR', 'OBSERVAR', 'DEVOLVER') AND d.Emp_Cod = $Ses_Emp_Cod
    GROUP BY d.Wde_Des
    ORDER BY Tiempo_Atencion ASC;", $obBD_conexion);

// 4. Vol?menes Mensuales de Solicitudes
$volumenes = $obBD_con1->getArrayConsultaSql("
    SELECT DATE_FORMAT(Sol_Fec, '%Y-%m') as Mes, COUNT(Sol_Cod) as Total, SUM(Sol_Val_Est) as Monto
    FROM adq_solicitudes
    WHERE Emp_Cod = $Ses_Emp_Cod AND Sol_Fec >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY Mes
    ORDER BY Mes ASC;", $obBD_conexion);

// 5. Todos los Procesos (Monitor de Gerencia)
$es_gerencial_admin = true; // Dejado abierto para que el perfil se asigne manualmente por seguridad.php / permisos nativos de EXA

$total_activos = 0;
$total_a_tiempo = 0;
$total_en_riesgo = 0;
$total_vencidos = 0;
$total_sin_sla = 0;
$procesos = array();
$departamentos = array();
$tipos_req = array();

$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$filtro_depto = isset($_GET['filtro_depto']) ? intval($_GET['filtro_depto']) : 0;
$filtro_tipo = isset($_GET['filtro_tipo']) ? intval($_GET['filtro_tipo']) : 0;
$filtro_sla = isset($_GET['filtro_sla']) ? trim((string)$_GET['filtro_sla']) : '';
$sla_filtros_ok = array('a_tiempo', 'en_riesgo', 'vencido', 'sin_sla');
if (!in_array($filtro_sla, $sla_filtros_ok, true)) {
    $filtro_sla = '';
}
$procesos_page_size = isset($_GET['page_size']) ? intval($_GET['page_size']) : 20;
if (!in_array($procesos_page_size, array(10, 20, 25, 50), true)) {
    $procesos_page_size = 20;
}
$procesos_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($procesos_page < 1) {
    $procesos_page = 1;
}
$procesos_total = 0;
$procesos_pages = 1;

if ($es_gerencial_admin) {
    // Calcular m?tricas de SLA generales para todos los procesos activos
    $sql_metrics = "
        SELECT i.Ins_Fec_Ini, COALESCE(s.Sol_Tiempo_Est, tr.Trq_Tiempo_Est) AS Sla_Dias
        FROM wf_instancias i
        INNER JOIN adq_solicitudes s ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod
        INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
        WHERE s.Emp_Cod = $Ses_Emp_Cod AND i.Ins_Est = 'P' AND s.Sol_Est <> 'I';";
    $active_processes = $obBD_con1->getArrayConsultaSql($sql_metrics, $obBD_conexion);

    $now = time();
    foreach ($active_processes as $ap) {
        $total_activos++;
        if ($ap['Sla_Dias'] === null || $ap['Sla_Dias'] === '') {
            $total_sin_sla++;
        } else {
            $fec_ini = strtotime($ap['Ins_Fec_Ini']);
            $elapsed_days = ($now - $fec_ini) / 86400.0;
            $limit_days = floatval($ap['Sla_Dias']);
            $ratio = $elapsed_days / $limit_days;
            
            if ($ratio < 0.8) {
                $total_a_tiempo++;
            } elseif ($ratio >= 0.8 && $ratio <= 1.0) {
                $total_en_riesgo++;
            } else {
                $total_vencidos++;
            }
        }
    }

    // Obtener listas para filtros
    $departamentos = $obBD_con1->getArrayConsultaSql("SELECT Wde_Cod AS Dep_Cod, Wde_Des AS Dep_Des FROM wf_departamentos WHERE Emp_Cod = $Ses_Emp_Cod AND Wde_Est = 'A' ORDER BY Wde_Des ASC;", $obBD_conexion);
    $tipos_req = $obBD_con1->getArrayConsultaSql("SELECT Trq_Cod, Trq_Des FROM adq_tipos_requerimientos WHERE Emp_Cod = $Ses_Emp_Cod AND Trq_Est = 'A' ORDER BY Trq_Des ASC;", $obBD_conexion);

    // Construir consulta para la tabla
    $where_clauses = array("s.Emp_Cod = $Ses_Emp_Cod");

    if (!empty($filtro_estado)) {
        if ($filtro_estado === 'P') {
            $where_clauses[] = "i.Ins_Est = 'P' AND s.Sol_Est <> 'I'";
        } elseif ($filtro_estado === 'F') {
            $where_clauses[] = "i.Ins_Est = 'F'";
        } elseif ($filtro_estado === 'R') {
            $where_clauses[] = "i.Ins_Est = 'R'";
        } elseif ($filtro_estado === 'O') {
            $where_clauses[] = "s.Sol_Est = 'O'";
        } elseif ($filtro_estado === 'I') {
            $where_clauses[] = "s.Sol_Est = 'I'";
        }
    }

    if ($filtro_depto > 0) {
        $where_clauses[] = "n.Dep_Cod = $filtro_depto";
    }

    if ($filtro_tipo > 0) {
        $where_clauses[] = "s.Trq_Cod = $filtro_tipo";
    }

    // Categor?a SLA en SQL (misma l?gica del sem?foro) para filtrar/paginar en BD.
    $sla_sql_expr = "(
        CASE
            WHEN COALESCE(s.Sol_Tiempo_Est, tr.Trq_Tiempo_Est) IS NULL
              OR COALESCE(s.Sol_Tiempo_Est, tr.Trq_Tiempo_Est) <= 0
              OR i.Ins_Fec_Ini IS NULL
            THEN 'sin_sla'
            WHEN (
                TIMESTAMPDIFF(
                    SECOND,
                    i.Ins_Fec_Ini,
                    CASE WHEN i.Ins_Est = 'P' THEN NOW() ELSE IFNULL(i.Ins_Fec_Fin, NOW()) END
                ) / 86400.0
            ) / COALESCE(s.Sol_Tiempo_Est, tr.Trq_Tiempo_Est) < 0.8 THEN 'a_tiempo'
            WHEN (
                TIMESTAMPDIFF(
                    SECOND,
                    i.Ins_Fec_Ini,
                    CASE WHEN i.Ins_Est = 'P' THEN NOW() ELSE IFNULL(i.Ins_Fec_Fin, NOW()) END
                ) / 86400.0
            ) / COALESCE(s.Sol_Tiempo_Est, tr.Trq_Tiempo_Est) <= 1.0 THEN 'en_riesgo'
            ELSE 'vencido'
        END
    )";
    if ($filtro_sla !== '') {
        $where_clauses[] = "$sla_sql_expr = '$filtro_sla'";
    }

    $sql_from = "
        FROM wf_instancias i
        INNER JOIN adq_solicitudes s ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod
        INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
        LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
        LEFT JOIN wf_departamentos d ON d.Wde_Cod = n.Dep_Cod
        LEFT JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
        LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
        WHERE " . implode(" AND ", $where_clauses);

    $count_row = $obBD_con1->getRowConsultaSql(
        "SELECT COUNT(*) AS cnt $sql_from;",
        $obBD_conexion
    );
    $procesos_total = !empty($count_row['cnt']) ? intval($count_row['cnt']) : 0;
    $procesos_pages = max(1, (int)ceil($procesos_total / $procesos_page_size));
    if ($procesos_page > $procesos_pages) {
        $procesos_page = $procesos_pages;
    }
    $offset = ($procesos_page - 1) * $procesos_page_size;

    $sql_select = "
        SELECT i.Ins_Cod, i.Ins_Fec_Ini, i.Ins_Fec_Fin, i.Ins_Est, i.Nod_Act, i.Wfm_Cod,
               s.Sol_Cod, s.Sol_Num, s.Sol_Fec, s.Sol_Val_Est, s.Sol_Est, s.Sol_Pri,
               tr.Trq_Des, COALESCE(s.Sol_Tiempo_Est, tr.Trq_Tiempo_Est) AS Sla_Dias,
               n.Nod_Nom, d.Wde_Des AS Dep_Des,
               u.Usu_Ced as Usu_Nom, p.Prs_Nom, p.Prs_Ape
        $sql_from
        ORDER BY s.Sol_Fec DESC, s.Sol_Cod DESC";

    // PDF exporta todo el resultado filtrado; la vista pagina en servidor.
    $es_export_pdf = isset($_GET['ajax_exportar_procesos_pdf']);
    if ($es_export_pdf) {
        $sql_table = $sql_select . ";";
    } else {
        $sql_table = $sql_select . " LIMIT $procesos_page_size OFFSET $offset;";
    }

    $procesos = $obBD_con1->getArrayConsultaSql($sql_table, $obBD_conexion);
    if ($procesos === false || $procesos === null) {
        $procesos = array();
    }

    foreach ($procesos as $idx => $p) {
        $sla = adq_dashboard_calcular_sla_proceso($p);
        $procesos[$idx]['_sla'] = $sla;
        $prog = $wf_mgr->obtenerProgresoPasosFlujo(
            intval(isset($p['Wfm_Cod']) ? $p['Wfm_Cod'] : 0),
            intval(isset($p['Nod_Act']) ? $p['Nod_Act'] : 0),
            isset($p['Ins_Est']) ? $p['Ins_Est'] : 'P'
        );
        if (isset($p['Sol_Est']) && $p['Sol_Est'] === 'A' && $prog['total'] > 0) {
            $prog['actual'] = $prog['total'];
            $prog['texto'] = $prog['total'] . '/' . $prog['total'];
        }
        $procesos[$idx]['Paso_Cant'] = $prog['texto'];
    }
}

// Flujos modelo para tab Flujos
$filtro_flujo_est = isset($_GET['filtro_flujo_est']) ? trim((string)$_GET['filtro_flujo_est']) : 'A';
if (!in_array($filtro_flujo_est, array('A', 'I', 'T'), true)) {
    $filtro_flujo_est = 'A';
}
$buscar_flujo = isset($_GET['buscar_flujo']) ? trim((string)$_GET['buscar_flujo']) : '';
$flujos_dashboard_all = $wf_mgr->listarFlujosDashboard(intval($Ses_Emp_Cod));
wf_manager_log::utf8EnsureDeep($buscar_flujo);
$flujos_dashboard = array();
foreach ($flujos_dashboard_all as $f) {
    $anulado = !empty($f['anulado']);
    if ($filtro_flujo_est === 'A' && $anulado) {
        continue;
    }
    if ($filtro_flujo_est === 'I' && !$anulado) {
        continue;
    }
    if ($buscar_flujo !== '') {
        $nom = isset($f['Wfm_Nom']) ? (string)$f['Wfm_Nom'] : '';
        $des = isset($f['Wfm_Des']) ? (string)$f['Wfm_Des'] : '';
        $hay = (function_exists('mb_stripos')
            ? (mb_stripos($nom, $buscar_flujo, 0, 'UTF-8') !== false || mb_stripos($des, $buscar_flujo, 0, 'UTF-8') !== false)
            : (stripos($nom, $buscar_flujo) !== false || stripos($des, $buscar_flujo) !== false));
        if (!$hay) {
            continue;
        }
    }
    $flujos_dashboard[] = $f;
}

if (isset($_GET['ajax_exportar_procesos_pdf'])) {
    if (empty($es_gerencial_admin)) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array('success' => false, 'message' => 'Acceso denegado.'));
        exit;
    }
    if (!class_exists('mPDF')) {
        include_once(dirname(__FILE__) . '/../../Librerias/MPDF57/mpdf.php');
    }

    $map_estado = array(
        'P' => 'En Proceso (Activos)',
        'F' => 'Aprobados (Finalizados)',
        'R' => 'Rechazados',
        'O' => 'Observados'
    );
    $map_sla = array(
        'a_tiempo' => 'A tiempo (<80%)',
        'en_riesgo' => 'En riesgo (80%-100%)',
        'vencido' => 'Vencidos (>100%)',
        'sin_sla' => 'Sin SLA definido'
    );
    $lbl_estado = ($filtro_estado !== '' && isset($map_estado[$filtro_estado])) ? $map_estado[$filtro_estado] : 'Todos';
    $lbl_sla = ($filtro_sla !== '' && isset($map_sla[$filtro_sla])) ? $map_sla[$filtro_sla] : 'Todos';
    $lbl_depto = 'Todos';
    if ($filtro_depto > 0) {
        foreach ($departamentos as $d) {
            if (intval($d['Dep_Cod']) === $filtro_depto) {
                $lbl_depto = $d['Dep_Des'];
                break;
            }
        }
    }
    $lbl_tipo = 'Todos';
    if ($filtro_tipo > 0) {
        foreach ($tipos_req as $tr) {
            if (intval($tr['Trq_Cod']) === $filtro_tipo) {
                $lbl_tipo = $tr['Trq_Des'];
                break;
            }
        }
    }

    $filas = '';
    $n = 0;
    foreach ($procesos as $p) {
        $n++;
        $sla = isset($p['_sla']) ? $p['_sla'] : adq_dashboard_calcular_sla_proceso($p);
        $solicitante = !empty($p['Prs_Nom'])
            ? trim($p['Prs_Nom'] . ' ' . $p['Prs_Ape'])
            : (isset($p['Usu_Nom']) ? $p['Usu_Nom'] : '-');
        if ($p['Ins_Est'] === 'F') {
            $est_txt = 'Aprobado';
        } elseif ($p['Ins_Est'] === 'R') {
            $est_txt = 'Rechazado';
        } elseif (isset($p['Sol_Est']) && $p['Sol_Est'] === 'I') {
            $est_txt = 'Inhabilitado';
        } elseif (isset($p['Sol_Est']) && $p['Sol_Est'] === 'O') {
            $est_txt = 'Observado';
        } else {
            $est_txt = 'En Proceso';
        }
        $elapsed_fmt = number_format($sla['elapsed'], 1);
        $limit_fmt = $sla['limit'] !== null ? number_format($sla['limit'], 0) : '-';
        $color_sla = '#64748b';
        if ($sla['cat'] === 'a_tiempo') {
            $color_sla = '#198754';
        } elseif ($sla['cat'] === 'en_riesgo') {
            $color_sla = '#d97706';
        } elseif ($sla['cat'] === 'vencido') {
            $color_sla = '#dc3545';
        }
        $bg = ($n % 2 === 0) ? '#f8fafc' : '#ffffff';
        $filas .= '<tr style="background:' . $bg . ';">
            <td style="padding:5px 6px;border:1px solid #cbd5e1;font-size:8px;">' . adq_dashboard_esc($p['Sol_Num']) . '</td>
            <td style="padding:5px 6px;border:1px solid #cbd5e1;font-size:8px;">' . adq_dashboard_esc(!empty($p['Sol_Fec']) ? date('Y-m-d H:i', strtotime($p['Sol_Fec'])) : '-') . '</td>
            <td style="padding:5px 6px;border:1px solid #cbd5e1;font-size:8px;">' . adq_dashboard_esc($solicitante) . '</td>
            <td style="padding:5px 6px;border:1px solid #cbd5e1;font-size:8px;">' . adq_dashboard_esc($p['Trq_Des']) . '</td>
            <td style="padding:5px 6px;border:1px solid #cbd5e1;font-size:8px;text-align:right;">$ ' . number_format(floatval($p['Sol_Val_Est']), 2) . '</td>
            <td style="padding:5px 6px;border:1px solid #cbd5e1;font-size:8px;">' . adq_dashboard_esc(!empty($p['Nod_Nom']) ? $p['Nod_Nom'] : '-') . '</td>
            <td style="padding:5px 6px;border:1px solid #cbd5e1;font-size:8px;">' . adq_dashboard_esc(!empty($p['Dep_Des']) ? $p['Dep_Des'] : 'General') . '</td>
            <td style="padding:5px 6px;border:1px solid #cbd5e1;font-size:8px;">' . adq_dashboard_esc($est_txt) . '</td>
            <td style="padding:5px 6px;border:1px solid #cbd5e1;font-size:8px;color:' . $color_sla . ';font-weight:bold;">'
                . adq_dashboard_esc($sla['label']) . ' (' . $elapsed_fmt . '/' . $limit_fmt . 'd)</td>
        </tr>';
    }
    if ($filas === '') {
        $filas = '<tr><td colspan="9" style="padding:12px;border:1px solid #cbd5e1;text-align:center;color:#64748b;font-size:9px;">No hay procesos con los filtros seleccionados.</td></tr>';
    }

    $html = '
    <div style="font-family:dejavusans,helvetica,arial,sans-serif;color:#0f172a;">
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;">
            <tr>
                <td>
                    <div style="font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#2563eb;font-weight:bold;">Dashboard gerencial</div>
                    <div style="font-size:16px;font-weight:bold;color:#1e3a5f;margin-top:2px;">Reporte de procesos de adquisiciones</div>
                    <div style="font-size:9px;color:#64748b;margin-top:3px;">Generado: ' . adq_dashboard_esc(date('d/m/Y H:i')) . ' &middot; ' . intval(count($procesos)) . ' registro(s)</div>
                </td>
            </tr>
        </table>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;border-collapse:collapse;">
            <tr>
                <td width="25%" style="padding:6px 8px;background:#f1f5f9;border:1px solid #cbd5e1;font-size:8px;"><strong>Estado:</strong> ' . adq_dashboard_esc($lbl_estado) . '</td>
                <td width="25%" style="padding:6px 8px;background:#f1f5f9;border:1px solid #cbd5e1;font-size:8px;"><strong>SLA:</strong> ' . adq_dashboard_esc($lbl_sla) . '</td>
                <td width="25%" style="padding:6px 8px;background:#f1f5f9;border:1px solid #cbd5e1;font-size:8px;"><strong>Depto:</strong> ' . adq_dashboard_esc($lbl_depto) . '</td>
                <td width="25%" style="padding:6px 8px;background:#f1f5f9;border:1px solid #cbd5e1;font-size:8px;"><strong>Tipo:</strong> ' . adq_dashboard_esc($lbl_tipo) . '</td>
            </tr>
        </table>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;border-collapse:collapse;">
            <tr>
                <td width="20%" style="padding:7px;background:#1e3a5f;color:#fff;text-align:center;font-size:8px;border:1px solid #1e3a5f;">Activos<br><span style="font-size:14px;font-weight:bold;">' . intval($total_activos) . '</span></td>
                <td width="20%" style="padding:7px;background:#198754;color:#fff;text-align:center;font-size:8px;border:1px solid #198754;">A tiempo<br><span style="font-size:14px;font-weight:bold;">' . intval($total_a_tiempo) . '</span></td>
                <td width="20%" style="padding:7px;background:#d97706;color:#fff;text-align:center;font-size:8px;border:1px solid #d97706;">En riesgo<br><span style="font-size:14px;font-weight:bold;">' . intval($total_en_riesgo) . '</span></td>
                <td width="20%" style="padding:7px;background:#dc3545;color:#fff;text-align:center;font-size:8px;border:1px solid #dc3545;">Vencidos<br><span style="font-size:14px;font-weight:bold;">' . intval($total_vencidos) . '</span></td>
                <td width="20%" style="padding:7px;background:#64748b;color:#fff;text-align:center;font-size:8px;border:1px solid #64748b;">Sin SLA<br><span style="font-size:14px;font-weight:bold;">' . intval($total_sin_sla) . '</span></td>
            </tr>
        </table>
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="padding:6px;background:#1e3a5f;color:#fff;font-size:8px;border:1px solid #1e3a5f;text-align:left;">N&ordm; Sol.</th>
                    <th style="padding:6px;background:#1e3a5f;color:#fff;font-size:8px;border:1px solid #1e3a5f;text-align:left;">Fecha</th>
                    <th style="padding:6px;background:#1e3a5f;color:#fff;font-size:8px;border:1px solid #1e3a5f;text-align:left;">Solicitante</th>
                    <th style="padding:6px;background:#1e3a5f;color:#fff;font-size:8px;border:1px solid #1e3a5f;text-align:left;">Tipo</th>
                    <th style="padding:6px;background:#1e3a5f;color:#fff;font-size:8px;border:1px solid #1e3a5f;text-align:right;">Monto</th>
                    <th style="padding:6px;background:#1e3a5f;color:#fff;font-size:8px;border:1px solid #1e3a5f;text-align:left;">Etapa</th>
                    <th style="padding:6px;background:#1e3a5f;color:#fff;font-size:8px;border:1px solid #1e3a5f;text-align:left;">Responsable</th>
                    <th style="padding:6px;background:#1e3a5f;color:#fff;font-size:8px;border:1px solid #1e3a5f;text-align:left;">Estado</th>
                    <th style="padding:6px;background:#1e3a5f;color:#fff;font-size:8px;border:1px solid #1e3a5f;text-align:left;">SLA</th>
                </tr>
            </thead>
            <tbody>' . $filas . '</tbody>
        </table>
        <div style="margin-top:10px;font-size:7px;color:#94a3b8;">Documento generado electr&oacute;nicamente desde el Dashboard Gerencial de Adquisiciones. Uso interno.</div>
    </div>';

    $mpdf = new mPDF('c', 'A4-L', '', '', 10, 10, 12, 12, 6, 6);
    $mpdf->SetTitle('Reporte de procesos - Adquisiciones');
    $mpdf->SetAuthor('EXA Adquisiciones');
    $mpdf->WriteHTML($html);
    $filename = 'reporte_procesos_' . date('Ymd_His') . '.pdf';
    $pdfBinary = $mpdf->Output($filename, 'S');
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfBinary));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $pdfBinary;
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>EXA Dashboard Gerencial</title>
    <?php require_once('adq_model3_assets.php'); ?>
    <style>
        .adq-proceso-inhabilitado > td { opacity: 0.78; }
        .adq-proceso-inhabilitado .badge.bg-dark {
            background: #334155 !important;
            color: #fff !important;
        }
        #all-processes-panel .exa-adq-filter-bar {
            padding: 8px 10px;
            margin-bottom: 8px;
            gap: 8px;
        }
        #all-processes-panel .exa-adq-kpi-row {
            margin-bottom: 10px;
            gap: 8px;
        }
        #all-processes-panel .exa-adq-kpi {
            padding: 8px 12px;
            min-width: 120px;
        }
        #all-processes-panel .exa-adq-kpi .kpi-value {
            font-size: 20px;
        }
        #all-processes-panel .exa-adq-table {
            font-size: 11px;
        }
        #all-processes-panel .exa-adq-table > thead > tr > th {
            padding: 5px 6px !important;
            font-size: 10px;
            letter-spacing: 0.02em;
        }
        #all-processes-panel .exa-adq-table > tbody > tr > td {
            padding: 3px 6px !important;
            line-height: 1.25;
            white-space: nowrap;
        }
        #all-processes-panel .exa-adq-table > tbody > tr > td.text-start {
            white-space: normal;
            max-width: 180px;
        }
        #all-processes-panel .exa-adq-table .badge {
            font-size: 9px;
            padding: 2px 5px;
            font-weight: 700;
            line-height: 1.2;
        }
        #all-processes-panel .semaforo-dot {
            width: 8px;
            height: 8px;
            margin-right: 3px;
            vertical-align: middle;
        }
        #all-processes-panel .adq-proc-acciones {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            white-space: nowrap;
        }
        #all-processes-panel .adq-proc-acciones .btn {
            width: 28px;
            height: 28px;
            padding: 0;
            margin: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            line-height: 1;
        }
        #all-processes-panel .adq-proc-acciones .btn i {
            font-size: 14px;
            line-height: 1;
        }
        #all-processes-panel .adq-proc-acciones .btn-anular {
            background: #f87171 !important;
            border-color: #f87171 !important;
            color: #fff !important;
        }
        #all-processes-panel .adq-proc-acciones .btn-anular:hover,
        #all-processes-panel .adq-proc-acciones .btn-anular:focus {
            background: #ef4444 !important;
            border-color: #ef4444 !important;
            color: #fff !important;
        }
        #all-processes-panel .adq-sla-meta {
            font-size: 9px !important;
        }
        #all-processes-panel .adq-table-panel {
            margin-top: 0;
        }
        #all-processes-panel .adq-table-panel .exa-adq-table-wrap {
            border-radius: 8px 8px 0 0;
        }
        #all-processes-panel .adq-table-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 6px 10px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        #all-processes-panel .adq-table-pager-info {
            font-size: 11px;
            color: #475569;
            font-weight: 600;
        }
        #all-processes-panel .adq-table-pager-controls {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }
        #all-processes-panel .adq-table-pager-pages {
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        #all-processes-panel .adq-table-pager .btn,
        #all-processes-panel .adq-table-pager a.btn {
            min-width: 28px;
            height: 26px;
            padding: 2px 7px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 5px;
            border: 1px solid #64748b;
            background: #ffffff;
            color: #334155;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            line-height: 1;
        }
        #all-processes-panel .adq-table-pager .btn:hover:not(:disabled),
        #all-processes-panel .adq-table-pager a.btn:hover {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #1e3a8a;
        }
        #all-processes-panel .adq-table-pager .btn.active,
        #all-processes-panel .adq-table-pager .btn.active:hover,
        #all-processes-panel .adq-table-pager a.btn.active,
        #all-processes-panel .adq-table-pager a.btn.active:hover {
            background: #4b678a;
            border-color: #3a516e;
            color: #ffffff;
        }
        #all-processes-panel .adq-table-pager .btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        #all-processes-panel .adq-table-pager-size {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
        }
        #all-processes-panel .adq-table-pager-size select {
            height: 26px;
            font-size: 11px;
            border-radius: 5px;
            border: 1px solid #64748b;
            padding: 1px 6px;
            background: #ffffff;
            color: #1e293b;
        }
        /* Alerta centrada en pantalla */
        .adq-alert-overlay {
            position: fixed;
            inset: 0;
            z-index: 10050;
            background: rgba(15, 23, 42, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .adq-alert-overlay.is-visible {
            display: flex;
        }
        .adq-alert-box {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.28);
            overflow: hidden;
            text-align: center;
            animation: adqAlertIn .18s ease-out;
        }
        @keyframes adqAlertIn {
            from { transform: translateY(8px) scale(0.98); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
        .adq-alert-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            margin: 22px auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        .adq-alert-icon.warn {
            background: #fee2e2;
            color: #dc2626;
        }
        .adq-alert-icon.ok {
            background: #dcfce7;
            color: #15803d;
        }
        .adq-alert-icon.info {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .adq-alert-icon.error {
            background: #fee2e2;
            color: #b91c1c;
        }
        .adq-alert-title {
            margin: 0 20px 6px;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .adq-alert-msg {
            margin: 0 22px 18px;
            font-size: 13px;
            color: #475569;
            line-height: 1.45;
            white-space: pre-line;
        }
        .adq-alert-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            padding: 0 18px 18px;
        }
        .adq-alert-actions .btn {
            min-width: 110px;
            font-weight: 700;
        }
    </style>
</head>
<body class="exa-ui-fill-root">
    <div class="panel panel-main exa-ui-panel exa-ui-fill-page">
        <div class="panel-heading exa-header exa-header-flex">
            <h3 class="panel-title"><i class="bi bi-graph-up-arrow"></i> Dashboard Gerencial</h3>
        </div>
        <div class="panel-body exa-body">
            <div class="exa-ui-page-view">
        <ul class="nav nav-tabs exa-ui-nav-tabs" id="dashboardTabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#metrics-panel" id="metrics-tab" role="tab" data-toggle="tab"><i class="bi bi-bar-chart-line"></i> M?tricas Generales</a>
            </li>
            <li role="presentation">
                <a href="#all-processes-panel" id="all-processes-tab" role="tab" data-toggle="tab"><i class="bi bi-collection-play"></i> Todos los Procesos</a>
            </li>
            <li role="presentation">
                <a href="#flujos-panel" id="flujos-tab" role="tab" data-toggle="tab"><i class="bi bi-diagram-3"></i> Flujos</a>
            </li>
        </ul>

        <div class="tab-content exa-ui-tab-content panels-area" id="dashboardTabsContent">
            <!-- 1. M?TRICAS GENERALES -->
            <div class="tab-pane active" id="metrics-panel" role="tabpanel">
                <div class="exa-adq-kpi-row">
                    <div class="exa-adq-kpi kpi-primary">
                        <span class="kpi-label">Procesos en ejecuci?n</span>
                        <span class="kpi-value"><?php echo $stats['Activos']; ?></span>
                    </div>
                    <div class="exa-adq-kpi kpi-success">
                        <span class="kpi-label">Solicitudes aprobadas</span>
                        <span class="kpi-value"><?php echo $stats['Aprobados']; ?></span>
                    </div>
                    <div class="exa-adq-kpi kpi-warning">
                        <span class="kpi-label">Solicitudes observadas</span>
                        <span class="kpi-value"><?php echo $stats['Observados']; ?></span>
                    </div>
                    <div class="exa-adq-kpi kpi-danger">
                        <span class="kpi-label">Tiempo promedio ciclo</span>
                        <span class="kpi-value"><?php echo number_format($stats['Tiempo_Promedio'], 1); ?> <small>D?as</small></span>
                    </div>
                </div>

        <div class="row">
            <!-- 1. Cuellos de Botella -->
            <div class="col-md-6">
                <div class="exa-adq-section">
                    <h5 class="exa-adq-section-title"><i class="bi bi-exclamation-octagon text-danger"></i> Cuellos de Botella (Procesos en Espera)</h5>
                    <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table">
                            <thead>
                                <tr>
                                    <th>Etapa del Workflow</th>
                                    <th>Departamento Responsable</th>
                                    <th style="width: 100px;">En Espera</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cuellos)) { ?>
                                    <tr class="exa-adq-empty text-center"><td colspan="3">No hay cuellos de botella identificados.</td></tr>
                                <?php } else {
                                    foreach ($cuellos as $c) { ?>
                                        <tr class="text-center">
                                            <td class="text-start fw-bold"><?php echo $c['Nod_Nom']; ?></td>
                                            <td><?php echo $c['Dep_Des'] ?: '[General]'; ?></td>
                                            <td class="fw-bold fs-6 text-danger"><?php echo $c['Total']; ?></td>
                                        </tr>
                                <?php }
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 2. Rendimiento Departamental -->
            <div class="col-md-6">
                <div class="exa-adq-section">
                    <h5 class="exa-adq-section-title"><i class="bi bi-speedometer2 text-success"></i> Eficiencia y SLA Departamental</h5>
                    <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table">
                            <thead>
                                <tr>
                                    <th>Departamento</th>
                                    <th style="width: 120px;">Pasos Resueltos</th>
                                    <th style="width: 150px;">Tiempo Medio Aprob.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($departamentos_ranking)) { ?>
                                    <tr class="text-center"><td colspan="3" class="text-muted py-3">No se han registrado transacciones de workflow aprobadas a?n.</td></tr>
                                <?php } else {
                                    foreach ($departamentos_ranking as $r) { ?>
                                        <tr class="text-center">
                                            <td class="text-start fw-bold"><?php echo $r['Dep_Des']; ?></td>
                                            <td><?php echo $r['Resoluciones']; ?></td>
                                            <td class="fw-bold font-monospace text-success"><?php echo number_format($r['Tiempo_Atencion'], 1); ?> d&iacute;as</td>
                                        </tr>
                                <?php }
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. Evoluci?n del Gasto -->
            <div class="col-12">
                <div class="exa-adq-section">
                    <h5 class="exa-adq-section-title"><i class="bi bi-calendar-event text-primary"></i> Vol?menes de Gasto de los ?ltimos 6 Meses</h5>
                    <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table">
                            <thead>
                                <tr>
                                    <th>Mes Calendario</th>
                                    <th>Total Requerimientos de Adquisici?n</th>
                                    <th>Presupuesto Total Estimado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($volumenes)) { ?>
                                    <tr class="text-center"><td colspan="3" class="text-muted py-3">No se registran solicitudes en el semestre actual.</td></tr>
                                <?php } else {
                                    foreach ($volumenes as $v) { ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo $v['Mes']; ?></td>
                                            <td class="fs-6"><?php echo $v['Total']; ?></td>
                                            <td class="fw-bold font-monospace fs-6 text-primary">$ <?php echo number_format($v['Monto'], 2); ?></td>
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

    <!-- 2. TODOS LOS PROCESOS (MONITOR DE GERENCIA) -->
    <div class="tab-pane" id="all-processes-panel" role="tabpanel">
        <?php if (!$es_gerencial_admin) { ?>
            <div class="alert alert-warning m-4 text-center">
                <i class="bi bi-shield-slash fs-1 d-block mb-2"></i>
                <h5 class="fw-bold">Acceso Restringido</h5>
                <p class="mb-0">Esta secci?n es de uso exclusivo para perfiles gerenciales, directores o administradores del sistema.</p>
            </div>
        <?php } else { ?>
            <!-- Tarjetas de Resumen SLA -->
            <div class="exa-adq-kpi-row">
                <a class="exa-adq-kpi kpi-muted" href="adq_dashboard.php?tab=todos_procesos&filtro_estado=P" style="text-decoration:none;color:inherit;">
                    <span class="kpi-label">Procesos activos</span><span class="kpi-value"><?php echo $total_activos; ?></span>
                </a>
                <a class="exa-adq-kpi kpi-success" href="adq_dashboard.php?tab=todos_procesos&filtro_estado=P&filtro_sla=a_tiempo" style="text-decoration:none;color:inherit;">
                    <span class="kpi-label">A tiempo (&lt;80%)</span><span class="kpi-value"><?php echo $total_a_tiempo; ?></span>
                </a>
                <a class="exa-adq-kpi kpi-warning" href="adq_dashboard.php?tab=todos_procesos&filtro_estado=P&filtro_sla=en_riesgo" style="text-decoration:none;color:inherit;">
                    <span class="kpi-label">En riesgo (80%-100%)</span><span class="kpi-value"><?php echo $total_en_riesgo; ?></span>
                </a>
                <a class="exa-adq-kpi kpi-danger" href="adq_dashboard.php?tab=todos_procesos&filtro_estado=P&filtro_sla=vencido" style="text-decoration:none;color:inherit;" title="Ver tareas vencidas">
                    <span class="kpi-label">Vencidos (&gt;100%)</span><span class="kpi-value"><?php echo $total_vencidos; ?></span>
                </a>
                <a class="exa-adq-kpi kpi-muted" href="adq_dashboard.php?tab=todos_procesos&filtro_estado=P&filtro_sla=sin_sla" style="text-decoration:none;color:inherit;">
                    <span class="kpi-label">Sin SLA definido</span><span class="kpi-value"><?php echo $total_sin_sla; ?></span>
                </a>
            </div>

            <!-- Formulario de Filtros -->
            <form method="GET" action="adq_dashboard.php" class="exa-adq-filter-bar" id="frmFiltrosProcesos">
                    <input type="hidden" name="tab" value="todos_procesos">
                    <input type="hidden" name="page" value="1">
                    <input type="hidden" name="page_size" id="filtroPageSizeHidden" value="<?php echo intval($procesos_page_size); ?>">
                    
                    <div class="filter-item">
                        <label>Estado del Proceso</label>
                        <select class="form-control input-sm" name="filtro_estado">
                            <option value="">-- Todos los Estados --</option>
                            <option value="P" <?php echo $filtro_estado === 'P' ? 'selected' : ''; ?>>En Proceso (Activos)</option>
                            <option value="F" <?php echo $filtro_estado === 'F' ? 'selected' : ''; ?>>Aprobados (Finalizados)</option>
                            <option value="R" <?php echo $filtro_estado === 'R' ? 'selected' : ''; ?>>Rechazados</option>
                            <option value="O" <?php echo $filtro_estado === 'O' ? 'selected' : ''; ?>>Observados</option>
                            <option value="I" <?php echo $filtro_estado === 'I' ? 'selected' : ''; ?>>Inhabilitados</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>SLA / Vencimiento</label>
                        <select class="form-control input-sm" name="filtro_sla">
                            <option value="">-- Todos los SLA --</option>
                            <option value="a_tiempo" <?php echo $filtro_sla === 'a_tiempo' ? 'selected' : ''; ?>>A tiempo (&lt;80%)</option>
                            <option value="en_riesgo" <?php echo $filtro_sla === 'en_riesgo' ? 'selected' : ''; ?>>En riesgo (80%-100%)</option>
                            <option value="vencido" <?php echo $filtro_sla === 'vencido' ? 'selected' : ''; ?>>Tareas vencidas (&gt;100%)</option>
                            <option value="sin_sla" <?php echo $filtro_sla === 'sin_sla' ? 'selected' : ''; ?>>Sin SLA definido</option>
                        </select>
                    </div>
                    
                    <div class="filter-item">
                        <label>Departamento Responsable</label>
                        <select class="form-control input-sm" name="filtro_depto">
                            <option value="0">-- Todos los Departamentos --</option>
                            <?php foreach ($departamentos as $d) { ?>
                                <option value="<?php echo $d['Dep_Cod']; ?>" <?php echo $filtro_depto == $d['Dep_Cod'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['Dep_Des'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="filter-item">
                        <label>Tipo de Requerimiento</label>
                        <select class="form-control input-sm" name="filtro_tipo">
                            <option value="0">-- Todos los Tipos --</option>
                            <?php foreach ($tipos_req as $tr) { ?>
                                <option value="<?php echo $tr['Trq_Cod']; ?>" <?php echo $filtro_tipo == $tr['Trq_Cod'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($tr['Trq_Des'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filtrar</button>
                        <a href="adq_dashboard.php?tab=todos_procesos" class="btn btn-default btn-sm"><i class="bi bi-x-circle"></i> Limpiar</a>
                        <button type="button" class="btn btn-danger btn-sm" id="btnExportarProcesosPdf" onclick="abrirReporteProcesosPdf()">
                            <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                        </button>
                    </div>
                </form>

            <?php if ($filtro_sla === 'vencido') { ?>
                <div class="alert alert-danger" style="margin:0 0 12px 0;padding:10px 14px;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Mostrando solo <strong>tareas vencidas</strong> (consumo de SLA mayor al 100%).
                    <?php echo intval($procesos_total); ?> registro(s).
                </div>
            <?php } ?>

            <!-- Tabla de Procesos -->
            <div class="adq-table-panel" id="panelProcesosDashboard" data-page-size="<?php echo intval($procesos_page_size); ?>" data-page="<?php echo intval($procesos_page); ?>" data-total="<?php echo intval($procesos_total); ?>" data-pages="<?php echo intval($procesos_pages); ?>">
            <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table adq-table-paginated">
                        <thead>
                            <tr>
                                <th>N? Sol.</th>
                                <th>Fecha Emisi?n</th>
                                <th>Solicitante</th>
                                <th>Tipo Requerimiento</th>
                                <th>Monto Est.</th>
                                <th>Etapa Actual</th>
                                <th>Responsable</th>
                                <th>Estado</th>
                                <th style="width:70px;" title="Paso actual / total">Avance</th>
                                <th>SLA Sem?foro</th>
                                <th>Acci?n</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($procesos)) { ?>
                                <tr class="adq-row-empty">
                                    <td colspan="11" class="text-center text-muted py-4">No se encontraron procesos que coincidan con los filtros seleccionados.</td>
                                </tr>
                            <?php } else {
                                foreach ($procesos as $p) {
                                    $sla = isset($p['_sla']) ? $p['_sla'] : adq_dashboard_calcular_sla_proceso($p);
                                    $elapsed_days_fmt = number_format($sla['elapsed'], 1);
                                    $limit_days = $sla['limit'];
                                    $semaforo_class = $sla['semaforo'];
                                    if ($sla['cat'] === 'a_tiempo') {
                                        $sla_badge = '<span class="badge bg-success">A tiempo</span>';
                                    } elseif ($sla['cat'] === 'en_riesgo') {
                                        $sla_badge = '<span class="badge bg-warning text-dark">En riesgo</span>';
                                    } elseif ($sla['cat'] === 'vencido') {
                                        $sla_badge = '<span class="badge bg-danger">Vencido</span>';
                                    } else {
                                        $sla_badge = '<span class="badge bg-secondary">Sin SLA</span>';
                                    }
                                    
                                    // Estado de la solicitud
                                    $est_badge = '';
                                    $esta_inhabilitado = (isset($p['Sol_Est']) && $p['Sol_Est'] === 'I');
                                    $esta_aprobado = ($p['Ins_Est'] === 'F' || (isset($p['Sol_Est']) && $p['Sol_Est'] === 'A'));
                                    $puede_toggle = !$esta_inhabilitado && !$esta_aprobado;
                                    if ($esta_inhabilitado) {
                                        $est_badge = '<span class="badge bg-dark">Inhabilitado</span>';
                                    } elseif ($p['Ins_Est'] === 'F') {
                                        $est_badge = '<span class="badge bg-success">Aprobado</span>';
                                    } elseif ($p['Ins_Est'] === 'R') {
                                        $est_badge = '<span class="badge bg-danger">Rechazado</span>';
                                    } else {
                                        if ($p['Sol_Est'] === 'O') {
                                            $est_badge = '<span class="badge bg-warning text-dark">Observado</span>';
                                        } else {
                                            $est_badge = '<span class="badge bg-primary">En Proceso</span>';
                                        }
                                    }
                                    
                                    $solicitante_nom = $p['Prs_Nom'] ? ($p['Prs_Nom'] . ' ' . $p['Prs_Ape']) : $p['Usu_Nom'];
                                    ?>
                                    <tr class="text-center adq-row-proceso<?php echo $sla['cat'] === 'vencido' ? ' table-danger' : ''; ?><?php echo $esta_inhabilitado ? ' adq-proceso-inhabilitado' : ''; ?>">
                                        <td class="fw-bold"><?php echo htmlspecialchars($p['Sol_Num'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($p['Sol_Fec'])); ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars($solicitante_nom, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-start fw-semibold text-primary"><?php echo htmlspecialchars($p['Trq_Des'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="fw-bold font-monospace text-success text-end">$ <?php echo number_format($p['Sol_Val_Est'], 2); ?></td>
                                        <td class="text-start"><?php echo $p['Nod_Nom'] ? htmlspecialchars($p['Nod_Nom'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">-</span>'; ?></td>
                                        <td><?php echo $p['Dep_Des'] ? htmlspecialchars($p['Dep_Des'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">[General]</span>'; ?></td>
                                        <td><?php echo $est_badge; ?></td>
                                        <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars(isset($p['Paso_Cant']) ? $p['Paso_Cant'] : '-', ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td class="text-start">
                                            <span class="semaforo-dot <?php echo $semaforo_class; ?>"></span>
                                            <?php echo $sla_badge; ?>
                                            <span class="text-muted font-monospace adq-sla-meta">
                                                (<?php echo $elapsed_days_fmt; ?>/<?php echo $limit_days !== null ? $limit_days : '-'; ?>d)
                                            </span>
                                        </td>
                                        <td>
                                            <div class="adq-proc-acciones">
                                                <button type="button" class="btn btn-primary" title="Ver seguimiento" onclick="abrirSeguimiento(<?php echo intval($p['Sol_Cod']); ?>, '<?php echo htmlspecialchars($p['Sol_Num'], ENT_QUOTES, 'UTF-8'); ?>')">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php if ($esta_inhabilitado) { ?>
                                                <button type="button" class="btn btn-success" title="Habilitar" onclick="toggleProcesoInhabilitado(<?php echo intval($p['Sol_Cod']); ?>, '<?php echo htmlspecialchars($p['Sol_Num'], ENT_QUOTES, 'UTF-8'); ?>', false)">
                                                    <i class="bi bi-unlock-fill"></i>
                                                </button>
                                                <?php } elseif ($puede_toggle) { ?>
                                                <button type="button" class="btn btn-anular" title="Anular / Inhabilitar" onclick="toggleProcesoInhabilitado(<?php echo intval($p['Sol_Cod']); ?>, '<?php echo htmlspecialchars($p['Sol_Num'], ENT_QUOTES, 'UTF-8'); ?>', true)">
                                                    <i class="bi bi-x-circle-fill"></i>
                                                </button>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                            <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
                <div class="adq-table-pager"></div>
            </div>
        <?php } ?>
    </div>

            <!-- 3. FLUJOS MODELO -->
            <div class="tab-pane" id="flujos-panel" role="tabpanel">
                <div class="exa-adq-section">
                    <h5 class="exa-adq-section-title"><i class="bi bi-diagram-3 text-primary"></i> Flujos de trabajo</h5>
                    <p class="text-muted" style="margin:0 0 12px 0;font-size:12px;">
                        Lista de flujos modelo. Al anular, todos los nodos del flujo quedan con <strong>Nod_Est = I</strong> (inactivo) y el flujo deja de estar disponible para nuevas solicitudes.
                    </p>
                    <form method="GET" action="adq_dashboard.php" class="exa-adq-filter-bar" id="frmFiltrosFlujos" style="margin-bottom:14px;">
                        <input type="hidden" name="tab" value="flujos">
                        <div class="filter-item">
                            <label>Estado</label>
                            <select class="form-control input-sm" name="filtro_flujo_est" id="filtroFlujoEst">
                                <option value="A" <?php echo $filtro_flujo_est === 'A' ? 'selected' : ''; ?>>Activos</option>
                                <option value="I" <?php echo $filtro_flujo_est === 'I' ? 'selected' : ''; ?>>Inactivos (anulados)</option>
                                <option value="T" <?php echo $filtro_flujo_est === 'T' ? 'selected' : ''; ?>>Todos</option>
                            </select>
                        </div>
                        <div class="filter-item" style="flex:1 1 240px;min-width:200px;">
                            <label>Buscar por nombre</label>
                            <input type="text" class="form-control input-sm" name="buscar_flujo" id="buscarFlujo"
                                   value="<?php echo adq_dashboard_esc($buscar_flujo); ?>"
                                   placeholder="Nombre o descripci&oacute;n del flujo">
                        </div>
                        <div class="filter-item" style="align-self:flex-end;">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Buscar</button>
                            <a href="adq_dashboard.php?tab=flujos" class="btn btn-default btn-sm"><i class="bi bi-x-circle"></i> Limpiar</a>
                        </div>
                    </form>
                    <div class="text-muted" style="font-size:11px;margin-bottom:8px;">
                        Mostrando <?php echo count($flujos_dashboard); ?> de <?php echo count($flujos_dashboard_all); ?> flujo(s)
                    </div>
                    <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table" id="tblFlujosDashboard">
                            <thead>
                                <tr>
                                    <th style="width:70px;">C&oacute;d.</th>
                                    <th>Nombre</th>
                                    <th>Descripci&oacute;n</th>
                                    <th>Departamento</th>
                                    <th style="width:80px;">Versi&oacute;n</th>
                                    <th style="width:110px;">Nodos activos</th>
                                    <th style="width:120px;">Estado</th>
                                    <th style="width:130px;">Acci&oacute;n</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($flujos_dashboard)) { ?>
                                    <tr class="exa-adq-empty text-center"><td colspan="8">No hay flujos con los filtros seleccionados.</td></tr>
                                <?php } else {
                                    foreach ($flujos_dashboard as $f) {
                                        $anulado = !empty($f['anulado']);
                                        $inst_act = intval(isset($f['instancias_activas']) ? $f['instancias_activas'] : 0);
                                        $nom_esc = adq_dashboard_esc($f['Wfm_Nom']);
                                        $des_txt = isset($f['Wfm_Des']) ? trim((string)$f['Wfm_Des']) : '';
                                        ?>
                                        <tr class="text-center<?php echo $anulado ? ' adq-proceso-inhabilitado' : ''; ?>" data-wfm="<?php echo intval($f['Wfm_Cod']); ?>">
                                            <td class="font-monospace"><?php echo intval($f['Wfm_Cod']); ?></td>
                                            <td class="text-start"><strong><?php echo $nom_esc; ?></strong></td>
                                            <td class="text-start"><?php echo $des_txt !== '' ? adq_dashboard_esc($des_txt) : '<span class="text-muted">&mdash;</span>'; ?></td>
                                            <td><?php echo adq_dashboard_esc($f['Dep_Des'] !== '' ? $f['Dep_Des'] : '[General]'); ?></td>
                                            <td>v<?php echo intval($f['Wfm_Version']); ?></td>
                                            <td><?php echo intval($f['Nodos_Activos']); ?> / <?php echo intval($f['Nodos_Total']); ?></td>
                                            <td>
                                                <?php if ($anulado) { ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php } else { ?>
                                                    <span class="badge bg-success">Activo</span>
                                                    <?php if ($inst_act > 0) { ?>
                                                        <div class="text-muted" style="font-size:10px;margin-top:3px;"><?php echo $inst_act; ?> en ejecuci&oacute;n</div>
                                                    <?php } ?>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <?php if ($anulado) { ?>
                                                    <span class="text-muted" style="font-size:11px;">Sin acci&oacute;n</span>
                                                <?php } else { ?>
                                                    <button type="button" class="btn btn-danger btn-xs btn-anular-flujo"
                                                        title="<?php echo $inst_act > 0 ? 'Hay procesos en ejecucion; no se puede anular' : 'Anular flujo (Nod_Est = I)'; ?>"
                                                        data-wfm-cod="<?php echo intval($f['Wfm_Cod']); ?>"
                                                        data-wfm-nom="<?php echo adq_dashboard_esc($f['Wfm_Nom']); ?>"
                                                        data-inst="<?php echo $inst_act; ?>">
                                                        <i class="bi bi-ban"></i> Anular
                                                    </button>
                                                <?php } ?>
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
        </div>
    </div>

<!-- ALERTA CENTRADA -->
<div class="adq-alert-overlay" id="adqAlertOverlay" aria-hidden="true">
    <div class="adq-alert-box" role="dialog" aria-modal="true" aria-labelledby="adqAlertTitle">
        <div class="adq-alert-icon warn" id="adqAlertIcon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <h4 class="adq-alert-title" id="adqAlertTitle">Confirmar</h4>
        <p class="adq-alert-msg" id="adqAlertMsg"></p>
        <div class="adq-alert-actions" id="adqAlertActions"></div>
    </div>
</div>

<!-- MODAL SEGUIMIENTO DETALLADO (SLA) -->
<div class="modal fade" id="mdlSeguimiento" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg adq-seg-modal-dialog">
        <div class="modal-content adq-seg-modal-content">
            <div class="modal-header adq-seg-modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <div class="adq-seg-modal-heading">
                    <div class="adq-seg-modal-icon"><i class="bi bi-diagram-3"></i></div>
                    <div>
                        <h4 class="modal-title" id="lblSeguimientoTitle">Seguimiento de requerimiento</h4>
                        <p class="adq-seg-modal-sub" id="lblSeguimientoSub">L&iacute;nea de tiempo, SLA y documentos del proceso</p>
                    </div>
                </div>
            </div>
            <div class="modal-body adq-seg-modal-body" id="seguimientoModalBody">
                <!-- Contenido AJAX se inyecta aqu? -->
            </div>
            <div class="modal-footer adq-seg-modal-footer">
                <span class="adq-seg-modal-hint text-muted"><i class="bi bi-info-circle"></i> Clic en un nodo de la l&iacute;nea de tiempo para ver sus tareas</span>
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL REPORTE PDF -->
<div class="modal fade" id="mdlReportePdf" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="width:95%;max-width:1180px;margin:20px auto;">
        <div class="modal-content" style="border:0;border-radius:12px;overflow:hidden;box-shadow:0 20px 50px rgba(15,23,42,.28);">
            <div class="modal-header" style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);color:#fff;border:0;padding:14px 18px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color:#fff;opacity:.9;text-shadow:none;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" style="font-size:16px;font-weight:700;margin:0;">
                    <i class="bi bi-file-earmark-pdf"></i> Vista previa del reporte PDF
                </h4>
                <p class="mb-0" style="font-size:12px;opacity:.85;margin-top:4px;">Revise el reporte con los filtros actuales. Luego puede descargarlo o imprimirlo.</p>
            </div>
            <div class="modal-body" style="padding:0;background:#0f172a;position:relative;min-height:70vh;">
                <div id="reportePdfLoading" style="display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;position:absolute;inset:0;background:rgba(15,23,42,.92);z-index:2;color:#fff;">
                    <div class="spinner-border text-light" role="status" style="width:2.4rem;height:2.4rem;"></div>
                    <div style="font-weight:700;">Generando reporte...</div>
                    <div style="font-size:12px;opacity:.75;">Esto puede tomar unos segundos</div>
                </div>
                <div id="reportePdfError" style="display:none;padding:40px 24px;text-align:center;color:#fecaca;">
                    <i class="bi bi-exclamation-triangle" style="font-size:28px;"></i>
                    <div id="reportePdfErrorText" style="margin-top:10px;font-weight:600;"></div>
                </div>
                <iframe id="reportePdfFrame" title="Vista previa reporte PDF" style="display:none;width:100%;height:70vh;border:0;background:#525659;"></iframe>
            </div>
            <div class="modal-footer" style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:12px 16px;">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnImprimirReportePdf" disabled onclick="imprimirReporteProcesosPdf()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDescargarReportePdf" disabled onclick="descargarReporteProcesosPdf()">
                    <i class="bi bi-download"></i> Descargar PDF
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    let currentSolCod = null;
    let reportePdfBlobUrl = null;
    let reportePdfFilename = 'reporte_procesos.pdf';

    function construirUrlReporteProcesosPdf() {
        const params = new URLSearchParams();
        params.set('ajax_exportar_procesos_pdf', '1');
        params.set('tab', 'todos_procesos');
        const $form = $('#frmFiltrosProcesos');
        if ($form.length) {
            params.set('filtro_estado', $form.find('[name="filtro_estado"]').val() || '');
            params.set('filtro_sla', $form.find('[name="filtro_sla"]').val() || '');
            params.set('filtro_depto', $form.find('[name="filtro_depto"]').val() || '0');
            params.set('filtro_tipo', $form.find('[name="filtro_tipo"]').val() || '0');
        }
        return 'adq_dashboard.php?' + params.toString();
    }

    function liberarReportePdfBlob() {
        if (reportePdfBlobUrl) {
            window.URL.revokeObjectURL(reportePdfBlobUrl);
            reportePdfBlobUrl = null;
        }
    }

    function abrirReporteProcesosPdf() {
        const $btn = $('#btnExportarProcesosPdf');
        const original = $btn.html();
        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Generando...');

        liberarReportePdfBlob();
        $('#reportePdfFrame').hide().attr('src', 'about:blank');
        $('#reportePdfError').hide();
        $('#reportePdfLoading').css('display', 'flex');
        $('#btnImprimirReportePdf, #btnDescargarReportePdf').prop('disabled', true);
        $('#mdlReportePdf').modal('show');

        fetch(construirUrlReporteProcesosPdf(), { credentials: 'same-origin' })
            .then(function(resp) {
                const ctype = (resp.headers.get('Content-Type') || '').toLowerCase();
                if (ctype.indexOf('application/json') !== -1) {
                    return resp.json().then(function(res) {
                        throw new Error((res && res.message) ? res.message : 'No se pudo generar el PDF.');
                    });
                }
                if (!resp.ok) {
                    throw new Error('Error al generar el reporte PDF.');
                }
                const disp = resp.headers.get('Content-Disposition') || '';
                const m = /filename="?([^"]+)"?/i.exec(disp);
                if (m && m[1]) {
                    reportePdfFilename = m[1];
                } else {
                    reportePdfFilename = 'reporte_procesos_' + Date.now() + '.pdf';
                }
                return resp.blob();
            })
            .then(function(blob) {
                if (!blob || blob.size <= 0) {
                    throw new Error('El PDF generado esta vacio.');
                }
                const pdfBlob = (blob.type && blob.type.indexOf('pdf') !== -1)
                    ? blob
                    : new Blob([blob], { type: 'application/pdf' });
                reportePdfBlobUrl = window.URL.createObjectURL(pdfBlob);
                $('#reportePdfLoading').hide();
                $('#reportePdfFrame').attr('src', reportePdfBlobUrl).show();
                $('#btnImprimirReportePdf, #btnDescargarReportePdf').prop('disabled', false);
            })
            .catch(function(err) {
                $('#reportePdfLoading').hide();
                $('#reportePdfErrorText').text(err && err.message ? err.message : 'No se pudo generar el reporte.');
                $('#reportePdfError').show();
            })
            .then(function() {
                $btn.prop('disabled', false).html(original);
            });
    }

    function descargarReporteProcesosPdf() {
        if (!reportePdfBlobUrl) {
            alert('Primero genere el reporte.');
            return;
        }
        const a = document.createElement('a');
        a.href = reportePdfBlobUrl;
        a.download = reportePdfFilename || 'reporte_procesos.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
    }

    function imprimirReporteProcesosPdf() {
        const frame = document.getElementById('reportePdfFrame');
        if (!frame || !reportePdfBlobUrl) {
            alert('Primero genere el reporte.');
            return;
        }
        try {
            if (frame.contentWindow) {
                frame.contentWindow.focus();
                frame.contentWindow.print();
                return;
            }
        } catch (e) { /* fallback */ }
        const w = window.open(reportePdfBlobUrl, '_blank');
        if (w) {
            w.addEventListener('load', function() {
                try { w.print(); } catch (err) {}
            });
        } else {
            alert('Permita ventanas emergentes para imprimir, o use Descargar PDF.');
        }
    }

    function cerrarAlertaCentro() {
        const $ov = $('#adqAlertOverlay');
        $ov.removeClass('is-visible').attr('aria-hidden', 'true');
        $('#adqAlertActions').empty();
    }

    function mostrarAlertaCentro(opts) {
        opts = opts || {};
        const tipo = opts.tipo || 'info';
        const titulo = opts.titulo || 'Aviso';
        const mensaje = opts.mensaje || '';
        const iconMap = {
            warn: 'bi-exclamation-triangle-fill',
            ok: 'bi-check-circle-fill',
            info: 'bi-info-circle-fill',
            error: 'bi-x-circle-fill'
        };
        const $ov = $('#adqAlertOverlay');
        const $icon = $('#adqAlertIcon');
        $icon
            .removeClass('warn ok info error')
            .addClass(tipo)
            .html('<i class="bi ' + (iconMap[tipo] || iconMap.info) + '"></i>');
        $('#adqAlertTitle').text(titulo);
        $('#adqAlertMsg').text(mensaje);

        const $actions = $('#adqAlertActions').empty();
        if (opts.confirmacion) {
            const $cancel = $('<button type="button" class="btn btn-default">Cancelar</button>');
            const $ok = $('<button type="button" class="btn btn-danger">S?, anular</button>');
            if (opts.confirmText) {
                $ok.text(opts.confirmText);
            }
            if (opts.confirmClass) {
                $ok.removeClass('btn-danger').addClass(opts.confirmClass);
            }
            $cancel.on('click', function() {
                cerrarAlertaCentro();
                if (typeof opts.onCancel === 'function') opts.onCancel();
            });
            $ok.on('click', function() {
                cerrarAlertaCentro();
                if (typeof opts.onConfirm === 'function') opts.onConfirm();
            });
            $actions.append($cancel).append($ok);
        } else {
            const $ok = $('<button type="button" class="btn btn-primary">Aceptar</button>');
            $ok.on('click', function() {
                cerrarAlertaCentro();
                if (typeof opts.onClose === 'function') opts.onClose();
            });
            $actions.append($ok);
        }

        $ov.addClass('is-visible').attr('aria-hidden', 'false');
    }


    function anularFlujoDashboard(wfmCod, wfmNom, instActivas) {
        wfmCod = parseInt(wfmCod, 10) || 0;
        instActivas = parseInt(instActivas, 10) || 0;
        if (wfmCod <= 0) {
            return;
        }
        if (instActivas > 0) {
            mostrarAlertaCentro({
                tipo: 'error',
                titulo: 'No se puede anular',
                mensaje: 'El flujo "' + wfmNom + '" tiene ' + instActivas + ' proceso(s) en ejecucion. Finalice o anule esos procesos primero.'
            });
            return;
        }
        mostrarAlertaCentro({
            tipo: 'warn',
            titulo: 'Anular flujo',
            mensaje: '?Desea anular el flujo "' + wfmNom + '"?\n\nTodos sus nodos quedar?n con Nod_Est = I (inactivo) y el flujo no estar? disponible para nuevas solicitudes.',
            confirmacion: true,
            confirmText: 'S?, anular',
            confirmClass: 'btn-danger',
            onConfirm: function() {
                $.post('adq_dashboard.php', {
                    ajax_anular_flujo: 1,
                    wfm_cod: wfmCod
                }, function(res) {
                    if (!res || !res.success) {
                        mostrarAlertaCentro({
                            tipo: 'error',
                            titulo: 'No se pudo anular',
                            mensaje: (res && res.message) ? res.message : 'No se pudo anular el flujo.'
                        });
                        return;
                    }
                    mostrarAlertaCentro({
                        tipo: 'ok',
                        titulo: 'Flujo anulado',
                        mensaje: res.message || 'Flujo anulado correctamente.',
                        onClose: function() {
                            const params = new URLSearchParams(window.location.search);
                            params.set('tab', 'flujos');
                            if (!params.has('filtro_flujo_est')) {
                                params.set('filtro_flujo_est', 'A');
                            }
                            window.location.search = params.toString();
                        }
                    });
                }, 'json').fail(function() {
                    mostrarAlertaCentro({
                        tipo: 'error',
                        titulo: 'Error de red',
                        mensaje: 'No se pudo conectar con el servidor. Intente nuevamente.'
                    });
                });
            }
        });
    }
    function toggleProcesoInhabilitado(solCod, solNum, inhabilitar) {
        const accion = inhabilitar ? 'inhabilitar' : 'habilitar';
        const titulo = inhabilitar ? 'Anular proceso' : 'Habilitar proceso';
        const mensaje = inhabilitar
            ? ('?Desea anular el proceso #' + solNum + '?\n\nQuedar? solo en consulta y no se podr? avanzar el workflow.')
            : ('?Desea habilitar nuevamente el proceso #' + solNum + '?\n\nPodr? continuar su flujo normal.');

        mostrarAlertaCentro({
            tipo: inhabilitar ? 'warn' : 'info',
            titulo: titulo,
            mensaje: mensaje,
            confirmacion: true,
            confirmText: inhabilitar ? 'S?, anular' : 'S?, habilitar',
            confirmClass: inhabilitar ? 'btn-danger' : 'btn-success',
            onConfirm: function() {
                $.post('adq_dashboard.php', {
                    ajax_toggle_proceso_inhabilitado: 1,
                    sol_cod: solCod,
                    accion: accion
                }, function(res) {
                    if (!res || !res.success) {
                        mostrarAlertaCentro({
                            tipo: 'error',
                            titulo: 'No se pudo actualizar',
                            mensaje: (res && res.message) ? res.message : 'No se pudo actualizar el proceso.'
                        });
                        return;
                    }
                    mostrarAlertaCentro({
                        tipo: 'ok',
                        titulo: 'Operaci?n exitosa',
                        mensaje: res.message || 'Proceso actualizado.',
                        onClose: function() {
                            const params = new URLSearchParams(window.location.search);
                            params.set('tab', 'todos_procesos');
                            window.location.search = params.toString();
                        }
                    });
                }, 'json').fail(function() {
                    mostrarAlertaCentro({
                        tipo: 'error',
                        titulo: 'Error de red',
                        mensaje: 'No se pudo conectar con el servidor. Intente nuevamente.'
                    });
                });
            }
        });
    }

    function abrirSeguimiento(solCod, solNum) {
        currentSolCod = solCod;
        const tituloNum = solNum || solCod;
        $('#lblSeguimientoTitle').text('Requerimiento #' + tituloNum);
        $('#lblSeguimientoSub').text('Seguimiento operativo ? SLA ? Documentos del proceso');
        $('#seguimientoModalBody').html(
            '<div class="adq-seg-loading">'
            + '<div class="adq-seg-loading-spinner"></div>'
            + '<div class="adq-seg-loading-title">Cargando seguimiento</div>'
            + '<div class="adq-seg-loading-text">Obteniendo l&iacute;nea de tiempo y documentos...</div>'
            + '</div>'
        );

        $('#mdlSeguimiento').modal('show');

        $.get('adq_seguimiento.php', { sol_cod: solCod }, function(html) {
            $('#seguimientoModalBody').html(html);
        }).fail(function() {
            $('#seguimientoModalBody').html(
                '<div class="adq-seg-error">'
                + '<i class="bi bi-exclamation-triangle"></i>'
                + '<div><strong>No se pudo cargar el seguimiento</strong><br>'
                + '<span>Intente nuevamente en unos segundos.</span></div>'
                + '</div>'
            );
        });
    }

    function descargarDocumentosZip(solCod) {
        const cod = solCod || currentSolCod;
        if (!cod) {
            alert('No se identifico la solicitud.');
            return;
        }
        const $btn = $('#btnDescargarDocsZip');
        const original = $btn.html();
        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Generando ZIP...');
        const url = 'adq_dashboard.php?ajax_descargar_docs_zip=1&sol_cod=' + encodeURIComponent(cod);
        fetch(url, { credentials: 'same-origin' }).then(function(resp) {
            const ctype = (resp.headers.get('Content-Type') || '').toLowerCase();
            if (ctype.indexOf('application/json') !== -1) {
                return resp.json().then(function(res) {
                    throw new Error((res && res.message) ? res.message : 'No se pudo generar el ZIP.');
                });
            }
            if (!resp.ok) {
                throw new Error('Error al generar el ZIP.');
            }
            return resp.blob().then(function(blob) {
                const a = document.createElement('a');
                const objUrl = window.URL.createObjectURL(blob);
                a.href = objUrl;
                a.download = 'documentos_solicitud_' + cod + '.zip';
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(objUrl);
            });
        }).catch(function(err) {
            alert(err && err.message ? err.message : 'No se pudo descargar el ZIP.');
        }).then(function() {
            $btn.prop('disabled', false).html(original);
        });
    }

    function urlPaginaProcesos(page, pageSize) {
        const params = new URLSearchParams(window.location.search);
        params.set('tab', 'todos_procesos');
        params.set('page', String(page));
        params.set('page_size', String(pageSize));
        return window.location.pathname + '?' + params.toString();
    }

    function renderPagerProcesosServidor() {
        const $panel = $('#panelProcesosDashboard');
        if (!$panel.length) {
            return;
        }
        const $pager = $panel.find('.adq-table-pager');
        const total = parseInt($panel.attr('data-total'), 10) || 0;
        const pageSize = parseInt($panel.attr('data-page-size'), 10) || 20;
        const pages = Math.max(1, parseInt($panel.attr('data-pages'), 10) || 1);
        let page = parseInt($panel.attr('data-page'), 10) || 1;
        if (page < 1) page = 1;
        if (page > pages) page = pages;

        if (total <= 0) {
            $pager.html('<div class="adq-table-pager-info">Sin registros para mostrar</div>');
            return;
        }
        const from = ((page - 1) * pageSize) + 1;
        const to = Math.min(page * pageSize, total);
        let pagesHtml = '';
        const maxBtns = 5;
        let start = Math.max(1, page - Math.floor(maxBtns / 2));
        let end = Math.min(pages, start + maxBtns - 1);
        if (end - start < maxBtns - 1) {
            start = Math.max(1, end - maxBtns + 1);
        }
        for (let i = start; i <= end; i++) {
            pagesHtml += '<a class="btn' + (i === page ? ' active' : '') + '" href="' + urlPaginaProcesos(i, pageSize) + '">' + i + '</a>';
        }
        $pager.html(
            '<div class="adq-table-pager-info">Mostrando ' + from + '-' + to + ' de ' + total + '</div>'
            + '<div class="adq-table-pager-controls">'
            + '<label class="adq-table-pager-size">Filas '
            + '<select class="adq-table-page-size">'
            + '<option value="10"' + (pageSize === 10 ? ' selected' : '') + '>10</option>'
            + '<option value="20"' + (pageSize === 20 ? ' selected' : '') + '>20</option>'
            + '<option value="25"' + (pageSize === 25 ? ' selected' : '') + '>25</option>'
            + '<option value="50"' + (pageSize === 50 ? ' selected' : '') + '>50</option>'
            + '</select></label>'
            + '<div class="adq-table-pager-pages">'
            + (page <= 1
                ? '<button type="button" class="btn" disabled><i class="bi bi-chevron-left"></i></button>'
                : '<a class="btn" href="' + urlPaginaProcesos(page - 1, pageSize) + '" title="Anterior"><i class="bi bi-chevron-left"></i></a>')
            + pagesHtml
            + (page >= pages
                ? '<button type="button" class="btn" disabled><i class="bi bi-chevron-right"></i></button>'
                : '<a class="btn" href="' + urlPaginaProcesos(page + 1, pageSize) + '" title="Siguiente"><i class="bi bi-chevron-right"></i></a>')
            + '</div></div>'
        );
    }

    $(document).ready(function() {
        // Activar pesta?a espec?fica por URL si se solicita
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'todos_procesos') {
            $('a[href="#all-processes-panel"]').tab('show');
        } else if (tab === 'flujos') {
            $('a[href="#flujos-panel"]').tab('show');
        }

        $(document).on('click', '.btn-anular-flujo', function() {
            const $btn = $(this);
            anularFlujoDashboard(
                $btn.attr('data-wfm-cod'),
                $btn.attr('data-wfm-nom') || '',
                $btn.attr('data-inst')
            );
        });

        $(document).on('change', '#filtroFlujoEst', function() {
            $('#frmFiltrosFlujos').submit();
        });

        renderPagerProcesosServidor();
        $(document).on('change', '#panelProcesosDashboard .adq-table-page-size', function() {
            const size = parseInt($(this).val(), 10) || 20;
            window.location.href = urlPaginaProcesos(1, size);
        });

        $('#adqAlertOverlay').on('click', function(e) {
            if (e.target === this) {
                cerrarAlertaCentro();
            }
        });
        $(document).on('keydown.adqAlert', function(e) {
            if (e.key === 'Escape' && $('#adqAlertOverlay').hasClass('is-visible')) {
                cerrarAlertaCentro();
            }
        });

        $('#mdlReportePdf').on('hidden.bs.modal', function() {
            liberarReportePdfBlob();
            $('#reportePdfFrame').hide().attr('src', 'about:blank');
            $('#reportePdfError').hide();
            $('#reportePdfLoading').css('display', 'flex');
            $('#btnImprimirReportePdf, #btnDescargarReportePdf').prop('disabled', true);
        });
    });
</script>
</body>
</html>
