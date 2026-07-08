<?php
/**
 * @abstract Permite realizar el registro de un proceso de facturaciï¿½n de viajes
 * @author Josï¿½ Ambuludï¿½
 * @version 2.0
 * Fecha de creaciï¿½n  2017-01-30
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tca_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_viajeFactura($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_viajeFactura;

$hoy = date("Y-m-d");

//Secciï¿½n para cargar la configuraciï¿½n de la facturaciï¿½n
if (isset($cargarConfi)) {
    $response = $obBD_con1->getRowConsulta(17,$Ses_Emp_Cod, $obBD_conexion);
    $rs_perio=$obBD_con1->getArrayConsulta(16,$Ses_Emp_Cod, $obBD_conexion);
    $rs_forma=$obBD_con1->getArrayConsulta(11,"", $obBD_conexion);
    $rs_fpago=$obBD_con1->getArrayConsulta(14,"", $obBD_conexion);
    $rs_tipoc=$obBD_con1->getArrayConsulta(3 ,"", $obBD_conexion);
    $rs_banko=$obBD_con1->getArrayConsulta(26,"", $obBD_conexion);
    $rs_ivass=$obBD_con1->getArrayConsulta(6 ,"", $obBD_conexion);
    $a=1;foreach ($rs_perio as $row){($a==1)?$sel='selected':$sel='';$a++;$Pec_Cod.="<option value=".$row['Pec_Cod']." data-inicio=".$row['Pec_Fei']." data-fin=".$row['Pec_Fef']." $sel>".$row['Anio']."</option>";}
    $b=1;foreach ($rs_forma as $row){($b==1)?$sel='selected':$sel='';$b++;$For_Cod.="<option value=".$row['For_Cod']." $sel>".mb_convert_encoding($row['For_Des'], 'UTF-8', 'ISO-8859-1')."</option>";}
    $c=1;foreach ($rs_fpago as $row){($c==1)?$sel='selected':$sel='';$c++;$Tpc_Cod.="<option value=".$row['Tpc_Cod']." $sel>".$row['Tpc_Des']."</option>";}
    $d=1;foreach ($rs_tipoc as $row){($d==1)?$sel='selected':$sel='';$d++;$Tic_Cod.="<option value=".$row['Tic_Cod']." $sel>".$row['Tic_Des']."</option>";}
    $e=1;foreach ($rs_banko as $row){($e==1)?$sel='selected':$sel='';$e++;$Bak_Cod.="<option value=".$row['Bak_Cod']." $sel>".$row['Bak_Des']."</option>";}
    $f=1;foreach ($rs_ivass as $row){($f==1)?$sel='selected':$sel='';$f++;$Iva_Cod.="<option value=".$row['Iva_Cod']." data-iva=".$row['Iva_Por']." $sel>".$row['Iva_Por']."</option>";}
    $response["Pec_Cod_html"]=$Pec_Cod;
    $response["For_Cod_html"]=$For_Cod;
    $response["Tpc_Cod_html"]=$Tpc_Cod;
    $response["Tic_Cod_html"]=$Tic_Cod;
    $response["Bak_Cod_html"]=$Bak_Cod;
    $response["Iva_Cod_html"]=$Iva_Cod;
	utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

//Secciï¿½n para cargar datos en el Jqgrid referente a los clientes registrados
if (isset($clienteAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(1, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(1, $data, $obBD_conexion);
    }
	utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

//Sección para cargar datos en el Jqgrid referente a los clientes registrados
if (isset($clientefacturaAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(33, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(33, $data, $obBD_conexion);
    }
	utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

//Sección para cargar los viajes sin facturar de un cliente seleccionado
if(isset($cargarViajes)){
    $response=$obBD_con1->getArrayConsulta(2,$Cli_Cod,$obBD_conexion);
	utf8_encode_deep($response);	
    echo json_encode($response);
    exit();
}

//Secciï¿½n para cargar los tipos de pago
if (isset($cargar_tipoP)) {
    $rs_tpago = $obBD_con1->getArrayConsulta(12, $For_Cod, $obBD_conexion);
    $a=1;foreach ($rs_tpago as $row){($a==1)?$sel='selected':$sel='';$a++;$Pag_Cod.="<option value=".$row['Pag_Cod']." $sel>".$row['Pag_Des']."</option>";}
    $response["Pag_Cod_html"]=$Pag_Cod;
    echo json_encode($response);
    exit();
}

//Secciï¿½n para cargar los bancos del plan de cuentas
if (isset($cargarBancos)) {
    $rs_banco = $obBD_con1->getArrayConsulta(13, $Pag_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
    $a=1;foreach ($rs_banco as $row){($a==1)?$sel='selected':$sel='';$a++;$Ban_Cod.="<option value=".$row['Ban_Cod']." $sel>".$row['Pld_Des']."</option>";}
    $response["Ban_Cod_html"]=$Ban_Cod;
    echo json_encode($response);
    exit();
}

//Secciï¿½n para cargar las cuentas deudoras
if (isset($cargarCtad)) {
    $rs_ctadeudora = $obBD_con1->getArrayConsulta(24, $Pec_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
    $a=1;foreach ($rs_ctadeudora as $row){($a==1)?$sel='selected':$sel='';$a++;$Pld_Cod.="<option value=".$row['Pld_Cod']." $sel>".$row['Pld_Des']."</option>";}
    $response["Pld_Cod_html"]=$Pld_Cod;
    echo json_encode($response);
    exit();
}

//Secciï¿½n para obtener el nï¿½mero de secuencia
if(isset($numeroSec)){
    $response = $obBD_con1->getRowConsulta(4,$Ses_Prs_Cod.'*'.$Ses_Suc_Cod.'*'.$Tic_Cod,$obBD_conexion);
    $siguiente=$obBD_con1->getRowConsulta(25,$response['Aut_Ini'].'*'.$response['Aut_Fin'].'*'.$response['Aut_Sri'].'*'.$Tic_Cod.'*'.$Ses_Suc_Cod,$obBD_conexion);
    $response['Num_Sig']=$siguiente['siguiente'];
    echo json_encode($response);
    exit();
}

//Secciï¿½n para verificar si el nï¿½mero de secuencia ya esta registrado
if(isset($verificarNrosecuencia)){
    $response=$obBD_con1->getRowConsulta(37,$Ses_Prs_Cod.'*'.$Ses_Suc_Cod.'*'.$Tic_Cod.'*'.$Vet_Num, $obBD_conexion);
    if(!empty($response['Vet_Num'])){
        $response['existe']=true;
    }else{$response['existe']=false;}
    echo json_encode($response);
    exit();
}

//Secciï¿½n para verificar si el nï¿½mero de secuencia ya esta registrado
if(isset($verificarViajes)){
    $cont=0;
    foreach ($Det_Fac as $row){
        $response=$obBD_con1->getRowConsulta(57,$row['Via_Cod'], $obBD_conexion);
        if($response['Existe']=="SI"){ $cont++;}
    }
    $response['existe']=$cont;
    echo json_encode($response);
    exit();
}

//Secciï¿½n para guardar una factura
if(isset($saveFactura)){
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_conexionIns = new Class_Log_Conexion_viajeFactura($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_viajeFactura;
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
    
    try {
        //Secciï¿½n para extraer el Pun_Cod y Vnd_Cod del usuario sobre la tabla vendedor
        $rs_Punto = $obBD_con1->getRowConsulta(7,$Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
        
        //Secciï¿½n para verificar si la caja ya fue aperturada
        $rs_Caja = $obBD_con1->getRowConsulta(28,$rs_Punto['Pun_Cod'].'*'.$Fac_Fec, $obBD_conexion);
        if(empty($rs_Caja['Caj_Cod'])){
            //Secciï¿½n para aperturar la caja a travï¿½s de insert a la tabla caja_aper
            $obBD_conIns->operacionobBD(8,$rs_Punto['Pun_Cod'].'*'.$Fac_Fec, $obBD_conexionIns);
            //Secciï¿½n para obtener el id ingresado en la tabla caja_aper
            $Caj_Cod=$obBD_conIns->insercionid($obBD_conexionIns->conexion);
        }else{
            $Caj_Cod=$rs_Caja['Caj_Cod'];
        }

        //Secciï¿½n para efectuar un insert en la tabla ventas
        $obBD_conIns->operacionobBD(9,$Aut_Cod.'*'.$Tic_Cod.'*'.$Cli_Cod.'*'.$Ciu_Cod.'*'.$Caj_Cod.'*'.$rs_Punto['Vnd_Cod'].'*'.$Vet_Num.'*'.$vet_des.'*'.$Vet_Obs.'*'.$Tpc_Cod,$obBD_conexionIns);
        //Secciï¿½n para obtener el id ingresado en la tabla ventas
        $Vet_Cod=$obBD_conIns->insercionid($obBD_conexionIns->conexion);
        $response['Vet_Cod']=$Vet_Cod;

        //Secciï¿½n para insertar en la tabla ventas_det
        $Vet_Ite=1;
        foreach ($Det_Fac as $row){
            $obBD_conIns->operacionobBD(10,$Vet_Ite.'*'.$Vet_Cod.'*'.$row['Pro_Cod'].'*'.$row['Iva_Cod'].'*'.$row['Vet_Can'].'*'.$row['Vet_Pru'].'*'.$row['Vet_Imp'].'*'.$row['Vet_Ice'],$obBD_conexionIns);
            $Vet_Ite++;
        }
        
        //Secciï¿½n para efectuar un UPDATE sobre la tabla viaje
        foreach ($Det_Fac as $row){
            $obBD_conIns->operacionobBD(27,$Vet_Cod.'*'.$row['Iva_Cod'].'*'.$row['Via_Cod'],$obBD_conexionIns);
        }
        
        if($Cof_Con=='S'){
            //Secciï¿½n para guardar en la tabla comprobantes
            //Consulta el numero del comprobante de Egreso/Diario 
            $meseCom = explode('-', $Fac_Fec);
            $Com_Num= $obBD_con1->codigoComprAuto(7, $Pec_Cod, $meseCom[1], $obBD_conexion); 
            $obBD_conIns->operacionobBD(18,$Pec_Cod.'*'.$Cli_Cod.'*'.$Ses_Usu_Cod.'*'.$Com_Num.'*'.$Fac_Fec.'*'.$Vet_Obs.'*'.$Vet_Tot.'*'.$Vet_Obs.'*7',$obBD_conexionIns);
            $Com_Cod=$obBD_conIns->insercionid($obBD_conexionIns->conexion);
            $response['Com_Cod']=$Com_Cod;
            //Secciï¿½n para guardar el asiento del total la factura DEBE
            if($For_Cod*1==1){//Indica que la forma de pago es al contado
                $Pld_Cod_Bco = $obBD_con1->getRowConsulta(21,$Ban_Cod,$obBD_conexion);
                $Pld_Cod=$Pld_Cod_Bco['Pld_Cod'];
                $Bak_Cod="1";
            }
            $obBD_conIns->operacionobBD(20,$Com_Cod.'*'.'D'.'*'.$Vet_Tot.'*'.'DCTO.:'.$Vet_Num.'*'.$Pld_Cod,$obBD_conexionIns);

            //Secciï¿½n para guardar el asiento del detalle de la factura HABER
            foreach ($Det_Fac as $row){
                $Pld_Cod=$obBD_con1->getRowConsulta(19,$row['Pro_Cod'],$obBD_conexion);
                $obBD_conIns->operacionobBD(20,$Com_Cod.'*'.'H'.'*'.$row['Vet_Imp'].'*'.$row['Ite_Lar'].'*'.$Pld_Cod['Pld_Cod'],$obBD_conexionIns);
            }
            
            //Secciï¿½n para insertar en la tabla ccpp_cobrar
            if($For_Cod*1==2){//Indica que la forma de pago es a crï¿½dito
                $obBD_conIns->operacionobBD(23,$Com_Cod.'*'.$Vet_Cod.'*'.$Cpc_Ven.'*'.$Vet_Obs,$obBD_conexionIns);
                $Ban_Cod="";
            }

            //Secciï¿½n para efectuar un insert sobre la tabla ventas_compr
            $obBD_conIns->operacionobBD(29,$Vet_Cod.'*'.$Com_Cod,$obBD_conexionIns);
            
            //Secciï¿½n para insertar el descuento en la tabla asientos
            if($t_descuento*1>0){
                $Pld_Cod_Dsc=$obBD_con1->getRowConsulta(48,"DV", $obBD_conexion);
                if(empty($Pld_Cod_Dsc['Pld_Cod'])){
                    throw new Exception("Falta parametrizar la cuenta contable de DESCUENTO..!!");
                }
                $obBD_conIns->operacionobBD(20,$Com_Cod.'*'.'D'.'*'.$t_descuento.'*'.'ASIENTO DE DESCUENTO'.'*'.$Pld_Cod_Dsc['Pld_Cod'],$obBD_conexionIns);
            }
            
            //Secciï¿½n para insertar el ice en la tabla asientos
            if($t_ice*1>0){
                $Pld_Cod_Ice=$obBD_con1->getRowConsulta(48,"ICV", $obBD_conexion);
                if(empty($Pld_Cod_Ice['Pld_Cod'])){
                    throw new Exception("Falta parametrizar la cuenta contable de tipo ICE..!!");
                }
                $obBD_conIns->operacionobBD(20,$Com_Cod.'*'.'H'.'*'.$t_ice.'*'.'ASIENTO DE ICE'.'*'.$Pld_Cod_Ice['Pld_Cod'],$obBD_conexionIns);
            }

            //Secciï¿½n para guardar asiento del iva en caso de que este sea mayor a cero
            if(isset($t_iva)&&($t_iva*1)>0){
                $Pld_Cod_Iva = $obBD_con1->getRowConsulta(22,$Pec_Cod,$obBD_conexion);
                if(empty($Pld_Cod_Iva['Pld_Cod'])){
                    throw new Exception("Falta parametrizar el IVA cobrado..!!");
                }
                $obBD_conIns->operacionobBD(20,$Com_Cod.'*'.'H'.'*'.$t_iva.'*'.'ASIENTO DE IVA'.'*'.$Pld_Cod_Iva['Pld_Cod'],$obBD_conexionIns);
            }
        }   
        
        //Secciï¿½n para efectuar un insert en la tabla pago_venta
        $obBD_conIns->operacionobBD(15,$Vet_Cod.'*'.$Bak_Cod.'*'.$Ban_Cod.'*'.$Pag_Cod.'*'.$Vet_Cue.'*'.$Vet_Che.'*'.$Vet_Tot,$obBD_conexionIns);
        
        //Seccion para efectuar la imprecion de documentos: factura, nota de venta, comprobante, etc.
        $pagina = 'fac_alt_fac_ven_manual';
        $imprimir = $obBD_con1->reportes($pagina, $Ses_Emp_Cod, $obBD_conexion);
        $url_fac=$imprimir[1].'?Vet_Cod='.$Vet_Cod;
        $response['url_fac']=$url_fac;
        $url_com=$imprimir[2].'?codigo='.$Com_Cod.'&tabla=cliente&campo=Cli_Cod&tipo=7&Pec_Cod='.$Pec_Cod;
        $response['url_com']=$url_com;
        $url_fac=$imprimir[3].'?Vet_Cod='.$Vet_Cod;
        $response['url_gru']=$url_fac;
        //Sección para imprimir detalle de factura
        $response['url_dfa']='tca_pri_detfactura_1.0.php?Vet_Cod='.$Vet_Cod;
        
    } catch (Exception $e) {
        mysqli_rollback($obBD_conexionIns->conexion); $response['message']=$e->getMessage(); echo json_encode($response); exit();
    }
    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
    if ($obBD_conIns->Error == 0) {$response['success'] = true;}
    else{$response=array(success=>false,message=>"No se ha logrado realizar la Transaccion",error=>$obBD_ins1->MsgError);}
    echo json_encode($response);
    exit();
}
if(isset($cargarAsiento)){
    //Seccion para obtener los datos de la cabecera del comprobante
    $cab_com=$obBD_con1->getRowConsulta(50,$Com_Cod,$obBD_conexion);
    $response['cab_com']=$cab_com;

    //Seccion para obtener los datos de los asientos concernientes al comprobante
    $det_asi=$obBD_con1->getArrayConsulta(49,$Com_Cod,$obBD_conexion);
    $response['det_asi']=$det_asi;
    
    $cab_fac=$obBD_con1->getRowConsulta(31,$Ses_Emp_Cod.'*'.$Vet_Cod, $obBD_conexion);
    $response['cab_fac']= $cab_fac;
    
    $detalle=$obBD_con1->getArrayConsulta(32,$Vet_Cod,$obBD_conexion);
    $response['det_fac']=$detalle;
    
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script language="javascript" src="../VALIDACIONES/tca_factura.js"></script>
        <style>
            .footrow td[aria-describedby="documento_Cop_Imp"],.footrow td[aria-describedby="documento_Cop_Pru"]{padding: 0 !important;}
            .footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact textarea{text-align: left; height: 75px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}
            #jqGridButtonDiv{float:right; padding-right:10px; position:relative; top:-1px;}
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Factura</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="reg_fac">
                <form id="frm_cab" name="frm_cab" class="form-horizontal normal formulario" action="javascript:">
                    <!--Campo de cï¿½digo de cliente-->
                    <input type="hidden" id="Cli_Cod" name="Cli_Cod" value="0">
                    <!--Campo de cï¿½digo de la tabla autorizaci-->
                    <input type="hidden" id="Aut_Cod" name="Aut_Cod">
                    <!--Campo que indica si lleva contabilidad o no-->
                    <input type="hidden" id="Cof_Con" name="Cof_Con">
                    <div class="row">
                        <div class="col-md-5">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos de Cliente</legend>
                                <div class="col-md-12 col-sm-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm required">C&eacute;dula/R.U.C.:</label>
                                        <div class="col-md-7 col-sm-7">
                                            <div class="input-group">
                                                <input type="text" id="Prs_Ced" name="Prs_Ced" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="$('#clienteDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-xs">Cliente:</label>
                                        <div class="col-md-7 col-sm-7">
                                            <input type="text" id="cliente" name="cliente" class="form-control input-xs" readonly="">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-xs">Facturar a:</label>
                                        <div class="col-md-7 col-sm-7">
                                            <div class="input-group">
                                                <input type="text" id="Prs_Ced1" name="Prs_Ced1" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-xs" type="button" title="Cambiar Cliente" onclick="$('#clientefacturaDialog').dialog('open');"><span class="glyphicon glyphicon-user"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-xs">Cliente:</label>
                                        <div class="col-md-7 col-sm-7">
                                            <input type="text" id="cliente1" name="cliente1" class="form-control input-xs" readonly="">
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-md-7">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos Encabezado Factura</legend>
                                <div class="col-md-12 col-sm-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-5 label-sm required">Tipo Dcto.:</label>
                                        <div class="col-md-3 col-sm-7">
                                            <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs" required=""></select>
                                        </div>
                                        <div id="periodo" style="display: none;">
                                            <label class="control-label col-md-2 col-sm-4 label-xs">Periodo:</label>
                                            <div class="col-md-3 col-sm-7">
                                                <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs"></select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-xs">Fecha:</label>
                                        <div class="col-md-3 col-sm-7">
                                            <input type="text" id="Fac_Fec" name="Fac_Fec" class="form-control input-xs datepicker">
                                        </div>
                                        <label class="control-label col-md-2 col-sm-4 label-xs">Ciudad:</label>
                                        <div class="col-md-3 col-sm-7">
                                            <?php $Ciu_Des=$obBD_con1->getRowConsulta(5, $Ses_Usu_Cod, $obBD_conexion);?>
                                            <input type="hidden" id="Ciu_Cod" name="Ciu_Cod" value="<?php echo $Ciu_Des['Ciu_Cod']?>">
                                            <input type="text" id="Ciu_De1" name="Ciu_De1" class="form-control input-xs" readonly="" value="<?php echo $Ciu_Des['Ciu_Des']?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-xs">Nro. Secuencia:</label>
                                        <div class="col-md-3 col-sm-7">
                                            <input type="text" id="Vet_Num" name="Vet_Num" class="form-control input-xs" onkeypress="return validar_numeric(event);">
                                        </div>
                                        <label class="control-label col-md-2 col-sm-4 label-xs">Autorizaci&oacute;n:</label>
                                        <div class="col-md-3 col-sm-7">
                                            <input type="text" id="Aut_Sri" name="Aut_Sri" class="form-control input-xs" readonly="">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-xs">Observaci&oacute;n:</label>
                                        <div class="col-md-8 col-sm-7">
                                            <textarea id="Vet_Obs" name="Vet_Obs" class="form-control input-xs" style="resize: none;"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </form>
                    <form id="frm_fpa" name="frm_fpa" class="formulario" action="javascript:">
                    <div class="row">
                        <div class="col-md-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Forma de Pago</legend>
                                <div id="tpa_com" class="form-group col-md-2" style="display: none;">
                                    <label class="control-label label-xs" style="font-size: 11px;">Tipo:</label>
                                    <select id="Tpc_Cod" name="Tpc_Cod" class="form-control input-xs"></select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="control-label label-xs" style="font-size: 11px;">Forma:</label>
                                    <select id="For_Cod" name="For_Cod" class="form-control input-xs"></select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="control-label label-xs" style="font-size: 11px;">Tipo:</label>
                                    <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs"></select>
                                </div>
                                <div id="Con_Cue">
                                    <div class="form-group col-md-2">
                                        <label class="control-label label-xs" style="font-size: 11px;">Cuenta:</label>
                                        <select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs"></select>
                                    </div>
                                </div>
                                <div id="Cre_Bak" style="display: none;">
                                    <div class="form-group col-md-2">
                                        <label class="control-label label-xs" style="font-size: 11px;">Banco:</label>
                                        <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs"></select>
                                    </div>
                                </div>
                                <div id="Con_Che" style="display: none;">
                                    <div class="form-group col-md-2">
                                        <label class="control-label label-xs" style="font-size: 11px;">Nro. Cuenta:</label>
                                        <input type="text" id="Vet_Cue" name="Vet_Cue" class="form-control input-xs">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label class="control-label label-xs" style="font-size: 11px;">Nro. Cheque:</label>
                                        <input type="text" id="Vet_Che" name="Vet_Che" class="form-control input-xs">
                                    </div>
                                </div>
                                <div id="Cre_Dto" style="display: none;">
                                    <div class="form-group col-md-2">
                                        <label class="control-label label-xs" style="font-size: 11px;">Cta. Deudora:</label>
                                        <select id="Pld_Cod" name="Pld_Cod" class="form-control input-xs"></select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label class="control-label label-xs" style="font-size: 11px;">Fecha Vencimiento:</label>
                                        <input type="text" id="Cpc_Ven" name="Cpc_Ven" class="form-control input-xs datepicker" placeholder="Seleccione fecha">
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </form>
                <div class="row">
                    <div class="col-md-12">
                        <table id="Det_Fac"></table>
                    </div>
                </div>
                <div style="padding-top: 5px;" >
                    <button type="button" onclick="saveFactura();" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
                </div>
                </div>
                <div id="imp_asi" style="display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div style="text-align: center;" >
                                <h3><b>Transaccion Realizada con &Eacute;xito</b></h3>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div style="padding: 5px; text-align: center;" >
                                <button type="button" onclick="$('#imp_asi').moveComp('#reg_fac');$('#Det_Fac').jqGrid('resizeGrid');" class="btn btn-success btn-sm"><i class="fa fa-file" aria-hidden="true"></i> Nueva Factura</butto>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Factura de Venta</legend>
                                <div class="row">
                                    <div class="col-md-12">
                                        <form id="frm_cfa" name="frm_cfa" class="form-horizontal normal">
                                            <div class="form-group">
                                                <label class="control-label col-sm-2 label-xs">C&eacute;dula/R.U.C.:</label>
                                                <div class="col-sm-4">
                                                    <span name="Prs_Ced" class="form-control input-xs datatitle"></span>
                                                </div>
                                                <label class="control-label col-sm-3 label-xs">Tipo Dcto.:</label>
                                                <div class="col-sm-3">
                                                    <span name="Tic_Des" class="form-control input-xs datatitle"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-sm-2 label-xs">Cliente:</label>
                                                <div class="col-sm-4">
                                                    <span name="cliente" class="form-control input-xs datatitle"></span>
                                                </div>
                                                <label class="control-label col-sm-3 label-xs">Nro.:</label>
                                                <div class="col-sm-3">
                                                    <span name="Vet_Num" class="form-control input-xs datatitle"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-sm-2 label-xs">Ciudad:</label>
                                                <div class="col-sm-4">
                                                    <span name="Ciu_De1" class="form-control input-xs datatitle"></span>
                                                </div>
                                                <label class="control-label col-sm-3 label-xs">Fecha:</label>
                                                <div class="col-sm-3">
                                                    <span name="Caj_Fec" class="form-control input-xs datatitle"></span>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <table id="Imp_Fac"></table>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-md-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Resultado de Comprobante</legend>
                                <div class="row">
                                    <div class="col-md-12 col-sm-12">
                                        <form id="frm_cco" name="frm_cco" class="form-horizontal normal">
                                            <div class="form-group">
                                                <label class="control-label col-sm-2 label-xs">Tipo:</label>
                                                <div class="col-sm-3">
                                                    <span name="Tia_Des" class="form-control input-xs datatitle"></span>
                                                </div>
                                                <label class="control-label col-sm-3 label-xs">Fecha:</label>
                                                <div class="col-sm-3">
                                                    <span name="Com_Fec" class="form-control input-xs datatitle"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-sm-2 label-xs">Nro.:</label>
                                                <div class="col-sm-3">
                                                    <span name="Nro_Com" class="form-control input-xs datatitle"></span>
                                                </div>
                                                <label class="control-label col-sm-3 label-xs">Valor:</label>
                                                <div class="col-sm-3">
                                                    <span name="Com_Val" class="form-control input-xs datatitle"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-sm-2 label-xs">Concepto:</label>
                                                <div class="col-sm-9">
                                                    <span name="Com_Con" class="form-control input-xs datatitle"></span>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <table id="Imp_Asi"></table>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>    
                </div>
            </div>
        </div>
        <!-- Inicio del diï¿½logo para buscar un cliente inmerso en la tabla viaje -->
        <div id="clienteDialog" title="B&uacute;squeda de Clientes">
            <form class="form-horizontal normal"></form>
        </div>
        <!-- Inicio del diï¿½logo para buscar un cliente y cambiarlo al momento de realizar la factura -->
        <div id="clientefacturaDialog" title="B&uacute;squeda de Clientes">
            <form class="form-horizontal normal"></form>
        </div>
        <script type="text/javascript">
            $(function () {
                //Inicializaciï¿½n
                cargarDatos();
                
                //Inicio del diï¿½logo para presentar clientes
                $.createSearchDialog('#clienteDialog', [
                    {label: 'Cï¿½d.Int.', name: 'Cli_Cod', key: true, hidden: true},
                    {label: 'C&eacute;dula', name: 'Prs_Ced', width: 30},
                    {label: 'Cliente', name: 'cliente', width: 70},
                    {label: 'Viajes sin Facturar', name: 'Via_Con', width: 40, align: 'center'},
                    {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            return $.getGridButton(cargarCliente, rowObject,'Seleccionar Cliente','glyphicon glyphicon-ok');
                        }
                    }
                ], null, null, null, null, {title: 'Clientes', options: [{label: '&nbsp;&nbsp;Apellido&nbsp;&nbsp;', value: 'd'},
                {label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c'}]});

                //Change para los tipos de pago
                $('#Pag_Cod').change(function(){
                    (this.value==='3')?$('#Con_Che').show():$('#Con_Che').hide();
                    cargarBancos(this.value);
                });
                
                //Capturar evento cuando termina de escribir en el input Fac_Fec
                $("#Fac_Fec").change(function(){
                    if((this.value>aut_cad)||(this.value<aut_fci)){
                       $.alert('La fecha '+this.value+' debe constar dentro del rango estipulado por la autorizaci&oacute;n , cuyas fechas de inicio y fin son: ('+aut_fci+' y '+aut_cad+')'); 
                    }
                });
                
            });
            
            /*** FUNCIONES PARA EL MANEJO DE DATOS ***/
            
            //Funciï¿½n para cargar la configuraciï¿½n de la factura tabla confi_fact para saber si la empresa lleva o no contabilidad
            function cargarDatos(){
                $.post("<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING);?>",{cargarConfi:true},function(response){
                    if(response.Cof_Con==='S'){$('#periodo').show();}
                    $("[id^='frm_']").setData(response,false);
                    $('#Fac_Fec').dateLimits($('#Pec_Cod').find('option:selected').data('inicio'),$('#Pec_Cod').find('option:selected').data('fin'));
                    cargar_tipoPago($('#For_Cod').val());
                    numSecuencia($('#Tic_Cod').val());
                    $('.iva_por').text($( "#Iva_Cod option:selected" ).text());
                },'json').fail(function(){$.alert();});
            }
            
            //Función para guardar una factura de venta
            function saveFactura() {
                var data=$("[id^='frm_']").getData('saveFactura');
                data['Det_Fac']=$("#Det_Fac").getGridBatch();
                if($("#Cli_Cod").val()==='0'){$.alert('Debe seleccionar un cliente..!!');return;}
                if(arreglo.length<=0){$.alert('Debe agregar items al detalle de factura..!!');return;}
                
                //Se verifica que el número de documento no se repita
                var data1={verificarNrosecuencia:true,Vet_Num:$('#Vet_Num').val(),Tic_Cod:$('#Tic_Cod').val()};
                $.post("<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING);?>",data1,function(response){
                    if(response['existe']===true){
                        $.alert('N&uacute;mero de secuencia '+$('#Vet_Num').val()+' ya se encuentra registrado..!!');
                    }else{
                        //Se verifica que no registre un viaje dos veces
                        var data2={verificarViajes:true,Det_Fac:$("#Det_Fac").getGridBatch()};
                        $.post("<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING);?>",data2,function(response){
                            if(response['existe']*1>0){
                                $.alert('Algunos viajes ya se encuentran registrados..!!');
                            }else{
                                $.saveDataJson("<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", data, function (response) {
                                    $('.formulario')[0].reset();
                                    $('#frm_dat')[0].reset();
                                    $('#Fac_Fec').val('<?php echo $hoy; ?>');
                                    $("#Det_Fac").setRowsByIndex("");
                                    $("#clienteDialog").getDialogGrid().trigger('reloadGrid', [{page: 1}]);
                                    $("[id^='Cre_']").hide();$('#Con_Cue').show();cargar_tipoPago($('#For_Cod').val());
                                    $('#tpa_com').hide();
                                    numSecuencia($('#Tic_Cod').val());
                                    $('#imprimir_fac').data('url_fac',response['url_fac']);
                                    $('#imprimir_dfa').data('url_dfa',response['url_dfa']);
                                    $('#imprimir_com').data('url_com',response['url_com']);
                                    $('#imprimir_gru').data('url_gru',response['url_gru']);
                                    asientos(response['Com_Cod'],response['Vet_Cod']);
                                    $('#reg_fac').moveComp('#imp_asi');
                                    return false;
                                });
                            }
                        },'json').fail(function(){$.alert();});
                    }
                },'json').fail(function(){$.alert();});
            }
            
        </script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    </BODY>
</HTML>


