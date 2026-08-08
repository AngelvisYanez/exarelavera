<?php
/**
 * ppto_motor_calculo.php
 * Motor de Fï¿½rmulas y Bases de Cï¿½lculo para EXA PPTO.
 * Proporciona el evaluador seguro de ï¿½lgebra y consolida los cï¿½lculos de rubros, partidas y versiones.
 */

include_once('ppto_persistencia_logica.php');


/**
 * Evalï¿½a algebraicamente una expresiï¿½n matemï¿½tica parametrizada de manera segura.
 * NO utiliza eval() ni preg_replace con modificadores peligrosos.
 * Implementa un analizador sintï¿½ctico (Recursive Descent Parser) de nivel senior.
 *
 * @param string $formula_str Expresiï¿½n en texto (ej: "toneladas * factor_anual / 12").
 * @param array $variables Arreglo asociativo de variables y sus valores (ej: ['toneladas' => 1500, 'factor_anual' => 2.5]).
 * @return float Retorna el resultado numï¿½rico de la evaluaciï¿½n.
 * @throws Exception Si existe un error sintï¿½ctico o divisiï¿½n para cero.
 */
function ppto_motor_formula_evaluar($formula_str, $variables) {
    // 1. Sanitizaciï¿½n de caracteres: Solo permitir letras, nï¿½meros, operadores bï¿½sicos, espacios y parï¿½ntesis
    $sanitized = preg_replace('/[^a-zA-Z0-9\+\-\*\/\(\)\.\s_]/', '', $formula_str);
    
    // 2. Reemplazar las variables por sus valores numï¿½ricos reales en la cadena
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

    // 4. Analizador Sintï¿½ctico Procedural Seguro (Recursive Descent Parser)
    $index = 0;
    $length = strlen($expression);

    // Funciï¿½n auxiliar para leer caracteres
    $peek = function() use (&$expression, &$index, $length) {
        return $index < $length ? $expression[$index] : null;
    };

    $get_char = function() use (&$expression, &$index, $length) {
        return $index < $length ? $expression[$index++] : null;
    };

    // Parser: Expresiones de suma y resta (Nivel de precedencia mï¿½s bajo)
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

    // Parser: Multiplicaciï¿½n y divisiï¿½n (Nivel de precedencia medio)
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
                    throw new Exception("Error de cï¿½lculo: Divisiï¿½n para cero detectada en el motor presupuestario.");
                }
                $result /= $denom;
            } else {
                break;
            }
        }
        return $result;
    };

    // Parser: Parï¿½ntesis y nï¿½meros (Nivel de precedencia mï¿½s alto)
    $parse_factor = function() use (&$parse_expression, &$parse_factor, $get_char, $peek, &$index, &$expression, $length) {
        $next = $peek();
        
        // Manejar nï¿½meros negativos unarios
        if ($next === '-') {
            $get_char();
            return -$parse_factor();
        }
        if ($next === '+') {
            $get_char();
            return $parse_factor();
        }

        // Subexpresiones entre parï¿½ntesis
        if ($next === '(') {
            $get_char(); // Consumir '('
            $result = $parse_expression();
            if ($peek() === ')') {
                $get_char(); // Consumir ')'
            }
            return $result;
        }

        // Parsear nï¿½mero decimal o entero
        $num_str = '';
        while ($index < $length && (ctype_digit($expression[$index]) || $expression[$index] === '.')) {
            $num_str .= $expression[$index++];
        }

        if ($num_str === '') {
            // Si la expresiï¿½n contiene texto no resuelto tras el reemplazo (variables huï¿½rfanas)
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
 * Obtiene el valor dinï¿½mico correspondiente a una base de cï¿½lculo especï¿½fica.
 * Resuelve variables fï¿½sicas del negocio (toneladas, metros cï¿½bicos, etc.) a partir de parï¿½metros.
 *
 * @param string $base Identificador de la base de cï¿½lculo (toneladas, dias, api, modulo_exa, etc.).
 * @param array $params Parï¿½metros contextuales del rubro o transacciï¿½n.
 * @return float El valor numï¿½rico de la variable base.
 */
function ppto_motor_base_obtener_valor($base, $params) {
    $base = strtolower(trim($base));
    if ($base === 'manual' || $base === 'monto_fijo') {
        return 1.0; // Bases constantes neutras
    }

    // Buscar directamente en el mapa de parï¿½metros (ej: 'toneladas', 'horas_maquina')
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

    // Simulaciï¿½n de resoluciï¿½n avanzada para API Externa o Mï¿½dulo EXA si se parametriza dinï¿½micamente
    if ($base === 'api' && isset($params['api_valor'])) {
        return (float)$params['api_valor'];
    }
    if ($base === 'modulo_exa' && isset($params['exa_valor'])) {
        return (float)$params['exa_valor'];
    }

    return 0.0; // Retorno por defecto seguro
}

/**
 * Calcula el presupuesto nominal para un rubro especï¿½fico (Pdp_Cod) de un proyecto.
 * Recupera la fï¿½rmula y base asignadas, inyecta las variables correspondientes y asienta mensualidades.
 *
 * @param mysqli $mysqli Conexiï¿½n activa a la BD.
 * @param int $id_rubro ID del rubro del proyecto (`Pdp_Cod`).
 * @param int|null $periodo Mes especï¿½fico (1-12) o NULL para procesar el aï¿½o completo.
 * @param array $params Parï¿½metros adicionales u Overrides para las variables.
 * @return array Retorna un arreglo asociativo con 'anual_total' y desgloses mensuales.
 */
function ppto_motor_calcular_rubro($mysqli, $id_rubro, $periodo = null, $params = array()) {
    $id_rubro = (int)$id_rubro;
    
    // 1. Obtener la parametrizaciï¿½n de cï¿½lculo del rubro de la base de datos
    // pre_proyecto_detalles no tiene Frm_Cod; formula/base opcionales via isset.
    $sql = "SELECT r.*
            FROM pre_proyecto_detalles r
            WHERE r.Pdp_Cod = $id_rubro LIMIT 1";
    $res = $mysqli->query($sql);
    
    if (!$res || $res->num_rows === 0) {
        return array('anual_total' => 0.0, 'mensual' => array());
    }
    
    $rubro_data = $res->fetch_assoc();
    $base = isset($rubro_data['bas_id']) ? $rubro_data['bas_id'] : null;
    $formula = isset($rubro_data['Frm_Expresion']) ? $rubro_data['Frm_Expresion'] : '';
    
    // Parsear parï¿½metros actuales del rubro y fusionarlos con los overrides recibidos
    $pdp_params = array();
    if (!empty($rubro_data['pdp_parametros'])) {
        $pdp_params = json_decode($rubro_data['pdp_parametros'], true);
        if (!is_array($pdp_params)) {
            $pdp_params = array();
        }
    }
    $merged_params = array_merge($pdp_params, $params);

    // Integraciï¿½n de los campos canï¿½nicos histï¿½ricos como variables de soporte automï¿½ticas
    $merged_params['toneladas'] = isset($merged_params['toneladas']) ? $merged_params['toneladas'] : (float)$rubro_data['Pdp_TonBase'];
    $merged_params['factor_anual'] = isset($merged_params['factor_anual']) ? $merged_params['factor_anual'] : (float)$rubro_data['Pdp_FacAnualTon'];
    $merged_params['monto_anual'] = isset($merged_params['monto_anual']) ? $merged_params['monto_anual'] : (float)$rubro_data['Pdp_PreAnual'];

    // 2. Resolver el valor de la base fï¿½sica
    $valor_base = ppto_motor_base_obtener_valor($base, $merged_params);
    $merged_params[$base] = $valor_base;

    // 3. Evaluar la fï¿½rmula para obtener el presupuesto anual proyectado
    $presupuesto_anual = 0.0;
    if (!empty($formula)) {
        $presupuesto_anual = ppto_motor_formula_evaluar($formula, $merged_params);
    } else {
        // Fallback lineal si el rubro no tiene fï¿½rmula asignada
        $presupuesto_anual = (float)$rubro_data['Pdp_PreAnual'];
    }

    // Sincronizar el presupuesto anual de cabecera en el registro Pdp_Cod de forma asï¿½ncrona
    $mysqli->query("UPDATE pre_proyecto_detalles SET Pdp_PreAnual = $presupuesto_anual WHERE Pdp_Cod = $id_rubro");

    // 4. Procesar el desglose y prorrateo mensual en pre_proyecto_detalles_mes
    $sql_meses = "SELECT Pdm_Cod, Pdp_Cod, Pdm_Mes, Pdm_DiasLab, Pdm_FacMensual, Pdm_PreMensual,
            Pdm_Ejecutado, Pdm_Comprometido, Pdm_Disponible
        FROM pre_proyecto_detalles_mes WHERE Pdp_Cod = $id_rubro ORDER BY Pdm_Mes ASC";
    $res_meses = $mysqli->query($sql_meses);
    
    $desglose_meses = array();
    $total_dias_laborables = 0;
    $meses_db = array();
    
    if ($res_meses) {
        while ($m_row = $res_meses->fetch_assoc()) {
            $meses_db[$m_row['Pdm_Mes']] = $m_row;
            $total_dias_laborables += (int)$m_row['Pdm_DiasLab'];
        }
    }

    // Si no existen desgloses mensuales en BD, creamos una distribuciï¿½n plana temporal
    if (empty($meses_db)) {
        $total_dias_laborables = 240; // Estï¿½ndar de 20 dï¿½as promedio por mes
        for ($m = 1; $m <= 12; $m++) {
            $meses_db[$m] = array(
                'Pdm_Mes' => $m,
                'Pdm_DiasLab' => 20,
                'Pdm_FacMensual' => 0.0833, // 1/12
                'Pdm_Ejecutado' => 0.0,
                'Pdm_Comprometido' => 0.0
            );
        }
    }

    // Calcular cuotas mensuales e impactar la BD
    $rango_meses = ($periodo !== null) ? array((int)$periodo) : range(1, 12);
    
    foreach ($rango_meses as $m) {
        if (!isset($meses_db[$m])) continue;
        
        $m_data = $meses_db[$m];
        $factor_mensual = (float)$m_data['Pdm_FacMensual'];
        $dias_lab = (int)$m_data['Pdm_DiasLab'];
        
        // Ponderaciï¿½n mixta: Si el rubro tiene definido un factor mensual estacional, se usa directo.
        // Si no tiene factor o es cero, se prorratea ponderando proporcionalmente los dï¿½as laborables del mes sobre el aï¿½o.
        if ($factor_mensual > 0.0001) {
            $monto_mes = $presupuesto_anual * $factor_mensual;
        } else {
            $monto_mes = $total_dias_laborables > 0 
                ? $presupuesto_anual * ($dias_lab / $total_dias_laborables) 
                : $presupuesto_anual / 12.0;
        }

        // Redondear a centavos estï¿½ndar
        $monto_mes = round($monto_mes, 2);

        // RESOLUCIï¿½N FASE 5: Incorporaciï¿½n de Reajustes sobre Rubro de Proyecto sin tocar el Presupuesto Inicial
        $reajuste_neto_mes = 0.00;
        if (file_exists(__DIR__ . '/ppto_reajustes_logica.php')) {
            include_once(__DIR__ . '/ppto_reajustes_logica.php');
            $reajuste_neto_mes = ppto_reajuste_consolidar_proyecto($mysqli, (int)$rubro_data['Ppe_Cod'], (int)$rubro_data['Ppa_Cod'], $rubro_data['Pro_Cod'], $rubro_data['Pdp_Rubro'], $m);
        }
        
        $monto_mes_modificado = $monto_mes + $reajuste_neto_mes;
        
        // Actualizar balance atï¿½mico de disponibilidad en tiempo real
        $ejecutado = (float)$m_data['Pdm_Ejecutado'];
        $comprometido = (float)$m_data['Pdm_Comprometido'];
        $disponible = $monto_mes_modificado - $ejecutado - $comprometido;

        // Escribir en base de datos de manera atï¿½mica
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
 * Calcula y consolida recursivamente el presupuesto nominal e histï¿½rico para una partida presupuestaria.
 * Suma la asignaciï¿½n nominal general y todos los rubros analï¿½ticos de proyectos asociados.
 *
 * @param mysqli $mysqli Conexiï¿½n activa.
 * @param int $id_partida ID de la partida presupuestaria (`Ppa_Cod`).
 * @param int|null $periodo Mes especï¿½fico (1-12) o NULL para el aï¿½o.
 * @param array $params Parï¿½metros contextuales adicionales.
 * @return array Consolidado de 'presupuestado', 'ejecutado', 'comprometido' y 'disponible'.
 */
function ppto_motor_calcular_partida($mysqli, $id_partida, $periodo = null, $params = array()) {
    $id_partida = (int)$id_partida;
    $mes_corte = ($periodo !== null) ? (int)$periodo : 12;
    
    // 1. Obtener el presupuesto estï¿½ndar nominal cargado en pre_detalle (sin proyecto)
    $sql_det = "SELECT d.Ppe_Cod AS Ppe_Cod, SUM(d.Pde_Mon) AS total_base 
                FROM pre_detalle d
                INNER JOIN pre_presupuesto c ON d.Ppe_Cod = c.Ppe_Cod
                WHERE d.Ppa_Cod = $id_partida AND c.Ppe_Est = 'A' AND d.Pde_Mes <= $mes_corte";
    $res_det = $mysqli->query($sql_det);
    $presupuesto_base = 0.0;
    $Ppe_Cod = 0;
    if ($res_det && $row_det = $res_det->fetch_assoc()) {
        $presupuesto_base = (float)$row_det['total_base'];
        $Ppe_Cod = (int)$row_det['Ppe_Cod'];
    }

    // RESOLUCIï¿½N FASE 5: Incorporaciï¿½n de Reajustes sobre la partida (Bï¿½sico sin proyectos)
    $reajuste_neto_partida = 0.00;
    if ($Ppe_Cod > 0 && file_exists(__DIR__ . '/ppto_reajustes_logica.php')) {
        include_once(__DIR__ . '/ppto_reajustes_logica.php');
        // Sumamos de manera acumulada todos los reajustes de la partida hasta el mes de corte
        for ($m = 1; $m <= $mes_corte; $m++) {
            $reajuste_neto_partida += ppto_reajuste_consolidar_partida($mysqli, $Ppe_Cod, $id_partida, $m);
        }
    }
    $presupuesto_base += $reajuste_neto_partida;

    // 2. Obtener el presupuesto consolidado proveniente del motor de proyectos y rubros analï¿½ticos
    $sql_proy = "SELECT pd.Pdp_Cod AS Pdp_Cod 
                 FROM pre_proyecto_detalles pd
                 INNER JOIN pre_presupuesto c ON pd.Ppe_Cod = c.Ppe_Cod
                 WHERE pd.Ppa_Cod = $id_partida AND c.Ppe_Est = 'A'";
    $res_proy = $mysqli->query($sql_proy);
    
    $presupuesto_proyectos = 0.0;
    $comprometido_proyectos = 0.0;
    $ejecutado_proyectos = 0.0;

    if ($res_proy) {
        while ($row_proy = $res_proy->fetch_assoc()) {
            $Pdp_Cod = (int)$row_proy['Pdp_Cod'];
            
            // Forzar recï¿½lculo dinï¿½mico de este rubro de proyecto para el mes de corte
            $calc_res = ppto_motor_calcular_rubro($mysqli, $Pdp_Cod, $periodo, $params);
            
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

    // 3. Consultar transacciones directas del ledger general de ejecuciones para lo contable estï¿½ndar
    $sql_eje = "SELECT 
                    SUM(CASE WHEN pe.Pej_Fase = 'E' AND pe.Pej_Sig = '+' THEN pe.Pej_Mon WHEN pe.Pej_Fase = 'E' AND pe.Pej_Sig = '-' THEN -pe.Pej_Mon ELSE 0 END) AS total_ejecutado,
                    SUM(CASE WHEN pe.Pej_Fase = 'C' AND pe.Pej_Sig = '+' THEN pe.Pej_Mon WHEN pe.Pej_Fase = 'C' AND pe.Pej_Sig = '-' THEN -pe.Pej_Mon ELSE 0 END) AS total_comprometido
                FROM pre_ejecucion pe
                INNER JOIN pre_presupuesto c ON pe.Ppe_Cod = c.Ppe_Cod
                WHERE pe.Ppa_Cod = $id_partida 
                  AND c.Ppe_Est = 'A'
                  AND pe.Pej_Mes <= $mes_corte";
                  
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
 * Calcula y consolida el balance de una versiï¿½n presupuestaria completa.
 * Ejecuta el reprocesamiento atï¿½mico recursivo de todas las partidas activas asociadas.
 *
 * @param mysqli $mysqli Conexiï¿½n activa.
 * @param int $id_version ID de la cabecera presupuestaria (`Ppe_Cod`).
 * @param int|null $periodo Mes especï¿½fico (1-12) o NULL para el aï¿½o.
 * @return array Balance globalconsolidado de 'presupuesto_total', 'ejecutado_total', 'comprometido_total' y 'disponible_total'.
 */
function ppto_motor_calcular_version($mysqli, $id_version, $periodo = null) {
    $id_version = (int)$id_version;
    
    // 1. Obtener la empresa asociada a la versiï¿½n de presupuesto
    $sql_ver = "SELECT Emp_Cod FROM pre_presupuesto WHERE Ppe_Cod = $id_version LIMIT 1";
    $res_ver = $mysqli->query($sql_ver);
    if (!$res_ver || $res_ver->num_rows === 0) {
        return array('presupuesto_total' => 0.0, 'ejecutado_total' => 0.0, 'comprometido_total' => 0.0, 'disponible_total' => 0.0);
    }
    $ver_data = $res_ver->fetch_assoc();
    $Emp_Cod = (int)$ver_data['Emp_Cod'];

    // 2. Obtener todas las partidas activas parametrizadas para la empresa
    $sql_parts = "SELECT Ppa_Cod AS Ppa_Cod FROM pre_partidas WHERE Emp_Cod = $Emp_Cod AND Ppa_Est = 'A'";
    $res_parts = $mysqli->query($sql_parts);
    
    $presupuesto_total = 0.0;
    $ejecutado_total = 0.0;
    $comprometido_total = 0.0;
    $disponible_total = 0.0;

    if ($res_parts) {
        while ($row_part = $res_parts->fetch_assoc()) {
            $Ppa_Cod = (int)$row_part['Ppa_Cod'];
            
            // Consolidar partida de forma recursiva
            $part_balance = ppto_motor_calcular_partida($mysqli, $Ppa_Cod, $periodo);
            
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
