<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
//require_once('../LOGICA/ban_log_productor.php');
//require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new MysqlDatos(true);
$hoy = date("Y-m-d");

if(isset($getPlanes)){  
    $obBD_con1->closeConnection();
    $obBD_con1->setConnection(new MyGlobalConexion);
    $base = $obBD_con1->getRow('data.selectWhere', array('Emp_Cod'=>$Emp_Cod));
    $obBD_con1->closeConnection();    
    $obBD_con1->setConnection(new MyGlobalConexion($base['Dat_Dis']));
    $responce=array('success'=>true,'Planes'=>$obBD_con1->getArray('plan_cuenta.selectWhere', array('Pla_Est'=>'A', 'Emp_Cod'=>$Emp_Cod)));
    $obBD_con1->echoJson($responce); 
}
if(isset($treeAjax)){   
    function getListadoCuentas($Pla_Cod,$id,$obBD_con1){
        $cols=array('Pld_Cod','Pld_Cdc','Pld_Des','Pld_Tip','id'=>$obBD_con1->expr("CONCAT('C_',Pld_Cod)"),'type'=>$id==0?$obBD_con1->expr("'R'"):("Pld_Tip"));
        $cuentas = $obBD_con1->getArrayConsulta('det_plan.selectWhere',array('clean'=>true, 'unsetCols'=>true, 'addCols'=>array('det_plan'=>$cols), 'where'=>array('Pla_Cod'=>$Pla_Cod,'Pld_Rec'=>$id,'Pld_Est'=>'A'),'setWhere'=>array('orderByCdc') ));       
        if(!empty($cuentas)){ foreach ($cuentas as &$row){ 
            $row['children']=getListadoCuentas($Pla_Cod,$row['Pld_Cod'],$obBD_con1);
            //$row['parametros']=array();
        } unset($row); }
        return !empty($cuentas)?$cuentas:array();
    }
    $obBD_con1->closeConnection();
    $obBD_con1->setConnection(new MyGlobalConexion);
    $base = $obBD_con1->getRow('data.selectWhere', array('Emp_Cod'=>$Emp_Cod));
    $obBD_con1->closeConnection();   
    $obBD_con1->setConnection(new MyGlobalConexion($base['Dat_Dis']));
    $responce=array('success'=>true,'Cuentas'=>getListadoCuentas($Pla_Cod,0,$obBD_con1));
    $obBD_con1->echoJson($responce); 
}
if(isset($savePlan)){
    function saveCta($oBdSet,&$ids,&$params,&$data){          
        $data['data']['Pld_Rec']=$data['parent']==='#'?0:$ids[$data['parent']];
        $p=isset($data['data']['parametros'])?$data['data']['parametros']:null;        
        unset($data['data']['parametros']);        
        $data['data']['Pld_Cod']=$oBdSet->operation('det_plan.insert', $data['data'])->lastId();
        $ids[$data['id']]=$data['data']['Pld_Cod'];
        if($data['data']['Pld_Tip']=='D'&&!is_null($p))              
            foreach($p as $k=> $v){
                $params[$k]=isset($params[$k])?$params[$k]:array();
                array_push($params[$k],$data['data']['Pld_Cod']);
            }         
    }
    function saveParametros($oBdSet,$k,$ids){
        //$oBdSet->echoLog("Parametro $k => ");
        //$oBdSet->echoLog($ids);
        switch($k){
            case 'Clientes':
                //$oBdSet->operation('plan_params.insert', array('Pld_Cod'=>$Pld_Cod));
                break;
            case 'Proveedores':
                //$oBdSet->operation('plan_params.insert', array('Pld_Cod'=>$Pld_Cod));
                break;            
        }
    }   
    $Plan=$obBD_con1->getArray('plan_cuenta.selectWhere', array('clean'=>true, 'Emp_Cod'=>$Ses_Emp_Cod, 'Pla_Est'=>'A'));
    if(count($Plan)==1){
        $Conteo=$obBD_con1->getRow('det_plan.selectWhere', array('clean'=>true, 'Pla_Cod'=>$Plan[0]['Pla_Cod'], 'Pld_Est'=>'A', 'unsetCols'=>true, 'addCols'=>array(''=>array('COUNT(*) AS Total'))));
        if(isset($Conteo['Total'])&&$Conteo['Total']*1==0){
            $Pla_Cod=$Plan[0]['Pla_Cod'];
        }
    }
    $resp=array(); 
    $oBdSet = new MysqlDatos($obBD_con1->getMyCon());
    //$oBdSet->debug(true);
    //$oBdSet->debugLogs(false);
    $oBdSet->beginTrans();
    try{
        $ids=array(); $params=array();
        if(!isset($Pla_Cod))
            $Pla_Cod=$oBdSet->operation('plan_cuenta.insert', array('Emp_Cod'=>$Ses_Emp_Cod, 'Pla_Fec'=>$hoy, 'Pla_Obs'=>$savePlan))->lastId();
        else
            $oBdSet->operation('plan_cuenta.update', array('Pla_Fec'=>$hoy, 'Pla_Obs'=>$savePlan, 'where'=>array('Pla_Cod'=>$Pla_Cod)));
        //$c=json_decode(stripslashes($c),true);  
        $c=json_decode($c,true);      
        foreach($c as &$v){
            $v['data']['Pla_Cod']=$Pla_Cod;
            saveCta($oBdSet,$ids,$params,$v);            
        } unset($v); 
        foreach($params as $k=>$v){
            saveParametros($oBdSet,$k,$v);
        }        
        //if($oBdSet->getError()==0) throw new Exception("Probando: Todo se guardo bien!");
        $oBdSet->endTrans($resp);
    }catch(Exception $e){ $oBdSet->revertTrans($e->getMessage(),$resp); }
    $oBdSet->echoJson($resp);
}
$obBD_Aux = new MysqlDatos(new MyGlobalConexion);
$empresas=$obBD_Aux->getArray('empresas.selectWhere', array('Emp_Est'=>'A')); 
$obBD_Aux->closeConnection();
$emp_options="<option value=''></option>";
foreach($empresas as $r) $emp_options.="<option value='".$r['Emp_Cod']."' data--emp_-nom='".($r['Emp_Nom'])."'>".($r['Emp_Cor'])."</option>";
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Plan Cuenta Importar [EXA]"; ?></TITLE>
    <meta charset= "UTF-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
    <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script> 
    <script type="text/javascript" src="../VALIDACIONES/con_val_planc_copy.js"></script>
    <style>.panel-body{ padding: 5px 5px 0px 5px; }</style>
