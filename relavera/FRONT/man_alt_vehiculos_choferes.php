<?php

/**
 * Formulario de Registro de Vehículos y Choferes - Relavera
 * Permite ingresar choferes y vehículos asociándolos a la planta activa en segundo plano.
 * Cuenta con un ambiente de consulta y otro de registro (Dos Ambientes).
 * @author Sistema EXA
 * @version 1.1
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_vehiculos_choferes.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Vehiculos_Choferes($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Vehiculos_Choferes;

// 1. Intentar obtener planta de la sesión del usuario
$Pla_Cod_Log = isset($_SESSION['Ses_Pla_Cod']) ? (int)$_SESSION['Ses_Pla_Cod'] : 0;

// 2. Si no está en sesión, buscar en manifiesto_usuario por el usuario logeado
if ($Pla_Cod_Log === 0) {
    $row_mu = $obBD_con1->getArrayConsultaSql("SELECT Pla_Cod FROM manifiesto_usuario WHERE Usu_Cod = '" . $_SESSION['Ses_Usu_Cod'] . "' LIMIT 1", $obBD_conexion);
    if (isset($row_mu[0]['Pla_Cod'])) {
        $Pla_Cod_Log = (int)$row_mu[0]['Pla_Cod'];
    }
}

// 3. Si sigue siendo 0, obtener la primera planta activa de la empresa
if ($Pla_Cod_Log === 0) {
    $row_pla_def = $obBD_con1->getArrayConsultaSql("SELECT Pla_Cod FROM manifiesto_plantas mp LEFT JOIN cliente c ON c.Cli_Cod = mp.Cli_Cod WHERE mp.Pla_Est = 'A' AND c.Emp_Cod = '" . $_SESSION['Ses_Emp_Cod'] . "' LIMIT 1", $obBD_conexion);
    $Pla_Cod_Log = isset($row_pla_def[0]['Pla_Cod']) ? (int)$row_pla_def[0]['Pla_Cod'] : 0;
}

// Obtener nombre de la planta para la vista
$planta_nombre = 'Sin planta asignada';
if ($Pla_Cod_Log > 0) {
    $row_pla = $obBD_con1->getArrayConsultaSql("SELECT Pla_Nom FROM manifiesto_plantas WHERE Pla_Cod = $Pla_Cod_Log LIMIT 1", $obBD_conexion);
    if (isset($row_pla[0]['Pla_Nom'])) {
        $obBD_con1->utf8_change_param($row_pla);
        $planta_nombre = $row_pla[0]['Pla_Nom'];
    }
}

/* ==================== AJAX HANDLERS ==================== */

