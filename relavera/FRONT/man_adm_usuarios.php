<?php
/**
 * @abstract Permite realizar la activacion/baja de usuarios
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de creacion  2018-08-22
 */

// RECURSOS 
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_usuario.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Usuarios;
/* Creacion del Objeto de conexion */
$obBD_con1->setConnection(new Class_Log_Conexion_Global($Ses_Dat_Dis));

//Listo usuarios
if(isset($usrAjax)){
    $order = (isset($_GET['sidx']) && !empty($_GET['sidx'])) ? $_GET['sidx']." ".$_GET['sord'] : 'Usu_Est ASC, Usuario ASC';
    // $data = array_merge($_GET, array('order' => $order, 'setWhere'=>array('distinct','setEmpCod','setSucCod','notIsInterno','setPerfiles','notIsSpecialProfiles')));
    $setWhere = array('distinct','setEmpCod','setSucCod','notIsInterno','setPerfiles');
    if(isset($tabType) && $tabType === 'plantas'){
        $setWhere[] = 'setOnlyPlantas';
        $setWhere[] = 'joinPlanta';
        $setWhere[] = 'notHasAdminProfile';
        // $data['group'] = 'usuarios.Usu_Cod';
    } else {
        $setWhere[] = 'notIsSpecialProfiles';
        $setWhere[] = 'notHasPlantasProfile';
    }
    // $data = array_merge($_GET, array('order' => $order, 'setWhere'=>$setWhere));
    $data = array_merge($_GET, array('order' => $order, 'setWhere' => $setWhere));
    if(isset($tabType) && $tabType === 'plantas'){
        $data['group'] = 'usuarios.Usu_Cod';
    }
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
        <!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?php echo "Usuario Control [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <style>
            .nav-tabs {
                border-bottom: 1px solid #337ab7;
                margin-bottom: 15px;
            }
            .nav-tabs > li > a {
                background: #b8cde6;
                background: -moz-linear-gradient(top, #f0f5f9 0%, #b8cde6 100%);
                background: -webkit-linear-gradient(top, #f0f5f9 0%,#b8cde6 100%);
                background: linear-gradient(to bottom, #f0f5f9 0%,#b8cde6 100%);
                border: 1px solid #337ab7;
                border-radius: 8px 8px 0 0;
                color: #222;
                font-weight: bold;
                padding: 6px 20px;
                margin-right: 4px;
                transition: all 0.2s ease;
            }
            .nav-tabs > li > a:hover {
                background: #d9e5f5;
                color: #000;
            }
            .nav-tabs > li.active > a, 
            .nav-tabs > li.active > a:hover, 
            .nav-tabs > li.active > a:focus {
                background: #fff !important;
                border: 1px solid #337ab7 !important;
                border-bottom-color: transparent !important;
                color: #e67e22 !important; /* Color naranja del estilo */
                cursor: default;
            }
        </style>
    </HEAD>
        <BODY>
        <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Control de Usuarios</h3></div> 
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <ul class="nav nav-tabs" id="userTabs">
                    <li class="active"><a href="#administrativo" data-toggle="tab" data-type="admin">Administrativo</a></li>
                    <li><a href="#plantas" data-toggle="tab" data-type="plantas">Plantas</a></li>
                </ul>
                <div id="allUsuarios" class = "row">  
                    <div class="col-sm-12">
                        <form name="searchUsr" id="searchUsr" method="get" class="form-horizontal normal" action="javascript:$('#container').Search('#searchUsr','usrAjax');">
                            <input type="hidden" name="tabType" id="tabType" value="admin">
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
                                        </div>
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
        <!-- MINI DIALOG: Editar Cliente/Planta -->
        <div id="editPlantaDialog" style="display:none;">
            <input type="hidden" id="epUsu_Cod" />
            <input type="hidden" id="epCli_Cod" />
            <fieldset class="exa-fieldset" style="margin:8px 0 0;">
                <legend class="Titulos2">Datos Empresa/Cliente</legend>
                <div class="form-group">
                    <label class="col-sm-3 control-label label-sm">C.I/Ruc:</label>
                    <div class="col-sm-9">
                        <input id="epPrs_Ced" type="text" class="form-control input-sm" style="width:180px;" readonly placeholder="C.I/RUC del Cliente..." />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label label-sm">Cliente:</label>
                    <div class="col-sm-9">
                        <div class="input-group input-group-sm" style="width:310px;">
                            <input id="epCliNom" class="form-control input-sm" readonly placeholder="Nombre del Cliente..." />
                            <span class="input-group-btn">
                                <button type="button" id="epBtnBusCli" class="btn btn-success btn-sm" title="Buscar Cliente">
                                    <span class="glyphicon glyphicon-transfer"></span>
                                </button>
                            </span>
                            <span class="input-group-btn">
                                <button type="button" id="epBtnLimpiar" class="btn btn-danger btn-sm" title="Limpiar">
                                    <span class="glyphicon glyphicon-trash"></span>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label label-sm">C&oacute;digo de Planta:</label>
                    <div class="col-sm-9">
                        <select id="epPla_Cod" class="form-control input-sm" style="width:250px;">
                            <option value="">-- Seleccione --</option>
                        </select>
                    </div>
                </div>
            </fieldset>
            <div style="text-align:center; margin-top:14px;">
                <button type="button" id="epBtnGuardar" class="btn btn-sm btn-primary">
                    <i class="glyphicon glyphicon-floppy-disk"></i> Guardar
                </button>
            </div>
        </div>

        <!-- DIALOG: B&uacute;squeda de Clientes para Editar Planta -->
        <div id="epCliDialog" title="B&uacute;squeda de Clientes" style="display:none;">
            <form id="epCliSearchForm" onsubmit="return false;" style="margin-bottom:6px;">
                <div style="display:flex; align-items:center; gap:6px; padding:4px 0; flex-wrap:wrap;">
                    <div class="radioset" style="white-space:nowrap;">
                        <input type="radio" id="epOpN" name="epOp" value="d" checked/><label for="epOpN">&nbsp;Nombre&nbsp;</label>
                        <input type="radio" id="epOpC" name="epOp" value="c"/><label for="epOpC">&nbsp;C&eacute;dula/RUC&nbsp;</label>
                    </div>
                    <input type="text" id="epCliSearch" class="form-control input-sm" placeholder="Ingrese b&uacute;squeda..." style="flex:1; min-width:160px;" />
                    <button type="button" id="epCliBtnBuscar" class="btn btn-success btn-sm">
                        <span class="glyphicon glyphicon-search"></span> Buscar
                    </button>
                </div>
            </form>
            <div style="max-height:280px; overflow-y:auto;">
                <table class="table table-bordered table-hover table-condensed" style="margin:0; font-size:12px;">
                    <thead>
                        <tr>
                            <th style="width:130px;">C&eacute;dula/RUC</th>
                            <th>Cliente</th>
                            <th style="width:70px;"></th>
                        </tr>
                    </thead>
                    <tbody id="epCliResultBody">
                        <tr><td colspan="3" class="text-center text-muted">Realice una b&uacute;squeda...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script src="../VALIDACIONES/man_adm_usuario.js?x=5"></script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script> 
    </BODY>
</HTML> 