</HEAD>
<BODY>
<div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Copiar Plan de Cuentas</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="row">
            <div class="col-sm-6">                 
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Cargar Cuentas Desde Excel</legend>
                    <form id="formExcel" enctype="multipart/form-data" class="form-horizontal normal">
                    <div class="form-group">
                        <label class="control-label col-xs-2 label-sm">Excel:</label>
                        <div class="col-xs-10">
                            <input id="upload" type=file  name="files[]"  class="form-control input-sm" />
                        </div>
                    </div>
                    </form>
                </fieldset>
                <?php if($_SESSION['Ses_Prs_Cod']*1==1){ ?>
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Copiar Plan de Cuentas Existente</legend>
                    <form id="formCuentas" action="javascript:loadCuentas();" class="form-horizontal normal">
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-sm required ">Empresa:</label>
                        <div class="col-xs-9">     
                            <select name="Emp_Cod" id="Emp_Cod" class="form-control input-sm" onchange="getPlanes(this.value);" data-placeholder="Seleccione Empresa..."><?php echo $emp_options; ?></select>
                        </div>
                    </div> 
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-sm required ">Plan Cuenta:</label>
                        <div class="col-xs-9">     
                            <select name="Pla_Cod" id="Pla_Cod" class="form-control input-sm" ><option value="" selected="">Selecione ...</option></select>
                        </div>
                    </div>    
                    <div class="form-group center">
                        <div class="separator"></div>
                        <button type="submit" class="btn btn-sm btn-primary" ><i class="glyphicon glyphicon-download"></i> Traer Plan</button>
                    </div>    
                    </form>
                </fieldset>
                <?php } ?>
            </div>
            <div class="col-sm-6"> 
                <div class="panel panel-success exa-panel">
                    <div class="panel-heading">
                        <i class="fa fa-list-ol"></i>&nbsp;&nbsp;<span id="plan-tittle">Listado Cuentas</span>
                        <span class="pull-right">
                            <div class="input-group input-group-xs" style="width: 150px;display:inline-flex;margin-right: 44px;"><input type="text" class="form-control clearable onEnter" data-onenter="searchNode" placeholder="Buscar..."/><span class="input-group-btn"><button type="button" onclick="searchNode.call($(this).parent().prev(),$(this).parent().prev().val());" class="btn btn-info"><i class="glyphicon glyphicon-search"></i></button><button type="button" onclick="searchNode.call($(this).parent().prev(),'');" class="btn btn-warning"><i class="glyphicon glyphicon-remove"></i></button></span></div>                           
                        </span>
                    </div>
                    <div class="panel-body backWhiteSquare">
                        <div class="scrollable-tree" style="height: 350px"><div id="cuentas"></div></div>
                    </div> 
                    <div class="panel-footer txtRight">&nbsp;
                        <span id="plan-footer">&nbsp;</span>
                        <span id="guardarBtn" style="display:none">
                            <button type="button" onclick="savePlan();" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </span>
                    </div>   
               </div>
            </div>
            
        </div> 
    </div>
</div>
<script src="../../framework/plugins/xlsx/xlsx.core.min.js"></script>

</BODY>
</HTML>