<?php
    require_once(__DIR__ .'/../../../classes/Marca.php');
    require_once(__DIR__.'/../../../facturacion/LOGICA/fac_log_marca.php');

    $app->post('/v1/marcas/crear', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Mar($body['Bdd']);
        
        $obBD_con1 =  new Class_Log_Datos_Mar;
        $marca_api = new MarcaClass($obBD_conexion, $obBD_con1);
        $marca_api->setMarca($body);
        // echo json_encode($body);
    });

    $app->post('/v1/marcas/obtener', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Mar($body['Bdd']);
        $obBD_con1 =  new Class_Log_Datos_Mar;
        $marca_api = new MarcaClass($obBD_conexion, $obBD_con1);
        $marca_api->getMarcas($body);
        echo json_encode($body);
    });

    $app->post('/v1/marcas/modificar', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Mar($body['Bdd']);
        $obBD_con1 =  new Class_Log_Datos_Mar;
        $marca_api = new MarcaClass($obBD_conexion, $obBD_con1);
        $marca_api->updateMarca($body);
        echo json_encode($body);
    });
?>