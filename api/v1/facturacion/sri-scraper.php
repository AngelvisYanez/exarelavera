<?php
/**
 * API de descarga masiva SRI - Endpoints REST.
 *
 * POST   /v1/facturacion/sri-scraper/jobs          → Crear job de descarga
 * GET    /v1/facturacion/sri-scraper/jobs/:id       → Estado del job (polling)
 * DELETE /v1/facturacion/sri-scraper/jobs/:id        → Cancelar job
 * GET    /v1/facturacion/sri-scraper/jobs/:id/files  → Listar archivos descargados
 * GET    /v1/facturacion/sri-scraper/jobs/:id/xml/:clave → Descargar XML
 * GET    /v1/facturacion/sri-scraper/jobs/:id/pdf/:clave → Descargar PDF
 * GET    /v1/facturacion/sri-scraper/jobs            → Historial de jobs
 */

require_once __DIR__ . '/../../../classes/SriScraperManager.php';
require_once __DIR__ . '/../../../classes/SriScraperJob.php';

// ─── Crear job de descarga masiva ──────────────────────────────

$app->post('/v1/facturacion/sri-scraper/jobs', function () {
    try {
        $body = getBody();
        $manager = new SriScraperManager($body['Bdd'] ?? 'servicios');

        $result = $manager->createJob([
            'ruc'               => $body['ruc'] ?? '',
            'clave'             => $body['clave'] ?? '',
            'fecha_desde'       => $body['fecha_desde'] ?? '',
            'fecha_hasta'       => $body['fecha_hasta'] ?? '',
            'tipo_comprobante'  => $body['tipo_comprobante'] ?? 'todos',
            'flow'              => $body['flow'] ?? 'recibidos',
            'Emp_Cod'           => $body['Emp_Cod'] ?? '',
        ]);

        if ($result['success']) {
            jsonOk($result);
        } else {
            jsonError(400, $result['error']);
        }
    } catch (Exception $e) {
        jsonError(500, 'Error creando job: ' . $e->getMessage());
    }
});

// ─── Estado del job (polling) ──────────────────────────────────

$app->get('/v1/facturacion/sri-scraper/jobs/:id', function ($id) {
    try {
        $body = getBody();
        $manager = new SriScraperManager($body['Bdd'] ?? 'servicios');
        $status = $manager->getJobStatus($id);

        if (!$status) {
            return jsonError(404, 'Job no encontrado');
        }

        jsonOk($status);
    } catch (Exception $e) {
        jsonError(500, 'Error consultando job: ' . $e->getMessage());
    }
});

// ─── Cancelar job ──────────────────────────────────────────────

$app->delete('/v1/facturacion/sri-scraper/jobs/:id', function ($id) {
    try {
        $body = getBody();
        $manager = new SriScraperManager($body['Bdd'] ?? 'servicios');
        $cancelled = $manager->cancelJob($id);

        if ($cancelled) {
            jsonOk(['cancelled' => true, 'message' => 'Job cancelado correctamente']);
        } else {
            jsonError(404, 'Job no encontrado o ya finalizado');
        }
    } catch (Exception $e) {
        jsonError(500, 'Error cancelando job: ' . $e->getMessage());
    }
});

// ─── Listar archivos descargados ───────────────────────────────

$app->get('/v1/facturacion/sri-scraper/jobs/:id/files', function ($id) {
    try {
        $body = getBody();
        $manager = new SriScraperManager($body['Bdd'] ?? 'servicios');
        $files = $manager->getJobFiles($id);

        jsonOk($files);
    } catch (Exception $e) {
        jsonError(500, 'Error listando archivos: ' . $e->getMessage());
    }
});

// ─── Descargar XML específico ──────────────────────────────────

$app->get('/v1/facturacion/sri-scraper/jobs/:id/xml/:clave', function ($id, $clave) {
    try {
        $body = getBody();
        $manager = new SriScraperManager($body['Bdd'] ?? 'servicios');
        $filePath = $manager->getJobFilePath($id, $clave, 'xml');

        if (!$filePath || !file_exists($filePath)) {
            return jsonError(404, 'Archivo XML no encontrado');
        }

        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $clave . '.xml"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } catch (Exception $e) {
        jsonError(500, 'Error descargando XML: ' . $e->getMessage());
    }
});

// ─── Descargar PDF específico ──────────────────────────────────

$app->get('/v1/facturacion/sri-scraper/jobs/:id/pdf/:clave', function ($id, $clave) {
    try {
        $body = getBody();
        $manager = new SriScraperManager($body['Bdd'] ?? 'servicios');
        $filePath = $manager->getJobFilePath($id, $clave, 'pdf');

        if (!$filePath || !file_exists($filePath)) {
            return jsonError(404, 'Archivo PDF no encontrado');
        }

        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $clave . '.pdf"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } catch (Exception $e) {
        jsonError(500, 'Error descargando PDF: ' . $e->getMessage());
    }
});

// ─── Historial de jobs ─────────────────────────────────────────

$app->get('/v1/facturacion/sri-scraper/jobs', function () {
    try {
        $body = getBody();
        $manager = new SriScraperManager($body['Bdd'] ?? 'servicios');
        $jobs = $manager->listJobs(
            $body['ruc'] ?? '',
            $body['page'] ?? 1
        );

        jsonOk($jobs);
    } catch (Exception $e) {
        jsonError(500, 'Error listando jobs: ' . $e->getMessage());
    }
});
