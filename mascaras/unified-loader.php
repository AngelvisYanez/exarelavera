<?php
/**
 * Unified Mask Loader for ERP System
 * 
 * This file provides a centralized way to load the mask/style system
 * across all modules. It ensures consistent styling and makes it easy
 * to update the entire system by modifying this single file.
 * 
 * Usage in modules:
 *   require_once('../../mascaras/unified-loader.php');
 * 
 * Or with options:
 *   $mask_options = [
 *       'model' => 'model3',           // 'model1', 'model2', or 'model3'
 *       'core_only' => false,          // Use core CSS only (no master-detail)
 *       'no_grid_fit' => false,        // Disable grid fitting JS
 *       'include_legacy' => true       // Include legacy model1 styles
 *   ];
 *   require_once('../../mascaras/unified-loader.php');
 * 
 * Configuration Variables:
 *   $mask_model      - Which mask model to use ('model1', 'model2', 'model3')
 *   $mask_core_only  - Use core CSS only (default: false)
 *   $mask_no_grid_fit - Disable grid fitting JS (default: false)
 *   $mask_include_legacy - Include legacy model1 styles (default: true)
 */

// Default options
$mask_model = isset($mask_model) ? $mask_model : 'model3';
$mask_core_only = isset($mask_core_only) ? $mask_core_only : false;
$mask_no_grid_fit = isset($mask_no_grid_fit) ? $mask_no_grid_fit : false;
$mask_include_legacy = isset($mask_include_legacy) ? $mask_include_legacy : true;

// Calculate base path based on script location
$mask_base_path = dirname(__FILE__);

// Load the selected mask model
switch ($mask_model) {
    case 'model1':
        // Legacy model1 style system
        require_once($mask_base_path . '/model1/estilos/jqgrid5.php');
        break;
        
    case 'model2':
        // Transitional model2 (mostly used for login)
        require_once($mask_base_path . '/model2/estilos/estilos.php');
        break;
        
    case 'model3':
    default:
        // Modern model3 style system
        // First load model1 base styles if needed
        if ($mask_include_legacy) {
            require_once($mask_base_path . '/model1/estilos/jqgrid5.php');
        }
        
        // Set model3 options
        $m3_ui_core_only = $mask_core_only;
        $m3_no_grid_fit = $mask_no_grid_fit;
        
        // Load model3 styles
        require_once($mask_base_path . '/model3/estilos/estilos.php');
        break;
}

/**
 * Helper function to get CSS classes for common UI patterns
 * 
 * @param string $element Type of element ('button', 'alert', 'grid', etc.)
 * @param string $variant Variant type ('primary', 'success', 'danger', etc.)
 * @return string CSS class string
 */
function getMaskClass($element, $variant = 'default') {
    $classes = [];
    
    switch ($element) {
        case 'button':
            $classes[] = 'btn';
            if ($variant !== 'default') {
                $classes[] = "btn-$variant";
            }
            break;
            
        case 'alert':
            $classes[] = 'alert';
            if ($variant !== 'default') {
                $classes[] = "alert-$variant";
            }
            break;
            
        case 'panel':
            $classes[] = 'panel';
            $classes[] = 'panel-main';
            $classes[] = 'exa-ui-panel';
            break;
            
        case 'grid':
            $classes[] = 'exa-ui-grid-host';
            break;
            
        case 'cell':
            // Cell color classes
            $cell_classes = [
                'success' => 'cellGreen1',
                'success-light' => 'cellGreen2',
                'danger' => 'cellRed1',
                'danger-light' => 'cellRed2',
                'warning' => 'cellOrange1',
                'warning-light' => 'cellOrange2',
                'info' => 'cellBlue1',
                'info-light' => 'cellBlue2',
                'purple' => 'cellPurple1',
                'gray' => 'cellGray'
            ];
            if (isset($cell_classes[$variant])) {
                $classes[] = $cell_classes[$variant];
            }
            break;
    }
    
    return implode(' ', $classes);
}

/**
 * Helper function to get status row class
 * 
 * @param string $status Status type ('pagado', 'vencido', 'dirty')
 * @return string CSS class string
 */
function getStatusRowClass($status) {
    $status_classes = [
        'pagado' => 'exa-ui-row-pagado',
        'vencido' => 'exa-ui-row-vencido',
        'dirty' => 'exa-ui-row-dirty'
    ];
    
    return isset($status_classes[$status]) ? $status_classes[$status] : '';
}
?>