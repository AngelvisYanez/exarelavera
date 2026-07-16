<?php $meses = cte_todos_meses();
$tot = cte_totales_anuales_iva($meses);
$ir = cte_conciliacion_ir();
$semaforo = cte_semaforo_obligaciones();
$declCount = count(array_filter((isset($_SESSION['declaraciones']) ? $_SESSION['declaraciones'] : array())));
$pendientes = 12 - $declCount;

$iconoVentas = $tot['ventas'] > $tot['compras'] ? 'kpi-ok' : 'kpi-alert';
$iconoCompras = $tot['compras'] > 0 ? 'kpi-ok' : 'kpi-alert';
?>
<div class="cte-card">
    <h2 class="h4 text-primary mb-3">Paso 5 — Dashboard / Resumen</h2>

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-lg-3">
            <div class="kpi-card <?= $iconoVentas ?>">
                <p class="small text-muted mb-1">Total ventas acumuladas</p>
                <p class="kpi-value">$ <?= cte_format_money($tot['ventas']) ?></p>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="kpi-card <?= $iconoCompras ?>">
                <p class="small text-muted mb-1">Total compras acumuladas</p>
                <p class="kpi-value">$ <?= cte_format_money($tot['compras']) ?></p>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="kpi-card">
                <p class="small text-muted mb-1">IVA causado vs crédito trib.</p>
                <p class="kpi-value small"><?= cte_format_money($tot['iva_causado']) ?> / <?= cte_format_money($tot['credito_tributario']) ?></p>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="kpi-card">
                <p class="small text-muted mb-1">IVA pagado en el año</p>
                <p class="kpi-value">$ <?= cte_format_money($tot['iva_pagado']) ?></p>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="kpi-card <?= $pendientes > 0 ? 'kpi-alert' : 'kpi-ok' ?>">
                <p class="small text-muted mb-1">Declaraciones F104</p>
                <p class="kpi-value"><?= $declCount ?> / 12</p>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="kpi-card">
                <p class="small text-muted mb-1">Total nómina anual</p>
                <p class="kpi-value">$ <?= cte_format_money($tot['nomina']) ?></p>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="kpi-card">
                <p class="small text-muted mb-1">Total IESS pagado</p>
                <p class="kpi-value">$ <?= cte_format_money($tot['iess']) ?></p>
            </div>
        </div>
    </div>

    <h5>Semáforo de obligaciones</h5>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered">
            <thead class="table-cte"><tr><th>Mes</th><th>Obligación</th><th>Vencimiento</th><th>Estado</th></tr></thead>
            <tbody>
                <?php foreach ($semaforo as $s):
                    $cls = 'semaforo-' . $s['estado'];
                    $icos = array('pendiente' => '🔴', 'cumplido' => '🟢', 'tardio' => '🟠');
                    $ico = isset($icos[$s['estado']]) ? $icos[$s['estado']] : '';
                    ?>
                    <tr>
                        <td><?= cte_h($s['mes_label']) ?></td>
                        <td><?= cte_h($s['obligacion']) ?></td>
                        <td><?= cte_h($s['vencimiento']) ?></td>
                        <td class="<?= $cls ?>"><?= $ico ?> <?= strtoupper($s['estado']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h5>Tabla resumen mensual</h5>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-cte">
            <thead>
                <tr>
                    <th>Mes</th><th>Ventas</th><th>Compras</th><th>IVA Causado</th>
                    <th>Crédito Trib.</th><th>IVA a Pagar</th><th>Nómina</th><th>IESS</th><th>Saldo CT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($meses as $dm): ?>
                    <tr>
                        <td><?= cte_h($dm['mes_label']) ?></td>
                        <td class="text-end"><?= cte_format_money($dm['ventas']) ?></td>
                        <td class="text-end"><?= cte_format_money($dm['compras']) ?></td>
                        <td class="text-end"><?= cte_format_money($dm['iva_causado']) ?></td>
                        <td class="text-end"><?= cte_format_money($dm['credito_tributario']) ?></td>
                        <td class="text-end"><?= cte_format_money($dm['iva_a_pagar']) ?></td>
                        <td class="text-end col-nomina"><?= cte_format_money($dm['nomina']) ?></td>
                        <td class="text-end"><?= cte_format_money($dm['iess']) ?></td>
                        <td class="text-end"><?= cte_format_money($dm['617']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="fw-bold bg-light">
                    <td>TOTALES</td>
                    <td class="text-end"><?= cte_format_money($tot['ventas']) ?></td>
                    <td class="text-end"><?= cte_format_money($tot['compras']) ?></td>
                    <td class="text-end"><?= cte_format_money($tot['iva_causado']) ?></td>
                    <td class="text-end"><?= cte_format_money($tot['credito_tributario']) ?></td>
                    <td class="text-end"><?= cte_format_money($tot['iva_a_pagar']) ?></td>
                    <td class="text-end"><?= cte_format_money($tot['nomina']) ?></td>
                    <td class="text-end"><?= cte_format_money($tot['iess']) ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h5>Borrador I.R. (conciliación)</h5>
    <div class="row">
        <div class="col-md-6">
            <table class="table table-sm">
                <tr><td>Ingresos</td><td class="text-end"><?= cte_format_money($ir['ingresos']) ?></td></tr>
                <tr><td>(-) Gastos deducibles</td><td class="text-end"><?= cte_format_money($ir['gastos_deducibles']) ?></td></tr>
                <tr><td><strong>Utilidad</strong></td><td class="text-end"><strong><?= cte_format_money($ir['utilidad']) ?></strong></td></tr>
                <tr><td>(+) Gastos no deducibles</td><td class="text-end"><?= cte_format_money($ir['gastos_no_deducibles']) ?></td></tr>
                <tr><td><strong>Base imponible</strong></td><td class="text-end"><strong><?= cte_format_money($ir['base_imponible']) ?></strong></td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table table-sm">
                <tr><td>IR causado</td><td class="text-end"><?= cte_format_money($ir['ir_causado']) ?></td></tr>
                <tr><td>(-) Gastos personales</td><td class="text-end"><?= cte_format_money($ir['gastos_personales']) ?></td></tr>
                <tr><td>(-) Retenciones</td><td class="text-end"><?= cte_format_money($ir['retenciones']) ?></td></tr>
                <tr><td>(-) CT año anterior</td><td class="text-end"><?= cte_format_money($ir['credito_anterior']) ?></td></tr>
                <tr class="table-primary"><td><strong>IR a pagar</strong></td><td class="text-end"><strong><?= cte_format_money($ir['ir_a_pagar']) ?></strong></td></tr>
            </table>
        </div>
    </div>

    <form method="post" action="index.php?paso=5">
        <?php cte_nav_pasos(5); ?>
    </form>
</div>
