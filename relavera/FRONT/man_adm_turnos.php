<?php
/**
 * @abstract Administración de Turnos y Cupos
 * @author Sistema EXA
 * @version 1.0
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Mani;

$hoy = date("Y-m-d"); 

/* ==================== AJAX HANDLERS ==================== */

// Listar todas las configuraciones de turnos (para el grid)
if (isset($listTurnosCabAjax)) {
    $resultado = array('success' => true);
    
    // Construir condiciones de búsqueda
    $where = array('Emp_Cod' => $Ses_Emp_Cod);
    
    // Filtro por estado - solo aplicar si tiene un valor válido (no vacío y no es 'T')
    if (isset($_GET['filtro_estado']) && $_GET['filtro_estado'] !== '' && $_GET['filtro_estado'] !== null && $_GET['filtro_estado'] !== 'T') {
        $where['Tur_Est'] = trim($_GET['filtro_estado']);
    }
    
    // Filtro por fecha inicio - usar BETWEEN si hay ambas fechas
    $fechaDesde = isset($_GET['filtro_fecha_inicio_desde']) && !empty($_GET['filtro_fecha_inicio_desde']) ? $_GET['filtro_fecha_inicio_desde'] : null;
    $fechaHasta = isset($_GET['filtro_fecha_inicio_hasta']) && !empty($_GET['filtro_fecha_inicio_hasta']) ? $_GET['filtro_fecha_inicio_hasta'] : null;
    
    if ($fechaDesde && $fechaHasta) {
        // Usar BETWEEN cuando hay ambas fechas
        // Usar clave numérica para que se procese como condición literal
        $where[0] = "Tur_Fei BETWEEN '$fechaDesde' AND '$fechaHasta'";
    } else if ($fechaDesde) {
        // Solo fecha desde
        $where['Tur_Fei >='] = $fechaDesde;
    } else if ($fechaHasta) {
        // Solo fecha hasta
        $where['Tur_Fei <='] = $fechaHasta;
    }
    
    $configs = $obBD_con1->getArrayConsulta('manifiesto_turnos_cab.selectWhere', 
        array('where' => $where, 'order' => 'Tur_Sys DESC'), 
        $obBD_conexion, true);
     
    // Agregar información adicional a cada configuración
    foreach ($configs as &$config) {
        // Contar turnos activos e inactivos
        $detalles = $obBD_con1->getArrayConsulta('manifiesto_turnos_det.selectWhere', 
            array('where' => array('Tur_Cod' => $config['Tur_Cod'])), 
            $obBD_conexion, true);
        
        $activos = 0;
        $inactivos = 0;
        $totalCupos = 0;
        
        foreach ($detalles as $det) {
            if ($det['Tud_Est'] == 'A') {
                $activos++;
                $totalCupos += intval($det['Tud_Cup']);
            } else if ($det['Tud_Est'] == 'S') {
                $inactivos++;
            }
        }
        
        $config['turnos_activos'] = $activos;
        $config['turnos_inactivos'] = $inactivos;
        $config['total_cupos'] = $totalCupos;
        $config['total_turnos'] = count($detalles);
        if ($config['Tur_Est'] == 'A') {
            $config['estado_texto'] = 'ACTIVO';
        } else if ($config['Tur_Est'] == 'F') {
            $config['estado_texto'] = 'FINALIZADO';
        } else if ($config['Tur_Est'] == 'I') {
            $config['estado_texto'] = 'INACTIVO';
        } else {
            $config['estado_texto'] = 'SUSPENDIDO';
        }
        
        // Verificar si algún detalle de turno está siendo usado en la tabla manifiesto
        $tieneTurnosEnUso = false;
        if (!empty($detalles)) {
            $tudCodArray = array();
            foreach ($detalles as $detalle) {
                $tudCodArray[] = intval($detalle['Tud_Cod']);
            }
            
            if (!empty($tudCodArray)) {
                $tudCodList = implode(',', $tudCodArray);
                $sql_check = "SELECT COUNT(*) as total FROM manifiesto WHERE Tud_Cod IN ($tudCodList) AND Man_Est = 'A'";
                $result_check = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql_check, $obBD_conexion->conexion));
                
                if (!empty($result_check) && intval($result_check['total']) > 0) {
                    $tieneTurnosEnUso = true;
                }
            }
        }
        $config['tiene_turnos_en_uso'] = $tieneTurnosEnUso;
        
        // Obtener el nombre del usuario que creó el turno
        if (!empty($config['Usu_Cod'])) {
            $usuario = $obBD_con1->getRowConsulta('usuarios.selectWhere', 
                array('where' => array('Usu_Cod' => $config['Usu_Cod'])), 
                $obBD_conexion);
            if (!empty($usuario) && !empty($usuario['Prs_Cod'])) {
                $persona = $obBD_con1->getRowConsulta('persona.selectWhere', 
                    array('where' => array('Prs_Cod' => $usuario['Prs_Cod'])), 
                    $obBD_conexion);
                if (!empty($persona)) {
                    $config['usuario_creador'] = trim($persona['Prs_Nom'] . ' ' . $persona['Prs_Ape']);
                } else {
                    $config['usuario_creador'] = 'N/A';
                }
            } else {
                $config['usuario_creador'] = 'N/A';
            }
        } else {
            $config['usuario_creador'] = 'N/A';
        }
    }
    unset($config);
    
    $resultado['rows'] = $configs;
    $obBD_con1->echoJson($resultado);
}

// Endpoint AJAX específico para filtrar turnos
if (isset($filtroTurnosAjax)) {
    $resultado = array('success' => true);
    
    // Construir condiciones de búsqueda
    $where = array('Emp_Cod' => $Ses_Emp_Cod);
    
    // Filtro por estado - solo aplicar si tiene un valor válido (no vacío)
    if (isset($_POST['filtro_estado']) && $_POST['filtro_estado'] !== '' && $_POST['filtro_estado'] !== null && $_POST['filtro_estado'] !== 'T') {
        $where['Tur_Est'] = trim($_POST['filtro_estado']);
    }
    
    // Filtro por fecha inicio - usar BETWEEN si hay ambas fechas
    $fechaDesde = isset($_POST['filtro_fecha_inicio_desde']) && !empty($_POST['filtro_fecha_inicio_desde']) ? $_POST['filtro_fecha_inicio_desde'] : null;
    $fechaHasta = isset($_POST['filtro_fecha_inicio_hasta']) && !empty($_POST['filtro_fecha_inicio_hasta']) ? $_POST['filtro_fecha_inicio_hasta'] : null;
    
    if ($fechaDesde && $fechaHasta) {
        // Usar BETWEEN cuando hay ambas fechas
        // Usar clave numérica para que se procese como condición literal
        $where[0] = "Tur_Fei BETWEEN '$fechaDesde' AND '$fechaHasta'";
    } else if ($fechaDesde) {
        // Solo fecha desde
        $where['Tur_Fei >='] = $fechaDesde;
    } else if ($fechaHasta) {
        // Solo fecha hasta
        $where['Tur_Fei <='] = $fechaHasta;
    }
    
    $configs = $obBD_con1->getArrayConsulta('manifiesto_turnos_cab.selectWhere', array('where' => $where, 'order' => 'Tur_Sys DESC'), $obBD_conexion, true);     
    // Agregar información adicional a cada configuración
    foreach ($configs as &$config) {
        // Contar turnos activos e inactivos
        $detalles = $obBD_con1->getArrayConsulta('manifiesto_turnos_det.selectWhere', 
            array('where' => array('Tur_Cod' => $config['Tur_Cod'])), 
            $obBD_conexion, true);
        
        $activos = 0;
        $inactivos = 0;
        $totalCupos = 0;
        
        foreach ($detalles as $det) {
            if ($det['Tud_Est'] == 'A') {
                $activos++;
                $totalCupos += intval($det['Tud_Cup']);
            } else if ($det['Tud_Est'] == 'S') {
                $inactivos++;
            }
        }
        
        $config['turnos_activos'] = $activos;
        $config['turnos_inactivos'] = $inactivos;
        $config['total_cupos'] = $totalCupos;
        $config['total_turnos'] = count($detalles);
        if ($config['Tur_Est'] == 'A') {
            $config['estado_texto'] = 'ACTIVO';
        } else if ($config['Tur_Est'] == 'F') {
            $config['estado_texto'] = 'FINALIZADO';
        } else if ($config['Tur_Est'] == 'I') {
            $config['estado_texto'] = 'INACTIVO';
        } else {
            $config['estado_texto'] = 'SUSPENDIDO';
        }
        
        // Verificar si algún detalle de turno está siendo usado en la tabla manifiesto
        $tieneTurnosEnUso = false;
        if (!empty($detalles)) {
            $tudCodArray = array();
            foreach ($detalles as $detalle) {
                $tudCodArray[] = intval($detalle['Tud_Cod']);
            }
            
            if (!empty($tudCodArray)) {
                $tudCodList = implode(',', $tudCodArray);
                $sql_check = "SELECT COUNT(*) as total FROM manifiesto WHERE Tud_Cod IN ($tudCodList) AND Man_Est = 'A'";
                $result_check = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql_check, $obBD_conexion->conexion));
                
                if (!empty($result_check) && intval($result_check['total']) > 0) {
                    $tieneTurnosEnUso = true;
                }
            }
        }
        $config['tiene_turnos_en_uso'] = $tieneTurnosEnUso;
        
        // Obtener el nombre del usuario que creó el turno
        if (!empty($config['Usu_Cod'])) {
            $usuario = $obBD_con1->getRowConsulta('usuarios.selectWhere', 
                array('where' => array('Usu_Cod' => $config['Usu_Cod'])), 
                $obBD_conexion);
            if (!empty($usuario) && !empty($usuario['Prs_Cod'])) {
                $persona = $obBD_con1->getRowConsulta('persona.selectWhere', 
                    array('where' => array('Prs_Cod' => $usuario['Prs_Cod'])), 
                    $obBD_conexion);
                if (!empty($persona)) {
                    $config['usuario_creador'] = trim($persona['Prs_Nom'] . ' ' . $persona['Prs_Ape']);
                } else {
                    $config['usuario_creador'] = 'N/A';
                }
            } else {
                $config['usuario_creador'] = 'N/A';
            }
        } else {
            $config['usuario_creador'] = 'N/A';
        }
    }
    unset($config);
    
    $resultado['rows'] = $configs;
    $obBD_con1->echoJson($resultado);
    exit;
}

// Obtener configuración de turnos específica por ID
if (isset($getTurnosConfigAjax)) {
    $resp = array('success' => true, 'turnos' => array(), 'config' => null);
    
    if (isset($Tur_Cod) && !empty($Tur_Cod)) {
        // Buscar configuración específica
        $config = $obBD_con1->getRowConsulta('manifiesto_turnos_cab.selectWhere', 
            array('where' => array('Tur_Cod' => $Tur_Cod)), 
            $obBD_conexion);
    } else {
        // Buscar configuración activa para la empresa
        $config = $obBD_con1->getRowConsulta('manifiesto_turnos_cab.selectWhere', 
            array('where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Tur_Est' => 'A')), 
            $obBD_conexion);
    }
    
    if (!empty($config)) {
        $resp['config'] = $config;
        
        // Si se especifica una fecha, obtener turnos solo de ese día
        if (isset($fecha) && !empty($fecha)) {
            $turnos = $obBD_con1->getArrayConsulta('manifiesto_turnos_det.selectWhere', 
                array('where' => array('Tur_Cod' => $config['Tur_Cod'], 'Tud_Fec' => $fecha), 'setWhere' => array('orderByDes')), 
                $obBD_conexion, true);
        } else {
            // Obtener todos los turnos agrupados por fecha
            $turnos = $obBD_con1->getArrayConsulta('manifiesto_turnos_det.selectWhere', 
                array('where' => array('Tur_Cod' => $config['Tur_Cod']), 'setWhere' => array('orderByDes')), 
                $obBD_conexion, true);
        }
        $resp['turnos'] = $turnos;
    }
    
    $obBD_con1->echoJson($resp);
}

// Obtener lista de celdas con estructura jerárquica
if (isset($listarCeldasAjax)) {
    $resultado = array('success' => true);
    try {
        // Obtener grupos (Cel_Tip = 'G')
        // Usar filtro_tipo para que el modelo filtre correctamente por tipo
        // El filtro_tipo debe estar en el array principal, no en where
        $grupos = $obBD_con1->getArrayConsulta('manifiesto_celdas.selectWhere', 
            array('where' => array('Cel_Est' => 'A'), 'setWhere' => array('setEmpCod', 'getGrupos', 'orderByCelCod')), 
            $obBD_conexion, true);
        
        // Obtener detalles (Cel_Tip = 'D')
        $detalles = $obBD_con1->getArrayConsulta('manifiesto_celdas.selectWhere', 
            array('where' => array('Cel_Est' => 'A'),  'setWhere' => array('setEmpCod', 'getDetalles', 'orderByCelCod')), 
            $obBD_conexion, true);
        
        // Verificar que solo se obtengan los registros correctos
        // Filtrar manualmente por si acaso el modelo no está filtrando correctamente
        // Re-indexar el array después del filtro
        $grupos = array_values(array_filter($grupos, function($g) { 
            return isset($g['Cel_Tip']) && $g['Cel_Tip'] == 'G'; 
        }));
        $detalles = array_values(array_filter($detalles, function($d) { 
            return isset($d['Cel_Tip']) && $d['Cel_Tip'] == 'D'; 
        }));
        
        // Estructurar jerárquicamente: grupos con sus detalles
        $celdas_jerarquicas = array();
        
        foreach($grupos as $grupo){
            // Agregar el grupo
            $celdas_jerarquicas[] = array(
                'Cel_Cod' => $grupo['Cel_Cod'],
                'Cel_Nom' => $grupo['Cel_Nom'],
                'Cel_Num' => $grupo['Cel_Num'],
                'Cel_Tip' => 'G',
                'Cel_Rec' => $grupo['Cel_Rec'],
                'es_grupo' => true,
                'nivel' => 0
            );
            
            // Buscar y agregar los detalles de este grupo
            foreach($detalles as $detalle){
                if(isset($detalle['Cel_Rec']) && $detalle['Cel_Rec'] == $grupo['Cel_Cod']){
                    $celdas_jerarquicas[] = array(
                        'Cel_Cod' => $detalle['Cel_Cod'],
                        'Cel_Nom' => $detalle['Cel_Nom'],
                        'Cel_Num' => $detalle['Cel_Num'],
                        'Cel_Tip' => 'D',
                        'Cel_Rec' => $detalle['Cel_Rec'],
                        'es_grupo' => false,
                        'nivel' => 1,
                        'grupo_cod' => $grupo['Cel_Cod'],
                        'grupo_nom' => $grupo['Cel_Nom']
                    );
                }
            }
        }
        
        // Agregar detalles sin grupo (Cel_Rec es null o 0)
        foreach($detalles as $detalle){
            if(empty($detalle['Cel_Rec']) || $detalle['Cel_Rec'] == 0 || $detalle['Cel_Rec'] == null){
                $celdas_jerarquicas[] = array(
                    'Cel_Cod' => $detalle['Cel_Cod'],
                    'Cel_Nom' => $detalle['Cel_Nom'],
                    'Cel_Num' => $detalle['Cel_Num'],
                    'Cel_Tip' => 'D',
                    'Cel_Rec' => null,
                    'es_grupo' => false,
                    'nivel' => 0
                );
            }
        }
        
        $resultado['celdas'] = $celdas_jerarquicas;
    } catch (Exception $e) {
        $resultado['success'] = false;
        $resultado['message'] = 'Error al cargar celdas: ' . $e->getMessage();
        $resultado['celdas'] = array();
    }
    $obBD_con1->echoJson($resultado);
}

// Obtener turnos agrupados por día
if (isset($getTurnosPorDiaAjax)) {
    $resp = array('success' => true, 'turnosPorDia' => array(), 'config' => null);
    
    if (isset($Tur_Cod) && !empty($Tur_Cod)) {
        $config = $obBD_con1->getRowConsulta('manifiesto_turnos_cab.selectWhere', 
            array('where' => array('Tur_Cod' => $Tur_Cod)), 
            $obBD_conexion);
    } else {
        $config = $obBD_con1->getRowConsulta('manifiesto_turnos_cab.selectWhere', 
            array('where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Tur_Est' => 'A')), 
            $obBD_conexion);
    }
    
    if (!empty($config)) {
        $resp['config'] = $config;
        
        // Obtener todos los turnos
        $turnos = $obBD_con1->getArrayConsulta('manifiesto_turnos_det.selectWhere', 
            array('where' => array('Tur_Cod' => $config['Tur_Cod']), 'setWhere' => array('orderByDes')), 
            $obBD_conexion, true);
        
        // Obtener reservas para estos turnos
        $reservasMap = array();
        if (!empty($turnos)) {
            $tudCods = array();
            foreach ($turnos as $t) {
                $tudCods[] = $t['Tud_Cod'];
            }
            
            if (!empty($tudCods)) {
                $tudList = implode(',', $tudCods);
                $reservas = $obBD_con1->getArrayConsulta('manifiesto_turno_reserva.selectWhere', 
                    array('where' => array("manifiesto_turno_reserva.Tud_Cod IN ($tudList)", 'Tre_Est' => 'A')), 
                    $obBD_conexion, true);
                
                // Agrupar reservas por Tud_Cod
                foreach ($reservas as $res) {
                    $tudCod = $res['Tud_Cod'];
                    if (!isset($reservasMap[$tudCod])) {
                        $reservasMap[$tudCod] = array();
                    }
                    
                    // Obtener nombre de la planta
                    $planta = $obBD_con1->getRowConsulta('manifiesto_plantas.selectWhere', 
                        array('where' => array('Pla_Cod' => $res['Pla_Cod'])), 
                        $obBD_conexion);
                        
                    $res['Pla_Nom'] = $planta ? $planta['Pla_Nom'] : 'Planta Desconocida';
                    // Asegurar que Tre_Can sea entero
                    $res['Tre_Can'] = intval($res['Tre_Can']);
                    $reservasMap[$tudCod][] = $res;
                }
            }
        }
        
        // Agrupar por fecha
        $turnosPorDia = array();
        foreach ($turnos as $turno) {
            $fecha = $turno['Tud_Fec'];
            if (!isset($turnosPorDia[$fecha])) {
                $turnosPorDia[$fecha] = array();
            }
            
            // Adjuntar reservas al turno
            if (isset($reservasMap[$turno['Tud_Cod']])) {
                $turno['reservas'] = $reservasMap[$turno['Tud_Cod']];
            } else {
                $turno['reservas'] = array();
            }
            
            $turnosPorDia[$fecha][] = $turno;
        }
        
        // Ordenar por fecha
        ksort($turnosPorDia);
        $resp['turnosPorDia'] = $turnosPorDia;
    }
    
    $obBD_con1->echoJson($resp);
}

