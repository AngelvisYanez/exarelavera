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
 * @param array $params Argumentos requeridos: {id_documento, tipo_doc, modulo, tipo_mov, id_rubro, monto, id_usuario, emp_id, fecha, proy_id}
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
    $emp_id       = isset($params['emp_id']) ? (int)$params['emp_id'] : 1;
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
    $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('emp_id' => $emp_id, 'ppe_anio' => $anio));
    if (!$ppe_id) {
        return false; // Sin presupuesto activo, no hay afectación posible
    }

    // 2. Buscar regla de coincidencia secuencial para obtener la partida (ppa_id)
    $regla = ppto_regla_buscar($mysqli, $emp_id, $tipo_doc, $id_documento);
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
        $sql_prev = "SELECT mov_tipo_mov FROM exa_ppto_movimientos 
                     WHERE mov_tipo_doc = '" . $mysqli->real_escape_string($tipo_doc) . "' 
                       AND mov_doc_id = '" . $mysqli->real_escape_string($id_documento) . "' 
                     ORDER BY mov_id DESC LIMIT 1";
        $res_prev = $mysqli->query($sql_prev);
        if ($res_prev && $row_prev = $res_prev->fetch_assoc()) {
            $pej_fase = ($row_prev['mov_tipo_mov'] === 'comprometido') ? 'C' : 'E';
        }
    }

    // 6. Registrar en la bitácora histórica exa_ppto_movimientos
    $clean_proy = $proy_id ? "'" . $mysqli->real_escape_string($proy_id) . "'" : "NULL";
    $clean_rubro = $id_rubro ? "'" . $mysqli->real_escape_string($id_rubro) . "'" : "NULL";

    $sql_mov = "INSERT INTO exa_ppto_movimientos (
                    emp_id, ppe_id, proy_id, ppa_id, pdp_rubro,
                    mov_doc_id, mov_tipo_doc, mov_modulo, mov_tipo_mov,
                    mov_monto, mov_signo, mov_mes, mov_anio,
                    mov_fecha_documento, mov_fecha_registro, usu_id
                ) VALUES (
                    $emp_id, $ppe_id, $clean_proy, $ppa_id, $clean_rubro,
                    '" . $mysqli->real_escape_string($id_documento) . "',
                    '" . $mysqli->real_escape_string($tipo_doc) . "',
                    '" . $mysqli->real_escape_string($modulo) . "',
                    '" . $mysqli->real_escape_string($tipo_mov) . "',
                    $monto, '$signo', $mes, $anio,
                    '" . $mysqli->real_escape_string($fecha) . "', NOW(), $id_usuario
                )";

    $res_mov = $mysqli->query($sql_mov);
    if (!$res_mov) {
        return false;
    }

    // 7. Invocar secuencialmente al motor de ejecución de documentos para actualizar exa_ppto_ejecuciones
    return ppto_documento_ejecutar(
        $mysqli,
        $emp_id,
        $id_usuario,
        $tipo_doc,
        $id_documento,
        $pej_estado,
        $fecha,
        $monto,
        isset($params['suc_id']) ? $params['suc_id'] : null,
        isset($params['dep_id']) ? $params['dep_id'] : null,
        $proy_id,
        $pej_fase,
        $id_rubro
    );
}

/**
 * HOOK COMPRAS: Registra comprometidos por orden de compra o reversa en caso de anulación.
 * Cumple con los requerimientos específicos de la Fase 6 de compras.
 *
 * @param mixed $arg1 mysqli|string Conexión o ID de orden.
 * @param mixed $arg2 string ID de orden o Evento.
 * @param mixed $arg3 string Evento (opcional si arg1 es la conexión).
 * @return array {status: ok|error, message, id_movimiento}
 */
