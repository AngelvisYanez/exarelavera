<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_exportacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Exportacion;

$hoy = date("Y-m-d");

/* busqueda de documentos */
if(isset($ventaAjax)){    
    $obBD_con1->getPageGridJson('ventas.selectWhere', array_merge($_GET, array('where'=>"(Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='4' OR Tic_Sri='2' OR Tic_Sri='41' OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' OR Tic_Sri='51' OR Tic_Sri='52') AND exporta_vent.Eve_Cod IS NULL AND (Ide_Sri='R' OR Ide_Sri='P') AND Ide_Pre IS NOT NULL AND sucursal.Suc_Cod=$Ses_Suc_Cod",'setWhere'=>array('isActive','setExportacion','setTotales'))), $obBD_conexion) ;
}
if(isset($saveDocumento)){
    $resp=array('success'=>false);
    if(isset($resp['message'])) $obBD_con1->echoJson($resp);
    
    $obBD_ins1 =  new Class_Log_Datos_Exportacion;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);  
    try{
        // guardo la exportacion
        $obBD_ins1->operacionobBD('exporta_vent.insert', $obBD_ins1->formatDataExp($_POST), $obBD_conexionIns); 
        
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}
$paises=$obBD_con1->getArrayConsulta('pais.selectWhere', array('Pas_Est'=>'A','order'=>'Pas_Nom'), $obBD_conexion);
$paraisos=$obBD_con1->getArrayConsulta('paraisos_fisc.selectWhere', array('Paf_Est'=>'A','order'=>'Paf_Nom'), $obBD_conexion);
$distritos=$obBD_con1->getArrayConsulta('exporta_dist.selectWhere', array('Edi_Est'=>'A','order'=>'Edi_Nom'), $obBD_conexion);
$regimen=$obBD_con1->getArrayConsulta('exporta_regi.selectWhere', array('Ere_Est'=>'A','order'=>'Ere_Nom'), $obBD_conexion);
$ingresos=$obBD_con1->getArrayConsulta('exporta_ingr.selectWhere', array('Ein_Est'=>'A','order'=>'Ein_Nom'), $obBD_conexion);
$comprobantes=$obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('Tic_Est'=>'A','order'=>'Tic_Sri'), $obBD_conexion);

