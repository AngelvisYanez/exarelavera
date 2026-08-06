<?php

/**
 * Descripcion:          Modulo de Autorizaciones
 * Fecha de creacion:    Agosto 2017
 * Desarrollador:	Asael Tello
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_autorizaci_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/** 
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");
$mes = date("m");
/*
     * Save Autorizaci�n
     */
if (isset($saveAut)) {
    $data = $obBD_con1->getArrayConsulta(516, $_POST, $obBD_conexion);
    $obBD_con1->echoLog($data[0]['contador']);
    if ($data[0]['contador'] > 0) //guarda Inactivo
    {
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(517, $_POST, $obBD_conexion);
    } else // guarda Activo
    {
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(103, $_POST, $obBD_conexion);
    }

    if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) {
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
        $responce['message'] = "No se ha logrado realizar la Transacción";
    }

    $obBD_con1->echoJson($responce);
}

/*
     * Modify Autorizaci�n
     */
if (isset($modifyAut)) {
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(109, $_POST, $obBD_conexion);

    if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) {
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
        $responce['message'] = "No se ha logrado realizar la Transacción";
    }
    $obBD_con1->echoJson($responce);
}

/*
     * Get Punto by Sucursal
     */
if (isset($getPunto)) {
    $data['punto'] = $obBD_con1->getArrayConsulta(508, $_GET, $obBD_conexion);
    if (count($data['punto']) > 0) {
        $data['success'] = true;
    } else {
        $data['success'] = false;
    }
    $obBD_con1->echoJson($data);
}

/*
     * Get Tipo de Documento by Punto
     */
if (isset($getTipDoc)) {
    $data['tipDoc'] = $obBD_con1->getArrayConsulta(511, $_GET, $obBD_conexion);
    if (count($data['tipDoc']) > 0) {
        $data['success'] = true;
    } else {
        $data['success'] = true;
    }
    $obBD_con1->echoJson($data);
}

/*
     * Get data by Filters
     */
if (isset($searchFiltro)) {
    $data = $obBD_con1->getArrayConsulta(512, $_GET, $obBD_conexion);
    // Grid necesita este array
    $obBD_con1->echoJson(array(
        'rows' => $data,
        'total' => 1,
        'records' => count($data),
        'success' => true
    ));
}

/*
     * Enable or Disable state of Autorizacion
     */
if (isset($setEstado)) {
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(513, $_GET, $obBD_conexion);
    if ($_GET[Aut_Est] === "A") // desactiva todos los demas
    {
        $obBD_con1->operacionobBD(519, $_GET, $obBD_conexion);
    }

    if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) {
        $response['success'] = true;
    } else {
        $response['success'] = false;
        $response['message'] = "No se ha logrado realizar la Transaccion";
    }
    $obBD_con1->echoJson($response);
}

/*
     * Get User level by Emp_Cod and Suc_Cod
     */
$dataUser = $obBD_con1->getRowConsulta(518, array('Suc_Cod' => $Ses_Suc_Cod, 'Prs_Cod' => $Ses_Prs_Cod), $obBD_conexion);
/*
     * Get Tipos de Documentos (all)
     */
$tipDoc = $obBD_con1->getArrayConsulta(515, $Ses_Emp_Cod, $obBD_conexion);
$confi = $obBD_con1->getRowConsulta(520, $Ses_Emp_Cod, $obBD_conexion);
?>

