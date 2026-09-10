<?php
/**
 * Formulario de avances - Mis tareas asignadas (página independiente)
 * El usuario solo ve sus tareas asignadas y puede registrar/editar avances.
 * Módulo: avances | Área: aud | Versión: 1.0
 *
 * @author Sistema EXA
 * @version 1.0
 * @package auditoria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/aud_log_dashboard_tareas_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Aud_Tareas($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Aud_Tareas;

$Ses_Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;
$Ses_Usu_Cod = isset($Ses_Usu_Cod) ? intval($Ses_Usu_Cod) : 0;

// ----- Ajax: Registrar o actualizar avance -----
$req_avance = array_merge($_GET, $_POST);
if (!empty($req_avance['guardarAvance'])) {
    $resp = array('success' => false);
    try {
    $tarCod = isset($req_avance['Tar_Cod']) ? intval($req_avance['Tar_Cod']) : 0;
    $desc_raw = isset($req_avance['Ava_Descripcion']) ? trim($req_avance['Ava_Descripcion']) : '';
    // Asegurar UTF-8: si llegó en Latin1/Windows-1252 (común en Windows), convertir
    if ($desc_raw !== '' && !mb_check_encoding($desc_raw, 'UTF-8')) {
        $enc = mb_detect_encoding($desc_raw, array('Windows-1252', 'ISO-8859-1'), true);
        if ($enc) {
            $desc_raw = mb_convert_encoding($desc_raw, 'UTF-8', $enc);
        } else {
            $desc_raw = mb_convert_encoding($desc_raw, 'UTF-8', 'ISO-8859-1');
        }
    }
    $porc = isset($req_avance['Ava_Porcentaje']) ? min(100, max(0, intval($req_avance['Ava_Porcentaje']))) : 0;
    $fecCulminacion = isset($req_avance['Tar_Fecha_Culminacion']) ? trim($req_avance['Tar_Fecha_Culminacion']) : '';
    if ($tarCod <= 0) {
        $resp['message'] = 'Debe indicar la tarea.';
        echo json_encode($resp);
        exit;
    }
    $fec = date('Y-m-d H:i:s');
    $existe = $obBD_con1->getRowConsulta(13, array('Tar_Cod' => $tarCod), $obBD_conexion);
    // Escapar después de usar la conexión (charset UTF-8 ya aplicado) para no truncar tildes
    $conn = isset($obBD_conexion->conexion) ? $obBD_conexion->conexion : null;
    if ($conn) {
        @mysqli_set_charset($conn, 'utf8');
        $desc_safe = mysqli_real_escape_string($conn, $desc_raw);
    } else {
        $desc_safe = addslashes($desc_raw);
    }
    if (!empty($existe) && isset($existe['Ava_Cod'])) {
        $par = array('Ava_Cod' => $existe['Ava_Cod'], 'Ava_Porcentaje' => $porc, 'Ava_Descripcion_safe' => $desc_safe, 'Ava_Fecha' => $fec);
        $obBD_con1->operacionobBD(14, $par, $obBD_conexion);
    } else {
        $par = array('Tar_Cod' => $tarCod, 'Usu_Cod' => $Ses_Usu_Cod, 'Ava_Descripcion_safe' => $desc_safe, 'Ava_Porcentaje' => $porc, 'Ava_Fecha' => $fec);
        $obBD_con1->operacionobBD(3, $par, $obBD_conexion);
    }
    $resp['success'] = ($obBD_con1->Error == 0);
    $avaCod = 0;
    if ($resp['success']) {
        $avaCod = (!empty($existe) && isset($existe['Ava_Cod'])) ? intval($existe['Ava_Cod']) : intval($obBD_con1->insercionid($conn ? $conn : $obBD_conexion->conexion));
        if ($porc == 100) {
            $obBD_con1->operacionobBD(22, array('Tar_Cod' => $tarCod, 'Tar_Fecha_Culminacion' => $fecCulminacion), $obBD_conexion);
            if ($obBD_con1->Error != 0) {
                $obBD_con1->Error = 0;
                $obBD_con1->operacionobBD(28, array('Tar_Cod' => $tarCod), $obBD_conexion);
            }
        } else {
            $obBD_con1->operacionobBD(34, array('Tar_Cod' => $tarCod), $obBD_conexion);
            if ($obBD_con1->Error != 0) {
                $obBD_con1->Error = 0;
                $obBD_con1->operacionobBD(35, array('Tar_Cod' => $tarCod), $obBD_conexion);
            }
            if ($porc > 0 && $porc < 100) {
                $obBD_con1->operacionobBD(26, array('Tar_Cod' => $tarCod), $obBD_conexion);
            }
        }
        // Subir capturas/imágenes adjuntos (evidencia del avance) - si falla la tabla no existe, no afecta el avance
        if ($avaCod > 0 && !empty($_FILES['adjuntos'])) {
            $dirAdjuntos = __DIR__ . '/../adjuntos/avances';
            if (!is_dir($dirAdjuntos)) {
                @mkdir($dirAdjuntos, 0755, true);
            }
            if (is_dir($dirAdjuntos) && is_writable($dirAdjuntos)) {
                $allowTypes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
                $allowExt = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                $maxSize = 5 * 1024 * 1024; // 5 MB
                $files = $_FILES['adjuntos'];
                $names = isset($files['name']) ? $files['name'] : array();
                $tmpNames = isset($files['tmp_name']) ? $files['tmp_name'] : array();
                $errors = isset($files['error']) ? $files['error'] : array();
                if (!is_array($names)) {
                    $names = array($names);
                    $tmpNames = array($tmpNames);
                    $errors = array($errors);
                }
                $fecAdj = date('Y-m-d H:i:s');
                foreach ($names as $idx => $origName) {
                    if (empty($origName) || !isset($tmpNames[$idx]) || !is_uploaded_file($tmpNames[$idx])) continue;
                    if (isset($errors[$idx]) && $errors[$idx] !== UPLOAD_ERR_OK) continue;
                    $size = isset($files['size']) && is_array($files['size']) ? $files['size'][$idx] : $files['size'];
                    if ($size > $maxSize) continue;
                    $mime = '';
                    if (function_exists('finfo_open')) {
                        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                        if ($finfo) {
                            $mime = @finfo_file($finfo, $tmpNames[$idx]);
                            finfo_close($finfo);
                        }
                    }
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowExt)) $ext = 'jpg';
                    if (!empty($mime) && !in_array($mime, $allowTypes)) continue;
                    $safeName = $avaCod . '_' . time() . '_' . $idx . '.' . $ext;
                    $destino = $dirAdjuntos . '/' . $safeName;
                    if (move_uploaded_file($tmpNames[$idx], $destino)) {
                        $errAnt = $obBD_con1->Error;
                        $obBD_con1->operacionobBD(40, array('Ava_Cod' => $avaCod, 'Adj_Nombre' => basename($origName), 'Adj_Ruta' => $safeName, 'Adj_Fecha' => $fecAdj), $obBD_conexion);
                        if ($obBD_con1->Error != 0) {
                            $obBD_con1->Error = $errAnt;
                        }
                    }
                }
            }
        }
    }
    if (!$resp['success']) {
        $resp['message'] = $obBD_con1->MsgError;
    }
    } catch (Throwable $e) {
        $resp['message'] = 'Error: ' . $e->getMessage();
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Listar adjuntos (imágenes) de una tarea (para contexto al asignado) -----
if (!empty($_REQUEST['listarAdjuntosTarea'])) {
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    $arr = array();
    if ($tarCod > 0) {
        try {
            $arr = $obBD_con1->getArrayConsulta(43, array('Tar_Cod' => $tarCod), $obBD_conexion);
        } catch (Throwable $e) {
            $arr = array();
        }
        if (!is_array($arr)) $arr = array();
    }
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Listar adjuntos (capturas) de un avance -----
if (!empty($_REQUEST['listarAdjuntosAvance'])) {
    $avaCod = isset($_REQUEST['Ava_Cod']) ? intval($_REQUEST['Ava_Cod']) : 0;
    $arr = array();
    if ($avaCod > 0) {
        try {
            $arr = $obBD_con1->getArrayConsulta(41, array('Ava_Cod' => $avaCod), $obBD_conexion);
        } catch (Throwable $e) {
            $arr = array();
        }
        if (!is_array($arr)) $arr = array();
    }
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Mis tareas asignadas (case 24 sin Tar_Fecha_Culminacion por si la migración no se ejecutó) -----
if (!empty($_REQUEST['listarMisTareasAsignadas'])) {
    $rowPer = $obBD_con1->getRowConsulta(16, array('Usu_Cod' => $Ses_Usu_Cod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    $perCod = isset($rowPer['Per_Cod']) ? intval($rowPer['Per_Cod']) : 0;
    $resp = array('rows' => array(), 'sin_vinculo' => false);
    if ($perCod <= 0) {
        $resp['sin_vinculo'] = true;
        utf8_encode_deep($resp);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($resp);
        exit;
    }
    $asig = $obBD_con1->getArrayConsulta(24, array('Per_Cod' => $perCod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    if (!is_array($asig)) {
        $asig = array();
    }
    if ($obBD_con1->Error != 0) {
        $obBD_con1->Error = 0;
        $obBD_con1->MsgError = '';
        $asig = $obBD_con1->getArrayConsulta(27, array('Per_Cod' => $perCod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
        if (!is_array($asig)) {
            $asig = array();
        }
        foreach ($asig as &$row) {
            if (!isset($row['Tar_Fecha_Culminacion'])) {
                $row['Tar_Fecha_Culminacion'] = null;
            }
        }
        unset($row);
    }
    $arrAv = $obBD_con1->getArrayConsulta(15, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    if (!is_array($arrAv)) {
        $arrAv = array();
    }
    $avances = array();
    foreach ($arrAv as $r) {
        $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
        if ($tc > 0 && !isset($avances[$tc])) {
            $avances[$tc] = $r;
        }
    }
    foreach ($asig as &$r) {
        $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
        $r['Ava_Porcentaje'] = isset($avances[$tc]['Ava_Porcentaje']) ? $avances[$tc]['Ava_Porcentaje'] : null;
        $r['Ava_Cod'] = isset($avances[$tc]['Ava_Cod']) ? $avances[$tc]['Ava_Cod'] : null;
    }
    unset($r);
    $resp['rows'] = $asig;
    utf8_encode_deep($resp);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Un avance por tarea (para editar) - incluye Tar_Fecha_Culminacion si existe -----
if (isset($avancePorTarea)) {
    $tarCod = isset($Tar_Cod) ? intval($Tar_Cod) : 0;
    if ($tarCod <= 0) {
        echo json_encode(array('row' => null));
        exit;
    }
    $row = $obBD_con1->getRowConsulta(13, array('Tar_Cod' => $tarCod), $obBD_conexion);
    $obBD_con1->Error = 0;
    $tareaFec = $obBD_con1->getRowConsulta(29, array('Tar_Cod' => $tarCod), $obBD_conexion);
    if ($obBD_con1->Error == 0 && !empty($tareaFec) && isset($tareaFec['Tar_Fecha_Culminacion']) && $tareaFec['Tar_Fecha_Culminacion'] !== '' && $tareaFec['Tar_Fecha_Culminacion'] !== '0000-00-00') {
        $row['Tar_Fecha_Culminacion'] = $tareaFec['Tar_Fecha_Culminacion'];
    }
    utf8_encode_deep($row);
    echo json_encode(array('row' => $row));
    exit;
}

// ----- Ajax: Actualizar fecha fin de tarea (solo el usuario asignado) -----
if (isset($actualizarFechaFinTarea)) {
    $resp = array('success' => false);
    $tarCod = isset($Tar_Cod) ? intval($Tar_Cod) : 0;
    $fecFin = isset($Tar_Fecha_Fin) ? trim($Tar_Fecha_Fin) : '';
    if ($tarCod <= 0) {
        $resp['message'] = 'Tarea no indicada.';
        echo json_encode($resp);
        exit;
    }
    $rowPer = $obBD_con1->getRowConsulta(16, array('Usu_Cod' => $Ses_Usu_Cod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    $perCod = isset($rowPer['Per_Cod']) ? intval($rowPer['Per_Cod']) : 0;
    if ($perCod <= 0) {
        $resp['message'] = 'Usuario no vinculado a empleado.';
        echo json_encode($resp);
        exit;
    }
    $asig = $obBD_con1->getRowConsulta(20, array('Tar_Cod' => $tarCod, 'Per_Cod' => $perCod), $obBD_conexion);
    if (empty($asig)) {
        $resp['message'] = 'No tiene asignada esta tarea.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(19, array('Tar_Cod' => $tarCod, 'Tar_Fecha_Fin' => $fecFin), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) {
        $resp['message'] = $obBD_con1->MsgError;
    }
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Crear tarea adicional (autoasignada al usuario actual) -----
if (!empty($_REQUEST['crearTareaAdicional'])) {
    $resp = array('success' => false);
    try {
        $req = array_merge($_GET, $_POST);
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }
        $titulo = isset($req['Tar_Titulo']) ? trim($req['Tar_Titulo']) : '';
        $desc = isset($req['Tar_Descripcion']) ? trim($req['Tar_Descripcion']) : '';
        $normalizar_utf8 = function ($s) {
            if ($s === '') return '';
            if (function_exists('mb_convert_encoding')) {
                $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
            }
            if (mb_check_encoding($s, 'UTF-8')) return $s;
            $enc = mb_detect_encoding($s, array('Windows-1252', 'ISO-8859-1'), true);
            return $enc ? mb_convert_encoding($s, 'UTF-8', $enc) : mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
        };
        $titulo = $normalizar_utf8($titulo);
        $desc = $normalizar_utf8($desc);
        $prioridad = isset($req['Tar_Prioridad']) && in_array($req['Tar_Prioridad'], array('Alta', 'Media', 'Baja')) ? $req['Tar_Prioridad'] : 'Media';
        $fecIni = isset($req['Tar_Fecha_Inicio']) ? trim($req['Tar_Fecha_Inicio']) : '';
        $fecFin = isset($req['Tar_Fecha_Fin']) ? trim($req['Tar_Fecha_Fin']) : '';
        if ($titulo === '' || $fecIni === '') {
            $resp['message'] = 'Título y fecha de inicio son obligatorios.';
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($resp);
            exit;
        }
        $rowPer = $obBD_con1->getRowConsulta(16, array('Usu_Cod' => $Ses_Usu_Cod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
        $perCod = isset($rowPer['Per_Cod']) ? intval($rowPer['Per_Cod']) : 0;
        if ($perCod <= 0) {
            $resp['message'] = 'Su usuario no está vinculado a un empleado. No puede crear tareas adicionales. Contacte al administrador.';
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($resp);
            exit;
        }
        $conn = $obBD_conexion->conexion;
        mysqli_set_charset($conn, 'utf8');
        $titulo_safe = ($conn && function_exists('mysqli_real_escape_string')) ? mysqli_real_escape_string($conn, $titulo) : addslashes($titulo);
        $desc_safe = ($conn && function_exists('mysqli_real_escape_string')) ? mysqli_real_escape_string($conn, $desc) : addslashes($desc);
        $estado = 'Pendiente';
        $par = array('Tar_Titulo_safe' => $titulo_safe, 'Tar_Descripcion_safe' => $desc_safe, 'Tar_Prioridad' => $prioridad, 'Tar_Fecha_Inicio' => $fecIni, 'Tar_Fecha_Fin' => $fecFin, 'Tar_Estado' => $estado, 'Usu_Creador' => $Ses_Usu_Cod, 'Emp_Cod' => $Ses_Emp_Cod);
        $obBD_con1->operacionobBD(1, $par, $obBD_conexion);
        $resp['success'] = ($obBD_con1->Error == 0);
        if ($resp['success']) {
            $tarCod = $obBD_con1->insercionid($conn);
            $obBD_con1->operacionobBD(2, array('Tar_Cod' => $tarCod, 'Per_Cod' => $perCod, 'Tas_Fecha_Asignacion' => date('Y-m-d H:i:s')), $obBD_conexion);
            if ($obBD_con1->Error == 0) {
                $obBD_con1->operacionobBD(25, array('Tar_Cod' => $tarCod), $obBD_conexion);
            }
            $resp['Tar_Cod'] = $tarCod;
            $tiene_archivos = !empty($_FILES['adjuntosTareaAdicional']) && isset($_FILES['adjuntosTareaAdicional']['name']);
            if ($tiene_archivos) {
                $dirAdjuntos = __DIR__ . '/../adjuntos/tareas';
                if (!is_dir($dirAdjuntos)) @mkdir($dirAdjuntos, 0755, true);
                if (is_dir($dirAdjuntos) && is_writable($dirAdjuntos)) {
                    $allowTypes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
                    $allowExt = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                    $maxSize = 5 * 1024 * 1024;
                    $files = $_FILES['adjuntosTareaAdicional'];
                    $names = isset($files['name']) ? $files['name'] : array();
                    $tmpNames = isset($files['tmp_name']) ? $files['tmp_name'] : array();
                    $errors = isset($files['error']) ? $files['error'] : array();
                    if (!is_array($names)) { $names = array($names); $tmpNames = array($tmpNames); $errors = array($errors); }
                    $fecAdj = date('Y-m-d H:i:s');
                    foreach ($names as $idx => $origName) {
                        if (empty($origName) || !isset($tmpNames[$idx]) || !is_uploaded_file($tmpNames[$idx])) continue;
                        if (isset($errors[$idx]) && $errors[$idx] !== UPLOAD_ERR_OK) continue;
                        $size = isset($files['size']) && is_array($files['size']) ? $files['size'][$idx] : $files['size'];
                        if ($size > $maxSize) continue;
                        $mime = '';
                        if (function_exists('finfo_open')) {
                            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                            if ($finfo) { $mime = @finfo_file($finfo, $tmpNames[$idx]); finfo_close($finfo); }
                        }
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowExt)) $ext = 'jpg';
                        if (!empty($mime) && !in_array($mime, $allowTypes)) continue;
                        $safeName = $tarCod . '_' . time() . '_' . $idx . '.' . $ext;
                        $destino = $dirAdjuntos . '/' . $safeName;
                        if (move_uploaded_file($tmpNames[$idx], $destino)) {
                            $obBD_con1->operacionobBD(42, array('Tar_Cod' => $tarCod, 'Adj_Nombre' => basename($origName), 'Adj_Ruta' => $safeName, 'Adj_Fecha' => $fecAdj), $obBD_conexion);
                        }
                    }
                }
            }
        } else {
            $resp['message'] = $obBD_con1->MsgError;
        }
    } catch (Throwable $e) {
        $resp['message'] = 'Error: ' . $e->getMessage();
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($resp);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <TITLE><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'EXA'; ?> - Mis avances</TITLE>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <?php require_once('../../mascaras/model1/estilos/estilos.php'); ?>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/aud_val_dashboard_tareas_1.0.js"></script>
    <style>
        html, body { height: 100%; margin: 0; padding: 0; box-sizing: border-box; }
        *, *::before, *::after { box-sizing: inherit; }
        body { display: flex; flex-direction: column; }
        .form-group { margin-bottom: 10px; }
        .avances-container {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 100vh;
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0 15px 15px;
            overflow: hidden;
        }
        .config-card {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .avances-scroll-area {
            flex: 1;
            min-height: 0;
            overflow: auto;
        }
        .config-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%);
            color: white;
            padding: 8px 14px;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .config-header h4 { margin: 0; font-size: 14px; font-weight: 600; }
        .exa-header {
            flex-shrink: 0;
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 50%, #5A9BD4 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(44,93,148,0.3);
            margin-bottom: 0;
        }
        .exa-header h3 { margin: 0; font-size: 16px; font-weight: 600; }
        .aud-tabla { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .aud-tabla th, .aud-tabla td { padding: 8px; border: 1px solid #e2e8f0; text-align: left; }
        .aud-tabla th { background: linear-gradient(135deg, #72A1CF 0%, #8EB7DD 100%); color: white; font-weight: 600; }
        .aud-tabla .celda-descripcion { min-width: 200px; font-size: 12px; color: #555; white-space: normal; word-wrap: break-word; overflow-wrap: break-word; }
        .aud-tabla td { vertical-align: top; }
        .aud-tabla th:nth-child(2), .aud-tabla td:nth-child(2) { min-width: 220px; }
        .mini-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; margin-bottom: 18px; }
        .mini-stat-card { background: linear-gradient(145deg, #fff 0%, #f8fafc 100%); border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; text-align: center; }
        .mini-stat-card .stat-label { font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600; text-transform: uppercase; }
        .mini-stat-card .stat-value { font-size: 22px; font-weight: 700; }
        .mini-stat-card.total .stat-value { color: #0ea5e9; }
        .mini-stat-card.completadas .stat-value { color: #10b981; }
        .mini-stat-card.proceso .stat-value { color: #64748b; }
        .mini-stat-card.atrasadas .stat-value { color: #ef4444; }
        .mini-stat-card.avance .stat-value { color: #2C5D94; }
        .link-dashboard { color: #2C5D94; margin-left: 10px; }
        /* Barra de avance: mismos colores que el dashboard (bajo=rojo, medio=amarillo, alto=verde) */
        .barra-progreso-fondo {
            position: relative;
            min-width: 90px;
            max-width: 120px;
            margin: 0 auto;
            height: 22px;
            background: #e9ecef;
            border-radius: 4px;
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
        .barra-progreso-relleno.bajo { background: #dc3545; }
        .barra-progreso-relleno.medio { background: #ffc107; color: #333; }
        .barra-progreso-relleno.alto { background: #28a745; }
        .avances-zona-pegar {
            margin-top: 8px;
            padding: 12px;
            border: 2px dashed #2C5D94;
            border-radius: 8px;
            background: #D1E6F4;
            text-align: center;
            cursor: pointer;
            outline: none;
            box-shadow: 0 2px 12px rgba(44,93,148,0.25);
        }
        .avances-zona-pegar:focus { border-color: #2C5D94; background: #D1E6F4; box-shadow: 0 0 0 3px rgba(44,93,148,0.3); }
        .avances-zona-pegar:hover { background: #DEE7EF; border-color: #2C5D94; box-shadow: 0 4px 16px rgba(44,93,148,0.3); }
        .avances-zona-pegar.avances-zona-pegar-drag { border-color: #2C5D94; background: #8EB7DD; box-shadow: 0 4px 16px rgba(44,93,148,0.35); }
        .aud-tabla th.col-estado, .aud-tabla td.col-estado { text-align: center; }
        .estado-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .estado-badge.estado-finalizada { color: #15803d; background: #dcfce7; }
        .estado-badge.estado-pendiente { color: #c2410c; background: #ffedd5; }
        .estado-badge.estado-en-proceso { color: #2C5D94; background: #D1E6F4; }
        .estado-badge.estado-asignado { color: #475569; background: #f1f5f9; }
        /* Modal flotante Crear tarea adicional */
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
        .modal-avance-backdrop.modal-avance-abierto { display: flex; }
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
        .modal-avance-body { padding: 24px; overflow-y: auto; flex: 1; }
    </style>
</head>
<body>
<div class="avances-container">
    <div class="exa-header">
        <h3>&raquo; Formulario de avances - Mis tareas asignadas</h3>
    </div>

    <div class="config-card">
        <div class="config-header"><h4><i class="glyphicon glyphicon-tasks"></i> Mis tareas asignadas</h4></div>
        <div class="avances-scroll-area">
        <p class="text-muted small">Aquí solo se muestran las tareas que tiene asignadas su usuario. Registre o edite el avance (porcentaje) de cada una. También puede crear <strong>tareas adicionales</strong> que se autoasignan y el administrador podrá verlas.</p>
        <div class="avances-filtros" style="margin-bottom:14px;">
            <button type="button" class="btn btn-success btn-sm" id="btnToggleCrearTarea"><i class="glyphicon glyphicon-plus"></i> Crear tarea adicional</button>
        </div>
        <div id="miniDashboard" class="mini-stats" style="display:none;">
            <div class="mini-stat-card total"><div class="stat-label">Total tareas</div><div class="stat-value" id="stat-total">0</div></div>
            <div class="mini-stat-card completadas"><div class="stat-label">Completadas</div><div class="stat-value" id="stat-completadas">0</div></div>
            <div class="mini-stat-card proceso"><div class="stat-label">En proceso</div><div class="stat-value" id="stat-proceso">0</div></div>
            <div class="mini-stat-card atrasadas"><div class="stat-label">Atrasadas</div><div class="stat-value" id="stat-atrasadas">0</div></div>
            <div class="mini-stat-card avance"><div class="stat-label">Avance promedio</div><div class="stat-value" id="stat-avance">0%</div></div>
        </div>
        <div class="avances-filtros" style="margin-bottom:14px; display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
            <label class="control-label" style="margin:0; font-weight:600; color:#475569;">Estado:</label>
            <select id="avancesEstado" class="form-control input-sm" style="width:150px; border-radius:6px; border-color:#e2e8f0;">
                <option value="pendientes" selected>Pendientes</option>
                <option value="finalizados">Finalizados</option>
                <option value="todos">Todos</option>
            </select>
            <label class="control-label" style="margin:0; font-weight:600; color:#475569;">Período:</label>
            <select id="avancesPeriodo" class="form-control input-sm" style="width:150px; border-radius:6px; border-color:#e2e8f0;">
                <option value="">Todo</option>
                <option value="semana">Esta semana</option>
                <option value="mes">Este mes</option>
                <option value="anio">Este año</option>
            </select>
            <button type="button" class="btn btn-primary btn-sm" id="btnRefrescarMisAvances"><i class="glyphicon glyphicon-refresh"></i> Actualizar mis tareas</button>
        </div>
        <table id="gridMisAvances" class="aud-tabla">
            <thead><tr><th>Tarea</th><th>Descripción</th><th>Prioridad</th><th class="col-estado">Estado</th><th>Fecha Inicio</th><th>Fecha fin Tentativa</th><th>Fecha de culminación</th><th>Avance %</th><th>Acción</th></tr></thead>
            <tbody id="bodyMisAvances"><tr><td colspan="9" class="text-muted">Cargando…</td></tr></tbody>
        </table>
        </div>
    </div>
</div>

<!-- Modal flotante Crear tarea adicional -->
<div id="modalTareaAdicional" class="modal-avance-backdrop">
    <div class="modal-avance-contenido" style="max-width:560px;">
        <div class="modal-avance-header">
            <h5><i class="glyphicon glyphicon-plus"></i> Crear tarea adicional</h5>
            <button type="button" class="modal-avance-cerrar" id="btnCerrarModalTareaAdicional" title="Cerrar">&times;</button>
        </div>
        <div class="modal-avance-body">
            <p class="text-muted small" style="margin-top:0;">La tarea se autoasignará a usted. El administrador podrá verla.</p>
            <form id="formTareaAdicional" class="form-horizontal" accept-charset="UTF-8">
                <div class="form-group">
                    <label class="control-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="Tar_Titulo" id="Tar_Titulo_Adicional" class="form-control input-sm" maxlength="200" placeholder="Título de la tarea" required style="width:100%;" />
                </div>
                <div class="form-group">
                    <label class="control-label">Descripción</label>
                    <textarea name="Tar_Descripcion" id="Tar_Descripcion_Adicional" class="form-control input-sm" rows="2" placeholder="Descripción opcional" style="width:100%;"></textarea>
                </div>
                <div class="form-group" style="display:flex; flex-wrap:wrap; gap:20px 24px;">
                    <div style="display:flex; flex-direction:column; align-items:flex-start;">
                        <label class="control-label" for="Tar_Prioridad_Adicional" style="margin:0 0 6px 0;">Prioridad</label>
                        <select name="Tar_Prioridad" id="Tar_Prioridad_Adicional" class="form-control input-sm" style="min-width:120px;">
                            <option value="Media">Media</option>
                            <option value="Alta">Alta</option>
                            <option value="Baja">Baja</option>
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:flex-start;">
                        <label class="control-label" for="Tar_Fecha_Inicio_Adicional" style="margin:0 0 6px 0;">Fecha inicio <span class="text-danger">*</span></label>
                        <input type="date" name="Tar_Fecha_Inicio" id="Tar_Fecha_Inicio_Adicional" class="form-control input-sm" required />
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:flex-start;">
                        <label class="control-label" for="Tar_Fecha_Fin_Adicional" style="margin:0 0 6px 0;">Fecha fin</label>
                        <input type="date" name="Tar_Fecha_Fin" id="Tar_Fecha_Fin_Adicional" class="form-control input-sm" placeholder="Opcional" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label">Imágenes adjuntas</label>
                    <div style="width:100%;">
                        <input type="file" name="adjuntosTareaAdicional[]" id="adjuntosTareaAdicional" class="form-control input-sm" accept="image/jpeg,image/png,image/gif,image/webp" multiple />
                        <p class="text-muted small">Opcional. Suba, pegue (Ctrl+V) o arrastre imágenes. JPG, PNG, GIF, WebP. Máx. 5 MB.</p>
                        <div id="zonaPegarTareaAdicional" class="avances-zona-pegar" tabindex="0" title="Pegar (Ctrl+V) o arrastrar imágenes aquí">
                            <span class="text-muted">O pegar / arrastrar imagen aquí (Ctrl+V)</span>
                        </div>
                        <div id="adjuntosTareaAdicionalPreviews" style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px;"></div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-success btn-sm"><i class="glyphicon glyphicon-ok"></i> Crear y asignarme</button>
                    <button type="button" class="btn btn-default btn-sm" id="btnCancelarTareaAdicional" style="margin-left:8px;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal flotante Editar avance -->
<div id="modalAvance" class="modal-avance-backdrop">
    <div class="modal-avance-contenido" style="max-width:560px;">
        <div class="modal-avance-header">
            <h5 id="tituloFormAvance">Editar avance</h5>
            <button type="button" class="modal-avance-cerrar" id="btnCerrarModalAvance" title="Cerrar">&times;</button>
        </div>
        <div class="modal-avance-body">
            <div id="imagenesTareaContexto" style="display:none; margin-bottom:16px; padding:10px; background:#fff; border:1px solid #e2e8f0; border-radius:8px;">
                <strong class="text-muted small">Imágenes adjuntas:</strong>
                <div id="imagenesTareaContextoLista" style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px;"></div>
            </div>
            <form id="formAvance" class="form-horizontal" accept-charset="UTF-8">
                <input type="hidden" name="Tar_Cod" id="Tar_Cod_Avance" value="" />
                <div class="form-group">
                    <label class="control-label">Porcentaje (0-100) <span class="text-danger">*</span></label>
                    <input type="number" name="Ava_Porcentaje" id="Ava_Porcentaje" class="form-control input-sm" min="0" max="100" value="0" style="width:100px;" />
                </div>
                <div class="form-group">
                    <label class="control-label">Descripción</label>
                    <textarea name="Ava_Descripcion" id="Ava_Descripcion" class="form-control input-sm" rows="2" placeholder="Comentario del avance" style="width:100%;"></textarea>
                </div>
                <div class="form-group">
                    <label class="control-label">Capturas / imágenes</label>
                    <input type="file" name="adjuntos[]" id="adjuntos" class="form-control input-sm" accept="image/jpeg,image/png,image/gif,image/webp" multiple />
                    <p class="text-muted small">Opcional. Suba archivos, pegue (Ctrl+V) o arrastre imágenes aquí. JPG, PNG, GIF, WebP. Máx. 5 MB por imagen.</p>
                    <div id="zonaPegarImagen" class="avances-zona-pegar" tabindex="0" title="Pegar (Ctrl+V) o arrastrar imágenes aquí">
                        <span class="text-muted">O pegar / arrastrar imagen aquí (Ctrl+V)</span>
                    </div>
                    <div id="adjuntosPreviews" style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px;"></div>
                    <div id="adjuntosActuales" style="display:none; margin-top:8px;"></div>
                </div>
                <div class="form-group" id="grupoFechaCulminacion" style="display:none;">
                    <label class="control-label">Fecha culminación</label>
                    <input type="date" name="Tar_Fecha_Culminacion" id="Tar_Fecha_Culminacion" class="form-control input-sm" style="width:auto;" />
                    <p id="notaFechaCulminacion" class="text-muted small" style="margin-top:6px;">Solo visible al 100%. Se usará esta fecha o la de hoy y la tarea pasará a Finalizada.</p>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-primary btn-sm" id="btnGuardarAvance"><i class="glyphicon glyphicon-ok"></i> Guardar avance</button>
                    <button type="button" class="btn btn-default btn-sm" id="btnCancelarAvance" style="margin-left:8px;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
(function () {
    var urlBase = '<?php echo str_replace("'", "\\'", $_SERVER['PHP_SELF']); ?>';
    var rowsAll = [];
    var sinVinculoFlag = false;
    /** Lista unificada de archivos a subir (seleccionados + pegados). Una sola fuente para evitar duplicados y permitir eliminar. */
    var filesToUpload = [];
    /** Archivos para tarea adicional (imágenes de contexto) */
    var filesToUploadAdicional = [];
    var previewObjectUrlsAdicional = [];

    function getPeriodoFechas() {
        var p = document.getElementById('avancesPeriodo');
        var periodo = (p && p.value) ? p.value : '';
        var hoy = new Date();
        var ini = '', fin = '';
        var pad = function (n) { return (n < 10 ? '0' : '') + n; };
        if (periodo === 'semana') {
            var d = new Date(hoy); d.setDate(d.getDate() - 6);
            ini = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
            fin = hoy.getFullYear() + '-' + pad(hoy.getMonth() + 1) + '-' + pad(hoy.getDate());
        } else if (periodo === 'mes') {
            ini = hoy.getFullYear() + '-' + pad(hoy.getMonth() + 1) + '-01';
            fin = hoy.getFullYear() + '-' + pad(hoy.getMonth() + 1) + '-' + pad(hoy.getDate());
        } else if (periodo === 'anio') {
            ini = hoy.getFullYear() + '-01-01';
            fin = hoy.getFullYear() + '-' + pad(hoy.getMonth() + 1) + '-' + pad(hoy.getDate());
        }
        return { Fecha_Ini: ini, Fecha_Fin: fin };
    }

    function filtrarPorPeriodo(rows) {
        var pf = getPeriodoFechas();
        if (!pf.Fecha_Ini || !pf.Fecha_Fin) return rows;
        return rows.filter(function (r) {
            var fec = r.Tar_Fecha_Inicio;
            if (!fec || fec === '0000-00-00') return false;
            return fec >= pf.Fecha_Ini && fec <= pf.Fecha_Fin;
        });
    }

    function filtrarPorEstado(rows) {
        var sel = document.getElementById('avancesEstado');
        var estado = (sel && sel.value) ? sel.value : 'pendientes';
        if (estado === 'todos') return rows;
        return rows.filter(function (r) {
            var e = (r.Tar_Estado || '').trim();
            if (estado === 'finalizados') return e === 'Finalizada';
            if (estado === 'pendientes') return e !== 'Finalizada';
            return true;
        });
    }

    function formToQuery(form) {
        var s = [], el, i, name, val;
        var els = form.querySelectorAll('input, select, textarea');
        for (i = 0; i < els.length; i++) {
            el = els[i];
            if (!el.name || el.disabled) continue;
            if (el.type === 'radio' || el.type === 'checkbox') { if (!el.checked) continue; }
            if (el.type === 'file') continue;
            name = encodeURIComponent(el.name);
            val = encodeURIComponent(el.value || '');
            s.push(name + '=' + val);
        }
        return s.join('&');
    }

    function renderAdjuntosAdicionalPreviews() {
        var cont = document.getElementById('adjuntosTareaAdicionalPreviews');
        if (!cont) return;
        previewObjectUrlsAdicional.forEach(function (u) { try { URL.revokeObjectURL(u); } catch (e) {} });
        previewObjectUrlsAdicional = [];
        if (filesToUploadAdicional.length === 0) {
            cont.innerHTML = '';
            cont.style.display = 'none';
            return;
        }
        cont.style.display = 'flex';
        cont.innerHTML = '';
        filesToUploadAdicional.forEach(function (file, idx) {
            var url = URL.createObjectURL(file);
            previewObjectUrlsAdicional.push(url);
            var wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative; display:inline-block;';
            var img = document.createElement('img');
            img.src = url;
            img.alt = file.name || 'Imagen';
            img.style.cssText = 'max-height:50px; max-width:80px; object-fit:contain; border:1px solid #ccc; border-radius:4px; display:block;';
            var btnEliminar = document.createElement('button');
            btnEliminar.type = 'button';
            btnEliminar.className = 'btn btn-default btn-xs';
            btnEliminar.title = 'Eliminar';
            btnEliminar.innerHTML = '&times;';
            btnEliminar.style.cssText = 'position:absolute; top:2px; right:2px; padding:0 4px; line-height:1.2; font-size:14px;';
            btnEliminar.onclick = (function (i) { return function () { filesToUploadAdicional.splice(i, 1); renderAdjuntosAdicionalPreviews(); }; })(idx);
            wrap.appendChild(img);
            wrap.appendChild(btnEliminar);
            cont.appendChild(wrap);
        });
    }

    function handlePasteAdicional(e) {
        var items = e.clipboardData && e.clipboardData.items;
        if (!items) return;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== 0) continue;
            e.preventDefault();
            e.stopPropagation();
            var file = items[i].getAsFile();
            if (file && file.size <= 5 * 1024 * 1024) {
                filesToUploadAdicional.push(file);
                renderAdjuntosAdicionalPreviews();
            }
            break;
        }
    }

    function handleDragOverAdicional(e) { e.preventDefault(); e.stopPropagation(); if (e.dataTransfer) e.dataTransfer.dropEffect = 'copy'; var z = document.getElementById('zonaPegarTareaAdicional'); if (z) z.classList.add('avances-zona-pegar-drag'); }
    function handleDragLeaveAdicional(e) { e.preventDefault(); e.stopPropagation(); var z = document.getElementById('zonaPegarTareaAdicional'); if (z) z.classList.remove('avances-zona-pegar-drag'); }
    function handleDropAdicional(e) {
        e.preventDefault();
        e.stopPropagation();
        var z = document.getElementById('zonaPegarTareaAdicional');
        if (z) z.classList.remove('avances-zona-pegar-drag');
        var files = e.dataTransfer && e.dataTransfer.files;
        if (!files || files.length === 0) return;
        var allowExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            var ext = (f.name || '').toLowerCase().split('.').pop();
            if (f.size <= 5 * 1024 * 1024 && allowExt.indexOf(ext) !== -1) {
                filesToUploadAdicional.push(f);
            }
        }
        if (filesToUploadAdicional.length > 0) renderAdjuntosAdicionalPreviews();
    }

    var previewObjectUrls = [];
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

    var maxSizeImage = 5 * 1024 * 1024;
    var allowTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    function aceptaImagen(file) {
        if (!file || file.size > maxSizeImage) return false;
        var t = (file.type || '').toLowerCase();
        return allowTypes.indexOf(t) !== -1 || t.indexOf('image/') === 0;
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
        var added = 0;
        for (var i = 0; i < files.length; i++) {
            if (aceptaImagen(files[i])) {
                filesToUpload.push(files[i]);
                added++;
            }
        }
        if (added > 0) renderAdjuntosPreviews();
    }

    function request(method, url, data, onSuccess, onError) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if (method === 'POST' && data && !(data instanceof FormData)) {
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        }
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (xhr.status >= 200 && xhr.status < 300) {
                var resp = xhr.responseText;
                try { resp = JSON.parse(resp); } catch (e) {}
                if (onSuccess) onSuccess(resp);
            } else {
                if (onError) onError(); else alert('Error de conexión.');
            }
        };
        xhr.send(data || null);
    }

    function fmtFechaDMA(f) {
        if (!f || f === '0000-00-00') return '';
        var p = String(f).split('-');
        return p.length === 3 ? (parseInt(p[2], 10) + '/' + parseInt(p[1], 10) + '/' + p[0]) : f;
    }

    function actualizarMiniDashboard(rows) {
        var total = rows.length;
        var completadas = 0, proceso = 0, atrasadas = 0, sumaPct = 0;
        var hoy = new Date();
        var hoyStr = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-' + String(hoy.getDate()).padStart(2, '0');
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            if (r.Tar_Estado === 'Finalizada') completadas++;
            else proceso++;
            if (r.Tar_Estado !== 'Finalizada' && r.Tar_Fecha_Fin && r.Tar_Fecha_Fin !== '0000-00-00' && r.Tar_Fecha_Fin < hoyStr) atrasadas++;
            var pct = r.Ava_Porcentaje != null ? (parseInt(r.Ava_Porcentaje, 10) || 0) : 0;
            sumaPct += (pct >= 0 && pct <= 100) ? pct : 0;
        }
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

    function renderTablaYDashboard(rows, sinVinculo, tieneTareasAlguna) {
        var body = document.getElementById('bodyMisAvances');
        if (!body) return;
        actualizarMiniDashboard(rows);
        var btnToggle = document.getElementById('btnToggleCrearTarea');
        if (btnToggle) btnToggle.style.display = sinVinculo ? 'none' : 'inline-block';
        if (sinVinculo && typeof cerrarModalTareaAdicional === 'function') cerrarModalTareaAdicional();
        var html = '';
        if (sinVinculo) {
            html = '<tr><td colspan="9" class="text-warning">Su usuario no está vinculado a un empleado (personal). No tiene tareas asignadas. Contacte al administrador.</td></tr>';
        } else if (rows.length === 0) {
            html = '<tr><td colspan="9" class="text-muted">' + (tieneTareasAlguna ? 'No hay tareas que coincidan con los filtros seleccionados.' : 'No tiene tareas asignadas. Las tareas se asignan desde el Dashboard de Tareas.') + '</td></tr>';
        } else {
            for (var i = 0; i < rows.length; i++) {
                    var r = rows[i];
                    var titulo = (r.Tar_Titulo || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    var descripcion = (r.Tar_Descripcion || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/\n/g, '<br>');
                    var descripcionCell = descripcion ? '<span class="celda-descripcion" title="' + descripcion.replace(/"/g, '&quot;') + '">' + descripcion + '</span>' : '<span class="text-muted">-</span>';
                    var prioridad = (r.Tar_Prioridad || '').replace(/&/g, '&amp;');
                    var prioridadCell = prioridad ? prioridad : '<span class="text-muted">-</span>';
                    var estadoRaw = (r.Tar_Estado || '').trim();
                    var estadoClase = 'estado-otro';
                    if (estadoRaw === 'Finalizada') estadoClase = 'estado-finalizada';
                    else if (estadoRaw === 'Pendiente') estadoClase = 'estado-pendiente';
                    else if (estadoRaw === 'En Proceso') estadoClase = 'estado-en-proceso';
                    else if (estadoRaw === 'Asignado') estadoClase = 'estado-asignado';
                    var estado = '<span class="estado-badge ' + estadoClase + '">' + (estadoRaw || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') + '</span>';
                    var fechaIni = (r.Tar_Fecha_Inicio && r.Tar_Fecha_Inicio !== '0000-00-00') ? fmtFechaDMA(r.Tar_Fecha_Inicio) : '';
                    var fechaIniCell = fechaIni ? fechaIni : '<span class="text-muted">-</span>';
                    var fechaFin = (r.Tar_Fecha_Fin && r.Tar_Fecha_Fin !== '0000-00-00') ? fmtFechaDMA(r.Tar_Fecha_Fin) : '';
                    var fechaFinCell = fechaFin ? fechaFin : '<span class="text-muted">No definida</span>';
                    var fechaCulm = (r.Tar_Fecha_Culminacion && r.Tar_Fecha_Culminacion !== '0000-00-00') ? fmtFechaDMA(r.Tar_Fecha_Culminacion) : '';
                    var fechaCulmCell = fechaCulm ? fechaCulm : '<span class="text-muted">-</span>';
                    var pct = r.Ava_Porcentaje != null ? (parseInt(r.Ava_Porcentaje, 10) || 0) : -1;
                    var pctVal = (pct >= 0 && pct <= 100) ? pct : 0;
                    var claseBarra = pctVal >= 70 ? 'alto' : (pctVal >= 40 ? 'medio' : 'bajo');
                    var barraAvance = pct < 0
                        ? '<span class="text-muted">-</span>'
                        : '<div class="barra-progreso-fondo"><div class="barra-progreso-relleno ' + claseBarra + '" style="width:' + pctVal + '%;">' + pctVal + '%</div></div>';
                    var tieneAvance = r.Ava_Cod != null;
                    var accion = (tieneAvance && pctVal === 100)
                        ? '<span class="text-success"><i class="glyphicon glyphicon-ok-circle"></i> Completada</span>'
                        : (tieneAvance
                            ? '<button type="button" class="btn btn-default btn-xs btn-editar-avance" data-tar-cod="' + (r.Tar_Cod || '') + '"><i class="glyphicon glyphicon-edit"></i> Editar avance</button>'
                            : '<button type="button" class="btn btn-primary btn-xs btn-registrar-avance" data-tar-cod="' + (r.Tar_Cod || '') + '"><i class="glyphicon glyphicon-plus"></i> Registrar avance</button>');
                    html += '<tr><td>' + titulo + '</td><td>' + descripcionCell + '</td><td>' + prioridadCell + '</td><td class="col-estado">' + estado + '</td><td>' + fechaIniCell + '</td><td>' + fechaFinCell + '</td><td>' + fechaCulmCell + '</td><td>' + barraAvance + '</td><td>' + accion + '</td></tr>';
                }
            }
        body.innerHTML = html;
        bindAvanceButtons();
    }

    function refreshMisAvances() {
        var body = document.getElementById('bodyMisAvances');
        if (!body) return;
        request('GET', urlBase + '?listarMisTareasAsignadas=1', null, function (resp) {
            sinVinculoFlag = resp && resp.sin_vinculo;
            rowsAll = (resp && resp.rows) ? resp.rows : [];
            var rows = filtrarPorEstado(filtrarPorPeriodo(rowsAll));
            renderTablaYDashboard(rows, sinVinculoFlag, rowsAll.length > 0);
        });
    }

    function bindAvanceButtons() {
        var reg = document.querySelectorAll('#bodyMisAvances .btn-registrar-avance');
        var ed = document.querySelectorAll('#bodyMisAvances .btn-editar-avance');
        var i;
        for (i = 0; i < reg.length; i++) {
            reg[i].onclick = (function (tarCod) {
                return function () { abrirFormAvance(tarCod, false); };
            })(parseInt(reg[i].getAttribute('data-tar-cod'), 10));
        }
        for (i = 0; i < ed.length; i++) {
            ed[i].onclick = (function (tarCod) {
                return function () { abrirFormAvance(tarCod, true); };
            })(parseInt(ed[i].getAttribute('data-tar-cod'), 10));
        }
    }

    function toggleFechaCulminacion(fechaGuardada) {
        var porcInput = document.getElementById('Ava_Porcentaje');
        var grupo = document.getElementById('grupoFechaCulminacion');
        var inputFec = document.getElementById('Tar_Fecha_Culminacion');
        var notaFec = document.getElementById('notaFechaCulminacion');
        var val = porcInput ? parseInt(porcInput.value, 10) : 0;
        if (grupo) {
            if (val === 100) {
                grupo.style.display = 'block';
                if (inputFec) {
                    var hoy = new Date();
                    var hoyStr = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-' + String(hoy.getDate()).padStart(2, '0');
                    if (fechaGuardada && fechaGuardada !== '0000-00-00') {
                        inputFec.value = fechaGuardada;
                    } else {
                        inputFec.value = hoyStr;
                    }
                    inputFec.readOnly = true;
                    inputFec.setAttribute('readonly', 'readonly');
                    if (notaFec) notaFec.textContent = 'Al 100% la fecha de culminación queda fijada a hoy y no se puede editar. La tarea pasará a Finalizada.';
                }
            } else {
                grupo.style.display = 'none';
                if (inputFec) {
                    inputFec.value = '';
                    inputFec.readOnly = false;
                    inputFec.removeAttribute('readonly');
                }
                if (notaFec) notaFec.textContent = 'Solo visible al 100%. Se usará esta fecha o la de hoy y la tarea pasará a Finalizada.';
            }
        }
    }

    var urlAdjuntos = '../adjuntos/avances/';
    var urlAdjuntosTarea = '../adjuntos/tareas/';

    var modalAvance = document.getElementById('modalAvance');
    function cerrarModalAvance() {
        if (modalAvance) modalAvance.classList.remove('modal-avance-abierto');
    }
    function abrirFormAvance(tarCod, esEdicion) {
        var hid = document.getElementById('Tar_Cod_Avance');
        var porc = document.getElementById('Ava_Porcentaje');
        var desc = document.getElementById('Ava_Descripcion');
        var inputFec = document.getElementById('Tar_Fecha_Culminacion');
        var titulo = document.getElementById('tituloFormAvance');
        var fileInput = document.getElementById('adjuntos');
        var adjuntosActuales = document.getElementById('adjuntosActuales');
        if (hid) hid.value = tarCod;
        if (porc) porc.value = 0;
        if (desc) desc.value = '';
        if (inputFec) inputFec.value = '';
        if (fileInput) fileInput.value = '';
        filesToUpload = [];
        renderAdjuntosPreviews();
        if (adjuntosActuales) { adjuntosActuales.innerHTML = ''; adjuntosActuales.style.display = 'none'; }
        var imgTareaCtx = document.getElementById('imagenesTareaContexto');
        var imgTareaLista = document.getElementById('imagenesTareaContextoLista');
        if (imgTareaCtx) { imgTareaCtx.style.display = 'none'; imgTareaLista.innerHTML = ''; }
        if (titulo) titulo.textContent = esEdicion ? 'Editar avance (actualizar porcentaje)' : 'Registrar avance';
        toggleFechaCulminacion(null);
        if (modalAvance) modalAvance.classList.add('modal-avance-abierto');
        request('GET', urlBase + '?listarAdjuntosTarea=1&Tar_Cod=' + encodeURIComponent(tarCod), null, function (adjT) {
            var rows = (adjT && adjT.rows) ? adjT.rows : [];
            if (imgTareaCtx && imgTareaLista && rows.length > 0) {
                var html = '';
                for (var a = 0; a < rows.length; a++) {
                    var ruta = (rows[a].Adj_Ruta || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    var nombre = (rows[a].Adj_Nombre || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    html += '<a href="' + urlAdjuntosTarea + ruta + '" target="_blank" title="' + nombre + '"><img src="' + urlAdjuntosTarea + ruta + '" alt="" style="max-height:80px; max-width:120px; object-fit:contain; border:1px solid #ccc; border-radius:4px;" /></a>';
                }
                imgTareaLista.innerHTML = html;
                imgTareaCtx.style.display = 'block';
            }
        });
        if (esEdicion) {
            request('GET', urlBase + '?avancePorTarea=1&Tar_Cod=' + encodeURIComponent(tarCod), null, function (d) {
                var row = (d && d.row) ? d.row : null;
                if (row && porc) porc.value = parseInt(row.Ava_Porcentaje, 10) || 0;
                if (row && desc) desc.value = row.Ava_Descripcion || '';
                var fechaCulm = (row && row.Tar_Fecha_Culminacion) ? row.Tar_Fecha_Culminacion : null;
                toggleFechaCulminacion(fechaCulm);
                if (row && row.Ava_Cod && adjuntosActuales) {
                    request('GET', urlBase + '?listarAdjuntosAvance=1&Ava_Cod=' + encodeURIComponent(row.Ava_Cod), null, function (adj) {
                        var rows = (adj && adj.rows) ? adj.rows : [];
                        if (rows.length > 0) {
                            var html = '<span class="text-muted small">Capturas actuales:</span> ';
                            for (var a = 0; a < rows.length; a++) {
                                var ruta = (rows[a].Adj_Ruta || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                                var nombre = (rows[a].Adj_Nombre || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                                html += '<a href="' + urlAdjuntos + ruta + '" target="_blank" title="' + nombre + '"><img src="' + urlAdjuntos + ruta + '" alt="" style="max-height:50px; max-width:80px; margin:2px; border:1px solid #ccc; border-radius:4px;" /></a> ';
                            }
                            adjuntosActuales.innerHTML = html;
                            adjuntosActuales.style.display = 'block';
                        }
                    });
                }
            });
        }
    }

    function init() {
        var formAvance = document.getElementById('formAvance');
        var tarAvanceHidden = document.getElementById('Tar_Cod_Avance');
        var btnAvance = document.getElementById('btnGuardarAvance');

        if (btnAvance && formAvance) {
            btnAvance.addEventListener('click', function () {
                if (!tarAvanceHidden || !tarAvanceHidden.value) {
                    alert('Debe seleccionar una tarea.');
                    return;
                }
                if (typeof aud_validaAvance === 'function' && !aud_validaAvance(formAvance)) return;
                var fileInput = document.getElementById('adjuntos');
                var hasFiles = filesToUpload.length > 0;
                var data;
                if (hasFiles) {
                    data = new FormData();
                    data.append('Tar_Cod', tarAvanceHidden.value);
                    data.append('Ava_Porcentaje', document.getElementById('Ava_Porcentaje').value || 0);
                    data.append('Ava_Descripcion', document.getElementById('Ava_Descripcion').value || '');
                    var inputFec = document.getElementById('Tar_Fecha_Culminacion');
                    if (inputFec && inputFec.value) data.append('Tar_Fecha_Culminacion', inputFec.value);
                    data.append('guardarAvance', '1');
                    for (var f = 0; f < filesToUpload.length; f++) data.append('adjuntos[]', filesToUpload[f]);
                } else {
                    data = formToQuery(formAvance) + '&guardarAvance=1';
                }
                request('POST', urlBase, data, function (r) {
                    if (r && r.success) {
                        alert('Avance guardado correctamente.');
                        if (fileInput) fileInput.value = '';
                        filesToUpload = [];
                        renderAdjuntosPreviews();
                        cerrarModalAvance();
                        refreshMisAvances();
                    } else {
                        alert((r && r.message) ? r.message : 'Error al guardar avance.');
                    }
                }, function () {
                    alert('Error de conexión. Compruebe que el servidor esté activo y que la carpeta auditoria/adjuntos/avances exista y tenga permisos de escritura.');
                });
            });
        }
        var btnCancelarAvance = document.getElementById('btnCancelarAvance');
        var btnCerrarModalAvance = document.getElementById('btnCerrarModalAvance');
        if (btnCancelarAvance) {
            btnCancelarAvance.addEventListener('click', cerrarModalAvance);
        }
        if (btnCerrarModalAvance) {
            btnCerrarModalAvance.addEventListener('click', cerrarModalAvance);
        }
        if (modalAvance) {
            modalAvance.addEventListener('click', function (e) {
                if (e.target === modalAvance) cerrarModalAvance();
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modalAvance && modalAvance.classList.contains('modal-avance-abierto')) {
                cerrarModalAvance();
            }
        });
        var btnRefrescarMisAvances = document.getElementById('btnRefrescarMisAvances');
        if (btnRefrescarMisAvances) {
            btnRefrescarMisAvances.addEventListener('click', refreshMisAvances);
        }
        var modalTareaAdicional = document.getElementById('modalTareaAdicional');
        function abrirModalTareaAdicional() {
            filesToUploadAdicional = [];
            renderAdjuntosAdicionalPreviews();
            var hoy = new Date();
            var hoyStr = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-' + String(hoy.getDate()).padStart(2, '0');
            var fecIni = document.getElementById('Tar_Fecha_Inicio_Adicional');
            if (fecIni && !fecIni.value) fecIni.value = hoyStr;
            if (modalTareaAdicional) modalTareaAdicional.classList.add('modal-avance-abierto');
        }
        function cerrarModalTareaAdicional() {
            if (modalTareaAdicional) modalTareaAdicional.classList.remove('modal-avance-abierto');
        }
        var btnToggleCrearTarea = document.getElementById('btnToggleCrearTarea');
        if (btnToggleCrearTarea) {
            btnToggleCrearTarea.addEventListener('click', abrirModalTareaAdicional);
        }
        var btnCancelarTareaAdicional = document.getElementById('btnCancelarTareaAdicional');
        var btnCerrarModalTareaAdicional = document.getElementById('btnCerrarModalTareaAdicional');
        if (btnCancelarTareaAdicional) {
            btnCancelarTareaAdicional.addEventListener('click', function () {
                cerrarModalTareaAdicional();
                filesToUploadAdicional = [];
                renderAdjuntosAdicionalPreviews();
            });
        }
        if (btnCerrarModalTareaAdicional) {
            btnCerrarModalTareaAdicional.addEventListener('click', cerrarModalTareaAdicional);
        }
        if (modalTareaAdicional) {
            modalTareaAdicional.addEventListener('click', function (e) {
                if (e.target === modalTareaAdicional) cerrarModalTareaAdicional();
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modalTareaAdicional && modalTareaAdicional.classList.contains('modal-avance-abierto')) {
                cerrarModalTareaAdicional();
            }
        });
        var formTareaAdicional = document.getElementById('formTareaAdicional');
        if (formTareaAdicional) {
            formTareaAdicional.addEventListener('submit', function (e) {
                e.preventDefault();
                var titulo = document.getElementById('Tar_Titulo_Adicional');
                var fecIni = document.getElementById('Tar_Fecha_Inicio_Adicional');
                if (!titulo || !titulo.value.trim()) {
                    alert('El título es obligatorio.');
                    return;
                }
                if (!fecIni || !fecIni.value) {
                    alert('La fecha de inicio es obligatoria.');
                    return;
                }
                var data;
                if (filesToUploadAdicional.length > 0) {
                    data = new FormData();
                    data.append('crearTareaAdicional', '1');
                    var els = formTareaAdicional.querySelectorAll('input, select, textarea');
                    for (var i = 0; i < els.length; i++) {
                        var el = els[i];
                        if (!el.name || el.disabled || el.type === 'file') continue;
                        if (el.type === 'radio' || el.type === 'checkbox') { if (!el.checked) continue; }
                        data.append(el.name, el.value || '');
                    }
                    for (var f = 0; f < filesToUploadAdicional.length; f++) {
                        data.append('adjuntosTareaAdicional[]', filesToUploadAdicional[f]);
                    }
                } else {
                    data = 'crearTareaAdicional=1&' + formToQuery(formTareaAdicional);
                }
                request('POST', urlBase, data, function (r) {
                    if (r.success) {
                        alert('Tarea creada y asignada correctamente. Aparecerá en su listado.');
                        formTareaAdicional.reset();
                        filesToUploadAdicional = [];
                        renderAdjuntosAdicionalPreviews();
                        cerrarModalTareaAdicional();
                        refreshMisAvances();
                    } else {
                        alert(r.message || 'Error al crear la tarea.');
                    }
                });
            });
        }
        var fileInputAdicional = document.getElementById('adjuntosTareaAdicional');
        if (fileInputAdicional) {
            fileInputAdicional.addEventListener('change', function () {
                var files = fileInputAdicional.files;
                if (files) {
                    for (var i = 0; i < files.length; i++) filesToUploadAdicional.push(files[i]);
                    fileInputAdicional.value = '';
                }
                renderAdjuntosAdicionalPreviews();
            });
        }
        var zonaPegarAdicional = document.getElementById('zonaPegarTareaAdicional');
        if (zonaPegarAdicional) {
            zonaPegarAdicional.addEventListener('paste', handlePasteAdicional);
            zonaPegarAdicional.addEventListener('click', function () { zonaPegarAdicional.focus(); });
            zonaPegarAdicional.addEventListener('dragover', handleDragOverAdicional);
            zonaPegarAdicional.addEventListener('dragleave', handleDragLeaveAdicional);
            zonaPegarAdicional.addEventListener('drop', handleDropAdicional);
        }
        if (formTareaAdicional) formTareaAdicional.addEventListener('paste', handlePasteAdicional);
        function aplicarFiltrosYRenderizar() {
            var rows = filtrarPorEstado(filtrarPorPeriodo(rowsAll));
            renderTablaYDashboard(rows, sinVinculoFlag, rowsAll.length > 0);
        }
        var selPeriodo = document.getElementById('avancesPeriodo');
        if (selPeriodo) selPeriodo.addEventListener('change', aplicarFiltrosYRenderizar);
        var selEstado = document.getElementById('avancesEstado');
        if (selEstado) selEstado.addEventListener('change', aplicarFiltrosYRenderizar);
        var porcInput = document.getElementById('Ava_Porcentaje');
        if (porcInput) {
            porcInput.addEventListener('input', function () { toggleFechaCulminacion(null); });
            porcInput.addEventListener('change', function () { toggleFechaCulminacion(null); });
        }
        var fileInputAdj = document.getElementById('adjuntos');
        console.log('fileInputAdj encontrado:', fileInputAdj);
        if (fileInputAdj) {
            fileInputAdj.addEventListener('change', function () {
                console.log('Event listener change disparado');
                var files = fileInputAdj.files;
                console.log('Archivos seleccionados:', files ? files.length : 0);
                if (files) {
                    // for (var i = 0; i < files.length; i++) filesToUpload.push(files[i]);
                    // fileInputAdj.value = '';
                    for (var i = 0; i < files.length; i++) {
                        console.log('Agregando archivo:', files[i].name);
                        filesToUpload.push(files[i]);
                    }
                    fileInputAdj.value = '';
                }
                console.log('Total archivos en filesToUpload:', filesToUpload.length);
                renderAdjuntosPreviews();
            });
        }
        var zonaPegar = document.getElementById('zonaPegarImagen');
        if (zonaPegar) {
            zonaPegar.addEventListener('paste', handlePasteImagen);
            zonaPegar.addEventListener('click', function () { zonaPegar.focus(); });
            zonaPegar.addEventListener('dragover', handleDragOver);
            zonaPegar.addEventListener('dragleave', handleDragLeave);
            zonaPegar.addEventListener('drop', handleDrop);
        }
        if (formAvance) formAvance.addEventListener('paste', handlePasteImagen);
        refreshMisAvances();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
</body>
</html>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>
