<?php

/**
 * @abstract Permite realizar el ingreso de retenciones bancarias
 * @author Cesar Bermeo
 * @version 1.1
 * Fecha de modificación: 12-11-2018
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_ret_cre_vta.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Ret;
//$obBD_con1->debugLogs(false);
$hoy = date("Y-m-d");


$configs = $obBD_con1->getRowConsulta('confi_fact.selectWhere',array('setWhere'=>array('setEmpCod')),$obBD_conexion);

if (isset($searchAllRet)) {
	$obBD_con1->echoLog('** PHP RETENCIONES AJAX ***');
	if($configs['Cof_Con'] == 'N'){
		$datos = array_merge($_GET, array('setWhere' => array('byCliente','byPersona','byUsuarios','byPersonaUsu', 'addTipo','byPeriodo')));
    	$resultado = $obBD_con1->getPageGrid('retcre_vta.selectWhere', $datos, $obBD_conexion);

	}else{
		$datos = array_merge($_GET, array('setWhere' => array('byCliente','byPersona','byUsuarios','byPersonaUsu', 'addTipo','byPeriodo', 'byDetPlan')));
    	$resultado = $obBD_con1->getPageGrid('retcre_vta.selectWhere', $datos, $obBD_conexion);

	}

    //$obBD_con1->echoJson($resultado);
    //$obBD_con1->echoLog($resultado);
    $obBD_con1->echoJson($resultado);
}

if (isset($searchAllRetCon)) {
	$obBD_con1->echoLog('** PHP RETENCIONES  Consultar AJAX ***');
	if($configs['Cof_Con'] == 'N'){
		$datos = array_merge($_GET, array('setWhere' => array('isActive','byCliente','byPersona','byUsuarios','byPersonaUsu', 'addTipo','byPeriodo', 'byDetalleRet','byRentaIva','setRenTot')));
    	ChromePhp::log("searchRetCon", $datos);
		$resultado = $obBD_con1->getPageGrid('retcre_vta.selectWhere', $datos, $obBD_conexion);

	}else{
		$datos = array_merge($_GET, array('setWhere' => array('isActive','byCliente','byPersona','byUsuarios','byPersonaUsu', 'addTipo','byPeriodo', 'byDetPlan','byDetalleRet','byRentaIva','setRenTot')));
    	ChromePhp::log("searchRetConELSE", $datos);
		$resultado = $obBD_con1->getPageGrid('retcre_vta.selectWhere', $datos, $obBD_conexion);
	}


    $obBD_con1->echoJson($resultado);
}
if(isset($detalleRetenAjax)){
    $obBD_con1->echoLog('** PHP DETALLE RETENCIONES AJAX ***');
    $resuladoDetalle=array(
        'success' => true,
        'detRet'=> $obBD_con1->getArrayConsulta('retcre_vta.selectWhere', array('setWhere' => array('isActive','byDetalleRet','byRentaIva')), $obBD_conexion),
    );
    $obBD_con1->echoJson($resuladoDetalle);

}
if(isset($buscarRetencionesTodas)){
    $obBD_con1->echoLog('** PHP RETENCIONES AJAX ***');
    $resultRet=array(
        'success'=> true,
        'retLista'=> $obBD_con1->getArrayConsulta('retcre_vta.selectWhere', array('retcre_vta.Cli_Cod'=>$Cli_Cod, 'retcre_vta.Rvt_Num'=>$Rvt_Num,'setWhere' => array('isActive','byCliente','setEmpCod')), $obBD_conexion),
    );
    $obBD_con1->echoJson($resultRet);
}


/*if (isset($clientAjax)) {
    $obBD_con1->echoLog('** PHP CLIENTE AJAX ***');
    $obBD_con1->echoLog($configs['Cof_Con']);
    if($configs['Cof_Con'] == 'N'){
        $respuesta = $obBD_con1->getPageGrid('cliente.selectWhere', $_GET, $obBD_conexion,true);
        $obBD_con1->echoJson($respuesta);
    }else{
        $data = array_merge($_GET, array(

            'setWhere' => array('byVentas', 'byCcppC', 'byCcppCD', 'byAsient', 'isAsientoCod'))
        );
        $respuesta = $obBD_con1->getPageGrid('cliente.selectWhere', $data, $obBD_conexion,true);
        $obBD_con1->echoJson($respuesta);
    }

    //$obBD_con1->echoLog($respuesta);
}*/

/* Busqueda de proveedores*/
if(isset($proveeAjax2)){
    $obBD_con1->echoLog('** PHP PROVEDORES AJAX ***');
    //$data = array_merge($_GET, array('setWhere' => array('isActive')));
    $respuesta = $obBD_con1->getPageGrid('proveedore.selectWhere', $_GET, $obBD_conexion);
    $obBD_con1->echoJson($respuesta);
}
/* Busqueda de clientes*/
if(isset($proveeAjax)){
    $obBD_con1->echoLog('** PHP CLientes AJAXsa ***');
    $data = array_merge($_GET, array('setWhere' => array('isActive')));
    $respuesta = $obBD_con1->getPageGrid('cliente.selectWhere', $data, $obBD_conexion);
    //ChromePhp::log($respuesta['rows'][0]);
    $obBD_con1->echoJson($respuesta);
}

