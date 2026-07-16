<?php $c = (isset($_SESSION['contribuyente']) ? $_SESSION['contribuyente'] : array());
if (empty($c['razon_social'])): ?>
    <div class="alert alert-warning">Complete el paso 1 antes de generar informes.</div>
<?php else: ?>
<div class="cte-card text-center">
    <h2 class="h4 text-primary mb-4">Paso 6 — Generar informe final</h2>
    <p class="mb-4">Descargue el control tributario en Excel (5 hojas) o PDF (resumen ejecutivo).</p>
    <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="index.php?paso=6&amp;descargar=excel" class="btn btn-success btn-lg">
            📥 Descargar Excel
        </a>
        <a href="index.php?paso=6&amp;descargar=pdf" class="btn btn-danger btn-lg">
            📄 Descargar PDF
        </a>
        <button type="button" class="btn btn-secondary btn-lg" onclick="window.print()">
            🖨️ Imprimir
        </button>
    </div>
    <hr class="my-4">
    <div class="text-start d-none d-print-block">
        <?php include __DIR__ . '/paso5_dashboard.php'; ?>
    </div>
    <div class="text-start no-print">
        <h5>Contenido del informe</h5>
        <ul class="text-muted">
            <li><strong>Excel:</strong> Control Tributario (matriz meses × columnas), Resumen I.R., IESS Planillas, Comprobantes SRI, Detalle F104</li>
            <li><strong>PDF:</strong> Portada, resumen mensual, semáforo, IR, IESS</li>
        </ul>
    </div>
</div>
<?php endif; ?>

<form method="post" action="index.php?paso=6">
    <div class="d-flex justify-content-between mt-3">
        <a href="index.php?paso=5" class="btn btn-secondary">← Anterior</a>
    </div>
</form>

<style>@media print { .cte-navbar, .cte-progress, .btn, nav { display:none !important; } }</style>
