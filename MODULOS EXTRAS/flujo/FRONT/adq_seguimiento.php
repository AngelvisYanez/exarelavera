<?php
/**
 * EXA Adquisiciones - Vista Detallada y Seguimiento de Requerimientos (Línea de Tiempo y SLA)
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dis_Dis ?: $Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dis_Dis ?: $Ses_Dat_Dis);

$sol_cod = intval($_GET['sol_cod']);
$usu_cod = $_SESSION['Ses_Usu_Cod'];

// 1. Obtener datos de la solicitud y del workflow
$sol = $obBD_con1->getRowConsultaSql("
    SELECT s.*, tr.Trq_Des, tr.Trq_Req_Fac, tr.Trq_Req_Cot, tr.Trq_Min_Cot, tr.Trq_Req_Pre, tr.Trq_Req_Adj, tr.Trq_Req_Pro,
           u.Usu_Ced as Usu_Nom, d.Dep_Des,
           IFNULL(p.Prs_Nom, '') as Sol_Nom, IFNULL(p.Prs_Ape, '') as Sol_Ape,
           i.Ins_Cod, i.Nod_Act, i.Ins_Est, i.Ins_Fec_Ini, i.Ins_Fec_Fin,
           n.Nod_Nom, n.Nod_Tip
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    INNER JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    LEFT JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod
    LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    WHERE s.Sol_Cod = $sol_cod;", $obBD_conexion);

if (empty($sol)) {
    echo "<div class='alert alert-danger m-2 small'>No se encontró el requerimiento solicitado.</div>";
    exit;
}

// --- REGLA DE SEGURIDAD DE VISUALIZACIÓN POR REGISTRO ---
// 1. Creador de la solicitud -> Permitido.
// 2. Participante del flujo (pertenece al departamento o perfil del nodo activo actual) -> Permitido.
// 3. Rol gerencial o administrador (Per_Cod 1 o 2) o Administrador de Sistema (Usu_Cod = 1) -> Permitido.
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
        
        if (($pertenece_depto && $es_usuario_asignado) || $pertenece_perfil) {
            $es_participante_turno = true;
        }
    }
}

if (!$es_creador && !$es_gerencial_admin && !$es_participante_turno) {
    echo "<div class='alert alert-danger m-2 small'><i class='bi bi-shield-slash'></i> Acceso denegado. No tiene permisos para visualizar el seguimiento de esta solicitud.</div>";
    exit;
}

// 2. Obtener ítems, cotizaciones e historial
$items = $obBD_con1->getArrayConsultaSql("SELECT id.*, pr.Pro_Nom FROM adq_solicitudes_det id LEFT JOIN producto pr ON pr.Pro_Cod = id.Pro_Cod WHERE id.Sol_Cod = $sol_cod ORDER BY id.Sde_Int;", $obBD_conexion);
$cotizaciones = $obBD_con1->getArrayConsultaSql("SELECT c.*, p.Prs_Nom, p.Prs_Ape, pr.Prv_Com FROM adq_solicitudes_cotizaciones c INNER JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod INNER JOIN persona p ON p.Prs_Cod = pr.Prs_Cod WHERE c.Sol_Cod = $sol_cod;", $obBD_conexion);
$historial = array();
if (!empty($sol['Ins_Cod'])) {
    $historial = $obBD_con1->getArrayConsultaSql("
        SELECT h.*, n.Nod_Nom, n.Nod_Tip, p.Prs_Nom, p.Prs_Ape, d.Dep_Des 
        FROM wf_instancias_nodos h 
        INNER JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod 
        LEFT JOIN usuarios u ON u.Usu_Cod = h.Usu_Cod 
        LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod 
        LEFT JOIN departamen d ON d.Dep_Cod = h.Dep_Cod 
        WHERE h.Ins_Cod = {$sol['Ins_Cod']} 
        ORDER BY h.Isn_Fec ASC;", $obBD_conexion);
}

// 3. Evaluar qué requisitos faltan para avanzar
$requisitos_faltantes = array();
if ($sol['Ins_Est'] === 'P') {
    if ($sol['Trq_Req_Fac'] == 1) {
        $compras_v = $wf_mgr->getComprasVinculadas($sol_cod);
        if (empty($compras_v)) {
            $requisitos_faltantes[] = array('req' => 'Factura de Compra', 'desc' => 'Se requiere vincular al menos una factura física de compra para cerrar el proceso.');
        }
    }
    if ($sol['Trq_Req_Cot'] == 1) {
        $min_cot = intval($sol['Trq_Min_Cot']) ?: 1;
        if (count($cotizaciones) < $min_cot) {
            $requisitos_faltantes[] = array('req' => 'Sustento de Cotizaciones', 'desc' => "Se requiere cargar un mínimo de $min_cot cotizaciones físicas. Actualmente posee " . count($cotizaciones) . ".");
        }
        $ganadora = false;
        foreach ($cotizaciones as $c) {
            if ($c['Cot_Sel'] == 1) $ganadora = true;
        }
        if (!$ganadora) {
            $requisitos_faltantes[] = array('req' => 'Cotización Seleccionada', 'desc' => 'Debe marcar cuál de las cotizaciones físicas cargadas es la ganadora/seleccionada.');
        }
    }
    if ($sol['Trq_Req_Pro'] == 1 && empty($sol['Prv_Sug'])) {
        $requisitos_faltantes[] = array('req' => 'Proveedor Sugerido', 'desc' => 'Debe ingresar un proveedor sugerido para la adquisición.');
    }
}
?>
<div class="row">
    <!-- Información de Requerimiento -->
    <div class="col-xs-12 col-md-5" style="margin-bottom: 12px;">
        <div class="adq-detail-card" style="min-height: 155px; padding: 8px 12px;">
            <h5 class="adq-section-header" style="color: #1e3a8a; border-bottom-color: #cbd5e1; margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-file-earmark-text"></i> Detalles del Requerimiento</h5>
            <table class="table table-condensed table-borderless small mb-0" style="font-size: 11px; margin-bottom: 0;">
                <tr>
                    <td class="fw-bold text-muted" style="width: 80px; padding: 2px 4px; border:none;">Num. Sol:</td>
                    <td class="fw-bold text-dark" style="padding: 2px 4px; border:none;"><?php echo $sol['Sol_Num']; ?></td>
                    <td class="fw-bold text-muted" style="width: 80px; padding: 2px 4px; border:none;">Fecha:</td>
                    <td style="padding: 2px 4px; border:none;"><?php echo date('Y-m-d H:i', strtotime($sol['Sol_Fec'])); ?></td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none;">Solicitante:</td>
                    <td style="padding: 2px 4px; border:none;"><?php echo $sol['Sol_Nom'] . ' ' . $sol['Sol_Ape']; ?> (<?php echo $sol['Usu_Nom']; ?>)</td>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none;">Depto:</td>
                    <td style="padding: 2px 4px; border:none;"><?php echo $sol['Dep_Des']; ?></td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none;">Tipo Req:</td>
                    <td class="fw-semibold text-primary" style="padding: 2px 4px; border:none;"><?php echo $sol['Trq_Des']; ?></td>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none;">Monto Est:</td>
                    <td class="fw-bold font-monospace text-success" style="padding: 2px 4px; border:none;">$ <?php echo number_format($sol['Sol_Val_Est'], 2); ?></td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none; vertical-align: top;">Justificación:</td>
                    <td colspan="3" class="text-muted" style="font-size: 10.5px; padding: 2px 4px; border:none; line-height: 1.2;"><?php echo $sol['Sol_Jus']; ?></td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted" style="padding: 2px 4px; border:none; vertical-align: top;">Descripción:</td>
                    <td colspan="3" class="text-muted" style="font-size: 10.5px; padding: 2px 4px; border:none; line-height: 1.2;"><?php echo $sol['Sol_Det']; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Requisitos Faltantes / Qué falta para avanzar -->
    <div class="col-xs-12 col-md-7" style="margin-bottom: 12px;">
        <div class="adq-detail-card" style="min-height: 155px; padding: 8px 12px;">
            <h5 class="adq-section-header" style="color: #dc3545; border-bottom-color: #fca5a5; margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-shield-exclamation"></i> ¿Qué falta para avanzar?</h5>
            <?php if (empty($requisitos_faltantes)) { ?>
                <div class="alert alert-success p-2 small mb-0" style="padding: 6px 10px; font-size: 11px; margin-bottom: 0;"><i class="bi bi-check-circle-fill"></i> ¡Todos los requisitos de este tipo de requerimiento están cubiertos! El flujo puede avanzar normalmente.</div>
            <?php } else { ?>
                <div style="max-height: 110px; overflow-y: auto;">
                    <?php foreach ($requisitos_faltantes as $rf) { ?>
                        <div style="padding: 4px 0; border-bottom: 1px solid #f1f5f9;">
                            <span class="badge bg-danger" style="font-size: 9px; padding: 2px 6px; background-color: #ef4444 !important; color: #fff !important; display: inline-block; margin-bottom: 2px;"><i class="bi bi-x-circle"></i> <?php echo $rf['req']; ?></span>
                            <span class="text-muted" style="font-size: 10.5px; margin-left: 5px;"><?php echo $rf['desc']; ?></span>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>

    <div style="clear: both;"></div>

    <!-- Línea de Tiempo Horizontal Interactiva -->
    <div class="col-xs-12 col-md-12" style="margin-top: 5px; margin-bottom: 10px; clear: both; float: left;">
        <div class="adq-detail-card" style="padding: 8px 12px;">
            <h5 class="adq-section-header" style="color: #475569; border-bottom-color: #cbd5e1; margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-clock-history"></i> Línea de Tiempo del Proceso (SLA)</h5>
            <div class="tracker-wrapper">
                <?php 
                if (!empty($sol['Ins_Cod'])) {
                    $flow_visual = $wf_mgr->getVisualFlowData($sol['Ins_Cod']);
                    foreach ($flow_visual['nodos'] as $idx => $node) {
                        if ($idx > 0) {
                            echo '<div class="tracker-arrow"><i class="bi bi-arrow-right-short"></i></div>';
                        }
                        
                        // Calcular tiempo transcurrido en el nodo si corresponde
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
                        
                        // Buscar fecha de salida (entrada del siguiente nodo en el historial)
                        if ($fecha_entrada) {
                            foreach ($historial as $h) {
                                if ($h['Isn_Fec'] > $fecha_entrada) {
                                    $fecha_salida = $h['Isn_Fec'];
                                    break;
                                }
                            }
                            if (!$fecha_salida && $node['color'] === 'blue') {
                                $fecha_salida = date('Y-m-d H:i:s'); // Sigue activo, usar hora actual
                            }
                            
                            if ($fecha_salida) {
                                $diff = strtotime($fecha_salida) - strtotime($fecha_entrada);
                                $horas = round($diff / 3600, 1);
                                $tiempo_nodo = "<br><span class='text-muted' style='font-size: 8px;'><i class='bi bi-hourglass-split'></i> $horas Hrs</span>";
                            }
                        }
                        
                        echo "
                        <div class='tracker-node color-{$node['color']}'>
                            <i class='bi bi-circle-fill'></i> {$node['nombre']}<br>
                            <span style='font-size: 9px; font-weight: normal; opacity: 0.8;'>[{$node['tipo']}]</span>
                            $tiempo_nodo
                        </div>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Historial de Comentarios y Movimientos -->
    <div class="col-xs-12 col-md-12" style="margin-top: 20px; margin-bottom: 20px; clear: both; float: left;">
        <div class="adq-detail-card">
            <h5 class="adq-section-header"><i class="bi bi-journal-text"></i> Historial de Movimientos y Comentarios</h5>
            <div class="adq-timeline">
                <?php if (empty($historial)) { ?>
                    <div class="text-center text-muted py-3 small">No se registran movimientos en el workflow todavía.</div>
                <?php } else {
                    foreach ($historial as $h) {
                        $actor = $h['Prs_Nom'] ? ($h['Prs_Nom'] . ' ' . $h['Prs_Ape']) : ($h['Dep_Des'] ?: 'Sistema');
                        $actionBadge = '';
                        $itemClass = '';
                        if ($h['Isn_Acc'] === 'CREAR') {
                            $actionBadge = '<span class="badge bg-secondary" style="background-color: #64748b !important; color: #ffffff !important;">Inició Pedido</span>';
                            $itemClass = 'active';
                        } elseif ($h['Isn_Acc'] === 'APROBAR') {
                            $actionBadge = '<span class="badge bg-success" style="background-color: #10b981 !important; color: #ffffff !important;">Aprobó</span>';
                            $itemClass = 'success';
                        } elseif ($h['Isn_Acc'] === 'OBSERVAR') {
                            $actionBadge = '<span class="badge bg-warning text-dark" style="background-color: #f59e0b !important; color: #1e293b !important;">Observó</span>';
                            $itemClass = 'warning';
                        } elseif ($h['Isn_Acc'] === 'DEVOLVER') {
                            $actionBadge = '<span class="badge bg-secondary" style="background-color: #4b5563 !important; color: #ffffff !important;">Devolvió</span>';
                            $itemClass = 'active';
                        } elseif ($h['Isn_Acc'] === 'RECHAZAR') {
                            $actionBadge = '<span class="badge bg-danger" style="background-color: #ef4444 !important; color: #ffffff !important;">Rechazó</span>';
                            $itemClass = 'danger';
                        }
                        ?>
                        <div class="adq-timeline-item <?php echo $itemClass; ?>">
                            <div class="adq-timeline-content">
                                <div class="adq-timeline-header">
                                    <span class="adq-timeline-title"><?php echo $actionBadge; ?> en etapa: <strong><?php echo $h['Nod_Nom']; ?></strong></span>
                                    <span class="adq-timeline-date"><i class="bi bi-clock"></i> <?php echo date('Y-m-d H:i', strtotime($h['Isn_Fec'])); ?></span>
                                </div>
                                <div class="adq-timeline-body">
                                    Por: <span class="text-primary fw-bold"><?php echo $actor; ?></span>
                                    <?php if ($h['Isn_Com']) { ?>
                                        <div class="adq-timeline-comment">"<?php echo $h['Isn_Com']; ?>"</div>
                                    <?php } ?>
                                    <?php if ($h['Isn_Adj']) { ?>
                                        <div style="margin-top: 6px;">
                                            <a href="../../DATA/<?php echo $h['Isn_Adj']; ?>" target="_blank" class="btn btn-xs btn-default" style="font-size: 10px; padding: 1px 6px;"><i class="bi bi-paperclip"></i> Ver Adjunto</a>
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
