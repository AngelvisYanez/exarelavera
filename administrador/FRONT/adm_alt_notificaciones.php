<?php 
/**
* Descripcion:          Modulo de Notificaciones
* Fecha de creacion:    21/11/2019
* Desarrollador:  Santiago Ruiz
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_notificaciones.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');  
//
/** 
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");
$mes = date("m");
    /*
     * Guarda notificacion
     */
     if(isset($saveAut))
     {                 
 
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(1003,$_POST,$obBD_conexion);
        $obBD_con1->operacionobBD(1004,$_POST,$obBD_conexion);
        $obBD_con1->operacionobBD(1005,$_POST,$obBD_conexion);
        $obBD_con1->operacionobBD(1006,$_POST,$obBD_conexion);
        $obBD_con1->operacionobBD(1007,$_POST,$obBD_conexion);

        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion))
        { 
            $responce['success'] = true;            
        }
        else
        {
            $responce['success'] = false;
            $responce['message'] = "No se ha logrado realizar la Transacción";
        }
        
        $obBD_con1->echoJson($responce); 
     }

    /*
     * Modifica notificacion
     */
     if(isset($modifyAut))
     {         
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(1009,$_POST,$obBD_conexion);
        $obBD_con1->operacionobBD(1010,$_POST,$obBD_conexion);
        $obBD_con1->operacionobBD(1011,$_POST,$obBD_conexion);
        $obBD_con1->operacionobBD(1012,$_POST,$obBD_conexion);  
        $obBD_con1->operacionobBD(1013,$_POST,$obBD_conexion); 

        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion))
        { 
            $responce['success'] = true;            
        }
        else
        { 
            $responce['success'] = false;
            $responce['message'] = "No se ha logrado realizar la Transacción";
        }
        $obBD_con1->echoJson($responce); 
     }

    
    /*
     * Activa o desactiva el estado de la notificacion
     */
    if(isset($setEstado))
    {
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(5013,$_GET,$obBD_conexion);
        $obBD_con1->operacionobBD(5014,$_GET,$obBD_conexion);
        $obBD_con1->operacionobBD(5016,$_GET,$obBD_conexion);
        $obBD_con1->operacionobBD(5017,$_GET,$obBD_conexion);
        $obBD_con1->operacionobBD(5018,$_GET,$obBD_conexion);
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion))
        { 
            $responce['success'] = true;
         
        }
        else
        {
            $responce['success'] = false;
            $responce['message'] = "No se ha logrado realizar la Transacción";
        }
        
        $obBD_con1->echoJson($response);
    }

    if(isset($searchFiltro))
     {
         //$obBD_con1->debug(true);
         $obBD_con1->getPageGridJson(28, $_GET, $obBD_conexion,true); 
     }

    if(isset($searchAll))
    {
       //$obBD_con1->debug(true);
       $data = $obBD_con1->getArrayConsulta(101,  $_GET, $obBD_conexion);
       // Grid necesita este array
       $obBD_con1->echoJson(array(
           'rows'=>$data,
           'total'=>1,
           'records'=>count($data),
           'success'=>true
       ));
    }

    /*
     * Get Empresas activas registradas (all)
     */
    $empresas = $obBD_con1->getArrayConsulta(515, $Ses_Emp_Cod, $obBD_conexion);
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Empresas Notificaciones [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>       
        <script language="javascript" src="../VALIDACIONES/adm_val_notificaciones.js?a=a17"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Notificaciones</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-sm-12">
                        <div id="tabsSearch" class="ui-tab-fix ui-tabs">
                            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                              <li><a href="#tabs-1">Gestion Notificaciones</a></li>                            
                            </ul>
                            <div id="tabs-1" class="ui-tabs-panel ui-widget-content ui-corner-bottom" style="min-height: 350px;">
                                <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:$('#tableResult').Search('#frm_bus','searchFiltro');">
                                  <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">B&uacute;squeda de Notificaciones</legend>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                        <div class="col-sm-4 radioset">
                                            <input id="rad_ba2" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)"/>
                                        
                                            <input id="rad_bb1" name="est_opciones" type="radio" value="a" checked="" onclick="setfocus(this.form.search)"/><label for="rad_bb1">Activo</label>
                                            <input id="rad_bb2" name="est_opciones" type="radio" value="i" onclick="setfocus(this.form.search)"/><label for="rad_bb2">Inactivo</label>
                                        </div>
                                    </div>
                 
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                        <div class="col-sm-5">
                                            <div class="input-group">
                                                <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-xs" type="button" title="Buscar Notificacion" id="btnSearch" name="btnSearch" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <!--<div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Filtrar por estado:</label>
                                        
                                    </div>-->
                                </fieldset>                             
                                </form>
                                <div >
                                    <TABLE id="tableResult">
                                        
                                    </TABLE>
                                    <div id="tableResultPager"> 
                                        
                                    </div>
                                    <BR>
                                    <div class="col-sm-2">
                                        <button id="btnNueva" name="btnNueva" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-plus"></i>   Nueva</button>
                                    </div>
                                </div>
                            </div>                          
                        </div>
                                  
                    </div>
                </div>                
            </div>
        </div>
        <div id="editDialog" title="">
            <form id ="formDialog" name="formDialog" class="form-horizontal" autocomplete="off">              
                 <fieldset>
                <div class="form-group Titulos2">
                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                </div>
                
                <!-- Cod Notificacion-->
                <div>
                    <input type="text" id="not_cod" name="not_cod" hidden="true">
                </div>

                 <!-- Eempresa -->
                <div class="form-group">
                  <label class="col-xs-4 control-label label-xs required" for="Emp_Cod">Empresa:</label>
                  <div class="col-xs-8">
                    <select id="Emp_Cod_n" name="Emp_Cod_n" class="form-control input-xs">
                        <?php 
                        foreach ($empresas as $emp) {
                            echo "<option value='{$emp['Emp_Cod']}'>{$emp['Emp_Nom']}</option>";
                        }
                        ?>
                    </select>
                  </div>
                </div>

                <!-- Fecha Inicio-->
                <div class="form-group">
                  <label class="col-md-4 control-label label-xs required" for="not_fei">Fecha Inicio:</label>  
                  <div class="col-md-6">
                      <input id="not_fei" name="not_fei" type="text" placeholder="" class="form-control input-xs" value="<?PHP echo $hoy; ?>" />
                  </div>
                </div>
                
                <!-- Fecha Fin-->
                <div class="form-group">
                  <label class="col-md-4 control-label label-xs required" for="not_fec">Fecha Fin:</label>  
                  <div class="col-md-6">
                  <input id="not_fec" name="not_fec" type="text" placeholder="" class="form-control input-xs"> 
                  </div>
                </div>
                
                <!-- Encabezado-->
                <div class="form-group">
                  <label class="col-md-4 control-label label-xs required" for="not_enc">Encabezado:</label>  
                  <div class="col-md-6">
                  <input id="not_enc" name="not_enc" type="text" placeholder="" class="form-control input-xs" >
                  </div>
                </div>

                <!-- Contenido-->
                <div class="form-group">
                  <label class="col-md-4 control-label label-xs required" for="not_msj">Mensaje:</label>  
                  <div class="col-md-6">
                  <textarea id="not_msj" name="not_msj" type="text" placeholder="" class="form-control input-xs" ></textarea> 
                  </div>
                </div>      
                
                
                <!-- Buttons -->
                <div class="form-group">
                  <label class="col-md-4 control-label" for="btnModificar"></label>
                  <div class="col-md-8">
                      <button id="btnAccion" name="btnAccion" class="btn btn-sm btn-primary"></button>
                  </div>
                </div>

                </fieldset>
            </form>
        </div>
    </BODY>
</HTML>