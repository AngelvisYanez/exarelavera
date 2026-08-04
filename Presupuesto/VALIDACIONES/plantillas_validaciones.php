<?php
/**
 * plantillas_validaciones.php
 * Reglas de negocio y validación de datos para el CRUD de Plantillas Presupuestarias en EXA PPTO.
 */

/**
 * Valida los datos recibidos para la creación o edición de una plantilla.
 *
 * @param array $datos Arreglo de datos recibidos (nombre, descripcion, etc.).
 * @param bool $es_edicion Indica si se está validando para una edición (true) o creación (false).
 * @return array Retorna un arreglo con 'valido' (bool) y 'errores' (array de strings).
 */
function ppto_plantilla_validar_datos($datos, $es_edicion = false) {
    $errores = array();
    
    // Si es edición, el ID debe ser un entero positivo válido
    if ($es_edicion) {
        if (!isset($datos['plt_id']) || filter_var($datos['plt_id'], FILTER_VALIDATE_INT) === false || (int)$datos['plt_id'] <= 0) {
            $errores[] = "El identificador de la plantilla no es válido para la edición.";
        }
    }

    // El nombre de la plantilla es obligatorio, debe ser string y tener longitud mínima
    if (!isset($datos['plt_nombre']) || !is_string($datos['plt_nombre'])) {
        $errores[] = "El nombre de la plantilla es requerido y debe ser un texto válido.";
    } else {
        $nombre = trim($datos['plt_nombre']);
        if (strlen($nombre) < 3) {
            $errores[] = "El nombre de la plantilla debe tener al menos 3 caracteres.";
        }
        if (strlen($nombre) > 150) {
            $errores[] = "El nombre de la plantilla no puede superar los 150 caracteres.";
        }
    }

    // La descripción es opcional, pero si existe debe ser texto y no superar un límite razonable
    if (isset($datos['plt_descripcion']) && !is_string($datos['plt_descripcion'])) {
        $errores[] = "La descripción de la plantilla debe ser un texto válido.";
    } elseif (isset($datos['plt_descripcion']) && strlen(trim($datos['plt_descripcion'])) > 500) {
        $errores[] = "La descripción de la plantilla no puede superar los 500 caracteres.";
    }

    // El estado es opcional, pero si se envía debe ser 'A' (Activo) o 'I' (Inactivo)
    if (isset($datos['plt_estado'])) {
        $estado = strtoupper(trim($datos['plt_estado']));
        if ($estado !== 'A' && $estado !== 'I') {
            $errores[] = "El estado especificado no es válido (valores permitidos: 'A' o 'I').";
        }
    }

    return array(
        'valido' => empty($errores),
        'errores' => $errores
    );
}

/**
 * Valida la consistencia de datos antes de proceder con la duplicación de una plantilla.
 *
 * @param int $id ID de la plantilla origen a duplicar.
 * @param string $nuevo_nombre Nombre que se le asignará a la nueva plantilla.
 * @return array Arreglo con 'valido' (bool) y 'errores' (array de strings).
 */
function ppto_plantilla_validar_duplicacion($id, $nuevo_nombre) {
    $errores = array();

    if (filter_var($id, FILTER_VALIDATE_INT) === false || (int)$id <= 0) {
        $errores[] = "El identificador de la plantilla de origen no es válido.";
    }

    if (!is_string($nuevo_nombre) || strlen(trim($nuevo_nombre)) < 3) {
        $errores[] = "El nuevo nombre para la plantilla duplicada debe tener al menos 3 caracteres.";
    } elseif (strlen(trim($nuevo_nombre)) > 150) {
        $errores[] = "El nombre de la plantilla duplicada no puede superar los 150 caracteres.";
    }

    return array(
        'valido' => empty($errores),
        'errores' => $errores
    );
}
