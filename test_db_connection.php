<?php
// test_db_connection.php

require_once 'Librerias/config.php/debugbar.php';
require_once 'DATA/MysqlConexion.php';

$conexion = new MysqlConexion("master");

if ($conexion->conexion) {
    echo "<h1>Conexión Exitosa</h1>";
    echo "<p>Conectado a la base de datos '{$conexion->BaseDatos}' en el servidor '{$conexion->Servidor}'.</p>";
    $conexion->cerrar();
} else {
    echo "<h1>Error de Conexión</h1>";
    echo "<p>No se pudo conectar a la base de datos.</p>";
    echo "<p>Error: " . $conexion->Error . "</p>";
}
