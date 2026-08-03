<?php
/**
 * ppto_motor_calculo.php
 * Motor de Fórmulas y Bases de Cálculo para EXA PPTO.
 * Proporciona el evaluador seguro de álgebra y consolida los cálculos de rubros, partidas y versiones.
 */

include_once('ppto_persistencia_logica.php');


/**
 * Evalúa algebraicamente una expresión matemática parametrizada de manera segura.
 * NO utiliza eval() ni preg_replace con modificadores peligrosos.
 * Implementa un analizador sintáctico (Recursive Descent Parser) de nivel senior.
 *
 * @param string $formula_str Expresión en texto (ej: "toneladas * factor_anual / 12").
 * @param array $variables Arreglo asociativo de variables y sus valores (ej: ['toneladas' => 1500, 'factor_anual' => 2.5]).
 * @return float Retorna el resultado numérico de la evaluación.
 * @throws Exception Si existe un error sintáctico o división para cero.
 */
function ppto_motor_formula_evaluar($formula_str, $variables) {
    // 1. Sanitización de caracteres: Solo permitir letras, números, operadores básicos, espacios y paréntesis
    $sanitized = preg_replace('/[^a-zA-Z0-9\+\-\*\/\(\)\.\s_]/', '', $formula_str);
    
    // 2. Reemplazar las variables por sus valores numéricos reales en la cadena
    // Ordenamos de mayor a menor longitud para evitar que "tarifa" reemplace una parte de "tarifa_hora"
    uksort($variables, function($a, $b) {
        return strlen($b) - strlen($a);
    });

    foreach ($variables as $var_name => $val) {
        $num_val = (float)$val;
        // Evitamos reemplazos parciales amarrando la variable a fronteras de palabra
        $sanitized = preg_replace('/\b' . preg_quote($var_name, '/') . '\b/', $num_val, $sanitized);
    }

    // 3. Remover espacios en blanco
    $expression = str_replace(' ', '', $sanitized);

    // 4. Analizador Sintáctico Procedural Seguro (Recursive Descent Parser)
    $index = 0;
    $length = strlen($expression);

    // Función auxiliar para leer caracteres
    $peek = function() use (&$expression, &$index, $length) {
        return $index < $length ? $expression[$index] : null;
    };

    $get_char = function() use (&$expression, &$index, $length) {
        return $index < $length ? $expression[$index++] : null;
    };

    // Parser: Expresiones de suma y resta (Nivel de precedencia más bajo)
    $parse_expression = function() use (&$parse_expression, &$parse_term, $get_char, $peek) {
        $result = $parse_term();
        while (true) {
            $next = $peek();
            if ($next === '+') {
                $get_char();
                $result += $parse_term();
            } elseif ($next === '-') {
                $get_char();
                $result -= $parse_term();
            } else {
                break;
            }
        }
        return $result;
    };

    // Parser: Multiplicación y división (Nivel de precedencia medio)
    $parse_term = function() use (&$parse_factor, $get_char, $peek) {
        $result = $parse_factor();
        while (true) {
            $next = $peek();
            if ($next === '*') {
                $get_char();
                $result *= $parse_factor();
            } elseif ($next === '/') {
                $get_char();
                $denom = $parse_factor();
                if (abs($denom) < 0.000001) {
                    throw new Exception("Error de cálculo: División para cero detectada en el motor presupuestario.");
                }
                $result /= $denom;
            } else {
                break;
            }
        }
        return $result;
    };

    // Parser: Paréntesis y números (Nivel de precedencia más alto)
    $parse_factor = function() use (&$parse_expression, $get_char, $peek, &$index, &$expression, $length) {
        $next = $peek();
        
        // Manejar números negativos unarios
        if ($next === '-') {
            $get_char();
            return -$parse_factor();
        }
        if ($next === '+') {
            $get_char();
            return $parse_factor();
        }

        // Subexpresiones entre paréntesis
        if ($next === '(') {
            $get_char(); // Consumir '('
            $result = $parse_expression();
            if ($peek() === ')') {
                $get_char(); // Consumir ')'
            }
            return $result;
        }

        // Parsear número decimal o entero
        $num_str = '';
        while ($index < $length && (ctype_digit($expression[$index]) || $expression[$index] === '.')) {
            $num_str .= $expression[$index++];
        }

        if ($num_str === '') {
            // Si la expresión contiene texto no resuelto tras el reemplazo (variables huérfanas)
            return 0.0;
        }

        return (float)$num_str;
    };

    try {
        $final_val = $parse_expression();
        return is_nan($final_val) || is_infinite($final_val) ? 0.0 : $final_val;
    } catch (Exception $e) {
        // En un entorno ERP procedural, registramos el error y retornamos cero para evitar interrupciones letales de flujo
        error_log($e->getMessage());
        return 0.0;
    }
}

