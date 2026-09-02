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

if (!function_exists('relavera_ultramsg_request')) {
    /**
     * Realiza peticiones POST a la API de UltraMsg con doble capa (cURL PHP + fallback curl.exe)
     * para asegurar compatibilidad TLS 1.2+ en entornos Windows / XAMPP.
     */
    function relavera_ultramsg_request($url, $params, $timeout = 60)
    {
        $jsonPayload = json_encode($params);

        // Intento 1: cURL PHP
        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if (!$err && !empty($response)) {
                $data = json_decode((string)$response, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        // Intento 2: Fallback a curl.exe de Windows
        $tmpFile = tempnam(sys_get_temp_dir(), 'um_');
        file_put_contents($tmpFile, $jsonPayload);
        $cmd = 'curl.exe -s -k -X POST ' . escapeshellarg($url) . ' -H "Content-Type: application/json" -d @' . escapeshellarg($tmpFile);
        $response = shell_exec($cmd);
        @unlink($tmpFile);

        if (!empty($response)) {
            $data = json_decode((string)$response, true);
            if (is_array($data)) {
                return $data;
            }
        }
        return false;
    }
}

if (!function_exists('relavera_whatsapp_sanitizar_texto')) {
    /**
     * Sanitiza caracteres especiales, entidades HTML y posibles fallos de codificación
     * para que el mensaje de WhatsApp se vea limpio, legible y sin caracteres extraños.
     */
    function relavera_whatsapp_sanitizar_texto($texto)
    {
        $texto = (string) $texto;
        if ($texto === '') {
            return '';
        }
        // 1. Decodificar entidades HTML (&quot; -> ", &amp; -> &, &ntilde; -> ñ, etc.)
        $texto = html_entity_decode($texto, ENT_QUOTES, 'UTF-8');

        // 2. Corregir posibles secuencias Mojibake / doble UTF-8
        $reemplazos = array(
            'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú',
            'Ã ' => 'Á', 'Ã‰' => 'É', 'Ã ' => 'Í', 'Ã“' => 'Ó', 'Ãš' => 'Ú',
            'Ã±' => 'ñ', 'Ã‘' => 'Ñ', 'Ã¼' => 'ü', 'Ãœ' => 'Ü',
            'â€“' => '–', 'â€”' => '—', 'â€œ' => '“', 'â€' => '”', 'â€˜' => '‘', 'â€™' => '’',
            'Â°' => '°', 'Â«' => '«', 'Â»' => '»'
        );
        $texto = strtr($texto, $reemplazos);

        // 3. Asegurar codificación UTF-8
        if (function_exists('mb_check_encoding') && !mb_check_encoding($texto, 'UTF-8')) {
            if (function_exists('mb_convert_encoding')) {
                $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
            } elseif (function_exists('utf8_encode')) {
                $texto = utf8_encode($texto);
            }
        }

        // 4. Limpiar espacios duros / no separables
        $texto = str_replace(array("\xC2\xA0", "\xA0"), ' ', $texto);

        return trim($texto);
    }
}

if (!function_exists('relavera_enviar_whatsapp_notif')) {
    /**
     * Envía un mensaje de texto por WhatsApp (UltraMsg messages/chat).
     *
     * @param string $numero Destino (formato esperado por la API)
     * @param string $mensaje Texto del mensaje
     * @return bool|int|string
     */
    function relavera_enviar_whatsapp_notif($numero, $mensaje)
    {
        $numero = preg_replace('/\s+/', '', (string) $numero);
        if ($numero === '') {
            return false;
        }
        $mensaje = relavera_whatsapp_sanitizar_texto($mensaje);
        $params = array('token' => 'ao5aoi2f77trfaxc', 'to' => $numero, 'body' => $mensaje);
        $data = relavera_ultramsg_request('https://api.ultramsg.com/instance164295/messages/chat', $params, 30);
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
     * @return bool|int|string
     */
    function relavera_enviar_whatsapp_imagen_notif($numero, $imageBase64, $caption)
    {
        $numero = preg_replace('/\s+/', '', (string) $numero);
        if ($numero === '' || $imageBase64 === '') {
            return false;
        }
        $caption = relavera_whatsapp_sanitizar_texto($caption);
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($caption, 'UTF-8') > 1024) {
                $caption = mb_substr($caption, 0, 1021, 'UTF-8') . '...';
            }
        } else {
            $caption = substr($caption, 0, 1024);
        }
        $params = array(
            'token'   => 'ao5aoi2f77trfaxc',
            'to'      => $numero,
            'image'   => $imageBase64,
            'caption' => $caption,
        );
        $data = relavera_ultramsg_request('https://api.ultramsg.com/instance164295/messages/image', $params, 90);
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
     * @return bool|int|string
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
        $caption = relavera_whatsapp_sanitizar_texto($caption);
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($caption, 'UTF-8') > 1024) {
                $caption = mb_substr($caption, 0, 1021, 'UTF-8') . '...';
            }
        } else {
            $caption = substr($caption, 0, 1024);
        }
        $params = array(
            'token'    => 'ao5aoi2f77trfaxc',
            'to'       => $numero,
            'filename' => $filename,
            'document' => (strpos($documentBase64, 'data:') === 0)
                ? $documentBase64
                : ('data:application/pdf;base64,' . $documentBase64),
            'caption'  => $caption,
        );
        $data = relavera_ultramsg_request('https://api.ultramsg.com/instance164295/messages/document', $params, 120);
        return (is_array($data) && isset($data['id'])) ? $data['id'] : null;
    }
}
