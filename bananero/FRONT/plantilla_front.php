<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
//require_once('../LOGICA/ban_log_productor.php');
//require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new MysqlDatos(true);

if(isset($gridAjax)){
    $resp=$obBD_con1->getPageGridJson('tabla.selectWhere', array_merge($_GET,array('setWhere'=>array())) );
}
if(isset($saveForm)){
    $resp=array();
    $oBdSet = new MysqlDatos($obBD_con1->getMyCon());
    //$oBdSet->debug(true);
    $oBdSet->beginTrans();
    try{

        //$oBdSet->truncateTrans(); //si se guardo bien detengo el commit
        $oBdSet->endTrans($resp);
    } catch(Exception $e){ $oBdSet->revertTrans($e->getMessage(),$resp); }
    $oBdSet->echoJson($resp);
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style></style>
</HEAD>
<BODY>
<div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Productor</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="row">
            <div class="col-sm-12">

            </div>
        </div>
    </div>
</div>


<script type="text/javascript">

</script>
</BODY>
</HTML>