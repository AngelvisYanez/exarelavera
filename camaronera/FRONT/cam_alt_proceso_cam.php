<?php

/**
 * @abstract Consulta todos los procesos que tienen que ver con la negociacion de camaron
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de cración: 16-03-2025
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/cam_log_negociacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Cam($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_datos_Cam();
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

//Obtener el grupo de empresas (Se obtiene el Emp_Cod de la empresa principal y las que estan en el detalle)
$grupo_empresas = $obBD_con1->getArrayConsulta(57, $Ses_Emp_Cod, $obBD_conexion);
$empresas[] = $Ses_Emp_Cod;
if (!empty($grupo_empresas)) {
    foreach ($grupo_empresas as $empresa) {
        if (!empty($empresa['Emp_Cod'])) {
            $empresas[] = $empresa['Emp_Cod'];
        }
    }
}
$Emp_Cod = implode(',', array_unique($empresas)); //Obtener codigo de empresa

if (isset($empacadoraAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(7, $Ses_Emp_Cod, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}

if (isset($negociacionesAjax)) {
    $Cod_Nego = $ced_ruc = $nombre_productor = "";
    if ($order != '') $order = " ORDER BY " . $order;
    if ($op_opciones == 'h') $Cod_Nego = "AND ng.Num_Neg LIKE '%$search%' ";
    $fec = " AND ng.Fec_Neg BETWEEN '$Fec_Ini' AND '$Fec_Fin' ";
    if ($op_opciones == 'nom_prov') $nombre_productor = " AND CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) LIKE '%$search%' ";
    if ($op_opciones == 'ced_ruc') $ced_ruc = " AND persona.Prs_Ced LIKE '%$search%' ";
    if ($codigos_compras != '')  $sql_cod_compras = $codigos_compras;
    try {
        $responce = $obBD_con1->getArrayConsulta(4, $Ses_Emp_Cod . '*' . $order . '*' . $Cod_Nego . '*' . $fec . '*' . $nombre_productor . '*' . $ced_ruc . '*' . $sql_cod_compras, $obBD_conexion);
    } catch (Exception $e) {
    }
    $obBD_con1->echoJson($responce);
    exit();
}


if (isset($comprasAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(5, $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}

if (isset($pagoComprasAjax)) {
    $response1 = $obBD_con1->getArrayConsulta(17, $Emp_Cod . '*' . $Cod_Neg . '*' . $Cop_Cod, $obBD_conexion);
    $response2 = $obBD_con1->getArrayConsulta(30, $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $response3 = $obBD_con1->getArrayConsulta(45, $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $responce['response'] = array_merge($response1, $response2, $response3);
    $obBD_con1->echoJson($responce);
    exit();
}

if (isset($ventasAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(16, $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}

if (isset($pagoVentasAjax)) {
    $responce1 = $obBD_con1->getArrayConsulta(18, $Emp_Cod . '*' . $Cod_Neg . '*' . $Vet_Cod, $obBD_conexion);
    $responce2 = $obBD_con1->getArrayConsulta(46, $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $responce['response'] = array_merge($responce1, $responce2);
    $obBD_con1->echoJson($responce);
    exit();
}
//REGISTRO DE NEGOCIACION
if (isset($prodAjax)) {
    $responce['rows'] = $obBD_con1->getArrayConsulta(6, $Ses_Emp_Cod . '*' . $search . '*' . $op_opciones, $obBD_conexion);
    //$responce['rows'] = $obBD_con1->getArrayConsulta(6, $Ses_Emp_Cod, $obBD_conexion);
    $responce['total'] = count($responce['rows']);
    $obBD_con1->echoJson($responce);
}
// CARGAR CODIGO DE AGUAJE
if (isset($secAguajeAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(8, $Ses_Emp_Cod, $obBD_conexion);
    $Num_Agu = $responce['response'][0]['Num_Agu'] + 1;
    $Num_Agu = str_pad($Num_Agu, 10, '0', STR_PAD_LEFT);
    $obBD_con1->echoJson($Num_Agu);
}
// Guardar Aguaje
/*
if (isset($saveAguaje)) {
    try {
        $encabezado_aguaje = array('Agu_Cod' => $Agu_Cod, 'Num_Agu' => $Num_Agu, 'Nom_Agu' => $Nom_Agu, 'Prod_Cod' => $Prod_Cod, 'Desc_Agu' => $Desc_Agu, 'Est_Agu' => 'A', 'Emp_Cod' => $Ses_Emp_Cod);
        $obBD_con1->operacionobBD(9, $encabezado_aguaje, $obBD_conexion);
        $response = array('success' => true, 'message' => "Transaccion realizada con exito");
    } catch (Exception $e) {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }
    echo json_encode($response);
    exit();
}*/
// Guardar Aguaje
if (isset($saveAguaje)) {
    try {
        $encabezado_aguaje = array('Agu_Cod' => $Agu_Cod, 'Num_Agu' => $Num_Agu, 'Nom_Agu' => $Nom_Agu, 'Emc_Cod' => $Prod_Cod, 'Desc_Agu' => $Desc_Agu, 'Est_Agu' => 'A', 'Emp_Cod' => $Ses_Emp_Cod);
        $resultado = $obBD_con1->operacionobBD(9, $encabezado_aguaje, $obBD_conexion);
        // Validar si el resultado es FALSE o no se registró el dato
        if ($resultado === false || $resultado === 0) {
            $response = array(
                'success' => false,
                'message' => "No se ha registrado el aguaje. Por favor, intente nuevamente.",
                'error' => isset($obBD_conIns->MsgError) ? $obBD_conIns->MsgError : 'Error desconocido'
            );
        } else {
            $response = array('success' => true, 'message' => "Transaccion realizada con exito");
        }
    } catch (Exception $e) {
        $response = array(
            'success' => false,
            'message' => "No se ha logrado realizar la Transaccion",
            'error' => isset($obBD_conIns->MsgError) ? $obBD_conIns->MsgError : $e->getMessage()
        );
    }
    echo json_encode($response);
    exit();
}


