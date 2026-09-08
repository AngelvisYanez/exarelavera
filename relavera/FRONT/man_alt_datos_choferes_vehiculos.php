<?php

/**
 * Formulario: Datos de Choferes y Vehículos
 * Agrupa la gestión completa de Empresas de Transporte, Choferes y Vehículos con Máscara Model3.
 * Ubicación: relavera/FRONT/man_alt_datos_choferes_vehiculos.php
 * @author Sistema EXA
 * @version 2.8
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_datos_choferes_vehiculos.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once(__DIR__ . '/../LOGICA/relavera_whatsapp_utils.php');
require_once(__DIR__ . '/../LOGICA/relavera_notif_mail_utils.php');

$obBD_conexion = new Class_Log_Conexion_Datos_Choferes_Vehiculos($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Choferes_Vehiculos;
require_once(__DIR__ . '/../COMPONENTES/man_sanciones_ajax.inc.php');

// Cargar catálogos iniciales
$transportes = $obBD_con1->getArrayConsulta(1, array($Ses_Emp_Cod), $obBD_conexion);
$obBD_con1->utf8_change_param($transportes);

$plantas = $obBD_con1->getArrayConsulta(2, array(), $obBD_conexion);
$obBD_con1->utf8_change_param($plantas);

$ciudades = $obBD_con1->getArrayConsulta(19, array(), $obBD_conexion);
$obBD_con1->utf8_change_param($ciudades);







/* ==========================================================================
   FUNCIÓN AUXILIAR DE COMPRESIÓN DE IMÁGENES EN SERVIDOR (PHP GD)
   ========================================================================== */
function optimizarYComprimirImagen($sourcePath, $targetPath, $maxDim = 1920, $quality = 85)
{
    list($width, $height, $type) = @getimagesize($sourcePath);
    if (!$width || !$height) return false;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImg = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $srcImg = @imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $srcImg = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return move_uploaded_file($sourcePath, $targetPath);
    }

    if (!$srcImg) return move_uploaded_file($sourcePath, $targetPath);

    // Calcular nuevas dimensiones conservando la relación de aspecto
    $ratio = $width / $height;
    if ($width > $maxDim || $height > $maxDim) {
        if ($ratio > 1) {
            $newWidth = $maxDim;
            $newHeight = round($maxDim / $ratio);
        } else {
            $newHeight = $maxDim;
            $newWidth = round($maxDim * $ratio);
        }
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    $dstImg = imagecreatetruecolor($newWidth, $newHeight);

    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
    }

    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    $res = imagejpeg($dstImg, $targetPath, $quality);

    imagedestroy($srcImg);
    imagedestroy($dstImg);

    return $res && file_exists($targetPath);
}

/**
 * Une dos imágenes (frente + reverso) en un solo JPEG vertical.
 */
function unirDosImagenesVertical($pathFrente, $pathReverso, $targetPath, $maxWidth = 1600, $quality = 85)
{
    $load = function ($path) {
        $info = @getimagesize($path);
        if (!$info) {
            return null;
        }
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $img = @imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $img = @imagecreatefrompng($path);
                break;
            case IMAGETYPE_WEBP:
                $img = @imagecreatefromwebp($path);
                break;
            case IMAGETYPE_GIF:
                $img = @imagecreatefromgif($path);
                break;
            default:
                return null;
        }
        if (!$img) {
            return null;
        }
        return array('img' => $img, 'w' => imagesx($img), 'h' => imagesy($img));
    };

    $a = $load($pathFrente);
    $b = $load($pathReverso);
    if (!$a || !$b) {
        if ($a) {
            imagedestroy($a['img']);
        }
        if ($b) {
            imagedestroy($b['img']);
        }
        return false;
    }

    $scale = function ($src, $maxW) {
        $w = $src['w'];
        $h = $src['h'];
        if ($w > $maxW) {
            $nw = $maxW;
            $nh = max(1, (int) round($h * ($maxW / $w)));
        } else {
            $nw = $w;
            $nh = $h;
        }
        $dst = imagecreatetruecolor($nw, $nh);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src['img'], 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src['img']);
        return array('img' => $dst, 'w' => $nw, 'h' => $nh);
    };

    $a = $scale($a, $maxWidth);
    $b = $scale($b, $maxWidth);
    $gap = 12;
    $outW = max($a['w'], $b['w']);
    $outH = $a['h'] + $gap + $b['h'];
    $out = imagecreatetruecolor($outW, $outH);
    $bg = imagecolorallocate($out, 245, 247, 250);
    imagefill($out, 0, 0, $bg);

    $xA = (int) (($outW - $a['w']) / 2);
    $xB = (int) (($outW - $b['w']) / 2);
    imagecopy($out, $a['img'], $xA, 0, 0, 0, $a['w'], $a['h']);
    imagecopy($out, $b['img'], $xB, $a['h'] + $gap, 0, 0, $b['w'], $b['h']);

    imagedestroy($a['img']);
    imagedestroy($b['img']);

    $dir = dirname($targetPath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $ok = imagejpeg($out, $targetPath, $quality);
    imagedestroy($out);
    return $ok && file_exists($targetPath);
}

/**
 * Procesa un archivo subido (PDF o imagen) hacia carpeta de vehículos.
 */
function guardarArchivoMatriculaVehiculo($fileInfo, $placa, $prefijoNombre)
{
    if (empty($fileInfo) || !isset($fileInfo['error']) || $fileInfo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al recibir archivo de matrícula (código ' . $fileInfo['error'] . ').');
    }

    $placaSafe = preg_replace('/[^A-Za-z0-9_-]/', '', strtoupper($placa));
    if ($placaSafe === '') {
        $placaSafe = 'SINPLACA';
    }
    $baseDir = dirname(__DIR__) . '/RECURSOS/archivos_adjuntos/vehiculos/' . $placaSafe . '/';
    if (!is_dir($baseDir) && !@mkdir($baseDir, 0777, true) && !is_dir($baseDir)) {
        throw new Exception('No se pudo crear carpeta de adjuntos del vehículo.');
    }

    $origName = isset($fileInfo['name']) ? $fileInfo['name'] : 'archivo';
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $tmp = $fileInfo['tmp_name'];
    $mime = isset($fileInfo['type']) ? $fileInfo['type'] : '';
    $isPdf = ($ext === 'pdf' || $mime === 'application/pdf');
    $isImage = in_array($ext, array('jpg', 'jpeg', 'png', 'webp', 'gif'), true) || strpos($mime, 'image/') === 0;

    if (!$isPdf && !$isImage) {
        throw new Exception('El adjunto de matrícula debe ser PDF o imagen.');
    }

    $filename = $prefijoNombre . '_' . date('Ymd_His') . ($isPdf ? '.pdf' : '.jpg');
    $target = $baseDir . $filename;

    if ($isPdf) {
        if (!move_uploaded_file($tmp, $target)) {
            throw new Exception('No se pudo guardar el PDF de matrícula.');
        }
    } else {
        if (!optimizarYComprimirImagen($tmp, $target, 1920, 85)) {
            if (!move_uploaded_file($tmp, $target)) {
                throw new Exception('No se pudo guardar la imagen de matrícula.');
            }
        }
    }

    return '../RECURSOS/archivos_adjuntos/vehiculos/' . $placaSafe . '/' . $filename;
}

// Función auxiliar para responder JSON limpio sin interferencia de buffers o advertencias de PHP
function responderJsonLimpio($response)
{
    if (class_exists('DebugBar')) {
        @DebugBar::sendDataInHeaders(true);
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}

/* ==========================================================================
   MANEJADORES AJAX (AJAX DISPATCHER)
   ========================================================================== */

// Check if POST data was discarded due to post_max_size limit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $maxPost = ini_get('post_max_size');
    $contentMB = number_format($_SERVER['CONTENT_LENGTH'] / (1024 * 1024), 2);
    responderJsonLimpio(array(
        'success' => false,
        'message' => "El tamaño total del envío ($contentMB MB) superó el límite 'post_max_size' ($maxPost) configurado en el servidor PHP. Reduzca la cantidad o tamaño de archivos adjuntos."
    ));
}

