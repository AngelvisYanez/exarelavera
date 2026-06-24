<?php
/**
 * Formulario y Gestión de Horómetro de Maquinaria - Relavera
 * Permite registrar, revisar, aprobar y controlar lecturas de horómetro y mantenimiento preventivo.
 * Desarrollado bajo arquitectura de dos ambientes de consulta y registro.
 * Compatible con PHP 5.3.8.
 * @author Sistema EXA
 * @version 1.0
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_maquinaria_horometro.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Maquinaria_Horometro($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Maquinaria_Horometro;

// 1. Obtener planta asignada al usuario
$Pla_Cod_Log = isset($_SESSION['Ses_Pla_Cod']) ? (int)$_SESSION['Ses_Pla_Cod'] : 0;

if ($Pla_Cod_Log === 0) {
    $row_mu = $obBD_con1->getArrayConsultaSql("SELECT Pla_Cod FROM manifiesto_usuario WHERE Usu_Cod = '".$_SESSION['Ses_Usu_Cod']."' LIMIT 1", $obBD_conexion);
    if (isset($row_mu[0]['Pla_Cod'])) {
        $Pla_Cod_Log = (int)$row_mu[0]['Pla_Cod'];
    }
}

if ($Pla_Cod_Log === 0) {
    $row_pla_def = $obBD_con1->getArrayConsultaSql("SELECT Pla_Cod FROM manifiesto_plantas mp LEFT JOIN cliente c ON c.Cli_Cod = mp.Cli_Cod WHERE mp.Pla_Est = 'A' AND c.Emp_Cod = '".$_SESSION['Ses_Emp_Cod']."' LIMIT 1", $obBD_conexion);
    $Pla_Cod_Log = isset($row_pla_def[0]['Pla_Cod']) ? (int)$row_pla_def[0]['Pla_Cod'] : 0;
}

// Determinar rol de usuario
$user_role = 'OP'; // Operador por defecto
$row_perfil = $obBD_con1->getArrayConsultaSql("SELECT p.Per_Des FROM usuarperfi up INNER JOIN perfiles p ON up.Per_Cod = p.Per_Cod WHERE up.Usu_Cod = '" . $_SESSION['Ses_Usu_Cod'] . "'", $obBD_conexion);
if (!empty($row_perfil)) {
    foreach ($row_perfil as $perf) {
        $des = strtoupper($perf['Per_Des']);
        if ($des === 'ADMINISTRADOR' || $des === 'GERENTE' || $des === 'SISTEMAS') {
            $user_role = 'ADM';
            break;
        } else if ($des === 'SUPERVISOR' || $des === 'PLANTAS') {
            $user_role = 'SUP';
        }
    }
}

/* ==================== AJAX HANDLERS ==================== */

// Cargar Métricas Dashboard
if (isset($_GET['getDashboardMetricsAjax'])) {
    $resp = array('success' => true, 'pendientes' => 0, 'horas_mes' => 0, 'mantenimientos_alerta' => 0);
    
    // Lecturas pendientes
    $row_p = $obBD_con1->getRowConsulta(7, array('tipo' => 'pendientes'), $obBD_conexion);
    $resp['pendientes'] = isset($row_p['total']) ? (int)$row_p['total'] : 0;

    // Horas acumuladas mes
    $row_h = $obBD_con1->getRowConsulta(7, array('tipo' => 'horas_mes'), $obBD_conexion);
    $resp['horas_mes'] = isset($row_h['total']) ? (float)$row_h['total'] : 0.00;

    // Alertas de mantenimiento preventivo
    $alert_count = 0;
    $row_alertas = $obBD_con1->getArrayConsulta(10, array($_SESSION['Ses_Emp_Cod']), $obBD_conexion);
    if (!empty($row_alertas)) {
        foreach ($row_alertas as $alert) {
            $limite = (float)$alert['Cma_Hrs_Ult'] + (float)$alert['Cma_Hrs_Fco'];
            if ((float)$alert['lectura_actual'] >= $limite) {
                $alert_count++;
            }
        }
    }
    $resp['mantenimientos_alerta'] = $alert_count;

    $obBD_con1->echoJson($resp);
    exit;
}

// Listar máquinas asociadas a la planta
if (isset($_GET['listMaquinasAjax'])) {
    $rows_data = $obBD_con1->getArrayConsulta(1, array('Pla_Cod' => $Pla_Cod_Log), $obBD_conexion);
    $obBD_con1->utf8_change_param($rows_data);
    $obBD_con1->echoJson($rows_data);
    exit;
}

// Listar operadores asociados a la planta
if (isset($_GET['listOperadoresAjax'])) {
    $rows_data = $obBD_con1->getArrayConsulta(2, array('Pla_Cod' => $Pla_Cod_Log), $obBD_conexion);
    $obBD_con1->utf8_change_param($rows_data);
    $obBD_con1->echoJson($rows_data);
    exit;
}

