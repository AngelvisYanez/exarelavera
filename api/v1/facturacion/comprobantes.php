<?php
require_once __DIR__ . '/../../../DATA/MysqlConexion.php';
require_once __DIR__ . '/../../../DATA/MysqlDatos.php';
require_once __DIR__ . '/../../../classes/FacturacionElectronica.php';

// ── Endpoints de listado ──────────────────────────────────────────────────────

$app->post('/v1/facturacion/comprobantes', function () {
    $body = getBody();
    $obBD_conexion = new MysqlConexion($body['Bdd']);
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($obBD_conexion);
    $api = new FacturacionElectronicaClass($obBD_conexion, $obBD_con1);
    $api->getComprobantes($body);
});

$app->post('/v1/facturacion/retenciones', function () {
    $body = getBody();
    $obBD_conexion = new MysqlConexion($body['Bdd']);
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($obBD_conexion);
    $api = new FacturacionElectronicaClass($obBD_conexion, $obBD_con1);
    $api->getRetenciones($body);
});

$app->post('/v1/facturacion/comprobantes-contables', function () {
    $body = getBody();
    $obBD_conexion = new MysqlConexion($body['Bdd']);
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($obBD_conexion);
    $api = new FacturacionElectronicaClass($obBD_conexion, $obBD_con1);
    $api->getComprobantesContables($body);
});

