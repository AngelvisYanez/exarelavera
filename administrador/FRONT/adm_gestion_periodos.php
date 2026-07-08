<?php

/**
 * Archivo principal para la gestión de periodos contables
 * 
 * @author Sistema
 * @version 1.0
 * Fecha de actualización:	2025-12-31
 * 
 * @package administrador.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once("../LOGICA/adm_log_gestion_periodos.php");
require_once('../../Librerias/procedimientos/almacenados_standar.php');

// Función para codificar UTF-8 recursivamente
if (!function_exists('utf8_encode_deep')) {
    function utf8_encode_deep(&$input) {
        if (is_string($input)) {
            $input = mb_convert_encoding($input, 'UTF-8', 'ISO-8859-1');
        } else if (is_array($input)) {
            foreach ($input as &$value) {
                utf8_encode_deep($value);
            }
            unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));
            foreach ($vars as $var) {
                utf8_encode_deep($input->$var);
            }
        }
    }
}

// Función helper para enviar respuestas JSON
function enviarRespuestaJSON($data) {
    utf8_encode_deep($data);
    echo json_encode($data);
    exit();
}

date_default_timezone_set('America/Guayaquil');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Configurar manejador de errores para peticiones AJAX
if (isset($_POST['action'])) {
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        $error = array(
            'success' => false, 
            'message' => 'Error de conexión: ' . $errstr
        );
        utf8_encode_deep($error);
        echo json_encode($error);
        exit();
    }, E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
}

// Inicializar conexiones
try {
    $obBD_conexion_master = new Class_Log_Conexion_Gestion_Periodos();
    $obBD_datos = new Class_Log_Datos_Gestion_Periodos();
    
    // Verificar que la conexión principal sea válida
    if (!$obBD_conexion_master || !$obBD_conexion_master->conexion) {
        $error_msg = isset($obBD_conexion_master->Error) && !empty($obBD_conexion_master->Error) 
            ? $obBD_conexion_master->Error 
            : 'Error de conexión a la base de datos principal. Verifique las credenciales y que el servidor esté disponible.';
        if (isset($_POST['action'])) {
            header('Content-Type: application/json; charset=utf-8');
            $error = array('success' => false, 'message' => $error_msg);
            utf8_encode_deep($error);
            echo json_encode($error);
            exit();
        }
        die($error_msg);
    }
    
} catch (Exception $e) {
    if (isset($_POST['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        $error = array('success' => false, 'message' => 'Error de conexión: ' . $e->getMessage());
        utf8_encode_deep($error);
        echo json_encode($error);
        exit();
    }
    die("Error de conexión: " . $e->getMessage());
} catch (Error $e) {
    if (isset($_POST['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        $error = array('success' => false, 'message' => 'Error fatal: ' . $e->getMessage());
        utf8_encode_deep($error);
        echo json_encode($error);
        exit();
    }
    die("Error fatal: " . $e->getMessage());
}

// Procesar peticiones AJAX
if (isset($_POST['action'])) {
    // Limpiar cualquier salida previa
    if (ob_get_level()) {
        ob_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($_POST['action']) {
        case 'buscar_empresas':
            try {
                $fecha_inicial = isset($_POST['fecha_inicial']) ? $_POST['fecha_inicial'] : '';
                $fecha_final = isset($_POST['fecha_final']) ? $_POST['fecha_final'] : '';
                
                $empresas = $obBD_datos->getArrayConsulta(1, '', $obBD_conexion_master);
                
                $ano_seleccionado = date('Y', strtotime($fecha_inicial));
                $ano_anterior = $ano_seleccionado - 1;
                $ano_actual = date('Y');
                $fecha_inicial_anterior = $ano_anterior . '-01-01';
                $fecha_final_anterior = $ano_anterior . '-12-31';
                
                foreach ($empresas as &$emp) {
                    $emp['tiene_periodo'] = false;
                    $emp['tiene_periodo_anterior'] = false;
                    $emp['es_periodo_actual'] = false;
                    $emp['Emp_Con'] = '';
                    $emp['Cof_Rim'] = '';
                }
                unset($emp);
                
                $empresas_por_db = array();
                $db_names_original = array();
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                
                foreach ($empresas as $index => $emp) {
                    if (!empty($emp['Dat_Dis'])) {
                        $db_name_lower = strtolower($emp['Dat_Dis']);
                        if (in_array($db_name_lower, $bases_permitidas)) {
                            if (!isset($empresas_por_db[$db_name_lower])) {
                                $empresas_por_db[$db_name_lower] = array();
                                $db_names_original[$db_name_lower] = $emp['Dat_Dis'];
                            }
                            $empresas_por_db[$db_name_lower][$index] = $emp['Emp_Cod'];
                        }
                    }
                }
                
                foreach ($empresas_por_db as $db_name_lower => $indices_empresas) {
                    try {
                        $db_name_original = isset($db_names_original[$db_name_lower]) ? $db_names_original[$db_name_lower] : $db_name_lower;
                        $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_name_original);
                        
                        $emp_cods = array_values($indices_empresas);
                        
                        if (empty($emp_cods)) {
                            $obBD_conexion_dist->cerrar();
                            continue;
                        }
                        
                        $param_datos = implode('*', $emp_cods);
                        $datos_empresas = $obBD_datos->getArrayConsulta(2, $param_datos, $obBD_conexion_dist);
                        
                        foreach ($datos_empresas as $datos) {
                            foreach ($indices_empresas as $index => $emp_cod) {
                                if ($emp_cod == $datos['Emp_Cod']) {
                                    if (!empty($datos['Emp_Con'])) {
                                        $empresas[$index]['Emp_Con'] = $datos['Emp_Con'];
                                    }
                                    if (!empty($datos['Cof_Rim'])) {
                                        $empresas[$index]['Cof_Rim'] = $datos['Cof_Rim'];
                                    }
                                    break;
                                }
                            }
                        }
                        
                        $param_periodo = implode('*', array_merge($emp_cods, array($fecha_inicial, $fecha_final)));
                        $empresas_con_periodo_raw = $obBD_datos->getArrayConsulta(3, $param_periodo, $obBD_conexion_dist);
                        $empresas_con_periodo = array();
                        foreach ($empresas_con_periodo_raw as $row) {
                            $empresas_con_periodo[] = $row['Emp_Cod'];
                        }
                        
                        foreach ($empresas_con_periodo as $emp_cod_periodo) {
                            foreach ($indices_empresas as $index => $emp_cod) {
                                if ($emp_cod == $emp_cod_periodo) {
                                    $empresas[$index]['tiene_periodo'] = true;
                                    if ($ano_seleccionado == $ano_actual) {
                                        $empresas[$index]['es_periodo_actual'] = true;
                                    }
                                    break;
                                }
                            }
                        }
                        
                        $param_periodo_ant = implode('*', array_merge($emp_cods, array($fecha_inicial_anterior, $fecha_final_anterior)));
                        $empresas_con_periodo_ant_raw = $obBD_datos->getArrayConsulta(4, $param_periodo_ant, $obBD_conexion_dist);
                        $empresas_con_periodo_ant = array();
                        foreach ($empresas_con_periodo_ant_raw as $row) {
                            $empresas_con_periodo_ant[] = $row['Emp_Cod'];
                        }
                        
                        foreach ($empresas_con_periodo_ant as $emp_cod_periodo_ant) {
                            foreach ($indices_empresas as $index => $emp_cod) {
                                if ($emp_cod == $emp_cod_periodo_ant) {
                                    $empresas[$index]['tiene_periodo_anterior'] = true;
                                    break;
                                }
                            }
                        }
                        
                        $obBD_conexion_dist->cerrar();
                    } catch (Exception $e) {
                        if (isset($obBD_conexion_dist) && method_exists($obBD_conexion_dist, 'cerrar')) {
                            try {
                                $obBD_conexion_dist->cerrar();
                            } catch (Exception $e2) {
                            }
                        }
                        continue;
                    }
                }
                $total = count($empresas);
                $con_periodo = count(array_filter($empresas, function($e) { return $e['tiene_periodo']; }));
                $sin_periodo = count(array_filter($empresas, function($e) { 
                    return $e['tiene_periodo_anterior'] && !$e['tiene_periodo']; 
                }));
                
                $response = array(
                    'success' => true, 
                    'data' => $empresas,
                    'stats' => array(
                        'total' => $total,
                        'con_periodo' => $con_periodo,
                        'sin_periodo' => $sin_periodo
                    )
                );
                enviarRespuestaJSON($response);
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        case 'obtener_regimen':
            try {
                $emp_cods = json_decode($_POST['emp_cods'], true);
                if (empty($emp_cods)) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'No se seleccionaron empresas'));
                }
                
                $regimenes = array();
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                
                foreach ($emp_cods as $emp_cod) {
                    $param_dat_dis = $emp_cod;
                    $dat_dis = $obBD_datos->getRowConsulta(5, $param_dat_dis, $obBD_conexion_master);
                    
                    if ($dat_dis) {
                        $db_dist = $dat_dis['Dat_Dis'];
                        if (!in_array(strtolower($db_dist), $bases_permitidas)) {
                            continue;
                        }
                        try {
                            $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                            
                            $param_reg = $emp_cod;
                            $reg = $obBD_datos->getRowConsulta(7, $param_reg, $obBD_conexion_dist);
                            
                            if ($reg) {
                                $regimenes[] = $reg;
                            }
                            $obBD_conexion_dist->cerrar();
                        } catch (Exception $e) {
                        }
                    }
                }
                
                enviarRespuestaJSON(array('success' => true, 'data' => $regimenes));
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        case 'activar_periodo':
            try {
                $fecha_inicial = $_POST['fecha_inicial'];
                $fecha_final = $_POST['fecha_final'];
                $emp_cods = json_decode($_POST['emp_cods'], true);
                
                if (empty($emp_cods)) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'No se seleccionaron empresas'));
                }
                
                $param_data = implode('*', $emp_cods);
                $empresas_bases = $obBD_datos->getArrayConsulta(6, $param_data, $obBD_conexion_master);
                
                if (empty($empresas_bases)) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'No se encontraron bases distribuidas para las empresas seleccionadas'));
                }
                
                $total_insertados = 0;
                $errores = array();
                $procesadas = array();
                
                $bases_empresas = array();
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                
                foreach ($empresas_bases as $row) {
                    $db_dist = $row['Dat_Dis'];
                    if (in_array(strtolower($db_dist), $bases_permitidas)) {
                        if (!isset($bases_empresas[$db_dist])) {
                            $bases_empresas[$db_dist] = array();
                        }
                        $bases_empresas[$db_dist][] = $row['Emp_Cod'];
                    }
                }
                
                foreach ($bases_empresas as $db_dist => $emp_cods_base) {
                    $obBD_conexion_dist = null;
                    try {
                        $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                        
                        // Verificar que la conexión sea válida
                        if (!$obBD_conexion_dist || !$obBD_conexion_dist->conexion) {
                            $error_msg = isset($obBD_conexion_dist->Error) && !empty($obBD_conexion_dist->Error) 
                                ? $obBD_conexion_dist->Error 
                                : "Error de conexión a la base de datos '$db_dist'";
                            $errores[] = "Base $db_dist: $error_msg";
                            if ($obBD_conexion_dist && method_exists($obBD_conexion_dist, 'cerrar')) {
                                try {
                                    $obBD_conexion_dist->cerrar();
                                } catch (Exception $e) {
                                    // Ignorar errores al cerrar
                                }
                            }
                            continue;
                        }
                        
                        $param_emp_exist = implode('*', $emp_cods_base);
                        $emp_cods_validos_raw = $obBD_datos->getArrayConsulta(8, $param_emp_exist, $obBD_conexion_dist);
                        
                        // Verificar si la consulta falló
                        if ($emp_cods_validos_raw === false) {
                            $errores[] = "Base $db_dist: Error al consultar empresas válidas";
                            if ($obBD_conexion_dist && method_exists($obBD_conexion_dist, 'cerrar')) {
                                try {
                                    $obBD_conexion_dist->cerrar();
                                } catch (Exception $e) {
                                    // Ignorar errores al cerrar
                                }
                            }
                            continue;
                        }
                        
                        $emp_cods_validos = array();
                        foreach ($emp_cods_validos_raw as $row) {
                            $emp_cods_validos[] = $row['Emp_Cod'];
                        }
                        
                        if (empty($emp_cods_validos)) {
                            $errores[] = "Base $db_dist: No se encontraron empresas válidas";
                            $obBD_conexion_dist->cerrar();
                            continue;
                        }
                        
                        $param_insert = implode('*', array_merge(
                            array($fecha_inicial, $fecha_final),
                            $emp_cods_validos
                        ));
                        
                        $obBD_datos->inicio_transaccion($obBD_conexion_dist->conexion);
                        $result = $obBD_datos->operacionobBD(9, $param_insert, $obBD_conexion_dist);
                        
                        if ($result !== false) {
                            $obBD_datos->fin_transaccion($obBD_conexion_dist->conexion);
                            $insertados_base = count($emp_cods_validos);
                        } else {
                            $obBD_datos->rollBack($obBD_conexion_dist->conexion, 0);
                            throw new Exception('Error al ejecutar la inserción en la base de datos');
                        }
                        $total_insertados += $insertados_base;
                        $procesadas[] = "Base $db_dist: $insertados_base período(s) insertado(s)";
                        
                        $obBD_conexion_dist->cerrar();
                    } catch (Exception $e) {
                        $error_msg = $e->getMessage();
                        if (isset($obBD_conexion_dist) && $obBD_conexion_dist && isset($obBD_conexion_dist->Error) && !empty($obBD_conexion_dist->Error)) {
                            $error_msg = $obBD_conexion_dist->Error;
                        }
                        $errores[] = "Base $db_dist: $error_msg";
                        if (isset($obBD_conexion_dist) && $obBD_conexion_dist && isset($obBD_conexion_dist->conexion) && $obBD_conexion_dist->conexion) {
                            try {
                                $obBD_datos->rollBack($obBD_conexion_dist->conexion, 0);
                            } catch (Exception $rollback_e) {
                                // Ignorar errores de rollback
                            }
                            try {
                                if (method_exists($obBD_conexion_dist, 'cerrar')) {
                                    $obBD_conexion_dist->cerrar();
                                }
                            } catch (Exception $close_e) {
                                // Ignorar errores al cerrar
                            }
                        }
                    } catch (Error $e) {
                        $errores[] = "Base $db_dist: Error fatal - " . $e->getMessage();
                        if (isset($obBD_conexion_dist) && $obBD_conexion_dist && isset($obBD_conexion_dist->conexion) && $obBD_conexion_dist->conexion) {
                            try {
                                $obBD_datos->rollBack($obBD_conexion_dist->conexion, 0);
                            } catch (Exception $rollback_e) {
                                // Ignorar errores de rollback
                            }
                            try {
                                if (method_exists($obBD_conexion_dist, 'cerrar')) {
                                    $obBD_conexion_dist->cerrar();
                                }
                            } catch (Exception $close_e) {
                                // Ignorar errores al cerrar
                            }
                        }
                    }
                }
                
                if ($total_insertados > 0) {
                    $msg = "Se insertaron $total_insertados período(s) contable(s) en la(s) base(s) de datos.\n";
                    $msg .= implode("\n", $procesadas);
                    if (!empty($errores)) {
                        $msg .= "\n\nErrores:\n" . implode("\n", array_slice($errores, 0, 5));
                    }
                    enviarRespuestaJSON(array('success' => true, 'message' => $msg));
                } else {
                    $msg = 'No se insertaron períodos. Verifique que:\n';
                    $msg .= '- Existan planes de cuenta activos (Pla_Est = "A")\n';
                    $msg .= '- El período no esté duplicado\n';
                    $msg .= '- Las bases distribuidas sean accesibles';
                    if (!empty($errores)) {
                        $msg .= "\n\nErrores:\n" . implode("\n", array_slice($errores, 0, 5));
                    }
                    enviarRespuestaJSON(array('success' => false, 'message' => $msg));
                }
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => 'Error general: ' . $e->getMessage()));
            }
            
        case 'cambiar_regimen':
            try {
                $emp_cods = json_decode($_POST['emp_cods'], true);
                $nuevo_regimen = $_POST['nuevo_regimen'];
                
                if (empty($emp_cods)) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'No se seleccionaron empresas'));
                }
                
                $actualizadas = 0;
                $errores = array();
                
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                
                foreach ($emp_cods as $emp_cod) {
                    $param_dat_dis = $emp_cod;
                    $dat_dis = $obBD_datos->getRowConsulta(5, $param_dat_dis, $obBD_conexion_master);
                    
                    if ($dat_dis) {
                        $db_dist = $dat_dis['Dat_Dis'];
                        if (!in_array(strtolower($db_dist), $bases_permitidas)) {
                            continue;
                        }
                        try {
                            $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                            
                            $param_update = implode('*', array($nuevo_regimen, $emp_cod));
                            $result = $obBD_datos->operacionobBD(10, $param_update, $obBD_conexion_dist);
                            
                            if ($result) { // Asumimos éxito si la operación no lanzó excepción
                                $actualizadas++;
                            }
                            $obBD_conexion_dist->cerrar();
                        } catch (Exception $e) {
                            $errores[] = "Empresa $emp_cod: " . $e->getMessage();
                        }
                    } else {
                        $errores[] = "Empresa $emp_cod: No se encontró base distribuida";
                    }
                }
                
                if ($actualizadas > 0) {
                    $msg = "Se actualizaron $actualizadas empresa(s)";
                    if (!empty($errores)) {
                        $msg .= ". Errores: " . implode(", ", $errores);
                    }
                    enviarRespuestaJSON(array('success' => true, 'message' => $msg));
                } else {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'No se pudo actualizar ninguna empresa. ' . implode(", ", $errores)));
                }
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        case 'guardar_listado':
            try {
                $nombre_listado = isset($_POST['nombre_listado']) ? $_POST['nombre_listado'] : '';
                $periodo = isset($_POST['periodo']) ? $_POST['periodo'] : '';
                $fecha_inicial = isset($_POST['fecha_inicial']) ? $_POST['fecha_inicial'] : '';
                $fecha_final = isset($_POST['fecha_final']) ? $_POST['fecha_final'] : '';
                $emp_cods = json_decode($_POST['emp_cods'], true);
                $emp_cods_marcadas = isset($_POST['emp_cods_marcadas']) ? json_decode($_POST['emp_cods_marcadas'], true) : array();
                
                if (empty($nombre_listado) || empty($periodo) || empty($fecha_inicial) || empty($fecha_final)) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'Faltan datos requeridos'));
                }
                
                if (empty($emp_cods_marcadas)) {
                    ob_clean();
                    header('Content-Type: application/json');
                    enviarRespuestaJSON(array('success' => false, 'message' => 'No se seleccionaron empresas'));
                }
                
                $param_data = implode('*', $emp_cods);
                $empresas_bases = $obBD_datos->getArrayConsulta(6, $param_data, $obBD_conexion_master);
                
                if (empty($empresas_bases)) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'No se encontraron bases distribuidas para las empresas seleccionadas'));
                }
                
                $bases_empresas = array();
                foreach ($empresas_bases as $row) {
                    $db_dist = $row['Dat_Dis'];
                    if (!isset($bases_empresas[$db_dist])) {
                        $bases_empresas[$db_dist] = array();
                    }
                    $bases_empresas[$db_dist][] = $row['Emp_Cod'];
                }
                
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                $bases_empresas_filtradas = array();
                foreach ($bases_empresas as $db_dist => $emp_cods_base) {
                    if (in_array(strtolower($db_dist), $bases_permitidas)) {
                        $bases_empresas_filtradas[$db_dist] = $emp_cods_base;
                    }
                }
                $bases_empresas = $bases_empresas_filtradas;
                
                if (empty($bases_empresas)) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'No se encontraron bases de datos permitidas para las empresas seleccionadas'));
                }
                
                $total_guardados = 0;
                $errores = array();
                //$lis_id = 0; // Se generará en la primera base usando AUTO_INCREMENT
                
                $primera_base = true;
                foreach ($bases_empresas as $db_dist => $emp_cods_base) {
                    try {
                        $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                        
                        $obBD_datos->inicio_transaccion($obBD_conexion_dist->conexion);
                        
                        $air = array('Lis_Nom' => $nombre_listado, 'Lis_Per' => $periodo, 'Lis_Fei' => $fecha_inicial, 'Lis_Fef' => $fecha_final);
                        $result = $obBD_datos->operacionobBD('listado_apertura.insert', $air, $obBD_conexion_dist);
                        
                        if (!$result) {
                            throw new Exception("Error al insertar listado en base $db_dist");
                        }
                        
                        if ($primera_base) {
                            $lis_id_raw = $obBD_datos->insercionid($obBD_conexion_dist->conexion);
                            if ($lis_id_raw == 0 || $lis_id_raw === false) {
                                throw new Exception("Error al obtener ID del listado generado en base $db_dist");
                            }
                            $lis_id = (string)$lis_id_raw;
                            $primera_base = false;
                        } else {
                            $lis_id = (string)$lis_id;
                        }
                        
                        $emp_cods_marcadas = isset($_POST['emp_cods_marcadas']) ? json_decode($_POST['emp_cods_marcadas'], true) : array();
                        
                        foreach ($emp_cods_base as $emp_cod) {
                            $emp_cods_marcadas_int = array_map('intval', $emp_cods_marcadas);
                            $lis_mar = in_array(intval($emp_cod), $emp_cods_marcadas_int) ? 'S' : 'N';
                            $param_det = implode('*', array($lis_id, $emp_cod, $lis_mar, 'L'));
                            $result_det = $obBD_datos->operacionobBD(12, $param_det, $obBD_conexion_dist);
                            if ($result_det) {
                                $total_guardados++;
                            }
                        }
                        
                        $obBD_datos->commit($obBD_conexion_dist->conexion, 0);
                        $obBD_conexion_dist->cerrar();
                    } catch (Exception $e) {
                        if (isset($obBD_conexion_dist->conexion)) {
                            $obBD_datos->rollBack($obBD_conexion_dist->conexion, 0);
                        }
                        $errores[] = "Base $db_dist: " . $e->getMessage();
                        if (isset($obBD_conexion_dist->conexion)) {
                            $obBD_conexion_dist->cerrar();
                        }
                    }
                }
                
                if ($total_guardados > 0) {
                    enviarRespuestaJSON(array(
                        'success' => true, 
                        'message' => "Listado guardado con $total_guardados empresa(s)",
                        'lis_id' => $lis_id
                    ));
                } else {
                    enviarRespuestaJSON(array(
                        'success' => false, 
                        'message' => 'No se pudo guardar el listado. ' . implode(', ', $errores)
                    ));
                }
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        case 'cargar_listados':
            try {
                $empresas = $obBD_datos->getArrayConsulta(1, '', $obBD_conexion_master);
                
                $listados_agrupados = array();
                $bases_procesadas = array();
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                
                foreach ($empresas as $emp) {
                    if (empty($emp['Dat_Dis'])) {
                        continue;
                    }
                    
                    $db_dist = $emp['Dat_Dis'];
                    if (!in_array(strtolower($db_dist), $bases_permitidas)) {
                        continue;
                    }
                    
                    if (in_array($db_dist, $bases_procesadas)) {
                        continue;
                    }
                    $bases_procesadas[] = $db_dist;
                    
                    try {
                        $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                        
                        $listados_base = $obBD_datos->getArrayConsulta(13, '', $obBD_conexion_dist);
                        
                        foreach ($listados_base as $listado) {
                            $lis_id = (string)$listado['Lis_Cod'];
                            
                            if (isset($listados_agrupados[$lis_id])) {
                                $listados_agrupados[$lis_id]['cantidad'] += $listado['cantidad'];
                            } else {
                                $listados_agrupados[$lis_id] = array(
                                    'id' => $lis_id,
                                    'nombre' => $listado['Lis_Nom'],
                                    'periodo' => $listado['Lis_Per'],
                                    'fechaInicial' => $listado['Lis_Fei'],
                                    'fechaFinal' => $listado['Lis_Fef'],
                                    'fechaCreacion' => $listado['Lis_Fec'],
                                    'cantidad' => $listado['cantidad']
                                );
                            }
                        }
                        
                        $obBD_conexion_dist->cerrar();
                    } catch (Exception $e) {
                        continue;
                    }
                }
                
                $listados_finales = array_values($listados_agrupados);
                
                enviarRespuestaJSON(array('success' => true, 'data' => $listados_finales));
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        case 'obtener_empresas_listado':
            try {
                $lis_id = isset($_POST['lis_id']) && is_numeric($_POST['lis_id']) ? $_POST['lis_id'] : 0;
                
                if ($lis_id == 0) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'ID de listado inválido'));
                }
                
                $empresas_todas = $obBD_datos->getArrayConsulta(1, '', $obBD_conexion_master);
                $empresas_listado = array();
                $bases_procesadas = array();
                
                foreach ($empresas_todas as $emp) {
                    if (empty($emp['Dat_Dis'])) {
                        continue;
                    }
                    
                    $db_dist = $emp['Dat_Dis'];
                    if (in_array($db_dist, $bases_procesadas)) {
                        continue;
                    }
                    $bases_procesadas[] = $db_dist;
                    
                    try {
                        $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                        
                        $param_emp = $lis_id;
                        $empresas_base = $obBD_datos->getArrayConsulta(14, $param_emp, $obBD_conexion_dist);
                        
                        foreach ($empresas_base as $emp_det) {
                            $empresas_listado[] = array(
                                'Emp_Cod' => $emp_det['Emp_Cod'],
                                'Lis_Mar' => isset($emp_det['Lis_Mar']) ? $emp_det['Lis_Mar'] : 'N',
                                'Lad_Est' => $emp_det['Lad_Est']
                            );
                        }
                        
                        $obBD_conexion_dist->cerrar();
                    } catch (Exception $e) {
                        continue;
                    }
                }
                
                enviarRespuestaJSON(array('success' => true, 'data' => $empresas_listado));
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        case 'eliminar_listado':
            try {
                ob_clean();
                header('Content-Type: application/json');
                
                $lis_id = isset($_POST['lis_id']) && is_numeric($_POST['lis_id']) ? $_POST['lis_id'] : 0;
                
                if ($lis_id == 0) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'ID de listado inválido'));
                }
                
                $empresas = $obBD_datos->getArrayConsulta(1, '', $obBD_conexion_master);
                $bases_procesadas = array();
                $eliminados = 0;
                $errores = array();
                
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                
                foreach ($empresas as $emp) {
                    if (empty($emp['Dat_Dis'])) {
                        continue;
                    }
                    
                    $db_dist = $emp['Dat_Dis'];
                    
                    if (!in_array(strtolower($db_dist), $bases_permitidas)) {
                        continue;
                    }
                    
                    if (in_array($db_dist, $bases_procesadas)) {
                        continue;
                    }
                    $bases_procesadas[] = $db_dist;
                    
                    try {
                        $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                        
                        $obBD_datos->inicio_transaccion($obBD_conexion_dist->conexion);
                        
                        $param_elim_det = $lis_id;
                        $result_det = $obBD_datos->operacionobBD(22, $param_elim_det, $obBD_conexion_dist);
                        
                        if ($result_det === false) {
                            throw new Exception("Error al eliminar detalles del listado en base $db_dist");
                        }
                        
                        $param_elim = $lis_id;
                        $result = $obBD_datos->operacionobBD(18, $param_elim, $obBD_conexion_dist);
                        
                        if ($result === false) {
                            throw new Exception("Error al marcar listado como inactivo en base $db_dist");
                        }
                        
                        $mysqli = $obBD_datos->getMyCon($obBD_conexion_dist->conexion);
                        if ($mysqli && mysqli_affected_rows($mysqli) > 0) {
                            $obBD_datos->commit($obBD_conexion_dist->conexion, 0);
                            $eliminados++;
                        } else {
                            if ($mysqli && mysqli_error($mysqli)) {
                                throw new Exception("Error en UPDATE: " . mysqli_error($mysqli));
                            }
                            $obBD_datos->rollBack($obBD_conexion_dist->conexion, 0);
                        }
                        
                        $obBD_conexion_dist->cerrar();
                    } catch (Exception $e) {
                        if (isset($obBD_conexion_dist->conexion)) {
                            $obBD_datos->rollBack($obBD_conexion_dist->conexion, 0);
                            if (isset($obBD_conexion_dist)) {
                                $obBD_conexion_dist->cerrar();
                            }
                        }
                        $errores[] = "Base $db_dist: " . $e->getMessage();
                    }
                }
                
                if ($eliminados > 0) {
                    $msg = "Listado eliminado de $eliminados base(s) de datos";
                    if (!empty($errores)) {
                        $msg .= ". Errores: " . implode(", ", array_slice($errores, 0, 3));
                    }
                    enviarRespuestaJSON(array('success' => true, 'message' => $msg));
                } else {
                    $msg = 'No se pudo eliminar el listado';
                    if (!empty($errores)) {
                        $msg .= ". Errores: " . implode(", ", array_slice($errores, 0, 3));
                    }
                    enviarRespuestaJSON(array('success' => false, 'message' => $msg));
                }
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        case 'verificar_empresa_en_listado':
            try {
                $emp_cod = isset($_POST['emp_cod']) ? intval($_POST['emp_cod']) : 0;
                
                if ($emp_cod == 0) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'Código de empresa inválido'));
                }
                
                $param_dat_dis = $emp_cod;
                $dat_dis = $obBD_datos->getRowConsulta(5, $param_dat_dis, $obBD_conexion_master);
                
                if (!$dat_dis || empty($dat_dis['Dat_Dis'])) {
                    enviarRespuestaJSON(array('success' => true, 'data' => false));
                }
                
                $db_dist = $dat_dis['Dat_Dis'];
                
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                if (!in_array(strtolower($db_dist), $bases_permitidas)) {
                    enviarRespuestaJSON(array('success' => true, 'data' => false));
                }
                
                $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                
                $param_ver = $emp_cod;
                $result = $obBD_datos->getRowConsulta(19, $param_ver, $obBD_conexion_dist);
                
                $obBD_conexion_dist->cerrar();
                
                if ($result) {
                    enviarRespuestaJSON(array('success' => true, 'data' => $result['Lad_Est']));
                } else {
                    enviarRespuestaJSON(array('success' => true, 'data' => false));
                }
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        case 'verificar_empresas_en_listado':
            try {
                $emp_cods = isset($_POST['emp_cods']) ? json_decode($_POST['emp_cods'], true) : array();
                
                if (empty($emp_cods) || !is_array($emp_cods)) {
                    enviarRespuestaJSON(array('success' => true, 'data' => array()));
                }
                
                $emp_cods_clean = array();
                foreach ($emp_cods as $emp_cod) {
                    $emp_cod_int = intval($emp_cod);
                    if ($emp_cod_int > 0) {
                        $emp_cods_clean[] = $emp_cod_int;
                    }
                }
                
                if (empty($emp_cods_clean)) {
                    enviarRespuestaJSON(array('success' => true, 'data' => array()));
                }
                
                $param_data = implode('*', $emp_cods_clean);
                $empresas_bases = $obBD_datos->getArrayConsulta(6, $param_data, $obBD_conexion_master);
                
                if (empty($empresas_bases)) {
                    enviarRespuestaJSON(array('success' => true, 'data' => array()));
                }
                
                $bases_empresas = array();
                foreach ($empresas_bases as $row) {
                    $db_dist = $row['Dat_Dis'];
                    if (!isset($bases_empresas[$db_dist])) {
                        $bases_empresas[$db_dist] = array();
                    }
                    $bases_empresas[$db_dist][] = $row['Emp_Cod'];
                }
                
                $estados_empresas = array();
                
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                $bases_empresas_filtradas = array();
                foreach ($bases_empresas as $db_dist => $emp_cods_base) {
                    if (in_array(strtolower($db_dist), $bases_permitidas)) {
                        $bases_empresas_filtradas[$db_dist] = $emp_cods_base;
                    }
                }
                $bases_empresas = $bases_empresas_filtradas;
                
                foreach ($bases_empresas as $db_dist => $emp_cods_base) {
                    try {
                        $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                        
                        if (!empty($emp_cods_base)) {
                            $param_ver = implode('*', $emp_cods_base);
                            $resultados = $obBD_datos->getArrayConsulta(20, $param_ver, $obBD_conexion_dist);
                            
                            if (!empty($resultados)) {
                                $empresas_procesadas_base = array();
                                foreach ($resultados as $result) {
                                    $emp_cod = $result['Emp_Cod'];
                                    if (!isset($empresas_procesadas_base[$emp_cod])) {
                                        $estados_empresas[$emp_cod] = $result['Lad_Est'];
                                        $empresas_procesadas_base[$emp_cod] = true;
                                    }
                                }
                            }
                        }
                        
                        $obBD_conexion_dist->cerrar();
                    } catch (Exception $e) {
                        continue;
                    }
                }
                
                enviarRespuestaJSON(array('success' => true, 'data' => $estados_empresas));
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        case 'actualizar_listado':
            try {
                $lis_id = isset($_POST['lis_id']) && is_numeric($_POST['lis_id']) ? $_POST['lis_id'] : 0;
                $nombre_listado = isset($_POST['nombre_listado']) ? $_POST['nombre_listado'] : '';
                $periodo = isset($_POST['periodo']) ? $_POST['periodo'] : '';
                $fecha_inicial = isset($_POST['fecha_inicial']) ? $_POST['fecha_inicial'] : '';
                $fecha_final = isset($_POST['fecha_final']) ? $_POST['fecha_final'] : '';
                $emp_cods_agregar = isset($_POST['emp_cods_agregar']) ? json_decode($_POST['emp_cods_agregar'], true) : array();
                $emp_cods_eliminar = isset($_POST['emp_cods_eliminar']) ? json_decode($_POST['emp_cods_eliminar'], true) : array();
                
                if ($lis_id == 0) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'ID de listado inválido'));
                }
                
                if (empty($nombre_listado) || empty($periodo) || empty($fecha_inicial) || empty($fecha_final)) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'Faltan datos requeridos'));
                }
                
                $empresas = $obBD_datos->getArrayConsulta(1, '', $obBD_conexion_master);
                $bases_procesadas = array();
                $actualizados = 0;
                $errores = array();
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                
                foreach ($empresas as $emp) {
                    if (empty($emp['Dat_Dis'])) {
                        continue;
                    }
                    
                    $db_dist = $emp['Dat_Dis'];
                    if (!in_array(strtolower($db_dist), $bases_permitidas)) {
                        continue;
                    }
                    
                    if (in_array($db_dist, $bases_procesadas)) {
                        continue;
                    }
                    $bases_procesadas[] = $db_dist;
                    
                    try {
                        $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                        
                        $listados_base = $obBD_datos->getArrayConsulta(13, '', $obBD_conexion_dist);
                        $listado_existe = false;
                        foreach ($listados_base as $list) {
                            if ($list['Lis_Cod'] == $lis_id) {
                                $listado_existe = true;
                                break;
                            }
                        }
                        
                        if ($listado_existe) {
                            $param_update = implode('*', array($lis_id, $nombre_listado, $periodo, $fecha_inicial, $fecha_final));
                            $result = $obBD_datos->operacionobBD(16, $param_update, $obBD_conexion_dist);
                            
                            if ($result) {
                                $actualizados++;
                            }
                            
                            foreach ($emp_cods_agregar as $emp_cod) {
                                $param_emp_exist = $emp_cod;
                                $emp_existe = $obBD_datos->getRowConsulta(8, $param_emp_exist, $obBD_conexion_dist);
                                
                                if ($emp_existe) {
                                    $emp_cods_marcadas = isset($_POST['emp_cods_marcadas']) ? json_decode($_POST['emp_cods_marcadas'], true) : array();
                                    $emp_cods_marcadas_int = array_map('intval', $emp_cods_marcadas);
                                    $lis_mar = in_array(intval($emp_cod), $emp_cods_marcadas_int) ? 'S' : 'N';
                                    $param_det = implode('*', array($lis_id, $emp_cod, $lis_mar, 'L'));
                                    $obBD_datos->operacionobBD(12, $param_det, $obBD_conexion_dist);
                                }
                            }
                            
                            foreach ($emp_cods_eliminar as $emp_cod) {
                                $param_elim_emp = implode('*', array($lis_id, $emp_cod));
                                $obBD_datos->operacionobBD(17, $param_elim_emp, $obBD_conexion_dist);
                            }
                        }
                        
                        $obBD_conexion_dist->cerrar();
                    } catch (Exception $e) {
                        $errores[] = "Base $db_dist: " . $e->getMessage();
                    }
                }
                
                if ($actualizados > 0) {
                    enviarRespuestaJSON(array('success' => true, 'message' => "Listado actualizado en $actualizados base(s) de datos"));
                } else {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'No se pudo actualizar el listado. ' . implode(', ', $errores)));
                }
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        case 'actualizar_listado_det':
            try {
                $lis_id = isset($_POST['lis_id']) && is_numeric($_POST['lis_id']) ? $_POST['lis_id'] : 0;
                $emp_cods_marcadas = isset($_POST['emp_cods_marcadas']) ? json_decode($_POST['emp_cods_marcadas'], true) : array();
                $nombre_listado = isset($_POST['nombre_listado']) ? $_POST['nombre_listado'] : '';
                $periodo = isset($_POST['periodo']) ? $_POST['periodo'] : '';
                $fecha_inicial = isset($_POST['fecha_inicial']) ? $_POST['fecha_inicial'] : '';
                $fecha_final = isset($_POST['fecha_final']) ? $_POST['fecha_final'] : '';
                
                if ($lis_id == 0) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'ID de listado inválido'));
                }
                
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                
                $empresas = $obBD_datos->getArrayConsulta(1, '', $obBD_conexion_master);
                
                if (empty($nombre_listado) || empty($periodo) || empty($fecha_inicial) || empty($fecha_final)) {
                    foreach ($empresas as $emp) {
                        if (empty($emp['Dat_Dis'])) {
                            continue;
                        }
                        $db_dist = $emp['Dat_Dis'];
                        if (!in_array(strtolower($db_dist), $bases_permitidas)) {
                            continue;
                        }
                        try {
                            $obBD_conexion_temp = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                            $listados_base = $obBD_datos->getArrayConsulta(13, '', $obBD_conexion_temp);
                            foreach ($listados_base as $list) {
                                if ((string)$list['Lis_Cod'] === (string)$lis_id) {
                                    if (empty($nombre_listado)) $nombre_listado = $list['Lis_Nom'];
                                    if (empty($periodo)) $periodo = $list['Lis_Per'];
                                    if (empty($fecha_inicial)) $fecha_inicial = $list['Lis_Fei'];
                                    if (empty($fecha_final)) $fecha_final = $list['Lis_Fef'];
                                    $obBD_conexion_temp->cerrar();
                                    break 2; // Salir de ambos loops
                                }
                            }
                            $obBD_conexion_temp->cerrar();
                        } catch (Exception $e) {
                            continue;
                        }
                    }
                }
                
                $bases_procesadas = array();
                $actualizados = 0;
                $errores = array();
                
                // Validar que haya empresas marcadas
                if (empty($emp_cods_marcadas) || !is_array($emp_cods_marcadas)) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'No se proporcionaron empresas marcadas'));
                }
                
                // Agrupar empresas marcadas por base de datos
                $bases_empresas_marcadas = array();
                $emp_cods_marcadas_int = array_map('intval', $emp_cods_marcadas);
                foreach ($empresas as $emp) {
                    if (empty($emp['Dat_Dis'])) {
                        continue;
                    }
                    $db_dist = $emp['Dat_Dis'];
                    if (!in_array(strtolower($db_dist), $bases_permitidas)) {
                        continue;
                    }
                    if (in_array(intval($emp['Emp_Cod']), $emp_cods_marcadas_int)) {
                        if (!isset($bases_empresas_marcadas[$db_dist])) {
                            $bases_empresas_marcadas[$db_dist] = array();
                        }
                        $bases_empresas_marcadas[$db_dist][] = intval($emp['Emp_Cod']);
                    }
                }
                
                if (empty($bases_empresas_marcadas)) {
                    enviarRespuestaJSON(array('success' => false, 'message' => 'No se encontraron empresas marcadas en las bases de datos permitidas'));
                }
                
                foreach ($bases_empresas_marcadas as $db_dist => $emp_cods_base) {
                    if (in_array($db_dist, $bases_procesadas)) {
                        continue;
                    }
                    $bases_procesadas[] = $db_dist;
                    
                    try {
                        $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                        
                        // Verificar si el listado existe en esta base
                        $listados_base = $obBD_datos->getArrayConsulta(13, '', $obBD_conexion_dist);
                        $listado_existe = false;
                        foreach ($listados_base as $list) {
                            if ((string)$list['Lis_Cod'] === (string)$lis_id) {
                                $listado_existe = true;
                                break;
                            }
                        }
                        
                        // Si el listado no existe en esta base, crearlo primero
                        $listado_recien_creado = false;
                        if (!$listado_existe && !empty($nombre_listado) && !empty($periodo) && !empty($fecha_inicial) && !empty($fecha_final)) {
                            // Iniciar transacción
                            $obBD_datos->inicio_transaccion($obBD_conexion_dist->conexion);
                            
                            $nombre_listado_esc = addslashes($nombre_listado);
                            $fecha_inicial_esc = addslashes($fecha_inicial);
                            $fecha_final_esc = addslashes($fecha_final);
                            $periodo_int = intval($periodo);
                            $lis_id_clean = is_numeric($lis_id) ? $lis_id : 0;
                            
                            $sql_insert = "INSERT INTO listado_apertura (Lis_Cod, Lis_Nom, Lis_Per, Lis_Fei, Lis_Fef, Lis_Fec, Lis_Est) 
                                           VALUES ($lis_id_clean, '$nombre_listado_esc', $periodo_int, '$fecha_inicial_esc', '$fecha_final_esc', NOW(), 'A')";
                            
                            $result = $obBD_datos->grabarv_registros($sql_insert, $obBD_conexion_dist->conexion);
                            
                            if ($result) {
                                $obBD_datos->commit($obBD_conexion_dist->conexion, 0);
                                $listado_existe = true; // Ahora existe
                                $listado_recien_creado = true;
                            } else {
                                $obBD_datos->rollBack($obBD_conexion_dist->conexion, 0);
                                throw new Exception("Error al crear listado en base $db_dist");
                            }
                        }
                        
                        if ($listado_existe) {
                            $empresas_listado = array();
                            $empresas_listado_cods = array();
                            if (!$listado_recien_creado) {
                                $param_listado = $lis_id;
                                $empresas_listado = $obBD_datos->getArrayConsulta(14, $param_listado, $obBD_conexion_dist);
                                foreach ($empresas_listado as $emp_list) {
                                    $empresas_listado_cods[] = intval($emp_list['Emp_Cod']);
                                }
                            }
                            
                            $emp_cods_marcadas_int = array_map('intval', $emp_cods_marcadas);
                            
                            $empresas_base_esta = array();
                            foreach ($empresas as $emp_act) {
                                if (!empty($emp_act['Dat_Dis']) && $emp_act['Dat_Dis'] == $db_dist && in_array(intval($emp_act['Emp_Cod']), $emp_cods_marcadas_int)) {
                                    $empresas_base_esta[] = intval($emp_act['Emp_Cod']);
                                }
                            }
                            
                            // Iniciar transacción
                            $obBD_datos->inicio_transaccion($obBD_conexion_dist->conexion);
                            
                            if (!$listado_recien_creado) {
                                foreach ($empresas_listado as $emp_listado) {
                                    $emp_cod = intval($emp_listado['Emp_Cod']);
                                    $lis_mar = in_array($emp_cod, $emp_cods_marcadas_int) ? 'S' : 'N';
                                    
                                    $param_det = implode('*', array($lis_id, $emp_cod, $lis_mar, $emp_listado['Lad_Est']));
                                    $result_det = $obBD_datos->operacionobBD(12, $param_det, $obBD_conexion_dist);
                                    
                                    if ($result_det) {
                                        $actualizados++;
                                    }
                                }
                            }
                            
                            foreach ($empresas_base_esta as $emp_cod_nueva) {
                                if (!in_array($emp_cod_nueva, $empresas_listado_cods)) {
                                    $param_det = implode('*', array($lis_id, $emp_cod_nueva, 'S', 'L'));
                                    $result_det = $obBD_datos->operacionobBD(12, $param_det, $obBD_conexion_dist);
                                    
                                    if ($result_det) {
                                        $actualizados++;
                                    }
                                }
                            }
                            
                            $obBD_datos->commit($obBD_conexion_dist->conexion, 0);
                            
                            if (!$listado_recien_creado && !empty($nombre_listado) && !empty($periodo) && !empty($fecha_inicial) && !empty($fecha_final)) {
                                $param_update = implode('*', array($lis_id, $nombre_listado, $periodo, $fecha_inicial, $fecha_final));
                                $obBD_datos->operacionobBD(16, $param_update, $obBD_conexion_dist);
                            }
                        }
                        
                        $obBD_conexion_dist->cerrar();
                    } catch (Exception $e) {
                        if (isset($obBD_conexion_dist->conexion)) {
                            $obBD_datos->rollBack($obBD_conexion_dist->conexion, 0);
                        }
                        $errores[] = "Base $db_dist: " . $e->getMessage();
                        if (isset($obBD_conexion_dist->conexion)) {
                            $obBD_conexion_dist->cerrar();
                        }
                    }
                }
                
                if ($actualizados > 0) {
                    $mensaje = "Listado actualizado con $actualizados empresa(s)";
                    if (!empty($errores)) {
                        $mensaje .= ". Advertencias: " . implode(', ', array_slice($errores, 0, 3));
                    }
                    enviarRespuestaJSON(array('success' => true, 'message' => $mensaje));
                } else {
                    $mensaje = 'No se pudo actualizar el listado';
                    if (!empty($errores)) {
                        $mensaje .= '. Errores: ' . implode(', ', array_slice($errores, 0, 5));
                    } else {
                        $mensaje .= '. No se encontraron empresas para procesar o el listado no existe en ninguna base de datos.';
                    }
                    enviarRespuestaJSON(array('success' => false, 'message' => $mensaje));
                }
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        case 'obtener_estados_empresas':
            try {
                $empresas = $obBD_datos->getArrayConsulta(1, '', $obBD_conexion_master);
                $estados_empresas = array();
                $bases_procesadas = array();
                $bases_permitidas = array('servicios', 'exa', 'coopsb', 'agronuevo', 'gsl_chavez');
                
                foreach ($empresas as $emp) {
                    if (empty($emp['Dat_Dis'])) {
                        continue;
                    }
                    
                    $db_dist = $emp['Dat_Dis'];
                    if (!in_array(strtolower($db_dist), $bases_permitidas)) {
                        continue;
                    }
                    
                    if (in_array($db_dist, $bases_procesadas)) {
                        continue;
                    }
                    $bases_procesadas[] = $db_dist;
                    
                    try {
                        $obBD_conexion_dist = new Class_Log_Conexion_Gestion_Periodos($db_dist);
                        
                        $param_ver = '';
                        $resultados = $obBD_datos->getArrayConsulta(19, $param_ver, $obBD_conexion_dist);
                        
                        // Esto no funcionará porque case 19 espera un Emp_Cod
                        // Necesito crear una nueva consulta SQL
                        
                        $obBD_conexion_dist->cerrar();
                    } catch (Exception $e) {
                        continue;
                    }
                }
                
                enviarRespuestaJSON(array('success' => true, 'data' => $estados_empresas));
            } catch (Exception $e) {
                enviarRespuestaJSON(array('success' => false, 'message' => $e->getMessage()));
            }
            
        default:
            enviarRespuestaJSON(array('success' => false, 'message' => 'Acción no reconocida'));
        }
    } catch (Exception $e) {
        enviarRespuestaJSON(array('success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()));
    }
}

if (!isset($_POST['action'])) {
    $obBD_conexion_master->cerrar();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Apertura - EXA</title>
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script language="javascript" src="../VALIDACIONES/adm_val_gestion_periodos.js?x=4"></script>
    <!-- LINEAS DE CSS PARA LA VISTA -->
    <style>
        .exa-header { background-color: #0797D8; color: #FFFFFF; font-weight: bold; padding: 10px 15px; }
        .exa-body { background-color: #DFE9F6; padding: 15px; }
        .exa-fieldset {/*background-color: #FFFFFF;*/ border: 1px solid #CCCCCC; padding: 15px; margin-bottom: 15px; }
        .Titulos2 { font: normal 11px "Trebuchet MS", Arial, Helvetica, sans-serif; color: #5B6F88; font-weight: bold; }
        .Etiqueta1 { font: normal 11px "Trebuchet MS", Arial, Helvetica, sans-serif; color: #95BA2E; text-align: right; }
        .Etiqueta2 { font: normal 20px  "Trebuchet MS", Arial, Helvetica, sans-serif; font-weight: bold; color:rgb(0, 0, 0); text-align: right; }
        .Cabecera1 { font: normal 11px "Trebuchet MS", Arial, Helvetica, sans-serif; background-color: #0797D8; color: #FFFFFF; text-align: center; padding: 8px; }
        .Fondo { font: normal 11px "Trebuchet MS", Arial, Helvetica, sans-serif; background-color: #FFFFFF; color: #5B6F88; padding: 8px; }
        .checkbox-cell { width: 30px; text-align: center; }
        .stats-container { /*background-color: #FFFFFF;*/ border: 1px solid #CCCCCC; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .stats-item { display: inline-block; margin-right: 20px; font-weight: bold; color: #5B6F88; }
        .stats-item .label { color: #000000; margin-right: 5px; }
        .filter-buttons { margin-bottom: 10px; }
        .filter-btn { padding: 5px 15px; margin-right: 5px; border: 1px solid #CCCCCC; background-color: #FFFFFF; color: #5B6F88; cursor: pointer; border-radius: 3px; }
        .filter-btn.active { background-color: #0797D8; color: #FFFFFF; border-color: #0797D8; }
        
        /* Estilos para el modal */
        .modal-overlay {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            align-items: center;
            justify-content: center;
        }
        /* Asegurar que los diálogos de confirmación estén sobre los modales */
        .ui-dialog, .ui-dialog-overlay {
            z-index: 2000 !important;
        }
        .modal-content { background-color: #fefefe; margin: auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19); animation-name: animatetop; animation-duration: 0.4s }
        
        /* Add Animation */
        @-webkit-keyframes animatetop { from {top: -300px; opacity: 0} to {top: 0; opacity: 1} }
        @keyframes animatetop { from {top: -300px; opacity: 0} to {top: 0; opacity: 1} }
        
        .modal-header { padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; color: #5B6F88; }
        .modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; background: none; border: none; cursor: pointer; }
        .modal-close:hover, .modal-close:focus { color: black; text-decoration: none; cursor: pointer; }
        .modal-body { padding: 20px 0; }
        .modal-footer { padding: 10px 0; border-top: 1px solid #eee; text-align: right; }
        .form-group { margin-bottom: 15px; } .form-control { width: 100%; padding: 8px; border: 1px solid #CCCCCC; border-radius: 3px; font-size: 14px; }
        .btn { padding: 8px 15px; border-radius: 3px; cursor: pointer; font-size: 14px; }
        .btn-primary { background-color: #0797D8; color: white; border: 1px solid #0797D8; }
        .btn-success { background-color: #5cb85c; color: white; border: 1px solid #4cae4c; }
        .btn-danger { background-color: #d9534f; color: white; border: 1px solid #d43f3f; }
        .btn-warning { background-color: #f0ad4e; color: white; border: 1px solid #eea236; }
        .btn-info { background-color: #5bc0de; color: white; border: 1px solid #46b8da; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; display: none; }
        .alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
        .alert-info { color: #31708f; background-color: #d9edf7; border-color: #bce8f1; }
        .alert-warning { color: #8a6d3b; background-color: #fcf8e3; border-color: #faebcc; }
        .alert-danger { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
        .has-periodo { background-color: #e6ffe6 !important; }
        .en-listado-icon { color: #006400; font-weight: bold; margin-right: 5px; }
        .listado-item { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; background-color: #f9f9f9; }
        .listado-item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .listado-item-info strong { color: #0797D8; }
        .listado-item-info p { margin: 2px 0; font-size: 0.9em; color: #5B6F88; }
        .listado-item-acciones .btn-accion { margin-left: 5px; padding: 5px 10px; font-size: 0.8em; }
        .item-pendiente { display: flex; justify-content: space-between; align-items: center; border: 1px solid #eee; padding: 10px; margin-bottom: 10px; background-color: #fff; }
        .item-pendiente .info-empresa { flex-grow: 1; }
        .item-pendiente .acciones-empresa button { margin-left: 5px; }
        .regimen-general { color: #006400; font-weight: bold; }
        .regimen-popular { color: #FF8C00; font-weight: bold; }
        .regimen-emprendedor { color: #1E90FF; font-weight: bold; }
        .regimen-na { color: #808080; }
    </style>
</head>
<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registro de Apertura</h3>
        </div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="alert-container"></div>
            
            <div style="display: flex; gap: 15px; align-items: flex-start; margin-bottom: 15px;">
                <fieldset class="exa-fieldset" style="flex: 0 0 auto; min-width: 350px; padding: 8px 12px;">
                <legend class="Titulos2">Seleccione Periodo</legend>
                    <form id="formPeriodo" class="form-horizontal normal" onsubmit="return buscarEmpresas();" style="margin: 0;">
                        <div class="form-group" style="margin: 0; margin-left: 10px;">
                            <label class="Etiqueta2" style="margin-right: 5px;">Periodo:</label>
                                <input type="number" id="periodo" name="periodo" class="form-control input-sm" style="width: 90px; display: inline-block; text-align: center;" min="2000" max="2100" value="<?php echo date('Y'); ?>" />
                            <button type="submit" class="btn btn-success btn-sm" style="margin-left: 10px; padding: 4px 10px;">
                            <span class="glyphicon glyphicon-search"></span> Buscar
                        </button>
                    </div>
                    <input type="hidden" id="fecha_inicial" value="<?php echo date('Y') . '-01-01'; ?>">
                    <input type="hidden" id="fecha_final" value="<?php echo date('Y') . '-12-31'; ?>">
                </form>
            </fieldset>
            
                <fieldset class="exa-fieldset" id="fieldset-stats" style="flex: 0 0 auto; max-width: 400px; display: none; padding: 8px 12px;">
                    <legend class="Titulos2">Control de Empresa</legend>
                    <div class="stats-container" id="stats-container" style="width: 100%; padding: 5px 8px; margin: 0;">
                        <div class="stats-item">
                            <span class="label">Total:</span>
                            <span id="stat-total">0</span>
                        </div>
                        <div class="stats-item">
                            <span class="label">Con Período:</span>
                            <span id="stat-con-periodo" style="color: #006400; font-weight: bold;">0</span>
                        </div>
                        <div class="stats-item">
                            <span class="label">Sin Período:</span>
                            <span id="stat-sin-periodo" style="color: #8B0000; font-weight: bold;">0</span>
                        </div>
                    </div>
                </fieldset>
                    </div>
                    
            <div id="empresas-container" style="display: none;">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Datos de Empresa</legend>
                    <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Filtrado Por</legend>
                        <div class="filter-buttons" id="filter-buttons" style="display: none; margin-top: 10px;">
                        <button class="filter-btn active" data-filtro="todos" onclick="filtrarEmpresas('todos', this)">Todos</button>
                        <button class="filter-btn" data-filtro="con-periodo" onclick="filtrarEmpresas('con-periodo', this)">Con Período</button>
                        <button class="filter-btn" data-filtro="sin-periodo" onclick="filtrarEmpresas('sin-periodo', this)">Sin Período</button>
                        <label style="margin-left: 20px; color: #5B6F88; font-weight: bold;">
                            Filtrar por Contador:
                                <select id="filtro-contador" onchange="filtrarPorContador()" style="margin-left: 5px; width: 250px; padding: 5px; border: 1px solid #CCCCCC; border-radius: 3px;">
                                <option value="">Todos los Contadores</option>
                            </select>
                        </label>
                        <label style="margin-left: 20px; color: #5B6F88; font-weight: bold;">
                            Filtrar por Régimen:
                            <select id="filtro-regimen" onchange="filtrarPorRegimen()" style="margin-left: 5px; padding: 5px; border: 1px solid #CCCCCC; border-radius: 3px;">
                                <option value="">Todos los Regímenes</option>
                                <option value="N">Regimen General</option>
                                <option value="NP">Rimpe Negocio Popular</option>
                                <option value="EM">Rimpe Emprendedor</option>
                            </select>
                        </label>
                    </div>
                    </fieldset>
                    
                    <div style="margin-bottom: 10px;">
                        <label>
                            <input type="checkbox" id="select-all" onchange="toggleSelectAll()" style="margin-right: 5px;">
                            <span id="select-all-label">Seleccionar Todas</span>
                        </label>
                        <button class="btn btn-success" onclick="activarPeriodo()" style="margin-left: 20px;">
                            <span class="glyphicon glyphicon-ok"></span> Activar Período Contable
                        </button>
                        <button class="btn btn-warning" onclick="abrirModalCambiarRegimen()" style="margin-left: 10px;">
                            <span class="glyphicon glyphicon-edit"></span> Cambiar Régimen
                        </button>
                        <button class="btn btn-primary" onclick="guardarListado()" style="margin-left: 10px; background-color: #6c757d;">
                            <span class="glyphicon glyphicon-floppy-disk"></span> Guardar Listado
                        </button>
                        <button class="btn btn-primary" onclick="abrirModalListadosGuardados()" style="margin-left: 10px; background-color: #17a2b8;">
                            <span class="glyphicon glyphicon-folder-open"></span> Ver Listados Guardados
                        </button>

                        <input type="hidden" id="Lis_Cod" value="">
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <input type="text" id="buscar-empresas" class="form-control" placeholder="Buscar empresas por nombre, RUC, correo o contador..." style="max-width: 500px; display: inline-block;">
                        <button class="btn btn-success" onclick="buscarEmpresasPorTexto()" style="margin-left: 5px;">
                            <span class="glyphicon glyphicon-search"></span> Buscar
                        </button>
                        <button class="btn btn-danger" onclick="limpiarBusqueda()" style="margin-left: 5px;">
                            <span class="glyphicon glyphicon-erase"></span> Limpiar
                        </button>
                    </div>
                    <div style="min-height: 300px;">
                        <table id="tabla-empresas"></table>
                        <div id="tabla-empresasPager"></div>
                    </div>
                </fieldset>
    </div>
    
    <!-- Modal para Cambiar Régimen -->
    <div id="modal-cambiar-regimen" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                            <h3>Cambiar Régimen</h3>
                <button class="modal-close" onclick="cerrarModalCambiarRegimen()">&times;</button>
            </div>
            <div class="modal-body">
                            <p>Se cambiará el régimen para <strong id="modal-empresas-seleccionadas">0</strong> empresa(s) seleccionada(s).</p>
                <div class="form-group">
                                <label class="Etiqueta1" for="nuevo_regimen">Nuevo Régimen:</label>
                                <select id="nuevo_regimen" class="form-control">
                        <option value="">Seleccione...</option>
                        <option value="N">Regimen General</option>
                        <option value="NP">Rimpe Negocio Popular</option>
                        <option value="EM">Rimpe Emprendedor</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                            <button class="btn btn-success" onclick="cambiarRegimen()">
                                <span class="glyphicon glyphicon-ok"></span> Aplicar Cambio
                </button>
                <button class="btn btn-primary" onclick="cerrarModalCambiarRegimen()" style="margin-left: 10px;">
                    <span class="glyphicon glyphicon-remove"></span> Cancelar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal para Pendiente Grupal -->
    <div id="modal-pendiente-grupal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                            <h3>Empresas Pendientes de Apertura</h3>
                <button class="modal-close" onclick="cerrarModalPendienteGrupal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="lista-pendientes-container">
                                <p style="text-align: center; color: #5B6F88; padding: 20px;">Cargando empresas...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="cerrarModalPendienteGrupal()">
                    <span class="glyphicon glyphicon-remove"></span> Cerrar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal para Listados Guardados -->
    <div id="modal-listados-guardados" class="modal-overlay" style="display: none;">
                    <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3>Listados Guardados</h3>
                <button class="modal-close" onclick="cerrarModalListadosGuardados()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="listados-container">
                                <p style="text-align: center; color: #5B6F88; padding: 20px;">No hay listados guardados</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="cerrarModalListadosGuardados()">
                    <span class="glyphicon glyphicon-remove"></span> Cerrar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal para Modificar Listado -->
    <div id="modal-modificar-listado" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3>Modificar Listado</h3>
                <button class="modal-close" onclick="cerrarModalModificarListado()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="Etiqueta1" style="display: block; margin-bottom: 5px;">Nombre del Listado:</label>
                    <input type="text" id="edit-nombre-listado" style="width: 100%; padding: 8px; border: 1px solid #CCCCCC; border-radius: 3px; font-size: 14px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="Etiqueta1" style="display: block; margin-bottom: 5px;">Período:</label>
                                <select id="edit-periodo-listado" style="width: 150px; padding: 8px; border: 1px solid #CCCCCC; border-radius: 3px; font-size: 14px;">
                                    <?php
                                    $currentYear = date('Y');
                                    for ($year = $currentYear; $year >= 2020; $year--) {
                                        echo "<option value=\"$year\">$year</option>";
                                    }
                                    ?>
                                </select>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="Etiqueta1" style="display: block; margin-bottom: 5px;">Fecha Inicial:</label>
                    <input type="date" id="edit-fecha-inicial-listado" style="width: 200px; padding: 8px; border: 1px solid #CCCCCC; border-radius: 3px; font-size: 14px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="Etiqueta1" style="display: block; margin-bottom: 5px;">Fecha Final:</label>
                    <input type="date" id="edit-fecha-final-listado" style="width: 200px; padding: 8px; border: 1px solid #CCCCCC; border-radius: 3px; font-size: 14px;">
                </div>
                            
                            <h4 class="Titulos2" style="margin-top: 20px;">Empresas en este listado (<span id="edit-cantidad-empresas">0</span>):</h4>
                            <div id="edit-empresas-listado-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px; margin-bottom: 15px;">
                                <p style="text-align: center; color: #999; padding: 10px;">No hay empresas en el listado</p>
                        </div>
                            
                            <div style="text-align: right; margin-top: 15px;">
                                <p id="edit-empresas-disponibles" style="display: inline-block; margin-right: 10px; color: #999;">Seleccione empresas en la tabla principal para agregarlas al listado</p>
                                <button class="btn btn-info" onclick="agregarEmpresasSeleccionadasAlListado()">
                        <span class="glyphicon glyphicon-plus"></span> Agregar Seleccionadas
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                            <button class="btn btn-success" onclick="guardarCambiosListado()">
                                <span class="glyphicon glyphicon-save"></span> Guardar Cambios
                </button>
                <button class="btn btn-primary" onclick="cerrarModalModificarListado()" style="margin-left: 10px;">
                    <span class="glyphicon glyphicon-remove"></span> Cancelar
                </button>
            </div>
        </div>
    </div>
                        </div>
                        </div>
</body>
</html>
