<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erick Cordova
* @version 1.0
* Fecha de creacion 2017-12-21
*/
require_once('../../administrador/LOGICA/seguridad.php');	 
require_once('../LOGICA/bod_log_bodega.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Bod($Ses_Dat_Dis);
/**
* Creaciï¿½n del Objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Bod;


if(isset($usersAjax)){
	$resp['rows']=  $obBD_con1->getArrayConsulta(2,NULL,$obBD_conexion);
	$resp['success']=true;
	$obBD_con1->echoJson($resp);    
}


if(isset($getProductores)){
  $resp['data']=  $obBD_con1->getArrayConsulta(28,NULL,$obBD_conexion);
  $resp['success']=true;
  $obBD_con1->echoJson($resp);    
}


if (isset($searchBodegas)) {
 	// $obBD_con1->debug(true);
   $data = $_GET;
   try{
   	$data['where']="";
   	if(trim($search)!=""){
   		if ($op_opciones =='n') {
   			$data['where']="AND Bod_Nom Like '%$search%'" ;
   		}else{
   			$data['where']="AND Bod_Res Like '%$search%'" ;
   		}
   	}
      $resp = $obBD_con1->getPageGrid(5, $data, $obBD_conexion);
   }catch (Exception $exc){
      $obBD_con1->echoLog($exc->getTraceAsString());
   }
   $obBD_con1->echoJson($resp);
}

if (isset($getBodData)) {
 	// $obBD_con1->debug(true);
   $data = $_GET;
   try{
   	$resp=array();
      $general = $obBD_con1->getRowConsulta(6, $data, $obBD_conexion);
      $users= $obBD_con1->getArrayConsulta(7, $data, $obBD_conexion);
      $resp['data']=$general;
      $resp['data']['users']=$users;
      $resp['success']=true;
   }catch (Exception $exc){
      $obBD_con1->echoLog($exc->getTraceAsString());
   }
   $obBD_con1->echoJson($resp);		
}


/**
 * guarda  Bodega
 */
if (isset($saveBodega)) {

	$obBD_conexionIns = new Class_Log_Conexion_Bod($Ses_Dat_Dis);
	$obBD_conIns = new Class_Log_Datos_Bod;
	$data=$_POST;
	$Anticipo_Cod=0;

    $obBD_conIns->debug(true);
    // $obBD_con1->debug(true);

    $obBD_conIns->inicio_transaccion($obBD_conexionIns);
    $resp['success']=false;
    try {
    	// actualiza Bodega
    	$obBD_conIns->operacionobBD(8,$data,$obBD_conexionIns);

		// borra asignacion de usuarios anterior
    	$obBD_conIns->operacionobBD(9,$data,$obBD_conexionIns);		

    	// asigna usuarios a bodega
    	if(!is_array($Usu_Cod))
    		$Usu_Cod=array($Usu_Cod);
    	foreach ($Usu_Cod as $Cod_Usu) {
    		$obBD_conIns->operacionobBD(4,array('Usu_Cod' =>$Cod_Usu , 'Bod_Cod' =>$Bod_Cod),$obBD_conexionIns);
    	}
    	$resp['success']=true;
    	// $resp['error']=$obBD_conIns->MsgError;
    	// throw new Exception('Error de Transaccion');
    } catch (Exception $ex) {
    	$resp['message']=$ex->getMessage();
    	$resp['success']=false;
    	$obBD_conIns->rollBack_nomsn($obBD_conexionIns);
    }
    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
    $resp['Ant_Cod']=$Anticipo_Cod;
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
		<script src="../VALIDACIONES/bod_alt_bod.js"></script>
		<script type="text/javascript" src="../VALIDACIONES/bod_mod_bod.js"></script>
		<style>                     
			.pagination>li>a, .pagination>li>span {padding: 4px 2px;}
			.pagination {/*display: block;*/margin:0;padding: 0;}
			.chosen-default span,.chosen-single span{color:#555;}
			.chosen-single span{padding-left: 5px;}
		</style>
	</HEAD>
	<BODY>
		<div class="panel panel-main">
			<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;Modificaci&oacute;n de Bodegas</h3></div>
			<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
				<div id="documentoSearch">
					<?php include("COMPONENTES/form_search_bodega.html");?>
				</div>

				<div id="documentoMain">
					<?php include("COMPONENTES/form_bodega.html");?>
				</div>
			</div>
		</div>
		<script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
		<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	</BODY>
</html>