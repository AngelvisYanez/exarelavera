<?php

/**
 * Permite modificar un Cliente ya sea Nacional(Cedula o Ruc) o Extranjero(Pasaporte)
 * 
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualizaci�n:	2012-04-16
 * @author lewis.chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2014-05-21
 * 
 * @package tesoreria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cliente.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Tes
 */
$obBD_conexion = new Class_Log_Conexion_Cli($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Tes
 */
$obBD_con1 =  new Class_Log_Datos_Cli;

/*Secci�n para listar los clientes registrados dentro de la empresa*/
if (isset($clientesAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(27, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(27, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

/* ver si exite un cliente */
if (isset($searchCliente)) {
    $responce = $obBD_con1->getRowConsulta(17, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con1->getRowConsulta(18, $responce['Prs_Cod'] . '*' . $Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Cli_Cod'])) ? $responce['exisCli'] = true : $responce['exisCli'] = false;
    (!empty($responce['Prs_Cod'])) ? $responce['exisPer'] = true : $responce['exisPer'] = false;
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

/* guarda un nuevo cliente */
if (isset($guardarCliente)) {
    $pers = $obBD_con1->getRowConsulta('persona.selectWhere', array('clean' => true, 'Prs_Cod' => $Prs_Cod), $obBD_conexion);
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(12, mb_convert_encoding($Prs_Ced . '*' . $Prs_Nom . '*' . $Prs_Ape . '*' . $Prs_Sex . '*' . $Prs_Dir . '*' . $Prs_Tel . '*' . $Prs_Te2 . '*' . $Prs_Cel . '*' . $Ciu_Cod . '*' . $Ide_Cod . '*' . (!empty($Prs_Cor) ? $Prs_Cor : '') . '*' . $Prs_Cod, 'ISO-8859-1', 'UTF-8'), $obBD_conexion);
   
    $razon_social = "";//!empty($Prs_Nom) ? $Prs_Nom : $Prs_Ape;
   
    $obBD_con1->operacionobBD(26, $Prs_Cod . '*' . $Cli_Tic . '*' . $Cli_Con . '*' . $Cli_Cod . '*' . $Prs_Cor .'*'. $razon_social . '*'. $Dia_Cred . '*' . $Mon_Max, $obBD_conexion);
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

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Clientes Modificar [EXA]"; ?></TITLE>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <!--<script type="text/javascript" src="../VALIDACIONES/tes_val_cliente.js?a=12"></script>-->
    <script type="text/javascript" src="../../framework/plugins/cedulaRuc.js"></script>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Modificar Clientes</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="lista" class="row">
                <div class="col-md-12">
                    <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:$('#Lis_Cli').Search('#frm_bus','clientesAjax');">
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
                    <form class="form-horizontal normal" id="formCliente" name="formCliente" action="javascript:guardarCliente();">
                        <input name="Cli_Cod" type="text" class="hidden" />
                        <input name="Prs_Cod" type="text" class="hidden" />
                        <input id="oldcedula" type="text" class="hidden" />
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del Cliente</legend>
                            <div class="form-group Titulos2">
                                <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
                                    <hr />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Ciudadano:</label>
                                <div class="col-xs-5">
                                    <div class="btn-group" data-toggle="buttons">
                                        <label id="lb_ec" class="btn btn-success btn-xs">
                                            <input id="radioec" name="tipo" value="Ec" type="radio" disabled=""><i id="spanec" class="fa fa-check"></i> Ecuatoriano
                                        </label>
                                        <label id="lb_ex" class="btn btn-default btn-xs">
                                            <input id="radioex" name="tipo" value="Ex" type="radio" disabled=""><i id="spanex" class="fa fa-check" style="display: none;"></i> Extranjero
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                                <div class="col-xs-5">
                                    <div class="input-group input-group-xs">
                                        <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="validar(1)" required="" readonly="" />
                                        <span class="input-group-addon validate"><i></i></span>
                                        <span class="input-group-addon alert-info"><input id="isRuc" type="checkbox" value="S" offval="N" style="vertical-align: middle;" onchange="setTipoDoc();"><b> RUC</b></span>
                                    </div>
                                </div>
                                <div class="col-xs-4">
                                    <div class="checkbox check-big" style="position:absolute;">
                                        <label><input type="checkbox" name="Cli_Con" value="S" offval="N">Obligado Contab.</label>
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
                                <label class="col-xs-3 control-label label-xs required">Contribuyente:</label>
                                <div class="col-xs-4">
                                    <select id="Cli_Tic" name="Cli_Tic" class="form-control input-xs" required="" onchange="if(this.value==='N'){ $('.juridico').hide();$('.natural').show(); }else{ $('.natural').hide();$('.juridico').show(); }">
                                        <option value="N">NATURAL</option>
                                        <option value="J">JURIDICO</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Razón Social:</span></label>
                                <div class="col-xs-9"><input name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required"><span class='natural'>Nombres:</span><span class='juridico' style="display: none;">Nomb.Comerc.:</span></label>
                                <div class="col-xs-9"><input name="Prs_Nom" type="text" class="form-control input-xs" required="" /></div>
                            </div>
                            <div class="form-group natural">
                                <label class="col-xs-3 control-label label-xs required">Genero:</label>
                                <div class="col-xs-4">
                                    <select name="Prs_Sex" class="form-control input-xs">
                                        <option value="M">MASCULINO</option>
                                        <option value="F">FEMENINO</option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de Ubicación</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                                <div class="col-xs-6">
                                    <?php $rs_ciudad = $obBD_con1->getArrayConsulta(15, '', $obBD_conexion); ?>
                                    <select id="Ciu_Cod" name="Ciu_Cod" class="form-control input-xs" data-placeholder="Seleccione una ciudad" required="">
                                        <option value=""></option>
                                        <!--?php foreach ($rs_ciudad as $row) {
                                            echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]' data-pais='$row[Pas_Nom]'>$row[Ciu_Des]</option>";
                                        } ?-->
                                        <?php
                                        foreach ($rs_ciudad as $row) {
                                            echo "<option value='{$row['Ciu_Cod']}' data-prov='" . mb_convert_encoding($row['Pro_Nom'], 'UTF-8', 'ISO-8859-1') . "' data-pais='" . mb_convert_encoding($row['Pas_Nom'], 'UTF-8', 'ISO-8859-1') . "'>" . mb_convert_encoding($row['Ciu_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
                                        }
                                        ?>

                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Dirección:</label>
                                <div class="col-xs-9"><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Teléfono(s):</label>
                                <div class="col-xs-9">
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon bold alert-info">#1:</span>
                                        <input name="Prs_Tel" type="text" class="form-control input-xs" pattern="\d*" onkeypress="return validar_numeric(event);" />
                                        <span class="input-group-addon bold alert-info">#2:</span>
                                        <input name="Prs_Te2" type="text" class="form-control input-xs" pattern="\d*" onkeypress="return validar_numeric(event);" />
                                        <span class="input-group-addon bold alert-info">#3:</span>
                                        <input name="Prs_Cel" type="text" class="form-control input-xs" pattern="\d*" onkeypress="return validar_numeric(event);" />
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Mail:</label>
                                <div class="col-xs-9"><input name="Prs_Cor" type="email" class="form-control input-xs" multiple /></div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <legend class="Titulos2">Datos del Credito Otorgado</legend>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required" style="margin-left: -25px;"><span class='natural'>Dias de Credito:</span><span class='juridico' style="display: none;">dias de credito</span></label>
                                    <div class="col-xs-3"><input name="Dia_Cred" id="Dia_Cred" type="text" class="form-control input-xs" required="" /></div>
                                <!-- </div>
                                <div class="form-group"> -->
                                    <label class="col-xs-3 control-label label-xs required" style="margin-left: -25px;"><span class='natural'>Monto Maximo:</span><span class='juridico' style="display: none;">Monto Maximo</span></label>
                                    <div class="col-xs-3"><input name="Mon_Max" id="Mon_Max" type="text" class="form-control input-xs" required="" /></div>
                                </div>
                        </fieldset></script>
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

            //Inicio Grid para presentar el detalle de factura
            $("#Lis_Cli").createGrid({
                postData: $("#frm_bus").getData("clientesAjax"),
                height: 295,
                colModel: [{
                        label: 'C&oacute;d. Int.',
                        name: 'Cli_Cod',
                        width: 50,
                        align: "left"
                    },
                    {
                        label: 'C&eacute;dula/R.U.C.',
                        name: 'Prs_Ced',
                        width: 50,
                        align: "left"
                    },
                    {
                        label: 'Cliente',
                        name: 'cliente',
                        width: 150,
                        align: "left"
                    },
                    {
                        label: 'Correo',
                        name: 'Prs_Cor',
                        width: 150,
                        align: "left"
                    },
                    {
                        label: '&nbsp;',
                        name: 'act1',
                        width: 30,
                        align: 'center',
                        viewable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            return $.getGridButton(cargarCliente, rowObject, 'Editar Cliente');
                        }
                    }
                ]
            }, false, "#Pag_Cli");


            $('#Ciu_Cod').createChosen('input-xs', {
                tabIndex: 6,
                width: '100%',
                template: function(t, d) {
                    return '<div class="over"><b>' + t + '</b></div><div class="over desc" style="font-size:11px;"><b>Provincia:</b> ' + d['prov'] + ' <b>Pa&iacute;s:</b> ' + d['pais'] + '</div>';
                }
            });

            $("#radioec").change(function() {
                habilitar('ec', 1);
                $('#Prs_Ced').attr('onchange', 'validar(1)');
                $('#lb_ec').attr('class', 'btn btn-success btn-xs');
                $('#lb_ex').attr('class', 'btn btn-default btn-xs');
                $('#spanec').show();
                $('#spanex').hide();
            });

            $("#radioex").change(function() {
                habilitar('ex', 1);
                $('#Prs_Ced').attr('onchange', 'validar(2)');
                $('#lb_ex').attr('class', 'btn btn-success btn-xs');
                $('#lb_ec').attr('class', 'btn btn-default btn-xs');
                $('#spanex').show();
                $('#spanec').hide();
            });

            $('#Ide_Cod').change(function() {
                if (this.value * 1 === 1) {
                    $('#Prs_Ced').attr('onchange', 'validar(2)');
                } else {
                    $('#Prs_Ced').attr('onchange', 'validar(3)');
                }
                habilitar('ex', this.value);
            });
        });

        var err = 0;

        function validar(op) {
            var cedula = $('#Prs_Ced').val();
            switch (op) {
                case 1:
                    if (validaNoIdentif(cedula)['success']) {
                        err = 0;
                        $('#Ide_Cod').val(cedula.length === 10 ? 2 : 1);
                        $('#Prs_Ced').fieldValid(true);
                        searchCliente(cedula, 'ec');
                    } else {
                        err = 1;
                        $('#Ide_Cod').val('');
                        $('#Prs_Ced').fieldValid(false, validaNoIdentif(cedula)['message']);
                    }
                    break;
                case 2:
                    if (cedula.length === 13 && validaNoIdentif(cedula)['success']) {
                        err = 0;
                        $('#Prs_Ced').fieldValid(true);
                        searchCliente(cedula, 'ec');
                    } else {
                        err = 1;
                        $('#Ide_Cod').val(1);
                        $('#Prs_Ced').fieldValid(false, validaNoIdentif(cedula)['message']);
                    }
                    break;
                case 3:
                    $('#Prs_Ced').fieldValid(true);
                    err = 0;
                    break;
            }
        }

        function habilitar(op, val) {
            $('#Prs_Ced').val('').focus();
            if (op === 'ec') {
                $('#Ide_Cod').find('option').show();
                $('#Ide_Cod').attr('disabled', true);
                $('#Ide_Cod').val('');
            } else {
                $('#Prs_Ced').fieldValid('');
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
                    Prs_Ced: ced
                }, function(response) {
                    if (response['exisCli'] === true) {
                        $.alert('El n&uacute;mero de identificaci&oacute;n ingresado(' + ced + ') ya se encuentra registrado..!!');
                        $('#Prs_Ced').val('').focus();
                        $('#Ide_Cod').val('');
                    } else {
                        if (response['exisPer'] === true) {
                            $.createDialogConfirm('Desea sustituir los datos del cliente actual..!!', null, function() {
                                $('#formCliente').setData(response, false);
                                $('#Ciu_Cod').val(response['Ciu_Cod']).trigger('chosen:updated');
                            }, function() {
                                $('#Prs_Ced').val(oldced).focus();
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
                $('#Prs_Ced').attr('onchange', 'validar(3)');
            } else {
                pasaporte = 'O';
                $("#radioec").trigger('change').prop("checked", true);
            }
            $('#Ciu_Cod').val(cliente['Ciu_Cod']).trigger('chosen:updated');
            $('#oldcedula').val(cliente['Prs_Ced']);
            $('#formCliente').setData(cliente);
            $('#Prs_Ced').fieldValid(true);
            ide_cod = cliente['Ide_Cod'];
            (pasaporte !== 'P') ? validar(1): '';
            ($('#isRuc').parent())[pasaporte == 'P' ? 'hide' : 'show']();
            $('#isRuc').prop('checked', pasaporte != 'P' && cliente['Prs_Ced'].length === 13);
        }

        function guardarCliente() {
            if (err === 1) {
                $.alert('Debe ingresar un n&uacute;mero de identificaci&oacute;n v&aacute;lido');
                return false;
            }
            $.saveDataJson("", $('#formCliente').getData('guardarCliente'), function(resp) {
                // $('#Lis_Cli').trigger('reloadGrid');
                if (resp && resp.success) {
                    // Volver a la lista y recargar la grilla
                    $('#modificar').moveComp('#lista')/*.updateGridsSizes()*/;
                    $('#Lis_Cli').trigger('reloadGrid');
                } else {
                    $.alert(resp && resp.message ? resp.message : 'Error al guardar el cliente');
                }
            });
        }

        function setTipoDoc() {
            var $Prs_Ced = $('#Prs_Ced'),
                Prs_Ced = $Prs_Ced.val(),
                isRuc = $('#isRuc').is(':checked');

            if (Prs_Ced.length >= 10 && $.isNum(Prs_Ced)) {
                Prs_Ced = Prs_Ced.substring(0, 10);
                $Prs_Ced.val(isRuc ? Prs_Ced + '001' : Prs_Ced);
                $Prs_Ced.trigger('change');
            } else {
                $.alert("El numero " + Prs_Ced + " no puede convertirse en RUC!");
            }
        }
    </script>
</BODY>

</HTML>