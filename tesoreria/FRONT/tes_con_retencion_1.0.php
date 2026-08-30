<?php
/**
 * Descripcion:          Modulo de Deuda Inicial
 * Fecha de creacion:    Octubre 3, 2017
 * Desarrollador:	Asael Tello
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_retencion_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Ret($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Ret;
$hoy = date("Y-m-d");
//$obBD_con1->debug(true);

    /*
     * Get data
     */
    if (isset($searchAll)) 
    {
        //$obBD_con1->echoLog($_GET);
        $data = $obBD_con1->getArrayConsulta(1, array("Emp_Cod" => $Ses_Emp_Cod, "filtro" => $_GET['filtro'], "numero" => $_GET['numero']), $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows' => $data,
            'total' => 1,
            'records' => count($data),
            'success' => true
        ));
    }
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Retenciones Reporte [EXA]"; ?></TITLE>
        <meta charset= "UTF-8"> 
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>       
        <script type="text/javascript" src="../VALIDACIONES/tes_val_retencion_1.0.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"> </script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Retenciones</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-sm-12">
                        <form id="formBusqueda" name="formBusqueda">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&#250;squeda Retenciones</legend>                                
                                <div class="col-sm-3"></div>
                                <!-- Filtro de Busqueda -->
                                <div class="form-group">
                                    <label class="col-xs-1 control-label label-xs">Retenciones:</label>
                                    <div class="col-xs-3">                                        
                                        <select id="Ret_Est" name="Ret_Est" class="form-control col-sm">
                                            <option value="0">TODAS</option>
                                            <option value="A">ACTIVAS</option>
                                            <option value="I">INACTIVAS</option>
                                            <option value="N">N&#218;MERO</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-xs-2" id="numero">
                                        <input  class="form-control col-sm" type="text" id="Ret_Num" name="Ret_Num" onkeypress="return validar_numeric(event)"/>
                                    </div>
                                    
                                    <button id="btnSearch" type="button" name="btnSearch" class="btn btn-success"><i class="glyphicon glyphicon-search"></i>   Buscar</button>
                                </div>                               

                                <div class="col-sm-3"></div>
                            </fieldset>    
                        </form>
                    </div>
                    
                    <div class="col-sm-12">
                        <div id="tabsSearch" class="ui-tab-fix ui-tabs">
                            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                                <li><a href="#tabs-1">Retenciones Existentes</a></li>
                            </ul>
                            <div id="tabs-1" style="min-height: 450px;">

                                <div>
                                    <table id="tableResult">                                        
                                    </table>
                                    <div id="tableResultPager">                                         
                                    </div>
                                    <BR>                                    
                                </div>
                            </div>                          
                        </div>

                    </div>                    
                </div>
                <!-- REPORTE -->
                <div id="formatoReporte" style="display: none;">
                    <div style="width: 1030px;">
                      <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE RETENCIONES', '<span id="titleReporte"></span>',$obBD_conexion); ?>
                      <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>            
                      <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
                    </div>
                </div>
                
                <div id="formatoExportar" style="width: 700px;display: none;">
                    <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE RETENCIONES', '<span class="title_grid"></span>',$obBD_conexion,false,5); ?>
                </div>
                
                <!-- MODAL RETENCION-->
                <div id="modalRetencion" title="">
                    <form id ="frmMdRet" name="frmMdRet" class="form-horizontal" autocomplete="off">
                        <fieldset>

                            <!-- VENDEDOR -->
                            <div class="form-group">
                                <label class="label-xs" id="vendedor">Vendedor:</label>                                
                            </div>

                            <!-- Buttons 
                            <div class="form-group">                                
                                <div class="col-md-8">
                                    <button id="btnAccion" type="button" name="btnAccion" class="btn btn-sm btn-primary"></button>
                                </div>
                            </div>-->

                        </fieldset>
                    </form>
                </div>   
                
            </div>
        </div>
    </BODY>
</HTML>