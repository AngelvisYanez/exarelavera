<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// DEBUG: Echo and exit to check for parse errors
// echo "Llegó al script"; exit;
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '3600');
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/TCPDF/MYPDF.php');

if (!isset($_POST['generarZipVouchers'])) {
    die("Acceso no autorizado.");
}

// Variables globales que deben estar en sesión o extraídas
$db_dis_ses = isset($Ses_Dat_Dis) ? $Ses_Dat_Dis : (isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : NULL);

if (!$db_dis_ses) {
    die("Error: No se pudo identificar la base de datos. Por favor, inicie sesión nuevamente.");
}

$obBD_conexion = new Class_Log_Conexion_Mani($db_dis_ses);
if (!$obBD_conexion->conexion) {
    die("Error de conexión a la base de datos.");
}
$obBD_con1 = new Class_Log_Datos_Mani;

/**
 * Comprime y redimensiona una imagen para ahorrar memoria en TCPDF
 */
function comprimirImagenVoucher($img_path, $temp_dir) {
    if (empty($img_path)) return '';
    $tmp_file = $temp_dir . '/img_' . uniqid() . '.jpg';
    if (strpos($img_path, 'data:image') === 0) {
        $parts = explode(',', $img_path);
        if (isset($parts[1])) {
            file_put_contents($tmp_file, base64_decode($parts[1]));
        } else { return ''; }
    } else {
        if (!file_exists($img_path)) return '';
        $tmp_file = $img_path; // Usar el original si no es base64 para ahorrar copia
    }
    
    $info = @getimagesize($tmp_file);
    if (!$info) return $tmp_file;
    
    $w = $info[0]; $h = $info[1]; $type = $info[2];
    
    // Si la imagen no es excesivamente grande, no la procesamos para no agotar la RAM (32MB es muy poco)
    if ($w < 1200 && $h < 1200 && $type == IMAGETYPE_JPEG) {
        return $tmp_file;
    }

    $max_w = 600; // Reducido para ahorrar RAM
    if ($w > $max_w) {
        $new_w = $max_w; $new_h = floor($h * ($max_w / $w));
    } else {
        $new_w = $w; $new_h = $h;
    }
    
    $img_res = null;
    switch ($type) {
        case IMAGETYPE_JPEG: $img_res = @imagecreatefromjpeg($tmp_file); break;
        case IMAGETYPE_PNG:  $img_res = @imagecreatefrompng($tmp_file);  break;
        case IMAGETYPE_GIF:  $img_res = @imagecreatefromgif($tmp_file);  break;
    }
    if (!$img_res) return $tmp_file;
    
    $canvas = imagecreatetruecolor($new_w, $new_h);
    @imagecopyresampled($canvas, $img_res, 0, 0, 0, 0, $new_w, $new_h, $w, $h);
    
    $out_file = $temp_dir . '/opt_' . uniqid() . '.jpg';
    @imagejpeg($canvas, $out_file, 65); // Calidad 65 para ser más ligeros
    
    @imagedestroy($canvas);
    @imagedestroy($img_res);
    
    // Si la imagen original era temporal, la borramos
    if ($tmp_file != $img_path && file_exists($tmp_file)) { @unlink($tmp_file); }
    
    return $out_file;
}

$Fec_Des = $_POST['Fec_Des'];
$Fec_Has = $_POST['Fec_Has'];

// LIMPIEZA PARA PHP 5.3 (Magic Quotes) y entornos que escapan el POST
$json_raw = isset($_POST['seleccionados']) ? $_POST['seleccionados'] : '';

// Evitar colisión con register_globals usando un nombre de variable interno diferente
$items_procesar = json_decode($json_raw, true);

// Si falla, probar con stripslashes (necesario si magic_quotes_gpc está ON)
if ($items_procesar === NULL || $items_procesar === false) {
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        $items_procesar = json_decode(stripslashes($json_raw), true);
    } else {
        // Intento desesperado: intentar stripslashes de todas formas si parece JSON escapado
        if (strpos($json_raw, '\"') !== false) {
             $items_procesar = json_decode(stripslashes($json_raw), true);
        }
    }
}

// Asegurar variables críticas de sesión (evitando dependencia de register_globals)
$cod_empresa_ses = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : (isset($Ses_Emp_Cod) ? $Ses_Emp_Cod : '');
$nom_empresa_ses = isset($Ses_Emp_Nom) ? $Ses_Emp_Nom : (isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : (isset($_POST['Ses_Emp_Nom']) ? $_POST['Ses_Emp_Nom'] : 'EMPRESA'));

