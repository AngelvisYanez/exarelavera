<?Php 
/**
 * Developer    :   Asael Tello Barcia
 * Fecha        :   04-09-2017
*/	
require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_usuarios_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

    /**
    * objeto para la conexion
    * @var Class_Log_Conexion_Tes
    */
    $obBD_conexion = new Class_Log_Conexion_Admu($Ses_Dat_Dis);


    /**
    * objeto para consultas
    * @var Class_Log_Datos_Tes
    */
    $obBD_con1 =  new Class_Log_Datos_Admu;    
    
    /*
     * Valida si el password es correcto
     * 
     */
    if(isset($searchPass))
    {  
        $response = $obBD_con1->getRowConsulta(4, array("Usu_Cod" => $Ses_Usu_Cod, "Usu_Pal" => $_GET['Usu_Pal']) , $obBD_conexion); //       
        if ($response['contador'] > 0) // si existe la persona
        {        
            $response['existe']=1;
            $response['success'] = true;
        }
        else
        {
            $response['existe']=0;
            $response['success'] = true;
        }
        $obBD_con1->echoJson($response);
    }
    
    /*
     * Update Password
     * 
     */
    if(isset($updatePass))
    {        
        $obBD_con1->inicio_transaccion($obBD_conexion);
        $obBD_con1->operacionobBD(3, array("Usu_Cod" => $Ses_Usu_Cod, "Usu_Pal" => $_GET['Usu_Pal']) ,$obBD_conexion); //udpate pass
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion))
        {             
            $response['success'] = true;
            $response['message'] = "Password actualizado correctamente";
        }
        else
        { 
            $response['success'] = false; 
            $response['message'] = "No se ha logrado realizar la Transaccion";
        }
        $obBD_con1->echoJson($response);
    }

?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Usuario Password [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>        
        <script type="text/javascript" src="../VALIDACIONES/adm_val_usuarios_2.0.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.2.0/zxcvbn.js"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Cambio De Clave De Usuario</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div id="tabsUser" class="ui-tab-fix ui-tabs">                                               
                            <!-- CREAR TAB !-->
                            <div id="tabs-1" style="min-height: 150px;">
                                <div class="col-sm-3"></div>
                                <div class="col-md-6 col-sm-8">
                                <form class="form-horizontal normal" id="frmUser" name="frmUser" autocomplete="off">                                   
                                    
                                    <!-- LOGUEO -->
                                    <fieldset class="exa-fieldset" >
                                        <legend class="Titulos2">Datos a Modificar</legend>
                                        
                                        <!-- Cedula -->
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs">Cédula:</label>  
                                            <div class="col-xs-4" >
                                                <label id="Usu_Ced" name="Usu_Ced"><?Php echo $Ses_Usu_Ced; ?></label>
                                            </div>
                                        </div>

                                        <!-- Usuario -->
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs">Usuario:</label>  
                                            <div class="col-xs-4" >
                                                <label id="Usu_Nom" name="Usu_Nom"><?Php echo $Ses_Prs_Ape." ".$Ses_Prs_Nom; ?></label>
                                            </div>
                                        </div>
                                        
                                        <!-- Clave -->
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs required">Clave Actual:</label>  
                                            <div class="col-xs-4" >
                                                <div class="input-group input-group-xs">
                                                    <input id="Usu_Pal_c" name="Usu_Pal_c" type="password" class="form-control input-xs" required="" />
                                                    <span class="input-group-addon validate" ><i></i></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Nueva Clave -->
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs required">Nueva Clave:</label>  
                                            <div class="col-xs-4" >
                                                <div class="input-group input-group-xs">
                                                    <input id="Usu_Pal" name="Usu_Pal" type="password" class="form-control input-xs" required="" />
                                                    <span class="input-group-addon validate" ><i></i></span>                                                    
                                                </div>                                                
                                            </div>
                                            <div class="col-xs-2">
                                                <meter max="4" id="password-strength-meter"></meter>
                                            </div>
                                            <div>
                                                <p id="password-strength-text"></p>
                                            </div>
                                        </div>

                                        <!-- Confirmar Clave -->
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs required">Confirmar Clave:</label>  
                                            <div class="col-xs-4" >
                                                <div class="input-group input-group-xs">
                                                    <input id="Usu_Pal_C" name="Usu_Pal_C" type="password" class="form-control input-xs" required="" />
                                                    <span class="input-group-addon validate" ><i></i></span>
                                                </div>
                                            </div>
                                        </div>                                       

                                    </fieldset>
                                    
                                    <div class="center">
                                        <button type="button" class="btn btn-sm btn-primary no" id="btnGuardar"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                    </div>
                                </form>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>        
    </BODY>
</HTML>