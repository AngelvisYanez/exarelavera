<?php 
/**
* @abstract Permite recibir movimientos inter-bodegas
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


if (isset($bodSuc)) {
    $data = $_GET;
    try{
      $resp['data'] = $obBD_con1->getArrayConsulta(18, $data, $obBD_conexion);
      $resp['success']=true;
    }catch (Exception $exc){
      $obBD_con1->echoLog($exc->getTraceAsString());
    }
    $obBD_con1->echoJson($resp);
}


if(isset($searchProductos)){
		$data = $_GET;
   	try{
      $resp = $obBD_con1->getPageGrid(26, $data, $obBD_conexion);
    }catch (Exception $exc){
    	$obBD_con1->echoLog($exc->getTraceAsString());
    }
   $obBD_con1->echoJson($resp);
}

if(isset($ajaxSubgrid)){
	$data=$_GET;
	try {
		$resp=$obBD_con1->getPageGrid(27, $data, $obBD_conexion);
	} catch (Exception $e) {
		$obBD_con1->echoLog($exc->getTraceAsString());
	}
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

		<style>                     
			.pagination>li>a, .pagination>li>span {padding: 4px 2px;}
			.pagination {/*display: block;*/margin:0;padding: 0;}
			.chosen-default span,.chosen-single span{color:#555;}
			.chosen-single span{padding-left: 5px;}
		</style>
	</HEAD>
	<BODY>
		<script type="text/javascript">
			var name_file="<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>";
		</script>
		<div class="panel panel-main">
			<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;Estado de Productos</h3></div>
			<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="documentoSearch">
                    <?php include("COMPONENTES/form_search_producto.html");?>
                </div>
			</div>
		</div>
		<script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
		<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
		<script src="../VALIDACIONES/bod_ver_pro.js"></script>
	</BODY>
</html>