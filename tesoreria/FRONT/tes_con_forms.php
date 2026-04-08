<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
//require_once('../LOGICA/ban_log_productor.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_con1 = new MysqlDatos(new Class_Log_Conexion_Global($Ses_Dat_Dis));
//$obBD_con1->debug(true);
$hoy = date("Y-m-d");

function sql_aux($id,$Par_Sql){
    $sql='';
    switch($id){
        case 1:
            $sql="SELECT grupo1.Fog_Nom AS Grupo, CONCAT(grupo1.Fog_Ord, '. ',grupo1.Fog_Nom) AS GrupoOrd, grupo0.Fog_Nom AS SubGrupo, formulario_codi.* FROM formulario_codi
                    INNER JOIN formulario_grupo AS grupo0 ON grupo0.Fog_Cod=formulario_codi.Fog_Cod
                    INNER JOIN formulario_grupo AS grupo1 ON grupo1.Fog_Cod=grupo0.Fog_Rec
                    INNER JOIN formulario_grupo AS grupo2 ON grupo2.Fog_Cod=grupo1.Fog_Rec
                    WHERE grupo2.Fog_Cod='$Par_Sql[0]' AND formulario_codi.Pec_Cod='$Par_Sql[1]'
                    ORDER BY grupo1.Fog_Ord,grupo0.Fog_Ord,Foc_Num";
            //echo $sql;
            break;
    } return $sql;
}
function getCodigos($Pld_Cod,$Pec_Cod,$obj){
    return $obj->getArrayConsulta('formulario_det_plan.selectWhere',array('clean'=>true,'Pld_Cod'=>$Pld_Cod, 'Foc_Est'=>'A' ,'Pec_Cod'=>$Pec_Cod,'join'=>array('formulario_codi'=>array('on'=>'formulario_codi.Foc_Cod=formulario_det_plan.Foc_Cod','cols'=>array('Fog_Cod','Foc_Num','Foc_Nom','Foc_Sig','Pec_Cod')))));
}
function getMayor($Pld_Cod,$Pec_Cod,$Year,$obj){
    return $obj->getRowConsulta('comprobantes.getMayor',array('Pld_Cod'=>$Pld_Cod,'Pec_Cod'=>$Pec_Cod,'Year'=>$Year));
}
function getCuentas($Foc_Cod,$Pec_Cod,$obj){
    return $obj->getArrayConsulta('formulario_det_plan.selectWhere',array('clean'=>true,'formulario_det_plan.Foc_Cod'=>$Foc_Cod, 'Foc_Est'=>'A' ,'Pec_Cod'=>$Pec_Cod,'join'=>array('formulario_codi'=>array('on'=>'formulario_codi.Foc_Cod=formulario_det_plan.Foc_Cod','cols'=>array('Fog_Cod','Foc_Num','Foc_Nom','Foc_Sig','Pec_Cod')))));
}
if(isset($getPlan)){
    $r=array('success'=>true);
    $r['rows']=$obBD_con1->getArrayConsulta('det_plan.selectWhere', array('Pec_Cod'=>$Pec_Cod,'det_plan.Pld_Tip'=>'D','setWhere'=>array("isActive",'setPerioCont','orderByCdc')));
    foreach($r['rows'] as &$c){
        if($c['Pld_Tip']=='D'){
            $mayor=getMayor($c['Pld_Cod'],$Pec_Cod,$Year,$obBD_con1);
            $c['Valor']=($mayor['Acreedor']!=null?$mayor['Acreedor']*1:($mayor['Deudor']!=null?$mayor['Deudor']*1:0));
            $c['Codigos']=getCodigos($c['Pld_Cod'],$Pec_Cod,$obBD_con1);
        }
    } unset($c);
    $r['grupos']=$obBD_con1->getArrayConsulta('formulario_grupo.selectWhere',array('clean'=>true,'Fog_Rec'=>$Fog_Cod, 'Fog_Est'=>'A','order'=>'Fog_Ord'));
    $r['subgrupos']=$obBD_con1->getArrayConsulta('formulario_grupo.selectWhere',array('clean'=>true,'grupo.Fog_Rec'=>$Fog_Cod, 'formulario_grupo.Fog_Est'=>'A','order'=>'formulario_grupo.Fog_Ord','join'=>array(array('table'=>array('grupo'=>'formulario_grupo'),'on'=>'grupo.Fog_Cod=formulario_grupo.Fog_Rec','cols'=>array()))));
    for($i=0,$z=count($r['rows']); $i<$z; $i++){
        //$obBD_con1->echoLog($rows[$i]['Valor']==0);
        if(isset($r['rows'][$i]['Valor'])&&($r['rows'][$i]['Valor']==0||empty($r['rows'][$i]['Valor'])) )
            unset($r['rows'][$i]);
    }
    $r['rows']=array_values($r['rows']);
    $obBD_con1->echoJson($r);
}
if(isset($saveCodigo)){
    $resp=array();

    $obBD_con_set = new MysqlDatos(new Class_Log_Conexion_Global($Ses_Dat_Dis));
    //$obBD_con_set->debug(true);
    $codigo=$obBD_con1->getRowConsulta('formulario_codi.selectWhere',array('Pec_Cod'=>$Pec_Cod,'Foc_Num'=>$Foc_Num));
    $obBD_con_set->inicio_transaccion();
    try{
        $resp['codigo']=array('Pec_Cod'=>$Pec_Cod,'Fog_Cod'=>$Fog_Cod,'Foc_Num'=>$Foc_Num,'Foc_Nom'=>$Foc_Nom,'Foc_Sig'=>$Foc_Sig);
        if(isset($codigo['Foc_Cod'])&&!empty($codigo['Foc_Cod'])){
            if(!empty($codigo['Foc_Nom'])&&empty($Foc_Nom)) $resp['codigo']['Foc_Nom']=$codigo['Foc_Nom'];
            $obBD_con_set->operacionobBD('formulario_codi.update', array_merge($resp['codigo'],array('where'=>array('Foc_Cod'=>$codigo['Foc_Cod']))));
            $resp['codigo']['Foc_Cod']=$codigo['Foc_Cod'];
        }else{
            $obBD_con_set->operacionobBD('formulario_codi.insert', $resp['codigo']);
            $resp['codigo']['Foc_Cod']=$obBD_con_set->insercionid();
        }
        $resp['codigo']['Pld_Cod']=$Pld_Cod;
        $obBD_con_set->operacionobBD('formulario_det_plan.insert', array('Pld_Cod'=>$Pld_Cod,'Foc_Cod'=>$resp['codigo']['Foc_Cod']));

        $obBD_con_set->fin_transaccion_nomsn(null,$resp);
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn(null,$e->getMessage(),$resp); }
    $obBD_con_set->echoJson($resp);
}
if(isset($removeCodigo)){
    $resp=array('Pld_Cod'=>$rowId);

    $obBD_con_set = new MysqlDatos(new Class_Log_Conexion_Global($Ses_Dat_Dis));
    //$obBD_con_set->debug(true);
    $obBD_con_set->inicio_transaccion();
    try{
        $obBD_con_set->operacionobBD('formulario_det_plan.deleteWhere', $resp);
        if(isset($Codigos)&&is_array($Codigos))
        foreach($Codigos as $v){
            $obBD_con_set->operacionobBD('formulario_det_plan.insert', array('Pld_Cod'=>$resp['Pld_Cod'],'Foc_Cod'=>$v['Foc_Cod']));
        }
        $obBD_con_set->fin_transaccion_nomsn(null,$resp);
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn(null,$e->getMessage(),$resp); }
    $obBD_con_set->echoJson($resp);
}
if(isset($getReporte1)){
    $r=array('success'=>true);
    $obBD_con1->setSentencias('sql_aux');
    $rows=$obBD_con1->getArrayConsulta(1, $Fog_Cod.'*'.$Pec_Cod);
    foreach ($rows as $i=>&$d){
        $d['Valor']=0;
        $cuentas=getCuentas($d['Foc_Cod'],$Pec_Cod,$obBD_con1);
                //$obBD_con1->getArrayConsulta(357, $Pla_Cod.'*'.$d['Foc_Cod'], $obBD_conexion);
        foreach ($cuentas as $c){
            //var_dump( $c);
            $mayor=getMayor($c['Pld_Cod'],$Pec_Cod,$Year,$obBD_con1);
                    //$obBD_con1->getRowConsulta(358,array('Pld_Cod'=>$c['Pld_Cod'],'Pec_Cod'=>$Pec_Cod,'Year'=>$Year));
            //var_dump( $mayor);
            $Cdc=explode(".", $mayor['Pld_Cdc']);
            //$obBD_con1->echoLog( $mayor['Pld_Cdc'].' '.$Cdc[0]);
            //if(trim($d['Foc_Sig'])=='+/-'){
                $debe=$mayor['Debe']!=null?$mayor['Debe']*1:0;
                $haber=$mayor['Haber']!=null?$mayor['Haber']*1:0;

                $d['Valor']+=(in_array($Cdc[0], array('1','5','6'))?$debe-$haber:$haber-$debe);
            //}else
               //$d['Valor']+=(in_array($Cdc[0], array('1','5','6'))?$debe-$haber:$haber-$debe);
        }
    } unset($d);
    $tot=count($rows);
    for($i=0; $i<$tot; $i++){
        //$obBD_con1->echoLog($rows[$i]['Valor']==0);
        if($rows[$i]['Valor']==0||empty($rows[$i]['Valor']))
            unset($rows[$i]);
    }

    $r['rows']=array_values($rows);
    $obBD_con1->echoJson($r);
}
if(isset($ajaxSubgrid1)){
    $rows=array();
    $cuentas=getCuentas($Foc_Cod,$Pec_Cod,$obBD_con1);
    foreach ($cuentas as $c){
        $mayor=getMayor($c['Pld_Cod'],$Pec_Cod,$Year,$obBD_con1);
        if($mayor!=null && !empty($mayor))
        array_push($rows, $mayor);
    }
    $r=array('success'=>true, 'page'=>1, 'rows'=>$rows, 'records'=>count($rows));
    $obBD_con1->echoJson($r);
}
if(isset($getReporte2)){
    $r=array('success'=>true);
    $obBD_con1->setSentencias('sql_aux');
    $rows=array();
    $codigos=$obBD_con1->getArrayConsulta(1, $Fog_Cod.'*'.$Pec_Cod);
    foreach ($codigos as $i=>$d){
        $cuentas=getCuentas($d['Foc_Cod'],$Pec_Cod,$obBD_con1);
        foreach ($cuentas as $c){
            $mayor=getMayor($c['Pld_Cod'],$Pec_Cod,$Year,$obBD_con1);
            $mayor['id']=$d['Foc_Cod'].'_'.$c['Pld_Cod'];
            if($mayor!=null && !empty($mayor) && (!empty($mayor['Deudor']) || !empty($mayor['Acreedor']))){
                $Cdc=explode(".", $mayor['Pld_Cdc']);
                $debe=$mayor['Debe']!=null?$mayor['Debe']*1:0;
                $haber=$mayor['Haber']!=null?$mayor['Haber']*1:0;
                $mayor['ValueCod']=(in_array($Cdc[0], array('1','5','6'))?$debe-$haber:$haber-$debe);
                array_push($rows, array_merge($d,array_merge($c,$mayor)));
            }
        }
    }
    $r['rows']=$rows;
    $obBD_con1->echoJson($r);
}
$row_rs_planes = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('setWhere'=>array('setEmpCod','order')));
$row_rs_formula = $obBD_con1->getArrayConsulta('formulario_grupo.selectWhere', array('clean'=>true,'Fog_Rec'=>NULL,'Fog_Est'=>'A'));
$obBD_Rep = new MysqlDatosContab($obBD_con1->getMyCon());
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Renta Mapeo [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style>.title-group-grid{ text-align: left; }</style>
</HEAD>
<BODY>
<div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Productor</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="row">
            <div class="col-sm-12">
                <div id='tabsMain' class="ui-tabs ui-tab-fix noPaddingH noBorder">
                    <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                        <li><a href="#tabs-1">Reporte 1</a></li>
                        <li><a href="#tabs-2">Reporte 2</a></li>
                        <li><a href="#tabs-3">Parametrizar</a></li>
                    </ul>
                    <div id ="tabs-1" class="ui-tabs-panel">
                        <fieldset class="exa-fieldset">
                           <legend class="Titulos2">Plan de Cuentas</legend> <!-- Form Name -->
                            <form id="formularioReport1" class="form-horizontal normal">
                                <div class="form-group">
                                  <label class="control-label label-sm col-xs-2">Seleccione Periodo:</label>
                                  <div class="col-xs-2">
                                    <select  name="Pec_Cod" onchange="" class="form-control input-sm getData ins">
                                        <option value="">Seleccione Periodo...</option>
                                        <?php foreach($row_rs_planes as $row){?>
                                        <option value="<?php echo $row['Pec_Cod']; ?>" data--pla_-cod="<?php echo $row['Pla_Cod']; ?>" data--year="<?php echo $row['Year']; ?>">Periodo <?php echo $row['Year']; ?></option>
                                        <?php } ?>
                                   </select>
                                  </div>
                                  <label class="control-label label-sm col-xs-2">Seleccione Fortmulario:</label>
                                  <div class="col-xs-2">
                                    <select name="Fog_Cod" onchange="" class="form-control input-sm getData ins">
                                        <option value="">Seleccione Formulario...</option>
                                        <?php foreach($row_rs_formula as $row){?>
                                        <option value="<?php echo $row['Fog_Cod']; ?>"><?php echo $row['Fog_Nom']; ?></option>
                                        <?php } ?>
                                   </select>
                                  </div>
                                  <div class="col-xs-2">
                                      <button type="button" onclick="updatePlanReport1();" class="btn btn-sm btn-success" ><i class="glyphicon glyphicon-search"></i> Buscar</button>
                                  </div>
                                </div>
                             </form>
                        </fieldset>
                        <div style="min-height: 300px; padding-bottom:8px; ">
                            <table id="reporte1"></table>
                            <div id="reporte1Pager"></div>
                        </div>
                    </div>
                    <div id ="tabs-2" class="ui-tabs-panel">
                        <fieldset class="exa-fieldset">
                           <legend class="Titulos2">Plan de Cuentas</legend> <!-- Form Name -->
                            <form id="formularioReport2" class="form-horizontal normal">
                                <div class="form-group">
                                  <label class="control-label label-sm col-xs-2">Seleccione Periodo:</label>
                                  <div class="col-xs-2">
                                   <select name="Pec_Cod" onchange="" class="form-control input-sm getData ins">
                                        <option value="">Seleccione Periodo...</option>
                                        <?php foreach($row_rs_planes as $row){?>
                                        <option value="<?php echo $row['Pec_Cod']; ?>" data--pla_-cod="<?php echo $row['Pla_Cod']; ?>" data--year="<?php echo $row['Year']; ?>">Periodo <?php echo $row['Year']; ?></option>
                                        <?php } ?>
                                   </select>
                                  </div>
                                  <label class="control-label label-sm col-xs-2">Seleccione Fortmulario:</label>
                                  <div class="col-xs-2">
                                    <select name="Fog_Cod" onchange="" class="form-control input-sm getData ins">
                                        <option value="">Seleccione Formulario...</option>
                                        <?php foreach($row_rs_formula as $row){?>
                                        <option value="<?php echo $row['Fog_Cod']; ?>"><?php echo $row['Fog_Nom']; ?></option>
                                        <?php } ?>
                                   </select>
                                  </div>
                                  <div class="col-xs-2">
                                      <button type="button" onclick="updatePlanReport2();" class="btn btn-sm btn-success" ><i class="glyphicon glyphicon-search"></i> Buscar</button>
                                  </div>
                                </div>
                             </form>
                        </fieldset>
                        <div style="min-height: 300px; padding-bottom:8px; ">
                            <table id="reporte2"></table>
                            <div id="reporte2Pager"></div>
                        </div>
                    </div>
                    <div id ="tabs-3" class="ui-tabs-panel">
                        <fieldset class="exa-fieldset">
                           <legend class="Titulos2">Plan de Cuentas</legend> <!-- Form Name -->
                            <form  id="formularioParam" class="form-horizontal normal">
                                <div class="form-group">
                                  <label class="control-label label-sm col-xs-2">Seleccione Periodo:</label>
                                  <div class="col-xs-2">
                                    <select id="Pec_Cod" name="Pec_Cod" onchange="" class="form-control input-sm getData ins">
                                        <option value="">Seleccione Periodo...</option>
                                        <?php foreach($row_rs_planes as $row){?>
                                        <option value="<?php echo $row['Pec_Cod']; ?>" data--pla_-cod="<?php echo $row['Pla_Cod']; ?>" data--year="<?php echo $row['Year']; ?>">Periodo <?php echo $row['Year']; ?></option>
                                        <?php } ?>
                                    </select>
                                  </div>
                                  <label class="control-label label-sm col-xs-2">Seleccione Formulario:</label>
                                  <div class="col-xs-2">
                                    <select name="Fog_Cod" onchange="" class="form-control input-sm getData ins">
                                        <option value="" data-estado="">Seleccione Formulario...</option>
                                        <?php foreach($row_rs_formula as $row){?>
                                        <option value="<?php echo $row['Fog_Cod']; ?>" data-estado="<?php echo $row['Fog_Est']; ?>"><?php echo $row['Fog_Nom']; ?></option>
                                        <?php } ?>
                                    </select>
                                  </div>
                                  <div class="col-xs-2">
                                      <button type="button" onclick="updatePlanParametros();" class="btn btn-sm btn-success" ><i class="glyphicon glyphicon-search"></i> Buscar</button>
                                  </div>
                                </div>
                             </form>
                        </fieldset>
                        <div style="min-height: 300px; padding-bottom:8px; ">
                            <table id="parametros"></table>
                            <div id="parametrosPager"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
var parametros=$("#parametros");
var reporte1=$("#reporte1");
var reporte2=$("#reporte2");
</script>
<script type="text/javascript">
$(function() {
    $('#tabsMain').find('>.ui-tabs-panel').initDivs({
        'tabs-1':function(){
            reporte1.createGrid({
                height: 250,caption:'&nbsp;Codigos',sortname:'Foc_Num', multiselect: false,
                colModel: [
                    { label: 'Cód.Int.', name: 'Foc_Cod', key: true, width: 20,align:"center", hidden:false },
                    { label: 'Grupo', name: 'GrupoOrd', width: 50, sortable:false, hidden:true },
                    { label: 'Grupo', name: 'Grupo', width: 50, sortable:false, hidden:true },
                    //{ label: 'SubGrupo', name: 'SubGrupo', width: 50, sortable:false },
                    { label: 'Codigo', name: 'Foc_Num', width: 30, align:"center", stype:'int' },
                    { label: 'Descripcion', name: 'Foc_Nom', width: 150 },
                    { label: 'Signo', name: 'Foc_Sig', width: 30, align:"center" },
                    { label: 'Valor', name: 'Valor', width: 75, align:"right", formatter:'number'/*, summaryTpl: "TOTAL: {0}", summaryType:'suma', summaryRoundType: 'fixed', summaryRound: 2*/ }
                ],
                groupingView : {
                   groupField : ['GrupoOrd'/*,'SubGrupo'*/], groupColumnShow:[false,false],//groupSummary: [true,true],//showSummaryOnHide: [true,true],
                   groupText:["<div class='title-group-grid'>{0}</div>","<div class='title-group-grid'>{0}</div>"]
                }, grouping:true,
                subGridRowExpanded: function(subgrid_id, row_id) {
                    var subgrid_table_id = subgrid_id+"_t", data=reporte1.getGridParam('extraData');
                    $("#"+subgrid_id).addClass('condensed jqSecond').html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                    $("#"+subgrid_table_id).createGrid({
                        postData:$.extend({ajaxSubgrid1:true,Foc_Cod:row_id},data),
                        colModel: [
                            {label:'Cod.Int.',name:"Pld_Cod",width:80,key:true,align:"center",hidden:true},
                            {label:'Cod.Int.',name:"Pla_Cod",width:80,align:"center",hidden:true},
                            {label:'Codigo',name:"Pld_Cdc",width:50,align:"center"},
                            {label:'Cuenta ',name:"Pld_Des",width:100},
                            {label:'Debe', name:"Debe",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'},
                            {label:'Haber', name:"Haber",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'},
                            {label:'Acreedor', name:"Acreedor",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'},
                            {label:'Deudor', name:"Deudor",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'}
                        ], beforeSelectRow: function(rowid, e) {return false;}, rowNum:10000000, pager: "",height: '100%'
                    },false);
                }, subGrid: true
            },true,'#reporte1Pager').gridButtonsAdd([
                {buttonicon:'print',caption:'Imprimir',onClickButton:function(){ printR(reporte1,[0,1,4]); }},
                {buttonicon:'download-alt',caption:'Descargar',onClickButton:function(){ exportR(reporte1,[0,1,4],3); }}
            ]);
        },
        'tabs-2':function(){
            reporte2.createGrid({
                height: 250,caption:'&nbsp;Codigos',sortname:'GrupoOrd',
                colModel: [
                    { label: 'Cód.Int.', name: 'id', key: true, width: 20,align:"center", hidden:true },
                    { label: 'Grupo', name: 'GrupoOrd', width: 50, sortable:false, hidden:true },
                    { label: 'SubGrupo', name: 'SubGrupo', width: 50, sortable:false },
                    { label: 'Codigo', name: 'Foc_Num', width: 30, align:"center", stype:'int', hidden:true },
                    { label: 'Descripcion', name: 'Foc_Nom', width: 150, summaryType:$.fieldHeader, hidden:true },

                    {label:'Codigo',name:"Pld_Cdc",width:50,align:"center"},
                    {label:'Cuenta ',name:"Pld_Des",width:100},
                    /*{label:'Debe', name:"Debe",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'},
                    {label:'Haber', name:"Haber",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'},*/
                    {label:'Deudor', name:"Acreedor",width:45, align: 'right', formatter:'currency', formatoptions:{defaultValue:''}, decimalPlaces: '2'},
                    {label:'Acreedor', name:"Deudor",width:45, align: 'right', formatter:'currency', formatoptions:{defaultValue:''}, decimalPlaces: '2'},
                    {label:'Valor', name:"ValueCod",width:45, align: 'right', formatter:'currency', decimalPlaces: '2', summaryTpl:"TOTAL: {0}", summaryType:'sum', summaryRound:2, summaryRoundType:'fixed'}
                ],
                groupingView : {
                   groupField : ['GrupoOrd','Foc_Num'],groupColumnShow:[false,false],groupSummary: [false,true],showSummaryOnHide: [false,true],
                   groupText:["<div class='title-group-grid'>{0}</div>","<div class='title-group-grid'>{0} - {Foc_Nom}<div class='pull-right'></div></div></div>"]
                }, grouping:true
            },true,'#reporte2Pager').gridButtonsAdd([
                {buttonicon:'print',caption:'Imprimir',onClickButton:function(){ printR(reporte2,[]); }},
                {buttonicon:'download-alt',caption:'Descargar',onClickButton:function(){ exportR(reporte2,[],6); }}
            ]);
        },
        'tabs-3':function(){
            parametros.createGrid({
                height: 250,caption:'&nbsp;Plan de Cuentas', stateCol:'Pld_Tip', stateConfig:{G:'cellGreen2'},
                colModel: [
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 25, align:"center", hidden:false },
                    { label: 'Codigo', name: 'Pld_Cdc', width: 25, align:"right" },
                    { label: 'Cuenta', name: 'Pld_Des', width: 100  },
                    { label: 'Tipo', name: 'Pld_Tip', width: 25, hidden:true  },
                    { label: 'Tipo', name: 'Pld_Tipo', width: 25, align:'center'  },
                    { label: 'Valor', name: 'Valor', width: 50, formatter:'currency', align:'right' },
                    { label: 'Codigo', name: 'Codigos', width: 150, formatter:'tags', formatoptions:{action:'dialogCodigos',type:'brown',remove:'removeCodigo',icon:'plus',/*button:function(o){ return $.getGridButton('addCod',o.Pld_Cod,'Add','plus'); },*//*btnType:'success',*/conditional:function(o){return o.Pld_Tip==='D';},data:function(v){ return "<b>"+v.Foc_Num+"</b>"+($.vv(v.Foc_Nom)&&v.Foc_Nom.trim()!==''?" - "+(v.Foc_Nom.length>=20?v.Foc_Nom.substr(0,20):v.Foc_Nom):''); } }  }
                ]
            },true,'#parametrosPager').gridButtonsAdd([
                {buttonicon:'print',caption:'Imprimir',onClickButton:function(){ printR(parametros,[0,1/*,5*/]); }},
                {buttonicon:'download-alt',caption:'Descargar',onClickButton:function(){ exportR(parametros,[0,1/*,5*/],4); }}
            ]);
    }}).end().createTabs();
    $('#codiDialog').createDialog({icon:'plus',height:175,width:400});
});
</script>
<script type="text/javascript">
/*function suma(v, field, rc){
    v=$.numUnformat(v||0);
    var res=0;
    if(rc['Foc_Sig'].trim()==='-')
        res= parseFloat(v||0) - parseFloat((rc[field]||0));
    else
        res= parseFloat(v||0) + parseFloat((rc[field]||0));
    return $.numFormat(res);
}*/
function updatePlanReport1(){
    reporte1[0].p['extraData']=$('#formularioReport1').getData();
    var data=reporte1[0].p['extraData'];
    if($.isEmpty(data['Pec_Cod']) || $.isEmpty(data['Fog_Cod'])){
        reporte1.clearGrid();
        return $.alert("Seleccione <i>Periodo Contable</i> y <i>Formulario</i> a parametrizar!");
    }
    $.getDataJson('', $.extend(true,{getReporte1:true},data) , function(r) {
        reporte1.setRows(r['rows']);
        reporte1.setCaption(data['Pec_Cod_Txt']+' - '+data['Fog_Cod_Txt']);
    });
}
function updatePlanReport2(){
    var data=$('#formularioReport2').getData('getReporte2');
    if($.isEmpty(data['Pec_Cod']) || $.isEmpty(data['Fog_Cod'])){
        reporte2.clearGrid();
        return $.alert("Seleccione <i>Periodo Contable</i> y <i>Formulario</i> a parametrizar!");
    }
    $.getDataJson('', data, function(r) {
        reporte2.setRows(r['rows']);
        reporte2.setCaption(data['Pec_Cod_Txt']+' - '+data['Fog_Cod_Txt']);
    });
}
</script>
<script type="text/javascript">
function removeCodigo(data){
$.arraySplicePos(data.Codigos,data.pos);
data['removeCodigo']=true;
//console.log(data);
$.createDialogConfirm('¿Est&aacute; seguro que desea remover el codigo?',data,function(d){
    $.saveDataJson('',d,function (r){
        parametros.changeRowData(r.Pld_Cod,{Codigos:data.Codigos});
        return false;
    });
});
}
function dialogCodigos(data,id){
    if(data.length>1) return $.alert('Maximo dos Codigos por Cuenta!');
    $('#codiForm').setData({Pld_Cod:id,Foc_Num:'',Foc_Nom:''},false);
    $('#codiDialog').dialog('open');
}
function updatePlanParametros(){
    var data=$('#formularioParam').getData('getPlan');
    //console.log(data);
    if($.isEmpty(data['Pla_Cod']) || $.isEmpty(data['Fog_Cod'])){
        parametros.clearGrid();
        return $.alert("Seleccione <i>Plan de Cuentas</i> y <i>Formulario</i> a parametrizar!");
    }
    $.getDataJson('', data , function(r) {
        parametros.setCaption("Plan de Cuentas "+data['Pec_Cod_Txt']+' - '+data['Fog_Cod_Txt']);
        parametros.setRows(r['rows']);
        $('#subgrupos').fillSelect(r.subgrupos,'Fog_Nom','Fog_Cod',false,['Fog_Rec']);
        $('#grupos').fillSelect(r.grupos,'Fog_Nom','Fog_Cod').val('').trigger('change');
        $('#codiForm').setData(data,false);
    });
}
function saveCodigo(){
    var add=true,data=$('#codiForm').getData('saveCodigo');
    data['codigos']=parametros.getCell(data.Pld_Cod,'Codigos');
    if(data['codigos'].length===1){ add=data['codigos'][0].Foc_Num!==data.Foc_Num; }
    if(!add){ $('#codiDialog').dialog('close'); return $.alert('El Codigo ya esta parametrizado a esta cuenta!'); }
    //console.log(data);
    //$.createDialogConfirm('�Est&aacute; seguro que desea guardar el codigo?',data,function(d){
        $.saveDataJson('',data,function (r){
            var cell=parametros.getCell(r['codigo'].Pld_Cod,'Codigos');
            cell.push(r['codigo']);
            parametros.changeRowData(r['codigo'].Pld_Cod,{Codigos:cell});
            $('#codiDialog').dialog('close');
            return false;
        });
    //});
}
function changeGroup(Fog_Cod){
    var sub=$('#subgrupos');
    sub.find('option[data--fog_-rec]').hide();
    if(Fog_Cod==='') return sub.val('');
    var items=sub.find('option[data--fog_-rec='+Fog_Cod+']');
    items.show();
    sub.val(items.length===1?items.attr('value'):'');
}
</script>
<div id="codiDialog" title="Buscar Codigos Formulario" style="display:none">
    <form id="codiForm" action="javascript:saveCodigo();" class="form-horizontal normal">
        <input type="text" name="Foc_Cod" class="hidden" />
        <input type="text" name="Pec_Cod" class="hidden" />
        <input type="text" name="Pld_Cod" class="hidden" />
        <div class="form-group">
            <label class="control-label label-xs col-xs-3 required">Grupo:</label>
            <div class="col-xs-9"><select id="grupos" onchange="changeGroup($(this).val());" class="form-control input-xs" required=""></select></div>
        </div>
        <div class="form-group">
            <label class="control-label label-xs col-xs-3 required">SubGrupo:</label>
            <div class="col-xs-9"><select id="subgrupos" name="Fog_Cod" onchange="" class="form-control input-xs readOnly" required="" disabled=""></select></div>
        </div>
        <div class="form-group">
            <label class="control-label label-xs col-xs-3 required">Signo:</label>
            <div class="col-xs-3">
                <select name="Foc_Sig" class="form-control input-xs readOnly" required="" disabled="">
                    <option value="">Seleccione..</option><option value="+" selected=""> + </option><option value="-"> - </option><option value="+/-"> +/- </option><option value="="> = </option>
                </select>
            </div>
            <label class="control-label label-xs col-xs-2 required">Codigo:</label>
            <div class="col-xs-4">
                <input type="text" name="Foc_Num" class="form-control input-xs numeric" required="" pattern="[0-9]+" autofocus=""/>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label label-xs col-xs-3">Descripc.:</label>
            <div class="col-xs-9">
                <input type="text" name="Foc_Nom" class="form-control input-xs" />
            </div>
        </div>
        <div class="form-group center"><div class="separator"></div>
            <button class="btn btn-xs btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
        </div>
    </form>
</div>
<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
<script>
function printR(gridComp,removes) {
    $('#tablaReporte').html(gridComp.jqGrid('exportGridInnerHTML',{generated:false,caption:false,footer:true, bodyBorder:false,print:true,removeHiddens:true,removeCols:removes}));
    $('#titleReporte').html(gridComp.getCaption());
    $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",/*printMode:'popup',*/overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});
}
function exportR(gridComp,removes,colspan) {
    $('#formatoExportar').find('tbody tr td, thead tr th').attr('colspan',colspan);
    var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
    temp.append(gridComp.jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true,print:true,removeHiddens:true,removeCols:removes}));
    $.downloadFile($.exportarExcelBlob(temp.html(),'Digitacion'),'digitacion_'+$.getDate()+'.xls');
}
</script>
<div id="formatoReporte" style="display: none;">
    <div style="width: 1030px;">
      <?php echo $obBD_Rep->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span id="titleReporte"></span>',$obBD_Rep->getMyCon()); ?>
      <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
      <?php echo $obBD_Rep->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
    </div>
</div>
<div id="formatoExportar" style="width: 1030px;display: none;">
    <?php echo $obBD_Rep->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span class="title_grid"></span>',$obBD_Rep->getMyCon(),false,6); ?>
</div>
</BODY>
</HTML>