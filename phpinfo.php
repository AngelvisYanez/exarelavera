<?php
// phpinfo() restringido solo a localhost para debugging
date_default_timezone_set("America/Bogota");

$allowedIps = ['127.0.0.1', '::1', 'localhost'];
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($clientIp, $allowedIps) && !in_array(gethostname(), $allowedIps)) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

phpinfo();
echo '<center><b>Fecha:</b> '.date("Y-m-d").'. Hora: <b>'.date("H:i:s").'</b></center>';
