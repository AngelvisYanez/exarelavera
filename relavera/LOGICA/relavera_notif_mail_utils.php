<?php

/**
 * Envío de correo para notificaciones Relavera (SMTP dedicado en relavera_notif_mail_config.php).
 */

if (!function_exists('relavera_notif_enviar_correo_notif')) {
    /**
     * @param string $para
     * @param string $nombreDestinatario
     * @param string $asunto
     * @param string $mensajePlano Mismo texto plano que se envía por WhatsApp
     * @param array|null $imagenAdj
     * @return bool
     */
    function relavera_notif_enviar_correo_notif($para, $nombreDestinatario, $asunto, $mensajePlano, $imagenAdj = null)
    {
        $asuntoLinea = trim(str_replace(array("\r", "\n"), ' ', $asunto));
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#222;">'
            . '<p style="margin:0 0 12px;"><strong>' . htmlspecialchars($asuntoLinea, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<div style="white-space:pre-wrap;">' . nl2br(htmlspecialchars($mensajePlano, ENT_QUOTES, 'UTF-8')) . '</div>'
            . '</div>';
        $nombre = $nombreDestinatario !== '' ? $nombreDestinatario : 'Destinatario';

        $cfgPath = __DIR__ . '/relavera_notif_mail_config.php';
        if (is_file($cfgPath)) {
            require_once $cfgPath;
        }
        if (defined('RELAVERA_NOTIF_SMTP_ENABLED') && RELAVERA_NOTIF_SMTP_ENABLED) {
            require_once __DIR__ . '/../../Librerias/PHPMailer/PHPMailer.php';
            require_once __DIR__ . '/../../Librerias/PHPMailer/SMTP.php';
            require_once __DIR__ . '/../../Librerias/PHPMailer/Exception.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = RELAVERA_NOTIF_SMTP_HOST;
            $mail->SMTPAuth = RELAVERA_NOTIF_SMTP_AUTH;
            $mail->Username = RELAVERA_NOTIF_SMTP_USERNAME;
            $mail->Password = RELAVERA_NOTIF_SMTP_PASSWORD;
            $mail->SMTPSecure = RELAVERA_NOTIF_SMTP_SECURE;
            $mail->Port = RELAVERA_NOTIF_SMTP_PORT;
            $mail->Timeout = RELAVERA_NOTIF_SMTP_TIMEOUT;

            $mail->From = RELAVERA_NOTIF_SMTP_FROM;
            $mail->Sender = RELAVERA_NOTIF_SMTP_SENDER;
            $mail->FromName = RELAVERA_NOTIF_SMTP_FROMNAME;

            $mail->addAddress(trim((string) $para), strtoupper($nombre));
            $mail->isHTML(true);
            $mail->Subject = ($asuntoLinea !== '') ? $asuntoLinea : (defined('RELAVERA_NOTIF_SMTP_SUBJECT_DEFAULT') ? RELAVERA_NOTIF_SMTP_SUBJECT_DEFAULT : 'Notificación Relavera');
            $mail->Body = $html;

            if (is_array($imagenAdj) && !empty($imagenAdj['base64'])) {
                $bin = base64_decode((string) $imagenAdj['base64'], true);
                if ($bin !== false && $bin !== '') {
                    $fname = !empty($imagenAdj['name']) ? (string) $imagenAdj['name'] : 'imagen';
                    $mime = !empty($imagenAdj['mime']) ? (string) $imagenAdj['mime'] : 'application/octet-stream';
                    $mail->addStringAttachment($bin, $fname, 'base64', $mime);
                }
            }

            try {
                $mail->send();
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        require_once __DIR__ . '/../../enviar_correo/ClaseEnviarCorreo.php';
        $mailer = new ClaseEnviarCorreo();
        $adjuntos = array();
        $tmpFile = null;
        if (is_array($imagenAdj) && !empty($imagenAdj['base64'])) {
            $bin = base64_decode((string) $imagenAdj['base64'], true);
            if ($bin !== false && $bin !== '') {
                $tmpFile = tempnam(sys_get_temp_dir(), 'rel_notif_');
                if ($tmpFile) {
                    @file_put_contents($tmpFile, $bin);
                    $adjuntos[] = array(
                        'ruta' => $tmpFile,
                        'nombre' => (!empty($imagenAdj['name']) ? (string) $imagenAdj['name'] : 'imagen'),
                    );
                }
            }
        }
        $ok = $mailer->enviarComprobante($para, $nombre, $html, 'Relavera', $adjuntos, $asuntoLinea);
        if ($tmpFile && is_file($tmpFile)) {
            @unlink($tmpFile);
        }
        return $ok;
    }
}
