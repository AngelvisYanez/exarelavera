<?php
/**
 * @abstract Permite realizar el registro de proformas
 * @author Cear Bermeo
 * @version 1.0
 * Fecha de creación  2018-06-26
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/prf_log_proforma.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Proforma;
$numeroProformaActual;
$tusItems ;
$busquedaProf;
//
if (isset($clieAjax)) {
    $responce = $obBD_con1->getPageGridJson('proformas.selectWhere', $_GET, $obBD_conexion);
}
//busqueda de proformas
if (isset($prfAjax)) {
    $responce = $obBD_con1->getPageGridJson('proformas.selectWhere', $_GET, $obBD_conexion);
    //$resp = $obBD_con1->getArrayConsultaSql("select * from proformas_det inner join proformas on proformas.Prf_Cod=proformas_det.Prf_Cod;", $obBD_conexion, true);
    $obBD_con1->echoLog($responce);
}

if(isset($detalleProformas)){
    $resuladoDetalle=array(
        'success' => true,
        'detPrfms'=>$obBD_con1->getArrayConsultaSql("SELECT proformas.Prf_Cod,Pfd_Int,proformas_det.Pro_Cod,Prf_Imp,Prf_Cant,unidad.Uni_Cod,Uni_Des,Prf_Pru,Prf_Des,
        Prf_Num,Prf_Obs,item.Ite_Cod,adquisicio.Adq_Cod,Ite_Lar,Adq_Des,Adq_Cor,
        iva.Iva_Cod,Iva_Por
      FROM
        proformas
        INNER JOIN proformas_det ON (proformas.Prf_Cod = proformas_det.Prf_Cod)
        INNER JOIN producto ON (proformas_det.Pro_Cod = producto.Pro_Cod)
        INNER JOIN iva ON (proformas_det.Iva_Cod = iva.Iva_Cod)
        INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
        INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
        INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
      WHERE proformas.Prf_Cod='$Prf_Cod';", $obBD_conexion),
      'numVentasAct'=>$obBD_con1->getArrayConsultaSql("SELECT  COUNT(*) AS total from ventas inner join cliente on cliente.Cli_Cod=ventas.Cli_Cod inner join persona on persona.Prs_Cod=cliente.Prs_Cod inner join caja_aper on ventas.Caj_Cod=caja_aper.Caj_Cod  where Prf_Cod= '$Prf_Cod';",$obBD_conexion),
    );
    $obBD_con1->echoJson($resuladoDetalle);
}

if (isset($cliSearchAjax)) {
    //$respuesta = $obBD_con1->getRowConsulta(1,  $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion,true);
    //array('Cliente'=>"CONCAT(Prs_Nom,' ',Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir')
    $data = array_merge($_GET, array('setWhere' => array('byPerCod')));
    $respuesta = $obBD_con1->getPageGridJson('cliente.selectWhere', $_GET, $obBD_conexion);
    //$obBD_con1->echoLog($respuesta);


}

if (isset($proAjax)) {
    $productos = $obBD_con1->getPageGridJson('producto.selectWhere', $_GET, $obBD_conexion);
    //$obBD_con1->echoLog($productos);
}

/*
<?php
    // consulta que cuenta los registros del año actual
    $result = query_result("select count(*) as cont from document where year(created_at)=year(now())");
    $cont = $result->cont + 1; //incrementas
    $num = sprintf("%'.05d", $cont); // formato con 5 digitos
    $codigo = date("Y")."-".$num; // concatenas con el año actual
    echo $codigo;
?>
public function getCount()
    {
        $select = $this->select();
        $select->from($this, array('count(*) as amount'));
        $rows = $this->fetchAll($select);

        return($rows[0]->amount);
    }
*/
if(isset($saveDocumento)){

    $resp=array('success'=>false);
    $obBD_ins1 =  new Class_Log_Datos_Proforma;
    //$obBD_ins2 =  new Class_Log_Datos_Proforma_Det;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->debugLogs(false);
    $obBD_con1->debugLogs(false);

    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    $prd_indice = 0;
    $es_creacion = 0;
    try{

        $data = $_POST;
        //$obBD_con1->echoLog('php saveDocumento');
        //$obBD_con1->echoLog($data);
        $numeroProformaActual = $data;
        //$obBD_con1->echoLog($numeroProformaActual);
        if ($data['Prf_Nva'] > 0) {
            //es nueva proforma
            $tituloImp= 'Proforma No. '.($data['Prf_Num']);
            $obBD_con1->echoLog('opcion creacion');
            //Busco el numero actual de las proformas
            $numProforma = $obBD_con1->getRowConsultaSql("SELECT IFNULL(MAX(Prf_Num),0) AS total FROM proformas INNER JOIN cliente ON cliente.Cli_Cod=proformas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod INNER JOIN sucursal ON cliente.Emp_Cod=sucursal.Emp_Cod WHERE (sucursal.Emp_Cod='$Ses_Emp_Cod');", $obBD_conexion);
            // insertamos caabecera de proforma
            $obBD_ins1->operacionobBD('proformas.insert', array('Prf_Fec' => $data['Prf_Fec'], 'Prf_Num' => $numProforma['total'] + 1, 'Prf_Des' => $data['t_descuento'], 'Cli_Cod' => $data['Cli_Cod'], 'Vnd_Cod' => $data['Vnd_Cod'], 'Prf_Obs' => $data['Vet_Obs'], 'Prf_Ord' => $data['Prf_Num_Ext']), $obBD_conexionIns);

            $Prf_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
            $busquedaProf = $data['Prf_Num'];
            $conta = 0;
            foreach ($items as $itemsito) {
                if ($itemsito['Pro_Cod'] != 0 && $itemsito['Iva_Por'] > 0) {
                    $obBD_ins1->operacionobBD('proformas_det.insert', array('Prf_Cod' => $Prf_Cod, 'Pfd_Int' => $conta + 1, 'Pro_Cod' => $itemsito['Pro_Cod'], 'Prf_Cant' => $itemsito['Vet_Can'], 'Prf_Uni' => $itemsito['Uni_Des'], 'Prf_Imp' => $itemsito['Vet_Imp'], 'Prf_Pru' => $itemsito['Vet_Pru'], 'Iva_Cod' => $data['Iva_Cod']), $obBD_conexionIns);
                    $conta++;
                } else {
                    if ($itemsito['Pro_Cod'] != 0) {
                        $obBD_ins1->operacionobBD('proformas_det.insert', array('Prf_Cod' => $Prf_Cod, 'Pfd_Int' => $conta + 1, 'Pro_Cod' => $itemsito['Pro_Cod'], 'Prf_Cant' => $itemsito['Vet_Can'], 'Prf_Uni' => $itemsito['Uni_Des'], 'Prf_Imp' => $itemsito['Vet_Imp'], 'Prf_Pru' => $itemsito['Vet_Pru'], 'Iva_Cod' => $itemsito['Iva_Cod']), $obBD_conexionIns);
                        $conta++;
                    }
                }
            }
            $tusItems = $obBD_con1->getArrayConsultaSql("select * from proformas_det inner join proformas on proformas.Prf_Cod=proformas_det.Prf_Cod where proformas.Prf_Num='$busquedaProf';", $obBD_conexion);
        }

        if($data['Num_Vtas'] > 0 && $data['Es_Mod'] > 0){
            //tiene ventas
            //$obBD_con1->echoLog('opcion 1');
            $es_creacion = $es_creacion + 1;
            $numProforma = $obBD_con1->getRowConsultaSql("SELECT IFNULL(MAX(Prf_Num),0) AS total FROM proformas INNER JOIN cliente ON cliente.Cli_Cod=proformas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod INNER JOIN sucursal ON cliente.Emp_Cod=sucursal.Emp_Cod WHERE (sucursal.Emp_Cod='$Ses_Emp_Cod');", $obBD_conexion);
            $numPrfMens = $numProforma['total'] + 1;
            $obBD_ins1->operacionobBD('proformas.insert', array('Prf_Fec' => $data['Prf_Fec'], 'Prf_Num' => $numProforma['total'] + 1, 'Prf_Des' => $data['t_descuento'], 'Cli_Cod' => $data['Cli_Cod'], 'Vnd_Cod' => $data['Vnd_Cod'], 'Prf_Obs' => $data['Vet_Obs'], 'Prf_Ord' => $data['Prf_Num_Ext']), $obBD_conexionIns);
            $Prf_Cod = $obBD_ins1->insercionid($obBD_conexionIns);

            $busquedaProf = $data['Prf_Num'];
            $conta = 0;
            foreach ($items as $itemsito) {
                if ($itemsito['Pro_Cod'] != 0 && $itemsito['Iva_Por'] > 0) {
                    $obBD_ins1->operacionobBD('proformas_det.insert', array('Prf_Cod' => $Prf_Cod, 'Pfd_Int' => $conta + 1, 'Pro_Cod' => $itemsito['Pro_Cod'], 'Prf_Cant' => $itemsito['Vet_Can'], 'Prf_Uni' => $itemsito['Uni_Des'], 'Prf_Imp' => $itemsito['Vet_Imp'], 'Prf_Pru' => $itemsito['Vet_Pru'], 'Iva_Cod' => $data['Iva_Cod']), $obBD_conexionIns);
                    $conta++;
                }
                else {
                    if ($itemsito['Pro_Cod'] != 0) {
                        $obBD_ins1->operacionobBD('proformas_det.insert', array('Prf_Cod' => $Prf_Cod, 'Pfd_Int' => $conta + 1, 'Pro_Cod' => $itemsito['Pro_Cod'], 'Prf_Cant' => $itemsito['Vet_Can'], 'Prf_Uni' => $itemsito['Uni_Des'], 'Prf_Imp' => $itemsito['Vet_Imp'], 'Prf_Pru' => $itemsito['Vet_Pru'], 'Iva_Cod' => $itemsito['Iva_Cod']), $obBD_conexionIns);
                        $conta++;
                    }
                }
            }
            $tusItems = $obBD_con1->getArrayConsultaSql("select * from proformas_det inner join proformas on proformas.Prf_Cod=proformas_det.Prf_Cod where proformas.Prf_Num='$busquedaProf';", $obBD_conexion);
            //$obBD_con1->echoLog($es_creacion);
            $mensajito = "Se genero una nueva proforma No. $numPrfMens !";
            $resp['message']=$mensajito;
            //echo json_encode($mensajito);

        } else{
            //modificacion
            $obBD_con1->echoLog('opcion modificacion');
            $es_creacion = $es_creacion + 1;
            if($data['Num_Vtas'] == 0 && $data['Es_Mod'] > 0){
                //busco la proforma
                $prof_Cod = $obBD_con1->getRowConsulta('proformas.selectWhere', array('proformas.Prf_Cod'=>$data['Prf_Mod_Cod']), $obBD_conexion,true);
                //actualizo la proforma
                //if($data['Prf_Fec'] != $prof_Cod['Prf_Fec'] || $data['t_descuento'] != $prof_Cod['Prf_Des'] || $data['Vet_Obs'] != $prof_Cod['Prf_Obs']  ){  $obBD_ins1->operacionobBD('proformas.update', array('Prf_Cod' => $prof_Cod['Prf_Cod'],'Prf_Fec'=>$data['Prf_Fec'], 'Prf_Des'=> $data['t_descuento'], 'Prf_Obs' => $data['Vet_Obs'], 'Prf_Ord' => $data['Prf_Num_Ext'] ),$obBD_conexionIns,true); }
                $obBD_ins1->operacionobBD('proformas.update', array('Prf_Cod' => $prof_Cod['Prf_Cod'],'Prf_Fec'=>$data['Prf_Fec'], 'Prf_Des'=> $data['t_descuento'], 'Prf_Obs' => $data['Vet_Obs'], 'Prf_Ord' => $data['Prf_Num_Ext'] ),$obBD_conexionIns,true);
                //Busco items de la proforma
                $articulos = $obBD_con1->getArrayConsulta('proformas_det.selectWhere', array('proformas_det.Prf_Cod'=>$prof_Cod['Prf_Cod']), $obBD_conexion);
                foreach($articulos as $articulo){
                    //borro los items guardos
                    $obBD_ins1->operacionobBD('proformas_det.deleteByPk', array('Prf_Cod' => $prof_Cod['Prf_Cod'], 'Pfd_Int' => $articulo['Pfd_Int'], 'Pro_Cod' => $articulo['Pro_Cod']), $obBD_conexionIns,true);
                }
                foreach ($items as $itemsito) {
                    if ($itemsito['Pro_Cod'] != 0 && $itemsito['Iva_Por'] > 0) {
                        //Guardo los nuevos items
                        $obBD_ins1->operacionobBD('proformas_det.insert', array('Prf_Cod' => $prof_Cod['Prf_Cod'], 'Pfd_Int' => $conta + 1, 'Pro_Cod' => $itemsito['Pro_Cod'], 'Prf_Cant' => $itemsito['Vet_Can'], 'Prf_Uni' => $itemsito['Uni_Des'], 'Prf_Imp' => $itemsito['Vet_Imp'], 'Prf_Pru' => $itemsito['Vet_Pru'], 'Iva_Cod' => $data['Iva_Cod']), $obBD_conexionIns);
                        $conta++;
                    }
                    else {
                        if ($itemsito['Pro_Cod'] != 0) {
                             //Guardo los nuevos items
                            $obBD_ins1->operacionobBD('proformas_det.insert', array('Prf_Cod' => $prof_Cod['Prf_Cod'], 'Pfd_Int' => $conta + 1, 'Pro_Cod' => $itemsito['Pro_Cod'], 'Prf_Cant' => $itemsito['Vet_Can'], 'Prf_Uni' => $itemsito['Uni_Des'], 'Prf_Imp' => $itemsito['Vet_Imp'], 'Prf_Pru' => $itemsito['Vet_Pru'], 'Iva_Cod' => $itemsito['Iva_Cod']), $obBD_conexionIns);
                            $conta++;
                        }
                    }
                }

            }

        }

        $obBD_con1->echoLog($es_creacion);
        //$obBD_con1->echoJson($resp);

    }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    /*if(!empty($resp['message'])){
        echo json_encode($resp);exit();

    }
         */
    ///if(!empty($resp['message']))$obBD_con1->echoJson($resp);
    //$resp['message'] = $obBD_con1->echoJson($resp);
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);

}




