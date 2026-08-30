<?php
require_once __DIR__ . "/../../../classes/Marca.php";

// Cambiar temporalmente el directorio de trabajo para simular la ejecución desde un FRONT del módulo
// Esto permite que los requires relativos (../../) dentro de la lógica legacy se resuelvan correctamente.
$old_cwd = getcwd();
chdir(__DIR__ . "/../../../facturacion/FRONT");
require_once __DIR__ . "/../../../facturacion/LOGICA/fac_log_marca.php";
chdir($old_cwd);

$app->post("/v1/marcas/crear", function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Mar($body["Bdd"]);

    $obBD_con1 = new Class_Log_Datos_Mar();
    $marca_api = new MarcaClass($obBD_conexion, $obBD_con1);
    $marca_api->setMarca($body);
});

$app->post("/v1/marcas/obtener", function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Mar($body["Bdd"]);
    $obBD_con1 = new Class_Log_Datos_Mar();
    $marca_api = new MarcaClass($obBD_conexion, $obBD_con1);
    $marca_api->getMarcas($body);
});

$app->post("/v1/marcas/modificar", function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Mar($body["Bdd"]);
    $obBD_con1 = new Class_Log_Datos_Mar();
    $marca_api = new MarcaClass($obBD_conexion, $obBD_con1);
    $marca_api->updateMarca($body);
});

$app->post("/v1/marcas/eliminar", function () {
    $body = getBody();
    require_once(__DIR__ . '/../../../classes/DataAPI.php');
    try {
        $api = new DataAPI($body['Bdd']);
        $marCod = $body['Mar_Cod'] ?? null;
        if (!$marCod) {
            echo json_encode(['success' => false, 'error' => 'Mar_Cod es requerido']);
            return;
        }
        $api->delete('marca', 'Mar_Cod', $marCod);
        echo json_encode(['success' => true, 'message' => 'Marca eliminada exitosamente']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});
?>
