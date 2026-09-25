<?php
/**
 * Alias:     Estilos Model4
 * Descripcion: Fork visual de Model3 + Tailwind local (Librerias/tailwind).
 *              Misma API de clases (exa-ui-*) + componentes m4-*.
 *              NO modifica model3. NO usa CDN.
 *
 * Uso:
 *   <?php require_once('../../mascaras/model1/estilos/jqgrid5.php'); ?>
 *   <?php require_once('../../mascaras/model4/estilos/estilos.php'); ?>
 *
 * Recompilar Tailwind tras cambiar componentes:
 *   cd Librerias/tailwind && npm install && npm run build
 */
$m4_ver = '20260924resp1';
$m4_base = '../../mascaras/model4/estilos/';
$m4_js = '../../mascaras/model4/js/';
$m4_tw = '../../Librerias/tailwind/dist/';
$m4_bundle = isset($m4_ui_core_only) && $m4_ui_core_only ? 'exa-ui-core.css' : 'exa-ui.css';
$m4_load_grid_fit = !isset($m4_no_grid_fit) || !$m4_no_grid_fit;
$m4_load_components_js = !isset($m4_no_components_js) || !$m4_no_components_js;
?>
<link href="<?php echo $m4_tw; ?>tailwind.min.css?v=<?php echo $m4_ver; ?>" rel="stylesheet" type="text/css" />
<link href="<?php echo $m4_base . $m4_bundle; ?>?v=<?php echo $m4_ver; ?>" rel="stylesheet" type="text/css" />
<?php if ($m4_load_grid_fit) { ?>
<script type="text/javascript" src="<?php echo $m4_js; ?>exa-ui-grid-fit.js?v=<?php echo $m4_ver; ?>"></script>
<?php } ?>
<?php if ($m4_load_components_js) { ?>
<script type="text/javascript" src="<?php echo $m4_js; ?>exa-ui-components.js?v=<?php echo $m4_ver; ?>"></script>
<?php } ?>
