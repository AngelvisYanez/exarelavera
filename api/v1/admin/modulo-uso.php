<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/admin/modulo-uso', function () use ($app) {
    $body = getBody();
    $bdd = $body['Bdd'] ?? '';
    $emp_cod = $body['Emp_Cod'] ?? '';
    $fecha_desde = $body['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
    $fecha_hasta = $body['fecha_hasta'] ?? date('Y-m-d');
    $ruc_cliente = trim($body['ruc_cliente'] ?? '');

    if (empty($bdd)) {
        $app->response->setStatus(400);
        echo json_encode(['success' => false, 'error' => 'Base de datos no especificada']);
        return;
    }

    try {
        $api = new DataAPI($bdd);
        $datos = $api->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'auditoria' AND TABLE_NAME = 'logs'");
        if (empty($datos)) {
            echo json_encode(['success' => true, 'data' => [
                'porModulo' => [],
                'porUsuario' => [],
                'tendencia' => [],
                'resumen' => ['totalAcciones' => 0, 'totalUsuarios' => 0, 'totalModulos' => 0],
                'mensaje' => 'La tabla de auditoría (auditoria.logs) no existe o no tiene datos'
            ]]);
            return;
        }

        $whereUsuario = '';
        if (!empty($ruc_cliente)) {
            $usersFilter = $api->query(
                "SELECT u.Usu_Cod FROM usuarios u "
                . "INNER JOIN persona p ON u.Prs_Cod = p.Prs_Cod "
                . "WHERE (p.Prs_Ced LIKE " . $api->escape('%' . $ruc_cliente . '%')
                . " OR CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) LIKE " . $api->escape('%' . $ruc_cliente . '%') . ")"
            );
            if (!empty($usersFilter)) {
                $usuCodes = array_column($usersFilter, 'Usu_Cod');
                $whereUsuario = 'AND l.Usu_Cod IN (' . implode(',', array_map('intval', $usuCodes)) . ')';
            } else {
                echo json_encode(['success' => true, 'data' => [
                    'porModulo' => [],
                    'porUsuario' => [],
                    'tendencia' => [],
                    'resumen' => ['totalAcciones' => 0, 'totalUsuarios' => 0, 'totalModulos' => 0]
                ]]);
                return;
            }
        }

        $logsRaw = $api->query(
            "SELECT l.Usu_Cod, l.Pcs_Cod, l.Log_Fec, p.Pcs_Lin, p.Pcs_Det "
            . "FROM auditoria.logs l "
            . "INNER JOIN `{$bdd}`.procesos p ON l.Pcs_Cod = p.Pcs_Cod "
            . "WHERE l.Log_Fec >= " . $api->escape($fecha_desde . ' 00:00:00')
            . " AND l.Log_Fec <= " . $api->escape($fecha_hasta . ' 23:59:59')
            . " $whereUsuario "
            . "ORDER BY l.Log_Fec ASC"
        );

        if (empty($logsRaw)) {
            echo json_encode(['success' => true, 'data' => [
                'porModulo' => [],
                'porUsuario' => [],
                'tendencia' => [],
                'resumen' => ['totalAcciones' => 0, 'totalUsuarios' => 0, 'totalModulos' => 0]
            ]]);
            return;
        }

        $usuariosMap = [];
        $usersData = $api->query(
            "SELECT u.Usu_Cod, p.Prs_Nom, p.Prs_Ape, p.Prs_Ced "
            . "FROM usuarios u INNER JOIN persona p ON u.Prs_Cod = p.Prs_Cod "
            . "WHERE u.Usu_Est = 'A'"
        );
        foreach ($usersData as $u) {
            $usuariosMap[$u['Usu_Cod']] = $u;
        }

        $moduloCounts = [];
        $userModuloCounts = [];
        $dailyCounts = [];
        $totalAcciones = count($logsRaw);

        foreach ($logsRaw as $row) {
            $modNombre = !empty($row['Pcs_Lin']) ? $row['Pcs_Lin'] : (!empty($row['Pcs_Det']) ? $row['Pcs_Det'] : 'Módulo #' . $row['Pcs_Cod']);
            $usuCod = $row['Usu_Cod'];
            $fecha = substr($row['Log_Fec'], 0, 10);

            if (!isset($moduloCounts[$modNombre])) {
                $moduloCounts[$modNombre] = 0;
            }
            $moduloCounts[$modNombre]++;

            if (!isset($userModuloCounts[$usuCod])) {
                $userModuloCounts[$usuCod] = [];
            }
            if (!isset($userModuloCounts[$usuCod][$modNombre])) {
                $userModuloCounts[$usuCod][$modNombre] = 0;
            }
            $userModuloCounts[$usuCod][$modNombre]++;

            if (!isset($dailyCounts[$fecha])) {
                $dailyCounts[$fecha] = ['total' => 0, 'modulos' => []];
            }
            $dailyCounts[$fecha]['total']++;
            if (!isset($dailyCounts[$fecha]['modulos'][$modNombre])) {
                $dailyCounts[$fecha]['modulos'][$modNombre] = 0;
            }
            $dailyCounts[$fecha]['modulos'][$modNombre]++;
        }

        arsort($moduloCounts);
        $totalModulos = count($moduloCounts);
        $porModulo = [];
        $rank = 1;
        foreach (array_slice($moduloCounts, 0, 20) as $nombre => $total) {
            $porModulo[] = [
                'rank' => $rank++,
                'nombre' => $nombre,
                'total' => $total,
                'porcentaje' => round(($total / $totalAcciones) * 100, 1)
            ];
        }

        $porUsuario = [];
        foreach ($userModuloCounts as $usuCod => $modulos) {
            $userInfo = $usuariosMap[$usuCod] ?? ['Prs_Nom' => 'Usuario #' . $usuCod, 'Prs_Ape' => '', 'Prs_Ced' => ''];
            $nombreCompleto = trim($userInfo['Prs_Nom'] . ' ' . $userInfo['Prs_Ape']);
            $modulosArr = [];
            arsort($modulos);
            $totalUser = array_sum($modulos);
            foreach (array_slice($modulos, 0, 10) as $modNombre => $modTotal) {
                $modulosArr[] = [
                    'modulo' => $modNombre,
                    'total' => $modTotal,
                    'porcentaje' => round(($modTotal / $totalUser) * 100, 1)
                ];
            }
            $porUsuario[] = [
                'usuario' => $nombreCompleto,
                'ruc' => $userInfo['Prs_Ced'],
                'total' => $totalUser,
                'modulos' => $modulosArr
            ];
        }
        usort($porUsuario, fn($a, $b) => $b['total'] - $a['total']);

        $tendencia = [];
        $fechaActual = new DateTime($fecha_desde);
        $fechaFin = new DateTime($fecha_hasta);
        while ($fechaActual <= $fechaFin) {
            $fechaStr = $fechaActual->format('Y-m-d');
            $dayData = $dailyCounts[$fechaStr] ?? ['total' => 0, 'modulos' => []];
            $tendencia[] = [
                'fecha' => $fechaStr,
                'total' => $dayData['total'],
                'modulos' => $dayData['modulos']
            ];
            $fechaActual->modify('+1 day');
        }

        echo json_encode(['success' => true, 'data' => [
            'porModulo' => $porModulo,
            'porUsuario' => array_slice($porUsuario, 0, 10),
            'tendencia' => $tendencia,
            'resumen' => [
                'totalAcciones' => $totalAcciones,
                'totalUsuarios' => count($porUsuario),
                'totalModulos' => $totalModulos
            ]
        ]]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});
