<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por lotes
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_ccpp.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/** 
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Che;
/**
 * Evita el reenvio 
 */
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");


if (isset($dataReport)) {
    $responce['success'] = false;
    $table = array();
    if (!empty($Pec_Cod)) {
        $periodo = $obBD_con1->getRowConsulta(59, $Pec_Cod, $obBD_conexion);
        $txt_fec_ini = '0001-01-01';
        $txt_fec_fin = $periodo['Pec_Fef'];
        $Pec_Cod = '';
    }
    if ($tipo == 'true') $b = '1px';
    else $b = '0.1pt';
    $table['{body}'] = '';
    $table['{caption}'] = $caption;
    $table['{empresa}'] = $Ses_Emp_Nom;
    $fecha = explode('-', $hoy);

    $table['{fecha}'] = ' ' . dias(calcula_numero_dia_semana($fecha[2], $fecha[1], $fecha[0]), 1) . ', ' . $fecha[2] . ' de ' . mes($fecha[1], 1) . ' de ' . $fecha[0];
    $saldoGeneral = 0;
    foreach ($dataReport as $provee) {
        $saldoProvee = 0;
        $table['{body}'] = $table['{body}'] . '<tr><td colspan="7" style="border: ' . $b . ' solid #000;"><b>Proveedor:</b> ' . $provee['Proveedor'] . '</td>
    <td style="border: ' . $b . ' solid #000;text-align:center;">Total</td>
        <td style="border: ' . $b . ' solid #000;text-align:center;">Abonos</td>
        <td style="border: ' . $b . ' solid #000;text-align:center;">Saldo</td>
        </tr>';
        foreach ($provee['Cpps'] as $cuenta) {
            $Cpp_Data = $obBD_con1->getRowConsulta(34, $cuenta, $obBD_conexion);
            if ($Cpp_Data == NULL) $Cpp_Data = $obBD_con1->getRowConsulta(54, $cuenta, $obBD_conexion);
            $saldo = $Cpp_Data['total'] * 1;
            $table['{body}'] = $table['{body}'] . '<tr><td style="font-weight:bold;"  colspan="2">' . $Cpp_Data['Com_Codigo'] . '</td>  	    
                <td style="font-weight:bold;" >' . $Cpp_Data['Com_Fec'] . '</td> 
                <td style="font-weight:bold;" colspan="4">Fact.: ' . $Cpp_Data['Cop_Num'] . ' -  ' . $provee['Proveedor'] . '</td>
                <td style="text-align:center;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($Cpp_Data['total'], 2) . '</td></tr>';

            $pagoRetencion = false;
            if ($Cpp_Data['Cpp_Cod'] != NULL) {
                $cancelaciones = $obBD_con1->getArrayConsulta(57, $Cpp_Data['Cpp_Cod'] . '*' . $Pec_Cod . '*' . $txt_fec_ini . '*' . $txt_fec_fin, $obBD_conexion);
                foreach ($cancelaciones as $pago) {

                    if ($pago['Pag_Cod'] != 50) {

                        $banco = NULL;
                        $PagAbr = 'EF';
                        if ($pago['Pag_Cod'] == 2) $PagAbr = 'TC';
                        if ($pago['Pag_Cod'] == 3) $PagAbr = 'CH';

                        if ($PagAbr != 'EF')
                            $banco = $obBD_con1->getRowConsulta(36, $pago['Com_Cod'], $obBD_conexion);
                        $saldo = $saldo - $pago['Pag_Val'];
                        $table['{body}'] = $table['{body}'] . '<tr>
                            <td style="font-weight:bold;border-right: ' . $b . ' solid #000;">&gt;</td>
                            <td>' . $pago['Com_Codigo'] . '</td>
                            <td style="text-align:center;">' . $pago['Pag_Fec'] . '</td>
                            <td>' . $PagAbr . '</td>
                            <td style="mso-number-format:&#39;@&#39;;">' . ($banco != NULL ? $banco['Che_Num'] : '') . '</td>
                            <td style="white-space:nowrap;overflow:hidden;">' . ($banco != NULL ? $banco['Banco'] : '') . '</td>
                            <td style="text-align:center;">' . ($banco != NULL ? $banco['Che_Fec'] : '') . '</td>
                            <td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;" colspan="2">' . number_format($pago['Pag_Val'], 2) . '</td>
                            <td></td>
                        </tr>';
                    }
                }
            }

            if ($pagoRetencion == false) {
                $Retenciones = $obBD_con1->getArrayConsulta(35, $Cpp_Data['Cop_Cod'], $obBD_conexion);
                foreach ($Retenciones as $ret) {
                    $saldo = $saldo - round($ret['retencion'], 2);
                    $table['{body}'] = $table['{body}'] . '<tr><td style="font-weight:bold;border-right: ' . $b . ' solid #000;">&gt;</td><td style="color:gray;">--------------</td><td style="text-align:center;">' . $ret['Ret_Fec'] . '</td><td>' . $ret['tipo'] . '</td><td colspan="2" style="mso-number-format:&#39;@&#39;;">' . $ret['Ret_Num'] . '</td><td></td><td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;" colspan="2">' . number_format($ret['retencion'], 2) . '</td><td></td></tr>';
                }
            }


            $table['{body}'] = $table['{body}'] . '<tr><td></td><td>&nbsp;</td><td>&nbsp;</td><td></td><td>&nbsp;</td><td colspan="4" style="text-align:right;border-top: ' . $b . ' solid #000;">SALDO DOCUMENTO:</td><td style="border-top: ' . $b . ' solid #000;text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldo, 2) . '</td></tr><tr><td colspan="9" style="height:20px;"></td></tr>';
            $saldoProvee = $saldoProvee + $saldo;
        }
        $table['{body}'] = $table['{body}'] . '<tr><td colspan="9" style="height:10px;"></td></tr>
                    <tr>
                    <td></td>
                <td></td>
                <td></td>   
                <td></td>
                <td></td>           
                    <td colspan="4" style="border-top: ' . $b . ' solid #000; border-bottom: 3px double #000; font-weight:bold;text-align:right;">TOTAL PROVEEDOR: ' . $provee['Proveedor'] . ' &nbsp;&gt;&gt;</td>
                <td style="border-bottom: 3px double #000; border-top: ' . $b . ' solid #000; text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoProvee, 2) . '</td>
                    </tr>
                <tr>
                <td colspan="9"></td>
                </tr>';
        $saldoGeneral = $saldoGeneral + $saldoProvee;
    }
    $table['{saldoFinal}'] = number_format($saldoGeneral, 2);
    $responce['html'] = reporteHtml($table, 'tes_pri_ccpp_pagos.html');
    $responce['success'] = true;

    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}