$app->post('/v1/facturacion/resumen', function () {
    $body = getBody();
    $obBD_conexion = new MysqlConexion($body['Bdd']);
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($obBD_conexion);
    $api = new FacturacionElectronicaClass($obBD_conexion, $obBD_con1);
    $api->getResumen($body);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function jsonOk($data = null) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($code, $msg, $extra = null) {
    while (ob_get_level()) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json');
    $resp = ['status' => false, 'error' => $msg];
    if ($extra) $resp['data'] = $extra;
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

function getVentaDataComprobante($Vet_Cod, $conexion = null) {
    if (!$conexion) {
        $conexion = new MysqlConexion('servicios');
    }
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($conexion);
    $Vet_Cod = (int)$Vet_Cod;
    $sql = "SELECT v.*, s.Emp_Cod, t.Tic_Sri FROM ventas v
        INNER JOIN autorizaci a ON v.Aut_Cod = a.Aut_Cod
        INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
        INNER JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod
        INNER JOIN tipo_compr t ON v.Tic_Cod = t.Tic_Cod
        WHERE v.Vet_Cod = $Vet_Cod AND v.Vet_Est = 'A'";
    return $obBD_con1->getRowConsultaSql($sql, $conexion);
}

function getClaveAccesoFromDb($Vet_Cod, $conexion = null) {
    if (!$conexion) {
        $conexion = new MysqlConexion('servicios');
    }
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($conexion);
    $Vet_Cod = (int)$Vet_Cod;
    $sql = "SELECT v.Vet_Xml FROM ventas v WHERE v.Vet_Cod = $Vet_Cod AND v.Vet_Est = 'A'";
    $row = $obBD_con1->getRowConsultaSql($sql, $conexion);
    return ($row && $row['Vet_Xml']) ? $row['Vet_Xml'] : null;
}

function findXmlFileComprobante($claveAcceso) {
    $base = __DIR__ . '/../../../facturacion/FRONT/';
    $paths = [
        $base . $claveAcceso . '_A.xml',
        $base . $claveAcceso . '.xml',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
}

function getEmpresaXmlDir($empCod) {
    $dir = __DIR__ . '/../../../facturacion/FRONT/' . (int)$empCod . '/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function updateDbAuthorization($Vet_Cod, $numeroAutorizacion, $conexion) {
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($conexion);
    $mysqli = $obBD_con1->getMyCon($conexion);
    $numEsc = $mysqli ? $mysqli->real_escape_string($numeroAutorizacion) : $numeroAutorizacion;
    $sqlUpd = "UPDATE ventas SET Vet_Sri='" . $numEsc . "', Vet_Aut='S' WHERE Vet_Cod=" . (int)$Vet_Cod . " AND (Vet_Aut IS NULL OR Vet_Aut='N')";
    $obBD_con1->grabaru($sqlUpd, $conexion);
}

// ── Descarga de XML y RIDE ──────────────────────────────────────────────────

$app->get('/v1/facturacion/comprobantes/:Vet_Cod/xml', function ($Vet_Cod) {
    $claveAcceso = getClaveAccesoFromDb($Vet_Cod);
    if (!$claveAcceso) return jsonError(404, 'Comprobante no encontrado');
    $file = findXmlFileComprobante($claveAcceso);
    if (!$file) return jsonError(404, 'Archivo XML no encontrado en el servidor');
    $content = file_get_contents($file);
    if ($content === false) return jsonError(500, 'Error al leer el archivo XML');
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $claveAcceso . '.xml"');
    echo $content;
    exit;
});

$app->get('/v1/facturacion/comprobantes/:Vet_Cod/ride', function ($Vet_Cod) {
    $claveAcceso = getClaveAccesoFromDb($Vet_Cod);
    if (!$claveAcceso) return jsonError(404, 'Comprobante no encontrado');
    $file = findXmlFileComprobante($claveAcceso);
    if (!$file) return jsonError(404, 'Archivo XML no encontrado en el servidor');
    require_once __DIR__ . '/../../../WS/libs/RideSRI.php';
    try {
        $ride = new RideSRI();
        $pdfContent = $ride->createRide($file, '', 'S', false);
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $claveAcceso . '.pdf"');
        echo $pdfContent;
        exit;
    } catch (Exception $e) {
        return jsonError(500, 'Error generando RIDE: ' . $e->getMessage());
    }
});

// ── Consultar estado en SRI (sin re-enviar) ─────────────────────────────────

$app->post('/v1/facturacion/comprobantes/:Vet_Cod/estado-sri', function ($Vet_Cod) {
    try {
        $body = getBody();
        $bdd = $body['Bdd'] ?? 'servicios';

        $obBD_conexion = new MysqlConexion($bdd);
        $obBD_con1 = new MysqlDatos;
        $obBD_con1->setConnection($obBD_conexion);

        $venta = getVentaDataComprobante($Vet_Cod, $obBD_conexion);
        if (!$venta || empty($venta['Vet_Xml'])) {
            return jsonError(404, 'Documento no encontrado o sin clave de acceso');
        }

        $claveAcceso = $venta['Vet_Xml'];
        $empCod = $venta['Emp_Cod'];

        $config = $obBD_con1->getRowConsultaSql(
            "SELECT * FROM confi_fact WHERE Emp_Cod=" . (int)$empCod,
            $obBD_conexion
        );

        require_once __DIR__ . '/../../../Librerias/FactElect/FirmaElectronica.php';
        $DocElect = new FirmaElectronica();
        $DocElect->setProduction($config && $config['Cof_Fac'] * 1 == 2);

        $result = $DocElect->consultarEstadoSri($claveAcceso);

        jsonOk([
            'Vet_Cod' => (int)$Vet_Cod,
            'claveAcceso' => $claveAcceso,
            'estado_sri' => $result['estado'] ?? 'DESCONOCIDO',
            'numeroAutorizacion' => $result['numeroAutorizacion'] ?? null,
            'fechaAutorizacion' => $result['fechaAutorizacion'] ?? null,
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? '',
        ]);
    } catch (Exception $e) {
        return jsonError(500, 'Error en consulta SRI: ' . $e->getMessage());
    }
});

// ── Re-autorizar (consultar sin re-enviar, actualiza DB si ya está autorizado) ─

$app->post('/v1/facturacion/comprobantes/:Vet_Cod/re-autorizar', function ($Vet_Cod) {
    try {
        $body = getBody();
        $bdd = $body['Bdd'] ?? 'servicios';

        $obBD_conexion = new MysqlConexion($bdd);
        $obBD_con1 = new MysqlDatos;
        $obBD_con1->setConnection($obBD_conexion);

        $venta = getVentaDataComprobante($Vet_Cod, $obBD_conexion);
        if (!$venta || empty($venta['Vet_Xml'])) {
            return jsonError(404, 'Documento no encontrado o sin clave de acceso');
        }

        $claveAcceso = $venta['Vet_Xml'];
        $xmlAuthorizedPath = getEmpresaXmlDir($venta['Emp_Cod']) . $claveAcceso . '_A.xml';
        $empCod = $venta['Emp_Cod'];

        $config = $obBD_con1->getRowConsultaSql(
            "SELECT * FROM confi_fact WHERE Emp_Cod=" . (int)$empCod,
            $obBD_conexion
        );

        require_once __DIR__ . '/../../../Librerias/FactElect/FirmaElectronica.php';
        $DocElect = new FirmaElectronica();
        $DocElect->setProduction($config && $config['Cof_Fac'] * 1 == 2);
        $DocElect->setFileAutorized($xmlAuthorizedPath);

        $result = $DocElect->autorizarSri($claveAcceso);

        if ($result['success'] === true) {
            updateDbAuthorization($Vet_Cod, $result['numeroAutorizacion'], $obBD_conexion);
            jsonOk([
                'Vet_Cod' => (int)$Vet_Cod,
                'success' => true,
                'numeroAutorizacion' => $result['numeroAutorizacion'],
                'fechaAutorizacion' => $result['fechaAutorizacion'] ?? '',
                'message' => 'Documento autorizado correctamente',
                'estado' => 'AUTORIZADO',
            ]);
        } else {
            jsonOk([
                'Vet_Cod' => (int)$Vet_Cod,
                'success' => false,
                'estado' => $result['estado'] ?? 'NO_AUTORIZADO',
                'message' => $result['message'] ?? 'Error al autorizar en el SRI',
                'informacionAdicional' => $result['informacionAdicional'] ?? '',
                'reintentar' => $result['reintentar'] ?? false,
            ]);
        }
    } catch (Exception $e) {
        return jsonError(500, 'Error en re-autorización: ' . $e->getMessage());
    }
});

// ── Autorización completa (firmar + enviar + autorizar) ─────────────────────

$app->post('/v1/facturacion/comprobantes/:Vet_Cod/autorizar', function ($Vet_Cod) {
    try {
        $body = getBody();
        $bdd = $body['Bdd'] ?? 'servicios';

        $obBD_conexion = new MysqlConexion($bdd);
        $obBD_con1 = new MysqlDatos;
        $obBD_con1->setConnection($obBD_conexion);

        // 1. Get venta data
        $venta = getVentaDataComprobante($Vet_Cod, $obBD_conexion);
        if (!$venta) {
            return jsonError(404, 'Documento no encontrado');
        }

        $empCod = $venta['Emp_Cod'];
        $autCod = $venta['Aut_Cod'];
        $claveAcceso = $venta['Vet_Xml'];

        // 2. If no clave de acceso, generate it
        if (empty($claveAcceso)) {
            $cajFec = $venta['Caj_Fec'] ?? date('Y-m-d');
            $obBD_elect = getFacturaElectClass($obBD_conexion, $empCod);
            $claveAcceso = $obBD_elect->getClaveAcceso($autCod, $cajFec, $venta['Vet_Num'], $obBD_conexion);
            if (empty($claveAcceso)) {
                return jsonError(400, 'No se pudo generar la clave de acceso');
            }
            $mysqli = $obBD_con1->getMyCon($obBD_conexion);
            $claEsc = $mysqli ? $mysqli->real_escape_string($claveAcceso) : $claveAcceso;
            $obBD_con1->grabaru("UPDATE ventas SET Vet_Xml='" . $claEsc . "' WHERE Vet_Cod=" . (int)$Vet_Cod, $obBD_conexion);
            $venta['Vet_Xml'] = $claveAcceso;
        }

        $empDir = getEmpresaXmlDir($empCod);
        $xmlUnsignedPath = $empDir . $claveAcceso . '.xml';
        $xmlSignedPath   = $empDir . $claveAcceso . '_F.xml';
        $xmlAuthorizedPath = $empDir . $claveAcceso . '_A.xml';

        // 3. Check if already authorized
        if (is_readable($xmlAuthorizedPath)) {
            updateDbAuthorization($Vet_Cod, $claveAcceso, $obBD_conexion);
            return jsonOk([
                'success' => true,
                'numeroAutorizacion' => $venta['Vet_Sri'] ?: $claveAcceso,
                'message' => 'El documento ya estaba autorizado',
                'estado' => 'AUTORIZADO',
            ]);
        }

        // 4. Generate XML if not exists
        if (!is_readable($xmlUnsignedPath)) {
            $base = __DIR__ . '/../../../facturacion/FRONT/';
            $altPath = $base . $claveAcceso . '.xml';
            if (is_readable($altPath)) {
                if (!is_dir($empDir)) mkdir($empDir, 0775, true);
                copy($altPath, $xmlUnsignedPath);
            } else {
                $obBD_elect = getFacturaElectClass($obBD_conexion, $empCod);
                $xmlResult = $obBD_elect->createXmlFactura($Vet_Cod, $autCod, $claveAcceso, $obBD_conexion);
                if (!$xmlResult) {
                    return jsonError(500, 'Error al generar el XML del comprobante');
                }
                if (!is_readable($xmlUnsignedPath)) {
                    $altFallback = $base . $claveAcceso . '.xml';
                    if (is_readable($altFallback)) {
                        if (!is_dir($empDir)) mkdir($empDir, 0775, true);
                        copy($altFallback, $xmlUnsignedPath);
                    } else {
                        return jsonError(500, 'El XML fue generado pero no se encontró en el servidor');
                    }
                }
            }
        }

        // 5. Get company's electronic signature
        $llave = $obBD_con1->getRowConsultaSql(
            "SELECT * FROM llave_elect WHERE Lla_Est='A' AND Emp_Cod=" . (int)$empCod,
            $obBD_conexion
        );
        if (!$llave || empty($llave['Lla_Rut']) || empty($llave['Lla_Cla'])) {
            return jsonError(400, 'No se encontró firma electrónica configurada para esta empresa');
        }

        // 6. Get config for production/test mode
        $config = $obBD_con1->getRowConsultaSql(
            "SELECT * FROM confi_fact WHERE Emp_Cod=" . (int)$empCod,
            $obBD_conexion
        );

        // 7. Initialize FirmaElectronica
        require_once __DIR__ . '/../../../Librerias/FactElect/FirmaElectronica.php';
        $DocElect = new FirmaElectronica();
        $DocElect->setProduction($config && $config['Cof_Fac'] * 1 == 2);

        // 8. Path to .p12 file
        $keyPath = getEmpresaXmlDir($empCod) . $llave['Lla_Rut'];
        if (!is_readable($keyPath)) {
            $keyPath = __DIR__ . '/../../../facturacion/FRONT/' . $empCod . '/' . $llave['Lla_Rut'];
            if (!is_readable($keyPath)) {
                $keyPath = __DIR__ . '/../../../facturacion/FRONT/' . $llave['Lla_Rut'];
            }
        }

        // 9. Sign
        $DocElect->setFileSignedPath($xmlSignedPath);
        $doc = $DocElect->sendToSign($xmlUnsignedPath, $keyPath, $llave['Lla_Cla']);
        if (!$doc || $doc['success'] !== true || empty($doc['xml'])) {
            return jsonError(500, 'Error al firmar el documento: ' . ($doc['message'] ?? 'Error desconocido'));
        }

        // 10. Send to SRI
        $DocElect->setFileSignedPath($xmlSignedPath);
        $result = $DocElect->sendToSri($xmlSignedPath);
        if (!$result || $result['success'] !== true) {
            $msg = $result['message'] ?? 'Error al enviar al SRI';
            if (!empty($result['informacionAdicional'])) $msg .= ' - ' . $result['informacionAdicional'];
            return jsonError(502, $msg);
        }

        // 11. Authorize with SRI
        $DocElect->setFileAutorized($xmlAuthorizedPath);
        $aut = $DocElect->autorizarSri($claveAcceso);
        if (!$aut || $aut['success'] !== true) {
            $msg = $aut['message'] ?? 'Error al autorizar en el SRI';
            if (!empty($aut['informacionAdicional'])) $msg .= ' - ' . $aut['informacionAdicional'];
            return jsonError(502, $msg, [
                'estado' => $aut['estado'] ?? 'DESCONOCIDO',
                'reintentar' => $aut['reintentar'] ?? false,
                'claveAcceso' => $claveAcceso,
            ]);
        }

        // 12. Update DB
        $numeroAutorizacion = $aut['numeroAutorizacion'];
        updateDbAuthorization($Vet_Cod, $numeroAutorizacion, $obBD_conexion);

        // 13. Clean up intermediate files
        if (is_readable($xmlUnsignedPath) && $xmlUnsignedPath !== $xmlAuthorizedPath) {
            $baseCheck = __DIR__ . '/../../../facturacion/FRONT/' . $claveAcceso . '.xml';
            if ($xmlUnsignedPath !== $baseCheck) unlink($xmlUnsignedPath);
        }
        if (is_readable($xmlSignedPath)) unlink($xmlSignedPath);

        return jsonOk([
            'success' => true,
            'numeroAutorizacion' => $numeroAutorizacion,
            'fechaAutorizacion' => $aut['fechaAutorizacion'] ?? '',
            'message' => 'Documento autorizado correctamente',
            'estado' => 'AUTORIZADO',
            'claveAcceso' => $claveAcceso,
        ]);
    } catch (Exception $e) {
        return jsonError(500, 'Error en autorización: ' . $e->getMessage());
    }
});

function getFacturaElectClass($conexion, $empCod) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['Ses_Emp_Cod'] = $empCod;
    $oldDir = getcwd();
    chdir(__DIR__ . '/../../../facturacion/LOGICA/');
    require_once 'fac_log_electronica.php';
    chdir($oldDir);
    $obBD_elect = new Class_Log_Datos_Factura_Elect();
    $obBD_elect->setConnection($conexion);
    return $obBD_elect;
}

// ── Helpers for Retenciones ──────────────────────────────────────────────────

function getRetencionData($Ret_Cod, $conexion = null) {
    if (!$conexion) {
        $conexion = new MysqlConexion('servicios');
    }
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($conexion);
    $Ret_Cod = (int)$Ret_Cod;
    $sql = "SELECT r.*, s.Emp_Cod FROM retencion r
        INNER JOIN autorizaci a ON r.Aut_Cod = a.Aut_Cod
        INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
        INNER JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod
        WHERE r.Ret_Cod = $Ret_Cod AND r.Ret_Est = 'A'";
    return $obBD_con1->getRowConsultaSql($sql, $conexion);
}

function findXmlFileRetencion($claveAcceso) {
    $base = __DIR__ . '/../../../facturacion/FRONT/';
    $paths = [
        $base . $claveAcceso . '_A.xml',
        $base . $claveAcceso . '.xml',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
}

function updateRetDbAuthorization($Ret_Cod, $numeroAutorizacion, $conexion) {
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($conexion);
    $mysqli = $obBD_con1->getMyCon($conexion);
    $numEsc = $mysqli ? $mysqli->real_escape_string($numeroAutorizacion) : $numeroAutorizacion;
    $sqlUpd = "UPDATE retencion SET Ret_Sri='" . $numEsc . "', Ret_Aut='S' WHERE Ret_Cod=" . (int)$Ret_Cod . " AND (Ret_Aut IS NULL OR Ret_Aut='N')";
    $obBD_con1->grabaru($sqlUpd, $conexion);
}

// ── Retenciones: Descarga XML ────────────────────────────────────────────────

$app->get('/v1/facturacion/retenciones/:Ret_Cod/xml', function ($Ret_Cod) {
    $obBD_conexion = new MysqlConexion('servicios');
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($obBD_conexion);
    $Ret_Cod = (int)$Ret_Cod;
    $sql = "SELECT r.Ret_Xml FROM retencion r WHERE r.Ret_Cod = $Ret_Cod AND r.Ret_Est = 'A'";
    $row = $obBD_con1->getRowConsultaSql($sql, $obBD_conexion);
    if (!$row || !$row['Ret_Xml']) return jsonError(404, 'Retención no encontrada');
    $claveAcceso = $row['Ret_Xml'];
    $file = findXmlFileRetencion($claveAcceso);
    if (!$file) return jsonError(404, 'Archivo XML no encontrado en el servidor');
    $content = file_get_contents($file);
    if ($content === false) return jsonError(500, 'Error al leer el archivo XML');
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $claveAcceso . '.xml"');
    echo $content;
    exit;
});

// ── Retenciones: Descarga RIDE ───────────────────────────────────────────────

$app->get('/v1/facturacion/retenciones/:Ret_Cod/ride', function ($Ret_Cod) {
    $obBD_conexion = new MysqlConexion('servicios');
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($obBD_conexion);
    $Ret_Cod = (int)$Ret_Cod;
    $sql = "SELECT r.Ret_Xml FROM retencion r WHERE r.Ret_Cod = $Ret_Cod AND r.Ret_Est = 'A'";
    $row = $obBD_con1->getRowConsultaSql($sql, $obBD_conexion);
    if (!$row || !$row['Ret_Xml']) return jsonError(404, 'Retención no encontrada');
    $claveAcceso = $row['Ret_Xml'];
    $file = findXmlFileRetencion($claveAcceso);
    if (!$file) return jsonError(404, 'Archivo XML no encontrado en el servidor');
    require_once __DIR__ . '/../../../WS/libs/RideSRI.php';
    try {
        $ride = new RideSRI();
        $pdfContent = $ride->createRide($file, '', 'S', false);
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $claveAcceso . '.pdf"');
        echo $pdfContent;
        exit;
    } catch (Exception $e) {
        return jsonError(500, 'Error generando RIDE: ' . $e->getMessage());
    }
});

