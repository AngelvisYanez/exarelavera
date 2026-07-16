<?php
header('Content-Type: application/json');

// Ahora el archivo está en la misma carpeta que el JSON
$filePath = 'login_flayers.json';

if (!file_exists($filePath)) {
    echo json_encode(array());
    exit;
}

$jsonContent = file_get_contents($filePath);
if ($jsonContent === false) {
    echo json_encode(array('error' => 'No se pudo leer el archivo de configuración.'));
    exit;
}

$data = json_decode($jsonContent, true);
echo json_encode($data === null ? array() : $data);
?>
