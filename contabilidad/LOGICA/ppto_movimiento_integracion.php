<?php
/**
 * ppto_movimiento_integracion.php
 * Capa de Integración para el Módulo de Presupuestos (EXA PPTO).
 * Implementa el punto de entrada unificado para movimientos externos y hooks de los módulos.
 */

include_once(__DIR__ . '/ppto_persistencia_logica.php');
include_once(__DIR__ . '/ppto_motor_logica.php');
include_once(__DIR__ . '/ppto_motor_calculo.php');

if (file_exists(__DIR__ . '/ppto_reajustes_logica.php')) {
    include_once(__DIR__ . '/ppto_reajustes_logica.php');
}

/**
 * Función centralizada única para registrar todo movimiento transaccional externo (comprometido, ejecutado, reverso).
 * Escribe en la bitácora de movimientos externos y actualiza secuencialmente el ledger presupuestario general.
 *
 * @param mysqli $mysqli Objeto de conexión.
 * @param array $params Argumentos requeridos: {id_documento, tipo_doc, modulo, tipo_mov, id_rubro, monto, id_usuario, Emp_Cod, fecha, proy_id}
 * @return bool Retorna true si se registró y ejecutó con éxito, de lo contrario false.
 */
function ppto_movimiento_registrar($mysqli, $params) {
    if (!$mysqli || empty($params)) {
        return false;
    }

    $id_documento = isset($params['id_documento']) ? $params['id_documento'] : null;
    $tipo_doc     = isset($params['tipo_doc']) ? $params['tipo_doc'] : null;
    $modulo       = isset($params['modulo']) ? $params['modulo'] : null;
    $tipo_mov     = isset($params['tipo_mov']) ? $params['tipo_mov'] : null; // 'comprometido' | 'ejecutado' | 'reverso'
    $id_rubro     = isset($params['id_rubro']) ? $params['id_rubro'] : null;
    $monto        = isset($params['monto']) ? (float)$params['monto'] : 0.00;
    $id_usuario   = isset($params['id_usuario']) ? (int)$params['id_usuario'] : 1;
    $Emp_Cod      = isset($params['Emp_Cod']) ? (int)$params['Emp_Cod'] : 1;
    $fecha        = isset($params['fecha']) ? $params['fecha'] : date('Y-m-d');
    $proy_id      = isset($params['proy_id']) ? $params['proy_id'] : null;

    if (empty($id_documento) || empty($tipo_doc) || empty($modulo) || empty($tipo_mov) || $monto <= 0) {
        return false;
    }

    $time = strtotime($fecha);
    if (!$time) {
        $time = time();
        $fecha = date('Y-m-d', $time);
    }
    $anio = (int)date('Y', $time);
    $mes = (int)date('n', $time);

    // 1. Localizar la versión presupuestaria aprobada y activa de la empresa para ese año
    $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'ppe_anio' => $anio));
    if (!$ppe_id) {
        return false; // Sin presupuesto activo, no hay afectación posible
    }

    // 2. Buscar regla de coincidencia secuencial para obtener la partida (ppa_id)
    $regla = ppto_regla_buscar($mysqli, $Emp_Cod, $tipo_doc, $id_documento);
    $ppa_id = 0;
    if ($regla) {
        $ppa_id = (int)$regla['ppa_id'];
    } else {
        // Fallback: Si no se define una regla, podemos recibir la partida directamente en los parámetros
        $ppa_id = isset($params['ppa_id']) ? (int)$params['ppa_id'] : 0;
        if ($ppa_id <= 0) {
            return false; // No se puede registrar un movimiento sin conocer su partida destino
        }
    }

    // 3. Determinar signo de la transacción según la regla
    $signo = '+';
    if ($regla && isset($regla['prg_signo'])) {
        $signo = $regla['prg_signo'];
    }

    // Si es un reverso (anulación), invertimos el signo del movimiento para guardarlo en bitácora
    $pej_estado = 'A';
    if ($tipo_mov === 'reverso') {
        $signo = ($signo === '+') ? '-' : '+';
        $pej_estado = 'I'; // Gatilla que ppto_documento_ejecutar maneje la reversión de ejecución
    }

    // 4. Prevenir duplicación transaccional antes de registrar
    $duplicado = ppto_persistencia_consultar($mysqli, 3, array(
        'pej_tipo_documento' => $tipo_doc,
        'pej_documento_codigo' => $id_documento,
        'pej_signo' => $signo
    ));
    if ($duplicado) {
        return false; // Evitamos duplicidad transaccional
    }

    // 5. Determinar fase presupuestaria para el ledger general
    $pej_fase = 'E'; // Ejecutado por defecto
    if ($tipo_mov === 'comprometido') {
        $pej_fase = 'C';
    } elseif ($tipo_mov === 'reverso') {
        // Buscamos si existe un movimiento histórico previo para anular la misma fase
        $sql_prev = "SELECT mov_tipo_mov FROM pre_movimientos 
                     WHERE mov_tipo_doc = '" . $mysqli->real_escape_string($tipo_doc) . "' 
                       AND mov_doc_id = '" . $mysqli->real_escape_string($id_documento) . "' 
                     ORDER BY mov_id DESC LIMIT 1";
        $res_prev = $mysqli->query($sql_prev);
        if ($res_prev && $row_prev = $res_prev->fetch_assoc()) {
            $pej_fase = ($row_prev['mov_tipo_mov'] === 'comprometido') ? 'C' : 'E';
        }
    }

    // 6. Registrar en la bitácora histórica pre_movimientos
    $clean_proy = $proy_id ? "'" . $mysqli->real_escape_string($proy_id) . "'" : "NULL";
    $clean_rubro = $id_rubro ? "'" . $mysqli->real_escape_string($id_rubro) . "'" : "NULL";

    $sql_mov = "INSERT INTO pre_movimientos (
                    Emp_Cod, ppe_id, proy_id, ppa_id, pdp_rubro,
                    mov_doc_id, mov_tipo_doc, mov_modulo, mov_tipo_mov,
                    mov_monto, mov_signo, mov_mes, mov_anio,
                    mov_fecha_documento, mov_fecha_registro, Usu_Cod
                ) VALUES (
                    $Emp_Cod, $ppe_id, $clean_proy, $ppa_id, $clean_rubro,
                    '" . $mysqli->real_escape_string($id_documento) . "',
                    '" . $mysqli->real_escape_string($tipo_doc) . "',
                    '" . $mysqli->real_escape_string($modulo) . "',
                    '" . $mysqli->real_escape_string($tipo_mov) . "',
                    $monto, '$signo', $mes, $anio,
                    '$fecha', NOW(), $id_usuario
                )";
    $res_mov = $mysqli->query($sql_mov);

    if (!$res_mov) {
        return false;
    }

    // 7. Invocar secuencialmente al motor de ejecución de documentos para actualizar pre_ejecucion
    $ejecutado_ok = ppto_documento_ejecutar($mysqli, array(
        'ppe_id' => $ppe_id,
        'ppa_id' => $ppa_id,
        'Emp_Cod' => $Emp_Cod,
        'pej_mes' => $mes,
        'pej_anio' => $anio,
        'pej_tipo_documento' => $tipo_doc,
        'pej_documento_codigo' => $id_documento,
        'pej_monto' => $monto,
        'pej_signo' => $signo,
        'pej_fecha_documento' => $fecha,
        'Usu_Cod' => $id_usuario,
        'prg_id' => ($regla ? $regla['prg_id'] : null),
        'proy_id' => $proy_id,
        'pej_fase' => $pej_fase,
        'pej_rubro' => $id_rubro
    ));

    return $ejecutado_ok ? true : false;
}