// ── Retenciones: Consultar estado en SRI ─────────────────────────────────────

$app->post('/v1/facturacion/retenciones/:Ret_Cod/estado-sri', function ($Ret_Cod) {
    try {
        $body = getBody();
        $bdd = $body['Bdd'] ?? 'servicios';
        $obBD_conexion = new MysqlConexion($bdd);
        $obBD_con1 = new MysqlDatos;
        $obBD_con1->setConnection($obBD_conexion);

        $retencion = getRetencionData($Ret_Cod, $obBD_conexion);
        if (!$retencion || empty($retencion['Ret_Xml'])) {
            return jsonError(404, 'Retención no encontrada o sin clave de acceso');
        }
        $claveAcceso = $retencion['Ret_Xml'];
        $empCod = $retencion['Emp_Cod'];

        $config = $obBD_con1->getRowConsultaSql(
            "SELECT * FROM confi_fact WHERE Emp_Cod=" . (int)$empCod,
            $obBD_conexion
        );

        require_once __DIR__ . '/../../../Librerias/FactElect/FirmaElectronica.php';
        $DocElect = new FirmaElectronica();
        $DocElect->setProduction($config && $config['Cof_Fac'] * 1 == 2);

        $result = $DocElect->consultarEstadoSri($claveAcceso);

        jsonOk([
            'Ret_Cod' => (int)$Ret_Cod,
            'claveAcceso' => $claveAcceso,
            'estado_sri' => $result['estado'] ?? 'DESCONOCIDO',
            'numeroAutorizacion' => $result['numeroAutorizacion'] ?? null,
            'fechaAutorizacion' => $result['fechaAutorizacion'] ?? null,
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? '',
        ]);
    } catch (Exception $e) {
        return jsonError(500, 'Error en consulta SRI: ' . $e->getMessage());
    }
});