// SELECT COUNT(*) AS total FROM proformas INNER JOIN cliente ON cliente.Cli_Cod=proformas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod WHERE (cliente.Emp_Cod='6') AND (Prs_Ced='0705078186');
/*$resul = $obBD_con1->getRowConsultaSql("select count(*) as total from proformas  INNER JOIN cliente ON cliente.Cli_Cod=proformas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod;", $obBD_conexion);
$contad = $contad + $resul['total']+  1;
$num = sprintf("%'.05d", $contad);
$codigo = date("Y")."-".$num;*/


//IFNULL(MAX(number), 0)
if(isset($numProformaCliente)){
    $busquedaNum=array(
        'success'=>true,
        'numeroPrf'=>$obBD_con1->getRowConsultaSql("SELECT IFNULL(MAX(Prf_Num),0) AS total FROM proformas INNER JOIN cliente ON cliente.Cli_Cod=proformas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod INNER JOIN sucursal ON cliente.Emp_Cod=sucursal.Emp_Cod WHERE (sucursal.Emp_Cod='$Ses_Emp_Cod');", $obBD_conexion),
    );
    //v1->select count(*) as total from proformas  INNER JOIN cliente ON cliente.Cli_Cod=proformas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod WHERE (cliente.Emp_Cod='$Ses_Emp_Cod') AND (Prs_Ced='$Prs_Ced');
    //v2->SELECT COUNT(*) AS total FROM proformas INNER JOIN cliente ON cliente.Cli_Cod=proformas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod INNER JOIN sucursal ON cliente.Emp_Cod=sucursal.Suc_Cod WHERE (cliente.Emp_Cod='$Ses_Emp_Cod');
    $obBD_con1->echoJson($busquedaNum);

    //$resul = $obBD_con1->getRowConsultaSql("select count(*) as total from proformas  INNER JOIN cliente ON cliente.Cli_Cod=proformas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod;", $obBD_conexion, true);
}

