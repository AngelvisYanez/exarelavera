<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_tarja.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Tarja();


if(isset($provAjax)){
    $_GET['where']=array('Prd_Est'=>'A');
    $page=$obBD_con1->getPageGrid('productor_bana.selectWhere', $_GET, $obBD_conexion);
    foreach ($page['rows'] as &$d) {
        $d['haciendas']=$obBD_con1->getArrayConsulta('productor_haci.sql.basic', array($d['Prd_Cod']), $obBD_conexion);
    } unset($d);
    $obBD_con1->echoJson($page);
}

if(isset($getContainers)){
    $resp=array('success'=>true);
    $resp['containers']=$obBD_con1->getArrayConsulta('naviera_container.selectWhere', $_GET, $obBD_conexion);
    foreach($resp['containers'] as &$v){
        $Registrado=$obBD_con1->getRowConsulta('productor_tarja.selectWhere',array('unsetCols'=>true, 'addCols'=>array(''=>array('Conteo'=>$obBD_con1->expr('IFNULL(SUM(Prt_Car),0)'))), 'Nco_Cod'=>$v['Nco_Cod'], 'where'=>array('Prt_Est'=>'A'), 'setWhere'=>array('setByNcoCod')), $obBD_conexion);
        $v['Registrado']=$Registrado['Conteo'];
    }
    $obBD_con1->echoJson($resp);
}
if(isset($getProductos)){
    $resp=array('success'=>true);
    $resp['productos']=$obBD_con1->getArrayConsulta('mesclas.selectWhere',$_GET, $obBD_conexion);
    $obBD_con1->echoJson($resp);
}
if(isset($validaNum)){
    $next=$obBD_con1->getRowConsulta('productor_tarja.sql.getNext', null, $obBD_conexion);
    $resp=array('success'=>true, 'valid'=>true, 'next'=>$next['next'], 'Prt_Num'=>$next['next']);
    if(isset($_GET['Prt_Num'])&&!empty($Prt_Num)){
        $resp['Prt_Num']=$Prt_Num;
        $tarjas=$obBD_con1->getRowConsulta('productor_tarja.sql.getByPrtNum', array('Prt_Num'=>$Prt_Num), $obBD_conexion);
        if(isset($tarjas['Prt_Cod'])&&!empty($tarjas['Prt_Cod'])){
            $resp['Prt_Num']=$resp['next'];
            $resp= array_merge($resp,array('valid'=>false, 'message'=>"La <u>Tarja  No. <u>$Prt_Num</u> ya se encuentra Registrada!" ));
        }
    }
    $obBD_con1->echoJson($resp);
}
if(isset($saveDocumento)){
    $resp=array('success'=>false);

    $tarjas=$obBD_con1->getRowConsulta('productor_tarja.sql.getByPrtNum', array('Prt_Num'=>$Prt_Num), $obBD_conexion);
    if(isset($tarjas['Prt_Cod'])&&!empty($tarjas['Prt_Cod'])) $resp['message']="El numero de <u>Tarja</u> ya se encuentra Registrado!";

    if(isset($resp['message'])) $obBD_con1->echoJson($resp);

    $obBD_ins1 =  new Class_Log_Datos_Tarja;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        // guardo al proveedor como productor
        $obBD_ins1->operacionobBD('productor_tarja.insert', $obBD_ins1->formatTarjaInsert($_POST), $obBD_conexionIns);
        $Prt_Cod=$obBD_ins1->insercionid($obBD_conexionIns);
        $resp['Prt_Cod']=$Prt_Cod;

        foreach ($cartones as $c) {
            $obBD_ins1->operacionobBD('productor_tarja_det.insert', $obBD_ins1->formatTarjaDet($c,$Prt_Cod), $obBD_conexionIns);
        }
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    $resp['success']=$obBD_ins1->Error==0;
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}

$hoy = date("Y-m-d");

