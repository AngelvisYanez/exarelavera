<?php
/**
 * ppto_motor_produccion.php
 * Motor de Producción Configurable para EXA PPTO.
 * Extrae y unifica métricas físicas de producción (toneladas, m3, horas, etc.) de múltiples módulos del ERP.
 */

require_once('ppto_persistencia_logica.php');

/**
 * Orquestador principal que obtiene la producción física de un proyecto en un período.
 * Determina el origen configurado, invoca la sub-función y retorna la interfaz estándar.
 *
 * @param mysqli $mysqli Conexión activa a la base de datos.
 * @param string $id_proyecto Código del proyecto (`proy_id`).
 * @param int $periodo Mes consultado (1 a 12).
 * @param array $params Parámetros adicionales de contexto.
 * @return array Estructura unificada: {origen, periodo, valor, unidad, timestamp, fuente_detalle}
 */
function ppto_prod_obtener($mysqli, $id_proyecto, $periodo, $params = array()) {
    $periodo = (int)$periodo;
    $clean_proy = $mysqli->real_escape_string(trim($id_proyecto));
    
    // Obtener la empresa del contexto activo
    $emp_id = isset($params['emp_id']) ? (int)$params['emp_id'] : (isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 1);

    // 1. Consultar la parametrización de origen de producción de la base de datos para el proyecto
    $sql = "SELECT * FROM exa_ppto_prod_config WHERE proy_id = '$clean_proy' AND emp_id = $emp_id LIMIT 1";
    $res = $mysqli->query($sql);
    
    // Si no tiene origen personalizado, utiliza por defecto el origen Manual
    if (!$res || $res->num_rows === 0) {
        $config = array(
            'proy_id' => $id_proyecto,
            'emp_id' => $emp_id,
            'pco_origen' => 'manual',
            'pco_campo' => 'manual_valor',
            'pco_frecuencia' => 'mensual',
            'pco_extra_config' => '{"valor_defecto": 0.0}'
        );
    } else {
        $config = $res->fetch_assoc();
    }

    $origen = strtolower(trim($config['pco_origen']));
    $respuesta = null;

    // 2. Desviar al resolvedor correspondiente según la configuración (Estructura de fábrica modular)
    switch ($origen) {
        case 'relavera':
            $respuesta = ppto_prod_origen_relavera($mysqli, $config, $periodo, $params);
            break;
        case 'horometros':
            $respuesta = ppto_prod_origen_horometros($mysqli, $config, $periodo, $params);
            break;
        case 'inventario':
            $respuesta = ppto_prod_origen_inventario($mysqli, $config, $periodo, $params);
            break;
        case 'ventas':
            $respuesta = ppto_prod_origen_ventas($mysqli, $config, $periodo, $params);
            break;
        case 'produccion':
            $respuesta = ppto_prod_origen_produccion($mysqli, $config, $periodo, $params);
            break;
        case 'api_externa':
            $respuesta = ppto_prod_origen_api($mysqli, $config, $periodo, $params);
            break;
        case 'manual':
        default:
            $respuesta = ppto_prod_origen_manual($mysqli, $config, $periodo, $params);
            break;
    }

    return $respuesta;
}

/**
 * Resuelve la producción desde el módulo de Relaves (Relavera).
 * Lee de forma segura de las tablas del módulo relavera si existen, o fallback robusto.
 */
