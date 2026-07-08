<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creacion  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factu.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../tesoreria/LOGICA/tes_log_anticipo_prv.php');
	
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Factu($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Factu;

$hoy = date("Y-m-d");
$mes = date("m");

/* Consulta del tipo de proveedores */
if(isset($provAjax)){  
    $obBD_con1->getPageGridJson(2, $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);    
}
if(isset($provAjax2)){  
    $responce['rows'] = $obBD_con1->getArrayConsulta(30, $Prs_Ced.'*'.$Ses_Emp_Cod, $obBD_conexion);
    $responce['total'] = count($responce['rows']);
    $obBD_con1->echoJson($responce);
}
/* Consulta del tipo de documento a modificar */
if(isset($ajaxCodDoc)){ 
    $resp=array('success'=>false, 'Mod_Tic_Cod'=>'', 'Cop_Ntd'=>'', 'Mod_Tic_Des'=>'Ninguno');
    if(!empty($Cop_Ntd)){
        $row_rs_CodDoc = $obBD_con1->getRowConsulta(3, $Cop_Ntd,$obBD_conexion);
        if($row_rs_CodDoc['Tic_Cod']!="")
            $resp=array('success'=>true, 'Mod_Tic_Cod'=>$row_rs_CodDoc['Tic_Cod'], 'Cop_Ntd'=>$row_rs_CodDoc['Tic_Sri'], 'Mod_Tic_Des'=>$row_rs_CodDoc['Tic_Des']);
    }else $resp['success']=''; 
    $obBD_con1->echoJson($resp);   
}

/* Consulta datos del documento */
if(isset($ajaxCopNum)){
    $resp=array('success'=>true);
    if(!empty($Tic_Cod)&&!empty($Cop_Num)){
        $row_rs_CodDoc = $obBD_con1->getRowConsulta(7,$Prv_Cod.'*'.$Tic_Cod.'*'.$Cop_Num,$obBD_conexion);
        if($row_rs_CodDoc['Cop_Cod']!="")
            $resp=array('success'=>false, 'message'=>'El documento ya Existe en el Sistema!');
    }else $resp['success']=''; 
    $obBD_con1->echoJson($resp);      
}
if(isset($guardaProvAjax)){    
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion);                  
        if(empty($Prs_Cod)){
            $obBD_con1->operacionobBD(31,$data,$obBD_conexion); 
            $data['Prs_Cod'] = $obBD_con1->insercionid ($obBD_conexion);
        }
        $obBD_con1->operacionobBD(32,$data,$obBD_conexion); 
        $data['Prv_Cod'] = $obBD_con1->insercionid ($obBD_conexion);
        $data['proveedor'] = trim($data['Prs_Ape'].' '.$data['Prs_Nom']);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if($obBD_con1->Error==0) {$responce=array('success'=>true,'prov'=>$data);} else {$responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_con1->MsgError);}
    $obBD_con1->echoJson($responce);   
}

/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(8, $Ses_Emp_Cod,$obBD_conexion);

/* Consulta del tipo de productos */
if(isset($proAjax)){ 
    if(!empty($Cop_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion); else $Pec_Cop=array('Pla_Cod'=>null);
    $responce=$obBD_con1->getPageGrid(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);   

    if($responce['records']*1>0){        
        if($configs['Cof_Con']=='S'&&!empty($Pec_Cop['Pla_Cod'])){
            foreach ($responce['rows'] AS &$r){
                $cuenta = $obBD_con1->getRowConsulta(16,$Pec_Cop['Pla_Cod'].'*'.$r['Pro_Cod'].'*'.'C', $obBD_conexion);
                if(!empty($cuenta['Pld_Cod'])) $r=array_merge($r,$cuenta);
            }unset($r);
        }
    }
    $obBD_con1->echoJson($responce); 
}
/* Consulta datos del documento */
if(isset($ajaxModDoc)){ 
    $resp=array('success'=>false, 'message'=>'El documento no Existe en el Sistema!', 'Mod_Cop_Cod'=>'', 'Cop_Nns'=>$Cop_Nns, 'Mod_Cop_Fec'=>'', 'Mod_Cod_Aut'=>'', 'Mod_Cpp_Cod'=>'' );
    if(!empty($Mod_Tic_Cod)&&!empty($Cop_Nns)){
        $row_rs_CodDoc = $obBD_con1->getRowConsulta(7,$Prv_Cod.'*'.$Mod_Tic_Cod.'*'.$Cop_Nns,$obBD_conexion);
        if($row_rs_CodDoc['Cop_Cod']!=""){
            $resp=array('success'=>true, 'Mod_Cop_Cod'=>$row_rs_CodDoc['Cop_Cod'], 'Cop_Nns'=>$Cop_Nns, 'Mod_Cop_Fec'=>$row_rs_CodDoc['Cop_Fec'], 'Cop_Nna'=>$row_rs_CodDoc['Cop_Aut'], 'Mod_Cpp_Cod'=>$row_rs_CodDoc['Cpp_Cod'], 'Com_Cod'=>$row_rs_CodDoc['Com_Cod']);
            if(!empty( $row_rs_CodDoc['Cpp_Cod'])){
                $saldo = $obBD_con1->getRowConsulta(97, $row_rs_CodDoc['Cpp_Cod'], $obBD_conexion);
                $resp['Cop_Saldo']=$saldo['Cop_Saldo'];
            } else {
                $resp['Cop_Saldo'] = '0.00';
            }
        }

        if($configs['Cof_Con']=='S'&&!empty($row_rs_CodDoc['Cpp_Cod'])){
            $cuentas = $obBD_con1->getRowConsulta(37, $row_rs_CodDoc['Com_Cod'], $obBD_conexion);
            $resp['Pld_Cod_Pag']=$cuentas['Pld_Cod'];
            //foreach ($cuentas AS $row)
            //    $resp[cuentas']=$resp['cuenta'].'<option value="'.$row['Pld_Cod'].'" data-extra="'.$row['extra'].'">'.$row['Pld_Des'].'</option>';            
        }
    }else $resp['success']=''; 
    $obBD_con1->echoJson($resp);   
}

/* reviso las cuentas pago */
if(isset($cuentasPago)){     
    $responce['cuentas']='';
    $Pec_Cod = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion);  
    if($For_Cod*1==2)
        $cuentas = $obBD_con1->getArrayConsulta(23, $Pec_Cod['Pla_Cod'].'*'.$For_Cod, $obBD_conexion);
    else
        $cuentas = $obBD_con1->getArrayConsulta(22, $Pec_Cod['Pla_Cod'].'*'.'1', $obBD_conexion);
        
    $responce['total']=count($cuentas);
    foreach ($cuentas AS $row)
        $responce['cuentas']=$responce['cuentas'].'<option value="'.$row['Pld_Cod'].'" data-extra="'.(isset($row['extra'])?$row['extra']:'').'" '.(isset($Pld_Cod)&&$row['Pld_Cod']==$Pld_Cod?'selected="selected"':'').'>'.$row['Pld_Des'].'</option>';
    if($responce['total']>1)
        $responce['cuentas']="<option value=''>Seleccione...</option>".$responce['cuentas'];
    $responce['success']=true; 
    $obBD_con1->echoJson($responce);     
}
/* reviso los ivas */
if(isset($Check_Iva)){     
    //  $responce['varIvas']=(/*!empty($Tic_Sri)&&('0'.$Tic_Sri)*1==4&&*/$Cop_Fec<='2017-05-31');
    //  if($responce['varIvas'])
        $responce['ivas']  = $obBD_con1->getArrayConsulta(18, '', $obBD_conexion);
    //  else
        $responce['iva_activo']  = $obBD_con1->getRowConsulta(19, $Cop_Fec, $obBD_conexion); 
    $responce['varIvas']=true;    
    $responce['total']=count($responce['ivas']);
    $responce['options']='';
    foreach ($responce['ivas'] AS $row){
        $responce['options']= $responce['options'].'<option value="'.$row['Iva_Cod'].'" data-ivapor="'.$row['Iva_Por'].'" '.($responce['iva_activo']['Iva_Por']==$row['Iva_Por']?'selected="selected"':'').'>'.$row['Iva_Por'].' %</option>';
    }
    if($configs['Cof_Con']=='S') {
        $responce['cuentas']='';
        $Pec_Cod = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion);  
        $iva_pag = $obBD_con1->getArrayConsulta(20, $Pec_Cod['Pla_Cod'], $obBD_conexion);
        foreach ($iva_pag AS $row)
            $responce['cuentas']=$responce['cuentas'].'<option value="'.$row['Pld_Cod'].'" >'.$row['Pld_Des'].'</option>';
    }
    $responce['success']=true; 
    $obBD_con1->echoJson($responce);  
}

