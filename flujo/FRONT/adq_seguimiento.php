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

$sol_cod = isset($_GET['sol_cod']) ? intval($_GET['sol_cod']) : 0;
$usu_cod = $_SESSION['Ses_Usu_Cod'];

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

$es_creador = ($sol['Usu_Sol'] == $usu_cod);
$es_gerencial_admin = ($usu_cod == 1) || count(array_intersect(array(1, 2), $_SESSION['Ses_Lis_Per'])) > 0;

$es_participante_turno = false;
if (!empty($sol['Nod_Act'])) {
    $nodo_act = $obBD_con1->getRowConsultaSql("SELECT Dep_Cod, Per_Cod, Nod_Usu_Asig FROM wf_nodos WHERE Nod_Cod = {$sol['Nod_Act']};", $obBD_conexion);
    if (!empty($nodo_act)) {
        $perfiles_ids = $_SESSION['Ses_Lis_Per'];
        $dep_cod_usu = $_SESSION['Ses_Dep_Cod'];

        $pertenece_depto = false;
        if (!empty($nodo_act['Dep_Cod'])) {
            $check_dep_names = $obBD_con1->getRowConsultaSql("
                SELECT COUNT(*) as Q
                FROM departamen d1
                INNER JOIN departamen d2 ON d2.Dep_Des = d1.Dep_Des
                WHERE d1.Dep_Cod = {$nodo_act['Dep_Cod']} AND d2.Dep_Cod = $dep_cod_usu;", $obBD_conexion);
            $pertenece_depto = (!empty($check_dep_names) && $check_dep_names['Q'] > 0);
        }
        $pertenece_perfil = in_array($nodo_act['Per_Cod'], $perfiles_ids);
        $es_usuario_asignado = ($nodo_act['Nod_Usu_Asig'] === 'TODOS' || $nodo_act['Nod_Usu_Asig'] === '' || $nodo_act['Nod_Usu_Asig'] === null || in_array($usu_cod, explode(',', $nodo_act['Nod_Usu_Asig'])));

        if ($es_usuario_asignado && (($pertenece_depto) || $pertenece_perfil)) {
            $es_participante_turno = true;
        }
    }
}

if (!$es_creador && !$es_gerencial_admin && !$es_participante_turno) {
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
if (!empty($sol['Ins_Cod'])) {
    $historial = $obBD_con1->getArrayConsultaSql("
        SELECT h.*,
               COALESCE(n.Nod_Nom, CONCAT('Nodo #', h.Nod_Cod)) AS Nod_Nom,
               COALESCE(n.Nod_Tip, 'PASO') AS Nod_Tip,
               p.Prs_Nom, p.Prs_Ape, d.Wde_Des AS Dep_Des
        FROM wf_instancias_nodos h
        LEFT JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
        LEFT JOIN usuarios u ON u.Usu_Cod = h.Usu_Cod
        LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
        LEFT JOIN wf_departamentos d ON d.Wde_Cod = h.Dep_Cod
        WHERE h.Ins_Cod = {$sol['Ins_Cod']}
        ORDER BY h.Isn_Fec ASC;", $obBD_conexion);
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

    <div class="col-xs-12 col-md-12" style="margin-top: 5px; margin-bottom: 10px; clear: both; float: left;">
        <div class="adq-detail-card" style="padding: 8px 12px;">
            <h5 class="adq-section-header" style="color: #475569; border-bottom-color: #cbd5e1; margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-clock-history"></i> L&iacute;nea de Tiempo del Proceso (SLA)</h5>
            <div class="tracker-wrapper">
                <?php
                if (!empty($sol['Ins_Cod'])) {
                    $flow_visual = $wf_mgr->getVisualFlowData($sol['Ins_Cod']);
                    foreach ($flow_visual['nodos'] as $idx => $node) {
                        if ($idx > 0) {
                            echo '<div class="tracker-arrow"><i class="bi bi-arrow-right-short"></i></div>';
                        }

                        $tiempo_nodo = '';
                        $fecha_entrada = null;
                        $fecha_salida = null;

                        foreach ($historial as $h) {
                            if ($h['Nod_Cod'] == $node['id']) {
                                if ($h['Isn_Acc'] === 'CREAR' || $h['Isn_Acc'] === 'APROBAR' || $h['Isn_Acc'] === 'OBSERVAR' || $h['Isn_Acc'] === 'DEVOLVER') {
                                    $fecha_entrada = $h['Isn_Fec'];
                                }
                            }
                        }

                        if ($fecha_entrada) {
                            foreach ($historial as $h) {
                                if ($h['Isn_Fec'] > $fecha_entrada) {
                                    $fecha_salida = $h['Isn_Fec'];
                                    break;
                                }
                            }
                            if (!$fecha_salida && $node['color'] === 'blue') {
                                $fecha_salida = date('Y-m-d H:i:s');
                            }

                            if ($fecha_salida) {
                                $diff = strtotime($fecha_salida) - strtotime($fecha_entrada);
                                $dias = round($diff / 86400, 1);
                                $tiempo_nodo = "<br><span class='text-muted' style='font-size: 8px;'><i class='bi bi-hourglass-split'></i> $dias d&iacute;as</span>";
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
                        <div class='tracker-node color-{$node['color']}'>
                            <i class='bi bi-circle-fill'></i> " . adq_h($node['nombre']) . "<br>
                            <span style='font-size: 9px; font-weight: normal; opacity: 0.8;'>[" . adq_h($node['tipo']) . "]</span>
                            $actor_html
                            $tiempo_nodo
                        </div>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-md-12" style="margin-top: 20px; margin-bottom: 20px; clear: both; float: left;">
        <div class="adq-detail-card">
            <h5 class="adq-section-header"><i class="bi bi-journal-text"></i> Historial de Movimientos y Comentarios</h5>
            <div class="adq-timeline">
                <?php if (empty($historial)) { ?>
                    <div class="text-center text-muted py-3 small">No se registran movimientos en el workflow todav&iacute;a.</div>
                <?php } else {
                    foreach ($historial as $h) {
                        $actor = $h['Prs_Nom'] ? trim($h['Prs_Nom'] . ' ' . $h['Prs_Ape']) : ($h['Dep_Des'] ?: 'Sistema');
                        $actionBadge = '';
                        $itemClass = '';
                        if ($h['Isn_Acc'] === 'CREAR') {
                            $actionBadge = '<span class="badge bg-secondary" style="background-color: #64748b !important; color: #ffffff !important;">Inici&oacute; Pedido</span>';
                            $itemClass = 'active';
                        } elseif ($h['Isn_Acc'] === 'APROBAR') {
                            $actionBadge = '<span class="badge bg-success" style="background-color: #10b981 !important; color: #ffffff !important;">Aprob&oacute;</span>';
                            $itemClass = 'success';
                        } elseif ($h['Isn_Acc'] === 'OBSERVAR') {
                            $actionBadge = '<span class="badge bg-warning text-dark" style="background-color: #f59e0b !important; color: #1e293b !important;">Observ&oacute;</span>';
                            $itemClass = 'warning';
                        } elseif ($h['Isn_Acc'] === 'DEVOLVER') {
                            $actionBadge = '<span class="badge bg-secondary" style="background-color: #4b5563 !important; color: #ffffff !important;">Devolvi&oacute;</span>';
                            $itemClass = 'active';
                        } elseif ($h['Isn_Acc'] === 'RECHAZAR') {
                            $actionBadge = '<span class="badge bg-danger" style="background-color: #ef4444 !important; color: #ffffff !important;">Rechaz&oacute;</span>';
                            $itemClass = 'danger';
                        }
                        ?>
                        <div class="adq-timeline-item <?php echo $itemClass; ?>">
                            <div class="adq-timeline-content">
                                <div class="adq-timeline-header">
                                    <span class="adq-timeline-title"><?php echo $actionBadge; ?> en etapa: <strong><?php echo adq_h($h['Nod_Nom']); ?></strong></span>
                                    <span class="adq-timeline-date"><i class="bi bi-clock"></i> <?php echo date('Y-m-d H:i', strtotime($h['Isn_Fec'])); ?></span>
                                </div>
                                <div class="adq-timeline-body">
                                    Por: <span class="text-primary fw-bold"><?php echo adq_h($actor); ?></span>
                                    <?php if ($h['Isn_Com']) { ?>
                                        <div class="adq-timeline-comment">"<?php echo adq_h($h['Isn_Com']); ?>"</div>
                                    <?php } ?>
                                    <?php if ($h['Isn_Adj']) { ?>
                                        <div style="margin-top: 6px;">
                                            <a href="../../DATA/<?php echo adq_h($h['Isn_Adj']); ?>" target="_blank" class="btn btn-xs btn-default" style="font-size: 10px; padding: 1px 6px;"><i class="bi bi-paperclip"></i> Ver Adjunto</a>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                <?php }
                } ?>
            </div>
        </div>
    </div>
</div>
<?php exit; ?>
