<?php	
/**
* @abstract Permite realizar el registro de un tipo de poliza
* @author Jos� Ambulud�
* @version 1.0
* Fecha de creaci�n  2016-06-03
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_tipo_poliz.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Tipo_Poliz($Ses_Dat_Dis);
  
/**
* objeto para consultas
* @var Class_Log_Datos_Tes
*/
$obBD_con1 =  new Class_Log_Datos_Tipo_Poliz;


if(isset($save)){ 
    $data=filter_input_array(INPUT_POST);
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(2,$data, $obBD_conexion);  
    
    $responce['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    $responce['message']=$obBD_con1->MsgError;
    
    echo json_encode($responce);exit();
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
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registro de Tipos de Poliza</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <form id="formTipo_Poliza" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,null,saveForm);"  >
                <div class="row">
                	<div class="col-sm-3"></div>
                    <div class="col-sm-6">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Datos a registrar</legend> <!-- Form Name -->
                            	<!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm required" for="Tip_Des">Nombre:</label>  
                                  <div class="col-sm-4">                                    
                                  	<input id="Tip_Des" name="Tip_Des" class="form-control input-sm" placeholder="Tipo de poliza" type="text" required >                                                                             
                                  </div>                                 
                                </div>
                                
								<!-- Textarea -->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label" for="des_cuenta">Observaci�n:</label>
                                  <div class="col-sm-9">                     
                                    <textarea class="form-control" id="Tip_Obs" name="Tip_Obs"></textarea>
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
                $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",$('#formTipo_Poliza').getData('save'), 	    		function(response){	
                    if(response['success']==true){
                        $('#formTipo_Poliza')[0].reset();						
                        alert("El Registro se ha Guardado con Exito!");
                    }else{alert("No se logro guardar el Registro!");}
					$('.btn-form').removeAttr('disabled');
                },'json').fail(function(error) {$('#formTipo_Poliza')[0].reset();$('.btn-form').removeAttr('disabled');$.alert("El Servidor ha fallado en responder!");});
       }
   </script>
</BODY>
</HTML>