/**
 * Hook universal de integración para ser invocado cuando se inserta o modifica un documento contable/logístico.
 * Evalúa automáticamente las reglas de asignación y gatilla el movimiento presupuestario.
 *
 * @param mysqli $mysqli Objeto de conexión.
 * @param string $tipo_documento Tipo de documento (ej. 'orden_compra', 'liquidacion_nomina', 'factura_compra').
 * @param string $id_documento Código único del documento.
 * @param array $datos_documento Arreglo con datos completos del documento.
 * @return bool Retorna true si se procesó de manera transparente, false si no aplicaba o falló.
 */
function ppto_hook_documento_procesar($mysqli, $tipo_documento, $id_documento, $datos_documento) {
    if (!$mysqli || empty($tipo_documento) || empty($id_documento)) {
        return false;
    }

    $Emp_Cod = isset($datos_documento['Emp_Cod']) ? (int)$datos_documento['Emp_Cod'] : (isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 1);
    
    // 1. Verificar si existe al menos una regla activa para este tipo de documento y empresa
    $reglas = ppto_persistencia_consultar($mysqli, 2, array('Emp_Cod' => $Emp_Cod, 'prg_tipo_documento' => $tipo_documento));
    if (empty($reglas)) {
        return false; // No hay regla de integración configurada para este documento
    }

    // 2. Evaluar reglas de coincidencia secuencial
    $regla_aplicada = ppto_regla_buscar($mysqli, $Emp_Cod, $tipo_documento, $id_documento, $datos_documento);
    if (!$regla_aplicada) {
        return false; // El documento no cumple con los criterios de filtrado de las reglas
    }

    // 3. Extraer el monto de afectación presupuestaria dinámicamente según la regla
    $monto = 0.00;
    $campo_monto = isset($regla_aplicada['prg_campo_monto']) ? $regla_aplicada['prg_campo_monto'] : 'monto';
    
    if (isset($datos_documento[$campo_monto])) {
        $monto = (float)$datos_documento[$campo_monto];
    } elseif (isset($datos_documento['monto'])) {
        $monto = (float)$datos_documento['monto'];
    } elseif (isset($datos_documento['total'])) {
        $monto = (float)$datos_documento['total'];
    }

    if ($monto <= 0) {
        return false;
    }

    // 4. Determinar tipo de movimiento presupuestario ('comprometido' | 'ejecutado' | 'reverso')
    $tipo_mov = 'ejecutado';
    if (isset($datos_documento['tipo_mov'])) {
        $tipo_mov = $datos_documento['tipo_mov'];
    } elseif (isset($datos_documento['estado']) && in_array(strtolower($datos_documento['estado']), array('anulado', 'cancelado', 'reversado'))) {
        $tipo_mov = 'reverso';
    } elseif (in_array($tipo_documento, array('orden_compra', 'pedido_compra', 'contrato_servicio'))) {
        $tipo_mov = 'comprometido'; // Documentos previos de reserva
    }

    $id_usuario = isset($datos_documento['id_usuario']) ? (int)$datos_documento['id_usuario'] : (isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 1);
    $fecha = isset($datos_documento['fecha']) ? $datos_documento['fecha'] : date('Y-m-d');
    $modulo = isset($datos_documento['modulo']) ? $datos_documento['modulo'] : 'integracion';
    $proy_id = isset($datos_documento['proy_id']) ? $datos_documento['proy_id'] : null;
    $id_rubro = isset($datos_documento['id_rubro']) ? $datos_documento['id_rubro'] : null;

    // 5. Invocar al motor unificado de registro de movimientos
    return ppto_movimiento_registrar($mysqli, array(
        'id_documento' => $id_documento,
        'tipo_doc'     => $tipo_documento,
        'modulo'       => $modulo,
        'tipo_mov'     => $tipo_mov,
        'id_rubro'     => $id_rubro,
        'monto'        => $monto,
        'id_usuario'   => $id_usuario,
        'Emp_Cod'       => $Emp_Cod,
        'fecha'        => $fecha,
        'proy_id'      => $proy_id,
        'ppa_id'       => $regla_aplicada['ppa_id']
    ));
}

