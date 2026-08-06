<?php	
/**
* @abstract Permite realizar movimientos de inventario
* @author Erik Niebla
* @version 1.0
* Fecha de creaci?n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque_post.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Che;

$hoy = date("Y-m-d");
$mes = date("m");

//if(isset($saveBene)){      
//        $responce['id']='';
//        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
//        $obBD_con1->grabarv_registros(sentencias_che(363,$obBD_con1->parametros($apel.'*'.$nomb)),$obBD_conexion->conexion);
//        $ultimo = $obBD_con1->insercionid ($obBD_conexion->conexion);
//        $obBD_con1->grabarv_registros(sentencias_che(364,$obBD_con1->parametros($ultimo.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);
//        $responce['id'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
//        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);       
//        if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false; $responce['message']=$obBD_con1->MsgError;
//	echo json_encode($responce);exit();
//}

if(isset($valChe)){ 
    $conteo = $obBD_con1->getRowConsulta(405, $Ban_Cod.'*'.$valChe, $obBD_conexion);
    $contar = $obBD_con1->getRowConsulta(360, $Ban_Cod, $obBD_conexion);
    $contar['success']=true;
    if($conteo['conteo']==0)$contar['valid']=true; else $contar['valid']=false;
    echo json_encode($contar); exit();
}
if(isset($cheNum)){ 
    $contar = $obBD_con1->getRowConsulta(360, $Ban_Cod, $obBD_conexion);
    $contar['success']=true; 
    echo json_encode($contar); exit();
}
if(isset($term)){ $contar = $obBD_con1->getArrayConsulta(365, $Ses_Emp_Cod.'*'.$term, $obBD_conexion);  echo json_encode($contar);exit(); }
if(isset($cuenAjax)){ 
    $obBD_con1->getPageGridJson(352, $search.'*'.$Ses_Emp_Cod.'*'.(isset($Pec_Cod)?$Pec_Cod:'').'*'.$op_opciones, $obBD_conexion, $page, $rows);
}
if(isset($clieAjax)){ 
    $obBD_con1->getPageGridJson(359, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);    
}
if(isset($provAjax)){ 
    $obBD_con1->getPageGridJson(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);     
}

if(isset($saveCheque)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(410,$data,$obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true,'message'=>'Transacci�n realizada con Exito!');
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacci�n!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if(isset($searchCheque)){
    $data=$_GET;
    $response=array('success'=>true);
    $response['rows'] = $obBD_con1->getArrayConsulta(411, $data, $obBD_conexion);
    if($obBD_con1->Error==0) {
        $response['success'] = true;
    } else {
        $response=array('success'=>false,'message'=>'No se pudo realizar la transacci�n!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>  
        <style>#tabs.ui-widget-content{background:none !important;} .ui-tabs-panel{padding-bottom: 0 !important;}.ui-tabs-nav{padding-top: 0 !important;}
        </style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Cheques Post-fechados</h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div>
                
                <form id="periodoForm" class="hidden">
                    <input name="periodo" type="text">
                    <input type="text" name="Pec_Cod" />
                    <input type="text" name="Pld_Cod" />
                    <input type="text" name="Ban_Cod" />
                    <input type="text" name="Ban_Cue" />
                </form>                
                <div class="row">  
                    <div class="col-sm-12">
                        <div id="tabs" class="ui-tab-fix">
                            <ul>
                                <!--<li><a href="#tabs-1">Ingresos</a></li>-->
                                <li><a href="#tabs-2">Registrar</a></li>
                                <li><a href="#tabs-3">Cheques</a></li>
                                <!--<li><div><select><option value="">Ber</option><</select></div></li>-->
                            </ul>
                            <div class="panels-area form-horizontal normal ">

    <div id="tabs-2">
        <div class="row">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Cheque</legend>	
            <form name="formComp" id="formComp" method="post" action="javascript:validaEgreso()">                
                    <div class="col-sm-12 form-horizontal normal">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Periodo/Banco</legend>
                            <div class="form-group">
                                <label class="col-xs-1 control-label label-xs required">Periodo:</label> 
                                <div class="col-xs-2">
                                    <select name="Pec_Cod" id="Pec_Cod"  class="form-control input-sm response['success']===true">
                                        <option value="">Seleccione...</option>
                                        <?php $row_rs_periodos = $obBD_con1->getArrayConsulta(214/*339*/, $Ses_Emp_Cod, $obBD_conexion);
                                          if (count($row_rs_periodos) > 0){ $periodo = current($row_rs_periodos);
                                              foreach ($row_rs_periodos as $row){ 
                                                  echo "<option value='$row[Pec_Cod]' data--pec_-cod='$row[Pec_Cod]' data--pla_-cod='$row[Pla_Cod]' data--pec_-fei='$row[Pec_Fei]' data--pec_-fef='$row[Pec_Fef]'  data-periodo='$row[Periodo]'>$row[Periodo]</option>";
                                            }    
                                        } ?>
                                    </select>   
                                </div>
                                <label class="col-xs-1 control-label label-xs required">Banco:</label> 
                                <div class="col-xs-4">
                                     <select name="Bak_Cod" id="Bak_Cod" class="form-control input-sm required">
                                        <option value="">Seleccione...</option>
                                        <?php $row_rs_bancos = $obBD_con1->getArrayConsulta(409, $Ses_Emp_Cod, $obBD_conexion);
                                          if (count($row_rs_bancos) > 0){ $periodo = current($row_rs_bancos);
                                              foreach ($row_rs_bancos as $row){ 
                                                  echo "<option value='$row[Bak_Cod]' data--pec_-cod='$row[Bak_Cod]' data-periodo='$row[Bak_Des]'>$row[Bak_Des]</option>";
                                              }
                                        } ?>
                                    </select> 
                                </div> 
                            </div>    
                        </fieldset>
                    </div>

                    <div class="col-xs-6">
                        <div class="form-group">
                            <div class="col-xs-3"><div class="checkbox check-big input-xs" ><label><input id="es_cheque" checked type="checkbox" value="true" offval="false" hidden="true"/></label></div></div>
                            <div class="col-xs-3"><div class="checkbox check-big input-xs"><label><input onchange="setPosfecha()" id="postfecha" type="checkbox" value="true" offval="false"  />Postfechado</label></div></div>  
                        </div>  
                        <div class="form-group es_cheque">
                            <label class="col-xs-3 control-label label-xs required">Fecha Cheque:</label> 
                            <div class="col-xs-4" ><input id="Chp_Fec" name="Chp_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="form-control input-xs" required="true" disabled /></div>
                        </div> 
                        <div class="form-group es_cheque">
                            <label class="col-xs-3 control-label label-xs required">No. Cheque:</label> 
                            <div class="col-xs-4"><input  class="form-control input-xs" style="text-align: center" name="Chp_Num" id="Chp_Num" type="text" size="10" placeholder="N�MERO DE CHEQUE" onkeypress="return  validar_numeric(event);"  required /></div>
                            <div class="col-xs-4 msgDiv"><img class="imgMsg" /><label class="lblMsg"></label></div>
                        </div> 
                        <div class="form-group es_cheque">
                            <label class="col-xs-3 control-label label-xs required">No. Cuenta:</label> 
                            <div class="col-xs-4"><input  class="form-control input-xs" style="text-align: center" name="Chp_Cta" id="Chp_Cta" type="text" size="10" placeholder="N�MERO DE CUENTA" onkeypress="return  validar_numeric(event);"  required /></div>
                        </div>
                        <div class="form-group es_cheque">
                            <label class="col-xs-3 control-label label-xs required">Propietario:</label>
                            <div class="col-xs-5"><input class="form-control input-xs" style="text-align: center;" type="text" name="Chp_Pro" id="Chp_Pro" placeholder="PROPIETARIO DE LA CUENTA"></div>
                        </div> 
                        <div class="form-group es_cheque">
                            <label class="col-xs-3 control-label label-xs required">Beneficiario:</label> 
                            <div class="col-xs-5" ><input id="Chp_Ben" name="Chp_Ben" type="text" style="text-align: center" class="form-control input-xs" placeholder="APELLIDOS Y NOMBRES" required/></div>
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Valor:</label> 
                            <div class="col-xs-3"><div class="input-group input-group-xs"><span class="input-group-addon" style="font-weight: bold;">$</span><input  class="form-control input-xs" name="Chp_Val" id="Chp_Val" type="text" size="10" maxlength="12" style="text-align:right;" required placeholder="0.00" /></div></div>
                            <div class="col-xs-2"><div class="input-group input-group-xs"><span class="input-group-addon" style="font-weight: bold;">%</span><input  class="form-control input-xs" name="Chp_Por" id="Chp_Por" type="text" size="10" maxlength="12" style="text-align:right" required placeholder="0.00" onkeyup="calculaGanancia();"/></div></div>
                            <div class="col-xs-3"><div class="input-group input-group-xs"><span class="input-group-addon" style="font-weight: bold; font-size: 11px;">Utilidad</span><input  class="form-control input-xs" name="Chp_Gan" id="Chp_Gan" type="text" size="10" maxlength="12" style="text-align:right" required placeholder="0.00" readonly/></div></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Valor entregado:</label> 
                            <div class="col-xs-3"><div class="input-group input-group-xs"><span class="input-group-addon" style="font-weight: bold;">$</span><input  class="form-control input-xs" name="Chp_Ent" id="Chp_Ent" type="text" size="10" maxlength="12" style="text-align:right" required placeholder="0.00" readonly/></div></div>

                        </div>                   
                    </div>
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Concepto:</label> 
                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Chp_Con" id="Chp_Con" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Observaci&oacuten:</label>  
                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Chp_Obs" id="Chp_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                        </div> 
                    </div>
                    <div class="col-sm-12 center" style="padding-top: 6px;">                    
                        <button onclick="guardaCheque();" title="Guardar Cheque" type="button" class="btn btn-primary start" ><i class="glyphicon glyphicon-floppy-disk"></i><span> Guardar</span></button><span style="width: 15px;"></span>                
                    </div>
                    <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( &nbsp;<span class="required"></span> ) son campos obligatorios.</div>
            </form>
        </fieldset>
        </div>    
    </div>
