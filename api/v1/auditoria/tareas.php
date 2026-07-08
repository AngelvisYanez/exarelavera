<?php

require_once __DIR__ . '/../../../classes/Tarea.php';
require_once __DIR__ . '/../../../auditoria/LOGICA/aud_log_dashboard_tareas_1.0.php';

$app->post('/v1/auditoria/tareas/obtener', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->obtener($body);
});

$app->post('/v1/auditoria/tareas/crear', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->crear($body);
});

$app->post('/v1/auditoria/tareas/modificar', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->modificar($body);
});

$app->post('/v1/auditoria/tareas/eliminar', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->eliminar($body);
});

$app->post('/v1/auditoria/tareas/obtener-por-id', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->obtenerPorId($body);
});

$app->post('/v1/auditoria/tareas/obtener-empleados', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->obtenerEmpleados($body);
});

$app->post('/v1/auditoria/tareas/asignar', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->asignar($body);
});

$app->post('/v1/auditoria/tareas/eliminar-asignacion', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->eliminarAsignacion($body);
});

$app->post('/v1/auditoria/tareas/listar-asignaciones', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->listarAsignaciones($body);
});

$app->post('/v1/auditoria/tareas/guardar-avance', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->guardarAvance($body);
});

$app->post('/v1/auditoria/tareas/obtener-avances', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->obtenerAvances($body);
});

$app->post('/v1/auditoria/tareas/obtener-mi-avance', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->obtenerMiAvance($body);
});

$app->post('/v1/auditoria/tareas/indicadores', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->indicadores($body);
});

$app->post('/v1/auditoria/tareas/tareas-atencion', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->tareasAtencion($body);
});

$app->post('/v1/auditoria/tareas/metricas-rendimiento', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->metricasRendimiento($body);
});

$app->post('/v1/auditoria/tareas/mis-tareas', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->misTareas($body);
});

$app->post('/v1/auditoria/tareas/listar-adjuntos', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->listarAdjuntos($body);
});

$app->post('/v1/auditoria/tareas/reporte', function () {
    $body = getBody();
    $obBD_conexion = new Class_Log_Conexion_Aud_Tareas($body['Bdd']);
    $obBD_con1 = new Class_Log_Datos_Aud_Tareas;
    $api = new TareaClass($obBD_conexion, $obBD_con1);
    $api->reporteTareas($body);
});
