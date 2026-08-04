<?php
/**
 * ppto_integracion_motor.php
 * Motor de Integración entre Presupuesto y Producción para EXA PPTO.
 * Orquesta el recálculo dinámico, el registro de producción real, cálculo de variaciones y proyecciones.
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
 * Registra o actualiza un valor de producción (esperada, real, o proyectada) para un proyecto y período.
 *
 * @param mysqli $mysqli Conexión activa a la BD.
 * @param string $id_proyecto Código del proyecto (`proy_id`).
 * @param int $periodo Mes calendarizado (1 a 12).
 * @param float $valor Importe numérico físico medido.
 * @param string $tipo Tipo de medición ('esperada', 'real', 'proyectada').
 * @param int|null $anio Año fiscal de medición (opcional, por defecto el año actual).
 * @return bool Retorna true si se registró con éxito, false de lo contrario.
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
        $res_est = $mysqli->query("SELECT prd_estado, prd_real FROM pre_prod_periodos
            WHERE proy_id='$clean_proy' AND Emp_Cod=$Emp_Cod AND prd_anio=$anio AND prd_mes=$periodo LIMIT 1");
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

    $col_campo = 'prd_' . $tipo;

    // Ejecuta la inserción atómica con ON DUPLICATE KEY para evitar duplicación de periodos
    $sql = "INSERT INTO pre_prod_periodos 
                (proy_id, Emp_Cod, prd_anio, prd_mes, $col_campo, prd_fecha_registro, Usu_Cod)
            VALUES 
                ('$clean_proy', $Emp_Cod, $anio, $periodo, $valor, NOW(), $Usu_Cod)
            ON DUPLICATE KEY UPDATE 
                $col_campo = $valor,
                prd_fecha_registro = NOW(),
                Usu_Cod = $Usu_Cod";

    $res = $mysqli->query($sql);
    if ($res) {
        // Disparador explícito: Al registrar producción real, calculamos inmediatamente variaciones
        if ($tipo === 'real') {
            ppto_integracion_variacion_calcular($mysqli, $id_proyecto, $periodo, $anio);
        }
        return true;
    }
    return false;
}

/**
 * Calcula de forma automática la desviación o variación absoluta y porcentual real vs esperada.
 * Guarda los resultados en pre_prod_variaciones.
 *
 * @param mysqli $mysqli Conexión activa.
 * @param string $id_proyecto Código del proyecto.
 * @param int $periodo Mes calendarizado.
 * @param int|null $anio Año fiscal.
 * @return array Arreglo asociativo con la variación calculada: {absoluta, porcentual}
 */
function ppto_integracion_variacion_calcular($mysqli, $id_proyecto, $periodo, $anio = null) {
    $periodo = (int)$periodo;
    $anio = ($anio !== null) ? (int)$anio : (int)date('Y');
    $clean_proy = $mysqli->real_escape_string(trim($id_proyecto));
    $Emp_Cod = ppto_integracion_emp_id($mysqli, $id_proyecto);
    $var_porcentual = 0.00;

    // 1. Consultar el histórico cargado para el periodo
    $sql = "SELECT prd_esperada, prd_real 
            FROM pre_prod_periodos 
            WHERE proy_id = '$clean_proy' AND Emp_Cod = $Emp_Cod AND prd_anio = $anio AND prd_mes = $periodo 
            LIMIT 1";
    $res = $mysqli->query($sql);
    if (!$res || $res->num_rows === 0) {
        return array('absoluta' => 0.0, 'porcentual' => 0.0);
    }

    $row = $res->fetch_assoc();
    $esperada = (float)$row['prd_esperada'];
    $real = (float)$row['prd_real'];

    // 2. Aplicar fórmula matemática de variaciones
    $var_absoluta = round($real - $esperada, 2);
    if ($esperada > 0.0001) {
        $var_porcentual = round(($var_absoluta / $esperada) * 100, 2);
    }

    // 3. Persistir atómicamente el resultado en pre_prod_variaciones
    $sql_var = "INSERT INTO pre_prod_variaciones 
                    (proy_id, Emp_Cod, var_anio, var_mes, var_absoluta, var_porcentual, var_fecha_calculo)
                VALUES 
                    ('$clean_proy', $Emp_Cod, $anio, $periodo, $var_absoluta, $var_porcentual, NOW())
                ON DUPLICATE KEY UPDATE 
                    var_absoluta = $var_absoluta,
                    var_porcentual = $var_porcentual,
                    var_fecha_calculo = NOW()";

    $mysqli->query($sql_var);

    return array(
        'absoluta' => $var_absoluta,
        'porcentual' => $var_porcentual
    );
}