/**
 * Recalcula de manera integral la disponibilidad disponible de un rubro de proyecto en tiempo real.
 * Considera Presupuesto Inicial + Reajustes + Ejecutados + Comprometidos.
 *
 * @param mysqli $mysqli Conexión activa.
 * @param int $ppe_id ID de versión presupuestaria.
 * @param int $ppa_id ID de partida.
 * @param string|null $proy_id ID de proyecto (opcional).
 * @param string|null $rubro Nombre del rubro (opcional).
 * @param int|null $mes Mes específico (1-12) o nulo para todo el año.
 * @return float Retorna el saldo disponible calculado.
 */
function ppto_vigente_obtener($mysqli, $ppe_id, $ppa_id, $proy_id = null, $rubro = null, $mes = null) {
    $ppe_id = (int)$ppe_id;
    $ppa_id = (int)$ppa_id;
    $mes_consulta = ($mes !== null) ? (int)$mes : 12;

    $monto_inicial = 0.00;
    $reajustes = 0.00;
    $comprometido = 0.00;
    $ejecutado = 0.00;

    if (!empty($proy_id) && !empty($rubro)) {
        // Presupuesto Inicial desde rubros de proyecto
        $clean_proy = $mysqli->real_escape_string($proy_id);
        $clean_rubro = $mysqli->real_escape_string($rubro);

        $sql_proy = "SELECT SUM(pdm.Pdm_PreMensual) AS inicial 
                      FROM pre_proyecto_detalles_mes pdm
                      INNER JOIN pre_proyecto_detalles pd ON pdm.Pdp_Cod = pd.Pdp_Cod
                      WHERE pd.Ppe_Cod = $ppe_id 
                        AND pd.Ppa_Cod = $ppa_id 
                        AND pd.Pro_Cod = '$clean_proy' 
                        AND pd.Pdp_Rubro = '$clean_rubro'
                        AND pdm.Pdm_Mes <= $mes_consulta";
        $res_proy = $mysqli->query($sql_proy);
        if ($res_proy && $row_p = $res_proy->fetch_assoc()) {
            $monto_inicial = (float)$row_p['inicial'];
        }

        // Consolidador de reajustes netos para el proyecto
        if (function_exists('ppto_reajuste_consolidar_proyecto')) {
            $reajustes = ppto_reajuste_consolidar_proyecto($mysqli, $ppe_id, $ppa_id, $proy_id, $rubro, $mes_consulta);
        }
    } else {
        // Presupuesto Inicial estándar para partidas generales de empresa
        $monto_inicial = ppto_persistencia_consultar($mysqli, 5, array('ppe_id' => $ppe_id, 'ppa_id' => $ppa_id, 'mes' => $mes_consulta));
        
        // Consolidador de reajustes netos para partidas generales
        if (function_exists('ppto_reajuste_consolidar_partida')) {
            $reajustes = ppto_reajuste_consolidar_partida($mysqli, $ppe_id, $ppa_id, $mes_consulta);
        }
    }

    // Sumatoria de Comprometido y Ejecutado real en el ledger general de ejecuciones
    $sql_eje = "SELECT 
                    SUM(CASE WHEN Pej_Fase = 'C' AND Pej_Sig = '+' THEN Pej_Mon WHEN Pej_Fase = 'C' AND Pej_Sig = '-' THEN -Pej_Mon ELSE 0 END) AS comprometido,
                    SUM(CASE WHEN Pej_Fase = 'E' AND Pej_Sig = '+' THEN Pej_Mon WHEN Pej_Fase = 'E' AND Pej_Sig = '-' THEN -Pej_Mon ELSE 0 END) AS ejecutado
                 FROM pre_ejecucion
                 WHERE Ppe_Cod = $ppe_id 
                   AND Ppa_Cod = $ppa_id 
                   AND Pej_Mes <= $mes_consulta";

    if (!empty($proy_id) && !empty($rubro)) {
        $sql_eje .= " AND Pro_Cod = '" . $mysqli->real_escape_string($proy_id) . "' AND Pej_Rubro = '" . $mysqli->real_escape_string($rubro) . "'";
    } else {
        $sql_eje .= " AND Pro_Cod IS NULL";
    }

    $res_eje = $mysqli->query($sql_eje);
    if ($res_eje && $row_e = $res_eje->fetch_assoc()) {
        $comprometido = (float)$row_e['comprometido'];
        $ejecutado = (float)$row_e['ejecutado'];
    }

    // Cálculo atómico de la disponibilidad disponible
    $monto_vigente = $monto_inicial + $reajustes;
    $disponible = $monto_vigente - $comprometido - $ejecutado;

    return max(0.00, $disponible);
}

