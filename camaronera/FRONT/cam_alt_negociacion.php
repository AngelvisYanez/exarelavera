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
$obBD_conexion = new Class_Log_Conexion_Cam($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_datos_Cam();


if (isset($prodAjax)) {
    $obBD_con1->getPageGridJson('proveedore.selectWhere', array_merge($_GET, array('setWhere' => 'isNotProductor')), $obBD_conexion);
}

if (isset($sectorAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(1,  $Prod_Cod, $obBD_conexion);
    $obBD_con1->echoJson($responce);
}

if (isset($secNegAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod, $obBD_conexion);
    $Num_Neg = $responce['response'][0]['Num_Neg'] + 1;
    $Num_Neg = str_pad($Num_Neg, 10, '0', STR_PAD_LEFT);
    $obBD_con1->echoJson($Num_Neg);
}

if (isset($saveNegociacion)) {
    try {
        $encabezado_negociacion = array('Tip_Neg' => $Tip_Neg, 'Prod_Cod' => $Prod_Cod, 'Sec_Cod' => $Sec_Cod, 'Num_Neg' => $Num_Neg, 'Fec_Neg' => $Fec_Neg, 'Val_garantia' => $Val_garantia, 'Val_Gar_Neta' => $Val_Gar_Neta, 'Val_Ant' => $Val_Ant, 'Val_Balanceado' => $Val_Balanceado, 'Val_Larva' => $Val_Larva, 'Neg_Tot' => $Neg_Tot, 'Tot_Libras' => $Tot_Libras, 'Est_Neg' => 'A', 'Link_Contrato' => $Link_Contrato, 'Link_Garantia' => $Link_Garantia, 'Link_Verf_Garan' => $Link_Verf_Garan, 'Neg_Des' => $Neg_Des, 'Emp_Cod' => $Ses_Emp_Cod);
        $obBD_con1->operacionobBD(3, $encabezado_negociacion, $obBD_conexion);
        $response = array('success' => true, 'message' => "Transaccion realizada con exito");
    } catch (Exception $e) {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }
    echo json_encode($response);
    exit();
}

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
            <h3 class="panel-title">&raquo; Registrar Negociación</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="panels-area form-horizontal normal ">
                        <div class="row">
                            <form id="frm_negociacion" name="frm_negociacion" class="form-horizontal normal" action="javascript:validaDocument();">

                                <div class="col-xs-12">
                                    <fieldset class="exa-fieldset">
                                        <div class="form-group">
                                            <label class="col-xs-1 control-label label-xs">Fecha:</label>
                                            <div class="col-xs-2">
                                                <input name="Fec_Neg" type="date" value="<?php echo  date("Y-m-d") ?>" class="form-control input-xs ">
                                            </div>
                                            <label class="col-xs-2 control-label label-xs">Nro. Negociación:</label>
                                            <div class="col-xs-2">
                                                <input name="Num_Neg" type="text" class="form-control input-xs ">
                                            </div>
                                            <label class="col-xs-2 control-label label-xs">Tipo Negociación:</label>
                                            <div class="col-xs-3">
                                                <select name="Tip_Neg" id="Tip_Neg" class="form-control input-xs ">
                                                    <option value="1">Con Anticipo</option>
                                                    <option value="2" selected>Sin Anticipo (Contado)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                                <div class="col-xs-6">
                                    <fieldset class="exa-fieldset">
                                        <div class="form-group">
                                            <label class="control-label col-md-2 col-sm-4 label-sm required">Productor:</label>
                                        </div>
                                        <div class="form-group col-xs-12">
                                            <div class="form-group">
                                                <input type="hidden" name="Prod_Cod" id="Prod_Cod" class="form-control input-xs">
                                                <label class="col-xs-3 control-label label-xs">Nombre:</label>
                                                <div class="col-xs-9">
                                                    <div class="input-group input-group-xs">
                                                        <input name="Nom_Prod" id="Nom_Prod" type="text" class="form-control input-xs ">
                                                        <span class="input-group-btn">
                                                            <button id="Prv_Btn" type="button" onclick="$('#prodDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Productor" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Contacto:</label>
                                                <div class="col-xs-9"><input name="Telf_Prod" type="text" class="form-control input-xs" readonly></div>
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
                                                    <select name="Sec_Cod" id="Sec_Cod" type="text" class="form-control input-xs "></select>
                                                </div>
                                            </div>
                                            <!--div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Encargado:</label>
                                                <div class="col-xs-9"><input name="Sec_Encargado" id="Sec_Encargado" type="text" class="form-control input-xs "></div>
                                            </div-->
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
                                                    <input name="Val_garantia" type="number" step="any" class="form-control input-xs ">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Garantia neta:</label>
                                                <div class="col-xs-9"><input name="Val_Grnt_Neta" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                        </div>
                                        <div class="form-group col-xs-4">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Anticipo:</label>
                                                <div class="col-xs-9"><input name="Val_Ant" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Balanceado:</label>
                                                <div class="col-xs-9"><input name="Val_Balanceado" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Larva:</label>
                                                <div class="col-xs-9"><input name="Val_Larva" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Total:</label>
                                                <div class="col-xs-9"><input name="Neg_Tot" type="number" step="any" class="form-control input-xs "></div>
                                            </div>
                                        </div>
                                        <div class="form-group col-xs-3">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Libras:</label>
                                                <div class="col-xs-9"><input name="Tot_Libras" type="number" step="any" class="form-control input-xs "></div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-xs-12">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Link contrato:</label>
                                        <div class="col-xs-9"> <input type="text" class="form-control input-xs" id="Link_Contrato" name="link_Contrato"></span></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Link Garantia:</label>
                                        <div class="col-xs-9"> <input type="text" class="form-control input-xs" id="Link_Garantia" name="Link_Garantia"></span></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Link Verificación garantia:</label>
                                        <div class="col-xs-9"> <input type="text" class="form-control input-xs" id="Link_Verf_Garantia" name="Link_Verf_Garantia"></span></div>
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
                                        <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk" ></i> Cancelar</button>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="$('#frm_negociacion').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="prodDialog" title="B&uacute;squeda de Productor"></div>

    <script>
        function selectProd(productor) {
            var reset = ($('#reset').val() !== '0');
            $('#frm_negociacion').setData($.extend(productor, {
                op_opciones: 'c'
            }), 'name').find('.dialogSearch').addClass('x');
            console.log(productor);
            $('#Prod_Cod').val(1 /* productor.Prs_Cod  */ );
            $('#Nom_Prod').val(productor.Prs_Nom + ' ' + productor.Prs_Ape);
            $('#Telf_Prod').val(productor.Prs_Tel);
            //Cargar los sectores
            $.ajax({
                url: '',
                method: 'POST',
                data: {
                    sectorAjax: true,
                    Prod_Cod: 11
                },
                dataType: 'json',
                success: function(response) {
                    const select = $('#Sec_Cod');
                    select.empty();
                    if (Array.isArray(response.response)) {
                        response.response.forEach(function(item) {
                            console.log(item.Sec_Cod);
                            select.append(`<option value="${item.Sec_Cod}">${item.Sec_Nom}</option>`);
                        });
                    } else {
                        console.error('La respuesta no es un array:', response);
                    }
                },
                error: function() {
                    alert('Error al obtener los datos');
                }
            });
            $('#prodDialog').dialog('close');
        }

        /* function SelectCta(cta) {
             $('#' + ($('#Index').val())).setData($.getDialogGrid("#cuenDialog").jqGrid('getRowData', cta['Pld_Cod']), 'name');
             $('#cuenDialog').dialog('close');
         }*/
    </script>
    </script>


    <script language="javascript" src="../VALIDACIONES/cam_val_negociacion.js"></script>
    <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>
</HTML>