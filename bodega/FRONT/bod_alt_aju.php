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
$obBD_con1->debug(true);

if(isset($getTipoAjus)){
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
      $resp = $obBD_con1->getPageGrid(24, $data, $obBD_conexion);
   }catch (Exception $exc){
      $obBD_con1->echoLog($exc->getTraceAsString());
   }
   $obBD_con1->echoJson($resp);
}

if (isset($bodActive)) {
  	$data = $_GET;
    try{
      $resp['data'] = $obBD_con1->getArrayConsulta(10, $data, $obBD_conexion);
      $resp['success']=true;
    }catch (Exception $exc){
      $obBD_con1->echoLog($exc->getTraceAsString());
    }
    $obBD_con1->echoJson($resp);
}


if(isset($saveAjuste)){
	$hora = date("H:i:s");
	$obBD_conexionIns = new Class_Log_Conexion_Bod($Ses_Dat_Dis);
	$obBD_conIns = new Class_Log_Datos_Bod;
	$obBD_conIns->debug(true);
	$data=$_POST;
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
        $data['Aju_Tip']=$Tia_Tra;
        $data['Aju_Hor']=$hora;
        $data['Mov_Obs']=$Aju_Obs;
        $campo='Kar_Can';
        $data['Aju_Det']='AJUSTE DE BODEGAS BODEGAS';
        if ($Tia_Tra=='E') {
            $campo='Kar_Sal';
        }

    	// insertando ajuste_kar
    	$obBD_conIns->operacionobBD(13,$data,$obBD_conexionIns);
    	$data['Aju_Cod']=$obBD_conIns->insercionid($obBD_conexionIns);

    	//insertando en Kardex
    	foreach ($items as $i => $item) {
    		$data_save=array_merge($item,array('Ind' =>$i,'Iva_Cod'=>$iva['Iva_Cod'],'Aju_Cod'=>$data['Aju_Cod'],'Vnd_Cod'=>$data['Vnd_Cod'],'Aju_Fec'=>$data['Aju_Fec'],'Aju_Hor'=>$hora,'Bod_Cod'=>$data['Bod_Cod'],'campo'=>$campo));
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
		<style>                     
			.pagination>li>a, .pagination>li>span {padding: 4px 2px;}
			.pagination {/*display: block;*/margin:0;padding: 0;}
			.chosen-default span,.chosen-single span{color:#555;}
			.chosen-single span{padding-left: 5px;}
		</style>
	</HEAD>
	<BODY>
		<div class="panel panel-main">
			<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;Alta de Ajuste</h3></div>
			<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
				<?php include("COMPONENTES/form_ajuste.html");?>
			</div>
		</div>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?> 
    <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
		<script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <script src="../VALIDACIONES/bod_alt_aju.js"></script>
		
	</BODY>
</html>