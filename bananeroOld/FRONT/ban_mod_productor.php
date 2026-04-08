<?php	
/**
* @abstract Permite realizar la creacion de productores
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

// $obBD_con1->debug(true);

if(isset($searchProductor)) {
	$data=$_GET;  
	$obBD_con1->getPageGridJson(37,$data, $obBD_conexion,$page, $rows); 
}


/* Consulta del tipo de proveedores */
if(isset($provAjax)){  
	$obBD_con1->getPageGridJson(2, $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows); 
}

if(isset($cargarCiudades)){
	$resp['data']=$obBD_con1->getArrayConsulta(7,'',$obBD_conexion);
	$resp['success']=true;
	$obBD_con1->echoJson($resp);
}
if(isset($cargarDocumentos)){
	$resp['data']=$obBD_con1->getArrayConsulta(8,'',$obBD_conexion);
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
    if($obBD_con1->Error==0) {$responce=array('success'=>true,'prov'=>$data);} else {$responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!',error=>$obBD_con1->MsgError);}
    $obBD_con1->echoJson($responce);
}


if (isset($saveProductor)) {
	$obBD_ins1 =  new Class_Log_Datos_Bana;
    $obBD_conexionIns = new Class_Log_Conexion_Bana($Ses_Dat_Dis);
    // $obBD_ins1->debug(true);
    $data=$_POST;

    $cont=$obBD_con1->getRowConsulta(12,$data, $obBD_conexion);
    if ($cont['productor']<=0 || $data['Ant_Prv_Cod']==$data['Prv_Cod']) {
    	$obBD_ins1->inicio_transaccion($obBD_conexionIns);  
	    try {
	    	$obBD_ins1->operacionobBD(36,$data,$obBD_conexionIns);
	    	$response['success']=true;
	    } catch (Exception $e) {
	    	$obBD_ins1->rollBack_nomsn($obBD_conexionIns); 
	    	$response['message']=$e->getMessage(); 
	    	$obBD_con1->echoJson($response);
	    }
	    $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);    
	    if($obBD_ins1->Error==0){ 

	    }else{
	    	$responce=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_ins1->MsgError);
	    }
    }else{
    	$response['success']=false;
    	$response['message']='Ya existe Proveedor Asociado';
    }
    $obBD_con1->echoJson($response);
}


?>
<!DOCTYPE html>
<html>
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
		<div class="panel panel-main">
			<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;Modificaci&oacute;n de Productores</h3></div>
			<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
				<div id="documentoSearch">
					<?php include("form_search_productor.html");?>
				</div>

				<div id="documentoMain" style="display:none;">
					<?php include("form_productor.html");?>
				</div>
			</div>
		</div>
		<script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
		<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
		<script src="../VALIDACIONES/ban_alt_productor.js"></script>
		<script src="../VALIDACIONES/ban_mod_productor.js"></script>
	</BODY>
</html>