/**
 * Motor de proyecciones dinámicas de producción para meses futuros.
 *
 * @param mysqli $mysqli Conexión activa.
 * @param string $id_proyecto Código del proyecto.
 * @param int $periodo Mes que se está proyectando (1 a 12).
 * @param string $metodo Algoritmo de proyección ('ultimo_mes', 'promedio_movil', 'run_rate').
 * @param array $params Parámetros adicionales (ej. {meses_moviles => 3}).
 * @return float Retorna el valor proyectado calculado.
 */
function ppto_integracion_proyeccion_generar($mysqli, $id_proyecto, $periodo, $metodo = 'ultimo_mes', $params = array()) {
    $periodo = (int)$periodo;
    $anio = isset($params['anio']) ? (int)$params['anio'] : (int)date('Y');
    $clean_proy = $mysqli->real_escape_string(trim($id_proyecto));
    $Emp_Cod = ppto_integracion_emp_id($mysqli, $id_proyecto);

    $proyeccion = 0.00;

    switch (strtolower(trim($metodo))) {
        case 'promedio_movil':
            $num_meses = isset($params['meses_moviles']) ? (int)$params['meses_moviles'] : 3;
            $sql = "SELECT AVG(prd_real) AS promedio 
                    FROM (
                        SELECT prd_real FROM pre_prod_periodos 
                        WHERE proy_id = '$clean_proy' AND Emp_Cod = $Emp_Cod AND prd_anio = $anio AND prd_mes < $periodo AND prd_real > 0
                        ORDER BY prd_mes DESC LIMIT $num_meses
                    ) sub";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                $proyeccion = round((float)$row['promedio'], 2);
            }
            break;

        case 'run_rate':
            // Suma del acumulado real del año / meses transcurridos con producción
            $sql = "SELECT SUM(prd_real) AS suma_real, COUNT(prd_real) AS meses_con_datos 
                    FROM pre_prod_periodos 
                    WHERE proy_id = '$clean_proy' AND Emp_Cod = $Emp_Cod AND prd_anio = $anio AND prd_real > 0";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                $count = (int)$row['meses_con_datos'];
                if ($count > 0) {
                    $proyeccion = round((float)$row['suma_real'] / $count, 2);
                }
            }
            break;

        case 'esperada':
            // Usa el plan base esperado como proyección
            $res_esp = $mysqli->query("SELECT prd_esperada FROM pre_prod_periodos
                WHERE proy_id='$clean_proy' AND Emp_Cod=$Emp_Cod AND prd_anio=$anio AND prd_mes=$periodo LIMIT 1");
            if ($res_esp && ($row_esp = $res_esp->fetch_assoc())) {
                $proyeccion = round((float)$row_esp['prd_esperada'], 2);
            }
            break;

        case 'ultimo_mes':
        default:
            $mes_anterior = ($periodo > 1) ? ($periodo - 1) : 1;
            $sql = "SELECT prd_real FROM pre_prod_periodos 
                    WHERE proy_id = '$clean_proy' AND Emp_Cod = $Emp_Cod AND prd_anio = $anio AND prd_mes = $mes_anterior LIMIT 1";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                $proyeccion = (float)$row['prd_real'];
            } else {
                // Fallback: Si no hay mes anterior real, buscamos el último real disponible del año
                $res_last = $mysqli->query("SELECT prd_proyectada FROM pre_prod_periodos
                    WHERE proy_id='$clean_proy' AND Emp_Cod=$Emp_Cod AND prd_anio=$anio
                    ORDER BY prd_mes DESC LIMIT 1");
                if ($res_last && ($row_l = $res_last->fetch_assoc())) {
                    $proyeccion = (float)$row_l['prd_proyectada'];
                }
            }
            break;
    }

    if ($proyeccion <= 0.0001) {
        $res_fb = $mysqli->query("SELECT SUM(prd_real) AS acum FROM pre_prod_periodos
            WHERE proy_id='$clean_proy' AND Emp_Cod=$Emp_Cod AND prd_anio=$anio AND prd_mes <= $periodo");
        if ($res_fb && ($rfb = $res_fb->fetch_assoc())) {
            $proyeccion = round((float)$rfb['acum'] / max(1, $periodo), 2);
        }
    }

    // Persistir el valor proyectado
    ppto_integracion_produccion_registrar($mysqli, $id_proyecto, $periodo, $proyeccion, 'proyectada', $anio);

    return $proyeccion;
}