// ── Retenciones: Re-autorizar ────────────────────────────────────────────────

$app->post('/v1/facturacion/retenciones/:Ret_Cod/re-autorizar', function ($Ret_Cod) {
    try {
        $body = getBody();
        $bdd = $body['Bdd'] ?? 'servicios';
        $obBD_conexion = new MysqlConexion($bdd);
        $obBD_con1 = new MysqlDatos;
        $obBD_con1->setConnection($obBD_conexion);

        $retencion = getRetencionData($Ret_Cod, $obBD_conexion);
        if (!$retencion || empty($retencion['Ret_Xml'])) {
            return jsonError(404, 'Retención no encontrada o sin clave de acceso');
        }
        $claveAcceso = $retencion['Ret_Xml'];
        $empCod = $retencion['Emp_Cod'];

        $config = $obBD_con1->getRowConsultaSql(
            "SELECT * FROM confi_fact WHERE Emp_Cod=" . (int)$empCod,
            $obBD_conexion
        );

        require_once __DIR__ . '/../../../Librerias/FactElect/FirmaElectronica.php';
        $DocElect = new FirmaElectronica();
        $DocElect->setProduction($config && $config['Cof_Fac'] * 1 == 2);
        $DocElect->setFileAutorized(getEmpresaXmlDir($empCod) . $claveAcceso . '_A.xml');

        $result = $DocElect->autorizarSri($claveAcceso);

        if ($result['success'] === true) {
            updateRetDbAuthorization($Ret_Cod, $result['numeroAutorizacion'], $obBD_conexion);
            jsonOk([
                'Ret_Cod' => (int)$Ret_Cod,
                'success' => true,
                'numeroAutorizacion' => $result['numeroAutorizacion'],
                'fechaAutorizacion' => $result['fechaAutorizacion'] ?? '',
                'message' => 'Retención autorizada correctamente',
                'estado' => 'AUTORIZADO',
            ]);
        } else {
            jsonOk([
                'Ret_Cod' => (int)$Ret_Cod,
                'success' => false,
                'estado' => $result['estado'] ?? 'NO_AUTORIZADO',
                'message' => $result['message'] ?? 'Error al autorizar en el SRI',
                'informacionAdicional' => $result['informacionAdicional'] ?? '',
                'reintentar' => $result['reintentar'] ?? false,
            ]);
        }
    } catch (Exception $e) {
        return jsonError(500, 'Error en re-autorización: ' . $e->getMessage());
    }
});