function ppto_hook_compras($arg1, $arg2 = null, $arg3 = null) {
    global $mysqli_conn, $mysqli;

    // Firma flexible para soportar tanto ppto_hook_compras($id_orden, $evento) como ppto_hook_compras($mysqli, $id_orden, $evento)
    if ($arg1 instanceof mysqli) {
        $mysqli_obj = $arg1;
        $id_orden   = $arg2;
        $evento     = $arg3;
    } else {
        $mysqli_obj = isset($mysqli_conn) ? $mysqli_conn : (isset($mysqli) ? $mysqli : null);
        $id_orden   = $arg1;
        $evento     = $arg2;
    }

    if (!$mysqli_obj || empty($id_orden) || empty($evento)) {
        return array('status' => 'error', 'message' => 'Parámetros insuficientes o conexión de base de datos no disponible.', 'id_movimiento' => null);
    }

    $id_clean = $mysqli_obj->real_escape_string($id_orden);

    // 1. OBTENER DATOS DESDE LA TABLA DEL MÓDULO EXTERNO
    $sql_ext = "SELECT id_orden, id_empresa, id_proyecto, id_rubro, monto_total, estado, id_usuario, fecha 
                FROM exa_compras_ordenes 
                WHERE id_orden = '$id_clean' LIMIT 1";
    
    $res_ext = $mysqli_obj->query($sql_ext);
    if (!$res_ext || $res_ext->num_rows === 0) {
        return array('status' => 'error', 'message' => 'La orden de compra no existe en la base de datos.', 'id_movimiento' => null);
    }
    
    $row = $res_ext->fetch_assoc();

    // Mapeo exacto
    $monto       = (float)$row['monto_total'];
    $rubro       = $row['id_rubro'];
    $id_proyecto = $row['id_proyecto'];
    $id_empresa  = (int)$row['id_empresa'];
    $id_usuario  = (int)$row['id_usuario'];
    $fecha       = $row['fecha'];

    // Determinar tipo_mov a partir del evento
    $tipo_mov = '';
    if ($evento === 'crear' || $evento === 'aprobar') {
        $tipo_mov = 'comprometido';
    } elseif ($evento === 'anular') {
        $tipo_mov = 'reverso';
    } else {
        return array('status' => 'error', 'message' => "Evento '$evento' no soportado.", 'id_movimiento' => null);
    }

    $time = strtotime($fecha);
    $anio = $time ? (int)date('Y', $time) : (int)date('Y');
    $mes  = $time ? (int)date('n', $time) : (int)date('n');

    // Localizar versión de presupuesto activa
    $ppe_id = ppto_persistencia_consultar($mysqli_obj, 1, array('emp_id' => $id_empresa, 'ppe_anio' => $anio));
    if (!$ppe_id) {
        return array('status' => 'error', 'message' => "No existe una versión de presupuesto activa para la empresa $id_empresa en el año $anio.", 'id_movimiento' => null);
    }

    // Buscar la partida mediante las reglas del presupuesto
    $regla = ppto_regla_buscar($mysqli_obj, $id_empresa, 'orden_compra', $id_orden);
    if (!$regla) {
        return array('status' => 'error', 'message' => 'No se encontró una regla presupuestaria activa vinculada a orden_compra.', 'id_movimiento' => null);
    }
    $ppa_id = (int)$regla['ppa_id'];

    // 2. VALIDACIÓN 1: Existe id_rubro en la versión activa del proyecto
    if (!empty($id_proyecto) && !empty($rubro)) {
        $clean_proy = $mysqli_obj->real_escape_string($id_proyecto);
        $clean_rubro = $mysqli_obj->real_escape_string($rubro);

        $sql_chk = "SELECT pdp_id FROM exa_ppto_proyecto_detalles 
                    WHERE ppe_id = $ppe_id 
                      AND ppa_id = $ppa_id 
                      AND proy_id = '$clean_proy' 
                      AND pdp_rubro = '$clean_rubro' 
                    LIMIT 1";
        $res_chk = $mysqli_obj->query($sql_chk);
        if (!$res_chk || $res_chk->num_rows === 0) {
            return array(
                'status' => 'error',
                'message' => "El rubro '$rubro' no existe en la versión activa del proyecto '$id_proyecto'.",
                'id_movimiento' => null
            );
        }
    }

    // 3. VALIDACIÓN 2: Hay presupuesto disponible (solo para comprometido)
    if ($tipo_mov === 'comprometido') {
        $disponible = 0.00;
        if (!empty($id_proyecto) && !empty($rubro)) {
            // Verificación en la distribución mensual del proyecto
            $clean_proy = $mysqli_obj->real_escape_string($id_proyecto);
            $clean_rubro = $mysqli_obj->real_escape_string($rubro);
            $sql_avail = "SELECT (pdm_presupuesto_mensual - pdm_ejecutado - pdm_comprometido) AS disponible 
                          FROM exa_ppto_proyecto_detalles_mes pdm
                          INNER JOIN exa_ppto_proyecto_detalles pd ON pdm.pdp_id = pd.pdp_id
                          WHERE pd.ppe_id = $ppe_id 
                            AND pd.ppa_id = $ppa_id 
                            AND pd.proy_id = '$clean_proy' 
                            AND pd.pdp_rubro = '$clean_rubro' 
                            AND pdm.pdm_mes = $mes LIMIT 1";
            $res_avail = $mysqli_obj->query($sql_avail);
            if ($res_avail && $row_avail = $res_avail->fetch_assoc()) {
                $disponible = (float)$row_avail['disponible'];
            }
        } else {
            // Verificación en la partida estándar general
            $calc_partida = ppto_motor_calcular_partida($mysqli_obj, $ppa_id, $mes);
            if (isset($calc_partida['disponible'])) {
                $disponible = (float)$calc_partida['disponible'];
            }
        }

        if ($disponible < $monto) {
            return array(
                'status' => 'error',
                'message' => 'Presupuesto insuficiente. Disponible: $' . number_format($disponible, 2) . ', Solicitado: $' . number_format($monto, 2) . '.',
                'id_movimiento' => null
            );
        }
    }

    // 4. VALIDACIÓN 3: No duplicar: mismo id_orden + mismo evento ya registrado
    $sql_dup = "SELECT mov_id FROM exa_ppto_movimientos 
                WHERE mov_tipo_doc = 'orden_compra' 
                  AND mov_doc_id = '$id_clean' 
                  AND mov_tipo_mov = '$tipo_mov' 
                LIMIT 1";
    $res_dup = $mysqli_obj->query($sql_dup);
    if ($res_dup && $res_dup->num_rows > 0) {
        return array(
            'status' => 'error',
            'message' => "El movimiento de la orden '$id_orden' ya se encuentra registrado para la fase '$tipo_mov'.",
            'id_movimiento' => null
        );
    }

    // 5. REGISTRAR MOVIMIENTO
    $params_registrar = array(
        'id_documento' => $id_orden,
        'tipo_doc' => 'orden_compra',
        'modulo' => 'compras',
        'tipo_mov' => $tipo_mov,
        'id_rubro' => $rubro,
        'monto' => $monto,
        'id_usuario' => $id_usuario,
        'emp_id' => $id_empresa,
        'fecha' => $fecha,
        'proy_id' => $id_proyecto,
        'ppa_id' => $ppa_id
    );

    $reg_ok = ppto_movimiento_registrar($mysqli_obj, $params_registrar);
    if ($reg_ok) {
        // Obtener el ID del movimiento recién insertado
        $id_movimiento = null;
        $sql_last = "SELECT mov_id FROM exa_ppto_movimientos 
                     WHERE mov_tipo_doc = 'orden_compra' 
                       AND mov_doc_id = '$id_clean' 
                     ORDER BY mov_id DESC LIMIT 1";
        $res_last = $mysqli_obj->query($sql_last);
        if ($res_last && $row_last = $res_last->fetch_assoc()) {
            $id_movimiento = (int)$row_last['mov_id'];
        }

        return array(
            'status' => 'ok',
            'message' => 'Movimiento de compras registrado exitosamente.',
            'id_movimiento' => $id_movimiento
        );
    } else {
        return array(
            'status' => 'error',
            'message' => 'Error al registrar el movimiento en el motor presupuestario.',
            'id_movimiento' => null
        );
    }
}