// Validar solapamiento de fechas con turnos existentes
if (isset($validarFechasTurnosAjax)) {
    $resp = array('success' => true, 'haySolapamiento' => false);
    
    $fechaInicio = isset($fecha_inicio) ? $fecha_inicio : $hoy;
    $fechaFin = isset($fecha_fin) ? $fecha_fin : '2099-12-31';
    
    // Validar formato de fechas
    if (empty($fechaInicio) || empty($fechaFin)) {
        $resp['success'] = false;
        $resp['message'] = 'Debe seleccionar fechas de inicio y fin';
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    // Validar que fecha inicio sea menor o igual a fecha fin
    if ($fechaInicio > $fechaFin) {
        $resp['success'] = false;
        $resp['message'] = 'La fecha de inicio debe ser menor o igual a la fecha de fin';
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    // Buscar todas las configuraciones existentes (activas o inactivas) que tengan turnos
    $configsExistentes = $obBD_con1->getArrayConsulta('manifiesto_turnos_cab.selectWhere', 
        array('where' => array('Emp_Cod' => $Ses_Emp_Cod)), 
        $obBD_conexion, true);
    
    $fechasOcupadas = array();
    
    foreach ($configsExistentes as $config) {
        // Verificar si hay solapamiento de fechas
        // Solapamiento existe si: (fechaInicio <= config.Tur_Fef) AND (fechaFin >= config.Tur_Fei)
        if ($fechaInicio <= $config['Tur_Fef'] && $fechaFin >= $config['Tur_Fei']) {
            // Verificar si realmente hay turnos en ese rango
            $turnosExistentes = $obBD_con1->getArrayConsulta('manifiesto_turnos_det.selectWhere', 
                array('where' => array('Tur_Cod' => $config['Tur_Cod'])), 
                $obBD_conexion, true);
            
            if (!empty($turnosExistentes)) {
                // Obtener el rango real de fechas de los turnos existentes
                $fechasTurnos = array();
                foreach ($turnosExistentes as $turno) {
                    if (!empty($turno['Tud_Fec'])) {
                        $fechasTurnos[] = $turno['Tud_Fec'];
                    }
                }
                
                if (!empty($fechasTurnos)) {
                    $fechaMinTurnos = min($fechasTurnos);
                    $fechaMaxTurnos = max($fechasTurnos);
                    
                    // Verificar solapamiento real con las fechas de los turnos
                    if ($fechaInicio <= $fechaMaxTurnos && $fechaFin >= $fechaMinTurnos) {
                        $resp['haySolapamiento'] = true;
                        $resp['configExistente'] = array(
                            'Tur_Cod' => $config['Tur_Cod'],
                            'Tur_Fei' => $fechaMinTurnos,
                            'Tur_Fef' => $fechaMaxTurnos,
                            'Tur_Est' => $config['Tur_Est']
                        );
                        $resp['message'] = "Las fechas seleccionadas se solapan con una configuración existente (del $fechaMinTurnos al $fechaMaxTurnos). Por favor, seleccione fechas diferentes.";
                        break;
                    }
                }
            }
        }
    }
    
    $obBD_con1->echoJson($resp);
}

// Buscar plantas para el modal de reserva
if (isset($buscarPlantasAjax)) {
    $resultado = array('success' => true);
    $term = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    $where = array('Pla_Est' => 'A');
    if (!empty($term)) {
        // Búsqueda por nombre
        $where[] = "Pla_Nom LIKE '%$term%'";
    }
    
    $plantas = $obBD_con1->getArrayConsulta('manifiesto_plantas.selectWhere', 
        array('where' => $where, 'order' => 'Pla_Nom ASC', 'limit' => 500), 
        $obBD_conexion, true);
        
    $items = array();
    foreach ($plantas as $p) {
        $items[] = array(
            'id' => $p['Pla_Cod'],
            'text' => $p['Pla_Nom'],
            'licencia' => $p['Pla_Lic'],
            'direccion' => $p['Pla_Dir']
        );
    }
    
    $resultado['items'] = $items;
    $obBD_con1->echoJson($resultado);
}

// Guardar reserva de cupos
if (isset($guardarReservaAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    
    try {
        if (!isset($Tud_Cod) || empty($Tud_Cod)) {
            throw new Exception('No se especificó el turno');
        }
        
        // Verificar que el turno exista
        $turno = $obBD_con1->getRowConsulta('manifiesto_turnos_det.selectWhere', 
            array('where' => array('Tud_Cod' => $Tud_Cod)), 
            $obBD_conexion);
            
        if (empty($turno)) {
            throw new Exception('El turno no existe');
        }
        
        // Decodificar los datos de las plantas
        $plantas = isset($plantas_data) ? json_decode(stripslashes($plantas_data), true) : array();
        
        if (empty($plantas)) {
            throw new Exception('No se seleccionaron plantas para reservar');
        }
        
        // Inactivar todas las reservas previas del turno
        $obBD_con1->operacionobBD('manifiesto_turno_reserva.update', 
            array('Tre_Est' => 'I', 'where' => array('manifiesto_turno_reserva.Tud_Cod' => $Tud_Cod)), 
            $obBD_conexion, true);
        
        foreach ($plantas as $p) {
            // Verificar si ya existe el registro (activo o inactivo)
            $existe = $obBD_con1->getRowConsulta('manifiesto_turno_reserva.selectWhere', 
                array('where' => array('manifiesto_turno_reserva.Tud_Cod' => $Tud_Cod, 'manifiesto_turno_reserva.Pla_Cod' => $p['planta_id'])), 
                $obBD_conexion);
                
            if (!empty($existe)) {
                // Actualizar
                $obBD_con1->operacionobBD('manifiesto_turno_reserva.update', 
                    array(
                        'Tre_Can' => intval($p['cantidad']),
                        'Tre_Est' => 'A',
                        'where' => array('Tre_Cod' => $existe['Tre_Cod'])
                    ), 
                    $obBD_conexion, true);
            } else {
                // Insertar
                $datosReserva = array(
                    'Tud_Cod' => $Tud_Cod,
                    'Pla_Cod' => $p['planta_id'],
                    'Usu_Cod' => $Ses_Usu_Cod,
                    'Tre_Can' => intval($p['cantidad']),                
                    'Tre_Est' => 'A'
                );
                $obBD_con1->operacionobBD('manifiesto_turno_reserva.insert', $datosReserva, $obBD_conexion, true);
            }
        }
        
        $resp['message'] = 'Reserva guardada correctamente';
        
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Generar turnos automáticamente (rango de horas configurable)
if (isset($generarTurnosAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    
    try {
        $cuposPorDefecto = isset($cupos_default) ? intval($cupos_default) : 25;
        $fechaInicio = isset($fecha_inicio) ? $fecha_inicio : $hoy;
        $fechaFin = isset($fecha_fin) ? $fecha_fin : '2099-12-31';
        
        // Rango de horas (por defecto 00:00 a 23:00 = 24 horas)
        $horaInicioTurno = isset($hora_inicio_turno) ? intval($hora_inicio_turno) : 0;
        $horaFinTurno = isset($hora_fin_turno) ? intval($hora_fin_turno) : 24;
        
        // Celda asignada (opcional)
        $celCod = isset($celda_turno) && !empty($celda_turno) ? $celda_turno : null;
        
        // Validar rango de horas
        if ($horaInicioTurno < 0) $horaInicioTurno = 0;
        if ($horaFinTurno > 24) $horaFinTurno = 24;
        if ($horaInicioTurno >= $horaFinTurno) {
            $resp['message'] = 'La hora de inicio debe ser menor a la hora de fin';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        // Validar solapamiento de fechas ANTES de crear la nueva configuración
        $configsExistentes = $obBD_con1->getArrayConsulta('manifiesto_turnos_cab.selectWhere', 
            array('where' => array('Emp_Cod' => $Ses_Emp_Cod)), 
            $obBD_conexion, true);
        
        foreach ($configsExistentes as $config) {
            // Verificar solapamiento de fechas
            if ($fechaInicio <= $config['Tur_Fef'] && $fechaFin >= $config['Tur_Fei']) {
                // Verificar si realmente hay turnos en ese rango
                $turnosExistentes = $obBD_con1->getArrayConsulta('manifiesto_turnos_det.selectWhere', 
                    array('where' => array('Tur_Cod' => $config['Tur_Cod'])), 
                    $obBD_conexion, true);
                
                if (!empty($turnosExistentes)) {
                    $fechasTurnos = array();
                    foreach ($turnosExistentes as $turno) {
                        if (!empty($turno['Tud_Fec'])) {
                            $fechasTurnos[] = $turno['Tud_Fec'];
                        }
                    }
                    
                    if (!empty($fechasTurnos)) {
                        $fechaMinTurnos = min($fechasTurnos);
                        $fechaMaxTurnos = max($fechasTurnos);
                        
                        if ($fechaInicio <= $fechaMaxTurnos && $fechaFin >= $fechaMinTurnos) {
                            $obBD_con1->rollBack_nomsn($obBD_conexion);
                            $resp['message'] = "Las fechas seleccionadas se solapan con turnos existentes (del $fechaMinTurnos al $fechaMaxTurnos). Por favor, seleccione fechas diferentes.";
                            $obBD_con1->echoJson($resp);
                            exit();
                        }
                    }
                }
            }
        }
        
        // Verificar si ya existe una configuración activa
        $configExistente = $obBD_con1->getRowConsulta('manifiesto_turnos_cab.selectWhere', 
            array('where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Tur_Est' => 'A'),true), 
            $obBD_conexion);
        
        if (!empty($configExistente)) {
            // Inactivar la configuración anterior
            $obBD_con1->operacionobBD('manifiesto_turnos_cab.update', 
                array('Tur_Est' => 'S', 'where' => array('Tur_Cod' => $configExistente['Tur_Cod'])), 
                $obBD_conexion,true);
        }
        
        // Crear nueva cabecera de turnos
        $datosCab = array( 
            'Usu_Cod' => $Ses_Usu_Cod,
            'Emp_Cod' => $Ses_Emp_Cod,
            'Tur_Fei' => $fechaInicio,
            'Tur_Fef' => $fechaFin,
            'Tur_Est' => 'A',
            'Tur_Sys' => date('Y-m-d H:i:s')
        );
        $obBD_con1->operacionobBD('manifiesto_turnos_cab.insert', $datosCab, $obBD_conexion,true);
        $Tur_Cod = $obBD_con1->insercionid($obBD_conexion);
        
        // Calcular días en el rango
        $fechaInicioObj = new DateTime($fechaInicio);
        $fechaFinObj = new DateTime($fechaFin);
        $cantidadDias = $fechaInicioObj->diff($fechaFinObj)->days + 1;
        
        // Verificar si vienen turnos personalizados desde la previsualización
        $cantidadTurnos = 0;
        $filasDet = array();
        $turnosConReservas = array();
        
        if (isset($turnos_personalizados) && !empty($turnos_personalizados)) {
            // Usar los turnos personalizados desde la previsualización
            $turnosCustom = json_decode(stripslashes($turnos_personalizados), true);
            
            if (!empty($turnosCustom)) {
                foreach ($turnosCustom as $idx => $turno) {
                    if (!isset($turno['fecha']) || !isset($turno['hora_inicio']) || !isset($turno['hora_fin'])) continue;
                    $fechaTurno = $turno['fecha'];
                    if ($fechaTurno < $fechaInicio || $fechaTurno > $fechaFin) continue;
                    
                    $tudEst = isset($turno['estado']) ? ($turno['estado'] == 'I' ? 'S' : $turno['estado']) : 'A';
                    $celCodRow = isset($turno['cel_cod']) && !empty($turno['cel_cod']) ? intval($turno['cel_cod']) : ($celCod ? intval($celCod) : null);
                    
                    $filasDet[] = array(
                        'Tur_Cod' => $Tur_Cod,
                        'Tud_Fec' => $fechaTurno,
                        'Tud_Hin' => $turno['hora_inicio'],
                        'Tud_Hfi' => $turno['hora_fin'],
                        'Tud_Cup' => isset($turno['cupos']) ? intval($turno['cupos']) : $cuposPorDefecto,
                        'Tud_Est' => $tudEst,
                        'Cel_Cod' => $celCodRow
                    );
                    if (isset($turno['reservas']) && !empty($turno['reservas'])) {
                        $turnosConReservas[] = array('idx' => count($filasDet) - 1, 'reservas' => $turno['reservas']);
                    }
                }
            }
        } else {
            // Generar turnos automáticamente para cada día del rango
            $fechaActual = clone $fechaInicioObj;
            while ($fechaActual <= $fechaFinObj) {
                $fechaActualStr = $fechaActual->format('Y-m-d');
                for ($i = $horaInicioTurno; $i < $horaFinTurno; $i++) {
                    $horaInicio = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                    $horaFin = str_pad(($i + 1) % 24, 2, '0', STR_PAD_LEFT) . ':00';
                    $filasDet[] = array(
                        'Tur_Cod' => $Tur_Cod,
                        'Tud_Fec' => $fechaActualStr,
                        'Tud_Hin' => $horaInicio,
                        'Tud_Hfi' => $horaFin,
                        'Tud_Cup' => $cuposPorDefecto,
                        'Tud_Est' => 'A',
                        'Cel_Cod' => $celCod ? intval($celCod) : null
                    );
                }
                $fechaActual->modify('+1 day');
            }
        }
        
        // INSERT masivo: un solo INSERT con múltiples registros
        if (!empty($filasDet)) {
            $conn = isset($obBD_conexion->conexion) ? $obBD_conexion->conexion : null;
            $valuesArr = array();
            foreach ($filasDet as $row) {
                $cel = ($row['Cel_Cod'] !== null && $row['Cel_Cod'] > 0) ? intval($row['Cel_Cod']) : 'NULL';
                $fec = $conn ? "'" . mysqli_real_escape_string($conn, $row['Tud_Fec']) . "'" : "'" . addslashes($row['Tud_Fec']) . "'";
                $hin = $conn ? "'" . mysqli_real_escape_string($conn, $row['Tud_Hin']) . "'" : "'" . addslashes($row['Tud_Hin']) . "'";
                $hfi = $conn ? "'" . mysqli_real_escape_string($conn, $row['Tud_Hfi']) . "'" : "'" . addslashes($row['Tud_Hfi']) . "'";
                $est = $conn ? "'" . mysqli_real_escape_string($conn, $row['Tud_Est']) . "'" : "'" . addslashes($row['Tud_Est']) . "'";
                $valuesArr[] = '(' . intval($row['Tur_Cod']) . ',' . $fec . ',' . $hin . ',' . $hfi . ',' . intval($row['Tud_Cup']) . ',' . $est . ',' . $cel . ')';
            }
            $sqlBulk = 'INSERT INTO manifiesto_turnos_det (Tur_Cod,Tud_Fec,Tud_Hin,Tud_Hfi,Tud_Cup,Tud_Est,Cel_Cod) VALUES ' . implode(',', $valuesArr);
            $obBD_con1->grabarv_registros($sqlBulk, $obBD_conexion);
            $cantidadTurnos = count($filasDet);
            
            // Guardar reservas para turnos personalizados (Tud_Cod es secuencial tras bulk insert)
            if (!empty($turnosConReservas)) {
                $firstTudCod = $obBD_con1->insercionid($obBD_conexion);
                foreach ($turnosConReservas as $tr) {
                    $tudCodInsertado = $firstTudCod + $tr['idx'];
                    foreach ($tr['reservas'] as $res) {
                        if (isset($res['planta_id']) && isset($res['cantidad'])) {
                            $datosReserva = array(
                                'Tud_Cod' => $tudCodInsertado,
                                'Pla_Cod' => $res['planta_id'],
                                'Usu_Cod' => $Ses_Usu_Cod,
                                'Tre_Can' => intval($res['cantidad'])
                            );
                            $obBD_con1->operacionobBD('manifiesto_turno_reserva.insert', $datosReserva, $obBD_conexion, true);
                        }
                    }
                }
            }
        }
        
        $cantidadHoras = $horaFinTurno - $horaInicioTurno;
        $resp['Tur_Cod'] = $Tur_Cod;
        $resp['message'] = "Se guardaron $cantidadTurnos turnos ($cantidadDias días × $cantidadHoras horas/día) correctamente";
        
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Activar una configuración de turnos
if (isset($activarTurnosCabAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    
    try {
        // Inactivar todas las configuraciones actuales
        $obBD_con1->operacionobBD('manifiesto_turnos_cab.update', 
            array('Tur_Est' => 'I', 'where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Tur_Est' => 'A')), 
            $obBD_conexion);
        
        // Activar la configuración seleccionada
        $obBD_con1->operacionobBD('manifiesto_turnos_cab.update', 
            array('Tur_Est' => 'A', 'where' => array('Tur_Cod' => $Tur_Cod)), 
            $obBD_conexion);
        
        $resp['message'] = 'Configuración activada correctamente';
        
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Eliminar (inactivar) una configuración de turnos
if (isset($eliminarTurnosCabAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    
    try {
        $obBD_con1->operacionobBD('manifiesto_turnos_cab.update', 
            array('Tur_Est' => 'I', 'where' => array('Tur_Cod' => $Tur_Cod)), 
            $obBD_conexion);
        
        $resp['message'] = 'Configuración eliminada correctamente';
        
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Actualizar todos los turnos masivamente
if (isset($updateTurnosMasivoAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    
    try {
        $turnos = json_decode(stripslashes($turnos_data), true);
        
        foreach ($turnos as $turno) {
            $datosTurno = array(
                'Tud_Cup' => intval($turno['cupos']),
                'Tud_Est' => $turno['estado'],
                'where' => array('Tud_Cod' => $turno['id'])
            );
            
            // Agregar Cel_Cod si está presente
            if (isset($turno['cel_cod']) && !empty($turno['cel_cod'])) {
                $datosTurno['Cel_Cod'] = $turno['cel_cod'];
            } else {
                // Si viene vacío o null, establecer como NULL en la base de datos
                $datosTurno['Cel_Cod'] = null;
            }
            
            $obBD_con1->operacionobBD('manifiesto_turnos_det.update', $datosTurno, $obBD_conexion);
        }
        
        $resp['message'] = 'Turnos actualizados correctamente';
        
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Cambiar estado de un turno (habilitar/deshabilitar)
if (isset($toggleTurnoAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    
    try {
        // Obtener estado actual
        $turno = $obBD_con1->getRowConsulta('manifiesto_turnos_det.selectWhere', 
            array('where' => array('Tud_Cod' => $Tud_Cod)), 
            $obBD_conexion);
        
        $nuevoEstado = ($turno['Tud_Est'] == 'A') ? 'S' : 'A';
        
        $obBD_con1->operacionobBD('manifiesto_turnos_det.update', 
            array('Tud_Est' => $nuevoEstado, 'where' => array('Tud_Cod' => $Tud_Cod)), 
            $obBD_conexion);
        
        $resp['nuevo_estado'] = $nuevoEstado;
        
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Insertar nuevo turno detalle (rango de fechas)
if (isset($insertarTurnoDetAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    
    try {
        if (!isset($Tur_Cod) || empty($Tur_Cod)) {
            $resp['message'] = 'No se especificó el código de configuración';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        // Validar que exista la configuración
        $config = $obBD_con1->getRowConsulta('manifiesto_turnos_cab.selectWhere', 
            array('where' => array('Tur_Cod' => $Tur_Cod)), 
            $obBD_conexion);
        
        if (empty($config)) {
            $resp['message'] = 'La configuración de turnos no existe';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        // Validar rango de fechas
        $fechaDesde = isset($fecha_turno_desde) ? $fecha_turno_desde : null;
        $fechaHasta = isset($fecha_turno_hasta) ? $fecha_turno_hasta : null;
        
        if (empty($fechaDesde) || empty($fechaHasta)) {
            $resp['message'] = 'Debe especificar fecha desde y fecha hasta';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        if ($fechaDesde > $fechaHasta) {
            $resp['message'] = 'La fecha desde debe ser menor o igual a la fecha hasta';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        // Validar que las fechas estén dentro del rango de la configuración
        if ($fechaDesde < $config['Tur_Fei'] || $fechaHasta > $config['Tur_Fef']) {
            $resp['message'] = 'Las fechas deben estar dentro del rango de la configuración (' . $config['Tur_Fei'] . ' al ' . $config['Tur_Fef'] . ')';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        // Validar horas
        $horaInicio = isset($hora_inicio) ? $hora_inicio : '00:00';
        $horaFin = isset($hora_fin) ? $hora_fin : '01:00';
        
        // Convertir horas a enteros para comparar
        $horaInicioInt = intval(substr($horaInicio, 0, 2));
        $horaFinInt = intval(substr($horaFin, 0, 2));
        
        // Si hora fin es 00:00, es porque es 24:00, entonces es 24
        if ($horaFin === '00:00' && $horaInicioInt > 0) {
            $horaFinInt = 24;
        }
        
        if ($horaInicioInt >= $horaFinInt && $horaFin !== '00:00') {
            $resp['message'] = 'La hora de inicio debe ser menor a la hora de fin';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        // Validar cupos
        $cupos = isset($cupos_turno) ? intval($cupos_turno) : 25;
        if ($cupos < 1) $cupos = 1;
        if ($cupos > 100) $cupos = 100;
        
        // Estado
        $estado = isset($estado_turno) && $estado_turno === 'S' ? 'S' : 'A';
        
        // Celda (opcional)
        $celCod = isset($celda_turno) && !empty($celda_turno) ? $celda_turno : null;
        
        // Calcular días en el rango
        $fechaInicioObj = new DateTime($fechaDesde);
        $fechaFinObj = new DateTime($fechaHasta);
        $cantidadDias = $fechaInicioObj->diff($fechaFinObj)->days + 1;
        
        // Generar turnos para cada día del rango
        $turnosInsertados = 0;
        $turnosDuplicados = 0;
        $fechaActual = clone $fechaInicioObj;
        
        while ($fechaActual <= $fechaFinObj) {
            $fechaActualStr = $fechaActual->format('Y-m-d');
            
            // Validar que no exista un turno con la misma fecha y rango de horas
            $turnosExistentes = $obBD_con1->getArrayConsulta('manifiesto_turnos_det.selectWhere', 
                array('where' => array('Tur_Cod' => $Tur_Cod, 'Tud_Fec' => $fechaActualStr)), 
                $obBD_conexion, true);
            
            $existeDuplicado = false;
            foreach ($turnosExistentes as $turno) {
                if ($turno['Tud_Hin'] === $horaInicio && $turno['Tud_Hfi'] === $horaFin) {
                    $existeDuplicado = true;
                    $turnosDuplicados++;
                    break;
                }
            }
            
            // Solo insertar si no existe duplicado
            if (!$existeDuplicado) {
                $datosDet = array(
                    'Tur_Cod' => $Tur_Cod,
                    'Tud_Fec' => $fechaActualStr,
                    'Tud_Hin' => $horaInicio,
                    'Tud_Hfi' => $horaFin,
                    'Tud_Cup' => $cupos,
                    'Tud_Est' => $estado
                );
                
                if ($celCod) {
                    $datosDet['Cel_Cod'] = $celCod;
                }
                
                $obBD_con1->operacionobBD('manifiesto_turnos_det.insert', $datosDet, $obBD_conexion, true);
                $turnosInsertados++;
            }
            
            $fechaActual->modify('+1 day');
        }
        
        // Construir mensaje
        if ($turnosInsertados > 0) {
            if ($turnosDuplicados > 0) {
                $resp['message'] = "Se insertaron $turnosInsertados turno(s) correctamente. $turnosDuplicados turno(s) no se insertaron porque ya existían con la misma fecha y rango de horas.";
                $resp['warning'] = true;
            } else {
                $resp['message'] = "Se insertaron $turnosInsertados turno(s) correctamente para $cantidadDias día(s)";
            }
        } else {
            $resp['message'] = "No se insertaron turnos. Todos los turnos en el rango de fechas ya existen con la misma hora.";
            $resp['success'] = false; // Marcar como error si no se insertó ninguno
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        $resp['turnos_insertados'] = $turnosInsertados;
        $resp['turnos_duplicados'] = $turnosDuplicados;
        
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = 'Error: ' . $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Guardar reserva masiva
if (isset($guardarReservaMasivaAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    
    try {
        if (!isset($Tur_Cod) || empty($Tur_Cod)) {
            throw new Exception('No se especificó el código de configuración');
        }
        
        if (empty($fec_ini) || empty($fec_fin)) {
            throw new Exception('Debe especificar el rango de fechas');
        }
        
        if (empty($plantas) || !is_array($plantas)) {
            throw new Exception('No se seleccionaron plantas para reservar');
        }
        
        // Iterar sobre las fechas
        $fechaInicioObj = new DateTime($fec_ini);
        $fechaFinObj = new DateTime($fec_fin);
        $fechaFinObj->modify('+1 day'); // Incluir el último día
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($fechaInicioObj, $interval, $fechaFinObj);
        
        $count = 0;
        
        foreach ($period as $dt) {
            $fecha = $dt->format('Y-m-d');
            
            // Buscar turnos para esta fecha y configuración
            $turnosDia = $obBD_con1->getArrayConsulta('manifiesto_turnos_det.selectWhere', 
                array('where' => array('manifiesto_turnos_det.Tur_Cod' => $Tur_Cod, 'Tud_Fec' => $fecha, 'Tud_Est' => 'A')), 
                $obBD_conexion, true);
                
            if (!empty($turnosDia)) {
                foreach ($turnosDia as $turno) {
                    // Verificar horario (string compare works for HH:mm:ss vs HH:mm if left aligned)
                    // Tud_Hin y Tud_Hfi de DB suelen ser HH:mm:ss. Inputs son HH:mm.
                    // Aseguramos formato comparable.
                    $tudHin = substr($turno['Tud_Hin'], 0, 5);
                    $tudHfi = substr($turno['Tud_Hfi'], 0, 5);
                    
                    if ($tudHin >= $h_ini && $tudHfi <= $h_fin) {
                        // Aplicar reservas
                        foreach ($plantas as $planta) {
                            // Verificar si existe
                            $existe = $obBD_con1->getRowConsulta('manifiesto_turno_reserva.selectWhere', 
                                array('where' => array('manifiesto_turno_reserva.Tud_Cod' => $turno['Tud_Cod'], 'manifiesto_turno_reserva.Pla_Cod' => $planta['planta_id'])), 
                                $obBD_conexion);
                                
                            if (!empty($existe)) {
                                $obBD_con1->operacionobBD('manifiesto_turno_reserva.update', 
                                    array(
                                        'Tre_Can' => $planta['cantidad'],
                                        'Tre_Est' => 'A',
                                        'where' => array('Tre_Cod' => $existe['Tre_Cod'])
                                    ), 
                                    $obBD_conexion);
                            } else {
                                $datosReserva = array(
                                    'Tud_Cod' => $turno['Tud_Cod'],
                                    'Pla_Cod' => $planta['planta_id'],
                                    'Usu_Cod' => $Ses_Usu_Cod,
                                    'Tre_Can' => $planta['cantidad'],
                                    'Tre_Est' => 'A'
                                );
                                $obBD_con1->operacionobBD('manifiesto_turno_reserva.insert', $datosReserva, $obBD_conexion);
                            }
                            $count++;
                        }
                    }
                }
            }
        }
        
        $resp['count'] = $count;
        $resp['success'] = true;
        
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Obtener reservas de un turno
if (isset($obtenerReservasTurnoAjax)) {
    $resp = array('success' => false, 'items' => array());
    try {
        if (!isset($Tud_Cod) || empty($Tud_Cod)) {
            throw new Exception('No se especificó el turno');
        }
        $items = $obBD_con1->getArrayConsulta('manifiesto_turno_reserva.selectWhere',array('where' => array('manifiesto_turno_reserva.Tud_Cod' => $Tud_Cod, 'Tre_Est' => 'A')),$obBD_conexion, true);
        $resp['items'] = $items;
        $resp['success'] = true;
    } catch (Exception $e) {
        $resp['message'] = $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
}

// Eliminar (inactivar) reserva de una planta en un turno
if (isset($eliminarReservaAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        if (empty($Tud_Cod) || empty($Pla_Cod)) {
            throw new Exception('Datos insuficientes para eliminar la reserva');
        }
        $obBD_con1->operacionobBD('manifiesto_turno_reserva.update',
            array('Tre_Est' => 'I', 'where' => array('Tud_Cod' => $Tud_Cod, 'Pla_Cod' => $Pla_Cod)),
            $obBD_conexion);
        $resp['success'] = true;
        $resp['message'] = 'Reserva eliminada';
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit();
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?php echo "EXA [Turnos]"; ?></TITLE>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="../../framework/jquery/jquery.mask/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js"></script>
    <script>
        (function($){
            if (!$) return;
            if (!$.alert) {
                $.alert = function(arg){
                    if (typeof arg === 'string') {
                        alert(String(arg).replace(/<[^>]*>/g,''));
                    } else {
                        var t = (arg && arg.title) ? arg.title + '\n' : '';
                        var c = (arg && arg.content) ? arg.content : '';
                        alert(String(t + c).replace(/<[^>]*>/g,''));
                        if (arg && typeof arg.onClose === 'function') arg.onClose();
                    }
                };
            }
            if (!$.confirm) {
                $.confirm = function(opts){
                    var t = (opts && opts.title) ? opts.title + '\n' : '';
                    var c = (opts && opts.content) ? opts.content : '';
                    var msg = String(t + c).replace(/<[^>]*>/g,'');
                    if (confirm(msg)) {
                        if (opts && opts.buttons && opts.buttons.confirm && typeof opts.buttons.confirm.action === 'function') {
                            opts.buttons.confirm.action();
                        }
                    }
                };
            }
        })(window.jQuery);
    </script>
    <style>
        /* Estilos personalizados para la pantalla de turnos */
        .turnos-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            padding: 15px;
        }
        
        .turno-card {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .turno-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .turno-card.activo {
            border-color: #28a745;
            background: linear-gradient(145deg, #d4edda, #c3e6cb);
        }
        
        .turno-card.inactivo {
            border-color: #dc3545;
            background: linear-gradient(145deg, #f8d7da, #f1b0b7);
            opacity: 0.7;
        }
        
        .turno-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .turno-hora {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .turno-card.inactivo .turno-hora {
            text-decoration: line-through;
            color: #999;
        }
        
        /* Estilos compactos para previsualización */
        #turnosContainerPreview .turnos-container {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px;
            padding: 10px;
        }
        
        #turnosContainerPreview .turno-card {
            padding: 8px;
            border-radius: 6px;
            border-width: 1px;
        }
        
        #turnosContainerPreview .turno-card:hover {
            transform: translateY(-1px);
        }
        
        #turnosContainerPreview .turno-header {
            margin-bottom: 5px;
            padding-bottom: 5px;
        }
        
        #turnosContainerPreview .turno-hora {
            font-size: 13px;
        }
        
        #turnosContainerPreview .turno-toggle {
            padding: 2px 6px;
            font-size: 9px;
        }
        
        #turnosContainerPreview .turno-cupos {
            gap: 5px;
        }
        
        #turnosContainerPreview .turno-cupos label {
            font-size: 11px;
        }
        
        #turnosContainerPreview .turno-cupos input {
            width: 50px;
            font-size: 12px;
            padding: 3px;
        }
        
        /* Estilos compactos para vista de detalle (igual que previsualización) */
        #turnosContainer .turnos-container {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px;
            padding: 10px;
        }
        
        #turnosContainer .turno-card {
            padding: 8px;
            border-radius: 6px;
            border-width: 1px;
        }
        
        #turnosContainer .turno-card:hover {
            transform: translateY(-1px);
        }
        
        #turnosContainer .turno-header {
            margin-bottom: 5px;
            padding-bottom: 5px;
        }
        
        #turnosContainer .turno-hora {
            font-size: 13px;
        }
        
        #turnosContainer .turno-toggle {
            padding: 2px 6px;
            font-size: 9px;
        }
        
        #turnosContainer .turno-cupos {
            gap: 5px;
        }
        
        #turnosContainer .turno-cupos label {
            font-size: 11px;
        }
        
        #turnosContainer .turno-cupos input {
            width: 50px;
            font-size: 12px;
            padding: 3px;
        }
        
        .turno-toggle {
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            border: none;
            transition: all 0.2s;
        }
        
        .turno-toggle.activo {
            background-color: #28a745;
            color: white;
        }
        
        .turno-toggle.inactivo {
            background-color: #dc3545;
            color: white;
        }
        
        .turno-cupos {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .turno-cupos label {
            font-size: 13px;
            color: #666;
            margin: 0;
        }
        
        .turno-cupos input {
            width: 70px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 5px;
            padding: 5px;
        }
        
        .turno-cupos input:focus {
            border-color: #007bff;
            outline: none;
        }
        
        .config-panel {
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .config-panel h4 {
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        
        .config-panel input,
        .config-panel select {
            color: #000000;
            background-color: #ffffff;
        }
        
        .config-row {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .config-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .config-item label {
            font-weight: bold;
            margin: 0;
        }
        
        .config-item input {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn-generar {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-generar:hover {
            background: #e0a800;
            transform: scale(1.05);
        }
        
        .acciones-masivas {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .acciones-masivas .btn {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .resumen-turnos {
            background: #e9ecef;
            padding: 10px 20px;
            border-radius: 8px;
            display: flex;
            gap: 30px;
        }
        
        .resumen-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .resumen-item .numero {
            font-size: 24px;
            font-weight: bold;
        }
        
        .resumen-item.activos .numero { color: #28a745; }
        .resumen-item.inactivos .numero { color: #dc3545; }
        .resumen-item.total-cupos .numero { color: #007bff; }
        
        .no-config {
            text-align: center;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px dashed #ddd;
        }
        
        .no-config i {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .no-config h4 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .no-config p {
            color: #999;
        }
        
        .badge-activo {
            background-color: #28a745;
            color: white;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        .badge-inactivo {
            background-color: #6c757d;
            color: white;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        .section-title {
            background: linear-gradient(90deg, #4a90a4, #357abd);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title h5 {
            margin: 0;
            font-size: 16px;
        }
        
        .dia-grupo {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .dia-grupo:last-child {
            border-bottom: none;
        }
        
        /* Estilos para modal de agregar turno */
        #modalAgregarTurno {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #modalAgregarTurno .modal-dialog {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
        }
        
        #modalAgregarTurno .modal-header {
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }
        
        #modalAgregarTurno .modal-header .close {
            font-size: 24px;
            font-weight: bold;
            opacity: 0.8;
            background: none;
            border: none;
            cursor: pointer;
        }
        
        #modalAgregarTurno .modal-header .close:hover {
            opacity: 1;
        }
        
        #modalAgregarTurno .modal-body .form-group {
            margin-bottom: 15px;
        }
        
        #modalAgregarTurno .modal-body label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }
        
        #modalAgregarTurno .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        
        #modalAgregarTurno .modal-footer .btn {
            margin-left: 10px;
        }
        
        /* Estilos para que el diálogo de confirmación aparezca sobre todos los modales */
        .ui-dialog.dialog-confirm-test {
            z-index: 20000 !important;
        }
        
        .ui-widget-overlay {
            z-index: 19999 !important;
        }
        
        /* Asegurar que el diálogo de confirmación esté por encima del modal de agregar turno */
        #modalAgregarTurno ~ .ui-dialog,
        .ui-dialog.dialog-confirm-test {
            z-index: 20000 !important;
        }
        
        /* Asegurar que el overlay de confirmación esté por encima del modal */
        body > .ui-widget-overlay {
            z-index: 19999 !important;
        }
        
        /* Tablas reserva (cupos y masiva): moderna, compacta y colores encendidos */
        #tablaPlantasReserva,
        #tablaPlantasReservaMasiva {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(240, 173, 78, 0.2);
            font-size: 13px;
        }
        #tablaPlantasReserva thead,
        #tablaPlantasReservaMasiva thead {
            background: linear-gradient(180deg, #ffb347 0%, #f0ad4e 50%, #ec971f 100%);
        }
        #tablaPlantasReserva thead th,
        #tablaPlantasReservaMasiva thead th {
            padding: 8px 10px;
            font-weight: 600;
            color: #fff;
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
            border: none;
            border-bottom: 2px solid #d58512;
            white-space: nowrap;
        }
        #tablaPlantasReserva tbody tr,
        #tablaPlantasReservaMasiva tbody tr {
            transition: background-color 0.15s ease;
        }
        #tablaPlantasReserva tbody tr:hover,
        #tablaPlantasReservaMasiva tbody tr:hover {
            background-color: #fff3e0;
        }
        #tablaPlantasReserva tbody tr:nth-child(even),
        #tablaPlantasReservaMasiva tbody tr:nth-child(even) {
            background-color: #fff8e7;
        }
        #tablaPlantasReserva tbody tr:nth-child(even):hover,
        #tablaPlantasReservaMasiva tbody tr:nth-child(even):hover {
            background-color: #ffe0b2;
        }
        #tablaPlantasReserva tbody td,
        #tablaPlantasReservaMasiva tbody td {
            padding: 6px 10px;
            vertical-align: middle;
            border: none;
            border-bottom: 1px solid #ffcc80;
        }
        #tablaPlantasReserva tbody td:first-child,
        #tablaPlantasReservaMasiva tbody td:first-child {
            padding-left: 12px;
        }
        #tablaPlantasReserva .select-planta,
        #tablaPlantasReserva .input-cantidad,
        #tablaPlantasReservaMasiva .select-planta,
        #tablaPlantasReservaMasiva .input-cantidad {
            padding: 5px 8px;
            font-size: 12px;
            border: 1px solid #f0ad4e;
            border-radius: 6px;
            height: 32px;
            min-height: 32px;
            background-color: #fff;
        }
        #tablaPlantasReserva .select-planta:focus,
        #tablaPlantasReserva .input-cantidad:focus,
        #tablaPlantasReservaMasiva .select-planta:focus,
        #tablaPlantasReservaMasiva .input-cantidad:focus {
            border-color: #ec971f;
            outline: 0;
            box-shadow: 0 0 0 2px rgba(236, 151, 31, 0.4);
        }
        #tablaPlantasReserva .input-cantidad,
        #tablaPlantasReservaMasiva .input-cantidad {
            width: 70px;
            text-align: center;
        }
        #tablaPlantasReserva .btn-danger.btn-xs,
        #tablaPlantasReservaMasiva .btn-danger.btn-xs {
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 6px;
        }
        #modalReservaCupos .table-responsive,
        #modalReservaMasiva .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
    </style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Administración de Turnos y Cupos</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
            <!-- VISTA PRINCIPAL: Grid de configuraciones -->
            <div id="vistaGrid">
                
                
                <!-- Sección de Filtros Compacta -->
                <fieldset class="exa-fieldset" style="margin-bottom: 10px;">
                    <legend class="Titulos2">Filtros</legend>
                    <div class="form-group" style="margin-bottom: 5px; display: flex; align-items: center; flex-wrap: wrap;">
                        <label class="col-sm-1 control-label label-xs" style="flex: 0 0 auto;">Estado:</label>
                        <div class="col-sm-2" style="flex: 0 0 auto;">
                            <select id="filtro_estado" name="filtro_estado" class="form-control input-xs">
                                <option value="T">Todos</option>
                                <option value="A">ACTIVO</option>
                                <option value="F">FINALIZADO</option>
                                <option value="S">SUSPENDIDO</option>
                                <option value="I">INACTIVO</option>
                            </select>
                        </div>
                        <div class="col-sm-4" style="margin-left: 10px; flex: 0 0 auto;">
                            <div class="input-group input-group-xs">
                                <span class="input-group-addon bold alert-info">Fecha Desde:</span>
                                <input type="date" id="filtro_fecha_inicio_desde" name="filtro_fecha_inicio_desde" class="form-control input-xs databind" value="<?php echo date("Y-m-d", strtotime("monday this week")); ?>" style="text-align: center;" />
                                <span class="input-group-addon bold alert-info">Hasta:</span>
                                <input type="date" id="filtro_fecha_inicio_hasta" name="filtro_fecha_inicio_hasta" class="form-control input-xs databind" value="<?php echo date("Y-m-d"); ?>" style="text-align: center;" />
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-success btn-xs" onclick="aplicarFiltros();" title="Aplicar Filtros">
                                        <span class="glyphicon glyphicon-search"></span> Buscar
                                    </button>
                                    <button type="button" class="btn btn-default btn-xs" onclick="limpiarFiltros();" title="Limpiar Filtros">
                                        <span class="glyphicon glyphicon-refresh"></span>
                                    </button>
                                </span>
                            </div>
                        </div>
                        <div style="margin-left: auto; padding-left: 15px; flex: 0 0 auto;">
                            <button class="btn btn-success btn-sm" onclick="mostrarFormularioCrear();"><i class="glyphicon glyphicon-plus"></i> Crear Nueva Configuración</button>
                        </div>
                    </div>
                </fieldset>
                
                <div class="row">
                    <div class="col-xs-12">
                        <table id="turnosGrid"></table>
                        <div id="turnosGridPager"></div>
                    </div>
                </div>
                
                <div style="margin-top: 10px;">
                    <span class="glyphicon glyphicon-stop" style="color: #28a745;"></span> Configuración Activa
                    <span class="glyphicon glyphicon-stop" style="color: #6c757d; margin-left: 20px;"></span> Configuración Inactiva
                </div>
            </div>
            
            <!-- VISTA CREAR: Formulario para crear nueva configuración -->
            <div id="vistaCrear" style="display: none;">
                <div class="section-title">
                    <h5><i class="glyphicon glyphicon-plus"></i> Crear Nueva Configuración de Turnos</h5>
                    <button class="btn btn-default btn-sm" onclick="volverAlGrid();">
                        <i class="glyphicon glyphicon-arrow-left"></i> Volver al Listado
                    </button>
                </div>
                
                <!-- Panel de Configuración -->
                <div class="config-panel">
                    <h4><i class="glyphicon glyphicon-cog"></i> Configuración de Turnos</h4>
                    <div class="config-row">
                        <div class="config-item">
                            <label>Num. Cupos:</label>
                            <input type="number" id="cupos_default" value="25" min="1" max="100" style="width: 80px;">
                        </div>
                        <div class="config-item">
                            <label>Fecha inicio:</label>
                            <input type="date" id="fecha_inicio" value="<?php echo $hoy; ?>">
                        </div>
                        <div class="config-item">
                            <label>Fecha fin:</label>
                            <input type="date" id="fecha_fin" value="<?php echo $hoy; ?>">
                        </div>
                    </div>
                    <div class="config-row" style="margin-top: 15px;">
                        <div class="config-item">
                            <label><i class="glyphicon glyphicon-time"></i> Hora Inicio:</label>
                            <select id="hora_inicio_turno" style="padding: 8px 12px; border-radius: 5px; font-size: 14px;">
                                <?php for($h = 0; $h < 24; $h++): ?>
                                <option value="<?php echo $h; ?>" <?php echo ($h == 0) ? 'selected' : ''; ?>>
                                    <?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>:00
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="config-item">
                            <label><i class="glyphicon glyphicon-time"></i> Hora Fin:</label>
                            <select id="hora_fin_turno" style="padding: 8px 12px; border-radius: 5px; font-size: 14px;">
                                <?php for($h = 1; $h <= 24; $h++): ?>
                                <option value="<?php echo $h; ?>" <?php echo ($h == 24) ? 'selected' : ''; ?>>
                                    <?php echo str_pad($h % 24, 2, '0', STR_PAD_LEFT); ?>:00
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="config-item">
                            <label><i class="glyphicon glyphicon-th"></i> Celda:</label>
                            <select id="celda_turno" style="padding: 8px 12px; border-radius: 5px; font-size: 14px; min-width: 150px;">
                                <option value="">-- Seleccione Celda --</option>
                            </select>
                        </div>
                        <div class="config-item">
                            <span id="previewTurnos" style="background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; font-weight: bold;">
                                <i class="glyphicon glyphicon-info-sign"></i> Se generarán <span id="cantidadTurnos">24</span> turnos
                            </span>
                        </div>
                        <div class="config-item">
                            <span id="previewTurnosReservas" style="background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; font-weight: bold;">
                                <i class="glyphicon glyphicon-tags" style="color:#f0ad4e;"></i> Turnos con reserva: <span id="cantidadTurnosReserva">0</span>
                            </span>
                        </div>
                        <button class="btn-generar" onclick="previsualizarTurnos();">
                            <i class="glyphicon glyphicon-eye-open"></i> Previsualizar Turnos
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- VISTA PREVISUALIZAR: Previsualización antes de guardar -->
            <div id="vistaPrevisualizar" style="display: none;">
                <div class="section-title" style="background: linear-gradient(90deg, #f39c12, #e67e22);">
                    <h5><i class="glyphicon glyphicon-eye-open"></i> Previsualización de Turnos (Sin Guardar)</h5>
                    <div>
                        <button class="btn btn-default btn-sm" onclick="volverACrear();">
                            <i class="glyphicon glyphicon-arrow-left"></i> Modificar Configuración
                        </button>
                        <button class="btn btn-success btn-sm" onclick="guardarNuevaConfiguracion();" style="margin-left: 10px;">
                            <i class="glyphicon glyphicon-floppy-disk"></i> Guardar Configuración
                        </button>
                    </div>
                </div>
                
                <!-- Info de configuración -->
                <div class="alert alert-warning" style="margin-bottom: 15px;">
                    <i class="glyphicon glyphicon-info-sign"></i>
                    <strong>Modo Previsualización:</strong> Los cambios NO se han guardado aún. 
                    Modifique los cupos y estados según necesite, luego presione <strong>"Guardar Configuración"</strong>.
                    <br>
                    <span id="infoConfigPreview"></span>
                </div>
                
                <!-- Acciones Masivas -->
                <div class="acciones-masivas">
                    <button class="btn btn-success btn-sm" onclick="habilitarTodosPreview();">
                        <i class="glyphicon glyphicon-ok"></i> Habilitar Todos
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deshabilitarTodosPreview();">
                        <i class="glyphicon glyphicon-remove"></i> Deshabilitar Todos
                    </button>
                    <button class="btn btn-info btn-sm" onclick="setCuposMasivoPreview();">
                        <i class="glyphicon glyphicon-edit"></i> Cupos Masivo
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="abrirModalReservaMasiva(true);">
                        <i class="glyphicon glyphicon-tags"></i> Reserva Masiva
                    </button>
                    
                    <div class="resumen-turnos">
                        <div class="resumen-item activos">
                            <span class="numero" id="totalActivosPreview">0</span>
                            <span>Activos</span>
                        </div>
                        <div class="resumen-item inactivos">
                            <span class="numero" id="totalInactivosPreview">0</span>
                            <span>Suspendidos</span>
                        </div>
                        <div class="resumen-item total-cupos">
                            <span class="numero" id="totalCuposPreview">0</span>
                            <span>Cupos Totales</span>
                        </div>
                        <div class="resumen-item reservas">
                            <span class="numero" id="totalReservasPreview">0</span>
                            <span>Turnos con Reserva</span>
                        </div>
                    </div>
                </div>
                
                <!-- Contenedor de Turnos Preview -->
                <div id="turnosContainerPreview"></div>
            </div>
            
            <!-- VISTA DETALLE: Detalle de una configuración -->
            <div id="vistaDetalle" style="display: none;">
                <div class="section-title">
                    <h5><i class="glyphicon glyphicon-time"></i> Detalle de Configuración: <span id="configFechas"></span></h5>
                    <button class="btn btn-default btn-sm" onclick="volverAlGrid();">
                        <i class="glyphicon glyphicon-arrow-left"></i> Volver al Listado
                    </button>
                </div>
                
                <!-- Acciones Masivas -->
                <div class="acciones-masivas">
                    <button class="btn btn-success btn-sm" onclick="abrirModalAgregarTurno();">
                        <i class="glyphicon glyphicon-plus"></i> Agregar Turno
                    </button>
                    <button class="btn btn-success btn-sm" onclick="habilitarTodos();">
                        <i class="glyphicon glyphicon-ok"></i> Habilitar Todos
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deshabilitarTodos();">
                        <i class="glyphicon glyphicon-remove"></i> Deshabilitar Todos
                    </button>
                    <button class="btn btn-info btn-sm" onclick="setCuposMasivo();">
                        <i class="glyphicon glyphicon-edit"></i> Cupos Masivo
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="abrirModalReservaMasiva();">
                        <i class="glyphicon glyphicon-tags"></i> Reserva Masiva
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="guardarCambios();">
                        <i class="glyphicon glyphicon-floppy-disk"></i> Guardar Cambios
                    </button>
                    
                    <div class="resumen-turnos" id="resumenTurnos">
                        <div class="resumen-item activos">
                            <span class="numero" id="totalActivos">0</span>
                            <span>Activos</span>
                        </div>
                        <div class="resumen-item inactivos">
                            <span class="numero" id="totalInactivos">0</span>
                            <span>Suspendidos</span>
                        </div>
                        <div class="resumen-item total-cupos">
                            <span class="numero" id="totalCupos">0</span>
                            <span>Cupos Totales</span>
                        </div>
                        <div class="resumen-item reservas">
                            <span class="numero" id="totalReservas">0</span>
                            <span>Turnos con Reserva</span>
                        </div>
                    </div>
                </div>
                
                <!-- Contenedor de Turnos -->
                <div id="turnosContainer"></div>
            </div>
            
            <!-- Modal para Agregar Turno -->
            <div id="modalAgregarTurno" style="display: none;">
                <div class="modal-dialog" style="width: 500px; margin: 30px auto;">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(90deg, #28a745, #20c997); color: white;">
                            <h4 class="modal-title"><i class="glyphicon glyphicon-plus"></i> Agregar Nuevo Turno</h4>
                            <button type="button" class="close" onclick="cerrarModalAgregarTurno();" style="color: white; opacity: 0.8;">&times;</button>
                        </div>
                        <div class="modal-body" style="padding: 20px;">
                            <form id="formAgregarTurno">
                                <div class="form-group">
                                    <label><i class="glyphicon glyphicon-calendar"></i> Rango de Fechas:</label>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <div style="flex: 1;">
                                            <label style="font-size: 12px; font-weight: normal; margin-bottom: 5px;">Desde:</label>
                                            <input type="date" id="nuevo_turno_fecha_desde" class="form-control" required>
                                        </div>
                                        <div style="flex: 1;">
                                            <label style="font-size: 12px; font-weight: normal; margin-bottom: 5px;">Hasta:</label>
                                            <input type="date" id="nuevo_turno_fecha_hasta" class="form-control" required>
                                        </div>
                                    </div>
                                    <small style="color: #666; display: block; margin-top: 5px;">
                                        <i class="glyphicon glyphicon-info-sign"></i> Se generarán turnos para todas las fechas en este rango
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label><i class="glyphicon glyphicon-time"></i> Hora Inicio:</label>
                                    <select id="nuevo_turno_hora_inicio" class="form-control" required>
                                        <?php for($h = 0; $h < 24; $h++): ?>
                                        <option value="<?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>:00">
                                            <?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>:00
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><i class="glyphicon glyphicon-time"></i> Hora Fin:</label>
                                    <select id="nuevo_turno_hora_fin" class="form-control" required>
                                        <?php for($h = 1; $h <= 24; $h++): ?>
                                        <option value="<?php echo str_pad($h % 24, 2, '0', STR_PAD_LEFT); ?>:00">
                                            <?php echo str_pad($h % 24, 2, '0', STR_PAD_LEFT); ?>:00
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><i class="glyphicon glyphicon-user"></i> Cupos:</label>
                                    <input type="number" id="nuevo_turno_cupos" class="form-control" value="25" min="1" max="100" required>
                                </div>
                                <div class="form-group">
                                    <label><i class="glyphicon glyphicon-th"></i> Celda:</label>
                                    <select id="nuevo_turno_celda" class="form-control">
                                        <option value="">-- Sin Celda --</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><i class="glyphicon glyphicon-info-sign"></i> Estado:</label>
                                    <select id="nuevo_turno_estado" class="form-control">
                                        <option value="A">ACTIVO</option>
                                        <option value="S">SUSPENDIDO</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" onclick="cerrarModalAgregarTurno();">Cancelar</button>
                            <button type="button" class="btn btn-success" onclick="guardarNuevoTurno();">
                                <i class="glyphicon glyphicon-floppy-disk"></i> Guardar Turno
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <script>
        var turnosData = [];
        var turCodActual = null;
        var configActual = null;
        var celdasData = [];
        
        $(function() {
            crearGrid();
            cargarCeldas();
            // Ejecutar aplicarFiltros() automáticamente al cargar la página
            // Esperar un momento para que el grid se inicialice completamente
            setTimeout(function() {
                aplicarFiltros();
            }, 300);
        });
        
        // Cargar lista de celdas
        function cargarCeldas() {
            $.get('', { listarCeldasAjax: true }, function(r) {
                if (r.success && r.celdas) {
                    celdasData = r.celdas;
                    var select = $('#celda_turno');
                    select.empty();
                    select.append('<option value="">-- Seleccione Celda --</option>');
                    // Renderizar celdas con estructura jerárquica
                    r.celdas.forEach(function(celda) {
                        var texto = '';
                        var disabled = '';
                        if(celda.es_grupo === true || celda.Cel_Tip === 'G'){
                            // Es un grupo - mostrar en mayúsculas y deshabilitado (no seleccionable)
                            texto = celda.Cel_Nom.toUpperCase();
                            disabled = 'disabled style="font-weight: bold; background-color: #f0f0f0; color: #333;"';
                        } else {
                            // Es un detalle - mostrar con indentación
                            var prefijo = (celda.nivel === 1) ? '&nbsp;&nbsp;&nbsp;&nbsp;&raquo; ' : '';
                            texto = prefijo + (celda.Cel_Num || '') + ' - ' + celda.Cel_Nom;
                        }
                        select.append('<option value="' + celda.Cel_Cod + '" ' + disabled + '>' + texto + '</option>');
                    });
                }
            }, 'json');
        }
        
        // Aplicar filtros de búsqueda mediante AJAX
        function aplicarFiltros() {
            var filtros = {
                filtroTurnosAjax: true,
                filtro_fecha_inicio_desde: $('#filtro_fecha_inicio_desde').val(),
                filtro_fecha_inicio_hasta: $('#filtro_fecha_inicio_hasta').val(),
                filtro_estado: $('#filtro_estado').val()
            };
            
            // Mostrar indicador de carga
            $('#turnosGrid').jqGrid('setGridParam', { loadui: 'block' });
            
            $.post('', filtros, function(resp) {
                if (resp.success && resp.rows) {
                    // Cambiar a modo local y cargar los datos filtrados
                    $('#turnosGrid').jqGrid('setGridParam', {
                        datatype: 'local',
                        data: resp.rows
                    }).jqGrid('clearGridData').jqGrid('setGridParam', {
                        data: resp.rows
                    }).trigger('reloadGrid');
                } else {
                    $.alert('Error al aplicar los filtros: ' + (resp.message || 'Error desconocido'));
                }
            }, 'json').fail(function() {
                $.alert('Error de comunicación con el servidor al aplicar los filtros.');
            }).always(function() {
                $('#turnosGrid').jqGrid('setGridParam', { loadui: 'enable' });
            });
        }
        
        // Limpiar filtros de búsqueda
        function limpiarFiltros() {
            $('#filtro_fecha_inicio_desde').val('');
            $('#filtro_fecha_inicio_hasta').val('');
            $('#filtro_estado').val('');
            
            // Recargar el grid sin filtros (volver a modo JSON)
            $('#turnosGrid').jqGrid('setGridParam', {
                datatype: 'json',
                postData: { listTurnosCabAjax: true }
            }).trigger('reloadGrid');
        }
        
        // Crear el grid de configuraciones
        function crearGrid() {
            $('#turnosGrid').createGrid({
                caption: 'Listado de Configuraciones de Turnos',
                url: '',
                postData: function() {
                    // No enviar filtros en la carga inicial, se aplicarán mediante aplicarFiltros()
                    return {
                        listTurnosCabAjax: true
                    };
                },
                datatype: 'local', // Iniciar en modo local para evitar carga automática
                mtype: 'GET',
                height: 300,
                colModel: [
                    { label: 'ID', name: 'Tur_Cod', key: true, width: 50, align: 'center' },
                    { label: 'Fecha Inicio', name: 'Tur_Fei', width: 100, align: 'center' },
                    { label: 'Fecha Fin', name: 'Tur_Fef', width: 100, align: 'center' },
                    { label: 'Estado', name: 'estado_texto', width: 100, align: 'center',
                        formatter: function(val, opts, row) {
                            if (val === 'ACTIVO') {
                                return '<span class="badge-activo">ACTIVO</span>';
                            } else if (val === 'FINALIZADO') {
                                return '<span class="badge-inactivo">FINALIZADO</span>';
                            } else if (val === 'INACTIVO') {
                                return '<span class="badge-inactivo" style="background-color: #dc3545; color: white;">INACTIVO</span>';
                            } else {
                                return '<span class="badge-inactivo">SUSPENDIDO</span>';
                            }
                        }
                    },
                    { label: 'Turnos', name: 'total_turnos', width: 70, align: 'center' },
                    { label: 'Activos', name: 'turnos_activos', width: 70, align: 'center',
                        formatter: function(val) {
                            return '<span style="color: #28a745; font-weight: bold;">' + val + '</span>';
                        }
                    },
                    { label: 'Suspendidos', name: 'turnos_inactivos', width: 70, align: 'center',
                        formatter: function(val) {
                            return '<span style="color: #dc3545; font-weight: bold;">' + val + '</span>';
                        }
                    },
                    { label: 'Total Cupos', name: 'total_cupos', width: 90, align: 'center',
                        formatter: function(val) {
                            return '<span style="color: #007bff; font-weight: bold;">' + val + '</span>';
                        }
                    },
                    { label: 'Usuario Creador', name: 'usuario_creador', width: 150, align: 'left',
                        formatter: function(val) {
                            return '<span style="color: #555; font-weight: normal;">' + (val || 'N/A') + '</span>';
                        }
                    },
                    { label: 'Creado', name: 'Tur_Sys', width: 130, align: 'center' },
                    { label: 'Acciones', name: 'acciones', width: 180, align: 'center',
                        formatter: function(val, opts, row) {
                            let btns = '';
                            btns += '<button class="btn btn-xs btn-info" onclick="verDetalle(' + row.Tur_Cod + ');" title="Ver Detalle"><i class="glyphicon glyphicon-eye-open"></i></button> ';
                            if (row.Tur_Est !== 'A') {
                                btns += '<button class="btn btn-xs btn-success" onclick="activarConfig(' + row.Tur_Cod + ');" title="Activar"><i class="glyphicon glyphicon-ok"></i></button> ';
                            }
                            // Solo mostrar botón eliminar si no tiene turnos en uso
                            if (!row.tiene_turnos_en_uso) {
                                btns += '<button class="btn btn-xs btn-danger" onclick="eliminarConfig(' + row.Tur_Cod + ');" title="Eliminar"><i class="glyphicon glyphicon-trash"></i></button>';
                            }
                            return btns;
                        }
                    }
                ],
                rowNum: 10,
                rowList: [10, 20, 50],
                pager: '#turnosGridPager',
                viewrecords: true,
                gridview: true,
                jsonReader: {
                    root: 'rows',
                    page: function() { return 1; },
                    total: function() { return 1; },
                    records: function(obj) { return obj.rows ? obj.rows.length : 0; }
                },
                loadonce: false
            }, true, '#turnosGridPager', { refresh: true, add: false, edit: false, del: false, search: false });
            
            // Inicializar el grid con datos vacíos
            $('#turnosGrid').jqGrid('setGridParam', {
                datatype: 'local',
                data: []
            });
        }
        
        // Mostrar formulario de crear
        function mostrarFormularioCrear() {
            $('#vistaGrid').hide();
            $('#vistaDetalle').hide();
            $('#vistaCrear').show();
        }
        
        // Volver al grid
        function volverAlGrid() {
            $('#vistaCrear').hide();
            $('#vistaDetalle').hide();
            $('#vistaPrevisualizar').hide();
            $('#vistaGrid').show();
            $('#turnosGrid').trigger('reloadGrid');
        }
        
        // Ver detalle de una configuración
        function verDetalle(turCod) {
            turCodActual = turCod;
            // Asegurar que las celdas estén cargadas antes de renderizar
            if (celdasData.length === 0) {
                cargarCeldas();
            }
            cargarDetalleTurnos(turCod);
        }
        
        // Cargar detalle de turnos
        function cargarDetalleTurnos(turCod) {
            $.get('', { getTurnosPorDiaAjax: true, Tur_Cod: turCod }, function(r) {
                if (r.success && r.turnosPorDia) {
                    turnosData = r.turnosPorDia;
                    configActual = r.config || null;
                    $('#configFechas').text(r.config.Tur_Fei + ' al ' + r.config.Tur_Fef);
                    renderizarTurnosPorDia(r.turnosPorDia, r.config);
                    $('#vistaGrid').hide();
                    $('#vistaCrear').hide();
                    $('#vistaDetalle').show();
                } else {
                    $.alert('No se encontraron turnos para esta configuración');
                }
            }, 'json');
        }
        
        // Abrir modal para agregar turno
        function abrirModalAgregarTurno() {
            if (!turCodActual) {
                $.alert('Error: No hay configuración seleccionada');
                return;
            }
            
            // Limpiar formulario
            $('#formAgregarTurno')[0].reset();
            
            // Cargar celdas en el select del modal
            var selectCelda = $('#nuevo_turno_celda');
            selectCelda.empty();
            selectCelda.append('<option value="">-- Sin Celda --</option>');
            celdasData.forEach(function(celda) {
                var texto = '';
                var disabled = '';
                
                if(celda.es_grupo === true || celda.Cel_Tip === 'G'){
                    texto = celda.Cel_Nom.toUpperCase();
                    disabled = 'disabled style="font-weight: bold; background-color: #f0f0f0; color: #333;"';
                } else {
                    var prefijo = (celda.nivel === 1) ? '&nbsp;&nbsp;&nbsp;&nbsp;&raquo; ' : '';
                    texto = prefijo + (celda.Cel_Num || '') + ' - ' + celda.Cel_Nom;
                }
                selectCelda.append('<option value="' + celda.Cel_Cod + '" ' + disabled + '>' + texto + '</option>');
            });
            
            // Establecer fechas por defecto (hoy)
            $('#nuevo_turno_fecha_desde').val('<?php echo $hoy; ?>');
            $('#nuevo_turno_fecha_hasta').val('<?php echo $hoy; ?>');
            $('#nuevo_turno_cupos').val(25);
            $('#nuevo_turno_estado').val('A');
            
            // Mostrar modal
            $('#modalAgregarTurno').show();
            
            // Cerrar modal al hacer clic fuera
            $('#modalAgregarTurno').off('click').on('click', function(e) {
                if ($(e.target).is('#modalAgregarTurno')) {
                    cerrarModalAgregarTurno();
                }
            });
            
            // Prevenir que el modal se cierre al hacer clic dentro
            $('#modalAgregarTurno .modal-dialog').off('click').on('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // Cerrar modal de agregar turno
        function cerrarModalAgregarTurno() {
            $('#modalAgregarTurno').hide();
            $('#formAgregarTurno')[0].reset();
        }
        
        // Guardar nuevo turno
        function guardarNuevoTurno() {
            if (!turCodActual) {
                $.alert('Error: No hay configuración seleccionada');
                return;
            }
            
            var fechaDesde = $('#nuevo_turno_fecha_desde').val();
            var fechaHasta = $('#nuevo_turno_fecha_hasta').val();
            var horaInicio = $('#nuevo_turno_hora_inicio').val();
            var horaFin = $('#nuevo_turno_hora_fin').val();
            var cupos = parseInt($('#nuevo_turno_cupos').val());
            var celda = $('#nuevo_turno_celda').val() || null;
            var estado = $('#nuevo_turno_estado').val();
            
            // Validar campos
            if (!fechaDesde || !fechaHasta) {
                $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> Debe seleccionar fecha desde y fecha hasta');
                return;
            }
            
            if (fechaDesde > fechaHasta) {
                $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> La fecha desde debe ser menor o igual a la fecha hasta');
                return;
            }
            
            if (!horaInicio || !horaFin) {
                $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> Debe seleccionar hora de inicio y fin');
                return;
            }
            
            if (isNaN(cupos) || cupos < 1) {
                $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> Debe ingresar un número válido de cupos');
                return;
            }
            
            // Validar que hora inicio sea menor a hora fin
            var horaInicioInt = parseInt(horaInicio.split(':')[0]);
            var horaFinInt = parseInt(horaFin.split(':')[0]);
            if (horaFin === '00:00' && horaInicioInt > 0) {
                horaFinInt = 24;
            }
            
            if (horaInicioInt >= horaFinInt && horaFin !== '00:00') {
                $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> La hora de inicio debe ser menor a la hora de fin');
                return;
            }
            
            // Calcular cantidad de días para confirmación
            var fechaDesdeObj = new Date(fechaDesde + 'T00:00:00');
            var fechaHastaObj = new Date(fechaHasta + 'T00:00:00');
            var cantidadDias = Math.ceil((fechaHastaObj - fechaDesdeObj) / (1000 * 60 * 60 * 24)) + 1;
            
            // Preparar datos para enviar
            var datosTurno = {
                insertarTurnoDetAjax: true,
                Tur_Cod: turCodActual,
                fecha_turno_desde: fechaDesde,
                fecha_turno_hasta: fechaHasta,
                hora_inicio: horaInicio,
                hora_fin: horaFin,
                cupos_turno: cupos,
                celda_turno: celda,
                estado_turno: estado
            };
            
            // Crear mensaje de confirmación simplificado
            var mensajeConfirmacion = 
                '<div style="text-align: center; padding: 20px;">' +
                '<i class="glyphicon glyphicon-time" style="font-size: 48px; color: #28a745; margin-bottom: 15px;"></i>' +
                '<h4 style="margin: 10px 0; color: #2c3e50; font-weight: bold;">¿Confirmar Creación de Turnos?</h4>' +
                '<p style="margin: 15px 0; color: #555; font-size: 14px; line-height: 1.6;">' +
                'Se generarán <strong style="color: #28a745;">' + cantidadDias + '</strong> turno' + (cantidadDias > 1 ? 's' : '') + 
                ' desde <strong>' + fechaDesde + '</strong> hasta <strong>' + fechaHasta + '</strong><br>' +
                'con horario <strong>' + horaInicio + ' - ' + horaFin + '</strong> y <strong>' + cupos + '</strong> cupos por turno.' +
                '</p>' +
                '</div>';
            
            // Mostrar confirmación personalizada
            $.createDialogConfirm(mensajeConfirmacion, datosTurno, function(data) {
                // Asegurar que el diálogo de confirmación aparezca sobre el modal
                setTimeout(function() {
                    $('.ui-dialog.dialog-confirm-test').css('z-index', '20000');
                    $('.ui-widget-overlay').css('z-index', '19999');
                }, 100);
                
                // Enviar datos
                $.post('', data, function(r) {
                    if (r.success) {
                        alert(r.message);
                        cerrarModalAgregarTurno();
                        // Recargar los turnos
                        cargarDetalleTurnos(turCodActual);
                    } else {
                        alert('Error: ' + (r.message || 'Error desconocido'));
                    }
                }, 'json');
            });
            
            // Asegurar z-index alto cuando se abre el diálogo
            setTimeout(function() {
                $('.ui-dialog.dialog-confirm-test').css('z-index', '20000');
                $('.ui-widget-overlay').last().css('z-index', '19999');
            }, 200);
        }
        
        // Activar una configuración
        function activarConfig(turCod) {
            if (confirm('¿Está seguro de activar esta configuración?\nLa configuración actual será desactivada.')) {
                $.post('', { activarTurnosCabAjax: true, Tur_Cod: turCod }, function(r) {
                    if (r.success) {
                        $.alert('<i class="glyphicon glyphicon-ok-circle" style="color: #28a745;"></i> ' + r.message);
                        $('#turnosGrid').trigger('reloadGrid');
                    } else {
                        $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> Error: ' + r.message);
                    }
                }, 'json');
            }
        }
        
        // Eliminar una configuración
        function eliminarConfig(turCod) {
            if (confirm('¿Está seguro de eliminar esta configuración?')) {
                $.post('', { eliminarTurnosCabAjax: true, Tur_Cod: turCod }, function(r) {
                    if (r.success) {
                        $.alert('<i class="glyphicon glyphicon-ok-circle" style="color: #28a745;"></i> ' + r.message);
                        $('#turnosGrid').trigger('reloadGrid');
                    } else {
                        $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> Error: ' + r.message);
                    }
                }, 'json');
            }
        }
        
        // Renderizar los turnos agrupados por día
        function renderizarTurnosPorDia(turnosPorDia, config) {
            let html = '';
            let activos = 0;
            let inactivos = 0;
            let totalCupos = 0;
            
            // Días de la semana en español
            let diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            let meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            
            // Ordenar fechas
            let fechas = Object.keys(turnosPorDia).sort();
            
            fechas.forEach(function(fecha) {
                let fechaObj = new Date(fecha + 'T00:00:00');
                let diaSemana = diasSemana[fechaObj.getDay()];
                let dia = fechaObj.getDate();
                let mes = meses[fechaObj.getMonth()];
                let anio = fechaObj.getFullYear();
                
                // Calcular total de cupos del día
                let totalCuposDia = 0;
                turnosPorDia[fecha].forEach(function(turno) {
                    if (turno.Tud_Est === 'A') {
                        totalCuposDia += parseInt(turno.Tud_Cup) || 0;
                    }
                });
                
                html += '<div class="dia-grupo" style="margin-bottom: 20px;" data-fecha-dia="' + fecha + '">';
                html += '  <div style="background: linear-gradient(90deg, #3f60ad, #5b799f); color: white; padding: 8px 12px; border-radius: 5px; margin-bottom: 10px; font-size: 13px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">';
                html += '    <span><i class="glyphicon glyphicon-calendar"></i> ' + diaSemana + ', ' + dia + ' de ' + mes + ' (' + fecha + ') - ' + turnosPorDia[fecha].length + ' turnos</span>';
                html += '    <span class="total-cupos-dia" style="background: rgba(255,255,255,0.3); padding: 4px 10px; border-radius: 4px; font-weight: bold;"><i class="glyphicon glyphicon-user"></i> Total Cupos: ' + totalCuposDia + '</span>';
                html += '  </div>';
                html += '  <div class="turnos-container">';
                
                turnosPorDia[fecha].forEach(function(turno) {
                    let esActivo = turno.Tud_Est === 'A';
                    let claseEstado = esActivo ? 'activo' : 'inactivo';
                    let textoEstado = esActivo ? 'ACTIVO' : 'SUSPENDIDO';
                    
                    // Verificar si es turno pasado
                    let esPasado = false;
                    let ahora = new Date();
                    // fecha es YYYY-MM-DD. turno.Tud_Hfi es HH:MM:SS
                    let fechaFinStr = fecha + 'T' + turno.Tud_Hfi;
                    let dFin = new Date(fechaFinStr);
                    
                    if (dFin < ahora) {
                        esPasado = true;
                        textoEstado = 'FINALIZADO';
                    }
                    
                    let disabledAttr = esPasado ? 'disabled' : '';
                    let styleBloqueado = esPasado ? 'opacity: 0.7; background-color: #f5f5f5; border-color: #ddd;' : '';
                    
                    if (esActivo) {
                        activos++;
                        totalCupos += parseInt(turno.Tud_Cup) || 0;
                    } else {
                        inactivos++;
                    }
                    
                    // Generar opciones del select de celdas
                    var selectCeldas = '<select class="celda-select" data-id="' + turno.Tud_Cod + '" onchange="actualizarCeldaTurno(' + turno.Tud_Cod + ', this.value);" style="padding: 4px 8px; border-radius: 4px; font-size: 12px; width: 100%; margin-top: 5px;" ' + disabledAttr + '>';
                    selectCeldas += '<option value="">-- Sin Celda --</option>';
                    // Renderizar celdas con estructura jerárquica
                    celdasData.forEach(function(celda) {
                        var texto = '';
                        var disabled = '';
                        var selected = (turno.Cel_Cod == celda.Cel_Cod) ? 'selected' : '';
                        
                        if(celda.es_grupo === true || celda.Cel_Tip === 'G'){
                            // Es un grupo - mostrar en mayúsculas y deshabilitado (no seleccionable)
                            texto = celda.Cel_Nom.toUpperCase();
                            disabled = 'disabled style="font-weight: bold; background-color: #f0f0f0; color: #333;"';
                            selected = ''; // Los grupos no pueden estar seleccionados
                        } else {
                            // Es un detalle - mostrar con indentación
                            var prefijo = (celda.nivel === 1) ? '&nbsp;&nbsp;&nbsp;&nbsp;- ' : '';
                            texto = prefijo + (celda.Cel_Num || '') + ' - ' + celda.Cel_Nom;
                        }
                        selectCeldas += '<option value="' + celda.Cel_Cod + '" ' + selected + ' ' + disabled + '>' + texto + '</option>';
                    });
                    selectCeldas += '</select>';
                    
                    html += '<div class="turno-card ' + claseEstado + '" data-id="' + turno.Tud_Cod + '" style="' + styleBloqueado + '">';
                    html += '  <div class="turno-header">';
                    html += '    <span class="turno-hora"><i class="glyphicon glyphicon-time"></i> ' + turno.Tud_Hin + ' - ' + turno.Tud_Hfi + '</span>';
                    html += '    <button class="turno-toggle ' + claseEstado + '" onclick="toggleTurno(' + turno.Tud_Cod + ', this);" ' + disabledAttr + '>' + textoEstado + '</button>';
                    html += '  </div>';
                    
                    var reservedTotalDet = 0;
                    if (turno.reservas && turno.reservas.length > 0) {
                        turno.reservas.forEach(function(r){ reservedTotalDet += parseInt(r.Tre_Can) || 0; });
                    }
                    
                    html += '  <div class="turno-cupos">';
                    html += '    <label>Cupos:</label>';
                    html += '    <input type="number" class="cupos-input" data-id="' + turno.Tud_Cod + '" data-fecha="' + fecha + '" value="' + turno.Tud_Cup + '" min="0" max="100" onchange="marcarModificado(this);" ' + disabledAttr + '>';
                    html += '    <span class="reserved-count" title="Cupos Reservados" style="display:inline-block;margin-left:6px;padding:2px 6px;border:1px solid #e67e22;border-radius:4px;background:#fff3e0;color:#a85b00;font-weight:bold;font-size:11px;">' + reservedTotalDet + '</span>';
                    html += '  </div>';
                    
                    html += '  <div style="margin-top: 5px;">';
                    if (esPasado) {
                        html += '    <button class="btn btn-default btn-xs btn-block" disabled><i class="glyphicon glyphicon-lock"></i> Bloqueado</button>';
                    } else {
                        html += '    <button class="btn ' + ((turno.reservas && turno.reservas.length > 0) ? 'btn-warning' : 'btn-info') + ' btn-xs btn-block" onclick="abrirModalReserva(\'' + fecha + '\', \'' + turno.Tud_Cod + '\', \'' + turno.Tud_Hin + '\', \'' + turno.Tud_Hfi + '\')"><i class="glyphicon glyphicon-bookmark"></i> Reservar</button>';
                    }
                    html += '  </div>';
                    
                    
                    html += '  <div style="margin-top: 8px;">';
                    html += '    <label style="font-size: 11px; color: #666; display: block; margin-bottom: 3px;"><i class="glyphicon glyphicon-th"></i> Celda:</label>';
                    html += selectCeldas;
                    html += '  </div>';
                    html += '</div>';
                });
                
                html += '  </div>';
                html += '</div>';
            });
            
            $('#turnosContainer').html(html);
            
            // Actualizar resumen
            $('#totalActivos').text(activos);
            $('#totalInactivos').text(inactivos);
            $('#totalCupos').text(totalCupos);
        }
        
        // Renderizar los turnos en cards (función legacy - mantener por compatibilidad)
        function renderizarTurnos(turnos, config) {
            // Agrupar por fecha si tienen el campo Tud_Fec
            if (turnos.length > 0 && turnos[0].Tud_Fec) {
                let turnosPorDia = {};
                turnos.forEach(function(turno) {
                    let fecha = turno.Tud_Fec;
                    if (!turnosPorDia[fecha]) {
                        turnosPorDia[fecha] = [];
                    }
                    turnosPorDia[fecha].push(turno);
                });
                renderizarTurnosPorDia(turnosPorDia, config);
            } else {
                // Renderizado simple sin agrupar (compatibilidad con datos antiguos)
                let html = '';
                let activos = 0;
                let inactivos = 0;
                let totalCupos = 0;
                
                turnos.forEach(function(turno) {
                    let esActivo = turno.Tud_Est === 'A';
                    let claseEstado = esActivo ? 'activo' : 'inactivo';
                    let textoEstado = esActivo ? 'ACTIVO' : 'SUSPENDIDO';
                    
                    if (esActivo) {
                        activos++;
                        totalCupos += parseInt(turno.Tud_Cup) || 0;
                    } else {
                        inactivos++;
                    }
                    
                    html += '<div class="turno-card ' + claseEstado + '" data-id="' + turno.Tud_Cod + '">';
                    html += '  <div class="turno-header">';
                    html += '    <span class="turno-hora"><i class="glyphicon glyphicon-time"></i> ' + turno.Tud_Hin + ' - ' + turno.Tud_Hfi + '</span>';
                    html += '    <button class="turno-toggle ' + claseEstado + '" onclick="toggleTurno(' + turno.Tud_Cod + ', this);">' + textoEstado + '</button>';
                    html += '  </div>';
                    html += '  <div class="turno-cupos">';
                    html += '    <label>Cupos:</label>';
                    html += '    <input type="number" class="cupos-input" data-id="' + turno.Tud_Cod + '" value="' + turno.Tud_Cup + '" min="1" max="100" onchange="marcarModificado(this);">';
                    html += '  </div>';
                    html += '</div>';
                });
                
                $('#turnosContainer').html('<div class="turnos-container">' + html + '</div>');
                
                // Actualizar resumen
                $('#totalActivos').text(activos);
                $('#totalInactivos').text(inactivos);
                $('#totalCupos').text(totalCupos);
            }
        }
        
        // Variables para previsualización
        var turnosPreview = [];
        var configPreview = {};
        
        // Actualizar vista previa de cantidad de turnos
        function actualizarPreviewTurnos() {
            let horaInicio = parseInt($('#hora_inicio_turno').val());
            let horaFin = parseInt($('#hora_fin_turno').val());
            let cantidad = horaFin - horaInicio;
            
            if (cantidad > 0) {
                $('#cantidadTurnos').text(cantidad);
                $('#previewTurnos').css('background', 'rgba(255,255,255,0.2)');
            } else {
                $('#cantidadTurnos').text('0 (error)');
                $('#previewTurnos').css('background', 'rgba(255,0,0,0.3)');
            }
        }
        
        // Eventos para actualizar preview
        $(document).on('change', '#hora_inicio_turno, #hora_fin_turno', function() {
            actualizarPreviewTurnos();
        });
        
        // Validar fechas antes de previsualizar o guardar
        function validarFechasTurnos(fechaInicio, fechaFin, callback) {
            $.get('', { 
                validarFechasTurnosAjax: true, 
                fecha_inicio: fechaInicio, 
                fecha_fin: fechaFin 
            }, function(r) {
                if (r.success && r.haySolapamiento) {
                    $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> ' + r.message);
                    return;
                } else if (r.success) {
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                    return;
                } else {
                    $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> ' + (r.message || 'Error al validar fechas'));
                    return;
                }
            }, 'json');
        }
        
        // Previsualizar turnos (sin guardar)
        function previsualizarTurnos() {
            let cupos = parseInt($('#cupos_default').val());
            let fechaInicio = $('#fecha_inicio').val();
            let fechaFin = $('#fecha_fin').val();
            let horaInicioTurno = parseInt($('#hora_inicio_turno').val());
            let horaFinTurno = parseInt($('#hora_fin_turno').val());
            
            // Validar que las fechas estén seleccionadas
            if (!fechaInicio || !fechaFin) {
                $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> Debe seleccionar fecha de inicio y fecha de fin');
                return;
            }
            
            // Validar rango de horas
            if (horaInicioTurno >= horaFinTurno) {
                $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> La hora de inicio debe ser menor a la hora de fin');
                return;
            }
            
            // Validar fechas contra turnos existentes
            validarFechasTurnos(fechaInicio, fechaFin, function() {
                continuarPrevisualizacion(cupos, fechaInicio, fechaFin, horaInicioTurno, horaFinTurno);
            });
        }
        
        // Continuar con la previsualización después de validar
        function continuarPrevisualizacion(cupos, fechaInicio, fechaFin, horaInicioTurno, horaFinTurno) {
            
            // Obtener celda seleccionada
            var celCod = $('#celda_turno').val() || null;
            
            // Guardar configuración para el guardado posterior
            configPreview = {
                cupos_default: cupos,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin,
                hora_inicio_turno: horaInicioTurno,
                hora_fin_turno: horaFinTurno,
                cel_cod: celCod
            };
            
            // Calcular días en el rango
            let fechaInicioObj = new Date(fechaInicio + 'T00:00:00');
            let fechaFinObj = new Date(fechaFin + 'T00:00:00');
            let cantidadDias = Math.ceil((fechaFinObj - fechaInicioObj) / (1000 * 60 * 60 * 24)) + 1;
            
            // Generar turnos en memoria (JavaScript) para cada día
            turnosPreview = [];
            let fechaActual = new Date(fechaInicioObj);
            
            while (fechaActual <= fechaFinObj) {
                let fechaActualStr = fechaActual.toISOString().split('T')[0];
                
                for (let i = horaInicioTurno; i < horaFinTurno; i++) {
                    let horaIni = i.toString().padStart(2, '0') + ':00';
                    let horaFin = ((i + 1) % 24).toString().padStart(2, '0') + ':00';
                    
                    turnosPreview.push({
                        id: 'new_' + fechaActualStr + '_' + i,
                        fecha: fechaActualStr,
                        hora_inicio: horaIni,
                        hora_fin: horaFin,
                        cupos: cupos,
                        estado: 'A',
                        cel_cod: celCod
                    });
                }
                
                fechaActual.setDate(fechaActual.getDate() + 1);
            }
            
            // Mostrar información de configuración
            let horaIniTexto = horaInicioTurno.toString().padStart(2, '0') + ':00';
            let horaFinTexto = (horaFinTurno % 24).toString().padStart(2, '0') + ':00';
            let cantidadHoras = horaFinTurno - horaInicioTurno;
            $('#infoConfigPreview').html(
                '<strong>Fecha:</strong> ' + fechaInicio + ' al ' + fechaFin + ' (' + cantidadDias + ' días) | ' +
                '<strong>Horario:</strong> ' + horaIniTexto + ' - ' + horaFinTexto + ' (' + cantidadHoras + ' horas/día) | ' +
                '<strong>Total Turnos:</strong> ' + turnosPreview.length + ' (' + cantidadDias + ' días × ' + cantidadHoras + ' turnos)'
            );
            
            // Renderizar turnos en la vista de previsualización
            renderizarTurnosPreview();
            
            // Mostrar vista de previsualización
            $('#vistaCrear').hide();
            $('#vistaPrevisualizar').show();
        }
        
        // Renderizar turnos en previsualización agrupados por día
        function renderizarTurnosPreview() {
            let html = '';
            let activos = 0;
            let inactivos = 0;
            let totalCupos = 0;
            let conReserva = 0;
            let totalReservas = 0;
            
            // Agrupar turnos por fecha
            let turnosPorDia = {};
            turnosPreview.forEach(function(turno, index) {
                let fecha = turno.fecha || 'sin-fecha';
                if (!turnosPorDia[fecha]) {
                    turnosPorDia[fecha] = [];
                }
                turnosPorDia[fecha].push({...turno, index: index});
            });
            
            // Días de la semana en español
            let diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            let meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            
            // Ordenar fechas
            let fechas = Object.keys(turnosPorDia).sort();
            
            fechas.forEach(function(fecha) {
                let fechaObj = new Date(fecha + 'T00:00:00');
                let diaSemana = diasSemana[fechaObj.getDay()];
                let dia = fechaObj.getDate();
                let mes = meses[fechaObj.getMonth()];
                let anio = fechaObj.getFullYear();
                
                // Calcular total de cupos del día
                let totalCuposDia = 0;
                turnosPorDia[fecha].forEach(function(turno) {
                    if (turno.estado === 'A') {
                        totalCuposDia += parseInt(turno.cupos) || 0;
                    }
                });
                
                html += '<div class="dia-grupo" style="margin-bottom: 20px;" data-fecha-dia="' + fecha + '">';
                html += '  <div style="background: linear-gradient(90deg, #3f60ad, #5b799f); color: white; padding: 8px 12px; border-radius: 5px; margin-bottom: 10px; font-size: 13px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">';
                html += '    <span><i class="glyphicon glyphicon-calendar"></i> ' + diaSemana + ', ' + dia + ' de ' + mes + ' (' + fecha + ') - ' + turnosPorDia[fecha].length + ' turnos</span>';
                html += '    <span class="total-cupos-dia" style="background: rgba(255,255,255,0.3); padding: 4px 10px; border-radius: 4px; font-weight: bold;"><i class="glyphicon glyphicon-user"></i> Total Cupos: ' + totalCuposDia + '</span>';
                html += '  </div>';
                html += '  <div class="turnos-container">';
                
                turnosPorDia[fecha].forEach(function(turno) {
                    let esActivo = turno.estado === 'A';
                    let claseEstado = esActivo ? 'activo' : 'inactivo';
                    let textoEstado = esActivo ? 'ACTIVO' : 'SUSPENDIDO';
                    
                    if (esActivo) {
                        activos++;
                        totalCupos += parseInt(turno.cupos) || 0;
                    } else {
                        inactivos++;
                    }
                    if (turno.reservas && turno.reservas.length > 0) {
                        conReserva++;
                    }
                    
                    // Generar opciones del select de celdas
                    var selectCeldas = '<select class="celda-select-preview" data-index="' + turno.index + '" onchange="actualizarCeldaPreview(' + turno.index + ', this.value);" style="padding: 4px 8px; border-radius: 4px; font-size: 12px; width: 100%; margin-top: 5px;">';
                    selectCeldas += '<option value="">-- Sin Celda --</option>';
                    // Renderizar celdas con estructura jerárquica
                    celdasData.forEach(function(celda) {
                        var texto = '';
                        var disabled = '';
                        var selected = (turno.cel_cod == celda.Cel_Cod) ? 'selected' : '';
                        
                        if(celda.es_grupo === true || celda.Cel_Tip === 'G'){
                            // Es un grupo - mostrar en mayúsculas y deshabilitado (no seleccionable)
                            texto = celda.Cel_Nom.toUpperCase();
                            disabled = 'disabled style="font-weight: bold; background-color: #f0f0f0; color: #333;"';
                            selected = ''; // Los grupos no pueden estar seleccionados
                        } else {
                            // Es un detalle - mostrar con indentación
                            var prefijo = (celda.nivel === 1) ? '&nbsp;&nbsp;&nbsp;&nbsp;- ' : '';
                            texto = prefijo + (celda.Cel_Num || '') + ' - ' + celda.Cel_Nom;
                        }
                        selectCeldas += '<option value="' + celda.Cel_Cod + '" ' + selected + ' ' + disabled + '>' + texto + '</option>';
                    });
                    selectCeldas += '</select>';
                    
                    html += '<div class="turno-card ' + claseEstado + '" data-index="' + turno.index + '">';
                    html += '  <div class="turno-header">';
                    html += '    <span class="turno-hora"><i class="glyphicon glyphicon-time"></i> ' + turno.hora_inicio + ' - ' + turno.hora_fin + '</span>';
                    html += '    <button class="turno-toggle ' + claseEstado + '" onclick="toggleTurnoPreview(' + turno.index + ', this);">' + textoEstado + '</button>';
                    html += '  </div>';
                    
                    let reservedTotal = 0;
                    if (turno.reservas && turno.reservas.length > 0) {
                        turno.reservas.forEach(function(r){ reservedTotal += parseInt(r.Tre_Can || r.cantidad) || 0; });
                    }
                    totalReservas += reservedTotal;
                    
                    html += '  <div class="turno-cupos">';
                    html += '    <label>Cupos:</label>';
                    html += '    <input type="number" class="cupos-input-preview" data-index="' + turno.index + '" data-fecha="' + fecha + '" value="' + turno.cupos + '" min="0" max="100" onchange="actualizarCupoPreview(' + turno.index + ', this.value);" oninput="actualizarCupoPreview(' + turno.index + ', this.value);">';
                    html += '    <span class="reserved-count" style="display:inline-block;margin-left:6px;padding:2px 6px;border:1px solid #e67e22;border-radius:4px;background:#fff3e0;color:#a85b00;font-weight:bold;font-size:11px;">' + reservedTotal + '</span>';
                    html += '  </div>';
                    html += '  <div style="margin-top: 5px;">';
                    html += '    <button class="btn ' + ((turno.reservas && turno.reservas.length > 0) ? 'btn-warning' : 'btn-info') + ' btn-xs btn-block" onclick="abrirModalReserva(\'' + fecha + '\', \'' + turno.id + '\', \'' + turno.hora_inicio + '\', \'' + turno.hora_fin + '\')"><i class="glyphicon glyphicon-bookmark"></i> Reservar</button>';
                    html += '  </div>';
                    html += '  <div style="margin-top: 8px;">';
                    html += '    <label style="font-size: 11px; color: #666; display: block; margin-bottom: 3px;"><i class="glyphicon glyphicon-th"></i> Celda:</label>';
                    html += selectCeldas;
                    html += '  </div>';
                    html += '</div>';
                });
                
                html += '  </div>';
                html += '</div>';
            });
            
            $('#turnosContainerPreview').html(html);
            
            // Actualizar resumen
            $('#totalActivosPreview').text(activos);
            $('#totalInactivosPreview').text(inactivos);
            $('#totalCuposPreview').text(totalCupos);
            
            $('#cantidadTurnosReserva').text(conReserva);
            $('#totalReservasPreview').text(totalReservas);
        }
        
        // Función reutilizable para actualizar el total de cupos de un día específico
        function actualizarTotalCuposDia(fecha) {
            if (!fecha) return;
            
            let totalCuposDia = 0;
            turnosPreview.forEach(function(turno) {
                if (turno.fecha === fecha && turno.estado === 'A') {
                    totalCuposDia += parseInt(turno.cupos) || 0;
                }
            });
            
            // Buscar el encabezado del día y actualizar el total usando el atributo data-fecha-dia
            let $diaGrupo = $('.dia-grupo[data-fecha-dia="' + fecha + '"]');
            if ($diaGrupo.length > 0) {
                let $totalElement = $diaGrupo.find('.total-cupos-dia');
                $totalElement.html('<i class="glyphicon glyphicon-user"></i> Total Cupos: ' + totalCuposDia);
                
                // Efecto visual de actualización (destello)
                $totalElement.css('transition', 'background-color 0.3s');
                $totalElement.css('background-color', 'rgba(46, 204, 113, 0.5)');
                setTimeout(function() {
                    $totalElement.css('background-color', 'rgba(255,255,255,0.3)');
                }, 300);
            }
        }
        
        // Función para actualizar todos los totales de días (útil después de cambios masivos)
        function actualizarTodosTotalCuposDia() {
            // Obtener todas las fechas únicas
            let fechas = [];
            turnosPreview.forEach(function(turno) {
                if (turno.fecha && fechas.indexOf(turno.fecha) === -1) {
                    fechas.push(turno.fecha);
                }
            });
            
            // Actualizar cada día
            fechas.forEach(function(fecha) {
                actualizarTotalCuposDia(fecha);
            });
        }
        
        // Toggle estado en previsualización
        function toggleTurnoPreview(index, btn) {
            let turno = turnosPreview[index];
            turno.estado = (turno.estado === 'A') ? 'S' : 'A';
            
            let $card = $(btn).closest('.turno-card');
            let esActivo = turno.estado === 'A';
            
            $card.removeClass('activo inactivo').addClass(esActivo ? 'activo' : 'inactivo');
            $(btn).removeClass('activo inactivo').addClass(esActivo ? 'activo' : 'inactivo');
            $(btn).text(esActivo ? 'ACTIVO' : 'SUSPENDIDO');
            
            actualizarResumenPreview();
            
            // Actualizar el total de cupos del día específico
            actualizarTotalCuposDia(turno.fecha);
        }
        
        // Actualizar cupo en previsualización
        function actualizarCupoPreview(index, valor) {
            if (index === undefined || turnosPreview[index] === undefined) return;
            
            let nuevoValor = parseInt(valor) || 0;
            if (nuevoValor < 0) nuevoValor = 0;
            if (nuevoValor > 100) nuevoValor = 100;
            
            let turno = turnosPreview[index];
            let fechaAntes = turno.fecha;
            turno.cupos = nuevoValor;
            
            // Actualizar también el valor en el input si se cambió por validación
            let $input = $('.cupos-input-preview[data-index="' + index + '"]');
            if ($input.length > 0 && parseInt($input.val()) !== nuevoValor) {
                $input.val(nuevoValor);
            }
            
            actualizarResumenPreview();
            
            // Actualizar el total de cupos del día específico en el encabezado
            actualizarTotalCuposDia(fechaAntes);
        }
        
        // Actualizar celda en previsualización
        function actualizarCeldaPreview(index, celCod) {
            if (index === undefined || turnosPreview[index] === undefined) return;
            
            let turno = turnosPreview[index];
            turno.cel_cod = celCod || null;
        }
        
        // Actualizar resumen de previsualización
        function actualizarResumenPreview() {
            let activos = 0;
            let inactivos = 0;
            let totalCupos = 0;
            let totalReservas = 0;
            
            turnosPreview.forEach(function(turno) {
                if (turno.estado === 'A') {
                    activos++;
                    totalCupos += parseInt(turno.cupos) || 0;
                } else {
                    inactivos++;
                }
                
                if (turno.reservas && turno.reservas.length > 0) {
                    turno.reservas.forEach(function(r){ totalReservas += parseInt(r.Tre_Can) || 0; });
                }
            });
            
            $('#totalActivosPreview').text(activos);
            $('#totalInactivosPreview').text(inactivos);
            $('#totalCuposPreview').text(totalCupos);
            $('#totalReservasPreview').text(totalReservas);
        }
        
        // Habilitar todos en previsualización
        function habilitarTodosPreview() {
            turnosPreview.forEach(function(turno) {
                turno.estado = 'A';
            });
            renderizarTurnosPreview();
        }
        
        // Deshabilitar todos en previsualización
        function deshabilitarTodosPreview() {
            turnosPreview.forEach(function(turno) {
                turno.estado = 'S';
            });
            renderizarTurnosPreview();
        }
        
        // Cupos masivo en previsualización
        function setCuposMasivoPreview() {
            let nuevoCupo = prompt('Ingrese el nuevo valor de cupos para todos los turnos:', '25');
            if (nuevoCupo && !isNaN(nuevoCupo)) {
                turnosPreview.forEach(function(turno) {
                    turno.cupos = parseInt(nuevoCupo) || 0;
                });
                renderizarTurnosPreview();
            }
        }
        
        // Volver a la vista de crear
        function volverACrear() {
            $('#vistaPrevisualizar').hide();
            $('#vistaCrear').show();
        }
        
        // Guardar la nueva configuración en base de datos
        function guardarNuevaConfiguracion() {
            // Si no hay configPreview, construir desde los campos del formulario
            if (!configPreview || !configPreview.fecha_inicio || !configPreview.fecha_fin) {
                let cupos = parseInt($('#cupos_default').val());
                let fechaInicio = $('#fecha_inicio').val();
                let fechaFin = $('#fecha_fin').val();
                let horaInicioTurno = parseInt($('#hora_inicio_turno').val());
                let horaFinTurno = parseInt($('#hora_fin_turno').val());
                var celCod = $('#celda_turno').val() || null;
                
                // Validar que las fechas estén seleccionadas
                if (!fechaInicio || !fechaFin) {
                    $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> Debe seleccionar fecha de inicio y fecha de fin');
                    return;
                }
                
                // Validar rango de horas
                if (horaInicioTurno >= horaFinTurno) {
                    $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> La hora de inicio debe ser menor a la hora de fin');
                    return;
                }
                
                // Construir configPreview desde los campos
                configPreview = {
                    cupos_default: cupos,
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin,
                    hora_inicio_turno: horaInicioTurno,
                    hora_fin_turno: horaFinTurno,
                    cel_cod: celCod
                };
                
                // Generar turnosPreview básico (sin personalizaciones)
                turnosPreview = [];
                let fechaInicioObj = new Date(fechaInicio + 'T00:00:00');
                let fechaFinObj = new Date(fechaFin + 'T00:00:00');
                let fechaActual = new Date(fechaInicioObj);
                
                while (fechaActual <= fechaFinObj) {
                    let fechaActualStr = fechaActual.toISOString().split('T')[0];
                    
                    for (let i = horaInicioTurno; i < horaFinTurno; i++) {
                        let horaIni = i.toString().padStart(2, '0') + ':00';
                        let horaFin = ((i + 1) % 24).toString().padStart(2, '0') + ':00';
                        
                        turnosPreview.push({
                            id: 'new_' + fechaActualStr + '_' + i,
                            fecha: fechaActualStr,
                            hora_inicio: horaIni,
                            hora_fin: horaFin,
                            cupos: cupos,
                            estado: 'A',
                            cel_cod: celCod
                        });
                    }
                    
                    fechaActual.setDate(fechaActual.getDate() + 1);
                }
            }
            
            // Validar fechas nuevamente antes de guardar
            validarFechasTurnos(configPreview.fecha_inicio, configPreview.fecha_fin, function() {
                $.createDialogConfirm(
                    '<div style="text-align: center; padding: 15px;">' +
                    '<i class="glyphicon glyphicon-exclamation-sign" style="font-size: 48px; color: #f39c12; margin-bottom: 15px;"></i>' +
                    '<h4 style="margin: 10px 0; color: #2c3e50; font-weight: bold;">¿Confirmar Guardado?</h4>' +
                    '<p style="margin: 15px 0; color: #555; font-size: 14px; line-height: 1.6;">' +
                    '¿Está seguro de guardar esta configuración de turnos?<br>' +
                    '<strong style="color: #e74c3c;">La configuración anterior será desactivada.</strong>' +
                    '</p>' +
                    '</div>',
                    {
                        cupos_default: configPreview.cupos_default,
                        fecha_inicio: configPreview.fecha_inicio,
                        fecha_fin: configPreview.fecha_fin,
                        hora_inicio_turno: configPreview.hora_inicio_turno,
                        hora_fin_turno: configPreview.hora_fin_turno,
                        celda_turno: configPreview.cel_cod || '',
                        turnos_personalizados: JSON.stringify(turnosPreview)
                    },
                    function(data) {
                        $.post('', {
                            generarTurnosAjax: true,
                            cupos_default: data.cupos_default,
                            fecha_inicio: data.fecha_inicio,
                            fecha_fin: data.fecha_fin,
                            hora_inicio_turno: data.hora_inicio_turno,
                            hora_fin_turno: data.hora_fin_turno,
                            celda_turno: data.celda_turno,
                            turnos_personalizados: data.turnos_personalizados
                        }, function(r) {
                            if (r.success) {
                                $.alert('<i class="glyphicon glyphicon-ok-circle" style="color: #28a745;"></i> ' + r.message);
                                // Ocultar vista previsualizar y volver al grid
                                $('#vistaPrevisualizar').hide();
                                volverAlGrid();
                            } else {
                                $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> Error: ' + r.message);
                            }
                        }, 'json');
                    }
                );
            });
        }
        
        // Función para actualizar el total de cupos de un día específico en la vista de detalle
        function actualizarTotalCuposDiaDetalle(fecha) {
            if (!fecha) return;
            
            let totalCuposDia = 0;
            $('.dia-grupo[data-fecha-dia="' + fecha + '"] .turno-card').each(function() {
                if ($(this).hasClass('activo')) {
                    let cupos = parseInt($(this).find('.cupos-input').val()) || 0;
                    totalCuposDia += cupos;
                }
            });
            
            // Buscar el encabezado del día y actualizar el total
            let $diaGrupo = $('.dia-grupo[data-fecha-dia="' + fecha + '"]');
            if ($diaGrupo.length > 0) {
                let $totalElement = $diaGrupo.find('.total-cupos-dia');
                $totalElement.html('<i class="glyphicon glyphicon-user"></i> Total Cupos: ' + totalCuposDia);
                
                // Efecto visual de actualización (destello)
                $totalElement.css('transition', 'background-color 0.3s');
                $totalElement.css('background-color', 'rgba(46, 204, 113, 0.5)');
                setTimeout(function() {
                    $totalElement.css('background-color', 'rgba(255,255,255,0.3)');
                }, 300);
            }
        }
        
        // Toggle estado de un turno
        function toggleTurno(id, btn) {
            $.post('', { toggleTurnoAjax: true, Tud_Cod: id }, function(r) {
                if (r.success) {
                    let $card = $(btn).closest('.turno-card');
                    let esActivo = r.nuevo_estado === 'A';
                    
                    $card.removeClass('activo inactivo').addClass(esActivo ? 'activo' : 'inactivo');
                    $(btn).removeClass('activo inactivo').addClass(esActivo ? 'activo' : 'inactivo');
                    $(btn).text(esActivo ? 'ACTIVO' : 'SUSPENDIDO');
                    
                    actualizarResumen();
                    
                    // Actualizar el total de cupos del día
                    let fecha = $card.closest('.dia-grupo').data('fecha-dia');
                    if (fecha) {
                        actualizarTotalCuposDiaDetalle(fecha);
                    }
                }
            }, 'json');
        }
        
        // Marcar campo como modificado
        function marcarModificado(input) {
            $(input).css('border-color', '#ffc107');
            
            // Actualizar el total de cupos del día
            let fecha = $(input).data('fecha');
            if (fecha) {
                actualizarTotalCuposDiaDetalle(fecha);
            }
        }
        
        // Actualizar celda de un turno en la vista de detalle
        function actualizarCeldaTurno(tudCod, celCod) {
            // Marcar visualmente que se modificó
            $('.celda-select[data-id="' + tudCod + '"]').css('border-color', '#ffc107');
        }
        
        // Guardar cambios masivos
        function guardarCambios() {
            let turnos = [];
            
            $('.turno-card').each(function() {
                let id = $(this).data('id');
                let cupos = $(this).find('.cupos-input').val();
                let estado = $(this).hasClass('activo') ? 'A' : 'S';
                let celCod = $(this).find('.celda-select').val() || null;
                
                turnos.push({ id: id, cupos: cupos, estado: estado, cel_cod: celCod });
            });
            
            $.post('', {
                updateTurnosMasivoAjax: true,
                turnos_data: JSON.stringify(turnos)
            }, function(r) {
                if (r.success) {
                    $('.cupos-input').css('border-color', '#ddd');
                    $('.celda-select').css('border-color', '#ddd');
                    actualizarResumen();
                    // Persistir reservas que estén en turnosData (aplicadas con reserva masiva sin guardar directo)
                    if (typeof turnosData === 'object' && turnosData !== null) {
                        Object.keys(turnosData).forEach(function(fecha) {
                            (turnosData[fecha] || []).forEach(function(turno) {
                                if (turno.reservas && turno.reservas.length > 0) {
                                    var plantasData = turno.reservas.map(function(res) {
                                        return { planta_id: res.Pla_Cod, cantidad: res.Tre_Can, nombre: res.planta_nombre || '' };
                                    });
                                    $.post('', {
                                        guardarReservaAjax: true,
                                        Tud_Cod: turno.Tud_Cod,
                                        plantas_data: JSON.stringify(plantasData)
                                    }, function() {}, 'json');
                                }
                            });
                        });
                    }
                    $.alert('<i class="glyphicon glyphicon-ok-circle" style="color: #28a745;"></i> Cambios guardados correctamente');
                } else {
                    $.alert('<i class="glyphicon glyphicon-warning-sign" style="color: #dc3545;"></i> Error: ' + r.message);
                }
            }, 'json');
        }
        
        // Habilitar todos los turnos
        function habilitarTodos() {
            $('.turno-card').removeClass('inactivo').addClass('activo');
            $('.turno-toggle').removeClass('inactivo').addClass('activo').text('ACTIVO');
            actualizarResumen();
            
            // Actualizar todos los totales de días
            $('.dia-grupo').each(function() {
                let fecha = $(this).data('fecha-dia');
                if (fecha) {
                    actualizarTotalCuposDiaDetalle(fecha);
                }
            });
        }
        
        // Deshabilitar todos los turnos
        function deshabilitarTodos() {
            $('.turno-card').removeClass('activo').addClass('inactivo');
            $('.turno-toggle').removeClass('activo').addClass('inactivo').text('SUSPENDIDO');
            actualizarResumen();
            
            // Actualizar todos los totales de días (ahora todos serán 0)
            $('.dia-grupo').each(function() {
                let fecha = $(this).data('fecha-dia');
                if (fecha) {
                    actualizarTotalCuposDiaDetalle(fecha);
                }
            });
        }
        
        // Establecer cupos masivamente
        function setCuposMasivo() {
            let nuevoCupo = prompt('Ingrese el nuevo valor de cupos para todos los turnos:', '25');
            if (nuevoCupo && !isNaN(nuevoCupo)) {
                $('.cupos-input').val(nuevoCupo).css('border-color', '#ffc107');
                actualizarResumen();
                
                // Actualizar todos los totales de días
                $('.dia-grupo').each(function() {
                    let fecha = $(this).data('fecha-dia');
                    if (fecha) {
                        actualizarTotalCuposDiaDetalle(fecha);
                    }
                });
            }
        }
        
        // Actualizar resumen
        function actualizarResumen() {
            let activos = 0;
            let inactivos = 0;
            let totalCupos = 0;
            let reservados = 0;
            
            $('.turno-card').each(function() {
                if ($(this).hasClass('activo')) {
                    activos++;
                    totalCupos += parseInt($(this).find('.cupos-input').val()) || 0;
                } else {
                    inactivos++;
                }
                var $resCount = $(this).find('.reserved-count');
                if ($resCount.length) {
                    reservados += parseInt($resCount.text()) || 0;
                }
            });
            
            $('#totalActivos').text(activos);
            $('#totalInactivos').text(inactivos);
            $('#totalCupos').text(totalCupos);
            $('#totalReservas').text(reservados);
        }
        // ==========================================
        // FUNCIONES PARA RESERVA DE CUPOS
        // ==========================================
        var plantasCatalogo = null;
        
        function agregarFilaPlanta(plantaData, tableId) {
            let targetId = tableId || 'tablaPlantasReserva';
            let tbody = $('#' + targetId + ' tbody');
            let rowId = 'row_' + new Date().getTime();
            
            let html = '<tr id="' + rowId + '">';
            html += '<td>';
            html += '<select class="form-control input-sm select-planta" style="width: 100%;">';
            
            // Si hay datos pre-seleccionados, agregar la opción
            if (plantaData && plantaData.planta_id) {
                 html += '<option value="' + plantaData.planta_id + '" selected>' + plantaData.nombre + '</option>';
            } else {
                 html += '<option value="">-- Buscando plantas... --</option>';
            }
            
            html += '</select>';
            html += '</td>';
            html += '<td>';
            let valCantidad = (plantaData && plantaData.cantidad) ? plantaData.cantidad : '';
            html += '<input type="number" class="form-control input-sm input-cantidad" min="1" placeholder="Cant." value="' + valCantidad + '">';
            html += '</td>';
            html += '<td class="text-center">';
            html += '<button type="button" class="btn btn-danger btn-xs" onclick="eliminarReservaPlanta(this)"><i class="glyphicon glyphicon-trash"></i></button>';
            html += '</td>';
            html += '</tr>';
            
            tbody.append(html);
            
            let $newSelect = $('#' + rowId + ' .select-planta');
            function buildOptionsForSelect($select) {
                let selectedVal = $select.val() || (plantaData && plantaData.planta_id) || '';
                let selectedText = $select.find('option:selected').text() || (plantaData && plantaData.nombre) || '';
                let usedIds = [];
                $('#' + targetId + ' tbody .select-planta').each(function() {
                    let v = $(this).val();
                    if (v && v !== selectedVal) usedIds.push(v);
                });
                let options = '<option value="">-- Seleccione Planta --</option>';
                if (selectedVal && selectedVal !== '') {
                    options += '<option value="' + selectedVal + '" selected>' + selectedText + '</option>';
                }
                (plantasCatalogo || []).forEach(function(item) {
                    let idStr = item.id.toString();
                    if (usedIds.indexOf(idStr) === -1 && idStr !== selectedVal) {
                        options += '<option value="' + item.id + '">' + item.text + '</option>';
                    }
                });
                $select.html(options);
                if (targetId === 'tablaPlantasReserva' || targetId === 'tablaPlantasReservaMasiva') {
                    if ($.fn.select2) {
                        if ($select.data('select2')) $select.select2('destroy');
                        var $modal = targetId === 'tablaPlantasReserva' ? $('#modalReservaCupos') : $('#modalReservaMasiva');
                        $select.select2({
                            width: '100%',
                            placeholder: '-- Seleccione Planta --',
                            allowClear: true,
                            dropdownParent: $modal.length ? $modal : $('body')
                        });
                    } else if ($.fn.chosen) {
                        $select.chosen({width: "100%", search_contains: true});
                    }
                } else if ($.fn.chosen) {
                    $select.chosen({width: "100%", search_contains: true});
                } else if ($.fn.select2) {
                    $select.select2({width: "100%"});
                }
            }
            if (!plantasCatalogo) {
                $.get('', { buscarPlantasAjax: true }, function(r) {
                    if (r.success) {
                        plantasCatalogo = r.items || [];
                        buildOptionsForSelect($newSelect);
                    }
                }, 'json');
            } else {
                buildOptionsForSelect($newSelect);
            }
        }
        
        function eliminarFilaPlanta(btn) {
            $(btn).closest('tr').remove();
        }
        
        function refreshPlantaOptions(tableId) {
            if (!plantasCatalogo) return;
            let $table = $('#' + tableId);
            let usedIds = [];
            $table.find('tbody .select-planta').each(function() {
                let v = $(this).val();
                if (v) usedIds.push(v);
            });
            $table.find('tbody .select-planta').each(function() {
                let $sel = $(this);
                let selectedVal = $sel.val() || '';
                let selectedText = $sel.find('option:selected').text() || '';
                let options = '<option value="">-- Seleccione Planta --</option>';
                if (selectedVal) {
                    options += '<option value="' + selectedVal + '" selected>' + selectedText + '</option>';
                }
                plantasCatalogo.forEach(function(item) {
                    let idStr = item.id.toString();
                    if ((selectedVal && idStr === selectedVal) || usedIds.indexOf(idStr) !== -1) return;
                    options += '<option value="' + item.id + '">' + item.text + '</option>';
                });
                $sel.html(options);
                if (tableId === 'tablaPlantasReserva' || tableId === 'tablaPlantasReservaMasiva') {
                    if ($.fn.select2) {
                        if ($sel.data('select2')) $sel.select2('destroy');
                        var $modal = tableId === 'tablaPlantasReserva' ? $('#modalReservaCupos') : $('#modalReservaMasiva');
                        $sel.select2({
                            width: '100%',
                            placeholder: '-- Seleccione Planta --',
                            allowClear: true,
                            dropdownParent: $modal.length ? $modal : $('body')
                        });
                    }
                } else if ($.fn.chosen) {
                    $sel.trigger('chosen:updated');
                } else if ($.fn.select2 && $sel.data('select2')) {
                    $sel.trigger('change.select2');
                }
            });
        }
        $(document).on('change', '#tablaPlantasReserva .select-planta, #tablaPlantasReservaMasiva .select-planta', function() {
            let tableId = $(this).closest('table').attr('id');
            refreshPlantaOptions(tableId);
        });
        
        function eliminarReservaPlanta(btn) {
            let turnoId = $('#reserva_turno_id').val() || '';
            let $row = $(btn).closest('tr');
            let plantaId = $row.find('.select-planta').val();
            
            if (!plantaId) {
                // No hay planta seleccionada, sólo eliminar la fila
                $row.remove();
                return;
            }
            
            // Preview mode: turnoId empieza con new_
            if (turnoId && turnoId.toString().startsWith('new_')) {
                if (typeof turnosPreview !== 'undefined') {
                    let turnoFound = turnosPreview.find(t => t.id === turnoId);
                    if (turnoFound && turnoFound.reservas && turnoFound.reservas.length > 0) {
                        turnoFound.reservas = turnoFound.reservas.filter(r => r.planta_id != plantaId);
                        renderizarTurnosPreview();
                    }
                }
                $row.remove();
                return;
            }
            
            // BD: eliminar la reserva de esta planta para el turno
            $.confirm({
                title: 'Eliminar Reserva',
                content: '¿Desea eliminar la reserva de esta planta para el turno?',
                buttons: {
                    confirm: {
                        text: 'Eliminar',
                        btnClass: 'btn-danger',
                        action: function() {
                            $.post('', { eliminarReservaAjax: true, Tud_Cod: turnoId, Pla_Cod: plantaId }, function(r) {
                                if (r && r.success) {
                                    $row.remove();
                                    if (typeof turCodActual !== 'undefined' && turCodActual) {
                                        cargarDetalleTurnos(turCodActual);
                                    }
                                } else {
                                    $.alert('Error al eliminar la reserva: ' + (r.message || 'Error'));
                                }
                            }, 'json');
                        }
                    },
                    cancel: { text: 'Cancelar' }
                }
            });
        }

        function abrirModalReserva(fecha, turnoId, horaInicio, horaFin) {
            // Permitir abrir si es turno de previsualización (new_)
            let esPreview = turnoId && turnoId.toString().startsWith('new_');
            
            // Configurar botón de guardado según el modo
            if (esPreview) {
                $('#btnGuardarReserva').html('<i class="glyphicon glyphicon-pushpin"></i> Reservar Cupos');
                $('#btnGuardarReserva').removeClass('btn-primary btn-success').addClass('btn-info');
            } else {
                $('#btnGuardarReserva').html('<i class="glyphicon glyphicon-floppy-disk"></i> Guardar Reserva');
                $('#btnGuardarReserva').removeClass('btn-info btn-success').addClass('btn-primary');
            }

            if (!turnoId && !esPreview) {
                $.alert('<i class="glyphicon glyphicon-info-sign" style="color: #31708f;"></i> Debe guardar la configuración de turnos antes de realizar reservas.');
                return;
            }

            $('#reserva_fecha').val(fecha);
            $('#reserva_turno_id').val(turnoId || '');
            $('#reserva_hora_inicio').val(horaInicio || '');
            $('#reserva_hora_fin').val(horaFin || '');
            
            // Limpiar tabla
            $('#tablaPlantasReserva tbody').empty();
            
            // Si es preview, cargar desde memoria
            if (esPreview) {
                let turnoFound = null;
                // Buscar en turnosPreview
                if (typeof turnosPreview !== 'undefined') {
                    turnoFound = turnosPreview.find(t => t.id === turnoId);
                }
                
                if (turnoFound && turnoFound.reservas && turnoFound.reservas.length > 0) {
                    turnoFound.reservas.forEach(res => {
                        agregarFilaPlanta(res);
                    });
                } else {
                    agregarFilaPlanta();
                }
            } else {
                // Comportamiento original para turnos de BD
                let cargadoDesdeMemoria = false;
                if (typeof turnosData !== 'undefined' && fecha && turnoId && turnosData[fecha]) {
                    let lista = turnosData[fecha];
                    for (var i = 0; i < lista.length; i++) {
                        if (lista[i].Tud_Cod == turnoId && lista[i].reservas && lista[i].reservas.length > 0) {
                            lista[i].reservas.forEach(function(item) {
                                agregarFilaPlanta({
                                    planta_id: item.Pla_Cod || item.planta_id,
                                    nombre: item.Pla_Nom || item.planta_nombre || item.nombre || ('Planta ' + (item.Pla_Cod || item.planta_id)),
                                    cantidad: item.Tre_Can || item.cantidad
                                });
                            });
                            cargadoDesdeMemoria = true;
                            break;
                        }
                    }
                }
                if (!cargadoDesdeMemoria) {
                    if (turnoId) {
                        $.get('', { obtenerReservasTurnoAjax: true, Tud_Cod: turnoId }, function(r) {
                            if (r && r.success && r.items && r.items.length > 0) {
                                r.items.forEach(function(item) {
                                    agregarFilaPlanta({
                                        planta_id: item.Pla_Cod,
                                        nombre: item.Pla_Nom || item.planta_nombre || ('Planta ' + item.Pla_Cod),
                                        cantidad: item.Tre_Can 
                                    });
                                });
                            } else {
                                agregarFilaPlanta();
                            }
                        }, 'json');
                    } else {
                        agregarFilaPlanta();
                    }
                }
            }
            
            // Mostrar fecha formateada en el título
            let fechaObj = new Date(fecha + 'T00:00:00');
            let opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            let textoInfo = fechaObj.toLocaleDateString('es-ES', opciones);
            
            if (horaInicio && horaFin) {
                textoInfo += ' <br><small>Horario: ' + horaInicio + ' - ' + horaFin + '</small>';
            }
            
            $('#tituloFechaReserva').html(textoInfo);
            
            $('#modalReservaCupos').modal('show');
        }
        
        function guardarReserva() {
            let fecha = $('#reserva_fecha').val();
            let turnoId = $('#reserva_turno_id').val();
            let horaInicio = $('#reserva_hora_inicio').val();
            let horaFin = $('#reserva_hora_fin').val();
            
            // Recolectar datos de plantas
            let plantas = [];
            let error = false;
            let seen = {};
            
            $('#tablaPlantasReserva tbody tr').each(function() {
                let plantaId = $(this).find('.select-planta').val();
                let cantidad = $(this).find('.input-cantidad').val();
                let plantaTexto = $(this).find('.select-planta option:selected').text();
                                
                if (plantaId && cantidad && parseInt(cantidad) > 0) {
                    if (seen[plantaId]) { error = true; return false; }
                    seen[plantaId] = true;
                    plantas.push({
                        planta_id: plantaId,
                        cantidad: cantidad,
                        nombre: plantaTexto
                    });
                } else if (plantaId || (cantidad && parseInt(cantidad) > 0)) {                   
                    error = true;
                    return false; // break loop
                }
            });
            
            if (error) return;
            
            if (plantas.length === 0) {
                return;
            }
            
            // Verificar si es turno de previsualización (guardado en memoria)
            if (turnoId && turnoId.toString().startsWith('new_')) {
                 if (typeof turnosPreview !== 'undefined') {
                    let turnoFound = turnosPreview.find(t => t.id === turnoId);
                    if (turnoFound) {
                        // Guardar en el objeto de memoria
                        // plantas es array de {planta_id, cantidad, nombre}
                        turnoFound.reservas = plantas;
                        
                        // Actualizar UI
                        renderizarTurnosPreview();
                        
                        $('#modalReservaCupos').modal('hide');
                        return;
                    }
                 }
            }
            
            if (typeof turnosData !== 'undefined' && fecha && turnoId && turnosData[fecha]) {
                let lista = turnosData[fecha];
                for (var i = 0; i < lista.length; i++) {
                    if (lista[i].Tud_Cod == turnoId) {
                        lista[i].reservas = plantas.map(function(p) {
                            return { Pla_Cod: p.planta_id, Tre_Can: p.cantidad, planta_nombre: p.nombre };
                        });
                        break;
                    }
                }
            }
            
            var totalReservado = 0;
            plantas.forEach(function(p){ totalReservado += parseInt(p.cantidad) || 0; });
            var $card = $('.turno-card[data-id="' + turnoId + '"]');
            if ($card.length) {
                $card.find('.reserved-count').text(totalReservado);
                var $btn = $card.find('button.btn.btn-xs.btn-block');
                if ($btn.length) {
                    $btn.removeClass('btn-info').addClass('btn-warning');
                }
            }
            
            // Guardar cambios en BD
            $.post('', {
                guardarReservaAjax: true,
                Tud_Cod: turnoId,
                plantas_data: JSON.stringify(plantas)
            }, function(r) {
                if (!r.success) {
                    $.alert('Error al guardar reserva: ' + (r.message || 'Error desconocido'));
                }
            }, 'json');
            
            actualizarResumen();
            $('#modalReservaCupos').modal('hide');
        }

    </script>

<!-- Modal Reserva Cupos -->
<div class="modal fade" id="modalReservaCupos" tabindex="-1" role="dialog" aria-labelledby="modalReservaLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #f0ad4e; color: white;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="modalReservaLabel"><i class="glyphicon glyphicon-bookmark"></i> Reservar Cupos</h4>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning" style="padding: 10px; margin-bottom: 15px;">
            <i class="glyphicon glyphicon-calendar"></i> Fecha: <strong id="tituloFechaReserva"></strong>
        </div>
        <form id="formReservaCupos">
            <input type="hidden" id="reserva_fecha">
            <input type="hidden" id="reserva_turno_id">
            <input type="hidden" id="reserva_hora_inicio">
            <input type="hidden" id="reserva_hora_fin">
            
            <div class="table-responsive">
                <table class="table" id="tablaPlantasReserva">
                    <thead>
                        <tr>
                            <th style="width: 60%">Planta</th>
                            <th style="width: 25%">Cupos</th>
                            <th style="width: 15%; min-width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows added dynamically -->
                    </tbody>
                </table>
            </div>
            
            <button type="button" class="btn btn-info btn-sm" onclick="agregarFilaPlanta()"><i class="glyphicon glyphicon-plus"></i> Agregar Planta</button>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" id="btnGuardarReserva" onclick="guardarReserva()">Guardar Reserva</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Reserva Masiva -->
<div class="modal fade" id="modalReservaMasiva" tabindex="-1" role="dialog" aria-labelledby="modalReservaMasivaLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #f0ad4e; color: white;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="modalReservaMasivaLabel"><i class="glyphicon glyphicon-tags"></i> Reserva Masiva de Cupos</h4>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning" style="padding: 10px; margin-bottom: 15px;">
            <i class="glyphicon glyphicon-info-sign"></i> Esta acción aplicará la reserva de plantas a todos los turnos que coincidan con el rango de fechas y hora seleccionados.
        </div>
        
        <form id="formReservaMasiva">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Fecha Desde:</label>
                        <input type="date" class="form-control input-sm" id="reserva_masiva_desde" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Fecha Hasta:</label>
                        <input type="date" class="form-control input-sm" id="reserva_masiva_hasta" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Hora Inicio:</label>
                        <select class="form-control input-sm" id="reserva_masiva_hin">
                            <?php for($h=0; $h<24; $h++): $val = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00'; ?>
                            <option value="<?php echo $val; ?>"><?php echo $val; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Hora Fin:</label>
                        <select class="form-control input-sm" id="reserva_masiva_hfi">
                            <?php for($h=1; $h<=24; $h++): $val = str_pad($h % 24, 2, '0', STR_PAD_LEFT) . ':00'; ?>
                            <option value="<?php echo $val; ?>"><?php echo $val; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <hr>
            <label>Plantas a Reservar:</label>
            <div class="table-responsive">
                <table class="table" id="tablaPlantasReservaMasiva">
                    <thead>
                        <tr>
                            <th style="width: 60%">Planta</th>
                            <th style="width: 25%">Cupos</th>
                            <th style="width: 15%; min-width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows added dynamically -->
                    </tbody>
                </table>
            </div>
            
            <button type="button" class="btn btn-info btn-sm" onclick="agregarFilaPlanta(null, 'tablaPlantasReservaMasiva')"><i class="glyphicon glyphicon-plus"></i> Agregar Planta</button>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" id="btnGuardarReservaMasiva" onclick="guardarReservaMasiva()">Aplicar Reserva Masiva</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Confirmación Previsualización -->
<div class="modal fade" id="modalConfirmPreview" tabindex="-1" role="dialog" aria-labelledby="modalConfirmPreviewLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #5bc0de; color: white;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="modalConfirmPreviewLabel"><i class="glyphicon glyphicon-pushpin"></i> Confirmar en Previsualización</h4>
      </div>
      <div class="modal-body">
        <div id="confirmPreviewContent" style="font-size: 14px; color: #333;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-info" id="btnConfirmPreview">Aplicar</button>
      </div>
    </div>
  </div>
</div>

<script>
function abrirModalReservaMasiva(esPreview) {
    esPreview = esPreview || false;
    var esModificarConfig = !esPreview && $('#vistaDetalle').is(':visible');
    $('#modalReservaMasiva').data('esPreview', esPreview);
    $('#modalReservaMasiva').data('esModificarConfig', esModificarConfig);
    
    if (esPreview) {
        // En preview mode, usar las fechas de la configuración
        if (typeof configPreview !== 'undefined' && configPreview.fecha_inicio) {
            $('#reserva_masiva_desde').val(configPreview.fecha_inicio);
            $('#reserva_masiva_hasta').val(configPreview.fecha_fin);
        } else {
             $('#reserva_masiva_desde').val($('#fecha_inicio').val());
             $('#reserva_masiva_hasta').val($('#fecha_fin').val());
        }
        
        $('#btnGuardarReservaMasiva').html('<i class="glyphicon glyphicon-pushpin"></i> Reservar Cupos');
        $('#btnGuardarReservaMasiva').removeClass('btn-warning').addClass('btn-info');
    } else {
        // Modo normal (BD)
        // Intentar pre-llenar con las fechas del filtro actual si existen
        if ($('#filtro_fecha_inicio_desde').val()) {
            $('#reserva_masiva_desde').val($('#filtro_fecha_inicio_desde').val());
        }
        if ($('#filtro_fecha_inicio_hasta').val()) {
            $('#reserva_masiva_hasta').val($('#filtro_fecha_inicio_hasta').val());
        }
        
        $('#btnGuardarReservaMasiva').html('Aplicar Reserva Masiva');
        $('#btnGuardarReservaMasiva').removeClass('btn-info').addClass('btn-warning');
    }
    
    // Limpiar tabla
    $('#tablaPlantasReservaMasiva tbody').empty();
    agregarFilaPlanta(null, 'tablaPlantasReservaMasiva');
    
    $('#modalReservaMasiva').modal('show');
}

function guardarReservaMasiva() {
    let fechaDesde = $('#reserva_masiva_desde').val();
    let fechaHasta = $('#reserva_masiva_hasta').val();
    let horaInicio = $('#reserva_masiva_hin').val();
    let horaFin = $('#reserva_masiva_hfi').val();
    
    if (!fechaDesde || !fechaHasta) {
        $.alert('Debe seleccionar el rango de fechas.');
        return;
    }
    
    if (horaInicio >= horaFin) {
        $.alert('La hora de inicio debe ser menor a la hora de fin.');
        return;
    }
    
    // Recolectar plantas
    let plantas = [];
    let valid = true;
    
    $('#tablaPlantasReservaMasiva tbody tr').each(function() {
        let $select = $(this).find('.select-planta');
        let plantaId = $select.val();
        let plantaNombre = $select.find('option:selected').text();
        let cantidad = $(this).find('.input-cantidad').val();
        
        if (!plantaId || plantaId === '') return;
        
        if (!cantidad || cantidad <= 0) {
            $.alert('Debe ingresar una cantidad válida para todas las plantas seleccionadas.');
            valid = false;
            return false;
        }
        
        plantas.push({
            planta_id: plantaId,
            planta_nombre: plantaNombre,
            cantidad: cantidad
        });
    });
    
    if (!valid) return;
    
    if (plantas.length === 0) {
        $.alert('Debe agregar al menos una planta para reservar.');
        return;
    }
    
    let esPreview = $('#modalReservaMasiva').data('esPreview');
    let esModificarConfig = $('#modalReservaMasiva').data('esModificarConfig');
    let titleMsg = esPreview ? 'Confirmar en Previsualización' : (esModificarConfig ? 'Aplicar Reserva Masiva (en vista)' : 'Confirmar Reserva Masiva');
    let contentMsg = 'Se aplicará esta reserva a todos los turnos en el rango seleccionado entre las ' + horaInicio + ' y ' + horaFin + '.<br>';
    
    if (esPreview) {
        contentMsg += '<b>Nota:</b> Los cambios se aplicarán solo en memoria. Debe guardar la configuración para hacerlos permanentes.';
    } else if (esModificarConfig) {
        contentMsg += '<b>Nota:</b> Los cambios se aplicarán solo en la vista. Debe hacer clic en "Guardar Cambios" para persistir las reservas.';
    } else {
        contentMsg += 'Esta acción no se puede deshacer fácilmente.';
    }
    
    if (esPreview) {
        ejecutarReservaMasivaPreview(fechaDesde, fechaHasta, horaInicio, horaFin, plantas);
    } else if (esModificarConfig) {
        ejecutarReservaMasivaEnTurnosData(fechaDesde, fechaHasta, horaInicio, horaFin, plantas);
    } else {
        ejecutarReservaMasiva(fechaDesde, fechaHasta, horaInicio, horaFin, plantas);
    }
}

function ejecutarReservaMasivaPreview(fecIni, fecFin, hIni, hFin, plantas) {
    let count = 0;
    
    // Convertir fechas para comparación
    let dInicio = new Date(fecIni + 'T00:00:00');
    let dFin = new Date(fecFin + 'T00:00:00');
    
    turnosPreview.forEach(function(turno) {
        let dTurno = new Date(turno.fecha + 'T00:00:00');
        
        // Comparar fechas (ignorar hora en objeto Date)
        if (dTurno >= dInicio && dTurno <= dFin) {
            // Verificar horas
            let tIni = turno.hora_inicio.substring(0, 5);
            let tFin = turno.hora_fin.substring(0, 5);
            
            if (tIni >= hIni && tFin <= hFin && turno.estado === 'A') {
                // Aplicar reservas
                if (!turno.reservas) turno.reservas = [];
                
                plantas.forEach(function(p) {
                    // Verificar si ya existe reserva para esta planta
                    let existingIndex = -1;
                    for(let i=0; i<turno.reservas.length; i++) {
                        if (turno.reservas[i].planta_id == p.planta_id) {
                            existingIndex = i;
                            break;
                        }
                    }
                    
                    if (existingIndex >= 0) {
                         // Actualizar cantidad
                         turno.reservas[existingIndex].cantidad = p.cantidad;
                    } else {
                        turno.reservas.push({
                            planta_id: p.planta_id,
                            nombre: p.planta_nombre,
                            cantidad: p.cantidad,
                            Tre_Est: 'A'
                        });
                    }
                });
                count++;
            }
        }
    });
    
    $('#modalReservaMasiva').modal('hide');
    renderizarTurnosPreview();
    
    /*$.alert({
        title: 'Aplicado en Previsualización',
        content: 'Se han aplicado reservas a ' + count + ' turnos.<br>Recuerde guardar la configuración para persistir los cambios.',
        type: 'blue'
    });*/
}

function ejecutarReservaMasivaEnTurnosData(fecIni, fecFin, hIni, hFin, plantas) {
    if (typeof turnosData === 'undefined' || !turnosData || typeof configActual === 'undefined' || !configActual) {
        $.alert('No hay datos de turnos cargados. Recargue el detalle de la configuración.');
        return;
    }
    var dInicio = new Date(fecIni + 'T00:00:00');
    var dFin = new Date(fecFin + 'T00:00:00');
    var count = 0;
    Object.keys(turnosData).forEach(function(fecha) {
        var dTurno = new Date(fecha + 'T00:00:00');
        if (dTurno < dInicio || dTurno > dFin) return;
        var lista = turnosData[fecha];
        if (!lista || !lista.length) return;
        lista.forEach(function(turno) {
            if ((turno.Tud_Est || 'A') !== 'A') return;
            var tIni = (turno.Tud_Hin || '').substring(0, 5);
            var tFin = (turno.Tud_Hfi || '').substring(0, 5);
            if (tIni >= hIni && tFin <= hFin) {
                if (!turno.reservas) turno.reservas = [];
                plantas.forEach(function(p) {
                    var existingIndex = -1;
                    for (var i = 0; i < turno.reservas.length; i++) {
                        if (turno.reservas[i].Pla_Cod == p.planta_id) { existingIndex = i; break; }
                    }
                    var item = { Pla_Cod: p.planta_id, Tre_Can: parseInt(p.cantidad, 10) || 0, planta_nombre: p.planta_nombre || '' };
                    if (existingIndex >= 0) {
                        turno.reservas[existingIndex] = item;
                    } else {
                        turno.reservas.push(item);
                    }
                });
                count++;
            }
        });
    });
    renderizarTurnosPorDia(turnosData, configActual);
    actualizarResumen();
    $('#modalReservaMasiva').modal('hide');
}

function ejecutarReservaMasiva(fecIni, fecFin, hIni, hFin, plantas) {
    let turCod = (typeof turCodActual !== 'undefined') ? turCodActual : '';
    
    $.post('', {
        guardarReservaMasivaAjax: true,
        Tur_Cod: turCod,
        fec_ini: fecIni,
        fec_fin: fecFin,
        h_ini: hIni,
        h_fin: hFin,
        plantas: plantas
    }, function(r) {
        if (r.success) {
            $.alert('Reserva masiva aplicada correctamente. ' + (r.count || 0) + ' reservas creadas.');
            $('#modalReservaMasiva').modal('hide');
            aplicarFiltros(); 
        } else {
            $.alert(r.message || 'Error al guardar reserva masiva.');
        }
    }, 'json');
}
</script>

</BODY>
</HTML>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>
