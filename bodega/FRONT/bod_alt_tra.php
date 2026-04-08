<?php 
/**
* @abstract Permite realizar movimientos inter-bodegas
* @author Erick Cordova
* @version 1.0
* Fecha de creacion 2018-01-03
*/
require_once('../../administrador/LOGICA/seguridad.php');	 
require_once('../LOGICA/bod_log_bodega.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Bod($Ses_Dat_Dis);
/**
* Creacion del Objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Bod;


if(isset($getTipoAjus)){
   $obBD_con1->debug(true);
   $data = $_GET;
    try{
      $resp['data'] = $obBD_con1->getArrayConsulta(14, $data, $obBD_conexion);
      $resp['success']=true;
    }catch (Exception $exc){
      $obBD_con1->echoLog($exc->getTraceAsString());
    }
    $obBD_con1->echoJson($resp);
}

if (isset($proAjax)) {
   $obBD_con1->debug(true);
   $data = $_GET;
   try{
   	$data['where']="";
   	if(trim($search)!=""){
   		if ($op_opciones =='d') {
   			$data['where']="AND item.Ite_Lar Like '%$search%'" ;
   		}else{
   			$data['where']="AND kar.Pro_Cod = '$search'" ;
   		}
   	}
      $resp = $obBD_con1->getPageGrid(11, $data, $obBD_conexion);
   }catch (Exception $exc){
      $obBD_con1->echoLog($exc->getTraceAsString());
   }
   $obBD_con1->echoJson($resp);
}

if (isset($bodActive)) {
	$obBD_con1->debug(true);
  	$data = $_GET;
    try{
      $resp['data'] = $obBD_con1->getArrayConsulta(18, $data, $obBD_conexion);
      $resp['success']=true;
    }catch (Exception $exc){
      $obBD_con1->echoLog($exc->getTraceAsString());
    }
    $obBD_con1->echoJson($resp);
}


if (isset($bodSuc)) {
  $obBD_con1->debug(true);
    $data = $_GET;
    try{
      $resp['data'] = $obBD_con1->getArrayConsulta(18, $data, $obBD_conexion);
      $resp['success']=true;
    }catch (Exception $exc){
      $obBD_con1->echoLog($exc->getTraceAsString());
    }
    $obBD_con1->echoJson($resp);
}

if(isset($saveMovimiento)){
	$hora = date("H:i:s");
	$obBD_conexionIns = new Class_Log_Conexion_Bod($Ses_Dat_Dis);
	$obBD_conIns = new Class_Log_Datos_Bod;
	$obBD_conIns->debug(true);
	$data=$_POST;
    /**
     * debug en Guardado de Anticipo
     */
   	$obBD_conIns->inicio_transaccion($obBD_conexionIns);
    $resp['success']=false;
    try {
    	$vendedor=$obBD_con1->getRowConsulta(15,array('none' =>'none'),$obBD_conexion);
    	if(!isset($vendedor['Vnd_Cod'])||empty($vendedor['Vnd_Cod']))
    		throw new Exception('No tiene permisos de Vendedor!');
    	$proveedor = $obBD_con1->getRowConsulta(16,array('none' =>'none'), $obBD_conexion);
        if(!isset($proveedor['Prv_Cod'])||empty($proveedor['Prv_Cod']))
        	throw new Exception('Revisar la parametrizacion de proveedores varios!');
        $iva=$obBD_con1->getRowConsulta(19,$data, $obBD_conexion);
        if(!isset($iva['Iva_Cod'])||empty($iva['Iva_Cod']))
        	throw new Exception('Revisar la configuracion de Ivas activos!');
        $data['Prv_Cod']=$proveedor['Prv_Cod'];
        $data['Vnd_Cod']=$vendedor['Vnd_Cod'];
        $data['Iva_Cod']=$iva['Iva_Cod'];
        $data['Aju_Det']='MOVIMIENTO INTER BODEGAS';
        $data['Bod_Cod']=$data['Bod_Ori'];
        $data['Aju_Tip']='E';
        $data['Aju_Hor']=$hora;
    	// inserta nuevo movimiento
    	$obBD_conIns->operacionobBD(12,$data,$obBD_conexionIns);
    	$data['Mov_Cod']=$obBD_conIns->insercionid($obBD_conexionIns);

    	// insertando ajuste_kar egreso
    	$obBD_conIns->operacionobBD(13,$data,$obBD_conexionIns);
    	$data['Aju_Cod']=$obBD_conIns->insercionid($obBD_conexionIns);

	   	//relacionar ajuste con movimiento
    	$obBD_conIns->operacionobBD(17,$data,$obBD_conexionIns);
    	

    	//insertando en Kardex
    	foreach ($items as $i => $item) {
    		$data_save=array_merge($item,array('Ind' =>$i,'Iva_Cod'=>$iva['Iva_Cod'],'Aju_Cod'=>$data['Aju_Cod'],'Vnd_Cod'=>$data['Vnd_Cod'],'Aju_Fec'=>$data['Aju_Fec'],'Aju_Hor'=>$hora,'Bod_Ori'=>$data['Bod_Ori'],'Bod_Cod'=>$data['Bod_Cod'],'campo'=>'Kar_Sal'));
    			$obBD_conIns->operacionobBD(23,$data_save,$obBD_conexionIns);
    	}

      $data['Aju_Tip']='I';
      $data['Bod_Cod']=$data['Bod_Des'];
      $data['Aju_Det']='TRANSFERENCIA INTER BODEGAS';
      $data['Aju_Hor']=$hora;
      // datos de Movimiento
      $data['Mov_Est']='F';
      $data['alert']='0';
      // update movimiento a tipo F
      $obBD_conIns->operacionobBD(22,$data,$obBD_conexionIns);


      // insertando ajuste_kar
      $obBD_conIns->operacionobBD(13,$data,$obBD_conexionIns);
      $data['Aju_Cod']=$obBD_conIns->insercionid($obBD_conexionIns);

      //relacionar ajuste con movimiento
      $obBD_conIns->operacionobBD(17,$data,$obBD_conexionIns);
      

      //insertando en Kardex
      foreach ($items as $i => $item) {
        $data_save=array_merge($item,array('Ind' =>$i,'Iva_Cod'=>$iva['Iva_Cod'],'Aju_Cod'=>$data['Aju_Cod'],'Vnd_Cod'=>$data['Vnd_Cod'],'Aju_Fec'=>$data['Aju_Fec'],'Aju_Hor'=>$hora,'Bod_Des'=>$data['Bod_Des'],'Bod_Cod'=>$data['Bod_Cod'],'campo'=>'Kar_Can'));
          $obBD_conIns->operacionobBD(23,$data_save,$obBD_conexionIns);
      }


    	$resp['success']=true;
    } catch (Exception $ex) {
    	$resp['message']=$ex->getMessage();
    	$resp['success']=false;
    	$obBD_conIns->rollBack_nomsn($obBD_conexionIns);
    }
    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
    $resp['action']='new';
    $obBD_con1->echoJson($resp);
}

?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
		<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
		<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>                   
		<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
		<script src="../VALIDACIONES/bod_alt_mov.js"></script>
		<style>                     
			.pagination>li>a, .pagination>li>span {padding: 4px 2px;}
			.pagination {/*display: block;*/margin:0;padding: 0;}
			.chosen-default span,.chosen-single span{color:#555;}
			.chosen-single span{padding-left: 5px;}
      .bolden{font-family:"Arial Black"}
		</style>
	</HEAD>
	<BODY>
		<div class="panel panel-main">
			<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;Alta de Transferencias de Bodegas</h3></div>
			<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
				<?php include("COMPONENTES/form_movimiento.html");?>
			</div>
		</div>
		<script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
		<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	</BODY>
</html>