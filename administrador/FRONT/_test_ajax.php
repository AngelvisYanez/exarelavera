<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$_GET['Pla_Cod'] = '1';
$_GET['Pec_Cod'] = '1';
$_GET['ivaCobrado'] = 'true';
$_GET['page'] = '1';
$_GET['rows'] = '250';
$_GET['_search'] = 'false';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

// Capture the output
ob_start();
include __DIR__ . '/adm_param_inicial.php';
$output = ob_get_clean();

// Check if it's JSON
$decoded = json_decode($output, true);
if ($decoded !== null) {
    echo "VALID JSON!\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "NOT VALID JSON!\n";
    echo "Length: " . strlen($output) . "\n";
    if (strpos($output, '<!DOCTYPE') !== false || strpos($output, '<html') !== false) {
        echo "OUTPUT IS HTML!\n";
        echo substr($output, 0, 500) . "\n";
    } else {
        echo substr($output, 0, 1000) . "\n";
    }
}