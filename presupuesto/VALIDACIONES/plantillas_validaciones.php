<?php
/**
 * plantillas_validaciones.php
 * Reglas de negocio y validacion de datos para el CRUD de Plantillas Presupuestarias en EXA PPTO.
 * Nomenclatura original: plt_id, plt_nombre, plt_descripcion, plt_estado.
 */

/**
 * Normaliza claves de plantilla (acepta legado Plt_* por compatibilidad).
 *
 * @param array $datos
 * @return array
 */
function ppto_plantilla_normalizar_datos($datos) {
    if (!is_array($datos)) {
        return array();
    }
    $out = $datos;
    if (!isset($out['plt_id']) && isset($out['Plt_Cod'])) {
        $out['plt_id'] = $out['Plt_Cod'];
    }
    if (!isset($out['plt_nombre']) && isset($out['Plt_Nom'])) {
        $out['plt_nombre'] = $out['Plt_Nom'];
    }
    if (!isset($out['plt_descripcion']) && isset($out['Plt_Des'])) {
        $out['plt_descripcion'] = $out['Plt_Des'];
    }
    if (!isset($out['plt_estado']) && isset($out['Plt_Est'])) {
        $out['plt_estado'] = $out['Plt_Est'];
    }
    return $out;
}

/**
 * Valida los datos recibidos para la creacion o edicion de una plantilla.
 *
 * @param array $datos Arreglo de datos recibidos (nombre, descripcion, etc.).
 * @param bool $es_edicion Indica si se esta validando para una edicion (true) o creacion (false).
 * @return array Retorna un arreglo con 'valido' (bool) y 'errores' (array de strings).
 */
function ppto_plantilla_validar_datos($datos, $es_edicion = false) {
    $errores = array();
    $datos = ppto_plantilla_normalizar_datos($datos);

    if ($es_edicion) {
        if (!isset($datos['plt_id']) || filter_var($datos['plt_id'], FILTER_VALIDATE_INT) === false || (int)$datos['plt_id'] <= 0) {
            $errores[] = "El identificador de la plantilla no es valido para la edicion.";
        }
    }

    if (!isset($datos['plt_nombre']) || !is_string($datos['plt_nombre'])) {
        $errores[] = "El nombre de la plantilla es requerido y debe ser un texto valido.";
    } else {
        $nombre = trim($datos['plt_nombre']);
        if (strlen($nombre) < 3) {
            $errores[] = "El nombre de la plantilla debe tener al menos 3 caracteres.";
        }
        if (strlen($nombre) > 150) {
            $errores[] = "El nombre de la plantilla no puede superar los 150 caracteres.";
        }
    }

    if (isset($datos['plt_descripcion']) && $datos['plt_descripcion'] !== null && !is_string($datos['plt_descripcion'])) {
        $errores[] = "La descripcion de la plantilla debe ser un texto valido.";
    } elseif (isset($datos['plt_descripcion']) && is_string($datos['plt_descripcion']) && strlen(trim($datos['plt_descripcion'])) > 500) {
        $errores[] = "La descripcion de la plantilla no puede superar los 500 caracteres.";
    }

    if (isset($datos['plt_estado'])) {
        $estado = strtoupper(trim($datos['plt_estado']));
        if ($estado !== 'A' && $estado !== 'I') {
            $errores[] = "El estado especificado no es valido (valores permitidos: 'A' o 'I').";
        }
    }

    return array(
        'valido' => empty($errores),
        'errores' => $errores
    );
}

/**
 * Valida la consistencia de datos antes de proceder con la duplicacion de una plantilla.
 *
 * @param int $id ID de la plantilla origen a duplicar.
 * @param string $nuevo_nombre Nombre que se le asignara a la nueva plantilla.
 * @return array Arreglo con 'valido' (bool) y 'errores' (array de strings).
 */
function ppto_plantilla_validar_duplicacion($id, $nuevo_nombre) {
    $errores = array();

    if (filter_var($id, FILTER_VALIDATE_INT) === false || (int)$id <= 0) {
        $errores[] = "El identificador de la plantilla de origen no es valido.";
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
