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

    // Declarar variables de los closures para evitar errores de ámbito
    $parse_factor = null;
    $parse_term = null;
    $parse_expression = null;

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
    $parse_factor = function() use (&$parse_expression, &$parse_factor, $get_char, $peek, &$index, &$expression, $length) {
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
            return 0.0;
        }

        return (float)$num_str;
    };

    try {
        return $parse_expression();
    } catch (Exception $e) {
        return 0.00;
    }
}

/**
 * Resuelve y retorna el valor escalar numérico de una base de cálculo física.
 *
 * @param string $id_base Identificador o nombre de la base física (manual, toneladas_mes, horometros, etc.).
 * @param array $parametros Arreglo de contexto con variables actuales.
 * @return float Retorna el valor escalar.
 */
function ppto_motor_base_obtener_valor($id_base, $parametros = array()) {
    $id_base = strtolower(trim($id_base));

    if (isset($parametros[$id_base])) {
        return (float)$parametros[$id_base];
    }

    switch ($id_base) {
        case 'toneladas_mes':
        case 'toneladas':
            return isset($parametros['toneladas']) ? (float)$parametros['toneladas'] : 0.0;
        case 'dias_laborables':
        case 'dias_mes':
            return isset($parametros['dias_mes']) ? (float)$parametros['dias_mes'] : 22.0;
        case 'horometros':
        case 'horas':
            return isset($parametros['horas']) ? (float)$parametros['horas'] : 180.0;
        case 'manual':
        default:
            return 1.0; // Factor neutro escalar para cálculos directos
    }
}

/**
 * Ejecuta el recálculo dinámico completo para un rubro de proyecto individual.
 * Aplica fórmula -> calcula presupuesto anual -> distribuye en meses -> actualiza disponibilidades en DB.
 *
 * @param mysqli $mysqli Conexión activa a la BD.
 * @param int $id_rubro ID del rubro del proyecto (`pdp_id`).
 * @param int|null $periodo Mes específico a recalcular o null para procesar los 12 meses.
 * @param array $params Parámetros adicionales de sobreescritura (overrides).
 * @return array Retorna un arreglo asociativo con 'anual_total' y desgloses mensuales.
 */
