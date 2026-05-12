<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por lotes
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaciï¿½n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cccc.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Cccc;
/* Evita el reenvio  */
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(61, $Ses_Emp_Cod, $obBD_conexion);

// Aviso: facturas a crédito cuyo Debe no está en ccpp_cliente (mismos filtros que la grilla; consulta 137)
if (!empty($_POST['getCcccCuentaAlerta'])) {
    $p = $_POST;
    $p['Ses_Emp_Cod'] = $Ses_Emp_Cod;
    if (!isset($p['txt_fec_ini'])) {
        $p['txt_fec_ini'] = '';
    }
    if (!isset($p['txt_fec_fin'])) {
        $p['txt_fec_fin'] = '';
    }
    if (!isset($p['Cli_Cod']) || $p['Cli_Cod'] === null) {
        $p['Cli_Cod'] = '';
    }
    if (!isset($p['Pec_Cod'])) {
        $p['Pec_Cod'] = '';
    }
    if (!isset($p['Tic_Cod'])) {
        $p['Tic_Cod'] = '';
    }
    if (!isset($p['isnegoCCxCC'])) {
        $p['isnegoCCxCC'] = '';
    }
    if (!isset($p['filtroCCxCC'])) {
        $p['filtroCCxCC'] = '';
    }
    $rows = $obBD_con1->getArrayConsulta(137, $p, $obBD_conexion);
    if (!is_array($rows)) {
        $rows = array();
    }
    if (method_exists($obBD_con1, 'utf8_change_param')) {
        $obBD_con1->utf8_change_param($rows);
    } else {
        utf8_encode_deep($rows);
    }
    @ob_end_clean();
    error_reporting(0);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => true,
        'count' => count($rows),
        'rows' => $rows
    ));
    exit();
}

