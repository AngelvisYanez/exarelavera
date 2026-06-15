<?php
/**
 * EXA Adquisiciones - Dashboard Gerencial de Flujos
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);

// 1. Estadísticas de Resumen
$stats = $obBD_con1->getRowConsultaSql("
    SELECT 
        COUNT(CASE WHEN Sol_Est = 'E' THEN 1 END) as Activos,
        COUNT(CASE WHEN Sol_Est = 'A' THEN 1 END) as Aprobados,
        COUNT(CASE WHEN Sol_Est = 'R' THEN 1 END) as Rechazados,
        COUNT(CASE WHEN Sol_Est = 'O' THEN 1 END) as Observados,
        IFNULL(AVG(CASE WHEN Sol_Est = 'A' THEN TIMESTAMPDIFF(HOUR, Sol_Fec, Sol_Sys) END), 0) as Tiempo_Promedio
    FROM adq_solicitudes 
    WHERE Emp_Cod = $Ses_Emp_Cod;", $obBD_conexion);

// 2. Cuellos de Botella: Solicitudes por etapa activa actual
$cuellos = $obBD_con1->getArrayConsultaSql("
    SELECT n.Nod_Nom, d.Dep_Des, COUNT(i.Ins_Cod) as Total
    FROM wf_instancias i
    INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    LEFT JOIN departamen d ON d.Dep_Cod = n.Dep_Cod
    WHERE i.Ins_Est = 'P' AND n.Wfm_Cod IN (SELECT Wfm_Cod FROM wf_flujos_modelos WHERE Emp_Cod = $Ses_Emp_Cod)
    GROUP BY n.Nod_Nom, d.Dep_Des
    ORDER BY Total DESC;", $obBD_conexion);

// 3. Ranking de Departamentos por SLA de atención
$departamentos_ranking = $obBD_con1->getArrayConsultaSql("
    SELECT d.Dep_Des,
           COUNT(h.Isn_Cod) as Resoluciones,
           IFNULL(AVG(TIMESTAMPDIFF(HOUR, h.Isn_Fec, (SELECT MIN(h2.Isn_Fec) FROM wf_instancias_nodos h2 WHERE h2.Ins_Cod = h.Ins_Cod AND h2.Isn_Cod > h.Isn_Cod))), 0) as Tiempo_Atencion
    FROM wf_instancias_nodos h
    INNER JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
    INNER JOIN departamen d ON d.Dep_Cod = h.Dep_Cod
    WHERE h.Isn_Acc IN ('APROBAR', 'OBSERVAR', 'DEVOLVER') AND d.Emp_Cod = $Ses_Emp_Cod
    GROUP BY d.Dep_Des
    ORDER BY Tiempo_Atencion ASC;", $obBD_conexion);

// 4. Volúmenes Mensuales de Solicitudes
$volumenes = $obBD_con1->getArrayConsultaSql("
    SELECT DATE_FORMAT(Sol_Fec, '%Y-%m') as Mes, COUNT(Sol_Cod) as Total, SUM(Sol_Val_Est) as Monto
    FROM adq_solicitudes
    WHERE Emp_Cod = $Ses_Emp_Cod AND Sol_Fec >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY Mes
    ORDER BY Mes ASC;", $obBD_conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>EXA Dashboard Gerencial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body class="bg-light py-4">
    <div class="container bg-white p-4 rounded shadow-sm">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
            <h3 class="fw-bold m-0 text-primary"><i class="bi bi-graph-up-arrow"></i> Dashboard Gerencial</h3>
            <div class="d-flex gap-2">
                <a href="adq_tipos_requerimientos.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-tags"></i> Tipos</a>
                <a href="wf_builder.php" class="btn btn-sm btn-outline-dark"><i class="bi bi-gear"></i> Diseñador Flujos</a>
                <a href="adq_bandeja.php" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left"></i> Volver a Bandeja</a>
            </div>
        </div>

        <!-- Fila de Indicadores Resumen (KPIs) -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card p-3 border-primary bg-primary-subtle text-primary">
                    <span class="text-uppercase fw-semibold" style="font-size: 11px;">PROCESOS EN EJECUCIÓN</span>
                    <h2 class="fw-bold mb-0 mt-1"><?php echo $stats['Activos']; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-success bg-success-subtle text-success">
                    <span class="text-uppercase fw-semibold" style="font-size: 11px;">SOLICITUDES APROBADAS</span>
                    <h2 class="fw-bold mb-0 mt-1"><?php echo $stats['Aprobados']; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-warning bg-warning-subtle text-warning">
                    <span class="text-uppercase fw-semibold" style="font-size: 11px;">SOLICITUDES OBSERVADAS</span>
                    <h2 class="fw-bold mb-0 mt-1"><?php echo $stats['Observados']; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-danger bg-danger-subtle text-danger">
                    <span class="text-uppercase fw-semibold" style="font-size: 11px;">TIEMPO PROMEDIO CICLO</span>
                    <h2 class="fw-bold mb-0 mt-1"><?php echo number_format($stats['Tiempo_Promedio'], 1); ?> <span style="font-size: 14px;">Hrs</span></h2>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- 1. Cuellos de Botella -->
            <div class="col-md-6">
                <div class="card p-3 shadow-sm h-100">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="bi bi-exclamation-octagon text-danger"></i> Cuellos de Botella (Procesos en Espera)</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr class="text-center font-monospace">
                                    <th>Etapa del Workflow</th>
                                    <th>Departamento Responsable</th>
                                    <th style="width: 100px;">En Espera</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cuellos)) { ?>
                                    <tr class="text-center"><td colspan="3" class="text-muted py-3">No hay cuellos de botella identificados.</td></tr>
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
                <div class="card p-3 shadow-sm h-100">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="bi bi-speedometer2 text-success"></i> Eficiencia y SLA Departamental</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr class="text-center font-monospace">
                                    <th>Departamento</th>
                                    <th style="width: 120px;">Pasos Resueltos</th>
                                    <th style="width: 150px;">Tiempo Medio Aprob.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($departamentos_ranking)) { ?>
                                    <tr class="text-center"><td colspan="3" class="text-muted py-3">No se han registrado transacciones de workflow aprobadas aún.</td></tr>
                                <?php } else {
                                    foreach ($departamentos_ranking as $r) { ?>
                                        <tr class="text-center">
                                            <td class="text-start fw-bold"><?php echo $r['Dep_Des']; ?></td>
                                            <td><?php echo $r['Resoluciones']; ?></td>
                                            <td class="fw-bold font-monospace text-success"><?php echo number_format($r['Tiempo_Atencion'], 1); ?> Hrs</td>
                                        </tr>
                                <?php }
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. Evolución del Gasto -->
            <div class="col-12">
                <div class="card p-3 shadow-sm">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="bi bi-calendar-event text-primary"></i> Volúmenes de Gasto de los Últimos 6 Meses</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr class="font-monospace text-center">
                                    <th>Mes Calendario</th>
                                    <th>Total Requerimientos de Adquisición</th>
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
</body>
</html>