if (empty($items_procesar) || !is_array($items_procesar)) {
    die("No se seleccionaron registros válidos. Datos recibidos: " . htmlspecialchars($json_raw));
}

// Crear directorio temporal de forma segura
$base_temp = rtrim(sys_get_temp_dir(), '/\\');
$temp_dir = $base_temp . DIRECTORY_SEPARATOR . 'vouchers_' . uniqid();
if (!file_exists($temp_dir)) {
    if (!@mkdir($temp_dir, 0777, true)) {
        die("Error: No se pudo crear el directorio temporal en: " . $temp_dir);
    }
}

$files_to_zip = array();

foreach ($items_procesar as $item_sel) {
    if (!isset($item_sel['cli'])) continue;
    
    $cli_cod_val = $item_sel['cli'];
    $pla_cod_val = isset($item_sel['pla']) ? $item_sel['pla'] : '';
    
    // Escapar para evitar SQL Injection (mínimo)
    $cli_cod_sql = (int)$cli_cod_val;
    $where_pla_sql = empty($pla_cod_val) ? "AND ma.Pla_Cod IS NULL" : "AND ma.Pla_Cod = " . (int)$pla_cod_val;
    $emp_cod_sql = "'" . addslashes($cod_empresa_ses) . "'";
    
    $sql = "SELECT 
                p.Prs_Nom, 
                p.Prs_Ape, 
                pl.Pla_Nom, 
                ma.Ama_Img, 
                ma.Ama_Fec, 
                ma.Ama_Doc,
                ma.Ama_Val,
                ma.Cli_Cod,
                ma.Pla_Cod
            FROM manifiesto_anticipo ma
            INNER JOIN cliente c ON ma.Cli_Cod = c.Cli_Cod
            INNER JOIN persona p ON c.Prs_Cod = p.Prs_Cod
            LEFT JOIN manifiesto_plantas pl ON ma.Pla_Cod = pl.Pla_Cod
            INNER JOIN tipos_pago tp ON ma.Pag_Cod = tp.Pag_Cod
            WHERE ma.Ama_Est = 'A' 
                AND ma.Cli_Cod = $cli_cod_sql 
                $where_pla_sql
                AND ma.Ama_Img IS NOT NULL 
                AND ma.Ama_Img != ''
                AND ma.Ama_Img != 'NULL'
                AND ma.Ama_Fec BETWEEN '$Fec_Des' AND '$Fec_Has'
                AND c.Emp_Cod = $emp_cod_sql
                AND (tp.Pag_Abr = 'TRF' OR tp.Pag_Abr = 'DEP')
            ORDER BY ma.Ama_Fec";

    $res = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    if (!$res) continue; 
    
    if ($v = $obBD_con1->fetch_assoc($res)) {
        $cli_name = trim($v['Prs_Nom'] . ' ' . $v['Prs_Ape']);
        $pla_name = $v['Pla_Nom'] ? $v['Pla_Nom'] : 'SIN_PLANTA';

        $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('EXA Software');
        $pdf->SetAuthor('EXA');
        $pdf->SetTitle('Reporte de Vouchers - ' . $cli_name);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(TRUE, 15);

        $img_count = 0;
        do {
            // Cada 2 imágenes agregamos una nueva página
            if ($img_count % 2 == 0) {
                $pdf->AddPage();
                
                if ($img_count == 0) {
                    $pdf->SetFont('helvetica', 'B', 14);
                    $pdf->Cell(0, 7, $nom_empresa_ses, 0, 1, 'C');
                    $pdf->SetFont('helvetica', 'B', 12);
                    $pdf->Cell(0, 7, 'Reporte de Voucher', 0, 1, 'C');
                    $pdf->Ln(2);
                    
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell(0, 6, "Cliente: " . $cli_name, 0, 1, 'C');
                    $pdf->Cell(0, 6, "Planta: " . $pla_name, 0, 1, 'C');
                    $pdf->SetFont('helvetica', '', 10);
                    $pdf->Cell(0, 6, "Rango: $Fec_Des hasta $Fec_Has", 0, 1, 'C');
                    $pdf->Ln(5);
                } else {
                    $pdf->Ln(5);
                }
            }

            $img_data = $v['Ama_Img'];
            $img_src = '';
            
            if (strpos($img_data, 'data:image') === 0) {
                $img_src = comprimirImagenVoucher($img_data, $temp_dir);
            } else {
                // Limpiar la ruta de barras invertidas si vienen de Windows local
                $img_data = str_replace('\\', '/', $img_data);
                
                // 1. Intentar ruta tal cual viene en BD
                if (file_exists($img_data)) {
                    $img_src = comprimirImagenVoucher($img_data, $temp_dir);
                } else {
                    // 2. Intentar buscarla de forma relativa (un nivel arriba desde FRONT/)
                    $img_relativa = '../' . ltrim($img_data, './');
                    if (file_exists($img_relativa)) {
                        $img_src = comprimirImagenVoucher($img_relativa, $temp_dir);
                    } else {
                        // 3. Intentar buscarla en la raíz del proyecto usando DOCUMENT_ROOT
                        $img_base = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($img_data, '/');
                        // Ajuste específico para subdirectorios como /exa/
                        if (!file_exists($img_base)) {
                            $img_base = $_SERVER['DOCUMENT_ROOT'] . '/exa/' . ltrim($img_data, '/');
                        }
                        
                        if (file_exists($img_base)) {
                            $img_src = comprimirImagenVoucher($img_base, $temp_dir);
                        }
                    }
                }
            }

            $img_html = '<span>No se pudo cargar la imagen</span>';
            if ($img_src !== '') {
                $img_info = @getimagesize($img_src);
                if ($img_info && $img_info[0] > 0 && $img_info[1] > 0) {
                    $orig_w = $img_info[0];
                    $orig_h = $img_info[1];
                    
                    // Reducido aún más para asegurar que entren 2 por página (Aprox 240px de alto máximo)
                    $max_w = 480; 
                    $max_h = 240; 
                    
                    $ratio = min($max_w / $orig_w, $max_h / $orig_h);
                    if ($ratio > 1) {
                        $ratio = 1; // No estirar si la imagen ya es pequeña
                    }
                    $new_w = round($orig_w * $ratio);
                    $new_h = round($orig_h * $ratio);
                    
                    $img_html = '<img src="' . $img_src . '" width="' . $new_w . '" height="' . $new_h . '">';
                } else {
                    $img_html = '<img src="' . $img_src . '" height="240">';
                }
            }

            $ama_val = isset($v['Ama_Val']) ? $v['Ama_Val'] : 0;
            $valor_formateado = number_format((float)$ama_val, 2, '.', ',');

            $html = '
            <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border: 1px solid #ccc; margin-bottom: 0px;" nobr="true">
                <tr style="background-color: #f2f2f2; font-weight: bold; text-align: center;">
                    <td width="33%">Fecha</td>
                    <td width="34%">Nº Doc</td>
                    <td width="33%">Valor</td>
                </tr>
                <tr style="text-align: center;">
                    <td width="33%">' . $v['Ama_Fec'] . '</td>
                    <td width="34%">' . $v['Ama_Doc'] . '</td>
                    <td width="33%">$ ' . $valor_formateado . '</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center;">' . $img_html . '</td>
                </tr>
            </table>';

            $pdf->writeHTML($html, true, false, true, false, '');
            
            $img_count++;
        } while ($v = $obBD_con1->fetch_assoc($res));

        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $cli_name . '_' . $pla_name) . '.pdf';
        $filepath = $temp_dir . '/' . $filename;
        $pdf->Output($filepath, 'F');
        $files_to_zip[] = $filepath;
        unset($pdf); // Liberar memoria del objeto PDF
    }
}

if (empty($files_to_zip)) {
    die("No se pudieron generar los PDFs.");
}

// Crear el ZIP
if (!class_exists('ZipArchive')) {
    die("El servidor no tiene habilitada la libreria ZipArchive.");
}
$zip_filename = 'vouchers_' . date('Ymd_His') . '.zip';
$zip_path = $temp_dir . DIRECTORY_SEPARATOR . $zip_filename;
$zip = new ZipArchive();

if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
    foreach ($files_to_zip as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();

    // Enviar el ZIP al navegador
    if (isset($_POST['downloadToken'])) {
        setcookie('downloadToken_' . $_POST['downloadToken'], 'true', time() + 300, '/');
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . filesize($zip_path));
    readfile($zip_path);

    // Limpiar archivos temporales
    foreach ($files_to_zip as $file) {
        unlink($file);
    }
    unlink($zip_path);
    rmdir($temp_dir);
} else {
    die("Error al crear el archivo ZIP.");
}
