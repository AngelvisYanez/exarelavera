<?php

/** Token de la API de mensajería (mismo valor en chat e imagen). */
if (!defined('SEND_WHATSAPP_TOKEN')) {
    define('SEND_WHATSAPP_TOKEN', 'ao5aoi2f77trfaxc');
}

/** URL base sin barra final: host + instancia (ej. …/instance12345). Los endpoints se añaden abajo. */
if (!defined('SEND_WHATSAPP_API_BASE')) {
    define('SEND_WHATSAPP_API_BASE', 'https://api.ultramsg.com/instance164295');
}

if (!defined('SEND_WHATSAPP_URL_CHAT')) {
    define('SEND_WHATSAPP_URL_CHAT', SEND_WHATSAPP_API_BASE . '/messages/chat');
}

if (!defined('SEND_WHATSAPP_URL_IMAGE')) {
    define('SEND_WHATSAPP_URL_IMAGE', SEND_WHATSAPP_API_BASE . '/messages/image');
}

if (!function_exists('send_whatsapp_normalizar_numero_ec')) {

    function send_whatsapp_normalizar_numero_ec($tel_raw)
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


function send_whatsapp_resolver_destinos($numeros)
{
    if (!is_array($numeros)) {
        $numeros = ($numeros === null || $numeros === '') ? array() : array($numeros);
    }
    $numerosNorm = array();
    foreach ($numeros as $raw) {
        $n = send_whatsapp_normalizar_numero_ec($raw);
        if ($n === '') {
            continue;
        }
        $numerosNorm[$n] = $n;
    }
    return array_values($numerosNorm);
}


function send_whatsapp_api_respuesta_ok($response)
{
    if ($response === '' || $response === false) {
        return false;
    }
    $data = json_decode($response, true);
    if (!is_array($data)) {
        return false;
    }
    if (isset($data['error'])) {
        return false;
    }
    if (array_key_exists('sent', $data)) {
        $sent = $data['sent'];
        $id = $data['id'];
        if ($sent !== true && $sent !== 'true' && $sent !== 1 && $sent !== '1') {
            return false;
        }
    }
    return $id; //Retorna el id del mensaje
}

function enviarNotificacionWhatsapp($mensaje, $numeros)
{
    $mensaje = isset($mensaje) ? trim((string) $mensaje) : '';
    if ($mensaje === '') {
        return false;
    }
    $numeros = send_whatsapp_resolver_destinos($numeros);
    if (count($numeros) === 0) {
        return false;
    }
    $resultados = array();
    foreach ($numeros as $numero) {
        $id = send_whatsapp_text($numero, $mensaje);
        $resultados[$numero] = $id;
    }
    return count($resultados) === 0 ? null : $resultados;
}

function enviarImagenWhatsapp($imagen, $numeros, $caption = '')
{
    $imagen = isset($imagen) ? trim((string) $imagen) : '';
    if ($imagen === '') {
        return false;
    }
    $caption = isset($caption) ? trim((string) $caption) : '';
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($caption, 'UTF-8') > 1024) {
            $caption = mb_substr($caption, 0, 1021, 'UTF-8') . '...';
        }
    } elseif (strlen($caption) > 1024) {
        $caption = substr($caption, 0, 1021) . '...';
    }
    $numeros = send_whatsapp_resolver_destinos($numeros);
    if (count($numeros) === 0) {
        return false;
    }
    $resultados = array();
    foreach ($numeros as $numero) {
        $resultados[$numero] = send_whatsapp_image($numero, $imagen, $caption);
    }
    return !in_array(false, $resultados, true);
}


function send_whatsapp_text($numero, $mensaje)
{
    $params = array('token' => SEND_WHATSAPP_TOKEN, 'to' => $numero, 'body' => $mensaje);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => SEND_WHATSAPP_URL_CHAT,
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
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err !== '') {
        return false;
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        return false;
    }
    return send_whatsapp_api_respuesta_ok($response);
}


function send_whatsapp_image($numero, $imagen, $caption)
{
    $params = array(
        'token' => SEND_WHATSAPP_TOKEN,
        'to' => $numero,
        'image' => $imagen,
        'caption' => $caption,
    );
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => SEND_WHATSAPP_URL_IMAGE,
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
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err !== '') {
        return false;
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        return false;
    }
    return send_whatsapp_api_respuesta_ok($response);
}
