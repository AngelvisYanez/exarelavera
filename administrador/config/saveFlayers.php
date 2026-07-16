<?php
header('Content-Type: application/json');

$filePath = 'login_flayers.json';
$uploadDir = 'images/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Función manual para simular JSON_PRETTY_PRINT en PHP 5.3
function json_format($json) {
    $result = '';
    $level = 0;
    $in_quotes = false;
    $ends_line_level = NULL;
    $json_length = strlen($json);

    for ($i = 0; $i < $json_length; $i++) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if ($ends_line_level !== NULL) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if ($char === '"' && ($i === 0 || $json[$i-1] !== '\\')) {
            $in_quotes = !$in_quotes;
        } else if (!$in_quotes) {
            switch ($char) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;
                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;
                case ':':
                    $post = " ";
                    break;
                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        }
        if ($new_line_level !== NULL) {
            $result .= "\n" . str_repeat("    ", $new_line_level);
        }
        $result .= $char . $post;
    }
    return $result;
}

// 1. Leer configuración anterior
$oldFlayers = array();
if (file_exists($filePath)) {
    $jsonOld = file_get_contents($filePath);
    $oldFlayers = json_decode($jsonOld, true);
    if (!is_array($oldFlayers)) $oldFlayers = array();
}

$flayers = array();
if (isset($_POST['flayers_data'])) {
    $dataRaw = $_POST['flayers_data'];
    if (get_magic_quotes_gpc()) { $dataRaw = stripslashes($dataRaw); }
    $flayers = json_decode($dataRaw, true);
}
if (!is_array($flayers)) $flayers = array();

// 2. Procesar subidas
foreach ($flayers as $idx => &$flayer) {
    $fileKey = 'file_' . $idx;
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == UPLOAD_ERR_OK) {
        if (!empty($flayer['ruta_imagen'])) {
            $oldFile = str_replace('administrador/config/', '', $flayer['ruta_imagen']);
            if (file_exists($oldFile)) unlink($oldFile);
        }
        $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
        $newName = 'flayer_' . time() . '_' . $idx . '.' . $ext;
        $targetPath = $uploadDir . $newName;
        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
            $flayer['ruta_imagen'] = 'administrador/config/' . $targetPath;
        }
    }
}

// 3. Limpieza de archivos eliminados
foreach ($oldFlayers as $oldF) {
    if (!empty($oldF['ruta_imagen'])) {
        $found = false;
        foreach ($flayers as $newF) {
            if (isset($newF['ruta_imagen']) && $newF['ruta_imagen'] === $oldF['ruta_imagen']) {
                $found = true; break;
            }
        }
        if (!$found) {
            $fileToDelete = str_replace('administrador/config/', '', $oldF['ruta_imagen']);
            if (file_exists($fileToDelete)) unlink($fileToDelete);
        }
    }
}

// Ordenar
usort($flayers, function($a, $b) {
    $oa = isset($a['orden']) ? (int)$a['orden'] : 0;
    $ob = isset($b['orden']) ? (int)$b['orden'] : 0;
    return $oa - $ob;
});

// Guardar formateado manualmente
$jsonRaw = json_encode($flayers);
$jsonFormatted = json_format($jsonRaw);

if (file_put_contents($filePath, $jsonFormatted)) {
    echo json_encode(array('success' => true, 'message' => 'Configuración guardada correctamente.'));
} else {
    echo json_encode(array('success' => false, 'message' => 'Error al escribir el archivo.'));
}
?>
