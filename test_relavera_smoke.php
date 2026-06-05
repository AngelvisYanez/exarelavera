<?php
// Mock server variables
$_SERVER['DOCUMENT_ROOT'] = __DIR__;
$_SERVER['REQUEST_URI'] = '/relavera/FRONT/dashboard_relavera.php';

// Mock session variables that might be needed by seguridad.php
$_SESSION['Ses_Usu_Cod'] = 1;
$_SESSION['Ses_Emp_Cod'] = 463; // Company from test_login
$_SESSION['Ses_Dat_Dis'] = 'servicios';
$_SESSION['Ses_Suc_Cod'] = 1;

ob_start();
try {
    // We include safety wrapper around the actual execution
    require_once __DIR__ . '/relavera/FRONT/dashboard_relavera.php';
    $output = ob_get_clean();
    echo "SUCCESS: dashboard_relavera.php executed without fatal errors.\n";
    echo "Output length: " . strlen($output) . " bytes\n";
    // echo substr($output, 0, 200) . "...\n";
} catch (Throwable $e) {
    echo "ERROR: Fatal error encountered:\n";
    echo $e->getMessage() . "\n";
}
