<?php
// SECCION PARA IMPORTAR RECURSOS
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../LOGICA/man_log_maquinaria_dispensador.php');


// INICIAMOS LA CONEXION A LA BASE DE DATOS
$obBD_conexion = new Class_Log_Conexion_Maquinaria_Dispensador($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Maquinaria_Dispensador;


// ======================================================================
// MANEJO DE PETICIONES AJAX - FASE 6 (DASHBOARD)
// ======================================================================
if (isset($_POST['getDashboardAjax'])) {
    $fecha_ini = isset($_POST['fecha_ini']) ? $_POST['fecha_ini'] : date('Y-m-01');
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : date('Y-m-t');
    $dispensador = isset($_POST['dis_cod']) ? $_POST['dis_cod'] : '';

    $response = array(
        'success' => true,
        'general' => array(
            'total_dispensadores' => 0,
            'existencia_total' => 0,
            'ultimo_cierre' => '-'
        ),
        'movimientos' => array(
            'ingresos_mes' => 0,
            'despachos_mes' => 0,
            'consumo_dia' => 0,
            'in_dia' => 0,
            'ic_dia' => 0,
            'sc_dia' => 0
        ),
        'cierre' => array(
            'Cie_Fec' => '-',
            'Cie_Estado' => '-',
            'Cie_Dif' => 0
        ),
        'dispensadores' => array(),
        'grafico' => array(),
        'top_maq' => array(),
        'alertas' => array()
    );

    $emp_cod = $_SESSION['Ses_Emp_Cod'];
    $f_hoy = date('Y-m-d');

    // Resumen General (22)
    $res22 = $obBD_con1->getArrayConsulta(22, array($emp_cod), $obBD_conexion);
    if ($res22 && count($res22) > 0) {
        $response['general']['total_dispensadores'] = $res22[0]['total_dispensadores'];
        $response['general']['existencia_total'] = $res22[0]['existencia_total'];
        $response['general']['ultimo_cierre'] = $res22[0]['ultimo_cierre'];
    }

    // Movimientos (23)
    $res23 = $obBD_con1->getArrayConsulta(23, array($emp_cod, $fecha_ini, $fecha_fin, $dispensador, '', $f_hoy), $obBD_conexion);
    if ($res23 && count($res23) > 0) {
        $response['movimientos']['ingresos_mes'] = $res23[0]['ingresos_mes'];
        $response['movimientos']['despachos_mes'] = $res23[0]['despachos_mes'];
        $response['movimientos']['consumo_dia'] = $res23[0]['consumo_dia'];
        $response['movimientos']['in_dia'] = $res23[0]['in_dia'];
        $response['movimientos']['ic_dia'] = $res23[0]['ic_dia'];
        $response['movimientos']['sc_dia'] = $res23[0]['sc_dia'];
    }

    // Ultimo Cierre (25)
    $res25 = $obBD_con1->getArrayConsulta(25, array($emp_cod, $dispensador), $obBD_conexion);
    if ($res25 && count($res25) > 0) {
        $response['cierre']['Cie_Fec'] = $res25[0]['Cie_Fec'];
        $response['cierre']['Cie_Estado'] = $res25[0]['Cie_Estado'];
        $response['cierre']['Cie_Dif'] = $res25[0]['Cie_Dif'];
    }

    // Tarjetas Dispensadores (24)
    $res24 = $obBD_con1->getArrayConsulta(24, array($emp_cod, $dispensador, ''), $obBD_conexion);
    if ($res24) {
        $obBD_con1->utf8_change_param($res24);
        $response['dispensadores'] = $res24;
    }

    // Top Maquinarias (26)
    $res26 = $obBD_con1->getArrayConsulta(26, array($emp_cod, $fecha_ini, $fecha_fin, $dispensador, ''), $obBD_conexion);
    if ($res26) {
        $obBD_con1->utf8_change_param($res26);
        $response['top_vehiculos'] = $res26;
    }

    // Gr&aacute;fico Consumo Diario (27)
    $res27 = $obBD_con1->getArrayConsulta(27, array($emp_cod, $fecha_ini, $fecha_fin, $dispensador, ''), $obBD_conexion);
    if ($res27) {
        $response['grafico'] = $res27;
    }

    echo json_encode($response);
    exit;
}

// ======================================================================
// MANEJO DE PETICIONES AJAX - FASE 5 (CIERRE DIARIO)
// ======================================================================
if (isset($_GET['listCierresAjax'])) {
    $resp = array('success' => false);
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;
        $start = ($limit * $page) - $limit;
        if ($start < 0) $start = 0;

        $params = array(
            0 => $_SESSION['Ses_Emp_Cod'],
            'fec_ini' => isset($_GET['fec_ini']) ? $_GET['fec_ini'] : '',
            'fec_fin' => isset($_GET['fec_fin']) ? $_GET['fec_fin'] : '',
            'Dis_Cod' => isset($_GET['Dis_Cod']) ? $_GET['Dis_Cod'] : '',
            'Cie_Estado' => isset($_GET['Cie_Estado']) ? $_GET['Cie_Estado'] : ''
        );

        $params['limits'] = "";
        $total_arr = $obBD_con1->getArrayConsulta(18, $params, $obBD_conexion);
        $count = (int)$total_arr[0]['total'];
        $total_pages = ($count > 0 && $limit > 0) ? ceil($count / $limit) : 0;

        $params['limits'] = "LIMIT $start, $limit";
        $rows = $obBD_con1->getArrayConsulta(18, $params, $obBD_conexion);
        if ($rows) {
            $obBD_con1->utf8_change_param($rows);
        } else {
            $rows = array();
        }

        $response = array();
        $response['page'] = $page;
        $response['total'] = $total_pages;
        $response['records'] = $count;
        $response['rows'] = $rows;



        $obBD_con1->echoJson($response);
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

if (isset($_GET['getCalculoPrevioCierreAjax'])) {
    $resp = array('success' => false);
    try {
        $dis_cod = $_GET['Dis_Cod'];
        $fecha = $_GET['Cie_Fec'];
        if (!$dis_cod || !$fecha) {
            throw new Exception("Par&aacute;metros incompletos");
        }
        $params = array(
            0 => $_SESSION['Ses_Emp_Cod'],
            1 => $dis_cod,
            2 => $fecha
        );
        $res = $obBD_con1->getArrayConsulta(19, $params, $obBD_conexion);
        if ($res && count($res) > 0) {
            $resp['success'] = true;
            $resp['data'] = $res[0];
        } else {
            throw new Exception("No se pudo calcular.");
        }
    } catch (Exception $e) {
        $resp['message'] = $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
    exit;
}

if (isset($_POST['saveCierreAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        // Verificar duplicados de forma directa para mayor seguridad
        $params_check = array(
            0 => $_SESSION['Ses_Emp_Cod'],
            1 => $_POST['Cie_Dis_Cod'],
            2 => $_POST['Cie_Fec']
        );
        $res_check = $obBD_con1->getArrayConsulta(19, $params_check, $obBD_conexion);
        if ($res_check[0]['existe_cierre'] > 0) {
            throw new Exception("Ya existe un cierre registrado para este dispensador en la fecha seleccionada.");
        }

        $params = array(
            0 => $_SESSION['Ses_Emp_Cod'],
            1 => $_POST['Cie_Dis_Cod'],
            2 => $_SESSION['Ses_Usu_Cod'],
            3 => $_POST['Cie_Fec'],
            4 => $_POST['Cie_Ini'],
            5 => $_POST['Cie_Ing'],
            6 => $_POST['Cie_Sal'],
            7 => $_POST['Cie_Teo'],
            8 => $_POST['Cie_Fis'],
            9 => $_POST['Cie_Dif'],
            10 => $_POST['Cie_Estado'],
            11 => mb_convert_encoding(isset($_POST['Cie_Obs']) ? $_POST['Cie_Obs'] : '', 'ISO-8859-1', 'UTF-8')
        );

        $obBD_con1->operacionobBD(20, $params, $obBD_conexion);
        if ($obBD_con1->Error != 0) {
            throw new Exception("Error al guardar el cierre: " . $obBD_con1->getMsgError());
        }

        $resp['success'] = true;
        $resp['message'] = 'Cierre diario guardado correctamente.';
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

if (isset($_POST['changeEstadoCierreAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $params = array(0 => $_POST['Cie_Cod']);
        $obBD_con1->operacionobBD(21, $params, $obBD_conexion);
        if ($obBD_con1->Error != 0) {
            throw new Exception("Error al anular el cierre: " . $obBD_con1->getMsgError());
        }
        $resp['success'] = true;
        $resp['message'] = 'Cierre anulado correctamente.';
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

// ======================================================================
// MANEJO DE PETICIONES AJAX - FASE 1 (DISPENSADORES)
// ======================================================================
if (isset($_GET['listGridAjax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $params = array(
        0 => $_SESSION['Ses_Emp_Cod'],
        'search' => $search
    );

    $row_count = $obBD_con1->getRowConsulta(1, $params, $obBD_conexion);
    $total_records = isset($row_count['total']) ? (int)$row_count['total'] : 0;

    $pagination = pages($total_records, $page, $rows);
    $response = $pagination['data'];

    if ($total_records > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(1, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }


    $obBD_con1->echoJson($response);
    exit;
}

if (isset($_POST['saveAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Dis_Cod = isset($_POST['Dis_Cod']) ? (int)$_POST['Dis_Cod'] : 0;
        $Dis_Nom = isset($_POST['Dis_Nom']) ? trim($_POST['Dis_Nom']) : '';
        $Dis_Cap = isset($_POST['Dis_Cap']) ? (float)$_POST['Dis_Cap'] : 0;
        $Dis_Tip = isset($_POST['Dis_Tip']) ? trim($_POST['Dis_Tip']) : '';
        $Dis_Uni = isset($_POST['Dis_Uni']) ? trim($_POST['Dis_Uni']) : '';

        if (empty($Dis_Nom) || $Dis_Cap <= 0 || empty($Dis_Tip) || empty($Dis_Uni)) {
            throw new Exception('Faltan campos obligatorios o la capacidad es inv&aacute;lida.');
        }

        if ($Dis_Cod == 0) {
            $params = array($_SESSION['Ses_Emp_Cod'], $_SESSION['Ses_Usu_Cod'], $Dis_Nom, $Dis_Cap, $Dis_Tip, $Dis_Uni);
            $obBD_con1->operacionobBD(2, $params, $obBD_conexion);
            if ($obBD_con1->Error != 0) throw new Exception("Error BD: " . $obBD_con1->getMsgError());
        } else {
            $params = array($Dis_Cod, $Dis_Nom, $Dis_Cap, $Dis_Tip, $Dis_Uni);
            $obBD_con1->operacionobBD(3, $params, $obBD_conexion);
            if ($obBD_con1->Error != 0) throw new Exception("Error BD: " . $obBD_con1->getMsgError());
        }
        $resp['success'] = true;
        $resp['message'] = 'Dispensador guardado exitosamente.';
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

if (isset($_POST['changeEstadoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Dis_Cod = isset($_POST['Dis_Cod']) ? (int)$_POST['Dis_Cod'] : 0;
        $Dis_Est = isset($_POST['Dis_Est']) ? trim($_POST['Dis_Est']) : '';
        if (empty($Dis_Cod) || empty($Dis_Est)) throw new Exception('Par&aacute;metros inv&aacute;lidos.');

        $obBD_con1->operacionobBD(4, array($Dis_Cod, $Dis_Est), $obBD_conexion);
        if ($obBD_con1->Error != 0) throw new Exception("Error BD: " . $obBD_con1->getMsgError());

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

// ======================================================================
// MANEJO DE PETICIONES AJAX - FASE 2 (INGRESOS)
// ======================================================================
if (isset($_GET['listIngresosGridAjax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;

    $params = array(
        0 => $_SESSION['Ses_Emp_Cod'],
        'fec_ini' => isset($_GET['fec_ini']) ? $_GET['fec_ini'] : '',
        'fec_fin' => isset($_GET['fec_fin']) ? $_GET['fec_fin'] : '',
        'Dis_Cod' => isset($_GET['Dis_Cod']) ? $_GET['Dis_Cod'] : '',
        'Prv_Cod' => isset($_GET['Prv_Cod']) ? $_GET['Prv_Cod'] : ''
    );

    $row_count = $obBD_con1->getRowConsulta(6, $params, $obBD_conexion);
    $total_records = isset($row_count['total']) ? (int)$row_count['total'] : 0;

    $pagination = pages($total_records, $page, $rows);
    $response = $pagination['data'];

    if ($total_records > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(6, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }


    $obBD_con1->echoJson($response);
    exit;
}

if (isset($_POST['getInfoDispensadorAjax'])) {
    $Dis_Cod = isset($_POST['Dis_Cod']) ? (int)$_POST['Dis_Cod'] : 0;
    $params = array(0 => $_SESSION['Ses_Emp_Cod'], 1 => $Dis_Cod);
    $info = $obBD_con1->getRowConsulta(9, $params, $obBD_conexion);
    if ($info) {
        $obBD_con1->utf8_change_param($info);
        $obBD_con1->echoJson(array('success' => true, 'data' => $info));
    } else {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Dispensador no encontrado.'));
    }
    exit;
}

if (isset($_POST['saveIngresoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Dis_Cod = isset($_POST['Dis_Cod']) ? (int)$_POST['Dis_Cod'] : 0;
        $Did_Tip = isset($_POST['Did_Tip']) ? trim($_POST['Did_Tip']) : '';
        $Prv_Cod = isset($_POST['Prv_Cod']) ? (int)$_POST['Prv_Cod'] : 0;
        $Veh_Cod = isset($_POST['Veh_Cod']) ? (int)$_POST['Veh_Cod'] : 0;
        $Did_Fec = isset($_POST['Did_Fec']) ? trim($_POST['Did_Fec']) : '';
        $Did_Fec = str_replace('T', ' ', $Did_Fec); // Asegurar formato YYYY-MM-DD HH:MM para MySQL
        $Did_Can = isset($_POST['Did_Can']) ? (float)$_POST['Did_Can'] : 0;
        $Did_Pun = isset($_POST['Did_Pun']) ? (float)$_POST['Did_Pun'] : 0;

        if (empty($Dis_Cod) || empty($Did_Tip) || empty($Did_Fec)) throw new Exception('Faltan campos obligatorios.');
        if ($Did_Tip == 'IN' && empty($Prv_Cod)) throw new Exception('El proveedor es obligatorio para compras (IN).');
        if ($Did_Tip == 'IC' && empty($Veh_Cod)) throw new Exception('El veh&iacute;culo es obligatorio para cargas internas (IC).');
        if ($Did_Tip != 'IN' && $Did_Tip != 'IC') throw new Exception('Tipo de ingreso no v&aacute;lido.');

        if ($Did_Tip == 'IN') $Veh_Cod = 0;
        if ($Did_Tip == 'IC') $Prv_Cod = 0;
        if ($Did_Can <= 0) throw new Exception('La cantidad debe ser mayor a cero.');
        if ($Did_Pun < 0) throw new Exception('El precio unitario no puede ser negativo.');

        // Validar capacidad
        $params_info = array(0 => $_SESSION['Ses_Emp_Cod'], 1 => $Dis_Cod);
        $info = $obBD_con1->getRowConsulta(9, $params_info, $obBD_conexion);
        if (!$info) throw new Exception('Dispensador no v&aacute;lido.');

        $capacidad = (float)$info['Dis_Cap'];
        $existencia = (float)$info['existencia'];
        $capacidad_disponible = $capacidad - $existencia;

        if ($Did_Can > $capacidad_disponible) {
            throw new Exception("La cantidad ingresada ($Did_Can) supera la capacidad disponible del dispensador ($capacidad_disponible).");
        }

        // Insertar Ingreso
        $params_ins = array($Dis_Cod, $Prv_Cod, $_SESSION['Ses_Usu_Cod'], $Did_Can, $Did_Fec, $Did_Pun, $Did_Tip, $Veh_Cod);
        $obBD_con1->operacionobBD(10, $params_ins, $obBD_conexion);
        if ($obBD_con1->Error != 0) throw new Exception("Error BD: " . $obBD_con1->getMsgError());

        $resp['success'] = true;
        $resp['message'] = 'Carga interna registrada exitosamente.';
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

if (isset($_POST['changeEstadoIngresoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Did_Cod = isset($_POST['Did_Cod']) ? (int)$_POST['Did_Cod'] : 0;
        if (empty($Did_Cod)) throw new Exception('Registro no v&aacute;lido.');

        $obBD_con1->operacionobBD(11, array($Did_Cod), $obBD_conexion);
        if ($obBD_con1->Error != 0) throw new Exception("Error BD: " . $obBD_con1->getMsgError());

        $resp['success'] = true;
        $resp['message'] = 'Carga anulada exitosamente.';
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

// ======================================================================
// MANEJO DE PETICIONES AJAX - FASE 3 (DESPACHOS)
// ======================================================================
if (isset($_GET['listDespachosGridAjax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;

    $params = array(
        0 => $_SESSION['Ses_Emp_Cod'],
        'fec_ini' => isset($_GET['fec_ini']) ? $_GET['fec_ini'] : '',
        'fec_fin' => isset($_GET['fec_fin']) ? $_GET['fec_fin'] : '',
        'Dis_Cod' => isset($_GET['Dis_Cod']) ? $_GET['Dis_Cod'] : '',
        'Veh_Cod' => isset($_GET['Veh_Cod']) ? $_GET['Veh_Cod'] : '',
        'Did_Tip' => isset($_GET['Did_Tip']) ? $_GET['Did_Tip'] : ''
    );

    $row_count = $obBD_con1->getRowConsulta(13, $params, $obBD_conexion);
    $total_records = isset($row_count['total']) ? (int)$row_count['total'] : 0;

    $pagination = pages($total_records, $page, $rows);
    $response = $pagination['data'];

    if ($total_records > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(13, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }


    $obBD_con1->echoJson($response);
    exit;
}

if (isset($_POST['saveDespachoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Dis_Cod = isset($_POST['Dis_Cod']) ? (int)$_POST['Dis_Cod'] : 0;
        $Did_Tip = isset($_POST['Did_Tip']) ? trim($_POST['Did_Tip']) : '';
        $Veh_Cod = isset($_POST['Veh_Cod']) ? (int)$_POST['Veh_Cod'] : 0;
        $Did_Obs = isset($_POST['Did_Obs']) ? trim($_POST['Did_Obs']) : '';
        $Did_Fec = isset($_POST['Did_Fec']) ? trim($_POST['Did_Fec']) : '';
        $Did_Fec = str_replace('T', ' ', $Did_Fec);
        $Did_Can = isset($_POST['Did_Can']) ? (float)$_POST['Did_Can'] : 0;
        $Did_Pun = isset($_POST['Did_Pun']) ? (float)$_POST['Did_Pun'] : 0;

        if (empty($Dis_Cod) || empty($Did_Tip) || empty($Did_Fec)) throw new Exception('Faltan campos obligatorios.');
        if ($Did_Tip == 'SA' && empty($Veh_Cod)) throw new Exception('La maquinaria/veh&iacute;culo es obligatorio para abastecimiento (SA).');
        if ($Did_Tip == 'SC' && empty($Did_Obs)) throw new Exception('Debe ingresar el motivo del ajuste negativo (SC).');
        if ($Did_Tip != 'SA' && $Did_Tip != 'SC') throw new Exception('Tipo de salida no v&aacute;lido.');

        if ($Did_Tip == 'SC') {
            $Veh_Cod = 0;
            $Did_Pun = 0;
        }
        if ($Did_Can <= 0) throw new Exception('La cantidad debe ser mayor a cero.');

        // Validar existencia
        $params_info = array(0 => $_SESSION['Ses_Emp_Cod'], 1 => $Dis_Cod);
        $info = $obBD_con1->getRowConsulta(9, $params_info, $obBD_conexion);
        if (!$info) throw new Exception('Dispensador no v&aacute;lido.');

        $existencia = (float)$info['existencia'];

        if ($Did_Can > $existencia) {
            throw new Exception("No existe suficiente combustible disponible para realizar la salida. (Existencia actual: $existencia)");
        }

        // Insertar Salida (Dis_Cod, Veh_Cod, Usu_Cod, Did_Can, Did_Fec, Did_Pun, Did_Tip, Did_Obs)
        $params_ins = array($Dis_Cod, $Veh_Cod, $_SESSION['Ses_Usu_Cod'], $Did_Can, $Did_Fec, $Did_Pun, $Did_Tip, $Did_Obs);
        $obBD_con1->operacionobBD(14, $params_ins, $obBD_conexion);
        if ($obBD_con1->Error != 0) throw new Exception("Error BD: " . $obBD_con1->getMsgError());

        $resp['success'] = true;
        $resp['message'] = 'Salida registrada exitosamente.';
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

if (isset($_POST['changeEstadoDespachoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Did_Cod = isset($_POST['Did_Cod']) ? (int)$_POST['Did_Cod'] : 0;
        if (empty($Did_Cod)) throw new Exception('Registro no v&aacute;lido.');

        $obBD_con1->operacionobBD(15, array($Did_Cod), $obBD_conexion);
        if ($obBD_con1->Error != 0) throw new Exception("Error BD: " . $obBD_con1->getMsgError());

        $resp['success'] = true;
        $resp['message'] = 'Salida anulada exitosamente.';
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

// ======================================================================
// MANEJO DE PETICIONES AJAX - FASE 4 (AJUSTES Y KARDEX)
// ======================================================================
if (isset($_GET['listAjustesGridAjax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;

    $params = array(
        0 => $_SESSION['Ses_Emp_Cod'],
        'fec_ini' => isset($_GET['fec_ini']) ? $_GET['fec_ini'] : '',
        'fec_fin' => isset($_GET['fec_fin']) ? $_GET['fec_fin'] : '',
        'Dis_Cod' => isset($_GET['Dis_Cod']) ? $_GET['Dis_Cod'] : '',
        'Did_Tip' => isset($_GET['Did_Tip']) ? $_GET['Did_Tip'] : ''
    );

    $row_count = $obBD_con1->getRowConsulta(16, $params, $obBD_conexion);
    $total_records = isset($row_count['total']) ? (int)$row_count['total'] : 0;

    $pagination = pages($total_records, $page, $rows);
    $response = $pagination['data'];

    if ($total_records > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(16, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }


    $obBD_con1->echoJson($response);
    exit;
}

if (isset($_POST['saveAjusteAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Dis_Cod = isset($_POST['Dis_Cod']) ? (int)$_POST['Dis_Cod'] : 0;
        $Did_Tip = isset($_POST['Did_Tip']) ? trim($_POST['Did_Tip']) : '';
        $Did_Obs = isset($_POST['Did_Obs']) ? trim($_POST['Did_Obs']) : '';
        $Did_Fec = isset($_POST['Did_Fec']) ? trim($_POST['Did_Fec']) : '';
        $Did_Fec = str_replace('T', ' ', $Did_Fec);
        $Did_Can = isset($_POST['Did_Can']) ? (float)$_POST['Did_Can'] : 0;
        $Did_Pun = 0; // Se fuerza a 0 seg&uacute;n reglas de ajuste
        $Veh_Cod = 0;
        $Prv_Cod = 0;

        if (empty($Dis_Cod) || empty($Did_Tip) || empty($Did_Fec) || empty($Did_Obs)) throw new Exception('Faltan campos obligatorios.');
        if ($Did_Tip != 'IC' && $Did_Tip != 'SC') throw new Exception('Tipo de ajuste no v&aacute;lido.');
        if ($Did_Can <= 0) throw new Exception('La cantidad debe ser mayor a cero.');

        // Validar existencia
        $params_info = array(0 => $_SESSION['Ses_Emp_Cod'], 1 => $Dis_Cod);
        $info = $obBD_con1->getRowConsulta(9, $params_info, $obBD_conexion);
        if (!$info) throw new Exception('Dispensador no v&aacute;lido.');

        $existencia = (float)$info['existencia'];
        $capacidad = (float)$info['Dis_Cap'];
        $disponible = $capacidad - $existencia;

        if ($Did_Tip == 'IC' && $Did_Can > $disponible) {
            throw new Exception("El ajuste positivo supera la capacidad disponible del dispensador. (Disponible: $disponible)");
        }
        if ($Did_Tip == 'SC' && $Did_Can > $existencia) {
            throw new Exception("No existe suficiente combustible para el ajuste negativo. (Existencia actual: $existencia)");
        }

        // Insertar Ajuste (Dis_Cod, Veh_Cod, Usu_Cod, Did_Can, Did_Fec, Did_Pun, Did_Tip, Did_Obs)
        $params_ins = array($Dis_Cod, $Veh_Cod, $_SESSION['Ses_Usu_Cod'], $Did_Can, $Did_Fec, $Did_Pun, $Did_Tip, $Did_Obs);
        $obBD_con1->operacionobBD(14, $params_ins, $obBD_conexion);
        if ($obBD_con1->Error != 0) throw new Exception("Error BD: " . $obBD_con1->getMsgError());

        $resp['success'] = true;
        $resp['message'] = 'Ajuste de inventario registrado exitosamente.';
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

if (isset($_POST['changeEstadoAjusteAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Did_Cod = isset($_POST['Did_Cod']) ? (int)$_POST['Did_Cod'] : 0;
        if (empty($Did_Cod)) throw new Exception('Registro no v&aacute;lido.');

        $obBD_con1->operacionobBD(15, array($Did_Cod), $obBD_conexion);
        if ($obBD_con1->Error != 0) throw new Exception("Error BD: " . $obBD_con1->getMsgError());

        $resp['success'] = true;
        $resp['message'] = 'Ajuste anulado exitosamente.';
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

if (isset($_GET['listKardexAjax'])) {
    $resp = array('success' => false);
    try {
        $params = array(
            0 => $_SESSION['Ses_Emp_Cod'],
            'fec_ini' => isset($_GET['fec_ini']) ? $_GET['fec_ini'] : '',
            'fec_fin' => isset($_GET['fec_fin']) ? $_GET['fec_fin'] : '',
            'Dis_Cod' => isset($_GET['Dis_Cod']) ? $_GET['Dis_Cod'] : '',
            'Did_Tip' => isset($_GET['Did_Tip']) ? $_GET['Did_Tip'] : ''
        );

        $records = $obBD_con1->getArrayConsulta(17, $params, $obBD_conexion);

        $saldo_acumulado = 0;
        $sum_entradas = 0;
        $sum_salidas = 0;
        $count_mov = 0;

        $processed = array();

        if ($records) {
            foreach ($records as $r) {
                if ($r['Did_Est'] != 'A') continue;

                $cantidad = (float)$r['Did_Can'];
                $precio = (float)$r['Did_Pun'];
                $total_ref = $cantidad * $precio;

                $entrada = 0;
                $salida = 0;
                $responsable = '';

                if ($r['Did_Tip'] == 'IN') {
                    $entrada = $cantidad;
                    $responsable = 'Proveedor: ' . $r['proveedor_nombre'];
                    $sum_entradas += $cantidad;
                    $saldo_acumulado += $cantidad;
                } elseif ($r['Did_Tip'] == 'IC') {
                    $entrada = $cantidad;
                    $responsable = 'Ajuste Positivo - ' . $r['Did_Obs'];
                    $sum_entradas += $cantidad;
                    $saldo_acumulado += $cantidad;
                } elseif ($r['Did_Tip'] == 'SA') {
                    $salida = $cantidad;
                    $responsable = 'Veh/Maq: ' . $r['vehiculo_nombre'] . ' - ' . $r['Did_Obs'];
                    $sum_salidas += $cantidad;
                    $saldo_acumulado -= $cantidad;
                } elseif ($r['Did_Tip'] == 'SC') {
                    $salida = $cantidad;
                    $responsable = 'Ajuste Negativo - ' . $r['Did_Obs'];
                    $sum_salidas += $cantidad;
                    $saldo_acumulado -= $cantidad;
                }

                $count_mov++;

                $processed[] = array(
                    'Did_Fec' => $r['Did_Fec'],
                    'Dis_Nom' => $r['Dis_Nom'],
                    'Did_Tip' => $r['Did_Tip'],
                    'responsable' => $responsable,
                    'entrada' => $entrada,
                    'salida' => $salida,
                    'Did_Pun' => $r['Did_Pun'],
                    'total_ref' => $total_ref,
                    'saldo' => $saldo_acumulado,
                    'usuario_nombre' => $r['usuario_nombre'],
                    'Did_Est' => $r['Did_Est']
                );
            }
        }

        $response = array();
        $response['page'] = 1;
        $response['total'] = 1;
        $response['records'] = count($processed);
        $response['rows'] = $processed;

        $response['userdata'] = array(
            'sum_entradas' => $sum_entradas,
            'sum_salidas' => $sum_salidas,
            'count_mov' => $count_mov,
            'saldo_final' => $saldo_acumulado
        );

        $obBD_con1->utf8_change_param($response);


        $obBD_con1->echoJson($response);
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// ======================================================================
// RENDERIZADO HTML
// ======================================================================
// Obtener cat&aacute;logos para combos
$dispensadores = $obBD_con1->getArrayConsulta(7, array(0 => $_SESSION['Ses_Emp_Cod']), $obBD_conexion) ?: array();
$obBD_con1->utf8_change_param($dispensadores);

$proveedores = $obBD_con1->getArrayConsulta(8, array(0 => $_SESSION['Ses_Emp_Cod']), $obBD_conexion) ?: array();
$obBD_con1->utf8_change_param($proveedores);

$vehiculos = $obBD_con1->getArrayConsulta(12, array(0 => $_SESSION['Ses_Emp_Cod']), $obBD_conexion) ?: array();
$obBD_con1->utf8_change_param($vehiculos);

?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Administracion de Dispensadores</title>
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <?php require_once('../../mascaras/model3/estilos/estilos.php'); ?>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <link rel="stylesheet" type="text/css" href="../RECURSOS/maquinaria_dispensador.css" />

</HEAD>

<BODY>
    <style>
        /* Corrección de selección de fila y forzado ant-cache */
        /* Corrección de selección de fila infalible (Cubre JQueryUI y Bootstrap) */
        tr.ui-state-highlight,
        tr.ui-state-highlight td,
        .ui-jqgrid-btable tr.ui-state-highlight td,
        .ui-jqgrid-btable tr.success td,
        .ui-jqgrid-btable tr.active td,
        .ui-jqgrid-btable tr.info td,
        tr[aria-selected="true"] td {
            background-color: #337ab7 !important;
            background-image: none !important;
            color: #ffffff !important;
        }



        /* Estilo de encabezado igual al de Horómetro */
        .exa-header {
            background: #334a5f !important;
            color: #fff !important;
            font-weight: bold;
            font-size: 14px;
            padding: 12px 18px !important;
            border-bottom: none !important;
        }
    </style>
    <div class="panel panel-main exa-ui-panel exa-ui-fill-page">
        <div class="panel-heading exa-header">
            <h3 class="panel-title"><i class="fa fa-tint"></i> Administracion de Combustible</h3>
        </div>
        <div class="panel-body exa-ui-page-view">

            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li><a data-toggle="tab" href="#tab-dashboard"><i class="fa fa-bar-chart"></i> Dashboard</a></li>
                    <li class="active"><a data-toggle="tab" href="#tab-dispensadores"><i class="fa fa-hdd-o"></i> Dispensadores</a></li>
                    <li><a data-toggle="tab" href="#tab-ingresos"><i class="fa fa-arrow-down"></i> Carga de Combustible</a></li>
                    <li><a data-toggle="tab" href="#tab-despachos"><i class="fa fa-arrow-up"></i> Despachos</a></li>
                    <!-- <li><a data-toggle="tab" href="#tab-ajustes"><i class="fa fa-sliders"></i> Ajustes</a></li> -->
                    <li><a data-toggle="tab" href="#tab-kardex"><i class="fa fa-exchange"></i> Kardex</a></li>
                    <li><a data-toggle="tab" href="#tab-cierre"><i class="fa fa-lock"></i> Cierre Diario</a></li>
                    <!-- <li><a data-toggle="tab" href="#tab-reportes"><i class="fa fa-file-text-o"></i> Reportes</a></li> -->
                </ul>

                <div class="tab-content tab-content-custom">
                    <!-- =============================== -->
                    <!-- PENDIENTES DE IMPLEMENTACI&Oacute;N    -->
                    <!-- =============================== -->
                    <div id="tab-dashboard" class="tab-pane fade">
                        <div class="row" style="margin-bottom: 15px;">
                            <!-- Filtros Superiores -->
                            <div class="col-md-12">
                                <div class="v2-filters">
                                    <h4 style="margin-top:0; margin-bottom:15px; color:var(--v2-brand-dark); font-weight:600;"><i class="fa fa-filter"></i> Filtros del Dashboard</h4>
                                    <form id="formFiltrosDashboard" onsubmit="event.preventDefault(); loadDashboard();">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label>Fecha Inicio</label>
                                                <input type="date" id="dash_fec_ini" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Fecha Fin</label>
                                                <input type="date" id="dash_fec_fin" class="form-control" value="<?php echo date('Y-m-t'); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Dispensador (Opcional)</label>
                                                <select id="dash_dis_cod" class="form-control">
                                                    <option value="0">- Todos los Dispensadores -</option>
                                                    <?php foreach ($dispensadores as $d) {
                                                        echo "<option value='{$d['Dis_Cod']}'>{$d['Dis_Nom']}</option>";
                                                    } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3" style="padding-top: 25px;">
                                                <button type="button" class="btn btn-exa-success btn-block" onclick="loadDashboard()"><i class="fa fa-refresh"></i> Actualizar Dashboard</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- SECCI&Oacute;N 6: ALERTAS -->
                        <div class="row" id="dash_alertas_container" style="display:none;">
                            <div class="col-md-12">
                                <div class="alert alert-warning alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h4><i class="icon fa fa-warning"></i> Alertas de Sistema</h4>
                                    <ul id="dash_alertas_list" style="margin:0; padding-left:20px;"></ul>
                                </div>
                            </div>
                        </div>
                        <!-- SECCI&Oacute;N 1: RESUMEN GENERAL (Tarjetas Superiores) -->
                        <div class="row">
                            <div class="col-lg-2 col-xs-6">
                                <div class="v2-metric-card info">
                                    <i class="fa fa-hdd-o v2-metric-icon"></i>
                                    <div class="v2-metric-content">
                                        <h3 class="v2-metric-value" id="dash_gen_disp">0</h3>
                                        <p class="v2-metric-label">Dispensadores Activos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-xs-6">
                                <div class="v2-metric-card success">
                                    <i class="fa fa-cubes v2-metric-icon"></i>
                                    <div class="v2-metric-content">
                                        <h3 class="v2-metric-value" id="dash_gen_ext">0</h3>
                                        <p class="v2-metric-label">Existencia Total (Gl)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-xs-6">
                                <div class="v2-metric-card brand">
                                    <i class="fa fa-arrow-down v2-metric-icon"></i>
                                    <div class="v2-metric-content">
                                        <h3 class="v2-metric-value" id="dash_gen_ing">0</h3>
                                        <p class="v2-metric-label">Ingresos del Per&iacute;odo</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-xs-6">
                                <div class="v2-metric-card danger">
                                    <i class="fa fa-arrow-up v2-metric-icon"></i>
                                    <div class="v2-metric-content">
                                        <h3 class="v2-metric-value" id="dash_gen_sal">0</h3>
                                        <p class="v2-metric-label">Despachos Per&iacute;odo</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-xs-6">
                                <div class="v2-metric-card warning">
                                    <i class="fa fa-calendar v2-metric-icon"></i>
                                    <div class="v2-metric-content">
                                        <h3 class="v2-metric-value" id="dash_gen_condia">0</h3>
                                        <p class="v2-metric-label">Consumo del D&iacute;a</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-xs-6">
                                <div class="v2-metric-card purple">
                                    <i class="fa fa-lock v2-metric-icon"></i>
                                    <div class="v2-metric-content">
                                        <h3 class="v2-metric-value" id="dash_gen_cierre" style="font-size: 24px;">-</h3>
                                        <p class="v2-metric-label">&Uacute;ltimo Cierre</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCI&Oacute;N 2: ESTADO DE DISPENSADORES -->
                        <h4 class="page-header"><i class="fa fa-battery-half"></i> Estado de Dispensadores</h4>
                        <div class="row" id="dash_dispensadores_container">
                            <!-- Renderizado din&aacute;mico -->
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <!-- SECCI&Oacute;N 7: GR&Aacute;FICO (Consumo Diario) -->
                                <div class="v2-panel">
                                    <div class="v2-panel-header">
                                        <i class="fa fa-bar-chart"></i> Consumo Diario
                                    </div>
                                    <div class="v2-panel-body">
                                        <canvas id="chartConsumoDiario" style="height: 250px; width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <!-- SECCI&Oacute;N 3: MOVIMIENTOS DEL D&Iacute;A -->
                                <div class="v2-panel">
                                    <div class="v2-panel-header">
                                        <i class="fa fa-exchange"></i> Movimientos del D&iacute;a
                                    </div>
                                    <div class="v2-panel-body" style="padding:0;">
                                        <table class="v2-table">
                                            <tbody>
                                                <tr>
                                                    <td><strong>(IN) Compra Proveedor</strong></td>
                                                    <td class="text-right" style="color:#27ae60; font-weight:bold;" id="dash_dia_in">0.00</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>(IC) Ajuste (+)</strong></td>
                                                    <td class="text-right" style="color:#2980b9; font-weight:bold;" id="dash_dia_ic">0.00</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>(SA) Abastecimiento</strong></td>
                                                    <td class="text-right" style="color:#c0392b; font-weight:bold;" id="dash_dia_sa">0.00</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>(SC) Ajuste (-)</strong></td>
                                                    <td class="text-right" style="color:#f39c12; font-weight:bold;" id="dash_dia_sc">0.00</td>
                                                </tr>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th style="background:var(--v2-bg-grid);">Total Movimientos</th>
                                                    <th class="text-right" style="background:var(--v2-bg-grid); color:var(--v2-brand-dark);" id="dash_dia_total">0.00</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <!-- SECCI&Oacute;N 4: CIERRES -->
                                <div class="v2-panel">
                                    <div class="v2-panel-header">
                                        <i class="fa fa-lock"></i> Estado &Uacute;ltimo Cierre
                                    </div>
                                    <div class="v2-panel-body v2-cierre-status">
                                        <div class="v2-cierre-fecha" id="dash_cierre_fecha">-</div>
                                        <div class="v2-cierre-valor" id="dash_cierre_estado">-</div>
                                        <p style="color:var(--v2-text-muted); font-size:13px; margin:0;">Diferencia: <strong id="dash_cierre_dif" style="color:var(--v2-text);">0.00</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCI&Oacute;N 5: TOP MAQUINARIAS -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="v2-panel">
                                    <div class="v2-panel-header">
                                        <i class="fa fa-truck"></i> Top 5 Maquinarias (Mayor Consumo en el Per&iacute;odo)
                                    </div>
                                    <div style="overflow-x:auto;">
                                        <table class="v2-table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Maquinaria</th>
                                                    <th class="text-right">Consumo Total</th>
                                                    <th>Operador (Usuario)</th>
                                                </tr>
                                            </thead>
                                            <tbody id="dash_top_maq_body">
                                                <tr>
                                                    <td colspan="4" class="text-center">Cargando...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="tab-despachos" class="tab-pane fade">
                        <div class="row" style="margin-bottom: 10px;">
                            <div class="col-md-12 text-right">
                                <button class="btn btn-success" onclick="abrirModalDespacho()"><i class="fa fa-arrow-up"></i> Nueva Salida</button>
                                <button class="btn btn-default" onclick="reloadGridDespachos()"><i class="fa fa-refresh"></i> Actualizar</button>
                            </div>
                        </div>
                        <div class="row" style="margin-bottom: 15px;">
                            <form id="formFiltrosDespachos" onsubmit="event.preventDefault(); reloadGridDespachos();">
                                <div class="col-md-2">
                                    <label>Desde:</label>
                                    <input type="date" id="filtro_fec_ini_out" class="form-control input-sm" value="<?php echo date('Y-m-01'); ?>" max="9999-12-31">
                                </div>
                                <div class="col-md-2">
                                    <label>Hasta:</label>
                                    <input type="date" id="filtro_fec_fin_out" class="form-control input-sm" value="<?php echo date('Y-m-t'); ?>" max="9999-12-31">
                                </div>
                                <div class="col-md-3">
                                    <label>Dispensador:</label>
                                    <select id="filtro_Dis_Cod_Out" class="form-control input-sm">
                                        <option value="">-- Todos --</option>
                                        <?php foreach ($dispensadores as $d) {
                                            echo "<option value='{$d['Dis_Cod']}'>{$d['Dis_Nom']}</option>";
                                        } ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Tipo de Salida:</label>
                                    <select id="filtro_Did_Tip_Out" class="form-control input-sm">
                                        <option value="">-- Todos --</option>
                                        <option value="SA">SA - Abastecimiento a Maquinaria</option>
                                        <option value="SC">SC - Ajuste Negativo</option>
                                    </select>
                                </div>
                                <div class="col-md-2" style="padding-top: 22px;">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Buscar</button>
                                    <button type="button" class="btn btn-default btn-sm" onclick="limpiarFiltrosDespachos()"><i class="fa fa-eraser"></i> Limpiar</button>
                                </div>
                            </form>
                        </div>
                        <div class="row">
                            <div class="col-md-12 exa-ui-grid-host">
                                <table id="gridDespachos"></table>
                                <div id="pagerDespachos"></div>
                            </div>
                        </div>
                    </div>
                    <div id="tab-ajustes" class="tab-pane fade">
                        <div class="row" style="margin-bottom: 10px;">
                            <div class="col-md-12 text-right">
                                <button class="btn btn-success" onclick="abrirModalAjuste()"><i class="fa fa-wrench"></i> Nuevo Ajuste</button>
                                <button class="btn btn-default" onclick="reloadGridAjustes()"><i class="fa fa-refresh"></i> Actualizar</button>
                            </div>
                        </div>
                        <div class="row" style="margin-bottom: 15px;">
                            <form id="formFiltrosAjustes" onsubmit="event.preventDefault(); reloadGridAjustes();">
                                <div class="col-md-2">
                                    <label>Desde:</label>
                                    <input type="date" id="filtro_fec_ini_aj" class="form-control input-sm" value="<?php echo date('Y-m-01'); ?>" max="9999-12-31">
                                </div>
                                <div class="col-md-2">
                                    <label>Hasta:</label>
                                    <input type="date" id="filtro_fec_fin_aj" class="form-control input-sm" value="<?php echo date('Y-m-t'); ?>" max="9999-12-31">
                                </div>
                                <div class="col-md-3">
                                    <label>Dispensador:</label>
                                    <select id="filtro_Dis_Cod_Aj" class="form-control input-sm">
                                        <option value="">-- Todos --</option>
                                        <?php foreach ($dispensadores as $d) {
                                            echo "<option value='{$d['Dis_Cod']}'>{$d['Dis_Nom']}</option>";
                                        } ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Tipo de Ajuste:</label>
                                    <select id="filtro_Did_Tip_Aj" class="form-control input-sm">
                                        <option value="">-- Todos --</option>
                                        <option value="IC">IC - Ajuste Positivo</option>
                                        <option value="SC">SC - Ajuste Negativo</option>
                                    </select>
                                </div>
                                <div class="col-md-2" style="padding-top: 22px;">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Buscar</button>
                                    <button type="button" class="btn btn-default btn-sm" onclick="limpiarFiltrosAjustes()"><i class="fa fa-eraser"></i> Limpiar</button>
                                </div>
                            </form>
                        </div>
                        <div class="row">
                            <div class="col-md-12 exa-ui-grid-host">
                                <table id="gridAjustes"></table>
                                <div id="pagerAjustes"></div>
                            </div>
                        </div>
                    </div>
                    <div id="tab-kardex" class="tab-pane fade">
                        <div class="row" style="margin-bottom: 15px;">
                            <form id="formFiltrosKardex" onsubmit="event.preventDefault(); consultarKardex();">
                                <div class="col-md-2">
                                    <label>Desde:</label>
                                    <input type="date" id="filtro_fec_ini_kx" class="form-control input-sm" value="<?php echo date('Y-m-01'); ?>" max="9999-12-31" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Hasta:</label>
                                    <input type="date" id="filtro_fec_fin_kx" class="form-control input-sm" value="<?php echo date('Y-m-t'); ?>" max="9999-12-31" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Dispensador:</label>
                                    <select id="filtro_Dis_Cod_Kx" class="form-control input-sm">
                                        <option value="">-- Todos --</option>
                                        <?php foreach ($dispensadores as $d) {
                                            echo "<option value='{$d['Dis_Cod']}'>{$d['Dis_Nom']}</option>";
                                        } ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Movimiento:</label>
                                    <select id="filtro_Did_Tip_Kx" class="form-control input-sm">
                                        <option value="">-- Todos --</option>
                                        <option value="IN">IN - Compra</option>
                                        <option value="IC">IC - Ajuste Positivo</option>
                                        <option value="SA">SA - Abastecimiento</option>
                                        <option value="SC">SC - Ajuste Negativo</option>
                                    </select>
                                </div>
                                <div class="col-md-2" style="padding-top: 22px;">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Consultar</button>
                                </div>
                            </form>
                        </div>

                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-3 col-sm-6">
                                <div class="v2-metric-card success">
                                    <i class="fa fa-arrow-down v2-metric-icon"></i>
                                    <div class="v2-metric-content">
                                        <h3 class="v2-metric-value" id="lbl_Kx_In">0.00</h3>
                                        <p class="v2-metric-label">Total Entradas</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="v2-metric-card danger">
                                    <i class="fa fa-arrow-up v2-metric-icon"></i>
                                    <div class="v2-metric-content">
                                        <h3 class="v2-metric-value" id="lbl_Kx_Out">0.00</h3>
                                        <p class="v2-metric-label">Total Salidas</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="v2-metric-card info">
                                    <i class="fa fa-exchange v2-metric-icon"></i>
                                    <div class="v2-metric-content">
                                        <h3 class="v2-metric-value" id="lbl_Kx_Mov">0</h3>
                                        <p class="v2-metric-label">Movimientos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="v2-metric-card warning">
                                    <i class="fa fa-database v2-metric-icon"></i>
                                    <div class="v2-metric-content">
                                        <h3 class="v2-metric-value" id="lbl_Kx_Saldo">0.00</h3>
                                        <p class="v2-metric-label">Saldo Final</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="divKardexPrint">
                            <div class="col-md-12 exa-ui-grid-host">
                                <table id="gridKardex"></table>
                                <div id="pagerKardex"></div>
                            </div>
                        </div>
                    </div>
                    <div id="tab-cierre" class="tab-pane fade">
                        <div class="row" style="margin-bottom: 15px;">
                            <form id="formFiltrosCierre" onsubmit="event.preventDefault(); reloadGridCierres();">
                                <div class="col-md-2">
                                    <label>Desde:</label>
                                    <input type="date" id="filtro_fec_ini_cie" class="form-control input-sm" value="<?php echo date('Y-m-01'); ?>" max="9999-12-31" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Hasta:</label>
                                    <input type="date" id="filtro_fec_fin_cie" class="form-control input-sm" value="<?php echo date('Y-m-t'); ?>" max="9999-12-31" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Dispensador:</label>
                                    <select id="filtro_Dis_Cod_Cie" class="form-control input-sm">
                                        <option value="">-- Todos --</option>
                                        <?php foreach ($dispensadores as $d) {
                                            echo "<option value='{$d['Dis_Cod']}'>{$d['Dis_Nom']}</option>";
                                        } ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Estado:</label>
                                    <select id="filtro_Cie_Estado" class="form-control input-sm">
                                        <option value="">-- Todos --</option>
                                        <option value="CUADRADO">CUADRADO</option>
                                        <option value="SOBRANTE">SOBRANTE</option>
                                        <option value="DESCUADRADO">DESCUADRADO</option>
                                    </select>
                                </div>
                                <div class="col-md-2" style="padding-top: 22px;">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Buscar</button>
                                    <button type="button" class="btn btn-default btn-sm" onclick="limpiarFiltrosCierres()"><i class="fa fa-eraser"></i> Limpiar</button>
                                </div>
                            </form>
                        </div>

                        <div class="row" style="margin-bottom: 10px;">
                            <div class="col-md-12">
                                <button class="btn btn-success" onclick="abrirModalCierre()"><i class="fa fa-plus"></i> Nuevo Cierre</button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 exa-ui-grid-host">
                                <table id="gridCierre"></table>
                                <div id="pagerCierre"></div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL CIERRE DIARIO -->
                    <div class="modal fade" id="modalFormularioCierre" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title"><i class="fa fa-lock"></i> Registrar Cierre Diario</h4>
                                </div>
                                <div class="modal-body">
                                    <form id="formCierre" onsubmit="event.preventDefault(); guardarCierre(this);">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Fecha de Cierre <span class="text-danger">*</span></label>
                                                    <input type="date" id="Cie_Fec" class="form-control" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" onchange="cargarCalculoPrevioCierre()" required>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label>Dispensador <span class="text-danger">*</span></label>
                                                    <select id="Cie_Dis_Cod" class="form-control" onchange="cargarCalculoPrevioCierre()" required>
                                                        <option value="">Seleccione...</option>
                                                        <?php foreach ($dispensadores as $d) {
                                                            echo "<option value='{$d['Dis_Cod']}'>{$d['Dis_Nom']} - {$d['Dis_Cap']} {$d['Dis_Uni']}</option>";
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="boxCalculoTeorico" style="display:none;">
                                            <h5 class="page-header" style="margin-top:10px;">C&aacute;lculo Te&oacute;rico</h5>
                                            <div class="row text-center" style="margin-bottom:15px;">
                                                <div class="col-md-3">
                                                    <div class="well well-sm" style="background:#f9f9f9; border-left:3px solid #777;">
                                                        <small class="text-muted">Existencia Inicial</small><br>
                                                        <h4 id="lbl_cie_ini" style="margin:5px 0 0 0;">0.00</h4>
                                                        <input type="hidden" id="cie_ini_val" value="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="well well-sm" style="background:#f9f9f9; border-left:3px solid #00a65a;">
                                                        <small class="text-muted">(+) Ingresos del D&iacute;a</small><br>
                                                        <h4 id="lbl_cie_ing" class="text-success" style="margin:5px 0 0 0;">0.00</h4>
                                                        <input type="hidden" id="cie_ing_val" value="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="well well-sm" style="background:#f9f9f9; border-left:3px solid #dd4b39;">
                                                        <small class="text-muted">(-) Salidas del D&iacute;a</small><br>
                                                        <h4 id="lbl_cie_sal" class="text-danger" style="margin:5px 0 0 0;">0.00</h4>
                                                        <input type="hidden" id="cie_sal_val" value="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="well well-sm" style="background:#f4f4f4; border-left:3px solid #00c0ef;">
                                                        <small class="text-muted">(=) Existencia Te&oacute;rica</small><br>
                                                        <h4 id="lbl_cie_teo" class="text-info font-bold" style="margin:5px 0 0 0;">0.00</h4>
                                                        <input type="hidden" id="cie_teo_val" value="0">
                                                    </div>
                                                </div>
                                            </div>

                                            <h5 class="page-header">Medici&oacute;n F&iacute;sica</h5>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Existencia F&iacute;sica <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <input type="number" id="Cie_Fis" class="form-control" step="0.01" min="0" onkeyup="calcularDiferenciaCierre()" onchange="calcularDiferenciaCierre()" required>
                                                            <span class="input-group-addon"><i class="fa fa-tachometer"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Diferencia</label>
                                                        <input type="text" id="Cie_Dif" class="form-control" readonly>
                                                        <input type="hidden" id="cie_dif_val" value="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Estado</label><br>
                                                        <h3 id="lbl_cie_estado" style="text-align: center; margin:0; padding:5px 10px; border-radius:4px;" class="bg-gray text-center">-</h3>
                                                        <input type="hidden" id="cie_estado_val" value="">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Observaci&oacute;n (Opcional)</label>
                                                    <textarea id="Cie_Obs" class="form-control" rows="2" maxlength="250" placeholder="Motivo de la diferencia, condiciones especiales, etc."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="text-right">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-success" id="btnGuardarCierre" disabled><i class="fa fa-save"></i> Guardar Cierre</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- FIN MODAL CIERRE DIARIO -->
                    <div id="tab-reportes" class="tab-pane fade">
                        <div class="alert alert-info"><i class="fa fa-info-circle"></i> Funcionalidad pendiente de implementaci&oacute;n. (Fases futuras)</div>
                    </div>

                    <!-- =============================== -->
                    <!-- TAB 1: DISPENSADORES            -->
                    <!-- =============================== -->
                    <div id="tab-dispensadores" class="tab-pane fade in active">
                        <div class="row" style="margin-bottom: 10px;">
                            <div class="col-md-6">
                                <form id="formSearch" onsubmit="event.preventDefault(); searchGrid();" class="form-inline">
                                    <div class="form-group">
                                        <input type="text" class="form-control" id="search_dis_nom" placeholder="Buscar por nombre...">
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                                </form>
                            </div>
                            <div class="col-md-6 text-right">
                                <button class="btn btn-success" onclick="abrirModalNuevo()"><i class="fa fa-plus"></i> Nuevo Dispensador</button>
                                <button class="btn btn-default" onclick="reloadGrid()"><i class="fa fa-refresh"></i> Actualizar</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 exa-ui-grid-host">
                                <table id="gridData"></table>
                                <div id="pagerData"></div>
                            </div>
                        </div>
                    </div>

                    <!-- =============================== -->
                    <!-- TAB 2: CARGA DE COMBUSTIBLE     -->
                    <!-- =============================== -->
                    <div id="tab-ingresos" class="tab-pane fade">
                        <div class="row" style="margin-bottom: 10px;">
                            <div class="col-md-12 text-right">
                                <button class="btn btn-success" onclick="abrirModalIngreso()"><i class="fa fa-plus"></i> Nueva Carga</button>
                                <button class="btn btn-default" onclick="reloadGridIngresos()"><i class="fa fa-refresh"></i> Actualizar</button>
                            </div>
                        </div>
                        <div class="row" style="margin-bottom: 15px;">
                            <form id="formFiltrosIngresos" onsubmit="event.preventDefault(); reloadGridIngresos();">
                                <div class="col-md-2">
                                    <label>Desde:</label>
                                    <input type="date" id="filtro_fec_ini" class="form-control input-sm" value="<?php echo date('Y-m-01'); ?>" max="9999-12-31">
                                </div>
                                <div class="col-md-2">
                                    <label>Hasta:</label>
                                    <input type="date" id="filtro_fec_fin" class="form-control input-sm" value="<?php echo date('Y-m-t'); ?>" max="9999-12-31">
                                </div>
                                <div class="col-md-3">
                                    <label>Dispensador:</label>
                                    <select id="filtro_Dis_Cod_In" class="form-control input-sm">
                                        <option value="">-- Todos --</option>
                                        <?php foreach ($dispensadores as $d) {
                                            echo "<option value='{$d['Dis_Cod']}'>{$d['Dis_Nom']}</option>";
                                        } ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Proveedor (Ref. Antiguos):</label>
                                    <select id="filtro_Prv_Cod_In" class="form-control input-sm">
                                        <option value="">-- Todos --</option>
                                        <?php foreach ($proveedores as $p) {
                                            echo "<option value='{$p['Prv_Cod']}'>{$p['proveedor_nombre']}</option>";
                                        } ?>
                                    </select>
                                </div>
                                <div class="col-md-2" style="padding-top: 22px;">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Buscar</button>
                                    <button type="button" class="btn btn-default btn-sm" onclick="limpiarFiltrosIngresos()"><i class="fa fa-eraser"></i> Limpiar</button>
                                </div>
                            </form>
                        </div>
                        <div class="row">
                            <div class="col-md-12 exa-ui-grid-host">
                                <table id="gridIngresos"></table>
                                <div id="pagerIngresos"></div>
                            </div>
                        </div>
                    </div>

                </div> <!-- fin tab-content -->
            </div> <!-- fin nav-tabs-custom -->
        </div>
    </div>

    <!-- =============================== -->
    <!-- MODAL DISPENSADOR (FASE 1)      -->
    <!-- =============================== -->
    <div id="modalFormulario" class="modal fade" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-hdd-o"></i> Dispensador</h4>
                </div>
                <div class="modal-body">
                    <form id="formDispensador">
                        <input type="hidden" id="Dis_Cod" name="Dis_Cod" value="0">
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="Dis_Nom">Nombre / Identificador <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="Dis_Nom" name="Dis_Nom" maxlength="150" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="Dis_Cap">Capacidad <span class="text-danger">*</span></label>
                                <input type="number" class="form-control text-right" id="Dis_Cap" name="Dis_Cap" step="0.01" min="0.01" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="Dis_Tip">Combustible <span class="text-danger">*</span></label>
                                <select class="form-control" id="Dis_Tip" name="Dis_Tip" required>
                                    <option value="">- Seleccione -</option>
                                    <option value="DI">DIESEL</option>
                                    <option value="EC">ECO</option>
                                    <option value="SU">SUPER</option>
                                    <option value="EX">EXTRA</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="Dis_Uni">Unidad <span class="text-danger">*</span></label>
                                <select class="form-control" id="Dis_Uni" name="Dis_Uni" required>
                                    <option value="">- Seleccione -</option>
                                    <option value="GA">GALONES</option>
                                    <option value="LI">LITROS</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardar(this);"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- =============================== -->
    <!-- MODAL CARGA (FASE 2)            -->
    <!-- =============================== -->
    <div id="modalFormularioIngreso" class="modal fade" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-arrow-down"></i> Carga Interna al Dispensador</h4>
                </div>
                <div class="modal-body">
                    <form id="formIngreso">
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="Did_Tip">Tipo de Ingreso <span class="text-danger">*</span></label>
                                <select class="form-control" id="Did_Tip" name="Did_Tip" required onchange="cambiarTipoIngreso()">
                                    <option value="">-- Seleccione --</option>
                                    <option value="IN">IN - Compra a proveedor</option>
                                    <option value="IC">IC - Ingreso consignado</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="Dis_Cod_In">Dispensador <span class="text-danger">*</span></label>
                                <select class="form-control" id="Dis_Cod_In" name="Dis_Cod" required onchange="cargarInfoDispensador(this.value)">
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($dispensadores as $d) {
                                        echo "<option value='{$d['Dis_Cod']}'>{$d['Dis_Nom']}</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row" id="infoDispensadorBox" style="display:none;">
                            <div class="col-md-12">
                                <div class="info-box">
                                    <div class="row">
                                        <div class="col-xs-6"><span class="info-label">Combustible:</span> <span class="info-value" id="lbl_Dis_Tip"></span></div>
                                        <div class="col-xs-6"><span class="info-label">Unidad:</span> <span class="info-value" id="lbl_Dis_Uni"></span></div>
                                    </div>
                                    <div class="row" style="margin-top: 5px;">
                                        <div class="col-xs-6"><span class="info-label">Capacidad Total:</span> <span class="info-value text-primary" id="lbl_Dis_Cap">0</span></div>
                                        <div class="col-xs-6"><span class="info-label">Existencia Actual:</span> <span class="info-value text-warning" id="lbl_Dis_Ext">0</span></div>
                                    </div>
                                    <div class="row" style="margin-top: 5px;">
                                        <div class="col-xs-12"><span class="info-label">Espacio Disponible:</span> <strong class="info-value text-success" id="lbl_Dis_Dispo">0</strong></div>
                                    </div>
                                    <input type="hidden" id="capacidad_disponible" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="row" id="div_proveedor" style="display:none;">
                            <div class="col-md-12 form-group">
                                <label for="Prv_Cod_In">Proveedor <span class="text-danger">*</span></label>
                                <select class="form-control" id="Prv_Cod_In" name="Prv_Cod">
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($proveedores as $p) {
                                        echo "<option value='{$p['Prv_Cod']}'>{$p['proveedor_nombre']}</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row" id="div_vehiculo" style="display:none;">
                            <div class="col-md-12 form-group">
                                <label for="Veh_Cod_In">Veh&iacute;culo Consignado <span class="text-danger">*</span></label>
                                <select class="form-control" id="Veh_Cod_In" name="Veh_Cod">
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($vehiculos as $v) {
                                        echo "<option value='{$v['Veh_Cod']}'>{$v['vehiculo_nombre']}</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="Did_Fec">Fecha y Hora de Ingreso <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="Did_Fec" name="Did_Fec" required value="<?php echo date('Y-m-d\TH:i'); ?>" max="9999-12-31T23:59">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="Did_Can">Cantidad <span class="text-danger">*</span></label>
                                <input type="number" class="form-control text-right" id="Did_Can" name="Did_Can" step="0.01" min="0.01" required placeholder="0.00" onkeyup="calcularTotal()">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="Did_Pun">Precio Unitario <span class="text-danger">*</span></label>
                                <input type="number" class="form-control text-right" id="Did_Pun" name="Did_Pun" step="0.01" min="0" required placeholder="0.00" onkeyup="calcularTotal()">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 form-group text-right">
                                <label>Total Visual:</label>
                                <h3 style="margin: 0; color: #d9534f;">$ <span id="lbl_Total">0.00</span></h3>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarIngreso(this);"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- =============================== -->
    <!-- MODAL DESPACHO (FASE 3)         -->
    <!-- =============================== -->
    <div id="modalFormularioDespacho" class="modal fade" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-arrow-up"></i> Registrar Salida de Combustible</h4>
                </div>
                <div class="modal-body">
                    <form id="formDespacho">
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="Did_Tip_Out">Tipo de Salida <span class="text-danger">*</span></label>
                                <select class="form-control" id="Did_Tip_Out" name="Did_Tip" required onchange="cambiarTipoSalida()">
                                    <option value="">-- Seleccione --</option>
                                    <option value="SA">SA - Abastecimiento a Maquinaria</option>
                                    <option value="SC">SC - Ajuste Negativo / Correcci&oacute;n</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="Dis_Cod_Out">Dispensador <span class="text-danger">*</span></label>
                                <select class="form-control" id="Dis_Cod_Out" name="Dis_Cod" required onchange="cargarInfoDispensadorOut(this.value)">
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($dispensadores as $d) {
                                        echo "<option value='{$d['Dis_Cod']}'>{$d['Dis_Nom']}</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row" id="infoDispensadorBoxOut" style="display:none;">
                            <div class="col-md-12">
                                <div class="info-box">
                                    <div class="row">
                                        <div class="col-xs-6"><span class="info-label">Combustible:</span> <span class="info-value" id="lbl_Dis_Tip_Out"></span></div>
                                        <div class="col-xs-6"><span class="info-label">Unidad:</span> <span class="info-value" id="lbl_Dis_Uni_Out"></span></div>
                                    </div>
                                    <div class="row" style="margin-top: 5px;">
                                        <div class="col-xs-6"><span class="info-label">Capacidad Total:</span> <span class="info-value text-primary" id="lbl_Dis_Cap_Out">0</span></div>
                                        <div class="col-xs-6"><span class="info-label">Existencia Actual:</span> <span class="info-value text-warning" id="lbl_Dis_Ext_Out">0</span></div>
                                    </div>
                                    <div class="row" style="margin-top: 5px;">
                                        <div class="col-xs-12"><span class="info-label">Existencia Posterior:</span> <strong class="info-value text-success" id="lbl_Dis_ExtPost_Out">0</strong></div>
                                    </div>
                                    <input type="hidden" id="existencia_actual_out" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="row" id="div_vehiculo_out" style="display:none;">
                            <div class="col-md-12 form-group">
                                <label for="Veh_Cod_Out">Maquinaria / Veh&iacute;culo <span class="text-danger">*</span></label>
                                <select class="form-control" id="Veh_Cod_Out" name="Veh_Cod">
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($vehiculos as $v) {
                                        echo "<option value='{$v['Veh_Cod']}'>{$v['vehiculo_nombre']}</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row" id="div_motivo_out" style="display:none;">
                            <div class="col-md-12 form-group">
                                <label for="Did_Obs_Out">Motivo del Ajuste <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="Did_Obs_Out" name="Did_Obs" maxlength="250" placeholder="Ej: Derrame, Merma, Limpieza...">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="Did_Fec_Out">Fecha y Hora <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="Did_Fec_Out" name="Did_Fec" required value="<?php echo date('Y-m-d\TH:i'); ?>" max="9999-12-31T23:59">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="Did_Can_Out">Cantidad <span class="text-danger">*</span></label>
                                <input type="number" class="form-control text-right" id="Did_Can_Out" name="Did_Can" step="0.01" min="0.01" required placeholder="0.00" onkeyup="calcularExistenciaPosterior()">
                            </div>
                            <div class="col-md-4 form-group" id="div_precio_out">
                                <label for="Did_Pun_Out">Precio Referencial</label>
                                <input type="number" class="form-control text-right" id="Did_Pun_Out" name="Did_Pun" step="0.01" min="0" placeholder="0.00" onkeyup="calcularTotalOut()">
                            </div>
                        </div>

                        <div class="row" id="div_total_out">
                            <div class="col-md-12 form-group text-right">
                                <label>Total Referencial:</label>
                                <h3 style="margin: 0; color: #d9534f;">$ <span id="lbl_Total_Out">0.00</span></h3>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarDespacho(this);"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- =============================== -->
    <!-- MODAL AJUSTE (FASE 4A)          -->
    <!-- =============================== -->
    <div id="modalFormularioAjuste" class="modal fade" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-wrench"></i> Registrar Ajuste de Inventario</h4>
                </div>
                <div class="modal-body">
                    <form id="formAjuste">
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="Did_Tip_Aj">Tipo de Ajuste <span class="text-danger">*</span></label>
                                <select class="form-control" id="Did_Tip_Aj" name="Did_Tip" required onchange="cambiarTipoAjuste()">
                                    <option value="">-- Seleccione --</option>
                                    <option value="IC">IC - Ajuste Positivo (Aumentar)</option>
                                    <option value="SC">SC - Ajuste Negativo (Disminuir)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="Dis_Cod_Aj">Dispensador <span class="text-danger">*</span></label>
                                <select class="form-control" id="Dis_Cod_Aj" name="Dis_Cod" required onchange="cargarInfoDispensadorAj(this.value)">
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($dispensadores as $d) {
                                        echo "<option value='{$d['Dis_Cod']}'>{$d['Dis_Nom']}</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row" id="infoDispensadorBoxAj" style="display:none;">
                            <div class="col-md-12">
                                <div class="info-box">
                                    <div class="row">
                                        <div class="col-xs-6"><span class="info-label">Combustible:</span> <span class="info-value" id="lbl_Dis_Tip_Aj"></span></div>
                                        <div class="col-xs-6"><span class="info-label">Unidad:</span> <span class="info-value" id="lbl_Dis_Uni_Aj"></span></div>
                                    </div>
                                    <div class="row" style="margin-top: 5px;">
                                        <div class="col-xs-6"><span class="info-label">Capacidad Total:</span> <span class="info-value text-primary" id="lbl_Dis_Cap_Aj">0</span></div>
                                        <div class="col-xs-6"><span class="info-label">Existencia Actual:</span> <span class="info-value text-warning" id="lbl_Dis_Ext_Aj">0</span></div>
                                    </div>
                                    <div class="row" style="margin-top: 5px;">
                                        <div class="col-xs-12"><span class="info-label">Existencia Posterior:</span> <strong class="info-value text-success" id="lbl_Dis_ExtPost_Aj">0</strong></div>
                                    </div>
                                    <input type="hidden" id="existencia_actual_aj" value="0">
                                    <input type="hidden" id="capacidad_total_aj" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="Did_Obs_Aj">Motivo / Observaci&oacute;n del Ajuste <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="Did_Obs_Aj" name="Did_Obs" maxlength="250" placeholder="Ej: Medici&oacute;n de varilla, limpieza de tanque..." required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="Did_Fec_Aj">Fecha y Hora <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="Did_Fec_Aj" name="Did_Fec" required value="<?php echo date('Y-m-d\TH:i'); ?>" max="9999-12-31T23:59">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="Did_Can_Aj">Cantidad <span class="text-danger">*</span></label>
                                <input type="number" class="form-control text-right" id="Did_Can_Aj" name="Did_Can" step="0.01" min="0.01" required placeholder="0.00" onkeyup="calcularExistenciaPosteriorAj()">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarAjuste(this);"><i class="fa fa-save"></i> Guardar Ajuste</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Carga de Chart.js para el Dashboard -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

    <!-- Carga de JS -->
    <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
    <script src="../VALIDACIONES/man_val_maquinaria_dispensador.js?v=10"></script>

    <!-- Liberacion y cierre de conexiones -->
    <?php
        $obBD_con1->liberar();
        $obBD_conexion->cerrar();
    ?>
</BODY>

</HTML>