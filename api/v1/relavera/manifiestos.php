<?php
require_once __DIR__ . '/../../../classes/Manifiesto.php';

function initManiSession($body): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['Ses_Emp_Cod'] = $body['Emp_Cod'] ?? 0;
    $_SESSION['Ses_Suc_Cod'] = $body['Suc_Cod'] ?? 0;
    $_SESSION['Ses_Usu_Cod'] = $body['Usu_Cod'] ?? 0;
}

$app->post('/v1/manifiestos/obtener', function () {
    $body = getBody();
    initManiSession($body);
    $obBD_conexion = new Class_Log_Conexion_Mani($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Mani;
    $manifiesto_api = new ManifiestoClass($obBD_conexion, $obBD_con1);
    $manifiesto_api->getManifiestos($body);
});

$app->post('/v1/manifiestos/obtener-detalle', function () {
    $body = getBody();
    initManiSession($body);
    $obBD_conexion = new Class_Log_Conexion_Mani($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Mani;
    $manifiesto_api = new ManifiestoClass($obBD_conexion, $obBD_con1);
    $manifiesto_api->getManifiesto($body);
});
