<?php	
/**
* @abstract Permite
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2016-11-24
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_elect.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
//require_once('../../Librerias/FactElect/FirmaElectronica.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_FacEle($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_FacEle;

$hoy = date("Y-m-d");
$mes = date("m");
$ruta_xmls=$APP_REAL_PATH."/facturacion/FRONT/$Ses_Emp_Cod/";

//require_once('../LOGICA/fac_log_electronica.php');
//$obBD_con2 =  new Class_Log_Datos_Factura_Elect;
//echo $obBD_con2->getClaveAcceso(3760,'2018-04-23',1,$obBD_conexion);

if(isset($saveLlave) && $tipoLLamada=="guardar"){
    $resp=array('success'=>true );    
    $file_name=basename($_FILES["fileToUpload"]["name"]);
    $target_file = $ruta_xmls.$file_name;    
    $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
    
    // Check if file already exists
    if (file_exists($target_file)) unlink($target_file);    
    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 500000) $obBD_con1->echoJson(array('success'=>false, 'message'=>'El archivo es demasiado grande!' ));  
    // Allow certain file formats
    if($imageFileType != "p12" && $imageFileType != "P12" ) $obBD_con1->echoJson(array('success'=>false, 'message'=>'Solo se aceptan archivos tipo [.p12]!' ));      
    // if everything is ok, try to upload file    
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        $llave=array('Emp_Cod'=>$Ses_Emp_Cod, 'Lla_Rut'=>$file_name, 'Lla_Cla'=>$Lla_Cla, 'Lla_Cad'=>$Lla_Cad);
        $obBD_con1->inicio_transaccion($obBD_conexion);
            $obBD_con1->operacionobBD(3, $Ses_Emp_Cod.'*'.'I', $obBD_conexion); // inactiva otras llaves
            $obBD_con1->operacionobBD(4, $llave, $obBD_conexion); // registra llave
        $obBD_con1->echoJson(array('success'=>$obBD_con1->fin_transaccion_nomsn($obBD_conexion), 'guardar'=>true));             
    } else $obBD_con1->echoJson(array('success'=>false, 'message'=>'No se pudo subir el archivo!' ));  
}
if(isset($saveLlave) && $tipoLLamada=="revisar"){
	$resp=array('success'=>true );  
    $target_file = $ruta_xmls."revision_p12_prueba.p12";    
    $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
    
    // Check if file already exists
    if (file_exists($target_file)) unlink($target_file); 
	// Check file size
    if ($_FILES["fileToUpload"]["size"] > 500000) $obBD_con1->echoJson(array('success'=>false, 'message'=>'El archivo es demasiado grande!' ));  
    // Allow certain file formats
    if($imageFileType != "p12" && $imageFileType != "P12" ) $obBD_con1->echoJson(array('success'=>false, 'message'=>'Solo se aceptan archivos tipo [.p12]!' ));      
    // if everything is ok, try to upload file    
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
		require_once('../../Librerias/FactElect/FirmaElectronica.php');
        $firma = new FirmaElectronica();
		$firma->setKey($target_file,$Lla_Cla);
		unlink($target_file); 
		$x509=$firma->getKeyData();
		if($x509==null) $obBD_con1->echoJson(array('success'=>false, 'message'=>'No se pudo obtener datos del archivo, revise la contraseña o el archivo!' ));  
		$ext=array();
		$i=0;
		foreach($x509['extensions'] AS $k=>$v){
			if(strlen($k)>3 && substr($k, 0, 3)=="1.3"){
				$ext[substr($k, 0, 3).".".$i]=str_ireplace(array(chr(13).chr(10), "\r\n", "\n", "\r"),array(" ", " ", " ", " "),trim($v));
				$i++;
			}
		}
		$success=array('success'=>true, 'revisar'=>true, 'creacion'=>date('Y-m-d H:i:s', $x509['validFrom_time_t']),'caducidad'=>date('Y-m-d H:i:s', $x509['validTo_time_t']), 'entidad'=>$x509['issuer']['O'] , 'issuer'=>$x509['issuer'], 'subject'=>$x509['subject']['CN'], 'creation_city'=>$x509['subject']['L'], 'serialNumber'=>$x509['serialNumber'], 'extensions'=>sprintf("%s",print_r($ext,TRUE)));
        $obBD_con1->echoJson($success);             
    } else $obBD_con1->echoJson(array('success'=>false, 'message'=>'No se pudo subir el archivo!' )); 
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Firma Electr.Registrar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?> 
    <style>  
    </style>
</HEAD>
<BODY> 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Firma Digital</h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
           
                <div class="row">   
                    <form id="formLlave" class="form-horizontal normal" action="javascript:if($('#tipo').val()==='guardar') $.createDialogConfirm('¿Esta seguro que desea guardar la llave electrónica?',null,sendLlave); else sendLlave();" method="post" enctype="multipart/form-data">
                    <input type='hidden' name='saveLlave' value='true' />
					<input type='hidden' id="tipo" name='tipoLLamada' value='' />
                    <div class="col-xs-3"></div> 
                    <div class="col-xs-6">  
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Datos Llave Electrónica</legend>                            
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-sm required">Archivo:</label>  
                                <div class="col-xs-9"> 
                                    <input type="file" name="fileToUpload" accept=".p12,.P12" placeholder="Ingrese Archivo .P12.." value="" class="form-control input-sm" required="" />
                                </div>                                  
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-sm required">Clave:</label>  
                                <div class="col-xs-4"> 
                                    <input type="password" name="Lla_Cla" placeholder="Ingrese Clave.." value="" class="form-control input-sm" required="" />
                                </div>                                  
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-sm required">Caducidad:</label>  
                                <div class="col-xs-4"> 
                                    <input type="text" id="Lla_Cad" name="Lla_Cad" placeholder="Ingrese Caducidad.." value="" class="form-control input-sm" required="" />
                                </div>                                  
                            </div>                                                                 
                            
                            <div class="form-group center">
                                <br/>
								<button id="btnRevisar" type="button" onclick="$('#tipo').val('revisar'); $('#formLlave').formSubmit();" class="btn btn-primary btn-save"><span class="glyphicon glyphicon-floppy-disk"></span> Revisar Datos</button>
								<button id="btnGuardar" type="button" onclick="$('#tipo').val('guardar'); $('#formLlave').formSubmit();" class="btn btn-success btn-save"><span class="glyphicon glyphicon-floppy-disk"></span> Guarda LLave</button>
                            </div> 
                            <div class="col-xs-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( &nbsp;<span class="required"></span> ) son campos obligatorios.</div>
                        </fieldset>
                    </div> 
                    </form>                      
                </div>
                <div class="row">   
                                       
                </div>   
            
            
        </div>
    </div>
    <script type="text/javascript">
        $(function() {
            $('#Lla_Cad').createDatePickers();
			$('#certForm').createDialog({
				height:490, width:500, icon:'eye'
			});
        });
       
        function sendLlave(){           
            $('#loader').show();
            var formData = new FormData($('#formLlave')[0]);           
            $.ajax({
                url: window.location.pathname, type: 'POST', data: formData, dataType: "json", async: true, cache: false, contentType: false, processData: false
            }).done(function (re){
                if(re.success===true){
					if(re.guardar===true)
						$.alert('La llave electrónica se ha registrado con Éxito!');
					else{
						if(re.subject!=null){
							$('#Lla_Cad').val(re.caducidad.substring(0, 10));
							$('#certForm').setData(re,false);
							$('#datos').html(re.extensions);
							$('#certForm').dialog('open');
						}else $.alert("No se encontro información.<br/>Revise la contraseña o el archivo");
					}
                }else $.alert('No se pudo realizar la accion!');     
            }).fail(function (){ $.alert(); }).always(function (){ $('#loader').hide(); });
        }
       
    </script>
	<div id="certForm" title="Datos Certificado">
		<form id="certificado" class="form-horizontal normal">
			<fieldset class="exa-fieldset ">                           
                <legend class="Titulos2">Datos Certificado</legend>
				<div class="form-group">
					<label class="col-xs-3 control-label label-xs">Entidad:</label>  
					<div class="col-xs-9"><span name='entidad' class="form-control input-xs"><span> </div>                                  
				</div> 
				<div class="form-group">
					<label class="col-xs-3 control-label label-xs">Serial:</label>  
					<div class="col-xs-9"> <span name='serialNumber' class="form-control input-xs"><span> </div>                                  
				</div> 
				<div class="form-group">
					<label class="col-xs-3 control-label label-xs">Sujeto:</label>  
					<div class="col-xs-9"> <span name='subject' class="form-control input-xs"><span> </div>                                  
				</div> 
				<div class="form-group">
					<label class="col-xs-3 control-label label-xs">Lugar&nbsp;Creación:</label>  
					<div class="col-xs-9"> <span name='creation_city' class="form-control input-xs"><span> </div>                                  
				</div> 
				<div class="form-group">
					<label class="col-xs-3 control-label label-xs">Creación:</label>  
					<div class="col-xs-9"> <span name='creacion' class="form-control input-xs"><span> </div>                                  
				</div> 
				<div class="form-group">
					<label class="col-xs-3 control-label label-xs">Caducidad:</label>  
					<div class="col-xs-9"> <span name='caducidad' class="form-control input-xs"><span> </div>                                  
				</div> 
				<div class="form-group">
					<div class="col-xs-12"> 
						<pre id="datos" style="height:260px; margin-bottom:0;"></pre>
					</div>
				</div>
			</fieldset>	 
		</form>
    </div>
</BODY>
</HTML>