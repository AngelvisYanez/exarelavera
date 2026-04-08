<?php
/**
 * @abstract Permite realizar la consulta de productos y cliente
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de cración: 27-12-2018
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven_lista.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Vent_Lista;
$obBD_con1->debugLogs(false);

$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");
/**
 * Productos más vendidos
 */

if(isset($prodAjax)){
    $obBD_con1->echoLog('** PHP PRODUCTOS AJAX ***');
    //ChromePhp::log('MÁS VENDIDOS');
    if ($Cate_Cod != '' and $Sub_Cod == '') {
      $cat = " AND categorias.Cat_Rec=$Cate_Cod ";
     }
    if ($Cate_Cod!='' and $Sub_Cod!=''){
      $cat = " AND item.Cat_Cod=$Sub_Cod ";
    }
    if ($Ubi_Cod != '') {
      $ubi = " AND producto.Ubi_Cod=$Ubi_Cod ";
    }
    if ($order != ''){
      $order = " ORDER BY ". $order;
    }
    $prod = "";
    if ($op_opciones == 'h'){
        $prod = "AND item.Ite_Lar LIKE '%$search%' ";
    }
    $fec = "";
    if ($op_opciones == 'f'){
        $fec = " AND caja_aper.Caj_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin' ";
    }
    $data= array_merge($_GET, array('unsetColsInit'=>true,'setWhere'=>array('isActive','setItemsVent','setProductoInfo'),'group'=>'ventas_det.Pro_Cod'));
    $resultado =  $obBD_con1->getArrayConsulta(38, $Ses_Emp_Cod. '*'.$cat. '*'.$ubi. '*'.$order. '*'.$prod. '*'.$fec, $obBD_conexion);
    $obBD_con1->echoJson($resultado);
}


/**
 * Clientes con más ventas
 */
if(isset($cliFrecAjax)){
    $obBD_con1->echoLog('** PHP CLIENTES AJAX ***');
    if ($order != ''){
      $order = "ORDER BY ". $order;
    }
    $fec2 = "";
    if ($op_opciones == 'f'){
        $fec2 = " AND (caja_aper.Caj_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin') ";
    }
    $cli = "";
    if($op_opciones == 'r'){
      $cli = " AND (persona.Prs_Nom LIKE '%$search%' OR persona.Prs_Ape LIKE '%$search%') ";
    }
    $ced = "";
    if($op_opciones == 'c'){
      $ced = " AND (persona.Prs_Ced LIKE '%$search%') ";
    }
    $data = array_merge($_GET, array('unsetCols'=>array('tables'=>array('ventas')),'addCols'=>array('ventas.Cli_Cod'),'setWhere'=>array('isActive','setItemsVent'),'group'=>'ventas.Cli_Cod','order'=>'total desc'));
    $resultado =  $obBD_con1->getArrayConsulta(41, $Ses_Emp_Cod. '*'.$order. '*'.$fec2. '*'.$cli. '*'.$ced, $obBD_conexion);
    $obBD_con1->echoJson($resultado);
}

/**
 * Productos con stock cero
 */
if(isset($prodLowAjax)){
    $obBD_con1->echoLog('** PHP PRODUCTOS LOW AJAX ***');
    //ChromePhp::log('SIN STOCK');
    if ($Cate2_Cod != '' and $Sub2_Cod == '') {
          $cat = " AND categorias.Cat_Rec=$Cate2_Cod ";
        }
    if ($Cate2_Cod!='' and $Sub2_Cod!=''){
        $cat = " AND item.Cat_Cod=$Sub2_Cod ";
        }
    if ($Ubi2_Cod != '') {
        $ubi = " AND producto.Ubi_Cod=$Ubi2_Cod ";
    }
    $cod_bar = "";
    if($op_opciones == 'c'){
      $cod_bar = " AND (producto.Pro_Bar LIKE '%$search%') ";
    }
    $descrip = "";
    if($op_opciones == 'p'){
      $descrip = " AND (item.Ite_Lar LIKE '%$search%') ";
    }
    $data = array_merge($_GET, array('setWhere'=>array('isActive','byStock','addStock','haveNotStock')));
    $resultado =  $obBD_con1->getArrayConsulta(39, $Ses_Emp_Cod. '*'.$cat. '*'.$ubi. '*'.$cod_bar. '*'.$descrip, $obBD_conexion);
    $obBD_con1->echoJson($resultado);
}
/**
 * Productos sin precio
 */
