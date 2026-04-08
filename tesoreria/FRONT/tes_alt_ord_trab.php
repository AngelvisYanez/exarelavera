<?php
/**
 * @abstract Permite realizar el registro de un proceso de facturaciï¿½n de viajes
 * @author Erick Cordova
 * @version 2.0
 * Fecha de creación  2017-07-25
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_ord_trab.php');
require_once('../../tesoreria/LOGICA/tes_log_cccc_lotes_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');



/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Inventario($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Logica_Inventario;
//borrar debug completo
//$obBD_con1->echoLog($obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion));
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

//conexion para anticipos de clientes!
$obBD_conexion_get = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
$obBD_con_get =  new Class_Log_Datos_Cccc;

if(isset($getDateServ)){
    $resp['hoy']=date("Y-m-d");
    $obBD_con1->echoJson($resp);
}

//Sección para listar los clientes registrados en la empresa
if(isset($clieAjax)){
    $response=$obBD_con1->getPageGrid(1, $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);
    $obBD_con1->echoJson($response);
}

/* ver si exite un cliente */
if(isset($searchCliente)){
    $responce = $obBD_con1->getRowConsulta(17, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con1->getRowConsulta(18, $responce['Prs_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Cli_Cod']))?$responce['existe']=true:$responce['existe']=false;
    $obBD_con1->echoJson($responce);
}

