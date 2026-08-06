<?php
require_once(__DIR__ . '/../../Librerias/operacion.php');
if (!class_exists('Class_Log_Conexion_Gui', false)) {
    class Class_Log_Conexion_Gui extends MysqlConexion {}
}
if (!class_exists('Class_Log_Datos_Gui', false)) {
    class Class_Log_Datos_Gui extends MysqlDatos {}
}
