<?php	
/**
* @abstract Permite realizar el registro de un tipo de mantenimiento
* @author José Ambuludí
* @version 1.0
* Fecha de creación  2016-06-03
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_tipo_mante.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Tipo_Mante($Ses_Dat_Dis);
  
/**
* objeto para consultas
* @var Class_Log_Datos_Tes
*/
$obBD_con1 =  new Class_Log_Datos_Tipo_Mante;


if(isset($save)){ 
    
	$data=filter_input_array(INPUT_POST);
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(2,$data, $obBD_conexion);  
    
	/*$responce['success'] recive true o false, si la transacción se cierra correctamente se recibe true*/
	$responce['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
	/*Mensaje de error en caso de un error de base de datos*/
	$responce['message']=$obBD_con1->MsgError;
	
    echo json_encode($responce);
	exit();
}


?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
    	<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registro de Tipos de Mantenimiento</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <form id="formTipo_Mante" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,null,saveForm);"  >
                <div class="row">
                	<div class="col-sm-3"></div>
                    <div class="col-sm-6">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Datos a registrar</legend> <!-- Form Name -->
                            	<!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm required" for="Tma_Des">Nombre:</label>  
                                  <div class="col-sm-4">                                    
                                  	<input id="Tma_Des" name="Tma_Des" class="form-control input-sm" placeholder="Tipo de mantenimiento" type="text" required >                                                                             
                                  </div>                                 
                                </div>
                                
								<!-- Textarea -->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label" for="des_cuenta">Observaci&oacute;n:</label>
                                  <div class="col-sm-9">                     
                                    <textarea class="form-control" id="Tma_Obs" name="Tma_Obs"></textarea>
                                  </div>
                                </div>
                        </fieldset>
                    </div>
                    
                    <div class="col-sm-12">
                            <div class="form-group">  
                                <div class="col-sm-12 center">
                                    <button type="submit"  class="btn btn-primary btn-form"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>                               
                                </div>
                            </div>
                            <div class="form-group Titulos2">
                                <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                            </div>    
                    </div>
                </div>    
              
            </form>   
        </div>
    </div>

   <script type="text/javascript">
		function saveForm(){
			   $('.btn-form').attr('disabled','disabled');
                $.post("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",$('#formTipo_Mante').getData('save'), 	    		function(response){	
                    if(response['success']==true){
                        $('#formTipo_Mante')[0].reset();						
                        alert("El Registro se ha Guardado con Exito!");
                    }else{alert("No se logro guardar el Registro!");}
					$('.btn-form').removeAttr('disabled');
                },'json').fail(function(error) {$('#formTipo_Mante')[0].reset();$('.btn-form').removeAttr('disabled');$.alert("El Servidor ha fallado en responder!");});
       }
   </script>
</BODY>
</HTML>