//$resultado = $obBD_con1->getRowConsulta('proformas.selectWhere', array_merge($_GET,array('setWhere'=>array('setEmpCod', 'byPrfCnt' ))), $obBD_conexion, true);



//$resultado = $obBD_con1->getRowConsultaSql('select count(*) as total from proformas  INNER JOIN cliente ON cliente.Cli_Cod=proformas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod;', $obBD_conexion, true);
//$proformas = $obBD_con1->getArrayConsulta('proformas.selectWhere', array_merge($_GET,array('setWhere'=>array('setEmpCod'))), $obBD_conexion, true);
//$vendedor = $obBD_con1->getArrayConsulta('vendedor.selectWhere', array_merge($_GET,array('setWhere'=>array('setEmpCod','setUsuCod'))), $obBD_conexion, true);
$vendedores = $obBD_con1->getRowConsulta('vendedor.selectWhere', array_merge($_GET,array('setWhere'=>array('setSucCod','setPrsCod'))), $obBD_conexion);
$numActualProf=$obBD_con1->getRowConsultaSql("SELECT IFNULL(MAX(Prf_Num),0) AS total FROM proformas INNER JOIN cliente ON cliente.Cli_Cod=proformas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod INNER JOIN sucursal ON cliente.Emp_Cod=sucursal.Emp_Cod WHERE (sucursal.Emp_Cod='$Ses_Emp_Cod');", $obBD_conexion);
//$obBD_con1->echoLog('**-----**');
//$obBD_con1->echoLog($busquedaNum['numeroPrf']);
//$obBD_con1->echoLog($vendedores);
$ivas=$obBD_con1->getArrayConsultaSql("SELECT * FROM iva WHERE Iva_Por>0 ORDER BY Iva_Ini DESC;", $obBD_conexion);