<!-- FIN FORMULARIO COMPROBANTE DE EGRESO -->

    <div id="tabs-3" class="ui-tabs-panel ui-widget-content ui-corner-bottom" >
        <form id="frm_bus" name="frm_bus" class="form-horizontal normal" method="post" action="javascript:">
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">B&uacute;squeda de cheques</legend>
                <div class="form-group">
                    <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                    <div class="col-sm-5 radioset">
                        <input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)"/><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                        <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)"/><label for="rad_ba2">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                    <div class="col-sm-5">
                        <div class="input-group">
                            <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                            <span class="input-group-btn">
                                <button id="btnSearch" onclick="obtenerCheques();" class="btn btn-success btn-xs" type="button" title="Buscar Cliente"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                            </span>
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
        <div style="padding-top: 6px;">   
            <table id="comp"></table>
            <div id="compPager"></div>
        </div>
    </div>
                </div>   
            </div>
        </div>
    </div>

    <script type="text/javascript"> 

        function guardaCheque(){
            $.saveDataJson('',$('#formComp').getData('saveCheque'), 
                function( resp ){
                if(resp['success']==true){
                    $.alert("Cheque guardado con �xito");
                    document.getElementById("formComp").reset();
                }else{
                    
                }
                return false;
            });
        }

        function calculaGanancia(){
            var valor = $('#Chp_Val').val();
            var porcentaje = $('#Chp_Por').val() / 100;
            var ganancia = valor * porcentaje;
            $('#Chp_Gan').val(ganancia);
            var entregado = valor - ganancia;
            $('#Chp_Ent').val(entregado);
        }

        function obtenerCheques(){
            $.get("",{searchCheque:true}, function( response ) {
                //console.log(response['rows']);
                if(response['success']===true){

                    $("#comp").setRows(response['rows']);
                }else{
                    $.alert(response['message']);
                }
            },'json').fail(function(error) {
                $.alert("El Servidor ha fallado en responder!"); 
            }).always(function(response){ 
                //console.log(response); 
            });    
        }
        

        $(document).ready(function () {  
            $('#Chp_Fec').toggleClass('disabled').find('input').toggleAttr('disabled');
            gridComp=$("#comp");
            gridComp.createGrid({                               
                colModel: [
                    { label: 'Cod. Int.', name: 'Index', key: true, width: 15, align:"center", hidden:true },
                    { label: 'Cod. Int.', name: 'Chp_Cod', width: 20 },                      
                    { label: 'Beneficiario', name: 'Chp_Ben', width: 70  },
                    { label: 'Banco', name: 'Bak_Des', width: 60 },
                    { label: 'Cuenta', name: 'Chp_Cta', width: 30, align: 'center'},
                    { label: 'Cheque', name: 'Chp_Num', width: 30, align: 'center'},
                    { label: 'Fecha', name: 'Chp_Fec', width: 30, align: 'center'},
                    { label: 'Valor', name: 'Valor', width: 30, align: 'right'},
                    { label: 'Porc (%)', name: 'Porcentaje', width: 30, align: 'right' },
                    { label: 'Ganacia ($)', name: 'Ganancia', width: 30, align: 'right'},
                    { label: 'V. Entregado', name: 'Entregado', width: 30, align: 'right'},
                    { label: 'Est', name: 'Chp_Est', width: 10, align: 'center'},
                    { label: '&nbsp;', name: 'act1', width: 15, formatter:'gridButton', formatoptions:{action:'', icon:'pencil'}},
                ], height: 'auto', caption:"Cheques Registrados", footerrow: true, userDataOnFooter: false // set a footer row
            },true, "#compPager", {view:false} ).gridButtonAdd({
                        caption:"Agregar Cuenta", buttonicon:"glyphicon glyphicon-plus", title:'Agregar Cuenta', id: "add_cuenta", onClickButton: function (){ $('#Index').val(''); $('#cuenDialog').dialog('open'); }                
                    }); $("#add_cuenta").attr('disabled','disabled').addClass('perio_cont');            
            $.clearFooterDiario("#comp",true);
        });
        
        function resizeGridComp(){ var w=$('#compGrilla').width(); if(gridComp.width()>(w+2)||gridComp.width()<(w-2)) gridComp.jqGrid('resizeGrid'); }

        function validaCheque(){  
            var numAnt=$("#Chp_Num").val();

                $.get('',{'Ban_Cod':getBanco()["Ban_Cod"],'valChe': numAnt}, function(response){
                    if(response['success']===true){
                        if(response['valid']===false){
                            numChe=(response['Che_Num']*1)+1;
                            $("#Chp_Num").val(numChe).alertMsg('El Cheque <b>No. '+numAnt+'</b> ya existe.');
                        }else {$("#Chp_Num").alertMsg();}
                    }else {numChe=0;$("#Chp_Num").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}                                
                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;        
            
        }
        function resetForm(){
            gridComp.clearGrid().updateGridDiario();
            var dat_reset={};
            $('#formIngreso').setData(dat_reset);
            $('#formComp').setData(dat_reset);
            $('#formDiario').setData(dat_reset);
            $('#es_cheque').prop("checked",false).trigger('change');
            setChequeNum();
            valor=0;glosa=""; 
            return false;
        }

        function setPosfecha(){ $('#Chp_Fec')[$('#postfecha').is(':checked')?'removeAttr':'attr']('disabled','disabled').val($('#confec').val()); }


        function setChequeNum(){
            if(tipo==="Egresos"&&$("#bancos").val()!==''&&$('#es_cheque').is(':checked')){
                $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{'Ban_Cod':getBanco()["Ban_Cod"],'cheNum':true}, function(response){
                    if(response['success']===true){
                        numChe=(response['Che_Num']*1)+1;
                        $("#Chp_Num").val(numChe).alertMsg();                                  
                    }else {numChe=0;$("#Chp_Num").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}                                
                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;        
            }else{ $("#Chp_Num").val(0).parent().find('.lblMsg').html('').end().find('.imgMsg').removeAttr('src'); }    
        }

        $( "#tabs" ).tabs({activate: function(event ,ui){
            tipo=ui.newTab[0].getElementsByTagName("a")[0].innerHTML;           
            $(ui.newTab[0].getElementsByTagName("a")[0].hash).find('div.row:first-child').effect("highlight",{},500);
            resetForm();  
        }});

        $(document).ready(function() {  
            $("input[name='Com_Fec']").createDatePickers({checkAvailability:true});
            $.createDatePickers("input[name='Chp_Fec']");    
        });  

        function resetForm2(option){ gridComp.clearGrid().updateGridDiario(); }
    </script>

    <?php $ruta='./'.(file_exists ('cheques/'.$Ses_Emp_Cod)?"cheques/$Ses_Emp_Cod/":''); ?>
    <div id="modelo" style="display:none;">
        <table style="margin-bottom:10px;" cellpadding="1" border="1">
            <tr><td align="center" class="ui-widget-header" colspan="6"><label autofocus> Imprimir Cheque </label></td></tr>
            <tr><td align="center" class="ui-widget-content" colspan="6"><b>&nbsp; {banco} &nbsp;</b></td></tr>
            <tr>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_mac_1.0.php{link}" target="_blank" title="Banco de Machala"><img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35"/></a></td>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_pac_1.0.php{link}" target="_blank" title="Banco del Pacifico"><img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23"/></a></td>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_rum_1.0.php{link}" target="_blank" title="Banco del Rumi�ahui"><img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15"/></a></td>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_gua_1.0.php{link}" target="_blank" title="Banco del Guayaquil"><img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18"/></a></td>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_pch_1.0.php{link}" target="_blank" title="Banco del Pichincha"><img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30"/></a></td>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_int_1.0.php{link}" target="_blank" title="Banco Internacional"><img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32"/></a></td>
            </tr>
        </table>
    </div> 
</BODY>
</HTML>