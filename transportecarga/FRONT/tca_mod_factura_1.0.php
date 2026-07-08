<?php

/**
 * @abstract Permite realizar la modificaci�n de un proceso de facturaci�n de viajes
 * @author Jos� Ambulud�
 * @version 2.0
 * Fecha de creaci�n  2017-02-13
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tca_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_viajeFactura($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_viajeFactura;

$hoy = date("Y-m-d");

//Secci�n para cargar datos en el Jqgrid referente a los clientes registrados
if (isset($clientefacturaAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(33, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(33, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

//Secci�n para cargar los viajes sin facturar de un determinado cliente
if (isset($viajeAjax)) {
    $data = filter_input_array(INPUT_GET);
    $contar = $obBD_con1->getRowConsulta(55, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(55, $data, $obBD_conexion);
    }
    echo json_encode($responce);
    exit();
}

//Secci�n para verificar si existen pagos efectuados por una factura determinada
if (isset($buscarPagos)) {
    $response = $obBD_con1->getArrayConsulta(41, $Cpc_Cod, $obBD_conexion);
    if ($response['Cpc_Cod'] * 1 > 0) {
        $response['existe'] = true;
    } else {
        $response['existe'] = false;
    }
    echo json_encode($response);
    exit();
}

//Secci�n para obtener el n�mero de secuencia
if (isset($numeroSec)) {
    $response = $obBD_con1->getRowConsulta(4, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod . '*' . $Tic_Cod, $obBD_conexion);
    $siguiente = $obBD_con1->getRowConsulta(25, $response['Aut_Ini'] . '*' . $response['Aut_Fin'] . '*' . $response['Aut_Sri'] . '*' . $Tic_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
    $response['Num_Sig'] = $siguiente['siguiente'];
    echo json_encode($response);
    exit();
}

//Secci�n para verificar si el n�mero de secuencia ya esta registrado
if (isset($verificarNrosecuencia)) {
    $response = $obBD_con1->getRowConsulta(37, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod . '*' . $Tic_Cod . '*' . $Vet_Num, $obBD_conexion);
    if (!empty($response['Vet_Num'])) {
        $response['existe'] = true;
    } else {
        $response['existe'] = false;
    }
    echo json_encode($response);
    exit();
}

//Secci�n para cargar los tipos de pago
if (isset($cargarTipago)) {
    $rs_tpago = $obBD_con1->getArrayConsulta(12, $For_Cod, $obBD_conexion);
    $a = 1;
    foreach ($rs_tpago as $row) {
        ($a == 1) ? $sel = 'selected' : $sel = '';
        $a++;
        $Pag_Cod .= "<option value=" . $row['Pag_Cod'] . " $sel>" . $row['Pag_Des'] . "</option>";
    }
    $response["Pag_Cod_html"] = $Pag_Cod;
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

//Secci�n para cargar los bancos del plan de cuentas
if (isset($cargarBancos)) {
    $rs_banco = $obBD_con1->getArrayConsulta(13, $Pag_Cod . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $a = 1;
    foreach ($rs_banco as $row) {
        ($a == 1) ? $sel = 'selected' : $sel = '';
        $a++;
        $Ban_Cod .= "<option value=" . $row['Ban_Cod'] . " $sel>" . $row['Pld_Des'] . "</option>";
    }
    $response["Ban_Cod_html"] = $Ban_Cod;
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

//Secci�n para cargar las cuentas deudoras
if (isset($cargarCtadeu)) {
    $rs_ctadeudora = $obBD_con1->getArrayConsulta(24, $Pec_Cod . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $a = 1;
    foreach ($rs_ctadeudora as $row) {
        ($a == 1) ? $sel = 'selected' : $sel = '';
        $a++;
        $Pld_Cod .= "<option value=" . $row['Pld_Cod'] . " $sel>" . $row['Pld_Des'] . "</option>";
    }
    $response["Pld_Cod_html"] = $Pld_Cod;
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

//Secci�n para cargar datos en el Jqgrid referente a las facturas registradas
if (isset($facturasAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(30, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(30, $data, $obBD_conexion);
    }
    echo json_encode($responce);
    exit();
}

if (isset($cargarFactura)) {
    $response = $obBD_con1->getRowConsulta(31, $Ses_Emp_Cod . '*' . $Vet_Cod, $obBD_conexion);
    $rs_confi = $obBD_con1->getRowConsulta(17, $Ses_Emp_Cod, $obBD_conexion);
    $rs_tpago = $obBD_con1->getArrayConsulta(12, $response['For_Cod'], $obBD_conexion);
    $rs_ctade = $obBD_con1->getArrayConsulta(24, $response['Pec_Cod'] . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $rs_banco = $obBD_con1->getArrayConsulta(13, $response['Pag_Cod'] . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $rs_forma = $obBD_con1->getArrayConsulta(11, "", $obBD_conexion);
    $rs_fpago = $obBD_con1->getArrayConsulta(14, "", $obBD_conexion);
    $rs_tipoc = $obBD_con1->getArrayConsulta(3, "", $obBD_conexion);
    $rs_banko = $obBD_con1->getArrayConsulta(26, "", $obBD_conexion);
    $rs_ivass = $obBD_con1->getArrayConsulta(6, "", $obBD_conexion);
    foreach ($rs_forma as $row) {
        ($response['For_Cod'] == $row['For_Cod']) ? $sel = 'selected' : '';
        $For_Cod .= "<option value=" . $row['For_Cod'] . " $sel>" . mb_convert_encoding($row['For_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
    }
    foreach ($rs_tpago as $row) {
        ($response['Pag_Cod'] == $row['Pag_Cod']) ? $sel = 'selected' : '';
        $Pag_Cod .= "<option value=" . $row['Pag_Cod'] . " $sel>" . $row['Pag_Des'] . "</option>";
    }
    foreach ($rs_fpago as $row) {
        ($response['Tpc_Cod'] == $row['Tpc_Cod']) ? $sel = 'selected' : '';
        $Tpc_Cod .= "<option value=" . $row['Tpc_Cod'] . " $sel>" . $row['Tpc_Des'] . "</option>";
    }
    foreach ($rs_tipoc as $row) {
        ($response['Tic_Cod'] == $row['Tic_Cod']) ? $sel = 'selected' : '';
        $Tic_Cod .= "<option value=" . $row['Tic_Cod'] . " $sel>" . $row['Tic_Des'] . "</option>";
    }
    foreach ($rs_banko as $row) {
        ($response['Bak_Cod'] == $row['Bak_Cod']) ? $sel = 'selected' : '';
        $Bak_Cod .= "<option value=" . $row['Bak_Cod'] . " $sel>" . $row['Bak_Des'] . "</option>";
    }
    foreach ($rs_ctade as $row) {
        ($response['Pld_Cod'] == $row['Pld_Cod']) ? $sel = 'selected' : '';
        $Pld_Cod .= "<option value=" . $row['Pld_Cod'] . " $sel>" . $row['Pld_Des'] . "</option>";
    }
    foreach ($rs_banco as $row) {
        ($response['Ban_Cod'] == $row['Ban_Cod']) ? $sel = 'selected' : '';
        $Ban_Cod .= "<option value=" . $row['Ban_Cod'] . " $sel>" . $row['Pld_Des'] . "</option>";
    }
    foreach ($rs_ivass as $row) {
        ($response['Iva_Cod'] == $row['Iva_Cod']) ? $sel = 'selected' : '';
        $Iva_Cod .= "<option value=" . $row['Iva_Cod'] . " data-iva=" . $row['Iva_Por'] . " $sel>" . $row['Iva_Por'] . "</option>";
    }
    $response["Cof_Con"] = $rs_confi['Cof_Con'];
    $response["For_Cod_html"] = $For_Cod;
    $response["Pag_Cod_html"] = $Pag_Cod;
    $response["Tpc_Cod_html"] = $Tpc_Cod;
    $response["Tic_Cod_html"] = $Tic_Cod;
    $response["Bak_Cod_html"] = $Bak_Cod;
    $response["Pld_Cod_html"] = $Pld_Cod;
    $response["Ban_Cod_html"] = $Ban_Cod;
    $response["Iva_Cod_html"] = $Iva_Cod;
    $response["Des_Dpo"] = "1";
    $detalle = $obBD_con1->getArrayConsulta(32, $Vet_Cod, $obBD_conexion);
    //Se carga el total de pagos efectuados registrados en la tabla det_ccpp_c
    $tot_pagos = $obBD_con1->getRowConsulta(43, $Vet_Cod, $obBD_conexion);
    $response['Tot_Pag'] = $tot_pagos['Tot_Pag'];

    $t_iva0 = 0;
    $Iva_Cod = 0;
    foreach ($detalle as $row) {
        $t_subtotal = $t_subtotal + $row['Vet_Imp'];
        if ($row['Iva_Por'] * 1 > 0) {
            $Iva_Cod = $row['Iva_Cod'];
            $descuento = ($row['Vet_Imp'] * $response['Vet_Des']) / 100;
            $importe_descuento = $row['Vet_Imp'] - $descuento;
            (empty($row['Vet_Ice'])) ? $ice = 0 : $ice = $row['Vet_Ice'];
            $ice_individual = ($importe_descuento * $ice) / 100;
            $t_ice = $t_ice * 1 + $ice_individual * 1;

            $iva_individual = (($importe_descuento + $ice_individual) * $row['Iva_Por']) / 100;
            $t_iva = $t_iva * 1 + $iva_individual * 1;
            $t_iva12 = $t_iva12 + $row['Vet_Imp'];
        } else {
            $t_iva0 = $t_iva0 + $row['Vet_Imp'];
            if ($Iva_Cod <= 0) {
                $iva_default = $obBD_con1->getRowConsulta(56, $response['Caj_Fec'], $obBD_conexion);
                $Iva_Cod = $iva_default['Iva_Cod'];
            }
        }
    }

    $t_descuento = ($t_subtotal * $response['Vet_Des']) / 100;
    $t_iva12 = number_format($t_iva12, 2, '.', '');
    $t_iva = number_format($t_iva, 2, '.', '');
    $t_ice = number_format($t_ice, 2, '.', '');
    $Vet_Tot = ($t_iva0 + $t_iva12 + $t_iva + $t_ice) - $t_descuento;
    $response['t_subtotal'] = $t_subtotal;
    $response['t_iva0'] = $t_iva0;
    $response['t_iva12'] = $t_iva12;
    $response['t_ice'] = $t_ice;
    $response['t_iva'] = $t_iva;
    $response['t_descuento'] =  number_format($t_descuento, 2, '.', '');
    $response['Vet_Tot'] =  number_format($Vet_Tot, 2, '.', '');
    $response['Det_Ven'] = $detalle;
    $response['Iva_Cod'] = $Iva_Cod;
    echo json_encode($response);
    exit();
}

//Secci�n para guardar una factura
if (isset($saveFactura)) {
    $response['success'] = false;
    $obBD_conexionIns = new Class_Log_Conexion_viajeFactura($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_viajeFactura;
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);

    try {

        /*** ELIMINA LOS REGISTROS DE LAS TABLAS "ventas_det - pago_venta - asientos" ***/
        //Secci�n para eliminar los registros de la tabla ventas_det
        $obBD_conIns->operacionobBD(44, $Vet_Cod, $obBD_conexionIns);

        //Secci�n para eliminar los registros de la tabla pago_venta
        $obBD_conIns->operacionobBD(45, $Vet_Cod, $obBD_conexionIns);

        //Secci�n para eliminar los registros de la tabla asientos
        $obBD_conIns->operacionobBD(46, $Com_Cod, $obBD_conexionIns);

        //Secci�n para eliminar l�gicamente el Vet_Cod e Iva_Cod de la tabla viaje
        $obBD_conIns->operacionobBD(47, "" . '*' . "" . '*' . $Vet_Cod, $obBD_conexionIns);

        //Secci�n para extraer el Pun_Cod y Vnd_Cod del usuario sobre la tabla vendedor
        $rs_Punto = $obBD_con1->getRowConsulta(7, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);

        //Secci�n para verificar si la caja ya fue aperturada
        $rs_Caja = $obBD_con1->getRowConsulta(28, $rs_Punto['Pun_Cod'] . '*' . $Caj_Fec, $obBD_conexion);
        if (empty($rs_Caja['Caj_Cod'])) {
            //Secci�n para aperturar la caja a trav�s de insert a la tabla caja_aper
            $obBD_conIns->operacionobBD(8, $rs_Punto['Pun_Cod'] . '*' . $Caj_Fec, $obBD_conexionIns);
            //Secci�n para obtener el id ingresado en la tabla caja_aper
            $Caj_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
        } else {
            $Caj_Cod = $rs_Caja['Caj_Cod'];
        }

        //Secci�n para efectuar update en la tabla viaje
        $obBD_conIns->operacionobBD(34, $Cli_Cod . '*' . $Vet_Cod, $obBD_conexionIns);

        //Secci�n para efectuar update en la tabla ventas 
        $obBD_conIns->operacionobBD(53, $Cli_Cod . '*' . $Vet_Cod, $obBD_conexionIns);

        //Secci�n para efectuar update en la tabla ventas referente a Tic_Cod,Vet_Num,Caj_Cod,Vet_Obs
        $obBD_conIns->operacionobBD(36, $Tic_Cod . '*' . $Vet_Num . '*' . $Caj_Cod . '*' . (empty($vet_des) ? $Vet_Des : $vet_des) . '*' . $Vet_Obs . '*' . $Vet_Cod, $obBD_conexionIns);

        //Secci�n para insertar en la tabla ventas_det
        $Vet_Ite = 1;
        foreach ($Det_Fac as $row) {
            $obBD_conIns->operacionobBD(10, $Vet_Ite . '*' . $Vet_Cod . '*' . $row['Pro_Cod'] . '*' . $row['Iva_Cod'] . '*' . $row['Vet_Can'] . '*' . $row['Vet_Pru'] . '*' . $row['Vet_Imp'] . '*' . $row['Vet_Ice'], $obBD_conexionIns);
            $Vet_Ite++;
        }

        //Secci�n para efectuar un UPDATE sobre la tabla viaje
        foreach ($Det_Fac as $row) {
            $obBD_conIns->operacionobBD(27, $Vet_Cod . '*' . $row['Iva_Cod'] . '*' . $row['Via_Cod'], $obBD_conexionIns);
        }

        if ($Cof_Con == 'S') {
            //Secci�n para efectuar update en la tabla comprobantes
            $obBD_conIns->operacionobBD(35, $Cli_Cod . '*' . $Caj_Fec . '*' . $Vet_Obs . '*' . $Vet_Tot . '*' . $Vet_Cod, $obBD_conexionIns);

            //Secci�n para insertar en la tabla ccpp_cobrar
            if ($For_Cod * 1 == 2) { //Indica que la forma de pago es a cr�dito
                if (empty($Cpc_Cod)) {
                    $obBD_conIns->operacionobBD(23, $Com_Cod . '*' . $Vet_Cod . '*' . $Cpc_Ven . '*' . $Vet_Obs, $obBD_conexionIns);
                } else {
                    //Secci�n para efectuar update en la tabla ccpp_cobrar
                    $obBD_conIns->operacionobBD(39, $Cpc_Ven . '*' . $Cpc_Cod, $obBD_conexionIns);
                }
                $Ban_Cod = "";
            } else {
                //Se obtiene el Pld_Cod en caso de ser al contado
                $Pld_Cod_Bco = $obBD_con1->getRowConsulta(21, $Ban_Cod, $obBD_conexion);
                $Pld_Cod = $Pld_Cod_Bco['Pld_Cod'];

                if (!empty($Cpc_Cod)) {
                    //Secci�n para eliminar f�sicamente un registro de la tabla ccpp_cobrar
                    $obBD_conIns->operacionobBD(40, $Cpc_Cod, $obBD_conexionIns);
                    $Bak_Cod = "1";
                }
            }

            //Secci�n para guardar el asiento del total la factura DEBE
            $obBD_conIns->operacionobBD(20, $Com_Cod . '*' . 'D' . '*' . $Vet_Tot . '*' . 'DCTO.:' . $Vet_Num . '*' . $Pld_Cod, $obBD_conexionIns);

            //Secci�n para guardar el asiento del detalle de la factura HABER
            foreach ($Det_Fac as $row) {
                $Pld_Cod = $obBD_con1->getRowConsulta(19, $row['Pro_Cod'], $obBD_conexion);
                $obBD_conIns->operacionobBD(20, $Com_Cod . '*' . 'H' . '*' . $row['Vet_Imp'] . '*' . $row['Ite_Lar'] . '*' . $Pld_Cod['Pld_Cod'], $obBD_conexionIns);
            }

            //Secci�n para insertar el descuento en la tabla asientos
            if ($t_descuento * 1 > 0) {
                $Pld_Cod_Dsc = $obBD_con1->getRowConsulta(48, "DV", $obBD_conexion);
                if (empty($Pld_Cod_Dsc['Pld_Cod'])) {
                    throw new Exception("Falta parametrizar la cuenta contable de DESCUENTO..!!");
                }
                $obBD_conIns->operacionobBD(20, $Com_Cod . '*' . 'D' . '*' . $t_descuento . '*' . 'ASIENTO DE DESCUENTO' . '*' . $Pld_Cod_Dsc['Pld_Cod'], $obBD_conexionIns);
            }

            //Secci�n para insertar el ice en la tabla asientos
            if ($t_ice * 1 > 0) {
                $Pld_Cod_Ice = $obBD_con1->getRowConsulta(48, "ICV", $obBD_conexion);
                if (empty($Pld_Cod_Ice['Pld_Cod'])) {
                    throw new Exception("Falta parametrizar la cuenta contable de tipo ICE..!!");
                }
                $obBD_conIns->operacionobBD(20, $Com_Cod . '*' . 'H' . '*' . $t_ice . '*' . 'ASIENTO DE ICE' . '*' . $Pld_Cod_Ice['Pld_Cod'], $obBD_conexionIns);
            }

            //Secci�n para guardar asiento del iva en caso de que este sea mayor a cero
            if (isset($t_iva) && ($t_iva * 1) > 0) {
                $Pld_Cod_Iva = $obBD_con1->getRowConsulta(22, $Pec_Cod, $obBD_conexion);
                if (empty($Pld_Cod_Iva['Pld_Cod'])) {
                    throw new Exception("Falta parametrizar el IVA cobrado..!!");
                }
                $obBD_conIns->operacionobBD(20, $Com_Cod . '*' . 'H' . '*' . $t_iva . '*' . 'ASIENTO DE IVA' . '*' . $Pld_Cod_Iva['Pld_Cod'], $obBD_conexionIns);
            }
        }

        //Secci�n para efectuar un insert en la tabla pago_venta
        $obBD_conIns->operacionobBD(15, $Vet_Cod . '*' . $Bak_Cod . '*' . $Ban_Cod . '*' . $Pag_Cod . '*' . $Vet_Cue . '*' . $Vet_Che . '*' . $Vet_Tot, $obBD_conexionIns);

        //Seccion para efectuar la imprecion de documentos: factura, nota de venta, comprobante, etc.
        $pagina = 'fac_alt_fac_ven_manual';
        $imprimir = $obBD_con1->reportes($pagina, $Ses_Emp_Cod, $obBD_conexion);
        $url_fac = $imprimir[1] . '?Vet_Cod=' . $Vet_Cod;
        $response['url_fac'] = $url_fac;
        $url_com = $imprimir[2] . '?codigo=' . $Com_Cod . '&tabla=cliente&campo=Cli_Cod&tipo=7&Pec_Cod=' . $Pec_Cod;
        $response['url_com'] = $url_com;
        $url_fac = $imprimir[3] . '?Vet_Cod=' . $Vet_Cod;
        $response['url_gru'] = $url_fac;
        //Secci�n para imprimir detalle de factura
        $response['url_dfa'] = 'tca_pri_detfactura_1.0.php?Vet_Cod=' . $Vet_Cod;
    } catch (Exception $e) {
        mysqli_rollback($obBD_conexionIns->conexion);
        $response['message'] = $e->getMessage();
        echo json_encode($response);
        exit();
    }
    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
    if ($obBD_conIns->Error == 0) {
        $response['success'] = true;
    } else {
        $responce = array(success => false, message => "No se ha logrado realizar la Transaccion", error => $obBD_ins1->MsgError);
    }
    echo json_encode($response);
    exit();
}