/**
 * HOOK TESORERÍA: Registra ejecuciones reales por pagos de facturas o egresos financieros.
 */
function ppto_hook_tesoreria($mysqli, $id_pago, $evento, $params = array()) {
    if (!$mysqli || empty($id_pago)) {
        return false;
    }

    $tipo_mov = 'ejecutado';
    if ($evento === 'anular') {
        $tipo_mov = 'reverso';
    }

    $registrar_params = array_merge(array(
        'id_documento' => $id_pago,
        'tipo_doc' => 'pago_tesoreria',
        'modulo' => 'tesoreria',
        'tipo_mov' => $tipo_mov
    ), $params);

    return ppto_movimiento_registrar($mysqli, $registrar_params);
}

/**
 * HOOK RRHH: Afecta el presupuesto ejecutado al liquidarse la nómina de un departamento/proyecto.
 */
function ppto_hook_rrhh($mysqli, $id_nomina, $evento, $params = array()) {
    if (!$mysqli || empty($id_nomina)) {
        return false;
    }

    $tipo_mov = 'ejecutado';
    if ($evento === 'anular') {
        $tipo_mov = 'reverso';
    }

    $registrar_params = array_merge(array(
        'id_documento' => $id_nomina,
        'tipo_doc' => 'liquidacion_nomina',
        'modulo' => 'rrhh',
        'tipo_mov' => $tipo_mov
    ), $params);

    return ppto_movimiento_registrar($mysqli, $registrar_params);
}