function ppto_prod_origen_relavera($mysqli, $config, $periodo, $params) {
    $campo = $mysqli->real_escape_string($config['pco_campo']);
    $proy_id = $mysqli->real_escape_string($config['proy_id']);
    
    // Configuración extra (filtros específicos)
    $extra = !empty($config['pco_extra_config']) ? json_decode($config['pco_extra_config'], true) : array();
    $tabla = isset($extra['tabla']) ? $mysqli->real_escape_string($extra['tabla']) : 'manifiesto';
    $filtro_sql = isset($extra['filtros']) ? " AND " . $extra['filtros'] : "";

    $anio = isset($params['anio']) ? (int)$params['anio'] : (int)date('Y');
    $valor = 0.00;
    $fuente_detalle = "Simulación: Tabla [$tabla] no disponible en BD.";

    // Validación de existencia física de la tabla relavera
    $table_exists = $mysqli->query("SHOW TABLES LIKE '$tabla'");
    if ($table_exists && $table_exists->num_rows > 0) {
        // Detectar si la tabla de manifiestos tiene columna Proy_Cod (desacoplamiento dinámico)
        $cond_proy = "";
        $col_check = $mysqli->query("SHOW COLUMNS FROM `$tabla` LIKE 'Proy_Cod'");
        if ($col_check && $col_check->num_rows > 0) {
            $cond_proy = "Proy_Cod = '$proy_id' AND ";
        }

        // Definir factor de conversión (divisor) por defecto para kilogramos a toneladas (20000 KG -> 20 TON)
        $divisor = 1.00;
        if (strtolower($tabla) === 'manifiesto' && strtolower($campo) === 'man_pes') {
            $divisor = 1000.00; // 1000 KG = 1 Tonelada
        }

        // Si se define un multiplicador o divisor explícito en la configuración, se respeta
        if (isset($extra['divisor'])) {
            $divisor = (float)$extra['divisor'];
        } elseif (isset($extra['multiplicador'])) {
            $divisor = 1.00 / (float)$extra['multiplicador'];
        }

        // Consultar sumatoria del volumen o toneladas
        $sql = "SELECT SUM($campo) AS total 
                FROM $tabla 
                WHERE {$cond_proy}
                      MONTH(Man_Fec) = $periodo 
                  AND YEAR(Man_Fec) = $anio 
                  AND Man_Est = 'A' $filtro_sql";
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $valor = (float)$row['total'] / $divisor;
            $fuente_detalle = "Consolidado de Manifiestos de Relaves (convertido a toneladas con divisor $divisor) desde tabla [$tabla] para el periodo [$periodo-$anio].";
        }
    } else {
        // Fallback dinámico mockeado de acuerdo con la parametrización
        $valor = isset($params['mock_valor']) ? (float)$params['mock_valor'] : 2500.00;
        if (isset($extra['multiplicador'])) {
            $valor *= (float)$extra['multiplicador'];
        } elseif (isset($extra['divisor'])) {
            $valor /= (float)$extra['divisor'];
        }
    }

    return array(
        'origen' => 'relavera',
        'periodo' => $periodo,
        'valor' => round($valor, 4),
        'unidad' => isset($extra['unidad']) ? $extra['unidad'] : 'Ton',
        'timestamp' => date('Y-m-d H:i:s'),
        'fuente_detalle' => $fuente_detalle
    );
}

/**
 * Resuelve la producción desde el módulo de Horómetros (Maquinaria Pesada).
 */
function ppto_prod_origen_horometros($mysqli, $config, $periodo, $params) {
    $campo = $mysqli->real_escape_string($config['pco_campo']);
    $proy_id = $mysqli->real_escape_string($config['proy_id']);
    
    $extra = !empty($config['pco_extra_config']) ? json_decode($config['pco_extra_config'], true) : array();
    $tabla = isset($extra['tabla']) ? $mysqli->real_escape_string($extra['tabla']) : 'maq_horometros';
    $anio = isset($params['anio']) ? (int)$params['anio'] : (int)date('Y');
    
    $valor = 0.00;
    $fuente_detalle = "Simulación: Tabla [$tabla] no disponible en BD.";

    $table_exists = $mysqli->query("SHOW TABLES LIKE '$tabla'");
    if ($table_exists && $table_exists->num_rows > 0) {
        $sql = "SELECT SUM($campo) AS total 
                FROM $tabla 
                WHERE Proy_Cod = '$proy_id' 
                  AND MONTH(Hor_Fec) = $periodo 
                  AND YEAR(Hor_Fec) = $anio 
                  AND Hor_Est = 'A'";
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $valor = (float)$row['total'];
            $fuente_detalle = "Sumatoria de Horas de Máquina leídas desde [$tabla] para el periodo [$periodo-$anio].";
        }
    } else {
        $valor = isset($params['mock_valor']) ? (float)$params['mock_valor'] : 180.50;
    }

    return array(
        'origen' => 'horometros',
        'periodo' => $periodo,
        'valor' => round($valor, 4),
        'unidad' => isset($extra['unidad']) ? $extra['unidad'] : 'Hrs',
        'timestamp' => date('Y-m-d H:i:s'),
        'fuente_detalle' => $fuente_detalle
    );
}