/**
 * Motor dislocador: Sincroniza dinámicamente y recalcula la cascada entera de presupuestos
 * cuando la producción real difiere de la esperada.
 *
 * @param mysqli $mysqli Conexión activa.
 * @param string|null $proyecto_id Filtro por proyecto o null para procesar todos.
 * @param int|null $anio Año fiscal.
 * @return array Resumen del proceso {proyectos_procesados, rubros_recalculados}
 */
function ppto_integracion_recalcular_cascada($mysqli, $proyecto_id = null, $anio = null) {
    $anio = ($anio !== null) ? (int)$anio : (int)date('Y');
    $Emp_Cod = ppto_integracion_emp_id($mysqli, $proyecto_id);

    // 1. Obtener la versión presupuestaria activa del año
    $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'ppe_anio' => $anio));
    if (!$ppe_id) {
        return array('proyectos_procesados' => 0, 'rubros_recalculados' => 0);
    }

    $cond_proy = ($proyecto_id !== null) ? " AND r.Pro_Cod = '" . $mysqli->real_escape_string($proyecto_id) . "' " : "";

    // 2. Consultar los rubros analíticos que dependen de la producción física
    $sql = "SELECT r.Pdp_Cod AS id_rubro, r.Pro_Cod AS proy_id, r.Bas_Cod AS base, r.Ppe_Cod AS ppe_id 
            FROM pre_proyecto_detalles r
            INNER JOIN pre_presupuesto pp ON r.Ppe_Cod = pp.Ppe_Cod
            WHERE pp.Emp_Cod = $Emp_Cod AND pp.Ppe_Ani = $anio AND pp.Ppe_Est = 'A' $cond_proy";
    
    $res = $mysqli->query($sql);
    $rubros_recalculados = 0;
    $proyectos_afectados = array();

    if ($res) {
        while ($rubro = $res->fetch_assoc()) {
            $id_rubro = (int)$rubro['id_rubro'];
            $proy_id = $rubro['proy_id'];
            $proyectos_afectados[$proy_id] = true;

            // Recalcular los 12 meses del rubro obteniendo la producción configurada
            for ($periodo = 1; $periodo <= 12; $periodo++) {
                $ext_prod = ppto_prod_obtener($mysqli, $proy_id, $periodo, array('Emp_Cod' => $Emp_Cod, 'anio' => $anio));
                $valor_produccion_real = (float)$ext_prod['valor'];

                // Registrar el valor real en la tabla de periodos
                ppto_integracion_produccion_registrar($mysqli, $proy_id, $periodo, $valor_produccion_real, 'real', $anio);

                // Ejecutar el motor de cálculo del rubro enviando la nueva producción como override
                ppto_motor_calcular_rubro($mysqli, $id_rubro, $periodo, array('toneladas' => $valor_produccion_real));
                $rubros_recalculados++;
            }
        }
    }

    return array(
        'proyectos_procesados' => count($proyectos_afectados),
        'rubros_recalculados' => $rubros_recalculados
    );
}
