<?php

/**
 * @abstract Permite realizar guias de remision
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2015-07-22
 */
/**
 * Actualizado por Wilson Belduma
 * Fecha de actualizacion 2024-05-17
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_guia_remi.php');
// V2.5 - FIX DERECHOS MINEROS AJAX - 2026-02-06
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_G_Remi($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_G_Remi;
$hoy = date("Y-m-d");
$mes = date("m");
if (isset($Doc_Cod)) {
    require_once('../LOGICA/fac_log_electronica.php');
    $obBD_elect =  new Class_Log_Datos_Guia_Elect;
    $obBD_elect->createPdfGuiaRemision($Doc_Cod, $obBD_conexion);
}
/* busqueda de transportistas / destinatarios */
if (isset($transAjax) || isset($destiAjax)) {
    $obBD_con1->getPageGridJson(3, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*' . (isset($transAjax) ? 'T' : 'D'), $obBD_conexion, $page, $rows);
}
/* Consulta del tipo de productos */
if (isset($proAjax)) {
    $obBD_con1->getPageGridJson(4, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*' . $Ses_Suc_Cod, $obBD_conexion, $page, $rows);
}
/* Consulta de facturas de venta */
if (isset($vetAjax)) {
    $obBD_con1->getPageGridJson(7, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
}
/* ver si exite un transportista/destinatario */
if (isset($provAjax2)) {
    $pers = $obBD_con1->getArrayConsulta(14, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $Gpe_Tip, $obBD_conexion);
    $responce = array('rows' => null, 'total' => 0);
    if (count($pers) >= 1) {
        $per = array(0 => $pers[0]);
        foreach ($pers as $p) {
            if ($p['Emp_Cod'] * 1 == $Ses_Emp_Cod * 1) {
                $per[0] = $p;
                break;
            }
        }
        $responce['rows'] = $per;
        $responce['total'] = count($per);
    }
    $obBD_con1->echoJson($responce);
}
/* fuarda un nuevo proveedor */
if (isset($guardaProvAjax)) {
    $data = $_POST;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    if (empty($Prs_Cod)) {
        $obBD_con1->operacionobBD(15, $data, $obBD_conexion);
        $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    }
    $obBD_con1->operacionobBD(8, $data, $obBD_conexion);
    $data['Gpe_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    $data[$Gpe_Tip == 'T' ? 'transportista' : 'destinatario'] = (empty($data['Gpe_Ras']) ? trim($data['Prs_Ape'] . ' ' . $data['Prs_Nom']) : $data['Gpe_Ras']);

    if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
        $responce = array('success' => true, 'gpe' => $data);
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', 'error' => $obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($responce);
}

//Editar un proveedor 
if (isset($editarProvAjax)) {
    $data = $_POST;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    if (!empty($Prs_Cod)) { //regresa true   caso contrariio false
        $obBD_con1->operacionobBD(23, $data, $obBD_conexion);
        $data['Prs_Cod'] = $Prs_Cod;
    }
    $obBD_con1->operacionobBD(24, $data, $obBD_conexion);
    $data['Gpe_Cod'] =  $data['Gpe_Cod']; //   $obBD_con1->insercionid($obBD_conexion);
    $data[$Gpe_Tip == 'T' ? 'transportista' : 'destinatario'] = (empty($data['Gpe_Ras']) ? trim($data['Prs_Ape'] . ' ' . $data['Prs_Nom']) : $data['Gpe_Ras']);
    if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
        $responce = array('success' => true, 'gpe' => $data);
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', 'error' => $obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($responce);
}

// =====================================================
// AJAX: Guardar o modificar derecho minero
// =====================================================
if (isset($guardarDerechoMineroAjax)) {
    $resp = array('success' => false);
    $accion = isset($_POST['accion']) ? $_POST['accion'] : 'registrar';
    
    try {
        $obBD_con1->inicio_transaccion($obBD_conexion);
        
        if ($accion == 'modificar' && !empty($_POST['Der_Min_Id'])) {
            // Actualizar derecho minero existente - case 1279
            $params = array(
                $_POST['Der_Min_Codigo'],
                $_POST['Der_Min_Nombre'],
                $_POST['Der_Min_Titular_Operador'],
                $_POST['Der_Min_Tipo'],
                isset($_POST['Der_Min_Ubicacion']) ? $_POST['Der_Min_Ubicacion'] : '',
                isset($_POST['Der_Min_Observaciones']) ? $_POST['Der_Min_Observaciones'] : '',
                date('Y-m-d H:i:s'), // Fecha modificación
                $_POST['Der_Min_Id'], // WHERE Der_Min_Id
                isset($_POST['Der_Min_Recurso']) ? $_POST['Der_Min_Recurso'] : ''
            );
            $obBD_con1->operacionobBD(1279, $params, $obBD_conexion);
            $Der_Min_Id = $_POST['Der_Min_Id'];
            $resp['message'] = 'Derecho minero actualizado exitosamente';
        } else {
            // Insertar nuevo derecho minero - case 1278
            $params = array(
                $_POST['Der_Min_Codigo'],
                $_POST['Der_Min_Nombre'],
                $_POST['Der_Min_Titular_Operador'],
                $_POST['Der_Min_Tipo'],
                isset($_POST['Der_Min_Ubicacion']) ? $_POST['Der_Min_Ubicacion'] : '',
                isset($_POST['Der_Min_Observaciones']) ? $_POST['Der_Min_Observaciones'] : '',
                'A', // Estado
                date('Y-m-d H:i:s'), // Fecha registro
                isset($_POST['Der_Min_Recurso']) ? $_POST['Der_Min_Recurso'] : ''
            );
            $obBD_con1->operacionobBD(1278, $params, $obBD_conexion);
            $Der_Min_Id = $obBD_con1->insercionid($obBD_conexion);
            $resp['message'] = 'Derecho minero registrado exitosamente';
        }
        
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
            $resp['success'] = true;
            $resp['Der_Min_Id'] = $Der_Min_Id;
        } else {
            $resp['success'] = false;
            $resp['message'] = 'Error al guardar el derecho minero';
            $resp['error'] = $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($resp);
    exit;
}


// =====================================================
// AJAX: Obtener lista de derechos mineros
// =====================================================
if (isset($obtenerDerechosMineros)) {
    error_log("DEBUG: obtenerDerechosMineros AJAX called");
    $resp = array('success' => false, 'debug' => array());
    
    try {
        $resp['debug'][] = "Base de datos: " . (isset($Ses_Dat_Dis) ? $Ses_Dat_Dis : 'NO DEFINIDA');
        $resp['debug'][] = "Intentando ejecutar operacionobBD(1277)";
        
        $result = $obBD_con1->operacionobBD(1277, array(), $obBD_conexion);
        $derechos = array();
        
        $resp['debug'][] = "Resultado de operacionobBD: " . ($result ? "SI" : "NO");
        
        if ($result) {
            $count = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $derechos[] = $row;
                $count++;
            }
            $resp['debug'][] = "Registros encontrados: " . $count;
            $resp['success'] = true;
            $resp['derechos'] = $derechos;
            $resp['message'] = "Se cargaron $count derechos mineros";
        } else {
            $resp['success'] = false;
            $resp['message'] = 'operacionobBD retornó false';
            $resp['derechos'] = array();
            $resp['debug'][] = "Error en operacionobBD";
        }
    } catch (Exception $e) {
        $resp['success'] = false;
        $resp['message'] = 'Error: ' . $e->getMessage();
        $resp['derechos'] = array();
        $resp['debug'][] = "Exception: " . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($resp);
    exit;
}


if (isset($validaVetNum)) {
    $Gui_Cod = isset($Gui_Cod) ? $Gui_Cod : '';
    $electronica = ($Aut_Tem == 'E');
    $row_max_codig = $obBD_con1->getRowConsulta(5, $_GET, $obBD_conexion); //Consulta el maximo numero de retenciones en base a la autorizacion    
    $Ret_Id_Man = ($row_max_codig['next']);
    $resp = array_merge(array('success' => true, 'Vet_Num' => $Ret_Id_Man, 'Vet_Num_Old' => $Vet_Num, 'Vet_Cod' => $Gui_Cod, 'Aut_Ini' => $Aut_Ini, 'Aut_Fin' => $Aut_Fin));
    if (!empty($Vet_Num)) {
        $num_existe_gencod = $obBD_con1->getRowConsulta(6, $Suc_Sri . '*' . $Aut_Sri . '*' . $Vet_Num . '*' . $Gui_Cod . '*' . $Pun_Sri, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
        if ($num_existe_gencod['total'] * 1 > 0) {
            $resp['success'] = false;
            $resp['message'] = "El documento número $Vet_Num ya Existe en el Sistema!";
        }
    } else $resp['success'] = false;
    $obBD_con1->echoJson($resp);
}

$error = 0;
$vend = $obBD_con1->getRowConsulta(1, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
if (empty($vend['Vnd_Cod'])) $error++;
else {
    $autori = $obBD_con1->getRowConsulta(2, $vend['Vnd_Cod'], $obBD_conexion);
    if (empty($autori['Aut_Cod'])) $error++;
}

if (isset($saveDocument)) {
    $resp = array('success' => false);
    /* Que sea vendedor */
    if (empty($vend['Vnd_Cod'])) {
        $resp['message'] = "No tiene permisos de Vendedor!";
    }
    $Vnd_Cod = $vend['Vnd_Cod'];
    /* valida que no exista el documento */
    $num_existe_gencod = $obBD_con1->getRowConsulta(6, $Suc_Sri . '*' . $Aut_Sri . '*' . $Gui_Num . '*' . $Gui_Cod . '*' . $Pun_Sri, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
    if ($num_existe_gencod['total'] * 1 > 0) {
        $resp['message'] = "La guia de remision No. $Gui_Num ya existe!";
    }
    if (!empty($resp['message'])) {
        echo json_encode($resp);
        exit();
    }
    $obBD_ins1 =  new Class_Log_Datos_G_Remi;
    //$obBD_ins1->debug(true);
    $obBD_conexionIns = new Class_Log_Conexion_G_Remi($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {
        if ($Aut_Tem == 'E') { // documento electronico
            require_once('../LOGICA/fac_log_electronica.php');
            $obBD_elect =  new Class_Log_Datos_Guia_Elect;
            $claveAcceso = $obBD_elect->getClaveAcceso($Aut_Cod, $Gui_Fec, $Gui_Num, $obBD_conexion);
            $_POST['Gui_Xml'] = $claveAcceso;
        }
        /* Cabecera de la guia */
        $obBD_ins1->operacionobBD(10, $_POST, $obBD_conexionIns);
        $Gui_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
        foreach ($destinos as $i => $dest) {
            $g = array('Gui_Cod' => $Gui_Cod, 'Gui_Int' => ($i + 1));
            /* guarda destinatario */
            $obBD_ins1->operacionobBD(11, array_merge($dest, $g), $obBD_conexionIns);
            foreach ($dest['items'] as $j => $item) {
                $item['Gde_Int'] = ($j + 1);
                /* guarda detalle */
                $obBD_ins1->operacionobBD(12, array_merge($item, $g), $obBD_conexionIns);
            }
        }
    } catch (Exception $ex) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $e->getMessage();
        echo json_encode($resp);
        exit();
    }
    if ($obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns)) {
        $resp = array('success' => true, 'Gui_Cod' => $Gui_Cod);
        if ($Aut_Tem == 'E') { // documento electronico
            $resp['xml'] = $obBD_elect->createXmlGuiaRemision($Gui_Cod, $Aut_Cod, $claveAcceso, $obBD_conexion);
            $resp['pdf'] = '../COMPONENTES/tesPdfElectronicos.php?clave=' . $claveAcceso;
        } else {
            $resp['imprimir'] = $obBD_con1->reportesExa($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        }
    } else {
        $resp = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_ins1->MsgError);
    }
    $obBD_con1->echoJson($resp);
} ?>

<!DOCTYPE html>
<HTML>

<head>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Guias Registrar [EXA]"; ?></TITLE>
    <meta charset="utf-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript">
        var Aut_Tem = '<?php echo $autori['Aut_Tem'] == 'E' ? 'E' : 'N'; ?>',
            registrar = true;
    </script>
    <script type="text/javascript" src="../VALIDACIONES/fac_val_guia_remi_2.0.js?x=2"></script>
</head>

<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar Guias de Remision <span class="pull-right"><?php echo isset($vend['Pun_Des']) ? $vend['Pun_Des'] : ''; ?></span></h3>
        </div>
        <?php if ($error > 0) { ?>
            <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/bootstrap/info.tabs.css" />
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body vcenter center" style="height: 250px;">
                <?php
                if (empty($vend['Vnd_Cod'])) echo error_alerta(" No posee permisos de VENDEDOR o no posee un PUNTO DE IMPRESION activo!", 2, true, 'VENDEDORES');
                else if (empty($autori['Aut_Cod'])) echo error_alerta(" No existen autorizaciones para GU&Iacute;AS DE REMISI&Oacute;N otorgadas por SRI, activas", 2, true, 'AUTORIZACIONES');
                ?>
            </div>
        <?php } else { ?>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body" style="min-height: 350px;">
                <div id="documentoMain">
                    <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">
                        <input name="Gui_Cod" type="text" style="display:none;" />
                        <div class="row">
                            <div class="col-xs-6">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Datos del Transportista</legend>
                                    <div class="form-group trasportista">
                                        <label class="col-xs-3 control-label label-xs">Cédula/RUC:</label>
                                        <div class="col-xs-6" id="transFormTemp">
                                            <input name="Prs_Cod" type="text" data-trans="Prs_Cod" style="display:none;" />
                                            <input name="Gpe_Cod" type="text" data-trans="Gpe_Cod" style="display:none;" />
                                            <input name="op_opciones" type="text" value="c" style="display: none;">
                                            <div class="input-group input-group-xs">
                                                <input name="Prs_Ced" data-trans="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#transDialog',selectTrans); }" type="text" placeholder="Ingrese Trasportista..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                                <span class="input-group-btn">
                                                    <button id="Tra_Btn" type="button" onclick="$('#transDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Transportista" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                    <button type="button" onclick="$('#guiaPersonaDialog').setData({Gpe_Tip:'T'}).find('.validate').find('i').removeAttr('class'); $('#guiaPersonaDialog').dialog('open'); $('.search_dest').hide(); $('.search_trans').show();" class="btn btn-success btn-xs" title="Registrar Transportista" tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>
                                                    <button type="button" onclick="$('#guiaPersonaDialogEdit').setData({Gpe_Tip:'T'}).find('.validate').find('i').removeAttr('class'); $('#guiaPersonaDialogEdit').dialog('open'); $('.search_dest').hide(); $('.search_trans').show(); " class="btn btn-success btn-xs" title="Editar Transportista" tabindex="2"><span class="glyphicon glyphicon-pencil"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group trasportista">
                                        <label class="col-xs-3 control-label label-xs required">R.Social:</label>
                                        <div class="col-xs-9">
                                            <span name="transportista" data-trans="transportista" class="form-control input-xs databind datatitle"></span>
                                        </div>
                                    </div>
                                    <div class="form-group trasportista">
                                        <label class="col-xs-3 control-label label-xs required">Placa:</label>
                                        <div class="col-xs-4">
                                            <input type="text" name="Gui_Pla" data-trans="Gpe_Pla" class="form-control input-xs" maxlength="10" required="">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Fechas:</label>
                                        <div class="col-xs-9">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon bold required">Salida:</span>
                                                <input name="Gui_Fei" id="Gui_Fei" type="text" class="form-control span" style="text-align: center;" tabindex="-1" value="<?php echo $hoy; ?>" required="">
                                                <span class="input-group-addon bold required">Arrivo:</span>
                                                <input name="Gui_Fef" id="Gui_Fef" type="text" class="form-control span" style="text-align: center;" tabindex="-1" value="<?php echo $hoy; ?>" required="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Dir. Salida:</label>
                                        <div class="col-xs-9">
                                            <textarea name="Gui_Dor" class="form-control input-xs" required=""></textarea>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-xs-6">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Datos de la Guia de Remisión</legend>
                                    <input name="Aut_Cod" type="text" data-trans="Prs_Cod" style="display:none;" value="<?php echo $autori['Aut_Cod']; ?>" />
                                    <input name="Aut_Tem" type="text" data-trans="Prs_Cod" style="display:none;" value="<?php echo $autori['Aut_Tem']; ?>" />
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Documento:</label>
                                        <div class="col-xs-6">
                                            <select id="Tic_Cod_Guia" name="Tic_Cod" class="form-control input-xs readOnly getData ins" disabled="">
                                                <?php echo mb_convert_encoding("<option value='$autori[Tic_Cod]' selected='' data--tic_-cod='$autori[Tic_Cod]' data--aut_-fci='$autori[Aut_Fci]' data--aut_-cad='$autori[Aut_Cad]' data--suc_-sri='$autori[Suc_Sri]' data--pun_-sri='$autori[Pun_Sri]' data--aut_-sri='$autori[Aut_Sri]' data--aut_-ini='$autori[Aut_Ini]' data--aut_-fin='$autori[Aut_Fin]' data--aut_-tem='$autori[Aut_Tem]' selected=''>     $autori[Tic_Des]   </option>", 'UTF-8', 'ISO-8859-1');  ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Numero:</label>
                                        <div class="col-xs-4">
                                            <div class="input-group input-group-xs">
                                                <span id="Pun_Num" class="input-group-addon alert-info"><?php echo $autori['Suc_Sri'] . '-' . $autori['Pun_Sri']; ?></span>
                                                <input type="text" id="Gui_Num" name="Gui_Num" onchange="validaVetNum()" class="form-control input-xs secuencia" tabindex="5" required="" style="text-align: right;">
                                                <span class="input-group-addon validate"><i class="glyphicon glyphicon-ok green"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Fec. Emision:</label>
                                        <div class="col-xs-4">
                                            <input type="text" name="Gui_Fec" id="Gui_Fec" class="form-control input-xs" readonly="" value="<?php echo $hoy; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Autorizacion:</label>
                                        <div class="col-xs-6">
                                            <span type="text" class="form-control input-xs"><?php echo $autori['Aut_Tem'] == 'N' ? $autori['Aut_Sri'] : 'ELECTRONICA'; ?></span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Observacion:</label>
                                        <div class="col-xs-9">
                                            <textarea name="Gui_Obs" class="form-control input-xs"></textarea>
                                        </div>
                                    </div>
                                    <!-- SELECTOR DE DERECHO MINERO -->
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Derecho Minero:</label>
                                        <div class="col-xs-9">
                                            <select name="Der_Min_Id" id="Der_Min_Id" class="form-control input-xs" onchange="actualizarInfoDerecho(this)">
                                                <option value="">Cargando derechos mineros...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Botones de gestión -->
                                    <div class="form-group">
                                        <div class="col-xs-offset-3 col-xs-9">
                                            <button type="button" onclick="$('#derechoMineroDialog').dialog('open'); limpiarFormDerechoMinero();" class="btn btn-success btn-xs" title="Registrar Nuevo Derecho Minero">
                                                <span class="glyphicon glyphicon-plus"></span> Nuevo
                                            </button>
                                            <button type="button" onclick="editarDerechoMinero();" class="btn btn-warning btn-xs" title="Editar Derecho Minero Seleccionado" id="btnEditarDerecho">
                                                <span class="glyphicon glyphicon-pencil"></span> Editar
                                            </button>
                                            <small class="help-block" style="margin-top: 5px; margin-bottom: 0; color: #666;">
                                                <i class="glyphicon glyphicon-info-sign"></i> 
                                                Al seleccionar un derecho minero, su información se agregará automáticamente a las observaciones
                                            </small>
                                        </div>
                                    </div>
                                    <!-- FIN SELECTOR DE DERECHO MINERO -->
                                    
                                    <script type="text/javascript">
                                    function actualizarInfoDerecho(select) {
                                        var selectedOption = select.options[select.selectedIndex];
                                        var obsField = $('textarea[name="Gui_Obs"]');
                                        var currentObs = obsField.val() || '';
                                        
                                        // Prefijo para identificar y reemplazar la información
                                        var prefix = "DERECHO MINERO:";
                                        
                                        if (select.value && selectedOption) {
                                            var codigo = selectedOption.getAttribute('data-codigo') || '';
                                            var nombre = selectedOption.getAttribute('data-nombre') || '';
                                            var titular = selectedOption.getAttribute('data-titular') || '';
                                            var tipo = selectedOption.getAttribute('data-tipo');
                                            
                                            // Limpieza de nulos o undefined
                                            if (tipo === 'null' || !tipo || tipo === 'undefined') {
                                                tipo = ''; 
                                            }
                                            
                                            var infoLine = prefix + " " + codigo + " - " + nombre;
                                            if (titular) infoLine += " (" + titular + ")";
                                            if (tipo) infoLine += " - TIPO: " + tipo;
                                            
                                            // Lógica de reemplazo exacta
                                            var lines = currentObs.split("\n");
                                            var found = false;
                                            for (var i = 0; i < lines.length; i++) {
                                                if (lines[i].trim().indexOf(prefix) === 0) {
                                                    lines[i] = infoLine;
                                                    found = true;
                                                    break;
                                                }
                                            }
                                            
                                            if (!found) {
                                                if (currentObs.trim() !== "") {
                                                    lines.push(infoLine);
                                                } else {
                                                    lines = [infoLine];
                                                }
                                            }
                                            
                                            obsField.val(lines.join("\n"));
                                        } else {
                                            // Si se quita la selección, eliminar la línea correspondiente
                                            var lines = currentObs.split("\n");
                                            var newLines = [];
                                            for (var i = 0; i < lines.length; i++) {
                                                if (lines[i].trim().indexOf(prefix) !== 0) {
                                                    newLines.push(lines[i]);
                                                }
                                            }
                                            obsField.val(newLines.join("\n").trim());
                                        }
                                    }
                                    </script>
                                </fieldset>
                            </div>
                        </div>
                    </form>
                    <div class="row" id="panelDestinatario">
                        <div class="col-xs-12">
                            <form id="formDesti" class="form-horizontal normal formDatos" action="javascript:validaDestinatario();">
                                <input name="Gui_Index" type="text" style="display:none;" />
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Datos del Destinatario</legend>
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <div class="form-group destinatario">
                                                <label class="col-xs-3 control-label label-xs">Cédula/RUC:</label>
                                                <div class="col-xs-6" id="destiFormTemp">
                                                    <input name="Prs_Cod" type="text" data-desti="Prs_Cod" style="display:none;" />
                                                    <input name="Gpe_Cod" type="text" data-desti="Gpe_Cod" style="display:none;" />
                                                    <input name="op_opciones" type="text" value="c" style="display: none;">
                                                    <div class="input-group input-group-xs">
                                                        <input name="Prs_Ced" data-desti="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#destiDialog',selectDest); }" type="text" placeholder="Ingrese Destinatario..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                                        <span class="input-group-btn">
                                                            <button id="Des_Btn" type="button" onclick="$('#destiDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Destinatario" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                            <button type="button" onclick="$('#guiaPersonaDialog').setData({Gpe_Tip:'D'}).find('.validate').find('i').removeAttr('class'); $('#guiaPersonaDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Destinatario" tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>
                                                            <button type="button" onclick="$('#guiaPersonaDialogEdit').setData({Gpe_Tip:'D'}).find('.validate').find('i').removeAttr('class'); $('#guiaPersonaDialogEdit').dialog('open'); $('.search_dest').show(); $('.search_trans').hide(); " class="btn btn-success btn-xs" title="Editar Destinatario" tabindex="2"><span class="glyphicon glyphicon-pencil"></span></button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group destinatario">
                                                <label class="col-xs-3 control-label label-xs required">R.Social:</label>
                                                <div class="col-xs-9">
                                                    <span name="destinatario" data-desti="destinatario" class="form-control input-xs databind datatitle"></span>
                                                </div>
                                            </div>
                                            <div class="form-group destinatario">
                                                <label class="col-xs-3 control-label label-xs">Codigos:</label>
                                                <div class="col-xs-9">
                                                    <div class="input-group input-group-xs">
                                                        <span class="input-group-addon bold" title="Codigo Establecimiento">Establec.:</span>
                                                        <input name="Gui_Ces" data-desti="Gpe_Ces" type="text" class="form-control span" style="text-align: right;" tabindex="-1">
                                                        <span class="input-group-addon bold" title="Documento Aduanero">D. Aduane.:</span>
                                                        <input name="Gui_Dad" data-desti="Gpe_Dad" type="text" class="form-control span" style="text-align: right;" tabindex="-1">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group destinatario">
                                                <label class="col-xs-3 control-label label-xs required">Motivo:</label>
                                                <div class="col-xs-3">
                                                    <?php $motiv = $obBD_con1->getMotivos();  ?>
                                                    <select name="Mot_Aux" id="Mot_Aux" class="form-control input-xs" required="">
                                                        <option value="">Seleccione..</option>
                                                        <?php foreach ($motiv as $v) {
                                                            echo "<option value=\"$v\" >$v</option>";
                                                        } ?>
                                                    </select>
                                                </div>
                                                <div class="col-xs-6" style="display: none;">
                                                    <label class="control-label label-xs required">Descripcion Motivo:</label>
                                                    <textarea id="Gui_Mot" name="Gui_Mot" class="form-control input-xs"></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="col-xs-6">
                                                    <label class="control-label label-xs required">Dir. Destino:</label>
                                                    <textarea name="Gui_Dde" class="form-control input-xs" required=""></textarea>
                                                </div>

                                                <div class="col-xs-6">
                                                    <label class="control-label label-xs">Ruta:</label>
                                                    <textarea name="Gui_Rut" class="form-control input-xs"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <fieldset class="exa-fieldset venta">
                                                <legend class="Titulos2">Documento Respaldo</legend>
                                                <div class="form-group">
                                                    <label class="col-xs-3 control-label label-xs">Documento:</label>
                                                    <div class="col-xs-6">
                                                        <select name="Tic_Cod" data-venta="Tic_Cod" class="form-control input-xs readOnly getData ins" disabled="">
                                                            <option value="" selected="">Seleccione..</option>
                                                            <option value="1">FACTURA DE VENTA</option>
                                                            <option value="2">NOTA O BOLETA DE VENTA</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-xs-3 control-label label-xs">Numero:</label>
                                                    <div class="col-xs-4">
                                                        <input name="Vet_Cod" type="text" data-venta="Vet_Cod" style="display:none;" />
                                                        <div class="input-group input-group-xs">
                                                            <span name="Vet_Prefix" data-venta="Vet_Prefix" class="input-group-addon bold alert-info databind">000-000-</span>
                                                            <input name="Vet_Num" data-venta="Vet_Num" type="text" class="form-control span" style="text-align: center;" readonly="" tabindex="-1" value="">
                                                            <span class="input-group-btn">
                                                                <button id="Vet_Btn" type="button" onclick="$('#vetDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Documento" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <label class="col-xs-2 control-label label-xs">Fec.&nbsp;Emision:</label>
                                                    <div class="col-xs-3">
                                                        <span type="text" name="Caj_Fec" data-venta="Caj_Fec" class="form-control input-xs databind"></span>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-xs-3 control-label label-xs">Autorizacion:</label>
                                                    <div class="col-xs-9">
                                                        <span type="text" name="Aut_Sri" data-venta="Aut_Sri" class="form-control input-xs databind"></span>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-xs-3 control-label label-xs">Cliente:</label>
                                                    <div class="col-xs-9">
                                                        <span type="text" name="cliente" data-venta="cliente" class="form-control input-xs databind"></span>
                                                    </div>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-xs-12 center">
                                            <button id="btnAddDesti" type="button" onclick="$(this.form).formSubmit();" class="btn btn-info btn-sm" title="Registrar Destinatario"><i class="glyphicon glyphicon-plus"></i> Agregar Destino a la Guia</button>
                                            <button type="button" onclick="$('#panelDestinatario').hide(); $('#panelGuiaRemi').show().updateGridsSizes();" class="btn btn-danger btn-sm" title="Cancelar"><i class="glyphicon glyphicon-plus"></i> Cancelar</button>
                                        </div>
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                    <div class="row" id="panelGuiaRemi" style="display: none;">
                        <div class="col-xs-4">
                            <div style="min-height: 200px; padding: 5px 0;">
                                <table id="documentos"></table>
                                <div id="documentosPager"></div>
                            </div>
                        </div>
                        <div class="col-xs-8">
                            <div class="form-horizontal normal" id="formTempDestinat">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Destinatario</legend>
                                    <input type="text" id="Gui_Index" name="Gui_Index" style="display: none;" />
                                    <div class="form-group">
                                        <div class="col-xs-6">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon bold">Cédula:</span>
                                                <span name="Prs_Ced" class="form-control span"></span>
                                                <span class="input-group-addon bold">Destin.</span>
                                                <span name="destinatario" class="form-control span datatitle"></span>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon bold">Establecimiento:</span>
                                                <span name="Gui_Ces" class="form-control span"></span>
                                                <span class="input-group-addon bold">Cod. Aduanero</span>
                                                <span name="Gui_Dad" class="form-control span"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon bold">Destino:</span>
                                                <span name="Gui_Dde" class="form-control span datatitle"></span>
                                                <span class="input-group-addon bold">Motivo:</span>
                                                <span name="Gui_Mot" class="form-control span datatitle"></span>
                                                <span class="input-group-addon bold">Ruta:</span>
                                                <span name="Gui_Rut" class="form-control span datatitle"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon bold">Documen.:</span>
                                                <span name="Tic_Cod_Txt" class="form-control span"></span>
                                                <span class="input-group-addon bold">Numero:</span>
                                                <span name="documento" class="form-control span"></span>
                                                <span class="input-group-addon bold">Fecha:</span>
                                                <span name="Caj_Fec" class="form-control span"></span>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div id="itemsContainer">
                                <table id="items"></table>
                                <div id="itemsPager"></div>
                            </div>
                        </div>
                        <div class="col-xs-12">
                            <button class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                    </div>
                    <div class="col-sm-12 Titulos2">
                        <hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
                    </div>
                </div>
                <div id="documentoResult">
                </div>
            </div>
            <!-- Busqueda de Transportista -->
            <div id="transDialog" title="B&uacute;squeda de Transportista"></div>
            <!-- Busqueda de Destinatarios -->
            <div id="destiDialog" title="B&uacute;squeda de Destinatario"></div>
            <!-- Busqueda de Productos -->
            <div id="proDialog" title="B&uacute;squeda de Productos"></div>
            <!-- Busqueda de Facturas de Venta -->
            <div id="vetDialog" title="B&uacute;squeda de Facturas de Venta"></div>
            <!-- Dialogo guardado impresion -->
            <div id="successDialog" title="Mensaje del Sistema" style="display: none;">
                <center>
                    <b style="font-size:14px;">Se ha Guardado la Guia con Exito!</b>
                    <button id="btnImpGuia" type="button" class="btn btn-info bntSuccess" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Guia</button>
                    <button id="btnImpPdf" type="button" class="btn btn-info bntSuccess" onclick="window.open($(this).data('url'))"><i class="glyphicon glyphicon-file"></i> Visualizar PDF</button>
                </center>
            </div>
            <!-- Crear Trasportista/Destinatario -->
            <div id="guiaPersonaDialog" title="Registrar">
                <form class="form-horizontal normal" id="gpeCreateForm" action="javascript:if(validaNoIdentif($('#gpeCreateForm #Prs_Ced').val())['success']){ guardaProvee(); }else{ $('#gpeCreateForm #Prs_Ced').flyout('show').focus() }">
                    <input name="Prs_Cod" type="text" class="hidden" />
                    <input name="Gpe_Tip" id="Gpe_Tip" type="text" class="hidden datatrigger" onchange="$('.gpediv').hide(); if(this.value==='T'){ $('.gpeTipo').html('Transportista'); $('.transportista').show(); }else{ $('.gpeTipo').html('Destinatario'); $('.destinatario').show(); }" />
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos del <span class="gpeTipo">Trasnportista</span></legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                            <div class="col-xs-8">
                                <div class="input-group input-group-xs">
                                    <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="if ($('#isextrangero').is(':checked')) {   $(this).fieldValid(true);  $('#gpeCreateForm #Ide_Cod').val(7);   searchProvee(this.value);}else { if(validaNoIdentif(this.value)['success']){  $('#gpeCreateForm #Ide_Cod').val(this.value.length===10 ? 2:1); $('#Prv_Tic').val(validaNoIdentif(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchProvee(this.value); }else{ $('#gpeCreateForm #Ide_Cod').val(''); $('#Prv_Tic').val(''); $(this).fieldValid(false,validaNoIdentif(this.value)['message']); }};" required="" />
                                    <span class="input-group-addon validate"><i></i></span>
                                     <span class="input-group-addon">
                                        <input type="checkbox" id="isextrangero" name="isextrangero" value="1"> Extranjero
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Documento:</label>
                            <div class="col-xs-5">
                                <?php $rs_identi = $obBD_con1->getArrayConsulta(9, '', $obBD_conexion); ?>
                                <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                                    <option value=""></option>
                                    <?php foreach ($rs_identi as $row) {
                                        echo "<option value='$row[Ide_Cod]'>$row[Ide_Des]</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Razón Social:</span></label>
                            <div class="col-xs-9"><input name="Prs_Ape" type="text" class="form-control input-xs" /></div>
                        </div>
                        <div class="form-group natural">
                            <label class="col-xs-3 control-label label-xs">Nombres:</label>
                            <div class="col-xs-9"><input name="Prs_Nom" type="text" class="form-control input-xs" /></div>
                        </div>
                        <div class="form-group natural">
                            <label class="col-xs-3 control-label label-xs required">Genero:</label>
                            <div class="col-xs-4">
                                <select name="Prs_Sex" class="form-control input-xs">
                                    <option value="M">MASCULINO</option>
                                    <option value="F">FEMENINO</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group destinatario" style="display: none;">
                            <label class="col-xs-3 control-label label-xs">Razon Social:</label>
                            <div class="col-xs-9"><input name="Gpe_Ras" type="text" class="form-control input-xs" /></div>
                        </div>

                        <div class="form-group gpediv transportista" style="display:none;">
                            <label class="col-xs-3 control-label label-xs">Placa:</label>
                            <div class="col-xs-4"><input name="Gpe_Pla" type="text" class="form-control input-xs" maxlength="10" /></div>
                        </div>
                        <div class="form-group gpediv destinatario" style="display:none;">
                            <label class="col-xs-3 control-label label-xs">Establec.:</label>
                            <div class="col-xs-2"><input name="Gpe_Ces" type="text" class="form-control input-xs" maxlength="3" /></div>
                            <label class="col-xs-3 control-label label-xs">C.Aduanero:</label>
                            <div class="col-xs-4"><input name="Gpe_Dad" type="text" class="form-control input-xs" maxlength="20" /></div>
                        </div>
                    </fieldset>
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos de Ubicación</legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                            <div class="col-xs-4">
                                <?php $rs_ciudad = $obBD_con1->getArrayConsulta(13, '', $obBD_conexion); ?>
                                <select name="Ciu_Cod" class="form-control input-xs" required="">
                                    <option value=""></option>
                                    <?php foreach ($rs_ciudad as $row) {
                                        echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Dirección:</label>
                            <div class="col-xs-9"><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Teléfono:</label>
                            <div class="col-xs-4"><input name="Prs_Tel" type="text" class="form-control input-xs" required="" pattern="\d*" /></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Mail:</label>
                            <div class="col-xs-5"><input name="Prs_Cor" type="mail" class="form-control input-xs" required="" /></div>
                        </div>
                    </fieldset>
                    <div class="center">
                        <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                    </div>
                    <div class="Titulos2">
                        <hr><b>NOTA:</b> Los campos marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
                    </div>
                </form>
            </div>

            <!--Formulario para Editar  Trasportista/Destinatario-->
            <div id="guiaPersonaDialogEdit" title="Editar" class="guiaPersonaDialogEdit">
                <form class="form-horizontal normal" id="gpeEditForm" action="javascript:if(validaNoIdentif($('#gpeEditForm #Prs_Ced').val())['success']){ editarProvee(); }else{ $('gpeEditForm #Prs_Ced').flyout('show').focus() }">
                    <input name="Prs_Cod" id="Prs_Cod" type="hidden" class="text" />
                    <input name="Gpe_Cod" id="Gpe_Cod" type="hidden" data-trans="Gpe_Cod" />
                    <input name="Gpe_Tip" id="Gpe_Tip" type="text" class="hidden datatrigger" onchange="$('.gpediv').hide() ; if(this.value==='T'){ $('.gpeTipo').html('Transportista'); $('.transportista').show(); }else{ $('.gpeTipo').html('Destinatario'); $('.destinatario').show(); }" />
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos del <span class="gpeTipo">Trasnportista</span></legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                            <div class="col-xs-5">
                                <div class="input-group input-group-xs">
                                    <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="if(validaNoIdentif(this.value)['success']){ $('#gpeEditForm #Ide_Cod').val(this.value.length===10 ? 2:1); $('#Prv_Tic').val(validaNoIdentif(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchProveeEdit(this.value); }else{ $('#gpeEditForm #Ide_Cod').val(''); $('#Prv_Tic').val(''); $(this).fieldValid(false,validaNoIdentif(this.value)['message']); };" required="" readonly />
                                    <span class="input-group-addon validate"><i></i></span>
                                </div>
                            </div>
                            <button id="Tra_Btn" type="button" onclick="$('#transDialog').dialog('open');" class="btn btn-success btn-xs search_trans" title="Buscar Transportista" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                            <button id="Des_Btn search_dest" type="button" onclick="$('#destiDialog').dialog('open');" class="btn btn-success btn-xs search_dest" title="Buscar Destinatario" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Documento:</label>
                            <div class="col-xs-5">
                                <?php $rs_identi = $obBD_con1->getArrayConsulta(9, '', $obBD_conexion); ?>
                                <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                                    <option value=""></option>
                                    <?php foreach ($rs_identi as $row) {
                                        echo "<option value='$row[Ide_Cod]'>$row[Ide_Des]</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Raz�n Social:</span></label>
                            <div class="col-xs-9"><input name="Prs_Ape" type="text" class="form-control input-xs" /></div>
                        </div>
                        <div class="form-group natural">
                            <label class="col-xs-3 control-label label-xs">Nombres:</label>
                            <div class="col-xs-9"><input name="Prs_Nom" type="text" class="form-control input-xs" /></div>
                        </div>
                        <div class="form-group natural">
                            <label class="col-xs-3 control-label label-xs required">Genero:</label>
                            <div class="col-xs-4">
                                <select name="Prs_Sex" class="form-control input-xs">
                                    <option value="M">MASCULINO</option>
                                    <option value="F">FEMENINO</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group destinatario destinatario_edit" style="display: none;">
                            <label class="col-xs-3 control-label label-xs">Razon Social:</label>
                            <div class="col-xs-9"><input name="Gpe_Ras" type="text" class="form-control input-xs" /></div>
                        </div>
                        <div class="form-group gpediv transportista transportista_save" style="display:none;">
                            <label class="col-xs-3 control-label label-xs">Placa:</label>
                            <div class="col-xs-4"><input name="Gpe_Pla" type="text" class="form-control input-xs" maxlength="10" /></div>
                        </div>

                        <div class="form-group gpediv destinatario  destinatario_edit" style="display:none;">
                            <label class="col-xs-3 control-label label-xs">Establec.:</label>
                            <div class="col-xs-2"><input name="Gpe_Ces" type="text" class="form-control input-xs" maxlength="3" /></div>
                            <label class="col-xs-3 control-label label-xs">C.Aduanero:</label>
                            <div class="col-xs-4"><input name="Gpe_Dad" type="text" class="form-control input-xs" maxlength="20" /></div>
                        </div>

                    </fieldset>
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos de Ubicación</legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                            <div class="col-xs-4">
                                <?php $rs_ciudad = $obBD_con1->getArrayConsulta(13, '', $obBD_conexion); ?>
                                <select name="Ciu_Cod" class="form-control input-xs" required="">
                                    <option value=""></option>
                                    <?php foreach ($rs_ciudad as $row) {
                                        echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Dirección:</label>
                            <div class="col-xs-9"><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Teléfono:</label>
                            <div class="col-xs-4"><input name="Prs_Tel" type="text" class="form-control input-xs" required="" pattern="\d*" /></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Mail:</label>
                            <div class="col-xs-5"><input name="Prs_Cor" type="mail" class="form-control input-xs" required="" /></div>
                        </div>
                    </fieldset>
                    <div class="center">
                        <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Editar</button>
                    </div>
                    <div class="Titulos2">
                        <hr><b>NOTA:</b> Los campos marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
                    </div>
                </form>
            </div>
            <!-- Fin de formulario para Editar  Trasportista/Destinatario-->
            
            <!-- DIÁLOGO PARA GESTIONAR DERECHOS MINEROS -->
            <div id="derechoMineroDialog" title="Gestionar Derecho Minero" style="display:none;">
                <form class="form-horizontal normal" id="formDerechoMinero" action="javascript:guardarDerechoMinero();">
                    <input name="Der_Min_Id" id="Der_Min_Id_Form" type="hidden" />
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos del Derecho Minero</legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Código:</label>
                            <div class="col-xs-4">
                                <input name="Der_Min_Codigo" id="Der_Min_Codigo" type="text" class="form-control input-xs" maxlength="100" required="" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Nombre:</label>
                            <div class="col-xs-9">
                                <input name="Der_Min_Nombre" id="Der_Min_Nombre" type="text" class="form-control input-xs" maxlength="255" required="" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Titular/Operador:</label>
                            <div class="col-xs-6">
                                <select name="Der_Min_Titular_Operador" id="Der_Min_Titular_Operador" class="form-control input-xs" required="">
                                    <option value="TITULAR" selected>TITULAR</option>
                                    <option value="OPERADOR">OPERADOR</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Tipo:</label>
                            <div class="col-xs-6">
                                <?php 
                                 $tipos_derecho = $obBD_con1->getArrayConsulta(1281, '', $obBD_conexion);
                                $total_tipos = !empty($tipos_derecho) ? count($tipos_derecho) : 0;
                                $auto_select = ($total_tipos > 0 && $total_tipos <= 2); // Auto-seleccionar si hay 1 o 2 opciones
                                ?>
                                <select name="Der_Min_Tipo" id="Der_Min_Tipo" class="form-control input-xs" required="">
                                    <?php if (!$auto_select): ?>
                                        <option value="">Seleccione tipo...</option>
                                    <?php endif; ?>
                                    <?php 
                                    if (!empty($tipos_derecho)) {
                                        $first = true;
                                        foreach ($tipos_derecho as $tipo) {
                                            $selected = ($auto_select && $first) ? 'selected' : '';
                                            echo "<option value='{$tipo['Tip_Der_Min_Nombre']}' {$selected}>{$tipo['Tip_Der_Min_Nombre']}</option>";
                                            $first = false;
                                        }
                                    } else {
                                        // Opciones por defecto si no existe la tabla
                                        echo "<option value='Concesión Minera' selected>Concesión Minera</option>";
                                        echo "<option value='Permiso de Exploración'>Permiso de Exploración</option>";
                                        echo "<option value='Licencia de Explotación'>Licencia de Explotación</option>";
                                        echo "<option value='Concesión de Beneficio'>Concesión de Beneficio</option>";
                                        echo "<option value='Permiso de Libre Aprovechamiento'>Permiso de Libre Aprovechamiento</option>";
                                        echo "<option value='Otro'>Otro</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Tipo de Recurso:</label>
                            <div class="col-xs-6">
                                <select name="Der_Min_Recurso" id="Der_Min_Recurso" class="form-control input-xs">
                                    <option value="">Seleccione recurso...</option>
                                    <option value="METÁLICO">METÁLICO</option>
                                    <option value="NO METÁLICO">NO METÁLICO</option>
                                    <option value="MATERIALES DE CONSTRUCCIÓN">MATERIALES DE CONSTRUCCIÓN</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Ubicación:</label>
                            <div class="col-xs-9">
                                <input name="Der_Min_Ubicacion" id="Der_Min_Ubicacion" type="text" class="form-control input-xs" maxlength="255" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Observaciones:</label>
                            <div class="col-xs-9">
                                <textarea name="Der_Min_Observaciones" id="Der_Min_Observaciones" class="form-control input-xs" rows="3"></textarea>
                            </div>
                        </div>
                    </fieldset>
                    <div class="center">
                        <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        <button type="button" onclick="$('#derechoMineroDialog').dialog('close');" class="btn btn-sm btn-default">Cancelar</button>
                    </div>
                    <div class="Titulos2">
                        <hr><b>NOTA:</b> Los campos marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
                    </div>
                </form>
            </div>
            <!-- FIN DIÁLOGO DERECHOS MINEROS -->
            <script type="text/javascript">
                $(function() {
                    validaVetNum();
                    
                    // Inicializar diálogo de derechos mineros
                    $('#derechoMineroDialog').dialog({
                        autoOpen: false,
                        width: 600,
                        modal: true,
                        closeOnEscape: true
                    });
                    
                    // Cargar derechos mineros al iniciar la página
                    recargarDerechosMineros();
                });
                
                // Limpiar formulario de derecho minero
                function limpiarFormDerechoMinero() {
                    $('#formDerechoMinero')[0].reset();
                    $('#Der_Min_Id_Form').val('');
                    $('#Der_Min_Titular_Operador').val('TITULAR'); // Resetear a TITULAR por defecto
                    $('#derechoMineroDialog').dialog('option', 'title', 'Registrar Nuevo Derecho Minero');
                }
                
                // Editar derecho minero seleccionado
                function editarDerechoMinero() {
                    var derMinId = $('#Der_Min_Id').val();
                    if (!derMinId) {
                        alert('Por favor seleccione un derecho minero para editar');
                        return;
                    }
                    
                    var selectedOption = $('#Der_Min_Id option:selected');
                    $('#Der_Min_Id_Form').val(derMinId);
                    $('#Der_Min_Codigo').val(selectedOption.data('codigo'));
                    $('#Der_Min_Nombre').val(selectedOption.data('nombre'));
                    $('#Der_Min_Titular_Operador').val(selectedOption.data('titular'));
                    $('#Der_Min_Tipo').val(selectedOption.data('tipo'));
                    $('#Der_Min_Recurso').val(selectedOption.data('recurso'));
                    
                    $('#derechoMineroDialog').dialog('option', 'title', 'Editar Derecho Minero');
                    $('#derechoMineroDialog').dialog('open');
                }
                
                // Guardar derecho minero (nuevo o editar)
                function guardarDerechoMinero() {
                    var formData = $('#formDerechoMinero').serializeArray();
                    var derMinId = $('#Der_Min_Id_Form').val();
                    var accion = derMinId ? 'modificar' : 'registrar';
                    
                    formData.push({name: 'guardarDerechoMineroAjax', value: '1'});
                    formData.push({name: 'accion', value: accion});
                    
                    $.ajax({
                        url: window.location.pathname,
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert(response.message || 'Derecho minero guardado exitosamente');
                                $('#derechoMineroDialog').dialog('close');
                                // Recargar lista de derechos mineros
                                recargarDerechosMineros(response.Der_Min_Id);
                            } else {
                                alert('Error: ' + (response.message || 'No se pudo guardar el derecho minero'));
                                console.error('Error details:', response);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', xhr.responseText);
                            alert('Error al guardar: ' + error + '\nRevise la consola para más detalles');
                        }
                    });
                }
                
                // Recargar lista de derechos mineros
                function recargarDerechosMineros(selectId) {
                    // console.log('DEBUG: Llamando a recargarDerechosMineros...');
                    $.ajax({
                        url: window.location.pathname,
                        type: 'POST',
                        data: {obtenerDerechosMineros: 1},
                        dataType: 'json',
                        success: function(response) {
                            // console.log('DEBUG: Respuesta del servidor:', response);
                            // if (response.debug) {
                            //     console.log('DEBUG INFO:', response.debug);
                            // }
                            
                            if (response.success && response.derechos) {
                                // console.log('DEBUG: Cargando ' + response.derechos.length + ' derechos mineros');
                                var select = $('#Der_Min_Id');
                                select.empty();
                                select.append('<option value="">Seleccione un derecho minero (opcional)...</option>');
                                
                                $.each(response.derechos, function(index, derecho) {
                                    var selected = (selectId && derecho.Der_Min_Id == selectId) ? 'selected' : '';
                                    select.append(
                                        '<option value="' + derecho.Der_Min_Id + '" ' +
                                        'data-codigo="' + derecho.Der_Min_Codigo + '" ' +
                                        'data-nombre="' + derecho.Der_Min_Nombre + '" ' +
                                        'data-titular="' + derecho.Der_Min_Titular_Operador + '" ' +
                                        'data-tipo="' + derecho.Der_Min_Tipo + '" ' +
                                        'data-recurso="' + (derecho.Der_Min_Recurso || '') + '" ' +
                                        selected + '>' +
                                        derecho.Der_Min_Codigo + ' - ' + derecho.Der_Min_Nombre +
                                        '</option>'
                                    );
                                });
                                
                                // Si se seleccionó un derecho, actualizar el preview
                                if (selectId) {
                                    select.trigger('change');
                                }
                            } else {
                                console.error('Error al cargar derechos mineros:', response);
                                console.error('Mensaje:', response.message);
                                $('#Der_Min_Id').empty().append('<option value="">Error al cargar derechos mineros</option>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error AJAX al cargar derechos:', error);
                            console.error('Response Text:', xhr.responseText);
                            $('#Der_Min_Id').empty().append('<option value="">Error de conexión</option>');
                        }
                    });
                }
                </script>
            <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
            <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
            <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />
        <?php } ?>
    </div>
</body>

</html>