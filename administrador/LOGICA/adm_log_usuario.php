<?php
if (!class_exists('MysqlDatos', false)) {
    require_once(__DIR__ . '/../../DATA/MysqlDatos.php');
}
if (!class_exists('Class_Log_Conexion_Global', false)) {
    require_once(__DIR__ . '/../../DATA/MysqlConexion.php');
}
if (!class_exists('Class_Log_Datos_Usuarios', false)) {
    require_once(__DIR__ . '/adm_log_usuario_3.0.php');
}
