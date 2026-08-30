<?php   
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_facturaVenta;

$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* Busqueda de Clientes */
if(isset($cliAjax)||isset($cliFactAjax)){
    $obBD_con1->getPageGridJson(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion,$page,$rows);    
}
/* Busqueda de Notas de Venta */
if(isset($ajaxND_Ventas)){ 
    $data=$_GET;
    $data["Suc_Cod"]=$Ses_Suc_Cod;  
    $obBD_con1->getPageGridJson(126, $data, $obBD_conexion);
    $responce['rows'] = $obBD_con1->getArrayConsulta(126,$data, $obBD_conexion);   
    $responce['records']=count($responce['rows']);
    $obBD_con1->echoJson($responce); 
}
$row_tipo_compr = $obBD_con1->getArrayConsulta(134, '', $obBD_conexion);    
foreach ($row_tipo_compr as $row)
    if($row['Tic_Sri']=='01')
    {$Tic_Cod=$row['Tic_Cod'];break;}
$row_rs_vendedor = $obBD_con1->getRowConsulta(124, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod,$obBD_conexion);
$rs_infoEmpresa = $obBD_con1->getRowConsulta(125, $Ses_Suc_Cod, $obBD_conexion);

/* Cargar la cuentas contables para pagos en efectivo */
if(isset($cuentas)){ 
    $responce=array('success'=>true, 'html'=>'');
    if($For_Cod == 1){
        $contado=$obBD_con1->getArrayConsulta(19,$Pla_Cod.'*'.$Ses_Emp_Cod,$obBD_conexion);
        foreach ($contado as $row)
        {
            $responce['html']=$responce['html'].'<option value="'.$row['Pld_Cod'].'">'.$row['Pld_Des'].'</option>';
        }    
    }
    else{
        $credito=$obBD_con1->getArrayConsulta(90,$Pla_Cod.'*'.'2',$obBD_conexion );
        foreach ($credito as $row)
        {
            $responce['html']=$responce['html'].'<option value="'.$row['Pld_Cod'].'">'.$row['Pld_Des'].'</option>';
        } 
    }
    $obBD_con1->echoJson($responce);    
}

if(isset($buscarCuentas)){
    $responce=array('success'=>true, 'html'=>'');
   if($For_Cod == 1){
        $contado1=$obBD_con1->getArrayConsulta(19,$Pla_Cod.'*'.$Ses_Emp_Cod,$obBD_conexion);
        $contado1=$obBD_con1->getArrayConsulta(19,$Pla_Cod.'*'.$Ses_Emp_Cod,$obBD_conexion);
        $contado2=$obBD_con1->getArrayConsulta(20,$Pla_Cod,$obBD_conexion );
        $contado=array_merge($contado2,$contado1);
        foreach ($contado as $row)
        {
            $responce['html']=$responce['html'].'<option value="'.$row['Pld_Cod'].'">'.$row['Pld_Des'].'</option>';
        }    
    }
    else{
        $credito=$obBD_con1->getArrayConsulta(90,$Pla_Cod.'*'.'2',$obBD_conexion );
        foreach ($credito as $row)
        {
            $responce['html']=$responce['html'].'<option value="'.$row['Pld_Cod'].'">'.$row['Pld_Des'].'</option>';
        } 
    }
    $obBD_con1->echoJson($responce); 
}

if(isset($tiposPago)){ 
    $responce=array('success'=>true, 'html'=>'');
    $tipos = $obBD_con1->getArrayConsulta(153, $For_Cod, $obBD_conexion);
    foreach ($tipos as $row){
        $responce['html']=$responce['html'].'<option value="'.$row['Pag_Cod'].'">'.$row['Pag_Des'].'</option>';
    } 
    $obBD_con1->echoJson($responce);    
}
/* Valida Numero de Factura */
if(isset($valVetNum)){ 
    $responce['Vet_Num']=$valVetNum*1;$responce['exist']=false;$responce['valid']=false;
    $row_rs_autorizaci = $obBD_con1->getRowConsulta(133, $Tic_Cod.'*'.$row_rs_vendedor['Pun_Cod'].'*'.$Caj_Fec, $obBD_conexion); 
//    foreach ($row_rs_autorizaci as $row) {
        $row_rs_buscaNumVenta= $obBD_con1->getRowConsulta(131, $row_rs_autorizaci['Aut_Sri'].'*'.$valVetNum,$obBD_conexion);
    $total_rs_buscaNumVenta=$row_rs_buscaNumVenta['Vet_Cod'] > 0? 1 : 0;
        if($total_rs_buscaNumVenta==1)
            {$responce['exist']=true;}
//    }
//    foreach ($row_rs_autorizaci as $row) {
        if($row_rs_autorizaci['Aut_Ini']*1<=$valVetNum && $row_rs_autorizaci['Aut_Fin']*1>=$valVetNum)
        {$responce['valid']=true;}
        else{$responce['message']='El rango esta entre <b>'.$row_rs_autorizaci['Aut_Ini'].'</b> y <b>'.$row_rs_autorizaci['Aut_Fin'].'</b>.';}
//    }
    $responce['success']=true;
    $obBD_con1->echoJson($responce);
}

//obtenemos el detalle de la nota de pedido
if(isset($getDetalleNota)){
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";

    $response['data'] = $obBD_con1->getArrayConsulta(79, $Vet_Cod, $obBD_conexion);
    if ($obBD_con1->Error == 0) 
    {
    $response['success'] = true;
    }
  $obBD_con1->echoJson($response);
  exit();
}

