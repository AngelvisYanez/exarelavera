<?php

/**
 * @abstract Permite registrar las negociaciones en la compra/venta de productos de camaronera.
 * @author Wilson Belduma.
 * @version 1.0
 * Fecha de creaicón: 25/01/2025
 *
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/cam_log_negociacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Cam($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_datos_Cam();

if (isset($saveTallas)) {
    try {
        $data_tallas = array('Cod_Tall' => $Cod_Tall, 'Talla' => $talla, 'Tip' => $Tip, 'Tip_Med' => $Tip_Med, 'Tall_Est' => 'A');
        $obBD_con1->operacionobBD(42, $data_tallas, $obBD_conexion);
        $response = array('success' => true, 'message' => "Transaccion realizada con éxito");
    } catch (Exception $e) {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }
    echo json_encode($response);
    exit();
}

if (isset($loadTallasAjax)) {

    if (!empty($search)) {
        $search = " AND  (Talla='$search' || Tip ='$search')";
    }
    $responce = $obBD_con1->getArrayConsulta(43, $Ses_Emp_Cod . '*' . $search, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}

if (isset($anularTallasAjax)) {
    try {
        $obBD_con1->getArrayConsulta(44, $Ses_Emp_Cod . '*' . $Cod_Tall, $obBD_conexion);
        $response = array('success' => true, 'message' => "Transaccion realizada con éxito");
    } catch (Exception $e) {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }
    echo json_encode($response);
    exit();
}

?>
<!DOCTYPE html>
<HTML>

<head>
    <title>
        <?Php echo $Ses_Sys_Nom; ?>
    </title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script> </script>
    <style></style>
</head>

<body>
    <div class="panel panel-main" id="formFinal">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registro/Editar Tallas de Camarón</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="panels-area form-horizontal normal ">
                    <div class="col-sm-6">
                        <fieldset class="exa-fieldset" id="provFormTemp">
                            <button class="btn btn-xs btn-success" onclick="nuevoTalla()"><i class="fa fa-plus"></i> Nuevo</button>
                        </fieldset>
                        <fieldset class="exa-fieldset" id="provFormTemp">
                            <legend class="Titulos2">Datos de las tallas</legend>
                            <form id="frm_talla" name="frm_talla" class="form-horizontal normal" action="javascript:validaDocumentTalla();">
                                <input type="hidden" id="Cod_Tall" name="Cod_Tall">
                                <div class="col-xs-12">
                                    <div class="form-group">
                                        <label class="col-xs-1 control-label label-xs">Talla:</label>
                                        <div class="col-xs-3">
                                            <input name="talla" id="talla" type="text" class="form-control input-xs ">
                                        </div>
                                        <label class="col-xs-1 control-label label-xs">Tipo:</label>
                                        <div class="col-xs-3">
                                            <select name="Tip" id="Tip" class="form-control input-xs">
                                                <option value="ENTERO">Entero</option>
                                                <option value="COLAA">Cola A</option>
                                                <option value="COLAB">Cola B</option>
                                                <option value="NACIONAL">Nacional</option>
                                            </select>
                                        </div>
                                        <label class="col-xs-1 control-label label-xs">Medida:</label>
                                        <div class="col-xs-3">
                                            <select name="Tip_Med" id="Tip_Med" class="form-control input-xs">
                                                <option value="Kilos">Kilos</option>
                                                <option value="Libras">Libras</option>
                                            </select>
                                        </div>
                                    </div>
                                </div><br>
                                <div class="form-group">
                                    <div class="center">
                                        <button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                    </div>
                                </div>
                            </form>
                        </fieldset>
                    </div>
                    <div class="col-sm-6">
                        <form id="frm_prod_tall" name="frm_prod_tall" class="form-horizontal normal" action="javascript:$('#container').Search('#frm_prod_tall','loadTallasAjax'); ">
                            <fieldset class="exa-fieldset" id="prodFormTemp">
                                <legend class="Titulos2">B&uacute;squeda</legend>
                                <div class="form-group col-md-12">
                                    <label class="col-sm-2 control-label">B&uacute;squeda:</label>
                                    <div class="col-sm-10">
                                        <div class="input-group">
                                            <input id="search" name="search" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
                                            <span class="input-group-btn">
                                                <button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="-1">
                                                    <span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                        <div class="row">
                            <div class="col-sm-12">
                                <div>
                                    <table id="container"></table>
                                    <div id="containerPager"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../VALIDACIONES/cam_val_tallas.js?k=112"></script>
    <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</body>

</html>