/**
 * Hook para procesar el pago o ejecución efectiva de órdenes de compra del módulo de compras.
 *
 * @param mysqli $mysqli Objeto de conexión.
 * @param string $id_orden Código único de la orden de compra.
 * @param array $datos_orden Parámetros de la orden {monto, id_usuario, Emp_Cod, fecha, proy_id, ppa_id}
 * @return bool Retorna true si se ejecutó correctamente.
 */
function ppto_hook_orden_compra_procesar($mysqli, $id_orden, $datos_orden) {
    return ppto_hook_documento_procesar($mysqli, 'orden_compra', $id_orden, $datos_orden);
}

/**
 * Hook para procesar la reserva de fondos de solicitudes o pedidos de compra previos.
 *
 * @param mysqli $mysqli Objeto de conexión.
 * @param string $id_pedido Código único del pedido de compra.
 * @param array $datos_pedido Parámetros del pedido.
 * @return bool Retorna true si se ejecutó correctamente.
 */
function ppto_hook_pedido_compra_procesar($mysqli, $id_pedido, $datos_pedido) {
    $datos_pedido['tipo_mov'] = 'comprometido';
    return ppto_hook_documento_procesar($mysqli, 'pedido_compra', $id_pedido, $datos_pedido);
}

/**
 * Hook para procesar liquidaciones de nómina provenientes del módulo de RRHH.
 *
 * @param mysqli $mysqli Objeto de conexión.
 * @param string $id_liquidacion Código único de la liquidación de nómina.
 * @param array $datos_nomina Parámetros de la nómina.
 * @return bool Retorna true si se ejecutó correctamente.
 */
