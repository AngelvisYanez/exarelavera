<?php
// Disable display of raw errors to ensure clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

function dashboard_json_encode_save($data) {
    if (defined('JSON_PRETTY_PRINT') && defined('JSON_UNESCAPED_SLASHES') && defined('JSON_UNESCAPED_UNICODE')) {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    return json_encode($data);
}

try {
    $configFile = __DIR__ . '/dashboard_config.json';
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'load') {
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            if ($content === false || trim($content) === '') {
                echo '{}';
            } else {
                // Verify it's valid JSON
                $test = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo $content;
                } else {
                    echo '{}';
                }
            }
        } else {
            echo '{}';
        }
    } elseif ($action === 'save') {
        $rawInput = file_get_contents('php://input');
        if (!$rawInput || trim($rawInput) === '') {
            throw new Exception("No se recibieron datos para guardar");
        }
        
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON invalido: " . json_last_error_msg());
        }
        
        if (is_array($input)) {
            $dir = dirname($configFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            $jsonOut = dashboard_json_encode_save($input);
            if ($jsonOut !== false && strlen($jsonOut) > 0) {
                $written = file_put_contents($configFile, $jsonOut, LOCK_EX);
                if ($written !== false) {
                    echo json_encode(array('success' => true));
                } else {
                    throw new Exception("Fallo al escribir en dashboard_config.json (compruebe permisos)");
                }
            } else {
                throw new Exception("Error al serializar datos en formato JSON");
            }
        } else {
            echo json_encode(array('error' => 'Los datos deben ser un objeto o arreglo'));
        }
    } else {
        echo json_encode(array('error' => 'Accion invalida'));
    }
} catch (Exception $e) {
    http_response_code(200);
    echo json_encode(array('error' => $e->getMessage()));
}
