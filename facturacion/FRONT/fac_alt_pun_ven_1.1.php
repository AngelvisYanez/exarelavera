<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factu_ven.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

	
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Factu($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Factu;

$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* ver si exite un cliente */
if(isset($searchCliente)){
    $responce = $obBD_con1->getRowConsulta(177, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con1->getRowConsulta(188, $responce['Prs_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Cli_Cod']))?$responce['existe']=true:$responce['existe']=false;
    $obBD_con1->echoJson($responce);
}

if(isset($clieAjax)){  
    $obBD_con1->getPageGridJson(2, $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);  
}
/* ver si exite un cliente */
if(isset($provAjax2)){  
    $responce['rows'] = $obBD_con1->getArrayConsulta(30, $Prs_Ced.'*'.$Ses_Emp_Cod, $obBD_conexion);
    $responce['total'] = count($responce['rows']);
    $obBD_con1->echoJson($responce);
}
/* guarda un nuevo cliente */
if(isset($guardaClieAjax)){    
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion);                  
        if(empty($Prs_Cod)){
            $obBD_con1->operacionobBD(31,$data,$obBD_conexion); 
            $data['Prs_Cod'] = $obBD_con1->insercionid ($obBD_conexion);
        }
        $obBD_con1->operacionobBD(32,$data,$obBD_conexion); 
        $data['Cli_Cod'] = $obBD_con1->insercionid ($obBD_conexion);
        $data['cliente'] = trim($data['Prs_Ape'].' '.$data['Prs_Nom']);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if($obBD_con1->Error==0) {$responce=array('success'=>true,'clie'=>$data);} else {$responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!',error=>$obBD_con1->MsgError);}
    $obBD_con1->echoJson($responce);
}
/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(8, $Ses_Emp_Cod,$obBD_conexion);
$vendedor = $obBD_con1->getRowConsulta(10,$Ses_Suc_Cod.'*'.$Ses_Prs_Cod,$obBD_conexion);
//var_dump($vendedor);
/* Consulta del tipo de productos */
if(isset($proAjax)){
    if(!empty($Vet_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Vet_Fec,$obBD_conexion); else $Pec_Cop=array('Pla_Cod'=>null);
    $responce=$obBD_con1->getPageGrid(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);  
    if($responce['records']>0){                
        foreach ($responce['rows'] AS &$r){
            $r['Precios']=$obBD_con1->getArrayConsulta(733, $Ses_Suc_Cod.'*'.$r['Pro_Cod'].'*'.'A', $obBD_conexion);
            $precio = $obBD_con1->getRowConsulta(733, $Ses_Suc_Cod.'*'.$r['Pro_Cod'].'*'.'A'.'*'.$Tpv_Cod2.'*', $obBD_conexion);
            if(!empty($precio['Pre_Pvp'])){
                $r=array_merge($r,$precio);
                $r['Vet_Pru']=$r['Pre_Pvp'];
            }
            if($configs['Cof_Con']=='S'&&!empty($Pec_Cop['Pla_Cod'])){
                $cuenta = $obBD_con1->getRowConsulta(16,$Pec_Cop['Pla_Cod'].'*'.$r['Pro_Cod'].'*'.'V', $obBD_conexion);
                if(!empty($cuenta['Pld_Cod'])) $r=array_merge($r,$cuenta);
            }
        } unset($r);
    }
    $obBD_con1->echoJson($responce);
}

if(isset($validaVetNum)){        
    //$rs_infEmpFacElec = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
    $electronica=($auttem=='E');
    $row_max_codig=$obBD_con1->getRowConsulta(51, $Ses_Suc_Cod.'*'.$autsri.'*'.$autini.'*'.$autfin.'*'.$ticcod.'*'.$punsri, $obBD_conexion); //Consulta el maximo numero de retenciones en base a la autorizacion    
    $Ret_Id_Man = ($row_max_codig['next']);
    //if(empty($vendedor['Pun_Cod'])||empty($autoriz['Aut_Cod'])) $resp=array('success'=>false, 'message'=>"No tiene autorizacion para generar Retenciones!",'Vet_Num_Old'=>0,'Vet_Num'=>'');
    //else{    
        $resp=array_merge(array('success'=>true,'Vet_Num'=>$Ret_Id_Man,'Vet_Num_Old'=>$Vet_Num,'Vet_Cod'=>isset($Vet_Cod)?$Vet_Cod:''),array('Aut_Cod'=>$autcod,'Aut_Ini'=>$autini,'Aut_Fin'=>$autfin));
        if(!empty($Vet_Num)){            
            $num_existe_gencod=$obBD_con1->getRowConsulta(50, $Ses_Suc_Cod.'*'.$autsri.'*'.$Vet_Num.'*'.(isset($Vet_Cod)?$Vet_Cod:'').'*'.$punsri, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
            if($num_existe_gencod['total']*1>0){
                $resp['success']=false; $resp['message']="El documento número $Vet_Num ya Existe en el Sistema!";
            }
        }else $resp['success']=false;
        //$resp['Aut_Sri']=($electronica?'Electronica':$autoriz['Aut_Sri']);
    //}
    $obBD_con1->echoJson($resp);
}

/* Guardar documento */
if(isset($saveDocument)){
    $responce=array('success'=>false);    
    /* Que sea vendedor */    
    if(empty($vendedor['Vnd_Cod'])){ $responce['message']="No tiene permisos de Vendedor!";  }
    $Vnd_Cod=$vendedor['Vnd_Cod'];
    /* valida que no exista el documento */
    $num_existe_gencod=$obBD_con1->getRowConsulta(50, $Ses_Suc_Cod.'*'.$Aut_Sri.'*'.$Vet_Num.'*'.(isset($Vet_Cod)?$Vet_Cod:'').'*'.$Pun_Sri.'*'.$Tic_Cod, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
    if($num_existe_gencod['total']*1>0) { $responce['message']="El doc. $Tic_Des No. $Vet_Num ya existe!"; }
    /* Valida que los Periodos Existan */
    $Pec_Cop = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Vet_Fec,$obBD_conexion);    
    if(empty($Pec_Cop['Pec_Cod'])){ $responce['message']="No Existe Periodo para la Fecha: $Vet_Fec!"; }
    $Pec_Cod=$Pec_Cop['Pec_Cod'];
    
    $Vet_Des=(!empty($Vet_Des)?$Vet_Des*1:0);   
    
    if($Aut_Tem=='E'&&$Vet_Num!==0){ $Vet_Aut='N';
        if($num_existe_gencod['total']*1>0){ 
            $responce['message']=NULL;  
            $row_max_codig=$obBD_con1->getRowConsulta(51, $Ses_Suc_Cod.'*'.$Aut_Sri.'*'.$autini.'*'.$autfin.'*'.$Tic_Cod, $obBD_conexion); //Consulta el maximo numero de retenciones en base a la autorizacion    
            $Vet_Num = ($row_max_codig['next']);
        }
        $claveAcceso=$obBD_con1->getDocClaveAcceso($Ses_Emp_Cod, $Ses_Suc_Cod, $Tic_Sri, $Aut_Cod, $Vet_Fec, $Vet_Num, $obBD_conexion); 
        if(empty($claveAcceso)) $responce['message']="Error al generar <u>Clave de Acceso</u> del <i>Comprobante Electrónico</i>!";
        if(!$obBD_con1->createUsuCliente($Ses_Emp_Cod, $Ses_Suc_Cod, $Prs_Cod, $Prs_Ced, $obBD_conexion)) $responce['message']='Error al crear usuario de <u>Comprobantes Electrónicos</u>!'; 
    }
    $rise=($Tic_Sri*1==2||$Tic_Sri*1==9); // rise, nota de venta
    if($rise) $iva_cero=$obBD_con1->getRowConsulta(68,'0',$obBD_conexion);
    /* cierro en caso de error */
    if(!empty($responce['message'])){ echo json_encode($responce);exit(); }
        
    $obBD_ins1 =  new Class_Log_Datos_Factu;
    $obBD_conexionIns = new Class_Log_Conexion_Factu($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);  
    try{
        /* Cabecera de la factura de venta */       
        $obBD_ins1->operacionobBD(11, $Tic_Cod.'*'.$Cli_Cod.'*'.$Ciu_Cod.'*'.$Caj_Cod.'*'.$Vnd_Cod.'*'.	
		$Vet_Num.'*'.$Vet_Obs.'*'.$Aut_Cod.'*'.$Vet_Des.'*'.$hora.'*'.(isset($claveAcceso)?$claveAcceso:'').'*'.(isset($Vet_Aut)?$Vet_Aut:'').'*'.(isset($Num_Ret)?$Num_Ret:'').'*'.(isset($Ret_Fec)?$Ret_Fec:'').'*'.(isset($Num_Aut)?$Num_Aut:'').'*'.$Tpc_Cod, $obBD_conexionIns);
        $Vet_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
        
        /* REGISTRO PAGO VENTA */
        foreach ($pagos as $i=>&$pag){
            $pag['Vet_Num']=$i; 
            $pag['Vet_Cod']=$Vet_Cod;
            $obBD_ins1->operacionobBD(72, $pag, $obBD_conexionIns);  // inserta pago_venta

            //INSETAR EN LA TABLA CHEQUES_EXT if pag_cod = 3  BAK_COD - VET_CUE - VET_CHE - VET_TOT - CPC_VET(FECHA) $CLIENTE  $CLI_COD
            if($pag['Pag_Cod'] == '3'){
                $pag['Cliente'] = $cliente;
                $pag['Cli_Cod'] = $Cli_Cod;
                $obBD_ins1->operacionobBD(80, $pag, $obBD_conexionIns);
                $Che_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
                $obBD_ins1->operacionobBD(146, array('Vet_Cod'=>$Vet_Cod,'Che_Cod'=>$Che_Cod), $obBD_conexionIns);
            }

        } unset($pag);
        
        /* Creacion del comprobante contable */
        if($configs['Cof_Con']=='S'&&$Tic_Sri*1!=0){
            $Com_Con = 'REG. VENTA '.$Vet_Num; $Com_Fec=$Vet_Fec;
            $Tia_Asi = 7;  //$obBD_con1->getRowConsulta(13, $For_Cod, $obBD_conexion);            
            $meseCom = explode('-', $Com_Fec);
            $Com_Num= $obBD_con1->codigoComprAutomatic($Tia_Asi, $Pec_Cod, $meseCom[1], $obBD_conexion); // Secuencia de comprobante por mes y por tipo
            $campo='Cli_Cod';
            /* Cabecera del Comprobante */
            $obBD_ins1->operacionobBD(14, $Pec_Cod.'*'.$Cli_Cod.'*'.$Com_Num.'*'.$Com_Fec.'*'.trim($Com_Con).'*'.$Tia_Asi.'*'.$t_rubros.'*'.trim($Vet_Obs).'*'.$campo, $obBD_conexionIns);
            $Com_Cod = $obBD_ins1->insercionid ($obBD_conexionIns);
            $obBD_ins1->operacionobBD(15, $Com_Cod.'*'.$Vet_Cod, $obBD_conexionIns); // relacion venta comprobante
            
            /* Inserta datos en el detalle del asiento (por items) */
            foreach ($items as &$item){ 
                $cuenta = $obBD_con1->getRowConsulta(16,$Pec_Cop['Pla_Cod'].'*'.$item['Pro_Cod'].'*'.'V', $obBD_conexion);                 
                if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del producto: <u>'.$item['Ite_Lar'].'</u>!');
                $item['Pld_Cod']=$cuenta['Pld_Cod'];
                $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.'H'.'*'.($item['Vet_Imp']).'*'.$cuenta['Pld_Des'].'*'.$item['Ite_Lar'].'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Item                
            } unset($item); 
            /* IVA */
            if($t_iva*1>0){
                $cuenta = $obBD_con1->getRowConsulta(20,$Pec_Cop['Pla_Cod'], $obBD_conexion);
                if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Iva Cobrado</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('H').'*'.$t_iva.'*'.'IVA'.'*'.'IVA'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Iva            
            }
            /* DESCUENTO */
            if($Vet_Des>0){
                $cuenta = $obBD_con1->getRowConsulta(28,$Pec_Cop['Pla_Cod'].'*'.'DV', $obBD_conexion);
                if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Descuentos en ventas</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('D').'*'.$t_descuento.'*'.'DESCUENTO'.'*'.'DESCUENTO'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // descuento
            }
            /* Pago */            
            /* REVISAR VARIOS PAGOS/ANTICIPOS */
            foreach ($pagos as $pag){
                $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('D').'*'.$pag['Vet_Tot'].'*'.$pag['Vet_Num'].'*'.('Doc.'.$Vet_Num).'*'.$pag['Pag_Pld'], $obBD_conexionIns);  // inserta asiento // Iva
                /* CCPP Cuentas por pagar */ //ojo por ahora sigue dependiendo de contabilidad
                if($pag['For_Cod']*1==2){
                    $obBD_ins1->operacionobBD(55, $Com_Cod.'*'.$Vet_Cod.'*'.$pag['Cpc_Ven'].'*'.trim($pag['Cpc_Obs']), $obBD_conexionIns);
                    $Cpc_Cod = $obBD_ins1->insercionid ($obBD_conexionIns);
                }
            }
            
            /* REVISAR VARIOS PAGOS/ANTICIPOS */            
        }        
        /* Inserta datos en el detalle de la venta */
        $kardex=array('IoE'=>'E', 'Kar_Fec'=>$hoy, 'Kar_Hor'=>date("H:i:s"), 'Vet_Cod'=>$Vet_Cod, 'Vnd_Cod'=>$Vnd_Cod);
        $array_kardex=array();
        foreach ($items as $i => $item){             
            $item['Vet_Cod'] = $Vet_Cod;
            $item['Vet_Ite'] = $i+1;     
            if($rise) $item['Iva_Cod']=$iva_cero['Iva_Cod'];
            /* Item Documento */
            $obBD_ins1->operacionobBD(12,$item, $obBD_conexionIns);             
            /* Control de Inventarios */
            if(($Tic_Sri*1!=0 || (isset($configs['Cof_Stk']) && $configs['Cof_Stk']=='S')) && $item['Adq_Cor']=='B'){
                $s_add=true;
                foreach($array_kardex AS &$k){
                    if($item['Pro_Cod']==$k['Pro_Cod']){
                        $k['Kar_Sal']+=(1)*$item['Vet_Can'];
                        $k['Kar_Ime']+=(1)*$item['Vet_Imp'];
                        $k['Kar_Pre']=$k['Kar_Ime']/$k['Kar_Sal'];
                        $s_add=false; break;
                    }
                } unset($k);
                if($s_add){
                    $kardexIE=array_merge($kardex, array(
                        'Pro_Cod'=>$item['Pro_Cod'],
                        'Iva_Cod'=>$item['Iva_Cod'], 
                        'Kar_Sal'=>(1)*$item['Vet_Can'],
                        'Kar_Pre'=>$item['Vet_Pru']*1,
                        'Kar_Ime'=>(1)*$item['Vet_Imp']
                    )); array_push($array_kardex, $kardexIE);
                }
            }            
        }
        /* registro de kardex y stocks */
        foreach($array_kardex AS $i =>$k){
            $k['Kar_Int']=$i+1;
            $obBD_ins1->updateStockProd($Ses_Suc_Cod, $k, true, $obBD_conexion,$obBD_conexionIns,$Bod_Cod);   
        } 
    }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $responce['message']=$e->getMessage(); echo json_encode($responce); exit(); }
        
    $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns->conexion);    
    if($obBD_ins1->Error==0){ 
        $responce=array('success'=>true, 'Vet_Cod'=>$Vet_Cod, 'Com_Cod'=>$Com_Cod, 'Tic_Des'=>$Tic_Des); 
        $reportes = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);        
        if(!empty($Vet_Cod)){           
            $responce['Vet_Link']="".(!empty($reportes[1])?"$reportes[1]?Vet_Cod":baseUrl("../../facturacion/chompipa/fac_pri_fac_chompipa_1.2.php?Vet_Cod"))."=$Vet_Cod";
            $responce['Vet_Link_Min']=baseUrl("../../facturacion/chompipa/fac_pri_fac_chompipa_detalle_1.2.php?Vet_Cod")."=$Vet_Cod";
            /* dcumento electronico */
            if($Aut_Tem=='E'){         
                $rs_infoEmpresa = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
                $rs_infoCliente = $obBD_con1->getRowConsulta(61, $Aut_Cod, $obBD_conexion);
                $responce['xml']=$obBD_con1->documentoElectronico(5, $Ses_Emp_Cod, $Ses_Suc_Cod, $Tic_Sri, array_merge($rs_infoCliente, array('Vet_Cod'=>$Vet_Cod, 'Vet_Fec'=>$Vet_Fec, 'Vet_Num'=>str_pad($Vet_Num, 9, "0", STR_PAD_LEFT))), $obBD_conexion);
                $responce['Vet_Xmls']=baseUrl("../FRONT/".$Ses_Emp_Cod.'/'.$claveAcceso.'.xml');
                // envio del mail
                $meseVet = explode('-', $Vet_Fec);
                $datoElect=array('{Emp_Nom}'=>$Ses_Emp_Nom, '{Tic_Des}'=>$Tic_Des, '{Prs_Ced}'=>$Prs_Ced, '{proveedor}'=>$cliente, '{Prs_Cor}'=>$Prs_Cor, '{claveAcceso}'=>$claveAcceso, '{fecha}'=>$meseVet[2].' de '.mes($meseVet[1],1).' '.$meseVet[0], '{secuencia}'=>$rs_infoEmpresa["Suc_Sri"].'-'.$rs_infoCliente["Pun_Sri"].'-'.str_pad($Vet_Num, 9, "0", STR_PAD_LEFT));
                $responce['mail']=$obBD_con1->sendMailDoc($datoElect, reporteHtml($datoElect,'fac_pri_ret_ele.html'));
            }
  
        }
    } 
    else{$responce=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_ins1->MsgError);} 
    $obBD_con1->echoJson($responce);
}

