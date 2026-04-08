<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
//require_once('../LOGICA/ban_log_productor.php');
//require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new MysqlDatos(true);

if(isset($searchAjax)){ $resp=$obBD_con1->getPageGridJson('viaje', array_merge($_GET,array('setWhere'=>array())), true ); }
if(isset($clieAjax)||isset($clienteAjax)){ $obBD_con1->getPageGridJson('cliente', array_merge($_GET,array('setWhere'=>array('isActive')))); }
if(isset($provAjax)){ $obBD_con1->getPageGridJson('proveedore', array_merge($_GET,array('setWhere'=>array('isActive')))); }
if(isset($productoAjax)){ $page=$obBD_con1->getPageGridJson('producto', array_merge($_GET,array('setWhere'=>array('isActive') ))); }
if(isset($personaAjax)){
    $page=$obBD_con1->getPageGridJson('persona', array_merge($_GET,array(
        'setWhere'=>array('isActive'),
        'where'=>array('Cho_Cod'=>null),
        'join'=>array( 'chofer'=>array('type'=>'joinLeft','pk'=>'Prs_Cod','cols'=>array()) )
    )));

}
if(isset($kmAjax)){
    $sel=$obBD_con1->select()->from('viaje', array('Via_Cod','Via_Kil'))->where("Ori_Cod=? AND Des_Cod=?",array($Ori_Cod,$Des_Cod))->order('Via_Fec DESC')->limit(1);
    $obBD_con1->echoJson(array('success'=>true, 'kilometraje'=>$obBD_con1->getRow(null, $sel)));
}
// guardo el viaje
if(isset($saveForm)){
    $resp=array();
    $oBdSet = new MysqlDatos($obBD_con1->getMyCon());
    //$oBdSet->debug(true);
    $oBdSet->beginTrans();
    try{
        if(empty($data['Via_Cod'])){
            $resp['id']=$oBdSet->operation("viaje.insert", $data)->lastId();
        }else{
            $resp['id']=$data['Via_Cod'];
            $oBdSet->operation("viaje.update", $data);
        }
        //$oBdSet->truncateTrans(); //si se guardo bien detengo el commit
        $oBdSet->endTrans($resp);
    } catch(Exception $e){ $oBdSet->revertTrans($e->getMessage(),$resp); }
    $oBdSet->echoJson($resp);
}
// guardo datos de los selects
if(isset($saveData)){
    $resp=array();
    $tables=array('modo'=>'modo_trabajo','carga'=>'cargamento','chofer'=>'chofer','vehiculo'=>'vehiculo','origen'=>'viaje_lugar','destino'=>'viaje_lugar');
    $oBdSet = new MysqlDatos($obBD_con1->getMyCon());
    //$oBdSet->debug(true);
    $oBdSet->beginTrans();
    try{
        if(!isset($tables[$saveData])) $oBdSet->truncateTrans("Error Registro Imposible de Guardar!");
        $resp['id']=$oBdSet->operation("$tables[$saveData].insert", $data)->lastId();
        //$oBdSet->truncateTrans(); //si se guardo bien detengo el commit
        $oBdSet->endTrans($resp);
    } catch(Exception $e){ $oBdSet->revertTrans($e->getMessage(),$resp); }
    $oBdSet->echoJson($resp);
}
// guardo d cambio de cliente grupal
if(isset($changeCliente)){
    $resp=array();
    $oBdSet=new MysqlDatos($obBD_con1->getMyCon());
    //$oBdSet->debug(true);
    $oBdSet->beginTrans();
    try{
        if(!isset($data)) $oBdSet->truncateTrans("Error Registro Imposible de Guardar!");
        foreach ($data as $id){
            $oBdSet->operation("viaje.update", array('Cli_Cod'=>$Cli_Cod,'where'=>array('Via_Cod'=>$id)));
        }
        //$oBdSet->truncateTrans(); //si se guardo bien detengo el commit
        $oBdSet->endTrans($resp);
    } catch(Exception $e){ $oBdSet->revertTrans($e->getMessage(),$resp); }
    $oBdSet->echoJson($resp);
}
// guardo d cambio de cliente grupal
if(isset($deleteData)){
    $resp=array();
    $oBdSet=new MysqlDatos($obBD_con1->getMyCon());
    //$oBdSet->debug(true);
    $oBdSet->beginTrans();
    try{
        if(!isset($Via_Cod)) $oBdSet->truncateTrans("Error Registro Imposible de Anular!");
        $oBdSet->operation("viaje.update", array('Via_Est'=>'I','where'=>array('Via_Cod'=>$Via_Cod)));

        //$oBdSet->truncateTrans(); //si se guardo bien detengo el commit
        $oBdSet->endTrans($resp);
    } catch(Exception $e){ $oBdSet->revertTrans($e->getMessage(),$resp); }
    $oBdSet->echoJson($resp);
}
$choferes=$obBD_con1->getArray('chofer', array('setWhere'=>array('isActive','setLastVehiculo','setEmpCod')));
$vehiculos=$obBD_con1->getArray('vehiculo', array('setWhere'=>array('isActive','setEmpCod')));
$cargamento=$obBD_con1->getArray('cargamento', array('setWhere'=>array('isActive','setEmpCod')));
$mtrabajo=$obBD_con1->getArray('modo_trabajo', array('setWhere'=>array('isActive','setEmpCod')));
$origen=$obBD_con1->getArray('viaje_lugar', array('setWhere'=>array('isActive','setEmpCod','isOrigen')));
$destino=$obBD_con1->getArray('viaje_lugar', array('setWhere'=>array('isActive','setEmpCod','isDestino')));
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/tca_val_viaje.js"></script>
    <style>.over.desc{font-size: 11px;}</style>
