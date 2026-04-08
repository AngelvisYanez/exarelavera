<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_liquidacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Liquidacion;

$hoy = date("Y-m-d");

if(isset($provAjax)){
    $page=$obBD_con1->getPageGridJson('productor_bana.selectWhere', $_GET, $obBD_conexion);
}
if(isset($prodSearchAjax)){
    $page=$obBD_con1->getPageGridJson('producto.selectWhere', $_GET, $obBD_conexion);
}
if(isset($searchLiquid)){
    $obBD_con1->getPageGridJson('liquidacion_bana.selectWhere', array_merge(array('setWhere'=>array(/*'setProductor'*/)),$_GET), $obBD_conexion);
}
if(isset($validaNum)){
    $next=$obBD_con1->getRowConsulta('liquidacion_bana.sql.getNext', null, $obBD_conexion);
    $resp=array('success'=>true, 'valid'=>true, 'next'=>$next['next'], 'Lib_Num'=>$next['next']);
    if(isset($_GET['Lib_Num'])&&!empty($Lib_Num)){
        $resp['Lib_Num']=$Lib_Num;
        $tarjas=$obBD_con1->getRowConsulta('liquidacion_bana.sql.getByLibNum', array('Lib_Num'=>$Lib_Num,'Lib_Cod'=>$Lib_Cod), $obBD_conexion);
        if(isset($tarjas['Lib_Cod'])&&!empty($tarjas['Lib_Cod'])){
            $resp['Lib_Num']=$resp['next'];
            $resp= array_merge($resp,array('valid'=>false, 'message'=>"La <u>Tarja  No. <u>$Lib_Num</u> ya se encuentra Registrada!" ));
        }
    }
    $obBD_con1->echoJson($resp);
}
$config=$obBD_con1->getRowConsulta('confi_fact.selectWhere', array('confi_fact.Emp_Cod'=>$Ses_Emp_Cod), $obBD_conexion);

/*
if(isset($getDetalle)) {
    $tipos=array(-1=>'Cajas Embarcadas',0=>'cartones',1=>'materiales',2=>'materiales2');
    $resp=array('ingresos'=>array('cartones'=>array(),'materiales'=>array(),'materiales2'=>array()), 'descuentos'=>array('cartones'=>array(),'materiales'=>array(),'materiales2'=>array()), 'ctaPagar'=>array(), 'cajas'=>array(), 'retencion'=>array() );
    $resp['dato']=$obBD_con1->getRowConsulta('liquidacion_bana.selectWhere', array('where'=>array('Lib_Cod'=>$Lib_Cod),'setWhere'=>array()), $obBD_conexion);

    $resp['success']=isset($resp['dato']['Lib_Cod']);
    if($resp['success']){
        $resp['retencion']=$obBD_con1->getArrayConsulta('liquidacion_bana_det.selectWhere', array('where'=>array('Lib_Cod'=>$Lib_Cod),'setWhere'=>array('noProducto')), $obBD_conexion,true);
        $detalle=$obBD_con1->getArrayConsulta('liquidacion_bana_det.selectWhere', array('where'=>array('Lib_Cod'=>$Lib_Cod),'setWhere'=>array('setProducto')), $obBD_conexion,true);
        foreach ($detalle as $v) {
            if($v['Lid_Grp']==-1){
               $v['Total']=$v['Lid_Can'];
               $v['Producto']=$v['Lid_Des'];
               array_push($resp[$v['Lid_Tip']=='I'?'cajas':'retencion'],$v);
            }
            else
                array_push($resp[$v['Lid_Tip']=='I'?'ingresos':'descuentos'][$tipos[$v['Lid_Grp']]],$v);
        }
        $resp['detalle']=$detalle;
    }
    if($config['Cof_Con']=='S') $resp['ctaPagar']=$obBD_con1->getArrayConsulta('ccpp_prove.selectWhere', array('Year'=>$resp['dato']['Lib_Ano'],'where'=>array(),'setWhere'=>array('setEmpCod','isActive','getByYear')), $obBD_conexion);
    $obBD_con1->echoJson($resp);
}*/

