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

if (isset($cuenAjax)) {
    $obBD_con1->getPageGridJson(337, array($search, $Ses_Emp_Cod, $Pla_Cod, $op_opciones, $pld_id), $obBD_conexion, $page, $rows);
}
if (isset($saveCuenta)) {

    $obBD_con1->inicio_transaccion($obBD_conexion);
    $obBD_con1->operacionobBD(8, $cod_cuenta . '*' . $des_cuenta . '*' . $tip_cuenta . '***' . $pldCodigo, $obBD_conexion);
    if ($tip_cuenta != "D")
        $obBD_con1->operacionobBD(338, $codpla . '*' . $old_cuenta . '*' . $cod_cuenta, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
    }
    $obBD_con1->echoJson($responce);
}
if (isset($changeGrupo)) {
    $maximo = $obBD_con1->getRowConsulta(336, $Pla_Cod . '*' . $cod_padre, $obBD_conexion);
    $next = ('0' . $maximo['max']) * 1 + 1;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $obBD_con1->operacionobBD(338, $Pla_Cod . '*' . $old_cdc_padre . '*' . $cdc_padre . '.' . $next, $obBD_conexion);
    $obBD_con1->operacionobBD(339, $pld_id . '*' . $cod_padre, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
    }
    $obBD_con1->echoJson($responce);
}
if (isset($savePlan)) {
    $fecha = date("Y-m-d");
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $obBD_con1->operacionobBD(311, trim($des_plan) . '*' . 'A' . '*' . $Pla_Cod, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
    }
    $obBD_con1->echoJson($responce);
}
if (isset($listaPlanes)) {
    $row_rs_planes = $obBD_con1->getArrayConsulta(302, $Ses_Emp_Cod, $obBD_conexion);
    echo ' <option value="" data-estado="">Seleccione Plan...</option>';
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
if (isset($delete)) {
    //ChromePhp::log("PLD_CODIGO", $pldCodigo, $Pld_Cod);
    $rs_buscar = $obBD_con1->getRowConsulta(328, $Pld_Cod, $obBD_conexion);
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    if ((($rs_buscar['total']) * 1) == 0)
        $obBD_con1->grabarv_registros(sentencias_con(327, $obBD_con1->parametros($Pld_Cod)), $obBD_conexion->conexion);
    else
        $obBD_con1->grabarv_registros(sentencias_con(326, $obBD_con1->parametros($Pld_Cod)), $obBD_conexion->conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) $responce['success'] = true;
    else $responce['success'] = false;
    $responce['message'] = $obBD_con1->MsgError;
    echo json_encode($responce);
    exit();
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Plan Cuenta Modificar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
    <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
</HEAD>
<style>
    #btn_contraer {
        display: none;
    }

    #btn_expandir {
        display: none;
    }
