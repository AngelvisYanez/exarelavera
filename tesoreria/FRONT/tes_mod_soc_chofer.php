<?php
/**
 * Permite modificar un chofer ya sea Nacional(Cedula o Ruc) o Extranjero(Pasaporte)
 * 
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualizaci�n:	2025-09-19
 * @author Wilson Belduma
 * 
 * @package tesoreria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cliente.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
$obBD_conexion = new Class_Log_Conexion_Cli($Ses_Dat_Dis);
$obBD_con1 =  new Class_Log_Datos_Cli;
/*Secci�n para listar los clientes registrados dentro de la empresa*/
if (isset($choferAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(35, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(35, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

/* ver si exite un cliente */
if (isset($searchChofer)) {
    $existe = $obBD_con1->getRowConsulta(18, $Ext_Ruc . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $responce = ($existe['cant'] > 0) ? $responce['cant'] = true : $responce['cant'] = false;
    $obBD_con1->echoJson($responce);
}

/* guarda un nuevo cliente */
if (isset($guardarChofer)) {
    $data = $_POST;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(36, $data, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
        $responce['message'] = "No se ha logrado realizar la Transaccion";
    }
    echo json_encode($responce);
    exit();
}


?>
<!DOCTYPE html>
<HTML>
<head>
    <TITLE><?Php echo "Socio Modificar [EXA]"; ?></TITLE>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script language="javascript" src="../../framework/plugins/cedulaRuc.js"></script>
</head>
<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Modificar Socios transportistas</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="lista" class="row">
                <div class="col-md-12">
                    <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:$('#Lis_Cli').Search('#frm_bus','choferAjax');">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">B&uacute;squeda de Clientes</legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                <div class="col-sm-5 radioset">
                                    <input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)" /><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                                    <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" /><label for="rad_ba2">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                <div class="col-sm-5">
                                    <div class="input-group">
                                        <input type="text" id="search" name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                    <div style="min-height:300px;">
                        <table id="Lis_Cli"></table>
                        <div id="Pag_Cli"></div>
                    </div>
                </div>
            </div>
            <div id="modificar" class="row" style="display: none;">
                <div class="col-sm-3"></div>
                <div class="col-md-6 col-sm-8">
                    <form class="form-horizontal normal" id="formCliente" name="formCliente" action="javascript:guardarChofer();">
                        <input name="Ext_Cod" id="Ext_Cod" type="text" class="hidden" />
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos Socio</legend>
                            <div class="form-group Titulos2">
                                <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
                                    <hr />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Residencia:</label>
                                <div class="col-xs-5">
                                    <div class="btn-group" data-toggle="buttons">
                                        <label id="lb_ec" class="btn btn-success btn-xs">
                                            <input id="radioec" name="tipo" value="Ec" type="radio" checked=""><i id="spanec" class="fa fa-check"></i> Ecuatoriano
                                        </label>
                                        <label id="lb_ex" class="btn btn-default btn-xs">
                                            <input id="radioex" name="tipo" value="Ex" type="radio"><i id="spanex" class="fa fa-check" style="display: none;"></i> Extranjero
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                                <div class="col-xs-5">
                                    <div class="input-group input-group-xs">
                                        <input id="Ext_Ruc" name="Ext_Ruc" type="text" class="form-control input-xs" onchange="validar(1)" required="" />
                                        <span class="input-group-addon validate"><i></i></span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Documento:</label>
                                <div class="col-xs-5">
                                    <?php $rs_identi = $obBD_con1->getArrayConsulta(16, '', $obBD_conexion); ?>
                                    <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($rs_identi as $row) {
                                            echo "<option value='$row[Ide_Cod]' data-tipo='$row[Tipo]'>$row[Ide_Des]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required"><span class='natural'>Nombres:</span><span class='juridico' style="display: none;">Razón Social:</span></label>
                                <div class="col-xs-9"><input name="Ext_Nom" id="Ext_Nom" type="text" class="form-control input-xs" required="" /></div>
                            </div>
                           
                        </fieldset>
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de Ubicación</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                                <div class="col-xs-6">
                                    <?php $rs_ciudad = $obBD_con1->getArrayConsulta(15, '', $obBD_conexion); ?>
                                    <select id="Ext_Ciu" name="Ext_Ciu" class="form-control input-xs" data-placeholder="Seleccione una ciudad" required="">
                                        <option value=""></option>
                                        <?php foreach ($rs_ciudad as $row) {
                                            echo "<option value='{$row['Ciu_Des']}' data-prov='" . utf8_encode($row['Pro_Nom']) . "' data-pais='" . utf8_encode($row['Pas_Nom']) . "'>" . utf8_encode($row['Ciu_Des']) . "</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Dirección:</label>
                                <div class="col-xs-9"><input name="Ext_Dir" id="Ext_Dir" type="text" class="form-control input-xs" required="" /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Placa vehiculo:</label>
                                <div class="col-xs-9"><input name="Ext_Placa" id="Ext_Placa" type="text" class="form-control input-xs" multiple /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Teléfono(s):</label>
                                <div class="col-xs-9">
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon bold alert-info"><i class="fa fa-phone"></i></span>
                                        <input name="Ext_Telf" id="Ext_Telf" type="text" class="form-control input-xs" onkeypress="return validar_numeric(event);" />
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Mail:</label>
                                <div class="col-xs-3"><input name="Ext_Email" type="Ext_Email" class="form-control input-xs" multiple /></div>
                                <label class="col-xs-3 control-label label-xs">Regimen:</label>
                                <div class="col-xs-3">
                                    <select name="Ext_Reg_Sri" id="Ext_Reg_Sri" class="form-control input-xs">
                                        <option value="N" selected="">Regimen General</option>
                                        <option value="NP">Rimpe Negocio Popular</option>
                                        <option value="EM">Rimpe Emprendedor</option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>
                        <div class="center">
                            <button type="button" onclick="$('#modificar').moveComp('#lista').updateGridsSizes();" class="btn btn-inverse fileinput-button btn-sm"><span class="glyphicon glyphicon-arrow-left"></span> Atr&aacute;s</button>
                            <button type="button" onclick="$(this.form).formSubmit();" class="btn btn-sm btn-primary no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(function() {
            $("#Lis_Cli").createGrid({
                postData: $("#frm_bus").getData("choferAjax"),
                height: 295,
                colModel: [
                    { label: 'C&oacute;d. Int.', name: 'Ext_Cod', width: 50,  align: "left" },
                    { label: 'C&eacute;dula/R.U.C.',  name: 'Ext_Ruc', width: 50, align: "left"  },
                    { label: 'Cliente', name: 'Ext_Nom', width: 150, align: "left"},
                    { label: 'Correo',   name: 'Ext_Email', width: 150, align: "left" },
                    { label: '&nbsp;', name: 'act1',  width: 30,  align: 'center', viewable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            return $.getGridButton(cargarCliente, rowObject, 'Editar Cliente');
                        }
                    }
                ]
            }, false, "#Pag_Cli");

            $('#Ext_Ciu').createChosen('input-xs', {
                tabIndex: 6,
                width: '100%',
                template: function(t, d) {
                    return '<div class="over"><b>' + t + '</b></div><div class="over desc" style="font-size:11px;"><b>Provincia:</b> ' + d['prov'] + ' <b>Pa&iacute;s:</b> ' + d['pais'] + '</div>';
                }
            });

            $("#radioec").change(function() {
                habilitar('ec', 1);
                $('#Ext_Ruc').attr('onchange', 'validar(1)');
                $('#lb_ec').attr('class', 'btn btn-success btn-xs');
                $('#lb_ex').attr('class', 'btn btn-default btn-xs');
                $('#spanec').show();
                $('#spanex').hide();
            });

            $("#radioex").change(function() {
                habilitar('ex', 1);
                $('#Ext_Ruc').attr('onchange', 'validar(2)');
                $('#lb_ex').attr('class', 'btn btn-success btn-xs');
                $('#lb_ec').attr('class', 'btn btn-default btn-xs');
                $('#spanex').show();
                $('#spanec').hide();
            });

            $('#Ide_Cod').change(function() {
                if (this.value * 1 === 1) {
                    $('#Ext_Ruc').attr('onchange', 'validar(2)');
                } else {
                    $('#Ext_Ruc').attr('onchange', 'validar(3)');
                }
                habilitar('ex', this.value);
            });
        });

        var err = 0;

        function validar(op) {
            var cedula = $('#Ext_Ruc').val();
            switch (op) {
                case 1:
                    if (validaNoIdentif(cedula)['success']) {
                        err = 0;
                        $('#Ide_Cod').val(cedula.length === 10 ? 2 : 1);
                        $('#Ext_Ruc').fieldValid(true);
                        searchCliente(cedula, 'ec');
                    } else {
                        err = 1;
                        $('#Ide_Cod').val('');
                        $('#Ext_Ruc').fieldValid(false, validaNoIdentif(cedula)['message']);
                    }
                    break;
                case 2:
                    if (cedula.length === 13 && validaNoIdentif(cedula)['success']) {
                        err = 0;
                        $('#Ext_Ruc').fieldValid(true);
                        searchCliente(cedula, 'ec');
                    } else {
                        err = 1;
                        $('#Ide_Cod').val(1);
                        $('#Ext_Ruc').fieldValid(false, validaNoIdentif(cedula)['message']);
                    }
                    break;
                case 3:
                    $('#Ext_Ruc').fieldValid(true);
                    err = 0;
                    break;
            }
        }

        function habilitar(op, val) {
            $('#Ext_Ruc').val('').focus();
            if (op === 'ec') {
                $('#Ide_Cod').find('option').show();
                $('#Ide_Cod').attr('disabled', true);
                $('#Ide_Cod').val('');
            } else {
                $('#Ext_Ruc').fieldValid('');
                $('#Ide_Cod').find('option').hide().end().find('option[data-tipo="Ex"]').show();
                $('#Ide_Cod').val(val);
                $('#Ide_Cod').attr('disabled', false);
            }
        }

        function searchCliente(ced, tipo) {
            (tipo === 'ec') ? ced = ced.substring(0, 10): ced;
            var oldced = $('#oldcedula').val().substring(0, 10);
            if (ced !== oldced) {
                $.post("", {
                    searchCliente: true,
                    Ext_Ruc: ced
                }, function(response) {
                    if (response['exisCli'] === true) {
                        $.alert('El n&uacute;mero de identificaci&oacute;n ingresado(' + ced + ') ya se encuentra registrado..!!');
                        $('#Ext_Ruc').val('').focus();
                        $('#Ide_Cod').val('');
                    } else {
                        if (response['exisPer'] === true) {
                            $.createDialogConfirm('Desea sustituir los datos del cliente actual..!!', null, function() {
                                $('#formCliente').setData(response, false);
                                $('#Ext_Ciu').val(response['Ext_Ciu']).trigger('chosen:updated');
                            }, function() {
                                $('#Ext_Ruc').val(oldced).focus();
                                $('#Ide_Cod').val(ide_cod);
                            });
                        }
                    }
                }, 'json').fail(function() {
                    $.alert();
                });
            }
        }

        var pasaporte = '',
            ide_cod = 0;

        function cargarCliente(cliente) {
            $('#lista').moveComp('#modificar');
            if (cliente['Cli_Tic'] === 'J') {
                $('.juridico').show();
                $('.natural').hide();
            }
            if (cliente['Ide_Sri'] === 'P') {
                pasaporte = 'P';
                $("#radioex").trigger('change').prop("checked", true);
                $('#Ext_Ruc').attr('onchange', 'validar(3)');
            } else {
                pasaporte = 'O';
                $("#radioec").trigger('change').prop("checked", true);
            }
            $('#Ext_Ciu').val(cliente['Ext_Ciu']).trigger('chosen:updated');
            $('#oldcedula').val(cliente['Ext_Ruc']);
            $('#formCliente').setData(cliente);
            $('#Ext_Ruc').fieldValid(true);
            ide_cod = cliente['Ide_Cod'];
            (pasaporte !== 'P') ? validar(1): '';
            ($('#isRuc').parent())[pasaporte == 'P' ? 'hide' : 'show']();
            $('#isRuc').prop('checked', pasaporte != 'P' && cliente['Ext_Ruc'].length === 13);
        }

        function guardarChofer() {
            if (err === 1) {
                $.alert('Debe ingresar un n&uacute;mero de identificaci&oacute;n v&aacute;lido');
                return false;
            }
            $.saveDataJson("", $('#formCliente').getData('guardarChofer'), function(resp) {
                $('#Lis_Cli').trigger('reloadGrid');
            });
        }

        function setTipoDoc() {
            var $Ext_Ruc = $('#Ext_Ruc'),
                Ext_Ruc = $Ext_Ruc.val(),
                isRuc = $('#isRuc').is(':checked');
            if (Ext_Ruc.length >= 10 && $.isNum(Ext_Ruc)) {
                Ext_Ruc = Ext_Ruc.substring(0, 10);
                $Ext_Ruc.val(isRuc ? Ext_Ruc + '001' : Ext_Ruc);
                $Ext_Ruc.trigger('change');
            } else {
                $.alert("El numero " + Ext_Ruc + " no puede convertirse en RUC!");
            }
        }
    </script>
</body>

</HTML>