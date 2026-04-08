<?php
/**
 * Descripcion:          Reporte de Productos
 * Fecha de creacion:    Octubre 4, 2017
 * Desarrollador:	Asael Tello
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_reporteproducto_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Rep($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Rep;
$hoy = date("Y-m-d");
//$obBD_con1->debug(true);

    /*
     * Get data
     */
    if (isset($searchAll)) 
    {
        //$obBD_con1->echoLog($_GET);
        $data = $obBD_con1->getArrayConsulta(1, array("Emp_Cod" => $Ses_Emp_Cod, "desde" => $_GET['desde'], "hasta" => $_GET['hasta']), $obBD_conexion);
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
        <TITLE><?Php echo "Comp. Promedio Productos [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>       
        <script language="javascript" src="../VALIDACIONES/fac_val_reporteproducto_1.0.js"></script>
        	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Reporte de Producto</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-sm-12">
                        <form id="formBusqueda" name="formBusqueda">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtros de B&#250;squeda</legend>                                
                                
                                <div class="col-sm-3"></div>                                
                                <!-- Filtro de Busqueda -->
                                <div class="form-group-sm">
                                    <label class="col-xs-1 control-label label-xs">Desde:</label>
                                    <div class="col-xs-2">                                        
                                        <input class="form-control col-sm" type="text" name="desde" id="desde">  
                                    </div>
                                </div>
                                
                                <div class="form-group-sm">
                                    <label class="col-xs-1 control-label label-xs">Hasta:</label>
                                    <div class="col-xs-2">                                        
                                        <input  class="form-control col-sm" type="text" id="hasta" name="hasta"/>
                                    </div>
                                    
                                    <button id="btnSearch" type="button" name="btnSearch" class="btn btn-success btn-sm"><i class="glyphicon glyphicon-search"></i>   Buscar</button>
                                </div>                               

                                <div class="col-sm-3"></div>
                            </fieldset>    
                        </form>
                    </div>
                    
                    <div class="col-sm-12">
                        <div id="tabsSearch" class="ui-tab-fix ui-tabs">
                            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                                <li><a href="#tabs-1">Datos</a></li>
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
                <div id="formatoReporte" style="display: none;">
                    <div style="width: 1030px;">
                      <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'ENTRADA DE PRODUCTOS PROMEDIO', '<span id="titleReporte"></span>',$obBD_conexion); ?>
                      <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>            
                      <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
                    </div>
                  </div>  
                  <div id="formatoExportar" style="width: 700px;display: none;">
                      <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'ENTRADA DE PRODUCTOS PROMEDIO', '<span class="title_grid"></span>',$obBD_conexion,false,5); ?>
                  </div>
            </div>
        </div>
    </BODY>
</HTML>