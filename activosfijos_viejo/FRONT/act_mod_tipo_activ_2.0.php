<?php	
/**
* @abstract Permite realizar el registro de un tipo de activo
* @author José Ambuludí
* @version 2.0
* Fecha de creaci?n  2016-06-03
* @author José Ambuludí
* Fecha de modificación  2016-06-30
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_tipo_activ.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Act($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Act;
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

/*Sección ajax para editar los datos de un tipo de activo*/
if(isset($editarTipoActivo)){   
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
	$obBD_con1->operacionobBD(602, $Tia_Cod.'*'.$Tia_Des.'*'.$Tia_Tip.'*'.$Tia_Dep.'*'.$Tia_Obs, $obBD_conexion);
	
	/*Sección para obtener el código de la última inserción en la tabla tipo_activ*/
	$Tia_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
	
	foreach($campos as $valor)
	{
		/*Sección para modificar datos de la tabla campos_act*/
		$obBD_con1->operacionobBD(613, $valor['Cam_Cod'].'*'.$valor['Cam_Lar'].'*'.$valor['Cam_Cor'].'*'.$valor['Cam_Tip'].'*'.$valor['Cam_Obs'].'*'.$valor['Cam_Bus'], $obBD_conexion);
		
		/*Sección para modificar datos de la tabla campos_plan*/
		$obBD_con1->operacionobBD(614, $valor['Cam_Cod'].'*'.$valor['Cam_Ord'].'*'.$valor['Cam_Req'], $obBD_conexion);
	}
	$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; }  
    echo json_encode($responce);
    exit();
}

/*Sección para listar los campos pertenecientes a un tipo de activo*/
if(isset($buscarCampo)){ 
    $responce = $obBD_con1->getArrayConsulta(612,$Ses_Emp_Cod."*".$id, $obBD_conexion);
    utf8_encode_deep($responce);
    echo json_encode($responce);exit();
}

?>