if (isset($cargarAsiento)) {
    if ($Com_Cod * 1 > 0) {
        //Seccion para obtener los datos de la cabecera del comprobante
        $cab_com = $obBD_con1->getRowConsulta(50, $Com_Cod, $obBD_conexion);
        $response['cab_com'] = $cab_com;

        //Seccion para obtener los datos de los asientos concernientes al comprobante
        $det_asi = $obBD_con1->getArrayConsulta(49, $Com_Cod, $obBD_conexion);
        $response['det_asi'] = $det_asi;
    }
    //Seccion para obtener los datos de la cabecera de la factura
    $cab_fac = $obBD_con1->getRowConsulta(31, $Ses_Emp_Cod . '*' . $Vet_Cod, $obBD_conexion);
    $response['cab_fac'] = $cab_fac;

    //Seccion para obtener los datos del detalle de la factura
    $detalle = $obBD_con1->getArrayConsulta(32, $Vet_Cod, $obBD_conexion);
    $response['det_fac'] = $detalle;

    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script language="javascript" src="../VALIDACIONES/tca_factura.js?e=1"></script>
    <style>
        .footrow td[aria-describedby="documento_Cop_Imp"],
        .footrow td[aria-describedby="documento_Cop_Pru"] {
            padding: 0 !important;
        }

        .footerFact {
            text-align: right;
            width: 100%;
        }

        .footerFact input[type=text],
        .footerFact label,
        .footerFact textarea,
        .footerFact select {
            height: 19px;
            width: 100% !important;
            display: block;
            margin-bottom: 0px !important;
            margin-top: 0px !important;
            text-align: right;
        }

        .footerFact textarea {
            text-align: left;
            height: 75px !important;
        }

        .footerFact select {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
            display: inline;
        }

        .footerFact label {
            height: 19px;
            line-height: 18px;
            padding-right: 5px;
        }

        .footerFact label.total,
        .footerFact input.total {
            background-color: #254463;
            color: white;
            font-size: 14px;
            border: none;
        }

        #jqGridButtonDiv {
            float: right;
            padding-right: 10px;
            position: relative;
            top: -1px;
        }
    </style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Modificar Factura</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="bus_fac" class="row">
                <div class="col-md-12">
                    <form id="fom_bus" name="fom_bus" class="form-horizontal normal" action="javascript:$('#Lis_Fac').Search('#fom_bus','facturasAjax');">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">B&uacute;squeda de Clientes</legend>
                            <div class="form-group">
                                <label class="col-sm-1 control-label label-xs">Filtrar por:</label>
                                <div class="col-sm-5 radioset">
                                    <input id="rad_ba1" name="op_opciones" type="radio" value="ced" checked="" onclick="setfocus(this.form.search)" /><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                                    <input id="rad_ba2" name="op_opciones" type="radio" value="cli" onclick="setfocus(this.form.search)" /><label for="rad_ba2">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
                                    <input id="rad_ba3" name="op_opciones" type="radio" value="nro" onclick="setfocus(this.form.search)" /><label for="rad_ba3">&nbsp;&nbsp;Nro. Documento&nbsp;&nbsp;</label>
                                </div>

                                <label class="col-sm-1 control-label label-xs">Estado:</label>
                                <div class="col-sm-4 radioset">
                                    <input id="op_est3" name="op_est" type="radio" value="T"
                                        style="cursor:pointer"><label for="op_est3"> Todas </label>
                                    <input id="op_est1" name="op_est" type="radio" value="A" checked='checked'
                                        style="cursor:pointer"><label for="op_est1"> Activas </label>
                                    <input id="op_est2" name="op_est" type="radio" value="I"
                                        style="cursor:pointer"><label for="op_est2">Anuladas</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-1 control-label label-xs">B&uacute;squeda:</label>
                                <div class="col-sm-5">
                                    <div class="input-group">
                                        <input type="text" id="search" name="search" onkeydown="if (event.keyCode === 13)
                                            this.form.submit()" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                        </span>
                                    </div>
                                </div>
                                <label class="col-sm-1 control-label label-xs">Fechas:</label>
                                <div class="col-sm-4">
                                    <div class="input-group input-group-sm dateRangeInputs" style="width: 400px;">
                                        <span class="range input-group-addon alert-info">Desde</span>
                                        <input type="text" name="Fec_Ini" class="form-control range datepicker" style="text-align: center;" required="" value="<?php echo isset($_GET['Fec_Ini']) ? $_GET['Fec_Ini'] : date('Y-m-01'); ?>" />
                                        <span class="range input-group-addon alert-info">Hasta</span>
                                        <input type="text" name="Fec_Fin" class="form-control range datepicker" style="text-align: center;" required="" value="<?php echo isset($_GET['Fec_Fin']) ? $_GET['Fec_Fin'] : date('Y-m-d'); ?>" />
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                    <div>
                        <table id="Lis_Fac"></table>
                        <div id="Pag_Lis"></div>
                    </div>
                </div>
            </div>
            <div id="fac_tur" class="row" style="display: none;">
                <div class="col-md-12">
                    <form id="frm_cab" name="frm_cab" class="form-horizontal normal" action="javascript:">
                        <!--Campo de c�digo de cliente-->
                        <input type="hidden" id="Cli_Cod" name="Cli_Cod" value="0">
                        <!--Campo que indica si lleva contabilidad o no-->
                        <input type="hidden" id="Cof_Con" name="Cof_Con">
                        <!--Campo de c�digo de venta Vet_Cod-->
                        <input type="hidden" id="Vet_Cod" name="Vet_Cod">
                        <!--Campo de c�digo de periodo contable-->
                        <input type="hidden" id="Pec_Cod" name="Pec_Cod">
                        <!--Campo de c�digo de ccpp_cobrar-->
                        <input type="hidden" id="Cpc_Cod" name="Cpc_Cod">
                        <!--Campo de c�digo de comprobantes-->
                        <input type="hidden" id="Com_Cod" name="Com_Cod">
                        <div class="row">
                            <div class="col-md-5">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Datos de Cliente</legend>
                                    <div class="col-md-12 col-sm-12">
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-4 label-sm required">C&eacute;dula/R.U.C.:</label>
                                            <div class="col-md-7 col-sm-7">
                                                <div class="input-group">
                                                    <input type="text" id="Prs_Ced" name="Prs_Ced" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="">
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-success btn-xs" onclick="if($('#Prs_Ced').val() !== ''){$('#viajeDialog').dialog('open');$.Search('viaje');} else {$.alert('Debe seleccionar un Cliente..!!');}" title="Seleccionar Viajes"><span class="glyphicon glyphicon-transfer"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-4 label-xs">Cliente:</label>
                                            <div class="col-md-7 col-sm-7">
                                                <input type="text" id="cliente" name="cliente" class="form-control input-xs" readonly="">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-4 label-xs">Facturar a:</label>
                                            <div class="col-md-7 col-sm-7">
                                                <div class="input-group">
                                                    <input type="text" id="Prs_Ced1" name="Prs_Ced1" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-success btn-xs" type="button" title="Cambiar Cliente" onclick="$('#clientefacturaDialog').dialog('open');"><span class="glyphicon glyphicon-user"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-4 label-xs">Cliente:</label>
                                            <div class="col-md-7 col-sm-7">
                                                <input type="text" id="cliente1" name="cliente1" class="form-control input-xs" readonly="">
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-md-7">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Datos Encabezado Factura</legend>
                                    <div class="col-md-12 col-sm-12">
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-5 label-sm required">Tipo Dcto.:</label>
                                            <div class="col-md-3 col-sm-7">
                                                <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs" required=""></select>
                                            </div>
                                            <div id="periodo" style="display: none;">
                                                <label class="control-label col-md-2 col-sm-4 label-xs">Periodo:</label>
                                                <div class="col-md-3 col-sm-7">
                                                    <input type="text" id="Anio" name="Anio" class="form-control input-xs" readonly="" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-4 label-xs">Fecha:</label>
                                            <div class="col-md-3 col-sm-7">
                                                <input type="text" id="Caj_Fec" name="Caj_Fec" class="form-control input-xs datepicker" required="">
                                            </div>
                                            <label class="control-label col-md-2 col-sm-4 label-xs">Ciudad:</label>
                                            <div class="col-md-3 col-sm-7">
                                                <?php $Ciu_Des = $obBD_con1->getRowConsulta(5, $Ses_Usu_Cod, $obBD_conexion); ?>
                                                <input type="hidden" id="Ciu_Cod" name="Ciu_Cod" value="<?php echo $Ciu_Des['Ciu_Cod'] ?>">
                                                <input type="text" id="Ciu_De1" name="Ciu_De1" class="form-control input-xs" readonly="" value="<?php echo $Ciu_Des['Ciu_Des'] ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-4 label-xs">Nro. Secuencia:</label>
                                            <div class="col-md-3 col-sm-7">
                                                <input type="text" id="Vet_Num" name="Vet_Num" class="form-control input-xs" required="" onkeypress="return validar_numeric(event);">
                                            </div>
                                            <label class="control-label col-md-2 col-sm-4 label-xs">Autorizaci&oacute;n:</label>
                                            <div class="col-md-3 col-sm-7">
                                                <input type="text" id="Aut_Sri" name="Aut_Sri" class="form-control input-xs" readonly="">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-4 label-xs">Observaci&oacute;n:</label>
                                            <div class="col-md-8 col-sm-7">
                                                <textarea id="Vet_Obs" name="Vet_Obs" class="form-control input-xs" style="resize: none;"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </form>
                    <form id="frm_fpa" name="frm_fpa" action="javascript:">
                        <div class="row">
                            <div class="col-md-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Forma de Pago</legend>
                                    <div id="tpa_com" class="form-group col-md-2" style="display: none;">
                                        <label class="control-label label-xs" style="font-size: 11px;">Tipo:</label>
                                        <select id="Tpc_Cod" name="Tpc_Cod" class="form-control input-xs"></select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label class="control-label label-xs" style="font-size: 11px;">Forma:</label>
                                        <select id="For_Cod" name="For_Cod" class="form-control input-xs credito"></select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label class="control-label label-xs" style="font-size: 11px;">Tipo:</label>
                                        <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs credito"></select>
                                    </div>
                                    <div id="Con_Cue">
                                        <div class="form-group col-md-2">
                                            <label class="control-label label-xs" style="font-size: 11px;">Cuenta:</label>
                                            <select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs"></select>
                                        </div>
                                    </div>
                                    <div id="Cre_Bak" style="display: none;">
                                        <div class="form-group col-md-2">
                                            <label class="control-label label-xs" style="font-size: 11px;">Banco:</label>
                                            <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs credito"></select>
                                        </div>
                                    </div>
                                    <div id="Con_Che" style="display: none;">
                                        <div class="form-group col-md-2">
                                            <label class="control-label label-xs" style="font-size: 11px;">Nro. Cuenta:</label>
                                            <input type="text" id="Vet_Cue" name="Vet_Cue" class="form-control input-xs">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label class="control-label label-xs" style="font-size: 11px;">Nro. Cheque:</label>
                                            <input type="text" id="Vet_Che" name="Vet_Che" class="form-control input-xs">
                                        </div>
                                    </div>
                                    <div id="Cre_Dto" style="display: none;">
                                        <div class="form-group col-md-2">
                                            <label class="control-label label-xs" style="font-size: 11px;">Cta. Deudora:</label>
                                            <select id="Pld_Cod" name="Pld_Cod" class="form-control input-xs credito"></select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label class="control-label label-xs" style="font-size: 11px;">Fecha Vencimiento:</label>
                                            <input type="text" id="Cpc_Ven" name="Cpc_Ven" class="form-control input-xs datepicker credito" placeholder="Ingrese fecha" required="">
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-md-12">
                            <table id="Det_Fac"></table>
                            <div id="Lis_Dfa"></div>
                        </div>
                    </div>
                    <div style="padding-top: 5px;">
                        <button type="button" id="btn_atr" name="btn_atr" onclick="$('#fac_tur').moveComp('#bus_fac');" class="btn btn-inverse fileinput-button btn-xs"><span class="glyphicon glyphicon-arrow-left"></span> Atr&aacute;s</button>
                        <button type="button" id="btn_gua" name="btn_gua" onclick="saveFactura();" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                    </div>
                </div>
            </div>
            <div id="imp_asi" style="display: none;">
                <div class="row">
                    <div class="col-md-12">
                        <div style="text-align: center;">
                            <h3><b>Transaccion Realizada con &Eacute;xito</b></h3>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div style="padding: 5px; text-align: center;">
                            <button type="button" onclick="$('#imp_asi').moveComp('#bus_fac');$('#Lis_Fac').jqGrid('resizeGrid');" class="btn btn-success btn-sm"><span class="glyphicon glyphicon-edit"></span> Nueva Edici&oacute;n</butto>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Factura de Venta</legend>
                            <div class="row">
                                <div class="col-md-12">
                                    <form id="frm_cfa" name="frm_cfa" class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="control-label col-sm-2 label-xs">C&eacute;dula/R.U.C.:</label>
                                            <div class="col-sm-4">
                                                <span name="Prs_Ced" class="form-control input-xs datatitle"></span>
                                            </div>
                                            <label class="control-label col-sm-3 label-xs">Tipo Dcto.:</label>
                                            <div class="col-sm-3">
                                                <span name="Tic_Des" class="form-control input-xs datatitle"></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-2 label-xs">Cliente:</label>
                                            <div class="col-sm-4">
                                                <span name="cliente" class="form-control input-xs datatitle"></span>
                                            </div>
                                            <label class="control-label col-sm-3 label-xs">Nro.:</label>
                                            <div class="col-sm-3">
                                                <span name="Vet_Num" class="form-control input-xs datatitle"></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-2 label-xs">Ciudad:</label>
                                            <div class="col-sm-4">
                                                <span name="Ciu_De1" class="form-control input-xs datatitle"></span>
                                            </div>
                                            <label class="control-label col-sm-3 label-xs">Fecha:</label>
                                            <div class="col-sm-3">
                                                <span name="Caj_Fec" class="form-control input-xs datatitle"></span>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <table id="Imp_Fac"></table>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-md-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Resultado de Comprobante</legend>
                            <div class="row">
                                <div class="col-md-12 col-sm-12">
                                    <form id="frm_cco" name="frm_cco" class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="control-label col-sm-2 label-xs">Tipo:</label>
                                            <div class="col-sm-3">
                                                <span name="Tia_Des" class="form-control input-xs datatitle"></span>
                                            </div>
                                            <label class="control-label col-sm-3 label-xs">Fecha:</label>
                                            <div class="col-sm-3">
                                                <span name="Com_Fec" class="form-control input-xs datatitle"></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-2 label-xs">Nro.:</label>
                                            <div class="col-sm-3">
                                                <span name="Nro_Com" class="form-control input-xs datatitle"></span>
                                            </div>
                                            <label class="control-label col-sm-3 label-xs">Valor:</label>
                                            <div class="col-sm-3">
                                                <span name="Com_Val" class="form-control input-xs datatitle"></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-2 label-xs">Concepto:</label>
                                            <div class="col-sm-9">
                                                <span name="Com_Con" class="form-control input-xs datatitle"></span>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <table id="Imp_Asi"></table>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inicio del di�logo para listar viajes seg�n cliente seleccionado -->
    <div id="viajeDialog" title="Listado de viajes realizados">
        <form class="form-horizontal normal"><input type="hidden" name="Cli_Cod" /></form>
    </div>

    <!-- Inicio del di�logo para buscar un cliente y cambiarlo al momento de realizar la factura -->
    <div id="clientefacturaDialog" title="B&uacute;squeda de Clientes">
        <form class="form-horizontal normal"></form>
    </div>

    <!-- Inicio del di�logo para mostrar el detalle de la factura -->
    <div id="detfacturaDialog" title="Detallle de Factura">
        <div class="row">
            <div class="col-md-12">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Detalle de Factura</legend>
                    <form id="frm_det" class="form-horizontal normal">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-xs">C&eacute;dula/R.U.C.:</label>
                                <div class="col-sm-7">
                                    <span name="Prs_Ced" class="form-control input-xs datatitle"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-xs">Cliente:</label>
                                <div class="col-sm-7">
                                    <span name="cliente" class="form-control input-xs datatitle"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-xs">F.Pago:</label>
                                <div class="col-sm-7">
                                    <span name="For_Des" class="form-control input-xs datatitle"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-xs">Nro.Doc:</label>
                                <div class="col-sm-8">
                                    <span name="Vet_Num" class="form-control input-xs datatitle"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-xs">Fecha:</label>
                                <div class="col-sm-8">
                                    <span name="Caj_Fec" class="form-control input-xs datatitle"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-xs">T.Pago:</label>
                                <div class="col-sm-8">
                                    <span name="Pag_Des" class="form-control input-xs datatitle"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-xs">V. Total:</label>
                                <div class="col-sm-8">
                                    <span name="Com_Val" class="form-control input-xs" style="font-weight: bold; font-size: 14px;"></span>
                                </div>
                            </div>
                        </div>
                    </form>
                </fieldset>
            </div>
            <div class="col-md-12 condensed">
                <table id="list_dfa"></table>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(function() {
            //Inicializaci�n
            $.createDatePickers('.datepicker');

            //Inicio Grid para presentar el detalle de factura
            $("#Lis_Fac").createGrid({
                postData: $("#fom_bus").getData("facturasAjax"),
                height: 295,
                colModel: [{
                        label: 'C&oacute;d. Int.',
                        name: 'Vet_Cod',
                        width: 50,
                        align: "center"
                    },
                    {
                        label: 'Nro. Documento',
                        name: 'Vet_Num',
                        width: 50,
                        align: "center"
                    },
                    {
                        label: 'C&eacute;dula/R.U.C.',
                        name: 'Prs_Ced',
                        width: 50,
                        align: "center"
                    },
                    {
                        label: 'Cliente',
                        name: 'Cli_Nte',
                        width: 150,
                        align: "center"
                    },
                    {
                        label: 'Fecha',
                        name: 'Caj_Fec',
                        width: 50,
                        align: "center"
                    },
                    {
                        label: 'Estado',
                        name: 'Vet_Est',
                        width: 50,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: '&nbsp;',
                        name: 'act2',
                        width: 30,
                        align: 'center',
                        viewable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            return $.getGridButton(cargarDetalle, rowObject, 'Detalle de Factura', 'glyphicon glyphicon-info-sign', null, 'btn btn-info');
                        }
                    },
                    {
                        label: '&nbsp;',
                        name: 'act1',
                        width: 30,
                        align: 'center',
                        viewable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            return $.getGridButton(cargarFactura, rowObject, 'Editar Factura', 'glyphicon glyphicon-edit');
                        }
                    }
                ]
            }, false, "#Pag_Lis");

            //Se declara el jqgrid para presentar informaci�n de la afiliaci�n de un empleado
            $("#list_dfa").createGrid({
                height: 200,
                width: 660,
                responsive: false,
                colModel: [{
                        label: 'Cantidad',
                        name: 'Vet_Can',
                        width: 120,
                        align: "center"
                    },
                    {
                        label: 'Descripci&oacute;n',
                        name: 'Car_Des',
                        width: 120,
                        align: "center"
                    },
                    {
                        label: 'Precio U.',
                        name: 'Vet_Pru',
                        width: 100,
                        align: "right"
                    },
                    {
                        label: 'Importe',
                        name: 'Vet_Imp',
                        width: 100,
                        align: "right"
                    }
                ]
            }, true);
            //Inicio del di�logo para presentar el historial de afiliaciones
            $('#detfacturaDialog').createDialog({
                icon: 'glyphicon glyphicon-th',
                height: 400,
                width: 700
            });

            //Jqgrid para presentar los viajes sin facturar de un determinado cliente
            $.createSearchDialog('#viajeDialog', [{
                    label: 'C&oacute;d.',
                    align: 'center',
                    name: 'Via_Cod',
                    width: 20
                },
                {
                    label: 'Fecha Viaje',
                    align: 'center',
                    name: 'Via_Fec',
                    width: 50
                },
                {
                    label: 'Cargamento',
                    align: 'center',
                    name: 'Car_Des',
                    width: 70
                },
                {
                    label: 'Conductor',
                    align: 'center',
                    name: 'Cho_Fer',
                    width: 120
                },
                {
                    label: 'Veh&iacute;culo',
                    align: 'center',
                    name: 'Veh_Pla',
                    width: 50
                },
                {
                    label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                    name: 'act1',
                    width: 18,
                    align: 'center',
                    viewable: false,
                    formatter: function(cellvalue, options, rowObject) {
                        return $.getGridButton(cargarItem, rowObject, 'Seleccionar Viaje', 'glyphicon glyphicon-ok');
                    }
                }
            ], null, null, null, null, {
                title: 'Viajes',
                options: [{
                        label: '&nbsp;&nbsp;Veh&iacute;culo&nbsp;&nbsp;',
                        value: 'd'
                    },
                    {
                        label: '&nbsp;&nbsp;Fecha&nbsp;&nbsp;',
                        value: 'c'
                    }
                ]
            });

            //Change para obtener el porcentaje de iva
            $('#For_Cod').change(function() {
                (this.value === '2') ? ($("[id^='Cre_']").show(), $("[id^='Con_']").hide(), cargarCtadeu($('#Pec_Cod').val())) : ($("[id^='Cre_']").hide(), $('#Con_Cue').show());
                cargarTipago(this.value);
            });

            //Change para obtener el porcentaje de iva
            $('#Pag_Cod').change(function() {
                (this.value === '3') ? $('#Con_Che').show(): ($('#Con_Che').hide(), $('#Vet_Cue').val(''), $('#Vet_Che').val(''));
                cargarBancos(this.value);
            });

        });

        /*** FUNCIONES PARA EL MANEJO DE DATOS ***/

        //Funci�n para cargar el detalle de la factura
        function cargarDetalle(factura) {
            $('#detfacturaDialog').dialog('open');
            $.post("<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", {
                cargarAsiento: true,
                Com_Cod: 0,
                Vet_Cod: factura.Vet_Cod
            }, function(response) {
                $('#list_dfa').setRowsByIndex(response['det_fac']);
                $('#frm_det').setData(response['cab_fac']);
            }, 'json').fail(function() {
                $.alert();
            });
        }

        //Funci�n para cargar los datos de la factura
        var arreglo = [],
            subtotal = 0,
            total = 0,
            Tot_Pag = 0;

        function cargarFactura(factura) {
            $("#Det_Fac").jqGrid('resizeGrid');
            $.post("<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", {
                cargarFactura: true,
                Vet_Cod: factura.Vet_Cod
            }, function(response) {
                $("[name='Cli_Cod']").val(response['Cli_Cod']);
                arreglo = response.Det_Ven;
                $('#Det_Fac').setRowsByIndex(arreglo);
                (response.Cof_Con === 'S') ? $('#periodo').show(): $('#periodo').hide();
                $('#Caj_Fec').dateLimits(response.Pec_Fei, response.Pec_Fef);
                $("[id^='frm_']").setData(response);
                (response.For_Cod === '2') ? ($("[id^='Cre_']").show(), $("[id^='Con_']").hide()) : ($("[id^='Cre_']").hide(), $("[id^='Con_']").show());
                (response.Pag_Cod === '3') ? $('#Con_Che').show(): $('#Con_Che').hide();
                (response.Vet_Tot * 1 > 1000) ? $('#tpa_com').show(): $('#tpa_com').hide();
                $('#bus_fac').moveComp('#fac_tur');
                $('.iva_por').text($("#Iva_Cod option:selected").text());
                aut_ini = response.Aut_Ini;
                aut_fin = response.Aut_Fin;
                num_fac = response.Vet_Num;
                Tot_Pag = response.Tot_Pag;
                (Tot_Pag * 1 > 0) ? ($('.credito').prop('disabled', true), $('.credito').addClass('readOnly')) : ($('.credito').prop('disabled', false), $('.credito').removeClass('readOnly'));
            }, 'json').fail(function() {
                $.alert();
            });
        }

        //Funci�n para cargar los datos de un cliente seleccionado
        function cargarCliente(cliente) {
            $('#clienteDialog').dialog('close');
            $('input[name=Cli_Cod]').val(cliente.Cli_Cod);
            $('#frm_cab').setData(cliente, false);
        }

        //Funci�n para cargar items al detalle de factura
        function cargarItem(item) {
            $("#Det_Fac").jqGrid('resizeGrid');
            if (!$.arrayExistsVal(arreglo, 'Via_Cod', item['Via_Cod'])) {
                arreglo.push(item);
                calcular();
            } else {
                $.alert('Viaje ya esta cargado en Detalle de Factura..!!');
            }
        }

        //Funci�n para guardar un viaje
        function saveFactura() {
            if (!$.varValid(Tot_Pag)) {
                Tot_Pag = 0;
            }
            if ($('#Vet_Tot').val() * 1 < Tot_Pag) {
                $.alert('$' + $('#Vet_Tot').val() + ' No puede ser menor que $' + Tot_Pag);
                return;
            }
            if (arreglo.length <= 0) {
                $.alert('Debe agregar items al detalle de factura..!!');
                return;
            }
            if (!$('#frm_fpa').valid()) {
                setTimeout(function() {
                    $('#frm_fpa').formSubmit();
                }, 0);
                return;
            };
            if (!$('#frm_cab').valid()) {
                setTimeout(function() {
                    $('#frm_cab').formSubmit();
                }, 0);
                return;
            };
            var data = $("[id^='frm_']").getData('saveFactura');
            data['Det_Fac'] = $("#Det_Fac").getGridBatch();

            $.saveDataJson("<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", data, function(response) {
                asientos($('#Com_Cod').val(), $('#Vet_Cod').val());
                $('#frm_cab')[0].reset();
                $('#frm_dat')[0].reset();
                $('#frm_fpa')[0].reset();
                $('#Fac_Fec').val('<?php echo $hoy; ?>');
                $("#Det_Fac").setRowsByIndex("");
                $('#Lis_Fac').trigger('reloadGrid');
                $("[id^='Cre_']").hide();
                $('#tpa_com').hide();
                $('#Con_Cue').show();
                $('#imprimir_fac').data('url_fac', response['url_fac']);
                $('#imprimir_com').data('url_com', response['url_com']);
                $('#imprimir_gru').data('url_gru', response['url_gru']);
                $('#imprimir_dfa').data('url_dfa', response['url_dfa']);
                $('#fac_tur').moveComp('#imp_asi');
                return false;
            });
        }
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script>
        $.clearValidate();
    </script>
</BODY>

</HTML>