/* Guardar documento */
if(isset($saveDocument)){
	$obBD_con1->validaCierrePeriodo('compras','Cop_Fec','Cop_Cod',$Cop_Fec,null,$obBD_conexion);
    $responce=array('success'=>false);    
    /* Que sea vendedor */
    $vendedor = $obBD_con1->getRowConsulta(10,$Ses_Suc_Cod.'*'.$Ses_Prs_Cod,$obBD_conexion);
    if(empty($vendedor['Vnd_Cod'])){ $responce['message']="No tiene permisos de Vendedor!";  }
    $Vnd_Cod=$vendedor['Vnd_Cod'];
    /* valida que no exista el documento */
    $row_rs_CodDoc = $obBD_con1->getRowConsulta(7,$Prv_Cod.'*'.$Tic_Cod.'*'.$Cop_Num,$obBD_conexion);
    if(!empty($row_rs_CodDoc['Cop_Cod'])) { $responce['message']="El doc. $Tic_Des No. $Cop_Num ya existe!"; }
    /* Valida que los Periodos Existan */
    $Pec_Cop = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion);    
    if(empty($Pec_Cop['Pec_Cod'])){ $responce['message']="No Existe Periodo para la Fecha: $Cop_Fec!"; }
    $Pec_Cod=$Pec_Cop['Pec_Cod'];
    /* cierro en caso de error */
    if(!empty($responce['message'])){ echo json_encode($responce);exit(); }

    $Cop_Des=0; 
    $NCRED=(($Tic_Sri*1==4)?true:false);

    $obBD_ins1 =  new Class_Log_Datos_Factu;
    $obBD_conexionIns = new Class_Log_Conexion_Factu($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);  
    try{
        /* Cabecera de la factura de compra */
        $meseCop = explode('-', $Cop_Fec);
        $Cop_Sec= $obBD_con1->codigoSecMensualAuto($Pec_Cod, $meseCop[1], $obBD_conexion); // Secuencia de compras por mes
        $obBD_ins1->operacionobBD(11, $Tic_Cod.'*'.$Prv_Cod.'*'.$Ciu_Cod.'*'.trim($Cop_Num).'*'.trim($Cop_Aut).'*'.$Cop_Fec.'*'.$hoy.'*'.trim($Cop_Obs).'*'.$Cop_Cad.'*'.$Cop_Imf.'*'.$Tri_Cod.'*'.$Cop_Des.'*'.$Pec_Cod.'*'.(isset($Tpc_Cod)?$Tpc_Cod:'').'*'.$Cop_Ntd.'*'.$Cop_Nns.'*'.$Cop_Nna.'*'.$Vnd_Cod.'*'.$Cop_Sec.'*', $obBD_conexionIns);  
        $Cop_Cod = $obBD_ins1->insercionid ($obBD_conexionIns);
        
        /* Creacion del comprobante contable */
        if($configs['Cof_Con']=='S'){
            $Com_Con = $Cop_Obs; $Iva_Costo=0;
            $Tia_Asi = $obBD_con1->getRowConsulta(13, $For_Cod, $obBD_conexion);            
            $meseCom = explode('-', $Com_Fec);
            $Com_Num= $obBD_con1->getComNumPecAuto($Tia_Asi['Tia_Cod'], $Pec_Cod, $Com_Fec, $obBD_conexion); // Secuencia de comprobante por mes y por tipo
            $campo='Prv_Cod';
            /* Cabecera del Comprobante */
            $obBD_ins1->operacionobBD(14, $Pec_Cod.'*'.$Prv_Cod.'*'.$Com_Num.'*'.$Com_Fec.'*'.trim($Com_Con).'*'.$Tia_Asi['Tia_Cod'].'*'.$t_rubros.'*'.trim($Cop_Obs).'*'.$campo, $obBD_conexionIns);
            $Com_Cod = $obBD_ins1->insercionid ($obBD_conexionIns);
            $obBD_ins1->operacionobBD(15, $Com_Cod.'*'.$Cop_Cod, $obBD_conexionIns); // relacion compra comprobante
            foreach ($items as &$item){ /* Inserta datos en el detalle del asiento (por items) */
                $addIva=round((isset($item['Cop_Cos'])&&$item['Cop_Cos']=='S'?($item['Cop_Imp']*$item['Iva_Por']/100):0),2);
                $Iva_Costo=$Iva_Costo+$addIva;
                if($NCRED){
                    // $cuenta = $obBD_con1->getRowConsulta(28,$Pec_Cop['Pla_Cod'].'*'.($Cal_Inv=='S'?'CDV':'CDS'), $obBD_conexion); 
                    if ($Cal_Inv=='S') {
                        $cuenta = $obBD_con1->getRowConsulta(28,$Pec_Cop['Pla_Cod'].'*CDV', $obBD_conexion); 
                        $item['Pld_Cod']=$cuenta['Pld_Cod'];
                        $item['Pld_Des']=$cuenta['Pld_Des'];
                        if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable'.($NCRED?' <i>descuentos/devoluciones</i>':'').' del producto: <u>'.$item['Ite_Lar'].'</u>!');
                    }
                } else {
                    // $cuenta = $obBD_con1->getRowConsulta(16,$Pec_Cop['Pla_Cod'].'*'.$item['Pro_Cod'].'*'.'C', $obBD_conexion); 
                    $cuenta = $obBD_con1->getRowConsulta(16,$Pec_Cop['Pla_Cod'].'*'.$item['Pro_Cod'].'*'.'C', $obBD_conexion); 
                    $item['Pld_Cod']=$cuenta['Pld_Cod'];
                    $item['Pld_Des']=$cuenta['Pld_Des'];
                }

                // if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable'.($NCRED?' <i>descuentos/devoluciones</i>':'').' del producto: <u>'.$item['Ite_Lar'].'</u>!');
                
                // $item['Pld_Cod']=$cuenta['Pld_Cod']; // linea reemplazada 
                // $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.(!$NCRED?'D':'H').'*'.($item['Cop_Imp']+$addIva).'*'.$cuenta['Pld_Des'].'*'.$item['Ite_Lar'].'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Item
				$obBD_ins1->operacionobBD(17, $Com_Cod.'*'.(!$NCRED?'D':'H').'*'.($item['Cop_Imp']+$addIva).'*'.$item['Pld_Des'].'*'.$item['Ite_Lar'].'*'.$item['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // reemplazo cuenta por item
                $ultimo_asiento = $obBD_ins1->insercionid ($obBD_conexionIns);
				
            } unset($item);            
            /* IVA */
            $iva=$t_iva*1-$Iva_Costo;
            if($iva>0){
                if(empty($Iva_Pag))  throw new Exception('Revisar la parametrizacion contable de: <u>Iva Pagado</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.(!$NCRED?'D':'H').'*'.$iva.'*'.'IVA'.'*'.'IVA'.'*'.$Iva_Pag, $obBD_conexionIns);  // inserta asiento // Iva
				$ultimo_asiento = $obBD_ins1->insercionid ($obBD_conexionIns);
            }
            /* Pago */
            if(empty($Pag_Pld))  throw new Exception('Revisar la parametrizacion contable de la cuenta: <u>Pago '.($For_Cod==1?'Contado':'Credito').'</u>!');
                if($NCRED && $For_Cod == 2 && $_POST['Ant_Prov'] > 0){
                	$obBD_ins1->operacionobBD(17, $Com_Cod.'*'.(!$NCRED?'H':'D').'*'.$_POST['Cop_Saldo'].'*'.''.'*'.('Doc.'.$Cop_Num).'*'.$Pag_Pld, $obBD_conexionIns);  // inserta asiento // Descuento y devoluciones 
                	//INSERTAR EL OTRO ASIENTO DE ANTICIPO A PROVEEDORES POR EL VALOR DEL ANTICIPO
                    $Pld_Cod_Debe = $obBD_con1->getRowConsulta(103, "", $obBD_conexion);                  
                    $obBD_ins1->operacionobBD(109, array('Com_Cod'=>$Com_Cod,'Asi_Deh'=>'D','Asi_Glo'=>'Anticipo a proveedores','Asi_Val'=>$_POST['Ant_Prov'],'Pld_Cod'=>$Pld_Cod_Debe['Pld_Cod']), $obBD_conexionIns); 
                    $asiento_anticipo_proveedor = $obBD_ins1->insercionid ($obBD_conexionIns);
                } else {
                 	$obBD_ins1->operacionobBD(17, $Com_Cod.'*'.(!$NCRED?'H':'D').'*'.$t_rubros.'*'.''.'*'.('Doc.'.$Cop_Num).'*'.$Pag_Pld, $obBD_conexionIns);  // inserta asiento // Iva
                    $ultimo_asiento = $obBD_ins1->insercionid ($obBD_conexionIns);
                }          
	        /* Deuda */
            if(!empty($Mod_Cpp_Cod)){
                    if($NCRED && $For_Cod == 2 && $_POST['Ant_Prov'] > 0){
                        //ANTICIPO A PROVEEDOR
                        $Pec_Cod=$obBD_ins1->getRowConsulta(105, $Cop_Fec, $obBD_conexion);
                        $var_mes = explode('-', $Cop_Fec);

                        //OBTENER TIA_COD CON UNA CONSULTA EN TIP_ASIENTO POR ABREVIATURA EG
                        $Tipo_Asi=$obBD_ins1->getRowConsulta(104, "", $obBD_conexion);

                        $obBD_con2 =  new Class_Log_Datos_Ant_Prv;
                        $Com_Num = $obBD_con2->codigoComprAuto($Tipo_Asi['Tia_Cod'], $Pec_Cod['Pec_Cod'], $var_mes[1], $obBD_conexion);

                    //insertamos un comprobante y extraemos el id ingresado
                    //EL ATP_VAL DE EL INPUT DE ANTICIPO_PROVEEDOR DE LA INTERFAZ 
                    //$obBD_ins1->operacionobBD(106, array('Pec_Cod'=>$Pec_Cod['Pec_Cod'], 'Prv_Cod'=>$Prv_Cod, 'Com_Num'=>$Com_Num, 'Com_Fec'=>$Cop_Fec, 'Com_Con'=> 'Anticipo generado por N/Credito Doc. ' . $Cop_Num, 'Com_Val'=>$_POST['Ant_Prov'], 'Tia_Cod'=>$Tipo_Asi['Tia_Cod']), $obBD_conexionIns);
                    //$ultimo_comprobate = $obBD_ins1->insercionid ($obBD_conexionIns);


                    //insertamos un anticipo a proveedores
                        //CAMBIAR ATP_VAL por el anticipo
                        $obBD_ins1->operacionobBD(107, array('Atp_Fec'=>$Cop_Fec,'Atp_Val'=>$_POST['Ant_Prov'],'Atp_Obs'=>'Anticipo generado por N/Credito Doc.' . $Cop_Num,'Com_Cod'=>$Com_Cod,'Prv_Cod'=>$Prv_Cod), $obBD_conexionIns);
                        $ultimo_anticipo = $obBD_ins1->insercionid ($obBD_conexionIns);


                    // insertamos un asiento de debe
                    //FALTA CAMBIAR EL ASI_VAL POR ATP_VAL Y OBTENER EL PLD_COD DE ANTICIPO A PROVEEDOR
                       //$Pld_Cod_Debe = $obBD_con1->getRowConsulta(103, "", $obBD_conexion);                  
                       //$obBD_ins1->operacionobBD(109, array('Com_Cod'=>$Com_Cod,'Asi_Deh'=>'D','Asi_Glo'=>'Anticipo a proveedores','Asi_Val'=>$_POST['Ant_Prov'],'Pld_Cod'=>$Pld_Cod_Debe['Pld_Cod']), $obBD_conexionIns);


                    // insertamos un asiento de haber y un pago
                    //FALTA CAMBIAR EL ASI_VAL POR ATP_VAL Y OBTENER EL PLD_COD DE CAJA EFECTIVO
                        //$Pld_Cod_Haber = $obBD_con1->getRowConsulta(102, "", $obBD_conexion); 
                        //$obBD_ins1->operacionobBD(109, array('Com_Cod'=>$Com_Cod,'Asi_Deh'=>'H','Asi_Glo'=>'N/Credito Doc. ' . $Cop_Num,'Asi_Val'=>$_POST['Ant_Prov'],'Pld_Cod'=>$Pld_Cod_Haber['Pld_Cod']), $obBD_conexionIns);                       
			        //$ultimo_asiento = $obBD_ins1->insercionid ($obBD_conexionIns);

                    //FALTA EL PAG_cOD
                        $obBD_ins1->operacionobBD(108, array('Pap_Cto'=>'','Pap_Ctd'=>'','Pap_Val'=>$_POST['Ant_Prov'],'Atp_Cod'=>$ultimo_anticipo,'Pag_Cod'=>13, 'Asi_Cod'=>$asiento_anticipo_proveedor, 'Pap_Est'=>''), $obBD_conexionIns);
                    //Insertamos el detalle de pago para el comprobante de compra (cambiar t_rubros por el saldo de la interfaz) 
                    $obBD_ins1->operacionobBD(25,$Mod_Cpp_Cod.'*1*'. $Com_Cod.'*'.$Cop_Fec.'*'.$_POST['Cop_Saldo'].'*N/'.'CREDITO'.' '.$Cop_Num.'*'.$ultimo_asiento,$obBD_conexionIns);
                } else {
                    $obBD_ins1->operacionobBD(25,$Mod_Cpp_Cod.'*1*'. $Com_Cod.'*'.$Cop_Fec.'*'.($t_rubros*($NCRED?1:-1)).'*N/'.($NCRED?'CREDITO':'DEBITO').' '.$Cop_Num.'*'.$ultimo_asiento,$obBD_conexionIns);
                }
            }
        }
        /* Inserta datos en el detalle de la compra */
        $kardex=array('IoE'=>'I', 'Kar_Fec'=>$Cop_Fec, 'Kar_Hor'=>date("H:i:s"), 'Cop_Cod'=>$Cop_Cod, 'Vnd_Cod'=>$Vnd_Cod);
        $array_kardex=array();
        foreach ($items as $i => $item){             
            $item['Cop_Cod'] = $Cop_Cod;
            $item['Cop_Int'] = $i+1;            
            $obBD_ins1->operacionobBD(12,$item, $obBD_conexionIns); // inserta item
            /* Control de Inventarios */     
            if($Cal_Inv=='S')
                if($Tic_Sri*1!=0 && $item['Adq_Cor']=='B'){
                    $s_add=true;
                    foreach($array_kardex AS &$k){
                        if($item['Pro_Cod']==$k['Pro_Cod']){
                            $k['Kar_Can']+=(($NCRED?-1:1)*$item['Cop_Can']);
                            $k['Kar_Ims']+=(($NCRED?-1:1)*$item['Cop_Imp']);
                            $k['Kar_Prs']=$k['Kar_Ims']/$k['Kar_Can'];
                            $s_add=false; break;
                        }
                    } unset($k);
                    if($s_add){
                        $kardexIE=array_merge($kardex, array(
                            'Pro_Cod'=>$item['Pro_Cod'],
                            'Iva_Cod'=>$item['Iva_Cod'], 
                            'Kar_Can'=>($NCRED?-1:1)*$item['Cop_Can'],
                            'Kar_Prs'=>$item['Cop_Pru']*1,
                            'Kar_Ims'=>($NCRED?-1:1)*$item['Cop_Imp']
                        ));  array_push($array_kardex, $kardexIE);                  
                    }
                }
        }
        /* registro de kardex y stocks */
        foreach($array_kardex AS $i =>$k){
            $k['Kar_Int']=$i+1;
            $obBD_ins1->updateStockProd($Ses_Suc_Cod, $k, true, $obBD_conexion,$obBD_conexionIns,$Bod_Cod);   
        } 
    }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $responce['message']=$e->getMessage(); echo json_encode($responce); exit(); }
        
    $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);    
    if($obBD_ins1->Error==0){ $responce=array('success'=>true,'Cop_Cod'=>$Cop_Cod,'Cop_Sec'=>$Cop_Sec,'Com_Cod'=>$Com_Cod,'Ret_Cod'=>isset($Ret_Cod)?$Ret_Cod:NULL,'Tic_Des'=>$Tic_Des,'Mes'=>mes($meseCop[1],1)."/$meseCop[0]"); } 
    else{$responce=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_ins1->MsgError);}  
    /* Mostrar los resultados */

	ChromePhp::log($obBD_ins1->MsgError);

    if($responce['success']==true){
        $reportes = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion); 
        if(!empty($Cop_Cod)){
            $responce['Cop_Data']=array('Tic_Des'=>$Tic_Des,'proveedor'=>$proveedor,'Cop_Num'=>$Cop_Num,'Cop_Fec'=>$Cop_Fec,'Cop_Aut'=>$Cop_Aut);
            $responce['Cop_Rows']=$obBD_con1->getArrayConsulta(26,$Cop_Cod,$obBD_conexion);
            $responce['Cop_Link']=baseUrl("../../facturacion/FRONT/fac_pri_fac_detallecompras_1.0.php?com_codigo=$Cop_Cod");
        }
        if(!empty($Com_Cod)){
            $responce['Com_Data']=array('Com_Con'=>$Cop_Obs,'Com_Fec'=>$Com_Fec,'Com_Val'=>$t_rubros,'Tia_Des'=>$Tia_Asi['Tia_Des'],'Codigo'=>$Tia_Asi['Tia_Abr'].'-'.$meseCom[1].'-'.$Com_Num);
            $responce['Com_Rows']=$obBD_con1->getArrayConsulta(27,$Com_Cod,$obBD_conexion);
            $responce['Com_Link']="".(!empty($reportes[1])?$reportes[1]:baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php"))."?codigo=$Com_Cod&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Asi[Tia_Cod]&Pec_Cod=$Pec_Cop[Pec_Cod]";
        }
    }
    $obBD_con1->echoJson($responce);
}

