<?php
/**
 * Mensajes de un chat vía API UltraMsg — GET /chats/messages
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Extrae texto legible cuando UltraMsg devuelve JSON con clave error.
 *
 * @param mixed $json Decodificado de la respuesta
 * @return string Vacío si no hay error reconocido
 */
function relavera_ultramsg_api_texto_error_respuesta($json)
{
    if (!is_array($json)) {
        return '';
    }
    if (isset($json['error'])) {
        return json_encode($json['error'], JSON_UNESCAPED_UNICODE);
    }
    return '';
}

/**
 * Listado de chats — GET /chats
 *
 * @return array{ok:bool,http_code:int,body:string,json:mixed,error:string}
 */
function relavera_ultramsg_api_get_chats()
{
    $cfg = __DIR__ . '/../LOGICA/log_ultramsg_config.php';
    if (is_file($cfg)) {
        require_once $cfg;
    }
    $instance = defined('RELAVERA_ULTRAMSG_INSTANCE_ID') ? trim((string) RELAVERA_ULTRAMSG_INSTANCE_ID) : '';
    $token = defined('RELAVERA_ULTRAMSG_TOKEN') ? trim((string) RELAVERA_ULTRAMSG_TOKEN) : '';
    if ($instance === '' || $token === '') {
        return array(
            'ok' => false,
            'http_code' => 0,
            'body' => '',
            'json' => null,
            'error' => 'Configure INSTANCE_ID y TOKEN en relavera/LOGICA/log_ultramsg_config.php',
        );
    }
    $params = array('token' => $token);
    $url = 'https://api.ultramsg.com/' . rawurlencode($instance) . '/chats?' . http_build_query($params);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array('content-type: application/x-www-form-urlencoded'),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err !== '') {
        return array(
            'ok' => false,
            'http_code' => $httpCode,
            'body' => is_string($response) ? $response : '',
            'json' => null,
            'error' => 'cURL: ' . $err,
        );
    }
    $json = null;
    if (is_string($response) && $response !== '') {
        $json = json_decode($response, true);
    }
    $msgErr = relavera_ultramsg_api_texto_error_respuesta($json);
    return array(
        'ok' => ($httpCode >= 200 && $httpCode < 300 && $msgErr === ''),
        'http_code' => $httpCode,
        'body' => is_string($response) ? $response : '',
        'json' => $json,
        'error' => $msgErr !== '' ? ('UltraMsg: ' . $msgErr) : '',
    );
}

/**
 * @param string $chatId Identificador JID del chat (opcional según instancia)
 * @param int $limit Cantidad máxima de mensajes
 * @return array{ok:bool,http_code:int,body:string,json:mixed,error:string}
 */
function relavera_ultramsg_api_get_chat_messages($chatId = '', $limit = 50)
{
    $cfg = __DIR__ . '/../LOGICA/log_ultramsg_config.php';
    if (is_file($cfg)) {
        require_once $cfg;
    }
    $instance = defined('RELAVERA_ULTRAMSG_INSTANCE_ID') ? trim((string) RELAVERA_ULTRAMSG_INSTANCE_ID) : '';
    $token = defined('RELAVERA_ULTRAMSG_TOKEN') ? trim((string) RELAVERA_ULTRAMSG_TOKEN) : '';
    if ($instance === '' || $token === '') {
        return array(
            'ok' => false,
            'http_code' => 0,
            'body' => '',
            'json' => null,
            'error' => 'Configure INSTANCE_ID y TOKEN en relavera/LOGICA/log_ultramsg_config.php',
        );
    }
    $chatId = trim((string) $chatId);
    $limit = (int) $limit;
    if ($limit < 1) {
        $limit = 50;
    }
    if ($limit > 1000) {
        $limit = 1000;
    }
    $params = array(
        'token' => $token,
        'limit' => (string) $limit,
    );
    // Algunas instancias exigen chatId, otras permiten omitirlo.
    if ($chatId !== '') {
        $params['chatId'] = $chatId;
    }
    $url = 'https://api.ultramsg.com/' . rawurlencode($instance) . '/chats/messages?' . http_build_query($params);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array('content-type: application/x-www-form-urlencoded'),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err !== '') {
        return array(
            'ok' => false,
            'http_code' => $httpCode,
            'body' => is_string($response) ? $response : '',
            'json' => null,
            'error' => 'cURL: ' . $err,
        );
    }
    $json = null;
    if (is_string($response) && $response !== '') {
        $json = json_decode($response, true);
    }
    $msgErr = relavera_ultramsg_api_texto_error_respuesta($json);
    return array(
        'ok' => ($httpCode >= 200 && $httpCode < 300 && $msgErr === ''),
        'http_code' => $httpCode,
        'body' => is_string($response) ? $response : '',
        'json' => $json,
        'error' => $msgErr !== '' ? ('UltraMsg: ' . $msgErr) : '',
    );
}

/**
 * GET /messages (listado por instancia con page, limit, status).
 *
 * @param int $page
 * @param int $limit
 * @param string $status
 * @return array{ok:bool,http_code:int,body:string,json:mixed,error:string}
 */
function relavera_ultramsg_api_get_messages($page = 1, $limit = 10, $status = '')
{
    $cfg = __DIR__ . '/../LOGICA/log_ultramsg_config.php';
    if (is_file($cfg)) {
        require_once $cfg;
    }
    $instance = defined('RELAVERA_ULTRAMSG_INSTANCE_ID') ? trim((string) RELAVERA_ULTRAMSG_INSTANCE_ID) : '';
    $token = defined('RELAVERA_ULTRAMSG_TOKEN') ? trim((string) RELAVERA_ULTRAMSG_TOKEN) : '';
    if ($instance === '' || $token === '') {
        return array(
            'ok' => false,
            'http_code' => 0,
            'body' => '',
            'json' => null,
            'error' => 'Configure INSTANCE_ID y TOKEN en relavera/LOGICA/log_ultramsg_config.php',
        );
    }

    $page = (int) $page;
    if ($page < 1) {
        $page = 1;
    }
    $limit = (int) $limit;
    if ($limit < 1) {
        $limit = 10;
    }
    if ($limit > 1000) {
        $limit = 1000;
    }
    $status = trim((string) $status);

    $params = array(
        'token' => $token,
        'page' => (string) $page,
        'limit' => (string) $limit,
    );
    if ($status !== '' && $status !== 'all' && $status !== 'todos') {
        $params['status'] = $status;
    }

    $url = 'https://api.ultramsg.com/' . rawurlencode($instance) . '/messages?' . http_build_query($params);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array('content-type: application/x-www-form-urlencoded'),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err !== '') {
        return array(
            'ok' => false,
            'http_code' => $httpCode,
            'body' => is_string($response) ? $response : '',
            'json' => null,
            'error' => 'cURL: ' . $err,
        );
    }

    $json = null;
    if (is_string($response) && $response !== '') {
        $json = json_decode($response, true);
    }
    $msgErr = relavera_ultramsg_api_texto_error_respuesta($json);
    return array(
        'ok' => ($httpCode >= 200 && $httpCode < 300 && $msgErr === ''),
        'http_code' => $httpCode,
        'body' => is_string($response) ? $response : '',
        'json' => $json,
        'error' => $msgErr !== '' ? ('UltraMsg: ' . $msgErr) : '',
    );
}

