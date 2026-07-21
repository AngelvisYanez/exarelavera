<?php
/**
 * EXA Adquisiciones - Vista Detallada y Seguimiento de Requerimientos (Linea de Tiempo y SLA)
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');
require_once('../LOGICA/adq_adquisiciones_log.php');

header('Content-Type: text/html; charset=UTF-8');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$adq_log = new adq_adquisiciones_log($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);
$wf_ctx = $wf_mgr->resolverContextoUsuario($Ses_Emp_Cod);

$sol_cod = isset($_GET['sol_cod']) ? intval($_GET['sol_cod']) : 0;
$usu_cod = $wf_ctx['usu_cod'];
$dep_cod = $wf_ctx['dep_cod'];
$perfiles_ids = $wf_ctx['perfiles_ids'];

if ($sol_cod <= 0) {
    echo "<div class='alert alert-danger m-2 small'>No se encontro el requerimiento solicitado.</div>";
    exit;
}

$sol = $obBD_con1->getRowConsultaSql("
    SELECT s.*, tr.Trq_Des, tr.Trq_Req_Fac, tr.Trq_Req_Cot, tr.Trq_Min_Cot, tr.Trq_Req_Pre, tr.Trq_Req_Adj, tr.Trq_Req_Pro, tr.Trq_Tiempo_Est, tr.Trq_Per_Cie,
           IFNULL(u.Usu_Ced, '') as Usu_Nom, IFNULL(d.Dep_Des, '') as Dep_Des,
           IFNULL(p.Prs_Nom, '') as Sol_Nom, IFNULL(p.Prs_Ape, '') as Sol_Ape,
           i.Ins_Cod, i.Nod_Act, i.Ins_Est, i.Ins_Fec_Ini, i.Ins_Fec_Fin,
           n.Nod_Nom, n.Nod_Tip
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    LEFT JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    LEFT JOIN wf_instancias i ON i.Ins_Cod = (
        SELECT MAX(i2.Ins_Cod)
        FROM wf_instancias i2
        WHERE i2.Ins_Ent_Typ = 'adq_solicitudes' AND i2.Ins_Ent_Cod = s.Sol_Cod
    )
    LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    WHERE s.Sol_Cod = $sol_cod;", $obBD_conexion);

if (empty($sol)) {
    echo "<div class='alert alert-danger m-2 small'>No se encontro el requerimiento solicitado.</div>";
    exit;
}

$sol = $adq_log->aplicarRequisitosEfectivos($sol);

$es_gerencial_admin = ($usu_cod == 1) || count(array_intersect(array(1, 2), $_SESSION['Ses_Lis_Per'])) > 0;

if (!$wf_mgr->puedeUsuarioVerSeguimientoSolicitud($sol, $usu_cod, $dep_cod, $perfiles_ids, $es_gerencial_admin)) {
    echo "<div class='alert alert-danger m-2 small'><i class='bi bi-shield-slash'></i> Acceso denegado. No tiene permisos para visualizar el seguimiento de esta solicitud.</div>";
    exit;
}

$items = $obBD_con1->getArrayConsultaSql("
    SELECT d.*, i.Ite_Lar AS Pro_Nom
    FROM adq_solicitudes_det d
    LEFT JOIN producto pr ON pr.Pro_Cod = d.Pro_Cod
    LEFT JOIN item i ON i.Ite_Cod = pr.Ite_Cod
    WHERE d.Sol_Cod = $sol_cod
    ORDER BY d.Sde_Int;", $obBD_conexion);
if ($items === false || $items === null) {
    $items = array();
}
$cotizaciones = $obBD_con1->getArrayConsultaSql("SELECT c.*, p.Prs_Nom, p.Prs_Ape, pr.Prv_Com FROM adq_solicitudes_cotizaciones c INNER JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod INNER JOIN persona p ON p.Prs_Cod = pr.Prs_Cod WHERE c.Sol_Cod = $sol_cod;", $obBD_conexion);
$historial = array();
$historial_vista = array();
if (!empty($sol['Ins_Cod'])) {
    $historial = $obBD_con1->getArrayConsultaSql("
        SELECT h.*,
               COALESCE(n.Nod_Nom, CONCAT('Nodo #', h.Nod_Cod)) AS Nod_Nom,
               COALESCE(n.Nod_Tip, 'PASO') AS Nod_Tip,
               n.Dep_Cod AS Nodo_Dep_Cod,
               n.Per_Cod AS Nodo_Per_Cod,
               n.Nod_Usu_Asig AS Nodo_Usu_Asig,
               p.Prs_Nom, p.Prs_Ape, d.Wde_Des AS Dep_Des
        FROM wf_instancias_nodos h
        LEFT JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
        LEFT JOIN usuarios u ON u.Usu_Cod = h.Usu_Cod
        LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
        LEFT JOIN wf_departamentos d ON d.Dep_Cod = h.Dep_Cod
        WHERE h.Ins_Cod = {$sol['Ins_Cod']}
        ORDER BY h.Isn_Fec ASC, h.Isn_Cod ASC;", $obBD_conexion);
    if ($historial === false || $historial === null) {
        $historial = array();
    }
    $historial = $wf_mgr->normalizarHistorialFirmas(
        $historial,
        isset($sol['Ins_Est']) ? $sol['Ins_Est'] : '',
        isset($sol['Nod_Act']) ? intval($sol['Nod_Act']) : 0
    );
    $historial = $wf_mgr->agregarNodoPendienteHistorial(
        $historial,
        isset($sol['Ins_Est']) ? $sol['Ins_Est'] : '',
        isset($sol['Nod_Act']) ? intval($sol['Nod_Act']) : 0,
        0
    );
    $historial = $wf_mgr->agregarRechazoHistorialSiFalta(
        $historial,
        intval($sol['Ins_Cod']),
        isset($sol['Sol_Est']) ? $sol['Sol_Est'] : '',
        isset($sol['Ins_Est']) ? $sol['Ins_Est'] : ''
    );
    $historial = $adq_log->enriquecerHistorialConArchivos($historial, $sol_cod);
    $historial_vista = $historial;
    usort($historial_vista, function ($a, $b) {
        $fa = isset($a['Isn_Fec']) ? $a['Isn_Fec'] : '';
        $fb = isset($b['Isn_Fec']) ? $b['Isn_Fec'] : '';
        if ($fa === $fb) {
            return intval(isset($b['Isn_Cod']) ? $b['Isn_Cod'] : 0) - intval(isset($a['Isn_Cod']) ? $a['Isn_Cod'] : 0);
        }
        return strcmp($fb, $fa);
    });
}

$requisitos_faltantes = array();
if ($sol['Ins_Est'] === 'P') {
    if ($sol['Sol_Req_Fac'] == 1) {
        $compras_v = $wf_mgr->getComprasVinculadas($sol_cod);
        if (empty($compras_v)) {
            $requisitos_faltantes[] = array('req' => 'Factura de Compra', 'desc' => "Se requiere vincular al menos una factura f\xC3\xADsica de compra para cerrar el proceso.");
        }
    }
    if ($sol['Sol_Req_Cot'] == 1) {
        $min_cot = intval($sol['Sol_Min_Cot']) ?: 1;
        if (count($cotizaciones) < $min_cot) {
            $requisitos_faltantes[] = array('req' => 'Sustento de Cotizaciones', 'desc' => "Se requiere cargar un m\xC3\xADnimo de $min_cot cotizaciones f\xC3\xADsicas. Actualmente posee " . count($cotizaciones) . ".");
        }
        $ganadora = false;
        foreach ($cotizaciones as $c) {
            if ($c['Cot_Sel'] == 1) {
                $ganadora = true;
            }
        }
        if (!$ganadora) {
            $requisitos_faltantes[] = array('req' => 'Cotizaci' . "\xC3\xB3" . 'n Seleccionada', 'desc' => 'Debe marcar cu' . "\xC3\xA1" . 'l de las cotizaciones f' . "\xC3\xAD" . 'sicas cargadas es la ganadora/seleccionada.');
        }
    }
    if ($sol['Sol_Req_Pro'] == 1 && empty($sol['Prv_Sug'])) {
        $requisitos_faltantes[] = array('req' => 'Proveedor Sugerido', 'desc' => 'Debe ingresar un proveedor sugerido para la adquisici' . "\xC3\xB3" . 'n.');
    }
}

function adq_h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function adq_render_historial_archivos($archivos) {
    if (empty($archivos) || !is_array($archivos)) {
        return '';
    }
    $html = '<div class="adq-hist-archivos" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">';
    foreach ($archivos as $arch) {
        if (empty($arch['path'])) {
            continue;
        }
        $label = !empty($arch['label']) ? $arch['label'] : 'Archivo';
        $ext = strtolower(pathinfo($arch['path'], PATHINFO_EXTENSION));
        $es_firmado = !empty($arch['es_expediente_firmado']);
        $es_exp = $es_firmado || !empty($arch['es_expediente']);
        $icon = ($ext === 'pdf') ? 'bi-file-earmark-pdf' : 'bi-paperclip';
        $btn = 'btn-outline-primary';
        if ($es_firmado) {
            $icon = 'bi-file-earmark-check';
            $btn = 'btn-outline-success';
        } elseif ($es_exp) {
            $icon = 'bi-file-earmark-lock2';
            $btn = 'btn-outline-secondary';
        }
        $html .= '<a href="../../DATA/' . adq_h($arch['path']) . '" target="_blank" class="btn btn-xs ' . $btn . '" style="font-size:11px;padding:3px 8px;">'
            . '<i class="bi ' . $icon . '"></i> ' . adq_h($label) . '</a>';
    }
    $html .= '</div>';
    return $html;
}

function adq_render_historial_facturas($facturas) {
    if (empty($facturas) || !is_array($facturas)) {
        return '';
    }
    $html = '<div class="adq-hist-facturas mt-2">';
    foreach ($facturas as $f) {
        $numero = !empty($f['numero']) ? $f['numero'] : ('#' . intval(isset($f['cop_cod']) ? $f['cop_cod'] : 0));
        $proveedor = !empty($f['proveedor']) ? $f['proveedor'] : 'Proveedor';
        $fecha = !empty($f['fecha']) ? '<span class="text-muted">(' . adq_h($f['fecha']) . ')</span>' : '';
        $total = (isset($f['total']) && floatval($f['total']) > 0)
            ? '<span class="fw-bold font-monospace ms-1">$ ' . number_format(floatval($f['total']), 2, '.', '') . '</span>'
            : '';
        $pdf = !empty($f['link'])
            ? '<a href="' . adq_h($f['link']) . '" target="_blank" class="btn btn-xs btn-outline-primary ms-2" style="font-size:11px;padding:2px 8px;"><i class="bi bi-file-earmark-pdf"></i> Ver PDF</a>'
            : '';
        $des = !empty($f['des'])
            ? '<div class="text-muted mt-1" style="font-size:11px;">' . adq_h($f['des']) . '</div>'
            : '';
        $compsHtml = '';
        if (!empty($f['comprobantes']) && is_array($f['comprobantes'])) {
            $compsHtml = '<div class="adq-hist-comprobantes mt-1" style="padding-left:8px;border-left:2px solid #cbd5e1;">'
                . '<div class="text-muted" style="font-size:11px;margin-bottom:2px;"><i class="bi bi-journal-text"></i> Comprobantes de pago:</div>';
            foreach ($f['comprobantes'] as $c) {
                $codigo = !empty($c['codigo']) ? $c['codigo'] : '#';
                $cFecha = !empty($c['fecha']) ? '<span class="text-muted">(' . adq_h($c['fecha']) . ')</span>' : '';
                $cVal = (isset($c['valor']) && floatval($c['valor']) > 0)
                    ? '<span class="font-monospace ms-1">$ ' . number_format(floatval($c['valor']), 2, '.', '') . '</span>'
                    : '';
                $cForma = !empty($c['forma']) ? '<span class="text-muted ms-1">' . adq_h($c['forma']) . '</span>' : '';
                $cLink = !empty($c['link'])
                    ? '<a href="' . adq_h($c['link']) . '" target="_blank" class="btn btn-xs btn-outline-secondary ms-1" style="font-size:10px;padding:1px 6px;"><i class="bi bi-box-arrow-up-right"></i> ' . adq_h($codigo) . '</a>'
                    : '<span class="fw-semibold">' . adq_h($codigo) . '</span>';
                $compsHtml .= '<div style="font-size:11px;margin-bottom:2px;">' . $cLink . ' ' . $cFecha . $cVal . $cForma . '</div>';
            }
            $compsHtml .= '</div>';
        }
        $html .= '<div class="border rounded p-2 mb-1 bg-white small">'
            . '<strong><i class="bi bi-receipt-cutoff"></i> Factura # ' . adq_h($numero) . '</strong> - ' . adq_h($proveedor)
            . ' ' . $fecha . $total . $pdf . $des . $compsHtml
            . '</div>';
    }
    $html .= '</div>';
    return $html;
}

function adq_hist_iniciales($nombre) {
    $parts = preg_split('/\s+/', trim((string)$nombre));
    $parts = array_values(array_filter($parts));
    if (empty($parts)) {
        return 'SY';
    }
    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 2));
    }
    return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
}

function adq_hist_fecha($fec) {
    $ts = strtotime((string)$fec);
    if (!$ts) {
        return 'Sin movimiento';
    }
    return date('d/m/Y H:i', $ts);
}

function adq_render_historial_badge($h) {
    $actionBadge = '';
    $itemClass = '';
    $mk = function ($txt, $bg, $fg = '#ffffff') {
        return '<span class="badge adq-hist-badge" style="background-color:' . $bg . ' !important;color:' . $fg . ' !important;">' . $txt . '</span>';
    };
    if (!empty($h['Fin_Pendiente'])) {
        $actionBadge = $mk('Pendiente cierre', '#0284c7');
        $itemClass = 'active';
    } elseif (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'DECISION') {
        $actionBadge = $mk('Decisi&oacute;n', '#d97706');
        $itemClass = 'warning';
    } elseif (!empty($h['Pendiente_Aprobacion']) || $h['Isn_Acc'] === 'PENDIENTE') {
        $pendTxt = 'Pendiente de aprobaci&oacute;n';
        if (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'TAREA') {
            $pendTxt = 'Tarea pendiente';
        } elseif (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'FIN') {
            $pendTxt = 'Pendiente cierre';
        } elseif (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'AVANCE') {
            $pendTxt = 'Pendiente de avance';
        } elseif (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'FISCALIZACION') {
            $pendTxt = 'Pendiente de fiscalizaci&oacute;n';
        } elseif (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'FACTURA') {
            $pendTxt = 'Pendiente de factura';
        }
        $actionBadge = $mk($pendTxt, '#2563eb');
        $itemClass = 'active';
    } elseif ($h['Isn_Acc'] === 'CREAR') {
        $actionBadge = $mk('Inici&oacute; pedido', '#64748b');
        $itemClass = 'active';
    } elseif ($h['Isn_Acc'] === 'APROBAR') {
        $actionBadge = $mk('Aprobado', '#059669');
        $itemClass = 'success';
    } elseif ($h['Isn_Acc'] === 'COMPLETAR') {
        $actionBadge = $mk('Tarea completada', '#059669');
        $itemClass = 'success';
    } elseif ($h['Isn_Acc'] === 'OBSERVAR') {
        $actionBadge = $mk('Observado', '#d97706', '#fffbeb');
        $itemClass = 'warning';
    } elseif ($h['Isn_Acc'] === 'DEVOLVER') {
        $actionBadge = $mk('Devuelto', '#4b5563');
        $itemClass = 'active';
    } elseif ($h['Isn_Acc'] === 'RECHAZAR') {
        $actionBadge = $mk('Rechazado', '#dc2626');
        $itemClass = 'danger';
    } elseif ($h['Isn_Acc'] === 'REENVIAR') {
        $actionBadge = $mk('Reenvi&oacute; correcci&oacute;n', '#0284c7');
        $itemClass = 'active';
    } elseif ($h['Isn_Acc'] === 'AVANCE') {
        $actionBadge = $mk('Documentos cargados', '#0284c7');
        $itemClass = 'active';
    } elseif ($h['Isn_Acc'] === 'COTIZAR') {
        $actionBadge = $mk('Proformas cargadas', '#2563eb');
        $itemClass = 'active';
    } elseif ($h['Isn_Acc'] === 'CONDICIONAL') {
        $actionBadge = $mk('Rama', '#d97706');
        $itemClass = 'warning';
    }
    return array('badge' => $actionBadge, 'class' => $itemClass);
}

function adq_render_historial_item($h, $num_proceso, $mostrar_etapa) {
    $actor = !empty($h['Actor_Nom']) ? $h['Actor_Nom'] : ($h['Prs_Nom'] ? trim($h['Prs_Nom'] . ' ' . $h['Prs_Ape']) : ($h['Dep_Des'] ?: 'Sistema'));
    $actor_modo = !empty($h['Actor_Modo']) ? $h['Actor_Modo'] : 'Por';
    $badge = adq_render_historial_badge($h);
    $actionBadge = $badge['badge'];
    $itemClass = $badge['class'];
    $nod_nom = !empty($h['Nod_Nom']) ? $h['Nod_Nom'] : 'Etapa';
    $fecha = adq_hist_fecha(isset($h['Isn_Fec']) ? $h['Isn_Fec'] : '');
    $iniciales = adq_hist_iniciales($actor);
    $num_label = is_numeric($num_proceso) ? (string)(0 + $num_proceso) : trim((string)$num_proceso);
    if ($num_label === '') {
        $num_label = '0';
    }
    $es_decision = (isset($h['Nod_Tip']) && $h['Nod_Tip'] === 'DECISION');
    ob_start();
    ?>
    <div class="adq-timeline-item <?php echo $itemClass; ?>">
        <div class="adq-timeline-content">
            <div class="adq-timeline-header">
                <span class="adq-timeline-title">
                    <span class="adq-timeline-step">
                        <span class="adq-timeline-step-num" title="Nodo / tarea <?php echo adq_h($num_label); ?>"><?php echo adq_h($num_label); ?></span>
                        <?php if ($mostrar_etapa) { ?>
                            <span class="adq-timeline-stage"><?php echo adq_h($nod_nom); ?></span>
                        <?php } ?>
                    </span>
                    <?php echo $actionBadge; ?>
                </span>
                <span class="adq-timeline-date"><i class="bi bi-calendar3"></i> <?php echo adq_h($fecha); ?></span>
            </div>
            <div class="adq-timeline-body">
                <?php if (!$es_decision) { ?>
                <div class="adq-hist-actor">
                    <span class="adq-hist-avatar" aria-hidden="true"><?php echo adq_h($iniciales); ?></span>
                    <span class="adq-hist-actor-meta">
                        <span class="adq-hist-actor-mode"><?php echo adq_h($actor_modo); ?></span>
                        <span class="adq-hist-actor-name"><?php echo adq_h($actor); ?></span>
                    </span>
                </div>
                <?php } ?>
                <?php if (!empty($h['Isn_Com'])) { ?>
                    <div class="adq-timeline-comment"><?php echo adq_h($h['Isn_Com']); ?></div>
                <?php } ?>
                <?php if (!$es_decision) {
                    echo adq_render_historial_facturas(isset($h['facturas']) ? $h['facturas'] : array());
                    echo adq_render_historial_archivos(isset($h['archivos']) ? $h['archivos'] : array());
                } ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

$flow_visual = array('nodos' => array());
$historial_por_nodo = array();
$historial_nodo_html = array();
$nodos_meta = array();
$orden_nodo = array();

if (!empty($sol['Ins_Cod'])) {
    $flow_visual = $wf_mgr->getVisualFlowData($sol['Ins_Cod']);
    if (!empty($flow_visual['nodos'])) {
        $idx_orden = 0;
        foreach ($flow_visual['nodos'] as $node) {
            $nid = intval($node['id']);
            $idx_orden++;
            $orden_nodo[$nid] = $idx_orden;
            $historial_por_nodo[$nid] = array();
            $nodos_meta[$nid] = array(
                'nombre' => $node['nombre'],
                'tipo' => $node['tipo'],
                'color' => $node['color'],
                'orden' => $idx_orden
            );
        }
    }
    foreach ($historial as $h) {
        $etapa_cod = intval(isset($h['Etapa_Nod_Cod']) ? $h['Etapa_Nod_Cod'] : (isset($h['Nod_Cod']) ? $h['Nod_Cod'] : 0));
        if ($etapa_cod <= 0) {
            continue;
        }
        if (!isset($historial_por_nodo[$etapa_cod])) {
            $historial_por_nodo[$etapa_cod] = array();
            $nodos_meta[$etapa_cod] = array(
                'nombre' => isset($h['Nod_Nom']) ? $h['Nod_Nom'] : ('Nodo #' . $etapa_cod),
                'tipo' => isset($h['Nod_Tip']) ? $h['Nod_Tip'] : 'PASO',
                'color' => 'grey'
            );
        }
        $historial_por_nodo[$etapa_cod][] = $h;
    }
    foreach ($historial_por_nodo as $nid => $items) {
        usort($items, function ($a, $b) {
            $fa = isset($a['Isn_Fec']) ? $a['Isn_Fec'] : '';
            $fb = isset($b['Isn_Fec']) ? $b['Isn_Fec'] : '';
            if ($fa === $fb) {
                return intval(isset($a['Isn_Cod']) ? $a['Isn_Cod'] : 0) - intval(isset($b['Isn_Cod']) ? $b['Isn_Cod'] : 0);
            }
            return strcmp($fa, $fb);
        });
        $num_nodo = isset($orden_nodo[intval($nid)]) ? intval($orden_nodo[intval($nid)]) : 0;
        if ($num_nodo <= 0) {
            $num_nodo = intval($nid);
        }
        $html_items = '';
        $idx_mov = 0;
        $total_mov = count($items);
        foreach ($items as $item) {
            $idx_mov++;
            // Numero del nodo en el flujo; si hay varios movimientos en la misma etapa: 3.1, 3.2...
            $etiqueta_num = ($total_mov > 1) ? ($num_nodo . '.' . $idx_mov) : $num_nodo;
            $html_items .= adq_render_historial_item($item, $etiqueta_num, true);
        }
        if ($html_items === '') {
            $html_items = '<div class="text-center text-muted py-3 small">No hay tareas registradas en esta etapa.</div>';
        }
        $historial_nodo_html[$nid] = $html_items;
    }
}
?>
<div class="row">
    <div class="col-xs-12 col-md-5" style="margin-bottom: 12px;">
        <div class="adq-detail-card" style="min-height: 155px; padding: 8px 12px;">
            <h5 class="adq-section-header" style="color: #1e3a8a; border-bottom-color: #cbd5e1; margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-file-earmark-text"></i> Detalles del Requerimiento</h5>
            <table class="table table-condensed table-borderless small mb-0" style="font-size: 11px; margin-bottom: 0;">
                <tr>
                    <td class="fw-bold text-muted" style="width: 80px; padding: 2px 4px; border:none;">Num. Sol:</td>
                    <td class="fw-bold text-dark" style="padding: 2px 4px; border:none;"><?php echo adq_h($sol['Sol_Num']); ?></td>
                    <td class="fw-bold text-muted" style="width: 80px; padding: 2px 4px; border:none;">Fecha:</td>
                    <td style="padding: 2px 4px; border:none;"><?php echo date('Y-m-d H:i', strtotime($sol['Sol_Fec'])); ?></td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none;">Solicitante:</td>
                    <td style="padding: 2px 4px; border:none;"><?php echo adq_h(trim($sol['Sol_Nom'] . ' ' . $sol['Sol_Ape'])); ?> (<?php echo adq_h($sol['Usu_Nom']); ?>)</td>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none;">Depto:</td>
                    <td style="padding: 2px 4px; border:none;"><?php echo adq_h($sol['Dep_Des']); ?></td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none;">Tipo Req:</td>
                    <td class="fw-semibold text-primary" style="padding: 2px 4px; border:none;"><?php echo adq_h($sol['Trq_Des']); ?></td>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none;">Monto Est:</td>
                    <td class="fw-bold font-monospace text-success" style="padding: 2px 4px; border:none;">$ <?php echo number_format($sol['Sol_Val_Est'], 2); ?></td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none; vertical-align: top;">Justificaci&oacute;n:</td>
                    <td colspan="3" class="text-muted" style="font-size: 10.5px; padding: 2px 4px; border:none; line-height: 1.2;"><?php echo adq_h($sol['Sol_Jus']); ?></td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none; vertical-align: top;">Descripci&oacute;n:</td>
                    <td colspan="3" class="text-muted" style="font-size: 10.5px; padding: 2px 4px; border:none; line-height: 1.2;"><?php echo adq_h($sol['Sol_Det']); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="col-xs-12 col-md-7" style="margin-bottom: 12px;">
        <div class="adq-detail-card" style="min-height: 155px; padding: 8px 12px;">
            <h5 class="adq-section-header" style="color: #dc3545; border-bottom-color: #fca5a5; margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-shield-exclamation"></i> &iquest;Qu&eacute; falta para avanzar?</h5>
            <?php if (empty($requisitos_faltantes)) { ?>
                <div class="alert alert-success p-2 small mb-0" style="padding: 6px 10px; font-size: 11px; margin-bottom: 0;"><i class="bi bi-check-circle-fill"></i> &iexcl;Todos los requisitos de este tipo de requerimiento est&aacute;n cubiertos! El flujo puede avanzar normalmente.</div>
            <?php } else { ?>
                <div style="max-height: 110px; overflow-y: auto;">
                    <?php foreach ($requisitos_faltantes as $rf) { ?>
                        <div style="padding: 4px 0; border-bottom: 1px solid #f1f5f9;">
                            <span class="badge bg-danger" style="font-size: 9px; padding: 2px 6px; background-color: #ef4444 !important; color: #fff !important; display: inline-block; margin-bottom: 2px;"><i class="bi bi-x-circle"></i> <?php echo adq_h($rf['req']); ?></span>
                            <span class="text-muted" style="font-size: 10.5px; margin-left: 5px;"><?php echo adq_h($rf['desc']); ?></span>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>

    <div style="clear: both;"></div>

    <div class="col-xs-12 col-md-12" style="clear: both; float: left;">
        <div class="adq-seg-toolbar">
            <div class="adq-seg-toolbar-copy">
                <strong><i class="bi bi-archive"></i> Expediente documental</strong>
                <span>Descarga en un solo ZIP todos los archivos cargados en los procesos de este requerimiento.</span>
            </div>
            <button type="button" class="btn btn-success btn-sm" id="btnDescargarDocsZip" onclick="descargarDocumentosZip(<?php echo intval($sol_cod); ?>)">
                <i class="bi bi-file-earmark-zip"></i> Descargar todos los documentos generados
            </button>
        </div>
    </div>

    <div class="col-xs-12 col-md-12" style="margin-top: 0; margin-bottom: 10px; clear: both; float: left;">
        <div class="adq-detail-card" style="padding: 10px 14px;">
            <h5 class="adq-section-header" style="color: #475569; border-bottom-color: #cbd5e1; margin-bottom: 8px; padding-bottom: 6px;"><i class="bi bi-clock-history"></i> L&iacute;nea de Tiempo del Proceso (SLA) <small class="text-muted" style="font-weight: normal; text-transform: none; letter-spacing: 0;">— clic en un nodo para ver sus tareas</small></h5>
            <div class="tracker-wrapper adq-seg-flow-tracker" id="segFlowTracker">
                <?php
                if (!empty($flow_visual['nodos'])) {
                    foreach ($flow_visual['nodos'] as $idx => $node) {
                        if ($idx > 0) {
                            echo '<div class="tracker-arrow"><i class="bi bi-arrow-right-short"></i></div>';
                        }

                        $tiempo_nodo = '';
                        $sla_dias = intval(isset($node['sla_dias']) ? $node['sla_dias'] : 0);
                        $sla_estado = isset($node['sla_estado']) ? $node['sla_estado'] : 'sin_tiempo';
                        if ($sla_estado === 'sin_tiempo') {
                            $tiempo_nodo = "<br><span style='display:inline-block;margin-top:5px;padding:2px 6px;border-radius:10px;background:#e2e8f0;color:#475569;font-size:8px;font-weight:700;'><i class='bi bi-dash-circle'></i> Sin tiempo determinado</span>";
                        } elseif ($sla_estado === 'no_iniciado') {
                            $tiempo_nodo = "<br><span style='display:inline-block;margin-top:5px;padding:2px 6px;border-radius:10px;background:#f1f5f9;color:#64748b;font-size:8px;font-weight:700;'><i class='bi bi-clock'></i> SLA: {$sla_dias} d&iacute;as &middot; A&uacute;n no iniciado</span>";
                        } else {
                            $transcurridos = number_format(floatval($node['sla_dias_transcurridos']), 1);
                            $limite = !empty($node['sla_fecha_limite'])
                                ? date('d/m/Y H:i', strtotime($node['sla_fecha_limite']))
                                : '';
                            if ($sla_estado === 'retrasado') {
                                $retraso = number_format(floatval($node['sla_dias_retraso']), 1);
                                $tiempo_nodo = "<br><span style='display:inline-block;margin-top:5px;padding:3px 7px;border-radius:10px;background:#fee2e2;color:#b91c1c;font-size:8px;font-weight:800;'><i class='bi bi-exclamation-triangle-fill'></i> Retraso: {$retraso} d&iacute;as</span>";
                            } else {
                                $tiempo_nodo = "<br><span style='display:inline-block;margin-top:5px;padding:3px 7px;border-radius:10px;background:#dcfce7;color:#166534;font-size:8px;font-weight:800;'><i class='bi bi-check-circle-fill'></i> En plazo: {$transcurridos} / {$sla_dias} d&iacute;as</span>";
                            }
                            if ($limite !== '') {
                                $tiempo_nodo .= "<br><span style='font-size:8px;color:#64748b;'><i class='bi bi-calendar-check'></i> L&iacute;mite: {$limite}</span>";
                            }
                        }

                        $actor_html = '';
                        if (!empty($node['pendiente_meta'])) {
                            $pm = $node['pendiente_meta'];
                            $actor_html = "<br><span class='tracker-actor tracker-pendiente'>";
                            $actor_html .= "<span><i class='bi bi-hourglass-split'></i> Pendiente de aprobaci&oacute;n</span>";
                            if (!empty($pm['depto'])) {
                                $actor_html .= "<span>Depto: " . adq_h($pm['depto']) . "</span>";
                            }
                            if (!empty($pm['asignados'])) {
                                $actor_html .= "<span>Asignado: " . adq_h($pm['asignados']) . "</span>";
                            }
                            if (!empty($pm['enviado_por'])) {
                                $actor_html .= "<span>Enviado por: " . adq_h($pm['enviado_por']) . "</span>";
                            }
                            $actor_html .= "</span>";
                        } elseif (!empty($node['actor_label'])) {
                            $actor_html = "<br><span class='tracker-actor'><i class='bi bi-person-check'></i> " . adq_h($node['actor_label']) . "</span>";
                        }

                        echo "
                        <div class='tracker-node tracker-node-clickable color-{$node['color']}' data-nod-id='" . intval($node['id']) . "' data-nod-nom='" . adq_h($node['nombre']) . "' data-nod-tip='" . adq_h($node['tipo']) . "' title='Ver tareas de esta etapa'>
                            <i class='bi bi-circle-fill'></i> " . adq_h($node['nombre']) . "<br>
                            <span style='font-size: 9px; font-weight: normal; opacity: 0.8;'>[" . adq_h($node['tipo']) . "]</span>
                            $actor_html
                            $tiempo_nodo
                        </div>";
                    }
                } else {
                    echo "<div class='text-muted small text-center py-2'>La solicitud aun no tiene una instancia de workflow activa.</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <script type="application/json" id="segHistorialNodoHtml"><?php
    $json_flags = defined('JSON_HEX_TAG') ? (JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : 0;
    echo json_encode($historial_nodo_html, $json_flags);
    ?></script>
    <script type="application/json" id="segNodosMeta"><?php echo json_encode($nodos_meta, $json_flags); ?></script>

    <div class="col-xs-12 col-md-12" style="margin-top: 20px; margin-bottom: 20px; clear: both; float: left;">
        <div class="adq-detail-card">
            <h5 class="adq-section-header"><i class="bi bi-journal-text"></i> Historial de Movimientos y Comentarios</h5>
            <div class="adq-timeline">
                <?php if (empty($historial)) { ?>
                    <div class="text-center text-muted py-3 small">No se registran movimientos en el workflow todav&iacute;a.</div>
                <?php } else {
                    $total_hist = count($historial_vista);
                    $idx_hist = 0;
                    foreach ($historial_vista as $h) {
                        $num_proceso = $total_hist - $idx_hist;
                        $idx_hist++;
                        echo adq_render_historial_item($h, $num_proceso, true);
                    }
                } ?>
            </div>
        </div>
    </div>
</div>
<?php exit; ?>
