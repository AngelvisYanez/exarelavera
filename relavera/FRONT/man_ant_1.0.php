<?php

/* DIRECTORIOS REQUERIDOS */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_ant_1.0.php');
require_once(__DIR__ . '/../LOGICA/relavera_whatsapp_utils.php');
require_once('../../contabilidad/LOGICA/con_log_docs.php');    //guarda el comprobante y el asiento 
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Manifiesto($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Manifiesto;
$obBD_con2 = new Class_Log_Datos_Doc;

/* formato para fechas */
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* para pruebas */
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 9600);

/* DECLARACION DE AJAX */

/* Obtiene el cliente del usuario logueado */
$cliente_manifiesto = $obBD_con1->getRowConsulta('manifiesto_usuario.selectWhere', array('where' => array('manifiesto_usuario.Usu_Cod' => $Ses_Usu_Cod)), $obBD_conexion);

// carga el cliente para el usuario logueado
if (isset($loadCliAjax)) {
    $resp = $obBD_con1->getArrayConsulta(1, $Ses_Usu_Cod, $obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit();
}

// Obtener los límites del periodo contable activo para el datepicker
if (isset($obtenerPeriodoMinMax)) {
    $resp = array('success' => false, 'data' => array(), 'message' => '');
    $periodo = $obBD_con1->getRowConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
    
    if ($periodo && !empty($periodo)) {
        $resp['success'] = true;
        $resp['data'] = array(
            'minimo' => $periodo['Pec_Fei'],
            'maximo' => $periodo['Pec_Fef']
        );
    } else {
        $resp['message'] = 'No se encontró un periodo contable activo para establecer los límites de fecha.';
    }
    $obBD_con1->echoJson($resp);
    exit();
}

/* Insertar en pag_anticipo_cli (caso 32) vía AJAX */
if (isset($savePagAntAjax)) {
    $resp = array('success' => false, 'message' => '');
    try {
        if (!isset($_POST['Ama_Cod']) || empty($_POST['Ama_Cod'])) {
            $resp['message'] = 'No se proporcionó el código del anticipo (Ama_Cod)';
            $obBD_con1->echoJson($resp);
            exit();
        }

        $valores = array(
            'Ama_Cod' => $_POST['Ama_Cod'],
            'Com_Cod' => isset($_POST['Com_Cod']) ? $_POST['Com_Cod'] : null,
            'Ama_Val' => isset($_POST['Ama_Val']) ? $_POST['Ama_Val'] : null,
            'Cli_Cod' => isset($_POST['Cli_Cod']) ? $_POST['Cli_Cod'] : null,
            'Ant_Obs' => isset($_POST['Ant_Obs']) ? $_POST['Ant_Obs'] : ''
        );

        $obBD_con1->operacionobBD(32, $valores, $obBD_conexion);

        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
            $resp['message'] = 'Registro en pag_anticipo_cli realizado correctamente';
        } else {
            $resp['message'] = 'Error al registrar en pag_anticipo_cli: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = 'Excepción al registrar en pag_anticipo_cli: ' . $e->getMessage();
    }

    $obBD_con1->echoJson($resp);
    exit();
}

//seccion para obtener los clientes registrados en la empresa
if (isset($clientesAjax)) {

    $data = filter_input_array(INPUT_GET);
    $data['Emp_Cod'] = $Ses_Emp_Cod;

    // Primero contar los registros (sin limits)
    $contar = $obBD_con1->getRowConsulta(22, $data, $obBD_conexion);

    // Calcular la paginación
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rows = isset($_GET['rows']) ? intval($_GET['rows']) : 50;
    $pagination = pages($contar['total'], $page, $rows);

    $responce = $pagination['data'];
    $data['limits'] = $pagination['limits'];

    // Obtener los datos con paginación
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(22, $data, $obBD_conexion);
    } else {
        $responce['rows'] = array();
    }

    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

// Optiene el ultimo manifiesto de anticipo
if (isset($getLastAntAjax)) {
    $resp['success'] = false;

    $last_Ant = $obBD_con1->getRowConsulta(4, "", $obBD_conexion);
    if ($last_Ant['sig'] == 0) {
        $resp['data'] = "1";
    } else {
        $last_Ant1 = $obBD_con1->getRowConsulta(5, $last_Ant['sig'], $obBD_conexion);
        $resp['data'] = ($last_Ant1['Ant_Doc'] + 1);
    }

    if ($obBD_con1->Error == 0) {
        $resp['success'] = true;
        $resp['message'] = "Transaccion exitosa!";
    }
    $obBD_con1->echoJson($resp);
}

/* Carga de datos del cliente para el anticipo */
if (isset($LoadPayment)) {
    $resp['bandera'] = true;
    $resp['message'] = "No se lograron cargar los datos";
    $data_ban = null;
    if ($tipo == 'INICIAL') {
        $data = $obBD_con1->getRowConsulta(6, "", $obBD_conexion);
        $resp['message'] = "ANTICIPOS DE CLIENTES";
    }
    if ($tipo == 'DEP' || $tipo == 'TRF') {
        $data = $obBD_con1->getArrayConsulta(7, array('Ban_Tip' => 'B'), $obBD_conexion);
        $resp['message'] = "PAGOS EN ";
    }

    if (count($data) < 1) {
        $resp['bandera'] = false;
    }

    $resp['data'] = $data;
    $resp['data_ban'] = $data_ban;
    if ($obBD_con1->Error == 0) {
        $resp['success'] = true;
        $resp['message'] = "Transaccion exitosa!";
    }
    $obBD_con1->echoJson($resp);
}


/* Inicia la insercion o actualizacion de un anticipo */
if (isset($saveManiAjax)) {
    try {
        // Verificar si es una edición (UPDATE) o un nuevo registro (INSERT)
        $esEdicion = isset($_POST['Ama_Cod']) && !empty($_POST['Ama_Cod']);
        $valNumDoc = $obBD_con1->getRowConsulta(34, array('Ama_Doc' => $_POST['Ama_Doc'], 'Bak_Cod' => $_POST['Bak_Cod']), $obBD_conexion);
        if ($valNumDoc['total'] > 0) {
            $resp['success'] = false;
            $resp['message'] = 'El número de documento ya existe';
            $obBD_con1->echoJson($resp);
            exit();
        }

        // Recoger datos POST necesarios
        $valores = array(
            'Ban_Cod' => $_POST['Ban_Cod'],
            'Bak_Cod' => $_POST['Bak_Cod'],
            'Cli_Cod' => $_POST['Cli_Cod'],
            'Pla_Cod' => $_POST['Pla_Cod'],
            'Ama_Val' => $_POST['Ama_Val'],
            'Pag_Cod' => $_POST['Pag_Cod'],
            //'Ama_Tde' => $_POST['Ama_Tde'],
            'Ama_Doc' => $_POST['Ama_Doc'],
            'Ama_Fec' => $_POST['Ama_Fec'],
            'Ama_Img' => isset($_POST['Ama_Img']) ? $_POST['Ama_Img'] : '',
            'Ama_Obs' => isset($_POST['Ama_Obs']) ? $_POST['Ama_Obs'] : ''
        );

        $resp['success'] = false;

        if ($esEdicion) {
            // Es una actualización (UPDATE)
            $valores['Ama_Cod'] = $_POST['Ama_Cod'];
            $valores['Usu_Cod'] = $Ses_Usu_Cod; // Mantener el usuario original o actualizarlo según necesidad
            $operacion = $obBD_con1->operacionobBD(14, $valores, $obBD_conexion);

            if ($obBD_con1->Error == 0) {
                $resp['success'] = true;
                $resp['message'] = 'Anticipo actualizado correctamente';
            }
        } else {
            $valores['Usu_Cod'] = $Ses_Usu_Cod;
            $valores['Cli_Cod'] = $_POST['Cli_Cod'];
            $valores['Prs_Cod'] = $_POST['Prs_Cod'];
            $valores['Prs_Ced'] = $_POST['Prs_Ced'];
            $valores['Pla_Cod'] = $_POST['Pla_Cod'];
            $valores['nombre'] = $_POST['nombre'];
            $operacion = $obBD_con1->operacionobBD(10, $valores, $obBD_conexion);
            if ($obBD_con1->Error == 0) {
                $resp['success'] = true;
                $resp['message'] = 'Anticipo registrado correctamente';
            }
        }

        // Notificación WhatsApp a usuarios de la empresa con Usu_Ntf = 'S' (caso SQL 35)
        if (!empty($resp['success'])) {
            $usuarios_notif = $obBD_con1->getArrayConsulta(35, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
            if (is_array($usuarios_notif) && count($usuarios_notif) > 0) {
                $obBD_con1->utf8_change_param($usuarios_notif);
                $numeros_whatsapp = array();
                foreach ($usuarios_notif as $adm) {
                    $tel = isset($adm['Telefono']) ? trim((string) $adm['Telefono']) : '';
                    $tel = relavera_whatsapp_normalizar_numero_ec($tel);
                    if ($tel === '') {
                        continue;
                    }
                    $numeros_whatsapp[$tel] = $tel;
                }
                if (count($numeros_whatsapp) > 0) {
                    $nombre_pla = isset($_POST['Pla_Nom']) ? trim($_POST['Pla_Nom']) : '';
                    $ama_val = isset($_POST['Ama_Val']) ? trim($_POST['Ama_Val']) : '';
                    $icono = html_entity_decode('&#128227;', ENT_QUOTES, 'UTF-8');
                    $mensaje_notif = $icono . ' *Relavera* — Anticipo registrado.\n';
                    $mensaje_notif .= 'Cliente: *' . $nombre_pla . "*\n";
                    $mensaje_notif .= 'Valor: *' . $ama_val . '*';
                    $nums = array_values($numeros_whatsapp);
                    $ama_img_post = isset($_POST['Ama_Img']) ? trim((string) $_POST['Ama_Img']) : '';
                    $img_whatsapp = relavera_man_ant_resolver_ama_img_whatsapp($ama_img_post);
                    if ($img_whatsapp !== null && $img_whatsapp !== '') {
                        /* Un solo bloque de texto: va en la leyenda de la imagen (no duplicar con messages/chat). */
                        $capImg = relavera_man_ant_caption_imagen_whatsapp($mensaje_notif);
                        enviarMensajeWhatsappImagenLista($nums, $img_whatsapp, $capImg);
                    } else {
                        enviarMensajeWhatsappLista($mensaje_notif, $nums);
                    }
                }
            }
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = 'Excepción al registrar: ' . $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
    exit();
}

/**
 * Resuelve Ama_Img (ruta relativa en FRONT, URL http(s), o data URI) para UltraMsg (base64 o URL).
 *
 * @return string|null Base64 sin prefijo, URL pública, o null
 */

function relavera_man_ant_resolver_ama_img_whatsapp($ruta)
{
    $ruta = trim((string) $ruta);
    if ($ruta === '') {
        return null;
    }
    if (preg_match('#^data:image/[^;]+;base64,(.+)$#is', $ruta, $m)) {
        return $m[1];
    }
    if (preg_match('#^https?://#i', $ruta)) {
        return $ruta;
    }
    $rel = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $ruta);
    $full = __DIR__ . DIRECTORY_SEPARATOR . $rel;
    if (!is_file($full) || !is_readable($full)) {
        return null;
    }
    $realBase = realpath(__DIR__);
    $realFile = realpath($full);
    if ($realBase === false || $realFile === false) {
        return null;
    }
    $realBase = rtrim($realBase, DIRECTORY_SEPARATOR);
    if ($realFile !== $realBase && strpos($realFile, $realBase . DIRECTORY_SEPARATOR) !== 0) {
        return null;
    }
    $size = @filesize($full);
    if ($size === false || $size > 16 * 1024 * 1024) {
        return null;
    }
    $mime = '';
    if (class_exists('finfo')) {
        $f = new finfo(FILEINFO_MIME_TYPE);
        $mime = $f->file($full);
    }
    $allowed = array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp');
    if ($mime === '' || !in_array($mime, $allowed, true)) {
        $info = @getimagesize($full);
        if ($info === false || empty($info['mime']) || !in_array($info['mime'], $allowed, true)) {
            return null;
        }
    }
    $data = @file_get_contents($full);
    if ($data === false || $data === '') {
        return null;
    }
    return base64_encode($data);
}

/** Leyenda para messages/image (máx. 1024 en UltraMsg). */
function relavera_man_ant_caption_imagen_whatsapp($texto)
{
    $texto = (string) $texto;
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($texto, 'UTF-8') > 1024) {
            return mb_substr($texto, 0, 1021, 'UTF-8') . '...';
        }
    } elseif (strlen($texto) > 1024) {
        return substr($texto, 0, 1021) . '...';
    }
    return $texto !== '' ? $texto : '.';
}

/** Envía imagen (base64 o URL) a varios números — delega en relavera_enviar_whatsapp_imagen_notif. */
function enviarMensajeWhatsappImagenLista(array $numeros, $imagePayload, $caption)
{
    $caption = relavera_man_ant_caption_imagen_whatsapp($caption);
    if ($caption === '') {
        $caption = '.';
    }
    $resultados = array();
    foreach ($numeros as $numero) {
        $numero = trim((string) $numero);
        if ($numero === '' || $imagePayload === '') {
            continue;
        }
        $resultados[$numero] = relavera_enviar_whatsapp_imagen_notif($numero, $imagePayload, $caption);
    }
    return count($resultados) === 0 ? true : !in_array(false, $resultados, true);
}

/** Envía el mismo mensaje a varios números — delega en relavera_enviar_whatsapp_notif. */
function enviarMensajeWhatsappLista($mensaje, array $numeros)
{
    $resultados = array();
    foreach ($numeros as $numero) {
        $numero = trim((string) $numero);
        if ($numero === '') {
            continue;
        }
        $resultados[$numero] = relavera_enviar_whatsapp_notif($numero, $mensaje);
    }
    return count($resultados) === 0 ? true : !in_array(false, $resultados, true);
}

// Metodo universal: dos destinos (compatibilidad)
function enviarMensajeWhatsapp($mensaje, $tel_admin, $tel_planta)
{
    $numeros = array();
    if (!empty($tel_admin)) {
        $numeros[] = $tel_admin;
    }
    if (!empty($tel_planta) && $tel_planta != $tel_admin) {
        $numeros[] = $tel_planta;
    }
    return enviarMensajeWhatsappLista($mensaje, $numeros);
}




/* Carga de datos para el grid principal */
if (isset($LoadManifAjax)) {

    $parms = array(
        'Fec_IniM' => $_GET['Fec_IniM'],
        'Fec_FinM' => $_GET['Fec_FinM'],
        'filtro' => $_GET['op_opciones'],
        'estado' => $_GET['op_opciones2'],
        'search' => $_GET['search'],
        'Cli_Cod' => $_GET['Cli_Cod'],
        'Pla_Cod' => isset($cliente_manifiesto['Pla_Cod']) ? $cliente_manifiesto['Pla_Cod'] : '',
        'filtroAnt' => isset($_GET['filtroAnt']) ? $_GET['filtroAnt'] : ''
    );
    $resp = $obBD_con1->getArrayConsulta(11, $parms, $obBD_conexion);

    // Calcular Saldo para cada registro (Ama_Val - Abono)
    if (isset($resp['rows']) && is_array($resp['rows'])) {
        foreach ($resp['rows'] as &$row) {
            // Asegurar que Abono sea numérico
            $abono = isset($row['Abono']) ? floatval($row['Abono']) : 0;
            $ama_val = isset($row['Ama_Val']) ? floatval($row['Ama_Val']) : 0;
            
            // Asegurar que Abono esté presente y sea numérico
            if (!isset($row['Abono']) || $row['Abono'] === null || $row['Abono'] === '') {
                $row['Abono'] = 0;
            } else {
                $row['Abono'] = floatval($row['Abono']);
            }
            
            // Calcular Saldo
            $saldo = $ama_val - $abono;
            $row['Saldo'] = floatval($saldo); // Enviar como número, el grid lo formateará con currency
            
            // Asegurar que Ama_Val esté en formato numérico para el grid
            if (isset($row['Ama_Val'])) {
                $row['Ama_Val'] = floatval($row['Ama_Val']);
            } else {
                $row['Ama_Val'] = 0;
            }
        }
        unset($row); // Liberar referencia
    }

    $obBD_con1->echoJson($resp);
    exit();
}

/* cancelar el registro del anticipo */
if (isset($cancelAntiAjax)) {
    $resp = array();
    $resp['success'] = false;
    try {
        $valores = array('Ama_Cod' => $_POST['Ama_Cod']);
        $obBD_con1->operacionobBD(12, $valores, $obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
            $resp['message'] = 'Anticipo anulado correctamente';
        } else {
            $resp['success'] = false;
            $resp['message'] = 'Error al anular: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = 'Excepción al anular: ' . $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
    exit();
}

/* rechozo completo del anticipo */
if (isset($rechazoAjax)) {
    $resp = array();
    $resp['success'] = false;
    try {
        $valores = array('Ama_Cod' => $_POST['Ama_Cod']);
        $obBD_con1->operacionobBD(15, $valores, $obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
            $resp['message'] = 'Anticipo rechazado correctamente';
        } else {
            $resp['success'] = false;
            $resp['message'] = 'Error al rechazar: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = 'Excepción al rechazar: ' . $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
    exit();
}

/* Obtener datos completos de un anticipo para editar */
// Validar si Ama_Doc ya existe
if (isset($validarAmaDocAjax)) {
    $resp = array('success' => false, 'existe' => false, 'message' => '');

    $ama_doc_trim = isset($_POST['Ama_Doc']) ? trim($_POST['Ama_Doc']) : '';
    if (empty($ama_doc_trim)) {
        $resp['message'] = 'No se proporcionó el número de documento';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $ama_cod_trim = isset($_POST['Ama_Cod']) ? trim($_POST['Ama_Cod']) : '';
    $valores = array(
        'Ama_Doc' => $ama_doc_trim,
        'Ama_Cod' => $ama_cod_trim,
        'Bak_Cod' => $_POST['Bak_Cod'],
        'Pag_Cod' => isset($_POST['Pag_Cod']) ? $_POST['Pag_Cod'] : ''
    );

    $data = $obBD_con1->getRowConsulta(34, $valores, $obBD_conexion);

    if ($obBD_con1->Error == 0) {
        $resp['success'] = true;
        $resp['existe'] = (isset($data['total']) && $data['total'] > 0);
        $resp['message'] = $resp['existe'] ? 'El número de documento ya existe' : 'Número de documento válido';
    } else {
        $resp['message'] = 'Error al validar: ' . $obBD_con1->MsgError;
    }

    $obBD_con1->echoJson($resp);
    exit();
}

// Subir voucher a Google Drive (o sistema de almacenamiento)
if (isset($uploadVoucherAjax)) {
    $resp = array('success' => false, 'message' => '', 'url' => '');

    if (!isset($_FILES['voucher_file']) || $_FILES['voucher_file']['error'] !== UPLOAD_ERR_OK) {
        $resp['message'] = 'Error al subir el archivo';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $file = $_FILES['voucher_file'];

    // Validar que sea una imagen
    $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
    if (!in_array($file['type'], $allowed_types)) {
        $resp['message'] = 'El archivo debe ser una imagen (JPG, PNG o GIF)';
        $obBD_con1->echoJson($resp);
        exit();
    }

    // Validar tamaño permitido para subir(máximo 6MB)
    $max_upload_size = 6 * 1024 * 1024; // 6MB en bytes
    if ($file['size'] > $max_upload_size) {
        $resp['message'] = 'El archivo es demasiado grande. Máximo 6MB';
        $obBD_con1->echoJson($resp);
        exit();
    }

    // Leer el archivo y convertir a base64 para guardarlo en la base de datos
    // $fileContent = file_get_contents($file['tmp_name']);
    // if ($fileContent === false) {
    //     $resp['message'] = 'Error al leer el archivo';
    //     $obBD_con1->echoJson($resp);
    //     exit();
    // }

    // // Convertir a base64 con prefijo data URI para facilitar su uso
    // $base64 = base64_encode($fileContent);
    // $mimeType = $file['type'];
    // $dataUri = 'data:' . $mimeType . ';base64,' . $base64;

    // // Retornar el data URI para que se guarde en el campo Ama_Img
    // $resp['success'] = true;
    // $resp['url'] = $dataUri; // Esto se guardará en Ama_Img
    // $resp['message'] = 'Imagen cargada correctamente. Tamaño: ' . round($file['size'] / 1024, 2) . ' KB';

    $emp_cod = $_SESSION['Ses_Emp_Cod'];
    $target_dir = $emp_cod . "/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
        chmod($target_dir, 0777);
    }

    // Generar nombre de archivo
    $cli_cod_file = isset($_POST['Cli_Cod']) && !empty($_POST['Cli_Cod']) ? $_POST['Cli_Cod'] : '0';
    $pla_cod_file = isset($_POST['Pla_Cod']) && !empty($_POST['Pla_Cod']) ? $_POST['Pla_Cod'] : '0';
    $fecha_hora = date("Ymd_His");
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = "voucher_" . $pla_cod_file . "_" . $cli_cod_file . "_" . $fecha_hora . "." . $extension;
    $target_file = $target_dir . $filename;

    $tmp_file = $file['tmp_name'];
    $success_save = false;

    // Lógica de Compresión si pesa más de 3MB
    if ($file['size'] > 3 * 1024 * 1024 && ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png')) {
        $image = null;
        if ($extension == 'jpg' || $extension == 'jpeg') {
            $image = @imagecreatefromjpeg($tmp_file);
        } elseif ($extension == 'png') {
            $image = @imagecreatefrompng($tmp_file);
        }

        if ($image) {
            // Guardar con compresión (Calidad 75 para JPEG, Compresión 8 para PNG)
            if ($extension == 'jpg' || $extension == 'jpeg') {
                $success_save = imagejpeg($image, $target_file, 75);
            } else {
                // Para PNG, habilitar transparencia y comprimir
                imagealphablending($image, false);
                imagesavealpha($image, true);
                $success_save = imagepng($image, $target_file, 8);
            }
            imagedestroy($image);
            $msg_ok = 'Imagen comprimida y guardada correctamente.';
        } else {
            // Si falla la librería GD, intentar moverlo normalmente
            $success_save = move_uploaded_file($tmp_file, $target_file);
            $msg_ok = 'Imagen guardada (sin compresión por error de librería).';
        }
    } else {
        // Si no necesita compresión, mover directamente
        $success_save = move_uploaded_file($tmp_file, $target_file);
        $msg_ok = 'Imagen guardada correctamente.';
    }

    if ($success_save) {
        $resp['success'] = true;
        $resp['url'] = $target_file; 
        $resp['message'] = $msg_ok;
    } else {
        $resp['message'] = 'Error al guardar el archivo en el servidor.';
    }

    $obBD_con1->echoJson($resp);
    exit();
}

if (isset($getAnticipoAjax)) {
    $resp = array();
    $resp['success'] = false;

    if (!isset($_GET['Ama_Cod']) || empty($_GET['Ama_Cod'])) {
        $resp['message'] = 'No se proporcionó el código del anticipo';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $valores = array('Ama_Cod' => $_GET['Ama_Cod']);
    $data = $obBD_con1->getRowConsulta(13, $valores, $obBD_conexion);

    if ($obBD_con1->Error == 0 && $data) {
        $resp['success'] = true;
        $resp['data'] = $data;
        $resp['message'] = 'Datos cargados correctamente';
    } else {
        $resp['message'] = 'No se encontraron datos del anticipo: ' . $obBD_con1->MsgError;
    }

    $obBD_con1->echoJson($resp);
    exit();
}











/* Registrar el comprobante del anticipo */
if (isset($saveComprobanteAjax)) {
    $resp = array();
    $resp['success'] = false;
    $resp['message'] = '';

    try {
        // Validar que se reciban los datos necesarios
        if (!isset($_POST['Ama_Cod']) || empty($_POST['Ama_Cod'])) {
            $resp['message'] = 'No se proporcionó el código del anticipo';
            $obBD_con1->echoJson($resp);
            exit();
        }

        // Obtener los datos del anticipo
        $valores_anticipo = array('Ama_Cod' => $_POST['Ama_Cod']);
        $anticipo = $obBD_con1->getRowConsulta(13, $valores_anticipo, $obBD_conexion);

        if (!$anticipo || $obBD_con1->Error != 0) {
            $resp['message'] = 'No se encontró el anticipo: ' . ($obBD_con1->MsgError ? $obBD_con1->MsgError : 'Error desconocido');
            $obBD_con1->echoJson($resp);
            exit();
        }

        // Obtener el tipo de asiento INGRESO (Tia_Abr='IN' y Tia_Est='A')
        $tipo_asiento = $obBD_con1->getRowConsulta(31, "", $obBD_conexion);
        if (!$tipo_asiento || $obBD_con1->Error != 0 || empty($tipo_asiento['Tia_Cod'])) {
            $resp['message'] = 'No se encontró el tipo de asiento INGRESO (Tia_Abr=\'IN\' y Tia_Est=\'A\')';
            $obBD_con1->echoJson($resp);
            exit();
        }
        $tia_cod = $tipo_asiento['Tia_Cod'];

        // Obtener el periodo contable por fecha del anticipo
        $params_periodo = array('Com_Fec' => $anticipo['Ama_Fec'], 'Emp_Cod' => $_SESSION['Ses_Emp_Cod']);
        $periodo = $obBD_con1->getRowConsulta(17, $params_periodo, $obBD_conexion);

        if (!$periodo || $obBD_con1->Error != 0) {
            $resp['message'] = 'No se encontró un periodo contable activo para la fecha del anticipo: ' . ($obBD_con1->MsgError ? $obBD_con1->MsgError : 'Error desconocido');
            $obBD_con1->echoJson($resp);
            exit();
        }

        // Obtener el siguiente número de comprobante
        $params_num = array('Tia_Cod' => $tia_cod, 'Pec_Cod' => $periodo['Pec_Cod'], 'Com_Fec' => $anticipo['Ama_Fec']);
        $siguiente_num = $obBD_con1->getRowConsulta(18, $params_num, $obBD_conexion);        

        if (!$siguiente_num || $obBD_con1->Error != 0) {
            $resp['message'] = 'Error al obtener el siguiente número de comprobante: ' . ($obBD_con1->MsgError ? $obBD_con1->MsgError : 'Error desconocido');
            $obBD_con1->echoJson($resp);
            exit();
        }

        // Preparar los parámetros para insertar el comprobante
        $params = array(
            'Pec_Cod' => $periodo['Pec_Cod'],
            'Cli_Cod' => $anticipo['Cli_Cod'],
            'Usu_Cod' => $Ses_Usu_Cod,
            'Com_Num' => $siguiente_num['Com_Num'],
            'Com_Fec' => $anticipo['Ama_Fec'],
            'Com_Tip' => 'I',
            'Com_Con' => 'Anticipo por Manifiesto',
            'Com_Val' => $anticipo['Ama_Val'],
            'Com_Obs' => 'Anticipo por Manifiesto',
            'Tia_Cod' => $tia_cod
        );

        // Obtener el Pld_Cod del banco (cuenta que va al DEBE)
        $pld_cod_banco = $anticipo['Pld_Cod'];
        if (empty($pld_cod_banco)) {
            $resp['message'] = 'No se encontró la cuenta contable del banco en el anticipo';
            $obBD_con1->echoJson($resp);
            exit();
        }

        // Obtener el Pld_Cod de la cuenta parametrizada ANC (cuenta que va al HABER)
        $params_anc = array('Pec_Cod' => $periodo['Pec_Cod'], 'Emp_Cod' => $_SESSION['Ses_Emp_Cod']);
        $cuenta_anc = $obBD_con1->getRowConsulta(20, $params_anc, $obBD_conexion);

        if (!$cuenta_anc || $obBD_con1->Error != 0 || empty($cuenta_anc['Pld_Cod'])) {
            $resp['message'] = 'No se encontró la cuenta parametrizada ANC (Anticipos de Clientes): ' . ($obBD_con1->MsgError ? $obBD_con1->MsgError : 'Error desconocido');
            $obBD_con1->echoJson($resp);
            exit();
        }
        $pld_cod_anc = $cuenta_anc['Pld_Cod'];

        // Insertar el comprobante
        $obBD_con1->operacionobBD(16, $params, $obBD_conexion);

        if ($obBD_con1->Error == 0) {
            // Obtener el Com_Cod del comprobante recién insertado
            $com_cod = $obBD_con1->insercionid($obBD_conexion);

            if (empty($com_cod)) {
                $resp['message'] = 'Error al obtener el código del comprobante generado';
                $obBD_con1->echoJson($resp);
                exit();
            }

            // Insertar el primer asiento (DEBE) - Cuenta del banco
            $params_asiento_debe = array(
                'Com_Cod' => $com_cod,
                'Asi_Deh' => 'D',
                'Asi_Val' => $anticipo['Ama_Val'],
                'Pld_Cod' => $pld_cod_banco,
                'Asi_Con' => 'Anticipo por Manifiesto',
                'Asi_Glo' => 'Anticipo por Manifiesto'
            );
            $obBD_con1->operacionobBD(21, $params_asiento_debe, $obBD_conexion);
            // Capturar Asi_Cod generado para este asiento (DEBE)
            $asi_debe_cod = $obBD_con1->insercionid($obBD_conexion);

            if ($obBD_con1->Error != 0) {
                $resp['message'] = 'Error al insertar el asiento de DEBE: ' . ($obBD_con1->MsgError ? $obBD_con1->MsgError : 'Error desconocido');
                $obBD_con1->echoJson($resp);
                exit();
            }

            // Insertar el segundo asiento (HABER) - Cuenta ANC
            $params_asiento_haber = array(
                'Com_Cod' => $com_cod,
                'Asi_Deh' => 'H',
                'Asi_Val' => $anticipo['Ama_Val'],
                'Pld_Cod' => $pld_cod_anc,
                'Asi_Con' => 'Anticipo por Manifiesto',
                'Asi_Glo' => 'Anticipo por Manifiesto'
            );
            $obBD_con1->operacionobBD(21, $params_asiento_haber, $obBD_conexion);

            // Capturar Asi_Cod del asiento HABER si es necesario
            $asi_haber_cod = $obBD_con1->insercionid($obBD_conexion);

            if ($obBD_con1->Error != 0) {
                $resp['message'] = 'Error al insertar el asiento de HABER: ' . ($obBD_con1->MsgError ? $obBD_con1->MsgError : 'Error desconocido');
                $obBD_con1->echoJson($resp);
                exit();
            }

            // Actualizar el estado del anticipo a Acreditado
            $params_update = array('Ama_Cod' => $_POST['Ama_Cod']);
            $obBD_con1->operacionobBD(19, $params_update, $obBD_conexion);

            if ($obBD_con1->Error == 0) {
                // Obtener el siguiente número de documento para el cliente
                $params_sig_doc = array('Cli_Cod' => $anticipo['Cli_Cod']);
                $siguiente_doc = $obBD_con1->getRowConsulta(29, $params_sig_doc, $obBD_conexion);

                if (!$siguiente_doc || $obBD_con1->Error != 0) {
                    $resp['message'] = 'Comprobante y asientos registrados pero error al obtener siguiente número de documento: ' . ($obBD_con1->MsgError ? $obBD_con1->MsgError : 'Error desconocido');
                    $obBD_con1->echoJson($resp);
                    exit();
                }

                $ant_doc = $siguiente_doc['siguiente_doc'];

                // Insertar en la tabla anticipos_clientes
                $fecha_actual = date("Y-m-d");
                $params_anticipo_cliente = array(
                    'Ant_Fec' => $fecha_actual,
                    'Ant_Val' => $anticipo['Ama_Val'],
                    'Ant_Doc' => $ant_doc,
                    'Ant_Obs' => isset($anticipo['Ama_Obs']) ? $anticipo['Ama_Obs'] : '',
                    'Cli_Cod' => $anticipo['Cli_Cod'],
                    'Com_Cod' => $com_cod,
                    'Ama_Cod' => $_POST['Ama_Cod']
                );

                $obBD_con1->operacionobBD(30, $params_anticipo_cliente, $obBD_conexion);

                if ($obBD_con1->Error == 0) {
                    // Capturar el Ant_Cod generado (id en anticipos_clientes)
                    $ant_cod_generado = $obBD_con1->insercionid($obBD_conexion);

                    // Ahora insertar en pag_anticipo_cli (caso 32)
                    // Obtener datos del banco para tomar Ban_Cue (Pac_Ctd)
                    $ban = null;
                    if (!empty($anticipo['Ban_Cod'])) {
                        $ban = $obBD_con1->getRowConsulta('banco.selectWhere', array('where' => array('banco.Ban_Cod' => $anticipo['Ban_Cod'])), $obBD_conexion);
                    }

                    // Asegurar que se envíen valores en el orden esperado y con valores por defecto
                    $params_pag_anticipo = array(
                        'Pac_Cto' => $pld_cod_banco,
                        'Pac_Ctd' => ($ban && isset($ban['Ban_Cue'])) ? $ban['Ban_Cue'] : '',
                        'Pac_Val' => $anticipo['Ama_Val'],
                        'Ant_Cod' => $ant_cod_generado,
                        // Che_Cod debe ser NULL en la base; pasar NULL explícito
                        'Che_Cod' => null,
                        'Pac_Obs' => isset($anticipo['Ama_Obs']) ? $anticipo['Ama_Obs'] : '',
                        'Pac_Num' => isset($anticipo['Ama_Doc']) ? $anticipo['Ama_Doc'] : '',
                        'Pag_Cod' => isset($anticipo['Pag_Cod']) ? $anticipo['Pag_Cod'] : '',
                        // Asi_Cod: asegurarnos de enviar un entero (0 si no se obtuvo)
                        'Asi_Cod' => (!empty($asi_debe_cod) ? intval($asi_debe_cod) : 0)
                    );

                    $obBD_con1->operacionobBD(32, $params_pag_anticipo, $obBD_conexion);

                    if ($obBD_con1->Error == 0) {


                        // WhatsApp al administrador de la planta: pago aprobado (valor + nombre planta)
                        if (!empty($anticipo['Pla_Cod'])) {
                            $fila_pla = $obBD_con1->getRowConsulta(36, array('Pla_Cod' => $anticipo['Pla_Cod']), $obBD_conexion);
                            if ($fila_pla && $obBD_con1->Error == 0) {
                                $obBD_con1->utf8_change_param($fila_pla);
                                $tel_planta = relavera_whatsapp_normalizar_numero_ec(relavera_telefono_planta_fila($fila_pla));
                                if ($tel_planta !== '') {
                                    $pla_nom = '';
                                    if (isset($anticipo['Pla_Nom']) && trim((string) $anticipo['Pla_Nom']) !== '') {
                                        $pla_nom = trim($anticipo['Pla_Nom']);
                                    } elseif (isset($fila_pla['Pla_Nom']) && trim((string) $fila_pla['Pla_Nom']) !== '') {
                                        $pla_nom = trim($fila_pla['Pla_Nom']);
                                    }
                                    $val = isset($anticipo['Ama_Val']) ? $anticipo['Ama_Val'] : '';
                                    $val_txt = is_numeric($val) ? number_format((float) $val, 2, '.', ',') : (string) $val;
                                    $icono_ok = html_entity_decode('&#9989;', ENT_QUOTES, 'UTF-8');
                                    $msg_pla = $icono_ok . ' *Relavera*\n';
                                    $msg_pla .= 'Su anticipo ha sido *aprobado* y acreditado.\n';
                                    $msg_pla .= 'Planta: *' . $pla_nom . "*\n";
                                    $msg_pla .= 'Valor: *' . $val_txt . '*';
                                    enviarMensajeWhatsappLista($msg_pla, array($tel_planta));
                                }
                            }
                        }

                        
                        












                        $resp['success'] = true;
                        $resp['message'] = 'Datos registrados correctamente!!.';
                        $resp['Com_Cod'] = $com_cod;
                        $resp['Ant_Cod'] = $ant_cod_generado;
                    } else {
                        $resp['message'] = 'Error al registrar en pag_anticipo_cli: ' . ($obBD_con1->MsgError ? $obBD_con1->MsgError : 'Error desconocido');
                    }

                } else {
                    $resp['message'] = 'Error de registro:  ' . ($obBD_con1->MsgError ? $obBD_con1->MsgError : 'Error desconocido');
                }
            } else {
                $resp['message'] = 'Error de actualizacion: ' . ($obBD_con1->MsgError ? $obBD_con1->MsgError : 'Error desconocido');
            }
        } else {
            $resp['message'] = 'Error al registrar el comprobante: ' . ($obBD_con1->MsgError ? $obBD_con1->MsgError : 'Error desconocido');
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = 'Excepción al registrar el comprobante: ' . $e->getMessage();
    }

    $obBD_con1->echoJson($resp);
    exit();
}











/* Registro en la tabla de anticipos */
// if(isset($saveAnticipoAjax)){

// }

/* nuevos reportes */
if (!empty($PrintComprAjax)) {
    $params = array('Codigo' => $Com_Cod, 'Tia_Des' => $Tia_Asi['Tia_Des'], 'Com_Con' => $Vet_Obs, 'Com_Fec' => $Caj_Fec, 'Com_Val' => $t_rubros);
    $response['Com_Rows'] = $obBD_con1->getArrayConsulta(23, $Com_Cod, $obBD_conexion);
    $response['Com_Link'] = "" .  (!empty($reportes[2]) ? $reportes[2] . "?codigo=" : baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php") . "?codigo=") . $Com_Cod;
}

if (isset($cargarReportes)) {
    try {
        $response['reportes'] = $obBD_con2->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        $response['success'] = true;
    } catch (Exception $ex) {
        $response['message'] = $ex->getMessage();
    }
    $obBD_con2->echoJson($response);
}

// Endpoint para buscar clientes/plantas con vouchers en un rango de fechas
if (isset($buscarClientesVouchersAjax)) {
    $resp = array('success' => false, 'data' => array());
    try {
        $Fec_Des = $_GET['Fec_Des'];
        $Fec_Has = $_GET['Fec_Has'];

        $sql = "SELECT 
                    ma.Cli_Cod, 
                    ma.Pla_Cod, 
                    CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Cliente, 
                    pl.Pla_Nom, 
                    COUNT(*) as Cant
                FROM manifiesto_anticipo ma
                INNER JOIN cliente c ON ma.Cli_Cod = c.Cli_Cod
                INNER JOIN persona p ON c.Prs_Cod = p.Prs_Cod
                LEFT JOIN manifiesto_plantas pl ON ma.Pla_Cod = pl.Pla_Cod
                INNER JOIN tipos_pago tp ON ma.Pag_Cod = tp.Pag_Cod
                WHERE ma.Ama_Est = 'A' 
                    AND ma.Ama_Img IS NOT NULL 
                    AND ma.Ama_Img != ''
                    AND ma.Ama_Img != 'NULL'
                    AND ma.Ama_Fec BETWEEN '$Fec_Des' AND '$Fec_Has'
                    AND c.Emp_Cod = $Ses_Emp_Cod
                    AND (tp.Pag_Abr = 'TRF' OR tp.Pag_Abr = 'DEP')
                GROUP BY ma.Cli_Cod, ma.Pla_Cod, Cliente, pl.Pla_Nom
                ORDER BY Cliente, pl.Pla_Nom";

        $res = $obBD_con1->consulta($sql, $obBD_conexion->conexion);
        while ($row = $obBD_con1->fetch_assoc($res)) {
            $resp['data'][] = $row;
        }
        $resp['success'] = true;
    } catch (Exception $e) {
        $resp['message'] = $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
    exit();
}

/* Periodos */
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
utf8_encode_deep($periodos);

/* Perfil */
$perfil = $obBD_con1->getArrayConsulta('perfiles.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Usu_Cod' => $Ses_Usu_Cod), 'setWhere' => array('getPerfil')), $obBD_conexion);

?>

<!DOCTYPE html>
<HTML>

<HEAD>
    <TITLE><?php echo "Manifiesto Anticipo"; ?></TITLE>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script>
        var cliente_manifiesto = <?php echo json_encode($cliente_manifiesto); ?>;
        var prf = <?php echo json_encode($perfil) ?>;
        var hoy = <?php echo json_encode($hoy); ?>;
        var Ses_Emp_Nom = <?php echo json_encode($_SESSION['Ses_Emp_Nom']); ?>;
    </script>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo;Manifiesto de Anticipos</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <!-- AMBIENTE PRINCIPAL -->
            <div id="documentoSearch">
                <div class="row">
                    <form name="searchManifesto" id="searchManifesto" class="form-horizontal normal" action="javascript:$('#man_antGrid').Search('#searchManifesto','LoadManifAjax');">
                        <div class="col-xs-5">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&uacute;squeda</legend>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                    <div class="col-xs-10 radioset opt_search">
                                        <input id="radsf1" name="op_opciones" type="radio" value="cl" checked="" onclick="setfocus(this.form.search)" alt="" />
                                        <label for="radsf1">Cliente</label>
                                        <input id="radsf2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
                                        <label for="radsf2">C&eacute;dula/RUC</label>
                                        <input id="radsf3" name="op_opciones" type="radio" value="m" onclick="setfocus(this.form.search)" alt="" />
                                        <label for="radsf3">Manifiesto</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                    <div class="col-xs-8">
                                        <div class="input-group">
                                            <input name="search" onkeydown="if (event.keyCode === 13)
                                                    this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
                                            <input name="Cli_Cod" id="Cli_Cod" type="hidden" value="<?php echo isset($cliente_manifiesto['Cli_Cod']) ? $cliente_manifiesto['Cli_Cod'] : ''; ?>" />
                                            <input name="filtroAnt" id="filtroAnt" type="hidden" value="" />
                                            <span class="input-group-btn">
                                                <button type="button" id="btnSearch" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Documento" tabindex="-1">
                                                    <span class="glyphicon glyphicon-search"></span>
                                                    <span>Buscar</span>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                    <input type="text" tabindex="-1" style="display:none;" />
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-sm-7">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtros</legend>
                                <div class="form-group">
                                    <label class="col-xs-1 control-label label-xs" style="margin-left: 25px;">Estado:</label>
                                    <div class="col-sm-4 radioset opt_search">
                                        <!-- <input id="radsc1" name="op_opciones2" type="radio" value="T" /><label for="radsc1">Todos&nbsp;</label> -->
                                        <input id="radsc2" name="op_opciones2" type="radio" value="A" alt="" checked/><label for="radsc2">Activos</label>
                                        <input id="radsc3" name="op_opciones2" type="radio" value="I" alt="" /><label for="radsc3">Anulados</label>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-top: 10px; margin-left: 10px;">
                                    <label class="col-sm-1 control-label label-xs">Periodo:</label>
                                    <div class="col-sm-2" style="margin-right: 10px;">
                                        <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" style="text-align: center; width: 125px;" onchange="desbloquear();">
                                            <option value="T"> << Todos>> </option>
                                            <option value="PF">-- Por Fecha --</option>
                                            <?php
                                            // foreach ($periodos as $p) {
                                            //     echo "<option data-year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data-pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
                                            // }
                                            foreach ($periodos as $i => $p) {
                                                $selected = ($i === 0) ? 'selected' : '';
                                                echo "<option data-year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data-pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]' $selected>Periodo $p[Year]</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-4 rango-fecha" style="display:flex; align-items:center;">
                                        <div class="input-group input-group-xs por_fecha" style="width:100%; justify-content: flex-start;">
                                            <span class="input-group-addon alert-info">Desde</span>
                                            <input type="text" id="Fec_IniM" name="Fec_IniM" class="form-control" style="text-align: center; width: 120px;" disabled="disabled" />
                                            <span class="input-group-addon" title="Intercambiar fechas"><i class="glyphicon glyphicon-transfer"></i></span>
                                            <span class="input-group-addon alert-info">Hasta</span>
                                            <input type="text" id="Fec_FinM" name="Fec_FinM" class="form-control" style="text-align: center; width: 120px; margin-right: 10px;" disabled="disabled" />
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <button type="button" id="btnNuevoAnticipo" class="btn btn-success btn-xs pull-right" title="Nuevo Anticipo" style="margin-top:10px;" onclick="abrirModalPagos();">
                                        <span class="glyphicon glyphicon-plus"></span>
                                        Nuevo Anticipo
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Grid Principal de Manifiesto Anticipos -->
                        <div class="col-sm-12" style="min-height: 350px; padding-bottom: 1px;">
                            <table id="man_antGrid"></table>
                            <div id="man_antGridPager"></div>
                            <div class="Titulos2">
                                <span id="plan-footer">
                                    <strong>Leyenda:</strong> <span class="glyphicon glyphicon-ok green"></span> Aprobados | <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos
                                </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Inicio del diálogo para buscar Proveedores -->
        <div id="clientesDialog" title="B&uacute;squeda de Clientes">
            <form id="clienForm" class="form-horizontal normal"></form>
        </div>

        <!-- dialogo de registro de pagos de anticipo -->
        <div id="pagosDialog" title="Agregar Pagos" style="display: none;">
            <form id="pagosForm" class="form-horizontal normal">
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del Cliente</legend>
                            <div class="form-group">
                                    <!-- Hidden fields -->
                                    <input name="bandera_prov" id="bandera_prov" type="hidden" value="nosel" />
                                    <input name="Prs_Cod" id="Prs_Cod" type="hidden" />
                                    <input name="Cli_Cod" id="Cli_Cod" type="hidden" />
                                    <input name="Usu_Cod" id="Usu_Cod" type="hidden" />
                                    <input name="save_bnd" id="save_bnd" type="hidden" value="n" />
                                    <input name="Ant_Val" id="Ant_Val" type="hidden" value="0.00" />
                                    <input name="Ama_Cod" id="Ama_Cod" type="hidden" value="" />
                                    <input name="Pla_Cod" id="Pla_Cod" type="hidden" value="" />
                                <label class="col-xs-2 control-label label-sm">C&eacute;dula/RUC:</label><!-- required -->                               
                                <div class="col-xs-4">
                                    <input name="Prs_Ced" id="Prs_Ced" type="text" class="form-control input-xs always-readonly" tabindex="1" required readonly />
                                </div>                               
                                <label class="col-sm-1 control-label label-xs">Cliente:</label>
                                <div class="col-xs-5">
                                    <div class="input-group input-group-xs"><input name="nombre" id="nombre" class="form-control input-xs databind datatitle always-readonly" readonly />
                                        <span class="input-group-btn">
                                            <button type="button" id="btnBusCLi" placeholder="Ingrese nombre del Cliente..." onclick="$('#clientesDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente" tabindex="2" style="display: none;">
                                                <span class="glyphicon glyphicon-search"></span>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Planta:</label>
                                <div class="col-xs-4">
                                    <input name="Pla_Nom" id="Pla_Nom" class="form-control input-xs databind datatitle always-readonly" readonly />
                                </div>
                                <label class="col-sm-2 control-label label-xs" style="margin-left: -22px;">Licencia:</label>
                                <div class="col-xs-4">
                                    <input name="Pla_Lic" id="Pla_Lic" class="form-control input-xs databind datatitle always-readonly" style="margin-left: -25px;"readonly />
                                </div>    

                                    
                                
                            </div>
                        </fieldset>
                    </div>
                </div>

                <fieldset class="exa-fieldset" id="detPagos">
                    <legend class="Titulos2">Datos del Pago</legend>

                    <div class="form-group Transferencia Deposito hide_banco">
                        <label class="col-xs-3 control-label label-xs required">Fecha:</label>
                        <div class="col-xs-3" style="width: 150px;">
                            <input id="Ama_Fec" name="Ama_Fec" type="text" size="10" class="form-control input-xs datepicker" required="" style="text-align: center" />
                        </div>
                        <!-- </div>

                        <div class="form-group"> -->
                        <label class="col-xs-2 control-label label-xs required">Tipo:</label>
                        <div class="col-xs-3" style="width: 150px; margin-left: -12px;">
                            <!-- <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs readOnly" style="text-align: center" onchange="cambiarCamposPagos($(this).find(':selected').data().class, $('#Pag_Cod option:selected').attr('data-abr'))" -->
                            <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs readOnly" style="text-align: center" onchange="var sel=$(this).find(':selected'); if(sel.length > 0) cambiarCamposPagos(sel.data('class'), sel.attr('data-abr'));"
                                required="">
                                <?php $rows_tipo_pago = $obBD_con1->getArrayConsulta(2, "", $obBD_conexion);
                                if (count($rows_tipo_pago) > 0) {
                                    foreach ($rows_tipo_pago as $row) {
                                        echo "<option value='$row[Pag_Cod]' data-abr='$row[Pag_Abr]' data-class='$row[Pag_Des]' >$row[Pag_Des]</option>";
                                    }
                                } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Bancos de DataBase -->
                    <div class="form-group Transferencia Deposito hide_banco">
                        <label class="col-xs-3 control-label label-xs required">Acreditar a:</label>
                        <div class="col-xs-8">
                            <select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly" required="">
                            </select>
                        </div>
                    </div>

                    <div class="form-group Transferencia hide_banco">
                        <label class="col-xs-3 control-label label-xs required">Banco Origen:</label>
                        <div class="col-xs-8">
                            <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs readOnly" required="">
                                <?php $rows_bankos = $obBD_con1->getArrayConsulta(9, "", $obBD_conexion);
                                if (count($rows_bankos) > 0) {
                                    foreach ($rows_bankos as $row) {
                                        echo "<option value='$row[Bak_Cod]'>$row[Bak_Des]</option>";
                                    }
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group Deposito">
                        <label class="col-xs-3 control-label label-xs">Observaci&oacute;n:</label>
                        <div class="col-xs-8">
                            <textarea id="Ama_Obs" name="Ama_Obs" class="form-control input-xs"></textarea>
                        </div>
                    </div>

                    <div class="form-group" style="display: none;">
                        <label class="col-xs-3 control-label label-xs required">Estado</label>
                    </div>

                    <!--div class="form-group Transferencia Deposito">
                        <label class="col-xs-3 control-label label-xs">Voucher:</label>
                        <div class="col-xs-8">
                            <input type="file" id="Ama_Img_File" name="Ama_Img_File" class="form-control input-xs" accept="image/*" onchange="subirVoucher(this);">
                            <input type="hidden" id="Ama_Img" name="Ama_Img" value="">
                            <small class="help-block" id="Ama_Img_Status" style="color: #5cb85c; display: none;"></small>
                            <small class="help-block" id="Ama_Img_Link" style="display: none;">
                                <a href="javascript:void(0);" onclick="verImagenVoucher();" id="Ama_Img_Link_Href">Ver imagen</a>
                            </small>
                        </div>
                    </div-->
                                
                    <div class="form-group Transferencia Deposito">
                        <label class="col-xs-3 control-label label-xs required">Voucher:</label>
                        <div class="col-xs-8">
                            <div id="Ama_Img_Preview" style="margin-top:8px;;">
                                <img id="Ama_Img_Visual" src="" alt="Vista previa del voucher" style="max-width:180px; max-height:180px; border:1px solid #ddd; border-radius:4px;" />
                            </div>
                            <!--small class="help-block" id="Ama_Img_Status" style="color: #5cb85c; display: none;"></small-->
                            <small class="help-block" id="Ama_Img_Link" style="display: none;">
                                <a href="javascript:void(0);" onclick=" return verImagenVoucher();" id="Ama_Img_Link_Href">Ampliar imagen</a> |
                                <a href="javascript:void(0);" onclick="descargarImagenVoucher(); return false;" id="Ama_Img_Link_Descargar">Descargar imagen</a>
                            </small>
                            <input type="file" id="Ama_Img_File" name="Ama_Img_File" class="form-control input-xs" accept="image/*" onchange="subirVoucher(this);" required="">
                            <input type="hidden" id="Ama_Img" name="Ama_Img" value="">
                            <script>
                                // Mostrar la imagen automáticamente si existe código en Ama_Img al abrir el modal
                                $(document).ready(function() {
                                    function mostrarPreviewVoucher() {
                                        var imagenUrl = $('#Ama_Img').val();
                                        if (imagenUrl && imagenUrl.trim() !== '') {
                                            $('#Ama_Img_Visual').attr('src', imagenUrl);
                                            $('#Ama_Img_Preview').show();
                                        } else {
                                            $('#Ama_Img_Visual').attr('src', '');
                                            $('#Ama_Img_Preview').hide();
                                        }
                                    }
                                    // Ejecuta al abrir el formulario/modal (puedes invocar esto donde corresponda también)
                                    // Si usas un dialog de jquery ui:
                                    if ($('#pagosDialog').length) {
                                        $('#pagosDialog').on('dialogopen', function() {
                                            mostrarPreviewVoucher();
                                        });
                                    }
                                });
                                // También al cambiar el valor por subida nueva, muestra preview instantáneo
                                $('#Ama_Img').on('change', function() {
                                    var imagenUrl = $(this).val();
                                    if (imagenUrl && imagenUrl.trim() !== '') {
                                        $('#Ama_Img_Visual').attr('src', imagenUrl);
                                        $('#Ama_Img_Preview').show();
                                    } else {
                                        $('#Ama_Img_Visual').attr('src', '');
                                        $('#Ama_Img_Preview').hide();
                                    }
                                });
                            </script>
                        </div>
                    </div>



                    <div class="form-group Transferencia Deposito">
                        <label class="col-xs-3 control-label label-xs required" id="doc">No. Docum.:</label>
                        <div class="col-xs-4">
                            <div class="input-group input-group-xs">
                                <input type="text" id="Ama_Doc" name="Ama_Doc" class="form-control input-xs" onchange="/*soloNumeros($(this));*/ validarAmaDoc($(this).val());" required="">
                                <span class="input-group-addon validate"><i id="Ama_Doc_Est"></i></span>
                            </div>
                        </div>
                        <!-- </div>
                        <div class="form-group Transferencia Deposito"> -->
                        <label class="col-xs-1 control-label label-sm required" style="margin-top: -3px;">Valor:</label>
                        <div class="col-xs-3">
                            <div class="input-group input-group-xs">
                                <span class="input-group-addon">
                                    <i id="indicadorChe" class="glyphicon glyphicon-usd"></i>
                                </span>
                                <input name="Ama_Val" type="text" id="Pac_Val" size="10" class="form-control input-xs" required="" autocomplete="off" onchange="cambioValPago($(this));"
                                    onkeypress="return  validar_decimal(event)" />
                            </div>
                        </div>
                    </div>

                    <div class="form-group center Trasferencia Deposito">
                        </br>
                        <a id="btnAgregarPago" class="btn btn-sm btn-primary" onclick="AgregarPago()">
                            <i class="glyphicon glyphicon-floppy-disk"></i> Agregar
                        </a>
                    </div>
                </fieldset>
            </form>
        </div>

    </div>
    </div>
    <script type="text/javascript">
        // Variable global para indicar si el usuario tiene cliente asociado en manifiesto
        // Esta variable será utilizada por la función toggleBotonesCliente() en man_ant_1.0.js
        var tieneClienteManifiesto = <?php echo (isset($cliente_manifiesto) && !empty($cliente_manifiesto) && isset($cliente_manifiesto['Cli_Cod'])) ? 'true' : 'false'; ?>;
    </script>
    <script src="../VALIDACIONES/man_ant_1.0.js?x=26"></script>
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <!-- Modal para ver imagen del voucher -->
    <div id="imagenVoucherDialog" title="Voucher" style="display: none;">
        <div style="text-align: center; padding: 10px; position: relative;">
            <!-- Controles de zoom -->
            <div style="position: absolute; top: 15px; right: 15px; z-index: 1000; background: rgba(255,255,255,0.9); padding: 5px; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                <button id="zoomInBtn" style="width: 30px; height: 30px; font-size: 18px; font-weight: bold; border: 1px solid #ccc; background: #fff; cursor: pointer; border-radius: 3px; margin-right: 3px;" title="Acercar (+)" onclick="zoomImagenVoucher(1.2);">+</button>
                <button id="zoomOutBtn" style="width: 30px; height: 30px; font-size: 18px; font-weight: bold; border: 1px solid #ccc; background: #fff; cursor: pointer; border-radius: 3px; margin-right: 3px;" title="Alejar (-)" onclick="zoomImagenVoucher(0.8);">-</button>
                <button id="zoomResetBtn" style="width: 30px; height: 30px; font-size: 14px; border: 1px solid #ccc; background: #fff; cursor: pointer; border-radius: 3px;" title="Restablecer zoom" onclick="resetZoomImagenVoucher();">⟲</button>
            </div>
            <!-- Contenedor con scroll para la imagen -->
            <div id="imagenVoucherContainer" style="overflow: auto; max-height: 400px; max-width: 100%; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5; padding: 10px;">
                <img id="imagenVoucherContent" src="" alt="Voucher" style="display: block; margin: 0 auto; transition: transform 0.2s; transform-origin: center center; cursor: move;" draggable="false">
            </div>
            <div style="margin-top: 5px; font-size: 11px; color: #666;">
                <span id="zoomLevel">100%</span> | Usa la rueda del mouse para hacer zoom
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del anticipo -->
    <div id="detallesAnticipoDialog" title="Detalles del Anticipo" style="display: none;">
        <div id="detallesAnticipoContent" style="padding: 15px;">
            <div class="text-center" style="padding: 20px;">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <p>Cargando información...</p>
            </div>
        </div>
    </div>

    <!-- Modal para Descarga Masiva de Vouchers -->
    <div id="vouchersMasivosDialog" title="Descarga Masiva de Vouchers" style="display: none;">
        <div style="padding: 15px;">
            <div class="row">
                <div class="col-xs-12">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Rango de Fechas</legend>
                        <div class="form-horizontal">
                            <div class="form-group">
                                <label class="col-xs-2 control-label">Desde:</label>
                                <div class="col-xs-3">
                                    <input type="date" id="Vou_Fec_Ini" class="form-control input-xs" value="<?php echo date('Y-m-01'); ?>">
                                </div>
                                <label class="col-xs-2 control-label">Hasta:</label>
                                <div class="col-xs-3">
                                    <input type="date" id="Vou_Fec_Fin" class="form-control input-xs" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-xs-2">
                                    <button type="button" class="btn btn-primary btn-xs" onclick="buscarClientesVouchers()">
                                        <i class="glyphicon glyphicon-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>
            <div class="row" style="margin-top: 10px;">
                <div class="col-xs-12">
                    <div id="vouchersListContainer" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                        <table class="table table-condensed table-hover" id="vouchersTable">
                            <thead>
                                <tr>
                                    <th width="30"><input type="checkbox" id="checkAllVouchers" onclick="toggleAllVouchers(this)"></th>
                                    <th>Cliente</th>
                                    <th>Planta</th>
                                    <th class="text-center">Cant. Vouchers</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center">Seleccione un rango y busque...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pager info -->
                    <div style="margin-top: 5px; font-size: 11px; color: #666; font-weight: bold;">
                        <span>Registros Visualizados: <span id="lblVouchersRegistros">0</span></span> | 
                        <span>Total de Vouchers: <span id="lblVouchersTotal">0</span></span>
                    </div>
                </div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-xs-12 text-right">
                    <button type="button" class="btn btn-success" id="btnGenerarDescargaVouchers" onclick="generarDescargaMasiva()" disabled="disabled">
                        <i class="glyphicon glyphicon-download-alt"></i> Generar Descarga (.ZIP)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Cerrado y liberacion de las conexiones
    $obBD_con1->liberar();
    $obBD_conexion->cerrar();
    ?>
</BODY>

</HTML>