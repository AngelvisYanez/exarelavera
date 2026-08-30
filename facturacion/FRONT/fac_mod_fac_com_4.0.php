<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factu.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
	
define('tipo_compr', 6); //Tipo de comprobante de la retencion 
$cod_banano=338; //Codigo de Retencion del Banano

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Factu($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Factu;

$hoy = date("Y-m-d");
$mes = date("m");

/* Consulta del tipo de proveedores */
if(isset($provAjax)){  
    $contar = $obBD_con1->getRowConsulta(2,  $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    if($contar['total']>0)
        $responce['rows'] = $obBD_con1->getArrayConsulta(2, $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}
/* ver si exite un proveedor */
if(isset($provAjax2)){  
    $responce['rows'] = $obBD_con1->getArrayConsulta(30, $Prs_Ced.'*'.$Ses_Emp_Cod, $obBD_conexion);
    $responce['total'] = count($responce['rows']);
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}
/* fuarda un nuevo proveedor */
if(isset($guardaProvAjax)){    
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);                  
        if(empty($Prs_Cod)){
            $obBD_con1->operacionobBD(31,$data,$obBD_conexion); 
            $data['Prs_Cod'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
        }
        $obBD_con1->operacionobBD(32,$data,$obBD_conexion); 
        $data['Prv_Cod'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
        $data['proveedor'] = trim($data['Prs_Ape'].' '.$data['Prs_Nom']);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0) {$responce=array(success=>true,prov=>$data);} else {$responce=array(success=>false,message=>'No se pudo realizar la transacci�n!',error=>$obBD_con1->MsgError);}
    utf8_encode_deep($responce); echo json_encode($responce);exit();
}
/* Consulta datos del documento si existe */
if(isset($ajaxCopNum)){
    $resp=array(success=>true);
    if(!empty($Tic_Cod)&&!empty($Cop_Num)){
        $row_rs_CodDoc = $obBD_con1->getRowConsulta(7,$Prv_Cod.'*'.$Tic_Cod.'*'.$Cop_Num.'*'.$Cop_Cod,$obBD_conexion);
        if($row_rs_CodDoc['Cop_Cod']!="")
            $resp=array(success=>false, message=>'El documento ya Existe en el Sistema!');
    }else $resp['success']=''; 
    utf8_encode_deep($resp); echo json_encode($resp); exit();
}
/** Valida liquidaciones **/
if(isset($liquida)){ 
    /* Valida que los Periodos Existan */
    $Pec_Cop = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion);    
    if(empty($Pec_Cop['Pec_Cod'])){ $responce['message']="No Existe Periodo para la Fecha: $Cop_Fec!"; }
    $Pec_Cod=$Pec_Cop['Pec_Cod'];
    $total=$obBD_con1->getRowConsulta(56, $Prv_Cod.'*'.$Tic_Sri.'*'.$Pec_Cop['Pec_Fei'].'*'.$Pec_Cop['Pec_Fef'], $obBD_conexion); // busca total de liquidaciones
    $responce['total'] = $total['total']; 
    $responce['success']=true; echo json_encode($responce); exit();
}

/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(8, $Ses_Emp_Cod,$obBD_conexion);
$vendedor = $obBD_con1->getRowConsulta(10,$Ses_Suc_Cod.'*'.$Ses_Prs_Cod,$obBD_conexion);

/* Consulta del tipo de productos */
if(isset($proAjax)){ 
    if(!empty($Cop_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion); else $Pec_Cop=array(Pla_Cod=>null);
    $contar = $obBD_con1->getRowConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    if($contar['total']>0){
        $responce['rows'] = $obBD_con1->getArrayConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
        if($configs['Cof_Con']=='S'&&!empty($Pec_Cop['Pla_Cod'])){
            foreach ($responce['rows'] AS &$r){
                $cuenta = $obBD_con1->getRowConsulta(16,$Pec_Cop['Pla_Cod'].'*'.$r['Pro_Cod'].'*'.'C', $obBD_conexion);
                if(!empty($cuenta['Pld_Cod'])) $r=array_merge($r,$cuenta);
            }unset($r);
        }
    }
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}
/* Consulta del codigo retencion */
if(isset($codiAjax)){ 
    $data=$_GET;
    if($configs['Cof_Con']=='S'&&!empty($Cop_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion); 
    $contar = $obBD_con1->getRowConsulta(47, $data, $obBD_conexion);	      
    $pagination = pages($contar['total'], $page, $rows);
    $responce=$pagination['data']; $data['limits']=$pagination['limits'];
    if($contar['total']>0){
        $responce['rows'] = $obBD_con1->getArrayConsulta(47, $data, $obBD_conexion);
        if($configs['Cof_Con']=='S'&&!empty($Pec_Cop['Pla_Cod'])){
            foreach ($responce['rows'] AS &$r){
                $cuenta = $obBD_con1->getRowConsulta(60,$Pec_Cop['Pla_Cod'].'*'.$r['Ren_Cod'].'*C', $obBD_conexion);
                if(!empty($cuenta['Pld_Cod'])) $r=array_merge($r,$cuenta);
            }unset($r);
        }
    }
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}

/* busqueda de documentos */
if(isset($searchDocument)){
    $data=$_GET;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(34, $data, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $data['limits']=$pagination['limits'];
    if($contar['total']>0){
        $responce['rows'] = $obBD_con1->getArrayConsulta(34, $data, $obBD_conexion);        
        foreach($responce['rows'] AS &$row){
            $row['Cpp_Edit']='S';$row['Cpp_Min']=0;
            if(!empty($row['Cpp_Cod'])){
                $Pagos1=$obBD_con1->getRowConsulta(57, $row['Cpp_Cod'].'*'.'A', $obBD_conexion);
                if($Pagos1['total']*1>0){ 
                    $row['Cpp_Det']='S'; //tiene pagos activos
                    $Pagos1=$obBD_con1->getRowConsulta(57, $row['Cpp_Cod'].'*'.'A'.'*'.'SUM', $obBD_conexion);
                    $row['Cpp_Min']=round($Pagos1['total']*1, 2);
                }
                $Pagos2=$obBD_con1->getRowConsulta(57, $row['Cpp_Cod'], $obBD_conexion);                
                if($Pagos2['total']*1>0) $row['Cpp_Edit']='N'; //tiene algun pago vinculado
            }else{ // Caja Chica
                $caja=$obBD_con1->getRowConsulta(58, $row['Cop_Cod'], $obBD_conexion);
                if($caja['total']*1>0) $row['Rcc_Det']='S';
                $caja_pend=$obBD_con1->getRowConsulta(58, $row['Cop_Cod'].'*'.'P', $obBD_conexion);
                if($caja_pend['total']*1>0) $row['Rcc_Pen']='S';
            }
            if($configs['Cof_Con']=='S'&&!empty($row['Com_Cod'])){
                $cuentas = $obBD_con1->getRowConsulta( (!empty($row['Cpp_Cod'])?(!empty($row['Rcc_Pen'])?70:37):39), $row['Com_Cod'], $obBD_conexion);
                $row['Pld_Cod_Pag']=$cuentas['Pld_Cod'];
                $otras_comp = $obBD_con1->getRowConsulta(65, $row['Com_Cod'], $obBD_conexion);
                if($otras_comp['total']*1>1) $row['Com_Edit']='N'; 
            }
            
        }unset($row);
    }
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}

/* reviso las cuentas pago */
if(isset($cuentasPago)){     
    $responce['cuentas']='';
    $Pec_Cod = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion);  
    if($For_Cod*1==2)
        $cuentas = $obBD_con1->getArrayConsulta(23, $Pec_Cod['Pla_Cod'].'*'.$For_Cod, $obBD_conexion);
    if($For_Cod*1==1)
        $cuentas = $obBD_con1->getArrayConsulta(22, $Pec_Cod['Pla_Cod'].'*'.$For_Cod, $obBD_conexion);
    if($For_Cod*1==3)
        $cuentas = $obBD_con1->getArrayConsulta(28, $Pec_Cod['Pla_Cod'].'*'.'RC', $obBD_conexion);
        
    $responce['total']=count($cuentas);
    foreach ($cuentas AS $row)
        $responce['cuentas']=$responce['cuentas'].'<option value="'.$row['Pld_Cod'].'" data-extra="'.$row['extra'].'" '.($row['Pld_Cod']==$Pld_Cod?'selected="selected"':'').'>'.$row['Pld_Des'].'</option>';
    if($responce['total']>1)
        $responce['cuentas']="<option value=''>Seleccione...</option>".$responce['cuentas'];
    //if(!empty($Pld_Cod)) $responce['Pld_Cod']=$Pld_Cod;
    $responce['success']=true; echo json_encode($responce); exit();
}
/* reviso los ivas */
if(isset($Check_Iva)){
    $responce['varIvas']=(/*!empty($Tic_Sri)&&('0'.$Tic_Sri)*1==4&&*/$Cop_Fec>'2016-05-31');
    if($responce['varIvas'])
        $responce['ivas']  = $obBD_con1->getArrayConsulta(18, '', $obBD_conexion);
    else
        $responce['ivas']  = $obBD_con1->getArrayConsulta(19, $Cop_Fec, $obBD_conexion); 
    $responce['total']=count($responce['ivas']);
    $responce['options']='';
    foreach ($responce['ivas'] AS $row)
        $responce['options']=$responce['options'].'<option value="'.$row['Iva_Cod'].'" data-ivapor="'.$row['Iva_Por'].'" '.($row['Iva_Cod']==$Iva_Cod?'selected="selected"':'').'>'.$row['Iva_Por'].' %</option>';
    if($configs['Cof_Con']=='S'){
        $responce['cuentas']='';
        $Pec_Cod = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion);  
        $iva_pag = $obBD_con1->getArrayConsulta(20, $Pec_Cod['Pla_Cod'], $obBD_conexion);
        foreach ($iva_pag AS $row)
            $responce['cuentas']=$responce['cuentas'].'<option value="'.$row['Pld_Cod'].'" '.($row['Pld_Cod']==$Pld_Cod?'selected="selected"':'').' >'.$row['Pld_Des'].'</option>';
    }
    $responce['success']=true; echo json_encode($responce); exit();
}


/* Consulta el detalle del documento */
if(isset($docDetalle)){
    $resp=array(success=>true,Cop_Cod=>$Cop_Cod,Cop_Fec=>$Cop_Fec,Ret_Cod=>$Ret_Cod,rows=>array());
    if(!empty($Cop_Cod)){
        $resp['items'] = $obBD_con1->getArrayConsulta(35,$Cop_Cod,$obBD_conexion);
        if(count($resp['items'])==0)
            $resp=array(success=>false, message=>'No se encontraron items en el detalle del documento!');
        else{
            foreach ($resp['items'] as $r) if($r['Iva_Por']*1>0){ $resp['Iva_Cod']=$r['Iva_Cod']; break; }
            if(!empty($Ret_Cod)){  
                $retencion = $obBD_con1->getArrayConsulta(59,$Ret_Cod,$obBD_conexion);            
                foreach ($resp['items'] as &$it){
                    foreach ($retencion as $r) if($it['Cop_Int']==$r['Ret_Int']) foreach ($r as $k=>$v) $it[($r['Ren_Ret']=='R'?'Ret_':'Iva_').$k]=$v;                
                } unset($it);
            }
            if($configs['Cof_Con']=='S'&&!empty($Com_Cod)){
                $iva = $obBD_con1->getRowConsulta(36,$Com_Cod,$obBD_conexion);
                $resp['Pld_Cod']=$iva['Pld_Cod'];            
            } 
        }
    }else $resp['success']=false; 
    utf8_encode_deep($resp); echo json_encode($resp); exit();
}
/* Guardar documento */
if(isset($saveDocument)){
    $responce=array('success'=>false);    
    /* Que sea vendedor */    
    if(empty($vendedor['Vnd_Cod'])){ $responce['message']="No tiene permisos de Vendedor!";  }
    $Vnd_Cod=$vendedor['Vnd_Cod']; $For_Cod=$For_Cod*1;
    /* valida que no exista el documento */
    $row_rs_CodDoc = $obBD_con1->getRowConsulta(7,$Prv_Cod.'*'.$Tic_Cod.'*'.$Cop_Num.'*'.$Cop_Cod,$obBD_conexion);
    if(!empty($row_rs_CodDoc['Cop_Cod'])) { $responce['message']="El doc. $Tic_Des No. $Cop_Num ya existe!"; }
    /* Valida que los Periodos Existan */
    $Pec_Cop = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion);    
    if(empty($Pec_Cop['Pec_Cod'])){ $responce['message']="No Existe Periodo para la Fecha: $Cop_Fec!"; }
    $Pec_Cod=$Pec_Cop['Pec_Cod'];
    $Retencion=(!empty($rets)&&count($rets)>0);
    if($Retencion&&empty($Aut_Cod)){ $responce['message']="No tiene <i>Autorizaci&oacute;n Activa</i> para generar <u>Retenciones</u>!"; }
    $Cop_Des=(!empty($Cop_Des)?$Cop_Des*1:0); 
    $Ret_Num=($Retencion&&(($Ren_Tot)*1>0)?$Ret_Num:0);
    if(/*$configs['Cof_Gce']=='S'*/$Aut_Tem=='E'&&$Retencion&&$Ret_Num!==0){
        $claveAcceso=$obBD_con1->getRetClaveAcceso($Ses_Emp_Cod, $Ses_Suc_Cod, $Aut_Cod, $Ret_Fec, $Ret_Num, $obBD_conexion);
        if(empty($claveAcceso)) $responce['message']="Error al generar <u>Clave de Acceso</u> del <i>Comprobante Electr�nico</i>!";
        if(!$obBD_con1->createUsuCliente($Ses_Emp_Cod, $Ses_Suc_Cod, $Prs_Cod, $Prs_Ced, $obBD_conexion)) $responce['message']='Error al crear usuario de <u>Comprobantes Electr�nicos</u>!'; 
    }
    $rise=($Tic_Sri*1==2||$Tic_Sri*1==9); // rise, nota de venta
    if($rise) $iva_cero=$obBD_con1->getRowConsulta(68,'0',$obBD_conexion);
    /* cierro en caso de error */
    if(!empty($responce['message'])){ echo json_encode($responce);exit(); }    
        
    $obBD_ins1 =  new Class_Log_Datos_Factu;
    $obBD_conexionIns = new Class_Log_Conexion_Factu($Ses_Dat_Dis);  
    $obBD_ins1->inicio_transaccion($obBD_conexionIns->conexion);  
    try{        
        /* Cabecera de la factura de compra */
        $row_cop_old = $obBD_con1->getRowConsulta(40, $Cop_Cod, $obBD_conexion);
        $meseCop = explode('-', $Cop_Fec);
        if(substr($Cop_Fec, 0, 7)!==substr($row_cop_old['Cop_Fec'], 0, 7)){
            $Cop_Sec= $obBD_con1->codigoSecMensualAuto($Pec_Cod, $meseCop[1], $obBD_conexion); // Secuencia de compras por mes
        }else $Cop_Sec=$row_cop_old['Cop_Sec'];
        $obBD_ins1->operacionobBD(11, $Tic_Cod.'*'.$Prv_Cod.'*'.$Ciu_Cod.'*'.trim($Cop_Num).'*'.trim($Cop_Aut).'*'.$Cop_Fec.'*'.$hoy.'*'.trim($Cop_Obs).'*'.$Cop_Cad.'*'.$Cop_Imf.'*'.$Tri_Cod.'*'.$Cop_Des.'*'.$Pec_Cod.'*'.$Tpc_Cod.'*'.$Cop_Ntd.'*'.$Cop_Nns.'*'.$Cop_Nna.'*'.$Vnd_Cod.'*'.$Cop_Sec.'*'.$Cop_Cod, $obBD_conexionIns);  
        
        /* Cabecera de la Retenci�n */
        if($Retencion){    
            $Ret_Fec=(!empty($Ret_Fec)?$Ret_Fec:$Cop_Fec);
            $obBD_ins1->operacionobBD(53, $Cop_Cod.'*'.$Ret_Num.'*'.$Ret_Fec.'*'.trim($Cop_Obs).'*'.tipo_compr.'*'.$Vnd_Cod.'*'.$Aut_Cod.'*'.$claveAcceso.'*'.(!empty($Ret_Asu)?$Ret_Asu:'N').'*'.$Ret_Uca.'*'.$Ret_Pca.'*'.$Ret_Cod, $obBD_conexionIns);
            if(empty($Ret_Cod))
                $Ret_Cod=$obBD_ins1->insercionid($obBD_conexionIns->conexion);                
            else
                $obBD_ins1->operacionobBD(62, $Ret_Cod, $obBD_conexionIns); // Elimina el detalle retencion 
        }else{ if(!empty($Ret_Cod)) $obBD_ins1->operacionobBD(63, $Ret_Cod, $obBD_conexionIns); } // Elimina toda la retencion xq no hay codigos
        
        //arreglo Caja Chica
        $caja_pend=$obBD_con1->getRowConsulta(58, $Cop_Cod.'*'.'P', $obBD_conexion);
        if($For_Cod*1==3){
            if($caja_pend['total']*1==0) $obBD_ins1->operacionobBD(69, $Cop_Cod.'*'.'0'.'*'.'P', $obBD_conexionIns);                   
        }else{
            if($caja_pend['total']*1>0) $obBD_ins1->operacionobBD(71, $Cop_Cod.'*'.'0'.'*'.'P', $obBD_conexionIns);                   
        }
        
        /* Creacion del comprobante contable */
        if($configs['Cof_Con']=='S'){
            $Com_Con = $Cop_Obs; $Iva_Costo=0;
            $Tia_Asi = $obBD_con1->getRowConsulta(13, ($For_Cod!=2?1:2), $obBD_conexion);            
            $meseCom = explode('-', $Com_Fec);
            if(substr($Com_Fec, 0, 7)!==substr($row_cop_old['Com_Fec'], 0, 7)){
                $Com_Num= $obBD_con1->codigoComprAutomatic($Tia_Asi['Tia_Cod'], $Pec_Cod, $meseCom[1], $obBD_conexion); // Secuencia de comprobante por mes y por tipo
            }else $Com_Num=$row_cop_old['Com_Num'];
            $campo='Prv_Cod';
            /* Cabecera del Comprobante */
            $obBD_ins1->operacionobBD(14, $Pec_Cod.'*'.$Prv_Cod.'*'.$Com_Num.'*'.$Com_Fec.'*'.trim($Com_Con).'*'.$Tia_Asi['Tia_Cod'].'*'.$t_rubros.'*'.trim($Cop_Obs).'*'.$campo.'*'.$Com_Cod, $obBD_conexionIns);
            if(empty($Com_Cod)){
                $Com_Cod = $obBD_ins1->insercionid ($obBD_conexionIns->conexion);
                $obBD_ins1->operacionobBD(15, $Com_Cod.'*'.$Cop_Cod, $obBD_conexionIns); // relacion compra comprobante
            }else $obBD_ins1->operacionobBD(41, $Com_Cod, $obBD_conexionIns); // Elimina el asiento anterior
            
            /* CCPP Cuentas por pagar */ //ojo por ahora sigue dependiendo de contabilidad
            if($For_Cod*1==2){                
                $obBD_ins1->operacionobBD(55, $Com_Cod.'*'.$Cop_Cod.'*'.$Cpp_Ven.'*'.trim($Cpp_Obs).'*'.$Cpp_Cod, $obBD_conexionIns);
                if(empty($Cpp_Cod)) $Cpp_Cod = $obBD_ins1->insercionid ($obBD_conexionIns->conexion);
            }else{
                if(!empty($Cpp_Cod)) $obBD_ins1->operacionobBD(64, $Com_Cod.'*'.$Cop_Cod.'*'.$Cpp_Cod, $obBD_conexionIns);
            }
            /* Inserta datos en el detalle del asiento (por items) */
            foreach ($items as &$item){ 
                $addIva=round(($item['Iva_Cos']=='S'&&$item['Iva_Por']*1>0?(($item['Cop_Imp']-($Cop_Des>0?$item['Cop_Imp']*$Cop_Des/100:0))*$item['Iva_Por']/100):0),2);
                $Iva_Costo=$Iva_Costo+$addIva;
                if(empty($item['Pld_Cod'])){
                    $cuenta = $obBD_con1->getRowConsulta(16,$Pec_Cop['Pla_Cod'].'*'.$item['Pro_Cod'].'*'.'C', $obBD_conexion);                 
                    if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del producto: <u>'.$item['Ite_Lar'].'</u>!');
                    $item['Pld_Cod']=$cuenta['Pld_Cod']; $item['Pld_Des']=$cuenta['Pld_Des'];
                }
                $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.'D'.'*'.($item['Cop_Imp']+$addIva).'*'.$item['Pld_Des'].'*'.$item['Ite_Lar'].'*'.$item['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Item                
            } unset($item); 
            /* Inserta datos en el detalle del asiento (por codigo retenci�n) */            
            if($Ret_Asu=='S')  $cuenta_ret_asu = $obBD_con1->getRowConsulta(28, $Pec_Cop['Pla_Cod'].'*'.'RA', $obBD_conexion);            
            if($Retencion&&$Ret_Num>0)
            foreach ($rets as $ret){ 
                $cuenta = $obBD_con1->getRowConsulta(52,$Pec_Cop['Pla_Cod'].'*'.$ret['Ren_Cod'].'*'.'C', $obBD_conexion);                 
                if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del Codigo: <u>'.$ret['Ren_Sri'].'</u>!');                    
                $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.'H'.'*'.$ret['Ren_Val'].'*'.$cuenta['Pld_Des'].'*'.$ret['Ren_Con'].'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // retencion
                if($Ret_Asu=='S'){                     
                    if(!isset($cuenta_ret_asu['Pld_Cod'])&&empty($cuenta_ret_asu['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable de: <u>Retenciones Asumidas</u>!');                    
                    $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.'D'.'*'.$ret['Ren_Val'].'*'.''.'*'.'ASUMIDA '.$ret['Ren_Con'].'*'.$cuenta_ret_asu['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // retencion asumida
                }
            } 
            /* IVA */
            $iva=$t_iva*1-$Iva_Costo;
            if($iva>0){
                if(empty($Iva_Pag))  throw new Exception('Revisar la parametrizacion contable de: <u>Iva Pagado</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('D').'*'.$iva.'*'.'IVA'.'*'.'IVA'.'*'.$Iva_Pag, $obBD_conexionIns);  // inserta asiento // Iva            
            }
            /* DESCUENTO */
            if($Cop_Des>0){
                $cuenta = $obBD_con1->getRowConsulta(28,$Pec_Cop['Pla_Cod'].'*'.'CDS', $obBD_conexion);
                if(!isset($cuenta['Pld_Cod'])&&empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Descuentos en Compras</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('H').'*'.$t_descuento.'*'.'DESCUENTO'.'*'.'DESCUENTO'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // descuento
            }
            if($t_ice*1>0){
                $cuenta = $obBD_con1->getRowConsulta(28,$Pec_Cop['Pla_Cod'].'*'.'ICC', $obBD_conexion);
                if(!isset($cuenta['Pld_Cod'])&&empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>ICE en Compras</u>!');                
                $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('D').'*'.$t_ice.'*'.'ICE'.'*'.'ICE'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE            
            }   
            /* Pago */            
            /* REVISAR VARIOS PAGOS/ANTICIPOS */
            $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('H').'*'.$Val_Pcc.'*'.''.'*'.('Doc.'.$Cop_Num).'*'.$Pag_Pld, $obBD_conexionIns);  // inserta asiento // pago
            /* REVISAR VARIOS PAGOS/ANTICIPOS */            
        }      
        /*/* para eliminar el kardex anterior */        
            if($Tic_Sri*1!=0){
                $row_kard_old = $obBD_con1->getArrayConsulta(43, $Cop_Cod, $obBD_conexion);
                $obBD_Stock =  new Class_Log_Datos_Factu;
                $obBD_conexionStock = new Class_Log_Conexion_Factu($Ses_Dat_Dis); 
                $obBD_Stock->inicio_transaccion($obBD_conexionStock->conexion);  
                foreach ($row_kard_old as $row){ 
                    $row['IoE']='I';
                    $row['Kar_Can']=$row['Kar_Can']*-1;
                    $row['Kar_Prs']=$row['Kar_Prs']*1;
                    $row['Kar_Ims']=$row['Kar_Ims']*-1;
                    $obBD_Stock->updateStock($Ses_Suc_Cod, $row, false, $obBD_conexion,$obBD_conexionStock); //revierte el stock
                }
                $obBD_Stock->fin_transaccion_nomsn($obBD_conexionStock->conexion);
                if($obBD_Stock->Error!=0) throw new Exception('Error al limpiar los antiguos valores del <u>KARDEX</u>!');
                $obBD_ins1->operacionobBD(44, $Cop_Cod, $obBD_conexionIns); // limpia el kardex
            }
        /* Inserta datos en el detalle de la compra */
        $obBD_ins1->operacionobBD(42, $Cop_Cod, $obBD_conexionIns); // Elimina el detalle anterior  
        $kardex=array(IoE=>'I', Kar_Fec=>$hoy, Kar_Hor=>date("H:i:s"), Cop_Cod=>$Cop_Cod, Vnd_Cod=>$Vnd_Cod);
        foreach ($items as $i => $item){             
            $item['Cop_Cod'] = $Cop_Cod;
            $item['Cop_Int'] = $i+1;  
            if($rise) $item['Iva_Cod']=$iva_cero['Iva_Cod'];
            /* Item Documento */
            $obBD_ins1->operacionobBD(12,$item, $obBD_conexionIns);             
            /* Control de Inventarios */
            if($Tic_Sri*1!=0 && $item['Adq_Cor']=='B'){
                $kardexIE=array_merge($kardex, array(
                    Kar_Int=>$i+1, Iva_Cod=>$item['Iva_Cod'], Pro_Cod=>$item['Pro_Cod'],
                    Kar_Can=>(1)*$item['Cop_Can'],
                    Kar_Prs=>$item['Cop_Pru']*1,
                    Kar_Ims=>(1)*$item['Cop_Imp']
                ));                   
                $obBD_ins1->updateStock($Ses_Suc_Cod, $kardexIE, true, $obBD_conexion,$obBD_conexionIns);
            }
            /* Detalle de la retencion */
            if($Retencion){                 
                $des_indivi=($Cop_Des>0?($item['Cop_Imp']*$Cop_Des)/100:0);
                if(!empty($item['Ret_Ren_Cod']))
                    $obBD_ins1->operacionobBD(54, $Ret_Cod.'*'.($item['Cop_Imp']*1-$des_indivi).'*'.$item['Ret_Ren_Cod'].'*'.'R'.'*'.$item['Cop_Int'].'*'.$item['Adq_Cod'], $obBD_conexionIns);
                if(!empty($item['Iva_Ren_Cod'])&&$item['Iva_Por']*1>0)
                    $obBD_ins1->operacionobBD(54, $Ret_Cod.'*'.(($item['Cop_Imp']*1-$des_indivi)*($item['Iva_Por']/100)).'*'.$item['Iva_Ren_Cod'].'*'.'I'.'*'.$item['Cop_Int'].'*'.$item['Adq_Cod'], $obBD_conexionIns);
            }
        }     
    }catch(Exception $e){ mysqli_rollback($obBD_conexionIns->conexion); $responce['message']=$e->getMessage(); echo json_encode($responce); exit(); } 
    $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns->conexion);
    if($obBD_ins1->Error==0){ 
        $responce=array(success=>true,Cop_Cod=>$Cop_Cod,Cop_Sec=>$Cop_Sec,Com_Cod=>$Com_Cod,Ret_Cod=>$Ret_Cod,Tic_Des=>$Tic_Des,Mes=>mes($meseCop[1],1)."/$meseCop[0]");  
        $reportes = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);        
        if(!empty($Cop_Cod)){
            $responce['Cop_Data']=array(Tic_Des=>$Tic_Des,proveedor=>$proveedor,Cop_Num=>$Cop_Num,Cop_Fec=>$Cop_Fec,Cop_Aut=>$Cop_Aut);
            $responce['Cop_Rows']=$obBD_con1->getArrayConsulta(26,$Cop_Cod,$obBD_conexion);            
            $responce['Cop_Link']="".($Tic_Sri*1==3&&!empty($reportes[3])?"$reportes[3]?Cop_Cod=":baseUrl("../../facturacion/FRONT/fac_pri_fac_detallecompras_1.0.php?com_codigo"))."=$Cop_Cod";
            
        }
        if(!empty($Com_Cod)){
            $responce['Com_Data']=array(Com_Con=>$Cop_Obs,Com_Fec=>$Com_Fec,Com_Val=>$t_rubros,Tia_Des=>$Tia_Asi['Tia_Des'],Codigo=>$Tia_Asi['Tia_Abr'].'-'.$meseCom[1].'-'.$Com_Num);
            $responce['Com_Rows']=$obBD_con1->getArrayConsulta(27,$Com_Cod,$obBD_conexion);
            $responce['Com_Link']="".(!empty($reportes[1])?$reportes[1]:baseUrl("../../contabilidad/FRONT/con_pri_compr_1.1.php"))."?codigo=$Com_Cod&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Asi[Tia_Cod]&Pec_Cod=$Pec_Cop[Pec_Cod]";
        }
        if(!empty($Ret_Cod)){
            $responce['Ret_Cod']=$Ret_Cod;
            $responce['Ret_Link']="".($reportes[2])."?Ret_Cod=$Ret_Cod";
            if(/*$configs['Cof_Gce']=='S'*/$Aut_Tem=='E'&&$Ret_Num!==0){         
                $rs_infoEmpresa = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
                $rs_infoCliente = $obBD_con1->getRowConsulta(61, $Aut_Cod, $obBD_conexion);
                $responce['xml']=$obBD_con1->retencionElectronica($Ses_Emp_Cod, $Ses_Suc_Cod, $Prs_Cod, array_merge($rs_infoCliente, array(Ret_Cod=>$Ret_Cod, Ret_Fec=>$Ret_Fec, Ret_Num=>str_pad($Ret_Num, 9, "0", STR_PAD_LEFT))), $obBD_conexion, $Ret_Xml);
                $responce['Ret_Xmls']=baseUrl("../FRONT/".$Ses_Emp_Cod.'/'.$claveAcceso.'.xml');
                // envio del mail
                $meseRet = explode('-', $Ret_Fec);
                $datoElect=array('{Emp_Nom}'=>$Ses_Emp_Nom, '{Tic_Des}'=>$Tic_Des, '{Prs_Ced}'=>$Prs_Ced, '{proveedor}'=>$proveedor, '{Prs_Cor}'=>$Prs_Cor, '{claveAcceso}'=>$claveAcceso, '{fecha}'=>$meseRet[2].' de '.mes($meseRet[1],1).' '.$meseRet[0], '{secuencia}'=>$rs_infoEmpresa["Suc_Sri"].'-'.$rs_infoCliente["Pun_Sri"].'-'.str_pad($Ret_Num, 9, "0", STR_PAD_LEFT));
                $responce['mail']=$obBD_con1->sendMailRet($datoElect, reporteHtml($datoElect,'fac_pri_ret_ele.html'));
            }
        }
    } 
    else{$responce=array(success=>false,message=>"No se ha logrado realizar la Transaccion",error=>$obBD_ins1->MsgError);}  

    echo json_encode($responce);exit();
}
/* Valida numero de retenci�n */	
if(isset($validaRetNum)){    
    $autoriz=$obBD_con1->getRowConsulta(48, $vendedor['Pun_Cod'].'*'.tipo_compr, $obBD_conexion); //Consulta las autorizaciones de las retenciones
    //$rs_infEmpFacElec = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
    $electronica=($autoriz['Aut_Tem']=='E');//($rs_infEmpFacElec['Cof_Gce']=='S');
    $row_max_codig=$obBD_con1->getRowConsulta(51, $Ses_Suc_Cod.'*'.$autoriz['Aut_Sri'].'*'.$autoriz['Aut_Ini'].'*'.$autoriz['Aut_Fin'].'*'.$autoriz['Tic_Cod'], $obBD_conexion); //Consulta el maximo numero de retenciones en base a la autorizacion    
    $Ret_Id_Man = ($row_max_codig['next']);
    if(empty($vendedor['Pun_Cod'])||empty($autoriz['Aut_Cod'])) $resp=array(success=>false, message=>"No tiene autorizacion para generar Retenciones!",Ret_Num_Old=>0,Ret_Num=>'');
    else{    
        $resp=array_merge(array(success=>true,Ret_Num=>$Ret_Id_Man,Ret_Num_Old=>$Ret_Num,Ret_Cod=>$Ret_Cod),$autoriz);
        if(!empty($Ret_Num)){            
            $num_existe_gencod=$obBD_con1->getRowConsulta(50, $Ses_Suc_Cod.'*'.$autoriz['Aut_Sri'].'*'.$Ret_Num.'*'.$Ret_Cod, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
            if($num_existe_gencod['total']*1>0){
                $resp['success']=false; $resp['message']="La Retenci�n N�mero $Ret_Num ya Existe en el Sistema!";
            }
        }else $resp['success']=false;
        $resp['Aut_Sri']=($electronica?'Electronica':$autoriz['Aut_Sri']);
    }
    utf8_encode_deep($resp); echo json_encode($resp); exit();
}
$rs_tip_compr = $obBD_con1->getArrayConsulta(5, '', $obBD_conexion); 
$rs_periodo = $obBD_con1->getArrayConsulta(33, $Ses_Emp_Cod, $obBD_conexion); 
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>    
        <script type="text/ecmascript" src="../VALIDACIONES/fac_val_factu.js?x=49999"></script>
        <style>
            .footrow td[aria-describedby="documento_Cop_Imp"],.footrow td[aria-describedby="documento_Cop_Pru"]{padding: 0 !important;}
            .footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact input[type=text]{ padding: 0; }
            .footerFact textarea{text-align: left; height: 75px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}            
            #Ret_Asu{ vertical-align: middle; margin-top: -2px; padding: 5px;  -ms-transform: scale(1.4); -moz-transform: scale(1.4); -webkit-transform: scale(1.4); -o-transform: scale(1.4); }
            #resultContent .resp{ font-weight: 700; font-size: 30px; color: #3f3fc1; padding: 0; margin: 0; overflow: hidden; text-overflow: ellipsis; height: 32px;}
            #resultContent .resp span:first-child{color:darkgoldenrod;width: 100px;display: inline-block; margin-left: 42px;}
            .ret .input-group-btn button{padding: 1px 2px !important;}
            .ret{ padding: 0 !important;}
        </style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Modificar Documentos de Compras</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body"> 
            <div id="documentoSearch">
                <form id="serachDocDorm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#serachDocDorm','searchDocument');" >
                    <div class="row">
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B�squeda</legend>
                                <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>  
                                <div class="col-xs-10 radioset opt_search">
                                      <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Proveedor&nbsp;&nbsp;&nbsp;</label>
                                      <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;&nbsp;</label>
                                      <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;No. Documento&nbsp;&nbsp;</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label">B&uacute;squeda:</label>  
                                <div class="col-xs-7" >
                                    <div class="input-group">                        
                                    <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b�squeda..." autofocus  class="form-control input-sm clearable submit"/>
                                    <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                  </div><!-- /input-group --> 
                                </div><input type="text" tabindex="-1" style="display:none;" />                    
                            </div>
                            </fieldset>
                        </div>
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtros</legend>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Documento:</label>  
                                    <div class="col-xs-10" >    
                                        <select name="Tic_Cod" class="form-control input-xs">
                                            <!--<option value="">Seleccione...</option>-->
                                           <?php foreach($rs_tip_compr as $row){ 
                                               if($row['Tic_Sri']!=4&&$row['Tic_Sri']!=5&&$row['Tic_Sri']!=7&&$row['Tic_Sri']!=23&&$row['Tic_Sri']!=24)
                                               echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                            } ?>
                                        </select>
                                    </div> 
                                </div> 
                                <div class="form-group">
                                    <label class="col-xs-4 control-label label-xs">Periodo:</label>  
                                    <div class="col-xs-3" >
                                        <select name="Pec_Cod" class="form-control input-xs search_pec" onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled'); ">
                                           <?php foreach($rs_periodo as $row){ echo "<option value='$row[Pec_Cod]'>$row[Periodo]</option>"; } ?>
                                            <option value=""><< TODOS >></option>
                                        </select>
                                    </div> 
                                    <label class="col-xs-2 control-label label-xs">Mes:</label>  
                                    <div class="col-xs-3" >
                                        <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec">
                                           <option value=""><< TODOS >></option>
                                           <?Php  for ($i=1;$i<=12;$i++){ ?><option <?php if ($i == $mes){ echo "selected=''"; } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1); ?></option><?Php } ?>
                                        </select>
                                    </div>                                    
                                </div> 
                            </fieldset>
                        </div>
                    </div>    
                </form> 
                <div style="min-height: 270px;">
                    <table id="searchGrid"></table>
                    <table id="searchGridPager"></table>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop green"></span> Contiene Pagos | <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="fa fa-creative-commons purple"></span> Reposici�n Caja Chica | <span class="fa fa-globe green"></span> Retenci�n Electronica Validada | <span class="glyphicon glyphicon-lock orange"></span> Formato Anterior</div>
                </div>
                <script>
                    function setOpt(val){ if(val==='d') $('.search_pec').attr('disabled','disabled'); else $('.search_pec').removeAttr('disabled'); }
                    function editDocument(doc){        
                        $('.validate').find('i').removeAttr('class');
                        $('#provFormTemp').setData({op_opciones:'c',Cal_Inv:'N'});
                        $('.formDatos').setData(doc,false);
                        $('.Cop_Fec').val(doc['Cop_Fec']);
                        $('#Cop_Num').data('old_num',doc['Cop_Num']);
                        
                        $('#Cop_Des').val(doc['Cop_Des']);
                        $('#Ret_Num').data({Ret_Num:doc['Ret_Num'],Aut_Cod:doc['Aut_Cod'],Aut_Sri:doc['Aut_Sri']}).fieldValid();
                        var edit_pago=doc['Cpp_Edit']!=='N'&&doc['Cpp_Det']!=='S';
                        (edit_pago?$('#For_Cod').removeAttr('disabled'):$('#For_Cod').attr('disabled','disabled'));
                        $('#Pag_Pld').data('disabled',!edit_pago);
                        if(!$.varValid(doc['Ret_Cod'])||doc['Ret_Cod']==='') validaRetNum();
                        $.getDataJson('',{docDetalle:true,Cop_Cod:doc['Cop_Cod'],Com_Cod:doc['Com_Cod'],Cop_Fec:doc['Cop_Fec'],Ret_Cod:doc['Ret_Cod']},function(resp){                            
                            checkFechaIva(resp['Cop_Fec'],resp['Iva_Cod'],resp['Pld_Cod']);                            
                            $('#documento').setRows(resp['items']).startGridEdit();    
                            $.each(resp['items'],function(i,v){ updateRowItem({rowId:v['index']}); } );
                            addItem({}); 
                            $('#t_descuento').val($.toFixed($("#t_subtotal").val()*1*('0'+$('#Cop_Des').val())/100));
                            updateDocument();
                            $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();                            
                        });
                        $('#Ciu_Cod').trigger('chosen:updated'); 
                        var credito=($.varValid(doc['Cpp_Cod'])&&doc['Cpp_Cod']!=='');
                        $('#For_Cod').val(credito?2:$.varValid(doc['Rcc_Pen'])?3:1);
                        $('.pagoCredito')[credito?'show':'hide']();
                        checkCuentaPago(doc['Pld_Cod_Pag']);
                        $('#proForm').formSubmit();
                        selectProvee(doc);                                                 
                        $('#Cop_Imf').datepicker("option","maxDate",doc['Cop_Fec']); $('#Cop_Cad').datepicker("option","minDate",doc['Cop_Fec']); $('#Ret_Fec').datepicker("option","minDate",doc['Cop_Fec']); $('#Cpp_Ven').datepicker("option","minDate",doc['Cop_Fec']);
                        if(Cof_Con==='S') $('#Com_Fec').datepicker("option","minDate",doc['Cop_Fec']);
                        $('#Aut_Cod').html(doc['Aut_Cod']||'');
                        if(!$.varValid(doc['Ret_Num'])||doc['Ret_Num']==='') validaRetNum();
                    }
                    $(function() { 
                        $('#searchGrid').createGrid({
                            caption:'Resultado de la B�squeda',height: 270, datatype: "local",
                            colModel: [  
                                { label: 'C�d. Int.', name: 'Cop_Cod', width: 30 ,align:"center", key:true},  
                                { label: 'Compr.', name: 'Com_Exi', width: 20 ,align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Tiene Comprobante',noMsg:' '}, title:false}, 
                                { label: 'Reten.', name: 'Ret_Exi', width: 20 ,align:"center", formatter:'truefalse', formatoptions:{yesMsg:function(o){ return o.Ret_Num==='0'?'Sin Numero (0%)':'Ret. Num.:\u00A0'+o.Ret_Num; },noMsg:' '}, title:false}, 
                                { label: 'Pago', name: 'Pago', width: 35, align:"center"},
                                { label: 'P. SRI', name: 'Tpc_Sri', width: 20, align:"center", formatter:'title', formatoptions:{title:function(o){return o['Tpc_Des'];}}, title:false },
                                { label: 'Tipo Documento', name: 'Tic_Des', width: 100 },
                                { label: 'No. Documento', name: 'Cop_Num', width: 90, align:"center" },                                
                                { label: 'Fecha', name: 'Cop_Fec', width: 45,align:"center"},
                                { label: 'Proveedor', name: 'proveedor', width: 150},             
                                { label: 'Estado', name: 'Cop_Est', width: 20,align:"center", formatter:'estado', title:false },
                                { label: '&nbsp;', name: 'act0', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{ action:viewInfo, title:'Ver Documento', icon:'info-sign', type:'info' }, title:false },
                                { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'edicion', title:false }
                            ],                                    
                            loadComplete: function(data){   
                                if($.varValid(data.rows))
                                for(var i=0,z=data.rows.length;i<z;i++){                                    
                                    if(data.rows[i]['Cop_Est'] ==='I' || data.rows[i]['Cop_Est'] ==='E') $("#"+data.rows[i].Cop_Cod+' td:not(.jqgrid-rownum)').addClass('cellRed2');
                                    if(data.rows[i]['Ret_Aut'] ==='S' || data.rows[i]['Rcc_Det'] ==='S' )  $("#"+data.rows[i].Cop_Cod+' td:not(.jqgrid-rownum)').addClass('cellBlue2');
                                    if(data.rows[i]['Cpp_Det'] ==='S' || data.rows[i]['Cpp_Edit'] ==='N' ) $("#"+data.rows[i].Cop_Cod+' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                                }
                            }
                        },false,'#searchGridPager',{refresh: true});
                        //$('.formDatos:').find(':input').removeAttr('readonly');
                    });
                    function viewInfo(doc){
                        $('#docDetaDialog').setData(doc); 
                        $('#retViewGrid')[$.varValid(doc['Ret_Cod'])&&doc['Ret_Cod']!==''?'show':'hide']();
                        $.getDataJson('',{docDetalle:true,Cop_Cod:doc['Cop_Cod'],Com_Cod:doc['Com_Cod'],Cop_Fec:doc['Cop_Fec'],Ret_Cod:doc['Ret_Cod']},function(resp){
                            $('#documento').setRows(resp['items']).startGridEdit();
                            addItem({});
                            $('#detaDocu').setRows(resp['items']);
                            $('#detaRete').setRows($('#retencion').getGridBatch());
                            $('#docDetaDialog').dialog('open').updateGridsSizes();
                        });
                    }
                </script>
            </div>  
            <div id="documentoMain" style="visibility: hidden;">
                <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">                
                    <div class="row">
                        <div class="col-xs-5">
                            <fieldset class="exa-fieldset" id="provFormTemp">
                                <legend class="Titulos2">Datos del Proveedor</legend>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">C�dula/RUC:</label>  
                                        <div class="col-xs-6" >
                                          <input name="Prs_Cod" type="text" style="display:none;" />  
                                          <input name="Prs_Cor" type="text" style="display:none;" />  
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
                                        <label class="col-xs-2 control-label label-xs">Direcci�n:</label>  
                                        <div class="col-xs-10" ><span name="Prs_Dir" type="text" class="form-control input-xs datatitle"></span></div>                    
                                    </div>
                            </fieldset>                             
                        </div>
                        <div class="col-xs-7">
                            <fieldset class="exa-fieldset" id="docuFormTemp">
                                <legend class="Titulos2">Datos del Documento</legend>
                                <input type="text" name="Cop_Cod" style="display: none;" />
                                <input type="text" name="Com_Cod" style="display: none;" />
                                <div class="row">
                                <div class="col-xs-12">
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Sustento:</label>  
                                        <div class="col-xs-10" >
                                            <?php $rs_sustento = $obBD_con1->getArrayConsulta(4, '', $obBD_conexion); ?>
                                            <select name="Tri_Cod" class="form-control input-xs" tabindex="3" required="">
                                                <option value="">Seleccione...</option>
                                               <?php foreach($rs_sustento as $row){ 
                                                   echo "<option value='$row[Tri_Cod]' ".($row['Tri_Cod']==2?'selected':'').">$row[Tri_Sri] - $row[Tri_Des]</option>";
                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Documento:</label>  
                                        <div class="col-xs-5" >                                           
                                            <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs" tabindex="4" onchange="validaCopNum()" required=""  data-trigger="" >
                                                <option value="">Seleccione...</option>
                                               <?php foreach($rs_tip_compr as $row){ 
                                                   if($row['Tic_Sri']!=4&&$row['Tic_Sri']!=5&&$row['Tic_Sri']!=7&&$row['Tic_Sri']!=23&&$row['Tic_Sri']!=24)
                                                   echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                                } ?>
                                            </select>
                                        </div> 

                                        <label class="col-xs-2 control-label label-xs required">Emision:</label>  
                                        <div class="col-xs-3">
                                          <div class="input-group">                                          
                                              <input id="Cop_Fec" name="Cop_Fec" type="text" class="form-control input-xs datepickers" tabindex="8" required="" />
                                              <span class="input-group-addon input-xs" title="Fecha de Emisi�n del Proveedor"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                          </div>
                                        </div>    
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">N�mero:</label>                                     
                                        <div class="col-xs-5" >
                                            <div class="input-group input-group-xs">                                          
                                                <input type="text" id="Cop_Num" name="Cop_Num" onchange="validaCopNum()" class="form-control input-xs secuencia" tabindex="5" required="" />
                                                <span class="input-group-addon validate" ><i></i></span>
                                            </div>
                                        </div>

                                        <label class="col-xs-2 control-label label-xs required">Impresi�n:</label>  
                                        <div class="col-xs-3">
                                          <div class="input-group">                                          
                                              <input id="Cop_Imf" name="Cop_Imf" type="text" class="form-control input-xs datepickers empty" tabindex="9" required="" />
                                              <span class="input-group-addon input-xs" title="Fecha de Creaci�n en Imprenta"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                          </div>
                                        </div>  
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Autoriza:</label>  
                                        <div class="col-xs-5" >
                                            <div class="input-group input-group-xs"> 
                                                <input id="Cop_Aut" type="text" name="Cop_Aut" class="form-control datatitle datatrigger" tabindex="6" required="" maxlength="49" pattern="\d*" />
                                                <span class="input-group-addon validate" ><i></i></span>
                                            </div>
                                        </div> 

                                        <label class="col-xs-2 control-label label-xs required">Caducidad:</label>  
                                        <div class="col-xs-3">
                                          <div class="input-group">                                          
                                              <input id="Cop_Cad" name="Cop_Cad" type="text" class="form-control input-xs datepickers empty" tabindex="10" required="" />
                                              <span class="input-group-addon input-xs" title="Fecha de Caducidad en el SRI"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                          </div>
                                        </div>  
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Ciudad:</label>  
                                        <div class="col-xs-5" >
                                            <?php $rs_ciudad = $obBD_con1->getArrayConsulta(6, '', $obBD_conexion); ?>
                                            <select name="Ciu_Cod" id="Ciu_Cod" class="form-control input-xs" data-placeholder="Seleccione..." tabIndex="7" >
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
                                              <input id="Com_Fec" name="Com_Fec" type="text" class="form-control input-xs datepickers" tabindex="11" required="" />
                                              <span class="input-group-addon input-xs" title="Fecha del Comprobante de Egreso/Diario"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                          </div>
                                        </div> 
                                        <?php } ?>        
                                    </div>
                                </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </form>    
                <div class="row">                   
                    <div class="col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
                        <table id="documento"></table>
                        <div id="documentoPager"></div>                        
                    </div> 
                </div>
                <div class="row form-horizontal normal">
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de la Retenci�n</legend>
                            <form id="reteFormTemp" action="javascript:" class="formDatos">
                                <input type="text" name="Ret_Cod" style="display: none;" id="Ret_Cod" />
                                <input type="text" name="Ret_Xml" style="display: none;"  />
                                <input type="text" name="Aut_Cod" style="display: none;" id="Aut_Cod_Old" />                            
                            
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Numero:</label> 
                                <div class="col-xs-4" >
                                    <input type="text" name="Aut_Tem" style="display: none;" />
                                    <div class="input-group input-group-xs">                                          
                                        <input id="Ret_Num" name="Ret_Num" type="text" class="form-control input-xs readOnly ret_field" onchange="validaRetNum()" required="" />
                                        <span class="input-group-addon validate"><i></i></span>
                                    </div>
                                </div>
                                <div class="col-xs-4" >
                                    <?php if($configs['Cof_Con']=='S'){ ?> 
                                    <?php $row_rs_RetPld = $obBD_con1->getArrayConsulta(67, $Ses_Emp_Cod.'*'.'RA',$obBD_conexion); ?>
                                    <div id="asumirRet" style="display:none;" > 
                                        <input type="text" name="Ret_Pld_Cod" value="<?php if(count($row_rs_RetPld)>0) echo $row_rs_RetPld[0]['Pld_Cod']; ?>" style="display: none" />
                                        <input id="Ret_Asu" name="Ret_Asu" type="checkbox" value="S" offval="N" <?php if(count($row_rs_RetPld)===0) echo 'disabled="disabled" title="No se ha parametrizado una cuenta contable."'; ?>><label class="control-label label-xs">&nbsp;&nbsp;Asumir Retenci�n <i class="glyphicon glyphicon-info-sign blue" title="<?php if(count($row_rs_RetPld)===0) echo 'No se ha parametrizado una cuenta contable.'; else echo 'Asumir el Valor de la Retenci�n Contablemente'; ?>"></i></label>
                                    </div>
                                    <?php } ?> 
                                </div>
                                <label class="col-xs-2 control-label label-xs">C�d.Int.:&nbsp;<span id="Aut_Cod" class="blue"></span></label> 
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Autoriza:</label>  
                                <div class="col-xs-4" ><span name="Aut_Sri" class="form-control input-xs databind"></span></div>

                                <label class="col-xs-2 control-label label-xs required">Fecha:</label>  
                                <div class="col-xs-4">
                                  <div class="input-group">                                          
                                      <input id="Ret_Fec" name="Ret_Fec" type="text" class="form-control input-xs readOnly ret_field datepickers"  required=""  pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                                      <span class="input-group-addon input-xs" title="Fecha de la Retenci�n"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                  </div>
                                </div>  
                            </div>
                            <div class="form-group reteTot cod_banano" style="display:none;">
                                <label class="col-xs-2 control-label label-xs required">Banano:</label>  
                                <div class="col-xs-8">
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon bold alert-warning">&nbsp;Cod. 338&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i>&nbsp;</span>
                                        <span class="input-group-addon bold alert-success" title="Cajas de Banano">Cajas:</span>
                                        <input name="Ret_Uca" type="text" class="form-control span" style="text-align: right;" pattern="\d*" placeholder="0" />
                                        <span class="input-group-addon bold alert-success" title="Precio Unitario por Caja">P.Unit.:</span>
                                        <input name="Ret_Pca" type="text" class="form-control span" style="text-align: right;" pattern="\d*" placeholder="0.00" />
                                    </div>    
                                </div>
                            </div> 
                            <div class="form-group reteTot">                                
                                <label class="col-xs-2 control-label label-xs"></label>  
                                <div class="col-xs-10">                                    
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon bold alert-info">Renta:</span>
                                        <input name="Ret_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                                        <span class="input-group-addon bold alert-info">+&nbsp;IVA:</span>
                                        <input name="Iva_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly=""  />
                                        <span class="input-group-addon bold alert-info">=&nbsp;Retenido:</span>
                                        <input id="Ren_Tot" name="Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly=""  />
                                    </div>                                  
                                </div>
                            </div>
                            <div class="form-group reteTot">
                                <label class="col-xs-5 control-label label-xs"></label> 
                                <div class="col-xs-7">                                    
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-addon bold alert-warning">A Pagar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i></span>                                        
                                        <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                                        <input id="Val_Pcc" name="Val_Pcc" type="text" class="form-control bold span" style="text-align: right;font-size: 15px; background-color: white;" readonly="" />
                                        <span id="infoLiquida" class="input-group-addon validate" style="display:none;"><i></i></span>
                                        <span class="input-group-btn">
                                            <button type="button" onclick="$('#retDetaDialog').dialog('open')" class="btn btn-info" title="Ver Detalle Retenci�n" tabindex="-1"><span class="glyphicon glyphicon-eye-open"></span></button>    
                                        </span>
                                    </div>
                                </div>
                            </div> 
                            </form>
                        </fieldset>                        
                    </div>
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Forma de Pago</legend>   
                            <form id="pagoFormTemp" action="javascript:"  class="formDatos">
                            <input type="text" name="Cpp_Cod" style="display: none;" />
                            <div class="form-group pagoSri" style="display: none;">
                                <label class="col-xs-2 control-label label-xs required">Pago&nbsp;SRI:</label>  
                                <div class="col-xs-7"  >
                                    <?php $rs_pag_sri = $obBD_con1->getArrayConsulta(45, '', $obBD_conexion); ?>
                                    <select id="Tpc_Cod" name="Tpc_Cod" class="form-control input-xs readOnly">
                                        <option value="">Seleccione...</option>
                                       <?php foreach($rs_pag_sri as $row){ 
                                           echo "<option value='$row[Tpc_Cod]' >$row[Tpc_Sri] - $row[Tpc_Des]</option>";
                                        } ?>
                                    </select>
                                </div>    
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Forma:</label>  
                                <div class="col-xs-3" >
                                    <?php $rs_forma = $obBD_con1->getArrayConsulta(21, '', $obBD_conexion); ?>
                                    <select id="For_Cod" name="For_Cod" class="form-control input-xs readOnly" data-trigger="" onchange="checkCuentaPago();" required="">
                                        <option value="">Seleccione...</option>
                                       <?php foreach($rs_forma as $row){ 
                                           echo "<option value='$row[For_Cod]' ".($row['For_Des']=='Contado'?"selected=''":'').">$row[For_Des]</option>";
                                        } ?>
                                        <option value="3">Caja Chica</option>
                                    </select>
                                </div>
                                <?php if($configs['Cof_Con']=='S'){ ?>
                                <label class="col-xs-2 control-label label-xs required">Cuenta:</label>  
                                <div class="col-xs-5">                                    
                                    <select id="Pag_Pld" name="Pag_Pld" class="form-control input-xs readOnly" required=""></select>
                                </div> 
                                <?php } ?>    
                            </div>                                                         
                            <div class="form-group pagoCredito" style="display: none;"> 
                                <input type="text" name="Cpp_Min" style="display:none" />
                                <label class="col-xs-2 control-label label-xs required">Vencimiento:</label>  
                                <div class="col-xs-3">                                    
                                    <input id="Cpp_Ven" name="Cpp_Ven" type="text" class="form-control input-xs datepickers" />
                                </div> 
                                <label class="col-xs-2 control-label label-xs">Observaci�n:</label>  
                                <div class="col-xs-5">                                    
                                    <textarea name="Cpp_Obs" class="form-control input-xs"></textarea>
                                </div> 
                            </div>                                
                            </form>    
                        </fieldset>  
                    </div>
                    <div class="col-xs-12">
                        <button class="btn btn-sm btn-inverse" onclick="$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atr�s</button>
                        <button class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();" ><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>                        
                    </div>
                    <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                </div>  
            </div>
            <div id="documentoResult" class="form-horizontal normal" style="visibility: hidden;">
                <div class="row">
                    <div class="col-xs-6" id="resultContent">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Resultado De la Transacci�n</legend>
                            <div>
                                <h4 style="text-align: center; font-weight: 900;">El Documento se guardo con �xito!</h4>
                                <p class="form-control-static resp" name="Tic_Des"></p>
                                <p class="resp"><span>&raquo;Mes:</span><span style="color:coral;" class="databind" name="Mes"></span></p>
                                <p class="resp"><span>&raquo;Sec:</span><span style="color:teal;" class="databind" name="Cop_Sec"></span></p>
                                <p class="resp"><span>&raquo;Cod:</span><span style="color: #CE0000;" class="databind" name="Cop_Cod"></span></p>
                                <div style="padding-top: 15px; text-align: center;">
                                    <button class="btn btn-sm btn-success" onclick="$('#searchGrid').trigger('reloadGrid',[]); clearDocument();$('#documentoResult').moveComp('#documentoSearch').updateGridsSizes();" ><i class="glyphicon glyphicon-arrow-left"></i> Volver</button>
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
                                <label class="col-xs-2 control-label label-xs">Autorizaci�n:</label>  
                                <div class="col-xs-3"><span name="Cop_Aut" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Proveedor:</label>  
                                <div class="col-xs-9"><span name="proveedor" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <table id="copresult"></table>
                        </fieldset>                        
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset" id="retForm">
                            <legend class="Titulos2">Datos de la Retenci�n</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Numero:</label> 
                                <div class="col-xs-4" ><span name="Ret_Num"  class="form-control input-xs" ></span></div>                                
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Autoriza:</label>  
                                <div class="col-xs-4" ><span name="Aut_Sri" class="form-control input-xs"></span></div>

                                <label class="col-xs-2 control-label label-xs">Fecha:</label>  
                                <div class="col-xs-4"><span name="Ret_Fec" class="form-control input-xs" ></span></div>  
                            </div>
                            <div class="form-group reteTot">                                
                                <label class="col-xs-2 control-label label-xs"></label>  
                                <div class="col-xs-10">                                    
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon bold">Renta:</span>
                                        <input name="Ret_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                                        <span class="input-group-addon bold">+&nbsp;IVA:</span>
                                        <input name="Iva_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly=""  />
                                        <span class="input-group-addon bold">=&nbsp;Retenido:</span>
                                        <input name="Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly=""  />
                                    </div>                                  
                                </div>
                            </div>
                            <table id="reteresult"></table> 
                        </fieldset>    
                    </div>
                    <?php if($configs['Cof_Con']=='S'){ ?>
                    <div class="col-xs-6" id="compForm">
                        <fieldset class="exa-fieldset" >
                            <legend class="Titulos2">Datos del Comprobante</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">C�d. Comp.:</label>  
                                <div class="col-xs-3"><span name="Codigo" type="text" class="form-control input-xs "></span></div>                                        
                                <label class="col-xs-3 control-label label-xs">Fecha:</label>  
                                <div class="col-xs-3"><span name="Com_Fec" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Asiento:</label>  
                                <div class="col-xs-4"><span name="Tia_Des" type="text" class="form-control input-xs "></span></div>  
                                <label class="col-xs-2 control-label label-xs">Valor:</label>  
                                <div class="col-xs-3"><span name="Com_Val" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Observaci�n:</label>  
                                <div class="col-xs-9"><span name="Com_Con" type="text" class="form-control input-xs "></span></div>  
                            </div>    
                            <table id="asiento"></table>
                        </fiedset>    
                        <script>
                            $('#asiento').createGrid({                                                        
                                height:75,postData: {CheListAjax:true},caption:'Asiento Contable <button id="btnComPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>',
                                rowNum: 10000, footerrow: true, userDataOnFooter: true,
                                colModel: [
                                    { label: 'C�d.Int.', name: 'Asi_Cod', key: true, width: 15,align:"center", hidden:true },  
                                    { label: 'Tipo', name: 'Asi_Deh', hidden:true },
                                    { label: 'C�digo', name: 'Pld_Cdc', width: 45 },                      
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
        var gridFact, index, Cof_Con='<?php echo $configs['Cof_Con']; ?>', cod_banano=<?php echo $cod_banano; ?>;
        $(function() {    
            $('#documentoMain').css('visibility','').hide();
            $('#documentoResult').css('visibility','').hide();
        }); 
   </script>
   
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTO--> 
    <div id="proDialog" title="B&uacute;squeda de Productos"><form class="form-horizontal normal"><input type="text" name="Cop_Fec" class="Cop_Fec" style="display: none;" /></form></div>
    <script>
        // DIALOG BUSCAR Producto            
        $.createSearchDialog('proDialog',[
            { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20,align:"center",hidden:false },                                
            { label: 'Descripci�n', name: 'Ite_Lar', width: 110 },                      
            { label: 'Marca', name: 'Mar_Des', width: 40},            
            { label: 'Categoria', name: 'Cat_Des', width: 90,align:"center" },
            { label: 'IVA', name: 'Iva_Por', width: 20, align:"center",formatter:'truefalse', formatoptions:{yesMsg:'Grava IVA',noMsg:'No Grava IVA'}, title:false }, 
            { label: 'Adq.', name:'Adq_Cor', width:20, align:"center", formatter:'title',formatoptions:{title:function(o){return o['Adq_Des'];}} },
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectItem, conditional:function(o){ return !(Cof_Con==='S'&&(!$.varValid(o['Pld_Cod'])||o['Pld_Cod']==='')); }, caseFalse:function(){ return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>'; } } }
        ],null,null,null,null,{ title:'Producto', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] });                 
    </script> 
    <!-- FIN DEL DIALOGO PRODUCTO-->  
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="B&uacute;squeda de Proveedor"><form class="form-horizontal normal"> </form></div>
    <script>
        // DIALOG BUSCAR proveedor            
        $.createSearchDialog('provDialog',[
            { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15,align:"center",hidden:true },                                
            { label: 'C�dula/RUC', name: 'Prs_Ced', width: 50 },                      
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
            checkLiquidacion();
            validaCopNum();
        }
        function clearDocument(){
            $('.formDatos').setData({op_opciones:'c',Cal_Inv:'N'});
            $('#docuFormTemp').setData({For_Cod:1,Tri_Cod:2,Cop_Fec:'<?php echo $hoy; ?>',Com_Fec:'<?php echo $hoy; ?>'}).find(':input').attr('readonly');
           
            $('#Cop_Fec').trigger('change');
            $('#Ciu_Cod').trigger('chosen:updated');
            $('.validate').find('i').removeAttr('class');
            gridFact.clearGrid();
            $('#asumirRet').prop('checked',false).hide();
            addItem({});
            validaRetNum();
        }
    </script> 
    <!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="codiDialog" title="B&uacute;squeda de C�digos Retenci�n">  
        <form class="form-horizontal normal"><input type="text" name="Cop_Fec" class="Cop_Fec" style="display: none;" /> 
        <fieldset class="exa-fieldset">
		<legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-xs-7 radioset" >
                          <input id="radc3" name="op_opciones" type="radio" value="p" onclick="setfocus(this.form.search)" alt="" data-trigger="" /><label for="radc3">&nbsp;&nbsp;Porcentaje %&nbsp;&nbsp;</label>  
                          <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                          <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>
                    </div>                   
                    <div class="col-xs-3" style="text-align: right;"> 
                        <input type="text" name="tipo" class="hidden" />
                        <input type="text" name="index" class="hidden" />
                        <div class="checkbox check-big">
                          <label><input name="checkRentaIva" type="checkbox" value="S" offval="N">Aplicar a Todos</label>                         
                        </div>                                            
                    </div>    
                </div>                
        </fieldset>  
       </form> 
    </div> 
    <script>
        $.createSearchDialog('codiDialog',[
            { label: 'C�d.Int.', name: 'Ren_Cod', key: true, width: 25,align:"center" },                                
            { label: 'C�digo', name: 'Ren_Sri', width: 25, align:"center" },                      
            { label: 'Descripci�n', name: 'Ren_Con', width: 100 },
            { label: 'Porc.(%)', name: 'Ren_Por', width: 25,align:"center" },
            { label: 'Adq.', name: 'Ren_Tipo', width: 30,align:"center" },
            { label: 'Tipo', name: 'Ren_Rete', width: 30,align:"center"}, 
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:agregaRetencion, conditional:function(o){ return !(Cof_Con==='S'&&(!$.varValid(o['Pld_Cod'])||o['Pld_Cod']==='')); }, caseFalse:function(){ return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>'; } } }
        ],null,null,null,null,{ title:'B�squeda', options:[] });
    </script>    
    <div id="provCreateDialog" title="Registrar Proveedor" style="display:none;">        
        <form class="form-horizontal normal" id="provCreateForm" action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaProvee(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset" >
                <legend class="Titulos2">Datos del Proveedor</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">C�dula/RUC:</label>  
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
                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Raz�n Social:</span></label>  
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
                <legend class="Titulos2">Datos de Ubicaci�n</legend>
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
                    <label class="col-xs-3 control-label label-xs required">Direcci�n:</label>  
                    <div class="col-xs-9" ><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Tel�fono:</label>  
                    <div class="col-xs-4" ><input name="Prs_Tel" type="text" class="form-control input-xs" required="" pattern="\d*" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Mail:</label>  
                    <div class="col-xs-5" ><input name="Prs_Cor" type="mail" class="form-control input-xs" required="" /></div>
                </div>
            </fieldset>
            <div class="center">
                <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            </div>
            <div class="Titulos2"><hr><b>NOTA:</b> Los campos marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
        </form>
         
    </div>    
    <!-- FIN DEL DIALOGO PROVEEDOR-->       
    <!-- DIALOGO DETALLE RETENCION --> 
    <div id="retDetaDialog" title="Retenci�n"><div class="condensed-header"><table id="retencion"></table></div></div>
    <script>
        $(function() { 
            var opts={                                                        
                height:75,caption:'Detalle Retenci�n', sortable:true, sortname: 'Ren_Rete', sortorder: "desc", footerrow:true,
                colModel: [
                    { label: 'C�d.Int.', name: 'Ren_Cod', key: true, width: 15, align:"center", hidden:true },
                    { label: 'C�d.Int.', name: 'Ren_Ret', width: 15, align:"center", hidden:true },
                    { label: 'Ret.', name: 'Ren_Rete', width: 15, align: 'center' },
                    { label: 'C�digo ', name: 'Ren_Sri', width: 15, align: 'center' },
                    { label: 'Descripci�n ', name: 'Ren_Con', width: 50 },
                    { label: 'Importe', name: 'Ren_Imp', width: 30, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"},
                    { label: 'Porc.(%)', name: 'Ren_Por', width: 20, align: 'right' },
                    { label: 'Retenci�n.', name: 'Ren_Val', width: 30, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"}
                ],
                loadComplete: function (){ $(this).setGridSummary(['Ren_Val'],{Ren_Por:"<div style='text-align:right;'>TOTAL:</div>"}); }
            };
            $('#reteresult').createGrid($.extend(opts,{caption:'Detalle Retenci�n <button id="btnRetPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button><button id="btnRetXml" onclick="window.open($(this).data(\'url\'));" class="btn btn-success btn-xs pull-right" style="margin-top: -2px; display:none; margin-right:2px; "><i class="glyphicon glyphicon-download-alt"></i> Descargar XML</button>'}),true); 
            $('#reteresult').getFootRow(true);
            $('#retencion').createGrid($.extend(opts,{height:219,width:593,responsive:false,caption:'Detalle Retenci�n <button type="button" role="button" tabindex="-1" class="ui-button ui-widget ui-state-default ui-corner-all pull-right" title="Cerrar Ventana" onclick="$(\'#retDetaDialog\').dialog(\'close\')"><span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span></button>'}),true); 
            $('#retencion').getFootRow(true);        
            $('#detaRete').createGrid($.extend(opts,{height:'auto',width:550,responsive:false,caption:null,rownumbers:false}),true); 
            $('#detaRete').getFootRow(true); 
            $('#retDetaDialog').createDialog({height:293,width:600,noTitleStuff:false,noBorder:true,noOverflow:true,extraClass:'noMargin'});
            $('#docDetaDialog').createDialog({height:400,width:600,noTitleStuff:false,noBorder:true});
        });
    </script>
    <!-- DIALOGO DETALLE DOCUMENTO --> 
    <div id="docDetaDialog" title="Documento">
        <fieldset class="exa-fieldset" >
            <legend class="Titulos2">Documento:</legend>
            <div class="form-horizontal normal" style="padding: 0 4px;">
            <div class="form-group">
                <label class="col-xs-2 control-label label-xs">C�dula/RUC:</label>  
                <div class="col-xs-4" ><span name="Prs_Ced"  class="form-control input-xs"></span></div>
                <label class="col-xs-2 control-label label-xs">Doc.Num.:</label>  
                <div class="col-xs-4" ><span name="Cop_Num"  class="form-control input-xs"></span></div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label label-xs">Proveedor:</label>  
                <div class="col-xs-6" ><span name="proveedor"  class="form-control input-xs"></span></div>
                <label class="col-xs-1 control-label label-xs">Fecha:</label>  
                <div class="col-xs-3" ><span name="Cop_Fec"  class="form-control input-xs"></span></div>
            </div>    
            <div class="form-group condensed">
                <div class="col-xs-12"><div class="pull-right"><table id="detaDocu"></table></div></div>
                <div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;"><b>CREACI�N:</b> <span name="Cop_Sys" class="databind"></span> &nbsp;&nbsp;-&nbsp;&nbsp; <b>USUARIO:</b> <span name="vendedor" class="databind"></span></div>
            </div> 
            </div>    
        </fieldset>        
        <fieldset class="exa-fieldset" id="retViewGrid" >
            <legend class="Titulos2">Retenci�n:</legend>
            <div class="form-horizontal normal" style="padding: 0 4px;">
            <div class="form-group">
                <label class="col-xs-2 control-label label-xs">Num.:</label>  
                <div class="col-xs-3" ><span name="Ret_Num"  class="form-control input-xs"></span></div>
                <label class="col-xs-1 control-label label-xs">Fecha:</label>  
                <div class="col-xs-3" ><span name="Ret_Fec"  class="form-control input-xs"></span></div>
                <label class="col-xs-2 control-label label-xs">Autorizaci�n.:</label>  
                <div class="col-xs-1" ><span name="Ret_Aut"  class="form-control input-xs"></span></div>
            </div>     
            <div class="form-group condensed">  
                <div class="col-xs-12"><div class="pull-right"><table id="detaRete"></table></div></div>
            </div> 
            </div>    
        </fieldset>        
    </div>
    <script>
        $(function() { 
            var opts={                                                        
                height:75, postData: {CheListAjax:true},caption:'Detalle Compra <button id="btnCopPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>',                
                colModel: [
                    { label: 'C�d.Int.', name: 'Cop_Int', key: true, width: 15,align:"center", hidden:true },                                     
                    { label: 'Cantidad ', name: 'Cop_Can', width: 45, align: 'right' },                      
                    { label: 'Item', name: 'Ite_Lar', width: 130  },
                    { label: 'P. Unit.', name: 'Cop_Pru', width: 65, align: 'right'},
                    { label: 'Importe', name: 'Cop_Imp', width: 65, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"}
                ]       
            };
            $('#copresult').createGrid(opts,true);
            $('#detaDocu').createGrid($.extend(opts,{height:'auto',width:550,responsive:false,caption:null,rownumbers:false}),true);           
        });
    </script> 
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script> 
    <script>$.clearValidate();</script>
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