<?php
/**
 * ppto_movimiento_integracion.php
 * Capa de Integraciï¿½n para el Mï¿½dulo de Presupuestos (EXA PPTO).
 * Implementa el punto de entrada unificado para movimientos externos y hooks de los mï¿½dulos.
 */

include_once(__DIR__ . '/ppto_persistencia_logica.php');
include_once(__DIR__ . '/ppto_motor_logica.php');
include_once(__DIR__ . '/ppto_motor_calculo.php');

if (file_exists(__DIR__ . '/ppto_reajustes_logica.php')) {
    include_once(__DIR__ . '/ppto_reajustes_logica.php');
}

/**
 * Funciï¿½n centralizada ï¿½nica para registrar todo movimiento transaccional externo (comprometido, ejecutado, reverso).
 * Escribe en la bitï¿½cora de movimientos externos y actualiza secuencialmente el ledger presupuestario general.
 *
 * @param mysqli $mysqli Objeto de conexiï¿½n.
 * @param array $params Argumentos requeridos: {id_documento, tipo_doc, modulo, tipo_mov, id_rubro, monto, id_usuario, Emp_Cod, fecha, Pro_Cod}
 * @return bool Retorna true si se registrï¿½ y ejecutï¿½ con ï¿½xito, de lo contrario false.
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
    $Emp_Cod       = isset($params['Emp_Cod']) ? (int)$params['Emp_Cod'] : 1;
    $fecha        = isset($params['fecha']) ? $params['fecha'] : date('Y-m-d');
    $Pro_Cod      = isset($params['Pro_Cod']) ? $params['Pro_Cod'] : null;

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

    // 1. Localizar la versiï¿½n presupuestaria aprobada y activa de la empresa para ese aï¿½o
    $Ppe_Cod = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'Ppe_Ani' => $anio));
    if (!$Ppe_Cod) {
        return false; // Sin presupuesto activo, no hay afectaciï¿½n posible
    }

    // 2. Buscar regla de coincidencia secuencial para obtener la partida (Ppa_Cod)
    $regla = ppto_regla_buscar($mysqli, $Emp_Cod, $tipo_doc, $id_documento);
    $Ppa_Cod = 0;
    if ($regla) {
        $Ppa_Cod = (int)$regla['Ppa_Cod'];
    } else {
        // Fallback: Si no se define una regla, podemos recibir la partida directamente en los parï¿½metros
        $Ppa_Cod = isset($params['Ppa_Cod']) ? (int)$params['Ppa_Cod'] : 0;
        if ($Ppa_Cod <= 0) {
            return false; // No se puede registrar un movimiento sin conocer su partida destino
        }
    }

    // 3. Determinar signo de la transacciï¿½n segï¿½n la regla
    $signo = '+';
    if ($regla && isset($regla['Prg_Signo'])) {
        $signo = $regla['Prg_Signo'];
    }

    // Si es un reverso (anulaciï¿½n), invertimos el signo del movimiento para guardarlo en bitï¿½cora
    $pej_estado = 'A';
    if ($tipo_mov === 'reverso') {
        $signo = ($signo === '+') ? '-' : '+';
        $pej_estado = 'I'; // Gatilla que ppto_documento_ejecutar maneje la reversiï¿½n de ejecuciï¿½n
    }

    // 4. Prevenir duplicaciï¿½n transaccional antes de registrar
    $duplicado = ppto_persistencia_consultar($mysqli, 3, array(
        'Pej_TipDoc' => $tipo_doc,
        'Pej_DocCod' => $id_documento,
        'Pej_Sig' => $signo
    ));
    if ($duplicado) {
        return false; // Evitamos duplicidad transaccional
    }

    // 5. Determinar fase presupuestaria para el ledger general
    $Pej_Fase = 'E'; // Ejecutado por defecto
    if ($tipo_mov === 'comprometido') {
        $Pej_Fase = 'C';
    } elseif ($tipo_mov === 'reverso') {
        // Buscamos si existe un movimiento histï¿½rico previo para anular la misma fase
        $sql_prev = "SELECT Mov_TipMov FROM pre_movimientos 
                     WHERE Mov_TipDoc = '" . $mysqli->real_escape_string($tipo_doc) . "' 
                       AND Mov_DocCod = '" . $mysqli->real_escape_string($id_documento) . "' 
                     ORDER BY Mov_Cod DESC LIMIT 1";
        $res_prev = $mysqli->query($sql_prev);
        if ($res_prev && $row_prev = $res_prev->fetch_assoc()) {
            $Pej_Fase = ($row_prev['Mov_TipMov'] === 'comprometido') ? 'C' : 'E';
        }
    }

    // 6. Registrar en la bitï¿½cora histï¿½rica pre_movimientos
    $clean_proy = $Pro_Cod ? "'" . $mysqli->real_escape_string($Pro_Cod) . "'" : "NULL";
    $clean_rubro = $id_rubro ? "'" . $mysqli->real_escape_string($id_rubro) . "'" : "NULL";

    $sql_mov = "INSERT INTO pre_movimientos (
                    Emp_Cod, Ppe_Cod, Pro_Cod, Ppa_Cod, Mov_Rubro,
                    Mov_DocCod, Mov_TipDoc, Mov_Modulo, Mov_TipMov,
                    Mov_Mon, Mov_Sig, Mov_Mes, Mov_Ani,
                    Mov_FecDoc, Mov_FecReg, Usu_Cod
                ) VALUES (
                    $Emp_Cod, $Ppe_Cod, $clean_proy, $Ppa_Cod, $clean_rubro,
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

    // 7. Invocar secuencialmente al motor de ejecuciï¿½n de documentos para actualizar pre_ejecucion
    return ppto_documento_ejecutar(
        $mysqli,
        $Emp_Cod,
        $id_usuario,
        $tipo_doc,
        $id_documento,
        $pej_estado,
        $fecha,
        $monto,
        isset($params['Suc_Cod']) ? $params['Suc_Cod'] : null,
        isset($params['Dep_Cod']) ? $params['Dep_Cod'] : null,
        $Pro_Cod,
        $Pej_Fase,
        $id_rubro
    );
}

/**
 * HOOK COMPRAS: Registra comprometidos por orden de compra o reversa en caso de anulaciï¿½n.
 * Cumple con los requerimientos especï¿½ficos de la Fase 6 de compras.
 *
 * @param mixed $arg1 mysqli|string Conexiï¿½n o ID de orden.
 * @param mixed $arg2 string ID de orden o Evento.
 * @param mixed $arg3 string Evento (opcional si arg1 es la conexiï¿½n).
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
        return array('status' => 'error', 'message' => 'Parï¿½metros insuficientes o conexiï¿½n de base de datos no disponible.', 'id_movimiento' => null);
    }

    $id_clean = $mysqli_obj->real_escape_string($id_orden);

    // 1. OBTENER DATOS DESDE LA TABLA DEL Mï¿½DULO EXTERNO
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

    // Localizar versiï¿½n de presupuesto activa
    $Ppe_Cod = ppto_persistencia_consultar($mysqli_obj, 1, array('Emp_Cod' => $id_empresa, 'Ppe_Ani' => $anio));
    if (!$Ppe_Cod) {
        return array('status' => 'error', 'message' => "No existe una versiï¿½n de presupuesto activa para la empresa $id_empresa en el aï¿½o $anio.", 'id_movimiento' => null);
    }

    // Buscar la partida mediante las reglas del presupuesto
    $regla = ppto_regla_buscar($mysqli_obj, $id_empresa, 'orden_compra', $id_orden);
    if (!$regla) {
        return array('status' => 'error', 'message' => 'No se encontrï¿½ una regla presupuestaria activa vinculada a orden_compra.', 'id_movimiento' => null);
    }
    $Ppa_Cod = (int)$regla['Ppa_Cod'];

    // 2. VALIDACIï¿½N 1: Existe id_rubro en la versiï¿½n activa del proyecto
    if (!empty($id_proyecto) && !empty($rubro)) {
        $clean_proy = $mysqli_obj->real_escape_string($id_proyecto);
        $clean_rubro = $mysqli_obj->real_escape_string($rubro);

        $sql_chk = "SELECT Pdp_Cod FROM pre_proyecto_detalles 
                    WHERE Ppe_Cod = $Ppe_Cod 
                      AND Ppa_Cod = $Ppa_Cod 
                      AND Pro_Cod = '$clean_proy' 
                      AND Pdp_Rubro = '$clean_rubro' 
                    LIMIT 1";
        $res_chk = $mysqli_obj->query($sql_chk);
        if (!$res_chk || $res_chk->num_rows === 0) {
            return array(
                'status' => 'error',
                'message' => "El rubro '$rubro' no existe en la versiï¿½n activa del proyecto '$id_proyecto'.",
                'id_movimiento' => null
            );
        }
    }

    // 3. VALIDACIï¿½N 2: Hay presupuesto disponible (solo para comprometido)
    if ($tipo_mov === 'comprometido') {
        $disponible = 0.00;
        if (!empty($id_proyecto) && !empty($rubro)) {
            // Verificaciï¿½n en la distribuciï¿½n mensual del proyecto
            $clean_proy = $mysqli_obj->real_escape_string($id_proyecto);
            $clean_rubro = $mysqli_obj->real_escape_string($rubro);
            $sql_avail = "SELECT (Pdm_PreMensual - Pdm_Ejecutado - Pdm_Comprometido) AS disponible 
                          FROM pre_proyecto_detalles_mes pdm
                          INNER JOIN pre_proyecto_detalles pd ON pdm.Pdp_Cod = pd.Pdp_Cod
                          WHERE pd.Ppe_Cod = $Ppe_Cod 
                            AND pd.Ppa_Cod = $Ppa_Cod 
                            AND pd.Pro_Cod = '$clean_proy' 
                            AND pd.Pdp_Rubro = '$clean_rubro' 
                            AND pdm.Pdm_Mes = $mes LIMIT 1";
            $res_avail = $mysqli_obj->query($sql_avail);
            if ($res_avail && $row_avail = $res_avail->fetch_assoc()) {
                $disponible = (float)$row_avail['disponible'];
            }
        } else {
            // Verificaciï¿½n en la partida estï¿½ndar general
            $calc_partida = ppto_motor_calcular_partida($mysqli_obj, $Ppa_Cod, $mes);
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

    // 4. VALIDACIï¿½N 3: No duplicar: mismo id_orden + mismo evento ya registrado
    $sql_dup = "SELECT Mov_Cod FROM pre_movimientos 
                WHERE Mov_TipDoc = 'orden_compra' 
                  AND Mov_DocCod = '$id_clean' 
                  AND Mov_TipMov = '$tipo_mov' 
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
        'Emp_Cod' => $id_empresa,
        'fecha' => $fecha,
        'Pro_Cod' => $id_proyecto,
        'Ppa_Cod' => $Ppa_Cod
    );

    $reg_ok = ppto_movimiento_registrar($mysqli_obj, $params_registrar);
    if ($reg_ok) {
        // Obtener el ID del movimiento reciï¿½n insertado
        $id_movimiento = null;
        $sql_last = "SELECT Mov_Cod FROM pre_movimientos 
                     WHERE Mov_TipDoc = 'orden_compra' 
                       AND Mov_DocCod = '$id_clean' 
                     ORDER BY Mov_Cod DESC LIMIT 1";
        $res_last = $mysqli_obj->query($sql_last);
        if ($res_last && $row_last = $res_last->fetch_assoc()) {
            $id_movimiento = (int)$row_last['Mov_Cod'];
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
 * HOOK TESORERï¿½A: Registra ejecuciones reales por pagos de facturas o egresos financieros.
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
 * HOOK RRHH: Afecta el presupuesto ejecutado al liquidarse la nï¿½mina de un departamento/proyecto.
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
 * HOOK INVENTARIO: Registra egresos de almacï¿½n asignados a costos de partidas/proyectos.
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
 * Obtiene el presupuesto vigente consolidado (inicial + reajustes netos) para un escenario especï¿½fico.
 * 
 * @param mysqli $mysqli Conexiï¿½n activa.
 * @param int $Ppe_Cod ID de cabecera presupuestaria.
 * @param int $Ppa_Cod ID de partida.
 * @param string|null $Pro_Cod ID de proyecto (opcional).
 * @param string|null $rubro Nombre de rubro (opcional).
 * @param int|null $mes Mes especï¿½fico o NULL para el acumulado anual.
 * @return float Presupuesto vigente obtenido.
 */