/**
 * HOOK INVENTARIO: Registra egresos de almacén asignados a costos de partidas/proyectos.
 */
function ppto_hook_inventario($mysqli, $id_movimiento, $evento, $params = array()) {
    if (!$mysqli || empty($id_movimiento)) {
        return false;
    }

    $tipo_mov = 'ejecutado';
    if ($evento === 'anular') {
        $tipo_mov = 'reverso';
    }

    $registrar_params = array_merge(array(
        'id_documento' => $id_movimiento,
        'tipo_doc' => 'egreso_inventario',
        'modulo' => 'inventario',
        'tipo_mov' => $tipo_mov
    ), $params);

    return ppto_movimiento_registrar($mysqli, $registrar_params);
}

/**
 * HOOK ACTIVOS FIJOS: Registra ejecuciones al adquirir nuevos bienes capitalizables.
 */
function ppto_hook_activos($mysqli, $id_adquisicion, $evento, $params = array()) {
    if (!$mysqli || empty($id_adquisicion)) {
        return false;
    }

    $tipo_mov = 'ejecutado';
    if ($evento === 'anular') {
        $tipo_mov = 'reverso';
    }

    $registrar_params = array_merge(array(
        'id_documento' => $id_adquisicion,
        'tipo_doc' => 'adquisicion_activo',
        'modulo' => 'activos',
        'tipo_mov' => $tipo_mov
    ), $params);

    return ppto_movimiento_registrar($mysqli, $registrar_params);
}

/**
 * HOOK CONTABILIDAD: Afecta las ejecuciones mediante ajustes por diario o asientos manuales.
 */
function ppto_hook_contabilidad($mysqli, $id_asiento, $evento, $params = array()) {
    if (!$mysqli || empty($id_asiento)) {
        return false;
    }

    $tipo_mov = 'ejecutado';
    if ($evento === 'anular') {
        $tipo_mov = 'reverso';
    }

    $registrar_params = array_merge(array(
        'id_documento' => $id_asiento,
        'tipo_doc' => 'asiento_contable',
        'modulo' => 'contabilidad',
        'tipo_mov' => $tipo_mov
    ), $params);

    return ppto_movimiento_registrar($mysqli, $registrar_params);
}

/**
 * Obtiene el presupuesto vigente consolidado (inicial + reajustes netos) para un escenario específico.
 * 
 * @param mysqli $mysqli Conexión activa.
 * @param int $ppe_id ID de cabecera presupuestaria.
 * @param int $ppa_id ID de partida.
 * @param string|null $proy_id ID de proyecto (opcional).
 * @param string|null $rubro Nombre de rubro (opcional).
 * @param int|null $mes Mes específico o NULL para el acumulado anual.
 * @return float Presupuesto vigente obtenido.
 */
