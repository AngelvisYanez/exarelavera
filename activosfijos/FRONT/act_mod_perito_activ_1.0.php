<?php	
/**
* @abstract Permite realizar la modificaci�n de los datos de un perito
* @author Jos� Ambulud�
* @version 1.0
* Fecha de creaci?n  2016-08-08
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

/*Secci�n para cargar datos en el Jqgrid referente a los peritos*/
if(isset($peritoAjax)){ 
    $data=filter_input_array(INPUT_GET);
    $data["Emp_Cod"]=$Ses_Emp_Cod;   
    $contar = $obBD_con1->getRowConsulta(607, $data, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0)
            $responce['rows'] =  $obBD_con1->getArrayConsulta(607, $data, $obBD_conexion);
    //utf8_encode_deep($responce['rows']); 
    echo json_encode($responce);exit();
}

/*Secci�n ajax para editar los datos del perito seleccionado*/
if(isset($editPerito)){   
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    
    /*Update de datos de persona y perito*/
    $obBD_con1->operacionobBD(608, $Prs_Cod.'*'.$Prs_Nom.'*'.$Prs_Ape.'*'.$Prs_Sex.'*'.$Ciu_Cod.'*'.$Prs_Dir.'*'.$Prs_Tel.'*'.$Prs_Cel, $obBD_conexion);
    $obBD_con1->operacionobBD(609, $Pri_Cod.'*'.$Pri_Esp.'*'.$Pri_Obs, $obBD_conexion);
        
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
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Perito</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
            	<div class="row">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6">
                    	<fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <form id="FormPerito" name="FormPerito" class="form-horizontal normal"  action="javascript:editForm();">
                                <input type="hidden" id="Prs_Cod" name="Prs_Cod"/>
                                <input type="hidden" id="Pri_Cod" name="Pri_Cod"/>
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>
                                <!-- Text input y Button-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Prs_Ced">C&eacute;dula/R.U.C.:</label>  
                                    <div class="col-sm-6">
                                        <div class="input-group input-group-sm">                                                  
                                            <input id="Prs_Ced" name="Prs_Ced" type="text" readonly class="form-control" placeholder="Seleccionar Perito" required />
                                            <span class="input-group-btn">
                                                <button class="btn btn-success" type="button" onclick="$('#peritoDialog').dialog('open');" title="Buscar Perito"><span class="glyphicon glyphicon-search"></span></button>
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
                                        <button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span> Editar</button>
                                    </div>
                                </div>
                            </form>
                        </fieldset>
                    </div>
                </div> 
            </div>   
        </div>
    </div>
    
    <!-- Inicio del di�logo para buscar peritos --> 
    <div id="peritoDialog" title="B&uacute;squeda de Peritos">  
      <form class="form-horizontal normal"> 
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-8 radioset" >
                        <input id="rad1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                        <input id="rad2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad2">&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7" >                 
                         <div class="input-group">                        
                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese perito a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Perito" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                        </div>                        
                    </div>                    
                </div>
        </fieldset>  
       </form>    
    </div>
    
    <script type="text/javascript">
   
    //Secci�n para el choosen
    $(document).ready(function(){
        $("#Ciu_Cod").createChosen();                
    });
    
    
    $(document).ready(function() {               
        $.createSearchDialog('#peritoDialog',[
            { label: 'C�d.Int.', name: 'Pri_Cod', key: true,hidden:true,viewable: true },                                
            { label: 'C&eacutedula', name: 'Prs_Ced', width: 50 },
            { label: 'Perito', name: 'perito', width: 100 },                      
            { label: 'Especialidad', name: 'Pri_Esp', width: 100, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                                     
            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                    formatter:function (cellvalue, options, rowObject) { 
                        var clic='$("#Pri_Cod").val("'+rowObject.Pri_Cod+'");\n\
                                    $("#Prs_Cod").val("'+rowObject.Prs_Cod+'");\n\
                                    $("#Prs_Ced").val("'+rowObject.Prs_Ced+'");\n\
                                    $("#Ide_Des").val("'+rowObject.Ide_Des+'");\n\
                                    $("#Prs_Nom").val("'+rowObject.Prs_Nom+'");\n\
                                    $("#Prs_Ape").val("'+rowObject.Prs_Ape+'");\n\
                                    $("#Prs_Sex").val("'+rowObject.Prs_Sex+'");\n\
                                    $("#Ciu_Cod").val("'+rowObject.Ciu_Cod+'").trigger("chosen:updated");\n\
                                    $("#Prs_Dir").val("'+rowObject.Prs_Dir+'");\n\
                                    $("#Prs_Tel").val("'+rowObject.Prs_Tel+'");\n\
                                    $("#Prs_Cel").val("'+rowObject.Prs_Cel+'");\n\
                                    $("#Pri_Esp").val("'+rowObject.Pri_Esp+'");\n\
                                    $("#Pri_Obs").val("'+rowObject.Pri_Obs+'");\n\
                                    $("#peritoDialog").dialog("close");';
                        return  '<span class="btn btn-success btn-xs" type="button" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                    }
            }
        ]);  
    }); 
   
    /*Funci�n para editar datos de persona y perito*/
    function editForm(){
        $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",
        $('#FormPerito').getData('editPerito'), 
        function(response){	
            if(response['success']===true){
                $.alert("El Registro se ha Editado con Exito!");			
                $('#FormPerito')[0].reset();
                $('#Ciu_Cod').val('').trigger('chosen:updated');
                $.getDialogGrid('#peritoDialog').jqGrid("clearGridData").trigger('reloadGrid');
            }else{$.alert("No se logro guardar el Registro!");}
        },'json').fail(function(error) {$('#FormPerito')[0].reset();$.alert("El Servidor ha fallado en responder!");});
    }
    </script>
</BODY>
</HTML>