/* Informacion facturacion */
$ciudad = $obBD_con1->getRowConsulta(6,$Ses_Suc_Cod,$obBD_conexion);
$cons_final = $obBD_con1->getRowConsulta(2,'9999999999999'.'*'.$Ses_Emp_Cod.'*'.'c'.'*'.'LIMIT 1',$obBD_conexion);
$precios = $obBD_con1->getArrayConsulta(74,$Ses_Suc_Cod, $obBD_conexion);
$caja = $obBD_con1->getRowConsulta(75,$vendedor['Pun_Cod'].'*'.'A',$obBD_conexion);
if(!empty($caja['Caj_Fec'])&&empty($Vet_Fec)){
    $Vet_Fec=$caja['Caj_Fec'];
    $Cpc_Ven=date('Y-m-d', strtotime($Vet_Fec. ' + 15 days'));
}
$varIvas=true;//(/*!empty($Tic_Sri)&&('0'.$Tic_Sri)*1==4&&*/$Vet_Fec>'2016-05-31');
//$ivas = $obBD_con1->getArrayConsulta($varIvas?18:19,$Vet_Fec, $obBD_conexion);
$iva_act = $obBD_con1->getRowConsulta(19,$Vet_Fec, $obBD_conexion);
$ivas = $obBD_con1->getArrayConsulta(18,$Vet_Fec, $obBD_conexion);
$rs_tip_compr = $obBD_con1->getArrayConsulta(5, $vendedor['Pun_Cod'].'*'.$Vet_Fec, $obBD_conexion);
$Pec_Cop = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Vet_Fec,$obBD_conexion);    
 
if(empty($Pec_Cop['Pec_Cod'])) $error="No existe un periodo activo para la fecha $Vet_Fec."; 
if(count($rs_tip_compr)==0) $error="No tiene <u>autorización</u> vigente para ningun tipo de documento, registar autorizaciones y recargar la página."; 
if(empty($caja['Caj_Cod'])) $error="No existe una <u>caja</u> activa, aperture caja y vuelva a recargar la página.";  
if(empty($vendedor['Vnd_Cod'])) $error="No tiene permisos de <u>vendedor</u>, contacte al administrador en caso de ser un error.";  

?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Punto de Ventas"; ?></TITLE>
        <meta charset="UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>    
        <!--<script type="text/ecmascript" src="../VALIDACIONES/fac_val_factu.js?x=500"></script>-->
        <style>
            .footrow td[aria-describedby="documento_Vet_Imp"],.footrow td[aria-describedby="documento_Vet_Pru"]{padding: 0 !important;}
            .footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact input[type=text]{ padding: 0; }
            .footerFact textarea{text-align: left; height: 75px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}                        
            /*#resultContent .resp{ font-weight: 700; font-size: 30px; color: #3f3fc1; padding: 0; margin: 0; overflow: hidden; text-overflow: ellipsis; height: 32px;}
            #resultContent .resp span:first-child{color:darkgoldenrod;width: 100px;display: inline-block; margin-left: 42px;}     */
        </style>
        <script> var cons_final=<?php if(!empty($cons_final)) echo json_encode($cons_final); else echo '{}'; ?>; </script>
    </HEAD>
