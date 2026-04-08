<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por lotes
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_emp.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Emp($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Emp;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($saveEmpresa)){
    $data=filter_input_array(INPUT_POST); 
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion";
    $obBD_master = new Class_Log_Conexion_Emp("exa_master");
    $obBD_con1->inicio_transaccion($obBD_master->conexion);
    $obBD_con1->grabarv_registros(sentencias_emp(1,$obBD_con1->parametros($data)), $obBD_master->conexion); 
    $data['ultimo'] = $obBD_con1->insercionid($obBD_master->conexion);    
    $obBD_con1->grabarv_registros(sentencias_emp(2,$obBD_con1->parametros($data)), $obBD_master->conexion); 
    $obBD_con1->fin_transaccion_nomsn($obBD_master->conexion);
    if($obBD_con1->Error==0){        
        $obBD_child = new Class_Log_Conexion_Emp($data['base']);
        $obBD_con1->inicio_transaccion($obBD_child->conexion);
        $obBD_con1->grabarv_registros(sentencias_emp(3,$obBD_con1->parametros($data)), $obBD_child->conexion); 
        $obBD_con1->fin_transaccion_nomsn($obBD_child->conexion);
        if($obBD_con1->Error==0){ 
            $responce['success']=true; 
			$responce['Emp_Cod']=$data['ultimo'];
            mkdir("../../tesoreria/FRONT/SRI/$data[ultimo]", 0777);
            mkdir("../../facturacion/FRONT/$data[ultimo]", 0777);
            mkdir("../../imagenes/$data[ultimo]", 0777);
        }
    }
    echo json_encode($responce);
    exit();
}