// ── Retenciones: Autorización completa (firmar + enviar + autorizar) ─────────

$app->post('/v1/facturacion/retenciones/:Ret_Cod/autorizar', function ($Ret_Cod) {
    try {
        $body = getBody();
        $bdd = $body['Bdd'] ?? 'servicios';
        $obBD_conexion = new MysqlConexion($bdd);
        $obBD_con1 = new MysqlDatos;
        $obBD_con1->setConnection($obBD_conexion);

        $retencion = getRetencionData($Ret_Cod, $obBD_conexion);
        if (!$retencion) {
            return jsonError(404, 'Retención no encontrada');
        }

        $empCod = $retencion['Emp_Cod'];
        $autCod = $retencion['Aut_Cod'];
        $claveAcceso = $retencion['Ret_Xml'];

        // Generate clave de acceso if missing
        if (empty($claveAcceso)) {
            $retFec = $retencion['Ret_Fec'] ?? date('Y-m-d');
            $obBD_ret = getRetencionElectClass($obBD_conexion, $empCod);
            $claveAcceso = $obBD_ret->getClaveAcceso($autCod, $retFec, $retencion['Ret_Num'], $obBD_conexion);
            if (empty($claveAcceso)) {
                return jsonError(400, 'No se pudo generar la clave de acceso');
            }
            $mysqli = $obBD_con1->getMyCon($obBD_conexion);
            $claEsc = $mysqli ? $mysqli->real_escape_string($claveAcceso) : $claveAcceso;
            $obBD_con1->grabaru("UPDATE retencion SET Ret_Xml='" . $claEsc . "' WHERE Ret_Cod=" . (int)$Ret_Cod, $obBD_conexion);
            $retencion['Ret_Xml'] = $claveAcceso;
        }

        $empDir = getEmpresaXmlDir($empCod);
        $xmlUnsignedPath = $empDir . $claveAcceso . '.xml';
        $xmlSignedPath   = $empDir . $claveAcceso . '_F.xml';
        $xmlAuthorizedPath = $empDir . $claveAcceso . '_A.xml';

        // Check if already authorized
        if (is_readable($xmlAuthorizedPath)) {
            updateRetDbAuthorization($Ret_Cod, $claveAcceso, $obBD_conexion);
            return jsonOk([
                'success' => true,
                'numeroAutorizacion' => $retencion['Ret_Sri'] ?: $claveAcceso,
                'message' => 'La retención ya estaba autorizada',
                'estado' => 'AUTORIZADO',
            ]);
        }

        // Generate XML if not exists
        if (!is_readable($xmlUnsignedPath)) {
            $base = __DIR__ . '/../../../facturacion/FRONT/';
            $altPath = $base . $claveAcceso . '.xml';
            if (is_readable($altPath)) {
                if (!is_dir($empDir)) mkdir($empDir, 0775, true);
                copy($altPath, $xmlUnsignedPath);
            } else {
                $obBD_ret = getRetencionElectClass($obBD_conexion, $empCod);
                $xmlResult = $obBD_ret->createXmlRetencion($Ret_Cod, $autCod, $claveAcceso, $obBD_conexion);
                if (!$xmlResult) {
                    return jsonError(500, 'Error al generar el XML de la retención');
                }
                if (!is_readable($xmlUnsignedPath)) {
                    $altFallback = $base . $claveAcceso . '.xml';
                    if (is_readable($altFallback)) {
                        if (!is_dir($empDir)) mkdir($empDir, 0775, true);
                        copy($altFallback, $xmlUnsignedPath);
                    } else {
                        return jsonError(500, 'El XML fue generado pero no se encontró en el servidor');
                    }
                }
            }
        }

        $llave = $obBD_con1->getRowConsultaSql(
            "SELECT * FROM llave_elect WHERE Lla_Est='A' AND Emp_Cod=" . (int)$empCod,
            $obBD_conexion
        );
        if (!$llave || empty($llave['Lla_Rut']) || empty($llave['Lla_Cla'])) {
            return jsonError(400, 'No se encontró firma electrónica configurada para esta empresa');
        }

        $config = $obBD_con1->getRowConsultaSql(
            "SELECT * FROM confi_fact WHERE Emp_Cod=" . (int)$empCod,
            $obBD_conexion
        );

        require_once __DIR__ . '/../../../Librerias/FactElect/FirmaElectronica.php';
        $DocElect = new FirmaElectronica();
        $DocElect->setProduction($config && $config['Cof_Fac'] * 1 == 2);

        $keyPath = getEmpresaXmlDir($empCod) . $llave['Lla_Rut'];
        if (!is_readable($keyPath)) {
            $keyPath = __DIR__ . '/../../../facturacion/FRONT/' . $empCod . '/' . $llave['Lla_Rut'];
            if (!is_readable($keyPath)) {
                $keyPath = __DIR__ . '/../../../facturacion/FRONT/' . $llave['Lla_Rut'];
            }
        }

        $DocElect->setFileSignedPath($xmlSignedPath);
        $doc = $DocElect->sendToSign($xmlUnsignedPath, $keyPath, $llave['Lla_Cla']);
        if (!$doc || $doc['success'] !== true || empty($doc['xml'])) {
            return jsonError(500, 'Error al firmar la retención: ' . ($doc['message'] ?? 'Error desconocido'));
        }

        $DocElect->setFileSignedPath($xmlSignedPath);
        $result = $DocElect->sendToSri($xmlSignedPath);
        if (!$result || $result['success'] !== true) {
            $msg = $result['message'] ?? 'Error al enviar al SRI';
            if (!empty($result['informacionAdicional'])) $msg .= ' - ' . $result['informacionAdicional'];
            return jsonError(502, $msg);
        }

        $DocElect->setFileAutorized($xmlAuthorizedPath);
        $aut = $DocElect->autorizarSri($claveAcceso);
        if (!$aut || $aut['success'] !== true) {
            $msg = $aut['message'] ?? 'Error al autorizar en el SRI';
            if (!empty($aut['informacionAdicional'])) $msg .= ' - ' . $aut['informacionAdicional'];
            return jsonError(502, $msg, [
                'estado' => $aut['estado'] ?? 'DESCONOCIDO',
                'reintentar' => $aut['reintentar'] ?? false,
                'claveAcceso' => $claveAcceso,
            ]);
        }

        $numeroAutorizacion = $aut['numeroAutorizacion'];
        updateRetDbAuthorization($Ret_Cod, $numeroAutorizacion, $obBD_conexion);

        if (is_readable($xmlUnsignedPath) && $xmlUnsignedPath !== $xmlAuthorizedPath) {
            $baseCheck = __DIR__ . '/../../../facturacion/FRONT/' . $claveAcceso . '.xml';
            if ($xmlUnsignedPath !== $baseCheck) unlink($xmlUnsignedPath);
        }
        if (is_readable($xmlSignedPath)) unlink($xmlSignedPath);

        return jsonOk([
            'success' => true,
            'numeroAutorizacion' => $numeroAutorizacion,
            'fechaAutorizacion' => $aut['fechaAutorizacion'] ?? '',
            'message' => 'Retención autorizada correctamente',
            'estado' => 'AUTORIZADO',
            'claveAcceso' => $claveAcceso,
        ]);
    } catch (Exception $e) {
        return jsonError(500, 'Error en autorización de retención: ' . $e->getMessage());
    }
});

