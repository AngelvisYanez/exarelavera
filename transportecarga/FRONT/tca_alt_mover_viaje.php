<?php
/**
 * @abstract Permite realizar la modificacion de viajes a un cliente
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de creación 2018-09-06
 *
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tca_log_viaje.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Viaje($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas
 */
$obBD_con1 = new Class_Log_Datos_Viaje;

//Busqueda de viajes
//if(isset($viajesAjax)){
//   $obBD_con1->echoLog("viajes ajax");
//   $data = array_merge($_GET, array('setWhere'=>array(/*'byCliente',*/'byPersona', 'setEmpCod', 'isActive', /*'isPersona',*/ /*'notHasVetCod','byGroup','byOrder'*/)));
//   $responce= $obBD_con1->getPageGridJson('viaje.selectWhere', $data, $obBD_conexion, true);
//
//}

//busqueda viajes rapido
if(isset($bucarAjax)){
    $busqueda = array(
        'success' => true,
        'todosViajes'=>$obBD_con1->getArrayConsulta('viaje.selectWhere', array('viaje.Cli_Cod'=>$Cli_Cod, 'setWhere'=>array(/*'byChofer',*//*'byPersona',*//*'byCargamento',*//*'byModoTrabajo',*/'isActive', /*'byVehiculo',*/ 'notHasVetCod')),$obBD_conexion),
    );
    $obBD_con1->echoJson($busqueda);
}

//busqueda cargamento
if(isset($cargmentoAjax)){
    $buscar = array(
        'success'=> true,
        'cargamentoEncontrado'=>$obBD_con1->getRowConsulta('cargamento.selectwhere', array('cargamento.Car_Cod'=>$Car_Cod, 'setWhere'=>array(/*'byProducto','byItem','byCategorias',*/'isActive','setEmpCod')),$obBD_conexion, true),
    );
    $obBD_con1->echoJson($buscar);

}

///Sección para cargar datos en el Jqgrid referente a los productos registrado
if (isset($productoAjax)) {
   $data = filter_input_array(INPUT_GET);
   $data["Emp_Cod"] = $Ses_Emp_Cod;
   $contar = $obBD_con1->getRowConsulta(6, $data, $obBD_conexion,true);
   $pagination = pages($contar['total'], $page, $rows);
   $responce = $pagination['data'];
   $data["limits"] = $pagination['limits'];
   if ($contar['total'] > 0) {
       $responce['rows'] = $obBD_con1->getArrayConsulta(6, $data, $obBD_conexion,true);
   }
   echo json_encode($responce);
   exit();
}

//Sección para cargar datos en el Jqgrid referente al personal registrado
if (isset($personaAjax)) {
   $data = filter_input_array(INPUT_GET);
   $data["Emp_Cod"] = $Ses_Emp_Cod;
   $contar = $obBD_con1->getRowConsulta(9, $data, $obBD_conexion,true);
   $pagination = pages($contar['total'], $page, $rows);
   $responce = $pagination['data'];
   $data["limits"] = $pagination['limits'];
   if ($contar['total'] > 0) {
       $responce['rows'] = $obBD_con1->getArrayConsulta(9, $data, $obBD_conexion,true);
   }
   utf8_encode_deep($responce);
  echo json_encode($responce);
   exit();
}

//Sección para cargar datos en el Jqgrid referente a los clientes registrados
if (isset($viajeAjax)) {
   $data = filter_input_array(INPUT_GET);
   $data["Emp_Cod"] = $Ses_Emp_Cod;
   $contar = $obBD_con1->getRowConsulta(19, $data, $obBD_conexion,true);
   $pagination = pages($contar['total'], $page, $rows);
   $responce = $pagination['data'];
   $data["limits"] = $pagination['limits'];
   if ($contar['total'] > 0) {
       $responce['rows'] = $obBD_con1->getArrayConsulta(19, $data, $obBD_conexion,true);
   }
  utf8_encode_deep($responce);
   echo json_encode($responce);
   exit();
}

/*Sección para verificar si una persona ya se encuentra registrada como chofer*/
if(isset($verificarCho)){
   $rs_chofer=$obBD_con1->getRowConsulta(18,$Prs_Cod.'*'.$Ses_Emp_Cod,$obBD_conexion,true);
   if(!empty($rs_chofer['Prs_Cod'])){$response['existe']=true;}
   else{$response['existe']=false;}
   echo json_encode($response);
   exit();
}

/*Sección para buscar una persona según el número de cédula*/
if(isset($buscarCliente)){
   $longitud=  strlen($Prs_Ced);
   if($longitud*1===13){$Prs_Ced = substr($Prs_Ced, 0, -3);}
   $response=$obBD_con1->getRowConsulta(21,$Prs_Ced,$obBD_conexion,true);
   (!empty($response['Prs_Cod']))?$response['existe']=true:$response['existe']=false;
   utf8_encode_deep($response);
  echo json_encode($response);
   exit();
}

