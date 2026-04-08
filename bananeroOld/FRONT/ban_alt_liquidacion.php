<?php	
/**
* @abstract Permite realizar la creacion de Liquidaciones
* @author Erick Cordova
* @version 1.0
* Fecha de creacion 2017-12-21
*/

require_once('../../administrador/LOGICA/seguridad.php');	 
require_once('../LOGICA/ban_log_bananero.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Bana($Ses_Dat_Dis);
/**
* Creacion del Objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Bana;


$hoy = date("Y-m-d");

//$obBD_con1->debug(true);

/* Consulta de Productores */
if(isset($prodAjax)){
   $data=$_GET;  
	$obBD_con1->getPageGridJson(13,$data, $obBD_conexion,$page, $rows); 
}

if(isset($getTipoAjus)){
   //$obBD_con1->debug(true);
   $data = $_GET;
    try{
      $resp['data'] = $obBD_con1->getArrayConsulta(35, $data, $obBD_conexion);
      $resp['success']=true;
    }catch (Exception $exc){
      $obBD_con1->echoLog($exc->getTraceAsString());
    }
    $obBD_con1->echoJson($resp);
}


if(isset($getStokLiquidacion)){
   $data=$_GET;
   $Des_Pro=0;
   $Ing_Pro=0;
   $data['Det_Tip']='D';
   $row_descuento=$obBD_con1->getRowConsulta(22,$data, $obBD_conexion);
   if(!empty($row_descuento['valor'])){
      $Des_Pro=$row_descuento['valor'];
   }
   $data['Det_Tip']='I';
   $row_ingreso=$obBD_con1->getRowConsulta(22,$data, $obBD_conexion);
   if(!empty($row_ingreso['valor'])){
      $Ing_Pro=$row_ingreso['valor'];
   }
   $resp['success']=true;
   $resp['data']=array('valor' =>$Ing_Pro-$Des_Pro,'Pro_Cod'=>$row_ingreso['Pro_Cod']);
   $obBD_con1->echoJson($resp);
}

if(isset($saveLiquidacion)){
   $data=$_POST;
   try {
      if(!isset($data['descuentos'])){
         $data['descuentos']=array();
      }
       if(!isset($data['ingresos'])){
         $data['ingresos']=array();
      }
      if(!isset($data['retencion'])){
         $data['retencion']=array();
      }

       // validando cantidades de productos.
      foreach ($data['ingresos'] as $ingreso) {
         $ingreso['Bod_Cod']=$data['Bod_Cod'];
         $row_stock=$obBD_con1->getRowConsulta(21,$ingreso, $obBD_conexion);
         if(empty($row_stock['stocka_act'])||$row_stock['stocka_act']<$ingreso['Pro_Can']){
            throw new Exception('No tiene suficientes : <u>'.$ingreso['Ite_Lar'].'(s)</u> en Bodega de Productor '.$data['Prd_Nom'].' !');
         }
      }

      foreach ($data['descuentos'] as $descuento) {
         $Bod_Pro=0;$Ing_Pro=0;$Des_Pro=0;$limit=0;
         $descuento['Bod_Cod']=$data['Bod_Cod'];
         $row_stock=$obBD_con1->getRowConsulta(21,$descuento, $obBD_conexion);
         if(!empty($row_stock['stocka_act'])){
            $Bod_Pro=$row_stock['stocka_act'];
         }
         $descuento['Det_Tip']='I';
         $row_ingresos=$obBD_con1->getRowConsulta(22,$descuento, $obBD_conexion);
         if(!empty($row_ingresos['valor'])){
            $Ing_Pro=$row_ingresos['valor'];
         }
         $descuento['Det_Tip']='D';
         $row_descuento=$obBD_con1->getRowConsulta(22,$descuento, $obBD_conexion);
         if(!empty($row_descuento['valor'])){
            $Des_Pro=$row_descuento['valor'];
         }
         $limit=$Bod_Pro+($Ing_Pro-$Des_Pro);
         $obBD_con1->echoLog($limit.' ='.$Bod_Pro.'+('.$Ing_Pro.'-'.$Des_Pro.')');      
         $obBD_con1->echoLog($limit);      
         if(empty($limit)||$limit<$descuento['Pro_Can']){
            throw new Exception('No tiene suficientes : <u>'.$descuento['Ite_Lar'].'(s)</u> para descontar en '.$data['Prd_Nom'].' !');
         }
   

      }   
   } catch (Exception $ex) {
      $response['message']=$ex->getMessage();
      $obBD_con1->echoJson($response);
   }

   // variables de conexion para insercion en base de datos
   $obBD_conexionIns = new Class_Log_Conexion_Bana($Ses_Dat_Dis);
   $obBD_conIns = new Class_Log_Datos_Bana;
   //$obBD_conIns->debug(true);
   $obBD_conIns->inicio_transaccion($obBD_conexionIns);
   try {


      $vendedor=$obBD_con1->getRowConsulta(24,array('none' =>'none'),$obBD_conexion);
      if(!isset($vendedor['Vnd_Cod'])||empty($vendedor['Vnd_Cod']))
         throw new Exception('No tiene permisos de Vendedor!');
      $proveedor = $obBD_con1->getRowConsulta(25,array('none' =>'none'), $obBD_conexion);
        if(!isset($proveedor['Prv_Cod'])||empty($proveedor['Prv_Cod']))
         throw new Exception('Revisar la parametrizacion de proveedores varios!');
        $iva=$obBD_con1->getRowConsulta(26,array('Aju_Fec'=>$Liq_Fec), $obBD_conexion);
        if(!isset($iva['Iva_Cod'])||empty($iva['Iva_Cod']))
         throw new Exception('Revisar la configuracion de Ivas activos!');
        $data['Prv_Cod']=$proveedor['Prv_Cod'];
        $data['Vnd_Cod']=$vendedor['Vnd_Cod'];
        $data['Iva_Cod']=$iva['Iva_Cod'];
        $data['Aju_Det']='LIQUIDACION DE BODEGA';
        $data['Aju_Tip']='E'; //TIPO EGRESO DE BODEGA DE PRODUCTOR
        $data['Aju_Hor']=date("H:i:s");

       // consultando Bodega Principal
      
      $destino=$obBD_con1->getRowConsulta(32,array('none' =>'none'),$obBD_conexion);
      if(!isset($destino['Bod_Cod'])||empty($destino['Bod_Cod']))
         throw new Exception('No tiene asignada la Bodega Principal!');

      $data['Bod_Ori']=$data['Bod_Cod'];
      $data['Bod_Des']=$destino['Bod_Cod'];

         // cabecera de liquidacion
      $obBD_conIns->operacionobBD(23,$data,$obBD_conexionIns);
      $Liq_Cod=$obBD_conIns->insercionid($obBD_conexionIns);
      $obBD_conIns->echoLog('codigo de nueva liquidacion: '.$Liq_Cod);

      $data['Liq_Cod']=$Liq_Cod;
      $data['Mov_Obs']='MOVIMIENTO POR LIQUIDACION DE BANANO';

      // inserta nuevo movimiento
      $obBD_conIns->operacionobBD(27,$data,$obBD_conexionIns);
      $data['Mov_Cod']=$obBD_conIns->insercionid($obBD_conexionIns);

      // insertando ajuste_kar egreso
      $obBD_conIns->operacionobBD(28,$data,$obBD_conexionIns);
      $data['Aju_Cod']=$obBD_conIns->insercionid($obBD_conexionIns);

      //relacionar ajuste con movimiento
      $obBD_conIns->operacionobBD(29,$data,$obBD_conexionIns);

      //insertando en Kardex
      foreach ($data['ingresos'] as $i=>$ingreso) {
         $data_save=array_merge($ingreso,array('Ind' =>$i,'Iva_Cod'=>$iva['Iva_Cod'],'Aju_Cod'=>$data['Aju_Cod'],'Vnd_Cod'=>$data['Vnd_Cod'],'Aju_Fec'=>$data['Liq_Fec'],'Aju_Hor'=>date("H:i:s"),'Bod_Cod'=>$data['Bod_Cod'],'campo'=>'Kar_Sal'));
            $obBD_conIns->operacionobBD(30,$data_save,$obBD_conexionIns);
      }
     
      $data['Aju_Tip']='I';
      $data['Bod_Des']=$destino['Bod_Cod'];
      $data['Aju_Hor']=date("H:i:s");
      // datos de Movimiento
      $data['Mov_Est']='F';
      $data['alert']='0';

      // insertando ajuste_kar
      $obBD_conIns->operacionobBD(28,$data,$obBD_conexionIns);
      $data['Aju_Cod']=$obBD_conIns->insercionid($obBD_conexionIns);

      //relacionar ajuste con movimiento
      $obBD_conIns->operacionobBD(29,$data,$obBD_conexionIns);
      

      //insertando en Kardex
      foreach ($data['ingresos'] as $i=>$ingreso) {
        $data_save=array_merge($ingreso,array('Ind' =>$i,'Iva_Cod'=>$iva['Iva_Cod'],'Aju_Cod'=>$data['Aju_Cod'],'Vnd_Cod'=>$data['Vnd_Cod'],'Aju_Fec'=>$data['Liq_Fec'],'Aju_Hor'=>date("H:i:s"),'Bod_Cod'=>$data['Bod_Des'],'campo'=>'Kar_Can'));
          $obBD_conIns->operacionobBD(30,$data_save,$obBD_conexionIns);
      }
      
      // Insertar detalles de liquidacion/Descuentos-D.
      foreach ($data['descuentos'] as $descuento) {
         $obBD_conIns->operacionobBD(33,array_merge($descuento,array('Liq_Cod'=>$data['Liq_Cod'],'Det_Tip'=>'D')),$obBD_conexionIns);
      }

      // Insertar detalles de liquidacion/Ingresos-I.
      foreach ($data['ingresos'] as $ingreso) {
         $obBD_conIns->operacionobBD(33,array_merge($ingreso,array('Liq_Cod'=>$data['Liq_Cod'],'Det_Tip'=>'I')),$obBD_conexionIns);  

      }
      // Insertar detalles de liquidacion/Ingresos-I-cajas.
      foreach ($data['cajas'] as $caja) {
         $obBD_conIns->operacionobBD(33,array_merge($caja,array('Liq_Cod'=>$data['Liq_Cod'],'Det_Tip'=>'I')),$obBD_conexionIns);  

      }

   
      // Insertar detalles de liquidacion/Retenciones.
      foreach ($data['retencion'] as $retencion) {
        //cambiar a dinamico valor de coonfiguracion (Con_Cod) de retenciones
         $obBD_conIns->operacionobBD(34,array_merge($retencion,array('Liq_Cod'=>$data['Liq_Cod'])),$obBD_conexionIns);  
      }

   } catch (Exception $ex) {
      $response['message']=$ex->getMessage();
      $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
      $obBD_con1->echoJson($response);
   }
    if($obBD_conIns->Error==0) {
      $response=array('success'=>true);
      $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
    } 
    else{
      $response=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_conIns->MsgError);
      $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
    }
   $obBD_con1->echoJson($response);

}



