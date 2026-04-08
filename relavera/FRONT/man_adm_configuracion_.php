<?php
/**
 * @abstract Administración de Plantas, Choferes y Vehículos
 * @author Sistema EXA
 * @version 1.0
 * Fecha de creación: <?php echo date('d/m/Y'); ?>
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Mani;

$cliente_manifiesto = $obBD_con1->getRowConsulta('manifiesto_usuario.selectWhere', array('where' => array('manifiesto_usuario.Usu_Cod' => $Ses_Usu_Cod)), $obBD_conexion);

/* ==================== AJAX HANDLERS ==================== */

// Listar Plantas
if (isset($listPlantasGridAjax)) {
    $resultado = array('success' => true);
    $resultado['rows'] = $obBD_con1->getArrayConsulta('manifiesto_plantas.selectWhere', array_merge($_GET, array('where' => array('Cli_Cod' => $Cli_Cod))), $obBD_conexion, true);
    $obBD_con1->echoJson($resultado);
}

// Guardar Planta
if (isset($savePlantaAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        $datosPlanta = array(
            'Cli_Cod' => $Cli_Cod,
            'Ciu_Cod' => $Ciu_Cod,
            'Pla_Nom' => $Pla_Nom,
            'Pla_Lic' => $Pla_Lic,
            'Pla_Dir' => $Pla_Dir,
            'Pla_Est' => 'A'
        );
        if (!empty($Pla_Cod)) {
            $datosPlanta['where'] = array('Pla_Cod' => $Pla_Cod);
            $obBD_con1->operacionobBD('manifiesto_plantas.update', $datosPlanta, $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD('manifiesto_plantas.insert', $datosPlanta, $obBD_conexion);
            $resp['Pla_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Anular Planta
if (isset($anularPlantaAjax)) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('manifiesto_plantas.update', array('Pla_Est' => 'I', 'where' => array('Pla_Cod' => $Pla_Cod)), $obBD_conexion);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Listar Choferes
if (isset($listChoferesGridAjax)) {
    $resultado = array('success' => true);
    $resultado['rows'] = $obBD_con1->getArrayConsulta('manifiesto_chofer.selectWhere', array_merge($_GET, array('where' => array('manifiesto_chofer.Cli_Cod' => $Cli_Cod))), $obBD_conexion, true);
    $obBD_con1->echoJson($resultado);
}

// Buscar persona por cédula
if (isset($buscarPersonaCedulaAjax)) {
    $resp = array('success' => true, 'existe' => false);
    $prsAux = $Prs_Ced;
    $longitud = strlen($prsAux);
    if ($longitud * 1 === 13) {
        $prsAux = substr($prsAux, 0, -3);
    }
    $persona = $obBD_con1->getRowConsulta('persona.selectWhere', array('where' => array('Prs_Ced' => $prsAux)), $obBD_conexion);
    if (!empty($persona)) {
        $resp['existe'] = true;
        $resp['persona'] = $persona;
    }
    $obBD_con1->echoJson($resp);
}

// Guardar Chofer
if (isset($saveChoferAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        if (!empty($Prs_Cod)) {
            $Prs_Cod_New = $Prs_Cod;
        } else {
            $persona = $obBD_con1->getRowConsulta('persona.selectWhere', array('where' => array('Prs_Ced' => $Cho_Ced)), $obBD_conexion);
            if (empty($persona)) {
                $datosPersona = array('Prs_Ced' => $Cho_Ced, 'Prs_Nom' => $Prs_Nom, 'Prs_Ape' => $Prs_Ape, 'Prs_Tel' => $Cho_Tel);
                $obBD_con1->operacionobBD('persona.insert', $datosPersona, $obBD_conexion);
                $Prs_Cod_New = $obBD_con1->insercionid($obBD_conexion);
            } else {
                $Prs_Cod_New = $persona['Prs_Cod'];
            }
        }
        $datosChofer = array(
            'Prs_Cod' => $Prs_Cod_New,
            'Emp_Cod' => $Ses_Emp_Cod,
            'Cho_Tli' => $Cho_Tli,
            'Cho_Cli' => $Cho_Cli,
            'Cho_Tel' => $Cho_Tel,
            'Cho_Tsa' => $Cho_Tsa,
            'Cho_Mae' => isset($Cho_Mae) ? $Cho_Mae : ''
        );
        if (!empty($Cho_Cod)) {
            $datosChofer['where'] = array('Cho_Cod' => $Cho_Cod);
            $obBD_con1->operacionobBD('chofer.update', $datosChofer, $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD('chofer.insert', $datosChofer, $obBD_conexion);
            $resp['Cho_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
            $obBD_con1->operacionobBD('manifiesto_chofer.insert', array('Cho_Cod' => $resp['Cho_Cod_New'], 'Cli_Cod' => $Cli_Cod), $obBD_conexion);
        }
        $resp['nombre'] = $Prs_Nom . ' ' . $Prs_Ape;
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Anular Chofer
if (isset($anularChoferAjax)) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('chofer.update', array('Cho_Est' => 'I', 'where' => array('Cho_Cod' => $Cho_Cod)), $obBD_conexion);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Listar Vehículos
if (isset($listVehiculosGridAjax)) {
    $resultado = array('success' => true);
    $resultado['rows'] = $obBD_con1->getArrayConsulta('manifiesto_vehiculo.selectWhere', array_merge($_GET, array('where' => array('manifiesto_vehiculo.Cli_Cod' => $Cli_Cod))), $obBD_conexion, true);
    $obBD_con1->echoJson($resultado);
}

// Listar Transportes
if (isset($listTransportesAjax)) {
    $resp = array('success' => true);
    $resp['transportes'] = $obBD_con1->getArrayConsulta('manifiesto_transporte.selectWhere', array('where' => array('manifiesto_transporte.Cli_Cod' => $Cli_Cod)), $obBD_conexion, true);
    $obBD_con1->echoJson($resp);
}

// Guardar Vehículo
if (isset($saveVehiculoAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        $datos = array(
            'Veh_Mar' => $Veh_Mar,
            'Veh_Pla' => $Veh_Pla,
            'Veh_Col' => $Veh_Col,
            'Veh_Cap' => $Veh_Cap,
            'Veh_Tit' => $Veh_Tit,
            'Emp_Cod' => $Ses_Emp_Cod,
            'Veh_Tip' => 'VM',
            'Mat_Cod' => $Mat_Cod
        );
        if (!empty($Veh_Cod)) {
            $datos['where'] = array('Veh_Cod' => $Veh_Cod);
            $obBD_con1->operacionobBD('vehiculo.update', $datos, $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD('vehiculo.insert', $datos, $obBD_conexion);
            $resp['Veh_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
            $obBD_con1->operacionobBD('manifiesto_vehiculo.insert', array('Veh_Cod' => $resp['Veh_Cod_New'], 'Cli_Cod' => $Cli_Cod), $obBD_conexion);
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Anular Vehículo
if (isset($anularVehiculoAjax)) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('vehiculo.update', array('Veh_Est' => 'I', 'where' => array('Veh_Cod' => $Veh_Cod)), $obBD_conexion);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Obtener ciudades
$ciudades = $obBD_con1->getArrayConsulta('ciudad.selectWhere', array('setWhere' => array('getProvincia', 'getPais')), $obBD_conexion, true);
$obBD_con1->utf8_change_param($ciudades);

// Obtener transportes del cliente
$transportes = array();
if (!empty($cliente_manifiesto['Cli_Cod'])) {
    $transportes = $obBD_con1->getArrayConsulta('manifiesto_transporte.selectWhere', array('where' => array('manifiesto_transporte.Cli_Cod' => $cliente_manifiesto['Cli_Cod'])), $obBD_conexion, true);
    $obBD_con1->utf8_change_param($transportes);
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?php echo "Administración - Configuración [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script src="../../framework/jquery/jquery.mask/jquery.mask.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script>
        var Cli_Cod = '<?php echo $cliente_manifiesto['Cli_Cod']; ?>';
    </script>
    <style>
        .nav-tabs-custom {
            margin-bottom: 20px;
        }
        .nav-tabs-custom > .nav-tabs {
            border-bottom: 3px solid #3c8dbc;
        }
        .nav-tabs-custom > .nav-tabs > li {
            margin-right: 5px;
        }
        .nav-tabs-custom > .nav-tabs > li > a {
            border-radius: 5px 5px 0 0;
            color: #444;
            background: #f4f4f4;
            border: 1px solid #ddd;
            border-bottom: none;
            padding: 10px 20px;
            font-weight: bold;
        }
        .nav-tabs-custom > .nav-tabs > li.active > a {
            background: #3c8dbc;
            color: white;
            border-color: #3c8dbc;
        }
        .nav-tabs-custom > .nav-tabs > li > a:hover {
            background: #e9ecef;
        }
        .nav-tabs-custom > .nav-tabs > li.active > a:hover {
            background: #367fa9;
            color: white;
        }
        .tab-content {
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .tab-pane {
            min-height: 400px;
        }
        .btn-toolbar {
            margin-bottom: 15px;
        }
        .panel-config {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .icon-tab {
            margin-right: 8px;
            font-size: 16px;
        }
        .info-cliente {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-cliente h4 {
            margin: 0 0 10px 0;
        }
        .info-cliente p {
            margin: 0;
            opacity: 0.9;
        }
    </style>
</HEAD>
<BODY>
    <div class="panel panel-main panel-config">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Administración de Configuración</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
            <!-- Información del Cliente -->
            <div class="info-cliente">
                <h4><i class="glyphicon glyphicon-user"></i> Cliente Asociado</h4>
                <p><strong>Código:</strong> <?php echo $cliente_manifiesto['Cli_Cod']; ?> | 
                   <strong>Nombre:</strong> <?php echo isset($cliente_manifiesto['nombre']) ? $cliente_manifiesto['nombre'] : 'N/A'; ?></p>
            </div>

            <!-- Pestañas -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#tabPlantas" aria-controls="tabPlantas" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-home icon-tab"></i>Plantas
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tabChoferes" aria-controls="tabChoferes" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-user icon-tab"></i>Choferes
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tabVehiculos" aria-controls="tabVehiculos" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-road icon-tab"></i>Vehículos
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab Plantas -->
                    <div role="tabpanel" class="tab-pane active" id="tabPlantas">
                        <div class="btn-toolbar">
                            <button class="btn btn-success" onclick="abrirModalPlanta();">
                                <i class="glyphicon glyphicon-plus"></i> Nueva Planta
                            </button>
                            <button class="btn btn-default" onclick="actualizarGridPlantas();">
                                <i class="glyphicon glyphicon-refresh"></i> Actualizar
                            </button>
                        </div>
                        <table id="gridPlantas"></table>
                        <div id="gridPlantasPager"></div>
                    </div>

                    <!-- Tab Choferes -->
                    <div role="tabpanel" class="tab-pane" id="tabChoferes">
                        <div class="btn-toolbar">
                            <button class="btn btn-success" onclick="abrirModalChofer();">
                                <i class="glyphicon glyphicon-plus"></i> Nuevo Chofer
                            </button>
                            <button class="btn btn-default" onclick="actualizarGridChoferes();">
                                <i class="glyphicon glyphicon-refresh"></i> Actualizar
                            </button>
                        </div>
                        <table id="gridChoferes"></table>
                        <div id="gridChoferesPager"></div>
                    </div>

                    <!-- Tab Vehículos -->
                    <div role="tabpanel" class="tab-pane" id="tabVehiculos">
                        <div class="btn-toolbar">
                            <button class="btn btn-success" onclick="abrirModalVehiculo();">
                                <i class="glyphicon glyphicon-plus"></i> Nuevo Vehículo
                            </button>
                            <button class="btn btn-default" onclick="actualizarGridVehiculos();">
                                <i class="glyphicon glyphicon-refresh"></i> Actualizar
                            </button>
                        </div>
                        <table id="gridVehiculos"></table>
                        <div id="gridVehiculosPager"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Planta -->
    <div id="plantaDialog" title="Registrar Planta" style="display: none;">
        <form id="plantaForm" class="form-horizontal normal">
            <input type="hidden" id="Pla_Cod" name="Pla_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Nombre Planta:</label>
                <div class="col-xs-8">
                    <input type="text" id="Pla_Nom" name="Pla_Nom" class="form-control input-xs" required placeholder="Nombre de la planta" maxlength="50">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Ciudad:</label>
                <div class="col-xs-8">
                    <select id="Ciu_Cod" name="Ciu_Cod" class="form-control input-xs chosen-select" data-placeholder="Seleccione ciudad..." required>
                        <option value=""></option>
                        <?php foreach ($ciudades as $row) { ?>
                            <option value="<?php echo $row['Ciu_Cod']; ?>" data-prov="<?php echo $row['Pro_Nom']; ?>"><?php echo $row['Ciu_Des']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">N&uacute;mero Licencia:</label>
                <div class="col-xs-8">
                    <input type="text" id="Pla_Lic" name="Pla_Lic" class="form-control input-xs" required placeholder="Número de licencia" maxlength="20">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Dirección:</label>
                <div class="col-xs-8">
                    <input type="text" id="Pla_Dir" name="Pla_Dir" class="form-control input-xs" required placeholder="Dirección de la planta" maxlength="100">
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarPlanta();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#plantaDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Chofer -->
    <div id="choferDialog" title="Registrar Chofer" style="display: none;">
        <form id="choferForm" class="form-horizontal normal">
            <input type="hidden" id="Cho_Cod" name="Cho_Cod">
            <input type="hidden" id="Prs_Cod" name="Prs_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Cédula:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs">
                        <input type="text" id="Cho_Ced" name="Cho_Ced" class="form-control input-xs" required placeholder="Número de cédula" maxlength="13" onchange="buscarPersonaPorCedula(this.value)">
                        <span class="input-group-addon validate"><i id="Cho_Ced_Est"></i></span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Nombres:</label>
                <div class="col-xs-8">
                    <input type="text" id="Prs_Nom" name="Prs_Nom" class="form-control input-xs" required placeholder="Nombre del chofer">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Apellidos:</label>
                <div class="col-xs-8">
                    <input type="text" id="Prs_Ape" name="Prs_Ape" class="form-control input-xs" required placeholder="Apellidos del chofer">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Tipo Licencia:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs">
                        <select id="Cho_Tli" name="Cho_Tli" class="form-control input-xs" required>
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
                        <span class="input-group-addon bold alert-info">Caducidad:</span>
                        <input type="text" id="Cho_Cli" name="Cho_Cli" class="form-control input-xs datepicker" required placeholder="Fecha caducidad">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Teléfono:</label>
                <div class="col-xs-8">
                    <input type="text" id="Cho_Tel" name="Cho_Tel" class="form-control input-xs" required placeholder="Teléfono" maxlength="20">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Tipo de Sangre:</label>
                <div class="col-xs-8">
                    <select id="Cho_Tsa" name="Cho_Tsa" class="form-control input-xs" required>
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
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Licencia AMB MAE:</label>
                <div class="col-xs-8">
                    <input type="text" id="Cho_Mae" name="Cho_Mae" class="form-control input-xs" placeholder="Licencia ambiental MAE" maxlength="20">
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarChofer();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#choferDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Vehículo -->
    <div id="vehiculoDialog" title="Registrar Vehículo" style="display: none;">
        <form id="vehiculoForm" class="form-horizontal normal">
            <input type="hidden" id="Veh_Cod" name="Veh_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Empresa Transporte:</label>
                <div class="col-xs-8">
                    <select id="Mat_Cod" name="Mat_Cod" class="form-control input-xs" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($transportes as $row) { ?>
                            <option value="<?php echo $row['Mat_Cod']; ?>"><?php echo $row['Mat_Des']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Marca:</label>
                <div class="col-xs-8">
                    <input type="text" id="Veh_Mar" name="Veh_Mar" class="form-control input-xs" required placeholder="Ingrese marca">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Placa:</label>
                <div class="col-xs-8">
                    <input type="text" id="Veh_Pla" name="Veh_Pla" class="form-control input-xs" required placeholder="Ingrese numero placa">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Color:</label>
                <div class="col-xs-8">
                    <input type="text" id="Veh_Col" name="Veh_Col" class="form-control input-xs" required placeholder="Ingrese color">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Capacidad:</label>
                <div class="col-xs-5">
                    <div class="input-group input-group-xs">
                        <input name="Veh_Cap" id="Veh_Cap" type="text" class="form-control input-xs" required placeholder="Capacidad">
                        <span class="input-group-addon validate">Kg</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Tipo Vehículo:</label>
                <div class="col-xs-8">
                    <select id="Veh_Tit" name="Veh_Tit" class="form-control input-xs" required>
                        <option value="V">VOLQUETA</option>
                        <option value="D">TIPO DUMPER</option>
                        <option value="C">CAMION</option>
                    </select>
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarVehiculo();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#vehiculoDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script>
    $(function() {
        // Inicializar diálogos
        $("#plantaDialog").createDialog({ width: 500, height: 280, icon: 'home' });
        $("#choferDialog").createDialog({ width: 550, height: 420, icon: 'user' });
        $("#vehiculoDialog").createDialog({ width: 450, height: 350, icon: 'road' });
        
        // Inicializar datepickers
        $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
        
        // Inicializar Chosen
        $('.chosen-select').chosen({ width: '100%', no_results_text: 'No se encontró: ' });
        
        // Crear grids
        createGridPlantas();
        createGridChoferes();
        createGridVehiculos();
        
        // Evento de cambio de tab
        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            $(window).trigger('resize');
        });
    });

    // ==================== GRID PLANTAS ====================
    function createGridPlantas() {
        $('#gridPlantas').createGrid({
            caption: 'Listado de Plantas',
            url: '' ,
            height: 250,
            colModel: [
                { label: 'Código', name: 'Pla_Cod', key: true, width: 50, align: "center" },
                { label: 'Nombre', name: 'Pla_Nom', width: 150 },
                { label: 'Ciudad', name: 'Ciu_Des', width: 100 },
                { label: 'Licencia', name: 'Pla_Lic', width: 80, align: "center" },
                { label: 'Dirección', name: 'Pla_Dir', width: 150 },
                { label: 'Estado', name: 'Pla_Est', width: 50, align: "center", formatter: function(v) { return v === 'A' ? '<span class="label label-success">Activo</span>' : '<span class="label label-danger">Inactivo</span>'; } },
                {
                    label: '<center><i class="glyphicon glyphicon-cog"></i></center>',
                    name: 'acciones', width: 60, align: 'center',
                    formatter: function(cellvalue, options, o) {
                        return $.getGridButton('editarPlanta', o, 'Editar', 'pencil', '', 'success') + '&nbsp;' +
                               $.getGridButton('anularPlanta', o.Pla_Cod, 'Anular', 'trash', '', 'danger');
                    }
                }
            ],
            rowNum: 20, viewrecords: true
        }, true, '#gridPlantasPager', { refresh: true, view: false });
    }

    function actualizarGridPlantas() {
        $.post('', { listPlantasGridAjax: true, Cli_Cod: Cli_Cod }, function(r) {
                if (r.success) {
                    $('#gridPlantas').jqGrid('setGridParam', { data: r.rows }).trigger('reloadGrid');
                } else {
                    $.alert(r.message || 'Error en la consulta');
                }
        }, 'json');
        
    }

    function abrirModalPlanta() {
        $('#plantaForm')[0].reset();
        $('#Pla_Cod').val('');
        $('#Ciu_Cod').val('').trigger('chosen:updated');
        $('#plantaDialog').dialog('open');
    }

    function editarPlanta(o) {
        $('#plantaForm').setData(o);
        $('#Ciu_Cod').val(o.Ciu_Cod).trigger('chosen:updated');
        $('#plantaDialog').dialog('open');
    }

    function guardarPlanta() {
        let data = $('#plantaForm').getData();
        data.savePlantaAjax = true;
        data.Cli_Cod = Cli_Cod;
        $.createDialogConfirm('¿Está seguro que desea guardar los datos?', data, function(d) {
            $.saveDataJson('', d, function(r) {
                if (r.success) {
                    $('#plantaDialog').dialog('close');
                    actualizarGridPlantas();
                    $.alert('Planta guardada correctamente.');
                } else {
                    $.alert(r.message || 'Error al guardar');
                }
            });
        });
    }

    function anularPlanta(Pla_Cod) {
        $.createDialogConfirm('¿Está seguro que desea anular esta planta?', { Pla_Cod: Pla_Cod }, function(d) {
            $.post('', { anularPlantaAjax: true, Pla_Cod: d.Pla_Cod }, function(r) {
                if (r.success) {
                    actualizarGridPlantas();
                    $.alert('Planta anulada correctamente.');
                } else {
                    $.alert(r.message || 'Error al anular');
                }
            }, 'json');
        });
    }

    // ==================== GRID CHOFERES ====================
    function createGridChoferes() {
        $('#gridChoferes').createGrid({
            caption: 'Listado de Choferes',
            url: '',
            height: 250,
            colModel: [
                { label: 'Código', name: 'Cho_Cod', key: true, width: 50, align: "center" },
                { label: 'Cédula', name: 'Prs_Ced', width: 80, align: "center" },
                { label: 'Nombre', name: 'nombre', width: 150 },
                { label: 'Licencia', name: 'Cho_Tli', width: 60, align: "center" },
                { label: 'Caducidad', name: 'Cho_Cli', width: 80, align: "center" },
                { label: 'Teléfono', name: 'Cho_Tel', width: 80, align: "center" },
                { label: 'Sangre', name: 'Cho_Tsa', width: 50, align: "center" },
                {
                    label: '<center><i class="glyphicon glyphicon-cog"></i></center>',
                    name: 'acciones', width: 60, align: 'center',
                    formatter: function(cellvalue, options, o) {
                        return $.getGridButton('editarChofer', o, 'Editar', 'pencil', '', 'success') + '&nbsp;' +
                               $.getGridButton('anularChoferGrid', o.Cho_Cod, 'Anular', 'trash', '', 'danger');
                    }
                }
            ],
            rowNum: 20, viewrecords: true
        }, true, '#gridChoferesPager', { refresh: true, view: false });
    }

    function actualizarGridChoferes() {
        $.post('', { listChoferesGridAjax: true, Cli_Cod: Cli_Cod }, function(r) {
                if (r.success) {
                    $('#gridChoferes').jqGrid('setGridParam', { page: 1 }).trigger('reloadGrid');
                } else {
                    $.alert(r.message || 'Error en la consulta');
                }
        }, 'json');
        
    }

    function abrirModalChofer() {
        $('#choferForm')[0].reset();
        $('#Cho_Cod').val('');
        $('#Prs_Cod').val('');
        $('#Prs_Nom, #Prs_Ape').prop('readonly', false).css('background-color', '');
        $("#Cho_Ced_Est").removeClass().css("color", "");
        $('#choferDialog').dialog('open');
    }

    function editarChofer(o) {
        $('#choferForm').setData(o);
        $('#choferDialog').dialog('open');
    }

    function buscarPersonaPorCedula(cedula) {
        if ($.isEmpty(cedula) || cedula.length < 10) {
            $("#Cho_Ced_Est").removeClass().addClass("fa fa-close").css("color", "orange");
            return;
        }
        $("#Cho_Ced_Est").removeClass().addClass("fa fa-spinner fa-spin").css("color", "#337ab7");
        $.post("", { buscarPersonaCedulaAjax: true, Prs_Ced: cedula }, function(r) {
            if (r.existe) {
                $('#Prs_Cod').val(r.persona.Prs_Cod);
                $('#Prs_Nom').val(r.persona.Prs_Nom).prop('readonly', true).css('background-color', '#eee');
                $('#Prs_Ape').val(r.persona.Prs_Ape).prop('readonly', true).css('background-color', '#eee');
                if (r.persona.Prs_Tel) $('#Cho_Tel').val(r.persona.Prs_Tel);
                $("#Cho_Ced_Est").removeClass().addClass("fa fa-check").css("color", "green");
            } else {
                $('#Prs_Cod').val('');
                $('#Prs_Nom, #Prs_Ape').val('').prop('readonly', false).css('background-color', '');
                $("#Cho_Ced_Est").removeClass().addClass("fa fa-check").css("color", "#337ab7");
            }
        }, 'json');
    }

    function guardarChofer() {
        let data = $('#choferForm').getData();
        data.saveChoferAjax = true;
        data.Cli_Cod = Cli_Cod;
        $.createDialogConfirm('¿Está seguro que desea guardar los datos?', data, function(d) {
            $.saveDataJson('', d, function(r) {
                if (r.success) {
                    $('#choferDialog').dialog('close');
                    actualizarGridChoferes();
                    $.alert('Chofer guardado correctamente.');
                } else {
                    $.alert(r.message || 'Error al guardar');
                }
            });
        });
    }

    function anularChoferGrid(Cho_Cod) {
        $.createDialogConfirm('¿Está seguro que desea anular este chofer?', { Cho_Cod: Cho_Cod }, function(d) {
            $.post('', { anularChoferAjax: true, Cho_Cod: d.Cho_Cod }, function(r) {
                if (r.success) {
                    actualizarGridChoferes();
                    $.alert('Chofer anulado correctamente.');
                } else {
                    $.alert(r.message || 'Error al anular');
                }
            }, 'json');
        });
    }

    // ==================== GRID VEHICULOS ====================
    function createGridVehiculos() {
        $('#gridVehiculos').createGrid({
            caption: 'Listado de Vehículos',
            url: '',
            height: 250,
            colModel: [
                { label: 'Código', name: 'Veh_Cod', key: true, width: 50, align: "center" },
                { label: 'Placa', name: 'Veh_Pla', width: 80, align: "center" },
                { label: 'Marca', name: 'Veh_Mar', width: 100 },
                { label: 'Color', name: 'Veh_Col', width: 70, align: "center" },
                { label: 'Capacidad (Kg)', name: 'Veh_Cap', width: 80, align: "right" },
                { label: 'Tipo', name: 'Veh_Tit', width: 80, align: "center", formatter: function(v) {
                    let tipos = { 'V': 'Volqueta', 'D': 'Dumper', 'C': 'Camión' };
                    return tipos[v] || v;
                }},
                { label: 'Transporte', name: 'Mat_Des', width: 120 },
                {
                    label: '<center><i class="glyphicon glyphicon-cog"></i></center>',
                    name: 'acciones', width: 60, align: 'center',
                    formatter: function(cellvalue, options, o) {
                        return $.getGridButton('editarVehiculo', o, 'Editar', 'pencil', '', 'success') + '&nbsp;' +
                               $.getGridButton('anularVehiculoGrid', o.Veh_Cod, 'Anular', 'trash', '', 'danger');
                    }
                }
            ],
            rowNum: 20, viewrecords: true
        }, true, '#gridVehiculosPager', { refresh: true, view: false });
    }

    function actualizarGridVehiculos() {
        $.post('', { listVehiculosGridAjax: true, Cli_Cod: Cli_Cod }, function(r) {
                if (r.success) {
                    $('#gridVehiculos').jqGrid('setGridParam', { page: 1 }).trigger('reloadGrid');
                } else {
                    $.alert(r.message || 'Error en la consulta');
                }
        }, 'json');
        
    }

    function abrirModalVehiculo() {
        $('#vehiculoForm')[0].reset();
        $('#Veh_Cod').val('');
        $('#vehiculoDialog').dialog('open');
    }

    function editarVehiculo(o) {
        $('#vehiculoForm').setData(o);
        $('#vehiculoDialog').dialog('open');
    }

    function guardarVehiculo() {
        let data = $('#vehiculoForm').getData();
        data.saveVehiculoAjax = true;
        data.Cli_Cod = Cli_Cod;
        $.createDialogConfirm('¿Está seguro que desea guardar los datos?', data, function(d) {
            $.saveDataJson('', d, function(r) {
                if (r.success) {
                    $('#vehiculoDialog').dialog('close');
                    actualizarGridVehiculos();
                    $.alert('Vehículo guardado correctamente.');
                } else {
                    $.alert(r.message || 'Error al guardar');
                }
            });
        });
    }

    function anularVehiculoGrid(Veh_Cod) {
        $.createDialogConfirm('¿Está seguro que desea anular este vehículo?', { Veh_Cod: Veh_Cod }, function(d) {
            $.post('', { anularVehiculoAjax: true, Veh_Cod: d.Veh_Cod }, function(r) {
                if (r.success) {
                    actualizarGridVehiculos();
                    $.alert('Vehículo anulado correctamente.');
                } else {
                    $.alert(r.message || 'Error al anular');
                }
            }, 'json');
        });
    }
    </script>
</BODY>
</HTML>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>