</HEAD>
<BODY>
<div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestion Viajes</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="row">
            <div id="search" class="col-sm-12">
                <form id="searchForm" name="searchForm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchForm','searchAjax');">
                    <div class="row">
                    <div class="col-sm-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Seleccionar Cliente</legend>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-xs required">C&eacute;dula/R.U.C.:</label>
                                <div class="col-md-7 col-sm-7">
                                    <input type="hidden" name="Cli_Cod" data-cliente="Cli_Cod">
                                    <div class="input-group">
                                        <input type="text" name="Prs_Ced" data-cliente="Prs_Ced" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="clieDialog.data('search',true).dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                            <button class="btn btn-success btn-xs" type="button" title="Limpiar Par&aacute;metros" onclick="selectCliente({});"><span class="glyphicon glyphicon-eject"></span></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3 label-xs">Cliente:</label>
                                <div class="col-sm-7"><span data-cliente="Cliente" class="form-control input-xs cliente"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3 label-xs">Direcci&oacute;n:</label>
                                <div class="col-sm-6"><span data-cliente="Prs_Dir" class="form-control input-xs cliente"></span></div>
                                <div class="col-sm-3 center"><button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button></div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-md-6" id="reportDateRange">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Filtrar Por:</legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Estado:</label>
                                <div class="col-sm-10 radioset">
                                    <input id="r1" name="op_opciones" type="radio" value="T" onclick="setfocus(this.form.search)"/><label for="r1">&nbsp;&nbsp;Todos&nbsp;&nbsp;</label>
                                    <input id="r2" name="op_opciones" type="radio" value="F" onclick="setfocus(this.form.search)"/><label for="r2">&nbsp;&nbsp;Facturado&nbsp;&nbsp;</label>
                                    <input id="r3" name="op_opciones" type="radio" value="NF" checked="" onclick="setfocus(this.form.search)"/><label for="r3">&nbsp;&nbsp;Sin Facturar&nbsp;&nbsp;</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Fecha:</label>
                                <div class="col-sm-8">
                                    <div class="input-group input-group-xs dateRangeInputs">
                                        <span class="input-group-addon alert-default" ><input type="checkbox" name="byDates" value="S" offval="N" class="check-big" onchange="$(this).closest('.dateRangeInputs').find('input[type=text]').prop('disabled',!$(this).is(':checked')).end().find('.alert-info')[$(this).is(':checked')?'removeClass':'addClass']('alert-disabled')" /></span>
                                        <span class="input-group-addon alert-info alert-disabled" >Desde</span>
                                        <input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control" disabled="">
                                        <span class="input-group-addon alert-info alert-disabled" >Hasta</span>
                                        <input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control" disabled="">
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    </div>
                </form>
                <div class="">
                    <table id="searchGrid"></table>
                    <div id="searchGridPager"></div>
                </div>
            </div>
            <div id="edit" class="col-sm-12">
                <div class="panel exa-panel">
                    <div class="panel-heading ui-widget-header"><span class="panel-title">Datos Viaje</span></div>
                    <div class="panel-body">
                        <form id="formViaje" name="formViaje" class="form-horizontal normal" action="javascript:saveItem();">
                            <input type="text" class="hidden" id="Via_Cod" name="Via_Cod" />
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Cliente</legend>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-xs required">C&eacute;dula/R.U.C.:</label>
                                        <div class="col-md-7 col-sm-7">
                                            <div class="input-group">
                                                <input type="text" class="form-control input-xs overwritten" data-cliente="Cli_Cod" name="Cli_Cod" tabindex="-1" required>
                                                <input type="text" data-cliente="Prs_Ced" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="" tabindex="-1">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="clieDialog.data('search',false).dialog('open');"  tabindex="1" ><span class="glyphicon glyphicon-search"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-3 label-xs">Cliente:</label>
                                        <div class="col-sm-7"><span data-cliente="Cliente" class="form-control input-xs cliente"></span></div>
                                    </div>
                                </fieldset>

                                <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Viaje</legend>
                                <div class="form-group">
                                    <label class="control-label col-md-2 col-sm-3 label-xs required">Fecha:</label>
                                    <div class="col-md-4 col-sm-3">
                                        <input type="text" id="Via_Fec" name="Via_Fec" class="form-control input-xs datepicker datatrigger" required="" tabindex="2" />
                                    </div>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon alert-info">Año</span>
                                            <span id="Anio" name="Anio" class="form-control">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                                            <span class="input-group-addon alert-info">Semana</span>
                                            <select type="text" name="Via_Sem" class="form-control"  tabindex="3" ></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs">Origen:</label>
                                    <div class="col-sm-5">
                                        <div class="input-group input-group-xs">
                                        <select id="Ori_Cod" name="Ori_Cod" class="form-control input-xs datatrigger chosenDesc origen" label='Vlu_Aco' data-setter="origen" tabindex="4" >
                                            <option value="">Selecciona el Origen...</option>
                                            <?php echo $obBD_con1->htmlOptions($origen, 'Vlu_Cod', 'Vlu_Aco', true); ?>
                                        </select>
                                        <span class="input-group-btn"><button class="btn btn-info" type="button" title="Agregar Origen" onclick="$('#acopioNom').html('Origen');$('#lugarDialog').setData({Vlu_Tip:'O'});$('#lugarDialog').dialog('open');" tabindex="-1"><span class="glyphicon glyphicon-plus"></span></button></span>
                                        </div>
                                    </div>
                                    <label class="control-label col-sm-2 label-xs">Zona Ori.:</label>
                                    <div class="col-sm-3"><span data-origen="Vlu_Zon" class="form-control input-xs datatitle"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs">Destino:</label>
                                    <div class="col-sm-5">
                                        <div class="input-group input-group-xs">
                                        <select id="Des_Cod" name="Des_Cod" class="form-control input-xs datatrigger chosenDesc destino" label='Vlu_Aco' data-setter="destino" tabindex="5" >
                                            <option value="">Selecciona el Destino...</option>
                                            <?php echo $obBD_con1->htmlOptions($destino, 'Vlu_Cod', 'Vlu_Aco', true); ?>
                                        </select>
                                        <span class="input-group-btn"><button class="btn btn-info" type="button" title="Agregar Destino" onclick="$('#acopioNom').html('Destino');$('#lugarDialog').setData({Vlu_Tip:'D'});$('#lugarDialog').dialog('open');" tabindex="-1"><span class="glyphicon glyphicon-plus"></span></button></span>
                                        </div>
                                    </div>
                                    <label class="control-label col-sm-2 label-xs">Zona Dest.:</label>
                                    <div class="col-sm-3"><span data-destino="Vlu_Zon" class="form-control input-xs datatitle"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs required">M.&nbsp;Trabajo:</label>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-xs">
                                        <select name="Mot_Cod" class="form-control input-xs modo" tabindex="6" label='Mot_Des' required >
                                            <option value="">Seleccione...</option>
                                            <?php echo $obBD_con1->htmlOptions($mtrabajo, 'Mot_Cod', 'Mot_Des'); ?>
                                        </select>
                                        <span class="input-group-btn"><button class="btn btn-info" type="button" title="Agregar Modo Trabajo" onclick="$('#modoDialog').dialog('open');" tabindex="-1"><span class="glyphicon glyphicon-plus"></span></button></span>
                                        </div>
                                    </div>
                                    <label class="control-label col-sm-2 label-xs">Transito:</label>
                                    <div class="col-sm-4">
                                        <select name="Via_Tra" class="form-control input-xs" tabindex="7" data-default="N" required >
                                            <option value="N">INDEFINIDO</option>
                                            <option value="E">EXPORTACION</option>
                                            <option value="I">IMPORTACION</option>
                                            <option value="L">LOCAL</option>
                                            <option value="O">OTRAS</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs required">V&nbsp;Venta:</label>
                                    <div class="col-sm-10">
                                    <div class="input-group input-group-xs" id='TotalGroup'>
                                        <span class="input-group-addon alert-success required">Cant.:</span>
                                        <input type="text" name="Via_Can" class="form-control input-xs txtRight decimal TotalVals datatrigger" decimals='2' tabindex="10" required="" />
                                        <span class="input-group-addon alert-success required">P.Unit.:</span>
                                        <input type="text" name="Via_Pru" class="form-control input-xs txtRight decimal TotalVals datatrigger" decimals='2' tabindex="11" required="" />
                                        <span class="input-group-addon alert-success bold">TOTAL:</span><!--<span class="input-group-addon">$</span>-->
                                        <input type="text" data-total="Via_Tot" class="form-control input-xs txtRight readOnly bold isMoney" tabindex="-1" disabled="" placeholder="0.00" />
                                    </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs">V&nbsp;Compra:</label>
                                    <div class="col-sm-10">
                                    <div class="input-group input-group-xs" id='TotalComprasGroup'>
                                        <span class="input-group-addon alert-success" style="visibility: hidden;">&nbsp;&nbsp;&nbsp;Cant.:</span>
                                        <input type="text" class="form-control input-xs txtRight decimal TotalCVals datatrigger" decimals='2' tabindex="-1"  style="visibility: hidden;" />
                                        <span class="input-group-addon alert-success">&nbsp;&nbsp;&nbsp;P.Unit.:</span>
                                        <input type="text" name="Via_Cpr" class="form-control input-xs txtRight decimal TotalCVals datatrigger" decimals='2' tabindex="9"  />
                                        <span class="input-group-addon alert-success bold">TOTAL:</span><!--<span class="input-group-addon">$</span>-->
                                        <input type="text" data-total="Via_Cto" class="form-control input-xs txtRight readOnly bold isMoney" tabindex="-1" disabled="" placeholder="0.00"  />
                                    </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-5"></div>
                                    <div class="col-sm-7">
                                    <div class="input-group input-group-xs"  id='Utilidad'>
                                        <span class="input-group-addon alert-info bold">Km.:</span>
                                        <input type="text" id="Via_Kil" name="Via_Kil" class="form-control input-xs txtRight readOnly bold numeric isNumeric" tabindex="-1" placeholder="0" />
                                        <span class="input-group-addon alert-warning bold">UTILIDAD:</span>
                                        <input type="text" data-total="Uti_Tot" class="form-control input-xs txtRight readOnly bold isMoney" tabindex="-1" disabled="" placeholder="0.00" />
                                    </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs txtLeft">Observación:</label>
                                    <div class="col-sm-10"><textarea name="Via_Des" class="form-control input-xs" tabindex="12"></textarea></div>
                                </div>
                                </fieldset>

                            </div>
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset" id="provFormTemp">
                                <legend class="Titulos2">Datos del Vehiculo</legend>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs required">Chofer:</label>
                                    <div class="col-sm-7">
                                        <div class="input-group input-group-xs">
                                        <select name="Cho_Cod" class="form-control input-xs datatrigger chosen chofer" data-setter="chofer" tabindex="13" label='Chofer' format='formatChofer' required >
                                            <option value="">Selecciona Al Chofer...</option>
                                            <?php echo $obBD_con1->htmlOptions($choferes, 'Cho_Cod', 'Chofer', true); ?>
                                        </select>
                                        <span class="input-group-btn"><button class="btn btn-info" type="button" title="Agregar Conductor" onclick="$('#choferDialog').dialog('open');" tabindex="-1"><span class="glyphicon glyphicon-plus"></span></button></span>
                                        </div>
                                    </div>
                                    <label class="control-label col-sm-1 label-xs">Lic.:</label>
                                    <div class="col-sm-2"><span data-chofer="Cho_Tli" class="form-control input-xs"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs">Cedula:</label>
                                    <div class="col-sm-4"><span data-chofer="Ruc_Chofer" class="form-control input-xs"></span></div>
                                    <label class="control-label col-sm-2 label-xs">Telef.:</label>
                                    <div class="col-sm-4"><span data-chofer="Cho_Tel" class="form-control input-xs"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs required">Vehiculo:</label>
                                    <div class="col-sm-7">
                                        <div class="input-group input-group-xs">
                                        <select name="Veh_Cod" data-chofer="Veh_Cod" class="form-control input-xs datatrigger chosen vehiculo" data-setter="vehiculo" label="Vehiculo" format='formatVeh' tabindex="14" required >
                                            <option value="">Selecciona el Vehiculo...</option>
                                            <?php function formatVeh($v){ return "$v[Veh_Pla] $v[Veh_Mar] $v[Veh_Col]"; }
                                            echo $obBD_con1->htmlOptions($vehiculos, 'Veh_Cod', 'formatVeh', true);  ?>
                                        </select>
                                        <span class="input-group-btn"><button class="btn btn-info" type="button" title="Agregar Vehículo" onclick="$('#vehiculoDialog').dialog('open');" tabindex="-1"><span class="glyphicon glyphicon-plus"></span></button></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs">Proveedor:</label>
                                    <div class="col-sm-10"><span data-vehiculo="Proveedor" class="form-control input-xs"></span></div>
                                </div>
                                </fieldset>

                                <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Cargamento</legend>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs required">Carga:</label>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-xs">
                                        <select name="Car_Cod" class="form-control input-xs carga" tabindex="15" label='Car_Des' required >
                                            <option value="">Seleccione...</option>
                                            <?php echo $obBD_con1->htmlOptions($cargamento, 'Car_Cod', 'Car_Des'); ?>
                                        </select>
                                        <span class="input-group-btn"><button class="btn btn-info" type="button" title="Agregar Cargamento" onclick="$('#cargaDialog').dialog('open');" tabindex="-1"><span class="glyphicon glyphicon-plus"></span></button></span>
                                        </div>
                                    </div>
                                    <label class="control-label col-sm-2 label-xs">Unidad:</label>
                                    <div class="col-sm-4"><input type="text" name="Via_Uni" class="form-control input-xs" tabindex="16" /></div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs">Contenedor:</label>
                                    <div class="col-sm-4"><input type="text" name="Via_Con" class="form-control input-xs" tabindex="17" /></div>
                                    <label class="control-label col-sm-2 label-xs ">Sello:</label>
                                    <div class="col-sm-4"><input type="text" name="Via_Sel" class="form-control input-xs" tabindex="18" /></div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs">Serie:</label>
                                    <div class="col-sm-4"><input type="text" name="Via_Ded" class="form-control input-xs" tabindex="19" /></div>
                                    <label class="control-label col-sm-2 label-xs ">Booking:</label>
                                    <div class="col-sm-4"><input type="text" name="Via_Has" class="form-control input-xs" tabindex="20" /></div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2 label-xs">Lleva:</label>
                                    <div class="col-sm-4"><input type="text" name="Via_Lle" class="form-control input-xs" tabindex="21" /></div>
                                    <label class="control-label col-sm-2 label-xs ">Guia:</label>
                                    <div class="col-sm-4"><input type="text" name="Via_Gui" class="form-control input-xs" tabindex="22" /></div>
                                </div>
                                </fieldset>
                            </div>
                            <div class="col-sm-12">
                                <div class="center">
                                    <button type="button" onclick="$('#edit').moveComp('#search').updateGridsSizes();" class="btn btn-inverse btn-sm" tabindex="22"><span class="glyphicon glyphicon-arrow-left"></span> Cancelar</button>
                                    <button type="button" class="btn btn-sm btn-success" onclick="$(this.form).formSubmit();" tabindex="23"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button></div><div class="separator">
                                </div>
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--INICIO DEL DIALOGO BUSCAR CLIENTE-->
<div id="clieDialog" title="B&uacute;squeda de Cliente"></div>
<!-- Inicio del diálogo para buscar un producto -->
<div id="productoDialog" title="B&uacute;squeda de Productos"></div>
<!-- Inicio del diálogo para buscar una persona -->
<div id="personaDialog" title="B&uacute;squeda de Persona"></div>
<!-- Inicio del diálogo para buscar una persona -->
<div id="provDialog" title="B&uacute;squeda de Proveedor"></div>
<!-- Inicio del diálogo para agregar un modo trabajo -->
<div id="modoDialog" title="Registrar Modo de Trabajo">
    <div class="row">
        <div class="col-md-12">
            <form id="modoForm" name="modoForm" class="form-horizontal normal" action="javascript:saveDatos.call($('#modoForm'));" data-tipo='modo'>
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Formulario de Registro</legend>
                    <div class="form-group">
                        <label class="control-label col-xs-3 label-sm required">Descripci&oacute;n:</label>
                        <div class="col-xs-9"><input type="text" name="Mot_Des" class="form-control input-xs" required=""></div>
                    </div>
                </fieldset>
                <div class='center'><button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button></div>
            </form>
        </div>
    </div>
