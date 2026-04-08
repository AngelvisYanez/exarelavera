<?php 
/**
 * Interfaz para llevar el control de tareas del area contable para Ofsercont
 *
 * @author Alejandro CAmacho
 * @version 1.0
 * Fecha de actualizacion:	2021/03/22
 */

require_once('../../../administrador/LOGICA/seguridad.php');
require_once('../../LOGICA/requisiciones/index.php');
require_once('../../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../../Librerias/postclass.php');


/** 
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Requisiciones($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Requisiciones($obBD_conexion);
$hoy = date("Y-m-d H:i:s");
$mes = date("m");

	
  /*
   * Guardar y Modificar tarea
   */
   if(isset($guardarTipo)){    
   	  $_POST['Emp_Cod'] = $Ses_Emp_Cod;     
      $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
      $obBD_con1->operacionobBD('requisiciones_tipo.2',$_POST,$obBD_conexion);
      
      if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) { 
          $responce['success'] = true;            
      }
      else {
          $responce['success'] = false;
          $responce['message'] = "No se ha logrado realizar la Transaccion";
      }
      $obBD_con1->echoJson($responce);
      exit(); 
   }

   if(isset($consultarTipo)){    
     //ChromePhp::log($_GET);
   	  $_GET['Emp_Cod'] = $Ses_Emp_Cod;     
      $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
      $data = $obBD_con1->getROWConsulta('requisiciones_tipo.3',$_GET,$obBD_conexion);
      
      if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) { 
          $responce['success'] = true;
          $responce['data'] = $data;            
      }
      else {
          $responce['success'] = false;
          $responce['message'] = "No se ha logrado realizar la Transaccion";
      }
      $obBD_con1->echoJson($responce);
      exit(); 
   }

   if(isset($editarTipo)){ 
    // $_GET['Emp_Cod'] = $Ses_Emp_Cod;     
     $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
     $obBD_con1->operacionobBD('requisiciones_tipo.4',$_POST,$obBD_conexion);
     
     if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) { 
         $responce['success'] = true;            
     }
     else {
         $responce['success'] = false;
         $responce['message'] = "No se ha logrado realizar la Transaccion";
     }
     $obBD_con1->echoJson($responce);
     exit(); 
  }
 
    /*
     * Obtener Tipos
     */
    if(isset($searchFiltro))
    {
      //ChromePhp::log('SEARCHFILTRO');
      $_GET['Emp_Cod'] = $Ses_Emp_Cod;
        $data = $obBD_con1->getArrayConsulta('requisiciones_tipo.1', $_GET, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows'=>$data,
            'total'=>1,
            'records'=>count($data),
            'success'=>true
        ));
        exit(); 
    }

    if(isset($cargarDoc))
    {
        $data = $obBD_con1->getRowConsulta(7, $_GET, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows'=>$data,
            'total'=>1,
            'records'=>count($data),
            'success'=>true
        ));
        exit(); 
    }

    if(isset($cargarEditar))
    {
        $data = $obBD_con1->getRowConsulta(14, $_GET, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows'=>$data,
            'total'=>1,
            'records'=>count($data),
            'success'=>true
        ));
        exit(); 
    }

