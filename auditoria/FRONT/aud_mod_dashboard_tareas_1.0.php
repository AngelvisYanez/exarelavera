<?php
/** Para depurar error 500: agregue ?debug=1 a la URL para ver el mensaje de error */
if (!empty($_GET['debug'])) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
/**
 * Dashboard de Control de Personal - Gestión de Tareas
 * Módulo: dashboard_tareas | Área: aud | Versión: 1.0
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
$Ses_Suc_Cod = isset($Ses_Suc_Cod) ? intval($Ses_Suc_Cod) : 0;
$Ses_Usu_Cod = isset($Ses_Usu_Cod) ? intval($Ses_Usu_Cod) : 0;

// ----- Ajax: Grid paginado de tareas (caso 21) -----
if (isset($tareasGridAjax)) {
    $data = array_merge($_GET, array('Emp_Cod' => $Ses_Emp_Cod));
    $obBD_con1->getPageGridJson(21, $data, $obBD_conexion);
}

// ----- Ajax: Obtener una tarea por Tar_Cod (para editar) -----
if (!empty($_REQUEST['tareaPorCod'])) {
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    if ($tarCod <= 0) {
        echo json_encode(array('row' => null));
        exit;
    }
    $row = $obBD_con1->getRowConsulta(38, array('Tar_Cod' => $tarCod), $obBD_conexion);
    if (!is_array($row)) $row = null;
    utf8_encode_deep($row);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('row' => $row));
    exit;
}

// ----- Ajax: Guardar tarea (insert o update si Tar_Cod viene informado) -----
$req_tarea = array_merge($_GET, $_POST);
if (!empty($req_tarea['guardarTarea'])) {
    $resp = array('success' => false);
    $req = $req_tarea;
    $tiene_archivos = !empty($_FILES['adjuntosTarea']) && isset($_FILES['adjuntosTarea']['name']);
    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }
    $tarCod = isset($req['Tar_Cod']) ? intval($req['Tar_Cod']) : 0;
    $titulo = isset($req['Tar_Titulo']) ? trim($req['Tar_Titulo']) : '';
    $desc = isset($req['Tar_Descripcion']) ? trim($req['Tar_Descripcion']) : '';
    // Asegurar UTF-8 en título y descripción (evita que se corte en tildes/guía/etc.)
    $normalizar_utf8 = function ($s) {
        if ($s === '') return '';
        // Si hay bytes inválidos en UTF-8, reemplazar para no truncar el resto
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
        echo json_encode($resp);
        exit;
    }
    $estado = (isset($req['Tar_Estado']) && trim($req['Tar_Estado']) !== '') ? trim($req['Tar_Estado']) : 'Pendiente';
    $conn = $obBD_conexion->conexion;
    mysqli_set_charset($conn, 'utf8');
    $titulo_safe = ($conn && function_exists('mysqli_real_escape_string')) ? mysqli_real_escape_string($conn, $titulo) : addslashes($titulo);
    $desc_safe = ($conn && function_exists('mysqli_real_escape_string')) ? mysqli_real_escape_string($conn, $desc) : addslashes($desc);
    if ($tarCod > 0) {
        $par = array('Tar_Cod' => $tarCod, 'Tar_Titulo_safe' => $titulo_safe, 'Tar_Descripcion_safe' => $desc_safe, 'Tar_Prioridad' => $prioridad, 'Tar_Fecha_Inicio' => $fecIni, 'Tar_Fecha_Fin' => $fecFin, 'Tar_Estado' => $estado);
        $obBD_con1->operacionobBD(39, $par, $obBD_conexion);
        $resp['success'] = ($obBD_con1->Error == 0);
        if ($resp['success']) $resp['Tar_Cod'] = $tarCod;
    } else {
        $par = array('Tar_Titulo_safe' => $titulo_safe, 'Tar_Descripcion_safe' => $desc_safe, 'Tar_Prioridad' => $prioridad, 'Tar_Fecha_Inicio' => $fecIni, 'Tar_Fecha_Fin' => $fecFin, 'Tar_Estado' => $estado, 'Usu_Creador' => $Ses_Usu_Cod, 'Emp_Cod' => $Ses_Emp_Cod);
        $obBD_con1->operacionobBD(1, $par, $obBD_conexion);
        $resp['success'] = ($obBD_con1->Error == 0);
        if ($resp['success']) $resp['Tar_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
    }
    if ($resp['success'] && $resp['Tar_Cod'] > 0 && $tiene_archivos) {
        $dirAdjuntos = __DIR__ . '/../adjuntos/tareas';
        if (!is_dir($dirAdjuntos)) @mkdir($dirAdjuntos, 0755, true);
        if (is_dir($dirAdjuntos) && is_writable($dirAdjuntos)) {
            $allowTypes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
            $allowExt = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $maxSize = 5 * 1024 * 1024;
            $files = $_FILES['adjuntosTarea'];
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
                $safeName = $resp['Tar_Cod'] . '_' . time() . '_' . $idx . '.' . $ext;
                $destino = $dirAdjuntos . '/' . $safeName;
                if (move_uploaded_file($tmpNames[$idx], $destino)) {
                    $obBD_con1->operacionobBD(42, array('Tar_Cod' => $resp['Tar_Cod'], 'Adj_Nombre' => basename($origName), 'Adj_Ruta' => $safeName, 'Adj_Fecha' => $fecAdj), $obBD_conexion);
                }
            }
        }
    }
    if (!$resp['success']) {
        $resp['message'] = $obBD_con1->MsgError;
    }
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Asignar tarea a empleado (usa Per_Cod de tabla personal) -----
if (isset($asignarTarea)) {
    $resp = array('success' => false);
    $tarCod = isset($Tar_Cod) ? intval($Tar_Cod) : 0;
    $perCod = isset($Per_Cod) ? intval($Per_Cod) : 0;
    if ($tarCod <= 0 || $perCod <= 0) {
        $resp['message'] = 'Debe indicar tarea y empleado.';
        echo json_encode($resp);
        exit;
    }
    // Evitar asignación duplicada: misma tarea al mismo empleado
    $existe = $obBD_con1->getRowConsulta(12, array('Tar_Cod' => $tarCod, 'Per_Cod' => $perCod), $obBD_conexion);
    if (!empty($existe)) {
        $resp['message'] = 'Esta tarea ya está asignada a este empleado.';
        echo json_encode($resp);
        exit;
    }
    $par = array('Tar_Cod' => $tarCod, 'Per_Cod' => $perCod, 'Tas_Fecha_Asignacion' => date('Y-m-d H:i:s'));
    $obBD_con1->operacionobBD(2, $par, $obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $obBD_con1->operacionobBD(25, array('Tar_Cod' => $tarCod), $obBD_conexion);
    }
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) {
        $resp['message'] = $obBD_con1->MsgError;
    }
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Registrar o actualizar avance (un avance por tarea: INSERT o UPDATE) -----
if (isset($guardarAvance)) {
    $resp = array('success' => false);
    $tarCod = isset($Tar_Cod) ? intval($Tar_Cod) : 0;
    $desc = isset($Ava_Descripcion) ? trim($Ava_Descripcion) : '';
    $porc = isset($Ava_Porcentaje) ? min(100, max(0, intval($Ava_Porcentaje))) : 0;
    $fecCulminacion = isset($Tar_Fecha_Culminacion) ? trim($Tar_Fecha_Culminacion) : '';
    if ($tarCod <= 0) {
        $resp['message'] = 'Debe indicar la tarea.';
        echo json_encode($resp);
        exit;
    }
    $fec = date('Y-m-d H:i:s');
    $existe = $obBD_con1->getRowConsulta(13, array('Tar_Cod' => $tarCod), $obBD_conexion);
    if (!empty($existe) && isset($existe['Ava_Cod'])) {
        $par = array('Ava_Cod' => $existe['Ava_Cod'], 'Ava_Porcentaje' => $porc, 'Ava_Descripcion' => $desc, 'Ava_Fecha' => $fec);
        $obBD_con1->operacionobBD(14, $par, $obBD_conexion);
    } else {
        $par = array('Tar_Cod' => $tarCod, 'Usu_Cod' => $Ses_Usu_Cod, 'Ava_Descripcion' => $desc, 'Ava_Porcentaje' => $porc, 'Ava_Fecha' => $fec);
        $obBD_con1->operacionobBD(3, $par, $obBD_conexion);
    }
    $resp['success'] = ($obBD_con1->Error == 0);
    if ($resp['success']) {
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
    }
    if (!$resp['success']) {
        $resp['message'] = $obBD_con1->MsgError;
    }
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Actualizar estado de tarea -----
if (isset($actualizarEstado)) {
    $resp = array('success' => false);
    $tarCod = isset($Tar_Cod) ? intval($Tar_Cod) : 0;
    $estado = isset($Tar_Estado) ? trim($Tar_Estado) : '';
    if ($tarCod <= 0 || !in_array($estado, array('Pendiente', 'En Proceso', 'Pausada', 'Finalizada'))) {
        $resp['message'] = 'Datos inválidos.';
        echo json_encode($resp);
        exit;
    }
    $par = array('Tar_Cod' => $tarCod, 'Tar_Estado' => $estado);
    $obBD_con1->operacionobBD(4, $par, $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) {
        $resp['message'] = $obBD_con1->MsgError;
    }
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Indicadores dashboard general (con filtro período opcional) -----
if (isset($dashboardIndicadores)) {
    $fecIni = isset($_REQUEST['Fecha_Ini']) ? trim($_REQUEST['Fecha_Ini']) : '';
    $fecFin = isset($_REQUEST['Fecha_Fin']) ? trim($_REQUEST['Fecha_Fin']) : '';
    $par = array('Emp_Cod' => $Ses_Emp_Cod);
    if ($fecIni !== '' || $fecFin !== '') {
        $par['Fecha_Ini'] = $fecIni;
        $par['Fecha_Fin'] = $fecFin;
        $row = $obBD_con1->getRowConsulta(33, $par, $obBD_conexion);
    } else {
        $row = $obBD_con1->getRowConsulta(8, $par, $obBD_conexion);
    }
    $total = isset($row['Total_Tareas']) ? intval($row['Total_Tareas']) : 0;
    $completadas = isset($row['Completadas']) ? intval($row['Completadas']) : 0;
    $atrasadas = isset($row['Atrasadas']) ? intval($row['Atrasadas']) : 0;
    $pctCompletadas = $total > 0 ? round(100 * $completadas / $total, 1) : 0;
    $pctAtrasadas = $total > 0 ? round(100 * $atrasadas / $total, 1) : 0;
    $rendimiento = $total > 0 ? round(100 * $completadas / $total, 1) : 0;
    $resp = array('Total_Tareas' => $total, 'Completadas' => $completadas, 'Atrasadas' => $atrasadas, 'Pct_Completadas' => $pctCompletadas, 'Pct_Atrasadas' => $pctAtrasadas, 'Rendimiento_Promedio' => $rendimiento);
    utf8_encode_deep($resp);
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Listar tareas para detalle KPI (tipo: all|completadas|atrasadas) -----
if (!empty($_REQUEST['listarTareasKpi'])) {
    $tipo = isset($_REQUEST['Tipo']) ? trim($_REQUEST['Tipo']) : 'all';
    $fecIni = isset($_REQUEST['Fecha_Ini']) ? trim($_REQUEST['Fecha_Ini']) : '';
    $fecFin = isset($_REQUEST['Fecha_Fin']) ? trim($_REQUEST['Fecha_Fin']) : '';
    $par = array('Emp_Cod' => $Ses_Emp_Cod, 'Tipo' => $tipo, 'Fecha_Ini' => $fecIni, 'Fecha_Fin' => $fecFin);
    $arr = $obBD_con1->getArrayConsulta(31, $par, $obBD_conexion);
    if (!is_array($arr)) $arr = array();
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Tareas que requieren atención (con avance %) -----
if (!empty($_REQUEST['listarTareasAtencion'])) {
    $arr = $obBD_con1->getArrayConsulta(32, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    if (!is_array($arr)) $arr = array();
    $arrAv = $obBD_con1->getArrayConsulta(15, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    $avances = array();
    if (is_array($arrAv)) {
        foreach ($arrAv as $r) {
            $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
            if ($tc > 0 && !isset($avances[$tc])) $avances[$tc] = $r;
        }
    }
    foreach ($arr as &$r) {
        $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
        $r['Ava_Porcentaje'] = isset($avances[$tc]['Ava_Porcentaje']) ? $avances[$tc]['Ava_Porcentaje'] : null;
    }
    unset($r);
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Listar tareas para combo -----
if (isset($listarTareas)) {
    $arr = $obBD_con1->getArrayConsulta(9, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    utf8_encode_deep($arr);
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Listar tareas disponibles para asignar (sin asignación activa) -----
if (!empty($_REQUEST['listarTareasDisponibles'])) {
    $arr = $obBD_con1->getArrayConsulta(37, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    if (!is_array($arr)) $arr = array();
    utf8_encode_deep($arr);
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Listar empleados para asignación (desde personal, solo activos) -----
if (isset($listarEmpleados)) {
    $arr = $obBD_con1->getArrayConsulta(10, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    utf8_encode_deep($arr);
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Listar asignaciones (cuadro debajo del formulario de asignación) -----
if (!empty($_REQUEST['listarAsignaciones'])) {
    // Usar case 23 (sin Tar_Fecha_Culminacion) por si la migración de fecha culminación no se ejecutó
    $arr = $obBD_con1->getArrayConsulta(23, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    if (!is_array($arr)) {
        $arr = array();
    }
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Eliminar tarea (soft delete) -----
if (isset($eliminarTarea)) {
    $resp = array('success' => false);
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    if ($tarCod <= 0) {
        $resp['message'] = 'Tarea no indicada.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(36, array('Tar_Cod' => $tarCod), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) {
        $resp['message'] = $obBD_con1->MsgError;
    }
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Eliminar asignación (soft delete) -----
if (isset($eliminarAsignacion)) {
    $resp = array('success' => false);
    $tasCod = isset($Tas_Cod) ? intval($Tas_Cod) : 0;
    if ($tasCod <= 0) {
        $resp['message'] = 'Asignación no indicada.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(18, array('Tas_Cod' => $tasCod), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) {
        $resp['message'] = $obBD_con1->MsgError;
    }
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Mis tareas asignadas (formulario de avances del usuario logueado) -----
if (isset($listarMisTareasAsignadas)) {
    $rowPer = $obBD_con1->getRowConsulta(16, array('Usu_Cod' => $Ses_Usu_Cod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    $perCod = isset($rowPer['Per_Cod']) ? intval($rowPer['Per_Cod']) : 0;
    $resp = array('rows' => array(), 'sin_vinculo' => false);
    if ($perCod <= 0) {
        $resp['sin_vinculo'] = true;
        utf8_encode_deep($resp);
        echo json_encode($resp);
        exit;
    }
    $asig = $obBD_con1->getArrayConsulta(17, array('Per_Cod' => $perCod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    $arrAv = $obBD_con1->getArrayConsulta(15, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
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
    echo json_encode($resp);
    exit;
}

// ----- Ajax: Avances por tarea (historial) -----
if (!empty($_REQUEST['avancesPorTarea'])) {
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    if ($tarCod <= 0) {
        echo json_encode(array('rows' => array()));
        exit;
    }
    $arr = $obBD_con1->getArrayConsulta(6, array('Tar_Cod' => $tarCod), $obBD_conexion);
    utf8_encode_deep($arr);
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Un avance por tarea (para editar en formulario de avances) -----
if (!empty($_REQUEST['avancePorTarea'])) {
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    if ($tarCod <= 0) {
        echo json_encode(array('row' => null));
        exit;
    }
    $row = $obBD_con1->getRowConsulta(13, array('Tar_Cod' => $tarCod), $obBD_conexion);
    utf8_encode_deep($row);
    echo json_encode(array('row' => $row));
    exit;
}

// ----- Ajax: Listar adjuntos (capturas) de un avance -----
if (!empty($_REQUEST['listarAdjuntosAvance'])) {
    $avaCod = isset($_REQUEST['Ava_Cod']) ? intval($_REQUEST['Ava_Cod']) : 0;
    $arr = array();
    if ($avaCod > 0) {
        $arr = $obBD_con1->getArrayConsulta(41, array('Ava_Cod' => $avaCod), $obBD_conexion);
        if (!is_array($arr)) $arr = array();
    }
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Ajax: Listar adjuntos (imágenes) de una tarea -----
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

// ----- Ajax: Todas las tareas asignadas con avance (para reportes) -----
$req_asig_av = array_merge($_GET, $_POST);
if (!empty($req_asig_av['listarAsignacionesConAvance'])) {
    $par_asig = array('Emp_Cod' => $Ses_Emp_Cod);
    if (!empty($req_asig_av['Per_Cod']) && intval($req_asig_av['Per_Cod']) > 0) {
        $par_asig['Per_Cod'] = intval($req_asig_av['Per_Cod']);
    }
    $asig = $obBD_con1->getArrayConsulta(11, $par_asig, $obBD_conexion);
    if (!is_array($asig)) {
        $asig = $obBD_con1->getArrayConsulta(30, $par_asig, $obBD_conexion);
    }
    if (!is_array($asig)) {
        $asig = array();
    }
    $fecIni = isset($req_asig_av['Fecha_Ini']) ? trim($req_asig_av['Fecha_Ini']) : '';
    $fecFin = isset($req_asig_av['Fecha_Fin']) ? trim($req_asig_av['Fecha_Fin']) : '';
    if ($fecIni !== '' || $fecFin !== '') {
        $asig = array_filter($asig, function ($r) use ($fecIni, $fecFin) {
            $fec = isset($r['Tar_Fecha_Inicio']) ? trim($r['Tar_Fecha_Inicio']) : '';
            if ($fec === '' || $fec === '0000-00-00') return false;
            if ($fecIni !== '' && $fec < $fecIni) return false;
            if ($fecFin !== '' && $fec > $fecFin) return false;
            return true;
        });
        $asig = array_values($asig);
    }
    $arrAv = $obBD_con1->getArrayConsulta(15, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
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
    }
    unset($r);
    utf8_encode_deep($asig);
    echo json_encode(array('rows' => $asig));
    exit;
}

// ----- Ajax: Métricas de rendimiento (ranking) - calculadas desde asignaciones -----
if (!empty($_REQUEST['metricasRendimiento'])) {
    // Intentar Case 11 (con Tar_Fecha_Culminacion) para atrasadas; fallback a Case 30
    $asig = $obBD_con1->getArrayConsulta(11, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    if (!is_array($asig)) {
        $asig = $obBD_con1->getArrayConsulta(30, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    }
    if (!is_array($asig)) {
        $asig = array();
    }
    $metricas = array();
    foreach ($asig as $r) {
        $perCod = isset($r['Per_Cod']) ? intval($r['Per_Cod']) : 0;
        $nombre = isset($r['Empleado_Nombre']) ? trim($r['Empleado_Nombre']) : '';
        $estado = isset($r['Tar_Estado']) ? $r['Tar_Estado'] : '';
        $fecFin = isset($r['Tar_Fecha_Fin']) ? trim($r['Tar_Fecha_Fin']) : '';
        $fecCulm = isset($r['Tar_Fecha_Culminacion']) ? trim($r['Tar_Fecha_Culminacion']) : '';
        $key = $perCod . '|' . $nombre;
        if (!isset($metricas[$key])) {
            $metricas[$key] = array('Per_Cod' => $perCod, 'Nombre' => $nombre, 'Total_Tareas' => 0, 'Tareas_Completadas' => 0, 'Tareas_Atrasadas' => 0);
        }
        $metricas[$key]['Total_Tareas']++;
        if ($estado === 'Finalizada') {
            $metricas[$key]['Tareas_Completadas']++;
            // Atrasada si culminación real > fin tentativa
            if ($fecFin !== '' && $fecFin !== '0000-00-00' && $fecCulm !== '' && $fecCulm !== '0000-00-00' && $fecCulm > $fecFin) {
                $metricas[$key]['Tareas_Atrasadas']++;
            }
        } elseif ($fecFin !== '' && $fecFin !== '0000-00-00' && $fecFin < date('Y-m-d')) {
            // En proceso pero ya pasó la fecha tentativa
            $metricas[$key]['Tareas_Atrasadas']++;
        }
    }
    $arr = array_values($metricas);
    foreach ($arr as &$r) {
        $tot = isset($r['Total_Tareas']) ? intval($r['Total_Tareas']) : 0;
        $com = isset($r['Tareas_Completadas']) ? intval($r['Tareas_Completadas']) : 0;
        $r['Rendimiento_Porcentaje'] = $tot > 0 ? round(100 * $com / $tot, 1) : 0;
    }
    unset($r);
    usort($arr, function ($a, $b) {
        return strcmp($a['Nombre'], $b['Nombre']);
    });
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// ----- Reporte PDF de tareas en rango de fechas -----
if (!empty($_REQUEST['reporteTareasPDF'])) {
    $par_asig = array('Emp_Cod' => $Ses_Emp_Cod);
    if (!empty($_REQUEST['Per_Cod']) && intval($_REQUEST['Per_Cod']) > 0) {
        $par_asig['Per_Cod'] = intval($_REQUEST['Per_Cod']);
    }
    $par_asig['Fecha_Ini'] = isset($_REQUEST['Fecha_Ini']) ? trim($_REQUEST['Fecha_Ini']) : '';
    $par_asig['Fecha_Fin'] = isset($_REQUEST['Fecha_Fin']) ? trim($_REQUEST['Fecha_Fin']) : '';
    $asig = $obBD_con1->getArrayConsulta(11, $par_asig, $obBD_conexion);
    if (!is_array($asig)) {
        $asig = $obBD_con1->getArrayConsulta(30, $par_asig, $obBD_conexion);
    }
    if (!is_array($asig)) $asig = array();
    $fecIni = $par_asig['Fecha_Ini'];
    $fecFin = $par_asig['Fecha_Fin'];
    if ($fecIni !== '' || $fecFin !== '') {
        $asig = array_filter($asig, function ($r) use ($fecIni, $fecFin) {
            $fec = isset($r['Tar_Fecha_Inicio']) ? trim($r['Tar_Fecha_Inicio']) : '';
            if ($fec === '' || $fec === '0000-00-00') return false;
            if ($fecIni !== '' && $fec < $fecIni) return false;
            if ($fecFin !== '' && $fec > $fecFin) return false;
            return true;
        });
        $asig = array_values($asig);
    }
    $arrAv = $obBD_con1->getArrayConsulta(15, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    $avances = array();
    foreach ($arrAv as $r) {
        $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
        if ($tc > 0 && !isset($avances[$tc])) $avances[$tc] = $r;
    }
    foreach ($asig as &$r) {
        $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
        $r['Ava_Porcentaje'] = isset($avances[$tc]['Ava_Porcentaje']) ? $avances[$tc]['Ava_Porcentaje'] : null;
    }
    unset($r);
    utf8_encode_deep($asig);
    $titulo = 'Reporte de tareas realizadas';
    $subtitulo = ($fecIni !== '' || $fecFin !== '') ? 'Período: ' . ($fecIni ?: '...') . ' a ' . ($fecFin ?: '...') : 'Todos los períodos';
    $html = '<h2>' . htmlspecialchars($titulo) . '</h2><p>' . htmlspecialchars($subtitulo) . '</p>';
    $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:10px;">';
    $html .= '<thead><tr style="background:linear-gradient(135deg, #72A1CF 0%, #8EB7DD 100%); color:white;"><th>Tarea</th><th>Descripción</th><th>Empleado</th><th>Estado</th><th>Inicio</th><th>Fin tentativa</th><th>Culminación</th><th>Avance %</th><th>Fecha asignación</th></tr></thead><tbody>';
    foreach ($asig as $r) {
        $tit = htmlspecialchars(isset($r['Tar_Titulo']) ? $r['Tar_Titulo'] : '');
        $desc = htmlspecialchars(isset($r['Tar_Descripcion']) ? $r['Tar_Descripcion'] : '-');
        $emp = htmlspecialchars(isset($r['Empleado_Nombre']) ? $r['Empleado_Nombre'] : '');
        $est = htmlspecialchars(isset($r['Tar_Estado']) ? $r['Tar_Estado'] : '');
        $ini = (isset($r['Tar_Fecha_Inicio']) ? $r['Tar_Fecha_Inicio'] : '') !== '' && (isset($r['Tar_Fecha_Inicio']) ? $r['Tar_Fecha_Inicio'] : '') !== '0000-00-00' ? $r['Tar_Fecha_Inicio'] : '-';
        $fin = (isset($r['Tar_Fecha_Fin']) ? $r['Tar_Fecha_Fin'] : '') !== '' && (isset($r['Tar_Fecha_Fin']) ? $r['Tar_Fecha_Fin'] : '') !== '0000-00-00' ? $r['Tar_Fecha_Fin'] : '-';
        $culm = isset($r['Tar_Fecha_Culminacion']) && $r['Tar_Fecha_Culminacion'] !== '' && $r['Tar_Fecha_Culminacion'] !== '0000-00-00' ? $r['Tar_Fecha_Culminacion'] : '-';
        $pct = isset($r['Ava_Porcentaje']) ? (intval($r['Ava_Porcentaje']) . '%') : '-';
        $tasFec = isset($r['Tas_Fecha_Asignacion']) ? $r['Tas_Fecha_Asignacion'] : '-';
        $html .= '<tr><td>' . $tit . '</td><td>' . $desc . '</td><td>' . $emp . '</td><td>' . $est . '</td><td>' . $ini . '</td><td>' . $fin . '</td><td>' . $culm . '</td><td>' . $pct . '</td><td>' . $tasFec . '</td></tr>';
    }
    $html .= '</tbody></table>';
    ini_set('memory_limit', '64M');
    include(__DIR__ . '/../../Librerias/MPDF57/mpdf.php');
    $mpdf = new \Mpdf\Mpdf(['mode' => 'c', 'format' => 'A4-L', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10, 'margin_header' => 6, 'margin_footer' => 6]);
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->WriteHTML('<meta charset="UTF-8">' . $html, 2);
    $nombre = 'Reporte_tareas_' . date('Y-m-d_His') . '.pdf';
    $mpdf->Output($nombre, 'D');
    exit;
}

// ----- Reporte Excel de tareas en rango de fechas -----
if (!empty($_REQUEST['reporteTareasExcel'])) {
    $par_asig = array('Emp_Cod' => $Ses_Emp_Cod);
    if (!empty($_REQUEST['Per_Cod']) && intval($_REQUEST['Per_Cod']) > 0) {
        $par_asig['Per_Cod'] = intval($_REQUEST['Per_Cod']);
    }
    $par_asig['Fecha_Ini'] = isset($_REQUEST['Fecha_Ini']) ? trim($_REQUEST['Fecha_Ini']) : '';
    $par_asig['Fecha_Fin'] = isset($_REQUEST['Fecha_Fin']) ? trim($_REQUEST['Fecha_Fin']) : '';
    $asig = $obBD_con1->getArrayConsulta(11, $par_asig, $obBD_conexion);
    if (!is_array($asig)) {
        $asig = $obBD_con1->getArrayConsulta(30, $par_asig, $obBD_conexion);
    }
    if (!is_array($asig)) $asig = array();
    $fecIni = $par_asig['Fecha_Ini'];
    $fecFin = $par_asig['Fecha_Fin'];
    if ($fecIni !== '' || $fecFin !== '') {
        $asig = array_filter($asig, function ($r) use ($fecIni, $fecFin) {
            $fec = isset($r['Tar_Fecha_Inicio']) ? trim($r['Tar_Fecha_Inicio']) : '';
            if ($fec === '' || $fec === '0000-00-00') return false;
            if ($fecIni !== '' && $fec < $fecIni) return false;
            if ($fecFin !== '' && $fec > $fecFin) return false;
            return true;
        });
        $asig = array_values($asig);
    }
    $arrAv = $obBD_con1->getArrayConsulta(15, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    $avances = array();
    foreach ($arrAv as $r) {
        $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
        if ($tc > 0 && !isset($avances[$tc])) $avances[$tc] = $r;
    }
    foreach ($asig as &$r) {
        $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
        $r['Ava_Porcentaje'] = isset($avances[$tc]['Ava_Porcentaje']) ? $avances[$tc]['Ava_Porcentaje'] : null;
    }
    unset($r);
    utf8_encode_deep($asig);
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Reporte_tareas_' . date('Y-m-d_His') . '.xls"');
    echo "\xEF\xBB\xBF";
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1" cellpadding="4" cellspacing="0"><thead><tr style="background:linear-gradient(135deg, #72A1CF 0%, #8EB7DD 100%); color:white;">';
    echo '<th>Tarea</th><th>Descripción</th><th>Empleado</th><th>Estado</th><th>Inicio</th><th>Fin tentativa</th><th>Culminación</th><th>Avance %</th><th>Fecha asignación</th></tr></thead><tbody>';
    foreach ($asig as $r) {
        $tit = htmlspecialchars(isset($r['Tar_Titulo']) ? $r['Tar_Titulo'] : '');
        $desc = htmlspecialchars(isset($r['Tar_Descripcion']) ? $r['Tar_Descripcion'] : '-');
        $emp = htmlspecialchars(isset($r['Empleado_Nombre']) ? $r['Empleado_Nombre'] : '');
        $est = htmlspecialchars(isset($r['Tar_Estado']) ? $r['Tar_Estado'] : '');
        $ini = (isset($r['Tar_Fecha_Inicio']) ? $r['Tar_Fecha_Inicio'] : '') !== '' && (isset($r['Tar_Fecha_Inicio']) ? $r['Tar_Fecha_Inicio'] : '') !== '0000-00-00' ? $r['Tar_Fecha_Inicio'] : '-';
        $fin = (isset($r['Tar_Fecha_Fin']) ? $r['Tar_Fecha_Fin'] : '') !== '' && (isset($r['Tar_Fecha_Fin']) ? $r['Tar_Fecha_Fin'] : '') !== '0000-00-00' ? $r['Tar_Fecha_Fin'] : '-';
        $culm = isset($r['Tar_Fecha_Culminacion']) && $r['Tar_Fecha_Culminacion'] !== '' && $r['Tar_Fecha_Culminacion'] !== '0000-00-00' ? $r['Tar_Fecha_Culminacion'] : '-';
        $pct = isset($r['Ava_Porcentaje']) ? (intval($r['Ava_Porcentaje']) . '%') : '-';
        $tasFec = isset($r['Tas_Fecha_Asignacion']) ? $r['Tas_Fecha_Asignacion'] : '-';
        echo '<tr><td>' . $tit . '</td><td>' . $desc . '</td><td>' . $emp . '</td><td>' . $est . '</td><td>' . $ini . '</td><td>' . $fin . '</td><td>' . $culm . '</td><td>' . $pct . '</td><td>' . $tasFec . '</td></tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

// Cargar datos iniciales para combos (tareas y empleados) en PHP para primera carga
$lista_tareas = $obBD_con1->getArrayConsulta(9, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
$lista_tareas_disponibles = $obBD_con1->getArrayConsulta(37, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
if (!is_array($lista_tareas_disponibles)) $lista_tareas_disponibles = array();
$lista_empleados = $obBD_con1->getArrayConsulta(10, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
$indicadores = $obBD_con1->getRowConsulta(8, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
$total_tareas = isset($indicadores['Total_Tareas']) ? intval($indicadores['Total_Tareas']) : 0;
$completadas = isset($indicadores['Completadas']) ? intval($indicadores['Completadas']) : 0;
$atrasadas = isset($indicadores['Atrasadas']) ? intval($indicadores['Atrasadas']) : 0;
$pct_completadas = $total_tareas > 0 ? round(100 * $completadas / $total_tareas, 1) : 0;
$pct_atrasadas = $total_tareas > 0 ? round(100 * $atrasadas / $total_tareas, 1) : 0;
$rendimiento_promedio = $total_tareas > 0 ? round(100 * $completadas / $total_tareas, 1) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <TITLE><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'EXA'; ?> - Dashboard Tareas</TITLE>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <?php require_once('../../mascaras/model1/estilos/estilos.php'); ?>
    <link href="aud_zoom.css" rel="stylesheet" type="text/css">
    <?php require_once('../../mascaras/model1/estilos/jqgrid5.php'); ?>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/aud_val_dashboard_tareas_1.0.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/aud_par_dashboard_tareas_1.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        /* Colores alineados con aud_mod_despacho_operativo (mismo programa) */
        .dashboard-tareas-modulo .tab-content { padding: 20px; background: #E8F0F7; }
        .dashboard-tareas-modulo .form-group { margin-bottom: 10px; }
        #loader { display: none !important; }

        .dashboard-tareas-modulo .exa-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 50%, #5A9BD4 100%) !important;
            color: white !important;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(44,93,148,0.3);
            margin-bottom: 20px;
        }
        .dashboard-tareas-modulo .exa-header h3 { margin: 0; font-size: 18px; font-weight: 600; letter-spacing: 0.3px; }

        .dashboard-tareas-modulo .dashboard-container { padding: 0; background: transparent; }

        .dashboard-tareas-modulo .tabs-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .dashboard-tareas-modulo .nav-tabs {
            border-bottom: 2px solid #cbd5e1 !important;
            margin: 0;
            padding: 10px 16px 0 16px;
            background: #DEE7EF !important;
            display: flex;
            flex-wrap: nowrap;
            list-style: none;
        }
        .dashboard-tareas-modulo .nav-tabs > li { margin-bottom: -2px; margin-right: 4px; flex-shrink: 0; }
        .dashboard-tareas-modulo .nav-tabs > li > a {
            display: inline-block;
            color: #475569 !important;
            font-weight: 600;
            font-size: 13px;
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
            background: #e2e8f0 !important;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .dashboard-tareas-modulo .nav-tabs > li > a:hover { background: #DEE7EF !important; color: #2C5D94 !important; border-color: #cbd5e1; }
        .dashboard-tareas-modulo .nav-tabs > li.active > a,
        .dashboard-tareas-modulo .nav-tabs > li.active > a:hover,
        .dashboard-tareas-modulo .nav-tabs > li.active > a:focus {
            background: #3d7bb8 !important;
            color: white !important;
            border-color: #2C5D94;
            border-bottom: 2px solid #2C5D94;
            margin-bottom: -2px;
        }

        .dashboard-tareas-modulo .config-card {
            background: white;
            border: none;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s ease;
        }
        .dashboard-tareas-modulo .config-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .dashboard-tareas-modulo .config-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%) !important;
            color: white !important;
            padding: 6px 14px;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
            font-size: 14px;
        }
        .dashboard-tareas-modulo .config-header h4 { margin: 0; font-size: 14px; font-weight: 600; }

        .config-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            transition: transform 0.15s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.08); }
        .stat-card .stat-label { font-size: 11px; color: #64748b; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .stat-value { font-size: 24px; font-weight: 700; }
        .stat-card.total .stat-value { color: #0ea5e9; }
        .stat-card.completadas .stat-value { color: #10b981; }
        .stat-card.atrasadas .stat-value { color: #ef4444; }
        .stat-card.rendimiento .stat-value { color: #2C5D94; }

        .filtros-container {
            background-color: #f8fafc;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 15px;
        }
        .filtros-container .form-group { margin-bottom: 0; }
        .filtros-container label { margin: 0 8px 0 0; font-weight: 600; color: #2C5D94; }

        .aud-tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
        }
        .dashboard-tareas-modulo .aud-tabla thead th {
            background: linear-gradient(135deg, #72A1CF 0%, #8EB7DD 100%) !important;
            color: white !important;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
        }
        .aud-tabla tbody td { padding: 6px 10px; border-bottom: 1px solid #dee2e6; }
        .aud-tabla tbody tr:hover { background-color: #f8f9fa; }
        .aud-tabla th.col-estado, .aud-tabla td.col-estado { text-align: center; }
        .aud-tabla th.col-accion, .aud-tabla td.col-accion { width: 250px; max-width: 300px; white-space: nowrap; text-align: center; }
        .aud-tabla th.col-descripcion, .aud-tabla td.col-descripcion { max-width: 200px; overflow: hidden; text-overflow: ellipsis; }
        .estado-badge.estado-finalizada { color: #198754; font-weight: 600; }
        .estado-badge.estado-pendiente { color: #fd7e14; font-weight: 600; }
        .estado-badge.estado-en-proceso { color: #2C5D94; font-weight: 600; }
        .estado-badge.estado-asignado { color: #6c757d; font-weight: 500; }

        .tarea-zona-pegar {
            margin-top: 8px; padding: 12px; border: 2px dashed #2C5D94; border-radius: 8px;
            background: #f0f9ff; text-align: center; cursor: pointer; outline: none;
        }
        .tarea-zona-pegar:hover, .tarea-zona-pegar:focus { border-color: #3d7bb8; background: #D1E6F4; }
        .tarea-zona-pegar.tarea-zona-pegar-drag { border-color: #3d7bb8; background: #8EB7DD; }

        .mini-stats-reportes { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; }
        .mini-stat-card-reportes { background: linear-gradient(145deg, #fff 0%, #f8fafc 100%); border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; text-align: center; }
        .mini-stat-card-reportes .stat-label { font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600; text-transform: uppercase; }
        .mini-stat-card-reportes .stat-value { font-size: 22px; font-weight: 700; }
        .mini-stat-card-reportes.total .stat-value { color: #0ea5e9; }
        .mini-stat-card-reportes.completadas .stat-value { color: #10b981; }
        .mini-stat-card-reportes.proceso .stat-value { color: #64748b; }
        .mini-stat-card-reportes.atrasadas .stat-value { color: #ef4444; }
        .mini-stat-card-reportes.avance .stat-value { color: #2C5D94; }

        .barra-progreso-fondo {
            position: relative;
            width: 100%;
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

        .stat-clickable { cursor: pointer; }
        .tab-pane { background: transparent; display: none; }
        .tab-pane.active { display: block; }
        .dashboard-grafico-moderno {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            padding: 16px 0;
        }
        .dashboard-grafico-moderno .chart-container {
            position: relative;
            width: 200px;
            height: 200px;
            flex-shrink: 0;
        }
        .dashboard-grafico-moderno .chart-leyenda {
            flex: 0 1 auto;
            min-width: 200px;
        }
        .chart-leyenda-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            margin-bottom: 8px;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 14px;
        }
        .chart-leyenda-item .dot { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; }
        .chart-leyenda-item .label { flex: 1; font-weight: 600; color: #1e293b; }
        .chart-leyenda-item .valor { font-weight: 700; color: #2C5D94; font-size: 14px; }
        .accesos-rapidos .btn { border-radius: 8px; font-weight: 600; padding: 10px 18px; }
    </style>
</head>
<body>
<div id="set1" class="container-fluid dashboard-tareas-modulo" style="padding: 20px; background: #E8F0F7; min-height: 100vh;">
    <div class="exa-header">
        <h3><span class="glyphicon glyphicon-stats"></span> Dashboard de Control de Personal - Tareas</h3>
    </div>

    <div class="tabs-wrapper">
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#tab-dashboard" aria-controls="tab-dashboard" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-stats"></span> Dashboard General</a></li>
        <li role="presentation"><a href="#tab-gestion" aria-controls="tab-gestion" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-tasks"></span> Gestión de Tareas</a></li>
        <li role="presentation"><a href="#tab-asignacion" aria-controls="tab-asignacion" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-user"></span> Asignación de Tareas</a></li>
        <li role="presentation"><a href="#tab-reportes" aria-controls="tab-reportes" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-list-alt"></span> Reportes y Rendimiento</a></li>
    </ul>

    <div class="tab-content dashboard-container">
        <!-- 1. Dashboard General -->
        <div role="tabpanel" class="tab-pane active" id="tab-dashboard">
            <div class="config-card">
                <div class="config-header"><h4>Indicadores generales</h4></div>
                <div class="dashboard-filtros" style="margin-bottom:16px; display:flex; flex-wrap:wrap; align-items:center; gap:14px;">
                    <label class="control-label" style="margin:0; font-weight:600; color:#475569;">Período:</label>
                    <select id="dashboardPeriodo" class="form-control input-sm" style="width:150px; border-radius:8px; border-color:#e2e8f0;">
                        <option value="">Todo</option>
                        <option value="semana">Esta semana</option>
                        <option value="mes">Este mes</option>
                        <option value="anio">Este año</option>
                    </select>
                    <button type="button" class="btn btn-primary btn-sm" id="btnActualizarIndicadores" style="border-radius:8px;"><i class="glyphicon glyphicon-refresh"></i> Actualizar indicadores</button>
                    <span id="kpi-ultima-act" class="text-muted small" style="margin-left:auto; color:#94a3b8;">Actualizado al cargar</span>
                </div>
                <div class="config-stats" id="kpi-dashboard">
                    <div class="stat-card total stat-clickable" data-tipo="all" title="Clic para ver detalle de todas las tareas">
                        <div class="stat-label">Total tareas</div>
                        <div class="stat-value" id="kpi-total"><?php echo $total_tareas; ?></div>
                    </div>
                    <div class="stat-card completadas stat-clickable" data-tipo="completadas" title="Clic para ver tareas completadas">
                        <div class="stat-label">Completadas</div>
                        <div class="stat-value" id="kpi-completadas"><?php echo $pct_completadas; ?>%</div>
                    </div>
                    <div class="stat-card atrasadas stat-clickable" data-tipo="atrasadas" title="Clic para ver tareas atrasadas">
                        <div class="stat-label">Atrasadas</div>
                        <div class="stat-value" id="kpi-atrasadas"><?php echo $pct_atrasadas; ?>%</div>
                    </div>
                    <div class="stat-card rendimiento stat-clickable" data-tipo="all" title="Clic para ver desglose">
                        <div class="stat-label">Rendimiento promedio</div>
                        <div class="stat-value" id="kpi-rendimiento"><?php echo $rendimiento_promedio; ?>%</div>
                    </div>
                </div>
                <div id="dashboard-grafico" class="dashboard-grafico-moderno">
                    <div class="chart-container">
                        <canvas id="chartTareasEstado"></canvas>
                    </div>
                    <div class="chart-leyenda">
                        <p style="margin:0 0 10px 0; font-weight:700; color:#1e293b; font-size:13px;">Distribución por estado:</p>
                        <div class="chart-leyenda-item"><span class="dot" style="background:#10b981;"></span><span class="label">Completadas</span><span class="valor" id="graf-completadas">0</span></div>
                        <div class="chart-leyenda-item"><span class="dot" style="background:#ef4444;"></span><span class="label">Atrasadas</span><span class="valor" id="graf-atrasadas">0</span></div>
                        <div class="chart-leyenda-item"><span class="dot" style="background:#64748b;"></span><span class="label">En proceso</span><span class="valor" id="graf-proceso">0</span></div>
                    </div>
                </div>
                <div class="accesos-rapidos" style="margin:15px 0; display:flex; flex-wrap:wrap; gap:10px; justify-content:center;">
                    <a href="#tab-gestion" class="btn btn-success btn-sm" data-tab="tab-gestion"><i class="glyphicon glyphicon-plus"></i> Crear tarea</a>
                    <a href="#tab-asignacion" class="btn btn-info btn-sm" data-tab="tab-asignacion"><i class="glyphicon glyphicon-user"></i> Asignar tarea</a>
                </div>
            </div>
            <div class="config-card">
                <div class="config-header"><h4>Tareas que requieren atención</h4></div>
                <p class="text-muted small">Atrasadas o con fin tentativa en los próximos 7 días.</p>
                <table id="tablaTareasAtencion" class="aud-tabla" style="margin-top:10px;">
                    <thead><tr><th>Tarea</th><th>Empleado</th><th class="col-estado">Estado</th><th>Fin tentativa</th><th>Avance %</th><th>Tipo</th></tr></thead>
                    <tbody id="bodyTareasAtencion"><tr><td colspan="6" class="text-muted">Cargando…</td></tr></tbody>
                </table>
            </div>
        </div>

        <!-- 2. Gestión de Tareas -->
        <div role="tabpanel" class="tab-pane" id="tab-gestion">
            <div class="config-card">
                <div class="config-header"><h4>Nueva tarea</h4></div>
                <form id="formTarea" class="form-horizontal" accept-charset="UTF-8">
                    <input type="hidden" name="Tar_Cod" id="Tar_Cod" value="" />
                    <input type="hidden" name="Tar_Estado" id="Tar_Estado" value="Pendiente" />
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Título <span class="text-danger">*</span></label>
                        <div class="col-sm-6">
                            <input type="text" name="Tar_Titulo" id="Tar_Titulo" class="form-control input-sm" maxlength="200" placeholder="Título de la tarea" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Descripción</label>
                        <div class="col-sm-6">
                            <textarea name="Tar_Descripcion" id="Tar_Descripcion" class="form-control input-sm" rows="2" placeholder="Descripción opcional"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Prioridad</label>
                        <div class="col-sm-3">
                            <select name="Tar_Prioridad" id="Tar_Prioridad" class="form-control input-sm">
                                <option value="Media">Media</option>
                                <option value="Alta">Alta</option>
                                <option value="Baja">Baja</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Fecha inicio <span class="text-danger">*</span></label>
                        <div class="col-sm-3">
                            <input type="date" name="Tar_Fecha_Inicio" id="Tar_Fecha_Inicio" class="form-control input-sm" />
                        </div>
                        <label class="col-sm-1 control-label">Fecha fin tentativa</label>
                        <div class="col-sm-3">
                            <input type="date" name="Tar_Fecha_Fin" id="Tar_Fecha_Fin" class="form-control input-sm" placeholder="Opcional" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-8">
                            <p class="form-control-static text-muted small">Fechas inicio y fin son <strong>tentativas</strong> (para revisar si está atrasado). La <strong>fecha de culminación real</strong> la pone el usuario asignado al registrar el avance, o se registra automáticamente al guardar 100%.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Imágenes adjuntas</label>
                        <div class="col-sm-6">
                            <input type="file" name="adjuntosTarea[]" id="adjuntosTarea" class="form-control input-sm" accept="image/jpeg,image/png,image/gif,image/webp" multiple />
                            <p class="text-muted small">Opcional. Suba, <strong>pegue (Ctrl+V)</strong> o <strong>arrastre</strong> imágenes para dar contexto al asignado. JPG, PNG, GIF, WebP. Máx. 5 MB.</p>
                            <div id="zonaPegarTarea" class="tarea-zona-pegar" tabindex="0" title="Pegar (Ctrl+V) o arrastrar imágenes aquí">
                                <span class="text-muted">O pegar / arrastrar imagen aquí (Ctrl+V)</span>
                            </div>
                            <div id="adjuntosTareaPreviews" style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px;"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-6">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardarTarea"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar tarea</button>
                            <button type="reset" class="btn btn-default btn-sm">Limpiar</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="config-card">
                <div class="config-header"><h4>Listado de tareas</h4></div>
                <div class="filtros-container">
                    <div class="form-group">
                        <label>Buscar:</label>
                        <select id="op_opciones" class="form-control input-sm" style="width: auto;">
                            <option value="d">Por descripción</option>
                            <option value="c">Por código</option>
                        </select>
                        <input type="text" id="txt_search" class="form-control input-sm" placeholder="Texto o código" style="width: 180px;" />
                        <button type="button" class="btn btn-primary btn-sm" id="btnBuscarTareas"><i class="glyphicon glyphicon-search"></i> Buscar</button>
                    </div>
                </div>
                <div id="gridTareasContainer">
                    <table id="gridTareas" class="aud-tabla"></table>
                </div>
                <div id="pagerTareas"></div>
            </div>
        </div>

        <!-- 3. Asignación de Tareas -->
        <div role="tabpanel" class="tab-pane" id="tab-asignacion">
            <div class="config-card">
                <div class="config-header"><h4>Asignar tarea a empleado</h4></div>
                <form id="formAsignacion" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Tarea</label>
                        <div class="col-sm-5">
                            <select name="Tar_Cod" id="Tar_Cod_Asig" class="form-control input-sm">
                                <option value="">-- Seleccione tarea --</option>
                                <?php foreach ($lista_tareas_disponibles as $t): ?>
                                    <option value="<?php echo $t['Tar_Cod']; ?>"><?php echo htmlspecialchars($t['Tar_Titulo']); ?> (<?php echo $t['Tar_Estado']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Empleado</label>
                        <div class="col-sm-5">
                            <select name="Per_Cod" id="Per_Cod_Asig" class="form-control input-sm">
                                <option value="">-- Seleccione empleado --</option>
                                <?php foreach ($lista_empleados as $e): ?>
                                    <option value="<?php echo $e['Per_Cod']; ?>"><?php echo htmlspecialchars($e['Nombre']); ?> (<?php echo $e['Prs_Ced']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-6">
                            <button type="button" class="btn btn-success btn-sm" id="btnAsignarTarea"><i class="glyphicon glyphicon-user"></i> Asignar tarea</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="config-card">
                <div class="config-header"><h4>Asignaciones actuales</h4></div>
                <p class="text-muted small">Listado de tareas asignadas a empleados. Se actualiza al abrir la pestaña y al asignar una nueva tarea.</p>
                <table id="gridAsignaciones" class="aud-tabla">
                    <thead><tr><th>Tarea</th><th>Empleado</th><th class="col-estado">Estado</th><th>Fecha asignación</th><th>Fin tentativa</th><th>Acción</th></tr></thead>
                    <tbody id="bodyAsignaciones"><tr><td colspan="6" class="text-muted">Cargando…</td></tr></tbody>
                </table>
            </div>
        </div>

        <!-- 4. Reportes y Rendimiento -->
        <div role="tabpanel" class="tab-pane" id="tab-reportes">
            <div class="config-card">
                <div class="config-header"><h4>Ranking de productividad por empleado</h4></div>
                <p class="text-muted small">Métricas de tareas asignadas, completadas, atrasadas y rendimiento.</p>
                <table id="gridMetricas" class="aud-tabla">
                    <thead><tr><th>Empleado</th><th>Total tareas</th><th>Completadas</th><th>Atrasadas</th><th>Rendimiento</th></tr></thead>
                    <tbody id="bodyMetricas">
                        <?php
                        $asig_inicial = $obBD_con1->getArrayConsulta(11, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
                        if (!is_array($asig_inicial)) {
                            $asig_inicial = $obBD_con1->getArrayConsulta(30, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
                        }
                        if (!is_array($asig_inicial)) $asig_inicial = array();
                        $metricas_inicial = array();
                        foreach ($asig_inicial as $r) {
                            $perCod = isset($r['Per_Cod']) ? intval($r['Per_Cod']) : 0;
                            $nombre = isset($r['Empleado_Nombre']) ? trim($r['Empleado_Nombre']) : '';
                            $estado = isset($r['Tar_Estado']) ? $r['Tar_Estado'] : '';
                            $fecFin = isset($r['Tar_Fecha_Fin']) ? trim($r['Tar_Fecha_Fin']) : '';
                            $fecCulm = isset($r['Tar_Fecha_Culminacion']) ? trim($r['Tar_Fecha_Culminacion']) : '';
                            $key = $perCod . '|' . $nombre;
                            if (!isset($metricas_inicial[$key])) {
                                $metricas_inicial[$key] = array('Nombre' => $nombre, 'Total_Tareas' => 0, 'Tareas_Completadas' => 0, 'Tareas_Atrasadas' => 0);
                            }
                            $metricas_inicial[$key]['Total_Tareas']++;
                            if ($estado === 'Finalizada') {
                                $metricas_inicial[$key]['Tareas_Completadas']++;
                                if ($fecFin !== '' && $fecFin !== '0000-00-00' && $fecCulm !== '' && $fecCulm !== '0000-00-00' && $fecCulm > $fecFin) {
                                    $metricas_inicial[$key]['Tareas_Atrasadas']++;
                                }
                            } elseif ($fecFin !== '' && $fecFin !== '0000-00-00' && $fecFin < date('Y-m-d')) {
                                $metricas_inicial[$key]['Tareas_Atrasadas']++;
                            }
                        }
                        $arr_met = array_values($metricas_inicial);
                        if (count($arr_met) > 0) {
                            foreach ($arr_met as $m) {
                                $tot = intval($m['Total_Tareas']);
                                $com = intval($m['Tareas_Completadas']);
                                $atr = intval($m['Tareas_Atrasadas']);
                                $pct = $tot > 0 ? round(100 * $com / $tot, 1) : 0;
                                $claseBarra = $pct >= 70 ? 'alto' : ($pct >= 40 ? 'medio' : 'bajo');
                                echo '<tr><td>' . htmlspecialchars($m['Nombre']) . '</td><td>' . $tot . '</td><td>' . $com . '</td><td>' . $atr . '</td><td><div class="barra-progreso-fondo"><div class="barra-progreso-relleno ' . $claseBarra . '" style="width:' . $pct . '%;">' . $pct . '%</div></div></td></tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-muted">No hay datos de métricas.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn-primary btn-sm" id="btnRefrescarMetricas" style="margin-top:10px;"><i class="glyphicon glyphicon-refresh"></i> Actualizar métricas</button>
            </div>
            <div class="config-card">
                <div class="config-header"><h4>Todas las tareas asignadas y avances</h4></div>
                <p class="text-muted small">Listado de todas las tareas asignadas a empleados con su avance (porcentaje) respectivo.</p>
                <div id="miniDashboardReportes" class="mini-stats-reportes" style="display:none; margin-bottom:18px;">
                    <div class="mini-stat-card-reportes total"><div class="stat-label">Total tareas</div><div class="stat-value" id="stat-reportes-total">0</div></div>
                    <div class="mini-stat-card-reportes completadas"><div class="stat-label">Completadas</div><div class="stat-value" id="stat-reportes-completadas">0</div></div>
                    <div class="mini-stat-card-reportes proceso"><div class="stat-label">En proceso</div><div class="stat-value" id="stat-reportes-proceso">0</div></div>
                    <div class="mini-stat-card-reportes atrasadas"><div class="stat-label">Atrasadas</div><div class="stat-value" id="stat-reportes-atrasadas">0</div></div>
                    <div class="mini-stat-card-reportes avance"><div class="stat-label">Avance promedio</div><div class="stat-value" id="stat-reportes-avance">0%</div></div>
                </div>
                <div style="margin-bottom:12px; display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
                    <label class="control-label" style="margin:0; font-weight:600;">Empleado:</label>
                    <select id="filtroPerCodReportes" class="form-control input-sm" style="width:220px;">
                        <option value="">Todos los empleados</option>
                    </select>
                    <label class="control-label" style="margin:0; font-weight:600;">Estado:</label>
                    <select id="filtroEstadoReportes" class="form-control input-sm" style="width:150px;">
                        <option value="pendientes" selected>Pendientes</option>
                        <option value="finalizados">Finalizados</option>
                        <option value="todos">Todos</option>
                    </select>
                    <label class="control-label" style="margin:0; font-weight:600;">Período:</label>
                    <select id="filtroPeriodoReportes" class="form-control input-sm" style="width:150px;">
                        <option value="">Todo</option>
                        <option value="semana">Esta semana</option>
                        <option value="mes">Este mes</option>
                        <option value="anio">Este año</option>
                    </select>
                    <label class="control-label" style="margin:0; font-weight:600;">Rango fechas (reportes):</label>
                    <input type="date" id="reporteFechaIni" class="form-control input-sm" style="width:140px;" placeholder="Desde" title="Fecha desde (para PDF/Excel)" />
                    <input type="date" id="reporteFechaFin" class="form-control input-sm" style="width:140px;" placeholder="Hasta" title="Fecha hasta (para PDF/Excel)" />
                    <button type="button" class="btn btn-default btn-sm" id="btnRefrescarAsignacionesAvances"><i class="glyphicon glyphicon-refresh"></i> Actualizar listado</button>
                    <button type="button" class="btn btn-danger btn-sm" id="btnExportarPDF" title="Exportar listado a PDF"><i class="glyphicon glyphicon-file"></i> Exportar PDF</button>
                    <button type="button" class="btn btn-success btn-sm" id="btnExportarExcel" title="Exportar listado a Excel"><i class="glyphicon glyphicon-list-alt"></i> Exportar Excel</button>
                </div>
                <table id="gridAsignacionesAvances" class="aud-tabla" style="margin-top:12px;">
                    <thead><tr><th>Tarea</th><th class="col-descripcion">Descripción</th><th>Empleado</th><th class="col-estado">Estado</th><th>Inicio</th><th>Fin tentativa</th><th>Culminación real</th><th>Avance %</th><th>Fecha asignación</th><th>Acción</th></tr></thead>
                    <tbody id="bodyAsignacionesAvances"><tr><td colspan="10" class="text-muted">Cargando…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <!-- Modal detalle KPI -->
    <div class="modal fade" id="modalDetalleKpi" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="modalDetalleKpiTitle">Detalle de tareas</h4>
                </div>
                <div class="modal-body">
                    <table class="aud-tabla" style="width:100%;">
                        <thead><tr><th>Tarea</th><th>Asignado a</th><th class="col-estado">Estado</th><th>Inicio</th><th>Fin tentativa</th><th>Culminación</th></tr></thead>
                        <tbody id="modalDetalleKpiBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal adjuntos de tarea -->
    <div class="modal fade" id="modalAdjuntosTarea" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="modalAdjuntosTareaTitle">Imágenes de la tarea</h4>
                </div>
                <div class="modal-body">
                    <div id="modalAdjuntosTareaLista" style="display:flex; flex-wrap:wrap; gap:12px; min-height:80px;"></div>
                    <p id="modalAdjuntosTareaVacio" class="text-muted" style="display:none;">Esta tarea no tiene imágenes adjuntas.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal avances del empleado (formulario de avances en solo lectura) -->
    <div class="modal fade" id="modalAvancesTarea" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="modalAvancesTareaTitle">Avances de la tarea</h4>
                </div>
                <div class="modal-body">
                    <div id="modalAvancesTareaResumen" style="margin-bottom:16px; padding:12px; background:#f0f9f9; border:1px solid #2c7a7b; border-radius:4px;"></div>
                    <div id="modalAvancesTareaAdjuntos" style="margin-bottom:16px; display:none;">
                        <h5 style="margin-top:0;">Capturas / imágenes adjuntas</h5>
                        <div id="modalAvancesTareaAdjuntosLista"></div>
                    </div>
                    <h5 style="margin-top:0;">Historial de avances</h5>
                    <table class="aud-tabla" style="width:100%;">
                        <thead><tr><th>Usuario</th><th>Porcentaje</th><th>Descripción</th><th>Fecha</th></tr></thead>
                        <tbody id="modalAvancesTareaBody"></tbody>
                    </table>
                    <p id="modalAvancesTareaVacio" class="text-muted" style="display:none;">No hay registros de avance aún.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function () {
    var urlBase = <?php echo json_encode($_SERVER['PHP_SELF']); ?>;
    var sesEmpCod = '<?php echo $Ses_Emp_Cod; ?>';

    function estadoCellHtml(estadoRaw) {
        var e = (estadoRaw || '').trim();
        var c = 'estado-otro';
        if (e === 'Finalizada') c = 'estado-finalizada';
        else if (e === 'Pendiente') c = 'estado-pendiente';
        else if (e === 'En Proceso') c = 'estado-en-proceso';
        else if (e === 'Asignado') c = 'estado-asignado';
        var txt = e.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') || '-';
        return '<td class="col-estado"><span class="estado-badge ' + c + '">' + txt + '</span></td>';
    }

    var filesToUploadTarea = [];
    var previewObjectUrlsTarea = [];

    function formToQuery(form) {
        var s = [], el, i, name, val;
        var els = form.querySelectorAll('input, select, textarea');
        for (i = 0; i < els.length; i++) {
            el = els[i];
            if (!el.name || el.disabled) continue;
            if (el.type === 'radio' || el.type === 'checkbox') { if (!el.checked) continue; }
            if (el.type === 'file') continue;
            name = el.name;
            val = encodeURIComponent(el.value || '');
            s.push(encodeURIComponent(name) + '=' + val);
        }
        return s.join('&');
    }

    function renderAdjuntosTareaPreviews() {
        var cont = document.getElementById('adjuntosTareaPreviews');
        if (!cont) return;
        previewObjectUrlsTarea.forEach(function (u) { try { URL.revokeObjectURL(u); } catch (e) {} });
        previewObjectUrlsTarea = [];
        if (filesToUploadTarea.length === 0) {
            cont.innerHTML = '';
            cont.style.display = 'none';
            return;
        }
        cont.style.display = 'flex';
        cont.innerHTML = '';
        filesToUploadTarea.forEach(function (file, idx) {
            var url = URL.createObjectURL(file);
            previewObjectUrlsTarea.push(url);
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
            btnEliminar.onclick = (function (i) { return function () { filesToUploadTarea.splice(i, 1); renderAdjuntosTareaPreviews(); }; })(idx);
            wrap.appendChild(img);
            wrap.appendChild(btnEliminar);
            cont.appendChild(wrap);
        });
    }

    function handlePasteTarea(e) {
        var items = e.clipboardData && e.clipboardData.items;
        if (!items) return;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== 0) continue;
            e.preventDefault();
            e.stopPropagation();
            var file = items[i].getAsFile();
            if (file && file.size <= 5 * 1024 * 1024) {
                filesToUploadTarea.push(file);
                renderAdjuntosTareaPreviews();
            }
            break;
        }
    }

    function handleDragOverTarea(e) { e.preventDefault(); e.stopPropagation(); if (e.dataTransfer) e.dataTransfer.dropEffect = 'copy'; var z = document.getElementById('zonaPegarTarea'); if (z) z.classList.add('tarea-zona-pegar-drag'); }
    function handleDragLeaveTarea(e) { e.preventDefault(); e.stopPropagation(); var z = document.getElementById('zonaPegarTarea'); if (z) z.classList.remove('tarea-zona-pegar-drag'); }
    function handleDropTarea(e) {
        e.preventDefault();
        e.stopPropagation();
        var z = document.getElementById('zonaPegarTarea');
        if (z) z.classList.remove('tarea-zona-pegar-drag');
        var files = e.dataTransfer && e.dataTransfer.files;
        if (!files || files.length === 0) return;
        var allowExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            var ext = (f.name || '').toLowerCase().split('.').pop();
            if (f.size <= 5 * 1024 * 1024 && allowExt.indexOf(ext) !== -1) {
                filesToUploadTarea.push(f);
            }
        }
        if (filesToUploadTarea.length > 0) renderAdjuntosTareaPreviews();
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

    function getParams(obj) {
        var p = [];
        for (var k in obj) if (obj.hasOwnProperty(k)) p.push(encodeURIComponent(k) + '=' + encodeURIComponent(obj[k]));
        return p.join('&');
    }

    function loadGridFallback() {
        var txt = document.getElementById('txt_search');
        var op = document.getElementById('op_opciones');
        var q = getParams({
            tareasGridAjax: 1,
            Emp_Cod: sesEmpCod,
            page: 1,
            rows: 50,
            search: (txt && txt.value) ? txt.value : '',
            op_opciones: (op && op.value) ? op.value : 'd'
        });
        request('GET', urlBase + '?' + q, null, function (resp) {
            var rows = (resp && resp.rows) ? resp.rows : [];
            var body = document.getElementById('gridTareasBody');
            if (!body) return;
            var html = '';
            if (rows.length === 0) html = '<tr><td colspan="8" class="text-muted">Sin registros.</td></tr>';
            else {
                for (var i = 0; i < rows.length; i++) {
                    var r = rows[i];
                    var tarCod = (r.Tar_Cod != null) ? r.Tar_Cod : '';
                    var titulo = (r.Tar_Titulo || '').replace(/"/g, '&quot;');
                    var desc = (r.Tar_Descripcion || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    var descShort = desc.length > 60 ? desc.substring(0, 60) + '…' : desc;
                    var descTitle = desc ? ' title="' + desc.replace(/"/g, '&quot;') + '"' : '';
                    var btnEditar = '<button type="button" class="btn btn-default btn-xs btn-editar-tarea" data-tar-cod="' + tarCod + '" title="Editar tarea"><i class="glyphicon glyphicon-edit"></i> Editar</button>';
                    var btnEliminar = '<button type="button" class="btn btn-danger btn-xs btn-eliminar-tarea" data-tar-cod="' + tarCod + '" title="Eliminar tarea"><i class="glyphicon glyphicon-trash"></i> Eliminar</button>';
                    var btnAdjuntos = '<button type="button" class="btn btn-info btn-xs btn-ver-adjuntos-tarea" data-tar-cod="' + tarCod + '" data-titulo="' + titulo + '" title="Ver imágenes adjuntas"><i class="glyphicon glyphicon-picture"></i> Adjuntos</button>';
                    html += '<tr><td>' + (r.Tar_Cod || '') + '</td><td>' + (r.Tar_Titulo || '') + '</td><td' + descTitle + ' class="col-descripcion">' + descShort + '</td><td>' + (r.Tar_Prioridad || '') + '</td><td>' + (r.Tar_Fecha_Inicio || '') + '</td><td>' + (r.Tar_Fecha_Fin || '') + '</td>' + estadoCellHtml(r.Tar_Estado) + '<td class="col-accion">' + btnAdjuntos + ' ' + btnEditar + ' ' + btnEliminar + '</td></tr>';
                }
            }
            body.innerHTML = html;
        });
    }

    function refreshAsignaciones() {
        request('GET', urlBase + '?listarAsignaciones=1', null, function (resp) {
            var rows = (resp && resp.rows) ? resp.rows : [];
            var body = document.getElementById('bodyAsignaciones');
            if (!body) return;
            var html = '';
            if (rows.length === 0) {
                html = '<tr><td colspan="6" class="text-muted">No hay asignaciones.</td></tr>';
            } else {
                for (var i = 0; i < rows.length; i++) {
                    var r = rows[i];
                    var titulo = (r.Tar_Titulo || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    var empleado = (r.Empleado_Nombre || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    var fecha = r.Tas_Fecha_Asignacion || '';
                    var finTentativa = (r.Tar_Fecha_Fin && r.Tar_Fecha_Fin !== '0000-00-00') ? r.Tar_Fecha_Fin : '-';
                    var tasCod = (r.Tas_Cod != null) ? r.Tas_Cod : '';
                    var btnEliminar = '<button type="button" class="btn btn-danger btn-xs btn-eliminar-asignacion" data-tas-cod="' + tasCod + '" title="Quitar esta asignación"><i class="glyphicon glyphicon-remove"></i> Eliminar</button>';
                    html += '<tr><td>' + titulo + '</td><td>' + empleado + '</td>' + estadoCellHtml(r.Tar_Estado) + '<td>' + fecha + '</td><td>' + finTentativa + '</td><td>' + btnEliminar + '</td></tr>';
                }
            }
            body.innerHTML = html;
            bindEliminarAsignacion();
        });
    }

    function bindEliminarAsignacion() {
        var btns = document.querySelectorAll('#bodyAsignaciones .btn-eliminar-asignacion');
        for (var i = 0; i < btns.length; i++) {
            btns[i].onclick = (function (tasCod) {
                return function () {
                    if (!confirm('¿Eliminar esta asignación? El empleado dejará de tener la tarea asignada.')) return;
                    var data = 'eliminarAsignacion=1&Tas_Cod=' + encodeURIComponent(tasCod);
                    request('POST', urlBase, data, function (r) {
                        if (r.success) {
                            alert('Asignación eliminada.');
                            refreshAsignaciones();
                            refreshTareasSelects();
                        } else {
                            alert(r.message || 'Error al eliminar.');
                        }
                    });
                };
            })(parseInt(btns[i].getAttribute('data-tas-cod'), 10));
        }
    }

    function refreshEmpleadosSelect() {
        request('GET', urlBase + '?listarEmpleados=1', null, function (resp) {
            var rows = (resp && resp.rows) ? resp.rows : [];
            var sel = document.getElementById('Per_Cod_Asig');
            if (!sel) return;
            var val = sel.value;
            sel.innerHTML = '<option value="">-- Seleccione empleado --</option>';
            for (var i = 0; i < rows.length; i++) {
                var r = rows[i];
                var opt = document.createElement('option');
                opt.value = r.Per_Cod || '';
                opt.textContent = (r.Nombre || '') + ' (' + (r.Prs_Ced || '') + ')';
                sel.appendChild(opt);
            }
            if (val) sel.value = val;
        });
    }

    function refreshTareasSelects() {
        request('GET', urlBase + '?listarTareasDisponibles=1', null, function (resp) {
            var rows = (resp && resp.rows) ? resp.rows : [];
            var selAsig = document.getElementById('Tar_Cod_Asig');
            if (selAsig) {
                var val = selAsig.value;
                selAsig.innerHTML = '<option value="">-- Seleccione tarea --</option>';
                for (var i = 0; i < rows.length; i++) {
                    var r = rows[i];
                    var opt = document.createElement('option');
                    opt.value = r.Tar_Cod || '';
                    opt.textContent = (r.Tar_Titulo || '') + ' (' + (r.Tar_Estado || '') + ')';
                    selAsig.appendChild(opt);
                }
                if (val) { selAsig.value = val; }
            }
        });
    }

    function init() {
        var formTarea = document.getElementById('formTarea');
        var formAsignacion = document.getElementById('formAsignacion');
        var gridTareas = document.getElementById('gridTareas');
        var pagerTareas = document.getElementById('pagerTareas');
        var useJqGrid = false;
        try {
            if (window.jQuery && typeof window.jQuery.fn.jqGrid === 'function') useJqGrid = true;
        } catch (e) {}

        if (useJqGrid) {
            window.jQuery(gridTareas).jqGrid({
                url: urlBase,
                datatype: 'json',
                mtype: 'GET',
                postData: {
                    tareasGridAjax: 1,
                    Emp_Cod: sesEmpCod,
                    search: function () { var t = document.getElementById('txt_search'); return t ? t.value : ''; },
                    op_opciones: function () { var o = document.getElementById('op_opciones'); return o ? o.value : 'd'; }
                },
                colModel: [
                    { name: 'Tar_Cod', label: 'Cód.', key: true, width: 50, align: 'center', hidden: true },
                    { name: 'Tar_Titulo', label: 'Título', width: 150 },
                    { name: 'Tar_Descripcion', label: 'Descripción', width: 200 },
                    { name: 'Tar_Prioridad', label: 'Prioridad', width: 80, align: 'center' },
                    { name: 'Tar_Fecha_Inicio', label: 'Inicio', width: 90, align: 'center' },
                    { name: 'Tar_Fecha_Fin', label: 'Fin', width: 90, align: 'center' },
                    { name: 'Tar_Estado', label: 'Estado', width: 90, align: 'center' },
                    { name: 'actions', label: 'Acción', width: 250, align: 'center', sortable: false, formatter: function(cell, opts, row) {
                        var tit = (row.Tar_Titulo || '').replace(/"/g, '&quot;');
                        return '<button type="button" class="btn btn-info btn-xs btn-ver-adjuntos-tarea" data-tar-cod="' + (row.Tar_Cod || '') + '" data-titulo="' + tit + '" title="Ver imágenes adjuntas"><i class="glyphicon glyphicon-picture"></i> Adjuntos</button> ' +
                            '<button type="button" class="btn btn-default btn-xs btn-editar-tarea" data-tar-cod="' + (row.Tar_Cod || '') + '" title="Editar tarea"><i class="glyphicon glyphicon-edit"></i> Editar</button> ' +
                            '<button type="button" class="btn btn-danger btn-xs btn-eliminar-tarea" data-tar-cod="' + (row.Tar_Cod || '') + '" title="Eliminar tarea"><i class="glyphicon glyphicon-trash"></i> Eliminar</button>';
                    } }
                ],
                pager: '#pagerTareas',
                rowNum: 10,
                rowList: [10, 20, 50],
                sortname: 'Tar_Fecha_Fin',
                sortorder: 'asc',
                viewrecords: true,
                height: 280,
                caption: 'Listado de tareas'
            });
            window.jQuery(gridTareas).jqGrid('navGrid', '#pagerTareas', { edit: false, add: false, del: false, search: false });
        } else {
            gridTareas.innerHTML = '<thead><tr><th>Cód.</th><th>Título</th><th class="col-descripcion">Descripción</th><th>Prioridad</th><th>Inicio</th><th>Fin</th><th class="col-estado">Estado</th><th class="col-accion">Acción</th></tr></thead><tbody id="gridTareasBody"></tbody>';
            gridTareas.className = 'aud-tabla table table-bordered table-condensed';
            if (pagerTareas) pagerTareas.innerHTML = '<p class="text-muted small">Listado de tareas (tabla simple). Para paginación jqGrid, abra el módulo en una pestaña nueva.</p>';
            loadGridFallback();
        }

        var urlAdjuntosTarea = '../adjuntos/tareas/';

        document.addEventListener('click', function (e) {
            // Cerrar modal de adjuntos al hacer click en X (evita que quede bloqueado)
            var closeBtn = e.target && (e.target.closest ? e.target.closest('#modalAdjuntosTarea .close, #modalAdjuntosTarea [data-dismiss="modal"]') : null);
            if (closeBtn) {
                var modal = document.getElementById('modalAdjuntosTarea');
                if (modal) {
                    public $ = window.jQuery || window.$;
                    if ($ && typeof $.fn !== 'undefined' && typeof $.fn.modal === 'function') {
                        $(modal).modal('hide');
                    } else {
                        modal.style.display = 'none';
                        modal.classList.remove('in');
                        document.body.classList.remove('modal-open');
                        var b = document.getElementById('modalAdjuntosTarea-backdrop');
                        if (b && b.parentNode) b.parentNode.removeChild(b);
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
            }

            var btn = e.target && (e.target.classList.contains('btn-ver-adjuntos-tarea') ? e.target : (e.target.closest && e.target.closest('.btn-ver-adjuntos-tarea')));
            if (btn) {
                var tarCod = btn.getAttribute && btn.getAttribute('data-tar-cod');
                var titulo = btn.getAttribute && btn.getAttribute('data-titulo');
                if (!tarCod) return;
                var titleEl = document.getElementById('modalAdjuntosTareaTitle');
                var listaEl = document.getElementById('modalAdjuntosTareaLista');
                var vacioEl = document.getElementById('modalAdjuntosTareaVacio');
                if (titleEl) titleEl.textContent = 'Imágenes de la tarea' + (titulo ? ': ' + titulo : '');
                if (listaEl) { listaEl.innerHTML = '<span class="text-muted">Cargando…</span>'; listaEl.style.display = 'flex'; }
                if (vacioEl) vacioEl.style.display = 'none';
                var modal = document.getElementById('modalAdjuntosTarea');
                if (modal) {
                    public $ = window.jQuery || window.$;
                    var cerrarModalAdjuntos = function () {
                        modal.style.display = 'none';
                        modal.classList.remove('in');
                        document.body.classList.remove('modal-open');
                        var b = document.getElementById('modalAdjuntosTarea-backdrop');
                        if (b && b.parentNode) b.parentNode.removeChild(b);
                    };
                    if ($ && typeof $.fn !== 'undefined' && typeof $.fn.modal === 'function') {
                        $(modal).modal('show');
                        var btnClose = modal.querySelector('[data-dismiss="modal"], .close');
                        if (btnClose) btnClose.onclick = function (ev) { ev.preventDefault(); ev.stopPropagation(); $(modal).modal('hide'); };
                    } else {
                        modal.style.display = 'block';
                        modal.classList.add('in');
                        modal.setAttribute('aria-hidden', 'false');
                        document.body.classList.add('modal-open');
                        var backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade in';
                        backdrop.id = 'modalAdjuntosTarea-backdrop';
                        backdrop.onclick = cerrarModalAdjuntos;
                        document.body.appendChild(backdrop);
                        var btnClose = modal.querySelector('[data-dismiss="modal"], .close');
                        if (btnClose) btnClose.onclick = function (ev) { ev.preventDefault(); ev.stopPropagation(); cerrarModalAdjuntos(); };
                    }
                }
                request('GET', urlBase + '?listarAdjuntosTarea=1&Tar_Cod=' + encodeURIComponent(tarCod), null, function (resp) {
                    var rows = (resp && resp.rows) ? resp.rows : [];
                    if (listaEl) {
                        if (rows.length === 0) {
                            listaEl.style.display = 'none';
                            if (vacioEl) vacioEl.style.display = 'block';
                        } else {
                            var html = '';
                            for (var a = 0; a < rows.length; a++) {
                                var ruta = (rows[a].Adj_Ruta || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                                var nombre = (rows[a].Adj_Nombre || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                                html += '<a href="' + urlAdjuntosTarea + ruta + '" target="_blank" title="' + nombre + '"><img src="' + urlAdjuntosTarea + ruta + '" alt="" style="max-height:120px; max-width:180px; object-fit:contain; border:1px solid #ccc; border-radius:4px;" /></a>';
                            }
                            listaEl.innerHTML = html;
                            listaEl.style.display = 'flex';
                            if (vacioEl) vacioEl.style.display = 'none';
                        }
                    }
                });
                return;
            }
            btn = e.target && (e.target.classList.contains('btn-eliminar-tarea') ? e.target : (e.target.closest && e.target.closest('.btn-eliminar-tarea')));
            if (btn) {
                var tarCod = btn.getAttribute && btn.getAttribute('data-tar-cod');
                if (tarCod && confirm('¿Eliminar esta tarea? Se marcará como inactiva.')) {
                    var data = 'eliminarTarea=1&Tar_Cod=' + encodeURIComponent(tarCod);
                    request('POST', urlBase, data, function (r) {
                        if (r.success) {
                            alert('Tarea eliminada correctamente.');
                            if (useJqGrid && window.jQuery) window.jQuery(gridTareas).trigger('reloadGrid');
                            else loadGridFallback();
                            refreshTareasSelects();
                            if (typeof refreshAsignaciones === 'function') refreshAsignaciones();
                        } else {
                            alert(r.message || 'Error al eliminar.');
                        }
                    });
                }
                return;
            }
            btn = e.target && (e.target.classList.contains('btn-editar-tarea') ? e.target : (e.target.closest && e.target.closest('.btn-editar-tarea')));
            if (btn) {
                var tarCod = btn.getAttribute && btn.getAttribute('data-tar-cod');
                if (!tarCod) return;
                request('GET', urlBase + '?tareaPorCod=1&Tar_Cod=' + encodeURIComponent(tarCod), null, function (resp) {
                    var row = (resp && resp.row) ? resp.row : null;
                    if (!row) {
                        alert('No se pudo cargar la tarea.');
                        return;
                    }
                    var el = function (id) { return document.getElementById(id); };
                    if (el('Tar_Cod')) el('Tar_Cod').value = row.Tar_Cod || '';
                    if (el('Tar_Titulo')) el('Tar_Titulo').value = row.Tar_Titulo || '';
                    if (el('Tar_Descripcion')) el('Tar_Descripcion').value = row.Tar_Descripcion || '';
                    if (el('Tar_Prioridad')) el('Tar_Prioridad').value = row.Tar_Prioridad || 'Media';
                    if (el('Tar_Fecha_Inicio')) el('Tar_Fecha_Inicio').value = row.Tar_Fecha_Inicio || '';
                    if (el('Tar_Fecha_Fin')) el('Tar_Fecha_Fin').value = (row.Tar_Fecha_Fin && row.Tar_Fecha_Fin !== '0000-00-00') ? row.Tar_Fecha_Fin : '';
                    if (el('Tar_Estado')) el('Tar_Estado').value = row.Tar_Estado || 'Pendiente';
                    filesToUploadTarea = [];
                    renderAdjuntosTareaPreviews();
                    var tabGestion = document.querySelector('[href="#tab-gestion"], [data-tab="tab-gestion"]');
                    if (tabGestion) tabGestion.click();
                    var formCard = formTarea && formTarea.closest('.config-card');
                    if (formCard) formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }
        });

        var btnBuscar = document.getElementById('btnBuscarTareas');
        if (btnBuscar) {
            btnBuscar.addEventListener('click', function () {
                if (useJqGrid && window.jQuery) window.jQuery(gridTareas).trigger('reloadGrid');
                else loadGridFallback();
            });
        }

        var btnGuardar = document.getElementById('btnGuardarTarea');
        if (btnGuardar && formTarea) {
            btnGuardar.addEventListener('click', function () {
                if (typeof aud_validaCrearTarea === 'function' && !aud_validaCrearTarea(formTarea)) return;
                var data;
                if (filesToUploadTarea.length > 0) {
                    data = new FormData();
                    data.append('Tar_Cod', document.getElementById('Tar_Cod').value || '');
                    data.append('Tar_Titulo', document.getElementById('Tar_Titulo').value || '');
                    data.append('Tar_Descripcion', document.getElementById('Tar_Descripcion').value || '');
                    data.append('Tar_Prioridad', document.getElementById('Tar_Prioridad').value || 'Media');
                    data.append('Tar_Fecha_Inicio', document.getElementById('Tar_Fecha_Inicio').value || '');
                    data.append('Tar_Fecha_Fin', document.getElementById('Tar_Fecha_Fin').value || '');
                    data.append('Tar_Estado', document.getElementById('Tar_Estado').value || 'Pendiente');
                    data.append('guardarTarea', '1');
                    for (var f = 0; f < filesToUploadTarea.length; f++) data.append('adjuntosTarea[]', filesToUploadTarea[f]);
                } else {
                    data = formToQuery(formTarea) + '&guardarTarea=1';
                }
                request('POST', urlBase, data, function (r) {
                    if (r && r.success) {
                        alert('Tarea guardada correctamente.');
                        formTarea.reset();
                        filesToUploadTarea = [];
                        renderAdjuntosTareaPreviews();
                        if (useJqGrid && window.jQuery) window.jQuery(gridTareas).trigger('reloadGrid');
                        else loadGridFallback();
                        refreshTareasSelects();
                    } else {
                        alert((r && r.message) ? r.message : 'Error al guardar.');
                    }
                });
            });
        }
        var fileInputTarea = document.getElementById('adjuntosTarea');
        if (fileInputTarea) {
            fileInputTarea.addEventListener('change', function () {
                var files = fileInputTarea.files;
                if (files) {
                    for (var i = 0; i < files.length; i++) filesToUploadTarea.push(files[i]);
                    fileInputTarea.value = '';
                }
                renderAdjuntosTareaPreviews();
            });
        }
        var zonaPegarTarea = document.getElementById('zonaPegarTarea');
        if (zonaPegarTarea) {
            zonaPegarTarea.addEventListener('paste', handlePasteTarea);
            zonaPegarTarea.addEventListener('click', function () { zonaPegarTarea.focus(); });
            zonaPegarTarea.addEventListener('dragover', handleDragOverTarea);
            zonaPegarTarea.addEventListener('dragleave', handleDragLeaveTarea);
            zonaPegarTarea.addEventListener('drop', handleDropTarea);
        }
        if (formTarea) {
            formTarea.addEventListener('paste', handlePasteTarea);
            formTarea.addEventListener('reset', function () {
                filesToUploadTarea = [];
                renderAdjuntosTareaPreviews();
            });
        }

        var btnAsignar = document.getElementById('btnAsignarTarea');
        if (btnAsignar && formAsignacion) {
            btnAsignar.addEventListener('click', function () {
                var tarCod = document.getElementById('Tar_Cod_Asig').value;
                var perCod = document.getElementById('Per_Cod_Asig').value;
                if (typeof aud_validaAsignacion === 'function' && !aud_validaAsignacion(tarCod, perCod)) return;
                var data = 'asignarTarea=1&Tar_Cod=' + encodeURIComponent(tarCod) + '&Per_Cod=' + encodeURIComponent(perCod);
                request('POST', urlBase, data, function (r) {
                    if (r.success) {
                        alert('Tarea asignada correctamente.');
                        formAsignacion.reset();
                        refreshAsignaciones();
                        refreshTareasSelects();
                    } else {
                        alert(r.message || 'Error al asignar.');
                    }
                });
            });
        }

        function refreshMetricas() {
            request('GET', urlBase + '?metricasRendimiento=1', null, function (d) {
                var rows = (d && d.rows) ? d.rows : [];
                var body = document.getElementById('bodyMetricas');
                if (!body) return;
                var html = '';
                if (rows.length === 0) {
                    html = '<tr><td colspan="5" class="text-muted">No hay datos de métricas.</td></tr>';
                } else {
                    for (var i = 0; i < rows.length; i++) {
                        var m = rows[i];
                        var tot = parseInt(m.Total_Tareas, 10) || 0;
                        var com = parseInt(m.Tareas_Completadas, 10) || 0;
                        var atr = parseInt(m.Tareas_Atrasadas, 10) || 0;
                        var pct = tot > 0 ? Math.round(100 * com / tot * 10) / 10 : 0;
                        var claseBarra = pct >= 70 ? 'alto' : (pct >= 40 ? 'medio' : 'bajo');
                        var nom = (m.Nombre || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                        html += '<tr><td>' + nom + '</td><td>' + tot + '</td><td>' + com + '</td><td>' + atr + '</td><td><div class="barra-progreso-fondo"><div class="barra-progreso-relleno ' + claseBarra + '" style="width:' + pct + '%;">' + pct + '%</div></div></td></tr>';
                    }
                }
                body.innerHTML = html;
            });
        }

        var rowsAllReportes = [];

        function getPeriodoFechasReportes() {
            var elIni = document.getElementById('reporteFechaIni');
            var elFin = document.getElementById('reporteFechaFin');
            if (elIni && elIni.value && elFin && elFin.value) {
                return { Fecha_Ini: elIni.value, Fecha_Fin: elFin.value };
            }
            var p = document.getElementById('filtroPeriodoReportes');
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

        function filtrarPorEstadoReportes(rows) {
            var sel = document.getElementById('filtroEstadoReportes');
            var estado = (sel && sel.value) ? sel.value : 'pendientes';
            if (estado === 'todos') return rows;
            return rows.filter(function (r) {
                var e = (r.Tar_Estado || '').trim();
                if (estado === 'finalizados') return e === 'Finalizada';
                if (estado === 'pendientes') return e !== 'Finalizada';
                return true;
            });
        }

        function filtrarPorPeriodoReportes(rows) {
            var pf = getPeriodoFechasReportes();
            if (!pf.Fecha_Ini || !pf.Fecha_Fin) return rows;
            return rows.filter(function (r) {
                var fec = r.Tar_Fecha_Inicio;
                if (!fec || fec === '0000-00-00') return false;
                return fec >= pf.Fecha_Ini && fec <= pf.Fecha_Fin;
            });
        }

        function actualizarMiniDashboardReportes(rows) {
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
            var dash = document.getElementById('miniDashboardReportes');
            if (dash) {
                dash.style.display = total > 0 ? 'grid' : 'none';
                var el;
                if (el = document.getElementById('stat-reportes-total')) el.textContent = total;
                if (el = document.getElementById('stat-reportes-completadas')) el.textContent = completadas;
                if (el = document.getElementById('stat-reportes-proceso')) el.textContent = proceso;
                if (el = document.getElementById('stat-reportes-atrasadas')) el.textContent = atrasadas;
                if (el = document.getElementById('stat-reportes-avance')) el.textContent = avanceProm + '%';
            }
        }

        function renderTablaAsignacionesAvances(rows) {
            var body = document.getElementById('bodyAsignacionesAvances');
            if (!body) return;
            var html = '';
            if (rows.length === 0) {
                html = '<tr><td colspan="10" class="text-muted">' + (rowsAllReportes.length > 0 ? 'No hay tareas que coincidan con los filtros.' : 'No hay tareas asignadas.') + '</td></tr>';
            } else {
                for (var i = 0; i < rows.length; i++) {
                    var r = rows[i];
                    var titulo = (r.Tar_Titulo || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    var descripcion = (r.Tar_Descripcion || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/\n/g, ' ');
                    var descripcionShort = descripcion.length > 80 ? descripcion.substring(0, 80) + '…' : descripcion;
                    var descripcionCell = descripcion ? '<span class="col-descripcion" title="' + descripcion.replace(/"/g, '&quot;') + '">' + descripcionShort + '</span>' : '<span class="text-muted">-</span>';
                    var empleado = (r.Empleado_Nombre || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    var fechaIni = (r.Tar_Fecha_Inicio && r.Tar_Fecha_Inicio !== '0000-00-00') ? r.Tar_Fecha_Inicio : '-';
                    var fechaFin = (r.Tar_Fecha_Fin && r.Tar_Fecha_Fin !== '0000-00-00') ? r.Tar_Fecha_Fin : '-';
                    var fechaCulm = (r.Tar_Fecha_Culminacion && r.Tar_Fecha_Culminacion !== '0000-00-00') ? r.Tar_Fecha_Culminacion : '-';
                    var pctVal = r.Ava_Porcentaje != null ? (parseInt(r.Ava_Porcentaje, 10) || 0) : null;
                    var pctHtml;
                    if (pctVal != null) {
                        var claseBarra = pctVal >= 70 ? 'alto' : (pctVal >= 40 ? 'medio' : 'bajo');
                        pctHtml = '<div class="barra-progreso-fondo"><div class="barra-progreso-relleno ' + claseBarra + '" style="width:' + pctVal + '%;">' + pctVal + '%</div></div>';
                    } else {
                        pctHtml = '-';
                    }
                    var fechaAsig = r.Tas_Fecha_Asignacion || '';
                    var tarCod = (r.Tar_Cod != null) ? r.Tar_Cod : '';
                    var btnVer = '<button type="button" class="btn btn-info btn-xs btn-ver-avances" data-tar-cod="' + tarCod + '" data-titulo="' + titulo.replace(/"/g, '&quot;') + '" data-empleado="' + (empleado || '').replace(/"/g, '&quot;') + '" title="Ver formulario de avances del empleado"><i class="glyphicon glyphicon-list-alt"></i> Ver avances</button>';
                    html += '<tr><td>' + titulo + '</td><td class="col-descripcion">' + descripcionCell + '</td><td>' + empleado + '</td>' + estadoCellHtml(r.Tar_Estado) + '<td>' + fechaIni + '</td><td>' + fechaFin + '</td><td>' + fechaCulm + '</td><td>' + pctHtml + '</td><td>' + fechaAsig + '</td><td>' + btnVer + '</td></tr>';
                }
            }
            body.innerHTML = html;
            bindVerAvances();
        }

        function aplicarFiltrosReportesYRenderizar() {
            var rows = filtrarPorEstadoReportes(filtrarPorPeriodoReportes(rowsAllReportes));
            actualizarMiniDashboardReportes(rows);
            renderTablaAsignacionesAvances(rows);
        }

        function refreshAsignacionesAvances() {
            var filtroPer = document.getElementById('filtroPerCodReportes');
            var perCod = (filtroPer && filtroPer.value) ? filtroPer.value : '';
            var pf = getPeriodoFechasReportes();
            var url = urlBase + '?listarAsignacionesConAvance=1';
            if (perCod) url += '&Per_Cod=' + encodeURIComponent(perCod);
            if (pf.Fecha_Ini) url += '&Fecha_Ini=' + encodeURIComponent(pf.Fecha_Ini);
            if (pf.Fecha_Fin) url += '&Fecha_Fin=' + encodeURIComponent(pf.Fecha_Fin);
            request('GET', url, null, function (resp) {
                rowsAllReportes = (resp && resp.rows) ? resp.rows : [];
                aplicarFiltrosReportesYRenderizar();
            });
        }

        function abrirReporte(formato) {
            var filtroPer = document.getElementById('filtroPerCodReportes');
            var perCod = (filtroPer && filtroPer.value) ? filtroPer.value : '';
            var pf = getPeriodoFechasReportes();
            var url = urlBase + '?reporteTareas' + formato + '=1';
            if (perCod) url += '&Per_Cod=' + encodeURIComponent(perCod);
            if (pf.Fecha_Ini) url += '&Fecha_Ini=' + encodeURIComponent(pf.Fecha_Ini);
            if (pf.Fecha_Fin) url += '&Fecha_Fin=' + encodeURIComponent(pf.Fecha_Fin);
            window.open(url, '_blank', 'noopener');
        }

        function refreshEmpleadosFiltroReportes() {
            request('GET', urlBase + '?listarEmpleados=1', null, function (resp) {
                var rows = (resp && resp.rows) ? resp.rows : [];
                var sel = document.getElementById('filtroPerCodReportes');
                if (!sel) return;
                var val = sel.value;
                sel.innerHTML = '<option value="">Todos los empleados</option>';
                for (var i = 0; i < rows.length; i++) {
                    var r = rows[i];
                    var opt = document.createElement('option');
                    opt.value = r.Per_Cod || '';
                    opt.textContent = (r.Nombre || '') + (r.Prs_Ced ? ' (' + r.Prs_Ced + ')' : '');
                    sel.appendChild(opt);
                }
                if (val) sel.value = val;
            });
        }

        function bindVerAvances() {
            var btns = document.querySelectorAll('#bodyAsignacionesAvances .btn-ver-avances');
            for (var i = 0; i < btns.length; i++) {
                btns[i].onclick = (function (tarCod, titulo, empleado) {
                    return function () { openModalAvances(tarCod, titulo, empleado); };
                })(btns[i].getAttribute('data-tar-cod'), btns[i].getAttribute('data-titulo') || '', btns[i].getAttribute('data-empleado') || '');
            }
        }

        function openModalAvances(tarCod, titulo, empleado) {
            if (!tarCod) return;
            var titleEl = document.getElementById('modalAvancesTareaTitle');
            var resumenEl = document.getElementById('modalAvancesTareaResumen');
            var bodyEl = document.getElementById('modalAvancesTareaBody');
            var vacioEl = document.getElementById('modalAvancesTareaVacio');
            if (titleEl) titleEl.textContent = 'Avances - ' + (titulo || 'Tarea') + (empleado ? ' - ' + empleado : '');
            if (resumenEl) resumenEl.innerHTML = '<span class="text-muted">Cargando…</span>';
            if (bodyEl) bodyEl.innerHTML = '';
            if (vacioEl) vacioEl.style.display = 'none';
            var modal = document.getElementById('modalAvancesTarea');
            if (modal) {
                public $ = window.jQuery || window.$;
                var cerrarModalAvances = function () {
                    modal.style.display = 'none';
                    modal.classList.remove('in');
                    document.body.classList.remove('modal-open');
                    var b = document.getElementById('modalAvancesTarea-backdrop');
                    if (b && b.parentNode) b.parentNode.removeChild(b);
                };
                if ($ && typeof $.fn !== 'undefined' && typeof $.fn.modal === 'function') {
                    $(modal).modal('show');
                } else {
                    modal.style.display = 'block';
                    modal.classList.add('in');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                    var backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade in';
                    backdrop.id = 'modalAvancesTarea-backdrop';
                    backdrop.onclick = cerrarModalAvances;
                    document.body.appendChild(backdrop);
                    var btnClose = modal.querySelector('[data-dismiss="modal"], .close');
                    if (btnClose) btnClose.onclick = function (ev) { ev.preventDefault(); cerrarModalAvances(); };
                }
            }

            request('GET', urlBase + '?avancePorTarea=1&Tar_Cod=' + encodeURIComponent(tarCod), null, function (d) {
                var row = (d && d.row) ? d.row : null;
                var resumen = '';
                if (row) {
                    var pct = row.Ava_Porcentaje != null ? (parseInt(row.Ava_Porcentaje, 10) || 0) : 0;
                    var desc = (row.Ava_Descripcion || '').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                    var fec = row.Ava_Fecha || '-';
                    resumen = '<strong>Avance actual:</strong> ' + pct + '% &nbsp;|&nbsp; <strong>Fecha:</strong> ' + fec + (desc ? ' &nbsp;|&nbsp; <strong>Comentario:</strong> ' + desc : '');
                } else {
                    resumen = '<span class="text-muted">Sin avance registrado aún.</span>';
                }
                if (resumenEl) resumenEl.innerHTML = resumen;
                if (row && row.Ava_Cod) {
                    request('GET', urlBase + '?listarAdjuntosAvance=1&Ava_Cod=' + encodeURIComponent(row.Ava_Cod), null, function (adj) {
                        var adjRows = (adj && adj.rows) ? adj.rows : [];
                        var adjCont = document.getElementById('modalAvancesTareaAdjuntos');
                        var adjLista = document.getElementById('modalAvancesTareaAdjuntosLista');
                        if (adjCont && adjLista) {
                            if (adjRows.length > 0) {
                                var urlAdj = '../adjuntos/avances/';
                                var html = '';
                                for (var a = 0; a < adjRows.length; a++) {
                                    var ruta = (adjRows[a].Adj_Ruta || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                                    var nombre = (adjRows[a].Adj_Nombre || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                                    html += '<a href="' + urlAdj + ruta + '" target="_blank" title="' + nombre + '"><img src="' + urlAdj + ruta + '" alt="" style="max-height:60px; max-width:100px; margin:4px; border:1px solid #ccc; border-radius:4px;" /></a> ';
                                }
                                adjLista.innerHTML = html;
                                adjCont.style.display = 'block';
                            } else {
                                adjLista.innerHTML = '';
                                adjCont.style.display = 'none';
                            }
                        }
                    });
                } else {
                    var adjCont = document.getElementById('modalAvancesTareaAdjuntos');
                    var adjLista = document.getElementById('modalAvancesTareaAdjuntosLista');
                    if (adjCont && adjLista) { adjLista.innerHTML = ''; adjCont.style.display = 'none'; }
                }
            });

            request('GET', urlBase + '?avancesPorTarea=1&Tar_Cod=' + encodeURIComponent(tarCod), null, function (d) {
                var rows = (d && d.rows) ? d.rows : [];
                if (bodyEl) {
                    bodyEl.innerHTML = '';
                    if (rows.length === 0) {
                        if (vacioEl) vacioEl.style.display = 'block';
                    } else {
                        for (var j = 0; j < rows.length; j++) {
                            var a = rows[j];
                            var usu = (a.Usuario_Nombre || '-').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                            var pct = a.Ava_Porcentaje != null ? (parseInt(a.Ava_Porcentaje, 10) || 0) : 0;
                            var desc = (a.Ava_Descripcion || '-').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, ' ');
                            var fec = a.Ava_Fecha || '-';
                            bodyEl.innerHTML += '<tr><td>' + usu + '</td><td>' + pct + '%</td><td>' + desc + '</td><td>' + fec + '</td></tr>';
                        }
                    }
                }
            });
        }

        var btnMetricas = document.getElementById('btnRefrescarMetricas');
        if (btnMetricas) btnMetricas.addEventListener('click', refreshMetricas);
        var btnRefrescarAsigAv = document.getElementById('btnRefrescarAsignacionesAvances');
        if (btnRefrescarAsigAv) btnRefrescarAsigAv.addEventListener('click', refreshAsignacionesAvances);
        var btnExportarPDF = document.getElementById('btnExportarPDF');
        var btnExportarExcel = document.getElementById('btnExportarExcel');
        if (btnExportarPDF) btnExportarPDF.addEventListener('click', function () { abrirReporte('PDF'); });
        if (btnExportarExcel) btnExportarExcel.addEventListener('click', function () { abrirReporte('Excel'); });

        var filtroPerReportes = document.getElementById('filtroPerCodReportes');
        if (filtroPerReportes) filtroPerReportes.addEventListener('change', refreshAsignacionesAvances);
        var filtroEstadoReportes = document.getElementById('filtroEstadoReportes');
        if (filtroEstadoReportes) filtroEstadoReportes.addEventListener('change', aplicarFiltrosReportesYRenderizar);
        var filtroPeriodoReportes = document.getElementById('filtroPeriodoReportes');
        if (filtroPeriodoReportes) filtroPeriodoReportes.addEventListener('change', refreshAsignacionesAvances);
        var reporteFechaIni = document.getElementById('reporteFechaIni');
        var reporteFechaFin = document.getElementById('reporteFechaFin');
        if (reporteFechaIni) reporteFechaIni.addEventListener('change', refreshAsignacionesAvances);
        if (reporteFechaFin) reporteFechaFin.addEventListener('change', refreshAsignacionesAvances);

        var tabReportes = document.querySelector('a[href="#tab-reportes"]');
        if (tabReportes) {
            tabReportes.addEventListener('click', function () {
                refreshMetricas();
                refreshEmpleadosFiltroReportes();
                refreshAsignacionesAvances();
            });
        }

        var tabAsignacion = document.querySelector('a[href="#tab-asignacion"]');
        if (tabAsignacion) {
            tabAsignacion.addEventListener('click', function () {
                refreshEmpleadosSelect();
                refreshAsignaciones();
            });
        }
        // Si al cargar la página la pestaña activa es Asignación, cargar asignaciones
        var paneAsignacion = document.getElementById('tab-asignacion');
        if (paneAsignacion && paneAsignacion.classList.contains('active')) {
            refreshAsignaciones();
        }

        function getPeriodoFechas() {
            var p = document.getElementById('dashboardPeriodo');
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

        var chartTareasEstado = null;
        function refreshDashboardIndicadores() {
            var pf = getPeriodoFechas();
            var q = 'dashboardIndicadores=1&Emp_Cod=' + sesEmpCod;
            if (pf.Fecha_Ini) q += '&Fecha_Ini=' + encodeURIComponent(pf.Fecha_Ini);
            if (pf.Fecha_Fin) q += '&Fecha_Fin=' + encodeURIComponent(pf.Fecha_Fin);
            request('GET', urlBase + '?' + q, null, function (d) {
                var tot = d.Total_Tareas != null ? parseInt(d.Total_Tareas, 10) : 0;
                var com = d.Completadas != null ? parseInt(d.Completadas, 10) : 0;
                var atr = d.Atrasadas != null ? parseInt(d.Atrasadas, 10) : 0;
                var enProc = Math.max(0, tot - com);
                var k1 = document.getElementById('kpi-total');
                var k2 = document.getElementById('kpi-completadas');
                var k3 = document.getElementById('kpi-atrasadas');
                var k4 = document.getElementById('kpi-rendimiento');
                if (k1) k1.textContent = tot;
                if (k2) k2.textContent = (d.Pct_Completadas != null ? d.Pct_Completadas : 0) + '%';
                if (k3) k3.textContent = (d.Pct_Atrasadas != null ? d.Pct_Atrasadas : 0) + '%';
                if (k4) k4.textContent = (d.Rendimiento_Promedio != null ? d.Rendimiento_Promedio : 0) + '%';
                var g1 = document.getElementById('graf-completadas');
                var g2 = document.getElementById('graf-atrasadas');
                var g3 = document.getElementById('graf-proceso');
                var sumChart = com + atr + enProc;
                if (g1) g1.textContent = com + (sumChart > 0 ? ' (' + Math.round(100 * com / sumChart) + '%)' : '');
                if (g2) g2.textContent = atr + (sumChart > 0 ? ' (' + Math.round(100 * atr / sumChart) + '%)' : '');
                if (g3) g3.textContent = enProc + (sumChart > 0 ? ' (' + Math.round(100 * enProc / sumChart) + '%)' : '');
                var data = [com, atr, enProc];
                if (typeof Chart !== 'undefined') {
                    var ctx = document.getElementById('chartTareasEstado');
                    if (ctx) {
                        if (chartTareasEstado) chartTareasEstado.destroy();
                        chartTareasEstado = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Completadas', 'Atrasadas', 'En proceso'],
                                datasets: [{
                                    data: data,
                                    backgroundColor: ['#10b981', '#ef4444', '#64748b'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                cutout: '65%',
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: function(ctx) {
                                                var tot = ctx.dataset.data.reduce(function(a,b){return a+b;}, 0);
                                                var pct = tot > 0 ? Math.round(100 * ctx.raw / tot) : 0;
                                                return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                }
                var ult = document.getElementById('kpi-ultima-act');
                if (ult) ult.textContent = 'Actualizado hace un momento';
            });
        }

        function refreshTareasAtencion() {
            var body = document.getElementById('bodyTareasAtencion');
            if (!body) return;
            request('GET', urlBase + '?listarTareasAtencion=1', null, function (resp) {
                var rows = (resp && resp.rows) ? resp.rows : [];
                var html = '';
                if (rows.length === 0) {
                    html = '<tr><td colspan="6" class="text-muted">No hay tareas que requieran atención.</td></tr>';
                } else {
                    for (var i = 0; i < rows.length; i++) {
                        var r = rows[i];
                        var titulo = (r.Tar_Titulo || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                        var empleado = (r.Empleado_Nombre || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                        var fechaFin = (r.Tar_Fecha_Fin && r.Tar_Fecha_Fin !== '0000-00-00') ? r.Tar_Fecha_Fin : '-';
                        var pctVal = r.Ava_Porcentaje != null ? (parseInt(r.Ava_Porcentaje, 10) || 0) : null;
                        var pctHtml;
                        if (pctVal != null) {
                            var claseBarra = pctVal >= 70 ? 'alto' : (pctVal >= 40 ? 'medio' : 'bajo');
                            pctHtml = '<div class="barra-progreso-fondo"><div class="barra-progreso-relleno ' + claseBarra + '" style="width:' + pctVal + '%;">' + pctVal + '%</div></div>';
                        } else {
                            pctHtml = '-';
                        }
                        var tipo = (r.Tipo_Atencion || '').replace(/&/g, '&amp;');
                        var tipoLabel = tipo === 'atrasada' ? '<span class="label label-danger">Atrasada</span>' : (tipo === 'proxima' ? '<span class="label label-warning">Próxima</span>' : tipo);
                        html += '<tr><td>' + titulo + '</td><td>' + empleado + '</td>' + estadoCellHtml(r.Tar_Estado) + '<td>' + fechaFin + '</td><td>' + pctHtml + '</td><td>' + tipoLabel + '</td></tr>';
                    }
                }
                body.innerHTML = html;
            });
        }

        function abrirModalDetalleKpi(tipo, titulo) {
            var pf = getPeriodoFechas();
            var q = 'listarTareasKpi=1&Tipo=' + encodeURIComponent(tipo) + '&Emp_Cod=' + sesEmpCod;
            if (pf.Fecha_Ini) q += '&Fecha_Ini=' + encodeURIComponent(pf.Fecha_Ini);
            if (pf.Fecha_Fin) q += '&Fecha_Fin=' + encodeURIComponent(pf.Fecha_Fin);
            request('GET', urlBase + '?' + q, null, function (resp) {
                var rows = (resp && resp.rows) ? resp.rows : [];
                var body = document.getElementById('modalDetalleKpiBody');
                var titleEl = document.getElementById('modalDetalleKpiTitle');
                if (titleEl) titleEl.textContent = titulo || 'Detalle de tareas';
                if (!body) return;
                var html = '';
                if (rows.length === 0) {
                    html = '<tr><td colspan="6" class="text-muted">No hay tareas.</td></tr>';
                } else {
                    for (var i = 0; i < rows.length; i++) {
                        var r = rows[i];
                        var tit = (r.Tar_Titulo || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                        var emp = (r.Empleado_Nombre || '-').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                        var ini = (r.Tar_Fecha_Inicio && r.Tar_Fecha_Inicio !== '0000-00-00') ? r.Tar_Fecha_Inicio : '-';
                        var fin = (r.Tar_Fecha_Fin && r.Tar_Fecha_Fin !== '0000-00-00') ? r.Tar_Fecha_Fin : '-';
                        var culm = (r.Tar_Fecha_Culminacion && r.Tar_Fecha_Culminacion !== '0000-00-00') ? r.Tar_Fecha_Culminacion : '-';
                        html += '<tr><td>' + tit + '</td><td>' + emp + '</td>' + estadoCellHtml(r.Tar_Estado) + '<td>' + ini + '</td><td>' + fin + '</td><td>' + culm + '</td></tr>';
                    }
                }
                body.innerHTML = html;
                var modal = document.getElementById('modalDetalleKpi');
                if (modal) {
                    public $ = window.jQuery || window.$;
                    var cerrarModal = function () {
                        modal.style.display = 'none';
                        modal.classList.remove('in');
                        document.body.classList.remove('modal-open');
                        var b = document.getElementById('modalDetalleKpi-backdrop');
                        if (b && b.parentNode) b.parentNode.removeChild(b);
                    };
                    if ($ && typeof $.fn !== 'undefined' && typeof $.fn.modal === 'function') {
                        $(modal).modal('show');
                    } else {
                        modal.style.display = 'block';
                        modal.classList.add('in');
                        modal.setAttribute('aria-hidden', 'false');
                        document.body.classList.add('modal-open');
                        var backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade in';
                        backdrop.id = 'modalDetalleKpi-backdrop';
                        backdrop.onclick = cerrarModal;
                        document.body.appendChild(backdrop);
                        var btnClose = modal.querySelector('[data-dismiss="modal"], .close');
                        if (btnClose) btnClose.onclick = function (ev) { ev.preventDefault(); cerrarModal(); };
                    }
                }
            });
        }

        var btnActualizarInd = document.getElementById('btnActualizarIndicadores');
        if (btnActualizarInd) btnActualizarInd.addEventListener('click', function () { refreshDashboardIndicadores(); refreshTareasAtencion(); });
        var selPeriodo = document.getElementById('dashboardPeriodo');
        if (selPeriodo) selPeriodo.addEventListener('change', function () { refreshDashboardIndicadores(); refreshTareasAtencion(); });

        var tabDashboard = document.querySelector('a[href="#tab-dashboard"]');
        if (tabDashboard) {
            tabDashboard.addEventListener('click', function () {
                refreshDashboardIndicadores();
                refreshTareasAtencion();
            });
        }

        var accesosRapidos = document.querySelectorAll('.accesos-rapidos a[data-tab]');
        for (var i = 0; i < accesosRapidos.length; i++) {
            accesosRapidos[i].addEventListener('click', function (e) {
                var tab = this.getAttribute('data-tab');
                if (tab) {
                    e.preventDefault();
                    var tabLink = document.querySelector('a[href="#' + tab + '"]');
                    if (tabLink) tabLink.click();
                }
            });
        }

        var kpiDashboard = document.getElementById('kpi-dashboard');
        if (kpiDashboard) {
            kpiDashboard.addEventListener('click', function (e) {
                var card = e.target.closest ? e.target.closest('.stat-clickable') : (function () {
                    var n = e.target; while (n && n !== kpiDashboard) { if (n.classList && n.classList.contains('stat-clickable')) return n; n = n.parentNode; } return null;
                })();
                if (!card) return;
                var tipo = card.getAttribute('data-tipo') || 'all';
                var label = card.querySelector('.stat-label');
                var titulo = (label && label.textContent) ? 'Detalle: ' + label.textContent.trim() : 'Detalle de tareas';
                if (tipo === 'completadas') abrirModalDetalleKpi('completadas', titulo);
                else if (tipo === 'atrasadas') abrirModalDetalleKpi('atrasadas', titulo);
                else abrirModalDetalleKpi('all', titulo);
            });
        }

        if (document.getElementById('tab-dashboard') && document.getElementById('tab-dashboard').classList.contains('active')) {
            refreshDashboardIndicadores();
            refreshTareasAtencion();
        }
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