function ppto_vigente_obtener($mysqli, $ppe_id, $ppa_id, $proy_id = null, $rubro = null, $mes = null) {
    if (!$mysqli || empty($ppe_id) || empty($ppa_id)) {
        return 0.00;
    }
    
    $mes_consulta = ($mes !== null) ? (int)$mes : 12;
    $presupuesto_inicial = 0.00;
    $reajustes = 0.00;
    
    if (!empty($proy_id) && !empty($rubro)) {
        // Es un rubro de proyecto
        $clean_proy = $mysqli->real_escape_string($proy_id);
        $clean_rubro = $mysqli->real_escape_string($rubro);
        
        $sql = "SELECT pdm.pdm_presupuesto_mensual
                FROM exa_ppto_proyecto_detalles pd
                INNER JOIN exa_ppto_proyecto_detalles_mes pdm ON pd.pdp_id = pdm.pdp_id
                WHERE pd.ppe_id = $ppe_id 
                  AND pd.ppa_id = $ppa_id 
                  AND pd.proy_id = '$clean_proy' 
                  AND pd.pdp_rubro = '$clean_rubro'";
        if ($mes !== null) {
            $sql .= " AND pdm.pdm_mes = $mes_consulta";
        } else {
            $sql .= " AND pdm.pdm_mes <= 12";
        }
        
        $res = $mysqli->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $presupuesto_inicial += (float)$row['pdm_presupuesto_mensual'];
            }
        }
        
        // Sumar reajustes para proyecto
        if ($mes !== null) {
            $reajustes = ppto_reajuste_consolidar_proyecto($mysqli, $ppe_id, $ppa_id, $proy_id, $rubro, $mes_consulta);
        } else {
            for ($m = 1; $m <= 12; $m++) {
                $reajustes += ppto_reajuste_consolidar_proyecto($mysqli, $ppe_id, $ppa_id, $proy_id, $rubro, $m);
            }
        }
    } else {
        // Es una partida estándar
        $sql = "SELECT SUM(pde_monto) AS total_monto FROM exa_ppto_detalles WHERE ppe_id = $ppe_id AND ppa_id = $ppa_id";
        if ($mes !== null) {
            $sql .= " AND pde_mes = $mes_consulta";
        } else {
            $sql .= " AND pde_mes <= 12";
        }
        
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $presupuesto_inicial = (float)$row['total_monto'];
        }
        
        // Sumar reajustes para partida estándar
        if ($mes !== null) {
            $reajustes = ppto_reajuste_consolidar_partida($mysqli, $ppe_id, $ppa_id, $mes_consulta);
        } else {
            for ($m = 1; $m <= 12; $m++) {
                $reajustes += ppto_reajuste_consolidar_partida($mysqli, $ppe_id, $ppa_id, $m);
            }
        }
    }
    
    return round($presupuesto_inicial + $reajustes, 2);
}

/**
 * Crea o registra un reajuste presupuestario (Alias de integración).
 */
function ppto_reajuste_crear($mysqli, $datos) {
    if (file_exists(__DIR__ . '/ppto_reajustes_logica.php')) {
        include_once(__DIR__ . '/ppto_reajustes_logica.php');
        return ppto_reajuste_registrar($mysqli, $datos);
    }
    return false;
}

/**
 * HOOK DE INTEGRACIÓN PARA MÓDULO EXTERNO (Fila de Plantilla Reutilizable)
 * Realiza el mapeo de campos de tablas externas, validaciones previas y registro unificado.
 *
 * @param mysqli $mysqli Conexión activa a la base de datos.
 * @param mixed $id Identificador del registro en la tabla del módulo externo.
 * @param string $evento Evento capturado (crear | aprobar | anular | etc.)
 * @return bool Retorna true si se validó y registró correctamente, false de lo contrario.
 */
