<?php	
/**
* @abstract Permite realizar la edición de los datos de un activo
* @author José Ambuludí
* @version 1.0
* Fecha de creación  2016-06-21
* @author José Ambuludí
* Fecha de modificación  2016-07-06
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_activo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Activo($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Activo;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

/* Comprueba de que el perito exista o no */
if(isset($existePerito)){ 
    $responce['data'] = $obBD_con1->getRowConsulta(610,$Prs_Ced, $obBD_conexion);
    
	/* Esta sección comprueba si el dato existe o no, si existe enviará true caso contrario false*/
	if(isset($responce['data']['Pri_Cod']))
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

/*Lista los tipos de activos existentes*/
if(isset($tipoactivAjax)){ 
    $responce = $obBD_con1->getArrayConsulta(608,$Ses_Emp_Cod."*", $obBD_conexion);
    utf8_encode_deep($responce);
    echo json_encode($responce);exit();
}

/*Sección ajax para editar los datos de un activo existente*/
if(isset($edit_activo)){  
	
	$carpeta = "../../imagenes/".$Ses_Emp_Cod.'/Activos';
	if (!file_exists($carpeta)) {
		mkdir($carpeta, 0777, true);
	}
	
	$archivo = $_FILES['Act_Fot']['name'];
	$nombre=explode('.',$archivo);  
	$last=count($nombre)-1;
	$ruta="";
	
	/**
	 * Genera el Codigo de Barra senececitan 12 caracteres para generar
	 */
	$Act_Var='';/* esta variable crea una cadena del codigo de barra*/
	$Act_Gen='';
	if($Act_Bar1==1)
	{
	switch ( strlen($Act_Cod)) {
		case 1:
		 $Act_Var=$Act_Cod."00000000000";
		break;
		case 2:
		 $Act_Var=$Act_Cod."0000000000";
		break;
		case 3:
		 $Act_Var=$Act_Cod."000000000";
		break;
		case 4:
		 $Act_Var=$Act_Cod."00000000";
		break;
		case 5:
		 $Act_Var=$Act_Cod."0000000";
		break;
		case 6:
		 $Act_Var=$Act_Cod."000000";
		break;
		case 7:
		 $Act_Var=$Act_Cod."00000";
		break;
		case 8:
		 $Act_Var=$Act_Cod."0000";
		break;
		case 9:
		 $Act_Var=$Act_Cod."000";
		break;
		case 10:
		 $Act_Var=$Act_Cod."00";
		break;
		case 11:
		$Act_Var=$Act_Cod."0";
		break;
	}
		$Act_Bar=$Act_Var;
		$Act_Gen='G';
	}else{
		$Act_Gen='M';
	}
	
	/*Sección para editar los datos en la tabla activo*/
	$responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
	$obBD_con1->operacionobBD(614, $Act_Cod.'*'.$Tia_Cod.'*'.$Pri_Cod.'*'.$Est_Cod.'*'.$Prv_Cod.'*'.$Act_Des.'*'.$Act_Obs.'*'.$Act_Cdc.'*'.$Act_Can.'*'.$Act_Bar.'*'.$Act_Gen.'*'.$Act_Gar, $obBD_conexion);
	
	/*Sección para obtener los campos del tipo de actiivo para luego editar datos en la tabla det_activo*/
	$campos = $obBD_con1->getArrayConsulta(616,$Tia_Cod, $obBD_conexion);
	foreach($campos as $valor)
	{
		$obBD_con1->operacionobBD(619, $Act_Cod.'*'.$valor['Cam_Cod'].'*'.$_POST[$valor['Cam_Cod']], $obBD_conexion);
	}
	
	if($nombre[$last]!="")
	{
		//$ruta=contiene el nombre de la imagen;
		$ruta='img_activo_'.$Act_Cod.'.'.$nombre[$last];
		//Movemos la imagen cargada a la carpeta con la direccion establecida
		$move = @ move_uploaded_file($_FILES['Act_Fot']['tmp_name'],$carpeta.'\\'.$ruta);
		$obBD_con1->operacionobBD(602, $Act_Cod.'*'.$ruta, $obBD_conexion);
	}
	
	$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
	
	if($obBD_con1->Error==0){ $responce['success']=true; }  
	echo json_encode($responce);
	exit();
}

/*Sección para cargar datos en el Jqgrid referente a los proveedores*/
if(isset($provAjax)){ 
   $data=filter_input_array(INPUT_GET);
   $data["Emp_Cod"]=$Ses_Emp_Cod;   
    $contar = $obBD_con1->getRowConsulta(611, $data, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
	//var_dump($contar);
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0)
        $responce['rows'] =  $obBD_con1->getArrayConsulta(611, $data, $obBD_conexion);
    utf8_encode_deep($responce['rows']); 
    echo json_encode($responce);exit();
}

/*Sección para cargar datos en el jqgrid referente a los activos*/
if(isset($activAjax)){ 
   $data=filter_input_array(INPUT_GET);
   $data["Suc_Cod"]=$Ses_Suc_Cod;   
    $contar = $obBD_con1->getRowConsulta(613, $data, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0)
        $responce['rows'] =  $obBD_con1->getArrayConsulta(613, $data, $obBD_conexion);
    utf8_encode_deep($responce['rows']); 
    echo json_encode($responce);exit();
}

/*Sección para listar los campos pertenecientes a un tipo de activo incluida la información de la tabla det_activo*/
if(isset($buscarCampos)){ 
    $response = $obBD_con1->getArrayConsulta(618,$Act_Cod, $obBD_conexion);
    utf8_encode_deep($response);
    echo json_encode($response);exit();
}

?>

<!DOCTYPE html>
<HTML>
	<HEAD>		
      <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
      <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
      <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
      <link href="../../framework/jquery/bootstrap/bootstrap-fileinput/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
      <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
      <script src="../../framework/jquery/bootstrap/bootstrap-fileinput/js/fileinput.js" type="text/javascript"></script>
	</HEAD>
<BODY>
    <div class="panel panel-main">
    	<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Activos</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        	<div class="row"> 
                <!-- Sección para ingresar los datos de registro del activo -->
                <div class="col-sm-12">
                	<fieldset class="exa-fieldset">                           
                        <legend class="Titulos2">Formulario de Registro</legend>
                        <div class="row">
                        	<div class="col-xs-5"> 
                                <!-- Formulario para listar los activos -->
                                <form id="formBuscarActivo" name="formBuscarActivo" class="form-horizontal normal"  action="">
                                	<div class="form-group Titulos2">
                                        <div class="col-sm-12"><b>NOTA:</b> Para iniciar con la edición de información, deberá seleccionar un activo.<hr/></div>
                                    </div>
                                    <!-- Text input y Button-->
                                    <div class="form-group">
                                    	<label class="col-sm-4 control-label label-sm required" for="Bus_Act">Activo:</label>  
                                        <div class="col-sm-8">  
                                          <div class="input-group">
                                              <input id="Bus_Act" name="Bus_Act" type="text" class="form-control input-sm" onkeypress="return validar_numeric(event);" value="Seleccionar activo" required readonly/>
                                              <span class="input-group-btn">
                                                <button class="btn btn-success btn-sm" onclick="$('#activDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-search" title="Buscar Activo"></span></button>
                                              </span>
                                          </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="row">
                        	<div class="col-xs-5"> 
                                <!-- Formulario para comprobar que la persona esta registrada como perito -->
                                <form id="formPerito" name="formPerito" class="form-horizontal normal"  action="javascript:formPerito();">
                                	
                                    <!-- Text input y Button-->
                                    <div class="form-group">
                                    	<label class="col-sm-4 control-label label-sm required" for="Prs_Ced">Perito:</label>  
                                        <div class="col-sm-8">  
                                          <div class="input-group">
                                              <input id="Prs_Ced" name="Prs_Ced" type="text" placeholder="Digite cédula/R.U.C." class="form-control input-sm" onkeypress="return validar_numeric(event);" required/>
                                              <span class="input-group-btn">
                                                <button class="btn btn-success fileinput-button btn-sm" id="btnComprobar" type="submit" title="Comprobar"><span class="glyphicon glyphicon-search"></span></button>
                                              </span>
                                          </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Mostrar datos de perito -->
                                    <div class="form-group">
                                         <label class="col-sm-4"></label>
                                         <div id="datosPerito" class="col-sm-7" style="font-size:11px; font-weight:bold;">
                                            Sin &iacute;ndice de b&uacute;squeda
                                         </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="row">   
                                <!-- Formulario para la edición de datos del activo -->
                                <form id="formActivo" class="form-horizontal normal" enctype="multipart/form-data" action="javascript:$.createDialogConfirm(null,null,editForm)">
                                	<div class="col-xs-5"> 
                                    <!-- Código del Activo almacenado en la variable hidden Act_Cod misma que es cargada en el Jqgrid -->
                                    <input type="hidden" name="Act_Cod" id="Act_Cod"/>
                                    <!-- Código del Perito almacenado o asignado a la variable oculta Pri_Cod -->
                                    <input type="hidden" name="Pri_Cod" id="Pri_Cod"/>
                                    <!-- Código del proveedor -->
                                    <input type="hidden" name="Prv_Cod" id="Prv_Cod"/>
                                	
                                    <!-- Text input-->
                                    <div class="form-group">
                                      <label class="col-sm-4 control-label label-sm required" for="des_padre">Detalle Activo:</label>  
                                      <div class="col-sm-8">
                                          <input id="Tia_Cod" name="Tia_Cod" type="hidden" readonly value="0" />
                                          <input id="Tia_Des" name="Tia_Des" type="text" placeholder="" class="form-control input-sm" readonly required />
                                      </div>
                                    </div>
                                    
                                    <!-- Select Basic -->
                                    <div class="form-group">
                                      <label class="col-sm-4 control-label label-sm required" for="Est_Cod">Estado:</label>
                                      <div class="col-sm-8">
                                        <select name="Est_Cod" id="Est_Cod" class="form-control input-sm" required >
                                            <option value="">Seleccione Estado...</option>
                                            <?Php 
                                            $rs_estados = $obBD_con1->getArrayConsulta(612,$Ses_Emp_Cod, $obBD_conexion);                               
                                            if (count($rs_estados) > 0)
                                            {
												 foreach($rs_estados as $row){
												 ?>
                                                     <option value="<?Php echo $row['Est_Cod']; ?>"><?Php echo $row['Est_Des']; ?></option>	
												 <?php		
												 }
                                            }                              
                                            ?>
                                        </select>
                                      </div>
                                    </div>
                                    
                                    <!-- Text input-->
                                    <div class="form-group">
                                      <label class="col-sm-4 control-label label-sm required" for="des_padre">C&oacute;digo Secuencia:</label>  
                                      <div class="col-sm-8">
                                          <input id="Act_Cdc" name="Act_Cdc" type="text" placeholder="" class="form-control input-sm" value="" required />
                                      </div>
                                    </div>
                                    
                                    <!-- Text input-->
                                    <div class="form-group">
                                      <label class="col-sm-4 control-label label-sm required" for="des_padre">Cantidad:</label>  
                                      <div class="col-sm-8">
                                          <input id="Act_Can" name="Act_Can" type="text" placeholder="" class="form-control input-sm" value="" onkeypress="return validar_numeric(event);" required />
                                      </div>
                                    </div>
                                    
                                    <!-- Text input-->
                                    <div class="form-group">
                                      <label class="col-sm-4 control-label label-sm required" for="des_padre">C&oacute;digo Barras:</label>  
                                      <div class="col-sm-7">
                                          <input id="Act_Bar" name="Act_Bar" type="text" placeholder="" class="form-control input-sm" value="" required />
                                      </div>
                                      <div class="col-sm-1 checkbox">
                                      	<input name="Act_Bar1" type="checkbox" id="Act_Bar1"  value="1" checked>
                                      </div>  
                                    </div>
                                    
                                    <!-- Muestra información cuando esta seleccionado el checkbox -->
                                    <div class="form-group">
                                      <label class="col-sm-4 control-label label-sm"></label><div class="col-sm-7" style="font-size:11px; font-weight:bold;">Generar c&oacute;digo automaticamente <span class="glyphicon glyphicon-ok"></span></div>
                                    </div>
                                    
                                    <!-- Text input-->
                                    <div class="form-group">
                                      <label class="col-sm-4 control-label label-sm required" for="des_padre">Garant&iacute;a(meses):</label>  
                                      <div class="col-sm-8">
                                          <input id="Act_Gar" name="Act_Gar" type="text" placeholder="" class="form-control input-sm" value="" onkeypress="return validar_numeric(event);" required />
                                      </div>
                                    </div>
                                    
                                    <div class="form-group">
                                      <label class="col-sm-4 control-label label-sm required" for="cod_cuenta">Proveedor:</label>  
                                      <div class="col-sm-8">                                    
                                            <div class="input-group input-group-sm">                                                  
                                                    <input id="Prv_Nom" name="Prv_Nom" type="text" class="form-control" placeholder="Seleccione un Proveedor ..." required readonly />
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-success" onclick="$('#provDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-search" title="Buscar Proveedor"></span></button>
                                                    </span>
                                                  </div><!-- /input-group -->                              
                                      </div>                                  
                                    </div>
                                    
                                    <!-- Textarea -->
                                    <div class="form-group">
                                      <label class="col-sm-4 control-label required" for="Act_Des">Descripci&oacute;n:</label>
                                      <div class="col-sm-8">                     
                                        <textarea class="form-control" id="Act_Des" name="Act_Des" required></textarea>
                                      </div>
                                    </div>
                                    
                                    <!-- Textarea -->
                                    <div class="form-group">
                                      <label class="col-sm-4 control-label" for="Act_Obs">Observaci&oacute;n:</label>
                                      <div class="col-sm-8">                     
                                        <textarea class="form-control" id="Act_Obs" name="Act_Obs"></textarea>
                                      </div>
                                    </div>
                                    
                                    <!--Boton-->
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label"></label>
                                        <div class="col-sm-8">
                                            <button type="button" onClick="this.form.submit()" name="btEditarActivo" id="btEditarActivo"  class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span> Editar</button>
                                        </div>
                                    </div>
                                    
                                    </div>
                               
                                    <div class="col-xs-3">
                                          <fieldset class="exa-fieldset">                           
                                          <legend class="Titulos2">Foto de Activo</legend>
                                          <div class="col-sm-12">
                                              <input id="Act_Fot" name="Act_Fot" type="file">
                                          </div>
                                          </fieldset>
                                   </div>
                                   <div class="col-xs-4">
                                          <fieldset class="exa-fieldset">                           
                                          <legend class="Titulos2">Campos de Tipo de Activo</legend>
                                            <div id="campos_nuevos" class="col-sm-12">
                                                <!-- Sección para presentar los campos del tipo de activo seleccionado -->
                                            </div>
                                          </fieldset>
                                   </div>
                                </form>
                        </div>
                    </fieldset>
                </div>
                <!-- Fin de la sección para registrar los activos -->
            </div>
        </div>
    </div>
       
   <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="Búsqueda de Proveedores">  
      <form class="form-horizontal normal"> 
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-8 radioset" >
                          <input id="rad1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                          <input id="rad2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad2">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7" >                 
                      <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese proveedor a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                      </div><!-- /input-group -->                          
                    </div>                    
                </div>
        </fieldset>  
       </form>    
    </div>
    
    <!--INICIO DEL DIALOGO PARA BUSCAR UN ACTIVO--> 
    <div id="activDialog" title="Búsqueda de Activos">  
      <form class="form-horizontal normal"> 
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-8 radioset" >
                          <input id="rad_ba1" name="op_BuscarActivo" type="radio" value="d" checked="" onclick="setfocus(this.form.search_activo)" alt="" /><label for="rad_ba1">&nbsp;&nbsp;Descripción&nbsp;&nbsp;</label>
                          <input id="rad_ba2" name="op_BuscarActivo" type="radio" value="c" onclick="setfocus(this.form.search_activo)" alt="" /><label for="rad_ba2">&nbsp;&nbsp;Código de Barras&nbsp;&nbsp;</label>
                          <input id="rad_ba3" name="op_BuscarActivo" type="radio" value="dep" alt="" /><label for="rad_ba3">&nbsp;&nbsp;Departamento&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div id="buscar_departamento" class="form-group" style="display:none;">
                    <label class="col-md-2 control-label">Departamento:</label>
                    <div class="col-md-7" >                 
                        <select name="departamento" id="departamento" class="form-control input-sm">
                            <option value="">Seleccione Departamento...</option>
                            <option value="">Sistemas</option>
                            <option value="">Redes</option>
                            <option value="">Contabilidad</option>
                        </select>                      
                    </div>                    
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7" >                 
                      <div class="input-group">                        
                        <input name="search_activo" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese activo a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                      </div>                          
                    </div>                    
                </div>
        </fieldset>  
       </form>    
    </div>
    
    <script type="text/javascript">
	//Sección para bloquear el ingreso de código de barras cuando este marcado el checkbox	
	$(document).ready(function(e) {
		$('#Act_Bar').attr('readonly',true);
		$('#Act_Bar1').click(function(e) {
			if($('#Act_Bar1').is(':checked'))
			{
				$('#Act_Bar').attr('readonly',true);
			}
			else
			{
				$('#Act_Bar').attr('readonly',false);
			}
		});
	});
	
	/*Inicio de sección para cargar la ruta de la imagen desde la base de datos*/
	var $el = $('#Act_Fot');
	
	function initPlugin(img) {
		$el.fileinput({
			showUpload: false,
			showCaption: false,
			browseClass: "btn btn-success btn-sm",
			fileType: "any",
			maxFileSize: 2000,
			msgSizeTooLarge: 'Archivo: "{name}" (<b>{size} KB</b>) excede el tamaño máximo permitido de <b>{maxSize} KB</b>.',
			initialPreviewAsData: true, 
			initialPreviewFileType: 'image', 
			overwriteInitial: true,
			initialPreview: [img],
			initialPreviewConfig: [{caption: "Activo", size: 576237, width: "120px", url: "/site/file-delete", key: 1}],
			previewFileIcon: "<i class='glyphicon glyphicon-remove'></i>"
		});
	};
	/*Fin de seccion para cargar la ruta de la imagen de la base de datos*/
    </script>
    
    <!-- Dialogo para buscar activos -->
    <script type="text/javascript">
	$(document).ready(function() {               
			$.createSearchDialog('#activDialog',[
					{ label: 'Cód.Int.', name: 'Act_Cod', width: 30 },                                
					{ label: 'Descripción Activo', name: 'Act_Des', width: 130,cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                     
						{ label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
							formatter:function (cellvalue, options, rowObject) { 
								var clic='$("#Bus_Act").val("'+rowObject.Act_Cod+'");$("#Act_Cod").val("'+rowObject.Act_Cod+'");$("#Pri_Cod").val("'+rowObject.Pri_Cod+'");$("#Prs_Ced").val("'+rowObject.ced_perito+'");$("#datosPerito").html("'+rowObject.nom_perito+'");$("#Tia_Cod").val("'+rowObject.Tia_Cod+'");$("#Tia_Des").val("'+rowObject.Tia_Des+'");$("#Est_Cod").prop("selectedIndex","'+rowObject.Est_Cod+'");$("#Act_Bar").val("'+rowObject.Act_Bar+'");$("#Prv_Cod").val("'+rowObject.Prv_Cod+'");$("#Prv_Nom").val("'+rowObject.proveedor+'");$("#Act_Des").val("'+rowObject.Act_Des+'");$("#Act_Can").val("'+rowObject.Act_Can+'");$("#Act_Gar").val("'+rowObject.Act_Gar+'");$("#Act_Cdc").val("'+rowObject.Act_Cdc+'");$("#Act_Obs").val("'+rowObject.Act_Obs+'");changeFoto("'+rowObject.Act_Fot+'");marcar("'+rowObject.Act_Gen+'");campos("'+rowObject.Act_Cod+'");$("#activDialog").dialog("close");';
								return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
							}
						}
				]);  
								 
	}); 
    </script>
    <!-- Fin de dialogo para buscar activo -->
    
    <!-- Inicio dialogo proveedor -->
    <script type="text/javascript">
	$(document).ready(function() {               
			$.createSearchDialog('#provDialog',[
					{ label: 'Cód.Int.', name: 'Prv_Cod', key: true,hidden:true,viewable: true },                                
					{ label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
					{ label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
					{ label: 'DirecciÃ³n', name: 'Prs_Dir',hidden:true,viewable: true },                      
						{ label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
							formatter:function (cellvalue, options, rowObject) { 
								var clic='$("#Prv_Nom").val("'+rowObject.proveedor+'");$("#Prv_Cod").val("'+rowObject.Prv_Cod+'");$("#provDialog").dialog("close");';
								return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
							}
						}
				]);  
								 
	}); 
    </script>
	<!-- Fin dialogo proveedor -->

	<!-- Sección para presentar u ocultar el combobox de departamentos -->
    <script type="text/javascript">
	$(document).ready(function() {		               
		$("#rad_ba3").click(function(e) {
           $("#buscar_departamento").show();    
        });
		$("#rad_ba1").click(function(e) {
           $("#buscar_departamento").hide();    
        });
		$("#rad_ba2").click(function(e) {
           $("#buscar_departamento").hide();    
        });
	}); 
    </script>
	
   	<script type="text/javascript">
	function inicializar_imagen()
	{
		$("#Act_Fot").fileinput({
			showUpload: false,
			showCaption: false,
			browseClass: "btn btn-success btn-sm",
			fileType: "any",
			maxFileSize: 2000,
			msgSizeTooLarge: 'Archivo: "{name}" (<b>{size} KB</b>) excede el tamaño máximo permitido de <b>{maxSize} KB</b>.',
			previewFileIcon: "<i class='glyphicon glyphicon-remove'></i>"
		});
	}
   	</script>
     
   	<script type="text/javascript">
   	/*Función para editar los datos de un activo existente, se lo efectua con formData puesto que se esta enviando imagenes*/
   	function editForm(){                 
	  var formData = new FormData(document.getElementById("formActivo"));
	  formData.append("edit_activo", true); 
	  $.ajax({
		  url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
		  type: "post",
		  dataType: "json",
		  data: formData,
		  cache: false,
		  contentType: false,
		  processData: false
	  })
	  .done(function(responce){
		  if(responce.success===true){
			  $.alert("Edición de datos realizada Exitosamente..!!"); 
			  //$treeview.jstree(true).refresh(); 
			  $.getDialogGrid('#activDialog').jqGrid("clearGridData").trigger('reloadGrid');                      
		  }else{$.alert(responce.message);}
	  });
	  /*Sección para limpiar el formulario*/ 
	   $('#formActivo')[0].reset( );
	   $('#formPerito')[0].reset( );
	   $('#datosPerito').html('Sin índice de búsqueda');
	   $('#Bus_Act').val('Seleccione Activo');
	   $("#campos_nuevos").html("");
	   //inicializar_imagen();
	   changeFoto('');
   	};
	
	//Función para marcar o no el checkbox según si Act_Gen es "G" o "M"
	function marcar(opcion)
	{
		if(opcion==='M')
		{
			$('#Act_Bar1').prop('checked','');	
		}else{
			$('#Act_Bar1').prop('checked','checked');
		}
	}
	
	//Función para la presentación de los campos adicionales correspondientes al tipo de activo
	function campos(Act_Cod)
	{
		var codigo={Act_Cod:Act_Cod,buscarCampos:true};
		$.post('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',codigo, function( response ){
			campos_nuevos=response;
			/*Llama a la función addcampos para agregar los campos que pertenecen al tipo de activo pero con datos de la tabla det_activo*/
			addcampos(); 
		},'json');
	}
	
	//Función para aderir campos del tipo de activo
	function addcampos(){
	   $("#campos_nuevos").html("");
	   for(var i=0;i<campos_nuevos.length;i++){
		   if(campos_nuevos[i]['Cam_Req']==='S')
		   {
		   	  campo = '<div class="form-group"><label class="col-sm-5 control-label required">'+campos_nuevos[i]['Cam_Lar']+':</label><div class="col-sm-7"><input type="text" class="form-control input-sm" id="'+campos_nuevos[i]['Cam_Cod']+ '" name="'+campos_nuevos[i]['Cam_Cod']+'" value="'+campos_nuevos[i]['Act_Val']+'" required/></div></div>';
		   }
		   else
		   {
			  campo = '<div class="form-group"><label class="col-sm-5 control-label">'+campos_nuevos[i]['Cam_Lar']+':</label><div class="col-sm-7"><input type="text" class="form-control input-sm" id="'+campos_nuevos[i]['Cam_Cod']+ '" name="'+campos_nuevos[i]['Cam_Cod']+'" value="'+campos_nuevos[i]['Act_Val']+'"/></div></div>';
		   }
		   $("#campos_nuevos").append(campo);
	   }
    }
	
	//Sección para ubicar la foto que se encuentra registrada en la base de datos referente al activo buscado
   	function changeFoto(img){
		if(img!="")
		{
		   if ($el.data('fileinput')) {$el.fileinput('destroy');}
		   if (!$el.data('fileinput')) {initPlugin('../../imagenes/<?php echo $Ses_Emp_Cod; ?>/Activos/'+img);}
		   if ($el.val()) {$el.trigger('change');}
		}else{
			$el.fileinput('destroy');
			inicializar_imagen();
		}
		//$("#btEditarActivo").prop("disabled",false);
		/*Llamo a la función updateTipoActivo desde aqui para que cargue el jstree al momento de seleccionar un activo*/
   		//updateTipoActivo();
   	} 
	
	/*Función para comprobar de que un perito existe o no*/
   	function formPerito(){  
   	 $('#datosPerito').html('Sin índice de búsqueda');
     if($('#Prs_Ced').val()=='')
	 {
		 $.alert('Ingrese número de cédula o R.U.C.');
	 }
	 else
	 {
	  var data=$('#formPerito').serializeObject();
	  data["existePerito"]=true;
	             
	  $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function(response) {
		  
		  if(response['success']==true){
			  $('#datosPerito').html('<span class="glyphicon glyphicon-ok"></span> '+response['data']['Prs_Nom']+' '+response['data']['Prs_Ape']);
			  $('#Pri_Cod').val(response['data']['Pri_Cod']);                          
		  }
		  else
		  {
			  $('#datosPerito').html('<span class="glyphicon glyphicon-remove"></span> Perito no registrado');
		  }
	   },'json').fail(function(error) { $.alert();}); 
	 }
    }
	inicializar_imagen();
   	</script>
</BODY>
</HTML>