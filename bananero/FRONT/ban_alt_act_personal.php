<?php
/**
 * @abstract Permite registrar las actividades del personal
 * @author Cesar Bermeo.
 * @version 1.0
 * Fecha de creaicón: 25/01/2019
 *
 */

 require_once('../../administrador/LOGICA/seguridad.php');
 require_once('../LOGICA/ban_log_act_per.php');
 require_once('../../Librerias/procedimientos/almacenados_standar.php');

 /* Creacion del Objeto de conexion */
 $obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
 /* Creacion del objeto mysql para las consultas */
 $obBD_con1 = new Class_Log_Datos_Act;
 /**
  *
  */

  /**
   *
   */
?>
<!DOCTYPE html>
<HTML>

    <HEAD>
        <TITLE>
            <?Php echo $Ses_Sys_Nom; ?>
        </TITLE>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script> </script>
        <style></style>
    </HEAD>
    <BODY>
        <div class="panel panel-main" id="formFinal">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Control de Actividades</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panels-area form-horizontal normal ">
                            <div class="row">
                                <form id="frm_actividades" name="frm_actividades" class="form-horizontal normal" action="">
                                    <div class="col-xs-6">
                                        <fieldset class="exa-fieldset" id="actFormTemp">
                                            <div class="form-group">
                                                <label class="control-label col-md-2 col-sm-4 label-sm required">Tipo:</label>

                                            </div>
                                        </fieldset>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="../VALIDACIONES/ban_val_actividades.js?k=112"></script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    </BODY>

</HTML>