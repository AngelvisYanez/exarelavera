<?php

/**
 * Utilidades compartidas: teléfono planta y formato Ecuador para UltraMsg (Relavera).
 * Usado por man_ant_1.0.php, man_adm_notificacion.php, etc.
 */

if (!function_exists('relavera_telefono_planta_fila')) {
    /**
     * Primer teléfono disponible para notificar a una planta (prioridad: Pla_Wat, Pep_Tel, persona admin).
     *
     * @param array $f Fila con columnas tipo case 1 plantas (sql_man_notificacion) o caso 36 (sql_man_ant)
     * @return string
     */
    function relavera_telefono_planta_fila($f)
    {
        $cols = array('Pep_Tel_Admin', 'Prs_Tel_Admin', 'Prs_Te2_Admin');
        foreach ($cols as $c) {
            if (isset($f[$c]) && trim((string) $f[$c]) !== '') {
                return trim((string) $f[$c]);
            }
        }
        return '';
    }
}

if (!function_exists('relavera_whatsapp_normalizar_numero_ec')) {
    /**
     * Normaliza a formato internacional esperado por UltraMsg (Ecuador +593, etc.).
     *
     * @param string $tel_raw Teléfono tal como viene de BD o formulario
     * @return string Vacío si no hay dígitos útiles
     */
    function relavera_whatsapp_normalizar_numero_ec($tel_raw)
    {
        $tel = preg_replace('/\D/', '', trim((string) $tel_raw));
        if ($tel === '') {
            return '';
        }
        if (strlen($tel) === 9) {
            $tel = '+593' . $tel;
        } elseif (strlen($tel) === 10 && substr($tel, 0, 1) === '0') {
            $tel = '+593' . substr($tel, 1);
        } elseif (isset($tel[0]) && $tel[0] !== '+') {
            $tel = '+' . $tel;
        }
        return $tel;
    }
}

if (!function_exists('relavera_enviar_whatsapp_notif')) {
    /**
     * Envía un mensaje de texto por WhatsApp (UltraMsg messages/chat).
     *
     * @param string $numero Destino (formato esperado por la API)
     * @param string $mensaje Texto del mensaje
     * @return bool
     */
    function relavera_enviar_whatsapp_notif($numero, $mensaje)
    {
        $numero = preg_replace('/\s+/', '', (string) $numero);
        if ($numero === '') {
            return false;
        }
        $params = array('token' => 'ao5aoi2f77trfaxc', 'to' => $numero, 'body' => $mensaje);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.ultramsg.com/instance164295/messages/chat',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) { return false; }
        $data = json_decode((string)$response, true);
        return (is_array($data) && isset($data['id'])) ? $data['id'] : null;
    }
}

if (!function_exists('relavera_enviar_whatsapp_imagen_notif')) {
    /**
     * Envía una imagen con leyenda (UltraMsg messages/image). Leyenda máx. 1024 caracteres en la API.
     *
     * @param string $numero Destino
     * @param string $imageBase64 Base64 (sin prefijo data:) o URL pública
     * @param string $caption Leyenda bajo la imagen
     * @return bool
     */
    function relavera_enviar_whatsapp_imagen_notif($numero, $imageBase64, $caption)
    {
        $numero = preg_replace('/\s+/', '', (string) $numero);
        if ($numero === '' || $imageBase64 === '') {
            return false;
        }
        $caption = (string) $caption;
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($caption, 'UTF-8') > 1024) {
                $caption = mb_substr($caption, 0, 1021, 'UTF-8') . '...';
            }
        } else {
            $caption = substr($caption, 0, 1024);
        }
        $params = array( 'token' => 'ao5aoi2f77trfaxc','to' => $numero, 'image' => $imageBase64, 'caption' => $caption, );
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.ultramsg.com/instance164295/messages/image',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) { return false;}
        $data = json_decode((string)$response, true);
        return (is_array($data) && isset($data['id'])) ? $data['id'] : null;
    }
}

if (!function_exists('relavera_enviar_whatsapp_documento_notif')) {
    /**
     * Envía un documento (PDF, etc.) por WhatsApp (UltraMsg messages/document).
     *
     * @param string $numero Destino
     * @param string $documentBase64 Base64 del archivo (sin prefijo data:)
     * @param string $filename Nombre visible del archivo (ej. certificado.pdf)
     * @param string $caption Leyenda opcional
     * @return bool|string
     */
    function relavera_enviar_whatsapp_documento_notif($numero, $documentBase64, $filename, $caption = '')
    {
        $numero = preg_replace('/\s+/', '', (string) $numero);
        if ($numero === '' || $documentBase64 === '') {
            return false;
        }
        $filename = trim((string) $filename);
        if ($filename === '') {
            $filename = 'documento.pdf';
        }
        $caption = (string) $caption;
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($caption, 'UTF-8') > 1024) {
                $caption = mb_substr($caption, 0, 1021, 'UTF-8') . '...';
            }
        } else {
            $caption = substr($caption, 0, 1024);
        }
        $params = array(
            'token' => 'ao5aoi2f77trfaxc',
            'to' => $numero,
            'filename' => $filename,
            'document' => (strpos($documentBase64, 'data:') === 0)
                ? $documentBase64
                : ('data:application/pdf;base64,' . $documentBase64),
            'caption' => $caption,
        );
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.ultramsg.com/instance164295/messages/document',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;
        }
        $data = json_decode((string) $response, true);
        return (is_array($data) && isset($data['id'])) ? $data['id'] : null;
    }
}
