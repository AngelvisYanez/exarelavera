<?php 
/**
* Descripcion:          Modulo de Bodegas
* Fecha de creacion:    28/12/2019
* Desarrollador:  Santiago Ruiz
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/inv_log_bodega.php');
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

$hoy = date("Y-m-d");
$mes = date("m");


     if(isset($saveBodega))
     {  
        try{

        $dato = $obBD_con1->getArrayConsulta(1004, $Ses_Suc_Cod, $obBD_conexion);

        if($dato[0]['total']>0 && $data[3]['value']=='P'){
          $response=array('success'=>false,'message'=>"Solo puede existir una bodega principal");     
          $obBD_con1->echoJson($response);
          exit();
        }
        else
        {
            $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
            $response = $obBD_con1->operacionobBD(1003,array("suc_cod"=>$Ses_Suc_Cod, "bod_dir"=>$data[2]['value'], "bod_nom"=>$data[1]['value'], "bod_tip"=>$data[3]['value'], "bod_est"=>'A', "bod_cvt"=>$data[4]['value']),$obBD_conexion);

            $bod_cod = $obBD_con1->insercionid($obBD_conexion);

            foreach ($data as $key => $value){
                if($value['name'] == 'usu_cod'){
                 $obBD_con1->operacionobBD(31,array("usu_cod" => $value['value'], "bod_cod"=>$bod_cod),$obBD_conexion);
                }
            }                                
        }
        
        } catch (Exception $ex) {
            $obBD_con1->rollBack_nomsn($obBD_conexion);
            $response=array('success'=>false,'message'=>"No se pudo realizar la transacción");       
            $obBD_con1->echoJson($response); 
            exit();
        }

        if ($obBD_con1->Error == 0)
        {
          $response=array('success'=>true,'message'=>"La transacción se realizo con exito"); 
        }

        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
        $obBD_con1->echoJson($response); 
     }

    /*
     * Modifica bodega
     */
     if(isset($modifyAut))
     {         
        //$data=$_GET;
        $dato = $obBD_con1->getArrayConsulta(1004, $Ses_Suc_Cod, $obBD_conexion);
        
        $data=$_POST;
        if($dato[0]['total']>0 && $bod_tip=='P'){
          $responce['message'] = "Solo puede existir una bodega Principal";
        }
        else{
          $obBD_con1->operacionobBD(2,$_POST,$obBD_conexion);
          $obBD_con1->operacionobBD(33,array("bod_cod"=>$data['bod_cod']),$obBD_conexion);
          if(count($data)>6){       
            if (count($data['usu_cod'])>1){
                for($i=0; $i< count($data['usu_cod']); $i++)
                  $obBD_con1->operacionobBD(31,array("usu_cod" => $data['usu_cod'][$i], "bod_cod"=>$data['bod_cod']),$obBD_conexion); //save bodega x usuario
                }else{    
                  $obBD_con1->operacionobBD(31,array("usu_cod" => $data['usu_cod'], "bod_cod"=>$data['bod_cod']),$obBD_conexion);
            } 
          }           
        }

        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion))
        { 
            $responce['success'] = true;
            $responce['message'] = "La transacción se realizo con exito";       
        }
        else
        { 
            $responce['success'] = false;
            $responce['message'] = "No se ha logrado realizar la Transacción";
        }
        $obBD_con1->echoJson($responce);

     }


    function setDataUsers(&$arr,$obBD_con1,$obBD_conexion){
       foreach($arr as &$v){
            $perfiles = $obBD_con1->getArrayConsulta(32, array('bod_cod'=>$v['bod_cod']), $obBD_conexion);
            $v['Perfiles']=is_array($perfiles)&&!empty($perfiles)?array_map(function($e){ return $e['persona']; }, $perfiles):'';
       } unset($v);
    }

    
    if(isset($searchAll))
    {
       //$obBD_con1->debug(true);
       $data = $obBD_con1->getArrayConsulta(101, $Ses_Suc_Cod, $obBD_conexion);
       setDataUsers($data, $obBD_con1, $obBD_conexion);
       // Grid necesita este array
       $obBD_con1->echoJson(array(
           'rows'=>$data,
           'total'=>1,
           'records'=>count($data),
           'success'=>true
       ));
    }

    if(isset($getPerfilByUser))
    {
       $perfiles = $obBD_con1->getArrayConsulta(32, $_GET, $obBD_conexion);
       // Grid necesita este array
       $obBD_con1->echoJson(array(
           'perfiles'=>$perfiles,
           'success'=>true
       ));
    }
    

    /*
     * Get usuarios actovps registrados (por sucursal)
     */
    $usuarios = $obBD_con1->getArrayConsulta(200, array("Emp_Cod"=>$Ses_Emp_Cod, "Suc_Cod"=> $Ses_Suc_Cod), $obBD_conexion);
    $tipo = array('P' => 'Principal','S'=>'Secundario');
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Bodega Registrar [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>    
        <script type="text/javascript" src="../VALIDACIONES/inv_val_bodega.js"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Bodegas</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-sm-12">
                        <div id="tabsSearch" class="ui-tab-fix ui-tabs">
                            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                              <li><a href="#tabs-1">Gestion de Bodegas</a></li>                            
                            </ul>
                            <div id="tabs-1" class="ui-tabs-panel ui-widget-content ui-corner-bottom" style="min-height: 350px;">
                               
                                <div >
                                    <TABLE id="tableResult">
                                        
                                    </TABLE>
                                    <div id="tableResultPager"> 
                                        
                                    </div>
                                    <BR>
                                    <div class="col-sm-2">
                                        <button id="btnNueva" name="btnNueva" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-plus"></i>   Nueva</button>
                                    </div>
                                </div>
                            </div>                          
                        </div>
                                  
                    </div>
                </div>                
            </div>
        </div>
        <div id="editDialog" title="">
            <form id="formDialog" name="formDialog" class="form-horizontal" autocomplete="off">              
                <fieldset >
                <div class="form-group Titulos2">
                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                </div>
                
                <!-- Cod Notificacion-->
                <div>
                   <input type="text" id="bod_cod" name="bod_cod" hidden="true">
                </div> 

                 <!-- Eempresa -->
                <div class="form-group">
                  <label class="col-xs-4 control-label label-xs required">Nombre:</label>
                  <div class="col-md-8">
                  <input id="bod_nom" name="bod_nom" type="text" placeholder="" class="form-control input-xs" >
                  </div>
                </div>

                <!-- Fecha Inicio-->
                <div class="form-group">
                  <label class="col-md-4 control-label label-xs required" >Direccion:</label>  
                  <div class="col-md-8">
                  <input id="bod_dir" name="bod_dir" type="text" placeholder="" class="form-control input-xs" >
                  </div>
                </div>
                
                <!-- Fecha Fin-->
                <div class="form-group">
                  <label class="col-md-4 control-label label-xs required" for="Bod_Tip" >Tipo:</label>  
                  <div class="col-md-8">
                  <select  id="bod_tip" name="bod_tip" class="form-control input-xs">
                        <option value="P">Principal</option>
                        <option value="S">Secundaria</option>
                    </select>
                  </div>
                </div>
                
                <!-- Encabezado-->
                <div class="form-group">
                  <label class="col-md-4 control-label label-xs required" >Control de venta:</label>  
                  <div class="col-md-8">
                  <select  id="bod_cvt" name="bod_cvt" class="form-control input-xs" >
                        <option value="S">Si</option>
                        <option value="N">No</option>
                    </select>
                  </div>
                </div>  
                
                <div class="form-group">
                  <label class="col-md-4 control-label label-xs required" >Usuarios:</label>  
                  <div class="col-md-8">
                  <select multiple id="usu_cod" name="usu_cod" class="form-control input-xs" data-placeholder="Seleccione los usuarios"  >
                       <?php 
                          foreach ($usuarios as $usu) {
                              echo "<option value='{$usu['usu_cod']}'>{$usu['persona']}</option>";
                          }
                          ?>   
                    </select>
                  </div>
                </div>

                <!-- Buttons -->
                <div class="form-group">
                  <label class="col-md-4 control-label" for="btnModificar"></label>
                  <div class="col-md-8">
                      <button id="btnAccion" name="btnAccion" class="btn btn-sm btn-primary"></button>
                  </div>
                </div>

                </fieldset>
            </form>
        </div>
        
<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
        <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />

    </BODY>
</HTML>