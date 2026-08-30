<?php	
/**
* @abstract Permite
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2016-11-24
*/
require_once('../../Librerias/config.php/register_globals.php'); 
//require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_facturaVenta;

$hoy = date("Y-m-d");
$mes = date("m");

$row_rs_proceso= $obBD_con1->getRowConsultaSql("SELECT Pcs_Cod,Pcs_Nom FROM procesos WHERE Pcs_Nom LIKE 'fac_alt_fac_ven__._.php' ORDER BY Pcs_Nom DESC LIMIT 1;", $obBD_conexion);
$row_rs_reporte= $obBD_con1->getRowConsultaSql("SELECT reportes.Rep_Cod, procesos.Pcs_Nom, reportes.Rep_Ord, rutas.Rut_Des FROM procesos
        INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req)
        INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod) 
        WHERE reportes.Pcs_Cod = $row_rs_proceso[Pcs_Cod] AND reportes.Emp_Cod = $Ses_Emp_Cod AND Rep_Ord='1';", $obBD_conexion);
if(empty($row_rs_proceso)||empty($row_rs_reporte)) die();
$ruta=explode('/',$row_rs_reporte['Rut_Des']);
$Emp_Cod=$ruta[count($ruta)-1]; if(empty($Emp_Cod))$Emp_Cod=$ruta[count($ruta)-2];
$obBD_con1->echoLog($row_rs_reporte);
if(isset($saveFields)){   
    $file="../$Emp_Cod/plantilla_{$type}_{$Ses_Emp_Cod}.json";
    $exist=file_exists($file);
    $fp = fopen($file, 'w');
    fwrite($fp, json_encode($fields)); fclose($fp);  
    if(!$exist) chmod($file, 0777);
    $obBD_con1->echoJson(array('success'=>true));
}
if(isset($saveFondo)){  
    $data=$_POST;
    $responce=array('success'=>true,'message'=>'Guia Documento actualizada con Exito!');
    $tot = isset($_FILES["imagen"])?count($_FILES["imagen"]["name"]):0;
    try {        
        $file="../$Emp_Cod/plantilla_{$type}_ima_{$Ses_Emp_Cod}.json";
        $exist=file_exists($file);
        if($tot>0&&(!empty($_FILES["imagen"]["name"][0]))){  
            $target_file="../$Emp_Cod/".basename($_FILES["imagen"]["name"]);
            $imageFileType = strtoupper(pathinfo($target_file,PATHINFO_EXTENSION));            
            $check = getimagesize($_FILES["imagen"]["tmp_name"]);
            
            if($check == false) throw new Exception('Archivo no es una imagen!');
            if (file_exists($target_file)&&!file_exists($file)) throw new Exception('Archivo ya existe!');            
            if ($_FILES["imagen"]["size"] > 4000000) throw new Exception('Archivo demasiado grande!');
            if($imageFileType != "JPG" && $imageFileType != "PNG" && $imageFileType != "JPEG" && $imageFileType != "GIF" ) throw new Exception('Formato no Permitido!');
            if (!move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) throw new Exception('Error al subir el archivo!');  
            $data['imagen_guia']=basename($_FILES["imagen"]["name"]); 
        }
        if((!isset($data['imagen_guia'])||empty($data['imagen_guia']))&&is_readable($file)){
            $template_fact_ima= json_decode(file_get_contents($file), true);
            if(isset($template_fact_ima['imagen_guia'])) $data['imagen_guia']=$template_fact_ima['imagen_guia'];
        }
        if(isset($data['imagen'])) unset($data['imagen']);
        $fp = fopen($file, 'w');
        fwrite($fp, json_encode($data)); fclose($fp);
        if(!$exist) chmod($file, 0777);
    } catch (Exception $e) {$responce['success']=false;$responce['message']= 'ERROR: '.$e->getMessage();} 
    $obBD_con1->echoJson($responce);
}
if(isset($getPlantilla)){
    if(is_readable("../$Emp_Cod/plantilla_{$type}_{$Ses_Emp_Cod}.json")){
        $template_fact= json_decode(file_get_contents("../$Emp_Cod/plantilla_{$type}_{$Ses_Emp_Cod}.json"), true);
    }else include("../IMPRIMIR/plantilla_{$type}.php");    
    if(is_readable("../$Emp_Cod/plantilla_{$type}_ima_{$Ses_Emp_Cod}.json")){
        $template_fact_ima= json_decode(file_get_contents("../$Emp_Cod/plantilla_{$type}_ima_{$Ses_Emp_Cod}.json"), true);
        $template_fact_ima['imagen_guia']="../$Emp_Cod/".$template_fact_ima['imagen_guia'];
    }else $template_fact_ima=null;
    $string='';
    foreach ($template_fact as $k => $v) {
        if($k!='item'){
            $data=" data-id='$k'  data-x='$v[x]' data-y='$v[y]' data-width='$v[width]' data-bold='".(isset($v['bold'])?$v['bold']:'')."' data-type='".(isset($v['type'])?$v['type']:'')."' data-ejemplo='$v[ejemplo]' ";
            $string.="<span id='$k' title='$k' style='top:$v[y]px;left:$v[x]px;width:$v[width]px;' class='flota".(!isset($v['type'])||empty($v['type'])?'':($v['type']=='text'?' truncate':' number'))."' $data >$v[ejemplo]</span>";
        }
    } $obBD_con1->echoJson(array('success'=>true, 'html'=>$string, 'imagen'=>$template_fact_ima ));
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?> 
    <style>  
    </style>
</HEAD>
<BODY> 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Cuadrar Documentos - <?php echo $Ses_Emp_Nom; ?></h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
           
                <div class="row">   
                    <form id="formDocsSearch">
                    <div class="col-xs-8">  
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Filtros</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-sm required">Tipo Documento:</label>  
                                    <div class="col-xs-6"> 
                                        <select id="type" name="type" class="form-control input-sm" required="" onchange="setType(this.value)">                                           
                                            <option value="fact">Ventas</option>                                            
                                            <option value="retenc">Retenciones</option>
                                            <option value="notcred">Notas de Cr�dito</option>
                                            <option value="liquida">Liquidaciones</option>
                                        </select>
                                    </div>
                                    <div class="col-xs-3">
                                        <button type="button" onclick="setPlantilla();" class="btn btn-sm btn-primary"> Cargar</button>
                                    </div>                                    
                                </div>                                                                 
                            </div>
                        </fieldset>
                    </div>                     
                    </form>
                       
                    
                </div>
            <div class="row">
                <div class="col-xs-4">
                    <form id="formCampo"  class="form-horizontal normal" > 
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Campo</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Nombre:</label>  
                                <div class="col-xs-6"> 
                                    <input id="name" name="name" type="text" value="" class="form-control input-xs" readonly=""/>
                                </div> 
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Ejemplo:</label>  
                                <div class="col-xs-10"> 
                                    <input name="ejemplo" type="text" value="" class="form-control input-xs" readonly="" />
                                </div> 
                            </div>
                            <!--<div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Negrita:</label>  
                                <div class="col-xs-6"> 
                                    <input name="bold" type="checkbox" value="S" offval="N" />
                                </div> 
                            </div>-->
                        </fieldset>    
                    </form>    
                    <form id="formItem"  class="form-horizontal normal" >     
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Posiciones</legend>
                            <input type="hidden" name="id" value="" />
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Ancho:</label>  
                                <div class="col-xs-4"> 
                                    <input id="width" name="width" type="number" onchange="updatePos()" value="" class="form-control input-xs nospin" />
                                </div> 
                            </div>    
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">X:</label>  
                                <div class="col-xs-4"> 
                                    <input id="pos_x" name="x" type="number" onchange="updatePos()" value="" class="form-control input-xs nospin" />
                                </div> 
                                <label class="col-xs-2 control-label label-xs required">Y:</label>  
                                <div class="col-xs-4"> 
                                    <input id="pos_y" name="y" type="number" onchange="updatePos()" value="" class="form-control input-xs nospin" />
                                </div> 
                            </div>
                        </fieldset>
                    </form>
                    <form id="formImage" class="form-horizontal normal" > 
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Imagen Gu�a</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-sm">Imagen:</label>  
                                <div class="col-xs-10"> 
                                    <input id="imgInp" name="imagen" type="file"  class="form-control input-sm" />
                                </div>
                            </div>  
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Tipo:</label>  
                                <div class="col-xs-4"> 
                                    <select id="ppp" class="form-control input-xs" onchange="selectPPP()">
                                        <option value="0">Normal</option>
                                        <option value="1" data-ima_width="595" data-ima_height="842" >72 PPP</option>
                                        <option value="2" data-ima_width="1240" data-ima_height="1754">150 PPP</option>
                                        <option value="3" data-ima_width="2480" data-ima_height="3508">300 PPP</option>
                                    </select>
                                </div>
                                <label class="col-xs-2 control-label label-xs">Rotar:</label>  
                                <div class="col-xs-4"> 
                                    <input id="width" name="ima_rotate" type="number" onchange="updatePosIma()" value="" class="form-control input-xs nospin" />
                                </div>
                            </div>  
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Ancho:</label>  
                                <div class="col-xs-4"> 
                                    <input id="width" name="ima_width" type="number" onchange="updatePosIma()" value="" class="form-control input-xs nospin" />
                                </div>
                                <label class="col-xs-2 control-label label-xs">Alto:</label>  
                                <div class="col-xs-4"> 
                                    <input id="width" name="ima_height" type="number" onchange="updatePosIma()" value="" class="form-control input-xs nospin" />
                                </div> 
                            </div>    
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">X:</label>  
                                <div class="col-xs-4"> 
                                    <input name="ima_x" type="number" onchange="updatePosIma()" value="0" class="form-control input-xs nospin" />
                                </div> 
                                <label class="col-xs-2 control-label label-xs">Y:</label>  
                                <div class="col-xs-4"> 
                                    <input name="ima_y" type="number" onchange="updatePosIma()" value="0" class="form-control input-xs nospin" />
                                </div> 
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs"></label>
                                <div class="col-xs-4"> 
                                    <button type="button" onclick="guardaImagen();" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-picture"></i> Guardar Datos Imagen Guia</button>
                                </div> 
                            </div>
                        </fieldset>
                    </form> 
                    <button type="button" onclick="guardaDoc();" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar Documento</button>
                </div>
                <div class="col-xs-8" id="gridContainer" style="padding-bottom: 8px;">
                    <iframe id="iframe" src="./../IMPRIMIR/plantilla.html" style="width: 100%; height: 500px; background-color: white;" >
                        <p>Your browser does not support iframes.</p>
                    </iframe>
                </div>
            </div> 
                <div class="row">   
                    <div class="col-xs-12">
                        
                    </div>                    
                </div>   
            
            
        </div>
    </div>
    <script type="text/javascript">
        var iframe, type;
        $( function() {
            iframe=$("#iframe");
            type=$('#type').val();
            $("#imgInp").change(function(){
                $('#formImage').setData({ima_x:0,ima_y:0},false);
                readURL(this);
            });               
        });
        function setType(t){ type=t; }
        function setField(id){
            var data = iframe.prop('contentWindow').getDatos(id);
            $('#formItem').setData(data);  
            $('#formCampo').setData(data);  
            $('#name').val(data['id']);
        }
        function setPlantilla(){ 
            $.getDataJson('',{getPlantilla:true,type:type},function(re) {
                iframe.prop('contentWindow').setPlantilla(re['html']);
                if($.varValid(re['imagen'])){
                     $("#formImage").setData(re['imagen']);                     
                     setImaGuia(re['imagen']['imagen_guia']); 
                }
            });            
        }
        function updatePos(){ if($('#id').val()!=='') iframe.prop('contentWindow').updatePos($('#formItem').getData()); }
        function updatePosIma(){ iframe.prop('contentWindow').updatePosIma($('#formImage').getData()); }
        function setImaGuia(res){ iframe.prop('contentWindow').setImaGuia(res,$('#formImage').getData()); }
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var image = new Image();                   
                    image.src =e.target.result;
                    image.onload = function() { 
                        $('#ppp').val(0);
                        $('#ppp option:selected').data({ima_width:this.width,ima_height:this.height});
                        $('#formImage').setData({ima_width:850,ima_height:1400,ima_rotate:0},false);
                        updatePosIma();
                        setImaGuia(e.target.result); 
                    };
                };
                reader.readAsDataURL(input.files[0]);
            }
        } 
        function selectPPP(){
            $('#formImage').setData($('#ppp option:selected').data(),false);
            updatePosIma();
        }
        function guardaDoc(){            
            var fields=iframe.prop('contentWindow').getAllFields(),y=null;
            switch(type){
                case 'fact': y=fields['item_cant']['y']; break;
                case 'retenc': y=fields['item_rete']['y']; break;
                case 'notcred': y=fields['item_cant']['y']; break;
                case 'liquida': y=fields['item_cant']['y']; break;
            }
            fields['item']={y:y};
            console.log(fields);
            $.saveDataJson('',{saveFields:true, type:type, fields:fields},function(res){
                
            });
            console.log(fields);
        }
        function guardaImagen(){            
             var formData = new FormData(document.getElementById("formImage"));
             formData.append("saveFondo", true);
             formData.append("type", type);
             $.ajax({
                url: "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",
                type: "post", dataType: "json", data: formData, cache: false, contentType: false, processData: false,
                success:function(re){ $.alert(re.message); }
            });
        }
    </script>
</BODY>
</HTML>