/* Consulta del codigo retencion - tabla del cuadro emergente - cambio corregido 18-12-2024*/
if(isset($codiAjax)){
	// Campo de busqueda
	$search = isset($_GET['search']) ? $_GET['search'] : '';
	// $optR = isset($_GET['optR']) ? $_GET['optR'] : '';
	$search = trim($search); // Elimina espacios antes y después
	$search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); // Previene problemas con caracteres especiales

	//Busqueda Global
	if (empty($search)) {
		$resultado=array(
			'success' => true,
			'message' => 'Consulta Global Existosa',
			'periodo'=> $obBD_con1->getRowConsulta('perio_cont.selectWhere',array('perio_cont.Pec_Est'=>'A', 'setWhere'=>array('setEmpCod'),'where'=>array('Pec_Cod'=>$PecCod)), $obBD_conexion),
			'rows'=>$obBD_con1->getArrayConsulta('renta_iva.selectWhere', array('setWhere'=>array('isActive','byOrder')), $obBD_conexion),
		 );
	} else if ($op_opciones === "d") {
			// Busqueda por descripcion
			$resultado = array(
				'success' => true,
				'periodo' => $obBD_con1->getRowConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod')), $obBD_conexion),
				'rows' => $obBD_con1->getArrayConsulta('renta_iva.selectWhere', array("LOWER(renta_iva.Ren_Con) LIKE '%" . strtolower($search) . "%'", 'setWhere' => array('isActive', 'byOrder')), $obBD_conexion),
				// 'rows' => $obBD_con1->getArrayConsulta('renta_iva.selectWhere', array('setWhere' => array('isActive', 'byOrder'), 'LIKE' => array('LOWER(renta_iva.Ren_Con)' => "%" . strtolower($search) . "%")), $obBD_conexion),
			);
	} else if ($op_opciones === "c") {
		// Busqueda por codigo
		$resultado = array(
			'success' => true,
			'periodo' => $obBD_con1->getRowConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod')), $obBD_conexion),
			'rows' => $obBD_con1->getArrayConsulta('renta_iva.selectWhere', array('renta_iva.Ren_Sri' => $search, 'setWhere' => array('isActive', 'byOrder')), $obBD_conexion),
		);
	}

	 $Pla_Cod=$resultado['periodo']['Pla_Cod'];
	 //$obBD_con1->echoLog($resultado['periodo']['Pla_Cod']);
			foreach ($resultado['rows'] as &$r) {
			//$obBD_con1->echoLog($r['Ren_Cod']);
			$Ren_Cod=$r['Ren_Cod'];
			$cuenta = $obBD_con1->getRowConsulta('reniva_pla.selectWhere',array('reniva_pla.Ren_Cod'=>$Ren_Cod, 'detP.Pla_Cod'=>$Pla_Cod,'setWhere'=>array('byDetPlan','isVenta')),$obBD_conexion);
			//$obBD_con1->echoLog($cuenta);
			if(!empty($cuenta['Pld_Cod'])) $r=array_merge($r,$cuenta);
			//$obBD_con1->echoLog($r);
		}unset($r);

	$obBD_con1->echoJson($resultado);
}

/**
 * Inactivar Retenciones
 */
if(isset($inactiveRet)){
    $obBD_ins1 = new Class_Log_Datos_Ret;
    $obBD_con1->echoLog('**-- PHP INACTIVAR --**');
    $obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        if ($configs['Cof_Con'] == 'S') {
            //con contabilidad
        }else{
            //sin contabilidad
        }

    }catch(Exception $e){ $obBD_con1->rollBack_nomsn($obBD_conexion); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp);  }
    $resp['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if(!$resp['success']) $resp['error']=$obBD_con1->MsgError;
    $obBD_con1->echoJson($resp);

}
 

/********
 * Guardar
 */