//Cargar aguajes
if (isset($aguajesAjax)) {
    $sqlAgu = "";
    if (!empty($search)) {
        $search = " AND (aguaje_camaron.Agu_Cod = '$search' OR aguaje_camaron.Nom_Agu = '$search')";
    }
    if (!empty($Agu_Cod)) $sqlAgu = " AND aguaje_camaron.Agu_Cod=" . $Agu_Cod;
    $responce['response'] = $obBD_con1->getArrayConsulta(10, $Ses_Emp_Cod . '*' . $sqlAgu . '*' . $search,   $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
//Cargar tallas de camaron ENTERO
if (isset($tallaEnteroAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(11, 'ENTERO', $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}

if (isset($tallaColaAAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(11, 'COLAA', $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
if (isset($tallaColaBajax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(11, 'COLAB', $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
if (isset($tallaColaNaAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(11, 'NACIONAL', $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
// REGISTRAR PRECIOS DE TALLAS DE CAMARON
if (isset($savePreciosCamaronAjax)) {
    //obtener cod_talla y cod_aguaje para verificar si ya esta registrado
    if (!empty($Agu_Cod)) {
        foreach ($tallEntero as $tall) {
            $Cod_Prec = $obBD_con1->getRowConsulta(28, $tall['Cod_Tall'] . '*' . $Agu_Cod, $obBD_conexion);
            if (empty($Cod_Prec['Cod_Prec'])) {
                if ((float) $tall['Precio_A'] > 0)
                    $obBD_con1->operacionobBD(12, array('Cod_Tall' => $tall['Cod_Tall'], 'Precio_A' => $tall['Precio_A'], 'Precio_B' => $tall['Precio_B'], 'Cod_Agu' => $Agu_Cod), $obBD_conexion);
            } else {
                $obBD_con1->operacionobBD(29, array('Cod_Tall' => $tall['Cod_Tall'], 'Precio_A' => $tall['Precio_A'], 'Precio_B' => $tall['Precio_B'], 'Cod_Agu' => $Agu_Cod, 'Cod_Prec' => $Cod_Prec['Cod_Prec']), $obBD_conexion);
            }
        }
        foreach ($tallTipA as $tall) {
            $Cod_Prec = $obBD_con1->getRowConsulta(28, $tall['Cod_Tall'] . '*' . $Agu_Cod, $obBD_conexion);
            if (empty($Cod_Prec['Cod_Prec'])) {
                if ((float) $tall['Precio_CA'] > 0)
                    $obBD_con1->operacionobBD(12, array('Cod_Tall' => $tall['Cod_Tall'], 'Precio_A' => $tall['Precio_CA'], 'Precio_B' => $tall['Precio_CB'], 'Cod_Agu' => $Agu_Cod), $obBD_conexion);
            } else {
                $obBD_con1->operacionobBD(29, array('Cod_Tall'  => $tall['Cod_Tall'], 'Precio_A'  => $tall['Precio_CA'], 'Precio_B'  => $tall['Precio_CB'], 'Cod_Agu'   => $Agu_Cod, 'Cod_Prec' => $Cod_Prec['Cod_Prec']), $obBD_conexion);
            }
        }
        foreach ($tallTipB as $tall) {
            $Cod_Prec = $obBD_con1->getRowConsulta(28, $tall['Cod_Tall'] . '*' . $Agu_Cod, $obBD_conexion);
            if (empty($Cod_Prec['Cod_Prec'])) {
                if ((float) $tall['Precio_A'] > 0)
                    $obBD_con1->operacionobBD(12, array('Cod_Tall' => $tall['Cod_Tall'], 'Precio_A' => $tall['Precio_A'], 'Precio_B' => $tall['Precio_B'], 'Cod_Agu' => $Agu_Cod), $obBD_conexion);
            } else {
                $obBD_con1->operacionobBD(29, array('Cod_Tall' => $tall['Cod_Tall'], 'Precio_A' => $tall['Precio_A'], 'Precio_B' => $tall['Precio_B'], 'Cod_Agu' => $Agu_Cod, 'Cod_Prec' => $Cod_Prec['Cod_Prec']), $obBD_conexion);
            }
        }
        foreach ($tallTipNac as $tall) {
            $Cod_Prec = $obBD_con1->getRowConsulta(28, $tall['Cod_Tall'] . '*' . $Agu_Cod, $obBD_conexion);
            if (empty($Cod_Prec['Cod_Prec'])) {
                if ((float) $tall['Precio_NA'] > 0)
                    $obBD_con1->operacionobBD(12, array('Cod_Tall' => $tall['Cod_Tall'], 'Precio_A' => $tall['Precio_NA'], 'Precio_B' => $tall['Precio_NB'], 'Cod_Agu' => $Agu_Cod), $obBD_conexion);
            } else {
                $obBD_con1->operacionobBD(29, array('Cod_Tall' => $tall['Cod_Tall'], 'Precio_A' => $tall['Precio_NA'], 'Precio_B' => $tall['Precio_NB'], 'Cod_Agu' => $Agu_Cod, 'Cod_Prec' => $Cod_Prec['Cod_Prec']), $obBD_conexion);
            }
        }
        $responce = array('success' => true, 'message' => "Transaccion realizada con exito");
    } else {
        $responce['message'] = "No ha seleccionado el aguaje.";
        $obBD_con1->echoJson($responce);
        exit();
    }
    $obBD_con1->echoJson($responce);
    exit();
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
        //Validar que no exista el numero de negociacion
        $responce['response'] = $obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod, $obBD_conexion);
        $Num_Neg = $responce['response'][0]['Num_Neg'] + 1;
        $Num_Neg = str_pad($Num_Neg, 10, '0', STR_PAD_LEFT);

        $encabezado_negociacion = array('Tip_Neg' => $Tip_Neg, 'Prod_Cod' => $Prod_Cod, 'Sec_Cod' => $Sec_Cod, 'Num_Neg' => $Num_Neg, 'Fec_Neg' => $Fec_Neg, 'Val_garantia' => $Val_garantia, 'Val_Gar_Neta' => $Val_Gar_Neta, 'Val_Ant' => $Val_Ant, 'Val_Balanceado' => $Val_Balanceado, 'Val_Larva' => $Val_Larva, 'Neg_Tot' => $Neg_Tot, 'Tot_Libras' => $Tot_Libras, 'Est_Neg' => 'A', 'Link_Contrato' => $Link_Contrato, 'Link_Garantia' => $Link_Garantia, 'Link_Verf_Garan' => $Link_Verf_Garan, 'Neg_Des' => $Neg_Des, 'Emp_Cod' => $Ses_Emp_Cod);
        $obBD_con1->operacionobBD(3, $encabezado_negociacion, $obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $response = array('success' => true, 'message' => "Transaccion realizada con &eacute;xito");
        } else {
            $response = array('success' => true, 'message' => "Error al registrar datos");
        }
    } catch (Exception $e) {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }
    echo json_encode($response);
    exit();
}
// CARGAR CODIGO DE AGUAJE
if (isset($numLiqAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(13, $Ses_Emp_Cod, $obBD_conexion);
    $Num_Liq = $responce['response'][0]['Num_Liq'] + 1;
    $Num_Liq = str_pad($Num_Liq, 10, '0', STR_PAD_LEFT);
    $obBD_con1->echoJson($Num_Liq);
}
//REGISTRAR LIQUIDACION
if (isset($saveLiqAjax)) {
    try {
        $data_liquidacion = array('Cod_Agu' => $Cod_Agu, 'Prod_Cod' => $Prod_Cod, 'Empa_Cod' => $Empa_Cod, 'Liq_Fecha' => $Liq_Fecha, 'Peso_Rem' => $Peso_Rem, 'Peso_Planta' => $Peso_Planta, 'Lib_Falt' => $Lib_Falt, 'Basur' => $Basur, 'Peso_Net' => $Peso_Net, 'Lib_Proces' => $Lib_Proces, 'Val_Rendi' => $Val_Rendi, 'Val_Lote' => $Val_Lote, 'Val_Guia' => $Val_Guia, 'Val_Gram_Glo' => $Val_Gram_Glo, 'Peso_Prom' => $Peso_Prom, 'Val_Pisc' => $Val_Pisc, 'Val_Comision' => $Val_Comision, 'Vnd_Cod' => $Vnd_Cod, 'Gast_Control' => $Gast_Control, 'Otr_Gastos' => $Otr_Gastos, 'Num_Liq' => $Num_Liq, 'Emp_Cod' => $Ses_Emp_Cod, 'Cod_Neg' => $Cod_Neg);
        $resultado = $obBD_con1->operacionobBD(14, $data_liquidacion, $obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $response = array('success' => true, 'message' => "Transaccion realizada con &eacute;xito");
        } else {
            $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_con1->MsgError);
        }
    } catch (Exception $e) {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }
    echo json_encode($response);
    exit();
}
//EDITAR LIQUIDACION
if (isset($editLiqAjax)) {
    try {
        $data_liquidacion = array('Liq_Cod' => $Liq_Cod, 'Cod_Agu' => $Cod_Agu, 'Prod_Cod' => $Prod_Cod, 'Empa_Cod' => $Empa_Cod, 'Liq_Fecha' => $Liq_Fecha, 'Peso_Rem' => $Peso_Rem, 'Peso_Planta' => $Peso_Planta, 'Lib_Falt' => $Lib_Falt, 'Basur' => $Basur, 'Peso_Net' => $Peso_Net, 'Lib_Proces' => $Lib_Proces, 'Val_Rendi' => $Val_Rendi, 'Val_Lote' => $Val_Lote, 'Val_Guia' => $Val_Guia, 'Val_Gram_Glo' => $Val_Gram_Glo, 'Peso_Prom' => $Peso_Prom, 'Val_Pisc' => $Val_Pisc, 'Val_Comision' => $Val_Comision, 'Vnd_Cod' => $Vnd_Cod, 'Gast_Control' => $Gast_Control, 'Otr_Gastos' => $Otr_Gastos, 'Num_Liq' => $Num_Liq, 'Emp_Cod' => $Ses_Emp_Cod);
        $obBD_con1->operacionobBD(40, $data_liquidacion, $obBD_conexion);
        $response = array('success' => true, 'message' => "Transaccion realizada con &eacute;xito");
    } catch (Exception $e) {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }
    echo json_encode($response);
    exit();
}
//Cargar todas las liquidaciones
if (isset($loadLiquiAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(15, $Ses_Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
if (isset($loadCostosaguajesAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(20, $Ses_Emp_Cod . '*' . $Cod_Agu . '*' . $Tip, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
//Save detalle de la liquidación
if (isset($saveDetLiqAjax)) {
    $success = true;
    //Eliminar datos de la liquidacion
    if (!empty($Liq_Cod)) {
        $obBD_con1->operacionobBD(22, array('Liq_Cod' => $Liq_Cod), $obBD_conexion);
        foreach ($det_liqui as $liqui) {
            $result =  $obBD_con1->operacionobBD(21, array('Liq_Cod' => $Liq_Cod, 'Cod_Prec' => $liqui['Cod_Prec'], 'Cant' => $liqui['Cant'], 'Prec' => $liqui['Prec'], 'Med' => $liqui['Med'], 'Tip_Cla' => $liqui['Tip_Cla'], 'Est_Det' => 'A'), $obBD_conexion);
            if (!$result) {
                $success = false; // Si alguna inserción falla, marcamos false
                break;
            }
        }
        if ($success) {
            $response['status'] = 'success';
            $response['message'] = 'Operación registrada correctamente';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Error al registrar la operación';
        }
    } else {
        $response['status'] = 'error';
        $response['error'] = 'Error al registrar la operación';
    }
    $obBD_con1->echoJson($response);
    exit();
}
if (isset($loadDetLiquijax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(23, $Liq_Cod, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
//Cargar precios y tallas de camaron por aguaje
if (isset($loadTallAguajesAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(27, $Ses_Emp_Cod . '*' . $Cod_Agu, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
//Vendedor
$vendedor = $obBD_con1->getRowConsulta(19, $Ses_Suc_Cod . '*' . $Ses_Prs_Cod, $obBD_conexion);
//Imprimir reporte
if (isset($dataReportLiq)) {
    $emp = $obBD_con1->getRowConsulta(47, $Ses_Emp_Cod, $obBD_conexion);
    $responce['success'] = false;
    $table['{body}'] = '';
    $table['{empresa}'] = $Ses_Emp_Nom;
    $table['{logo}'] =   $emp['Emp_Log'];
    $fecha = explode('-', $hoy);
    $table['{fecha}'] = $fecha[2] . ' de ' . mes($fecha[1], 1) . ' de ' . $fecha[0];
    $data_nego = $obBD_con1->getRowConsulta(58, $Ses_Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $table['{productor}'] = $data_nego['productor'];
    $sum_rend = 0;
    $sum_peso = 0;
    $sum_total = 0;
    $ant_prod = 0;
    $gasto_Control = 0;
    $Val_Comision = 0;
    $val_tota_peso = 0;
    //Obtener valores de balanceado y larva
    //Facturas y notas de credito de balanceado 
    $vntaBalanc = $obBD_con1->getRowConsulta(37, $Emp_Cod . '*' . $Cod_Neg . '*' . 'B', $obBD_conexion);
    $nc_balanceado = $obBD_con1->getRowConsulta(4888, $Emp_Cod . '*' . $Cod_Neg . '*' . 'B' . '*' . '4', $obBD_conexion);
    $nc_balanceado = number_format((float)$nc_balanceado['total'], 2, '.', '');    // NOTA DE DEBITO DE BALANCEADO
    $nd_balanceado = $obBD_con1->getRowConsulta(4888, $Emp_Cod . '*' . $Cod_Neg . '*' . 'B' . '*' . '5', $obBD_conexion);
    $nd_balanceado = number_format((float)$nd_balanceado['total'], 2, '.', '');
    $reten_balanceado = $obBD_con1->getRowConsulta(56, $Emp_Cod . '*' . $Cod_Neg . '*' . 'B', $obBD_conexion);
    $data_valoresB = $obBD_con1->getRowConsulta(54, $Emp_Cod . '*' . $Cod_Neg . '*' . 'B', $obBD_conexion);
    $vntaBalanc = number_format((float)$vntaBalanc['total'], 2, '.', '') + $nc_balanceado + $nd_balanceado + number_format((float)$reten_balanceado['Asi_Val'], 2, '.', '');

    //Facturas y notas de credito de larava
    $vntaLarva = $obBD_con1->getRowConsulta(37, $Emp_Cod . '*' . $Cod_Neg . '*' . 'L', $obBD_conexion);
    $nc_larva = $obBD_con1->getRowConsulta(4888, $Emp_Cod . '*' . $Cod_Neg . '*' . 'L' . '*' . '4',   $obBD_conexion);    // NOTA DE DEBITO DE LARVA
    $nd_larva = $obBD_con1->getRowConsulta(4888, $Emp_Cod . '*' . $Cod_Neg . '*' . 'L' . '*' . '5', $obBD_conexion);
    $nd_larva = number_format((float)$nd_larva['total'], 2, '.', '');
    $reten_larva = $obBD_con1->getRowConsulta(56, $Emp_Cod . '*' . $Cod_Neg . '*' . 'L', $obBD_conexion);
    $nc_larva = number_format((float)$nc_larva['total'], 2, '.', '');
    $data_valoresL = $obBD_con1->getRowConsulta(54, $Emp_Cod . '*' . $Cod_Neg . '*' . 'L', $obBD_conexion);
    $vntaLarva = number_format((float)$vntaLarva['total'], 2, '.', '') + $nc_larva + $nd_larva + number_format((float)$reten_larva['Asi_Val'], 2, '.', '');
    //FLETE FALSO
    $fleteFalso = $obBD_con1->getRowConsulta(37, $Emp_Cod . '*' . $Cod_Neg . '*' . 'F', $obBD_conexion);
    $nc_flete = $obBD_con1->getRowConsulta(4888, $Emp_Cod . '*' . $Cod_Neg . '*' . 'F' . '*' . '4', $obBD_conexion);
    $reten_flete = $obBD_con1->getRowConsulta(56, $Emp_Cod . '*' . $Cod_Neg . '*' . 'F', $obBD_conexion);
    $nc_flete = number_format((float)$nc_flete['total'], 2, '.', ''); // NOTA DE DEBITO DE FLETE FALSO
    $nd_flete = $obBD_con1->getRowConsulta(4888, $Emp_Cod . '*' . $Cod_Neg . '*' . 'F' . '*' . '5', $obBD_conexion);
    $nd_flete = number_format((float)$nd_flete['total'], 2, '.', '');
    $data_valoresF = $obBD_con1->getRowConsulta(54, $Emp_Cod . '*' . $Cod_Neg . '*' . 'F', $obBD_conexion);
    $fleteFalso = number_format((float)$fleteFalso['total'], 2, '.', '') +  $nc_flete  + $nd_flete + number_format((float)$reten_flete['Asi_Val'], 2, '.', '');

    //Ventas Insumos
    $vntaInsumos = $obBD_con1->getRowConsulta(37, $Emp_Cod . '*' . $Cod_Neg . '*' . 'I', $obBD_conexion);
    $nc_insumos = $obBD_con1->getRowConsulta(4888, $Emp_Cod . '*' . $Cod_Neg . '*' . 'I' . '*' . '4', $obBD_conexion);
    $nc_insumos = number_format((float)$nc_insumos['total'], 2, '.', '');    // NOTA DE DEBITO DE INSUMOS
    $nd_insumos = $obBD_con1->getRowConsulta(4888, $Emp_Cod . '*' . $Cod_Neg . '*' . 'I' . '*' . '5', $obBD_conexion);
    $nd_insumos = number_format((float)$nd_insumos['total'], 2, '.', '');
    $reten_insumos = $obBD_con1->getRowConsulta(56, $Emp_Cod . '*' . $Cod_Neg . '*' . 'I', $obBD_conexion);
    $data_valoresI = $obBD_con1->getRowConsulta(54, $Emp_Cod . '*' . $Cod_Neg . '*' . 'I', $obBD_conexion);
    $vntaInsumos = number_format((float)$vntaInsumos['total'], 2, '.', '') + $nc_insumos    + $nd_insumos + number_format((float)$reten_insumos['Asi_Val'], 2, '.', '');

    //Otros descuentos
    $vntaDescOtros = $obBD_con1->getRowConsulta(37, $Emp_Cod . '*' . $Cod_Neg . '*' . 'D', $obBD_conexion);
    $nc_desc_otros = $obBD_con1->getRowConsulta(4888, $Emp_Cod . '*' . $Cod_Neg . '*' . 'D', $obBD_conexion);
    $nc_desc_otros = number_format((float)$nc_desc_otros['total'], 2, '.', '');
    $nd_desc_otros = $obBD_con1->getRowConsulta(4888, $Emp_Cod . '*' . $Cod_Neg . '*' . 'D' . '*' . '5', $obBD_conexion); // NOTA DE DEBITO DE OTROS DESCUENTOS
    $nd_desc_otros = number_format((float)$nd_desc_otros['total'], 2, '.', '');
    $reten_desc_otros = $obBD_con1->getRowConsulta(56, $Emp_Cod . '*' . $Cod_Neg . '*' . 'D', $obBD_conexion);
    $data_valoresD = $obBD_con1->getRowConsulta(54, $Emp_Cod . '*' . $Cod_Neg . '*' . 'D', $obBD_conexion);
    $vntaDescOtros = number_format((float)$vntaDescOtros['total'], 2, '.', '') + $nc_desc_otros  + $nd_desc_otros  +  number_format((float)$reten_desc_otros['Asi_Val'], 2, '.', '');

    //$response1 = $obBD_con1->getArrayConsulta(45, $Ses_Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $response1 = $obBD_con1->getArrayConsulta(45, $Emp_Cod  . '*' . $Cod_Neg, $obBD_conexion);
    $response2 = $obBD_con1->getArrayConsulta(30, $Emp_Cod  . '*' . $Cod_Neg, $obBD_conexion);
    $response3 = $obBD_con1->getArrayConsulta(17, $Emp_Cod  . '*' . $Cod_Neg, $obBD_conexion);
    $responce['response'] = array_merge($response1, $response2, $response3);
    foreach ($responce['response'] as $item) {
        $ant_prod += $item['Pag_Val']; //ANTICIPO PRODUCTOR
    }

    /*$total_pagos = $obBD_con1->getRowConsulta(59, $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $ant_prod = number_format((float)$total_pagos['total_pagado'], 2, '.', '');
    */

    $rendi_rel1 = 0;
    $contar_liqui = Count($dataLiq);
    if (!empty($dataLiq)) {
        foreach ($dataLiq as $item) {
            $val_tot = $obBD_con1->getRowConsulta(41, $item['Liq_Cod'], $obBD_conexion);
            $val_tota_peso = $val_tota_peso + $val_tot["totalCant"];
            $rendi_rel1 = $rendi_rel1 + $item['Val_Rendi'];
        }
        $tot_comi = $lib_proces = $peso_planta = $Otr_Gastos = $gasto_Control = $basura = $peso_rem = $peso_neto = 0;
        foreach ($dataLiq as $item) {
            $lib_proces =  $lib_proces +  $item['Lib_Proces']; //TOTAL LIBRAS PROCESADAS
            $peso_planta = $peso_planta +  $item['Peso_Planta']; //TOTAL PESO PLANTA
            $basura = $basura +  $item['Basur']; //TOTAL PESO PLANTA
            $peso_rem =  $peso_rem +  $item['Peso_Rem']; //TOTAL PESO REMITIDO
            $peso_neto = $peso_neto +  $item['Peso_Net']; //TOTAL PESO NETO
            $table['{Lib_Proces}'] = $lib_proces;
            $table['{Peso_Net}'] = $peso_neto;
            $table['{Peso_Rem}'] = $peso_rem;
            $table['{Basur}'] = $basura;
            $table['{Peso_Planta}'] =  $peso_planta;
            $table['{Liq_Cod}'] = str_pad($item['Liq_Cod'], 6, "0", STR_PAD_LEFT);
            $table['{Num_Neg}'] = $item['Num_Neg'];
            //$table['{productor}'] = $item['productor'];
            $table['{Cod_Agu}'] = $item['Cod_Agu'];
            $table['{fecha}'] = $item['Liq_Fecha'];
            $table['{Lib_Falt}'] = $item['Lib_Falt'];
            $table['{Val_Lote}'] = $item['Val_Lote'];
            $table['{Val_Guia}'] = $item['Val_Guia'];
            $table['{Val_Gram_Glo}'] = $item['Val_Gram_Glo'];
            $table['{Peso_Prom}'] = $item['Peso_Prom'];
            $table['{Val_Pisc}'] = $item['Val_Pisc'];
            $table['{Nom_Agu}'] = $item['Nom_Agu'];
            $Val_Comision = $item['Val_Comision'];
            $Otr_Gastos += $item['Otr_Gastos'];
            $gasto_Control += $item['Gast_Control'];
            $Val_Ent =  $val_cant_entero =  $subt_peso = $subt_total = $cantTotal = 0;
            $tot_comi = ($peso_neto * $Val_Comision);
            //obtener las tallas 
            $responce['Tipos'] = $obBD_con1->getArrayConsulta(25, 0, $obBD_conexion);
            $table['{body}'] .= '<tr style=" border-top: 1px solid black;">'
                . '<td style="text-align:left;border:1"><strong>Piscina :' . $item['Val_Pisc']  . ' </strong></td>'
                . '<td style="text-align:right;color:blue;border:1"><strong> Lote : ' .  $item['Val_Lote'] . '</strong></td>'
                . '<td style="text-align:left;border:1"></td>'
                . '<td style="text-align:left;border:1"></td>'
                . '<td style="text-align:right;color:blue;border:1"><strong></strong></td>'
                . '<td style="text-align:right;border:1"><strong></strong></td>'
                . '</tr> ';
            $total_rendi = 0;
            foreach ($responce['Tipos'] as $tip) {
                $responce['rows'] = $obBD_con1->getArrayConsulta(23, $item['Liq_Cod'] . '*' . $tip['Tip'], $obBD_conexion);
                if (!empty($responce['rows'])) {
                    $subt_rend = 0;
                    $subt_peso_aux = 0;
                    $subt_precio_aux = 0;
                    foreach ($responce['rows'] as $row) {
                        $subt_peso = $subt_peso + $row['Cant'];
                        $subt_peso_aux = $subt_peso_aux + $row['Cant'];
                        $subt_total = $subt_total + $row['total'];
                        $subt_precio_aux = $subt_precio_aux + $row['total'];
                        $subt_rend = $subt_rend + (($row['Cant']  * 100 /*$rendi_rel*/) / $val_tota_peso);
                        $val_comi = $row['Val_Comision'];
                        $tall = $row['Tip'];
                        $table['{body}'] .= '<tr style="border-collapse: collapse; border: none;">'
                            . '<td style="text-align:left;border:0">' . $row['Tip'] . '</td>'
                            . '<td style="text-align:right;border:0">' . number_format($row['Cant'], 2) . '</td>'
                            . '<td style="text-align:left;border:0">' . $row['Talla'] . '</td>'
                            . '<td style="text-align:center;border:0">' . '$' . number_format($row['Prec'], 7) . '</td>'
                            . '<td style="text-align:right;border:0">' . '$' . number_format($row['total'], 2) . '</td>'
                            . '<td style="text-align:right;border:0">' . number_format(($row['Cant'] * (100)/* $rendi_rel*/) / $val_tota_peso, 2)  . '%</td>'
                            . '</tr>';
                    }
                    $table['{body}'] .= '<tr style="text-align:left;border-bottom: 1px solid black;">'
                        . '<td style="text-align:left;">' . $tall . ':' . number_format($subt_rend, 2) . '%</td>'
                        . '<td style="text-align:right;color:#000000;border-top:1px solid"><b>' . $subt_peso_aux   /* $totales["totalCant_lbrs"] */ . '</b></td>'
                        . '<td style="text-align:left;border:1"></td>'
                        . '<td style="text-align:left;border:1"></td>'
                        . '<td style="text-align:right;color:#000000;border-top:1px solid"><b>' .  $subt_precio_aux  /*$totales["total_precio"]*/ . '</b></td>'
                        . '<td style="text-align:left;border:1"></td>'
                        .  '</tr>';
                    $total_rendi = $total_rendi + $subt_rend;
                }
            }
            $sum_rend =  $sum_rend + $total_rendi /*$subt_rend*/;
            $sum_peso = $sum_peso + $subt_peso;
            $sum_total = $sum_total + $subt_total;
            $total_descuentos = ($ant_prod + $gasto_Control + $Otr_Gastos + $vntaLarva + $vntaBalanc + (float)$fleteFalso + $vntaInsumos  + $vntaDescOtros);
            $total_saldo = number_format((float)$data_valoresI["Total_Saldo"] + (float)$data_valoresB["Total_Saldo"] + (float)$data_valoresL["Total_Saldo"] + (float)$data_valoresF["Total_Saldo"], 2, '.', '');
            $total_facturar = ($sum_total - ($tot_comi));
            $table['{body}'] .= '<tr style=" border-top: 1px solid black;">'
                . '<td style="text-align:left;border:1"><strong>SUBTOTAL</strong></td>'
                . '<td style="text-align:right;color:blue;border:1"><strong>' . number_format($subt_peso, 2) . '</strong></td>'
                . '<td style="text-align:left;border:1"></td>'
                . '<td style="text-align:left;border:1"></td>'
                . '<td style="text-align:right;color:blue;border:1"><strong>' . number_format($subt_total, 2) . '</strong></td>'
                . '<td style="text-align:right;border:1"><strong>' /*. number_format($total_rendi, 2) */ . '</strong></td>'
                . '</tr> ';
        }

        $aux_rendimi =  round(($rendi_rel1 / $contar_liqui), 2); //Se activo debido a que se puede ingresar manualmente el valor
        //$aux_rendimi =  round(($val_tota_peso / $peso_neto) * 100, 2);
        $table['{Val_Rendi}'] =  $aux_rendimi;

        $table['{Precio_Prom}'] = number_format(($sum_total / $peso_neto), 2);
        $table['{body}'] .= '<tr><td style="padding:10px"></td></tr> ';
        $table['{body}'] .= '<tr style="">'
            . '<td style="text-align:left;border:1"><strong>TOTAL</strong></td>'
            . '<td style="text-align:right;border:1">' . number_format($sum_peso, 2) . '</td>'
            . '<td style="text-align:right;border:1"></td>'
            . '<td style="text-align:right;border:1"></td>'
            . '<td style="text-align:right;border:1">' .  number_format($sum_total, 2) . '</td>'
            . '<td style="text-align:right;border:1">' . number_format($sum_rend, 2) . '</td>'
            . '</tr>';
        $table['{body}'] .= ' <tr style=" border-bottom: 1px solid black;"><td style="padding-top:30px;"></td></tr><tr>'
            . '<tr style=" border-top: none;">'
            . '<td colspan="1" style="text-align:left;border:1"><b>Detalle</b></td>'
            . '<td><b>Saldo</b></td>'
            . '<td style="text-align:left;border:1"><b>Monto Total</b></td>'
            . '<td  colspan="2" style="text-align:right;border:1"></td>'
            . '<td style="text-align:left;border:1"></td>'
            . '</tr>'

            . '<td colspan="2" style="text-align:left;border:1">(-)Anticipos productores:</td>'
            . '<td style="text-align:left;border:1">' . number_format($ant_prod, 2) . '</td>'
            . '<td colspan="2" style="text-align:right;border:1">TOTAL LIB USD:</td>'
            . '<td style="text-align:left;border:1">' .   number_format($sum_total, 2) . '</td>'
            . '</tr> 
            <tr style=" border-top: none;">'
            . '<td colspan="2" style="text-align:left;border:1">(-)Anticipos Controlador:</td>'
            . '<td style="text-align:left;border:1">' . number_format($gasto_Control, 2) . '</td>'
            . '<td  colspan="3" style="text-align:right;border:1"></td>'
            . '<td style="text-align:left;border:1"></td>'
            . '</tr>
             <tr style=" border-top: none;">'
            . '<td colspan="1" style="text-align:left;border:1">(-)Flete Falso:</td>'
            . '<td> ' . number_format($data_valoresF["Total_Saldo"], 2, '.', '') . '</td>'
            . '<td style="text-align:left;border:1">' . number_format(($Otr_Gastos + $fleteFalso), 2) . '</td>'
            . '<td  colspan="2" style="text-align:right;border:1"></td>'
            . '<td style="text-align:left;border:1"></td>'
            . '</tr>
             
             <tr style=" border-top: none;">'
            . '<td colspan="1" style="text-align:left;border:1">(-)Larva:</td>'
            . '<td> ' . number_format($data_valoresL["Total_Saldo"], 2, '.', '') . '</td>'
            . '<td style="text-align:left;border:1">' . number_format($vntaLarva, 2) . '</td>'
            . '<td  colspan="2" style="text-align:right;border:1;color:#a11111;"></td>'
            . '<td style="text-align:left;border:1;color:#a11111;"></td>'
            . '</tr>

            <tr style=" border-top: none;">'
            . '<td colspan="1" style="text-align:left;border:1">(-)Balanceado:</td>'
            . '<td> ' . number_format($data_valoresB["Total_Saldo"], 2, '.', '') . '</td>'
            . '<td style="text-align:left;border:1">' . number_format($vntaBalanc, 2) . '</td>'
            . '</tr>

             <tr style=" border-top: none;">'
            . '<td colspan="1" style="text-align:left;border:1">(-)Insumos:</td>'
            . '<td> ' . number_format($data_valoresI["Total_Saldo"], 2, '.', '') . '</td>'
            . '<td style="text-align:left;border:1">' . number_format($vntaInsumos, 2) . '</td>'
            . '<td  colspan="2" style="text-align:right;border:1">COMISION: </td>'
            . '<td style="text-align:left;border:1">' .   number_format($tot_comi, 2) . '</td>'
            . '</tr>

            <tr style=" border-top: none;">'
            . '<td colspan="1" style="text-align:left;border:1">(-)Otros Desc:</td>'
            . '<td> ' . number_format($data_valoresD["Total_Saldo"], 2, '.', '') . '</td>'
            . '<td style="text-align:left;border:1">' . number_format($vntaDescOtros, 2) . '</td>'
            . '<td  colspan="2" style="text-align:right;border:1;color:#a11111;"><b>TOTAL A FACTURAR: </b></td>'
            . '<td style="text-align:left;border:1;color:#a11111; weight-bold:bold;  "><b>' . number_format($total_facturar, 2) . '</b></td>'
            . '</tr>';



        $table['{body}'] .= '<tr style=" border-top: 1px solid black;">'
            . '<td colspan="1"  style="text-align:left;"><strong>TOTAL DESCUENTOS:</strong></td>'
            . '<td>' . number_format($total_saldo, 2) . '</td>'
            . '<td style="text-align:left;">' . number_format($total_descuentos, 2) . '</td>'
            . '<td colspan="2" style="text-align:right;"><strong>VALOR A CANCELAR:</strong></td>'
            . '<td style="text-align:left;">' . number_format((float)$total_facturar - (float)$total_descuentos, 2)  . '</td>'
            . '</tr>';
        $responce['html'] = reporteHtml($table, 'tes_pri_comaron_liqui.html');
        $responce['success'] = true;
        utf8_encode_deep($responce);
        echo json_encode($responce);
        exit();
    }
}

//Actualizar Negociacion
if (isset($updateNegAjax)) {
    $response = array('success' => true);
    if ($Est_Neg == 'C') {
        $dataC =  $obBD_con1->getRowConsulta(34, $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
        $dataP =  $obBD_con1->getRowConsulta(35, $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
        // $dataPC =  $obBD_con1->getRowConsulta(36, $Ses_Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
        $tot_pag_comras = $dataP["tot_pag"] /*+ $dataPC["tot_pag_c"]*/;
        $tot_pag_comras = number_format((float)$tot_pag_comras, 2, '.', '');
        $dataC["total"] = number_format((float)$dataC["total"], 2, '.', '');
        if ($dataC["total"] != ($tot_pag_comras) ||  $tot_pag_comras  == 0) {
            $response = array('success' => false, 'message' => "No se puede cerrar la negociación porque no se han realizado todos los pagos  o no existen registros");
            echo json_encode($response);
            exit();
        }
        $dataVnts =  $obBD_con1->getRowConsulta(37, $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
        $dataVntCbrar =  $obBD_con1->getRowConsulta(38, $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
        $tot_vnts = number_format((float)$dataVnts["total"], 2, '.', '');
        $tot_cob_vntas = number_format((float) $dataVntCbrar["val_cobr"], 2, '.', '');
        if ($tot_vnts != ($tot_cob_vntas) ||  $tot_cob_vntas  == 0) {
            $response = array('success' => false, 'message' => "No se puede cerrar la negociación porque no se han realizado todos los cobros  o no existen registros");
            echo json_encode($response);
            exit();
        }
    }
    try {
        $update_nego = array(
            'Tip_Neg' => $Tip_Neg,
            'Prod_Cod' => $Prod_Cod,
            'Sec_Cod' => $Sec_Cod,
            'Fec_Neg' => $Fec_Neg,
            'Val_Ant' => $Val_Ant,
            'Tot_Libras' => $Tot_Libras,
            'Prec_Comis' => $Prec_Comis,
            'Vnd_Cod' => $Vnd_Cod,
            'Clasf' => $Clasf,
            'Empa_Cod' => $Empa_Cod,
            'Cod_Neg' => $Cod_Neg,
            'Fec_Pesca' => $Fec_Pesca,
            'Est_Neg' =>  !empty($Est_Neg) ? $Est_Neg  : 'A',
            'Cod_Agu' => $Cod_Agu
        );
        $obBD_con1->operacionobBD(31, $update_nego, $obBD_conexion);
        $response = array('success' => true, 'message' => "Transaccion realizada con exito");
    } catch (Exception $e) {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion");
    }
    echo json_encode($response);
    exit();
}


//Anular Negociacion
if (isset($anularNegAjax)) {
    try {
        $obBD_con1->operacionobBD(32, $Cod_Neg, $obBD_conexion);
        $response = array('success' => true, 'message' => "Transaccion realizada con éxito");
    } catch (Exception $e) {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }
    echo json_encode($response);
    exit();
}

//Anular Liquidación
if (isset($anularLiqAjax)) {
    try {
        $obBD_con1->operacionobBD(33, $Liq_Cod, $obBD_conexion);
        $response = array('success' => true, 'message' => "Transaccion realizada con éxito");
    } catch (Exception $e) {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }
    echo json_encode($response);
    exit();
}
/*
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
}*/

//Cargar ventas de balanceado y larva
if (isset($ventasBLajax)) {
    $responce1['response1'] = $obBD_con1->getArrayConsulta(488,  $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $responce1['response2'] = $obBD_con1->getArrayConsulta(48,  $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $responce1['response3'] = $obBD_con1->getArrayConsulta(55,   $Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $responce['response'] = array_merge($responce1['response2'], $responce1['response1'], $responce1['response3']);
    $obBD_con1->echoJson($responce);
    exit();
}
//Obtener Productor
if (isset($productorAjax)) {
    $responce['response'] = $obBD_con1->getRowConsulta(39,   $Ses_Emp_Cod . '*' . $Tip_Prod  . '*' . $Prod_Cod, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
//Obtener EMPACADORA
if (isset($loadempacadoraAjax)) {
    $responce['response'] = $obBD_con1->getRowConsulta(399,   $Ses_Emp_Cod . '*' . $Tip_Prod  . '*' . $Prod_Cod, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Negociación camaron [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script> </script>
    <style>
        .ui-jqgrid .jqgrow td {
            white-space: normal !important;
            word-wrap: break-word;
        }

        @media print {
            #tablaReporte {
                width: 100%;
                font-size: 10pt;
            }

            #tablaReporte td,
            #tablaReporte th {
                word-wrap: break-word;
                white-space: normal;
            }
        }
    </style>
</HEAD>

<body>
    <div class="panel panel-main" id="formFinal">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Datos Negociación Camarón</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-xs-12 ">
                    <div id="tabsDatos" class="ui-tab-fix">
                        <div class="panels-area form-horizontal normal ">
                            <fieldset class="exa-fieldset" id="prodFormTemp">
                                <div class="col-xs-12 col-sm-6">
                                    <input type="text" id="form_bandera" name="form_bandera" hidden>
                                    <button type="button" class="btn btn-success btn-xs" onclick="negociacionDialog()"><i class="fa fa-plus"></i> Nueva Negociación</button>
                                    <button type="button" class="btn btn-success btn-xs" onclick="liquidacionDialog()"><i class="fa fa-plus"></i> Nueva Liquidación</button>
                                    <button type="button" class="btn btn-success btn-xs" onclick="aguajesDialog()"><i class="fa fa-dollar"></i> Definir Precios</button>
                                    <input type="text" id="aux_cod_neg" name="aux_cod_neg" hidden>
                                    <input type="text" id="aux_num_neg" name="aux_num_neg" hidden>
                                </div>
                                <div class="col-xs-12 col-sm-6 text-right">
                                    <a href="/facturacion/FRONT/fac_alt_fac_ven_3.2.php" class="btn btn-success btn-xs" target="_blank" rel="noopener noreferrer">Venta</a>
                                    <a href="/facturacion/FRONT/fac_alt_fac_com_3.0.php" class="btn btn-success btn-xs" target="_blank" rel="noopener noreferrer">Compras</a>
                                    <a href="/tesoreria/FRONT/tes_alt_anticipo_prv.php" class="btn btn-success btn-xs" target="_blank" rel="noopener noreferrer">Anticipos</a>
                                    <a href="/tesoreria/FRONT/tes_alt_ccpp_lotes_2.0.php" class="btn btn-success btn-xs" target="_blank" rel="noopener noreferrer">Pagos</a>
                                </div>
                            </fieldset>
                            <div class="col-md-6">
                                <div class="row">
                                    <form id="frm_prod_ven" name="frm_prod_ven" class="form-horizontal normal" action="javascript:$('#container').Search('#frm_prod_ven','negociacionesAjax'); ">
                                        <fieldset class="exa-fieldset" id="prodFormTemp">
                                            <legend class="Titulos2">B&uacute;squeda</legend>
                                            <input name="order" type="hidden" value="" />
                                            <div class="col-md-6">
                                                <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>
                                                <div class="col-sm-10 radioset opt_search">
                                                    <input id="radsc1" name="op_opciones" type="radio" value="h" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">Cod.Neg</label>
                                                    <input id="radsc4" name="op_opciones" type="radio" value="nom_prov" onclick="setfocus(this.form.search)" alt="" /><label for="radsc4">Productor</label>
                                                    <input id="radsc5" name="op_opciones" type="radio" value="ced_ruc" onclick="setfocus(this.form.search)" alt="" /><label for="radsc5">Ced/Ruc</label>
                                                </div>
                                            </div>
                                            <div id="divFecha" class="col-md-6">
                                                <div class="col-sm-12">
                                                    <div class="input-group input-group-xs por_fecha">
                                                        <span class="input-group-addon alert-info">Desde</span>
                                                        <input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control" />
                                                        <span class="input-group-addon alert-info">Hasta</span>
                                                        <input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-12">
                                                <label class="col-sm-2 control-label">B&uacute;squeda:</label>
                                                <div class="col-sm-10">
                                                    <div class="input-group">
                                                        <input id="search" name="search" onkeydown="if (event.keyCode === 13)
                                                            this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
                                                        <input type="text" id="cod_plan_cntas" name="cod_plan_cntas" style="display: none;">
                                                        <span class="input-group-btn">
                                                            <button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="-1">
                                                                <span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div><input type="text" tabindex="-1" style="display:none;" />
                                            </div>
                                        </fieldset>
                                    </form>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div>
                                                <table id="container"></table>
                                                <div id="containerPager"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" id="tableLiq">
                                        <div class="col-sm-12">
                                            <table id="containerLiquidacion"></table>
                                            <div id="containerPagerLiquidacion"></div>
                                        </div>
                                        <fieldset class="exa-fieldset">
                                            <div class="col-sm-12">
                                                <button type="button" class="btn btn-success btn-xs" onclick="liquidacionDialog()"><i class="fa fa-plus"></i> Nueva Liquidación</button>
                                                <button type="button" id="btn_imprimir_liquidacion" class="btn btn-success btn-xs" onclick="imprimirLiquidacion(true)"><i class="fa fa-print"></i> Imprimir</button>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div class="row" id="ventasLB" hidden>
                                        <div class="col-sm-12">
                                            <table id="tablasVentasBL"></table>
                                            <div id="containerPagertablasVentasBL"></div>
                                            <div class="Titulos2">
                                                <span id="plan-footer">
                                                    <strong>Leyenda:</strong>
                                                    <span class="glyphicon glyphicon-stop green"></span> Ventas Larva
                                                    <span class="glyphicon glyphicon-stop " style="color: #82dcff;"> </span>Ventas Balanceado
                                                    <span class="glyphicon glyphicon-stop " style="color: #ff928bff;"> </span>Flete falso
                                                    <span class="glyphicon glyphicon-stop " style="color: #dfcb5dff;"> </span>Ventas Insumos
                                                    <span class="glyphicon glyphicon-stop " style="color: #4ea1d8ff;"> </span>Otros Descuentos
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-12 form-group">
                                            <label class="col-xs-1 control-label label-xs">Balanceado </label>
                                            <div class="col-xs-2">
                                                <input type="text" id="total_vntas_bal" name="total_vntas_bal" class="form-control input-xs" readonly>
                                            </div>
                                            <label class="col-xs-1 control-label label-xs">Larva </label>
                                            <div class="col-xs-2">
                                                <input type="text" id="total_vntas_larva" name="total_vntas_larva" class="form-control input-xs" readonly>
                                            </div>
                                            <label class="col-xs-1 control-label label-xs">Flete.Falso</label>
                                            <div class="col-xs-2">
                                                <input type="text" id="total_flete_falso" name="total_flete_falso" class="form-control input-xs" readonly>
                                            </div>
                                            <label class="col-xs-1 control-label label-xs">Insumos</label>
                                            <div class="col-xs-2">
                                                <input type="text" id="total_vnta_insumos" name="total_vnta_insumos" class="form-control input-xs" readonly>
                                            </div>
                                            <label class="col-xs-1 control-label label-xs">Otros.Desc</label>
                                            <div class="col-xs-2">
                                                <input type="text" id="total_otros_desc" name="total_otros_desc" class="form-control input-xs" readonly>
                                            </div>
                                        </div>

                                    </div>
                                    <fieldset class="exa-fieldset">
                                        <button type="button" class="btn btn-success btn-xs" onclick="armarGridBL()"><i class="fa fa-check"></i> Vent. Balanceado/Larva</button>
                                        <button type="button" class="btn btn-success btn-xs" onclick="armarGridLiqui()"><i class="fa fa-check"></i> List. Liquidación</button>
                                    </fieldset>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="m-5">
                                    <table id="containerCompras"></table>
                                    <div id="containerPagerCmp"></div>
                                </div>
                                <hr>
                                <div id="tablasEgresos" class="" style="min-height: 200px;">
                                    <table id="containerEgresos"></table>
                                    <div id="containerPagerEgr"></div>
                                </div>
                                <hr>
                                <div id="tablasVentas" class="" style="min-height: 200px;">
                                    <table id="containerVentas"></table>
                                    <div id="containerPagerVnt"></div>
                                </div>
                                <hr>
                                <div id="tablasAnticipos" class="" style="min-height: 200px;">
                                    <table id="containerAnticipos"></table>
                                    <div id="containerPagerAnt"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="imprimir" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE NEGOCIACIONES', '<span class="subtitle">Lista de Negociaciones</span>', $obBD_conexion) ?>
            <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;table-layout:auto  ;font-size:12px;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>
    <div id="liquidacionDialog" title="Registro de liquidación" style="display: none;">
        <form id="frm_liquidacion" name="frm_liquidacion" class="form-horizontal normal" action="javascript:validaDocumentLiq();">
            <div class="col-xs-12">
                <fieldset class="exa-fieldset">
                    <div class="form-group">
                        <label class="col-xs-1 control-label label-xs">Fecha:</label>
                        <div class="col-xs-3">
                            <input name="Fec_Neg" id="Fec_Neg" type="date" value="<?php echo  date("Y-m-d") ?>" class="form-control input-xs ">
                        </div>
                        <label class="col-xs-2 control-label label-xs">Nro.Liq:</label>
                        <div class="col-xs-2">
                            <input name="Num_Liq" id="Num_Liq" type="text" class="form-control input-xs" readonly>
                        </div>
                        <label class="col-xs-2 control-label label-xs">Nro.Nego:</label>
                        <div class="col-xs-2">
                            <input name="Num_Neg" id="Num_Neg" type="text" class="form-control input-xs frm_liq_num_neg" readonly>
                            <input type="text" id="Cod_Neg" name="Cod_Neg" class="frm_liq_cod_neg" hidden>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="col-xs-12">
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Seleccionar Aguaje:</label>
                    <div class="col-xs-9"><select name="Cod_Agu" id="Cod_Agu" class="form-control input-xs" onchange="loadDataLiq('addLiq')"> </select>
                    </div>
                </div>
            </div>
            <div class="col-xs-6">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Productor:</legend>
                    <div class="form-group col-xs-12">
                        <div class="form-group">
                            <input type="hidden" name="Prod_Cod" id="Prod_Cod" class="form-control input-xs">
                            <label class="col-xs-3 control-label label-xs required">Nombre:</label>
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
                            <label class="col-xs-3 control-label label-xs">Dirección:</label>
                            <div class="col-xs-9"><input name="Telf_Prod" id="Telf_Prod" type="text" class="form-control input-xs" readonly></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Telf:</label>
                            <div class="col-xs-9"><input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-xs" readonly></div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="col-xs-6">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Empacadora:</legend>
                    <div class="form-group">
                        <input type="text" id="Empa_Cod" name="Empa_Cod" hidden>
                        <label class="col-xs-3 control-label label-xs">Nombre:</label>
                        <div class="col-xs-9">
                            <input type="text" name="Nom_Emp" id="Nom_Emp" class="form-control input-xs" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs">Dirección:</label>
                        <div class="col-xs-9">
                            <input type="text" name="Dir_Emp" id="Dir_Emp" class="form-control input-xs" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs">Ciudad:</label>
                        <div class="col-xs-9">
                            <input type="text" name="Ciu" id="Ciu" class="form-control input-xs" readonly>
                        </div>
                    </div>
                </fieldset>
            </div>

            <div class="col-xs-12">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Datos liquidación:</legend>
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Fecha Ingreso:</label>
                            <div class="col-xs-6">
                                <input name="Liq_Fecha" type="Liq_Fecha" value="<?php echo  date("Y-m-d") ?>" class="form-control input-xs ">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Peso remitido:</label>
                            <div class="col-xs-6"><input name="Peso_Rem" id="Peso_Rem" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Peso planta:</label>
                            <div class="col-xs-6"><input name="Peso_Planta" id="Peso_Planta" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Lib. faltantes:</label>
                            <div class="col-xs-6"><input name="Lib_Falt" id="Lib_Falt" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Basura:</label>
                            <div class="col-xs-6"><input name="Basur" id="Basur" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Peso neto:</label>
                            <div class="col-xs-6"><input name="Peso_Net" id="Peso_Net" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Libs. procesadas:</label>
                            <div class="col-xs-6"><input name="Lib_Proces" id="Lib_Proces" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Rendimiento %:</label>
                            <div class="col-xs-6">
                                <input name="Val_Rendi" id="Val_Rendi" type="number" step="any" class="form-control input-xs ">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Lote:</label>
                            <div class="col-xs-6"><input name="Val_Lote" id="Val_Lote" type="text" class="form-control input-xs " placeholder="0"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Guia:</label>
                            <div class="col-xs-6"><input name="Val_Guia" id="Val_Guia" type="text" class="form-control input-xs " placeholder="0001"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Gramaje global:</label>
                            <div class="col-xs-6"><input name="Val_Gram_Glo" id="Val_Gram_Glo" type="number" step="any" class="form-control input-xs " placeholder="0.00"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Peso promedio:</label>
                            <div class="col-xs-6"><input name="Peso_Prom" id="Peso_Prom" type="number" step="any" class="form-control input-xs " placeholder="0.00"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Piscina:</label>
                            <div class="col-xs-6"><input name="Val_Pisc" id="Val_Pisc" type="text" class="form-control input-xs " placeholder="0"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 control-label label-xs">Comisión:</label>
                            <div class="col-xs-6"><input name="Val_Comision" type="Val_Comision" step="any" class="form-control input-xs " placeholder="0.00"></div>
                        </div>
                    </div>
                    <div class="col-xs-12">
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Controlador:</label>
                            <div class="col-xs-9"><input name="Vnd_Name" id="Vnd_Name" type="text" class="form-control input-xs " value="<?php echo ($vendedor["vendedores"]); ?>" readonly></div>
                            <input type="text" name="Vnd_Cod" id="Vnd_Cod" value="<?php echo ($vendedor["Vnd_Cod"]); ?>" hidden>
                        </div>
                    </div>
                </fieldset>
            </div>
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Gastos:</legend>
                <div class="row">
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="form-group row">
                            <label class="col-xs-6 col-sm-6 col-md-6 col-form-label">Gasto Controlador:</label>
                            <div class="col-xs-6 col-sm-6 col-md-6">
                                <input type="number" class="form-control input-xs" step="any" id="Gast_Control" name="Gast_Control">
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="form-group row">
                            <label class="col-xs-6 col-sm-6 col-md-6 col-form-label">Otros gastos:</label>
                            <div class="col-xs-6 col-sm-6 col-md-6">
                                <input type="number" class="form-control input-xs" step="any" id="Otr_Gastos" name="Otr_Gastos">
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>
            <div class="form-group">
                <div class="center">
                    <button type="button" class="btn btn-sm btn-danger no" onclick="cancelarAddLiq()"><i class="glyphicon glyphicon-minus"></i> Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="$('#frm_liquidacion').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                </div>
            </div>
        </form>
    </div>
    <div id="negociacionDialog" title="Registro de Negociación" style="display: none;">
        <?php include '../COMPONENTES/modalNegociacion.php'; ?>
    </div>
    <div id="aguajesDialog" title="Registro de Aguajes" style="display: none;">
        <?php include '../COMPONENTES/modalAguajes.php'; ?>
    </div>

    <div id="liqNegDialog" title="Editar Negociación" style="display: none;">
        <?php include '../COMPONENTES/liqNegModal.php'; ?>
    </div>

    <div id="liquiEditDialog" title="Editar de liquidación" style="display: none;">
        <?php include '../COMPONENTES/editLiqModal.php'; ?>
    </div>

    <div id="prodDialog" title="B&uacute;squeda de Productor"></div>
    <div id="agregarAguajesAddDialog" title="Aguaje" style="display: none;">
        <form id="frm_aguaje" name="frm_aguaje" action="javascript:validaDocumentAguaje();">
            <fieldset>
                <legend class="Titulos2">Datos Aguaje:</legend>
                <div class="form-group">
                    <label class="col-xs-12 control-label label-xs">Código:</label>
                    <div class="col-xs-12"><input name="Num_Agu" id="Num_Agu" type="text" class="form-control input-xs " readonly></div>
                    <input type="text" id="Agu_Cod" name="Agu_Cod" hidden>
                </div>
                <div class="form-group">
                    <label class="col-xs-12 control-label label-xs">Nombre Aguaje:</label>
                    <div class="col-xs-12"><input name="Nom_Agu" id="Nom_Agu" type="text" class="form-control input-xs "></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-12 control-label label-xs">Empacadora:</label>
                    <div class="col-xs-12">
                        <select name="Prod_Cod" id="Prod_Cod" class="form-control input-xs">
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-12 control-label label-xs">Descripción:</label>
                    <div class="col-xs-12">
                        <textarea name="Desc_Agu" id="Desc_Agu" class="form-control input-xs"></textarea>
                    </div>
                </div>
            </fieldset><br>
            <div class="form-group">
                <div class="col-xs-12 center">
                    <button type="button" class="btn btn-sm btn-primary" onclick="$('#frm_aguaje').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                </div>
            </div>
        </form>
    </div>
    <div id="editLiqDialog" title="Editar Precios Liquidación" style="display: none;">
        <?php include '../COMPONENTES/editLiqPrecModal.php'; ?>
    </div>
    <script>
        function selectProd(productor) {
            console.log("productor", productor);
            console.log(productor);
            var reset = ($('#reset').val() !== '0');
            var isform = $("#form_bandera").val();
            if (isform == "NEG") {
                $('#frm_negociacion #Prod_Cod').val(productor.Prod_Cod);
                $('#frm_negociacion #Nom_Prod').val(productor.Prs_Nom + ' ' + productor.Prs_Ape);
                $('#frm_negociacion #Telf_Prod').val(productor.Prs_Tel);
                $.ajax({
                    url: '',
                    method: 'POST',
                    data: {
                        sectorAjax: true,
                        Prod_Cod: productor.Prod_Cod
                    },
                    dataType: 'json',
                    success: function(response) {
                        const select = $('#frm_negociacion #Sec_Cod');
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
            }
            if (isform == "LIQ") {
                $('#frm_liquidacion #Prod_Cod').val(productor.Prod_Cod);
                $('#frm_liquidacion #Nom_Prod').val(productor.Prs_Nom + ' ' + productor.Prs_Ape);
                $('#frm_liquidacion #Telf_Prod').val(productor.Prs_Tel);
                $('#frm_liquidacion #Prs_Dir').val(productor.Prs_Dir);
            }
            if (isform == "NEG_EDIT") {
                $('#frm_EditNego #Prod_Cod').val(productor.Prod_Cod);
                $('#frm_EditNego #Nom_Prod').val(productor.Prs_Nom + ' ' + productor.Prs_Ape);
                loadSector(productor.Prod_Cod);
            }

            if (isform == "LIQ_EDIT") {
                $('#frm_Edit_Liq #Prod_Cod').val(productor.Prod_Cod);
                $('#frm_Edit_Liq #productor').val(productor.Prs_Nom + ' ' + productor.Prs_Ape);
                $('#frm_Edit_Liq #Telf_Prod').val(productor.Prs_Tel);
                $('#frm_Edit_Liq #Prs_Dir').val(productor.Prs_Dir);
            }
            $('#prodDialog').dialog('close');
        }
    </script>
    <script src="../VALIDACIONES/cam_val_procesos_neg.js?x=7"></script>
    <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</body>