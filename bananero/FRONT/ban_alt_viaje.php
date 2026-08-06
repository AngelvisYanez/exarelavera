<?php

/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_viaje.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Viaje;

if (isset($searchAjax)) {
    $page = $obBD_con1->getPageGrid('naviera_vapor.selectWhere', $_GET, $obBD_conexion);
    foreach ($page['rows'] as &$v) {
        $conteo = $obBD_con1->getRowConsulta('naviera_vapor.sql.countContenedores', $v['Vap_Cod'], $obBD_conexion);
        $v['Contenedores'] = isset($conteo['total']) && !empty($conteo['total']) ? $conteo['total'] * 1 : 0;
    }
    unset($v);
    $obBD_con1->echoJson($page);
}
if (isset($pedidosDetAjax)) {
    $page = $obBD_con1->getPageGridJson('naviera_container.selectWhere', $_GET, $obBD_conexion);
}
if (isset($savePedido)) {
    $resp = array('success' => false);
    if (isset($resp['message'])) $obBD_con1->echoJson($resp);

    $obBD_ins1 =  new Class_Log_Datos_Viaje;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {
        $saver = $dato;
        $isNew = !isset($saver['Vap_Cod']) || empty($saver['Vap_Cod']);
        $obBD_ins1->operacionobBD('naviera_vapor.' . ($isNew ? 'insert' : 'update'), $saver, $obBD_conexionIns);
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns); // finalizo la transaccion y compruebo errores
    if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}
if (isset($saveDetalle)) {
    $resp = array('success' => false);
    if (isset($resp['message'])) $obBD_con1->echoJson($resp);

    $obBD_ins1 =  new Class_Log_Datos_Viaje;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {
        $saver = $dato;
        $isNew = !isset($saver['Nco_Cod']) || empty($saver['Nco_Cod']);
        $obBD_ins1->operacionobBD('naviera_container.' . ($isNew ? 'insert' : 'update'), $saver, $obBD_conexionIns);
        $Nco_Cod = $isNew ? $obBD_ins1->insercionid($obBD_conexionIns) : $saver['Nco_Cod'];
        if (!$isNew) {
            $obBD_ins1->operacionobBD('exporta_planif_container.deleteWhere', array('where' => array('Nco_Cod' => $Nco_Cod)), $obBD_conexionIns);
        }
        if (isset($Pde_Cod) && !empty($Pde_Cod)) {
            $obBD_ins1->operacionobBD('exporta_planif_container.insert', array('Nco_Cod' => $Nco_Cod, 'Pde_Cod' => $Pde_Cod), $obBD_conexionIns);
        }
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns); // finalizo la transaccion y compruebo errores
    if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}
