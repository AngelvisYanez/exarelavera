<?php
require_once __DIR__ . '/../../classes/DataAPI.php';

if (!function_exists('normalizar_celular_ec')) {
    function normalizar_celular_ec($celular)
    {
        $solo = preg_replace('/\D/', '', (string) $celular);
        if ($solo === '') {
            return '';
        }
        if (substr($solo, 0, 3) === '593' && strlen($solo) === 13) {
            return '+' . $solo;
        }
        if (strlen($solo) === 10 && substr($solo, 0, 1) === '0') {
            return '+593' . substr($solo, 1);
        }
        if (strlen($solo) === 9) {
            return '+593' . $solo;
        }
        return $solo;
    }
}

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

// ── LISTA DE CHOFERES POR PLANTA (solo lectura, para ERP Locator) ────────────
$handlerChoferes = function () use ($app) {
    try {
        $body = getBody();
        $bdd = !empty($body['Bdd']) ? $body['Bdd'] : 'ecoparkmining';
        $api = new DataAPI($bdd);

        $conditions = ["1=1"];

        // Filtro por planta ID o nombre
        $plaCod = isset($_GET['pla_cod']) ? (int)$_GET['pla_cod'] : (isset($_GET['planta_id']) ? (int)$_GET['planta_id'] : 0);
        if ($plaCod > 0) {
            $conditions[] = "mc.Pla_Cod = " . $plaCod;
        }
        $plantaNom = isset($_GET['planta']) ? trim($_GET['planta']) : '';
        if ($plantaNom !== '') {
            $conditions[] = "pl.Pla_Nom LIKE " . $api->escape('%' . $plantaNom . '%');
        }

        // Filtro por estado
        $estado = isset($_GET['estado']) ? strtoupper(trim($_GET['estado'])) : (isset($_GET['activo']) ? ($_GET['activo'] === 'false' || $_GET['activo'] === '0' ? 'I' : 'A') : 'A');
        if ($estado !== 'ALL' && in_array($estado, ['A', 'I'], true)) {
            $conditions[] = "c.Cho_Est = " . $api->escape($estado);
        }

        // Búsqueda general
        $search = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['buscar']) ? trim($_GET['buscar']) : '');
        if ($search !== '') {
            $sEsc = $api->escape('%' . $search . '%');
            $conditions[] = "(p.Prs_Nom LIKE $sEsc OR p.Prs_Ape LIKE $sEsc OR p.Prs_Ced LIKE $sEsc OR c.Cho_Tel LIKE $sEsc OR c.Cho_Tli LIKE $sEsc OR pl.Pla_Nom LIKE $sEsc)";
        }

        $whereSql = implode(" AND ", $conditions);

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? min(500, max(1, (int)$_GET['perPage'])) : 100;
        $offset = ($page - 1) * $perPage;

        $countSql = "
            SELECT COUNT(*)
            FROM chofer c
            INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
            LEFT JOIN manifiesto_chofer mc ON mc.Cho_Cod = c.Cho_Cod
            LEFT JOIN manifiesto_plantas pl ON pl.Pla_Cod = mc.Pla_Cod
            WHERE $whereSql
        ";
        $total = (int)$api->queryScalar($countSql);
        $pages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;

        $dataSql = "
            SELECT 
                c.Cho_Cod,
                c.Prs_Cod,
                p.Prs_Ced,
                p.Prs_Nom,
                p.Prs_Ape,
                p.Prs_Cel,
                p.Prs_Cor,
                c.Cho_Tli,
                c.Cho_Tel,
                c.Cho_Cli,
                c.Cho_Tsa,
                c.Cho_Est,
                c.Cho_Cor,
                c.Cho_Nli,
                c.Cho_Car,
                pl.Pla_Cod,
                pl.Pla_Nom,
                pl.Pla_Dir,
                pl.Pla_Rut
            FROM chofer c
            INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
            LEFT JOIN manifiesto_chofer mc ON mc.Cho_Cod = c.Cho_Cod
            LEFT JOIN manifiesto_plantas pl ON pl.Pla_Cod = mc.Pla_Cod
            WHERE $whereSql
            ORDER BY pl.Pla_Nom ASC, p.Prs_Nom ASC, p.Prs_Ape ASC
            LIMIT $offset, $perPage
        ";
        $rows = $api->query($dataSql);

        $choferes = [];
        foreach ($rows as $r) {
            $nom = trim((string)$r['Prs_Nom']);
            $ape = trim((string)$r['Prs_Ape']);
            $choferes[] = [
                'id' => (int)$r['Cho_Cod'],
                'codigo' => 'CHF-' . str_pad((string)$r['Cho_Cod'], 4, '0', STR_PAD_LEFT),
                'cedula' => trim((string)$r['Prs_Ced']),
                'nombres' => $nom,
                'apellidos' => $ape,
                'nombre_completo' => trim($nom . ' ' . $ape),
                'celular' => normalizar_celular_ec(!empty($r['Cho_Tel']) ? $r['Cho_Tel'] : $r['Prs_Cel']),
                'correo' => !empty($r['Cho_Cor']) ? trim((string)$r['Cho_Cor']) : trim((string)$r['Prs_Cor']),
                'tipo_sangre' => trim((string)$r['Cho_Tsa']),
                'licencia' => [
                    'tipo' => trim((string)$r['Cho_Tli']),
                    'numero' => trim((string)$r['Cho_Nli']),
                    'vencimiento' => !empty($r['Cho_Cli']) && $r['Cho_Cli'] !== '0000-00-00' ? $r['Cho_Cli'] : null,
                ],
                'activo' => $r['Cho_Est'] === 'A',
                'estado' => $r['Cho_Est'],
                'planta' => !empty($r['Pla_Cod']) ? [
                    'id' => (int)$r['Pla_Cod'],
                    'codigo' => 'PLA-' . str_pad((string)$r['Pla_Cod'], 3, '0', STR_PAD_LEFT),
                    'nombre' => trim((string)$r['Pla_Nom']),
                    'ruta' => trim((string)$r['Pla_Rut']),
                    'direccion' => trim((string)$r['Pla_Dir']),
                ] : null,
            ];
        }
        normalizar_utf8_deep($choferes);

        $app->response->headers->set('Content-Type', 'application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $choferes,
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

$app->map('/v1/choferes', $handlerChoferes)->via('GET', 'POST');
$app->map('/v1/choferes/', $handlerChoferes)->via('GET', 'POST');
$app->map('/choferes', $handlerChoferes)->via('GET', 'POST');
$app->map('/choferes/', $handlerChoferes)->via('GET', 'POST');
