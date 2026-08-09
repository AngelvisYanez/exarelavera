<?php
/**
 * Compatibilidad con PHPMailer vía Composer (>=6.5.0, sin CVEs conocidos).
 *
 * Reemplaza las copias manuales que han quedado desactualizadas:
 *   - Librerias/PHPMail/PHPMail.php (wrapper "PHPMail" + PHPMailer 5.x)
 *   - Librerias/PHPMail/class.phpmailer.php (PHPMailer 5.x sin namespace)
 *   - Librerias/PHPMailer/ (PHPMailer 6.0.3)
 *   - Librerias/PHPMailer_2023/
 *   - WS/libs/PHPMailer/ (PHPMailer 6.0.3)
 *
 * Carga vendor/autoload.php y registra alias/definiciones para el código
 * legacy que usa la API de PHPMailer 5.x sin namespace (PHPMailer, SMTP,
 * POP3, phpmailerException) y el wrapper PHPMail.
 */
require_once dirname(__FILE__) . '/../vendor/autoload.php';

if (!class_exists('PHPMailer', false)) {
    class_alias('PHPMailer\PHPMailer\PHPMailer', 'PHPMailer');
}

if (!class_exists('SMTP', false)) {
    class_alias('PHPMailer\PHPMailer\SMTP', 'SMTP');
}

if (!class_exists('POP3', false)) {
    class POP3 extends \PHPMailer\PHPMailer\POP3
    {
        public function Authorise($host, $port = false, $timeout = false, $username = '', $password = '', $debug_level = 0)
        {
            return parent::authorise($host, $port, $timeout, $username, $password, $debug_level);
        }
    }
}

if (!class_exists('phpmailerException', false)) {
    class phpmailerException extends \PHPMailer\PHPMailer\Exception
    {
        public function errorMessage()
        {
            return '<strong>' . htmlspecialchars($this->getMessage()) . "</strong><br />\n";
        }
    }
}

if (!class_exists('PHPMail', false)) {
    class PHPMail
    {
        public $mail = null;
        public $pop = null;

        public $SMTPDebug = 0;

        public $Host = "ofsercont.com";

        public $Port = 10;

        public $Username = "facturacion.electronica@ofsercont.com";

        public $Password = "p.123456";

        public $Msg = '';

        /**
         * Crea y envía el mensaje devolviendo verdadero o falso según el caso.
         *
         * @param array  $Destinatarios (Correo, Nombre)
         * @param string $Asunto
         * @param mixed  $Mensaje       puede estar en formato html o string
         * @param array  $Archivos      (path, name, encoding, type)
         * @param string $Comentario
         * @param array  $copias
         * @return bool
         */
        public function enviar($Destinatarios, $Asunto, $Mensaje, $Archivos, $Comentario, $copias = array())
        {
            try {
                $this->pop = new POP3();
                $this->pop->Authorise($this->Host, $this->Port, 30, $this->Username, $this->Password, 1);

                $this->mail = new PHPMailer(true);
                $this->mail->isSMTP();
                $this->mail->Host = $this->Host;
                $this->mail->setFrom($this->Username, $_SESSION['Ses_Emp_Nom']);
                $this->mail->Subject = $Asunto;
                $this->mail->AltBody = $Comentario;
                $this->mail->msgHTML($Mensaje);

                foreach ($Destinatarios as $row) {
                    $this->mail->addAddress($row['Correo'], $row['Nombre']);
                }

                foreach ($Archivos as $row) {
                    $this->mail->addAttachment($row['path'], $row['name'], $row['encoding'], $row['type']);
                }

                if (!$this->mail->send()) {
                    $this->Msg = "Mailer Error: " . $this->mail->ErrorInfo;
                    return false;
                }

                $this->Msg = "Message sent!";
                return true;
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                $this->Msg = "Mailer Error: " . $e->getMessage();
                echo $this->Msg;
            } catch (Exception $e) {
                $this->Msg = "Mailer Error: " . $e->getMessage();
                echo $this->Msg;
            }
        }
    }
}
