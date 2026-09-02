<?php
// Disable error display to prevent HTML injection into JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/dashboard_json_helper.php';

// Set header
header('Content-Type: application/json');

try {
    $configFile = __DIR__ . '/dashboard_config.json';
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'load') {
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            if ($content === false) {
                throw new Exception("No se pudo leer el archivo de configuracion");
            }
            // Ensure content is valid JSON
            if (trim($content) === '') {
                echo '{}';
            } else {
                echo $content;
            }
        } else {
            echo '{}';
        }
    } elseif ($action === 'save') {
        $rawInput = file_get_contents('php://input');
        if (!$rawInput) {
            throw new Exception("No se recibieron datos");
        }
        
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON invalido");
        }
        
        if ($input) {
            // Check if directory is writable
            if (!is_writable(dirname($configFile))) {
                 // Try to check if file is writable if it exists
                 if (file_exists($configFile) && !is_writable($configFile)) {
                     throw new Exception("Permisos denegados: No se puede escribir en el archivo");
                 } elseif (!file_exists($configFile)) {
                     throw new Exception("Permisos denegados: No se puede crear el archivo en el directorio");
                 }
            }

            $jsonOut = dashboard_json_encode_save($input);
            if ($jsonOut !== false && strlen($jsonOut) > 0 && file_put_contents($configFile, $jsonOut)) {
                echo json_encode(array('success' => true));
            } else {
                 throw new Exception("Fallo al escribir el archivo de configuracion");
            }
        } else {
            echo json_encode(array('error' => 'Datos vacios'));
        }
    } else {
        echo json_encode(array('error' => 'Accion invalida'));
    }
} catch (Exception $e) {
    // Return 200 so the frontend can parse the JSON error message
    // http_response_code(200); // Not available in PHP 5.3
    header("HTTP/1.1 200 OK");
    echo json_encode(array('error' => $e->getMessage()));
}
?>