<?php
    require_once(__DIR__ .'/../../../classes/Producto.php');

    require_once(__DIR__.'/../../../facturacion/LOGICA/fac_log_categoria.php');

    // $app = new \Slim\Slim();

    $app->post('/v1/productos/crear', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Tes($body['Bdd']);
        
        $obBD_con1 =  new Class_Log_Datos_Tes;
        $producto_api = new ProductoClass($obBD_conexion, $obBD_con1);
        $producto_api->setProducto($body);
        // echo json_encode($body);
    });

    $app->post('/v1/productos/obtener', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Tes($body['Bdd']);
        $obBD_con1 =  new Class_Log_Datos_Tes;
        $producto_api = new ProductoClass($obBD_conexion, $obBD_con1);
        $producto_api->getProductos($body);
        echo json_encode($body);
    });

    $app->post('/v1/productos/modificar', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Tes($body['Bdd']);
        $obBD_con1 =  new Class_Log_Datos_Tes;
        $producto_api = new ProductoClass($obBD_conexion, $obBD_con1);
        $producto_api->updateProducto($body);
        echo json_encode($body);
    });
?>