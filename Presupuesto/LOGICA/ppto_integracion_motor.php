<?php
/**
 * ppto_integracion_motor.php
 * Motor de Integraci?n entre Presupuesto y Producci?n para EXA PPTO.
 * Orquesta el recalculo din?mico, el registro de producci?n real, c?lculo de variaciones y proyecciones.
 */

include_once('ppto_persistencia_logica.php');
include_once('ppto_motor_calculo.php');
include_once('ppto_motor_produccion.php');
include_once('ppto_schema_logica.php');

/**
 * @param mysqli $mysqli
 * @param string|null $proy_id
 * @param int|null $emp_override
 * @return int
 */
function ppto_integracion_emp_id($mysqli, $proy_id = null, $emp_override = null) {
    if ($emp_override !== null && (int)$emp_override > 0) {
        return (int)$emp_override;
    }
    if ($proy_id !== null && trim($proy_id) !== '') {
        return ppto_resolve_emp_id_proyecto($mysqli, $proy_id);
    }
    return ppto_resolve_emp_id();
}


/**
 * Registra o actualiza un valor de producci?n (esperada, real, o proyectada) para un proyecto y per?odo.
 *
 * @param mysqli $mysqli Conexi?n activa a la BD.
 * @param string $id_proyecto C?digo del proyecto (`proy_id`).
 * @param int $periodo Mes calendarizado (1 a 12).
 * @param float $valor Importe num?rico f?sico medido.
 * @param string $tipo Tipo de medici?n ('esperada', 'real', 'proyectada').
 * @param int|null $anio A?o fiscal de medici?n (opcional, por defecto el a?o actual).
 * @return bool Retorna true si se registr? con ?xito, false de lo contrario.
 */
