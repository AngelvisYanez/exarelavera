<?php
    require '../framework/Slim/Slim.php';
    // require '../framework/Slim/Autoloader.php';
    \Slim\Slim::registerAutoloader();

    $app = new \Slim\Slim(array(
        'debug' => true
    ));
    $app->add(new \Slim\Middleware\ContentTypes());

    function getBody(){
        return json_decode(file_get_contents('php://input'), true);
     }

    require_once(__DIR__.'/v1/tesoreria/clientes.php');
    require_once(__DIR__.'/v1/adquisiciones/proveedores.php');
    require_once(__DIR__.'/v1/inventario/categorias.php');

    $app->run();
    
?>