if(isset($prodLowPriceAjax)){
    $obBD_con1->echoLog('** PHP PRODUCTOS SIN PRICE AJAX ***');
    //ChromePhp::log('SIN PRECIO');
    if ($Cate3_Cod != '' and $Sub3_Cod == '') {
          $cat = " AND categorias.Cat_Rec=$Cate3_Cod ";
        }
    if ($Cate3_Cod!='' and $Sub3_Cod!=''){
        $cat = " AND item.Cat_Cod=$Sub3_Cod ";
        }
    if ($Ubi3_Cod != '') {
        $ubi = " AND producto.Ubi_Cod=$Ubi3_Cod ";
    }
    $cod_barra = "";
    if($op_opciones == 'c'){
      $cod_barra = " AND (producto.Pro_Bar LIKE '%$search%') ";
    }
    $descripcio = "";
    if($op_opciones == 'p'){
      $descripcio = " AND (item.Ite_Lar LIKE '%$search%') ";
    }
    $data = array_merge($_GET, array('setWhere'=>array('isActive','byStock','addStock','haveNotStock')));
    $resultado =  $obBD_con1->getArrayConsulta(40, $Ses_Emp_Cod. '*'.$cat. '*'.$ubi. '*'.$cod_bar. '*'.$descripcio, $obBD_conexion);
    $obBD_con1->echoJson($resultado);
}

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($CatSelect)){
    $rs_tpaj= $obBD_con1->getArrayConsulta(31, $Ses_Emp_Cod.'*'.$CatSelect, $obBD_conexion);
    $Cat_Cod=$CatSelect;
    echo "<option value=''>Todas</option>";
    foreach ($rs_tpaj as $row) 
        echo utf8_encode("<option value='$row[Cat_Cod]'>$row[Cat_Des]</option>");        
    exit();
}
?>

