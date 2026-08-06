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
require_once('../LOGICA/adq_log_provee.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Tes
 */
$obBD_conexion = new Class_Log_Conexion_Prv($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Tes
 */
$obBD_con1 = new Class_Log_Datos_Prv;


if (isset($cuenAjax)) {

    $Cop_Fec = $hoy;
    $data = $_GET;
    $data['Cop_Fec'] = $Cop_Fec;

    $configs = $obBD_con1->getRowConsulta(88, $Ses_Emp_Cod, $obBD_conexion);
    if ($configs['Cof_Con'] == 'S' && !empty($Cop_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(99, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
    $contar = $obBD_con1->getRowConsulta(47, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data['limits'] = $pagination['limits'];

    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(47, $data, $obBD_conexion);
        if ($configs['Cof_Con'] == 'S' && !empty($Pec_Cop['Pla_Cod'])) {
            foreach ($responce['rows'] as &$r) {
                $cuenta = $obBD_con1->getRowConsulta(60, $Pec_Cop['Pla_Cod'] . '*' . $r['Ren_Cod'] . '*C', $obBD_conexion);
                if (!empty($cuenta['Pld_Cod'])) $r = array_merge($r, $cuenta);
            }
            unset($r);
        }
    }
    $obBD_con1->echoJson($responce);
}


/* Secci�n para listar los Proveedores registrados dentro de la empresa */
if (isset($proveedoreAjax)) {
    $data = $_GET;
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $obBD_con1->getPageGridJson(1, $data, $obBD_conexion);
}

/* ver si exite una persona con el mismo numero de cedula que se desea registar al Proveedor */
if (isset($searchProveedor)) {
    $responce = $obBD_con1->getRowConsulta(5, $Prs_Ced, $obBD_conexion, true);
    (!empty($responce['Prs_Cod'])) ? $responce['exisPer'] = true : $responce['exisPer'] = false;
    $obBD_con1->echoJson($responce);
}

/* Actualizar Proveedor*/
if (isset($editarProvAjax)) {
    //$obBD_con1->debug(true);
    $data = $_POST;
    $data['Emp_Cod'] = $Ses_Emp_Cod;

    $obBD_con1->inicio_transaccion($obBD_conexion);
    //ACTUALIZA CAMPOS DE PROVEEDOR
    $obBD_con1->operacionobBD(4, $data, $obBD_conexion);
    //ACTUALIZA CAMPOS DE PERSONA
    $obBD_con1->operacionobBD(6, $data, $obBD_conexion);


    $obBD_con1->operacionobBD(11, $data, $obBD_conexion);
    $obBD_con1->operacionobBD(12, $data, $obBD_conexion);

    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true, 'prov' => $data);
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', error => $obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($responce);
}

$rs_tip_compr = $obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('clean' => true, 'where' => array('Tic_Est' => 'A')), $obBD_conexion);

?>

<!DOCTYPE html>
<HTML>

<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>

    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script type="text/javascript" src="../../framework/plugins/cedulaRuc.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/adq_val_proveedor.js"></script>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
    <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>


</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Modificar Proveedores</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="lista" class="row">
                <div class="col-md-12">
                    <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:$('#Lis_Proveedor').Search('#frm_bus','proveedoreAjax');">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">B&uacute;squeda de Proveedores</legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                <div class="col-sm-5 radioset">
                                    <input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)" /><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                                    <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" /><label for="rad_ba2">&nbsp;&nbsp;Proveedor&nbsp;&nbsp;</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                <div class="col-sm-5">
                                    <div class="input-group">
                                        <input type="text" id="search" name="search" onkeydown="if (event.keyCode === 13)
                                                        this.form.submit()" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="button" title="Buscar Proveedor" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                    <div style="min-height: 300px;">
                        <table id="Lis_Proveedor"></table>
                        <div id="Pag_Cli"></div>
                    </div>
                </div>
            </div>


            <!--FORMULARIO DE MODIFICACION DE PROVEEDORES-->
            <div id="modificar" class="row" style="display: none;">

                <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
                <div id="cuenDialog" title="B&uacute;squeda de Codigos de Retencion">
                    <form class="form-horizontal normal">
                        <fieldset>
                            <legend>Filtros</legend>
                            <div class="form-group">
                                <label class="col-md-2 control-label label-xs">Filtrar Por:</label>
                                <div class="col-md-7 radioset">
                                    <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                                    <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>
                                    <input id="radc3" name="op_opciones" type="radio" value="p" onclick="setfocus(this.form.search)" alt="" /><label for="radc3">&nbsp;&nbsp;Porcentaje&nbsp;&nbsp;</label>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="tipo" class="hidden" />
                                    <input type="text" name="index" class="hidden" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-2 control-label">B&uacute;squeda:</label>
                                <div class="col-md-7">
                                    <div class="input-group">
                                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus class="form-control input-sm " />
                                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                    </div><!-- /input-group -->
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>

                <div class="col-sm-3"></div>
                <div class="col-md-6 col-sm-8">
                    <form class="form-horizontal normal" id="formProvedor" action="
                              javascript:if(validaNoIdentif($('#Prs_Ced').val())['success'])
                              { EditaProveedor() }">
                        <input name="Prs_Cod" type="text" class="hidden" />
                        <input name="Prv_Cod" type="text" class="hidden" />
                        <input name="Prs_Ced_Ant" id="oldcedula" type="text" class="hidden" />
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del Proveedor</legend>


                            <div class="col-md-7">

                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                                    <div class="col-xs-9">
                                        <div class="input-group input-group-xs">
                                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" data-trigger="true" onchange="if (validaNoIdentif(this.value)['success']) {
        $('#Ide_Cod').val(this.value.length === 10 ? 2 : 1);
        $('#Prv_Tic').val(validaNoIdentif(this.value)['tipo_abrev'] === 'NA' ? 'N' : 'J').trigger('change');
        $(this).fieldValid(true);
        /*searchProveedor($('#Prs_Ced').val(),'ec');*/
    } else {
        $('#Ide_Cod').val('');
        $('#Prv_Tic').val('');
        $(this).fieldValid(false, validaNoIdentif(this.value)['message']);
    }
    ;" required="" readonly="" />

                                            <span class="input-group-addon validate"><i id="ch"></i></span>
                                            <span class="input-group-addon alert-info"><input id="isRuc" type="checkbox" value="S" offval="N" style="vertical-align: middle;" onchange="setTipoDoc();"><b> RUC</b></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Documento:</label>
                                    <div class="col-xs-9">
                                        <?php $rs_identi = $obBD_con1->getArrayConsulta(3, '', $obBD_conexion); ?>
                                        <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                                            <option value=""></option>
                                            <?php foreach ($rs_identi as $row) {
                                                echo "<option value='{$row['Ide_Cod']}'>{$row['Ide_Des']}</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Contribuyente:</label>
                                    <div class="col-xs-4">
                                        <select id="Prv_Tic" name="Prv_Tic" class="form-control input-xs" required="" onchange="if (this.value === 'N') {
                $('.juridico').hide();
                $('.natural').show();
                } else {
                $('.natural').hide();
                $('.juridico').show();
                }">
                                            <option value="N">NATURAL</option>
                                            <option value="J">JURIDICO</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Razón Social:</span></label>
                                    <div class="col-xs-9"><input name="Prs_Ape" id="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                                </div>
                                <div class="form-group natural">
                                    <label class="col-xs-3 control-label label-xs">Nombres:</label>
                                    <div class="col-xs-9"><input name="Prs_Nom" id="Prs_Nom" type="text" class="form-control input-xs" /></div>
                                </div>
                                <div class="form-group natural">
                                    <label class="col-xs-3 control-label label-xs ">Genero:</label>
                                    <div class="col-xs-4">
                                        <select name="Prs_Sex" id="Prs_Sex" class="form-control input-xs ">
                                            <option value="M">MASCULINO</option>
                                            <option value="F">FEMENINO</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs ">Nomb.Comerc.:</label>
                                    <div class="col-xs-9"><input name="Prv_Com" id="Prv_Com" type="text" class="form-control input-xs" /></div>
                                </div>
                            </div>


                            <div class="col-md-5">
                                <div class="form-group">
                                    <div class="col-xs-12">
                                        <div class="checkbox check-big" style="position:absolute;">
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Esp" value="S" offval="N">Contrib. Especial</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Reg" value="S" offval="N" disabled>Reg. Micro.</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Con" value="S" offval="N">Obligado Contab.</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Ris" value="S" offval="N" disabled>RISE</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Rim_Emp" value="S" offval="N" onclick="toggleCheckbox('Prv_Rim_Np')">RIMPE Emprendedor</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Rim_Np" value="S" offval="N" onclick="toggleCheckbox('Prv_Rim_Emp')">RIMPE Neg. Popular</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Ag_Ret" value="S" offval="N">Agente Retención</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Gct" value="S" offval="N">Grande Contribuyente</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </fieldset>


                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de Ubicación</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                                <div class="col-xs-4">
                                    <select name="Ciu_Cod" id="Select_Ciudad" class="form-control input-xs" required="">
                                        <?php $rs_ciudad = $obBD_con1->getArrayConsulta(2, '', $obBD_conexion); ?>
                                        <option value=""></option>
                                        <?php
                                        foreach ($rs_ciudad as $row) {
                                            echo "<option value='{$row['Ciu_Cod']}' data-prov='{$row['Pro_Nom']}' data-pai='{$row['Pas_Nom']}'>{$row['Ciu_Des']}</option>";
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
                                <label class="col-xs-3 control-label label-xs ">Teléfono:</label>
                                <div class="col-xs-4"><input name="Prv_Tel" type="text" class="form-control input-xs" pattern="\d*" /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs ">Mail:</label>
                                <div class="col-xs-5"><input name="Prv_Cor" type="email" class="form-control input-xs" multiple required="" /></div>
                            </div>
                        </fieldset>

                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de Autorizaci&oacute;n</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs ">Sustento:</label>
                                <div class="col-xs-10">
                                    <?php $rs_sustento = $obBD_con1->getArrayConsulta('sustento.selectWhere', array('clean' => true, 'where' => array('Tri_Est' => 'A')), $obBD_conexion); ?>
                                    <select name="Tri_Cod" class="form-control input-xs" tabindex="3">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($rs_sustento as $row) {
                                            echo "<option value='{$row['Tri_Cod']}' " . ($row['Tri_Cod'] == 2 ? 'selected' : '') . ">{$row['Tri_Sri']} - {$row['Tri_Des']}</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs ">Documento:</label>
                                <div class="col-xs-5">
                                    <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs" tabindex="4" data-trigger="">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($rs_tip_compr as $row) {
                                            if ($row['Tic_Sri'] != 4 && $row['Tic_Sri'] != 5 && $row['Tic_Sri'] != 7 && $row['Tic_Sri'] != 23 && $row['Tic_Sri'] != 24)
                                                echo "<option value='{$row['Tic_Cod']}' data-ticsri='{$row['Tic_Sri']}'>{$row['Tic_Sri']} - {$row['Tic_Des']}</option>";
                                        } ?>
                                    </select>
                                </div>

                                <label class="col-xs-2 control-label label-xs ">Impresión:</label>
                                <div class="col-xs-3">
                                    <div class="input-group">
                                        <input id="Prd_Imp" name="Prd_Imp" type="text" class="form-control input-xs datepickers empty" tabindex="9" />
                                        <span class="input-group-addon input-xs" title="Fecha de Creación en Imprenta"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                    </div>
                                </div>

                            </div>

                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs ">Autoriza:</label>
                                <div class="col-xs-5">
                                    <div class="input-group input-group-xs">
                                        <input id="Prd_Aut" type="text" name="Prd_Aut" class="form-control datatitle datatrigger" tabindex="6" maxlength="49" pattern="\d{10}|\d{37}|\d{49}" />
                                        <span class="input-group-addon validate"><i></i></span>
                                    </div>
                                </div>

                                <label class="col-xs-2 control-label label-xs ">Caducidad:</label>
                                <div class="col-xs-3">
                                    <div class="input-group">
                                        <input id="Prd_Cad" name="Prd_Cad" type="text" class="form-control input-xs datepickers" tabindex="10" />
                                        <span class="input-group-addon input-xs" title="Fecha de Caducidad en el SRI"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs ">Ciudad:</label>
                                <div class="col-xs-5">
                                    <?php $rs_ciudad = $obBD_con1->getArrayConsulta('ciudad.selectWhere', array('clean' => true, 'join' => array('provincia' => array('on' => 'provincia.Pro_Cod=ciudad.Pro_Cod', 'cols' => 'Pro_Nom')), 'where' => "Ciu_Des != ''", 'order' => 'Ciu_Des'), $obBD_conexion); ?>
                                    <select name="Ciu_Cod_Aut" id="Ciu_Cod_Aut" class="form-control input-xs" data-placeholder="Seleccione..." tabIndex="7">
                                        <option value=""></option>
                                        <?php foreach ($rs_ciudad as $row) {
                                            echo "<option value='{$row['Ciu_Cod']}' data-prov='{$row['Pro_Nom']}'>{$row['Ciu_Des']}</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Codigo Renta e Iva</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs ">Renta:</label>
                                <div class="col-xs-3 input-group input-group-xs ret"><input class="form-control" type="text" id="codRenta" name="Ren_Cod_Ren" value="" readonly="true"><span class="input-group-btn" data-originaldata='{"tipo":"R","index":"1","op_opciones":"p","checkRentaIva":"N"}'><button type="button" onclick="seleccionaRetencion($(this).parent().data('originaldata'));" class="btn btn-info" title="Agregar Imp. a la Renta" tabindex="-1"><i class="glyphicon glyphicon-plus"></i></button></span></div>
                                <br />
                                <label class="col-xs-2 control-label label-xs ">IVA:</label>
                                <div class="col-xs-3 input-group input-group-xs ret"><input class="form-control" type="text" id="codIva" name="Ren_Cod_Iva" value="" readonly="true"><span class="input-group-btn" data-originaldata='{"tipo":"I","index":"1","op_opciones":"p","checkRentaIva":"N"}'><button type="button" onclick="seleccionaRetencion($(this).parent().data('originaldata'));" class="btn btn-info" title="Agregar Ret. del Iva" tabindex="-1"><i class="glyphicon glyphicon-plus"></i></button></span></div>
                            </div>
                        </fieldset>

                        <div class="center">
                            <button type="button" onclick="$('#modificar').moveComp('#lista').updateGridsSizes();" class="btn btn-inverse fileinput-button btn-sm"><span class="glyphicon glyphicon-arrow-left"></span> Atr&aacute;s</button>
                            <button type="button" onclick="$(this.form).formSubmit();" class="btn btn-sm btn-primary no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>

                        </div>
                        <div class="Titulos2">
                            <hr><b>NOTA:</b> Los campos marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        $.createSearchDialog('cuenDialog', [{
                label: 'C&oacute;d.Int.',
                name: 'Ren_Cod',
                key: true,
                width: 25,
                align: "center"
            },
            {
                label: 'C&oacute;digo',
                name: 'Ren_Sri',
                width: 25,
                align: "center"
            },
            {
                label: 'Descripci&oacute;n',
                name: 'Ren_Con',
                width: 100
            },
            {
                label: 'Porc.(%)',
                name: 'Ren_Por',
                width: 25,
                align: "center"
            },
            {
                label: 'Adq.',
                name: 'Ren_Tipo',
                width: 30,
                align: "center"
            },
            {
                label: 'Tipo',
                name: 'Ren_Rete',
                width: 30,
                align: "center"
            },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                name: 'act1',
                width: 30,
                align: 'center',
                viewable: false,
                formatter: function(cellvalue, options, rowObject) {
                    return $.getGridButton(addCodigo, rowObject, 'Agregar Codigo');
                }
            }
        ]);

        function seleccionaRetencion(data) {
            $('#cuenDialog').dialog('open');
            $('#cuenForm').setData(data).formSubmit();
        }

        function addCodigo(codigo) {
            if (codigo.Ren_Rete == "RENTA") {
                $("#codRenta").val(codigo.Ren_Cod);
            } else {
                $("#codIva").val(codigo.Ren_Cod);
            }
            $('#cuenDialog').dialog('close');
        }
    </script>

    <script type="text/javascript">
        $(function() {
            $('#Select_Ciudad').createChosen('input-xs', {
                tabIndex: 6,
                width: '100%',
                template: function(t, d) {
                    return '<div class="over"><b>' + t + '</b></div><div class="over desc" style="font-size:11px;"><b>Provincia:</b> ' + d['prov'] + ' <b>Pa&iacute;s:</b> ' + d['pai'] + '</div>';
                }
            });

            //Inicio Grid para presentar el detalle de factura
            $("#Lis_Proveedor").createGrid({
                postData: $("#frm_bus").getData("proveedoreAjax"),
                height: 295,
                colModel: [{
                        label: 'C&oacute;d. Int.',
                        name: 'Prv_Cod',
                        width: 25,
                        align: "left"
                    },
                    {
                        label: 'C&eacute;dula/R.U.C.',
                        name: 'Prs_Ced',
                        width: 35,
                        align: "left"
                    },
                    {
                        label: 'Proveedor',
                        name: 'proveedor',
                        width: 100,
                        align: "left"
                    },
                    {
                        label: 'Correo',
                        name: 'Prv_Cor',
                        width: 60,
                        align: "left"
                    },
                    {
                        label: 'Telefono',
                        name: 'Prv_Tel',
                        width: 30,
                        align: "left"
                    },
                    {
                        label: 'Tipo',
                        name: 'Prv_Tic',
                        width: 30,
                        align: "center",
                        formatter: 'estado',
                        formatoptions: {
                            types: {
                                'N': 'Natural',
                                'J': 'Juridico'
                            },
                            full: true
                        }
                    },
                    {
                        label: 'Contabilidad',
                        name: 'Prv_Con',
                        width: 30,
                        align: "center",
                        formatter: 'truefalse'
                    },
                    {
                        label: 'Especial',
                        name: 'Prv_Esp',
                        width: 30,
                        align: "center",
                        formatter: 'truefalse'
                    },
                    {
                        label: 'Reg. Micro.',
                        name: 'Prv_Reg',
                        width: 30,
                        align: "center",
                        formatter: 'truefalse'
                    },
                    {
                        label: 'RISE',
                        name: 'Prv_Ris',
                        width: 30,
                        align: "center",
                        formatter: 'truefalse'

                    },

                    {
                        label: 'Gran.Contrib',
                        name: 'Prv_Gct',
                        width: 30,
                        align: "center",
                        formatter: 'truefalse'

                    },
                    {
                        label: 'Rimpe.Emp.',
                        name: 'Prv_Rim_Emp',
                        width: 30,
                        align: "center",
                        formatter: 'truefalse'

                    },
                    {
                        label: 'Rimpe.Neg.Popul',
                        name: 'Prv_Rim_Np',
                        width: 30,
                        align: "center",
                        formatter: 'truefalse'

                    },
                    {
                        label: 'Ag. Ret.',
                        name: 'Prv_Ag_Ret',
                        width: 30,
                        align: "center",
                        formatter: 'truefalse'

                    },
                    {
                        label: '&nbsp;',
                        name: 'act1',
                        width: 30,
                        align: 'center',
                        viewable: false,
                        formatter: 'gridButton',
                        formatoptions: {
                            action: cargarProveedor,
                            title: 'Editar Proveedor'
                        }
                    }
                ]
            }, false, "#Pag_Cli");

            $('#Ide_Cod').change(function() {
                if (this.value * 1 === 1) {
                    $('#Prs_Ced').attr('onchange', 'validar(2)');
                } else {
                    $('#Prs_Ced').attr('onchange', 'validar(3)');
                }
                habilitar('ex', this.value);
            });
            $('#Prs_Ced').fieldValid(true);
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
                        searchProveedor(cedula, 'ec');
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
                        searchProveedor(cedula, 'ec');
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

        function searchProveedor(ced, tipo) {
            (tipo === 'ec') ? ced = ced.substring(0, 10): ced;
            var oldced = $('#oldcedula').val().substring(0, 10);
            if (ced !== oldced) {
                $.post("", {
                        searchProveedor: true,
                        Prs_Ced: ced
                    },
                    function(response) {
                        if (response['exisPer'] === true) {
                            $.alert('La Identificacion esta siendo Utilizada por otro Proveedor..!!', function() {
                                $('#Prs_Ced').val($('#oldcedula').val()).focus();
                            }, 'warning-sign');
                        } else {
                            $('#oldcedula').val($('#Prs_Ced').val());
                        }
                    }, 'json').fail(function() {
                    $.alert();
                });
            }
        }

        ide_cod = 0;

        function cargarProveedor(proveedor) {
            $('#lista').moveComp('#modificar').updateGridsSizes();
            if (proveedor['Prv_Tic'] === 'J') {
                $('.juridico').show();
                $('.natural').hide();
            } else {
                $('.natural').show();
                $('.juridico').hide();
            }
            $('#isRuc').prop('checked', proveedor['Prs_Ced'].length === 13);
            $('#Ciu_Cod').val(proveedor['Ciu_Cod']).trigger('chosen:updated');
            $('#oldcedula').val(proveedor['Prs_Ced']);
            $('#formProvedor').setData(proveedor, null);
            $('#Prs_Ced').fieldValid(true);
            ide_cod = proveedor['Ide_Cod'];
            $('#Select_Ciudad').trigger('chosen:updated');
        }

        function EditaProveedor() {

            $.createDialogConfirm('Desea Confirmar los Cambios en el Proveedor!!', null,
                function() {
                    //          Funcion Aceptar
                    if ($('#Prv_Tic').val() === 'J') {
                        $('#Prs_Sex').val('');
                        $('#Prs_Nom').val('');
                    }
                    $.saveDataJson("", $('#formProvedor').getData('editarProvAjax'), function(resp) {
                        $('#Lis_Proveedor').trigger('reloadGrid');
                        $('#modificar').moveComp('#lista').updateGridsSizes();
                    });
                },
                function() {
                    //          Funcion Cancelar
                    $('#Prs_Ced').val($('#oldcedula').val()).focus();
                });


        }

        function setTipoDoc() {
            public $Prs_Ced = $('#Prs_Ced'),
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

        $('#Cop_Aut').on('change', function() {
            var val = $(this).val(),
                aut = val.length;
            $(this).attr('title', val);
            if (aut !== 0 && aut !== 10 && aut !== 37 && aut !== 49) {
                $(this).fieldValid(false, 'El campo debe tener 10, 37 o 49 digitos!');
            } else {
                $(this).fieldValid(aut === 0 ? '' : true);
            }
        });

         //Solo se puede seleccionar RIMPE Emprendedor o Negocio popular
         function toggleCheckbox(otherCheckboxName) {
            const otherCheckbox = document.querySelector(`input[name="${otherCheckboxName}"]`);
            otherCheckbox.checked = false;
        }
    </script>
</BODY>

</HTML>