if (isset($dataReport)) {
    $resumido1 = isset($resumido1) ? $resumido1 : 'G';
    // Detalle completo: General (G) o Resumido por factura (T). Solo columnas de totales: Resumido por clientes (S).
    $full = ($resumido1 !== 'S');
    $responce['success'] = false;
    if ($tipo == 'true') $b = '1px';
    else $b = '0.1pt';
    $table['{body}'] = '';
    $table['{rowtotal}'] = '';
    $table['{caption}'] = $caption;
    $table['{caption2}'] = isset($caption2) ? $caption2 : '';
    $table['{empresa}'] = $Ses_Emp_Nom;
    $fecha = explode('-', $hoy);
    $table['{fecha}'] = dias(date('w'), 1) . ' ' . $fecha[2] . ' de ' . mes($fecha[1], 1) . ' de ' . $fecha[0];
    $saldoGeneral = 0;
    $saldoTotalFact = 0;

    // Encabezado según tipo de reporte
    if ($full || $resumido1 == "T") {
        // Encabezado completo para reporte general y resumido total
        $table['{header}'] = '<tr>
            <td style="font-weight:bold; border: 1px solid #000;" colspan="1">No. COMPR.</td>
            <td style="font-weight:bold; border: 1px solid #000;">FECHA EMIS.</td>
            <td style="font-weight:bold; border: 1px solid #000;">FECHA VENC.</td>
            <td style="font-weight:bold; border: 1px solid #000;">&nbsp;</td>    
            <td style="font-weight:bold; border: 1px solid #000;">DOCUM.</td>	  
            <td style="font-weight:bold; border: 1px solid #000;" colspan="2">CTA. BANCARIA / BANCO</td> 
            <td style="font-weight:bold; border: 1px solid #000;">FEC. CH.</td>          
            <td style="font-weight:bold; border: 1px solid #000;text-align:center;" colspan="3">SALDOS</td>
        </tr>';
    } else {
        // Encabezado resumido por clientes (filas compactas; cédula aún separada de cliente)
        $compact = 'line-height:1.15;';
        $table['{header}'] = '<tr>
            <td style="font-weight:bold; border-top:1px solid #000; border-bottom:1px solid #000; text-align:center; white-space:nowrap; min-width:88px; width:12%; padding:3px 12px 3px 5px; ' . $compact . '">C&eacute;dula</td>
            <td style="font-weight:bold; border-top:1px solid #000; border-bottom:1px solid #000; border-left:1px solid #bbb; text-align:left; padding:3px 6px 3px 10px; ' . $compact . '" colspan="7">Cliente</td>
            <td style="font-weight:bold; border-top:1px solid #000; border-bottom:1px solid #000; text-align:center; padding:3px 4px; ' . $compact . '" colspan="3">SALDOS</td>
        </tr>
        <tr>
            <td style="font-weight:bold; border-bottom:1px solid #000; padding:2px 12px 2px 5px; ' . $compact . '">&nbsp;</td>
            <td style="font-weight:bold; border-bottom:1px solid #000; border-left:1px solid #bbb; padding:2px 6px 2px 10px; ' . $compact . '" colspan="7">&nbsp;</td>
            <td style="font-weight:bold; border-bottom:1px solid #000; text-align:right; padding:2px 4px; ' . $compact . '">Total</td>
            <td style="font-weight:bold; border-bottom:1px solid #000; text-align:right; padding:2px 4px; ' . $compact . '">Abonos</td>
            <td style="font-weight:bold; border-bottom:1px solid #000; text-align:right; padding:2px 4px; ' . $compact . '">Saldo</td>
        </tr>';
    }

    if ($resumido1 != "T") {
        foreach ($dataReport as $provee) {
            $saldoProvee = 0;
            $saldoFacturas = 0;
            if ($full) {
                // Reporte General: muestra nombre del cliente en el encabezado
                $table['{body}'] = $table['{body}'] . '<tr>
                <td colspan="8" style="border-left: 0px solid white; border-right:  0px solid white; border-top: 1px solid #fff; border-bottom: 1px solid #fff; font-weight:bold;"><strong> </strong></td> 
                <td style="border-left: 0px solid white; border-right:  0px solid white; border-top: 1px solid #000; border-bottom: 1px solid #000; text-align:right;"><strong>Total</strong></td>
                <td style="border-left: 0px solid white; border-right:  0px solid white; border-top: 1px solid #000; border-bottom: 1px solid #000; text-align:right;"><strong>Abonos</strong></td>
                <td style="border-left: 0px solid white; border-right:  0px solid white; border-top: 1px solid #000; border-bottom: 1px solid #000; text-align:right;"><strong>Saldo</strong></td>
                </tr>';
            }
            foreach ($provee['Cpcs'] as $cuenta) {
                $Cpp_Data = $obBD_con1->getRowConsulta(31, $cuenta, $obBD_conexion);
                $verif_rete = $obBD_con1->getRowConsulta(62, $Cpp_Data['Vet_Cod'], $obBD_conexion);
                if (empty($Cpp_Data)) $Cpp_Data = $obBD_con1->getRowConsulta(46, $cuenta, $obBD_conexion);
                utf8_encode_deep($Cpp_Data);
                $saldo = $Cpp_Data['total'] * 1;
                $saldoFacturas = $saldoFacturas + $Cpp_Data['total'] * 1;
                $saldoTotalFact = $saldoTotalFact + $Cpp_Data['total'] * 1;
                if ($full) $table['{body}'] = $table['{body}'] . '<tr><td style="font-weight:bold;"  colspan="2">' . $Cpp_Data['Com_Codigo'] . '</td>  	    
                    <td style="font-weight:bold;white-space:nowrap;width:70px;">' . $Cpp_Data['Caj_Fec'] . '</td> 
                    <td style="font-weight:bold;" colspan="5">' . $Cpp_Data['Tic_Des'] . ': ' . $Cpp_Data['Vet_Num'] . ' -  ' . $provee['Cliente'] . '</td>
                     <td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($Cpp_Data['total'], 2) . '</td>
		       
		        </tr>';
                $pagoRetencion = false;
                if ($Cpp_Data['Cpc_Cod'] != NULL) {
                    $cancelaciones = $obBD_con1->getArrayConsulta(7, $Cpp_Data['Cpc_Cod'], $obBD_conexion);
                    utf8_encode_deep($cancelaciones);
                    foreach ($cancelaciones as $pago) {
                        $abrevi = $obBD_con1->getRowConsulta(53, $pago['Pag_Cod'], $obBD_conexion);
                        utf8_encode_deep($abrevi);
                        $banco = NULL;
                        $info = "";
                        $PagAbr = $abrevi['Pag_Abr'];
                        if ($PagAbr != 'RET' || $verif_rete['tot_fact'] > 0) {
                            if ($PagAbr == 'CHE') {
                                $banco = $obBD_con1->getRowConsulta(38, $pago['Dcc_Cod'], $obBD_conexion);
                                $info =  $banco != NULL ? $banco['Che_Cta'] . '/' . $banco['Banco'] : '';
                            }
                            if ($PagAbr == 'TRF' || $PagAbr == 'DEP')
                                $info =  $pago['Pld_Des'];
                            $saldo = $saldo - $pago['Cpc_Val'];
                            if ($full) $table['{body}'] = $table['{body}'] . '<tr>
                            <td style="font-weight:bold;border-right: ' . $b . ' solid #000;">&gt;</td>
                            <td>' . $pago['Com_Codigo'] . '</td>
                            <td style="text-align:center;white-space:nowrap;">' . $pago['Cpc_Fec'] . '</td>
                            <td>' . $PagAbr . '</td>
                            <td style="mso-number-format:&#39;@&#39;;">' . ($banco != NULL ? $banco['Che_Num'] : '') . '</td>
                            <td style="white-space:nowrap;overflow:hidden;">' . $info . '</td>
                            <td>' . ($banco != NULL && $banco['Che_Fec'] != '' ? '(' . $banco['Che_Fec'] . ')' : '') . '</td>
                            <td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;" colspan="2">' . number_format($pago['Cpc_Val'], 2) . '</td>
                           <td></td>
                        </tr>';
                        }
                    }
                }

                if ($pagoRetencion == false) {
                    if ($verif_rete['tot_fact'] <= 0) {
                        $Retenciones1 = $obBD_con1->getArrayConsulta(32, $Cpp_Data['Vet_Cod'], $obBD_conexion);
                        $Retenciones2 = $obBD_con1->getArrayConsulta(33, $Cpp_Data['Vet_Cod'], $obBD_conexion);
                        utf8_encode_deep($Retenciones1);
                        utf8_encode_deep($Retenciones2);
                        $Retenciones = array_merge($Retenciones1, $Retenciones2);
                        foreach ($Retenciones as $ret) {
                            $saldo = $saldo - round($ret['retencion'], 2);
                            if ($full) $table['{body}'] = $table['{body}'] . '<tr>
                            <td style="font-weight:bold;border-right: ' . $b . ' solid #000;">&gt;</td>
                            <td></td>
                            <td style="text-align:center;white-space:nowrap;">' . $ret['Ret_Fec'] . '</td>
                            <td></td>
                            <td>' . $ret['tipo'] . '</td>
                            <td style="mso-number-format:&#39;@&#39;;">' . $ret['Ret_Num'] . '</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($ret['retencion'], 2) . '</td>
                            <td></td>
                        </tr>';
                        }
                    }
                }
                if ($full) $table['{body}'] = $table['{body}'] . '<tr><td style="word-wrap: break-word;" colspan="8"><strong>Obs:</strong> ' . $Cpp_Data['Vet_Obs'] . '</td>
               
                <td colspan="2" style="text-align:right;border-top: ' . $b . ' solid #000;">SALDO DOCUMENTO:</td>
                <td style="border-top: ' . $b . ' solid #000;text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldo, 2) . '</td></tr><tr><td colspan="10" style="height:20px;"></td></tr>';
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
                <td colspan="5" style="border-bottom: 3px double #000; font-weight:bold;text-align:right;">TOTAL CLIENTE: ' . $provee['Cliente'] . ' &nbsp;&gt;&gt;</td>
                <td style="border-bottom: 3px double #000;text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoProvee, 2) . '</td>
                </tr>
                <tr>
                <td colspan="10" style="height:25px;">&nbsp;</td>
                </tr>';
            } else {
                $cedCli = isset($provee['Prs_Ced']) ? $provee['Prs_Ced'] : '';
                $table['{body}'] = $table['{body}'] . '<tr>
                <td style="border-bottom:1px solid #D5DADF; mso-number-format:&#39;@&#39;; white-space:nowrap; min-width:88px; width:12%; padding:2px 12px 2px 5px; line-height:1.15; vertical-align:middle;">' . $cedCli . '</td>
                <td colspan="7" style="border-bottom:1px solid #D5DADF; border-left:1px solid #bbb; padding:2px 6px 2px 10px; line-height:1.15; vertical-align:middle;">' . $provee['Cliente'] . '</td>
                <td style="text-align:right; border-bottom:1px solid #D5DADF; padding:2px 4px; line-height:1.15; vertical-align:middle;">' . number_format($saldoFacturas, 2) . '</td>
                <td style="text-align:right; border-bottom:1px solid #D5DADF; padding:2px 4px; line-height:1.15; vertical-align:middle;">' . number_format($saldoAbono, 2) . '</td>
                <td style="text-align:right; border-bottom:1px solid #D5DADF; padding:2px 4px; line-height:1.15; vertical-align:middle;">' . number_format($saldoProvee, 2) . '</td>
                </tr>';
            }
            $saldoGeneral = $saldoGeneral + $saldoProvee;
        }
        if ($full) {
            $table['{rowtotal}'] = $table['{rowtotal}'] . '<tr>
            <td colspan="10" style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;">TOTAL GENERAL:</td>       
            <td style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoGeneral, 2) . '</td>
            </tr>';
        } else {
            $table['{rowtotal}'] = $table['{rowtotal}'] . '<tr>
            <td colspan="8" style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000; padding:3px 6px; line-height:1.15;">TOTAL GENERAL:</td> 
            <td style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000; padding:3px 4px; line-height:1.15;">' . number_format($saldoTotalFact, 2) . '</td> 
            <td style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000; padding:3px 4px; line-height:1.15;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoTotalFact - $saldoGeneral, 2) . '</td>
            <td style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000; padding:3px 4px; line-height:1.15;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoGeneral, 2) . '</td>
            </tr>';
        }
    } else {
        $full = false;
        foreach ($dataReport as $provee) {
            $saldoProvee = 0;
            $saldoFacturas = 0;
            $saldo_abono = 0;
            $table['{body}'] = $table['{body}'] . '<tr>
                <td style="font-weight:bold; border-top: 1px solid #000; border-bottom: 1px solid #000;" colspan="8">CLIENTE : ' . $provee['Cliente'] . '</td>
                <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; text-align:right;"><strong>Total</strong></td>
                <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; text-align:right;"><strong>Abonos</strong></td>
                <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; text-align:right;"><strong>Saldo</strong></td>
                </tr>';
            foreach ($provee['Cpcs'] as $cuenta) {
                $Cpp_Data = $obBD_con1->getRowConsulta(31, $cuenta, $obBD_conexion);
                $verif_rete = $obBD_con1->getRowConsulta(62, $Cpp_Data['Vet_Cod'], $obBD_conexion);
                if (empty($Cpp_Data)) $Cpp_Data = $obBD_con1->getRowConsulta(46, $cuenta, $obBD_conexion);
                utf8_encode_deep($Cpp_Data);
                $saldo = $Cpp_Data['total'] * 1;
                $saldoFacturas = $saldoFacturas + $Cpp_Data['total'] * 1;
                $saldoTotalFact = $saldoTotalFact + $Cpp_Data['total'] * 1;
                $pagoRetencion = false;
                if ($Cpp_Data['Cpc_Cod'] != NULL) {
                    $cancelaciones = $obBD_con1->getArrayConsulta(7, $Cpp_Data['Cpc_Cod'], $obBD_conexion);
                    utf8_encode_deep($cancelaciones);
                    foreach ($cancelaciones as $pago) {
                        $abrevi = $obBD_con1->getRowConsulta(53, $pago['Pag_Cod'], $obBD_conexion);
                        utf8_encode_deep($abrevi);
                        $banco = NULL;
                        $PagAbr = $abrevi['Pag_Abr'];
                        if ($PagAbr != 'RET' || $verif_rete['tot_fact'] > 0) {
                            if ($PagAbr != 'EF')
                                $banco = $obBD_con1->getRowConsulta(38, $pago['Dcc_Cod'], $obBD_conexion);
                            $saldo = $saldo - $pago['Cpc_Val'];
                            $saldo_abono = $saldo_abono + $pago['Cpc_Val'];
                        }
                    }
                }
                if ($pagoRetencion == false) {
                    if ($verif_rete['tot_fact'] <= 0) {
                        $Retenciones1 = $obBD_con1->getArrayConsulta(32, $Cpp_Data['Vet_Cod'], $obBD_conexion);
                        $Retenciones2 = $obBD_con1->getArrayConsulta(33, $Cpp_Data['Vet_Cod'], $obBD_conexion);
                        utf8_encode_deep($Retenciones1);
                        utf8_encode_deep($Retenciones2);
                        $Retenciones = array_merge($Retenciones1, $Retenciones2);
                        foreach ($Retenciones as $ret) {
                            $saldo = $saldo - round($ret['retencion'], 2);
                            $saldo_abono = $saldo_abono + round($ret['retencion'], 2);
                        }
                    }
                }

                $table['{body}'] = $table['{body}'] . '<tr><td style="font-weight:400;"  colspan="2">' . $Cpp_Data['Com_Codigo'] . '</td>  	    
                <td style="font-weight:400;white-space:nowrap;width:75px;">' . $Cpp_Data['Caj_Fec'] . '</td> 
                <td style="font-weight:400;white-space:nowrap;width:75px;">' . $Cpp_Data['Cpc_Ven'] . '</td> 
                <td style="font-weight:400;" colspan="4">' . $Cpp_Data['Tic_Des'] . ': ' . $Cpp_Data['Vet_Num'] . ' -  ' .  $Cpp_Data['Vet_Obs'] . '</td>
                <td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($Cpp_Data['total'], 2) . '</td> 
                <td style="text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldo_abono, 2) . '</td>
                <td style="border-right: 1px solid #fff;text-align:right;mso-number-format:&#39;#,##0.00&#39;;">' . number_format(max($saldo, 0), 2) . '</td>
                </tr>';
                $saldoProvee = $saldoProvee + $saldo;
                $saldoAbono = $saldoFacturas - $saldoProvee;
                $saldo_abono = 0;
            }
            $table['{body}'] = $table['{body}'] . '
            <tr>
               <td style="text-align:right; border-bottom:1px solid #D5DADF;" colspan="11"></td>
            </tr>
            <tr>
                <td style="text-align:right; border-bottom:1px solid #D5DADF;font-weight:bold;" colspan="8"><b>TOTAL DE: ' . $provee['Cliente'] . '   </b> </td>
                <td style="text-align:right; border-bottom:1px solid #D5DADF;font-weight:bold;">' . number_format($saldoFacturas, 2) . '</td>
                <td style="text-align:right; border-bottom:1px solid #D5DADF;font-weight:bold;">' . number_format($saldoAbono, 2) . '</td>
                <td style="text-align:right; border-bottom:1px solid #D5DADF;font-weight:bold;">' . number_format(max($saldoProvee, 0), 2) . '</td>
            </tr>
            <tr>
                <td colspan="11" style="height:15px;"></td>
            </tr>
               ';
            $saldoGeneral = $saldoGeneral + $saldoProvee;
        }
        $table['{rowtotal}'] = $table['{rowtotal}'] . '<tr>
            <td colspan="8" style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;">TOTAL GENERAL:</td> 
            <td style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;">' . number_format($saldoTotalFact, 2) . '</td> 
            <td style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoTotalFact - $saldoGeneral, 2) . '</td>
            <td style="font-weight:bold;text-align:right; border-bottom: 3px double #000; border-top:1px solid #000;mso-number-format:&#39;#,##0.00&#39;;">' . number_format($saldoGeneral, 2) . '</td>
            </tr>';
    }
    $templateFile = __DIR__ . '/tes_pri_ccpp_cobros.html';
    if (!file_exists($templateFile)) {
        $responce['error'] = 'Plantilla no encontrada: ' . $templateFile;
        $responce['success'] = false;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($responce);
        exit();
    }
    $responce['html'] = reporteHtml($table, $templateFile);
    $responce['success'] = true;
    header('Content-Type: application/json; charset=utf-8');
    $jsonResult = json_encode($responce);
    if ($jsonResult === false) {
        echo json_encode(array('success' => false, 'error' => 'Error JSON: ' . json_last_error_msg()));
    } else {
        echo $jsonResult;
    }
    exit();
}

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
    if (!isset($txt_fec_ini)) $txt_fec_ini = '';
    if (!isset($txt_fec_fin)) $txt_fec_fin = '';

    // if (empty($isnegoCCxCC)) $isnegoCCxCC = '';
    // if (empty($filtroCCxCC)) $filtroCCxCC = '';

    $responce['rows'] = $obBD_con1->getArrayConsulta(8, array('0' => $Ses_Emp_Cod, '1' => $Cli_Cod, '2' => $Pec_Cod, '3' => $txt_fec_ini, '4' => $txt_fec_fin, '5' => $Tic_Cod, '6' => $isnegoCCxCC, '7' => $filtroCCxCC), $obBD_conexion);
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
    $responce['rows'] = $obBD_con1->getArrayConsulta(7, $ajaxSubgrid, $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    $responce['records'] = count($responce['rows']);
    echo json_encode($responce);
    exit();
}
if (isset($detAjax)) {
    $responce['success'] = false;
    $responce['com'] = $obBD_con1->getRowConsulta(9, $Com, $obBD_conexion);
    $responce['pag'] = $obBD_con1->getRowConsulta(30, $Cpc . '*' . $Com, $obBD_conexion);
    $responce['asi']['rows'] = $obBD_con1->getArrayConsulta(10, $Com, $obBD_conexion);
    $responce['asi']['records'] = count($responce['asi']['rows']);
    $responce['che']['rows'] = $obBD_con1->getArrayConsulta(11, $Cpc . '*' . $Com, $obBD_conexion);
    $responce['che']['records'] = count($responce['che']['rows']);
    $responce['link_rec'] = "/tesoreria/FRONT/tes_pri_recibocobro_1.0.php?Com_Cod=$Com";
    utf8_encode_deep($responce);
    $responce['success'] = true;
    echo json_encode($responce);
    exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>

<HEAD>
    <!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?php echo "Ccxcc Consultar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/basic.php"); ?>
    <?php require_once("../../mascaras/model1/estilos/jqgrid.php") ?>
    <meta http-equiv="Content-Type" content="text/html;" />
    <!-- Variables PHP para JavaScript -->
    <script type="text/javascript">
        var isPlanta = '<?php echo $isPlanta; ?>';
        var plantName = '<?php echo $plantName; ?>';
        var isnego = '<?php echo $configs['Cof_NegCam']; ?>';
        var phpSelf = '<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>';
        var sesSysNom = '<?php echo $Ses_Sys_Nom; ?>';
    </script>
    <!-- JavaScript externo -->
    <script type="text/javascript" src="../VALIDACIONES/tes_val_con_cccc_pagados.js?v=1"></script>
