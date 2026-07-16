<?php
require_once CTE_ROOT . '/parsers/parser_sri.php';

$pendientes = (isset($_SESSION['declaraciones_pendientes']) ? $_SESSION['declaraciones_pendientes'] : array());
$decl = (isset($_SESSION['declaraciones']) ? $_SESSION['declaraciones'] : array());
?>
<div class="cte-card">
    <h2 class="h4 text-primary mb-3">Paso 2 — Declaraciones SRI (Form. 104 / IR)</h2>
    <p class="text-muted">Suba PDFs del SRI (comprobante simple o declaración completa). Los valores detectados pueden editarse antes de confirmar.</p>

    <form method="post" enctype="multipart/form-data" action="index.php?paso=2" class="mb-4">
        <input type="hidden" name="subpaso" value="upload">
        <div class="mb-3">
            <label class="form-label">Archivos PDF (múltiple)</label>
            <input type="file" name="pdfs_sri[]" class="form-control" accept=".pdf" multiple required>
        </div>
        <button type="submit" class="btn btn-primary cte-btn-primary">Analizar PDFs</button>
    </form>

    <?php if (!empty($pendientes)): ?>
        <form method="post" action="index.php?paso=2" data-confirm="¿Confirmar y guardar declaraciones?">
            <input type="hidden" name="subpaso" value="confirmar">
            <h5 class="mt-4">Revisión de datos detectados</h5>
            <?php foreach ($pendientes as $idx => $p): ?>
                <div class="border rounded p-3 mb-3 <?= !empty($p['error']) ? 'border-danger' : 'campo-auto' ?>">
                    <h6><?= cte_h(isset($p['archivo']) ? $p['archivo'] : 'Documento') ?> — <?= cte_h(isset($p['tipo_doc']) ? $p['tipo_doc'] : '') ?></h6>
                    <?php if (!empty($p['error'])): ?>
                        <p class="text-danger"><?= cte_h($p['error']) ?></p>
                    <?php else: ?>
                        <div class="row g-2">
                            <?php $campos = array('periodo_texto', 'mes', 'formulario', 'numero_serie', 'ruc', 'estado',
                                '411', '421', '422', '423', '424', '425', '426', '427', '428', '429', '403', '510', '529', '564', '601', '609', '617', '999', '483', '485',
                                'fecha_recaudacion', 'codigo_verificador', 'tipo_declaracion');
                            foreach ($campos as $campo):
                                $val = (isset($p[$campo]) ? $p[$campo] : '');
                                $cls = ($val !== '' && $val !== 0) ? 'campo-auto' : 'campo-manual';
                                ?>
                                <div class="col-md-3 <?= $cls ?>">
                                    <label class="form-label small"><?= cte_h(strtoupper($campo)) ?></label>
                                    <input type="text" class="form-control form-control-sm"
                                           name="pendiente[<?= $idx ?>][<?= cte_h($campo) ?>]"
                                           value="<?= cte_h($val) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-success">Confirmar y guardar en sesión</button>
        </form>
    <?php endif; ?>

    <?php if (!empty($decl)): ?>
        <h5 class="mt-4">Declaraciones guardadas</h5>
        <div class="table-responsive">
            <table class="table table-sm table-cte">
                <thead>
                    <tr>
                        <th>Mes</th><th>Form.</th><th>Serie</th><th>411</th><th>601</th><th>999</th><th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($m = 1; $m <= 12; $m++):
                        if (empty($decl[$m])) continue;
                        $d = $decl[$m]; ?>
                        <tr>
                            <td><?= $GLOBALS['CTE_MESES'][$m] ?></td>
                            <td>104</td>
                            <td><?= cte_h((isset($d['numero_serie']) ? $d['numero_serie'] : '')) ?></td>
                            <td class="text-end"><?= cte_format_money((isset($d['411']) ? $d['411'] : 0)) ?></td>
                            <td class="text-end"><?= cte_format_money((isset($d['601']) ? $d['601'] : 0)) ?></td>
                            <td class="text-end"><?= cte_format_money((isset($d['999']) ? $d['999'] : 0)) ?></td>
                            <td><?= cte_h((isset($d['estado']) ? $d['estado'] : '')) ?></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <form method="post" action="index.php?paso=2">
        <?php cte_nav_pasos(2); ?>
    </form>
</div>
