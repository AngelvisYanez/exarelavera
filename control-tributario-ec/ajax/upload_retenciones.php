<?php
error_reporting(0);
session_start();
header('Content-Type: application/json');

if (empty($_FILES['file'])) {
    echo json_encode(array('error' => 'No se recibio ningun archivo'));
    exit;
}

$file = $_FILES['file']['tmp_name'];
$fileName = strtolower($_FILES['file']['name']);
$ext = pathinfo($fileName, PATHINFO_EXTENSION);
if (!isset($_SESSION['ct_data'])) {
    $_SESSION['ct_data'] = array();
}

$resultados = isset($_SESSION['ct_data']['retenciones_rec']) ? $_SESSION['ct_data']['retenciones_rec'] : array();
$analisis = isset($_SESSION['ct_data']['ret_analisis']) ? $_SESSION['ct_data']['ret_analisis'] : array(
    'docs_por_mes' => array(),
    'agentes' => array(),
    'codigos' => array(),
    'total_docs' => 0
);

require_once __DIR__ . '/../parsers/parser_retenciones_xml.php';

if ($ext === 'zip') {
    $zip = new ZipArchive;
    if ($zip->open($file) === TRUE) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (strtolower(pathinfo($zip->getNameIndex($i), PATHINFO_EXTENSION)) == 'xml') {
                parsearRetencionXML($zip->getFromIndex($i), $resultados, $analisis);
            }
        }
        $zip->close();
    } else {
        echo json_encode(array('error' => 'No se pudo abrir el ZIP'));
        exit;
    }
} elseif ($ext === 'xml') {
    parsearRetencionXML(file_get_contents($file), $resultados, $analisis);
} else {
    echo json_encode(array('error' => 'Formato no soportado, sube ZIP o XML'));
    exit;
}

$_SESSION['ct_data']['retenciones_rec'] = $resultados;
$_SESSION['ct_data']['ret_analisis'] = $analisis;

// Obtener todas las columnas únicas encontradas
$columnas_ret = array();

foreach ($resultados as $mes => $datos) {
    foreach ($datos as $col => $info) {
        if (!in_array($col, $columnas_ret)) {
            $columnas_ret[] = $col;
        }
    }
}
sort($columnas_ret);

$debug_log = "FILES: " . print_r($_FILES, true) . "\n"
           . "Resultados parsing:\n" . print_r($resultados, true) . "\n"
           . "Ext: $ext\n"
           . "ZipArchive Status: " . (isset($zip) ? $zip->status : 'N/A') . "\n"
           . "Archivos procesados en ZIP: " . (isset($zip) ? $zip->numFiles : 1);
file_put_contents(__DIR__ . '/debug_retenciones.txt', $debug_log);

echo json_encode(array(
    'status' => 'ok', 
    'datos' => $resultados,
    'columnas_ret' => $columnas_ret,
    'msg' => 'Retenciones parseadas correctamente.'
));
