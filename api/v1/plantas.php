<?php
require_once __DIR__ . '/../../classes/DataAPI.php';

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

// ── LISTA DE PLANTAS DE BENEFICIO (solo lectura, para ERP Locator) ──────────
$handlerPlantas = function () use ($app) {
    try {
        $body = getBody();
        $bdd = !empty($body['Bdd']) ? $body['Bdd'] : 'ecoparkmining';
        $api = new DataAPI($bdd);

        $where = [];
        $estado = isset($_GET['estado']) ? strtoupper(trim($_GET['estado'])) : (isset($_GET['activo']) ? ($_GET['activo'] === 'false' || $_GET['activo'] === '0' ? 'I' : 'A') : 'A');
        if ($estado !== 'ALL' && in_array($estado, ['A', 'I'], true)) {
            $where['Pla_Est'] = $estado;
        }

        $search = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['buscar']) ? trim($_GET['buscar']) : '');
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? min(500, max(1, (int)$_GET['perPage'])) : 100;

        if ($search !== '') {
            $sEsc = $api->escape('%' . $search . '%');
            $whereSql = " (Pla_Nom LIKE $sEsc OR Pla_Dir LIKE $sEsc OR Pla_Rut LIKE $sEsc OR Pla_Lic LIKE $sEsc)";
            if (isset($where['Pla_Est'])) {
                $whereSql .= " AND Pla_Est = " . $api->escape($where['Pla_Est']);
            }
            $countSql = "SELECT COUNT(*) FROM manifiesto_plantas WHERE $whereSql";
            $total = (int)$api->queryScalar($countSql);
            $offset = ($page - 1) * $perPage;
            $dataSql = "SELECT * FROM manifiesto_plantas WHERE $whereSql ORDER BY Pla_Nom ASC LIMIT $offset, $perPage";
            $rows = $api->query($dataSql);
            $pages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
        } else {
            $paged = $api->listPaged('manifiesto_plantas', $where, 'Pla_Nom ASC', $page, $perPage);
            $rows = $paged['data'];
            $total = $paged['total'];
            $pages = $paged['pages'];
        }

        $plantas = [];
        foreach ($rows as $r) {
            $plantas[] = [
                'id' => (int)$r['Pla_Cod'],
                'codigo' => 'PLA-' . str_pad((string)$r['Pla_Cod'], 3, '0', STR_PAD_LEFT),
                'nombre' => trim((string)$r['Pla_Nom']),
                'direccion' => trim((string)$r['Pla_Dir']),
                'ruta' => trim((string)$r['Pla_Rut']),
                'licencia' => trim((string)$r['Pla_Lic']),
                'capacidad' => isset($r['Pla_Cap']) ? (float)$r['Pla_Cap'] : 0,
                'coordenadas' => trim((string)$r['Pla_Geo']),
                'activo' => $r['Pla_Est'] === 'A',
                'estado' => $r['Pla_Est'],
            ];
        }
        normalizar_utf8_deep($plantas);

        $app->response->headers->set('Content-Type', 'application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $plantas,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => $pages,
        ]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        $app->response->headers->set('Content-Type', 'application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
};

$app->map('/v1/plantas', $handlerPlantas)->via('GET', 'POST');
$app->map('/v1/plantas/', $handlerPlantas)->via('GET', 'POST');
$app->map('/plantas', $handlerPlantas)->via('GET', 'POST');
$app->map('/plantas/', $handlerPlantas)->via('GET', 'POST');
