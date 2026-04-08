<?php
// Disable error display to prevent HTML injection into JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

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
        
        if ($input !== null && $input !== false) {
            // Verificar permisos de escritura
            $dir = dirname($configFile);
            $dirWritable = is_writable($dir);
            $fileExists  = file_exists($configFile);
            $fileWritable = $fileExists ? is_writable($configFile) : false;

            if (!$dirWritable && !$fileWritable) {
                if ($fileExists) {
                    throw new Exception("Permisos denegados: el archivo de configuracion no tiene permisos de escritura");
                } else {
                    throw new Exception("Permisos denegados: no se puede crear el archivo en " . $dir);
                }
            }

            $json = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new Exception("Error al codificar los datos: " . json_last_error_msg());
            }

            $result = file_put_contents($configFile, $json);
            if ($result === false) {
                throw new Exception("Fallo al escribir el archivo de configuracion");
            }
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('error' => 'Datos vacios o invalidos'));
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