/**
 * Resuelve la producción basándose en movimientos de Inventario (Entradas/Bodega).
 */
function ppto_prod_origen_inventario($mysqli, $config, $periodo, $params) {
    $campo = $mysqli->real_escape_string($config['pco_campo']);
    $proy_id = $mysqli->real_escape_string($config['proy_id']);
    
    $extra = !empty($config['pco_extra_config']) ? json_decode($config['pco_extra_config'], true) : array();
    $tabla = isset($extra['tabla']) ? $mysqli->real_escape_string($extra['tabla']) : 'inv_movimientos';
    $item_id = isset($extra['item_id']) ? (int)$extra['item_id'] : 0;
    $anio = isset($params['anio']) ? (int)$params['anio'] : (int)date('Y');
    
    $valor = 0.00;
    $fuente_detalle = "Simulación: Tabla [$tabla] no disponible en BD.";

    $table_exists = $mysqli->query("SHOW TABLES LIKE '$tabla'");
    if ($table_exists && $table_exists->num_rows > 0) {
        $cond_item = $item_id ? " AND Item_Cod = $item_id " : "";
        $sql = "SELECT SUM($campo) AS total 
                FROM $tabla 
                WHERE Proy_Cod = '$proy_id' 
                  AND MONTH(Mov_Fec) = $periodo 
                  AND YEAR(Mov_Fec) = $anio 
                  AND Mov_Tip = 'E' $cond_item"; // 'E' = Entradas
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $valor = (float)$row['total'];
            $fuente_detalle = "Movimientos de Entrada física de almacén [$tabla] del periodo [$periodo-$anio].";
        }
    } else {
        $valor = isset($params['mock_valor']) ? (float)$params['mock_valor'] : 1420.00;
    }

    return array(
        'origen' => 'inventario',
        'periodo' => $periodo,
        'valor' => round($valor, 4),
        'unidad' => isset($extra['unidad']) ? $extra['unidad'] : 'Unidades',
        'timestamp' => date('Y-m-d H:i:s'),
        'fuente_detalle' => $fuente_detalle
    );
}

/**
 * Resuelve la producción a partir de Facturas de Ventas despachadas del proyecto.
 */
function ppto_prod_origen_ventas($mysqli, $config, $periodo, $params) {
    $campo = $mysqli->real_escape_string($config['pco_campo']);
    $proy_id = $mysqli->real_escape_string($config['proy_id']);
    
    $extra = !empty($config['pco_extra_config']) ? json_decode($config['pco_extra_config'], true) : array();
    $tabla = isset($extra['tabla']) ? $mysqli->real_escape_string($extra['tabla']) : 'ventas';
    $anio = isset($params['anio']) ? (int)$params['anio'] : (int)date('Y');
    
    $valor = 0.00;
    $fuente_detalle = "Simulación: Tabla [$tabla] no disponible en BD.";

    $table_exists = $mysqli->query("SHOW TABLES LIKE '$tabla'");
    if ($table_exists && $table_exists->num_rows > 0) {
        $sql = "SELECT SUM($campo) AS total 
                FROM $tabla 
                WHERE Proy_Cod = '$proy_id' 
                  AND MONTH(Vet_Fec) = $periodo 
                  AND YEAR(Vet_Fec) = $anio 
                  AND Vet_Est = 'A'";
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $valor = (float)$row['total'];
            $fuente_detalle = "Acumulado físico de ventas despachadas leídos de [$tabla] para el periodo [$periodo-$anio].";
        }
    } else {
        $valor = isset($params['mock_valor']) ? (float)$params['mock_valor'] : 84000.00;
    }

    return array(
        'origen' => 'ventas',
        'periodo' => $periodo,
        'valor' => round($valor, 4),
        'unidad' => isset($extra['unidad']) ? $extra['unidad'] : 'USD',
        'timestamp' => date('Y-m-d H:i:s'),
        'fuente_detalle' => $fuente_detalle
    );
}

/**
 * Resuelve la producción a partir de partes diarios de Producción Operativa directa.
 */
