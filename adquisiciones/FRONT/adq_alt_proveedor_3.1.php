<?php

/**
 * Permite registrar un nuevo Cliente ya sea Nacional(Cedula o Ruc) o Extranjero(Pasaporte)
 * 
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualizaci�n:	2012-04-16
 * @author lewis.chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2014-05-21
 * 
 * @package tesoreria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adq_log_provee.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Tes
 */
$obBD_conexion = new Class_Log_Conexion_Prv($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Tes
 */
$obBD_con1 =  new Class_Log_Datos_Prv;

if (isset($cuenAjax)) {

    $Cop_Fec = $hoy;
    $data = $_GET;
    $data['Cop_Fec'] = $Cop_Fec;

    $configs = $obBD_con1->getRowConsulta(88, $Ses_Emp_Cod, $obBD_conexion);
    if ($configs['Cof_Con'] == 'S' && !empty($Cop_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(99, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
    $contar = $obBD_con1->getRowConsulta(47, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data['limits'] = $pagination['limits'];

    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(47, $data, $obBD_conexion);
        if ($configs['Cof_Con'] == 'S' && !empty($Pec_Cop['Pla_Cod'])) {
            foreach ($responce['rows'] as &$r) {
                $cuenta = $obBD_con1->getRowConsulta(60, $Pec_Cop['Pla_Cod'] . '*' . $r['Ren_Cod'] . '*C', $obBD_conexion);
                if (!empty($cuenta['Pld_Cod'])) $r = array_merge($r, $cuenta);
            }
            unset($r);
        }
    }
    $obBD_con1->echoJson($responce);
}

/* ver si exite una persona con el mismo numero de cedula que se desea registar al Proveedor */
if (isset($searchProveedor)) {
    $pers = $obBD_con1->getArrayConsulta(10, $Prs_Ced . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $responce = isset($pers[0]) ? $pers[0] : array();
    if (count($pers) > 0) {
        foreach ($pers as $p) {
            if ($p['Emp_Cod'] * 1 == $Ses_Emp_Cod * 1) {
                $responce = $p;
                break;
            }
        }
    }
    (!empty($responce['Ide_Cod'])) ?  $responce['Ide_Cod'] : $responce['Ide_Cod'] =   2; //Verificar luego
    //$responce = $obBD_con1->getRowConsulta(5, $Prs_Ced, $obBD_conexion);
    //$existe=$obBD_con1->getRowConsulta(9, $responce['Prs_Cod'].'*'.$Ses_Emp_Cod ,$obBD_conexion,true);
    (!empty($responce['Prs_Cod'])) ? $responce['exisPer'] = true : $responce['exisPer'] = false;
    (!empty($responce['Prv_Cod'])) ? $responce['exisProv'] = true : $responce['exisProv'] = false;
    $obBD_con1->echoJson($responce);
}

/* Actualizar Proveedor*/
if (isset($guardarProvAjax)) {
    $data = $_POST;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    // SI PERSONA EXISTE
    if (empty($data['Prs_Cod'])) {
        //GUARDA DATOS DE PERSONA
        $obBD_con1->operacionobBD(7, $data, $obBD_conexion);
        $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    } else {
        //ACTUALIZA CAMPOS DE PERSONA
        $obBD_con1->operacionobBD(6, $data, $obBD_conexion);
    }

    //GUARDA CAMPOS DE PROVEEDOR
    $obBD_con1->operacionobBD(8, $data, $obBD_conexion);

    //Guarda campos en autorizacion por default
    $data['Prv_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    $obBD_con1->operacionobBD(12, $data, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);

    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true, 'prov' => $data);
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', error => $obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($responce);
}

if (isset($guardarVIProveedor)) {
    $resp = array();
    $oBdSet = new MysqlDatos(true);
    $oBdSet->debug(true);
    $oBdSet->beginTrans();
    try {
        $Prv_Cod = $oBdSet->operation('proveedore.insert', array('Prs_Cod' => $Prs_Cod, 'Emp_Cod' => $_SESSION['Ses_Emp_Cod']))->lastId();
        $oBdSet->operation('compra_prov.insert', array('Prv_Cod' => $Prv_Cod));
        //$oBdSet->truncateTrans(); //si se guardo bien detengo el commit
        $oBdSet->endTrans($resp);
    } catch (Exception $e) {
        $oBdSet->revertTrans($e->getMessage(), $resp);
    }
    $oBdSet->echoJson($resp);
}

$rs_tip_compr = $obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('clean' => true, 'where' => array('Tic_Est' => 'A')), $obBD_conexion);

$varios = "persona.Prs_Ape = 'VARIOS EGRESOS'";
$busqueda = array(
    'variosIngresos' => $obBD_con1->getArrayConsulta('proveedore', array('where' => $varios, 'setWhere' => array('setEmpCod', 'isActive', /*'byVariosIngresos'*/)), $obBD_conexion),
    'variosIngresosPersona' => $obBD_con1->getArrayConsulta('persona', array('where' => $varios, 'setWhere' => array('isActive'/*,'byVariosIngresos'*/)), $obBD_conexion, true),
);
$obBD_con1->echoLog($busqueda);

?>

<!DOCTYPE html>
<html lang="es">

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Proveedor Registrar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script language="javascript" src="../../framework/plugins/cedulaRuc.js"></script>
    <script language="javascript" src="../../framework/plugins/validadorCedulaRucFinal.js"></script>


</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar Proveedores</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-3"></div>
                <div class="col-md-6 col-sm-8">
                    <form class="form-horizontal normal" id="formProvedor" action="javascript:GuardarProveedor();">
                        <input name="Prs_Cod" type="text" class="hidden" />
                        <input name="Prv_Cod" type="text" class="hidden" />
                        <input name="Emp_Cod" type="text" value='<?php echo $Ses_Emp_Cod; ?>' class="hidden" />

                        <input name="Prs_Ced_Ant" id="oldcedula" type="text" class="hidden" />
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del Proveedor</legend>
                            <?php if (!count($busqueda["variosIngresos"])) { ?>
                                <div class="col-sm-9"></div>
                                <div class="col-sm-3">
                                    <button id="btnCVI" type="button" onclick="saveItem()" data-persona="<?php echo htmlentities(json_encode($busqueda["variosIngresosPersona"])) ?>" class="btn btn-xs btn-info no"><i class="glyphicon glyphicon-tent"></i> Crear Varios Egresos</button>
                                </div>
                            <?php } ?>
                            
                            
                            

                            <div class="col-md-7">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                                    <div class="col-xs-9">
                                        <div class="input-group input-group-xs">
                                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" data-trigger="true" onchange="
                                        if (ValidacionCedulaRucService.esIdentificacionValida(this.value)['success'] && ValidacionCedulaRucService.esIdentificacionValida(this.value)['tipo_abrev'] !== 'PA') {
                                        $('#Ide_Cod').val(this.value.length === 10 ? 2 : (this.value.length === 13 ? 1 : 0));
                                        $('#Prv_Tic').val(ValidacionCedulaRucService.esIdentificacionValida(this.value)['tipo_abrev'] === 'NA' ? 'N' : 'J').trigger('change');
                                        $(this).fieldValid(true);
                                        searchProveedor($('#Prs_Ced').val(),'ec');
                                        } else {
										if(this.value.length>1 && this.value.length<14) {
											$('#Ide_Cod').val('3');
											$('#Prv_Tic').val('N').trigger('change');
											$(this).fieldValid('warning','FORMATO DESCONOCIDO');
											searchProveedor($('#Prs_Ced').val(),'ex');
										} else {
											$('#Ide_Cod').val('');
											$('#Prv_Tic').val('');
											$(this).fieldValid(false, ValidacionCedulaRucService.esIdentificacionValida(this.value)['message'] );
										}
                                    }
                                    ;" required="" />
                                            <span class="input-group-addon validate"><i id="ch"></i></span>
                                            <span class="input-group-addon alert-info"><input id="isRuc" type="checkbox" value="S" offval="N" style="vertical-align: middle;" onchange="setTipoDoc();"><b> RUC</b></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Documento:</label>
                                    <div class="col-xs-9">
                                        <?php $rs_identi = $obBD_con1->getArrayConsulta(3, '', $obBD_conexion); ?>
                                        <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                                            <option value="">Seleccionar</option>
                                            <?php foreach ($rs_identi as $row) {
                                                $row = array_map('utf8_encode', $row); // Convert each element to UTF-8
                                                echo "<option value='$row[Ide_Cod]'>$row[Ide_Des]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Contribuyente:</label>
                                    <div class="col-xs-9">
                                        <select id="Prv_Tic" name="Prv_Tic" class="form-control input-xs" required="" onchange="if (this.value === 'N') {
                                    $('.juridico').hide();
                                    $('.natural').show();
                                } else {
                                    $('.natural').hide();
                                    $('.juridico').show();
                                }">
                                            <option value="N">NATURAL</option>
                                            <option value="J">JURIDICO</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Razón Social:</span></label>
                                    <div class="col-xs-9"><input name="Prs_Ape" id="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                                </div>
                                <div class="form-group natural">
                                    <label class="col-xs-3 control-label label-xs">Nombres:</label>
                                    <div class="col-xs-9"><input name="Prs_Nom" id="Prs_Nom" type="text" class="form-control input-xs" /></div>
                                </div>
                                <div class="form-group natural">
                                    <label class="col-xs-3 control-label label-xs ">Genero:</label>
                                    <div class="col-xs-9">
                                        <select name="Prs_Sex" id="Prs_Sex" class="form-control input-xs ">
                                            <option value="M">MASCULINO</option>
                                            <option value="F">FEMENINO</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group juridico">
                                    <label class="col-xs-3 control-label label-xs ">Nomb.Comerc.:</label>
                                    <div class="col-xs-9"><input name="Prv_Com" id="Prv_Com" type="text" class="form-control input-xs" /></div>
                                </div>
                            </div>


                            <div class="col-md-5">
                                <div class="form-group">
                                    <div class="col-xs-12">
                                        <div class="checkbox check-big" style="position:absolute;">
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Esp" value="S" offval="N">Contrib. Especial</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Reg" value="S" offval="N" disabled>Reg. Micro.</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Con" value="S" offval="N">Obligado Contab.</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Ris" value="S" offval="N" disabled>RISE</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Rim_Emp" value="S" offval="N" onclick="toggleCheckbox('Prv_Rim_Np')">RIMPE Emprendedor</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Rim_Np" value="S" offval="N" onclick="toggleCheckbox('Prv_Rim_Emp')">RIMPE Neg. Popular</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Ag_Ret" value="S" offval="N">Agente Retención</label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label><input type="checkbox" name="Prv_Gct" value="S" offval="N">Grande Contribuyente</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            
                            
                            
                            
                            
                            
                            <!--div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                                <div class="col-xs-5">
                                    <div class="input-group input-group-xs">
                                        <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" data-trigger="true" onchange="
                                        if (ValidacionCedulaRucService.esIdentificacionValida(this.value)['success'] && ValidacionCedulaRucService.esIdentificacionValida(this.value)['tipo_abrev'] !== 'PA') {
                                        $('#Ide_Cod').val(this.value.length === 10 ? 2 : (this.value.length === 13 ? 1 : 0));
                                        $('#Prv_Tic').val(ValidacionCedulaRucService.esIdentificacionValida(this.value)['tipo_abrev'] === 'NA' ? 'N' : 'J').trigger('change');
                                        $(this).fieldValid(true);
                                        searchProveedor($('#Prs_Ced').val(),'ec');
                                        } else {
										if(this.value.length>1 && this.value.length<14) {
											$('#Ide_Cod').val('3');
											$('#Prv_Tic').val('N').trigger('change');
											$(this).fieldValid('warning','FORMATO DESCONOCIDO');
											searchProveedor($('#Prs_Ced').val(),'ex');
										} else {
											$('#Ide_Cod').val('');
											$('#Prv_Tic').val('');
											$(this).fieldValid(false, ValidacionCedulaRucService.esIdentificacionValida(this.value)['message'] );
										}
                                    }
                                    ;" required="" />
                                        <span class="input-group-addon validate"><i id="ch"></i></span>
                                        <span class="input-group-addon alert-info"><input id="isRuc" type="checkbox" value="S" offval="N" style="vertical-align: middle;" onchange="setTipoDoc();"><b> RUC</b></span>
                                    </div>
                                </div>
                                <div class="col-xs-4">
                                    <div class="checkbox check-big" style="position:absolute;">
                                        <label><input type="checkbox" name="Prv_Esp" value="S" offval="N">Contrib. Especial</label>
                                        <label><input type="checkbox" name="Prv_Reg" value="S" offval="N">Reg. Micro.</label>
                                        <label><input type="checkbox" name="Prv_Con" value="S" offval="N">Obligado Contab.</label>
                                        <label><input type="checkbox" name="Prv_Ris" value="S" offval="N">RISE</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Documento:</label>
                                <div class="col-xs-5">
                                    <?php $rs_identi = $obBD_con1->getArrayConsulta(3, '', $obBD_conexion); ?>
                                    <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($rs_identi as $row) {
                                            $row = array_map('utf8_encode', $row); // Convert each element to UTF-8
                                            echo "<option value='$row[Ide_Cod]'>$row[Ide_Des]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Contribuyente:</label>
                                <div class="col-xs-4">
                                    <select id="Prv_Tic" name="Prv_Tic" class="form-control input-xs" required="" onchange="if (this.value === 'N') {
                                    $('.juridico').hide();
                                    $('.natural').show();
                                } else {
                                    $('.natural').hide();
                                    $('.juridico').show();
                                }">
                                        <option value="N">NATURAL</option>
                                        <option value="J">JURIDICO</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Razón Social:</span></label>
                                <div class="col-xs-9"><input name="Prs_Ape" id="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                            </div>
                            <div class="form-group natural">
                                <label class="col-xs-3 control-label label-xs">Nombres:</label>
                                <div class="col-xs-9"><input name="Prs_Nom" id="Prs_Nom" type="text" class="form-control input-xs" /></div>
                            </div>
                            <div class="form-group natural">
                                <label class="col-xs-3 control-label label-xs ">Genero:</label>
                                <div class="col-xs-4">
                                    <select name="Prs_Sex" id="Prs_Sex" class="form-control input-xs ">
                                        <option value="M">MASCULINO</option>
                                        <option value="F">FEMENINO</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group juridico">
                                <label class="col-xs-3 control-label label-xs ">Nomb.Comerc.:</label>
                                <div class="col-xs-9"><input name="Prv_Com" id="Prv_Com" type="text" class="form-control input-xs" /></div>
                            </div-->









                        </fieldset>
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de Ubicación</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                                <div class="col-xs-4" id="Ciudad">
                                    <select name="Ciu_Cod" id="Ciu_Cod" class="form-control input-xs">
                                        <?php $rs_ciudad = $obBD_con1->getArrayConsulta(2, '', $obBD_conexion); ?>
                                        <option value=""></option>
                                        <?php
                                        foreach ($rs_ciudad as $row) {
                                            echo "<option value='$row[Ciu_Cod]' data-prov='" . utf8($row['Pro_Nom']) . "' data-pai='" . utf8($row['Pas_Nom']) . "'> " . utf8($row['Ciu_Des']) . " </option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Dirección:</label>
                                <div class="col-xs-9"><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs ">Teléfono:</label>
                                <div class="col-xs-4"><input name="Prv_Tel" type="text" class="form-control input-xs" pattern="\d*" /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs ">Mail:</label>
                                <div class="col-xs-5"><input id="Prv_Cor" name="Prv_Cor" type="email" class="form-control input-xs" multiple required="" /></div>
                            </div>
                        </fieldset>

                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de Autorizaci&oacute;n</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs ">Sustento:</label>
                                <div class="col-xs-10">
                                    <?php $rs_sustento = $obBD_con1->getArrayConsulta('sustento.selectWhere', array('clean' => true, 'where' => array('Tri_Est' => 'A')), $obBD_conexion); ?>
                                    <select name="Tri_Cod" class="form-control input-xs" tabindex="3">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($rs_sustento as $row) {
                                            $row = array_map('utf8_encode', $row); // Convert each element to UTF-8
                                            echo "<option value='{$row['Tri_Cod']}' " . ($row['Tri_Cod'] == 2 ? 'selected' : '') . ">$row[Tri_Sri] - $row[Tri_Des]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs ">Documento:</label>
                                <div class="col-xs-5">
                                    <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs" tabindex="4" data-trigger="">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($rs_tip_compr as $row) {
                                            if ($row['Tic_Sri'] != 4 && $row['Tic_Sri'] != 5 && $row['Tic_Sri'] != 7 && $row['Tic_Sri'] != 23 && $row['Tic_Sri'] != 24)
                                            $row = array_map('utf8_encode', $row); // Convert each element to UTF-8
                                                echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                        } ?>
                                    </select>
                                </div>

                                <label class="col-xs-2 control-label label-xs ">Impresión:</label>
                                <div class="col-xs-3">
                                    <div class="input-group">
                                        <input id="Prd_Imp" name="Prd_Imp" type="text" class="form-control input-xs datepickers empty" tabindex="9" />
                                        <span class="input-group-addon input-xs" title="Fecha de Creación en Imprenta"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                    </div>
                                </div>

                            </div>

                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs ">Autoriza:</label>
                                <div class="col-xs-5">
                                    <div class="input-group input-group-xs">
                                        <input id="Prd_Aut" type="text" name="Prd_Aut" class="form-control datatitle datatrigger" tabindex="6" maxlength="49" pattern="\d{10}|\d{37}|\d{49}" />
                                        <span class="input-group-addon validate"><i></i></span>
                                    </div>
                                </div>

                                <label class="col-xs-2 control-label label-xs ">Caducidad:</label>
                                <div class="col-xs-3">
                                    <div class="input-group">
                                        <input id="Prd_Cad" name="Prd_Cad" type="text" class="form-control input-xs datepickers" tabindex="10" />
                                        <span class="input-group-addon input-xs" title="Fecha de Caducidad en el SRI"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs ">Ciudad:</label>
                                <div class="col-xs-5">
                                    <?php $rs_ciudad = $obBD_con1->getArrayConsulta('ciudad.selectWhere', array('clean' => true, 'join' => array('provincia' => array('on' => 'provincia.Pro_Cod=ciudad.Pro_Cod', 'cols' => 'Pro_Nom')), 'where' => "Ciu_Des != ''", 'order' => 'Ciu_Des'), $obBD_conexion); ?>
                                    <select name="Ciu_Cod_Aut" id="Ciu_Cod_Aut" class="form-control input-xs" data-placeholder="Seleccione..." tabIndex="7">
                                        <option value=""></option>
                                        <?php foreach ($rs_ciudad as $row) {
                                            $row = array_map('utf8_encode', $row); // Convert each element to UTF-8
                                            echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Codigo Renta e Iva</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs ">Renta:</label>
                                <div class="col-xs-3 input-group input-group-xs ret"><input class="form-control" type="text" id="codRenta" name="Ren_Cod_Ren" value="" readonly="true"><span class="input-group-btn" data-originaldata='{"tipo":"R","index":"1","op_opciones":"p","checkRentaIva":"N"}'><button type="button" onclick="seleccionaRetencion($(this).parent().data('originaldata'));" class="btn btn-info" title="Agregar Imp. a la Renta" tabindex="-1"><i class="glyphicon glyphicon-plus"></i></button></span></div>
                                <br />
                                <label class="col-xs-2 control-label label-xs ">IVA:</label>
                                <div class="col-xs-3 input-group input-group-xs ret"><input class="form-control" type="text" id="codIva" name="Ren_Cod_Iva" value="" readonly="true"><span class="input-group-btn" data-originaldata='{"tipo":"I","index":"1","op_opciones":"p","checkRentaIva":"N"}'><button type="button" onclick="seleccionaRetencion($(this).parent().data('originaldata'));" class="btn btn-info" title="Agregar Ret. del Iva" tabindex="-1"><i class="glyphicon glyphicon-plus"></i></button></span></div>
                            </div>
                        </fieldset>

                        <div class="center">
                            <button type="button" onclick="$('#modificar').moveComp('#lista').updateGridsSizes();" class="btn btn-inverse fileinput-button btn-sm"><span class="fa fa-times"></span> Cancelar </button>
                            <button type="button" onclick="$(this.form).formSubmit();" class="btn btn-sm btn-primary no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>

                        <div class="Titulos2">
                            <hr><b>NOTA:</b> Los campos marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
    <div id="cuenDialog" title="B&uacute;squeda de Codigos de Retencion">
        <form class="form-horizontal normal">
            <fieldset>
                <legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>
                    <div class="col-md-7 radioset">
                        <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                        <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>
                        <input id="radc3" name="op_opciones" type="radio" value="p" onclick="setfocus(this.form.search)" alt="" /><label for="radc3">&nbsp;&nbsp;Porcentaje&nbsp;&nbsp;</label>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="tipo" class="hidden" />
                        <input type="text" name="index" class="hidden" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7">
                        <div class="input-group">
                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus class="form-control input-sm " />
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                        </div><!-- /input-group -->
                    </div>
                </div>
            </fieldset>
        </form>
    </div>


    <script>
        function inicio() {
            $('#Prd_Imp').createDatePickers();
            $('#Prd_Cad').createDatePickers();
        }

        window.onload = inicio;

        $.createSearchDialog('cuenDialog', [{
                label: 'C&oacute;d.Int.',
                name: 'Ren_Cod',
                key: true,
                width: 25,
                align: "center"
            },
            {
                label: 'C&oacute;digo',
                name: 'Ren_Sri',
                width: 25,
                align: "center"
            },
            {
                label: 'Descripci&oacute;n',
                name: 'Ren_Con',
                width: 100
            },
            {
                label: 'Porc.(%)',
                name: 'Ren_Por',
                width: 25,
                align: "center"
            },
            {
                label: 'Adq.',
                name: 'Ren_Tipo',
                width: 30,
                align: "center"
            },
            {
                label: 'Tipo',
                name: 'Ren_Rete',
                width: 30,
                align: "center"
            },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                name: 'act1',
                width: 30,
                align: 'center',
                viewable: false,
                formatter: function(cellvalue, options, rowObject) {
                    return $.getGridButton(addCodigo, rowObject, 'Agregar Codigo');
                }
            }
        ]);

        function seleccionaRetencion(data) {
            $('#cuenDialog').dialog('open');
            $('#cuenForm').setData(data).formSubmit();
        }

        function addCodigo(codigo) {
            if (codigo.Ren_Rete == "RENTA") {
                $("#codRenta").val(codigo.Ren_Cod);
            } else {
                $("#codIva").val(codigo.Ren_Cod);
            }
            $('#cuenDialog').dialog('close');
        }
    </script>

    <script type="text/javascript">
        var err = 1;
        $(function() {
            $('#Ciu_Cod').createChosen('input-xs', {
                tabIndex: 6,
                width: '100%',
                template: function(t, d) {
                    return '<div class="over"><b>' + t + '</b></div><div class="over desc" style="font-size:11px;"><b>Provincia:</b> ' + d['prov'] + ' <b>Pa&iacute;s:</b> ' + d['pai'] + '</div>';
                }
            });
        });
        $('#Ciu_Cod').val(217);



        function searchProveedor(ced, tipo) {

            ced_aux = ced;
            (tipo === 'ec') ? ced = ced.substring(0, 10): ced;
            $.post("", {
                    searchProveedor: true,
                    Prs_Ced: ced
                },
                function(response) {
                    err = 0;
                    if (response['exisPer'] === true) {
                        if (response['exisProv'] === true) {
                            err = 1;
                            $('#Ide_Cod').val('');
                            $('#Prs_Ced').fieldValid(false, 'Existe un Proveedor Registrado con esa Identificacion');
                            $.alert('La Identificacion esta siendo Utilizada por otro Proveedor..!!', function() {
                                $('#Prs_Ced').focus();
                            }, 'warning-sign');
                            clear();
                        } else {
                            err = 0;
                            response['Prs_Ced'] = ced_aux;
                            response['Ide_Cod'] = (ced_aux.length === 10 ? 2 : (ced_aux.length === 13 ? 1 : 0));
                            $('#Prv_Tic').val(ValidacionCedulaRucService.esIdentificacionValida(ced_aux)['tipo_abrev'] === 'NA' ? 'N' : 'J').trigger('change');
                            $('#formProvedor').setData(response);
                            $('#Prv_Cor').val(response['Prs_Cor']);

                        }
                    }
                }, 'json').fail(function() {
                $.alert();
            });

        }

        function GuardarProveedor() {

            if ($('#Ciu_Cod').val() !== '') {
                $.createDialogConfirm('Desea registrar un nuevo Proveedor!!', null,
                    function() {
                        if ($('#Prv_Tic').val() === 'J') {
                            $('#Prs_Sex').val('');
                            $('#Prs_Nom').val('');
                        } else {
                            $('#Prv_Com').val('');
                        }
                        var texto = $("#Ide_Cod option:selected").text();
                        if (texto == 'Seleccionar') {
                            $.alert('Debe ingresar un n&uacute;mero de identificaci&oacute;n v&aacute;lido');
                            return false;
                        }

                        // bloque de validaciones recurrentes
                        // var isValid = true;
                        // var mensaje = '';

                        // function validateField(selector, condition, errorMsg) {
                        //     if (condition) {
                        //         isValid = false;
                        //         $(selector).fieldValid(false).css("border-color", "red");
                        //         if ($(selector).next(".error-message").length === 0) {
                        //             $(selector).after('<span class="error-message" style="color: red; font-size: 10px;">' + errorMsg + "</span>");
                        //         }
                        //     } else {
                        //         $(selector).fieldValid(true).css("border-color", "").next(".error-message").remove();
                        //     }
                        // }

                        // if ($('#Prv_Tic').prop('selectedIndex') === 0) {
                        //     var apellido = $('#formProvedor input[name="Prs_Ape"]').val();
                        //     validateField('input[name="Prs_Ape"]', $.trim(apellido) !== apellido || apellido.charAt(0) === "." || !/^[a-zA-Z\s]+$/.test(apellido), "Campo inválido");

                        //     var nombre = $('#formProvedor input[name="Prs_Nom"]').val();
                        //     validateField('input[name="Prs_Nom"]', $.trim(nombre) !== nombre || nombre.charAt(0) === "." || !/^[a-zA-Z\s]+$/.test(nombre), "Campo inválido");
                        // } else if ($('#Prv_Tic').prop('selectedIndex') === 1) {
                        //     var apellido = $('#formProvedor input[name="Prs_Ape"]').val();
                        //     validateField('input[name="Prs_Ape"]', $.trim(apellido) !== apellido || apellido.charAt(0) === "." || !/^[a-zA-Z\s]+$/.test(apellido), "Campo inválido");

                        //     var nombcom = $('#formProvedor input[name="Prv_Com"]').val();
                        //     validateField('input[name="Prv_Com"]', $.trim(nombcom) !== '' && ($.trim(nombcom) !== nombcom || nombcom.charAt(0) === '.' || nombcom.charAt(nombcom.length - 1) === '.' || !/^[a-zA-Z0-9\s]+$/.test(nombcom)), "Campo inválido");
                        // }

                        // var direccion = $('#formProvedor input[name="Prs_Dir"]').val();
                        // validateField('input[name="Prs_Dir"]', $.trim(direccion) !== direccion || direccion.charAt(0) === "." || direccion.charAt(direccion.length - 1) === "." || !/^[a-zA-Z0-9\s\/]+$/.test(direccion), "Campo inválido");

                        // var telefono = $('#formProvedor input[name="Prv_Tel"]').val();
                        // validateField('input[name="Prv_Tel"]', !/^\d*$/.test(telefono), "Solo debe contener números");

                        // var email = $('#formProvedor input[name="Prv_Cor"]').val();
                        // if ($.trim(email) !== '') {
                        //     validateField('input[name="Prv_Cor"]', !/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email), "Formato de correo electrónico no válido");
                        // }

                        // if (!isValid) {
                        //     return false;
                        // }

                        $.saveDataJson("", $('#formProvedor').getData('guardarProvAjax'), function(resp) {
                            $('#Lis_Proveedor').trigger('reloadGrid');
                            clear();
                            $('#Prs_Ced').focus();

                        });
                    },
                    function() {
                        $('#Prs_Ced').focus();

                    });
            } else {

            }
        }

        function clear() {
            $('#formProvedor').setData({
                Cli_Tic: 'N',
                Prs_Ciu: 'Ec',
                Prs_Sex: 'M'
            });
            $('.juridico').hide();
            $('.natural').show();
            $('#Prs_Ced').fieldValid();
        }

        function saveItem() {
            var data = {
                guardarVIProveedor: true,
                Prs_Cod: $('#btnCVI').data("persona")[0]['Prs_Cod']
            };
            $.createDialogConfirm('¿Esta seguro que desea <b class="green">GUARDAR</b>?', data, function() {
                $.saveDataJson('', data, function(resp) {
                    $('#btnCVI').hide();
                });
            });
        }


        // Funcion para activar de cédula a RUC
        function setTipoDoc() {
            var $Prs_Ced = $('#Prs_Ced'),
                Prs_Ced = $Prs_Ced.val(),
                isRuc = $('#isRuc').is(':checked');

            if (Prs_Ced.length >= 10 && $.isNum(Prs_Ced)) {
                Prs_Ced = Prs_Ced.substring(0, 10);
                $Prs_Ced.val(isRuc ? Prs_Ced + '001' : Prs_Ced);
                $Prs_Ced.trigger('change');
            } else {
                $.alert("El numero " + Prs_Ced + " no puede convertirse en RUC!");
            }
        }

         //Solo se puede seleccionar RIMPE Emprendedor o Negocio popular
         function toggleCheckbox(otherCheckboxName) {
            const otherCheckbox = document.querySelector(`input[name="${otherCheckboxName}"]`);
            otherCheckbox.checked = false;
        }
    </script>
</BODY>

</html>