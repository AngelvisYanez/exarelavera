<?php
/**
 * Bloques HTML de firma / borrador para certificados B.07.01
 * @param bool $firmar Si el usuario eligió firmar (Cert_Firmar)
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
                    $qr_src = '';
                    if (class_exists('QRcode')) {
                        ob_start();
                        @QRcode::png($qr_sig_data, false, 0, 6, 2);
                        $qr_img_bin = ob_get_contents();
                        ob_end_clean();
                        if (!empty($qr_img_bin)) {
                            $qr_src = 'data:image/png;base64,' . base64_encode($qr_img_bin);
                        }
                    }
                    if ($qr_src === '') {
                        $qr_src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qr_sig_data);
                    }
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