function ppto_hook_nomina_procesar($mysqli, $id_liquidacion, $datos_nomina) {
    return ppto_hook_documento_procesar($mysqli, 'liquidacion_nomina', $id_liquidacion, $datos_nomina);
}

/**
 * Hook para procesar pagos directos ejecutados desde Tesorería/Bancos.
 *
 * @param mysqli $mysqli Objeto de conexión.
 * @param string $id_pago Código único del comprobante o egreso.
 * @param array $datos_pago Parámetros del pago.
 * @return bool Retorna true si se ejecutó correctamente.
 */
function ppto_hook_tesoreria_pago_procesar($mysqli, $id_pago, $datos_pago) {
    return ppto_hook_documento_procesar($mysqli, 'pago_tesoreria', $id_pago, $datos_pago);
}

/**
 * Hook para reversar o anular cualquier documento contabilizado previamente.
 *
 * @param mysqli $mysqli Objeto de conexión.
 * @param string $tipo_documento Tipo de documento a reversar.
 * @param string $id_documento Código del documento a anular.
 * @param array $datos_reverso Parámetros del reverso.
 * @return bool Retorna true si el reverso se efectuó con éxito.
 */
function ppto_hook_documento_reversar($mysqli, $tipo_documento, $id_documento, $datos_reverso) {
    $datos_reverso['tipo_mov'] = 'reverso';
    return ppto_hook_documento_procesar($mysqli, $tipo_documento, $id_documento, $datos_reverso);
}