if(isset($ingresoAjax)){
   $data=$_GET;
   $resp=$obBD_con1->getPageGrid(16,$data, $obBD_conexion,$page, $rows);
   // consultando precios
   foreach ($resp['rows'] as &$row) {
      $precio=$obBD_con1->getRowConsulta(17,$row, $obBD_conexion);
      $row=array_merge($row,$precio);
   }unset($row);
   $resp['success']=true;
   $obBD_con1->echoJson($resp);  
}




if (isset($descuentoAjax)) {
   $data=$_GET;
   $resp=$obBD_con1->getPageGrid(18,$data, $obBD_conexion,$page, $rows);
    // consultando precios
   foreach ($resp['rows'] as &$row) {
      $Des_Pro=0;
      $Ing_Pro=0;
      $row['Det_Tip']='D';
      $row['Bod_Cod']=$data['Bod_Cod'];
      $row_descuento=$obBD_con1->getRowConsulta(22,$row, $obBD_conexion);
      if(!empty($row_descuento['valor'])){
         $Des_Pro=$row_descuento['valor'];
      }
      $row['Det_Tip']='I';
      $row_ingreso=$obBD_con1->getRowConsulta(22,$row, $obBD_conexion);
      if(!empty($row_ingreso['valor'])){
         $Ing_Pro=$row_ingreso['valor'];
      }
      $row['Pro_Can']=$row['Pro_Can']+$Ing_Pro-$Des_Pro;
   }unset($row);
   $resp['success']=true;
   $obBD_con1->echoJson($resp);  
}

