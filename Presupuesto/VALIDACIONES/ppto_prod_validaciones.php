<?php
/**
 * ppto_prod_validaciones.php
 * Reglas de validacion para cierre/reapertura de periodos de produccion (Fase 2A).
 */

/**
 * @param array $params
 * @return array {ok: bool, message: string}
 */
function ppto_prod_validar_cerrar($params) {
    if (empty($params['proy_id'])) {
        return array('ok' => false, 'message' => 'Proyecto requerido.');
    }
    $anio = isset($params['anio']) ? (int)$params['anio'] : 0;
    $mes = isset($params['mes']) ? (int)$params['mes'] : 0;
    if ($anio < 2000 || $anio > 2100) {
        return array('ok' => false, 'message' => 'Anio invalido.');
    }
    if ($mes < 1 || $mes > 12) {
        return array('ok' => false, 'message' => 'Mes invalido.');
    }
    if (!array_key_exists('real_val', $params)) {
        return array('ok' => false, 'message' => 'Debe indicar el valor real al cerrar el periodo.');
    }
    if (!is_numeric($params['real_val'])) {
        return array('ok' => false, 'message' => 'Valor real invalido.');
    }
    return array('ok' => true, 'message' => '');
}

/**
 * @param array $params
 * @param array|null $fila_periodo
 * @return array {ok: bool, message: string}
 */
function ppto_prod_validar_reabrir($params, $fila_periodo = null) {
    if (empty($params['proy_id'])) {
        return array('ok' => false, 'message' => 'Proyecto requerido.');
    }
    $mes = isset($params['mes']) ? (int)$params['mes'] : 0;
    if ($mes < 1 || $mes > 12) {
        return array('ok' => false, 'message' => 'Mes invalido.');
    }
    $motivo = isset($params['motivo']) ? trim($params['motivo']) : '';
    if ($motivo === '') {
        return array('ok' => false, 'message' => 'Motivo de reapertura requerido.');
    }
    if ($fila_periodo !== null) {
        $estado = isset($fila_periodo['prd_estado']) ? $fila_periodo['prd_estado'] : 'sin_dato';
        if ($estado !== 'cerrado') {
            return array('ok' => false, 'message' => 'Solo se puede reabrir un periodo cerrado.');
        }
    }
    return array('ok' => true, 'message' => '');
}

/**
 * @param array $params
 * @param array|null $fila_periodo
 * @return array {ok: bool, message: string}
 */
function ppto_prod_validar_corregir_sync($params, $fila_periodo = null) {
    if (empty($params['proy_id'])) {
        return array('ok' => false, 'message' => 'Proyecto requerido.');
    }
    if (!isset($params['nuevo_real']) || !is_numeric($params['nuevo_real'])) {
        return array('ok' => false, 'message' => 'Valor real invalido.');
    }
    if ($fila_periodo !== null) {
        $estado = isset($fila_periodo['prd_estado']) ? $fila_periodo['prd_estado'] : 'sin_dato';
        if ($estado !== 'cerrado') {
            return array('ok' => false, 'message' => 'Correccion sync solo aplica sobre periodo cerrado.');
        }
    }
    return array('ok' => true, 'message' => '');
}

/**
 * Valida invariante cerrado + fecha_cierre en la misma transaccion.
 *
 * @param string $estado
 * @param string|null $fecha_cierre
 * @return array {ok: bool, message: string}
 */
function ppto_prod_validar_invariante_cierre($estado, $fecha_cierre) {
    if ($estado === 'cerrado' && (empty($fecha_cierre) || $fecha_cierre === '0000-00-00 00:00:00')) {
        return array('ok' => false, 'message' => 'Periodo cerrado requiere prd_fecha_cierre.');
    }
    return array('ok' => true, 'message' => '');
}