</HEAD>

<BODY>
    <div id="set1">
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table" style="table-layout:fixed;">
            <tr class="BarraTitulo">
                <td colspan="2" height="10">&raquo; Historial De Cr&eacute;ditos Otorgados </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div id="comp1">
                        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
                            <tr>
                                <td>
                                    <FIELDSET>
                                        <LEGEND>
                                            <label class="Titulos2">Seleccionar Cliente</label>
                                        </LEGEND>
                                        <form id="provFormTemp">
                                            <div class="segmento">C&eacute;dula/R.U.C.:</div>
                                            <div class="datasegmento">

                                                <input id="docu" name="search" maxlength="13" onkeydown='if (event.keyCode === 13) $.SearchOrDialog("#provDialog",selectProvee);' type="text" class="search ui-corner-all" placeholder="Ingrese Cedula/R.U.C." title="Buscar Cliente Por Documento o Descripci&oacute;n" autofocus />
                                                <input type="text" name="op_opciones" value="c" style="display: none;" />
                                                <input id="PrvCodBus" type="hidden" name="Cli_Cod" value="" />
                                                <a onclick="$('#provDialog').dialog('open');/*$('#docu').removeAttr('readOnly');*/" title="B&uacute;squeda de Clientes" class="btn btn-success btn-mini"><i class=" icon-check icon-white"></i></a>
                                                <a onclick="selectProvee();" title="Quitar Cliente" class="btn btn-success btn-mini"><i class=" icon-eject icon-white"></i></a>

                                            </div>
                                        </form>
                                        <div class="segmento">Cliente:</div>
                                        <div class="datasegmento"><input id="lblProv" type="text" class="label ui-widget-content ui-corner-all" readonly /></div><br />
                                        <div class="segmento">Direcci&oacute;n:</div>
                                        <div class="datasegmento"><input id="lblDirec" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                                    </FIELDSET>
                                </td>

                                <td>
                                    <fieldset>
                                        <legend> <label class="Titulos2">Filtros</label></legend>
                                        <form id="formCompTemp" action="javascript:$('#list').Search('#formCompTemp','ajaxComprobante');setCaption();">
                                            <input type="hidden" name="Cli_Cod" value="" />
                                            <div>
                                                <div class="segmento">Todos: <input name="op_opciones" type="radio" value="T" checked alt="" /></div>
                                                <div class="segmento">Cobrados: <input name="op_opciones" type="radio" value="P" alt="" /></div>
                                                <div class="segmento">Por Cobrar: <input name="op_opciones" type="radio" value="I" alt="" /></div>
                                            </div>
                                            <!-- informativo -->
                                            <i class="glyphicon glyphicon-info-sign blue"></i>
                                            <div class="d-flex justify-content-center " style="text-align: center;padding:8px;display:none">
                                                <span class="alert alert-warning text-center"><b>Importante:</b> Los documentos se filtra por fecha de emision.</span>
                                            </div>
                                            <!-- Espaciado entre secciones -->
                                            <div style="height: 8px;"></div>
                                            <!-- Rango de Fechas -->
                                            <div style="display: flex; align-items: center; gap: 15px;">
                                                <div class="segmento" style="margin-left: -80px;">Per&iacute;odo:</div>
                                                <div>
                                                    <select name="Pec_Cod" id="Pec_Cod" style="text-align: center; width: 110px;" onchange="if($('#Pec_Cod').val()!==''){$('#rangeDates').addClass('disabled').find('input').attr('disabled','disabled');}else{$('#rangeDates').removeClass('disabled').find('input').removeAttr('disabled');}" class="ui-corner-all">
                                                        <?php
                                                        $rs_periodos = $obBD_con1->consulta(sentencias_cccc(5, array(0 => $Ses_Emp_Cod)), $obBD_conexion->conexion);
                                                        $row_rs_periodos = $obBD_con1->registros();
                                                        $total_rs_periodos = $obBD_con1->numregistros();
                                                        if ($total_rs_periodos > 0) {
                                                            do {
                                                        ?>
                                                                <option value="<?Php echo $row_rs_periodos['Pec_Cod']; ?>">
                                                                    <?php echo $row_rs_periodos['Periodo']; ?></option>
                                                        <?php
                                                            } while ($row_rs_periodos = $obBD_con1->fetch_assoc($rs_periodos));
                                                        }
                                                        ?>
                                                        <option value="">Por Fecha</option>
                                                    </select>
                                                </div>
                                                <div id="rangeDates" class="datasegmento" style="display: flex; align-items: center; gap: 5px; margin-left: -12px;">
                                                    <span class="segmento">Desde:</span>
                                                    <input name="txt_fec_ini" type="text" id="txt_fec_ini" size="12" class="focus ui-corner-all" style="text-align: center;" />
                                                    <span class="segmento">Hasta:</span>
                                                    <input name="txt_fec_fin" type="text" id="txt_fec_fin" size="12" class="focus ui-corner-all" style="text-align: center;" />
                                                    <button type="button" class="btn btn-info" title="El filtro de fechas aplicado en este reporte solo afecta las facturas. Los pagos realizados hasta la fecha no están siendo filtrados por el rango de fechas seleccionado, por lo que se muestran todos los pagos realizados hasta el día de hoy.">
                                                        <i class="icon-info-sign icon-white"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- Espaciado entre secciones -->
                                            <div style="height: 8px;"></div>
                                            <!-- Tipo de Documento -->
                                            <div class="datasegmento" style="display: flex; align-items: center; gap: 10px;">
                                                <label for="Tic_Cod" style="margin-right: 5px; width: 62px;">Tipo Doc.</label>
                                                <select name="Tic_Cod" id="Tic_Cod" class="segemento datasegmento" style="height: 25px;">
                                                    <option value="">
                                                        << TODOS>>
                                                    </option>
                                                    <?php
                                                    $tipos = $obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('Tic_Est' => 'A'), $obBD_conexion);
                                                    foreach ($tipos as $key => $value) { ?>
                                                        <option value="<?Php echo $value['Tic_Cod']; ?>"><?php echo $value['Tic_Des']; ?></option>
                                                    <?php } ?>
                                                </select>
                                                <button type="button" onclick="this.form.submit()" class="btn btn-success" style="height: 25px; width: 105px;" title="Ejecutar B&uacute;squeda">
                                                    <i class="icon-search icon-white"></i>
                                                    <span>Buscar</span>
                                                </button>
                                            </div>
                                            <input type="hidden" id="isnegoCCxCC" name="isnegoCCxCC" class="form-control" value="" />
                                            <input type="hidden" id="filtroCCxCC" name="filtroCCxCC" class="form-control" />
                                        </form>
                                    </fieldset>
                                </td>
                                <!-- </tr> -->
                            <tr>

                                <td colspan="2" height="389" align="left" valign="top">
                                    <FIELDSET>
                                        <LEGEND>
                                            <label class="Titulos2">Resultados de la b&uacute;squeda</label>
                                        </LEGEND>
                                        <div id="cccc_alerta_clie_varios" class="alert alert-warning" style="display:none;margin-bottom:10px;padding:10px 14px;font-size:12px;">
                                            <span class="glyphicon glyphicon-warning-sign"></span>
                                            Facturas a cr&eacute;dito con cuenta diferente a <strong>Clientes varios</strong> (cuenta del Debe no registrada en CxC clientes / <code>ccpp_cliente</code>); no se incluyen en este listado:
                                            <a href="#" id="cccc_alerta_clie_varios_num" class="badge alert-danger" style="font-size:13px;cursor:pointer;vertical-align:middle;margin-left:4px;" title="">0</a>
                                            <span class="text-muted" style="margin-left:6px;">Pase el cursor o haga clic en el n&uacute;mero para ver el detalle.</span>
                                        </div>
                                        <div id="grillaComp">
                                            <table id="list"></table>
                                            <div id="listPager"></div>
                                        </div>
                                    </FIELDSET>

                                    <FIELDSET>
                                        <LEGEND>
                                            <label class="Titulos2">Seleccione el tipo de reporte </label>
                                        </LEGEND>



                                        <div class="radio-container" style="padding:5px;">
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
    <div id="provDialog" title="Búsqueda de Clientes">
        <form>
            <fieldset>
                <legend><label class="Titulos2">Búsqueda de Cliente</label></legend>
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
                        <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cliente a buscar..." autofocus /><input type="text" style="display:none" /></td>
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
                        <div class="datasegmento"><input id="lblComVal2" type="text" class="text medium ui-widget-content ui-corner-all" style="text-align: right;" readonly />
                            <a id="impRecib" target="_blank" href="" style="display: inline;" title="Imprimir Recibo"><span class="btn btn-primary btn-mini start"> <i class="icon-print icon-white"></i> <span>Recibo</span></span> </a>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div style="width: 50%;display: inline;float:right;">
                <fieldset>
                    <legend><label class="Titulos2">Datos del Cliente</label></legend>
                    <div class="row">
                        <div class="segmento">Cédula:</div>
                        <div class="datasegmento"><input id="lblCed2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                    </div>
                    <div class="row">
                        <div class="segmento">Cliente:</div>
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
    <!-- FIN DEL DIALOGO DETALLE PAGO -->
    <div id="output"></div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <div id="Exportar" style="display: none;"></div>
</BODY>

</HTML>