<!DOCTYPE html>
<HTML>
   <HEAD>
      <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
      <TITLE><?Php echo "Ventas Reportes [EXA]"; ?></TITLE>
      <meta charset= "UTF-8">
      <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
      <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
      <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
      <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
      <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
      <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
      <script> </script>
      <style>

      </style>
   </HEAD>
   <BODY>
      <div class="panel panel-main" id="formFinal">
      <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Datos Productos - Clientes</h3></div>
      <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
         <div class="row">
            <div class="col-xs-12 ">
               <div id="tabsDatos" class="ui-tab-fix">
                  <ul>
                     <li><a href="#tabs-1">Productos m&aacutes vendidos</a></li>
                     <li><a href="#tabs-2">Clientes frecuentes</a></li>
                     <li><a href="#tabs-3">Productos sin stock</a></li>
                     <li><a href="#tabs-4">Productos sin precio</a></li>
                  </ul>
                  <div class="panels-area form-horizontal normal ">
                    
                    <!-- CREAR TAB !-->
                    <div id="tabs-1" >
                        <div class="row">
                           <form id="frm_prod_ven" name="frm_prod_ven" class="form-horizontal normal" action="javascript:$('#container').Search('#frm_prod_ven','prodAjax');">
                            <fieldset class="exa-fieldset" id="prodFormTemp">
                              <div class="col-xs-12 col-sm-6">
                                    <legend class="Titulos2">B&uacute;squeda</legend>
                                    <input name="order" type="hidden" value="" />
                                    <div class="form-group">
                                       <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>
                                       <div class="col-sm-7 radioset opt_search">
                                          <input id="radsc1" name="op_opciones" type="radio" value="h"checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">Producto</label>
                                          <input id="radsc2" name="op_opciones" type="radio" value="f" onclick="setfocus(this.form.search)" alt="" /><label for="radsc2">Fecha</label>
                                       </div>
                                    </div>

                                    <div id="divFecha" class="form-group" style="display:none;">
                                       <div class="col-xs-2"></div>
                                       <div class="col-xs-6">
                                          <div class="input-group input-group-xs por_fecha">
                                             <span class="input-group-addon"><span class="">Rango:</span></span>
                                             <span class="input-group-addon alert-info">Desde</span>
                                             <input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control" disabled="" />
                                             <span class="input-group-addon alert-info">Hasta</span>
                                             <input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control" disabled="" />
                                          </div>
                                       </div>
                                    </div>

                                    <div class="form-group">
                                       <label class="col-sm-2 control-label">B&uacute;squeda:</label>
                                       <div class="col-sm-7">
                                          <div class="input-group">
                                              <input  id="search" name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus  class="form-control input-xs clearable submit"/>
                                                    <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Producto"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                          </div>
                                       </div><input type="text" tabindex="-1" style="display:none;" />
                                    </div>
                                </div>

                                        <!-- FILTROS MAS VENDIDO--->
                                      
                                        <div class="col-xs-6">
                                          <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs " for="Cate_Cod">Categor&iacutea:</label>
                                            <div class="col-sm-6">
                                              <?php $row_rs_categ = $obBD_con1->getArrayConsulta(30, $Ses_Emp_Cod, $obBD_conexion); ?>
                                              <select name="Cate_Cod" id="Cate_Cod" class="form-control input-xs" data-placeholder="Todas">
                                                <option value="">Todas</option>
                                                <?Php foreach ($row_rs_categ as $row) { ?><option value="<?Php echo $row['Cat_Cod']; ?>"><?Php echo /* strtoupper($row['Par_Cat_Des']).' » '. */$row['Cat_Des']; ?></option><?Php } ?>
                                              </select>
                                            </div>
                                            
                                          </div>

                                          <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs " for="Sub_Cod">Subcategor&iacutea:</label>
                                          <div class="col-sm-6">
                                            <select name="Sub_Cod" id="Sub_Cod" class="form-control input-xs" data-placeholder="Todas">
                                              <option value=''>Todas</option>
                                            </select>
                                          </div>
                                          </div>
                                          
                                          <div class="form-group">
                                            <label class="col-sm-3 control-label label-xs " for="Ubi_Cod">Ubicaci&oacuten:</label>
                                            <div class="col-sm-6">
                                              <?php $rs_ubicacion = $obBD_con1->getArrayConsulta(50, $Ses_Emp_Cod, $obBD_conexion); ?>
                                              <select name="Ubi_Cod" id="Ubi_Cod" class="form-control input-xs">
                                                <option value="">Todas</option>
                                                <?Php foreach ($rs_ubicacion as $row) {?><option value="<?Php echo $row['Ubi_Cod']; ?>"><?Php echo $row['Ubi_Des']; ?></option><?Php }?>
                                              </select>
                                            </div>
                                            
                                          </div>
                                        </div>
                                  </fieldset>
                           </form>
                        </div>
                        <div id="tablasProd" class="" style="min-height: 550px;">
                           <table id="container"></table>
                           <div id="containerPager"></div>
                        </div>
                    </div>

                    <!-- OTRO TAB !-->
                    <div id="tabs-2">
                        <div class="row">
                            <form id="frm_cli_frec" name="frm_cli_frec" class="form-horizontal normal" action="javascript:$('#clienFrecuentesTabla').Search('#frm_cli_frec','cliFrecAjax');">
                                <div class="col-xs-12 ">
                                    <fieldset class="exa-fieldset" id="cliVentFormTemp">
                                        <legend class="Titulos2">B&uacute;squeda</legend>
                                        <input name="order" type="hidden" value="" />
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-5 radioset opt_search">
                                                <input id="radB1" name="op_opciones" type="radio" value="c"checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radB1">C&eacute;dula/RUC</label>
                                                <input id="radB2" name="op_opciones" type="radio" value="r" onclick="setfocus(this.form.search)" alt="" /><label for="radB2">Cliente</label>
                                                <input id="radB3" name="op_opciones" type="radio" value="f" onclick="setfocus(this.form.search)" alt="" /><label for="radB3">Fecha</label>
                                            </div>
                                            <div id="divFechaForm2" class="form-group" style="display:none;">
                                                <div class="col-xs-4">
                                                    <div class="input-group input-group-xs por_fecha">
                                                        <span class="input-group-addon"><span class="">Rango:</span></span>
                                                        <span class="input-group-addon alert-info">Desde</span>
                                                        <input type="text" name="Fec_Ini" class="form-control" disabled="" />
                                                        <span class="input-group-addon alert-info">Hasta</span>
                                                        <input type="text" name="Fec_Fin" class="form-control" disabled="" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                            <div class="col-sm-5">
                                                <div class="input-group">
                                                    <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                                    <span class="input-group-btn">
                                                        <button id="btnSearch" onclick="this.form.submit()" class="btn btn-success btn-xs" type="button" title="Buscar Cliente"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </form>
                        </div>
                        <div id="tablasCli" class="" style="min-height: 550px;">
                           <table id="clienFrecuentesTabla"></table>
                           <div id="clienFrecuentesTablaPager"></div>
                        </div>
                    </div>

                     <!-- OTRO TAB !-->
                    <div id="tabs-3">
                        <div class="row">
                            <form id="frm_prod_low" name="frm_prod_low" class="form-horizontal normal" action="javascript:$('#prodLowTabla').Search('#frm_prod_low','prodLowAjax');">
                                <fieldset class="exa-fieldset" id="prodLowFormTemp">
                                  <div class="col-xs-12 col-sm-6">
                                        <legend class="Titulos2">B&uacute;squeda</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                             <div class="col-xs-5 radioset opt_search">
                                                <input id="radioB1" name="op_opciones" type="radio" value="c"checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radioB1">C&oacute;digo Barra</label>
                                                <input id="radioB2" name="op_opciones" type="radio" value="p"checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radioB2">Producto</label>
                                             </div>
                                        </div>
                                        <div class="form-group">
                                             <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                             <div class="col-sm-5">
                                                 <div class="input-group">
                                                    <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                                    <span class="input-group-btn">
                                                        <button id="btnSearch" onclick="this.form.submit()" class="btn btn-success btn-xs" type="button" title="Buscar Producto"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                                    </span>
                                                 </div>
                                             </div>
                                        </div>
                                    </div>

                                        <!-- FILTROS SIN STOCK--->
                                      
                                        <div class="col-xs-6">
                                          <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs " for="Cate2_Cod">Categor&iacutea:</label>
                                            <div class="col-sm-6">
                                              <?php $row_rs_categ = $obBD_con1->getArrayConsulta(30, $Ses_Emp_Cod, $obBD_conexion); ?>
                                              <select name="Cate2_Cod" id="Cate2_Cod" class="form-control input-xs" data-placeholder="Todas">
                                                <option value="">Todas</option>
                                                <?Php foreach ($row_rs_categ as $row) { ?><option value="<?Php echo $row['Cat_Cod']; ?>"><?Php echo /* strtoupper($row['Par_Cat_Des']).' » '. */$row['Cat_Des']; ?></option><?Php } ?>
                                              </select>
                                            </div>
                                            
                                          </div>

                                          <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs " for="Sub2_Cod">Subcategor&iacutea:</label>
                                          <div class="col-sm-6">
                                            <select name="Sub2_Cod" id="Sub2_Cod" class="form-control input-xs" data-placeholder="Todas">
                                              <option value=''>Todas</option>
                                            </select>
                                          </div>
                                          </div>
                                          
                                          <div class="form-group">
                                            <label class="col-sm-3 control-label label-xs " for="Ubi2_Cod">Ubicaci&oacuten:</label>
                                            <div class="col-sm-6">
                                              <?php $rs_ubicacion = $obBD_con1->getArrayConsulta(50, $Ses_Emp_Cod, $obBD_conexion); ?>
                                              <select name="Ubi2_Cod" id="Ubi2_Cod" class="form-control input-xs">
                                                <option value="">Todas</option>
                                                <?Php foreach ($rs_ubicacion as $row) {?><option value="<?Php echo $row['Ubi_Cod']; ?>"><?Php echo $row['Ubi_Des']; ?></option><?Php }?>
                                              </select>
                                            </div>
                                            
                                          </div>
                                        </div>
                                     </fieldset>
                            </form>
                        </div>
                        <div id="tablasProdStock" class="" style="min-height: 550px;">
                           <table id="prodLowTabla"></table>
                           <div id="prodLowTablaPager"></div>
                        </div>
                    </div>
                    
                    <!-- OTRO TAB !-->
                    <div id="tabs-4">
                        <div class="row">
                            <form id="frm_prod_low_price" name="frm_prod_low_price" class="form-horizontal normal" action="javascript:$('#prodPriceTabla').Search('#frm_prod_low_price','prodLowPriceAjax');">
                              <fieldset class="exa-fieldset" id="prodLowPriceFormTemp">
                                <div class="col-xs-12 col-sm-6">
                                        <legend class="Titulos2">B&uacute;squeda</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                             <div class="col-xs-5 radioset opt_search">
                                                <input id="radioBO1" name="op_opciones" type="radio" value="c"checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radioBO1">C&oacute;digo Barra</label>
                                                <input id="radioBO2" name="op_opciones" type="radio" value="p"checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radioBO2">Producto</label>
                                             </div>
                                        </div>
                                        <div class="form-group">
                                             <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                             <div class="col-sm-5">
                                                 <div class="input-group">
                                                    <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                                    <span class="input-group-btn">
                                                        <button id="btnSearch" onclick="this.form.submit()" class="btn btn-success btn-xs" type="button" title="Buscar Producto"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                                    </span>
                                                 </div>
                                             </div>
                                        </div>
                                       </div>

                                  <!--FILTROS SIN PRECIO -->

                                  <div class="col-xs-6">
                                          <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs " for="Cate3_Cod">Categor&iacute;a:</label>
                                            <div class="col-sm-6">
                                              <?php $row_rs_categ = $obBD_con1->getArrayConsulta(30, $Ses_Emp_Cod, $obBD_conexion); ?>
                                              <select name="Cate3_Cod" id="Cate3_Cod" class="form-control input-xs" data-placeholder="Todas">
                                                <option value="">Todas</option>
                                                <?Php foreach ($row_rs_categ as $row) { ?><option value="<?Php echo $row['Cat_Cod']; ?>"><?Php echo /* strtoupper($row['Par_Cat_Des']).' » '. */$row['Cat_Des']; ?></option><?Php } ?>
                                              </select>
                                            </div>
                                            
                                          </div>

                                          <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs " for="Sub3_Cod">Subcategor&iacute;a:</label>
                                          <div class="col-sm-6">
                                            <select name="Sub3_Cod" id="Sub3_Cod" class="form-control input-xs" data-placeholder="Todas">
                                              <option value=''>Todas</option>
                                            </select>
                                          </div>

                                          </div>
                                          
                                          <div class="form-group">
                                            <label class="col-sm-3 control-label label-xs " for="Ubi3_Cod">Ubicaci&oacute;n:</label>
                                            <div class="col-sm-6">
                                              <?php $rs_ubicacion = $obBD_con1->getArrayConsulta(50, $Ses_Emp_Cod, $obBD_conexion); ?>
                                              <select name="Ubi3_Cod" id="Ubi3_Cod" class="form-control input-xs">
                                                <option value="">Todas</option>
                                                <?Php foreach ($rs_ubicacion as $row) {?><option value="<?Php echo $row['Ubi_Cod']; ?>"><?Php echo $row['Ubi_Des']; ?></option><?Php }?>
                                              </select>
                                            </div>
                                            
                                          </div>
                                        </div>
                                </fieldset>
                            </form>
                        </div>
                        <div id="tablasProdPrice" class="" style="min-height: 550px;">
                           <table id="prodPriceTabla"></table>
                           <div id="prodPriceTablaPager"></div>
                        </div>
                    </div>

                  </div>
               </div>
            </div>
         </div>
      </div>
      </div>

      <script type="text/javascript">
        $('#Cate_Cod').change(function(){
        var cod=$('#Cate_Cod').val();
        $('#Sub_Cod').html('');
        $.get("",{CatSelect:cod}, function( response ) {
        $('#Sub_Cod').html(response);
        //$('#proGrid').trigger('reloadGrid', [{ page: 1 }]);
        // Grid.clearGrid();
        })  
         });
      </script>

      <script type="text/javascript">
        $('#Cate2_Cod').change(function(){
        var cod=$('#Cate2_Cod').val();
        $('#Sub2_Cod').html('');
        $.get("",{CatSelect:cod}, function( response ) {
        $('#Sub2_Cod').html(response);
        //$('#proGrid').trigger('reloadGrid', [{ page: 1 }]);
        // Grid.clearGrid();
        })  
         });
      </script>

      <script type="text/javascript">
        $('#Cate3_Cod').change(function(){
        var cod=$('#Cate3_Cod').val();
        $('#Sub3_Cod').html('');
        $.get("",{CatSelect:cod}, function( response ) {
        $('#Sub3_Cod').html(response);
        //$('#proGrid').trigger('reloadGrid', [{ page: 1 }]);
        // Grid.clearGrid();
        })  
         });
      </script>

      <script src="../VALIDACIONES/fact_val_fac_ven_lista.js?k=321"></script>
      <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
      <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
      <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
      <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
   </BODY>