/*Sección para listar los viajes de un cliente*/
if(isset($cargarViajes)){
   $response=$obBD_con1->getArrayConsulta(22,$Cli_Cod.'*'.$Fecha.'*'.$Fec_Ini.'*'.$Fec_Fin,$obBD_conexion,true);
   $obBD_con1->echoLog($response);
   foreach ($response as &$row){
       $Via_Tot=$row['Via_Can']*$row['Via_Pru'];
       $row['Via_Tot']=  number_format($Via_Tot,2,".","");
   }unset($row);
  utf8_encode_deep($response);
   echo json_encode($response);

   exit();
}

/*Sección para guardar datos de los agregar*/
if(isset($save)){
   $response['success'] = false;
   $response['message'] = "No se ha logrado realizar la Transaccion # 1";

   $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

   if(isset($saveCargamento)){$case=4;$consultar=2;$datos=$Car_Des.'*'.$Pro_Cod;}              //Sección para registrar un cargamento
   if(isset($saveModo)){$case=5;$consultar=3;$datos=$Mot_Des.'*'.$Ses_Emp_Cod;}                                 //Sección para registrar un modo_trabajo
   if(isset($saveAutomotor)){$case=7;$consultar=8;$datos=$Ses_Emp_Cod.'*'.$Veh_Mar.'*'.$Veh_Pla.'*'.$Veh_Col;}  //Sección para registrar un vehiculo
   if(isset($saveChofer)){
       $longitud=strlen($Prs_Ced);
       $rs_Ide_Cod = $obBD_con1->getRowConsulta(13,$longitud, $obBD_conexion,true);
       $Ide_Cod=$rs_Ide_Cod['Ide_Cod'];

       if(empty($Prs_Cod)){
           $obBD_con1->operacionobBD(14,$Ciu_Cod.'*'.$Ide_Cod.'*'.$Prs_Ced.'*'.$Prs_Nom.'*'.$Prs_Ape.'*'.$Prs_Dir, $obBD_conexion,true);
           $Prs_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
       }
       $case=10;$consultar=15;$datos=$Prs_Cod.'*'.$Ses_Emp_Cod.'*'.$Cho_Tli;
   }                                                                                           //Sección para registrar un chofer

   $obBD_con1->operacionobBD($case,$datos, $obBD_conexion);
   $codigo=$obBD_con1->insercionid($obBD_conexion->conexion);
   $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

   if ($obBD_con1->Error == 0) {$response['registro']=$obBD_con1->getRowConsulta($consultar,$codigo.'*'.$Ses_Emp_Cod,$obBD_conexion,true);$response['success'] = true;}
   echo json_encode($response);
   exit();
}