<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Autorización Registrar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../VALIDACIONES/con_val_autorizaci_2.0.js?a=a17"></script>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar Autorización S.R.I.</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="lista" class="row">
                <div class="col-sm-12">
                    <div id="tabsSearch" class="ui-tab-fix ui-tabs">
                        <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                            <li><a href="#tabs-1">Gestion Autorización</a></li>
                        </ul>
                        <div id="tabs-1" style="min-height: 450px;">
                            <form id="frm_alt_auto" name="frm_alt_auto" class="form-vertical" autocomplete="off">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Busqueda Autorización</legend>

                                    <!-- Sucursal -->
                                    <div class="col-sm-2 col-sm-offset-1">
                                        <label class="col-sm-2 control-label col-sm">Sucursal</label>
                                        <select id="Suc_Cod" name="Suc_Cod" class="form-control col-sm" disabled>
                                            <?php
                                            echo "<option value='{$dataUser['Suc_Cod']}'>{$dataUser['Suc_Des']}</option>";
                                            ?>
                                        </select>
                                    </div>

                                    <!-- Vendedor Pto Impresion-->
                                    <div class="col-sm-4">
                                        <label class="control-label label-xs">Vendedor - Punto Impresion:</label>
                                        <select id="Pun_Cod" name="Pun_Cod" class="form-control col-sm">
                                            <option value="0"> Seleccionar ...</option>
                                            <?php
                                            echo "<option value='{$dataUser['Pun_Cod']}'>{$dataUser['Pun_Des']}</option>";
                                            ?>
                                        </select>
                                    </div>

                                    <!-- Tipo de Documento -->
                                    <div class="col-sm-3">
                                        <label class="control-label label-xs">Tipo de Documento:</label>
                                        <select id="Tic_Cod" name="Tic_Cod" class="form-control col-sm">
                                        </select>
                                    </div>

                                    <!-- Button -->
                                    <div class="col-sm-1">
                                        <label class="control-label" for="">Acción</label>
                                        <button id="btnSearch" name="btnSearch" class="btn btn-success">Buscar</button>
                                    </div>

                                    <div class="col-sm-3"></div>

                                </fieldset>
                            </form>
                            <div>
                                <TABLE id="tableResult">

                                </TABLE>
                                <div id="tableResultPager">

                                </div>
                                <BR>
                                <div class="col-sm-2">
                                    <button id="btnNueva" name="btnNueva" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-plus"></i> Nueva</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div id="editDialog" title="">
        <form id="formDialog" name="formDialog" class="form-horizontal" autocomplete="off">
            <fieldset>
                <div class="form-group Titulos2">
                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
                        <hr />
                    </div>
                </div>

                <!-- Cod Autorizacion-->
                <div>
                    <input type="text" id="Aut_Cod" name="Aut_Cod" hidden="true">
                </div>

                <!-- Punto - Vendedor -->
                <div class="form-group" id="Pun_Cod_d">
                    <label class="col-xs-4 control-label label-xs" for="Tic_Cod">Vendedor - Punto:</label>
                    <div class="col-xs-8">
                        <select id="Pun_Cod_n" name="Pun_Cod_n" class="form-control input-xs" hidden="true" disabled required="">
                        </select>
                    </div>
                </div>

                <!-- Tipo de Documento -->
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs required" for="Tic_Cod">Tipo Doc:</label>
                    <div class="col-xs-8">
                        <select id="Tic_Cod_n" name="Tic_Cod_n" class="form-control input-xs">
                            <?php
                            foreach ($tipDoc as $td) {
                                echo "<option data--tic_-sri='" . mb_convert_encoding($td['Tic_Sri'], 'UTF-8', 'ISO-8859-1') . "' value='" . $td['Tic_Cod'] . "'>" . mb_convert_encoding($td['Tic_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";

                                //echo "<option data--tic_-sri='$td[Tic_Sri]' value='{$td['Tic_Cod']}'>{$td['Tic_Des']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Tip Emision -->
                <div class="form-group">
                    <label class="col-md-4 control-label label-xs required" for="Aut_Tem">Tip. Emisión:</label>
                    <div class="col-md-8">
                        <select id="Aut_Tem" name="Aut_Tem" class="form-control input-xs">
                            <option value="N">Normal</option>
                            <option value="E" <?php echo (isset($confi['Cof_Gce']) && $confi['Cof_Gce'] == 'S' ? 'selected' : 'disabled') . '=""';  ?>>Electronica</option>
                        </select>
                    </div>
                </div>

                <!-- Autorizacion SRI-->
                <div class="form-group">
                    <label class="col-md-4 control-label label-xs required" for="Aut_Sri">Nú Autorización:</label>
                    <div class="col-md-6">
                        <input id="Aut_Sri" name="Aut_Sri" type="text" placeholder="" class="form-control input-xs" maxlength="10" onkeypress="return validar_numeric(event)" required="">
                    </div>
                </div>

                <!-- Punto SRI-->
                <div class="form-group">
                    <label class="col-md-4 control-label label-xs required" for="Aut_Pun">Punto S.R.I.:</label>
                    <div class="col-md-6">
                        <input id="Pun_Sri" name="Pun_Sri" type="text" placeholder="Ejem: 001" class="form-control input-xs" maxlength="3" onkeypress="return validar_numeric(event)">
                    </div>
                </div>

                <!-- Fecha Inicio-->
                <div class="form-group">
                    <label class="col-md-4 control-label label-xs required" for="Aut_Fci">Fecha Inicio:</label>
                    <div class="col-md-6">
                        <input id="Aut_Fci" name="Aut_Fci" type="text" placeholder="" class="form-control input-xs" value="<?PHP echo $hoy; ?>" />
                    </div>
                </div>

                <!-- Fecha Fin-->
                <div class="form-group">
                    <label class="col-md-4 control-label label-xs required" for="Aut_Cad">Fecha Fin:</label>
                    <div class="col-md-6">
                        <input id="Aut_Cad" name="Aut_Cad" type="text" placeholder="" class="form-control input-xs">
                    </div>
                </div>

                <!-- Secuencia Inicial-->
                <div class="form-group">
                    <label class="col-md-4 control-label label-xs required" for="Aut_Ini">Secuencia Inicial:</label>
                    <div class="col-md-6">
                        <input id="Aut_Ini" name="Aut_Ini" type="text" placeholder="" class="form-control input-xs" onkeypress="return validar_numeric(event)">
                    </div>
                </div>

                <!-- Secuencia Final-->
                <div class="form-group">
                    <label class="col-md-4 control-label label-xs required" for="Aut_Fin">Secuencia Final:</label>
                    <div class="col-md-6">
                        <input id="Aut_Fin" name="Aut_Fin" type="text" placeholder="" class="form-control input-xs" onkeypress="return validar_numeric(event) " onkeyup="validaRango();">
                    </div>
                </div>

                <!-- Numero de Items maximo-->
                <div class="form-group">
                    <label class="col-md-4 control-label label-xs required" for="Aut_Ima">Número de Items:</label>
                    <div class="col-md-6">
                        <input id="Aut_Ima" name="Aut_Ima" type="text" placeholder="" class="form-control input-xs" onkeypress="return validar_numeric(event)" value="20">
                    </div>
                </div>

                <!-- Documentos Minimos-->
                <div class="form-group">
                    <label class="col-md-4 label-xs control-label" for="Aut_Fin">Alerta 1:</label>
                    <div class="col-md-6">
                        <input id="Aut_Adv" name="Aut_Adv" type="text" placeholder="Dias de anterioridad en las cuales será informada la caducidad" class="form-control input-xs" onkeypress="return validar_numeric(event)">
                    </div>
                </div>

                <!-- Alerta 2-->
                <div class="form-group">
                    <label class="col-md-4 label-xs control-label" for="Aut_Fin">Alerta 2:</label>
                    <div class="col-md-6">
                        <input id="Aut_Ads" name="Aut_Ads" type="text" placeholder="Dias para mostrar una alerta antes que caduque la autorización" class="form-control input-xs" onkeypress="return validar_numeric(event)">
                    </div>
                </div>



                <!-- Alerta 2-->
                <div class="form-group">
                    <label class="col-md-4 label-xs control-label" for="Aut_Tpt">Socio transportista:</label>
                    <div class="col-md-6">
                        <input type="checkbox" id="Aut_Tpt" name="Aut_Tpt" value="S" onclick="ver_socios()">
                    </div>
                </div>

                <div class="form-group" id="label_socio" hidden>
                    <label class="col-md-4 label-xs control-label" for="Aut_Tpt">Socio :</label>
                    <div class="col-md-6">
                        <select name="Ext_Cod" id="Ext_Cod" class="form-control input-xs">
                            <?php
                            $socios_conductores = $obBD_con1->getArrayConsulta(521, $Ses_Emp_Cod, $obBD_conexion);
                            foreach ($socios_conductores  as $soc) { ?>
                                <option value="<?= $soc["Ext_Cod"]; ?>"><?php echo $soc["Ext_Nom"]; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>




                <!-- Buttons -->
                <div class="form-group">
                    <label class="col-md-4 control-label" for="btnModificar"></label>
                    <div class="col-md-8">
                        <button id="btnAccion" name="btnAccion" class="btn btn-sm btn-primary"></button>
                    </div>
                </div>

            </fieldset>
        </form>
    </div>
</BODY>

</HTML>