/*
if (isset($dataReport)) {
    $responce['success'] = false;
    $table = array();
    if (!empty($Pec_Cod)) {
        $periodo = $obBD_con1->getRowConsulta(59, $Pec_Cod, $obBD_conexion);
        $txt_fec_ini = '0001-01-01';
        $txt_fec_fin = $periodo['Pec_Fef'];
        $Pec_Cod = '';
    }
    if ($tipo == 'true') $b = '1px';
    else $b = '0.1pt';
    $table['{body}'] = '';
    $table['{caption}'] = $caption;
    $table['{empresa}'] = $Ses_Emp_Nom;
    $fecha = explode('-', $hoy);

    $table['{fecha}'] =  ' ' . dias(calcula_numero_dia_semana($fecha[2], $fecha[1], $fecha[0]), 1) . ', ' . $fecha[2] . ' de ' . mes($fecha[1], 1) . ' de ' . $fecha[0];
    $saldoGeneral = 0;


    if ($resumido1 != "T") {

        foreach ($dataReport as $provee) {
            $saldoProvee = 0;
            $table['{body}'] = $table['{body}'] . '<tr><td colspan="7" style="border: ' . $b . ' solid #000;"><b>Proveedor:</b> ' . $provee['Proveedor'] . '</td>
	        <td style="border: ' . $b . ' solid #000;text-align:center;">Total</td>
            <td style="border: ' . $b . ' solid #000;text-align:center;">Abonos</td>
            <td style="border: ' . $b . ' solid #000;text-align:center;">Saldo</td>
            </tr>';
            foreach ($provee['Cpps'] as $cuenta) {
                $Cpp_Data = $obBD_con1->getRowConsulta(34, $cuenta, $obBD_conexion);
                if ($Cpp_Data == NULL) {
                    $Cpp_Data = $obBD_con1->getRowConsulta(54, $cuenta, $obBD_conexion);
                }
                $saldo = $Cpp_Data['total'] * 1;
                $pagoRetencion = false;
                $table['{body}'] = $table['{body}'] . '<tr><td style="font-weight:bold;"  colspan="2">' . $Cpp_Data['Com_Codigo'] . ' ::' .   $saldoa  . '  </td>  	    
                    <td style="font-weight:bold;" >' . $Cpp_Data['Com_Fec'] . '</td> 
                    <td style="font-weight:bold;" colspan="4">Fact.: ' . $Cpp_Data['Cop_Num'] . ' -  ' . $provee['Proveedor'] . '</td>
                    <td style="text-align:center;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($Cpp_Data['total'], 2) . '</td></tr>';

                if ($Cpp_Data['Cpp_Cod'] != NULL) {
                    $cancelaciones = $obBD_con1->getArrayConsulta(57, $Cpp_Data['Cpp_Cod'] . '*' . $Pec_Cod . '*' . $txt_fec_ini . '*' . $txt_fec_fin, $obBD_conexion);
                    foreach ($cancelaciones as $pago) {
                        if ($pago['Pag_Cod'] != 50) {
                            $banco = NULL;
                            $PagAbr = 'EF';
                            if ($pago['Pag_Cod'] == 2) $PagAbr = 'TC';
                            if ($pago['Pag_Cod'] == 3) $PagAbr = 'CH';
                            if ($PagAbr != 'EF')
                                $banco = $obBD_con1->getRowConsulta(36, $pago['Com_Cod'], $obBD_conexion);
                            $saldo = $saldo - $pago['Pag_Val'];
                            $table['{body}'] = $table['{body}'] . '<tr>
                                <td style="font-weight:bold;border-right: ' . $b . ' solid #000;">&gt;</td>
                                <td>' . $pago['Com_Codigo'] . '</td>
                                <td style="text-align:center;">' . $pago['Pag_Fec'] . '</td>
                                <td>' . $PagAbr . '</td>
                                <td style="mso-number-format:&#39;@&#39;;">' . ($banco != NULL ? $banco['Che_Num'] : '') . '</td>
                                <td style="white-space:nowrap;overflow:hidden;">' . ($banco != NULL ? $banco['Banco'] : '') . '</td>
                                <td style="text-align:center;">' . ($banco != NULL ? $banco['Che_Fec'] : '') . '</td>
                                <td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;" colspan="2">' . number_format($pago['Pag_Val'], 2) . '</td>
                                <td></td>
                            </tr>';
                        }
                    }
                }
                if ($pagoRetencion == false) {
                    $Retenciones = $obBD_con1->getArrayConsulta(35, $Cpp_Data['Cop_Cod'], $obBD_conexion);
                    foreach ($Retenciones as $ret) {
                        $saldo = $saldo - round($ret['retencion'], 2);
                        if ($full) $table['{body}'] = $table['{body}'] . '<tr><td style="font-weight:bold;border-right: ' . $b . ' solid #000;">&gt;</td><td style="color:gray;">--------------</td><td style="text-align:center;">' . $ret['Ret_Fec'] . '</td><td>' . $ret['tipo'] . '</td><td colspan="2" style="mso-number-format:&#39;@&#39;;">' . $ret['Ret_Num'] . '</td><td></td><td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;" colspan="2">' . number_format($ret['retencion'], 2) . '</td><td></td></tr>';
                    }
                }

                if ($full) $table['{body}'] = $table['{body}'] . '<tr><td></td><td>&nbsp;</td><td>&nbsp;</td><td></td><td>&nbsp;</td><td colspan="4" style="text-align:right;border-top: ' . $b . ' solid #000;">SALDO DOCUMENTO:</td><td style="border-top: ' . $b . ' solid #000;text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldo, 2) . '</td></tr><tr><td colspan="9" style="height:20px;"></td></tr>';
                $saldoProvee = $saldoProvee + $saldo;
                $saldoAbono = $saldoFacturas - $saldoProvee;
            }
          

            if ($full) {
                $table['{body}'] = $table['{body}'] . '<tr>
                <td></td>
                <td></td>
                <td></td>   
                <td></td>
                <td></td>        
                    <td colspan="4" style="border-top: ' . $b . ' solid #000; border-bottom: 3px double #000; font-weight:bold;text-align:right;">TOTAL PROVEEDOR: ' . $provee['Proveedor'] . ' &nbsp;&gt;&gt;</td>
                <td style="border-bottom: 3px double #000;text-align:right;border-top: ' . $b . ' solid #000; mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoProvee, 2) . '</td>
                    </tr>
                    <tr>
                    <td colspan="9"></td>
                    </tr>';
            } else {
                $table['{body}'] = $table['{body}'] . '<tr>
                    <td colspan="7" style="border-bottom:1px solid #D5DADF;"><strong>>>>Cliente: </strong>' . $provee['Proveedor'] . '</td>
                    <td style="text-align:right; border-bottom:1px solid #D5DADF;">' . number_format($saldoFacturas, 2) . '</td>
                    <td style="text-align:right; border-bottom:1px solid #D5DADF;">' . number_format($saldoAbono, 2) . '</td>
                    <td style="text-align:right; border-bottom:1px solid #D5DADF;">' . number_format($saldoProvee, 2) . '</td>
                    </tr>
                    <tr>
                    <td colspan="9"></td>
                    </tr>';
            }
            $saldoGeneral = $saldoGeneral + $saldoProvee;
        }

        if ($full) {
            $table['{rowtotal}'] = $table['{rowtotal}'] . '<tr>
            <td colspan="8" style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;border-right:0;">TOTAL GENERAL:</td>       
                <td colspan="2" style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;border-left:0;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoGeneral, 2) . '</td>
            </tr>';
        } else {
            $table['{rowtotal}'] = $table['{rowtotal}'] . '<tr>
            <td colspan="7" style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;border-right:0;">TOTAL GENERAL:</td> 
            <td style="font-weight:bold;text-align:center; border-bottom: 3px double #000; border-top:1px solid #000;border-right:0;">' . number_format($saldoTotalFact, 2) . '</td> 
            <td style="font-weight:bold;text-align:center; border-bottom: 3px double #000; border-top:1px solid #000;border-left:0;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoTotalFact - $saldoGeneral, 2) . '</td>
            <td style="font-weight:bold;text-align:center; border-bottom: 3px double #000; border-top:1px solid #000;border-left:0;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoGeneral, 2) . '</td>
            </tr>';
        }
    } else {
        $full = false;
        foreach ($dataReport as $provee) {
            $saldoProvee = 0;
            $saldoFacturas = 0;
            $saldo_abono = 0;
            $table['{body}'] = $table['{body}'] . '<tr>
                <td style="font-weight:bold;border-top:10px" colspan="7">CLIENTE : ' . $provee['Proveedor'] . '</td></tr>
                <tr>
                <td colspan="7" style="border-left: 0px solid white; border-right:  0px solid white; border-top:0px solid white ;border-bottom: 0px solid #D5DADF; ' . $b . ' solid #000;"></td> 
                <td style="border-left: 0px solid white; border-right:  0px solid white; border-top:0px solid white ;border-bottom: 1px solid #000; ' . $b . ' solid #000;text-align:right;"><strong>Total</strong></td>
                <td style="border-left: 0px solid white; border-right:  0px solid white; border-top:0px solid white ;border-bottom: 1px solid #000; ' . $b . ' solid #000;text-align:right;"><strong>Abonos</strong></td>
                <td style="border-left: 0px solid white; border-right:  0px solid white; border-top:0px solid white ;border-bottom: 1px solid #000; ' . $b . ' solid #000;text-align:right;"><strong>Saldo</strong></td>
                </tr>';
           
            foreach ($provee['Cpps'] as $cuenta) {
                $Cpp_Data = $obBD_con1->getRowConsulta(34, $cuenta, $obBD_conexion);
                ChromePhp::log($Cpp_Data);
                if (empty($Cpp_Data)) $Cpp_Data = $obBD_con1->getRowConsulta(54, $cuenta, $obBD_conexion);
                $saldo = $Cpp_Data['total'] * 1;
                $saldoFacturas = $saldoFacturas + $Cpp_Data['total'] * 1;
                $saldoTotalFact = $saldoTotalFact + $Cpp_Data['total'] * 1;
                $pagoRetencion = false;
                if ($Cpp_Data['Cpc_Cod'] != NULL) {
                    $cancelaciones = $obBD_con1->getArrayConsulta(57, $Cpp_Data['Cpc_Cod'], $obBD_conexion);
                    foreach ($cancelaciones as $pago) {
                        $abrevi = $obBD_con1->getRowConsulta(53, $pago['Pag_Cod'], $obBD_conexion);
                        $banco = NULL;
                        $PagAbr = $abrevi['Pag_Abr'];
                        if ($PagAbr != 'RET') {
                            if ($PagAbr != 'EF')
                                $banco = $obBD_con1->getRowConsulta(36, $pago['Dcc_Cod'], $obBD_conexion);
                            $saldo = $saldo - $pago['Cpc_Val'];
                            $saldo_abono = $saldo_abono + $pago['Cpc_Val'];
                        }
                    }
                }
                if ($pagoRetencion == false) {
                    $Retenciones = $obBD_con1->getArrayConsulta(35, $Cpp_Data['Cop_Cod'], $obBD_conexion);
                    foreach ($Retenciones as $ret) {
                        $saldo = $saldo - round($ret['retencion'], 2);
                        $saldo_abono = $saldo_abono + number_format($ret['retencion'], 2);
                    }
                }
                $table['{body}'] = $table['{body}'] . '<tr><td style="font-weight:400;"  colspan="2">' . $Cpp_Data['Com_Codigo'] . '</td>  	    
                <td style="font-weight:400;" >' . $Cpp_Data['Com_Fec'] . '</td> 
                <td style="font-weight:400;" colspan="4">' . $Cpp_Data['Tic_Des'] . ': ' . $Cpp_Data['Cop_Num'] . ' -  ' .  $Cpp_Data['Cop_Obs'] . '</td>
                <td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($Cpp_Data['total'], 2) . '</td> 
                <td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;" colspan="">' . number_format($saldo_abono, 2) . '</td>
                <td style="solid #000;text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format(max($saldo, 0), 2) . '</td>
                </tr>';
                $saldoProvee = $saldoProvee + $saldo;
                $saldoAbono = $saldoFacturas - $saldoProvee;
                $saldo_abono = 0;
            }
            $table['{body}'] = $table['{body}'] . '
            <tr>
               <td style="text-align:right;"  colspan="7" ></td>
               <td style="text-align:right; border-bottom:1px solid #D5DADF;" colspan="3"></td>
            </tr>
            <tr>
                <td style="text-align:right; border-bottom:1px solid #D5DADF;font-weight:bold;"  colspan="7" ><b>TOTAL DE: ' . $provee['Cliente'] . '   </b> </td>
                <td style="text-align:right; border-bottom:1px solid #D5DADF;font-weight:bold;">' . number_format($saldoFacturas, 2) . '</td>
                <td style="text-align:right; border-bottom:1px solid #D5DADF;font-weight:bold;">' . number_format($saldoAbono, 2) . '</td>
                <td style="text-align:right; border-bottom:1px solid #D5DADF;font-weight:bold;">' . number_format(max($saldoProvee, 0), 2) . '</td>
                </tr>
               ';
            $saldoGeneral = $saldoGeneral + $saldoProvee;
        }
        $table['{rowtotal}'] = $table['{rowtotal}'] . '<tr>
            <td colspan="7" style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;border-right:0;">TOTAL GENERAL:</td> 
            <td style="font-weight:bold;text-align:center; border-bottom: 3px double #000; border-top:1px solid #000;border-right:0;">' . number_format($saldoTotalFact, 2) . '</td> 
            <td style="font-weight:bold;text-align:center; border-bottom: 3px double #000; border-top:1px solid #000;border-left:0;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoTotalFact - $saldoGeneral, 2) . '</td>
            <td style="font-weight:bold;text-align:center; border-bottom: 3px double #000; border-top:1px solid #000;border-left:0;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoGeneral, 2) . '</td>
            </tr>';
    }

    $table['{saldoFinal}'] = number_format($saldoGeneral, 2);
    $responce['html'] = reporteHtml($table, 'tes_pri_ccpp_pagos.html');
    $responce['success'] = true;

    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}
*/