if(isset($cargarCiudades)){
	$resp['data']=$obBD_con1->getArrayConsulta(7,'',$obBD_conexion);
	$resp['success']=true;
	$obBD_con1->echoJson($resp);
}

if(isset($get_descuentos)){
   try {
      $data=$_GET;
      $res['data']=$obBD_con1->getArrayConsulta(15,$data,$obBD_conexion);
      $res['success']=true;
   } catch (Exception $e) {
      $res['success']=false;
      $res['msg']=$e->getMessage();
   }
   $obBD_con1->echoJson($res);
}


if (isset($getDobegas)) {
   try {
      $data=$_GET;
      $res['data']=$obBD_con1->getArrayConsulta(14,$data,$obBD_conexion);
      $res['success']=true;
   } catch (Exception $e) {
      $res['success']=false;
      $res['msg']=$e->getMessage();
   }
   $obBD_con1->echoJson($res);

}


/* ver si exite un proveedor */
if(isset($provAjax2)){  
    $pers=$obBD_con1->getArrayConsulta(9, $Prs_Ced.'*'.$Ses_Emp_Cod, $obBD_conexion); 
    $responce=array('rows'=>null,'total'=>0);    
    if(count($pers)>=1){
        
        $per=array(0=>$pers[0]);
        foreach($pers AS $p){ if($p['Emp_Cod']*1==$Ses_Emp_Cod*1){  $per[0]=$p; break; } }
        $responce['rows'] = $per;
        $responce['total'] = count($per);
    }    
    $obBD_con1->echoJson($responce);
}


