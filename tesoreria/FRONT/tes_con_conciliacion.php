<?php

/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2020-03-10
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_conciliacion.php');

/* Creacion del Objeto de conexion */
$obBD_conexion_get = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con_get = new Class_Log_Datos_Conciliacion();
$obBD_con_get->setConnection($obBD_conexion_get);

/* Informacion de Representante y Contador */
$infoFirmas = $obBD_con_get->getRowConsultaSql("SELECT Emp_Ren, Emp_Rre, Emp_Con, Emp_Rco FROM empresas WHERE Emp_Cod='$Ses_Emp_Cod'", $obBD_conexion_get);
/* Informacion del Usuario logeado */
$rowUsr = $obBD_con_get->getRowConsultaSql("SELECT persona.Prs_Cod, Prs_Ced, Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Ses_Usu_Cod", $obBD_conexion_get);

if (isset($conciliaAjax)) {
    $data = array_merge($_GET, array(
        'where' => array("perio_cont.Pec_Cod" => $Pec_Cod, "banco.Ban_Cod" => $Ban_Cod),
        'order' => 'Cob_Fec DESC'
    ));
    $page = $obBD_con_get->getPageGrid('conciliacion_bancaria', $data);
    $last = $obBD_con_get->getRow('conciliacion_bancaria', array_merge($data, array('setWhere' => array('isActive', 'setEmpCod'), 'limits' => 'LIMIT 0,1')));
    foreach ($page['rows'] as &$p) {
        if ($p['Cob_Cod'] == $last['Cob_Cod']) {
            $p['Cob_Last'] = 'S';
            break;
        }
    }
    unset($p);
    $obBD_con_get->echoJson($page);
}

