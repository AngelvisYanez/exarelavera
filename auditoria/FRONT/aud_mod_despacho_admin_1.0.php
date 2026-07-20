<?php
/**
 * Gestión Operativa del Despacho - Administración
 * Servicios, Actividades, Clientes del despacho
 * @author Sistema EXA | @version 1.0
 */
if (!empty($_GET['debug'])) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/aud_log_despacho_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Despacho($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Despacho();

$Ses_Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;
$Ses_Usu_Cod = isset($Ses_Usu_Cod) ? intval($Ses_Usu_Cod) : 0;

// ----- Ajax: Listar servicios -----
if (!empty($_REQUEST['listarServicios'])) {
    $arr = $obBD_con1->getArrayConsulta(1, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Guardar servicio -----
if (!empty($_REQUEST['guardarServicio'])) {
    $resp = array('success' => false);
    $cod = isset($_POST['Ser_Cod']) ? intval($_POST['Ser_Cod']) : 0;
    $nom = trim(isset($_POST['Ser_Nombre']) ? $_POST['Ser_Nombre'] : '');
    $desc = trim(isset($_POST['Ser_Descripcion']) ? $_POST['Ser_Descripcion'] : '');
    if ($nom === '') {
        $resp['message'] = 'El nombre del servicio es obligatorio.';
        echo json_encode($resp);
        exit;
    }
    $conn = $obBD_conexion->conexion;
    mysqli_set_charset($conn, 'utf8');
    $nom_safe = mysqli_real_escape_string($conn, $nom);
    $desc_safe = mysqli_real_escape_string($conn, $desc);
    if ($cod > 0) {
        $obBD_con1->operacionobBD(4, array('Ser_Cod' => $cod, 'Ser_Nombre_safe' => $nom_safe, 'Ser_Descripcion_safe' => $desc_safe), $obBD_conexion);
    } else {
        $obBD_con1->operacionobBD(3, array('Ser_Nombre_safe' => $nom_safe, 'Ser_Descripcion_safe' => $desc_safe, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    }
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Listar actividades -----
if (!empty($_REQUEST['listarActividades'])) {
    $ser = isset($_REQUEST['Ser_Cod']) ? intval($_REQUEST['Ser_Cod']) : 0;
    $par = array('Emp_Cod' => $Ses_Emp_Cod);
    if ($ser > 0) $par['Ser_Cod'] = $ser;
    $arr = $obBD_con1->getArrayConsulta(2, $par, $obBD_conexion);
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Eliminar actividad (soft delete) -----
if (!empty($_REQUEST['eliminarActividad'])) {
    $resp = array('success' => false);
    $cod = isset($_POST['Act_Cod']) ? intval($_POST['Act_Cod']) : 0;
    if ($cod <= 0) {
        $resp['message'] = 'Código de actividad inválido.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(69, array('Act_Cod' => $cod), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->getMsgError();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Guardar actividad -----
if (!empty($_REQUEST['guardarActividad'])) {
    $resp = array('success' => false);
    $cod = isset($_POST['Act_Cod']) ? intval($_POST['Act_Cod']) : 0;
    $ser = intval(isset($_POST['Ser_Cod']) ? $_POST['Ser_Cod'] : 0);
    $nom = trim(isset($_POST['Act_Nombre']) ? $_POST['Act_Nombre'] : '');
    $tipo = isset($_POST['Act_Tipo']) ? $_POST['Act_Tipo'] : 'MENSUAL';
    $prior = isset($_POST['Act_Prioridad']) ? $_POST['Act_Prioridad'] : 'MEDIA';
    $rec = isset($_POST['Act_Recurrente']) ? $_POST['Act_Recurrente'] : 'S';
    $desc = trim(isset($_POST['Act_Descripcion']) ? $_POST['Act_Descripcion'] : '');
    $usa_ruc = (isset($_POST['Act_Usa_Ruc']) && $_POST['Act_Usa_Ruc'] === 'S') ? 'S' : 'N';
    $meses_anual = trim(isset($_POST['Act_Meses_Anual']) ? $_POST['Act_Meses_Anual'] : '');
    if ($nom === '' || $ser <= 0) {
        $resp['message'] = 'Nombre y servicio son obligatorios.';
        echo json_encode($resp);
        exit;
    }
    $conn = $obBD_conexion->conexion;
    mysqli_set_charset($conn, 'utf8');
    $nom_safe = mysqli_real_escape_string($conn, $nom);
    $desc_safe = mysqli_real_escape_string($conn, $desc);
    $par_base = array('Act_Nombre_safe' => $nom_safe, 'Act_Tipo' => $tipo, 'Act_Prioridad' => $prior, 'Act_Recurrente' => $rec, 'Act_Descripcion_safe' => $desc_safe, 'Act_Usa_Ruc' => $usa_ruc, 'Act_Meses_Anual' => $meses_anual);
    if ($cod > 0) {
        $obBD_con1->operacionobBD(6, array_merge($par_base, array('Act_Cod' => $cod)), $obBD_conexion);
    } else {
        $obBD_con1->operacionobBD(5, array_merge($par_base, array('Ser_Cod' => $ser)), $obBD_conexion);
    }
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Listar clientes despacho -----
if (!empty($_REQUEST['listarClientesDespacho'])) {
    $est = isset($_REQUEST['Dcl_Est']) ? trim($_REQUEST['Dcl_Est']) : '';
    $par = array('Emp_Cod' => $Ses_Emp_Cod);
    if ($est !== '') $par['Dcl_Est'] = $est;
    $arr = $obBD_con1->getArrayConsulta(7, $par, $obBD_conexion);
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Buscar clientes (para agregar a despacho) -----
if (!empty($_REQUEST['buscarClientes'])) {
    $bus = trim(isset($_REQUEST['search']) ? $_REQUEST['search'] : '');
    $arr = $obBD_con1->getArrayConsulta(10, array('Emp_Cod' => $Ses_Emp_Cod, 'search' => $bus), $obBD_conexion);
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Agregar cliente a despacho -----
if (!empty($_REQUEST['agregarClienteDespacho'])) {
    $resp = array('success' => false);
    $cli = intval(isset($_POST['Cli_Cod']) ? $_POST['Cli_Cod'] : 0);
    if ($cli <= 0) {
        $resp['message'] = 'Debe seleccionar un cliente.';
        echo json_encode($resp);
        exit;
    }
    $existe = $obBD_con1->getRowConsulta(11, array('Cli_Cod' => $cli, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    if (!empty($existe)) {
        $resp['message'] = 'Este cliente ya está registrado en el despacho.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(8, array('Cli_Cod' => $cli, 'Emp_Cod' => $Ses_Emp_Cod, 'Dcl_Est' => 'ACTIVO'), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Listar regimenes -----
if (!empty($_REQUEST['listarRegimenes'])) {
    $arr = $obBD_con1->getArrayConsulta(60, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Guardar regimen -----
if (!empty($_REQUEST['guardarRegimen'])) {
    $resp = array('success' => false);
    $cod = isset($_POST['Reg_Cod']) ? intval($_POST['Reg_Cod']) : 0;
    $nom = trim(isset($_POST['Reg_Nombre']) ? $_POST['Reg_Nombre'] : '');
    $desc = trim(isset($_POST['Reg_Descripcion']) ? $_POST['Reg_Descripcion'] : '');
    if ($nom === '') {
        $resp['message'] = 'El nombre del régimen es obligatorio.';
        echo json_encode($resp);
        exit;
    }
    $conn = $obBD_conexion->conexion;
    mysqli_set_charset($conn, 'utf8');
    $nom_safe = mysqli_real_escape_string($conn, $nom);
    $desc_safe = mysqli_real_escape_string($conn, $desc);
    if ($cod > 0) {
        $obBD_con1->operacionobBD(62, array('Reg_Cod' => $cod, 'Reg_Nombre_safe' => $nom_safe, 'Reg_Descripcion_safe' => $desc_safe), $obBD_conexion);
    } else {
        $obBD_con1->operacionobBD(61, array('Reg_Nombre_safe' => $nom_safe, 'Reg_Descripcion_safe' => $desc_safe, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    }
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Actividades por regimen -----
if (!empty($_REQUEST['actividadesRegimen'])) {
    $reg = isset($_REQUEST['Reg_Cod']) ? intval($_REQUEST['Reg_Cod']) : 0;
    $ser = isset($_REQUEST['Ser_Cod']) ? intval($_REQUEST['Ser_Cod']) : 0;
    $par = array('Reg_Cod' => $reg);
    if ($ser > 0) $par['Ser_Cod'] = $ser;
    $arr = $obBD_con1->getArrayConsulta(63, $par, $obBD_conexion);
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Agregar actividad a regimen -----
if (!empty($_REQUEST['agregarActividadRegimen'])) {
    $resp = array('success' => false);
    $reg = intval(isset($_POST['Reg_Cod']) ? $_POST['Reg_Cod'] : 0);
    $ser = intval(isset($_POST['Ser_Cod']) ? $_POST['Ser_Cod'] : 0);
    $act = intval(isset($_POST['Act_Cod']) ? $_POST['Act_Cod'] : 0);
    if ($reg <= 0 || $ser <= 0 || $act <= 0) {
        $resp['message'] = 'Régimen, servicio y actividad son obligatorios.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(64, array('Reg_Cod' => $reg, 'Ser_Cod' => $ser, 'Act_Cod' => $act), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Quitar actividad de regimen -----
if (!empty($_REQUEST['quitarActividadRegimen'])) {
    $resp = array('success' => false);
    $regact = intval(isset($_POST['RegAct_Cod']) ? $_POST['RegAct_Cod'] : 0);
    if ($regact <= 0) {
        $resp['message'] = 'Datos inválidos.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(65, array('RegAct_Cod' => $regact), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Guardar precios por actividad -----
if (!empty($_REQUEST['guardarPreciosActividad'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $resp = array('success' => false);
    $act = intval(isset($_POST['Act_Cod']) ? $_POST['Act_Cod'] : 0);
    $peq = floatval(str_replace(',', '.', isset($_POST['Precio_Pequeno']) ? $_POST['Precio_Pequeno'] : 0));
    $med = floatval(str_replace(',', '.', isset($_POST['Precio_Mediano']) ? $_POST['Precio_Mediano'] : 0));
    $gra = floatval(str_replace(',', '.', isset($_POST['Precio_Grande']) ? $_POST['Precio_Grande'] : 0));
    if ($act <= 0) {
        $resp['message'] = 'Actividad inválida.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->setError(0, '');
    $obBD_con1->operacionobBD(68, array('Act_Cod' => $act, 'Tipo_Empresa' => 'PEQUENO', 'Precio' => $peq), $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $resp['message'] = $obBD_con1->getMsgError() ?: 'Error al guardar precio Pequeño.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(68, array('Act_Cod' => $act, 'Tipo_Empresa' => 'MEDIANO', 'Precio' => $med), $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $resp['message'] = $obBD_con1->getMsgError() ?: 'Error al guardar precio Mediano.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(68, array('Act_Cod' => $act, 'Tipo_Empresa' => 'GRANDE', 'Precio' => $gra), $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $resp['message'] = $obBD_con1->getMsgError() ?: 'Error al guardar precio Grande.';
        echo json_encode($resp);
        exit;
    }
    $resp['success'] = true;
    $resp['message'] = 'Precios guardados correctamente.';
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Actualizar estado cliente despacho -----
if (!empty($_REQUEST['actualizarClienteDespacho'])) {
    $resp = array('success' => false);
    $dcl = intval(isset($_POST['Dcl_Cod']) ? $_POST['Dcl_Cod'] : 0);
    $est = trim(isset($_POST['Dcl_Est']) ? $_POST['Dcl_Est'] : 'ACTIVO');
    if ($dcl <= 0 || !in_array($est, array('ACTIVO', 'SUSPENDIDO', 'FINALIZADO'))) {
        $resp['message'] = 'Datos inválidos.';
        echo json_encode($resp);
        exit;
    }
    $reg = isset($_POST['Reg_Cod']) ? intval($_POST['Reg_Cod']) : 0;
    $tipo_emp = isset($_POST['Dcl_Tipo_Empresa']) && in_array($_POST['Dcl_Tipo_Empresa'], array('PEQUENO','MEDIANO','GRANDE')) ? $_POST['Dcl_Tipo_Empresa'] : '';
    $obBD_con1->operacionobBD(9, array('Dcl_Cod' => $dcl, 'Dcl_Est' => $est, 'Reg_Cod' => $reg, 'Dcl_Tipo_Empresa' => $tipo_emp), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    echo json_encode($resp);
    exit;
}

$lista_servicios = $obBD_con1->getArrayConsulta(1, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
$lista_actividades = $obBD_con1->getArrayConsulta(2, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
$lista_clientes = $obBD_con1->getArrayConsulta(7, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
$lista_regimenes = $obBD_con1->getArrayConsulta(60, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
$lista_actividades_precios = array();
try {
    $lista_actividades_precios = $obBD_con1->getArrayConsulta(67, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'EXA'; ?> - Gestión Despacho (Admin)</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <?php require_once('../../mascaras/model1/estilos/estilos.php'); ?>
    <link href="aud_zoom.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script type="text/javascript" src="../../Librerias/jquery.min/jquery-1.11.3.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="../../mascaras/model1/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/aud_par_despacho_1.0.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/aud_val_despacho_1.0.js"></script>
    <style>
        .despacho-admin-container { padding: 20px; background: #E8F0F7; min-height: 100vh; }
        .exa-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 50%, #5A9BD4 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(44,93,148,0.3);
            margin-bottom: 20px;
        }
        .exa-header h3 { margin: 0; font-size: 18px; font-weight: 600; letter-spacing: 0.3px; }
        .config-card {
            background: white;
            border: none;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s ease;
        }
        .config-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .config-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 8px 8px 0 0;
            margin: -18px -18px 16px -18px;
            font-size: 14px;
        }
        .config-header h4 { margin: 0; font-size: 14px; font-weight: 600; }
        .config-header h4 .glyphicon { margin-right: 6px; opacity: 0.9; }
        .form-group .text-danger { color: #ef4444 !important; }
        .tabs-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .tabs-wrapper .nav-tabs {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label { font-weight: 600; color: #334155; margin-bottom: 8px; display: block; font-size: 13px; } 
        .form-control {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #fff;
        }
        .form-control:hover { border-color: #94a3b8; }
        .form-control:focus {
            border-color: #2C5D94;
            box-shadow: 0 0 0 3px rgba(44,93,148,0.15);
            outline: none;
        }
        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 56px;
        }
        .nav-tabs {
            border-bottom: 2px solid #e2e8f0;
            margin: 0;
            padding: 10px 16px 0 16px;
            background: #f8fafc;
            display: flex;
            flex-wrap: nowrap;
            list-style: none;
        }
        .nav-tabs > li {
            margin-bottom: -2px;
            margin-right: 4px;
            float: none;
            flex-shrink: 0;
        }
        .nav-tabs > li > a {
            display: inline-block;
            color: #475569;
            font-weight: 600;
            font-size: 13px;
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
            background: #e2e8f0;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .nav-tabs > li > a:hover {
            background: #DEE7EF;
            color: #2C5D94;
            border-color: #cbd5e1;
        }
        .nav-tabs > li.active > a,
        .nav-tabs > li.active > a:hover,
        .nav-tabs > li.active > a:focus {
            background: #3d7bb8;
            color: white;
            border-color: #2C5D94;
            border-bottom: 2px solid #2C5D94;
            margin-bottom: -2px;
        }
        .tab-content {
            padding: 24px;
            background: #E8F0F7;
        }
        .tab-pane {
            background: transparent;
            display: none;
        }
        .tab-pane.active {
            display: block;
        }
        .aud-tabla {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-top: 14px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            background: white;
        }
        .aud-tabla thead th {
            background: linear-gradient(135deg, #72A1CF 0%, #8EB7DD 100%);
            color: white;
            padding: 12px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .aud-tabla thead th:first-child { width: 80px; }
        .aud-tabla thead th.col-accion { width: 120px; text-align: center; }
        #gridRegimenes thead th.col-accion,
        #gridRegimenes .col-accion { width: 220px; min-width: 220px; }
        /* Solo el panel Actividades del régimen - letra más grande */
        #panelActividadesRegimen h5 { font-size: 16px; font-weight: 600; }
        #panelActividadesRegimen .form-control { font-size: 14px; }
        #panelActividadesRegimen .btn { font-size: 14px; }
        #gridActividadesRegimen { font-size: 14px; }
        #gridActividadesRegimen thead th { font-size: 13px; padding: 12px 14px; }
        #gridActividadesRegimen tbody td { font-size: 14px; padding: 12px 14px; }
        .quitar-act-regimen { font-size: 18px !important; line-height: 1; padding: 6px 10px !important; min-width: 36px; }
        .aud-tabla tbody td { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; }
        .aud-tabla tbody tr:hover { background-color: #D1E6F4; }
        .aud-tabla tbody tr.empty-row:hover { background-color: transparent; }
        .aud-tabla tbody tr:last-child td { border-bottom: none; }
        .aud-tabla .col-accion { text-align: center; }
        .aud-tabla .btn-xs.btn-info {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            border: none;
            border-radius: 6px;
            padding: 5px 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .aud-tabla .btn-xs.btn-info:hover {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            transform: translateY(-1px);
        }
        .est-activo { color: #10b981; font-weight: 600; }
        .est-suspendido { color: #f59e0b; font-weight: 500; }
        .est-finalizado { color: #64748b; }
        .btn-editar {
            border-radius: 6px;
            font-weight: 600;
            padding: 8px 16px;
            font-size: 13px;
            transition: all 0.2s ease;
            border: none;
        }
        .btn-primary.btn-editar {
            background: linear-gradient(135deg, #5CB85C 0%, #4cae4c 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(92,184,92,0.35);
        }
        .btn-primary.btn-editar:hover {
            background: linear-gradient(135deg, #4cae4c 0%, #449d44 100%);
            box-shadow: 0 4px 12px rgba(92,184,92,0.45);
            transform: translateY(-1px);
        }
        .btn-default.btn-editar {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-default.btn-editar:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
        }
        .btn-group-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px; }
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #64748b;
            font-size: 14px;
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            margin-top: 16px;
        }
        .empty-state .glyphicon { font-size: 40px; margin-bottom: 16px; opacity: 0.4; color: #94a3b8; }
        .empty-state strong { display: block; margin-top: 8px; color: #475569; }
        .filtro-clientes {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 12px;
        }
        .filtro-clientes .form-control { border-radius: 6px; max-width: 100%; }
        .filtro-clientes .form-inline { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
        .cambiar-estado-cli { font-size: 12px; padding: 4px 8px; border-radius: 6px; min-width: 110px; }
        .form-seccion {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            padding: 18px 20px;
            border-radius: 10px;
            margin-bottom: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .form-seccion .form-group:last-of-type { margin-bottom: 0; }
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 24px;
        }
        @media (max-width: 768px) { .form-grid-2 { grid-template-columns: 1fr; } }
        .form-grid-2 .form-group { margin-bottom: 0; }
        /* Formularios apilados: label e input alineados a la izquierda */
        .form-stack-aligned .form-row-aligned {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 8px 16px;
            align-items: start;
            margin-bottom: 14px;
        }
        .form-stack-aligned .form-row-aligned:last-of-type { margin-bottom: 0; }
        .form-stack-aligned .form-row-aligned label {
            text-align: left;
            margin: 0;
            font-weight: 600;
            padding-top: 6px;
        }
        .form-stack-aligned .form-row-aligned .form-control {
            width: 50%;
            max-width: 50%;
            min-width: 0;
        }
        .form-row-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-start;
        }
        .form-row-inline .form-group { margin-bottom: 0; flex: 1; min-width: 120px; max-width: 160px; }
        .form-control-moderno { max-width: 280px; }
        .form-grid-2 .form-control-moderno { max-width: none; }
        .btn-group-actions { margin-top: 4px; }
        .form-actividad-grid {
            display: flex;
            flex-direction: column;
            gap: 0;
            max-width: 100%;
        }
        .form-actividad-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 16px;
            align-items: center;
            min-height: 38px;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .form-actividad-row:last-of-type { border-bottom: none; }
        .form-actividad-row label {
            margin: 0;
            font-weight: 600;
            font-size: 13px;
            color: #334155;
        }
        .form-actividad-row .form-control { width: 100%; box-sizing: border-box; }
        .form-actividad-row textarea.form-control { min-height: 60px; resize: vertical; }
        .form-actividad-row.form-actividad-row-full .form-control { width: 100%; }
        /* Modal: selects e inputs al mismo ancho */
        #modalActividad .form-actividad-row { grid-template-columns: 110px 1fr; }
        #modalActividad .form-actividad-row .form-control,
        #modalActividad .form-actividad-row select.form-control,
        #modalActividad .form-actividad-row input.form-control { width: 100%; min-width: 0; }
        .form-actividad-row-inline {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 16px;
            align-items: center;
            padding: 12px 0;
        }
        .form-actividad-row-inline > div { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .form-actividad-row-inline label { margin: 0; font-weight: 500; }
        .form-actividad-grid .btn-group-actions { display: inline-flex; gap: 8px; margin: 0; }
        .form-actividad-grid .btn-group-actions .btn { padding: 5px 12px; font-size: 12px; }
        /* Modal flotante - oculto por defecto, centrado al mostrar */
        #modalActividad {
            display: none !important;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 1050;
            overflow-x: hidden;
            overflow-y: auto;
            outline: 0;
        }
        #modalActividad.modal-in { display: flex !important; align-items: center; justify-content: center; }
        #modalActividad .modal-dialog {
            position: relative;
            width: 480px;
            max-width: 95vw;
            margin: 20px auto;
        }
        #modalActividad .modal-content {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        #modalActividad .modal-body { padding: 20px 24px; }
        #modalActividad .modal-header {
            position: relative;
            padding: 14px 40px 14px 20px;
            border-radius: 10px 10px 0 0;
        }
        #modalActividad .modal-header .close {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            margin: 0;
            padding: 4px 8px;
            font-size: 20px;
            line-height: 1;
        }
        #modalActividad .modal-title {
            text-align: center;
            width: 100%;
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        #modalActividad .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 0 0 10px 10px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        #modalActividad .modal-footer .btn {
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease;
            border: none;
        }
        #modalActividad .modal-footer .btn .glyphicon { margin-right: 6px; }
        #modalActividad .modal-footer .btn-default {
            background: #fff;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        #modalActividad .modal-footer .btn-default:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #334155;
        }
        #modalActividad .modal-footer .btn-primary {
            background: linear-gradient(135deg, #3d7bb8 0%, #2C5D94 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(13,148,136,0.35);
        }
        #modalActividad .modal-footer .btn-primary:hover {
            background: linear-gradient(135deg, #2C5D94 0%, #1e4a75 100%);
            box-shadow: 0 4px 12px rgba(13,148,136,0.45);
            transform: translateY(-1px);
        }
        #backdropActividad { cursor: pointer; }
        /* Modal Editar Cliente Despacho - mismo estilo flotante */
        #modalEditarClienteDespacho {
            display: none !important;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 1050;
            overflow-x: hidden;
            overflow-y: auto;
        }
        #modalEditarClienteDespacho.modal-in { display: flex !important; align-items: center; justify-content: center; }
        #modalEditarClienteDespacho .modal-dialog { width: 420px; max-width: 95vw; margin: 20px auto; }
        #modalEditarClienteDespacho .modal-content { background: #fff; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        #modalEditarClienteDespacho .modal-body { padding: 20px 24px; }
        #backdropClienteDespacho { cursor: pointer; }
        .aud-tabla-precios tbody td { padding: 4px 10px; line-height: 1.3; }
        .aud-tabla-precios thead th { padding: 6px 10px; }
        .aud-tabla-precios .col-actividad { max-width: 32%; width: 32%; }
        .aud-tabla-precios .col-tipo { width: 90px; max-width: 90px; white-space: nowrap; }
        .aud-tabla-precios .col-precio { text-align: right; width: 100px; }
        .aud-tabla-precios .precio-input { padding: 4px 8px; height: 28px; font-size: 12px; }
        .aud-tabla-precios .ser-grupo { background: #f8fafc; font-weight: 600; color: #2C5D94; }
        #msgPrecios { margin-top: 12px; padding: 10px 16px; border-radius: 8px; display: none; }
        #msgPrecios.msg-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; display: block; }
        #msgPrecios.msg-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; display: block; }
    </style>
</head>
<body>
<div id="set1" class="container-fluid despacho-admin-container">
    <div class="exa-header">
        <h3><span class="glyphicon glyphicon-cog"></span> Gestión Operativa del Despacho - Administración</h3>
    </div>

    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#tab-servicios" data-toggle="tab"><span class="glyphicon glyphicon-list-alt"></span> Servicios</a></li>
        <li role="presentation"><a href="#tab-actividades" data-toggle="tab"><span class="glyphicon glyphicon-tasks"></span> Actividades</a></li>
        <li role="presentation"><a href="#tab-regimenes" data-toggle="tab"><span class="glyphicon glyphicon-book"></span> Regímenes</a></li>
        <li role="presentation"><a href="#tab-clientes" data-toggle="tab"><span class="glyphicon glyphicon-user"></span> Clientes del Despacho</a></li>
        <li role="presentation"><a href="#tab-precios" data-toggle="tab"><span class="glyphicon glyphicon-usd"></span> Precios por Actividad</a></li>
    </ul>

    <div class="tab-content">
        <!-- Servicios -->
        <div role="tabpanel" class="tab-pane active" id="tab-servicios">
            <div class="config-card">
                <div class="config-header"><h4><span class="glyphicon glyphicon-list-alt"></span> Catálogo de Servicios</h4></div>
                <div class="form-seccion">
                    <form id="formServicio" class="form-horizontal">
                        <input type="hidden" name="Ser_Cod" id="Ser_Cod" value="" />
                        <div class="form-stack-aligned">
                            <div class="form-group form-row-aligned">
                                <label class="control-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="Ser_Nombre" id="Ser_Nombre" class="form-control" maxlength="100" placeholder="Ej: IESS, SRI, SUPERCIAS" />
                            </div>
                            <div class="form-group form-row-aligned">
                                <label class="control-label">Descripción</label>
                                <textarea name="Ser_Descripcion" id="Ser_Descripcion" class="form-control" rows="2" placeholder="Opcional"></textarea>
                            </div>
                        </div>
                        <div class="form-group btn-group-actions">
                            <button type="button" class="btn btn-primary btn-editar" id="btnGuardarServicio"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                            <button type="reset" class="btn btn-default btn-editar"><span class="glyphicon glyphicon-plus"></span> Nuevo</button>
                        </div>
                    </form>
                </div>
                <table id="gridServicios" class="aud-tabla">
                    <thead><tr><th>Código</th><th>Nombre</th><th>Descripción</th><th class="col-accion">Acción</th></tr></thead>
                    <tbody>
                    <?php if (empty($lista_servicios)): ?>
                    <tr class="empty-row"><td colspan="4" style="padding:0;"><div class="empty-state"><span class="glyphicon glyphicon-inbox"></span><br/>No hay servicios registrados.<strong>Agregue el primero usando el formulario de arriba.</strong></div></td></tr>
                    <?php else: foreach ($lista_servicios as $s): ?>
                    <tr><td><?php echo $s['Ser_Cod']; ?></td><td><?php echo htmlspecialchars($s['Ser_Nombre']); ?></td><td><?php echo htmlspecialchars(isset($s['Ser_Descripcion']) ? $s['Ser_Descripcion'] : '-'); ?></td>
                        <td class="col-accion"><button type="button" class="btn btn-xs btn-editar-modificar editar-servicio" title="Editar" data-cod="<?php echo $s['Ser_Cod']; ?>" data-nom="<?php echo htmlspecialchars($s['Ser_Nombre']); ?>" data-desc="<?php echo htmlspecialchars(isset($s['Ser_Descripcion']) ? $s['Ser_Descripcion'] : ''); ?>"><span class="glyphicon glyphicon-pencil"></span></button></td></tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Actividades -->
        <div role="tabpanel" class="tab-pane" id="tab-actividades">
            <div class="config-card">
                <div class="config-header"><h4><span class="glyphicon glyphicon-tasks"></span> Catálogo de Actividades</h4></div>
                <div class="filtro-clientes" style="margin-bottom: 16px;">
                    <div class="form-inline">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="margin-right: 8px; font-weight: 600;">Filtrar por servicio:</label>
                            <select id="filtroServicioActividades" class="form-control" style="width: 220px;">
                                <option value="">-- Todos los servicios --</option>
                                <?php foreach ($lista_servicios as $s): ?>
                                <option value="<?php echo $s['Ser_Cod']; ?>"><?php echo htmlspecialchars($s['Ser_Nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary btn-editar" id="btnAplicarFiltroActividades"><span class="glyphicon glyphicon-filter"></span> Filtrar</button>
                        <button type="button" class="btn btn-primary btn-editar" id="btnNuevaActividad"><span class="glyphicon glyphicon-plus"></span> Nueva actividad</button>
                    </div>
                </div>
                <table id="gridActividades" class="aud-tabla">
                    <thead><tr><th>Código</th><th>Servicio</th><th>Actividad</th><th>Tipo</th><th>Prioridad</th><th>RUC</th><th class="col-accion">Acción</th></tr></thead>
                    <tbody id="tbodyActividades">
                    <?php if (empty($lista_actividades)): ?>
                    <tr class="empty-row"><td colspan="7" style="padding:0;"><div class="empty-state"><span class="glyphicon glyphicon-tasks"></span><br/>No hay actividades registradas.<strong>Haga clic en "Nueva actividad" para agregar.</strong></div></td></tr>
                    <?php else: foreach ($lista_actividades as $a): 
                        $act_usa_ruc = isset($a['Act_Usa_Ruc']) ? $a['Act_Usa_Ruc'] : 'N';
                    ?>
                    <tr><td><?php echo $a['Act_Cod']; ?></td><td><?php echo htmlspecialchars($a['Ser_Nombre']); ?></td><td><?php echo htmlspecialchars($a['Act_Nombre']); ?></td>
                        <td><?php echo $a['Act_Tipo']; ?></td><td><?php echo $a['Act_Prioridad']; ?></td>
                        <td><?php echo $act_usa_ruc === 'S' ? 'Sí' : 'No'; ?></td>
                        <td class="col-accion" style="white-space:nowrap;"><button type="button" class="btn btn-xs btn-editar-modificar editar-actividad" title="Editar" data-cod="<?php echo $a['Act_Cod']; ?>" data-ser="<?php echo $a['Ser_Cod']; ?>" data-nom="<?php echo htmlspecialchars($a['Act_Nombre']); ?>" data-tipo="<?php echo $a['Act_Tipo']; ?>" data-prior="<?php echo $a['Act_Prioridad']; ?>" data-desc="<?php echo htmlspecialchars(isset($a['Act_Descripcion']) ? $a['Act_Descripcion'] : ''); ?>" data-ruc="<?php echo $act_usa_ruc; ?>" data-meses="<?php echo htmlspecialchars(isset($a['Act_Meses_Anual']) ? $a['Act_Meses_Anual'] : ''); ?>"><span class="glyphicon glyphicon-pencil"></span></button>
                        <button type="button" class="btn btn-xs btn-danger eliminar-actividad" data-cod="<?php echo $a['Act_Cod']; ?>" data-nom="<?php echo htmlspecialchars($a['Act_Nombre']); ?>" title="Eliminar"><span aria-hidden="true">&times;</span></button></td></tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Regimenes -->
        <div role="tabpanel" class="tab-pane" id="tab-regimenes">
            <div class="config-card">
                <div class="config-header"><h4><span class="glyphicon glyphicon-book"></span> Regímenes Tributarios y Actividades por Régimen</h4></div>
                <p class="text-muted" style="margin-bottom: 20px; padding: 12px 16px; background: #f0fdfa; border-radius: 8px; border-left: 4px solid #2C5D94;"><span class="glyphicon glyphicon-info-sign"></span> Configure los regímenes (RIMPE, General, etc.) y las actividades que aplican a cada uno. Luego asigne el régimen al cliente en la pestaña Clientes.</p>
                <div class="form-seccion">
                    <form id="formRegimen" class="form-horizontal">
                        <input type="hidden" name="Reg_Cod" id="Reg_Cod" value="" />
                        <div class="form-stack-aligned">
                            <div class="form-group form-row-aligned">
                                <label class="control-label">Nombre del régimen <span class="text-danger">*</span></label>
                                <input type="text" name="Reg_Nombre" id="Reg_Nombre" class="form-control" maxlength="150" placeholder="Ej: RIMPE - Personas Naturales Negocios Populares" />
                            </div>
                            <div class="form-group form-row-aligned">
                                <label class="control-label">Descripción</label>
                                <textarea name="Reg_Descripcion" id="Reg_Descripcion" class="form-control" rows="2" placeholder="Opcional"></textarea>
                            </div>
                        </div>
                        <div class="form-group btn-group-actions">
                            <button type="button" class="btn btn-primary btn-editar" id="btnGuardarRegimen"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                            <button type="reset" class="btn btn-default btn-editar"><span class="glyphicon glyphicon-plus"></span> Nuevo</button>
                        </div>
                    </form>
                </div>
                <table id="gridRegimenes" class="aud-tabla">
                    <thead><tr><th>Código</th><th>Nombre</th><th>Descripción</th><th class="col-accion">Acción</th></tr></thead>
                    <tbody>
                    <?php if (empty($lista_regimenes)): ?>
                    <tr class="empty-row"><td colspan="4" style="padding:0;"><div class="empty-state"><span class="glyphicon glyphicon-book"></span><br/>No hay regímenes. Ejecute el script <code>aud_sql_regimen_actividades.sql</code> o agregue uno manualmente.</div></td></tr>
                    <?php else: foreach ($lista_regimenes as $r): ?>
                    <tr><td><?php echo $r['Reg_Cod']; ?></td><td><?php echo htmlspecialchars($r['Reg_Nombre']); ?></td><td><?php echo htmlspecialchars(isset($r['Reg_Descripcion']) ? $r['Reg_Descripcion'] : '-'); ?></td>
                        <td class="col-accion"><button type="button" class="btn btn-xs btn-editar-modificar editar-regimen" title="Editar" data-cod="<?php echo $r['Reg_Cod']; ?>" data-nom="<?php echo htmlspecialchars($r['Reg_Nombre']); ?>" data-desc="<?php echo htmlspecialchars(isset($r['Reg_Descripcion']) ? $r['Reg_Descripcion'] : ''); ?>"><span class="glyphicon glyphicon-pencil"></span></button>
                        <button type="button" class="btn btn-xs btn-success config-actividades-regimen btn-editar" data-cod="<?php echo $r['Reg_Cod']; ?>" data-nom="<?php echo htmlspecialchars($r['Reg_Nombre']); ?>"><span class="glyphicon glyphicon-tasks"></span> Actividades</button></td></tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <div id="panelActividadesRegimen" style="display:none; margin-top: 24px; padding: 20px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <h5 style="margin-top: 0;"><span class="glyphicon glyphicon-tasks"></span> Actividades del régimen: <strong id="lblRegimenNombre"></strong></h5>
                    <div class="form-inline" style="margin-bottom: 16px;">
                        <select id="selServicioRegimen" class="form-control" style="width: 180px;">
                            <option value="">-- Servicio --</option>
                            <?php foreach ($lista_servicios as $s): ?>
                            <option value="<?php echo $s['Ser_Cod']; ?>"><?php echo htmlspecialchars($s['Ser_Nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="selActividadRegimen" class="form-control" style="width: 280px;">
                            <option value="">-- Actividad --</option>
                        </select>
                        <button type="button" class="btn btn-primary btn-editar" id="btnAgregarActividadRegimen"><span class="glyphicon glyphicon-plus"></span> Agregar</button>
                    </div>
                    <table id="gridActividadesRegimen" class="aud-tabla">
                        <thead><tr><th>Servicio</th><th>Actividad</th><th>Tipo</th><th class="col-accion">Quitar</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Clientes Despacho -->
        <div role="tabpanel" class="tab-pane" id="tab-clientes">
            <div class="config-card">
                <div class="config-header"><h4><span class="glyphicon glyphicon-user"></span> Clientes del Despacho</h4></div>
                <p class="text-muted" style="margin-bottom: 20px; padding: 12px 16px; background: #f0fdfa; border-radius: 8px; border-left: 4px solid #2C5D94;"><span class="glyphicon glyphicon-info-sign"></span> Solo clientes con estado <strong>ACTIVO</strong> generan tareas. Agregue clientes desde el catálogo de tesorería.</p>
                <div class="filtro-clientes">
                    <div class="form-inline">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="margin-right: 8px; font-weight: 600;">Buscar:</label>
                            <input type="text" id="buscarCliente" class="form-control" placeholder="Cédula o nombre..." style="width: 220px;" />
                        </div>
                        <button type="button" class="btn btn-success btn-editar" id="btnBuscarCliente"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                        <div class="form-group" style="margin-bottom: 0;">
                            <select id="selClienteAgregar" class="form-control" style="width: 280px;">
                                <option value="">-- Seleccione cliente para agregar --</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary btn-editar" id="btnAgregarCliente"><span class="glyphicon glyphicon-plus"></span> Agregar al despacho</button>
                    </div>
                </div>
                <p class="text-muted" style="margin-bottom: 12px; font-size: 12px;"><a href="#tab-precios" data-toggle="tab"><span class="glyphicon glyphicon-usd"></span> Gestionar precios por actividad (Pequeño/Mediano/Grande)</a></p>
                <p class="text-muted" style="margin-bottom: 8px; font-size: 12px;">La tabla es informativa. Use el botón <strong>Editar</strong> para modificar Régimen, Tipo empresa o Estado.</p>
                <table id="gridClientesDespacho" class="aud-tabla">
                    <thead><tr><th>Cliente</th><th>Cédula/RUC</th><th>Régimen</th><th>Tipo Empresa</th><th>Estado</th><th>Inicio contrato</th><th>Fin contrato</th><th class="col-accion">Acción</th></tr></thead>
                    <tbody>
                    <?php if (empty($lista_clientes)): ?>
                    <tr class="empty-row"><td colspan="8" style="padding:0;"><div class="empty-state"><span class="glyphicon glyphicon-user"></span><br/>No hay clientes en el despacho.<strong>Busque en el catálogo de tesorería y agregue clientes arriba.</strong></div></td></tr>
                    <?php else: foreach ($lista_clientes as $c): 
                        $reg_cod = isset($c['Reg_Cod']) ? (int)$c['Reg_Cod'] : 0;
                        $reg_nom = '';
                        foreach ($lista_regimenes as $r) { if ($r['Reg_Cod'] == $reg_cod) { $reg_nom = $r['Reg_Nombre']; break; } }
                        $tipo_emp = isset($c['Dcl_Tipo_Empresa']) ? $c['Dcl_Tipo_Empresa'] : '';
                        $tipo_emp_txt = $tipo_emp === 'PEQUENO' ? 'Pequeño' : ($tipo_emp === 'MEDIANO' ? 'Mediano' : ($tipo_emp === 'GRANDE' ? 'Grande' : '-'));
                    ?>
                    <tr data-dcl="<?php echo $c['Dcl_Cod']; ?>" data-reg="<?php echo $reg_cod; ?>" data-tipo="<?php echo htmlspecialchars($tipo_emp); ?>" data-est="<?php echo htmlspecialchars($c['Dcl_Est']); ?>">
                        <td><?php echo htmlspecialchars($c['Cliente_Nombre']); ?></td>
                        <td><?php echo htmlspecialchars(isset($c['Prs_Ced']) ? $c['Prs_Ced'] : (isset($c['Cli_Ruf']) ? $c['Cli_Ruf'] : '')); ?></td>
                        <td class="cell-regimen"><?php echo htmlspecialchars($reg_nom ?: '-- Sin régimen --'); ?></td>
                        <td class="cell-tipo"><?php echo htmlspecialchars($tipo_emp_txt); ?></td>
                        <td><span class="est-<?php echo strtolower($c['Dcl_Est']); ?> cell-estado"><?php echo $c['Dcl_Est']; ?></span></td>
                        <td><?php echo isset($c['Con_Fecha_Inicio']) && $c['Con_Fecha_Inicio'] ? $c['Con_Fecha_Inicio'] : '-'; ?></td>
                        <td><?php echo isset($c['Con_Fecha_Fin']) && $c['Con_Fecha_Fin'] ? $c['Con_Fecha_Fin'] : '-'; ?></td>
                        <td class="col-accion"><button type="button" class="btn btn-xs btn-editar-modificar editar-cliente-despacho" title="Editar" data-dcl="<?php echo $c['Dcl_Cod']; ?>" data-nom="<?php echo htmlspecialchars($c['Cliente_Nombre']); ?>" data-reg="<?php echo $reg_cod; ?>" data-tipo="<?php echo htmlspecialchars($tipo_emp); ?>" data-est="<?php echo htmlspecialchars($c['Dcl_Est']); ?>"><span class="glyphicon glyphicon-pencil"></span></button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Precios por Actividad -->
        <div role="tabpanel" class="tab-pane" id="tab-precios">
            <div class="config-card">
                <div class="config-header"><h4><span class="glyphicon glyphicon-usd"></span> Precios por Actividad (Pequeño / Mediano / Grande)</h4></div>
                <p class="text-muted" style="margin-bottom: 16px; padding: 12px 16px; background: #f0fdfa; border-radius: 8px; border-left: 4px solid #2C5D94;">
                    <span class="glyphicon glyphicon-info-sign"></span> Defina el precio de cada actividad según el tipo de empresa del cliente (Pequeño, Mediano, Grande). Asigne el tipo de empresa a cada cliente en <strong>Clientes del Despacho</strong>.
                </p>
                <div class="form-inline" style="margin-bottom: 16px;">
                    <label style="margin-right: 8px; font-weight: 600;">Filtrar por tipo:</label>
                    <select id="filtroTipoPrecios" class="form-control" style="width: 160px;">
                        <option value="">Todos</option>
                        <option value="MENSUAL">Mensual</option>
                        <option value="ANUAL">Anual</option>
                        <option value="EVENTUAL">Eventual</option>
                    </select>
                </div>
                <table id="gridPrecios" class="aud-tabla aud-tabla-precios">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th class="col-actividad">Actividad</th>
                            <th class="col-tipo">Tipo</th>
                            <th class="col-precio">Precio Pequeño</th>
                            <th class="col-precio">Precio Mediano</th>
                            <th class="col-precio">Precio Grande</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $ser_ant = '';
                    foreach ($lista_actividades_precios as $r):
                        $ser = isset($r['Ser_Nombre']) ? $r['Ser_Nombre'] : '';
                        $ser_cls = ($ser !== $ser_ant) ? ' ser-grupo' : '';
                        $ser_ant = $ser;
                        $peq = isset($r['Precio_Pequeno']) ? number_format((float)$r['Precio_Pequeno'], 2, '.', '') : '0.00';
                        $med = isset($r['Precio_Mediano']) ? number_format((float)$r['Precio_Mediano'], 2, '.', '') : '0.00';
                        $gra = isset($r['Precio_Grande']) ? number_format((float)$r['Precio_Grande'], 2, '.', '') : '0.00';
                    ?>
                    <tr data-act="<?php echo (int)$r['Act_Cod']; ?>" data-tipo="<?php echo htmlspecialchars(isset($r['Act_Tipo']) ? $r['Act_Tipo'] : ''); ?>">
                        <td class="<?php echo $ser_cls; ?>"><?php echo htmlspecialchars($ser); ?></td>
                        <td class="col-actividad"><?php echo htmlspecialchars($r['Act_Nombre']); ?></td>
                        <td class="col-tipo"><?php echo htmlspecialchars($r['Act_Tipo']); ?></td>
                        <td class="col-precio"><input type="text" class="form-control precio-input precio-pequeno" value="<?php echo $peq; ?>" data-act="<?php echo (int)$r['Act_Cod']; ?>" style="width:80px;text-align:right;" /></td>
                        <td class="col-precio"><input type="text" class="form-control precio-input precio-mediano" value="<?php echo $med; ?>" data-act="<?php echo (int)$r['Act_Cod']; ?>" style="width:80px;text-align:right;" /></td>
                        <td class="col-precio"><input type="text" class="form-control precio-input precio-grande" value="<?php echo $gra; ?>" data-act="<?php echo (int)$r['Act_Cod']; ?>" style="width:80px;text-align:right;" /></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lista_actividades_precios)): ?>
                    <tr><td colspan="6" style="padding: 24px; text-align: center; color: #64748b;">No hay actividades. Ejecute los scripts SQL de migración (aud_sql_regimen_actividades.sql y aud_sql_regimen_general_precios.sql).</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div style="margin-top: 16px;">
                    <button type="button" class="btn btn-primary btn-editar" id="btnGuardarTodosPrecios"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                    <div id="msgPrecios" role="alert"></div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- Modal Nueva/Editar Actividad (flotante, oculto por defecto) -->
<div class="modal" id="modalActividad" tabindex="-1" role="dialog" aria-labelledby="modalActividadTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color: white; opacity: 1;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalActividadTitle"><span class="glyphicon glyphicon-tasks"></span> Nueva actividad</h4>
            </div>
            <div class="modal-body">
                <form id="formActividad" class="form-horizontal">
                    <input type="hidden" name="Act_Cod" id="Act_Cod" value="" />
                    <div class="form-actividad-grid">
                        <div class="form-actividad-row">
                            <label for="Ser_Cod_Act">Servicio <span class="text-danger">*</span></label>
                            <select name="Ser_Cod" id="Ser_Cod_Act" class="form-control">
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($lista_servicios as $s): ?>
                                <option value="<?php echo $s['Ser_Cod']; ?>"><?php echo htmlspecialchars($s['Ser_Nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-actividad-row">
                            <label for="Act_Nombre">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="Act_Nombre" id="Act_Nombre" class="form-control" maxlength="100" placeholder="Ej: IVA, ATS, RDEP" />
                        </div>
                        <div class="form-actividad-row">
                            <label for="Act_Tipo">Tipo</label>
                            <select name="Act_Tipo" id="Act_Tipo" class="form-control">
                                <option value="MENSUAL">Mensual</option>
                                <option value="ANUAL">Anual</option>
                                <option value="EVENTUAL">Eventual</option>
                            </select>
                        </div>
                        <div class="form-actividad-row">
                            <label for="Act_Prioridad">Prioridad</label>
                            <select name="Act_Prioridad" id="Act_Prioridad" class="form-control">
                                <option value="ALTA">Alta</option>
                                <option value="MEDIA" selected>Media</option>
                                <option value="BAJA">Baja</option>
                            </select>
                        </div>
                        <div class="form-actividad-row form-actividad-row-full" id="rowMesesAnual" style="display:none;">
                            <label>Meses (para actividades anuales)</label>
                            <div class="meses-anual-grid">
                                <label><input type="checkbox" name="meses_anual[]" value="01" /> Ene</label>
                                <label><input type="checkbox" name="meses_anual[]" value="02" /> Feb</label>
                                <label><input type="checkbox" name="meses_anual[]" value="03" /> Mar</label>
                                <label><input type="checkbox" name="meses_anual[]" value="04" /> Abr</label>
                                <label><input type="checkbox" name="meses_anual[]" value="05" /> May</label>
                                <label><input type="checkbox" name="meses_anual[]" value="06" /> Jun</label>
                                <label><input type="checkbox" name="meses_anual[]" value="07" /> Jul</label>
                                <label><input type="checkbox" name="meses_anual[]" value="08" /> Ago</label>
                                <label><input type="checkbox" name="meses_anual[]" value="09" /> Sep</label>
                                <label><input type="checkbox" name="meses_anual[]" value="10" /> Oct</label>
                                <label><input type="checkbox" name="meses_anual[]" value="11" /> Nov</label>
                                <label><input type="checkbox" name="meses_anual[]" value="12" /> Dic</label>
                            </div>
                            <small class="text-muted" style="display: block; width: 100%; grid-column: 1 / -1;">Cuando el tipo sea <strong>Anual</strong>, seleccione en qué mes(es) del año se debe generar la tarea.</small>
                            <input type="hidden" name="Act_Meses_Anual" id="Act_Meses_Anual" value="" />
                        </div>
                        <div class="form-actividad-row form-actividad-row-full">
                            <label for="Act_Descripcion">Descripción</label>
                            <textarea name="Act_Descripcion" id="Act_Descripcion" class="form-control" rows="2" placeholder="Opcional"></textarea>
                        </div>
                        <div class="form-actividad-row form-actividad-row-inline">
                            <label></label>
                            <div>
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 500;">
                                    <input type="checkbox" name="Act_Usa_Ruc" id="Act_Usa_Ruc" value="S" />
                                    <span>Usar fecha según 9.º dígito del RUC</span>
                                </label>
                                <small class="text-muted" style="margin-left: 8px;">Opcional.</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> Cerrar</button>
                <button type="button" class="btn btn-default" id="btnNuevoActividadModal"><span class="glyphicon glyphicon-plus"></span> Nuevo</button>
                <button type="button" class="btn btn-primary" id="btnGuardarActividad"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Cliente Despacho (flotante) -->
<div class="modal" id="modalEditarClienteDespacho" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color: white; opacity: 1;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" style="text-align:center; margin:0; font-size:16px;"><span class="glyphicon glyphicon-pencil"></span> Editar cliente: <strong id="lblClienteEditar"></strong></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editDcl_Cod" value="" />
                <div class="form-group">
                    <label>Régimen</label>
                    <select id="editReg_Cod" class="form-control">
                        <option value="">-- Sin régimen --</option>
                        <?php foreach ($lista_regimenes as $r): ?>
                        <option value="<?php echo $r['Reg_Cod']; ?>"><?php echo htmlspecialchars($r['Reg_Nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo empresa</label>
                    <select id="editDcl_Tipo_Empresa" class="form-control">
                        <option value="">-- Sin tipo --</option>
                        <option value="PEQUENO">Pequeño</option>
                        <option value="MEDIANO">Mediano</option>
                        <option value="GRANDE">Grande</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select id="editDcl_Est" class="form-control">
                        <option value="ACTIVO">Activo</option>
                        <option value="SUSPENDIDO">Suspendido</option>
                        <option value="FINALIZADO">Finalizado</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e2e8f0; padding:16px 24px; background:#f8fafc;">
                <button type="button" class="btn btn-default" id="btnCancelarEditarCliente"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarClienteDespacho"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function () {
    var urlBase = <?php echo json_encode($_SERVER['PHP_SELF']); ?>;
    var select2Opts = { language: { noResults: function() { return 'No se encontraron resultados'; }, searching: function() { return 'Buscando...'; } }, allowClear: true };
    function initSelect2Buscable($el, extra) {
        if (!$el.length || typeof $el.select2 !== 'function') return;
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        $el.select2(extra ? $.extend(true, {}, select2Opts, extra) : select2Opts);
        $el.off('select2:open.select2noclick select2:selecting.select2noclick').on('select2:open.select2noclick', function () {
            $el.data('select2OpenAt', Date.now());
        }).on('select2:selecting.select2noclick', function (ev) {
            var openedAt = $el.data('select2OpenAt');
            if (openedAt && (Date.now() - openedAt) < 300) ev.preventDefault();
        });
    }
    initSelect2Buscable($('#filtroServicioActividades'), { placeholder: '-- Todos los servicios --' });
    initSelect2Buscable($('#selServicioRegimen'), { placeholder: '-- Servicio --' });
    initSelect2Buscable($('#selClienteAgregar'), { placeholder: '-- Seleccione cliente para agregar --' });

    try {
        var tabGuardada = sessionStorage.getItem('aud_despacho_tab');
        if (tabGuardada) {
            sessionStorage.removeItem('aud_despacho_tab');
            $('a[href="#' + tabGuardada + '"]').tab('show');
        }
    } catch (e) {}

    $('#modalActividad').removeClass('modal-in');
    $('#backdropActividad').remove();
    $('#modalEditarClienteDespacho').removeClass('modal-in');
    $('#backdropClienteDespacho').remove();

    function mostrarModalActividad() {
        public $modal = $('#modalActividad');
        $modal.appendTo('body').addClass('modal-in').attr('aria-hidden', 'false');
        if (!$('#backdropActividad').length) {
            $('body').append('<div class="modal-backdrop fade in" id="backdropActividad" style="z-index:1040;position:fixed;top:0;left:0;width:100%;height:100%;background:#000;opacity:0.5;"></div>');
            $('#backdropActividad').on('click', function () { ocultarModalActividad(); });
        }
    }
    function ocultarModalActividad() {
        $('#modalActividad').removeClass('modal-in').attr('aria-hidden', 'true');
        $('#backdropActividad').remove();
    }

    $(document).on('click', '#modalActividad [data-dismiss="modal"], #modalActividad .close', function (e) {
        e.preventDefault();
        ocultarModalActividad();
    });

    $(document).on('click', '.editar-servicio', function () {
        public $t = $(this);
        $('#Ser_Cod').val($t.data('cod'));
        $('#Ser_Nombre').val($t.data('nom'));
        $('#Ser_Descripcion').val($t.data('desc'));
    });

    $('#btnGuardarServicio').on('click', function () {
        var cod = $('#Ser_Cod').val(), nom = $('#Ser_Nombre').val().trim();
        if (nom === '') { alert('El nombre es obligatorio.'); return; }
        $.post(urlBase, { guardarServicio: 1, Ser_Cod: cod, Ser_Nombre: nom, Ser_Descripcion: $('#Ser_Descripcion').val() }, function (r) {
            if (r.success) { alert('Guardado correctamente.'); location.reload(); }
            else alert(r.message || 'Error al guardar.');
        }, 'json');
    });

    function syncMesesAnualFromHidden() {
        var val = ($('#Act_Meses_Anual').val() || '').trim();
        var set = {};
        if (val !== '') {
            $.each(val.split(','), function (_, m) {
                m = $.trim(m);
                if (m) set[m] = true;
            });
        }
        $('#rowMesesAnual input[name=\"meses_anual[]\"]').each(function () {
            var v = $(this).val();
            $(this).prop('checked', !!set[v]);
        });
    }

    function syncHiddenFromMesesAnual() {
        var meses = [];
        $('#rowMesesAnual input[name=\"meses_anual[]\"]:checked').each(function () {
            meses.push($(this).val());
        });
        $('#Act_Meses_Anual').val(meses.join(','));
    }

    function toggleRowMesesAnual() {
        var tipo = $('#Act_Tipo').val();
        if (tipo === 'ANUAL') {
            $('#rowMesesAnual').show();
            syncMesesAnualFromHidden();
        } else {
            $('#rowMesesAnual').hide();
        }
    }

    $('#Act_Tipo').on('change', function () {
        toggleRowMesesAnual();
    });

    $('#rowMesesAnual').on('change', 'input[name=\"meses_anual[]\"]', function () {
        syncHiddenFromMesesAnual();
    });

    $(document).on('click', '#btnNuevaActividad', function (e) {
        e.preventDefault();
        $('#formActividad')[0].reset();
        $('#Act_Cod').val('');
        $('#Act_Prioridad').val('MEDIA');
        $('#Act_Tipo').val('MENSUAL');
        $('#Act_Usa_Ruc').prop('checked', false);
        $('#Act_Meses_Anual').val('');
        $('#rowMesesAnual input[name=\"meses_anual[]\"]').prop('checked', false);
        $('#modalActividadTitle').html('<span class="glyphicon glyphicon-tasks"></span> Nueva actividad');
        mostrarModalActividad();
        toggleRowMesesAnual();
    });

    $('#btnNuevoActividadModal').on('click', function () {
        $('#formActividad')[0].reset();
        $('#Act_Cod').val('');
        $('#Act_Prioridad').val('MEDIA');
        $('#Act_Tipo').val('MENSUAL');
        $('#Act_Usa_Ruc').prop('checked', false);
        $('#Act_Meses_Anual').val('');
        $('#rowMesesAnual input[name=\"meses_anual[]\"]').prop('checked', false);
        $('#modalActividadTitle').html('<span class="glyphicon glyphicon-tasks"></span> Nueva actividad');
        toggleRowMesesAnual();
    });

    $(document).on('click', '.editar-actividad', function () {
        public $t = $(this);
        $('#Act_Cod').val($t.data('cod'));
        $('#Ser_Cod_Act').val($t.data('ser'));
        $('#Act_Nombre').val($t.data('nom'));
        $('#Act_Tipo').val($t.data('tipo'));
        $('#Act_Prioridad').val($t.data('prior'));
        $('#Act_Descripcion').val($t.data('desc'));
        $('#Act_Usa_Ruc').prop('checked', $t.data('ruc') === 'S');
        $('#Act_Meses_Anual').val($t.data('meses') || '');
        syncMesesAnualFromHidden();
        toggleRowMesesAnual();
        $('#modalActividadTitle').html('<span class="glyphicon glyphicon-pencil"></span> Editar actividad');
        mostrarModalActividad();
    });

    $('#btnGuardarActividad').on('click', function () {
        var cod = $('#Act_Cod').val(), ser = $('#Ser_Cod_Act').val(), nom = $('#Act_Nombre').val().trim();
        if (nom === '' || ser === '') { alert('Servicio y nombre son obligatorios.'); return; }
        var usaRuc = $('#Act_Usa_Ruc').is(':checked') ? 'S' : 'N';
        syncHiddenFromMesesAnual();
        $.post(urlBase, {
            guardarActividad: 1, Act_Cod: cod, Ser_Cod: ser, Act_Nombre: nom,
            Act_Tipo: $('#Act_Tipo').val(), Act_Prioridad: $('#Act_Prioridad').val(), Act_Recurrente: 'S',
            Act_Descripcion: $('#Act_Descripcion').val(), Act_Usa_Ruc: usaRuc, Act_Meses_Anual: $('#Act_Meses_Anual').val()
        }, function (r) {
            if (r.success) {
                ocultarModalActividad();
                cargarActividadesFiltradas();
            } else alert(r.message || 'Error al guardar.');
        }, 'json');
    });

    $('#btnBuscarCliente').on('click', function () {
        var bus = $('#buscarCliente').val();
        $.get(urlBase, { buscarClientes: 1, search: bus }, function (r) {
            public $sel = $('#selClienteAgregar');
            if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
            $sel.find('option:gt(0)').remove();
            $.each(r.rows || [], function (i, row) {
                $sel.append('<option value="' + row.Cli_Cod + '">' + (row.Nombre || '') + ' - ' + (row.Prs_Ced || row.Cli_Ruf || '') + '</option>');
            });
            initSelect2Buscable($sel, { placeholder: '-- Seleccione cliente para agregar --' });
        }, 'json');
    });

    $('#btnAgregarCliente').on('click', function () {
        var cli = $('#selClienteAgregar').val();
        if (!cli) { alert('Seleccione un cliente.'); return; }
        $.post(urlBase, { agregarClienteDespacho: 1, Cli_Cod: cli }, function (r) {
            if (r.success) {
                try { sessionStorage.setItem('aud_despacho_tab', 'tab-clientes'); } catch (e) {}
                alert('Cliente agregado.');
                location.reload();
            } else alert(r.message || 'Error.');
        }, 'json');
    });

    function mostrarModalClienteDespacho() {
        public $modal = $('#modalEditarClienteDespacho');
        $modal.appendTo('body').addClass('modal-in');
        if (!$('#backdropClienteDespacho').length) {
            $('body').append('<div class="modal-backdrop fade in" id="backdropClienteDespacho" style="z-index:1040;position:fixed;top:0;left:0;width:100%;height:100%;background:#000;opacity:0.5;"></div>');
            $('#backdropClienteDespacho').on('click', function () { ocultarModalClienteDespacho(); });
        }
    }
    function ocultarModalClienteDespacho() {
        $('#modalEditarClienteDespacho').removeClass('modal-in');
        $('#backdropClienteDespacho').remove();
    }

    $(document).on('click', '.editar-cliente-despacho', function () {
        public $t = $(this);
        $('#editDcl_Cod').val($t.data('dcl'));
        $('#lblClienteEditar').text($t.data('nom'));
        $('#editReg_Cod').val($t.data('reg') || '');
        $('#editDcl_Tipo_Empresa').val($t.data('tipo') || '');
        $('#editDcl_Est').val($t.data('est') || 'ACTIVO');
        mostrarModalClienteDespacho();
    });

    $(document).on('click', '#modalEditarClienteDespacho [data-dismiss="modal"], #modalEditarClienteDespacho .close', function (e) {
        e.preventDefault();
        ocultarModalClienteDespacho();
    });

    $('#btnCancelarEditarCliente').on('click', function () {
        ocultarModalClienteDespacho();
    });

    $('#filtroTipoPrecios').on('change', function () {
        var tipo = $(this).val();
        $('#gridPrecios tbody tr[data-act]').each(function () {
            var rowTipo = $(this).data('tipo') || '';
            if (!tipo || rowTipo === tipo) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    function mostrarMsgPrecios(texto, esError) {
        public $msg = $('#msgPrecios');
        $msg.removeClass('msg-success msg-error').addClass(esError ? 'msg-error' : 'msg-success').text(texto).show();
        if (!esError) setTimeout(function () { $msg.fadeOut(); }, 4000);
    }

    $('#btnGuardarTodosPrecios').on('click', function () {
        public $btn = $(this);
        public $rows = $('#gridPrecios tbody tr[data-act]').filter(':visible');
        if ($rows.length === 0) {
            mostrarMsgPrecios('No hay actividades para guardar.', true);
            return;
        }
        $btn.prop('disabled', true);
        $('#msgPrecios').hide();
        var pendientes = $rows.length;
        var errores = [];
        $rows.each(function () {
            public $row = $(this);
            var act = $row.data('act');
            var peq = parseFloat($row.find('.precio-pequeno').val().replace(',', '.')) || 0;
            var med = parseFloat($row.find('.precio-mediano').val().replace(',', '.')) || 0;
            var gra = parseFloat($row.find('.precio-grande').val().replace(',', '.')) || 0;
            $.post(urlBase, {
                guardarPreciosActividad: 1,
                Act_Cod: act,
                Precio_Pequeno: peq,
                Precio_Mediano: med,
                Precio_Grande: gra
            }, function (r) {
                if (r && r.success) {
                    $row.css('background', '#ecfdf5');
                    setTimeout(function () { $row.css('background', ''); }, 600);
                } else {
                    errores.push(r && r.message ? r.message : 'Error en actividad ' + act);
                }
            }, 'json').fail(function () {
                errores.push('Error de conexión en actividad ' + act);
            }).always(function () {
                pendientes--;
                if (pendientes <= 0) {
                    $btn.prop('disabled', false);
                    if (errores.length > 0) {
                        mostrarMsgPrecios(errores.length === 1 ? errores[0] : 'Se guardaron pero hubo ' + errores.length + ' errores: ' + errores.join('; '), true);
                    } else {
                        mostrarMsgPrecios('Precios guardados correctamente.', false);
                    }
                }
            });
        });
    });

    $('#btnGuardarClienteDespacho').on('click', function () {
        var dcl = $('#editDcl_Cod').val(), reg = $('#editReg_Cod').val() || '', tipoEmp = $('#editDcl_Tipo_Empresa').val() || '', est = $('#editDcl_Est').val() || 'ACTIVO';
        if (!dcl) return;
        $.post(urlBase, { actualizarClienteDespacho: 1, Dcl_Cod: dcl, Dcl_Est: est, Reg_Cod: reg, Dcl_Tipo_Empresa: tipoEmp }, function (r) {
            if (r.success) {
                public $row = $('tr[data-dcl="' + dcl + '"]');
                $row.attr('data-reg', reg).attr('data-tipo', tipoEmp).attr('data-est', est);
                $row.find('.cell-regimen').text($('#editReg_Cod option:selected').text() || '-- Sin régimen --');
                $row.find('.cell-tipo').text($('#editDcl_Tipo_Empresa option:selected').text() || '-');
                $row.find('.cell-estado').removeClass('est-activo est-suspendido est-finalizado').addClass('est-' + est.toLowerCase()).text(est);
                $row.find('.editar-cliente-despacho').data('reg', reg).data('tipo', tipoEmp).data('est', est);
                ocultarModalClienteDespacho();
            } else alert(r.message || 'Error.');
        }, 'json');
    });

    $(document).on('click', '.editar-regimen', function () {
        public $t = $(this);
        $('#Reg_Cod').val($t.data('cod'));
        $('#Reg_Nombre').val($t.data('nom'));
        $('#Reg_Descripcion').val($t.data('desc'));
        $('#panelActividadesRegimen').hide();
    });

    $('#btnGuardarRegimen').on('click', function () {
        var cod = $('#Reg_Cod').val(), nom = $('#Reg_Nombre').val().trim();
        if (nom === '') { alert('El nombre es obligatorio.'); return; }
        $.post(urlBase, { guardarRegimen: 1, Reg_Cod: cod, Reg_Nombre: nom, Reg_Descripcion: $('#Reg_Descripcion').val() }, function (r) {
            if (r.success) { alert('Guardado correctamente.'); location.reload(); }
            else alert(r.message || 'Error al guardar.');
        }, 'json');
    });

    var regActivo = 0;
    $(document).on('click', '.config-actividades-regimen', function () {
        regActivo = $(this).data('cod');
        $('#lblRegimenNombre').text($(this).data('nom'));
        $('#panelActividadesRegimen').show();
        $('#selServicioRegimen').val('');
        $('#selActividadRegimen').find('option:gt(0)').remove();
        cargarActividadesRegimen(regActivo);
    });

    function cargarActividadesRegimen(reg) {
        $.get(urlBase, { actividadesRegimen: 1, Reg_Cod: reg }, function (r) {
            public $tb = $('#gridActividadesRegimen tbody');
            $tb.empty();
            $.each(r.rows || [], function (i, row) {
                $tb.append('<tr><td>' + (row.Ser_Nombre || '') + '</td><td>' + (row.Act_Nombre || '') + '</td><td>' + (row.Act_Tipo || '') + '</td>' +
                    '<td class="col-accion"><button type="button" class="btn btn-sm btn-danger quitar-act-regimen" data-regact="' + row.RegAct_Cod + '" title="Eliminar"><span aria-hidden="true">&times;</span></button></td></tr>');
            });
            if (!r.rows || r.rows.length === 0) $tb.append('<tr><td colspan="4" class="text-muted">Sin actividades. Agregue desde los selects arriba.</td></tr>');
        }, 'json');
    }

    function cargarActividadesFiltradas() {
        var ser = $('#filtroServicioActividades').val();
        $.get(urlBase, { listarActividades: 1, Ser_Cod: ser || '' }, function (r) {
            var rows = r.rows || [];
            var html = '';
            if (rows.length === 0) {
                html = '<tr class="empty-row"><td colspan="7" style="padding:0;"><div class="empty-state"><span class="glyphicon glyphicon-tasks"></span><br/>No hay actividades.' + (ser ? ' No hay actividades para este servicio.' : ' Primero agregue un servicio y registre actividades.') + '</div></td></tr>';
            } else {
                $.each(rows, function (i, a) {
                    var ruc = (a.Act_Usa_Ruc || 'N') === 'S' ? 'Sí' : 'No';
                    html += '<tr><td>' + (a.Act_Cod || '') + '</td><td>' + (a.Ser_Nombre || '').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</td><td>' + (a.Act_Nombre || '').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</td>';
                    html += '<td>' + (a.Act_Tipo || '') + '</td><td>' + (a.Act_Prioridad || '') + '</td><td>' + ruc + '</td>';
                    var mesesAttr = (a.Act_Meses_Anual != null && a.Act_Meses_Anual !== '') ? String(a.Act_Meses_Anual).replace(/"/g,'&quot;') : '';
                    html += '<td class="col-accion" style="white-space:nowrap;"><button type="button" class="btn btn-xs btn-editar-modificar editar-actividad" title="Editar" data-cod="' + (a.Act_Cod||'') + '" data-ser="' + (a.Ser_Cod||'') + '" data-nom="' + (a.Act_Nombre||'').replace(/"/g,'&quot;') + '" data-tipo="' + (a.Act_Tipo||'') + '" data-prior="' + (a.Act_Prioridad||'') + '" data-desc="' + (a.Act_Descripcion||'').replace(/"/g,'&quot;') + '" data-ruc="' + (a.Act_Usa_Ruc||'N') + '" data-meses="' + mesesAttr + '"><span class="glyphicon glyphicon-pencil"></span></button> ';
                    html += '<button type="button" class="btn btn-xs btn-danger eliminar-actividad" data-cod="' + (a.Act_Cod||'') + '" data-nom="' + (a.Act_Nombre||'').replace(/"/g,'&quot;') + '" title="Eliminar"><span aria-hidden="true">&times;</span></button></td></tr>';
                });
            }
            $('#tbodyActividades').html(html);
        }, 'json');
    }

    $(document).on('click', '.eliminar-actividad', function () {
        var cod = $(this).data('cod'), nom = $(this).data('nom') || 'esta actividad';
        if (!confirm('¿Eliminar la actividad "' + nom + '"? Se marcará como inactiva y dejará de mostrarse en el catálogo.')) return;
        public $btn = $(this);
        $btn.prop('disabled', true);
        $.post(urlBase, { eliminarActividad: 1, Act_Cod: cod }, function (r) {
            if (r.success) {
                cargarActividadesFiltradas();
            } else {
                $btn.prop('disabled', false);
                alert(r.message || 'Error al eliminar.');
            }
        }, 'json').fail(function () {
            $btn.prop('disabled', false);
            alert('Error de conexión.');
        });
    });

    $('#filtroServicioActividades').on('change', cargarActividadesFiltradas);
    $('#btnAplicarFiltroActividades').on('click', cargarActividadesFiltradas);
    $('a[href="#tab-actividades"]').on('shown.bs.tab', cargarActividadesFiltradas);

    function actualizarSelectActividadesRegimen(reg, ser) {
        public $sel = $('#selActividadRegimen');
        if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
        $sel.find('option:gt(0)').remove();
        if (!reg || !ser) return;
        $.get(urlBase, { actividadesRegimen: 1, Reg_Cod: reg }, function (regResp) {
            var enRegimen = (regResp && regResp.rows) ? regResp.rows : [];
            var actIdsEnRegimen = {};
            $.each(enRegimen, function (i, r) { actIdsEnRegimen[String(r.Act_Cod)] = true; });
            $.get(urlBase, { listarActividades: 1, Ser_Cod: ser }, function (actResp) {
                var actividades = (actResp && actResp.rows) ? actResp.rows : [];
                $.each(actividades, function (i, row) {
                    if (!actIdsEnRegimen[String(row.Act_Cod)]) {
                        $sel.append('<option value="' + row.Act_Cod + '">' + (row.Act_Nombre || '') + '</option>');
                    }
                });
                initSelect2Buscable($sel, { placeholder: '-- Actividad --' });
            }, 'json');
        }, 'json');
    }

    $('#selServicioRegimen').on('change', function () {
        var ser = $(this).val();
        actualizarSelectActividadesRegimen(regActivo, ser);
    });

    $('#btnAgregarActividadRegimen').on('click', function () {
        var reg = regActivo, ser = $('#selServicioRegimen').val(), act = $('#selActividadRegimen').val();
        if (!reg || !ser || !act) { alert('Seleccione servicio y actividad.'); return; }
        $.post(urlBase, { agregarActividadRegimen: 1, Reg_Cod: reg, Ser_Cod: ser, Act_Cod: act }, function (r) {
            if (r.success) {
                cargarActividadesRegimen(reg);
                $('#selActividadRegimen').val('');
                actualizarSelectActividadesRegimen(reg, ser);
            } else alert(r.message || 'Error.');
        }, 'json');
    });

    $(document).on('click', '.quitar-act-regimen', function () {
        var regact = $(this).data('regact');
        if (!confirm('¿Quitar esta actividad del régimen?')) return;
        $.post(urlBase, { quitarActividadRegimen: 1, RegAct_Cod: regact }, function (r) {
            if (r.success) {
                cargarActividadesRegimen(regActivo);
                actualizarSelectActividadesRegimen(regActivo, $('#selServicioRegimen').val());
            } else alert(r.message || 'Error.');
        }, 'json');
    });
});
</script>
</body>
</html>
