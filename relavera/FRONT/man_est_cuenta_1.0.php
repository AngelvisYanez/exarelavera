<?php

/* DIRECTORIOS REQUERIDOS */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_est_cuenta_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Estado_Cuenta($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Estado_Cuenta;

/* formato para fechas */
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* para pruebas */
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 9600);

/* DECLARACION DE AJAX */

/* Obtiene el cliente del usuario logueado */
$cliente_manifiesto = $obBD_con1->getRowConsulta(7, array('Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion);

/* Verificar perfil de usuario */
$row_perfil = $obBD_con1->getRowConsulta(8, array('Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion);
$es_perfil_plantas = (isset($row_perfil['count']) && $row_perfil['count'] > 0);


// Cargar datos del grid principal
if(isset($_REQUEST['loadEstadoCuentaAjax'])){
    $parms = array(
        'Fec_IniM' => isset($_REQUEST['Fec_IniM']) ? $_REQUEST['Fec_IniM'] : '',
        'Fec_FinM' => isset($_REQUEST['Fec_FinM']) ? $_REQUEST['Fec_FinM'] : '',
        'Pla_Cod' => isset($_REQUEST['Pla_Cod']) ? $_REQUEST['Pla_Cod'] : '',
        'Mes_Cod' => isset($_REQUEST['Mes_Cod']) ? $_REQUEST['Mes_Cod'] : '00',
        'Cli_Cod' => isset($_REQUEST['Cli_Cod']) ? $_REQUEST['Cli_Cod'] : ''
    );
    $rows = $obBD_con1->getArrayConsulta(1, $parms, $obBD_conexion);
    
    // Calcular Saldo para cada registro (Ama_Val - Abono)
    if (is_array($rows)) {
        foreach ($rows as &$row) {
            $abono = isset($row['Abono']) ? floatval($row['Abono']) : 0;
            $ama_val = isset($row['Ama_Val']) ? floatval($row['Ama_Val']) : 0;
            $saldo = $ama_val - $abono;
            $row['Saldo'] = floatval($saldo);
            $row['Ama_Val'] = floatval($ama_val);
            $row['Abono'] = floatval($abono);
        }
    }
    
    $resp = array( 'success' => true, 'rows' => $rows, 'total' => count($rows) );
    $obBD_con1->echoJson($resp);
    exit();
}

// Cargar detalle/balance de un cliente
if(isset($_REQUEST['loadDetalleAjax'])){
    $resp = array('success' => false, 'message' => '');
    
    if (!isset($_REQUEST['Cli_Cod']) || empty($_REQUEST['Cli_Cod'])) {
        $resp['message'] = 'No se proporcionó el código del cliente';
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $parms = array(
        'Cli_Cod' => $_REQUEST['Cli_Cod'],
        'Pla_Cod' => isset($_REQUEST['Pla_Cod']) ? $_REQUEST['Pla_Cod'] : '',
        'Fec_Ini' => isset($_REQUEST['Fec_Ini']) ? $_REQUEST['Fec_Ini'] : '',
        'Fec_Fin' => isset($_REQUEST['Fec_Fin']) ? $_REQUEST['Fec_Fin'] : '',
        'Mes_Cod' => isset($_REQUEST['Mes_Cod']) ? $_REQUEST['Mes_Cod'] : '00'
    );
    
    // Obtener movimientos
    $data = $obBD_con1->getArrayConsulta(2, $parms, $obBD_conexion);
    
    // Obtener resumen
    $resumen_parms = array(
        'Cli_Cod' => $_REQUEST['Cli_Cod'],
        'Pla_Cod' => isset($_REQUEST['Pla_Cod']) ? $_REQUEST['Pla_Cod'] : '',
        'Fec_Ini' => isset($_REQUEST['Fec_Ini']) ? $_REQUEST['Fec_Ini'] : '',
        'Fec_Fin' => isset($_REQUEST['Fec_Fin']) ? $_REQUEST['Fec_Fin'] : ''
    );
    $resumen = $obBD_con1->getRowConsulta(5, $resumen_parms, $obBD_conexion);
        
    // Cconsulta dedicada para cabecera completa (Cliente + Cuenta)
    $header_info = $obBD_con1->getRowConsulta(12, array('Cli_Cod' => $_REQUEST['Cli_Cod']), $obBD_conexion);
    
    if ($obBD_con1->Error == 0) {
        $resp['success'] = true;
        $resp['data'] = isset($data['rows']) ? $data['rows'] : array();
        if (isset($data[0])) $resp['data'] = $data;
        
        $resp['resumen'] = $resumen;
        // Usar datos de header_info
        $resp['cliente'] = isset($header_info['Cliente']) ? $header_info['Cliente'] : 'Cliente';
        $resp['cliente_ruc'] = isset($header_info['Prs_Ced']) ? $header_info['Prs_Ced'] : '';
        $resp['cliente_cuenta'] = isset($header_info['Ban_Cue']) ? $header_info['Ban_Cue'] : '';
        $resp['message'] = 'Datos cargados correctamente';
    } else {
        $resp['message'] = 'Error al cargar datos: ' . $obBD_con1->MsgError;
    }
    
    $obBD_con1->echoJson($resp);
    exit();
}

// Buscar plantas
if(isset($_REQUEST['loadPlantasAjax'])){
    $parms = array(
        'search' => isset($_REQUEST['search']) ? $_REQUEST['search'] : ''
    );
    $rows = $obBD_con1->getArrayConsulta(6, $parms, $obBD_conexion);
    
    $resp = array( 'success' => true, 'rows' => $rows );
    $obBD_con1->echoJson($resp);
    exit();
}

// Cargar detalle del movimiento (subgrid)
if(isset($_REQUEST['loadDetalleMovimientoAjax'])){
    $parms = array(
        'Ama_Cod' => isset($_REQUEST['Ama_Cod']) ? $_REQUEST['Ama_Cod'] : ''
    );
    $rows = $obBD_con1->getArrayConsulta(7, $parms, $obBD_conexion);
    
    $resp = array( 'success' => true, 'rows' => $rows );
    $obBD_con1->echoJson($resp);
    exit();
}

// Cargar datos grupales (por planta)
if (isset($_REQUEST['loadEstadoCuentaGrupalAjax'])) {
    $parms = array(
        'Fec_IniM' => isset($_REQUEST['Fec_IniM']) ? $_REQUEST['Fec_IniM'] : '',
        'Fec_FinM' => isset($_REQUEST['Fec_FinM']) ? $_REQUEST['Fec_FinM'] : '',
        'Mes_Cod' => isset($_REQUEST['Mes_Cod']) ? $_REQUEST['Mes_Cod'] : '00'
    );
    $rows = $obBD_con1->getArrayConsulta(9, $parms, $obBD_conexion);

    // Calcular totales para cada registro
    if (is_array($rows)) {
        foreach ($rows as &$row) {
            $saldo_inicial = isset($row['Saldo_Inicial']) ? floatval($row['Saldo_Inicial']) : 0;
            $depositos = isset($row['Depositos']) ? floatval($row['Depositos']) : 0;
            $retenciones = isset($row['Retenciones']) ? floatval($row['Retenciones']) : 0;
            $manifiestos_fact = isset($row['Manifiestos_Fact']) ? floatval($row['Manifiestos_Fact']) : 0;
            $manifiestos_pend = isset($row['Manifiestos_Pend']) ? floatval($row['Manifiestos_Pend']) : 0;

            $row['Saldo_Inicial'] = floatval($saldo_inicial);
            $row['Depositos'] = floatval($depositos);
            $row['Retenciones'] = floatval($retenciones);
            $row['Manifiestos_Fact'] = floatval($manifiestos_fact);
            $row['Manifiestos_Pend'] = floatval($manifiestos_pend);
        }
    }

    $resp = array('success' => true, 'rows' => $rows, 'total' => count($rows));
    $obBD_con1->echoJson($resp);
    exit();
}

/* Periodos */
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
utf8_encode_deep($periodos);

?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?php echo " Estado de Cuenta"; ?></TITLE>
        <meta charset="UTF-8">

        <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        
        <style>
            /* Estilos modernos para el formulario */
            .estado-cuenta-container { background: #DFE9F6; padding: 0; min-height: 100vh; }
            .estado-cuenta-card { background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
            .estado-cuenta-header { background: linear-gradient(135deg, #254463 0%, #1d354d 100%); color: #ffffff; padding: 15px 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px; }
            .estado-cuenta-header h3 { margin: 0; font-size: 18px; font-weight: 600; }
            .filtros-section { border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin-bottom: 20px; }
            .btn-modern { padding: 8px 20px; border-radius: 6px; border: none; font-weight: 500; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; }
            .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
            .btn-success-modern { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; }
            .btn-primary-modern { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #ffffff; }
            .btn-default-modern { background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: #ffffff; }
            #detalle_container { margin-top: 20px; display: none; }
            .well { background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; }
            /* Responsive */
            @media (max-width: 768px) {
                .estado-cuenta-card { padding: 15px; }
                .estado-cuenta-header { padding: 12px 15px; margin: -15px -15px 15px -15px; }
            }
            /* Styles for Modal */
            .modal-header { background: linear-gradient(135deg, #254463 0%, #1d354d 100%); color: white; }
            /* Custom Tabs Styling */
            .nav-tabs { border-bottom: 2px solid #5c9ccc; /* Blue bottom border */ }
            .nav-tabs > li { margin-bottom: -2px; /* Pull down to overlap border */ }
            .nav-tabs > li > a { background: linear-gradient(to bottom, #f0f0f0 0%, #d0d0d0 100%); /* Light grey gradient */ color: #333; /* Dark text */ border: 1px solid #aaa; border-bottom-color: #5c9ccc; border-radius: 6px 6px 0 0; font-weight: bold; margin-right: 2px; padding: 8px 15px; }
            .nav-tabs > li > a:hover { background: linear-gradient(to bottom, #e0e0e0 0%, #c0c0c0 100%); border-color: #999; border-bottom-color: #5c9ccc; }
            .nav-tabs > li.active > a, 
            .nav-tabs > li.active > a:hover, 
            .nav-tabs > li.active > a:focus { background: #fff; /* White background */ color: #d35400; /* Orange/Rust text */ border: 1px solid #5c9ccc; border-bottom-color: transparent; /* Remove bottom border to merge with container */ cursor: default; }
            .nav-tabs > li > a > i { margin-right: 5px; }
        </style>
    </HEAD>

    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo; Estado de Cuenta</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <!-- TABS -->
                <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 15px;">
                    <li role="presentation" class="active"><a href="#tabPlantas" aria-controls="tabPlantas" role="tab" data-toggle="tab">Individual</a></li>
                    <?php if (!$es_perfil_plantas) { ?>
                        <li role="presentation"><a href="#tabPlantero" aria-controls="tabPlantero" role="tab" data-toggle="tab">Grupal</a></li>
                    <?php } ?>
                </ul>

                <div class="tab-content">
                    <!-- TAB 1: Individual -->
                    <div role="tabpanel" class="tab-pane active" id="tabPlantas">
                        <!-- AMBIENTE PRINCIPAL -->
                        <div id="documentoSearch">
                    <div class="row">
                        <form name="searchEstadoCuenta" id="searchEstadoCuenta" class="form-horizontal normal">
                            <div class="col-sm-12">
                                <fieldset class="exa-fieldset filtros-section">
                                    <legend class="Titulos2">Filtros de Búsqueda</legend>
                                    
                                    <div class="row">
                                        <!-- Columna Izquierda: Filtro Planta y Cliente -->
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-2 control-label">Planta:</label>
                                                <div class="col-xs-12 col-sm-10">
                                                    <div class="input-group" style="width: 100%;">
                                                        <input type="hidden" id="Pla_Cod" name="Pla_Cod" />
                                                        <input type="hidden" id="Cli_Cod" name="Cli_Cod" />
                                                        <input type="hidden" id="Ses_Emp_Nom" value="<?php echo isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : 'RELA VERA S.A.'; ?>" />
                                                        <input type="text" id="Pla_Nom" name="Pla_Nom" class="form-control input-xs" placeholder="Seleccione una planta..." readonly style="height: auto" />
                                                        <span class="input-group-btn">
                                                            <button type="button" id="btnBuscarPlanta" class="btn btn-info btn-xs" title="Buscar Planta">
                                                                <span class="glyphicon glyphicon-search"></span>
                                                            </button>
                                                            <button type="button" id="btnLimpiarPlanta" class="btn btn-danger btn-xs" title="Limpiar Planta">
                                                                <span class="glyphicon glyphicon-remove"></span>
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-2 control-label">Cliente:</label>
                                                <div class="col-xs-12 col-sm-10">
                                                    <input type="text" id="Cli_Nom" name="Cli_Nom" class="form-control input-xs" readonly style="height: auto" />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Columna Derecha: Período y Fechas -->
                                        <div class="col-sm-6">
                                            <!-- Fila 1: Período y Mes -->
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-3 control-label label-xs">Período:</label>
                                                <div class="col-xs-12 col-sm-3">
                                                    <select name="Pec_Cod" id="Pec_Cod" class="form-control input-xs" style="height: auto; width: 100%; text-align: center;" onchange="cambiarPeriodo()">
                                                        <option value="T"><< TODOS >></option>
                                                        <option value="PF"><< Por Fechas >></option>
                                                        <?php
                                                        $currentYear = date("Y");
                                                        foreach ($periodos as $p) {
                                                            $year = substr($p['Pec_Fei'], 0, 4);
                                                            $selected = ($year == $currentYear) ? 'selected' : '';
                                                            echo "<option data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' value='$p[Pec_Cod]' $selected>$year</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <label class="col-xs-12 col-sm-1 control-label label-xs">Mes:</label>
                                                <div class="col-xs-12 col-sm-3">
                                                    <select name="Mes_Cod" id="Mes_Cod" class="form-control input-xs" style="height: auto; width: 100%; text-align: center;" onchange="cambiarPeriodo()">
                                                        <option value="00"><< TODOS >></option>
                                                        <option value="01" <?php if($mes == '01') echo 'selected'; ?>>Enero</option>
                                                        <option value="02" <?php if($mes == '02') echo 'selected'; ?>>Febrero</option>
                                                        <option value="03" <?php if($mes == '03') echo 'selected'; ?>>Marzo</option>
                                                        <option value="04" <?php if($mes == '04') echo 'selected'; ?>>Abril</option>
                                                        <option value="05" <?php if($mes == '05') echo 'selected'; ?>>Mayo</option>
                                                        <option value="06" <?php if($mes == '06') echo 'selected'; ?>>Junio</option>
                                                        <option value="07" <?php if($mes == '07') echo 'selected'; ?>>Julio</option>
                                                        <option value="08" <?php if($mes == '08') echo 'selected'; ?>>Agosto</option>
                                                        <option value="09" <?php if($mes == '09') echo 'selected'; ?>>Septiembre</option>
                                                        <option value="10" <?php if($mes == '10') echo 'selected'; ?>>Octubre</option>
                                                        <option value="11" <?php if($mes == '11') echo 'selected'; ?>>Noviembre</option>
                                                        <option value="12" <?php if($mes == '12') echo 'selected'; ?>>Diciembre</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Fila 2: Fechas -->
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-3 control-label label-xs">Fecha:</label>
                                                <div class="col-xs-12 col-sm-9">
                                                    <div class="input-group input-group-xs" style="width: 100%;">
                                                        <span class="input-group-addon alert-info">Desde</span>
                                                        <input type="text" id="Fec_IniM" name="Fec_IniM" class="form-control datepicker" style="text-align: center;" disabled />
                                                        <span class="input-group-addon" style="cursor: pointer;">
                                                            <i class="glyphicon glyphicon-transfer"></i>
                                                        </span>
                                                        <span class="input-group-addon alert-info">Hasta</span>
                                                        <input type="text" id="Fec_FinM" name="Fec_FinM" class="form-control datepicker" style="text-align: center;" disabled />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Fila 3: Botones -->
                                            <div class="form-group">
                                                <div class="col-xs-12 text-right">
                                                    <button type="button" id="btnBuscar" class="btn btn-success btn-xs" onclick="buscarEstadoCuenta()">
                                                        <span class="glyphicon glyphicon-search"></span> Buscar
                                                    </button>
                                                    <button type="button" class="btn btn-default btn-xs btn-danger" onclick="limpiarFiltros()">
                                                        <span class="glyphicon glyphicon-trash"></span> Limpiar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            
                            <!-- Grid Principal de Estado de Cuenta (jqGrid) - OCULTO -->
                            <div class="col-sm-12" style="padding-bottom: 10px; display: none;">
                                <table id="gridEstadoCuenta"></table>
                                <div id="pagerEstadoCuenta"></div>
                            </div>

                            <!-- Contenedor de Detalle -->
                            <div class="col-sm-12">
                                <div id="detalle_container"></div>
                            </div>
                        </form>
                    </div>
                </div>
                </div>
                
                <!-- TAB 2: Grupal -->
                <?php if (!$es_perfil_plantas) { ?>
                <div role="tabpanel" class="tab-pane" id="tabPlantero">
                    <!-- AMBIENTE GRUPAL -->
                        <div id="documentoSearchGrupal">
                            <div class="row">
                                <form name="searchEstadoCuentaGrupal" id="searchEstadoCuentaGrupal" class="form-horizontal normal">
                                    <div class="col-sm-10 col-sm-offset-1">
                                        <fieldset class="exa-fieldset filtros-section">
                                            <legend class="Titulos2">Filtros de Búsqueda</legend>

                                            <!-- Fila 1: Todos los filtros en una línea -->
                                            <div class="row" style="font-size: 14px;">
                                                <!-- Período -->
                                                <div class="col-xs-12 col-sm-3" style="padding-right: 8px; width: 200px;">
                                                    <label class="control-label label-xs" style="display: inline-block; width: auto; margin-right: 5px; vertical-align: middle; font-size: 14px;">Período:</label>
                                                    <select name="Pec_Cod_Grupal" id="Pec_Cod_Grupal" class="form-control input-xs" style="display: inline-block; height: auto; width: 100px; text-align: center; vertical-align: middle; font-size: 14px;" onchange="cambiarPeriodoGrupal()">
                                                        <option value="T">
                                                            << TODOS>>
                                                        </option>
                                                        <option value="PF">
                                                            << Por Fechas>>
                                                        </option>
                                                        <?php
                                                        $currentYear = date("Y");
                                                        foreach ($periodos as $p) {
                                                            $year = substr($p['Pec_Fei'], 0, 4);
                                                            $selected = ($year == $currentYear) ? 'selected' : '';
                                                            echo "<option data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' value='$p[Pec_Cod]' $selected>$year</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <!-- Mes -->
                                                <div class="col-xs-12 col-sm-2" style="padding-left: 8px; padding-right: 8px; width: 300px;">
                                                    <label class="control-label label-xs" style="display: inline-block; width: auto; margin-right: 5px; vertical-align: middle; font-size: 14px;">Mes:</label>
                                                    <select name="Mes_Cod_Grupal" id="Mes_Cod_Grupal" class="form-control input-xs" style="display: inline-block; height: auto; width: 100px; text-align: center; vertical-align: middle; font-size: 14px;" onchange="cambiarPeriodoGrupal()">
                                                        <option value="00">
                                                            << TODOS>>
                                                        </option>
                                                        <option value="01" <?php if ($mes == '01') echo 'selected'; ?>>Enero</option>
                                                        <option value="02" <?php if ($mes == '02') echo 'selected'; ?>>Febrero</option>
                                                        <option value="03" <?php if ($mes == '03') echo 'selected'; ?>>Marzo</option>
                                                        <option value="04" <?php if ($mes == '04') echo 'selected'; ?>>Abril</option>
                                                        <option value="05" <?php if ($mes == '05') echo 'selected'; ?>>Mayo</option>
                                                        <option value="06" <?php if ($mes == '06') echo 'selected'; ?>>Junio</option>
                                                        <option value="07" <?php if ($mes == '07') echo 'selected'; ?>>Julio</option>
                                                        <option value="08" <?php if ($mes == '08') echo 'selected'; ?>>Agosto</option>
                                                        <option value="09" <?php if ($mes == '09') echo 'selected'; ?>>Septiembre</option>
                                                        <option value="10" <?php if ($mes == '10') echo 'selected'; ?>>Octubre</option>
                                                        <option value="11" <?php if ($mes == '11') echo 'selected'; ?>>Noviembre</option>
                                                        <option value="12" <?php if ($mes == '12') echo 'selected'; ?>>Diciembre</option>
                                                    </select>
                                                </div>

                                                <!-- Espacio en blanco -->
                                                <div class="col-xs-12 col-sm-1"></div>

                                                <!-- Fechas -->
                                                <div class="col-xs-12 col-sm-6" style="padding-left: 8px; white-space: nowrap;">
                                                    <label class="control-label label-xs" style="display: inline-block; width: auto; margin-right: 5px; font-size: 14px;">Fecha:</label>
                                                    <span class="input-group-addon alert-info" style="display: inline-block; font-size: 14px; vertical-align: middle; border-radius: 4px 0 0 4px; margin-bottom: 0; width: 65px;">Desde</span>
                                                    <input type="text" id="Fec_IniM_Grupal" name="Fec_IniM_Grupal" class="form-control datepicker" style="display: inline-block; text-align: center; width: 120px; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0; margin-bottom: 0;" disabled />
                                                    <span class="input-group-addon" style="display: inline-block; cursor: pointer; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0; margin-bottom: 0; width: auto;">
                                                        <i class="glyphicon glyphicon-transfer"></i>
                                                    </span>
                                                    <span class="input-group-addon alert-info" style="display: inline-block; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0; margin-bottom: 0; width: 65px;">Hasta</span>
                                                    <input type="text" id="Fec_FinM_Grupal" name="Fec_FinM_Grupal" class="form-control datepicker" style="display: inline-block; text-align: center; width: 120px; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0 4px 4px 0; margin-bottom: 0;" disabled />
                                                </div>
                                            </div>

                                            <!-- Fila 2: Botones de acción -->
                                            <div class="row" style="margin-top: 15px;">
                                                <div class="col-xs-12 col-sm-offset-6 col-sm-6 text-right">
                                                    <button type="button" id="btnBuscarGrupal" class="btn btn-success btn-sm" onclick="buscarEstadoCuentaGrupal()">
                                                        <span class="glyphicon glyphicon-search"></span> Buscar
                                                    </button>
                                                    <button type="button" id="btnExportarExcelGrupal" class="btn btn-primary btn-sm" onclick="exportarExcelGrupal()">
                                                        <span class="glyphicon glyphicon-download-alt"></span> Excel
                                                    </button>
                                                    <button type="button" id="btnExportarPDFGrupal" class="btn btn-danger btn-sm" onclick="exportarPDFGrupal()">
                                                        <span class="glyphicon glyphicon-file"></span> PDF
                                                    </button>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>

                                    <!-- Contenedor de Resultados Grupal -->
                                    <div class="col-sm-12" style="margin-top: 20px;">
                                        <div id="detalle_grupal_container"></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                </div> <!-- Fin tab-content -->
            </div>
        </div>

        <!-- Dialogo Buscar Planta (jQuery UI) -->
        <div id="plantaDialog" title="Buscar Planta" style="display: none;">
            <form class="form-horizontal normal">
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Criterios de Búsqueda</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Buscar:</label>
                                <div class="col-xs-7">
                                    <input type="text" id="searchPlantaInput" class="form-control input-xs" placeholder="Ingrese nombre de planta o ciudad...">
                                </div>
                                <div class="col-xs-2">
                                    <button class="btn btn-success btn-xs btn-block" type="button" onclick="buscarPlantas()">
                                        <span class="glyphicon glyphicon-search"></span> Buscar
                                    </button>
                                </div>
                            </div>
                        </fieldset>
                        <div style="margin-top: 10px;">
                            <table id="gridPlantas"></table>
                            <div id="pagerPlantas"></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script src="../VALIDACIONES/man_est_cuenta_1.0.js?e=4"></script>
        <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                <?php 
                // Verificar si se obtuvo un registro válido y si tiene planta asignada
                if ($cliente_manifiesto && isset($cliente_manifiesto['Pla_Cod']) && !empty($cliente_manifiesto['Pla_Cod'])) { 
                ?>
                    // Pre-cargar datos del usuario/planta
                    $("#Pla_Cod").val(<?php echo json_encode($cliente_manifiesto['Pla_Cod']); ?>);
                    $("#Pla_Nom").val(<?php echo json_encode($cliente_manifiesto['Pla_Nom']); ?>);
                    $("#Cli_Cod").val(<?php echo json_encode($cliente_manifiesto['Cli_Cod']); ?>);
                    $("#Cli_Nom").val(<?php echo json_encode($cliente_manifiesto['nombre']); ?>);
                    
                    // Ocultar botones de búsqueda de planta
                    $("#btnBuscarPlanta").hide();
                    $("#btnLimpiarPlanta").hide();
                    // Ocultar el contenedor de botones para que el input ocupe todo el ancho
                    $("#btnBuscarPlanta").closest(".input-group-btn").hide();
                <?php } ?>
                
                // Inicializar fechas según periodo/mes seleccionado
                cambiarPeriodo();
                cambiarPeriodoGrupal();
            });
        </script>

        <?php
        // Cerrado y liberacion de las conexiones
            $obBD_con1->liberar();
            $obBD_conexion->cerrar();
        ?>
    </BODY>
</HTML>
