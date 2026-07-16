<?php
require_once CTE_ROOT . '/parsers/parser_iess.php';

$empleados = (isset($_SESSION['iess']['empleados']) ? $_SESSION['iess']['empleados'] : array());
$pendientesIess = (isset($_SESSION['iess_pendientes']) ? $_SESSION['iess_pendientes'] : array());
?>
<div class="cte-card">
    <h2 class="h4 text-primary mb-3">Paso 3 — Planilla IESS</h2>
    <p class="text-muted">PDF &quot;Consulta Consolidada de Planillas IESS&quot;</p>

    <form method="post" enctype="multipart/form-data" action="index.php?paso=3" class="mb-4">
        <input type="hidden" name="subpaso" value="upload">
        <input type="file" name="pdf_iess" class="form-control" accept=".pdf" required>
        <button type="submit" class="btn btn-primary cte-btn-primary mt-2">Analizar planilla</button>
    </form>

    <?php if (!empty($pendientesIess)): ?>
        <form method="post" action="index.php?paso=3">
            <input type="hidden" name="subpaso" value="confirmar">
            <h5>Empleados detectados (<?= count($pendientesIess) ?>)</h5>
            <div class="table-responsive">
                <table class="table table-sm table-cte">
                    <thead>
                        <tr>
                            <th>#</th><th>Período</th><th>Cédula</th><th>Nombre</th><th>Sueldo</th>
                            <th>Días</th><th>Patronal</th><th>Individual</th><th>CCC</th><th>Líquido</th><th>Costo Emp.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendientesIess as $i => $e):
                            $calc = cte_calcular_fila_iess($e); ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <?php foreach (array('periodo', 'cedula', 'nombre', 'sueldo', 'dias', 'aporte_patronal', 'aporte_individual', 'valor_ccc') as $f): ?>
                                    <td><input class="form-control form-control-sm" name="emp[<?= $i ?>][<?= $f ?>]" value="<?= cte_h((isset($calc[$f]) ? $calc[$f] : '')) ?>"></td>
                                <?php endforeach; ?>
                                <td class="text-end"><?= cte_format_money($calc['sueldo_liquido']) ?></td>
                                <td class="text-end"><?= cte_format_money($calc['costo_empresa']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-success">Confirmar empleados</button>
        </form>
    <?php endif; ?>

    <?php if (!empty($empleados)): ?>
        <h5 class="mt-4">Resumen por empleado (año)</h5>
        <div class="table-responsive">
            <table class="table table-sm table-cte">
                <thead>
                    <tr>
                        <th>Cédula</th><th>Nombre</th><th>Meses</th><th>Total sueldo</th>
                        <th>Total IESS</th><th>13° prov.</th><th>14° prov.</th><th>Vacaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (cte_iess_resumen_empleados() as $r): ?>
                        <tr>
                            <td><?= cte_h($r['cedula']) ?></td>
                            <td><?= cte_h($r['nombre']) ?></td>
                            <td><?= $r['meses'] ?></td>
                            <td class="text-end"><?= cte_format_money($r['total_sueldo']) ?></td>
                            <td class="text-end"><?= cte_format_money($r['total_aportes']) ?></td>
                            <td class="text-end"><?= cte_format_money($r['decimo_tercero']) ?></td>
                            <td class="text-end"><?= cte_format_money($r['decimo_cuarto']) ?></td>
                            <td class="text-end"><?= cte_format_money($r['vacaciones']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <form method="post" action="index.php?paso=3">
        <?php cte_nav_pasos(3); ?>
    </form>
</div>
