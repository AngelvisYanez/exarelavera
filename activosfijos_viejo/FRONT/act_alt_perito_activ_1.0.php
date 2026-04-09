<?php	
/**
* @abstract Permite realizar el registro de un tipo de activo
* @author José Ambuludí
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
if(isset($existePerito)){ 
    $responce['data'] = $obBD_con1->getRowConsulta(601,$Prs_Ced, $obBD_conexion);
    
	/* Esta sección comprueba si el dato existe o no, si existe enviará true caso contrario false*/
	if(isset($responce['data']['Prs_Ced']))
	{
		$responce['success']=true;
	}
	else 
	{
		$responce['success']=false;
	}
	utf8_encode_deep($responce);
    echo json_encode($responce);
	exit();
}

/*Sección ajax para guardar un nuevo perito*/
if(isset($savePerito)){   
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(602, $Pri_Esp.'*'.$Pri_Obs.'*'.$Prs_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
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
                            <legend class="Titulos2">Comprobar Perito</legend>
                            <form id="comprobarForm" name="comprobarForm" class="form-horizontal normal"  action="javascript:comprobarForm();">
                            	
                                <!-- Text input y Button-->
                                <div class="form-group">
                                  	<label class="col-sm-3 control-label label-sm required" for="Prs_Ced">Buscar cédula/R.U.C.:</label>  
                                  	<div class="col-sm-4">
                                  		<input id="Prs_Ced" name="Prs_Ced" type="text" placeholder="" class="form-control input-sm bold" onkeypress="return validar_numeric(event);" required/>
                                  	</div>
                                    <div class="col-sm-5">
                                      <button id="btnComprobar" type="submit" class="btn btn-success fileinput-button btn-sm" title="Comprobar">
                                          <i class="icon-refresh icon-white"></i>
                                          <span>Comprobar</span>
                                      </button>
                                    </div>
                                </div>
                                
                                <!-- Mostrar datos de perito -->
                                <div class="form-group">
                                	 <label class="col-sm-3 control-label label-sm">Datos del Perito:</label>
                                	 <div id="datosPerito" class="col-sm-4" style="font-size:11px; font-weight:bold;">
                                     	Sin índice de búsqueda
                                     </div>
                                </div>
                            </form>
                        </fieldset>
                    </div>
                </div>
                
                <!-- Registro de datos del peritaje -->
                <div class="row">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6">    
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <form id="formPerito" class="form-horizontal normal"  action="javascript:saveForm();"  >
                                                    
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm required" for="Pri_Esp">Especialidad:</label>  
                                  <div class="col-sm-4">
                                    <input id="Pri_Esp" name="Pri_Esp" class="form-control input-sm bold" placeholder="" type="text" required/>
                                    <input type="hidden" id="Prs_Cod" name="Prs_Cod"/>
                                  </div>
                                </div>
                                
                                <!-- Textarea -->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label" for="Pri_Obs">Observaci&oacute;n:</label>
                                  <div class="col-sm-9">                     
                                    <textarea class="form-control" id="Pri_Obs" name="Pri_Obs"></textarea>
                                  </div>
                                </div>
                                
                            	<!--Boton-->
                            	<div class="form-group">
                                    <label class="col-sm-3 control-label">Acci&oacute;n:</label>
                                    <div class="col-sm-9">
                                        <button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Agregar</button>
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
        </div>
    </div>
             
   <script type="text/javascript">
   
   /*Función para registrar un perito*/
   function saveForm(){
	 if($('#Prs_Ced').val()=='')
	 {
		 $.alert('Ingrese número de cédula o R.U.C.');
	 }
	 else
	 {
		 $.post("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",
		 $('#formPerito').getData('savePerito'), 
		 function(response){	
			if(response['success']===true){
				$('#formPerito')[0].reset();
				$('#comprobarForm')[0].reset();
				$('#datosPerito').html('Sin índice de búsqueda');
				$.alert("El Registro se ha Guardado con Exito!");
			}else{$.alert("No se logro guardar el Registro!");}
		 },'json').fail(function(error) {$('#formPerito')[0].reset();$.alert("El Servidor ha fallado en responder!");});
	 }
   }
   
   
   /*Función para comprobar de que un perito existe o no*/
   function comprobarForm(){  
   	 $('#datosPerito').html('Sin índice de búsqueda');
     if($('#Prs_Ced').val()=='')
	 {
		 $.alert('Ingrese número de cédula o R.U.C.');
	 }
	 else
	 {
	  var data=$('#comprobarForm').serializeObject();
	  data["existePerito"]=true;
	             
	  $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function(response) {
		  
		  if(response['success']==true){
			  $('#datosPerito').html('Cédula: '+response['data']['Prs_Ced']+'<br/>Nombres: '+response['data']['Prs_Nom']+'<br/>Apellidos: '+response['data']['Prs_Ape']+'<br/>Ciudad: '+response['data']['Ciu_Des']);
			  $('#Prs_Cod').val(response['data']['Prs_Cod']);                          
		  }
		  else
		  {
			  $('#datosPerito').html('Datos del perito no existen');
		  }
	   },'json').fail(function(error) { $.alert();}); 
	 }
    }
   
   </script>
</BODY>
</HTML>