<?php

/**
 * Registra clientes que se les cobrara a credito en empresa de gasolinera
 *
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualizaci�n:	2025-02-13
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de actualizaci�n:	2025-02-13
 *
 * @package tesoreria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cliente.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Cli($Ses_Dat_Dis);
$obBD_con1 =  new Class_Log_Datos_Cli;
/* guarda un nuevo cliente */
if (isset($guardarClienteCre)) {
    $data = $_POST;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $obBD_con1->operacionobBD(30, $data, $obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
        $responce['message'] = "No se ha logrado realizar la Transaccion";
    }
    $obBD_con1->echoJson($responce);
}

if (isset($clientesAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(31, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(31, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if (isset($searchCliente)) {
    $existe = $obBD_con1->getRowConsulta(32, $Prs_Ced . '*' . $Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Cod_Cre'])) ? $responce['existe'] = true : $responce['existe'] = false;
    $obBD_con1->echoJson($responce);
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <TITLE><?Php echo "Registrar Clientes a Crédito [EXA]"; ?></TITLE>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar Clientes para cobros a crédito</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-3"></div>
                <div class="col-md-6 col-sm-8 ">
                    <span class="input-group-btn py-2">
                        <button class="btn btn-success btn-xs" type="submit" title="Nuevo Cliente" onclick="limpiar()"><span class="glyphicon glyphicon-plus"></span> Nuevo</button>
                    </span><br>
                    <form class="form-horizontal normal" id="formCliente" name="formCliente" action="javascript:guardarClienteCre();">
                        <input name="Cod_Cre" type="text" class="hidden" />
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del Cliente</legend>
                            <div class="form-group Titulos2">
                                <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
                                    <hr />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                                <div class="col-xs-5">
                                    <div class="input-group input-group-xs">
                                        <input id="Ced_Cre" name="Ced_Cre" type="text" class="form-control input-xs" onchange="validar(1)" required="" />
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
                                <label class="col-xs-3 control-label label-xs required"><span class='natural'>Nombres:</span></label>
                                <div class="col-xs-9"><input name="Nom_Cli" type="text" class="form-control input-xs" required="" /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required"><span class='natural'>Plazo de Crédito:</span></label>
                                <div class="col-xs-3"><input name="Tim_Cre" type="number" class="form-control input-xs" required="" value="30" /></div>
                                <label class="col-xs-3 control-label label-xs required"><span class='natural'>Estado:</span></label>
                                <div class="col-xs-3">
                                    <select class="form-control input-xs" name="Cre_Est" id="Cre_Est" required>
                                        <option value="A">Activo</option>
                                        <option value="I">Inactivo</option>
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
    <script type="text/javascript">
        $(function() {
            $("#Lis_Cli").createGrid({
                postData: $("#frm_bus").getData("clientesAjax"),
                height: 295,
                colModel: [{
                        label: 'C&oacute;d. Int.',
                        name: 'Cod_Cre',
                        width: 20,
                        align: "left"
                    },
                    {
                        label: 'C&eacute;dula/R.U.C.',
                        name: 'Ced_Cre',
                        width: 50,
                        align: "left"
                    },
                    {
                        label: 'Cliente',
                        name: 'Nom_Cli',
                        width: 150,
                        align: "left"
                    },
                    {
                        label: 'Dias Cred.',
                        name: 'Tim_Cre',
                        width: 30,
                        align: "left"
                    },
                    {
                        label: 'Estado',
                        name: 'Cre_Est',
                        width: 30,
                        align: "left",
                        formatter: function(cellValue) {
                            if (cellValue === 'A') {
                                return 'ACTIVO'; 
                            } else if (cellValue === 'I') {
                                return 'INACTIVO'; 
                            }
                            return ''; 
                        }
                    }, {
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
        });
        var err = 1;
        var err = 0;

        function cargarCliente(cliente) {
            $('#formCliente').setData(cliente);
            document.getElementById("Cre_Est").value = cliente.Cre_Est;
            if (cliente.Ced_Cre.length == 10) {
                document.getElementById("Ide_Cod").value = 2;
            } else if (cliente.Ced_Cre.length == 13) {
                document.getElementById("Ide_Cod").value = 1;
            } else {
                document.getElementById("Ide_Cod").value = 3;
            }
            $(".btn.no").html('<i class="glyphicon glyphicon-floppy-disk"></i> Actualizar');
        }

        function limpiar() {
            $(".btn.no").html('<i class="glyphicon glyphicon-floppy-disk"></i> Guardar');
            clear();
        }

        function validar(op) {
            var cedula = $('#Ced_Cre').val();
            switch (op) {
                case 1:
                    if (validaNoIdentif(cedula)['success']) {
                        err = 0;
                        $('#Ide_Cod').val(cedula.length === 10 ? 2 : 1);
                        $('#Ced_Cre').fieldValid(true);
                        searchCliente(cedula, 'ec');
                    } else {
                        err = 1;
                        $('#Ide_Cod').val('');
                        $('#Ced_Cre').fieldValid(false, validaNoIdentif(cedula)['message']);
                    }
                    break;
                case 2:
                    if (cedula.length === 13 && validaNoIdentif(cedula)['success']) {
                        err = 0;
                        $('#Ced_Cre').fieldValid(true);
                        searchCliente(cedula, 'ec');
                    } else {
                        err = 1;
                        $('#Ide_Cod').val(1);
                        $('#Ced_Cre').fieldValid(false, validaNoIdentif(cedula)['message']);
                    }
                    break;
                case 3:
                    err = 0;
                    $('#Ced_Cre').fieldValid(true);
                    searchCliente(cedula, 'ex');
                    break;
            }
        }

        function searchCliente(ced, tipo) {
            (tipo === 'ec') ? ced = ced.substring(0, 10): ced;
            $.post("", {
                searchCliente: true,
                Prs_Ced: ced
            }, function(response) {
                if (response['existe'] === true) {
                    $.alert('El cliente ' + ced + ' ya se encuentra registrado..!!');
                    clear();
                } else {
                    $.extend(response, {
                        Prs_Ced: $('#Ced_Cre').val(),
                        Ide_Cod: $('#Ide_Cod').val()
                    });
                    $('#formCliente').setData(response, false);
                }
            }, 'json').fail(function() {
                $.alert();
            });
        }
        //METODO NUEVO
        function clear() {
            $('#formCliente').setData({});
            $('#Ced_Cre').val('').focus();
        }

        function searchCliente(ced, tipo) {
            (tipo === 'ec') ? ced = ced.substring(0, 10): ced;
            $.post("", {
                searchCliente: true,
                Prs_Ced: ced
            }, function(response) {
                if (response['existe'] === true) {
                    $.alert('El cliente ' + ced + ' ya se encuentra registrado..!!');
                    clear();
                } else {
                    $('#Ciu_Cod').val(response['Ciu_Cod']).trigger('chosen:updated');
                    $.extend(response, {
                        Prs_Ced: $('#Ced_Cre').val(),
                        Ide_Cod: $('#Ide_Cod').val()
                    });
                    $('#formCliente').setData(response, false);
                }
            }, 'json').fail(function() {
                $.alert();
            });
        }

        function guardarClienteCre() {
            if (err === 1) {
                $.alert('Debe ingresar un n&uacute;mero de identificaci&oacute;n v&aacute;lido');
                return false;
            }
            $.saveDataJson("", $('#formCliente').getData('guardarClienteCre'), function(resp) {
                clear();
                $('#Lis_Cli').Search('#frm_bus', 'clientesAjax');
                $(".btn.no").html('<i class="glyphicon glyphicon-floppy-disk"></i> Guardar');
            });
        }

        function validaNoIdentif(number) {
            number = number.trim();
            var digitos = number.split(""),
                dto = digitos.length,
                acu = 0,
                resp = {
                    success: false,
                    message: '',
                    doc_num: number
                },
                coef = {
                    'NA': [2, 1, 2, 1, 2, 1, 2, 1, 2],
                    'PU': [3, 2, 7, 6, 5, 4, 3, 2, 0],
                    'PR': [4, 3, 2, 7, 6, 5, 4, 3, 2]
                },
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
                    var residuo = acum % modulo,
                        digitoVerificador = residuo === 0 ? 0 : modulo - residuo;
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
</BODY>

</HTML>