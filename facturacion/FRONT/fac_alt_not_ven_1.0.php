<?php

/**
 * @abstract Permite realizar el registro de alta de notas de credito y debito en ventas
 * @author Erick Cordova
 * @version 2.0
 * Fecha de creaci�n  2017-07-25
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_facturaVenta;
//borrar debug completo
//$obBD_con1->echoLog($obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion));
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

if (isset($getDateServ)) {
    $resp['hoy'] = date("Y-m-d");
    $obBD_con1->echoJson($resp);
}

//Secci�n para listar los clientes registrados en la empresa
if (isset($clieAjax)) {
    $obBD_con1->getPageGridJson(1, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
}
/* ver si exite un cliente */
if (isset($provAjax2)) {
    $responce['rows'] = $obBD_con1->getArrayConsulta(2, $Prs_Ced . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $responce['total'] = count($responce['rows']);
    $responce['success'] = true;
    $obBD_con1->echoJson($responce);
}
/* guarda un nuevo cliente */
if (isset($guardaClieAjax)) {
    $data = $_POST;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    if (empty($Prs_Cod)) {
        $obBD_con1->operacionobBD(3, $data, $obBD_conexion);
        $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    }
    $obBD_con1->operacionobBD(4, $data, $obBD_conexion);
    $data['Cli_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    $data['cliente'] = trim($data['Prs_Ape'] . ' ' . $data['Prs_Nom']);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true, 'clie' => $data);
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacci&oacuten!', 'error' => $obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

//Secci�n para extraer el Pun_Cod y Vnd_Cod del usuario sobre la tabla vendedor
$rs_Punto = $obBD_con1->getRowConsulta(7, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);

//Secci�n para obtener el n�mero de secuencia
if (isset($numeroSec)) {
    $response = $obBD_con1->getRowConsulta(9, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod . '*' . $Tic_Cod . '*' . $Aut_Cod, $obBD_conexion);
    if (isset($Aut_Sri)) $response['Aut_Sri'] = $Aut_Sri;
    $siguiente = $obBD_con1->getRowConsulta(10, $response['Aut_Ini'] . '*' . $response['Aut_Fin'] . '*' . $response['Aut_Sri'] . '*' . $Tic_Cod . '*' . $Ses_Suc_Cod . '*' . $Pun_Sri, $obBD_conexion);
    $response['Vet_Num'] = $siguiente['siguiente'];
    $response['contador'] = $siguiente['contador'];
    echo json_encode($response);
    exit();
}

if (isset($getForCod)) {
    $resp['data'] = $obBD_con1->getArrayConsulta(89, '', $obBD_conexion);
    $resp['succes'] = true;
    $obBD_con1->echoJson($resp);
}

//Secci�n para comprobar si el n�mero de secuencia ya se encuentra registado
if (isset($existeNumdoc)) {
    $rs_numdocumento = $obBD_con1->getRowConsulta(11, $Ses_Suc_Cod . '*' . $Aut_Sri . '*' . $Vet_Num . '**' . $Pun_Sri, $obBD_conexion);
    if ($rs_numdocumento['total'] * 1 > 0) {
        $response['existe'] = true;
    } else {
        $response['existe'] = false;
    }
    echo json_encode($response);
    exit();
}

/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(12, $Ses_Emp_Cod, $obBD_conexion);
$vendedor = $obBD_con1->getRowConsulta(85, $Ses_Suc_Cod . '*' . $Ses_Prs_Cod, $obBD_conexion);
/* Consulta los productos */
if (isset($proAjax)) {
    if (!empty($Caj_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(78, $Ses_Emp_Cod . '*' . $Caj_Fec, $obBD_conexion);
    else $Pec_Cop = array('Pla_Cod' => null);
    $contar = $obBD_con1->getRowConsulta(13, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*', $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(13, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*' . $pagination['limits'], $obBD_conexion);
        foreach ($responce['rows'] as &$r) {
            $r['Precios'] = $obBD_con1->getArrayConsulta(14, $Ses_Suc_Cod . '*' . $r['Pro_Cod'] . '*' . 'A', $obBD_conexion);
            $precio = $obBD_con1->getRowConsulta(14, $Ses_Suc_Cod . '*' . $r['Pro_Cod'] . '*' . 'A' . '*' . 'D' . '*', $obBD_conexion);
            if (!empty($precio['Pre_Pvp'])) {
                $r = array_merge($r, $precio);
                $r['Vet_Pru'] = $r['Pre_Pvp'];
            }
            if ($configs['Cof_Con'] == 'S' && !empty($Pla_Cod)) {
                $cuenta = $obBD_con1->getRowConsulta(15, $Pla_Cod . '*' . $r['Pro_Cod'] . '*' . 'V', $obBD_conexion);
                if (!empty($cuenta['Pld_Cod'])) $r = array_merge($r, $cuenta);
            }
        }
        unset($r);
    }
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

$ivas = $obBD_con1->getArrayConsulta(16, "", $obBD_conexion);      //Secci�n para obtener los ivas de la tabla iva
$bankos = $obBD_con1->getArrayConsulta(18, "", $obBD_conexion);    //Secci�n para obtener los bancos de la tabla bancos

if (isset($buscarCuentas)) {
    $contado1 = $obBD_con1->getArrayConsulta(19, $Pla_Cod . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $contado2 = $obBD_con1->getArrayConsulta(20, $Pla_Cod, $obBD_conexion);
    $contado = array_merge($contado2, $contado1);
    //$obBD_con1->echoLog($contado);
    $response['Contado'] = $contado;
    $credito = $obBD_con1->getArrayConsulta(90, $Pla_Cod . '*' . '2', $obBD_conexion);
    $response['Credito'] = $credito;
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

/* Consulta del codigo retencion */
if (isset($codiAjax)) {
    $data = $_GET;
    $contar = $obBD_con1->getRowConsulta(21, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data['limits'] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(21, $data, $obBD_conexion);
        if ($configs['Cof_Con'] == 'S' && !empty($Pla_Cod)) {
            foreach ($responce['rows'] as &$r) {
                $cuenta = $obBD_con1->getRowConsulta(22, $Pla_Cod . '*' . $r['Ren_Cod'] . '*V', $obBD_conexion);
                if (!empty($cuenta['Pld_Cod'])) $r = array_merge($r, $cuenta);
            }
            unset($r);
        }
    }
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

if (isset($autorizaAjax)) {
    $obBD_con1->getPageGridJson(108, $rs_Punto['Pun_Cod'] . '*' . $Tic_Cod, $obBD_conexion, $page, $rows);
}

if (isset($ventasAjax)) {
    $obBD_con1->getPageGridJson(109, $Caja_Fecha . '*' . $Ses_Suc_Cod . '**' . $search, $obBD_conexion, $page, $rows);
}

if (isset($getDataPunto)) {
    $resp = $obBD_con1->getRowConsulta(7, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
    $obBD_con1->echoJson($resp);
}


if (isset($getValoresVenta)) {
    $resp['Vet_Total'] = $obBD_con1->getRowConsulta(112, $Vet_Cod, $obBD_conexion, true);
    $resp['Vet_Abonos'] = $obBD_con1->getRowConsulta(115, $Vet_Cod, $obBD_conexion, true);
    $resp = array_merge($resp['Vet_Total'], $resp['Vet_Abonos']);
    $resp['pagos'] = $obBD_con1->getArrayConsulta(92, $Vet_Cod, $obBD_conexion, true);
    $resp['success'] = true;
    $obBD_con1->echoJson($resp);
}





/* Secci�n para realizar el guardado */
if (isset($saveDocument)) {
    $obBD_con1->validaCierrePeriodo('ventas', 'Caj_Fec', 'Vet_Cod', $Caj_Fec, null, $obBD_conexion);
    /* Creacion de Objetos de Conexiones para Proceso de Guardado de Venta*/
    $obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_facturaVenta;
    /*Habilita Debuger de SQLs en Proceso de Guardado de Venta*/
    // $obBD_conIns->debug(true);
    // $obBD_con1->debug(true);
    /*Inicio de Transaccion*/
    $obBD_conIns->inicio_transaccion($obBD_conexionIns);
    /*Verifica usuario tenga Permisos de Vendedor*/
    if (empty($vendedor['Vnd_Cod'])) {
        $responce['message'] = "No tiene permisos de Vendedor!";
    }
    $Vnd_Cod = $vendedor['Vnd_Cod'];
    if (is_string($items)) $items = json_decode(stripslashes($items), true);

    try {
        //Seccion para verificar si la caja ya fue aperturada
        $rs_Caja = $obBD_con1->getRowConsulta(76, $rs_Punto['Pun_Cod'] . '*' . $Caj_Fec, $obBD_conexion);
        if (empty($rs_Caja['Caj_Cod'])) {
            //Secci�n para aperturar la caja a trav�s de insert a la tabla caja_aper
            $obBD_conIns->operacionobBD(77, $rs_Punto['Pun_Cod'] . '*' . $Caj_Fec, $obBD_conexionIns);
            //Secci�n para obtener el id ingresado en la tabla caja_aper
            $Caj_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
        } else {
            $Caj_Cod = $rs_Caja['Caj_Cod'];
        }

        /* valida que no exista el documento */
        $num_existe_gencod = $obBD_con1->getRowConsulta(50, $Ses_Suc_Cod . '*' . $Aut_Sri . '*' . $Vet_Num . '*' . $Vet_Cod . '*' . $documento['punsri'], $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI
        if ($num_existe_gencod['total'] * 1 > 0) {
            $responce['message'] = "El doc. $Tic_Des No. $Vet_Num ya existe!";
        }
        $claveAcceso = NULL;
        if ($Aut_Tem == 'E' && $input_autorizacion == '') {
            $Vet_Aut = 'N';
            require_once('../LOGICA/fac_log_electronica.php');
            if ($Tic_Sri == 4) {
                $obBD_elect =  new Class_Log_Datos_NCredito_Elect();
            }
            if ($Tic_Sri == 5) {
                $obBD_elect =  new Class_Log_Datos_NDebito_Elect();
            }
            $claveAcceso = $obBD_elect->getClaveAcceso($Aut_Cod, $Caj_Fec, $Vet_Num, $obBD_conexion);
            if (empty($claveAcceso)) $responce['message'] = "Error al generar <u>Clave de Acceso</u> del <i>Comprobante Electrónico</i>!";
        }

        // nuevo bloque para autorizacion externa
        if (!empty($input_autorizacion)) {
            $Vet_Aut = 'S';
            $Vet_Sri = $input_autorizacion;
            $Vet_Xml = $input_autorizacion;
            $claveAcceso = $input_autorizacion;
        }

        if (!empty($responce['message'])) {
            echo json_encode($responce);
            exit();
        }

        /* Cabecera de la factura de venta */
        $obBD_conIns->operacionobBD(114, $Tic_Cod . '*' . $Cli_Cod . '*' . $Ciu_Cod . '*' . $Caj_Cod . '*' . $rs_Punto['Vnd_Cod'] . '*' .
            $Vet_Num . '*' . $Vet_Obs . '*' . $documento['autcod'] . '*' . $Vet_Des . '*' . $hora . '*' .
            $claveAcceso . '*' . $Vet_Aut . '*' . $Vet_Sri . '****1*' .
            $ventas[0]['Vet_Num_Asoc'] . '*' . $ventas[0]['Tic_Cod'] . '*' . $ventas[0]['Caj_Fec'], $obBD_conexionIns);
        $Vet_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
        $cod_pro_unique = array();

        /* Inserta datos en el detalle de la venta */
        $kardex = array('IoE' => 'E', 'Kar_Fec' => $hoy, 'Kar_Hor' => date("H:i:s"), 'Vet_Cod' => $Vet_Cod, 'Vnd_Cod' => $Vnd_Cod);

        $array_kardex = array();
        $s_add = true;
        foreach ($items as $i => $item) {
            $item['Vet_Cod'] = $Vet_Cod;
            $item['Vet_Ite'] = $i + 1;
            /* Item Documento */
            $obBD_conIns->operacionobBD(86, $item, $obBD_conexionIns);

            /* Control de Inventarios */
            $s_add = true;
            foreach ($array_kardex as &$k) {
                if ($k['Pro_Cod'] == $item['Pro_Cod']) {
                    $s_add = false;
                    $k['Kar_Sal'] += (1) * $item['Vet_Can'];
                    $k['Kar_Ime'] += (1) * $item['Vet_Imp'];
                    $k['Kar_Pre'] = $k['Kar_Ime'] / $k['Kar_Sal'];
                    break;
                }
            }
            unset($k);
            if ($s_add == true && $Cal_Inv) {
                $kardexIE = array_merge($kardex, array(
                    'Kar_Int' => $i + 1,
                    'Iva_Cod' => $item['Iva_Cod'],
                    'Pro_Cod' => $item['Pro_Cod'],
                    'Kar_Sal' => (1) * $item['Vet_Can'] * (($documento['ticsri'] * 1 === 4) ? -1 : 1), //$obBD_conIns->CantidadStock($item['Pro_Cod'],$items),
                    'Kar_Pre' => $item['Vet_Pru'] * 1,
                    'Kar_Ime' => (1) * $item['Vet_Imp'] * (($documento['ticsri'] * 1 === 4) ? -1 : 1)
                    //'Kar_Rep'=>(in_array($item['Pro_Cod'],$cod_pro_unique)?true:false),
                    //'Kar_Max'=>$obBD_conIns->CantidadStock($item['Pro_Cod'],$items),
                ));
                array_push($array_kardex, $kardexIE);
            }
        }

        if ($documento['ticsri'] * 1 != 0 && $Cal_Inv) {
            foreach ($array_kardex as $k) {

                $obBD_conIns->updateStockProd($Ses_Suc_Cod, $k, true, $obBD_conexion, $obBD_conexionIns, $Bod_Cod);
            }
        }



        /* Creacion del comprobante contable */
        if ($configs['Cof_Con'] == 'S' && $documento['ticsri'] * 1 != 0) {
            $Com_Con = 'REG. VENTA ' . $Vet_Num;
            $Com_Fec = $Caj_Fec;
            $Tia_Asi = $obBD_con1->getRowConsulta(80, 7, $obBD_conexion);
            $meseCom = explode('-', $Com_Fec);
            $Com_Num = $obBD_con1->codigoComprAuto($Tia_Asi['Tia_Cod'], $Pec_Cod, $meseCom[1], $obBD_conexion); // Secuencia de comprobante por mes y por tipo
            $campo = 'Cli_Cod';
            /* Cabecera del Comprobante */
            $obBD_conIns->operacionobBD(70, $Pec_Cod . '*' . $Cli_Cod . '*' . $Com_Num . '*' . $Com_Fec . '*' . trim($Com_Con) . '*' . $Tia_Asi['Tia_Cod'] . '*' . $t_rubros . '*' . trim($Vet_Obs) . '*' . $campo, $obBD_conexionIns);
            $Com_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
            $obBD_conIns->operacionobBD(83, $Com_Cod . '*' . $Vet_Cod, $obBD_conexionIns); // relacion venta comprobante

            /* Inserta datos en el detalle del asiento (por items) */

            foreach ($items as &$item) {
                if ($documento['ticsri'] * 1 === 4) { //si nota de Credito
                    $cuenta = $obBD_con1->getRowConsulta(28, $Plan_Cod . '*' . ($Cal_Inv == true ? 'VDV' : 'DV'), $obBD_conexion);
                } else {                          //caso contrario nota de debito
                    $cuenta = $obBD_con1->getRowConsulta(84, $Plan_Cod . '*' . $item['Pro_Cod'] . '*' . 'V', $obBD_conexion);
                }
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del producto: <u>' . $item['Ite_Lar'] . '</u>!');
                $item['Pld_Cod'] = $cuenta['Pld_Cod'];
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . (($documento['ticsri'] * 1 === 4) ? 'D' : 'H') . '*' . ($item['Vet_Imp']) . '*' . $cuenta['Pld_Des'] . '*' . $item['Ite_Lar'] . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Item
            }
            unset($item);
            /* IVA */

            if ($t_iva * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(88, $Plan_Cod, $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Iva Cobrado</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . (($documento['ticsri'] * 1 === 4) ? 'D' : 'H') . '*' . $t_iva . '*' . 'IVA' . '*' . 'IVA' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Iva
            }
            /* DESCUENTO */

            if ($Vet_Des > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Plan_Cod . '*' . 'DV', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Descuentos en Ventas</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . (($documento['ticsri'] * 1 === 4) ? 'H' : 'D') . '*' . $t_descuento . '*' . 'DESCUENTO' . '*' . 'DESCUENTO' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // descuento
            }

            if ($t_ice * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Plan_Cod . '*' . 'ICV', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>ICE en Ventas</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . (($documento['ticsri'] * 1 === 4) ? 'D' : 'H') . '*' . $t_ice . '*' . 'ICE' . '*' . 'ICE' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE
            }

            /* Pago */
            /* pagos a las ventas asociadas */
            $t_pago = $obBD_con1->getRowConsultaSql("SELECT * FROM tipos_pago WHERE Pag_Des='NotaCredito' AND Pag_Est='A'", $obBD_conexion);
            $nota_valor = $t_rubros;
            foreach ($ventas as $venta) {
                if ($t_rubros < $venta['Vet_Saldo'] || $documento['ticsri'] * 1 === 5) {
                    $venta['Vet_Saldo'] = $t_rubros;
                }
                if ($t_rubros > 0) {
                    $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . (($documento['ticsri'] * 1 === 4) ? 'H' : 'D') . '*' . $venta['Vet_Saldo'] . '*' . $Vet_Num . '*' . ('Doc.' . $venta['Vet_Num']) . '*' . $Pld_Cod_Not, $obBD_conexionIns);
                    $t_rubros = $t_rubros - $venta['Vet_Saldo'];
                    if ($venta['For_Cod'] * 1 == 2 && $For_Cod_Nota['For_Cod'] * 1 == 2) {
                        $obBD_conIns->operacionobBD(113, $Com_Cod . '*' . $t_pago['Pag_Cod'] . '*' . $Caj_Fec . '*' . (($documento['ticsri'] * 1 === 4) ? $venta['Vet_Saldo'] : $venta['Vet_Saldo'] * -1) . '*' . '"Nota C/D"' . '*' . $venta['Cpc_Cod'], $obBD_conexionIns);
                        $Cpc_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
                    }
                }
            }
        }
    } catch (Exception $ex) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
        $responce['message'] = $ex->getMessage();
        echo json_encode($responce);
        exit();
    }

    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns);
    if ($obBD_conIns->Error == 0) {
        $reportes = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        //$obBD_con1->echoLog($reportes);
        $response = array(
            'success' => true,
            'Vet_Impr' => "" . (!empty($reportes[1]) ? "$reportes[1]?Vet_Cod=" : "") . "$Vet_Cod",
            'Vet_Cod' => $Vet_Cod,
            'Vet_Num' => $Vet_Num,
            'Vet_Fec' => $Caj_Fec,
            'Tic_Des' => ($documento['ticsri'] * 1 === 5 ? 'NOTA DE DEBITO' : 'NOTA DE CREDITO')
        );

        if ($Aut_Tem == 'E' && $input_autorizacion == '') {
            $rs_infoEmpresa = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
            $rs_infoCliente = $obBD_con1->getRowConsulta(61, $Aut_Cod, $obBD_conexion);
            if ($Tic_Sri == 4) {
                $responce['xml'] = $obBD_elect->createXmlNCredito($Vet_Cod, $Aut_Cod, $claveAcceso, $obBD_conexion);
            }
            if ($Tic_Sri == 5) {
                $responce['xml'] = $obBD_elect->createXmlNDebito($Vet_Cod, $Aut_Cod, $claveAcceso, $obBD_conexion);
            }
            $responce['Vet_Xmls'] = baseUrl("../FRONT/" . $Ses_Emp_Cod . '/' . $claveAcceso . '.xml');

            $meseVet = explode('-', $Caj_Fec);
            $datoElect = array('{Emp_Nom}' => $Ses_Emp_Nom, '{Tic_Des}' => $Tic_Des, '{Prs_Ced}' => $Prs_Ced, '{proveedor}' => $cliente, '{Prs_Cor}' => $Prs_Cor, '{claveAcceso}' => $claveAcceso, '{fecha}' => $meseVet[2] . ' de ' . mes($meseVet[1], 1) . ' ' . $meseVet[0], '{secuencia}' => $rs_infoEmpresa["Suc_Sri"] . '-' . $rs_infoCliente["Pun_Sri"] . '-' . str_pad($Vet_Num, 9, "0", STR_PAD_LEFT));
            //$responce['mail']=$obBD_con1->sendMailDoc($datoElect, reporteHtml($datoElect,'fac_pri_ret_ele.html'));
            //$responce['mail']=$obBD_elect->sendMailDoc($Vet_Cod,$Prs_Cor,NULL,$obBD_conexion);
        }

        if (!empty($Vet_Cod)) {
            $response['Vet_Data'] = array('Tic_Des' => ($documento['ticsri'] * 1 === 4 ? 'NOTA DE CREDITO' : 'NOTA DE DEBITO'), 'cliente' => $cliente, 'Vet_Num' => $Vet_Num, 'Vet_Fec' => $Caj_Fec, 'Vet_Aut' => $Aut_Sri);
            $response['Vet_Rows'] = $obBD_con1->getArrayConsulta(79, $Vet_Cod, $obBD_conexion);
            $response['Vet_Link'] = "" . (!empty($reportes[1]) ? "$reportes[1]?Vet_Cod=" : "") . "$Vet_Cod";
            if ($Aut_Tem == 'E'){
                $response['Xml_Path'] = ("../FRONT/" . $Ses_Emp_Cod . '/' . $claveAcceso . '.xml');
            }
        }
        if (!empty($Com_Cod)) {
            $response['Com_Data'] = array('Codigo' => $Com_Cod, 'Tia_Des' => $Tia_Asi['Tia_Des'], 'Com_Con' => $Vet_Obs, 'Com_Fec' => $Caj_Fec, 'Com_Val' => $t_rubros);
            $response['Com_Rows'] = $obBD_con1->getArrayConsulta(27, $Com_Cod, $obBD_conexion);
            $response['Com_Link'] = "" . (!empty($reportes[2]) ? "$reportes[2]?codigo=" : "") . "$Com_Cod";
        }
        if (isset($rets)) {
            $response['Ret_Cod'] = $Ret_Cod;
            $response['Ret_Data'] = array('Ret_Num' => $Ret_Num, 'Aut_Sri' => $Ret_Aut_Sri, 'Ret_Fec' => $Ret_Fec, 'Ren_Tot' => $Ren_Tot, 'Iva_Ren_Tot' => $Iva_Ren_Tot, 'Ret_Ren_Tot' => $Ret_Ren_Tot);
            $response['Ret_Rows'] = $rets;
        }
    } else {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_ins1->MsgError);
    }
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "NCredi.Vent. Registrar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/fac_val_factura.js?a=1"></script>

    <script>
        var Nota_CreDeb = true;
        inicializarDocVenta();
        //setTimeout(function(){ $("#Pec_Cod").trigger('change'); }, 1000);   
        var docs, items, pagos, doc_ventas, data = [],
            vet_num_ant = 0,
            tic_cod_ant = 0,
            Vet_Index = 1,
            Vet_Selected, index, Conf_Con = '<?php echo $configs['Cof_Con']; ?>';
        <?php $array_documentos = $obBD_con1->getArrayConsulta(107, $rs_Punto['Pun_Cod'], $obBD_conexion); ?>
        var Cof_Con = Conf_Con,
            array_documentos = <?php echo json_encode($array_documentos); ?>,
            ivas_venta = <?php echo json_encode($ivas) ?>;
    </script>
    <style>
        .ui-jqgrid td input,
        .ui-jqgrid td select,
        .ui-jqgrid td textarea {
            padding-top: 2px;
        }

        .footrow td[aria-describedby="documento_Vet_Imp"],
        .footrow td[aria-describedby="documento_Vet_Pru"] {
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

        .footerFact input[type=text] {
            padding: 0;
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

        #Ret_Asu {
            vertical-align: middle;
            margin-top: -2px;
            padding: 5px;
            -ms-transform: scale(1.4);
            -moz-transform: scale(1.4);
            -webkit-transform: scale(1.4);
            -o-transform: scale(1.4);
        }

        #resultContent .resp {
            font-weight: 700;
            font-size: 30px;
            color: #3f3fc1;
            padding: 0;
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 32px;
        }

        #resultContent .resp span:first-child {
            color: darkgoldenrod;
            width: 100px;
            display: inline-block;
            margin-left: 42px;
        }

        .msg_fly {
            font-size: 12px !important;
        }


        .ret .input-group-btn button {
            padding: 1px 2px !important;
        }

        .ret {
            padding: 0 !important;
        }
    </style>
</HEAD>

<BODY>


    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar Notas de Cr&eacute;dito/D&eacute;bito</h3>
            <p id="cabeceraPuntoImp" class="text-right col-xs-12  " style="margin-top:-15px;">punto de impresion</p>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="factura">
                <div class="row">
                    <div class="col-xs-12" id="panelVentas">
                        <div class="row">
                            <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaNotaCreDeb();">

                                <!--ivas-->
                                <select id="Def_Ivas" name="Def_Ivas" class="form-control input-xs" style="display: none;">
                                    <?php
                                    $temp = array();
                                    foreach ($ivas as $row) {
                                        if (!in_array($row['Iva_Por'], $temp)) {
                                            echo '<option value="' . $row['Iva_Cod'] . '" data-ivapor="' . $row['Iva_Por'] . '" data-ivaini=' . $row['Iva_Ini'] . ' data-ivafin=' . $row['Iva_Fin'] . ' >' . $row['Iva_Por'] . ' %</option>';
                                        }
                                        array_push($temp, $row['Iva_Por']);
                                    }

                                    ?>
                                </select>

                                <!--tipos_pago-->
                                <select id="pag_cod" name="pag_cod" class="form-control input-xs" style="display: none;">
                                    <?php foreach ($tipospago as $row) { ?><option value="<?php echo $row['Pag_Cod']; ?>" data-forcod="<?php echo $row['For_Cod']; ?>"><?php echo mb_convert_encoding($row['Pag_Des'], 'ISO-8859-1', 'UTF-8'); ?></option><?php } ?>
                                </select>

                                <!--bancos-->
                                <select id="bak_cod" name="bak_cod" class="form-control input-xs" style="display: none;">
                                    <?php foreach ($bankos as $row) { ?><option value="<?php echo $row['Bak_Cod']; ?>"><?php echo mb_convert_encoding($row['Bak_Des'], 'ISO-8859-1', 'UTF-8'); ?></option><?php } ?>
                                </select>

                                <!--cuentas contado=1, credito=2-->
                                <select id="pld_cod" name="pld_cod" class="form-control input-xs" style="display: none;"></select>

                                <div class="col-md-5 col-xs-12">
                                    <fieldset class="exa-fieldset" id="clieFormTemp">
                                        <legend class="Titulos2">Datos del Cliente</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">C&eacute;dula/RUC:</label>
                                            <div class="col-xs-7">
                                                <input name="Prs_Cod" type="text" style="display:none;" />
                                                <input name="Prs_Cor" type="text" style="display:none;" />
                                                <input name="Cli_Cod" type="text" style="display:none;" />
                                                <input name="op_opciones" type="text" value="c" style="display: none;">
                                                <div class="input-group input-group-xs">
                                                    <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#clieDialog',selectCliente);" type="text" placeholder="Ingrese Cliente..." class="form-control input-xs datatrigger clearable dialogSearch" tabindex="1" required="" />
                                                    <span class="input-group-btn">
                                                        <button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                        <button type="button" onclick="$('#clieCreateForm').setData({}).find('.validate').find('i').removeAttr('class'); $('#clieCreateDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Cliente" tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Cliente:</label>
                                            <div class="col-xs-10"><span name="cliente" class="form-control input-xs databind datatitle"></span></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Direcci&oacute;n:</label>
                                            <div class="col-xs-4"><span name="Prs_Dir" type="text" class="form-control input-xs databind datatitle"></span></div>
                                            <label class="col-xs-1 control-label label-xs">Correo:</label>
                                            <div class="col-xs-5"><span name="Prs_Cor" type="text" class="form-control input-xs databind datatitle"></span></div>
                                        </div>

                                    </fieldset>


                                </div>
                                <div class="col-md-7 col-xs-12">
                                    <fieldset class="exa-fieldset" id="docuFormTemp">
                                        <legend class="Titulos2">Datos del Documento</legend>
                                        <input type="text" name="Vet_Cod" style="display: none;" />
                                        <input type="text" name="Com_Cod" style="display: none;" />
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Periodo:</label>
                                            <div class="col-xs-2">
                                                <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs">
                                                    <?php $rs_perio = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
                                                    foreach ($rs_perio as $row) { ?>
                                                        <option value="<?php echo $row['Pec_Cod']; ?>" data-inicio="<?php echo $row['Pec_Fei']; ?>" data-fin="<?php echo $row['Pec_Fef']; ?>" data-PlaCod="<?php echo $row['Pla_Cod']; ?>"><?php echo $row['Anio']; ?></option>
                                                    <?php   } ?>
                                                </select>
                                            </div>
                                            <label class="col-xs-1 control-label label-xs">Fecha:</label>
                                            <div class="col-xs-3">
                                                <input type="text" id="Caj_Fec" name="Caj_Fec" class="form-control input-xs datepickers">
                                            </div>
                                            <label class="col-xs-1 control-label label-xs">Ciudad:</label>
                                            <div class="col-xs-3">
                                                <?php $Ciu_Des = $obBD_con1->getRowConsulta(6, $Ses_Usu_Cod, $obBD_conexion); ?>
                                                <input type="hidden" id="Ciu_Cod" name="Ciu_Cod" value="<?php echo $Ciu_Des['Ciu_Cod'] ?>">
                                                <span name="Ciu_Des" class="form-control input-xs"><?php echo $Ciu_Des['Ciu_Des'] ?></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Docum.:</label>
                                            <div class="col-xs-6">
                                                <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs" required=""></select>
                                            </div>

                                            <label class="col-xs-1 control-label label-xs">Aut.:</label>
                                            <div class="col-xs-3">
                                                <div class="col-xs-12 input-group input-group-xs">
                                                    <span id="Aut_Sri" name="Aut_Sri" class="form-control input-xs databind"></span>
                                                    <span id="cambiarAut" class="btn btn-block btn-success input-group-addon " title="Cambiar de Autorizacion">
                                                        <i class="glyphicon glyphicon-transfer white"></i>
                                                    </span>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">N&uacute;mero:</label>
                                            <div class="col-xs-5">
                                                <div class="input-group input-group-xs">
                                                    <span id="Pun_Sri" name="Pun_Sri" class="input-group-addon alert-info"></span>
                                                    <input type="text" id="Vet_Num" name="Vet_Num" onchange="validarTic_Cod()" class="form-control input-xs trigger" tabindex="5" required="" data-container="body" data-toggle="popover" />
                                                    <span class="input-group-addon validate"><i></i></span>
                                                </div>
                                            </div>
                                            <label id="mensajeAutorizacion" class="col-xs-5 control-label label-xs  red"></label>

                                            <div class="col-xs-5">
                                                <div class="addAutorizacion">
                                                    <div class="input-group">
                                                        <span class="input-group-btn"><button type=button id="btnAddAut" title="Autorizacion Externa" class="btn-xs btn btn-warning"><span class="glyphicon glyphicon-alert"></span></button></span>
                                                        <input maxlength="55" minlength="49" title="minimo 49 caracteres" name="input_autorizacion" size="" class="form-control input-xs" />
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </fieldset>
                                </div>
                                <div class="col-md-7 col-xs-12">
                                    <fieldset class="exa-fieldset" id="docuFormTemp">
                                        <legend class="Titulos2">Ventas Asociadas</legend>
                                        <table id="ventas"></table>
                                        <div id="ventas1Pager"></div>
                                    </fieldset>
                                </div>
                                <div class="col-md-5 col-xs-12">
                                    <fieldset class="exa-fieldset" id="docuFormTemp">
                                        <legend class="Titulos2">Pago</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Forma:</label>
                                            <div class="col-xs-3">
                                                <select id="For_Cod_Nota" name="For_Cod_Nota" class="form-control input-xs readOnly" disabled="disabled" data-trigger="" required="">
                                                    <option value="">Seleccione...</option>
                                                </select>
                                            </div>
                                            <?php if ($configs['Cof_Con'] == 'S') { ?>
                                                <label class="col-xs-2 control-label label-xs required">Cuenta:</label>
                                                <div class="col-xs-5">
                                                    <select id="Pag_Pld_Nota" name="Pag_Pld_Nota" class="form-control input-xs readOnly" required="" disabled="disabled"></select>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="form-group pagoCredito hidden">
                                            <label class="col-xs-2 control-label label-xs required">Vencimiento:</label>
                                            <div class="col-xs-3">
                                                <input id="Cpp_Ven" name="Cpp_Ven" type="text" class="form-control input-xs datepickers" />
                                            </div>
                                            <label class="col-xs-2 control-label label-xs">Observaci&oacute;n:</label>
                                            <div class="col-xs-5">
                                                <textarea name="Cpp_Obs" class="form-control input-xs"></textarea>
                                            </div>
                                        </div>
                                    </fieldset>

                                    <?php $bodegas = $obBD_con1->getArrayConsulta('bodega.1', array('Suc_Cod' => $Ses_Suc_Cod, 'Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion); ?>

                                    <fieldset class="exa-fieldset" <?php if (count($bodegas) == 0) echo 'style="display:none; "'; ?>>
                                        <legend class="Titulos2"></legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Bodega:</label>
                                            <div class="col-xs-10">
                                                <select name="Bod_Cod" class="form-control input-xs">
                                                    <?php if (count($bodegas) > 0) foreach ($bodegas as $row) {
                                                        echo "<option value='$row[Bod_Cod]'>$row[Bod_Nom]</option>";
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </fieldset>

                                </div>
                            </form>
                            <div class="col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
                                <table id="items"></table>
                                <div id="itemsPager"></div>
                            </div>
                            <div class="col-md-7 col-xs-12 hidden">
                                <form id="reteFormTemp" action="javascript:" class="formDatos form-horizontal normal">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Datos de la Retenci&oacute;n</legend>
                                        <input type="text" name="Ret_Cod" style="display: none;" id="Ret_Cod" />
                                        <input type="text" name="Ret_Xml" style="display: none;" />
                                        <input type="text" name="Aut_Cod" style="display: none;" id="Aut_Cod_Old" />
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs ">N&uacute;mero:</label>
                                            <div class="col-xs-4">
                                                <input type="text" name="Aut_Tem" style="display: none;" />
                                                <div class="input-group input-group-xs">
                                                    <input id="Ret_Num" name="Ret_Num" type="text" class="form-control input-xs ret_field" />
                                                    <span class="input-group-addon validate"><i></i></span>
                                                </div>
                                            </div>


                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs ">Autoriza:</label>
                                            <div class="col-xs-4">
                                                <input name="Ret_Aut_Sri" class="form-control input-xs ret_field" />
                                            </div>
                                            <label class="col-xs-2 control-label label-xs required">Fecha:</label>
                                            <div class="col-xs-4">
                                                <div class="input-group">
                                                    <input id="Ret_Fec" name="Ret_Fec" type="text" class="form-control input-xs readOnly ret_field datepickers" required="" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                                                    <span class="input-group-addon input-xs" title="Fecha de la Retenci&oacuten"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group reteTot cod_banano" style="display:none;">
                                            <label class="col-xs-2 control-label label-xs required">Banano:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <span class="input-group-addon bold alert-warning">&nbsp;Cod. 338&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i>&nbsp;</span>
                                                    <span class="input-group-addon bold alert-success" title="Cajas de Banano">Cajas:</span>
                                                    <input name="Ret_Uca" type="text" class="form-control span" style="text-align: right;" pattern="\d*" placeholder="0" />
                                                    <span class="input-group-addon bold alert-success" title="Precio Unitario por Caja">P.Unit.:</span>
                                                    <input name="Ret_Pca" type="text" class="form-control span" style="text-align: right;" pattern="\d*" placeholder="0.00" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group reteTot">
                                            <label class="col-xs-2 control-label label-xs"></label>
                                            <div class="col-xs-10">
                                                <div class="input-group input-group-xs">
                                                    <span class="input-group-addon bold alert-info">Renta:</span>
                                                    <input name="Ret_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                                                    <span class="input-group-addon bold alert-info">+&nbsp;IVA:</span>
                                                    <input name="Iva_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                                                    <span class="input-group-addon bold alert-info">=&nbsp;Retenido:</span>
                                                    <input id="Ren_Tot" name="Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                                                    <span class="input-group-btn">
                                                        <button type="button" onclick="$('#retDetaDialog').dialog('open')" class="btn btn-info" title="Ver Detalle Retenci&oacuten" tabindex="-1"><span class="glyphicon glyphicon-eye-open"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group reteTot">
                                            <label class="col-xs-5 control-label label-xs"></label>
                                            <div class="col-xs-7">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-addon bold alert-warning">A Pagar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i></span>
                                                    <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                                                    <input id="Val_Pcc" name="Val_Pcc" type="text" class="form-control bold span" style="text-align: right;font-size: 15px; background-color: white;" readonly="" />
                                                    <span id="infoLiquida" class="input-group-addon validate" style="display:none;"><i></i></span>

                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                </form>
                            </div>

                            <div class="col-md-5 col-xs-12">
                                <form id="pagoFormTemp" action="javascript:" class="formDatos form-horizontal normal hidden">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Forma de Pago</legend>
                                        <input type="text" name="Cpc_Cod" style="display: none;" />
                                        <div class="form-group pagoSri">
                                            <label class="col-xs-3 control-label label-xs required">Pago&nbsp;SRI:</label>
                                            <div class="col-xs-9">
                                                <?php $rs_pag_sri = $obBD_con1->getArrayConsulta(45, '', $obBD_conexion); ?>
                                                <select id="Tpc_Cod" name="Tpc_Cod" defaultValue=1 class="form-control input-xs readOnly" required="" onchange="">
                                                    <option value="">Seleccione...</option>
                                                    <?php foreach ($rs_pag_sri as $row) {
                                                        $selected = '';
                                                        if ($row['Tpc_Sri'] == 1) {
                                                            $selected = 'Selected';
                                                        }
                                                        echo "<option value='$row[Tpc_Cod]' " . $selected . "  >$row[Tpc_Sri] - $row[Tpc_Des]</option>";
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group porCobrar">
                                            <label class="col-xs-3 control-label label-xs"></label>
                                            <div class="col-xs-9">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-addon bold alert-warning">Por Cobrar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i></span>
                                                    <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                                                    <input id="Val_Pcc_2" name="Val_Pcc_2" type="text" class="form-control bold span" style="text-align: right;font-size: 15px; background-color: white;" readonly="" tabindex="-1">
                                                </div>
                                            </div>
                                        </div>

                                    </fieldset>
                                </form>
                            </div>
                        </div>
                        <div class="row center-block">
                            <div class="col-md-7 col-xs-12">
                                <div class="condensed hidden" style="min-height: 100px; padding-bottom: 5px;">
                                    <table id="pagos"></table>
                                    <div id="pagosPager"></div>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 Titulos2">
                        <hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
                    </div>
                </div>
            </div>
            <div id="documentoResult" class="form-horizontal normal" style="display: none;">
                <div class="row">
                    <div class="col-xs-6" id="resultContent">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Resultado De la Transacci&oacute;n</legend>
                            <div>
                                <h4 style="text-align: center; font-weight: 900;">El Documento se guardo con &Eacute;xito!</h4>
                                <p class="form-control-static resp" data-name="Tic_Des"></p>
                                <p class="resp"><span>&raquo;Fec:</span><span style="color:coral;" class="databind" data-name="Vet_Fec"></span></p>
                                <p class="resp"><span>&raquo;Num:</span><span style="color:teal;" class="databind" data-name="Vet_Num"></span></p>
                                <p class="resp"><span>&raquo;Cod:</span><span style="color: #CE0000;" class="databind" data-name="Vet_Cod"></span></p>
                                <div style="padding-top: 15px; text-align: center;">
                                    <button class="btn btn-sm btn-success" onclick="clearDocument();$('#documentoResult').moveComp('#factura').updateGridsSizes();"><i class="glyphicon glyphicon-file"></i> Nuevo Documento</button>
                                    <button class="btn btn-sm btn-success" name="Vet_Impr" id="Vet_Impr" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Documento</button>
                                    <!--Metodo para imprimir pdf de nota de credito-->
                                    <button id="frm_pdf_btn" type="button" class="btn btn-sm btn-primary" onclick="window.open($('#frm_pdf_btn').data('url'), '_blank')"><i class="glyphicon glyphicon-file"></i> <span>Pdf</span> </button>
                                    <input name="urlXml" id="urlXml" type="hidden" value="" alt="">
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-xs-6" id="copForm">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del Documento</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Documento:</label>
                                <div class="col-xs-5"><span name="Tic_Des" type="text" class="form-control input-xs "></span></div>
                                <label class="col-xs-1 control-label label-xs">Fecha:</label>
                                <div class="col-xs-3"><span name="Vet_Fec" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">N&uacute;mero:</label>
                                <div class="col-xs-4"><span name="Vet_Num" type="text" class="form-control input-xs "></span></div>
                                <label class="col-xs-2 control-label label-xs">Autorizaci&oacute;n:</label>
                                <div class="col-xs-3"><span name="Vet_Aut" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Cliente:</label>
                                <div class="col-xs-9"><span name="cliente" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <table id="copresult"></table>
                        </fieldset>
                        <script>

                        </script>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset" id="retForm">
                            <legend class="Titulos2">Datos de la Retenci&oacute;n</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">N&uacute;mero:</label>
                                <div class="col-xs-4"><span name="Ret_Num" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Autoriza:</label>
                                <div class="col-xs-4"><span name="Aut_Sri" class="form-control input-xs"></span></div>

                                <label class="col-xs-2 control-label label-xs">Fecha:</label>
                                <div class="col-xs-4"><span name="Ret_Fec" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group reteTot">
                                <label class="col-xs-2 control-label label-xs"></label>
                                <div class="col-xs-10">
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon bold">Renta:</span>
                                        <input name="Ret_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                                        <span class="input-group-addon bold">+&nbsp;IVA:</span>
                                        <input name="Iva_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                                        <span class="input-group-addon bold">=&nbsp;Retenido:</span>
                                        <input name="Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                                    </div>
                                </div>
                            </div>
                            <table id="reteresult"></table>
                        </fieldset>
                    </div>
                    <?php if ($configs['Cof_Con'] == 'S') { ?>
                        <div class="col-xs-6" id="compForm">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Comprobante</legend>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">C&oacute;d. Comp.:</label>
                                    <div class="col-xs-3"><span name="Codigo" type="text" class="form-control input-xs "></span></div>
                                    <label class="col-xs-3 control-label label-xs">Fecha:</label>
                                    <div class="col-xs-3"><span name="Com_Fec" type="text" class="form-control input-xs "></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Asiento:</label>
                                    <div class="col-xs-4"><span name="Tia_Des" type="text" class="form-control input-xs "></span></div>
                                    <label class="col-xs-2 control-label label-xs">Valor:</label>
                                    <div class="col-xs-3"><span name="Com_Val" type="text" class="form-control input-xs "></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Observaci&oacute;n:</label>
                                    <div class="col-xs-9"><span name="Com_Con" type="text" class="form-control input-xs "></span></div>
                                </div>
                                <table id="asiento"></table>
                                </fiedset>
                                <script>
                                    $('#asiento').createGrid({
                                        height: 75,
                                        postData: {
                                            CheListAjax: true
                                        },
                                        caption: 'Asiento Contable <button id="btnComPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>',
                                        rowNum: 10000,
                                        footerrow: true,
                                        userDataOnFooter: true,
                                        colModel: [{
                                                label: 'C&oacute;d.Int.',
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
                                                label: 'C&oacute;digo',
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
                                        loadComplete: function() {
                                            $(this).setGridSummary(['Debe', 'Haber'], {
                                                Glosa: "<div style='text-align:right;'>TOTALES:</div>"
                                            });
                                        }
                                    }, true);
                                    $.clearFooterDiario("#asiento");
                                </script>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Inicio del di�logo para buscar clientes -->
    <div id="clieDialog" title="B&uacute;squeda de Cliente">
        <form class="form-horizontal normal"> </form>
    </div>
    <script>
        //Dialog buscar clientes
        $.createSearchDialog('clieDialog', [{
                label: 'C&oacute;d.Int.',
                name: 'Cli_Cod',
                key: true,
                width: 15,
                align: "center",
                hidden: true
            },
            {
                label: 'C&eacute;dula/RUC',
                name: 'Prs_Ced',
                width: 50
            },
            {
                label: 'Cliente',
                name: 'cliente',
                width: 100
            },
            {
                label: 'Direcc.',
                name: 'Prs_Dir',
                width: 60
            },
            {
                label: '&nbsp;',
                name: 'act1',
                width: 20,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: selectCliente
                }
            }
        ], null, null, null, {
            headertitles: true
        }, {
            title: 'Cliente',
            text: 'Prs_Ced'
        });

        function selectCliente(cliente) {
            $('#clieFormTemp').setData($.extend(cliente, {
                op_opciones: 'c'
            }));
            $('#clieDialog').dialog('close');
        }
    </script>

    <!-- Inicio del di�logo para registrar clientes -->
    <div id="clieCreateDialog" title="Registrar Cliente" style="display:none;">
        <form class="form-horizontal normal" id="clieCreateForm" action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaCliente(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos del Cliente</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">C&eacute;dula/RUC:</label>
                    <div class="col-xs-5">
                        <div class="input-group input-group-xs">
                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="if(validaNoIdentif(this.value)['success']){ $('#Ide_Cod').val(this.value.length===10?2:1); $('#Cli_Tic').val(validaNoIdentif(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchCliente(this.value); }else{ $('#Ide_Cod').val(''); $('#Cli_Tic').val(''); $(this).fieldValid(false,validaNoIdentif(this.value)['message']); };" required="" />
                            <span class="input-group-addon validate"><i></i></span>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="checkbox check-big" style="position:absolute;">
                            <label><input type="checkbox" name="Cli_Con" value="S" offval="N">Obligado Contab.</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Documento:</label>
                    <div class="col-xs-5">
                        <?php $rs_identi = $obBD_con1->getArrayConsulta(29, '', $obBD_conexion); ?>
                        <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                            <option value=""></option>
                            <?php foreach ($rs_identi as $row) {
                                echo "<option value='$row[Ide_Cod]'>$row[Ide_Des]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Contribuyente:</label>
                    <div class="col-xs-4">
                        <select id="Cli_Tic" name="Cli_Tic" class="form-control input-xs" required="" onchange="if(this.value==='N'){ $('.juridico').hide();$('.natural').show(); }else{ $('.natural').hide();$('.juridico').show(); }">
                            <option value="N">NATURAL</option>
                            <option value="J">JURIDICO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Raz&oacute;n Social:</span></label>
                    <div class="col-xs-9"><input name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs">Nombres:</label>
                    <div class="col-xs-9"><input name="Prs_Nom" type="text" class="form-control input-xs" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs required">G&eacute;nero:</label>
                    <div class="col-xs-4">
                        <select name="Prs_Sex" class="form-control input-xs">
                            <option value="M">MASCULINO</option>
                            <option value="F">FEMENINO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Nomb.Comerc.:</label>
                    <div class="col-xs-9"><input name="Cli_Fac" type="text" class="form-control input-xs" /></div>
                </div>
            </fieldset>
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos de Ubicaci&oacute;n</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                    <div class="col-xs-4">
                        <?php $rs_ciudad = $obBD_con1->getArrayConsulta(81, '', $obBD_conexion); ?>
                        <select name="Ciu_Cod" class="form-control input-xs" required="">
                            <option value=""></option>
                            <?php foreach ($rs_ciudad as $row) {
                                echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Direcci&oacute;n:</label>
                    <div class="col-xs-9"><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Tel&eacute;fono:</label>
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

    <script>
        // DIALOG create cliente
        $('#clieCreateDialog').createDialog({
            icon: 'plus',
            width: 500,
            height: 430
        });
        $('#For_Cod').val(1).trigger('change');
    </script>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
    <div id="codiDialog" title="B&uacute;squeda de C&oacute;digos Retenci&oacute;n">
        <form class="form-horizontal normal"><input type="text" name="Pla_Cod" class="placod" style="display: none;" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                    <div class="col-xs-7 radioset">
                        <input id="radc3" name="op_opciones" type="radio" value="p" onclick="setfocus(this.form.search)" alt="" data-trigger="" /><label for="radc3">&nbsp;&nbsp;Porcentaje %&nbsp;&nbsp;</label>
                        <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                        <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>
                    </div>
                    <div class="col-xs-3" style="text-align: right;">
                        <input type="text" name="tipo" class="hidden" />
                        <input type="text" name="index" class="hidden" />
                        <div class="checkbox check-big">
                            <label><input name="checkRentaIva" type="checkbox" value="S" offval="N">Aplicar a Todos</label>
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
    <script>
        $.createSearchDialog('codiDialog', [{
                label: 'C&oacuted.Int.',
                name: 'Ren_Cod',
                key: true,
                width: 25,
                align: "center"
            },
            {
                label: 'C&oacutedigo',
                name: 'Ren_Sri',
                width: 25,
                align: "center"
            },
            {
                label: 'Descripci&oacuten',
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
                label: '&nbsp;',
                name: 'act1',
                width: 20,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: agregaRetencion,
                    conditional: function(o) {
                        return !(Cof_Con === 'S' && (!$.varValid(o['Pld_Cod']) || o['Pld_Cod'] === ''));
                    },
                    caseFalse: function() {
                        return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>';
                    }
                }
            }
        ], null, null, null, null, {
            title: 'B&uacutesqueda',
            options: []
        });
    </script>


    <!-- Inicio de di�logo para buscar un producto -->
    <div id="proDialog" title="B&uacute;squeda de Productos">
        <form class="form-horizontal normal">
            <input type="text" name="Pla_Cod" class="placod" style="display: none;" />
        </form>
    </div>
    <script>
        // Dialog para buscar productos
        $.createSearchDialog('proDialog', [{
                label: 'C&oacute;d.Int.',
                name: 'Pro_Cod',
                key: true,
                width: 20,
                align: "center",
                hidden: false
            },
            {
                label: 'Descripci&oacute;n',
                name: 'Ite_Lar',
                width: 110
            },
            {
                label: 'Marca',
                name: 'Mar_Des',
                width: 40
            },
            {
                label: 'Categoria',
                name: 'Cat_Des',
                width: 90,
                align: "center"
            },
            {
                label: 'IVA',
                name: 'Iva_Por',
                width: 20,
                align: "center",
                formatter: 'truefalse',
                formatoptions: {
                    yesMsg: 'Grava IVA',
                    noMsg: 'No Grava IVA'
                },
                title: false
            },
            {
                label: 'Adq.',
                name: 'Adq_Cor',
                width: 20,
                align: "center",
                formatter: 'title',
                formatoptions: {
                    title: function(o) {
                        return o['Adq_Des'];
                    }
                }
            },
            {
                label: '&nbsp;',
                name: 'act1',
                width: 20,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: selectItem,
                    conditional: function(o) {
                        return !(Cof_Con === 'S' && (!$.varValid(o['Pld_Cod']) || o['Pld_Cod'] === ''));
                    },
                    caseFalse: function() {
                        return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>';
                    }
                }
            }
        ], null, null, null, null, {
            title: 'Producto',
            options: [{
                label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',
                value: 'd'
            }, {
                label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',
                value: 'c'
            }]
        });
    </script>

    <div id="autorizaDialog" title="B&uacute;squeda de Autorizaciones">
        <form class="form-horizontal normal" id="autorizaForm">
            <input type="text" name="Tic_Cod" class="hidden" />
            <input type="text" name="Pun_Cod" class="hidden" />
        </form>
    </div>

    <div id="ventasDialog" title="B&uacute;squeda de Ventas">
        <form class="form-horizontal normal" id="ventaForm">
            <input type="text" id="Forma_Cod" name="Forma_Cod" class="hidden" />
            <input type="text" id="Caja_Fecha" name="Caja_Fecha" class="hidden" />
        </form>
    </div>

    <!-- DIALOGO DETALLE RETENCION -->
    <div id="retDetaDialog" title="Retenci&oacute;n">
        <div class="condensed-header">
            <table id="retencion"></table>
        </div>
    </div>
    <script>
        var opts = {
            height: 75,
            caption: 'Detalle Retenci&oacute;n',
            sortable: true,
            sortname: 'Ren_Rete',
            sortorder: "desc",
            footerrow: true,
            colModel: [{
                    label: 'C&oacute;d.Int.',
                    name: 'Ren_Cod',
                    key: true,
                    width: 15,
                    align: "center",
                    hidden: true
                },
                {
                    label: 'C&oacute;d.Int.',
                    name: 'Ren_Ret',
                    width: 15,
                    align: "center",
                    hidden: true
                },
                {
                    label: 'Ret.',
                    name: 'Ren_Rete',
                    width: 15,
                    align: 'center'
                },
                {
                    label: 'C&oacute;digo ',
                    name: 'Ren_Sri',
                    width: 15,
                    align: 'center'
                },
                {
                    label: 'Descripci&oacute;n ',
                    name: 'Ren_Con',
                    width: 50
                },
                {
                    label: 'Importe',
                    name: 'Ren_Imp',
                    width: 30,
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
                    label: 'Porc.(%)',
                    name: 'Ren_Por',
                    width: 20,
                    align: 'right'
                },
                {
                    label: 'Retenci&oacute;n.',
                    name: 'Ren_Val',
                    width: 30,
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
            loadComplete: function() {
                $(this).setGridSummary(['Ren_Val'], {
                    Ren_Por: "<div style='text-align:right;'>TOTAL:</div>"
                });
            }
        };
        $('#reteresult').createGrid($.extend(opts, {
            caption: 'Detalle Retenci&oacute;n <button id="btnRetPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right hidden" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button><button id="btnRetXml" onclick="window.open($(this).data(\'url\'));" class="btn btn-success btn-xs pull-right" style="margin-top: -2px; display:none; margin-right:2px; "><i class="glyphicon glyphicon-download-alt"></i> Descargar XML</button>'
        }), true);
        $('#reteresult').getFootRow(true);
        $('#retencion').createGrid($.extend(opts, {
            height: 219,
            width: 593,
            responsive: false,
            caption: 'Detalle Retenci&oacute;n <button type="button" role="button" tabindex="-1" class="ui-button ui-widget ui-state-default ui-corner-all pull-right" title="Cerrar Ventana" onclick="$(\'#retDetaDialog\').dialog(\'close\')"><span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span></button>'
        }), true);
        $('#retencion').getFootRow(true);
        $('#detaRete').createGrid($.extend(opts, {
            height: 'auto',
            width: 550,
            responsive: false,
            caption: null,
            rownumbers: false
        }), true);
        $('#detaRete').getFootRow(true);
        $('#retDetaDialog').createDialog({
            height: 293,
            width: 600,
            noTitleStuff: false,
            noBorder: true,
            noOverflow: true,
            extraClass: 'noMargin'
        });
        $('#docDetaDialog').createDialog({
            height: 400,
            width: 600,
            noTitleStuff: false,
            noBorder: true
        });
    </script>

    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <script>
        $('#Pec_Cod').trigger('change');
        $.clearValidate();
    </script>
    <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</BODY>

</HTML>