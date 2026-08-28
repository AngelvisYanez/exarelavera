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

// ── LISTA DE VEHÍCULOS / VOLQUETAS POR PLANTA (solo lectura, para ERP Locator) ──
$handlerVehiculos = function () use ($app) {
    try {
        $body = getBody();
        $bdd = !empty($body['Bdd']) ? $body['Bdd'] : 'ecoparkmining';
        $api = new DataAPI($bdd);

        $conditions = ["1=1"];

        // Filtro por planta ID o nombre
        $plaCod = isset($_GET['pla_cod']) ? (int)$_GET['pla_cod'] : (isset($_GET['planta_id']) ? (int)$_GET['planta_id'] : 0);
        if ($plaCod > 0) {
            $conditions[] = "mv.Pla_Cod = " . $plaCod;
        }
        $plantaNom = isset($_GET['planta']) ? trim($_GET['planta']) : '';
        if ($plantaNom !== '') {
            $conditions[] = "pl.Pla_Nom LIKE " . $api->escape('%' . $plantaNom . '%');
        }

        // Filtro por estado
        $estado = isset($_GET['estado']) ? strtoupper(trim($_GET['estado'])) : (isset($_GET['activo']) ? ($_GET['activo'] === 'false' || $_GET['activo'] === '0' ? 'I' : 'A') : 'A');
        if ($estado !== 'ALL' && in_array($estado, ['A', 'I'], true)) {
            $conditions[] = "v.Veh_Est = " . $api->escape($estado);
        }

        // Búsqueda general
        $search = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['buscar']) ? trim($_GET['buscar']) : '');
        if ($search !== '') {
            $sEsc = $api->escape('%' . $search . '%');
            $conditions[] = "(v.Veh_Pla LIKE $sEsc OR v.Veh_Mar LIKE $sEsc OR v.Veh_Col LIKE $sEsc OR pl.Pla_Nom LIKE $sEsc)";
        }

        $whereSql = implode(" AND ", $conditions);

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? min(500, max(1, (int)$_GET['perPage'])) : 100;
        $offset = ($page - 1) * $perPage;

        $countSql = "
            SELECT COUNT(*)
            FROM vehiculo v
            LEFT JOIN manifiesto_vehiculo mv ON mv.Veh_Cod = v.Veh_Cod
            LEFT JOIN manifiesto_plantas pl ON pl.Pla_Cod = mv.Pla_Cod
            WHERE $whereSql
        ";
        $total = (int)$api->queryScalar($countSql);
        $pages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;

        $dataSql = "
            SELECT 
                v.Veh_Cod,
                v.Veh_Pla,
                v.Veh_Mar,
                v.Veh_Col,
                v.Veh_Cap,
                v.Veh_Tip,
                v.Veh_Tit,
                v.Veh_Est,
                pl.Pla_Cod,
                pl.Pla_Nom,
                pl.Pla_Dir,
                pl.Pla_Rut
            FROM vehiculo v
            LEFT JOIN manifiesto_vehiculo mv ON mv.Veh_Cod = v.Veh_Cod
            LEFT JOIN manifiesto_plantas pl ON pl.Pla_Cod = mv.Pla_Cod
            WHERE $whereSql
            ORDER BY pl.Pla_Nom ASC, v.Veh_Pla ASC
            LIMIT $offset, $perPage
        ";
        $rows = $api->query($dataSql);

        $vehiculos = [];
        foreach ($rows as $r) {
            $capKg = (float)$r['Veh_Cap'];
            $vehiculos[] = [
                'id' => (int)$r['Veh_Cod'],
                'codigo' => 'VEH-' . str_pad((string)$r['Veh_Cod'], 4, '0', STR_PAD_LEFT),
                'placa' => trim((string)$r['Veh_Pla']),
                'marca' => trim((string)$r['Veh_Mar']),
                'color' => trim((string)$r['Veh_Col']),
                'capacidad_kg' => $capKg,
                'capacidad_toneladas' => round($capKg / 1000, 2),
                'tipo' => trim((string)$r['Veh_Tip']),
                'tipo_nombre' => $r['Veh_Tip'] === 'VM' ? 'Volqueta Minera' : $r['Veh_Tip'],
                'titularidad' => trim((string)$r['Veh_Tit']),
                'activo' => $r['Veh_Est'] === 'A',
                'estado' => $r['Veh_Est'],
                'planta' => !empty($r['Pla_Cod']) ? [
                    'id' => (int)$r['Pla_Cod'],
                    'codigo' => 'PLA-' . str_pad((string)$r['Pla_Cod'], 3, '0', STR_PAD_LEFT),
                    'nombre' => trim((string)$r['Pla_Nom']),
                    'ruta' => trim((string)$r['Pla_Rut']),
                    'direccion' => trim((string)$r['Pla_Dir']),
                ] : null,
            ];
        }
        normalizar_utf8_deep($vehiculos);

        $app->response->headers->set('Content-Type', 'application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $vehiculos,
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

$app->map('/v1/vehiculos', $handlerVehiculos)->via('GET', 'POST');
$app->map('/v1/vehiculos/', $handlerVehiculos)->via('GET', 'POST');
$app->map('/vehiculos', $handlerVehiculos)->via('GET', 'POST');
$app->map('/vehiculos/', $handlerVehiculos)->via('GET', 'POST');