if(isset($saveDocumento)){
	$data = $_POST;
	$resp=array('success'=>false);
	$obBD_ins1 = new Class_Log_Datos_Ret;
	$obBD_conexionIns = new  Class_Log_Conexion_Global($Ses_Dat_Dis);
	$obBD_ins1->validaCierrePeriodo('retcre_vta', 'Rvt_Fec', 'Rvt_Cod', $data['Caj_Fec'], null, $obBD_conexion, 'S');
	$obBD_ins1->debug(true);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try{
        $obBD_con1->echoLog('**-- PHP GUARDAR --**');       
        $obBD_con1->echoLog($data);
        if (isset($saveCrear)) {
            $obBD_con1->echoLog('**-- PHP GUARDAR  crear--**');
            $mesCom = explode('-', $data['Caj_Fec']);
            $Tia_Asi = $obBD_con1->getRowConsulta('tipo_asien.selectWhere', array('Tia_Abr' => 'DG', 'setWhere' => array('isActive')), $obBD_conexion);
            //$Tic_Cod=$data['Tic_Cod'];
            $Com_Num = $obBD_con1->getComNumPecAuto($Tia_Asi['Tia_Cod'], $data['Pec_Cod'], $mesCom[1], $obBD_conexion);
            $numRetenciones = $obBD_con1->getRowConsulta('retcre_vta.sql.getNext', array('where' => array('')), $obBD_conexion);
            $valorRetTot = $numRetenciones['total'] + 1;
            //$obBD_con1->echoLog($valorRetTot);
            //$obBD_con1->echoLog($Ses_Prs_Cod);
            //$obBD_con1->echoLog($Ses_Usu_Cod);
            //$obBD_con1->echoLog($Ses_Sys_Nom);

            if ($configs['Cof_Con'] == 'S') {
                $obBD_con1->echoLog('**-- PHP CONTABILIDAD --**');
                $obBD_ins1->operacionobBD('comprobantes.insert', array('Pec_Cod' => $data['Pec_Cod'], 'Prv_Cod' => $data['Prv_Cod'], 'Usu_Cod' => $Ses_Usu_Cod,'Cli_Cod'=>$data['Cli_Cod'],
                    'Com_Num' => $Com_Num, 'Com_Fec' => $data['Caj_Fec'], 'Com_Tip' => '','Com_Con'=>'Retención Bancaria', 'Com_Gen' => 'A', 'Com_Val' => $data['t_retencion'], 'Tia_Cod' => $Tia_Asi['Tia_Cod']), $obBD_conexionIns);
                $Com_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
                $obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $Com_Cod, 'Asi_Deh' => 'H', 'Asi_Val' => $data['t_retencion'], 'Pld_Cod' => $data['Pld_Cod'], 'Asi_Glo' => 'Retención Tarjeta de Credito'), $obBD_conexionIns);

                $obBD_ins1->operacionobBD('retcre_vta.insert', array('Rvt_Num' => $data['Rvt_Num'], 'Rvt_Aut' => $data['Rvt_Aut'], 'Rvt_Fec' => $data['Caj_Fec'], 'Usu_Cod' => $Ses_Usu_Cod,
                    'Rvt_Tem' => $data['Rvt_Tem'], 'Pec_Cod' => $data['Pec_Cod'], 'Rvt_Obs' => $data['Rvt_Obs'], 'Pld_Cod' => $data['Pld_Cod'], 'Com_Cod' => $Com_Cod, 'Cli_Cod' => $data['Cli_Cod'], 'Tpc_Cod'=>$data['Tpc_Cod']), $obBD_conexionIns);
                $Rvt_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
            } else {
                $obBD_ins1->operacionobBD('retcre_vta.insert', array('Rvt_Num' => $data['Rvt_Num'], 'Rvt_Aut' => $data['Rvt_Aut'], 'Rvt_Fec' => $data['Caj_Fec'], 'Usu_Cod' => $Ses_Usu_Cod,
                    'Rvt_Tem' => $data['Rvt_Tem'], 'Pec_Cod' => $data['Pec_Cod'], 'Rvt_Obs' => $data['Rvt_Obs'], 'Pld_Cod' => $data['Pld_Cod'], 'Com_Cod' => '0', 'Cli_Cod' => $data['Cli_Cod'],'Tpc_Cod'=>$data['Tpc_Cod']), $obBD_conexionIns);
                $Rvt_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
            }

            //$obBD_con1->echoLog($Rvt_Cod);
            $cont = 0;
            foreach ($retenciones as $retencion) {
                if ($retencion['Ren_Cod'] != 0) {
                    if ($configs['Cof_Con'] == 'S') {
                        $obBD_ins1->operacionobBD('retcrevta_det.insert', array('Rvt_Cod' => $Rvt_Cod, 'Rvt_Int' => $cont + 1, 'Ren_Cod' => $retencion['Ren_Cod'], 'Rvt_Bas' => $retencion['Rvt_Bas'], 'Pld_Cod' => $retencion['Pld_Cod']), $obBD_conexionIns);
                        $obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $Com_Cod, 'Asi_Deh' => 'D', 'Asi_Val' => $retencion['total_val'], 'Pld_Cod' => $retencion['Pld_Cod']), $obBD_conexionIns);
                    }else{
                        $obBD_ins1->operacionobBD('retcrevta_det.insert', array('Rvt_Cod' => $Rvt_Cod, 'Rvt_Int' => $cont + 1, 'Ren_Cod' => $retencion['Ren_Cod'], 'Rvt_Bas' => $retencion['Rvt_Bas'], 'Pld_Cod' => $retencion['0']), $obBD_conexionIns);
                    }                    
			$cont++;
                }
            }
        }
        if($saveModReten){
            $obBD_con1->echoLog('**-- PHP MODIFICAR RETENCION --**');
            if ($configs['Cof_Con'] == 'S') {
                $obBD_con1->echoLog('**-- PHP CONTABILIDAD modificar --**');
                /* Actualizar el comprobante */
                $comprobantActual = $obBD_con1->getRowConsulta('comprobantes.selectWhere', array('comprobantes.Com_Cod'=>$data['Com_Cod']), $obBD_conexionIns);
                $obBD_con1->echoLog($data);
                if($data['verificador']==='true'){
                    $obBD_con1->echoLog('**-- PHP MODIFICAR VERIFICADOR --**');
                    $mesCom = explode('-', $data['Rvt_Fec']);
                    $Tia_Asi = $obBD_con1->getRowConsulta('tipo_asien.selectWhere', array('Tia_Abr' => 'DG', 'setWhere' => array('isActive')), $obBD_conexion);
                    //$Tic_Cod=$data['Tic_Cod'];
                    $Com_Num = $obBD_con1->getComNumPecAuto($Tia_Asi['Tia_Cod'], $data['Pec_Cod'], $mesCom[1], $obBD_conexion);
                    //'Com_Num' => $Com_Num
                    $obBD_con1->echoLog($Com_Num);
                    $obBD_ins1->operacionobBD('comprobantes.update', array('Com_Cod'=>$data['Com_Cod'], 'Com_Obs'=>$data['Rvt_Obs'] ,'Com_Num' => $Com_Num, 'Usu_Cod' => $Ses_Usu_Cod, 'Com_Val' => $data['t_retencion_m'], 'Com_Fec'=>$data['Rvt_Fec']), $obBD_conexionIns);
                }else{
                    $obBD_ins1->operacionobBD('comprobantes.update', array('Com_Cod'=>$data['Com_Cod'], 'Com_Obs'=>$data['Rvt_Obs'] ,'Usu_Cod' => $Ses_Usu_Cod, 'Com_Val' => $data['t_retencion_m'], 'Com_Fec'=>$data['Rvt_Fec']), $obBD_conexionIns);
                }

                /* borro asientos actuales*/
                $asientosActuales= $obBD_con1->getArrayConsulta('asientos.selectWhere', array('asientos.Com_Cod'=>$data['Com_Cod']), $obBD_conexionIns);
                if(!empty($asientosActuales)){
                    $obBD_con1->echoLog('**-- PHP MODIFICAR VERIFICA --**');
                    $obBD_ins1->operacionobBD('asientos.deleteWhere', array('Com_Cod'=>$data['Com_Cod']), $obBD_conexionIns);

                }
                /* inserto asientos */
                $obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'H', 'Asi_Val' => $data['t_retencion_m'], 'Pld_Cod' => $data['Pld_Cod'], 'Asi_Glo' => 'Retención Tarjeta de Credito'), $obBD_conexionIns);
                $obBD_ins1->operacionobBD('retcre_vta.update', array('Rvt_Cod'=>$data['Rvt_Cod'],'Rvt_Obs'=>$data['Rvt_Obs'],'Pld_Cod'=>$data['Pld_Cod'],
                                                                       'Rvt_Num'=>$data['Rvt_Num'],'Rvt_Aut'=>$data['Rvt_Aut'],'Rvt_Tem'=>$data['Rvt_Tem'],'Tpc_Cod'=>$data['Tpc_Cod'], 'Rvt_Fec'=>$data['Rvt_Fec'] ), $obBD_conexionIns);

            }else{
                  $obBD_ins1->operacionobBD('retcre_vta.update', array('Rvt_Cod'=>$data['Rvt_Cod'],'Rvt_Obs'=>$data['Rvt_Obs'],'Pld_Cod'=>$data['Pld_Cod'],
                                                                       'Rvt_Num'=>$data['Rvt_Num'],'Rvt_Aut'=>$data['Rvt_Aut'],'Rvt_Tem'=>$data['Rvt_Tem'],'Tpc_Cod'=>$data['Tpc_Cod'],'Rvt_Fec'=>$data['Rvt_Fec']  ), $obBD_conexionIns);
            }
            $detActualRet = $obBD_con1->getArrayConsulta('retcrevta_det.selectWhere', array('retcrevta_det.Rvt_Cod'=>$data['Rvt_Cod']), $obBD_conexionIns);
            $obBD_con1->echoLog($detActualRet);
            if(!empty($detActualRet)){
                foreach($detActualRet as $detaRet){
                    $obBD_ins1->operacionobBD('retcrevta_det.deleteWhere', array('Rvt_Cod'=>$detaRet['Rvt_Cod'], 'Rvt_Int'=>$detaRet['Rvt_Int']), $obBD_conexionIns);
                }
            }
            $cont = 0;
            $obBD_con1->echoLog($retenciones);
            foreach ($retenciones as $retencion) {
                if ($retencion['Ren_Cod'] != 0) {
                    $obBD_con1->echoLog('**-- PHP RTENCIONES --**');
                    $obBD_con1->echoLog($retencion);
                    $obBD_ins1->operacionobBD('retcrevta_det.insert', array('Rvt_Cod' => $Rvt_Cod, 'Rvt_Int' => $cont + 1, 'Ren_Cod' => $retencion['Ren_Cod'], 'Rvt_Bas' => $retencion['Rvt_Bas_Mod'],'Pld_Cod' => $retencion['Pld_Cod']), $obBD_conexionIns);
                    if ($configs['Cof_Con'] == 'S') {
                        $obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'D', 'Asi_Val' => $retencion['total_val'], 'Pld_Cod' => $retencion['Pld_Cod']), $obBD_conexionIns);
                    }
                    $cont++;
                }
            }

        }
    }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
	$resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);

}
/**
 * Anular retenciones bancarias
 */