/* Guardar La Factuta*/
if(isset($saveForm)){

    /* Configuraciones de la Empresa */
    $configs = $obBD_con1->getRowConsulta(12, $Ses_Emp_Cod,$obBD_conexion);
    $vendedor = $obBD_con1->getRowConsulta(85,$Ses_Suc_Cod.'*'.$Ses_Prs_Cod,$obBD_conexion);
    $rs_Punto = $obBD_con1->getRowConsulta(7,$Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
    $row_rs_autorizaci = $obBD_con1->getRowConsulta(133, $Tic_Cod.'*'.$vendedor['Pun_Cod'].'*'.$Caj_Fec, $obBD_conexion);

    /* Creacion de Objetos de Conexiones para Proceso de Guardado de Venta*/
    $obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_facturaVenta;
    
    $Ret_Num=''; $Vet_Cod=''; $Ret_Aut_Sri=''; $Pec= explode('*', $Pec_Cod);
    $Vnd_Cod=$vendedor['Vnd_Cod'];  $Vet_Des=0; $Plan_Cod=$Pec[1]; $Pec_Cod=$Pec[0];
    $Tic_Sri=$row_rs_autorizaci['Tic_Sri']; $Aut_Cod=$row_rs_autorizaci['Aut_Cod']; $Aut_Sri=$row_rs_autorizaci['Aut_Sri'];
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
    
    try{
        $rs_Caja = $obBD_con1->getRowConsulta(76,$rs_Punto['Pun_Cod'].'*'.$Caj_Fec, $obBD_conexion);
        if(empty($rs_Caja['Caj_Cod'])){
            $obBD_conIns->operacionobBD(77,$rs_Punto['Pun_Cod'].'*'.$Caj_Fec, $obBD_conexionIns);
            $Caj_Cod=$obBD_conIns->insercionid($obBD_conexionIns->conexion);
        }else{
            $Caj_Cod=$rs_Caja['Caj_Cod'];
        }

        /* valida que no exista el documento */
        $num_existe_gencod=$obBD_con1->getRowConsulta(50, $Ses_Suc_Cod.'*'.$row_rs_autorizaci['Aut_Sri'].'*'.$Vet_Num.'*'.$Vet_Cod.'*'.$row_rs_autorizaci['Pun_Sri'], $obBD_conexion); 
        if($num_existe_gencod['total']*1>0) 
        {
          $responce['message']="El doc. $row_rs_autorizaci[Tic_Des] No. $Vet_Num ya existe!";
        }
        if($Aut_Tem=='E'&&$Vet_Num!==0)
        { 
            $Vet_Aut='N';
            require_once('../LOGICA/fac_log_electronica.php');
            $obBD_elect =  new Class_Log_Datos_Factura_Elect();
            $claveAcceso = $obBD_elect->getClaveAcceso($Aut_Cod, $Caj_Fec, $Vet_Num, $obBD_conexion);
            if(empty($claveAcceso))
               $responce['message']="Error al generar <u>Clave de Acceso</u> del <i>Comprobante Electr�nico</i>!";
         }

        $rise=($Tic_Sri*1==2||$Tic_Sri*1==9);
        if($rise) $iva_cero=$obBD_con1->getRowConsulta(68,'0',$obBD_conexion);
        /* cierro en caso de error */
        if(!empty($responce['message'])){ echo json_encode($responce);exit(); }

        if(isset($rets)){
            if(empty($Ret_Fec) && count($rets)>0 ){
                $Ret_Fec=$hoy;
            }
        }else{$Ret_Fec=NULL;}
        
        /* Cabecera de la factura de venta */
        $obBD_conIns->operacionobBD(23, $Tic_Cod.'*'.$Cli_Cod.'*'.$Ciu_Cod.'*'.$Caj_Cod.'*'.$rs_Punto['Vnd_Cod'].'*'.
            $Vet_Num.'*'.$Vet_Obs.'*'.$Aut_Cod.'*'.$Vet_Des.'*'.$hora.'*'.(isset($claveAcceso)?$claveAcceso:'').'*'.(isset($Vet_Aut)?$Vet_Aut:'').'*'.$Ret_Num.'*'.$Ret_Fec.'*'.$Ret_Aut_Sri.'*'.$Tpc_Cod, $obBD_conexionIns);
        $Vet_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
        $cod_pro_unique=array(); 

        /* CONTROL DEL DETALLE DE LA VENTA E INVENTARIO (KARDEX) */
        $kardex=array('IoE'=>'E', 'Kar_Fec'=>$hoy, 'Kar_Hor'=>date("H:i:s"), 'Vet_Cod'=>$Vet_Cod, 'Vnd_Cod'=>$Vnd_Cod);
        $array_kardex=array(); $s_add=true;
        foreach ($items as $i => $item)
        {
            $item['Vet_Cod'] = $Vet_Cod;
            $item['Vet_Ite'] = $i+1;
            if($rise) $item['Iva_Cod']=$iva_cero['Iva_Cod'];
            /* Item Documento */
            $item['Vet_Imp']=$item['Importe'];
            $obBD_conIns->operacionobBD(86,$item, $obBD_conexionIns);

            /* Control de Inventarios */
            if((isset($configs['Cof_Stk']) && $configs['Cof_Stk']=='N'))
            {
                if($Tic_Sri*1!=0 && $item['Adq_Cor']=='B'){
                    $s_add=true;
                    foreach ($array_kardex as &$k){
                        if($k['Pro_Cod']==$item['Pro_Cod']){
                            $s_add=false;
                            $k['Kar_Sal']+=(1)*$item['Vet_Can'];
                            $k['Kar_Ime']+=(1)*$item['Importe'];
                            $k['Kar_Pre']=$k['Kar_Ime']/$k['Kar_Sal'];
                            break;
                        }
                    } unset($k);
                    if($s_add==true){
                        $kardexIE=array_merge($kardex, array(
                            'Kar_Int'=>$i+1, 'Iva_Cod'=>$item['Iva_Cod'], 'Pro_Cod'=>$item['Pro_Cod'],
                            'Kar_Sal'=>(1)*$item['Vet_Can'],
                            'Kar_Pre'=>$item['Vet_Pru']*1,
                            'Kar_Ime'=>(1)*$item['Importe'],
                        ));
                        array_push($array_kardex, $kardexIE);
                    }    
                }
            }  

        }

        if((isset($configs['Cof_Stk']) && $configs['Cof_Stk']=='N'))
        {
            foreach ($array_kardex as $k){
                $obBD_conIns->updateStockProd($Ses_Suc_Cod, $k, true, $obBD_conexion,$obBD_conexionIns,$Bod_Cod);
            }
        }
         
         /* REGISTRO PAGO VENTA */ 
         $pagos = array('Vet_Cod'=>$Vet_Cod,'Bak_Cod'=>1,'Tipo_Cod'=>$Pag_Cod,'Pag_Pld'=>$Pag_Pld,
                        'Vet_Tot'=>$Vet_Tot,'Vet_Num'=>$Vet_Num,'Forma_Cod'=>$For_Cod); 
         $obBD_conIns->operacionobBD(72, $pagos, $obBD_conexionIns);


         /* CREACION DEL COMPROBANTE CONTABLE */
        if($configs['Cof_Con']=='S'&&($Tic_Sri*1!=0))
        {
            $Com_Con = 'REG. NOTA CRED/DEB '.$Vet_Num; 
            $Com_Fec=$Caj_Fec;
            $Tia_Asi = $obBD_con1->getRowConsulta(80, 7, $obBD_conexion);
            $meseCom = explode('-', $Com_Fec);
            $Com_Num= $obBD_con1->codigoComprAuto($Tia_Asi['Tia_Cod'], $Pec_Cod, $meseCom[1], $obBD_conexion);
            $campo='Cli_Cod';

            /* Cabecera del Comprobante */
            $obBD_conIns->operacionobBD(70, $Pec_Cod.'*'.$Cli_Cod.'*'.$Com_Num.'*'.$Com_Fec.'*'.trim($Com_Con).'*'.$Tia_Asi['Tia_Cod'].'*'.$Vet_Tot.'*'.trim($Vet_Obs).'*'.$campo, $obBD_conexionIns);
            $Com_Cod = $obBD_conIns->insercionid ($obBD_conexionIns->conexion);
            $obBD_conIns->operacionobBD(83, $Com_Cod.'*'.$Vet_Cod, $obBD_conexionIns);

            foreach ($items as &$item)
            {   
                $cuenta = $obBD_con1->getRowConsulta(84,$Plan_Cod.'*'.$item['Pro_Cod'].'*'.'V', $obBD_conexion);
                if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del producto: <u>'.$item['Ite_Lar'].'</u>!');
                $item['Pld_Cod']=$cuenta['Pld_Cod'];
                $obBD_conIns->operacionobBD(87, $Com_Cod.'*'.'H'.'*'.($item['Importe']).'*'.$cuenta['Pld_Des'].'*'.$item['Ite_Lar'].'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Item
            } 
            if($Iva_Tot*1>0)
            {
                $cuenta = $obBD_con1->getRowConsulta(88,$Plan_Cod, $obBD_conexion);
                if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Iva Cobrado</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod.'*'.('H').'*'.$Iva_Tot.'*'.'IVA'.'*'.'IVA'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);
            }


            /* CCPP Cuentas por Cobrar */ //ojo por ahora sigue dependiendo de contabilidad
            if($pagos['Forma_Cod']*1==2){
                $obBD_conIns->operacionobBD(87, $Com_Cod.'*'.('D').'*'.$pagos['Vet_Tot'].'*'.$pagos['Vet_Num'].'*'.('Doc.'.$Vet_Num).'*'.$pagos['Pag_Pld'], $obBD_conexionIns);
                $obBD_conIns->operacionobBD(55, $Com_Cod.'*'.$Vet_Cod.'*'.$Caj_Fec.'*'.(isset($Vet_Obs)?$Vet_Obs:''), $obBD_conexionIns);
                $Cpc_Cod = $obBD_conIns->insercionid ($obBD_conexionIns->conexion);
            }
            else{
                $obBD_conIns->operacionobBD(87, $Com_Cod.'*'.('D').'*'. $pagos['Vet_Tot'] .'*'.$pagos['Vet_Num'].'*'.('Doc.'.$Vet_Num).'*'.$pagos['Pag_Pld'], $obBD_conexionIns);
            }           
        }

        //UPDATE NOTA DE PEDIDO COMO USADA 
        $obBD_conIns->operacionobBD(132,str_replace(",", " OR ventas.Vet_Cod=", "(ventas.Vet_Cod=".$ND_Ventas.")") , $obBD_conexionIns);     

    } catch (Exception $ex){
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
        $responce['message']=$ex->getMessage();
        echo json_encode($responce); exit();
    }

    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
    if ($obBD_conIns->Error == 0) 
    {
        $reportes = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        $response=array('success'=>true,'Vet_Impr'=>"".(!empty($reportes[1])?"$reportes[1]?Vet_Cod=":"")."$Vet_Cod",
        'Vet_Cod'=>$Vet_Cod, 'Vet_Num'=>$Vet_Num, 'Vet_Fec'=>$Caj_Fec,'Tic_Des'=>$Tic_Txt);
        
        if($Aut_Tem=='E'){
                $rs_infoEmpresa = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
                $rs_infoCliente = $obBD_con1->getRowConsulta(61, $Aut_Cod, $obBD_conexion);
                $xml= $obBD_elect->createXmlFactura($Vet_Cod, $Aut_Cod, $claveAcceso, $obBD_conexion);
                $responce['Vet_Xmls']=baseUrl("../FRONT/".$Ses_Emp_Cod.'/'.$claveAcceso.'.xml');
                $response['xml']=base64_encode($xml);
        }
        
        if(!empty($Vet_Cod)){
            $response['Vet_Data']=array('Tic_Des'=>$Tic_Txt,'cliente'=>$cliente,'Vet_Num'=>$Vet_Num,'Vet_Fec'=>$Caj_Fec,'Vet_Aut'=>$Aut_Sri);
            $response['Vet_Rows']=$obBD_con1->getArrayConsulta(79, $Vet_Cod, $obBD_conexion);
            $response['Vet_Link']="".(!empty($reportes[1])?"$reportes[1]?Vet_Cod=":"")."$Vet_Cod";
        }
        if (!empty($Com_Cod)) {
            $response['Com_Data']=array('Codigo'=>$Com_Cod,'Tia_Des'=>$Tia_Asi['Tia_Des'],'Com_Con'=>$Vet_Obs,'Com_Fec'=>$Caj_Fec,'Com_Val'=>$Vet_Tot);
            $response['Com_Rows']=$obBD_con1->getArrayConsulta(27,$Com_Cod,$obBD_conexion);
            $response['Com_Link']="".(baseUrl("../../contabilidad/FRONT/con_pri_compr_1.1.php?codigo=")."$Com_Cod");
        }
   
    }
    else
    {
        $response=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_conIns->MsgError);
    }
    echo json_encode($response);
    exit();
}

