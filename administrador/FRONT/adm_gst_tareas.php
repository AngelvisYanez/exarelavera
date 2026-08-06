<?php 
/**
 * Interfaz para llevar el control de tareas del area contable para Ofsercont
 *
 * @author Alejandro CAmacho
 * @version 1.0
 * Fecha de actualizacion:	2021/03/22
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_tareas.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');


/** 
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;
$hoy = date("Y-m-d H:i:s");
$mes = date("m");

	
  /*
   * Guardar y Modificar tarea
   */
   if(isset($modeTar))
   {    
   	    $_POST['Tar_Fec'] = $hoy;     

        if (isset($_FILES["Tar_Fil"]) && $_FILES["Tar_Fil"]['size'] > 0){
          $carpeta = "../tareas/$Ses_Emp_Cod/";

          if (!file_exists($carpeta)) {
              mkdir($carpeta, 0777, true);
          }

          //asignar un nombre de archivo unico para que no se confundan
          $extension = pathinfo($_FILES["Tar_Fil"]["name"], PATHINFO_EXTENSION);
          if($_POST["Tar_Cod"] != null){
            $nombreArchivo = "tarea" . $_POST["Tar_Cod"] . "." . $extension;
          }
          else{
            $Tar_Cod_Fut = $obBD_con1->getROWConsulta(11,  $Ses_Emp_Cod, $obBD_conexion);
            $nombreArchivo = "tarea" . ($Tar_Cod_Fut["Cod_Fut"] + 1) . "." . $extension;
          }

          $target_file = $carpeta . basename($nombreArchivo);

          // Verifica si existe el archivo
          if (file_exists($target_file)) unlink($target_file);
          // Comprueba el tamano del archivo
          if ($_FILES["Tar_Fil"]["size"] > 5242880) $obBD_con1->echoJson(array('success'=>false, 'message'=>'El archivo es demasiado grande!' ));
          // if everything is ok, try to upload file
          if (move_uploaded_file($_FILES["Tar_Fil"]["tmp_name"], $target_file)) {
              $_POST['Tar_Fil']= $target_file;

          }else $obBD_con1->echoJson(array('success'=>false, 'message'=>'No se pudo subir el archivo!' ));

      }

      $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
      if($modeTar=='1'){
        $_POST['Tar_Fec_Mod'] = $hoy;
        $obBD_con1->operacionobBD(5,$_POST,$obBD_conexion);
      }
      else{
        $obBD_con1->operacionobBD(3,$_POST,$obBD_conexion);
      }
      
      
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
     * Realizar y Modificar DESARROLLO de la Tarea
     */
     if(isset($Tad_Cod_Mod))
     {     

       $_POST['Tar_Fec_Env'] = $hoy;
          if (isset($_FILES["Tad_Fil"]) && $_FILES["Tad_Fil"]['size'] > 0){
              $carpeta = "../tareasRealizadas/$Ses_Emp_Cod/";

              if (!file_exists($carpeta)) {
                  mkdir($carpeta, 0777, true);
              }
              $extension = pathinfo($_FILES["Tad_Fil"]["name"], PATHINFO_EXTENSION);
              $nombreArchivo = "tareaRealizada" . $_POST["Tar_Cod_Re"] . "." . $extension;

              $target_file = $carpeta . basename($nombreArchivo);


              // Verifica si existe el archivo
              if (file_exists($target_file)) unlink($target_file);
              // Comprueba el tamano del archivo
              if ($_FILES["Tad_Fil"]["size"] > 5242880) $obBD_con1->echoJson(array('success'=>false, 'message'=>'El archivo es demasiado grande!' ));
              // if everything is ok, try to upload file
              if (move_uploaded_file($_FILES["Tad_Fil"]["tmp_name"], $target_file)) {
                  $_POST['Tad_Fil']= $target_file;

              }else $obBD_con1->echoJson(array('success'=>false, 'message'=>'No se pudo subir el archivo!' ));
          }

        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        if($_POST['Tad_Cod_Mod'] == 1){
          $obBD_con1->operacionobBD(9,$_POST,$obBD_conexion);
          $obBD_con1->operacionobBD(10,$_POST,$obBD_conexion);
        }
        else{
          $obBD_con1->operacionobBD(8,$_POST,$obBD_conexion);
          $obBD_con1->operacionobBD(10,$_POST,$obBD_conexion);
        }
        
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
     * Obtener Tareas
     */
    if(isset($searchFiltro))
    {
        $data = $obBD_con1->getArrayConsulta(4, $_GET, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows'=>$data,
            'total'=>1,
            'records'=>count($data),
            'success'=>true
        ));
        exit(); 
    }

    /*
     * Cambiar el estado de las tareas (anular y validar)
     */
    if(isset($setEstado))
    {
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(6,$_GET,$obBD_conexion);
        
        if($Tar_Validar){
          $obBD_con1->operacionobBD(13,array('Tar_Cod' => $Tar_Cod, 'Ses_Usu_Cod' => $Ses_Usu_Cod),$obBD_conexion);
        }

        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion))
        {
            $response['success'] = true;
        }else{ 
            $response['success'] = false; 
            $response['message'] = "No se ha logrado realizar la Transaccion";
        }
        $obBD_con1->echoJson($response);
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


    if(isset($reporte))
    {   
        $responce['success']=false;
        $table['{body}']='';
        $table['{empresa}']=$Ses_Emp_Nom;
        $hoy = date("Y-m-d");
        $fecha=explode('-',$hoy);
        $table['{fecha}']=$fecha[2].' de '.mes($fecha[1],1).' del '.$fecha[0];
        $responce['rows'] =$obBD_con1->getArrayConsulta(4, $_POST, $obBD_conexion);
        $tareas = $responce['rows'];

        foreach ($tareas as $tarea) 
        {
          $table['{body}'] = $table['{body}'] . '<tr>' 
                              . '<td>' . $tarea['Tar_Cod'] . '</td>'
                              . '<td>' . $tarea['Tar_Fec'] . '</td>'
                              . '<td>' . $tarea['Tar_Cre_Nom'] . '</td>'
                              . '<td>' . $tarea['Emp_Nom'] . '</td>'
                              . '<td>' . $tarea['Tar_Des'] . '</td>'
                              . '<td>' . $tarea['Tar_Res_Nom']. '</td>'
                              . '<td>' . $tarea['Tar_Fec_Ent'] . '</td>'
                              . '<td>' . $tarea['Tar_Fec_Env']. '</td>'
                              . '<td>' . $tarea['Tar_Est'] . '</td>'
                              . '</tr>';
        }
        $responce['html']=reporteHtml($table,'adm_gst_reporte.html');
        $responce['success']=true;
        utf8_encode_deep($responce);        
        echo json_encode($responce);
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
        <TITLE><?Php echo "Emp. Control Tarea [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        
        <script type="text/javascript">
          var jsUsuario = <?php echo $Ses_Usu_Cod; ?>;
        </script>  
        <script type="text/javascript">
          var jsUsuarioTipo = <?php if($usuarioTipo['Ust_Tip']!=""){echo "'" .$usuarioTipo['Ust_Tip'] . "'";}else{echo "''";} ?>;
        </script>

        <script type="text/javascript" src="../VALIDACIONES/adm_val_tareas.js?x=x11"></script>
    </HEAD>
    <BODY>
        <div id="documentoSearch" class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Control de Tareas Ofsercont S.A </h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-sm-12">

                                <form id="frm_alt_auto" name="frm_alt_auto" class="form-vertical" autocomplete="off">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Filtrar Tareas</legend>
                                          
                                        <!-- Fecha -->
                                        <div class="col-sm-3"> 
                                          <div class="input-group input-group-xs">
                                            <span class="input-group-addon bold alert-info">Desde:</span>
                                            <input name="txt_fec_ini" type="text" id="txt_fec_ini" class="form-control input-sm datepicker databind" style="text-align: center;"/>
                                            <span class="input-group-addon bold alert-info">Hasta:</span>
                                            <input name="txt_fec_fin" type="text" id="txt_fec_fin" class="form-control input-sm datepicker databind" style="text-align: center;"/>
                                          </div>
                                        </div>
                                        

                                        <!-- Responsable -->
                                        <div class="col-sm-4">
                                          <label class="col-sm-4 control-label label-xs">Responsable:</label>
                                          <div class="input-group input-group-xs">
                                            <select id="Tar_Res_Filtro" name="Tar_Res_Filtro" class="form-control input-xs">
                                               <option value="T">Todos</option>
                                                <?php 
                                                  foreach ($usuario as $usu) {
                                                      echo "<option  value='{$usu['Usu_Cod']}'>{$usu['persona']}</option>";
                                                  }
                                                  ?>
                                            </select>
                                          </div>
                                        </div>

                                        <!-- Estado -->
                                        <div class="col-sm-3">
                                        <label class="col-sm-3 control-label label-xs">Estado:</label>
                                          <div class="input-group input-group-xs">
                                            <select id="Tar_Est_Filtro" name="Tar_Est_Filtro" class="form-control input-xs">
                                                <option value="T">Todos</option>
                                                <option value="A">Activa</option>
                                                <option value="I">Inactiva</option>
                                                <option value="V">Validada</option>
                                                <option value="E">Entregada</option>
                                            </select>
                                          </div>
                                        </div>

                                        <!-- Button -->
                                        <div class="col-sm-1">
                                          <label class="control-label" for="">Acción</label>                                              
                                          <button id="btnSearch" name="btnSearch" class="btn btn-success">Buscar</button>                                              
                                        </div>
                                  
                                        <div class="col-sm-3"></div>
                                    </fieldset>                                    
                                </form>

                                <div >
                                    <table id="tableResult"></table>
                                    <div id="tableResultPager"></div>
                                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop green"></span> Validada |<span class="glyphicon glyphicon-stop red"></span> Inactivo | <span class="glyphicon glyphicon-stop blue"></span> Entregado </div>

                                    <br>
                                    <div class="col-sm-2 ">
                                        <button id="btnNueva" name="btnNueva" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-plus"></i>   Nueva</button>
                                    </div>
                                    <div class="col-sm-2 col-sm-offset-8 ">
                                          <button onclick="exportar(true)" title="Imprimir Reporte" type="button" class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></button>   
                                          <button onclick="exportar(false)" class="btn btn-primary start" title="Descargar archivo de Excel"> <i class="icon-share icon-white"></i> <span>Excel</span></button>          
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
                    <input type="text" id="modeTar" name="modeTar" hidden="true" value="0">
                    <input type="text" id="Tar_Cod" name="Tar_Cod" hidden="true">
                </div>
                
                <!-- Creador -->
                <div class="form-group">
                  <label class="col-xs-4 control-label label-xs required" for="Tar_Cre">Creador:</label>
                  <div class="col-xs-8">
                    <select id="Tar_Cre" name="Tar_Cre" class="form-control input-xs">
                        <?php 
                        foreach ($usuario as $usu) {
                          if($Ses_Usu_Cod == $usu['Usu_Cod']){
                            echo "<option  value='{$usu['Usu_Cod']}'>{$usu['persona']}</option>";
                          }
                        }
                        ?>
                    </select>
                  </div>
                </div>

                <!-- Tipo de Documento -->
                <div class="form-group">
                  <label class="col-xs-4 control-label label-xs required" for="Tar_Res">Responsable:</label>
                  <div class="col-xs-8">
                    <select id="Tar_Res" name="Tar_Res" class="form-control input-xs">
                        <?php 
                        foreach ($usuario as $usu) {
                          if($usuarioTipo['Ust_Tip']=='A' || $usuarioTipo['Ust_Tip']=='T'){
                            echo "<option  value='{$usu['Usu_Cod']}'>{$usu['persona']}</option>";
                          }
                          else{
                             if($Ses_Usu_Cod == $usu['Usu_Cod']){
                              echo "<option  value='{$usu['Usu_Cod']}'>{$usu['persona']}</option>";
                            }
                          }
                        }
                        ?>
                    </select>
                  </div>
                </div>

                <!-- Nro Emision -->
                <div class="form-group">
                  <label class="col-md-4 control-label label-xs required" for="Emp_Cod">Empresa:</label>
                  <div class="col-md-8">
                    <select id="Emp_Cod" name="Emp_Cod" class="form-control input-xs">
                      <?php 
                        foreach ($empresas as $emp) {
                            echo "<option  value='{$emp['Emp_Cod']}'>{$emp['Emp_Cor']}</option>";
                        }
                        ?>
                    </select>
                  </div>
                </div>
 
                <!-- Archivo-->
                <div class="form-group">
                  <label class="col-md-4 control-label label-xs" for="Tar_Fil">Archivo:</label>  
                  <div class="col-md-8">
                      <input id="Tar_Fil" name="Tar_Fil" type="file" placeholder="" class="form-control input-xs"/>
                  </div>
                </div>
                
                <!-- Fecha Fin-->
                <div class="form-group">
                  <label class="col-md-4 control-label label-xs required" for="Tar_Fec_Ent">Fecha Entrega:</label>  
                  <div class="col-md-8">
                  <input id="Tar_Fec_Ent" name="Tar_Fec_Ent" type="text" placeholder="" class="form-control input-xs" required>
                  </div>
                </div>

                <!--Descripcion de la tarea-->
                <div class="form-group"> 
                  <div class="col-sm-12">
                  	  <textarea id="Tar_Des" name="Tar_Des" rows="14" cols="55" placeholder="Descripcion de la tarea" required></textarea>	
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

			                  	<label class="col-xs-5 control-label label-xs text-info" for="Tar_Cre_Re">Creador:</label>
			                  	<p class="col-xs-7" id="Tar_Cre_Re" name="Tar_Cre_Re"></p>

			                  	<label class="col-xs-5 control-label label-xs text-info" for="Tar_Res_Re">Responsable:</label>
			                  	<p class="col-xs-7" id="Tar_Res_Re" name="Tar_Res_Re"></p>

			                  	<label class="col-xs-5 control-label label-xs text-info" for="Tar_Val_Re">Validador:</label>
			                  	<p class="col-xs-7" id="Tar_Val_Re" name="Tar_Val_Re"></p>

			                  	<label class="col-xs-5 control-label label-xs text-info" for="Emp_Cod">Empresa:</label>
			                  	<p class="col-xs-7" id="Emp_Cod_Re" name="Emp_Cod"></p>

			                  	<label class="col-xs-5 control-label label-xs text-info" for="Emp_Cod">Fecha Entrega:</label>
			                  	<p class="col-xs-7" id="Tar_Fec_Ent_Re" name="Tar_Fec_Ent"></p>

			                  	<label class="col-xs-5 control-label label-xs text-info" for="Emp_Cod">Archivo:</label>
			                  	<a id='Tar_Fil_Re' name='Tar_Fil_Re' class="btn btn-warning btn-sm" href="" download><i class='glyphicon glyphicon-download'></i> Descargar Archivo</a>
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
      						    	<label for="Tad_Des" class="col-xs-12 col-form-label text-left required">Desarrollo: </label>
      							    <div class="col-xs-12">
      							      <textarea id="Tad_Des" name="Tad_Des" class="form-control" rows="5" disabled="false" required></textarea>
      							    </div>
        							</div>
                    </div>

                    <div class="form-group">
        							<div class="col-xs-12">
        							    <label for="Tad_Obs" class="col-xs-12 col-form-label text-left">Observacion: </label>
        							    <div class="col-xs-12">
        							     <textarea id="Tad_Obs" name="Tad_Obs" class="form-control" rows="3" disabled="false"></textarea>
        							    </div>
        							</div>
                    </div>

                    <div class="form-group">
        							<div class="col-xs-12">
        							    <label for="Tad_Fil" class="col-xs-12 col-form-label text-left">Archivo (Sustento): </label>
        							    <div class="col-xs-12">
        							     <input type="file"  class="form-control-file" id="Tad_Fil" name="Tad_Fil">
                          </div>  
        							</div>
                    </div>

                    <div class="form-group">
                      <div class="col-xs-12">
                        <div class="col-xs-12">
                             <a id='Tad_Fil_Sub' name='Tad_Fil_Sub' class="btn btn-warning btn-sm" href="" download><i class='glyphicon glyphicon-download'></i> Descargar Archivo</a>
                        </div>
                      </div>
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

        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>

        <script type="text/javascript">
        	$('#documentoMain').hide();
          loadTasks();    	
        </script>
    </BODY>
</HTML>