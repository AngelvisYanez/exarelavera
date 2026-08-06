<?php
require_once('../LOGICA/seguridad.php');

// Validate parameters
if (!isset($_GET['emp_cod']) || !isset($_GET['file'])) {
    die("Faltan para&aacute;metros");
}

$emp_cod = intval($_GET['emp_cod']);
// Sanitize filename to prevent directory traversal
$file_name = basename($_GET['file']);

// Define path (relative to this script which is in administrador/FRONT)
$file_path = "../../facturacion/FRONT/" . $emp_cod . "/" . $file_name;

// Check if file exists
if (!file_exists($file_path)) {
    die("El archivo de firma no fue encontrado.");
}

// Get file extension
$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Content type
if ($ext == 'p12') {
    $content_type = 'application/x-pkcs12';
} else {
    $content_type = 'application/octet-stream';
}

// Force download
header('Content-Description: File Transfer');
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit;
?>
