<?php
/**
 * @abstract Permite realizar el registro de un proceso de facturaci�n de viajes
 * @author Erick Cordova
 * @version 1.0
 * Fecha de creaci�n  2017-07-25
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

if(isset($doc_xml)){   
    header('Location: '."../FRONT/$Ses_Emp_Cod/{$doc_xml}_A.xml");
}

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_facturaVenta;
//borrar debug completo
//$obBD_con1->echoLog($obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion));
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

if(isset($getDateServ)){
    $resp['hoy']=date("Y-m-d");
    $obBD_con1->echoJson($resp);
}
if(isset($Doc_Cod)){
    require_once('../LOGICA/fac_log_electronica.php');
    
    $obBD_elect =  new Class_Log_Datos_NCredito_Elect();
    $obBD_elect->createPdf($Doc_Cod, $obBD_conexion);
}
//Secci�n para listar los clientes registrados en la empresa
if(isset($clieAjax)){
    $obBD_con1->getPageGridJson(1, $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);    
}
/* ver si exite un cliente */
if(isset($provAjax2)){
    $responce['rows'] = $obBD_con1->getArrayConsulta(2, $Prs_Ced.'*'.$Ses_Emp_Cod, $obBD_conexion);
    $responce['total'] = count($responce['rows']);
    $responce['success']=true;
    $obBD_con1->echoJson($responce);    
}
/* guarda un nuevo cliente */
if(isset($guardaClieAjax)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion);
        if(empty($Prs_Cod)){
            $obBD_con1->operacionobBD(3,$data,$obBD_conexion);
            $data['Prs_Cod'] = $obBD_con1->insercionid ($obBD_conexion);
        }
        $obBD_con1->operacionobBD(4,$data,$obBD_conexion);
        $data['Cli_Cod'] = $obBD_con1->insercionid ($obBD_conexion);
        $data['cliente'] = trim($data['Prs_Ape'].' '.$data['Prs_Nom']);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if($obBD_con1->Error==0) {$responce=array(success=>true,clie=>$data);} else {$responce=array(success=>false,message=>'No se pudo realizar la transacci&oacute;n!',error=>$obBD_con1->MsgError);}
    utf8_encode_deep($responce); echo json_encode($responce);exit();
}

//Secci�n para extraer el Pun_Cod y Vnd_Cod del usuario sobre la tabla vendedor
$rs_Punto = $obBD_con1->getRowConsulta(7,$Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);

//Secci�n para obtener el n�mero de secuencia
if(isset($numeroSec)){
    $response=$obBD_con1->getRowConsulta(9,$Ses_Prs_Cod.'*'.$Ses_Suc_Cod.'*'.$Tic_Cod.'*'.$Aut_Cod,$obBD_conexion);
    if(isset($Aut_Sri)) $response['Aut_Sri']=$Aut_Sri;    
    $siguiente=$obBD_con1->getRowConsulta(10,$response['Aut_Ini'].'*'.$response['Aut_Fin'].'*'.$response['Aut_Sri'].'*'.$Tic_Cod.'*'.$Ses_Suc_Cod.'*'.$Pun_Sri,$obBD_conexion);
    $response['Vet_Num']=$siguiente['siguiente'];
    $response['contador']=$siguiente['contador'];
    echo json_encode($response);
    exit();
}

if(isset($getForCod)){
    $resp['data']=$obBD_con1->getArrayConsulta(89,'', $obBD_conexion);
    $resp['succes']=true;
    $obBD_con1->echoJson($resp);
    
}

//Secci�n para comprobar si el n�mero de secuencia ya se encuentra registado
if(isset($existeNumdoc)){
    $rs_numdocumento=$obBD_con1->getRowConsulta(11,$Ses_Suc_Cod.'*'.$Aut_Sri.'*'.$Vet_Num.'**'.$Pun_Sri,$obBD_conexion);
    if($rs_numdocumento['total']*1>0){$response['existe']=true;}else{$response['existe']=false;}
    echo json_encode($response);
    exit();
}