function ppto_integracion_produccion_registrar($mysqli, $id_proyecto, $periodo, $valor, $tipo, $anio = null, $emp_override = null) {
    $periodo = (int)$periodo;
    $valor = (float)$valor;
    $tipo = strtolower(trim($tipo));
    $anio = ($anio !== null) ? (int)$anio : (int)date('Y');
    
    $clean_proy = $mysqli->real_escape_string(trim($id_proyecto));
    $Emp_Cod = ppto_integracion_emp_id($mysqli, $id_proyecto, $emp_override);
    $Usu_Cod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 1;

    if (!in_array($tipo, array('esperada', 'real', 'proyectada'))) {
        return false; // Evitamos tipos no soportados
    }

    // Sync tardio sobre periodo cerrado: no cambia prd_estado, registra evento_log
    if ($tipo === 'real') {
        $res_est = $mysqli->query("SELECT Prd_Est AS prd_estado, Prd_Real AS prd_real FROM pre_prod_periodos
            WHERE (Pro_Cod='$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Emp_Cod=$Emp_Cod AND Prd_Anio=$anio AND Prd_Mes=$periodo LIMIT 1");
        if ($res_est && ($row_est = $res_est->fetch_assoc()) && isset($row_est['prd_estado']) && $row_est['prd_estado'] === 'cerrado') {
            if (abs((float)$row_est['prd_real'] - $valor) < 0.0001) {
                return true;
            }
            require_once __DIR__ . '/../../contabilidad/LOGICA/con_log_balances2.php';
            require_once __DIR__ . '/ppto_prod_periodo_logica.php';
            $obDatos = new Class_Log_Datos_Con();
            $Ses_Dat_Aut = isset($_SESSION['Ses_Dat_Aut']) ? $_SESSION['Ses_Dat_Aut'] : null;
            $request_uri = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
            $result = ppto_prod_periodo_corregir_real_cerrado(
                $mysqli, $obDatos, $id_proyecto, $Emp_Cod, $anio, $periodo,
                $valor, 'sync', $Usu_Cod, $Ses_Dat_Aut, $request_uri
            );
            return !empty($result['ok']);
        }
    }

    $col_campo = 'Prd_' . ucfirst($tipo);

    $sql = "INSERT INTO pre_prod_periodos 
                (Pro_Cod, Emp_Cod, Prd_Anio, Prd_Mes, $col_campo, Prd_FecReg, Usu_Cod)
            VALUES 
                (COALESCE((SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy' OR Pro_Cod='$clean_proy' LIMIT 1), 0), $Emp_Cod, $anio, $periodo, $valor, NOW(), $Usu_Cod)
            ON DUPLICATE KEY UPDATE 
                $col_campo = $valor,
                Prd_FecReg = NOW(),
                Usu_Cod = $Usu_Cod";

    $res = $mysqli->query($sql);
    if ($res) {
        // Disparador expl?cito: Al registrar producci?n real, calculamos inmediatamente variaciones
        if ($tipo === 'real') {
            ppto_integracion_variacion_calcular($mysqli, $id_proyecto, $periodo, $anio);
        }
        return true;
    }
    return false;
}

/**
 * Calcula de forma autom?tica la desviaci?n o variaci?n absoluta y porcentual real vs esperada.
 * Guarda los resultados en pre_prod_variaciones.
 *
 * @param mysqli $mysqli Conexi?n activa.
 * @param string $id_proyecto C?digo del proyecto.
 * @param int $periodo Mes calendarizado.
 * @param int|null $anio A?o fiscal.
 * @return array Arreglo asociativo con la variaci?n calculada: {absoluta, porcentual}
 */
function ppto_integracion_variacion_calcular($mysqli, $id_proyecto, $periodo, $anio = null) {
    $periodo = (int)$periodo;
    $anio = ($anio !== null) ? (int)$anio : (int)date('Y');
    $clean_proy = $mysqli->real_escape_string(trim($id_proyecto));
    $Emp_Cod = ppto_integracion_emp_id($mysqli, $id_proyecto);
    $var_porcentual = 0.00;

    // 1. Consultar el hist?rico cargado para el periodo
    $sql = "SELECT Prd_Esperada AS prd_esperada, Prd_Real AS prd_real 
            FROM pre_prod_periodos 
            WHERE (Pro_Cod = '$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Emp_Cod = $Emp_Cod AND Prd_Anio = $anio AND Prd_Mes = $periodo 
            LIMIT 1";
    $res = $mysqli->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $esperada = (float)$row['prd_esperada'];
        $real = (float)$row['prd_real'];

        $var_absoluta = $real - $esperada;
        
        if ($esperada > 0.0001) {
            $var_porcentual = (($real / $esperada) * 100.00) - 100.00;
        } else {
            $var_porcentual = ($real > 0.0001) ? 100.00 : 0.00;
        }
    }

    $var_porcentual = round($var_porcentual, 2);

    $mysqli->query("INSERT INTO pre_prod_variaciones 
                        (Pro_Cod, Emp_Cod, Var_Anio, Var_Mes, Var_Absoluta, Var_Porcentual, Var_FecCal)
                    VALUES 
                        (COALESCE((SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy' OR Pro_Cod='$clean_proy' LIMIT 1), 0), $Emp_Cod, $anio, $periodo, $var_absoluta, $var_porcentual, NOW())
                    ON DUPLICATE KEY UPDATE 
                        Var_Absoluta = $var_absoluta,
                        Var_Porcentual = $var_porcentual,
                        Var_FecCal = NOW()");

    return array(
        'absoluta' => $var_absoluta,
        'porcentual' => $var_porcentual
    );
}

/**
 * Proyectada por mes:
 * - Mes 1: valor esperado.
 * - Mes 2: real mes 1.
 * - Mes 3: promedio real meses 1-2.
 * - Mes N (N>=4): promedio real de los 3 meses anteriores (N-3 .. N-1) / 3.
 * - Si no hay real en la ventana o hay hueco tras el ultimo real, mantiene la ultima proyectada.
 *
 * @param mysqli $mysqli
 * @param string $id_proyecto
 * @param int|null $anio
 * @param int|null $periodo_corte Reservado (no usado en la formula por fila)
 * @param int|null $emp_override
 * @param int|null $mes_destino Mes donde guardar proyectada (null = mes posterior al ultimo real)
 * @return array
 */
function ppto_integracion_proyectar_esperada_mes($mysqli, $id_proyecto, $anio, $mes_destino, $Emp_Cod, $clean_proy) {
    $res_esp = $mysqli->query("SELECT Prd_Esperada AS prd_esperada FROM pre_prod_periodos
        WHERE (Pro_Cod='$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Emp_Cod=$Emp_Cod AND Prd_Anio=$anio AND Prd_Mes=$mes_destino LIMIT 1");
    $valor = 0.0;
    if ($res_esp && ($re = $res_esp->fetch_assoc())) {
        $valor = round((float)$re['prd_esperada'], 4);
    }
    return $valor;
}

function ppto_integracion_proyectar_ultima_calculada($mysqli, $id_proyecto, $anio, $mes_destino, $Emp_Cod, $clean_proy, $mes_ult_real) {
    $res_last = $mysqli->query("SELECT Prd_Proyectada AS prd_proyectada FROM pre_prod_periodos
        WHERE (Pro_Cod='$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Emp_Cod=$Emp_Cod AND Prd_Anio=$anio
          AND Prd_Proyectada > 0 AND Prd_Mes < $mes_destino
        ORDER BY Prd_Mes DESC LIMIT 1");
    if ($res_last && ($rl = $res_last->fetch_assoc()) && (float)$rl['prd_proyectada'] > 0) {
        return round((float)$rl['prd_proyectada'], 4);
    }
    if ($mes_ult_real > 0) {
        $res_fb = $mysqli->query("SELECT SUM(Prd_Real) AS acum FROM pre_prod_periodos
            WHERE (Pro_Cod='$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Emp_Cod=$Emp_Cod AND Prd_Anio=$anio AND Prd_Mes <= $mes_ult_real");
        if ($res_fb && ($rf = $res_fb->fetch_assoc()) && (float)$rf['acum'] > 0) {
            return round((float)$rf['acum'] / $mes_ult_real, 4);
        }
    }
    return 0.0;
}

function ppto_integracion_proyectar_promedio_siguiente_mes($mysqli, $id_proyecto, $anio = null, $periodo_corte = null, $emp_override = null, $mes_destino = null) {
    $anio = ($anio !== null) ? (int)$anio : (int)date('Y');
    $clean_proy = $mysqli->real_escape_string(trim($id_proyecto));
    $Emp_Cod = ppto_integracion_emp_id($mysqli, $id_proyecto, $emp_override);

    $res_m = $mysqli->query("SELECT MAX(Prd_Mes) AS mes FROM pre_prod_periodos
        WHERE (Pro_Cod='$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Emp_Cod=$Emp_Cod AND Prd_Anio=$anio AND Prd_Real > 0");
    $mes_corte_global = 0;
    if ($res_m && ($row_m = $res_m->fetch_assoc())) {
        $mes_corte_global = (int)$row_m['mes'];
    }

    if ($mes_destino === null) {
        $mes_destino = ($mes_corte_global > 0) ? ($mes_corte_global + 1) : 1;
    } else {
        $mes_destino = (int)$mes_destino;
    }

    if ($mes_destino < 1 || $mes_destino > 12) {
        return array('ok' => false, 'message' => 'Mes destino invalido.');
    }

    if ($mes_destino === 1) {
        $valor = ppto_integracion_proyectar_esperada_mes($mysqli, $id_proyecto, $anio, 1, $Emp_Cod, $clean_proy);
        if ($valor <= 0) {
            return array('ok' => false, 'message' => 'No hay toneladas esperadas en mes 1. Guarde el plan primero.');
        }
        ppto_integracion_produccion_registrar($mysqli, $id_proyecto, 1, $valor, 'proyectada', $anio, $emp_override);
        $msg = 'Proyectada en mes 1 = esperada: ' . number_format($valor, 2, '.', ',') . ' Ton.';
        return array(
            'ok' => true,
            'modo' => 'esperada',
            'mes_corte' => $mes_corte_global,
            'mes_destino' => 1,
            'acumulado' => 0,
            'valor' => $valor,
            'message' => $msg
        );
    }

    $mes_ref = $mes_destino - 1;
    $res_cut = $mysqli->query("SELECT MAX(Prd_Mes) AS mes FROM pre_prod_periodos
        WHERE (Pro_Cod='$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Emp_Cod=$Emp_Cod AND Prd_Anio=$anio AND Prd_Real > 0 AND Prd_Mes <= $mes_ref");
    $mes_ult_real = 0;
    if ($res_cut && ($row_cut = $res_cut->fetch_assoc())) {
        $mes_ult_real = (int)$row_cut['mes'];
    }

    if ($mes_ult_real <= 0) {
        return array('ok' => false, 'message' => 'No hay produccion real en los meses anteriores para calcular la proyectada.');
    }

    $mes_ini = 1;
    $mes_fin = $mes_ref;
    $divisor = $mes_ref;
    if ($mes_destino >= 4) {
        $mes_ini = $mes_destino - 3;
        $mes_fin = $mes_destino - 1;
        $divisor = 3;
    }

    $acum = 0.0;
    $modo = 'calculada';
    $valor = 0.0;

    if ($mes_ref > $mes_ult_real) {
        $valor = ppto_integracion_proyectar_ultima_calculada($mysqli, $id_proyecto, $anio, $mes_destino, $Emp_Cod, $clean_proy, $mes_ult_real);
        $modo = 'mantenida';
        if ($valor <= 0) {
            return array('ok' => false, 'message' => 'No hay proyectada previa ni real suficiente para el mes ' . $mes_destino . '.');
        }
    } else {
        $res_acum = $mysqli->query("SELECT SUM(Prd_Real) AS acum FROM pre_prod_periodos
            WHERE (Pro_Cod='$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Emp_Cod=$Emp_Cod AND Prd_Anio=$anio
              AND Prd_Mes >= $mes_ini AND Prd_Mes <= $mes_fin");
        if ($res_acum && ($row = $res_acum->fetch_assoc())) {
            $acum = (float)$row['acum'];
        }
        if ($acum <= 0) {
            $valor = ppto_integracion_proyectar_ultima_calculada($mysqli, $id_proyecto, $anio, $mes_destino, $Emp_Cod, $clean_proy, $mes_ult_real);
            $modo = 'mantenida';
            if ($valor <= 0) {
                return array('ok' => false, 'message' => 'No hay real en la ventana ni proyectada previa para el mes ' . $mes_destino . '.');
            }
        } else {
            $valor = round($acum / $divisor, 4);
        }
    }

    ppto_integracion_produccion_registrar($mysqli, $id_proyecto, $mes_destino, $valor, 'proyectada', $anio, $emp_override);

    if ($modo === 'mantenida') {
        $msg = 'Proyectada mantenida en mes ' . $mes_destino . ': ' . number_format($valor, 2, '.', ',') . ' Ton (sin real suficiente).';
    } elseif ($mes_destino >= 4) {
        $msg = 'Proyectada mes ' . $mes_destino . ': promedio ultimos 3 meses (' . $mes_ini . '-' . $mes_fin . ') '
            . number_format($acum, 2, '.', ',') . ' / 3 = ' . number_format($valor, 2, '.', ',') . ' Ton';
    } else {
        $msg = 'Proyectada mes ' . $mes_destino . ': ' . number_format($acum, 2, '.', ',') . ' / ' . $divisor . ' = ' . number_format($valor, 2, '.', ',') . ' Ton';
    }

    return array(
        'ok' => true,
        'modo' => $modo,
        'mes_corte' => $mes_ult_real,
        'mes_destino' => $mes_destino,
        'acumulado' => $acum,
        'valor' => $valor,
        'message' => $msg
    );
}

/**
 * Extrapola la produccion proyectada al mes siguiente segun promedio mensual real acumulado.
 *
 * @param mysqli $mysqli Conexion activa.
 * @param string $id_proyecto Codigo del proyecto.
 * @param int $periodo_actual Mes de corte (ultimo mes con real considerado).
 * @param int|null $anio Anio fiscal.
 * @return float Valor proyectado insertado en el mes siguiente.
 */
function ppto_integracion_proyectar_cierre($mysqli, $id_proyecto, $periodo_actual, $anio = null) {
    $result = ppto_integracion_proyectar_promedio_siguiente_mes($mysqli, $id_proyecto, $anio, (int)$periodo_actual);
    return ($result['ok']) ? (float)$result['valor'] : 0.0;
}

/**
 * Orquestador principal que automatiza el flujo de rec?lculo din?mico del presupuesto del Proyecto.
 * 1. Extrae de forma configurable la producci?n real registrada desde Fase 3.
 * 2. Registra el valor obtenido en las bit?coras de producci?n de Fase 4.
 * 3. Ejecuta la f?rmula del rubro de Fase 2 usando la producci?n real en lugar de la esperada.
 * 4. Reprocesa toda la versi?n presupuestaria afectada de manera at?mica.
 *
 * @param mysqli $mysqli Conexi?n activa.
 * @param int $id_version ID de la cabecera del presupuesto.
 * @param int $periodo Mes de corte a procesar (1 a 12).
 * @param string|null $proyecto_id Opcional, filtrar por un proyecto espec?fico.
 * @return bool Retorna true si el proceso de rec?lculo unificado finaliz? con ?xito.
 */
function ppto_integracion_recalcular($mysqli, $id_version, $periodo, $proyecto_id = null) {
    $id_version = (int)$id_version;
    $periodo = (int)$periodo;
    $Emp_Cod = ppto_integracion_emp_id($mysqli, $proyecto_id);

    // Filtro condicional por proyecto espec?fico
    $cond_proy = ($proyecto_id !== null) ? " AND r.proy_id = '" . $mysqli->real_escape_string($proyecto_id) . "' " : "";

    // 1. Obtener todos los rubros anal?ticos de proyectos activos para la versi?n del presupuesto dada
    $sql_rubros = "SELECT r.*, pp.Ppe_Ani AS ppe_anio 
                   FROM pre_proyecto_detalles r
                   INNER JOIN pre_presupuesto pp ON r.Ppe_Cod = pp.Ppe_Cod
                   WHERE r.Ppe_Cod = $id_version AND r.Emp_Cod = $Emp_Cod $cond_proy";
    $res_rubros = $mysqli->query($sql_rubros);
    
    if (!$res_rubros || $res_rubros->num_rows === 0) {
        return false;
    }

    $procesados = 0;
    while ($rubro = $res_rubros->fetch_assoc()) {
        $pdp_id = (int)$rubro['pdp_id'];
        $proy_id = $rubro['proy_id'];
        $anio = (int)$rubro['ppe_anio'];
        $base_id = $rubro['bas_id'];

        // 2. Extraer de manera configurable la producci?n real registrada para el proyecto (Fase 3)
        $ext_prod = ppto_prod_obtener($mysqli, $proy_id, $periodo, array('Emp_Cod' => $Emp_Cod, 'anio' => $anio));
        $valor_produccion_real = (float)$ext_prod['valor'];

        // 3. Registrar la medici?n real en la tabla hist?rica relacional (Fase 4)
        ppto_integracion_produccion_registrar($mysqli, $proy_id, $periodo, $valor_produccion_real, 'real', $anio);

        // 4. Inyectar la producci?n real obtenida como "override" sobre la base de c?lculo de Fase 2
        // El rubro recalcula su presupuesto mensual de forma at?mica en base a la variable real de producci?n
        $overrides = array();
        $overrides[$base_id] = $valor_produccion_real; // Forzamos el valor real sobre la variable base
        
        // Recalcular rubro con override de variable f?sica real
        ppto_motor_calcular_rubro($mysqli, $pdp_id, $periodo, $overrides);
        
        $procesados++;
    }

    // 5. Reprocesar y consolidar toda la versi?n presupuestaria afectada de manera at?mica y recursiva
    if ($procesados > 0) {
        ppto_motor_calcular_version($mysqli, $id_version, $periodo);
        return true;
    }

    return false;
}