if(isset($cargarDocumentos)){
	$resp['data']=$obBD_con1->getArrayConsulta(8,'',$obBD_conexion);
	$resp['success']=true;
	$obBD_con1->echoJson($resp);
}


if(isset($retencionAjax)){
   $data=$_GET;
   $resp['data']=$obBD_con1->getArrayConsulta(19,$data,$obBD_conexion);
   $resp['success']=true;
   $obBD_con1->echoJson($resp);
}

if(isset($getTipoRetenciones)){
   $data=$_GET;
   $resp['data']=$obBD_con1->getArrayConsulta(20,$data,$obBD_conexion);
   $resp['success']=true;
   $obBD_con1->echoJson($resp);
}

/* guarda un nuevo proveedor */
if(isset($guardaProvAjax)){    
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion);                  
        if(empty($Prs_Cod)){
            $obBD_con1->operacionobBD(10,$data,$obBD_conexion); 
            $data['Prs_Cod'] = $obBD_con1->insercionid ($obBD_conexion);
        }
        $obBD_con1->operacionobBD(11,$data,$obBD_conexion); 
        $data['Prv_Cod'] = $obBD_con1->insercionid ($obBD_conexion);
        $data['proveedor'] = trim($data['Prs_Ape'].' '.$data['Prs_Nom']);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if($obBD_con1->Error==0) {$responce=array('success'=>true,'prov'=>$data);} else {$responce=array('success'=>false,'message'=>'No se pudo realizar la transacci&oacute;n!',error=>$obBD_con1->MsgError);}
    $obBD_con1->echoJson($responce);
}
?>

