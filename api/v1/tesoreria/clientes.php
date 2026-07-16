<?php
    require_once(__DIR__ .'/../../../classes/Cliente.php');

    require_once(__DIR__.'/../../../tesoreria/LOGICA/tes_log_cliente.php');


    $app->post('/v1/clientes/crear', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Cli($body['Bdd']);

        $obBD_con1 =  new Class_Log_Datos_Cli;
        $cliente_api = new ClienteClass($obBD_conexion, $obBD_con1);
        $cliente_api->setCliente($body);
    });

    $app->post('/v1/clientes/obtener', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Cli($body['Bdd']);

        $obBD_con1 =  new Class_Log_Datos_Cli;
        $cliente_api = new ClienteClass($obBD_conexion, $obBD_con1);
        $cliente_api->getClientes($body);
    });

    $app->post('/v1/clientes/modificar', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Cli($body['Bdd']);

        $obBD_con1 =  new Class_Log_Datos_Cli;
        $cliente_api = new ClienteClass($obBD_conexion, $obBD_con1);
        $cliente_api->updateCliente($body);
    });

    $app->post('/v1/clientes/eliminar', function () {
        $body = getBody();
        require_once(__DIR__ . '/../../../classes/DataAPI.php');
        try {
            $api = new DataAPI($body['Bdd']);
            $cliCod = $body['Cli_Cod'] ?? null;
            if (!$cliCod) {
                echo json_encode(['success' => false, 'error' => 'Cli_Cod es requerido']);
                return;
            }
            $api->update('cliente', ['Cli_Est' => 'I'], 'Cli_Cod', $cliCod);
            echo json_encode(['success' => true, 'message' => 'Cliente eliminado exitosamente']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });
?>