if(isset($uploadImg)){
    $responce['success']=false;
    $mensaje="No se ha seleccionado una Imagen.!";
    //if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
    {        
        $archivo = $_FILES['archivo']['name'];
        $nombre=explode('.',$archivo);  
        $last=count($nombre)-1;
        $nuevo=preg_replace('([^A-Za-z0-9])', '', $nombre[0]).'-'.$hoy.'.'.$nombre[$last];
        //extensive suitability check before doing anything with the file...
               if (($_FILES['archivo'] == "none") OR (empty($_FILES['archivo']['name'])) )
               {  $message = "No se ha encontrado ningun archivo.";}
                    else if ($_FILES['archivo']["size"] == 0)
                    {$message = "El archivo tiene un tamaño de <b>0Kb.<b>!";}
                        else if (($_FILES['archivo']["type"] != "image/jpeg") && ($_FILES['archivo']["type"] != "image/png") )
                            {$message = "El Archivo debe ser una <b><i>Imagen</i></b>.";}
                                else if (!is_uploaded_file($_FILES['archivo']["tmp_name"]))
                                {$message = "You may be attempting to hack our server. We're on to you; expect a knock on the door sometime soon.";}
                                    else {                                      
                                      $move = @ move_uploaded_file($_FILES['archivo']['tmp_name'], "../../imagenes/".$nuevo);
                                      if(!$move){$message = "Error al subir el Archivo.";}
                                      else{$responce['success']=true;$responce['name']=$nuevo;$message="";}
                                    }
    }
    
//    $explode_name = explode('.',$_FILES['archivoCsv']['name']);
//    if($explode_name[1] == 'jpg'||$explode_name[1] == 'JPG'||$explode_name[1] == 'png'||$explode_name[1] == 'PNG'
//            ||$explode_name[1] == 'gif'||$explode_name[1] == 'GIF'||$explode_name[1] == 'jpeg'||$explode_name[1] == 'JPEG'){
//        
//
//    }
//    else{$responce['message']="El archivo debe ser una <b><i>imagen</i></b>";}   
    $responce['message']=$mensaje;
    echo json_encode($responce);
    exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Empresa Crear [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?> 
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <style>
                    
                </style>
	</HEAD>
<BODY>
<div id="set1">
    <form id="empForm" action="javascript:$.createDialogConfirm(null,null,saveForm)" enctype="multipart/form-data" > 
        <input type="hidden" name="Emp_Cod" value="" />
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table" style="table-layout:fixed;">
	<tr class="BarraTitulo">
            <td colspan="2" height="10">&raquo; Registrar Empresa</td>
        </tr>
        <tr>
            <td valign="top">             
             
             <fieldset>
		<legend>
                    <label class="Titulos2">Datos Empresa</label>
		</legend>                   
                    <div>
                        <div class="segmento"><span class="Asterisco">*</span>C&eacute;dula/R.U.C.:</div>                          
                        <div class="datasegmento"><input name="ruc" minlength="10" maxlength="13" type="text" class="text ui-corner-all" autofocus required /></div>
                    </div>
                    <div>
                        <div class="segmento"><span class="Asterisco">*</span>Razon Social:</div>
                        <div  class="datasegmento"><input name="nom" maxlength="50" type="text" class="text ui-corner-all" required  /></div>
                    </div>
                    <div>
                        <div class="segmento"><span class="Asterisco">*</span>Razon Social Abreviada:</div>
                        <div  class="datasegmento"><input name="abr" maxlength="30" type="text" class="text ui-corner-all" required style="width:50%;" /></div>
                    </div>
                    <div>
                        <div class="segmento">Registro:</div>
                        <div  class="datasegmento"><input name="reg" maxlength="20" type="text" class="text ui-corner-all"  /></div>
                    </div>
             </fieldset> 
             <fieldset>
		<legend>
                    <label class="Titulos2">Representante Legal</label>
		</legend>                   
                    <div>
                        <div class="segmento"><span class="Asterisco">*</span>C&eacute;dula/R.U.C.:</div>                          
                        <div class="datasegmento"><input name="ruc_rep" minlength="10" maxlength="13" type="text" class="text ui-corner-all"  required /></div>
                    </div>
                    <div>
                        <div class="segmento"><span class="Asterisco">*</span>Apellidos y Nombres:</div>
                        <div  class="datasegmento"><input name="nom_rep" maxlength="50" type="text" class="text ui-corner-all" required  /></div>
                    </div>
             </fieldset>
             <fieldset>
		<legend>
                    <label class="Titulos2">Contador de la Empresa</label>
		</legend>                   
                    <div>
                        <div class="segmento"><span class="Asterisco">*</span>C&eacute;dula/R.U.C.:</div>                          
                        <div class="datasegmento"><input name="ruc_con" minlength="10" maxlength="13" type="text" class="text ui-corner-all"  required /></div>
                    </div>
                    <div>
                        <div class="segmento"><span class="Asterisco">*</span>Apellidos y Nombres:</div>
                        <div  class="datasegmento"><input name="nom_con" maxlength="50" type="text" class="text ui-corner-all" required  /></div>
                    </div>
             </fieldset>
             <fieldset>
		<legend>
                    <label class="Titulos2">Base de Datos</label>
		</legend>                   
                    <div>
                        <div class="segmento"><span class="Asterisco">*</span>Nombre Base de Datos:</div>                          
                        <div class="datasegmento"><input name="base" maxlength="30" type="text" class="text ui-corner-all clearable x onX" value="servicios"  required style="width:50%;" /></div>
                    </div> 
                </fieldset>          
               <div align="center" style="padding:10px;">	                                
                        <button type="subtmit" class="btn btn-success" title="Guardar Empresa" > <i class="icon-book icon-white"></i> <span>Guardar</span> </button>
                    </div>
            </td>
            <td valign="top">                
                <fieldset>
		<legend>
                    <label class="Titulos2">Logo de la Empresa</label>
		</legend>                        
                     <div>
                        <div class="segmento">Logo:</div>                          
                        <div class="datasegmento"><input type="file" name="archivo" class="text" accept="image/jpeg, image/png" /></div>
                    </div>
                    <div align="center" style="padding-top:5px;">	                                
                        <button type="button" class="btn btn-success" title="Subir Imagén" onclick="LoadImg()"> <i class="icon-upload icon-white"></i> <span>Subir Imagén</span> </button>
                    </div>
                     <div align="center" style="padding-top:5px;">
                        <input id="nom_img" name="nom_img" type="hidden" readonly />                          
                        <img id="logo" src="" style="height: auto;width: 35%;" />
                    </div>
                </fieldset>
            </td>
        </tr>      
    </table>
    </form>   	
</div>
    <script>
        function LoadImg(){	                            
                var formData = new FormData(document.getElementById("empForm"));
                formData.append("uploadImg", true);                
                $.ajax({
                    url: "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",
                    type: "post", dataType: "json", data: formData, cache: false, contentType: false, processData: false
                }).done(function(response){
                    if(response['success']===true){
                        $("#nom_img").val(response['name']);var d = new Date();
                        $("#logo").attr("src",'../../imagenes/'+response['name']+"?time="+d.getTime());
                    }else{$.alert(response['message']);}                                  
                }).fail(function(error) { $.alert(); });                              
        }   
		
        function saveForm(){
            var data=$('#empForm').serializeObject();
            data["saveEmpresa"]=true;
            $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( response ) {
                if(response['success']===true){
                    $.alert("Transaccion Realizada con &Eacute;xito!");                          
                    $('#empForm')[0].reset();$("#logo").attr("src",'');
                }else{$.alert(response['message']);}
             },'json').fail(function(error) { $.alert();});
        }
    </script>

</BODY>
</HTML>