if (isset($changeEstado)) {
    $resp = array('success' => false);
    $obBD_ins1 =  new Class_Log_Datos_Viaje;
    //$obBD_ins1->debug(true);
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {
        $obBD_ins1->operacionobBD('naviera_container.update', array('where' => array('Nco_Cod' => $Nco_Cod), 'Nco_Est' => $Nco_Est), $obBD_conexionIns);
    } catch (Exception $e) {
        $obBD_con_set->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $e->getMessage();
        $obBD_ins1->echoJson($resp);
    }
    $resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns); // finalizo la transaccion y compruebo errores
    if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
    $obBD_ins1->echoJson($resp);
}
if (isset($getPlanif)) {
    $resp = array('success' => true);
    $resp['planif'] = $obBD_con1->getArrayConsulta('exporta_planif_det.selectWhere',  array('where' => array('Exd_Cod' => $dato['Exd_Cod'], 'Pln_Ano' => $dato['Vap_Ano'], 'Pln_Sem' => $dato['Vap_Sem']), 'setWhere' => array('isActive', 'setPersona')), $obBD_conexion, true);
    $obBD_con1->echoJson($resp);
}
$hoy = date("Y-m-d");
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => 'setEmpCod', 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
$destinos = $obBD_con1->getArrayConsulta('exporta_dest.selectWhere',  array('setWhere' => array('isActive')), $obBD_conexion);
$partidas = $obBD_con1->getArrayConsulta('exporta_dist.selectWhere',  array('setWhere' => array('isActive')), $obBD_conexion);
$navieras = $obBD_con1->getArrayConsulta('naviera_exporta.selectWhere',  array('setWhere' => array('setEmpCod')), $obBD_conexion);
$cur_periodo = current($periodos);
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../VALIDACIONES/ban_val_viaje.js?x=1"></script>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/tagsinput/bootstrap-tagsinput.min.css">
    <script type="text/javascript" src="../../framework/jquery/bootstrap/tagsinput/bootstrap-tagsinput.min.js"></script>
    <style></style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Gestion Containers</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="divSearch" class="row">
                <div class="col-sm-12">
                    <form id="searchForm" name="searchForm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchForm','searchAjax');">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Consulta de Información</legend>
                            <div class="col-sm-4">

                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Periodo:</label>
                                    <div class="col-xs-7">
                                        <select id="Lib_Ano" name="Vap_Ano" class="form-control input-xs">
                                            <option value="">Periodo..</option>
                                            <?php foreach ($periodos as $p) {
                                                echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Semana:</label>
                                    <div class="col-xs-9"><select id="Prt_Sem" name="Vap_Sem" class="form-control input-xs"></select></div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Buscar:</label>
                                    <div class="col-xs-9">
                                        <input type="text" name="search" class="form-control input-xs clearable" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="center">
                                    <button type="button" onclick="$('#searchForm').formSubmit();" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Cargar Datos</button>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>
                <div class="col-xs-12">
                    <div>
                        <table id="searchGrid"></table>
                        <div id="searchGridPager"></div>
                    </div>
                </div>
            </div>

            <div id="divPedido" class="row" style="display: none;">
                <div class="col-sm-3"></div>
                <div class="col-sm-6">
                    <form id="formDocumentoPedido" class="form-horizontal normal formDatos" action="javascript:validaPedido();">
                        <input name="Vap_Cod" type="text" value="" class="hidden" />
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de Pedido</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Naviera/Agente:</label>
                                <div class="col-xs-6">
                                    <select name="Nav_Cod" class="form-control input-xs" required="">
                                        <?php if (count($navieras) != 1) { ?><option value="">Selecione Naviera...</option><?php } ?>
                                        <?php foreach ($navieras as $m) {
                                            echo "<option value='$m[Nav_Cod]' data--nav_-tip='$m[Nav_Tip]' >$m[Nav_Nom]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Nave/Vapor</label>
                                <div class="col-xs-9">
                                    <input type="text" name="Vap_Nom" class="form-control input-xs" required="" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Periodo</label>
                                <div class="col-xs-3">
                                    <select name="Vap_Ano" class="form-control input-xs" required="">
                                        <option value="">Periodo..</option>
                                        <?php foreach ($periodos as $p) {
                                            echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Semana</label>
                                <div class="col-xs-3">
                                    <select id="Prt_Sem_Ped" name="Vap_Sem" class="form-control input-xs" required=""></select>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset id="detinoTemp" class="exa-fieldset">
                            <legend class="Titulos2">Datos del Viaje</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Cod Viaje</label>
                                <div class="col-xs-3">
                                    <input type="text" name="Vap_Via" class="form-control input-xs" required="" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Cut Off:</label>
                                <div class="col-xs-3">
                                    <input type="text" name="Vap_Cof" class="form-control input-xs isFecha" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Partida:</label>
                                <div class="col-xs-6">
                                    <select name="Edi_Cod" class="form-control input-xs" required="">
                                        <?php if (count($partidas) != 1) { ?><option value="">Selecione Partida...</option><?php } ?>
                                        <?php foreach ($partidas as $m) {
                                            echo "<option value='$m[Edi_Cod]' data--edi_-cod='$m[Edi_Cod]' >$m[Edi_Nom]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Arribo:</label>
                                <div class="col-xs-6">

                                    <?php //$destinos2 = $obBD_con1->getArrayConsulta(29, '', $obBD_conexion);  var_dump($destinos2);   ?>

                                    <select name="Exd_Cod" class="form-control input-xs" required="" onchange="$('#detinoTemp').setData(this.value===''?{}:$(this).find('option:selected').data(),'name');">
                                        <?php if (count($destinos) != 1) { ?><option value="">Selecione Destino...</option><?php } ?>
                                        <?php foreach ($destinos as $m) {
                                            echo "<option value='$m[Exd_Cod]' data--exd_-cod='$m[Exd_Cod]' data--pas_-nom='$m[Pas_Nom]'>$m[Exd_Nom]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Pais:</label>
                                <div class="col-xs-3">
                                    <span name="Pas_Nom" data-name="Pas_Nom" class="form-control input-xs datatitle"></span>
                                </div>
                            </div>
                        </fieldset>
                        <div class="form-group">
                            <div class="col-xs-9">
                                <button type="button" class="btn btn-sm btn-inverse" onclick="$('#divPedido').moveComp('#divSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atr�s</button>
                                <button type="button" class="btn btn-sm btn-success" onclick="$('#formDocumentoPedido').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div id="divDetalle" class="row" style="display: none;">
                <div class="col-sm-4">
                    <div id="viajeTmp" class="form-horizontal normal">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del Vapor/Viaje</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Naviera:</label>
                                <div class="col-xs-9"><span name="Nav_Nom" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Naviera Tipo:</label>
                                <div class="col-xs-7"><span name="Nav_Tipo" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Vapor/Nave:</label>
                                <div class="col-xs-9"><span name="Vap_Nom" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Cod. Viaje:</label>
                                <div class="col-xs-9"><span name="Vap_Via" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Periodo:</label>
                                <div class="col-xs-6"><span name="Vap_Ano" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Semana:</label>
                                <div class="col-xs-6"><span name="Vap_Sem" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Cut Off:</label>
                                <div class="col-xs-6"><span name="Vap_Cof" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Partida:</label>
                                <div class="col-xs-9"><span name="Edi_Nom" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Arribo:</label>
                                <div class="col-xs-9"><span name="Exd_Nom" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">País:</label>
                                <div class="col-xs-9"><span name="Pas_Nom" class="form-control input-xs"></span></div>
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="col-sm-4">
                    <form id="formDocumentoDetalle" class="form-horizontal normal formDatos" action="javascript:validaDetalle();">
                        <input name="Nco_Cod" type="text" value="" class="hidden" />
                        <input name="Vap_Cod" type="text" value="" class="hidden" />
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del Container</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Nombre/Descrip.:</label>
                                <div class="col-xs-9">
                                    <div class="input-group input-group-xs">
                                        <input type="text" name="Nco_Nom" class="form-control input-xs" required="" pattern="([a-zA-Z]{4}[0-9]{7})" />
                                        <span class="input-group-addon" title='Formato Alfanumerico Ejem.: <span class="green">ABCD1234567</span>'><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                    </div>

                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-sm">Sellos:</label>
                                <div class="col-xs-9">
                                    <input type="text" id="sellos" name="Nco_Sel" class="form-control input-xs datatrigger" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Check:</label>
                                <div class="col-xs-3">
                                    <input type="text" name="Nco_Che" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Cantidad:</label>
                                <div class="col-xs-4">
                                    <input type="text" name="Nco_Can" class="form-control input-xs" onkeypress="return validar_numeric(event);" required="" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Dia:</label>
                                <div class="col-xs-4">
                                    <input type="text" id="Nco_Dia" name="Nco_Dia" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Termografo:</label>
                                <div class="col-xs-9">
                                    <input type="text" name="Nco_Ter" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">BKG:</label>
                                <div class="col-xs-4">
                                    <input type="text" name="Nco_Bkg" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">PNA:</label>
                                <div class="col-xs-4">
                                    <input type="text" name="Nco_Pna" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Retira:</label>
                                <div class="col-xs-9">
                                    <input type="text" name="Nco_Ret" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Agencia:</label>
                                <div class="col-xs-4">
                                    <input type="text" name="Nco_Age" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Lugar/Consol.:</label>
                                <div class="col-xs-9">
                                    <input type="text" name="Nco_Con" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Acopio/Bodega:</label>
                                <div class="col-xs-9">
                                    <input type="text" name="Nco_Bod" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">CI Chofer:</label>
                                <div class="col-xs-4">
                                    <input type="text" name="Nco_Cch" class="form-control input-xs" />
                                </div>
                                <label class="col-xs-2 control-label label-xs">Placa:</label>
                                <div class="col-xs-3">
                                    <input type="text" name="Nco_Pla" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Chofer:</label>
                                <div class="col-xs-9">
                                    <input type="text" name="Nco_Cho" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Obs:</label>
                                <div class="col-xs-9">
                                    <textarea name="Nco_Obs" class="form-control input-xs"></textarea>
                                </div>
                            </div>
                            <div class="form-group center">
                                <button type="button" class="btn btn-sm btn-success" onclick="$('#formDocumentoDetalle').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                            </div>
                        </fieldset>

                    </form>
                </div>
                <div class="col-sm-4">
                    <fieldset id="viajeTmp" class="exa-fieldset">
                        <legend class="Titulos2">Datos de la Planificacion</legend>
                        <div class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Planif:</label>
                                <div class="col-xs-9">
                                    <select id="Pde_Cod" name="Pde_Cod" class="form-control input-xs datatrigger" onchange="setPlanifData(this);"></select>
                                </div>
                            </div>
                            <div id="planificacion">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">RUC:</label>
                                    <div class="col-xs-7"><span name="Ruc" class="form-control input-xs datatitle"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Cliente:</label>
                                    <div class="col-xs-9"><span name="Cliente" class="form-control input-xs datatitle"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">AUCP:</label>
                                    <div class="col-xs-9"><span name="Pln_Auc" class="form-control input-xs datatitle"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">DAE:</label>
                                    <div class="col-xs-9"><span name="Pln_Dae" class="form-control input-xs datatitle"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Observ.:</label>
                                    <div class="col-xs-9"><span name="Pln_Obs" class="form-control input-xs datatitle textarea"></span></div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="help-block"></div>
                <div class="col-sm-12">
                    <div class="form-group">
                        <div class="col-xs-9">
                            <button type="button" class="btn btn-sm btn-inverse" onclick="$('#divDetalle').moveComp('#divSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atr�s</button>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script type="text/javascript">
        $(function() {
            $('#sellos').createTagsInput({
                tagClass: 'label label-warning'
            });
            $('#Nco_Dia').createDatePickers();
        });
    </script>
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTOR-->
    <div id="provDialog" title="B&uacute;squeda de Productor"></div>
</BODY>

</HTML>