if(isset($getDetalle)) {
    $tipos=array(-1=>'Cajas Embarcadas',0=>'cartones',1=>'materiales',2=>'materiales2');
    $resp=array('ingresos'=>array('cartones'=>array(),'materiales'=>array(),'materiales2'=>array()), 'descuentos'=>array('cartones'=>array(),'materiales'=>array(),'materiales2'=>array()), 'ctaPagar'=>array(), 'cajas'=>array(), 'retencion'=>array() );
    $resp['dato']=$obBD_con1->getRowConsulta('liquidacion_bana.selectWhere', array('where'=>array('Lib_Cod'=>$Lib_Cod),'setWhere'=>array(/*'setProductor'*/)), $obBD_conexion);

    $resp['success']=isset($resp['dato']['Lib_Cod']);
    if($resp['success']) {
        $resp['retencion']=$obBD_con1->getArrayConsulta('liquidacion_bana_det.selectWhere', array('where'=>array('liquidacion_bana_det.Lib_Cod'=>$Lib_Cod),'setWhere'=>array('noProducto')), $obBD_conexion,true);
        $detalle=$obBD_con1->getArrayConsulta('liquidacion_bana_det.selectWhere', array('where'=>array('liquidacion_bana_det.Lib_Cod'=>$Lib_Cod),'setWhere'=>array('setProducto')), $obBD_conexion,true);
        foreach ($detalle as $v) {
            if($v['Lid_Grp']==-1){
               $v['Total']=$v['Lid_Can'];
               $v['Producto']=$v['Lid_Des'];
               array_push($resp[$v['Lid_Tip']=='I'?'cajas':'retencion'],$v);
            }
            else
               array_push($resp[$v['Lid_Tip']=='I'?'ingresos':'descuentos'][$tipos[$v['Lid_Grp']]],$v);
        }
        $resp['detalle']=$detalle;
    }
    if($config['Cof_Con']=='S') $resp['ctaPagar']=$obBD_con1->getArrayConsulta('ccpp_prove.selectWhere', array('Year'=>$resp['dato']['Lib_Ano'],'where'=>array(),'setWhere'=>array('setEmpCod','isActive','getByYear')), $obBD_conexion);
    $obBD_con1->echoJson($resp);
}




