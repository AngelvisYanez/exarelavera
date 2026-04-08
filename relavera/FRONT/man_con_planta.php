<?php

/* DIRECTORIOS REQUERIDOS */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_con_planta.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Consulta_Planta($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Consulta_Planta;

/* DECLARACION DE AJAX */

// Buscar planta por código
if(isset($buscarPlantaAjax)){
    $resp = array('success' => false, 'message' => '', 'data' => null);
    
    $Pla_Cod = isset($_GET['Pla_Cod']) ? trim($_GET['Pla_Cod']) : '';
    if (empty($Pla_Cod)) {
        $resp['message'] = 'Debe ingresar un código de planta';
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $valores = array('Pla_Cod' => $Pla_Cod);
    
    // Obtener datos de la planta
    $planta = $obBD_con1->getRowConsulta(1, $valores, $obBD_conexion);
    
    if ($obBD_con1->Error == 0) {
        if ($planta && !empty($planta)) {
            $resp['success'] = true;
            $resp['data'] = array();
            $resp['data']['planta'] = $planta;
            
            // Obtener datos del administrador
            $admin = $obBD_con1->getRowConsulta(2, $valores, $obBD_conexion);
            if ($admin && !empty($admin)) {
                $resp['data']['admin'] = $admin;
            }
            
            // Obtener datos del contador
            $contador = $obBD_con1->getRowConsulta(3, $valores, $obBD_conexion);
            if ($contador && !empty($contador)) {
                $resp['data']['contador'] = $contador;
            }
            
            // Obtener datos del ingeniero ambiental
            $ambiental = $obBD_con1->getRowConsulta(4, $valores, $obBD_conexion);
            if ($ambiental && !empty($ambiental)) {
                $resp['data']['ambiental'] = $ambiental;
            }
            
            $resp['message'] = 'Planta encontrada';
        } else {
            $resp['success'] = false;
            $resp['message'] = 'No existe la planta buscada';
        }
    } else {
        $resp['message'] = 'Error en la consulta: ' . $obBD_con1->MsgError;
    }
    
    $obBD_con1->echoJson($resp);
    exit();
}

// Cargar grid de plantas
if(isset($LoadPlantasGridAjax)){
    $filtro = isset($_GET['op_opciones']) ? $_GET['op_opciones'] : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $parms = array( 
        'filtro' => $filtro, 
        'search' => $search
    );
    $resp = $obBD_con1->getArrayConsulta(5, $parms, $obBD_conexion);
    utf8_encode_deep($resp);
    $obBD_con1->echoJson($resp);
    exit();
}

// Buscar plantas para el modal de búsqueda
if(isset($plantasAjax)){
    $filtro = isset($_REQUEST['op_opciones']) ? $_REQUEST['op_opciones'] : '';
    $search = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '';
    
    $parms = array( 
        'filtro' => $filtro, 
        'search' => $search
    );
    $resp = $obBD_con1->getArrayConsulta(5, $parms, $obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit();
}

// Cargar ciudades para los selects
if(isset($loadCiudadesAjax)){
    require_once('../../Librerias/procedimientos/almacenados_standar.php');
    $ciudades = $obBD_con1->getArrayConsulta('ciudad.selectWhere', array('where' => array('Ciu_Est' => 'A'), 'order' => 'ciudad.Ciu_Des ASC'), $obBD_conexion, true);
    utf8_encode_deep($ciudades);
    $obBD_con1->echoJson($ciudades);
    exit();
}

?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?php echo "Consulta de Planta"; ?></TITLE>
        <meta charset="UTF-8">
        <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <style>
            .tab-container {
                margin-top: 20px;
            }
            .tab-content {
                padding: 20px;
                background-color: #fff;
                border: 1px solid #ddd;
                border-top: none;
                min-height: 400px;
            }
            .tab-pane {
                display: none;
            }
            .tab-pane.active {
                display: block;
            }
            .nav-tabs {
                border-bottom: 2px solid #ddd;
            }
            .nav-tabs > li > a {
                border-radius: 4px 4px 0 0;
                margin-right: 2px;
                cursor: pointer;
            }
            .nav-tabs > li.active > a,
            .nav-tabs > li.active > a:hover,
            .nav-tabs > li.active > a:focus {
                color: #555;
                background-color: #fff;
                border: 1px solid #ddd;
                border-bottom-color: transparent;
                cursor: default;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .required-field {
                color: #28a745;
                font-weight: bold;
            }
            .readonly-field {
                background-color: #f5f5f5;
            }
            /* Estilos para subgrid */
            .ui-subgrid {
                background-color: #f9f9f9;
            }
            .ui-subgrid .tab-content {
                padding: 15px;
                background-color: #fff;
                border: 1px solid #ddd;
                border-top: none;
                min-height: auto;
            }
            .ui-subgrid .nav-tabs {
                margin-bottom: 10px;
            }
            .ui-subgrid .form-group {
                margin-bottom: 10px;
                border-bottom: 1px solid #eee;
                padding-bottom: 8px;
            }
            .ui-subgrid .form-group:last-child {
                border-bottom: none;
            }
            .ui-subgrid .form-group label {
                font-weight: bold;
                color: #333;
            }
            .ui-subgrid .form-group .col-xs-9 {
                color: #666;
            }
            /* Forzar que los botones del pager se muestren individualmente */
            #plantasGridPager .ui-pg-button,
            #plantasGridPager .ui-pg-button:hover {
                display: inline-block !important;
                visibility: visible !important;
                float: none !important;
            }
            #plantasGridPager .ui-pg-button-single {
                display: inline-block !important;
            }
            /* Evitar que los botones se agrupen en menú desplegable */
            #plantasGridPager .ui-jqgrid-toppager {
                white-space: nowrap !important;
                overflow: visible !important;
            }
            #plantasGridPager table {
                width: auto !important;
                min-width: 100% !important;
            }
            /* Asegurar espacio suficiente para los botones */
            #plantasGridPager .ui-paging-info {
                margin-right: 10px;
            }
            /* Ocultar y descomponer cualquier menú desplegable de botones */
            #plantasGridPager .ui-pg-button-dropdown,
            #plantasGridPager .ui-pg-button-menu,
            #plantasGridPager .ui-menu {
                display: none !important;
            }
            /* Forzar que los botones dentro de cualquier contenedor sean visibles */
            #plantasGridPager td .ui-pg-button {
                display: inline-block !important;
                visibility: visible !important;
            }

        </style>
    </HEAD>

    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo;Consulta de Planta</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <!-- Grid Principal de Plantas -->
                <div id="plantasSearch">
                    <div class="row">
                        <form name="searchPlanta" id="searchPlanta" class="form-horizontal normal" onsubmit="return false;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">B&uacute;squeda</legend>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                        <div class="col-xs-10 radioset opt_search">
                                            <input id="radsf1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)" alt="" />
                                            <label for="radsf1">Planta</label>
                                            <input id="radsf4" name="op_opciones" type="radio" value="cl" onclick="setfocus(this.form.search)" alt="" />
                                            <label for="radsf4">Cliente</label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                        <div class="col-xs-4">
                                            <div class="input-group">
                                                <input name="search" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable" />
                                                <span class="input-group-btn">
                                                    <button type="button" id="btnSearch" onclick="buscarPlantas()" class="btn btn-success btn-xs" title="Buscar Planta" tabindex="-1">
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
                            <!-- Grid Principal de Plantas -->
                            <div class="col-sm-12" style="min-height: 350px; padding-bottom: 1px;">
                                <table id="plantasGrid"></table>
                                <div id="plantasGridPager"></div>
                                <div type="button" id="exportButtons" style="margin-top: 10px; text-align: left;">
                                    <button id="exportExcelBtn" class="btn btn-primary btn-sm" title="Exportar a Excel">
                                        <span class="glyphicon glyphicon-download"></span> Exportar a Excel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

        </div>
        
        <script type="text/javascript" src="../VALIDACIONES/man_val_con_planta.js?e=7"></script>
        <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <?php
        // Cerrado y liberacion de las conexiones
        $obBD_con1->liberar();
        $obBD_conexion->cerrar();
        ?>
    </BODY>
</HTML>
