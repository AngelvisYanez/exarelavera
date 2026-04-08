<?php
/**
 * @abstract Permite actualizar comprobantes automaticos con cuentas de banco. 
 * @author Erick Cordova
 * @version 1.0
 * Fecha de creaci�n  18/10/2017
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque_ban.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Che;


//$obBD_con1->debug(true);
if (isset($cargarPeriodos)) {
    try {
        $resp['periodos'] = $obBD_con1->getArrayConsulta(3, $Ses_Emp_Cod, $obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
        } else {
            $resp = array('messsage' => $obBD_con1->MsgError);
        }
    } catch (Exception $ex) {
        $resp = array('messsage' => $ex->getMessage());
    }
    $obBD_con1->echoJson($resp);
}



if (isset($getBancos)) {

    $resp = array('success' => true, 'options' => "<option value=''></option>");
    $bancos = $obBD_con1->getArrayConsulta(4, $Pec_Cod, $obBD_conexion);
    //var_dump($bancos);
    foreach ($bancos as $v) {
        $resp['options'] = $resp['options'] . "<option value='$v[Pld_Cod]' data--pld_-cod='$v[Pld_Cod]' data--ban_-cod='$v[Ban_Cod]' data--ban_-cue='$v[Ban_Cue]' data--pld_-cdc='$v[Pld_Cdc]' data--pld_-des='" . str_replace("'", '', $v['Pld_Des']) . "'>$v[Pld_Des] (Cta.#: $v[Ban_Cue])</option>";
    }
    $obBD_con1->echoJson($resp);
}

if (isset($searchDocument)) {
    //$obBD_con1->debug(true);
    try {
        $data = $_GET;
        $bancos_array = array();
        $data['Emp_Cod'] = $Ses_Emp_Cod;


        $resp = $obBD_con1->getPageGrid(1, $data, $obBD_conexion);    
    } catch (Exception $exc) {
        $obBD_con1->echoLog($exc->getTraceAsString());
    }
    $obBD_con1->echoJson($resp);
}



?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Cheques Consultar [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>  
        <style>#tabs.ui-widget-content{background:none !important;} .ui-tabs-panel{padding-bottom: 0 !important;}.ui-tabs-nav{padding-top: 0 !important;}
        </style>
        <script type="text/ecmascript" src="../VALIDACIONES/tes_val_cheque_ban.js?a=2">
        </script>
        <script>
            var baja_che=0;
        </script>
        <style>
            .swlFlyout{
                height: 60px !important;
                min-width: 150px !important;
                z-index: 9999 !important;
            }
        </style>
    </HEAD>
    <BODY>

        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Listar Cheques Emitidos</h3></div>        
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="documentoSearch">
                    <div class="row">
                        <form name="searchComprobantes" id="searchComprobantes" class="form-horizontal normal" action="javascript:$('#searchGrid').Search($.extend($('#searchComprobantes').getData(),{'searchDocument':true}));">
                            <div class="col-xs-5">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Búsqueda</legend>
                                <div class="form-group">    
                                    <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>  
                                    <div class="col-sm-10 radioset opt_search">
                                          <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Proveedor&nbsp;&nbsp;&nbsp;</label>
                                          <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;&nbsp;</label>
                                          <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;No. Cheque&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">B&uacute;squeda:</label>  
                                    <div class="col-sm-10" >
                                        <div class="input-group">                        
                                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus  class="form-control input-sm clearable submit"/>
                                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                        </div><!-- /input-group --> 
                                    </div><input type="text" tabindex="-1" style="display:none;" />                    
                                </div>
                            </fieldset>
                        </div>
                            <div class="col-sm-7 ">
                                <input type="text" id='Order_By' name ='Order_By' class="hidden"/>
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtros</legend>
                                    <div class="col-sm-12">   
                                        <div class="form-group">
                                            <label class="col-sm-1 control-label label-sm">Periodo:</label>
                                            <div class="col-sm-5">
                                                <select class="form-control input-sm" id="Pec_Cod" name="Pec_Cod"  required="">
                                                    <option value=0>----</option>
                                                </select>
                                            </div>
                                            <label class="col-sm-1 control-label label-sm">Banco:</label>
                                            <div class="col-sm-5">
                                                <select id="Pld_Cod" name="Pld_Cod" class="form-control input-sm" ></select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div id="rango_fechas">
                                                <label class="col-sm-1 control-label label-sm">Desde:</label>
                                                <div class="col-sm-2">
                                                    <input name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="form-control input-sm datepicker" style="text-align: center;"/>
                                                </div>
                                                <label class="col-sm-1 control-label label-sm">Hasta:</label>
                                                <div class="col-sm-2">
                                                    <input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="form-control input-sm datepicker" style="text-align: center;"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            
                        </form>
                        <div class="col-xs-12" style="min-height: 360px;">
                            <table id="searchGrid" name="searchGrid"></table>
                            <table id="searchGridPager"></table>
                            <div class="Titulos2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="formatoReporte" style="display: none;">
            <div style="width: 1030px;">
                <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span id="titleReporte"></span>', $obBD_conexion); ?>
                <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>            
                <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
            </div>
        </div>  
        <div id="formatoExportar" style="width: 700px;display: none;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span class="title_grid"></span>', $obBD_conexion, false, 5); ?>
        </div>

        <?php $ruta='./'.(file_exists ('cheques/'.$Ses_Emp_Cod)?"cheques/$Ses_Emp_Cod/":''); ?>
        <div id="modelo" style="display:none;"  title="Elija la plantilla del cheque " >
            <center id="printCheque"></center>
        </div>    
        <div id="conten_bancos_imp" style="width: 700px;display: none;">
            <table style="margin-bottom:10px;" cellpadding="1" border="1">
                <tr><td align="center" class="ui-widget-content" colspan="7"><b>&nbsp; plantillas  &nbsp;</b></td></tr>
                <tr>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_mac_1.0.php{link}" target="_blank" title="Banco de Machala"><img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35"/></a></td>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_pac_1.0.php{link}" target="_blank" title="Banco del Pacifico"><img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23"/></a></td>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_rum_1.0.php{link}" target="_blank" title="Banco del Rumiñahui"><img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15"/></a></td>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_gua_1.0.php{link}" target="_blank" title="Banco del Guayaquil"><img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18"/></a></td>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_pch_1.0.php{link}" target="_blank" title="Banco del Pichincha"><img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30"/></a></td>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_int_1.0.php{link}" target="_blank" title="Banco Internacional"><img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32"/></a></td>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_aust_1.0.php{link}" target="_blank" title="Banco del Austro"><img src="../../mascaras/model1/imagenes/32x32/ban_aust.jpg" width="32" height="32"/></a>
                    <td align="center"><a href="<?php echo $ruta; ?>cheques/1/tes_pri_cheque_loj_1.0.php{link}" target="_blank" title="Banco de Loja"><img src="../../mascaras/model1/imagenes/32x32/banco_loja.jpg" width="24" height="32"/></a>
                </tr>
            </table>
        </div>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <script>$.clearValidate();</script>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script> 
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
        <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />    
    </BODY>
</HTML>