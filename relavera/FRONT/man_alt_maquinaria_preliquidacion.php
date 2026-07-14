<?php

/**
 * Formulario y Gestión de Preliquidación de Maquinaria - Relavera
 * Permite consolidar información operacional previa a la liquidación.
 * @author Sistema EXA
 * @version 1.0
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_maquinaria_preliquidacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Maquinaria_Preliquidacion($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Maquinaria_Preliquidacion;

// ==================== AJAX HANDLERS ====================

if (isset($_GET['listMaquinasAjax'])) {
    $rows_data = $obBD_con1->getArrayConsulta(1, array('Emp_Cod' => $_SESSION['Ses_Emp_Cod']), $obBD_conexion);
    $obBD_con1->utf8_change_param($rows_data);
    $obBD_con1->echoJson($rows_data);
    exit;
}

if (isset($_GET['listOperadoresAjax'])) {
    $rows_data = $obBD_con1->getArrayConsulta(2, array('Emp_Cod' => $_SESSION['Ses_Emp_Cod']), $obBD_conexion);
    $obBD_con1->utf8_change_param($rows_data);
    $obBD_con1->echoJson($rows_data);
    exit;
}

if (isset($_GET['getLastOperatorAjax'])) {
    $veh_cod = $_GET['veh_cod'];
    $row = $obBD_con1->getRowConsulta(15, array('Veh_Cod' => $veh_cod), $obBD_conexion);
    if ($row && isset($row['Cho_Cod'])) {
        echo json_encode(array('success' => true, 'Cho_Cod' => $row['Cho_Cod']));
    } else {
        echo json_encode(array('success' => false));
    }
    exit;
}

if (isset($_POST['generarPreliquidacionAjax'])) {
    $fecha_ini = isset($_POST['fecha_ini']) ? $_POST['fecha_ini'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    $veh_cod = isset($_POST['veh_cod']) ? $_POST['veh_cod'] : '';
    $cho_cod = isset($_POST['cho_cod']) ? $_POST['cho_cod'] : '';

    $params = array(
        'fecha_ini' => $fecha_ini,
        'fecha_fin' => $fecha_fin,
        'Veh_Cod' => $veh_cod,
        'Cho_Cod' => $cho_cod
    );

    $horometros = $obBD_con1->getArrayConsulta(3, $params, $obBD_conexion);
    $combustible = $obBD_con1->getArrayConsulta(4, $params, $obBD_conexion);
    $compras = array(); // Vacío temporalmente hasta definir tabla de Compras

    if (!is_array($horometros)) {
        $horometros = array();
    }
    if (!is_array($combustible)) {
        $combustible = array();
    }

    $obBD_con1->utf8_change_param($horometros);
    $obBD_con1->utf8_change_param($combustible);
    // $obBD_con1->utf8_change_param($compras); // No necesario para array vacío

    // Obtener siguiente AUTO_INCREMENT y generar Mal_Num estimado
    $next_id_query = $obBD_con1->getArrayConsulta(14, array(), $obBD_conexion);
    $next_id = isset($next_id_query[0]['next_id']) ? $next_id_query[0]['next_id'] : 1;
    $next_mal_num = 'PRE-' . str_pad($next_id, 6, '0', STR_PAD_LEFT);

    $total_horas = 0;
    foreach ($horometros as $h) {
        $total_horas += (float)$h['horas_trab'];
    }

    $total_comb_gal = 0;
    $total_comb_cost = 0;
    foreach ($combustible as $c) {
        $total_comb_gal += (float)$c['cantidad'];
        $total_comb_cost += (float)$c['costo'];
    }

    $total_compras = 0;

    echo json_encode(array(
        'success' => true,
        'resumen' => array(
            'next_mal_num' => $next_mal_num,
            'total_horas' => $total_horas,
            'total_combustible' => $total_comb_gal,
            'costo_combustible' => $total_comb_cost,
            'total_compras' => $total_compras,
            'costo_total_referencial' => $total_comb_cost
        ),
        'horometro' => $horometros ? $horometros : array(),
        'combustible' => $combustible ? $combustible : array(),
        'compras' => $compras ? $compras : array()
    ));
    exit;
}

if (isset($_POST['guardarPreliquidacionAjax'])) {
    $fecha_ini = isset($_POST['fecha_ini']) ? $_POST['fecha_ini'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    $veh_cod = isset($_POST['veh_cod']) ? $_POST['veh_cod'] : '';
    $cho_cod = isset($_POST['cho_cod']) ? $_POST['cho_cod'] : '';
    $observacion = isset($_POST['observacion']) ? trim($_POST['observacion']) : '';
    $mal_tot_hor = isset($_POST['mal_tot_hor']) ? (float)$_POST['mal_tot_hor'] : 0;
    $mal_des_hor = isset($_POST['mal_des_hor']) ? (float)$_POST['mal_des_hor'] : 0;
    $mal_tot_des = isset($_POST['mal_tot_des']) ? (float)$_POST['mal_tot_des'] : 0;
    $mal_tot_cob = isset($_POST['mal_tot_cob']) ? (float)$_POST['mal_tot_cob'] : 0;

    $has_hor = isset($_POST['has_hor']) && $_POST['has_hor'] == '1';
    $has_com = isset($_POST['has_com']) && $_POST['has_com'] == '1';
    $usu_cod = isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 'NULL';

    if (!$has_hor && !$has_com) {
        echo json_encode(array('success' => false, 'message' => 'No existen registros pendientes de horómetro o combustible.'));
        exit;
    }

    $obBD_con1->inicio_transaccion($obBD_conexion);

    // 2. Insertar Cabecera
    $params_cab = array(
        'Veh_Cod' => $veh_cod,
        'Usu_Cod' => $usu_cod,
        'Mal_Fec_Ini' => $fecha_ini,
        'Mal_Fec_Fin' => $fecha_fin,
        'Mal_Obs' => $observacion,
        'Mal_Tot_Hor' => $mal_tot_hor,
        'Mal_Des_Hor' => $mal_des_hor,
        'Mal_Tot_Des' => $mal_tot_des,
        'Mal_Tot_Cob' => $mal_tot_cob
    );

    $obBD_con1->operacionobBD(10, $params_cab, $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        echo json_encode(array('success' => false, 'message' => 'Error al guardar cabecera.'));
        exit;
    }

    // Obtener ID insertado
    $mal_cod = $obBD_con1->insercionid($obBD_conexion);
    if (!$mal_cod) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        echo json_encode(array('success' => false, 'message' => 'No se pudo obtener el ID del manifiesto. Revise que Mal_Cod sea AUTO_INCREMENT.'));
        exit;
    }

    // Auto-generar Mal_Num y actualizar la cabecera
    $mal_num = 'PRE-' . str_pad($mal_cod, 6, '0', STR_PAD_LEFT);
    $obBD_con1->operacionobBD(13, array('Mal_Num' => $mal_num, 'Mal_Cod' => $mal_cod), $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        echo json_encode(array('success' => false, 'message' => 'Error al generar número de manifiesto.'));
        exit;
    }

    // 4. Actualizar Horometros
    if ($has_hor) {
        $params_hor = array(
            'Mal_Cod' => $mal_cod,
            'Veh_Cod' => $veh_cod,
            'Cho_Cod' => $cho_cod,
            'fecha_ini' => $fecha_ini,
            'fecha_fin' => $fecha_fin
        );
        $obBD_con1->operacionobBD(11, $params_hor, $obBD_conexion);
        if ($obBD_con1->Error != 0) {
            $obBD_con1->rollBack_nomsn($obBD_conexion);
            echo json_encode(array('success' => false, 'message' => 'Error al enlazar horómetros.'));
            exit;
        }
    }

    // 5. Actualizar Combustible
    if ($has_com) {
        $params_com = array(
            'Mal_Cod' => $mal_cod,
            'Veh_Cod' => $veh_cod,
            'Cho_Cod' => $cho_cod,
            'fecha_ini' => $fecha_ini,
            'fecha_fin' => $fecha_fin
        );
        $obBD_con1->operacionobBD(12, $params_com, $obBD_conexion);
        if ($obBD_con1->Error != 0) {
            $obBD_con1->rollBack_nomsn($obBD_conexion);
            echo json_encode(array('success' => false, 'message' => 'Error al enlazar combustible.'));
            exit;
        }
    }

    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    echo json_encode(array('success' => true, 'message' => 'Preliquidación guardada exitosamente.', 'Mal_Cod' => $mal_cod));
    exit;
}

if (isset($_POST['listMasivoAjax'])) {
    $fecha_ini = $_POST['fecha_ini'];
    $fecha_fin = $_POST['fecha_fin'];
    $descuento = (float)$_POST['descuento'];

    $params = array('fecha_ini' => $fecha_ini, 'fecha_fin' => $fecha_fin);

    // Consultar horometros agrupados
    $horometros = $obBD_con1->getArrayConsulta(16, $params, $obBD_conexion);
    // Consultar combustible agrupado
    $combustibles = $obBD_con1->getArrayConsulta(17, $params, $obBD_conexion);

    // Unir por Veh_Cod + Cho_Cod
    $mapa_comb = array();
    if ($combustibles) {
        foreach ($combustibles as $c) {
            $key = $c['Veh_Cod'] . '_' . $c['Cho_Cod'];
            $mapa_comb[$key] = $c;
        }
    }

    $resultado = array();
    if ($horometros) {
        foreach ($horometros as $h) {
            $key = $h['Veh_Cod'] . '_' . $h['Cho_Cod'];

            $comb = isset($mapa_comb[$key]) ? $mapa_comb[$key] : null;

            $total_horas = (float)$h['total_horas'];
            $valor_hora = (float)$h['valor_hora'];
            $subtotal = $total_horas * $valor_hora;
            $total_descuento = $total_horas * $descuento;
            $total_cobrar = $subtotal - $total_descuento;

            $combustible_cargado = $comb ? (float)$comb['combustible_cargado'] : 0;
            $costo_combustible = $comb ? (float)$comb['costo_combustible'] : 0;
            $total_despachos = $comb ? (int)$comb['total_despachos'] : 0;

            $resultado[] = array(
                'Veh_Cod' => $h['Veh_Cod'],
                'Cho_Cod' => $h['Cho_Cod'],
                'vehiculo_desc' => $h['vehiculo_desc'],
                'chofer_desc' => $h['chofer_desc'],
                'total_registros_horometro' => $h['total_registros_horometro'],
                'total_horas' => $total_horas,
                'valor_hora' => $valor_hora,
                'descuento_hora' => $descuento,
                'total_descuento' => $total_descuento,
                'total_cobrar' => $total_cobrar,
                'combustible_cargado' => $combustible_cargado,
                'costo_combustible' => $costo_combustible,
                'total_despachos' => $total_despachos
            );
        }
    }

    $obBD_con1->utf8_change_param($resultado);
    echo json_encode(array('success' => true, 'data' => $resultado));
    exit;
}

if (isset($_POST['getDetalleMasivoRowAjax'])) {
    $fecha_ini = $_POST['fecha_ini'];
    $fecha_fin = $_POST['fecha_fin'];
    $veh_cod = $_POST['veh_cod'];
    $cho_cod = $_POST['cho_cod'];

    $params = array('fecha_ini' => $fecha_ini, 'fecha_fin' => $fecha_fin, 'Veh_Cod' => $veh_cod, 'Cho_Cod' => $cho_cod);

    $horometros = $obBD_con1->getArrayConsulta(3, $params, $obBD_conexion);
    $combustibles = $obBD_con1->getArrayConsulta(4, $params, $obBD_conexion);

    $obBD_con1->utf8_change_param($horometros);
    $obBD_con1->utf8_change_param($combustibles);

    echo json_encode(array(
        'success' => true,
        'horometros' => $horometros ? $horometros : array(),
        'combustibles' => $combustibles ? $combustibles : array()
    ));
    exit;
}

if (isset($_POST['guardarMasivoAjax'])) {
    $fecha_ini = $_POST['fecha_ini'];
    $fecha_fin = $_POST['fecha_fin'];
    $descuento = (float)$_POST['descuento'];
    $observacion = $_POST['observacion'];
    $seleccionados = isset($_POST['seleccionados']) ? $_POST['seleccionados'] : array();
    $usu_cod = isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 'NULL';

    if (empty($seleccionados)) {
        echo json_encode(array('success' => false, 'message' => 'No hay filas seleccionadas.'));
        exit;
    }

    $params = array('fecha_ini' => $fecha_ini, 'fecha_fin' => $fecha_fin);
    $horometros = $obBD_con1->getArrayConsulta(16, $params, $obBD_conexion);

    $mapa_hor = array();
    if ($horometros) {
        foreach ($horometros as $h) {
            $key = $h['Veh_Cod'] . '_' . $h['Cho_Cod'];
            $mapa_hor[$key] = $h;
        }
    }

    $obBD_con1->inicio_transaccion($obBD_conexion);

    foreach ($seleccionados as $key) {
        if (!isset($mapa_hor[$key])) continue;
        $h = $mapa_hor[$key];

        $total_horas = (float)$h['total_horas'];
        $valor_hora = (float)$h['valor_hora'];
        $subtotal = $total_horas * $valor_hora;
        $total_descuento = $total_horas * $descuento;
        $total_cobrar = $subtotal - $total_descuento;

        // Insertar cabecera
        $params_cab = array(
            'Veh_Cod' => $h['Veh_Cod'],
            'Usu_Cod' => $usu_cod,
            'Mal_Fec_Ini' => $fecha_ini,
            'Mal_Fec_Fin' => $fecha_fin,
            'Mal_Obs' => $observacion,
            'Mal_Tot_Hor' => $total_horas,
            'Mal_Des_Hor' => $descuento,
            'Mal_Tot_Des' => $total_descuento,
            'Mal_Tot_Cob' => $total_cobrar
        );
        $obBD_con1->operacionobBD(10, $params_cab, $obBD_conexion);
        if ($obBD_con1->Error != 0) {
            $obBD_con1->rollBack_nomsn($obBD_conexion);
            echo json_encode(array('success' => false, 'message' => 'Error al guardar cabecera para vehículo ' . $h['Veh_Cod']));
            exit;
        }

        $mal_cod = $obBD_con1->insercionid($obBD_conexion);
        $mal_num = 'PRE-' . str_pad($mal_cod, 6, '0', STR_PAD_LEFT);

        $obBD_con1->operacionobBD(13, array('Mal_Num' => $mal_num, 'Mal_Cod' => $mal_cod), $obBD_conexion);

        $params_upd = array(
            'Mal_Cod' => $mal_cod,
            'Veh_Cod' => $h['Veh_Cod'],
            'Cho_Cod' => $h['Cho_Cod'],
            'fecha_ini' => $fecha_ini,
            'fecha_fin' => $fecha_fin
        );

        // Actualizar horometros del vehiculo
        $obBD_con1->operacionobBD(11, $params_upd, $obBD_conexion);

        // Actualizar combustible del vehiculo
        $obBD_con1->operacionobBD(12, $params_upd, $obBD_conexion);
    }

    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    echo json_encode(array('success' => true, 'message' => 'Se generaron ' . count($seleccionados) . ' preliquidaciones con éxito.'));
    exit;
}

if (isset($_GET['listRealizadasAjax'])) {
    $params = array(
        'fil_hist_est' => isset($_GET['fil_hist_est']) ? $_GET['fil_hist_est'] : '',
        'fil_hist_veh' => isset($_GET['fil_hist_veh']) ? $_GET['fil_hist_veh'] : '',
        'fil_hist_doc' => isset($_GET['fil_hist_doc']) ? $_GET['fil_hist_doc'] : ''
    );
    $rows = $obBD_con1->getArrayConsulta(6, $params, $obBD_conexion);
    $obBD_con1->utf8_change_param($rows);
    echo json_encode(array('success' => true, 'data' => $rows));
    exit;
}

if (isset($_GET['getDetalleHistoricoAjax'])) {
    $mal_cod = $_GET['mal_cod'];

    $horometros = $obBD_con1->getArrayConsulta(8, array('Mal_Cod' => $mal_cod), $obBD_conexion);
    $combustible = $obBD_con1->getArrayConsulta(9, array('Mal_Cod' => $mal_cod), $obBD_conexion);
    $cabecera_arr = $obBD_con1->getArrayConsulta(18, array('Mal_Cod' => $mal_cod), $obBD_conexion);

    $obBD_con1->utf8_change_param($horometros);
    $obBD_con1->utf8_change_param($combustible);
    $obBD_con1->utf8_change_param($cabecera_arr);

    $cabecera = count($cabecera_arr) > 0 ? $cabecera_arr[0] : null;

    $total_horas = 0;
    foreach ($horometros as $h) {
        $total_horas += (float)$h['horas_trab'];
    }

    $total_comb_gal = 0;
    $total_comb_cost = 0;
    foreach ($combustible as $c) {
        $total_comb_gal += (float)$c['cantidad'];
        $total_comb_cost += (float)$c['costo'];
    }

    // De la cabecera, podemos sacar el Cho_Cod que usaremos si queremos mostrar al chofer
    $cho_cod = count($horometros) > 0 ? $horometros[0]['Cho_Cod'] : '';
    $chofer_desc = count($horometros) > 0 ? $horometros[0]['chofer'] : 'NO SELECCIONADO';
    $vehiculo_desc = $cabecera ? $cabecera['vehiculo_desc'] : 'NO SELECCIONADO';

    echo json_encode(array(
        'success' => true,
        'resumen' => array(
            'total_horas' => $cabecera ? (float)$cabecera['Mal_Tot_Hor'] : $total_horas,
            'total_combustible' => $total_comb_gal,
            'costo_combustible' => $total_comb_cost,
            'total_compras' => 0,
            'costo_total_referencial' => $total_comb_cost,
            'next_mal_num' => $cabecera ? $cabecera['Mal_Num'] : '',
            'vehiculo_desc' => $vehiculo_desc,
            'chofer_desc' => $chofer_desc,
            'Mal_Des_Hor' => $cabecera ? (float)$cabecera['Mal_Des_Hor'] : 0,
            'Mal_Tot_Des' => $cabecera ? (float)$cabecera['Mal_Tot_Des'] : 0,
            'Mal_Tot_Cob' => $cabecera ? (float)$cabecera['Mal_Tot_Cob'] : 0,
            'Mal_Fec_Ini' => $cabecera ? $cabecera['Mal_Fec_Ini'] : '',
            'Mal_Fec_Fin' => $cabecera ? $cabecera['Mal_Fec_Fin'] : ''
        ),
        'horometro' => $horometros ? $horometros : array(),
        'combustible' => $combustible ? $combustible : array(),
        'compras' => array()
    ));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Preliquidación de Maquinaria</title>

    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <?php require_once('../../mascaras/model3/estilos/estilos.php'); ?>
    <!-- Scripts Generales -->
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>

    <!-- Modal Visor de Imágenes -->
    <div class="modal fade" id="modalVerImagenes" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background:#3b82f6; color:#fff;">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-image"></i> Evidencias del Horómetro</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-6 text-center">
                            <h5>Evidencia Inicial</h5>
                            <div id="visor_preliq_img_ini" style="width:100%; min-height:200px; background:#e2e8f0; display:flex; align-items:center; justify-content:center; border:1px solid #cbd5e1; border-radius:4px; overflow:hidden;">
                                <span class="text-muted">Sin imagen</span>
                            </div>
                        </div>
                        <div class="col-sm-6 text-center">
                            <h5>Evidencia Final</h5>
                            <div id="visor_preliq_img_fin" style="width:100%; min-height:200px; background:#e2e8f0; display:flex; align-items:center; justify-content:center; border:1px solid #cbd5e1; border-radius:4px; overflow:hidden;">
                                <span class="text-muted">Sin imagen</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de Validaciones de la Pantalla -->
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script language="javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>

    <!-- Módulo CSS -->
    <link rel="stylesheet" type="text/css" href="../RECURSOS/maquinaria_preliquidacion.css?v=1">
</head>

<body style="background:#f1f5f9; padding: 15px;">

    <!-- ENCABEZADO -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-sm-12">
            <div class="panel panel-default" style="border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: none;">
                <div class="panel-heading exa-header" style="background: #334a5f; color: #fff; border-radius: 8px 8px 0 0; padding: 15px 20px;">
                    <div class="row">
                        <div class="col-sm-8">
                            <h3 style="margin:0; font-weight: 600;"><i class="fa fa-calculator"></i> Preliquidación de Maquinaria</h3>
                            <small style="color:#cbd5e1;">Consolidador Operacional - Generación de documento previo a liquidación definitiva</small>
                        </div>
                        <div class="col-sm-4 text-right">
                            <h4 style="margin:0; font-weight: 600;" id="lblEstadoVisual"><span class="label label-warning">PENDIENTE</span></h4>
                            <small style="color:#cbd5e1;">Nuevo Documento</small>
                        </div>
                    </div>
                </div>
                <div class="panel-body" style="background-color: #DFE9F6 !important;">

                    <!-- SELECTOR DE MODO -->
                    <div class="row" style="margin: 0 0 15px 0;">
                        <div class="col-sm-12 text-center">
                            <style>
                                .mode-selector label.btn { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-weight: bold; transition: all 0.2s; }
                                .mode-selector label.btn:hover { background: #e2e8f0; }
                                .mode-selector label.btn.active { background: #2563eb; color: #fff; border-color: #1d4ed8; box-shadow: 0 2px 4px rgba(37,99,235,0.3); }
                                .mode-selector label.btn.active i { color: #fff; }
                                .mode-selector label.btn i { color: #64748b; }
                            </style>
                            <div class="btn-group mode-selector" data-toggle="buttons">
                                <label class="btn active" id="btn_modo_ind" onclick="cambiarModo('individual')">
                                    <input type="radio" name="modo_generacion" autocomplete="off" checked> <i class="fa fa-file-text-o"></i> Individual
                                </label>
                                <label class="btn" id="btn_modo_mas" onclick="cambiarModo('masivo')">
                                    <input type="radio" name="modo_generacion" autocomplete="off"> <i class="fa fa-copy"></i> Por Lotes
                                </label>
                            </div>
                            <div id="hlp_modo_ind" style="margin-top: 8px; font-size: 13px; color: #64748b;">Permite revisar detalladamente una sola maquinaria y generar una preliquidación individual.</div>
                            <div id="hlp_modo_mas" style="margin-top: 8px; font-size: 13px; color: #64748b; display: none;">Busca automáticamente todas las maquinarias con registros pendientes dentro del período seleccionado y permite generar múltiples preliquidaciones independientes.</div>
                        </div>
                    </div>

                    <!-- FILTROS MODO INDIVIDUAL -->
                    <div id="div_filtros_individual" class="row" style="background: #f8fafc; padding: 15px; padding-bottom: 120px; border-radius: 6px; margin: 0 0 20px 0; border: 1px solid #e2e8f0;">
                        <div class="col-sm-2">
                            <label>Fecha Inicial:</label>
                            <input type="date" id="fil_fec_ini" class="form-control input-sm" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="col-sm-2">
                            <label>Fecha Final:</label>
                            <input type="date" id="fil_fec_fin" class="form-control input-sm" value="<?php echo date('Y-m-t'); ?>">
                        </div>
                        <div class="col-sm-3">
                            <label>Vehículo / Maquinaria:</label>
                            <select id="fil_vehiculo" class="form-control input-sm chosen-select" data-placeholder="Seleccione Vehículo..." onchange="buscarUltimoOperador(this.value)">
                                <option value="">Todos los Vehículos</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label>Operador / Chofer:</label>
                            <select id="fil_operador" class="form-control input-sm chosen-select" data-placeholder="Seleccione Operador...">
                                <option value="">Todos los Operadores</option>
                            </select>
                        </div>
                        <div class="col-sm-2 text-right">
                            <label>&nbsp;</label><br>
                            <button type="button" class="btn btn-sm btn-primary" onclick="generarPreliquidacion()"><i class="fa fa-search"></i> Generar</button>
                            <button type="button" class="btn btn-sm btn-default" onclick="limpiarFiltros()"><i class="fa fa-eraser"></i> Limpiar</button>
                        </div>
                    </div>

                    <!-- FILTROS MODO MASIVO -->
                    <div id="div_filtros_masivo" class="row" style="display:none; background: #f8fafc; padding: 15px; border-radius: 6px; margin: 0 0 20px 0; border: 1px solid #e2e8f0;">
                        <div class="col-sm-2">
                            <label>Fecha Inicial:</label>
                            <input type="date" id="fil_mas_fec_ini" class="form-control input-sm" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="col-sm-2">
                            <label>Fecha Final:</label>
                            <input type="date" id="fil_mas_fec_fin" class="form-control input-sm" value="<?php echo date('Y-m-t'); ?>">
                        </div>
                        <div class="col-sm-2">
                            <label>Dcto x Hora ($):</label>
                            <input type="number" id="fil_mas_desc" class="form-control input-sm text-right" value="0.00" step="0.01" min="0">
                            <small style="color:#64748b; font-size:11px; display:block; margin-top:4px; line-height:1.2;">Valor que se aplicará inicialmente a todas las preliquidaciones generadas.</small>
                        </div>
                        <div class="col-sm-4">
                            <label>Observación General (opcional):</label>
                            <input type="text" id="fil_mas_obs" class="form-control input-sm" placeholder="Aplica para todas las generadas en lote">
                        </div>
                        <div class="col-sm-2 text-right">
                            <label>&nbsp;</label><br>
                            <button type="button" class="btn btn-sm btn-primary" onclick="buscarPendientesMasivo()"><i class="fa fa-search"></i> Buscar Pendientes</button>
                            <button type="button" class="btn btn-sm btn-default" onclick="location.reload();"><i class="fa fa-eraser"></i> Limpiar</button>
                        </div>
                    </div>

                    <!-- TARJETAS KPI (SOLO INDIVIDUAL) -->
                    <div id="div_kpis_individual" class="row" style="margin-bottom: 20px;">
                        <div class="col-md-2 col-sm-4 col-xs-6 mb-2">
                            <div class="kpi-card text-center" style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:15px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                <div style="font-size:11px; color:#64748b; font-weight:bold; text-transform:uppercase;">Horas Trabajadas</div>
                                <div style="font-size:22px; color:#3b82f6; font-weight:bold;" id="kpi_horas">0.0</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6 mb-2">
                            <div class="kpi-card text-center" style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:15px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                <div style="font-size:11px; color:#64748b; font-weight:bold; text-transform:uppercase;">Comb. Cargado</div>
                                <div style="font-size:22px; color:#ca8a04; font-weight:bold;" id="kpi_comb_gal">0 Gls</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-xs-6 mb-2">
                            <div class="kpi-card text-center" style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:15px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                <div style="font-size:11px; color:#64748b; font-weight:bold; text-transform:uppercase;">Costo Comb.</div>
                                <div style="font-size:22px; color:#ef4444; font-weight:bold;" id="kpi_comb_cost">$0.00</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-xs-6 mb-2">
                            <div class="kpi-card text-center" style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:15px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                <div style="font-size:11px; color:#64748b; font-weight:bold; text-transform:uppercase;">Compras / Gastos</div>
                                <div style="font-size:22px; color:#8b5cf6; font-weight:bold;" id="kpi_compras">$0.00</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-6 mb-2">
                            <div class="kpi-card text-center" style="background:#2px solid #0f172a; border-radius:8px; padding:13px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <div style="font-size:11px; color:#334155; font-weight:bold; text-transform:uppercase;">Costo Total Ref.</div>
                                <div style="font-size:24px; color:#0f172a; font-weight:bold;" id="kpi_total">$0.00</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TABLA MASIVA (SOLO MASIVO) -->
                    <div id="div_tabla_masiva" style="display:none; background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <h4 style="margin-top:0; border-bottom: 2px solid #3b82f6; padding-bottom:4px;"><i class="fa fa-users text-primary"></i> Preliquidaciones Pendientes</h4>
                        <small style="color:#64748b; display:block; margin-bottom:10px; font-size:13px;">Cada fila representa una preliquidación que puede generarse de forma independiente.</small>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="tblMasivo">
                                <thead style="background:#f1f5f9;">
                                    <tr>
                                        <th width="60" class="text-center">Generar<br><input type="checkbox" id="chkMasivoAll" onclick="toggleMasivoAll(this)"></th>
                                        <th>Vehículo / Máquina</th>
                                        <th>Operador / Chofer</th>
                                        <th class="text-right">Hrs Incs</th>
                                        <th class="text-right">Horas Trab</th>
                                        <th class="text-right">D. Comb</th>
                                        <th class="text-right">Comb. Gls</th>
                                        <th class="text-right">Val. Hora</th>
                                        <th class="text-right">Dcto/Hr</th>
                                        <th class="text-right">Tot. Dcto</th>
                                        <th class="text-right">TOTAL</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="13" class="text-center text-muted"><i class="fa fa-info-circle"></i> Utilice los filtros para buscar pendientes.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center" style="margin-top: 15px;">
                            <button class="btn btn-success btn-lg" id="btnGuardarMasivo" disabled onclick="guardarMasivoAjax()"><i class="fa fa-cogs"></i> Generar Lotes</button>
                            <small style="color:#64748b; display:block; margin-top:8px; font-size:13px;">Se creará una preliquidación independiente por cada maquinaria seleccionada.</small>
                        </div>
                        <div class="alert alert-info text-left" style="margin-top:25px; font-size:13px; line-height:1.5; background-color:#e0f2fe; border-color:#bae6fd; color:#0369a1;">
                            <strong><i class="fa fa-info-circle"></i> Información Importante:</strong><br>
                            Las preliquidaciones generadas por lote son documentos independientes.<br>
                            Cada maquinaria conserva su propio número de preliquidación, total de horas, descuento aplicado y total a cobrar.<br>
                            La generación por lote únicamente automatiza el proceso de creación de múltiples documentos.
                        </div>
                    </div>

                    <!-- PESTAÑAS (SOLO INDIVIDUAL) -->
                    <div id="div_tabs_individual">
                        <ul class="nav nav-tabs custom-tabs" style="border-bottom: 2px solid #cbd5e1;" id="tabsDetalle">
                            <li class="active"><a href="#tab-realizadas" data-toggle="tab" onclick="cargarRealizadas()"><i class="fa fa-history text-success"></i> Consulta Historial</a></li>
                            <li><a href="#tab-horometro" data-toggle="tab"><i class="fa fa-clock-o text-info"></i> Horómetro <span class="badge" id="bdg_hor" style="background:#3b82f6;">0</span></a></li>
                            <li><a href="#tab-combustible" data-toggle="tab"><i class="fa fa-tint text-warning"></i> Combustible <span class="badge" id="bdg_comb" style="background:#ca8a04;">0</span></a></li>
                            <li><a href="#tab-compras" data-toggle="tab"><i class="fa fa-shopping-cart text-purple"></i> Compras / Gastos <span class="badge" id="bdg_com" style="background:#8b5cf6;">0</span></a></li>
                            <li><a href="#tab-resumen" data-toggle="tab"><i class="fa fa-list-alt text-primary"></i> Resumen Preliquidado</a></li>
                        </ul>
                        <div class="tab-content" style="background-color: #DFE9F6 !important; border: 1px solid #ddd; border-top: none; padding: 20px;">

                            <!-- TAB REALIZADAS (HISTORIAL) -->
                            <div class="tab-pane active" id="tab-realizadas">
                                <!-- FILTROS DE BUSQUEDA HISTORIAL -->
                                <div class="well well-sm" style="background:#f8fafc; border:1px solid #e2e8f0;">
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <label class="control-label text-xs">Estado:</label>
                                            <select id="fil_hist_est" class="form-control input-sm">
                                                <option value="">Todos</option>
                                                <option value="P">Preliquidado</option>
                                                <option value="L">Liquidado</option>
                                                <option value="I">Anulado</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="control-label text-xs">Vehículo (Placa):</label>
                                            <input type="text" id="fil_hist_veh" class="form-control input-sm" placeholder="Ej. ABC-1234">
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="control-label text-xs">Nº Preliquidación:</label>
                                            <input type="text" id="fil_hist_doc" class="form-control input-sm" placeholder="Ej. PRE-000001">
                                        </div>
                                        <div class="col-sm-2 text-right">
                                            <label class="control-label">&nbsp;</label><br>
                                            <button type="button" class="btn btn-primary btn-sm btn-block" onclick="cargarRealizadas()"><i class="fa fa-search"></i> Buscar</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover" id="tblRealizadas">
                                        <thead style="background:#10b981; color:#fff;">
                                            <tr>
                                                <th>Nº Preliq</th>
                                                <th>Fecha/Hora</th>
                                                <th>Vehículo</th>
                                                <th>Periodo de Datos</th>
                                                <th>Usuario Generador</th>
                                                <th>Estado</th>
                                                <th width="80">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted"><i class="fa fa-info-circle"></i> Aquí aparecerá el historial de preliquidaciones.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB RESUMEN -->
                            <div class="tab-pane" id="tab-resumen">
                                <h3 style="margin-top:0; border-bottom: 2px solid #3b82f6; padding-bottom:10px;"><i class="fa fa-file-text-o text-primary"></i> Documento de Preliquidación</h3>

                                <div class="row">
                                    <!-- BLOQUE 1: INFORMACIÓN GENERAL -->
                                    <div class="col-md-6">
                                        <div class="panel panel-default">
                                            <div class="panel-heading" style="background:#f8fafc;"><strong>1. Información General</strong></div>
                                            <div class="panel-body" style="padding: 10px;">
                                                <table class="table table-condensed" style="margin: 0; border: none;">
                                                    <tr>
                                                        <th style="border:none;">Nº Preliquidación:</th>
                                                        <td style="border:none;" id="res_num_doc" class="text-primary font-weight-bold">NUEVO DOCUMENTO</td>
                                                    </tr>
                                                    <tr>
                                                        <th style="border:none;">Vehículo / Máquina:</th>
                                                        <td style="border:none;" id="res_vehiculo">---</td>
                                                    </tr>
                                                    <tr>
                                                        <th style="border:none;">Operador / Chofer:</th>
                                                        <td style="border:none;" id="res_operador">---</td>
                                                    </tr>
                                                    <tr>
                                                        <th style="border:none;">Periodo de Fechas:</th>
                                                        <td style="border:none;" id="res_periodo">---</td>
                                                    </tr>
                                                    <tr>
                                                        <th style="border:none;">Usuario Emisor:</th>
                                                        <td style="border:none;"><?php echo $_SESSION['Ses_Usu_Nom']; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th style="border:none;">Fecha Sistema:</th>
                                                        <td style="border:none;"><?php echo date('d/m/Y H:i'); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- BLOQUE 2: RESUMEN OPERATIVO -->
                                    <div class="col-md-6">
                                        <div class="panel panel-default">
                                            <div class="panel-heading" style="background:#f8fafc;"><strong>2. Resumen Operativo</strong></div>
                                            <div class="panel-body" style="padding: 10px;">
                                                <table class="table table-condensed" style="margin: 0; border: none;">
                                                    <tr>
                                                        <th style="border:none;">Horómetros Incluidos:</th>
                                                        <td style="border:none;" id="res_cant_hor" class="text-info font-weight-bold">0</td>
                                                    </tr>
                                                    <tr>
                                                        <th style="border:none;">Total Horas Trabajadas:</th>
                                                        <td style="border:none;" id="res_tot_hor" class="text-info font-weight-bold">0.00</td>
                                                    </tr>
                                                    <tr>
                                                        <th style="border:none;">Despachos de Combustible:</th>
                                                        <td style="border:none;" id="res_cant_com" class="text-warning font-weight-bold">0</td>
                                                    </tr>
                                                    <tr>
                                                        <th style="border:none;">Combustible Cargado (Gls):</th>
                                                        <td style="border:none;" id="res_gls_com" class="text-warning font-weight-bold">0.00</td>
                                                    </tr>
                                                    <tr>
                                                        <th style="border:none;">Costo Combustible ($):</th>
                                                        <td style="border:none;" id="res_cost_com" class="text-danger font-weight-bold">$0.00</td>
                                                    </tr>
                                                    <tr>
                                                        <th style="border:none;">Compras / Gastos Extras ($):</th>
                                                        <td style="border:none;" id="res_cost_gas" class="text-purple font-weight-bold">$0.00</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- BLOQUE 3: RESUMEN ECONÓMICO -->
                                    <div class="col-md-6">
                                        <div class="panel panel-default">
                                            <div class="panel-heading" style="background:#e0f2fe; color:#0369a1;"><strong>3. Resumen Económico</strong></div>
                                            <div class="panel-body" style="padding: 10px;">
                                                <table class="table table-bordered table-striped" style="margin: 0;">
                                                    <tr>
                                                        <th>Valor Hora Pactado ($)</th>
                                                        <td class="text-right font-weight-bold" id="res_val_hora">0.00</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Horas Trabajadas</th>
                                                        <td class="text-right font-weight-bold" id="res_horas_trab">0.00</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Subtotal ($)</th>
                                                        <td class="text-right font-weight-bold" id="res_subtotal">0.00</td>
                                                    </tr>
                                                    <tr>
                                                        <th style="vertical-align: middle;">Descuento por Hora ($)</th>
                                                        <td class="text-right">
                                                            <input type="number" id="inp_descuento_hora" class="form-control input-sm text-right" value="0.00" step="0.01" min="0" oninput="recalcularEconomico()" onchange="recalcularEconomico()" style="font-weight:bold;">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Total Descuento ($)</th>
                                                        <td class="text-right text-danger font-weight-bold" id="res_tot_des">0.00</td>
                                                    </tr>
                                                    <tr style="background:#dcfce7;">
                                                        <th style="font-size: 16px; color:#166534; vertical-align: middle;">TOTAL A COBRAR ($)</th>
                                                        <td class="text-right font-weight-bold" style="font-size: 22px; color:#15803d;" id="res_tot_cobrar">0.00</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- BLOQUE 4: OBSERVACIONES -->
                                    <div class="col-md-6">
                                        <div class="panel panel-default">
                                            <div class="panel-heading" style="background:#f8fafc;"><strong>4. Observaciones del Documento</strong></div>
                                            <div class="panel-body" style="padding: 10px;">
                                                <textarea id="inp_observaciones" class="form-control" rows="8" placeholder="Ingrese cualquier observación pertinente a esta preliquidación..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- BOTONES DE ACCIÓN -->
                                <div class="row">
                                    <div class="col-sm-12 text-center" style="margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                                        <button type="button" class="btn btn-default btn-lg" id="btnCancelarPre" onclick="location.reload();" style="margin-right:10px;"><i class="fa fa-times"></i> Cancelar</button>
                                        <button type="button" class="btn btn-default btn-lg" id="btnAtrasPre" onclick="cerrarDetalleHistorico();" style="margin-right:10px; display:none;"><i class="fa fa-arrow-left"></i> Atrás</button>
                                        <button type="button" class="btn btn-info btn-lg" id="btnImprimirPre" disabled onclick="imprimirPre()" style="margin-right:10px;"><span id="txtImprimirPre"><i class="fa fa-print"></i> Imprimir Vista Previa</span></button>
                                        <button type="button" class="btn btn-success btn-lg" id="btnGuardarPre" disabled onclick="guardarPreliquidacionEconomica()"><i class="fa fa-save"></i> Guardar Preliquidación</button>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB HOROMETRO -->
                            <div class="tab-pane" id="tab-horometro">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover" id="tblHorometro">
                                        <thead style="background:#3b82f6; color:#fff;">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Operador</th>
                                                <th>Hora Ini</th>
                                                <th>Hora Fin</th>
                                                <th class="text-right">Lec. Ini</th>
                                                <th class="text-right">Lec. Fin</th>
                                                <th class="text-right">Horas Trabajadas</th>
                                                <th class="text-right">Valor P. Total</th>
                                                <th class="text-center">Estado</th>
                                                <th>Observación</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="11" class="text-center text-muted"><i class="fa fa-info-circle"></i> Presione "Generar / Consultar" para buscar datos pendientes.</td>
                                            </tr>
                                        </tbody>
                                        <tfoot style="background:#eff6ff; font-weight:bold;">
                                            <tr>
                                                <td colspan="6" class="text-right text-primary">TOTAL HORAS:</td>
                                                <td class="text-right text-primary" id="lblTotalHor">0.00</td>
                                                <td class="text-right text-primary" id="lblTotalHorMonto">$0.00</td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB COMBUSTIBLE -->
                            <div class="tab-pane" id="tab-combustible">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover" id="tblCombustible">
                                        <tr>
                                            <th>Fecha</th>
                                            <th class="text-right">Cantidad</th>
                                            <th class="text-right">Precio Ref.</th>
                                            <th class="text-right">Costo Total</th>
                                            <th>Chofer</th>
                                            <th>Observación</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted"><i class="fa fa-info-circle"></i> Los consumos aparecerán aquí.</td>
                                            </tr>
                                        </tbody>
                                        <tfoot style="background:#fefce8; font-weight:bold;">
                                            <tr>
                                                <td colspan="3" class="text-right text-warning">TOTAL COMBUSTIBLE:</td>
                                                <td class="text-right text-warning" id="lblTotalCom">$0.00</td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB COMPRAS / GASTOS -->
                            <div class="tab-pane" id="tab-compras">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover" id="tblCompras">
                                        <thead style="background:#8b5cf6; color:#fff;">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Nº Doc</th>
                                                <th>Proveedor</th>
                                                <th>Descripción</th>
                                                <th class="text-right">Total ($)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted"><i class="fa fa-info-circle"></i> (Desarrollo Futuro) Compras del periodo.</td>
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

    <!-- MODAL GUARDAR PRELIQUIDACIÓN -->
    <div class="modal fade" id="modalGuardarPre" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background:#334a5f; color:#fff;">
                    <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-save"></i> Guardar Preliquidación</h4>
                </div>
                <div class="modal-body">
                    <p>Se guardará la cabecera de la preliquidación y se enlazarán los <strong id="lblModalTotalHor">0</strong> registros de horómetro y <strong id="lblModalTotalCom">0</strong> de combustible consultados.</p>
                    <div class="form-group">
                        <label>Observaciones Generales:</label>
                        <textarea id="txtModalObs" class="form-control" rows="3" placeholder="Opcional..."></textarea>
                    </div>
                    <div class="alert alert-info" style="margin-bottom:0;">
                        <i class="fa fa-info-circle"></i> Se actualizará el Mal_Cod en los registros de horómetro y combustible pendientes.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarGuardar" onclick="ejecutarGuardar()">Confirmar y Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script del Módulo -->
    <script type="text/javascript" src="../VALIDACIONES/man_val_maquinaria_preliquidacion.js?v=2"></script>

    <!-- Liberacion y Cierre de conexiones -->
    <?php
    $obBD_con1->liberar();
    $obBD_conexion->cerrar();
    ?>
</body>

</html>