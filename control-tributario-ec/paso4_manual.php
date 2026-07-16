<?php $dm = $_SESSION['datos_manuales'];
$ir = (isset($dm['ir_anual']) ? $dm['ir_anual'] : array());
$anio = cte_anio_contribuyente();
$tabla = (isset($dm['tabla_progresiva']) ? $dm['tabla_progresiva'] : array());
if (empty($tabla)) {
    $tabla = cte_tabla_progresiva_default($anio);
}

$camposMes = array(
    'ventas_0' => 'Ventas 0%', 'nc_ventas_15' => 'NC 15%', 'nc_ventas_0' => 'NC 0%',
    'compras_0' => 'Compras 0%', 'activos_fijos' => 'Activos fijos', 'importaciones' => 'Importaciones',
    'ret_iva_20' => 'Ret. IVA 20%', 'ret_iva_30' => '30%', 'ret_iva_70' => '70%', 'ret_iva_100' => '100%',
    'ret_ir_303' => '303', 'ret_ir_303a' => '303A', 'ret_ir_304' => '304', 'ret_ir_307' => '307',
    'ret_ir_310' => '310', 'ret_ir_312' => '312', 'ret_ir_322' => '322', 'ret_ir_332' => '332',
    'ret_ir_343' => '343', 'ret_ir_344' => '344', 'ret_ir_346' => '346',
    'depreciaciones' => 'Depreciaciones', 'gastos_no_deducibles' => 'Gastos ND',
);
?>
<div class="cte-card">
    <h2 class="h4 text-primary mb-3">Paso 4 — Datos manuales complementarios</h2>
    <form method="post" action="index.php?paso=4">
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-mensual" type="button">Por mes</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ir" type="button">IR anual</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-progresiva" type="button">Tabla progresiva <?= $anio ?></button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-mensual">
                <div class="table-responsive" style="max-height:500px">
                    <table class="table table-sm table-bordered table-cte">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <th class="small"><?= $GLOBALS['CTE_MESES_CORTO'][$m] ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($camposMes as $key => $label): ?>
                                <tr>
                                    <td class="small fw-bold"><?= cte_h($label) ?></td>
                                    <?php for ($m = 1; $m <= 12; $m++):
                                        $v = (isset($dm['meses'][$m][$key]) ? $dm['meses'][$m][$key] : 0); ?>
                                        <td>
                                            <input type="number" step="0.01" class="form-control form-control-sm campo-manual"
                                                   name="meses[<?= $m ?>][<?= $key ?>]" value="<?= cte_h($v) ?>">
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-secondary">
                                <td>Form 103 presentado</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td><input type="checkbox" name="meses[<?= $m ?>][form_103_presentado]" value="1"
                                        <?= !empty($dm['meses'][$m]['form_103_presentado']) ? 'checked' : '' ?>></td>
                                <?php endfor; ?>
                            </tr>
                            <tr class="table-secondary">
                                <td>ATS presentado</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td><input type="checkbox" name="meses[<?= $m ?>][ats_presentado]" value="1"
                                        <?= !empty($dm['meses'][$m]['ats_presentado']) ? 'checked' : '' ?>></td>
                                <?php endfor; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-ir">
                <div class="row g-3">
                    <?php $irCampos = array(
                        'rendimientos_financieros' => 'Rendimientos financieros',
                        'otros_ingresos' => 'Otros ingresos',
                        'intereses_bancarios' => 'Intereses bancarios',
                        'otros_servicios' => 'Otros servicios',
                        'otros_gastos_deducibles' => 'Otros gastos deducibles',
                        'gastos_personales' => 'Gastos personales (rebaja IR)',
                        'credito_tributario_anterior' => 'Crédito tributario año anterior',
                    );
                    foreach ($irCampos as $k => $lbl): ?>
                        <div class="col-md-4">
                            <label class="form-label"><?= cte_h($lbl) ?></label>
                            <input type="number" step="0.01" name="ir_anual[<?= $k ?>]" class="form-control"
                                   value="<?= cte_h((isset($ir[$k]) ? $ir[$k] : 0)) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-progresiva">
                <p class="small text-muted">Tabla progresiva IR personas naturales — editable</p>
                <table class="table table-sm">
                    <thead><tr><th>Hasta</th><th>Imp. fb</th><th>Excedente desde</th><th>% excedente</th></tr></thead>
                    <tbody>
                        <?php foreach ($tabla as $i => $tr): ?>
                            <tr>
                                <td><input class="form-control form-control-sm" name="tabla[<?= $i ?>][hasta]" value="<?= cte_h($tr['hasta']) ?>"></td>
                                <td><input class="form-control form-control-sm" name="tabla[<?= $i ?>][imp]" value="<?= cte_h($tr['imp']) ?>"></td>
                                <td><input class="form-control form-control-sm" name="tabla[<?= $i ?>][exced]" value="<?= cte_h($tr['exced']) ?>"></td>
                                <td><input class="form-control form-control-sm" name="tabla[<?= $i ?>][pct]" value="<?= cte_h($tr['pct']) ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php cte_nav_pasos(4); ?>
    </form>
</div>
