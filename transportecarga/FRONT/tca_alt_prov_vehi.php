<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new MysqlDatos(true);
$hoy = date("Y-m-d");

if(isset($provAjax)){ $obBD_con1->getPageGridJson('proveedore.selectWhere', array_merge($_GET,array('setWhere'=>array()))); }

if(isset($searchAjax)){
    $resp=$obBD_con1->getPageGridJson('vehiculo.selectWhere', array_merge($_GET,array('setWhere'=>array('setLastChofer'))) ,true);
}
if(isset($saveData)){
    $resp=array();
    $oBdSet = new MysqlDatos($obBD_con1->getMyCon());
    //$oBdSet->debug(true);
    $oBdSet->beginTrans();
    try{
        $data=array('Veh_Cod'=>$Veh_Cod, 'Prv_Cod'=>$Prv_Cod, 'Veh_Pla'=>$Veh_Pla, 'Veh_Mar'=>$Veh_Mar, 'Veh_Col'=>$Veh_Col );
        if(empty($Veh_Cod)){
            unset($data['Veh_Cod']);
            $oBdSet->operation('vehiculo.insert', $data);
        }else
            $oBdSet->operation('vehiculo.update', $data);
        //$oBdSet->truncateTrans();
        $oBdSet->endTrans($resp);
    } catch(Exception $e){ $oBdSet->revertTrans($e->getMessage(),$resp); }
    $oBdSet->echoJson($resp);
}
if(isset($deleteData)){
    $resp=array('success'=>false);
    $oBdSet = new MysqlDatos($obBD_con1->getMyCon());
    //$oBdSet->debug(true);
    $oBdSet->beginTrans();
    try{
        $oBdSet->operation('vehiculo.setInactive', array('Veh_Cod'=>$Veh_Cod));
        //$oBdSet->truncateTrans();
        $oBdSet->endTrans($resp);
    } catch(Exception $e){ $oBdSet->revertTrans($e->getMessage(),$resp); }
    $oBdSet->echoJson($resp);
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/ecmascript" src="../VALIDACIONES/tca_val_vehiculos.js"></script>
    <style></style>
</HEAD>
<BODY>
<div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestion Vehiculos</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="row">
            <div class="col-sm-6">
                <form id="searchForm" name="searchForm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchForm','searchAjax');">
                    <fieldset class="exa-fieldset form-horizontal normal">
                        <legend class="Titulos2">Busqueda de Vehiculos</legend>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                    <div class="col-sm-10 radioset">
                                        <input id="rad_ba3" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)"/><label for="rad_ba3">&nbsp;&nbsp;Placa&nbsp;&nbsp;</label>
                                        <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)"/><label for="rad_ba2">&nbsp;&nbsp;Proveedor&nbsp;&nbsp;</label>
                                        <input id="rad_ba1" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)"/><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                                        <input id="rad_ba4" name="op_opciones" type="radio" value="n" onclick="setfocus(this.form.search)"/><label for="rad_ba4">&nbsp;&nbsp;No Rel.&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-sm">B&uacute;squeda:</label>
                                    <div class="col-sm-7">
                                        <div class="input-group">
                                            <input type="text" id="search" name="search" onkeydown="if (event.keyCode===13) this.form.submit()" class="form-control input-sm clearable" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                            <span class="input-group-btn">
                                                <button class="btn btn-success btn-sm" type="button" title="Buscar Cliente" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                            </span>
                                        </div>
                                    </div>
                                    <label class="col-sm-3 control-label label-sm "><input type="checkbox" name="isActive" value="S" class="check-big" checked="" />&nbsp;&nbsp;&nbsp;Activos</label>
                                </div>
                    </fieldset>
                </form>
                <div class="">
                    <table id="searchGrid"></table>
                    <div id="searchGridPager"></div>
                </div>
            </div>
            <div id="edit" class="col-sm-6">
                <div class="panel exa-panel">
                    <div class="panel-heading ui-widget-header"><span class="panel-title">Datos Vehiculo</span></div>
                    <div class="panel-body">
                        <form id="frm_aut" name="frm_aut" class="form-horizontal normal" action="javascript:saveItem();">
                        <fieldset class="exa-fieldset" id="provFormTemp">
                            <legend class="Titulos2">Datos del Proveedor</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                                <div class="col-xs-6" >
                                  <input name="Prs_Cod" data-name="Prs_Cod" type="text" style="display:none;" />
                                  <input name="Prv_Cod" data-name="Prv_Cod" type="text" style="display:none;" />
                                  <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
                                  <div class="input-group input-group-xs">
                                    <input name="search" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#provDialog',selectProvee); }" type="text" placeholder="Ingrese Proveedor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                    <span class="input-group-btn">
                                        <button id="Prv_Btn" type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                        <!--<button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open'); $('#reset').val(1); " class="btn btn-success btn-xs" title="Registrar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>-->
                                    </span>
                                  </div>
                                </div>
                                <label class="col-xs-4 control-label label-xs">Oblig.Contab:&nbsp;<i id="Prv_Con" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Proveedor:</label>
                                <div class="col-xs-6" ><span name="Proveedor" data-name="Proveedor" class="form-control input-xs databind datatitle"></span></div>
                                <label class="col-xs-4 control-label label-xs">Contr.Especial:&nbsp;<i  id="Prv_Esp" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Dirección:</label>
                                <div class="col-xs-10" >
                                    <div class="input-group input-group-xs">
                                        <input name="Prs_Dir" data-name="Prs_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
                                        <span class="input-group-addon bold">e-mail:</span>
                                        <input name="Prs_Cor" data-name="Prs_Cor" type="text" class="form-control span datatitle" readonly="" tabindex="-1" />
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="exa-fieldset" >
                            <legend class="Titulos2">Datos del Vehiculo</legend>
                            <input type="text" id="Veh_Cod" name="Veh_Cod" class="hidden">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm required">Placa:</label>
                                <div class="col-md-7 col-sm-4">
                                    <input type="text" id="Veh_Pla" name="Veh_Pla" class="form-control input-xs" required="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm">Marca:</label>
                                <div class="col-md-7 col-sm-4">
                                    <input type="text" id="Veh_Mar" name="Veh_Mar" class="form-control input-xs">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm">Color:</label>
                                <div class="col-md-7 col-sm-4">
                                    <input type="text" id="Veh_Col" name="Veh_Col" class="form-control input-xs">
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="exa-fieldset" id="provFormTemp">
                            <legend class="Titulos2">Ultimo Chofer</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Ruc:</label>
                                <div class="col-xs-6" ><span name="Ruc_Chofer"  class="form-control input-xs datatitle"></span></div>
                                <label class="col-xs-2 control-label label-xs">Licencia:</label>
                                <div class="col-xs-2" ><span name="Cho_Tli"  class="form-control input-xs datatitle"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Chofer:</label>
                                <div class="col-xs-6" ><span name="Chofer"  class="form-control input-xs datatitle"></span></div>
                                <label class="col-xs-1 control-label label-xs">Telf.:</label>
                                <div class="col-xs-3" ><span name="Cho_Tel"  class="form-control input-xs datatitle"></span></div>
                            </div>
                        </fieldset>
                        <div style="text-align: center;">
                            <button type="button" onclick="$('#edit').fadeOut();" class="btn btn-inverse btn-sm"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
<div id="provDialog" title="B&uacute;squeda de Proveedor"></div>

<script type="text/javascript">

</script>
</BODY>
</HTML>