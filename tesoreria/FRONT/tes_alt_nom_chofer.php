<?php

/**
 * Permite registrar datos de un chofer asociado a una empresa
 *
 * @author car.87cod :)
 * @version 1.0
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de actualizaci�n:	18/09/2025
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
$obBD_con1 =  new Class_Log_Datos_Cli;

/* ver si exite un chofer */
if (isset($searchChofer)) {
    $existe = $obBD_con1->getRowConsulta(18, $Ext_Ruc . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $responce = ($existe['cant'] > 0) ? $responce['cant'] = true : $responce['cant'] = false;
    $obBD_con1->echoJson($responce);
}

/* guarda un nuevo cliente */
if (isset($guardarChofer)) {
    $data = $_POST;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $data['Ext_Fec'] = date("Y-m-d H:i:s");

    $obBD_con1->inicio_transaccion($obBD_conexion);
    if (!empty($Ext_Ruc)) {
        $obBD_con1->operacionobBD(33, $data, $obBD_conexion);
        $data['Ext_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
        $responce['message'] = "No se ha logrado realizar la Transaccion";
    }
    $obBD_con1->echoJson($responce);
}

?>
<!DOCTYPE html>
<html>

<head>
    <TITLE><?Php echo "Socio Registrar [EXA]"; ?></TITLE>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script type="text/javascript" src="../../framework/plugins/validadorCedulaRucFinal.js"></script>
</head>

<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar chofer</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-3"></div>
                <div class="col-md-6 col-sm-8">
                    <form class="form-horizontal normal" id="formCliente" name="formCliente" action="javascript:guardarChofer();">
                        <input name="Prs_Cod" type="text" class="hidden" />
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
                                            echo "<option value='$row[Ciu_Des]' data-prov='" . mb_convert_encoding($row['Pro_Nom'], 'UTF-8', 'ISO-8859-1') . "' data-pais='" . mb_convert_encoding($row['Pas_Nom'], 'UTF-8', 'ISO-8859-1') . "'>" . mb_convert_encoding($row['Ciu_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
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
                            <button type="button" onclick="$(this.form).formSubmit();" class="btn btn-sm btn-primary no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        var err = 1;
        $(function() {
            $('#Ciu_Cod').createChosen('input-xs', {
                tabIndex: 6,
                width: '100%',
                template: function(t, d) {
                    return '<div class="over"><b>' + t + '</b></div><div class="over desc" style="font-size:11px;"><b>Provincia:</b> ' + d['prov'] + ' <b>Pa&iacute;s:</b> ' + d['pais'] + '</div>';
                }
            });
            $("#radioec").change(function() {
                $('#Ext_Ruc').attr('onchange', 'validar(1)');
                habilitar('ec', 1);
                $('#lb_ec').attr('class', 'btn btn-success btn-xs');
                $('#lb_ex').attr('class', 'btn btn-default btn-xs');
                $('#spanec').show();
                $('#spanex').hide();
                clear();
            });
            $("#radioex").change(function() {
                clear();
                habilitar('ex', 7);
                $('#Ext_Ruc').attr('onchange', 'validar(2)');
                $('#lb_ex').attr('class', 'btn btn-success btn-xs');
                $('#lb_ec').attr('class', 'btn btn-default btn-xs');
                $('#spanex').show();
                $('#spanec').hide();
            });
            $('#Ide_Cod').change(function() {
                $('#Ext_Ruc').val('').focus();
                if (this.value * 1 === 1) {
                    $('#Ext_Ruc').attr('onchange', 'validar(2)');
                } else {
                    $('#Ext_Ruc').attr('onchange', 'validar(3)');
                }
                habilitar('ex', this.value);
            });
        });
        //Nuevos cambios
        var err = 0;

        function validar(op) {
            var cedula = $('#Ext_Ruc').val();
            switch (op) {
                case 1:
                    if (validaNoIdentif(cedula)['success']) {
                        err = 0;
                        $('#Ide_Cod').val(cedula.length === 10 ? 2 : 1);
                        $('#Ext_Ruc').fieldValid(true);
                        searchChofer(cedula, 'ec');
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
                        searchChofer(cedula, 'ec');
                    } else {
                        err = 1;
                        $('#Ide_Cod').val(1);
                        $('#Ext_Ruc').fieldValid(false, validaNoIdentif(cedula)['message']);
                    }
                    break;
                case 3:
                    err = 0;
                    $('#Ext_Ruc').fieldValid(true);
                    searchChofer(cedula, 'ex');
                    break;
            }
        }

        function habilitar(op, val) {
            var lon_ced = $('#Ext_Ruc').val().length;
            $('#Ext_Ruc').fieldValid('');
            if (op === 'ec') {
                $('#Ide_Cod').find('option').show();
                $('#Ide_Cod').attr('disabled', true);
                $('#Ide_Cod').val(lon_ced === 10 ? 2 : 1);
            } else {
                $('#Ide_Cod').find('option').hide().end().find('option[data-tipo="Ex"]').not('[value="1"]').show();
                $('#Ide_Cod').val(val);
                $('#Ide_Cod').attr('disabled', false);
            }
        }
        //METODO NUEVO
        function searchChofer(ced, tipo) {
            (tipo === 'ec') ? ced = ced.substring(0, 10): ced;
            $.post("", {
                searchChofer: true, Ext_Ruc: ced
            }, function(response) {
                if (response['existe'] === true) {
                    $.alert('El cliente ' + ced + ' ya se encuentra registrado..!!');
                    clear();
                } else {
                    $('#Ciu_Cod').val(response['Ciu_Cod']).trigger('chosen:updated');
                    $.extend(response, { Ext_Ruc: $('#Ext_Ruc').val(), Ide_Cod: $('#Ide_Cod').val() });
                    $('#formCliente').setData(response, false);
                }
            }, 'json').fail(function() {
                $.alert();
            });
        }
        //Fin de metodo nuevo
        function clear() {
            $('#formCliente').setData({ Cli_Tic: 'N', Prs_Ciu: 'Ec', Prs_Sex: 'M'});
            $('#Ext_Ruc').val('').focus();
            $('.juridico').hide();
            $('.natural').show();
        }

        function guardarChofer() {
            if (err === 1) {
                $.alert('Debe ingresar un n&uacute;mero de identificaci&oacute;n v&aacute;lido');
                return false;
            }
            $.saveDataJson("", $('#formCliente').getData('guardarChofer'), function(resp) {
                $("#radioec").trigger('change');
                clear();
            });
        }

        function validaNoIdentif(number) {
            number = number.trim();
            var digitos = number.split(""), dto = digitos.length,  acu = 0,
                resp = {  success: false, message: '', doc_num: number },
                coef = {'NA': [2, 1, 2, 1, 2, 1, 2, 1, 2], 'PU': [3, 2, 7, 6, 5, 4, 3, 2, 0],'PR': [4, 3, 2, 7, 6, 5, 4, 3, 2] },
                modulo, acum = 0;
            if (dto === 0) resp['message'] = 'No has ingresado ning\u00fan dato!';
            else {
                for (var i = 0; i < dto; i++)
                    if (!isNaN(digitos[i])) {
                        digitos[i] = digitos[i] * 1;
                        acu = acu + 1;
                    }
                if (acu === dto) {
                    var tipo = digitos[2],
                        prov = number.substring(0, 2) * 1;
                    tercer_digito = tipo;
                    if (tipo === 7 || tipo === 8) resp['message'] = '"El tercer d\u00edgito ingresado es inv\u00e1lido"';
                    else {
                        tipo = (tipo < 6 ? 'NA' : (tipo === 6 ? 'PU' : (tipo === 9 ? 'PR' : '')));
                        if ((tipo === 'PU' && dto === 10) || (tipo === 'PU' && dto === 13 && number.substring(9, 13) !== '0001')) tipo = 'NA';
                        modulo = (tipo === 'NA' ? 10 : 11);
                        resp['tipo_abrev'] = tipo;
                        resp['tipo'] = (tipo === 'NA' ? 'Natural' : (tipo === 'PR' ? 'Privada' : (tipo === 'PU' ? 'P\u00fablica' : '')));
                    }
                    if (dto !== 10 && dto !== 13) {
                        resp['message'] = 'La cantidad de d\u00EDgitos deben ser 10 o 13';
                        return resp;
                    } else {
                        resp['doc_abr'] = (dto === 10 ? 'C' : (dto === 13 ? 'R' : ''));
                        resp['doc'] = (dto === 10 ? 'C\u00E9dula' : (dto === 13 ? 'R.U.C.' : ''));
                    }
                    if (prov < 1 && prov > 24 && prov != 30) resp['message'] = 'Los dos primeros d\u00EDgitos no pueden ser mayores a 24, o diferente a 30.';
                    if (dto === 13) {
                        if (number.substring(10, 13) !== '001') resp['message'] = 'Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.';
                        if (tipo === 'PU' && number.substring(9, 13) !== '0001') resp['message'] = 'El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
                    } else if ((tipo === 'PU' || tipo === 'PR')) resp['message'] = 'El R.U.C. de las empresas ' + resp['tipo'] + 's deben tener 13 digitos!';
                    if (resp['message'].length > 0) {
                        return resp
                    };
                    for (var a = 0; a < 9; a++) {
                        var resul = digitos[a] * coef[tipo][a];
                        acum += (resul - (tipo === 'NA' && resul >= 10 ? 9 : 0));
                    }
                    var residuo = acum % modulo, digitoVerificador = residuo === 0 ? 0 : modulo - residuo;
                    if (digitos[(tipo === 'PU' ? 8 : 9)] !== digitoVerificador) resp['message'] = 'El n\u00famero de ' + resp['doc'] + ' de la ' + (tipo === 'NA' ? 'Persona Natural' : 'Empresa ' + resp['tipo']) + ' ingresado es inv\u00E1lido!';
                    if (resp['message'].length === 0) {
                        resp['success'] = true
                    } else if (tipo == "PR" && tercer_digito == 9) { //Validar RUC privado
                        resp['success'] = true;
                    }
                } else resp['message'] = "ERROR: Solo debe contener d\u00EDgitos!";
            }
            return resp;
        }
    </script>
</body>

</html>