<?php 
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
        } else {
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
        <!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?php echo "Usuario Password [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>        
        <script language="javascript" src="../VALIDACIONES/adm_val_usuarios_2.0.js?e=3"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.2.0/zxcvbn.js"></script>
        <style>
            :root {
                --primary-blue: #337ab7;
                --dark-blue: #2c3e50;
                --bg-soft: #ebf2f7;
                --card-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            }

            body {
                background-color: #f5f7f9;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
            }

            .panel-main {
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: var(--card-shadow);
                margin: 20px 10px;
                background: white;
            }

            .exa-header {
                background-color: var(--dark-blue) !important;
                padding: 10px 15px !important;
                border-bottom: 2px solid var(--primary-blue) !important;
            }

            .exa-header .panel-title {
                color: white !important;
                font-weight: 600;
                font-size: 1.2rem;
                margin: 0;
            }

            .exa-body {
                padding: 30px !important;
                background-color: #f9fbfd;
            }

            .exa-fieldset {
                border: 1px solid #dce4ec;
                padding: 25px !important;
                margin-bottom: 25px;
                border-radius: 4px;
                background: white;
                position: relative;
            }

            .Titulos2 {
                width: auto;
                border-bottom: none;
                padding: 0 10px;
                margin-bottom: 0;
                font-size: 1rem;
                font-weight: 700;
                color: #2c3e50;
                position: absolute;
                top: -10px;
                left: 15px;
                background: white;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .control-label {
                color: #555;
                font-weight: 600;
                padding-top: 7px;
            }

            .form-control {
                border-radius: 4px;
                border: 1px solid #ccc;
                height: 34px;
            }

            .form-control:focus {
                border-color: var(--primary-blue);
                box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102, 175, 233, .6);
            }

            .input-group-addon {
                background-color: #eee;
                border-color: #ccc;
            }

            .required:before {
                content: "* ";
                color: #27ae60;
                font-weight: bold;
            }

            #btnGuardar {
                background-color: var(--primary-blue);
                border: 1px solid #2e6da4;
                padding: 8px 30px;
                border-radius: 4px;
                font-weight: 600;
                color: white;
                transition: all 0.2s;
            }

            #btnGuardar:hover {
                background-color: #286090;
                border-color: #204d74;
            }

            .data-info-box {
                background: #f8fafc;
                border-radius: 4px;
                padding: 15px;
                margin: 10px 0 25px 0;
                border: 1px solid #e2e8f0;
            }

            .data-label-text {
                font-weight: 700;
                color: #2c3e50;
            }

            #password-strength-meter {
                width: 100%;
                height: 6px;
                margin-top: 5px;
            }

            #password-strength-text {
                font-size: 11px;
                margin-top: 4px;
                color: #777;
            }

            @media (max-width: 768px) {
                .exa-body {
                    padding: 15px !important;
                }
            }
        </style>
    </HEAD>
    <BODY>
        <div class="container-fluid">
            <div class="panel panel-main">
                <div class="panel-heading exa-header">
                    <h3 class="panel-title"><i class="glyphicon glyphicon-lock"></i> Cambio de Clave de Usuario</h3>
                </div>
                <div class="panel-body exa-body">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1">
                            <form id="frmUser" name="frmUser" autocomplete="off" class="form-horizontal">                                   
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Datos a Modificar</legend>
                                    
                                    <div class="data-info-box">
                                        <div class="row">
                                            <div class="col-xs-6 text-center">
                                                <span style="color: #666;">Cédula:</span> 
                                                <span class="data-label-text" id="Usu_Ced"><?php echo $Ses_Usu_Ced; ?></span>
                                            </div>
                                            <div class="col-xs-6 text-center">
                                                <span style="color: #666;">Usuario:</span> 
                                                <span class="data-label-text" id="Usu_Nom"><?php echo $Ses_Prs_Ape." ".$Ses_Prs_Nom; ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Clave Actual -->
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label required">Clave Actual:</label>  
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <input id="Usu_Pal_c" name="Usu_Pal_c" type="password" class="form-control" required />
                                                <span class="input-group-addon validate"><i></i></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Nueva Clave -->
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label required">Nueva Clave:</label>  
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <input id="Usu_Pal" name="Usu_Pal" type="password" class="form-control" placeholder="Mínimo 8 caracteres (letras y números)" required />
                                                <span class="input-group-addon validate"><i></i></span>                                                    
                                            </div>
                                            <meter max="4" id="password-strength-meter"></meter>
                                            <p id="password-strength-text"></p>
                                        </div>
                                    </div>

                                    <!-- Confirmar Clave -->
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label required">Confirmar Clave:</label>  
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <input id="Usu_Pal_C" name="Usu_Pal_C" type="password" class="form-control" required />
                                                <span class="input-group-addon validate"><i></i></span>
                                            </div>
                                        </div>
                                    </div>                                       

                                </fieldset>
                                
                                <div class="text-center">
                                    <button type="button" class="btn btn-primary" id="btnGuardar">
                                        <i class="glyphicon glyphicon-floppy-disk"></i> Guardar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>        
    </BODY>
</HTML>