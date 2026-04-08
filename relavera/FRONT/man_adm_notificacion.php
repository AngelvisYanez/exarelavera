<?php

/**
 * Envío de mensaje general (WhatsApp) a plantas o choferes
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_notificacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once(__DIR__ . '/../LOGICA/relavera_whatsapp_utils.php');

$obBD_con1 = new Class_Log_Datos_notificacion();
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1->setConnection($obBD_conexion);

/**
 * Texto + imagen opcional: si hay imagen y el texto cabe en la leyenda (≤1024), un solo envío;
 * si no, primero el chat completo y luego la imagen con leyenda mínima.
 *
 * @param string|null $imagenBase64 Base64 o null si no hay imagen
 * @return bool
 */
function relavera_enviar_whatsapp_notif_con_imagen($numero, $cuerpoTexto, $imagenBase64)
{
    if ($imagenBase64 === null || $imagenBase64 === '') {
        return relavera_enviar_whatsapp_notif($numero, $cuerpoTexto);
    }
    $len = function_exists('mb_strlen') ? mb_strlen($cuerpoTexto, 'UTF-8') : strlen($cuerpoTexto);
    if ($len <= 1024) {
        return relavera_enviar_whatsapp_imagen_notif($numero, $imagenBase64, $cuerpoTexto);
    }
    $okTxt = relavera_enviar_whatsapp_notif($numero, $cuerpoTexto);
    $okImg = relavera_enviar_whatsapp_imagen_notif($numero, $imagenBase64, '.');
    return $okTxt && $okImg;
}

/**
 * Procesa la subida opcional de imagen. Sin archivo: null. Archivo inválido: false y mensaje en $error.
 *
 * @param string $error
 * @return string|null|false Base64, null si no hubo archivo, false si error
 */
function relavera_notif_leer_imagen_subida(&$error)
{
    $error = '';
    if (!isset($_FILES['imagen_notif']) || !is_array($_FILES['imagen_notif'])) {
        return null;
    }
    if ($_FILES['imagen_notif']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES['imagen_notif']['error'] !== UPLOAD_ERR_OK) {
        if ($_FILES['imagen_notif']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['imagen_notif']['error'] === UPLOAD_ERR_FORM_SIZE) {
            $error = 'La imagen supera el tamaño máximo permitido por el servidor.';
        } else {
            $error = 'No se pudo subir la imagen.';
        }
        return false;
    }
    $tmp = $_FILES['imagen_notif']['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        $error = 'Archivo de imagen no válido.';
        return false;
    }
    $size = (int) @filesize($tmp);
    if ($size <= 0 || $size > 16 * 1024 * 1024) {
        $error = 'La imagen debe ser menor a 16 MB.';
        return false;
    }
    $mime = '';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
    }
    $allowed = array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp');
    if ($mime === '' || !in_array($mime, $allowed, true)) {
        $info = @getimagesize($tmp);
        if ($info !== false && isset($info['mime']) && in_array($info['mime'], $allowed, true)) {
            $mime = $info['mime'];
        }
    }
    if (!in_array($mime, $allowed, true)) {
        $error = 'Use una imagen JPG, PNG, GIF, WEBP o BMP.';
        return false;
    }
    $data = @file_get_contents($tmp);
    if ($data === false || $data === '') {
        $error = 'No se pudo leer la imagen.';
        return false;
    }
    return base64_encode($data);
}

/**
 * Parámetros GET de filtros para listados AJAX (plantas / choferes).
 *
 * @return array
 */
function relavera_notif_params_filtros_listado()
{
    return array(
        'filtro_nombre' => isset($_GET['filtro_nombre']) ? trim($_GET['filtro_nombre']) : '',
        'filtro_cedula' => isset($_GET['filtro_cedula']) ? trim($_GET['filtro_cedula']) : '',
        'filtro_planta' => isset($_GET['filtro_planta']) ? trim($_GET['filtro_planta']) : '',
    );
}