// 1. Listar Empresas de Transporte
if (isset($_REQUEST['listEmpresasTransporteGridAjax'])) {
    $req = array_merge($_GET, $_POST);
    $page = isset($req['page']) ? intval($req['page']) : 1;
    $rows = isset($req['rows']) ? intval($req['rows']) : 50;

    if ($rows <= 0 || $rows >= 99999) {
        $rows = 999999;
    }
    if ($page < 1) {
        $page = 1;
    }

    $params = array($Ses_Emp_Cod);
    if (isset($req['op_opciones']) && isset($req['search']) && !empty($req['search'])) {
        $params['op_opciones'] = $req['op_opciones'];
        $params['search'] = $req['search'];
    }

    $contar = $obBD_con1->getRowConsulta(5, $params, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $response = $pagination['data'];
    if ($contar['total'] > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(5, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    responderJsonLimpio($response);
}

// 2. Guardar Empresa de Transporte
if (isset($_POST['saveEmpresaTransporteAjax'])) {
    $resp = array('success' => false);
    $Mat_Cod = isset($_POST['Mat_Cod']) ? $_POST['Mat_Cod'] : '';
    $Mat_Des = isset($_POST['Mat_Des']) ? addslashes($_POST['Mat_Des']) : '';
    $Mat_Mae = isset($_POST['Mat_Mae']) ? addslashes($_POST['Mat_Mae']) : '';
    $Mat_Tel = isset($_POST['Mat_Tel']) ? addslashes($_POST['Mat_Tel']) : '';
    $Mat_Pco = isset($_POST['Mat_Pco']) ? addslashes($_POST['Mat_Pco']) : '';
    $Mat_Dir = isset($_POST['Mat_Dir']) ? addslashes($_POST['Mat_Dir']) : '';

    $data = array(
        'Mat_Des' => $Mat_Des,
        'Mat_Mae' => $Mat_Mae,
        'Mat_Tel' => $Mat_Tel,
        'Mat_Pco' => $Mat_Pco,
        'Mat_Dir' => $Mat_Dir,
        'Emp_Cod' => $Ses_Emp_Cod,
        'Mat_Est' => 'A'
    );
    if (!empty($Mat_Cod)) {
        $data['where'] = array('Mat_Cod' => $Mat_Cod);
        $obBD_con1->operacionobBD('manifiesto_transporte.update', $data, $obBD_conexion);
    } else {
        $obBD_con1->operacionobBD('manifiesto_transporte.insert', $data, $obBD_conexion);
    }
    $resp['success'] = ($obBD_con1->Error == 0);
    if (!$resp['success']) $resp['message'] = $obBD_con1->MsgError;
    responderJsonLimpio($resp);
}

// 3. Anular Empresa de Transporte
if (isset($_POST['anularEmpresaTransporteAjax'])) {
    $resp = array('success' => false);
    $Mat_Cod = isset($_POST['Mat_Cod']) ? $_POST['Mat_Cod'] : '';
    if (!empty($Mat_Cod)) {
        $obBD_con1->operacionobBD('manifiesto_transporte.update', array('Mat_Est' => 'I', 'where' => array('Mat_Cod' => $Mat_Cod)), $obBD_conexion);
        $resp['success'] = ($obBD_con1->Error == 0);
    }
    responderJsonLimpio($resp);
}



// 4. Listar Choferes
if (isset($_REQUEST['listChoferesGridAjax'])) {
    $req = array_merge($_GET, $_POST);
    $page = isset($req['page']) ? intval($req['page']) : 1;
    $rows = isset($req['rows']) ? intval($req['rows']) : 50;

    if ($rows <= 0 || $rows >= 99999) {
        $rows = 999999;
    }
    if ($page < 1) {
        $page = 1;
    }

    $params = array($Ses_Emp_Cod);
    if (isset($req['op_opciones'])) {
        $params['op_opciones'] = $req['op_opciones'];
    }
    if (isset($req['mostrar_datos'])) {
        $params['mostrar_datos'] = $req['mostrar_datos'];
    }
    if (isset($req['foto_cedula'])) {
        $params['foto_cedula'] = $req['foto_cedula'];
    }
    if (isset($req['foto_licencia'])) {
        $params['foto_licencia'] = $req['foto_licencia'];
    }
    if (isset($req['search']) && !empty($req['search'])) {
        $params['search'] = $req['search'];
    }

    $contar = $obBD_con1->getRowConsulta(3, $params, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $response = $pagination['data'];
    if ($contar['total'] > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(3, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    responderJsonLimpio($response);
}

// 5. Buscar persona / chofer por Cédula / Identificación
if (isset($_GET['buscarPersonaCedulaAjax'])) {
    $resp = array('success' => true, 'existe' => false, 'esChofer' => false);
    $ced = isset($_GET['Prs_Ced']) ? preg_replace('/[^a-zA-Z0-9]/', '', trim($_GET['Prs_Ced'])) : '';
    if (!empty($ced)) {
        // Verificar si ya existe como chofer en la empresa
        $chofer = $obBD_con1->getRowConsulta(15, array($Ses_Emp_Cod, $ced), $obBD_conexion);
        if (!empty($chofer)) {
            $resp['existe'] = true;
            $resp['esChofer'] = true;
            $resp['chofer'] = $chofer;
            $resp['persona'] = array(
                'Prs_Cod' => $chofer['Prs_Cod'],
                'Prs_Ced' => $chofer['Prs_Ced'],
                'Prs_Nom' => $chofer['Prs_Nom'],
                'Prs_Ape' => $chofer['Prs_Ape'],
                'Prs_Tel' => !empty($chofer['Prs_Tel_Base']) ? $chofer['Prs_Tel_Base'] : $chofer['Cho_Tel'],
                'Prs_Cor' => !empty($chofer['Prs_Cor']) ? $chofer['Prs_Cor'] : $chofer['Cho_Cor'],
                'Prs_Dir' => !empty($chofer['Prs_Dir_Base']) ? $chofer['Prs_Dir_Base'] : $chofer['Cho_Dir'],
                'Prs_Fec' => $chofer['Prs_Fec']
            );
            $obBD_con1->utf8_change_param($resp['chofer']);
            $obBD_con1->utf8_change_param($resp['persona']);
        } else {
            // Verificar si existe en tabla persona
            $persona = $obBD_con1->getRowConsulta(6, array($ced), $obBD_conexion);
            if (!empty($persona)) {
                $resp['existe'] = true;
                $resp['esChofer'] = false;
                $resp['persona'] = $persona;
                $obBD_con1->utf8_change_param($resp['persona']);
            }
        }
    }
    responderJsonLimpio($resp);
}

// 5.5. Obtener Chofer Completo por ID para Edición de 100% de Campos
if (isset($_GET['getChoferByIdAjax'])) {
    $resp = array('success' => false);
    $Cho_Cod = isset($_GET['Cho_Cod']) ? $_GET['Cho_Cod'] : '';
    if (!empty($Cho_Cod)) {
        $chofer = $obBD_con1->getRowConsulta(8, array($Cho_Cod), $obBD_conexion);
        if (!empty($chofer)) {
            $resp['success'] = true;
            $resp['chofer'] = $chofer;
            $obBD_con1->utf8_change_param($resp['chofer']);
        }
    }
    responderJsonLimpio($resp);
}



// 6. Guardar Chofer Completo
if (isset($_POST['saveChoferAjax'])) {
    $resp = array('success' => false);
    $Cho_Cod = isset($_POST['Cho_Cod']) ? trim($_POST['Cho_Cod']) : '';
    $Prs_Cod = isset($_POST['Prs_Cod']) ? trim($_POST['Prs_Cod']) : '';
    $Cho_Ced = isset($_POST['Cho_Ced']) ? preg_replace('/[^a-zA-Z0-9]/', '', trim($_POST['Cho_Ced'])) : '';
    $Prs_Nom = isset($_POST['Prs_Nom']) ? addslashes($_POST['Prs_Nom']) : '';
    $Prs_Ape = isset($_POST['Prs_Ape']) ? addslashes($_POST['Prs_Ape']) : '';
    $Prs_Fec = !empty($_POST['Prs_Fec']) ? $_POST['Prs_Fec'] : null;

    $Cho_Nac = isset($_POST['Cho_Nac']) ? addslashes($_POST['Cho_Nac']) : 'Ecuatoriana';
    $Cho_Eci = isset($_POST['Cho_Eci']) ? $_POST['Cho_Eci'] : 'Soltero/a';
    $Pla_Cod = isset($_POST['Pla_Cod']) ? $_POST['Pla_Cod'] : '';
    $Cho_Car = isset($_POST['Cho_Car']) ? addslashes($_POST['Cho_Car']) : 'Chofer';
    $Cho_Est = isset($_POST['Cho_Est']) ? $_POST['Cho_Est'] : 'A';
    $Cho_Tco = isset($_POST['Cho_Tco']) ? $_POST['Cho_Tco'] : 'Indefinido';

    $Cho_Tli = isset($_POST['Cho_Tli']) ? $_POST['Cho_Tli'] : '';
    $Cho_Nli = isset($_POST['Cho_Nli']) ? preg_replace('/[^a-zA-Z0-9]/', '', trim($_POST['Cho_Nli'])) : '';
    $Cho_Fei = !empty($_POST['Cho_Fei']) ? $_POST['Cho_Fei'] : null;
    $Cho_Cli = !empty($_POST['Cho_Cli']) ? $_POST['Cho_Cli'] : null;

    $Cho_Tsa = isset($_POST['Cho_Tsa']) ? $_POST['Cho_Tsa'] : '';
    $Cho_Tel = isset($_POST['Cho_Tel']) ? trim($_POST['Cho_Tel']) : '';
    $Cho_Cor = isset($_POST['Cho_Cor']) ? trim($_POST['Cho_Cor']) : '';
    $Cho_Dir = isset($_POST['Cho_Dir']) ? addslashes($_POST['Cho_Dir']) : '';
    $Cho_Nem = isset($_POST['Cho_Nem']) ? addslashes($_POST['Cho_Nem']) : '';
    $Cho_Tem = isset($_POST['Cho_Tem']) ? trim($_POST['Cho_Tem']) : '';

    // Datos de Capacitaciones
    $Cap_Bas_Obli = isset($_POST['Cap_Bas_Obli']) ? $_POST['Cap_Bas_Obli'] : 'N';
    $Cap_Bas_Fec = !empty($_POST['Cap_Bas_Fec']) ? $_POST['Cap_Bas_Fec'] : null;
    $Cap_Bas_Vig = !empty($_POST['Cap_Bas_Vig']) ? $_POST['Cap_Bas_Vig'] : null;

    $Cap_Mat_Peli = isset($_POST['Cap_Mat_Peli']) ? $_POST['Cap_Mat_Peli'] : 'N';
    $Cap_Mat_Fec = !empty($_POST['Cap_Mat_Fec']) ? $_POST['Cap_Mat_Fec'] : null;
    $Cap_Mat_Vig = !empty($_POST['Cap_Mat_Vig']) ? $_POST['Cap_Mat_Vig'] : null;

    $baseDir = dirname(__DIR__) . '/RECURSOS/archivos_adjuntos/choferes/';
    if (!file_exists($baseDir)) {
        @mkdir($baseDir, 0777, true);
    }
    $uploadDir = $baseDir . $Cho_Ced . '/';
    if (!file_exists($uploadDir)) {
        if (!@mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            throw new Exception("No se pudo crear la carpeta de destino para los archivos ($uploadDir). Verifique permisos de escritura.");
        }
    }

    $debugDetails = array(
        'Cho_Ced' => $Cho_Ced,
        'Prs_Nom' => $Prs_Nom,
        'Prs_Ape' => $Prs_Ape,
        'Cho_Tel' => $Cho_Tel,
        'Cho_Tli' => $Cho_Tli,
        'archivos_recibidos' => array()
    );

    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        // 1. Guardar o Actualizar Persona
        if (empty($Prs_Cod)) {
            $persona = $obBD_con1->getRowConsulta(6, array($Cho_Ced), $obBD_conexion);
            if (empty($persona)) {
                $datosPersona = array(
                    'Prs_Ced' => $Cho_Ced,
                    'Prs_Nom' => $Prs_Nom,
                    'Prs_Ape' => $Prs_Ape,
                    'Prs_Tel' => $Cho_Tel,
                    'Prs_Cor' => $Cho_Cor,
                    'Prs_Dir' => $Cho_Dir
                );
                if (!empty($Prs_Fec)) $datosPersona['Prs_Fec'] = $Prs_Fec;

                $obBD_con1->operacionobBD('persona.insert', $datosPersona, $obBD_conexion);
                if ($obBD_con1->Error != 0) {
                    $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                    throw new Exception("Error al guardar Persona: " . $errMsg);
                }
                $Prs_Cod = $obBD_con1->insercionid($obBD_conexion);
            } else {
                $Prs_Cod = $persona['Prs_Cod'];
            }
        }

        if (!empty($Prs_Cod)) {
            $datosPrs = array(
                'Prs_Ced' => $Cho_Ced,
                'Prs_Nom' => $Prs_Nom,
                'Prs_Ape' => $Prs_Ape,
                'Prs_Tel' => $Cho_Tel,
                'Prs_Cor' => $Cho_Cor,
                'Prs_Dir' => $Cho_Dir,
                'where' => array('Prs_Cod' => $Prs_Cod)
            );
            if (!empty($Prs_Fec)) $datosPrs['Prs_Fec'] = $Prs_Fec;
            $obBD_con1->operacionobBD('persona.update', $datosPrs, $obBD_conexion);
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al actualizar Persona: " . $errMsg);
            }
        }

        // 2. Procesar Archivos Adjuntos
        $uploadedFiles = array();
        $fileFields = array(
            'Cho_Img_Lic_Anv' => 'Licencia Anverso',
            'Cho_Img_Lic_Rev' => 'Licencia Reverso',
            'Cap_Bas_Adj'     => 'Certificado Básico',
            'Cap_Mat_Adj'     => 'Certificado Mat. Peligrosos',
            'Cap_Otr_Adj'     => 'Otros Certificados',
            'Cho_Doc_Ced'     => 'Cédula Anverso',
            'Cho_Doc_Ced_Rev' => 'Cédula Reverso',
            'Cho_Doc_Vot'     => 'Certificado Votación',
            'Cho_Doc_Fot'     => 'Foto Carnet',
            'Cho_Doc_Ldi'     => 'Licencia Digital',
            'Cho_Doc_Ant'     => 'Antecedentes Penales',
            'Cho_Doc_San'     => 'Carnet Sangre'
        );

        $maxAllowedBytes = 5 * 1024 * 1024; // 5.00 MB Máximo

        foreach ($fileFields as $field => $fieldLabel) {
            if (isset($_FILES[$field])) {
                $errCode = $_FILES[$field]['error'];
                if ($errCode !== UPLOAD_ERR_OK && $errCode !== UPLOAD_ERR_NO_FILE) {
                    $errDesc = "Error desconocido (Cód: $errCode)";
                    switch ($errCode) {
                        case UPLOAD_ERR_INI_SIZE:
                            $errDesc = "Supera el límite 'upload_max_filesize' (" . ini_get('upload_max_filesize') . ") del servidor PHP.";
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $errDesc = "Supera el límite permitido por el formulario HTML.";
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errDesc = "El archivo solo se subió parcialmente. Intente de nuevo.";
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errDesc = "Falta la carpeta temporal de subida en el servidor PHP.";
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errDesc = "Error de escritura al guardar el archivo en el disco del servidor.";
                            break;
                    }
                    throw new Exception("Error al recibir el archivo '$fieldLabel': $errDesc");
                }

                if ($errCode === UPLOAD_ERR_OK) {
                    $tmpPath = $_FILES[$field]['tmp_name'];
                    $origSize = $_FILES[$field]['size'];
                    $origName = $_FILES[$field]['name'];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    $filename = strtolower($field) . '.' . ($ext === 'pdf' ? 'pdf' : 'jpg');
                    $targetPath = $uploadDir . $filename;

                    if (in_array($ext, array('jpg', 'jpeg', 'png', 'webp'))) {
                        $compSuccess = optimizarYComprimirImagen($tmpPath, $targetPath, 1920, 85);
                        if (!$compSuccess || !file_exists($targetPath)) {
                            throw new Exception("No se pudo procesar la imagen para '$fieldLabel'. Verifique que el archivo sea una imagen válida.");
                        }

                        $finalSize = filesize($targetPath);
                        if ($finalSize > $maxAllowedBytes) {
                            @unlink($targetPath);
                            $finalMB = number_format($finalSize / (1024 * 1024), 2);
                            throw new Exception("La imagen de '$fieldLabel', aun después de ser optimizada, pesa $finalMB MB y supera el límite máximo de 5.00 MB.");
                        }

                        $debugDetails['archivos_recibidos'][$field] = array(
                            'campo' => $fieldLabel,
                            'nombre' => $origName,
                            'tamano_original' => number_format($origSize / 1024, 2) . ' KB',
                            'tamano_optimizado' => number_format($finalSize / 1024, 2) . ' KB',
                            'ruta' => $targetPath
                        );
                    } else if ($ext === 'pdf') {
                        if ($origSize > $maxAllowedBytes) {
                            $pdfMB = number_format($origSize / (1024 * 1024), 2);
                            throw new Exception("El archivo PDF para '$fieldLabel' ($pdfMB MB) supera el límite máximo de 5.00 MB.");
                        }
                        if (!move_uploaded_file($tmpPath, $targetPath)) {
                            throw new Exception("No se pudo subir el PDF de '$fieldLabel' al servidor ($targetPath).");
                        }

                        $debugDetails['archivos_recibidos'][$field] = array(
                            'campo' => $fieldLabel,
                            'nombre' => $origName,
                            'tamano' => number_format($origSize / 1024, 2) . ' KB',
                            'ruta' => $targetPath
                        );
                    } else {
                        throw new Exception("Formato no válido para '$fieldLabel'. Solo se permiten imágenes (JPG, PNG, WEBP) o documentos PDF.");
                    }

                    $uploadedFiles[$field] = '../RECURSOS/archivos_adjuntos/choferes/' . $Cho_Ced . '/' . $filename;
                }
            }
        }

        // 3. Guardar en chofer
        $datosChofer = array(
            'Prs_Cod' => $Prs_Cod,
            'Emp_Cod' => $Ses_Emp_Cod,
            'Cho_Nac' => $Cho_Nac,
            'Cho_Eci' => $Cho_Eci,
            'Cho_Car' => $Cho_Car,
            'Cho_Est' => $Cho_Est,
            'Cho_Tco' => $Cho_Tco,
            'Cho_Tli' => $Cho_Tli,
            'Cho_Nli' => $Cho_Nli,
            'Cho_Tsa' => $Cho_Tsa,
            'Cho_Tel' => $Cho_Tel,
            'Cho_Cor' => $Cho_Cor,
            'Cho_Dir' => $Cho_Dir,
            'Cho_Nem' => $Cho_Nem,
            'Cho_Tem' => $Cho_Tem
        );

        if (!empty($Cho_Fei)) $datosChofer['Cho_Fei'] = $Cho_Fei;
        if (!empty($Cho_Cli)) $datosChofer['Cho_Cli'] = $Cho_Cli;

        foreach (array('Cho_Img_Lic_Anv', 'Cho_Img_Lic_Rev', 'Cho_Doc_Ced', 'Cho_Doc_Ced_Rev', 'Cho_Doc_Vot', 'Cho_Doc_Fot', 'Cho_Doc_Ldi', 'Cho_Doc_Ant', 'Cho_Doc_San') as $f) {
            if (isset($uploadedFiles[$f])) {
                $datosChofer[$f] = $uploadedFiles[$f];
            }
        }

        if (!empty($Cho_Cod)) {
            $datosChofer['where'] = array('Cho_Cod' => $Cho_Cod);
            $obBD_con1->operacionobBD('chofer.update', $datosChofer, $obBD_conexion);
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al actualizar Chofer: " . $errMsg);
            }
            if (!empty($Prs_Cod)) {
                $datosChoferSync = $datosChofer;
                $datosChoferSync['where'] = array('Prs_Cod' => $Prs_Cod);
                $obBD_con1->operacionobBD('chofer.update', $datosChoferSync, $obBD_conexion);
            }
        } else {
            $obBD_con1->operacionobBD('chofer.insert', $datosChofer, $obBD_conexion);
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al guardar Chofer: " . $errMsg);
            }
            $Cho_Cod = $obBD_con1->insercionid($obBD_conexion);
        }

        // 4. Guardar o Actualizar Relación Planta (manifiesto_chofer)
        if (!empty($Pla_Cod) && !empty($Cho_Cod)) {
            try {
                $rel = $obBD_con1->getRowConsulta(9, array($Cho_Cod), $obBD_conexion);
                if (empty($rel)) {
                    $obBD_con1->operacionobBD('manifiesto_chofer.insert', array('Cho_Cod' => $Cho_Cod, 'Pla_Cod' => $Pla_Cod), $obBD_conexion);
                } else {
                    $obBD_con1->operacionobBD('manifiesto_chofer.update', array('Pla_Cod' => $Pla_Cod, 'where' => array('Cho_Cod' => $Cho_Cod)), $obBD_conexion);
                }
            } catch (Throwable $ePla) {
            }
        }

        // 5. Guardar o Actualizar Capacitaciones (manifiesto_chofer_capaci)
        if (!empty($Cho_Cod)) {
            try {
                $datosCapaci = array(
                    'Cho_Cod' => $Cho_Cod,
                    'Cap_Bas_Obli' => $Cap_Bas_Obli,
                    'Cap_Mat_Peli' => $Cap_Mat_Peli
                );
                if (!empty($Cap_Bas_Fec)) $datosCapaci['Cap_Bas_Fec'] = $Cap_Bas_Fec;
                if (!empty($Cap_Bas_Vig)) $datosCapaci['Cap_Bas_Vig'] = $Cap_Bas_Vig;
                if (!empty($Cap_Mat_Fec)) $datosCapaci['Cap_Mat_Fec'] = $Cap_Mat_Fec;
                if (!empty($Cap_Mat_Vig)) $datosCapaci['Cap_Mat_Vig'] = $Cap_Mat_Vig;

                if (isset($uploadedFiles['Cap_Bas_Adj'])) $datosCapaci['Cap_Bas_Adj'] = $uploadedFiles['Cap_Bas_Adj'];
                if (isset($uploadedFiles['Cap_Mat_Adj'])) $datosCapaci['Cap_Mat_Adj'] = $uploadedFiles['Cap_Mat_Adj'];
                if (isset($uploadedFiles['Cap_Otr_Adj'])) $datosCapaci['Cap_Otr_Adj'] = $uploadedFiles['Cap_Otr_Adj'];

                $capRow = $obBD_con1->getRowConsulta(10, array($Cho_Cod), $obBD_conexion);
                if (empty($capRow)) {
                    $obBD_con1->operacionobBD('manifiesto_chofer_capaci.insert', $datosCapaci, $obBD_conexion);
                } else {
                    $datosCapaci['where'] = array('Cho_Cod' => $Cho_Cod);
                    $obBD_con1->operacionobBD('manifiesto_chofer_capaci.update', $datosCapaci, $obBD_conexion);
                }
            } catch (Throwable $eCap) {
            }
        }

        $obBD_con1->Error = 0;
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        $resp['success'] = true;
        $resp['message'] = 'Chofer guardado correctamente';
        $resp['debug_info'] = $debugDetails;
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = $e->getMessage();
        $resp['debug_info'] = isset($debugDetails) ? $debugDetails : null;
    } catch (Throwable $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = $e->getMessage();
        $resp['debug_info'] = isset($debugDetails) ? $debugDetails : null;
    }
    responderJsonLimpio($resp);
}

// 7. Anular Chofer
if (isset($_POST['anularChoferAjax'])) {
    $resp = array('success' => false);
    $Cho_Cod = isset($_POST['Cho_Cod']) ? $_POST['Cho_Cod'] : '';
    if (!empty($Cho_Cod)) {
        $obBD_con1->operacionobBD('chofer.update', array('Cho_Est' => 'I', 'where' => array('Cho_Cod' => $Cho_Cod)), $obBD_conexion);
        $resp['success'] = ($obBD_con1->Error == 0);
    }
    responderJsonLimpio($resp);
}



// 7.5. Enviar notificación de capacitación al chofer (WhatsApp + correo Relavera)
if (isset($_POST['enviarNotifCapacitacionChoferAjax'])) {
    $resp = array(
        'success' => false,
        'message' => '',
        'whatsapp' => false,
        'correo' => false,
        'omitido_whatsapp' => false,
        'omitido_correo' => false
    );

    $Cho_Cod = isset($_POST['Cho_Cod']) ? trim((string) $_POST['Cho_Cod']) : '';
    if ($Cho_Cod === '') {
        $resp['message'] = 'No se recibió el código del chofer.';
        responderJsonLimpio($resp);
    }

    $chofer = $obBD_con1->getRowConsulta(8, array($Cho_Cod), $obBD_conexion);
    if (empty($chofer)) {
        $resp['message'] = 'No se encontró el chofer indicado.';
        responderJsonLimpio($resp);
    }
    $obBD_con1->utf8_change_param($chofer);

    $nombre = isset($chofer['nombre']) ? trim((string) $chofer['nombre']) : '';
    if ($nombre === '') {
        $nombre = trim(
            (isset($chofer['Prs_Nom']) ? (string) $chofer['Prs_Nom'] : '') . ' ' .
                (isset($chofer['Prs_Ape']) ? (string) $chofer['Prs_Ape'] : '')
        );
    }
    $cedula = isset($chofer['Prs_Ced']) ? trim((string) $chofer['Prs_Ced']) : '';
    $telefono = isset($chofer['Cho_Tel']) ? trim((string) $chofer['Cho_Tel']) : '';
    if ($telefono === '' && isset($chofer['Prs_Tel'])) {
        $telefono = trim((string) $chofer['Prs_Tel']);
    }
    $correo = isset($chofer['Cho_Cor']) ? trim((string) $chofer['Cho_Cor']) : '';
    if ($correo === '' && isset($chofer['Prs_Cor'])) {
        $correo = trim((string) $chofer['Prs_Cor']);
    }
    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $correo = '';
    }
    $licencia = trim(
        (isset($chofer['Cho_Tli']) ? (string) $chofer['Cho_Tli'] : '') .
            (isset($chofer['Cho_Nli']) && trim((string) $chofer['Cho_Nli']) !== ''
                ? ' / ' . trim((string) $chofer['Cho_Nli'])
                : '')
    );
    $tipoSangre = isset($chofer['Cho_Tsa']) ? trim((string) $chofer['Cho_Tsa']) : '';
    $cargo = isset($chofer['Cho_Car']) ? trim((string) $chofer['Cho_Car']) : '';
    $cadLic = isset($chofer['Cho_Cli']) ? trim((string) $chofer['Cho_Cli']) : '';

    $plantaNom = '';
    $relPla = $obBD_con1->getRowConsulta(9, array($Cho_Cod), $obBD_conexion);
    if (!empty($relPla['Pla_Cod'])) {
        $plaRow = $obBD_con1->getRowConsulta(14, array($relPla['Pla_Cod']), $obBD_conexion);
        if (!empty($plaRow)) {
            $obBD_con1->utf8_change_param($plaRow);
            if (!empty($plaRow['Pla_Nom'])) {
                $plantaNom = trim((string) $plaRow['Pla_Nom']);
            }
        }
    }

    $lineas = array();
    $lineas[] = 'Gracias por asistir a la capacitación, tus datos se han registrado con éxito al sistema.';
    $lineas[] = '';
    $lineas[] = 'Datos registrados:';
    if ($nombre !== '') {
        $lineas[] = '- Nombre: ' . $nombre;
    }
    if ($cedula !== '') {
        $lineas[] = '- Cédula: ' . $cedula;
    }
    if ($telefono !== '') {
        $lineas[] = '- Teléfono: ' . $telefono;
    }
    if ($correo !== '') {
        $lineas[] = '- Correo: ' . $correo;
    }
    if ($cargo !== '') {
        $lineas[] = '- Cargo: ' . $cargo;
    }
    if ($licencia !== '') {
        $lineas[] = '- Licencia: ' . $licencia;
    }
    if ($cadLic !== '') {
        $lineas[] = '- Caducidad licencia: ' . $cadLic;
    }
    if ($tipoSangre !== '') {
        $lineas[] = '- Tipo de sangre: ' . $tipoSangre;
    }
    if ($plantaNom !== '') {
        $lineas[] = '- Planta: ' . $plantaNom;
    }
    $mensaje = implode("\n", $lineas);
    $asunto = 'Registro de capacitación - Relavera';

    $detalle = array();

    $telWa = relavera_whatsapp_normalizar_numero_ec($telefono);
    if ($telWa === '') {
        $resp['omitido_whatsapp'] = true;
        $detalle[] = 'WhatsApp omitido (sin teléfono válido)';
    } else {
        $okWa = relavera_enviar_whatsapp_notif($telWa, $mensaje);
        $resp['whatsapp'] = (bool) $okWa;
        $detalle[] = $okWa ? 'WhatsApp enviado' : 'WhatsApp falló';
    }

    if ($correo === '') {
        $resp['omitido_correo'] = true;
        $detalle[] = 'Correo omitido (sin email válido)';
    } else {
        $okMail = relavera_notif_enviar_correo_notif($correo, $nombre, $asunto, $mensaje, null);
        $resp['correo'] = (bool) $okMail;
        $detalle[] = $okMail ? 'Correo enviado' : 'Correo falló';
    }

    $resp['success'] = ($resp['whatsapp'] || $resp['correo']);
    if ($resp['success']) {
        $resp['message'] = 'Notificación procesada: ' . implode('. ', $detalle) . '.';
    } else {
        $resp['message'] = 'No se pudo enviar la notificación. ' . implode('. ', $detalle) . '.';
    }
    responderJsonLimpio($resp);
}



// 8. Listar Vehículos
if (isset($_REQUEST['listVehiculosGridAjax'])) {
    $req = array_merge($_GET, $_POST);
    $page = isset($req['page']) ? intval($req['page']) : 1;
    $rows = isset($req['rows']) ? intval($req['rows']) : 50;

    if ($rows <= 0 || $rows >= 99999) {
        $rows = 999999;
    }
    if ($page < 1) {
        $page = 1;
    }

    $params = array($Ses_Emp_Cod);
    if (isset($req['op_opciones']) && isset($req['search']) && !empty($req['search'])) {
        $params['op_opciones'] = $req['op_opciones'];
        $params['search'] = $req['search'];
    }

    $contar = $obBD_con1->getRowConsulta(4, $params, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $response = $pagination['data'];
    if ($contar['total'] > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(4, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    responderJsonLimpio($response);
}

// 9. Validar Placa Vehículo
if (isset($_POST['validarPlacaVehiculoAjax'])) {
    $resp = array('existe' => false);
    $pla = isset($_POST['Veh_Pla']) ? trim($_POST['Veh_Pla']) : '';
    if (!empty($pla)) {
        $row = $obBD_con1->getRowConsulta(7, array($pla), $obBD_conexion);
        if (!empty($row)) $resp['existe'] = true;
    }
    responderJsonLimpio($resp);
}

// 9.5. Obtener Vehículo Completo por ID para Edición
if (isset($_GET['getVehiculoByIdAjax'])) {
    $resp = array('success' => false);
    $Veh_Cod = isset($_GET['Veh_Cod']) ? $_GET['Veh_Cod'] : '';
    if (!empty($Veh_Cod)) {
        $vehiculo = $obBD_con1->getRowConsulta(12, array($Veh_Cod), $obBD_conexion);
        if (!empty($vehiculo)) {
            $resp['success'] = true;
            $resp['vehiculo'] = $vehiculo;
            $obBD_con1->utf8_change_param($resp['vehiculo']);
        }
    }
    responderJsonLimpio($resp);
}

// 9.6. Buscar Proveedor (Propietario) por CI 10, RUC 13 o ID < 10
if (isset($_GET['buscarProveedorPropietarioAjax'])) {
    $resp = array('success' => true, 'existe' => false, 'esProveedor' => false);
    $ced = isset($_GET['Prv_Ced']) ? preg_replace('/[^a-zA-Z0-9]/', '', trim($_GET['Prv_Ced'])) : '';
    if ($ced !== '') {
        $proveedor = $obBD_con1->getRowConsulta(16, array($ced, $Ses_Emp_Cod), $obBD_conexion);
        if (!empty($proveedor)) {
            $resp['existe'] = true;
            $resp['esProveedor'] = true;
            if (empty($proveedor['Prv_Tel']) && !empty($proveedor['Prv_Tel_Prv'])) {
                $proveedor['Prv_Tel'] = $proveedor['Prv_Tel_Prv'];
            }
            if (empty($proveedor['Prv_Tel']) && !empty($proveedor['Prs_Tel'])) {
                $proveedor['Prv_Tel'] = $proveedor['Prs_Tel'];
            }
            if (empty($proveedor['Prv_Tel']) && !empty($proveedor['Prs_Cel'])) {
                $proveedor['Prv_Tel'] = $proveedor['Prs_Cel'];
            }
            $resp['proveedor'] = $proveedor;
            $obBD_con1->utf8_change_param($resp['proveedor']);
        } else {
            $persona = $obBD_con1->getRowConsulta(18, array($ced), $obBD_conexion);
            if (!empty($persona)) {
                $resp['existe'] = true;
                $resp['esProveedor'] = false;
                if (empty($persona['Prs_Tel']) && !empty($persona['Prs_Cel'])) {
                    $persona['Prs_Tel'] = $persona['Prs_Cel'];
                }
                $resp['persona'] = $persona;
                $obBD_con1->utf8_change_param($resp['persona']);
            }
        }
    }
    responderJsonLimpio($resp);
}

// 10. Guardar Vehículo Completo (vehiculo + manifiesto_vehiculo + manifiesto_matricula_vehiculo)
if (isset($_POST['saveVehiculoAjax'])) {
    $resp = array('success' => false);
    $Veh_Cod = isset($_POST['Veh_Cod']) ? trim($_POST['Veh_Cod']) : '';
    $Pla_Cod = !empty($_POST['Pla_Cod']) ? trim($_POST['Pla_Cod']) : null;
    $Mat_Cod = !empty($_POST['Mat_Cod']) ? trim($_POST['Mat_Cod']) : null; // empresa transporte
    $Veh_Pla = isset($_POST['Veh_Pla']) ? strtoupper(trim($_POST['Veh_Pla'])) : '';
    $Veh_Mar = isset($_POST['Veh_Mar']) ? addslashes(trim($_POST['Veh_Mar'])) : '';
    $Veh_Col = isset($_POST['Veh_Col']) ? addslashes(trim($_POST['Veh_Col'])) : '';
    $Veh_Col2 = isset($_POST['Veh_Col2']) ? addslashes(trim($_POST['Veh_Col2'])) : '';
    $Veh_Cap = isset($_POST['Veh_Cap']) ? floatval($_POST['Veh_Cap']) : 0;
    $Veh_Tit = isset($_POST['Veh_Tit']) ? $_POST['Veh_Tit'] : 'V';
    $Veh_Est = isset($_POST['Veh_Est']) ? $_POST['Veh_Est'] : 'A';
    $Veh_Mde = isset($_POST['Veh_Mde']) ? addslashes(trim($_POST['Veh_Mde'])) : '';
    $Veh_Amo = isset($_POST['Veh_Amo']) ? preg_replace('/\D/', '', trim($_POST['Veh_Amo'])) : '';
    $Veh_Pes = isset($_POST['Veh_Pes']) ? trim($_POST['Veh_Pes']) : '';
    if (strlen($Veh_Amo) > 4) {
        $Veh_Amo = substr($Veh_Amo, 0, 4);
    }
    if ($Veh_Amo === '') {
        $Veh_Amo = '0000';
    }
    if ($Veh_Mde === '') {
        $Veh_Mde = '-';
    }

    // Propietario → proveedore (Prv_*)
    $Prv_Cod = !empty($_POST['Prv_Cod']) ? intval($_POST['Prv_Cod']) : 0;
    $Prv_Ced = isset($_POST['Prv_Ced']) ? preg_replace('/[^a-zA-Z0-9]/', '', trim($_POST['Prv_Ced'])) : '';
    $Prv_Nom = isset($_POST['Prv_Nom']) ? trim($_POST['Prv_Nom']) : '';
    $Prv_Can = isset($_POST['Prv_Can']) ? addslashes(trim($_POST['Prv_Can'])) : '';
    $Prv_Tel = isset($_POST['Prv_Tel']) ? trim($_POST['Prv_Tel']) : '';
    $Prv_Cor = isset($_POST['Prv_Cor']) ? trim($_POST['Prv_Cor']) : '';

    // Matrícula (manifiesto_matricula_vehiculo) — columnas reales
    $Ciu_Cod = !empty($_POST['Ciu_Cod']) ? intval($_POST['Ciu_Cod']) : null;
    $Mat_Pan = isset($_POST['Mat_Pan']) ? strtoupper(trim($_POST['Mat_Pan'])) : '';
    $Mat_Fem = !empty($_POST['Mat_Fem']) ? $_POST['Mat_Fem'] : date('Y-m-d');
    $Mat_Fca = !empty($_POST['Mat_Fca']) ? $_POST['Mat_Fca'] : $Mat_Fem;
    $Mat_Nmo = isset($_POST['Mat_Nmo']) ? addslashes(trim($_POST['Mat_Nmo'])) : '';
    $Mat_Cha = isset($_POST['Mat_Cha']) ? addslashes(trim($_POST['Mat_Cha'])) : '';
    $Mat_Ram = isset($_POST['Mat_Ram']) ? addslashes(trim($_POST['Mat_Ram'])) : '';
    $Mat_Cil = isset($_POST['Mat_Cil']) && $_POST['Mat_Cil'] !== '' ? floatval($_POST['Mat_Cil']) : 0;
    $Mat_Cve = isset($_POST['Mat_Cve']) ? addslashes(trim($_POST['Mat_Cve'])) : '';
    $Mat_Tip = isset($_POST['Mat_Tip']) ? addslashes(trim($_POST['Mat_Tip'])) : '';
    $Mat_Npa = isset($_POST['Mat_Npa']) && $_POST['Mat_Npa'] !== '' ? intval($_POST['Mat_Npa']) : null;
    $Mat_Ori = isset($_POST['Mat_Ori']) ? addslashes(trim($_POST['Mat_Ori'])) : '';
    $Mat_Tco = isset($_POST['Mat_Tco']) ? substr(trim($_POST['Mat_Tco']), 0, 1) : 'D';
    $Mat_Car = isset($_POST['Mat_Car']) ? addslashes(trim($_POST['Mat_Car'])) : '';
    $Mat_Tpe = isset($_POST['Mat_Tpe']) ? addslashes(trim($_POST['Mat_Tpe'])) : '';
    $Mat_Deg = isset($_POST['Mat_Deg']) ? addslashes(trim($_POST['Mat_Deg'])) : '';
    $Mat_Est = isset($_POST['Mat_Est']) ? substr(trim($_POST['Mat_Est']), 0, 1) : 'A';
    if ($Mat_Nmo === '') { $Mat_Nmo = '-'; }
    if ($Mat_Cha === '') { $Mat_Cha = '-'; }
    if ($Mat_Ram === '') { $Mat_Ram = '-'; }

    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        // 0. Registrar / resolver propietario en proveedore
        if (!empty($Prv_Ced)) {
            $persona = $obBD_con1->getRowConsulta(18, array($Prv_Ced), $obBD_conexion);
            $Prs_Cod_Pro = !empty($persona['Prs_Cod']) ? $persona['Prs_Cod'] : 0;

            $nomParts = preg_split('/\s+/', $Prv_Nom, 2);
            $Prs_Nom_Pro = addslashes(!empty($nomParts[0]) ? $nomParts[0] : $Prv_Nom);
            $Prs_Ape_Pro = addslashes(!empty($nomParts[1]) ? $nomParts[1] : $Prv_Nom);
            if ($Prv_Nom === '') {
                $Prs_Nom_Pro = !empty($persona['Prs_Nom']) ? addslashes($persona['Prs_Nom']) : '';
                $Prs_Ape_Pro = !empty($persona['Prs_Ape']) ? addslashes($persona['Prs_Ape']) : $Prs_Nom_Pro;
            }

            $Ide_Cod = (strlen($Prv_Ced) === 13) ? 1 : 2;
            $Prv_Tic = (strlen($Prv_Ced) === 13) ? 'J' : 'N';
            $Prv_Com = ($Prv_Tic === 'J') ? addslashes($Prv_Nom) : '';

            if (empty($Prs_Cod_Pro)) {
                $datosPersona = array(
                    'Prs_Ced' => $Prv_Ced,
                    'Prs_Nom' => $Prs_Nom_Pro,
                    'Prs_Ape' => $Prs_Ape_Pro,
                    'Prs_Tel' => $Prv_Tel,
                    'Prs_Cor' => $Prv_Cor,
                    'Ide_Cod' => $Ide_Cod,
                    'Prs_Est' => 'A'
                );
                $obBD_con1->operacionobBD('persona.insert', $datosPersona, $obBD_conexion);
                if ($obBD_con1->Error != 0) {
                    $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                    throw new Exception("Error al guardar Persona del propietario: " . $errMsg);
                }
                $Prs_Cod_Pro = $obBD_con1->insercionid($obBD_conexion);
            } else {
                $datosPrs = array(
                    'Prs_Tel' => $Prv_Tel,
                    'Prs_Cor' => $Prv_Cor,
                    'where' => array('Prs_Cod' => $Prs_Cod_Pro)
                );
                if (!empty($Prv_Nom)) {
                    $datosPrs['Prs_Nom'] = $Prs_Nom_Pro;
                    $datosPrs['Prs_Ape'] = $Prs_Ape_Pro;
                }
                $obBD_con1->operacionobBD('persona.update', $datosPrs, $obBD_conexion);
                if ($obBD_con1->Error != 0) {
                    $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                    throw new Exception("Error al actualizar Persona del propietario: " . $errMsg);
                }
            }

            if (empty($Prv_Cod) && !empty($Prs_Cod_Pro)) {
                $prvRow = $obBD_con1->getRowConsulta(17, array($Prs_Cod_Pro, $Ses_Emp_Cod), $obBD_conexion);
                if (!empty($prvRow['Prv_Cod'])) {
                    $Prv_Cod = intval($prvRow['Prv_Cod']);
                }
            }

            if (empty($Prv_Cod) && !empty($Prs_Cod_Pro)) {
                $datosProveedor = array(
                    'Emp_Cod' => $Ses_Emp_Cod,
                    'Prs_Cod' => $Prs_Cod_Pro,
                    'Prv_Com' => $Prv_Com,
                    'Prv_Tic' => $Prv_Tic,
                    'Prv_Esp' => 'N',
                    'Prv_Con' => 'N',
                    'Prv_Reg' => 'N',
                    'Prv_Ris' => 'N',
                    'Prv_Gct' => 'N',
                    'Prv_Rim_Emp' => 'N',
                    'Prv_Rim_Np' => 'N',
                    'Prv_Ag_Ret' => 'N',
                    'Prv_Est' => 'A'
                );
                if (!empty($Prv_Tel)) {
                    $datosProveedor['Prv_Tel'] = $Prv_Tel;
                }
                if (!empty($Prv_Cor)) {
                    $datosProveedor['Prv_Cor'] = $Prv_Cor;
                }
                $obBD_con1->operacionobBD('proveedore.insert', $datosProveedor, $obBD_conexion);
                if ($obBD_con1->Error != 0) {
                    $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                    throw new Exception("Error al registrar Proveedor (propietario): " . $errMsg);
                }
                $Prv_Cod = $obBD_con1->insercionid($obBD_conexion);
            } else if (!empty($Prv_Cod)) {
                $updPrv = array(
                    'Prv_Tel' => $Prv_Tel,
                    'Prv_Cor' => $Prv_Cor,
                    'where' => array('Prv_Cod' => $Prv_Cod)
                );
                if ($Prv_Tic === 'J' && !empty($Prv_Com)) {
                    $updPrv['Prv_Com'] = $Prv_Com;
                }
                $obBD_con1->operacionobBD('proveedore.update', $updPrv, $obBD_conexion);
            }
        }

        $datosVehiculo = array(
            'Veh_Mar' => $Veh_Mar,
            'Veh_Pla' => $Veh_Pla,
            'Veh_Col' => $Veh_Col,
            'Veh_Col2' => $Veh_Col2,
            'Veh_Cap' => $Veh_Cap,
            'Veh_Tit' => $Veh_Tit,
            'Veh_Mde' => $Veh_Mde,
            'Veh_Amo' => $Veh_Amo,
            'Veh_Pes' => $Veh_Pes,
            'Emp_Cod' => $Ses_Emp_Cod,
            'Veh_Est' => $Veh_Est
        );
        // Empresa transporte opcional (Mat_Cod puede quedar NULL)
        if (!empty($Mat_Cod)) {
            $datosVehiculo['Mat_Cod'] = $Mat_Cod;
        } elseif (!empty($Veh_Cod)) {
            $datosVehiculo['Mat_Cod'] = null;
        }
        if (!empty($Prv_Cod)) {
            $datosVehiculo['Prv_Cod'] = $Prv_Cod;
        }

        // 1. Guardar o Actualizar Tabla vehiculo
        if (!empty($Veh_Cod)) {
            $datosVehiculo['where'] = array('Veh_Cod' => $Veh_Cod);
            $obBD_con1->operacionobBD('vehiculo.update', $datosVehiculo, $obBD_conexion);
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al actualizar Vehículo: " . $errMsg);
            }
        } else {
            $obBD_con1->operacionobBD('vehiculo.insert', $datosVehiculo, $obBD_conexion);
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al guardar Vehículo: " . $errMsg);
            }
            $Veh_Cod = $obBD_con1->insercionid($obBD_conexion);
        }

        // 2. Guardar o Actualizar Relación Planta (manifiesto_vehiculo)
        if (!empty($Pla_Cod) && !empty($Veh_Cod)) {
            $rel = $obBD_con1->getRowConsulta(11, array($Veh_Cod), $obBD_conexion);
            if (empty($rel)) {
                $obBD_con1->operacionobBD('manifiesto_vehiculo.insert', array('Veh_Cod' => $Veh_Cod, 'Pla_Cod' => $Pla_Cod), $obBD_conexion);
            } else {
                $obBD_con1->operacionobBD('manifiesto_vehiculo.update', array('Pla_Cod' => $Pla_Cod, 'where' => array('Veh_Cod' => $Veh_Cod)), $obBD_conexion);
            }
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al actualizar Planta del Vehículo: " . $errMsg);
            }
        }

        // 3. Guardar o Actualizar Matrícula (columnas reales de manifiesto_matricula_vehiculo)
        if (!empty($Veh_Cod) && !empty($Veh_Pla)) {
            $datosMatricula = array(
                'Veh_Cod' => $Veh_Cod,
                'Mat_Pla' => $Veh_Pla,
                'Mat_Pan' => $Mat_Pan,
                'Mat_Fem' => $Mat_Fem,
                'Mat_Fca' => $Mat_Fca,
                'Mat_Nmo' => $Mat_Nmo,
                'Mat_Cha' => $Mat_Cha,
                'Mat_Ram' => $Mat_Ram,
                'Mat_Cil' => $Mat_Cil,
                'Mat_Cve' => $Mat_Cve,
                'Mat_Tip' => $Mat_Tip,
                'Mat_Ori' => $Mat_Ori,
                'Mat_Tco' => $Mat_Tco,
                'Mat_Car' => $Mat_Car,
                'Mat_Tpe' => $Mat_Tpe,
                'Mat_Deg' => $Mat_Deg,
                'Mat_Est' => $Mat_Est
            );
            if (!empty($Ciu_Cod)) {
                $datosMatricula['Ciu_Cod'] = $Ciu_Cod;
            }
            if (!is_null($Mat_Npa)) {
                $datosMatricula['Mat_Npa'] = $Mat_Npa;
            }

            // Adjunto matrícula: PDF directo o unión Frente+Reverso
            $Mat_Adj_Modo = isset($_POST['Mat_Adj_Modo']) ? $_POST['Mat_Adj_Modo'] : 'pdf';
            $Mat_Adj_Clear = !empty($_POST['Mat_Adj_Clear']);
            $Mat_Adj_Path = null;

            if ($Mat_Adj_Clear) {
                $datosMatricula['Mat_Adj'] = '';
            }

            if ($Mat_Adj_Modo === 'fotos') {
                $hasFrente = isset($_FILES['Mat_Adj_Frente']) && $_FILES['Mat_Adj_Frente']['error'] === UPLOAD_ERR_OK;
                $hasReverso = isset($_FILES['Mat_Adj_Reverso']) && $_FILES['Mat_Adj_Reverso']['error'] === UPLOAD_ERR_OK;
                if ($hasFrente xor $hasReverso) {
                    throw new Exception('Para unir la matrícula debe adjuntar las 2 fotos: Frente y Reverso.');
                }
                if ($hasFrente && $hasReverso) {
                    $placaSafe = preg_replace('/[^A-Za-z0-9_-]/', '', strtoupper($Veh_Pla));
                    if ($placaSafe === '') {
                        $placaSafe = 'SINPLACA';
                    }
                    $baseDir = dirname(__DIR__) . '/RECURSOS/archivos_adjuntos/vehiculos/' . $placaSafe . '/';
                    if (!is_dir($baseDir) && !@mkdir($baseDir, 0777, true) && !is_dir($baseDir)) {
                        throw new Exception('No se pudo crear carpeta de adjuntos del vehículo.');
                    }
                    $tmpFrente = $baseDir . 'tmp_frente_' . uniqid() . '.jpg';
                    $tmpReverso = $baseDir . 'tmp_reverso_' . uniqid() . '.jpg';
                    if (!optimizarYComprimirImagen($_FILES['Mat_Adj_Frente']['tmp_name'], $tmpFrente, 1600, 85)) {
                        if (!move_uploaded_file($_FILES['Mat_Adj_Frente']['tmp_name'], $tmpFrente)) {
                            throw new Exception('No se pudo procesar la foto frontal de la matrícula.');
                        }
                    }
                    if (!optimizarYComprimirImagen($_FILES['Mat_Adj_Reverso']['tmp_name'], $tmpReverso, 1600, 85)) {
                        if (!move_uploaded_file($_FILES['Mat_Adj_Reverso']['tmp_name'], $tmpReverso)) {
                            @unlink($tmpFrente);
                            throw new Exception('No se pudo procesar la foto reverso de la matrícula.');
                        }
                    }
                    $filename = 'matricula_unida_' . date('Ymd_His') . '.jpg';
                    $target = $baseDir . $filename;
                    if (!unirDosImagenesVertical($tmpFrente, $tmpReverso, $target, 1600, 85)) {
                        @unlink($tmpFrente);
                        @unlink($tmpReverso);
                        throw new Exception('No se pudo unir las fotos de la matrícula.');
                    }
                    @unlink($tmpFrente);
                    @unlink($tmpReverso);
                    $Mat_Adj_Path = '../RECURSOS/archivos_adjuntos/vehiculos/' . $placaSafe . '/' . $filename;
                }
            } else {
                // Modo PDF (también acepta imagen única)
                if (isset($_FILES['Mat_Adj_Pdf']) && $_FILES['Mat_Adj_Pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $Mat_Adj_Path = guardarArchivoMatriculaVehiculo($_FILES['Mat_Adj_Pdf'], $Veh_Pla, 'matricula');
                }
            }

            if (!empty($Mat_Adj_Path)) {
                $datosMatricula['Mat_Adj'] = $Mat_Adj_Path;
            }

            $matRow = $obBD_con1->getRowConsulta(13, array($Veh_Cod), $obBD_conexion);
            if (empty($matRow)) {
                $obBD_con1->operacionobBD('manifiesto_matricula_vehiculo.insert', $datosMatricula, $obBD_conexion);
            } else {
                $datosMatricula['where'] = array('Veh_Cod' => $Veh_Cod);
                $obBD_con1->operacionobBD('manifiesto_matricula_vehiculo.update', $datosMatricula, $obBD_conexion);
            }
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al guardar Matrícula del Vehículo: " . $errMsg);
            }
        }

        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        $resp['success'] = true;
        $resp['message'] = 'Vehículo guardado correctamente';
        if (!empty($Prv_Cod)) {
            $resp['Prv_Cod'] = $Prv_Cod;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = $e->getMessage();
    } catch (Throwable $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = $e->getMessage();
    }
    responderJsonLimpio($resp);
}

// 11. Anular Vehículo
if (isset($_POST['anularVehiculoAjax'])) {
    $resp = array('success' => false);
    $Veh_Cod = isset($_POST['Veh_Cod']) ? $_POST['Veh_Cod'] : '';
    if (!empty($Veh_Cod)) {
        $obBD_con1->operacionobBD('vehiculo.update', array('Veh_Est' => 'I', 'where' => array('Veh_Cod' => $Veh_Cod)), $obBD_conexion);
        $resp['success'] = ($obBD_con1->Error == 0);
    }
    responderJsonLimpio($resp);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestion de Choferes y Vehículo</title>
    <!-- Framework & CSS Requirements -->
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <?php require_once("../../mascaras/model3/estilos/estilos.php") ?>
    <link rel="stylesheet" type="text/css" href="../RECURSOS/datos_choferes_vehiculos.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="panel panel-default panel-main exa-ui-panel">
        <!-- Encabezado Estilo Model3 -->
        <div class="panel-heading exa-header">
            <h3 class="panel-title"><span class="glyphicon glyphicon-list-alt"></span> Datos de Choferes</h3>
        </div>

        <div class="panel-body exa-body">
            <!-- Pestañas (Tabs) -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation">
                        <a href="#tabEmpresasTransporte" aria-controls="tabEmpresasTransporte" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-truck icon-tab"></i>Empresas Transporte
                        </a>
                    </li>
                    <li role="presentation" class="active">
                        <a href="#tabChoferes" aria-controls="tabChoferes" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-user icon-tab"></i>Choferes
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tabVehiculos" aria-controls="tabVehiculos" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-road icon-tab"></i>Vehículos
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tabSanciones" aria-controls="tabSanciones" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-ban-circle icon-tab"></i>Sanciones
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- ==================== TAB: EMPRESAS TRANSPORTE ==================== -->
                    <div role="tabpanel" class="tab-pane" id="tabEmpresasTransporte">
                        <div class="row" style="margin-top: 5px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroEmpresasTransporteForm" class="form-horizontal normal" onsubmit="event.preventDefault(); actualizarGridEmpresasTransporte();">
                                        <div class="form-group" style="margin-bottom: 8px;">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radTransporte1" name="op_opciones" type="radio" value="n" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radTransporte1">Nombre</label>
                                                <input id="radTransporte2" name="op_opciones" type="radio" value="m" onclick="setfocus(this.form.search)" />
                                                <label for="radTransporte2">Licencia MAE</label>
                                            </div>
                                        </div>
                                        <div style="margin-top: 6px; margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between; width: 100%;">
                                            <div style="display: flex; align-items: center; flex-grow: 1;">
                                                <label class="control-label label-xs" style="width: 100px; text-align: right; padding-right: 8px; margin-bottom: 0; line-height: 32px; flex-shrink: 0;">Búsqueda:</label>
                                                <div style="width: 520px; max-width: 100%;">
                                                    <div class="input-group">
                                                        <input name="search" type="text" maxlength="50" placeholder="Ingrese búsqueda..." class="form-control clearable" style="height: 32px; font-size: 12px;" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridEmpresasTransporte(); }" />
                                                        <span class="input-group-btn">
                                                            <button type="button" onclick="actualizarGridEmpresasTransporte();" class="btn btn-success" style="height: 32px; font-size: 12px;" title="Buscar">
                                                                <span class="glyphicon glyphicon-search"></span> Buscar
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="flex-shrink: 0; margin-left: 15px;">
                                                <button class="btn btn-success" type="button" onclick="abrirModalEmpresaTransporte();" style="height: 32px; font-size: 12px; font-weight: 600; padding: 0 18px;">
                                                    <i class="glyphicon glyphicon-plus"></i> Nueva Empresa
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <div class="exa-ui-grid-host">
                            <table id="gridEmpresasTransporte"></table>
                            <div id="gridEmpresasTransportePager"></div>
                        </div>
                    </div>

                    <!-- ==================== TAB: CHOFERES (ACTIVO) ==================== -->
                    <div role="tabpanel" class="tab-pane active" id="tabChoferes">
                        <div class="row" style="margin-top: 5px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroChoferesForm" class="form-horizontal normal" onsubmit="event.preventDefault(); actualizarGridChoferes();">
                                        <div class="form-group" style="margin-bottom: 8px;">
                                            <label class="control-label label-xs" style="float: left; width: 100px; text-align: right; padding-right: 8px;">Filtrar Por:</label>
                                            <div class="radioset opt_search" style="float: left;">
                                                <input id="radChofer1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer1">Nombre</label>
                                                <input id="radChofer2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer2">Cédula</label>
                                            </div>

                                            <label class="control-label label-xs" style="float: left; width: 110px; text-align: right; padding-right: 8px; margin-left: 30px; line-height: 28px;">MOSTRAR DATOS:</label>
                                            <div style="float: left; width: 320px;">
                                                <select id="selMostrarDatos" name="mostrar_datos[]" class="form-control input-xs select-wide chosen-select" multiple data-placeholder="<< TODOS >>">
                                                    <option value="incompletos">Datos / Fotos Incompletos</option>
                                                    <option value="sin_foto_cedula">Sin Foto Cédula</option>
                                                    <option value="con_foto_cedula">Con Foto Cédula</option>
                                                    <option value="sin_foto_licencia">Sin Foto Licencia</option>
                                                    <option value="con_foto_licencia">Con Foto Licencia</option>
                                                    <option value="licencia_vencida">Licencia Vencida</option>
                                                    <option value="sin_cedula">Sin N° Cédula</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div style="margin-top: 6px; margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between; width: 100%;">
                                            <div style="display: flex; align-items: center; flex-grow: 1;">
                                                <label class="control-label label-xs" style="width: 100px; text-align: right; padding-right: 8px; margin-bottom: 0; line-height: 32px; padding-top: 0; flex-shrink: 0;">Búsqueda:</label>
                                                <div style="width: 650px; max-width: 100%;">
                                                    <div class="input-group">
                                                        <input name="search" type="text" maxlength="50" placeholder="Ingrese búsqueda..." class="form-control clearable" style="height: 32px; font-size: 12px;" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridChoferes(); }" />
                                                        <span class="input-group-btn">
                                                            <button type="button" onclick="actualizarGridChoferes();" class="btn btn-success" style="height: 32px; font-size: 12px;" title="Buscar">
                                                                <span class="glyphicon glyphicon-search"></span> Buscar
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="flex-shrink: 0; margin-left: 15px;">
                                                <button class="btn btn-success" type="button" onclick="abrirModalChofer();" style="height: 32px; font-size: 12px; font-weight: 600; padding: 0 18px;">
                                                    <i class="glyphicon glyphicon-plus"></i> Nuevo Chofer
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <div class="exa-ui-grid-host">
                            <table id="gridChoferes"></table>
                            <div id="gridChoferesPager"></div>
                        </div>
                    </div>



                    <!-- ==================== TAB 3: VEHÍCULOS ==================== -->
                    <div role="tabpanel" class="tab-pane" id="tabVehiculos">
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroVehiculosForm" class="form-horizontal normal" onsubmit="event.preventDefault(); actualizarGridVehiculos();">
                                        <div class="form-group" style="margin-bottom: 8px;">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radVehiculo1" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo1">Placa</label>
                                                <input id="radVehiculo2" name="op_opciones" type="radio" value="pn" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo2">Nombre Planta</label>
                                                <input id="radVehiculo3" name="op_opciones" type="radio" value="pl" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo3">Licencia Planta</label>
                                                <input id="radVehiculo4" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo4">Cédula/RUC Propietario</label>
                                            </div>
                                        </div>
                                        <div style="margin-top: 6px; margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between; width: 100%;">
                                            <div style="display: flex; align-items: center; flex-grow: 1;">
                                                <label class="control-label label-xs" style="width: 100px; text-align: right; padding-right: 8px; margin-bottom: 0; line-height: 32px; padding-top: 0; flex-shrink: 0;">Búsqueda:</label>
                                                <div style="width: 650px; max-width: 100%;">
                                                    <div class="input-group">
                                                        <input name="search" type="text" maxlength="70" placeholder="Ingrese búsqueda..." class="form-control clearable" style="height: 32px; font-size: 12px;" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridVehiculos(); }" />
                                                        <span class="input-group-btn">
                                                            <button type="button" onclick="actualizarGridVehiculos();" class="btn btn-success" style="height: 32px; font-size: 12px;" title="Buscar">
                                                                <span class="glyphicon glyphicon-search"></span> Buscar
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="flex-shrink: 0; margin-left: 15px;">
                                                <button class="btn btn-success" type="button" onclick="abrirModalVehiculo();" style="height: 32px; font-size: 12px; font-weight: 600; padding: 0 18px;">
                                                    <i class="glyphicon glyphicon-plus"></i> Nuevo Vehículo
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <div class="exa-ui-grid-host">
                            <table id="gridVehiculos"></table>
                            <div id="gridVehiculosPager"></div>
                        </div>
                    </div>
                    <?php require_once(__DIR__ . '/../COMPONENTES/man_sanciones_ui.inc.php'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODALES / DIÁLOGOS ==================== -->

    <!-- Modal Empresa Transporte (igual que man_adm_configuracion) -->
    <div id="empresaTransporteDialog" title="Registrar Empresa de Transporte" style="display: none;">
        <form id="empresaTransporteForm" class="form-horizontal normal">
            <input type="hidden" id="Mat_Cod" name="Mat_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Nombre de la Empresa:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Des" name="Mat_Des" class="form-control input-xs" required placeholder="Nombre de la Empresa" maxlength="100">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Licencia Ambiental MAE:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Mae" name="Mat_Mae" class="form-control input-xs" required placeholder="Licencia Ambiental MAE" maxlength="30">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Teléfono:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Tel" name="Mat_Tel" class="form-control input-xs" placeholder="Teléfono" maxlength="10" onkeypress="return validar_numeric(event);">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Nro. Plan de Contingencia:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Pco" name="Mat_Pco" class="form-control input-xs" placeholder="Número Plan de Contingencia" maxlength="30">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Dirección:</label>
                <div class="col-xs-8">
                    <textarea id="Mat_Dir" name="Mat_Dir" class="form-control input-xs" rows="3" placeholder="Dirección"></textarea>
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px; padding: 10px; border-top: 1px solid #ddd;">
            <button id="btnGuardarEmpresa" class="btn btn-sm btn-primary" type="button" onclick="guardarEmpresaTransporte();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#empresaTransporteDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Chofer Completo (Layout Vertical Apilado 920px) -->
    <div id="choferDialog" title="Registrar Chofer" style="display: none;">
        <form id="choferForm" class="form-horizontal normal" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" id="Cho_Cod" name="Cho_Cod">
            <input type="hidden" id="Prs_Cod" name="Prs_Cod">

            <!-- BLOQUE 1: IDENTIFICACIÓN PERSONAL -->
            <div class="row">
                <div class="col-xs-12">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-user"></i> 1. Identificación Personal</legend>
                        <input type="hidden" id="Cho_Est" name="Cho_Est" value="A">
                        <input type="hidden" id="Cho_Car" name="Cho_Car" value="Chofer">
                        <input type="hidden" id="Cho_Tco" name="Cho_Tco" value="Indefinido">

                        <div class="row">
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Cédula / RUC / Pasaporte / Documento de Identidad">Cédula / Doc. Id:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Cho_Ced" name="Cho_Ced" class="form-control input-xs" required placeholder="N° Identificación" maxlength="20" onchange="buscarPersonaCedula(this.value);">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Nombres del Chofer">Nombres:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Prs_Nom" name="Prs_Nom" class="form-control input-xs" required placeholder="Nombres completos">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Apellidos del Chofer">Apellidos:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Prs_Ape" name="Prs_Ape" class="form-control input-xs" required placeholder="Apellidos completos">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Fecha Nacimiento">Fec. Nacimiento:</label>
                                    <div class="col-xs-12">
                                        <input type="date" id="Prs_Fec" name="Prs_Fec" class="form-control input-xs input-date-wide" min="1940-01-01" max="2010-12-31" onchange="calcularEdad(this.value);">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Edad calculada">Edad:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Cho_Edad" class="form-control input-xs bold text-center" readonly placeholder="-">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Nacionalidad del Chofer">Nacionalidad:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Cho_Nac" name="Cho_Nac" class="form-control input-xs" value="Ecuatoriana">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Estado Civil">Estado Civil:</label>
                                    <div class="col-xs-12">
                                        <select id="Cho_Eci" name="Cho_Eci" class="form-control input-xs select-wide chosen-select">
                                            <option value="Soltero/a">Soltero/a</option>
                                            <option value="Casado/a">Casado/a</option>
                                            <option value="Divorciado/a">Divorciado/a</option>
                                            <option value="Viudo/a">Viudo/a</option>
                                            <option value="Unión de Hecho">Unión de Hecho</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Teléfono Celular Personal">Celular Personal:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Cho_Tel" name="Cho_Tel" class="form-control input-xs" required placeholder="0991234567" maxlength="20">
                                    </div>
                                </div>
                            </div>
                            <div id="box_cho_planta" class="col-xs-3">
                                <div class="form-group">
                                    <label id="lbl_cho_planta" class="col-xs-12 control-label label-xs required" title="Planta de Beneficio">Planta de Beneficio:</label>
                                    <div class="col-xs-12">
                                        <select id="Cho_Pla_Cod" name="Pla_Cod" class="form-control input-xs select-wide chosen-select" required>
                                            <option value="">Seleccione Planta...</option>
                                            <?php foreach ($plantas as $pla) {
                                                echo '<option value="' . htmlspecialchars($pla['Pla_Cod'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($pla['Pla_Nom'], ENT_QUOTES, 'UTF-8') . '</option>';
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>

            <!-- BLOQUE 2: LICENCIA DE CONDUCIR -->
            <div id="sec_licencia_conducir" class="row" style="margin-top: 6px;">
                <div class="col-xs-12">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-credit-card"></i> 2. Licencia de Conducir</legend>
                        <div class="row">
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Tipo o Categoría de Licencia">Tipo Licencia:</label>
                                    <div class="col-xs-12">
                                        <select id="Cho_Tli" name="Cho_Tli" class="form-control input-xs select-wide chosen-select" required onchange="evaluarLicenciaNoPosee(this.value);">
                                            <option value="">Licencia...</option>
                                            <option value="NP">NO POSEE</option>
                                            <option value="A">A</option>
                                            <option value="A1">A1</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="C1">C1</option>
                                            <option value="D">D</option>
                                            <option value="D1">D1</option>
                                            <option value="E">E</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Número de Licencia de Conducir (Solo letras y números)">N° Licencia:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Cho_Nli" name="Cho_Nli" class="form-control input-xs" placeholder="Número (Letras/Núm)" maxlength="20">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Fecha de Emisión de Licencia">Fec. Emisión:</label>
                                    <div class="col-xs-12">
                                        <input type="date" id="Cho_Fei" name="Cho_Fei" class="form-control input-xs input-date-wide" min="1980-01-01" max="2050-12-31" onchange="evaluarEstadoLicencia();">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Fecha de Vencimiento de Licencia">Fec. Vencimiento:</label>
                                    <div class="col-xs-12">
                                        <input type="date" id="Cho_Cli" name="Cho_Cli" class="form-control input-xs input-date-wide" min="2000-01-01" max="2050-12-31" onchange="evaluarEstadoLicencia();">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-1">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Estado de la Licencia">Vigencia:</label>
                                    <div class="col-xs-12" style="padding-top: 2px;">
                                        <span id="badgeLicencia" style="display: none; font-size: 11px; padding: 4px 8px;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Foto de la Licencia Anverso (Frontal)">Foto Licencia Anverso:</label>
                                    <div class="col-xs-12">
                                        <input type="file" id="Cho_Img_Lic_Anv" name="Cho_Img_Lic_Anv" class="form-control input-xs input-file-compressed" accept="image/*">
                                        <div id="preview_Cho_Img_Lic_Anv" class="preview-doc-box"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Foto de la Licencia Reverso (Posterior)">Foto Licencia Reverso:</label>
                                    <div class="col-xs-12">
                                        <input type="file" id="Cho_Img_Lic_Rev" name="Cho_Img_Lic_Rev" class="form-control input-xs input-file-compressed" accept="image/*">
                                        <div id="preview_Cho_Img_Lic_Rev" class="preview-doc-box"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>

            <!-- BLOQUE 3: CONTACTOS E INFORMACIÓN MÉDICA -->
            <div class="row" style="margin-top: 6px;">
                <div class="col-xs-12">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-phone"></i> 3. Contactos e Información Médica</legend>
                        <div class="row">
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Tipo de Sangre del Chofer">Tipo Sangre:</label>
                                    <div class="col-xs-12">
                                        <select id="Cho_Tsa" name="Cho_Tsa" class="form-control input-xs select-wide chosen-select" required>
                                            <option value="">Seleccione...</option>
                                            <option value="A+">A+</option>
                                            <option value="A-">A-</option>
                                            <option value="B+">B+</option>
                                            <option value="B-">B-</option>
                                            <option value="AB+">AB+</option>
                                            <option value="AB-">AB-</option>
                                            <option value="O+">O+</option>
                                            <option value="O-">O-</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Correo Electrónico Personal">Correo Electrónico:</label>
                                    <div class="col-xs-12">
                                        <input type="email" id="Cho_Cor" name="Cho_Cor" class="form-control input-xs" placeholder="correo@ejemplo.com">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-5">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Dirección Domiciliaria de Residencia">Dirección Domiciliaria:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Cho_Dir" name="Cho_Dir" class="form-control input-xs" placeholder="Dirección residencia">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Nombre del Contacto de Emergencia">Contacto Emergencia:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Cho_Nem" name="Cho_Nem" class="form-control input-xs" placeholder="Nombre contacto emergencia">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Teléfono del Contacto de Emergencia">Teléfono Emergencia:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Cho_Tem" name="Cho_Tem" class="form-control input-xs" placeholder="Teléfono emergencia" maxlength="20">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-12">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Observaciones Adicionales">Observaciones:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Cho_Obs" name="Cho_Obs" class="form-control input-xs" placeholder="Observaciones generales del chofer">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>

            <!-- BLOQUE 4: DOCUMENTACIÓN ADICIONAL -->
            <div class="row" style="margin-top: 6px;">
                <div class="col-xs-12">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-folder-open"></i> 4. Documentación Adicional</legend>
                        <div class="row">
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Copia Cédula Anverso (PDF/Imagen)">Cédula Anverso:</label>
                                    <div class="col-xs-12">
                                        <input type="file" id="Cho_Doc_Ced" name="Cho_Doc_Ced" class="form-control input-xs input-file-compressed" accept=".pdf,image/*">
                                        <div id="preview_Cho_Doc_Ced" class="preview-doc-box"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Copia Cédula Reverso (PDF/Imagen)">Cédula Reverso:</label>
                                    <div class="col-xs-12">
                                        <input type="file" id="Cho_Doc_Ced_Rev" name="Cho_Doc_Ced_Rev" class="form-control input-xs input-file-compressed" accept=".pdf,image/*">
                                        <div id="preview_Cho_Doc_Ced_Rev" class="preview-doc-box"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Certificado de Votación (PDF/Imagen)">Certif. Votación:</label>
                                    <div class="col-xs-12">
                                        <input type="file" id="Cho_Doc_Vot" name="Cho_Doc_Vot" class="form-control input-xs input-file-compressed" accept=".pdf,image/*">
                                        <div id="preview_Cho_Doc_Vot" class="preview-doc-box"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Foto Carnet Fondo Blanco (Imagen)">Foto Carnet:</label>
                                    <div class="col-xs-12">
                                        <input type="file" id="Cho_Doc_Fot" name="Cho_Doc_Fot" class="form-control input-xs input-file-compressed" accept="image/*">
                                        <div id="preview_Cho_Doc_Fot" class="preview-doc-box"></div>
                                    </div>
                                </div>
                            </div>
                            <div id="box_doc_ldi" class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Licencia Digital (PDF/Imagen)">Licencia Digital:</label>
                                    <div class="col-xs-12">
                                        <input type="file" id="Cho_Doc_Ldi" name="Cho_Doc_Ldi" class="form-control input-xs input-file-compressed" accept=".pdf,image/*">
                                        <div id="preview_Cho_Doc_Ldi" class="preview-doc-box"></div>
                                    </div>
                                </div>
                            </div>
                            <div id="box_doc_ant" class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Antecedentes Penales (PDF/Imagen)">Antecedentes Penales:</label>
                                    <div class="col-xs-12">
                                        <input type="file" id="Cho_Doc_Ant" name="Cho_Doc_Ant" class="form-control input-xs input-file-compressed" accept=".pdf,image/*">
                                        <div id="preview_Cho_Doc_Ant" class="preview-doc-box"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>
        </form>

        <div style="text-align: center; margin-top: 10px; margin-bottom: 5px;">
            <button id="btnGuardarChofer" class="btn btn-primary" type="button" onclick="guardarChofer();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar Chofer</button>
            <button class="btn btn-danger" type="button" onclick="$('#choferDialog').dialog('close');" style="margin-left: 5px;"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>


    </div>

    <!-- Modal Vehículo Completo — Tabs Propietario / Matrícula -->
    <div id="vehiculoDialog" title="Registrar Vehículo" style="display: none;">
        <form id="vehiculoForm" class="form-horizontal normal" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" id="Veh_Cod" name="Veh_Cod">
            <input type="hidden" id="Prv_Cod" name="Prv_Cod">
            <input type="hidden" id="Mat_Adj_Clear" name="Mat_Adj_Clear" value="0">
            <input type="hidden" id="Mat_Adj_Actual" name="Mat_Adj_Actual" value="">

            <ul class="nav nav-tabs veh-inner-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#vehTabPropietario" aria-controls="vehTabPropietario" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-user"></i> Propietario
                    </a>
                </li>
                <li role="presentation">
                    <a href="#vehTabMatricula" aria-controls="vehTabMatricula" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-file"></i> Matrícula
                    </a>
                </li>
            </ul>

            <div class="tab-content veh-inner-tab-content">

                <!-- ==================== TAB PROPIETARIO ==================== -->
                <div role="tabpanel" class="tab-pane active" id="vehTabPropietario">
                    <div class="row">
                        <div class="col-xs-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"><i class="glyphicon glyphicon-credit-card"></i> Identificación del Propietario</legend>
                                <div class="row">
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="C.I. / RUC / Pasaporte">C.I. / RUC:</label>
                                            <div class="col-xs-12">
                                                <div class="input-group input-group-xs">
                                                    <input type="text" id="Prv_Ced" name="Prv_Ced" class="form-control input-xs" placeholder="RUC/CI" maxlength="13" onchange="buscarProveedorPropietario(this.value);" onkeyup="if(event.keyCode===13){ buscarProveedorPropietario(this.value); }">
                                                    <span class="input-group-btn">
                                                        <button type="button" id="btnReloadPrv" class="btn btn-default btn-xs" title="Recargar proveedor" onclick="buscarProveedorPropietario($('#Prv_Ced').val());">
                                                            <i class="glyphicon glyphicon-refresh"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-9">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Nombre del propietario">Nombre:</label>
                                            <div class="col-xs-12">
                                                <input type="text" id="Prv_Nom" name="Prv_Nom" class="form-control input-xs" placeholder="Nombres del propietario" maxlength="150">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row veh-status-row">
                                    <div class="col-xs-12">
                                        <div id="Prv_Ced_Est"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Ciudad">Ciudad:</label>
                                            <div class="col-xs-11">
                                                <input type="text" id="Prv_Can" name="Prv_Can" class="form-control input-xs" placeholder="Ciudad" maxlength="50" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Teléfono">Teléfono:</label>
                                            <div class="col-xs-11">
                                                <input type="text" id="Prv_Tel" name="Prv_Tel" class="form-control input-xs" placeholder="Teléfono" maxlength="30">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Correo">Correo:</label>
                                            <div class="col-xs-12">
                                                <input type="email" id="Prv_Cor" name="Prv_Cor" class="form-control input-xs" placeholder="correo@ejemplo.com" maxlength="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"><i class="glyphicon glyphicon-road"></i> Asignación Operativa</legend>
                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Planta de Beneficio (opcional)">Planta:</label>
                                            <div class="col-xs-11">
                                                <select id="Veh_Pla_Cod" name="Pla_Cod" class="form-control input-xs select-wide chosen-select" data-placeholder="Opcional...">
                                                    <option value="">Sin planta asignada</option>
                                                    <?php foreach ($plantas as $row) { ?>
                                                        <option value="<?php echo $row['Pla_Cod']; ?>"><?php echo $row['Pla_Nom']; ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Empresa de Transporte (opcional)">Empresa Transporte:</label>
                                            <div class="col-xs-12">
                                                <select id="Veh_Mat_Cod" name="Mat_Cod" class="form-control input-xs select-wide chosen-select" data-placeholder="Opcional...">
                                                    <option value="">Sin empresa asignada</option>
                                                    <?php foreach ($transportes as $row) { ?>
                                                        <option value="<?php echo $row['Mat_Cod']; ?>"><?php echo htmlspecialchars($row['Mat_Des']); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"><i class="glyphicon glyphicon-tags"></i> Datos Principales</legend>
                                <div class="row">
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="Placa Actual">Placa Actual:</label>
                                            <div class="col-xs-11">
                                                <input type="text" id="Veh_Pla" name="Veh_Pla" class="form-control input-xs bold text-uppercase" required placeholder="Ej: ABC-1234" maxlength="10" onchange="validarPlacaVehiculo(this.value);" onkeyup="this.value = this.value.toUpperCase();">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Placa Anterior">Placa Anterior:</label>
                                            <div class="col-xs-11">
                                                <input type="text" id="Mat_Pan" name="Mat_Pan" class="form-control input-xs text-uppercase" placeholder="Placa anterior" maxlength="10" onkeyup="this.value = this.value.toUpperCase();">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="Tipo">Tipo:</label>
                                            <div class="col-xs-11">
                                                <select id="Veh_Tit" name="Veh_Tit" class="form-control input-xs select-wide chosen-select" required>
                                                    <option value="V">VOLQUETA</option>
                                                    <option value="C">CAMION / CAMIONETA</option>
                                                    <option value="D">TIPO DUMPER</option>
                                                    <option value="B">BUS / VAN</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Estado">Estado:</label>
                                            <div class="col-xs-12">
                                                <select id="Veh_Est" name="Veh_Est" class="form-control input-xs select-wide chosen-select">
                                                    <option value="A">ACTIVO</option>
                                                    <option value="I">INACTIVO</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row veh-status-row">
                                    <div class="col-xs-12">
                                        <div id="Veh_Pla_Est"></div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"><i class="glyphicon glyphicon-cog"></i> Especificaciones</legend>
                                <div class="row">
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="Marca">Marca:</label>
                                            <div class="col-xs-11">
                                                <input type="text" id="Veh_Mar" name="Veh_Mar" class="form-control input-xs" required placeholder="Marca" maxlength="30">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="Modelo">Modelo:</label>
                                            <div class="col-xs-11">
                                                <input type="text" id="Veh_Mde" name="Veh_Mde" class="form-control input-xs" required placeholder="Modelo" maxlength="50">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="Año Modelo">Año Modelo:</label>
                                            <div class="col-xs-11">
                                                <input type="text" id="Veh_Amo" name="Veh_Amo" class="form-control input-xs text-center" required placeholder="AAAA" maxlength="4">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Peso en toneladas">Peso (Tn):</label>
                                            <div class="col-xs-11">
                                                <input type="text" id="Veh_Pes" name="Veh_Pes" class="form-control input-xs text-right" placeholder="Tn" maxlength="10">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-4">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="Color 1">Color 1:</label>
                                            <div class="col-xs-10">
                                                <input type="text" id="Veh_Col" name="Veh_Col" class="form-control input-xs" required placeholder="Color 1" maxlength="20">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-4">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Color 2">Color 2:</label>
                                            <div class="col-xs-11">
                                                <input type="text" id="Veh_Col2" name="Veh_Col2" class="form-control input-xs" placeholder="Color 2" maxlength="20">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-4">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="Capacidad Kg">Capacidad Kg:</label>
                                            <div class="col-xs-11">
                                                <input type="number" id="Veh_Cap" name="Veh_Cap" class="form-control input-xs text-right" required placeholder="Kg" step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB MATRÍCULA ==================== -->
                <div role="tabpanel" class="tab-pane" id="vehTabMatricula">
                    <div class="row">
                        <div class="col-xs-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"><i class="glyphicon glyphicon-barcode"></i> Motor y Chasis</legend>
                                <div class="row">
                                    <div class="col-xs-4">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="N° Motor">N° Motor:</label>
                                            <div class="col-xs-12">
                                                <input type="text" id="Mat_Nmo" name="Mat_Nmo" class="form-control input-xs" required placeholder="Motor" maxlength="30">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-4">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="N° Chasis">N° Chasis:</label>
                                            <div class="col-xs-12">
                                                <input type="text" id="Mat_Cha" name="Mat_Cha" class="form-control input-xs" required placeholder="Chasis" maxlength="50">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-4">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="RAMV / CPN">RAMV / CPN:</label>
                                            <div class="col-xs-12">
                                                <input type="text" id="Mat_Ram" name="Mat_Ram" class="form-control input-xs" required placeholder="RAMV" maxlength="30">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="Cilindraje">Cilindraje:</label>
                                            <div class="col-xs-12">
                                                <input type="number" id="Mat_Cil" name="Mat_Cil" class="form-control input-xs text-right" required placeholder="cc" step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Combustible">Combustible:</label>
                                            <div class="col-xs-12">
                                                <select id="Mat_Tco" name="Mat_Tco" class="form-control input-xs select-wide chosen-select">
                                                    <option value="D">DIÉSEL</option>
                                                    <option value="G">GASOLINA</option>
                                                    <option value="E">ELÉCTRICO</option>
                                                    <option value="H">HÍBRIDO</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Clase">Clase vehiculo:</label>
                                            <div class="col-xs-12">
                                                <input type="text" id="Mat_Cve" name="Mat_Cve" class="form-control input-xs" placeholder="Clase" maxlength="20">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Tipo Específico">Tipo Vehiculo</label>
                                            <div class="col-xs-12">
                                                <input type="text" id="Mat_Tip" name="Mat_Tip" class="form-control input-xs" placeholder="Tipo" maxlength="20">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"><i class="glyphicon glyphicon-file"></i> Datos de Matrícula</legend>
                                <div class="row">
                                    <div class="col-xs-4">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Ciudad emisión matrícula">Ciudad Emisión:</label>
                                            <div class="col-xs-12">
                                                <select id="Ciu_Cod" name="Ciu_Cod" class="form-control input-xs select-wide chosen-select">
                                                    <option value="">Seleccione ciudad...</option>
                                                    <?php foreach ($ciudades as $ciu) { ?>
                                                        <option value="<?php echo $ciu['Ciu_Cod']; ?>"><?php echo htmlspecialchars($ciu['Ciu_Des']); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-4">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="Fecha emisión">Fec. Emisión:</label>
                                            <div class="col-xs-12">
                                                <input type="date" id="Mat_Fem" name="Mat_Fem" class="form-control input-xs input-date-wide" required min="2000-01-01" max="2050-12-31">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-4">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs required" title="Fecha caducidad">Fec. Caducidad:</label>
                                            <div class="col-xs-12">
                                                <input type="date" id="Mat_Fca" name="Mat_Fca" class="form-control input-xs input-date-wide" required min="2000-01-01" max="2050-12-31">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Pasajeros">Pasajeros:</label>
                                            <div class="col-xs-12">
                                                <input type="number" id="Mat_Npa" name="Mat_Npa" class="form-control input-xs text-right" placeholder="N°" step="1" min="0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="País de origen">Origen:</label>
                                            <div class="col-xs-12">
                                                <input type="text" id="Mat_Ori" name="Mat_Ori" class="form-control input-xs" placeholder="País" maxlength="20">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Carrocería">Carrocería:</label>
                                            <div class="col-xs-12">
                                                <input type="text" id="Mat_Car" name="Mat_Car" class="form-control input-xs" placeholder="Carrocería" maxlength="20">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-3">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Tipo peso">Tipo Peso:</label>
                                            <div class="col-xs-12">
                                                <select id="Mat_Tpe" name="Mat_Tpe" class="form-control input-xs select-wide chosen-select">
                                                    <option value="">Seleccione...</option>
                                                    <option value="LIVIANO (<=3.5T)">LIVIANO (&lt;=3.5T)</option>
                                                    <option value="PESADO (>3.5T)">PESADO (&gt;3.5T)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Digitador">Digitador:</label>
                                            <div class="col-xs-12">
                                                <input type="text" id="Mat_Deg" name="Mat_Deg" class="form-control input-xs" placeholder="Digitador" maxlength="20">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label class="col-xs-12 control-label label-xs" title="Estado matrícula">Estado Matrícula:</label>
                                            <div class="col-xs-12">
                                                <select id="Mat_Est" name="Mat_Est" class="form-control input-xs select-wide chosen-select">
                                                    <option value="A">ACTIVO</option>
                                                    <option value="I">INACTIVO</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"><i class="glyphicon glyphicon-camera"></i> Documento de Matrícula</legend>
                                <div class="mat-adj-top-row">
                                    <div class="mat-adj-modo-wrap">
                                        <label class="mat-adj-modo-opt">
                                            <input type="radio" name="Mat_Adj_Modo" value="pdf" checked onchange="toggleMatAdjModo('pdf');">
                                            <span><i class="glyphicon glyphicon-file"></i> Subir PDF</span>
                                        </label>
                                        <label class="mat-adj-modo-opt">
                                            <input type="radio" name="Mat_Adj_Modo" value="fotos" onchange="toggleMatAdjModo('fotos');">
                                            <span><i class="glyphicon glyphicon-camera"></i> Fotos Frente + Reverso</span>
                                        </label>
                                    </div>
                                    <div id="matAdjActualBox" class="mat-adj-actual" style="display:none;">
                                        <span class="mat-adj-actual-label">Archivo actual:</span>
                                        <div id="preview_Mat_Adj" class="preview-doc-box"></div>
                                    </div>
                                </div>

                                <div id="matAdjPdfBlock" class="mat-adj-block">
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <div class="form-group">
                                                <label class="col-xs-12 control-label label-xs" title="Archivo PDF de matrícula">Archivo PDF:</label>
                                                <div class="col-xs-12">
                                                    <input type="file" id="Mat_Adj_Pdf" name="Mat_Adj_Pdf" class="form-control input-xs input-file-compressed" accept=".pdf,application/pdf">
                                                    <div id="preview_Mat_Adj_Pdf" class="preview-doc-box"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="matAdjFotosBlock" class="mat-adj-block" style="display:none;">
                                    <p class="mat-adj-hint">Capture o seleccione las 2 caras. Al guardar, el sistema las unirá en un solo archivo.</p>
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label class="col-xs-12 control-label label-xs required" title="Frente de matrícula">Frente:</label>
                                                <div class="col-xs-12">
                                                    <input type="file" id="Mat_Adj_Frente" name="Mat_Adj_Frente" class="form-control input-xs input-file-compressed" accept="image/*" capture="environment">
                                                    <div id="preview_Mat_Adj_Frente" class="preview-doc-box"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label class="col-xs-12 control-label label-xs required" title="Reverso de matrícula">Reverso:</label>
                                                <div class="col-xs-12">
                                                    <input type="file" id="Mat_Adj_Reverso" name="Mat_Adj_Reverso" class="form-control input-xs input-file-compressed" accept="image/*" capture="environment">
                                                    <div id="preview_Mat_Adj_Reverso" class="preview-doc-box"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="veh-modal-actions">
            <button id="btnGuardarVehiculo" class="btn btn-primary" type="button" onclick="guardarVehiculo();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar Vehículo</button>
            <button class="btn btn-danger" type="button" onclick="$('#vehiculoDialog').dialog('close');" style="margin-left: 5px;"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>
    <!-- Modal QR Vehículo -->
    <div id="qrVehiculoDialog" title="Código QR del Vehículo" style="display: none;">
        <div id="qrVehiculoContainer">
            <h4 id="qrVehiculoTitulo"></h4>
            <img id="qrVehiculoImg" src="" alt="Código QR" width="180" height="180">
        </div>
    </div>

    <!-- Modal Previsualizador de Documentos y Fotos -->
    <div id="previewDocModal" title="Previsualización de Documento" style="display: none;">
        <div id="previewDocContent" style="text-align: center; padding: 5px;">
            <img id="previewDocImg" src="" alt="Vista previa de foto" style="max-width: 100%; max-height: 72vh; border-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); display: none;" />
            <iframe id="previewDocPdf" src="" style="width: 100%; height: 72vh; border: none; display: none;"></iframe>
        </div>
        <div style="text-align: center; margin-top: 10px;">
            <button class="btn btn-sm btn-danger" type="button" onclick="$('#previewDocModal').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cerrar</button>
        </div>
    </div>

    <!-- Diálogo de Alerta UI Model3 (Sustituto Elegante de Cuadros Emergentes del Navegador) -->
    <div id="alertCustomDialog" title="Notificación" style="display: none;">
        <div style="margin-top: 10px; display: flex; align-items: flex-start;">
            <span id="alertCustomIcon" class="glyphicon glyphicon-info-sign" style="font-size: 24px; margin-right: 12px;"></span>
            <div id="alertCustomMessage" style="font-size: 12px; line-height: 1.5; color: #333;"></div>
        </div>
    </div>

    <!-- JS Scripts Inclusion con parámetro de cache-busting -->
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/man_val_sanciones.js?e=1"></script>
    <script type="text/javascript" src="../VALIDACIONES/man_val_datos_choferes_vehiculos.js?e=59"></script>
</body>

</html>

<!-- Cierre de conexiones y liberacion de memoria -->
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>
