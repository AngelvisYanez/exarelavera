<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

// ── Stats generales del dashboard ──
$app->post('/v1/admin/dashboard/stats', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $emp = $body['Emp_Cod'] ?? null;

        $stats = [];

        // Total clientes
        try {
            $where = $emp ? "WHERE Emp_Cod = " . intval($emp) : "";
            $r = $api->queryRow("SELECT COUNT(*) AS total FROM cliente $where");
            $stats['totalClientes'] = (int)($r['total'] ?? 0);
        } catch (\Throwable $e) {
            $stats['totalClientes'] = 0;
        }

        // Total productos
        try {
            $r = $api->queryRow("SELECT COUNT(*) AS total FROM producto WHERE Pro_Est = 'A'");
            $stats['totalProductos'] = (int)($r['total'] ?? 0);
        } catch (\Throwable $e) {
            $stats['totalProductos'] = 0;
        }

        // Total comprobantes/facturas
        try {
            $where = $emp ? "WHERE s.Emp_Cod = " . intval($emp) : "";
            $r = $api->queryRow("
                SELECT COUNT(*) AS total FROM ventas v
                INNER JOIN autorizaci a ON v.Aut_Cod = a.Aut_Cod
                INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
                INNER JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod
                $where
            ");
            $stats['totalFacturas'] = (int)($r['total'] ?? 0);
        } catch (\Throwable $e) {
            $stats['totalFacturas'] = 0;
        }

        // Total proveedores
        try {
            $r = $api->queryRow("SELECT COUNT(*) AS total FROM proveedor");
            $stats['totalProveedores'] = (int)($r['total'] ?? 0);
        } catch (\Throwable $e) {
            $stats['totalProveedores'] = 0;
        }

        // Total manifiestos
        try {
            $r = $api->queryRow("SELECT COUNT(*) AS total FROM manifiesto");
            $stats['totalManifiestos'] = (int)($r['total'] ?? 0);
        } catch (\Throwable $e) {
            $stats['totalManifiestos'] = 0;
        }

        // Total tareas
        try {
            $r = $api->queryRow("SELECT COUNT(*) AS total FROM tarea");
            $stats['totalTareas'] = (int)($r['total'] ?? 0);
        } catch (\Throwable $e) {
            $stats['totalTareas'] = 0;
        }

        echo json_encode(['success' => true, 'data' => $stats]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

// ── Ingresos mensuales del año (para gráfico de área) ──
$app->post('/v1/admin/dashboard/ingresos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $emp = $body['Emp_Cod'] ?? null;
        $anio = date('Y');

        $whereEmp = $emp ? "AND s.Emp_Cod = " . intval($emp) : "";

        $sql = "
            SELECT
                MONTH(v.Vet_Fec) AS mes,
                COUNT(*) AS total_facturas,
                COALESCE(SUM(v.Vet_Val), 0) AS total_ingresos
            FROM ventas v
            INNER JOIN autorizaci a ON v.Aut_Cod = a.Aut_Cod
            INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
            INNER JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod
            WHERE YEAR(v.Vet_Fec) = " . intval($anio) . "
            AND v.Vet_Est = 'A'
            $whereEmp
            GROUP BY MONTH(v.Vet_Fec)
            ORDER BY mes ASC
        ";

        $data = $api->query($sql);
        utf8_encode_deep($data);

        // Completar meses faltantes con 0
        $meses = [];
        for ($i = 1; $i <= 12; $i++) {
            $meses[$i] = ['mes' => $i, 'total_facturas' => 0, 'total_ingresos' => 0];
        }
        foreach ($data as $row) {
            $m = (int)$row['mes'];
            $meses[$m] = [
                'mes' => $m,
                'total_facturas' => (int)$row['total_facturas'],
                'total_ingresos' => (float)$row['total_ingresos'],
            ];
        }

        $nombresMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $result = [];
        foreach ($meses as $m) {
            $result[] = [
                'label' => $nombresMeses[$m['mes'] - 1],
                'mes' => $m['mes'],
                'facturas' => $m['total_facturas'],
                'ingresos' => $m['total_ingresos'],
            ];
        }

        echo json_encode(['success' => true, 'data' => $result]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

// ── Resumen de ingresos del mes actual ──
$app->post('/v1/admin/dashboard/resumen-mes', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $emp = $body['Emp_Cod'] ?? null;
        $anio = date('Y');
        $mes = date('m');

        $whereEmp = $emp ? "AND s.Emp_Cod = " . intval($emp) : "";

        // Mes actual
        $sqlActual = "
            SELECT
                COUNT(*) AS total_facturas,
                COALESCE(SUM(v.Vet_Val), 0) AS total_ingresos
            FROM ventas v
            INNER JOIN autorizaci a ON v.Aut_Cod = a.Aut_Cod
            INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
            INNER JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod
            WHERE YEAR(v.Vet_Fec) = " . intval($anio) . "
            AND MONTH(v.Vet_Fec) = " . intval($mes) . "
            AND v.Vet_Est = 'A'
            $whereEmp
        ";

        // Mes anterior
        $mesAnt = (int)$mes - 1;
        $anioAnt = $anio;
        if ($mesAnt < 1) { $mesAnt = 12; $anioAnt--; }

        $sqlAnterior = "
            SELECT
                COUNT(*) AS total_facturas,
                COALESCE(SUM(v.Vet_Val), 0) AS total_ingresos
            FROM ventas v
            INNER JOIN autorizaci a ON v.Aut_Cod = a.Aut_Cod
            INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
            INNER JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod
            WHERE YEAR(v.Vet_Fec) = " . intval($anioAnt) . "
            AND MONTH(v.Vet_Fec) = " . intval($mesAnt) . "
            AND v.Vet_Est = 'A'
            $whereEmp
        ";

        $actual = $api->queryRow($sqlActual);
        $anterior = $api->queryRow($sqlAnterior);

        $ingresosActual = (float)($actual['total_ingresos'] ?? 0);
        $ingresosAnterior = (float)($anterior['total_ingresos'] ?? 0);
        $variacion = $ingresosAnterior > 0
            ? round(($ingresosActual - $ingresosAnterior) / $ingresosAnterior * 100, 1)
            : 0;

        echo json_encode([
            'success' => true,
            'data' => [
                'ingresos' => $ingresosActual,
                'facturas' => (int)($actual['total_facturas'] ?? 0),
                'variacion' => $variacion,
            ]
        ]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

// ── Productos más vendidos ──
$app->post('/v1/admin/dashboard/productos-populares', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $emp = $body['Emp_Cod'] ?? null;
        $limit = (int)($body['limit'] ?? 5);

        $whereEmp = $emp ? "AND s.Emp_Cod = " . intval($emp) : "";

        $sql = "
            SELECT
                p.Pro_Des AS nombre,
                COUNT(*) AS total_ventas,
                COALESCE(SUM(d.Det_Can * d.Det_Val), 0) AS ingresos
            FROM det_venta d
            INNER JOIN ventas v ON d.Vet_Cod = v.Vet_Cod
            INNER JOIN autorizaci a ON v.Aut_Cod = a.Aut_Cod
            INNER JOIN puntos_imp pto ON a.Pun_Cod = pto.Pun_Cod
            INNER JOIN sucursal s ON pto.Suc_Cod = s.Suc_Cod
            INNER JOIN producto p ON d.Pro_Cod = p.Pro_Cod
            WHERE v.Vet_Est = 'A'
            $whereEmp
            GROUP BY p.Pro_Cod, p.Pro_Des
            ORDER BY total_ventas DESC
            LIMIT " . intval($limit) . "
        ";

        $data = $api->query($sql);
        utf8_encode_deep($data);

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

// ── Distribución de ingresos por tipo de comprobante ──
$app->post('/v1/admin/dashboard/distribucion-ingresos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $emp = $body['Emp_Cod'] ?? null;

        $whereEmp = $emp ? "AND s.Emp_Cod = " . intval($emp) : "";

        $sql = "
            SELECT
                tc.Tic_Des AS nombre,
                COUNT(*) AS total,
                COALESCE(SUM(v.Vet_Val), 0) AS ingresos
            FROM ventas v
            INNER JOIN tipo_compr tc ON v.Tic_Cod = tc.Tic_Cod
            INNER JOIN autorizaci a ON v.Aut_Cod = a.Aut_Cod
            INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
            INNER JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod
            WHERE v.Vet_Est = 'A'
            $whereEmp
            GROUP BY tc.Tic_Cod, tc.Tic_Des
            ORDER BY ingresos DESC
        ";

        $data = $api->query($sql);
        utf8_encode_deep($data);

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});