?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../../mascaras/model1/estilos/jqgrid5.php") ?>

        
    </HEAD>
    <BODY>
        <div id="documentoSearch" class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Tipos de Requisiciones </h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-sm-12">
                      <div >
                          <table id="tableResult"></table>
                          <div id="tableResultPager"></div>
                          
                          <br>
                          <div class="col-sm-2 ">
                              <button id="btnNueva" name="btnNueva" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-plus"></i>   Nueva</button>
                          </div>
                      </div>      
                    </div>
                </div>                
            </div>
        </div>

        <div id="editDialog" title="">
            <form id ="formDialog" name="formDialog" class="form-horizontal" autocomplete="off" action="javascript:guardarTarea()"  method="post" enctype="multipart/form-data">
                <fieldset>
                
                <!-- Cod Autorizacion-->
                <div>
                    <input type="text" id="guardarTipo" name="guardarTipo" hidden="true" value="0">
                    <!-- <input type="text" id="Tar_Cod" name="Tar_Cod" hidden="true"> -->
                </div>
                
                <!-- Creador -->
                <div class="form-group">
                  <label class="col-xs-4 control-label label-xs required" for="Rtp_Des">Descripci&oacute;n:</label>
                  <div class="col-xs-8">
                    <input id="Rtp_Des" name="Rtp_Des" type="text" class="form-control input-xs"/>
                  </div>
                </div>
                
                <!-- Buttons -->
                <div class="form-group">
                  <label class="col-md-4 control-label" for="btnModificar"></label>
                  <div class="col-md-8">
                      <button type="submit" id="btnAccion" name="btnAccion" class="btn btn-sm btn-primary"></button>
                  </div>
                </div>

                </fieldset>
            </form>
        </div>

        <div id="editModal" title="">
            <form id ="formModal" name="formModal" class="form-horizontal" autocomplete="off" action="javascript:guardarTarea()"  method="post" enctype="multipart/form-data">
                <fieldset>
                
                <!-- Cod Autorizacion-->
                <div>
                    <!-- <input type="text" id="editarTipo" name="editarTipo" hidden="true" value="0"> -->
                    <input type="text" id="Rtp_Cod" name="Rtp_Cod" hidden="true">
                </div>
                
                <!-- Creador -->
                <div class="form-group">
                  <label class="col-xs-4 control-label label-xs required" for="Rtp_Des_Edit">Descripci&oacute;n:</label>
                  <div class="col-xs-8">
                    <input id="Rtp_Des_Edit" name="Rtp_Des_Edit" type="text" class="form-control input-xs"/>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-xs-4 control-label label-xs required" for="Rtp_Est_Edit">Estado:</label>
                  <div class="col-xs-8">
                    <select id="Rtp_Est_Edit" name="Rtp_Est_Edit" class="form-control input-xs">
                      <option value="A">ACTIVO</option>
                      <option value="I">INACTIVO</option>
                    </select>
                  </div>
                </div>
                
                <!-- Buttons -->
                <div class="form-group">
                  <label class="col-md-4 control-label" for="btnModificar"></label>
                  <div class="col-md-8">
                      <button type="submit" id="btnEditar" name="btnEditar" class="btn btn-sm btn-primary"></button>
                  </div>
                </div>

                </fieldset>
            </form>
        </div>


        <div id="documentoMain" class="panel panel-main">
        	<div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Control de Tareas Ofsercont S.A </h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="fila" class="row">

                  <!-- seccion informativa de la tarea -->
                		<div class="col-xs-12">
                			<fieldset class="exa-fieldset">
			                  	<legend class="Titulos2"> Datos de la Tarea</legend>

			                  <div id="cargarDoc" class="col-xs-4">
			                  	<label class="col-xs-5 control-label label-xs text-info" for="Tar_Fec_Cre">Creacion:</label>
			                  	<p class="col-xs-7" id="Tar_Fec_Cre" name="Tar_Fec_Cre"></p>

			                  </div>

			                  <div class="col-xs-8">
			                  		<div class="col-xs-12">
			                  			<textarea id="Tar_Des_Re" name="Tar_Des" class="form-control" rows="10" readonly></textarea>	
			                  		</div>	
			                  </div>
			                 </fieldset>
			            </div>

                  <!--Seeccion para guardar la tarea realizada -->
                  <form id ="formDialogRe" name="formDialogRe" class="form-horizontal" autocomplete="off" action="javascript:guardar()" method="post" enctype="multipart/form-data">
  			            
                    <div>
                        <input type="text" id="Tar_Cod_Re" name="Tar_Cod_Re" hidden="true">
                        <input type="text" id="Tad_Cod_Mod" name="Tad_Cod_Mod" hidden="true">
                    </div>
                    
                    <div class="form-group">
                      <div class="col-xs-12">
                         <div class="col text-center">
                            <a class="black btn btn-md btn-inverse" onclick="atras();" ><i class="glyphicon glyphicon-arrow-left"></i>Atr&aacute;s</a>
                            <button type="submit" id="saveButton" class="btn btn-md btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i>Guardar</button>
                          </div>
                      </div>
                    </div>

                  </form>
                  <!--FIN para guardar la tarea realizada -->

                </div>

            </div>
        </div>

        <script type="text/ecmascript" src="/Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
        <script type="text/javascript" src="/framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/ecmascript" src="/Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
        <script language="javascript" src="/Librerias/validaciones/validacion.js"></script>
        <script type="text/javascript" src="/framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script language="javascript" src="../../VALIDACIONES/requisiciones/tipos.js?x=x11"></script>
        <script type="text/javascript">
        	$('#documentoMain').hide();
          loadTasks();    	
        </script>
    </BODY>
</HTML>