if(isset($setInactiveRet)){
    $obBD_con1->echoLog('**-- PHP INACTIVAR RETENCION --**');
    $obBD_ins1 = new Class_Log_Datos_Ret;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try{
        if($configs['Cof_Con'] === 'S'){
            //Con contabilidad
            $obBD_con1->echoLog('**-- PHP INACTIVAR RETENCION CONTABILIDAD--**');
            $obBD_con1->operacionobBD('retcre_vta.setInactive', array('Rvt_Cod'=>$Rvt_Cod),$obBD_conexion);
            $obBD_con1->operacionobBD('comprobantes.setInactive', array('Com_Cod'=>$Com_Cod),$obBD_conexion);
        }else{
            //Sin contabilidad
            $obBD_con1->operacionobBD('retcre_vta.setInactive', array('Rvt_Cod'=>$Rvt_Cod),$obBD_conexion);
        }

    }catch(Exception $e){ $obBD_con1->rollBack_nomsn($obBD_conexion); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp);  }
    $resp['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if(!$resp['success']) $resp['error']=$obBD_con1->MsgError;
    $obBD_con1->echoJson($resp);
}





//$obBD_con1->echoLog($configs);
$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>array('setEmpCod'),'order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion);
$comprobantes=$obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('tipo_compr.Tic_Est'=>'A', 'setWhere'=>'isFactura'), $obBD_conexion);
$bancos=$obBD_con1->getArrayConsulta('banco.selectWhere', array('setWhere'=>array('byDetPlan','byPlan','setEmpCod','isActive','isTipo')) ,$obBD_conexion);
//$retencionesActuales = $obBD_con1->getArrayConsulta('retcre_vta.selectWhere',array('setWhere'=>array('isActive','byCliente','byPersona','byUsuarios','byPersonaUsu')),$obBD_conexion);
$detallePlan = $obBD_con1->getArrayConsulta('det_plan.selectWhere', array('setWhere'=>array('setEmpCod', 'setPlanParam', 'isParamRTJ'/*,'byTipoPlan','isTpr'*/)),$obBD_conexion);
$tipoPagoCom = $obBD_con1->getArrayConsulta('tipopagocom.selectWhere', array('setWhere'=>array('isActive')),$obBD_conexion);
//$obBD_con1->echoLog('**-- PHP TIPO PAGO COM --**');
//$obBD_con1->echoLog($tipoPagoCom);
//$obBD_con1->utf8_change_param(detallePlan);
utf8_encode_deep($detallePlan);

// INICIO DE LA VISTA
?>
<!DOCTYPE html>
<HTML>