<BODY>    
    <?php if(empty($error)){ ?>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title center">
                <span class="pull-left">&raquo; Registrar Documentos de Venta</span>
                &raquo; <b>PUNTO IMPRESIÓN:</b> <?php echo $vendedor['Pun_Des']; ?>
                <span class="pull-right">&raquo; <b>CAJA:</b> <?php echo $caja['Caj_Fec']; ?></span>
            </h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body"> 
            <div id="documentoMain"> 
                <div class="row">                   
                    <div class="col-xs-3">
                        <div id="accordion">
                            <h3>Valores Default</h3>
                            <div id="def_form" class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Descrip:</label>  
                                    <div class="col-xs-9">
                                        <select id="Def_Desc" name="Def_Desc" class="form-control input-xs" onchange="setLocalStore('Def_Desc',$(this).val());">
                                            <option value="" selected="">(Ninguna)</option>
                                            <option value="Docu " selected="">Docu #</option>
                                            <option value="Mesa ">Mesa #</option>
                                        </select>
                                    </div>                                                                                
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Docum.:</label>  
                                    <div class="col-xs-9" >                                          
                                        <select id="Def_Tic_Cod" name="Def_Tic_Cod"  class="form-control input-xs" onchange="setLocalStore('Def_Tic_Cod',$(this).val());">
                                            <?php if(count($rs_tip_compr)>1) echo '<option value="">Seleccione...</option>'; ?> 
                                            <?php foreach($rs_tip_compr as $row){ 
                                               if($row['Tic_Sri']!=4&&$row['Tic_Sri']!=5&&$row['Tic_Sri']!=7&&$row['Tic_Sri']!=23&&$row['Tic_Sri']!=24)
                                               echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                            } ?>
                                        </select>
                                    </div>   
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">I.V.A.:</label>  
                                    <div class="col-xs-6" >                                        
                                        <select id="Def_Ivas" name="Def_Ivas" class="form-control input-xs" onchange="setLocalStore('Def_Ivas',$(this).val());">
                                            <?php 
                                                foreach ($ivas AS $row) echo '<option value="'.$row['Iva_Cod'].'" data-ivapor="'.$row['Iva_Por'].'" '.($iva_act['Iva_Por']==$row['Iva_Por']?'selected="selected"':'').'>'.$row['Iva_Por'].' %</option>';
                                            ?>
                                        </select>
                                    </div>   
                                </div>
                                <?php $Pec_Cod = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Vet_Fec,$obBD_conexion);  ?>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Contado:</label>  
                                    <div class="col-xs-9" >                                        
                                        <select id="Pag_Pld_1" name="Pag_Pld_1" class="form-control input-xs datatrigger" onchange="setLocalStore('Def_Contado',$(this).val());">
                                            <?php 
                                                $cuentas_1 = $obBD_con1->getArrayConsulta(22, $Pec_Cod['Pla_Cod'].'*'.'1', $obBD_conexion);
                                                if(count($cuentas_1)>1) echo "<option value='' selected=''>Seleccione...</option>";
                                                foreach ($cuentas_1 AS $row) echo '<option value="'.$row['Pld_Cod'].'" data-extra="'.(isset($row['extra'])?$row['extra']:'').'" '.((isset($Pld_Cod)&&$row['Pld_Cod']==$Pld_Cod)||count($cuentas_1)==1?'selected="selected"':'').'>'.$row['Pld_Des'].'</option>';
                                            ?>
                                        </select>
                                    </div>   
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Crédito:</label>  
                                    <div class="col-xs-9" >                                        
                                        <select id="Pag_Pld_2" name="Pag_Pld_2" class="form-control input-xs datatrigger" onchange="setLocalStore('Def_Credito',$(this).val());">
                                            <?php 
                                                $cuentas_2 = $obBD_con1->getArrayConsulta(23, $Pec_Cod['Pla_Cod'].'*'.'2', $obBD_conexion);
                                                if(count($cuentas_2)>1) echo "<option value=''>Seleccione...</option>";
                                                foreach ($cuentas_2 AS $row) echo '<option value="'.$row['Pld_Cod'].'" data-extra="'.$row['extra'].'" '.((isset($Pld_Cod)&&$row['Pld_Cod']==$Pld_Cod)||count($cuentas_1)==1?'selected="selected"':'').'>'.$row['Pld_Des'].'</option>';
                                            ?>
                                        </select>
                                    </div>   
                                </div>
                            </div>
                        </div>  
                        <div style="min-height: 200px; padding: 5px 0;">
                            <table id="documentos"></table>
                            <div id="documentosPager"></div> 
                        </div>    
                    </div>
                    <div class="col-xs-9" id="panelVentas" >
                        <div id="Doc_Title" class="ui-widget-header ui-corner-top" style="padding: 1px 10px; font-size: 20px;">&nbsp;</div>
                        <div class="row">                            
                            <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">    
                                <div class="col-xs-5">
                                    <fieldset class="exa-fieldset" id="clieFormTemp">
                                        <legend class="Titulos2">Datos del Cliente</legend>
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs">Cédula/RUC:</label>  
                                            <div class="col-xs-8" >
                                              <input name="Prs_Cod" type="text" style="display:none;" />  
                                              <input name="Prs_Cor" type="text" style="display:none;" />  
                                              <input name="Cli_Cod" type="text" style="display:none;" />
                                              <input name="op_opciones" type="text" value="c" style="display: none;">  
                                              <div class="input-group input-group-xs">                                          
                                                <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#clieDialog',selectCliente);" type="text" placeholder="Ingrese Cliente..."  class="form-control input-xs datatrigger clearable dialogSearch" tabindex="1" />
                                                <span class="input-group-btn">
                                                    <button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                     <button type="button" onclick="$('#clieCreateForm').setData({}).find('.validate').find('i').removeAttr('class'); $('#clieCreateDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Cliente"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>   
                                                </span>
                                              </div>
                                            </div>                                        
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs required">Cliente:</label>  
                                            <div class="col-xs-8" ><span name="cliente" class="form-control input-xs databind datatitle"></span></div>                                                                                
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs">Dirección:</label>  
                                            <div class="col-xs-8" >
                                                <div class="input-group input-group-xs">                                                    
                                                    <span name="Prs_Dir" type="text" class="form-control input-xs databind datatitle"></span>
                                                    <span class="input-group-addon" title="Sin E-Mail">@</span>
                                                    <span name="Prs_Cor" type="text" class="form-control input-xs databind datatitle"></span>
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
                                        <input type="text" name="Vet_Cod" style="display: none;" />
                                        <input type="text" name="Com_Cod" style="display: none;" />
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Fecha:</label>  
                                            <div class="col-xs-4" ><span name="Vet_Fec" class="form-control input-xs databind datatitle"></span></div> 
                                            <label class="col-xs-2 control-label label-xs">Ciudad:</label> 
                                            <input type="hidden" name="Ciu_Cod" class="form-control" />
                                            <div class="col-xs-4" ><span name="Ciu_Des" class="form-control input-xs databind datatitle"></span></div> 
                                        </div> 
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Docum.:</label>  
                                            <div class="col-xs-10" >  
                                                <input type="text" id="Aut_Tem" name="Aut_Tem" style="display: none;" />
                                                <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs datatrigger" tabindex="4" onchange="punNum(); updateDocument(); backupHeader();" required="" >
                                                   <?php if(count($rs_tip_compr)>1) echo '<option value="">Seleccione...</option>'; ?>
                                                   <?php foreach($rs_tip_compr as $row){ 
                                                       if($row['Tic_Sri']!=4&&$row['Tic_Sri']!=5&&$row['Tic_Sri']!=7&&$row['Tic_Sri']!=23&&$row['Tic_Sri']!=24)
                                                       echo "<option value='$row[Tic_Cod]' data-ticcod='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]' data-puncod='$row[Pun_Cod]'  data-autcod='$row[Aut_Cod]'  data-autsri='$row[Aut_Sri]' data-auttem='$row[Aut_Tem]' data-autima='$row[Aut_Ima]' data-punsri='$row[Pun_Sri]' data-sucsri='$row[Suc_Sri]' data-autini='$row[Aut_Ini]' data-autfin='$row[Aut_Fin]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                                    } ?>
                                                </select>
                                            </div>   
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Número:</label>                                     
                                            <div class="col-xs-5" >
                                                <div class="input-group input-group-xs">  
                                                    <span id="Pun_Num" class="input-group-addon alert-info"></span>
                                                    <input type="text" id="Vet_Num" onchange="validaVetNum()" class="form-control input-xs secuencia" tabindex="5" required="" />
                                                    <span class="input-group-addon validate" ><i></i></span>
                                                </div>
                                            </div>

                                            <label class="col-xs-1 control-label label-xs">Aut.:</label>  
                                            <div class="col-xs-4" >
                                                <span id="Aut_Sri" class="form-control input-xs"></span>
                                            </div>   
                                        </div>
                                    </fieldset>    
                                </div>
                            
                            <div class="col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
                                <table id="items"></table>
                                <div id="itemsPager"></div>    
                            </div>
                            <div class="col-xs-7">
                                <div class="condensed" style="min-height: 100px; padding-bottom: 5px;">
                                    <table id="pagos"></table>
                                    <div id="pagosPager"></div>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-primary" type="submit"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                    <button id="btnVetPrint" onclick="$.imprimirUrl($(this).data('url'))" class="btn btn-sm btn-success" style="display:none;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>
                                    <button id="btnVetPrintMin" onclick="$.imprimirUrl($(this).data('url'))" class="btn btn-sm btn-success" style="display:none;"><i class="glyphicon glyphicon-print"></i> Imprimir Detalle</button>
                                </div>                   
                            </div>
							</form>
                            <div class="col-xs-5">
                                <form id="pagoFormTemp" action="javascript:"  class="formDatos form-horizontal normal">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Forma de Pago</legend>                                       
                                    <input type="text" name="Cpc_Cod" style="display: none;" />
                                    <div class="form-group pagoSri" >
                                        <label class="col-xs-3 control-label label-xs required">Pago&nbsp;SRI:</label>  
                                        <div class="col-xs-9"  >
                                            <?php $rs_pag_sri = $obBD_con1->getArrayConsulta(45, '', $obBD_conexion); ?>
                                            <select id="Tpc_Cod" name="Tpc_Cod" class="form-control input-xs readOnly" required="" onchange="backupHeader();">
                                                <option value="">Seleccione...</option>
                                               <?php foreach($rs_pag_sri as $row){ 
                                                   echo "<option value='$row[Tpc_Cod]' >$row[Tpc_Sri] - $row[Tpc_Des]</option>";
                                                } ?>
                                            </select>
                                        </div>    
                                    </div>
                                                                   
                                    <div class="form-group reteTot">
                                        <label class="col-xs-3 control-label label-xs"></label> 
                                        <div class="col-xs-9">                                    
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-addon bold alert-warning">Por Cobrar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i></span>                                        
                                                <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                                                <input id="Val_Pcc" name="Val_Pcc" type="text" class="form-control bold span" style="text-align: right;font-size: 15px; background-color: white;" readonly="" tabindex="-1">
                                            </div>
                                        </div>
                                    </div>   
                                </fieldset>  
                                </form> 
                            </div>                            
                        </div>                                              
                    </div>
                    <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                </div>    
                <script>
                    $(function() { 
                       
                    });
                </script>
               
            </div>
           
        </div>
    </div>

    <script type="text/javascript">
        var docs, items, pagos, data=[], Vet_Index=1, Vet_Selected, index, Cof_Con='<?php echo $configs['Cof_Con']; ?>',storage='Pun_Venta_<?php echo $vendedor['Pun_Cod'] ?>';
        $(function() {           
            docs=$('#documentos');
            items=$('#items');
            pagos=$('#pagos');

            $("#radioec").change(function(){
                $('#Prs_Ced').attr('onchange','validar(1)');
                habilitar('ec',1);
                $('#lb_ec').attr('class','btn btn-success btn-xs');
                $('#lb_ex').attr('class','btn btn-default btn-xs');
                $('#spanec').show();$('#spanex').hide();clear();
            });

            $("#radioex").change(function(){
                clear();
                habilitar('ex',7);
                $('#Prs_Ced').attr('onchange','validar(2)');
                $('#lb_ex').attr('class','btn btn-success btn-xs');
                $('#lb_ec').attr('class','btn btn-default btn-xs');
                $('#spanex').show();$('#spanec').hide();
            });

            $('#Ide_Cod').change(function(){
                $('#Prs_Ced').val('').focus();
                if(this.value*1===1){
                    $('#Prs_Ced').attr('onchange','validar(2)');
                }else{
                    $('#Prs_Ced').attr('onchange','validar(3)');
                }
                habilitar('ex',this.value);
            });

            docs.createGrid({
                data:[], caption: "Documentos Pendientes", rowNum: 10000000, height: 'auto', rownumbers:false,
                onSelectRow:function(index){ selectDoc(index); }, ondblClickRow:function(){ return false ; },
                colModel:[
                    { label: '<i class="glyphicon glyphicon-pencil"></i>', name: 'Vet_Hour', width: 60, align: 'center', formatter:'gridButton', formatoptions:{ action:selectDoc, data:function(o){ return o.Vet_Index; }, icon:'pencil'  }},
                    { label: 'Céd.Int.', name: 'Vet_Index', key: true, width: 15,align:"center", hidden:true },
                    { label: 'Num.', name: 'Vet_Num', width: 60, align: 'center', classes:'bold columnHighlight4'},
                    { label: 'Descrip', name: 'Vet_Desc', width: 125, title:false, editable:true, editoptions:{dataInit:styleDesc} },
                    { label: 'Hora', name: 'Vet_Hour', width: 65, align: 'center'},                    
                    { label: '<i class="glyphicon glyphicon-remove"></i>', name: 'Vet_Hour', width: 60, align: 'center', formatter:'gridButton', formatoptions:{ action:deleteDoc, data:function(o){ return o.Vet_Index; }, icon:'remove', type:'danger' } }
                ]
            },true,'documentosPager',{view:false}).gridButtonsAdd([{caption:'Agregar',buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ addDoc(); } }]).unbind("contextmenu");
            pagos.createGrid({
                data:[], caption: "Pagos", rowNum: 10000000, height: 'auto', footerrow:true,
                colModel:[
                    { label: 'Céd.Int.', name: 'Vet_Num', key: true, width: 15, align:"center", hidden:true },
                    { label: 'Forma', name: 'For_Cod', width: 30, classes:'bgNoRight', formatter: function(cv, opts, rObj){ return '<div data-val="'+cv+'">'+$('#For_Cod option[value="'+cv+'"]').text()+'</div>'; }, unformat:function(el, opts, cell){ return $('div', cell).data('val'); } },
                    { label: 'Tipo', name: 'Pag_Cod', width: 30, classes:'bgNoRight', formatter: function(cv, opts, rObj){ return $('#Pag_Cod option[value="'+cv+'"]').text(); }  },                    
                    { label: 'Banco', name: 'Vet_Ban', width: 50, align: 'center', classes:'bgNoRight', formatter: function(cv, opts, rObj){ var ban=$.varValid(rObj['Ban_Cod'])&&rObj['Ban_Cod'].length>0?'Ban_Cod':($.varValid(rObj['Bak_Cod'])&&rObj['Bak_Cod'].length>0?'Bak_Cod':null); if($.varValid(ban)) return $('#'+ban+' option[value="'+rObj[ban]+'"]').text(); else return ''; } },
                    { label: 'Cta. Banco', name: 'Vet_Cue', width: 50, align: 'center', classes:'bgNoRight'},
                    { label: 'Doc./Cheque', name: 'Vet_Che', width: 50, align: 'center'},
                    { label: 'Monto', name: 'Vet_Tot', width: 40, align: 'right', formatter:'currency', classes:'bgNoColor'},
                    { label: '<i class="glyphicon glyphicon-remove"></i>', name: 'Vet_Hour', width: 20, align: 'center', formatter:'gridButton', formatoptions:{ action:deletePago, data:function(o){ return o.Vet_Num; }, icon:'remove', type:'danger' } }
                ], loadComplete:function(){ $(this).setGridSummary(['Vet_Tot'],{Vet_Che:'<div style="text-align:right;">TOTAL:</div>'}); }
            },true,'pagosPager',{view:false}).gridButtonsAdd([
                {caption:'Agregar',buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){  if(!isDocSelected()) return; if($('#Val_Pcc').val()*1<=0){ $.alert('El saldo a cobrar es cero!'); return; }  registarPagos(); } },{},
                {caption:'Al Contado',buttonicon:'glyphicon glyphicon-usd', onClickButton: function(){  if(!isDocSelected()) return; if($('#Val_Pcc').val()*1<=0){ $.alert('El saldo a cobrar es cero!'); return; }  alContado(); } },
                {caption:'A Crédito',buttonicon:'glyphicon glyphicon-credit-card', onClickButton: function(){  if(!isDocSelected()) return; if($('#Val_Pcc').val()*1<=0){ $.alert('El saldo a cobrar es cero!'); return; }  aCredito(); } }
            ]);
            $('#documentosPager_center').css('width','0px');
            items.createGrid({
                data:[], caption: "Detalle Documento <div class='pull-right'>Precio:&nbsp;<select id='Tpv_Cod' class='' style=''>"+
                        "<?php foreach($precios as $pr) echo "<option value='$pr[Tpv_Cod]' ".($pr['Tpv_Def']=='D'?"selected=''":'').">$pr[Tpv_Des]</option>"; ?>"+
                        "</select>&nbsp;</div>", 
                rowNum: 10000000, height: 'auto', footerrow:true, headertitles:true, selectGridRows:false,
                colModel:[
                    {name:'select',label:'<i class="glyphicon glyphicon-check"></i>', width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:openItemSelector, icon:'check', title:'Seleccionar Item', data:function(o){ return o.index; } }, resizable: false },
                    {name:'Vet_Index',label:'Vet_Index', width:40, align:'center', hidden:true},
                    {name:'index',label:'Index', width:20, sorttype:"int",align:'center',key:true,hidden:true},
                    {name:'Pro_Cod',label:'Céd.Int.', width:20, sorttype:"int",align:'center',hidden:true},  
                    {name:'Pro_Bar',label:'Barras', width:50, sorttype:"int", align:'left', title:false, editable:true, editoptions:{dataInit:styleBar} },
                    {name:'Vet_Can',label:'Cant.', labelLong:'Cantidad', width:40, align:"right", title:false, editable:true, editoptions:{dataInit:styleCant}},
                    {name:'Uni_Des',label:'Uni.', labelLong:'Unidad', width:25, resizable: false },
                    {name:'Ite_Lar',label:'Descripción', width:150},                   
                    {name:'Vet_Dec',label:'Descuen.', labelLong:'Descuento', align:"right", width:20},
                    {name:'Vet_Pru',label:'P. Unitario', labelLong:'Precio Unitario', width:60, align:"right", title:false/*, summaryRound: 8,formatter:"currency",formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.',decimalPlaces: 8, defaultValue: ''}*/, editable:true, editoptions:{dataInit:stylePru}},                    
                    {name:'Vet_Imp',label:'Importe', width:70, align:"right", summaryRound: 2, formatter:"currency", formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.', defaultValue: '0.00'},classes:'columnHighlight1'},
                    {name:'Iva_Cod',label:'CodIva', width:20,hidden:true},  
                    {name:'Iva_Por',label:'IVA', width:15,align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Grava IVA',noMsg:'No Grava IVA'}, title:false, resizable: false },                    
                    {name:'Ice_Int',label:'CodIce', width:20,hidden:true}, 
                    {name:'Ice_Por',label:'ICE', width:20,align:"right", title:false, resizable: false },
                    {name:'Pld_Cod',label:'Pld_Cod', width:20,hidden:true},
                    {name:'Pld_Cdc',label:'Cuenta', width:50, align:"center", formatter:'title', formatoptions:{title:function(o){return o['Pld_Cdc']+' - '+o['Pld_Des'];}}, title:false },
                    {name:'Pld_Des',label:'Pld_Des', width:20,hidden:true},
                    {name:'Adq_Cod',label:'CodAdq', width:20,hidden:true},
                    {name:'Adq_Cor',label:'Adq.',labelLong:'Adquisiciones', width:20,align:"center", title:false, formatter:'title', formatoptions:{title:function(o){return o['Adq_Des'];}}, resizable: false  },                    
                    {name:'delete',label:'<i class="glyphicon glyphicon-remove"></i>', width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:deleteItem, icon:'remove', title:'Eliminar Item', type:'danger', data:function(o){ return o.index; }, attr:{'tabindex':'-1'}, conditional:function(o){ return !(!$.varValid(o['Pro_Cod'])||o['Pro_Cod']===''); } }, resizable: false }
                ]
            },true,'itemsPager',{view:false}).gridButtonsAdd([
                {caption:'Agregar Productos',buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ if(!isDocSelected()) return;  if(!available()){ $.alert('No hay espacio para mas items en este documento!'); return; }  index=0;$('#proDialog').dialog('open'); } },
                {caption:'Remover Todos',buttonicon:'glyphicon glyphicon-remove', onClickButton: function(){ if(!isDocSelected()) return; items.clearGrid(); $.arrayGetItem(data,'Vet_Index',Vet_Selected)['items']=[]; addItem({}); } }
            ]);            
            items.getFootRow(true);          
            items.jqGrid('footerData', 'set',{
                //Vet_Can:'<div class="footerFact"><label>Observ.:</label></div>',
                Ite_Lar:'<div class="footerFact formDatos" class="formDatos"><div style="text-align: left;">Observación:</div><textarea name="Vet_Obs" tabindex="12" class="text" onchange="backupHeader();"></textarea></div>',
                Vet_Pru:'<div class="footerFact"><label>SUBTOTAL:</label><label>TARIFA 0%:</label><label>TARIFA <span class="iva_por"></span>%:</label><label><span class="iva_por"></span>% IVA:</label><label>ICE:</label><label>DESCUENTO:</label><label class="total">TOTAL:</label></div>',
                Vet_Imp:'<div class="footerFact formDatos" id="formTotales"><input id="t_subtotal" name="t_subtotal" type="text" readonly/><input name="t_iva0" type="text" readonly/><input name="t_iva12" type="text" readonly/><input name="t_iva" type="text" readonly/><input name="t_ice" type="text" readonly/><input id="t_descuento" name="t_descuento" type="text" onchange="updateDocument();" class="text" /><input id="t_rubros" name="t_rubros" type="text"  class="total" readonly/></div>',
                Iva_Por:'<div class="footerFact formDatos"><div style="height:56px;"></div><div style="position:absolute;text-align: left;"><select id="Iva_Cod" name="Iva_Cod" style="max-width:100%;" onchange="changeIvas(); backupHeader();" class="text">'+$('#Def_Ivas').html().trim()+'</select></div><div style="height:75px;padding-top:38px;text-align: left;"><input id="Vet_Des" name="Vet_Des" style="height:19px;position:absolute;display:none;" /></div>'
            },false); 
            $("#accordion").accordion({collapsible : true/*, active : 'none'*/});
            $('#Pag_Pld_1,#Pag_Pld_2').on('change',function (){ var aux=$(this).val(); $(this).find('option').removeAttr('selected'); $(this).find('option[value='+aux+']').attr('selected','selected');  });
            $('#For_Cod').on('change',function (){ 
                checkCuentaPago(); 
                var credi=('0'+this.value)*1===2, val=$('#Pag_Cod').find('option').hide().end().find('option[data-forcod="'+this.value+'"]').show()[0].value;
                $('#Pag_Cod').val(val).trigger('change');
                $('.pagoCredito')[credi?'show':'hide']();  
                (credi?$('#Cpc_Ven').attr('required','required'):$('#Cpc_Ven').removeAttr('required')); 
                (credi?$('#saldo_pago').attr('readonly','readonly'):$('#saldo_pago').removeAttr('readonly'));                
                //backupHeader(); 
            }).trigger('change');
            
            $('#Pag_Cod').on('change',function (){ 
                var text=$(this).find('option:selected').text().toUpperCase();
                $('.cuen_ban,.banco,.bancos,.obs_credito').find(':input').removeAttr('required').end().hide().setData({}); 
                $('.cuenta_pago').show().find(':input').attr('required','required');

                $('.fecha_cheque').hide().removeAttr('disabled');

                switch(text){
                    case 'DEPOSITO':
                    case 'TRANSFERENCIA':  
                        $('.banco,.cuen_ban').show().find(':input').attr('required','required'); 
                        $('.cuenta_pago').hide().removeAttr('disabled');
                        $('#Vet_Cue').attr('disabled','disabled');
                        break;
                    case 'CHEQUE':
                        $('.bancos,.cuen_ban').show().find(':input').attr('required','required');
                        $('.fecha_cheque').show().find(':input').attr('required','required');    
                        $('#Ban_Cod').trigger('change');
                        $('#Vet_Cue').removeAttr('disabled');
                        break;    
                    default: 
                        
                        break;
                }
            }).trigger('change');

            $('#monto_pago,#saldo_pago').on('keyup',function (){ 
                var mon=$('#monto_pago').val(),sal=$('#saldo_pago').val(), cam=(!isNaN(mon)&&!isNaN(sal)?$.round(mon)-$.round(sal):0);
                $('#cambio_pago').val($.toFixed(cam));
                $('#cam_sal').removeClass('alert-danger alert-success').addClass(cam<0?'alert-danger':'alert-success').find('b').html(cam<0?'Por Cobrar':'Cambio');
            }).on('change',function (){ 
                var monto=$(this).attr('id')==='monto_pago', val=$(this).val(),sal=$('#Val_Pcc').val()*1;
                $(this).val(isNaN(val)||val===''?
                    (monto?'':$.toFixed(sal)):
                    (monto?
                            $.toFixed(val):
                            (val>sal?
                                    $.toFixed(sal):
                                    $.toFixed(val)
                            )
                    )
                ).trigger('keyup');
            });
            $('#Ban_Cod').on('change',function (){ 
                $('#Vet_Cue').val($(this).find('option:selected').data('bancue'));
            });
            $('.datepickers').createDatePickers({checkAvailability:true,hideMsg:false}).mask("9999-99-99",{placeholder:"_"});
            $('#Cpc_Ven').datepicker("option","minDate",'<?php echo $Vet_Fec; ?>');
            $('#Cli_Btn').createFlyout('Debe Seleccionar el Proveedor!',{icon:'exclamation',placement:'right'});
            checkCuentaPago();
            clearDocument();
            recoverDocs();

            $("#Tpv_Cod2").val($("#Tpv_Cod").val());
            $('#Tpv_Cod').on('change',function(event){
                $("#Tpv_Cod2").val($("#Tpv_Cod").val());
            });

        }); 

        function isDocSelected(){ if(!$.varValid(Vet_Selected)){ $.alert('Seleccione un documento pendiente!'); return false; } return true;  } 
        function addDoc(){
            $('#panelVentas').find(':input,td.btn').removeAttr('disabled').removeClass('readOnly');
            $('#btnVetPrint').hide();
	    $('#btnVetPrintMin').hide();
            var next=Vet_Index, d=new Date(), hour=d.getHours().padLeft(2)+':'+d.getMinutes().padLeft(2)+':'+d.getSeconds().padLeft(2);
            var doc=$.extend(doc,{
                Vet_Index:next, Vet_Num:'No. '+next+':', Vet_Desc:$('#Def_Desc').val(), Vet_Hour:hour,                 
                items:[], pagos:[], data:$.extend({ For_Cod:1, Pag_Pld:$('#Pag_Pld_1').val(), Tic_Cod:$('#Def_Tic_Cod').val(), Tpc_Cod:1, Iva_Cod:$('#Def_Ivas').val(), Ciu_Cod:'<?php echo $ciudad['Ciu_Cod']; ?>', Ciu_Des:'<?php echo $ciudad['Ciu_Des']; ?>',Vet_Fec:'<?php echo $Vet_Fec; ?>',Cpc_Ven:'<?php echo $Cpc_Ven; ?>' },cons_final||{})
            });
             $('#Doc_Title').html('<u>'+doc['Vet_Num']+'</u> '+doc['Vet_Desc']);
            $('.formDatos').setData(doc['data']);
            //items.set
            data.push(doc);
            docs.jqGrid('addRowData',next,doc,'last');
            docs.jqGrid('editRow',next); 
            docs.jqGrid('setSelection', next, false);
            items.clearGridData();
            pagos.clearGrid();
            Vet_Selected=next;
            addItem({Vet_Index:next});            
            Vet_Index++;   
            changeIvas();
            backupDocs();
            punNum();
        }
        function selectDoc(index){ 
            $('#panelVentas').find(':input,td.btn').removeAttr('disabled').removeClass('readOnly');    
            $('#btnVetPrint').hide();
	    $('#btnVetPrintMin').hide();
            var doc=$.arrayGetItem(data,'Vet_Index',index), rows=doc['items'];
            $('#Doc_Title').html('<u>'+doc['Vet_Num']+'</u> '+doc['Vet_Desc']);
            $('.formDatos').setData(doc['data']);
            Vet_Selected=index;
            items.setRows(rows).startGridEdit();  
            pagos.setRows(doc['pagos']);  
            $.each(rows,function(i,v){ items.changeRow(v['index'],{rowId:v['index']}); } );   
            changeIvas();
            punNum();
            resize();
        }
        function deleteDoc(index){ 
            docs.jqGrid('delRowData',index); 
            $.arraySpliceWhere(data,'Vet_Index',index); backupDocs();
            if(Vet_Selected*1===index*1){ Vet_Selected=null; clearDocument(); }
        }
        function clearDocument(){
            Vet_Selected=null;
            $('#Doc_Title').html('&nbsp;');
            items.clearGridData();
            updateDocument();
            $('.formDatos').setData({});
            $('#panelVentas').find(':input').attr('disabled','disabled').addClass('readOnly');
            $('#btnVetPrint').hide();
	    $('#btnVetPrintMin').hide();
            resize();
        }
        // A�ade un item al documento
        function addItem(item){
            var next=items.jqGrid('getCol','index',false,'max');
            next=(isNaN(next)?1:next+1);
            var new_item=$.extend(item,{index:next,Vet_Can:1,Vet_Pru:'',Vet_Index:Vet_Selected});
            items.jqGrid('addRowData',next,new_item,'last');
            items.jqGrid('editRow',next);
            $.arrayGetItem(data,'Vet_Index',Vet_Selected)['items'].push(new_item);
            updateRowItem({rowId:next});            
            resize();
        }
        // Abre dialogo producto para cambiar item
        function openItemSelector(id){ index=id; $('#proDialog').dialog('open'); }
        // Elimina item
        function deleteItem(index){ 
            var row=items.jqGrid('getRowData',index), lastId=items.jqGrid('getCol','index',false,'max'); 
            if(row['Pro_Cod']!==''){ 
                items.jqGrid('delRowData',index);
                $.arraySpliceWhere($.arrayGetItem(data,'Vet_Index',Vet_Selected)['items'],'index',index);
                if(items.jqGrid('getRowData',lastId)['Pro_Cod']!=='') addItem({});
                updateDocument(); resize(); backupDocs();
            } 
        }
        function resize(){ if(items.width()!==$('#documentoMain').width()) items.jqGrid("resizeGrid");  }
        function styleDesc(e,obj,opt){ $(e).on('change',function(){ var id=$(this).attr('rowid'), doc=$.arrayGetItem(data,'Vet_Index',id); doc['Vet_Desc']=$(this).val(); if(id*1===Vet_Selected*1) $('#Doc_Title').html('<u>'+doc['Vet_Num']+'</u> '+doc['Vet_Desc']); backupDocs(); }); }
        function styleCant(e,obj,opt){            
            e.style.textAlign = 'right';  e.placeholder='0'; e.type='number';
            $(e).on('keyup',function (){
               if(isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/){ $(this).val('1').focus();   }
               else if (this.value % 1 !== 0){ var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>2) this.value=$.toFixed(this.value);  } 
               updateRowItem(obj);
            });
        }
        // estilo precio unitario
        function stylePru(e,obj,opt){            
            e.style.textAlign = 'right'; e.placeholder='0.00';
            $(e).on('keyup',function (){
               if(isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/){ $(this).val('').focus();; }
               else if (this.value % 1 !== 0){ var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>8) this.value=$.toFixed(this.value,8);  } 
               updateRowItem(obj);
            });            
        }
        function styleBar(e,obj,opt){ 
            e.placeholder='BAR.COD.';
            e.value='';
            $(e).on('keyup',function (e){
                if(this.value!==''&&e.keyCode===13){
                    index=obj['rowId'];
                    $.SearchOrDialogArray('#proDialog',selectItem,$.extend($('#proForm').getData(),{search:this.value,op_opciones:'c'}));  
                    this.value='';
                }
            });
        }
        // Actualiza los valores de la fila
        function updateRowItem(obj){
            var row=$.extend({},items.jqGrid('getRowData',obj['rowId']),items.find('tr#'+obj['rowId']).getDataForced());
            row['Vet_Imp']=row['Vet_Can']*(0+row['Vet_Pru'])*1;
            row['Vet_Imp']=row['Vet_Imp']-(('0'+row['Vet_Dec'])*1>0?row['Vet_Imp']*row['Vet_Dec']/100:0);
            items.changeRow(obj['rowId'],row);            
            updateDocument();
            $.extend($.arrayGetItem($.arrayGetItem(data,'Vet_Index',Vet_Selected)['items'],'index',obj['rowId'],'item_index'),row);
            backupDocs();
        }
        function updateDocument(){
            var rows = items.jqGrid('getRowData'),des_val=$('#t_descuento').val(), des_por=$('#Vet_Des').val(), tot={t_subtotal:0,t_iva0:0,t_iva12:0,t_iva:0,t_ice:0,t_descuento:(!isNaN(des_val)?des_val*1:0),Vet_Des:(!isNaN(des_por)?des_por*1:0),t_rubros:0},                    
                Tic_Sri=$('#Tic_Cod').find('option:selected').data('ticsri')*1, rise=(Tic_Sri===2||Tic_Sri===9);
            for (var i=0, z=rows.length-1; i<z ; i++){
                var row=rows[i];
                row['Vet_Imp']=(row['Vet_Imp']*1);
                row['Iva_Por']=rise?0:('0'+row['Iva_Por'])*1;
                row['Ice_Por']=('0'+row['Ice_Por'])*1;
                tot['t_subtotal']=tot['t_subtotal']+row['Vet_Imp'];
                if(row['Iva_Por']===0||rise) tot['t_iva0']=tot['t_iva0']+row['Vet_Imp']; 
                else tot['t_iva12']=tot['t_iva12']+row['Vet_Imp']; 
            }
            tot['Vet_Des']=(tot['t_descuento']>0?(tot['t_subtotal']>=tot['t_descuento']?tot['t_descuento']*100/tot['t_subtotal']:100):tot['Vet_Des']); 
            for (var i=0, z=rows.length-1; i<z ; i++){
                var row=rows[i], des_glob=(tot['Vet_Des']>0?row['Vet_Imp']*tot['Vet_Des']/100:0), ice=(row['Ice_Por']>0?(row['Vet_Imp']-des_glob)*row['Ice_Por']/100:0);
                if(row['Iva_Por']>0&&!rise){
                    tot['t_ice']=tot['t_ice']+ice;
                    tot['t_iva']=tot['t_iva']+(row['Vet_Imp']+ice-des_glob)*row['Iva_Por']/100;
                }
            }
            tot['t_iva']=$.round(tot['t_iva']); tot['t_ice']=$.round(tot['t_ice']);    
            tot['t_rubros']=tot['t_subtotal']+tot['t_iva']+tot['t_ice']-tot['t_descuento'];
            
            var pagos_tot=pagos.jqGrid('getCol', 'Vet_Tot', false, 'sum');;           
            $('#Val_Pcc').val( $.toFixed(tot['t_rubros']-pagos_tot) );
            
            $.each(tot,function (k,v){ tot[k]=$.toFixed(v,k!=='Vet_Des'?2:10); });   
            $('#formTotales').setData(tot);
            $('#Vet_Des').val(tot['Vet_Des']);             
        }
        // Valida Todo antes de guardar
        function validaDocument(){  
           if(($('#Val_Pcc').val())*1<0){ $.alert('El valor a pagar no puede ser negativo!<br/>Revise los datos.',null,'remove'); return; }
           if(($('#Val_Pcc').val())*1>0){ $.alert('Aun queda saldo pendiente por cobrar!',null,'remove'); return; }           
           var docu=$('.formDatos').getData('saveDocument'), Tic=$("#Tic_Cod option:selected");           
           if(!docu['Cli_Cod'].length){ $('#Cli_Btn').focus(); $('#Cli_Btn').flyout('show'); return; }
           
           docu['pagos']=$.arrayGetItem(data,'Vet_Index',Vet_Selected)['pagos'];           
           docu['Tic_Des']=Tic.text(); 
           docu['Tic_Sri']=Tic.data('ticsri');
           docu['Aut_Cod']=Tic.data('autcod');
           docu['Aut_Tem']=Tic.data('auttem');
           docu['Aut_Sri']=Tic.data('autsri');
           docu['Pun_Sri']=Tic.data('punsri');
           docu['autini']=Tic.data('autini');
           docu['autfin']=Tic.data('autfin');
           docu['Vet_Num']=$('#Vet_Num').val();
           docu['Caj_Cod']='<?php echo $caja['Caj_Cod']; ?>';
           docu['Ciu_Cod']='<?php echo $ciudad['Ciu_Cod']; ?>';
           
           if(cons_final['Cli_Cod']===docu['Cli_Cod']&&docu['Aut_Tem']==='E'&&$.round($('#t_rubros').val())>200){ $.alert('El <i>Documento Electronico</i> es mayor de $&nbsp;200.00 y no puede ser <u>Consumidor Final</u>!',null,'remove'); return;  }
           if(items.jqGrid('getDataIDs').length-1<=0){ $.alert('Debe seleccionar al menos un <u>Item</u>!',null,'remove'); return; }
           docu['items']=items.getGridBatch(); 
           items.startGridEdit();
           docu['items'].splice(docu['items'].length-1, 1);
           for(var i=0; i<docu['items'].length; i++){
               if(docu['items'][i]['Vet_Imp']*1<=0){ $.alert('El producto <u>'+docu['items'][i]['Ite_Lar']+'</u> no puede tener <i>Importe cero</i>!',null,'remove'); return; }
           }           
           $.arraySpliceFields(docu['items'],['index','delete','select']);
           //console.log(docu);
           $.createDialogConfirm('¿Esta seguro de guardar el Documento?',docu,saveDocument);
        }  
        // Guardar documento
        function saveDocument(data){ $.saveDataJson('',data,
            function (resp){
                var aux=Vet_Selected; Vet_Selected=null;
                deleteDoc(aux);                
                $('#panelVentas').find(':input,td.btn').attr('disabled','disabled').addClass('readOnly');
                $('#btnVetPrint').removeAttr('disabled').show().data('url',resp['Vet_Link']);
		if($.varValid(resp['Vet_Link_Min'])) $('#btnVetPrintMin').removeAttr('disabled').show().data('url',resp['Vet_Link_Min']); else $('#btnVetPrintMin').hide();  
                if($.varValid(resp['mail'])){
                    if(resp['mail']===false) $.alert('La transacción se realizó con éxito!.<br> Surgio un problema al enviar el mail <u>Comprobante Electronico</u> al <i>Cliente</i>!');
                    if(resp['mail']===true) $.alert('El mail del <u>Comprobante Electronico</u> al <i>Proveedor</i> se envio correctamente!',null,'ok green');
                    return false;
                }
            }); 
        }
        function punNum(){
            var pun=$('#Tic_Cod').find('option:selected').data()||{};            
            $('#Pun_Num').html(typeof pun['sucsri']!=='undefined'?pun['sucsri']+'-'+pun['punsri']+'-':'');             
            $('#Aut_Sri').html(pun['auttem']==='E'?'Electronica':pun['autsri']);             
            $('#Vet_Num').val('').data('vet_num_old','');
            validaVetNum();
        }
        function validaVetNum(){
            var pun=$('#Tic_Cod').find('option:selected').data()||{}, vnum=$('#Vet_Num'), Vet_Num=vnum.val(), set_old=false;            
            if(typeof pun['autini']==='undefined'){ vnum.val('').fieldValid(''); return; }
            if(isNaN(Vet_Num)){ vnum.val('').fieldValid(false,'El dato "'+Vet_Num+'" no es válido!'); set_old=true; }          
            Vet_Num=(Vet_Num!==''&&!isNaN(Vet_Num))?Vet_Num*1:'';            
            if(Vet_Num!==''&&(Vet_Num<pun['autini']||Vet_Num>pun['autfin'])){ vnum.val('').fieldValid(false,'El número '+Vet_Num+' no está en el rango ('+pun['autini']+' - '+pun['autfin']+')!'); set_old=true; }
            if(set_old&&$.varValid(vnum.data('old_vet_num'))&&vnum.data('old_vet_num').length>0){ vnum.val(vnum.data('old_vet_num')); return; }
            $.extend(pun,{validaVetNum:true, Vet_Num:Vet_Num, Vet_Num_Old:vnum.data('vet_num_old')});  
            $('#Vet_Num').getValidationJson('',pun,function(r){  
                var rnum=$('#Vet_Num');
                if(r['success']===false){
                    if(r['Vet_Num_Old']==='') rnum.fieldValid(true);                    
                }else{
                    if(r['Vet_Num']*1>r['Aut_Fin']){
                        rnum.fieldValid(false,'Ya no quedan números disponibles en el rango ('+r['Aut_Ini']+' - '+r['Aut_Fin']+')!');
                        r['Vet_Num']='';
                    }else{
                        if(r['Vet_Num_Old']*1>=r['Aut_Ini']&&r['Vet_Num_Old']*1<=r['Aut_Fin'])
                            delete r['Vet_Num'];
                        else
                            rnum.fieldValid(false,'El número '+r['Vet_Num_Old']+' no está en el rango ('+r['Aut_Ini']+' - '+r['Aut_Fin']+')!');
                    }
                }
                if($.varValid(r['Vet_Num'])){
                    rnum.val(r['Vet_Num']).data('old_vet_num',r['Vet_Num']);
                }
            });
        }
        function changeIvas(){
            var ids = items.jqGrid('getDataIDs'), iva={Iva_Cod:$('#Iva_Cod').val(), Iva_Por:$('#Iva_Cod option:selected').data('ivapor')}; $('.iva_por').html(iva['Iva_Por']);
            for (var i = 0; i < ids.length; i++){ if('0'+items.jqGrid('getCell',ids[i],'Iva_Por')*1>0) items.changeRow(ids[i],iva); } updateDocument();
        }  
        function checkCuentaPago(){ var opts=$('#Pag_Pld_'+$('#For_Cod').val()).html(); if(!$.varValid(opts)) opts='<option value="">Seleccione..</option>'; $('#Pag_Pld').html(opts.trim()); }
        function registarPagos(){
            if(pagos.jqGrid('getDataIDs').length>0) $('#For_Cod').attr('disabled','disabled'); else $('#For_Cod').removeAttr('disabled');
            $('#For_Cod').val(1).trigger('change');
            $('#pagosDialog').dialog('open');
            $('.saldos').setData({Vet_Tot:$('#Val_Pcc').val()});
            
        }
        function alContado(){  
            $('#For_Cod').val(1).trigger('change');
            var ite_pagos=[$.extend($('#pagosForm').getData(),{Vet_Num:1,Vet_Tot:$('#t_rubros').val()})];            
            $.arrayGetItem(data,'Vet_Index',Vet_Selected)['pagos']=ite_pagos;      
            pagos.setRows(ite_pagos);
            updateDocument();
            backupDocs();
        }
        function aCredito(){  
            $('#For_Cod').val(2).trigger('change').attr('disabled','disabled');                      
            $.arrayGetItem(data,'Vet_Index',Vet_Selected)['pagos']=[];      
            pagos.clearGrid();                   
            updateDocument();
            $('#pagosDialog').dialog('open');
            $('.saldos').setData({Vet_Tot:$('#Val_Pcc').val()});
        }
        function addPago(){
             var next=pagos.jqGrid('getCol','Vet_Num',false,'max'), text=$('#Pag_Cod').find('option:selected').text().toUpperCase(), pago=$('#pagosForm').getData(), ite_pagos=$.arrayGetItem(data,'Vet_Index',Vet_Selected)['pagos'];

             pago['Vet_Num']=(isNaN(next)?1:next+1);
             if(text==='TRANSFERENCIA'||text==='DEPOSITO') pago['Pag_Pld']=$('#Ban_Cod option:selected').data('pldcod');              
             ite_pagos.push(pago);
             pagos.setRows(ite_pagos);
             updateDocument();
             backupDocs();
             $('#pagosDialog').dialog('close');
             //console.log(pago);
        }
        function deletePago(Vet_Num){
            if(!$.varValid(Vet_Selected)) return;
            pagos.jqGrid('delRowData',Vet_Num);
            pagos.trigger('reloadGrid');           
            var ite_pagos=$.arrayGetItem(data,'Vet_Index',Vet_Selected)['pagos'];
            $.arraySpliceWhere(ite_pagos,'Vet_Num',Vet_Num); 
            updateDocument();
            backupDocs();
        }
        function setLocalStore(name,data){ localStorage.setItem(name, $.jsonParser(data)); }
        function getLocalStore(name){
            var data=localStorage.getItem(name);
            if($.varValid(data)) return $.jsonParser(data); else return;
        }
        function backupHeader(){ if(!$.varValid(Vet_Selected)) return;  $.arrayGetItem(data,'Vet_Index',Vet_Selected)['data']=$('.formDatos').getData(); backupDocs(); }
        function backupDocs(){ setLocalStore(storage,{Vet_Index:Vet_Index,docs:data}); }
        function recoverDocs(){
            var punto_venta=getLocalStore(storage)||{},  def={
                    Def_Desc:getLocalStore('Def_Desc'),
                    Def_Tic_Cod:getLocalStore('Def_Tic_Cod'),
                    Def_Ivas:getLocalStore('Def_Ivas'),
                    Def_Contado:getLocalStore('Def_Contado'),
                    Def_Credito:getLocalStore('Def_Credito')
                }; 
            $('#def_form').setData(def,false);
            Vet_Index=(punto_venta['Vet_Index']||1)*1;
            data=punto_venta['docs']||[];           
            $.each(data,function(i,v){ docs.jqGrid('addRowData',v['Vet_Index'],v,'last'); });            
            docs.startGridEdit();
            
        }
    </script>
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTO--> 
    <div id="proDialog" title="B&uacute;squeda de Productos"><form class="form-horizontal normal"><input type="text" name="Vet_Fec" class="Vet_Fec" style="display: none;" value="<?php echo $Vet_Fec; ?>" /></form></div>
    <script>
        // DIALOG BUSCAR Producto            
        $.createSearchDialogPrecio('proDialog',[
            { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20,align:"center",hidden:false },                                
            { label: 'Descripción', name: 'Ite_Lar', width: 100 },                      
            { label: 'Marca', name: 'Mar_Des', width: 25},            
            { label: 'Categoria', name: 'Cat_Des', width: 40,align:"center" },            
            { label: 'Stock', name: 'Stk_Can', width: 30, align: 'right'},
            { label: 'Precio', name: 'Pre_Pvp', width: 30, align: 'right'},
            { label: 'IVA', name: 'Iva_Por', width: 20, align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Grava IVA',noMsg:'No Grava IVA'}, title:false }, 
            { label: 'P.V.P.', name: 'PVP', width: 30, align: 'right', formatter:function(cv,opts,robj){ if(!$.varValid(robj['Pre_Pvp'])||!$.varValid($('#Def_Ivas option:selected').data('ivapor'))) return ''; return !($.round(robj['Iva_Por'])>0)? $.toFixed(robj['Pre_Pvp']):$.toFixed( $.round(robj['Pre_Pvp']) +$.round(robj['Pre_Pvp'])*$('#Def_Ivas option:selected').data('ivapor')/100 ); } },            
            { label: 'Adq.', name:'Adq_Cor', width:20, align:"center", formatter:'title',formatoptions: {title:function(o){return o['Adq_Des'];}} },
            { label: 'Ubic.', name: 'Ubi_Des', width: 25, align: 'right'},
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectItem, conditional:function(o){ return !(Cof_Con==='S'&&(!$.varValid(o['Pld_Cod'])||o['Pld_Cod']==='')); }, caseFalse:function(){ return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>'; } } }            
        ],null,700,null,null,{ title:'Producto', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] });                 
        // Selecciona Producto
        function available(){ var its=items.jqGrid('getDataIDs').length, max=$('#Tic_Cod').find('option:selected').data('autima'),  available=((isNaN(max)||max==='')||($.round(('0'+max),0)!==0)&&its<$.round(('0'+max),0)), full=!available; return available; }
        function selectItem(item){            
            if(Cof_Con==='S'&&(!$.varValid(item['Pld_Cod'])||item['Pld_Cod'].length===0)){ $.alert('El producto no posee una <u>Cuenta Contable</u> parametrizada!'); return; }
            var lastId=items.jqGrid('getCol','index',false,'max'), close=true, its=items.jqGrid('getDataIDs').length, full=!available();
            if(its===0){ addItem({}); lastId=1; }else if(!full&&items.jqGrid('getRowData',lastId)['Pro_Cod']!==''){ addItem({}); lastId=lastId*1+1; }
            if(index===0){ index=lastId; close=false;  }  
            var new_item=$.extend(item,item['Iva_Por']*1>0?{Iva_Cod:$('#Iva_Cod').val(), Iva_Por:$('#Iva_Cod option:selected').data('ivapor')}:{Iva_Ren_Cod:'',Iva_Ren_Con:'',Iva_Ren_Por:'',Iva_Ren_Sri:''});
            var precio=$.arrayGetItem(item['Precios'],'Tpv_Cod',$('#Tpv_Cod').val(),'pre_index')||{};           
            items.changeRow(index,new_item,null,{Vet_Pru:precio['Pre_Pvp']});            
            updateRowItem({rowId:index});
            /* Actualizo el arreglo y el backup */
            //console.log($.arrayGetItem($.arrayGetItem(data,'Vet_Index',Vet_Selected)['items'],'index',index,'item_index'));
            $.extend($.arrayGetItem($.arrayGetItem(data,'Vet_Index',Vet_Selected)['items'],'index',index,'item_index'),new_item);
            var last=items.jqGrid('getRowData',lastId);
            if(last['Pro_Cod']!==''&&available()){                
                    addItem({});
            }
            backupDocs();
            if(full){ $('#proDialog').dialog('close'); return;  }
            if(close){ $('#proDialog').dialog('close'); setTimeout(function (){ $('#'+(index)+'_Vet_Can').focus(); },0); }else if(available()) index=0; else index=lastId*1+1;
            updateDocument();
        }

        

    </script> 
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="clieDialog" title="B&uacute;squeda de Cliente"><form class="form-horizontal normal"> </form></div>
    <script>
        // DIALOG BUSCAR proveedor            
        $.createSearchDialog('clieDialog',[
            { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15,align:"center",hidden:true },                                
            { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },                      
            { label: 'Cliente', name: 'cliente', width: 100},
            { label: 'Direcc.', name: 'Prs_Dir', width: 60 },             
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectCliente} }
        ],null,null,null,{headertitles:true},{ title:'Cliente', text:'Prs_Ced' });         
        function selectCliente(cliente){
            $('#clieFormTemp').setData($.extend(cliente,{op_opciones:'c'})); 
            $('#clieDialog').dialog('close');  
            backupHeader();
        }
    </script>  
    <div id="pagosDialog" title="Agregar Pagos">        
        <form id="pagosForm" class="form-horizontal normal" action="javascript:addPago()">
            <div class="form-group">
                <label class="col-xs-3 control-label label-xs required">Forma:</label>  
                <div class="col-xs-6" >
                    <?php $rs_forma = $obBD_con1->getArrayConsulta(21, '', $obBD_conexion); ?>
                    <select id="For_Cod" name="For_Cod" class="form-control input-xs readOnly" data-trigger="" required="">
                        <option value="">Seleccione...</option>
                       <?php foreach($rs_forma as $row){ 
                           echo "<option value='$row[For_Cod]' ".($row['For_Des']=='Contado'?"selected=''":'').">$row[For_Des]</option>";
                        } ?>
                    </select>
                </div>
            </div>  
            <div class="form-group">
                <label class="col-xs-3 control-label label-xs required">Tipo:</label>  
                <div class="col-xs-6" >
                    <?php $rs_tipo = $obBD_con1->getArrayConsulta(69, '', $obBD_conexion); ?>
                    <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs readOnly" data-trigger="" required="">                        
                       <?php 
                       echo "<option value='' data-forcod=''>Seleccione...</option>";
                       foreach($rs_tipo as $row){ 
                           if(!endsWith(strtoupper(trim($row['Pag_Des'])),'PAGAR')&&!startsWith(strtoupper(trim($row['Pag_Des'])),'CRUCE')) echo "<option value='$row[Pag_Cod]' data-forcod='$row[For_Cod]' ".">$row[Pag_Des]</option>";
                        } ?>
                    </select>
                </div>
            </div> 
            <?php if($configs['Cof_Con']=='S'){ ?>
            <div class="form-group cuenta_pago">
                <label class="col-xs-3 control-label label-xs">Cuenta:</label>  
                <div class="col-xs-9">                                    
                    <select id="Pag_Pld" name="Pag_Pld" class="form-control input-xs readOnly" required=""></select>
                </div>
            </div>
            <?php } ?> 
            <div class="form-group bancos">
                <label class="col-xs-3 control-label label-xs required">Banco:</label>  
                <div class="col-xs-6" >
                    <?php $rs_bancos = $obBD_con1->getArrayConsulta(70, '', $obBD_conexion); ?>
                    <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs readOnly" required="">                        
                       <?php foreach($rs_bancos as $row){ 
                           echo "<option value='$row[Bak_Cod]' >$row[Bak_Des]</option>";
                        } ?>
                    </select>
                </div>
            </div>
            <div class="form-group banco">
                <label class="col-xs-3 control-label label-xs required">Banco:</label>  
                <div class="col-xs-6" >
                    <?php $rs_banco = $obBD_con1->getArrayConsulta(71, $Ses_Emp_Cod, $obBD_conexion); ?>
                    <select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly" data-trigger="" required="">                        
                       <?php foreach($rs_banco as $row){ 
                           echo "<option value='$row[Ban_Cod]' data-pldcod='$row[Pld_Cod]' data-bancue='$row[Ban_Cue]'>$row[Pld_Des]</option>";
                        } ?>
                    </select>
                </div>
            </div>
            <div class="form-group cuen_ban" style="display: none;">
                <label class="col-xs-3 control-label label-xs required">Cta&nbsp;Banco:</label>                                     
                <div class="col-xs-9">
                     <input type="text" id="Vet_Cue" name="Vet_Cue" onchange="" class="form-control input-xs readOnly">
                </div>
            </div>
            <div class="form-group cuen_ban" style="display: none;">
                <label class="col-xs-3 control-label label-xs required">Número:</label>                                     
                <div class="col-xs-6">
                    <div class="input-group input-group-xs">                          
                        <input type="text" id="Vet_Che" name="Vet_Che" onchange="" class="form-control input-xs">
                        <span class="input-group-addon validate"><i class="glyphicon glyphicon-ok green"></i></span>
                    </div>
                </div>
            </div>

            <div class="form-group fecha_cheque" style="display: none;">
                <label class="col-xs-3 control-label label-xs required">Fecha:</label>                                     
                <div class="col-xs-6">
                   <input id="Fec_che" name="Fec_che" type="text" class="form-control input-xs datepickers">
                </div>
            </div>

            <?php if($configs['Cof_Con']=='S'){ ?>
            <div class="form-group pagoCredito" style="display: none;"> 
                <input type="text" name="Cpc_Min" style="display:none" />
                <label class="col-xs-3 control-label label-xs required">Vencimiento:</label>  
                <div class="col-xs-6">                                    
                    <input id="Cpc_Ven" name="Cpc_Ven" type="text" class="form-control input-xs datepickers" />
                </div>                 
            </div> 
            <div class="form-group pagoCredito obs_credito" style="display: none;">
                <label class="col-xs-3 control-label label-xs">Observación:</label>  
                <div class="col-xs-9">                                    
                    <textarea name="Cpc_Obs" class="form-control input-xs"></textarea>
                </div> 
            </div>
            <?php } ?>  
            <div class="form-group saldos">               
                <div class="col-xs-12">                                    
                    <div class="input-group input-group-sm">
                        <span class="input-group-addon bold alert-warning" style="width:140px;">Saldo a Cobrar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right pull-right"></i></span>                                        
                        <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                        <input id='saldo_pago' name="Vet_Tot" type="text" class="form-control bold span" style="text-align: right;font-size: 15px;padding-right: 20px;" required="" >
                    </div>
                </div>
            </div> 
            <div class="form-group saldos">               
                <div class="col-xs-12">                                    
                    <div class="input-group input-group-sm">
                        <span class="input-group-addon bold alert-info" style="width:140px;">Monto Dinero&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right pull-right"></i></span>                                        
                        <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                        <input id='monto_pago' name="Vet_Mon" type="text" class="form-control bold span clearable" style="text-align: right;font-size: 15px;padding-right: 20px;">
                    </div>
                </div>
            </div>               
            <div class="form-group saldos">               
                <div class="col-xs-12">                                    
                    <div class="input-group input-group-sm">
                        <span id='cam_sal' class="input-group-addon bold alert-danger" style="width:140px;"><b>Por Cobrar</b>&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right pull-right"></i></span>                                        
                        <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                        <input id='cambio_pago' name="Vet_Cam" type="text" class="form-control bold span" style="text-align: right;font-size: 15px;padding-right: 20px;" readonly="" >
                    </div>
                </div>
            </div> 
            <div class="form-group center">     
                <button class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> Agregar</button>                
            </div>
        </form>
    </div>
    <script>
        // DIALOG pagos
        $('#pagosDialog').createDialog({height:325,icon:'usd'});
    </script>
    <div id="clieCreateDialog" title="Registrar Cliente" style="display:none;">        
        <form class="form-horizontal normal" id="clieCreateForm" action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaProvee(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset" >
                <legend class="Titulos2">Datos del Cliente</legend>

                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Ciudadano:</label>
                    <div class="col-xs-5" >
                        <div class="btn-group" data-toggle="buttons">
                            <label id="lb_ec" class="btn btn-success btn-xs">
                                <input id="radioec" name="tipo" value="Ec" type="radio" checked=""><i id="spanec" class="fa fa-check"></i> Ecuatoriano
                            </label>
                            <label id="lb_ex" class="btn btn-default btn-xs">
                                <input id="radioex" name="tipo" value="Ex" type="radio"><i id="spanex" class="fa fa-check" style="display: none;"></i> Extranjero
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>  
                    <div class="col-xs-5" >
                            <div class="input-group input-group-xs">
                                <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="validar(1)" required="" />
                                <span class="input-group-addon validate" ><i></i></span>
                            </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="checkbox check-big" style="position:absolute;">                          
                          <label><input type="checkbox" name="Cli_Con" value="S" offval="N">Obligado Contab.</label>
                        </div>
                    </div>
                </div> 
                <div class="form-group">
                        <label class="col-xs-3 control-label label-xs">Documento:</label>
                        <div class="col-xs-5" >
                            <?php $rs_identi = $obBD_con1->getArrayConsulta(299, '', $obBD_conexion); ?>
                            <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                                <option value="">Seleccionar</option>
                                <?php foreach($rs_identi as $row){ echo "<option value='$row[Ide_Cod]' data-tipo='$row[Tipo]'>$row[Ide_Des]</option>"; } ?>
                            </select>
                        </div>
                </div> 
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Contribuyente:</label>  
                    <div class="col-xs-4" >
                        <select id="Cli_Tic" name="Cli_Tic" class="form-control input-xs" required="" onchange="if(this.value==='N'){ $('.juridico').hide();$('.natural').show(); }else{ $('.natural').hide();$('.juridico').show(); }">
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
                    <label class="col-xs-3 control-label label-xs">Nomb.Comerc.:</label>  
                    <div class="col-xs-9" ><input name="Cli_Fac" type="text" class="form-control input-xs"/></div>
                </div>
            </fieldset>
            <fieldset class="exa-fieldset" >
                <legend class="Titulos2">Datos de Ubicación</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Ciudad:</label>  
                    <div class="col-xs-4" >
                        <?php $rs_ciudad = $obBD_con1->getArrayConsulta(6, '', $obBD_conexion); ?>
                        <select name="Ciu_Cod" class="form-control input-xs" required="" >
                            <option value=""></option>
                            <?php  foreach($rs_ciudad as $row){ echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>"; } ?>
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
                <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            </div>
            <div class="Titulos2"><hr><b>NOTA:</b> Los campos marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
        </form>
         
    </div>  

<script>
// DIALOG create cliente
$('#clieCreateDialog').createDialog({icon:'plus', width:500, height:460});

// guardar un proveedor
function guardaProvee(){            
    $.saveDataJson("",$('#clieCreateForm').getData('guardaClieAjax'), function( resp ){ selectCliente(resp['clie']); $('#clieCreateDialog').dialog('close'); return false; });
 }

function habilitar(op,val){
    var lon_ced=$('#Prs_Ced').val().length; $('#Prs_Ced').fieldValid('');
    if(op==='ec'){
        $('#Ide_Cod').find('option').show();
        $('#Ide_Cod').attr('disabled',true);
        $('#Ide_Cod').val(lon_ced===10?2:1);
    }else{
        $('#Ide_Cod').find('option').hide().end().find('option[data-tipo="Ex"]').show();
        $('#Ide_Cod').val(val);
        $('#Ide_Cod').attr('disabled',false);
    }
}

var err=0;
function validar(op){
    var cedula=$('#Prs_Ced').val();
    switch(op){
        case 1:
            if(validaNoIdentif(cedula)['success']){  err=0; $('#Ide_Cod').val(cedula.length===10?2:1); $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ec'); }else{ err=1; $('#Ide_Cod').val(''); $('#Prs_Ced').fieldValid(false,validaNoIdentif(cedula)['message']); }
            break;
        case 2:
            if(cedula.length===13 && validaNoIdentif(cedula)['success']){ err=0; $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ec');}else{ err=1; $('#Ide_Cod').val(1); $('#Prs_Ced').fieldValid(false,validaNoIdentif(cedula)['message']); }
            break;
        case 3:
            err=0;
            $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ex');
            break;
    }
}

function validaNoIdentif(number){
    var digitos = number.split(""), dto=digitos.length, acu=0, resp={success:false,message:''}, 
    coef={'NA':[2,1,2,1,2,1,2,1,2],'PU':[3,2,7,6,5,4,3,2,0],'PR':[4,3,2,7,6,5,4,3,2]}, modulo, acum=0;
    if(dto===0) resp['message']='No has ingresado ning\u00fan dato!'; 
    else{
     for(var i=0; i<dto; i++) if(!isNaN(digitos[i])){ digitos[i]=digitos[i]*1; acu = acu+1; }
     if(acu===dto){
      var tipo = digitos[2];
      if (tipo===7||tipo===8) resp['message']='"El tercer d\u00edgito ingresado es inv\u00e1lido"'; else{ tipo=(tipo<6?'NA':(tipo===6?'PU':(tipo===9?'PR':''))); modulo=(tipo==='NA'?10:11); resp['tipo_abrev']=tipo; resp['tipo']=(tipo==='NA'?'Natural':(tipo==='PR'?'Privada':(tipo==='PU'?'P\u00fablica':''))); }
          if(dto!==10&&dto!==13){ resp['message']='La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; }else{ resp['doc_abr']=(dto===10?'C':(dto===13?'R':'')); resp['doc']=(dto===10?'C\u00E9dula':(dto===13?'R.U.C.':'')); }   
          if(number.substring(0,2)*1>24) resp['message']='Los dos primeros d\u00EDgitos no pueden ser mayores a 24.';   
          if(dto===13){
                  if(number.substring(10,13)!=='001') resp['message']='Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.';   
                  if(tipo==='PU'&&number.substring(9,13)!=='0001') resp['message']='El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
          }else if((tipo==='PU'||tipo==='PR')) resp['message']='El R.U.C. de las empresas '+resp['tipo']+'s deben tener 13 digitos!';
          if(resp['message'].length>0) return resp; 

          for(var a=0;a<9;a++){
                  var resul=digitos[a]*coef[tipo][a];
                  acum+=(resul-(tipo==='NA'&&resul>=10?9:0));
          } 
          var residuo=acum%modulo, digitoVerificador = residuo===0 ? 0: modulo - residuo;
          if(digitos[(tipo==='PU'?8:9)]!==digitoVerificador) resp['message'] = 'El n\u00famero de '+resp['doc']+' de la '+(tipo==='NA'?'Persona Natural':'Empresa '+resp['tipo'])+' ingresado es inv\u00E1lido!';

          if(resp['message'].length===0) resp['success']=true;
     }else resp['message']="ERROR: Solo debe contener d\u00EDgitos!";
    }
    return resp;
}

function clear(){
    $('#clieCreateForm').setData({Cli_Tic:'N',Prs_Ciu:'Ec',Prs_Sex:'M'});
    $('#Prs_Ced').val('').focus();
    $('.juridico').hide();$('.natural').show();
}

function searchCliente(ced,tipo){
    (tipo==='ec')?ced=ced.substring(0,10):ced;
    $.post("",{searchCliente:true,Prs_Ced:ced}, function(response){
        if(response['existe']===true){
            $.alert('El cliente '+ced+' ya se encuentra registrado..!!');
            clear();
        }else{
            $('#Ciu_Cod').val(response['Ciu_Cod']).trigger('chosen:updated');
            $.extend(response,{Prs_Ced:$('#Prs_Ced').val(),Ide_Cod:$('#Ide_Cod').val()});
            $('#clieCreateForm').setData(response,false);
        }
    },'json').fail(function (){$.alert();});
}

</script>
    <?php }else{ ?>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/info.tabs.css" />
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Documentos de Venta</h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body"> 
            <div class="row">                   
                <div class="col-xs-3"></div>
                <div class="col-xs-6 vcenter center" style="height:300px;margin-bottom: 25px;">
                    <div>
                        <?php echo error_alerta($error,2,true); ?>                        
                    </div>    
                </div>
            </div>   
        </div>
    </div>    
    <?php } ?>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script> 
    <script>$.clearValidate();</script>    
    <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script> 
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
    <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />    
</BODY>
</HTML>