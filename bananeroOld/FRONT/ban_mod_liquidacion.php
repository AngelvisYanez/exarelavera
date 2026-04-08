<?php	
/**
* @abstract Permite realizar la creacion de Liquidaciones
* @author Erick Cordova
* @version 1.0
* Fecha de creacion 2017-12-21
*/
require_once('../../administrador/LOGICA/seguridad.php');	 
require_once('../LOGICA/ban_log_bananero.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Bana($Ses_Dat_Dis);
/**
* Creacion del Objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Bana;


$hoy = date("Y-m-d");

$obBD_con1->debug(true);






?>

<!DOCTYPE html>
<html>
<head>
 <title><?Php echo $Ses_Sys_Nom; ?></title>
 <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
 <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
 <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>                   
 <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
 <style>                     
 .pagination>li>a, .pagination>li>span {padding: 4px 2px;}
 .pagination {/*display: block;*/margin:0;padding: 0;}
 .chosen-default span,.chosen-single span{color:#555;}
 .chosen-single span{padding-left: 5px;}
</style>
</head>
<body>
   <div class="panel panel-main">
      <div class="panel-heading exa-header">
         <h3 class="panel-title">&raquo; Editar Liquidaci&oacute;n</h3>
      </div>
      <div class="panel-body ui-widget-content ui-corner-bottom exa-body">  <div id="documentoSearch">
          <?php include("COMPONENTES/form_search_bodega.html");?>
        </div>
        <div id="documentoMain">
            <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaLiquidacion();">
               <div class="row">
                  <div class="col-xs-12  col-md-5 col-lg-5">
                     <fieldset class="exa-fieldset" id="datos_productor">
                        <legend class="Titulos2">Datos del Productor</legend>
                        <div class="form-group">
                           <label class="col-xs-2 control-label label-xs">C&eacute;dula/RUC:</label>  
                           <div class="col-xs-6" >
                              <input name="Prs_Cod" type="text" style="display:none;" />  
                              <input name="Prs_Cor" type="text" style="display:none;" />  
                              <input name="Prv_Cod" type="text" style="display:none;" />
                              <input name="op_opciones" type="text" value="c" style="display: none;">  
                              <div class="input-group input-group-xs">
                                 <input name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#prodDialog',selectProd); }" type="text" placeholder="Ingrese Productor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                 <span class="input-group-btn">
                                    <button id="Prv_Btn" type="button" onclick="$('#prodDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Productor"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                    <button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open'); $('#reset').val(1); " class="btn btn-success btn-xs" title="Registrar Productor"  tabindex="2">
                                       <span class="glyphicon glyphicon-plus"></span>
                                    </button>    
                                 </span>
                              </div>
                           </div>                                      
                        </div>
                        <div class="form-group">
                              <label class="col-xs-2 control-label label-xs required">Productor:</label>  
                              <div class="col-xs-6" >
                                 <input name="Prd_Nom" class="form-control input-xs" readonly="">
                              </div>
                              <div class="col-xs-4" >
                                 <select name="Bod_Cod"  id="Bod_Cod" class="form-control input-xs">
                                    <option>-------</option>
                                 </select>
                              </div>                                             
                        </div>
                        <div class="form-group">
                           <label class="col-xs-2 control-label label-xs">Direcci&oacute;n:</label>  
                           <div class="col-xs-10" >
                              <span name="Prs_Dir" type="text" class="form-control input-xs datatitle"></span>
                           </div>                    
                        </div>
                     </fieldset>
                  </div>
                  <div class="col-xs-12 col-md-7 col-lg-7">
                     <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos de Liquidaci&oacute;n</legend>
                        <div class="form-group">

                           <label class="col-xs-2 control-label label-xs required">N&uacute;mero:</label>  
                           <div class="col-xs-2" >
                              <input name="Liq_Num" type="text" class="form-control input-xs datatitle" required="" />
                           </div>

                           <label class="col-xs-2 control-label label-xs required">Emisi&oacute;n:</label>  
                           <div class="col-xs-2">
                              <div class="input-group">                                          
                                 <input id="Liq_Fec" name="Liq_Fec" type="text" class="form-control input-xs datepickers" tabindex="8" required="" value="<?php echo $hoy; ?>" />
                                 <span class="input-group-addon input-xs" title="Fecha de Emisi&oacute;n del Documento"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                              </div>
                           </div>


               <label class="col-xs-2 control-label label-sm">Liquidaci&oacute;n por:</label>
               <div class="col-xs-2">
                  <select name="Tia_Cod" id="Tia_Cod" class="form-control input-xs" required=""></select>
               </div>
                          
                        </div>
                                    
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Semana:</label>  
                           <div class="col-xs-2" >
                              <input name="Liq_Sem" type="text" class="form-control input-xs datatitle" required="" />
                           </div>
                           <label class="col-xs-2 control-label label-xs">Hacienda MAG:</label>  
                           <div class="col-xs-6" >
                              <input name="Liq_Hac" type="text" class="form-control input-xs datatitle"/>
                           </div>
                        </div>
                        <div class="form-group">
                           <label class="col-xs-2  control-label label-xs">Hectareas:</label>
                           <div class="col-xs-2" >
                              <input name="Liq_Hec" type="text" class="form-control input-xs datatitle"/>
                           </div>
                           <label class="control-label label-xs col-xs-2">Marcas:</label>
                           <div class="col-xs-6">
                              <input type="text" class="form-control input-xs" name="Liq_Mar"/>
                           </div>
                        </div>
                     </fieldset>
                  </div>
               </div>

               <div class="row">
                  <div class="col-lg-6 col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
                        <table id="ingresos"></table>
                        <div id="ingresosPager"></div>                        
                  </div> 
               
                  <div class="col-lg-6 col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
                        <table id="descuentos"></table>
                        <div id="descuentosPager"></div>                        
                  </div> 
               </div>

               <div class="row" id="resumenes">
                  <div class="col-xs-6 col-xs-offset-3">
                     <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Resumen</legend>
                        <div class="form-group">
                           <label class="control-label label-xs col-xs-6">Ingresos :</label>
                           <input class="col-xs-2 control-input" style=" text-align: right;border-radius: 4px;" name="res_ingreso" readonly="" />
                        </div>
                        <div class="form-group">
                           <label class="control-label label-xs col-xs-6">Descuentos :</label>
                           <input  class="col-xs-2 control-input" style=" text-align: right;border-radius: 4px;" name="res_descuento" readonly="" />
                        </div>
                        <div class="form-group">
                           <label class="control-label label-xs col-xs-6"> Neto a Pagar :</label>
                           <b><input class="col-xs-2 control-input" style="text-align: right; border-radius: 4px;" name="res_total" readonly="" /></b>
                        </div>
                     </fieldset>
                  </div>
               </div>
               
               <div class="col-xs-1">
                  <button class="btn btn-sm btn-primary" onclick=""><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
               </div>
            </form>
            <!-- dialogo de busqueda de Productor -->
            <div id="prodDialog" title="B&uacute;squeda de Productores"></div>

            <!-- dialogo de busqueda de Productos -->
            <div id="ingresoDialog" title="B&uacute;squeda de Items"></div>
            <div id="descuentoDialog" title="B&uacute;squeda de Productos en Bodega"></div>


            <div id="RetencionDialog" title="Cambiar valor de Retenci&oacute;n" style="display:none;">
               <form class="form-horizontal normal" id='form_change_rete' action="javascript:CambiarRetencion(this)">
                 <input type="text" name="index" class="hidden">
                 
                 <div class="form-group">
                   <label class="col-xs-4 control-label label-xs required">Tipos/Retencion:</label> 
                   <div class="col-xs-6">
                      <select id="tipo_ret_ban" class="form-control input-xs"></select>
                   </div>
                 </div>
                 
                 <br>
                 <div class="form-group">
                    <div class="center" style="min-height: 100px; width: 480px; padding-bottom: 5px;">
                        <table id="reten"></table>
                 </div>
                 </div>
                 <div class="form-group col-xs-offset-2">
                  <label class="col-xs-5 control-label label-xs required">Total a Retener:</label> 
                  <div class="col-xs-4">
                     <div class="input-group">  
                        <span class="input-group-addon input-xs">$</span>
                       <input id="total_ret" name="Ret_Tot" class="form-control input-xs"/>         
                     </div>
                  </div>
                 </div>

                 <input type="text" class="hidden" id="Ret_Bas" name="Ret_Bas"/>

                 <div class="form-group col-xs-offset-2">
                  <label class="col-xs-5 control-label label-xs required">Tarifa Efectiva:</label> 
                  <div class="col-xs-4">
                     <div class="input-group">                                          
                        <input id="total_tarifa" name="Ret_Por" class="form-control input-xs"/>
                        <span class="input-group-addon input-xs">
                           %
                        </span>
                     </div>
                  </div>
                 </div>
                 <br>

                 <div class="center">
                   <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Aceptar</button>
                 </div>
               </form>
             </div>
        </div>

      </div>
   </div>
   <script src="../VALIDACIONES/ban_alt_liquidacion.js"></script>
</body>
</html>