<HEAD>
	<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?Php echo "Registrar Reten.Banco"; ?></TITLE>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
		  <script> var Cof_Con='<?php echo $configs['Cof_Con'];?>', detPlanCuenta=<?php echo json_encode($detallePlan) ?>; </script>
        <style>
		  		.footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact input[type=text]{ padding: 0; }
            .footerFact textarea{text-align: left; height: 75px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}
            #jqGridButtonDiv{float:right; padding-right:10px; position:relative; top:-1px;}
        </style>
   </HEAD>
   <BODY>
       <div class="panel panel-main" id="formFinal">
           <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Retenciones Bancarias</h3></div>
           <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
               <div class="row">
                   <div class="col-sm-12">
                       <div id="tabsRetencion" class="ui-tab-fix">
                           <ul>
                               <li><a href="#tabs-1">Registrar</a></li>
                               <li><a href="#tabs-2">Modificar</a></li>
                               <li><a href="#tabs-3">Consultar</a></li>
                           </ul>
                           <div class="panels-area form-horizontal normal ">
                               <!-- CREAR TAB !-->
	<div id="tabs-1">
		<div class="row">
			<form id="frm_ret_ban" name="frm_ret_ban" class="form-horizontal normal" action="javascript:saveRetBancaria('frm_ret_ban','saveCrear');">
				<div class="col-xs-6">
					<fieldset class="exa-fieldset" id="retFormTemp">
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs">Periodo:</label>
							<div class="col-xs-5">
								<select id="Pec_Cod" name="Pec_Cod" onchange="$('#PecCod').val($('#Pec_Cod option:selected').attr('data--pec-cod'));" class="form-control input-xs">
									<?php
									foreach ($periodos as $p) {
										echo "<option data--year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data--pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
									}
									?>
								</select>
							</div>
							<label class="col-xs-2 control-label label-xs">Fecha emisión:</label>
							<div class="col-xs-3">
								<input type="text" id="Caj_Fec" name="Caj_Fec" class="form-control input-xs datepickers">
							</div>
						</div>
					</fieldset>
					<fieldset class="exa-fieldset" id="retFormTemp4">
						<legend class="Titulos2">Datos Contables</legend>
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs required">Cuenta:</label>
							<div class="col-xs-8">

								<input id="Pld_Cod" name="Pld_Cod" type="text" class="hidden" />
								<input id="Pld_Cdc" name="Pld_Cdc" class="form-control input-xs readOnly" readOnly=""> </input>
								<!-- <span class="input-group-addon bold">Desc.</span>
                                                               <input id="Pld_Des" name="Pld_Des" class="form-control input-xs readOnly"  readOnly=""> </input> -->


								<!-- <select id="Bak_Cod" name="Bak_Cod" onchange="$('#formFinal').setData($('#Bak_Cod').find('option:selected').data(),'name');"
                                                                   class="form-control input-xs" required="">
                                                                       <?php foreach ($bancos as $bank) { ?>
								<option value="<?php echo $bank['Pld_Cod']; ?>" data-extra="<?php echo $bank['Ban_Cod']; ?>">
									<?php echo $bank['Pld_Des']; ?>
								</option>
								<?php } ?>
								</select>
								<!--<input name="Ban_Pld_Cod" type="text"  value="<?php echo $bank['Pld_Cod']; ?>"/>-->
							</div>
						</div>

						<!--  <div class="form-group">
                                                       <label class="col-xs-2 control-label label-xs required">Fact. Compra:</label>
                                                       <div class="col-xs-4">
                                                           <div class="input-group input-group-xs">
                                                               <input id="Cop_Num_Fc" name="Cop_Num_Fc" data-name="Cop_Num_Fc" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#factCompraDialog',selecinarFacturaCompra); }"
                                                                      type="text" placeholder="Ingrese Nº Documento" class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                                               <input name="Asi_Cod_Fc" type="text" style="display:none;" />
                                                               <input name="Asi_Val_Fc" type="text" style="display:none;" />
                                                               <input name="Asi_Deh_Fc" type="text" style="display:none;" />
                                                               <input name="Com_Cod_Fc" type="text" style="display:none;" />
                                                               <input name="Ciu_Cod_Fc" type="text" style="display:none;" />
                                                               <input name="Cop_Cod_Fc" type="text" style="display:none;" />
                                                               <input name="Pec_Cod_Fc" type="text" style="display:none;" />
                                                               <input name="Pld_Cod_Fc" type="text" style="display:none;" />
                                                               <input name="Prs_Cod_Fc" type="text" style="display:none;" />
                                                               <input name="Prv_Cod_Fc" type="text" style="display:none;" />
                                                               <input name="Tic_Cod_Fc" type="text" style="display:none;" />
                                                               <input name="Tpc_Cod_Fc" type="text" style="display:none;" />
                                                               <input name="Tri_Cod_Fc" type="text" style="display:none;" />
                                                               <input name="Vnd_Cod_Fc" type="text" style="display:none;" />
                                                               <span class="input-group-btn">
                                                                   <button id="FactC_Btn" type="button" onclick="$('#factCompraDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Factura Compra"
                                                                           tabindex="2">
                                                                       <span class="glyphicon glyphicon-th-list"></span>
                                                                   </button>
                                                               </span>
                                                           </div>
                                                       </div>
                                                   </div> -->
					</fieldset>
					<fieldset class="exa-fieldset" id="retFormTemp2">
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs required">Cédula/RUC:</label>
							<div class="col-xs-6">
								<input id="Cli_Cod" name="Cli_Cod" type="text" style="display:none;" />
								<input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
								<div class="input-group input-group-xs">
									<input id="Prs_Ced" name="Prs_Ced" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#proveeDialog',selectClientePuro); }"
									 type="text" placeholder="Ingrese Cliente..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
									<span class="input-group-btn">
										<button id="Cli_Btn" type="button" onclick="$('#proveeDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor"
										 tabindex="2">
											<span class="glyphicon glyphicon-search"></span>
										</button>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs required">Cliente:</label>
							<div class="col-xs-6">
								<span id="Cliente" name="Cliente" data-name="Cliente" class="form-control input-xs databind datatitle"></span>
							</div>
						</div>
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs">Dirección:</label>
							<div class="col-xs-10">
								<div class="input-group input-group-xs">
									<input name="Prs_Dir" data-name="Prs_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
									<span class="input-group-addon bold">e-mail:</span>
									<input name="Prs_Cor" data-name="Prs_Cor" type="text" class="form-control span datatitle" readonly="" tabindex="-1" />
								</div>
							</div>
						</div>
						<!-- <div class="form-group">
                                                       <label class="col-xs-2 control-label label-xs">Observaciones:</label>
                                                       <div class="col-xs-10">
                                                           <textarea name="Rvt_Obs" type="text" class="form-control span datatitle"></textarea>
                                                       </div>
                                                   </div> -->
					</fieldset>
					<fieldset class="exa-fieldset" id="retFormTemp3">
						<!-- <div class="form-group">
                                                       <label class="col-xs-2 control-label label-xs">Documento:</label>
                                                       <div class="col-xs-6">
                                                           <select id="Tic_Cod" name="Tic_Cod" onchange="$('#formFinal').setData($('#Tic_Cod').find('option:selected').data(),'name');"
                                                                   class="form-control input-xs" required=""> -->
						<!--  <?php foreach ($comprobantes as $c) { ?>-->
						<!-- echo "<option data--descripcion='$c[Tic_Des]' data--compra_-cod='$c[Tic_Cod]' value='$c[Tic_Cod]'>* $c[Tic_Des]</option>";-->
						<!--  <option value="<?php echo $c['Tic_Cod']; ?>"> -->
						<!--  <?php echo $c['Tic_Des']; ?>-->
						<!--  </option>
                                                               <?php } ?>-->
						<!-- </select>
                                                       </div>
                                                   </div> -->
						<!-- <div class="form-group">
                                                       <label class="col-xs-2 control-label label-xs required">Num. Factura:</label>
                                                       <div class="col-xs-6">
                                                           <input id="Rvt_Doc" name="Rvt_Doc" data-name="Rvt_Doc" type="text" class="form-control input-xs secuencia" tabindex="-1" required>
                                                       </div>
                                                   </div> -->
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs required" style='text-align:left;'>Num. Reten:</label>
							<div class="col-xs-6">
								<div class="input-group input-group-xs">
									<input id="Rvt_Num" name="Rvt_Num" data-name="Rvt_Num" type="text" onchange="retencionesExistentes();" onkeyup="javascript:this.value=this.value.toUpperCase();"
									 class="form-control input-xs trigger" required="" data-container="body" data-toggle="popover" required>
									<span class="input-group-addon validate">
										<i></i>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs required" style='text-align:left;'>Autorización:</label>
							<div class="col-xs-6">
								<input name="Rvt_Aut" data-name="Rvt_Aut" type="number" class="form-control input-xs nospin" tabindex="-1" required>
							</div>
						</div>
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs required" style='text-align:left;'>Forma&nbsp;Pago:</label>
							<!-- <div class="col-xs-8">
                                                         <input  id="Tpc_Cod" name="Pld_Cod" type="text" class="hidden" />
                                                      </div> -->
							<div class="col-xs-6">
								<select id="Tpc_Cod" name="Tpc_Cod" onchange="" class="form-control input-xs">
									<?php foreach ($tipoPagoCom as $c) { ?>
									<option value="<?php echo $c['Tpc_Cod']; ?>">
										<?php echo $c['Tpc_Sri']." - ". $c['Tpc_Des']; ?>
									</option>
									<?php } ?>
								</select>
							</div>

						</div>
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs required" style='text-align:left;'>Tipo Emisión:</label>
							<div class="col-xs-6">
								<div class="form-check">
									<input class="form-check-input" type="radio" name="Rvt_Tem" id="inlineRadio1" value="E">
									<label class="form-check-label" for="inlineRadio1">Electronica</label>
								</div>
								<div class="form-check">
									<input class="form-check-input" type="radio" name="Rvt_Tem" id="inlineRadio2" value="F">
									<label class="form-check-label" for="inlineRadio2">Fisica</label>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs" style='text-align:left;'>Observaciones:</label>
							<div class="col-xs-10">
								<textarea name="Rvt_Obs" type="text" class="form-control span datatitle"></textarea>
							</div>
						</div>
					</fieldset>

				</div>
				<div class="col-sm-6">
					<div class="jqHeaderFirst jqFirst">
						<table id="detalle"></table>
						<div id="detallePager"></div>
					</div>
				</div>
				<div class="form-group Titulos2">
					<div class="col-sm-12">
						<hr/>
						<b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (
						<span class="required"></span> ) son campos obligatorios.</div>
				</div>
				<div style="text-align: center;padding-top: 5px;">
					<button type="button" id="btn_gua" name="btn_gua" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();">
						<span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
				</div>
			</form>
		</div>
	</div>
	<!-- OTRO TAB -->
	<div id="tabs-2">
		<div id="tab2" class="row">
			<form id="frm_mod_ret" name="frm_mod_ret" class="form-horizontal normal" action="javascript:$('#tableRetenciones').Search('#frm_mod_ret','searchAllRet');">
				<fieldset class="exa-fieldset">
					<legend class="Titulos2">B&uacute;squeda de Retenciones</legend>
					<div class="form-group">
						<label class="col-sm-2 control-label label-xs">Filtrar por:</label>
						<div class="col-sm-5 radioset">
							<input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)" />
							<label for="rad_ba1">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
							<input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" />
							<label for="rad_ba2">&nbsp;&nbsp;Retenci&oacute;n&nbsp;&nbsp;</label>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
						<div class="col-sm-5">
							<div class="input-group">
								<input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda"
								 autofocus="">
								<span class="input-group-btn">
									<button id="btnSearch" onclick="this.form.submit()" class="btn btn-success btn-xs" type="button" title="Buscar Cliente">
										<span class="glyphicon glyphicon-search"></span> Buscar</button>
								</span>
							</div>
						</div>
					</div>
				</fieldset>
			</form>
			<div class="col-sm-12">
				<div class="jqHeaderFirst jqFirst">
					<table id="tableRetenciones"></table>
					<div id="tableRetencionesPager"></div>
				</div>
			</div>
			<div id="docDetaDialog" title="Retenciones - Bancarias">
				<fieldset class="exa-fieldset">
					<legend class="Titulos2"> Datos Retención Bancaria:</legend>
					<div class="form-horizontal normal" style="padding: 0 4px;">
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs">Cod.Int:</label>
							<div class="col-xs-2">
								<span name="Rvt_Cod" class="form-control input-xs"></span>
							</div>
						</div>
						<div class="form-group">
							<!--  <label class="col-xs-2 control-label label-xs">Fact.Num:</label>
                                                        <div class="col-xs-4" ><span name="Rvt_Doc"  class="form-control input-xs"></span></div> -->
							<label class="col-xs-2 control-label label-xs">Ret.Num:</label>
							<div class="col-xs-9" style="text-align: center;">
								<span name="Rvt_Num" class="form-control input-xs"></span>
							</div>
						</div>
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
							<div class="col-xs-4">
								<span name="Prs_Ced" class="form-control input-xs"></span>
							</div>
							<label class="col-xs-1 control-label label-xs">Fecha:</label>
							<div class="col-xs-3" style="text-align: center;">
								<span name="Rvt_Fec" class="form-control input-xs"></span>
							</div>
						</div>
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs">Cliente:</label>
							<div class="col-xs-9">
								<span name="cliente" class="form-control input-xs"></span>
							</div>
						</div>
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs">Cuenta:</label>
							<div class="col-xs-9">
								<span name="Pld_Des" class="form-control input-xs"></span>
							</div>
						</div>
						<div class="form-group">
							<label class="col-xs-2 control-label label-xs">Observación:</label>
							<div class="col-xs-9">
								<textarea name="Rvt_Obs" type="text" class="form-control span datatitle" disabled></textarea>
							</div>
						</div>
					</div>
					<div class="form-group condensed">
						<div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;">
							<b>Usuario:</b>
							<span id="usuario" name="usuario" class="databind"></span>
						</div>
					</div>
				</fieldset>
				<fieldset class="exa-fieldset" id="venViewGrid">
					<legend class="Titulos2">Detalle Retención:</legend>
					<div class="form-horizontal normal" style="padding: 0 4px;">
						<div class="form-group condensed">
							<div class="col-xs-12">
								<div class="pull-right">
									<table id="detaDocu"></table>
								</div>
							</div>
						</div>
				</fieldset>
				</div>
			</div>
			<!--DIV DE EDICION DE RETENCIÓN -->
			<div class="row" id="documentoVistaER" style="display:none;">
				<div class="col-md-10 col-sm-8 col-md-offset-1">
					<form id="frm_mod_ret_edi" name="frm_mod_ret_edi" class="form-horizontal normal" action="javascript:guardarModificacion('frm_mod_ret_edi','saveModReten');">
						<input id="Rvt_Cod" name="Rvt_Cod" type="text" class="hidden" />
						<input name="Com_Cod" type="text" class="hidden" />
						<div class="col-md-6 col-sm-8 col-md-offset-3">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">Datos de la Retención</legend>
								<div class="form-group">
									<label class="col-xs-2 control-label label-xs required">Periodo:</label>
									<div class="col-xs-3">
										<input id="Pec_Cod" name="Pec_Cod" type="text" style="display:none;" />
										<input id="Pec_Fei_anho" name="Pec_Fei_anho" type="text" class="form-control input-xs " style="text-align: center;" readOnly=""> </input>
									</div>
									<label class="col-xs-3 control-label label-xs">Fecha:</label>
									<div class="col-xs-3">
										<input id="Rvt_Fec" name="Rvt_Fec" class="form-control input-xs datepickers" style="text-align: center;"> </input>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-3 control-label label-xs required">Cliente:</label>
									<div class="col-xs-8">
										<input id="cliente" name="cliente" class="form-control input-xs readOnly" readOnly=""> </input>
										<input id="Cli_Cod" name="Cli_Cod" type="text" style="display:none;" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-3 control-label label-xs">Dirección:</label>
									<div class="col-xs-8">
										<div class="input-group input-group-xs">
											<input name="Prs_Dir" data-name="Prs_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
											<span class="input-group-addon bold">e-mail:</span>
											<input name="Prs_Cor" data-name="Prs_Cor" type="text" class="form-control span datatitle" readonly="" tabindex="-1" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-3 control-label label-xs">Observaciones:</label>
									<div class="col-xs-8">
										<textarea name="Rvt_Obs" type="text" class="form-control span datatitle"></textarea>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-3 control-label label-xs required">Cuenta:</label>
									<div class="col-xs-8">

										<input id="Pld_Cod" name="Pld_Cod" type="text" class="hidden" />
										<input id="param_Cuenta" name="param_Cuenta" class="form-control input-xs readOnly" style="text-align: left;" readOnly=""> </input>
										<!-- <span class="input-group-addon bold">Desc.</span>
																<input id="Pld_Des" name="Pld_Des" class="form-control input-xs readOnly"  readOnly=""> </input> -->

										<!-- <div class="col-xs-8">
																<select id="Pld_Cod" name="Pld_Cod" onchange=" "
																		class="form-control input-xs" required="">
																			<?php foreach ($bancos as $bank) { ?>
										<option value="<?php echo $bank['Pld_Cod']; ?>" data-extra="<?php echo $bank['Ban_Cod']; ?>">
											<?php echo $bank['Pld_Des']; ?>
										</option>
										<?php } ?>
										</select>
										<!--<input name="Ban_Pld_Cod" type="text"  value="<?php echo $bank['Pld_Cod']; ?>"/>-->
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-3 control-label label-xs">Formas de Pago:</label>
									<div class="col-xs-8">
										<select id="Tpc_Cod" name="Tpc_Cod" onchange="" class="form-control input-xs" required="">
											<?php foreach ($tipoPagoCom as $c) { ?>
											<option value="<?php echo $c['Tpc_Cod']; ?>">
												<?php echo $c['Tpc_Sri']." - ". $c['Tpc_Des']; ?>
											</option>
											<?php } ?>
										</select>

									</div>
								</div>

								<!-- <div class="form-group">
															<label class="col-xs-3 control-label label-xs">Documento:</label>
															<div class="col-xs-4">
																<input id="Tic_Cod" name="Tic_Cod" type="text" style="display:none;" />
																<input id="Tic_Des" name="Tic_Des" class="form-control input-xs readOnly" readOnly=""> </input>
															</div>
														</div>
														<div class="form-group">
															<label class="col-xs-3 control-label label-xs required">Num. Factura:</label>
															<div class="col-xs-6">
																<input id="Rvt_Doc" name="Rvt_Doc" data-name="Rvt_Doc" type="text" class="form-control input-xs secuencia" tabindex="-1" required>
															</div>
														</div> -->
								<div class="form-group">
									<label class="col-xs-3 control-label label-xs required">Num. Reten:</label>
									<div class="col-xs-6">
										<div class="input-group input-group-xs">
											<input id="Rvt_Num" name="Rvt_Num" data-name="Rvt_Num" type="text" onkeyup="verificarTamanho()" class="form-control input-xs trigger"
											 required="" data-container="body" data-toggle="popover" required>
											<span class="input-group-addon validate">
												<i></i>
											</span>
										</div>

									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-3 control-label label-xs ">Autorización:</label>
									<div class="col-xs-6">
										<div class="input-group input-group-xs">
											<input name="Rvt_Aut" data-name="Rvt_Aut" type="number" class="form-control input-xs trigger" required="" data-container="body"
											 data-toggle="popover" />
											<span class="input-group-addon validate">
												<i></i>
											</span>
										</div>

									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-3 control-label label-xs required">Tipo Emisión:</label>
									<div class="col-xs-6">
										<div class="form-check">
											<input class="form-check-input" type="radio" name="Rvt_Tem" id="inlineRadio1" value="E">
											<label class="form-check-label" for="inlineRadio1">Electronica</label>

										</div>
										<div class="form-check">
											<input class="form-check-input" type="radio" name="Rvt_Tem" id="inlineRadio2" value="F">
											<label class="form-check-label" for="inlineRadio2">Fisica</label>
										</div>
									</div>
								</div>

							</fieldset>
						</div>


						<div class="col-sm-12">
							<div class="jqHeaderFirst jqFirst">
								<table id="detalleM"></table>
								<div id="detalleMPager"></div>
							</div>
						</div>
						</br>
						<div id="btn_atras" class="col-sm-12" style="text-align: left;padding-top: 15px;">
							<button type="button" class="btn btn-sm btn-inverse" onclick="clearDocument();$('#documentoVistaER').moveComp('#tab2').updateGridsSizes();">
								<i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
							<button type="button" id="btn_guardado" name="btn_guardado" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();"
							 disabled="disabled">
								<span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
						</div>
					</form>


				</div>
			</div>
		</div>
		<!-- OTRO TAB -->
		<div id="tabs-3">
			<div id="tab2" class="row">
				<form id="frm_mod_ret_con" name="frm_mod_ret_con" class="form-horizontal normal" action="javascript:$('#tableRetencionesC').Search('#frm_mod_ret_con','searchAllRetCon');">
					<fieldset class="exa-fieldset">
						<legend class="Titulos2">B&uacute;squeda de Retenciones</legend>
						<div class="form-group">
							<label class="col-sm-2 control-label label-xs">Filtrar por:</label>
							<div class="col-sm-5 radioset">
								<input id="rad_bac1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)" />
								<label for="rad_bac1">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
								<input id="rad_bac2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" />
								<label for="rad_bac2">&nbsp;&nbsp;Retenci&oacute;n&nbsp;&nbsp;</label>
								<input id="rad_bac3" name="op_opciones" type="radio" value="e" onclick="setfocus(this.form.search)" />
								<label for="rad_bac3">&nbsp;&nbsp;Inactivos&nbsp;&nbsp;</label>
								<input id="rad_bac4" name="op_opciones" type="radio" value="f" onclick="setfocus(this.form.search)" />
								<label for="rad_bac4">&nbsp;&nbsp;Por Fecha&nbsp;&nbsp;</label>
							</div>
						</div>
						<div id="divFecha" class="form-group" style="display:none;">
							<div class="col-xs-2"></div>
							<div class="col-xs-4">
								<div class="input-group input-group-xs por_fecha">
									<span class="input-group-addon">
										<span class=""></span>
									</span>
									<span class="input-group-addon alert-info">Desde</span>
									<input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control" disabled="" />
									<span class="input-group-addon alert-info">Hasta</span>
									<input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control" disabled="" />
								</div>
							</div>

						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
							<div class="col-sm-5">
								<div class="input-group">
									<input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda"
									 autofocus="">
									<span class="input-group-btn">
										<button id="btnSearch" onclick="this.form.submit()" class="btn btn-success btn-xs" type="button" title="Buscar Cliente">
											<span class="glyphicon glyphicon-search"></span> Buscar</button>
									</span>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
				<div class="col-sm-12">
					<div class="jqHeaderFirst jqFirst">
						<table id="tableRetencionesC"></table>
						<div id="tableRetencionesCPager"></div>
					</div>
				</div>
			</div>
		</div>

	</div>
	</div>
	</div>
	</div>
	</div>
	</div>
	<div id="clientDialog" title="B&uacute;squeda de Clientes ">
		<form>
			<input type="hidden" id="BakCodAux" name="Bak_Cod">
		</form>
	</div>
	<div id="codiDialog" title="B&uacute;squeda de Retenciones ">
		<form>
			<input type="hidden" id="RetCodAux" name="RetCodAux">
			<input type="hidden" id="PecCod" name="PecCod">
		</form>
	</div>
	<div id="proveeDialog" title="B&uacute;squeda de Proveedores ">
		<form>
			<input type="hidden" id="ProvCodAux" name="ProvCodAux">
		</form>
	</div>
	<div id="factCompraDialog" title="B&uacute;squeda de Facturas Compra "></div>
	<script>
		$('#Pec_Cod').trigger('change');
	</script>
	<script src="../VALIDACIONES/fact_val_ret_cre_vta_1.0.js?k=36546"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
	</BODY>

</HTML>