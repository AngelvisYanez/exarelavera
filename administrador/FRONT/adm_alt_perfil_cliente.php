<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_orgproc_cliente.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Admo;

$hoy = date("Y-m-d");

if(isset($perfilGroupAjax)){   
    $data = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod, $obBD_conexion);
    $obBD_con1->echoJson($data);
}

if(isset($perfilAjax)){   
    $data = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod, $obBD_conexion);
    $obBD_con1->echoJson($data);
}
if(isset($getPermisos)){
    $permisos=$obBD_con1->getArrayConsulta(12, $Per_Cod, $obBD_conexion);
    $obBD_con1->echoJson(array('success'=>true, 'permisos'=>$permisos));
}
if(isset($treeAjax)){

    // $data = $obBD_con1->getArrayConsulta(13, $Ses_Emp_Cod, $obBD_conexion);
    // $obBD_con1->echoJson($data);

    function getListadoProcesos($id,$obBD_con1,$obBD_conexion){
        $cols=array('Pcs_Cod','Pcs_Tip','Pcs_Lin','Pcs_Nom','Pcs_Det','Pcs_Ico','Pcs_Est','Pcs_Int','id'=>$obBD_con1->expr("CONCAT('P_',Pcs_Cod)"),'type'=>$obBD_con1->expr("'Pcs'"));

        $procesos = $obBD_con1->getArrayConsulta('procesos.selectWhere',array('clean'=>true, 'unsetCols'=>true, 'addCols'=>array('procesos'=>$cols), 'where'=>array('Org_Cod'=>$id, 'Pcs_Int'=>'N'), 'order'=>array('Pcs_Ord')), $obBD_conexion);

        if(!empty($procesos)){
            foreach ($procesos as &$row){
                if($row['Pcs_Est']!='A')$row['state']=array('disabled'=>true);
            } unset($row);
        }
        return !empty($procesos)?$procesos:array();
    }

    function getListadoMenu($id,$obBD_con1,$obBD_conexion){       
        $cols=array('Org_Cod','Org_Des','Org_Det','Org_Ico','Org_Mod','id'=>$obBD_con1->expr("CONCAT('G_',Org_Cod)"),'type'=>$obBD_con1->expr("'Org'"));

        $menus = $obBD_con1->getArrayConsulta('organizado.selectWhere',array('clean'=>true, 'unsetCols'=>true, 'addCols'=>array('organizado'=>$cols), 'where'=>array('Org_Niv'=>$id, 'Org_Mod'=>'A'), 'order'=>array('Org_Ord')), $obBD_conexion);
        
        
        if(!empty($menus)){
            foreach ($menus as &$row){
                $row['children']=array_merge(getListadoMenu($row['Org_Cod'],$obBD_con1,$obBD_conexion),getListadoProcesos($row['Org_Cod'],$obBD_con1,$obBD_conexion));
            }  unset($row);
        }
        return !empty($menus)?$menus:array();
    }

    $responce=getListadoMenu(0,$obBD_con1,$obBD_conexion);
    $obBD_con1->echoJson($responce); 
}

