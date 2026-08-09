<?php
/**
 * Clase para envío de correo usando PHPMailer con configuración exacontable.
 * Usa las mismas librerías y lógica que fac_log_electronica.php (líneas 158-199).
 *
 * Uso:
 *   require_once 'ClaseEnviarCorreo.php';
 *   $envio = new ClaseEnviarCorreo();
 *   $ok = $envio->enviarComprobante($correo, $destinatario, $bodyHtml, $nombreEmpresa, $adjuntos);
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/config_correo.php';
require_once __DIR__ . '/../Librerias/PHPMailer_compat.php';

class ClaseEnviarCorreo
{
    /** @var string Último error si el envío falla */
    public $ultimoError = '';

    /**
     * Envía un correo de comprobante electrónico usando SMTP exacontable.
     *
     * @param string       $correo        Destinatario(s), separados por coma
     * @param string       $destinatario  Nombre del destinatario (ej. razón social)
     * @param string       $bodyHtml      Cuerpo del correo en HTML
     * @param string       $fromName      Nombre del remitente (ej. nombre empresa)
     * @param array        $adjuntos      Opcional. Array de ['ruta' => path, 'nombre' => nombre visible]
     * @param string|null  $asunto        Opcional. Asunto (por defecto: Comprobante Electrónico)
     * @return bool true si se envió correctamente, false en caso contrario (ver $this->ultimoError)
     */
    public function enviarComprobante($correo, $destinatario, $bodyHtml, $fromName, $adjuntos = array(), $asunto = null)
    {
        $this->ultimoError = '';
        if (empty($correo) || strlen(trim($correo)) < 4) {
            $this->ultimoError = 'Correo destinatario no válido.';
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';

            // Protocolo SMTP (misma configuración que fac_log_electronica 158-199)
            $mail->IsSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = SMTP_AUTH;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->Timeout    = SMTP_TIMEOUT;
            $mail->SMTPKeepAlive = SMTP_KEEP_ALIVE;

            // Remitente
            $mail->From     = SMTP_FROM;
            $mail->Sender   = SMTP_SENDER;
            $mail->FromName = $fromName;

            // Destinatarios (varios separados por coma)
            $correos = explode(',', $correo);
            foreach ($correos as $c) {
                $mail->AddAddress(trim($c), strtoupper($destinatario));
            }

            // Formato HTML
            $mail->IsHTML(true);
            $mail->Subject = $asunto !== null ? $asunto : SMTP_SUBJECT_DEFAULT;
            $mail->Body    = $bodyHtml;

            // Adjuntos
            if (!empty($adjuntos) && is_array($adjuntos)) {
                foreach ($adjuntos as $adj) {
                    $ruta   = isset($adj['ruta']) ? $adj['ruta'] : $adj[0];
                    $nombre = isset($adj['nombre']) ? $adj['nombre'] : basename($ruta);
                    if (is_file($ruta)) {
                        $mail->AddAttachment($ruta, $nombre);
                    }
                }
            }

            $mail->Send();
            return true;

        } catch (PHPMailerException $e) {
            $this->ultimoError = $e->getMessage();
            return false;
        } catch (Exception $e) {
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }
}