function ppto_vigente_obtener($mysqli, $Ppe_Cod, $Ppa_Cod, $Pro_Cod = null, $rubro = null, $mes = null) {
    if (!$mysqli || empty($Ppe_Cod) || empty($Ppa_Cod)) {
        return 0.00;
    }
    
    $mes_consulta = ($mes !== null) ? (int)$mes : 12;
    $presupuesto_inicial = 0.00;
    $reajustes = 0.00;
    
    if (!empty($Pro_Cod) && !empty($rubro)) {
        // Es un rubro de proyecto
        $clean_proy = $mysqli->real_escape_string($Pro_Cod);
        $clean_rubro = $mysqli->real_escape_string($rubro);
        
        $sql = "SELECT pdm.Pdm_PreMensual
                FROM pre_proyecto_detalles pd
                INNER JOIN pre_proyecto_detalles_mes pdm ON pd.Pdp_Cod = pdm.Pdp_Cod
                WHERE pd.Ppe_Cod = $Ppe_Cod 
                  AND pd.Ppa_Cod = $Ppa_Cod 
                  AND pd.Pro_Cod = '$clean_proy' 
                  AND pd.Pdp_Rubro = '$clean_rubro'";
        if ($mes !== null) {
            $sql .= " AND pdm.Pdm_Mes = $mes_consulta";
        } else {
            $sql .= " AND pdm.Pdm_Mes <= 12";
        }
        
        $res = $mysqli->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $presupuesto_inicial += (float)$row['Pdm_PreMensual'];
            }
        }
        
        // Sumar reajustes para proyecto
        if ($mes !== null) {
            $reajustes = ppto_reajuste_consolidar_proyecto($mysqli, $Ppe_Cod, $Ppa_Cod, $Pro_Cod, $rubro, $mes_consulta);
        } else {
            for ($m = 1; $m <= 12; $m++) {
                $reajustes += ppto_reajuste_consolidar_proyecto($mysqli, $Ppe_Cod, $Ppa_Cod, $Pro_Cod, $rubro, $m);
            }
        }
    } else {
        // Es una partida estï¿½ndar
        $sql = "SELECT SUM(Pde_Mon) AS total_monto FROM pre_detalle WHERE Ppe_Cod = $Ppe_Cod AND Ppa_Cod = $Ppa_Cod";
        if ($mes !== null) {
            $sql .= " AND Pde_Mes = $mes_consulta";
        } else {
            $sql .= " AND Pde_Mes <= 12";
        }
        
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $presupuesto_inicial = (float)$row['total_monto'];
        }
        
        // Sumar reajustes para partida estï¿½ndar
        if ($mes !== null) {
            $reajustes = ppto_reajuste_consolidar_partida($mysqli, $Ppe_Cod, $Ppa_Cod, $mes_consulta);
        } else {
            for ($m = 1; $m <= 12; $m++) {
                $reajustes += ppto_reajuste_consolidar_partida($mysqli, $Ppe_Cod, $Ppa_Cod, $m);
            }
        }
    }
    
    return round($presupuesto_inicial + $reajustes, 2);
}