/* guarda un nuevo cliente */
if(isset($guardaClieAjax)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $data['Cli_Cor']=$data['Cli_Cor'];
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        if(empty($Prs_Cod)){
            $obBD_con1->operacionobBD(3,$data,$obBD_conexion);
            $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
        }else{
            $pers=$obBD_con1->getRowConsulta('persona.selectWhere',array('clean'=>true, 'Prs_Cod'=>$Prs_Cod),$obBD_conexion);
            if(empty($Prs_Cor)&&!empty($pers['Prs_Cor'])) $data['Cli_Cor']=$pers['Prs_Cor'];
            $up=array();
            if(!empty($Prs_Cor)&&empty($pers['Prs_Cor'])) $up['Prs_Cor']=$Prs_Cor;
            if(!empty($Prs_Dir)&&empty($pers['Prs_Dir'])) $up['Prs_Dir']=$Prs_Dir;
            if(!empty($Prs_Tel)&&empty($pers['Prs_Tel'])) $up['Prs_Tel']=$Prs_Tel;

            if(!empty($up)) $obBD_con1->operacionobBD('persona.update',array_merge($up,array('where'=>array('Prs_Cod'=>$Prs_Cod))),$obBD_conexion);
        }
        $obBD_con1->operacionobBD(4,$data,$obBD_conexion);
        $data['Cli_Cod'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
        $data['cliente'] = trim($data['Prs_Ape'].' '.$data['Prs_Nom']);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true,'clie'=>$data);
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if(isset($saveMedAjax)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    //$data['Med_Nom']=$data['Prs_Cor'];
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(5,$data,$obBD_conexion);
    $data['Med_Cod'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true,'clie'=>$data);
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if(isset($proAjax)){
    $responce['rows'] = $obBD_con1->getArrayConsulta(13, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

if(isset($numSec)){
    $data=$_GET;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $maximo = $obBD_con1->getRowConsulta(14, $data, $obBD_conexion);
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true,'Ord_Num'=>$maximo['numero']);
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if(isset($fichaAjax)){
    $data=$_GET;
    $data['search']=$search;
    $data['Emp_Cod']=$Ses_Emp_Cod; 
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true);
        $responce['rows'] = $obBD_con1->getArrayConsulta(15, $data, $obBD_conexion);
        $responce['total']=10;
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($responce);
    exit();
}

if(isset($saveDocument)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod; 
    $data['Suc_Cod']=$Ses_Suc_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $responce = $obBD_con1->operacionobBD(154, $data, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true,'message'=>'Transacción realizada con Exito!');
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if(isset($cargarReportes)){
    $responce['success']=false;
    $table['{empresa}']=$Ses_Emp_Nom;
    $_POST['Suc_Cod']=$Ses_Suc_Cod;
    //ChromePhp::log($table['{empresa}']);
    $_POST['Emp_Cod']=$Ses_Emp_Cod; 

    //ENCABEZADO
    $responce['cabecera'] = $obBD_con1->getArrayConsulta(12, $_POST, $obBD_conexion_get);
    $cabecera = $responce['cabecera'];
    $table['{ruc}']=$cabecera[0]['Emp_Ruc'];
    $table['{sucursal}']=$cabecera[0]['Suc_Des'];
    $table['{tel_suc}']=$cabecera[0]['Suc_Te1'];
    $table['{dir_suc}']=$cabecera[0]['Suc_Dir'];
    $table['{cor_suc}']=$cabecera[0]['Suc_Cor'];
    $table['{fichaNum}'] = $cabecera[0]['Ord_Num'];
    $table['{fecCon}'] = $cabecera[0]['Ord_Fec'];
    $table['{cliente}'] = $cabecera[0]['cliente'];
    $table['{cedula}'] = $cabecera[0]['Prs_Ced'];
    $table['{sexo}'] = $cabecera[0]['Prs_Sex'];
    $table['{direccion}'] = $cabecera[0]['Prs_Dir'];
    $table['{correo}'] = $cabecera[0]['Prs_Cor'];
    $table['{termino}'] = $cabecera[0]['Ord_Ter'];
    $table['{facturara}'] = $cabecera[0]['Ord_Cli'];
    $table['{telefono}'] = $cabecera[0]['Prs_Tel'];
    $table['{modelo}'] = $cabecera[0]['Ord_Mod'];
    $table['{anio}'] = $cabecera[0]['Ord_Ani'];
    $table['{color}'] = $cabecera[0]['Ord_Col'];
    $table['{placa}'] = $cabecera[0]['Ord_Pla'];
    $table['{vin}'] = $cabecera[0]['Ord_Vin'];
    $table['{motor}'] = $cabecera[0]['Ord_Mot'];
    $table['{kms}'] = $cabecera[0]['Ord_Kms'];
    $table['{asesor}'] = $cabecera[0]['Ord_Ase'];
    $table['{observaciones}'] = $cabecera[0]['Ord_Obs'];
    $table['{antena}'] = $cabecera[0]['Ord_Ant'];if($table['{antena}']=='S'){$table['{antena}']='x';}else{$table['{antena}']='';}
    $table['{radio}'] = $cabecera[0]['Ord_Rad'];if($table['{radio}']=='S'){$table['{radio}']='x';}else{$table['{radio}']='';}
    $table['{plumas}'] = $cabecera[0]['Ord_Plu'];if($table['{plumas}']=='S'){$table['{plumas}']='x';}else{$table['{plumas}']='';}
    $table['{exting}'] = $cabecera[0]['Ord_Ext'];if($table['{exting}']=='S'){$table['{exting}']='x';}else{$table['{exting}']='';}
    $table['{control}'] = $cabecera[0]['Ord_Con'];if($table['{control}']=='S'){$table['{control}']='x';}else{$table['{control}']='';}
    $table['{seguros}'] = $cabecera[0]['Ord_Seg'];if($table['{seguros}']=='S'){$table['{seguros}']='x';}else{$table['{seguros}']='';}
    $table['{signos}'] = $cabecera[0]['Ord_Sig'];if($table['{signos}']=='S'){$table['{signos}']='x';}else{$table['{signos}']='';}
    $table['{encend}'] = $cabecera[0]['Ord_Enc'];if($table['{encend}']=='S'){$table['{encend}']='x';}else{$table['{encend}']='';}
    $table['{moqueta}'] = $cabecera[0]['Ord_Moq'];if($table['{moqueta}']=='S'){$table['{moqueta}']='x';}else{$table['{moqueta}']='';}
    $table['{espejos}'] = $cabecera[0]['Ord_Esp'];if($table['{espejos}']=='S'){$table['{espejos}']='x';}else{$table['{espejos}']='';}
    $table['{combust}'] = $cabecera[0]['Ord_Com'];if($table['{combust}']=='UC'){$table['{combust}']='1/4';}else if($table['{combust}']=='DC'){$table['{combust}']='2/4';}else if($table['{combust}']=='TC'){$table['{combust}']='3/4';}else if($table['{combust}']=='CC'){$table['{combust}']='4/4';}
    $table['{triang}'] = $cabecera[0]['Ord_Tri'];if($table['{triang}']=='S'){$table['{triang}']='x';}else{$table['{triang}']='';}
    $table['{cds}'] = $cabecera[0]['Ord_Cds'];if($table['{cds}']=='S'){$table['{cds}']='x';}else{$table['{cds}']='';}
    $table['{tapac}'] = $cabecera[0]['Ord_Tap'];if($table['{tapac}']=='S'){$table['{tapac}']='x';}else{$table['{tapac}']='';}
    $table['{llantas}'] = $cabecera[0]['Ord_Lla'];if($table['{llantas}']=='S'){$table['{llantas}']='x';}else{$table['{llantas}']='';}
    $table['{gata}'] = $cabecera[0]['Ord_Gat'];if($table['{gata}']=='S'){$table['{gata}']='x';}else{$table['{gata}']='';}
    $table['{herram}'] = $cabecera[0]['Ord_Her'];if($table['{herram}']=='S'){$table['{herram}']='x';}else{$table['{herram}']='';}
    $table['{llave}'] = $cabecera[0]['Ord_Llr'];if($table['{llave}']=='S'){$table['{llave}']='x';}else{$table['{llave}']='';}
    $table['{botiq}'] = $cabecera[0]['Ord_Bot'];if($table['{botiq}']=='S'){$table['{botiq}']='x';}else{$table['{botiq}']='';}
    $table['{tapag}'] = $cabecera[0]['Ord_Tpg'];if($table['{tapag}']=='S'){$table['{tapag}']='x';}else{$table['{tapag}']='';}
    $responce['html']=reporteHtml($table,'tes_alt_orden.html');
    $responce['success']=true;
        
    utf8_encode_deep($responce);        
    echo json_encode($responce);
    exit();
}

if(isset($ajaxCertificado)){
    $responce['success']=false;
    $table['{body}']='';
    //$table['{empresa}']=$Ses_Emp_Nom;
    $fecha=explode('-',$hoy);
    $table['{fecha}']=$fecha[2].' de '.mes($fecha[1],1).' de '.$fecha[0];

    $responce['medicamentos'] = $obBD_con1->getArrayConsulta(16, $_POST, $obBD_conexion_get);
    $medicamentos = $responce['medicamentos'];

    $table{'empresa'} = $medicamentos[0]['Emp_Nom'];
    $table{'ruc'} = $medicamentos[0]['Emp_Ruc'];
    $table{'src'} = $medicamentos[0]['Emp_Log'];
    $table{'tel'} = $medicamentos[0]['Suc_Te1'];
    $table{'dir'} = $medicamentos[0]['Suc_Dir'];
    $table{'ciudad'} = $medicamentos[0]['Ciu_Des'];
    $table{'provincia'} = $medicamentos[0]['Pro_Nom'];
    $table{'pais'} = $medicamentos[0]['Pas_Nom'];

    $responce['html']=reporteHtml($table,'tes_alt_pac_certificado.html');
    $responce['success']=true;
        
    utf8_encode_deep($responce);        
    echo json_encode($responce);
    exit();
}

?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script language="javascript" src="../VALIDACIONES/fic_val_paciente2.js?x=0"></script>

        <script>
        $('.panel-main').hide();
        inicializarDocVenta();
        $('.panel-main').show();
        //setTimeout(function(){ $("#Pec_Cod").trigger('change'); }, 1000);
        var docs, items, pagos, data=[],vet_num_ant=0,tic_cod_ant=0, Vet_Index=1, Vet_Selected, index, Cof_Con='<?php echo $configs['Cof_Con']; ?>';
            <?php $array_documentos=$obBD_con1->getArrayConsulta(8,$rs_Punto['Pun_Cod'],$obBD_conexion);?>
        var array_documentos=<?php echo json_encode($array_documentos);?>, ivas_venta=<?php echo json_encode($ivas)?>;

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
            #Ret_Asu{ vertical-align: middle; margin-top: -2px; padding: 5px;  -ms-transform: scale(1.4); -moz-transform: scale(1.4); -webkit-transform: scale(1.4); -o-transform: scale(1.4); }
            #resultContent .resp{ font-weight: 700; font-size: 30px; color: #3f3fc1; padding: 0; margin: 0; overflow: hidden; text-overflow: ellipsis; height: 32px; }
            #resultContent .resp span:first-child{ color:darkgoldenrod;width: 100px;display: inline-block; margin-left: 42px; }
            .msg_fly { font-size: 12px !important; }
            .ret .input-group-btn button{padding: 1px 2px !important;}
            .ret{ padding: 0 !important;}
            .footrow td[aria-describedby="items_Vet_Pru"], .footrow td[aria-describedby="items_Vet_Imp"]{padding: 0 !important;}
        </style>
    </HEAD>
    <BODY>


        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  ORDEN DE TRABAJO</h3><p id="cabeceraPuntoImp" class="text-right col-xs-12  " style="margin-top:-15px;"></p></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="fichaMedica">
                    <div class="row">
                        <div class="col-xs-12" id="panelFicha" >
                            <div class="row">
                                <form id="fichaForm" name="fichaForm" method="post" class="form-horizontal normal" action="javascript:" >
                                    <div class="col-md-12 col-xs-12">
                                        <fieldset class="exa-fieldset" id="clieFormTemp">
                                            <legend class="Titulos2"></legend>
                                            <div class="form-group">
                                                <label class="col-xs-1 control-label label-xs required">C&eacute;dula/RUC:</label>
                                                <div class="col-xs-2" >
                                                  <input name="Prs_Cod" type="text" style="display:none;" />
                                                  <input name="Prs_Cor" id="Prs_Cor" type="text" style="display:none;" />
                                                  <input id="Cli_Cod" name="Cli_Cod" type="text" style="display:none;" />
                                                  
                                                  <input name="op_opciones" type="text" value="c" style="display: none;">
                                                  <div class="input-group input-group-xs">
                                                      <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#clieDialog',selectCliente);" type="text" placeholder="Ingrese cliente..."  class="form-control input-xs datatrigger clearable dialogSearch" tabindex="1" required="true" />
                                                    <span class="input-group-btn">
                                                        <button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar cliente"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                        <button id="Rgt_Btn" type="button" onclick="$('#clieCreateForm').setData({}).find('.validate').find('i').removeAttr('class'); $('#clieCreateDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Cliente"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>
                                                        <button id="Via_Btn" type="button" onclick="$('#viajesGrid').clearGridData(); $('#viajesDialog').dialog('open');" class="btn btn-success btn-xs viajes" title="Seleccionar Viajes"  tabindex="2" style="display:none;"><span class="fa fa-truck"></span></button>
                                                    </span>
                                                  </div>
                                                </div>
                                                <label class="col-xs-1 control-label label-xs">Cliente:</label>
                                                <div class="col-xs-2" ><span id="cliente" name="cliente" class="form-control input-xs databind datatitle"></span></div>
                                                <label class="col-xs-1 control-label label-xs">Correo:</label>
                                                <div class="col-xs-2" ><span id="Prs_Cor" name="Prs_Cor" type="text" class="form-control input-xs databind datatitle"></span></div>
                                                <label class="col-xs-1 control-label label-xs">Ord. Num:</label>
                                                <div class="col-xs-1" ><div class="input-group input-group-xs"><span class="input-group-addon">OT</span><input id="Ord_Num" name="Ord_Num" type="text" class="form-control input-xs databind datatitle" readonly></div></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-1 control-label label-xs">Facturar a:</label>
                                                <div class="col-xs-2" ><input type="text" id="Ord_Cli" name="Ord_Cli" class="form-control input-xs databind datatitle"></div>
                                                <label class="col-xs-1 control-label label-xs">Direcci&oacute;n:</label>
                                                <div class="col-xs-2" ><span id="Prs_Dir" name="Prs_Dir" class="form-control input-xs databind datatitle"></span></div>
                                                <label class="col-xs-1 control-label label-xs">Fecha:</label>
                                                <div class="col-md-1"><input id="Ord_Fec" name="Ord_Fec" type="text" placeholder="" class="form-control input-xs" required></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-1 control-label label-xs">Term. Pago:</label>
                                                <div class="col-xs-2" ><input name="Ord_Ter" type="text" class="form-control input-xs databind datatitle" style="text-transform:uppercase;"></div>
                                                <label class="col-xs-1 control-label label-xs">Tel&eacute;fono:</label>
                                                <div class="col-xs-2" ><span name="Prs_Tel" type="text" class="form-control input-xs databind datatitle"></span></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-1 control-label label-xs">Modelo:</label>
                                                <div class="col-xs-1" ><input name="Ord_Mod" type="text" class="form-control input-xs databind datatitle" style="text-transform:uppercase;"></div>
                                                <label class="col-xs-1 control-label label-xs">A&ntilde;o:</label>
                                                <div class="col-xs-1 "><input name="Ord_Ani" type="text" class="form-control input-xs databind datatitle" maxlength="4" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;"></div>
                                                <label class="col-xs-1 control-label label-xs">Color:</label>
                                                <div class="col-xs-1 "><input name="Ord_Col" type="text" class="form-control input-xs databind datatitle" style="text-transform:uppercase;"></div>
                                                <label class="col-xs-1 control-label label-xs">Placa:</label>
                                                <div class="col-xs-1 "><input id="Pla_Cod" name="Ord_Pla" type="search" placeholder="AAA-0000" pattern="[A-Z]{3}[0-9]{4}" maxlength="8" class="form-control input-xs databind datatitle" style="text-transform:uppercase;"></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-1 control-label label-xs">VIN:</label>
                                                <div class="col-xs-2" ><input name="Ord_Vin" type="text" class="form-control input-xs databind datatitle" style="text-transform:uppercase;"></div>
                                                <label class="col-xs-1 control-label label-xs">Motor:</label>
                                                <div class="col-xs-1 ">
                                                <select id="Ord_Mot" name="Ord_Mot" class="form-control input-xs" required="" >
                                                    <option value = "T/A" >T/A</option>
                                                    <option value = "T/M" >T/M</option>
                                                    <option value = "4/4" >4/4</option>
                                                    <option value = "4/2" >4/2</option>
                                                </select></div>
                                                <label class="col-xs-2 control-label label-xs">Kms:</label>
                                                <div class="col-xs-1" ><input id="Ord_Kms" name="Ord_Kms" type="number" class="form-control input-xs databind datatitle"></div>
                                            </div>
                                        </fieldset>
                                        <div class="form-group">
                                            <label class="col-xs-1 control-label label-xs">Antena:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Ant" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Radio:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Rad" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Plumas</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Plu" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Extinguidor:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Ext" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Control puerta:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Con" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Seguros aros:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Seg" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Signos:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Sig" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Encendedor:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Enc" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Moqueta:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Moq" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Espejos:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Esp" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Combustible:</label>
                                            <div class="col-xs-1 ">
                                                <select id="Ord_Com" name="Ord_Com" class="form-control input-xs" required="" >
                                                    <option value = "UC" >1/4</option>
                                                    <option value = "DC" >2/4</option>
                                                    <option value = "TC" >3/4</option>
                                                    <option value = "CC" >4/4</option>
                                                </select></div>
                                            <label class="col-xs-1 control-label label-xs">Triangulos:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Tri" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Cds:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Cds" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Tapacubos:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Tap" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Llantas:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Lla" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Gata:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Gat" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Herramientas:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Her" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Llave ruedas:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Llr" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Botiquin:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Bot" value="S" offval="N"></div>
                                            <label class="col-xs-1 control-label label-xs">Tapagas:</label>
                                            <div class="col-xs-1" ><input type="checkbox" name="Ord_Tpg" value="S" offval="N"></div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-12" >
                                                <textarea id="Ord_Obs" name="Ord_Obs" class="form-control" rows="4" placeholder="Observaciones generales"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-md-6">
                                                <h3 style="font-size: 12px; text-align: justify; border: 1px solid; padding: 15px; border-radius: 5px;"><b>Autorizaci&oacute;n del cliente:</b><br>1.- La presente autorizaci&oacute;n expresa que: Siendo el propietario o actuando como representante del mismo estoy en condiciones de autorizar los servicios anotados, as&iacute; como reemplazo de las piezas que fueren pertinentes para la ejecuci&oacute;n de los mismos.<br>2.- El valor de la factura por toda la reparaci&oacute;n o servicio ser&aacute; cancelada de acuerdo a los t&eacute;rminos y forma de pago, al recibir el trabajo.<br>3.- Que despu&eacute;s de 3 d&iacute;as de haberme comunicado XXX que los servicios requeridos han sido completamente efectuados, se cargue $3.00 diarios por concepto de bodega.<br>4.- Cualquier reclamo relacionado con los servicios obtenidos deber&eacute; presentarlo dentro de un plazo m&aacute;ximo de 10 d&iacute;as, a contar de la fecha de recibidos estos.<br>5.- Autorizar al personal espec&iacute;fico de XXX para que mi vehiculo sea probado en la v&iacute;a p&uacute;blica.<br>6.- Autorizar a XXX para que env&iacute;e a otros talleres a hacer ciertas reparaciones especializadas que no se efect&uacute;n regularmente en este taller.<br>7.- Exonerar de responsabilidad a XXX por cualquier demora causada por la dificultad de conseguir repuestos o por demoras en el despacho de los mismos por el proveedor.<br>8.- Exonerar a XXX y a su personal de toda responsabilidad por p&eacute;rdidas, robo, incendio o accidentes y los riesgos que est&eacute;n fuera de su control.</h3>
                                            </div>

                                            <div class="col-md-6 center">
                    
                                              <div class="col-md-offset-1 col-sm-6 margin-top-20 margin-bottom-20">
                                                <img src="picture_library/car1.png">
                                              </div>
                                            </div>

                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-1 control-label label-xs"></label>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <div class="col-sm-12 center" hidden>
                                                    <table id="items" ></table>
                                                    <div id="itemsPager" ></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-md-12">
                                                <h3 style="font-size: 12px; text-align: justify; padding: 15px;"><b>INFORMACI&Oacute;N PARA EL CLIENTE:</b><br>1.- Este comprobante certifica que hemos recibido su veh&iacute;culo para reparaci&oacute;n o chequeo.<br>2.- El pago debe hacerse seg&uacute;n los t&eacute;rminos autorizados y aceptados; si desea cancelar de otra manera debe ponerse en contacto con XXX antes de acercarse a retirar el veh&iacute;culo.<br>3.- Nuestro horario de atenci&oacute;n es de lunes a viernes de 08h30 a 18h00, la hora m&aacute;xima de entrega de veh&iacute;culos es hasta las 17h30 y s&aacute;bados de 09h00 a 17h00.<br>4.- La caja atiende desde las 09h00 a 18h00, ininterrumpidamente.<br>5.- S&aacute;bados desde las 07h00 a 17h00.</h3>
                                            </div>
                                            <div class="col-md-12 center">
                                                <h3 style="align: center;">PROGRAMA TU CITA AL 098 374 8559</h3>
                                                <h3 style="font-size: 12px; font-weight: bold; text-align: justify; border: 1px solid; padding: 25px; border-radius: 5px;">RECIB&Iacute; EL VEH&Iacute;CULO A ______________________________________________________________________ FECHA __________________________<br><br>NOMBRE __________________________________________________________________________________<br><br>C.I. O RUC __________________________________ FIRMA ______________________________ FIRMA RECEPCIONISTA ______________________</h3>
                                            </div>
                                            <div class="form-group">
                                                <div class="col-xs-12 text-center">
                                                    <button id="btnSave" name="btnSave" type="button" onclick="guardar();" class="btn btn-primary "><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                                    <button id="Btn_His" type="button" onclick="$('#fichaDialog').dialog('open');" name="Btn_His"class="btn btn-info"><i class="glyphicon glyphicon-folder-open"></i> Historial</button>
                                                </div> 
                                            </div>
                                        </div>
                                </form>
                            </div>
                            <div class="col-sm-12 Titulos2" style="text-align: center;"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                        </div>
                    </div>
                </div>
            </div>
        
        <!-- Inicio del diálogo para buscar pacientes -->
        <div id="clieDialog" title="B&uacute;squeda de clientes"><form class="form-horizontal normal"> </form></div>
        <!-- Inicio del diálogo para buscar fichas medicas -->
        <div id="fichaDialog" title="Historial de mantenimientos">
            <form class="form-horizontal normal">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Filtros</legend>
                <div class="form-group ">
                    <div class="col-xs-6 radioset">
                        <label class="col-xs-4 control-label label-xs">Filtrar por:  </label>
                        <input id="rad7" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)"/><label for="rad7">&nbsp;&nbsp;Placa&nbsp;&nbsp;</label>
                         <input id="rad8" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)"/><label for="rad8">&nbsp;&nbsp;Apellido/Nombre&nbsp;&nbsp;</label>
                    </div>
                </div>
                    <div class="form-group ">
                        <label class="col-xs-2 control-label label-xs">B&uacute;squeda:</label>
                        <div class="col-xs-5">
                            <div class="input-group">
                                <input name="search" id="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese criterio a buscar..." autofocus  class="form-control input-sm "/>
                                <span class="input-group-btn">
                                    <button class="btn btn-primary btn-sm" onclick="searchFichas();"><i class="glyphicon glyphicon-search"> </i> Buscar</button>
                                    <span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button>
                                </span>
                            </div>                        
                        </div>
                    </div>
                    </fieldset>
            </form>
        </div>
         <!-- Inicio del diálogo para buscar mediccamentos -->
        <div id="proDialog" title="Buscar producto"><form class="form-horizontal normal"></form></div>

        <!-- FORMULARIO IMPRESION -->
    <div class="container" id="documentoVista" style="display:none;">
        <div id="datosPrf" class="box-container">
            <?php 
            if($Ses_Emp_Cod == 300){
                echo '<h3 style="text-align: center; font: 14pt Verdana, Geneva, sans-serif; font-weight: bold;"> PROFORMA N. <span id="titleReporte"></span></h3>';
            }else{
                echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'ORDEN DE TRABAJO N. <span id="titleReporte"></span>', ' ', $obBD_conexion);
            }
            ?>
            <form name="datosProf" id="datosProf" class="form-horizontal normal">
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                            <table border="0" cellpadding="0" cellspacing="0" id="cabeceraTabla" style="width: 100%;border-collapse: collapse; font-family:Verdana, Geneva, sans-serif; font-size:12px "
                             class="rep">
                                <tr style="height: 0;">
                                    <td style="width: 10%;"></td>
                                    <td style="width: 40%;">&nbsp;</td>
                                    <td style="width: 10%;"></td>
                                    <td style="width: 40%;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>CLIENTE: </strong> 
                                    </td>
                                    <td colspan="1">
                                        <span name="cliente" id="cliente"style="font-size: 12px;" class="form-control input-xs databind datatitle"><?php  $cliente ?></span>
                                    </td>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>RUC/C.I.: </strong>
                                    </td>
                                    <td colspan="1">
                                        <span name="Ord_Fec" style="font-size: 12px;" class="form-control input-xs databind datatitle">
                                    </td>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>CORREO: </strong>
                                    </td>
                                    <td>
                                        <span name="Ord_Num" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>FACTURAR:</strong>
                                    </td>
                                    <td colspan="1">
                                        <span name="cliente" id="cliente" style="font-size: 11px;" class="form-control input-xs databind datatitle"></span>
                                    </td>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>DIRECCION:</strong>
                                    </td>
                                    <td colspan="1">
                                        <span name="Prs_Dir" id="Prs_Dir" style="font-size: 11px;" class="form-control input-xs databind datatitle"></span>
                                    </td>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>PLACA:</strong>
                                    </td>
                                    <td colspan="1">
                                        <span name="Ord_Pla" id="Ord_Pla" style="font-size: 11px;" class="form-control input-xs databind datatitle"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>PAGO:</strong>
                                    </td>
                                    <td colspan="1">
                                        <span name="Prs_Dir" style="font-size: 12px;" class="form-control input-xs databind datatitle">
                                    </td>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>TELEFONO:</strong>
                                    </td>
                                    <td colspan="1">
                                        <span name="Prs_Dir" style="font-size: 12px;" class="form-control input-xs databind datatitle">
                                    </td>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>KMS:</strong>
                                    </td>
                                    <td colspan="1">
                                        <span name="Ord_Kms" id="Ord_Kms" style="font-size: 11px;" class="form-control input-xs databind datatitle"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>MODELO:</strong>
                                    </td>
                                    <td>
                                        <span name="Ord_Mod" style="font-size: 12px;" class="form-control input-xs databind datatitle">
                                    </td>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>A&Ntilde;O:</strong>
                                    </td>
                                    <td>
                                        <span name="Ord_Ani" style="font-size: 12px;" class="form-control input-xs databind datatitle">
                                    </td>
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>COLOR:</strong>
                                    </td>
                                    <td>
                                        <span name="Ord_Col" style="font-size: 12px;" class="form-control input-xs databind datatitle">
                                    </td>
                                </tr>
                                <tr style="display:none;">
                                    <td class='bold' style="font-size: 12px;">
                                        <strong>Vendedor:</strong>
                                    </td>
                                    <td>
                                        <span id="Vnd_Cod" name="Vnd_Cod" style="font-size: 12px;" class="form-control input-xs databind datatitle">
                                    </td>
                                </tr>
                            </table>
                        </fieldset>
                    </div>
                </div>

                <br/>
            </form>
            <table style=" width: 680px; border-collapse: collapse; font: 8pt Verdana;" border="1" >
                <tr>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Antena:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle">x</span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Radio:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Plumas:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Extinguidor:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Control puerta:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Seguros aros:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Signos:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                </tr>
                <tr>
                    <td width="7%" height="30px" class='bold' align='left' style="font-size: 12px;">
                        <strong>Encendedor:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Enc" id="Ord_Enc" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Moqueta:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Moq" id="Ord_Moq" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Espejos:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Esp" id="Ord_Esp" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Combustible:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Triangulos:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Cds:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Tapacubos:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                </tr>
                <tr>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Llantas:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Gata:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Herramientas:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Ant" id="Ord_Ant" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Llave ruedas:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Llr" id="Ord_Llr" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Botiquin:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Bot" id="Ord_Bot" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                    <td width="7%" class='bold' align='left' style="font-size: 12px;">
                        <strong>Tapagas:</strong>
                    </td>
                    <td width="7%">
                        <span name="Ord_Tpg" id="Ord_Tpg" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                    </td>
                </tr>
            </table>
            
                <br/>

                <table style=" width: 100%;font-size: 12px;border-collapse: collapse;" border="1">
                    <tr>
                        <td class='bold' style="font: 10pt Verdana;">
                            <strong>Observaciones generales:</strong>
                        </td>
                    </tr>
                    <tr>
                        <td class='bold' style="padding: 20px; font-size: 12px; font: 12pt Verdana;">
                            <span name="Ord_Obs" id="Ord_Obs" style="font-size: 12px;" class="form-control input-xs databind datatitle"></span>
                        </td>
                    </tr>
                </table>
                
                <br/>

                <table style=" width: 100%;font-size: 12px;border-collapse: collapse;" border="1">
                    <tr>
                        <td style="font-size: 12px;">
                            <h3 style="font-weight: normal; font: 12pt Verdana; padding: 5px; font-size: 10px; text-align: justify; "><b>Autorizaci&oacute;n del cliente:</b><br>1.- La presente autorizaci&oacute;n expresa que: Siendo el propietario o actuando como representante del mismo estoy en condiciones de autorizar los servicios anotados, as&iacute; como reemplazo de las piezas que fueren pertinentes para la ejecuci&oacute;n de los mismos.<br>2.- El valor de la factura por toda la reparaci&oacute;n o servicio ser&aacute; cancelada de acuerdo a los t&eacute;rminos y forma de pago, al recibir el trabajo.<br>3.- Que despu&eacute;s de 3 d&iacute;as de haberme comunicado XXX que los servicios requeridos han sido completamente efectuados, se cargue $3.00 diarios por concepto de bodega.<br>4.- Cualquier reclamo relacionado con los servicios obtenidos deber&eacute; presentarlo dentro de un plazo m&aacute;ximo de 10 d&iacute;as, a contar de la fecha de recibidos estos.<br>5.- Autorizar al personal espec&iacute;fico de XXX para que mi vehiculo sea probado en la v&iacute;a p&uacute;blica.<br>6.- Autorizar a XXX para que env&iacute;e a otros talleres a hacer ciertas reparaciones especializadas que no se efect&uacute;n regularmente en este taller.<br>7.- Exonerar de responsabilidad a XXX por cualquier demora causada por la dificultad de conseguir repuestos o por demoras en el despacho de los mismos por el proveedor.<br>8.- Exonerar a XXX y a su personal de toda responsabilidad por p&eacute;rdidas, robo, incendio o accidentes y los riesgos que est&eacute;n fuera de su control.</h3>
                        </td>
                        <td>
                            <img src="picture_library/car.png">
                        </td>
                    </tr>

                </table>
                <h3 style="font-weight: normal; font: 8pt Verdana; text-align: justify;"><b>INFORMACI&Oacute;N PARA EL CLIENTE:</b><br>1.- Este comprobante certifica que hemos recibido su veh&iacute;culo para reparaci&oacute;n o chequeo.<br>2.- El pago debe hacerse seg&uacute;n los t&eacute;rminos autorizados y aceptados; si desea cancelar de otra manera debe ponerse en contacto con XXX antes de acercarse a retirar el veh&iacute;culo.<br>3.- Nuestro horario de atenci&oacute;n es de lunes a viernes de 08h30 a 18h00, la hora m&aacute;xima de entrega de veh&iacute;culos es hasta las 17h30 y s&aacute;bados de 09h00 a 17h00.<br>4.- La caja atiende desde las 09h00 a 18h00, ininterrumpidamente.<br>5.- S&aacute;bados desde las 07h00 a 17h00.</h3>
                <h3 style="font-weight: bold; font-size: 10px; font: 10pt Verdana; text-align: center;"><b>PROGRAMA TU CITA AL 098 374 8559</b>
                </h3>
                <h3 style="border: 1px solid; padding: 15px; font-weight: normal; font-size: 10px; font: 10pt Verdana; text-align: justify;">
                    RECIBI EL VEHICULO A ________________________________ FECHA: ________________ <br><br>  
                    NOMBRE ______________________________________________ <br><br> 
                    C.I./RUC ______________ FIRMA ____________ FIRMA RECEPCIONISTA ______________
                </h3>
                <div class="grid" style="width: 39%; font-size: 10px; text-align: justify;">
                    <span ><strong></strong></span>
                </div>
                <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
            </div>
        </div>

        <script>
            //Dialog buscar clientes
            function impProforma() {
                $('#datosPrf').printElement({ pageTitle: "", overrideElementCSS: [{ href: '../../mascaras/model1/estilos/print.css', media: 'print' }] });
            }

            $.createSearchDialog('fichaDialog',[
                { label: 'C&oacute;d.', name: 'Ord_Cod', key: true, width: 25,align:"center" },
                { label: 'Num', name: 'Ord_Num', width: 30 },
                { label: 'C&eacute;dula/RUC', name: 'Prs_Ced',  hidden:true, width: 50 },
                { label: 'Cod.Int', name: 'Cli_Cod',  hidden:true, width: 50 },
                { label: 'Cliente', name: 'cliente', hidden:false, width: 100},
                { label: 'Direcc.', name: 'Prs_Dir', hidden:true, width: 60 },
                { label: 'Fecha mant.', name: 'Ord_Fec', align:'center', hidden:false, width: 40 },
                { label: 'Placa', name: 'Ord_Pla', align:'center', hidden:false, width: 40 },
                { label: 'Modelo', name: 'Ord_Mod', width: 50 },
                { label: 'Observaciones', name: 'Ord_Obs', width: 50 },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatter:'gridButton', formatoptions:{action:reportFicha, icon:'print', type:'info',title:'Imprimir ficha'}}
            ],null,null,{headertitles:true},{ title:'Ficha', text:'Prs_Ced' });

            $.createSearchDialog('clieDialog',[
                { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15,align:"center",hidden:true },
                { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Cliente', name: 'cliente', width: 100},
                { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
                { label: 'Ciudad', name: 'Ciu_Des', hidden:false, width: 50 },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectCliente} }
            ],null,null,null,{headertitles:true},{ title:'Cliente', text:'Prs_Ced' });

             // Dialog para buscar medicamentos
           $.createSearchDialog('proDialog',[
            { label: 'C&oacute;d.Int.', name: 'Med_Cod', key: true, width: 20,align:"center"},
            { label: 'Descripción', name: 'Med_Nom', width: 180, classes:'highlightSearch' },
            { label: 'Cantidad', name: 'Med_Can', width: 40, align:"center"},
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectItem} }
        ],null,900,null,null,{ title:'Medicamento' });
            
            function reportFicha(row){
                $.post( "",{cargarReportes:true, Ord_Cod:row['Ord_Cod']}, function( response ) {
                    if(response['success']===true){
                        $(response['html']).printElement({pageTitle:'<?Php echo $Ses_Sys_Nom; ?>'});
                    }else{$.alert(response['message']);}                                   
                 },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });
            }

            function certificado(row){
                $.post( "",{ajaxCertificado:true}, function( response ) {
                    if(response['success']===true){
                        $(response['html']).printElement({pageTitle:'<?Php echo $Ses_Sys_Nom; ?>'});
                    }else{$.alert(response['message']);}                                   
                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });
            }

            function selectCliente(cliente){
                $('#clieFormTemp').setData($.extend(cliente,{op_opciones:'c'}));
                $('#clieDialog').dialog('close');
                $('#fichaDialog').dialog('close');

                $.post("", { enableDisableCampos: true, Pac_Cod: cliente['Cli_Cod'] }, function(responce) {
                    if (responce['success'] === true) {

                        if(responce['data_ant'] === 'none' || !(responce['data_ant'] > 0)){
                            $("#anticipo").css("display", "none");
                        }
                        else{
                            $("#anticipo").css("display", "block");
                        }

                        $("#ant_msg").html(responce['data_ant'] === 'none' || !(responce['data_ant'] > 0) ? "$ 0.00" : $.numFormat(responce['data_ant']));
                        $("#ant_msg")[responce['data_ant'] === 'none' || !(responce['data_ant'] > 0) ? 'removeClass' : 'addClass']('alert alert-danger bold');
                    } else {
                        $("#anticipo").css("display", "none");
                        $("#ant_msg").html("$ 0.00");
                        $("#ant_msg").removeClass('alert alert-danger bold');
                        $.alert(responce['message']);
                    }
                }, 'json');
                numFicha();
            }

        </script>

        <!-- Inicio del diálogo para registrar clientes -->
        <div id="clieCreateDialog" title="Registrar Cliente" style="display:none;">
            <form class="form-horizontal normal" id="clieCreateForm" action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaCliente(); }else{ $('#Prs_Ced').flyout('show').focus() }">
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
                        <label class="col-xs-3 control-label label-xs required">C&eacute;dula/RUC:</label>
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
                        <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Razon Social:</span></label>
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
                            <?php $rs_ciudad = $obBD_con1->getArrayConsulta(81, '', $obBD_conexion); ?>
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

        <!-- Formulario para registrar nuevo medicamento-->
        <div id="medCreateDialog" title="Registrar medicamento" style="display:none;">
            <form class="form-horizontal normal" id="medCreateForm" action="javascript:guardaMed();">
                <input name="Med_Cod" type="text" class="hidden" />
                <fieldset class="exa-fieldset" >
                    <legend class="Titulos2">Datos del medicamento</legend>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Nombre comercial:</label>
                        <div class="col-xs-7" ><input name="Med_Nom" type="text" class="form-control input-xs" required="" /></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Cantidad:</label>
                        <div class="col-xs-7" ><input name="Med_Can" type="text" class="form-control input-xs" required=""/></div>
                    </div>
                </fieldset>
                <div class="center">
                    <button  type="submit" class="btn btn-sm btn-success "><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                </div>
                <div class="Titulos2" style="text-align: center;"><hr><b>NOTA:</b> Los campos marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
            </form>
        </div>
        
        <!-- DIALOG crear nuevo cliente -->
        <script>
            $('#clieCreateDialog').createDialog({icon:'plus', width:500, height:430});
            $('#For_Cod').val(1).trigger('change');
        </script>

        <!--// DIALOG crear nuevo medicamento-->
        <script>
            $('#medCreateDialog').createDialog({icon:'plus', width:500, height:200});
            //$('#For_Cod').val(1).trigger('change');
        </script>
        
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <script>
    $('#Pec_Cod').trigger('change');
    $.clearValidate();</script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <script type="text/javascript">
            

            function validaNoIdentif(number){
                var digitos = number.split(''), dto=digitos.length, acu=0, resp={success:false,message:''},
                    coef={'NA':[2,1,2,1,2,1,2,1,2],'PU':[3,2,7,6,5,4,3,2,0],'PR':[4,3,2,7,6,5,4,3,2]}, modulo, acum=0;
                if(dto===0) resp['message']='No has ingresado ning\u00fan dato!';
                else{
                    for(var i=0; i<dto; i++) if(!isNaN(digitos[i])){ digitos[i]=digitos[i]*1; acu = acu+1; }
                    if(acu===dto){
                        var tipo = digitos[2];
                        if (tipo===7||tipo===8) resp['message']='"El tercer d\u00edgito ingresado es inv\u00e1lido"'; else{ tipo=(tipo<=6?'NA':(tipo===6?'PU':(tipo===9?'PR':''))); modulo=(tipo==='NA'?10:11); resp['tipo_abrev']=tipo; resp['tipo']=(tipo==='NA'?'Natural':(tipo==='PR'?'Privada':(tipo==='PU'?'P\u00fablica':''))); }
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
                    }else resp['message']='ERROR: Solo debe contener d\u00EDgitos!';
                }
                return resp;
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

            function clear(){
                $('#clieCreateForm').setData({Cli_Tic:'N',Prs_Ciu:'Ec',Prs_Sex:'M'});
                $('#Prs_Ced').val('').focus();
                $('.juridico').hide();$('.natural').show();
            }
            function searchFichas(){
                //var cod= $('#Pac_Cod').val();
                $.post("",{fichaAjax:true}, function( response ) {
                    //console.log(response);
                    $('#fichaDialog').setData(response,false);
                },'json').fail(function (){
                    $.alert();
                });
            }

            function clear(){
                $('#clieCreateDialog').setData({Cli_Tic:'N',Prs_Ciu:'Ec',Prs_Sex:'M'});
                $('#Prs_Ced').val('').focus();
                $('.juridico').hide();$('.natural').show();
            }

            function guardaCliente(){
                $.saveDataJson('',$('#clieCreateForm').getData('guardaClieAjax'), function( resp ){
                    $('#Ciu_Cod').val(3);
                    selectCliente(resp['clie']); 
                    $('#clieCreateDialog').dialog('close'); 
                    return false; 
                });
            }
            
            items=$("#items");
            if($('#items').length===1){
                
            items.createGrid({
            data:[],
            rowNum: 10000000, height: 'auto', footerrow:true, headertitles:true, selectGridRows:false,
            colModel:[
                {name:'index', label:'Index', width:20, sorttype:'int',align:'center',key:true,hidden:true},
                {name:'Med_Cod',label:'C&oacute;d.Int.', width:20, sorttype:'int',align:'center',hidden:true},
                {name:'Med_Nom',label:'Descripci&oacute;n', width:150},
                {name:'Med_Can',label:'Cantidad', width:50, align:'center' },
                {name:'Med_Dos',label:'Dosis', width:70, edittype:'text', editable:true},
                {name:'Med_Dur',label:'Duraci&oacute;n', width:70, edittype:'text', editable:true},
                {name:'act1', label: '&nbsp;',width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:deleteItem, icon:'remove', type:'danger', data: function(o) { return o.index; }, attr: { 'tabindex': '-1' },title:'Eliminar &iacute;tem'} }
            ]
        },true,'itemsPager',{view:false}).gridButtonsAdd([
            {caption:'Agregar',buttonicon:'plus', onClickButton: function(){if(!available()){ $.alert('No hay espacio para mas items en este documento!');return;}index=0;$('#proDialog').dialog('open');$.Search('pro');}},
            {caption:'Remover todo',buttonicon:'remove', onClickButton: function(){items.clearGrid(); }},
            {caption:'Nuevo',buttonicon:'plus', onClickButton: function(){if(!available())index=0;$('#medCreateDialog').dialog('open');$.Search('pro');}},
        ]);
        items.getFootRow(true);

        function numFicha(){
            $.post("",{numSec:true}, function( response ) {
                var num = parseInt(response['Ord_Num']) + 1;
                $('#Ord_Num').val('000'+num);
            },'json').fail(function (){
                $.alert();
            });
        }

        function guardar(){
            var cod = $('#Cli_Cod').val();
            var fec = $('#Ord_Fec').val();
            var num = $('#Ord_Num').text();
            var obs = $('#Ord_Obs').val();
            var fac = $('#Ord_Cli').val();

            if(obs!='' && fac!='' &&  fec!=''){

                $.saveDataJson('', $('#fichaForm').getData('saveDocument'), 
                function( resp ){
                if(resp['success']==true){
                    $.alert("Orden guardada con exito");
                }else{
                    $.alert("No se pudo realizar la accion");
                }
                return false;
                });
            }else{
                $('#cliente').focus();
                $.alert('Ingrese todos los campos');

            }
        }

        function limpiar(){
            //location.reload();
            $("input[name=Prs_Ced]").val('');
            $("span[name=paciente]").text('');
            $("span[name=Prs_Dir]").text('');
            $("span[name=Pac_Emp]").text('');
            $("span[name=Prs_Sex]").text('');
            $("span[name=Prs_Tel]").text('');
            $("span[name=Ciu_Des]").text('');
            $("span[name=Prs_Cor]").text('');
            $("span[name=Pac_Fna]").text('');
            $("span[name=edad]").text('');
            $("span[name=Fic_Num]").text('');
            $("textarea[name=Fic_Mot]").val('');
            $("textarea[name=Fic_Hea]").val('');
            $("textarea[name=Fic_Obs]").val('N/A');
            numFicha();
        }

        

        }
        </script>
    </BODY>
</HTML>