<?php

/**
 * Permite registrar un grupo de clientes de una empresa especifica
 *
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de actualización:	2024-09-23
 *
 * @package tesoreria.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_grupo_clientes.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


$obBD_conexion = new Class_Log_Conexion_grupoCliente($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_grupoCliente;
//Secci�n para listar los clientes registrados en la empresa
if (isset($clieAjax)) {
    $response = $obBD_con1->getPageGrid(1, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
    $obBD_con1->echoJson($response);
}



if (isset($saveDocument)) {
    $data = $_POST;
    //ChromePhp::log($data);
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $obBD_con1->operacionobBD(2, $data, $obBD_conexion);
    //Registrar el detalle
    $Cod_group_cli =  $obBD_con1->insercionid($obBD_conexion);
    $clientesData = json_decode(stripslashes($data['clientesData']), true);
    //ChromePhp::log($data);
    foreach ($clientesData as $clie) {
        $obBD_con1->operacionobBD(3, $clie["Cli_Cod"] . '*' . $Cod_group_cli, $obBD_conexion);
    }
    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true, 'message' => 'Registrado con éxito');
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', 'error' => $obBD_con1->MsgError);
    }

    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}



?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>

</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; REGISTRAR GRUPO DE EMPRESAS</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-3"></div>
                <div class="col-md-6 col-sm-8">
                    <form class="form-horizontal normal" id="formCliente" name="formCliente" action="javascript:saveDocument();">

                        <input name="Prs_Cod" type="text" style="display:none;" />
                        <input name="Prs_Cor" type="text" style="display:none;" />
                        <input name="Cli_Cod" type="text" style="display:none;" />
                        <input name="op_opciones" type="text" value="c" style="display: none;">


                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del Grupo de empresas</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Nombre:</label>
                                <div class="col-xs-9">
                                    <div class="input-group input-group-xs">
                                        <input id="Grup_Nom" name="Grup_Nom" type="text" class="form-control input-xs" required="" />

                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required"><span class='natural'>Descripción:</span></label>
                                <div class="col-xs-7"> <textarea name="Grup_Des" id="Grup_Des" class="form-control input-xs"></textarea> </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required"><span class='natural'>Fecha de registro:</span></label>
                                <div class="col-md-3">
                                    <input id="Grup_Fec" name="Grup_Fec" type="date" placeholder="" value="<?php echo date('Y-m-d'); ?>" class="form-control input-xs" required>
                                </div>
                            </div>
                        </fieldset>

                        <button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente" tabindex="2"> Agregar clientes <span class="glyphicon glyphicon-plus"></span></button>
                        <fieldset class="exa-fieldset">
                            <table id="seleccionadosTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr class="text-center">
                                        <th class="text-center">Código</th>
                                        <th class="text-center">Ced/Ruc</th>
                                        <th class="text-center">Nombre</th>
                                        <th class="text-center">Dirección</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Aquí se cargarán dinámicamente los datos -->
                                </tbody>
                            </table>
                        </fieldset>
                        <div class="center">
                            <button type="button" onclick="$(this.form).formSubmit();" class="btn btn-sm btn-primary no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2"></legend>
                            <div class="form-group Titulos2">
                                <div class="col-sm-12" style="text-align: center;"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
                                    <hr />
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div id="clieDialog" title="B&uacute;squeda de Cliente">
        <form class="form-horizontal normal"> </form>
    </div>

    <script type="text/javascript">
        $.createSearchDialog('clieDialog', [{
                label: 'C&oacute;d.Int.',
                name: 'Cli_Cod',
                key: true,
                width: 15,
                align: "center",
                hidden: true
            },
            {
                label: 'Cédula/RUC',
                name: 'Prs_Ced',
                width: 50
            },
            {
                label: 'Cliente',
                name: 'cliente',
                width: 100
            },
            {
                label: 'Direcc.',
                name: 'Prs_Dir',
                width: 60
            },
            {
                label: '&nbsp;',
                name: 'act1',
                width: 20,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: selectCliente
                }
            }
        ], null, null, null, {
            headertitles: true
        }, {
            title: 'Cliente',
            text: 'Prs_Ced'
        });

        function selectCliente(cliente) {
            const tablaSeleccionados = document.getElementById('seleccionadosTable').getElementsByTagName('tbody')[0];
            if (cliente) {
                const fila = tablaSeleccionados.insertRow();
                fila.insertCell(0).innerHTML = cliente.Cli_Cod || 'Sin código'; // Código Cliente
                fila.insertCell(1).innerHTML = cliente.Prs_Ced || 'Sin Cédula/RUC'; // Cédula/RUC
                fila.insertCell(2).innerHTML = cliente.cliente || 'Desconocido'; // Nombre del Cliente
                fila.insertCell(3).innerHTML = cliente.Prs_Dir || 'Sin dirección'; // Dirección
                // Agregar un botón de "Quitar" en la última celda
                const celdaQuitar = fila.insertCell(4);
                const botonQuitar = document.createElement('button');
                botonQuitar.className = 'btn btn-danger btn-xs  glyphicon glyphicon-remove  '; // Usando una clase de Bootstrap para estilo
                botonQuitar.onclick = function() {
                    event.preventDefault(); // Evita que el formulario se envíe
                    const filaAEliminar = this.parentNode.parentNode;
                    filaAEliminar.parentNode.removeChild(filaAEliminar);
                };
                celdaQuitar.appendChild(botonQuitar);
            } else {
                console.error("Error: El cliente seleccionado no tiene datos.");
            }
        }

        function saveDocument() {
            var clientes = [];
            $('#seleccionadosTable tbody tr').each(function() {
                var cliente = {
                    Cli_Cod: $(this).find('td').eq(0).addClass('text-center').text(), // Código Cliente
                    Prs_Ced: $(this).find('td').eq(1).addClass('text-center').text(), // Cedula/Ruc
                    cliente: $(this).find('td').eq(2).addClass('text-center').text(), // Nombre del Cliente
                    Prs_Dir: $(this).find('td').eq(3).addClass('text-center').text() // Dirección
                };
                clientes.push(cliente);
            });
            // Crear un campo oculto para agregar los datos de la tabla al formulario
            var existingInput = $('#formCliente input[name="clientesData"]');
            if (existingInput.length > 0) {
                // Si existe, solo actualiza el valor
                existingInput.val(JSON.stringify(clientes));
            } else {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'clientesData',
                    value: JSON.stringify(clientes) // Convertir los datos de clientes a JSON
                }).appendTo('#formCliente'); // Añadir al formulario

            }
            $.saveDataJson('',
                $('#formCliente').getData('saveDocument'),
                function(resp) {
                    $.alert(resp.message, null, 'remove');
                    return false;
                });
        }
    </script>
</BODY>

</HTML>