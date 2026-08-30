<?php
    require_once(__DIR__ .'/../../../classes/Categoria.php');

    require_once(__DIR__.'/../../../facturacion/LOGICA/fac_log_categoria.php');

    // $app = new \Slim\Slim();

    $app->post('/v1/categorias/crear', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Tes($body['Bdd']);
        
        $obBD_con1 =  new Class_Log_Datos_Tes;
        $categoria_api = new CategoriaClass($obBD_conexion, $obBD_con1);
        $categoria_api->setCategoria($body);
    });

    $app->post('/v1/categorias/obtener', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Tes($body['Bdd']);
        $obBD_con1 =  new Class_Log_Datos_Tes;
        $categoria_api = new CategoriaClass($obBD_conexion, $obBD_con1);
        $categoria_api->getCategorias($body);
    });

    $app->post('/v1/categorias/obtener-detalles', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Tes($body['Bdd']);
        $obBD_con1 =  new Class_Log_Datos_Tes;
        $categoria_api = new CategoriaClass($obBD_conexion, $obBD_con1);
        $categoria_api->getAllDetalles($body);
    });

    $app->post('/v1/categorias/modificar', function () {
        $body = getBody();
        $obBD_conexion = new Class_Log_Conexion_Tes($body['Bdd']);
        $obBD_con1 =  new Class_Log_Datos_Tes;
        $categoria_api = new CategoriaClass($obBD_conexion, $obBD_con1);
        $categoria_api->updateCategoria($body);
    });

    $app->post('/v1/categorias/eliminar', function () {
        $body = getBody();
        require_once(__DIR__ . '/../../../classes/DataAPI.php');
        try {
            $api = new DataAPI($body['Bdd']);
            $catCod = $body['Cat_Cod'] ?? null;
            if (!$catCod) {
                echo json_encode(['success' => false, 'error' => 'Cat_Cod es requerido']);
                return;
            }
            $api->delete('categoria', 'Cat_Cod', $catCod);
            echo json_encode(['success' => true, 'message' => 'Categoría eliminada exitosamente']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });
?>