</div>
<!-- Inicio del diálogo para agregar un cargamento -->
<div id="cargaDialog" title="Registrar Cargamento">
    <div class="row">
        <div class="col-md-12">
            <form id="cargaForm" name="cargaForm" class="form-horizontal normal" action="javascript:saveDatos.call($('#cargaForm'));" data-tipo='carga'>
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Formulario de Registro</legend>
                    <div class="form-group">
                        <label class="control-label col-xs-3 label-sm required">Producto:</label>
                        <div class="col-xs-9">
                            <div class="input-group">
                                <input type="text" name="Pro_Cod" data-name="Pro_Cod" class="form-control input-xs overwritten" required />
                                <span type="text" data-name="Producto" class="form-control input-xs" placeholder="Seleccione un producto" ></span>
                                <span class="input-group-btn"><button type="button" class="btn btn-success btn-xs" onclick="$('#productoDialog').dialog('open');" title="Buscar Producto"><span class="glyphicon glyphicon-search"></span></button></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-xs-3 label-sm required">Descripci&oacute;n:</label>
                        <div class="col-xs-9"><input type="text" name="Car_Des" class="form-control input-xs" required="" /></div>
                    </div>
                </fieldset>
                <div class="center"><button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button></div>
            </form>
        </div>
    </div>
