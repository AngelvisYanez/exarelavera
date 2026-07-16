<?php
    require_once(__DIR__ .'/../../../classes/Proveedor.php');

    require_once(__DIR__.'/../../../adquisiciones/LOGICA/adq_log_provee.php');

    // $app = new \Slim\Slim();

    $app->post('/v1/proveedores/crear', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Prv($body['Bdd']);
        
        $obBD_con1 =  new Class_Log_Datos_Prv;
        $proveedor_api = new ProveedorClass($obBD_conexion, $obBD_con1);
        $proveedor_api->setProveedor($body);
    });

    $app->post('/v1/proveedores/obtener', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Prv($body['Bdd']);
        $obBD_con1 =  new Class_Log_Datos_Prv;
        $proveedor_api = new ProveedorClass($obBD_conexion, $obBD_con1);
        $proveedor_api->getProveedores($body);
    });
    $app->post('/v1/proveedores/modificar', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Prv($body['Bdd']);
        
        $obBD_con1 =  new Class_Log_Datos_Prv;
        $proveedor_api = new ProveedorClass($obBD_conexion, $obBD_con1);
        $proveedor_api->updateProveedor($body);
    });

    $app->post('/v1/proveedores/eliminar', function () {
        $body = getBody();
        require_once(__DIR__ . '/../../../classes/DataAPI.php');
        try {
            $api = new DataAPI($body['Bdd']);
            $prvCod = $body['Prv_Cod'] ?? null;
            if (!$prvCod) {
                echo json_encode(['success' => false, 'error' => 'Prv_Cod es requerido']);
                return;
            }
            $api->update('proveedor', ['Prv_Est' => 'I'], 'Prv_Cod', $prvCod);
            echo json_encode(['success' => true, 'message' => 'Proveedor eliminado exitosamente']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });
?>