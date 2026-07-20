<?php	
/**
* @abstract Permite realizar el registro de un perito
* @author Jos� Ambulud�
* @version 1.0
* Fecha de creaci?n  2016-06-03
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_perito.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Per($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Per;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

/* Comprueba de que el perito exista o no */
if(isset($existePersona)){ 
    $responce['data'] = $obBD_con1->getRowConsulta(601,$Prs_Ced, $obBD_conexion);
    $perito=$obBD_con1->getRowConsulta(605,$Prs_Ced, $obBD_conexion);
    
    /* Se obtiene el Ide_Cod basandonos en la longitud de la cadena ingresada (cedula,ruc,pasaporte,etc)*/
    $longitud=strlen($Prs_Ced);
    $responce['identificacion'] = $obBD_con1->getRowConsulta(604,$longitud, $obBD_conexion);
    
    if(isset($perito['Pri_Cod']))
    {
        $responce['perito']=true;
    }
    else
    {
        $responce['perito']=false;
    }
    
    /* Esta secci�n comprueba si la persona existe o no, si existe enviar� true caso contrario false*/
    if(isset($responce['data']['Prs_Ced']))
    {
        $responce['persona']=true;
    }
    else 
    {
        $responce['persona']=false;
    }
    echo json_encode($responce);
    exit();
}