</div>
<!-- Inicio del diálogo para agregar un chofer -->
<div id="choferDialog" title="Registrar Conductor">
    <div class="row">
        <div class="col-md-12">
            <form id="choferForm" name="choferForm" class="form-horizontal normal" action="javascript:saveDatos.call($('#choferForm'));" data-tipo='chofer'>
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Formulario de Registro</legend>
                    <div class="form-group">
                        <label class="control-label col-sm-4 label-sm required">C&eacute;dula/R.U.C.:</label>
                        <div class="col-sm-7">
                            <div class="input-group">
                                <input type="text" name="Prs_Cod" data-name="Prs_Cod" class="form-control input-xs overwritten" required="" tabindex="-1"  />
                                <span type="text" data-name="Prs_Ced" class="form-control input-xs cedula" placeholder="Ingrese C&eacute;dula/R.U.C."></span>
                                <span class="input-group-btn"><button type="button" class="btn btn-success btn-xs" onclick="$('#personaDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span></button></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-4 label-sm">Nombres:</label>
                        <div class="col-sm-7">
                            <input type="hidden" data-name="Persona" class="form-control input-xs persona" tabindex="-1" readonly="" />
                            <input type="text" data-name="Prs_Nom" class="form-control input-xs" tabindex="-1" readonly="" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-4 label-sm">Apellidos:</label>
                        <div class="col-sm-7">
                            <input type="text" data-name="Prs_Ape" class="form-control input-xs" tabindex="-1" readonly="" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-4 label-sm required">Licencia Tipo:</label>
                        <div class="col-sm-4"><input type="text" name="Cho_Tli" class="form-control input-xs" required="" /></div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-4 label-sm">Telef.:</label>
                        <div class="col-sm-4"><input type="text" name="Cho_Tel" data-name="Prs_Tel" class="form-control input-xs" /></div>
                    </div>
                </fieldset>
                <div class="center">
                    <button type="button" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Inicio del diálogo para agregar un automotor -->
