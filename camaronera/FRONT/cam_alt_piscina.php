<?php

/**
 * @abstract Permite registrar las negociaciones en la compra/venta de productos de camaronera.
 * @author Wilson Belduma.
 * @version 1.0
 * Fecha de creaicón: 25/01/2025
 *
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/cam_log_negociacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Cam();

?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE>
        <?php echo $Ses_Sys_Nom; ?>
    </TITLE>
    <meta charset="UTF-8">
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
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar Negociación</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="panels-area form-horizontal normal ">
                        <div class="row">
                            <form id="frm_negociacion" name="frm_negociacion" class="form-horizontal normal" action="">
                                <div class="col-xs-12">
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Fecha:</label>
                                            <div class="col-xs-4">
                                                <input name="date_contrato" type="date" value="<?php echo  date("Y-m-d") ?>" class="form-control input-xs ">
                                            </div>
                                            <label class="col-xs-2 control-label label-xs">Nro:</label>
                                            <div class="col-xs-4">
                                                <input name="cod_neg" type="text" class="form-control input-xs ">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xs-6">
                                    <fieldset class="exa-fieldset">
                                        <div class="form-group">
                                            <label class="control-label col-md-2 col-sm-4 label-sm required">Productor:</label>
                                        </div>
                                        <div class="form-group col-xs-12">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Nombre:</label>
                                                <div class="col-xs-9">
                                                    <input name="val_grnt" type="text"  class="form-control input-xs ">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Contacto:</label>
                                                <div class="col-xs-9"><input name="val_grnt_neta" type="text"  class="form-control input-xs "></div>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>

                                <div class="col-xs-6">
                                    <fieldset class="exa-fieldset">
                                        <div class="form-group">
                                            <label class="control-label col-md-2 col-sm-4 label-sm required">Sector:</label>
                                        </div>
                                        <div class="form-group col-xs-12">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Nombre:</label>
                                                <div class="col-xs-9">
                                                    <input name="val_grnt" type="text" class="form-control input-xs ">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Encargado:</label>
                                                <div class="col-xs-9"><input name="val_grnt_neta" type="text"  class="form-control input-xs "></div>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>

                                <div class="col-xs-12">
                                    <fieldset class="exa-fieldset">
                                        <label class="control-label col-md-2 col-sm-4 label-sm">Garantia:</label>
                                    </fieldset>
                                    <div class="col-xs-12">
                                        <div class="form-group col-xs-4">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Valor garantia:</label>
                                                <div class="col-xs-9">
                                                    <input name="val_grnt" type="number" step="any" class="form-control input-xs ">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Garantia neta:</label>
                                                <div class="col-xs-9"><input name="val_grnt_neta" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                        </div>
                                        <div class="form-group col-xs-4">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Anticipo:</label>
                                                <div class="col-xs-9"><input name="Cop_Num" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Balanceado:</label>
                                                <div class="col-xs-9"><input name="Cop_Aut" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Larva:</label>
                                                <div class="col-xs-9"><input name="Cop_Aut" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Total:</label>
                                                <div class="col-xs-9"><input name="Cop_Aut" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                        </div>
                                        <div class="form-group col-xs-3">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Libras:</label>
                                                <div class="col-xs-9"><input name="proveedor" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Dolares:</label>
                                                <div class="col-xs-9"><input name="proveedor" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-xs-12">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Link contrato:</label>
                                        <div class="col-xs-9"> <input type="text" class="form-control input-xs" id="link" name="link"></span></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Link Garantia:</label>
                                        <div class="col-xs-9"> <input type="text" class="form-control input-xs" id="link" name="link"></span></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Link Verificación garantia:</label>
                                        <div class="col-xs-9"> <input type="text" class="form-control input-xs" id="link" name="link"></span></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Nota:</label>
                                        <div class="col-xs-9">
                                            <textarea class="form-control input-xs" name="nota" id="nota"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="center">
                                        <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                    </div>
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