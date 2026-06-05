<?php
// Mock server variables required for CLI
$_SERVER['DOCUMENT_ROOT'] = __DIR__;
$_SERVER['REQUEST_URI'] = '/administrador/FRONT/dashboard_proyecto.php';

// Capture output to prevent flooding the terminal
ob_start();

try {
    require_once __DIR__ . '/administrador/FRONT/dashboard_proyecto.php';
    $output = ob_get_clean();
    
    // Check if the output contains expected HTML elements
    if (strpos($output, 'Dashboard - Proyectos') !== false && strpos($output, 'class="container"') !== false) {
        echo "SUCCESS: dashboard_proyecto.php executed and rendered correctly.\n";
        echo "Length of output: " . strlen($output) . " bytes\n";
    } else {
        echo "WARNING: Executed without fatal errors, but output did not match expectations.\n";
    }
} catch (Throwable $e) {
    echo "ERROR: Fatal error encountered during execution:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