<div id="vehiculoDialog" title="Registrar Automotor">
    <div class="row">
        <div class="col-md-12">
            <form id="vehiculoForm" name="vehiculoForm" class="form-horizontal normal" action="javascript:saveDatos.call($('#vehiculoForm'));" data-tipo='vehiculo'>
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Formulario de Registro</legend>
                    <div class="form-group">
                        <label class="control-label col-xs-3 label-sm required">Ruc&nbsp;Prov.:</label>
                        <div class="col-xs-7">
                            <div class="input-group">
                                <input type="text" name="Prv_Cod" data-name="Prv_Cod" class="form-control input-xs overwritten" required />
                                <span type="text" data-name="Ruc" class="form-control input-xs" placeholder="Seleccione un Provedor" ></span>
                                <span class="input-group-btn"><button type="button" class="btn btn-success btn-xs" onclick="$('#provDialog').dialog('open');" title="Buscar Proveedor"><span class="glyphicon glyphicon-search"></span></button></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-4 label-sm required">Proveedor:</label>
                        <div class="col-md-9 col-sm-4"><span type="text" data-name="Proveedor" class="form-control input-xs proveedor" placeholder="Seleccione un producto" ></span></div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-4 label-sm required">Placa:</label>
                        <div class="col-md-9 col-sm-4"><input type="text" id="Veh_Pla" name="Veh_Pla" class="form-control input-xs" required=""></div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-4 label-sm">Marca:</label>
                        <div class="col-md-9 col-sm-4"><input type="text" id="Veh_Mar" name="Veh_Mar" class="form-control input-xs"></div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-4 label-sm">Color:</label>
                        <div class="col-md-9 col-sm-4"><input type="text" id="Veh_Col" name="Veh_Col" class="form-control input-xs"></div>
                    </div>
                </fieldset>
                <div class="center">
                    <button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Inicio del diálogo para agregar un lugar viaje -->