<!DOCTYPE html>
<html>
<head>
 <title><?Php echo $Ses_Sys_Nom; ?></title>
 <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
 <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
 <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>                   
 <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
 <style>                     
 .pagination>li>a, .pagination>li>span {padding: 4px 2px;}
 .pagination {/*display: block;*/margin:0;padding: 0;}
 .chosen-default span,.chosen-single span{color:#555;}
 .chosen-single span{padding-left: 5px;}
</style>
</head>
<body>
   <div class="panel panel-main">
      <div class="panel-heading exa-header">
         <h3 class="panel-title">&raquo; Nueva Liquidaci&oacute;n</h3>
      </div>
      <div class="panel-body ui-widget-content ui-corner-bottom exa-body"> 
         <div id="documentoMain">
            <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaLiquidacion();">
               <div class="row">
                  <div class="col-xs-12  col-md-5 col-lg-5">
                     <fieldset class="exa-fieldset" id="datos_productor">
                        <legend class="Titulos2">Datos del Productor</legend>
                        <div class="form-group">
                           <label class="col-xs-2 control-label label-xs">C&eacute;dula/RUC:</label>  
                           <div class="col-xs-6" >
                              <input name="Prs_Cod" type="text" style="display:none;" />  
                              <input name="Prs_Cor" type="text" style="display:none;" />  
                              <input name="Prv_Cod" type="text" style="display:none;" />
                              <input name="op_opciones" type="text" value="c" style="display: none;">  
                              <div class="input-group input-group-xs">
                                 <input name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#prodDialog',selectProd); }" type="text" placeholder="Ingrese Productor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                 <span class="input-group-btn">
                                    <button id="Prv_Btn" type="button" onclick="$('#prodDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Productor"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                    <button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open'); $('#reset').val(1); " class="btn btn-success btn-xs" title="Registrar Productor"  tabindex="2">
                                       <span class="glyphicon glyphicon-plus"></span>
                                    </button>    
                                 </span>
                              </div>
                           </div>                                      
                        </div>
                        <div class="form-group">
                              <label class="col-xs-2 control-label label-xs required">Productor:</label>  
                              <div class="col-xs-6" >
                                 <input name="Prd_Nom" class="form-control input-xs" readonly="">
                              </div>
                              <div class="col-xs-4" >
                                 <select name="Bod_Cod"  id="Bod_Cod" class="form-control input-xs">
                                    <option>-------</option>
                                 </select>
                              </div>                                             
                        </div>
                        <div class="form-group">
                           <label class="col-xs-2 control-label label-xs">Direcci&oacute;n:</label>  
                           <div class="col-xs-10" >
                              <span name="Prs_Dir" type="text" class="form-control input-xs datatitle"></span>
                           </div>                    
                        </div>
                     </fieldset>
                  </div>
                  <div class="col-xs-12 col-md-7 col-lg-7">
                     <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos de Liquidaci&oacute;n</legend>
                        <div class="form-group">

                           <label class="col-xs-2 control-label label-xs required">N&uacute;mero:</label>  
                           <div class="col-xs-2" >
                              <input name="Liq_Num" type="text" class="form-control input-xs datatitle" required="" />
                           </div>

                           <label class="col-xs-2 control-label label-xs required">Emisi&oacute;n:</label>  
                           <div class="col-xs-2">
                              <div class="input-group">                                          
                                 <input id="Liq_Fec" name="Liq_Fec" type="text" class="form-control input-xs datepickers" tabindex="8" required="" value="<?php echo $hoy; ?>" />
                                 <span class="input-group-addon input-xs" title="Fecha de Emisi&oacute;n del Documento"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                              </div>
                           </div>


               <label class="col-xs-2 control-label label-sm">Liquidaci&oacute;n por:</label>
               <div class="col-xs-2">
                  <select name="Tia_Cod" id="Tia_Cod" class="form-control input-xs" required=""></select>
               </div>
                          
                        </div>
                                    
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Semana:</label>  
                           <div class="col-xs-2" >
                              <input name="Liq_Sem" type="text" class="form-control input-xs datatitle" required="" />
                           </div>
                           <label class="col-xs-2 control-label label-xs">Hacienda MAG:</label>  
                           <div class="col-xs-6" >
                              <input name="Liq_Hac" type="text" class="form-control input-xs datatitle"/>
                           </div>
                        </div>
                        <div class="form-group">
                           <label class="col-xs-2  control-label label-xs">Hectareas:</label>
                           <div class="col-xs-2" >
                              <input name="Liq_Hec" type="text" class="form-control input-xs datatitle"/>
                           </div>
                           <label class="control-label label-xs col-xs-2">Marcas:</label>
                           <div class="col-xs-6">
                              <input type="text" class="form-control input-xs" name="Liq_Mar"/>
                           </div>
                        </div>
                     </fieldset>
                  </div>
               </div>

               <div class="row">
                  <div class="col-lg-6 col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
                        <table id="ingresos"></table>
                        <div id="ingresosPager"></div>                        
                  </div> 
               
                  <div class="col-lg-6 col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
                        <table id="descuentos"></table>
                        <div id="descuentosPager"></div>                        
                  </div> 
               </div>

               <div class="row" id="resumenes">
                  <div class="col-xs-6 col-xs-offset-3">
                     <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Resumen</legend>
                        <div class="form-group">
                           <label class="control-label label-xs col-xs-6">Ingresos :</label>
                           <input class="col-xs-2 control-input" style=" text-align: right;border-radius: 4px;" name="res_ingreso" readonly="" />
                        </div>
                        <div class="form-group">
                           <label class="control-label label-xs col-xs-6">Descuentos :</label>
                           <input  class="col-xs-2 control-input" style=" text-align: right;border-radius: 4px;" name="res_descuento" readonly="" />
                        </div>
                        <div class="form-group">
                           <label class="control-label label-xs col-xs-6"> Neto a Pagar :</label>
                           <b><input class="col-xs-2 control-input" style="text-align: right; border-radius: 4px;" name="res_total" readonly="" /></b>
                        </div>
                     </fieldset>
                  </div>
               </div>
               
               <div class="col-xs-1">
                  <button class="btn btn-sm btn-primary" onclick=""><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
               </div>
            </form>
            <!-- dialogo de busqueda de Productor -->
            <div id="prodDialog" title="B&uacute;squeda de Productores"></div>

            <!-- dialogo de busqueda de Productos -->
            <div id="ingresoDialog" title="B&uacute;squeda de Items"></div>
            <div id="descuentoDialog" title="B&uacute;squeda de Productos en Bodega"></div>


            <div id="RetencionDialog" title="Cambiar valor de Retenci&oacute;n" style="display:none;">
               <form class="form-horizontal normal" id='form_change_rete' action="javascript:CambiarRetencion(this)">
                 <input type="text" name="index" class="hidden">
                 
                 <div class="form-group">
                   <label class="col-xs-4 control-label label-xs required">Tipos/Retencion:</label> 
                   <div class="col-xs-6">
                      <select id="tipo_ret_ban" class="form-control input-xs"></select>
                   </div>
                 </div>
                 
                 <br>
                 <div class="form-group">
                    <div class="center" style="min-height: 100px; width: 480px; padding-bottom: 5px;">
                        <table id="reten"></table>
                 </div>
                 </div>
                 <div class="form-group col-xs-offset-2">
                  <label class="col-xs-5 control-label label-xs required">Total a Retener:</label> 
                  <div class="col-xs-4">
                     <div class="input-group">  
                        <span class="input-group-addon input-xs">$</span>
                       <input id="total_ret" name="Ret_Tot" class="form-control input-xs"/>         
                     </div>
                  </div>
                 </div>

                 <input type="text" class="hidden" id="Ret_Bas" name="Ret_Bas"/>

                 <div class="form-group col-xs-offset-2">
                  <label class="col-xs-5 control-label label-xs required">Tarifa Efectiva:</label> 
                  <div class="col-xs-4">
                     <div class="input-group">                                          
                        <input id="total_tarifa" name="Ret_Por" class="form-control input-xs"/>
                        <span class="input-group-addon input-xs">
                           %
                        </span>
                     </div>
                  </div>
                 </div>
                 <br>

                 <div class="center">
                   <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Aceptar</button>
                 </div>
               </form>
             </div>
         </div>
      </div>
   </div>
   <script src="../VALIDACIONES/ban_alt_liquidacion.js?a=2"></script>
</body>
</html>