if(isset($saveDocument)){
    $resp=array('success'=>false);
    if(empty($form['Cop_Cod'])) $form['Cop_Cod']=null;
    $Lib_Cod=$form['Lib_Cod'];
    $Com_Cod=$form['Com_Cod'];
    $liquid=$obBD_con1->getRowConsulta('liquidacion_bana.sql.getByLibNum', array('Lib_Num'=>$form['Lib_Num'],'Lib_Cod'=>$form['Lib_Cod']), $obBD_conexion);
    if(isset($liquid['Lib_Cod'])&&!empty($liquid['Lib_Cod'])) $resp['message']="El numero de <u>Liquidacion</u> ya se encuentra Registrado!";

    if(isset($resp['message'])) $obBD_con1->echoJson($resp);
    $liquidacion=array('INGRESOS'=>$ing, 'DESCUENTOS'=>$des);
    //$config=$obBD_con1->getRowConsulta('confi_fact.selectWhere', array('confi_fact.Emp_Cod'=>$Ses_Emp_Cod), $obBD_conexion);
    $obBD_ins1 =  new Class_Log_Datos_Liquidacion;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    //$obBD_con1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        if($config['Cof_Con']=='S'){
            $PecCod=$obBD_con1->getPerioCont($Ses_Emp_Cod, $form['Lib_Ano'], $obBD_conexion);
            $TiaCod=$obBD_con1->getRowConsulta('tipo_asien.selectWhere', array('Tia_Abr'=>'LF', 'Tia_Ini'=>'D','Tia_Est'=>'A'), $obBD_conexion);
            $ComNum=$obBD_con1->getComNumPecAuto($TiaCod['Tia_Cod'], $PecCod['Pec_Cod'], $form['Lib_Fec'], $obBD_conexion);
            $obBD_ins1->operacionobBD('comprobantes.update', array('where'=>array('Com_Cod'=>$Com_Cod),'Usu_Cod'=>$Ses_Usu_Cod,/*'Com_Num'=>$ComNum,'Com_Fec'=>$form['Lib_Fec'],*/'Com_Obs'=>$form['Lib_Obs'],'Com_Val'=>$totalDescuentos), $obBD_conexionIns);
            $resp['Com_Cod']=$Com_Cod;
            $resp['codigo']="LF-".substr($form['Lib_Fec'],5,2)."-$ComNum";
            $obBD_ins1->echoLog($Com_Cod);
            $obBD_ins1->echoLog($form);
            $obBD_ins1->operacionobBD('asientos.deleteWhere', array('Com_Cod'=>$Com_Cod), $obBD_conexionIns,true);

            $ctas=array('DEBE'=>array(), 'HABER'=>array());

            $cConsumo=$obBD_con1->getRowConsulta('consumo.selectWhere', array('Emp_Cod'=>$Ses_Emp_Cod, 'Con_Est'=>'A', 'Con_Des'=>'PRODUCTORES'), $obBD_conexion);
            if(is_empty($cConsumo,'Con_Cod')) throw new Exception('No se ha registrado un centro de consumo denominado <u>PRODUCTORES</u>!');
            //$ctHaber=$obBD_con1->getRowConsulta('productor_det_plan.selectWhere', array('Prd_Cod'=>$form['Prd_Cod'], 'Prp_Tip'=>'CC'), $obBD_conexion);
            //if(is_empty($ctHaber,'Pld_Cod')) throw new Exception('Revisar la parametrizacion contable: <u>CxC a '.$form['Productor'].'</u>!');

            /* guardo cartones y material chico */
            foreach ($liquidacion as $k1 => $l) {
                $tiCta=($k1=='INGRESOS'?'DEBE':'HABER');
                foreach ($l as  $vt=>$ti) {
                    if($vt!='materiales2'){
                        foreach ($ti as $i => $v) {
                            if(($v['Lid_Imp']*1)>0){
                                $add=true;

                                foreach($ctas[$tiCta] as &$ct){
                                    if($ct['Pld_Cod']==$cta['Pld_Cod']&&$ct['Asi_Deh']==substr($tiCta,0,1)){
                                        $ct['Asi_Val']+=($v['Lid_Imp']*1);
                                        $ct['Asi_Con'].=(', '.$v['Producto']);
                                        $add=false;
                                    }
                                } unset($ct);
                                if($add){
                                    $cta=($tiCta=='HABER'?//$ctHaber
                                        $obBD_con1->getRowConsulta('produ_plan.selectWhere', array('Pro_Cod'=>$v['Pro_Cod'], 'Tip_Pld'=>'E'), $obBD_conexion,true)
                                        :$obBD_con1->getRowConsulta('produ_plan.selectWhere', array('Pro_Cod'=>$v['Pro_Cod'], 'Tip_Pld'=>'O', 'Con_Cod'=>$cConsumo['Con_Cod']), $obBD_conexion,true));
                                    if(is_empty($cta,'Pld_Cod')) throw new Exception('Revisar la parametrizacion contable <i>COSTOS</i> del Centro de Consumo <i>PRODUCTORES</i> para el Producto: <u>'.$v['Producto'].'</u>!');

                                    array_push($ctas[$tiCta],array('Com_Cod'=>$Com_Cod,'Asi_Deh'=>substr($tiCta,0,1),'Asi_Val'=>$v['Lid_Imp']*1,'Asi_Con'=>(substr($tiCta,0,1)=='H'?'DESCUENTO':' ').strtoupper($vt),'Asi_Glo'=>(substr($tiCta,0,1)=='H'?'DESCUENTO':' ').strtoupper($vt),'Pld_Cod'=>$cta['Pld_Cod']));
                                }
                            }
                        }
                    }
                }
            }
            foreach($ctas as $ct){ foreach($ct as $v){ $obBD_ins1->operacionobBD('asientos.insert', $v, $obBD_conexionIns); } }
            /* guardo material chico 2 */
            if($totalIngresos*1!=$totalDescuentos*1) {
                $ajusCtaTip=$totalIngresos*1>$totalDescuentos*1;
                $ajusCtaVal=abs($totalIngresos*1-$totalDescuentos*1);
                if(is_empty($_POST,'Pld_Cod')) throw new Exception('No se configurado las cuentas por pagar!');
                $obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod'=>$Com_Cod,'Asi_Deh'=>($ajusCtaTip?'H':'D'),'Asi_Val'=>$ajusCtaVal,'Asi_Con'=>$form['Productor'],'Asi_Glo'=>($ajusCtaTip?'INGRESO':'DESCUENTO').' MATERIAL CHICO 2','Pld_Cod'=>$Pld_Cod), $obBD_conexionIns);

                $ctAjus=$obBD_con1->getRowConsulta('plan_param.selectWhere', array('Tpa_Abr'=>($ajusCtaTip?'OTE':'OTI'), 'where'=>'', 'setWhere'=>array('isActive','setEmpCod','getByAbr')), $obBD_conexion);
                if(is_empty($ctAjus,'Pld_Cod')) throw new Exception('Revisar la parametrizacion contable de <i>PARAMETRO</i>: <u>'.($ajusCtaTip?'OTROS EGRESOS':'OTROS INGRESOS').'</u>!');
                $obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod'=>$Com_Cod,'Asi_Deh'=>($ajusCtaTip?'D':'H'),'Asi_Val'=>$ajusCtaVal,'Asi_Con'=>$form['Productor'],'Asi_Glo'=>'OTROS '.($ajusCtaTip?'EGRESOS':'INGRESOS'),'Pld_Cod'=>$ctAjus['Pld_Cod']), $obBD_conexionIns);
            }
            /* asiento ajuste inventario productor */
            if($totalCartIngresos*1!=$totalCartDescuentos*1){
                if($totalCartIngresos*1>$totalCartDescuentos*1){
                    throw new Exception('La Cantidad de <u>Cartones Recibidos</u> es mayor a los entregados por despacho!');
                }
                if($totalCartIngresos*1<$totalCartDescuentos*1){
                    $val=$totalCartDescuentos*1-$totalCartIngresos*1;
                    $ctInv=$obBD_con1->getRowConsulta('productor_det_plan.selectWhere', array('Prd_Cod'=>$form['Prd_Cod'], 'Prp_Tip'=>'IN'), $obBD_conexion);
                    if(is_empty($ctInv,'Pld_Cod')) throw new Exception('Revisar la parametrizacion contable: <u>Cta. Inventario a '.$form['Productor'].'</u>!');
                    $ctLiq=$obBD_con1->getRowConsulta('productor_det_plan.selectWhere', array('Prd_Cod'=>$form['Prd_Cod'], 'Prp_Tip'=>'LI'), $obBD_conexion);
                    if(is_empty($ctLiq,'Pld_Cod')) throw new Exception('Revisar la parametrizacion contable: <u>Cta. x Liquidar a '.$form['Productor'].'</u>!');

                    $obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod'=>$Com_Cod,'Asi_Deh'=>'D','Asi_Val'=>$val,'Asi_Con'=>$form['Productor'],'Asi_Glo'=>'DESCUENTO CARTONES','Pld_Cod'=>$ctInv['Pld_Cod']), $obBD_conexionIns);
                    $obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod'=>$Com_Cod,'Asi_Deh'=>'H','Asi_Val'=>$val,'Asi_Con'=>$form['Productor'],'Asi_Glo'=>'DESCUENTO CARTONES','Pld_Cod'=>$ctLiq['Pld_Cod']), $obBD_conexionIns);
                }
            }
            $resp['linkCompr']=baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$Com_Cod");
        }
        /* falta control de inventarios aqui */

        $form['Com_Cod']=$Com_Cod;
        $obBD_ins1->operacionobBD('liquidacion_bana.update', $form, $obBD_conexionIns);
        $obBD_ins1->operacionobBD('liquidacion_bana_det.deleteWhere', array('Lib_Cod'=>$Lib_Cod), $obBD_conexionIns,true);

        $resp['Lib_Cod']=$Lib_Cod;
        $resp['linkLiqui']=baseUrl("../../bananero/FRONT/ban_pri_liquidacion.php?Lib_Cod=$Lib_Cod");
        $resp['linkLiquiDet']=baseUrl("../../bananero/FRONT/ban_pri_liquidacion.php?Lib_Cod=$Lib_Cod&detallado=");
        /* guardo cajas devueltas */
        foreach ($cajas as $i => $c) {
            $obBD_ins1->operacionobBD('liquidacion_bana_det.insert', array(
                'Lib_Cod'=>$Lib_Cod,
                'Lid_Tip'=>'I',
                'Lid_Int'=> ($i+1),
                'Pro_Cod'=> $c['Pro_Cod'],
                'Lid_Des'=> $c['Producto'],
                'Lid_Can'=> $c['Total'],
                'Lid_Pru'=> $c['Caj_Pru'],
                'Lid_Imp'=> $c['Caj_Imp']
            ), $obBD_conexionIns);
        }
        /* guardo retenciones */
        foreach ($rets as $i => $r) {
            $obBD_ins1->operacionobBD('liquidacion_bana_det.insert', array(
                'Lib_Cod'=>$Lib_Cod,
                'Lid_Tip'=>'D',
                'Lid_Int'=> ($i+1),
                'Lid_Des'=> $r['Desc'],
                'Lid_Can'=> $r['Cajas'],
                'Lid_Pru'=> $r['Porc'],
                'Lid_Imp'=> $r['Retenido']
            ), $obBD_conexionIns);
        }
        /* guardo materiales y cartones */
        foreach ($liquidacion as $k1 => $l) {
            $Lid_Grp=0;
            foreach ($l as  $ti) {
                foreach ($ti as $i => $v) {
                    $obBD_ins1->operacionobBD('liquidacion_bana_det.insert', array(
                        'Lib_Cod'=>$Lib_Cod,
                        'Lid_Tip'=>$k1=='INGRESOS'?'I':'D',
                        'Lid_Grp'=>$Lid_Grp,
                        'Lid_Int'=> ($i+1),
                        'Pro_Cod'=> $v['Pro_Cod'],
                        'Lid_Des'=> $v['Producto'],
                        'Lid_Can'=> $v['Lid_Can'],
                        'Lid_Pru'=> $v['Lid_Pru'],
                        'Lid_Imp'=> $v['Lid_Imp']
                    ), $obBD_conexionIns);
                }
                $Lid_Grp++;
            }
        }
        //throw new Exception('SE GUARDO TODO BIEN');
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns,$e->getMessage()); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    $resp['success']=$obBD_ins1->Error==0;
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}