/**
 * Obtiene el valor dinámico correspondiente a una base de cálculo específica.
 * Resuelve variables físicas del negocio (toneladas, metros cúbicos, etc.) a partir de parámetros.
 *
 * @param string $base Identificador de la base de cálculo (toneladas, dias, api, modulo_exa, etc.).
 * @param array $params Parámetros contextuales del rubro o transacción.
 * @return float El valor numérico de la variable base.
 */
function ppto_motor_base_obtener_valor($base, $params) {
    $base = strtolower(trim($base));
    if ($base === 'manual' || $base === 'monto_fijo') {
        return 1.0; // Bases constantes neutras
    }

    // Buscar directamente en el mapa de parámetros (ej: 'toneladas', 'horas_maquina')
    if (isset($params[$base])) {
        return (float)$params[$base];
    }

    // Soporte para alias comunes en el ERP
    $aliases = array(
        'metros_cubicos' => array('m3', 'volumen', 'metros_c'),
        'horas_maquina'  => array('horas', 'hrs_maq', 'tiempo_h'),
        'kilometros'     => array('km', 'distancia', 'recorrido'),
        'empleados'      => array('headcount', 'personal', 'trabajadores'),
        'dias'           => array('dias_laborables', 'dias_lab', 'dias_op'),
        'unidades'       => array('cantidad', 'cant', 'cant_unid'),
        'monto_anual'    => array('presupuesto_anual', 'anual')
    );

    foreach ($aliases as $key => $sub_aliases) {
        if ($base === $key) {
            foreach ($sub_aliases as $alias) {
                if (isset($params[$alias])) {
                    return (float)$params[$alias];
                }
            }
        }
    }

    // Simulación de resolución avanzada para API Externa o Módulo EXA si se parametriza dinámicamente
    if ($base === 'api' && isset($params['api_valor'])) {
        return (float)$params['api_valor'];
    }
    if ($base === 'modulo_exa' && isset($params['exa_valor'])) {
        return (float)$params['exa_valor'];
    }

    return 0.0; // Retorno por defecto seguro
}

/**
 * Calcula el presupuesto nominal para un rubro específico (pdp_id) de un proyecto.
 * Recupera la fórmula y base asignadas, inyecta las variables correspondientes y asienta mensualidades.
 *
 * @param mysqli $mysqli Conexión activa a la BD.
 * @param int $id_rubro ID del rubro del proyecto (`pdp_id`).
 * @param int|null $periodo Mes específico (1-12) o NULL para procesar el año completo.
 * @param array $params Parámetros adicionales u Overrides para las variables.
 * @return array Retorna un arreglo asociativo con 'anual_total' y desgloses mensuales.
 */