<!DOCTYPE html>
<HTML>
	<HEAD>		
      <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
      <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
      <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
      <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
	</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Tipo de Activos</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <div class="col-sm-4">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Tipo de Activos</legend> <!-- Form Name -->
                           <div class="panel panel-success exa-panel">
                           		<div class="panel-heading"><i class="fa fa-list-ol"></i>&nbsp;&nbsp;<span>Tipos de activos por Grupo/Detalle</span></div>
                                <div class="panel-body">
                                    <div class="scrollable-tree" style="height: 350px"><div id="using_json_2"></div></div>
                                </div> 
                                <div id="foot" class="panel-footer" style="display:none;"><span id="plan-footer">No puede agregar elementos en un <b>DETALLE</b></span></div>
                           </div>
                        </fieldset>
                    </div> 
                    <div class="col-xs-8"> 
                    	<div class="row"> 
                        <div class="col-xs-12">  
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <form id="formTipo_Activ" class="form-horizontal normal"  action="javascript:saveForm();"  >
                            	<div class="form-group Titulos2">
                                    <div class="col-xs-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>
                            	<!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-2 control-label label-xs" for="des_padre">Descripción Activo:</label>  
                                  <div class="col-sm-10">
                                      <input id="Tia_Cod" name="Tia_Cod" type="text"  readonly style="display: none" value="0" />
                                      <input id="cod_padre" name="cod_padre" type="text"  readonly style="display: none" value="0" />
                                      <input id="des_padre" name="des_padre" type="text" placeholder="" class="form-control input-xs bold" readonly value="Tipo de Activo Raíz" />

                                  </div>
                                </div>
                                
                                <!-- Textarea -->
                                <div class="form-group">
                                  <label class="col-xs-2 control-label required" for="Tia_Des">Descripci&oacute;n:</label>
                                  <div class="col-sm-10">                     
                                    <textarea class="form-control input-xs" id="Tia_Des" name="Tia_Des" required></textarea>
                                  </div>
                                </div>
                                
                                <!-- Select Basic -->
                                <div class="form-group">
                                  <label class="col-xs-2 control-label label-xs required" for="Tia_Tip">Tipo:</label>
                                  <div class="col-sm-2">
                                    <select id="Tia_Tip" name="Tia_Tip" class="form-control input-xs" required>
                                      <option value="G">GRUPO</option>
                                      <option value="D">DETALLE</option>
                                    </select>
                                  </div>
                                  <label class="col-sm-2 control-label label-xs required" for="Tia_Dep">Depreciable:</label>
                                  <div class="col-sm-2">
                                    <select id="Tia_Dep" name="Tia_Dep" class="form-control input-xs" required>
                                      <option value="S">SI</option>
                                      <option value="N">NO</option>
                                    </select>
                                  </div>
                                </div>
                                
                                <!-- Textarea -->
                                <div class="form-group">
                                  <label class="col-xs-2 control-label" for="Tia_Obs">Observaci&oacute;n:</label>
                                  <div class="col-sm-10">                     
                                    <textarea class="form-control input-xs" id="Tia_Obs" name="Tia_Obs"></textarea>
                                  </div>
                                </div>
                            </form> 
                        </fieldset>
                        </div>
                    	</div> 
                        <div class="row">
                            <div class="col-xs-12">
                            <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Registro de Campos</legend>
                                <div class="col-xs-7">    
                                        <!-- Formulario para los campos que definiran el tipo de activo -->
                                        <form id="formCamp_Activ" class="form-horizontal normal"  action="javascript:saveCampos();">
                                        <input id="indice" name="indice" type="text"  readonly style="display: none" value="0" />
                                        <input id="Cam_Cod" name="Cam_Cod" type="text" style="display:none;"  readonly value="0" />
                                            <!-- Text input-->
                                            <div class="form-group">
                                              <label class="col-sm-4 control-label label-xs required" for="Cam_Lar">Descripci&oacute;n Larga:</label>  
                                              <div class="col-sm-8">
                                                  <input id="Cam_Lar" name="Cam_Lar" type="text" required class="form-control input-xs"/>
                                              </div>
                                            </div>
                                            
                                            <!-- Text input-->
                                            <div class="form-group">
                                              <label class="col-sm-4 control-label label-xs required" for="Cam_Cor">Descripci&oacute;n Corta:</label>  
                                              <div class="col-sm-8">
                                                  <input id="Cam_Cor" name="Cam_Cor" type="text" required class="form-control input-xs"/>
                                              </div>
                                            </div>
                                            
                                            <!-- Select Basic -->
                                            <div class="form-group">
                                              <label class="col-sm-4 control-label label-xs required" for="Tia_Dep">Tipo:</label>
                                              <div class="col-sm-4">
                                                <select id="Cam_Tip" name="Cam_Tip" class="form-control input-xs" required>
                                                  <option value="NE">ENTERO</option>
                                                  <option value="ND">DECIMAL</option>
                                                  <option value="CA">CARACTER</option>
                                                  <option value="TX">TEXTO</option>
                                                </select>
                                              </div>
                                            </div>
                                            
                                            <!-- Textarea -->
                                            <div class="form-group">
                                              <label class="col-xs-4 control-label" for="Cam_Obs">Observaci&oacute;n:</label>
                                              <div class="col-sm-8">                     
                                                <textarea class="form-control input-xs" id="Cam_Obs" name="Cam_Obs"></textarea>
                                              </div>
                                            </div>
                                            
                                            <!-- Select Basic -->
                                            <div class="form-group">
                                              <label class="col-xs-4 control-label label-xs required" for="Cam_Bus">Campo B&uacute;squeda:</label>
                                              <div class="col-sm-3">
                                                <select id="Cam_Bus" name="Cam_Bus" class="form-control input-xs" required>
                                                  <option value="N">NO</option>
                                                  <option value="S">SI</option>
                                                </select>
                                              </div>
                                            </div>
                                        
                                        	<!-- Text input-->
                                            <div class="form-group">
                                              <label class="col-sm-4 control-label label-xs required" for="Cam_Ord">Orden:</label>  
                                              <div class="col-sm-3">
                                                  <input id="Cam_Ord" name="Cam_Ord" type="text" placeholder="" onkeypress="return validar_numeric(event);" class="form-control input-xs" required/>
                                              </div>
                                            </div>
                                            
                                            <!-- Select Basic -->
                                            <div class="form-group">
                                              <label class="col-xs-4 control-label label-xs required" for="Cam_Req">Campo Requerido:</label>
                                              <div class="col-sm-3">
                                                <select id="Cam_Req" name="Cam_Req" class="form-control input-xs" required>
                                                  <option value="S">SI</option>
                                                  <option value="N">NO</option>
                                                </select>
                                              </div>
                                            </div>
                                            
                                            <!--Boton-->
                                            <div class="form-group">
                                                <label class="col-xs-4 control-label"></label>
                                                <div class="col-sm-4">
                                                    <button id="bt_add" name="bt_add" type="submit"  class="btn btn-success btn-xs"><span class="glyphicon glyphicon-pencil"></span> Editar</button>
                                                </div>
                                            </div>
                                        </form> 
                                </div>   
                                <!-- Fin de seción de definición de campoos de tipo de activo -->
                            
                                <!-- Tabla de campos que se van aignando por tipo de activo -->
                                <div id="listado" class="col-sm-5">
                                    <table id="list"></table>
                                    <div id="listPager"></div>
                                </div>
                                <!-- Tabla de campos que se van aignando por tipo de activo -->
                        	</fieldset>
                            </div> 
                            
                            <!-- Boton para guaradar los datos de los dos formularios -->
                            <div class="col-sm-12">
                                <button id="bt_editar" name="bt_editar" type="submit"  class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-pencil"></span> Editar</button>
                            </div>
                            
                        </div> 
                    </div>
                </div>  
            </div>   
        </div>
    </div>
   
   <!-- Boton para añadir un campo al jqgrid y al array -->
   <script type="text/javascript">
	/*Desactiva el boton de editar mientras no seleccione un tipo de activo*/
	$(document).ready(function() {
		$('#bt_editar').prop('disabled',true);
	});
	
	/*Función para guardar los campos, pero a la vez se valida el formulario*/
	var campos=new Array();
	function saveCampos(){
		var datarow = $('#formCamp_Activ').serializeObject();
		var indice=datarow['indice'];
		campos[indice]=datarow;
		$("#list").clearGridData();
		for(var i=0;i<campos.length;i++){
			campos[i]['index']=i;
			$("#list").jqGrid('addRowData',i,campos[i]);
		}
		$.alert('Campos editados');
	}
   </script>
   
   <!-- Boton para guardar un registro en la tabla tipo_activ, campos_act, campos_plan -->
   <script type="text/javascript">
    var campos=new Array();
	$(document).ready(function() {
		$('#bt_editar').click(function(e) {
			$('#formTipo_Activ').formSubmit();
        });
	});
   </script>
   
   <script type="text/javascript">
	$(document).ready(function() {                
		$("#list").jqGrid({
			url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
			mtype: "GET", datatype: "json", regional : 'es',
			autowidth : true, shrinkToFit: true, height: 160,
			cmTemplate: {sortable:false},
			colModel: [  
				{ label: 'index', key:true, hidden:true, name: 'index', width: 150 },
				{ label: 'Cam_Cod', hidden:true, name: 'Cam_Cod', width: 150 },
				{ label: 'Descripción Larga', name: 'Cam_Lar', width: 220 }, 
				{ label: 'Orden', name: 'Cam_Ord', width: 150 },
				{ label:'&nbsp;', name: 'act1', width: 60, align: 'center',viewable: false,
						formatter:function (cellvalue, options, rowObject) { 
							return  '<span class="btn btn-success btn-xs" title="Seleccionar" type="button" onclick="camposEditarActivo(\''+rowObject.index+'\')";><i class="glyphicon glyphicon-arrow-right"></i></span>';
						}
					}
			],                                                     
			rowNum: 20, pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass"
		});		 
	}); 
    </script>
   
   <script type="text/javascript">
   /*Función para cargar los datos seleccionados del jqgrid hacia los campos del form para editar*/
   function camposEditarActivo(index)
   {
	   $('#indice').val(campos[index]['index']);
	   $('#Cam_Lar').val(campos[index]['Cam_Lar']);
	   $('#Cam_Cor').val(campos[index]['Cam_Cor']);
	   $('#Cam_Tip').val(campos[index]['Cam_Tip']);
	   $('#Cam_Obs').val(campos[index]['Cam_Obs']);
	   $('#Cam_Bus').val(campos[index]['Cam_Bus']);
	   $('#Cam_Ord').val(campos[index]['Cam_Ord']);
	   $('#Cam_Req').val(campos[index]['Cam_Req']);
	   $('#Cam_Cod').val(campos[index]['Cam_Cod']);
   }
   
   /*Función para guardar un nuevo tipo de activo*/
   function saveForm(){            
	  var data=$('#formTipo_Activ').serializeObject();
	  data['campos']=campos;
	  data["editarTipoActivo"]=true;
	             
	  $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( response ) {
		  if(response['success']===true){
			  $.alert("Transaccion Realizada con &Eacute;xito!");                          
			  $treeview.jstree(true).refresh();
		  }else{$.alert(response['message']);}
	   },'json').fail(function(error) { $.alert();});     
	   /*Sección para limpiar formularios*/ 
	   $('#formTipo_Activ')[0].reset( );
	   $('#formCamp_Activ')[0].reset( ); 
	   $treeview.jstree(true).refresh();  
	   $("#list").clearGridData();  
	   $('#prefijo').html('');  
	   //$('#bt_guardar').prop('disabled',true);    
    }
   
   /*Variable para manejo del arbol jstree*/
   var $treeview=$('#using_json_2');     
   
   function updateTipoActivo(){
   		$treeview.jstree(true).settings.core.data = {'url': '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>?tipoactivAjax=true',"dataType": "json" };
   		$treeview.jstree(true).refresh();
   } 
   var campos=new Array();
   $treeview.jstree({'core' : {'data': {}}}).on('select_node.jstree', function (e, data) { 
   		
		/*Crago los valores devueltos por ajax a traves de data a cada uno de los input#*/  
   		$('#cod_padre').val(data.node.id);
		$('#des_padre').val(data.node.text);
		$('#prefijo').html(data.node.text.split(" - ")[0]+'.');
		var tipo=data.node.original.Tia_Tip;
		$('#Tia_Des').val(data.node.original.Tia_Des);
		$('#Tia_Obs').val(data.node.original.Tia_Obs);
		$('#Tia_Tip').val(data.node.original.Tia_Tip);
		$('#Tia_Dep').val(data.node.original.Tia_Dep);
		$('#Tia_Cod').val(data.node.original.Tia_Cod);
		
		/*Se habilita el boton de editar una vez que haya seleccionado un tipo de activo*/
		$('#bt_editar').prop('disabled',false);
		
		/*Sección para cargar el Jqgrid*/
		var codigo={id:data.node.id,buscarCampo:true};
		var i=0;
		$.post('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',codigo, function( response ){
			campos=response;
			/*Llama a la función updateGridcampos para cargar los datos al arreglo campos y a su vez al jqgrid*/
			updateGridcampos();
		},'json');
   });
   
   function updateGridcampos(){
	   $("#list").clearGridData();
	   for(var i=0;i<campos.length;i++){
		   campos[i]['index']=i;
		   $("#list").jqGrid('addRowData',i,campos[i]);
	   }
   }
  
   /*Llamo a la función updateTipoActivo desde aqui para que cargue el jstree al momento de cargar la página*/
   updateTipoActivo();
   </script>
</BODY>
</HTML>