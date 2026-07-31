<?php
// ob_start();
// function _suppress_free_result_warning($errno, $errstr) {
//     if ($errno === E_WARNING && strpos($errstr, 'mysqli_free_result') !== false) return true;
//     return false;
// }
// set_error_handler('_suppress_free_result_warning', E_WARNING);

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

$obBD_conexion = new Class_Log_Conexion_Datos_Choferes_Vehiculos($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Choferes_Vehiculos;

// Cargar catálogos iniciales
$transportes = $obBD_con1->getArrayConsulta(1, array($Ses_Emp_Cod), $obBD_conexion);
$obBD_con1->utf8_change_param($transportes);

$plantas = $obBD_con1->getArrayConsulta(2, array(), $obBD_conexion);
$obBD_con1->utf8_change_param($plantas);

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

// Función auxiliar para responder JSON limpio sin interferencia de buffers o advertencias de PHP
function responderJsonLimpio($response)
{
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
    if (isset($req['op_opciones']) && isset($req['search']) && !empty($req['search'])) {
        $params['op_opciones'] = $req['op_opciones'];
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

// 5. Buscar persona por Cédula / Identificación
if (isset($_GET['buscarPersonaCedulaAjax'])) {
    $resp = array('success' => true, 'existe' => false);
    $ced = isset($_GET['Prs_Ced']) ? trim($_GET['Prs_Ced']) : '';
    if (!empty($ced)) {
        $persona = $obBD_con1->getRowConsulta(6, array($ced), $obBD_conexion);
        if (!empty($persona)) {
            $resp['existe'] = true;
            $resp['persona'] = $persona;
            $obBD_con1->utf8_change_param($resp['persona']);
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

// 6. Guardar Chofer Completo: Comprime la Imagen y Verifica que el Resultado sea <= 5 MB
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
    // Limpiar caracteres especiales del número de licencia (solo alfanumérico)
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

    // Directorio Especificado: relavera/RECURSOS/archivos_adjuntos/choferes/{Cho_Ced}/
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

        // 2. Procesar Archivos Adjuntos: Se comprime la imagen y se evalúa el resultado final (< 5 MB)
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
                        // Intenta comprimir la imagen
                        $compSuccess = optimizarYComprimirImagen($tmpPath, $targetPath, 1920, 85);
                        if (!$compSuccess || !file_exists($targetPath)) {
                            throw new Exception("No se pudo procesar la imagen para '$fieldLabel'. Verifique que el archivo sea una imagen válida.");
                        }

                        // Verificar el peso FINAL de la imagen optimizada resultante
                        $finalSize = filesize($targetPath);
                        if ($finalSize > $maxAllowedBytes) {
                            @unlink($targetPath); // Elimina el archivo resultante pesado
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

                    // Guardar la ruta relativa estandarizada
                    $uploadedFiles[$field] = '../RECURSOS/archivos_adjuntos/choferes/' . $Cho_Ced . '/' . $filename;
                }
            }
        }

        // 3. Guardar o Actualizar Chofer
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
            $rel = $obBD_con1->getRowConsulta(9, array($Cho_Cod), $obBD_conexion);
            if (empty($rel)) {
                $obBD_con1->operacionobBD('manifiesto_chofer.insert', array('Cho_Cod' => $Cho_Cod, 'Pla_Cod' => $Pla_Cod), $obBD_conexion);
            } else {
                $obBD_con1->operacionobBD('manifiesto_chofer.update', array('Pla_Cod' => $Pla_Cod, 'where' => array('Cho_Cod' => $Cho_Cod)), $obBD_conexion);
            }
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al actualizar Planta del Chofer: " . $errMsg);
            }
        }

        // 5. Guardar o Actualizar Capacitaciones (manifiesto_chofer_capaci)
        if (!empty($Cho_Cod)) {
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
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al guardar Capacitaciones del Chofer: " . $errMsg);
            }
        }

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

// 10. Guardar Vehículo Completo (vehiculo + manifiesto_vehiculo + manifiesto_matricula_vehiculo)
if (isset($_POST['saveVehiculoAjax'])) {
    $resp = array('success' => false);
    $Veh_Cod = isset($_POST['Veh_Cod']) ? trim($_POST['Veh_Cod']) : '';
    $Pla_Cod = !empty($_POST['Pla_Cod']) ? trim($_POST['Pla_Cod']) : null;
    $Mat_Cod = !empty($_POST['Mat_Cod']) ? trim($_POST['Mat_Cod']) : null;
    $Veh_Pla = isset($_POST['Veh_Pla']) ? strtoupper(trim($_POST['Veh_Pla'])) : '';
    $Veh_Mar = isset($_POST['Veh_Mar']) ? addslashes(trim($_POST['Veh_Mar'])) : '';
    $Veh_Col = isset($_POST['Veh_Col']) ? addslashes(trim($_POST['Veh_Col'])) : '';
    $Veh_Cap = isset($_POST['Veh_Cap']) ? floatval($_POST['Veh_Cap']) : 0;
    $Veh_Tit = isset($_POST['Veh_Tit']) ? $_POST['Veh_Tit'] : 'V';
    $Veh_Est = isset($_POST['Veh_Est']) ? $_POST['Veh_Est'] : 'A';

    // Campos de Matricula y Propietario (manifiesto_matricula_vehiculo)
    $Mat_Pro_Nom = isset($_POST['Mat_Pro_Nom']) ? addslashes(trim($_POST['Mat_Pro_Nom'])) : '';
    $Mat_Pro_Id  = isset($_POST['Mat_Pro_Id']) ? trim($_POST['Mat_Pro_Id']) : '';
    $Mat_Pro_Prv = isset($_POST['Mat_Pro_Prv']) ? addslashes(trim($_POST['Mat_Pro_Prv'])) : '';
    $Mat_Pro_Can = isset($_POST['Mat_Pro_Can']) ? addslashes(trim($_POST['Mat_Pro_Can'])) : '';
    $Mat_Pro_Dir = isset($_POST['Mat_Pro_Dir']) ? addslashes(trim($_POST['Mat_Pro_Dir'])) : '';
    $Mat_Pro_Tel = isset($_POST['Mat_Pro_Tel']) ? trim($_POST['Mat_Pro_Tel']) : '';

    $Mat_Ctr = isset($_POST['Mat_Ctr']) ? addslashes(trim($_POST['Mat_Ctr'])) : '';
    $Mat_Ttr = isset($_POST['Mat_Ttr']) ? addslashes(trim($_POST['Mat_Ttr'])) : '';
    $Mat_Aop = isset($_POST['Mat_Aop']) ? addslashes(trim($_POST['Mat_Aop'])) : '';
    $Mat_Otr = isset($_POST['Mat_Otr']) ? addslashes(trim($_POST['Mat_Otr'])) : '';
    $Mat_Dis = isset($_POST['Mat_Dis']) ? trim($_POST['Mat_Dis']) : '';

    $Mat_Ava = !empty($_POST['Mat_Ava']) ? floatval($_POST['Mat_Ava']) : null;
    $Mat_Vma = !empty($_POST['Mat_Vma']) ? floatval($_POST['Mat_Vma']) : null;
    $Mat_Fco = !empty($_POST['Mat_Fco']) ? $_POST['Mat_Fco'] : null;
    $Mat_Dig = isset($_POST['Mat_Dig']) ? addslashes(trim($_POST['Mat_Dig'])) : '';

    $Mat_Nma = isset($_POST['Mat_Nma']) ? trim($_POST['Mat_Nma']) : '';
    $Mat_Fem = !empty($_POST['Mat_Fem']) ? $_POST['Mat_Fem'] : null;
    $Mat_Fve = !empty($_POST['Mat_Fve']) ? $_POST['Mat_Fve'] : null;
    $Mat_Lem = isset($_POST['Mat_Lem']) ? addslashes(trim($_POST['Mat_Lem'])) : '';

    $Mat_Pan = isset($_POST['Mat_Pan']) ? strtoupper(trim($_POST['Mat_Pan'])) : '';
    $Mat_Ano = isset($_POST['Mat_Ano']) ? trim($_POST['Mat_Ano']) : '';
    $Mat_Nmo = isset($_POST['Mat_Nmo']) ? addslashes(trim($_POST['Mat_Nmo'])) : '';
    $Mat_Cha = isset($_POST['Mat_Cha']) ? addslashes(trim($_POST['Mat_Cha'])) : '';
    $Mat_Ram = isset($_POST['Mat_Ram']) ? addslashes(trim($_POST['Mat_Ram'])) : '';
    $Mat_Mar = isset($_POST['Mat_Mar']) ? addslashes(trim($_POST['Mat_Mar'])) : $Veh_Mar;
    $Mat_Mde = isset($_POST['Mat_Mde']) ? addslashes(trim($_POST['Mat_Mde'])) : '';
    $Mat_Cil = !empty($_POST['Mat_Cil']) ? floatval($_POST['Mat_Cil']) : null;
    $Mat_Amo = isset($_POST['Mat_Amo']) ? trim($_POST['Mat_Amo']) : '';
    $Mat_Cve = isset($_POST['Mat_Cve']) ? addslashes(trim($_POST['Mat_Cve'])) : '';
    $Mat_Tip = isset($_POST['Mat_Tip']) ? addslashes(trim($_POST['Mat_Tip'])) : '';
    $Mat_Npa = !empty($_POST['Mat_Npa']) ? intval($_POST['Mat_Npa']) : null;
    $Mat_Ton = isset($_POST['Mat_Ton']) ? trim($_POST['Mat_Ton']) : '';
    $Mat_Ori = isset($_POST['Mat_Ori']) ? addslashes(trim($_POST['Mat_Ori'])) : '';
    $Mat_Tco = isset($_POST['Mat_Tco']) ? $_POST['Mat_Tco'] : 'D';
    $Mat_Car = isset($_POST['Mat_Car']) ? addslashes(trim($_POST['Mat_Car'])) : '';
    $Mat_Tpe = isset($_POST['Mat_Tpe']) ? $_POST['Mat_Tpe'] : 'PESADO (>3.5T)';

    $Mat_Co1 = isset($_POST['Mat_Co1']) ? addslashes(trim($_POST['Mat_Co1'])) : $Veh_Col;
    $Mat_Co2 = isset($_POST['Mat_Co2']) ? addslashes(trim($_POST['Mat_Co2'])) : '';
    $Mat_Ort = isset($_POST['Mat_Ort']) ? $_POST['Mat_Ort'] : 'N';
    $Mat_Rem = isset($_POST['Mat_Rem']) ? $_POST['Mat_Rem'] : 'N';
    $Mat_Obs = isset($_POST['Mat_Obs']) ? addslashes(trim($_POST['Mat_Obs'])) : '';

    $datosVehiculo = array(
        'Veh_Mar' => $Veh_Mar,
        'Veh_Pla' => $Veh_Pla,
        'Veh_Col' => $Veh_Col,
        'Veh_Cap' => $Veh_Cap,
        'Veh_Tit' => $Veh_Tit,
        'Emp_Cod' => $Ses_Emp_Cod,
        'Mat_Cod' => $Mat_Cod,
        'Veh_Est' => $Veh_Est
    );

    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
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

        // 3. Guardar o Actualizar Matrícula (manifiesto_matricula_vehiculo)
        if (!empty($Veh_Cod)) {
            $datosMatricula = array(
                'Veh_Cod' => $Veh_Cod,
                'Mat_Pro_Nom' => $Mat_Pro_Nom,
                'Mat_Pro_Id'  => $Mat_Pro_Id,
                'Mat_Pro_Prv' => $Mat_Pro_Prv,
                'Mat_Pro_Can' => $Mat_Pro_Can,
                'Mat_Pro_Dir' => $Mat_Pro_Dir,
                'Mat_Pro_Tel' => $Mat_Pro_Tel,
                'Mat_Ctr' => $Mat_Ctr,
                'Mat_Ttr' => $Mat_Ttr,
                'Mat_Aop' => $Mat_Aop,
                'Mat_Otr' => $Mat_Otr,
                'Mat_Dis' => $Mat_Dis,
                'Mat_Dig' => $Mat_Dig,
                'Mat_Nma' => $Mat_Nma,
                'Mat_Lem' => $Mat_Lem,
                'Mat_Pla' => $Veh_Pla,
                'Mat_Pan' => $Mat_Pan,
                'Mat_Ano' => $Mat_Ano,
                'Mat_Nmo' => $Mat_Nmo,
                'Mat_Cha' => $Mat_Cha,
                'Mat_Ram' => $Mat_Ram,
                'Mat_Mar' => $Mat_Mar,
                'Mat_Mde' => $Mat_Mde,
                'Mat_Amo' => $Mat_Amo,
                'Mat_Cve' => $Mat_Cve,
                'Mat_Tip' => $Mat_Tip,
                'Mat_Ton' => $Mat_Ton,
                'Mat_Ori' => $Mat_Ori,
                'Mat_Tco' => $Mat_Tco,
                'Mat_Car' => $Mat_Car,
                'Mat_Tpe' => $Mat_Tpe,
                'Mat_Co1' => $Mat_Co1,
                'Mat_Co2' => $Mat_Co2,
                'Mat_Ort' => $Mat_Ort,
                'Mat_Rem' => $Mat_Rem,
                'Mat_Obs' => $Mat_Obs
            );

            if (!empty($Mat_Fem)) $datosMatricula['Mat_Fem'] = $Mat_Fem;
            if (!empty($Mat_Fve)) $datosMatricula['Mat_Fve'] = $Mat_Fve;
            if (!empty($Mat_Fco)) $datosMatricula['Mat_Fco'] = $Mat_Fco;
            if (!is_null($Mat_Ava)) $datosMatricula['Mat_Ava'] = $Mat_Ava;
            if (!is_null($Mat_Vma)) $datosMatricula['Mat_Vma'] = $Mat_Vma;
            if (!is_null($Mat_Cil)) $datosMatricula['Mat_Cil'] = $Mat_Cil;
            if (!is_null($Mat_Npa)) $datosMatricula['Mat_Npa'] = $Mat_Npa;

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
    <title>Datos de Choferes y Vehículos</title>
    <!-- Framework & CSS Requirements -->
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <?php require_once("../../mascaras/model3/estilos/estilos.php") ?>
    <link rel="stylesheet" type="text/css" href="../RECURSOS/datos_choferes_vehiculos.css">
</head>

<body>
    <div class="panel panel-default panel-main exa-ui-panel">
        <!-- Encabezado Estilo Model3 -->
        <div class="panel-heading exa-header">
            <h3 class="panel-title"><span class="glyphicon glyphicon-list-alt"></span> Datos de Choferes y Vehículos</h3>
        </div>

        <div class="panel-body exa-body">
            <!-- Pestañas (Tabs) -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs" role="tablist">
                    <!-- <li role="presentation">
                        <a href="#tabEmpresasTransporte" aria-controls="tabEmpresasTransporte" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-truck icon-tab"></i>Empresas Transporte
                        </a>
                    </li> -->
                    <li role="presentation" class="active">
                        <a href="#tabChoferes" aria-controls="tabChoferes" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-user icon-tab"></i>Choferes
                        </a>
                    </li>
                    <!-- <li role="presentation">
                        <a href="#tabVehiculos" aria-controls="tabVehiculos" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-road icon-tab"></i>Vehículos
                        </a>
                    </li> -->
                </ul>

                <div class="tab-content">
                    <!-- ==================== TAB 1: EMPRESAS TRANSPORTE (COMENTADO) ==================== -->
                    <!--
                    <div role="tabpanel" class="tab-pane" id="tabEmpresasTransporte">
                        <div class="btn-toolbar" style="margin-bottom: 10px;">
                            <button class="btn btn-success" onclick="abrirModalEmpresaTransporte();">
                                <i class="glyphicon glyphicon-plus"></i> Nueva Empresa
                            </button>
                        </div>
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroEmpresasTransporteForm" class="form-horizontal normal" onsubmit="event.preventDefault(); actualizarGridEmpresasTransporte();">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radTransporte1" name="op_opciones" type="radio" value="n" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radTransporte1">Nombre</label>
                                                <input id="radTransporte2" name="op_opciones" type="radio" value="m" onclick="setfocus(this.form.search)" />
                                                <label for="radTransporte2">Licencia MAE</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Búsqueda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridEmpresasTransporte(); }" />
                                                    <span class="input-group-btn">
                                                        <button type="button" onclick="actualizarGridEmpresasTransporte();" class="btn btn-success btn-xs" title="Buscar">
                                                            <span class="glyphicon glyphicon-search"></span> Buscar
                                                        </button>
                                                    </span>
                                                </div>
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
                    -->

                    <!-- ==================== TAB 2: CHOFERES (ACTIVO) ==================== -->
                    <div role="tabpanel" class="tab-pane active" id="tabChoferes">
                        <div class="btn-toolbar" style="margin-bottom: 10px;">
                            <button class="btn btn-success" onclick="abrirModalChofer();">
                                <i class="glyphicon glyphicon-plus"></i> Nuevo Chofer
                            </button>
                        </div>
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroChoferesForm" class="form-horizontal normal" onsubmit="event.preventDefault(); actualizarGridChoferes();">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radChofer1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer1">Nombre Chofer</label>
                                                <input id="radChofer2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer2">Cédula</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Búsqueda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridChoferes(); }" />
                                                    <span class="input-group-btn">
                                                        <button type="button" onclick="actualizarGridChoferes();" class="btn btn-success btn-xs" title="Buscar">
                                                            <span class="glyphicon glyphicon-search"></span> Buscar
                                                        </button>
                                                    </span>
                                                </div>
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

                    <!-- ==================== TAB 3: VEHÍCULOS (COMENTADO) ==================== -->
                    <!--
                    <div role="tabpanel" class="tab-pane" id="tabVehiculos">
                        <div class="btn-toolbar" style="margin-bottom: 10px;">
                            <button class="btn btn-success" onclick="abrirModalVehiculo();">
                                <i class="glyphicon glyphicon-plus"></i> Nuevo Vehículo
                            </button>
                        </div>
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroVehiculosForm" class="form-horizontal normal" onsubmit="event.preventDefault(); actualizarGridVehiculos();">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radVehiculo1" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo1">Placa</label>
                                                <input id="radVehiculo2" name="op_opciones" type="radio" value="pn" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo2">Nombre Planta</label>
                                                <input id="radVehiculo3" name="op_opciones" type="radio" value="pl" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo3">Licencia Planta</label>
                                                <input id="radVehiculo4" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo4">Cédula/RUC Cliente</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Búsqueda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridVehiculos(); }" />
                                                    <span class="input-group-btn">
                                                        <button type="button" onclick="actualizarGridVehiculos();" class="btn btn-success btn-xs" title="Buscar">
                                                            <span class="glyphicon glyphicon-search"></span> Buscar
                                                        </button>
                                                    </span>
                                                </div>
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
                    -->
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODALES / DIÁLOGOS ==================== -->

    <!-- Modal Empresa de Transporte -->
    <div id="empresaTransporteDialog" title="Registrar Empresa de Transporte" style="display: none;">
        <form id="empresaTransporteForm" class="form-horizontal normal">
            <input type="hidden" id="Mat_Cod" name="Mat_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required" title="Descripción / Nombre de la empresa">Descripción / Nombre:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Des" name="Mat_Des" class="form-control input-xs" required placeholder="Nombre de la empresa">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs" title="Número de RUC / Cédula / Licencia MAE">Licencia MAE / RUC:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Mae" name="Mat_Mae" class="form-control input-xs" placeholder="Número de RUC / Licencia MAE" maxlength="30">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs" title="Teléfono de Contacto">Teléfono:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Tel" name="Mat_Tel" class="form-control input-xs" placeholder="Teléfono" maxlength="20">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs" title="Persona de Contacto">Persona Contacto:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Pco" name="Mat_Pco" class="form-control input-xs" placeholder="Nombre de contacto" maxlength="50">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs" title="Dirección de la Empresa">Dirección:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Dir" name="Mat_Dir" class="form-control input-xs" placeholder="Dirección">
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button id="btnGuardarEmpresa" class="btn btn-sm btn-primary" type="button" onclick="guardarEmpresaTransporte();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-danger" type="button" onclick="$('#empresaTransporteDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Chofer Completo (Layout Vertical Apilado 920px) -->
    <div id="choferDialog" title="Registrar Chofer" style="display: none;">
        <form id="choferForm" class="form-horizontal normal" enctype="multipart/form-data">
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
                        <input type="hidden" id="Cho_Pla_Cod" name="Pla_Cod" value="">

                        <!-- CAMPOS LABORALES OCULTADOS (COMENTADOS EN HTML PARA PRESERVAR CÓDIGO ORIGINAL):
                        <select id="Cho_Pla_Cod_Visible" name="Pla_Cod"><option value="">Seleccione Planta...</option></select>
                        <input type="text" id="Cho_Car_Visible" name="Cho_Car" value="Chofer">
                        <select id="Cho_Est_Visible" name="Cho_Est"><option value="A">ACTIVO</option></select>
                        <select id="Cho_Tco_Visible" name="Cho_Tco"><option value="Indefinido">Indefinido</option></select>
                        -->

                        <div class="row">
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Cédula / RUC / Pasaporte / Documento de Identidad">Cédula / Doc. Id:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Cho_Ced" name="Cho_Ced" class="form-control input-xs" required placeholder="N° Identificación" maxlength="20" onchange="buscarPersonaCedula(this.value);">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Nombres Completos">Nombres Completos:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Prs_Nom" name="Prs_Nom" class="form-control input-xs" required placeholder="Nombres del chofer">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-5">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Apellidos Completos">Apellidos Completos:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Prs_Ape" name="Prs_Ape" class="form-control input-xs" required placeholder="Apellidos del chofer">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Fecha de Nacimiento del Chofer">Fec. Nacimiento:</label>
                                    <div class="col-xs-12">
                                        <input type="date" id="Prs_Fec" name="Prs_Fec" class="form-control input-xs input-date-wide" min="1940-01-01" max="2015-12-31" onchange="calcularEdad(this.value);">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Edad calculada automáticamente">Edad:</label>
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
                        </div>
                    </fieldset>
                </div>
            </div>

            <!-- BLOQUE 2: LICENCIA DE CONDUCIR -->
            <div class="row" style="margin-top: 6px;">
                <div class="col-xs-12">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-credit-card"></i> 2. Licencia de Conducir</legend>
                        <div class="row">
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Tipo o Categoría de Licencia">Tipo Licencia:</label>
                                    <div class="col-xs-12">
                                        <select id="Cho_Tli" name="Cho_Tli" class="form-control input-xs select-wide chosen-select" required>
                                            <option value="">Licencia...</option>
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
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Licencia Digital (PDF/Imagen)">Licencia Digital:</label>
                                    <div class="col-xs-12">
                                        <input type="file" id="Cho_Doc_Ldi" name="Cho_Doc_Ldi" class="form-control input-xs input-file-compressed" accept=".pdf,image/*">
                                        <div id="preview_Cho_Doc_Ldi" class="preview-doc-box"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
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
            <button class="btn btn-danger" type="button" onclick="$('#choferDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Vehículo Completo (Layout Multicolumna 1250px con 6 Fieldsets) -->
    <div id="vehiculoDialog" title="Registrar Vehículo" style="display: none;">
        <form id="vehiculoForm" class="form-horizontal normal">
            <input type="hidden" id="Veh_Cod" name="Veh_Cod">

            <!-- BLOQUE 1: PROPIETARIO Y ASIGNACIÓN -->
            <div class="row">
                <div class="col-xs-6">
                    <fieldset class="exa-fieldset height-sync">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-user"></i> 1. Datos del Propietario</legend>
                        <div class="row">
                            <div class="col-xs-5">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="C.I. / Pasaporte / RUC Propietario">C.I. / RUC / Pasap.:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Pro_Id" name="Mat_Pro_Id" class="form-control input-xs" placeholder="Identificación" maxlength="20" onchange="buscarPersonaPropietario(this.value);" onkeyup="if(event.keyCode===13){ buscarPersonaPropietario(this.value); }">
                                        <div id="Mat_Pro_Id_Est" style="margin-top: 2px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-7">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Nombre Completo del Propietario">Nombre Propietario:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Pro_Nom" name="Mat_Pro_Nom" class="form-control input-xs" placeholder="Nombres del propietario" maxlength="150">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Provincia Residencial">Provincia:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Pro_Prv" name="Mat_Pro_Prv" class="form-control input-xs" placeholder="Provincia" maxlength="50">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Cantón Residencial">Cantón:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Pro_Can" name="Mat_Pro_Can" class="form-control input-xs" placeholder="Cantón" maxlength="50">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Teléfono del Propietario">Teléfono:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Pro_Tel" name="Mat_Pro_Tel" class="form-control input-xs" placeholder="Teléfono" maxlength="30">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="col-xs-6">
                    <fieldset class="exa-fieldset height-sync">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-road"></i> 2. Asignación y Datos Principales</legend>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Planta de Beneficio">Planta Beneficio:</label>
                                    <div class="col-xs-12">
                                        <select id="Veh_Pla_Cod" name="Pla_Cod" class="form-control input-xs select-wide chosen-select">
                                            <option value="">Seleccione Planta...</option>
                                            <?php foreach ($plantas as $row) { ?>
                                                <option value="<?php echo $row['Pla_Cod']; ?>"><?php echo $row['Pla_Nom']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Empresa de Transporte">Empresa Transporte:</label>
                                    <div class="col-xs-12">
                                        <select id="Mat_Cod" name="Mat_Cod" class="form-control input-xs select-wide chosen-select" required>
                                            <option value="">Seleccione Empresa...</option>
                                            <?php foreach ($transportes as $row) { ?>
                                                <option value="<?php echo $row['Mat_Cod']; ?>"><?php echo $row['Mat_Des']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Placa Actual del Vehículo">Placa Actual:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Veh_Pla" name="Veh_Pla" class="form-control input-xs bold text-uppercase" required placeholder="Ej: ABC-1234" maxlength="10" onchange="validarPlacaVehiculo(this.value);" onkeyup="this.value = this.value.toUpperCase();">
                                        <div id="Veh_Pla_Est" style="margin-top: 2px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Placa Anterior (Si aplica)">Placa Anterior:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Pan" name="Mat_Pan" class="form-control input-xs text-uppercase" placeholder="Placa anterior" maxlength="10" onkeyup="this.value = this.value.toUpperCase();">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Estado Operativo del Vehículo">Estado:</label>
                                    <div class="col-xs-12">
                                        <select id="Veh_Est" name="Veh_Est" class="form-control input-xs select-wide chosen-select">
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

            <!-- BLOQUE 2: ESPECIFICACIONES TÉCNICAS Y COLORES -->
            <div class="row" style="margin-top: 4px;">
                <div class="col-xs-6">
                    <fieldset class="exa-fieldset height-sync">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-cog"></i> 3. Especificaciones Técnicas y Colores</legend>
                        <div class="row">
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Marca del Vehículo">Marca:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Veh_Mar" name="Veh_Mar" class="form-control input-xs" required placeholder="Marca">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Modelo del Vehículo">Modelo:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Mde" name="Mat_Mde" class="form-control input-xs" placeholder="Modelo">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Año Fabricación">Año Fab.:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Ano" name="Mat_Ano" class="form-control input-xs text-center" placeholder="AAAA" maxlength="4">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Año del Modelo">Año Modelo:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Amo" name="Mat_Amo" class="form-control input-xs text-center" placeholder="AAAA" maxlength="4">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Color Primario / Color 1">Color 1 (Principal):</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Veh_Col" name="Veh_Col" class="form-control input-xs" required placeholder="Color 1">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Color Secundario / Color 2">Color 2 (Secundario):</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Co2" name="Mat_Co2" class="form-control input-xs" placeholder="Color 2">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Capacidad en Kilogramos">Capac. (Kg):</label>
                                    <div class="col-xs-12">
                                        <input type="number" id="Veh_Cap" name="Veh_Cap" class="form-control input-xs text-right" required placeholder="Kg" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Capacidad en Toneladas">Toneladas:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Ton" name="Mat_Ton" class="form-control input-xs text-right" placeholder="Ton">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Tipo Principal">Tipo Modalidad:</label>
                                    <div class="col-xs-12">
                                        <select id="Veh_Tit" name="Veh_Tit" class="form-control input-xs select-wide chosen-select" required>
                                            <option value="V">VOLQUETA</option>
                                            <option value="D">TIPO DUMPER</option>
                                            <option value="C">CAMION</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="col-xs-6">
                    <fieldset class="exa-fieldset height-sync">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-barcode"></i> 4. Motor, Chasis y Mecánica</legend>
                        <div class="row">
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Número de Motor">N° Motor:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Nmo" name="Mat_Nmo" class="form-control input-xs" placeholder="Número de motor" maxlength="30">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Número de Chasis">N° Chasis:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Cha" name="Mat_Cha" class="form-control input-xs" placeholder="Número de chasis" maxlength="50">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Número RAMV / CPN">RAMV / CPN:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Ram" name="Mat_Ram" class="form-control input-xs" placeholder="RAMV / CPN" maxlength="30">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Cilindraje (cc)">Cilindraje:</label>
                                    <div class="col-xs-12">
                                        <input type="number" id="Mat_Cil" name="Mat_Cil" class="form-control input-xs text-right" placeholder="cc" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Tipo de Combustible">Combustible:</label>
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
                                    <label class="col-xs-12 control-label label-xs" title="Clase de Vehículo">Clase Vehículo:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Cve" name="Mat_Cve" class="form-control input-xs" placeholder="Ej: JEPP, VOLQUETA" maxlength="20">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Tipo Específico de Vehículo">Tipo Específico:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Tip" name="Mat_Tip" class="form-control input-xs" placeholder="Ej: DUMPER" maxlength="20">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>

            <!-- BLOQUE 3: REGISTRO MATRÍCULA Y OBSERVACIONES -->
            <div class="row" style="margin-top: 4px;">
                <div class="col-xs-6">
                    <fieldset class="exa-fieldset height-sync">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-file"></i> 5. Matrícula, Fechas y Avalúo</legend>
                        <div class="row">
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="N° de Matrícula">N° Matrícula:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Nma" name="Mat_Nma" class="form-control input-xs" placeholder="N° Matrícula" maxlength="50">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Fecha de Emisión Matrícula">Fec. Emisión:</label>
                                    <div class="col-xs-12">
                                        <input type="date" id="Mat_Fem" name="Mat_Fem" class="form-control input-xs input-date-wide" min="2000-01-01" max="2050-12-31">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Fecha de Vencimiento Matrícula">Fec. Vencimiento:</label>
                                    <div class="col-xs-12">
                                        <input type="date" id="Mat_Fve" name="Mat_Fve" class="form-control input-xs input-date-wide" min="2000-01-01" max="2050-12-31">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Lugar de Emisión">Lugar Emisión:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Lem" name="Mat_Lem" class="form-control input-xs" placeholder="Lugar emisión" maxlength="100">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Fecha de Compra">Fecha Compra:</label>
                                    <div class="col-xs-12">
                                        <input type="date" id="Mat_Fco" name="Mat_Fco" class="form-control input-xs input-date-wide" min="2000-01-01" max="2050-12-31">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Avalúo Comercial ($)">Avalúo ($):</label>
                                    <div class="col-xs-12">
                                        <input type="number" id="Mat_Ava" name="Mat_Ava" class="form-control input-xs text-right" placeholder="0.00" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Valor Matrícula ($)">Valor Matrícula ($):</label>
                                    <div class="col-xs-12">
                                        <input type="number" id="Mat_Vma" name="Mat_Vma" class="form-control input-xs text-right" placeholder="0.00" step="0.01">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="col-xs-6">
                    <fieldset class="exa-fieldset height-sync">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-list-alt"></i> 6. Operación y Observaciones</legend>
                        <div class="row">
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="N° de Disco">Disco:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Dis" name="Mat_Dis" class="form-control input-xs text-center" placeholder="N° Disco" maxlength="20">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Vehículo Ortopédico">Ortopédico:</label>
                                    <div class="col-xs-12">
                                        <select id="Mat_Ort" name="Mat_Ort" class="form-control input-xs select-wide chosen-select">
                                            <option value="N">NO</option>
                                            <option value="S">SÍ</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Motor / Chasis Remarcado">Remarcado:</label>
                                    <div class="col-xs-12">
                                        <select id="Mat_Rem" name="Mat_Rem" class="form-control input-xs select-wide chosen-select">
                                            <option value="N">NO</option>
                                            <option value="S">SÍ</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Digitador del Registro">Digitador:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Dig" name="Mat_Dig" class="form-control input-xs" placeholder="Digitador" maxlength="50">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 4px;">
                            <div class="col-xs-12">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Observaciones Adicionales">Observaciones:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Mat_Obs" name="Mat_Obs" class="form-control input-xs" placeholder="Observaciones generales sobre el vehículo o matrícula">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 10px; margin-bottom: 5px;">
            <button id="btnGuardarVehiculo" class="btn btn-primary" type="button" onclick="guardarVehiculo();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar Vehículo</button>
            <button class="btn btn-danger" type="button" onclick="$('#vehiculoDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
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
    <script type="text/javascript" src="../VALIDACIONES/man_val_datos_choferes_vehiculos.js?e=17"></script>
</body>

</html>

<!-- Cierre de conexiones y liberacion de memoria -->
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>