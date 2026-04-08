<?php

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_productor.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Productor;

if(isset($searchAjax)){
    $obBD_con1->getPageGridJson('productor_bana.selectWhere', array_merge(array(),$_GET), $obBD_conexion);
}
/* cuscar cuentas contables */
if(isset($cuenAjax)){
    $responce=$obBD_con1->getPageGridJson('det_plan.selectWhere', array_merge($_GET,array('setWhere'=>array('byPecCod','isActive','isDetalle'))), $obBD_conexion);
}
if(isset($changeEstado)){
    $resp=array('success'=>false);
    $obBD_ins1 =  new Class_Log_Datos_Productor;
    //$obBD_ins1->debug(true);
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        $obBD_ins1->operacionobBD('productor_bana.update', array('where'=>array('Prd_Cod'=>$Prd_Cod),'Prd_Est'=>$Prd_Est), $obBD_conexionIns);
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_ins1->echoJson($resp); }
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);// finalizo la transaccion y compruebo errores
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_ins1->echoJson($resp);
}
if(isset($getDatoDetalle)){
    $resp=array();
    $resp['dato']=$obBD_con1->getRowConsulta('productor_bana.selectWhere', array('where'=>array('productor_bana.Prd_Cod'=>$Prd_Cod),'setWhere'=>array('setAcopio')), $obBD_conexion);

    $resp['success']=isset($resp['dato']['Prd_Cod']);
    if($resp['success']){
        $resp['haciendas']=$obBD_con1->getArrayConsulta('productor_haci.sql.basic', $Prd_Cod, $obBD_conexion);
        $resp['CxC']=$obBD_con1->getRowConsulta('productor_det_plan.selectWhere', array('Prd_Cod'=>$Prd_Cod,'Prp_Tip'=>'CC'), $obBD_conexion);
        $resp['Inv']=$obBD_con1->getRowConsulta('productor_det_plan.selectWhere', array('Prd_Cod'=>$Prd_Cod,'Prp_Tip'=>'IN'), $obBD_conexion);
        $resp['Liq']=$obBD_con1->getRowConsulta('productor_det_plan.selectWhere', array('Prd_Cod'=>$Prd_Cod,'Prp_Tip'=>'Li'), $obBD_conexion);
    }
    $obBD_con1->echoJson($resp);
}
if(isset($saveDocumento)){
    $resp=array('success'=>false);

    if(isset($resp['message'])) $obBD_con1->echoJson($resp);

    $obBD_ins1 =  new Class_Log_Datos_Productor;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        $obBD_ins1->operacionobBD('productor_bana.update', array('where'=>array('Prd_Cod'=>$Prd_Cod),'Prd_Cup'=>$Prd_Cup,'Prd_Cau'=>$Prd_Cau), $obBD_conexionIns);
        $obBD_ins1->operacionobBD('acopio.update', array('where'=>array('Prd_Cod'=>$Prd_Cod),'Aco_Des'=>$Aco_Des), $obBD_conexionIns);
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns); // finalizo la transaccion y compruebo errores
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}
if(isset($saveCuentas)){
    $resp=array('success'=>false);

    if(isset($resp['message'])) $obBD_con1->echoJson($resp);

    $obBD_ins1 =  new Class_Log_Datos_Productor;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        // guardo las cuentas contables para configuracion
        if(isset($Pld_Cod_Cxc)&&!empty($Pld_Cod_Cxc)){
            $obBD_ins1->operacionobBD('productor_det_plan.deleteWhere', array('where'=>array('Prd_Cod'=>$Prd_Cod, 'Prp_Tip'=>'CC')), $obBD_conexionIns);
            $obBD_ins1->operacionobBD('productor_det_plan.insert', array('Pld_Cod'=>$Pld_Cod_Cxc, 'Prd_Cod'=>$Prd_Cod, 'Prp_Tip'=>'CC'), $obBD_conexionIns);
        }
        if(isset($Pld_Cod_Inv)&&!empty($Pld_Cod_Inv)){
            $obBD_ins1->operacionobBD('productor_det_plan.deleteWhere', array('where'=>array('Prd_Cod'=>$Prd_Cod, 'Prp_Tip'=>'IN')), $obBD_conexionIns);
            $obBD_ins1->operacionobBD('productor_det_plan.insert', array('Pld_Cod'=>$Pld_Cod_Inv, 'Prd_Cod'=>$Prd_Cod, 'Prp_Tip'=>'IN'), $obBD_conexionIns);
        }
        if(isset($Pld_Cod_Liq)&&!empty($Pld_Cod_Liq)){
            $obBD_ins1->operacionobBD('productor_det_plan.deleteWhere', array('where'=>array('Prd_Cod'=>$Prd_Cod, 'Prp_Tip'=>'LI')), $obBD_conexionIns);
            $obBD_ins1->operacionobBD('productor_det_plan.insert', array('Pld_Cod'=>$Pld_Cod_Liq, 'Prd_Cod'=>$Prd_Cod, 'Prp_Tip'=>'LI'), $obBD_conexionIns);
        }
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns); // finalizo la transaccion y compruebo errores
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}
if(isset($saveHacienda)){
    $resp=array('success'=>false);

    if(isset($resp['message'])) $obBD_con1->echoJson($resp);

    $obBD_ins1 =  new Class_Log_Datos_Productor;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        $obBD_ins1->operacionobBD('productor_haci.'.($saveHacienda=='S'?'insert':'update'), $hacienda, $obBD_conexionIns);
        if($saveHacienda=='S') $resp['Prh_Cod']=$obBD_ins1->insercionid($obBD_conexionIns);
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns); // finalizo la transaccion y compruebo errores
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}
if(isset($deleteHacienda)){
    $resp=array('success'=>false);
    $obBD_ins1 =  new Class_Log_Datos_Productor;
    //$obBD_ins1->debug(true);
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        $obBD_ins1->operacionobBD('productor_haci.update', array('where'=>array('Prh_Cod'=>$Prh_Cod),'Prh_Est'=>'I'), $obBD_conexionIns);
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_ins1->echoJson($resp); }
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);// finalizo la transaccion y compruebo errores
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_ins1->echoJson($resp);
}
$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>'setEmpCod','order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion);
$cur_periodo=current($periodos);
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script language="javascript" src="../VALIDACIONES/ban_val_productor.js"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Productores</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-xs-12">
                        <form id="searchForm" name="searchForm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchForm','searchAjax');">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&uacute;squeda de Productores</legend>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                    <div class="col-sm-5 radioset">
                                        <input id="rad_ba2" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)"/><label for="rad_ba2">&nbsp;&nbsp;Productor&nbsp;&nbsp;</label>
                                        <input id="rad_ba1" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)"/><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-sm">B&uacute;squeda:</label>
                                    <div class="col-sm-5">
                                        <div class="input-group">
                                            <input type="text" id="search" name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" class="form-control input-sm clearable" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                            <span class="input-group-btn">
                                                <button class="btn btn-success btn-sm" type="button" title="Buscar Cliente" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                    <div class="col-xs-12">
                        <div >
                            <table id="searchGrid"></table>
                            <div id="searchGridPager"></div>
                        </div>
                    </div>
                </div>

                <div id="editarDato" class="row" style="visibility: hidden;">
                    <div class="col-sm-6">
                        <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">
                        <input id="Prd_Cod" name="Prd_Cod" type="text" style="display:none;" />
                        <fieldset class="exa-fieldset" id="provFormTemp">
                            <legend class="Titulos2">Datos del Proveedor</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                                <div class="col-xs-6" >
                                  <input name="Prs_Cod" data-name="Prs_Cod" type="text" style="display:none;" />
                                  <input name="Prv_Cod" data-name="Prv_Cod" type="text" style="display:none;" />
                                     <span name="Prs_Ced" data-name="Prs_Ced" class="form-control input-xs databind datatitle"></span>
                                </div>

                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Proveedor:</label>
                                <div class="col-xs-6" >
                                    <span name="Productor" data-name="Productor" class="form-control input-xs databind datatitle"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Dirección:</label>
                                <div class="col-xs-10" >

                                        <input name="Prs_Dir" data-name="Prs_Dir" type="text" class="form-control  input-xs datatitle" readonly="" tabindex="-1">

                                </div>
                            </div>

                        </fieldset>
                        <fieldset class="exa-fieldset" id="provFormTemp">
                            <legend class="Titulos2">Datos del Productor</legend>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs">Código Aux.:</label>
                                <div class="col-xs-6" >
                                    <input type="text" name="Prd_Cau" class="form-control input-xs" value=""  />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Cupo Referencial:</label>
                                <div class="col-xs-6" >
                                    <input type="number" name="Prd_Cup" class="form-control input-xs nospin" value="" step="1" min="1" required="" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Nombre Acopio:</label>
                                <div class="col-xs-6" >
                                    <input type="text" id="Aco_Des" name="Aco_Des" class="form-control input-xs nospin" value="" required="" value="" />
                                </div>
                            </div>
                            <div class="center">
                                <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                            </div>
                        </fieldset>
                        </form>
                        <form id="formDocumentoCtas" class="form-horizontal normal formDatos" action="javascript:validaCuentas();">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Cuentas Contables</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Periodo Contable</label>
                                <div class="col-xs-3" >
                                    <select id="Pec_Cod" onchange="clearCuentas(); $('#cuenForm').setData($('#Pec_Cod').find('option:selected').data(),'name');" class="form-control input-xs">
                                        <?php foreach ($periodos as $p) {
                                            echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="ctaCC1FormTemp">
                                <label class="col-xs-3 control-label label-xs">Cta. x Cobrar:</label>
                                <div class="col-xs-9" >
                                  <input name="Pld_Cod_Cxc" data-name="Pld_Cod" type="text" style="display:none;" />
                                  <div class="input-group input-group-xs">
                                    <span data-name="Pld_Cdc" class="input-group-addon bold"> </span>
                                    <span data-name="Pld_Des" placeholder="Ingrese Proveedor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" ></span>
                                    <span class="input-group-btn">
                                        <button id="Prv_Btn" type="button" onclick="$('#Index').val('ctaCC1FormTemp'); $('#cuenDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                    </span>
                                  </div>
                                </div>
                            </div>
                            <div class="form-group" id="ctaCC2FormTemp">
                                <label class="col-xs-3 control-label label-xs">Cta. Inventario:</label>
                                <div class="col-xs-9" >
                                  <input name="Pld_Cod_Inv" data-name="Pld_Cod" type="text" style="display:none;" />
                                  <div class="input-group input-group-xs">
                                    <span data-name="Pld_Cdc" class="input-group-addon bold"> </span>
                                    <span data-name="Pld_Des" class="form-control input-xs clearable dialogSearch" tabindex="1" ></span>
                                    <span class="input-group-btn">
                                        <button id="Prv_Btn" type="button" onclick="$('#Index').val('ctaCC2FormTemp'); $('#cuenDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                    </span>
                                  </div>
                                </div>
                            </div>
                            <div class="form-group" id="ctaCC3FormTemp">
                                <label class="col-xs-3 control-label label-xs">Cta. Liquidacion:</label>
                                <div class="col-xs-9" >
                                  <input name="Pld_Cod_Liq" data-name="Pld_Cod" type="text" style="display:none;" />
                                  <div class="input-group input-group-xs">
                                    <span data-name="Pld_Cdc" class="input-group-addon bold"> </span>
                                    <span data-name="Pld_Des" class="form-control input-xs clearable dialogSearch" tabindex="1" ></span>
                                    <span class="input-group-btn">
                                        <button id="Prv_Btn" type="button" onclick="$('#Index').val('ctaCC3FormTemp'); $('#cuenDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                    </span>
                                  </div>
                                </div>
                            </div>
                            <div class="center">
                                <button type="submit" onclick="" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                            </div>
                        </fieldset>
                            <div>
                                 <button type="button" class="btn btn-sm btn-inverse" onclick="$('#editarDato').moveComp('#lista').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>

                            </div>
                            <div class="help-block"></div>
                        </form>
                    </div>

                    <div class="col-sm-6">
    <!--                    <div class="jqHeaderFirst jqFirst">
                            <table id="marcaBan"></table>
                            <div id="marcaBanPager"></div>
                        </div>
                        <div class="help-block"></div>-->
                        <div class="jqHeaderFirst jqFirst">
                            <table id="haciendas"></table>
                            <div id="haciendasPager"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
        <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
        <script type="text/javascript">
            $(function(){
                $('#Pec_Cod').trigger('change');
                $('#editarDato').css({display:'none',visibility:''});
            });
            function saveHacienda(){
                var data= haciendasCreateForm.getData();
                var isNew=$.isEmpty(data['Prh_Cod']);
                if(!isNew){
                    data=$.extend(haciendas.getCell(data.Index,'OriginalData'),data);
                }else{
                    data['Index']=haciendas.nextIndex('Index');
                    data['Prh_Est']='A';
                }
                data['Prd_Cod']=$('#Prd_Cod').val();
                var saver=$.cloneData(data);
                delete saver['Index'];
                $.createDialogConfirm('¿Esta seguro de guardar los datos?', data, function(){
                    $.saveDataJson('',{saveHacienda:isNew?'S':'N',hacienda:saver},
                        function (resp){
                            //console.log(isNew);
                            if(!isNew) haciendas.changeRow(data.Index,data); else{ data['Prh_Cod']=resp.Prh_Cod; haciendas.setRow(data); };
                            haciendasCreateDlg.dialog('close');
                        }
                    );
                });
            }
            function updateHacienda(data){
                var dat=haciendas.getCell(data.Index,'OriginalData');
                $('#MagapCod').val($.isEmpty(dat['Prh_Mag'])?'N':'S').trigger('change');
                console.log(dat);
                haciendasCreateForm.setData(dat);
                haciendasCreateDlg.dialog('open');
            }
            function deleteHacienda(data){
                data['deleteHacienda']=true;
                $.createDialogConfirm('¿Esta seguro que desea <b class="red">DESACTIVAR</b> la <b>Hacienda</b>?', data, function(){
                    $.saveDataJson('',data,
                        function (resp){
                            haciendas.changeRow(data.Index,{Prh_Est:'I',update:'','delete':''});
                        }
                    );
                });
            }
            function validaDocument(){
                var data=$('#formDocumento').getData('saveDocumento');
                $.createDialogConfirm('¿Esta seguro de actualizar el Productor?', data, saveDocument);
            }
            function validaCuentas(){
                var data=$('#formDocumentoCtas').getData('saveCuentas');
                data['Prd_Cod']=$('#Prd_Cod').val();
                console.log(data);
                $.createDialogConfirm('¿Esta seguro de actualizar las cuentas del productor?<br/><br/><b class="red">NOTA:</b> <span class="blue">Esto no afectara a los asientos anteriores, solo a los siguientes.</span>', data, function(data){
                   $.saveDataJson('',data,
                        function (resp){

                        }
                    );
                });
            }
            function saveDocument(data){ //console.log(data); console.log(data['rets']);
                $.saveDataJson('',data,
                    function (resp){
                        searchGrid.gridUpdate();
                        $('#editarDato').moveComp('#lista').updateGridsSizes();
                    }
                );
            }
            function clearCuentas(){
                $('#ctaCC1FormTemp').setData({},'name');
                $('#ctaCC2FormTemp').setData({},'name');
                $('#ctaCC3FormTemp').setData({},'name');
            }
            function SelectCta(cta){
                $('#'+($('#Index').val())).setData($.getDialogGrid("#cuenDialog").jqGrid('getRowData', cta['Pld_Cod']), 'name' );
                $('#cuenDialog').dialog('close');
            }
            function setCuenta(form,data){
                $('#'+form).setData(data,'name');
            }
        </script>
        <?php include('../COMPONENTES/gestionHaciendaModal.php'); ?>
    </BODY>
</HTML>



