<?php
    require_once(__DIR__ .'/../../../classes/Cliente.php');

    require_once(__DIR__.'/../../../tesoreria/LOGICA/tes_log_cliente.php');


    $app->post('/v1/clientes/crear', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Cli($body['Bdd']);

        $obBD_con1 =  new Class_Log_Datos_Cli;
        $cliente_api = new ClienteClass($obBD_conexion, $obBD_con1);
        $cliente_api->setCliente($body);
        echo json_encode($body);
    });

    $app->post('/v1/clientes/obtener', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Cli($body['Bdd']);

        $obBD_con1 =  new Class_Log_Datos_Cli;
        $cliente_api = new ClienteClass($obBD_conexion, $obBD_con1);
        $cliente_api->getClientes($body);
        echo json_encode($body);
    });

    $app->post('/v1/clientes/modificar', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Cli($body['Bdd']);

        $obBD_con1 =  new Class_Log_Datos_Cli;
        $cliente_api = new ClienteClass($obBD_conexion, $obBD_con1);
        $cliente_api->updateCliente($body);
        echo json_encode($body);
    });
?>