function ppto_hook_modulo($mysqli, $id, $evento) {
    if (!$mysqli || empty($id) || empty($evento)) {
        return false;
    }

    // 1. OBTENER DATOS DESDE LA TABLA DEL MÓDULO EXTERNO
    // [NOMBRE_TABLA] representa la tabla externa (ej: com_ordenes_compra)
    // [CAMPOS CLAVE] representa las columnas de dicha tabla externa
    $id_clean = $mysqli->real_escape_string($id);
    $sql_ext = "SELECT 
                    [campo_monto] AS monto, 
                    [campo_rubro] AS rubro, 
                    [campo_doc] AS doc,
                    [campo_proyecto] AS proy_id,
                    [campo_empresa] AS emp_id,
                    [campo_fecha] AS fecha
                FROM [NOMBRE_TABLA] 
                WHERE [id_key] = '$id_clean' LIMIT 1";
    
    $res_ext = $mysqli->query($sql_ext);
    if (!$res_ext || $res_ext->num_rows === 0) {
        return false; // El documento externo no existe en la base de datos
    }
    
    $row = $res_ext->fetch_assoc();
    
    // Mapeo exacto de los campos claves requeridos
    $monto   = (float)$row['monto'];
    $rubro   = $row['rubro'];
    $doc_cod = $row['doc'];
    $proy_id = isset($row['proy_id']) ? $row['proy_id'] : null;
    $emp_id  = isset($row['emp_id']) ? (int)$row['emp_id'] : 1;
    $fecha   = isset($row['fecha']) ? $row['fecha'] : date('Y-m-d');
    
    // Determinar tipo de movimiento presupuestario a partir del evento externo
    $tipo_mov = 'comprometido'; // Por defecto para pre-gastos como aprobación de OCs
    if ($evento === 'anular' || $evento === 'rechazar') {
        $tipo_mov = 'reverso';
    } elseif ($evento === 'pagar' || $evento === 'liquidar') {
        $tipo_mov = 'ejecutado';
    }

    $time = strtotime($fecha);
    $anio = $time ? (int)date('Y', $time) : (int)date('Y');
    $mes  = $time ? (int)date('n', $time) : (int)date('n');

    // Identificar cabecera de presupuesto activa de la empresa
    $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('emp_id' => $emp_id, 'ppe_anio' => $anio));
    if (!$ppe_id) {
        return false; // Sin presupuesto activo configurado
    }

    // Buscar partida a través de las reglas automáticas configuradas
    $regla = ppto_regla_buscar($mysqli, $emp_id, '[tipo_documento]', $doc_cod);
    if (!$regla) {
        return false; // No hay regla presupuestaria para este tipo de documento
    }
    $ppa_id = (int)$regla['ppa_id'];

    // 2. VALIDACIÓN A: Existe partida/rubro asignada en el proyecto (si aplica)
    if (!empty($proy_id) && !empty($rubro)) {
        $clean_proy = $mysqli->real_escape_string($proy_id);
        $clean_rubro = $mysqli->real_escape_string($rubro);
        
        $sql_chk = "SELECT pdp_id FROM exa_ppto_proyecto_detalles 
                    WHERE ppe_id = $ppe_id 
                      AND ppa_id = $ppa_id 
                      AND proy_id = '$clean_proy' 
                      AND pdp_rubro = '$clean_rubro' 
                    LIMIT 1";
        $res_chk = $mysqli->query($sql_chk);
        if (!$res_chk || $res_chk->num_rows === 0) {
            return false; // Combinación rubro/partida no pertenece al proyecto asignado
        }
    }

    // 3. VALIDACIÓN B: Presupuesto disponible (solo para fase comprometido)
    if ($tipo_mov === 'comprometido') {
        $disponible = 0.00;
        if (!empty($proy_id) && !empty($rubro)) {
            // Verificación en la distribución mensual del proyecto
            $sql_avail = "SELECT (pdm_presupuesto_mensual - pdm_ejecutado - pdm_comprometido) AS disponible 
                          FROM exa_ppto_proyecto_detalles_mes pdm
                          INNER JOIN exa_ppto_proyecto_detalles pd ON pdm.pdp_id = pd.pdp_id
                          WHERE pd.ppe_id = $ppe_id 
                            AND pd.ppa_id = $ppa_id 
                            AND pd.proy_id = '" . $mysqli->real_escape_string($proy_id) . "' 
                            AND pd.pdp_rubro = '" . $mysqli->real_escape_string($rubro) . "' 
                            AND pdm.pdm_mes = $mes LIMIT 1";
            $res_avail = $mysqli->query($sql_avail);
            if ($res_avail && $row_avail = $res_avail->fetch_assoc()) {
                $disponible = (float)$row_avail['disponible'];
            }
        } else {
            // Verificación en la partida estándar general
            $calc_partida = ppto_motor_calcular_partida($mysqli, $ppa_id, $mes);
            if (isset($calc_partida['disponible'])) {
                $disponible = (float)$calc_partida['disponible'];
            }
        }

        // Bloquear si el monto de pre-gasto supera el remanente disponible
        if ($disponible < $monto) {
            return false; // Rechazado por fondos insuficientes
        }
    }

    // 4. VALIDACIÓN C: No duplicar movimiento por mismo documento + evento (tipo_mov)
    $sql_dup = "SELECT mov_id FROM exa_ppto_movimientos 
                WHERE mov_tipo_doc = '[tipo_documento]' 
                  AND mov_doc_id = '" . $mysqli->real_escape_string($doc_cod) . "' 
                  AND mov_tipo_mov = '$tipo_mov' 
                LIMIT 1";
    $res_dup = $mysqli->query($sql_dup);
    if ($res_dup && $res_dup->num_rows > 0) {
        return false; // El movimiento de este documento y tipo de evento ya fue asentado
    }

    // 5. REGISTRO FINAL EN EL MOTOR CENTRAL
    $params_registrar = array(
        'id_documento' => $doc_cod,
        'tipo_doc' => '[tipo_documento]',
        'modulo' => '[modulo]',
        'tipo_mov' => $tipo_mov,
        'id_rubro' => $rubro,
        'monto' => $monto,
        'id_usuario' => 1, // Reemplazable por el ID del usuario en sesión
        'emp_id' => $emp_id,
        'fecha' => $fecha,
        'proy_id' => $proy_id,
        'ppa_id' => $ppa_id
    );

    return ppto_movimiento_registrar($mysqli, $params_registrar);
}

