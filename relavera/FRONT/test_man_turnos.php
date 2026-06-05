<?php
$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(__DIR__));
$_SERVER['REQUEST_URI'] = '/relavera/FRONT/man_adm_turnos.php';
$_SESSION['Ses_Usu_Cod'] = 1;
$_SESSION['Ses_Emp_Cod'] = 463;
$_SESSION['Ses_Dat_Dis'] = 'servicios';

ob_start();
try {
    require_once __DIR__ . '/man_adm_turnos.php';
    $output = ob_get_clean();
    echo "SUCCESS: man_adm_turnos.php executed without fatal errors.\n";
    echo "Output length: " . strlen($output) . " bytes\n";
} catch (Throwable $e) {
    echo "ERROR: Fatal error encountered:\n";
    echo $e->getMessage() . "\n";
}