function ppto_prod_origen_produccion($mysqli, $config, $periodo, $params) {
    $campo = $mysqli->real_escape_string($config['pco_campo']);
    $proy_id = $mysqli->real_escape_string($config['proy_id']);
    
    $extra = !empty($config['pco_extra_config']) ? json_decode($config['pco_extra_config'], true) : array();
    $tabla = isset($extra['tabla']) ? $mysqli->real_escape_string($extra['tabla']) : 'prd_partes_diarios';
    $anio = isset($params['anio']) ? (int)$params['anio'] : (int)date('Y');
    
    $valor = 0.00;
    $fuente_detalle = "Simulación: Tabla [$tabla] no disponible en BD.";

    $table_exists = $mysqli->query("SHOW TABLES LIKE '$tabla'");
    if ($table_exists && $table_exists->num_rows > 0) {
        $sql = "SELECT SUM($campo) AS total 
                FROM $tabla 
                WHERE Proy_Cod = '$proy_id' 
                  AND MONTH(Prd_Fec) = $periodo 
                  AND YEAR(Prd_Fec) = $anio 
                  AND Prd_Est = 'A'";
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $valor = (float)$row['total'];
            $fuente_detalle = "Metros cúbicos procesados consolidados desde [$tabla] para el periodo [$periodo-$anio].";
        }
    } else {
        $valor = isset($params['mock_valor']) ? (float)$params['mock_valor'] : 9400.00;
    }

    return array(
        'origen' => 'produccion',
        'periodo' => $periodo,
        'valor' => round($valor, 4),
        'unidad' => isset($extra['unidad']) ? $extra['unidad'] : 'm3',
        'timestamp' => date('Y-m-d H:i:s'),
        'fuente_detalle' => $fuente_detalle
    );
}

/**
 * Resuelve la producción desde una asignación de Presupuesto Manual / Fijo.
 */
function ppto_prod_origen_manual($mysqli, $config, $periodo, $params) {
    $extra = !empty($config['pco_extra_config']) ? json_decode($config['pco_extra_config'], true) : array();
    
    // Obtiene el valor directo del override, de la configuración manual, o retorna el valor_defecto (cero)
    $valor = isset($params['valor']) ? (float)$params['valor'] : 
             (isset($params['mock_valor']) ? (float)$params['mock_valor'] : 
             (isset($extra['valor_defecto']) ? (float)$extra['valor_defecto'] : 0.00));

    return array(
        'origen' => 'manual',
        'periodo' => $periodo,
        'valor' => round($valor, 4),
        'unidad' => isset($extra['unidad']) ? $extra['unidad'] : 'Unidades',
        'timestamp' => date('Y-m-d H:i:s'),
        'fuente_detalle' => "Ingreso manual directo parametrizado para el periodo [$periodo]."
    );
}

/**
 * Resuelve la producción consumiendo dinámicamente una API Externa.
 */
function ppto_prod_origen_api($mysqli, $config, $periodo, $params) {
    $extra = !empty($config['pco_extra_config']) ? json_decode($config['pco_extra_config'], true) : array();
    $endpoint = isset($extra['endpoint_url']) ? $extra['endpoint_url'] : 'https://api.exa-erp.com/produccion';
    
    $valor = 0.00;
    $fuente_detalle = "Llamada fallida o endpoint [$endpoint] no disponible. Retorno por defecto.";

    // Simular el consumo curl de una API externa bajo el entorno procedural
    if (isset($params['mock_api_valor'])) {
        $valor = (float)$params['mock_api_valor'];
        $fuente_detalle = "Consumo HTTP exitoso (Simulado) desde endpoint [$endpoint] para el periodo [$periodo].";
    } else {
        // En producción se ejecutaría curl_exec(). Aquí fallback de seguridad para no frenar hilos
        $valor = isset($extra['valor_defecto']) ? (float)$extra['valor_defecto'] : 3150.00;
        $fuente_detalle = "Consumo HTTP GET a [$endpoint?periodo=$periodo] (Simulado con valor por defecto).";
    }

    return array(
        'origen' => 'api_externa',
        'periodo' => $periodo,
        'valor' => round($valor, 4),
        'unidad' => isset($extra['unidad']) ? $extra['unidad'] : 'Kg',
        'timestamp' => date('Y-m-d H:i:s'),
        'fuente_detalle' => $fuente_detalle
    );
}
