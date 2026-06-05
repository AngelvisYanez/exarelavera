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
?>