/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(12, $Ses_Emp_Cod,$obBD_conexion);
$vendedor = $obBD_con1->getRowConsulta(85,$Ses_Suc_Cod.'*'.$Ses_Prs_Cod,$obBD_conexion);
/* Consulta los productos */
if(isset($proAjax)){
    if(!empty($Caj_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(78,$Ses_Emp_Cod.'*'.$Caj_Fec,$obBD_conexion); else $Pec_Cop=array('Pla_Cod'=>null);
    $contar = $obBD_con1->getRowConsulta(13, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    if($contar['total']>0){
        $responce['rows'] = $obBD_con1->getArrayConsulta(13, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
        foreach ($responce['rows'] AS &$r){
            $r['Precios']=$obBD_con1->getArrayConsulta(14, $Ses_Suc_Cod.'*'.$r['Pro_Cod'].'*'.'A', $obBD_conexion);
            $precio = $obBD_con1->getRowConsulta(14, $Ses_Suc_Cod.'*'.$r['Pro_Cod'].'*'.'A'.'*'.'D'.'*', $obBD_conexion);
            if(!empty($precio['Pre_Pvp'])){
                $r=array_merge($r,$precio);
                $r['Vet_Pru']=$r['Pre_Pvp'];
            }
            if($configs['Cof_Con']=='S'&&!empty($Pla_Cod)){
                $cuenta = $obBD_con1->getRowConsulta(15,$Pla_Cod.'*'.$r['Pro_Cod'].'*'.'V', $obBD_conexion);
                if(!empty($cuenta['Pld_Cod'])) $r=array_merge($r,$cuenta);
            }
        }unset($r);
    }
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}

$ivas= $obBD_con1->getArrayConsulta(16,"",$obBD_conexion);      //Secci�n para obtener los ivas de la tabla iva
$bankos= $obBD_con1->getArrayConsulta(18,"",$obBD_conexion);    //Secci�n para obtener los bancos de la tabla bancos

if(isset($buscarCuentas)){
    $contado1=$obBD_con1->getArrayConsulta(19,$Pla_Cod.'*'.$Ses_Emp_Cod,$obBD_conexion);
    $contado2=$obBD_con1->getArrayConsulta(20,$Pla_Cod,$obBD_conexion);
    $contado=array_merge($contado2,$contado1);
    //$obBD_con1->echoLog($contado);
    $response['Contado']=$contado;
    $credito=$obBD_con1->getArrayConsulta(90,$Pla_Cod.'*'.'2',$obBD_conexion);
    $response['Credito']=$credito;
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

/* Consulta del codigo retencion */
if(isset($codiAjax)){
    $data=$_GET;
    $contar = $obBD_con1->getRowConsulta(21, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce=$pagination['data']; $data['limits']=$pagination['limits'];
    if($contar['total']>0){
        $responce['rows'] = $obBD_con1->getArrayConsulta(21, $data, $obBD_conexion);
        if($configs['Cof_Con']=='S'&&!empty($Pla_Cod)){
            foreach ($responce['rows'] AS &$r){
                $cuenta = $obBD_con1->getRowConsulta(22,$Pla_Cod.'*'.$r['Ren_Cod'].'*V', $obBD_conexion);
                if(!empty($cuenta['Pld_Cod'])) $r=array_merge($r,$cuenta);
            }unset($r);
        }
    }
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}

if(isset($autorizaAjax)){
    $obBD_con1->getPageGridJson(108, $rs_Punto['Pun_Cod'].'*'.$Tic_Cod, $obBD_conexion,$page, $rows);
}

if(isset($ventasAjax)){
    $obBD_con1->getPageGridJson(109, $Caja_Fecha.'*'.$Ses_Suc_Cod.'**'.$search, $obBD_conexion,$page, $rows);
}

if(isset($getDataPunto)){
    $resp = $obBD_con1->getRowConsulta(7,$Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
    $obBD_con1->echoJson($resp); 
}


if(isset($getValoresVenta)){
    $resp['Vet_Total'] = $obBD_con1->getRowConsulta(112,$Vet_Cod, $obBD_conexion);
    $resp['Vet_Abonos'] = $obBD_con1->getRowConsulta(115,$Vet_Cod.'*'.$Com_Asoc, $obBD_conexion);
    $resp=array_merge($resp['Vet_Total'],$resp['Vet_Abonos']);
    $resp['pagos']=$obBD_con1->getArrayConsulta(92,$Vet_Cod,$obBD_conexion);
    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}


if(isset($cargarDoc)){
    $responce=$obBD_con1->getRowConsulta(91, $vet_cod, $obBD_conexion);
    $responce['Bod_Cod']=$obBD_con1->getRowConsulta(154, $vet_cod, $obBD_conexion);
    $responce['items']=$obBD_con1->getArrayConsulta(93, $vet_cod, $obBD_conexion);
    foreach ($responce['items'] as $r) if($r['Iva_Por']*1>0){ $responce['Iva_Cod']=$r['Iva_Cod']; break; }
    $responce['pagos']=$obBD_con1->getArrayConsulta(92,$vet_cod,$obBD_conexion);
    if($Aut_Cod=='')$Aut_Cod=0;
    if($Tic_Cod=='')$Tic_Cod=0;
    $array_documentos=$obBD_con1->getArrayConsulta(37,$rs_Punto['Pun_Cod'].'*'.$Aut_Cod,$obBD_conexion);
    if($Tic_Cod>0){
        $array_doc=$obBD_con1->getArrayConsulta(116,$rs_Punto['Pun_Cod'].'*'.$Aut_Cod.'*'.$Tic_Cod,$obBD_conexion);
        $array_documentos=array_merge($array_documentos, $array_doc);
    }
    
    $responce['ventas1']=$obBD_con1->getArrayConsulta(121,$vet_asoc_num.'*'.$vet_asoc_tic_cod.'*'.$vet_asoc_suc_sri.'*'.$vet_asoc_pun_sri,$obBD_conexion, true);
    $responce['ventas2']=$obBD_con1->getArrayConsulta(117,$Com_Cod.'*'.$vet_asoc_num,$obBD_conexion);
    
    $responce['ventas']= array_merge( $responce['ventas1'],$responce['ventas2']);
    
    $responce['documentos']=$array_documentos;
    //$obBD_con1->echoLog($responce['documentos']);
    $responce['success']=true;
    $obBD_con1->echoJson($responce);
}


if(isset($searchDocument)){	
    $data=$_GET; $data['Emp_Cod']=$Ses_Emp_Cod;
    $data['Tic_Cod']=$data['Tic_Cod_Search'];
    $responce=$obBD_con1->getPageGrid(143, $data, $obBD_conexion); 
    if($responce['total']>0){     
        foreach($responce['rows'] AS &$row){
            $row['Cpc_Edit']='S';
            $row['Cpc_Min']=0;
            if(!empty($row['Cpc_Cod'])){
                $Pagos1=$obBD_con1->getRowConsulta(57, $row['Cpc_Cod'].'*'.'A', $obBD_conexion);
                if($Pagos1['total']*1>0){ 
                    $row['Cpc_Det']='S'; //tiene pagos activos
                    $Pagos1=$obBD_con1->getRowConsulta(57, $row['Cpc_Cod'].'*'.'A'.'*'.'SUM', $obBD_conexion);
                    $row['Cpc_Min']=round($Pagos1['total']*1, 2);
                }
                $Pagos2=$obBD_con1->getRowConsulta(57, $row['Cpc_Cod'], $obBD_conexion);                
                if($Pagos2['total']*1>0) $row['Cpc_Edit']='N'; //tiene algun pago vinculado
            }
            if($configs['Cof_Con']=='S'&&!empty($row['Com_Cod'])){
                $cuentas = $obBD_con1->getRowConsulta(39, $row['Com_Cod'], $obBD_conexion);
                $row['Pld_Cod_Pag']=$cuentas['Pld_Cod'];
                $otras_comp = $obBD_con1->getRowConsulta(65, $row['Com_Cod'], $obBD_conexion);
                if($otras_comp['total']*1>1) $row['Com_Edit']='N'; 
            }
        //$obBD_con1->echoLog($row);
        }unset($row);
    }
    $obBD_con1->echoJson($responce);
}

if(isset($cargarDocumentos)){
    if($Aut_Cod=='')$Aut_Cod=0;
    if($Tic_Cod=='')$Tic_Cod=0;
    $array_documentos=$obBD_con1->getArrayConsulta(8,$rs_Punto['Pun_Cod'].'*'.$Aut_Cod.'*1',$obBD_conexion);
    if($Tic_Cod>0){
        $array_doc=$obBD_con1->getArrayConsulta(101,$rs_Punto['Pun_Cod'].'*'.$Aut_Cod.'*'.$Tic_Cod.'*1',$obBD_conexion);
        $array_documentos=array_merge($array_documentos, $array_doc);
    }
    echo json_encode($array_documentos);
    exit();
}


if(isset($docDetalle)){
    $resp['Vet_items']=$obBD_con1->getArrayConsulta(93,$Vet_Cod, $obBD_conexion);
    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}
if(isset($cargarReportes)){
    try {
        $response['reportes'] = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        $response['success']=true;
    } catch (Exception $ex) {
        $response['message']=$ex->getMessage();
    }
    $obBD_con1->echoJson($response);
}

/* Secci�n para realizar el guardado */
if(isset($saveDocument)){
	$obBD_con1->validaCierrePeriodo('ventas','Caj_Fec','Vet_Cod',$Caj_Fec,$Vet_Cod,$obBD_conexion);
    /* Creacion de Objetos de Conexiones para Proceso de Guardado de Venta*/
    $obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_facturaVenta;
/*Habilita Debuger de SQLs en Proceso de Guardado de Venta*/
    //$obBD_conIns->debug(true);
/*Inicio de Transaccion*/    
    $obBD_conIns->inicio_transaccion($obBD_conexionIns);
/*Verifica usuario tenga Permisos de Vendedor*/
    if(empty($vendedor['Vnd_Cod'])){
      $responce['message']="No tiene permisos de Vendedor!";
    }
    $Vnd_Cod=$vendedor['Vnd_Cod'];
    if(is_string($items)) $items=json_decode(stripslashes($items),true);

    try{
        //venta (nota-debito/credito) a editar
        //$row_vet_old = $obBD_con1->getRowConsulta(118, $Vet_Cod, $obBD_conexion,true);
        
        //Seccion para verificar si la caja ya fue aperturada
        $rs_Caja = $obBD_con1->getRowConsulta(76,$rs_Punto['Pun_Cod'].'*'.$Caj_Fec, $obBD_conexion);
		
        if(empty($rs_Caja['Caj_Cod'])){
            //Secci�n para aperturar la caja a trav�s de insert a la tabla caja_aper
            $obBD_conIns->operacionobBD(77,$rs_Punto['Pun_Cod'].'*'.$Caj_Fec, $obBD_conexionIns);
            //Secci�n para obtener el id ingresado en la tabla caja_aper
            $Caj_Cod=$obBD_conIns->insercionid($obBD_conexionIns);
        }else{
            $Caj_Cod=$rs_Caja['Caj_Cod'];
        }

        /* valida que no exista el documento */
        $num_existe_gencod=$obBD_con1->getRowConsulta(50, $Ses_Suc_Cod.'*'.$Aut_Sri.'*'.$Vet_Num.'*'.$Vet_Cod.'*'.$documento['punsri'], $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI
        if($num_existe_gencod['total']*1>0) {
          $responce['message']="El doc. $Tic_Des No. $Vet_Num ya existe!";
        }
       
        $claveAcceso=NULL;
        if($Aut_Tem=='E'){ $Vet_Aut='N';
            require_once('../LOGICA/fac_log_electronica.php');
            if($Tic_Sri==4){
				$obBD_elect =  new Class_Log_Datos_NCredito_Elect();
			}
			if($Tic_Sri==5){
				$obBD_elect =  new Class_Log_Datos_NDebito_Elect();	
			}
            $claveAcceso=$obBD_elect->getClaveAcceso($Aut_Cod, $Caj_Fec, $Vet_Num, $obBD_conexion);
            if(empty($claveAcceso)) $responce['message']="Error al generar <u>Clave de Acceso</u> del <i>Comprobante Electr&oacute;nico</i>!";
        }


        if(!empty($responce['message'])){
            echo json_encode($responce);exit(); 
        }

        
        /* Actualizar cabecera del documento de venta*/
        $obBD_conIns->operacionobBD(119, $Tic_Cod.'*'.$Cli_Cod.'*'.$Ciu_Cod.'*'.$Caj_Cod.'*'.$rs_Punto['Vnd_Cod'].'*'.
		    $Vet_Num.'*'.$Vet_Obs.'*'.$documento['autcod'].'*'.$Vet_Des.'*'.$hora.'*'.$claveAcceso.'*'.$ventas[0]['Vet_Num_Asoc'].'*'.$ventas[0]['Tic_Cod'].'*'.$ventas[0]['Caj_Fec'].'*'.$Vet_Cod, $obBD_conexionIns);
        $cod_pro_unique=array(); 
        
        /* Borrando detalles de cuentas por cobrar*/
        if($Com_Cod*1>0){
            $obBD_conIns->operacionobBD(120, $Com_Cod, $obBD_conexionIns);   

        }
        
        /*/* para eliminar el kardex anterior */        
            $row_kard_old = $obBD_con1->getArrayConsulta(43, $Vet_Cod, $obBD_conexion);
            $obBD_Stock =  new Class_Log_Datos_facturaVenta;
            $obBD_conexionStock = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
            $obBD_Stock->inicio_transaccion($obBD_conexionStock);  
            
            foreach ($row_kard_old as $row){ 
                $row['IoE']='E';
                $row['Kar_Sal']=$row['Kar_Can']*-1;
                $row['Kar_Pre']=$row['kar_Pre']*1;
                $row['Kar_Ime']=$row['Kar_Ime']*-1;
                $obBD_Stock->updateStockProd($Ses_Suc_Cod, $row, false, $obBD_conexion,$obBD_conexionStock); //revierte el stock
            }
            $obBD_Stock->fin_transaccion_nomsn($obBD_conexionStock);
            if($obBD_Stock->Error!=0) throw new Exception('Error al limpiar los antiguos valores del <u>KARDEX</u>!');               
            //$obBD_Stock->echoLog('borrando kardex_ie');
            $obBD_conIns->operacionobBD(44, $Vet_Cod, $obBD_conexionIns); // limpia el kardex
        
        
        //Eliminando items de Documento
        $obBD_conIns->operacionobBD(97, $Vet_Cod, $obBD_conexionIns);        
        
        /* Inserta datos en el detalle de la venta */
        $kardex=array('IoE'=>'E', 'Kar_Fec'=>$hoy, 'Kar_Hor'=>date("H:i:s"), 'Vet_Cod'=>$Vet_Cod, 'Vnd_Cod'=>$Vnd_Cod);
        
         
        
        $array_kardex=array(); $s_add=true;
        foreach ($items as $i => $item){
            $item['Vet_Cod'] = $Vet_Cod;
            $item['Vet_Ite'] = $i+1;
            //if($rise) {$item['Iva_Cod']=$iva_cero['Iva_Cod'];}
            /* Item Documento */
            $obBD_conIns->operacionobBD(86,$item, $obBD_conexionIns);
            
            /* Control de Inventarios */
            $s_add=true;
            foreach ($array_kardex as &$k){
                if($k['Pro_Cod']==$item['Pro_Cod']){
                    $s_add=false;
                    $k['Kar_Sal']+=(1)*$item['Vet_Can'];
                    $k['Kar_Ime']+=(1)*$item['Vet_Imp'];
                    $k['Kar_Pre']=$k['Kar_Ime']/$k['Kar_Sal'];
                    break;
                }
            } unset($k);
            if($s_add==true && $Cal_Inv){
                $kardexIE=array_merge($kardex, array(
                    'Kar_Int'=>$i+1, 'Iva_Cod'=>$item['Iva_Cod'], 'Pro_Cod'=>$item['Pro_Cod'],
                    'Kar_Sal'=>(1)*$item['Vet_Can']*(($documento['ticsri']*1===4)?-1:1),//$obBD_conIns->CantidadStock($item['Pro_Cod'],$items),
                    'Kar_Pre'=>$item['Vet_Pru']*1,
                    'Kar_Ime'=>(1)*$item['Vet_Imp']*(($documento['ticsri']*1===4)?-1:1)
                    //'Kar_Rep'=>(in_array($item['Pro_Cod'],$cod_pro_unique)?true:false),
                    //'Kar_Max'=>$obBD_conIns->CantidadStock($item['Pro_Cod'],$items),
                ));
                array_push($array_kardex, $kardexIE);
            }            
        }

            if($documento['ticsri']*1!=0 && $Cal_Inv){
                foreach ($array_kardex as $k){
                    //$obBD_conIns->echoLog('actualizando cardex');
                //array_push($cod_pro_unique, $item['Pro_Cod']);
                    $obBD_conIns->updateStockProd($Ses_Suc_Cod, $k, true, $obBD_conexion,$obBD_conexionIns,$Bod_Cod);
                }
            }
        

         
         
         /* Creacion del comprobante contable */
        if($configs['Cof_Con']=='S'&&$documento['ticsri']*1!=0){
            $Com_Con = 'REG. VENTA '.$Vet_Num; $Com_Fec=$Caj_Fec;
            $Tia_Asi = $obBD_con1->getRowConsulta(80, 7, $obBD_conexion);
            $meseCom = explode('-', $Com_Fec);
            $Com_Num= $obBD_con1->codigoComprAuto($Tia_Asi['Tia_Cod'], $Pec_Cod, $meseCom[1], $obBD_conexion); // Secuencia de comprobante por mes y por tipo
            $campo='Cli_Cod';
            /* Cabecera del Comprobante */
            $obBD_conIns->operacionobBD(70, $Pec_Cod.'*'.$Cli_Cod.'*'.$Com_Num.'*'.$Com_Fec.'*'.trim($Com_Con).'*'.$Tia_Asi['Tia_Cod'].'*'.$t_rubros.'*'.trim($Vet_Obs).'*'.$campo.'*'.$Com_Cod, $obBD_conexionIns);
            if(empty($Com_Cod)||($Com_Cod*1)<=0){
                $Com_Cod = $obBD_conIns->insercionid ($obBD_conexionIns->conexion);
                $obBD_conIns->operacionobBD(83, $Com_Cod.'*'.$Vet_Cod, $obBD_conexionIns); // relacion venta comprobante
            }else {$obBD_conIns->operacionobBD(41,$Com_Cod , $obBD_conexionIns);} // Elimina el asiento anterior
            
            /* Inserta datos en el detalle del asiento (por items) */
            foreach ($items as &$item){
                if($documento['ticsri']*1===4){ //si nota de Credito
                    $cuenta = $obBD_con1->getRowConsulta(28,$Plan_Cod.'*'.($Cal_Inv==true?'VDV':'DV'), $obBD_conexion); 
                }else{                          //caso contrario nota de debito
                    $cuenta = $obBD_con1->getRowConsulta(84,$Plan_Cod.'*'.$item['Pro_Cod'].'*'.'V', $obBD_conexion);
                }
                if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del producto: <u>'.$item['Ite_Lar'].'</u>!');
                $item['Pld_Cod']=$cuenta['Pld_Cod'];
                $obBD_conIns->operacionobBD(87, $Com_Cod.'*'.(($documento['ticsri']*1===4)?'D':'H').'*'.($item['Vet_Imp']).'*'.$cuenta['Pld_Des'].'*'.$item['Ite_Lar'].'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Item
            } unset($item);
            /* IVA */
            
            if($t_iva*1>0){
                $cuenta = $obBD_con1->getRowConsulta(88,$Plan_Cod, $obBD_conexion);
                if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Iva Cobrado</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod.'*'.(($documento['ticsri']*1===4)?'D':'H').'*'.$t_iva.'*'.'IVA'.'*'.'IVA'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Iva
            }
            /* DESCUENTO */
           
            if($Vet_Des>0){
                $cuenta = $obBD_con1->getRowConsulta(28,$Plan_Cod.'*'.'DV', $obBD_conexion);
                if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Descuentos en Ventas</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod.'*'.(($documento['ticsri']*1===4)?'H':'D').'*'.$t_descuento.'*'.'DESCUENTO'.'*'.'DESCUENTO'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // descuento
            }
            
            if($t_ice*1>0){
                $cuenta = $obBD_con1->getRowConsulta(28,$Plan_Cod.'*'.'ICV', $obBD_conexion);
                if(!isset($cuenta['Pld_Cod'])&&empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>ICE en Ventas</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod.'*'.(($documento['ticsri']*1===4)?'D':'H').'*'.$t_ice.'*'.'ICE'.'*'.'ICE'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE
            }
    
            
            /* Pago */
            /* pagos a las ventas asociadas */
            $nota_valor=$t_rubros;
            foreach ($ventas as $venta){
                if($t_rubros<$venta['Vet_Saldo']|| $documento['ticsri']*1===5){
                    $venta['Vet_Saldo']=$t_rubros;
                }
                if($t_rubros>0){
                    $obBD_conIns->operacionobBD(87, $Com_Cod.'*'.(($documento['ticsri']*1===4)?'H':'D').'*'.$venta['Vet_Saldo'].'*'.$Vet_Num.'*'.('Doc.'.$venta['Vet_Num']).'*'.$Pld_Cod_Not, $obBD_conexionIns);
                    $t_rubros=$t_rubros-$venta['Vet_Saldo'];
                    // inserta asiento // Iva
                    /* CCPP Cuentas por Cobrar */ //ojo por ahora sigue dependiendo de contabilidad
                    if($venta['For_Cod']*1==2 && $For_Cod_Nota['For_Cod']*1==2 ){
                        $obBD_conIns->operacionobBD(113, $Com_Cod.'*1*'.$Caj_Fec.'*'.(($documento['ticsri']*1===4)?$venta['Vet_Saldo']:$venta['Vet_Saldo']*-1).'*'.'"Nota C/D"'.'*'.$venta['Cpc_Cod'], $obBD_conexionIns);
                        $Cpc_Cod = $obBD_conIns->insercionid ($obBD_conexionIns);
                    }
                }
                
            }
           
        }
             
        
        

    } catch (Exception $ex) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
        $responce['message']=$ex->getMessage();
        echo json_encode($responce); exit();
    }

    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns);
    if ($obBD_conIns->Error == 0) {
        // $obBD_con1->debug(true);
        $reportes = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        //$obBD_con1->echoLog($reportes);
        //$obBD_con1->echoLog($reportes);
        $response=array('success'=>true,'Vet_Impr'=>"".(!empty($reportes[1])?"$reportes[1]?Vet_Cod=":"")."$Vet_Cod",
        'Vet_Cod'=>$Vet_Cod, 'Vet_Num'=>$Vet_Num, 'Vet_Fec'=>$Caj_Fec,'Tic_Des'=>($documento['ticsri']*1===5?'NOTA DE DEBITO':'NOTA DE CREDITO'));
        
        if($Aut_Tem=='E'){
                $rs_infoEmpresa = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
                $rs_infoCliente = $obBD_con1->getRowConsulta(61, $Aut_Cod, $obBD_conexion);
                if($Tic_Sri==4){
					$responce['xml']= $obBD_elect->createXmlNCredito($Vet_Cod, $Aut_Cod, $claveAcceso, $obBD_conexion);
				}
				if($Tic_Sri==5){
					$responce['xml']= $obBD_elect->createXmlNDebito($Vet_Cod, $Aut_Cod, $claveAcceso, $obBD_conexion);
				}
                $responce['Vet_Xmls']=baseUrl("../FRONT/".$Ses_Emp_Cod.'/'.$claveAcceso.'.xml');
                //$obBD_con1->echoLog("archivoXML: ".$responce['Vet_Xmls']);
                //$meseVet = explode('-', $Caj_Fec);
                //$datoElect=array('{Emp_Nom}'=>$Ses_Emp_Nom, '{Tic_Des}'=>$Tic_Des, '{Prs_Ced}'=>$Prs_Ced, '{proveedor}'=>$cliente, '{Prs_Cor}'=>$Prs_Cor, '{claveAcceso}'=>$claveAcceso, '{fecha}'=>$meseVet[2].' de '.mes($meseVet[1],1).' '.$meseVet[0], '{secuencia}'=>$rs_infoEmpresa["Suc_Sri"].'-'.$rs_infoCliente["Pun_Sri"].'-'.str_pad($Vet_Num, 9, "0", STR_PAD_LEFT));
                //$responce['mail']=$obBD_con1->sendMailDoc($datoElect, reporteHtml($datoElect,'fac_pri_ret_ele.html'));
				//$responce['mail']=$obBD_elect->sendMailDoc($Vet_Cod,$Prs_Cor,NULL,$obBD_conexion);
            }
        
        if(!empty($Vet_Cod)){
            $response['Vet_Data']=array('Tic_Des'=>($documento['ticsri']*1===5?'5.-NOTA DE DEBITO':'4.-NOTA DE CREDITO'),'cliente'=>$cliente,'Vet_Num'=>$Vet_Num,'Vet_Fec'=>$Caj_Fec,'Vet_Aut'=>$Aut_Sri);
            $response['Vet_Rows']=$obBD_con1->getArrayConsulta(79, $Vet_Cod, $obBD_conexion);
            $response['Vet_Link']="".(!empty($reportes[1])?"$reportes[1]?Vet_Cod=":"")."$Vet_Cod";
        }
        if (!empty($Com_Cod)) {
            $response['Com_Data']=array('Codigo'=>$Com_Cod,'Tia_Des'=>$Tia_Asi['Tia_Des'],'Com_Con'=>$Vet_Obs,'Com_Fec'=>$Caj_Fec,'Com_Val'=>$t_rubros);
            $response['Com_Rows']=$obBD_con1->getArrayConsulta(27,$Com_Cod,$obBD_conexion);
            $response['Com_Link']="".(!empty($reportes[2])?"$reportes[2]?codigo=":"")."$Com_Cod";
        }
        if(isset($rets)){
            $response['Ret_Cod']=$Ret_Cod;
            $response['Ret_Data']=array('Ret_Num'=>$Ret_Num,'Aut_Sri'=>$Ret_Aut_Sri,'Ret_Fec'=>$Ret_Fec,'Ren_Tot'=>$Ren_Tot,'Iva_Ren_Tot'=>$Iva_Ren_Tot,'Ret_Ren_Tot'=>$Ret_Ren_Tot);
            $response['Ret_Rows']=$rets;
            
        }
   
    }
    else{
        $response=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_conIns->MsgError);
    }
    echo json_encode($response);
    exit();
}


if(isset($get_documentos)){
    $resp['data'] = $obBD_con1->getArrayConsulta(33, '', $obBD_conexion);
    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}



?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "NCredi.Vent. Modificar [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script language="javascript" src="../VALIDACIONES/fac_val_factura.js?a=9"></script>
        
        <script>
            var Nota_CreDeb=true,Mod_Nota_CreDeb=true;
            inicializarDocVenta();
            //setTimeout(function(){ $("#Pec_Cod").trigger('change'); }, 1000);   
            var docs, items,edicion_ventas=true, pagos,doc_ventas, data=[],vet_num_ant=0,tic_cod_ant=0, Vet_Index=1, Vet_Selected, index, Cof_Con='<?php echo $configs['Cof_Con']; ?>';
                <?php $array_documentos=$obBD_con1->getArrayConsulta(107,$rs_Punto['Pun_Cod'],$obBD_conexion);?>
            var array_documentos=<?php echo json_encode($array_documentos);?>,Conf_Con=Cof_Con, ivas_venta=<?php echo json_encode($ivas)?>;
        
        </script>
        <style>
            .ui-jqgrid td input, .ui-jqgrid td select, .ui-jqgrid td textarea {padding-top: 2px;}
            .footrow td[aria-describedby="documento_Vet_Imp"],.footrow td[aria-describedby="documento_Vet_Pru"]{padding: 0 !important;}
            .footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact input[type=text]{ padding: 0; }
            .footerFact textarea{text-align: left; height: 75px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}
            #jqGridButtonDiv{float:right; padding-right:10px; position:relative; top:-1px;}
            #Ret_Asu{ 
                vertical-align: middle; margin-top: -2px; padding: 5px;  -ms-transform: scale(1.4); -moz-transform: scale(1.4); -webkit-transform: scale(1.4); -o-transform: scale(1.4); 
            }
            #resultContent .resp{
                font-weight: 700; font-size: 30px; color: #3f3fc1; padding: 0; margin: 0; overflow: hidden; text-overflow: ellipsis; height: 32px;
            }
            #resultContent .resp span:first-child{
                color:darkgoldenrod;width: 100px;display: inline-block; margin-left: 42px;
            }
            .msg_fly 
            {
                font-size: 12px !important;
            }
            
            
            .ret .input-group-btn button{padding: 1px 2px !important;}
            .ret{ padding: 0 !important;}
        </style>
    </HEAD>
    <BODY>
        
        
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Notas de Cr&eacute;dito/D&eacute;bito</h3><p id="cabeceraPuntoImp" class="text-right col-xs-12  " style="margin-top:-15px;">punto de impresion</p></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="documentoSearch">
                <form id="serachDocDorm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#serachDocDorm','searchDocument');" >
                    <div class="row">
                        <input name="order" type="hidden" value="" />
                        <input name="fecha_inicio" type="hidden" value="" />
                        <input name="fecha_fin" type="hidden" value="" />

                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&uacute;squeda</legend>
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
                                    <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus  class="form-control input-sm clearable submit"/>
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
                                        <select name="Tic_Cod_Search" id="Tic_Cod_Search" class="form-control input-xs">
                                        </select>
                                    </div> 
                                </div> 
                                <div class="form-group">
                                    <label class="col-xs-4 control-label label-xs">Periodo:</label>  
                                    <div class="col-xs-3" >
                                        <select name="Pec_Cod" class="form-control input-xs search_pec" onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled');">
                                            <option value=""><< TODOS >></option>
                                            <?php   $rs_perio=$obBD_con1->getArrayConsulta(5,$Ses_Emp_Cod, $obBD_conexion);
                                                                foreach ($rs_perio as $row){?>
                                                                <option value="<?php echo $row['Pec_Cod'];?>" data-inicio="<?php echo $row['Pec_Fei'];?>" data-fin="<?php echo $row['Pec_Fef'];?>" ><?php echo $row['Anio'];?></option>
                                            <?php   }?>
                                
                                        </select>
                            
                                    </div> 
                                    <label class="col-xs-2 control-label label-xs">Mes:</label>  
                                    <div class="col-xs-3" >
                                        <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec" disabled="disabled">
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
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop green"></span> Contiene Pagos | <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="fa fa-globe green"></span> Retenci&oacute;n Electronica Validada | <span class="glyphicon glyphicon-lock orange"></span> Formato Anterior</div>
                </div>
                <script>
                    function setOpt(val){ if(val==='d') $('.search_pec').attr('disabled','disabled'); else $('.search_pec').removeAttr('disabled'); }
                        var vet_asoc_num=0,vet_asoc_tic_cod=0,vet_asoc_suc_sri=0,vet_asoc_pun_sri=0;
                    function cargarDoc(doc){
                        
                        if(!$.isEmptyObject(doc['Vet_Nns'])){
                            var array_asoc=doc['Vet_Nns'].split('-');
                            vet_asoc_num=array_asoc[2]*1;/*1*/
                            vet_asoc_tic_cod=doc['Vet_Ntd'];/*2*/
                            vet_asoc_suc_sri=array_asoc[0];/*3*/
                            vet_asoc_pun_sri=array_asoc[1];/*4*/
                        }else{
                             var vet_asoc_num=0,vet_asoc_tic_cod=0,vet_asoc_suc_sri=0,vet_asoc_pun_sri=0;
                        }
                        doc_ventas.clearGridData();
                        items.clearGridData();
                        doc_ventas.trigger('reloadGrid');
                        items.trigger('reloadGrid');
                        vet_num_ant=doc['Vet_Num'];
                        tic_cod_ant=doc['Tic_Cod'];
                        editDoc=true;
                        AutCod=doc['Aut_Cod'];
                        TicCod=doc['Tic_Cod'];
                        $('#editDoc').setData({});
                       // $('#Pec_Cod').attr('disabled',true);
                        $('#Tpc_Cod').val(doc['Tpc_Cod']*1);
  
                        $.getDataJson('',{'vet_asoc_num':vet_asoc_num,'vet_asoc_tic_cod':vet_asoc_tic_cod,'vet_asoc_suc_sri':vet_asoc_suc_sri,'vet_asoc_pun_sri':vet_asoc_pun_sri,'cargarDoc':true,'vet_cod':doc['Vet_Cod'],'Com_Cod':doc['Com_Cod'],'Aut_Cod':AutCod,'Tic_Cod':TicCod},function(resp){
                                $('#clieFormTemp').setData({'Prs_Ced':doc['Prs_Ced']});
                                $.SearchOrDialog('#clieDialog',selectCliente);
                                if(doc['Pec_Cod']){
                                    $('#Pec_Cod').val(doc['Pec_Cod']);
                                }else{
                                    var periodo_selec = doc['Vet_Fec'].split("-")[0];
                                    $("#Pec_Cod").find('option:contains("'+periodo_selec+'")').prop('selected', true);
                                }
                                //$('#Pec_Cod').trigger('change');
                                var sel_fecha=$("#Pec_Cod").find('option:selected');
                                $('#Caj_Fec').dateLimits(sel_fecha.data('inicio'),sel_fecha.data('fin'));
                                $('.placod').val(sel_fecha.data('placod'));
                                $('#Caj_Fec').val(doc['Vet_Fec']);
                                
                                $("textarea[name=Vet_Obs]").val(doc['Vet_Obs']);
                                $('#docuFormTemp').setData({'Vet_Cod':doc['Vet_Cod'],'Com_Cod':doc['Com_Cod']},false);
                                items.jqGrid('delRowData',1);
                                $.each(resp['items'],function(x,item){
                                     addItem(item,item['Vet_Can'],item['Vet_Pru']);
                                });
                                
                                aBorrar=addItem({});
                                var aCobrar=$('#Val_Pcc_2').val()*1;
                                if(resp['Iva_Cod']>0)$('#Iva_Cod').val(resp['Iva_Cod']);
                                //updateDocument();
                                items.jqGrid('delRowData',aBorrar);
                                $('#Ret_Fec').val(doc['Ret_Fec']);
                                var botones_pagos=$('#pagosPager_left').find('td.btn-success');
                                var btn_pagos_activos=$('.porCobrar').find('span.input-group-btn');
                                if((doc['Cpc_Min']*1)<=0){
                                    btn_pagos_activos.addClass('hidden');
                                    botones_pagos.removeClass('hidden');
                                    pago_min=0;
                                }else{
                                    pago_min=doc['Cpc_Min']*1;
                                    btn_pagos_activos.removeClass('hidden');
                                    btn_pagos_activos.createFlyout('Posee pagos activos por <i class="glyphicon glyphicon-usd">'+pago_min+'</i> !',{icon:'exclamation',placement:'left_top'});                              
                                    btn_pagos_activos.flyout('show').focus();
                                    botones_pagos.not(botones_pagos.find('span.glyphicon-credit-card').parent().parent()).addClass('hidden');
                                }
                                var html;
                                html+='<option value="">Seleccione...</option>';
                                $.each(resp['documentos'],function(i,v){
                                    if(doc['Vet_Fec']>=v['Aut_Fci'] && doc['Vet_Fec']<=v['Aut_Cad']){
                                        html+='<option value='+v['Tic_Cod']+' data-ticcod='+v['Tic_Cod']+' data-ticsri='+v['Tic_Sri']+' data-puncod='+v['Pun_Cod']+' data-autcod='+v['Aut_Cod']+' data-autsri='+v['Aut_Sri']+' data-auttem='+v['Aut_Tem']+' data-autima='+v['Aut_Ima']+' data-punsri='+v['Pun_Sri']+' data-sucsri='+v['Suc_Sri']+' data-autini='+v['Aut_Ini']+' data-autfin='+v['Aut_Fin']+' data-autfci='+v['Aut_Fci']+' data-ticdes="'+v['Tic_Des']+'" data-autcad='+v['Aut_Cad']+'>'+v['Tic_Sri']+' - '+v['Tic_Des']+'</option>';
                                    }
                                    
                                });
                                
                                
                                $.each(resp['ventas'],function(index,val){
                                    index===0?$('#For_Cod_Nota').val(val['For_Cod']).trigger('change'):'';
                                    Com_Asoc=doc['Com_Cod'];
                                    selectVent(val);
                                });
                                
                                $("#Bod_Cod").val(resp['Bod_Cod'].Bod_Cod);
                                
                                $('#Tic_Cod').html(html);
                                $('#Tic_Cod').val(doc['Tic_Cod']).trigger('change');
                                $('#Vet_Des').val(doc['Vet_Des']).trigger('change');
                                $('#t_descuento').val($('#t_subtotal').val()*$('#Vet_Des').val()*1/100).trigger('change');
                                $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
                                $('#documentoMain').removeClass('hidden');
                                
                            }); 
                            
                    } 
                        $('#searchGrid').createGrid({
                            caption:'Resultado de la B&uacute;squeda',height: 270, datatype: "local",caption:'Resultados <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="order by caja_aper.Caj_Fec DESC ">Fecha Venta</option><option value="order by Vet_Num DESC ">Num. Documento</option></select>&nbsp;</div>',
                            colModel: [  
                                { label: 'C&oacute;d. Int.', name: 'Vet_Cod', width: 30 ,align:"center", key:true},  
                                { label: 'Compr.', name: 'Com_Exi', width: 20 ,align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Tiene Comprobante',noMsg:' '}, title:false},
                                { label: 'Pago', name: 'Pago', width: 35, align:"center"},
                                //{ label: 'P. SRI', name: 'Tpc_Sri', width: 20, align:"center", formatter:'title', formatoptions:{title:function(o){return o['Tpc_Des'];}}, title:false },
                                { label: 'Tipo Documento', name: 'Tic_Des', width: 100 },
                                { label: 'No. Documento', name: 'Vet_Num', width: 90, align:"center" },                                
                                { label: 'Fecha', name: 'Vet_Fec', width: 45,align:"center"},
                                { label: 'Cliente', name: 'cliente_per', width: 150},             
                                { label: 'Estado', name: 'Vet_Est', width: 20,align:"center", formatter:'estado', title:false },
                                { label: '&nbsp;', name: 'act2', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{ action:ImpDoc, title:'Imprimir Documento', icon:'print', type:'info' }, title:false },
                                { label: '&nbsp;', name: 'act0', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{ action:viewInfo, title:'Ver Documento', icon:'info-sign', type:'info' }, title:false },
                                
                                { label: 'XML', name: 'act01', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{ action:viewXml, title:'Ver XML', icon:'paperclip', type:'info', conditional:function(o){ return o.Vet_Est!=='I'&&o.Vet_Aut==='S'; }, caseFalse:function(o){ return o.Vet_Est!=='I'&&!$.isEmpty(o.Vet_Xml)?$.createIcon('info-sign orange',null,'title="PENDIENTE"'):''; } }, title:false },
                                { label: 'PDF', name: 'act02', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{ action:viewPdf, title:'Ver PDF', icon:'file', type:'info', conditional:function(o){ return o.Vet_Est!=='I'&&!$.isEmpty(o.Vet_Xml); } }, title:false },
                                
                                { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'edicion', title:false }
                            ],                                    
                            loadComplete: function(data){   
                                if($.varValid(data.rows))
                                for(var i=0,z=data.rows.length;i<z;i++){                                    
                                    if(data.rows[i]['Vet_Est'] ==='I' || data.rows[i]['Vet_Est'] ==='E') $("#"+data.rows[i].Vet_Cod+' td:not(.jqgrid-rownum)').addClass('cellRed2');
                                    //if(data.rows[i]['Ret_Aut'] ==='S' || data.rows[i]['Rcc_Det'] ==='S' )  $("#"+data.rows[i].Vet_Cod+' td:not(.jqgrid-rownum)').addClass('cellBlue2');
                                    if(data.rows[i]['Cpc_Det'] ==='S' || data.rows[i]['Cpc_Edit'] ==='N' ) $("#"+data.rows[i].Vet_Cod+' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                                }
                            }
                        },false,'#searchGridPager',{refresh: true});
                        //$('.formDatos:').find(':input').removeAttr('readonly');
                        
                        $('#OrderBy').on('change',function(){ $('input[name=order]').val($(this).val()); $('#serachDocDorm').formSubmit(); });

                    
                    function viewInfo(doc){
                        $('#docDetaDialog').setData(doc); 
                        $('#RetenViewGrid')[$.varValid(doc['Com_Cod'])&&doc['Ret_Exi']==='S'?'show':'hide']();
                        $.post('',{'docDetalle':true,'Vet_Cod':doc['Vet_Cod'],'Com_Cod':doc['Com_Cod']},function(resp){                        
                            $('#detaDocu').setRows(resp['Vet_items']);
                            $.each(resp['Vet_items'],function(x,item){
                                     addItem(item,item['Vet_Can'],item['Vet_Pru']);
                            });
                            updateDocument();
                            $('#detaReten').setRows($('#retencion').getGridBatch());
                            $('#docDetaDialog').dialog('open').updateGridsSizes();
                        },'json').fail(function(){$.alert();});
                    }
                    
                    
                    
                </script>
            </div>
                
                <div id="documentoMain" class="hidden">
                    <div class="row">
                        <div class="col-xs-12" id="panelVentas" >
                            <div class="row">
                              <div id="pagosDialog" title="Agregar Pagos">
                                  <form id="pagosForm" class="form-horizontal normal" action="javascript:addPago($('#pagosForm').getData());">
                                      <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs required">Forma:</label>
                                          <div class="col-xs-6" >
                                              <?php $rs_forma = $obBD_con1->getArrayConsulta(89, '', $obBD_conexion); ?>
                                              <select id="For_Cod" name="For_Cod" class="form-control input-xs readOnly" data-trigger="" required="">
                                                  <option value="">Seleccione...</option>
                                                 <?php foreach($rs_forma as $row){
                                                     echo "<option value='$row[For_Cod]'  ".($row['For_Des']=='Contado'?"selected=''":'').">$row[For_Des]</option>";
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
                                                     if(!endsWith(strtoupper(trim($row['Pag_Des'])),'PAGAR')&&!startsWith(strtoupper(trim($row['Pag_Des'])),'CRUCE')) echo "<option value='$row[Pag_Cod]' data-forcod='$row[For_Cod]' ".(strtoupper(trim($row['Pag_Des']))=='CHEQUE'?'disabled=""':'').">$row[Pag_Des]</option>";
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
                                      <!-- bancos en la base de datos -->
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
                                        <!-- cuentas bancaria -->
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
                                          <label class="col-xs-3 control-label label-xs required">N&uacute;mero:</label>
                                          <div class="col-xs-6">
                                              <div class="input-group input-group-xs">
                                                  <input type="text" id="Vet_Che" name="Vet_Che" onchange="" class="form-control input-xs">
                                                  <span class="input-group-addon validate"><i class="glyphicon glyphicon-ok green"></i></span>
                                              </div>
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
                                          <label class="col-xs-3 control-label label-xs">Observaci&oacute;n:</label>
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
                                <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaNotaCreDeb();">

                                    <!--ivas-->
                                    <select id="Def_Ivas" name="Def_Ivas" class="form-control input-xs" style="display: none;">
                                        <?php
                                            $temp=array();
                                            foreach ($ivas AS $row){
                                                if (!in_array($row['Iva_Por'], $temp)) {
                                                    echo '<option value="'.$row['Iva_Cod'].'" data-ivapor="'.$row['Iva_Por'].'" data-ivaini='.$row['Iva_Ini'].' data-ivafin='.$row['Iva_Fin'].' >'.$row['Iva_Por'].' %</option>';
                                                }
                                                array_push($temp, $row['Iva_Por']);
                                            }
                                            
                                        ?>
                                    </select>

                                    <!--tipos_pago-->
                                    <select id="pag_cod" name="pag_cod" class="form-control input-xs" style="display: none;">
                                        <?php foreach ($tipospago as $row){?><option value="<?php echo $row['Pag_Cod'];?>" data-forcod="<?php echo $row['For_Cod'];?>"><?php echo mb_convert_encoding($row['Pag_Des'], 'ISO-8859-1', 'UTF-8');?></option><?php }?>
                                    </select>

                                    <!--bancos-->
                                    <select id="bak_cod" name="bak_cod" class="form-control input-xs" style="display: none;">
                                        <?php foreach ($bankos as $row){?><option value="<?php echo $row['Bak_Cod'];?>"><?php echo mb_convert_encoding($row['Bak_Des'], 'ISO-8859-1', 'UTF-8');?></option><?php }?>
                                    </select>

                                    <!--cuentas contado=1, credito=2-->
                                    <select id="pld_cod" name="pld_cod" class="form-control input-xs" style="display: none;"></select>

                                    <div class="col-md-5 col-xs-12">
                                        <fieldset class="exa-fieldset" id="clieFormTemp">
                                            <legend class="Titulos2">Datos del Cliente</legend>
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs required">C&eacute;dula/RUC:</label>
                                                <div class="col-xs-7" >
                
                                                  <input name="Prs_Cod" type="text" style="display:none;" />
                                                  <input name="Prs_Cor" type="text" style="display:none;" />
                                                  <input name="Cli_Cod" type="text" style="display:none;" />
                                                  <input name="op_opciones" type="text" value="c" style="display: none;">
                                                  <div class="input-group input-group-xs">
                                                      <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#clieDialog',selectCliente);" type="text" placeholder="Ingrese Cliente..."  class="form-control input-xs datatrigger clearable dialogSearch" tabindex="1" required="" />
                                                    <span class="input-group-btn">
                                                        <button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                        <button type="button" onclick="$('#clieCreateForm').setData({}).find('.validate').find('i').removeAttr('class'); $('#clieCreateDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Cliente"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>
                                                    </span>
                                                  </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs">Cliente:</label>
                                                <div class="col-xs-10" ><span name="cliente" class="form-control input-xs databind datatitle"></span></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs">Direcci&oacute;n:</label>
                                                <div class="col-xs-4" ><span name="Prs_Dir" type="text" class="form-control input-xs databind datatitle"></span></div>
                                                <label class="col-xs-1 control-label label-xs">Correo:</label>
                                                <div class="col-xs-5" ><span name="Prs_Cor" type="text" class="form-control input-xs databind datatitle"></span></div>
                                            </div>
                                        
                                        </fieldset>
                                    </div>
                                    <div class="col-md-7 col-xs-12">
                                        <fieldset class="exa-fieldset" id="docuFormTemp">
                                            <legend class="Titulos2">Datos del Documento</legend>
                                            <input type="text" name="Vet_Cod" style="display: none;" />
                                            <input type="text" name="Com_Cod" style="display: none;" />
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs">Periodo:</label>
                                                <div class="col-xs-2" >
                                                    <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs">
                                                        <?php   $rs_perio=$obBD_con1->getArrayConsulta(5,$Ses_Emp_Cod, $obBD_conexion);
                                                                foreach ($rs_perio as $row){?>
                                                                <option value="<?php echo $row['Pec_Cod'];?>" data-inicio="<?php echo $row['Pec_Fei'];?>" data-fin="<?php echo $row['Pec_Fef'];?>" data-PlaCod="<?php echo $row['Pla_Cod'];?>"><?php echo $row['Anio'];?></option>
                                                        <?php   }?>
                                                    </select>
                                                </div>
                                                <label class="col-xs-1 control-label label-xs">Fecha:</label>
                                                <div class="col-xs-3" >
                                                    <input type="text" id="Caj_Fec" name="Caj_Fec" class="form-control input-xs datepickers">
                                                </div>
                                                <label class="col-xs-1 control-label label-xs">Ciudad:</label>
                                                <div class="col-xs-3" >
                                                    <?php $Ciu_Des=$obBD_con1->getRowConsulta(6,$Ses_Usu_Cod, $obBD_conexion);?>
                                                    <input type="hidden" id="Ciu_Cod" name="Ciu_Cod" value="<?php echo $Ciu_Des['Ciu_Cod']?>">
                                                    <span name="Ciu_Des" class="form-control input-xs"><?php echo $Ciu_Des['Ciu_Des']?></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs required">Docum.:</label>
                                                <div class="col-xs-6">
                                                    <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs" required="" ></select>
                                                </div>

                                                <label class="col-xs-1 control-label label-xs">Aut.:</label>
                                                <div class="col-xs-3">
                                                    <div class="col-xs-12 input-group input-group-xs" >
                                                        <span id="Aut_Sri" name="Aut_Sri" class="form-control input-xs databind"></span>
                                                        <span id="cambiarAut"  class="btn btn-block btn-success input-group-addon " title="Cambiar de Autorizacion">
                                                            <i class="glyphicon glyphicon-transfer white"></i>
                                                        </span>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs required">N&uacute;mero:</label>
                                                <div class="col-xs-5" >
                                                    <div class="input-group input-group-xs">
                                                        <span id="Pun_Sri" name="Pun_Sri" class="input-group-addon alert-info"></span>
                                                        <input type="text" id="Vet_Num" name="Vet_Num" onchange="validarTic_Cod()" class="form-control input-xs trigger" tabindex="5" required="" data-container="body" data-toggle="popover"/>
                                                        <span class="input-group-addon validate" ><i></i></span>
                                                    </div>
                                                </div>
                                                <label id="mensajeAutorizacion" class="col-xs-5 control-label label-xs  red"></label>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div class="col-md-7 col-xs-12">
                                        <fieldset class="exa-fieldset" id="docuFormTemp">
                                            <legend class="Titulos2">Ventas Asociadas</legend>
                                            <table id="ventas"></table>
                                            <div id="ventas1Pager"></div>
                                        </fieldset>
                                    </div>
                                    <div class="col-md-5 col-xs-12">
                                        <fieldset class="exa-fieldset" id="docuFormTemp">
                                            <legend class="Titulos2">Pago</legend>
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs required">Forma:</label>
                                                <div class="col-xs-3" >
                                                    <select id="For_Cod_Nota" name="For_Cod_Nota" class="form-control input-xs " data-trigger=""  disabled="disabled" required="">
                                                        <option value="">Seleccione...</option>
                                                    </select>
                                                </div>
                                                <?php if($configs['Cof_Con']=='S'){ ?>
                                                <label class="col-xs-2 control-label label-xs required">Cuenta:</label>
                                                <div class="col-xs-5">
                                                    <select id="Pag_Pld_Nota" name="Pag_Pld_Nota" class="form-control input-xs readOnly" required="" disabled="disabled"></select>
                                                </div>
                                                <?php } ?>
                                            </div>
                                            <div class="form-group pagoCredito hidden">
                                                <label class="col-xs-2 control-label label-xs required">Vencimiento:</label>
                                                <div class="col-xs-3">
                                                    <input id="Cpp_Ven" name="Cpp_Ven" type="text" class="form-control input-xs datepickers" />
                                                </div>
                                                <label class="col-xs-2 control-label label-xs">Observaci&oacute;n:</label>
                                                <div class="col-xs-5">
                                                    <textarea name="Cpp_Obs" class="form-control input-xs"></textarea>
                                                </div>
                                            </div>
                                        </fieldset>

                                         <?php $bodegas = $obBD_con1->getArrayConsulta('bodega.1',array('Suc_Cod'=>$Ses_Suc_Cod,'Usu_Cod'=>$Ses_Usu_Cod), $obBD_conexion);?>

                                        <fieldset class="exa-fieldset" <?php if( count($bodegas)==0) echo 'style="display:none; "'; ?> >
                                            <legend class="Titulos2"></legend>                               
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs">Bodega:</label>  
                                                <div class="col-xs-10" >                                        
                                                    <select id="Bod_Cod" name="Bod_Cod" class="form-control input-xs">
                                                        <?php if(count($bodegas)>0) foreach($bodegas as $row){ echo "<option value='$row[Bod_Cod]'>$row[Bod_Nom]</option>"; } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </fieldset> 
                                    </div>
                                </form>
                                <div class="col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
                                    <table id="items"></table>
                                    <div id="itemsPager"></div>
                                </div>
                                <div class="col-md-7 col-xs-12 hidden">
                                    <form id="reteFormTemp" action="javascript:" class="formDatos form-horizontal normal">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Datos de la Retenci&oacute;n</legend>
                                        <input type="text" name="Ret_Cod" style="display: none;" id="Ret_Cod" />
                                        <input type="text" name="Ret_Xml" style="display: none;"  />
                                        <input type="text" name="Aut_Cod" style="display: none;" id="Aut_Cod_Old" />
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs ">Numero:</label>
                                            <div class="col-xs-4" >
                                                <input type="text" name="Aut_Tem" style="display: none;" />
                                                <div class="input-group input-group-xs">
                                                    <input id="Ret_Num" name="Ret_Num" type="text" class="form-control input-xs ret_field" />
                                                    <span class="input-group-addon validate"><i></i></span>
                                                </div>
                                            </div>
                                            

                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs ">Autoriza:</label>
                                            <div class="col-xs-4" >
                                                <input name="Ret_Aut_Sri" class="form-control input-xs ret_field"/>
                                            </div>
                                            <label class="col-xs-2 control-label label-xs required">Fecha:</label>
                                            <div class="col-xs-4">
                                              <div class="input-group">
                                                  <input id="Ret_Fec" name="Ret_Fec" type="text" class="form-control input-xs readOnly ret_field datepickers"  required=""  pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                                                  <span class="input-group-addon input-xs" title="Fecha de la Retenci&oacute;n"><i class="glyphicon glyphicon-info-sign blue"></i></span>
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
                                                    <span class="input-group-btn">
                                                        <button type="button" onclick="$('#retDetaDialog').dialog('open')" class="btn btn-info" title="Ver Detalle Retenci&oacute;n" tabindex="-1"><span class="glyphicon glyphicon-eye-open"></span></button>
                                                    </span>
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
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                    </form>
                                </div>

                                <div class="col-md-5 col-xs-12">
                                    <form id="pagoFormTemp" action="javascript:"  class="formDatos form-horizontal normal hidden">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Forma de Pago</legend>
                                        <input type="text" name="Cpc_Cod" style="display: none;" />
                                        <div class="form-group pagoSri" >
                                            <label class="col-xs-3 control-label label-xs required">Pago&nbsp;SRI:</label>
                                            <div class="col-xs-9"  >
                                                <?php $rs_pag_sri = $obBD_con1->getArrayConsulta(45, '', $obBD_conexion); ?>
                                                <select id="Tpc_Cod" name="Tpc_Cod" defaultValue=1 class="form-control input-xs readOnly" required="" onchange="">
                                                    <option value="">Seleccione...</option>
                                                   <?php foreach($rs_pag_sri as $row){
                                                     $selected='';
                                                      if ($row[Tpc_Sri]==1) {
                                                        $selected='Selected';
                                                      }
                                                       echo "<option value='$row[Tpc_Cod]' ".$selected."  >$row[Tpc_Sri] - $row[Tpc_Des]</option>";
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group porCobrar">
                                            <label class="col-xs-3 control-label label-xs"></label>
                                            <div class="col-xs-9">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-addon bold alert-warning">Por Cobrar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i></span>
                                                    <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                                                    <input id="Val_Pcc_2" name="Val_Pcc_2" type="text" class="form-control bold span" style="text-align: right;font-size: 15px; background-color: white;" readonly="" tabindex="-1">
                                                </div>
                                            </div>
                                        </div>

                                    </fieldset>
                                    </form>
                                </div>
                            </div>
                            <div class="row center-block">
                                <div class="col-md-7 col-xs-12">
                                    <div class="condensed hidden" style="min-height: 100px; padding-bottom: 5px;">
                                        <table id="pagos"></table>
                                        <div id="pagosPager"></div>
                                    </div>
                                    <div>
                                        <button class="black btn btn-sm btn-inverse" onclick="clearDocument();$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();" ><i class="glyphicon glyphicon-arrow-left"></i>Atr&aacute;s</button>
                                        <button class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                    </div>
                </div>
                <div id="documentoResult" class="form-horizontal normal" style="visibility: hidden;">
                <div class="row">
                    <div class="col-xs-6" id="resultContent">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Resultado De la Transacci&oacute;n</legend>
                            <div>
                                <h4 style="text-align: center; font-weight: 900;">El Documento se guardo con &Eacute;xito!</h4>
                                <p class="form-control-static resp" data-name="Tic_Des"></p>         
                                <p class="resp"><span>&raquo;Fec:</span><span style="color:coral;" class="databind" data-name="Vet_Fec"></span></p>
                                <p class="resp"><span>&raquo;Num:</span><span style="color:teal;" class="databind" data-name="Vet_Num"></span></p>
                                <p class="resp"><span>&raquo;Cod:</span><span style="color: #CE0000;" class="databind" data-name="Vet_Cod"></span></p>
                                <div style="padding-top: 15px; text-align: center;">
                                    <button class="btn btn-sm btn-success" onclick="clearDocument();$('#searchGrid').trigger('reloadGrid');$('#documentoResult').moveComp('#documentoSearch').updateGridsSizes();" ><i class="glyphicon glyphicon-search"></i> Buscar Documento</button>
                                    <button class="btn btn-sm btn-success" name="Vet_Impr" id="Vet_Impr" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Documento</button>
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
                                <div class="col-xs-3"><span name="Vet_Fec" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Numero:</label>
                                <div class="col-xs-4"><span name="Vet_Num" type="text" class="form-control input-xs "></span></div>
                                <label class="col-xs-2 control-label label-xs">Autorizaci&oacute;n:</label>
                                <div class="col-xs-3"><span name="Vet_Aut" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Cliente:</label>
                                <div class="col-xs-9"><span name="cliente" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <table id="copresult"></table>
                        </fieldset>
                        <script>
                            
                        </script>
                    </div>
                    
                    
                    
                </div>
                <div class="row">
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset" id="retForm">
                            <legend class="Titulos2">Datos de la Retenci&oacute;n</legend>
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
                                <label class="col-xs-3 control-label label-xs">C&oacute;d. Comp.:</label>  
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
                                <label class="col-xs-3 control-label label-xs">Observaci&oacute;n:</label>  
                                <div class="col-xs-9"><span name="Com_Con" type="text" class="form-control input-xs "></span></div>  
                            </div>    
                            <table id="asiento"></table>
                        </fiedset>    
                        <script>
                            $('#asiento').createGrid({                                                        
                                height:75,postData: {CheListAjax:true},caption:'Asiento Contable <button id="btnComPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>',
                                rowNum: 10000, footerrow: true, userDataOnFooter: true,
                                colModel: [
                                    { label: 'C&oacute;d.Int.', name: 'Asi_Cod', key: true, width: 15,align:"center", hidden:true },  
                                    { label: 'Tipo', name: 'Asi_Deh', hidden:true },
                                    { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 45 },                      
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

        <!-- Inicio del di�logo para buscar clientes -->
        <div id="clieDialog" title="B&uacute;squeda de Cliente"><form class="form-horizontal normal"> </form></div>
        <script>
            //Dialog buscar clientes
            $.createSearchDialog('clieDialog',[
                { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15,align:"center",hidden:true },
                { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Cliente', name: 'cliente', width: 100},
                { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
                { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectCliente} }
            ],null,null,null,{headertitles:true},{ title:'Cliente', text:'Prs_Ced' });
            function selectCliente(cliente){
                $('#clieFormTemp').setData($.extend(cliente,{op_opciones:'c'}));
                $('#clieDialog').dialog('close');
            }
        </script>
        
    <div id="docDetaDialog" title="Documento">
        <fieldset class="exa-fieldset" >
            <legend class="Titulos2">Documento:</legend>
            <div class="form-horizontal normal" style="padding: 0 4px;">
            <div class="form-group">
                <label class="col-xs-2 control-label label-xs">C&eacute;dula/RUC:</label>  
                <div class="col-xs-4" ><span name="Prs_Ced"  class="form-control input-xs"></span></div>
                <label class="col-xs-2 control-label label-xs">Doc.Num.:</label>  
                <div class="col-xs-4" ><span name="Vet_Num"  class="form-control input-xs"></span></div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label label-xs">Cliente:</label>  
                <div class="col-xs-6" ><span name="cliente_per"  class="form-control input-xs"></span></div>
                <label class="col-xs-1 control-label label-xs">Fecha:</label>  
                <div class="col-xs-3" ><span name="Vet_Fec"  class="form-control input-xs"></span></div>
            </div>    
            <div class="form-group condensed">
                <div class="col-xs-12"><div class="pull-right"><table id="detaDocu"></table></div></div>
                <div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;"><b>CREACI&oacute;N:</b> <span name="Vet_Sys" class="databind"></span> &nbsp;&nbsp;-&nbsp;&nbsp; <b>USUARIO:</b> <span name="vendedor_per" class="databind"></span></div>
            </div> 
            </div>    
        </fieldset>        
        <fieldset class="exa-fieldset" id="RetenViewGrid" >
            <legend class="Titulos2">Retencion:</legend>
            <div class="form-horizontal normal" style="padding: 0 4px;">
            <div class="form-group">
                <label class="col-xs-2 control-label label-xs">Numero.:</label>  
                <div class="col-xs-3" ><span name="Ret_Num"  class="form-control input-xs"></span></div>
                <label class="col-xs-1 control-label label-xs">Fecha:</label>  
                <div class="col-xs-3" ><span name="Ret_Fec"  class="form-control input-xs"></span></div>
                <label class="col-xs-2 control-label label-xs">Autorizaci&oacute;n.:</label>  
                <div class="col-xs-1" ><span name="Ret_Aut"  class="form-control input-xs"></span></div>
            </div>     
            <div class="form-group condensed">  
                <div class="col-xs-12"><div class="pull-right"><table id="detaReten"></table></div></div>
            </div> 
            </div>    
        </fieldset>        
    </div>
        
        
        
        
        <!-- Inicio del di�logo para registrar clientes -->
        <div id="clieCreateDialog" title="Registrar Cliente" style="display:none;">
            <form class="form-horizontal normal" id="clieCreateForm" action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaCliente(); }else{ $('#Prs_Ced').flyout('show').focus() }">
                <input name="Prs_Cod" type="text" class="hidden" />
                <fieldset class="exa-fieldset" >
                    <legend class="Titulos2">Datos del Cliente</legend>
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs required">C&eacute;dula/RUC:</label>
                        <div class="col-xs-5" >
                            <div class="input-group input-group-xs">
                                <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="if(validaNoIdentif(this.value)['success']){ $('#Ide_Cod').val(this.value.length===10?2:1); $('#Cli_Tic').val(validaNoIdentif(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchCliente(this.value); }else{ $('#Ide_Cod').val(''); $('#Cli_Tic').val(''); $(this).fieldValid(false,validaNoIdentif(this.value)['message']); };" required="" />
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
                            <select id="Cli_Tic" name="Cli_Tic" class="form-control input-xs" required="" onchange="if(this.value==='N'){ $('.juridico').hide();$('.natural').show(); }else{ $('.natural').hide();$('.juridico').show(); }">
                                <option value = "N" >NATURAL</option>
                                <option value = "J" >JURIDICO</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Raz&oacute;n Social:</span></label>
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
                    <legend class="Titulos2">Datos de Ubicaci&oacute;n</legend>
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                        <div class="col-xs-4" >
                            <?php $rs_ciudad = $obBD_con1->getArrayConsulta(81, '', $obBD_conexion); var_dfum?>
                            <select name="Ciu_Cod" class="form-control input-xs" required="" >
                                <option value=""></option>
                                <?php  foreach($rs_ciudad as $row){ echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>"; } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs required">Direcci&oacute;n:</label>
                        <div class="col-xs-9" ><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs required">Tel&eacute;fono:</label>
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
            
            var opts={                                                        
                height:75, postData: {CheListAjax:true},caption:'Detalle Venta',                
                colModel: [
                    { label: 'C&oacute;d.Int.', name: 'Vet_Int', key: true, width: 15,align:"center", hidden:true },                                     
                    { label: 'Cantidad ', name: 'Vet_Can', width: 45, align: 'right' },                      
                    { label: 'Item', name: 'Ite_Lar', width: 130  },
                    { label: 'P. Unit.', name: 'Vet_Pru', width: 65, align: 'right'},
                    { label: 'Importe', name: 'Vet_Imp', width: 65, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"}
                ]       
            };
            $('#detaDocu').createGrid($.extend(opts,{height:'auto',width:550,responsive:false,caption:null,rownumbers:false}),true);
            
            
            
            // DIALOG create cliente
            $('#clieCreateDialog').createDialog({icon:'plus', width:500, height:430});
            $('#For_Cod').val(1).trigger('change');
        </script>
        <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
        <div id="codiDialog" title="B&uacute;squeda de C&oacute;digos Retenci&oacute;n">
            <form class="form-horizontal normal"><input type="text" name="Pla_Cod" class="placod" style="display: none;"/>
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
                { label: 'C&oacute;d.Int.', name: 'Ren_Cod', key: true, width: 25,align:"center" },
                { label: 'C&oacute;digo', name: 'Ren_Sri', width: 25, align:"center" },
                { label: 'Descripci&oacute;n', name: 'Ren_Con', width: 100 },
                { label: 'Porc.(%)', name: 'Ren_Por', width: 25,align:"center" },
                { label: 'Adq.', name: 'Ren_Tipo', width: 30,align:"center" },
                { label: 'Tipo', name: 'Ren_Rete', width: 30,align:"center"},
                { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton',
                formatoptions:{action:agregaRetencion,
                  conditional:function(o){
                    return !(Cof_Con==='S'&&(!$.varValid(o['Pld_Cod'])||o['Pld_Cod']===''));
                },
                caseFalse:function(){ return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>';
                    }
                  }
                }
            ],null,null,null,null,{ title:'B&uacute;squeda', options:[] });
            
            $('#documentoMain').css('visibility','').hide();
            $('#documentoResult').css('visibility','').hide();

        </script>


        <!-- Inicio de di�logo para buscar un producto -->
        <div id="proDialog" title="B&uacute;squeda de Productos">
            <form class="form-horizontal normal">
                <input type="text" name="Pla_Cod" class="placod" style="display: none;" />
            </form>
        </div>
        <script>
            // Dialog para buscar productos
           $.createSearchDialog('proDialog',[
            { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20,align:"center",hidden:false },                                
            { label: 'Descripci&oacute;n', name: 'Ite_Lar', width: 110 },                      
            { label: 'Marca', name: 'Mar_Des', width: 40},            
            { label: 'Categoria', name: 'Cat_Des', width: 90,align:"center" },
            { label: 'IVA', name: 'Iva_Por', width: 20, align:"center",formatter:'truefalse', formatoptions:{yesMsg:'Grava IVA',noMsg:'No Grava IVA'}, title:false }, 
            { label: 'Adq.', name:'Adq_Cor', width:20, align:"center", formatter:'title',formatoptions:{title:function(o){return o['Adq_Des'];}} },
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectItem, conditional:function(o){ 
                return !(Cof_Con==='S'&&(!$.varValid(o['Pld_Cod'])||o['Pld_Cod']==='')); }, caseFalse:function(){ return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>'; } } }
        ],null,null,null,null,{ title:'Producto', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] });                 

        </script>
        
         <div id="autorizaDialog" title="B&uacute;squeda de Autorizaciones">
            <form class="form-horizontal normal" id="autorizaForm"> 
                <input type="text" name="Tic_Cod" class="hidden"/>
                <input type="text" name="Pun_Cod" class="hidden"/>
            </form>
        </div>
        
        <div id="ventasDialog" title="B&uacute;squeda de Ventas">
            <form class="form-horizontal normal" id="ventaForm"> 
                <input type="text" id="Forma_Cod" name="Forma_Cod" class="hidden"/>
                <input type="text" id="Caja_Fecha" name="Caja_Fecha" class="hidden"/>
            </form>
        </div>
        
        <!-- DIALOGO DETALLE RETENCION -->
        <div id="retDetaDialog" title="Retenci&oacute;n">
            <div class="condensed-header">
                <table id="retencion"></table>
            </div>
        </div>
        <script>
           

                var opts={
                    height:75,caption:'Detalle Retenci&oacute;n', sortable:true, sortname: 'Ren_Rete', sortorder: "desc", footerrow:true,
                    colModel: [
                        { label: 'C&oacute;d.Int.', name: 'Ren_Cod', key: true, width: 15, align:"center", hidden:true },
                        { label: 'C&oacute;d.Int.', name: 'Ren_Ret', width: 15, align:"center", hidden:true },
                        { label: 'Ret.', name: 'Ren_Rete', width: 15, align: 'center' },
                        { label: 'C&oacute;digo ', name: 'Ren_Sri', width: 15, align: 'center' },
                        { label: 'Descripci&oacute;n ', name: 'Ren_Con', width: 50 },
                        { label: 'Importe', name: 'Ren_Imp', width: 30, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"},
                        { label: 'Porc.(%)', name: 'Ren_Por', width: 20, align: 'right' },
                        { label: 'Retenci&oacute;n.', name: 'Ren_Val', width: 30, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"}
                    ],
                    loadComplete: function (){ $(this).setGridSummary(['Ren_Val'],{Ren_Por:"<div style='text-align:right;'>TOTAL:</div>"}); }
                };
                $('#reteresult').createGrid($.extend(opts,{caption:'Detalle Retenci&oacute;n <button id="btnRetPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right hidden" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button><button id="btnRetXml" onclick="window.open($(this).data(\'url\'));" class="btn btn-success btn-xs pull-right" style="margin-top: -2px; display:none; margin-right:2px; "><i class="glyphicon glyphicon-download-alt"></i> Descargar XML</button>'}),true);
                $('#reteresult').getFootRow(true);
                $('#retencion').createGrid($.extend(opts,{height:219,width:593,responsive:false,caption:'Detalle Retenci&oacute;n <button type="button" role="button" tabindex="-1" class="ui-button ui-widget ui-state-default ui-corner-all pull-right" title="Cerrar Ventana" onclick="$(\'#retDetaDialog\').dialog(\'close\')"><span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span></button>'}),true);
                $('#retencion').getFootRow(true);
                $('#detaRete').createGrid($.extend(opts,{height:'auto',width:550,responsive:false,caption:null,rownumbers:false}),true);
                $('#detaRete').getFootRow(true);
                $('#retDetaDialog').createDialog({height:293,width:600,noTitleStuff:false,noBorder:true,noOverflow:true,extraClass:'noMargin'});
                $('#docDetaDialog').createDialog({height:400,width:600,noTitleStuff:false,noBorder:true});
            
        </script>

        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <script>
    $('#Pec_Cod').trigger('change');         
    $.clearValidate();</script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    </BODY>
</HTML>