/*Sección para registrar un viaje*/
if(isset($saveViaje)){
   $obBD_con1->echoLog("mejoraProf");
   $response['success'] = false;
   $response['message'] = "No se ha logrado realizar la Transaccion # 2";
   $obBD_con1->debug(true);
   $obBD_con1->inicio_transaccion($obBD_conexion->conexion,true);
   $obBD_con1->echoLog($Cli_Cod);
   foreach ($campos as $valor){
       $obBD_con1->echoLog('entro en el else ');
       $obBD_con1->echoLog($Cli_Cod_Dest);
       $obBD_con1->operacionobBD(36,$valor['Via_Cod'].'*'.$Cli_Cod_Dest, $obBD_conexion,true);
   }
   $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
   if ($obBD_con1->Error == 0) {$response['success'] = true;}
   echo json_encode($response);
   exit();
}
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script language="javascript" src="../VALIDACIONES/tca_mover_viajes.js?=147"></script>
        <style>
        .chosen-drop .chosen-results {
            max-height: 70px;
        }
        .ui-jqgrid td input, .ui-jqgrid td select, .ui-jqgrid td textarea {padding-top: 2px;}
        .ret .input-group-btn button{padding: 1px 2px !important;}
        </style>
        <script>
            <?php   $car=$obBD_con1->getArrayConsulta(2,"".'*'.$Ses_Emp_Cod,$obBD_conexion);
                    $mod=$obBD_con1->getArrayConsulta(3,"".'*'.$Ses_Emp_Cod,$obBD_conexion);
                    $arr_con=$obBD_con1->getArrayConsulta(15,"".'*'.$Ses_Emp_Cod,$obBD_conexion);
                    $arr_aut=$obBD_con1->getArrayConsulta(8,"".'*'.$Ses_Emp_Cod,$obBD_conexion);    ?>

                    var arr_cho=<?php echo json_encode($arr_con);?>;
                    var arr_aut=<?php echo json_encode($arr_aut);?>;
        </script>
   </HEAD>
   <BODY>
      <div class="panel panel-main">
         <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Mover Viajes</h3></div>
         <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <form id="frm_viajes" name="frm_viajes" class="form-horizontal normal" action="javascript:confirmaGuardado();">
               <select id="car_cod" name="car_cod" class="form-control input-xs select_carga" style="display: none;">
                  <?php foreach ($car as $row){?>
                  <option   value="<?php echo $row['Car_Cod'];?>"><?php echo utf8_decode($row['Car_Des']);?></option>
                  <?php }?>
               </select>
               <select id="mot_cod" name="mot_cod" class="form-control input-xs select_modo" style="display: none;">
                  <?php foreach ($mod as $row){?>
                  <option  value="<?php echo $row['Mot_Cod'];?>"><?php echo utf8_decode($row['Mot_Des']);?></option>
                  <?php }?>
               </select>
               <div class="row">
                  <div class="col-md-12">
                     <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Formulario para cambios de viajes</legend>
                        <div class="col-md-6 col-sm-8">
                           <legend class="Titulos2">Datos del Cliente</legend>
                           <div class="form-group">
                              <label class="control-label col-md-3 col-sm-4 label-xs required">Cliente:</label>
                              <input type="text" id="Cli_Cod" name="Cli_Cod" style="display:none;">
                              <div class="col-md-7 col-sm-7">
                                 <div class="input-group">
                                    <input type="text" id="cliente" name="cliente" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="">
                                    <span class="input-group-btn">
                                       <button id="btnCarga" class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="$('#viajeDialog').dialog('open');" style="z-index: 1;"><span class="glyphicon glyphicon-search"></span></button>
                                    </span>
                                 </div>
                              </div>
                           </div>
                           <div class="form-group">
                              <label class="control-label col-sm-3 label-xs">C&eacute;dula/RUC:</label>
                                 <div class="col-sm-5">
                                    <input type="text" id="Prs_Ced" name="Prs_Ced" class="form-control input-xs" readonly="">
                                 </div>
                           </div>

                           <div class="form-group">
                              <label class="control-label col-sm-3 label-xs">Direcci&oacute;n:</label>
                                 <div class="col-sm-7">
                                    <input type="text" id="Prs_Dir" name="Prs_Dir" class="form-control input-xs" readonly="">
                                 </div>
                           </div>
                        </div>
                        <div class="col-md-6 col-sm-8">
                           <legend class="Titulos2">Datos del Cliente Destino</legend>
                           <div class="form-group">
                              <label class="control-label col-md-3 col-sm-4 label-xs required">Cliente:</label>
                              <input type="text" id="Cli_Cod_Dest" name="Cli_Cod_Dest" style="display:none;">
                              <input type="text" id="Via_Des_Dest" name="Via_Des_Dest" style="display:none;">
                              <input type="text" id="viajes_Dest" name="viajes_Dest" style="display:none;">
                              <div class="col-md-7 col-sm-7">
                                 <div class="input-group">
                                    <input type="text" id="cliente_Dest" name="cliente_Dest" class="form-control input-xs" placeholder="Seleccione el cliente destino" readonly="">
                                    <span class="input-group-btn">
                                       <button id="btnSelecciona" class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="$('#viajeDialog').dialog('open');" style="z-index: 1;"><span class="glyphicon glyphicon-user"></span></button>
                                    </span>
                                 </div>
                              </div>
                           </div>
                           <div class="form-group">
                              <label class="control-label col-sm-3 label-xs">C&eacute;dula/RUC:</label>
                                 <div class="col-sm-5">
                                    <input type="text" id="Prs_Ced_Dest" name="Prs_Ced_Dest" class="form-control input-xs" readonly="">
                                 </div>
                           </div>
                           <div class="form-group">
                              <label class="control-label col-sm-3 label-xs">Direcci&oacute;n:</label>
                                 <div class="col-sm-7">
                                    <input type="text" id="Prs_Dir_Dest" name="Prs_Dir_Dest" class="form-control input-xs" readonly="">
                                 </div>
                           </div>
                        </div>
                        <div class="col-md-6" style="display: none;">
                           <div class="form-group">
                              <label class="control-label col-sm-3 label-sm">Descripci&oacute;n:</label>
                              <div class="col-sm-7">
                                 <textarea id="Via_Des_Dest" name="Via_Des_Dest" class="form-control input-sm" style="resize: none;"></textarea>
                              </div>
                           </div>
                        </div>
                     </fieldset>
                  </div>
               </div>
               <div  id="tabla" class="row">
                  <div class="col-md-12">
                     <table id="Viajes_Grid"></table>
                     <div id="Viajes_Page"></div>
                  </div>
               </div>
               <div class="form-group Titulos2">
                  <div class="col-sm-8"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
               </div>
               <div style="text-align: center;padding-top: 5px;">
               <!--<button type="button" id="btn_guar" name="btn_guar" class="btn btn-primary btn-sm" onclick="getSelectedRows();"><span class="glyphicon glyphicon-floppy-disk"></span> dedos</butto>-->
                  <button type="button" id="btn_gua" name="btn_gua" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
               </div>
            </form>
         </div>
      </div>
      <!-- Inicio del diálogo para buscar un cliente -->
      <div id="viajeDialog" title="B&uacute;squeda de Clientes">
         <form class="form-horizontal normal" ></form>
      </div>
      <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
      <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
      <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
      <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
   </BODY>
</HTML>