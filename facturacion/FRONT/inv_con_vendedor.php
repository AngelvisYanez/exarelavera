<?php
/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_producto_1.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);
/**
 * Creaci�n del Objeto para consultas
 */
$obBD_con1 = new Class_Log_Datos_Pro;
/**
 * Evita el reenvio 
 */
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");


?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>                   
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
        <style>                    
           
        </style>
    </HEAD>
    <BODY>

        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Reportes Ventas</h3></div>

            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
               <div id="tabs" class="ui-tab-fix">
                <ul>
                  <li><a href="#tabs-1">Inicio</a></li>
                  <li><a href="<?php echo basePath('inv_con_vet_repzonas.php');?>">Por Zona</a></li>
                  <li><a href="<?php echo basePath('inv_con_vet_repvende.php');?>">Por Vendedor</a></li>
                  <li><a href="<?php echo basePath('inv_con_vet_repclien.php');?>">Por Cliente</a></li>
                  <li><a href="<?php echo basePath('inv_con_vet_repprodu.php');?>">Por Producto</a></li>                  
                  <li><a href="<?php echo basePath('inv_con_vet_repfamil.php');?>">Por Familias</a></li>
                  
                  <li><a href="<?php echo basePath('inv_con_vet_repfactu.php');?>">Por Facturas</a></li>
                  
                  <li><a href="<?php echo basePath('inv_con_vet_repnocop.php');?>">No Compran</a></li>
                  <li><a href="<?php echo basePath('inv_con_vet_repinven.php');?>">Inventarios</a></li>
                  
                </ul>
                <div id="tabs-1">
                  <p>Graficas de Inicio <?php var_dump(basePath('ver.php')); ?></p>
                </div>
              </div>
            </div>
        </div>
        <script>
        $(function() {
          $( "#tabs" ).tabs({
            cache: true,  
            beforeLoad: function( event, ui ) {
                console.log('#'+$(ui.tab[0]).attr('aria-controls'));
              if ( ui.tab.data( "loaded" ) ) {
                $("#tabs").tabs("option", "active", ui.tab.index());      
                if($('#'+$(ui.tab[0]).attr('aria-controls')).find('.ui-jqgrid-btable').actual( 'outerWidth', { includeMargin : true })<300)
                    //$('#'+$(ui.tab[0]).attr('aria-controls')).find('.ui-jqgrid-btable').jqgrid().trigger('resize');
                console.log($('#'+$(ui.tab[0]).attr('aria-controls')).find('.ui-jqgrid-btable').actual( 'outerWidth', { includeMargin : true }));
                event.preventDefault();
                            
                return;
              }  
              ui.jqXHR.fail(function() {
                ui.panel.html("Error. No se logro cargar la ventana" );
              });
              ui.jqXHR.success(function() {
                ui.tab.data( "loaded", true );
              });
            }
          });
        });
        </script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    </BODY>
</HTML>