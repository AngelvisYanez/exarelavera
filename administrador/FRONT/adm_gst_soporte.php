<?php 
/**
 * Interfaz para llevar el control de tareas del area contable para Ofsercont
 *
 * @author Alejandro CAmacho
 * @version 1.0
 * Fecha de actualizaci�n:	2021/04/2021
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_soporte.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Spt($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Spt($obBD_conexion);

$hoy = date("Y-m-d H:i:s");
$mes = date("m");
	
  /* Guardar y Modificar tarea */
  if(isset($save)) {
      $_POST['Tic_Fec_Ter'] = $hoy;     
      $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
      $obBD_con1->operacionobBD(5,$_POST,$obBD_conexion);
      if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) { 
          $responce['success'] = true;            
      } else {
          $responce['success'] = false;
          $responce['message'] = "No se ha logrado realizar la Transacción";
      }
      $obBD_con1->echoJson($responce);
      exit(); 
  }

    /* Realizar y Modificar DESARROLLO de la Tarea */
    if(isset($Tic_Cod_Mod)) {
      $_POST['Tic_Fec_Env'] = $hoy;
          if (isset($_FILES["Tic_Evi_Sol_Arc"]) && $_FILES["Tic_Evi_Sol_Arc"]['size'] > 0){
              $carpeta = "../ticketsSolucionados/$Ses_Emp_Cod/";

              if (!file_exists($carpeta)) {
                  mkdir($carpeta, 0777, true);
              }
              $extension = pathinfo($_FILES["Tic_Evi_Sol_Arc"]["name"], PATHINFO_EXTENSION);
              $nombreArchivo = "tareaRealizada" . $_POST["Tic_Cod_Re"] . "." . $extension;

              $target_file = $carpeta . basename($nombreArchivo);

              // Verifica si existe el archivo
              if (file_exists($target_file)) unlink($target_file);
              // Comprueba el tamano del archivo
              if ($_FILES["Tic_Evi_Sol_Arc"]["size"] > 5242880) $obBD_con1->echoJson(array('success'=>false, 'message'=>'El archivo es demasiado grande!' ));
              // if everything is ok, try to upload file
              if (move_uploaded_file($_FILES["Tic_Evi_Sol_Arc"]["tmp_name"], $target_file)) {
                  $_POST['Tic_Evi_Sol_Arc']= $target_file;

              }else $obBD_con1->echoJson(array('success'=>false, 'message'=>'No se pudo subir el archivo!' ));
          }

        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        if($_POST['Tic_Cod_Mod'] == 1){
          $obBD_con1->operacionobBD(9,$_POST,$obBD_conexion);
          $obBD_con1->operacionobBD(10,$_POST,$obBD_conexion);
        } else{
          $obBD_con1->operacionobBD(8,$_POST,$obBD_conexion);
          $obBD_con1->operacionobBD(10,$_POST,$obBD_conexion);
        }
        
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) { 
            $responce['success'] = true;            
        } else {
            $responce['success'] = false;
            $responce['message'] = "No se ha logrado realizar la Transacción";
        }
        $obBD_con1->echoJson($responce);
        exit(); 
    }

    /* Obtener Tareas */
    if(isset($searchFiltro)) {
        $data = $obBD_con1->getArrayConsulta(4, $_GET, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array( 'rows'=>$data, 'total'=>1, 'records'=>count($data), 'success'=>true ));
        exit(); 
    }

    /* Cambiar el estado de las tareas (anular y validar) */
    if(isset($setEstado)) {
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(6,$_GET,$obBD_conexion);
        
        if($Tic_Validar){
          $obBD_con1->operacionobBD(13,array('Tic_Cod' => $Tic_Cod, 'Ses_Usu_Cod' => $Ses_Usu_Cod),$obBD_conexion);
        }

        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) {
            $response['success'] = true;
        }else{ 
            $response['success'] = false; 
            $response['message'] = "No se ha logrado realizar la Transaccion";
        }
        $obBD_con1->echoJson($response);
        exit(); 
    }

    if(isset($cargarDoc)) {
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

    if(isset($cargarEditar)) {
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
    


	$usuario = $obBD_con1->getArrayConsulta(1,  $Ses_Emp_Cod, $obBD_conexion);
	$empresas = $obBD_con1->getArrayConsulta(2,  $Ses_Emp_Cod, $obBD_conexion);
  $usuarioTipo = $obBD_con1->getRowConsulta(12,  $Ses_Usu_Cod, $obBD_conexion);

?>

<!DOCTYPE html>
<HTML>
    <HEAD>  
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Empresa Soporte [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        
        <script type="text/javascript">
          var jsUsuario = <?php echo $Ses_Usu_Cod; ?>;
        </script>  
        <script type="text/javascript">
          var jsUsuarioTipo = <?php if($usuarioTipo['Ust_Tip']!=""){echo "'" .$usuarioTipo['Ust_Tip'] . "'";}else{echo "''";} ?>;
        </script>
        <script src="../../Librerias/socketio/socket.io.min.js"></script> 
        <script type="text/javascript" src="../VALIDACIONES/adm_val_soporte.js?x=x15"></script>
        <style>
          .checked {
            color: orange;
          }
          /* #Label_Tic_Tel {
            padding-left: 30px !important;
          } */
          #Label_Tic_Evi_Pro {
            padding-left: 0px !important;
          }
        </style>
    </HEAD>
    <BODY>
        <div id="documentoSearch" class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Gesti&oacute;n de Tickets Ofsercont S.A </h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-sm-12">
                        <form id="frm_alt_auto" name="frm_alt_auto" class="form-vertical" autocomplete="off">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtrar Tickets</legend>
                                <div class="form-group">
                                  <label class="col-sm-1 control-label label-xs required" for="Tic_Fech_Cre_Sch" style="margin-top: -3px; margin-right: -40px;">Inicio:</label>  
                                  <div class="col-sm-1" style="width: 150px;">
                                    <input id="Tic_Fech_Cre_Sch" name="Tic_Fech_Cre_Sch" type="text" placeholder="" class="form-control input-xs" style="text-align: center;" required>
                                  </div>
                                  <label class="col-sm-1 control-label label-xs required" for="Tic_Fech_Cre_Sch" style="margin-top: -3px; margin-right: -55px;">Fin:</label>  
                                  <div class="col-sm-1" style="width: 150px;">
                                    <input id="Tic_Fech_Ter_Sch" name="Tic_Fech_Cre_Sch" type="text" placeholder="" class="form-control input-xs" style="text-align: center;" required>
                                  </div>
                                  <label class="col-sm-1 control-label label-xs" style="margin-top: -3px; margin-right: -40px;">Estado</label>
                                  <div class="col-sm-1" style="width: 150px;">
                                    <select id="Tic_Est_Sch" name="Tic_Est_Sch" class="form-control input-xs" style="text-align: center;">
                                        <option value="9"><< Todos >></option>
                                        <option value="0">- Pendiente -</option>
                                        <option value="1">- En Proceso -</option>
                                        <option value="3">- Solucionado -</option>
                                    </select>
                                  </div>
                                  <label class="col-sm-1 control-label label-xs" style="margin-top: -3px; margin-right: -40px;">Modulo:</label>
                                  <div class="col-sm-1" style="width: 150px;">
                                    <select id="Org_Mod_Sch" name="Org_Mod_Sch" class="form-control input-xs" style="text-align: center;">
                                        <option value="T"><< Todos >></option>
                                    </select>
                                  </div>
                                  <div class="col-sm-2">                                             
                                    <button id="btnSearch" name="btnSearch" class="btn btn-success btn-sm" style="margin-top: -3px;">Buscar</button>
                                  </div>
                                </div>
                            </fieldset>
                          </form>
                        <div >
                            <table id="tableResult"></table>
                            <div id="tableResultPager"></div>
                            <div class="Titulos2">
                              <span id="plan-footer">
                                <strong>Leyenda:</strong>
                                <span class="glyphicon glyphicon-stop orange"></span> Pendiente | <span class="glyphicon glyphicon-stop yellow"></span> En Proceso | <span class="glyphicon glyphicon-stop green"></span> Solucionado </div>

                            <!-- <BR>
                            <div class="col-sm-2">
                                <button id="btnNueva" name="btnNueva" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-plus"></i>   Nueva</button>
                            </div> -->
                        </div>
                        <div class="col-sm-1" style="margin-top: 5px;">                                             
                          <button id="btnExcel" name="btnExcel" class="btn btn-primary btn-sm"> <i class="glyphicon glyphicon-download-alt"></i> Excel</button>
                        </div>
                    </div>
                </div>                
            </div>
        </div>

        <div id="documentoMain" class="panel panel-main">
        	<div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Control de Tickets Ofsercont S.A </h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="fila" class="row">
                  <!-- seccion informativa de la tarea -->
                		<div class="col-xs-12">
                			<fieldset class="exa-fieldset">
			                  	<legend class="Titulos2"> Datos del Ticket</legend>
                          <input type="text" id="Tic_Cod" name="Tic_Cod" hidden="true">
                          <input type="text" id="Emp_Cod" name="Emp_Cod" hidden="true">

			                  <div id="cargarDoc" class="col-xs-4">
                          <div class="row">
                            <label class="col-xs-5 control-label label-xs text-info" for="Tic_Fec_Cre">Creaci&oacute;n:</label>
                            <p class="col-xs-7" id="Tic_Fec_Cre" name="Tic_Fec_Cre"></p>
                          </div>
			                  	<div class="row">
                            <label class="col-xs-5 control-label label-xs text-info" for="Tic_Fec_Ter">Terminado:</label>
                            <p class="col-xs-7" id="Tic_Fec_Ter" name="Tic_Fec_Ter"></p>
                          </div>
                          <div class="row">
                            <label class="col-xs-5 control-label label-xs text-info" for="Prs_Nom">Creador:</label>
                            <p class="col-xs-7" id="Prs_Nom" name="Prs_Nom"></p>
                          </div>
                          <div class="row">
                            <label class="col-xs-5 control-label label-xs text-info" for="Emp_Cod">Empresa:</label>
                            <p class="col-xs-7" id="Emp_Cod_Re" name="Emp_Cod"></p>
                          </div>
                          <div class="row">
                            <label id="Label_Tic_Tel" class="col-xs-5 control-label label-xs text-info" for="Tic_Tel">Tel&eacute;fono:</label>
                            <p class="col-xs-7" id="Tic_Tel" name="Tic_Tel"></p>
                          </div>
                          <div class="row">
                            <label class="col-xs-5 control-label label-xs text-info" for="Org_Mod">M&oacute;dulo:</label>
                            <p class="col-xs-7" id="Org_Mod" name="Org_Mod"></p>
                          </div>
                          <div class="row">
                            <label class="col-xs-5 control-label label-xs text-info" for="Org_Sec">Secci&oacute;n:</label>
                            <p class="col-xs-7" id="Org_Sec" name="Org_Sec"></p>
                          </div>
                          <div class="row">
                            <label class="col-xs-5 control-label label-xs text-info" for="Tic_Tip">Tipo de Soporte:</label>
                            <div class="col-xs-6">
                              <select id="Tic_Tip" name="Tic_Tip" class="form-control input-xs">
                                <option value="Tecnico">T&eacute;cnico</option>
                                <option value="Uso">Uso</option>
                                <option value="Acceso">Acceso</option>
                              </select>
                            </div>
                          </div>
                          <div class="row">
                            <label class="col-xs-5 control-label label-xs text-info" for="Tic_Est">Estado:</label>
                            <div class="col-xs-6">
                              <select id="Tic_Est" name="Tic_Est" class="form-control input-xs">
                                <option value="Pendiente">Pendiente</option>
                                <option value="En Proceso">En Proceso</option>
                                <option value="Solucionado">Solucionado</option>
                              </select>
                            </div>
                          </div>
                          <div class="col-xs-11">
                            <label id="Label_Tic_Evi_Pro" class="col-xs-5 control-label label-xs text-info" for="Tic_Evi_Pro">Archivo:</label>
                            <a id='Tic_Evi_Pro' name='Tic_Evi_Pro' class="btn btn-warning btn-sm" href="" download><i class='glyphicon glyphicon-download'></i> Descargar Archivo</a>
                          </div>
			                  </div>

			                  <div class="col-xs-8">
			                  		<div class="col-xs-12">
			                  			<textarea id="Tic_Des" name="Tic_Des" class="form-control" rows="10" readonly></textarea>	
			                  		</div>	
			                  </div>
			                 </fieldset>
                       <fieldset class="exa-fieldset">
			                  	<legend class="Titulos2"> Calificaci&oacute;n</legend>

                          <div class="row">
                            <label class="col-xs-2 control-label label-xs text-info" for="Tic_Fec_Cre">Puntuaci&oacute;n:</label>
                            <div id="Tic_Cal" class="col-xs-2">
                            </div>
                          </div>
                          <div class="row">
                            <label class="col-xs-2 control-label label-xs text-info" for="Tic_Fec_Cre">Descripci&oacute;n:</label>
                            <p class="col-xs-4" id='Tic_Cal_Des' name='Tic_Cal_Des'></p>
                          </div>
              
			                 </fieldset>
			            </div>

                  <!--Seeccion para guardar la tarea realizada -->
                  <form id ="formDialogRe" name="formDialogRe" class="form-horizontal" autocomplete="off" action="javascript:guardar()" method="post" enctype="multipart/form-data">
  			            
                    <!-- <div class="form-group">
  			            	<div class="col-xs-12">
      						    	<label for="Tic_Des" class="col-xs-12 col-form-label text-left required">Desarrollo: </label>
      							    <div class="col-xs-12">
      							      <textarea id="Tic_Des" name="Tic_Des" class="form-control" rows="5" disabled="false" required></textarea>
      							    </div>
        							</div>
                    </div>
 -->
                    <div class="form-group">
        							<div class="col-xs-12">
        							    <label for="Tic_Obs" class="col-xs-12 col-form-label text-left">Observaci&oacute;n: </label>
        							    <div class="col-xs-12">
        							     <textarea id="Tic_Obs" name="Tic_Obs" class="form-control" rows="3" disabled="false"></textarea>
        							    </div>
        							</div>
                    </div>

                    <!-- <div class="form-group">
        							<div class="col-xs-12">
        							    <label for="Tic_Evi_Sol_Arc" class="col-xs-12 col-form-label text-left">Evidencia (Soluci&oacute;n): </label>
        							    <div class="col-xs-12">
        							     <input type="file"  class="form-control-file" id="Tic_Evi_Sol_Arc" name="Tic_Evi_Sol_Arc">
                          </div>
        							</div>
                    </div>

                    <div class="form-group">
                      <div class="col-xs-12">
                        <div class="col-xs-12">
                             <a id='Tic_Evi_Sol' name='Tic_Evi_Sol' class="btn btn-warning btn-sm" href="" download><i class='glyphicon glyphicon-download'></i> Descargar Archivo</a>
                        </div>
                      </div>
                    </div> -->

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

        <script type="text/javascript">
        	$('#documentoMain').hide();
          // ini();
          loadTasks(true);    	
        </script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    </BODY>
</HTML>