<?php
/**
* @abstract Permite realizar el registro de �reas,departamentos y subdepartamentos
* @author Jos� Ambulud�
* @version 1.0
* Fecha de creaci�n  2016-10-21
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_rrhh.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_rrhh($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_rrhh;

if(isset($areaAjax)){ 
    $area = $obBD_con1->getArrayConsulta(1,$Ses_Emp_Cod, $obBD_conexion);
    $departamento=$obBD_con1->getArrayConsulta(2,$Ses_Emp_Cod, $obBD_conexion);
    $cargos=$obBD_con1->getArrayConsulta(13,$Ses_Emp_Cod, $obBD_conexion);
    $arbol=  array_merge($area,$departamento);
    $arbol2=  array_merge($arbol,$cargos);
    $response=$arbol2;
    echo json_encode($response);exit();
}
//Complemento ajax para obtener el nuevo c�digo
if (isset($ajaxCodigo))
{
    if($case=='D'){$num=4;}else{$num=6;}
    $maximo = $obBD_con1->getRowConsulta($num, $Are_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);          
    $responce=$maximo;
    $responce['next']=('0'.$maximo['max'])*1+1;
    echo json_encode($responce);
    exit();
}
//Secci�n para insertar un �rea o un departamento
if(isset($save)){
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->debug(true);
    if($tipo=='A'){
        $obBD_con1->operacionobBD(3,$Are_Des.'*'.$Ses_Emp_Cod, $obBD_conexion);
    }
    if($tipo=='D'){
        $obBD_con1->operacionobBD(5,$Are_Cod.'*'.$Dep_Des.'*'.(isset($Dep_Rec)?$Dep_Rec:'').'*'.$Dep_Cdc.'*'.$Ses_Emp_Cod, $obBD_conexion);
    }
    if($tipo=='SD'){
        $obBD_con1->operacionobBD(5,$Are_Cod_SD.'*'.$Dep_Sud.'*'.$Dep_Rec_SD.'*'.$Dep_Cdc_SD.'*'.$Ses_Emp_Cod, $obBD_conexion);
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; }  
    echo json_encode($responce);
    exit();
}
//Secci�n para guardar cun tipo de cargo
if(isset($saveCargo))
{
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(11,$Dep_Cod.'*'.$Tic_Des.'*'.$Tic_Per, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; }  
    echo json_encode($responce);
    exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Areas Cargos Registrar [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
        <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
        <link rel="stylesheet" href="../../framework/jquery/summernote/summernote.css">
        <script type="text/javascript" src="../../framework/jquery/summernote/summernote.min.js"></script>
        <script src="../../framework/jquery/summernote/lang/summernote-es-ES.js"></script>
        <style>
            .panel { margin-bottom: 1px; }
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar &Aacute;reas - Cargos</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-md-12">  
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <div class="col-md-6">
                                <div class="panel panel-success exa-panel">
                                    <div class="panel-heading"><i class="fa fa-list-ol"></i>&nbsp;&nbsp;<span>&Aacute;rbol de &aacute;reas</span>
                                        <div class="pull-right">                                       
                                            <button type="button" id="btn_are" onclick="$('#dialogArea').dialog('open');$('#Are_Des').val('');" class="btn btn-success btn-xs btn-add btn-P"><span class="fa fa-plus"></span><b> Agregar &Aacute;rea</b></button>
                                            <button type="button" id="btn_ade" onclick="$('#dialogDepar').dialog('open');$('#Dep_Des').val('').focus();" class="btn btn-success btn-xs btn-add btn-P" style="display: none;"><span class="fa fa-plus"></span><b> Agregar Departamento</b></button>
                                            <button type="button" id="btn_asu" onclick="$('#dialogSubdepar').dialog('open');$('#Dep_Sud').val('').focus();" class="btn btn-success btn-xs btn-add btn-P" style="display: none;"><span class="fa fa-plus"></span><b> Agregar Subdepartamento</b></button>
                                            <button type="button" id="btn_car" onclick="" class="btn btn-primary btn-xs btn-add btn-P" style="display: none;"><span class="fa fa-plus"></span><b> Agregar Cargo</b></button>
                                       </div>
                                    </div>
                                    <div class="panel-body">
                                        <div class="scrollable-tree" style="height: 400px;"><div id="using_json_2"></div></div>
                                    </div> 
                                    <div id="foot" class="panel-footer"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-home red"></span> &Aacute;reas | <span class="glyphicon fa fa-users blue"></span> Departamentos | <span class="glyphicon fa fa-briefcase green"></span> Subdepartamentos | <span class="glyphicon glyphicon-user black"></span> Cargos</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <fieldset class="exa-fieldset">                           
                                    <legend class="Titulos2">Registro de cargo</legend>
                                    <form id="formCargo" name="formCargo" class="form-horizontal normal" action="javascript:saveCargo();">
                                        <!--C�digo del departamento-->
                                        <input type="hidden" id="Dep_Cod" name="Dep_Cod">
                                        <div class="form-group Titulos2">
                                            <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 label-xs">Subdepartamento:</label>
                                            <div class="col-md-4">
                                                <input type="text" id="dep_des" name="dep_des" class="form-control input-xs" readonly="">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 label-xs required">Nombre:</label>
                                            <div class="col-md-4">
                                                <input type="text" id="Tic_Des" name="Tic_Des" class="form-control input-xs" required="">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 label-xs">Perfil:</label>
                                            <div class="col-md-9">
                                                <textarea id="Tic_Per" name="Tic_Per"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-md-3"></div>
                                            <div class="col-md-9">
                                                <button type="submit" id="bto_gua" name="bto_gua" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
        <div id="dialogArea" title="Registrar nueva &aacute;rea"> 
            <div class="row">
                <div class="form-horizontal normal col-sm-12" >
                    <fieldset>
                        <legend><label class="Titulos2">Datos del &Aacute;rea</label></legend>
                        <form id="formArea" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,'formArea',save)">
                            <div class="form-group">
                                <label class="col-sm-3 control-label label-sm required">&Aacute;rea:</label>  
                                <div class="col-sm-7" >
                                    <input type="hidden" name="tipo" value="A"/>
                                    <input type="text" class="form-control input-xs" name="Are_Des" id="Are_Des" required/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label"></label>
                                <div class="col-sm-9">
                                    <button type="submit"  class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Agregar</button>
                                    <button type="button" onclick="$('#dialogArea').dialog('close');"  class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                                </div>
                            </div>
                        </form>    
                        <div class="form-group Titulos2">
                            <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                        </div>  
                     </fieldset>    
                </div>
            </div>
        </div>
        <div id="dialogDepar" title="Registrar nuevo departamento"> 
            <div class="row">
                <div class="form-horizontal normal col-sm-12" >
                    <fieldset>
                        <legend><label class="Titulos2">Datos del Departamento</label></legend>
                        <form id="formDepar" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,'formDepar',save)">
                            <input type="hidden" id="Are_Cod_D" name="Are_Cod">
                            <input type="hidden" id="Dep_Cdc" name="Dep_Cdc">
                            <div class="form-group">
                                <label class="col-sm-3 control-label label-sm required">&Aacute;rea:</label>  
                                <div class="col-sm-7" >
                                    <input type="hidden" name="tipo" value="D"/>
                                    <input type="text" class="form-control input-xs" name="Are_Des_D" id="Are_Des_D" readonly=""/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label label-sm required">Departamento:</label>  
                                <div class="col-sm-7" >
                                    <input type="text" class="form-control input-xs" name="Dep_Des" id="Dep_Des" required/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label"></label>
                                <div class="col-sm-9">
                                    <button type="submit"  class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Agregar</button>
                                    <button type="button" onclick="$('#dialogDepar').dialog('close');"  class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                                </div>
                            </div>
                        </form>    
                        <div class="form-group Titulos2">
                            <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                        </div>  
                     </fieldset>    
                </div>
            </div>
        </div>
        <div id="dialogSubdepar" title="Registrar nuevo subdepartamento"> 
            <div class="row">
                <div class="form-horizontal normal col-sm-12" >
                    <fieldset>
                        <legend><label class="Titulos2">Datos del Departamento</label></legend>
                        <form id="formSubdepar" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,'formSubdepar',save)">
                            <input type="hidden" name="tipo" value="SD"/>
                            <input type="hidden" class="form-control input-xs" name="Are_Cod_SD" id="Are_Cod_SD" readonly=""/>
                            <input type="hidden" id="Dep_Cdc_SD" name="Dep_Cdc_SD">
                            <input type="hidden" id="Dep_Rec_SD" name="Dep_Rec_SD">
                            <div class="form-group">
                                <label class="col-sm-4 control-label label-sm required">&Aacute;rea:</label>  
                                <div class="col-sm-6" >
                                    <input type="text" class="form-control input-xs" name="Are_Des_SD" id="Are_Des_SD" readonly=""/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label label-sm required">Departamento:</label>  
                                <div class="col-sm-6">
                                    <input type="text" class="form-control input-xs" name="Dep_Des_SD" id="Dep_Des_SD" readonly=""/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label label-sm required">Subdepartamento:</label>  
                                <div class="col-sm-6" >
                                    <input type="text" class="form-control input-xs" name="Dep_Sud" id="Dep_Sud" required=""/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label"></label>
                                <div class="col-sm-8">
                                    <button type="submit"  class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Agregar</button>
                                    <button type="button" onclick="$('#dialogSubdepar').dialog('close');"  class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                                </div>
                            </div>
                        </form>    
                        <div class="form-group Titulos2">
                            <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                        </div>  
                     </fieldset>    
                </div>
            </div>
        </div>
        <script type="text/javascript">
            $(function (){
                //Llamo a la funci�n updateTipoActivo desde aqui para que cargue el jstree al momento de cargar la p�gina
                updateTipoActivo();
                $.createDialog('#dialogArea',200,450);
                $.createDialog('#dialogDepar',230,450);
                $.createDialog('#dialogSubdepar',250,450);
                //Secci�n inicializar el textarea como editor de texto
                $('#Tic_Per').createWYSIWYG({height: 248});
                $('#btn_car').click(function(){
                    limpiar(false);$('#Tic_Des').focus();
                });
                $("[id^='btn_a']").click(function (){
                    limpiar(true,'');$('#btn_car').hide();
                });
            });
            
            //Variable para manejo del arbol jstree
            var $treeview=$('#using_json_2');     
            var Dep_Cod=0,Dep_Des='';
            function updateTipoActivo(){
                $treeview.jstree(true).settings.core.data = {'url': '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>?areaAjax=true',"dataType": "json" };
                $treeview.jstree(true).refresh();
            } 
            $treeview.jstree({'core' : {'data': {}},
                            'types' : {
                                "A"  : {"icon" : "glyphicon glyphicon-home red"},
                                "D"  : {"icon" : "glyphicon fa fa-users blue"},
                                "SD" : {"icon" : "glyphicon fa fa-briefcase green"},
                                "C"  : {"icon" : "glyphicon glyphicon-user black"}
                            },"plugins": ["types"]}).on('select_node.jstree', function (e, data) { 
                            
                            var type=data.node.type,Are_Cod;
                            $("[id^='btn_']").hide();$('#btn_are').show();limpiar(true);$('#dep_des').val('');
                            if(type==='A'){
                                $('#btn_ade').show();
                                $('#Are_Des_D').val(data.node.text);
                                Are_Cod=data.node.original.Are_Cod;$("#Are_Cod_D").val(Are_Cod);
                                $.post('<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING)?>',{'Are_Cod':Are_Cod,'case':'D','ajaxCodigo':true},
                                    function(response){
                                        $("#Dep_Cdc").val(response['next']); 
                                    },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
                            }
                            if(type==='D'){
                                $('#btn_asu').show();
                                var descomponer=data.node.text.split('-');
                                $('#Dep_Des_SD').val(descomponer[1]);
                                Dep_Des=descomponer[1];
                                Dep_Cod=data.node.id;$('#Dep_Rec_SD').val(Dep_Cod);
                                $.post('<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING)?>',{'Are_Cod':Dep_Cod,'case':'SD','ajaxCodigo':true},
                                    function(response){
                                        $("#Dep_Cdc_SD").val(descomponer[0].trim()+'.'+response['next']);$('#Are_Cod_SD').val(data.node.original.Are_Cod);
                                        $('#Are_Des_SD').val(data.node.original.Are_Des);$('#Are_Des_sd').attr('readonly',true);
                                    },'json').fail(function() { $.alert("El Servidor ha fallado en responder!");});
                            }
                            if(type==='SD'){
                                $('#Dep_Cod').val(data.node.id);$('#dep_des').val(data.node.text);$('#btn_car').show(); 
                            }
            });
            
            //Funci�n para limpiar formCargo
            function limpiar(value)
            {
                $('#Tic_Des').attr('readonly',value);$('#bto_gua').attr('disabled',value);
            }
            //Funci�n para guardar un �rea o un departamento
            function save(form)
            {
                var data=$('#'+form).serializeObject();
                data['save']=true;
                $.post('<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING)?>',data,function (response){
                    if(response['success']===true){
                        setTimeout(function(){ $.alert('Transaccion Realizada con &Eacute;xito!'); }, 1);
                        $treeview.jstree(true).refresh();
                        $("[id^='dialog']").dialog('close');
                    }
                },'json').fail(function (){$.alert();});
            }
            //Funci�n para guardar un tipo de cargo
            function saveCargo()
            {
                var data=$('#formCargo').serializeObject();
                data['saveCargo']=true;
                $.post('<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING)?>',data,function (response){
                    if(response['success']===true){
                        $.alert('Transaccion Realizada con &Eacute;xito!');
                        $('#dep_des').val('');
                        $('#Tic_Des').val('');
                        $('#Tic_Per').summernote('reset');
                        $treeview.jstree(true).refresh();
                    }
                },'json').fail(function (){$.alert();});
                limpiar(true);
            }
        </script>
    </BODY>
</HTML>
