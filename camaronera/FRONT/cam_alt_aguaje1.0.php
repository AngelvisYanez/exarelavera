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
        <?Php echo $Ses_Sys_Nom; ?>
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
            <h3 class="panel-title">&raquo; Aguages</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="panels-area form-horizontal normal ">
                        <div class="row">
                            <form action="frm_aguaje" id="frm_aguaje">
                                <div class="col-xs-12">
                                    <div class="col-xs-12">
                                        <div class="form-group col-xs-4">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Código:</label>
                                                <div class="col-xs-9"><input name="Cod_Agu" id="Cod_Agu" type="text" class="form-control input-xs "></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Num. Aguaje:</label>
                                                <div class="col-xs-9"><input name="Num_Agu" type="text" class="form-control input-xs "></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">activo:</label>
                                                <div class="col-xs-9"><input name="Cop_Aut" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Nota:</label>
                                                <div class="col-xs-9"><input name="nota_agu" id="nota_agu" type="text" class="form-control input-xs "></div>
                                            </div>
                                            <div class="form-group">
                                                <div class="center">
                                                    <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div id="tbl_agu">
                            </div>

                            <div class="col-md-12">
                                <div class="col-md-3">
                                    <label for="">TIPO: ENTERO</label>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Talla</th>
                                                <th>Precio A</th>
                                                <th>Precio B</th>
                                                <th>Medida</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="text" value="100/120" class="form-control form-control-sm" style="width:80px"></td>
                                                <td><input type="text" value="0.00000" class="form-control form-control-sm" style="width:80px"></td>
                                                <td><input type="text" value="0.00000" class="form-control form-control-sm" style="width:80px"></td>
                                                <td>Kilos</td>
                                            </tr>
                                            <tr>
                                                <td>100/120</td>
                                                <td>0.00000</td>
                                                <td>0.00000</td>
                                                <td>Kilos</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-3">
                                    <label for="">TIPO: COLA(A)</label>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Talla</th>
                                                <th>Precio A</th>
                                                <th>Precio B</th>
                                                <th>Medida</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>100/120</td>
                                                <td>0.00000</td>
                                                <td>0.00000</td>
                                                <td>Kilos</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-3">
                                    <label for="">TIPO: COLA(B)</label>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Talla</th>
                                                <th>Precio A</th>
                                                <th>Precio B</th>
                                                <th>Medida</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>100/120</td>
                                                <td>0.00000</td>
                                                <td>0.00000</td>
                                                <td>Kilos</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-3">
                                    <label for="">TIPO: NACIONAL</label>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Talla</th>
                                                <th>Precio A</th>
                                                <th>Precio B</th>
                                                <th>Medida</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>100/120</td>
                                                <td>0.00000</td>
                                                <td>0.00000</td>
                                                <td>Kilos</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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