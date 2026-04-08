<?php

/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_tarja.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Tarja;

$hoy = date("Y-m-d");

if (isset($provAjax)) {
    $page = $obBD_con1->getPageGridJson('productor_bana.selectWhere', $_GET, $obBD_conexion);
}
if (isset($getProductos)) {
    $resp = array('success' => true);
    $resp['productos'] = $obBD_con1->getArrayConsulta('mesclas.selectWhere', $_GET, $obBD_conexion, true);
    $obBD_con1->echoJson($resp);
}
if (isset($searchLiquid)) {
    $obBD_con1->getPageGridJson('productor_tarja.selectWhere', array_merge(array('setWhere' => array('setProductor')), $_GET), $obBD_conexion, true);
}
if (isset($getDetalle)) {
    $resp = array();
    $resp['dato'] = $obBD_con1->getRowConsulta('productor_tarja.selectWhere', array('where' => array('Prt_Cod' => $Prt_Cod), 'setWhere' => array('setProductor')), $obBD_conexion, true);

    $resp['success'] = isset($resp['dato']['Prt_Cod']);
    if ($resp['success']) {
        $resp['dato']['Prt_Cam'] = json_decode($resp['dato']['Prt_Cam']);
        $resp['dato']['Prt_Hoe'] = strlen($resp['dato']['Prt_Hoe']) > 4 ?  substr($resp['dato']['Prt_Hoe'], 0, 5) : $resp['dato']['Prt_Hoe'];
        $resp['dato']['Prt_Hos'] = strlen($resp['dato']['Prt_Hos']) > 4 ?  substr($resp['dato']['Prt_Hos'], 0, 5) : $resp['dato']['Prt_Hos'];
        $resp['haciendas'] = $obBD_con1->getArrayConsulta('productor_haci.sql.basic', array($resp['dato']['Prd_Cod']), $obBD_conexion);
        $resp['tarja_det'] = $obBD_con1->getArrayConsulta('productor_tarja_det.sql.basic', array($resp['dato']['Prt_Cod']), $obBD_conexion);

        // Este dato es el correcto
        $resp['containers'] = $obBD_con1->getArrayConsulta('naviera_container.selectWhere', array('setWhere' => array('setEmpCod', 'isActive', 'setVapor'), 'where' => array('Vap_Sem' => $resp['dato']['Prt_Sem'], 'Vap_Ano' => $resp['dato']['Prt_Ano'])), $obBD_conexion);
        
        // //ChromePhp::log($resp['dato']['Prt_Sem']);
        // $resp['containers']=$obBD_con1->getArrayConsulta('exportacion_container.selectWhere', array('where'=>array('Vap_Sem'=>$resp['dato']['Prt_Sem'],'Vap_Ano'=>$resp['dato']['Prt_Ano'])) , $obBD_conexion);
        // $resp['containers']=$obBD_con1->getArrayConsulta('exportacion_container.selectWhere', $_GET, $obBD_conexion); 
        // //ChromePhp::log($resp['containers']);
        foreach ($resp['containers'] as &$v) {
            $Registrado = $obBD_con1->getRowConsulta('productor_tarja.selectWhere', array('unsetCols' => true, 'addCols' => array('' => array('Conteo' => $obBD_con1->expr('SUM(Prt_Car)'))), 'Nco_Cod' => $v['Nco_Cod'], 'where' => array('Prt_Cod' => '!=' . $resp['dato']['Prt_Cod']), 'setWhere' => array('setByNcoCod')), $obBD_conexion, true);
            $v['Registrado'] = $Registrado['Conteo'];
        }
        $resp['productos'] = $obBD_con1->getArrayConsulta('mesclas.selectWhere', array('Mes_Tip' => 'C', 'mesclas.Bam_Cod' => $resp['dato']['Bam_Cod']), $obBD_conexion, true);
    }
    $obBD_con1->echoJson($resp);
}
if (isset($validaNum)) {
    $next = $obBD_con1->getRowConsulta('productor_tarja.sql.getNext', null, $obBD_conexion);
    $resp = array('success' => true, 'valid' => true, 'next' => $next['next'], 'Prt_Num' => $next['next']);
    if (isset($_GET['Prt_Num']) && !empty($Prt_Num)) {
        $resp['Prt_Num'] = $Prt_Num;
        $tarjas = $obBD_con1->getRowConsulta('productor_tarja.sql.getByPrtNum', array('Prt_Num' => $Prt_Num, 'Prt_Cod' => $Prt_Cod), $obBD_conexion);
        if (isset($tarjas['Prt_Cod']) && !empty($tarjas['Prt_Cod'])) {
            $resp['Prt_Num'] = $resp['next'];
            $resp = array_merge($resp, array('valid' => false, 'message' => "La <u>Tarja  No. <u>$Prt_Num</u> ya se encuentra Registrada!"));
        }
    }
    $obBD_con1->echoJson($resp);
}
if (isset($saveDocumento)) {
    $resp = array('success' => false);

    $tarjas = $obBD_con1->getRowConsulta('productor_tarja.sql.getByPrtNum', array('Prt_Num' => $Prt_Num, 'Prt_Cod' => $Prt_Cod), $obBD_conexion);
    if (isset($tarjas['Prt_Cod']) && !empty($tarjas['Prt_Cod'])) $resp['message'] = "El numero de <u>Tarja</u> ya se encuentra Registrado!";

    if (isset($resp['message'])) $obBD_con1->echoJson($resp);

    $obBD_ins1 =  new Class_Log_Datos_Tarja;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {
        // guardo al proveedor como productor
        $obBD_ins1->operacionobBD('productor_tarja.update', $obBD_con1->formatTarjaUpdate($_POST), $obBD_conexionIns, true);
        $resp['Prt_Cod'] = $Prt_Cod;

        $obBD_ins1->operacionobBD('productor_tarja_det.deleteWhere', array('Prt_Cod' => $Prt_Cod), $obBD_conexionIns);
        foreach ($cartones as $c) {
            $obBD_ins1->operacionobBD('productor_tarja_det.insert', $obBD_ins1->formatTarjaDet($c, $Prt_Cod), $obBD_conexionIns);
        }
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    // finalizo la transaccion y compruebo errores
    $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    $resp['success'] = $obBD_ins1->Error == 0;
    if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}
$marcas = $obBD_con1->getArrayConsulta('banano_marca.selectWhere',  array('setWhere' => array('setEmpCod', 'isActive')), $obBD_conexion);
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => 'setEmpCod', 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
$cur_periodo = current($periodos);
$linkLiqui = baseUrl("../../bananero/FRONT/ban_pri_liquidacion.php?Lib_Cod=");
$tipos = $obBD_con1->getTiposCaja();
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript">
        var buttonExtra = {
            label: $.createIcon('pencil'),
            name: 'imp1',
            width: 25,
            formatter: 'gridButton',
            formatoptions: {
                action: 'editarTarja',
                data: 'Prt_Cod',
                title: 'Editar Tarja',
                conditional: function(o) {
                    return o.Prt_Est !== 'I' && o.Lib_Cod === null;
                } /*, caseFalse: $.createIcon('remove red')*/
            },
            classes: 'bgNoColor'
        };
        var cajas = [{
            Nom: 'CAJAS DECLARADAS',
            Abr: 'Cad'
        }, {
            Nom: 'CAJAS RECIBIDAS',
            Abr: 'Car'
        }, {
            Nom: 'CAJAS RECHAZADAS',
            Abr: 'Cah'
        }, {
            Nom: 'CAJAS FALTANTES',
            Abr: 'Caf'
        }, {
            Nom: 'CAJAS CAIDAS',
            Abr: 'Caj'
        }];
        var hoy = '<?php echo $hoy; ?>';
    </script>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/timepicker/jquery-ui-timepicker-addon.min.css" />
    <script type="text/ecmascript" src="../../framework/jquery/timepicker/jquery-ui-timepicker-addon.min.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/ban_val_tarja_2.0.js"></script>
    <style></style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Modificar Tarja</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row" id="searchTarjas">
                <div class="col-xs-12">
                    <form id="formDocumentoSearch" class="form-horizontal normal formDatos" action="javascript:liquidaciones.Search('#formDocumentoSearch','searchLiquid');">
                        <input name="order" type="hidden" value="" />
                        <fieldset class="exa-fieldset" id="provFormTemp">
                            <legend class="Titulos2">Consulta de Información</legend>
                            <div class="col-sm-4">

                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Periodo:</label>
                                    <div class="col-xs-7">
                                        <select id="Lib_Ano" name="Prt_Ano" class="form-control input-xs">
                                            <option value="">Periodo..</option>
                                            <?php foreach ($periodos as $p) {
                                                echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>";
                                            } ?>
                                        </select>
                                    </div>

                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Semana:</label>
                                    <div class="col-xs-9"><select id="Prt_Sem" name="Prt_Sem" class="form-control input-xs"></select></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Marca:</label>
                                    <div class="col-xs-9">
                                        <select name="Bam_Cod" class="form-control input-xs getData ins" s>
                                            <?php if (count($marcas) != 1) { ?><option value="">Selecione Marca...</option><?php } ?>
                                            <?php foreach ($marcas as $m) {
                                                echo "<option value='$m[Bam_Cod]' data--bam_-cod='$m[Bam_Cod]' data--bam_-tam='$m[Bam_Tam]'>$m[Bam_Nom] $m[Bam_Tam]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Cédula/RUC:</label>
                                    <div class="col-xs-9">
                                        <input name="Prd_Cod" data-name="Prd_Cod" type="text" style="display:none;" />
                                        <input name="Prv_Cod" data-name="Prv_Cod" type="text" style="display:none;" />
                                        <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
                                        <div class="input-group input-group-xs">
                                            <input name="search" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#provDialog',selectProvee); }" type="text" placeholder="Ingrese Productor..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                            <span class="input-group-btn">
                                                <button id="Prv_Btn" type="button" onclick="selectProvee({})" class="btn btn-success btn-xs" title="Buscar Productor"><span class="glyphicon glyphicon-eject"></span></button>
                                                <button id="Prv_Btn" type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Productor"><span class="glyphicon glyphicon-search"></span></button>
                                                <!--<button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open'); $('#reset').val(1); " class="btn btn-success btn-xs" title="Registrar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>-->
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Productor:</label>
                                    <div class="col-xs-9">
                                        <span name="Productor" data-name="Productor" class="form-control input-xs databind datatitle"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">No.&nbsp;Tarja:</label>
                                    <div class="col-xs-9">
                                        <input name="Prt_Num" class="form-control input-xs " />
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="center">
                                    <button type="button" onclick="$('#formDocumentoSearch').formSubmit();" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Cargar Datos</button>
                                </div>
                            </div>
                        </fieldset>

                    </form>
                </div>
                <div class="col-xs-12">
                    <div style="min-height: 280px">
                        <table id="liquidaciones"></table>
                        <div id="liquidacionesPager"></div>
                    </div>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop green"></span> Ya Fue Usado en Liquidaciones | <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos </div>
                </div>
            </div>

            <div class="row" id="editTarja" style="visibility: hidden;">
                <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">

                    <div class="col-sm-5">

                        <fieldset class="exa-fieldset" id="provFormTemp">
                            <legend class="Titulos2">Datos del Productor</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                                <div class="col-xs-6">
                                    <input id="Prt_Cod" name="Prt_Cod" data-name="Prt_Cod" type="text" style="display:none;" />
                                    <input name="Prd_Cod" data-name="Prd_Cod" type="text" style="display:none;" />
                                    <input name="Prv_Cod" data-name="Prv_Cod" type="text" style="display:none;" />
                                    <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">

                                    <span name="Prs_Ced" type="text" placeholder="Ingrese Productor..." class="form-control input-xs databind datatitle"></span>


                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Productor:</label>
                                <div class="col-xs-6">
                                    <span name="Productor" data-name="Productor" class="form-control input-xs databind datatitle"></span>
                                </div>
                                <label class="col-xs-1 control-label label-xs">QC:</label>
                                <div class="col-xs-3"><input type="text" name="Prt_Nqc" class="form-control input-xs " /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Hacienda:</label>
                                <div class="col-xs-5">
                                    <select id="Prh_Cod" name="Prh_Cod" class="form-control input-xs datatrigger" required="" onchange="updateMagap();">
                                        <option value="">Selecione Hacienda...</option>
                                    </select>
                                </div>
                                <label class="col-xs-2 control-label label-xs">Magap:</label>
                                <div class="col-xs-3"><span id="Magap" class="form-control input-xs databind datatitle"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Grupo:</label>
                                <div class="col-xs-10"><input type="text" name="Prt_Grp" class="form-control input-xs " /></div>
                            </div>
                        </fieldset>
                        <div class="jqHeaderFirst jqFirst" style="padding: 5px 0px;">
                            <table id="cajasBan"></table>
                            <div id="cajasPager"></div>
                        </div>

                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Resultado de la Tarja</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Porc. Calidad:</label>
                                <div class="col-xs-3">
                                    <div class="input-group input-group-xs"><input type="number" min="0" max="100" step="0.1" name="Prt_Por" class="form-control input-xs nospin txtRight" value="100" /><span class="input-group-addon bold">%</span></div>
                                </div>
                                <label class="col-xs-1 control-label label-xs">Eval:</label>
                                <div class="col-xs-5"><input type="text" name="Prt_Eva" value="" class="form-control input-xs " /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Observación:</label>
                                <div class="col-xs-9"><textarea name="Prt_Obs" class="form-control input-xs "></textarea></div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-sm-3">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de la Tarja</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Número:</label>
                                <div class="col-xs-7">
                                    <div class="input-group input-group-xs">
                                        <input id="Prt_Num" name="Prt_Num" type="text" class="form-control input-xs" onchange="validatNum()" required="">
                                        <span class="input-group-addon validate"><i class="glyphicon glyphicon-ok green"></i></span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Fecha:</label>
                                <div class="col-xs-7"><input type="text" id="Prt_Fec" name="Prt_Fec" class="form-control input-xs " required="" /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Periodo:</label>
                                <div class="col-xs-7">
                                    <select id="Prt_Ano" name="Prt_Ano" class="form-control input-xs" required="" onchange="loadContainers();">
                                        <option value="">Periodo..</option>
                                        <?php foreach ($periodos as $p) {
                                            echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Semana:</label>
                                <div class="col-xs-7"><select id="Prt_Sem2" name="Prt_Sem" class="form-control input-xs" required="" onchange="loadContainers();"></select></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-6 control-label label-xs">Hora Entrada:</label>
                                <div class="col-xs-6"><input type="text" id="Prt_Hoe" name="Prt_Hoe" class="form-control input-xs " /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-6 control-label label-xs">Hora Salida:</label>
                                <div class="col-xs-6"><input type="text" id="Prt_Hos" name="Prt_Hos" class="form-control input-xs " /></div>
                            </div>

                        </fieldset>
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de la Marca de Caja</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Marca:</label>
                                <div class="col-xs-9">
                                    <select id="Bam_Cod" name="Bam_Cod" class="form-control input-xs" required="" onchange="updateMarca();getProductosMarca(this.value);">
                                        <option value="">Selecione Marca...</option>
                                        <?php foreach ($marcas as $m) {
                                            echo "<option value='$m[Bam_Cod]' data--bam_-cod='$m[Bam_Cod]' data--bam_-des='$m[Bam_Des]' data--bam_-tam='$m[Bam_Tam]'>$m[Bam_Nom] $m[Bam_Tam]</option>";
                                        } ?>
                                    </select>
                                </div>

                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Descrp:</label>
                                <div class="col-xs-9"><span id="DescrMarca" class="form-control input-xs databind datatitle"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Tipo:</label>
                                <div class="col-xs-9">
                                    <select name="Prt_Tip" class="form-control input-xs" required="">
                                        <option value="">Selecione Tipo...</option>
                                        <?php foreach ($tipos as $m) {
                                            echo "<option value='$m[value]'>$m[label]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="exa-fieldset" id="container">
                            <legend class="Titulos2">Datos del Embarque</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Container:</label>
                                <div class="col-xs-9">
                                    <select id="Exc_Cod" name="Nco_Cod" class="form-control input-xs datatrigger" required="" onchange="if($(this).val()!=='') $('#container').setData($(this).find('option:selected').data(),'name');">
                                        <option value="">Seleccione Container..</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Nave/Vapor:</label>
                                <div class="col-xs-9"><span data-name="Vap_Nom" class="form-control input-xs datatitle"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Puerto:</label>
                                <div class="col-xs-9"><span data-name="Edi_Nom" class="form-control input-xs datatitle"></span></div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12">
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon bold alert-info">Capac.:</span><input type="text" data-name="Nco_Can" class="form-control input-xs" />
                                        <span class="input-group-addon bold alert-warning">Carga:</span><input type="text" data-name="Registrado" class="form-control input-xs" />
                                    </div>
                                </div>
                                <!--<label class="col-xs-3 control-label label-xs">Zona:</label>
                            <div class="col-xs-9" ><span data-name="Nco_Con" class="form-control input-xs datatitle" ></span></div>-->
                            </div>


                        </fieldset>
                        <div class="center">
                            <button type="button" class="btn btn-sm btn-inverse" onclick="$('#editTarja').moveComp('#searchTarjas').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
                            <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                    </div>

                </form>
                <div class="col-sm-4">
                    <div class="">
                        <table id="prods"></table>
                    </div>
                    <div class="help-block"></div>
                    <div class="jqHeaderSecond jqSecond">
                        <table id="camiones"></table>
                        <div id="camionesPager"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(function() {
            $('#editTarja').hide().css('visibility', '');
            $('#Prt_Fec').createDatePickers();
            $('#Prt_Hoe').timepicker();
            $('#Prt_Hos').timepicker();
            setSemanas2();
        });
    </script>
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTOR-->
    <div id="provDialog" title="B&uacute;squeda de Productor"></div>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>

</HTML>