if(isset($saveData)||isset($anulaData)||isset($savePermisos)){
    $resp=array('success'=>false);
    
    $obBD_conexion_set = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_con_set = new Class_Log_Datos_Admo;

    $obBD_con_set->inicio_transaccion($obBD_conexion_set);
    try{
        if(isset($saveData)){
            $form=$_POST;
            unset($form['Per_Cod']); unset($form['saveData']);        
            if(!empty($Per_Cod))
                $obBD_con_set->operacionobBD('perfiles.update', array_merge($form,array('where'=>array('Per_Cod'=>$Per_Cod))), $obBD_conexion_set);
            else{
                $form['Emp_Cod']=$Ses_Emp_Cod;
                $obBD_con_set->operacionobBD('perfiles.insert', $form, $obBD_conexion_set);
            }
        }else if(isset($anulaData)){
            $obBD_con_set->operacionobBD('perfiles.update', array('Per_Est'=>'I','where'=>array('Per_Cod'=>$id)), $obBD_conexion_set); 
        }else if(isset($savePermisos)){
            $permisos = isset($_REQUEST['permisos']) ? $_REQUEST['permisos'] : (isset($permisos) ? $permisos : array());
            $perfiles = isset($_REQUEST['perfiles']) ? $_REQUEST['perfiles'] : (isset($perfiles) ? $perfiles : array());

            if($savePermisos=='Individuales'){
                $obBD_con_set->operacionobBD('perfiorgan.deleteWhere', array('Per_Cod'=>$Per_Cod), $obBD_conexion_set);
                if(isset($permisos)) foreach ($permisos as $p) {
                    $obBD_con_set->operacionobBD('perfiorgan.insert', array('Per_Cod'=>$Per_Cod, 'Pcs_Cod'=>$p), $obBD_conexion_set);
                }
            }else if($savePermisos=='Grupales'){
                foreach ($perfiles as $per) 
                foreach ($permisos as $p){
                    $obBD_con_set->operacionobBD('perfiorgan.deleteWhere', array('Per_Cod'=>$per['Per_Cod'], 'Pcs_Cod'=>$p), $obBD_conexion_set);
                    if($action=='add')
                        $obBD_con_set->operacionobBD('perfiorgan.insert', array('Per_Cod'=>$per['Per_Cod'], 'Pcs_Cod'=>$p), $obBD_conexion_set);
                }
            }
        }
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn($obBD_conexion_set); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    $resp['success']=$obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set);
    if(!$resp['success']) $resp['error']=$obBD_con_set->MsgError;
    $obBD_con_set->echoJson($resp);
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
    <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosenIcon/chosenIcon.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenIcon/chosenIcon.js"></script> 
    <script type="text/javascript" src="../../framework/jquery/bootstrap/jqboot.checkbox.buttons.js"></script>
    <link rel="stylesheet" href="../../skins/fonts/fontelo/fontello.css?x=0" />
    <script type="text/javascript">var urlTree='<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>?treeAjax=true';</script>
    <script type="text/javascript" src="../VALIDACIONES/adm_val_perfil_cliente.js"></script> 
    <style>.panel-body{padding: 5px 5px 0px 5px;} .jstree-icon:not(.glyphicon):not(.fa){font-family: "fontello";  font-style: normal;  font-weight: normal;} /*.hidden{display: inherit !important}*/</style>
</HEAD>
<BODY>
<div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registro De Perfiles y Permisos</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="row">
            <div class="col-sm-6">
                <div id='tabsMain' class="ui-tabs ui-tab-fix noPaddingH noBorder">
                    <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                        <li><a href="#tabs-1">Individuales</a></li>
                        <li><a href="#tabs-2">Grupales</a></li>
                    </ul>
                    <div id ="tabs-1" class="ui-tabs-panel">
                        <div>
                            <table id="perfilesGrid"></table><div id="perfilesPager"></div>
                        </div>
                    </div>
                    <div id ="tabs-2" class="ui-tabs-panel">
                        <div>
                            <table id="perfilesGroupGrid"></table><div id="perfilesGroupPager"></div>
                        </div>
                    </div>
                    
                </div>
            </div>
            <div class="col-sm-6"> 
                <div class="panel panel-success exa-panel">
                    <div class="panel-heading">
                        <i class="fa fa-list-ol"></i>&nbsp;&nbsp;<b>Permisos Perfil:</b> <span id="plan-tittle" class="blue bold"></span>
                        <span class="pull-right">
                            <form id='permisosForm' class="hidden"><input type="text" name='Per_Cod' /><input type="text" id='Per_Des' name='Per_Des' /></form>
                            <button onclick="javascript:validaPermisos();" type="button" class="btn btn-xs btn-success none individual"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button> 
                            <button onclick="javascript:validaPermisos('add');" type="button" class="btn btn-xs btn-success none group"><i class="glyphicon glyphicon-plus"></i> Agregar</button> 
                            <button onclick="javascript:validaPermisos('remove');" type="button" class="btn btn-xs btn-danger none group"><i class="glyphicon glyphicon-remove"></i> Remover</button> <i class="glyphicon glyphicon-option-vertical grey"></i>
                            <button onclick="clean(); updateTree();" type="button" class="btn btn-xs btn-success" title="Recargar Lista"><i class="glyphicon glyphicon-refresh"></i></button>
                            <button onclick="clean(); " type="button" class="btn btn-xs btn-success" title="Limpiar Selección"><i class="glyphicon glyphicon-unchecked"></i></button>
                        </span>
                    </div>
                    <div class="panel-body noHighlight backWhiteSquare">
                        <div class="scrollable-tree" style="height: 350px"><div id="directorios"></div></div>
                    </div> 
                    <div class="panel-footer">&nbsp;Seleccione los procesos
                        <span id="plan-footer">&nbsp;</span>
                        <div class="pull-right">
                            <div class="input-group input-group-xs" style="width:150px;display:inline-flex;margin-right:44px;margin-top:-2px;"><input type="text" class="form-control clearable onEnter" data-onenter="searchNode" placeholder="Buscar..."/><span class="input-group-btn"><button type="button" onclick="searchNode.call($(this).parent().prev(),$(this).parent().prev().val());" class="btn btn-info"><i class="glyphicon glyphicon-search"></i></button><button type="button" onclick="searchNode.call($(this).parent().prev(),'');" class="btn btn-warning"><i class="glyphicon glyphicon-remove"></i></button></span></div>
                        </div>                            
                    </div>   
               </div>
            </div>            
        </div> 
    </div>
</div>
<div id="perfilDialog" title='Edición de Perfil'>
    <form id="perfilForm" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?',$('#perfilForm').getData('saveData'),saveForm);" class="form-horizontal normal">
        <input type="text" name="Per_Cod" class="hidden" />        
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Datos Perfil</legend>            
            <div class="form-group">
                <label class="col-sm-2 control-label label-sm required">Nombre:</label>  
                <div class="col-sm-10" >
                    <input name="Per_Des" type="text" class="form-control input-sm"  required />                                  
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label label-sm required">Estado:</label>  
                <div class="col-sm-8" >
                    <select name="Per_Est" class="form-control input-sm">  
                        <option value="A">Activo</option><option value="I">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="form-group center"><div class="separator"></div>
                <button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            </div>
    </form>
</div>    
</BODY>
</HTML>