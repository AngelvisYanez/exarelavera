<?php
error_reporting(0);
require_once __DIR__ . '/../classes/DataAPI.php';
require_once __DIR__ . '/../framework/Slim/Slim.php';

$body = json_decode(file_get_contents('php://input'), true);
$bdd = $body['Bdd'] ?? 'servicios';

try {
    $api = new DataAPI($bdd);
    $tables = $api->listTables();
    $result = [];
    foreach ($tables as $t) {
        if (!$t) continue;
        try {
            $cols = $api->query("DESCRIBE `$t`");
            $colNames = array_map(function($c) { return $c['Field']; }, $cols);
            $result[$t] = $colNames;
        } catch (Throwable $e) {
            $result[$t] = 'ERROR: ' . $e->getMessage();
        }
    }
    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