$row_rs_autorizaci = $obBD_con1->getRowConsulta(133, $Tic_Cod.'*'.$row_rs_vendedor['Pun_Cod'].'*'.$hoy, $obBD_conexion);                           
?>
<!DOCTYPE html>
<HTML>
    <HEAD>      
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Transf. Nota Pedido a Fact. [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
        <style>                     
            .label-xs.required{padding-top: 4px !important;}
            /*.ui-jqgrid td input, .ui-jqgrid td select, .ui-jqgrid td textarea {padding-top: 2px;}*/
            .footrow td[aria-describedby="items_Importe"],.footrow td[aria-describedby="items_Vet_Pru"]{padding: 0 !important;}
            .footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact input[type=text]{ padding: 0; }
            .footerFact textarea{text-align: left; height: 50px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}
        </style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestion de Notas de Pedido</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">            
                <div class="row">
                    <?php if(isset($ND_Ventas)){ ?>
                    <div class="col-sm-12">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Facturar Notas de Pedido</legend> <!-- Form Name -->
                          <div class="row">
                              <form id="factForm" class="form-horizontal normal" action="javascript:if($('#Cli_Cod').val()===''){$.alert('Seleccione Cliente');}else{if($('#total').val()==='0.00'){$.alert('El Total de la Factura no puede ser cero!');}else{$.createDialogConfirm(null,null,saveForm);}}">
                                  <input name="ND_Ventas" type="text" value="<?php echo $ND_Ventas; ?>" style="display: none"/>
                           <div class="col-sm-5">
                               
                               <fieldset class="exa-fieldset clienteFact">                           
                                <legend class="Titulos2">Datos del Cliente</legend> <!-- Form Name -->
                                <!-- Text input-->
                               
                                    <div class="form-group">
                                      <label class="col-xs-2 control-label label-xs required" for="cliente">Cedula/R.U.C:</label>  
                                      <div class="col-xs-6">
                                            <div class="input-group input-group-xs">                                                
                                                <input type="text" id="Cli_Cod" name="Cli_Cod" data-cliente="Cli_Cod" value="" style="display: none" />
                                                <input type="text" id="Prs_Cod" name="Prs_Cod" data-cliente="Prs_Cod" value="" style="display: none" />
                                                <span id="cedula" name="Prs_Ced" data-cliente="Prs_Ced" class="form-control databind">Seleccione Cliente..</span>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success" onclick="$('#cliFactDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Clientes"></span></button>
                                                </span>
                                            </div><!-- /input-group -->                                          

                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label class="col-xs-2 control-label label-xs" for="cliente">Cliente:</label>  
                                      <div class="col-xs-10">                                    
                                            <span id="cliente" name="cliente" data-cliente="cliente" class="form-control databind input-xs"></span>

                                      </div>
                                    </div>
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs" for="direccion">Direccion:</label>  
                                      <div class="col-xs-10">                                    
                                              <span id="direccion" data-cliente="Prs_Ced" class="form-control input-xs"></span>

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
                           <div class="col-sm-7">
                           
                               
                               <fieldset class="exa-fieldset">                           
                                <legend class="Titulos2">Datos de La Factura</legend> <!-- Form Name -->                                    
                                    <div class="form-group">
                                      <?php if($rs_infoEmpresa['Cof_Con']=='S'){ ?>
                                      <label class="col-xs-2 control-label label-xs" for="Pec_Cod">Periodo:</label>  
                                      <div class="col-xs-2">
                                            <?php
                                                $row_rs_periodos = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
                                                $periodo = current($row_rs_periodos); 
                                            ?>
                                          <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" onchange="setCuenta(this.value);" required>
                                                
                                                <?php 
                                                foreach ($row_rs_periodos as $row)
                                                {
                                                ?>
                                                <option value="<?Php echo $row['Pec_Cod'].'*'.$row['Pla_Cod'].'*'.$row['Pec_Fei'].'*'.$row['Pec_Fef']; ?>"><?Php echo $row['Anio']; ?></option>
                                                <?php       
                                                } ?>
                                            </select>    
                                      </div> 
                                      <?php } ?>
                                      <label class="col-xs-1 control-label label-xs required" for="Caj_Fec">Fecha:</label>  
                                      <div class="col-xs-3">                                    
                                          <input name="Caj_Fec" id="Caj_Fec" type="text" class="form-control input-xs isDatePicker" required="" onchange="validaVetNum()"/>
                                      </div> 
                                      <label class="col-xs-1 control-label label-xs" for="ciudad">Ciudad:</label>  
                                      <div class="col-xs-3">  
                                          <?php $row_rs_ciudad = $obBD_con1->getRowConsulta(127, $Ses_Usu_Cod, $obBD_conexion); ?>
                                              <span id="ciudad" class="form-control input-xs"><?Php echo $row_rs_ciudad['Ciu_Des']; ?></span>
                                              <input name="Ciu_Cod" type="text" value="<?Php echo $row_rs_ciudad['Ciu_Cod']; ?>" style="display: none" required />
                                      </div> 
                                    </div>
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs" for="Tic_Cod">Documento:</label>  
                                      <div class="col-xs-6">
                                            <select name="Tic_Cod" id="Tic_Cod" class="form-control input-xs" required>
                                              <?Php
                                              foreach($row_tipo_compr as $row)
                                              { if($row['Tic_Sri']=='01'){ $Tic_Cod=$row['Tic_Cod'];?>
                                              <option  <?Php if ($Tic_Cod == $row['Tic_Cod']){ echo "selected"; } ?> value="<?Php echo $row['Tic_Cod']; ?>"><?Php echo $row['Tic_Des']; ?></option>
                                              <?Php
                                              }}
                                              ?>
                                            </select>
                                      </div>                                 
                                    </div> 
                                    
                                    
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs required" for="Vet_Num">Secuencia:</label>  
                                      <div class="col-xs-3">    
                                          <?php 
                                            $Vet_Num='';
                                            $Aut_Tem = '';
                                            if(count($row_rs_autorizaci) > 0){
                                                $siguiente=$obBD_con1->getRowConsulta(10,$row_rs_autorizaci['Aut_Ini'].'*'.$row_rs_autorizaci['Aut_Fin'].'*'.$row_rs_autorizaci['Aut_Sri'].'*'.$row_rs_autorizaci['Tic_Cod'].'*'.$Ses_Suc_Cod.'*'.$row_rs_autorizaci['Pun_Sri'],$obBD_conexion);
                                                $Vet_Num = $siguiente['siguiente'];
                                                $Aut_Tem = $siguiente['Aut_Tem'];
                                            }
    
                                          ?>
                                          <input id="Vet_Num" name="Vet_Num" type="text" class="form-control input-xs" value="<?php echo $Vet_Num; ?>" style="text-align: right" onchange="validaVetNum()" required/>

                                          <input id="Aut_Tem" name="Aut_Tem" type="text" class="form-control input-xs" value="<?php echo $Aut_Tem; ?>" style="display: none; />

                                      </div>  
                                      <div class="col-md-7 msgDiv">
                                        <?php if(count($row_rs_autorizaci) > 0){ ?>
                                        <img class="imgMsg" src="../../mascaras/model1/imagenes/ok-s.gif" /><label class="lblMsg"></label>
                                        <?php }else{ ?>
                                        <img class="imgMsg" src="../../mascaras/model1/imagenes/32x32/cancel.gif"><label class="lblMsg">No tiene <b>Autorizaci�n</b> para Facturar en <b><?php echo $hoy; ?></b></label>
                                        <?php } ?>
                                      </div>
                                    </div> 
                                   
                           </fieldset>
                            <fieldset class="exa-fieldset">                           
                                <legend class="Titulos2">Forma de Pago</legend> <!-- Form Name -->
                                
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs" for="For_Cod">Forma:</label>  
                                      <div class="col-xs-3">                                    
                                            <select id="For_Cod" name="For_Cod" class="form-control input-xs" required>
                                                <?Php
                                                    $formasPago = $obBD_con1->getArrayConsulta(89,'',$obBD_conexion);
                                                    foreach ($formasPago as $forma) {
                                                ?>
                                                   <option value="<?Php echo $forma['For_Cod']; ?>"><?Php echo $forma['For_Des']; ?></option>

                                                <?Php } ?>
                                            </select>    
                                      </div>
                                      <label class="col-xs-2 control-label label-xs required">Pago&nbsp;SRI:</label>
                                            <div class="col-xs-5"  >
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
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs" for="Pag_Cod">Tipo:</label>  
                                      <div class="col-xs-3">                                    
                                            <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs" required>
                                            </select> 
                                      </div>  
                                      <label class="col-xs-2 control-label label-xs" for="Pag_Pld">Cuenta:</label>  
                                      <div class="col-xs-5">   
                                            <?php $row_rs_bancos = $obBD_con1->getArrayConsulta(128, '1*'.$periodo['Pec_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion); ?>
                                            <select name="Pag_Pld" id="Pag_Pld" class="form-control input-xs" required>
                                               <?php if($rs_infoEmpresa['Cof_Con']=='S'){ ?>
                                                    <?php if(count($row_rs_bancos)>1){ ?><option value=''>Seleccione...</option> <?php } ?>
                                                    <?php foreach ($row_rs_bancos as $row)
                                                    {
                                                    ?>
                                                    <option value="<?Php echo $row['Pld_Cod']; ?>"><?Php echo $row['Ban_Des']; ?></option>
                                                    <?php       
                                                    } 
                                                }else{ ?> 
                                                    <option value='NULL'>Ninguno</option>
                                               <?php } ?> 
                                            </select> 
                                      </div> 
                                    </div> 
                               
                           </fieldset>
                           </div>
                              </form>   
                           <div class="col-sm-12">
                            <table id="items"></table>
                            <div id="pitems"></div>
                           </div> 
                              <div class="col-sm-12" style="padding-top:10px">
                                  
                                  <button type="button" class="btn btn-inverse btn-sm" title="Atr�s" onclick="window.history.back();" >
                                                <i class="glyphicon glyphicon-arrow-left"></i>
                                                <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
                                   </button>
                                  <button id="btnGuardar" type="button" class="btn btn-primary btn-sm" title="Guardar" onclick="$('#factForm').formSubmit()">
                                       <i class="glyphicon glyphicon-floppy-disk"></i>
                                       <span>&nbsp;&nbsp;Guardar</span>
                                </button>
                                  
                             
                           </div>
                        </div>   
       <?php  
         $NDs=explode(',',$ND_Ventas);
         $Vet_Cod=str_replace(",", " OR ventas.Vet_Cod=", "(ventas.Vet_Cod=".$ND_Ventas.")");
         $responce['rows'] = $obBD_con1->getArrayConsulta(129,$Vet_Cod, $obBD_conexion);                             
       ?>
       <script>                                
            $(document).ready(function () { 
                
                $("#items").jqGrid({
                     data:<?php echo json_encode($responce['rows']); ?>,
                     datatype: "local",                                        
                     rowNum: 10000000, rownumbers:true,
                     pgtext: ' ',   
                     autowidth : true, shrinkToFit: true, height: 100,responsive:true,
                     //colNames:['Inv No','Date', 'Client', 'Amount','Tax','Total','Notes'],
                     colModel:[
                             {name:'Pro_Cod',label:'C�d. Int', width:60, sorttype:"int",align:'center'},
                             {name:'Iva_Cod',label:'CodIva', width:20,hidden:true},
                             {name:'Ite_Lar',label:'Producto', width:200},                                                 
                             {name:'Vet_Can',label:'Cant.', width:40, align:"right"},
                             {name:'Vet_Pru',label:'P. Unitario', width:60, align:"right", summaryRound: 4,formatter:"currency",
                                formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.',decimalPlaces: 4, defaultValue: '0.0000'}},
                             {name:'Importe',label:'Importe', width:70,align:"right", summaryRound: 2,formatter:"currency",
                                formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.', defaultValue: '0.00'}},
                             
                             {name:'Iva_Por',label:'IVA', width:20,align:"right"},
                             {name:'Adq_Cor',label:'Adq.', width:20,align:"center"},
                             {name:'Pro_Obs',label:'Observaci�n', width:120}        
                     ],
                     pager: "#pitems",
                     footerrow:true,
                     viewrecords: true,hidegrid:false,                                                                                 
                     caption: "Detalle Ventas",
                     loadComplete: function (data) { 
                            
                     }
             });
             $("#items").jqGrid('setGroupHeaders', {
               useColSpanStyle: true, 
               groupHeaders:[
                     {startColumnName: 'Vet_Can', numberOfColumns: 4, titleText: '<em>Precio</em>'}
               ]    
             });
             var grid='items', $footRow = $("#gbox_"+grid+" #gview_"+grid+" .ui-jqgrid-sdiv .footrow");                                   
             $footRow.find('>td[aria-describedby="items_Pro_Obs"]').css("border-right-color",'1px solid #789');
             var descHtml='<div class="footerFact formDatos" class="formDatos"><label style="position:relative;text-align: left;">Observaci&oacute;n:</label><textarea name="Vet_Obs" tabindex="12" class="text" onchange=""></textarea></div>';
             var labelHtml='<div class="footerFact"><label>SUBTOTAL:</label><label>TARIFA 0%:</label><label>TARIFA 12%:</label><label>I.V.A.:</label><label class="total">TOTAL:</label>';
             var tablaHtml='<div class="footerFact"><input id="subtotal" type="text"  readonly/><input  id="sinIva" type="text"  readonly/><input  id="conIva" type="text"  readonly/><input  id="iva" type="text"  readonly/><input  id="total" type="text" class="total"  readonly/>';
             $("#items").jqGrid('footerData', 'set',{Ite_Lar:descHtml,Vet_Pru:labelHtml,Importe:tablaHtml},false);
             $footRow.find('>td').css("border-right-color", "transparent").end().find('>td[aria-describedby="items_Vet_Pru"]').removeAttr('title');
             
             setTimeout(function (){updateTotal();},150);
             $.createDatePickers('.isDatePicker');
             $.createDialog('#successDialog',135,475); 
             <?php if($rs_infoEmpresa['Cof_Con']=='S'){ ?>
                 $( "#Caj_Fec" ).datepicker( "option", "minDate", '<?php echo $periodo['Pec_Fei']; ?>' );
                 $( "#Caj_Fec" ).datepicker( "option", "maxDate", '<?php echo $periodo['Pec_Fef']; ?>');
             <?php } ?>    
          });
          function setCuenta(value){
              var periodo=value.split("*");
              var codigo = $('#For_Cod').val();
              $( "#Caj_Fec" ).datepicker( "option", "minDate",periodo[2] );
              $( "#Caj_Fec" ).datepicker( "option", "maxDate",periodo[3] );
              $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{cuentas:true,Pla_Cod:periodo[1],Pec_Cod:periodo[0],For_Cod:codigo}, function(response){
                    $("#Pag_Pld").html(response['html']);
              },'json').fail(function(error) { console.log(error); $.alert("El Servidor ha fallado en responder!");});
          }
          function updateTotal(){
                var grid=$('#items'),rows= grid.jqGrid('getRowData');
                var max = rows.length,conIva=0,sinIva=0,subtotal=0,iva=0,total=0;
                for(var i=0;i<max;i++){       
                    subtotal=subtotal+rows[i]['Importe']*1;
                    if(rows[i]['Iva_Por']*1>0){
                        conIva=conIva+rows[i]['Importe']*1;
                        iva=iva+rows[i]['Importe']*rows[i]['Iva_Por']/100;
                    }else {sinIva=sinIva+rows[i]['Importe']*1;}
                }
                total=subtotal+iva;
                
                $('#subtotal').val(subtotal.toFixed(2));
                $('#sinIva').val(sinIva.toFixed(2));
                $('#conIva').val(conIva.toFixed(2));
                $('#iva').val(iva.toFixed(2));
                $('#total').val(total.toFixed(2));
          }
          function validaVetNum(){  
                var numAnt=$("#Vet_Num").val();
                if(numAnt!==''&&numAnt!=='0'){                                       
                    $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{'Caj_Fec':$('#Caj_Fec').val(),'valVetNum': numAnt}, function(response){
                        if(response['success']===true){
                            $("#Vet_Num").alertMsg();
                            if(response['valid']===false){
                                $("#Vet_Num").val('').alertMsg('El N�mero de Factura <b>'+response['Vet_Num']+'</b> no esta <b>Autorizado</b>.');
                                $("#Vet_Num").focus();
                            }
                            if(response['exist']===true){
                                $("#Vet_Num").val('').alertMsg('El N�mero de Factura <b>'+response['Vet_Num']+'</b> ya esta <b>Registrado</b>.');
                                $("#Vet_Num").focus();
                            }
                        }else {numChe=0;$("#NumChe").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}                                
                    },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;        
                }else{$("#Vet_Num").alertMsg('El N�mero de <b>Factura</b> es incorrecto.');}  
            }
            function saveForm(){
                var data=$('#factForm').getData('saveForm');
                data['Tic_Txt']=$('#Tic_Cod').find('option:selected').text();
                data['Vet_Obs']=$('textarea[name=Vet_Obs]').val();
                data['Vet_Tot']=$('#total').val();
                data['Iva_Tot']=$('#iva').val();
                data['items'] = $("#items").getGridBatch();
                $.saveDataJson('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',data, function(response){                                       
                        <?php if($rs_infoEmpresa['Cof_Con']=='S'){ ?>                                                
                            $('#impCompr').data('url',response['Com_Link']);
                            $('#successDialog').dialog('open');
                        <?php }else{ ?>
                            $.alert('El Registro se Guardo Con Exito!');
                        <?php } ?>
                        $('#factForm').find(':input').attr('disabled',true).end().find(':input:not(.btn)').addClass('readOnly');
                        $('#btnGuardar').attr('disabled','disabled');
                        return false;
                    },function(r) {
                        $.alert('No se logro guardar el Registro!. '+r['message']);
                        return false;
                    });                                 
            }

            var codigo = $('#For_Cod').val();
            $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{tiposPago:true, For_Cod:codigo}, function(response){
                     $("#Pag_Cod").html(response['html']);
              },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});

            $('#For_Cod').on('change',function ()
            {
                var codigo = $('#For_Cod').val();
                var Pec_Val= $('#Pec_Cod').val();

                $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{tiposPago:true, For_Cod:codigo}, function(response){
                         $("#Pag_Cod").html(response['html']);
                  },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});

                 setCuenta(Pec_Val);
            });

           

       </script>
                        </fieldset>
                    </div>
                    <!--INICIO DEL DIALOGO IMPRIMIR --> 
                        <div id="successDialog"  title="Mensaje del Sistema">  
                            <center><h4>El Comprobante se ha registrado con Exito!</h4></center>  
                            <center id="printCheque"></center>
                            <center> 
                                <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse btn-sm" style="display: inline;" ><i class="glyphicon glyphicon-remove"></i> <span>Cerrar</span></button>            
                                <a id="impCompr"  onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-primary btn-sm"> <i class="glyphicon glyphicon-print"></i> <span>Imprimir</span></span> </a>               
                            </center>        
                        </div>
                    
                        <script type="text/javascript">
                           
                        </script>
                    <!-- FIN DEL DIALOGO CLIENTE-->
                    <?php } ?>
                    <?php if(!isset($ND_Ventas)){ ?>

                    <?php 
                        /**
                        * Evalua si el usuario es un vendedor 
                        */
                        if (count($row_rs_vendedor) > 0)
                        {                                
                           
                                               
                    ?>

                    <form  id="formCompTemp" action="javascript:$('#list').Search('#formCompTemp','ajaxND_Ventas')" class="form-horizontal normal">
                           <div class="col-xs-6">
                                
                                    <fieldset class="exa-fieldset cliente">
                                        <!-- Form Name -->
                                        <legend class="Titulos2">Seleccione Cliente</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs" for="cliente">C&eacute;dula/R.U.C:</label>  
                                            <div class="col-xs-6">
                                                  <div class="input-group input-group-xs">                                                
                                                      <input type="text" id="Cli_Cod" name="Cli_Cod" data-cliente='Cli_Cod' value="" style="display: none" />
                                                      <span id="cedula" data-cliente='Prs_Ced' class="form-control">Seleccione Cliente..</span>
                                                      <span class="input-group-btn">
                                                          <button class="btn btn-success" onclick="$('#cliDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Clientes"></span></button>
                                                      </span>
                                                  </div><!-- /input-group -->                                          

                                            </div>
                                            <div class="col-md-1"><a onclick="setCliente({});" title="Quitar Proveedor" class="btn btn-success btn-xs pull-right"><i class="glyphicon glyphicon-new-window"></i></a></div> 
                                          </div>
                                          <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs" for="cliente">Cliente:</label>  
                                            <div class="col-xs-10"><span id="cliente" data-cliente='cliente' class="form-control input-xs"></span></div>
                                          </div>
                                          <div class="form-group">  
                                            <label class="col-xs-2 control-label label-xs" for="direccion">Direcci&oacute;n:</label>  
                                            <div class="col-xs-10"><span id="direccion" data-cliente='Prs_Dir' class="form-control input-xs"></span></div>                                 
                                          </div>                                
                                    </fieldset>
                                
                            </div>
                            <div class="col-xs-6">
                                             
                                    <fieldset class="exa-fieldset">
                                        <!-- Form Name -->
                                        <legend class="Titulos2">Filtros</legend>
                                         <div class="col-sm-9">                                        
                                            
                                            <!-- Select Basic -->
                                            <div class="form-group">
                                              <label class="col-xs-2 control-label label-xs " for="Tic_Cod">Docum.:</label>
                                              
                                                <div class="col-xs-6">
                                                    <select name="Tic_Cod" id="Tic_Cod" class="form-control input-xs" required onchange="this.form.submit()">
                                                      <?Php
                                                      foreach($row_tipo_compr as $row)
                                                      { if($row['Tic_Sri']=='0'){ $Tic_Cod=$row['Tic_Cod'];?>
                                                      <option  <?Php if ($Tic_Cod == $row['Tic_Cod']){ echo "selected"; } ?> value="<?Php echo $row['Tic_Cod']; ?>"><?Php echo $row['Tic_Des']; ?></option>
                                                      <?Php
                                                      }}
                                                      ?>
                                                    </select>
                                              </div> 
                                              
                                            </div>
                                            <div class="form-group">  
                                                <label class="col-xs-2 control-label label-xs " for="Fec_Ini">Desde:</label>  
                                                <div class="col-xs-4">                                    
                                                    <input name="Fec_Ini" id="Fec_Ini" type="text" class="form-control input-xs"/>
                                                </div>                                 
                                                <label class="col-xs-2 control-label label-xs" for="Fec_Fin">Hasta:</label>  
                                                <div class="col-xs-4">                                    
                                                    <input name="Fec_Fin" id="Fec_Fin" type="text" class="form-control input-xs"/>
                                                </div>                                 
                                              </div>
                                             </div>
                                              <div class="col-md-3" style="padding-top: 10px;">
                                                  <div class=""><button type="button"  onclick="this.form.submit()" class="btn btn-sm btn-success" title="Ejecutar B�squeda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
                                              </div>
                                    </fieldset>
                                    <fieldset class="exa-fieldset">
                                        <!-- Form Name -->
                                        <legend class="Titulos2">Limitar Seleccion</legend>
                                        <div class="form-group">  
                                            <label class="col-xs-2 control-label label-xs " for="Fec_Ini"><input type="checkbox" class="check-big" onchange="$('.max').attr('disabled',!$(this).is(':checked'));" />&nbsp;&nbsp;&nbsp;Maximo:</label>  
                                            <div class="col-xs-4">                                    
                                                <input name="Max" id="Max" type="text" class="form-control input-xs max" value="" disabled=""/>
                                            </div>                                 
                                            <div class="col-xs-4">                                    
                                                <button type="button"  onclick="select()" class="btn btn-xs btn-primary max" title="Ejecutar Selecci�n" disabled=""><span class="glyphicon glyphicon-check"></span> &nbsp;Seleccionar</button>
                                            </div>                                 
                                        </div>
                                    </fieldset>    
                            </div>    
                        </form>
                    
                        <!--INICIO DEL DIALOGO MOSTRAR EL DETALLE DE LA NOTA DE PEDIDO--> 
                        <div id="verDetalleNota" title="Detalle Nota de Pedido">
                        <div class="row">
                            <div class="col-sm-12">
                                <div id="tabs_abo_det" class="ui-tab-fix">
                                    <ul style="font-size: 12px;" role="tablist">
                                        <li id="ant_detasi"><a href="#ant_det_asi">Productos</a></li>
                                    </ul>
                                    <div id="ant_det_asi">
                                        <div class="row">
                                            <div class="col-sm-12" style="padding-top: 10px;">
                                                <table id="showProductosNota" name="showProductosNota"></table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>

                   
                    <div class="col-sm-12">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Listado de Notas de Pedido</legend> <!-- Form Name -->
                           <table id="list"></table>
                           <div id="listPager"></div>
                        </fieldset>
                        <div style="" class="">                            
                            <button type="button" class="btn btn-sm btn-primary start" onclick="send();" title="Gestionar Notas de Pedido"> <span class="glyphicon glyphicon-floppy-open"></span>&nbsp; <span>Facturar Notas de Pedido</span></button>
                            <form id="formNDVentas" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
                                <input id='ND_Ventas' name='ND_Ventas' value='' style="display: none" >
                            </form>
                            <script>
                                   function send(){
                                       var nd=new Array();
                                       var grid=$('#list'),rows= grid.jqGrid('getRowData');
                                        for(var i=0;i<rows.length;i++){                                
                                            if(rows[i].act==="Yes") 
                                            {nd.push(rows[i]['Vet_Cod']);}
                                        }                                         
                                       $('#ND_Ventas').val(nd.join(','));
                                       
                                       if($('#ND_Ventas').val()!==''){
                                        $('#formNDVentas')[0].submit();
                                        $("#verDetalleNota").hide();
                                       }
                                       else{
                                         $.alert('Debe seleccionar al menos una Nota de Venta!');  
                                       }
                                   }

                                   function select(){
                                       var list=$('#list'), ids=list.jqGrid('getDataIDs'), sum=0, max=$.round($('#Max').val());

                                       for(var i=0,z=ids.length;i<z;i++){
                                            list.find('tr#'+ids[i]+'  td[aria-describedby^="list_act"] input[type="checkbox"]').prop('checked',false);
                                        }


                                       for(var i=0,z=ids.length;i<z;i++){
                                            var dat=list.jqGrid('getRowData',ids[i]), val=($.numUnformat(dat['Total']));
                                            if((sum+val)>max) break;
                                            sum+=val;
                                            list.find('tr#'+ids[i]+'  td[aria-describedby^="list_act"] input[type="checkbox"]').prop('checked',true);
                                       }
                                       list.jqGrid('footerData','set',{Iva:'<div style="text-align:right">TOTAL:</div>',Total:$.numFormat(sum)},false);
                                   }
                            </script>
                        </div>
                    </div>
                    <script>
                    var compGrid;
                    $(document).ready(function () { 

                        $( "#verDetalleNota" ).createDialog({width:700,height:435,icon:'info-sign'});
                        $("#tabs_abo_det").tabs();

                        compGrid=$("#list").createGrid({                           
                            colModel: [                               
                                { label: 'C�d.Int.', name: 'Vet_Cod', key: true, hidden:true,viewable:true }, 
                                { label: 'Fecha', name: 'Caj_Fec',align:"center", width: 40  },  
                                { label: 'C�dula/R.U.C.', name: 'Prs_Ced', width: 55, align:"center", classes:'bgNoRight'},
                                { label: 'Cliente', name: 'cliente', width: 100, classes:'bgNoRight'},
                                { label: 'Observaci�n', name: 'Vet_Obs', width: 80, classes:'bgNoRight'},
                                { label: 'Pago', name: 'Vet_Pag', width: 80,hidden:true, classes:'bgNoRight'},
                                { label: 'Valor', name: 'Vet_Tot', width: 40, align: 'right', decimalPlaces: '2', summaryRound: 2,formatter:"currency",
                                        formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" , classes:'bgNoRight'
                                },
                                { label: 'Descto.', name: 'Descuento', width: 30, align: 'right', decimalPlaces: '2', summaryRound: 2,formatter:"currency",
                                        formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" , classes:'bgNoRight'
                                }, 
                                { label: 'SubTotal', name: 'SubTotal', width: 45, align: 'right',  decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: { thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" , classes:'bgNoRight',
                                        formatter: function (cellValue, options, rowObject) { return $.fn.fmatter.call(this, "number",(rowObject.Vet_Tot-rowObject.Descuento), options);}
                                }, 
                                { label: 'IVA', name: 'Iva', width: 35, align: 'right',  decimalPlaces: '2', summaryRound: 2,formatter:"currency",
                                        formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" 
                                },                                 
                                { classes:'columnHighlight2',label: 'Total', name: 'Total', width: 50, align: 'right',  decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" ,
                                        formatter: function (cellValue, options, rowObject) { return $.fn.fmatter.call(this, "currency",(rowObject.Vet_Pag*1+rowObject.Iva*1), options);}
                                }, 
                                { label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act', width: 15, align: 'center',viewable: false, formatter: 'checkbox', classes:'bgNoRight', formatoptions: { disabled: false },resizable:false },
                                {label:'<center><i class="ui-icon ui-icon-info"></i></center>', name: 'btn_detalle', width: 25, align: 'center',viewable: false,
                                    formatter:function (cellvalue, options, rowObject) {
                                      return  $.getGridButton(verDetalle, rowObject, 'Ver Detalle', 'info-sign','','info')+"&nbsp;";
                                    }
                                  },
                                {classes:'columnHighlight1', label: 'No. Docum.', name: 'Fac_Num', width: 70, align:"center"}                                 
                            ],     
                            height: 270, 
                            footerrow: true, 
                            userDataOnFooter: false, 
                            selectGridRows:false, 
                            datatype: 'local',
                            loadComplete: function (data) { 
                                var grid=$(this), iCol = grid.getColumnIndexByName('act'), rows = this.rows, i, c = rows.length; 
                                updateTotals(grid);
                                for (i = 0; i < c; i += 1) {                                    
                                    $(rows[i].cells[iCol]).click(function (e) {                                        
                                        updateTotals(grid);    
                                    });
                                }
                            }
                        },false,"#listPager")
                        .gridButtonsAdd([
                            { caption:"Marcar Todo&nbsp;", buttonicon:"ui-icon-bullet", onClickButton:function(){compGrid.selectAllByComlumn('act',true);updateTotals(compGrid);}, position: "last", title:"", cursor: "pointer"},
                            { caption:"Desmarcar Todo&nbsp;", buttonicon:"ui-icon-radio-off", onClickButton:function(){compGrid.selectAllByComlumn('act',false);updateTotals(compGrid);}, position: "last", title:"", cursor: "pointer"}
                        ]);    
                        $('#ND_Ventas').val('');
                        $.createDateRange('#Fec_Ini','#Fec_Fin');
                        
                    });

                    function updateTotals(grid){                    
                        var sum=0, sel=grid.find('tr td[aria-describedby^="list_act"] input[type="checkbox"]:checked');                        
                        if(sel.length>0)
                        sel.each(function(){
                            var id=$(this).parent().parent().attr('id'), v=grid.jqGrid('getRowData',id);
                            sum+=($.numUnformat(v['Total'])); 
                        });
                        grid.jqGrid('footerData','set',{Iva:'<div style="text-align:right">TOTAL:</div>',Total:$.numFormat(sum)},false);
                    }
                    </script>
                   <?php    
                        }else
                        {
                                echo error_alerta (" Ud. no es un Vendedor autorizado para emitir Facturas o Notas de Ventas", 2);
                        }//Fin de else del if ($total_rs_vendedor > 0) ?>
                    <?php } ?>
                </div>    
              
            
        </div>
    </div>

    <!--INICIO DEL DIALOGO BUSCAR CLIENTE--> 
    <div id="cliDialog" title="B�squeda de Clientes"></div>
    <div id="cliFactDialog" title="B�squeda de Clientes"></div>
    <script type="text/javascript">
        $(document).ready(function() {
            let model=[
                { label: 'C�d.Int.', name: 'Cli_Cod', key: true,hidden:true,viewable: true },                
                { label: 'C�dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                { label: 'Cliente', name: 'cliente', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                { label: 'Direcci�n', name: 'Prs_Dir',hidden:true,viewable: true },                      
                { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,formatter:'gridButton',formatoptions:{action:'selectCliente',data:'Cli_Cod'}},
                { label: 'C�d.Int.', name: 'Prs_Cod', hidden:true,viewable: true }
            ];
            $('#cliDialog').createSearchDialog({colModel:model},{title:'Cliente'});
            model[4]['formatoptions']['action']='selectClienteFact';
            $('#cliFactDialog').createSearchDialog({colModel:model},{title:'Cliente'});
        }); 
        function selectCliente(Cli_Cod){           
            setCliente($("#cliGrid").jqGrid("getRowData",Cli_Cod));
            $('#cliDialog').dialog('close');            
        }
        function selectClienteFact(Cli_Cod){     
            $('.clienteFact').setData($("#cliFactGrid").jqGrid("getRowData",Cli_Cod),true,'cliente');
            $('#cliFactDialog').dialog('close');            
        }
        function setCliente(data){
            $('.cliente').setData(data,true,'cliente');                
            $('#list').Search('#formCompTemp','ajaxND_Ventas');                     
        }

    </script>

    <script type="text/javascript">
        $(document).ready(function() {
            
            if($('#ND_Ventas').val()=='')
            {
                $('#showProductosNota').createGrid({viewrecords:false,
                data:[], rowNum: 100, height: 250, width: 650, footerrow:true,responsive:false,
                onSelectRow: function(rowid, e) { $(this).resetSelection();},
                colModel:[
                  { label: 'index', name: 'index',hidden:true, classes:'bgNoRight' },
                  { label: 'Descripcion', name: 'Ite_Lar', width: 20, align:"left"},
                  { label: 'Cantidad', name: 'Vet_Can', width: 5, align: 'right', formatter:'number', editable:true,},
                  { label: 'Pre.Unit', name: 'Vet_Pru', width: 5, align: 'right', formatter:'currency', editable:true,
                    formatoptions: {
                      prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
                    }
                  },
                  { label: 'Total', name: 'Vet_Imp', width: 5, align: 'right', formatter:'currency', editable:true,
                    formatoptions: {
                      prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
                    }
                  }
                ]
              },true,'',{view:false});
            }

        }); 
          
        function verDetalle(row){
          $("#showProductosNota").updateGridsSizes();$("#showPagosChe").updateGridsSizes();
          $("#showProductosNota").jqGrid("clearGridData").trigger("reloadGrid");
          $('#verDetalleNota').dialog('open');
          $.post( "", {getDetalleNota:true,Vet_Cod:row['Vet_Cod']}, function(responce) 
          {
                if(responce['success']===true)
                {
                  for(let i=0;i<responce['data'].length;i++){
                    let ids_pg= $('#showProductosNota').jqGrid('getDataIDs').length+1;
                    $('#showProductosNota').jqGrid('addRowData', ids_pg,
                        {
                            index:ids_pg,
                            Ite_Lar:responce['data'][i].Ite_Lar,
                            Vet_Can:responce['data'][i].Vet_Can,
                            Vet_Pru:responce['data'][i].Vet_Pru,
                            Vet_Imp:responce['data'][i].Vet_Imp
                        },"last");
                  }

                  $('#showProductosNota').jqGrid('footerData', 'set', {
                    Ite_lar:"<div style='text-align:right;'>TOTALES:</div>",
                    Vet_Imp: $('#showProductosNota').jqGrid('getCol', 'Vet_Imp', true, 'sum')
                  },true);

                  $("#showProductosNota").updateGridsSizes();
                }
                else
                {
                    console.log(responce['message']);
                }
            },'json').fail(function(error) {
                console.log("El Servidor ha fallado en responder!");
            });
        }
    </script>



    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</BODY>
</HTML>