$marcas=$obBD_con1->getArrayConsulta('banano_marca.selectWhere',  array('setWhere'=>array('setEmpCod','isActive')), $obBD_conexion,true);
$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>'setEmpCod','order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion);
$cur_periodo=current($periodos);
$linkLiqui=baseUrl("../../bananero/FRONT/ban_pri_liquidacion.php?Lib_Cod=");
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript">
        var buttonExtra={ label: $.createIcon('pencil'), name: 'imp1', width: 25, formatter:'gridButton', formatoptions:{action:'editarLiquidacion',data:'Lib_Cod',title:'Editar Liquidacion', conditional:function(o){ return o.Lib_Est!=='I'&&o.Cop_Cod===null; }/*, caseFalse: $.createIcon('remove red')*/ } , classes:'bgNoColor' };
        var val_caja_bana=<?php echo $val_caja_def; ?>;
        var calculadora_bana=[
			{index:1, Desc:'De 1 a 50.000', Desd:1, Hast:50000, Porc:1.25},
			{index:2, Desc:'De 50.001 en Adelante', Desd:50001, Hast:Infinity, Porc:1.50},
            /*{index:1, Desc:'De 1 a 1.000', Desd:1, Hast:1000, Porc:1.00},
            {index:2, Desc:'De 1.001 a 5.000', Desd:1001, Hast:5000, Porc:1.25},
            {index:3, Desc:'De 5.001 a 20.000', Desd:5001, Hast:20000, Porc:1.50},
            {index:4, Desc:'De 20.001 a 50.000', Desd:20001, Hast:50000, Porc:1.75},
            {index:5, Desc:'De 50.001 en Adelante', Desd:50001, Hast:Infinity, Porc:2.00}*/
        ];
        var hoy='<?php echo $hoy; ?>';
    </script>
    <script type="text/javascript" src="../VALIDACIONES/ban_val_liquidacion.js"></script>
    <style>
        .ui-tabs-panel{ padding: 0!important; }
        .ui-tabs.ui-widget-content{ background:none !important; }
        #gbox_resumen .footrow{font-size: 14px;}
    </style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Liquidacion de Compra de Fruta</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row" id="main">
                <div class="col-xs-12">
                    <form id="formDocumentoSearch" class="form-horizontal normal formDatos" action="javascript:liquidaciones.Search('#formDocumentoSearch','searchLiquid');">
                        <input name="order" type="hidden" value="" />
                        <fieldset class="exa-fieldset" id="provFormTemp">
                            <legend class="Titulos2">Consulta de Información</legend>
                        <div class="col-sm-4">

                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Periodo:</label>
                                <div class="col-xs-7" >
                                    <select  t id="Lib_Ano" name="Lib_Ano" class="form-control input-xs" >
                                        <option value="">Periodo..</option>
                                        <?php foreach ($periodos as $p) { echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>"; } ?>
                                    </select>
                                </div>

                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Semana:</label>
                                <div class="col-xs-9" ><select id="Prt_Sem" name="Lib_Sem" class="form-control input-xs" ></select></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Marca:</label>
                                <div class="col-xs-9" >
                                    <select id="Bam_Cod" name="Bam_Cod" class="form-control input-xs getData ins">
                                        <?php if(count($marcas)!=1){ ?><option value="">Selecione Marca...</option><?php } ?>
                                        <?php foreach ($marcas as $m) {
                                            echo "<option value='$m[Bam_Cod]' data--bam_-cod='$m[Bam_Cod]' data--bam_-tam='$m[Bam_Tam]'>$m[Bam_Nom] $m[Bam_Tam]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Cédula/RUC:</label>
                                <div class="col-xs-9" >
                                  <input name="Prd_Cod" data-name="Prd_Cod" type="text" style="display:none;" />
                                  <input name="Prv_Cod" data-name="Prv_Cod" type="text" style="display:none;" />
                                  <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
                                  <div class="input-group input-group-xs">
                                    <input name="search" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#provDialog',selectProvee); }" type="text" placeholder="Ingrese Productor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                    <span class="input-group-btn">
                                         <button id="Prv_Btn" type="button" onclick="selectProvee({})" class="btn btn-success btn-xs" title="Buscar Productor" ><span class="glyphicon glyphicon-eject"></span></button>
                                        <button id="Prv_Btn" type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Productor" ><span class="glyphicon glyphicon-search"></span></button>
                                        <!--<button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open'); $('#reset').val(1); " class="btn btn-success btn-xs" title="Registrar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>-->
                                    </span>
                                  </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Productor:</label>
                                <div class="col-xs-9" >
                                    <span name="Productor" data-name="Productor" class="form-control input-xs databind datatitle"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">No.&nbsp;Liquid:</label>
                                <div class="col-xs-9" >
                                    <input name="Lib_Num" class="form-control input-xs " />
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="center">
                                <button type="button" onclick="$('#formDocumentoSearch').formSubmit();" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Cargar Datos</button>
                            </div>
                        </div>
                        </fieldset>

                    </form>
                </div>
                <div class="col-xs-12">
                    <div style="min-height: 280px">
                        <table id="liquidaciones"></table>
                        <div id="liquidacionesPager"></div>
                    </div>
                </div>
            </div>

            <div class="row" id="liquidacion" style="visibility: hidden;">
                <div class="col-sm-5">
                    <form id="formDocumentoMain" class="form-horizontal normal formDatos" action="javascript:validaDocument();">
                        <input id="Lib_Cod" name="Lib_Cod" data-name="Lib_Cod" type="text" style="display:none;" />
                        <input id="Com_Cod" name="Com_Cod" data-name="Com_Cod" type="text" style="display:none;" />
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de la Liquidación</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Número:</label>
                                <div class="col-xs-5" >
                                    <div class="input-group input-group-xs">
                                        <input id="Lib_Num" name="Lib_Num" data-name="Lib_Num" type="text" class="form-control input-xs" onchange="validatNum()" required="">
                                        <span class="input-group-addon validate"><i class="glyphicon glyphicon-ok green"></i></span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Periodo:</label>
                                <div class="col-xs-4" ><span data-name="Lib_Ano" class="form-control input-xs databind datatitle"></span></div>
                                <label class="col-xs-2 control-label label-xs required">Semana:</label>
                                <div class="col-xs-3" ><span data-name="Lib_Sem" class="form-control input-xs databind datatitle"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Fecha:</label>
                                <div class="col-xs-4" ><input type="text" id="Lib_Fec" name="Lib_Fec" class="form-control input-xs readOnly" required="" disabled="" /></div>
                                <label class="col-xs-2 control-label label-xs required">P. Caja:</label>
                                <div class="col-xs-3" ><input type="text" id="Lib_Pru" name="Lib_Pru" class="form-control input-xs txtRight" required="" value="6.26" onkeypress="return validar_decimal(event)" onchange="changePruCajas();" /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Marca:</label>
                                <div class="col-xs-9" ><span name="Bam_Des" data-name="Bam_Cod_Txt" class="form-control input-xs databind datatitle"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Productor:</label>
                                <div class="col-xs-9" ><span data-name="Productor" class="form-control input-xs databind datatitle"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Observación:</label>
                                <div class="col-xs-9" ><textarea name="Lib_Obs" data-name="Lib_Obs" class="form-control input-xs "></textarea></div>
                            </div>
                        </fieldset>
                    </form>
                    <div class="">
                        <table id="resumen"></table>
                    </div>
                    <div class="help-block"></div>
                </div>
                <div class="col-sm-7">

                    <div id="tabsLiqui" class="ui-tab-fix ui-tabs">
                        <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                          <li><a href="#tabs-1">Ingresos</a></li>
                          <li><a href="#tabs-2">Descuentos</a></li>
                          <li class="pull-right"><div class="ui-tabs-anchor <?php echo ($config['Cof_Con']=='S'?'':'hidden'); ?> " style="color:#333; text-shadow:0 0px 0px; padding: 0;">
                                  <div style="display: inline-table;">Cta. por Pagar:&nbsp;&nbsp;</div>
                                  <div style="display: inline-table;"><select id='Pld_Cod' name='Pld_Cod' class="form-control input-xs"><option>...</option></select></div>
                              </div>
                          </li>
                        </ul>
                        <div id="tabs-1" class="ui-tabs-panel">
                            <div class="help-block"></div>
                            <div class="condensed-header jqHeaderFirst jqFirst">
                                <table id="cartones"></table>
                            </div><div class="help-block"></div>
                            <div id="tabsIngresos" class="ui-tab-fix ui-tabs">
                                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                                  <li><a href="#tabsIng-1">Cartones</a></li>
                                  <li><a href="#tabsIng-2">Material Chico</a></li>
                                  <li><a href="#tabsIng-3">Material Chico Extra</a></li>
                                </ul>
                                <div id="tabsIng-1" class="ui-tabs-panel"></div>
                                <div id="tabsIng-2" class="ui-tabs-panel"></div>
                                <div id="tabsIng-3" class="ui-tabs-panel"></div>
                            </div>
                        </div>
                        <div id="tabs-2" class="ui-tabs-panel">
                            <div class="help-block"></div>
                            <div class="condensed-header jqHeaderSecond jqSecond">
                                <table id="retencion"></table>
                            </div><div class="help-block"></div>
                            <div id="tabsDescuentos" class="ui-tab-fix ui-tabs">
                                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                                  <li><a href="#tabsDes-1">Cartones</a></li>
                                  <li><a href="#tabsDes-2">Material Chico</a></li>
                                  <li><a href="#tabsDes-3">Material Chico Extra</a></li>
                                </ul>
                                <div id="tabsDes-1" class="ui-tabs-panel"></div>
                                <div id="tabsDes-2" class="ui-tabs-panel"></div>
                                <div id="tabsDes-3" class="ui-tabs-panel"></div>
                            </div>
                        </div>
                    </div>
                    <div id="prodsContainer" style="padding-top: 5px; min-height: 250px;" class="jqHeaderFirst jqFirst">
                        <!--<div class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Cajas Embarcadas:</label>
                                <div class="col-xs-2" >
                                    <span id="cajasEmbarcadas" class="form-control input-xs datatitle"></span>
                                </div>
                                <label class="col-xs-4 control-label label-xs required">Liquidaciones Anteriores:</label>
                                <div class="col-xs-2" >
                                    <span id="cajasLiquidaciones" class="form-control input-xs datatitle"></span>
                                </div>
                            </div>
                        </div>-->
                        <div class="condensed-header">
                            <table id="prods"></table>
                            <div id="prodsPager"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12">
                    <button class="btn btn-sm btn-inverse" onclick="$('#liquidacion').moveComp('#main').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
                    <button class="btn btn-sm btn-primary" onclick="$('#formDocumentoMain').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>


    <script type="text/javascript">
    $(function() {

    });

    </script>
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTOR-->
    <div id="provDialog" title="B&uacute;squeda de Productor"></div>
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTOR-->
    <div id="prodSearchDialog" title="B&uacute;squeda de Materiales"><form id='prodSearchForm'><input type='hidden' name='ingresos' /><input type='hidden' name='grupo' /></form></div>
    <!--INICIO DEL DIALOGO GUARDADO-->
    <div id="successDialog" title="Mensaje del Sistema" style="display: none;">
        <center>
            <b style="font-size:14px;">Se registro la Liquidación con éxito!</b>
            <h4 id="compCodGrp"><b class="blue">Asiento: </b><span class="orange" id="successCodigo">dd-55-55</span></h4>
            <button id="btnImpLiqui" type="button" class="btn btn-info" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Liquidacion</button><div class="help-block"></div>
            <button id="btnImpDetal" type="button" class="btn btn-info" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Detalle</button><div class="help-block"></div>
            <button id="btnImpCompr" type="button" class="btn btn-info" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Comprobante</button>
        </center>
    </div>
</BODY>
</HTML>



