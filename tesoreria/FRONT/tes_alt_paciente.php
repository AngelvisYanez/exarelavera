<?php

/**
 * Permite registrar un nuevo Cliente ya sea Nacional(Cedula o Ruc) o Extranjero(Pasaporte)
 *
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualizaci�n:	2012-04-16
 * @author lewis.chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2014-05-21
 *
 * @package tesoreria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_paciente.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
* objeto para la conexion
* @var Class_Log_Conexion_Tes
*/
$obBD_conexion = new Class_Log_Conexion_Cli($Ses_Dat_Dis);

/**
* objeto para consultas
* @var Class_Log_Datos_Tes
*/
$obBD_con1 =  new Class_Log_Datos_Cli;

/* ver si exite un paciente */
if(isset($searchCliente)){
    $responce = $obBD_con1->getRowConsulta(17, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con1->getRowConsulta(18, $responce['Prs_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Pac_Cod']))?$responce['existe']=true:$responce['existe']=false;
    $obBD_con1->echoJson($responce);
}

if(isset($verificaAjax)){
    $varios="persona.Prs_Ape = 'VARIOS INGRESOS'";
    $busqueda=array(
        'success'=>true,
        'consumidorFinal'=>$obBD_con1->getArrayConsulta('cliente.selectWhere', array('setWhere'=>array('setEmpCod','isActive', /*'byConsF',*/ 'isConsF')), $obBD_conexion),
        'consumidorFinalPersona'=>$obBD_con1->getArrayConsulta('persona.selectWhere', array('setWhere'=>array('isActive', /*'byConsF', */'isConsF')), $obBD_conexion),
        'variosIngresos'=>$obBD_con1->getArrayConsulta('cliente.selectWhere', array('where'=>$varios,'setWhere'=>array('setEmpCod', 'isActive', /*'byVariosIngresos'*/)), $obBD_conexion),
        'variosIngresosPersona'=>$obBD_con1->getArrayConsulta('persona.selectWhere',array('where'=>$varios,'setWhere'=>array('isActive'/*,'byVariosIngresos'*/)), $obBD_conexion),
    );
    $obBD_con1->echoJson($busqueda);
}
/* guarda consumidor final*/
if(isset($guardarConsumidorFnal)){
    $condicion = 'VARIOS INGRESOS';
    $resp=array('success'=>false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try{
        $data = $_POST;
        $data['Emp_Cod']=$Ses_Emp_Cod;
        $obBD_con1->operacionobBD(19,$data['consumidorF'],$obBD_conexion);
        $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion);
        $obBD_con1->operacionobBD(20,$data,$obBD_conexion);
        $data['Cli_Cod'] = $obBD_con1->insercionid($obBD_conexion);
        if($data['consumidorF']['Prs_Ape'] == $condicion){ $obBD_con1->operacionobBD('caja_clien.insert', array('Cli_Cod'=>$data['Cli_Cod']),$obBD_conexion);}
        $obBD_con1->operacionobBD(12,$data['consumidorF']['Prs_Ced'].'*'.$data['consumidorF']['Prs_Nom'].'*'.$data['consumidorF']['Prs_Ape'].'*'.$data['consumidorF']['Prs_Sex'].'*'.$data['consumidorF']['Prs_Dir'].'*'.$data['consumidorF']['Prs_Tel'].'*'.$data['consumidorF']['Prs_Tel'].'*'.$data['consumidorF']['Prs_Tel'].'*'.$data['consumidorF']['Ciu_Cod'].'*'.$data['consumidorF']['Ide_Cod'].'*'.$data['consumidorF']['Prs_Cor'].'*'.$data['Prs_Cod'],$obBD_conexion);
    }catch(Exception $e){ $obBD_con1->rollBack_nomsn($obBD_conexion); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    $resp['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if(!$resp['success']) $resp['error']=$obBD_con1->MsgError;
    $obBD_con1->echoJson($resp);
}
/* Guardar persona como cliente cuando existe Consumidor final en persona */
if(isset($guardarCfPersona)){
    $resp=array('success'=>false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try{
        $data = $_POST;
        $data['persona']['Emp_Cod']=$Ses_Emp_Cod;
        $obBD_con1->operacionobBD(20,$data['persona'],$obBD_conexion);
    }catch(Exception $e){ $obBD_con1->rollBack_nomsn($obBD_conexion); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    $resp['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if(!$resp['success']) $resp['error']=$obBD_con1->MsgError;
    $obBD_con1->echoJson($resp);
}
/* Guardar persona como cliente cuando existe Varios Ingresos en persona   */
if(isset($guardarVIPersona)){
    //$obBD_conexion1 = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_con1->echoLog("guardarVIPersona");
    $resp=array('success'=>false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try{
        $data = $_POST;
        $data['persona']['Emp_Cod']=$Ses_Emp_Cod;
        //$obBD_con1->echoLog($data['Emp_Cod']);
        //$obBD_con1->echoLog($data);
        $obBD_con1->operacionobBD(20,$data['persona'],$obBD_conexion);
        $data['persona']['Cli_Cod'] = $obBD_con1->insercionid($obBD_conexion);
        //$obBD_con1->echoLog($data['persona']['Cli_Cod']);
        //$obBD_con1->operacionobBD(29,'*'.$data['persona']['Cli_Cod'],$obBD_conexion, true);
        $obBD_con1->operacionobBD('caja_clien.insert', array('Cli_Cod'=>$data['persona']['Cli_Cod']),$obBD_conexion);
    }catch(Exception $e){ $obBD_con1->rollBack_nomsn($obBD_conexion); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    $resp['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if(!$resp['success']) $resp['error']=$obBD_con1->MsgError;
    $obBD_con1->echoJson($resp);
}

/* guarda un nuevo cliente */
if(isset($guardarCliente)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $data['Pac_Cor']=$data['Prs_Cor'];
    $obBD_con1->inicio_transaccion($obBD_conexion);
        if(empty($Prs_Cod)){
            $obBD_con1->operacionobBD(19,$data,$obBD_conexion);
            $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion);
        }else{
            $pers=$obBD_con1->getRowConsulta('persona.selectWhere',array('clean'=>true, 'Prs_Cod'=>$Prs_Cod),$obBD_conexion);
            if(empty($Prs_Cor)&&!empty($pers['Prs_Cor'])) $data['Pac_Cor']=$pers['Prs_Cor'];
        }
        $obBD_con1->operacionobBD(12,$Prs_Ced.'*'.$Prs_Nom.'*'.$Prs_Ape.'*'.$Prs_Sex.'*'.$Prs_Dir.'*'.$Prs_Tel.'*'.$Prs_Tel.'*'.$Prs_Cel.'*'.$Ciu_Cod.'*'.$Ide_Cod.'*'.(empty($pers['Prs_Cor'])&&!empty($Prs_Cor)?$Prs_Cor:'').'*'.$data['Prs_Cod'],$obBD_conexion);
        $obBD_con1->operacionobBD(20,$data,$obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) { $responce['success'] = true; }
    else{ $responce['success'] = false; $responce['message'] = "No se ha logrado realizar la Transaccion"; }
    $obBD_con1->echoJson($responce);
}
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script type="text/javascript" src="../VALIDACIONES/tes_val_paciente.js?a=786"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  REGISTRAR PACIENTES</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-3"></div>
                    <div class="col-md-6 col-sm-8">
                        <form class="form-horizontal normal" id="formCliente" name="formCliente" action="javascript:guardarCliente();">
                            <input name="Prs_Cod" type="text" class="hidden" />
                            <fieldset class="exa-fieldset" >
                                <legend class="Titulos2">Datos del paciente</legend>
                                
                                <div id="btnsCFd" class="form-group " style="display:none;">
                                    <!--col align-self-end -->
                                    <div class="col-sm-9"></div>
                                    <div class="col-sm-3">
                                        <button id="btn_Cns" type="button" onclick="crearCFConPersona()" class="btn btn-xs btn-info no" ><i class="glyphicon glyphicon-user"></i> Crear Consumidor Final</button>
                                    </div>


                                </div>
                                <div id="btnsCFdv" class="form-group " style="display:none;">
                                    <div class="col-sm-9"></div>
                                    <div class="col-sm-3">
                                        <button id="btnCVI" type="button" onclick="crearCFConPersona()" class="btn btn-xs btn-info no" ><i class="glyphicon glyphicon-tent"></i>  Crear  Varios  Ingresos</button>
                                    </div>

                                </div>

                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Ciudadano:</label>
                                    <div class="col-xs-5" >
                                        <div class="btn-group" data-toggle="buttons">
                                            <label id="lb_ec" class="btn btn-success btn-xs">
                                                <input id="radioec" name="tipo" value="Ec" type="radio" checked=""><i id="spanec" class="fa fa-check"></i> Ecuatoriano
                                            </label>
                                            <label id="lb_ex" class="btn btn-default btn-xs">
                                                <input id="radioex" name="tipo" value="Ex" type="radio"><i id="spanex" class="fa fa-check" style="display: none;"></i> Extranjero
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">C&eacute;dula/RUC:</label>
                                    <div class="col-xs-5" >
                                        <div class="input-group input-group-xs">
                                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="validar(1)" required="" />
                                            <span class="input-group-addon validate" ><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Documento:</label>
                                    <div class="col-xs-5" >
                                        <?php $rs_identi = $obBD_con1->getArrayConsulta(16, '', $obBD_conexion); ?>
                                        <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                                            <option value="">Seleccionar</option>
                                            <?php foreach($rs_identi as $row){ echo "<option value='$row[Ide_Cod]' data-tipo='$row[Tipo]'>$row[Ide_Des]</option>"; } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Raz&oacute;n Social:</span></label>
                                    <div class="col-xs-7" ><input name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Nombres:</span><span class='juridico' style="display: none;">Nomb.Comerc.:</span></label>
                                    <div class="col-xs-7" ><input name="Prs_Nom" type="text" class="form-control input-xs" required="" /></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Empresa:</span></label>
                                    <div class="col-xs-6" ><input name="Pac_Emp" type="text" class="form-control input-xs" required="" /></div>
                                </div>
                                <div class="form-group natural">
                                    <label class="col-xs-3 control-label label-xs required">G&eacute;nero:</label>
                                    <div class="col-xs-4" >
                                        <select name="Prs_Sex" class="form-control input-xs">
                                            <option value = "M" >MASCULINO</option>
                                            <option value = "F" >FEMENINO</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Fecha de nacimiento:</span></label>
                                    <div class="col-md-3">
                                        <input id="Pac_Fna" name="Pac_Fna" type="text" placeholder="" class="form-control input-xs" required>
                                    </div>
                                </div>
                            </fieldset>
                            <fieldset class="exa-fieldset" >
                                <legend class="Titulos2">Datos de ubicaci&oacute;n</legend>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                                    <div class="col-xs-6" >
                                        <?php $rs_ciudad = $obBD_con1->getArrayConsulta(15,'',$obBD_conexion); ?>
                                        <select id="Ciu_Cod" name="Ciu_Cod" class="form-control input-xs" data-placeholder="Seleccione una ciudad" required="" >
                                            <option value=""></option>
                                            <?php  foreach($rs_ciudad as $row){ echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]' data-pais='$row[Pas_Nom]'>$row[Ciu_Des]</option>"; } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Direcci&oacute;n:</label>
                                    <div class="col-xs-9" ><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Tel&eacute;fono(s):</label>
                                    <div class="col-xs-9">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon bold alert-info">#1:</span>
                                            <input name="Prs_Tel" type="text" class="form-control input-xs" pattern="\d*" onkeypress="return validar_numeric(event);" />
                                            <span class="input-group-addon bold alert-info">#2:</span>
                                            <input name="Prs_Te2" type="text" class="form-control input-xs" pattern="\d*" onkeypress="return validar_numeric(event);"/>
                                            <span class="input-group-addon bold alert-info">#3:</span>
                                            <input name="Prs_Cel" type="text" class="form-control input-xs" pattern="\d*" onkeypress="return validar_numeric(event);"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required" >Mail:</label>
                                    <div class="col-xs-9" ><input name="Prs_Cor" type="email" class="form-control input-xs"  required="true" /></div>
                                </div>
                            </fieldset>
                            <div class="center">
                                <button type="button" onclick="$(this.form).formSubmit();" class="btn btn-sm btn-primary no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                            </div>
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"></legend>
                            <div class="form-group Titulos2">
                                <div class="col-sm-12" style="text-align: center;"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                            </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            $(function(){
                $('#Ciu_Cod').createChosen('input-xs',{tabIndex:6, width:'100%',template:function (t,d){ return '<div class="over"><b>'+t+'</b></div><div class="over desc" style="font-size:11px;"><b>Provincia:</b> '+d['prov']+' <b>Pa&iacute;s:</b> '+d['pais']+'</div>';}});

                $("#radioec").change(function(){
                    $('#Prs_Ced').attr('onchange','validar(1)');habilitar('ec',1);
                    $('#lb_ec').attr('class','btn btn-success btn-xs');
                    $('#lb_ex').attr('class','btn btn-default btn-xs');
                    $('#spanec').show();$('#spanex').hide();clear();
                });

                $("#radioex").change(function(){
                    clear();habilitar('ex',7);
                    $('#Prs_Ced').attr('onchange','validar(2)');
                    $('#lb_ex').attr('class','btn btn-success btn-xs');
                    $('#lb_ec').attr('class','btn btn-default btn-xs');
                    $('#spanex').show();$('#spanec').hide();
                });

                $('#Ide_Cod').change(function(){
                    $('#Prs_Ced').val('').focus();
                    if(this.value*1===1){
                        $('#Prs_Ced').attr('onchange','validar(2)');
                    }else{
                        $('#Prs_Ced').attr('onchange','validar(3)');
                    }
                    habilitar('ex',this.value);
                });
            });

            var err=0;
            function validar(op){
                var cedula=$('#Prs_Ced').val();
                switch(op){
                    case 1:
                        if(validaNoIdentif(cedula)['success']){  err=0; $('#Ide_Cod').val(cedula.length===10?2:1); $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ec'); }else{ err=1; $('#Ide_Cod').val(''); $('#Prs_Ced').fieldValid(false,validaNoIdentif(cedula)['message']); }
                        break;
                    case 2:
                        if(cedula.length===13 && validaNoIdentif(cedula)['success']){ err=0; $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ec');}else{ err=1; $('#Ide_Cod').val(1); $('#Prs_Ced').fieldValid(false,validaNoIdentif(cedula)['message']); }
                        break;
                    case 3:
                        err=0;
                        $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ex');
                        break;
                }
            }

            function habilitar(op,val){
                var lon_ced=$('#Prs_Ced').val().length; $('#Prs_Ced').fieldValid('');
                if(op==='ec'){
                    $('#Ide_Cod').find('option').show();
                    $('#Ide_Cod').attr('disabled',true);
                    $('#Ide_Cod').val(lon_ced===10?2:1);
                }else{
                    $('#Ide_Cod').find('option').hide().end().find('option[data-tipo="Ex"]').show();
                    $('#Ide_Cod').val(val);
                    $('#Ide_Cod').attr('disabled',false);
                }
            }

            function searchCliente(ced,tipo){
                (tipo==='ec')?ced=ced.substring(0,10):ced;
                $.post("",{searchCliente:true,Prs_Ced:ced}, function( response ) {
                    if(response['existe']===true){
                        $.alert('El paciente '+ced+' ya se encuentra registrado..!!');
                        clear();
                    }else{
                        $('#Ciu_Cod').val(response['Ciu_Cod']).trigger('chosen:updated');
                        $.extend(response,{Prs_Ced:$('#Prs_Ced').val(),Ide_Cod:$('#Ide_Cod').val()});
                        $('#formCliente').setData(response,false);
                    }
                },'json').fail(function (){$.alert();});
            }

            function clear(){
                $('#formCliente').setData({Cli_Tic:'N',Prs_Ciu:'Ec',Prs_Sex:'M'});
                $('#Prs_Ced').val('').focus();
                $('.juridico').hide();$('.natural').show();
            }

            function guardarCliente(){
                if(err===1){$.alert('Debe ingresar un n&uacute;mero de identificaci&oacute;n v&aacute;lido'); return false;}
                $.saveDataJson("",$('#formCliente').getData('guardarCliente'), function( resp ){ $("#radioec").trigger('change'); clear(); });
            }
        </script>
    </BODY>
</HTML>

