<?php
function cte_layout_header($pasoActual) {
    $flash = cte_flash();
    $c = (isset($_SESSION['contribuyente']) ? $_SESSION['contribuyente'] : array());
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control Tributario EC — EXA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilo.php">
</head>
<body class="cte-body">
<nav class="navbar navbar-dark cte-navbar">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <span class="cte-logo">exa</span>
            <span>Control Tributario Ecuador</span>
        </a>
        <div class="d-flex gap-2">
            <a href="../administrador/FRONT/home.php" class="btn btn-outline-light btn-sm">← Volver a EXA</a>
            <a href="index.php?accion=limpiar" class="btn btn-danger btn-sm" onclick="return confirm('¿Limpiar toda la sesión?');">Nueva declaración</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="cte-progress mb-4">
        <?php for ($i = 1; $i <= 6; $i++): ?>
            <a href="index.php?paso=<?= $i ?>" class="cte-step <?= $i === $pasoActual ? 'active' : ($i < $pasoActual ? 'done' : '') ?>">
                <span class="num"><?= $i ?></span>
                <?php $nombres_pasos = array('Contribuyente', 'SRI', 'IESS', 'Manual', 'Dashboard', 'Informe'); ?>
                <span class="lbl"><?= $nombres_pasos[$i - 1] ?></span>
            </a>
        <?php endfor; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= cte_h($flash['texto']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($c['razon_social'])): ?>
        <div class="cte-banner-info mb-3">
            <strong><?= cte_h($c['razon_social']) ?></strong> — RUC <?= cte_h((isset($c['ruc']) ? $c['ruc'] : '')) ?> — Año <?= cte_h((isset($c['anio']) ? $c['anio'] : '')) ?>
        </div>
    <?php endif; ?>
<?php
}

function cte_layout_footer($pasoActual) {
    ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/validaciones.js"></script>
</body>
</html>
    <?php
}

function cte_nav_pasos($paso, $puedeSiguiente = true) {
    $ant = max(1, $paso - 1);
    $sig = min(6, $paso + 1);
    ?>
    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
        <?php if ($paso > 1): ?>
            <a href="index.php?paso=<?= $ant ?>" class="btn btn-secondary">← Anterior</a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
        <?php if ($paso < 6 && $puedeSiguiente): ?>
            <button type="submit" name="accion" value="guardar" class="btn btn-primary cte-btn-primary">Guardar y Siguiente →</button>
        <?php elseif ($paso < 6): ?>
            <a href="index.php?paso=<?= $sig ?>" class="btn btn-primary cte-btn-primary">Siguiente →</a>
        <?php endif; ?>
    </div>
    <?php
}