?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <style></style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registar Datos Exportacion</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();"> 
                    <div class="col-sm-6">
                        
                        <fieldset class="exa-fieldset venta">
                            <legend class="Titulos2">Datos de la Venta</legend>
                            <input type="hidden" name="Vet_Cod" value=""  />
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Tipo Documento:</label>  
                                <div class="col-xs-8" >
                                    <select name="Tic_Cod" class="form-control input-xs readOnly" required="required" disabled="" >
                                        <?php 
                                        foreach ($comprobantes as $pa) {
                                            echo "<option value='$pa[Tic_Cod]'>$pa[Tic_Des]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Fecha:</label>  
                                <div class="col-xs-4" >
                                    <input type="text" name="Caj_Fec" value="" class="form-control input-xs" required="required" readonly="" />
                                </div>
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Secuencia:</label>  
                                <div class="col-xs-6" >
                                    <input type="text" name="Secuencia" value="" class="form-control input-xs" required="required" readonly="" />
                                </div>
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Autorización:</label>  
                                <div class="col-xs-8" >
                                    <input type="text" name="Autorizacion" value="" class="form-control input-xs" required="required" readonly="" />
                                </div>
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Valor del FOB local:</label>  
                                <div class="col-xs-4" >
                                    <input type="text" name="Total" value="" class="form-control input-xs" required="required" readonly="" style="text-align: right;" />
                                </div>
                            </div> 
                            <div class="center">
                            <button type="button" onclick="$('#ventaDialog').dialog('open');" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> Buscar Documento de Venta</button>
                        </div>
                        </fieldset>
                        <fieldset class="exa-fieldset venta">
                            <legend class="Titulos2">Datos del Cliente</legend>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Tipo Doc:</label> 
                                <div class="col-xs-8" >
                                    <select id="Ide_Pre"  name="Ide_Pre" class="form-control input-xs readOnly" required="required" disabled="">                                        
                                        <option value="20">RUC</option>
                                        <option value="21">PASAPORTE/IDENTIFICACION TRIB. DEL EXTERIOR</option>
                                    </select>
                                </div>
                            </div>    
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Documento:</label>  
                                <div class="col-xs-8" >
                                    <input type="text" name="Ruc" value="" class="form-control input-xs" required="required" readonly="" />
                                </div>
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Cliente:</label>  
                                <div class="col-xs-8" >
                                    <input type="text" name="Cliente" value="" class="form-control input-xs" required="required" readonly="" />
                                </div>
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Parte Relacionada:</label> 
                                <div class="col-xs-8" >
                                    <input type="checkbox" id="Eve_Rel"  name="Eve_Rel" value="S" offval="N" /> 
                                </div>
                            </div>
                            <div class="form-group pasaporte">
                                <label class="col-xs-4 control-label label-xs required">Tipo Cliente:</label> 
                                <div class="col-xs-8" >
                                    <select id="Cli_Tic"  name="Cli_Tic" class="form-control input-xs readOnly" required="required" disabled=""> 
                                        <option value="">Seleccione..</option>
                                        <option value="N">Persona Natural</option>
                                        <option value="J">Sociedad</option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>
                    </div>    
                    <div class="col-sm-6">    
                        <fieldset class="exa-fieldset"  >
                            <legend class="Titulos2">Información de la Exportación</legend>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Tipo Regimen:</label>  
                                <div class="col-xs-8" >
                                    <select id="Reg_Cod" name="Reg_Cod" class="form-control input-xs datatrigger" required="required" onchange="setField();">                                        
                                        <option value="01">REGIMEN GENERAL</option>
                                        <option value="02">PARAISO FISCAL</option>
                                        <option value="03">REGIMEN FISCAL PREFERENTE O JURISDICCION DE MENOR IMPOSICION</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group regimen tipo01">
                                <label class="col-xs-4 control-label label-xs required">Pais Pago R. General:</label>  
                                <div class="col-xs-8" >
                                    <select id="Pas_Cod_Gen"  name="Pas_Cod_Gen" class="form-control input-xs" required="required" onchange="setPais($(this).val());">
                                        <?php 
                                        foreach ($paises as $pa) {
                                            echo "<option value='$pa[Pas_Cod]'>$pa[Pas_Nom]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div> 
                            <div class="form-group regimen tipo02">
                                <label class="col-xs-4 control-label label-xs required">Pago Paraiso Fiscal:</label>  
                                <div class="col-xs-8" >
                                    <select id="Paf_Cod" name="Paf_Cod" class="form-control input-xs" required="required" onchange="setPais($(this).find('option:selected').data('Pas_Cod'));">
                                        <?php 
                                        foreach ($paraisos as $pa) {
                                            echo "<option value='$pa[Paf_Cod]' data--pas_-cod='$pa[Pas_Cod]'>$pa[Paf_Nom]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div> 
                            <div class="form-group regimen tipo03">
                                <label class="col-xs-4 control-label label-xs required">Denominación Paraiso Fiscal:</label>  
                                <div class="col-xs-8" >
                                    <input type="text" name="Reg_Den" value="" class="form-control input-xs" required="required" />
                                </div>
                            </div>    
                            <div class="form-group regimen tipo01 tipo02 tipo03">
                                <label class="col-xs-4 control-label label-xs required">Pais Efectua Pago:</label>  
                                <div class="col-xs-8" >
                                    <select id="Pas_Cod" name="Pas_Cod" class="form-control input-xs readOnly"  required="required">
                                        <?php 
                                        foreach ($paises as $pa) {
                                            echo "<option value='$pa[Pas_Cod]'>$pa[Pas_Nom]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div> 
                            <script>
                                function setPais(Pas_Cod){
                                    if($.vv(Pas_Cod)){
                                        $('#Pas_Cod').val(Pas_Cod); 
                                    }
                                }
                                function setField(){
                                    $('.regimen').find(':input').val('').prop('disabled',true).end().hide();
                                    var tipo=$('#Reg_Cod').val();
                                    //console.log(tipo);
                                    if(tipo!==""){
                                        $('.regimen.tipo'+tipo).find(':input').val('').prop('disabled',false).end().show();
                                        $('#Pas_Cod').prop('disabled',tipo!=="03");
                                        $('#Pas_Cod').prop('required',true);
                                    }
                                }
                                setField();
                            </script>
                        </fieldset>
                        <fieldset class="exa-fieldset"  >
                            <legend class="Titulos2">Detalle de la Exportación</legend>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Tipo Exportación:</label>  
                                <div class="col-xs-8" >
                                    <select id="Ref_Cod" name="Ref_Cod" class="form-control input-xs datatrigger" required="required" onchange="setFieldExpo();">                                        
                                        <option value="01">01 - Con Referendo</option>
                                        <option value="02">02 - Sin Referendo</option>
                                        <option value="03">03 - Exportación de Servicios u Otros Ingresos del Exterior</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Fecha Transaccion:</label>  
                                <div class="col-xs-4" ><input type="text" name="Eve_Fec" required="required" class="form-control input-xs datepicker" /> </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">FOB / Valor Ingreso Exterior:</label>  
                                <div class="col-xs-4" ><input type="text" name="Eve_Fob" onkeypress="return validar_decimal(event);" required="required" style="text-align: right;" class="form-control input-xs" placeholder="0.00" /> </div>
                            </div>
                            <div class="form-group exporta tipo03">
                                <label class="col-xs-4 control-label label-xs required">Tipo Ingreso:</label>  
                                <div class="col-xs-8" >
                                    <select id="Ein_Cod" name="Ein_Cod" class="form-control input-xs" required="required">
                                        <?php 
                                        foreach ($ingresos as $in) {
                                            echo "<option value='$in[Ein_Cod]' >$in[Ein_Nom]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group exporta tipo03">
                                <label class="col-xs-8 control-label label-xs required">Ingreso graba Imp. Renta o similar en el Pais que se obtuvo?:</label>  
                                <div class="col-xs-2" ><input type="checkbox" id="Eve_Ren_Chk" name="Eve_Ren_Chk" value="S" offval="N" onchange="$('#Eve_Ren').prop('disabled',!$(this).is(':checked')).val('');" class="datatrigger" /></div>
                                <div class="col-xs-2" ><input type="text" id="Eve_Ren" name="Eve_Ren" placeholder="0.00" class="form-control input-xs txtRight" disabled="" /></div>
                            </div>
                        </fieldset> 
                        <fieldset class="exa-fieldset exporta tipo01"  >
                            <legend class="Titulos2">Referendo</legend>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Doc. Transportre:</label>  
                                <div class="col-xs-8" >
                                    <input type="text" name="Eve_Dot" value="" class="form-control input-xs" required="required" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Distrito:</label>  
                                <div class="col-xs-8" >
                                    <select id="Edi_Cod" name="Edi_Cod" class="form-control input-xs" required="required">
                                        <?php 
                                        foreach ($distritos as $di) {
                                            echo "<option value='$di[Edi_Cod]' >$di[Edi_Nom]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Año:</label>  
                                <div class="col-xs-4" >
                                    <input type="text" name="Eve_Ano" value="" class="form-control input-xs" minlength="4" maxlength="4" required="required" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Regimen:</label>  
                                <div class="col-xs-8" >
                                    <select id="Ere_Cod" name="Ere_Cod" class="form-control input-xs" required="required">
                                        <?php 
                                        foreach ($regimen as $re) {
                                            echo "<option value='$re[Ere_Cod]' >$re[Ere_Nom]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Correlativo:</label>  
                                <div class="col-xs-4" >
                                    <input type="text" name="Eve_Cor" value="" class="form-control input-xs" minlength="8" maxlength="8" required="required"/>
                                </div>
                                <label class="col-xs-2 control-label label-xs">Verificador:</label> 
                                <div class="col-xs-2" >
                                    <input type="text" id="Eve_Ver" name="Eve_Ver" value="" class="form-control input-xs" minlength="1" maxlength="1" readonly="" />
                                </div>
                            </div>
                        </fieldset>  
                        
                        <script>
                            function setFieldExpo(){
                                $('.exporta').find(':input').prop('disabled',true).not('input[type=checkbox]').val('').end().end().hide();
                                var tipo=$('#Ref_Cod').val();
                                //console.log(tipo);
                                if(tipo!==""){
                                    $('.exporta.tipo'+tipo).find(':input').prop('disabled',false).end().show();
                                    $('#Eve_Ren_Chk').prop('checked',false).trigger('change');
                                }
                            }
                            setFieldExpo();
                            $('.datepicker').createDatePickers();
                        </script>
                    </div>
                        
                    </form>    
                </div> 
                <div class="col-sm-12">
                    <div class="center">
                        <button type="button" onclick="$('#formDocumento').formSubmit();" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar Exportación</button>
                    </div>
                </div>
            </div>
        </div>        
        <script type="text/javascript">
            function clearDocument(){
                $('#formDocumento').setData({Reg_Cod:'01',Ref_Cod:'01'});
            }
            function validaDocument(){
                var data=$('#formDocumento').getData('saveDocumento');
                if(data['Vet_Cod']==='') return $.alert('Debe selecionar el documento de enta al exterior!');
                $.createDialogConfirm('¿Esta seguro de guardar la Venta como Exportación?', data, saveDocument);
            }
            function saveDocument(data){ //console.log(data); console.log(data['rets']);
                $.saveDataJson('',data,
                    function (resp){
                        clearDocument();
                        $.Search('venta');
                    }
                ); 
            }
            function SelectVta(venta){                
                venta['Eve_Rel']='N';
                //console.log(venta);
                $('.venta').setData(venta);
				$('input[name=Eve_Fob]').val(venta['Total']);
                $('#ventaDialog').dialog('close');
            }
        </script>
         <?php include('../COMPONENTES/buscaVentaModal.php'); ?>
    </BODY>
</HTML>