<div id="lugarDialog" title="Registrar Lugar/Acopio">
    <div class="row">
        <div class="col-md-12">
            <form id="lugarForm" name="lugarForm" class="form-horizontal normal" action="javascript:$('#lugarForm').data('tipo',$('#Vlu_Tip').val()==='D'?'destino':'origen');saveDatos.call($('#lugarForm'));">
                <input type="text" id='Vlu_Tip' name="Vlu_Tip" class="hidden" />
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Formulario de Registro</legend>
                    <div class="form-group">
                        <label id="acopioNom" class="control-label col-sm-4 label-sm required">Lugar/Acopio:</label>
                        <div class="col-sm-8"><input type="text" name="Vlu_Aco" class="form-control input-xs" required="" /></div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-4 label-sm">Zona:</label>
                        <div class="col-sm-8"><input type="text" name="Vlu_Zon" class="form-control input-xs" /></div>
                    </div>
                </fieldset>
                <div class="center">
                    <button type="button" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Inicio del diálogo para buscar viajes -->
<div id="viajesDialog" title="Viajes Seleccionados" style="display: none;">
    <form id="viajesForm" class="form-horizontal normal" data-action="javascript:$.createDialogConfirm('¿Esta seguro que desea <b class=&quot;green&quot;>CAMBIAR CLIENTE</b> de <b class=&quot;blue&quot;>VIAJE/TRANSPORTE</b>?', $('#viajesGrid').getCol('Via_Cod'), changeCliente);" >
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Datos del Cliente</legend>
            <div class="form-group">
                <label class="control-label col-xs-3 label-xs required">C&eacute;dula/R.U.C.:</label>
                <div class="col-xs-7">
                    <div class="input-group">
                        <input type="text" class="form-control input-xs overwritten" data-cliente="Cli_Cod" name="Cli_Cod" tabindex="-1" required>
                        <input type="text" data-cliente="Ruc" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="" tabindex="-1">
                        <span class="input-group-btn">
                            <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="$('#clienteDialog').dialog('open');"  tabindex="1" ><span class="glyphicon glyphicon-search"></span></button>
                        </span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-xs-3 label-xs">Cliente:</label>
                <div class="col-xs-7"><span data-cliente="Cliente" class="form-control input-xs cliente"></span></div>
                <div class='col-xs-2'><button type="button" onclick="$(this.form).formSubmit()" class="btn btn-success btn-sm" title="Guardar Cambio" tabindex="-1"><i class="glyphicon glyphicon-floppy-disk"></i> <span>Guardar</span></button></div>
            </div>
        </fieldset>
    </form>
</div>
<script type="text/javascript">

</script>
<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>
</HTML>