// Listar registros en el Grid (Ambiente 1)
if (isset($_GET['listHorometrosGridAjax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $op_opciones = isset($_GET['op_opciones']) ? trim($_GET['op_opciones']) : '';
    
    $params = array(
        0 => $_SESSION['Ses_Emp_Cod'],
        'search' => $search,
        'op_opciones' => $op_opciones
    );
    
    $row_count = $obBD_con1->getRowConsulta(3, $params, $obBD_conexion);
    $total_records = isset($row_count['total']) ? (int)$row_count['total'] : 0;
    
    $pagination = pages($total_records, $page, $rows);
    $response = $pagination['data'];
    
    if ($total_records > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(3, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
    exit;
}

// Obtener registros SubGrid
if (isset($_GET['getSubGridHorometrosAjax'])) {
    $resp = array('success' => true, 'rows' => array());
    $Veh_Cod = isset($_GET['Veh_Cod']) ? (int)$_GET['Veh_Cod'] : 0;
    $Cho_Cod = isset($_GET['Cho_Cod']) ? (int)$_GET['Cho_Cod'] : 0;
    $Hor_Fec = isset($_GET['Hor_Fec']) ? trim($_GET['Hor_Fec']) : '';
    $Hor_Tur = isset($_GET['Hor_Tur']) ? trim($_GET['Hor_Tur']) : '';

    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $Hor_Fec)) {
        $parts = explode('/', $Hor_Fec);
        $Hor_Fec = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }

    if ($Veh_Cod > 0 && $Cho_Cod > 0 && !empty($Hor_Fec)) {
        $resp['rows'] = $obBD_con1->getArrayConsulta(13, array(
            'Veh_Cod' => $Veh_Cod,
            'Cho_Cod' => $Cho_Cod,
            'Hor_Fec' => $Hor_Fec
        ), $obBD_conexion);
        $obBD_con1->utf8_change_param($resp['rows']);
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Guardar Horómetro (AJAX) - Soporta Insert (Ini) y Update (Fin)
if (isset($_POST['saveHorometroAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Hor_Cod = isset($_POST['Hor_Cod']) ? (int)$_POST['Hor_Cod'] : 0;
        $Veh_Cod = isset($_POST['Veh_Cod']) ? (int)$_POST['Veh_Cod'] : 0;
        $Cho_Cod = isset($_POST['Cho_Cod']) ? (int)$_POST['Cho_Cod'] : 0;
        $Hor_Fec = isset($_POST['Hor_Fec']) ? trim($_POST['Hor_Fec']) : '';
        $Hor_Ini = isset($_POST['Hor_Ini']) ? trim($_POST['Hor_Ini']) : '';
        $Hor_Fin = isset($_POST['Hor_Fin']) ? trim($_POST['Hor_Fin']) : '0';
        $Hor_Set = isset($_POST['Hor_Set']) ? trim($_POST['Hor_Set']) : '';
        $Hor_Obs = isset($_POST['Hor_Obs']) ? trim($_POST['Hor_Obs']) : '';
        
        $Hor_Hini = date('Y-m-d H:i:s'); // Fecha y Hora inicial automática (DATETIME)
        $Hor_Hfin = date('Y-m-d H:i:s'); // Fecha y Hora final automática (DATETIME)

        if (empty($Veh_Cod) || empty($Cho_Cod) || empty($Hor_Fec) || empty($Hor_Ini) || empty($Hor_Set)) {
            throw new Exception('Todos los campos marcados con asterisco (*) son obligatorios.');
        }

        $hor_ini_val = (float)$Hor_Ini;
        $hor_fin_val = (float)$Hor_Fin;
        $horas_calculadas = 0;

        if ($Hor_Cod > 0 && $hor_fin_val > 0) {
            if ($hor_fin_val < $hor_ini_val) {
                throw new Exception('El horómetro final no puede ser menor al horómetro inicial.');
            }
            $horas_calculadas = $hor_fin_val - $hor_ini_val;
        }

        // Formatear fecha para MySQL
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $Hor_Fec)) {
            $parts = explode('/', $Hor_Fec);
            $Hor_Fec = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }

        // Obtener el numero de registro del dia para la maquina y chofer
        $count_n = 1;
        $row_c = $obBD_con1->getRowConsulta(14, array('Veh_Cod' => $Veh_Cod, 'Cho_Cod' => $Cho_Cod, 'Hor_Fec' => $Hor_Fec, 'Hor_Cod' => $Hor_Cod), $obBD_conexion);
        if ($Hor_Cod > 0) {
            $count_n = isset($row_c['total']) ? (int)$row_c['total'] : 1;
        } else {
            $count_n = isset($row_c['total']) ? (int)$row_c['total'] + 1 : 1;
        }
        $fecha_str = date('Ymd'); // "fechaactual"

        $ruta_destino = "../RECURSOS/horometro/";
        if (!file_exists($ruta_destino)) {
            @mkdir($ruta_destino, 0777, true);
        }

        // Subida de imagen Inicial (opcional)
        $hor_img_ini_path = '';
        if (isset($_FILES['Hor_Img_Ini']) && $_FILES['Hor_Img_Ini']['error'] == 0) {
            $ext = pathinfo($_FILES['Hor_Img_Ini']['name'], PATHINFO_EXTENSION);
            $hor_img_ini_path = $fecha_str . '_horom_ini_' . $count_n . '.' . $ext;
            move_uploaded_file($_FILES['Hor_Img_Ini']['tmp_name'], $ruta_destino . $hor_img_ini_path);
        }

        // Subida de imagen Final (opcional)
        $hor_img_fin_path = '';
        if (isset($_FILES['Hor_Img_Fin']) && $_FILES['Hor_Img_Fin']['error'] == 0) {
            $ext = pathinfo($_FILES['Hor_Img_Fin']['name'], PATHINFO_EXTENSION);
            $hor_img_fin_path = $fecha_str . '_horom_fin_' . $count_n . '.' . $ext;
            move_uploaded_file($_FILES['Hor_Img_Fin']['tmp_name'], $ruta_destino . $hor_img_fin_path);
        }

        if ($Hor_Cod == 0) {
            // INSERTAR (Solo Hor_Ini, Hor_Fin=0)
            $params = array(
                0 => $_SESSION['Ses_Usu_Cod'],
                1 => $Veh_Cod,
                2 => $Cho_Cod,
                3 => $Hor_Fec,
                4 => $hor_ini_val,
                5 => 0, // Fin es 0 al inicio
                6 => $Hor_Set,
                7 => $Hor_Obs,
                8 => $Hor_Hini, // Hora Inicial del sistema
                9 => $hor_img_ini_path,
                10 => '', // Hora Final inicializa vacío
                11 => '' // Sin foto final al iniciar
            );
            $obBD_con1->operacionobBD(4, $params, $obBD_conexion);
            if ($obBD_con1->Error != 0) throw new Exception("Error BD (Inserción): " . $obBD_con1->getMsgError());
            $Hor_Cod_New = $obBD_con1->insercionid($obBD_conexion);
            $resp['message'] = 'Horómetro inicial guardado exitosamente.';
        } else {
            // ACTUALIZAR (Añadir Hor_Fin, etc)
            $params_upd = array(
                0 => $Hor_Cod,
                1 => $hor_ini_val,
                2 => $hor_fin_val,
                3 => $Hor_Hfin, // Hora final del sistema (solo se guardará si es cierre)
                4 => $Hor_Set,
                5 => $Hor_Obs,
                6 => $hor_img_ini_path,
                7 => $hor_img_fin_path
            );
            $obBD_con1->operacionobBD(12, $params_upd, $obBD_conexion);
            if ($obBD_con1->Error != 0) throw new Exception("Error BD (Actualización): " . $obBD_con1->getMsgError());
            $resp['message'] = 'Horómetro final y registro actualizado exitosamente.';
        }

        $resp['success'] = true;
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}

// Cambiar de Estado (Aprobación / Revisión / Rechazo / Anulación)
if (isset($_POST['changeEstadoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Hor_Cod = isset($_POST['Hor_Cod']) ? (int)$_POST['Hor_Cod'] : 0;
        $Estado_Nue = isset($_POST['Hor_Est']) ? trim($_POST['Hor_Est']) : '';
        $Hhi_Obs = isset($_POST['Hhi_Obs']) ? trim($_POST['Hhi_Obs']) : '';

        if (empty($Hor_Cod) || empty($Estado_Nue)) {
            throw new Exception('Parámetros de estado no válidos.');
        }

        // Obtener estado anterior
        $row_act = $obBD_con1->getArrayConsultaSql("SELECT Hor_Est, Veh_Cod, Hor_Fin FROM maquinaria_horometro WHERE Hor_Cod = $Hor_Cod LIMIT 1", $obBD_conexion);
        if (empty($row_act)) {
            throw new Exception('Registro de horómetro no encontrado.');
        }
        $Estado_Ant = $row_act[0]['Hor_Est'];
        $Veh_Cod = (int)$row_act[0]['Veh_Cod'];
        $Hor_Fin = (float)$row_act[0]['Hor_Fin'];

        // Si ya está aprobado y no es Administrador, bloquear
        if ($Estado_Ant == 'A' && $user_role !== 'ADM') {
            throw new Exception('No tiene permisos para modificar un registro que ya ha sido Aprobado.');
        }

        // Actualizar estado del registro
        $obBD_con1->operacionobBD(5, array($Hor_Cod, $Estado_Nue), $obBD_conexion);
        if ($obBD_con1->Error != 0) throw new Exception("Error BD (Estado): " . $obBD_con1->getMsgError());

        // Si se aprueba, revisar si supera límites de mantenimiento preventivo
        if ($Estado_Nue === 'A') {
            // Verificar si el vehículo tiene configuración de mantenimiento
            $row_conf = $obBD_con1->getArrayConsultaSql("SELECT Cma_Hrs_Ult, Cma_Hrs_Fco FROM maquinaria_config_mantenimiento WHERE Veh_Cod = $Veh_Cod AND Cma_Est = 'A' LIMIT 1", $obBD_conexion);
            if (!empty($row_conf)) {
                $frecuencia = (float)$row_conf[0]['Cma_Hrs_Fco'];
                $ultimo_mant = (float)$row_conf[0]['Cma_Hrs_Ult'];
                $proximo = $ultimo_mant + $frecuencia;
                
                if ($Hor_Fin >= $proximo) {
                    $resp['mantenimiento_warning'] = true;
                    $resp['mantenimiento_msg'] = '¡Alerta! La máquina requiere mantenimiento preventivo inmediato ya que superó la lectura de ' . $proximo . ' horas.';
                }
            }
        }

        $resp['success'] = true;
        $resp['message'] = 'Estado actualizado exitosamente.';
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}

// Cargar Historial de Mantenimientos por Máquina (AJAX)
if (isset($_GET['getHistorialMantenimientoAjax'])) {
    $Veh_Cod = isset($_GET['Veh_Cod']) ? (int)$_GET['Veh_Cod'] : 0;
    $resp = array('success' => true, 'rows' => array(), 'config' => null);
    if ($Veh_Cod > 0) {
        $resp['rows'] = $obBD_con1->getArrayConsulta(11, array($Veh_Cod), $obBD_conexion);
        $obBD_con1->utf8_change_param($resp['rows']);
        
        $row_conf = $obBD_con1->getArrayConsultaSql("SELECT Cma_Hrs_Fco, Cma_Hrs_Ult FROM maquinaria_config_mantenimiento WHERE Veh_Cod = $Veh_Cod LIMIT 1", $obBD_conexion);
        if (!empty($row_conf)) {
            $resp['config'] = array(
                'Cma_Hrs_Fco' => (float)$row_conf[0]['Cma_Hrs_Fco'],
                'Cma_Hrs_Ult' => (float)$row_conf[0]['Cma_Hrs_Ult']
            );
        }
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Registrar Mantenimiento Preventivo (AJAX)
if (isset($_POST['saveMantenimientoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Veh_Cod = isset($_POST['Veh_Cod_Mant']) ? (int)$_POST['Veh_Cod_Mant'] : 0;
        $Hma_Fec = isset($_POST['Hma_Fec']) ? trim($_POST['Hma_Fec']) : '';
        $Hma_Hor = isset($_POST['Hma_Hor']) ? trim($_POST['Hma_Hor']) : '';
        $Hma_Det = isset($_POST['Hma_Det']) ? trim($_POST['Hma_Det']) : '';
        $Hma_Res = isset($_POST['Hma_Res']) ? trim($_POST['Hma_Res']) : '';

        if (empty($Veh_Cod) || empty($Hma_Fec) || empty($Hma_Hor) || empty($Hma_Det) || empty($Hma_Res)) {
            throw new Exception('Todos los campos son obligatorios para registrar un mantenimiento.');
        }

        // Formatear fecha para MySQL
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $Hma_Fec)) {
            $parts = explode('/', $Hma_Fec);
            $Hma_Fec = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }

        // 1. Guardar en bitácora de historial
        $obBD_con1->operacionobBD(9, array($Veh_Cod, $_SESSION['Ses_Usu_Cod'], $Hma_Fec, (float)$Hma_Hor, $Hma_Det, $Hma_Res), $obBD_conexion);
        if ($obBD_con1->Error != 0) throw new Exception("Error BD (Mant Historial): " . $obBD_con1->getMsgError());

        // 2. Actualizar configuración para marcar este como el último mantenimiento realizado
        $row_conf = $obBD_con1->getArrayConsultaSql("SELECT Cma_Cod FROM maquinaria_config_mantenimiento WHERE Veh_Cod = $Veh_Cod LIMIT 1", $obBD_conexion);
        if (!empty($row_conf)) {
            // Existe -> Actualizar Cma_Hrs_Ult
            $obBD_con1->operacionobBD(8, array($Veh_Cod, (float)$_POST['Cma_Hrs_Fco'], (float)$Hma_Hor, 'update'), $obBD_conexion);
            if ($obBD_con1->Error != 0) throw new Exception("Error BD (Mant Update): " . $obBD_con1->getMsgError());
        } else {
            // No existe -> Crear nueva configuración con la frecuencia suministrada
            $frecuencia = isset($_POST['Cma_Hrs_Fco']) ? (float)$_POST['Cma_Hrs_Fco'] : 250.00;
            $obBD_con1->operacionobBD(8, array($Veh_Cod, $frecuencia, (float)$Hma_Hor, 'insert'), $obBD_conexion);
            if ($obBD_con1->Error != 0) throw new Exception("Error BD (Mant Insert): " . $obBD_con1->getMsgError());
        }

        $resp['success'] = true;
        $resp['message'] = 'Historial de mantenimiento preventivo registrado exitosamente. El horómetro de alerta se ha actualizado.';
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}

// Guardar o Actualizar Configuración de Frecuencia de Mantenimiento (AJAX)
if (isset($_POST['saveConfigMantenimientoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Veh_Cod = isset($_POST['Veh_Cod_Conf']) ? (int)$_POST['Veh_Cod_Conf'] : 0;
        $Cma_Hrs_Fco = isset($_POST['Cma_Hrs_Fco']) ? (float)$_POST['Cma_Hrs_Fco'] : 0.00;
        $Cma_Hrs_Ult = isset($_POST['Cma_Hrs_Ult']) ? (float)$_POST['Cma_Hrs_Ult'] : 0.00;

        if ($Veh_Cod <= 0 || $Cma_Hrs_Fco <= 0) {
            throw new Exception('Ingrese una frecuencia válida de horas para el mantenimiento.');
        }

        $row_conf = $obBD_con1->getArrayConsultaSql("SELECT Cma_Cod FROM maquinaria_config_mantenimiento WHERE Veh_Cod = $Veh_Cod LIMIT 1", $obBD_conexion);
        if (!empty($row_conf)) {
            $obBD_con1->operacionobBD(8, array($Veh_Cod, $Cma_Hrs_Fco, $Cma_Hrs_Ult, 'update'), $obBD_conexion);
            if ($obBD_con1->Error != 0) throw new Exception("Error BD (Conf Update): " . $obBD_con1->getMsgError());
        } else {
            $obBD_con1->operacionobBD(8, array($Veh_Cod, $Cma_Hrs_Fco, $Cma_Hrs_Ult, 'insert'), $obBD_conexion);
            if ($obBD_con1->Error != 0) throw new Exception("Error BD (Conf Insert): " . $obBD_con1->getMsgError());
        }

        $resp['success'] = true;
        $resp['message'] = 'Frecuencia de mantenimiento guardada de forma exitosa.';
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}

// Cargar Alertas Generales de Mantenimiento Preventivo (AJAX)
if (isset($_GET['listAlertasMantenimientoAjax'])) {
    $rows = $obBD_con1->getArrayConsulta(10, array($_SESSION['Ses_Emp_Cod']), $obBD_conexion);
    $obBD_con1->utf8_change_param($rows);
    $obBD_con1->echoJson($rows);
    exit;
}
// Cargar Evidencias de Horómetro (AJAX)
if (isset($_GET['getEvidenciasAjax'])) {
    $Hor_Cod = isset($_GET['Hor_Cod']) ? (int)$_GET['Hor_Cod'] : 0;
    $resp = array('success' => false, 'Hor_Img_Ini' => '', 'Hor_Img_Fin' => '');
    if ($Hor_Cod > 0) {
        $row = $obBD_con1->getArrayConsultaSql("SELECT Hor_Img_Ini, Hor_Img_Fin FROM maquinaria_horometro WHERE Hor_Cod = $Hor_Cod LIMIT 1", $obBD_conexion);
        if (!empty($row)) {
            $resp['Hor_Img_Ini'] = $row[0]['Hor_Img_Ini'];
            $resp['Hor_Img_Fin'] = $row[0]['Hor_Img_Fin'];
            $resp['success'] = true;
        } else {
            $resp['message'] = "Registro no encontrado.";
        }
    }
    $obBD_con1->echoJson($resp);
    exit;
}
?>
<!DOCTYPE HTML>
<HTML>
<HEAD>
    <TITLE>Gestión de Horómetro y Mantenimiento de Maquinaria [EXA]</TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script language="javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
    <link rel="stylesheet" type="text/css" href="../RECURSOS/maquinaria_horometro.css" />
    <script>
        var user_role = '<?php echo $user_role; ?>';
    </script>
    <style>
        /* Asegurar que los mensajes de error/alerta aparezcan por delante de los modales (z-index de Bootstrap es 1050) */
        .ui-dialog, .ui-widget-overlay, .jconfirm, .sweet-alert, .swal2-container, .modal-alert {
            z-index: 1060 !important;
        }
        .jconfirm-bg, .swal2-overlay, .ui-widget-overlay {
            z-index: 1059 !important;
        }
    </style>
</HEAD>
<BODY>
    <div class="panel panel-default panel-main">
        <div class="panel-heading">
            <span class="glyphicon glyphicon-tasks"></span> » Gestión de Horómetro y Mantenimiento de Maquinaria
        </div>
        <div class="panel-body exa-body">

            <!-- ==================== AMBIENTE 1: DASHBOARD, GRID Y CONSULTA ==================== -->
            <div id="divListado">
                
                <!-- Tarjetas del Dashboard -->
                <div class="dashboard-metrics-container">
                    <div class="metric-card metric-pending">
                        <div class="metric-icon-wrapper"><i class="glyphicon glyphicon-hourglass"></i></div>
                        <div class="metric-info">
                            <span class="metric-title">Lecturas Pendientes</span>
                            <span class="metric-value" id="dash_pendientes">0</span>
                        </div>
                    </div>
                    <div class="metric-card metric-hours">
                        <div class="metric-icon-wrapper"><i class="glyphicon glyphicon-time"></i></div>
                        <div class="metric-info">
                            <span class="metric-title">Horas Trabajadas (Mes)</span>
                            <span class="metric-value" id="dash_horas_mes">0.00 h</span>
                        </div>
                    </div>
                    <div class="metric-card metric-alert" style="cursor:pointer;" onclick="abrirModalAlertas();">
                        <div class="metric-icon-wrapper"><i class="glyphicon glyphicon-warning-sign"></i></div>
                        <div class="metric-info">
                            <span class="metric-title">Máquinas en Alerta</span>
                            <span class="metric-value" id="dash_alertas_mant">0</span>
                        </div>
                    </div>
                </div>

                <!-- Pestañas de Consulta -->
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="active">
                            <a href="#tabListHorometros" role="tab" data-toggle="tab">
                                <i class="glyphicon glyphicon-list-alt" style="margin-right: 8px;"></i>Lecturas de Horómetro
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content tab-content-custom">
                        <div role="tabpanel" class="tab-pane active" id="tabListHorometros">
                            <div class="form-inline" style="margin-bottom: 15px;">
                                <label class="control-label" style="font-weight: bold; margin-right:5px;">Periodo:</label>
                                <select id="tipoFiltroFecha" class="form-control input-sm" style="margin-right: 5px;" onchange="ajustarFiltroFecha(this.value);">
                                    <option value="T">Todo el tiempo</option>
                                    <option value="D">Por Día</option>
                                    <option value="S">Semanal</option>
                                    <option value="Q">Quincenal</option>
                                    <option value="M">Mensual</option>
                                </select>
                                
                                <input type="date" id="filtroFechaDia" class="form-control input-sm" style="margin-right: 5px; display:none;">
                                <input type="week" id="filtroFechaSemana" class="form-control input-sm" style="margin-right: 5px; display:none;">
                                <input type="month" id="filtroFechaMes" class="form-control input-sm" style="margin-right: 5px; display:none;">
                                <select id="filtroQuincena" class="form-control input-sm" style="margin-right: 15px; display:none;">
                                    <option value="1">1ra Quincena (1 - 15)</option>
                                    <option value="2">2da Quincena (16 - fin)</option>
                                </select>

                                <label class="control-label" style="font-weight: bold; margin-right:5px; margin-left:10px;">Buscar:</label>
                                <select id="opHorometro" class="form-control input-sm" style="margin-right: 5px;" onchange="ajustarPlaceholderBusqueda(this.value);">
                                    <option value="p">Placa de Máquina</option>
                                    <option value="o">Operador (Nombre/Cédula)</option>
                                </select>
                                
                                <input type="text" id="searchHorometro" class="form-control input-sm" placeholder="Buscar placa..." style="margin-right: 5px; width: 150px;">

                                <button type="button" class="btn btn-sm btn-primary" onclick="reloadGridHorometros();"><i class="fa fa-search"></i> Buscar</button>
                                <button type="button" class="btn btn-sm btn-exa-primary" onclick="exportarExcel();" title="Exportar a Excel"><i class="fa fa-file-excel-o"></i> Excel</button>
                                
                                <button type="button" class="btn btn-sm btn-exa-success" style="float: right;" onclick="mostrarFormulario();">
                                    <i class="glyphicon glyphicon-plus"></i> Registrar Lectura Horómetro
                                </button>
                            </div>
                            <table id="gridHorometros"></table>
                            <div id="pagerHorometros"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== AMBIENTE 2: REGISTRO / FORMULARIO ==================== -->
            <div id="divFormulario" style="display:none;">
                
                <!-- HEADER DE CONTEXTO DEL TURNO -->
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <form id="formContexto" class="form-horizontal" onsubmit="return false;">
                        <div class="row">
                            <div class="col-sm-3">
                                <label class="control-label" style="font-size:12px; margin-bottom:5px; color:#475569;">Máquina / Vehículo:</label>
                                <select id="Veh_Cod" name="Veh_Cod" class="form-control" onchange="limpiarSubgrid();">
                                    <option value="">Seleccione Máquina...</option>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <label class="control-label" style="font-size:12px; margin-bottom:5px; color:#475569;">Operador:</label>
                                <select id="Cho_Cod" name="Cho_Cod" class="form-control" onchange="limpiarSubgrid();">
                                    <option value="">Seleccione Operador...</option>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <label class="control-label" style="font-size:12px; margin-bottom:5px; color:#475569;">Fecha del Turno:</label>
                                <input id="Hor_Fec" name="Hor_Fec" type="text" class="form-control datepicker" placeholder="dd/mm/aaaa" onchange="limpiarSubgrid();" />
                            </div>
                            <div class="col-sm-2" style="padding-top: 25px;">
                                <button type="button" class="btn btn-primary btn-block" onclick="cargarJornada();" style="height:30px; padding:4px; font-weight:bold;"><i class="glyphicon glyphicon-search"></i> Cargar</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- SUBGRID DE REGISTROS DE LA JORNADA -->
                <div id="panelJornada" style="display:none; border: 1px solid #e2e8f0; border-radius: 8px; overflow:hidden;">
                    <div style="background: #334a5f; color: #fff; padding: 10px 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="glyphicon glyphicon-time"></i> Bitácora de Horómetros de la Jornada</span>
                        <button type="button" class="btn btn-xs btn-success" style="font-weight:bold; border-radius:4px;" onclick="abrirModalRegistro(0);">
                            <i class="glyphicon glyphicon-plus"></i> Añadir Nuevo Registro
                        </button>
                    </div>
                    <div style="background: #fff; padding: 15px;">
                        <table class="table table-striped table-bordered table-condensed" id="tblJornada" style="font-size:12px; margin-bottom:0;">
                            <thead>
                                <tr style="background:#f1f5f9; color:#334a5f;">
                                    <th>Horómetro Inicial</th>
                                    <th>Evidencia Inicial</th>
                                    <th>Horómetro Final</th>
                                    <th>Evidencia Final</th>
                                    <th>Horas Trab.</th>
                                    <th>Ubicación / Área</th>
                                    <th>Estado</th>
                                    <th style="width:90px; text-align:center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Las filas se cargarán por AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="button-center" style="margin-top: 20px;">
                    <button type="button" class="btn btn-custom btn-default" onclick="mostrarListado();">
                        <i class="glyphicon glyphicon-arrow-left"></i> Volver a Consultas
                    </button>
                </div>

            </div>

        </div>
    </div>

    <!-- ==================== MODAL: FORMULARIO DE INGRESO/EDICION HOROMETRO ==================== -->
    <div class="modal fade" id="modalRegistroHorometro" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document" style="width: 85%; max-width: 700px;">
            <div class="modal-content" style="border-radius:8px; overflow:hidden;">
                <div class="modal-header" style="background:#334a5f; color:#fff;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff; opacity:0.8;">&times;</button>
                    <h4 class="modal-title" style="font-weight:bold; font-size:14px;" id="modalRegistroHorometroTitulo"><i class="glyphicon glyphicon-edit"></i> Editar Horómetro</h4>
                </div>
                <div class="modal-body" style="background:#f8fafc; padding:20px;">
                    <form id="formHorometroModal" class="form-horizontal" onsubmit="return false;" enctype="multipart/form-data">
                        <input type="hidden" id="Hor_Cod_Modal" name="Hor_Cod" value="0" />
                        
                        <!-- Bloque Inicial -->
                        <fieldset class="exa-fieldset" style="background:#fff; border-color:#cbd5e1; border-top: 3px solid #3b82f6;">
                            <legend style="color:#1d4ed8;">Apertura (Horómetro Inicial)</legend>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group" style="margin-bottom:10px;">
                                        <label class="col-sm-5 control-label label-sm">Horómetro Inicial:<span class="text-danger">*</span></label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Ini" name="Hor_Ini" type="text" class="form-control calculo-horas" placeholder="Lectura inicial" onkeypress="return validar_decimal(event);" />
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom:10px;">
                                        <label class="col-sm-5 control-label label-sm">Hora Inicial:</label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Hini" type="text" class="form-control" readonly style="background:#e2e8f0; font-weight:bold; color:#0f172a;" value="Automático" />
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="col-sm-5 control-label label-sm">Ubicación/Área:<span class="text-danger">*</span></label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Set" name="Hor_Set" type="text" class="form-control" placeholder="Área de trabajo" maxlength="50" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 text-center">
                                    <label class="label-sm" style="display:block; text-align:center; margin-bottom:5px;">Evidencia Inicial (Foto):</label>
                                    <input type="file" id="Hor_Img_Ini" name="Hor_Img_Ini" class="form-control" style="height:auto; padding:3px; margin-bottom:5px; font-size:11px;" accept="image/*" />
                                    <div id="preview_ini_container" style="border: 1px dashed #cbd5e1; border-radius:4px; height:80px; display:flex; align-items:center; justify-content:center; background:#f1f5f9;">
                                        <span style="color:#94a3b8; font-size:11px;">(Suba una foto clara)</span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Bloque Final -->
                        <fieldset class="exa-fieldset" id="fsHorometroFinal" style="background:#fff; border-color:#cbd5e1; border-top: 3px solid #10b981; margin-bottom:10px;">
                            <legend style="color:#047857;">Cierre (Horómetro Final)</legend>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group" style="margin-bottom:10px;">
                                        <label class="col-sm-5 control-label label-sm">Horómetro Final:<span class="text-danger">*</span></label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Fin" name="Hor_Fin" type="text" class="form-control calculo-horas" placeholder="Lectura final" onkeypress="return validar_decimal(event);" />
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom:10px;">
                                        <label class="col-sm-5 control-label label-sm">Hora Final:</label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Hfin" type="text" class="form-control" readonly style="background:#e2e8f0; font-weight:bold; color:#0f172a;" value="Automático" />
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="col-sm-5 control-label label-sm">Horas Calculadas:</label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Hrs" type="text" class="form-control" readonly style="background:#e2e8f0; font-weight:bold; color: #0f172a;" value="0.00" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 text-center">
                                    <label class="label-sm" style="display:block; text-align:center; margin-bottom:5px;">Evidencia Final (Foto):</label>
                                    <input type="file" id="Hor_Img_Fin" name="Hor_Img_Fin" class="form-control" style="height:auto; padding:3px; margin-bottom:5px; font-size:11px;" accept="image/*" />
                                    <div id="preview_fin_container" style="border: 1px dashed #cbd5e1; border-radius:4px; height:80px; display:flex; align-items:center; justify-content:center; background:#f1f5f9;">
                                        <span style="color:#94a3b8; font-size:11px;">(Suba una foto clara)</span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Observaciones Adicionales -->
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="col-sm-2 control-label label-sm" style="text-align:left; padding-left:22px;">Observaciones:</label>
                            <div class="col-sm-10" style="padding-right:22px;">
                                <textarea id="Hor_Obs" name="Hor_Obs" class="form-control" style="height: 50px; resize:none;" placeholder="Detalles o eventualidades adicionales..."></textarea>
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer" style="background:#fff; border-top:1px solid #e2e8f0;">
                    <button type="button" id="btnGuardarHorometro" class="btn btn-sm btn-primary" onclick="guardarHorometroModal();" style="font-weight:bold; padding:6px 20px;"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar Registro</button>
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cerrar</button>
                </div>
            </div>
        </div>
    </div>

        </div>
    </div>

    <!-- ==================== MODAL 1: HISTORIAL Y CONFIGURACIÓN MANTENIMIENTO PREVENTIVO ==================== -->
    <div class="modal fade" id="modalMantenimiento" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="width: 85%; max-width: 750px;">
            <div class="modal-content" style="border-radius:8px; overflow:hidden;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                    <h4 class="modal-title modal-title-custom"><i class="glyphicon glyphicon-wrench"></i> Control de Mantenimiento Preventivo: <span id="modal_maquina_titulo"></span></h4>
                </div>
                <div class="modal-body" style="background: #f8fafc; padding: 20px;">
                    
                    <!-- Pestañas del Modal -->
                    <div class="nav-tabs-custom" style="margin-bottom: 15px;">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="active"><a href="#tabMantHistorial" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-time"></i> Historial de Trabajos</a></li>
                            <li id="tabHeaderRegistrarMant" style="display:none;"><a href="#tabMantForm" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-plus"></i> Registrar Mantenimiento</a></li>
                            <li id="tabHeaderConfigAlerts" style="display:none;"><a href="#tabMantConfig" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-cog"></i> Configurar Frecuencia</a></li>
                        </ul>
                        
                        <div class="tab-content" style="padding: 15px 0; background:transparent; border:none;">
                            
                            <!-- Modal Tab 1: Historial de mantenimientos -->
                            <div role="tabpanel" class="tab-pane active" id="tabMantHistorial">
                                <div id="alerta_proximo_mantenimiento" class="alert alert-warning" style="display:none; font-size:12px; font-weight:bold; margin-bottom:12px;"></div>
                                <div style="max-height: 250px; overflow-y: auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px;">
                                    <table class="table table-striped table-condensed" id="tblHistorialMantenimiento" style="margin-bottom:0; font-size:12px;">
                                        <thead>
                                            <tr style="background:#475569; color:#fff;">
                                                <th style="padding: 8px;">Fecha</th>
                                                <th>Horómetro</th>
                                                <th>Detalle del Trabajo</th>
                                                <th>Responsable</th>
                                                <th>Registrado Por</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="5" class="text-center text-muted" style="padding: 20px;">Cargando historial de trabajos...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Modal Tab 2: Registrar Mantenimiento Realizado -->
                            <div role="tabpanel" class="tab-pane" id="tabMantForm">
                                <form id="formRegistrarMantenimiento" class="form-horizontal" onsubmit="return false;">
                                    <input type="hidden" id="Veh_Cod_Mant" name="Veh_Cod_Mant" value="0" />
                                    <!-- Mandamos la frecuencia configurada por si se necesita actualizar Cma_Hrs_Ult -->
                                    <input type="hidden" id="Cma_Hrs_Fco_Hidden" name="Cma_Hrs_Fco" value="250" />
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="col-sm-4 control-label label-sm">Fecha Trabajo:<span class="text-danger">*</span></label>
                                                <div class="col-sm-8">
                                                    <input type="text" id="Hma_Fec" name="Hma_Fec" class="form-control datepicker" placeholder="dd/mm/aaaa" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="col-sm-4 control-label label-sm">Horómetro:<span class="text-danger">*</span></label>
                                                <div class="col-sm-8">
                                                    <input type="text" id="Hma_Hor" name="Hma_Hor" class="form-control" placeholder="Horómetro de trabajo" onkeypress="return validar_decimal(event);" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label label-sm">Responsable/Taller:<span class="text-danger">*</span></label>
                                                <div class="col-sm-10">
                                                    <input type="text" id="Hma_Res" name="Hma_Res" class="form-control" placeholder="Persona o taller encargado" maxlength="100" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group" style="margin-bottom:0;">
                                                <label class="col-sm-2 control-label label-sm">Detalle Trabajo:<span class="text-danger">*</span></label>
                                                <div class="col-sm-10">
                                                    <textarea id="Hma_Det" name="Hma_Det" class="form-control" style="height: 60px; resize:none;" placeholder="Escriba el detalle de los preventivos o reparaciones hechas..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="button-center" style="margin-top:12px; padding-top:10px;">
                                        <button type="button" class="btn btn-sm btn-exa-success" onclick="guardarMantenimiento();"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar Mantenimiento</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Modal Tab 3: Configurar Frecuencias -->
                            <div role="tabpanel" class="tab-pane" id="tabMantConfig">
                                <form id="formConfigMantenimiento" class="form-horizontal" onsubmit="return false;">
                                    <input type="hidden" id="Veh_Cod_Conf" name="Veh_Cod_Conf" value="0" />
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="col-sm-5 control-label label-sm">Frecuencia Alertas (Horas):<span class="text-danger">*</span></label>
                                                <div class="col-sm-7">
                                                    <input type="text" id="Cma_Hrs_Fco" name="Cma_Hrs_Fco" class="form-control" placeholder="Ej: 250" onkeypress="return validar_numeric(event);" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="col-sm-5 control-label label-sm">Último Mantenimiento (Horómetro):</label>
                                                <div class="col-sm-7">
                                                    <input type="text" id="Cma_Hrs_Ult" name="Cma_Hrs_Ult" class="form-control" placeholder="0.00" onkeypress="return validar_decimal(event);" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="button-center" style="margin-top:12px; padding-top:10px;">
                                        <button type="button" class="btn btn-sm btn-exa-primary" onclick="guardarConfigMantenimiento();"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar Configuración</button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f8fafc;">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL 2: TABLA DE ALERTAS GENERAL DE MANTENIMIENTO PREVENTIVO ==================== -->
    <div class="modal fade" id="modalAlertasMantenimiento" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="width: 80%; max-width: 650px;">
            <div class="modal-content" style="border-radius:8px; overflow:hidden;">
                <div class="modal-header" style="background: #fee2e2; border-bottom: 1px solid #fca5a5;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                    <h4 class="modal-title modal-title-custom" style="color:#b91c1c;"><i class="glyphicon glyphicon-warning-sign"></i> Alertas Automáticas de Mantenimiento Preventivo</h4>
                </div>
                <div class="modal-body" style="background:#fff; padding: 20px;">
                    <p style="font-size:12px; color:#475569; margin-bottom:12px; font-weight:bold;">Las siguientes máquinas han alcanzado o superado su frecuencia de mantenimiento preventivo configurada:</p>
                    <div style="max-height: 250px; overflow-y:auto; border: 1px solid #e2e8f0; border-radius:6px;">
                        <table class="table table-striped table-condensed" id="tblMaquinasAlerta" style="margin-bottom:0; font-size:12px;">
                            <thead>
                                <tr style="background:#f1f5f9; color:#1e293b;">
                                    <th style="padding:8px;">Placa</th>
                                    <th>Máquina</th>
                                    <th>Frecuencia (h)</th>
                                    <th>Últ. Mant. (h)</th>
                                    <th>Lectura Actual (h)</th>
                                    <th style="color:#b91c1c;">Exceso/Faltante (h)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="6" class="text-center text-muted" style="padding:20px;">No hay máquinas con alertas activas.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL 3: AUDITORÍA / HISTORIAL DE ESTADOS ==================== -->
    <div class="modal fade" id="modalAuditoria" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="width: 85%; max-width: 650px;">
            <div class="modal-content" style="border-radius:8px; overflow:hidden;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                    <h4 class="modal-title modal-title-custom"><i class="glyphicon glyphicon-time"></i> Historial de Cambios y Auditoría de Estados</h4>
                </div>
                <div class="modal-body" style="background:#f8fafc; padding:20px;">
                    <div style="max-height: 250px; overflow-y:auto; border: 1px solid #cbd5e1; border-radius:6px; background:#fff;">
                        <table class="table table-striped table-condensed" id="tblAuditoria" style="margin-bottom:0; font-size:11.5px;">
                            <thead>
                                <tr style="background:#475569; color:#fff;">
                                    <th style="padding: 8px;">Fecha/Hora</th>
                                    <th>Estado Anterior</th>
                                    <th>Nuevo Estado</th>
                                    <th>Justificación / Observaciones</th>
                                    <th>Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center text-muted" style="padding:20px;">Cargando historial de auditoría...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cargador Visual / Loader -->
    <div id="loader" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 9999; text-align: center; padding-top: 20%;">
        <div style="display: inline-block; padding: 25px 35px; background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
            <i class="fa fa-spinner fa-spin fa-3x fa-fw" style="color: #334a5f;"></i>
            <div style="margin-top: 15px; font-weight: bold; color: #334a5f; font-size: 14px;">Procesando solicitud...</div>
        </div>
    </div>

    <!-- Modal Cambio de Estado (Aprobar/Rechazar/Revisar/Anular con observaciones) -->
    <div class="modal fade" id="modalCambioEstado" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="width: 85%; max-width: 480px;">
            <div class="modal-content" style="border-radius:8px; overflow:hidden;">
                <div class="modal-header" id="modalCambioEstadoHeader">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                    <h4 class="modal-title modal-title-custom" id="modalCambioEstadoTitulo">Cambio de Estado</h4>
                </div>
                <div class="modal-body" style="padding:20px;">
                    <form id="formCambioEstado" onsubmit="return false;">
                        <input type="hidden" id="Hor_Cod_Est" value="0" />
                        <input type="hidden" id="Hor_Est_Nue" value="" />
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-weight:bold; color:#475569; font-size:12px; margin-bottom:6px;" id="lblJustificacion">Observaciones / Justificación:<span class="text-danger">*</span></label>
                            <textarea id="Hhi_Obs" class="form-control" style="height: 80px; resize:none;" placeholder="Escriba el motivo de la acción..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="background:#f8fafc;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="guardarCambioEstado();"><span class="glyphicon glyphicon-floppy-disk"></span> Aplicar Cambio</button>
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Visor de Evidencias -->
    <div id="modalVisorEvidencia" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal-header-custom" style="background:#0f172a; color:#fff; padding:10px 15px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff; opacity:0.8;">&times;</button>
                    <h4 class="modal-title modal-title-custom" id="modalVisorEvidenciaTitulo"><i class="glyphicon glyphicon-picture"></i> Evidencias Fotográficas</h4>
                </div>
                <div class="modal-body" style="padding:20px; text-align:center; background:#f8fafc;">
                    <div id="visor_loader" style="display:none; color:#64748b; margin-bottom:15px;"><i class="glyphicon glyphicon-refresh fast-spin"></i> Cargando imágenes desde el servidor...</div>
                    <div class="row">
                        <div class="col-sm-6">
                            <h5 style="font-weight:bold; color:#334155; margin-bottom:10px;">Evidencia Inicial</h5>
                            <div id="visor_img_ini" style="width:100%; height:200px; background:#e2e8f0; display:flex; align-items:center; justify-content:center; border:1px solid #cbd5e1; border-radius:4px; overflow:hidden;"></div>
                        </div>
                        <div class="col-sm-6">
                            <h5 style="font-weight:bold; color:#334155; margin-bottom:10px;">Evidencia Final</h5>
                            <div id="visor_img_fin" style="width:100%; height:200px; background:#e2e8f0; display:flex; align-items:center; justify-content:center; border:1px solid #cbd5e1; border-radius:4px; overflow:hidden;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f1f5f9; padding:10px;">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cerrar Visor</button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="../VALIDACIONES/man_val_alt_maquinaria_horometro.js?v=7"></script>
</BODY>
</HTML>
