<?php
/**
 * EXA Adquisiciones - Panel de Configuraci�n Unificado
 * Re�ne la gesti�n de Tipos de Requerimientos y el Dise�ador de Flujos en una sola ventana con pesta�as.
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dis_Dis ?: $Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dis_Dis ?: $Ses_Dat_Dis);

// Verificar acceso a la ventana 'configuracion'
if (!$wf_mgr->verificarAccesoVentana('configuracion')) {
    if (isset($ajax_save_tipo_req) || isset($ajax_toggle_tipo_req) || isset($ajax_get_tipo_req) ||
        isset($ajax_save_workflow) || isset($ajax_load_workflow) || isset($ajax_get_department_users) ||
        isset($ajax_save_department_users) || isset($ajax_get_users_by_department)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para realizar esta acci�n.'));
        exit;
    } else {
        echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
        exit;
    }
}

// --- DELEGACI�N DE ENDPOINTS AJAX ---
if (isset($_GET['ajax_get_tipo_req']) || isset($_GET['ajax_save_tipo_req']) || isset($_POST['ajax_save_tipo_req']) || isset($_POST['ajax_toggle_tipo_req'])) {
    $ajax_get_tipo_req = isset($_GET['ajax_get_tipo_req']) ? $_GET['ajax_get_tipo_req'] : null;
    $ajax_save_tipo_req = isset($_GET['ajax_save_tipo_req']) ? $_GET['ajax_save_tipo_req'] : (isset($_POST['ajax_save_tipo_req']) ? $_POST['ajax_save_tipo_req'] : null);
    $ajax_toggle_tipo_req = isset($_POST['ajax_toggle_tipo_req']) ? $_POST['ajax_toggle_tipo_req'] : null;
    include('adq_tipos_requerimientos.php');
    exit;
}

if (isset($_GET['ajax_load_workflow']) || isset($_GET['ajax_save_workflow']) || isset($_POST['ajax_save_workflow']) || 
    isset($_GET['ajax_get_department_users']) || isset($_POST['ajax_save_department_users']) || isset($_GET['ajax_get_users_by_department'])) {
    $ajax_load_workflow = isset($_GET['ajax_load_workflow']) ? $_GET['ajax_load_workflow'] : null;
    $ajax_save_workflow = isset($_GET['ajax_save_workflow']) ? $_GET['ajax_save_workflow'] : (isset($_POST['ajax_save_workflow']) ? $_POST['ajax_save_workflow'] : null);
    $ajax_get_department_users = isset($_GET['ajax_get_department_users']) ? $_GET['ajax_get_department_users'] : null;
    $ajax_save_department_users = isset($_POST['ajax_save_department_users']) ? $_POST['ajax_save_department_users'] : null;
    $ajax_get_users_by_department = isset($_GET['ajax_get_users_by_department']) ? $_GET['ajax_get_users_by_department'] : null;
    include('wf_builder.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Configuraci�n de Adquisiciones</title>
    <?php require_once('adq_model3_assets.php'); ?>
</head>
<body class="exa-ui-fill-root">
    <div class="panel panel-main exa-ui-panel exa-ui-fill-page">
        <div class="panel-heading exa-header exa-header-flex">
            <h3 class="panel-title"><i class="bi bi-sliders"></i> Configuraci�n de Adquisiciones</h3>
            <div class="exa-header-actions">
                <a href="adq_bandeja.php" class="btn btn-default btn-sm"><i class="bi bi-arrow-left"></i> Volver a Bandeja</a>
            </div>
        </div>
        <div class="panel-body exa-body">
            <div class="exa-ui-page-view">
                <ul class="nav nav-tabs exa-ui-nav-tabs" id="configTabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#tipos-panel" id="tipos-tab" role="tab" data-toggle="tab" onclick="cargarTiposConfiguracion()"><i class="bi bi-tags"></i> Tipos de Requerimiento</a>
                    </li>
                    <li role="presentation">
                        <a href="#builder-panel" id="builder-tab" role="tab" data-toggle="tab" onclick="cargarDisenadorFlujos()"><i class="bi bi-diagram-3"></i> Dise�ador de Flujos</a>
                    </li>
                </ul>

                <div class="tab-content exa-ui-tab-content panels-area" id="configTabsContent">
                    <div class="tab-pane active" id="tipos-panel" role="tabpanel">
                        <div id="tipos-panel-content">
                            <div class="text-center p-5 text-muted">
                                <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size:24px;"></i>
                                <div>Cargando tipos de requerimiento...</div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="builder-panel" role="tabpanel">
                        <div id="builder-panel-content">
                            <div class="text-center p-5 text-muted">
                                <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size:24px;"></i>
                                <div>Cargando dise�ador visual de flujos...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let tiposLoaded = false;
        function cargarTiposConfiguracion() {
            $.get('adq_tipos_requerimientos.php', { ajax_get_tipos: 1 }, function(html) {
                $('#tipos-panel-content').html(html);
                tiposLoaded = true;
            }).fail(function(xhr, status, error) {
                alert('Error al cargar tipos de requerimientos: ' + error + ' (Status: ' + xhr.status + ')');
            });
        }

        let builderLoaded = false;
        function cargarDisenadorFlujos() {
            if (builderLoaded) return;
            $.get('wf_builder.php', { ajax_get_builder: 1 }, function(html) {
                $('#builder-panel-content').html(html);
                builderLoaded = true;
            }).fail(function(xhr, status, error) {
                alert('Error al cargar dise�ador de flujos: ' + error + ' (Status: ' + xhr.status + ')');
            });
        }

        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab === 'disenador') {
                $('a[href="#builder-panel"]').tab('show');
                cargarDisenadorFlujos();
            } else {
                cargarTiposConfiguracion();
            }
        });
    </script>
</body>
</html>