</style>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Modificar Plan de Cuentas</h3>
        </div>

        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <div class="col-sm-6">

                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Plan de Cuentas</legend> <!-- Form Name -->
                            <form class="form-inline">
                                <div class="form-group">
                                    <label for="Pla_Cod" class="control-label label-xs">Seleccione Plan:</label>
                                    <?php $row_rs_planes = $obBD_con1->getArrayConsulta(302, $Ses_Emp_Cod . "* AND Pla_Est='A'", $obBD_conexion); ?>
                                    <select id="Pla_Cod" name="Pla_Cod" onchange="if($('#Pla_Cod option:selected').val()==='') {resetForm();$('#plan-footer').html('&nbsp;');gridComp.clearGrid();$('#change').attr('disabled','disabled');} updatePlan();" class="form-control input-sm">
                                        <option value="" data-estado="">Seleccione Plan...</option>
                                        <?php foreach ($row_rs_planes as $row) { ?>
                                            <option value="<?php echo $row['Pla_Cod']; ?>" data-estado="<?php echo $row['Pla_Est']; ?>"><?php echo $row['Pla_Obs']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <button onclick="if($('#Pla_Cod option:selected').val()!==''){$('#PlanCuen').val($('#Pla_Cod option:selected').text());$('#planDialog').dialog('open'); }else{$.alert('Selecione el Plan de Cuentas');}" type='button' class="btn btn-sm btn-success"><span class="fa fa-pencil"></span></button>
                                </div>
                            </form>
                            <div class="separator"></div>
                            <div class="panel panel-success exa-panel">
                                <div class="panel-heading"><i class="fa fa-list-ol"></i>&nbsp;&nbsp;<span id="plan-tittle"></span>

                                    <!-- Botón para expandir todo el árbol -->
                                    <button id="btn_expandir" onclick="expandirTodo();" class="btn btn-success btn-xs pull-right" style="float: right; margin-top: 0px; margin-right: -10px; padding: 2px 6px; font-size: 0.8em;">
                                        <span class="glyphicon glyphicon-resize-full" style="font-size: 10px;"></span> Expandir todo
                                    </button>

                                    <button id="btn_contraer" onclick="contraerTodo();" class="btn btn-success btn-xs pull-right" style="float: right; margin-top: 0px; margin-right: -10px; padding: 2px 6px; font-size: 0.8em;">
                                        <span class="glyphicon glyphicon-resize-small" style="font-size: 10px;"></span> Contraer todo
                                    </button>

                                    <button id="change" class="btn btn-success btn-xs pull-right" onclick="if($('#Pla_Cod option:selected').val()!==''&&$('#cod_padre').val()!==''&&$('#cod_padre').val()!=='0') {$.Search('cuen');$('#cuenDialog').dialog('open');} else $('#change').attr('disabled','disabled')" type="button" disabled="" title="Cambiar el Grupo Padre"><span class="glyphicon glyphicon-check"></span> Cambiar Grupo</button>
                                    <form id="changeForm" style="display:none">
                                        <input type="text" name="Pla_Cod" />
                                        <input type="text" name="pld_id" />
                                        <input type="text" name="cdc_padre" />
                                        <input type="text" name="old_cdc_padre" />
                                    </form>
                                </div>
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

                            <form id="formCuenta" class="form-horizontal normal" action="javascript:if($('#pldCodigo').val()!==''){$.createDialogConfirm(null,null,saveForm);}else $.alert('Selecciona una cuenta para editar!');">

                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm" for="des_padre">Cuenta Padre:</label>
                                    <div class="col-sm-9">
                                        <input id="cod_padre" name="cod_padre" type="text" readonly style="display: none" value="" />
                                        <input id="des_padre" name="des_padre" type="text" placeholder="" class="form-control input-sm bold" readonly value="Raíz de Plan" />

                                    </div>
                                </div>

                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="cod_cuenta">Codigo:</label>
                                    <div class="col-sm-4">
                                        <input type='text' value="" id="pldCodigo" name="pldCodigo" style="display: none" />
                                        <input type='text' value="" id="old_cuenta" name="old_cuenta" style="display: none" />
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
                                        <span id="auxTipo" class="form-control input-sm" style="display: none"></span>
                                    </div>
                                    <div class="col-sm-5 msgDiv vcenter">
                                        <img class="imgMsg"><label class="lblMsg"></label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Acci&oacute;n:</label>
                                    <div class="col-sm-9">
                                        <button type="submit" class="btn btn-primary btn-guarda"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                        <button type="button" onclick="resetForm();resetGrid();" class="btn btn-warning"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                                        <button type="button" onclick="borraCuenta();" class="btn btn-danger" style="float:right; margin-right:20px;"><span class="glyphicon glyphicon-remove"></span> Anular</button>
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

    <div id="planDialog" title="Editar Plan de Cuentas">
        <div class="row">
            <div class="form-horizontal normal col-sm-12">
                <fieldset>
                    <legend><label class="Titulos2">Datos de Plan de Cuentas</label></legend>
                    <form id="formPlan" class="form-horizontal normal" action="javascript:$.createDialogConfirm(null,null,savePlan);">
                        <div class="form-group">
                            <label class="col-sm-3 control-label label-sm required">Descripción:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control input-sm" id="PlanCuen" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Acción:</label>
                            <div class="col-sm-9">
                                <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
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
            gridComp = $("#comp"),
            Pld_Cdc_Aux = '';

        function savePlan() {
            $.saveDataJson("", {
                savePlan: true,
                des_plan: $('#PlanCuen').val(),
                Pla_Cod: $('#Pla_Cod option:selected').val()
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
            if ($('#Pla_Cod option:selected').val() === '') return $.alert('Selecione el Plan de Cuentas');
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

        function changeGroup(cta) {
            var data = $('#changeForm').serializeObject();
            $.extend(data, {
                changeGrupo: true,
                cdc_padre: cta['Pld_Cdc'],
                cod_padre: cta['Pld_Cod']
            });
            //console.log(data); return;
            $.saveDataJson("", data, function(r) {
                resetForm();
                resetGrid();
                $treeview.jstree(true).refresh();
                $('#cuenDialog').dialog('close');
            });
        }

        function updateCodigo() {
            $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>', {
                'Pld_Cod': $('#prefijo').html() + $('#cod_cuenta').val(),
                'pre': $('#prefijo').html(),
                'Pla_Cod': $('#Pla_Cod option:selected').val(),
                'Pld_Rec': $('#cod_padre').val(),
                'ajaxCodigo': true
            }, function(response) {
                $("#cod_cuenta").val(response['next']);
            }, 'json').fail(function(error) {
                $.alert("El Servidor ha fallado en responder!");
            });
        }

        function validaCodigo() {
            if ($('#prefijo').html() + $('#cod_cuenta').val() !== Pld_Cdc_Aux) {
                $('.btn-guarda').attr('disabled', 'disabled');
                $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>', {
                        'Pld_Cod': $('#prefijo').html() + $('#cod_cuenta').val(),
                        'pre': $('#prefijo').html(),
                        'Pla_Cod': $('#Pla_Cod option:selected').val(),
                        'Pld_Rec': $('#cod_padre').val(),
                        'ajaxCodigo': true
                    }, function(response) {
                        if (response['valid'] === false) {
                            resetCodigo();
                        } else {
                            $("#cod_cuenta").alertMsg();
                        }
                    }, 'json').fail(function(error) {
                        resetCodigo();
                        $.alert("El Servidor ha fallado en responder!");
                        $("#cod_cuenta").alertMsg();
                    })
                    .always(function() {
                        $('.btn-guarda').removeAttr('disabled');
                    });
            } else $("#cod_cuenta").alertMsg();
        }

        function resetCodigo() {
            var num = Pld_Cdc_Aux.split('.');
            $("#cod_cuenta").alertMsg('El Codigo <b>' + $('#prefijo').html() + $('#cod_cuenta').val() + '</b> ya existe.').val(num[num.length - 1]);
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
            $("input[name='Pla_Cod']").val($('#Pla_Cod option:selected').val());
        }

        function borraCuenta() {


            console.log("ENTRO", $('#pldCodigo').val())
            var confirmacion = confirm("¿Estás seguro de que deseas eliminar información del plan de cuentas.?");
            if (confirmacion) {
                $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", {
                    Pld_Cod: $('#pldCodigo').val(),
                    delete: true
                }, function(response) {
                    if (response['success'] === true) {
                        $.alert("Detalle de plan anulado con &Eacute;xito!");
                        resetForm()
                        resetGrid()
                        // CARGAR DE NUEVO EL ARBOL AQUI
                        $treeview.jstree(true).refresh();
                    } else {
                        $.alert(response['message']);
                    }
                }, 'json').fail(function(error) {
                    $.alert("El Servidor ha fallado en responder!");
                });
            } else {
                $.alert("Acción cancelada.");
            }
        }

        gridComp.createGrid({
            height: 180,
            caption: '&nbsp;Cuentas Hermanas',
            cmTemplate: {
                sortable: false,
                title: false
            },
            colModel: [{
                    label: 'Cód.Int.',
                    name: 'Pld_Cod',
                    key: true,
                    width: 25,
                    align: "center",
                    hidden: true
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
                //if(data.node.text!==''&&data.node.original.Pld_Tip==='GRUPO'){
                if (data.node.original.parent === '#') {
                    $('#des_padre').val('Raíz del Plan');
                    $('#prefijo').html('');
                } else {
                    $('#des_padre').val(data.instance.get_node(data.node.parent).text);
                    $('#prefijo').html(data.instance.get_node(data.node.parent).text.split(" - ")[0] + '.');
                }
                if (data.node.original.Pld_Tip === 'GRUPO') $('#tip_cuenta').val('G');
                else $('#tip_cuenta').val('D');
                if (data.node.children_d.length > 0) {
                    $("#tip_cuenta").alertMsg('No se puede cambiar el tipo porque posee <b>Sub-Cuentas</b>.');
                    $('#tip_cuenta').css('display', 'none');
                    $('#auxTipo').html('Grupo');
                    $('#auxTipo').css('display', 'block');
                } else {
                    $('#tip_cuenta').css('display', 'block');
                    $('#auxTipo').css('display', 'none');
                    $("#tip_cuenta").clearMsg();
                }
                var cuenta = data.node.text.split(" - ");
                var num = cuenta[0].split(".");
                if (data.node.parent === '#') $('#cod_padre').val('0');
                else $('#cod_padre').val(data.node.parent);
                $('#pldCodigo').val(data.node.id);
                $('#old_cuenta').val(cuenta[0]);

                $('#cod_cuenta').val(num[num.length - 1]);
                $('#des_cuenta').val(cuenta[1]);
                Pld_Cdc_Aux = $('#prefijo').html() + $('#cod_cuenta').val();

                $("input[name='old_cdc_padre']").val(cuenta[0]);
                $("input[name='pld_id']").val(data.node.id);

                if ($('#cod_padre').val() !== '0') $('#change').removeAttr('disabled');
                else $('#change').attr('disabled', 'disabled');
                //}
                resetGrid();
            });
        $(document).ready(function() {
            $.createDialog('#planDialog', 200, 550);
        });
        /*.on('loaded.jstree', function() { $treeview.jstree('open_all'); });*/
    </script>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas"></div>
    <script>
        $.createSearchDialog('cuenDialog', [{
                    label: 'C&oacute;d.Int.',
                    name: 'Pld_Cod',
                    key: true,
                    width: 15,
                    align: "center",
                    hidden: true
                },
                {
                    label: 'Codigo',
                    name: 'Pld_Cdc',
                    width: 45
                },
                {
                    label: 'Cuenta',
                    name: 'Pld_Des',
                    width: 80,
                    cellattr: function() {
                        return 'style="white-space: normal;"';
                    }
                },
                {
                    label: 'Grupo',
                    name: 'Pld_Grupo',
                    width: 110,
                    cellattr: function() {
                        return 'style="white-space: normal;"';
                    }
                },
                {
                    label: 'Tipo',
                    name: 'Pld_Tip',
                    width: 30,
                    align: "center"
                },
                {
                    label: 'Estado',
                    name: 'Pld_Est',
                    width: 30,
                    align: "center"
                },
                {
                    label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                    name: 'act1',
                    width: 30,
                    align: 'center',
                    viewable: false,
                    formatter: 'gridButton',
                    formatoptions: {
                        action: '$.createDialogConfirm(\'Esta Seguro que desea cambiar la cuenta padre?\',$(this).data("originaldata"),changeGroup)'
                    }
                }
            ], null, null, null, null, {
                title: 'Cuenta',
                options: [{
                    label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',
                    value: 'd'
                }, {
                    label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',
                    value: 'c'
                }]
            })
            .find('.form-group-options').append('<div class="col-md-4" style="display:none"><input name="periodo" type="text" /><input name="Pla_Cod" type="text" /><input type="text" name="pld_id" /> </div>');


        function expandirTodo() {
            $treeview.jstree('open_all');
            $('#btn_expandir').hide(); // Oculta el botón de expandir
            $('#btn_contraer').show();
        }

        function contraerTodo() {
            $treeview.jstree('close_all');
            $('#btn_expandir').show(); // Oculta el botón de expandir
            $('#btn_contraer').hide();
        }
    </script>
    <!-- FIN DEL DIALOGO CUENTAS-->
</BODY>

</HTML>