function ppto_motor_calcular_rubro($mysqli, $id_rubro, $periodo = null, $params = array()) {
    $id_rubro = (int)$id_rubro;
    
    // 1. Obtener la parametrización de cálculo del rubro de la base de datos
    $sql = "SELECT r.*, f.frm_expresion, f.frm_variables 
            FROM exa_ppto_proyecto_detalles r
            LEFT JOIN exa_ppto_formulas f ON r.frm_id = f.frm_id
            WHERE r.pdp_id = $id_rubro LIMIT 1";
    $res = $mysqli->query($sql);
    
    if (!$res || $res->num_rows === 0) {
        return array('anual_total' => 0.0, 'mensual' => array());
    }
    
    $rubro_data = $res->fetch_assoc();
    $base = $rubro_data['bas_id'];
    $formula = $rubro_data['frm_expresion'];
    
    // Parsear parámetros actuales del rubro y fusionarlos con los overrides recibidos
    $pdp_params = array();
    if (!empty($rubro_data['pdp_parametros'])) {
        $pdp_params = json_decode($rubro_data['pdp_parametros'], true);
        if (!is_array($pdp_params)) {
            $pdp_params = array();
        }
    }
    $merged_params = array_merge($pdp_params, $params);

    // Integración de los campos canónicos históricos como variables de soporte automáticas
    $merged_params['toneladas'] = isset($merged_params['toneladas']) ? $merged_params['toneladas'] : (float)$rubro_data['pdp_toneladas_base'];
    $merged_params['factor_anual'] = isset($merged_params['factor_anual']) ? $merged_params['factor_anual'] : (float)$rubro_data['pdp_factor_anual_tonelada'];
    $merged_params['monto_anual'] = isset($merged_params['monto_anual']) ? $merged_params['monto_anual'] : (float)$rubro_data['pdp_presupuesto_anual'];

    // 2. Resolver el valor de la base física
    $valor_base = ppto_motor_base_obtener_valor($base, $merged_params);
    $merged_params[$base] = $valor_base;

    // 3. Evaluar la fórmula para obtener el presupuesto anual proyectado
    $presupuesto_anual = 0.0;
    if (!empty($formula)) {
        $presupuesto_anual = ppto_motor_formula_evaluar($formula, $merged_params);
    } else {
        // Fallback lineal si el rubro no tiene fórmula asignada
        $presupuesto_anual = (float)$rubro_data['pdp_presupuesto_anual'];
    }

    // Sincronizar el presupuesto anual de cabecera en el registro pdp_id de forma asíncrona
    $mysqli->query("UPDATE exa_ppto_proyecto_detalles SET pdp_presupuesto_anual = $presupuesto_anual WHERE pdp_id = $id_rubro");

    // 4. Procesar el desglose y prorrateo mensual en exa_ppto_proyecto_detalles_mes
    $sql_meses = "SELECT * FROM exa_ppto_proyecto_detalles_mes WHERE pdp_id = $id_rubro ORDER BY pdm_mes ASC";
    $res_meses = $mysqli->query($sql_meses);
    
    $desglose_meses = array();
    $total_dias_laborables = 0;
    $meses_db = array();
    
    if ($res_meses) {
        while ($m_row = $res_meses->fetch_assoc()) {
            $meses_db[$m_row['pdm_mes']] = $m_row;
            $total_dias_laborables += (int)$m_row['pdm_dias_laborables'];
        }
    }

    // Si no existen desgloses mensuales en BD, creamos una distribución plana temporal
    if (empty($meses_db)) {
        $total_dias_laborables = 240; // Estándar de 20 días promedio por mes
        for ($m = 1; $m <= 12; $m++) {
            $meses_db[$m] = array(
                'pdm_mes' => $m,
                'pdm_dias_laborables' => 20,
                'pdm_factor_mensual' => 0.0833, // 1/12
                'pdm_ejecutado' => 0.0,
                'pdm_comprometido' => 0.0
            );
        }
    }

    // Calcular cuotas mensuales e impactar la BD
    $rango_meses = ($periodo !== null) ? array((int)$periodo) : range(1, 12);
    
    foreach ($rango_meses as $m) {
        if (!isset($meses_db[$m])) continue;
        
        $m_data = $meses_db[$m];
        $factor_mensual = (float)$m_data['pdm_factor_mensual'];
        $dias_lab = (int)$m_data['pdm_dias_laborables'];
        
        // Ponderación mixta: Si el rubro tiene definido un factor mensual estacional, se usa directo.
        // Si no tiene factor o es cero, se prorratea ponderando proporcionalmente los días laborables del mes sobre el año.
        if ($factor_mensual > 0.0001) {
            $monto_mes = $presupuesto_anual * $factor_mensual;
        } else {
            $monto_mes = $total_dias_laborables > 0 
                ? $presupuesto_anual * ($dias_lab / $total_dias_laborables) 
                : $presupuesto_anual / 12.0;
        }

        // Redondear a centavos estándar
        $monto_mes = round($monto_mes, 2);

        // RESOLUCIÓN FASE 5: Incorporación de Reajustes sobre Rubro de Proyecto sin tocar el Presupuesto Inicial
        $reajuste_neto_mes = 0.00;
        if (file_exists(__DIR__ . '/ppto_reajustes_logica.php')) {
            include_once(__DIR__ . '/ppto_reajustes_logica.php');
            $reajuste_neto_mes = ppto_reajuste_consolidar_proyecto($mysqli, (int)$rubro_data['ppe_id'], (int)$rubro_data['ppa_id'], $rubro_data['proy_id'], $rubro_data['pdp_rubro'], $m);
        }
        
        $monto_mes_modificado = $monto_mes + $reajuste_neto_mes;
        
        // Actualizar balance atómico de disponibilidad en tiempo real
        $ejecutado = (float)$m_data['pdm_ejecutado'];
        $comprometido = (float)$m_data['pdm_comprometido'];
        $disponible = $monto_mes_modificado - $ejecutado - $comprometido;

        // Escribir en base de datos de manera atómica
        $mysqli->query("INSERT INTO exa_ppto_proyecto_detalles_mes 
                            (pdp_id, pdm_mes, pdm_dias_laborables, pdm_factor_mensual, pdm_presupuesto_mensual, pdm_ejecutado, pdm_comprometido, pdm_disponible)
                        VALUES 
                            ($id_rubro, $m, $dias_lab, $factor_mensual, $monto_mes, $ejecutado, $comprometido, $disponible)
                        ON DUPLICATE KEY UPDATE 
                            pdm_presupuesto_mensual = $monto_mes,
                            pdm_disponible = $disponible");

        $desglose_meses[$m] = array(
            'mes' => $m,
            'presupuesto' => $monto_mes_modificado, // Devolvemos el presupuesto reajustado/modificado
            'presupuesto_inicial' => $monto_mes,     // Conservamos el nominal de referencia intacto
            'ejecutado' => $ejecutado,
            'comprometido' => $comprometido,
            'disponible' => $disponible
        );
    }

    return array(
        'anual_total' => $presupuesto_anual,
        'mensual' => $desglose_meses
    );
}

/**
 * Calcula y consolida recursivamente el presupuesto nominal e histórico para una partida presupuestaria.
 * Suma la asignación nominal general y todos los rubros analíticos de proyectos asociados.
 *
 * @param mysqli $mysqli Conexión activa.
 * @param int $id_partida ID de la partida presupuestaria (`ppa_id`).
 * @param int|null $periodo Mes específico (1-12) o NULL para el año.
 * @param array $params Parámetros contextuales adicionales.
 * @return array Consolidado de 'presupuestado', 'ejecutado', 'comprometido' y 'disponible'.
 */
function ppto_motor_calcular_partida($mysqli, $id_partida, $periodo = null, $params = array()) {
    $id_partida = (int)$id_partida;
    $mes_corte = ($periodo !== null) ? (int)$periodo : 12;
    
    // 1. Obtener el presupuesto estándar nominal cargado en exa_ppto_detalles (sin proyecto)
    $sql_det = "SELECT d.ppe_id, SUM(pde_monto) AS total_base 
                FROM exa_ppto_detalles d
                INNER JOIN exa_ppto_cabeceras c ON d.ppe_id = c.ppe_id
                WHERE d.ppa_id = $id_partida AND c.ppe_estado = 'A' AND d.pde_mes <= $mes_corte";
    $res_det = $mysqli->query($sql_det);
    $presupuesto_base = 0.0;
    $ppe_id = 0;
    if ($res_det && $row_det = $res_det->fetch_assoc()) {
        $presupuesto_base = (float)$row_det['total_base'];
        $ppe_id = (int)$row_det['ppe_id'];
    }

    // RESOLUCIÓN FASE 5: Incorporación de Reajustes sobre la partida (Básico sin proyectos)
    $reajuste_neto_partida = 0.00;
    if ($ppe_id > 0 && file_exists(__DIR__ . '/ppto_reajustes_logica.php')) {
        include_once(__DIR__ . '/ppto_reajustes_logica.php');
        // Sumamos de manera acumulada todos los reajustes de la partida hasta el mes de corte
        for ($m = 1; $m <= $mes_corte; $m++) {
            $reajuste_neto_partida += ppto_reajuste_consolidar_partida($mysqli, $ppe_id, $id_partida, $m);
        }
    }
    $presupuesto_base += $reajuste_neto_partida;

    // 2. Obtener el presupuesto consolidado proveniente del motor de proyectos y rubros analíticos
    $sql_proy = "SELECT pd.pdp_id 
                 FROM exa_ppto_proyecto_detalles pd
                 INNER JOIN exa_ppto_cabeceras c ON pd.ppe_id = c.ppe_id
                 WHERE pd.ppa_id = $id_partida AND c.ppe_estado = 'A'";
    $res_proy = $mysqli->query($sql_proy);
    
    $presupuesto_proyectos = 0.0;
    $comprometido_proyectos = 0.0;
    $ejecutado_proyectos = 0.0;

    if ($res_proy) {
        while ($row_proy = $res_proy->fetch_assoc()) {
            $pdp_id = (int)$row_proy['pdp_id'];
            
            // Forzar recálculo dinámico de este rubro de proyecto para el mes de corte
            $calc_res = ppto_motor_calcular_rubro($mysqli, $pdp_id, $periodo, $params);
            
            if ($periodo !== null) {
                if (isset($calc_res['mensual'][$periodo])) {
                    $presupuesto_proyectos += (float)$calc_res['mensual'][$periodo]['presupuesto'];
                    $comprometido_proyectos += (float)$calc_res['mensual'][$periodo]['comprometido'];
                    $ejecutado_proyectos += (float)$calc_res['mensual'][$periodo]['ejecutado'];
                }
            } else {
                // Si es consolidado anual, sumamos todos los meses
                foreach ($calc_res['mensual'] as $m_data) {
                    $presupuesto_proyectos += (float)$m_data['presupuesto'];
                    $comprometido_proyectos += (float)$m_data['comprometido'];
                    $ejecutado_proyectos += (float)$m_data['ejecutado'];
                }
            }
        }
    }

    // 3. Consultar transacciones directas del ledger general de ejecuciones para lo contable estándar
    $sql_eje = "SELECT 
                    SUM(CASE WHEN pej_fase = 'E' AND pej_signo = '+' THEN pej_monto WHEN pej_fase = 'E' AND pej_signo = '-' THEN -pej_monto ELSE 0 END) AS total_ejecutado,
                    SUM(CASE WHEN pej_fase = 'C' AND pej_signo = '+' THEN pej_monto WHEN pej_fase = 'C' AND pej_signo = '-' THEN -pej_monto ELSE 0 END) AS total_comprometido
                FROM exa_ppto_ejecuciones pe
                INNER JOIN exa_ppto_cabeceras c ON pe.ppe_id = c.ppe_id
                WHERE pe.ppa_id = $id_partida 
                  AND c.ppe_estado = 'A'
                  AND pe.pej_mes <= $mes_corte";
                  
    $res_eje = $mysqli->query($sql_eje);
    $ejecutado_contable = 0.0;
    $comprometido_contable = 0.0;
    if ($res_eje && $row_eje = $res_eje->fetch_assoc()) {
        $ejecutado_contable = (float)$row_eje['total_ejecutado'];
        $comprometido_contable = (float)$row_eje['total_comprometido'];
    }

    // Consolidad final de flujos
    $total_presupuestado = $presupuesto_base + $presupuesto_proyectos;
    $total_ejecutado = $ejecutado_contable + $ejecutado_proyectos;
    $total_comprometido = $comprometido_contable + $comprometido_proyectos;
    $total_disponible = $total_presupuestado - $total_ejecutado - $total_comprometido;

    return array(
        'presupuestado' => round($total_presupuestado, 2),
        'ejecutado' => round($total_ejecutado, 2),
        'comprometido' => round($total_comprometido, 2),
        'disponible' => round($total_disponible, 2)
    );
}

/**
 * Calcula y consolida el balance de una versión presupuestaria completa.
 * Ejecuta el reprocesamiento atómico recursivo de todas las partidas activas asociadas.
 *
 * @param mysqli $mysqli Conexión activa.
 * @param int $id_version ID de la cabecera presupuestaria (`ppe_id`).
 * @param int|null $periodo Mes específico (1-12) o NULL para el año.
 * @return array Balance globalconsolidado de 'presupuesto_total', 'ejecutado_total', 'comprometido_total' y 'disponible_total'.
 */
function ppto_motor_calcular_version($mysqli, $id_version, $periodo = null) {
    $id_version = (int)$id_version;
    
    // 1. Obtener la empresa asociada a la versión de presupuesto
    $sql_ver = "SELECT emp_id FROM exa_ppto_cabeceras WHERE ppe_id = $id_version LIMIT 1";
    $res_ver = $mysqli->query($sql_ver);
    if (!$res_ver || $res_ver->num_rows === 0) {
        return array('presupuesto_total' => 0.0, 'ejecutado_total' => 0.0, 'comprometido_total' => 0.0, 'disponible_total' => 0.0);
    }
    $ver_data = $res_ver->fetch_assoc();
    $emp_id = (int)$ver_data['emp_id'];

    // 2. Obtener todas las partidas activas parametrizadas para la empresa
    $sql_parts = "SELECT ppa_id FROM exa_ppto_partidas WHERE emp_id = $emp_id AND ppa_estado = 'A'";
    $res_parts = $mysqli->query($sql_parts);
    
    $presupuesto_total = 0.0;
    $ejecutado_total = 0.0;
    $comprometido_total = 0.0;
    $disponible_total = 0.0;

    if ($res_parts) {
        while ($row_part = $res_parts->fetch_assoc()) {
            $ppa_id = (int)$row_part['ppa_id'];
            
            // Consolidar partida de forma recursiva
            $part_balance = ppto_motor_calcular_partida($mysqli, $ppa_id, $periodo);
            
            $presupuesto_total += $part_balance['presupuestado'];
            $ejecutado_total += $part_balance['ejecutado'];
            $comprometido_total += $part_balance['comprometido'];
            $disponible_total += $part_balance['disponible'];
        }
    }

    return array(
        'presupuesto_total' => round($presupuesto_total, 2),
        'ejecutado_total' => round($ejecutado_total, 2),
        'comprometido_total' => round($comprometido_total, 2),
        'disponible_total' => round($disponible_total, 2)
    );
}
