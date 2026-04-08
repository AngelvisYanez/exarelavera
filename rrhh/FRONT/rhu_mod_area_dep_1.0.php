<?php
/**
* @abstract Permite realizar la edici�n de areas, departamento y subdepartamentos registradosd
* @author Jos� Ambulud�
* @version 1.0
* Fecha de creaci�n  2016-10-25
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
//Complemento ajax para obtener el nombre del departamento segun el parent del jtree
if (isset($nombreDep))
{
    $departamento = $obBD_con1->getRowConsulta(9,$Dep_Cod, $obBD_conexion);          
    $response['Dep_Des']=$departamento['Dep_Des'];
    echo json_encode($response);
    exit();
}
//Secci�n para extraer los cargos de un subdepartamento
if(isset($cargo))
{
    $response=$obBD_con1->getRowConsulta(12,$Tic_Cod, $obBD_conexion);
    echo json_encode($response);
    exit();
}
//Secci�n para editar un �rea, un departamento o un subdepartamento
if(isset($save)){
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    if($tipo=='A'){
        $obBD_con1->operacionobBD(7,$Are_Cod_A.'*'.$Are_Des, $obBD_conexion);
    }
    if($tipo=='D'){
        $obBD_con1->operacionobBD(8,$Dep_Cod_D.'*'.$Dep_Des, $obBD_conexion);
    }
    if($tipo=='SD'){
        $obBD_con1->operacionobBD(8,$Dep_Cod_SD.'*'.$Dep_Sud, $obBD_conexion);
    }
    if($tipo=='C'){
        $obBD_con1->operacionobBD(14,$Tic_Cod.'*'.$Tic_Des.'*'.$Tic_Per, $obBD_conexion);
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; }  
    echo json_encode($responce);
    exit();
}
if(isset($eliminar)){
    $resp=array('success'=>false);    
    try{
        $obBD_conexion_del1 = new Class_Log_Conexion_rrhh($Ses_Dat_Dis);
        $obBD_del1 =  new Class_Log_Datos_rrhh;
        $obBD_del1->debug(true);
        $obBD_con1->debug(true);
        $obBD_del1->inicio_transaccion($obBD_conexion_del1->conexion);
        switch ($tipo){
            case "C":
                $conteo = $obBD_con1->getRowConsulta(15,$eliminar, $obBD_conexion);
                if(isset($conteo['conteo']) && ("0".$conteo['conteo'])*1>0)
                    throw new Exception("El Cargo esta siendo usado!");
                $obBD_del1->operacionobBD(16,$eliminar,$obBD_conexion);
                break;
            case "D":
            case "SD":
                $conteo = $obBD_con1->getRowConsulta(17,$eliminar, $obBD_conexion);                
                if(isset($conteo['conteo']) && ("0".$conteo['conteo'])*1>0)
                    throw new Exception("El Cargo ".$conteo['Tic_Des']." esta siendo usado!");
                $obBD_del1->operacionobBD(18,$eliminar,$obBD_conexion);
                $obBD_del1->operacionobBD(19,$eliminar,$obBD_conexion);
                break;  
            case "A":
                $conteo = $obBD_con1->getRowConsulta(20,$eliminar, $obBD_conexion); 
                $obBD_del1->echoLog($conteo );
                if(isset($conteo['conteo']) && ("0".$conteo['conteo'])*1>0)
                    throw new Exception("El Cargo ".$conteo['Tic_Des']." esta siendo usado!");
               
                $obBD_del1->operacionobBD(21,$eliminar,$obBD_conexion);
                $obBD_del1->operacionobBD(22,$eliminar,$obBD_conexion);
                $obBD_del1->operacionobBD(23,$eliminar,$obBD_conexion);
                break;
        }
        throw new Exception("Tofo ok ".$value['Tic_Des']." esta siendo usado!");
        $resp['success']=$obBD_del1->fin_transaccion_nomsn($obBD_conexion_del1);  
    } catch(Exception $e){ $obBD_del1->rollBack_nomsn($obBD_conexion_del1); $resp['message']=$e->getMessage(); $obBD_del1->echoJson($resp); }    
    
    if(!$resp['success']) $resp['error']=$obBD_conexion_del1->MsgError;
    $obBD_con1->echoJson($resp);
}
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Areas Cargos Modificar [EXA]"; ?></TITLE>
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
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar &Aacute;reas - Cargos</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-md-12">
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <div class="col-md-6">
                                <div class="panel panel-success exa-panel">
                                    <div class="panel-heading"><i class="fa fa-list-ol"></i>&nbsp;&nbsp;<span>&Aacute;rbol de &aacute;reas</span>
                                        <div class="pull-right">                                       
                                            <button type="button" id="btn_are" onclick="$('#dialogArea').dialog('open');" class="btn btn-success btn-xs" style="display: none;"><span class="fa fa-plus"></span><b> Modificar &Aacute;rea</b></button>
                                            <button type="button" id="btn_dep" onclick="$('#dialogDepar').dialog('open');$('#Dep_Des').focus();" class="btn btn-success btn-xs" style="display: none;"><span class="fa fa-plus"></span><b> Modificar Departamento</b></button>
                                            <button type="button" id="btn_sud" onclick="$('#dialogSubdepar').dialog('open');$('#Dep_Sud').focus();" class="btn btn-success btn-xs" style="display: none;"><span class="fa fa-plus"></span><b> Modificar Subdepartamento</b></button>
                                            <button type="button" id="btn_car" onclick="" class="btn btn-success btn-xs btn-add btn-P" style="display: none;"><span class="fa fa-plus"></span><b> Modificar Cargo</b></button>
                                            <button type="button" id="btn_eli" onclick="$.createDialogConfirm('¿Está seguro que desea eliminar'+campo+'?',null,anular);" class="btn btn-danger btn-xs" style="display: none;"><span class="fa fa-trash-o"></span><b> Eliminar</b></button>
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
                                    <legend class="Titulos2">Modificar cargo</legend>
                                    <form id="formCargo" name="formCargo" class="form-horizontal normal" action="javascript:save('formCargo');">
                                        <!--C�digo del cargo-->
                                        <input type="hidden" id="Tic_Cod" name="Tic_Cod">
                                        <input type="hidden" name="tipo" value="C"/>
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
                                            <label class="control-label col-md-3 label-xs required">Cargo:</label>
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
                                                <button type="button" id="bto_can" name="bto_can" onclick="limpiar(true);$('#dep_des').val('');$('#Tic_Des').val('');$('#Tic_Per').summernote('reset');$('#using_json_2').jstree(true).deselect_all();" class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
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
        
        <div id="dialogArea" title="Modifiicar datos &aacute;rea"> 
            <div class="row">
                <div class="form-horizontal normal col-sm-12" >
                    <fieldset>
                        <legend><label class="Titulos2">Datos del &Aacute;rea</label></legend>
                        <form id="formArea" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,'formArea',save)">
                            <input type="hidden" id="Are_Cod_A" name="Are_Cod_A">
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
        <div id="dialogDepar" title="Modificar datos departamento"> 
            <div class="row">
                <div class="form-horizontal normal col-sm-12" >
                    <fieldset>
                        <legend><label class="Titulos2">Datos del Departamento</label></legend>
                        <form id="formDepar" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,'formDepar',save)">
                            <input type="hidden" id="Dep_Cod_D" name="Dep_Cod_D">
                            <input type="hidden" name="tipo" value="D"/>
                            <div class="form-group">
                                <label class="col-sm-3 control-label label-sm required">&Aacute;rea:</label>  
                                <div class="col-sm-7" >
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
        <div id="dialogSubdepar" title="Modificar datos subdepartamento"> 
            <div class="row">
                <div class="form-horizontal normal col-sm-12" >
                    <fieldset>
                        <legend><label class="Titulos2">Datos del Departamento</label></legend>
                        <form id="formSubdepar" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,'formSubdepar',save)">
                            <input type="hidden" name="tipo" value="SD"/>
                            <input type="hidden" id="Dep_Cod_SD" name="Dep_Cod_SD">
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
                /*Llamo a la funci�n updateTipoActivo desde aqui para que cargue el jstree al momento de cargar la p�gina*/
                updateTipoActivo();
                limpiar(true);
                $.createDialog('#dialogArea',200,450);
                $.createDialog('#dialogDepar',230,450);
                $.createDialog('#dialogSubdepar',250,450);
                //Secci�n inicializar el textarea como editor de texto
                $('#Tic_Per').createWYSIWYG({height: 248});
                $('#btn_car').click(function(){
                    limpiar(false);$('#Tic_Des').focus();
                });
            });
            
            //Variable para manejo del arbol jstree
            var $treeview=$('#using_json_2'),campo='',cod_eli=0,type='';     

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
                            
                            type=data.node.type;$("[id^='btn_']").hide();$('#btn_eli').show();$("#Tic_Per").summernote('reset');limpiar(true);$('#dep_des').val('');$('#Tic_Des').val('');
                            
                            if(type==='A'){
                                $('#btn_are').show();
                                $('#Are_Des').val(data.node.text);
                                $('#Are_Cod_A').val(data.node.original.Are_Cod);
                                campo=' el &aacute;rea: <b>'+data.node.text+'</b>, y con ello todos sus departamentos y subdepartamentos';
                                cod_eli=data.node.original.Are_Cod;
                            }
                            
                            if(type==='D'){
                                $('#btn_dep').show();
                                $('#Are_Des_D').val(data.node.original.Are_Des);
                                $('#Dep_Des').val(data.node.original.Dep_Des);
                                $('#Dep_Cod_D').val(data.node.id);
                                campo=' el departamento: <b>'+data.node.original.Dep_Des+'</b>, y con ello todos sus subdepartamentos';
                                cod_eli=data.node.id;
                            }
                            
                            if(type==='SD'){
                                $('#btn_sud').show(); 
                                $('#Dep_Cod').val(data.node.id);
                                $('#Are_Des_SD').val(data.node.original.Are_Des);
                                $.post('<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING)?>',{'Dep_Cod':data.node.parent,'nombreDep':true},
                                function (response){
                                    $('#Dep_Des_SD').val(response['Dep_Des']);
                                },'json').fail(function (){$.alert('El servidor ha fallado en responder');});
                                $('#Dep_Cod_SD').val(data.node.id);
                                $('#Dep_Sud').val(data.node.original.Dep_Des);
                                campo=' el subdepartamento: <b>'+data.node.original.Dep_Des+'</b>';
                                cod_eli=data.node.id;
                            }
                            
                            if(type==='C'){
                                $('#btn_car').show();
                                var Tic_Cod=data.node.id.replace('C_','');
                                $('#Tic_Cod').val(Tic_Cod);$('#dep_des').val(data.node.original.Dep_Des);
                                $.post('<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING)?>',{'Tic_Cod':Tic_Cod,'cargo':true},
                                function(response){
                                    $("#Tic_Per").summernote('code',response['Tic_Per']);
                                    $('#Tic_Des').val(response['Tic_Des']);
                                },'json').fail(function (){$.alert('El servidor ha fallado en responder');});
                                campo=' el cargo: <b>'+data.node.text+'</b>';
                                cod_eli=Tic_Cod;
                            }
            });
            
            //Funci�n para limpiar formCargo
            function limpiar(value){
                $('#Tic_Des').attr('readonly',value);$("[id^='bto_']").attr('disabled',value);
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
                },'json').fail(function (error){$.alert();});
            }
            //Funci�n para dar de baja a un departamento y subdepartamento
            function anular(){  
            $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{eliminar:cod_eli,tipo:type}, function( response ) {
                if(response['success']===true){ 
                    $.alert("El registro se ha anulado con &eacute;xito!");
                    $treeview.jstree(true).refresh();
                }else{$.alert(response['message']);}                                   
            },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
        }
        </script>
    </BODY>
</HTML>