if (isset($provAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(3, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0)
        $responce['rows'] =  $obBD_con1->getArrayConsulta(3, $data, $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}
if (isset($provAjax)) {
    $contar = $obBD_con1->getRowConsulta(1, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*', $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(1, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*' . $pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}
if (isset($ajaxComprobante)) {
    if (!empty($Pec_Cod)) {
        $periodo = $obBD_con1->getRowConsulta(59, $Pec_Cod, $obBD_conexion);
        $txt_fec_ini = '0001-01-01';
        $txt_fec_fin = $periodo['Pec_Fef'];
        $Pec_Cod = '';
    }
    $responce['rows'] = $obBD_con1->getArrayConsulta(58, $Ses_Emp_Cod . '*' . $Prv_Cod . '*' . $Pec_Cod . '*' . $txt_fec_ini . '*' . $txt_fec_fin . '*', $obBD_conexion);
    if ($op_opciones != 'T')
        foreach ($responce['rows'] as $key => $item) {
            if ($item['Abono'] != $item['Asi_Val'] && $op_opciones == 'P') unset($responce['rows'][$key]);
            if ($item['Abono'] == $item['Asi_Val'] && $op_opciones == 'I') unset($responce['rows'][$key]);
        }
    $responce['rows'] = array_values($responce['rows']);
    $responce['success'] = true;
    $responce['records'] = count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}
if (isset($ajaxSubgrid)) {
    if (!empty($Pec_Cod)) {
        $periodo = $obBD_con1->getRowConsulta(59, $Pec_Cod, $obBD_conexion);
        $txt_fec_ini = '0001-01-01';
        $txt_fec_fin = $periodo['Pec_Fef'];
        $Pec_Cod = '';
    }
    $responce['rows'] = $obBD_con1->getArrayConsulta(57, $ajaxSubgrid . '*' . $Pec_Cod . '*' . $txt_fec_ini . '*' . $txt_fec_fin, $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    $responce['records'] = count($responce['rows']);
    echo json_encode($responce);
    exit();
}
if (isset($detAjax)) {
    $responce['success'] = false;
    $responce['com'] = $obBD_con1->getRowConsulta(9, $Com, $obBD_conexion);
    $responce['pag'] = $obBD_con1->getRowConsulta(30, $Cpp . '*' . $Com, $obBD_conexion);
    $responce['asi']['rows'] = $obBD_con1->getArrayConsulta(10, $Com, $obBD_conexion);
    $responce['asi']['records'] = count($responce['asi']['rows']);
    $responce['che']['rows'] = $obBD_con1->getArrayConsulta(11, $Com, $obBD_conexion);
    $responce['che']['records'] = count($responce['che']['rows']);
    utf8_encode_deep($responce);
    $responce['success'] = true;
    echo json_encode($responce);
    exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Ccxpp Saldo al Corte [EXA] "; ?></TITLE>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid.php") ?>
    <style>

    </style>
</HEAD>

<BODY>
    <div id="set1">
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table" style="table-layout:fixed;">
            <tr class="BarraTitulo">
                <td colspan="2" height="10">&raquo; Historial De Deudas al Corte </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div id="comp1">
                        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
                            <tr>
                                <td>
                                    <FIELDSET>
                                        <LEGEND>
                                            <label class="Titulos2">Seleccionar Proveedor</label>
                                        </LEGEND>
                                        <form id="provFormTemp">
                                            <div class="segmento">Cédula/R.U.C.:</div>
                                            <div class="datasegmento">

                                                <input id="docu" name="search" maxlength="13" onkeydown='if (event.keyCode === 13) $.SearchOrDialog("#provDialog",selectProvee);' type="text" class="search ui-corner-all" placeholder="Ingrese Cedula/R.U.C. ..." title="Buscar Proveedor Por Documento o Descripción" autofocus />
                                                <input type="text" name="op_opciones" value="c" style="display: none;" />
                                                <input id="PrvCodBus" type="hidden" name="Prv_Cod" value="" />
                                                <a onclick="$('#provDialog').dialog('open');/*$('#docu').removeAttr('readOnly');*/" title="Búsqueda de Proveedores" class="btn btn-success btn-mini"><i class=" icon-check icon-white"></i></a>
                                                <a onclick="selectProvee();" title="Quitar Proveedor" class="btn btn-success btn-mini"><i class=" icon-eject icon-white"></i></a>

                                            </div>
                                        </form>
                                        <div class="segmento">Proveedor:</div>
                                        <div class="datasegmento"><input id="lblProv" type="text" class="label ui-widget-content ui-corner-all" readonly /></div><br />
                                        <div class="segmento">Dirección:</div>
                                        <div class="datasegmento"><input id="lblDirec" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                                    </FIELDSET>
                                </td>
                                <td>
                                    <FIELDSET>
                                        <LEGEND>
                                            <label class="Titulos2">Filtros</label>
                                        </LEGEND>
                                        <form id="formCompTemp" action="javascript:$('#list').Search('#formCompTemp','ajaxComprobante');setCaption();">
                                            <input type="hidden" name="Prv_Cod" value="" />
                                            <div>
                                                <div class="segmento">Todos: <input name="op_opciones" type="radio" value="T" checked alt="" /></div>
                                                <div class="segmento">Pagados: <input name="op_opciones" type="radio" value="P" alt="" /></div>
                                                <div class="segmento">Por Pagar: <input name="op_opciones" type="radio" value="I" alt="" /></div>
                                            </div>
                                            <div>
                                                <div class="segmento">
                                                    Desde:
                                                </div>
                                                <div id="rangeDates" class="datasegmento">
                                                    <input name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="focus ui-corner-all" style="text-align: center;" />&nbsp;&nbsp;&nbsp;&nbsp;
                                                    Hasta:<input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="focus ui-corner-all" style="text-align: center;" />
                                                    <!-- <div class="segmento"></div></div> -->
                                                    <button type="button" class="btn btn-info" title="Solo se esta filtrando por fecha de emision de documentos">
                                                        <i class="icon-info-sign icon-white"></i>
                                                    </button>
                                                </div>
                                            </div>
                    </div>
                    <div>
                        <div class="segmento">Periodo:</div>
                        <div class="datasegmento">
                            <select name="Pec_Cod" id="Pec_Cod" onchange="
                                                                                    if($('#Pec_Cod').val()!==''){
                                                                                        $('#rangeDates').addClass('disabled').find('input').attr('disabled','disabled');
                                                                                        $('#rangeDates').addClass('disabled').find('input').attr('disabled','disabled');
                                                                                    }else{
                                                                                        $('#rangeDates').removeClass('disabled').find('input').removeAttr('disabled');
                                                                                    }" class="ui-corner-all">
                                <?Php
                                $rs_periodos = $obBD_con1->consulta(sentencias_ccpp(5, array(0 => $Ses_Emp_Cod)), $obBD_conexion->conexion);
                                $row_rs_periodos = $obBD_con1->registros();
                                $total_rs_periodos = $obBD_con1->numregistros();
                                if ($total_rs_periodos > 0) {
                                    do {
                                ?>
                                        <option value="<?Php echo $row_rs_periodos['Pec_Cod']; ?>"><?Php echo $row_rs_periodos['Periodo']; ?></option>
                                <?php
                                    } while ($row_rs_periodos = $obBD_con1->fetch_assoc($rs_periodos));
                                } //Fin del if ($total_rs_periodo > 0)                                
                                ?>
                                <option value="">Por Fecha</option>
                            </select>&nbsp;&nbsp;&nbsp;&nbsp;
                            <button type="button" onclick="this.form.submit()" class="btn btn-success" style="height: 27px;" title="Ejecutar Búsqueda">
                                <i class="icon-search icon-white"></i>
                                <span>Buscar</span>
                            </button>
                        </div>

                    </div>
                    </form>
                    </FIELDSET>
                </td>
            </tr>
            <tr>
                <td colspan="2" height="389" align="left" valign="top">
                    <FIELDSET>
                        <LEGEND>
                            <label class="Titulos2">Resultados de la busqueda</label>
                        </LEGEND>
                        <div id="grillaComp">
                            <table id="list"></table>
                            <div id="listPager"></div>
                        </div>
                        <div style="padding:15px;" hidden>
                            <button onclick="exportar(true)" title="Imprimir Reporte" type="button" class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></button>
                            <button onclick="exportar(false)" class="btn btn-primary start" title="Descargar archivo de Excel"> <i class="icon-share icon-white"></i> <span>Excel</span></button>
                        </div>






                        <style>
                            .radio-container {
                                padding: 0px 15px 15px 15px;
                                /* Ajusta el padding para eliminar el espacio superior */
                                width: 95%;
                                margin: 0 auto;
                                margin-top: 10px;
                                font-family: Arial, sans-serif;
                                font-size: 14px;
                                color: #333333;
                            }

                            .radio-options {
                                display: flex;
                                justify-content: center;
                                gap: 15px;
                                /* Ajusta este valor para cambiar la separación entre las opciones */
                                margin-bottom: 10px;
                            }

                            .radio-option {
                                display: flex;
                                align-items: center;
                                margin: 0;
                                /* Elimina cualquier margen superior */
                            }

                            .radio-option input {
                                margin-right: 5px;
                            }

                            .buttons {
                                text-align: center;
                            }

                            .btn {
                                background-color: #007bff;
                                color: #ffffff;
                                border: none;
                                padding: 5px 10px;
                                margin: 5px;
                                border-radius: 3px;
                                cursor: pointer;
                                font-size: 14px;
                            }

                            .btn:hover {
                                background-color: #0056b3;
                            }

                            .btn-print {
                                background-color: #28a745;
                            }

                            .btn-print:hover {
                                background-color: #218838;
                            }

                            .btn-excel {
                                background-color: #17a2b8;
                            }

                            .btn-excel:hover {
                                background-color: #117a8b;
                            }
                        </style>


                        <!--div class="radio-container" style="padding:5px;">
                            <div class="col-sm-6">
                                <div class="radio-options">
                                    <div class="radio-option">
                                        <input type="radio" name="resumido" id="general" value="G" checked>
                                        <label for="general">Reporte General</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" name="resumido" id="resumido" value="S">
                                        <label for="resumido">Resumido por Clientes</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" name="resumido" id="factura" value="T">
                                        <label for="factura">Resumido por Factura</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 buttons">
                                <button onclick="exportar(true)" title="Imprimir Reporte" type="button" class="btn btn-primary start">
                                    <i class="icon-print icon-white"></i> <span>Imprimir</span>
                                </button>
                                <button onclick="exportar(false)" class="btn btn-primary start" title="Descargar archivo de Excel">
                                    <i class="icon-share icon-white"></i> <span>Excel</span>
                                </button>
                            </div>
                        </div-->


                        <div style="padding:15px;">
                            <button onclick="exportar(true)" title="Imprimir Reporte" type="button" class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></button>
                            <button onclick="exportar(false)" class="btn btn-primary start" title="Descargar archivo de Excel"> <i class="icon-share icon-white"></i> <span>Excel</span></button>
                            <!--<button type="button" class="btn btn-primary start" onclick="exportarExcel('Exportar')"> <i class="icon-share icon-white"></i> <span>Excel</span></button>-->
                        </div>





                        <script type="text/javascript">
                            var tipo = 'lista';

                            function buscaCedula() {
                                var array = {
                                    'search': $('#docu2').val(),
                                    'op_opciones': 'C'
                                };
                                $.SearchOrDialogArray("#provDialog", selectProvee, array);
                            }

                            function selectProvee(data) {
                                if (typeof data === 'undefined') {
                                    $("#lblProv").val('');
                                    $("#lblDirec").val('');
                                    $("input[name='Prv_Cod']").val('');
                                    $("#docu").val('');
                                    $('#PrvCodBus').val('');
                                    $('#list').Search('#formCompTemp', 'ajaxComprobante');
                                } else {
                                    if (tipo === 'lista') {
                                        $("#lblProv").val(data['proveedor']);
                                        $("#lblDirec").val(data['Prs_Dir']);
                                        $("input[name='Prv_Cod']").val(data['Prv_Cod']);
                                        $("#docu").val(data['Prs_Ced']);
                                        $("#provDialog").dialog("close");
                                        $('#list').Search('#formCompTemp', 'ajaxComprobante');
                                        //$("#docu").attr("readOnly","readOnly");
                                    }
                                    if (tipo === 'pago') {
                                        $("#lblProvee2").val(data['proveedor']);
                                        $("#cod_pvr").val(data['Prv_Cod']);
                                        $("#provDialog").dialog("close");
                                    }
                                }
                                setCaption();
                            }

                            function setCaption() {
                                var caption = '';
                                caption = "Historial de Cr&eacute;ditos Recibidos - ";
                                if ($('#Pec_Cod').val() === '') caption = caption + ' Desde ' + $('#txt_fec_ini').val() + ' Hasta ' + $('#txt_fec_fin').val();
                                else caption = caption + ' Periodo ' + $('#Pec_Cod').find('option:selected').text();
                                if ($('#PrvCodBus').val() !== '') caption = caption + ' - ' + $('#lblProv').val();
                                $('#list').jqGrid('setCaption', caption);
                            }

                            function clearFooter() {
                                var $footRow = $("#grillaComp .ui-jqgrid-sdiv .footrow");
                                $footRow.find('>td[aria-describedby="list_subgrid"]').css("border-right-color", "transparent");
                                $footRow.find('>td[aria-describedby="list_Com_Codigo"]').css("border-right-color", "transparent");
                                $footRow.find('>td[aria-describedby="list_Cop_Fec"]').css("border-right-color", "transparent");
                                $footRow.find('>td[aria-describedby="list_Cpp_Ven"]').css("border-right-color", "transparent");
                                $footRow.find('>td[aria-describedby="list_Cop_Num"]').css("border-right-color", "transparent");
                                $footRow.find('>td[aria-describedby="list_subgrid"]').css("background-color", "white");
                                $footRow.find('>td[aria-describedby="list_Com_Codigo"]').css("background-color", "white");
                                $footRow.find('>td[aria-describedby="list_vencimiento"]').css("background-color", "white");
                                $footRow.find('>td[aria-describedby="list_Cop_Fec"]').css("background-color", "white");
                                $footRow.find('>td[aria-describedby="list_Cpp_Ven"]').css("background-color", "white");
                                $footRow.find('>td[aria-describedby="list_Cop_Num"]').css("background-color", "white");
                                $footRow.find('>td[aria-describedby="list_proveedor"]').css("background-color", "white");
                            }

                            function updateSaldos(grid) {
                                var rows = grid.jqGrid('getRowData');
                                for (var i = 0; i < rows.length; i++) {
                                    if (rows[i].act === "Yes") {
                                        grid.jqGrid("setCell", rows[i].Cpp_Cod, "Pago", rows[i].Saldo); /*console.log(rows[i].Saldo);*/
                                    } else {
                                        grid.jqGrid("setCell", rows[i].Cpp_Cod, "Pago", "0.00");
                                    }
                                }
                            }

                            function updateTotals(grid) {
                                var abonos = 0,
                                    saldos = 0,
                                    rows = grid.jqGrid('getRowData'); //alert( grid.jqGrid('getCol', 'Com_Val', false, 'sum'));
                                for (var i = 0; i < rows.length; i++) {
                                    abonos = abonos + parseFloat(rows[i]['Abono']);
                                    saldos = saldos + (!isNaN(rows[i]['Saldo']) ? parseFloat(rows[i]['Saldo']) : 0);
                                }
                                grid.jqGrid('footerData', 'set', {
                                    vencimiento: "<div style='text-align:right;'>TOTALES:</div>",
                                    Asi_Val: grid.jqGrid('getCol', 'Asi_Val', false, 'sum')
                                });
                                grid.jqGrid('footerData', 'set', {
                                    Abono: "" + abonos
                                });
                                grid.jqGrid('footerData', 'set', {
                                    Saldo: "" + saldos
                                });
                            }
                            $(document).ready(function() {
                                $.createDateRange('#txt_fec_ini', '#txt_fec_fin');
                                var compGrid = $("#list");
                                compGrid.jqGrid({
                                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                    mtype: "GET",
                                    datatype: "local",
                                    regional: 'es', //ajaxRowOptions: { async: true },
                                    //postData: $("#form1").getData("ajaxGrid"),
                                    autowidth: true,
                                    shrinkToFit: true,
                                    height: 270,
                                    caption: ' ',
                                    hidegrid: false,
                                    cmTemplate: {
                                        sortable: false
                                    },
                                    colModel: [{
                                            label: 'Cód.Int.',
                                            name: 'Cpp_Cod',
                                            key: true,
                                            width: 15,
                                            align: "center",
                                            hidden: true
                                        },
                                        {
                                            label: 'Prv_Cod',
                                            name: 'Prv_Cod',
                                            width: 15,
                                            align: "center",
                                            hidden: true
                                        },
                                        {
                                            label: 'Cód.Int.',
                                            name: 'Asi_Cod',
                                            width: 15,
                                            align: "center",
                                            hidden: true
                                        },
                                        {
                                            label: 'No. Compr.',
                                            name: 'Com_Codigo',
                                            align: "center",
                                            width: 40
                                        },
                                        {
                                            label: 'Fecha Emis.',
                                            name: 'Cop_Fec',
                                            align: "center",
                                            width: 35
                                        },
                                        {
                                            label: 'Fecha Venc.',
                                            name: 'Cpp_Ven',
                                            align: "center",
                                            width: 35
                                        },
                                        {
                                            label: 'Vencimiento',
                                            name: 'vencimiento',
                                            align: "center",
                                            width: 35
                                        },
                                        {
                                            label: 'Total',
                                            name: 'Asi_Val',
                                            width: 45,
                                            align: 'right',
                                            formatter: 'currency',
                                            decimalPlaces: '2',
                                            summaryRound: 2,
                                            formatoptions: {
                                                prefix: '$ ',
                                                thousandsSeparator: ',',
                                                decimalSeparator: '.'
                                            },
                                            summaryTpl: "Total: {0}",
                                            summaryType: "sum"
                                        },
                                        {
                                            label: 'Abono',
                                            name: 'Abono',
                                            width: 45,
                                            align: 'right',
                                            decimalPlaces: '2',
                                            summaryRound: 2,
                                            formatoptions: {
                                                prefix: '$ ',
                                                thousandsSeparator: ',',
                                                decimalSeparator: '.'
                                            },
                                            formatter: function(cellValue, options, rowObject) {
                                                if (!parseFloat(rowObject.Abono)) rowObject.Abono = 0;
                                                return $.fn.fmatter.call(this, "currency", rowObject.Abono, options);
                                            },
                                            unformat: function(cellValue, options, cell) {
                                                var opt = $.extend(true, {}, options);
                                                opt.colModel.formatter = "currency";
                                                delete opt.colModel.unformat;
                                                return $.unformat.call(this, cell, opt);
                                            },
                                            summaryTpl: "Total: {0}",
                                            summaryType: "sum" // set the formula to calculate the summary type 
                                        },
                                        {
                                            label: 'Saldo',
                                            name: 'Saldo',
                                            width: 45,
                                            align: 'right',
                                            decimalPlaces: '2',
                                            summaryRound: 2,
                                            formatoptions: {
                                                prefix: '$ ',
                                                thousandsSeparator: ',',
                                                decimalSeparator: '.'
                                            },
                                            formatter: function(cellValue, options, rowObject) {
                                                if (!parseFloat(rowObject.Saldo)) {
                                                    rowObject.Saldo = parseFloat(rowObject.Asi_Val) - parseFloat(rowObject.Abono);
                                                }
                                                if (parseFloat(rowObject.Abono) === parseFloat(rowObject.Asi_Val)) return 'Pagado';
                                                else
                                                    return $.fn.fmatter.call(this, "currency", rowObject.Saldo, options);
                                            },
                                            unformat: function(cellValue, options, cell) {
                                                var opt = $.extend(true, {}, options);
                                                opt.colModel.formatter = "currency";
                                                delete opt.colModel.unformat;
                                                return $.unformat.call(this, cell, opt);
                                            },
                                            summaryTpl: "Total: {0}",
                                            summaryType: "sum" // set the formula to calculate the summary type 
                                        },
                                        {
                                            label: 'No. Docum.',
                                            name: 'Cop_Num',
                                            width: 60,
                                            align: "center"
                                        },
                                        {
                                            label: 'Proveedor',
                                            name: 'proveedor',
                                            width: 80
                                        },
                                        {
                                            label: '&nbsp;',
                                            name: 'act1',
                                            width: 30,
                                            align: 'center',
                                            viewable: false,
                                            title: false,
                                            formatter: function(cellvalue, options, rowObject) {
                                                return '<span class="btn btn-info btn-mini" title="Ver" type="button" onclick="$(\'#list\').viewGridRow(\'' + rowObject.Cpp_Cod + '\');"><i class="icon-info-sign icon-white"></i></span><span>&nbsp;&nbsp;</span>';
                                            }
                                        }

                                    ],
                                    rowNum: 10000000,
                                    pager: "#listPager",
                                    gridview: true,
                                    rownumbers: true,
                                    viewrecords: true,
                                    pgbuttons: false,
                                    pgtext: null,
                                    footerrow: true,
                                    userDataOnFooter: false,
                                    onSelectRow: function(rowid, e) {
                                        compGrid.resetSelection();
                                    },
                                    loadComplete: function(data) {
                                        var total = data.records;
                                        for (var i = 0; i < total; i++) {
                                            if (data.rows[i]['vencimiento'] === 'Vencido')
                                                $("#" + data.rows[i].Cpp_Cod).css("background", "#FADDDD");
                                            if (data.rows[i]['vencimiento'] === 'Pagado')
                                                $("#" + data.rows[i].Cpp_Cod).css("background", "#DDFAE2");
                                        }
                                        updateTotals($(this));
                                    },
                                    subGridOptions: {
                                        "plusicon": "ui-icon-triangle-1-e",
                                        "minusicon": "ui-icon-triangle-1-s",
                                        "openicon": "ui-icon-arrowreturn-1-e",
                                        "reloadOnExpand": false,
                                        "selectOnExpand": true
                                    },
                                    subGrid: true,
                                    multiselect: false,
                                    subGridRowExpanded: function(subgrid_id, row_id) {
                                        var subgrid_table_id = subgrid_id + "_t";
                                        $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
                                        $("#" + subgrid_table_id).jqGrid({
                                            url: "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?ajaxSubgrid=" + row_id + '&Pec_Cod=' + $('#Pec_Cod').val() + '&txt_fec_ini=' + $('#txt_fec_ini').val() + '&txt_fec_fin=' + $('#txt_fec_fin').val(),
                                            datatype: "json",
                                            regional: 'es',
                                            autowidth: true,
                                            shrinkToFit: true,
                                            cmTemplate: {
                                                sortable: false
                                            }, //colNames: ['No','Item','Qty','Unit','Line Total'],
                                            colModel: [{
                                                    label: 'Cod.Int.',
                                                    name: "Cpp_Cod",
                                                    width: 80,
                                                    key: true,
                                                    align: "center",
                                                    hidden: true
                                                },
                                                {
                                                    label: 'Cod.Int.',
                                                    name: "Com_Cod",
                                                    width: 80,
                                                    key: true,
                                                    align: "center",
                                                    hidden: true
                                                },
                                                {
                                                    label: 'No. Compr.',
                                                    name: "Com_Codigo",
                                                    width: 45,
                                                    align: "center"
                                                },
                                                {
                                                    label: 'Fecha',
                                                    name: "Pag_Fec",
                                                    width: 45,
                                                    align: "center"
                                                },
                                                {
                                                    label: 'Valor',
                                                    name: "Pag_Val",
                                                    width: 45,
                                                    align: 'right',
                                                    formatter: 'currency',
                                                    decimalPlaces: '2',
                                                    formatoptions: {
                                                        prefix: '$ ',
                                                        thousandsSeparator: ',',
                                                        decimalSeparator: '.'
                                                    }
                                                },
                                                {
                                                    label: 'Observación',
                                                    name: "Pag_Obs",
                                                    width: 100
                                                },
                                                {
                                                    label: 'Tipo',
                                                    name: "Pag_Des",
                                                    width: 45,
                                                    align: "center"
                                                },
                                                {
                                                    label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                                                    name: 'act1',
                                                    width: 18,
                                                    align: 'center',
                                                    viewable: false,
                                                    formatter: function(cellvalue, options, rowObject) {
                                                        var clic = 'selectDetalle(' + rowObject.Cpp_Cod + ',' + rowObject.Com_Cod + ');';
                                                        return '<span class="btn btn-info btn-mini" title="Seleccionar" onclick=\'' + clic + '\'><i class="icon-info-sign icon-white"></span>';
                                                    }
                                                }
                                            ],
                                            beforeSelectRow: function(rowid, e) {
                                                return false;
                                            },
                                            rowNum: 10000000,
                                            pager: "",
                                            height: '100%'
                                        });
                                    }
                                });
                                compGrid.navGrid('#listPager', {
                                    edit: false,
                                    add: false,
                                    del: false,
                                    search: false,
                                    refresh: true,
                                    view: false,
                                    position: "left",
                                    cloneToTop: false
                                });
                                //                            .jqGrid('navButtonAdd',"#listPager",{ caption: "Exportar &nbsp;",buttonicon: "ui-icon-arrowthickstop-1-s",title:"Exportar Excel",
                                //                                onClickButton: function() {
                                //                                    compGrid.jqGrid('exportGridExcel',{nombre:"CreditosRecibidos",hoja:"CCPP"});	
                                //                                },position: "last"
                                //                            });
                                compGrid.jqGrid('bindKeys');
                                clearFooter();
                                $('#rangeDates').addClass('disabled').find('input').attr('disabled', 'disabled');
                            });
                        </script>
                    </FIELDSET>
                </td>
            </tr>
        </table>
    </div>

    </td>
    </tr>
    </table>
    </div>

    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
    <div id="provDialog" title="Búsqueda de Proveedores">
        <form>
            <fieldset>
                <legend><label class="Titulos2">Búsqueda de Proveedor</label></legend>
                <table border="0">
                    <tr>
                        <td width="205"><input name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" />
                            <span class="LetraNegra"><strong>Apellido</strong></span>
                        </td>
                        <td width="266"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
                            <span class="LetraNegra"><strong>Cédula/R.U.C.</strong></span>
                        </td>
                    </tr>
                </table>
                <table height="36" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;">
                            <div align="right"><strong>B&uacute;squeda</strong></div>
                        </td>
                        <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese proveedor a buscar..." autofocus /><input type="text" style="display:none" /></td>
                        <td width="109" align="center">
                            <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar cuenta">
                                <i class="icon-search icon-white"></i>
                                <span>Buscar</span>
                            </button>
                        </td>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            $.createSearchDialog('#provDialog', [{
                    label: 'Cód.Int.',
                    name: 'Prv_Cod',
                    key: true,
                    hidden: true,
                    viewable: true
                },
                {
                    label: 'Cédula/R.U.C.',
                    name: 'Prs_Ced',
                    width: 50
                },
                {
                    label: 'Proveedor',
                    name: 'proveedor',
                    width: 190,
                    cellattr: function(rowId, tv, rawObject, cm, rdata) {
                        return 'style="white-space: normal;"';
                    }
                },
                {
                    label: 'Dirección',
                    name: 'Prs_Dir',
                    hidden: true,
                    viewable: true
                },
                {
                    label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                    name: 'act1',
                    width: 18,
                    align: 'center',
                    viewable: false,
                    formatter: function(cellvalue, options, rowObject) {
                        var clic = 'selectProvee($("#provGrid").jqGrid("getRowData",' + rowObject.Prv_Cod + '))';
                        return '<span class="btn btn-success btn-mini" title="Seleccionar" onclick=\'' + clic + '\'><i class="icon-arrow-right icon-white"></span>';
                    }
                }
            ]);

        });
    </script>
    <!-- FIN DEL DIALOGO PROVEEDOR-->
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jqgrid.ExcelExport.js"></script>
    <!--INICIO DEL DIALOGO DETALLE PAGO -->
    <div id="pagoDialog" title="Detalle Pago">
        <div>
            <div style="width: 50%;display: inline;float:left;">
                <fieldset>
                    <legend><label class="Titulos2">Datos Comprobante</label></legend>
                    <div class="row">
                        <div class="segmento">Compr. No.:</div>
                        <div class="datasegmento"><input id="lblComp2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                    </div>
                    <div class="row">
                        <div class="segmento">Fecha:</div>
                        <div class="datasegmento"><input id="lblComFe2" type="text" class="label medium ui-widget-content ui-corner-all" style="text-align: center;" readonly /></div>
                    </div>
                    <div class="row">
                        <div class="segmento">Valor:</div>
                        <div class="datasegmento"><input id="lblComVal2" type="text" class="text medium ui-widget-content ui-corner-all" style="text-align: right;" readonly /></div>
                    </div>
                </fieldset>
            </div>
            <div style="width: 50%;display: inline;float:right;">
                <fieldset>
                    <legend><label class="Titulos2">Datos del Proveedor</label></legend>
                    <div class="row">
                        <div class="segmento">Cédula:</div>
                        <div class="datasegmento"><input id="lblCed2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                    </div>
                    <div class="row">
                        <div class="segmento">Proveedor:</div>
                        <div class="datasegmento"><input id="lblProv2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                    </div>
                    <div class="row">
                        <div class="segmento">Dirección:</div>
                        <div class="datasegmento"><input id="lblDirec2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                    </div>
                </fieldset>
            </div>
            <div class="row" style="padding-top: 5px;padding-bottom: 15px;">
                <fieldset>
                    <legend><label class="Titulos2">Observación</label></legend>
                    <div class="datasegmento" style="width:95%;"><input id="lblConce2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                </fieldset>
            </div>
        </div>
        <div id="tabs">
            <ul>
                <li><a href="#detalleComp">Detalle Pago</a></li>
                <li><a href="#asienComp">Asiento Contable</a></li>
                <li><a href="#chequeComp">Cheques</a></li>
            </ul>
            <div id="asienComp" class="condensed">
                <table id="asiento"></table>
                <div id="asientoPager"></div>
            </div>
            <div id="chequeComp" class="condensed">
                <table id="cheque"></table>
                <div id="chequePager"></div>
            </div>
            <div id="detalleComp" style="clear: both;">
                <div class="row">
                    <div class="segmento">Factura:</div>
                    <div class="datasegmento"><input id="lblFac2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="row">
                    <div class="segmento">Vencimiento:</div>
                    <div class="datasegmento"><input id="lblVen2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="row">
                    <div class="segmento">Observación:</div>
                    <div class="datasegmento"><input id="lblObsV2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="row">
                    <div class="segmento">Tipo Pago:</div>
                    <div class="datasegmento"><input id="lblTipPa2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="row">
                    <div class="segmento">Fecha Pago:</div>
                    <div class="datasegmento"><input id="lblFePa2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="row">
                    <div class="segmento">Valor Pago:</div>
                    <div class="datasegmento"><input id="lblVaPa2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>



    <script type="text/javascript">
        $(document).ready(function() {
                $.createDialog('#pagoDialog',415,650); 
                $( "#tabs" ).tabs();
                $.createDatePickers("input[name='Com_Fec']");
                $.createDatePickers("input[name='Che_Fec']");
                $('#asiento').jqGrid({
                    datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },                             
                    width:618,height:75,postData: {CheListAjax:true},caption:'Asiento Contable',
                    cmTemplate: {sortable:false},colModel: [
                        { label: 'Cód.Int.', name: 'Asi_Cod', key: true, width: 15,align:"center", hidden:true },  
                                { label: 'Tipo', name: 'Asi_Deh', hidden:true },
                                { label: 'Código', name: 'Pld_Cdc', width: 45 },                      
                                { label: 'Cuenta', name: 'Pld_Des', width: 130  },
                                { label: 'Glosa', name: 'Glosa', width: 130},
                                { label: 'Debe', name: 'Debe', width: 65, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"},                                         
                                { label: 'Haber',name: 'Haber',width: 65,align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"}
                    ],
                    loadComplete: function (data) { $(this).jqGrid('footerData', 'set', { Glosa:"<div style='text-align:right;'>TOTALES:</div>",Debe: $(this).jqGrid('getCol', 'Debe', true, 'sum'),Haber: $(this).jqGrid('getCol', 'Haber', true, 'sum') },true); },
                    rowNum: 10000, gridview: true, viewrecords: true,footerrow: true, userDataOnFooter: false
                }); $.clearFooterDiario("#asiento");
                 $('#cheque').jqGrid({
                    datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },                             
                    width:618,height:97,postData: {CheListAjax:true},caption:'Cheques Girados',
                    cmTemplate: {sortable:false},colModel: [
                        { label: 'Cód.Int.', name: 'Che_Cod', key: true,hidden:true,viewable: true },                                
                        { label: 'Fecha', name: 'Che_Fec', key: true, width: 50 },
                        { label: 'Num.', name: 'Che_Num', key: true, width: 30,align:"center" },                        
                        { label: 'Banco', name: 'Pld_Des', width: 100,title:'Cuenta Bancaria' },
                        { label: 'Beneficiario', name: 'Beneficiario', width: 150 },
                        { label: 'Valor', name: 'Che_Val', key: true, width: 60,align:"right" , formatter:'currency', decimalPlaces: '2', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}}
                    ],                                                                                       
                    rowNum: 10000, gridview: true, viewrecords: true
                });   
                 $.createDialog('#successDialog',150,550);                 
        }); 
        function selectDetalle(Cpp,Com){                             
                           
                                $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{detAjax:true,Cpp:Cpp,Com:Com}, function( response ) {
                                   if(response['success']===true){                                       
                                        $("#lblComp2").val(response['com']['Com_Num']);
                                        $("#lblComFe2").val(response['com']['Com_Fec']);
                                        $("#lblComVal2").val(response['com']['Com_Val']);
                                        $("#lblConce2").val(response['com']['Com_Obs']);
                                        $("#lblCed2").val(response['com']['Prs_Ced']);
                                        $("#lblProv2").val(response['com']['Prs_Ape']+' '+response['com']['Prs_Nom']);
                                        $("#lblProv2").attr('title',response['com']['Prs_Ape']+' '+response['com']['Prs_Nom']);
                                        $("#lblDirec2").val(response['com']['Prs_Dir']);
                                        $("#lblDirec2").attr('title',response['com']['Prs_Dir']);
                                        
                                        $("#lblFac2").val(response['pag']['Cop_Num']);
                                        $("#lblVen2").val(response['pag']['Cpp_Ven']);
                                        $("#lblObsV2").val(response['pag']['Pag_Obs']);
                                        $("#lblTipPa2").val(response['pag']['Pag_Des']);
                                        $("#lblFePa2").val(response['pag']['Pag_Fec']);
                                        $("#lblVaPa2").val('$ '+response['pag']['Pag_Val']);                                        
                                       
                                        $("#asiento").jqGrid("clearGridData");                                    
                                        $("#asiento").jqGrid('setGridParam',{rowNum:response['asi']['records']});
                                        $("#asiento").jqGrid('setGridParam', {data:response['asi']['rows'],page:1,records:response['asi']['records'] }).trigger('reloadGrid');
                                        $("#cheque").jqGrid("clearGridData");                                    
                                        $("#cheque").jqGrid('setGridParam',{rowNum:response['che']['records']});
                                        $("#cheque").jqGrid('setGridParam', {data:response['che']['rows'],page:1,records:response['che']['records'] }).trigger('reloadGrid');
                                        
                                        $('#pagoDialog').dialog('open');
                                   }else{$.alert(response['message']);}                                   
                                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
                           
                        }
           function exportar(banTipo){
               var batch = new Array();               
                        var grid = $("#list");
                        var ids = grid.jqGrid('getDataIDs');
                        for (var i = 0; i < ids.length; i++) {
                            var datos = grid.jqGrid('getRowData', ids[i]),ban=true;
                            for (var j = 0;j < batch.length;j++) {
                                if(datos['Prv_Cod']===batch[j]['Prv_Cod']){
                                    ban=false;
                                }
                            }
                            if(ban) batch.push({Prv_Cod:datos['Prv_Cod'],Proveedor:datos['proveedor']});
                        }
                        for (var i = 0; i < batch.length; i++) {
                            batch[i]['Cpps']=new Array();
                            for (var j = 0; j < ids.length; j++){
                                var datos = grid.jqGrid('getRowData', ids[j]);
                                if(datos['Prv_Cod']===batch[i]['Prv_Cod']){
                                    batch[i]['Cpps'].push(datos['Cpp_Cod']);
                                }
                            }
                        }
               //  console.log(batch);       
               if(batch.length>0){ 
                    $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{dataReport:batch,tipo:banTipo,caption:grid.parent().parent().parent().find('.ui-jqgrid-title').text(), Pec_Cod:$('#Pec_Cod').val(), txt_fec_ini:$('#txt_fec_ini').val(), txt_fec_fin:$('#txt_fec_fin').val()}, function( response ) {
                        if(response['success']===true){
                            if(banTipo){
                                 $('#Exportar').html(response['html']);
                                $('#Exportar').printElement({pageTitle:'<?Php echo $Ses_Sys_Nom; ?>'});
                            }
                            else
                                $.downloadFile($.exportarExcelBlob(response['html'],'CCPP'),'CtaPorPagar-'+$.getDate()+'.xls');
                        }else{$.alert(response['message']);}                                   
                     },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
                }else{$("#list").startGridEdit();$.alert("No hay Datos!");}
           }
    </script>




    <!--script type="text/javascript">
        $(document).ready(function() {
            $.createDialog('#pagoDialog', 415, 650);
            $("#tabs").tabs();
            $.createDatePickers("input[name='Com_Fec']");
            $.createDatePickers("input[name='Che_Fec']");
            $('#asiento').jqGrid({
                datatype: "local",
                regional: 'es', //ajaxRowOptions: { async: true },                             
                width: 618,
                height: 75,
                postData: {
                    CheListAjax: true
                },
                caption: 'Asiento Contable',
                cmTemplate: {
                    sortable: false
                },
                colModel: [{
                        label: 'Cód.Int.',
                        name: 'Asi_Cod',
                        key: true,
                        width: 15,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'Tipo',
                        name: 'Asi_Deh',
                        hidden: true
                    },
                    {
                        label: 'Código',
                        name: 'Pld_Cdc',
                        width: 45
                    },
                    {
                        label: 'Cuenta',
                        name: 'Pld_Des',
                        width: 130
                    },
                    {
                        label: 'Glosa',
                        name: 'Glosa',
                        width: 130
                    },
                    {
                        label: 'Debe',
                        name: 'Debe',
                        width: 65,
                        align: 'right',
                        formatter: 'currency',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.',
                            defaultValue: ''
                        },
                        summaryType: "sum"
                    },
                    {
                        label: 'Haber',
                        name: 'Haber',
                        width: 65,
                        align: 'right',
                        formatter: 'currency',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.',
                            defaultValue: ''
                        },
                        summaryType: "sum"
                    }
                ],
                loadComplete: function(data) {
                    $(this).jqGrid('footerData', 'set', {
                        Glosa: "<div style='text-align:right;'>TOTALES:</div>",
                        Debe: $(this).jqGrid('getCol', 'Debe', true, 'sum'),
                        Haber: $(this).jqGrid('getCol', 'Haber', true, 'sum')
                    }, true);
                },
                rowNum: 10000,
                gridview: true,
                viewrecords: true,
                footerrow: true,
                userDataOnFooter: false
            });
            $.clearFooterDiario("#asiento");
            $('#cheque').jqGrid({
                datatype: "local",
                regional: 'es', //ajaxRowOptions: { async: true },                             
                width: 618,
                height: 97,
                postData: {
                    CheListAjax: true
                },
                caption: 'Cheques Girados',
                cmTemplate: {
                    sortable: false
                },
                colModel: [{
                        label: 'Cód.Int.',
                        name: 'Che_Cod',
                        key: true,
                        hidden: true,
                        viewable: true
                    },
                    {
                        label: 'Fecha',
                        name: 'Che_Fec',
                        key: true,
                        width: 50
                    },
                    {
                        label: 'Num.',
                        name: 'Che_Num',
                        key: true,
                        width: 30,
                        align: "center"
                    },
                    {
                        label: 'Banco',
                        name: 'Pld_Des',
                        width: 100,
                        title: 'Cuenta Bancaria'
                    },
                    {
                        label: 'Beneficiario',
                        name: 'Beneficiario',
                        width: 150
                    },
                    {
                        label: 'Valor',
                        name: 'Che_Val',
                        key: true,
                        width: 60,
                        align: "right",
                        formatter: 'currency',
                        decimalPlaces: '2',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.'
                        }
                    }
                ],
                rowNum: 10000,
                gridview: true,
                viewrecords: true
            });
            $.createDialog('#successDialog', 150, 550);
        });
        function selectDetalle(Cpp, Com) {
            $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", {
                detAjax: true,
                Cpp: Cpp,
                Com: Com
            }, function(response) {
                if (response['success'] === true) {
                    $("#lblComp2").val(response['com']['Com_Num']);
                    $("#lblComFe2").val(response['com']['Com_Fec']);
                    $("#lblComVal2").val(response['com']['Com_Val']);
                    $("#lblConce2").val(response['com']['Com_Obs']);
                    $("#lblCed2").val(response['com']['Prs_Ced']);
                    $("#lblProv2").val(response['com']['Prs_Ape'] + ' ' + response['com']['Prs_Nom']);
                    $("#lblProv2").attr('title', response['com']['Prs_Ape'] + ' ' + response['com']['Prs_Nom']);
                    $("#lblDirec2").val(response['com']['Prs_Dir']);
                    $("#lblDirec2").attr('title', response['com']['Prs_Dir']);
                    $("#lblFac2").val(response['pag']['Cop_Num']);
                    $("#lblVen2").val(response['pag']['Cpp_Ven']);
                    $("#lblObsV2").val(response['pag']['Pag_Obs']);
                    $("#lblTipPa2").val(response['pag']['Pag_Des']);
                    $("#lblFePa2").val(response['pag']['Pag_Fec']);
                    $("#lblVaPa2").val('$ ' + response['pag']['Pag_Val']);
                    $("#asiento").jqGrid("clearGridData");
                    $("#asiento").jqGrid('setGridParam', {
                        rowNum: response['asi']['records']
                    });
                    $("#asiento").jqGrid('setGridParam', {
                        data: response['asi']['rows'],
                        page: 1,
                        records: response['asi']['records']
                    }).trigger('reloadGrid');
                    $("#cheque").jqGrid("clearGridData");
                    $("#cheque").jqGrid('setGridParam', {
                        rowNum: response['che']['records']
                    });
                    $("#cheque").jqGrid('setGridParam', {
                        data: response['che']['rows'],
                        page: 1,
                        records: response['che']['records']
                    }).trigger('reloadGrid');

                    $('#pagoDialog').dialog('open');
                } else {
                    $.alert(response['message']);
                }
            }, 'json').fail(function(error) {
                $.alert("El Servidor ha fallado en responder!");
            });
        }

        function exportar(banTipo) {
            var batch = new Array();
            var grid = $("#list");
            var ids = grid.jqGrid('getDataIDs');
            for (var i = 0; i < ids.length; i++) {
                var datos = grid.jqGrid('getRowData', ids[i]),
                    ban = true;
                for (var j = 0; j < batch.length; j++) {
                    if (datos['Prv_Cod'] === batch[j]['Prv_Cod']) {
                        ban = false;
                    }
                }
                if (ban) batch.push({
                    Prv_Cod: datos['Prv_Cod'],
                    Proveedor: datos['proveedor']
                });
            }
            for (var i = 0; i < batch.length; i++) {
                batch[i]['Cpps'] = new Array();
                for (var j = 0; j < ids.length; j++) {
                    var datos = grid.jqGrid('getRowData', ids[j]);
                    if (datos['Prv_Cod'] === batch[i]['Prv_Cod']) {
                        batch[i]['Cpps'].push(datos['Cpp_Cod']);
                    }
                }
            }
            //  console.log(batch);     
            var seleccionado = (document.querySelector('input[name="resumido"]:checked')).value;
            if (batch.length > 0) {
                $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", {
                    resumido: $('#resumido').prop('checked'),
                    resumido1: seleccionado,
                    dataReport: batch,
                    tipo: banTipo,
                    caption: grid.parent().parent().parent().find('.ui-jqgrid-title').text(),
                    Pec_Cod: $('#Pec_Cod').val(),
                    txt_fec_ini: $('#txt_fec_ini').val(),
                    txt_fec_fin: $('#txt_fec_fin').val()
                }, function(response) {
                    if (response['success'] === true) {
                        if (banTipo) {
                            $('#Exportar').html(response['html']);
                            $('#Exportar').printElement({
                                pageTitle: '<?Php echo $Ses_Sys_Nom; ?>'
                            });
                        } else
                            $.downloadFile($.exportarExcelBlob(response['html'], 'CCPP'), 'CtaPorPagar-' + $.getDate() + '.xls');
                    } else {
                        $.alert(response['message']);
                    }
                }, 'json').fail(function(error) {
                    $.alert("El Servidor ha fallado en responder!");
                });
            } else {
                $("#list").startGridEdit();
                $.alert("No hay Datos!");
            }
        }
    </script-->
    <!-- FIN DEL DIALOGO DETALLE PAGO -->
    <div id="output"></div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <div id="Exportar" style="display: none;"></div>
</BODY>
</HTML>