/**
 * @param mixed $json
 * @return int|null
 */
function relavera_ultramsg_messages_total_desde_respuesta($json)
{
    if (!is_array($json)) {
        return null;
    }
    $idx = 0;
    foreach ($json as $k => $_) {
        if ($k !== $idx) {
            break;
        }
        $idx++;
    }
    if ($idx > 0 && $idx === count($json)) {
        return null;
    }
    foreach (array('total', 'totalMessages', 'total_messages', 'total_count', 'count') as $k) {
        if (isset($json[$k]) && is_numeric($json[$k])) {
            return (int) $json[$k];
        }
    }
    return null;
}

/**
 * @return array{ok:bool,http_code:int,body:string,json:mixed,error:string}
 */
function relavera_ultramsg_api_get_messages_statistics()
{
    $cfg = __DIR__ . '/../LOGICA/log_ultramsg_config.php';
    if (is_file($cfg)) {
        require_once $cfg;
    }
    $instance = defined('RELAVERA_ULTRAMSG_INSTANCE_ID') ? trim((string) RELAVERA_ULTRAMSG_INSTANCE_ID) : '';
    $token = defined('RELAVERA_ULTRAMSG_TOKEN') ? trim((string) RELAVERA_ULTRAMSG_TOKEN) : '';
    if ($instance === '' || $token === '') {
        return array(
            'ok' => false,
            'http_code' => 0,
            'body' => '',
            'json' => null,
            'error' => 'Configure INSTANCE_ID y TOKEN en relavera/LOGICA/log_ultramsg_config.php',
        );
    }
    $url = 'https://api.ultramsg.com/' . rawurlencode($instance) . '/messages/statistics?' . http_build_query(array('token' => $token));
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array('content-type: application/x-www-form-urlencoded'),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($err !== '') {
        return array(
            'ok' => false,
            'http_code' => $httpCode,
            'body' => is_string($response) ? $response : '',
            'json' => null,
            'error' => 'cURL: ' . $err,
        );
    }
    $json = null;
    if (is_string($response) && $response !== '') {
        $json = json_decode($response, true);
    }
    $msgErr = relavera_ultramsg_api_texto_error_respuesta($json);
    return array(
        'ok' => ($httpCode >= 200 && $httpCode < 300 && $msgErr === ''),
        'http_code' => $httpCode,
        'body' => is_string($response) ? $response : '',
        'json' => $json,
        'error' => $msgErr !== '' ? ('UltraMsg: ' . $msgErr) : '',
    );
}

/**
 * @param mixed $json
 * @return int|null
 */
function relavera_ultramsg_parse_total_estadisticas($json)
{
    if (!is_array($json)) {
        return null;
    }
    if (isset($json['total']) && is_numeric($json['total'])) {
        return (int) round((float) $json['total']);
    }
    $sum = 0;
    $any = false;
    foreach ($json as $k => $v) {
        if ($k === 'error' || $k === 'message') {
            continue;
        }
        if (is_numeric($v)) {
            $sum += (int) round((float) $v);
            $any = true;
        } elseif (is_array($v)) {
            $sub = relavera_ultramsg_parse_total_estadisticas($v);
            if ($sub !== null) {
                return $sub;
            }
        }
    }
    return $any ? $sum : null;
}

