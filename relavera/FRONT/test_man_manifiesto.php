<?php
$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(__DIR__));
$_SERVER['REQUEST_URI'] = '/relavera/FRONT/man_alt_manifiesto.php';
$_SESSION['Ses_Usu_Cod'] = 1;
$_SESSION['Ses_Emp_Cod'] = 463;
$_SESSION['Ses_Dat_Dis'] = 'servicios';
$_SESSION['Ses_Suc_Cod'] = 1;

ob_start();
try {
    require_once __DIR__ . '/man_alt_manifiesto.php';
    $output = ob_get_clean();
    echo "SUCCESS: man_alt_manifiesto.php executed without fatal errors.\n";
    echo "Output length: " . strlen($output) . " bytes\n";
} catch (Throwable $e) {
    echo "ERROR: Fatal error encountered:\n";
    echo $e->getMessage() . "\n";
}
