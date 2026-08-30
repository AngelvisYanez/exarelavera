<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '9600');
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/TCPDF/MYPDF.php');

$obBD_conexion = new Class_Log_Conexion_Mani($Ses_Dat_Dis);
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
        $tmp_file = $img_path; 
    }
    
    $info = @getimagesize($tmp_file);
    if (!$info) return $tmp_file;
    
    $w = $info[0]; $h = $info[1]; $type = $info[2];
    
    if ($w < 1200 && $h < 1200 && $type == IMAGETYPE_JPEG) {
        return $tmp_file;
    }

    $max_w = 600;
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
    @imagejpeg($canvas, $out_file, 65);
    
    @imagedestroy($canvas);
    @imagedestroy($img_res);
    
    if ($tmp_file != $img_path && file_exists($tmp_file)) { @unlink($tmp_file); }
    
    return $out_file;
}

$temp_dir_imgs = sys_get_temp_dir() . '/v_pdf_' . uniqid();
@mkdir($temp_dir_imgs);
if (!is_dir($temp_dir_imgs)) {
    error_log("No se pudo crear directorio temporal: $temp_dir_imgs");
}
$imgs_temporales = array();

$Fec_Des = isset($_GET['Fec_Des']) ? $_GET['Fec_Des'] : '';
$Fec_Has = isset($_GET['Fec_Has']) ? $_GET['Fec_Has'] : '';

// Asegurar variables críticas de sesión (evitando dependencia de register_globals)
$cod_empresa_ses = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : (isset($Ses_Emp_Cod) ? $Ses_Emp_Cod : '');
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
        WHERE ma.Ama_Est = 'A' 
            AND ma.Ama_Img IS NOT NULL 
            AND ma.Ama_Img != ''
            AND ma.Ama_Img != 'NULL'
            AND ma.Ama_Fec BETWEEN '$Fec_Des' AND '$Fec_Has'
            AND c.Emp_Cod = $emp_cod_sql
        ORDER BY p.Prs_Nom, p.Prs_Ape, pl.Pla_Nom, ma.Ama_Fec";

$res = $obBD_con1->consulta($sql, $obBD_conexion->conexion);

// Comprobar si hay resultados sin cargar todo a RAM
if ($obBD_con1->num_rows($res) == 0) {
    die("No se encontraron vouchers en el rango de fechas seleccionado.");
}

$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('EXA Software');
$pdf->SetAuthor('EXA');
$pdf->SetTitle('Reporte de Vouchers');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(TRUE, 15);

$current_cli = null;
$current_pla = null;

$img_count = 0;

while ($v = $obBD_con1->fetch_assoc($res)) {
    $cli_name = trim($v['Prs_Nom'] . ' ' . $v['Prs_Ape']);
    $pla_name = $v['Pla_Nom'] ? $v['Pla_Nom'] : 'SIN PLANTA';
    
    // New page if client or plant changes
    if ($current_cli !== $v['Cli_Cod'] || $current_pla !== $v['Pla_Cod']) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, $cli_name, 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, $pla_name, 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, "Rango: $Fec_Des hasta $Fec_Has", 0, 1, 'C');
        $pdf->Ln(5);
        
        $current_cli = $v['Cli_Cod'];
        $current_pla = $v['Pla_Cod'];
        $img_count = 0;
    } elseif ($img_count % 2 == 0 && $img_count > 0) {
        $pdf->AddPage();
        $pdf->Ln(5);
    }

    $img_data = $v['Ama_Img'];
    $img_src = '';
    
    if (strpos($img_data, 'data:image') === 0) {
        $img_src = comprimirImagenVoucher($img_data, $temp_dir_imgs);
        if ($img_src) $imgs_temporales[] = $img_src;
    } else {
        if (file_exists($img_data)) {
            $img_src = comprimirImagenVoucher($img_data, $temp_dir_imgs);
            if ($img_src) $imgs_temporales[] = $img_src;
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
}

    // Enviar el PDF al navegador
    if (isset($_REQUEST['downloadToken'])) {
        setcookie('downloadToken_' . $_REQUEST['downloadToken'], 'true', time() + 300, '/');
    }
    $pdf->Output('reporte_vouchers.pdf', 'I');

foreach ($imgs_temporales as $tmp) { @unlink($tmp); }
@rmdir($temp_dir_imgs);
