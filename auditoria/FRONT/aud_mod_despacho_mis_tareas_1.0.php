<?php
/**
 * Gestión Operativa del Despacho - Mis Tareas
 * Vista y funcionalidad como Formulario de avances: subida de archivos, diseño moderno
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
$Emp_Cod_Usar = isset($_REQUEST['Emp_Cod']) ? intval($_REQUEST['Emp_Cod']) : $Ses_Emp_Cod;
if ($Emp_Cod_Usar <= 0) $Emp_Cod_Usar = $Ses_Emp_Cod;

$rowPer = $obBD_con1->getRowConsulta(42, array('Usu_Cod' => $Ses_Usu_Cod, 'Emp_Cod' => $Emp_Cod_Usar), $obBD_conexion);
$Per_Cod = isset($rowPer['Per_Cod']) ? intval($rowPer['Per_Cod']) : 0;
if ($Per_Cod <= 0) {
    $rowPer = $obBD_con1->getRowConsulta(81, array('Usu_Cod' => $Ses_Usu_Cod, 'Emp_Cod' => $Emp_Cod_Usar), $obBD_conexion);
    $Per_Cod = isset($rowPer['Per_Cod']) ? intval($rowPer['Per_Cod']) : 0;
}

// Ajax: Listar adjuntos de tarea
if (!empty($_REQUEST['listarAdjuntosTarea'])) {
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    $arr = array();
    if ($tarCod > 0) {
        $arr = $obBD_con1->getArrayConsulta(40, array('Tar_Cod' => $tarCod), $obBD_conexion);
        if (!is_array($arr)) $arr = array();
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// Ajax: Mis tareas
if (!empty($_REQUEST['misTareasDespacho'])) {
    $arr = $Per_Cod > 0 ? $obBD_con1->getArrayConsulta(37, array('Per_Cod' => $Per_Cod, 'Emp_Cod' => $Emp_Cod_Usar), $obBD_conexion) : array();
    if (!is_array($arr)) $arr = array();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr, 'sin_vinculo' => ($Per_Cod <= 0)));
    exit;
}

// Ajax: Actualizar porcentaje (con adjuntos opcionales)
if (!empty($_REQUEST['actualizarPorcentaje'])) {
    $resp = array('success' => false);
    $tar = intval(isset($_POST['Tar_Cod']) ? $_POST['Tar_Cod'] : 0);
    $porc = min(100, max(0, intval(isset($_POST['TarUsu_Porcentaje']) ? $_POST['TarUsu_Porcentaje'] : 0)));
    $obsUsu = isset($_POST['TarUsu_Observacion']) ? trim($_POST['TarUsu_Observacion']) : (isset($_POST['Tar_Observaciones']) ? trim($_POST['Tar_Observaciones']) : '');
    if ($tar <= 0 || $Per_Cod <= 0) { $resp['message'] = 'Datos inválidos.'; header('Content-Type: application/json; charset=UTF-8'); echo json_encode($resp); exit; }
    $rowActual = $obBD_con1->getRowConsulta(76, array('Tar_Cod' => $tar, 'Per_Cod' => $Per_Cod), $obBD_conexion);
    $pctActual = isset($rowActual['TarUsu_Porcentaje']) ? intval($rowActual['TarUsu_Porcentaje']) : 0;
    if ($porc < $pctActual) {
        $resp['message'] = 'No puede disminuir el porcentaje de avance. El avance actual es ' . $pctActual . '%. Debe ingresar un valor igual o mayor.';
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(38, array('Tar_Cod' => $tar, 'Per_Cod' => $Per_Cod, 'TarUsu_Porcentaje' => $porc, 'TarUsu_Observacion' => $obsUsu), $obBD_conexion);
    if ($obBD_con1->Error != 0 && $obsUsu !== '') {
        $obBD_con1->setError(0, '');
        $obBD_con1->operacionobBD(80, array('Tar_Cod' => $tar, 'Per_Cod' => $Per_Cod, 'TarUsu_Porcentaje' => $porc), $obBD_conexion);
    }
    $resp['success'] = ($obBD_con1->Error == 0);
    if ($resp['success'] && !empty($_FILES['adjuntos'])) {
        $dirAdj = __DIR__ . '/../adjuntos/despacho';
        if (!is_dir($dirAdj)) @mkdir($dirAdj, 0755, true);
        if (is_dir($dirAdj) && is_writable($dirAdj)) {
            $allowExt = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $maxSize = 5 * 1024 * 1024;
            $files = $_FILES['adjuntos'];
            $names = isset($files['name']) ? $files['name'] : array();
            $tmpNames = isset($files['tmp_name']) ? $files['tmp_name'] : array();
            $errors = isset($files['error']) ? $files['error'] : array();
            if (!is_array($names)) { $names = array($names); $tmpNames = array($tmpNames); $errors = array($errors); }
            $conn = $obBD_conexion->conexion;
            for ($i = 0; $i < count($names); $i++) {
                if (empty($names[$i]) || !isset($tmpNames[$i]) || !is_uploaded_file($tmpNames[$i])) continue;
                if (isset($errors[$i]) && $errors[$i] !== UPLOAD_ERR_OK) continue;
                $size = is_array($files['size']) ? (isset($files['size'][$i]) ? $files['size'][$i] : 0) : $files['size'];
                if ($size > $maxSize) continue;
                $ext = strtolower(pathinfo($names[$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowExt)) $ext = 'jpg';
                $safeName = $tar . '_' . time() . '_' . $i . '.' . $ext;
                $destino = $dirAdj . '/' . $safeName;
                if (move_uploaded_file($tmpNames[$i], $destino)) {
                    $nomSafe = mysqli_real_escape_string($conn, basename($names[$i]));
                    $obBD_con1->operacionobBD(39, array('Tar_Cod' => $tar, 'Adj_Tipo' => 'IMG', 'Adj_Nombre' => $nomSafe, 'Adj_Ruta' => $safeName, 'Usu_Cod' => $Ses_Usu_Cod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
                }
            }
        }
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($resp);
    exit;
}

// Ajax: Listar clientes con contratos vigentes en el período (para Nueva tarea eventual)
if (!empty($_REQUEST['listarClientesContratosPeriodo'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $arr = array();
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    if ($per !== '' && !empty($Emp_Cod_Usar)) {
        $arr = $obBD_con1->getArrayConsulta(83, array('Emp_Cod' => $Emp_Cod_Usar, 'Tar_Periodo' => $per), $obBD_conexion);
        if (!is_array($arr)) $arr = array();
    }
    echo json_encode(array('rows' => $arr));
    exit;
}

// Ajax: Listar actividades EVENTUALES de un cliente en un período (para Nueva tarea eventual)
if (!empty($_REQUEST['listarActividadesEventuales'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $rowsOut = array();
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $cliCod = isset($_REQUEST['Cli_Cod']) ? intval($_REQUEST['Cli_Cod']) : 0;
    if ($per !== '' && $cliCod > 0 && !empty($Emp_Cod_Usar)) {
        $parContr = array('Emp_Cod' => $Emp_Cod_Usar, 'Tar_Periodo' => $per);
        $contratos = $obBD_con1->getArrayConsulta(30, $parContr, $obBD_conexion);
        if (is_array($contratos)) {
            $vistas = array();
            foreach ($contratos as $con) {
                if (!isset($con['Cli_Cod']) || intval($con['Cli_Cod']) !== $cliCod) continue;
                $conCod = isset($con['Con_Cod']) ? intval($con['Con_Cod']) : 0;
                if ($conCod <= 0) continue;
                $actividades = $obBD_con1->getArrayConsulta(43, array('Con_Cod' => $conCod), $obBD_conexion);
                $actPorDefecto = $obBD_con1->getArrayConsulta(44, array('Con_Cod' => $conCod), $obBD_conexion);
                $todas = array_merge($actividades ?: array(), $actPorDefecto ?: array());
                foreach ($todas as $a) {
                    $tipo = isset($a['Act_Tipo']) ? $a['Act_Tipo'] : 'MENSUAL';
                    if (strtoupper($tipo) !== 'EVENTUAL') continue;
                    $actCod = isset($a['Act_Cod']) ? intval($a['Act_Cod']) : 0;
                    if ($actCod <= 0 || isset($vistas[$actCod])) continue;
                    $vistas[$actCod] = true;
                    $rowsOut[] = array(
                        'Act_Cod' => $actCod,
                        'Act_Nombre' => isset($a['Act_Nombre']) ? $a['Act_Nombre'] : '',
                        'Ser_Nombre' => isset($a['Ser_Nombre']) ? $a['Ser_Nombre'] : '',
                        'Con_Cod' => $conCod
                    );
                }
            }
        }
    }
    echo json_encode(array('rows' => $rowsOut));
    exit;
}

// Ajax: Crear tarea EVENTUAL y autoasignarla al usuario actual
if (!empty($_REQUEST['generarTareaEventual'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $resp = array('success' => false, 'message' => '', 'Tar_Cod' => 0);
    if ($Per_Cod <= 0) {
        $resp['message'] = 'Su usuario no está vinculado a un empleado. No puede crear tareas.';
        echo json_encode($resp);
        exit;
    }
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $cliCod = isset($_REQUEST['Cli_Cod']) ? intval($_REQUEST['Cli_Cod']) : 0;
    $actCod = isset($_REQUEST['Act_Cod']) ? intval($_REQUEST['Act_Cod']) : 0;
    $fecLim = isset($_REQUEST['Tar_Fecha_Limite']) ? trim($_REQUEST['Tar_Fecha_Limite']) : '';
    if ($per === '' || $cliCod <= 0 || $actCod <= 0) {
        $resp['message'] = 'Período, cliente y actividad son obligatorios.';
        echo json_encode($resp);
        exit;
    }
    $existe = $obBD_con1->getRowConsulta(32, array('Cli_Cod' => $cliCod, 'Act_Cod' => $actCod, 'Tar_Periodo' => $per), $obBD_conexion);
    if (!empty($existe) && isset($existe['Tar_Cod'])) {
        $resp['message'] = 'Ya existe una tarea para este cliente, actividad y período.';
        $resp['Tar_Cod'] = intval($existe['Tar_Cod']);
        echo json_encode($resp);
        exit;
    }
    $conCodSel = 0;
    if (!empty($Emp_Cod_Usar)) {
        $parContr = array('Emp_Cod' => $Emp_Cod_Usar, 'Tar_Periodo' => $per);
        $contratos = $obBD_con1->getArrayConsulta(30, $parContr, $obBD_conexion);
        if (is_array($contratos)) {
            foreach ($contratos as $con) {
                if (isset($con['Cli_Cod']) && intval($con['Cli_Cod']) === $cliCod) {
                    $conCodSel = isset($con['Con_Cod']) ? intval($con['Con_Cod']) : 0;
                    if ($conCodSel > 0) break;
                }
            }
        }
    }
    if ($conCodSel <= 0) {
        $resp['message'] = 'No se encontró un contrato vigente para este cliente en el período indicado.';
        echo json_encode($resp);
        exit;
    }
    if ($fecLim === '' && strlen($per) >= 7) {
        $parts = explode('-', $per);
        $y = isset($parts[0]) ? intval($parts[0]) : intval(date('Y'));
        $m = isset($parts[1]) ? intval($parts[1]) : intval(date('m'));
        $fec_ini_m = sprintf('%04d-%02d-01', $y, $m);
        $fecLim = date('Y-m-t', strtotime($fec_ini_m));
    }
    $obs = 'Tarea EVENTUAL creada por el usuario desde Mis tareas.';
    $obBD_con1->setError(0, '');
    $ok = $obBD_con1->operacionobBD(31, array(
        'Cli_Cod' => $cliCod,
        'Act_Cod' => $actCod,
        'Tar_Periodo' => $per,
        'Tar_Fecha_Limite' => $fecLim,
        'Con_Cod' => $conCodSel,
        'Emp_Cod' => $Emp_Cod_Usar,
        'Tar_Observaciones' => $obs
    ), $obBD_conexion);
    if ($ok && $obBD_con1->Error == 0) {
        $tarCod = $obBD_con1->insercionid($obBD_conexion);
        if ($tarCod > 0) {
            $obBD_con1->setError(0, '');
            $obBD_con1->operacionobBD(33, array('Tar_Cod' => $tarCod, 'Per_Cod' => $Per_Cod), $obBD_conexion);
        }
        $resp['success'] = true;
        $resp['Tar_Cod'] = $tarCod ? intval($tarCod) : 0;
        $resp['message'] = 'Tarea eventual creada y asignada a su perfil correctamente.';
    } else {
        $resp['message'] = $obBD_con1->getMsgError() ?: 'Error al crear la tarea.';
    }
    echo json_encode($resp);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'EXA'; ?> - Mis Tareas Despacho</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <?php require_once('../../mascaras/model1/estilos/estilos.php'); ?>
    <link href="aud_zoom.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script type="text/javascript" src="../../Librerias/jquery.min/jquery-1.11.3.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <style>
        html, body { height: 100%; margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        *, *::before, *::after { box-sizing: inherit; }
        body { display: flex; flex-direction: column; background: #f1f5f9; }
        .form-group { margin-bottom: 14px; }
        .despacho-mis-container {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 100vh;
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 20px 24px 24px;
            overflow: hidden;
        }
        .config-card {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 10px 20px -5px rgba(0,0,0,0.06);
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.04);
        }
        .avances-scroll-area { flex: 1; min-height: 0; overflow: auto; }
        .config-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 50%, #5A9BD4 100%);
            color: white;
            padding: 14px 20px;
            border-radius: 12px;
            margin: -24px -24px 24px -24px;
            box-shadow: 0 2px 8px rgba(15,118,110,0.2);
        }
        .config-header h4 { margin: 0; font-size: 15px; font-weight: 600; letter-spacing: 0.3px; }
        .exa-header {
            flex-shrink: 0;
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 50%, #5A9BD4 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(15,118,110,0.25);
            margin-bottom: 20px;
        }
        .exa-header h3 { margin: 0; font-size: 18px; font-weight: 600; letter-spacing: 0.2px; }
        .despacho-mis-container .form-control {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .despacho-mis-container .form-control:focus {
            border-color: #2C5D94;
            box-shadow: 0 0 0 3px rgba(44,93,148,0.15);
            outline: none;
        }
        .despacho-mis-container .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 18px;
            transition: all 0.2s ease;
        }
        .despacho-mis-container .btn-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            border: none;
            box-shadow: 0 2px 8px rgba(14,165,233,0.3);
        }
        .despacho-mis-container .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(14,165,233,0.4);
        }
        .despacho-mis-container .btn-default {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
        }
        .despacho-mis-container .btn-default:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .avances-filtros-bar {
            padding: 16px !important;
            background: #f8fafc !important;
            border-radius: 12px !important;
            border: 1px solid #e2e8f0 !important;
        }
        .aud-tabla {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .aud-tabla th, .aud-tabla td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .aud-tabla th {
            background: linear-gradient(180deg, #72A1CF 0%, #8EB7DD 100%);
            color: white;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .aud-tabla tbody tr { transition: background 0.15s; }
        .aud-tabla tbody tr:hover { background: #f8fafc; }
        .aud-tabla tbody tr:last-child td { border-bottom: none; }
        .aud-tabla .celda-descripcion { min-width: 200px; font-size: 13px; color: #475569; white-space: normal; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.5; }
        .aud-tabla td { vertical-align: top; font-size: 13px; }
        .aud-tabla th:nth-child(2), .aud-tabla td:nth-child(2) { min-width: 200px; }
        .mini-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .mini-stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .mini-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        .mini-stat-card .stat-label { font-size: 11px; color: #64748b; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .mini-stat-card .stat-value { font-size: 26px; font-weight: 700; }
        .mini-stat-card.total .stat-value { color: #0ea5e9; }
        .mini-stat-card.completadas .stat-value { color: #10b981; }
        .mini-stat-card.proceso .stat-value { color: #64748b; }
        .mini-stat-card.atrasadas .stat-value { color: #ef4444; }
        .mini-stat-card.avance .stat-value { color: #2C5D94; }
        .barra-progreso-fondo {
            position: relative;
            min-width: 90px;
            max-width: 120px;
            margin: 0 auto;
            height: 24px;
            background: #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .barra-progreso-relleno {
            height: 100%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 11px;
        }
        .barra-progreso-relleno.bajo { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .barra-progreso-relleno.medio { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; }
        .barra-progreso-relleno.alto { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
        .avances-zona-pegar {
            margin-top: 12px;
            padding: 24px;
            border: 2px dashed #5A9BD4;
            border-radius: 12px;
            background: linear-gradient(180deg, #DEE7EF 0%, #D1E6F4 100%);
            text-align: center;
            cursor: pointer;
            outline: none;
            transition: all 0.2s ease;
        }
        .avances-zona-pegar:focus { border-color: #2C5D94; background: #D1E6F4; box-shadow: 0 0 0 3px rgba(44,93,148,0.2); }
        .avances-zona-pegar:hover { background: #DEE7EF; border-color: #2C5D94; }
        .avances-zona-pegar.avances-zona-pegar-drag { border-color: #2C5D94; background: #8EB7DD; }
        /* Modal flotante Editar avance */
        .modal-avance-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.5);
            z-index: 1050;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal-avance-backdrop.modal-avance-abierto {
            display: flex;
        }
        .modal-avance-contenido {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            max-width: 560px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            z-index: 1051;
        }
        .modal-avance-header {
            padding: 18px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            background: #fff;
        }
        .modal-avance-header h5 { margin: 0; color: #2C5D94; font-weight: 600; font-size: 16px; }
        .modal-avance-cerrar {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 24px;
            line-height: 1;
            padding: 0 4px;
            cursor: pointer;
            border-radius: 6px;
            transition: color 0.2s, background 0.2s;
        }
        .modal-avance-cerrar:hover { color: #2C5D94; background: rgba(44,93,148,0.15); }
        .modal-avance-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }
        #imagenesTareaContexto {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
        }
        .aud-tabla th.col-estado, .aud-tabla td.col-estado { text-align: center; }
        .estado-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .estado-badge.estado-finalizada { color: #15803d; background: #dcfce7; }
        .estado-badge.estado-pendiente { color: #c2410c; background: #ffedd5; }
        .estado-badge.estado-en-proceso { color: #2C5D94; background: #D1E6F4; }
        .estado-badge.estado-asignado { color: #475569; background: #f1f5f9; }
        .aud-tabla .btn-xs {
            border-radius: 8px;
            padding: 6px 12px;
            font-weight: 600;
            font-size: 12px;
        }
        .aud-tabla .btn-editar-avance { background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; }
        .aud-tabla .btn-editar-avance:hover { background: #f1f5f9; }
    </style>
</head>
<body>
<div class="despacho-mis-container">
    <div class="exa-header">
        <h3>&raquo; Formulario de avances - Mis tareas asignadas</h3>
    </div>

    <div class="config-card">
        <div class="config-header"><h4><i class="glyphicon glyphicon-tasks"></i> Mis tareas asignadas</h4></div>
        <div class="avances-scroll-area">
        <p class="text-muted small">Aquí solo se muestran las tareas del despacho que tiene asignadas. Registre o edite el avance (porcentaje) de cada una. Puede adjuntar imágenes como evidencia.</p>
        <div id="miniDashboard" class="mini-stats" style="display:none;">
            <div class="mini-stat-card total"><div class="stat-label">Total tareas</div><div class="stat-value" id="stat-total">0</div></div>
            <div class="mini-stat-card completadas"><div class="stat-label">Completadas</div><div class="stat-value" id="stat-completadas">0</div></div>
            <div class="mini-stat-card proceso"><div class="stat-label">En proceso</div><div class="stat-value" id="stat-proceso">0</div></div>
            <div class="mini-stat-card atrasadas"><div class="stat-label">Atrasadas</div><div class="stat-value" id="stat-atrasadas">0</div></div>
            <div class="mini-stat-card avance"><div class="stat-label">Avance promedio</div><div class="stat-value" id="stat-avance">0%</div></div>
        </div>
        <div class="avances-filtros avances-filtros-bar" style="margin-bottom:20px; display:flex; flex-wrap:wrap; align-items:center; gap:16px;">
            <label class="control-label" style="margin:0; font-weight:600; color:#334155;">Estado:</label>
            <select id="avancesEstado" class="form-control input-sm" style="width:160px;">
                <option value="todos" selected>Todos</option>
                <option value="pendientes">Pendientes</option>
                <option value="finalizados">Finalizados</option>
            </select>
            <label class="control-label" style="margin:0; font-weight:600; color:#334155;">Período:</label>
            <select id="avancesPeriodo" class="form-control input-sm" style="width:160px;">
                <option value="">Todo</option>
                <option value="semana">Esta semana</option>
                <option value="mes" selected>Este mes</option>
                <option value="anio">Este año</option>
            </select>
            <button type="button" class="btn btn-primary btn-sm" id="btnRefrescarMisTareas"><i class="glyphicon glyphicon-refresh"></i> Actualizar mis tareas</button>
            <button type="button" class="btn btn-success btn-sm" id="btnNuevaTarea" style="margin-left:8px;"><i class="glyphicon glyphicon-plus"></i> Nueva tarea</button>
        </div>
        <table id="gridMisTareas" class="aud-tabla">
            <thead><tr><th>Tarea</th><th>Descripción</th><th>Prioridad</th><th class="col-estado">Estado</th><th>Fecha Inicio</th><th>Fecha fin Tentativa</th><th>Fecha de culminación</th><th>Avance %</th><th>Comentario Admin</th><th>Acción</th></tr></thead>
            <tbody id="bodyMisTareas"><tr><td colspan="10" class="text-muted">Cargando…</td></tr></tbody>
        </table>
        </div>
    </div>
</div>

<!-- Modal flotante Editar avance -->
<div id="modalAvanceBackdrop" class="modal-avance-backdrop">
    <div class="modal-avance-contenido">
        <div class="modal-avance-header">
            <h5>Editar avance</h5>
            <button type="button" class="modal-avance-cerrar" id="btnCerrarModalAvance" title="Cerrar">&times;</button>
        </div>
        <div class="modal-avance-body">
            <div id="imagenesTareaContexto" style="display:none; margin-bottom:20px;">
                <strong class="text-muted small">Imágenes adjuntas:</strong>
                <div id="imagenesTareaContextoLista" style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px;"></div>
            </div>
            <form id="formAvance" class="form-horizontal" accept-charset="UTF-8">
                <input type="hidden" name="Tar_Cod" id="Tar_Cod_Avance" value="" />
                <input type="hidden" id="Ava_Porcentaje_Actual" value="0" />
                <div id="comentarioSupervisorBox" class="form-group" style="display:none;">
                    <label class="control-label text-info"><span class="glyphicon glyphicon-comment"></span> Comentario del supervisor</label>
                    <div id="comentarioSupervisorTexto" class="form-control input-sm" style="width: 500px; min-height: 50px; background: #f8fafc; border: 1px solid #e2e8f0; white-space: pre-wrap;" readonly></div>
                </div>
                <div class="form-group">
                    <label class="control-label">Mi observación</label>
                    <textarea name="TarUsu_Observacion" id="Ava_Observacion" class="form-control input-sm" rows="3" placeholder="Su comentario opcional sobre el avance (solo usted puede editarlo)..." style="width: 500px; height: 95px;"></textarea>
                </div>
                <div class="form-group">
                    <label class="control-label">Porcentaje (0-100) <span class="text-danger">*</span></label>
                    <input type="number" name="TarUsu_Porcentaje" id="Ava_Porcentaje" class="form-control input-sm" min="0" max="100" value="0" style="width:100px;" />
                </div>
                <div class="form-group">
                    <label class="control-label">Capturas / imágenes</label>
                    <input type="file" name="adjuntos[]" id="adjuntos" class="form-control input-sm" accept="image/jpeg,image/png,image/gif,image/webp" multiple />
                    <p class="text-muted small">Opcional. Suba archivos, <strong>pegue (Ctrl+V)</strong> o <strong>arrastre</strong> imágenes aquí. JPG, PNG, GIF, WebP. Máx. 5 MB por imagen.</p>
                    <div id="zonaPegarImagen" class="avances-zona-pegar" tabindex="0" title="Pegar (Ctrl+V) o arrastrar imágenes aquí">
                        <span class="text-muted">O pegar / arrastrar imagen aquí (Ctrl+V)</span>
                    </div>
                    <div id="adjuntosPreviews" style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px;"></div>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-primary btn-sm" id="btnGuardarAvance"><i class="glyphicon glyphicon-ok"></i> Guardar avance</button>
                    <button type="button" class="btn btn-default btn-sm" id="btnCancelarAvance">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nueva tarea eventual (autoasignada) -->
<div id="modalNuevaTarea" class="modal-avance-backdrop">
    <div class="modal-avance-contenido" style="max-width:520px;">
        <div class="modal-avance-header">
            <h5><i class="glyphicon glyphicon-plus"></i> Nueva tarea eventual</h5>
            <button type="button" class="modal-avance-cerrar" id="btnCerrarModalNuevaTarea" title="Cerrar">&times;</button>
        </div>
        <div class="modal-avance-body">
            <p class="text-muted small">Solo tareas de tipo <strong>eventual</strong>. Se asignará a su perfil automáticamente.</p>
            <div class="form-group">
                <label class="control-label">Período <span class="text-danger">*</span></label>
                <input type="month" id="nuevaTareaPeriodo" class="form-control input-sm" style="width:160px;" />
            </div>
            <div class="form-group">
                <label class="control-label">Cliente <span class="text-danger">*</span></label>
                <select id="nuevaTareaCliente" class="form-control input-sm" style="width:100%;"><option value="">-- Seleccione --</option></select>
            </div>
            <div class="form-group">
                <label class="control-label">Actividad eventual <span class="text-danger">*</span></label>
                <select id="nuevaTareaActividad" class="form-control input-sm" style="width:100%;"><option value="">-- Seleccione cliente y período --</option></select>
            </div>
            <div class="form-group">
                <label class="control-label">Fecha límite</label>
                <input type="date" id="nuevaTareaFechaLimite" class="form-control input-sm" style="width:160px;" />
                <p class="text-muted small">Opcional. Si no indica, se usará el último día del mes.</p>
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-primary btn-sm" id="btnCrearNuevaTarea"><i class="glyphicon glyphicon-ok"></i> Crear y asignarme</button>
                <button type="button" class="btn btn-default btn-sm" id="btnCancelarNuevaTarea">Cancelar</button>
            </div>
            <div id="nuevaTareaResultado" style="margin-top:10px;"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function () {
    var urlBase = <?php echo json_encode($_SERVER['PHP_SELF']); ?>;
    var urlAdjuntos = '../adjuntos/despacho/';
    var rowsAll = [];
    var sinVinculoFlag = false;
    var filesToUpload = [];
    var previewObjectUrls = [];
    var maxSizeImage = 5 * 1024 * 1024;
    var allowTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    var select2Opts = { language: { noResults: function() { return 'No se encontraron resultados'; }, searching: function() { return 'Buscando...'; } }, allowClear: true };
    function initSelect2Buscable($el, extra) {
        if (!$el.length || typeof $el.select2 !== 'function') return;
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        $el.select2(extra ? $.extend(true, {}, select2Opts, extra) : select2Opts);
        $el.off('select2:open.s2nc select2:selecting.s2nc').on('select2:open.s2nc', function () { $el.data('select2OpenAt', Date.now()); }).on('select2:selecting.s2nc', function (ev) {
            var t = $el.data('select2OpenAt'); if (t && (Date.now() - t) < 300) ev.preventDefault();
        });
    }

    function aceptaImagen(file) {
        if (!file || file.size > maxSizeImage) return false;
        var t = (file.type || '').toLowerCase();
        return allowTypes.indexOf(t) !== -1 || t.indexOf('image/') === 0;
    }

    function fmtFechaDMA(f) {
        if (!f || f === '0000-00-00') return '';
        var p = String(f).split(/[-/]/);
        if (p.length >= 3) return p[2] + '/' + p[1] + '/' + p[0];
        return f;
    }

    function getPeriodoFechas() {
        var p = document.getElementById('avancesPeriodo');
        var periodo = (p && p.value) ? p.value : '';
        var hoy = new Date();
        var ini = '', fin = '';
        var pad = function (n) { return (n < 10 ? '0' : '') + n; };
        if (periodo === 'semana') {
            // Semana calendario (lunes a domingo) de la semana actual
            var dIni = new Date(hoy);
            var day = dIni.getDay();
            var diffIni = dIni.getDate() - day + (day === 0 ? -6 : 1); // lunes
            dIni.setDate(diffIni);
            var dFin = new Date(dIni);
            dFin.setDate(dIni.getDate() + 6); // domingo
            ini = dIni.getFullYear() + '-' + pad(dIni.getMonth() + 1) + '-' + pad(dIni.getDate());
            fin = dFin.getFullYear() + '-' + pad(dFin.getMonth() + 1) + '-' + pad(dFin.getDate());
        } else if (periodo === 'mes') {
            // Mes completo actual
            var y = hoy.getFullYear();
            var m = hoy.getMonth() + 1;
            ini = y + '-' + pad(m) + '-01';
            var ultimoDia = new Date(y, m, 0).getDate();
            fin = y + '-' + pad(m) + '-' + pad(ultimoDia);
        } else if (periodo === 'anio') {
            // Año completo actual
            var ya = hoy.getFullYear();
            ini = ya + '-01-01';
            fin = ya + '-12-31';
        }
        return { Fecha_Ini: ini, Fecha_Fin: fin };
    }

    function filtrarPorPeriodo(rows) {
        var pf = getPeriodoFechas();
        if (!pf.Fecha_Ini || !pf.Fecha_Fin) return rows;
        return rows.filter(function (r) {
            var fec = r.Tar_Fecha_Limite || '';
            if (!fec || fec === '0000-00-00') return false;
            return fec >= pf.Fecha_Ini && fec <= pf.Fecha_Fin;
        });
    }

    function filtrarPorEstado(rows) {
        var sel = document.getElementById('avancesEstado');
        var estado = (sel && sel.value) ? sel.value : 'pendientes';
        if (estado === 'todos') return rows;
        return rows.filter(function (r) {
            var e = (r.Tar_Est || '').trim();
            if (estado === 'finalizados') return e === 'FINALIZADA';
            if (estado === 'pendientes') return e !== 'FINALIZADA';
            return true;
        });
    }

    function actualizarMiniDashboard(rows) {
        var total = rows.length;
        var completadas = rows.filter(function (r) { return (r.Tar_Est || '').trim() === 'FINALIZADA'; }).length;
        var hoy = new Date().toISOString().slice(0, 10);
        var atrasadas = rows.filter(function (r) {
            var e = (r.Tar_Est || '').trim();
            if (e === 'FINALIZADA') return false;
            var lim = r.Tar_Fecha_Limite || '';
            return lim && lim < hoy;
        }).length;
        var proceso = total - completadas - atrasadas;
        if (proceso < 0) proceso = 0;
        var sumaPct = 0;
        rows.forEach(function (r) { sumaPct += parseInt(r.TarUsu_Porcentaje || 0, 10); });
        var avanceProm = total > 0 ? Math.round(sumaPct / total) : 0;
        var dash = document.getElementById('miniDashboard');
        if (dash) {
            dash.style.display = total > 0 ? 'grid' : 'none';
            var el;
            if (el = document.getElementById('stat-total')) el.textContent = total;
            if (el = document.getElementById('stat-completadas')) el.textContent = completadas;
            if (el = document.getElementById('stat-proceso')) el.textContent = proceso;
            if (el = document.getElementById('stat-atrasadas')) el.textContent = atrasadas;
            if (el = document.getElementById('stat-avance')) el.textContent = avanceProm + '%';
        }
    }

    function renderAdjuntosPreviews() {
        var cont = document.getElementById('adjuntosPreviews');
        if (!cont) return;
        previewObjectUrls.forEach(function (u) { try { URL.revokeObjectURL(u); } catch (e) {} });
        previewObjectUrls = [];
        if (filesToUpload.length === 0) {
            cont.innerHTML = '';
            cont.style.display = 'none';
            return;
        }
        cont.style.display = 'flex';
        cont.innerHTML = '';
        filesToUpload.forEach(function (file, idx) {
            var url = URL.createObjectURL(file);
            previewObjectUrls.push(url);
            var wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative; display:inline-block;';
            var img = document.createElement('img');
            img.src = url;
            img.alt = file.name || 'Captura';
            img.style.cssText = 'max-height:50px; max-width:80px; object-fit:contain; border:1px solid #ccc; border-radius:4px; display:block;';
            var btnEliminar = document.createElement('button');
            btnEliminar.type = 'button';
            btnEliminar.className = 'btn btn-default btn-xs';
            btnEliminar.title = 'Eliminar imagen';
            btnEliminar.innerHTML = '&times;';
            btnEliminar.style.cssText = 'position:absolute; top:2px; right:2px; padding:0 4px; line-height:1.2; font-size:14px; border-radius:3px;';
            btnEliminar.onclick = (function (index) {
                return function () {
                    filesToUpload.splice(index, 1);
                    renderAdjuntosPreviews();
                };
            })(idx);
            wrap.appendChild(img);
            wrap.appendChild(btnEliminar);
            cont.appendChild(wrap);
        });
    }

    function handlePasteImagen(e) {
        var items = e.clipboardData && e.clipboardData.items;
        if (!items) return;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== 0) continue;
            e.preventDefault();
            e.stopPropagation();
            var file = items[i].getAsFile();
            if (aceptaImagen(file)) {
                filesToUpload.push(file);
                renderAdjuntosPreviews();
            }
            break;
        }
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        if (e.dataTransfer) e.dataTransfer.dropEffect = 'copy';
        var z = document.getElementById('zonaPegarImagen');
        if (z) z.classList.add('avances-zona-pegar-drag');
    }

    function handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        var z = document.getElementById('zonaPegarImagen');
        if (z) z.classList.remove('avances-zona-pegar-drag');
    }

    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        var z = document.getElementById('zonaPegarImagen');
        if (z) z.classList.remove('avances-zona-pegar-drag');
        var files = e.dataTransfer && e.dataTransfer.files;
        if (!files || files.length === 0) return;
        for (var i = 0; i < files.length; i++) {
            if (aceptaImagen(files[i])) {
                filesToUpload.push(files[i]);
            }
        }
        if (filesToUpload.length > 0) renderAdjuntosPreviews();
    }

    function abrirModalAvance() {
        var b = document.getElementById('modalAvanceBackdrop');
        if (b) b.classList.add('modal-avance-abierto');
    }
    function cerrarModalAvance() {
        var b = document.getElementById('modalAvanceBackdrop');
        if (b) b.classList.remove('modal-avance-abierto');
    }
    function abrirFormAvance(tarCod, pctActual, obsActual, comentarioSup) {
        var pct = parseInt(pctActual, 10) || 0;
        document.getElementById('Tar_Cod_Avance').value = tarCod;
        document.getElementById('Ava_Porcentaje_Actual').value = pct;
        document.getElementById('Ava_Porcentaje').value = pct;
        document.getElementById('Ava_Porcentaje').setAttribute('min', pct);
        document.getElementById('Ava_Observacion').value = obsActual || '';
        var boxSup = document.getElementById('comentarioSupervisorBox');
        var txtSup = document.getElementById('comentarioSupervisorTexto');
        if (boxSup && txtSup) {
            if (comentarioSup && String(comentarioSup).trim()) {
                txtSup.textContent = String(comentarioSup).trim();
                boxSup.style.display = 'block';
            } else {
                boxSup.style.display = 'none';
            }
        }
        document.getElementById('adjuntos').value = '';
        filesToUpload = [];
        renderAdjuntosPreviews();
        abrirModalAvance();
        var imgCtx = document.getElementById('imagenesTareaContexto');
        var imgLista = document.getElementById('imagenesTareaContextoLista');
        imgCtx.style.display = 'none';
        imgLista.innerHTML = '';
        $.get(urlBase, { listarAdjuntosTarea: 1, Tar_Cod: tarCod }, function (adjT) {
            var rows = (adjT && adjT.rows) ? adjT.rows : [];
            if (imgCtx && imgLista && rows.length > 0) {
                var html = '';
                for (var a = 0; a < rows.length; a++) {
                    var ruta = (rows[a].Adj_Ruta || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    var nombre = (rows[a].Adj_Nombre || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    html += '<a href="' + urlAdjuntos + ruta + '" target="_blank" title="' + nombre + '"><img src="' + urlAdjuntos + ruta + '" alt="" style="max-height:80px; max-width:120px; object-fit:contain; border:1px solid #ccc; border-radius:4px;" /></a>';
                }
                imgLista.innerHTML = html;
                imgCtx.style.display = 'block';
            }
        }, 'json');
    }

    function renderTabla(rows, sinVinculo, tieneTareasAlguna) {
        var body = document.getElementById('bodyMisTareas');
        if (!body) return;
        actualizarMiniDashboard(rows);
        var html = '';
        if (sinVinculo) {
            html = '<tr><td colspan="10" class="text-warning">Su usuario no está vinculado a un empleado (personal). No tiene tareas asignadas. Contacte al administrador.</td></tr>';
        } else if (rows.length === 0) {
            html = '<tr><td colspan="10" class="text-muted">' + (tieneTareasAlguna ? 'No hay tareas que coincidan con los filtros seleccionados.' : 'No tiene tareas asignadas. Las tareas del despacho se asignan desde el Generador de Tareas.') + '</td></tr>';
        } else {
            for (var i = 0; i < rows.length; i++) {
                var r = rows[i];
                var titulo = ((r.Cliente_Nombre || '') + ' - ' + (r.Act_Nombre || '')).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                var descripcion = ((r.Act_Nombre || '') + ' (' + (r.Ser_Nombre || '') + ')').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                var descripcionCell = descripcion ? '<span class="celda-descripcion">' + descripcion + '</span>' : '<span class="text-muted">-</span>';
                var prioridadCell = '<span class="text-muted">-</span>';
                var estadoRaw = (r.Tar_Est || '').trim();
                var estadoClase = 'estado-otro';
                if (estadoRaw === 'FINALIZADA') estadoClase = 'estado-finalizada';
                else if (estadoRaw === 'PENDIENTE' || estadoRaw === 'VENCIDA') estadoClase = 'estado-pendiente';
                else if (estadoRaw === 'EN_PROCESO') estadoClase = 'estado-en-proceso';
                else if (estadoRaw === 'ASIGNADA') estadoClase = 'estado-asignado';
                var estado = '<span class="estado-badge ' + estadoClase + '">' + (estadoRaw || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') + '</span>';
                var fechaIni = (r.Tar_Periodo || '').replace(/^(\d{4})-(\d{2})$/, '$2/$1');
                var fechaIniCell = fechaIni ? fechaIni : '<span class="text-muted">-</span>';
                var fechaFin = (r.Tar_Fecha_Limite && r.Tar_Fecha_Limite !== '0000-00-00') ? fmtFechaDMA(r.Tar_Fecha_Limite) : '';
                var fechaFinCell = fechaFin ? fechaFin : '<span class="text-muted">-</span>';
                var fechaCulm = (r.Tar_Fecha_Culminacion && r.Tar_Fecha_Culminacion !== '0000-00-00') ? fmtFechaDMA(r.Tar_Fecha_Culminacion) : '';
                var fechaCulmCell = fechaCulm ? fechaCulm : '<span class="text-muted">-</span>';
                var pct = r.TarUsu_Porcentaje != null ? (parseInt(r.TarUsu_Porcentaje, 10) || 0) : 0;
                var claseBarra = pct >= 70 ? 'alto' : (pct >= 40 ? 'medio' : 'bajo');
                var barraAvance = '<div class="barra-progreso-fondo"><div class="barra-progreso-relleno ' + claseBarra + '" style="width:' + pct + '%;">' + pct + '%</div></div>';
                var culminada = (estadoRaw === 'FINALIZADA' || pct >= 100);
                var comentarioSup = (r.Tar_Comentario_Supervisor || r.Tar_Observaciones || '').trim();
                var comentarioSupEsc = comentarioSup.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                var comentarioShort = comentarioSup.substring(0, 35) + (comentarioSup.length > 35 ? '…' : '');
                var comentarioShortEsc = comentarioShort.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                var comentarioCell = comentarioSup ? '<span class="comentario-task" title="' + comentarioSupEsc + '" style="max-width:150px; display:inline-block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + comentarioShortEsc + '</span>' : '<span class="text-muted">-</span>';
                var obsUsu = (r.TarUsu_Observacion || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/&/g, '&amp;');
                var comentarioSupAttr = comentarioSupEsc.replace(/'/g, '&#39;');
                var accion = culminada ? '' : '<button type="button" class="btn btn-xs btn-editar-modificar btn-editar-avance" data-tar="' + (r.Tar_Cod || '') + '" data-pct="' + pct + '" data-obs="' + obsUsu + '" data-comentario-sup="' + comentarioSupAttr + '" title="Editar avance"><i class="glyphicon glyphicon-pencil"></i></button>';
                html += '<tr><td>' + titulo + '</td><td>' + descripcionCell + '</td><td>' + prioridadCell + '</td><td class="col-estado">' + estado + '</td><td>' + fechaIniCell + '</td><td>' + fechaFinCell + '</td><td>' + fechaCulmCell + '</td><td>' + barraAvance + '</td><td class="col-comentario">' + comentarioCell + '</td><td>' + accion + '</td></tr>';
            }
        }
        body.innerHTML = html;
        $(document).off('click.despachoEditar', '.btn-editar-avance').on('click.despachoEditar', '.btn-editar-avance', function () {
            abrirFormAvance($(this).data('tar'), $(this).data('pct'), $(this).data('obs'), $(this).data('comentario-sup'));
        });
    }

    function refreshMisTareas() {
        var body = document.getElementById('bodyMisTareas');
        if (!body) return;
        body.innerHTML = '<tr><td colspan="10" class="text-muted">Cargando…</td></tr>';
        $.get(urlBase, { misTareasDespacho: 1 }, function (resp) {
            sinVinculoFlag = resp && resp.sin_vinculo;
            rowsAll = (resp && resp.rows) ? resp.rows : [];
            var rows = filtrarPorEstado(filtrarPorPeriodo(rowsAll));
            renderTabla(rows, sinVinculoFlag, rowsAll.length > 0);
        }, 'json').fail(function () {
            body.innerHTML = '<tr><td colspan="10" class="text-danger">Error al cargar.</td></tr>';
        });
    }

    $(function () {
        $('#avancesEstado, #avancesPeriodo').on('change', function () {
            var rows = filtrarPorEstado(filtrarPorPeriodo(rowsAll));
            renderTabla(rows, sinVinculoFlag, rowsAll.length > 0);
        });
        $('#btnRefrescarMisTareas').on('click', refreshMisTareas);
        $('#btnCancelarAvance, #btnCerrarModalAvance').on('click', cerrarModalAvance);
        $('#modalAvanceBackdrop').on('click', function (e) {
            if (e.target === this) cerrarModalAvance();
        });
        $(document).on('keydown.modalAvance', function (e) {
            if (e.key !== 'Escape') return;
            if (document.getElementById('modalAvanceBackdrop').classList.contains('modal-avance-abierto')) {
                cerrarModalAvance();
            } else if (document.getElementById('modalNuevaTarea').classList.contains('modal-avance-abierto')) {
                cerrarModalNuevaTarea();
            }
        });
        $('#adjuntos').on('change', function () {
            var files = this.files;
            if (!files) return;
            for (var i = 0; i < files.length; i++) {
                if (aceptaImagen(files[i])) filesToUpload.push(files[i]);
            }
            renderAdjuntosPreviews();
        });
        var zonaPegar = document.getElementById('zonaPegarImagen');
        if (zonaPegar) {
            zonaPegar.addEventListener('paste', handlePasteImagen);
            zonaPegar.addEventListener('dragover', handleDragOver);
            zonaPegar.addEventListener('dragleave', handleDragLeave);
            zonaPegar.addEventListener('drop', handleDrop);
        }
        // -------- Nueva tarea eventual (modal) --------
        function cargarClientesNuevaTarea() {
            var per = $('#nuevaTareaPeriodo').val() || '';
            public $cli = $('#nuevaTareaCliente');
            public $act = $('#nuevaTareaActividad');
            if ($cli.hasClass('select2-hidden-accessible')) $cli.select2('destroy');
            if ($act.hasClass('select2-hidden-accessible')) $act.select2('destroy');
            $cli.find('option:gt(0)').remove();
            $act.find('option:gt(0)').remove();
            $act.append($('<option value="">-- Seleccione cliente primero --</option>'));
            if (!per || per.length < 7) return;
            $.get(urlBase, { listarClientesContratosPeriodo: 1, Tar_Periodo: per }, function (r) {
                (r.rows || []).forEach(function (row) {
                    $cli.append($('<option></option>').val(row.Cli_Cod).text(row.Cliente_Nombre || ''));
                });
                initSelect2Buscable($cli, { placeholder: '-- Seleccione --' });
            }, 'json');
        }
        function cargarActividadesEventualesNuevaTarea() {
            var per = $('#nuevaTareaPeriodo').val() || '';
            var cli = $('#nuevaTareaCliente').val() || '';
            public $act = $('#nuevaTareaActividad');
            if ($act.hasClass('select2-hidden-accessible')) $act.select2('destroy');
            $act.find('option:gt(0)').remove();
            if (!per || !cli) {
                $act.append($('<option value="">-- Seleccione cliente y período --</option>'));
                return;
            }
            $.get(urlBase, { listarActividadesEventuales: 1, Tar_Periodo: per, Cli_Cod: cli }, function (r) {
                var rows = r.rows || [];
                if (!rows.length) {
                    $act.append($('<option value="">-- Sin actividades eventuales en contrato --</option>'));
                    return;
                }
                rows.forEach(function (row) {
                    var txt = (row.Ser_Nombre ? (row.Ser_Nombre + ' - ') : '') + (row.Act_Nombre || '');
                    $act.append($('<option></option>').val(row.Act_Cod).text(txt));
                });
                initSelect2Buscable($act, { placeholder: '-- Seleccione actividad --' });
            }, 'json');
        }
        function abrirModalNuevaTarea() {
            var hoy = new Date();
            var y = hoy.getFullYear();
            var m = (hoy.getMonth() + 1) < 10 ? '0' + (hoy.getMonth() + 1) : (hoy.getMonth() + 1);
            $('#nuevaTareaPeriodo').val(y + '-' + m);
            $('#nuevaTareaCliente').val('');
            $('#nuevaTareaActividad').find('option:gt(0)').remove();
            $('#nuevaTareaActividad').append($('<option value="">-- Seleccione cliente primero --</option>'));
            $('#nuevaTareaFechaLimite').val('');
            $('#nuevaTareaResultado').html('');
            cargarClientesNuevaTarea();
            $('#modalNuevaTarea').addClass('modal-avance-abierto');
        }
        function cerrarModalNuevaTarea() {
            $('#modalNuevaTarea').removeClass('modal-avance-abierto');
        }
        $('#btnNuevaTarea').on('click', function () {
            if (sinVinculoFlag) {
                alert('Su usuario no está vinculado a un empleado. No puede crear tareas.');
                return;
            }
            abrirModalNuevaTarea();
        });
        $('#btnCerrarModalNuevaTarea, #btnCancelarNuevaTarea').on('click', cerrarModalNuevaTarea);
        $('#modalNuevaTarea').on('click', function (e) {
            if (e.target === this) cerrarModalNuevaTarea();
        });
        $('#nuevaTareaPeriodo').on('change', function () {
            cargarClientesNuevaTarea();
            $('#nuevaTareaCliente').val('');
            cargarActividadesEventualesNuevaTarea();
        });
        $('#nuevaTareaCliente').on('change', cargarActividadesEventualesNuevaTarea);
        $('#btnCrearNuevaTarea').on('click', function () {
            var per = ($('#nuevaTareaPeriodo').val() || '').trim();
            var cli = $('#nuevaTareaCliente').val() || '';
            var act = $('#nuevaTareaActividad').val() || '';
            var fec = ($('#nuevaTareaFechaLimite').val() || '').trim();
            if (!per || per.length < 7) { alert('Indique el período (mes).'); return; }
            if (!cli) { alert('Seleccione el cliente.'); return; }
            if (!act) { alert('Seleccione la actividad eventual.'); return; }
            public $btn = $(this);
            $btn.prop('disabled', true);
            $('#nuevaTareaResultado').html('<span class="text-muted"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Creando...</span>');
            $.post(urlBase, { generarTareaEventual: 1, Tar_Periodo: per, Cli_Cod: cli, Act_Cod: act, Tar_Fecha_Limite: fec }, function (r) {
                $btn.prop('disabled', false);
                if (r && r.success) {
                    $('#nuevaTareaResultado').html('<div class="text-success">' + (r.message || 'Tarea creada y asignada.') + '</div>');
                    refreshMisTareas();
                    setTimeout(function () { cerrarModalNuevaTarea(); }, 1200);
                } else {
                    $('#nuevaTareaResultado').html('<div class="text-danger">' + (r && r.message ? r.message : 'Error al crear.') + '</div>');
                }
            }, 'json').fail(function () {
                $btn.prop('disabled', false);
                $('#nuevaTareaResultado').html('<div class="text-danger">Error de conexión.</div>');
            });
        });

        $('#btnGuardarAvance').on('click', function () {
            var tar = $('#Tar_Cod_Avance').val();
            var porc = parseInt($('#Ava_Porcentaje').val(), 10) || 0;
            var pctActual = parseInt($('#Ava_Porcentaje_Actual').val(), 10) || 0;
            if (!tar) { alert('Error.'); return; }
            if (porc < pctActual) {
                alert('No puede disminuir el porcentaje de avance. El avance actual es ' + pctActual + '%. Debe ingresar un valor igual o mayor.');
                return;
            }
            var formData = new FormData();
            formData.append('actualizarPorcentaje', '1');
            formData.append('Tar_Cod', tar);
            formData.append('TarUsu_Porcentaje', porc);
            formData.append('TarUsu_Observacion', $('#Ava_Observacion').val() || '');
            if (filesToUpload.length > 0) {
                for (var i = 0; i < filesToUpload.length; i++) {
                    formData.append('adjuntos[]', filesToUpload[i]);
                }
            }
            $.ajax({
                url: urlBase,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (r) {
                    if (r && r.success) {
                        cerrarModalAvance();
                        refreshMisTareas();
                    } else {
                        alert(r && r.message ? r.message : 'Error.');
                    }
                },
                error: function () { alert('Error de conexión.'); }
            });
        });
        refreshMisTareas();
    });
})();
</script>
</body>
</html>