/**
 * Crea o registra un reajuste presupuestario (Alias de integraciï¿½n).
 */
function ppto_reajuste_crear($mysqli, $datos) {
    if (file_exists(__DIR__ . '/ppto_reajustes_logica.php')) {
        include_once(__DIR__ . '/ppto_reajustes_logica.php');
        return ppto_reajuste_registrar($mysqli, $datos);
    }
    return false;
}

/**
 * HOOK DE INTEGRACIï¿½N PARA Mï¿½DULO EXTERNO (Fila de Plantilla Reutilizable)
 * Realiza el mapeo de campos de tablas externas, validaciones previas y registro unificado.
 *
 * @param mysqli $mysqli Conexiï¿½n activa a la base de datos.
 * @param mixed $id Identificador del registro en la tabla del mï¿½dulo externo.
 * @param string $evento Evento capturado (crear | aprobar | anular | etc.)
 * @return bool Retorna true si se validï¿½ y registrï¿½ correctamente, false de lo contrario.
 */
function ppto_hook_modulo($mysqli, $id, $evento) {
    if (!$mysqli || empty($id) || empty($evento)) {
        return false;
    }

    // 1. OBTENER DATOS DESDE LA TABLA DEL Mï¿½DULO EXTERNO
    // [NOMBRE_TABLA] representa la tabla externa (ej: com_ordenes_compra)
    // [CAMPOS CLAVE] representa las columnas de dicha tabla externa
    $id_clean = $mysqli->real_escape_string($id);
    $sql_ext = "SELECT 
                    [campo_monto] AS monto, 
                    [campo_rubro] AS rubro, 
                    [campo_doc] AS doc,
                    [campo_proyecto] AS Pro_Cod,
                    [campo_empresa] AS Emp_Cod,
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
    $Pro_Cod = isset($row['Pro_Cod']) ? $row['Pro_Cod'] : null;
    $Emp_Cod  = isset($row['Emp_Cod']) ? (int)$row['Emp_Cod'] : 1;
    $fecha   = isset($row['fecha']) ? $row['fecha'] : date('Y-m-d');
    
    // Determinar tipo de movimiento presupuestario a partir del evento externo
    $tipo_mov = 'comprometido'; // Por defecto para pre-gastos como aprobaciï¿½n de OCs
    if ($evento === 'anular' || $evento === 'rechazar') {
        $tipo_mov = 'reverso';
    } elseif ($evento === 'pagar' || $evento === 'liquidar') {
        $tipo_mov = 'ejecutado';
    }

    $time = strtotime($fecha);
    $anio = $time ? (int)date('Y', $time) : (int)date('Y');
    $mes  = $time ? (int)date('n', $time) : (int)date('n');

    // Identificar cabecera de presupuesto activa de la empresa
    $Ppe_Cod = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'Ppe_Ani' => $anio));
    if (!$Ppe_Cod) {
        return false; // Sin presupuesto activo configurado
    }

    // Buscar partida a travï¿½s de las reglas automï¿½ticas configuradas
    $regla = ppto_regla_buscar($mysqli, $Emp_Cod, '[tipo_documento]', $doc_cod);
    if (!$regla) {
        return false; // No hay regla presupuestaria para este tipo de documento
    }
    $Ppa_Cod = (int)$regla['Ppa_Cod'];

    // 2. VALIDACIï¿½N A: Existe partida/rubro asignada en el proyecto (si aplica)
    if (!empty($Pro_Cod) && !empty($rubro)) {
        $clean_proy = $mysqli->real_escape_string($Pro_Cod);
        $clean_rubro = $mysqli->real_escape_string($rubro);
        
        $sql_chk = "SELECT Pdp_Cod FROM pre_proyecto_detalles 
                    WHERE Ppe_Cod = $Ppe_Cod 
                      AND Ppa_Cod = $Ppa_Cod 
                      AND Pro_Cod = '$clean_proy' 
                      AND Pdp_Rubro = '$clean_rubro' 
                    LIMIT 1";
        $res_chk = $mysqli->query($sql_chk);
        if (!$res_chk || $res_chk->num_rows === 0) {
            return false; // Combinaciï¿½n rubro/partida no pertenece al proyecto asignado
        }
    }

    // 3. VALIDACIï¿½N B: Presupuesto disponible (solo para fase comprometido)
    if ($tipo_mov === 'comprometido') {
        $disponible = 0.00;
        if (!empty($Pro_Cod) && !empty($rubro)) {
            // Verificaciï¿½n en la distribuciï¿½n mensual del proyecto
            $sql_avail = "SELECT (Pdm_PreMensual - Pdm_Ejecutado - Pdm_Comprometido) AS disponible 
                          FROM pre_proyecto_detalles_mes pdm
                          INNER JOIN pre_proyecto_detalles pd ON pdm.Pdp_Cod = pd.Pdp_Cod
                          WHERE pd.Ppe_Cod = $Ppe_Cod 
                            AND pd.Ppa_Cod = $Ppa_Cod 
                            AND pd.Pro_Cod = '" . $mysqli->real_escape_string($Pro_Cod) . "' 
                            AND pd.Pdp_Rubro = '" . $mysqli->real_escape_string($rubro) . "' 
                            AND pdm.Pdm_Mes = $mes LIMIT 1";
            $res_avail = $mysqli->query($sql_avail);
            if ($res_avail && $row_avail = $res_avail->fetch_assoc()) {
                $disponible = (float)$row_avail['disponible'];
            }
        } else {
            // Verificaciï¿½n en la partida estï¿½ndar general
            $calc_partida = ppto_motor_calcular_partida($mysqli, $Ppa_Cod, $mes);
            if (isset($calc_partida['disponible'])) {
                $disponible = (float)$calc_partida['disponible'];
            }
        }

        // Bloquear si el monto de pre-gasto supera el remanente disponible
        if ($disponible < $monto) {
            return false; // Rechazado por fondos insuficientes
        }
    }

    // 4. VALIDACIï¿½N C: No duplicar movimiento por mismo documento + evento (tipo_mov)
    $sql_dup = "SELECT Mov_Cod FROM pre_movimientos 
                WHERE Mov_TipDoc = '[tipo_documento]' 
                  AND Mov_DocCod = '" . $mysqli->real_escape_string($doc_cod) . "' 
                  AND Mov_TipMov = '$tipo_mov' 
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
        'id_usuario' => 1, // Reemplazable por el ID del usuario en sesiï¿½n
        'Emp_Cod' => $Emp_Cod,
        'fecha' => $fecha,
        'Pro_Cod' => $Pro_Cod,
        'Ppa_Cod' => $Ppa_Cod
    );

    return ppto_movimiento_registrar($mysqli, $params_registrar);
}

