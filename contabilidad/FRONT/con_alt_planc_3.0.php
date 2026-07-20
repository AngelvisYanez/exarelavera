<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_planc_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas  */
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");
$mes = date("m");

if (isset($saveCuenta)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $obBD_con1->operacionobBD(322, $cod_padre . '*' . $codpla . '*' . $cod_cuenta . '*' . $des_cuenta . '*' . $tip_cuenta . '**', $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
        $responce['message'] = "No se ha logrado realizar la Transaccion";
    }
    $obBD_con1->echoJson($responce);
}
if (isset($savePlan)) {
    $fecha = date("Y-m-d");
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $obBD_con1->operacionobBD(309, $Ses_Emp_Cod . '*' . $fecha . '*' . trim($des_plan), $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
        $responce['message'] = "No se ha logrado realizar la Transaccion";
    }
    $obBD_con1->echoJson($responce);
}
if (isset($listaPlanes)) {
    $row_rs_planes = $obBD_con1->getArrayConsulta(302, $Ses_Emp_Cod, $obBD_conexion);
    echo ' <option value="" data-estado="">Seleccione plan...</option>';
    foreach ($row_rs_planes as $row)
        echo '<option value="' . $row['Pla_Cod'] . '" data-estado="' . $row['Pla_Est'] . '">' . $row['Pla_Obs'] . '</option>';
    exit();
}
if (isset($planAjax)) {
    $responce = $obBD_con1->getArrayConsulta(335, $Pla_Cod . "*", $obBD_conexion);
    foreach ($responce as &$row) $row['a_attr'] = array('title' => $row['Pld_Des']);
    $obBD_con1->echoJson($responce);
}
if (isset($gridAjax)) {
    $responce['rows'] = $obBD_con1->getArrayConsulta(335, $Pla_Cod . "* AND Pld_Rec=" . $Pld_Cod, $obBD_conexion);
    $obBD_con1->echoJson($responce);
}
if (isset($ajaxCodigo)) {
    $conteo = $obBD_con1->getArrayConsulta(340, $Pla_Cod . '*' . $Pld_Cod . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $maximo = $obBD_con1->getRowConsulta(336, $Pla_Cod . '*' . $Pld_Rec, $obBD_conexion);
    $responce['next'] = ('0' . $maximo['max']) * 1 + 1;
    if (count($conteo) == 0) $responce['valid'] = true;
    else $responce['valid'] = false;
    $obBD_con1->echoJson($responce);
}
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Plan Cuenta Registrar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
    <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
</HEAD>
<style>
    #btn_contraer{display: none;}
    #btn_expandir{display: none;}
</style>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registro de Plan de Cuentas</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <div class="col-sm-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Plan de Cuentas</legend> <!-- Form Name -->
                            <form class="form-inline">
                                <div class="form-group">
                                    <label for="Pla_Cod" class="control-label label-xs">Seleccione plan:</label>
                                    <?php $row_rs_planes = $obBD_con1->getArrayConsulta(302, $Ses_Emp_Cod . "* AND Pla_Est='A'", $obBD_conexion); ?>
                                    <select id="Pla_Cod" name="Pla_Cod" onchange="if($('#Pla_Cod option:selected').val()==='') {$('#plan-footer').html('&nbsp;');gridComp.clearGrid();} updatePlan();resetForm();" class="form-control input-sm">
                                        <option value="" data-estado="">Seleccione plan...</option>
                                        <?php foreach ($row_rs_planes as $row) { ?>
                                            <option value="<?php echo $row['Pla_Cod']; ?>" data-estado="<?php echo $row['Pla_Est']; ?>"><?php echo $row['Pla_Obs']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <!-- CREAR UN NUEVO PLAN DE CUENTAS -->
                                <!--div class="form-group">
                                    <button onclick="$('#PlanCuen').val('');$('#planDialog').dialog('open');" type='button' class="btn btn-sm btn-success"><span class="glyphicon glyphicon-plus"></span></button>
                                </div-->
                            </form>

                            <!-- Botón para expandir todo el árbol -->
                            <button id="btn_expandir" onclick="expandirTodo();" class="btn btn-success btn-xs pull-right" style="float: right; margin-top: 11px; margin-right: 5px; padding: 2px 6px; font-size: 0.8em;">
                                <span class="glyphicon glyphicon-resize-full" style="font-size: 10px;"></span> Expandir todo
                            </button>

                            <button id="btn_contraer" onclick="contraerTodo();" class="btn btn-success btn-xs pull-right" style="float: right; margin-top: 11px; margin-right: 5px; padding: 2px 6px; font-size: 0.8em;">
                                <span class="glyphicon glyphicon-resize-small" style="font-size: 10px;"></span> Contraer todo
                            </button>
                            <!--button onclick="contraerTodo()">contraer</button-->

                            <div class="separator"></div>
                            <div class="panel panel-success exa-panel">
                                <div class="panel-heading"><i class="fa fa-list-ol"></i>&nbsp;&nbsp;<span id="plan-tittle"></span></div>
                                <div class="panel-body">
                                    <div class="scrollable-tree" style="height: 350px">
                                        <div id="using_json_2"></div>
                                    </div>
                                </div>
                                <div class="panel-footer"><span id="plan-footer">&nbsp;</span></div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-sm-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Formulario de Registro</legend> <!-- Form Name -->
                            <form id="formCuenta" class="form-horizontal normal" action="javascript:if($('#Pla_Cod option:selected').val()!==''){$.createDialogConfirm(null,null,saveForm);}else{$.alert('Selecione el Plan de Cuentas');} ">
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm" for="des_padre">Cuenta Padre:</label>
                                    <div class="col-sm-9">
                                        <input id="cod_padre" name="cod_padre" type="text" readonly style="display: none" value="0" />
                                        <input id="des_padre" name="des_padre" type="text" placeholder="" class="form-control input-sm bold" readonly value="Ra&iacute;z de plan" />
                                    </div>
                                </div>

                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="cod_cuenta">C&oacute;digo:</label>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span id='prefijo' class="input-group-addon bold"></span>
                                            <input id="cod_cuenta" name="cod_cuenta" class="form-control" placeholder="" type="text" required="" onchange="validaCodigo();" onkeypress="return validar_numeric(event);">

                                        </div>
                                    </div>
                                    <div class="col-sm-5 msgDiv vcenter">
                                        <img class="imgMsg"><label class="lblMsg"></label>
                                    </div>
                                </div>

                                <!-- Textarea -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label required" for="des_cuenta">Cuenta</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" id="des_cuenta" name="des_cuenta" required></textarea>
                                    </div>
                                </div>

                                <!-- Select Basic -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="tip_cuenta">Tipo:</label>
                                    <div class="col-sm-4">
                                        <select id="tip_cuenta" name="tip_cuenta" class="form-control input-sm" required>
                                            <option value="G">Grupo</option>
                                            <option value="D">Detalle</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Acci&oacute;n:</label>
                                    <div class="col-sm-9">
                                        <button type="submit" class="btn btn-primary btn-guarda"><span class="glyphicon glyphicon-floppy-disk"></span> Agregar</button>
                                        <button type="button" onclick="resetForm();updateCodigo();resetGrid();" class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                                    </div>
                                </div>
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12">
                                        <hr /><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
                                    </div>
                                </div>
                            </form>
                        </fieldset>
                        <div>
                            <table id="comp"></table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="planDialog" title="Registrar Plan de Cuentas">

        <div class="row">
            <div class="form-horizontal normal col-sm-12">
                <fieldset>
                    <legend><label class="Titulos2">Datos de Plan de Cuentas</label></legend>
                    <form id="formPlan" class="form-horizontal normal" action="javascript:$.createDialogConfirm(null,null,savePlan);">
                        <div class="form-group">
                            <label class="col-sm-3 control-label label-sm required">Descripci&oacute;n:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control input-sm" id="PlanCuen" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Acci&oacute;n:</label>
                            <div class="col-sm-9">
                                <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Agregar</button>
                                <button type="button" onclick="$('#planDialog').dialog('close');" class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                            </div>
                        </div>
                    </form>
                    <div class="form-group Titulos2">
                        <div class="col-sm-12">
                            <hr /><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        public $treeview = $('#using_json_2'),
            gridComp = $("#comp");

        function savePlan() {
            $.saveDataJson("", {
                savePlan: true,
                des_plan: $('#PlanCuen').val()
            }, function(r) {
                $('#PlanCuen').val('');
                $("#Pla_Cod").load('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>', {
                    listaPlanes: true
                }, function(resp) {
                    $('#planDialog').dialog('close');
                    updatePlan();
                    resetForm();
                    $('#plan-footer').html('&nbsp;');
                });
            });
        }

        function saveForm() {
            var data = $('#formCuenta').serializeObject();
            $.extend(data, {
                saveCuenta: true,
                codpla: $('#Pla_Cod option:selected').val(),
                cod_cuenta: $('#prefijo').html() + data["cod_cuenta"]
            });
            $.saveDataJson("", data, function(r) {
                resetForm();
                resetGrid();
                $treeview.jstree(true).refresh();
            });
        }

        function updateCodigo() {
            $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>', {
                'Pld_Cod': $('#prefijo').html() + $('#cod_cuenta').val(),
                'pre': $('#prefijo').html(),
                'Pld_Rec': $('#cod_padre').val(),
                'Pla_Cod': $('#Pla_Cod option:selected').val(),
                'ajaxCodigo': true
            }, function(response) {
                $("#cod_cuenta").val(response['next']);
            }, 'json').fail(function(error) {
                $.alert("El Servidor ha fallado en responder!");
            });
        }

        function validaCodigo() {
            $('.btn-guarda').attr('disabled', 'disabled');
            $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>', {
                    'Pld_Cod': $('#prefijo').html() + $('#cod_cuenta').val(),
                    'pre': $('#prefijo').html(),
                    'Pld_Rec': $('#cod_padre').val(),
                    'Pla_Cod': $('#Pla_Cod option:selected').val(),
                    'ajaxCodigo': true
                }, function(response) {
                    if (response['valid'] === false) {
                        $("#cod_cuenta").alertMsg('El Codigo <b>' + $('#prefijo').html() + $('#cod_cuenta').val() + '</b> ya existe.').val(response['next']);
                    } else {
                        $("#cod_cuenta").alertMsg();
                    }
                }, 'json').fail(function(error) {
                    $.alert("El Servidor ha fallado en responder!");
                    $("#cod_cuenta").val('');
                })
                .always(function() {
                    $('.btn-guarda').removeAttr('disabled');
                });
        }

        //$("#cod_cuenta").val('').alertMsg('El Cheque <b>No. </b> ya existe.');
        function updatePlan() {
            //Activar btn expandir
            $('#btn_expandir').show();
            $('#plan-tittle').html($('#Pla_Cod option:selected').text());
            $treeview.jstree(true).settings.core.data = {
                'url': '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?planAjax=true&Pla_Cod=' + $('#Pla_Cod option:selected').val(),
                "dataType": "json"
            };
            $treeview.jstree(true).refresh();
            try {
                var estado = $('#Pla_Cod option:selected').data('estado');
                if (estado !== '') $('#plan-footer').html('El plan se encuentra: ' + ('Activo' === estado ? ' <span class="green bold">Activo</span>' : '<span class="red bold">Inactivo</span>'));
            } catch (e) {}
            resetGrid();
        }
        gridComp.createGrid({
            height: 180,
            caption: '&nbsp;Cuentas Hermanas',
            cmTemplate: {
                sortable: false,
                title: false
            },
            colModel: [{
                    label: 'C&oacute;d. Int.',
                    name: 'Pld_Cod',
                    key: true,
                    width: 25,
                    align: "center",
                    hidden: false
                },
                {
                    label: 'Codigo',
                    name: 'Pld_Cdc',
                    width: 45
                },
                {
                    label: 'Cuenta',
                    name: 'Pld_Des',
                    width: 150
                },
                {
                    label: 'Tipo',
                    name: 'Pld_Tip',
                    width: 50
                }
            ]
        }, true);

        function resetForm() {
            $('#formCuenta')[0].reset();
            $('#prefijo').html('');
            $("#cod_cuenta").clearMsg();
        }

        function resetGrid() {
            gridComp.clearGrid();
            gridComp.jqGrid('setGridParam', {
                datatype: 'json',
                postData: {
                    gridAjax: true,
                    Pla_Cod: $('#Pla_Cod option:selected').val(),
                    Pld_Cod: $('#cod_padre').val()
                }
            }).trigger("reloadGrid", [{
                page: 1
            }]);
        }
        $treeview.jstree({
                'core': {
                    'data': {}
                },
                "types": {
                    "default": {
                        "icon": "glyphicon glyphicon-folder-open yellow"
                    },
                    "raiz": {
                        "icon": "fa fa-hand-o-right red"
                    },
                    "grupo": {
                        "icon": "glyphicon glyphicon-folder-open blue"
                    },
                    "detalle": {
                        "icon": "fa fa-file-text green"
                    }
                },
                "plugins": ["types"]
            })
            .on('select_node.jstree', function(e, data) {
                resetForm();
                if (data.node.text !== '' && data.node.original.Pld_Tip === 'GRUPO') {
                    $('#cod_padre').val(data.node.id);
                    $('#des_padre').val(data.node.text);
                    $('#prefijo').html(data.node.text.split(" - ")[0] + '.');
                }
                updateCodigo();
                resetGrid();
            });
        $(document).ready(function() {
            $.createDialog('#planDialog', 200, 550);
        });

        function expandirTodo() {
            $treeview.jstree('open_all');
            $('#btn_expandir').hide();  // Oculta el botón de expandir
            $('#btn_contraer').show();
        }

        function contraerTodo() {
            $treeview.jstree('close_all');
            $('#btn_expandir').show();  // Oculta el botón de expandir
            $('#btn_contraer').hide();
        }

        /*.on('loaded.jstree', function() {
            $treeview.jstree('open_all');
        });*/
    </script>
</BODY>

</HTML>