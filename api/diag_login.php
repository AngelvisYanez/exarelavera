<?php
require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';
require_once __DIR__ . '/../Librerias/procedimientos/almacenados_standar.php';

$con = new MysqlConexion('exa_master');
$datos = new MysqlDatos();

$response = [];

// 1. Bases de datos en el servidor MySQL
$response['databases'] = $datos->getArrayConsultaSql("SHOW DATABASES", $con);

// 2. Tablas en exa_master
$response['master_tables'] = $datos->getArrayConsultaSql("SHOW TABLES FROM exa_master", $con);

// 3. Estructura de tabla usuarios en exa_master
$response['usuarios_columns'] = $datos->getArrayConsultaSql("DESCRIBE exa_master.usuarios", $con);

// 4. Muestra de 20 usuarios en exa_master
$response['sample_usuarios'] = $datos->getArrayConsultaSql("SELECT * FROM exa_master.usuarios LIMIT 25", $con);

// 5. Muestra de empresas en exa_master
$response['sample_empresas'] = $datos->getArrayConsultaSql("SELECT * FROM exa_master.empresas LIMIT 25", $con);

// 6. Buscar en todas las empresas
$response['all_empresas'] = $datos->getArrayConsultaSql("SELECT Emp_Cod, Emp_Nom, Emp_Cor, Emp_Ruc, Emp_Est FROM exa_master.empresas", $con);

// 7. Buscar si hay otra base de datos de usuarios o si se llama de otra forma
utf8_encode_deep($response);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