/* buscar cuentas contables */
if (isset($cuenAjax)) {
    if (!empty($Cop_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(9, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
    else $Pec_Cop = array('Pla_Cod' => '');
    $responce = $obBD_con1->getPageGridJson('det_plan.selectWhere', array_merge($_GET, array('where' => array('det_plan.Pla_Cod' => $Pec_Cop['Pla_Cod']), 'setWhere' => array('isActive', 'isDetalle'))), $obBD_conexion);
}

$Pec_Cop = $obBD_con1->getRowConsulta(33,$Ses_Emp_Cod,$obBD_conexion);   
if(!empty($Pec_Cop['Pec_Fei'])) $hoy=($Pec_Cop['Pec_Fei']);//$hoy=substr($Pec_Cop['Pec_Fei'], 0, 4).substr($hoy, 4, 10);
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "N.Cred.Comp Registrar [EXA] "; ?></TITLE>
        <meta charset="UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>    
        <script type="text/ecmascript" src="../VALIDACIONES/fac_val_notas.js?x=def"></script>
        <style>
            .footrow td[aria-describedby="documento_Cop_Imp"],.footrow td[aria-describedby="documento_Cop_Pru"]{padding: 0 !important;}
            .footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact textarea{text-align: left; height: 75px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}            
            #Cal_Inv{ vertical-align: middle; margin-top: -2px; padding: 5px;  -ms-transform: scale(1.4); -moz-transform: scale(1.4); -webkit-transform: scale(1.4); -o-transform: scale(1.4); }
            #resultContent .resp{ font-weight: 700; font-size: 30px; color: #3f3fc1; padding: 0; margin: 0; overflow: hidden; text-overflow: ellipsis; height: 32px;}
            #resultContent .resp span:first-child{color:darkgoldenrod;width: 100px;display: inline-block; margin-left: 42px;}
        </style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Notas de Crédito/Débito</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body"> 
            <div id="documentoMain">
                <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">                
                    <div class="row">
                        <div class="col-xs-5">
                            <fieldset class="exa-fieldset" id="provFormTemp">
                                <legend class="Titulos2">Datos del Proveedor</legend>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>  
                                        <div class="col-xs-6" >
                                            <input name="Prv_Cod" type="text" style="display:none;" />
                                            <input name="op_opciones" type="text" value="c" style="display: none;">  
                                            <div class="input-group input-group-xs">                                          
                                                <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#provDialog',selectProvee);" type="text" placeholder="Ingrese Proveedor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                                <span class="input-group-btn">
                                                    <button id="Prv_Btn" type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                    <button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>    
                                                </span>
                                            </div>
                                        </div>                                      
                                        <label class="col-xs-4 control-label label-xs">Oblig.Contab:&nbsp;<i id="Prv_Con" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Proveedor:</label>  
                                        <div class="col-xs-6" ><span name="proveedor" class="form-control input-xs databind datatitle"></span></div>                                        
                                        <label class="col-xs-4 control-label label-xs">Contr.Especial:&nbsp;<i  id="Prv_Esp" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label> 
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Dirección:</label>  
                                        <div class="col-xs-10" ><span name="Prs_Dir" type="text" class="form-control input-xs datatitle"></span></div>                    
                                    </div>
                            </fieldset>
                            <fieldset class="exa-fieldset" id="modiFormTemp">
                                <legend class="Titulos2">Documento a Modificar</legend>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs required">Codigo:</label> 
                                    <input name="Mod_Tic_Cod" type="text" style="display:none;" />
                                    <div class="col-xs-3" >                                    
                                        <div class="input-group input-group-xs">                                          
                                            <input type="text" id="Cop_Ntd" name="Cop_Ntd" onchange="selectTicSri(this.value)" class="form-control input-xs" readonly="readonly" tabindex="12"  required="" />
                                            <span class="input-group-addon validate" style="display: none;"><i></i></span>
                                            <span class="input-group-addon" title="Codigo SRI del tipo de documento"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                        </div>
                                    </div>                                
                                    <div class="col-xs-7" >
                                        <span name="Mod_Tic_Des" class="form-control input-xs" /></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs required">Número:</label>  
                                    <input type="text" name="Mod_Cop_Cod" class="form-control input-xs" style="display:none;" />
                                    <input type="text" name="Mod_Cpp_Cod" class="form-control input-xs" style="display:none;" />
                                    <div class="col-xs-5" >
                                        <div class="input-group input-group-xs">                                          
                                            <input type="text" id="Cop_Nns" name="Cop_Nns"  onchange="selectModDoc();" class="form-control secuencia" readonly="readonly" tabindex="13" required="" />
                                            <span class="input-group-addon validate" ><i></i></span>
                                        </div>
                                    </div>       
                                    <label class="col-xs-2 control-label label-xs">Fecha:</label>
                                    <div class="col-xs-3" >
                                        <span name="Mod_Cop_Fec" class="form-control input-xs" /></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs required">Autoriza.:</label>  
                                    <div class="col-xs-10" >
                                        <div class="input-group input-group-xs"> 
                                            <input type="text" id="Cop_Nna" name="Cop_Nna" class="form-control input-xs datatrigger" readonly="readonly" tabindex="14" pattern="\d*" required="" />
                                            <span class="input-group-addon validate" ><i></i></span>
                                        </div>                                      
                                    </div>                                                                          
                                </div>

                                <div id="creditoAnticipo" class="form-group" style="display: none;">

                                    <label class="col-xs-2 control-label label-xs">Saldo:</label>
                                    <div id="saldo" class="col-xs-3" >
                                        <input id="Cop_Saldo" name="Cop_Saldo" class="form-control input-xs" readonly>
                                    </div>
                                    <label class="col-xs-4 control-label label-xs">Anticipo a proveedor:</label>
                                    <div class="col-xs-3" >
                                        <div class="input-group input-group-xs" id="anticipo"> 
                                            <input id="Ant_Prov" name="Ant_Prov" class="form-control input-xs" readonly>
                                            <span class="input-group-addon" title="Se generara un anticipo al proveedor por este valor"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <?php $bodegas = $obBD_con1->getArrayConsulta('bodega.1',array('Suc_Cod'=>$Ses_Suc_Cod,'Usu_Cod'=>$Ses_Usu_Cod), $obBD_conexion);?>

                                        <fieldset class="exa-fieldset" <?php if( count($bodegas)==0) echo 'style="display:none; "'; ?> >
                                            <legend class="Titulos2"></legend>                               
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs">Bodega:</label>  
                                                <div class="col-xs-10" >                                        
                                                    <select name="Bod_Cod" class="form-control input-xs">
                                                        <?php if(count($bodegas)>0) foreach($bodegas as $row){ echo "<option value='$row[Bod_Cod]'>$row[Bod_Nom]</option>"; } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </fieldset> 

                        </div>
                        <div class="col-xs-7">
                            <fieldset class="exa-fieldset" id="docuFormTemp">
                                <legend class="Titulos2">Datos del Documento</legend>
                                <div class="row">
                                <div class="col-xs-12">
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Sustento:</label>  
                                        <div class="col-xs-10" >
                                            <?php $rs_sustento = $obBD_con1->getArrayConsulta(4, '', $obBD_conexion); ?>
                                            <select name="Tri_Cod" class="form-control input-xs" tabindex="3" readonly="" required="">
                                                <option value="">Seleccione...</option>
                                                <?php foreach($rs_sustento as $row){ 
                                                   //echo "<option value='$row[Tri_Cod]' ".($row['Tri_Cod']==2?'selected':'').">$row[Tri_Sri] - $row[Tri_Des]</option>";
                                                    echo "<option value='{$row['Tri_Cod']}' " . ($row['Tri_Cod'] == 2 ? 'selected' : '') . ">" . mb_convert_encoding($row['Tri_Sri'], 'UTF-8', 'ISO-8859-1') . " - " . mb_convert_encoding($row['Tri_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";

                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Documento:</label>  
                                        <div class="col-xs-5" >
                                            <?php $rs_tip_compr = $obBD_con1->getArrayConsulta(5, '', $obBD_conexion); ?>
                                            <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs" tabindex="4" readonly=""  onchange="validaCopNum()" required="" >
                                                <option value="">Seleccione...</option>
                                                <?php foreach($rs_tip_compr as $row){ 
                                                    if($row['Tic_Sri']==4||$row['Tic_Sri']==5)
                                                    echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                                } ?>
                                            </select>
                                        </div> 

                                        <label class="col-xs-2 control-label label-xs required">Emision:</label>  
                                        <div class="col-xs-3">
                                            <div class="input-group">                                          
                                                <input id="Cop_Fec" name="Cop_Fec" type="text" class="form-control input-xs datepickers" tabindex="8" readonly="" required="" value="<?php echo $hoy; ?>" />
                                                <span class="input-group-addon input-xs" title="Fecha de Emisión del Proveedor"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                            </div>
                                        </div>    
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Número:</label>                                     
                                        <div class="col-xs-5" >
                                            <div class="input-group input-group-xs">                                          
                                                <input type="text" id="Cop_Num" name="Cop_Num" onchange="validaCopNum()" class="form-control input-xs secuencia" tabindex="5" readonly="" required="" />
                                                <span class="input-group-addon validate" ><i></i></span>
                                            </div>
                                        </div>

                                        <label class="col-xs-2 control-label label-xs required">Impresión:</label>  
                                        <div class="col-xs-3">
                                            <div class="input-group">
                                                <input id="Cop_Imf" name="Cop_Imf" type="text" class="form-control input-xs datepickers empty" tabindex="9" readonly="" required="" value="<?php echo $hoy; ?>" />
                                                <span class="input-group-addon input-xs" title="Fecha de Creación en Imprenta"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                            </div>
                                        </div>  
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Autoriza.:</label>  
                                        <div class="col-xs-5" >
                                            <div class="input-group input-group-xs"> 
                                                <input id="Cop_Aut" type="text" name="Cop_Aut" class="form-control" tabindex="6" readonly="" required="" maxlength="49" pattern="\d*" />
                                                <span class="input-group-addon validate" ><i></i></span>
                                            </div>
                                        </div> 

                                        <label class="col-xs-2 control-label label-xs required">Caducidad:</label>  
                                        <div class="col-xs-3">
                                            <div class="input-group">
                                                <input id="Cop_Cad" name="Cop_Cad" type="text" class="form-control input-xs datepickers empty" tabindex="10" readonly="" required="" value="<?php echo $hoy; ?>" />
                                                <span class="input-group-addon input-xs" title="Fecha de Caducidad en el SRI"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                            </div>
                                        </div>  
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Ciudad:</label>  
                                        <div class="col-xs-5" >
                                            <?php $rs_ciudad = $obBD_con1->getArrayConsulta(6, '', $obBD_conexion); ?>
                                            <select name="Ciu_Cod" id="Ciu_Cod" class="form-control input-xs" data-placeholder="Seleccione..." tabIndex="7"  readonly="">
                                                <option value=""></option>
                                                <?php foreach($rs_ciudad as $row){ 
                                                    echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                                                } ?>
                                            </select>
                                        </div>
                                        <?php if($configs['Cof_Con']=='S'){ ?>
                                        <label class="col-xs-2 control-label label-xs required">Comprobante:</label>  
                                        <div class="col-xs-3">
                                            <div class="input-group">
                                                <input id="Com_Fec" name="Com_Fec" type="text" class="form-control input-xs datepickers" tabindex="11" readonly="" required="" value="<?php echo $hoy; ?>" />
                                                <span class="input-group-addon input-xs" title="Fecha del Comprobante de Egreso/Diario"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                            </div>
                                        </div> 
                                        <?php } ?>        
                                    </div>
                                </div>
                                </div>
                            </fieldset>
                            <fieldset class="exa-fieldset" id="provFormTemp">
                                <legend class="Titulos2">Forma de Pago</legend>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs required">Forma:</label>  
                                    <div class="col-xs-3" >
                                        <?php $rs_forma = $obBD_con1->getArrayConsulta(21, '', $obBD_conexion); ?>
                                        <select id="For_Cod" name="For_Cod" class="form-control input-xs readOnly" required="" onchange="<?php if($configs['Cof_Con']){ echo 'checkCuentaPago();'; } ?>" required="">
                                            <option value="">Seleccione...</option>
                                            <?php foreach($rs_forma as $row){ 
                                                echo "<option value='$row[For_Cod]' ".($row['For_Des']=='Contado'?"selected=''":'').">$row[For_Des]</option>";
                                            } ?>
                                        </select>
                                    </div> 
                                    <?php if($configs['Cof_Con']=='S'){ ?>
                                    <label class="col-xs-2 control-label label-xs required">Cuenta:</label>  
                                    <div class="col-xs-5">                                    
                                        <select id="Pag_Pld" name="Pag_Pld" class="form-control input-xs readOnly" required=""></select>
                                    </div> 
                                    <?php } ?>
                                </div>
                            </fieldset>    
                        </div>
                    </div>
                </form>    
                <div class="row">                   
                    <div class="col-xs-12" style="min-height: 200px; padding-bottom: 10px;">
                        <table id="documento"></table>
                        <div id="documentoPager"></div>
                    </div> 
                    <div class="col-xs-12">                        
                        <button class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();" ><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                    </div>
                    <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                </div>  
            </div>
            <div id="documentoResult" class="form-horizontal normal" style="display: none;">
                <div class="row">
                    <div class="col-xs-6" id="resultContent">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Resultado De la Transacción</legend>
                            <div>
                                <h4 style="text-align: center; font-weight: 900;">El Documento se guardo con Éxito!</h4>
                                <p class="form-control-static resp" name="Tic_Des"></p>
                                <p class="resp"><span>&raquo;Mes:</span><span style="color:coral;" class="databind" name="Mes"></span></p>
                                <p class="resp"><span>&raquo;Sec:</span><span style="color:teal;" class="databind" name="Cop_Sec"></span></p>
                                <p class="resp"><span>&raquo;Cod:</span><span style="color: #CE0000;" class="databind" name="Cop_Cod"></span></p>
                                <div style="padding-top: 15px; text-align: center;">
                                    <button class="btn btn-sm btn-success" onclick="clearDocument();$('#documentoResult').moveComp('#documentoMain').updateGridsSizes();" ><i class="glyphicon glyphicon-file"></i> Nuevo Documento</button>                                 
                                </div>
                            </div>
                        </fieldset>    
                    </div>
                    <div class="col-xs-6" id="copForm">
                        <fieldset class="exa-fieldset" >
                            <legend class="Titulos2">Datos del Documento</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Documento:</label>  
                                <div class="col-xs-5"><span name="Tic_Des" type="text" class="form-control input-xs "></span></div>                                        
                                <label class="col-xs-1 control-label label-xs">Fecha:</label>  
                                <div class="col-xs-3"><span name="Cop_Fec" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Numero:</label>  
                                <div class="col-xs-4"><span name="Cop_Num" type="text" class="form-control input-xs "></span></div>  
                                <label class="col-xs-2 control-label label-xs">Autorización:</label>  
                                <div class="col-xs-3"><span name="Cop_Aut" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Proveedor:</label>  
                                <div class="col-xs-9"><span name="proveedor" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <table id="copresult"></table>
                        </fieldset>
                        <script>
                            $('#copresult').createGrid({                                                        
                                height:75,postData: {CheListAjax:true},caption:'Detalle Compra <button id="btnCopPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>',
                                rowNum: 10000,
                                colModel: [
                                    { label: 'Cód.Int.', name: 'Cop_Int', key: true, width: 15,align:"center", hidden:true },                                     
                                    { label: 'Cantidad ', name: 'Cop_Can', width: 45, align: 'right' },                      
                                    { label: 'Item', name: 'Ite_Lar', width: 130  },
                                    { label: 'P. Unit.', name: 'Cop_Pru', width: 130, align: 'right'},
                                    { label: 'Importe', name: 'Cop_Imp', width: 65, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"}
                                ],
                                loadComplete: function (){ $(this).setGridSummary(['Debe','Haber'],{Glosa:"<div style='text-align:right;'>TOTALES:</div>"}); }
                            },true); 
                        </script>
                    </div>

                    <div class="col-xs-6" id="retForm">
                        
                    </div>
                    <?php if($configs['Cof_Con']=='S'){ ?>
                    <div class="col-xs-6" id="compForm">
                        <fieldset class="exa-fieldset" >
                            <legend class="Titulos2">Datos del Comprobante</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Cód. Comp.:</label>  
                                <div class="col-xs-3"><span name="Codigo" type="text" class="form-control input-xs "></span></div>                                        
                                <label class="col-xs-3 control-label label-xs">Fecha:</label>  
                                <div class="col-xs-3"><span name="Com_Fec" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Asiento:</label>  
                                <div class="col-xs-4"><span name="Tia_Des" type="text" class="form-control input-xs "></span></div>  
                                <label class="col-xs-5 control-label label-xs">Valor:</label>  
                                <div class="col-xs-3"><span name="Com_Val" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Observación:</label>  
                                <div class="col-xs-9"><span name="Com_Con" type="text" class="form-control input-xs "></span></div>  
                            </div>    
                            <table id="asiento"></table>
                        </fiedset>    
                        <script>
                            $('#asiento').createGrid({                                                        
                                height:75,postData: {CheListAjax:true},caption:'Asiento Contable <button id="btnComPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>',
                                rowNum: 10000, footerrow: true, userDataOnFooter: true,
                                colModel: [
                                    { label: 'Cód.Int.', name: 'Asi_Cod', key: true, width: 15,align:"center", hidden:true },  
                                    { label: 'Tipo', name: 'Asi_Deh', hidden:true },
                                    { label: 'Código', name: 'Pld_Cdc', width: 45 },                      
                                    { label: 'Cuenta', name: 'Pld_Des', width: 130  },
                                    { label: 'Glosa', name: 'Glosa', width: 130},
                                    { label: 'Debe', name: 'Debe', width: 65, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"},                                         
                                    { label: 'Haber',name: 'Haber',width: 65,align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"}
                                ],
                                loadComplete: function (){ $(this).setGridSummary(['Debe','Haber'],{Glosa:"<div style='text-align:right;'>TOTALES:</div>"}); }
                            },true); $.clearFooterDiario("#asiento");
                        </script>
                    </div>
                    <?php } ?>
                </div>    
            </div>
        </div>
    </div>

    <script type="text/javascript">
        var gridFact, index, Cof_Con='<?php echo $configs['Cof_Con']; ?>';
        $(function() { 
            $('#Cop_Nna').createFlyout('El campo debe tener 10, 37 o 49 digitos!',{icon:'exclamation',placement:'right'});
            checkFechaIva('<?php echo $hoy; ?>');
        }); 
    </script>

    <!--INICIO DEL DIALOGO BUSCAR PRODUCTO--> 
    <div id="proDialog" title="B&uacute;squeda de Productos"><form class="form-horizontal normal"><input type="text" name="Cop_Fec" class="Cop_Fec" value="<?php echo $hoy; ?>" style="display: none;" /></form></div>
    <script>
        // DIALOG BUSCAR Producto            
        $.createSearchDialog('proDialog',[
            { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20,align:"center",hidden:false },                                
            { label: 'Descripción', name: 'Ite_Lar', width: 110 },                      
            { label: 'Marca', name: 'Mar_Des', width: 40},            
            { label: 'Categoria', name: 'Cat_Des', width: 90,align:"center" },
            { label: 'IVA', name: 'Iva_Por', width: 20, align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Grava IVA',noMsg:'No Grava IVA'}, title:false },
            { label: 'Adq.', name:'Adq_Cor', width:20, align:"center", formatter:'title',formatoptions:{title:function(o){return o['Adq_Des'];}} },
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectItem, conditional:function(o){ return !(Cof_Con==='S'&&(!$.varValid(o['Pld_Cod'])||o['Pld_Cod']==='')); }, caseFalse:function(){ return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>'; } } }
        ],null,null,null,null,{ title:'Producto', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] });                 
    </script> 
    <!-- FIN DEL DIALOGO PRODUCTO-->  
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="B&uacute;squeda de Proveedor"><form class="form-horizontal normal"> </form></div>
    <!-- Cuadro de dialogo de cambio de cuenta - Nuevo -->
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
    <script>
        // DIALOG BUSCAR proveedor            
        $.createSearchDialog('provDialog',[
            { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15,align:"center",hidden:true },                                
            { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },                      
            { label: 'Proveedor', name: 'proveedor', width: 100},
            { label: 'Cont.', name: 'Prv_Con', width: 20,align:"center", labelLong:'Obligado a Llevar Contabilidad', formatter:'truefalse', formatoptions:{msg:false}  }, 
            { label: 'Espe.', name: 'Prv_Esp', width: 20,align:"center", labelLong:'Contribuyente Especial', formatter:'truefalse', formatoptions:{msg:false} }, 
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectProvee} }
        ],null,null,null,{headertitles:true},{ title:'Proveedor', text:'Prs_Ced' });         
        function selectProvee(provee){
            $('#provFormTemp').setData($.extend(provee,{op_opciones:'c'})).find('.dialogSearch').addClass('x');
            $('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-'+(provee['Prv_Con']==='S'?'ok green':'remove blue'));
            $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-'+(provee['Prv_Esp']==='S'?'ok green':'remove blue'));
            $('#provDialog').dialog('close');
            $('#modiFormTemp').setData({}).find(':input').removeAttr('readonly');
            $('#docuFormTemp').setData({For_Cod:1,Tri_Cod:2,Cop_Fec:'<?php echo $hoy; ?>',Com_Fec:'<?php echo $hoy; ?>'}).find(':input').removeAttr('readonly');
            $('#For_Cod').val(1).removeAttr('disabled');   
            $('#Cop_Fec').trigger('change');
            $('#Ciu_Cod').trigger('chosen:updated');
            $('.validate').find('i').removeAttr('class');
            $('#Pag_Pld').removeAttr('disabled');
        }
        function clearDocument(){
            $('.formDatos:not(.footerFact)').setData({op_opciones:'c',Cal_Inv:'N'});
            $('#docuFormTemp').setData({For_Cod:1,Tri_Cod:2,Cop_Fec:'<?php echo $hoy; ?>',Com_Fec:'<?php echo $hoy; ?>'}).find(':input').attr('readonly');
            $('#modiFormTemp').find(':input').attr('readonly');
            $('#Cop_Fec').trigger('change');
            $('#Ciu_Cod').trigger('chosen:updated');
            $('.validate').find('i').removeAttr('class');
            $('#Cop_Aut').attr('title','');
            gridFact.clearGrid();
            addItem({});
        }
    </script> 
    <div id="provCreateDialog" title="Registrar Proveedor" style="display:none;">
        
        <form class="form-horizontal normal" id="provCreateForm" action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaProvee(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset" >
                <legend class="Titulos2">Datos del Proveedor</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>  
                    <div class="col-xs-5" >
                        <div class="input-group input-group-xs">                                          
                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="if(validaNoIdentif(this.value)['success']){ $('#Ide_Cod').val(this.value.length===10?2:1); $('#Prv_Tic').val(validaNoIdentif(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchProvee(this.value); }else{ $('#Ide_Cod').val(''); $('#Prv_Tic').val(''); $(this).fieldValid(false,validaNoIdentif(this.value)['message']); };" required="" />
                            <span class="input-group-addon validate" ><i></i></span>
                        </div>
                    </div>                     
                    <div class="col-xs-4">
                        <div class="checkbox check-big" style="position:absolute;">
                            <label><input type="checkbox" name="Prv_Esp" value="S" offval="N">Contrib. Especial</label>
                            <label><input type="checkbox" name="Prv_Con" value="S" offval="N">Obligado Contab.</label>
                        </div>
                    </div>
                </div> 
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Documento:</label>  
                    <div class="col-xs-5" >
                        <?php $rs_identi = $obBD_con1->getArrayConsulta(29, '', $obBD_conexion); ?>
                        <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                            <option value=""></option>
                            <?php foreach($rs_identi as $row){ echo "<option value='$row[Ide_Cod]'>$row[Ide_Des]</option>"; } ?>
                        </select>
                    </div>
                </div> 
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Contribuyente:</label>  
                    <div class="col-xs-4" >
                        <select id="Prv_Tic" name="Prv_Tic" class="form-control input-xs" required="" onchange="if(this.value==='N'){ $('.juridico').hide();$('.natural').show(); }else{ $('.natural').hide();$('.juridico').show(); }">
                            <option value = "N" >NATURAL</option>
                            <option value = "J" >JURIDICO</option>
                        </select>
                    </div>
                </div>                
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Razón Social:</span></label>  
                    <div class="col-xs-9" ><input name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs">Nombres:</label>  
                    <div class="col-xs-9" ><input name="Prs_Nom" type="text" class="form-control input-xs" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs required">Genero:</label>  
                    <div class="col-xs-4" >
                        <select name="Prs_Sex" class="form-control input-xs">
                            <option value = "M" >MASCULINO</option>
                            <option value = "F" >FEMENINO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Nomb.Comerc.:</label>  
                    <div class="col-xs-9" ><input name="Prv_Com" type="text" class="form-control input-xs" required="" /></div>
                </div>
            </fieldset>
            <fieldset class="exa-fieldset" >
                <legend class="Titulos2">Datos de Ubicación</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Ciudad:</label>  
                    <div class="col-xs-4" >
                        <select name="Ciu_Cod" class="form-control input-xs" required="" >
                            <option value=""></option>
                            <?php foreach($rs_ciudad as $row){ echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>"; } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Dirección:</label>  
                    <div class="col-xs-9" ><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Teléfono:</label>  
                    <div class="col-xs-4" ><input name="Prs_Tel" type="text" class="form-control input-xs" required="" pattern="\d*" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Mail:</label>  
                    <div class="col-xs-5" ><input name="Prs_Cor" type="mail" class="form-control input-xs" required="" /></div>
                </div>
            </fieldset>
            <div class="center">
                <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            </div>
            <div class="Titulos2"><hr><b>NOTA:</b> Los campos marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
        </form>
        
    </div>
    <!-- FIN DEL DIALOGO PROVEEDOR--> 
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script> 
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
    <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />

    <script type="text/javascript">
        $( "#For_Cod" ).change(function(){  
            var tipoDocumento = $( "#Tic_Cod" ).val();  //4
            var formaPago =  $( "#For_Cod" ).val();     //2
            if(tipoDocumento == 4 && formaPago == 2){
                $("#creditoAnticipo").css({"display": "block"});
		$('#Cop_Saldo').attr('readonly', true);
		$('#Ant_Prov').attr('readonly', true); 
		
            }
            else{
                $("#creditoAnticipo").css({"display": "none"})
            }
        });

        $( "#Tic_Cod" ).change(function(){  
            var tipoDocumento = $( "#Tic_Cod" ).val();  //4
            var formaPago =  $( "#For_Cod" ).val();     //2
            if(tipoDocumento == 4 && formaPago == 2){
                $("#creditoAnticipo").css({"display": "block"});
		$('#Cop_Saldo').attr('readonly', true);
		$('#Ant_Prov').attr('readonly', true); 
            }
            else{
                $("#creditoAnticipo").css({"display": "none"})
            }
        });
    </script>

</BODY>
</HTML>