function getRetencionElectClass($conexion, $empCod) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['Ses_Emp_Cod'] = $empCod;
    $oldDir = getcwd();
    chdir(__DIR__ . '/../../../facturacion/LOGICA/');
    require_once 'fac_log_electronica.php';
    chdir($oldDir);
    $obBD_elect = new Class_Log_Datos_Retencion_Elect();
    $obBD_elect->setConnection($conexion);
    return $obBD_elect;
}

// ── Sincronización por lote ──────────────────────────────────────────────────

$app->post('/v1/facturacion/sincronizar-lote', function () {
    try {
        $body = getBody();
        $bdd = $body['Bdd'] ?? 'servicios';
        $tipo = $body['tipo'] ?? 'comprobantes';
        $codigos = $body['codigos'] ?? array();

        if (empty($codigos) || !is_array($codigos)) {
            return jsonError(400, 'Debe especificar un array de códigos');
        }

        $obBD_conexion = new MysqlConexion($bdd);
        $obBD_con1 = new MysqlDatos;
        $obBD_con1->setConnection($obBD_conexion);

        require_once __DIR__ . '/../../../Librerias/FactElect/FirmaElectronica.php';

        $resultados = array();
        $errores = 0;

        foreach ($codigos as $codigo) {
            $codigo = (int)$codigo;
            if ($codigo <= 0) continue;

            try {
                if ($tipo === 'comprobantes') {
                    $venta = getVentaDataComprobante($codigo, $obBD_conexion);
                    if (!$venta || empty($venta['Vet_Xml'])) {
                        $resultados[] = ['codigo' => $codigo, 'success' => false, 'message' => 'Documento no encontrado'];
                        $errores++;
                        continue;
                    }
                    $claveAcceso = $venta['Vet_Xml'];
                    $empCod = $venta['Emp_Cod'];

                    $config = $obBD_con1->getRowConsultaSql(
                        "SELECT * FROM confi_fact WHERE Emp_Cod=" . (int)$empCod,
                        $obBD_conexion
                    );

                    $DocElect = new FirmaElectronica();
                    $DocElect->setProduction($config && $config['Cof_Fac'] * 1 == 2);

                    $res = $DocElect->consultarEstadoSri($claveAcceso);

                    if ($res['success'] === true) {
                        updateDbAuthorization($codigo, $res['numeroAutorizacion'], $obBD_conexion);
                    }

                    $resultados[] = [
                        'codigo' => $codigo,
                        'tipo' => 'comprobante',
                        'claveAcceso' => $claveAcceso,
                        'estado' => $res['estado'] ?? 'ERROR',
                        'success' => $res['success'] ?? false,
                        'numeroAutorizacion' => $res['numeroAutorizacion'] ?? null,
                        'message' => $res['message'] ?? '',
                    ];
                } elseif ($tipo === 'retenciones') {
                    $retencion = getRetencionData($codigo, $obBD_conexion);
                    if (!$retencion || empty($retencion['Ret_Xml'])) {
                        $resultados[] = ['codigo' => $codigo, 'success' => false, 'message' => 'Retención no encontrada'];
                        $errores++;
                        continue;
                    }
                    $claveAcceso = $retencion['Ret_Xml'];
                    $empCod = $retencion['Emp_Cod'];

                    $config = $obBD_con1->getRowConsultaSql(
                        "SELECT * FROM confi_fact WHERE Emp_Cod=" . (int)$empCod,
                        $obBD_conexion
                    );

                    $DocElect = new FirmaElectronica();
                    $DocElect->setProduction($config && $config['Cof_Fac'] * 1 == 2);

                    $res = $DocElect->consultarEstadoSri($claveAcceso);

                    if ($res['success'] === true) {
                        updateRetDbAuthorization($codigo, $res['numeroAutorizacion'], $obBD_conexion);
                    }

                    $resultados[] = [
                        'codigo' => $codigo,
                        'tipo' => 'retencion',
                        'claveAcceso' => $claveAcceso,
                        'estado' => $res['estado'] ?? 'ERROR',
                        'success' => $res['success'] ?? false,
                        'numeroAutorizacion' => $res['numeroAutorizacion'] ?? null,
                        'message' => $res['message'] ?? '',
                    ];
                }
            } catch (Exception $e) {
                $resultados[] = ['codigo' => $codigo, 'success' => false, 'message' => $e->getMessage()];
                $errores++;
            }
        }

        jsonOk([
            'total' => count($codigos),
            'procesados' => count($resultados),
            'errores' => $errores,
            'resultados' => $resultados,
        ]);
    } catch (Exception $e) {
        return jsonError(500, 'Error en sincronización por lote: ' . $e->getMessage());
    }
});
