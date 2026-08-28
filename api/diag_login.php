<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$_POST['user_name'] = '22600781';
$_POST['password'] = '123456';
$_POST['encryptor'] = md5('123456');
$_POST['Emp_Cod'] = 96;
$_POST['Suc_Cod'] = 99;
$_POST['ajax_check'] = '1';

ob_start();
try {
    require __DIR__ . '/../administrador/FRONT/adm_con_control_1.2.php';
    $out = ob_get_clean();
    echo "SUCCESS:\n" . $out;
} catch (Throwable $t) {
    ob_end_clean();
    echo "ERROR: " . $t->getMessage() . " at " . $t->getFile() . ":" . $t->getLine() . "\n" . $t->getTraceAsString();
}