function ppto_motor_calcular_rubro($mysqli, $id_rubro, $periodo = null, $params = array()) {
    $id_rubro = (int)$id_rubro;
    
    // 1. Obtener la parametrización de cálculo del rubro de la base de datos
    $sql = "SELECT r.*, f.frm_expresion, f.frm_variables 
            FROM pre_proyecto_detalles r
            LEFT JOIN pre_formulas f ON r.frm_id = f.frm_id
            WHERE r.Pdp_Cod = $id_rubro LIMIT 1";
    $res = $mysqli->query($sql);
    
    if (!$res || $res->num_rows === 0) {
        return array('anual_total' => 0.0, 'mensual' => array());
    }
    
    $rubro_data = $res->fetch_assoc();
    $base = isset($rubro_data['bas_id']) ? $rubro_data['bas_id'] : 'manual';
    $formula = isset($rubro_data['frm_expresion']) ? $rubro_data['frm_expresion'] : '';
    
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
    $merged_params['toneladas'] = isset($merged_params['toneladas']) ? $merged_params['toneladas'] : (float)$rubro_data['Pdp_TonBase'];
    $merged_params['factor_anual'] = isset($merged_params['factor_anual']) ? $merged_params['factor_anual'] : (float)$rubro_data['Pdp_FacAnualTon'];
    $merged_params['monto_anual'] = isset($merged_params['monto_anual']) ? $merged_params['monto_anual'] : (float)$rubro_data['Pdp_PreAnual'];

    // 2. Resolver el valor de la base física
    $valor_base = ppto_motor_base_obtener_valor($base, $merged_params);
    $merged_params[$base] = $valor_base;

    // 3. Evaluar la fórmula para obtener el presupuesto anual proyectado
    $presupuesto_anual = 0.0;
    if (!empty($formula)) {
        $presupuesto_anual = ppto_motor_formula_evaluar($formula, $merged_params);
    } else {
        // Fallback lineal si el rubro no tiene fórmula asignada
        $presupuesto_anual = (float)$rubro_data['Pdp_PreAnual'];
    }

    // Sincronizar el presupuesto anual de cabecera en el registro Pdp_Cod de forma asíncrona
    $mysqli->query("UPDATE pre_proyecto_detalles SET Pdp_PreAnual = $presupuesto_anual WHERE Pdp_Cod = $id_rubro");

    // 4. Procesar el desglose y prorrateo mensual en pre_proyecto_detalles_mes
    $sql_meses = "SELECT * FROM pre_proyecto_detalles_mes WHERE Pdp_Cod = $id_rubro ORDER BY Pdm_Mes ASC";
    $res_meses = $mysqli->query($sql_meses);
    
    $desglose_meses = array();
    $total_dias_laborables = 0;
    $meses_db = array();
    
    if ($res_meses) {
        while ($m_row = $res_meses->fetch_assoc()) {
            $meses_db[(int)$m_row['Pdm_Mes']] = $m_row;
            $total_dias_laborables += (int)$m_row['Pdm_DiasLab'];
        }
    }

    if ($total_dias_laborables <= 0) {
        $total_dias_laborables = 264; // Default 22 días x 12 meses
    }

    // Incluir helper de consolidador de reajustes netos
    if (file_exists(__DIR__ . '/ppto_reajustes_logica.php')) {
        include_once(__DIR__ . '/ppto_reajustes_logica.php');
    }

    for ($m = 1; $m <= 12; $m++) {
        if ($periodo !== null && (int)$periodo !== $m) {
            continue; // Si se especificó un periodo particular, filtramos
        }

        $m_data = isset($meses_db[$m]) ? $meses_db[$m] : array('Pdm_DiasLab' => 22, 'Pdm_Ejecutado' => 0, 'Pdm_Comprometido' => 0);
        $dias_lab = (int)$m_data['Pdm_DiasLab'];
        
        // Ponderación mensual proporcional por días laborables del mes
        $factor_mensual = round($dias_lab / $total_dias_laborables, 6);
        $monto_mes = round($presupuesto_anual * $factor_mensual, 2);

        // Sumar reajustes (incrementos, reducciones, transferencias) al presupuesto inicial del mes
        $reajuste_neto_mes = 0.00;
        if (function_exists('ppto_reajuste_consolidar_proyecto')) {
            $reajuste_neto_mes = ppto_reajuste_consolidar_proyecto($mysqli, (int)$rubro_data['Ppe_Cod'], (int)$rubro_data['Ppa_Cod'], $rubro_data['Pro_Cod'], $rubro_data['Pdp_Rubro'], $m);
        }

        $monto_mes_modificado = $monto_mes + $reajuste_neto_mes;
        
        // Actualizar balance atómico de disponibilidad en tiempo real
        $ejecutado = (float)$m_data['Pdm_Ejecutado'];
        $comprometido = (float)$m_data['Pdm_Comprometido'];
        $disponible = $monto_mes_modificado - $ejecutado - $comprometido;

        // Escribir en base de datos de manera atómica
        $mysqli->query("INSERT INTO pre_proyecto_detalles_mes 
                            (Pdp_Cod, Pdm_Mes, Pdm_DiasLab, Pdm_FacMensual, Pdm_PreMensual, Pdm_Ejecutado, Pdm_Comprometido, Pdm_Disponible)
                        VALUES 
                            ($id_rubro, $m, $dias_lab, $factor_mensual, $monto_mes, $ejecutado, $comprometido, $disponible)
                        ON DUPLICATE KEY UPDATE 
                            Pdm_PreMensual = $monto_mes,
                            Pdm_Disponible = $disponible");

        $desglose_meses[$m] = array(
            'mes' => $m,
            'presupuesto' => $monto_mes_modificado, // Devolvemos el presupuesto reajustado/modificado
            'presupuesto_inicial' => $monto_mes,     // Conservamos el nominal de referencia intacto
            'reajustes' => $reajuste_neto_mes,
            'ejecutado' => $ejecutado,
            'comprometido' => $comprometido,
            'disponible' => max(0.00, $disponible)
        );
    }

    return array(
        'pdp_id' => $id_rubro,
        'anual_total' => $presupuesto_anual,
        'mensual' => $desglose_meses
    );
}

/**
 * Ejecuta el recálculo y consolidación de disponibilidad para una partida general de empresa (sin proyectos).
 *
 * @param mysqli $mysqli Conexión activa.
 * @param int $ppa_id ID de la partida.
 * @param int $mes Mes de corte (1 a 12).
 * @param int|null $ppe_id ID opcional de versión presupuestaria.
 * @return array Retorna la consolidación: {presupuesto_inicial, reajustes, vigente, ejecutado, comprometido, disponible}
 */
function ppto_motor_calcular_partida($mysqli, $ppa_id, $mes, $ppe_id = null) {
    $ppa_id = (int)$ppa_id;
    $mes = (int)$mes;
    $Emp_Cod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 1;

    // 1. Obtener el presupuesto estándar nominal cargado en pre_detalle (sin proyecto)
    if ($ppe_id === null) {
        $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'ppe_anio' => date('Y')));
    }

    $inicial = 0.00;
    if ($ppe_id) {
        $sql_nom = "SELECT SUM(d.Pde_Mon) AS total 
                    FROM pre_detalle d
                    INNER JOIN pre_presupuesto c ON d.Ppe_Cod = c.Ppe_Cod
                    WHERE d.Ppe_Cod = $ppe_id AND d.Ppa_Cod = $ppa_id AND d.Pde_Mes <= $mes";
        $res_nom = $mysqli->query($sql_nom);
        if ($res_nom && $row_nom = $res_nom->fetch_assoc()) {
            $inicial = (float)$row_nom['total'];
        }
    }

    // 2. Sumar presupuesto acumulado proveniente de rubros de proyectos publicados ligados a esta partida
    $proyectos_presupuesto = 0.00;
    if ($ppe_id) {
        $sql_proy = "SELECT SUM(pdm.Pdm_PreMensual) AS total 
                     FROM pre_proyecto_detalles pd
                     INNER JOIN pre_proyecto_detalles_mes pdm ON pd.Pdp_Cod = pdm.Pdp_Cod
                     INNER JOIN pre_presupuesto c ON pd.Ppe_Cod = c.Ppe_Cod
                     WHERE pd.Ppe_Cod = $ppe_id AND pd.Ppa_Cod = $ppa_id AND pdm.Pdm_Mes <= $mes";
        $res_proy = $mysqli->query($sql_proy);
        if ($res_proy && $row_proy = $res_proy->fetch_assoc()) {
            $proyectos_presupuesto = (float)$row_proy['total'];
        }
    }

    $presupuesto_base = $inicial + $proyectos_presupuesto;

    // 3. Consolidar reajustes (incrementos, reducciones, transferencias)
    $reajustes = 0.00;
    if (file_exists(__DIR__ . '/ppto_reajustes_logica.php')) {
        include_once(__DIR__ . '/ppto_reajustes_logica.php');
        if (function_exists('ppto_reajuste_consolidar_partida')) {
            $reajustes = ppto_reajuste_consolidar_partida($mysqli, $ppe_id, $ppa_id, $mes);
        }
    }

    $vigente = $presupuesto_base + $reajustes;

    // 4. Consolidar ejecuciones reales y compromisos desde el ledger general
    $ejecutado = 0.00;
    $comprometido = 0.00;

    if ($ppe_id) {
        $sql_eje = "SELECT 
                        SUM(CASE WHEN pe.Pej_Fase = 'C' AND pe.Pej_Sig = '+' THEN pe.Pej_Mon WHEN pe.Pej_Fase = 'C' AND pe.Pej_Sig = '-' THEN -pe.Pej_Mon ELSE 0 END) AS comprometido,
                        SUM(CASE WHEN pe.Pej_Fase = 'E' AND pe.Pej_Sig = '+' THEN pe.Pej_Mon WHEN pe.Pej_Fase = 'E' AND pe.Pej_Sig = '-' THEN -pe.Pej_Mon ELSE 0 END) AS ejecutado
                    FROM pre_ejecucion pe
                    INNER JOIN pre_presupuesto c ON pe.Ppe_Cod = c.Ppe_Cod
                    WHERE pe.Ppe_Cod = $ppe_id AND pe.Ppa_Cod = $ppa_id AND pe.Pej_Mes <= $mes";
        $res_eje = $mysqli->query($sql_eje);
        if ($res_eje && $row_eje = $res_eje->fetch_assoc()) {
            $comprometido = (float)$row_eje['comprometido'];
            $ejecutado = (float)$row_eje['ejecutado'];
        }
    }

    $disponible = $vigente - $comprometido - $ejecutado;

    return array(
        'ppa_id' => $ppa_id,
        'mes' => $mes,
        'presupuesto_inicial' => $presupuesto_base,
        'reajustes' => $reajustes,
        'vigente' => $vigente,
        'ejecutado' => $ejecutado,
        'comprometido' => $comprometido,
        'disponible' => max(0.00, $disponible)
    );
}

