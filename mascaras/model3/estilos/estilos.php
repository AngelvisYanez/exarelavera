<?php
/**
 * Alias:     Estilos Model3
 * Descripcion: UI ERP modular (panel, tabs, formularios, grillas maestro-detalle).
 *              Extraido de ban_est_labores_v2_ui.css + estilos de ban_alt_labores_v2.php
 *
 * Uso en un modulo (desde FRONT o similar):
 *   <?php require_once('../../mascaras/model1/estilos/jqgrid5.php'); ?>
 *   <?php require_once('../../mascaras/model3/estilos/estilos.php'); ?>
 *
 * HTML minimo:
 *   <div class="panel panel-main exa-ui-panel">
 *     <div class="panel-heading exa-header"><h3 class="panel-title">Titulo</h3></div>
 *     <div class="panel-body exa-body">...</div>
 *   </div>
 *
 * Pantalla completa (lista + jqGrid):
 *   class="exa-ui-fill-page" en el panel
 *   <div id="lista" class="row exa-ui-page-view"> ... <div class="exa-ui-grid-host"> (cabecera jqGrid azul global)
 *   Tras createGrid: exaUiFitJqGrid('#MiGrid', '#lista .exa-ui-grid-host');
 *   Subgrid: exaUiWrapSubgrid(subgridId, tableId, 'Titulo del detalle');
 *            y en loadComplete del hijo: exaUiFitSubgrid($('#'+tableId));
 *   Cambio de vista/pesta�a: exaUiAfterViewChange('#contenedor');
 *
 * Sin maestro-detalle (solo panel/tabs/formularios/grillas):
 *   <link href=".../exa-ui-core.css?v=..." /> en lugar de exa-ui.css
 */
$m3_ver = '20260520m3x';
$m3_base = '../../mascaras/model3/estilos/';
$m3_js = '../../mascaras/model3/js/';
$m3_bundle = isset($m3_ui_core_only) && $m3_ui_core_only ? 'exa-ui-core.css' : 'exa-ui.css';
$m3_load_grid_fit = !isset($m3_no_grid_fit) || !$m3_no_grid_fit;
?>
<link href="<?php echo $m3_base . $m3_bundle; ?>?v=<?php echo $m3_ver; ?>" rel="stylesheet" type="text/css" />
<?php if ($m3_load_grid_fit) { ?>
<script type="text/javascript" src="<?php echo $m3_js; ?>exa-ui-grid-fit.js?v=<?php echo $m3_ver; ?>"></script>
<?php } ?>
