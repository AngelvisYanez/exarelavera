<?php $c = $_SESSION['contribuyente'];
$defaults = array(
    'razon_social' => '', 'ruc' => '', 'tipo_persona' => 'natural',
    'regimen' => 'general', 'noveno_digito' => '', 'contador' => '',
    'anio' => date('Y'), 'tarifa_iva' => 15, 'sbu' => 460,
    'tasa_patronal' => 11.15, 'tasa_individual' => 9.45, 'tasa_ccc' => 1,
);
$c = array_merge($defaults, $c);
?>
<div class="cte-card">
    <h2 class="h4 text-primary mb-3">Paso 1 — Datos del contribuyente</h2>
    <form method="post" action="index.php?paso=1">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Razón social / Nombres completos *</label>
                <input type="text" name="razon_social" class="form-control" required
                       value="<?= cte_h($c['razon_social']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">RUC (13 dígitos) *</label>
                <input type="text" name="ruc" id="ruc" class="form-control" maxlength="13" required
                       pattern="\d{13}" value="<?= cte_h($c['ruc']) ?>">
                <div id="ruc_feedback" class="form-text"></div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipo</label>
                <select name="tipo_persona" class="form-select">
                    <option value="natural" <?= $c['tipo_persona'] === 'natural' ? 'selected' : '' ?>>Persona Natural</option>
                    <option value="sociedad" <?= $c['tipo_persona'] === 'sociedad' ? 'selected' : '' ?>>Sociedad</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Régimen</label>
                <select name="regimen" class="form-select">
                    <option value="general" <?= $c['regimen'] === 'general' ? 'selected' : '' ?>>General</option>
                    <option value="rimpe_emprendedor" <?= $c['regimen'] === 'rimpe_emprendedor' ? 'selected' : '' ?>>RIMPE Emprendedor</option>
                    <option value="rimpe_popular" <?= $c['regimen'] === 'rimpe_popular' ? 'selected' : '' ?>>RIMPE Negocio Popular</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">9no dígito RUC (vencimientos)</label>
                <input type="number" name="noveno_digito" id="noveno_digito" class="form-control" min="0" max="9"
                       value="<?= cte_h($c['noveno_digito'] !== '' ? $c['noveno_digito'] : '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contador responsable</label>
                <input type="text" name="contador" class="form-control" value="<?= cte_h($c['contador']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Período fiscal (año)</label>
                <select name="anio" class="form-select">
                    <?php foreach (array(2024, 2025, 2026, 2027) as $y): ?>
                        <option value="<?= $y ?>" <?= (int)$c['anio'] === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tarifa IVA vigente (%)</label>
                <input type="number" step="0.01" name="tarifa_iva" class="form-control" value="<?= cte_h($c['tarifa_iva']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">SBU vigente</label>
                <input type="number" step="0.01" name="sbu" class="form-control" value="<?= cte_h($c['sbu']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">IESS patronal (%)</label>
                <input type="number" step="0.01" name="tasa_patronal" class="form-control" value="<?= cte_h($c['tasa_patronal']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">IESS individual (%)</label>
                <input type="number" step="0.01" name="tasa_individual" class="form-control" value="<?= cte_h($c['tasa_individual']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">CCC (%)</label>
                <input type="number" step="0.01" name="tasa_ccc" class="form-control" value="<?= cte_h($c['tasa_ccc']) ?>">
            </div>
        </div>
        <?php cte_nav_pasos(1); ?>
    </form>
</div>
