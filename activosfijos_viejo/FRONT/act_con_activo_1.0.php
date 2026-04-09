<?php	
/**
* @abstract Permite realizar la edición de los datos de un activo
* @author José Ambuludí
* @version 1.0
* Fecha de creaci?n  2016-06-21
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
	/*OJO POR AHORA NO SE ESTA EDITANDO LA FOTO, TAMPOCO EL CAMPO Act_Gen=M o G*/
	$responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
	$obBD_con1->operacionobBD(614, $Act_Cod.'*'.$Tia_Cod.'*'.$Pri_Cod.'*'.$Est_Cod.'*'.$Prv_Cod.'*'.$Act_Des.'*'.$Act_Obs.'*'.$Act_Cdc.'*'.$Act_Can.'*'.$Act_Bar.'*'.$Act_Gen.'*'.$Act_Gar, $obBD_conexion);
	
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

/*Sección para cargar datos en el Jqgrid referente a los activos*/
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
    	<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consulta de Activos</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        	<div class="row">
                <!-- Sección para ingresar los datos de registro del activo -->
                <div class="col-sm-12">
                	<fieldset class="exa-fieldset">
                    <legend class="Titulos2">Filtros</legend>
                	<form id="formBuscarActivo" name="formBuscarActivo" class="form-horizontal normal" action="javascript:$('#list').Search('#formBuscarActivo','activAjax');"> 
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
                                    <input name="search_activo" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" value="" placeholder="Ingrese activo a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                                    <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                                  </div>                          
                                </div>                    
                            </div> 
                   </form>
                   </fieldset> 
                </div>
            </div>
            <!-- Presentación de resultados según el índice de búsqueda -->
            <div class="row">               
                <div id="listado" class="col-sm-12">
                	<fieldset class="exa-fieldset">                           
                        <legend class="Titulos2">Resultados de la búsqueda:</legend>
                        <!-- Formulario para realizar la búsqueda del activo -->
                        	<table id="list"></table>
                            <div id="listPager"></div>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
    
    <!-- INICIO DEL DIALOGO RESULTADO DE ACTIVO --> 
    <div id="activoDialog" title="Resultado de Activo">  
    	<div>
        	<div style="width: 62%;display: inline;float:left;">
                <fieldset class="exa-fieldset">
                <legend class="Titulos2">Activo</legend>
                <form class="form-horizontal normal"> 
                        <div class="form-group">
                            <label class="col-md-3 control-label label-xs">Perito:</label>  
                            <div class="col-md-9" >
                            	<input id="perito" name="perito" type="text" class="form-control input-sm" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label label-xs">Tipo de Activo:</label>  
                            <div class="col-md-9" >
                            	<input id="Tia_Des" name="Tia_Des" type="text" class="form-control input-sm" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label label-xs">Estado:</label>  
                            <div class="col-md-9" >
                            	<input id="Est_Des" name="Est_Des" type="text" class="form-control input-sm" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label label-xs">C. Secuencia:</label>  
                            <div class="col-md-9">
                            	<input id="Act_Cdc" name="Act_Cdc" type="text" class="form-control input-sm" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label label-xs">Cantidad:</label>  
                            <div class="col-md-9" >
                            	<input id="Act_Can" name="Act_Can" type="text" class="form-control input-sm" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label label-xs">Código Barras:</label>  
                            <div class="col-md-9" >
                            	<input id="Act_Bar" name="Act_Bar" type="text" class="form-control input-sm" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label label-xs">Garantía:</label>  
                            <div class="col-md-9" >
                            	<input id="Act_Gar" name="Act_Gar" type="text" class="form-control input-sm" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label label-xs">Proveedor:</label>  
                            <div class="col-md-9">
                            	<input id="proveedor" name="proveedor" type="text" class="form-control input-sm" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label label-xs">Descripción:</label>  
                            <div class="col-md-9">
                            	<textarea class="form-control" id="Act_Des" name="Act_Des" readonly></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label label-xs">Observación:</label>  
                            <div class="col-md-9">
                            	<textarea class="form-control" id="Act_Obs" name="Act_Obs" readonly></textarea>
                            </div>
                        </div>
               </form>
               </fieldset>
            </div>
          
            <div style="width: 37%;display: inline;float:right;">
                <fieldset class="exa-fieldset">
                <legend class="Titulos2">Activo - Foto</legend>
                <form class="form-horizontal normal"> 
                    <div class="form-group">
                        <div class="col-md-8" >
                            <img id="foto_activo" src="" style="width:250px; height:150px;">
                        </div>
                    </div>
               </form>
               </fieldset>
            </div>
        </div>
    </div> 
    
    <script type="text/javascript">
	$(document).ready(function() {   
		$.createDialog('#activoDialog',500,780);             
		$("#list").jqGrid({
			url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
			mtype: "GET", datatype: "json", regional : 'es',
			postData: $("#formBuscarActivo").getData("activAjax"),
			autowidth : true, shrinkToFit: true, height: 295,
			cmTemplate: {sortable:false},
			colModel: [  
				{ label: 'Descripción', name: 'Act_Des', width: 250 }, 
				{ label: 'Tipo de Activo', name: 'Tia_Des', width: 150 },
				{ label: 'Código de Barras', name: 'Act_Bar', width: 50,align:"center" },
				{ label: 'Estado', name: 'Est_Des', width: 50 ,align:"center" },
					{ label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
						formatter:function (cellvalue, options, rowObject) { 
							return  '<span class="btn btn-success btn-xs" title="Seleccionar" type="button" onclick="selectActivo(\''+rowObject.Tia_Des+'\',\''+rowObject.nom_perito+'\',\''+rowObject.Est_Des+'\',\''+rowObject.Act_Cdc+'\',\''+rowObject.Act_Can+'\',\''+rowObject.Act_Bar+'\',\''+rowObject.Act_Gar+'\',\''+rowObject.proveedor+'\',\''+rowObject.Act_Des+'\',\''+rowObject.Act_Obs+'\',\''+rowObject.Act_Fot+'\');"><i class="glyphicon glyphicon-arrow-right"></i></span>';
						}
					}
			],                                                     
			rowNum: 20, pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass"
		});		 
	}); 
    </script>
   
	
    <script type="text/javascript">
	function selectActivo(Tia_Des,perito,Est_Des,Act_Cdc,Act_Can,Act_Bar,Act_Gar,proveedor,Act_Des,Act_Obs,Act_Fot)
	{
		if(Act_Fot!='')
		{
			var foto='../../imagenes/<?php echo $Ses_Emp_Cod; ?>/Activos/'+Act_Fot;
		}else{
			var foto='../../imagenes/<?php echo $Ses_Emp_Cod; ?>/Activos/no-imagen.jpg';
		}
		
		$('#activoDialog').dialog('open');
		$('#Tia_Des').val(Tia_Des);
		$('#perito').val(perito);
		$('#Est_Des').val(Est_Des);
		$('#Act_Cdc').val(Act_Cdc);
		$('#Act_Can').val(Act_Can);
		$('#Act_Bar').val(Act_Bar);
		$('#Act_Gar').val(Act_Gar);
		$('#proveedor').val(proveedor);
		$('#Act_Des').val(Act_Des);
		$('#Act_Obs').val(Act_Obs);
		$('#foto_activo').prop('src',foto);
	}
    </script>
    
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
</BODY>
</HTML>