// Buscar persona por cédula para autocompletar
if (isset($_GET['buscarPersonaPorCedulaAjax'])) {
    $cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';
    $resp = array('exists' => false);
    if (!empty($cedula)) {
        // Quitar dígitos adicionales de RUC para la búsqueda en la tabla persona
        $prsCedBusq = strlen($cedula) === 13 ? substr($cedula, 0, 10) : $cedula;

        $row_per = $obBD_con1->getArrayConsultaSql("SELECT Prs_Cod, Prs_Nom, Prs_Ape, Prs_Tel, Prs_San FROM persona WHERE Prs_Ced = '$prsCedBusq' LIMIT 1", $obBD_conexion);
        if (!empty($row_per)) {
            $obBD_con1->utf8_change_param($row_per);
            $resp['exists'] = true;
            $resp['Prs_Nom'] = $row_per[0]['Prs_Nom'];
            $resp['Prs_Ape'] = $row_per[0]['Prs_Ape'];
            $resp['Prs_Tel'] = $row_per[0]['Prs_Tel'];
            $resp['Prs_San'] = $row_per[0]['Prs_San'];

            // Buscar si ya es chofer para cargar datos de su licencia
            $Prs_Cod = (int)$row_per[0]['Prs_Cod'];
            $row_cho = $obBD_con1->getArrayConsultaSql("SELECT Cho_Tli, Cho_Cli FROM chofer WHERE Prs_Cod = $Prs_Cod LIMIT 1", $obBD_conexion);
            if (!empty($row_cho)) {
                $obBD_con1->utf8_change_param($row_cho);
                $resp['isChofer'] = true;
                $resp['Cho_Tli'] = $row_cho[0]['Cho_Tli'];
                $resp['Cho_Cli'] = '';
                if (!empty($row_cho[0]['Cho_Cli'])) {
                    $parts = explode('-', $row_cho[0]['Cho_Cli']);
                    if (count($parts) === 3) {
                        $resp['Cho_Cli'] = $parts[2] . '/' . $parts[1] . '/' . $parts[0];
                    } else {
                        $resp['Cho_Cli'] = $row_cho[0]['Cho_Cli'];
                    }
                }
            }
        }
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Listar empresas de transporte para el selector
if (isset($_GET['listTransportesAjax'])) {
    $rows_data = $obBD_con1->getArrayConsulta(1, array($_SESSION['Ses_Emp_Cod']), $obBD_conexion);
    $obBD_con1->utf8_change_param($rows_data);
    $obBD_con1->echoJson($rows_data);
    exit;
}

// Listar Choferes para el Grid (Ambiente 1)
if (isset($_GET['listChoferesGridAjax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $op_opciones = isset($_GET['op_opciones']) ? trim($_GET['op_opciones']) : 'd';

    $params = array(
        0 => $_SESSION['Ses_Emp_Cod'],
        'Pla_Cod' => $Pla_Cod_Log,
        'search' => $search,
        'op_opciones' => $op_opciones
    );

    $row_count = $obBD_con1->getRowConsulta(2, $params, $obBD_conexion);
    $total_records = isset($row_count['total']) ? (int)$row_count['total'] : 0;

    $pagination = pages($total_records, $page, $rows);
    $response = $pagination['data'];

    if ($total_records > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(2, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
    exit;
}

// Listar Vehículos para el Grid (Ambiente 1)
if (isset($_GET['listVehiculosGridAjax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $op_opciones = isset($_GET['op_opciones']) ? trim($_GET['op_opciones']) : 'p';

    $params = array(
        0 => $_SESSION['Ses_Emp_Cod'],
        'Pla_Cod' => $Pla_Cod_Log,
        'search' => $search,
        'op_opciones' => $op_opciones
    );

    $row_count = $obBD_con1->getRowConsulta(3, $params, $obBD_conexion);
    $total_records = isset($row_count['total']) ? (int)$row_count['total'] : 0;

    $pagination = pages($total_records, $page, $rows);
    $response = $pagination['data'];

    if ($total_records > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(3, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
    exit;
}

// Buscar Proveedor por cédula o RUC (AJAX)
if (isset($_POST['buscarProveedorAjax'])) {
    $Prv_Ced = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
    $resp = array('success' => false, 'message' => 'Proveedor no encontrado.');
    
    if (!empty($Prv_Ced)) {
        $sql = "SELECT proveedor.Prv_Cod, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) as Prv_Nom 
                FROM proveedor 
                INNER JOIN persona ON persona.Prs_Cod = proveedor.Prs_Cod 
                WHERE persona.Prs_Ced = '$Prv_Ced' LIMIT 1";
        $proveedor = $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion);
        
        if (!empty($proveedor)) {
            $resp['success'] = true;
            $resp['Prv_Cod'] = $proveedor[0]['Prv_Cod'];
            $resp['Prv_Nom'] = trim($proveedor[0]['Prv_Nom']);
        }
    }
    
    $obBD_con1->echoJson($resp);
    exit;
}

// Guardar Chofer (AJAX)
if (isset($_POST['saveChoferAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Cho_Ced = isset($_POST['Cho_Ced']) ? trim($_POST['Cho_Ced']) : '';
        $Prs_Nom = isset($_POST['Prs_Nom']) ? trim($_POST['Prs_Nom']) : '';
        $Prs_Ape = isset($_POST['Prs_Ape']) ? trim($_POST['Prs_Ape']) : '';
        $Cho_Tli = isset($_POST['Cho_Tli']) ? trim($_POST['Cho_Tli']) : '';
        $Cho_Cli = isset($_POST['Cho_Cli']) ? trim($_POST['Cho_Cli']) : '';
        $Cho_Tel = isset($_POST['Cho_Tel']) ? trim($_POST['Cho_Tel']) : '';
        $Cho_Tsa = isset($_POST['Cho_Tsa']) ? trim($_POST['Cho_Tsa']) : '';

        if (empty($Cho_Ced) || empty($Prs_Nom) || empty($Prs_Ape) || empty($Cho_Tli) || empty($Cho_Cli) || empty($Cho_Tel) || empty($Cho_Tsa)) {
            throw new Exception('Todos los campos marcados con asterisco (*) son obligatorios.');
        }

        // Formatear fecha para MySQL (si viene como dd/mm/aaaa)
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $Cho_Cli)) {
            $parts = explode('/', $Cho_Cli);
            $Cho_Cli = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }

        $prsCedBusq = strlen($Cho_Ced) === 13 ? substr($Cho_Ced, 0, 10) : $Cho_Ced;

        // Buscar persona por cédula
        $persona = $obBD_con1->getArrayConsultaSql("SELECT Prs_Cod FROM persona WHERE Prs_Ced = '$prsCedBusq' LIMIT 1", $obBD_conexion);

        if (empty($persona)) {
            $ideCod = strlen($Cho_Ced) === 13 ? '2' : '1'; // 2=RUC, 1=Cédula
            $obBD_con1->operacionobBD(4, array(null, $ideCod, $prsCedBusq, $Prs_Nom, $Prs_Ape, $Cho_Tel, $Cho_Tsa), $obBD_conexion);
            $Prs_Cod = $obBD_con1->insercionid($obBD_conexion);
        } else {
            $Prs_Cod = $persona[0]['Prs_Cod'];
            $obBD_con1->operacionobBD(5, array($Prs_Cod, $Prs_Nom, $Prs_Ape, $Cho_Tel, $Cho_Tsa), $obBD_conexion);
        }

        // Buscar chofer
        $chofer = $obBD_con1->getArrayConsultaSql("SELECT Cho_Cod FROM chofer WHERE Prs_Cod = $Prs_Cod AND Emp_Cod = $Ses_Emp_Cod LIMIT 1", $obBD_conexion);
        if (!empty($chofer)) {
            $Cho_Cod = $chofer[0]['Cho_Cod'];
            $obBD_con1->operacionobBD(7, array($Cho_Cod, $Cho_Tli, $Cho_Cli, $Cho_Tel, $Cho_Tsa), $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD(6, array($Prs_Cod, $Ses_Emp_Cod, $Cho_Tli, $Cho_Cli, $Cho_Tel, $Cho_Tsa), $obBD_conexion);
            $Cho_Cod = $obBD_con1->insercionid($obBD_conexion);
        }

        // Crear relación con la planta activa
        $obBD_con1->operacionobBD(8, array($Cho_Cod, $Pla_Cod_Log), $obBD_conexion);

        $resp['success'] = true;
        $resp['message'] = 'Chofer registrado exitosamente.';
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}

// Guardar Vehículo (AJAX)
if (isset($_POST['saveVehiculoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Prv_Cod = isset($_POST['Prv_Cod']) ? (int)$_POST['Prv_Cod'] : 0;
        $Veh_Mar = isset($_POST['Veh_Mar']) ? trim($_POST['Veh_Mar']) : '';
        $Veh_Pla = isset($_POST['Veh_Pla']) ? trim($_POST['Veh_Pla']) : '';
        $Veh_Col = isset($_POST['Veh_Col']) ? trim($_POST['Veh_Col']) : '';
        $Veh_Tit = isset($_POST['Veh_Tit']) ? trim($_POST['Veh_Tit']) : '';

        if (empty($Prv_Cod) || empty($Veh_Mar) || empty($Veh_Pla) || empty($Veh_Col) || empty($Veh_Tit)) {
            throw new Exception('Todos los campos marcados con asterisco (*) son obligatorios.');
        }

        $veh_cap_val = 0; // Capacidad removida del form

        // Buscar vehículo
        $veh_exist = $obBD_con1->getArrayConsultaSql("SELECT Veh_Cod FROM vehiculo WHERE Veh_Pla = '$Veh_Pla' AND Emp_Cod = $Ses_Emp_Cod AND Veh_Est = 'A' LIMIT 1", $obBD_conexion);
        if (!empty($veh_exist)) {
            $Veh_Cod = $veh_exist[0]['Veh_Cod'];
            $obBD_con1->operacionobBD(10, array($Veh_Cod, $Veh_Mar, $Veh_Col, $veh_cap_val, $Veh_Tit, NULL, $Prv_Cod), $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD(9, array($Veh_Mar, $Veh_Pla, $Veh_Col, $veh_cap_val, $Veh_Tit, $Ses_Emp_Cod, NULL, $Prv_Cod), $obBD_conexion);
            $Veh_Cod = $obBD_con1->insercionid($obBD_conexion);
        }

        // Asociar con la planta activa
        $obBD_con1->operacionobBD(11, array($Veh_Cod, $Pla_Cod_Log), $obBD_conexion);

        $resp['success'] = true;
        $resp['message'] = 'Vehículo registrado exitosamente.';
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}
?>
<!DOCTYPE HTML>
<HTML>

<HEAD>
    <TITLE>Registrar Choferes y Vehículos [EXA]</TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script language="javascript" src="../../framework/plugins/cedulaRuc.js"></script>
    <link rel="stylesheet" type="text/css" href="../RECURSOS/vehiculos_choferes.css" />
</HEAD>

<BODY>
    <div class="panel panel-default panel-main">
        <div class="panel-heading">
            <span class="glyphicon glyphicon-edit"></span> » Gestión de Vehículos y Operarios
        </div>
        <div class="panel-body exa-body">

            <!-- ==================== AMBIENTE 1: CONSULTA / LISTADO ==================== -->
            <div id="divListado">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="active">
                            <a href="#tabListChoferes" role="tab" data-toggle="tab">
                                <i class="glyphicon glyphicon-user" style="margin-right: 8px;"></i>Datos del Operario
                            </a>
                        </li>
                        <li>
                            <a href="#tabListVehiculos" role="tab" data-toggle="tab">
                                <i class="glyphicon glyphicon-road" style="margin-right: 8px;"></i>Datos del Vehiculo
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content tab-content-custom">

                        <!-- Grid Choferes -->
                        <div role="tabpanel" class="tab-pane active" id="tabListChoferes">
                            <div class="form-inline" style="margin-bottom: 15px;">
                                <label class="control-label" style="font-weight: bold;">Filtrar por:</label>
                                <select id="opChofer" class="form-control input-sm" style="margin-left: 5px;">
                                    <option value="d">Nombre</option>
                                    <option value="c">Cédula</option>
                                </select>
                                <input type="text" id="searchChofer" class="form-control input-sm" placeholder="Buscar..." style="margin-left: 5px; width: 200px;">
                                <button type="button" class="btn btn-sm btn-primary" id="btnBuscarChofer" onclick="reloadGridChoferes();"><i class="fa fa-search"></i> Buscar</button>

                                <!-- Botón que lleva al Ambiente 2 de registro -->
                                <button type="button" class="btn btn-sm btn-exa-success" style="float: right;" onclick="mostrarFormulario('chofer');">
                                    <i class="glyphicon glyphicon-plus"></i> Registrar Nuevo Operario
                                </button>
                            </div>
                            <table id="gridChoferes"></table>
                            <div id="pagerChoferes"></div>
                        </div>

                        <!-- Grid Vehículos -->
                        <div role="tabpanel" class="tab-pane" id="tabListVehiculos">
                            <div class="form-inline" style="margin-bottom: 15px;">
                                <label class="control-label" style="font-weight: bold;">Filtrar por:</label>
                                <select id="opVehiculo" class="form-control input-sm" style="margin-left: 5px;">
                                    <option value="p">Placa</option>
                                </select>
                                <input type="text" id="searchVehiculo" class="form-control input-sm" placeholder="Buscar por placa..." style="margin-left: 5px; width: 200px;">
                                <button type="button" class="btn btn-sm btn-primary" id="btnBuscarVehiculo" onclick="reloadGridVehiculos();"><i class="fa fa-search"></i> Buscar</button>

                                <!-- Botón que lleva al Ambiente 2 de registro -->
                                <button type="button" class="btn btn-sm btn-exa-success" style="float: right;" onclick="mostrarFormulario('vehiculo');">
                                    <i class="glyphicon glyphicon-plus"></i> Registrar Nuevo Vehículo
                                </button>
                            </div>
                            <table id="gridVehiculos"></table>
                            <div id="pagerVehiculos"></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ==================== AMBIENTE 2: REGISTRO / FORMULARIO ==================== -->
            <div id="divFormulario" style="display:none;">
                <!-- Contenedor con ancho limitado para que los componentes no se vean tan extendidos o amplios -->
                <div style="max-width: 850px; margin: 0 auto;">

                    <!-- Formulario Chofer Individual -->
                    <div id="divFormTabChofer" style="display:none;">
                        <form id="formChofer" class="form-horizontal" onsubmit="return false;">
                            <fieldset class="exa-fieldset">
                                <legend>Datos Personales del Operario</legend>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Cédula o RUC:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <input id="Cho_Ced" name="Cho_Ced" type="text" class="form-control" placeholder="Cédula o RUC" maxlength="13" onkeypress="return validar_numeric(event);" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Teléfono:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <input id="Cho_Tel" name="Cho_Tel" type="text" class="form-control" placeholder="Teléfono o Celular" maxlength="15" onkeypress="return validar_numeric(event);" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Nombres:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <input id="Prs_Nom" name="Prs_Nom" type="text" class="form-control" placeholder="Nombres del Operario" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Apellidos:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <input id="Prs_Ape" name="Prs_Ape" type="text" class="form-control" placeholder="Apellidos del Operario" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Tipo de Licencia:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <select id="Cho_Tli" name="Cho_Tli" class="form-control">
                                                    <option value="">Seleccione...</option>
                                                    <option value="Np">NO POSEE</option>
                                                    <option value="A">TIPO A</option>
                                                    <option value="B">TIPO B</option>
                                                    <option value="C">TIPO C</option>
                                                    <option value="C1">TIPO C1</option>
                                                    <option value="D">TIPO D</option>
                                                    <option value="D1">TIPO D1</option>
                                                    <option value="E">TIPO E</option>
                                                    <option value="E1">TIPO E1</option>
                                                    <option value="F">TIPO F</option>
                                                    <option value="G">TIPO G</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Caducidad Licencia:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <input id="Cho_Cli" name="Cho_Cli" type="text" class="form-control datepicker" placeholder="dd/mm/aaaa" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Tipo de Sangre:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <select id="Cho_Tsa" name="Cho_Tsa" class="form-control">
                                                    <option value="">Seleccione...</option>
                                                    <option value="O+">O+</option>
                                                    <option value="O-">O-</option>
                                                    <option value="A+">A+</option>
                                                    <option value="A-">A-</option>
                                                    <option value="B+">B+</option>
                                                    <option value="B-">B-</option>
                                                    <option value="AB+">AB+</option>
                                                    <option value="AB-">AB-</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="button-center">
                                <button type="button" class="btn btn-custom btn-exa-primary" onclick="guardarChofer();">
                                    <span class="glyphicon glyphicon-floppy-disk"></span> Guardar Operario
                                </button>
                                <button type="button" class="btn btn-custom btn-default" onclick="mostrarListado();">
                                    <span class="glyphicon glyphicon-remove"></span> Cancelar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Formulario Vehículo Individual -->
                    <div id="divFormTabVehiculo" style="display:none;">
                        <form id="formVehiculo" class="form-horizontal" onsubmit="return false;">
                            <fieldset class="exa-fieldset">
                                <legend>Datos Técnicos del Vehículo</legend>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Proveedor (Cédula/RUC):<span class="text-danger">*</span></label>
                                            <div class="col-sm-6">
                                                <div class="input-group">
                                                    <input id="Prv_Ced" name="Prv_Ced" type="text" class="form-control" placeholder="Cédula/RUC Proveedor" maxlength="13" onkeypress="return validar_numeric(event);" />
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default" type="button" onclick="buscarProveedor($('#Prv_Ced').val());">
                                                            <i class="fa fa-search"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                                <input id="Prv_Nom" name="Prv_Nom" type="text" class="form-control" placeholder="Nombre Proveedor" readonly style="margin-top: 5px;" />
                                                <input id="Prv_Cod" name="Prv_Cod" type="hidden" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Placa (Ej: ABC-1234):<span class="text-danger">*</span></label>
                                            <div class="col-sm-6">
                                                <input id="Veh_Pla" name="Veh_Pla" type="text" class="form-control" placeholder="Ej: ABC-1234" maxlength="8" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Marca:<span class="text-danger">*</span></label>
                                            <div class="col-sm-6">
                                                <input id="Veh_Mar" name="Veh_Mar" type="text" class="form-control" placeholder="Ingrese marca" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Color:<span class="text-danger">*</span></label>
                                            <div class="col-sm-6">
                                                <input id="Veh_Col" name="Veh_Col" type="text" class="form-control" placeholder="Ingrese color" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Tipo Vehículo:<span class="text-danger">*</span></label>
                                            <div class="col-sm-6">
                                                <select id="Veh_Tit" name="Veh_Tit" class="form-control">
                                                    <option value="">Seleccione...</option>
                                                    <option value="V" selected>VOLQUETA</option>
                                                    <option value="B">BUS</option>
                                                    <option value="C">CAMIONETA</option>
                                                    <option value="T">TRÁILER</option>
                                                    <option value="M">MIXER</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="button-center">
                                <button type="button" class="btn btn-custom btn-exa-primary" onclick="guardarVehiculo();">
                                    <span class="glyphicon glyphicon-floppy-disk"></span> Guardar Vehículo
                                </button>
                                <button type="button" class="btn btn-custom btn-default" onclick="mostrarListado();">
                                    <span class="glyphicon glyphicon-remove"></span> Cancelar
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Cargador Visual / Loader -->
    <div id="loader" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 9999; text-align: center; padding-top: 20%;">
        <div style="display: inline-block; padding: 25px 35px; background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
            <i class="fa fa-spinner fa-spin fa-3x fa-fw" style="color: #334a5f;"></i>
            <div style="margin-top: 15px; font-weight: bold; color: #334a5f; font-size: 14px;">Procesando solicitud...</div>
        </div>
    </div>

    <script type="text/javascript" src="../VALIDACIONES/man_val_alt_vehiculos_choferes.js?v=2"></script>
</BODY>

</HTML>