utf8_encode_deep($vendedores);
//utf8_encode_deep($vendedores);
//$obBD_con1->echoLog('**-----**');
$tituloImp= 'Proforma Nº- '.($numActualProf['total'] + 1);
//$obBD_con1->echoLog('**-----**');
//$obBD_con1->echoLog($tituloImp);

//$obBD_con1->echoLog($_GET);
//$obBD_con1->echoLog($responce);
?>

<!DOCTYPE html>
<HTML>

<HEAD>
	<TITLE>
		<?Php echo $Ses_Sys_Nom; ?>
	</TITLE>
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
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
                font-weight: 700; font-size: 30px; color: #; padding: 0; margin: 0; overflow: hidden; text-overflow: ellipsis; height: 32px;
            }
            #3f3fc1resultContent .resp span:first-child{
                color:darkgoldenrod;width: 100px;display: inline-block; margin-left: 42px;
            }
            .msg_fly
            {
                font-size: 12px !important;
            }


            .ret .input-group-btn button{padding: 1px 2px !important;}
            .ret{ padding: 0 !important;}
            .bold{font-weight: bold;}
            .rep{font-size: 12px;}
            .noBorder{ border: 0; }
            .grid{ width: 31%; float:left; padding: 5px;  border: 1px solid; border-radius: 5px; }
            .grid:not(:last-child){  width: 31.5%; margin-right: 1%; }
            .rep td{ padding: 1px 6px;}
        </style>
        <script>
        var vendedor=<?php echo json_encode($vendedores); ?>, ivas_venta=<?php echo json_encode($ivas)?>, numPrfs=<?php echo json_encode($numActualProf)?>;
        </script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Pre Factura</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="proforma" class = "row">
                    <div class="col-xs-12">
                        <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validarFormProforma();">
                            <div class="col-md-5 col-xs-12">
                                <fieldset class="exa-fieldset" id="clieFormTemp">
                                    <legend class="Titulos2">Datos del Cliente</legend>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Cédula/RUC:</label>
                                        <div class="col-xs-7" >
                                            <input name="Prs_Cod" type="text" style="display:none;" />
                                            <input name="Prs_Cor" type="text" style="display:none;" />
                                            <input name="Cli_Cod" type="text" style="display:none;" />
                                            <input id="Prf_Mod_Cod" name="Prf_Mod_Cod" type="text" style="display:none;" />
                                            <input id="Num_Vtas" name="Num_Vtas" style="display:none;" type="number"  readonly/>
                                            <input id="Es_Mod" name="Es_Mod"  style="display:none;" type="number"  readonly/>
                                            <input id="Prf_Nva" name="Prf_Nva" style="display:none;" type="number"  readonly/>
                                            <input name="op_opciones" type="text" value="c" style="display: none;">
                                            <div class="input-group input-group-xs">
                                                <input id="Prs_Ced" name="Prs_Ced" onkeydown="if (event.keyCode === 13)
                                                            $.Search('clieDialog', selectCliente);" type="text" placeholder="Ingrese Cliente..."  class="form-control input-xs clearable dialogSearch" tabindex="1" required="" />
                                                <span class="input-group-btn">
                                                   <!-- <button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>-->
	<button id="Agr_Cli_Btn" type="button" onclick="$('#cliSearch').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente"
	 tabindex="2">
		<span class="glyphicon glyphicon-search"></span>
	</button>

	</span>
	</div>
	</div>
	<div class="col-xs-2">
		<button id="Prof_btn" type="button" onclick="$('#prfDialog').dialog('open');" class="btn btn-warning btn-xs" title="Modificar PreFactura"
		 tabindex="2">
			<span class="glyphicon glyphicon-edit"></span>
		</button>
	</div>
	</div>
	<div class="form-group">
		<label class="col-xs-2 control-label label-xs">Cliente:</label>
		<div class="col-xs-10">
			<span name="Cliente" class="form-control input-xs databind datatitle"></span>
		</div>
	</div>
	<div class="form-group">
		<label class="col-xs-2 control-label label-xs">Dirección:</label>
		<div class="col-xs-4">
			<span id="Prs_Dir" name="Prs_Dir" type="text" class="form-control input-xs databind datatitle"></span>
		</div>
		<label class="col-xs-1 control-label label-xs">Correo:</label>
		<div class="col-xs-5">
			<span name="Prs_Cor" type="text" class="form-control input-xs databind datatitle"></span>
		</div>
	</div>
	</fieldset>
	</div>
	<div class="col-md-7 col-xs-12" id="numPrfGenerado">
		<fieldset class="exa-fieldset" id="docuFormTemp">
			<legend class="Titulos2">Datos del Documento</legend>
			<input type="text" name="Vet_Cod" style="display: none;" />
			<input type="text" name="Com_Cod" style="display: none;" />
			<div class="form-group">
				<label class="col-xs-2 control-label label-xs required" style="text-align:center;">Fecha:</label>
				<div class="col-xs-5">
					<input type="text" id="Prf_Fec" name="Prf_Fec" class="form-control input-xs datepickers" style="text-align:center;" required>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label label-xs required" style="text-align:center;">Número:</label>
				<div class="col-xs-3 ">
					<input type="text" id="Prf_Num" name="Prf_Num" class="form-control input-xs trigger" tabindex="5" style="text-align:center; background-color:powderblue;"
					 required readonly/>

				</div>
				<div class="col-xs-2">
					<input type="number" id="Prf_Num_Ext" name="Prf_Num_Ext" class="form-control input-xs trigger" tabindex="2" style="text-align:center; background-color:powderblue;"
					 placeHolder="Ord.Compra" min="0" />
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label label-xs required" style="text-align:center;">Vendedor:</label>
				<div class="col-xs-5">
					<input type="text" id="Vendedor" name="Vendedor" class="form-control input-xs trigger" tabindex="2" style="text-align:center; background-color:powderblue;"
					 value="<?php  echo utf8_decode($vendedores['Vendedor'])?>" readonly></input>
					<input type="text" id="Vnd_Cod" name="Vnd_Cod" style="display: none;" value="<?php  echo utf8_decode($vendedores['Vnd_Cod'])?>"
					/>
				</div>
			</div>
		</fieldset>
	</div>
	<div class="col-sm-12 table-responsive" style=" padding-bottom: 5px;">
		<table id="items" class="table table-fixed"></table>
		<div id="itemsPager"></div>
	</div>
	<select id="Def_Ivas" name="Def_Ivas" class="form-control input-xs" style="display: none;">
		<?php
                                $temp = array();
                                foreach ($ivas AS $row) {
                                    if (!in_array($row['Iva_Por'], $temp)) {
                                        echo '<option value="' . $row['Iva_Cod'] . '" data-ivapor="' . $row['Iva_Por'] . '" data-ivaini=' . $row['Iva_Ini'] . ' data-ivafin=' . $row['Iva_Fin'] . ' >' . $row['Iva_Por'] . ' %</option>';
                                    }
                                    array_push($temp, $row['Iva_Por']);
                                }
                                ?>
	</select>
	<div class="col-xs-12 text-center">
		<button id="guardar" name="guardar" type="button" class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();">
			<i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
		<!-- <button class="btn btn-sm btn-success" onclick="verVentanaProformas();"><i class="glyphicon glyphicon-arrow-right"></i> Ver Proformas</button>-->
	</div>

	</form>

	<div class="col-sm-12 Titulos2">
		<hr>
		<b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (
		<span class="required"></span>) son campos obligatorios.</div>
	</div>
	</div>
	<div id="documentoMain" style="display:none;">
		<div class="col-sm-12">
			<fieldset class="exa-fieldset">
				<legend class="Titulos2">PreFacturas registrados</legend>
				<div>
					<form name="searchProf" id="searchProf" method="get" class="form-horizontal normal" action="javascript:$('#container').Search('#searchProf','profAjax');">
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">B&uacute;squeda</legend>
							<div class="form-group">
								<label class="col-xs-1 control-label label-xs">Filtrar Por:</label>
								<div class="col-xs-5 radioset opt_search">
									<input id="radsc1" name="op_opciones" type="radio" value="h" checked="" onclick="setfocus(this.form.search)" alt="" />
									<label for="radsc1">Nombre Cliente</label>
									<input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
									<label for="radsc2"># PreFactura</label>

								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-1 control-label">B&uacute;squeda:</label>
								<div class="col-xs-5">
									<div class="input-group">
										<input name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..."
										 autofocus class="form-control input-xs clearable submit" />
										<span class="input-group-btn">
											<button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar PreFactura" tabindex="-1">
												<span class="glyphicon glyphicon-search"></span>
												<span>Buscar</span>
											</button>
										</span>
									</div>
									<!-- /input-group -->
								</div>
								<input type="text" tabindex="-1" style="display:none;" />
							</div>
						</fieldset>
					</form>
				</div>
				<div class="" style="min-height: 50px;">
					<table id="container"></table>
					<div id="containerPager"></div>
				</div>
				<div class="center">
					<button type="button" class="btn btn-sm btn-inverse" onclick="$('#documentoMain').moveComp('#proforma').updateGridsSizes();">
						<i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
				</div>
			</fieldset>
		</div>
	</div>

	</div>



	<!-- Datos Reporte style="width: 900px;"  -->
	<div id="datosTabla5" class="grid" style="display:none;">
		<?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod,'Proforma Nº <span id="titleReporte"></span>', ' ',$obBD_conexion); ?>
		<table border="0" cellpadding="0" cellspacing="0" id="cabeceraTabla" style="width: 100%;border-collapse: collapse; font-family:Verdana, Geneva, sans-serif; font-size:12px"
		 class="rep">
			<tr style="height: 0;">
				<td width="10%">&nbsp;</td>
				<td width="20%">&nbsp;</td>
				<td width="6%">&nbsp;</td>
				<td width="19%">&nbsp;</td>
				<td width="15%">&nbsp;</td>
				<td width="30%">&nbsp;</td>
			</tr>
			<tr>
				<td class='bold'>
					<strong>RUC:</strong>
				</td>
				<td>&nbsp;
					<span name="Prs_Ced" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
				<td class='bold'>
					<strong>FECHA:</strong>
				</td>
				<td>&nbsp;
					<span name="Prf_Fec" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
				<td class='bold'>
					<strong>Ord.Comp:</strong>
				</td>
				<td>&nbsp;
					<span name="Prf_Num_Ext" style="font-size: 12px;" align='center' class="form-control input-xs databind datatitle">
				</td>
			</tr>
			<tr>
				<td class='bold'>
					<strong>CLIENTE:</strong>
				</td>
				<td colspan="5">&nbsp;
					<span name="Cliente" style="font-size: 11px;" class="form-control input-xs databind datatitle"></span>
				</td>
			</tr>
			<tr>
				<td class='bold'>
					<strong>DIRECCION:</strong>
				</td>
				<td>
					<span name="Prs_Dir" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
			</tr>
			<tr>
				<td class='bold'>
					<strong>CORREO:</strong>
				</td>
				<td>
					<span name="Prs_Cor" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
				<td>
					<span class="bold">&nbsp;</span>
				</td>
				<td colspan="3">&nbsp;</span>
				</td>
			</tr>
		</table>
		<table id="datosTabla" style="width: 100%;border-collapse: collapse;font-family:Verdana, Geneva, sans-serif; font-size:12px"
		 cellpadding="3" border="1" class="noBorder">
			<thead>
				<tr>
					<th style="width:5%;">Cantidad</th>
					<th style="width:60%;" align="center">Descripción</th>
					<th style="width:10%;" align="right">P.Unitario</th>
					<th style="width:10%;" align="center">Importe</th>
					<th style="width:15%;" align="right">Total</th>
				</tr>
			</thead>
			<tbody id="tablita" class='noBorder' align='center' style="border-bottom: none;">

			</tbody>
			<tbody class='noBorder' style="border-collapse: collapse;">

				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>SUBTOTAL:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_subtotal" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>TARIFA 0%:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_iva0" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>TARIFA
							<span class="iva_por"></span>%:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_iva12" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>
							<span class="iva_por"></span>% IVA:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_iva" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>DESCUENTO:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_descuento" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>TOTAL PreFactura:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_rubros" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
			</tbody>
		</table>
		<br/>

		<br/>
		<table style="width: 100%;font-size: 12px;border-collapse: collapse;" border="1">
			<tr>
				<td align='center' style="width: 25%;">
					<strong>OBSERVACION:</strong>
				</td>
				<td align='center' style="width: 25%;">
					<strong>SELLO/FIRMA</strong>
				</td>
			</tr>
			<tr style="height: 40px;">
				<td align='center'>
					<span style="width: 39%; font-size: 10px; text-align: justify;" name="Vet_Obs" class="form-control input-xs databind datatitle"></span>
				</td>
				<td></td>
			</tr>
		</table>
		<?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>

	</div>

	<!-- Formato Reporte -->
	<div id="formatoReporteProforma" style="display: none;">
		<div style="width: 900px;">
			<?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'PROFORMA', '<span id="titleReporte"></span>',$obBD_conexion); ?>
			<table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>
			<?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
		</div>
	</div>


	<!-- Inicio del diálogo para buscar proformas de clientes -->
	<!--<div id="clieDialog" title="B&uacute;squeda de Clientes con Proformas"><form class="form-horizontal normal"> </form></div>-->
	<!-- Inicio del diálogo para buscar de clientes -->
	<div id="cliSearch" title="B&uacute;squeda de Clientes" style="display: none">
		<form name="searchCli" id="searchCli" method="get" class="form-horizontal normal" action="javascript:$('#cliSearch').Search('#searchCli','cliSearchAjax');"></form>
	</div>
	<!-- Inicio del diálogo para buscar de Produuctos -->
	<div id="prfDialog" title="B&uacute;squeda de PreFacturas"></div>

	<script src="../VALIDACIONES/prf_val_proforma.js?a=100"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
	</BODY>



</HTML>