<?php
/**
 * Bloques HTML de firma / borrador para certificados B.07.01
 */

if (!class_exists('QRcode')) {
    global $APP_REAL_PATH;
    $ruta_qr_lib = isset($APP_REAL_PATH) ? $APP_REAL_PATH . '/Librerias/phpqrcode/phpqrcode.php' : '';
    if ($ruta_qr_lib !== '' && file_exists($ruta_qr_lib)) {
        require_once($ruta_qr_lib);
    }
}

/**
 * Sufijo aleatorio: 1 letra mayuscula + 10 alfanumericos (letras en mayuscula).
 */
function man_cert_url_token_suffix() {
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $suffix = $letters[mt_rand(0, 25)];
    for ($i = 0; $i < 10; $i++) {
        $suffix .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $suffix;
}

/**
 * Codigo numerico + sufijo (ej. 181 + X + 10 chars = 181X7K2M9A4P1Q).
 */
function man_cert_obfuscate_code($numeric_code) {
    $numeric_code = (int)$numeric_code;
    if ($numeric_code <= 0) {
        return '';
    }
    return (string)$numeric_code . man_cert_url_token_suffix();
}

/**
 * Extrae el codigo numerico de un token codesales / codecompany.
 */
function man_cert_deobfuscate_code($token) {
    $token = strtoupper(trim((string)$token));
    if ($token === '') {
        return 0;
    }
    if (preg_match('/^(\d+)([A-Z][A-Z0-9]{10})$/', $token, $m)) {
        return (int)$m[1];
    }
    if (preg_match('/^\d+$/', $token)) {
        return (int)$token;
    }
    return 0;
}

/**
 * URL absoluta de verificacion publica del certificado por factura.
 */
function man_cert_verificacion_url($vet_cod, $emp_cod) {
    $vet_cod = (int)$vet_cod;
    $emp_cod = (int)$emp_cod;
    $script_dir = '';
    if (!empty($_SERVER['SCRIPT_NAME'])) {
        $script_dir = rtrim(dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'])), '/');
    }
    if ($script_dir === '') {
        $script_dir = '/relavera/FRONT';
    }
    $codesales = man_cert_obfuscate_code($vet_cod);
    $codecompany = man_cert_obfuscate_code($emp_cod);
    $rel = $script_dir . '/man_verf_certificado.php?codesales=' . rawurlencode($codesales)
        . '&codecompany=' . rawurlencode($codecompany);
    $httpsVar = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
    $scheme = ($httpsVar && strtolower($httpsVar) !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    return $scheme . '://' . $host . $rel;
}

/**
 * Imagen QR (data URI o API) para incrustar en HTML/PDF.
 */
function man_cert_qr_image_src($text, $api_size = 140) {
    $text = (string)$text;
    if ($text === '') {
        return '';
    }
    if (class_exists('QRcode')) {
        ob_start();
        @QRcode::png($text, false, 0, 6, 2);
        $qr_img_bin = ob_get_contents();
        ob_end_clean();
        if (!empty($qr_img_bin) && strlen($qr_img_bin) > 100) {
            return 'data:image/png;base64,' . base64_encode($qr_img_bin);
        }
    }
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . (int)$api_size . 'x' . (int)$api_size . '&data=' . urlencode($text);
}

/**
 * Bloque HTML: QR de verificacion al final del certificado.
 */
function man_cert_verificacion_qr_html($vet_cod, $emp_cod) {
    $url = man_cert_verificacion_url($vet_cod, $emp_cod);
    $qr_src = man_cert_qr_image_src($url, 140);
    if ($qr_src === '') {
        return '';
    }
    $qr_h = htmlspecialchars($qr_src, ENT_QUOTES, 'UTF-8');
    return "
        <div class='verf-qr-section'>
            <div class='verf-qr-box'>
                <img src=\"{$qr_h}\" class='verf-qr-img' alt='QR de verificacion'>
                <div class='verf-qr-caption'>QR de verificaci&oacute;n</div>
                <div class='verf-qr-hint'>Escanee para verificar los datos del certificado</div>
            </div>
        </div>";
}

/**
 * Dibuja QR de verificacion en TCPDF (centrado).
 */
function man_cert_verificacion_qr_tcpdf($pdf, $vet_cod, $emp_cod, $x = null, $y = null, $size_mm = 28) {
    $url = man_cert_verificacion_url($vet_cod, $emp_cod);
    if ($url === '') {
        return;
    }
    $page_w = $pdf->getPageWidth();
    $margin = $pdf->getMargins();
    $usable_w = $page_w - $margin['left'] - $margin['right'];
    $qr_x = ($x !== null) ? $x : ($margin['left'] + ($usable_w - $size_mm) / 2);
    if ($y !== null) {
        $qr_y = $y;
    } else {
        $qr_y = $pdf->GetY() + 6;
        if ($qr_y > 248) {
            $pdf->AddPage();
            $qr_y = 30;
        }
    }
    require_once(dirname(__FILE__) . '/../../Librerias/TCPDF/include/barcodes/qrcode.php');
    $qr = new QRcode($url, 'L');
    $barcode_array = $qr->getBarcodeArray();
    if (empty($barcode_array) || !isset($barcode_array['bcode'])) {
        return;
    }
    $num_cols = max(1, (int)$barcode_array['num_cols']);
    $module_size = $size_mm / $num_cols;
    $pdf->SetFillColor(0, 0, 0);
    foreach ($barcode_array['bcode'] as $r => $row) {
        foreach ($row as $c => $val) {
            if ($val) {
                $pdf->Rect($qr_x + ($c * $module_size), $qr_y + ($r * $module_size), $module_size, $module_size, 'F');
            }
        }
    }
    $txt_w = max($size_mm, 50);
    $txt_x = $qr_x;
    $pdf->SetXY($txt_x, $qr_y + $size_mm + 2);
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Cell($txt_w, 4, 'QR de verificacion', 0, 1, 'L');
    $pdf->SetX($txt_x);
    $pdf->SetFont('helvetica', '', 5);
    $pdf->MultiCell($txt_w, 3, 'Validar certificado en linea', 0, 'L');
}

/**
 * @param bool $firmar Si el usuario eligi� firmar (Cert_Firmar)
 * @return array{watermark:string,signature:string}
 */
function man_cert_firma_html_blocks($firmar, $Ses_Emp_Cod, $obBD_con1, $obBD_conexion, $fecha_ref = null) {
    global $APP_REAL_PATH;

    $fecha_ref = $fecha_ref ? $fecha_ref : date('Y-m-d');
    $emp_nom = isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : 'ECOPARKMINING S.A.';
    $watermark = '';
    $signature = '';

    if ($firmar) {
        $firma_aplicada = false;
        $sql_llave = "SELECT Lla_Rut, Lla_Cla, Lla_Cad FROM llave_elect WHERE Lla_Est = 'A' AND Emp_Cod = " . intval($Ses_Emp_Cod);
        $res_llave = $obBD_con1->consulta($sql_llave, $obBD_conexion->conexion);
        $llave = $obBD_con1->fetch_assoc($res_llave);

        if ($llave && !empty($llave['Lla_Rut'])) {
            $ruta_p12 = $APP_REAL_PATH . "/facturacion/FRONT/$Ses_Emp_Cod/" . $llave['Lla_Rut'];
            $password = $llave['Lla_Cla'];
            if (file_exists($ruta_p12)) {
                $p12_data = file_get_contents($ruta_p12);
                $certs = array();
                if (openssl_pkcs12_read($p12_data, $certs, $password)) {
                    $cert_info = openssl_x509_parse($certs['cert']);
                    $nombre_firmante = isset($cert_info['subject']['CN']) ? $cert_info['subject']['CN'] : 'Firmante Autorizado';
                    $entidad_cert = isset($cert_info['issuer']['O']) ? $cert_info['issuer']['O'] : 'Entidad Certificadora';
                    $qr_sig_data = "Firmado electr\u00f3nicamente por: $nombre_firmante\nFecha: $fecha_ref\nEntidad: $entidad_cert\nValidar en: www.firmadigital.gob.ec";
                    $qr_src = man_cert_qr_image_src($qr_sig_data, 150);
                    $signature = "
                        <div class='signature-box'>
                            <div class='signature-box-content'>
                                <img src='$qr_src' class='signature-qr' alt='QR firma'>
                                <div class='signature-details'>
                                    <span class='label'>Firmado electr&oacute;nicamente por:</span>
                                    <span class='name'>" . htmlspecialchars(strtoupper($nombre_firmante), ENT_QUOTES, 'UTF-8') . "</span>
                                    <span class='check'>Validar documento con FirmaEC</span>
                                </div>
                            </div>
                            <div class='signature-line-bottom'></div>
                            <div class='signature-company'>" . htmlspecialchars($emp_nom, ENT_QUOTES, 'UTF-8') . "</div>
                        </div>";
                    $firma_aplicada = true;
                }
            }
        }
        if (!$firma_aplicada) {
            $signature = "
                <div class='signature-box'>
                    <div style='height: 80px;'></div>
                    <div class='signature-line-bottom'></div>
                    <div class='signature-company'>" . htmlspecialchars($emp_nom, ENT_QUOTES, 'UTF-8') . "</div>
                </div>";
        }
    } else {
        $signature = "
            <div class='signature-box' style='opacity: 0.4;'>
                <div style='height: 80px; text-align: center; padding-top: 30px; font-weight: bold; color: #999;'>SIN FIRMA AUTORIZADA</div>
                <div class='signature-line-bottom'></div>
                <div class='signature-company'>" . htmlspecialchars($emp_nom, ENT_QUOTES, 'UTF-8') . "</div>
            </div>";
    }

    return array('watermark' => $watermark, 'signature' => $signature);
}
