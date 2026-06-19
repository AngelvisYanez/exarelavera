<?php

/**
 * @abstract CRUD de contratos asociados a plantas (manifiesto_contratos)
 * @author Exa-Contable
 * @version 1.1
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_con_contratos.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Contratos_Planta($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Contratos_Planta;

$hoy = date('Y-m-d');

// ==================== AJAX ====================

if (isset($listarContratosAjax)) {
    $parms = array(
        'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
        'filtro' => isset($_GET['filtro']) ? $_GET['filtro'] : 'p',
        'Mco_Est' => isset($_GET['Mco_Est']) ? $_GET['Mco_Est'] : '',
        'Mco_Vig' => isset($_GET['Mco_Vig']) ? $_GET['Mco_Vig'] : ''
    );
    $data = $obBD_con1->getArrayConsulta(1, $parms, $obBD_conexion);
    $response = array('rows' => $data, 'records' => count($data));
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

if (isset($getContratoAjax)) {
    $Mco_Cod = isset($_GET['Mco_Cod']) ? (int)$_GET['Mco_Cod'] : 0;
    $data = $obBD_con1->getRowConsulta(2, array('Mco_Cod' => $Mco_Cod), $obBD_conexion);
    utf8_encode_deep($data);
    echo json_encode($data);
    exit();
}

if (isset($plantaAjax)) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;
    $parms = array(
        'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
        'filtro' => isset($_GET['op_opciones']) ? $_GET['op_opciones'] : 'p'
    );
    $contar = $obBD_con1->getRowConsulta(7, $parms, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $response = $pagination['data'];
    if ($contar['total'] > 0) {
        $parms['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(6, $parms, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
    exit();
}

if (isset($guardarContratoAjax)) {
    $resp = array('success' => false);
    try {
        $Pla_Cod = isset($_POST['Pla_Cod']) ? (int)$_POST['Pla_Cod'] : 0;
        $Mco_Not = isset($_POST['Mco_Not']) ? trim($_POST['Mco_Not']) : '';
        $Mco_Fap = isset($_POST['Mco_Fap']) ? trim($_POST['Mco_Fap']) : '';
        $Mco_Fca = isset($_POST['Mco_Fca']) ? trim($_POST['Mco_Fca']) : '';
        $Mco_Est = isset($_POST['Mco_Est']) ? trim($_POST['Mco_Est']) : 'A';

        if ($Pla_Cod <= 0) {
            throw new Exception('Seleccione una planta.');
        }
        if ($Mco_Not === '') {
            throw new Exception('Ingrese el nombre del notario.');
        }
        if ($Mco_Fap === '' || $Mco_Fca === '') {
            throw new Exception('Ingrese las fechas de apertura y caducidad.');
        }
        if ($Mco_Fca < $Mco_Fap) {
            throw new Exception('La fecha de caducidad no puede ser anterior a la fecha de apertura.');
        }
        if (!in_array($Mco_Est, array('A', 'I'), true)) {
            throw new Exception('Estado no valido.');
        }

        $valores = array(
            'Pla_Cod' => $Pla_Cod,
            'Mco_Num' => isset($_POST['Mco_Num']) ? trim($_POST['Mco_Num']) : '',
            'Mco_Not' => $Mco_Not,
            'Mco_Fap' => $Mco_Fap,
            'Mco_Fca' => $Mco_Fca,
            'Mco_Obs' => isset($_POST['Mco_Obs']) ? trim($_POST['Mco_Obs']) : '',
            'Mco_Est' => $Mco_Est
        );

        $obBD_con1->inicio_transaccion($obBD_conexion);

        if (isset($_POST['Mco_Cod']) && !empty($_POST['Mco_Cod']) && (int)$_POST['Mco_Cod'] > 0) {
            $valores['Mco_Cod'] = (int)$_POST['Mco_Cod'];
            $obBD_con1->operacionobBD(4, $valores, $obBD_conexion);
            $resp['Mco_Cod'] = $valores['Mco_Cod'];
            $resp['message'] = 'Contrato actualizado correctamente.';
        } else {
            $valores['Usu_Cod'] = $Ses_Usu_Cod;
            $obBD_con1->operacionobBD(3, $valores, $obBD_conexion);
            $resp['Mco_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
            $resp['message'] = 'Contrato registrado correctamente.';
        }

        if ($obBD_con1->Error != 0) {
            throw new Exception('Error al guardar contrato: ' . $obBD_con1->MsgError);
        }

        $Mco_Cod = (int)$resp['Mco_Cod'];

        $Emp_Cod = man_con_contratos_emp_cod();
        man_con_contratos_asegurar_directorio($Mco_Cod, $Emp_Cod);

        $n = man_con_contratos_procesar_respaldos($obBD_con1, $obBD_conexion, $Mco_Cod, $Emp_Cod);
        if ($n > 0) {
            $resp['message'] .= ' Se adjuntaron ' . $n . ' PDF(s).';
        }

        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
        } else {
            $resp['message'] = 'Error al guardar: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
    }
    echo json_encode($resp);
    exit();
}

if (isset($guardarRespaldosAjax)) {
    $resp = array('success' => false);
    try {
        $Mco_Cod = isset($_POST['Mco_Cod']) ? (int)$_POST['Mco_Cod'] : 0;
        if ($Mco_Cod <= 0) {
            throw new Exception('Contrato no valido.');
        }

        $contrato = $obBD_con1->getRowConsulta(2, array('Mco_Cod' => $Mco_Cod), $obBD_conexion);
        if (empty($contrato)) {
            throw new Exception('Contrato no encontrado.');
        }
        if (isset($contrato['Mco_Est']) && $contrato['Mco_Est'] === 'I') {
            throw new Exception('No se pueden agregar respaldos a un contrato inactivo.');
        }

        $archivos = man_con_contratos_normalizar_archivos('Mcd_File');
        $tieneEliminar = isset($_POST['Mcd_Del']) && (
            (is_array($_POST['Mcd_Del']) && count($_POST['Mcd_Del']) > 0) ||
            (!is_array($_POST['Mcd_Del']) && (int)$_POST['Mcd_Del'] > 0)
        );
        if (count($archivos) === 0 && !$tieneEliminar) {
            throw new Exception('Agregue al menos un PDF o marque respaldos para eliminar.');
        }

        $obBD_con1->inicio_transaccion($obBD_conexion);
        $Emp_Cod = man_con_contratos_emp_cod();
        man_con_contratos_asegurar_directorio($Mco_Cod, $Emp_Cod);
        $n = man_con_contratos_procesar_respaldos($obBD_con1, $obBD_conexion, $Mco_Cod, $Emp_Cod);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);

        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
            $resp['Mco_Cod'] = $Mco_Cod;
            if ($n > 0) {
                $resp['message'] = 'Se guardaron ' . $n . ' PDF(s) correctamente.';
            } else {
                $resp['message'] = 'Respaldos actualizados correctamente.';
            }
        } else {
            $resp['message'] = 'Error al guardar respaldos: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
    }
    echo json_encode($resp);
    exit();
}

if (isset($inactivarContratoAjax)) {
    $resp = array('success' => false);
    try {
        $Mco_Cod = isset($_POST['Mco_Cod']) ? (int)$_POST['Mco_Cod'] : 0;
        if ($Mco_Cod <= 0) {
            throw new Exception('Contrato no valido.');
        }
        $obBD_con1->inicio_transaccion($obBD_conexion);
        $obBD_con1->operacionobBD(5, array('Mco_Cod' => $Mco_Cod), $obBD_conexion);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
            $resp['message'] = 'Contrato inactivado correctamente.';
        } else {
            $resp['message'] = 'Error al inactivar: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
    }
    echo json_encode($resp);
    exit();
}

function man_con_contratos_emp_cod() {
    global $Ses_Emp_Cod;
    $emp = isset($Ses_Emp_Cod) ? (int)$Ses_Emp_Cod : 0;
    if ($emp <= 0 && isset($_SESSION['Ses_Emp_Cod'])) {
        $emp = (int)$_SESSION['Ses_Emp_Cod'];
    }
    if ($emp <= 0) {
        throw new Exception('No se pudo identificar la empresa de la sesion.');
    }
    return $emp;
}

function man_con_contratos_crear_directorio($dir) {
    $dir = rtrim(str_replace('\\', '/', $dir), '/') . '/';
    if (file_exists($dir) && is_dir($dir)) {
        return $dir;
    }
    if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new Exception('No se pudo crear el directorio de respaldos PDF.');
    }
    @chmod($dir, 0777);
    return $dir;
}

function man_con_contratos_ruta_empresa($Emp_Cod = null) {
    if ($Emp_Cod === null) {
        $Emp_Cod = man_con_contratos_emp_cod();
    }
    $base = dirname(__FILE__) . '/../RECURSOS/contratos/';
    man_con_contratos_crear_directorio($base);
    $dir = $base . (int)$Emp_Cod . '/';
    man_con_contratos_crear_directorio($dir);
    return $dir;
}

function man_con_contratos_asegurar_directorio($Mco_Cod, $Emp_Cod = null) {
    return man_con_contratos_ruta_respaldo($Mco_Cod, $Emp_Cod);
}

function man_con_contratos_ruta_respaldo($Mco_Cod, $Emp_Cod = null) {
    $dirEmp = man_con_contratos_ruta_empresa($Emp_Cod);
    $dir = $dirEmp . (int)$Mco_Cod . '/';
    man_con_contratos_crear_directorio($dir);
    return $dir;
}

function man_con_contratos_ruta_relativa($Emp_Cod, $Mco_Cod, $arcNom) {
    return 'contratos/' . (int)$Emp_Cod . '/' . (int)$Mco_Cod . '/' . $arcNom;
}

function man_con_contratos_resolver_ruta_fisica($Mcd_Url) {
    return dirname(__FILE__) . '/../RECURSOS/' . ltrim(str_replace('\\', '/', $Mcd_Url), '/');
}

function man_con_contratos_nombre_md5($file, $dir) {
    $hash = md5_file($file['tmp_name']);
    if ($hash === false) {
        $hash = md5(uniqid((string)mt_rand(), true));
    }
    $arcNom = $hash . '.pdf';
    if (file_exists($dir . $arcNom)) {
        $arcNom = md5($hash . microtime(true)) . '.pdf';
    }
    return $arcNom;
}

function man_con_contratos_guardar_pdf($file, $Mco_Cod, $Emp_Cod = null) {
    if ($Emp_Cod === null) {
        $Emp_Cod = man_con_contratos_emp_cod();
    }
    man_con_contratos_validar_pdf($file);
    $origNom = basename($file['name']);
    $dir = man_con_contratos_ruta_respaldo($Mco_Cod, $Emp_Cod);
    $arcNom = man_con_contratos_nombre_md5($file, $dir);
    $destino = $dir . $arcNom;
    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        throw new Exception('No se pudo guardar el archivo: ' . $origNom);
    }
    return array(
        'Mcd_Nom' => $origNom,
        'Mcd_Url' => man_con_contratos_ruta_relativa($Emp_Cod, $Mco_Cod, $arcNom),
        'destino' => $destino
    );
}

function man_con_contratos_procesar_respaldos($obBD_con1, $obBD_conexion, $Mco_Cod, $Emp_Cod) {
    $adjuntos = 0;

    if (isset($_POST['Mcd_Del'])) {
        $dels = is_array($_POST['Mcd_Del']) ? $_POST['Mcd_Del'] : array($_POST['Mcd_Del']);
        foreach ($dels as $Mcd_Cod) {
            $Mcd_Cod = (int)$Mcd_Cod;
            if ($Mcd_Cod <= 0) {
                continue;
            }
            $row = $obBD_con1->getRowConsulta(11, array('Mcd_Cod' => $Mcd_Cod), $obBD_conexion);
            if (empty($row) || (int)$row['Mco_Cod'] !== (int)$Mco_Cod) {
                continue;
            }
            $obBD_con1->operacionobBD(10, array('Mcd_Cod' => $Mcd_Cod), $obBD_conexion);
            if ($obBD_con1->Error != 0) {
                throw new Exception('Error al eliminar respaldo: ' . $obBD_con1->MsgError);
            }
            $ruta = man_con_contratos_resolver_ruta_fisica($row['Mcd_Url']);
            if (file_exists($ruta)) {
                @unlink($ruta);
            }
        }
    }

    $tips = isset($_POST['Mcd_Tip']) ? $_POST['Mcd_Tip'] : array();
    if (!is_array($tips)) {
        $tips = ($tips !== '') ? array($tips) : array();
    }
    $archivos = man_con_contratos_normalizar_archivos('Mcd_File');
    if (count($archivos) > 0) {
        if (count($archivos) !== count($tips)) {
            throw new Exception('Cada PDF debe tener un titulo asociado.');
        }
        foreach ($archivos as $i => $file) {
            $Mcd_Tip = isset($tips[$i]) ? trim($tips[$i]) : '';
            if ($Mcd_Tip === '') {
                throw new Exception('Ingrese el titulo de cada respaldo PDF.');
            }
            $pdf = man_con_contratos_guardar_pdf($file, $Mco_Cod, $Emp_Cod);
            $obBD_con1->operacionobBD(9, array(
                'Mco_Cod' => $Mco_Cod,
                'Mcd_Tip' => $Mcd_Tip,
                'Mcd_Nom' => $pdf['Mcd_Nom'],
                'Mcd_Url' => $pdf['Mcd_Url']
            ), $obBD_conexion);
            if ($obBD_con1->Error != 0) {
                @unlink($pdf['destino']);
                throw new Exception('Error al registrar respaldo: ' . $obBD_con1->MsgError);
            }
            $adjuntos++;
        }
    }

    return $adjuntos;
}

function man_con_contratos_nombre_descarga($row) {
    if (!empty($row['Mcd_Tip'])) {
        $base = trim($row['Mcd_Tip']);
    } elseif (!empty($row['Mcd_Nom'])) {
        $base = trim($row['Mcd_Nom']);
    } else {
        $base = 'documento_' . (int)$row['Mcd_Cod'];
    }
    $base = preg_replace('/[\\\\\/:*?"<>|]/', '_', $base);
    $base = preg_replace('/\s+/', '_', trim($base));
    if ($base === '') {
        $base = 'documento_' . (int)$row['Mcd_Cod'];
    }
    if (strtolower(substr($base, -4)) !== '.pdf') {
        $base .= '.pdf';
    }
    return $base;
}

function man_con_contratos_enviar_archivo($ruta, $nombre, $mime, $eliminarDespues = false) {
    if (!file_exists($ruta) || !is_readable($ruta)) {
        header('HTTP/1.0 404 Not Found');
        echo 'Archivo no encontrado.';
        exit();
    }
    $nombreSeguro = str_replace(array('"', "\r", "\n"), '', $nombre);
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $nombreSeguro . '"');
    header('Content-Length: ' . filesize($ruta));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    readfile($ruta);
    if ($eliminarDespues) {
        @unlink($ruta);
    }
    exit();
}

function man_con_contratos_validar_pdf($file) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $dump = var_export($file, true);
        throw new Exception('Error al subir el archivo PDF. Detalle del archivo: ' . $dump);
    }
    $nombre = isset($file['name']) ? $file['name'] : '';
    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new Exception('Solo se permiten archivos PDF.');
    }
}

function man_con_contratos_normalizar_archivos($key) {
    if (!isset($_FILES[$key])) {
        return array();
    }
    $f = $_FILES[$key];
    if (!is_array($f['name'])) {
        if ($f['error'] === UPLOAD_ERR_NO_FILE || $f['name'] === '') {
            return array();
        }
        return array(array(
            'name' => $f['name'],
            'type' => $f['type'],
            'tmp_name' => $f['tmp_name'],
            'error' => $f['error'],
            'size' => $f['size']
        ));
    }
    $out = array();
    foreach ($f['name'] as $i => $n) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE || $n === '') {
            continue;
        }
        $out[] = array(
            'name' => $n,
            'type' => $f['type'][$i],
            'tmp_name' => $f['tmp_name'][$i],
            'error' => $f['error'][$i],
            'size' => $f['size'][$i]
        );
    }
    return $out;
}

if (isset($listRespaldoAjax)) {
    $Mco_Cod = isset($_GET['Mco_Cod']) ? (int)$_GET['Mco_Cod'] : 0;
    $rows = ($Mco_Cod > 0) ? $obBD_con1->getArrayConsulta(8, array('Mco_Cod' => $Mco_Cod), $obBD_conexion) : array();
    $response = array('rows' => $rows, 'records' => count($rows));
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

if (isset($saveRespaldoAjax)) {
    $resp = array('success' => false);
    try {
        $Mco_Cod = isset($_POST['Mco_Cod']) ? (int)$_POST['Mco_Cod'] : 0;
        $Mcd_Tip = isset($_POST['Mcd_Tip']) ? trim($_POST['Mcd_Tip']) : '';
        if ($Mco_Cod <= 0) {
            throw new Exception('Guarde el contrato antes de adjuntar respaldos.');
        }
        if ($Mcd_Tip === '') {
            throw new Exception('Ingrese el titulo del respaldo.');
        }
        if (!isset($_FILES['Mcd_File']) || empty($_FILES['Mcd_File']['name'])) {
            throw new Exception('Seleccione un archivo PDF.');
        }

        $contrato = $obBD_con1->getRowConsulta(2, array('Mco_Cod' => $Mco_Cod), $obBD_conexion);
        if (empty($contrato)) {
            throw new Exception('Contrato no encontrado.');
        }

        $Emp_Cod = man_con_contratos_emp_cod();
        $pdf = man_con_contratos_guardar_pdf($_FILES['Mcd_File'], $Mco_Cod, $Emp_Cod);

        $obBD_con1->inicio_transaccion($obBD_conexion);
        $obBD_con1->operacionobBD(9, array(
            'Mco_Cod' => $Mco_Cod,
            'Mcd_Tip' => $Mcd_Tip,
            'Mcd_Nom' => $pdf['Mcd_Nom'],
            'Mcd_Url' => $pdf['Mcd_Url']
        ), $obBD_conexion);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
            $resp['Mcd_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
            $resp['message'] = 'PDF adjuntado correctamente.';
        } else {
            @unlink($pdf['destino']);
            $resp['message'] = 'Error al registrar respaldo: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
    }
    echo json_encode($resp);
    exit();
}

if (isset($deleteRespaldoAjax)) {
    $resp = array('success' => false);
    try {
        $Mcd_Cod = isset($_POST['Mcd_Cod']) ? (int)$_POST['Mcd_Cod'] : 0;
        if ($Mcd_Cod <= 0) {
            throw new Exception('Respaldo no valido.');
        }
        $row = $obBD_con1->getRowConsulta(11, array('Mcd_Cod' => $Mcd_Cod), $obBD_conexion);
        if (empty($row)) {
            throw new Exception('Respaldo no encontrado.');
        }
        $obBD_con1->inicio_transaccion($obBD_conexion);
        $obBD_con1->operacionobBD(10, array('Mcd_Cod' => $Mcd_Cod), $obBD_conexion);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $ruta = man_con_contratos_resolver_ruta_fisica($row['Mcd_Url']);
            if (file_exists($ruta)) {
                @unlink($ruta);
            }
            $resp['success'] = true;
            $resp['message'] = 'Respaldo eliminado correctamente.';
        } else {
            $resp['message'] = 'Error al eliminar: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
    }
    echo json_encode($resp);
    exit();
}

if (isset($viewRespaldoAjax)) {
    $Mcd_Cod = isset($_GET['Mcd_Cod']) ? (int)$_GET['Mcd_Cod'] : 0;
    $row = $obBD_con1->getRowConsulta(11, array('Mcd_Cod' => $Mcd_Cod), $obBD_conexion);
    if (empty($row)) {
        header('HTTP/1.0 404 Not Found');
        echo 'Archivo no encontrado';
        exit();
    }
    $ruta = man_con_contratos_resolver_ruta_fisica($row['Mcd_Url']);
    if (!file_exists($ruta)) {
        header('HTTP/1.0 404 Not Found');
        echo 'Archivo no encontrado en disco';
        exit();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($row['Mcd_Nom']) . '"');
    header('Content-Length: ' . filesize($ruta));
    readfile($ruta);
    exit();
}

if (isset($listarDocumentacionAjax)) {
    $resp = array('success' => false, 'rows' => array(), 'contrato' => null, 'message' => '');
    $Mco_Cod = isset($_GET['Mco_Cod']) ? (int)$_GET['Mco_Cod'] : 0;
    if ($Mco_Cod <= 0) {
        $resp['message'] = 'Contrato no valido.';
        utf8_encode_deep($resp);
        echo json_encode($resp);
        exit();
    }

    $contrato = $obBD_con1->getRowConsulta(2, array('Mco_Cod' => $Mco_Cod), $obBD_conexion);
    if (empty($contrato)) {
        $resp['message'] = 'Contrato no encontrado.';
        utf8_encode_deep($resp);
        echo json_encode($resp);
        exit();
    }

    $rows = $obBD_con1->getArrayConsulta(8, array('Mco_Cod' => $Mco_Cod), $obBD_conexion);
    $disponibles = array();
    foreach ($rows as $row) {
        $ruta = man_con_contratos_resolver_ruta_fisica($row['Mcd_Url']);
        if (file_exists($ruta) && is_readable($ruta)) {
            $disponibles[] = $row;
        }
    }

    $resp['success'] = true;
    $resp['contrato'] = array(
        'Mco_Cod' => (int)$contrato['Mco_Cod'],
        'Pla_Nom' => isset($contrato['Pla_Nom']) ? $contrato['Pla_Nom'] : '',
        'Mco_Num' => isset($contrato['Mco_Num']) ? $contrato['Mco_Num'] : '',
        'Mco_Not' => isset($contrato['Mco_Not']) ? $contrato['Mco_Not'] : ''
    );
    $resp['rows'] = $disponibles;
    $resp['records'] = count($disponibles);
    if (count($disponibles) === 0) {
        $resp['message'] = 'El contrato no tiene documentacion PDF disponible para descargar.';
    }

    utf8_encode_deep($resp);
    echo json_encode($resp);
    exit();
}

if (isset($descargarRespaldoAjax)) {
    $Mcd_Cod = isset($_GET['Mcd_Cod']) ? (int)$_GET['Mcd_Cod'] : 0;
    if ($Mcd_Cod <= 0) {
        header('HTTP/1.0 400 Bad Request');
        echo 'Respaldo no valido.';
        exit();
    }
    $row = $obBD_con1->getRowConsulta(11, array('Mcd_Cod' => $Mcd_Cod), $obBD_conexion);
    if (empty($row)) {
        header('HTTP/1.0 404 Not Found');
        echo 'Archivo no encontrado.';
        exit();
    }
    $ruta = man_con_contratos_resolver_ruta_fisica($row['Mcd_Url']);
    if (!file_exists($ruta) || !is_readable($ruta)) {
        header('HTTP/1.0 404 Not Found');
        echo 'Archivo no encontrado en disco.';
        exit();
    }
    man_con_contratos_enviar_archivo($ruta, man_con_contratos_nombre_descarga($row), 'application/pdf');
}

if (isset($descargarContratoDocAjax)) {
    $Mco_Cod = isset($_GET['Mco_Cod']) ? (int)$_GET['Mco_Cod'] : 0;
    if ($Mco_Cod <= 0) {
        header('HTTP/1.0 400 Bad Request');
        echo 'Contrato no valido.';
        exit();
    }

    $contrato = $obBD_con1->getRowConsulta(2, array('Mco_Cod' => $Mco_Cod), $obBD_conexion);
    if (empty($contrato)) {
        header('HTTP/1.0 404 Not Found');
        echo 'Contrato no encontrado.';
        exit();
    }

    $rows = $obBD_con1->getArrayConsulta(8, array('Mco_Cod' => $Mco_Cod), $obBD_conexion);
    $archivos = array();
    $nombresUsados = array();

    foreach ($rows as $row) {
        $ruta = man_con_contratos_resolver_ruta_fisica($row['Mcd_Url']);
        if (!file_exists($ruta) || !is_readable($ruta)) {
            continue;
        }
        $nombre = man_con_contratos_nombre_descarga($row);
        $base = $nombre;
        $i = 2;
        while (isset($nombresUsados[strtolower($nombre)])) {
            $nombre = preg_replace('/\.pdf$/i', '', $base) . '_' . $i . '.pdf';
            $i++;
        }
        $nombresUsados[strtolower($nombre)] = true;
        $archivos[] = array(
            'path' => $ruta,
            'name' => $nombre
        );
    }

    if (count($archivos) === 0) {
        header('HTTP/1.0 404 Not Found');
        echo 'El contrato no tiene documentacion PDF disponible para descargar.';
        exit();
    }

    if (count($archivos) === 1) {
        man_con_contratos_enviar_archivo($archivos[0]['path'], $archivos[0]['name'], 'application/pdf');
    }

    if (!class_exists('ZipArchive')) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'El servidor no tiene habilitada la libreria ZipArchive para descargar varios archivos.';
        exit();
    }

    $etiqueta = !empty($contrato['Mco_Num']) ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $contrato['Mco_Num']) : ('ctr_' . $Mco_Cod);
    $zipNombre = 'contrato_' . $etiqueta . '_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mco_doc_' . uniqid('', true) . '.zip';
    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'No se pudo generar el archivo ZIP.';
        exit();
    }

    foreach ($archivos as $item) {
        $zip->addFile($item['path'], $item['name']);
    }
    $zip->close();

    man_con_contratos_enviar_archivo($zipPath, $zipNombre, 'application/zip', true);
}

$usuarioActual = $obBD_con1->getRowConsulta('usuarios.selectWhere', array('where' => array('usuarios.Usu_Cod' => $Ses_Usu_Cod)), $obBD_conexion);
$nombreUsuario = '';
if (!empty($usuarioActual['Usuario'])) {
    $nombreUsuario = $usuarioActual['Usuario'];
} elseif (!empty($usuarioActual['Prs_Nom'])) {
    $nombreUsuario = trim($usuarioActual['Prs_Nom'] . ' ' . (isset($usuarioActual['Prs_Ape']) ? $usuarioActual['Prs_Ape'] : ''));
}

?>
<!DOCTYPE html>
<html>
<head>
    <TITLE><?php echo 'Contratos de Plantas [EXA]'; ?></TITLE>
    <meta charset="UTF-8">
    <?php require_once('../../mascaras/model1/estilos/jqgrid5.php') ?>
    <style>
        .toolbar-contratos {
            margin-top: 10px;
            text-align: left;
        }
        .contratos-busqueda {
            margin-bottom: 6px;
            padding: 6px 10px 8px !important;
        }
        .contratos-busqueda legend {
            margin-bottom: 4px;
        }
        .contratos-filtros-wrap {
            padding: 0;
        }
        .contratos-filtros-line {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px 10px;
        }
        .contratos-filtro-inline {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
        }
        .contratos-filtro-inline > label,
        .contratos-filtro-inline > .lbl {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            margin: 0;
            white-space: nowrap;
        }
        .contratos-filtro-inline .radioset {
            display: inline-flex;
            flex-wrap: nowrap;
            gap: 2px;
        }
        .contratos-filtro-inline select.form-control {
            height: 26px;
            width: auto;
            min-width: 108px;
            max-width: 130px;
            padding: 2px 8px;
            border-radius: 4px;
            border-color: #cbd5e1;
            font-size: 11px;
            box-shadow: none;
        }
        .contratos-filtro-grow {
            flex: 1 1 260px;
            min-width: 240px;
            max-width: 480px;
        }
        .contratos-filtro-grow .contratos-search-group {
            display: flex;
            align-items: stretch;
            flex: 1 1 auto;
            min-width: 0;
            width: auto;
        }
        .contratos-filtro-grow .contratos-search-group > .form-control {
            flex: 1 1 auto;
            height: 26px;
            min-width: 0;
            width: auto;
            max-width: none;
            padding: 2px 8px;
            border-radius: 4px 0 0 4px;
            border-color: #cbd5e1;
            font-size: 11px;
            box-shadow: none;
        }
        .contratos-filtro-grow .contratos-search-actions {
            display: flex;
            flex-shrink: 0;
            align-items: stretch;
        }
        .contratos-filtro-grow .contratos-search-actions .btn {
            height: 26px;
            padding: 0 9px;
            font-size: 11px;
            font-weight: 600;
            line-height: 24px;
            border-radius: 0;
            margin-left: -1px;
        }
        .contratos-filtro-grow .contratos-search-actions .btn-default {
            background: #fff;
            border-color: #cbd5e1;
            color: #64748b;
        }
        .contratos-filtro-grow .contratos-search-actions .btn-success {
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
        }
        .contratos-filtro-sep {
            width: 1px;
            height: 22px;
            background: #e2e8f0;
            flex-shrink: 0;
        }
        @media (max-width: 992px) {
            .contratos-filtro-grow {
                max-width: none;
                width: 100%;
            }
        }
        .badge-estado-A {
            background-color: #28a745;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        .badge-estado-I {
            background-color: #999;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        .badge-vig-V {
            background-color: #28a745;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        .badge-vig-P {
            background-color: #f59e0b;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        .badge-vig-C {
            background-color: #dc3545;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        .contrato-fca-alerta {
            color: #d97706;
            font-weight: 700;
        }
        .contrato-fca-alerta .glyphicon {
            margin-right: 3px;
        }
        tr.contrato-row-por-caducar td {
            background-color: #fffbeb !important;
        }
        tr.contrato-row-caducado td {
            background-color: #fef2f2 !important;
        }
        #contratoDialog.ui-dialog-content,
        #contratoDialog .ui-dialog-content {
            padding: 0 !important;
            overflow: hidden !important;
        }
        #contratoDialog .contrato-modal-wrap {
            display: flex;
            flex-direction: column;
            background: #fff;
            overflow: hidden;
            max-width: 100%;
            box-sizing: border-box;
        }
        #contratoDialog .contrato-modal-body {
            padding: 10px 12px 4px;
            overflow: hidden;
            box-sizing: border-box;
        }
        #contratoDialog .contrato-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 6px 10px;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        #contratoDialog .contrato-form-grid .span-2 {
            grid-column: span 2;
        }
        #contratoDialog .fld {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }
        #contratoDialog .fld label {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            margin: 0;
        }
        #contratoDialog .fld label.req:after {
            content: ' *';
            color: #ef4444;
        }
        #contratoDialog .fld .form-control {
            height: 30px;
            padding: 4px 8px;
            font-size: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            box-shadow: none;
            transition: border-color .15s, box-shadow .15s;
        }
        #contratoDialog .fld textarea.form-control {
            height: 40px;
            min-height: 40px;
            max-height: 40px;
            resize: none;
            overflow-y: auto;
        }
        #contratoDialog .fld .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }
        #contratoDialog .fld-planta .input-group {
            display: flex !important;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        #contratoDialog .fld-planta .form-control {
            flex: 1 1 auto;
            min-width: 0;
            width: auto !important;
            float: none !important;
            border-radius: 6px 0 0 6px;
            background: #f8fafc;
            cursor: pointer;
        }
        #contratoDialog .fld-planta .input-group-btn {
            display: flex;
            flex: 0 0 auto;
            width: auto;
            vertical-align: middle;
        }
        #contratoDialog .fld-planta .input-group-btn .btn {
            height: 30px;
            padding: 0 8px;
            border-radius: 0;
            font-size: 11px;
            float: none;
        }
        #contratoDialog .fld-planta .input-group-btn .btn:last-child {
            border-radius: 0 6px 6px 0;
        }
        #contratoDialog .contrato-divider {
            height: 1px;
            background: linear-gradient(90deg, #e2e8f0 0%, transparent 100%);
            margin: 2px 0 8px;
        }
        #contratoDialog .contrato-modal-footer {
            border-top: 1px solid #f1f5f9;
            background: #fafbfc;
            padding: 7px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-shrink: 0;
            box-sizing: border-box;
        }
        #contratoDialog .contrato-meta {
            font-size: 10px;
            color: #94a3b8;
        }
        #contratoDialog .contrato-meta strong {
            color: #475569;
            font-weight: 600;
        }
        #contratoDialog .contrato-meta .meta-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 4px;
            margin-right: 6px;
        }
        #contratoDialog .contrato-actions {
            display: flex;
            gap: 6px;
        }
        #contratoDialog .contrato-actions .btn {
            height: 28px;
            padding: 0 14px;
            font-size: 11px;
            border-radius: 6px;
            font-weight: 600;
        }
        #contratoDialog .contrato-actions .btn-default {
            background: #fff;
            border-color: #e2e8f0;
            color: #64748b;
        }
        #contratoDialog .contrato-actions .btn-success {
            background: #2563eb;
            border-color: #2563eb;
        }
        #contratoDialog .contrato-actions .btn-success:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }
        #contratoDialog .contrato-resumen-respaldos {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 8px;
            font-size: 11px;
            color: #475569;
            line-height: 1.5;
        }
        #contratoDialog .contrato-resumen-respaldos strong {
            color: #334155;
        }
        #contratoDialog .contrato-respaldos {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #e2e8f0;
        }
        #contratoDialog .contrato-respaldos > label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            margin-bottom: 6px;
            display: block;
        }
        #contratoDialog .respaldos-toolbar {
            display: flex;
            gap: 6px;
            align-items: center;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }
        #contratoDialog .respaldos-toolbar .form-control {
            height: 28px;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 6px;
        }
        #contratoDialog .respaldos-toolbar .fld-titulo {
            flex: 1 1 140px;
            min-width: 120px;
        }
        #contratoDialog .respaldos-toolbar .fld-file {
            flex: 1 1 160px;
            min-width: 140px;
        }
        #contratoDialog .respaldos-hint {
            font-size: 10px;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        #contratoDialog .respaldos-grid-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        #contratoDialog .respaldos-grid-wrap .ui-jqgrid {
            border: none !important;
        }
        #contratoDialog .link-pdf {
            color: #2563eb;
            cursor: pointer;
            text-decoration: none;
        }
        #contratoDialog .link-pdf:hover {
            text-decoration: underline;
        }
        #documentacionDialog .doc-resumen {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 10px;
            font-size: 11px;
            color: #475569;
            line-height: 1.5;
        }
        #documentacionDialog .doc-resumen strong {
            color: #334155;
        }
        #documentacionDialog .doc-grid-wrap {
            min-height: 120px;
        }
        #documentacionDialog .doc-modal-footer {
            border-top: 1px solid #f1f5f9;
            background: #fafbfc;
            padding: 8px 12px;
            text-align: right;
        }
        #documentacionDialog .doc-modal-footer .btn {
            margin-left: 6px;
        }
    </style>
</head>
<body>
    <input type="hidden" id="Usu_Cod" value="<?php echo (int)$Ses_Usu_Cod; ?>">
    <input type="hidden" id="Usu_Nom" value="<?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" id="Hoy_Fec" value="<?php echo $hoy; ?>">

    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Contratos de Plantas</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">

            <fieldset class="exa-fieldset contratos-busqueda">
                <legend class="Titulos2">B&uacute;squeda</legend>
                <div class="contratos-filtros-wrap">
                    <div class="contratos-filtros-line">
                        <div class="contratos-filtro-inline">
                            <span class="lbl">Criterio</span>
                            <div class="radioset">
                                <input id="filtro_p" name="filtro" type="radio" value="p" checked />
                                <label for="filtro_p">Planta</label>
                                <input id="filtro_n" name="filtro" type="radio" value="n" />
                                <label for="filtro_n">N&deg; Contrato</label>
                                <input id="filtro_t" name="filtro" type="radio" value="t" />
                                <label for="filtro_t">Notario</label>
                            </div>
                        </div>
                        <span class="contratos-filtro-sep"></span>
                        <div class="contratos-filtro-inline">
                            <label for="filtro_estado">Estado</label>
                            <select id="filtro_estado" class="form-control input-xs">
                                <option value="">Todos</option>
                                <option value="A">Activos</option>
                                <option value="I">Inactivos</option>
                            </select>
                        </div>
                        <div class="contratos-filtro-inline">
                            <label for="filtro_vigencia">Vigencia</label>
                            <select id="filtro_vigencia" class="form-control input-xs">
                                <option value="">Todos</option>
                                <option value="V">Vigentes</option>
                                <option value="C">Caducados</option>
                            </select>
                        </div>
                        <div class="contratos-filtro-inline contratos-filtro-grow">
                            <label for="search_contrato">Buscar</label>
                            <div class="contratos-search-group">
                                <input type="text" id="search_contrato" class="form-control input-xs clearable" placeholder="Texto..." maxlength="50">
                                <div class="contratos-search-actions">
                                    <button type="button" id="btnLimpiarFiltros" class="btn btn-default btn-xs" title="Restablecer filtros">
                                        <span class="glyphicon glyphicon-refresh"></span>
                                    </button>
                                    <button type="button" id="btnBuscarContratosMain" class="btn btn-success btn-xs">
                                        <span class="glyphicon glyphicon-search"></span> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div style="min-height: 320px; margin-top: 6px;">
                <table id="contratosGrid"></table>
                <div id="contratosGridPager"></div>
            </div>

            <div class="toolbar-contratos">
                <button type="button" id="btnNuevoContrato" class="btn btn-primary btn-sm">
                    <i class="glyphicon glyphicon-plus"></i> Nuevo Contrato
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: formulario contrato -->
    <div id="contratoDialog" title="Contrato de Planta" style="display: none;">
        <div class="contrato-modal-wrap">
            <form id="contratoForm" class="contrato-modal-body" onsubmit="return false;">
                <input type="hidden" id="Mco_Cod" name="Mco_Cod" value="">
                <input type="hidden" id="Pla_Cod" name="Pla_Cod" value="">
                <input type="hidden" id="Mco_Est" name="Mco_Est" value="A">

                <div id="contratoResumenRespaldos" class="contrato-resumen-respaldos span-2" style="display:none;">
                    <div><strong>Planta:</strong> <span id="resPla_Nom"></span></div>
                    <div><strong>N&deg; Contrato:</strong> <span id="resMco_Num">-</span> &nbsp;|&nbsp; <strong>Notario:</strong> <span id="resMco_Not"></span></div>
                </div>

                <div class="contrato-campos-contrato">
                <div class="contrato-form-grid">
                    <div class="fld fld-planta span-2">
                        <label class="req">Planta</label>
                        <div class="input-group">
                            <input type="text" id="Pla_Nom" name="Pla_Nom" class="form-control dialogSearch" readonly
                                placeholder="Buscar planta..." tabindex="1">
                            <span class="input-group-btn">
                                <button type="button" id="btnBuscarPlantaContrato" class="btn btn-info btn-xs" title="Buscar">
                                    <span class="glyphicon glyphicon-search"></span>
                                </button>
                                <button type="button" id="btnLimpiarPlantaContrato" class="btn btn-default btn-xs" title="Limpiar">
                                    <span class="glyphicon glyphicon-remove"></span>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="contrato-divider"></div>

                <div class="contrato-form-grid">
                    <div class="fld">
                        <label>N&deg; Contrato</label>
                        <input type="text" id="Mco_Num" name="Mco_Num" class="form-control" maxlength="20" placeholder="CTR-2026-001">
                    </div>
                    <div class="fld">
                        <label class="req">Notario(a)</label>
                        <input type="text" id="Mco_Not" name="Mco_Not" class="form-control" maxlength="50" required placeholder="Nombre del notario">
                    </div>
                    <div class="fld">
                        <label class="req">F. Apertura</label>
                        <input type="date" id="Mco_Fap" name="Mco_Fap" class="form-control" required>
                    </div>
                    <div class="fld">
                        <label class="req">F. Caducidad</label>
                        <input type="date" id="Mco_Fca" name="Mco_Fca" class="form-control" required>
                    </div>
                    <div class="fld span-2">
                        <label>Observaci&oacute;n</label>
                        <textarea id="Mco_Obs" name="Mco_Obs" class="form-control" rows="2" placeholder="Notas adicionales (opcional)"></textarea>
                    </div>
                </div>
                </div>

                <div class="contrato-respaldos span-2">
                    <label>Respaldos PDF</label>
                    <div id="respaldosHint" class="respaldos-hint">Agregue PDFs al listado; se guardaran al pulsar Guardar.</div>
                    <div id="respaldosToolbar" class="respaldos-toolbar">
                        <input type="text" id="Mcd_Tip_New" class="form-control fld-titulo" maxlength="100" placeholder="Titulo del documento">
                        <input type="file" id="Mcd_File_New" class="form-control fld-file" accept=".pdf,application/pdf" multiple>
                        <button type="button" id="btnAgregarRespaldo" class="btn btn-primary btn-xs">
                            <span class="glyphicon glyphicon-plus"></span> Agregar
                        </button>
                    </div>
                    <div class="respaldos-grid-wrap">
                        <table id="respaldosGrid"></table>
                    </div>
                </div>
            </form>

            <div class="contrato-modal-footer">
                <div class="contrato-meta">
                    <span id="contratoMetaCod" style="display:none;" class="meta-badge">#<span id="contratoMetaCodVal"></span></span>
                    <span id="contratoMetaUser"><strong id="Usuario_Reg"><?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                </div>
                <div class="contrato-actions">
                    <button type="button" id="btnDescargarDocumentacion" class="btn btn-info btn-xs" style="display:none;" title="Ver y descargar documentacion PDF">
                        <span class="glyphicon glyphicon-download-alt"></span> Documentacion
                    </button>
                    <button type="button" class="btn btn-default btn-xs" onclick="$('#contratoDialog').dialog('close');">Cancelar</button>
                    <button type="button" id="btnGuardarRespaldos" class="btn btn-success btn-xs" style="display:none;">
                        <span class="glyphicon glyphicon-floppy-disk"></span> Guardar respaldos
                    </button>
                    <button type="submit" form="contratoForm" id="btnGuardarContrato" class="btn btn-success btn-xs">
                        <span class="glyphicon glyphicon-ok"></span> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: documentacion del contrato -->
    <div id="documentacionDialog" title="Documentacion del contrato" style="display: none;">
        <div class="doc-resumen">
            <div><strong>Contrato #</strong> <span id="docResumenCod"></span></div>
            <div><strong>Planta:</strong> <span id="docResumenPlanta"></span></div>
            <div><strong>N&deg; Contrato:</strong> <span id="docResumenNum">-</span> &nbsp;|&nbsp; <strong>Notario:</strong> <span id="docResumenNot"></span></div>
        </div>
        <div class="doc-grid-wrap">
            <table id="documentacionGrid"></table>
        </div>
        <div class="doc-modal-footer">
            <button type="button" class="btn btn-default btn-xs" onclick="$('#documentacionDialog').dialog('close');">Cerrar</button>
            <button type="button" id="btnDescargarTodoZip" class="btn btn-success btn-xs">
                <span class="glyphicon glyphicon-download-alt"></span> Descargar todo (ZIP)
            </button>
        </div>
    </div>

    <!-- Modal: buscar plantas (createSearchDialog) -->
    <div id="plantaDialog" title="Buscar Planta" style="display: none;">
        <form class="form-horizontal normal"></form>
    </div>

    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/man_val_con_contratos.js?v=32"></script>

    <!-- Div oculto para imprimir reporte de contratos -->
    <div id="imprimirContratos" class="contratos-reporte-wrap" style="display: none;">
        <style type="text/css">
            #imprimirContratos.contratos-reporte-wrap,
            #imprimirContratos .contratos-reporte-sheet {
                font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
                color: #1e293b;
                font-size: 11px;
                line-height: 1.35;
            }
            #imprimirContratos .contratos-reporte-sheet {
                width: 1030px;
                margin: 0 auto;
                padding: 4px 2px 8px;
            }
            #imprimirContratos .TITULO_REPORTE,
            #imprimirContratos .Titulos2 {
                color: #6b8cae !important;
                font-size: 15px !important;
                font-weight: 700 !important;
                letter-spacing: 0.4px;
                margin: 8px 0 2px !important;
            }
            #imprimirContratos .subtitle {
                display: block;
                color: #94a3b8 !important;
                font-size: 11px !important;
                font-weight: 500 !important;
                margin-bottom: 8px;
            }
            #imprimirContratos .contratos-reporte-table-wrap {
                margin: 12px 0 0;
                padding: 0;
            }
            #imprimirContratos table.contratos-reporte-tabla {
                width: 100% !important;
                border-collapse: separate !important;
                border-spacing: 0 !important;
                table-layout: auto !important;
                font-size: 10.5px !important;
                border: none !important;
            }
            #imprimirContratos table.contratos-reporte-tabla thead th {
                background: #edf2f7 !important;
                color: #2d3748 !important;
                font-weight: 700 !important;
                text-transform: uppercase;
                font-size: 9px !important;
                letter-spacing: 0.5px;
                padding: 10px 8px !important;
                border: none !important;
                border-bottom: 2px solid #4299e1 !important;
                white-space: nowrap;
                vertical-align: bottom;
            }
            #imprimirContratos table.contratos-reporte-tabla tbody td {
                padding: 8px 8px !important;
                border: none !important;
                border-bottom: 1px solid #e2e8f0 !important;
                vertical-align: middle;
                color: #4a5568;
            }
            #imprimirContratos table.contratos-reporte-tabla tbody tr:last-child td {
                border-bottom: 2px solid #cbd5e0 !important;
            }
            #imprimirContratos table.contratos-reporte-tabla tbody tr:hover td {
                background-color: #f7fafc !important;
            }
            #imprimirContratos table.contratos-reporte-tabla tbody tr.rep-row-inactivo td {
                color: #a0aec0 !important;
            }
            #imprimirContratos table.contratos-reporte-tabla .rep-num {
                text-align: center;
                font-weight: 600;
                color: #a0aec0;
                width: 32px;
            }
            #imprimirContratos table.contratos-reporte-tabla .rep-cod {
                text-align: center;
                font-weight: 600;
                color: #718096;
            }
            #imprimirContratos table.contratos-reporte-tabla .rep-planta {
                font-weight: 600;
                color: #2d3748;
            }
            #imprimirContratos table.contratos-reporte-tabla .rep-contrato {
                font-weight: 600;
                color: #2b6cb0;
            }
            #imprimirContratos table.contratos-reporte-tabla .rep-fecha {
                text-align: center;
                color: #718096;
                white-space: nowrap;
            }
            #imprimirContratos table.contratos-reporte-tabla .rep-vig {
                text-align: center;
            }
            #imprimirContratos table.contratos-reporte-tabla .rep-empty {
                text-align: center;
                padding: 20px !important;
                color: #a0aec0;
                font-style: italic;
                border-bottom: 2px solid #cbd5e0 !important;
            }
            #imprimirContratos .rep-txt-vig { color: #276749; font-weight: 600; }
            #imprimirContratos .rep-txt-por { color: #b7791f; font-weight: 600; }
            #imprimirContratos .rep-txt-cad { color: #c53030; font-weight: 600; }
            @media print {
                #imprimirContratos .contratos-reporte-sheet { width: 100%; padding: 0; }
                #imprimirContratos table.contratos-reporte-tabla thead th {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
        <div class="contratos-reporte-sheet">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE CONTRATOS DE PLANTAS', '<span class="subtitle" id="contratosReporteSubtitulo">Listado de contratos registrados</span>', $obBD_conexion) ?>
            <div class="contratos-reporte-table-wrap">
                <table id="tablaReporteContratos" class="contratos-reporte-tabla" cellspacing="0" cellpadding="0"></table>
            </div>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>

    <!-- Cargador Visual / Loader -->
    <div id="loader" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 9999; text-align: center; padding-top: 20%;">
        <div style="display: inline-block; padding: 25px 35px; background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
            <i class="fa fa-spinner fa-spin fa-3x fa-fw" style="color: #334a5f;"></i>
            <div style="margin-top: 15px; font-weight: bold; color: #334a5f; font-size: 14px;">Procesando solicitud...</div>
        </div>
    </div>

    <?php
    $obBD_con1->liberar();
    $obBD_conexion->cerrar();
    ?>
</body>
</html>