/* ——— AJAX: listado según grupo ——— */
if (isset($cargarListaNotifAjax)) {
    $grupo = isset($_GET['grupo']) ? $_GET['grupo'] : 'plantas';
    if (!in_array($grupo, array('plantas', 'choferes'), true)) {
        $grupo = 'plantas';
    }
    $filtros = relavera_notif_params_filtros_listado();
    $rows = array();
    switch ($grupo) {
        case 'plantas':
            $rows = $obBD_con1->getArrayConsulta(1, array_merge(array('ids' => ''), $filtros), $obBD_conexion);
            break;
        case 'choferes':
            $rows = $obBD_con1->getArrayConsulta(2, array_merge(array('Emp_Cod' => $Ses_Emp_Cod, 'ids' => ''), $filtros), $obBD_conexion);
            break;
    }
    $obBD_con1->utf8_change_param($rows);
    $obBD_con1->echoJson(array('success' => true, 'grupo' => $grupo, 'rows' => $rows));
}

/* ——— AJAX: envío masivo ——— */
if (isset($enviarNotifMasivaAjax)) {
    $resp = array('success' => false, 'message' => '', 'enviados' => 0, 'omitidos' => 0, 'fallidos' => 0);

    $grupo = isset($_POST['grupo']) ? $_POST['grupo'] : 'plantas';
    if (!in_array($grupo, array('plantas', 'choferes'), true)) {
        $grupo = 'plantas';
    }

    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';
    $idsPost = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();

    if ($titulo === '') {
        $resp['message'] = 'Escriba el título del mensaje.';
        $obBD_con1->echoJson($resp);
    }

    if ($mensaje === '') {
        $resp['message'] = 'Escriba el mensaje a enviar.';
        $obBD_con1->echoJson($resp);
    }

    $errImg = '';
    $imagenBase64 = relavera_notif_leer_imagen_subida($errImg);
    if ($imagenBase64 === false) {
        $resp['message'] = $errImg !== '' ? $errImg : 'Imagen no válida.';
        $obBD_con1->echoJson($resp);
    }

    $titulo_esc = str_replace('*', '', $titulo);
    $cuerpo_envio = '*' . $titulo_esc . "*\n\n" . $mensaje;

    $ids = array();
    foreach ($idsPost as $u) {
        $u = (int) $u;
        if ($u > 0) {
            $ids[$u] = $u;
        }
    }

    if (empty($ids)) {
        $resp['message'] = 'Seleccione al menos un destinatario.';
        $obBD_con1->echoJson($resp);
    }

    $idsStr = implode(',', $ids);
    $filas = array();

    switch ($grupo) {
        case 'plantas':
            $filas = $obBD_con1->getArrayConsulta(1, array('ids' => $idsStr), $obBD_conexion);
            if (empty($filas)) {
                $resp['message'] = 'No se encontraron plantas válidas.';
                $obBD_con1->echoJson($resp);
            }
            foreach ($filas as $f) {
                $tel = relavera_telefono_planta_fila($f);
                if ($tel === '') {
                    $resp['omitidos']++;
                    continue;
                }
                if (relavera_enviar_whatsapp_notif_con_imagen($tel, $cuerpo_envio, $imagenBase64)) {
                    $resp['enviados']++;
                } else {
                    $resp['fallidos']++;
                }
            }
            break;

        case 'choferes':
            $filas = $obBD_con1->getArrayConsulta(2, array('Emp_Cod' => $Ses_Emp_Cod, 'ids' => $idsStr), $obBD_conexion);
            if (empty($filas)) {
                $resp['message'] = 'No se encontraron choferes válidos para su empresa.';
                $obBD_con1->echoJson($resp);
            }
            foreach ($filas as $f) {
                $tel = isset($f['Telefono']) ? trim((string) $f['Telefono']) : '';
                if ($tel === '') {
                    $resp['omitidos']++;
                    continue;
                }
                if (relavera_enviar_whatsapp_notif_con_imagen($tel, $cuerpo_envio, $imagenBase64)) {
                    $resp['enviados']++;
                } else {
                    $resp['fallidos']++;
                }
            }
            break;
    }

    $resp['success'] = ($resp['enviados'] > 0);
    if ($resp['enviados'] > 0) {
        $resp['message'] = 'Mensajes enviados: ' . $resp['enviados'] . '.';
        if ($resp['omitidos'] > 0) {
            $resp['message'] .= ' Sin teléfono: ' . $resp['omitidos'] . '.';
        }
        if ($resp['fallidos'] > 0) {
            $resp['message'] .= ' Fallidos: ' . $resp['fallidos'] . '.';
        }
    } else {
        if ($resp['omitidos'] > 0 && $resp['fallidos'] === 0) {
            $resp['message'] = 'Los destinatarios seleccionados no tienen teléfono registrado.';
        } elseif ($resp['fallidos'] > 0) {
            $resp['message'] = 'No se pudo completar el envío (error de API o red).';
        } else {
            $resp['message'] = 'No se pudo enviar el mensaje.';
        }
    }

    $obBD_con1->echoJson($resp);
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Notificaciones Masivas (WhatsApp)</title>
    <meta charset="UTF-8">
    <?php require_once('../../mascaras/model1/estilos/jqgrid5.php'); ?>
    <style>
        .notif-page-title {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .notif-page-title .glyphicon {
            margin-right: 6px;
            opacity: 0.9;
            color: #25d366;
        }

        .notif-intro {
            margin-bottom: 18px;
            border-radius: 6px;
            border-left: 4px solid #5bc0de;
        }

        .notif-intro p {
            margin: 0;
            line-height: 1.45;
        }

        .notif-intro .notif-intro-dynamic {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(0, 0, 0, .06);
            font-size: 12px;
            color: #555;
        }

        .notif-layout {
            margin-bottom: 0;
        }

        .notif-card {
            background: #fff;
            border: 1px solid #e2e2e2;
            border-radius: 8px;
            padding: 16px 18px 18px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
        }

        .notif-card-title {
            margin: 0 0 14px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }

        .notif-card-title .glyphicon {
            margin-right: 6px;
            color: #888;
            font-size: 12px;
        }

        .notif-composer .form-group {
            margin-bottom: 12px;
        }

        .notif-composer label {
            font-weight: 600;
            color: #444;
            font-size: 12px;
        }

        #titulo_notif {
            max-width: 100%;
        }

        #mensaje_notif {
            min-height: 110px;
            resize: vertical;
        }

        #preview_imagen_notif {
            max-height: 150px;
            max-width: 100%;
            margin-top: 10px;
            border-radius: 6px;
            border: 1px solid #ddd;
            display: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
        }

        .notif-imagen-hint {
            font-size: 11px;
            color: #777;
            margin-top: 6px;
            line-height: 1.4;
            margin-bottom: 0;
        }

        .notif-filtros-wrap .exa-fieldset {
            margin-bottom: 0;
            border-radius: 6px;
        }

        .notif-filtros-wrap .Titulos2 {
            font-size: 12px;
        }

        .tabla-notif-wrap {
            max-height: 400px;
            overflow: auto;
            margin-bottom: 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
        }

        .tabla-notif-wrap table {
            margin-bottom: 0;
            background: #fff;
        }

        .tabla-notif-wrap thead th {
            position: sticky;
            top: 0;
            background: #f0f4f3;
            z-index: 2;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #444;
            border-bottom: 2px solid #dde8e4 !important;
            white-space: nowrap;
        }

        .tabla-notif-wrap tbody td {
            font-size: 12px;
            vertical-align: middle !important;
        }

        .sin-tel {
            color: #c0392b;
            font-size: 11px;
        }

        .notif-composer .notif-actions-bar {
            margin-top: 6px;
            padding: 12px 14px;
            background: linear-gradient(to bottom, #fafafa, #f3f3f3);
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }

        .notif-composer .notif-actions-bar #btn_enviar_notif {
            min-width: 160px;
            font-weight: 600;
            border-color: #1fa855;
            background: linear-gradient(to bottom, #25d366, #20bd5a);
        }

        .notif-composer .notif-actions-bar #btn_enviar_notif:hover {
            background: linear-gradient(to bottom, #2ee571, #25d366);
        }

        .notif-composer .notif-actions-bar #notif_estado {
            flex: 1;
            min-width: 120px;
            margin-left: 0 !important;
            font-size: 13px;
        }

        @media (max-width: 991px) {
            .notif-card {
                margin-bottom: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title notif-page-title"><span class="glyphicon glyphicon-phone"></span> Notificaciones masivas (WhatsApp)</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="alert alert-info notif-intro" role="alert">
                <p><strong>Pasos:</strong> Redacte t&iacute;tulo y mensaje, opcionalmente adjunte una imagen, elija el grupo destinatario, filtre si lo necesita, marque filas en la tabla y pulse <em>Enviar mensaje</em>.</p>
                <p class="notif-intro-dynamic text-muted" id="txt_ayuda_grupo">Indique el t&iacute;tulo y el texto; opcionalmente adjunte una imagen. Elija el grupo destinatario, marque filas y env&iacute;e.</p>
            </div>

            <div class="row notif-layout">
                <div class="col-md-5 col-lg-4">
                    <div class="notif-card notif-composer">
                        <h4 class="notif-card-title"><span class="glyphicon glyphicon-edit"></span> Redacci&oacute;n del mensaje</h4>

                        <div class="form-group">
                            <label for="grupo_notif">Enviar a</label>
                            <select id="grupo_notif" name="grupo_notif" class="form-control input-sm">
                                <option value="plantas">Plantas</option>
                                <option value="choferes">Choferes</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="titulo_notif">T&iacute;tulo del mensaje</label>
                            <input type="text" id="titulo_notif" name="titulo_notif" class="form-control input-sm" maxlength="200" placeholder="Ej.: Aviso general, recordatorio&hellip;" />
                        </div>

                        <div class="form-group">
                            <label for="mensaje_notif">Mensaje</label>
                            <textarea id="mensaje_notif" name="mensaje_notif" class="form-control input-sm" maxlength="4000" placeholder="Texto que ver&aacute;n los destinatarios en WhatsApp&hellip;"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="imagen_notif">Imagen (opcional)</label>
                            <input type="file" id="imagen_notif" name="imagen_notif" class="form-control input-sm" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,.jpg,.jpeg,.png,.gif,.webp,.bmp" />
                            <p class="notif-imagen-hint">Formatos: JPG, PNG, GIF, WEBP o BMP; m&aacute;x. 16&nbsp;MB. Si el texto supera 1024 caracteres, se env&iacute;a primero el chat completo y despu&eacute;s la imagen.</p>
                            <img id="preview_imagen_notif" alt="Vista previa" />
                        </div>

                        <div class="notif-actions-bar">
                            <button type="button" id="btn_enviar_notif" class="btn btn-success btn-sm">
                                <span class="glyphicon glyphicon-send"></span> Enviar mensaje
                            </button>
                            <span id="notif_estado" class="text-info"></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-7 col-lg-8">
                    <div class="notif-card notif-recipients">
                        <h4 class="notif-card-title"><span class="glyphicon glyphicon-user"></span> Destinatarios</h4>

                        <div class="notif-filtros-wrap">
                            <fieldset class="exa-fieldset" id="wrap_filtros_plantas" style="display:none;">
                                <legend class="Titulos2">B&uacute;squeda (plantas)</legend>
                                <div class="row">
                                    <div class="col-sm-4">
                                        <label class="label-xs">Nombre (planta o cliente)</label>
                                        <input type="text" id="filtro_pla_nombre" class="form-control input-xs" maxlength="120" placeholder="Nombre o raz&oacute;n social&hellip;" />
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="label-xs">C&eacute;dula / RUC (cliente)</label>
                                        <input type="text" id="filtro_pla_cedula" class="form-control input-xs" maxlength="20" placeholder="C&eacute;dula o RUC&hellip;" />
                                    </div>
                                    <div class="col-sm-4" style="padding-top:20px;">
                                        <button type="button" id="btn_filtrar_notif" class="btn btn-success btn-sm"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="exa-fieldset" id="wrap_filtros_choferes" style="display:none;">
                                <legend class="Titulos2">B&uacute;squeda (choferes)</legend>
                                <div class="row">
                                    <div class="col-sm-3">
                                        <label class="label-xs">Planta</label>
                                        <input type="text" id="filtro_cho_planta" class="form-control input-xs" maxlength="120" placeholder="Nombre de planta&hellip;" />
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="label-xs">Nombre del chofer</label>
                                        <input type="text" id="filtro_cho_nombre" class="form-control input-xs" maxlength="120" placeholder="Nombre o apellido&hellip;" />
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="label-xs">C&eacute;dula del chofer</label>
                                        <input type="text" id="filtro_cho_cedula" class="form-control input-xs" maxlength="20" placeholder="C&eacute;dula&hellip;" />
                                    </div>
                                    <div class="col-sm-3" style="padding-top:20px;">
                                        <button type="button" id="btn_filtrar_notif_cho" class="btn btn-success btn-sm"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        <div class="tabla-notif-wrap" style="margin-top:12px;">
                            <table class="table table-striped table-condensed table-bordered" id="tabla_notif">
                                <thead id="thead_notif"></thead>
                                <tbody id="tbody_notif">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Cargando lista&hellip;</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../VALIDACIONES/man_adm_notificacion.js?x=8"></script>
</body>

</html>