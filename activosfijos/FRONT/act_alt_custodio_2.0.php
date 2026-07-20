<?php
/**
 * @abstract Permite realizar el registro de un perito
 * @author Jos� Ambulud�
 * @version 2.0
 * Fecha de creaci�n  2016-10-17
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_activo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Activo($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_Activo;
/* Secci�n para cargar datos en el Jqgrid referente a los activos */
if (isset($activoAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["custodio"] = "S"; //Esta variable se pasa con el fin de aderir una condici�n que es �nica cuando se va a reguistrar un custodio
    $data["Suc_Cod"] = $Ses_Suc_Cod;
    $contar = $obBD_con1->getRowConsulta(613, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0)
        $responce['rows'] = $obBD_con1->getArrayConsulta(613, $data, $obBD_conexion);
    echo json_encode($responce);
    exit();
}
/* Secci�n para cargar datos en el Jqgrid referente a los activos */
if (isset($personalAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(639, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0)
        $responce['rows'] = $obBD_con1->getArrayConsulta(639, $data, $obBD_conexion);
    echo json_encode($responce);
    exit();
}
/* Secci�n para guardar el custio y tablas aleda�as como: custodio,acta_activo y asignacion */
//ini_set('date.timezone','America/Guayaquil'); 
if (isset($saveCustodio)) {
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    //Insert en la tabla custodio
    $obBD_con1->operacionobBD(640, $Con_Cod . '*' . $Asg_Fec, $obBD_conexion);
    //Se obtiene el Cus_Cod insertado
    $Cus_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
    $response['Cus_Cod']=$Cus_Cod;
    $mayor = $obBD_con1->getRowConsulta(641, "", $obBD_conexion);
    $nuevo_Aca_Num = $mayor['Aca_Num'] + 1;
    //Insert en la tabla acta_activo
    $obBD_con1->operacionobBD(642, $nuevo_Aca_Num . '*' . $Aca_Fec . '*' . date("H:i:s"), $obBD_conexion);
    //Se obtiene el Aca_Cod de la tabla acta_activo
    $Aca_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
    $response['Aca_Num'] = $nuevo_Aca_Num;

     $asignacion = array(
                            "Asg_Cod"=>$Cus_Cod, 
                            "Asg_Typ"=>"C", 
                            "Aca_Cod"=>$Aca_Cod, 
                            "Asg_Fec"=>$Aca_Fec, 
                            "Asg_Hor"=>date("H:i:s"),
                            "Asg_Fas"=>$Asg_Fec,
                            "Asg_Raz"=>$Asg_Raz, 
                            "Asg_Con"=>$Asg_Con, 
                        );

    foreach ($activos as $valor) {
        $asignacion["Act_Cod"] = $valor['Act_Cod'];
        $obBD_con1->operacionobBD(712, $asignacion, $obBD_conexion);
    }

    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $response['success'] = true;
    }
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <style>
            #pager_activo_center{ display: none; }
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Asignar Custodio</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Formulario de Registro</legend>
                    <div class="form-group Titulos2">
                        <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                    </div>
                    <form id="formCustodio" name="formCustodio" class="form-horizontal normal" action="javascript:saveForm();">
                        <!--Clave principal de la tabla contratos_lab-->
                        <input type="hidden" id="Con_Cod" name="Con_Cod">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm">Acta Nro.:</label>
                                        <div class="col-md-4 col-sm-4">
                                            <input type="text" id="Aca_Num" name="Aca_Num" class="form-control input-xs" readonly="" placeholder="Autogenerado">
                                        </div>
                                        <div id="imprimir" class="col-md-4 col-sm-3" style="display: none;">
                                            <button type="button" name="bt_imprimir" id="bt_imprimir"  class="btn btn-success btn-xs"><span class="glyphicon glyphicon-print"></span> Imprimir</button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm required">Fecha Emisi&oacute;n:</label>
                                        <div class="col-md-4 col-sm-4">
                                            <input type="text" id="Aca_Fec" name="Aca_Fec" class="form-control input-xs" readonly="">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm required">Empleado:</label>
                                        <div class="col-md-8 col-sm-8">
                                            <div class="input-group input-group-xs">
                                                <input type="text" id="empleado" name="empleado" class="form-control input-xs" readonly="" placeholder="Seleccione un empleado">
                                                <span class="input-group-btn">
                                                    <button type="button" id="bt_buscar" name="bt_buscar" class="btn btn-success" onclick="$('#personalDialog').dialog('open');"><span class="glyphicon glyphicon-search" title="Buscar personal"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm required">Justificaci&oacute;n:</label>
                                        <div class="col-md-8 col-sm-8">
                                            <textarea id="Asg_Raz" name="Asg_Raz" class="form-control input-xs" rows="5" required="" style="resize: none;"></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm required">Fecha Entrega:</label>
                                        <div class="col-md-4 col-sm-4">
                                            <input type="text" id="Asg_Fec" name="Asg_Fec" class="form-control input-xs" readonly="">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm required">Asignaci&oacute;n:</label>
                                        <div class="col-md-4 col-sm-4">
                                            <select id="Asg_Con" name="Asg_Con" class="form-control input-xs">
                                                <option value="C">Confirmada</option>
                                                <option value="N">No Confirmada</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-3"></div>
                                        <div class="col-sm-4">
                                            <button type="submit" name="bt_guardar" id="bt_guardar"  class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <div class="col-md-4">
                                            <button type="button" name="bt_agregar" id="bt_agregar"  class="btn btn-success btn-xs" onclick="$('#activoDialog').dialog('open');"><span class="glyphicon glyphicon-plus"></span> Agregar</button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-10 col-sm-12">
                                            <table id="grid_activo"></table>
                                            <div id="pager_activo"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                </fieldset>
            </div>
        </div>
        <!-- Inicio del di�logo para buscar un activo --> 
        <div id="activoDialog" title="B&uacute;squeda de Activos">  
            <form class="form-horizontal normal"> 
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Filtros</legend>
                    <div class="form-group">
                        <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                        <div class="col-md-8 radioset" >
                            <input id="rad1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad1">&nbsp;&nbsp;Nombre&nbsp;&nbsp;</label>
                            <input id="rad2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad2">&nbsp;&nbsp;C&oacute;d. Barras&nbsp;&nbsp;</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">B&uacute;squeda:</label>
                        <div class="col-md-7" >                 
                            <div class="input-group">                        
                                <input name="search" onkeydown="if (event.keyCode === 13)this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese activo a buscar..." autofocus class="form-control input-sm " />
                                <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar activo" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                            </div>                         
                        </div>                    
                    </div>
                </fieldset>  
            </form>    
        </div>

        <!-- Inicio del di�logo para buscar un empleado --> 
        <div id="personalDialog" title="B&uacute;squeda de Empleados">  
            <form class="form-horizontal normal"> 
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Filtros</legend>
                    <div class="form-group">
                        <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                        <div class="col-md-8 radioset" >
                            <input id="rad3" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad3">&nbsp;&nbsp;Empleado&nbsp;&nbsp;</label>
                            <input id="rad4" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad4">&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">B&uacute;squeda:</label>
                        <div class="col-md-7" >                 
                            <div class="input-group">                        
                                <input name="search" onkeydown="if (event.keyCode === 13)
                                            this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese empleado a buscar..." autofocus class="form-control input-sm " />
                                <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar empleado" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                            </div>                         
                        </div>                    
                    </div>
                </fieldset>  
            </form>    
        </div>

        <script type="text/javascript">
            $(function () {
                $.createDatePickers('#Asg_Fec');
                $.createDatePickers('#Aca_Fec');
            });
            //Inicio del di�logo de activos
            $(function () {
                $.createSearchDialog('#activoDialog', [
                    {label: 'C�d.Int.', name: 'Act_Cod', key: true, hidden: true, viewable: true},
                    {label: 'Cod. Barras', name: 'Act_Bar', width: 60},
                    {label: 'Activo', name: 'Act_Des', width: 130},
                    {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            return $.getGridButton(cargarActivo, rowObject);
                        }
                    }
                ]);
            });

            //Funci�n para cargar datos del activo al grid y al array
            var activos = [];
            function cargarActivo(activo) {
                if (!$('#grid_activo').existsId(activo.Act_Cod))
                {
                    activos.push(activo);
                    $('#grid_activo').setRows(activos);
                }
            }

            //Inicio del di�logo de personal
            $(function () {
                $.createSearchDialog('#personalDialog', [
                    {label: 'C�d.Int.', name: 'Con_Cod', key: true, hidden: true, viewable: true},
                    {label: 'C&eacute;dula', name: 'Prs_Ced', width: 60},
                    {label: 'Empleado', name: 'empleado', width: 130},
                    {label: 'Departamento', name: 'Dep_Des', width: 80},
                    {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            var clic = '$("#empleado").val("' + rowObject.empleado + '");$("#Con_Cod").val("' + rowObject.Con_Cod + '");$("#personalDialog").dialog("close");';
                            return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\'' + clic + '\'><i class="glyphicon glyphicon-arrow-right"></span>';
                        }
                    }
                ]);
            });

            //Grid de activos a asignar
            $(function () {
                $("#grid_activo").jqGrid({
                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                    mtype: "GET", datatype: "local", regional: 'es',
                    responsive: true,
                    autowidth: true, shrinkToFit: true, height: 150,
                    cmTemplate: {sortable: false},
                    colModel: [
                        {label: 'Cod', key: true, hidden: true, name: 'Act_Cod', width: 150},
                        {label: 'C&oacute;d. Barras', name: 'Act_Bar', width: 110, align: 'center'},
                        {label: 'Activo', name: 'Act_Des', width: 250, align: 'center'},
                        {label: '&nbsp;', name: 'act1', width: 60, align: 'center',
                            formatter: function (cellvalue, options, rowObject) {
                                return  '<span id="eli' + options.rowId + '" class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="eliminarActivo(\'' + options.rowId + '\')";><i class="glyphicon glyphicon-trash"></i></span>';
                            }
                        }
                    ],
                    onSelectRow: function () {
                        $(this).resetSelection();
                    }, rowNum: 40, pager: "#pager_activo", gridview: true, rownumbers: true, viewrecords: false, altRows: true, altclass: "myAltRowClass"
                });
            });

            //Funci�n para eliminar un activo tanto del grid como del array
            function eliminarActivo(id) {
                $('#grid_activo').jqGrid('delRowData', id);
            }

            //Funci�n para guardar los datos
            var Cus_Cod=0;
            function saveForm() {
                var data = $('#formCustodio').serializeObject();
                var my_array = $("#grid_activo").jqGrid("getRowData");
                data['activos'] = my_array;
                data['saveCustodio'] = true;
                if (my_array.length > 0) {
                    $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>", data, function (response) {
                        if (response['success'] === true) {
                            $.alert("Transaccion Realizada con &Eacute;xito!");
                            $('#Aca_Num').val(response['Aca_Num']);
                            Cus_Cod=response['Cus_Cod'];
                            $('#imprimir').show();
                            $('#activoDialog').getDialogGrid().trigger('reloadGrid', [{page: 1}]);
                            $('#bt_guardar').attr('disabled', true);
                            $('#bt_agregar').attr('disabled', true);
                            $('#Asg_Raz').attr('disabled', true);
                        } else {
                            $.alert(response['message']);
                        }
                    }, 'json').fail(function (error) {
                        $.alert();
                    });
                } else {
                    $.alert('Debe seleccionar un activo');
                }
            }
            
            $(function(){
                $('#imprimir').click(function (){
                    window.open('./act_pri_custodio_3.0.php?Cus_Cod='+Cus_Cod,'_blank');
                });
            });
        </script>
    </BODY>
</HTML>