if (!empty($_GET['listarMensajesUltramsgAjax'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    if ($limit < 1) {
        $limit = 10;
    }
    if ($limit > 1000) {
        $limit = 1000;
    }
    $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
    $r = relavera_ultramsg_api_get_messages($page, $limit, $status);
    $totalMessages = relavera_ultramsg_messages_total_desde_respuesta($r['json']);
    $stNorm = ($status === '') ? 'todos' : strtolower($status);
    if ($totalMessages === null && ($stNorm === 'todos' || $stNorm === 'all')) {
        $rs = relavera_ultramsg_api_get_messages_statistics();
        if ($rs['ok']) {
            $totalMessages = relavera_ultramsg_parse_total_estadisticas($rs['json']);
        }
    }
    $totalPages = null;
    if ($totalMessages !== null && $totalMessages >= 0 && $limit > 0) {
        $totalPages = (int) max(1, (int) ceil($totalMessages / $limit));
    }
    echo json_encode(array(
        'success' => $r['ok'],
        'http_code' => $r['http_code'],
        'error' => $r['error'],
        'data' => $r['json'],
        'raw' => $r['body'],
        'page' => $page,
        'limit' => $limit,
        'total_messages' => $totalMessages,
        'total_pages' => $totalPages,
    ));
    exit;
}

if (!empty($_GET['listChatsUltramsgAjax'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $r = relavera_ultramsg_api_get_chats();
    echo json_encode(array(
        'success' => $r['ok'],
        'http_code' => $r['http_code'],
        'error' => $r['error'],
        'data' => $r['json'],
        'raw' => $r['body'],
    ));
    exit;
}

if (!empty($_GET['cargarChatsUltramsgAjax'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $chatId = isset($_GET['chatId']) ? trim((string) $_GET['chatId']) : '';
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $r = relavera_ultramsg_api_get_chat_messages($chatId, $limit);
    echo json_encode(array(
        'success' => $r['ok'],
        'http_code' => $r['http_code'],
        'error' => $r['error'],
        'data' => $r['json'],
        'raw' => $r['body'],
    ));
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Mensajes de chat UltraMsg</title>
    <meta charset="UTF-8">
    <?php require_once('../../mascaras/model1/estilos/jqgrid5.php'); ?>
    <style>
        .chats-page-title { margin: 0; font-size: 15px; font-weight: 600; }
        .chats-page-title .glyphicon { margin-right: 6px; color: #25d366; }
        .chats-toolbar { margin-bottom: 12px; }
        .chats-pre { max-height: 320px; overflow: auto; font-size: 11px; background: #f8f9fa; border: 1px solid #ddd; padding: 10px; border-radius: 6px; }
        .chats-meta { font-size: 12px; color: #666; margin-bottom: 8px; }
        .chats-subtit { font-size: 13px; font-weight: 600; margin: 16px 0 8px; }
        .chats-fecha { white-space: nowrap; font-size: 12px; font-weight: 600; color: #333; }
        .chats-fecha-raw { display: block; font-size: 10px; font-weight: normal; color: #888; }
        .chats-estado-extra { display: block; font-size: 10px; margin-top: 2px; color: #666; }
        /* Fallback visual si no hay estilos bootstrap para .label */
        .label { display: inline-block; padding: 2px 6px; font-size: 11px; font-weight: 700; border-radius: 3px; color: #fff; }
        .label-default { background: #777; }
        .label-primary { background: #337ab7; }
        .label-success { background: #5cb85c; }
        .label-info { background: #5bc0de; }
        .label-warning { background: #f0ad4e; }
        .label-danger { background: #d9534f; }
        .row-invalid td { background: #f8d7da !important; }
        .msg-invalid { background: #d9534f; color: #fff; padding: 2px 4px; border-radius: 3px; display: inline-block; }
        .um-instancia .row-expired td { background: #fcf8e3 !important; }
        .um-instancia .cell-expired { background: #f0ad4e; color: #fff; font-weight: 700; border-radius: 3px; padding: 2px 4px; display: inline-block; }
        .um-instancia .cell-invalid { background: #d9534f !important; color: #fff !important; font-weight: 700; border-radius: 3px; padding: 2px 4px; display: inline-block; }
        .um-instancia .um-pre { max-height: 320px; overflow: auto; font-size: 11px; background: #f8f9fa; border: 1px solid #ddd; padding: 10px; border-radius: 6px; }
        .um-instancia .um-meta { font-size: 12px; color: #666; margin: 10px 0 8px; }
        .um-instancia .table-pagination {
            display: none;
            margin-top: 0;
            padding: 6px 8px 10px;
            border: 1px solid #ddd;
            border-top: none;
            background: #f5f5f5;
            border-radius: 0 0 4px 4px;
            text-align: center;
        }
        .um-instancia .table-pagination .pagination { margin: 0; display: inline-block; }
        .um-instancia .table-pagination .pagination > li > a,
        .um-instancia .table-pagination .pagination > li > span { padding: 5px 10px; }
    </style>
</head>
<body>
<div class="panel panel-main">
    <div class="panel-heading exa-header">
        <h3 class="panel-title chats-page-title"><span class="glyphicon glyphicon-comment"></span> Lista de Mensajes</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <!--div class="chats-toolbar form-inline" style="margin-bottom:14px;">
            <a href="man_adm_chats_lista.php" class="btn btn-primary btn-sm">
                <span class="glyphicon glyphicon-list"></span> Ir a conversaciones
            </a>
            <a href="#um-mensajes-instancia" class="btn btn-default btn-sm" style="margin-left:8px;">
                <span class="glyphicon glyphicon-envelope"></span> Mensajes por estado (instancia)
            </a>
        </div-->

      
      
        <div class="chats-meta" id="chats_meta"></div>
        <div class="table-responsive">
            <table class="table table-striped table-condensed table-bordered" id="tabla_chats" style="display:none;">
                <thead id="thead_chats"></thead>
                <tbody id="tbody_chats"></tbody>
            </table>
        </div>
        <p class="text-muted" id="chats_sin_tabla" style="display:none;">No se pudo armar una tabla automática. Respuesta cruda:</p>
        <pre class="chats-pre" id="chats_raw" style="display:none;"></pre>

        <div id="um-mensajes-instancia" class="um-instancia">
           
            <div class="chats-toolbar form-inline" style="margin-bottom:12px;">
                <div class="form-group" style="margin-right:12px;">
                    <label for="um_sel_status" style="margin-right:6px;">Estado</label>
                    <select id="um_sel_status" class="form-control input-sm" style="min-width:180px;">
                        <option value="">todos</option>
                        <option value="queue">Cola (queue)</option>
                        <option value="sent">Enviado (sent)</option>
                        <option value="unsent">No enviado (unsent)</option>
                        <option value="invalid">Inválida (invalid)</option>
                        <option value="expired">Caducado (expired)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-right:12px;">
                    <label for="um_inp_limit" style="margin-right:6px;">Límite</label>
                    <input type="number" id="um_inp_limit" class="form-control input-sm" style="width:90px;" min="1" max="1000" value="10" />
                </div>
                <button type="button" id="btn_um_cargar" class="btn btn-primary btn-sm">
                    <span class="glyphicon glyphicon-refresh"></span> Cargar
                </button>
                <span id="um_estado" class="text-muted" style="margin-left:10px;"></span>
            </div>
            <div class="um-meta" id="um_meta"></div>
            <div class="table-responsive">
                <table class="table table-striped table-condensed table-bordered" id="tabla_um" style="display:none;">
                    <thead id="thead_um"></thead>
                    <tbody id="tbody_um"></tbody>
                </table>
                <div class="table-pagination" id="paginacion_um" role="navigation" aria-label="Paginación mensajes instancia">
                    <ul class="pagination pagination-sm" id="pag_ul_um"></ul>
                </div>
            </div>
            <p class="text-muted" id="um_sin_tabla" style="display:none;">No se pudo armar una tabla automática. Respuesta:</p>
            <pre class="um-pre" id="um_raw" style="display:none;"></pre>
        </div>
    </div>
</div>
<script>
(function () {
    var btn = document.getElementById('btn_refrescar_chats');
    var inpChat = document.getElementById('inp_chat_id');
    var inpLim = document.getElementById('inp_limit');
    var estado = document.getElementById('chats_estado');
    var meta = document.getElementById('chats_meta');
    var tabla = document.getElementById('tabla_chats');
    var thead = document.getElementById('thead_chats');
    var tbody = document.getElementById('tbody_chats');
    var sinTabla = document.getElementById('chats_sin_tabla');
    var rawPre = document.getElementById('chats_raw');
    var selFiltro = document.getElementById('sel_filtro_estado');

    /** Mensajes crudos de la última carga (antes de filtrar). */
    var mensajesCache = [];
    var ultimaRespuestaCruda = { raw: '', data: null };
    var ultimaCargaHttp = { http_code: null, success: false };

    function escapeHtml(s) {
        if (s == null) return '';
        var d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    function etiquetaColumnaEs(key) {
        var k = String(key || '').toLowerCase();
        var map = {
            id: 'ID',
            msgid: 'ID mensaje',
            messageid: 'ID mensaje',
            chatid: 'Chat ID',
            jid: 'Chat ID',
            from: 'Desde',
            to: 'Para',
            phone: 'Teléfono',
            fromme: 'Saliente',
            body: 'Mensaje',
            message: 'Mensaje',
            text: 'Mensaje',
            content: 'Contenido',
            caption: 'Descripción',
            type: 'Tipo',
            status: 'Estatus',
            ack: 'ACK',
            priority: 'Prioridad',
            reference: 'Referencia',
            created_at: 'Creado en',
            sent_at: 'Enviado en',
            delivered_at: 'Entregado en',
            read_at: 'Leído en',
            timestamp: 'Fecha/Hora',
            time: 'Fecha/Hora'
        };
        return map[k] || '';
    }

    function etiquetaStatusEs(statusRaw) {
        var s = textoSeguro(statusRaw).toLowerCase().trim();
        var map = {
            queue: 'Cola',
            sent: 'Enviado',
            unsent: 'No enviado',
            invalid: 'Inválido',
            expired: 'Caducado',
            pending: 'Pendiente',
            delivered: 'Entregado',
            read: 'Leído'
        };
        return map[s] || '';
    }

    function textoSeguro(v) {
        if (v == null) return '';
        if (typeof v === 'string') return v;
        if (typeof v === 'number' || typeof v === 'boolean') return String(v);
        try { return JSON.stringify(v); } catch (e) { return String(v); }
    }

    function textoStatusCrudo(row) {
        if (!row || typeof row !== 'object') return '';
        var rawSt = row.status || row.messageStatus || row.state || row.deliveryStatus || row.MessageStatus;
        return (rawSt != null) ? textoSeguro(rawSt) : '';
    }

    function esMensajeInvalido(row) {
        var st = textoStatusCrudo(row).toLowerCase();
        return st.indexOf('invalid') !== -1;
    }

    function esCampoMensaje(key) {
        var k = String(key || '').toLowerCase();
        return (k === 'message' || k === 'mensaje' || k === 'body' || k === 'text' || k === 'content' || k === 'caption');
    }

    /** Puntuación: menor = columna más "de fecha" (para ordenar). */
    function puntuacionClaveFecha(name) {
        var s = String(name).toLowerCase();
        if (/timestamp|(^|_)time($|_)|datetime|(^|_)date($|_)|created|modified|sent|received|(^|_)at$|_at$/.test(s)) {
            return 0;
        }
        return 1;
    }

    /** Convierte timestamp Unix (seg. o ms), ISO u otro valor a fecha relocalizable. */
    function fechaObjDesdeValor(v) {
        if (v == null || v === '') return null;
        if (typeof v === 'object' && v !== null) return null;
        var s = String(v).trim();
        if (/^\d{4}-\d{2}-\d{2}/.test(s) || s.indexOf('T') !== -1) {
            var t = Date.parse(s);
            if (!isNaN(t)) return new Date(t);
        }
        var n = typeof v === 'number' ? v : parseFloat(s);
        if (!isFinite(n)) return null;
        var ms;
        if (n > 9999999999) ms = Math.floor(n);
        else ms = Math.floor(n * 1000);
        var d = new Date(ms);
        if (isNaN(d.getTime())) return null;
        var y = d.getFullYear();
        if (y < 1990 || y > 2120) return null;
        return d;
    }

    function formatearFechaHoraExacta(d) {
        return d.toLocaleString('es', {
            weekday: 'short',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
            timeZoneName: 'short'
        });
    }

    function tomarPrimeraFechaDeFila(row) {
        if (!row || typeof row !== 'object') return { d: null, clave: '' };
        var orden = [
            'timestamp', 'time', 'created_at', 'created', 'sent_at', 'sent', 'date', 'datetime',
            'received_at', 'received', 'modified_at', 'modified', 'mtime', 'ctime', 't', 'ts'
        ];
        var i, k, d0;
        for (i = 0; i < orden.length; i++) {
            k = orden[i];
            if (Object.prototype.hasOwnProperty.call(row, k)) {
                d0 = fechaObjDesdeValor(row[k]);
                if (d0) return { d: d0, clave: k };
            }
        }
        for (k in row) {
            if (Object.prototype.hasOwnProperty.call(row, k) && puntuacionClaveFecha(k) === 0) {
                d0 = fechaObjDesdeValor(row[k]);
                if (d0) return { d: d0, clave: k };
            }
        }
        for (k in row) {
            if (Object.prototype.hasOwnProperty.call(row, k)) {
                d0 = fechaObjDesdeValor(row[k]);
                if (d0) return { d: d0, clave: k };
            }
        }
        return { d: null, clave: '' };
    }

    function htmlCeldaMensaje(key, val) {
        if (String(key).toLowerCase() === 'status') {
            var lbl = etiquetaStatusEs(val);
            if (lbl) return escapeHtml(lbl);
        }
        var dObj = fechaObjDesdeValor(val);
        if (dObj && puntuacionClaveFecha(key) === 0) {
            var leg = formatearFechaHoraExacta(dObj);
            var raw = (val != null && val !== '') ? String(val) : '';
            return '<span class="chats-fecha">' + escapeHtml(leg) + '</span>'
                + (raw && raw !== leg ? '<span class="chats-fecha-raw" title="Valor API">API: ' + escapeHtml(raw) + '</span>' : '');
        }
        if (dObj && (typeof val === 'number' || /^\d+\.?\d*$/.test(String(val).trim()))) {
            return '<span class="chats-fecha">' + escapeHtml(formatearFechaHoraExacta(dObj)) + '</span>'
                + '<span class="chats-fecha-raw" title="Valor numérico original">API: ' + escapeHtml(String(val)) + '</span>';
        }
        var t = (val == null) ? '' : String(val);
        return t ? escapeHtml(t) : '<span class="text-muted">—</span>';
    }

    function ordenarClavesMensajes(keys) {
        var copia = keys.slice();
        copia.sort(function (a, b) {
            var pa = puntuacionClaveFecha(a), pb = puntuacionClaveFecha(b);
            if (pa !== pb) return pa - pb;
            return String(a).localeCompare(String(b), 'es');
        });
        return copia;
    }

    function esSaliente(row) {
        if (!row || typeof row !== 'object') return null;
        if (!Object.prototype.hasOwnProperty.call(row, 'fromMe')) return null;
        var fm = row.fromMe;
        if (fm === true || fm === 1 || fm === '1' || fm === 'true') return true;
        if (fm === false || fm === 0 || fm === '0' || fm === 'false') return false;
        return null;
    }

    function obtenerAckNumerico(row) {
        if (!row || typeof row !== 'object') return null;
        var a = row.ack;
        if (a === undefined || a === null) {
            a = row.message_ack || row.msgAck || row.statusAck || row.messageAck;
        }
        if (typeof a === 'string' && /^-?\d+$/.test(String(a).trim())) {
            return parseInt(String(a).trim(), 10);
        }
        if (typeof a === 'number' && isFinite(a)) return Math.floor(a);
        return null;
    }

    /**
     * Estados normalizados para filtro / etiqueta.
     * Compatible con ack tipo whatsapp-web (-1 error, 0 pendiente, 1 servidor, 2 entregado, 3+ leído).
     */
    function estadoDesdeAck(ack) {
        if (ack === null || ack === undefined || typeof ack !== 'number' || !isFinite(ack)) {
            return null;
        }
        var k = Math.floor(Number(ack));
        if (k === -1) return 'error';
        if (k <= 0) return 'pendiente';
        if (k === 1) return 'enviado';
        if (k === 2) return 'entregado';
        if (k >= 3) return 'leido';
        return 'desconocido';
    }

    function normalizarEstadoEntrega(row) {
        if (!row || typeof row !== 'object') return 'desconocido';
        if (esSaliente(row) === false) return 'recibido';
        var str = row.status || row.messageStatus || row.state || row.deliveryStatus || row.MessageStatus;
        if (str != null && typeof str === 'string') {
            var sl = String(str).toLowerCase().trim();
            if (/fail|error|reject|undeliver|expired|cannot|invalid/.test(sl)) return 'error';
            if (/pend|queue|wait|unsent|sending|retry/.test(sl)) return 'pendiente';
            if (/read|seen|played/.test(sl)) return 'leido';
            if (/deliv|received/.test(sl)) return 'entregado';
            if (/sent|server|broadcast/.test(sl)) return 'enviado';
        }
        var ed = estadoDesdeAck(obtenerAckNumerico(row));
        if (ed) return ed;
        if (row.readTimestamp || row.read === true) return 'leido';
        if (row.deliveredTimestamp || row.delivered === true) return 'entregado';
        return 'desconocido';
    }

    function etiquetaEstadoHtml(row) {
        var st = normalizarEstadoEntrega(row);
        var map = {
            pendiente: ['warning', 'Pendiente'],
            enviado: ['info', 'Enviado'],
            entregado: ['default', 'Entregado'],
            leido: ['success', 'Leído'],
            error: ['danger', 'Error'],
            recibido: ['primary', 'Recibido'],
            desconocido: ['default', '¿Estado?']
        };
        var pair = map[st] || map.desconocido;
        var ack = obtenerAckNumerico(row);
        var bits = [];
        if (ack !== null) bits.push('ack=' + ack);
        var rawStTxt = textoStatusCrudo(row).trim();
        if (rawStTxt !== '') bits.push(rawStTxt);
        var extra = bits.length ? bits.join(' · ') : '';
        return '<span class="label label-' + pair[0] + '">' + escapeHtml(pair[1]) + '</span>'
            + (extra ? '<span class="chats-estado-extra" title="Datos API">' + escapeHtml(extra) + '</span>' : '');
    }

    function extraerArrayMensajes(d) {
        if (Array.isArray(d)) return d;
        if (d && typeof d === 'object' && Array.isArray(d.messages)) return d.messages;
        return null;
    }

    function mensajePasaFiltro(row, filtro) {
        var st = normalizarEstadoEntrega(row);
        var sal = esSaliente(row);
        switch (filtro) {
            case 'todos':
                return true;
            case 'pendiente':
                return st === 'pendiente';
            case 'enviado':
                return st === 'enviado';
            case 'entregado':
                return st === 'entregado';
            case 'leido':
                return st === 'leido';
            case 'error':
                return st === 'error';
            case 'recibido':
                return st === 'recibido';
            case 'saliente':
                return sal === true;
            case 'desconocido':
                return st === 'desconocido';
            case 'no_entregado':
                return sal === true && (st === 'pendiente' || st === 'enviado' || st === 'error');
            default:
                return true;
        }
    }

    function aplicarFiltroYRender(dataAjax) {
        dataAjax = dataAjax || {};
        var hc = dataAjax.http_code !== undefined && dataAjax.http_code !== null
            ? dataAjax.http_code : ultimaCargaHttp.http_code;
        var ok = dataAjax.success !== undefined ? !!dataAjax.success : ultimaCargaHttp.success;

        sinTabla.style.display = 'none';
        rawPre.style.display = 'none';
        tabla.style.display = 'none';

        if (!mensajesCache.length) {
            meta.textContent = 'HTTP ' + (hc != null ? hc : '—')
                + (ok ? ' — OK' : ' — Error')
                + ' · Sin mensajes en esta respuesta (o vacío).';
            sinTabla.style.display = 'block';
            sinTabla.textContent = 'No hay mensajes en el tramo solicitado, o el formato no es un listado.';
            if (ultimaRespuestaCruda.raw) {
                rawPre.style.display = 'block';
                rawPre.textContent = ultimaRespuestaCruda.raw;
            }
            return;
        }

        var filtro = selFiltro ? selFiltro.value : 'todos';
        var out = [];
        var i;
        for (i = 0; i < mensajesCache.length; i++) {
            if (mensajePasaFiltro(mensajesCache[i], filtro)) out.push(mensajesCache[i]);
        }

        meta.textContent = 'HTTP ' + (hc != null ? hc : '—')
            + (ok ? ' — OK' : ' — Error')
            + ' · Mostrando ' + out.length + ' de ' + mensajesCache.length + ' mensajes';

        if (!out.length) {
            sinTabla.style.display = 'block';
            sinTabla.textContent = 'Ningún mensaje coincide con el filtro seleccionado.';
            meta.textContent += ' · Ninguno coincide con el filtro.';
            return;
        }

        var okTabla = renderTablaDesdeFilas(out, thead, tbody, { modoMensajes: true });
        if (okTabla) tabla.style.display = 'table';
        else {
            sinTabla.style.display = 'block';
            sinTabla.textContent = 'No se pudo construir la tabla.';
            rawPre.style.display = 'block';
            rawPre.textContent = ultimaRespuestaCruda.raw || '';
        }
    }

    function renderTablaDesdeFilas(rows, theadEl, tbodyEl, opts) {
        opts = opts || {};
        if (!rows || !rows.length || typeof rows[0] !== 'object' || rows[0] === null) return false;
        var modoMensajes = !!opts.modoMensajes;
        var keys = Object.keys(rows[0]).filter(function (k) {
            var kl = String(k).toLowerCase();
            return kl !== 'referenceid' && kl !== 'metadata';
        });
        if (!keys.length) return false;
        if (modoMensajes) {
            keys = ordenarClavesMensajes(keys);
        }
        var h = '<tr>';
        if (modoMensajes) {
            h += '<th class="chats-fecha" style="min-width:220px;">Fecha y hora (env&iacute;o)</th>';
            h += '<th style="min-width:140px;">Estado entrega</th>';
        }
        for (var i = 0; i < keys.length; i++) {
            var es = etiquetaColumnaEs(keys[i]);
            h += '<th>' + escapeHtml(es ? es : keys[i]) + '</th>';
        }
        h += '</tr>';
        theadEl.innerHTML = h;
        var html = '';
        for (var r = 0; r < rows.length; r++) {
            var row = rows[r];
            var firstF = modoMensajes ? tomarPrimeraFechaDeFila(row) : { d: null, clave: '' };
            var trClass = (modoMensajes && esMensajeInvalido(row)) ? ' class="row-invalid"' : '';
            html += '<tr' + trClass + '>';
            if (modoMensajes) {
                if (firstF.d) {
                    var ftext = formatearFechaHoraExacta(firstF.d);
                    html += '<td class="chats-fecha">' + escapeHtml(ftext)
                        + (firstF.clave ? '<span class="chats-fecha-raw" title="Campo usado">' + escapeHtml(firstF.clave) + '</span>' : '')
                        + '</td>';
                } else {
                    html += '<td class="text-warning" style="font-size:11px;">Sin fecha en la respuesta. Revise columna cruda en la API.</td>';
                }
                html += '<td>' + etiquetaEstadoHtml(row) + '</td>';
            }
            for (var k = 0; k < keys.length; k++) {
                var v = row[keys[k]];
                if (modoMensajes && esMensajeInvalido(row) && esCampoMensaje(keys[k])) {
                    var txt = (v == null) ? '' : String(v);
                    html += '<td>' + (txt ? '<span class="msg-invalid">' + escapeHtml(txt) + '</span>' : '<span class="text-muted">—</span>') + '</td>';
                } else {
                    html += '<td>' + (modoMensajes ? htmlCeldaMensaje(keys[k], v) : ((v != null && v !== '') ? escapeHtml(String(v)) : '<span class="text-muted">—</span>')) + '</td>';
                }
            }
            html += '</tr>';
        }
        tbodyEl.innerHTML = html;
        return true;
    }

    function mostrarRespuesta(data) {
        data = data || {};
        ultimaCargaHttp.http_code = data.http_code;
        ultimaCargaHttp.success = !!data.success;
        ultimaRespuestaCruda.raw = data.raw || '';
        ultimaRespuestaCruda.data = data.data;

        meta.textContent = 'HTTP ' + (data.http_code != null ? data.http_code : '—')
            + (data.success ? ' — OK' : ' — Error');
        rawPre.style.display = 'none';
        rawPre.textContent = '';
        sinTabla.style.display = 'none';
        tabla.style.display = 'none';

        if (data.error) {
            estado.className = 'text-danger';
            estado.textContent = textoSeguro(data.error);
        } else if (!data.success) {
            estado.className = 'text-warning';
            estado.textContent = 'La API respondió con error. Revise el cuerpo abajo o el código HTTP.';
        } else {
            estado.className = 'text-success';
            estado.textContent = 'Datos cargados.';
        }

        var d = data.data;
        var arr = extraerArrayMensajes(d);
        if (arr !== null) {
            mensajesCache = arr;
            aplicarFiltroYRender(data);
            return;
        }

        mensajesCache = [];
        var okTabla = false;
        if (d && typeof d === 'object' && Array.isArray(d.chats)) {
            okTabla = renderTablaDesdeFilas(d.chats, thead, tbody, { modoMensajes: false });
        }
        if (okTabla) {
            tabla.style.display = 'table';
            meta.textContent += ' · Chat list (no es lista de mensajes).';
            return;
        }
        sinTabla.style.display = 'block';
        rawPre.style.display = 'block';
        rawPre.textContent = data.raw || JSON.stringify(d, null, 2);
    }

    function cargar() {
        estado.className = 'text-info';
        estado.textContent = 'Cargando…';
        meta.textContent = '';
        var q = 'cargarChatsUltramsgAjax=1';
        if (inpChat && inpChat.value.trim()) {
            q += '&chatId=' + encodeURIComponent(inpChat.value.trim());
        }
        var lim = inpLim && inpLim.value ? parseInt(inpLim.value, 10) : 50;
        if (!lim || lim < 1) lim = 50;
        q += '&limit=' + encodeURIComponent(String(lim));
        fetch('man_adm_chats.php?' + q, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) { mostrarRespuesta(data || {}); })
            .catch(function () {
                estado.className = 'text-danger';
                estado.textContent = 'Error de red o respuesta no JSON.';
            });
    }

    if (btn) btn.addEventListener('click', cargar);
    if (selFiltro) selFiltro.addEventListener('change', function () {
        aplicarFiltroYRender({});
    });

    // Prefill desde querystring: ?chatId=...
    try {
        var qs = new URLSearchParams(window.location.search || '');
        var cid = (qs.get('chatId') || '').trim();
        if (cid && inpChat && !inpChat.value) inpChat.value = cid;
    } catch (e) {}
})();
</script>
<script>
(function () {
    var btn = document.getElementById('btn_um_cargar');
    var sel = document.getElementById('um_sel_status');
    var inpL = document.getElementById('um_inp_limit');
    var pagBox = document.getElementById('paginacion_um');
    var pagUl = document.getElementById('pag_ul_um');
    var currentPage = 1;
    var lastLimit = 10;
    var lastRowCount = 0;
    var totalPagesEffective = null;
    var estado = document.getElementById('um_estado');
    var meta = document.getElementById('um_meta');
    var tabla = document.getElementById('tabla_um');
    var thead = document.getElementById('thead_um');
    var tbody = document.getElementById('tbody_um');
    var sinTabla = document.getElementById('um_sin_tabla');
    var raw = document.getElementById('um_raw');

    function escapeHtml(s) {
        if (s == null) return '';
        var d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    function etiquetaColumnaEs(key) {
        var k = String(key || '').toLowerCase();
        var map = {
            id: 'ID',
            msgid: 'ID mensaje',
            messageid: 'ID mensaje',
            chatid: 'Chat ID',
            jid: 'Chat ID',
            from: 'Emisor',
            to: 'Receptor',
            phone: 'Teléfono',
            body: 'Mensaje',
            message: 'Mensaje',
            text: 'Mensaje',
            content: 'Contenido',
            caption: 'Descripción',
            type: 'Tipo',
            status: 'Estado',
            ack: 'ACK',
            priority: 'Prioridad',
            reference: 'Referencia',
            created_at: 'Creado en',
            sent_at: 'Fecha Envio',
            delivered_at: 'Entregado en',
            read_at: 'Leído en',
            timestamp: 'Fecha/Hora',
            time: 'Fecha/Hora'
        };
        return map[k] || '';
    }

    function esClaveFecha(key) {
        var k = String(key || '').toLowerCase();
        return (k === 'created_at' || k === 'sent_at' || k === 'delivered_at' || k === 'read_at' || k === 'timestamp' || k === 'time' || /_at$/.test(k));
    }

    function fechaObjDesdeValor(v) {
        if (v == null || v === '') return null;
        if (typeof v === 'object' && v !== null) return null;
        var s = String(v).trim();
        // ISO date
        if (/^\d{4}-\d{2}-\d{2}/.test(s) || s.indexOf('T') !== -1) {
            var t = Date.parse(s);
            if (!isNaN(t)) return new Date(t);
        }
        // unix seconds or ms
        var n = typeof v === 'number' ? v : parseFloat(s);
        if (!isFinite(n)) return null;
        var ms = (n > 9999999999) ? Math.floor(n) : Math.floor(n * 1000);
        var d = new Date(ms);
        if (isNaN(d.getTime())) return null;
        var y = d.getFullYear();
        if (y < 1990 || y > 2120) return null;
        return d;
    }

    function formatearFechaHoraExacta(d) {
        return d.toLocaleString('es', {
            weekday: 'short',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
            timeZoneName: 'short'
        });
    }

    function textoSeguro(v) {
        if (v == null) return '';
        if (typeof v === 'string') return v;
        if (typeof v === 'number' || typeof v === 'boolean') return String(v);
        try { return JSON.stringify(v); } catch (e) { return String(v); }
    }

    function statusFila(row) {
        if (!row || typeof row !== 'object') return '';
        var st = row.status != null ? row.status : (row.messageStatus != null ? row.messageStatus : row.state);
        return textoSeguro(st).toLowerCase().trim();
    }

    function filaEsInvalid(row) {
        return statusFila(row) === 'invalid';
    }

    function filaEsExpired(row) {
        return statusFila(row) === 'expired';
    }

    function etiquetaStatusEs(statusRaw) {
        var s = textoSeguro(statusRaw).toLowerCase().trim();
        var map = {
            queue: 'Cola',
            sent: 'Enviado',
            unsent: 'No enviado',
            invalid: 'Inválido',
            expired: 'Caducado',
            pending: 'Pendiente',
            delivered: 'Entregado',
            read: 'Leído'
        };
        return map[s] || '';
    }

    function renderTabla(rows) {
        if (!rows || !rows.length || typeof rows[0] !== 'object' || rows[0] === null) return false;
        var keys = Object.keys(rows[0]).filter(function (k) {
            var kl = String(k).toLowerCase();
            return kl !== 'referenceid' && kl !== 'metadata' && kl !== 'created_at' && kl !== 'priority' && kl !== 'ack';
        });
        if (!keys.length) return false;
        var h = '<tr>';
        for (var i = 0; i < keys.length; i++) {
            var es = etiquetaColumnaEs(keys[i]);
            h += '<th>' + escapeHtml(es ? es : keys[i]) + '</th>';
        }
        h += '</tr>';
        thead.innerHTML = h;

        var html = '';
        for (var r = 0; r < rows.length; r++) {
            var row = rows[r];
            var trClass = filaEsInvalid(row) ? ' class="row-invalid"' : (filaEsExpired(row) ? ' class="row-expired"' : '');
            html += '<tr' + trClass + '>';
            for (var k = 0; k < keys.length; k++) {
                var key = keys[k];
                var v = row[key];
                var t = (v == null) ? '' : String(v);
                if (String(key).toLowerCase() === 'status') {
                    if (filaEsInvalid(row)) {
                        html += '<td><span class="cell-invalid">' + escapeHtml(etiquetaStatusEs(v) || t || 'invalid') + '</span></td>';
                    } else if (filaEsExpired(row)) {
                        html += '<td><span class="cell-expired">' + escapeHtml(etiquetaStatusEs(v) || t || 'expired') + '</span></td>';
                    } else {
                        var lbl = etiquetaStatusEs(v);
                        html += '<td>' + (lbl ? escapeHtml(lbl) : (t ? escapeHtml(t) : '<span class="text-muted">—</span>')) + '</td>';
                    }
                } else if (esClaveFecha(key)) {
                    var d = fechaObjDesdeValor(v);
                    if (d) {
                        var leg = formatearFechaHoraExacta(d);
                        html += '<td><span style="white-space:nowrap;">' + escapeHtml(leg) + '</span></td>';
                    } else {
                        html += '<td>' + (t ? escapeHtml(t) : '<span class="text-muted">—</span>') + '</td>';
                    }
                } else {
                    html += '<td>' + (t ? escapeHtml(t) : '<span class="text-muted">—</span>') + '</td>';
                }
            }
            html += '</tr>';
        }
        tbody.innerHTML = html;
        return true;
    }

    /**
     * @param {number} cur
     * @param {number|null} total
     * @param {boolean} canNext
     * @return {(number|string)[]}
     */
    function getPaginationItems(cur, total, canNext) {
        cur = Math.max(1, cur | 0);
        if (total != null && total >= 1) {
            if (total <= 10) {
                var r = [];
                for (var a = 1; a <= total; a++) r.push(a);
                return r;
            }
            if (cur <= 5) {
                var out = [];
                for (var i = 1; i <= Math.min(7, total); i++) out.push(i);
                if (total > 7) {
                    if (total > 9) {
                        out.push('ellipsis');
                        out.push(total - 1);
                        out.push(total);
                    } else {
                        for (var j = 8; j <= total; j++) out.push(j);
                    }
                }
                return out;
            }
            if (cur >= total - 4) {
                var b = [1, 'ellipsis'];
                for (var k = Math.max(2, total - 6); k <= total; k++) b.push(k);
                return b;
            }
            var left = Math.max(2, cur - 2);
            var right = Math.min(total - 1, cur + 2);
            var c = [1];
            if (left > 2) c.push('ellipsis');
            for (var x = left; x <= right; x++) c.push(x);
            if (right < total - 1) {
                c.push('ellipsis');
                c.push(total - 1);
                c.push(total);
            } else if (right === total - 1 && total > right) {
                c.push(total);
            }
            return c;
        }
        if (cur <= 7) {
            var end = canNext ? Math.max(7, cur + 1) : cur;
            var u = [];
            for (var p = 1; p <= end; p++) u.push(p);
            return u;
        }
        var res = [1, 'ellipsis'];
        for (var z = Math.max(2, cur - 2); z <= cur; z++) res.push(z);
        if (canNext) res.push(cur + 1);
        res.push('ellipsis');
        return res;
    }

    function actualizarPaginacionUI() {
        if (!pagBox || !pagUl) return;
        var visible = tabla.style.display === 'table' && lastRowCount > 0;
        pagBox.style.display = visible ? 'block' : 'none';
        if (!visible) return;

        var canPrev = currentPage > 1;
        var canNext = totalPagesEffective != null
            ? (currentPage < totalPagesEffective)
            : (lastRowCount >= lastLimit);

        var items = getPaginationItems(currentPage, totalPagesEffective, canNext);
        var html = '';

        if (canPrev) {
            html += '<li><a href="#" data-page="' + (currentPage - 1) + '">Previo</a></li>';
        } else {
            html += '<li class="disabled"><span>Previo</span></li>';
        }

        for (var n = 0; n < items.length; n++) {
            var it = items[n];
            if (it === 'ellipsis') {
                html += '<li class="disabled"><span>&hellip;</span></li>';
                continue;
            }
            var num = it | 0;
            if (num === currentPage) {
                html += '<li class="active"><a href="#">' + num + '</a></li>';
            } else {
                html += '<li><a href="#" data-page="' + num + '">' + num + '</a></li>';
            }
        }

        if (canNext) {
            html += '<li><a href="#" data-page="' + (currentPage + 1) + '">Próximo</a></li>';
        } else {
            html += '<li class="disabled"><span>Próximo</span></li>';
        }

        pagUl.innerHTML = html;
    }

    function mostrar(data) {
        data = data || {};
        meta.textContent = 'HTTP ' + (data.http_code != null ? data.http_code : '—')
            + (data.success ? ' — OK' : ' — Error');
        tabla.style.display = 'none';
        if (pagBox) pagBox.style.display = 'none';
        sinTabla.style.display = 'none';
        raw.style.display = 'none';
        raw.textContent = '';
        lastRowCount = 0;
        totalPagesEffective = null;

        if (data.error) {
            estado.className = 'text-danger';
            estado.textContent = textoSeguro(data.error);
        } else if (!data.success) {
            estado.className = 'text-warning';
            estado.textContent = 'La API respondió con error.';
        } else {
            estado.className = 'text-success';
            estado.textContent = 'Datos cargados.';
        }

        var d = data.data;
        var rows = null;
        if (Array.isArray(d)) rows = d;
        else if (d && typeof d === 'object' && Array.isArray(d.messages)) rows = d.messages;

        if (data.page != null) {
            var pSync = parseInt(data.page, 10);
            if (pSync >= 1) currentPage = pSync;
        }
        var limResp = data.limit != null ? parseInt(data.limit, 10) : lastLimit;
        if (limResp >= 1) lastLimit = limResp;

        if (rows && renderTabla(rows)) {
            tabla.style.display = 'table';
            lastRowCount = rows.length;
            meta.textContent += ' · ' + rows.length + ' filas';
            if (typeof data.total_pages === 'number' && data.total_pages >= 1) {
                totalPagesEffective = data.total_pages;
            } else if (rows.length < lastLimit) {
                totalPagesEffective = currentPage;
            } else {
                totalPagesEffective = null;
            }
            actualizarPaginacionUI();
            return;
        }
        sinTabla.style.display = 'block';
        raw.style.display = 'block';
        raw.textContent = data.raw || JSON.stringify(d, null, 2);
    }

    function cargar(resetPage) {
        if (resetPage) currentPage = 1;
        estado.className = 'text-info';
        estado.textContent = 'Cargando…';
        var status = sel ? (sel.value || '') : '';
        var page = currentPage;
        if (!page || page < 1) page = 1;
        currentPage = page;
        var limit = inpL && inpL.value ? parseInt(inpL.value, 10) : 10;
        if (!limit || limit < 1) limit = 10;
        lastLimit = limit;

        var q = 'listarMensajesUltramsgAjax=1'
            + '&page=' + encodeURIComponent(String(page))
            + '&limit=' + encodeURIComponent(String(limit));
        if (status) q += '&status=' + encodeURIComponent(status);

        fetch('man_adm_chats.php?' + q, { credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (txt) {
                    return { ok: r.ok, status: r.status, statusText: r.statusText, text: txt };
                });
            })
            .then(function (resp) {
                var data = null;
                try { data = resp.text ? JSON.parse(resp.text) : null; } catch (e) { data = null; }
                if (data) {
                    mostrar(data || {});
                    return;
                }
                estado.className = 'text-danger';
                estado.textContent = 'Respuesta no JSON (HTTP ' + resp.status + ').';
                meta.textContent = 'HTTP ' + resp.status + ' — No JSON';
                tabla.style.display = 'none';
                sinTabla.style.display = 'block';
                raw.style.display = 'block';
                raw.textContent = resp.text || '';
            })
            .catch(function (e) {
                estado.className = 'text-danger';
                estado.textContent = 'Error de red: ' + (e && e.message ? e.message : 'desconocido');
            });
    }

    if (btn) btn.addEventListener('click', function () { cargar(true); });

    if (pagUl) {
        pagUl.addEventListener('click', function (ev) {
            var t = ev.target;
            if (!t || t.tagName !== 'A') return;
            ev.preventDefault();
            if (t.parentNode && t.parentNode.classList && t.parentNode.classList.contains('disabled')) return;
            if (t.parentNode && t.parentNode.classList && t.parentNode.classList.contains('active')) return;
            var dp = t.getAttribute('data-page');
            if (dp == null || dp === '') return;
            var v = parseInt(dp, 10);
            if (!isFinite(v) || v < 1) return;
            if (totalPagesEffective != null && v > totalPagesEffective) return;
            currentPage = v;
            cargar(false);
        });
    }

    cargar(true);
})();
</script>
</body>
</html>
