<?php
require_once __DIR__ . '/../../classes/DataAPI.php';

/**
 * Normaliza un número móvil ecuatoriano a formato internacional +5939XXXXXXXX.
 * Acepta +5939XXXXXXXX, 09XXXXXXXX o 9XXXXXXXX. Devuelve '' si no hay número.
 */
if (!function_exists('normalizar_celular_ec')) {
    function normalizar_celular_ec($celular)
    {
        $solo = preg_replace('/\D/', '', (string) $celular);
        if ($solo === '') {
            return '';
        }
        // Ya internacional con código de país
        if (substr($solo, 0, 3) === '593' && strlen($solo) === 13) {
            return '+' . $solo;
        }
        // Local con 0 inicial: 09XXXXXXXX
        if (strlen($solo) === 10 && substr($solo, 0, 1) === '0') {
            return '+593' . substr($solo, 1);
        }
        // Celular sin código de país: 9XXXXXXXX
        if (strlen($solo) === 9) {
            return '+593' . $solo;
        }
        return $solo;
    }
}

/**
 * Convierte a UTF-8 solo las cadenas que aún no son UTF-8 válido.
 * La conexión de DataAPI ya aplica charset utf8 sobre tablas latin1,
 * por lo que no debe re-codificarse lo que ya llegó correctamente.
 */
if (!function_exists('normalizar_utf8_deep')) {
    function normalizar_utf8_deep(&$v)
    {
        if (is_array($v)) {
            foreach ($v as &$item) {
                normalizar_utf8_deep($item);
            }
            unset($item);
        } elseif (is_string($v) && preg_match('/./us', $v) !== 1) {
            $v = utf8_encode($v);
        }
    }
}

// ── LISTA DE CONTACTOS AUTORIZADOS (solo lectura, para ERP Locator) ──────────
$app->map('/v1/contactos', function () use ($app) {
    try {
        $body = getBody();
        $bdd = !empty($body['Bdd']) ? $body['Bdd'] : 'ecoparkmining';
        $empCod = isset($body['Emp_Cod']) ? (int) $body['Emp_Cod'] : null;

        $where = [];
        if ($empCod) {
            $where['Emp_Cod'] = $empCod;
        }
        $cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';
        if ($cliente !== '') {
            $where['Cnt_Cli'] = $cliente;
        }

        $api = new DataAPI($bdd);
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? min(500, max(1, (int) $_GET['perPage'])) : 500;

        $paged = $api->listPaged('contacto_notif', $where, 'Cnt_Nom ASC, Cnt_Ape ASC', $page, $perPage);

        $contactos = [];
        foreach ($paged['data'] as $r) {
            $contactos[] = [
                'id' => 'C-' . str_pad((string) $r['Cnt_Cod'], 4, '0', STR_PAD_LEFT),
                'nombres' => $r['Cnt_Nom'],
                'apellidos' => $r['Cnt_Ape'],
                'correo' => $r['Cnt_Cor'],
                'celular' => normalizar_celular_ec($r['Cnt_Cel']),
                'activo' => $r['Cnt_Est'] === 'A',
                'cargo' => $r['Cnt_Car'],
                'area' => $r['Cnt_Are'],
                'empresa' => $r['Cnt_Cli'],
            ];
        }
        normalizar_utf8_deep($contactos);

        echo json_encode([
            'success' => true,
            'data' => $contactos,
            'total' => $paged['total'],
            'page' => $paged['page'],
            'perPage' => $paged['perPage'],
            'pages' => $paged['pages'],
        ]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
})->via('GET', 'POST');
