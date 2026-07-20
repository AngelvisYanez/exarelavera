<?php
/**
 * Gestión Operativa del Despacho - Contratos
 * CRUD contratos + servicios/actividades contratadas
 * @author Sistema EXA | @version 1.0
 */
if (!empty($_GET['debug'])) { ini_set('display_errors', 1); error_reporting(E_ALL); }
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/aud_log_despacho_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Despacho($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Despacho();
$Ses_Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;
$Ses_Usu_Cod = isset($Ses_Usu_Cod) ? intval($Ses_Usu_Cod) : 0;

// Ajax: Listar contratos
if (!empty($_REQUEST['listarContratos'])) {
    $dcl = isset($_REQUEST['Dcl_Cod']) ? intval($_REQUEST['Dcl_Cod']) : 0;
    $par = array('Emp_Cod' => $Ses_Emp_Cod);
    if ($dcl > 0) $par['Dcl_Cod'] = $dcl;
    $arr = $obBD_con1->getArrayConsulta(12, $par, $obBD_conexion);
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// Ajax: Siguiente número contrato
if (!empty($_REQUEST['siguienteNumeroContrato'])) {
    $row = $obBD_con1->getRowConsulta(48, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('numero' => isset($row['Siguiente']) ? $row['Siguiente'] : 1));
    exit;
}

// Ajax: Guardar contrato
if (!empty($_REQUEST['guardarContrato'])) {
    $resp = array('success' => false);
    $cod = intval(isset($_POST['Con_Cod']) ? $_POST['Con_Cod'] : 0);
    $dcl = intval(isset($_POST['Dcl_Cod']) ? $_POST['Dcl_Cod'] : 0);
    $num = trim(isset($_POST['Con_Numero']) ? $_POST['Con_Numero'] : '');
    if ($num === '' && $cod <= 0) {
        $row = $obBD_con1->getRowConsulta(48, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
        $num = isset($row['Siguiente']) ? (string)$row['Siguiente'] : '1';
    }
    $fecIni = trim(isset($_POST['Con_Fecha_Inicio']) ? $_POST['Con_Fecha_Inicio'] : '');
    $fecFin = trim(isset($_POST['Con_Fecha_Fin']) ? $_POST['Con_Fecha_Fin'] : '');
    $tipo = isset($_POST['Con_Tipo']) ? $_POST['Con_Tipo'] : 'MENSUAL';
    $meses_anual = trim(isset($_POST['Con_Meses_Anual']) ? $_POST['Con_Meses_Anual'] : '');
    $val = floatval(str_replace(',', '.', isset($_POST['Con_Valor']) ? $_POST['Con_Valor'] : 0));
    if ($dcl <= 0 || $fecIni === '') {
        $resp['message'] = 'Cliente y fecha inicio son obligatorios.';
        echo json_encode($resp);
        exit;
    }
    $conn = $obBD_conexion->conexion;
    mysqli_set_charset($conn, 'utf8');
    $num_safe = mysqli_real_escape_string($conn, $num);
    if ($cod > 0) {
        $obBD_con1->operacionobBD(14, array('Con_Cod' => $cod, 'Con_Numero_safe' => $num_safe, 'Con_Fecha_Inicio' => $fecIni, 'Con_Fecha_Fin' => $fecFin, 'Con_Tipo' => $tipo, 'Con_Meses_Anual' => $meses_anual, 'Con_Valor' => $val), $obBD_conexion);
    } else {
        $obBD_con1->operacionobBD(13, array('Dcl_Cod' => $dcl, 'Con_Numero_safe' => $num_safe, 'Con_Fecha_Inicio' => $fecIni, 'Con_Fecha_Fin' => $fecFin, 'Con_Tipo' => $tipo, 'Con_Meses_Anual' => $meses_anual, 'Con_Valor' => $val), $obBD_conexion);
    }
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    echo json_encode($resp);
    exit;
}

// Ajax: Servicios/actividades del contrato
if (!empty($_REQUEST['serviciosContrato'])) {
    $con = intval(isset($_REQUEST['Con_Cod']) ? $_REQUEST['Con_Cod'] : 0);
    $arr = $obBD_con1->getArrayConsulta(15, array('Con_Cod' => $con), $obBD_conexion);
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}
if (!empty($_REQUEST['actividadesContrato'])) {
    $con = intval(isset($_REQUEST['Con_Cod']) ? $_REQUEST['Con_Cod'] : 0);
    $arr = $obBD_con1->getArrayConsulta(16, array('Con_Cod' => $con), $obBD_conexion);
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// Ajax: Agregar servicio/actividad al contrato
if (!empty($_REQUEST['agregarServicioContrato'])) {
    $resp = array('success' => false);
    $con = intval(isset($_POST['Con_Cod']) ? $_POST['Con_Cod'] : 0);
    $ser = intval(isset($_POST['Ser_Cod']) ? $_POST['Ser_Cod'] : 0);
    if ($con <= 0 || $ser <= 0) { $resp['message'] = 'Datos inválidos.'; echo json_encode($resp); exit; }
    $obBD_con1->operacionobBD(17, array('Con_Cod' => $con, 'Ser_Cod' => $ser, 'Incluido' => 'S', 'Facturable' => 'N'), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    echo json_encode($resp);
    exit;
}
if (!empty($_REQUEST['agregarActividadContrato'])) {
    $resp = array('success' => false);
    $con = intval(isset($_POST['Con_Cod']) ? $_POST['Con_Cod'] : 0);
    $act = intval(isset($_POST['Act_Cod']) ? $_POST['Act_Cod'] : 0);
    $ser = intval(isset($_POST['Ser_Cod']) ? $_POST['Ser_Cod'] : 0);
    if ($con <= 0 || $act <= 0) { $resp['message'] = 'Datos inválidos.'; echo json_encode($resp); exit; }
    if ($ser > 0) {
        $serviciosContrato = $obBD_con1->getArrayConsulta(15, array('Con_Cod' => $con), $obBD_conexion);
        $serEnContrato = false;
        foreach ($serviciosContrato as $s) { if (isset($s['Ser_Cod']) && (int)$s['Ser_Cod'] === $ser) { $serEnContrato = true; break; } }
        if (!$serEnContrato) {
            $obBD_con1->operacionobBD(17, array('Con_Cod' => $con, 'Ser_Cod' => $ser, 'Incluido' => 'S', 'Facturable' => 'N'), $obBD_conexion);
        }
    }
    $obBD_con1->operacionobBD(18, array('Con_Cod' => $con, 'Act_Cod' => $act, 'Incluida' => 'S', 'Facturable' => 'N'), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    echo json_encode($resp);
    exit;
}
if (!empty($_REQUEST['quitarActividadContrato'])) {
    $resp = array('success' => false);
    $conAct = intval(isset($_POST['ConAct_Cod']) ? $_POST['ConAct_Cod'] : 0);
    $con = intval(isset($_POST['Con_Cod']) ? $_POST['Con_Cod'] : 0);
    $ser = intval(isset($_POST['Ser_Cod']) ? $_POST['Ser_Cod'] : 0);
    if ($conAct <= 0) { $resp['message'] = 'Datos inválidos.'; echo json_encode($resp); exit; }
    $obBD_con1->operacionobBD(20, array('ConAct_Cod' => $conAct), $obBD_conexion);
    if ($obBD_con1->Error != 0) { $resp['message'] = $obBD_con1->MsgError; echo json_encode($resp); exit; }
    if ($con > 0 && $ser > 0) {
        $rowCnt = $obBD_con1->getRowConsulta(49, array('Con_Cod' => $con, 'Ser_Cod' => $ser), $obBD_conexion);
        $cnt = isset($rowCnt['Cnt']) ? (int)$rowCnt['Cnt'] : 0;
        if ($cnt <= 0) {
            $rowConSer = $obBD_con1->getRowConsulta(50, array('Con_Cod' => $con, 'Ser_Cod' => $ser), $obBD_conexion);
            if (!empty($rowConSer) && isset($rowConSer['ConSer_Cod'])) {
                $obBD_con1->operacionobBD(19, array('ConSer_Cod' => $rowConSer['ConSer_Cod']), $obBD_conexion);
            }
        }
    }
    $resp['success'] = true;
    echo json_encode($resp);
    exit;
}
if (!empty($_REQUEST['quitarServicioContrato'])) {
    $resp = array('success' => false);
    $conSer = intval(isset($_POST['ConSer_Cod']) ? $_POST['ConSer_Cod'] : 0);
    if ($conSer <= 0) { $resp['message'] = 'Datos inválidos.'; echo json_encode($resp); exit; }
    $rowConSer = $obBD_con1->getRowConsulta(51, array('ConSer_Cod' => $conSer), $obBD_conexion);
    if (!empty($rowConSer) && isset($rowConSer['Con_Cod'], $rowConSer['Ser_Cod'])) {
        $obBD_con1->operacionobBD(52, array('Con_Cod' => $rowConSer['Con_Cod'], 'Ser_Cod' => $rowConSer['Ser_Cod']), $obBD_conexion);
    }
    $obBD_con1->operacionobBD(19, array('ConSer_Cod' => $conSer), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    echo json_encode($resp);
    exit;
}
if (!empty($_REQUEST['eliminarContrato'])) {
    $resp = array('success' => false);
    $con = intval(isset($_POST['Con_Cod']) ? $_POST['Con_Cod'] : 0);
    if ($con <= 0) { $resp['message'] = 'Datos inválidos.'; echo json_encode($resp); exit; }
    $obBD_con1->operacionobBD(47, array('Con_Cod' => $con), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    echo json_encode($resp);
    exit;
}

// Ajax: Datos para Propuesta de Servicios Adicionales (cliente, actividades del contrato, catálogo con precios)
if (!empty($_REQUEST['datosPropuestaServiciosAdicionales'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $con = intval(isset($_REQUEST['Con_Cod']) ? $_REQUEST['Con_Cod'] : 0);
    $out = array('cliente' => array('Cliente_Nombre' => '', 'RUC' => '', 'Tipo_Empresa' => 'MEDIANO'), 'actividadesContrato' => array(), 'actividadesPrecios' => array());
    if ($con <= 0) {
        echo json_encode($out);
        exit;
    }
    $rowCliente = $obBD_con1->getRowConsulta(87, array('Con_Cod' => $con), $obBD_conexion);
    if (!empty($rowCliente)) {
        $tipoEmp = (isset($rowCliente['Tipo_Empresa']) && in_array(strtoupper(trim($rowCliente['Tipo_Empresa'])), array('PEQUENO','MEDIANO','GRANDE'))) ? strtoupper(trim($rowCliente['Tipo_Empresa'])) : 'MEDIANO';
        $out['cliente'] = array(
            'Cliente_Nombre' => isset($rowCliente['Cliente_Nombre']) ? $rowCliente['Cliente_Nombre'] : '',
            'RUC' => isset($rowCliente['RUC']) ? $rowCliente['RUC'] : '',
            'Tipo_Empresa' => $tipoEmp
        );
    }
    $out['actividadesContrato'] = $obBD_con1->getArrayConsulta(16, array('Con_Cod' => $con), $obBD_conexion);
    if (!is_array($out['actividadesContrato'])) $out['actividadesContrato'] = array();
    try {
        $out['actividadesPrecios'] = $obBD_con1->getArrayConsulta(67, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    } catch (Exception $e) {
        $out['actividadesPrecios'] = array();
    }
    if (!is_array($out['actividadesPrecios'])) $out['actividadesPrecios'] = array();
    $out['representanteLegal'] = array('Representante_Nombre' => '', 'Representante_Identificacion' => '');
    if (!empty($Ses_Emp_Cod)) {
        $obBD_con1->setError(0, '');
        $rowRep = $obBD_con1->getRowConsulta(88, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
        if (!empty($rowRep) && $obBD_con1->Error == 0) {
            $out['representanteLegal'] = array(
                'Representante_Nombre' => isset($rowRep['Representante_Nombre']) ? trim($rowRep['Representante_Nombre']) : '',
                'Representante_Identificacion' => isset($rowRep['Representante_Identificacion']) ? trim($rowRep['Representante_Identificacion']) : ''
            );
        }
    }
    echo json_encode($out);
    exit;
}

// Ajax: Precargar actividades del régimen en contrato
if (!empty($_REQUEST['precargarActividadesRegimen'])) {
    $resp = array('success' => false, 'agregadas' => 0);
    $con = intval(isset($_POST['Con_Cod']) ? $_POST['Con_Cod'] : 0);
    $reg = intval(isset($_POST['Reg_Cod']) ? $_POST['Reg_Cod'] : 0);
    if ($con <= 0 || $reg <= 0) {
        $resp['message'] = 'Contrato y régimen son obligatorios. Asigne un régimen al cliente en Admin.';
        echo json_encode($resp);
        exit;
    }
    $actividadesRegimen = $obBD_con1->getArrayConsulta(66, array('Reg_Cod' => $reg), $obBD_conexion);
    $serviciosContrato = $obBD_con1->getArrayConsulta(15, array('Con_Cod' => $con), $obBD_conexion);
    $serEnContrato = array();
    foreach ($serviciosContrato as $s) {
        if (isset($s['Ser_Cod'])) $serEnContrato[(int)$s['Ser_Cod']] = true;
    }
    $actividadesContrato = $obBD_con1->getArrayConsulta(16, array('Con_Cod' => $con), $obBD_conexion);
    $actEnContrato = array();
    foreach ($actividadesContrato as $a) {
        if (isset($a['Act_Cod'])) $actEnContrato[(int)$a['Act_Cod']] = true;
    }
    $agregadas = 0;
    foreach ($actividadesRegimen as $act) {
        $actCod = (int)$act['Act_Cod'];
        $serCod = (int)$act['Ser_Cod'];
        if (isset($actEnContrato[$actCod])) continue;
        if (!isset($serEnContrato[$serCod])) {
            $obBD_con1->operacionobBD(17, array('Con_Cod' => $con, 'Ser_Cod' => $serCod, 'Incluido' => 'S', 'Facturable' => 'N'), $obBD_conexion);
            $serEnContrato[$serCod] = true;
        }
        $obBD_con1->operacionobBD(18, array('Con_Cod' => $con, 'Act_Cod' => $actCod, 'Incluida' => 'S', 'Facturable' => 'N'), $obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $actEnContrato[$actCod] = true;
            $agregadas++;
        }
    }
    $resp['success'] = true;
    $resp['agregadas'] = $agregadas;
    $resp['message'] = $agregadas > 0 ? "Se agregaron $agregadas actividades del régimen." : "Las actividades del régimen ya estaban en el contrato.";
    echo json_encode($resp);
    exit;
}

$lista_clientes = $obBD_con1->getArrayConsulta(7, array('Emp_Cod' => $Ses_Emp_Cod, 'Dcl_Est' => 'ACTIVO'), $obBD_conexion);
$lista_contratos = $obBD_con1->getArrayConsulta(12, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
// Solo clientes que aún no tienen contrato (para el combo de nuevo contrato)
$dcl_con_contrato = array();
foreach ($lista_contratos as $con) {
    if (!empty($con['Dcl_Cod'])) $dcl_con_contrato[(int)$con['Dcl_Cod']] = true;
}
$clientes_sin_contrato = array();
foreach ($lista_clientes as $c) {
    if (empty($dcl_con_contrato[(int)$c['Dcl_Cod']])) $clientes_sin_contrato[] = $c;
}
$lista_servicios = $obBD_con1->getArrayConsulta(1, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
$lista_actividades = $obBD_con1->getArrayConsulta(2, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'EXA'; ?> - Contratos Despacho</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <?php require_once('../../mascaras/model1/estilos/estilos.php'); ?>
    <link href="aud_zoom.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script type="text/javascript" src="../../Librerias/jquery.min/jquery-1.11.3.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="../../mascaras/model1/js/bootstrap.min.js"></script>
    <style>
        .despacho-contratos-container { padding: 20px; background: #E8F0F7; min-height: 100vh; }
        .exa-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 50%, #5A9BD4 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(15,118,110,0.3);
            margin-bottom: 20px;
        }
        .exa-header h3 { margin: 0; font-size: 18px; font-weight: 600; }
        .config-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .config-header { background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white; padding: 6px 14px; border-radius: 10px 10px 0 0; margin: -20px -20px 20px -20px; font-size: 14px; }
        .config-header h4 { margin: 0; font-size: 14px; font-weight: 600; }
        .tabs-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
            overflow: hidden;
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
        .nav-tabs > li { margin-bottom: -2px; margin-right: 4px; flex-shrink: 0; }
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
        .nav-tabs > li > a:hover { background: #DEE7EF; color: #2C5D94; border-color: #cbd5e1; }
        .nav-tabs > li.active > a, .nav-tabs > li.active > a:hover, .nav-tabs > li.active > a:focus {
            background: #3d7bb8;
            color: white;
            border-color: #2C5D94;
            border-bottom: 2px solid #2C5D94;
            margin-bottom: -2px;
        }
        .tab-content { padding: 24px; background: #E8F0F7; }
        .tab-pane { background: transparent; display: none; }
        .tab-pane.active { display: block; }
        .aud-tabla { width: 100%; border-collapse: collapse; font-size: 13px; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); background: white; }
        .aud-tabla thead th { background: linear-gradient(135deg, #72A1CF 0%, #8EB7DD 100%); color: white; padding: 10px 14px; text-align: left; font-weight: 600; font-size: 12px; }
        .aud-tabla tbody td { padding: 8px 14px; border-bottom: 1px solid #e2e8f0; }
        .aud-tabla tbody tr:hover { background-color: #D1E6F4; }
        .form-control { border-radius: 8px; border: 1px solid #cbd5e1; padding: 8px 12px; }
        .btn-xs { margin: 1px 2px; }
        .form-contrato-compact .form-row-aligned {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 8px 16px;
            align-items: center;
            margin-bottom: 10px;
            max-width: 480px;
        }
        .form-contrato-compact .form-row-aligned:last-of-type { margin-bottom: 0; }
        .form-contrato-compact .form-row-aligned label { text-align: left; margin: 0; font-weight: 600; font-size: 13px; }
        .form-contrato-compact .form-row-aligned .form-control,
        .form-contrato-compact .form-row-aligned select.form-control,
        .form-contrato-compact .form-row-aligned input.form-control { width: 220px; min-width: 220px; max-width: 220px; }
        .form-contrato-compact .form-row-aligned input.form-control,
        .form-contrato-compact .form-row-aligned select.form-control { height: 30px; padding: 4px 10px; box-sizing: border-box; line-height: 1.4; }
        .form-contrato-compact .form-row-meses-anual { display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px; max-width: 480px; }
        .form-contrato-compact .form-row-meses-anual .control-label { margin: 0; font-weight: 600; font-size: 13px; }
        .form-contrato-compact .form-row-meses-anual .meses-anual-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px 12px; }
        .form-contrato-compact .form-row-meses-anual .meses-anual-grid label { margin: 0; font-weight: 500; cursor: pointer; font-size: 13px; }
        .col-acciones { white-space: nowrap; min-width: 420px; width: 1%; padding: 8px 16px !important; }
        .aud-tabla th.col-acciones, .aud-tabla td.col-acciones { min-width: 420px; }
        .col-acciones .btn { margin: 0 4px !important; }
        .col-cant-actividades { width: 1%; white-space: nowrap; text-align: center; }
        #modalVerServicios .modal-dialog { width: 90%; max-width: 700px; margin: 30px auto; }
        #modalVerServicios { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1050; overflow-x: hidden; overflow-y: auto; background: rgba(15, 23, 42, 0.5); }
        #modalVerServicios:not(.in) { display: none !important; }
        #modalVerServicios.in { display: flex !important; align-items: flex-start; justify-content: center; padding: 20px; }
        #modalVerServicios .modal-content { border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); border: none; overflow: hidden; background: #fff; }
        #modalVerServicios .modal-header { position: relative; text-align: center; padding: 16px 40px 16px 20px; background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%) !important; }
        #modalVerServicios .modal-header .modal-title { width: 100%; text-align: center; margin: 0; }
        #modalVerServicios .modal-header .close { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); margin: 0; }
        #modalVerServicios .modal-body { background: #f8fafc; padding: 20px; }
        #modalVerServicios .modal-footer { background: #f1f5f9 !important; border-top: 1px solid #e2e8f0; }
        #modalVerServiciosBackdrop { background: rgba(15, 23, 42, 0.65) !important; }
        /* Modal Modificar Contrato (flotante) */
        #modalModificarContrato { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1060; overflow-x: hidden; overflow-y: auto; display: none !important; background: rgba(15, 23, 42, 0.5); }
        #modalModificarContrato.modal-in { display: flex !important; align-items: center; justify-content: center; padding: 20px; }
        #modalModificarContrato .modal-dialog { margin: 0 auto; max-width: 440px; width: 100%; }
        #modalModificarContrato .modal-content { border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); border: none; overflow: hidden; background: #fff; }
        #modalModificarContrato .modal-header { position: relative; text-align: center; padding: 16px 40px 16px 20px; background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%) !important; }
        #modalModificarContrato .modal-header .modal-title { width: 100%; text-align: center; margin: 0; }
        #modalModificarContrato .modal-header .close { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); margin: 0; }
        #modalModificarContrato .modal-body { background: #f8fafc; padding: 24px; }
        #modalModificarContrato .form-modificar-grid { display: flex; flex-direction: column; gap: 16px; }
        #modalModificarContrato .form-group { margin: 0; }
        #modalModificarContrato .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #334155; }
        #modalModificarContrato .form-group .form-control,
        #modalModificarContrato .form-group select.form-control { width: 100%; box-sizing: border-box; }
        #modalModificarContrato .form-modificar-fechas { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 20px; }
        #modalModificarContrato .form-modificar-fechas .form-control { width: 100%; }
        #modalModificarContrato .meses-anual-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px 16px; }
        #modalModificarContrato .meses-anual-grid label { margin: 0; font-weight: 500; cursor: pointer; font-size: 13px; }
        #modalModificarContrato #modalClienteNombre { padding: 10px 12px; background: #e2e8f0; border-radius: 8px; font-size: 14px; }
        #modalModificarContrato .modal-footer { background: #f1f5f9 !important; }
        #modalModificarBackdrop { background: rgba(15, 23, 42, 0.65) !important; }
        @media (max-width: 500px) { #modalModificarContrato .form-modificar-fechas { grid-template-columns: 1fr; } }
        /* Propuesta Servicios Adicionales */
        #contenidoPropuesta .propuesta-titulo-principal { text-align: center; color: #2C5D94; font-weight: 700; font-size: 18px; margin-bottom: 4px; }
        #contenidoPropuesta .propuesta-titulo-secundario { text-align: center; color: #2C5D94; font-weight: 700; font-size: 16px; margin-bottom: 20px; }
        #contenidoPropuesta .propuesta-datos-cliente { margin-bottom: 20px; }
        #contenidoPropuesta .propuesta-seccion { margin-top: 24px; }
        #contenidoPropuesta .propuesta-seccion h3 { color: #2C5D94; font-weight: 700; font-size: 14px; text-transform: uppercase; margin-bottom: 10px; }
        #contenidoPropuesta .propuesta-tabla { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 13px; }
        #contenidoPropuesta .propuesta-tabla th { background: #2C5D94; color: #fff; padding: 10px 12px; text-align: left; }
        #contenidoPropuesta .propuesta-tabla td { border: 1px solid #dee2e6; padding: 10px 12px; vertical-align: top; }
        #contenidoPropuesta .propuesta-tabla tr.subcab { background: #e2e8f0; font-weight: 600; }
        #contenidoPropuesta .propuesta-notas { margin-top: 20px; }
        #contenidoPropuesta .propuesta-notas h3 { color: #5A9BD4; }
        #contenidoPropuesta .propuesta-notas ul { margin: 8px 0 0 20px; }
        #contenidoPropuesta .propuesta-aceptacion { margin-top: 24px; }
        #contenidoPropuesta .propuesta-firmas { display: flex; gap: 40px; margin-top: 32px; flex-wrap: wrap; }
        #contenidoPropuesta .propuesta-firma { flex: 1; min-width: 200px; }
        #contenidoPropuesta .propuesta-firma .linea { border-bottom: 1px solid #333; margin-top: 24px; padding-top: 4px; font-size: 12px; color: #555; }
        @media print { body * { visibility: hidden; } #contenidoPropuesta, #contenidoPropuesta * { visibility: visible; } #contenidoPropuesta { position: absolute; left: 0; top: 0; width: 100%; max-width: none; } }
    </style>
</head>
<body>
<div id="set1" class="container-fluid despacho-contratos-container">
    <div class="exa-header">
        <h3><span class="glyphicon glyphicon-file"></span> Contratos del Despacho</h3>
    </div>

    <div class="tabs-wrapper">
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#tab-contratos" data-toggle="tab"><span class="glyphicon glyphicon-file"></span> Contratos</a></li>
        <li role="presentation"><a href="#tab-configurar-servicios" data-toggle="tab"><span class="glyphicon glyphicon-cog"></span> Configurar servicios</a></li>
        <li role="presentation"><a href="#tab-propuesta-adicionales" data-toggle="tab"><span class="glyphicon glyphicon-list-alt"></span> Propuesta servicios adicionales</a></li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="tab-contratos">
            <div class="config-card">
                <div class="config-header"><h4><span class="glyphicon glyphicon-edit"></span> Nuevo Contrato</h4></div>
                <form id="formContrato" class="form-contrato-compact">
                    <input type="hidden" name="Con_Cod" id="Con_Cod" value="" />
                    <div>
                        <div class="form-row-aligned">
                            <label class="control-label">Cliente despacho <span class="text-danger">*</span></label>
                            <select name="Dcl_Cod" id="Dcl_Cod" class="form-control input-sm">
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($clientes_sin_contrato as $c): ?>
                                <option value="<?php echo $c['Dcl_Cod']; ?>"><?php echo htmlspecialchars($c['Cliente_Nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row-aligned">
                            <label class="control-label">N&uacute;mero</label>
                            <input type="text" name="Con_Numero" id="Con_Numero" class="form-control input-sm" readonly placeholder="Autom&aacute;tico" style="background:#f1f5f9;" />
                        </div>
                        <div class="form-row-aligned">
                            <label class="control-label">Tipo</label>
                            <select name="Con_Tipo" id="Con_Tipo" class="form-control input-sm">
                                <option value="MENSUAL">Mensual</option>
                                <option value="ANUAL">Anual</option>
                            </select>
                        </div>
                        <div class="form-row-meses-anual" id="rowMesesContratoAnual" style="display:none;">
                            <label class="control-label">Meses (para facturación anual)</label>
                            <div class="meses-anual-grid">
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="01" /> Ene</label>
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="02" /> Feb</label>
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="03" /> Mar</label>
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="04" /> Abr</label>
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="05" /> May</label>
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="06" /> Jun</label>
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="07" /> Jul</label>
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="08" /> Ago</label>
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="09" /> Sep</label>
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="10" /> Oct</label>
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="11" /> Nov</label>
                                <label><input type="checkbox" name="meses_contrato_anual[]" value="12" /> Dic</label>
                            </div>
                            <input type="hidden" name="Con_Meses_Anual" id="Con_Meses_Anual" value="" />
                            <small class="text-muted" style="display:block; margin-top:6px;">Cuando el tipo sea <strong>Anual</strong>, seleccione en qué mes(es) del año desea facturar.</small>
                        </div>
                        <div class="form-row-aligned">
                            <label class="control-label">Valor</label>
                            <input type="text" name="Con_Valor" id="Con_Valor" class="form-control input-sm" value="0" />
                        </div>
                        <div class="form-row-aligned">
                            <label class="control-label">Fecha inicio <span class="text-danger">*</span></label>
                            <input type="date" name="Con_Fecha_Inicio" id="Con_Fecha_Inicio" class="form-control input-sm" />
                        </div>
                        <div class="form-row-aligned">
                            <label class="control-label">Fecha fin <small class="text-muted">(opcional)</small></label>
                            <input type="date" name="Con_Fecha_Fin" id="Con_Fecha_Fin" class="form-control input-sm" placeholder="Opcional" />
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:8px; margin-bottom:0;">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarContrato"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar contrato</button>
                        <button type="button" class="btn btn-default btn-sm" id="btnNuevoContrato"><span class="glyphicon glyphicon-plus"></span> Nuevo</button>
                    </div>
                </form>
            </div>
            <div class="config-card" style="margin-top:20px;">
                <div class="config-header"><h4><span class="glyphicon glyphicon-list"></span> Contratos registrados</h4></div>
                <table id="gridContratos" class="aud-tabla">
                    <thead><tr><th>Cliente</th><th>N&uacute;mero</th><th>Inicio</th><th>Fin</th><th>Tipo</th><th>Valor</th><th>Estado</th><th class="col-acciones" style="text-align:center;">Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($lista_contratos as $con): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($con['Cliente_Nombre']); ?></td>
                        <td><?php echo htmlspecialchars(isset($con['Con_Numero']) ? $con['Con_Numero'] : '-'); ?></td>
                        <td><?php echo $con['Con_Fecha_Inicio']; ?></td>
                        <td><?php echo (!empty($con['Con_Fecha_Fin']) ? $con['Con_Fecha_Fin'] : '-'); ?></td>
                        <td><?php echo $con['Con_Tipo']; ?></td>
                        <td><?php echo number_format(isset($con['Con_Valor']) ? $con['Con_Valor'] : 0, 2); ?></td>
                        <td><?php echo $con['Con_Est']; ?></td>
                        <td class="col-acciones col-accion" style="text-align:center;">
                            <button type="button" class="btn btn-xs btn-success btn-ver-servicios" data-con="<?php echo $con['Con_Cod']; ?>" data-cliente="<?php echo htmlspecialchars($con['Cliente_Nombre']); ?>" data-num="<?php echo htmlspecialchars(isset($con['Con_Numero']) ? $con['Con_Numero'] : 'Nº'.$con['Con_Cod']); ?>"><span class="glyphicon glyphicon-list"></span> Ver Servicios</button>
                            <button type="button" class="btn btn-xs btn-info btn-configurar-contrato" data-con="<?php echo $con['Con_Cod']; ?>"><span class="glyphicon glyphicon-cog"></span> Configurar</button>
                            <button type="button" class="btn btn-xs btn-editar-modificar btn-modificar-contrato" title="Modificar" data-con="<?php echo $con['Con_Cod']; ?>" data-dcl="<?php echo $con['Dcl_Cod']; ?>" data-cliente="<?php echo htmlspecialchars($con['Cliente_Nombre']); ?>" data-num="<?php echo htmlspecialchars(isset($con['Con_Numero']) ? $con['Con_Numero'] : ''); ?>" data-ini="<?php echo $con['Con_Fecha_Inicio']; ?>" data-fin="<?php echo $con['Con_Fecha_Fin']; ?>" data-tipo="<?php echo $con['Con_Tipo']; ?>" data-meses="<?php echo htmlspecialchars(isset($con['Con_Meses_Anual']) ? $con['Con_Meses_Anual'] : ''); ?>" data-val="<?php echo isset($con['Con_Valor']) ? $con['Con_Valor'] : 0; ?>"><span class="glyphicon glyphicon-pencil"></span></button>
                            <button type="button" class="btn btn-xs btn-danger btn-eliminar-contrato" data-con="<?php echo $con['Con_Cod']; ?>" title="Eliminar"><span aria-hidden="true">&times;</span></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="tab-configurar-servicios">
            <div class="config-card">
                <div class="config-header"><h4><span class="glyphicon glyphicon-cog"></span> Configurar servicios y actividades del contrato</h4></div>
                <div class="form-group">
                    <label class="control-label">Seleccione el contrato a configurar:</label>
                    <select id="selContratoConfigurar" class="form-control" style="max-width:400px;">
                        <option value="">-- Seleccione contrato --</option>
                        <?php foreach ($lista_contratos as $con): ?>
                        <option value="<?php echo $con['Con_Cod']; ?>"><?php echo htmlspecialchars($con['Cliente_Nombre']); ?> - <?php echo htmlspecialchars(isset($con['Con_Numero']) ? $con['Con_Numero'] : 'Nº'.$con['Con_Cod']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="panelConfigurarServicios" style="display:none; margin-top:20px;">
                    <div id="panelPrecargarRegimen" style="display:none; margin-bottom:16px; padding:12px 16px; background:#DEE7EF; border-radius:8px; border-left:4px solid #2C5D94;">
                        <strong><span class="glyphicon glyphicon-book"></span> El cliente tiene régimen asignado.</strong>
                        <button type="button" class="btn btn-success btn-sm" id="btnPrecargarRegimen" style="margin-left:12px;"><span class="glyphicon glyphicon-download"></span> Precargar actividades del régimen</button>
                        <span id="lblPrecargarResult" class="text-muted" style="margin-left:8px;"></span>
                    </div>
                    <h5>Actividades por servicio</h5>
                    <p class="text-muted" style="font-size:12px; margin-bottom:10px;">Marque las actividades que desea incluir. El servicio se agregar&aacute; autom&aacute;ticamente si no est&aacute; en el contrato. Puede agregar, quitar o modificar actividades seg&uacute;n el contrato.</p>
                    <div id="actividadesPorServicio" style="max-height:280px; overflow-y:auto; border:1px solid #dee2e6; border-radius:8px; padding:12px; background:#f8fafc;"></div>
                    <h5 style="margin-top:20px;">Servicios contratados</h5>
                    <table id="tablaServiciosContrato" class="aud-tabla"><thead><tr><th>Servicio</th><th class="col-cant-actividades">Cant.</th><th>Incluido</th><th>Facturable</th><th>Valor</th><th style="width:90px; text-align:center;">Acci&oacute;n</th></tr></thead><tbody></tbody></table>
                    <div style="margin-top:16px;">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarServiciosActividades"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar cambios</button>
                    </div>
                </div>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="tab-propuesta-adicionales">
            <div class="config-card">
                <div class="config-header"><h4><span class="glyphicon glyphicon-list-alt"></span> Propuesta de Servicios Adicionales</h4></div>
                <p class="text-muted" style="margin-bottom: 12px;">Seleccione un contrato para generar la propuesta (cliente, servicios contratados vs no incluidos y catálogo de servicios adicionales con precios).</p>
                <div class="form-inline" style="margin-bottom: 16px;">
                    <label class="control-label" style="margin-right: 8px;">Contrato:</label>
                    <select id="selContratoPropuesta" class="form-control input-sm" style="max-width: 35%; min-width: 180px;">
                        <option value="">-- Seleccione el contrato --</option>
                        <?php foreach ($lista_contratos as $con): ?>
                        <option value="<?php echo $con['Con_Cod']; ?>"><?php echo htmlspecialchars($con['Cliente_Nombre']); ?> - <?php echo htmlspecialchars(isset($con['Con_Numero']) ? $con['Con_Numero'] : 'Nº'.$con['Con_Cod']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-primary btn-sm" id="btnGenerarPropuesta" style="margin-left: 12px; margin-right: 10px;"><span class="glyphicon glyphicon-file"></span> Generar propuesta</button>
                    <button type="button" class="btn btn-success btn-sm" id="btnGenerarPdfPropuesta" style="margin-right: 10px; display: none;"><span class="glyphicon glyphicon-save-file"></span> Generar PDF</button>
                    <button type="button" class="btn btn-info btn-sm" id="btnDescargarWordPropuesta" style="display: none;"><span class="glyphicon glyphicon-download-alt"></span> Descargar en Word</button>
                </div>
                <div id="contenidoPropuesta" style="display: none; max-width: 900px; margin: 0 auto; padding: 24px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; font-size: 14px;"></div>
            </div>
        </div>
    </div>
    </div>

    <input type="hidden" id="modalConCod" value="" />
</div>

<!-- Modal Ver Servicios (oculto al cargar, se muestra solo al clic en Ver Servicios) -->
<div class="modal fade" id="modalVerServicios" tabindex="-1" role="dialog" aria-labelledby="modalVerServiciosTitle" aria-hidden="true" data-backdrop="true" data-keyboard="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color: white; opacity: 1;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalVerServiciosTitle"><span class="glyphicon glyphicon-list"></span> Servicios y actividades del contrato</h4>
            </div>
            <div class="modal-body">
                <p id="modalVerServiciosTitulo" class="text-muted" style="margin-bottom: 12px;"></p>
                <div id="modalVerServiciosContenido" style="max-height: 400px; overflow-y: auto;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modificar Contrato (flotante) -->
<div class="modal" id="modalModificarContrato" tabindex="-1" role="dialog" aria-labelledby="modalModificarContratoTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white;">
                <button type="button" class="close btn-cerrar-modal-modificar" aria-label="Cerrar" style="color: white; opacity: 1;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalModificarContratoTitle"><span class="glyphicon glyphicon-pencil"></span> Modificar contrato</h4>
            </div>
            <div class="modal-body">
                <form id="formModificarContrato">
                    <input type="hidden" id="modalCon_Cod" value="" />
                    <input type="hidden" id="modalDcl_Cod" value="" />
                    <div class="form-group">
                        <label class="control-label">Cliente</label>
                        <p id="modalClienteNombre" class="form-control-static" style="font-weight:600; margin:0;"></p>
                    </div>
                    <div class="form-modificar-grid">
                        <div class="form-group">
                            <label class="control-label">N&uacute;mero</label>
                            <input type="text" id="modalCon_Numero" class="form-control input-sm" readonly style="background:#e2e8f0;" />
                        </div>
                        <div class="form-group">
                            <label class="control-label">Tipo</label>
                            <select id="modalCon_Tipo" class="form-control input-sm">
                                <option value="MENSUAL">Mensual</option>
                                <option value="ANUAL">Anual</option>
                            </select>
                        </div>
                        <div class="form-group" id="modalRowMesesAnual" style="display:none;">
                            <label class="control-label">Meses (para facturación anual)</label>
                            <div class="meses-anual-grid">
                                <label><input type="checkbox" class="modal-mes-anual" value="01" /> Ene</label>
                                <label><input type="checkbox" class="modal-mes-anual" value="02" /> Feb</label>
                                <label><input type="checkbox" class="modal-mes-anual" value="03" /> Mar</label>
                                <label><input type="checkbox" class="modal-mes-anual" value="04" /> Abr</label>
                                <label><input type="checkbox" class="modal-mes-anual" value="05" /> May</label>
                                <label><input type="checkbox" class="modal-mes-anual" value="06" /> Jun</label>
                                <label><input type="checkbox" class="modal-mes-anual" value="07" /> Jul</label>
                                <label><input type="checkbox" class="modal-mes-anual" value="08" /> Ago</label>
                                <label><input type="checkbox" class="modal-mes-anual" value="09" /> Sep</label>
                                <label><input type="checkbox" class="modal-mes-anual" value="10" /> Oct</label>
                                <label><input type="checkbox" class="modal-mes-anual" value="11" /> Nov</label>
                                <label><input type="checkbox" class="modal-mes-anual" value="12" /> Dic</label>
                            </div>
                            <small class="text-muted" style="display:block; margin-top:6px;">Cuando el tipo sea <strong>Anual</strong>, seleccione en qué mes(es) del año desea facturar.</small>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Valor</label>
                            <input type="text" id="modalCon_Valor" class="form-control input-sm" value="0" />
                        </div>
                        <div class="form-modificar-fechas">
                            <div class="form-group">
                                <label class="control-label">Fecha inicio <span class="text-danger">*</span></label>
                                <input type="date" id="modalCon_Fecha_Inicio" class="form-control input-sm" />
                            </div>
                            <div class="form-group">
                                <label class="control-label">Fecha fin <small class="text-muted">(opcional)</small></label>
                                <input type="date" id="modalCon_Fecha_Fin" class="form-control input-sm" placeholder="Opcional" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e2e8f0; padding:16px 24px; background:#f1f5f9;">
                <button type="button" class="btn btn-default btn-cerrar-modal-modificar"><span class="glyphicon glyphicon-remove"></span> Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarModalModificar"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function () {
    var urlBase = <?php echo json_encode($_SERVER['PHP_SELF']); ?>;
    var todasActividades = <?php echo json_encode($lista_actividades); ?>;
    var select2Opts = { language: { noResults: function() { return 'No se encontraron resultados'; }, searching: function() { return 'Buscando...'; } }, allowClear: true };
    function initSelect2Buscable($el, extra) {
        if (!$el.length || typeof $el.select2 !== 'function') return;
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        $el.select2(extra ? $.extend(true, {}, select2Opts, extra) : select2Opts);
        $el.off('select2:open.s2nc select2:selecting.s2nc').on('select2:open.s2nc', function () { $el.data('select2OpenAt', Date.now()); }).on('select2:selecting.s2nc', function (ev) {
            var t = $el.data('select2OpenAt'); if (t && (Date.now() - t) < 300) ev.preventDefault();
        });
    }
    initSelect2Buscable($('#selContratoConfigurar'), { placeholder: '-- Seleccione contrato --', width: '100%' });
    initSelect2Buscable($('#Dcl_Cod'), { placeholder: '-- Seleccione --' });
    initSelect2Buscable($('#selContratoPropuesta'), { placeholder: '-- Seleccione el contrato --', width: '35%' });
    var nombreDespacho = <?php echo json_encode((isset($Ses_Emp_Nom) && trim($Ses_Emp_Nom) !== '') ? trim($Ses_Emp_Nom) : (isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'DESPACHO CONTABLE')); ?>;
    var contratosRegimen = <?php
        $map = array();
        foreach ($lista_contratos as $c) {
            $map[(int)$c['Con_Cod']] = isset($c['Reg_Cod']) ? (int)$c['Reg_Cod'] : 0;
        }
        echo json_encode($map);
    ?>;

    function syncConMesesAnual() {
        var meses = [];
        $('#rowMesesContratoAnual input[name="meses_contrato_anual[]"]:checked').each(function () { meses.push($(this).val()); });
        $('#Con_Meses_Anual').val(meses.sort().join(','));
        }
    function toggleRowMesesContrato() {
        var tipo = $('#Con_Tipo').val();
        if (tipo === 'ANUAL') { $('#rowMesesContratoAnual').show(); } else { $('#rowMesesContratoAnual').hide(); $('#Con_Meses_Anual').val(''); $('#rowMesesContratoAnual input[name="meses_contrato_anual[]"]').prop('checked', false); }
    }
    $('#Con_Tipo').on('change', toggleRowMesesContrato);
    $('#rowMesesContratoAnual').on('change', 'input[name="meses_contrato_anual[]"]', syncConMesesAnual);

    $('#btnGuardarContrato').on('click', function () {
        var dcl = $('#Dcl_Cod').val(), fecIni = $('#Con_Fecha_Inicio').val(), fecFin = $('#Con_Fecha_Fin').val();
        if (!dcl || !fecIni) { alert('Cliente y fecha inicio son obligatorios.'); return; }
        syncConMesesAnual();
        public $btn = $(this);
        $btn.prop('disabled', true);
        $.post(urlBase, {
            guardarContrato: 1, Con_Cod: $('#Con_Cod').val(), Dcl_Cod: dcl, Con_Numero: $('#Con_Numero').val(),
            Con_Fecha_Inicio: fecIni, Con_Fecha_Fin: fecFin, Con_Tipo: $('#Con_Tipo').val(), Con_Meses_Anual: $('#Con_Meses_Anual').val(), Con_Valor: $('#Con_Valor').val()
        }, function (r) {
            if (r && r.success) { alert('Guardado correctamente.'); location.reload(); }
            else alert(r && r.message ? r.message : 'Error al guardar.');
        }, 'json').fail(function (xhr, status, err) {
            alert('Error de conexión. Verifique la consola del navegador.');
            console.error('Guardar contrato:', status, err, xhr.responseText);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    function refrescarTablaServicios(con, rows) {
        var html = '';
        $.each(rows || [], function (i, row) {
            var cnt = parseInt(row.Cnt_Actividades, 10) || 0;
            html += '<tr><td>' + (row.Ser_Nombre || '') + '</td><td class="col-cant-actividades">' + cnt + '</td><td>' + (row.Incluido || '') + '</td><td>' + (row.Facturable || '') + '</td><td>' + (row.Valor_Unitario || 0) + '</td>';
            html += '<td style="text-align:center;" class="col-accion"><button type="button" class="btn btn-xs btn-danger btn-quitar-servicio" data-conser="' + (row.ConSer_Cod || '') + '" data-ser="' + (row.Ser_Cod || '') + '" title="Eliminar servicio"><span aria-hidden="true">&times;</span></button></td></tr>';
        });
        $('#tablaServiciosContrato tbody').html(html || '<tr><td colspan="6">Sin servicios</td></tr>');
    }

    var estadoConfig = { actividades: [], servicios: [], seleccionados: {}, serviciosEliminados: {} };

    function construirActividadesPorServicio(con, actividadesContrato) {
        estadoConfig.actividades = actividadesContrato || [];
        estadoConfig.serviciosEliminados = {};
        var mapActEnContrato = {};
        $.each(estadoConfig.actividades, function (i, row) {
            mapActEnContrato[row.Act_Cod] = { ConAct_Cod: row.ConAct_Cod, Act_Cod: row.Act_Cod, Ser_Cod: row.Ser_Cod };
        });
        estadoConfig.seleccionados = $.extend({}, mapActEnContrato);
        var porServicio = {};
        $.each(todasActividades || [], function (i, a) {
            var serNom = a.Ser_Nombre || 'Otros';
            if (!porServicio[serNom]) porServicio[serNom] = [];
            porServicio[serNom].push(a);
        });
        var html = '';
        $.each(Object.keys(porServicio).sort(), function (i, serNom) {
            var acts = porServicio[serNom];
            html += '<div class="act-grupo-servicio" style="margin-bottom:16px;">';
            html += '<div style="background:#72A1CF; color:white; padding:6px 12px; border-radius:6px 6px 0 0; font-weight:600; font-size:13px;">' + (serNom || '') + '</div>';
            html += '<div style="background:white; border:1px solid #dee2e6; border-top:none; border-radius:0 0 6px 6px; padding:8px 12px;">';
            $.each(acts, function (j, act) {
                var enContrato = estadoConfig.seleccionados[act.Act_Cod] && !estadoConfig.serviciosEliminados[act.Ser_Cod];
                var checked = enContrato ? ' checked' : '';
                var conActCod = (mapActEnContrato[act.Act_Cod] || {}).ConAct_Cod || '';
                html += '<label style="display:block; margin:4px 0; cursor:pointer; font-size:13px;">';
                html += '<input type="checkbox" class="chk-actividad-contrato" data-act="' + act.Act_Cod + '" data-ser="' + (act.Ser_Cod || '') + '" data-conact="' + (conActCod || '') + '"' + checked + ' /> ';
                html += '<span>' + (act.Act_Nombre || '') + '</span>';
                html += '</label>';
            });
            html += '</div></div>';
        });
        $('#actividadesPorServicio').html(html || '<p class="text-muted">No hay actividades configuradas.</p>');
        $('#actividadesPorServicio .chk-actividad-contrato').off('change').on('change', function () {
            public $chk = $(this), act = parseInt($chk.data('act'), 10), ser = parseInt($chk.data('ser'), 10);
            if ($chk.is(':checked')) {
                estadoConfig.seleccionados[act] = { Act_Cod: act, Ser_Cod: ser };
                delete estadoConfig.serviciosEliminados[ser];
            } else {
                delete estadoConfig.seleccionados[act];
            }
            actualizarVistaServicios(con);
        });
    }

    function actualizarVistaServicios(con) {
        var porServicio = {};
        $.each(todasActividades || [], function (i, a) {
            var serNom = a.Ser_Nombre || 'Otros';
            if (!porServicio[serNom]) porServicio[serNom] = { Ser_Cod: a.Ser_Cod, acts: [] };
            porServicio[serNom].acts.push(a);
        });
        var rows = [];
        $.each(estadoConfig.servicios || [], function (i, s) {
            if (estadoConfig.serviciosEliminados[s.Ser_Cod]) return;
            var cnt = 0;
            $.each((porServicio[s.Ser_Nombre] || {}).acts || [], function (j, a) {
                if (estadoConfig.seleccionados[a.Act_Cod]) cnt++;
            });
            rows.push({ ConSer_Cod: s.ConSer_Cod, Ser_Cod: s.Ser_Cod, Ser_Nombre: s.Ser_Nombre, Incluido: s.Incluido, Facturable: s.Facturable, Valor_Unitario: s.Valor_Unitario, Cnt_Actividades: cnt });
        });
        $.each(porServicio, function (serNom, data) {
            var cnt = 0;
            $.each(data.acts || [], function (j, a) {
                if (estadoConfig.seleccionados[a.Act_Cod]) cnt++;
            });
            if (cnt > 0) {
                var yaEnLista = false;
                $.each(rows, function (j, r) {
                    if (r.Ser_Nombre === serNom) { yaEnLista = true; return false; }
                });
                if (!yaEnLista) {
                    rows.push({ ConSer_Cod: '', Ser_Cod: data.Ser_Cod, Ser_Nombre: serNom, Incluido: 'S', Facturable: 'N', Valor_Unitario: 0, Cnt_Actividades: cnt });
                }
            }
        });
        refrescarTablaServicios(con, rows);
    }

    function cargarConfigurarServicios(con) {
        $('#modalConCod').val(con);
        $('#panelConfigurarServicios').show();
        $('#lblPrecargarResult').text('');
        var reg = contratosRegimen[con] || 0;
        if (reg > 0) {
            $('#panelPrecargarRegimen').show();
            $('#btnPrecargarRegimen').data('reg', reg);
        } else {
            $('#panelPrecargarRegimen').hide();
        }
        $.get(urlBase, { serviciosContrato: 1, Con_Cod: con }, function (r) {
            estadoConfig.servicios = r.rows || [];
            $.get(urlBase, { actividadesContrato: 1, Con_Cod: con }, function (r2) {
                construirActividadesPorServicio(con, r2.rows);
                actualizarVistaServicios(con);
            }, 'json');
        }, 'json');
    }

    $('#selContratoConfigurar').on('change', function () {
        var con = $(this).val();
        if (!con) { $('#panelConfigurarServicios').hide(); return; }
        cargarConfigurarServicios(con);
    });

    $('#btnPrecargarRegimen').on('click', function () {
        var con = $('#modalConCod').val(), reg = $(this).data('reg');
        if (!con || !reg) { alert('No hay régimen asignado al cliente. Asigne el régimen en Admin > Clientes.'); return; }
        public $btn = $(this);
        $btn.prop('disabled', true);
        $('#lblPrecargarResult').text('Cargando...');
        $.post(urlBase, { precargarActividadesRegimen: 1, Con_Cod: con, Reg_Cod: reg }, function (r) {
            if (r && r.success) {
                $('#lblPrecargarResult').text(r.message || '');
                cargarConfigurarServicios(con);
            } else {
                alert(r && r.message ? r.message : 'Error al precargar.');
            }
        }, 'json').fail(function () {
            alert('Error de conexión.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.btn-configurar-contrato', function () {
        var con = $(this).data('con');
        $('a[href="#tab-configurar-servicios"]').tab('show');
        $('#selContratoConfigurar').val(con).trigger('change');
        cargarConfigurarServicios(con);
    });

    function mostrarModalServicios() {
        public $modal = $('#modalVerServicios');
        $modal.appendTo('body');
        $modal.addClass('in').css({ display: 'block', visibility: 'visible' }).attr('aria-hidden', 'false');
        $('body').addClass('modal-open');
        $('#modalVerServiciosBackdrop').remove();
        $('<div class="modal-backdrop fade in" id="modalVerServiciosBackdrop" style="position:fixed;top:0;left:0;right:0;bottom:0;background:#000;opacity:0.5;z-index:1040;"></div>')
            .prependTo('body').on('click', function () { ocultarModalServicios(); });
        $(document).on('keydown.cerrarModalServicios', function (ev) {
            if (ev.which === 27) { ocultarModalServicios(); $(document).off('keydown.cerrarModalServicios'); }
        });
    }
    function ocultarModalServicios() {
        public $modal = $('#modalVerServicios');
        $modal.removeClass('in').css({ display: 'none', visibility: 'hidden' }).attr('aria-hidden', 'true');
        $('body').removeClass('modal-open');
        $('#modalVerServiciosBackdrop').remove();
        $(document).off('keydown.cerrarModalServicios');
    }
    $(document).on('click', '.btn-ver-servicios', function (e) {
        e.preventDefault();
        e.stopPropagation();
        public $btn = $(this);
        var con = parseInt($btn.attr('data-con') || $btn.data('con') || 0, 10);
        var cliente = ($btn.attr('data-cliente') || $btn.data('cliente') || '').toString();
        var num = ($btn.attr('data-num') || $btn.data('num') || '').toString();
        if (!con) { alert('No se pudo obtener el contrato.'); return; }
        $('#modalVerServiciosTitulo').text(cliente + ' - Contrato ' + num);
        $('#modalVerServiciosContenido').html('<p class="text-muted"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando...</p>');
        mostrarModalServicios();
        $.ajax({
            url: urlBase,
            type: 'GET',
            data: { actividadesContrato: 1, Con_Cod: con },
            dataType: 'json',
            success: function (r) {
                var acts = (r && r.rows) ? r.rows : [];
                if (acts.length === 0) {
                    $('#modalVerServiciosContenido').html('<p class="text-muted">No hay actividades contratadas en este contrato.</p>');
                    return;
                }
                var porServicio = {};
                $.each(acts, function (i, a) {
                    var serNom = (a.Ser_Nombre || 'Otros').toString();
                    if (!porServicio[serNom]) porServicio[serNom] = [];
                    porServicio[serNom].push(a);
                });
                var html = '';
                $.each(Object.keys(porServicio).sort(), function (i, serNom) {
                    var lista = porServicio[serNom];
                    html += '<div style="margin-bottom: 16px;">';
                    html += '<div style="background: #72A1CF; color: white; padding: 6px 12px; border-radius: 6px 6px 0 0; font-weight: 600; font-size: 13px;">' + serNom + '</div>';
                    html += '<ul style="margin: 0; padding: 10px 12px 10px 28px; background: #f8fafc; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 6px 6px;">';
                    $.each(lista, function (j, act) {
                        html += '<li style="margin: 4px 0;">' + (act.Act_Nombre || '').toString() + '</li>';
                    });
                    html += '</ul></div>';
                });
                $('#modalVerServiciosContenido').html(html);
            },
            error: function (xhr, status, err) {
                $('#modalVerServiciosContenido').html('<p class="text-danger">Error al cargar las actividades. Verifique la consola.</p>');
                console.error('Ver servicios:', status, err, xhr.responseText);
            }
        });
    });

    function mostrarModalModificar() {
        public $modal = $('#modalModificarContrato');
        $modal.appendTo('body');
        $modal.addClass('modal-in').css({ display: 'block', visibility: 'visible' }).attr('aria-hidden', 'false');
        $('body').addClass('modal-open');
        $('#modalModificarBackdrop').remove();
        $('<div class="modal-backdrop fade in" id="modalModificarBackdrop" style="position:fixed;top:0;left:0;right:0;bottom:0;background:#000;opacity:0.5;z-index:1055;"></div>')
            .prependTo('body').on('click', function () { ocultarModalModificar(); });
        $(document).on('keydown.cerrarModalModificar', function (ev) {
            if (ev.which === 27) { ocultarModalModificar(); $(document).off('keydown.cerrarModalModificar'); }
        });
    }
    function ocultarModalModificar() {
        public $modal = $('#modalModificarContrato');
        $modal.removeClass('modal-in').css({ display: 'none', visibility: 'hidden' }).attr('aria-hidden', 'true');
        $('body').removeClass('modal-open');
        $('#modalModificarBackdrop').remove();
        $(document).off('keydown.cerrarModalModificar');
    }
    $(document).on('click', '.btn-modificar-contrato', function () {
        public $t = $(this);
        $('#modalCon_Cod').val($t.data('con'));
        $('#modalDcl_Cod').val($t.data('dcl'));
        $('#modalClienteNombre').text($t.data('cliente') || '');
        $('#modalCon_Numero').val($t.data('num') || '');
        $('#modalCon_Fecha_Inicio').val($t.data('ini') || '');
        $('#modalCon_Fecha_Fin').val($t.data('fin') || '');
        $('#modalCon_Tipo').val($t.data('tipo') || 'MENSUAL');
        $('#modalCon_Valor').val($t.data('val') || '0');
        var meses = ($t.data('meses') || '').toString().split(/[,\s]+/).filter(Boolean);
        $('#modalModificarContrato .modal-mes-anual').prop('checked', false);
        $('#modalModificarContrato .modal-mes-anual').each(function () {
            if (meses.indexOf($(this).val()) !== -1) $(this).prop('checked', true);
        });
        if (($t.data('tipo') || '') === 'ANUAL') { $('#modalRowMesesAnual').show(); } else { $('#modalRowMesesAnual').hide(); }
        mostrarModalModificar();
    });
    $(document).on('click', '.btn-cerrar-modal-modificar, #modalModificarContrato .btn-cerrar-modal-modificar', function (e) {
        e.preventDefault();
        ocultarModalModificar();
    });
    $('#modalCon_Tipo').on('change', function () {
        if ($(this).val() === 'ANUAL') { $('#modalRowMesesAnual').show(); } else { $('#modalRowMesesAnual').hide(); $('#modalModificarContrato .modal-mes-anual').prop('checked', false); }
    });
    $('#btnGuardarModalModificar').on('click', function () {
        var con = $('#modalCon_Cod').val();
        var fecIni = $('#modalCon_Fecha_Inicio').val();
        var fecFin = $('#modalCon_Fecha_Fin').val();
        if (!con || !fecIni) { alert('Fecha inicio es obligatoria.'); return; }
        var mesesModal = [];
        $('#modalModificarContrato .modal-mes-anual:checked').each(function () { mesesModal.push($(this).val()); });
        public $btn = $(this);
        $btn.prop('disabled', true);
        $.post(urlBase, {
            guardarContrato: 1, Con_Cod: con, Dcl_Cod: $('#modalDcl_Cod').val(), Con_Numero: $('#modalCon_Numero').val(),
            Con_Fecha_Inicio: fecIni, Con_Fecha_Fin: fecFin, Con_Tipo: $('#modalCon_Tipo').val(), Con_Meses_Anual: mesesModal.sort().join(','), Con_Valor: $('#modalCon_Valor').val()
        }, function (r) {
            if (r && r.success) { alert('Guardado correctamente.'); ocultarModalModificar(); location.reload(); }
            else alert(r && r.message ? r.message : 'Error al guardar.');
        }, 'json').fail(function (xhr, status, err) {
            alert('Error de conexión. Verifique la consola del navegador.');
            console.error('Guardar contrato:', status, err, xhr.responseText);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    function cargarSiguienteNumero() {
        $.get(urlBase, { siguienteNumeroContrato: 1 }, function (r) {
            $('#Con_Numero').val(r.numero || '');
        }, 'json');
    }

    $('#btnNuevoContrato').on('click', function () {
        $('#formContrato')[0].reset();
        $('#Con_Cod').val('');
        $('#Con_Valor').val('0');
        $('#Con_Meses_Anual').val('');
        $('#rowMesesContratoAnual input[name="meses_contrato_anual[]"]').prop('checked', false);
        $('#Dcl_Cod').html(opcionesClienteOriginales);
        cargarSiguienteNumero();
        toggleRowMesesContrato();
    });

    if ($('#Con_Cod').val() === '') cargarSiguienteNumero();
    toggleRowMesesContrato();
    $('#modalVerServicios').appendTo('body');
    $('#modalModificarContrato').appendTo('body');
    $(document).on('click', '#modalVerServicios .close, #modalVerServicios [data-dismiss="modal"]', function (e) {
        e.preventDefault();
        ocultarModalServicios();
    });

    $(document).on('click', '.btn-eliminar-contrato', function () {
        var con = $(this).data('con');
        if (!confirm('¿Eliminar este contrato? Se eliminarán también sus servicios y actividades.')) return;
        $.post(urlBase, { eliminarContrato: 1, Con_Cod: con }, function (r) {
            if (r.success) location.reload();
            else alert(r.message || 'Error al eliminar.');
        }, 'json');
    });

    $(document).on('click', '.btn-quitar-servicio', function () {
        public $btn = $(this), serCod = parseInt($btn.data('ser'), 10), con = $('#modalConCod').val();
        if (!serCod || !con) return;
        if (!confirm('¿Quitar este servicio? Las actividades asociadas se desmarcarán. Los cambios se guardarán al pulsar Guardar.')) return;
        estadoConfig.serviciosEliminados[serCod] = true;
        $.each(estadoConfig.seleccionados, function (actCod, info) {
            if (info && info.Ser_Cod === serCod) delete estadoConfig.seleccionados[actCod];
        });
        actualizarVistaServicios(con);
    });

    $('#btnGuardarServiciosActividades').on('click', function () {
        var con = $('#modalConCod').val();
        if (!con) { alert('Seleccione un contrato.'); return; }
        public $btn = $(this);
        $btn.prop('disabled', true);
        var actividadesActuales = estadoConfig.actividades || [];
        var mapActual = {};
        $.each(actividadesActuales, function (i, r) {
            mapActual[r.Act_Cod] = { ConAct_Cod: r.ConAct_Cod, Ser_Cod: r.Ser_Cod };
        });
        var aAgregar = [], aQuitar = [];
        $.each(estadoConfig.seleccionados, function (actCod, info) {
            if (!mapActual[actCod]) aAgregar.push({ Act_Cod: actCod, Ser_Cod: (info && info.Ser_Cod) || '' });
        });
        $.each(mapActual, function (actCod, info) {
            if (!estadoConfig.serviciosEliminados[info.Ser_Cod] && !estadoConfig.seleccionados[actCod]) {
                aQuitar.push({ ConAct_Cod: info.ConAct_Cod, Ser_Cod: info.Ser_Cod });
            }
        });
        var serviciosAEliminar = [];
        $.each(estadoConfig.servicios || [], function (i, s) {
            if (estadoConfig.serviciosEliminados[s.Ser_Cod] && s.ConSer_Cod) serviciosAEliminar.push(s.ConSer_Cod);
        });
        var pendientes = aAgregar.length + aQuitar.length + serviciosAEliminar.length;
        if (pendientes === 0) { alert('No hay cambios que guardar.'); $btn.prop('disabled', false); return; }
        var err = 0;
        function siguiente() {
            if (aQuitar.length > 0) {
                var q = aQuitar.pop();
                $.post(urlBase, { quitarActividadContrato: 1, ConAct_Cod: q.ConAct_Cod, Con_Cod: con, Ser_Cod: q.Ser_Cod || '' }, function (r) {
                    if (!r.success) err++;
                    siguiente();
                }, 'json').fail(function () { err++; siguiente(); });
            } else if (serviciosAEliminar.length > 0) {
                var cs = serviciosAEliminar.pop();
                $.post(urlBase, { quitarServicioContrato: 1, ConSer_Cod: cs }, function (r) {
                    if (!r.success) err++;
                    siguiente();
                }, 'json').fail(function () { err++; siguiente(); });
            } else if (aAgregar.length > 0) {
                var ag = aAgregar.pop();
                $.post(urlBase, { agregarActividadContrato: 1, Con_Cod: con, Act_Cod: ag.Act_Cod, Ser_Cod: ag.Ser_Cod || '' }, function (r) {
                    if (!r.success) err++;
                    siguiente();
                }, 'json').fail(function () { err++; siguiente(); });
            } else {
                if (err > 0) alert('Algunos cambios no se pudieron guardar.');
                else alert('Cambios guardados correctamente.');
                cargarConfigurarServicios(con);
                $btn.prop('disabled', false);
            }
        }
        siguiente();
    });

    // -------- Propuesta de Servicios Adicionales --------
    function esc(v) { return (v == null || v === undefined) ? '' : String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
    $('#btnGenerarPropuesta').on('click', function () {
        var con = $('#selContratoPropuesta').val();
        if (!con) { alert('Seleccione un contrato.'); return; }
        public $btn = $(this);
        $btn.prop('disabled', true);
        $.get(urlBase, { datosPropuestaServiciosAdicionales: 1, Con_Cod: con }, function (r) {
            $btn.prop('disabled', false);
            var cliente = (r && r.cliente) ? r.cliente : {};
            var actsContrato = (r && r.actividadesContrato) ? r.actividadesContrato : [];
            var actsPrecios = (r && r.actividadesPrecios) ? r.actividadesPrecios : [];
            var actIdsEnContrato = {};
            $.each(actsContrato, function (i, a) { actIdsEnContrato[parseInt(a.Act_Cod, 10)] = true; });
            var porServicio = {};
            $.each(actsContrato, function (i, a) {
                var serNom = (a.Ser_Nombre || 'Otros').toString();
                if (!porServicio[serNom]) porServicio[serNom] = { incluidas: [], noIncluidas: [] };
                porServicio[serNom].incluidas.push(a.Act_Nombre || '');
            });
            $.each(actsPrecios, function (i, a) {
                var serNom = (a.Ser_Nombre || 'Otros').toString();
                if (!porServicio[serNom]) porServicio[serNom] = { incluidas: [], noIncluidas: [] };
                var enContrato = actIdsEnContrato[parseInt(a.Act_Cod, 10)];
                if (enContrato) {
                    if (porServicio[serNom].incluidas.indexOf(a.Act_Nombre || '') === -1) porServicio[serNom].incluidas.push(a.Act_Nombre || '');
                } else {
                    porServicio[serNom].noIncluidas.push(a.Act_Nombre || '');
                }
            });
            var hoy = new Date();
            var fechaStr = ('0' + hoy.getDate()).slice(-2) + '/' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '/' + hoy.getFullYear();
            var html = '';
            html += '<div class="propuesta-titulo-principal">' + esc(nombreDespacho) + '</div>';
            html += '<div class="propuesta-titulo-secundario">PROPUESTA DE SERVICIOS ADICIONALES</div>';
            html += '<div class="propuesta-datos-cliente">Cliente: ' + esc(cliente.Cliente_Nombre) + '<br/>RUC: ' + esc(cliente.RUC) + '<br/>Fecha: ' + fechaStr + '</div>';
            html += '<div class="propuesta-seccion"><h3>1. SERVICIOS CONTRATADOS VS SERVICIOS ADICIONALES</h3>';
            html += '<p>A continuación se detalla la comparación entre los servicios incluidos en su contrato actual y los servicios adicionales disponibles:</p>';
            html += '<table class="propuesta-tabla"><thead><tr><th>CATEGORÍA</th><th>INCLUIDAS EN CONTRATO</th><th>NO INCLUIDAS</th></tr></thead><tbody>';
            $.each(Object.keys(porServicio).sort(), function (i, serNom) {
                var inv = porServicio[serNom];
                var inclArr = (inv.incluidas || []).filter(function (n) { return (n || '').toString().trim() !== ''; });
                var noInclArr = (inv.noIncluidas || []).filter(function (n) { return (n || '').toString().trim() !== ''; });
                var incl = inclArr.length ? inclArr.map(function (n) { return '✓ ' + esc(n); }).join('<br/>') : '—';
                var noIncl = noInclArr.length ? noInclArr.map(function (n) { return esc(n); }).join('<br/>') : '—';
                html += '<tr><td><strong>' + esc(serNom) + '</strong></td><td>' + incl + '</td><td>' + noIncl + '</td></tr>';
            });
            html += '</tbody></table></div>';
            html += '<div class="propuesta-seccion"><h3>2. CATÁLOGO DE SERVICIOS ADICIONALES</h3>';
            html += '<p>Los siguientes servicios pueden ser contratados de forma adicional según las necesidades de su empresa. Los precios están calculados según el régimen de su empresa.</p>';
            html += '<table class="propuesta-tabla"><thead><tr><th>SERVICIO ADICIONAL</th><th>PRECIO USD</th><th>SELECCIONAR</th></tr></thead><tbody>';
            var tipoEmp = (cliente && cliente.Tipo_Empresa) ? String(cliente.Tipo_Empresa).toUpperCase() : 'MEDIANO';
            if (tipoEmp !== 'PEQUENO' && tipoEmp !== 'MEDIANO' && tipoEmp !== 'GRANDE') tipoEmp = 'MEDIANO';
            var precioKey = (tipoEmp === 'PEQUENO' ? 'Precio_Pequeno' : (tipoEmp === 'GRANDE' ? 'Precio_Grande' : 'Precio_Mediano'));
            var ultimoSer = '';
            $.each(actsPrecios, function (i, a) {
                if (actIdsEnContrato[parseInt(a.Act_Cod, 10)]) return;
                var serNom = (a.Ser_Nombre || 'Otros').toString();
                if (serNom !== ultimoSer) {
                    html += '<tr class="subcab"><td colspan="3">' + esc(serNom).toUpperCase() + ' ADICIONALES</td></tr>';
                    ultimoSer = serNom;
                }
                var precio = parseFloat(a[precioKey]) || 0;
                html += '<tr data-act="' + a.Act_Cod + '" data-actnombre="' + esc(a.Act_Nombre) + '" data-sernombre="' + esc(serNom) + '" data-precio="' + precio + '"><td>' + esc(a.Act_Nombre) + '</td><td>$' + precio.toFixed(2) + '</td><td><input type="checkbox" class="chk-servicio-adicional" data-act="' + a.Act_Cod + '" data-precio="' + precio + '" /></td></tr>';
            });
            html += '</tbody></table></div>';
            var anioActual = new Date().getFullYear();
            html += '<div class="propuesta-seccion"><h3>CONDICIONES GENERALES</h3><ul>';
            html += '<li>Los precios mostrados son referenciales y están sujetos a la complejidad específica de cada caso.</li>';
            html += '<li>Los servicios adicionales se facturarán únicamente cuando sean solicitados y previamente aprobados por el cliente.</li>';
            html += '<li>Este tarifario tiene vigencia durante el año ' + anioActual + ' y podrá ser actualizado previa notificación.</li>';
            html += '<li>Los precios no incluyen IVA.</li>';
            html += '<li>Para solicitar cualquiera de estos servicios, puede comunicarse con nosotros a través de los canales habituales.</li></ul></div>';
            html += '<div class="propuesta-seccion"><h3>CONFIRMACIÓN DE RECEPCIÓN</h3>';
            html += '<p>Por favor, firme este documento como constancia de que ha recibido y conoce el listado de servicios adicionales y sus tarifas vigentes:</p>';
            var repNom = (r.representanteLegal && r.representanteLegal.Representante_Nombre) ? r.representanteLegal.Representante_Nombre : '';
            var repDoc = (r.representanteLegal && r.representanteLegal.Representante_Identificacion) ? r.representanteLegal.Representante_Identificacion : '';
            var textoRepNom = repNom !== '' ? repNom : '[NOMBRE DEL REPRESENTANTE]';
            var textoRepDoc = repDoc !== '' ? ('C.I./RUC: ' + repDoc) : '';
            var nombreCliente = (cliente && cliente.Cliente_Nombre) ? esc(cliente.Cliente_Nombre) : '';
            var rucCliente = (cliente && cliente.RUC) ? esc(cliente.RUC) : '';
            html += '<div class="propuesta-firmas">';
            html += '<div class="propuesta-firma"><div class="linea">_______________________________</div><div class="linea">Firma del Cliente</div>' + (nombreCliente ? '<div class="linea">' + nombreCliente + '</div>' : '') + (rucCliente ? '<div class="linea">RUC: ' + rucCliente + '</div>' : '') + '</div>';
            html += '<div class="propuesta-firma"><div class="linea">_______________________________</div><div class="linea">Firma del Despacho</div><div class="linea">' + esc(textoRepNom) + '</div>' + (textoRepDoc ? '<div class="linea">' + esc(textoRepDoc) + '</div>' : '') + '</div>';
            html += '</div></div>';
            $('#contenidoPropuesta').html(html).show();
            $('#btnGenerarPdfPropuesta, #btnDescargarWordPropuesta').show();
        }, 'json').fail(function () {
            $btn.prop('disabled', false);
            alert('Error al cargar los datos.');
        });
    });
    $('#btnGenerarPdfPropuesta').on('click', function () {
        var con = $('#selContratoPropuesta').val();
        if (!con) { alert('Seleccione un contrato.'); return; }
        var filas = $('#contenidoPropuesta .chk-servicio-adicional:checked').closest('tr');
        var seleccionados = [];
        filas.each(function () {
            public $tr = $(this);
            seleccionados.push({
                Act_Cod: $tr.data('act'),
                Act_Nombre: $tr.data('actnombre') || $tr.find('td:first').text(),
                Ser_Nombre: $tr.data('sernombre') || '',
                Precio: parseFloat($tr.data('precio')) || 0
            });
        });
        var form = $('<form method="post" action="aud_export_propuesta_servicios_adicionales_pdf.php" target="_blank" accept-charset="UTF-8"></form>');
        form.append($('<input type="hidden" name="Con_Cod" />').val(con));
        form.append($('<input type="hidden" name="servicios_seleccionados" />').val(JSON.stringify(seleccionados)));
        $('body').append(form);
        form.submit();
        form.remove();
    });
    $('#btnDescargarWordPropuesta').on('click', function () {
        var con = $('#selContratoPropuesta').val();
        if (!con) { alert('Seleccione un contrato.'); return; }
        var filas = $('#contenidoPropuesta .chk-servicio-adicional:checked').closest('tr');
        var seleccionados = [];
        filas.each(function () {
            public $tr = $(this);
            seleccionados.push({
                Act_Cod: $tr.data('act'),
                Act_Nombre: $tr.data('actnombre') || $tr.find('td:first').text(),
                Ser_Nombre: $tr.data('sernombre') || '',
                Precio: parseFloat($tr.data('precio')) || 0
            });
        });
        var form = $('<form method="post" action="aud_export_propuesta_servicios_adicionales_word.php" target="_blank" accept-charset="UTF-8"></form>');
        form.append($('<input type="hidden" name="Con_Cod" />').val(con));
        form.append($('<input type="hidden" name="servicios_seleccionados" />').val(JSON.stringify(seleccionados)));
        $('body').append(form);
        form.submit();
        form.remove();
    });
});
</script>
</body>
</html>
