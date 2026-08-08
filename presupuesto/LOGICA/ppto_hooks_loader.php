<?php
/**
 * Carga segura de hooks presupuestarios para modulos EXA externos.
 */

function ppto_hooks_cargar() {
    static $loaded = false;
    if ($loaded) {
        return true;
    }
    require_once __DIR__ . '/ppto_schema_logica.php';
    $base = __DIR__;
    $files = array(
        $base . '/ppto_persistencia_logica.php',
        $base . '/ppto_movimiento_integracion.php',
        $base . '/ppto_integracion_motor.php',
        $base . '/ppto_motor_produccion.php',
    );
    foreach ($files as $file) {
        if (file_exists($file)) {
            require_once $file;
        }
    }
    $loaded = true;
    return true;
}

/**
 * @param string $fn
 * @param mixed ...$args
 * @return mixed|null
 */
function ppto_hook_ejecutar($fn) {
    ppto_hooks_cargar();
    if (!function_exists($fn)) {
        return null;
    }
    $args = func_get_args();
    array_shift($args);
    try {
        return call_user_func_array($fn, $args);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Sincroniza produccion real desde Relavera hacia presupuesto.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int|null $Emp_Cod
 * @param int|null $anio
 */
function ppto_sync_relavera_produccion($mysqli, $Pro_Cod, $Emp_Cod = null, $anio = null) {
    ppto_hooks_cargar();
    if (!function_exists('ppto_prod_obtener') || !function_exists('ppto_integracion_produccion_registrar')) {
        return;
    }
    $anio = ($anio !== null) ? (int)$anio : (int)date('Y');
    $Emp_Cod = ($Emp_Cod !== null) ? (int)$Emp_Cod : (function_exists('ppto_resolve_emp_id') ? ppto_resolve_emp_id() : 1);
    for ($mes = 1; $mes <= 12; $mes++) {
        $ext = ppto_prod_obtener($mysqli, $Pro_Cod, $mes, array('Emp_Cod' => $Emp_Cod, 'anio' => $anio));
        if (!empty($ext['valor'])) {
            ppto_integracion_produccion_registrar($mysqli, $Pro_Cod, $mes, (float)$ext['valor'], 'real', $anio, $Emp_Cod);
        }
    }
    if (function_exists('ppto_integracion_proyectar_promedio_siguiente_mes')) {
        ppto_integracion_proyectar_promedio_siguiente_mes($mysqli, $Pro_Cod, $anio, null, $Emp_Cod);
    } elseif (function_exists('ppto_integracion_proyectar_cierre')) {
        ppto_integracion_proyectar_cierre($mysqli, $Pro_Cod, (int)date('n'), $anio);
    }
}
