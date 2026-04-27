<?php
/**
 * Listado de usuarios Relavera (empresa de sesión) — consulta vía sql_man_notificacion.php caso 3
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_notificacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_con1 = new Class_Log_Datos_notificacion();
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1->setConnection($obBD_conexion);

if (isset($cargarListaUserNotifAjax)) {
    $filtros = array(
        'filtro_nombre' => isset($_GET['filtro_nombre']) ? trim($_GET['filtro_nombre']) : '',
        'filtro_cedula' => isset($_GET['filtro_cedula']) ? trim($_GET['filtro_cedula']) : '',
    );
    $rows = $obBD_con1->getArrayConsulta(3, array_merge(array('ids' => ''), $filtros, array('Emp_Cod' => $Ses_Emp_Cod)), $obBD_conexion);
    $obBD_con1->utf8_change_param($rows);
    $obBD_con1->echoJson(array('success' => true, 'rows' => $rows));
}

if (isset($guardarUsuarioNotifModalAjax)) {
    $resp = array('success' => false, 'message' => '');
    $Usu_Cod = isset($_POST['Usu_Cod']) ? (int) $_POST['Usu_Cod'] : 0;
    $Prs_Cod = isset($_POST['Prs_Cod']) ? (int) $_POST['Prs_Cod'] : 0;
    $telefono = isset($_POST['Prs_Tel']) ? trim($_POST['Prs_Tel']) : '';
    $telefono = preg_replace('/\D/', '', $telefono);
    $Usu_Cor = isset($_POST['Usu_Cor']) ? trim((string) $_POST['Usu_Cor']) : '';
    $Usu_Cor = str_replace(array("\r", "\n"), '', $Usu_Cor);
    $Usu_Ntf = isset($_POST['Usu_Ntf']) ? trim((string) $_POST['Usu_Ntf']) : '';

    if ($Usu_Cod <= 0 || $Prs_Cod <= 0) {
        $resp['message'] = 'Datos inválidos.';
        $obBD_con1->echoJson($resp);
    }
    if (strlen($telefono) > 30) {
        $resp['message'] = 'El teléfono no puede superar 30 dígitos.';
        $obBD_con1->echoJson($resp);
    }
    if (strlen($Usu_Cor) > 200) {
        $resp['message'] = 'El correo no puede superar 200 caracteres.';
        $obBD_con1->echoJson($resp);
    }
    if ($Usu_Cor !== '' && !filter_var($Usu_Cor, FILTER_VALIDATE_EMAIL)) {
        $resp['message'] = 'Ingrese un correo válido.';
        $obBD_con1->echoJson($resp);
    }
    $Usu_Ntf = strtoupper($Usu_Ntf);
    if ($Usu_Ntf === 'SI') {
        $Usu_Ntf = 'S';
    }
    if ($Usu_Ntf === 'NO') {
        $Usu_Ntf = 'N';
    }
    if ($Usu_Ntf !== 'S' && $Usu_Ntf !== 'N') {
        $resp['message'] = 'Seleccione si desea enviar o no notificaciones.';
        $obBD_con1->echoJson($resp);
    }

    $ok = $obBD_con1->getRowConsulta(6, array('Usu_Cod' => $Usu_Cod, 'Prs_Cod' => $Prs_Cod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    if (empty($ok) || empty($ok['Usu_Cod'])) {
        $resp['message'] = 'No tiene permiso para modificar este registro.';
        $obBD_con1->echoJson($resp);
    }

    try {
        $obBD_con1->operacionobBD('persona.update', array(
            'Prs_Tel' => $telefono,
            'where' => array('Prs_Cod' => $Prs_Cod),
        ), $obBD_conexion);
        $obBD_con1->operacionobBD('usuarios.update', array(
            'Usu_Ntf' => $Usu_Ntf,
            'Usu_Cor' => ($Usu_Cor !== '' ? $Usu_Cor : null),
            'where' => array('Usu_Cod' => $Usu_Cod),
        ), $obBD_conexion);
    } catch (Exception $e) {
        $resp['message'] = 'No se pudo guardar los cambios.';
        $obBD_con1->echoJson($resp);
    }

    $resp['success'] = true;
    $resp['message'] = 'Cambios guardados.';
    $obBD_con1->echoJson($resp);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Usuarios Relavera</title>
    <meta charset="UTF-8">
    <?php require_once('../../mascaras/model1/estilos/jqgrid5.php'); ?>
    <style>
        .user-notif-page-title { margin: 0; font-size: 15px; font-weight: 600; letter-spacing: 0.02em; }
        .user-notif-page-title .glyphicon { margin-right: 6px; color: #25d366; }
        .user-notif-intro {
            margin-bottom: 16px;
            border-radius: 6px;
            border-left: 4px solid #5bc0de;
            line-height: 1.45;
        }
        .user-notif-intro p { margin: 0; }
        .user-notif-card {
            background: #fff;
            border: 1px solid #e2e2e2;
            border-radius: 8px;
            padding: 14px 16px 16px;
            margin-bottom: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .user-notif-card .Titulos2 { font-size: 12px; margin-bottom: 10px; }
        .user-notif-card label { font-weight: 600; color: #444; font-size: 12px; }
        .tabla-user-notif-wrap {
            max-height: 460px;
            overflow: auto;
            margin-bottom: 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
        }
        .tabla-user-notif-wrap table { margin-bottom: 0; background: #fff; }
        .tabla-user-notif-wrap thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f0f4f3;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #444;
            border-bottom: 2px solid #dde8e4 !important;
            white-space: nowrap;
        }
        .tabla-user-notif-wrap tbody td { font-size: 12px; vertical-align: middle !important; }
        .sin-tel { color: #c0392b; font-size: 11px; }
        .tel-alt-muted { color: #888; font-size: 11px; }
        .badge-ntf-s { background: #25d366; color: #fff; font-size: 10px; padding: 3px 8px; border-radius: 3px; font-weight: 600; }
        .badge-ntf-n { background: #95a5a6; color: #fff; font-size: 10px; padding: 3px 8px; border-radius: 3px; font-weight: 600; }
        #modal_edit_usu_notif .modal-header {
            background: linear-gradient(to bottom, #fafafa, #f0f0f0);
            border-radius: 5px 5px 0 0;
            border-bottom: 1px solid #e0e0e0;
        }
        #modal_edit_usu_notif .modal-title { font-size: 15px; font-weight: 600; }
        #modal_edit_usu_notif .modal-title .glyphicon { color: #25d366; margin-right: 6px; }
        #modal_edit_usu_notif .modal_usuario_resalt {
            padding: 10px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #eee;
            margin-bottom: 14px;
            font-size: 13px;
        }
        #modal_edit_usu_notif .form-group label { font-weight: 600; color: #444; font-size: 12px; }
    </style>
</head>
<body>
<div class="panel panel-main">
    <div class="panel-heading exa-header">
        <h3 class="panel-title user-notif-page-title"><span class="glyphicon glyphicon-bullhorn"></span> Usuarios Relavera — notificaciones de anticipos</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="alert alert-info user-notif-intro" role="alert">
            <p>Activar usuarios para que les llegue <strong>notificaciones de anticipos</strong>. <span>Puedes editar el número de telefono, email y activar si el modo para recibir notificaciones.</span>
        </div>
        <div class="user-notif-card">
            <fieldset class="exa-fieldset" style="margin:0; border:none; padding:0;">
                <legend class="Titulos2">B&uacute;squeda</legend>
                <div class="row">
                    <div class="col-sm-4">
                        <label class="label-xs" for="filtro_usu_nombre">Nombre</label>
                        <input type="text" id="filtro_usu_nombre" class="form-control input-sm" maxlength="120" placeholder="Nombre o apellido&hellip;" />
                    </div>
                    <div class="col-sm-4">
                        <label class="label-xs" for="filtro_usu_cedula">C&eacute;dula / RUC</label>
                        <input type="text" id="filtro_usu_cedula" class="form-control input-sm" maxlength="20" placeholder="C&eacute;dula o RUC&hellip;" />
                    </div>
                    <div class="col-sm-4" style="padding-top:20px;">
                        <button type="button" id="btn_filtrar_usu_notif" class="btn btn-success btn-sm"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="tabla-user-notif-wrap">
            <table class="table table-striped table-condensed table-bordered" id="tabla_usu_notif">
                <thead>
                    <tr>
                        <th style="width:46px;" class="text-center">N&deg;</th>
                        <th>Usuario</th>
                        <th>C&eacute;dula / RUC</th>
                        <th>Correo</th>
                        <th>Sucursal</th>
                        <th class="text-center" style="width:120px;">Recibe notificaci&oacute;n</th>
                        <th style="min-width:140px;">Tel&eacute;fono</th>
                        <th class="text-center" style="width:90px;">&nbsp;</th>
                    </tr>
                </thead>
                <tbody id="tbody_usu_notif">
                    <tr><td colspan="8" class="text-center text-muted">Cargando&hellip;</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_edit_usu_notif" tabindex="-1" role="dialog" aria-labelledby="modal_edit_usu_notif_title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modal_edit_usu_notif_title"><span class="glyphicon glyphicon-phone"></span> Editar contacto y notificaciones</h4>
            </div>
            <div class="modal-body">
                <div class="modal_usuario_resalt text-muted" id="modal_usuario_lbl_wrap"><span id="modal_usuario_lbl"></span></div>
                <input type="hidden" id="modal_usu_cod" value="" />
                <input type="hidden" id="modal_prs_cod" value="" />
                <div class="form-group">
                    <label for="modal_prs_tel">Tel&eacute;fono (WhatsApp)</label>
                    <input type="text" id="modal_prs_tel" class="form-control input-sm" maxlength="30" inputmode="numeric" pattern="[0-9]*" autocomplete="tel" placeholder="Solo n&uacute;meros; puede empezar con 0" />
                    <small class="text-muted">Solo d&iacute;gitos; sin letras ni s&iacute;mbolos.</small>
                </div>
                <div class="form-group">
                    <label for="modal_usu_cor">Correo electr&oacute;nico</label>
                    <input type="email" id="modal_usu_cor" class="form-control input-sm" maxlength="200" autocomplete="email" placeholder="usuario@dominio.com" />
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="modal_usu_ntf">Notificaciones de anticipos</label>
                    <select id="modal_usu_ntf" class="form-control input-sm" style="max-width:100%;">
                        <option value="S">Enviar notificaci&oacute;n</option>
                        <option value="N">No enviar notificaci&oacute;n</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-sm" id="btn_modal_guardar_usu_notif"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="../VALIDACIONES/man_user_notificacion.js?x=10"></script>
</body>
</html>