/*Secci�n ajax para guardar un nuevo perito*/
if(isset($savePerito)){   
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    if($Ide_Cod>0)
    {
        /*En caso de la persona no estar registrada primero la registra y luego inserta en la tabla perito*/
        $obBD_con1->operacionobBD(606, $Prs_Ced.'*'.$Prs_Nom.'*'.$Prs_Ape.'*'.$Prs_Sex.'*'.$Ciu_Cod.'*'.$Prs_Dir.'*'.$Prs_Tel.'*'.$Prs_Cel.'*'.$Ide_Cod, $obBD_conexion);
        /*Secci�n para obtener el c�digo de la �ltima inserci�n en la tabla persona*/
        $Prs_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(602, $Pri_Esp.'*'.$Pri_Obs.'*'.$Prs_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
    }
    else
    {
        $obBD_con1->operacionobBD(602, $Pri_Esp.'*'.$Pri_Obs.'*'.$Prs_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
    }
    
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; }  
    echo json_encode($responce);
    exit();
}
?>

<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Perito</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
            	<div class="row">
                	<div class="col-sm-3"></div>
                    <div class="col-sm-6">
                    	<fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <form id="FormPerito" name="FormPerito" class="form-horizontal normal"  action="javascript:saveForm();">
                                <input type="hidden" id="Prs_Cod" name="Prs_Cod"/>
                                <input type="hidden" id="Ide_Cod" name="Ide_Cod"/>
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>
                                <!-- Text input y Button-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Prs_Ced">Buscar C&eacute;dula/R.U.C.:</label>  
                                    <div class="col-sm-6">
                                        <div class="input-group input-group-sm">                                                  
                                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control" placeholder="Ingresar Informaci&oacute;n" required />
                                            <span class="input-group-btn">
                                                <button class="btn btn-success" type="button" onclick="comprobarForm()"><span class="glyphicon glyphicon-refresh" title="Buscar Perito"></span> Comprobar</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Secci�n para visualizar el tipo de documento -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm">Tipo de Documento:</label>
                                    <div class="col-sm-6">
                                            <input id="Ide_Des" name="Ide_Des" class="form-control input-sm" placeholder="" type="text" readonly/>
                                    </div>
                                </div>
                                
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm required" for="Prs_Nom">Nombres:</label>  
                                  <div class="col-sm-6">
                                    <input id="Prs_Nom" name="Prs_Nom" class="form-control input-sm" placeholder="" type="text" required/>
                                  </div>
                                </div>
                                
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm required" for="Prs_Ape">Apellidos:</label>  
                                  <div class="col-sm-6">
                                    <input id="Prs_Ape" name="Prs_Ape" class="form-control input-sm" placeholder="" type="text" required/>
                                  </div>
                                </div>
                                
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm required" for="Prs_Sex">Genero:</label>  
                                  <div class="col-sm-6">
                                    <select id="Prs_Sex" name="Prs_Sex" class="form-control input-sm" required>
                                      <option value="M">MASCULINO</option>
                                      <option value="F">FEMENINO</option>
                                    </select>
                                  </div>
                                </div>
                                
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm required" for="Ciu_Cod">Ciudad:</label>  
                                  <div class="col-sm-6">
                                    <?php $row_rs_ciudad = $obBD_con1->getArrayConsulta(603, $Ses_Emp_Cod, $obBD_conexion); 		?>
                                    <select name="Ciu_Cod" id="Ciu_Cod" class="form-control input-sm" data-placeholder="Seleccione una ciudad..">
                                      <option value=""></option>
                                      <?Php                                         
                                      foreach($row_rs_ciudad as $row)
                                            { ?>
                                      <option value="<?Php echo $row['Ciu_Cod'];?>" ><?Php echo $row['Ciu_Des'];?></option>
                                      <?Php 
                                            } ?>
                                    </select>
                                  </div>
                                </div>
                                
                                <!-- Textarea -->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label" for="Prs_Dir">Direcci&oacute;n:</label>
                                  <div class="col-sm-7">                     
                                    <textarea class="form-control" id="Prs_Dir" name="Prs_Dir"></textarea>
                                  </div>
                                </div>
                                
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm" for="Prs_Tel">Tel&eacute;fono:</label>  
                                  <div class="col-sm-3">
                                    <input id="Prs_Tel" name="Prs_Tel" class="form-control input-sm" type="text"/>
                                  </div>
                                  
                                  <label class="col-sm-1 control-label label-sm" for="Prs_Cel">Celular:</label>  
                                  <div class="col-sm-3">
                                    <input id="Prs_Cel" name="Prs_Cel" class="form-control input-sm" type="text"/>
                                  </div>
                                </div>
                                
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm required" for="Pri_Esp">Especialidad:</label>  
                                  <div class="col-sm-7">
                                    <input id="Pri_Esp" name="Pri_Esp" class="form-control input-sm" placeholder="" type="text" required/>
                                  </div>
                                </div>
                                
                                <!-- Textarea -->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label" for="Pri_Obs">Observaci&oacute;n:</label>
                                  <div class="col-sm-7">                     
                                    <textarea class="form-control" id="Pri_Obs" name="Pri_Obs"></textarea>
                                  </div>
                                </div>
                                
                            	<!--Boton-->
                            	<div class="form-group">
                                    <label class="col-sm-3 control-label"></label>
                                    <div class="col-sm-9">
                                        <button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                    </div>
                                </div>
                            </form>
                        </fieldset>
                    </div>
                </div> 
            </div>   
        </div>
    </div>
             
    <script type="text/javascript">
   
    //Secci�n para el choosen
    $(document).ready(function(){
        $("#Ciu_Cod").createChosen();                
    });
    
    /*Funci�n para registrar un perito*/
    function saveForm(){
        $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",
        $('#FormPerito').getData('savePerito'), 
        function(response){	
            if(response['success']===true){
                $.alert("El Registro se ha Guardado con Exito!");			
                $('#FormPerito')[0].reset();
                $('#Ciu_Cod').val('').trigger('chosen:updated');
            }else{$.alert("No se logro guardar el Registro!");}
        },'json').fail(function(error) {$('#FormPerito')[0].reset();$.alert("El Servidor ha fallado en responder!");});
    }
      
    /*Funci�n para comprobar de que un perito existe o no*/
    function comprobarForm()
    { 
        var cedula=$('#Prs_Ced').val();
        var campo=$('#Prs_Ced').attr('id');
        var respuesta=validar_cedula(cedula,campo);
        if(respuesta===true)
        {
            var data={Prs_Ced:cedula,existePersona:true};
            $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data,function(response){
                
                if(response['perito']===true)
                {
                    $.alert('Perito ya se encuentra registrado');
                }
                else
                {
                    if(response['persona']===true)
                    {
                        $('#Prs_Cod').val(response['data']['Prs_Cod']);
                        $('#Ide_Cod').val(response['identificacion']['Ide_Cod']);
                        $('#Ide_Des').val(response['identificacion']['Ide_Des']);
                        $('#Prs_Nom').val(response['data']['Prs_Nom']);
                        $('#Prs_Ape').val(response['data']['Prs_Ape']);
                        $('#Prs_Sex').val(response['data']['Prs_Sex']);
                        $('#Ciu_Cod').val(response['data']['Ciu_Cod']).trigger("chosen:updated");
                        $('#Prs_Dir').val(response['data']['Prs_Dir']);
                        $('#Prs_Tel').val(response['data']['Prs_Tel']);
                        $('#Prs_Cel').val(response['data']['Prs_Cel']);
                        bloquear(true);
                        $('#Pri_Esp').focus();
                    }
                    else{
                        bloquear(false);
                        $('#Ide_Cod').val(response['identificacion']['Ide_Cod']);
                        $('#Ide_Des').val(response['identificacion']['Ide_Des']);
                        $('#Prs_Nom').val('');
                        $('#Prs_Ape').val('');
                        $('#Prs_Dir').val('');
                        $('#Prs_Tel').val('');
                        $('#Prs_Cel').val('');
                        $('#Ciu_Cod').val('').trigger('chosen:updated');
                        $('#Prs_Nom').focus();
                        $.alert('Datos de la persona no existen. Registrelo llenando los campos.');
                    }
                }
            },'json').fail(function(error) { $.alert();});
        }
    }
    
    function bloquear(estado)
    {
        $('#Prs_Nom').attr('readonly',estado);
        $('#Prs_Ape').attr('readonly',estado);
        $('#Prs_Dir').attr('readonly',estado);
        $('#Prs_Tel').attr('readonly',estado);
        $('#Prs_Cel').attr('readonly',estado);
    }
   
    </script>
</BODY>
</HTML>