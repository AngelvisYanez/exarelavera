<?php
/**
 * @abstract Permite realizar la activaci�n/baja de usuarios
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de creaci�n  2018-08-22
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_usuario_3.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Usuarios;
$obBD_con1->setConnection(new Class_Log_Conexion_Global($Ses_Dat_Dis));
//Listo usuarios
if(isset($usrAjax)){
   $data = array_merge($_GET, array('setWhere'=>array('distinct','setEmpCod','setSucCod','notIsInterno','setPerfiles'/*,'isNotCliente','notIsAdmin'*/)));
   $responce = $obBD_con1->getPageGrid('usuarios.selectWhere', $data);
   foreach($responce['rows'] as $k=>&$v){
       $v['Perfiles']=$obBD_con1->getArrayConsulta("usuarperfi.selectWhere", array('clean'=>true,'Usu_Cod'=>$v['Usu_Cod'], 'Per_Est'=>'A', 'join'=>array('perfiles'=>array('on'=>'perfiles.Per_Cod=usuarperfi.Per_Cod','cols'=>'Per_Des'))));
   } unset($v);
   $obBD_con1->echoJson($responce);
}
// Activo/Desactivo Usuario
if(isset($updateUsuario)){    
    $obBD_con1->inicioTransaccion();
    try{
        $obBD_con1->operacionobBD('usuarios.update',array('Usu_Est'=>$Usu_Est,'where'=>array('Usu_Cod'=>$Usu_Cod)));
    }catch(Exception $e){ $obBD_con1->rollBackNomsn($e->getMessage(),$resp);  }
    $obBD_con1->finTransaccionNoMsn($resp);   
    $obBD_con1->echoJson($resp);
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Usuario Control [EXA]"; ?></TITLE>
    <meta charset= "UTF-8">
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <style>
    </style>
</HEAD>
<BODY>
  <div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Control de Usuarios</h3></div> 
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div id="allUsuarios" class = "row">  
            <div class="col-sm-12">
                   <form name="searchUsr" id="searchUsr" method="get" class="form-horizontal normal" action="javascript:$('#container').Search('#searchUsr','usrAjax');">
                         <fieldset class="exa-fieldset">
                            <legend class="Titulos2">B&uacute;squeda</legend>
                            <div class="form-group">                                        
                               <label class="col-xs-1 control-label label-xs">Filtrar Por:</label>
                               <div class="col-xs-5 radioset opt_search">
                                     <input id="radsc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Usuario&nbsp;&nbsp;&nbsp;</label>
                                     <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;&nbsp;</label>
                               </div>                                             
                            </div>   
                            <div class="form-group"> 
                               <label class="col-xs-1 control-label">B&uacute;squeda:</label>
                               <div class="col-xs-5">
                                     <div class="input-group">
                                        <input  id="search" name="search" onkeydown="if(event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus  class="form-control input-xs clearable submit"/>
                                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Usuario"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                     </div><!-- /input-group -->
                               </div><input type="text" tabindex="-1" style="display:none;" />
                            </div>
                         </fieldset>
                   </form>
            </div>    
            <div class="col-sm-12">        
                <div id="tablasContainer" class=""  style="min-height: 350px;">
                  <table id="container"></table>
                  <div id="containerPager"></div> 
                </div>                           
            </div>
        </div>
    </div>
  </div>
  <script src="../VALIDACIONES/adm_val_con_usuario.js?x=23"></script>
  <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
  <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
  <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
  <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script> 
</BODY>
</HTML> 