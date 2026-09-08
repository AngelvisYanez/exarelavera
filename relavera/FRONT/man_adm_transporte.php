<?php
/**
 * @abstract Registro de Choferes y Veh�culos (Manifiestos)
 * @author Sistema EXA
 * @version 1.0
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Mani;

$cliente_manifiesto = $obBD_con1->getRowConsulta(
    'manifiesto_usuario.selectWhere',
    array('where' => array('manifiesto_usuario.Usu_Cod' => $Ses_Usu_Cod)),
    $obBD_conexion
);
if (empty($cliente_manifiesto) || !is_array($cliente_manifiesto)) {
    $cliente_manifiesto = array('Cli_Cod' => '');
}

require_once(__DIR__ . '/../COMPONENTES/man_ajax_choferes_vehiculos.inc.php');

// Si se atendi� un AJAX, no renderizar HTML
if (
    isset($listChoferesGridAjax) || isset($listVehiculosGridAjax) ||
    isset($buscarPersonaCedulaAjax) || isset($saveChoferAjax) || isset($anularChoferAjax) ||
    isset($saveVehiculoAjax) || isset($anularVehiculoAjax) || isset($listPlantasSelectAjax) ||
    isset($listEmpresasTransporteGridAjax) || isset($saveEmpresaTransporteAjax) || isset($anularEmpresaTransporteAjax)
) {
    exit;
}

$plantas = $obBD_con1->getArrayConsulta(
    'manifiesto_plantas.selectWhere',
    array('where' => array('Pla_Est' => 'A')),
    $obBD_conexion
);
$obBD_con1->utf8_change_param($plantas);

$transportes = $obBD_con1->getArrayConsulta(
    'manifiesto_transporte.selectWhere',
    array('where' => array(
        'manifiesto_transporte.Emp_Cod' => $Ses_Emp_Cod,
        'manifiesto_transporte.Mat_Est' => 'A'
    )),
    $obBD_conexion,
    true
);
$obBD_con1->utf8_change_param($transportes);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo "Choferes y Vehículos - Manifiestos [EXA]"; ?></title>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
    <script>
        var Cli_Cod = '<?php echo isset($cliente_manifiesto["Cli_Cod"]) ? $cliente_manifiesto["Cli_Cod"] : ""; ?>';
    </script>
    <style>
        .nav-tabs-custom { margin-bottom: 20px; }
        .nav-tabs-custom > .nav-tabs { border-bottom: 3px solid #3c8dbc; }
        .nav-tabs-custom > .nav-tabs > li { margin-right: 5px; }
        .nav-tabs-custom > .nav-tabs > li > a {
            border-radius: 4px 4px 0 0;
            color: #444;
            font-weight: 600;
        }
        .nav-tabs-custom > .nav-tabs > li.active > a {
            border-top-color: #3c8dbc;
            color: #3c8dbc;
        }
        .icon-tab { margin-right: 6px; }
        .btn-toolbar { margin-bottom: 8px; }
        .tab-content { padding-top: 10px; }
    </style>
</head>
<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registro de Transporte, Choferes y Veh&iacute;culos</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#tabEmpresasTransporte" aria-controls="tabEmpresasTransporte" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-truck icon-tab"></i>Empresas Transporte
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tabChoferes" aria-controls="tabChoferes" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-user icon-tab"></i>Choferes
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tabVehiculos" aria-controls="tabVehiculos" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-road icon-tab"></i>Veh&iacute;culos
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab Empresas Transporte -->
                    <div role="tabpanel" class="tab-pane active" id="tabEmpresasTransporte">
                        <div class="btn-toolbar">
                            <button class="btn btn-success" onclick="abrirModalEmpresaTransporte();">
                                <i class="glyphicon glyphicon-plus"></i> Nueva Empresa
                            </button>
                            <button class="btn btn-default" onclick="actualizarGridEmpresasTransporte();">
                                <i class="glyphicon glyphicon-refresh"></i> Actualizar
                            </button>
                        </div>
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de B&uacute;squeda</legend>
                                    <form id="filtroEmpresasTransporteForm" class="form-horizontal normal">
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
                                            <label class="col-xs-2 control-label label-xs">B&uacute;squeda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" maxlength="50" placeholder="Ingrese b&uacute;squeda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridEmpresasTransporte(); }" />
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
                        <table id="gridEmpresasTransporte"></table>
                        <div id="gridEmpresasTransportePager"></div>
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
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de B&uacute;squeda</legend>
                                    <form id="filtroChoferesForm" class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radChofer1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer1">Nombre Chofer</label>
                                                <input id="radChofer2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer2">C&eacute;dula</label>
                                                <input id="radChofer3" name="op_opciones" type="radio" value="pn" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer3">Nombre Planta</label>
                                                <input id="radChofer4" name="op_opciones" type="radio" value="pl" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer4">Licencia Planta</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">B&uacute;squeda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" maxlength="50" placeholder="Ingrese b&uacute;squeda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridChoferes(); }" />
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
                        <table id="gridChoferes"></table>
                        <div id="gridChoferesPager"></div>
                    </div>

                    <!-- Tab Veh�culos -->
                    <div role="tabpanel" class="tab-pane" id="tabVehiculos">
                        <div class="btn-toolbar">
                            <button class="btn btn-success" onclick="abrirModalVehiculo();">
                                <i class="glyphicon glyphicon-plus"></i> Nuevo Veh&iacute;culo
                            </button>
                            <button class="btn btn-default" onclick="actualizarGridVehiculos();">
                                <i class="glyphicon glyphicon-refresh"></i> Actualizar
                            </button>
                        </div>
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de B&uacute;squeda</legend>
                                    <form id="filtroVehiculosForm" class="form-horizontal normal">
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
                                                <label for="radVehiculo4">C&eacute;dula/RUC Cliente</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">B&uacute;squeda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" maxlength="50" placeholder="Ingrese b&uacute;squeda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridVehiculos(); }" />
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
                        <table id="gridVehiculos"></table>
                        <div id="gridVehiculosPager"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Empresa Transporte -->
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
                <label class="col-xs-4 control-label label-xs">Tel&eacute;fono:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Tel" name="Mat_Tel" class="form-control input-xs" placeholder="Tel&eacute;fono" maxlength="10" onkeypress="return validar_numeric(event);">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Nro. Plan de Contingencia:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Pco" name="Mat_Pco" class="form-control input-xs" placeholder="N&uacute;mero Plan de Contingencia" maxlength="30">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Direcci&oacute;n:</label>
                <div class="col-xs-8">
                    <textarea id="Mat_Dir" name="Mat_Dir" class="form-control input-xs" rows="3" placeholder="Direcci&oacute;n"></textarea>
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px; padding: 10px; border-top: 1px solid #ddd;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarEmpresaTransporte();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#empresaTransporteDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Chofer -->
    <div id="choferDialog" title="Registrar Chofer" style="display: none;">
        <form id="choferForm" class="form-horizontal normal">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Seleccionar Planta:</label>
                <div class="col-xs-8">
                    <select id="Pla_Cod" name="Pla_Cod" class="form-control input-xs" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($plantas as $row) { ?>
                            <option value="<?php echo $row['Pla_Cod']; ?>"><?php echo htmlspecialchars($row['Pla_Nom']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <input type="hidden" id="Cho_Cod" name="Cho_Cod">
            <input type="hidden" id="Prs_Cod" name="Prs_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">C&eacute;dula:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs">
                        <input type="text" id="Cho_Ced" name="Cho_Ced" class="form-control input-xs" required placeholder="C&eacute;dula o RUC" maxlength="13" onchange="buscarPersonaPorCedula(this.value)" onkeypress="return validar_numeric(event);">
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
                        <input type="date" id="Cho_Cli" name="Cho_Cli" class="form-control input-xs" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Tel&eacute;fono:</label>
                <div class="col-xs-8">
                    <input type="text" id="Cho_Tel" name="Cho_Tel" class="form-control input-xs" required maxlength="20">
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
            <div class="form-group" style="display: none;">
                <label class="col-xs-4 control-label label-xs">Licencia AMB MAE:</label>
                <div class="col-xs-8">
                    <input type="text" id="Cho_Mae" name="Cho_Mae" class="form-control input-xs" maxlength="20" value="">
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarChofer();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#choferDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Veh�culo -->
    <div id="vehiculoDialog" title="Registrar Veh&iacute;culo" style="display: none;">
        <form id="vehiculoForm" class="form-horizontal normal">
            <input type="hidden" id="Veh_Cod" name="Veh_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Planta:</label>
                <div class="col-xs-8">
                    <select id="Pla_Cod" name="Pla_Cod" class="form-control input-xs" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($plantas as $row) { ?>
                            <option value="<?php echo $row['Pla_Cod']; ?>"><?php echo htmlspecialchars($row['Pla_Nom']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Empresa Transporte:</label>
                <div class="col-xs-8">
                    <select id="Mat_Cod" name="Mat_Cod" class="form-control input-xs" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($transportes as $row) { ?>
                            <option value="<?php echo $row['Mat_Cod']; ?>"><?php echo htmlspecialchars($row['Mat_Des']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Marca:</label>
                <div class="col-xs-8">
                    <input type="text" id="Veh_Mar" name="Veh_Mar" class="form-control input-xs" required>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Placa:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs">
                        <input type="text" id="Veh_Pla" name="Veh_Pla" class="form-control input-xs" required placeholder="Ej: ABC-1234" maxlength="8" onchange="validarPlacaVehiculo(this.value)" onkeyup="this.value = this.value.toUpperCase();">
                        <span class="input-group-addon validate"><i id="Veh_Pla_Est"></i></span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Color:</label>
                <div class="col-xs-8">
                    <input type="text" id="Veh_Col" name="Veh_Col" class="form-control input-xs" required>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Capacidad:</label>
                <div class="col-xs-5">
                    <div class="input-group input-group-xs">
                        <input name="Veh_Cap" id="Veh_Cap" type="number" class="form-control input-xs" required min="0" max="20000" step="0.01">
                        <span class="input-group-addon validate">Kg</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Tipo Veh&iacute;culo:</label>
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

    <div id="imprimirEmpresasTransporte" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE EMPRESAS DE TRANSPORTE', '<span class="subtitle">Listado de Empresas de Transporte</span>', $obBD_conexion); ?>
            <table id="tablaReporteEmpresasTransporte" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;table-layout:auto;font-size:12px;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>
    <div id="imprimirChoferes" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE CHOFERES', '<span class="subtitle">Listado de Choferes</span>', $obBD_conexion); ?>
            <table id="tablaReporteChoferes" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;table-layout:auto;font-size:12px;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>
    <div id="imprimirVehiculos" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE VEH�CULOS', '<span class="subtitle">Listado de Veh�culos</span>', $obBD_conexion); ?>
            <table id="tablaReporteVehiculos" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;table-layout:auto;font-size:12px;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>

    <script type="text/javascript" src="../VALIDACIONES/man_adm_choferes_vehiculos.js?x=2"></script>
</body>
</html>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>