$marcas=$obBD_con1->getArrayConsulta('banano_marca.selectWhere', array('setWhere'=>array('setEmpCod','isActive')), $obBD_conexion);
$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>'setEmpCod','order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion);
$cur_periodo=current($periodos);
$tipos=$obBD_con1->getTiposCaja();
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/timepicker/jquery-ui-timepicker-addon.min.css" />
    <script type="text/ecmascript" src="../../framework/jquery/timepicker/jquery-ui-timepicker-addon.min.js"></script>
    <style></style>
    <script>
        var cajas=[{Nom:'CAJAS DECLARADAS',Abr:'Cad'},{Nom:'CAJAS RECIBIDAS',Abr:'Car'},{Nom:'CAJAS RECHAZADAS',Abr:'Cah'},{Nom:'CAJAS FALTANTES',Abr:'Caf'},{Nom:'CAJAS CAIDAS',Abr:'Caj'}];
        var hoy='<?php echo $hoy; ?>';
    </script>
    <script type="text/javascript" src="../VALIDACIONES/ban_val_tarja_2.0.js"></script>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Tarja (Comprobante de Recepción)</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">

                <div class="col-sm-5">

                    <fieldset class="exa-fieldset" id="provFormTemp">
                        <legend class="Titulos2">Datos del Productor</legend>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                            <div class="col-xs-6" >
                              <input name="Prd_Cod" data-name="Prd_Cod" type="text" style="display:none;" />
                              <input name="Prv_Cod" data-name="Prv_Cod" type="text" style="display:none;" />
                              <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
                              <div class="input-group input-group-xs">
                                <input name="search" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#provDialog',selectProvee); }" type="text" placeholder="Ingrese Productor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                <span class="input-group-btn">
                                    <button id="Prv_Btn" type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Productor"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                    <!--<button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open'); $('#reset').val(1); " class="btn btn-success btn-xs" title="Registrar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>-->
                                </span>
                              </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Productor:</label>
                            <div class="col-xs-6" >
                                <span name="Productor" data-name="Productor" class="form-control input-xs databind datatitle"></span>
                            </div>
                            <label class="col-xs-1 control-label label-xs">QC:</label>
                            <div class="col-xs-3" ><input type="text" name="Prt_Nqc" class="form-control input-xs " /></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Hacienda:</label>
                            <div class="col-xs-5" >
                                <select id="Prh_Cod" name="Prh_Cod" class="form-control input-xs" required="" onchange="updateMagap();">
                                    <option value="">Selecione Hacienda...</option>
                                </select>
                            </div>
                            <label class="col-xs-2 control-label label-xs">Magap:</label>
                            <div class="col-xs-3" ><span id="Magap" class="form-control input-xs databind datatitle"></span></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Grupo:</label>
                            <div class="col-xs-10" ><input type="text" name="Prt_Grp" class="form-control input-xs " /></div>
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
                            <div class="col-xs-3" > <div class="input-group input-group-xs"><input type="number" min="0" max="100" step="0.1" name="Prt_Por" class="form-control input-xs nospin txtRight"  value="100" /><span class="input-group-addon bold">%</span></div></div>
                            <label class="col-xs-1 control-label label-xs">Eval:</label>
                            <div class="col-xs-5" ><input type="text" name="Prt_Eva" value="" class="form-control input-xs " /></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Observación:</label>
                            <div class="col-xs-9" ><textarea name="Prt_Obs" class="form-control input-xs "></textarea></div>
                        </div>
                    </fieldset>
                </div>
                <div class="col-sm-3">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos de la Tarja</legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Número:</label>
                            <div class="col-xs-7" >
                                <div class="input-group input-group-xs">
                                    <input id="Prt_Num" name="Prt_Num" type="text" class="form-control input-xs" onchange="validatNum()" required="">
                                    <span class="input-group-addon validate"><i class="glyphicon glyphicon-ok green"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Fecha:</label>
                            <div class="col-xs-7" ><input type="text" id="Prt_Fec" name="Prt_Fec" class="form-control input-xs " required="" /></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Periodo:</label>
                            <div class="col-xs-7" >
                                <select id="Prt_Ano" name="Prt_Ano" class="form-control input-xs" required="" onchange="loadContainers();">
                                    <option value="">Periodo..</option>
                                    <?php foreach ($periodos as $p) { echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>"; } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Semana:</label>
                            <div class="col-xs-7" ><select id="Prt_Sem" name="Prt_Sem" class="form-control input-xs" required="" onchange="loadContainers();"></select></div>
                        </div>                        
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Hora Entrada:</label>
                            <div class="col-xs-6" ><input type="text" id="Prt_Hoe" name="Prt_Hoe" class="form-control input-xs " /></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Hora Salida:</label>
                            <div class="col-xs-6" ><input type="text" id="Prt_Hos" name="Prt_Hos" class="form-control input-xs " /></div>
                        </div>

                    </fieldset>
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos de la Marca de Caja</legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Marca:</label>
                            <div class="col-xs-9" >
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
                            <div class="col-xs-9" ><span id="DescrMarca" class="form-control input-xs databind datatitle"></span></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Tipo:</label>
                            <div class="col-xs-9" >
                                <select name="Prt_Tip"  class="form-control input-xs" required="">
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
                            <div class="col-xs-9" >
                                <select id="Exc_Cod" name="Nco_Cod" class="form-control input-xs" required="" onchange="if($(this).val()!=='') $('#container').setData($(this).find('option:selected').data(),'name');">
                                    <option value="" >Seleccione Container..</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Nave/Vapor:</label>
                            <div class="col-xs-9" ><span data-name="Vap_Nom" class="form-control input-xs datatitle" ></span></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Puerto:</label>
                            <div class="col-xs-9" ><span data-name="Edi_Nom" class="form-control input-xs datatitle" ></span></div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12" ><div class="input-group input-group-xs">
                                <span class="input-group-addon bold alert-info">Capac.:</span><input type="text" data-name="Nco_Can" class="form-control input-xs" />
                                <span class="input-group-addon bold alert-warning">Carga:</span><input type="text" data-name="Registrado" class="form-control input-xs" />
                            </div></div>
                            <!--<label class="col-xs-3 control-label label-xs">Zona:</label>
                            <div class="col-xs-9" ><span data-name="Nco_Con" class="form-control input-xs datatitle" ></span></div>-->
                        </div>


                    </fieldset>
                    <div class="center">
                            <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                </div>

                </form>
                <div class="col-sm-4">
                    <div class="">
                        <table id="prods"></table>
                    </div><div class="help-block"></div>
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
        $('#Prt_Fec').createDatePickers();
        $('#Prt_Hoe').timepicker();
        $('#Prt_Hos').timepicker();
        clearForm();
    });


    </script>
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTOR-->
    <div id="provDialog" title="B&uacute;squeda de Productor"></div>
    <script>
        function selectProvee(provee){
            $('#provFormTemp').setData($.extend(provee,{op_opciones:'c'}),'name').find('.dialogSearch').addClass('x');
            setHaciendas(provee.haciendas);
            if($.isArray(provee.haciendas)&&provee.haciendas.length===1){                
                $('#Prh_Cod').val(provee.haciendas[0].Prh_Cod);
                updateMagap();
            }else
                $('#Magap').html('');
            $('#provDialog').dialog('close');
        }
    </script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>
</HTML>