if (isset($newConciliacion)) {

    $banco = $obBD_con_get->getRow('banco', array('where' =>
    array('Ban_Cod' => $conc['Ban_Cod'], 'Pec_Cod' => $conc['Pec_Cod']), 'setWhere' => array("setPeriodo")));

    $last = $obBD_con_get->getRow(
        'conciliacion_bancaria',
        array(
            'where' => array(
                'conciliacion_bancaria.Ban_Cod' => $conc['Ban_Cod'],
                'conciliacion_bancaria.Pec_Cod' =>
                $conc['Pec_Cod']
            ),
            'setWhere' => array("isActive", "setEmpCod"),
            'order' => 'Cob_Fec DESC'
        )
    );

    if (is_null($last)) {
        $last = array('Asi_Cod' => 'no_id', 'Asi_Sald' => '0.00', 'Com_Con' => 'Sin Conciliacion Anterior', 'Cob_Dis' => '0.00', 'pago_tipo' => ' ');
    } else {
        $last = array_merge($last, array('Asi_Cod' => 'no_id', 'Asi_Sald' => $last['Cob_Dis'], 'Com_Fec' => $last['Cob_Fec'], 'Com_Con' => 'Ultima Conciliación', 'pago_tipo' => ' '));
    }

    // $asientos = $obBD_con_get->getArray('asientos', array('where' => array("Com_Fec<='{$conc['Cob_Fec']}'", 'Cob_Cod' => null, 'Pec_Cod' => $conc['Pec_Cod'], 'Pld_Cod' => $banco['Pld_Cod']), 'setWhere' => array('comprobante', "saldo", "conciliacion", "tipo_pago", 'isActive'), 'order' => 'Com_Fec ASC', 'addCols' => array('' => array($obBD_con_get->expr("IF(Cob_Cod IS NULL,NULL,'S')AS conc_select"), $obBD_con_get->expr("'N' As conc_select")))));
    $asientos = $obBD_con_get->getArray(
        'asientos',
        array(
            'where' => array(
                "Com_Fec<='{$conc['Cob_Fec']}'",
                'Cob_Cod' => null,
                'Pec_Cod' => $conc['Pec_Cod'],
                'asientos.Pld_Cod' => $banco['Pld_Cod']
            ),
            'setWhere' => array('comprobante', "saldo", "conciliacion", "tipo_pago", 'isActive'),
            'order' => 'Com_Fec ASC',
            'addCols' => array('' => array(
                $obBD_con_get->expr("IF(Cob_Cod IS NULL,NULL,'S') AS conc_select"),
                $obBD_con_get->expr("'N' As conc_select"),
                //Doc_Num
                // $obBD_con_get->expr(" CONCAT( ' Num.Transf.: ', Num_Doc) AS Num_Doc"), // modo anterior
                /*  $obBD_con_get->expr("
                    IF(Num_Doc IS NOT NULL AND Num_Doc != '', 
                        CONCAT('Num.Transf.: ', Num_Doc), 
                        NULL
                    ) AS Doc_Num
                ")*/
                // === Com_Cod_Rel: si existe relación en ventas_compr, úsala; sino, usa el Com_Cod del comprobante
                $obBD_con_get->expr("
                    IFNULL(
                        (SELECT vc.Com_Cod
                            FROM ventas_compr vc
                        WHERE vc.Com_Cod = comprobantes.Com_Cod LIMIT 1),
                        comprobantes.Com_Cod
                    ) AS Com_Cod_Rel
                "),

                // === Vet_Che SOLO para Pag_Cod = 8 (tomamos el más reciente si existe Pag_Fec; ajusta ORDER BY a tu campo de fecha/id)
                $obBD_con_get->expr("
                    (SELECT pv.Vet_Che
                    FROM ventas_compr vc
                            JOIN pago_venta pv ON pv.Vet_Cod = vc.Vet_Cod
                    WHERE vc.Com_Cod = comprobantes.Com_Cod
                        AND pv.Pag_Cod = 8 LIMIT 1) AS Vet_Che
                "),

                // === FormasPago: todas las formas de pago de la venta en una sola columna
                $obBD_con_get->expr("
                    (SELECT GROUP_CONCAT(
                                CONCAT(
                                    CASE
                                        WHEN pv.Pag_Cod = 8 THEN 'Trf: '
                                        WHEN pv.Pag_Cod = 9 THEN 'Dps: '
                                        ELSE CONCAT('Pago ', pv.Pag_Cod, ': ')
                                    END,
                                    pv.Vet_Che
                                )
                                SEPARATOR ' | '
                            )
                        FROM ventas_compr vc
                            JOIN pago_venta pv ON pv.Vet_Cod = vc.Vet_Cod
                        WHERE vc.Com_Cod = comprobantes.Com_Cod
                    ) AS FormasPago
                ")
            ))
        )
    );

    array_unshift($asientos, $last);
    $obBD_con_get->echoJson(array('success' => true, 'Cob_Last' => $last, 'Ban' => $banco, 'asientos' => $asientos));
}


if (isset($editConciliacion)) {
    $conc = $obBD_con_get->getRow('conciliacion_bancaria', array('where' => array('Cob_Cod' => $Cob_Cod)));
    $banco = $obBD_con_get->getRow('banco', array('where' => array('Ban_Cod' => $conc['Ban_Cod'], 'Pec_Cod' => $conc['Pec_Cod']), 'setWhere' => array("setPeriodo")));
    $last = $obBD_con_get->getRow('conciliacion_bancaria', array('where' => array("Cob_Cod!={$conc['Cob_Cod']} AND Cob_Fec<'{$conc['Cob_Fec']}' AND conciliacion_bancaria.Ban_Cod={$conc['Ban_Cod']}"), 'setWhere' => array("isActive", "setEmpCod"), 'order' => 'Cob_Fec DESC'));
    if (is_null($last))
        $last = array('Asi_Cod' => 'no_id', 'Asi_Sald' => '0.00', 'Com_Con' => 'Sin Conciliacion Anterior', 'Cob_Dis' => '0.00', 'pago_tipo' => ' ');
    else
        $last = array_merge($last, array('Asi_Cod' => 'no_id', 'Asi_Sald' => $last['Cob_Dis'], 'Com_Fec' => $last['Cob_Fec'], 'Com_Con' => 'Ultima Conciliación', 'pago_tipo' => ' '));
    $asientos = $obBD_con_get->getArray('asientos', array('where' => array("Com_Fec<='{$conc['Cob_Fec']}'", "(Cob_Cod IS NULL OR Cob_Cod={$conc['Cob_Cod']})", 'Pec_Cod' => $conc['Pec_Cod'], 'Pld_Cod' => $banco['Pld_Cod']), 'setWhere' => array('comprobante', "saldo", "conciliacion", "tipo_pago", 'isActive'), 'order' => 'pago_tipo,Com_Fec ASC', 'addCols' => array('' => array($obBD_con_get->expr("IF(Cob_Cod IS NULL,'N','S')AS conc_select")))));
    array_unshift($asientos, $last);
    $obBD_con_get->echoJson(array('success' => true, 'Cob' => $conc, 'Cob_Last' => $last, 'Ban' => $banco, 'asientos' => $asientos));
}
if (isset($viewConciliacion)) {
    require_once('../../contabilidad/LOGICA/con_log_planc_2.php');
    $obBD_con_cont = new Class_Log_Datos_Con();
    $obBD_con_cont->setConnection($obBD_conexion_get);
    $conc = $obBD_con_get->getRow('conciliacion_bancaria', array('where' => array('Cob_Cod' => $Cob_Cod), 'setWhere' => array()));
    $banco = $obBD_con_get->getRow('banco', array('where' => array('Ban_Cod' => $conc['Ban_Cod'], 'Pec_Cod' => $conc['Pec_Cod']), 'setWhere' => array("setPeriodo")));
    $last = $obBD_con_get->getRow('conciliacion_bancaria', array('where' => array("Cob_Cod!={$conc['Cob_Cod']} AND Cob_Fec<'{$conc['Cob_Fec']}' AND conciliacion_bancaria.Ban_Cod={$conc['Ban_Cod']}"), 'setWhere' => array("isActive", "setEmpCod"), 'order' => 'Cob_Fec DESC'));
    if (is_null($last))
        $last = array('Asi_Cod' => 'no_id', 'Asi_Sald' => '0.00', 'Com_Con' => 'Sin Conciliacion Anterior', 'Cob_Dis' => '0.00', 'pago_tipo' => ' ');
    else
        $last = array_merge($last, array('Asi_Cod' => 'no_id', 'Asi_Sald' => $last['Cob_Dis'], 'Com_Fec' => $last['Cob_Fec'], 'Com_Con' => 'Ultima Conciliación', 'pago_tipo' => ' '));
    $Asi_Cods = array();
    $asientos = $obBD_con_get->getArray('asientos', array('where' => array("Com_Fec<='{$conc['Cob_Fec']}'", "(conciliacion_bancaria.Cob_Cod IS NULL OR conciliacion_bancaria.Cob_Cod={$conc['Cob_Cod']})", 'comprobantes.Pec_Cod' => $conc['Pec_Cod'], 'Pld_Cod' => $banco['Pld_Cod']), 'Cob_Cod' => $conc['Cob_Cod'], 'Cob_Fec' => $conc['Cob_Fec'], 'setWhere' => array('comprobante', "saldo", "conciliacion_menor", "tipo_pago", 'isActive'), 'order' => 'tipo,pago_tipo,Com_Fec ASC', 'addCols' => array('' => array($obBD_con_get->expr("IF(conciliacion_bancaria.Cob_Cod={$conc['Cob_Cod']},'CONCILIADAS','EN TRANSITO PERIODO EN CURSO')AS tipo")))));
    /*foreach($asientos AS $as){
        if($as['tipo']=='CONCILIADAS')array_push($Asi_Cods,$as['Asi_Cod']);
    }*/
    $mayor = $obBD_con_cont->getRowConsulta(371, array('Pld_Cod' => $banco['Pld_Cod'], 'Pec_Cod' => $conc['Pec_Cod'], 'Year' => $conc['Pec_Year'], 'Fin' => $conc['Cob_Fec'], 'Inicio' => isset($last['Cob_Fec']) ? $last['Cob_Fec'] : $conc['Pec_Fei'], 'Asi_Cods' => $Asi_Cods));
    $mayor['Pld_Sal'] = (is_null($mayor['Debe']) ? 0 : $mayor['Debe']) - (is_null($mayor['Haber']) ? 0 : $mayor['Haber']);
    $obBD_con_get->echoJson(array('success' => true, 'asientos' => $asientos, 'Mayor' => $mayor, 'Cob' => $conc, 'Cob_Last' => $last, 'Ban' => $banco));
}
if (isset($updateConcilia)) {
    $resp = array('success' => false);
    $obBD_con_set = new Class_Log_Datos_Conciliacion(true);
    //$obBD_con_set->debug(true);
    $obBD_con_set->beginTrans();
    try {
        if (empty($form['Cob_Cod'])) {
            $Cob_Cod = $obBD_con_set->operation('conciliacion_bancaria.insert', array_merge($form, array('Usu_Cod' => $Ses_Usu_Cod)))->lastId();
        } else {
            $Cob_Cod = $form['Cob_Cod'];
            $obBD_con_set->operation('conciliacion_bancaria.update', array_merge($form, array('Usu_Cod' => $Ses_Usu_Cod)));
            $obBD_con_set->operation('conciliacion_banc_asientos.deleteWhere', array('where' => array('Cob_Cod' => $Cob_Cod)));
        }
        foreach ($asientos as $asi) {
            $obBD_con_set->operation('conciliacion_banc_asientos.insert', array('Asi_Cod' => $asi, 'Cob_Cod' => $Cob_Cod));
        }
        //throw new Exception("Se guardo correctamente, pero no!");
    } catch (Exception $e) {
        $obBD_con_set->rollB($e->getMessage(), $resp);
        $obBD_con_set->echoJson($resp);
    }
    $obBD_con_set->endTrans($resp); // finalizo la transaccion y compruebo errores
    $obBD_con_set->echoJson($resp);
}
if (isset($deleteConcilia)) {
    $resp = array('success' => false);
    $obBD_con_set = new Class_Log_Datos_Conciliacion(true);
    //$obBD_con_set->debug(true);
    $obBD_con_set->beginTrans();
    try {
        if (empty($Cob_Cod)) throw new Exception("Error No se recibio el codigo!");
        $obBD_con_set->operation('conciliacion_banc_asientos.deleteWhere', array('where' => array('Cob_Cod' => $Cob_Cod)));
        $obBD_con_set->operation('conciliacion_bancaria.setInactive', array('Cob_Cod' => $Cob_Cod));
        //throw new Exception("Se guardo correctamente, pero no!");
    } catch (Exception $e) {
        $obBD_con_set->rollB($e->getMessage(), $resp);
        $obBD_con_set->echoJson($resp);
    }
    $obBD_con_set->endTrans($resp); // finalizo la transaccion y compruebo errores
    $obBD_con_set->echoJson($resp);
}
if ($docDetalle) {
    $com = $obBD_con_get->getRow('comprobantes', array('Com_Cod' => $Com_Cod, 'setWhere' => array("data")));
    $asientos = $obBD_con_get->getArray('asientos', array('Com_Cod' => $Com_Cod, 'setWhere' => array("data")));
    $obBD_con_get->echoJson(array('success' => true, 'comprobante' => $com, 'asientos' => $asientos));
}
$periodos = $obBD_con_get->getArrayConsulta('perio_cont', array('perio_cont.Pec_Est' => 'A', 'setWhere' => 'setEmpCod', 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion_get);
$bancos = $obBD_con_get->getArrayConsulta('banco', array('setWhere' => array('setPeriodo', 'isActive', 'isTipo', "setEmpCod")), $obBD_conexion_get);
?>
<!DOCTYPE html>
<HTML>

<haed>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Conciliacion [EXA]"; ?></TITLE>    
    <meta charset="UTF-8">    
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style></style>
    </head>
    <style>
        #gview_jqGridRep {
            height: 350px !important;
        }
    </style>
    <script>var Usu_Adm=<? echo $Ses_Prs_Cod;?></script>
    <BODY>
        <div id="buscaDiv" class="panel panel-main" style="display:none">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo; Buscar Conciliaciones</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <form name="searchConcilia" id="searchConcilia" method="get" class="form-horizontal normal" action="javascript:$('#conciliacion').Search('#searchConcilia','conciliaAjax');">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&uacute;squeda</legend>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs required">Periodo:</label>
                                    <div class="col-xs-3">
                                        <select name="Pec_Cod" class="form-control input-xs" required="" onchange="loadBancos($(this).val())">
                                            <option value="">Periodo..</option>
                                            <?php foreach ($periodos as $p) {
                                                echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Pec_Cod]' " . (count($periodos) == 1 ? 'selected=""' : '') . ">$p[Year]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs required">Banco:</label>
                                    <div class="col-xs-4">
                                        <div class="input-group">
                                            <select name="Ban_Cod" class="form-control input-xs" required="" onchange="">
                                                <option value="">Banco..</option>
                                                <?php foreach ($bancos as $b) {
                                                    echo "<option pec='$b[Pec_Cod]' data--pld_-cod='$b[Pld_Cod]' data--ban_-cod='$b[Ban_Cod]' value='$b[Ban_Cod]' style='display:none;' " . (count($bancos) == 1 ? 'selected=""' : '') . ">$b[Pld_Des]</option>";
                                                } ?>
                                            </select>
                                            <span class="input-group-btn"><button type="submit" class="btn btn-success btn-xs" title="Mostrar Conciliaciones"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                        <div class="" style="min-height: 300px;">
                            <table id="conciliacion"></table>
                            <div id="conciliacionPager"></div>
                        </div>
                    </div>
                    <div class="col-xs-12" style="padding-top: 8px;">
                        <button class="btn btn-sm btn-primary" onclick="nuevaConciliacion();"><i class="glyphicon glyphicon-plus"></i> Nueva Conciliaci&oacute;n</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="editDiv" class="panel panel-main" style="display:none">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo; Editar Conciliaci&oacute;n</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-xs-12">
                        <form name="formConcilia" id="formConcilia" method="get" class="form-horizontal normal" action="javascript:validarForm();">
                            <div class="row">
                                <div class="col-xs-6">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Dato Banco</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Periodo:</label>
                                            <div class="col-xs-3">
                                                <select name="Pec_Cod" class="form-control input-xs readOnly" required="" onchange="loadBancos2($(this).val())">
                                                    <option value="">Periodo..</option>
                                                    <?php foreach ($periodos as $p) {
                                                        echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Pec_Cod]' " . (count($periodos) == 1 ? 'selected="" default=""' : '') . ">$p[Year]</option>";
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Banco:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group">
                                                    <select name="Ban_Cod" class="form-control input-xs readOnly" required="" onchange="">
                                                        <option value="">Banco..</option>
                                                        <?php foreach ($bancos as $b) {
                                                            echo "<option pec='$b[Pec_Cod]' data--pld_-cod='$b[Pld_Cod]' data--ban_-cod='$b[Ban_Cod]' value='$b[Ban_Cod]' " . (count($bancos) == 1 ? 'selected=""' : '') . ">$b[Pld_Des]</option>";
                                                        } ?>
                                                    </select>
                                                    <span class="input-group-btn"><button id="loadAsientosBtn" type="button" onclick="loadAsientos();" class="btn btn-success btn-xs" title="Cargar Asientos"><span class="glyphicon glyphicon-refresh"></span> </button></span>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">&Uacute;ltima</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Fecha:</label>
                                            <div class="col-xs-4">
                                                <span data-last="Cob_Fec" class="form-control input-xs"></span>
                                            </div>
                                            <label class="col-xs-2 control-label label-xs required">Saldo:</label>
                                            <div class="col-xs-4">
                                                <span data-last="Cob_Dis" class="form-control input-xs"></span>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                                <div class="col-xs-6">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Conciliaci&oacute;n</legend>
                                        <input type="hidden" name="Cob_Cod" data-cob="Cob_Cod" />
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Fecha:</label>
                                            <div class="col-xs-4">
                                                <input id="Cob_Fec" name="Cob_Fec" data-cob="Cob_Fec" type="text" class="form-control input-xs readOnly" required="" />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Saldo&nbsp;Banco:</label>
                                            <div class="col-xs-4">
                                                <input id="Cob_Dis" name="Cob_Dis" data-cob="Cob_Dis" type="number" class="form-control input-xs nospin readOnly" required="" />
                                            </div>
                                            <label class="col-xs-2 control-label label-xs required">Observación:</label>
                                            <div class="col-xs-4">
                                                <textarea name="Cob_Obs" data-cob="Cob_Obs" class="form-control input-xs"></textarea>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="col-xs-12" style="min-height: 500px;">
                        <table id="conciliacionForm"></table>
                        <div id="conciliacionFormPager"></div>
                    </div>

                    <div class="col-xs-12">
                        <button class="btn btn-sm btn-inverse" onclick="$('#editDiv').moveComp('#buscaDiv').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atr&aacute;s</button>
                        <button class="btn btn-sm btn-primary" onclick="$('#formConcilia').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                    </div>

                </div>
            </div>
        </div>

        <div id="viewDiv" class="panel panel-main" style="display:none">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo; Buscar Conciliaciones</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="form-horizontal">
                    <fieldset class="exa-fieldset" id="formConciliacion">
                        <legend class="Titulos2">Información</legend>
                        <div class="form-group">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label for="Cob_Fec" class="control-label label-xs col-form-label col-xs-2" title="Fecha Conciliacion">Fecha</label>
                                    <div class="col-xs-4"><span type="text" name="Cob_Fec" class="form-control input-xs databind"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label label-xs col-form-label col-xs-2" title="Cta. Bancaria">Banco</label>
                                    <div class="col-xs-10 formBaem" id="editBaemId">
                                        <select id="conc_baem_id" name="Ban_Cod" class="form-control input-xs bancoImage readOnly" disabled="" style="background-color:rgba(255,255,255,0.25) !important;">
                                            <option value=''>Seleccione Banco...</option>
                                            <?php foreach ($bancos as $b) {
                                                echo "<option pec='$b[Pec_Cod]' data--pld_-cod='$b[Pld_Cod]' data--ban_-cod='$b[Ban_Cod]' value='$b[Ban_Cod]' " . (count($bancos) == 1 ? 'selected=""' : '') . ">$b[Pld_Des]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label label-xs col-form-label col-xs-8">SALDO ANTERIOR BANCO:</label>
                                    <div class="col-xs-4"><span type="text" name="Asi_Sald" class="form-control input-xs databind isNumber txt-right bold" decimals='2'></span></div>
                                </div>
                                <div class="form-group">
                                    <div class="col-xs-4"></div>
                                    <div class="col-xs-8">
                                        <table id="jqGridRepBanc" class="gridRepResumen" title="CONCILIACION"></table>
                                        <div id="jqGridRepBancPager"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label label-xs col-form-label col-xs-8">(=) SALDO CTA. BANCARIA:</label>
                                    <div class="col-xs-4"><span type="text" name="Tot_Cob_Dis" class="form-control input-xs databind isNumber txt-right bold" decimals='2'></span></div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label for="conc_obse" class="control-label label-xs col-form-label col-xs-2">Observación</label>
                                    <div class="col-xs-10"><span type="text" name="Cob_Obs" class="form-control input-xs databind"></span></div>
                                </div>
                                <div class="form-group formBaem" id="conc_cta">
                                    <label class="control-label label-xs col-form-label col-xs-2" title="Cta. Contable">Cta.</label>
                                    <div class="col-xs-10">
                                        <div class="input-group input-group-xs">
                                            <span name="Pld_Cdc" class="input-group-addon databind bold"> </span><span name="Pld_Des" class="form-control databind datatitle"> </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label label-xs col-form-label col-xs-8">SALDO LIBRO MAYOR:</label>
                                    <div class="col-xs-4"><span type="text" name="Pld_Sal" class="form-control input-xs databind isNumber txt-right bold" decimals='2'></span></div>
                                </div>
                                <div class="form-group">
                                    <div class="col-xs-4"></div>
                                    <div class="col-xs-8">

                                        <table id="jqGridRepCta" class="gridRepResumen" title="EN TRANSITO"></table>
                                        <div id="jqGridRepCtaPager"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label label-xs col-form-label col-xs-8">(=) SALDO CTA. BANCARIA:</label>
                                    <div class="col-xs-4"><span type="text" name="Tot_Pld_Sal" class="form-control input-xs databind isNumber txt-right bold" decimals='2'></span></div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <table id="jqGridRep"></table>
                            <div id="jqGridRepPager"></div>
                        </div>
                    </div>
                    <div style="padding-top:10px;">
                        <button class="btn btn-sm btn-inverse" onclick="$('#viewDiv').moveComp('#buscaDiv').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
                        <button class="btn btn-sm btn-primary" type="button" onclick="imprimir();"><i class="glyphicon glyphicon-print"></i> Imprimir </button>
                        <button class="btn btn-sm btn-primary" type="button" onclick="exportar();"><i class="glyphicon glyphicon-download-alt"></i> Exportar </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="docDetaDialog" title="Documento">
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Comprobante Contable:</legend>
                <div class="form-horizontal normal" style="padding: 0 4px;">
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">Asiento:</label>
                        <div class="col-xs-4"><span name="Tia_Ini_Long" class="form-control input-xs"></span></div>
                        <label class="col-xs-2 control-label label-xs">Tipo:</label>
                        <div class="col-xs-4"><span name="Tia_Des" class="form-control input-xs"></span></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">C&eacute;dula/RUC:</label>
                        <div class="col-xs-4"><span name="Inv_Ced" class="form-control input-xs"></span></div>
                        <label class="col-xs-2 control-label label-xs">Codigo:</label>
                        <div class="col-xs-4"><span name="Com_Codigo" class="form-control input-xs"></span></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">Proveedor:</label>
                        <div class="col-xs-6"><span name="Inv_Nom" class="form-control input-xs"></span></div>
                        <label class="col-xs-1 control-label label-xs">Fecha:</label>
                        <div class="col-xs-3"><span name="Com_Fec" class="form-control input-xs"></span></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">Concepto:</label>
                        <div class="col-xs-10"><span name="Com_Con" class="form-control input-xs"></span></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">Observación:</label>
                        <div class="col-xs-10"><span name="Com_Obs" class="form-control input-xs"></span></div>
                    </div>
                    <div class="form-group condensed">
                        <div class="col-xs-12">
                            <div class="pull-right">
                                <table id="detaDocu"></table>
                            </div>
                        </div>
                        <div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;"><b>CREACI&Oacute;N:</b> <span name="Com_Sys" class="databind"></span> &nbsp;&nbsp;-&nbsp;&nbsp; <b>USUARIO:</b> <span name="Usu_Nom" class="databind"></span></div>
                    </div>
                </div>
            </fieldset>
        </div>
        <div>
            <table width="100%" border="0" cellpadding="0" cellspacing="0" id="tableExtraInfo" style="display:none;">
                <tbody>
                    <tr>
                        <td width="11%"></td>
                        <td width="39%"></td>
                        <td width="11%"></td>
                        <td width="39%"></td>
                    </tr>
                    <tr class="head">
                        <td colspan="2" style="border-bottom: 0.1pt solid rgb(102, 102, 102); border-top: 0.1pt solid rgb(102, 102, 102);"></td>
                        <td style="border-bottom: 0.1pt solid rgb(102, 102, 102); border-top: 0.1pt solid rgb(102, 102, 102);font-weight:bold;text-align:right;">CORTE:</td>
                        <td style="border-bottom: 0.1pt solid rgb(102, 102, 102); border-top: 0.1pt solid rgb(102, 102, 102);font-weight:normal;"><span name="Cob_Fec" class="form-control"></span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;border-bottom:0.1pt solid rgb(102, 102, 102); border-top:0.1pt solid rgb(102, 102, 102);">OBSERVACIÓN:</td>
                        <td colspan="3" style="border-bottom: 0.1pt solid rgb(102, 102, 102); border-top: 0.1pt solid rgb(102, 102, 102);white-space:nowrap; overflow:hidden;"><span name="Cob_Obs" class="form-control"></span></td>
                    </tr>
                    <tr>
                        <td colspan="4"><br /><br /></td>
                    </tr>
                    <tr>
                        <td align="center" valign="top" colspan="2">
                            <table width="86%" border="0" cellpadding="0" cellspacing="0" class="mayor"></table>
                        </td>
                        <td align="center" valign="top" colspan="2">
                            <table width="86%" border="0" cellpadding="0" cellspacing="0" class="banco"></table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4"><br /><br /></td>
                    </tr>
                </tbody>
            </table>
            <div id="imprimir" style="display: none;">
                <div style="width: 1030px;">
                    <style>
                        #imprimir .form-control {
                            border: 0 !important;
                        }
                    </style>
                    <div style="margin-left:5%;">
                        <?php echo $obBD_con_get->getReportHeader($Ses_Suc_Cod, '<div style="text-align:center">CONCILIACION BANCARIA</div>', '<span class="subtitle"></span>', $obBD_conexion_get, false) ?>
                    </div>
                    <table id="tablereportExtra" cellspacing="0" cellpadding="0" style="border-collapse:collapse; table-layout:fixed;/*font-size:11px;*/" width="100%" class='tableReporteExtra'></table>
                    <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout: fixed;"></table>
                    <?php echo $obBD_con_get->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion_get); ?>
                    <br><br>
                    <table width="700" border="0" align="center" cellpadding="0" cellspacing="0" style="font-size: 11px; margin-top: 30px;">
                        <tr>
                            <td align="center" width="45%" valign="top">
                                ________________________________<br>
                                <strong>ELABORADO POR</strong><br>
                                <?php echo $rowUsr['Prs_Ape'] . ' ' . $rowUsr['Prs_Nom']; ?>
                            </td>
                            <td width="10%">&nbsp;</td>
                            <td align="center" width="45%" valign="top">
                                ________________________________<br>
                                <strong>CONTADOR</strong><br>
                                <?php echo $infoFirmas['Emp_Con']; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div id="exportar" style="display: none;">
                <?php echo $obBD_con_get->getReportHeader($Ses_Suc_Cod, '<div style="text-align:center">CONCILIACION BANCARIA</div>', '<div style="text-align:center"><span class="subtitle"></span></div>', $obBD_conexion_get, false, 6) ?>
            </div>
        </div>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
        <script type="text/javascript">
            let selBan1, selBan2;
            $(function() {
                $('#detaDocu').createGrid({
                    height: 'auto',
                    width: 550,
                    responsive: false,
                    rownumbers: false,
                    caption: 'Detalle Comprobante',
                    footerrow: true,
                    userDataOnFooter: true,
                    datatype: 'local',
                    totalCols: ['Asi_Debe', 'Asi_Haber'],
                    colModel: [
                        { label: 'Cód.Int.', name: 'Asi_Cod', key: true, width: 15, align: "center", hidden: true },
                        { label: 'Codigo ', name: 'Pld_Cdc', width: 35 },
                        { label: 'Cuenta', name: 'Pld_Des', width: 120 },
                        { label: 'Glosa.', name: 'Asi_Glo', width: 60, align: 'right' },
                        { label: 'Debe', name: 'Asi_Debe', width: 30, align: 'right', formatter: 'currency',
                            formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' },
                            summaryType: "sum"
                        },
                        { label: 'Haber', name: 'Asi_Haber', width: 30, align: 'right', formatter: 'currency',
                            formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' },
                            summaryType: "sum"
                        }
                    ]
                }, true);
                $('#docDetaDialog').createDialog({
                    height: 400,
                    width: 600,
                    noTitleStuff: false,
                    noBorder: true
                });

                $('#buscaDiv,#editDiv,#viewDiv').initDivs({
                    editDiv: () => {
                        $("#conciliacionForm").createGrid({
                            pager: '#conciliacionFormPager',
                            local: true,
                            height: 450,
                            postData: $('#formConcilia').getData(),
                            rowNum: 1000000,
                            footerrow: true,
                            userDataOnFooter: true,
                            datatype: 'local',
                            caption: 'Asientos No Conciliados',
                            stateCol: 'conc_select',
                            stateConfig: {
                                S: 'cellGreen2'
                            },
                            totalCols: ['Asi_Sald'],
                            colModel: [
                                { label: $.createIcon('check'), name: 'conc_select', width: 20, align: 'center', formatter: 'checkboxExa',
                                    formatoptions: {
                                        defaultChecked: false,
                                        dataEvents: { change: 'updateTotal($(this).data("rowId"));' },
                                        conditional: o => o.Asi_Cod !== 'no_id'
                                    }
                                },
                                { label: 'Id', name: 'Com_Cod', width: 20 },
                                { label: 'Id', name: 'Asi_Cod', key: true, width: 20 },
                                { label: 'Tipo', name: 'pago_tipo', width: 1, hidden: true },
                                { label: 'Codigo', name: 'Com_Codigo', width: 40, align: 'center' },
                                { label: 'Fecha', name: 'Com_Fec', width: 40, align: 'center' },
                                { label: 'Doc.', name: 'Doc_Num', width: 40, align: 'center',
                                    formatter: function(cellValue, options, rowObject) {
                                        return cellValue ? 'Che: ' + cellValue : '';
                                    }
                                },

                                // { label: 'Nro. Documento.', name: 'Num_Doc', width: 40, align: 'center' }, // forma anterior

                                { label: 'Nro. Documento.', name: 'Num_Doc', width: 50, align: 'center',
                                    formatter: function(cellValue, options, rowObject) {
                                        let doc = cellValue ? cellValue : '';
                                        let vet = rowObject.FormasPago ? rowObject.FormasPago : '';
                                        // Si FormasPago ya incluye el prefijo (Trf: / Dps: / Pago X:), solo mostrar ese campo
                                        if (vet) {
                                            return vet;
                                        } else if (doc) {
                                            return 'Trf: ' + doc;
                                        } else {
                                            return '';
                                        }
                                    }
                                },
                                { label: 'Clie./Provee.', name: 'Inv_Nom', width: 75 },
                                { label: 'Observación', name: 'Asi_Glo', width: 100 },

                                //{ label: 'Debe', name: 'cmpd_debe', width: 45, formatter:'number', formatoptions:{defaultValue:''}, summaryType:'sum' },
                                //{ label: 'Haber', name: 'cmpd_habe', width: 45, formatter:'number', formatoptions:{defaultValue:''}, summaryType:'sum' },

                                { label: 'Sumas', name: 'Asi_Sald', width: 45, formatter: 'number',
                                    formatoptions: { defaultValue: '0.00' },
                                    summaryType: 'sumNotInit', hidden: false
                                },
                                { label: 'Saldo', name: 'Cob_Disp', width: 45, formatter: 'number',
                                    formatoptions: { defaultValue: '--' },
                                    classes: 'columnHighlight3'
                                },
                                { label: 'Info.', name: 'view', width: 20, formatter: 'gridButton',
                                    formatoptions: {
                                        action: 'viewInfo', data: 'Com_Cod', type: 'primary', title: 'Ver Comprobante', icon: 'list-alt',
                                        conditional: o => $.vv(o.Asi_Cod) && o.Asi_Cod !== '' && o.Asi_Cod !== 'no_id'
                                    }
                                }
                            ],
                            notStriped: true,
                            loadonce: true,
                            grouping: true,
                            groupingView: {
                                groupField: ['pago_tipo'],
                                groupColumnShow: [false],
                                groupText: ["<div class='txtLeft'><b>{0}</b><span class='green pull-right'>{1} Operacion(es)</span></div>"],
                                groupCollapse: false,
                                groupSummary: [true],
                                showSummaryOnHide: [true]
                            }
                        }, true, '#conciliacionFormPager');
                        $("#conciliacionForm").jqGrid('footerData', 'set', {
                            cmpd_glos: '<div class="txtRight">TOTAL:</div>'
                        }, false);
                    },

                    buscaDiv: () => {
                        $("#conciliacion").createGrid({
                            stateCol: 'Cob_Est',
                            stateConfig: { I: 'cellRed2' },
                            rowNum: 1000000,
                            height: 300,
                            footerrow: true,
                            userDataOnFooter: true,
                            totalCols: ['cmpd_debe', 'cmpd_habe'],
                            totalDefault: {},
                            datatype: 'local',
                            caption: ' ',
                            colModel: [
                                { label: 'Id', name: 'Cob_Cod', key: true, width: 20 },
                                { label: 'Observación', name: 'Cob_Obs', width: 75 },
                                { label: 'Fecha', name: 'Cob_Fec', width: 40, align: 'center' },
                                { label: 'Responsable', name: 'Prs_Nom', width: 40, align: 'center' },
                                
                                //{ name: 'paem_imag_src',label:'<i class="glyphicon glyphicon-picture"></i>', width:15, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'showImage', icon:'picture', title:'Seleccionar Item', type:'info', data:['index','Cob_Res'], conditional:function(o){ return !!o.Cob_Res; } }, resizable: false },
                                
                                { label: 'Saldo Disp.', name: 'Cob_Dis', width: 40, formatter: 'currency', summaryType: 'sumIfNotDeleted' },
                                { name: 'view', label: $.createIcon('eye-open'), width: 15, align: 'center', viewable: false, formatter: 'gridButton',
                                    formatoptions: {
                                        action: 'viewItem', icon: 'eye-open', type: 'info', title: 'Visualizar Conciliacion', data: 'Cob_Cod',
                                        conditional: o => o.Cob_Est === 'A'
                                    },
                                    resizable: false
                                },
                                { label: '<center><i class="ui-icon ui-icon-pencil"></i></center>', name: 'edit', width: 40, align: 'center', viewable: false, resizable: false,
                                    formatter: function (cellvalue, options, o) {                                                                          
                                        return ((o.Cob_Est === 'A' && o.Cob_Last === 'S') || Usu_Adm==1)?$.getGridButton(editItem, o.Cob_Cod, 'Editar Conciliacion', 'pencil', '',  $.isEmpty(o.Cob_Last)?'warning':'success'):'';
                                    }
                                },                               
                                { name: 'delete', label: $.createIcon('remove'), width: 15, viewable: false, formatter: 'gridButton',
                                    formatoptions: {
                                        action: 'validaEliminacion', icon: 'remove', title: 'Eliminar Conciliacion', type: 'danger', data: 'Cob_Cod',
                                        attr: { 'tabindex': '-1' },
                                        conditional: o => o.Cob_Est === 'A' && o.Cob_Last === 'S'
                                    },
                                    resizable: false
                                },
                                { name: 'Cob_Est', label: 'isDeleted', width: 20, hidden: true }
                            ]
                        });
                        $("#conciliacion").on('jqGridAfterLoadComplete', function(ev, glc) {
                            $("#conciliacion").setCaption($('#searchConcilia').find('select[name="Ban_Cod"] option:selected').text() + " - Periodo " + $('#searchConcilia').find('select[name="Pec_Cod"] option:selected').text());
                        });
                    },
                    viewDiv: () => {
                        let optsRes = {
                            url: '',
                            pager: false,
                            main: true,
                            small: true,
                            local: true,
                            footerrow: true,
                            userDataOnFooter: true,
                            totalCols: ['val'],
                            height: 'auto',
                            tableType: ' ',
                            rownumbers: false,
                            colModel: [
                                { label: 'Id', name: 'id', key: true, hidden: true, width: 20 },
                                { label: 'Operacion', name: 'pago_desc', width: 50 },
                                { label: 'Valor', name: 'val', width: 30, formatter: 'number', summaryType: 'sum' }
                            ],
                            notStriped: true,
                            loadonce: true
                        }

                        $('#jqGridRepBanc').createGrid($.extend({
                            caption: 'CONCILIADAS'
                        }, optsRes));

                        $('#jqGridRepCta').createGrid($.extend({
                            caption: 'EN TRANSITO'
                        }, optsRes));

                        gridRep = $("#jqGridRep").createGrid({
                            url: '',
                            pager: true,
                            height: 350,
                            main: true,
                            small: true,
                            local: true,
                            datatype: 'local',
                            caption: 'DETALLE DE MOVIMIENTOS', //footerrow:true, userDataOnFooter:true,  totalCols:['Pld_Sal'],
                            colModel: [
                                { label: 'Id', name: 'Com_Cod', hidden: true, width: 20 },
                                { label: 'Id', name: 'Asi_Cod', key: true, hidden: true, width: 20 },
                                { label: 'Tipo', name: 'tipo', width: 40, hidden: true },
                                { label: 'Tipo', name: 'pago_tipo', width: 40, hidden: true },
                                { label: 'Fecha', name: 'Com_Fec', width: 40, align: 'center' },
                                { label: 'Codigo', name: 'Com_Codigo', width: 40, align: 'center' },
                                { label: 'Doc.', name: 'Doc_Num', width: 40, align: 'center' },
                                { label: 'Clie./Provee.', name: 'Inv_Nom', width: 75, cellattr: $.cellAjust },
                                { label: 'Observación', name: 'Asi_Glo', width: 100 },
                                { label: 'Sumas', name: 'Asi_Sald', width: 45, formatter: 'number', summaryType: 'sum' },
                                { label: 'Info.', name: 'view', width: 20, formatter: 'gridButton',
                                    formatoptions: {
                                        action: 'viewComprobante', data: 'cmpr_id', type: 'primary', title: 'Ver Comprobante', icon: 'list-alt',
                                        conditional: o => $.vv(o.cmpr_id) && o.cmpr_id !== ''
                                    }
                                }
                            ],
                            notStriped: true,
                            loadonce: true,
                            grouping: true,
                            groupingView: {
                                groupField: ['tipo', 'pago_tipo'],
                                groupOrder: ["desc", 'asc'],
                                groupColumnShow: [false, false],
                                groupText: ["<b>{0}</b>", "<div class='txtLeft'><b>{0}</b><span class='green pull-right'> {1} Operacion(es)</span></div>"],
                                groupCollapse: false,
                                groupSummary: [true, true],
                                showSummaryOnHide: [true, true]
                            }
                        }, true, '#jqGridRepPager');
                        gridRep.on('jqGridAfterLoadComplete', function() {
                            var i, groups = gridRep.jqGrid("getGridParam", "groupingView").groups,
                                l = groups.length;
                            for (i = 0; i < l; i++) {
                                let idSelectorPrefix = "#" + gridRep[0].id + "ghead_" + groups[i].idx + "_";
                                $(idSelectorPrefix + i + " ~ tr.jqfoot[jqfootlevel=" + groups[i].idx + "]").first().find(">td[aria-describedby=jqGridRep_Asi_Glo]").css({
                                    'text-align': 'right',
                                    'font-weight': 'bold'
                                }).html((groups[i].idx === 1 ? 'Total ' : 'OPERACIONES ') + groups[i].value);
                            }
                            gridRep.find('tr.jqfoot td[aria-describedby="jqGridRep_Pld_Sal"]').css({
                                'border-bottom': '1px solid #666',
                                'font-weight': 'bold'
                            });
                        });
                        $('#conc_baem_id').on('change', function() {
                            console.log('ver');
                            $('#conc_cta').setData($(this).find('option:selected').data('banco_empresa'));
                        });
                    }
                });
                $('#buscaDiv').show();
                selBan1 = $('<select></select>');
                selBan2 = $('<select></select>');
                $('#Cob_Fec').createDatePickers();
                /*<?php if (count($periodos) == 1) { ?>*/
                loadBancos($('#searchConcilia').find('select[name="Pec_Cod"]').val());
                /*<?php } ?>*/
            });

            function sumNotInit(v, n, obj) {
                return isNaN(v) ? 0 : (obj['cmpd_id'] === 'no_id' || obj['conc_select'] === 'N' ? 0 : v);
            }

            function nuevaConciliacion() {
                $('#conciliacionForm').clearGrid(true);
                let form = $('#formConcilia');
                form.setData({}, 'last');
                form.setData({}, 'cob');
                form.find('select[name=Pec_Cod]').trigger('change').prop('disabled', false);
                form.find('select[name=Ban_Cod]').add($('#loadAsientosBtn')).add($('#Cob_Fec')).prop('disabled', false);
                $('#buscaDiv').moveComp('#editDiv').updateGridsSizes();
            }

            function editItem(Cob_Cod) {
                $.getDataJson('', {
                    Cob_Cod: Cob_Cod,
                    editConciliacion: true
                }, function(r) {
                    $('#conciliacionForm').setRows(r.asientos);
                    let form = $('#formConcilia');
                    form.find('select[name=Pec_Cod]').val(r.Cob.Pec_Cod).trigger('change').prop('disabled', true);
                    form.find('select[name=Ban_Cod]').val(r.Cob.Ban_Cod).add($('#loadAsientosBtn')).add($('#Cob_Fec')).prop('disabled', true);
                    form.setData(r.Cob_Last, 'last');
                    form.setData(r.Cob, 'cob');
                    $('#buscaDiv').moveComp('#editDiv').updateGridsSizes();
                    updateTotal();
                });
            }

            function loadAsientos() {
                let conc = $('#formConcilia').getData(),
                    form = $('#formConcilia');
                if (conc.Pec_Cod === '') return $.alert('Seleccione Periodo Contable!');
                if (conc.Ban_Cod === '') return $.alert('Seleccione Cuenta Bancaria!');
                if (conc.Cob_Fec === '') return $.alert('Seleccione Fecha de Conciliaci&oacute;n!');
                $.getDataJson('', {
                    conc: conc,
                    newConciliacion: true
                }, function(r) {
                    if (r['success'] === true) {
                        $('#conciliacionForm').setRows(r.asientos);
                        form.setData(r.Cob_Last, 'last');
                        updateTotal();
                    }
                });
            }

            function updateTotal(rowId) {
                var t0 = performance.now();
                let gridConc = $("#conciliacionForm"),
                    acum = 0,
                    valMove = {},
                    rows = gridConc.getRowData(); //.getGridBatch(['conc_select','Asi_Cod','Asi_Sald','Com_Cod']);
                //console.log("Declaracion " + ((performance.now() - t0) / 1000) + " seconds.");
                t0 = performance.now();
                for (let i = 0, z = rows.length; i < z; i++) {
                    let addVal = rows[i].conc_select === 'S' || rows[i].Asi_Cod === 'no_id';
                    let val = (rows[i].Asi_Sald * (addVal ? 1 : -1));
                    if (rows[i].Asi_Cod * 1 === rowId) valMove = {
                        val: val,
                        tipo: rows[i].pago_tipo
                    };
                    if (addVal) {
                        acum += val;
                        rows[i].Cob_Disp = acum;
                    }
                    //gridConc.changeRowData(rows[i].Asi_Cod,{Cob_Disp:addVal?acum:null,Cod_Dat:addVal?rows[i].Cod_Dat:''},{trigger:false,excludeChangeCols:['conc_select','view']});
                    gridConc.setCell(rows[i].Asi_Cod, 'Cob_Disp', addVal ? acum : null);
                }
                console.log("Calculos " + ((performance.now() - t0) / 1000) + " seconds.");
                t0 = performance.now();
                totConc = acum.toFixed(2);
                gridConc.footerData('set', {
                    Cob_Disp: totConc
                });

                $('#Cob_Dis').attr('max', totConc).attr('min', totConc);
                console.log("Valores " + ((performance.now() - t0) / 1000) + " seconds.");
                t0 = performance.now();
                var i, groups = gridConc.jqGrid("getGridParam", "groupingView").groups,
                    l = groups.length,
                    idSelectorPrefix = "#" + gridConc[0].id + "ghead_0_";
                for (i = 0; i < l; i++) {
                    if (groups[i].value === valMove.tipo) { //console.log(groups[i].value,valMove.tipo,groups[i].summary[0].v,valMove.val);
                        groups[i].summary[0].v += valMove.val;
                        $(idSelectorPrefix + i + " ~ tr.jqfoot").first().find(">td[aria-describedby=conciliacionForm_Asi_Sald]").text(groups[i].summary[0].v.toFixed(2));

                    }
                }
                //console.log("Totales " + ((performance.now() - t0)/1000) + " seconds.");t0=performance.now();
                //gridConc.triggerHandler('jqGridAfterLoadComplete');
                //console.log("Trigger " + ((performance.now() - t0)/1000) + " seconds.");t0=performance.now();
            }

            function validarForm(newItem) {
                var data = {
                    form: $('#formConcilia').getData(),
                    updateConcilia: true,
                    asientos: $.map($("#conciliacionForm").getGridBatch(o => o.conc_select === 'S'), o => o.Asi_Cod)
                };
                /* aqui puedo poner validaciones */
                //console.log(data);
                $.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?', data, newItem ? createConcilia : updateConcilia);
            }

            function updateConcilia(data) {
                $.saveDataJson("", data, function(responce) {
                    $('#conciliacion').gridUpdate().loadUpdate();
                    $('#editDiv').moveComp('#buscaDiv').updateGridsSizes();
                });
            }
            //eliminar
            function validaEliminacion(Cob_Cod) {
                $.createDialogConfirm('¿Esta seguro de <u class="red"><b>ANULAR</b></u> la <b>Conciliación Bancaria</b>?', {
                    deleteConcilia: true,
                    Cob_Cod: Cob_Cod
                }, eliminaConciliacion);
            }

            function eliminaConciliacion(data) {
                //console.log(data);
                $.saveDataJson("", data, function(responce) {
                    $("#conciliacion").trigger("reloadGrid");
                });
            }

            function loadBancos(val) {
                let select = $('#searchConcilia').find('select[name="Ban_Cod"]');
                select.find('option[pec]').hide().detach().appendTo(selBan1);
                if (val === '') return;
                selBan1.find('option[pec="' + val + '"]').show().detach().appendTo(select);
                select.val('');
            }

            function loadBancos2(val) {
                let select = $('#formConcilia').find('select[name="Ban_Cod"]');
                select.find('option[pec]').hide().detach().appendTo(selBan2);
                if (val === '') return;
                selBan2.find('option[pec="' + val + '"]').show().detach().appendTo(select);
                select.val('');
            }

            function viewInfo(doc) {
                $('#docDetaDialog').dialog('open');
                $.getDataJson('', {
                    docDetalle: true,
                    Com_Cod: doc
                }, function(resp) {
                    $('#detaDocu').setRows(resp.asientos);
                    $('#docDetaDialog').setData(resp.comprobante).dialog('open');
                });
            }
            var gridRep, gridRepBanc, gridRepCta;

            function viewItem(Cob_Cod) {
                $.getDataJson('', {
                    viewConciliacion: true,
                    Cob_Cod: Cob_Cod
                }, function(r) {
                    gridRep.setRows(r.asientos);
                    console.log(gridRep.getGridBatch().length);
                    var i, groups = gridRep.jqGrid("getGridParam", "groupingView").groups,
                        l = groups.length,
                        tablas = {
                            consilia: [],
                            mayor: []
                        },
                        init = '';
                    for (i = 0; i < l; i++) {
                        if (groups[i].dataIndex === 'tipo') init = (groups[i].value === 'CONCILIADAS') ? 'consilia' : 'mayor';
                        else {
                            let add = true;
                            for (let j = 0, jl = tablas[init].length; j < jl; j++) {
                                if (tablas[init][j].pago_desc === groups[i].value) {
                                    tablas[init][j].val += groups[i].summary[0].v;
                                    add = false;
                                    break;
                                }
                            }
                            if (add) tablas[init].push({
                                pago_desc: groups[i].value,
                                val: groups[i].summary[0].v
                            });
                        }
                    }
                    $('#jqGridRepBanc').setRows(tablas.consilia);
                    $('#jqGridRepCta').setRows(tablas.mayor);
                    let tot_banc = $.round($('#jqGridRepBanc').getCol('val', false, 'sum')),
                        tot_mayor = $.round($('#jqGridRepCta').getCol('val', false, 'sum'));

                    $('#jqGridRepBanc').footerData('set', {
                        val: tot_banc,
                        pago_desc: '<div class="txt-right bold">(+) SUMA OPERACIONES:</div>'
                    });
                    $('#jqGridRepCta').footerData('set', {
                        val: tot_mayor,
                        pago_desc: '<div class="txt-right bold">(-) SUMA OP. EN TRANSITO:</div>'
                    });
                    //console.log(r);
                    //console.log("Total banco:"+tot_banc);
                    //No sumar el valor anterir del banco ya que esta en un ajuste al inicio del año(periodo) SOLO AL INICIO DEL AÑO

                    if (r.Cob && r.Cob.Cob_Fec) {
                        //console.log("Ingresa aqui");
                        let partesFecha = r.Cob.Cob_Fec.split('-');
                        let fecha = new Date(partesFecha[0], partesFecha[1] - 1, partesFecha[2]);
                        let dia = fecha.getDate(); // Obtener el día
                        let mes = fecha.getMonth() + 1; // Obtener el mes (sumar 1 porque los meses van de 0 a 11)
                        console.log(dia + "&&" + mes);
                        if (dia == 1 && mes == 1) {
                            //console.log("Debe ingresar aqui");
                            tot_banc = 0;
                            //r.Cob_Last.Cob_Dis = 0;

                        }
                    }
                    //console.log("Este dato :"+r.Cob_Last.Cob_Dis);

                    let data_form = $.extend({
                            Tot_Cob_Dis: (r.Cob_Last.Cob_Dis * 1 + tot_banc).toFixed(2),
                            Tot_Pld_Sal: (r.Mayor.Pld_Sal * 1 - tot_mayor).toFixed(2)
                        }, r.Cob_Last,
                        r.Cob,
                        r.Mayor || {}
                    );
                    $('#formConciliacion').setData(data_form);
                    $('#buscaDiv').moveComp('#viewDiv').updateGridsSizes();
                });
            }


            function reportTableExtra() {
                let table = $('#tableExtraInfo'),
                    data = $('#formConciliacion').getData();
                table.setData(data);
                let banco = table.find('table.banco').html($('#jqGridRepBanc').exportGridInnerHTML({
                        generated: false,
                        caption: true,
                        footer: true,
                        print: true,
                        removeHiddens: true
                    })),
                    banchead = banco.find('thead tr:nth-child(2)');
                banco.find('tbody:last-child tr td:last-child').css('border-top', '1px solid #666');
                $('<th style="text-align: center; border-bottom: 0.1pt solid rgb(102, 102, 102);white-space: nowrap; overflow: hidden;" colspan="2">BCO: ' + data.Pld_Des + '</th>').insertBefore(banchead);
                $('<tr><td style="padding-left: 5px; padding-right: 5px;border-bottom:1px solid rgb(102, 102, 102); white-space: nowrap; overflow: hidden;text-align:left;font-weight:bold;"> (&#187;) SALDO ANTERIOR BANCO:</td><td style="border-bottom:1px solid rgb(102, 102, 102); white-space: nowrap; overflow: hidden; text-align:right;padding-left: 5px; padding-right: 5px;">' + $.toFixed(data.Asi_Sald) + '</td></tr>').insertBefore(banchead);
                banco.append('<tbody><tr><td style="padding-left:5px;padding-right:5px;white-space:nowrap;overflow:hidden;"><b>(=) SALDO CTA. BANCARIA:</b></td><td style="text-align:right;border-bottom:1px dotted #666;border-top:1px solid #666;padding-left:5px;padding-right:5px;white-space:nowrap;overflow:hidden;"><b>' + $.toFixed(data.Tot_Cob_Dis) + '</b></td></tr></tbody>');
                let mayor = table.find('table.mayor').html($('#jqGridRepCta').exportGridInnerHTML({
                        generated: false,
                        caption: true,
                        footer: true,
                        print: true,
                        removeHiddens: true
                    })),
                    ctahead = mayor.find('thead tr:nth-child(2)');
                mayor.find('tbody:last-child tr td:last-child').css('border-top', '1px solid #666');
                $('<th style="text-align: center; border-bottom: 0.1pt solid rgb(102, 102, 102);white-space: nowrap; overflow: hidden;" colspan="2">CTA: ' + data.Pld_Cdc + ' - ' + data.Pld_Des + '</th>').insertBefore(ctahead);
                $('<tr><td style="padding-left: 5px; padding-right: 5px;border-bottom:1px solid rgb(102, 102, 102);white-space: nowrap; overflow: hidden;text-align:left;font-weight:bold;"> (&#187;) SALDO LIBRO MAYOR:</td><td style="border-bottom:1px solid rgb(102, 102, 102); white-space: nowrap; overflow: hidden; text-align:right;padding-left: 5px; padding-right: 5px;">' + $.toFixed(data.Pld_Sal) + '</td></tr>').insertBefore(ctahead);
                mayor.append('<tbody><tr><td style="border-bottom:1px dotted #666;padding-left:5px;padding-right:5px;white-space:nowrap;overflow:hidden;"><b>(=) SALDO CTA. BANCARIA:</b></td><td style="text-align:right;border-bottom:1px dotted #666;border-top:1px solid #666;padding-left:5px;padding-right:5px;white-space:nowrap;overflow:hidden;"><b>' + $.toFixed(data.Tot_Pld_Sal) + '</b></td></tr></tbody>');
                this.html(table.html());
            }



            function imprimir() {
                let grid = gridRep,
                    divRep = $('#imprimir'),
                    opts = {
                        generated: false,
                        print: true,
                        caption: true,
                        footer: true,
                        bodyBorder: false,
                        removeHiddens: true,
                        removeCols: [6]
                    },
                    tableExtra = $('#tablereportExtra').html(''),
                    tableRep = $('#tablaReporte').html(grid.jqGrid('exportGridInnerHTML', opts));
                divRep.find('span.subtitle').html('').parent().parent().hide();
                reportTableExtra.call(tableExtra, divRep);
                divRep.printElement({
                    pageTitle: "EXA Ofsercont",
                    printMode: 'popup',
                    icon: "glyphicon glyphicon-print",
                    printCss: true
                });
            }

            function exportar(pageExcel) {
                let grid = gridRep,
                    opts = {
                        excel: true,
                        generated: false,
                        caption: true,
                        bodyBorder: false,
                        footer: true,
                        sepEnd: true,
                        removeHiddens: true,
                        removeCols: [6]
                    },
                    divRep = $('#exportar'),
                    temp = $('<div></div>'),
                    data = $('#formConciliacion').getData();
                $('#exportar').find('span.subtitle').html(data.Pld_Cdc + ' - ' + data.Pld_Des + ' al ' + data.Cob_Fec);
                temp.html(divRep.html()).append(grid.jqGrid('exportGridHTML', opts));
                let last = temp.find('tbody:last').prev().find('>tr:last'),
                    newTr = last.clone();
                newTr.find('>td:last').html($.numFormat(data.Tot_Cob_Dis)).prev().html("SALDO ACTUAL");
                newTr.insertAfter(last);
                newTr = last.clone();
                newTr.find('>td:last').html($.numFormat(data.Asi_Sald)).prev().html("SALDO ANTERIOR");
                newTr.insertBefore(temp.find('thead:last>tr:nth-child(2)'));
                $.downloadFile($.exportarExcelBlob(temp.html(), pageExcel || 'report'), (pageExcel || 'report') + '_' + $.getDate() + '.xls');
            }
        </script>
    </body>

</html>