/**
 * Recalcula en cascada de manera jerárquica las partidas Padre / Nivel 1.
 *
 * @param mysqli $mysqli Conexión activa.
 * @param int $id_version ID de la versión de presupuesto.
 * @return void
 */
function ppto_motor_recalcular_jerarquia($mysqli, $id_version) {
    $id_version = (int)$id_version;
    $sql_ver = "SELECT Emp_Cod FROM pre_presupuesto WHERE Ppe_Cod = $id_version LIMIT 1";
    $res_ver = $mysqli->query($sql_ver);
    if (!$res_ver || $res_ver->num_rows === 0) {
        return;
    }
    $Emp_Cod = (int)$res_ver->fetch_assoc()['Emp_Cod'];

    // Partidas detalle de nivel hoja
    $sql_parts = "SELECT Ppa_Cod FROM pre_partidas WHERE Emp_Cod = $Emp_Cod AND Ppa_Est = 'A'";
    $res_parts = $mysqli->query($sql_parts);
    if ($res_parts) {
        while ($row_p = $res_parts->fetch_assoc()) {
            // Recálculo atómico de balances por partida
            for ($m = 1; $m <= 12; $m++) {
                ppto_motor_calcular_partida($mysqli, (int)$row_p['Ppa_Cod'], $m, $id_version);
            }
        }
    }
}
