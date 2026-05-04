<?php
session_start();
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
$obBD_con1->setConnection(new Class_Log_Conexion_Global($_SESSION['Ses_Dat_Dis']));

$isAdminProfile = false;
if (isset($_SESSION['Ses_Per_Des']) && is_array($_SESSION['Ses_Per_Des'])) {
    foreach ($_SESSION['Ses_Per_Des'] as $p) {
        if ($p === 'Administrador de Sistemas' || $p === 'Admin_Oper') {
            $isAdminProfile = true;
            break;
        }
    }
}

//Listo usuarios
if(isset($usrAjax)){
    $order = (isset($_GET['sidx']) && !empty($_GET['sidx'])) ? $_GET['sidx']." ".$_GET['sord'] : 'Usu_Est ASC, Usuario ASC';
    // $data = array_merge($_GET, array('order' => $order, 'setWhere'=>array('distinct','setEmpCod','setSucCod','notIsInterno','setPerfiles','notIsSpecialProfiles')));
    $setWhere = array('distinct','setEmpCod','setSucCod','notIsInterno','setPerfiles');
    if(isset($tabType) && $tabType === 'plantas'){
        $setWhere[] = 'setOnlyPlantas';
    } else {
        $setWhere[] = 'notIsSpecialProfiles';
        $setWhere[] = 'notHasPlantasProfile';
    }
    // $data = array_merge($_GET, array('order' => $order, 'setWhere'=>$setWhere));
    // Solo mostramos visibles si no se ha marcado "Ver Ocultos"
    if(!isset($ver_ocultos) || $ver_ocultos !== 'on'){
        $setWhere[] = 'setVisibles';
    }

    $data = array_merge($_GET, array('order' => $order, 'setWhere' => $setWhere));
    $responce = $obBD_con1->getPageGrid('usuarios.selectWhere', $data);
    foreach($responce['rows'] as $k=>&$v){
        $v['Perfiles']=$obBD_con1->getArrayConsulta("usuarperfi.selectWhere", array('clean'=>true,'Usu_Cod'=>$v['Usu_Cod'], 'Per_Est'=>'A', 'join'=>array('perfiles'=>array('on'=>'perfiles.Per_Cod=usuarperfi.Per_Cod','cols'=>'Per_Des'))));
        
        // Cargar Plantas si estamos en la pestaña de plantas
        if(isset($tabType) && $tabType === 'plantas'){
            $plantasArr = $obBD_con1->getArrayConsulta("manifiesto_usuario.selectWhere", array('clean'=>true, 'Usu_Cod'=>$v['Usu_Cod'], 'join'=>array('manifiesto_plantas'=>array('on'=>'manifiesto_plantas.Pla_Cod=manifiesto_usuario.Pla_Cod','cols'=>'Pla_Nom'))));
            $p_nombres = array();
            foreach($plantasArr as $pa) if(!empty($pa['Pla_Nom'])) $p_nombres[] = $pa['Pla_Nom'];
            $v['Planta'] = implode(", ", $p_nombres);
        }
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

// Cambiar contraseña
if(isset($changePassAjax)){
    $obBD_con1->inicioTransaccion();
    try{
        $obBD_con1->operacionobBD('usuarios.update', array('Usu_Pal'=>$Usu_Pal, 'where'=>array('Usu_Cod'=>$Usu_Cod)));
    }catch(Exception $e){ $obBD_con1->rollBackNomsn($e->getMessage(),$resp);  }
    $obBD_con1->finTransaccionNoMsn($resp);   
    $obBD_con1->echoJson($resp);
}

// Actualizar visibilidad de usuario
if(isset($updateUsuVis)){
    $obBD_con1->inicioTransaccion();
    try{
        $obBD_con1->operacionobBD('usuarios.update', array('Usu_Vis'=>$Usu_Vis, 'where'=>array('Usu_Cod'=>$Usu_Cod)));
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
        <script type="text/javascript">
            const IS_ADMIN_PROFILE = <?php echo $isAdminProfile ? 'true' : 'false'; ?>;
        </script>
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
            /* Ajustes para el modal de tabs */
            #editPlantaDialog .nav-tabs {
                margin-bottom: 10px;
                font-size: 12px;
            }
            #editPlantaDialog .nav-tabs > li > a {
                padding: 4px 12px;
            }
            #editPlantaDialog .tab-pane {
                padding-top: 5px;
            }
            #editPlantaDialog .tab-content {
                padding: 15px 45px 0;
            }
            #editPlantaDialog .control-label {
                padding-top: 0;
                text-align: right;
                font-weight: bold;
                font-size: 11px;
                color: #333;
                margin-bottom: 0;
            }
            #editPlantaDialog .form-group {
                margin-bottom: 12px;
                display: flex;
                align-items: center;
            }
            #editPlantaDialog .input-sm, 
            #editPlantaDialog .form-control {
                height: 26px;
                padding: 2px 10px;
                font-size: 11px;
                margin-bottom: 0;
            }
            #editPlantaDialog .btn-sm {
                padding: 4px 10px;
                height: 26px;
                line-height: 1.2;
            }
            #editPlantaDialog .exa-fieldset {
                padding-bottom: 15px;
            }
            #editPlantaDialog .required-star {
                color: #0C3;
                font-weight: bold;
                margin-right: 3px;
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
                                    </div>
                                    <div class="col-xs-3" style="padding-top: 4px;">
                                        <label style="cursor:pointer; font-weight: normal; color: #337ab7;">
                                            <input type="checkbox" name="ver_ocultos" id="ver_ocultos" onchange="$('#searchUsr').submit()"> 
                                            <strong>Ver Ocultos</strong>
                                        </label>
                                    </div>
                                    <input type="text" tabindex="-1" style="display:none;" />
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
        <!-- MINI DIALOG: Editar Cliente/Planta / Password -->
        <div id="editPlantaDialog" style="display:none;">
            <input type="hidden" id="epUsu_Cod" />
            <input type="hidden" id="epCli_Cod" />
            
            <div class="form-horizontal">
                <ul class="nav nav-tabs" id="modalEpTabs" style="margin-top: 5px;">
                    <li class="active"><a href="#epTabPlanta" data-toggle="tab">Cambiar Planta</a></li>
                    <li><a href="#epTabPass" data-toggle="tab">Cambiar Contrase&ntilde;a</a></li>
                </ul>

                <div class="tab-content">
                    <!-- TAB: CAMBIAR PLANTA -->
                    <div class="tab-pane active" id="epTabPlanta">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos Empresa/Cliente</legend>
                            <div class="form-group">
                                <label class="col-sm-3 control-label label-sm" style="margin-left: -20px;">Cliente:</label>
                                <div class="col-sm-8">
                                    <div style="display: flex; align-items: stretch; width: 100%;">
                                        <input id="epPrs_Ced" type="text" class="form-control input-sm" style="width: 120px; text-align: center; font-weight: bold; background: #b8cde6; background: linear-gradient(to bottom, #f0f5f9 0%,#b8cde6 100%); color: #337ab7; border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: 0;" readonly placeholder="---" />
                                        <input id="epCliNom" class="form-control input-sm" style="flex: 1; background: #f9f9f9; border-radius: 0; border-right: 0;" readonly placeholder="Nombre del Cliente..." />
                                        <div style="display: flex;">
                                            <button type="button" id="epBtnBusCli" class="btn btn-success btn-sm" style="border-radius: 0; margin: 0; height: 26px;" title="Buscar Cliente">
                                                <span class="glyphicon glyphicon-transfer"></span>
                                            </button>
                                            <button type="button" id="epBtnLimpiar" class="btn btn-danger btn-sm" style="border-top-left-radius: 0; border-bottom-left-radius: 0; margin: 0; height: 26px;" title="Limpiar">
                                                <span class="glyphicon glyphicon-trash"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label label-sm" style="margin-left: -20px;">C&oacute;digo de Planta:</label>
                                <div class="col-sm-8">
                                    <select id="epPla_Cod" class="form-control input-sm">
                                        <option value="">-- Seleccione --</option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <!-- TAB: CAMBIAR CONTRASEÑA -->
                    <div class="tab-pane" id="epTabPass">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Seguridad de Usuario</legend>
                            
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-sm">Usuario:</label>
                                <div class="col-sm-9">
                                    <div style="display: flex; align-items: stretch; width: 100%;">
                                        <input id="epUserCed" class="form-control input-sm" style="width: 110px; text-align: center; font-weight: bold; background: #b8cde6; background: linear-gradient(to bottom, #f0f5f9 0%,#b8cde6 100%); color: #337ab7; border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: 0;" readonly placeholder="---" />
                                        <input id="epUserNom" class="form-control input-sm" style="flex: 1; background: #f9f9f9; border-top-left-radius: 0; border-bottom-left-radius: 0;" readonly placeholder="Nombre del Usuario..." />
                                    </div>
                                </div>
                            </div>

                            <hr style="margin: 12px 0; border-color: #ddd;" />

                            <div class="form-group">
                                <label class="col-sm-3 control-label label-sm"><span class="required-star">*</span> Nueva clave:</label>
                                <div class="col-sm-8">
                                    <input id="epNewPass" type="password" class="form-control input-sm" placeholder="Nueva contrase&ntilde;a..." maxlength="20" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label label-sm"><span class="required-star">*</span> Confirmar:</label>
                                <div class="col-sm-8">
                                    <input id="epConfPass" type="password" class="form-control input-sm" placeholder="Repita contrase&ntilde;a..." maxlength="20" />
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 5px;">
                                <div class="col-sm-offset-4 col-sm-8">
                                    <span class="text-muted" style="font-size: 11px;">
                                        <i class="glyphicon glyphicon-info-sign"></i> Solo letras y n&uacute;meros.
                                    </span>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>

            <div style="text-align:center; padding: 15px 0 5px;">
                <button type="button" id="epBtnGuardar" class="btn btn-sm btn-primary" style="padding: 6px 10px; height: 30px;">
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

        <script src="../../Librerias/validaciones/validacion.js"></script>
        <script src="../VALIDACIONES/man_adm_usuario.js?x=10"></script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script> 
    </BODY>
</HTML> 