<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factu.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Factu($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Factu;

$hoy = date("Y-m-d");
$mes = date("m");
$rs_infoEmpresa = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
//Verificar este metodo
//$data_negociaciones = $obBD_con1->getArrayConsulta(1006,  $Ses_Emp_Cod, $obBD_conexion);
//var_dump($data_negociaciones);
if (isset($loadElectronico)) {
    require_once('../../Librerias/Xml/XML.php');
    $data = $_POST;
    $clave = trim($clave);
    $r = array('success' => true, 'message' => 'Documento Cargado con Exito!', 'data' => array(), 'items' => array());
    $r['data']['idCargaExitosa'] = $_SESSION['idCargar'];
    try {
        $tot = isset($_FILES["file"]) ? count($_FILES["file"]["name"]) : 0;
        if (empty($clave) && ($tot == 0 || empty($_FILES["file"]["name"]))) throw new Exception('Debe Ingresar un <u>XML</u> o una <u>Clave de Acceso</u>!');

        if ($tot > 0 && (!empty($_FILES["file"]["name"]))) {
            $imageFileType = strtoupper(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));
            if ($_FILES["file"]["size"] > 4000000) throw new Exception('Archivo demasiado grande!');
            if ($imageFileType != "XML") throw new Exception('Formato no Permitido, solo XML!');
            $xml_aut = XmlDoc::createFromFile($_FILES["file"]["tmp_name"]);
            $clave = null;
        }

        //Nuevo codigo para hacer la carga masiva con .zip
        $ruta_xml = "$Ses_Emp_Cod/load_masiva/" . $clave . ".xml";
        if (file_exists($ruta_xml)) {
            $xml_aut = XmlDoc::createFromFile($ruta_xml);
            $clave = null;
        }

        if (!empty($clave)) {
            require_once('../../Librerias/FactElect/FirmaElectronica.php');
            $DocElect = new FirmaElectronica();
            $res = $DocElect->autorizarSri($clave);
            if ($res['success'] == true) {
                $xml_aut = new XmlDoc((!mb_detect_encoding($res['xml'], 'UTF-8', true)) ? utf8_encode($res['xml']) : $res['xml']);
                //var_dump((string)$xml_aut->comprobante);
            } else throw new Exception($res['message']);
        }
        $data_xml = (string)$xml_aut->comprobante;
        $xml = new XmlDoc((!mb_detect_encoding($data_xml, 'UTF-8', true)) ? utf8_encode($data_xml) : $data_xml);
        if ($xml->getName() != 'factura') throw new Exception('El documento electronico no es una <u>Factura</u>!');
        $fact = $xml->infoTributaria;
        $factI = $xml->infoFactura;
        $items = $xml->detalles->detalle;
        $fecha = explode('/', $factI->fechaEmision->text());
        $r['data']['Prs_Ced'] = $fact->ruc->text();
        $r['data']['proveedor'] = $r['data']['Prs_Ape'] = $fact->razonSocial->text();
        $r['data']['Prv_Com'] = $fact->nombreComercial->text();
        $r['data']['Prs_Dir'] = $fact->dirMatriz->text();
        $r['data']['Tic_Cod'] = $fact->codDoc->text() * 1;
        $r['data']['Cop_Aut'] = $xml_aut->numeroAutorizacion->text();
        $r['data']['Cop_Num'] = $fact->estab . '-' . $fact->ptoEmi . '-' . $fact->secuencial;
        $r['data']['Cop_Fec'] = $r['data']['Cop_Imf'] = $r['data']['Cop_Cad'] = $r['data']['Com_Fec'] = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];
        $r['data']['propina'] = isset($factI->propina) ? $factI->propina->text() : '0.00'; // nuevo campo
        $impu = $factI->totalConImpuestos->totalImpuesto;
        //IRBPNR
        $r['data']['Cop_Irb'] = 0.00;
        for ($x1 = 0; $x1 < count($impu); $x1++) {
            if ($impu[$x1]->codigo->text() == '5')
                $r['data']['Cop_Irb'] += ("0" . $impu[$x1]->valor->text()) * 1;
        }

        // Separar totales de tarifa 0 y no objeto IVA
        $r['data']['t_iva0'] = 0;
        $r['data']['t_noiva'] = 0;
        for ($x1 = 0; $x1 < count($impu); $x1++) {
            if ($impu[$x1]->codigo->text() == '2') {
                // Tarifa 0
                if ($impu[$x1]->codigoPorcentaje->text() == '0') {
                    $r['data']['t_iva0'] += $impu[$x1]->baseImponible->text() * 1;
                }
                // No objeto IVA (solo sumar si codigoPorcentaje == '6')
                if ($impu[$x1]->codigoPorcentaje->text() == '6') {
                    $r['data']['t_noiva'] += $impu[$x1]->baseImponible->text() * 1;
                }
            }
        }

        //$agrupa='S';
        if (isset($agrupa) && $agrupa == 'S') {
            $ice = 0;
            for ($x1 = 0; $x1 < count($impu); $x1++) {
                if ($impu[$x1]->codigo->text() == '3') {
                    $ice += ($impu[$x1]->valor->text() * 1);
                }
            }
            //$Cop_Dec = ($factI->totalDescuento->text() * 1 > 0) ? $factI->totalDescuento->text() * 100 / ($factI->totalSinImpuestos->text() * 1 + $factI->totalDescuento->text()) : 0;
            $Cop_Decv = $factI->totalDescuento->text() * 1;
            for ($x1 = 0; $x1 < count($impu); $x1++) {
                if ($impu[$x1]->codigo->text() == '2' && $impu[$x1]->baseImponible->text() * 1 > 0) {
                    $iva = ($impu[$x1]->codigoPorcentaje->text() == '2' || $impu[$x1]->codigoPorcentaje->text() == '3');
                    // version antes de la separacion de No Objeto Iva
                    // array_push($r['items'], array(
                    //     'Cop_Can' => 1,
                    //     'Ite_Lar' => 'Producto Tarifa ' . $impu[$x1]->tarifa . '%',
                    //     'Cop_Dec' => $Cop_Dec,
                    //     'Cop_Pru' => ($impu[$x1]->baseImponible->text() * 1) - ($iva ? $ice : 0) + ($factI->totalDescuento->text() * 1 > 0 ? $factI->totalDescuento->text() * 1 : 0),
                    //     'Cop_Imp' => $impu[$x1]->baseImponible->text() * 1 - ($iva ? $ice : 0),
                    //     'Iva_Por' => ($impu[$x1]->codigoPorcentaje->text() == '2' ? 12 : ($impu[$x1]->codigoPorcentaje->text() == '3' ? 14 : null)),
                    //     'Ice_Por' => ($iva ? ($ice * 100 / ($impu[$x1]->baseImponible->text() * 1 - $ice)) : 0)
                    // ));
                    $Cop_Dec = $Cop_Decv * 100 / ($impu[$x1]->baseImponible->text() * 1 + $Cop_Decv);
                    // Nueva version que separa No Objeto Iva
                    $itemArr = array(
                        'Cop_Can' => 1,
                        'Ite_Lar' => 'Producto Tarifa ' . $impu[$x1]->tarifa . '%',
                        'Cop_Dec' => $Cop_Dec,
                        'Cop_Decv' => $Cop_Decv, //puesto recientemente
                        'Cop_Pru' => ($impu[$x1]->baseImponible->text() * 1) - ($iva ? $ice : 0) + ($factI->totalDescuento->text() * 1 > 0 ? $factI->totalDescuento->text() * 1 : 0),
                        'Cop_Imp' => $impu[$x1]->baseImponible->text() * 1 - ($iva ? $ice : 0),
                        //'Iva_Por' => ($impu[$x1]->codigoPorcentaje->text() == '2' ? 12 : ($impu[$x1]->codigoPorcentaje->text() == '3' ? 14 : null)),
                        'Iva_Por' => ($impu[$x1]->codigoPorcentaje->text() == '2' ? 12 : ($impu[$x1]->codigoPorcentaje->text() == '3' ? 14 : ($impu[$x1]->codigoPorcentaje->text() == '4' ? 15 : ($impu[$x1]->codigoPorcentaje->text() == '5' ? 5 : null)))),
                        'Ice_Por' => ($iva ? ($ice * 100 / ($impu[$x1]->baseImponible->text() * 1 - $ice)) : 0)
                    );
                    // Tarifa 0
                    if ($impu[$x1]->codigoPorcentaje->text() == '0') {
                        $itemArr['Iva_Por'] = 0;
                        $itemArr['Iva_Cod'] = '0';
                        $itemArr['t_iva0'] = $impu[$x1]->baseImponible->text() * 1;
                    }
                    // No objeto IVA (asignar Iva_Sri = 6)
                    if ($impu[$x1]->codigoPorcentaje->text() == '6') {
                        $itemArr['Iva_Por'] = 0;
                        $itemArr['Iva_Cod'] = '6';
                        $itemArr['Iva_Sri'] = 6;
                        $itemArr['t_noiva'] = $impu[$x1]->baseImponible->text() * 1;
                    }
                    array_push($r['items'], $itemArr);
                }
            }
        } else {
            for ($x = 0; $x < count($items); $x++) {
                $Cod_Dec = ($items[$x]->descuento->text() * 1 > 0) ? $items[$x]->descuento->text() * 100 / ($items[$x]->precioTotalSinImpuesto->text() * 1 + $items[$x]->descuento->text() * 1) : 0;
                // $Cod_Dec = ($items[$x]->descuento->text() * 1 > 0) ? ($items[$x]->descuento->text() * 1) / ($items[$x]->precioTotalSinImpuesto->text() * 1) * 100 : 0;
                $Cop_Decv = $items[$x]->descuento->text() * 1;

                $arrIt = array(
                    'Cop_Can' => $items[$x]->cantidad->text(),
                    'Ite_Lar' => $items[$x]->descripcion->text(),
                    'Cop_Dec' => $Cod_Dec,
                    'Cop_Decv' => $Cop_Decv,
                    'Cop_Pru' => round(($items[$x]->precioTotalSinImpuesto->text() * 1 + ($items[$x]->descuento->text() * 1 > 0 ? $items[$x]->descuento->text() * 1 : 0)) / ($items[$x]->cantidad->text() * 1), 8),
                    'Cop_Imp' => $items[$x]->precioTotalSinImpuesto->text(),
                );

                $impuItem = $items[$x]->impuestos->impuesto;

                for ($x1 = 0; $x1 < count($impuItem); $x1++) {
                    // IVA / No objeto / Exento
                    if ($impuItem[$x1]->codigo->text() == '2') {
                        $arrIt['Iva_Por'] =  $impuItem[$x1]->tarifa->text();
                        $arrIt['Iva_Cod'] =  $impuItem[$x1]->codigoPorcentaje->text();

                        // Tarifa 0
                        if ($impuItem[$x1]->codigoPorcentaje->text() == '0' && $impuItem[$x1]->tarifa->text() == '0') {
                            $arrIt['t_iva0'] = $impuItem[$x1]->baseImponible->text() * 1;
                        } else {
                            $arrIt['t_iva0'] = 0;
                        }
                        // No objeto IVA (asignar Iva_Sri = 6)
                        if ($impuItem[$x1]->codigoPorcentaje->text() == '6' && $impuItem[$x1]->tarifa->text() == '0') {
                            $arrIt['t_noiva'] = $impuItem[$x1]->baseImponible->text() * 1;
                            $arrIt['Iva_Sri'] = 6;
                        } else {
                            $arrIt['t_noiva'] = 0;
                        }
                    }

                    if ($impuItem[$x1]->codigo->text() == '3' && ("0" . $impuItem[$x1]->valor->text()) * 1 > 0 && $arrIt['Cop_Imp'] * 1 > 0) {
                        $arrIt['Ice_Por'] = (("0" . $impuItem[$x1]->valor->text()) * 1) * 100 / $arrIt['Cop_Imp'];
                    }
                }
                array_push($r['items'], $arrIt);
            }
        }
        //para abrir crear proveedor
        $pers = $obBD_con1->getArrayConsulta(30, substr($r['data']['Prs_Ced'], 0, 10) . '*' . $Ses_Emp_Cod, $obBD_conexion);
        if (count($pers) > 0) {
            $per = array(0 => $pers[0]);
            if (count($pers) > 1) foreach ($pers as $p) {
                if ($p['Emp_Cod'] * 1 == $Ses_Emp_Cod * 1) {
                    $per[0] = $p;
                    break;
                }
            }
            if (count($per) == 1) $r['data'] = array_merge($r['data'], $per[0]);
        }

        // Sumamos y consolidamos el total No Objeto IVA y Tarifa 0
        $r['data']['t_noiva'] = 0;
        $r['data']['t_iva0'] = 0;
        foreach ($r['items'] as $it) {
            if (isset($it['t_noiva']) && $it['t_noiva'] > 0) {
                $r['data']['t_noiva'] += $it['t_noiva'];
            }
            if (isset($it['t_iva0']) && $it['t_iva0'] > 0) {
                $r['data']['t_iva0'] += $it['t_iva0'];
            }
        }
        unset($_SESSION['idCargar']);
    } catch (Exception $e) {
        $r['success'] = false;
        $r['message'] = '<span class="red">ERROR:</span> ' . $e->getMessage();
    }
    $obBD_con1->echoJson($r);
}
/* Consulta del tipo de proveedores */
if (isset($provAjax)) {
    $obBD_con1->getPageGridJson(2, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
}
//Cargar negociaciones
/*
if ($rs_infoEmpresa["Cof_NegCam"] == 'S') {
    if (isset($negociacionesAjax)) {
        $data_negociaciones = $obBD_con1->getArrayConsulta(1006,  $Ses_Emp_Cod, $obBD_conexion);
        $obBD_con1->echoJson($data_negociaciones);
    }
}*/

if ($rs_infoEmpresa["Cof_NegCam"] == 'S') {
    $grupo_empresas = $obBD_con1->getRowConsulta(1013, $Ses_Emp_Cod, $obBD_conexion); //Solo si tiene grupo de ecomar
    if (isset($negociacionesAjax)) {
        $Emp_Cod = $Ses_Emp_Cod;
        if (!empty($grupo_empresas["Emp_Cod"])) {
            $empresas = array_merge((array)$Emp_Cod, (array)$grupo_empresas["Emp_Cod"]);
            $Emp_Cod = implode(",", $empresas);
        }
        $data_negociaciones = $obBD_con1->getArrayConsulta(1006, $Emp_Cod . '*' . $search, $obBD_conexion);
        $obBD_con1->echoJson($data_negociaciones);
    }
}

$configsTemp = $obBD_con1->getRowConsulta(8, $Ses_Emp_Cod, $obBD_conexion);
if (isset($configsTemp["Cof_Prl"]) && $configsTemp["Cof_Prl"] == 'S') {
    if (isset($preliquidacionAjax)) {
        $sql = "SELECT Mal_Cod, Mal_Num, Mal_Fec, Mal_Obs, Mal_Tot_Cob FROM manifiesto_liquidacion_maq WHERE Mal_Est = 'A'";
        if (!empty($search)) {
            $sql .= " AND Mal_Num LIKE '%" . mysqli_real_escape_string($obBD_conexion->conexion, $search) . "%'";
        }
        $res = @mysqli_query($obBD_conexion->conexion, $sql);
        $data = array();
        while ($row = @mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
        $obBD_con1->echoJson($data);
    }
}

/* ver si exite un proveedor */
if (isset($provAjax2)) {
    $pers = $obBD_con1->getArrayConsulta(30, $Prs_Ced . '*' . $Ses_Emp_Cod, $obBD_conexion);
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

/* ver si exite un proveedor */
if (isset($cargarDefault)) {
    try {
        $default = $obBD_con1->getRowConsulta(968, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
        $responce['rows'] = $default;
        $responce['total'] = count($default);
        $responce['success'] = true;
    } catch (Exception $e) {
        $responce['success'] = false;
        $responce['message'] = $e->getMessage();
    }
    $obBD_con1->echoJson($responce);
}

/* Guarda un nuevo proveedor */
if (isset($guardaProvAjax)) {
    $data = $_POST;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    if (empty($Prs_Cod)) {
        $obBD_con1->operacionobBD(31, $data, $obBD_conexion);
        $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    }
    $data['Prv_Tel'] = $data['Prs_Tel'];
    $data['Prv_Cor'] = $data['Prs_Cor'];
    $obBD_con1->operacionobBD(32, $data, $obBD_conexion);
    $data['Prv_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    $data['proveedor'] = trim($data['Prs_Ape'] . ' ' . $data['Prs_Nom']);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true, 'prov' => $data);
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', error => $obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($responce);
}
/* Consulta datos del documento si existe */
if (isset($ajaxCopNum)) {
    $resp = array('success' => true);
    if (!empty($Tic_Cod) && !empty($Cop_Num)) {
        $row_rs_CodDoc = $obBD_con1->getRowConsulta(7, $Prv_Cod . '*' . $Tic_Cod . '*' . $Cop_Num, $obBD_conexion);
        if ($row_rs_CodDoc['Cop_Cod'] != "")
            $resp = array('success' => false, 'message' => 'El documento ya Existe en el Sistema!');
    } else $resp['success'] = '';
    $obBD_con1->echoJson($resp);
}

//Secci�n para obtener el n�mero de secuencia de Liquidación de compras
if (isset($numeroSec)) {
    $response = $obBD_con1->getRowConsulta(1004, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod . '*' . $Tic_Cod . '*' . $Aut_Cod, $obBD_conexion);
    if (isset($Aut_Sri)) $response['Aut_Sri'] = $Aut_Sri;
    $siguiente = $obBD_con1->getRowConsulta(1005, $response['Aut_Ini'] . '*' . $response['Aut_Fin'] . '*' . $response['Aut_Sri'] . '*' . $Tic_Cod . '*' . $Ses_Suc_Cod . '*' . $Pun_Sri, $obBD_conexion);
    $response['Cop_Num'] =  str_pad($siguiente['siguiente'], 9, '0', STR_PAD_LEFT);
    $response['contador'] = $siguiente['contador'];
    echo json_encode($response);
    exit();
}

/** Valida liquidaciones **/
if (isset($liquida)) {
    /* Valida que los Periodos Existan */
    $Pec_Cop = $obBD_con1->getRowConsulta(9, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
    if (empty($Pec_Cop['Pec_Cod'])) {
        $responce['message'] = "No Existe Periodo para la Fecha: $Cop_Fec!";
    }
    $Pec_Cod = $Pec_Cop['Pec_Cod'];
    $total = $obBD_con1->getRowConsulta(56, $Prv_Cod . '*' . $Tic_Sri . '*' . $Pec_Cop['Pec_Fei'] . '*' . $Pec_Cop['Pec_Fef'], $obBD_conexion); // busca total de liquidaciones
    $responce['total'] = $total['total'];
    $responce['success'] = true;
    $obBD_con1->echoJson($responce);
}

/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(8, $Ses_Emp_Cod, $obBD_conexion);
$vendedor = $obBD_con1->getRowConsulta(10, $Ses_Suc_Cod . '*' . $Ses_Prs_Cod, $obBD_conexion);

/* Consulta del tipo de productos */
if (isset($proAjax)) {
    if (!empty($Cop_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(9, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
    else $Pec_Cop = array('Pla_Cod' => null);
    $responce = $obBD_con1->getPageGrid(1, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
    if ($responce['records'] > 0) {
        if ($configs['Cof_Con'] == 'S' && !empty($Pec_Cop['Pla_Cod'])) {
            foreach ($responce['rows'] as &$r) {
                $cuenta = $obBD_con1->getRowConsulta(16, $Pec_Cop['Pla_Cod'] . '*' . $r['Pro_Cod'] . '*' . 'C', $obBD_conexion);
                if (!empty($cuenta['Pld_Cod'])) $r = array_merge($r, $cuenta);
            }
            unset($r);
        }
    }
    $obBD_con1->echoJson($responce);
}
/* Consulta del codigo retencion */
if (isset($codiAjax)) {
    $data = $_GET;
    if ($configs['Cof_Con'] == 'S' && !empty($Cop_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(9, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
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

/* reviso las cuentas pago */
if (isset($cuentasPago)) {
    $responce['cuentas'] = '';
    $Pec_Cod = $obBD_con1->getRowConsulta(9, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
    if ($For_Cod * 1 == 2)
        $cuentas = $obBD_con1->getArrayConsulta(23, $Pec_Cod['Pla_Cod'] . '*' . $For_Cod, $obBD_conexion);
    if ($For_Cod * 1 == 1)
        $cuentas = $obBD_con1->getArrayConsulta(22, $Pec_Cod['Pla_Cod'] . '*' . $For_Cod, $obBD_conexion);
    if ($For_Cod * 1 == 3)
        $cuentas = $obBD_con1->getArrayConsulta(28, $Pec_Cod['Pla_Cod'] . '*' . 'RC', $obBD_conexion);

    $responce['total'] = count($cuentas);
    foreach ($cuentas as $row)
        $responce['cuentas'] = $responce['cuentas'] . '<option value="' . $row['Pld_Cod'] . '" data-extra="' . (isset($row['extra']) ? $row['extra'] : '') . '" ' . (isset($Pld_Cod) && $row['Pld_Cod'] == $Pld_Cod ? 'selected="selected"' : '') . '>' . $row['Pld_Des'] . '</option>';
    if ($responce['total'] > 1)
        $responce['cuentas'] = "<option value=''>Seleccione...</option>" . $responce['cuentas'];
    $responce['success'] = true;
    $obBD_con1->echoJson($responce);
}
/* reviso los ivas */
if (isset($Check_Iva)) {
    //    $responce['varIvas']=(/*!empty($Tic_Sri)&&('0'.$Tic_Sri)*1==4&&*/$Cop_Fec<='2017-05-31');
    //    if($responce['varIvas'])
    $responce['ivas']  = $obBD_con1->getArrayConsulta(18, '', $obBD_conexion);
    //    else
    $responce['iva_activo']  = $obBD_con1->getRowConsulta(19, $Cop_Fec, $obBD_conexion);
    $responce['varIvas'] = true;
    $responce['total'] = count($responce['ivas']);
    $responce['options'] = '';
    foreach ($responce['ivas'] as $row)
        $responce['options'] = $responce['options'] . '<option value="' . $row['Iva_Cod'] . '" data-ivapor="' . $row['Iva_Por'] . '" ' . ($responce['iva_activo']['Iva_Por'] == $row['Iva_Por'] ? 'selected="selected"' : '') . '>' . $row['Iva_Por'] . ' %</option>';
    if ($configs['Cof_Con'] == 'S') {
        $responce['cuentas'] = '';
        $Pec_Cod = $obBD_con1->getRowConsulta(9, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
        $iva_pag = $obBD_con1->getArrayConsulta(20, $Pec_Cod['Pla_Cod'], $obBD_conexion);
        foreach ($iva_pag as $row)
            $responce['cuentas'] = $responce['cuentas'] . '<option value="' . $row['Pld_Cod'] . '" >' . $row['Pld_Des'] . '</option>';
    }
    $responce['success'] = true;
    $obBD_con1->echoJson($responce);
}

/* Guardar documento */
if (isset($saveDocument)) {
    // $idCargaExitosa = $_SESSION['idCargar'];
    /*if (!empty($idCargaExitosa)) {
        $obBD_con1->operacionobBD(201, $idCargaExitosa, $obBD_conexion);
    }*/
    $obBD_con1->validaCierrePeriodo('compras', 'Cop_Fec', 'Cop_Cod', $Cop_Fec, null, $obBD_conexion, 'S');
    $responce = array('success' => false);
    /* Que sea vendedor */
    if (empty($vendedor['Vnd_Cod'])) {
        $responce['message'] = "No tiene permisos de Vendedor!";
    }
    $Vnd_Cod = $vendedor['Vnd_Cod'];
    $For_Cod = $For_Cod * 1;
    /* valida que no exista el documento */
    if ($Tic_Sri * 1 != 17) { // Condicion agregada xq se repite el numero de DAE
        $row_rs_CodDoc = $obBD_con1->getRowConsulta(7, $Prv_Cod . '*' . $Tic_Cod . '*' . $Cop_Num, $obBD_conexion);
        if (!empty($row_rs_CodDoc['Cop_Cod'])) {
            $responce['message'] = "El doc. $Tic_Des No. $Cop_Num ya existe!";
        }
    }
    if (is_string($items)) {
        $items = json_decode(stripslashes($items), true);
    }
    /* Valida que los Periodos Existan */
    $Pec_Cop = $obBD_con1->getRowConsulta(9, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
    if (empty($Pec_Cop['Pec_Cod'])) {
        $responce['message'] = "No Existe Periodo para la Fecha: $Cop_Fec!";
    }
    $Pec_Cod = $Pec_Cop['Pec_Cod'];
    $Retencion = (!empty($rets) && count($rets) > 0);
    //$responce['message'] = "No Existe Periodo para la Fecha:".($Retencion ? 'true' : 'false');
    //No generar retencion si el codigo del sri posee el codigo 332.. 
    if ($Retencion) {
        foreach ($items as $i => $item) { //Verificar si tiene el codigo 332.. del sri
            $cod_sri =  $item['Ret_Ren_Sri'];
            $porc_ret = isset($item['Ret_Ren_Por']) ? ($item['Ret_Ren_Por'] * 1) : 0;
            if (!empty($Aut_Cod)  &&  ($cod_sri == "332" || $cod_sri == "332B" || $cod_sri == "332C" || $cod_sri == "332D" || $cod_sri == "332G" || $cod_sri == "332H" || $cod_sri == "332I")) {
                $Retencion = false;
            }
             // 332E: solo bloquear si el porcentaje es 0%; con 1% sí generar retención
             if (!empty($Aut_Cod) && $cod_sri == "332E" && $porc_ret <= 0) {
                $Retencion = false;
            }
            if ($Tic_Cod == 2  &&  !empty($Aut_Cod)) {
                $Retencion = true;
            }
        }
    }
    //fin codigo
    // $responce['message'] = "No Existe Periodo para la Fecha: $Aut_Cod";
    if ($Retencion && empty($Aut_Cod)) {
        $responce['message'] = "No tiene <i>Autorizaci&oacute;n Activa</i> para generar <u>Retenciones</u>!";
    }
    $Cop_Des = (!empty($Cop_Des) ? $Cop_Des * 1 : 0);
    $Ret_Num = ($Retencion/*&&(($Ren_Tot)*1>0)*/ ? $Ret_Num : 0);
    $isClaveAccesoExterna = (isset($isClaveExterna) && !empty($isClaveExterna));
    if (/*$configs['Cof_Gce']=='S'*/$Aut_Tem == 'E' && $Retencion && $Ret_Num !== 0) {
        if (!$isClaveAccesoExterna) {
            require_once('../LOGICA/fac_log_electronica.php');
            $obBD_elect =  new Class_Log_Datos_Retencion_Elect();
            $claveAcceso = $obBD_con1->getRetClaveAcceso($Ses_Emp_Cod, $Ses_Suc_Cod, $Aut_Cod, $Ret_Fec, $Ret_Num, $obBD_conexion);
            //$claveAcceso = $obBD_elect->getClaveAcceso($Aut_Cod, $Ret_Fec, $Ret_Num, $obBD_conexion);
            if (empty($claveAcceso)) $responce['message'] = "Error al generar <u>Clave de Acceso</u> del <i>Comprobante Electrónico</i>!";
            //if(!$obBD_con1->createUsuCliente($Ses_Emp_Cod, $Ses_Suc_Cod, $Prs_Cod, $Prs_Ced, $obBD_conexion)) $responce['message']='Error al crear usuario de <u>Comprobantes Electr�nicos</u>!';
        } else $claveAcceso = $claveAccesoExt;
    }
    /* valida que no exista la retencion */
    if ($Retencion && $Ret_Num !== 0) {
        $autor = $obBD_con1->getRowConsulta(61, $Aut_Cod, $obBD_conexion);
        $row_rs_RetDoc = $obBD_con1->getRowConsulta(76, $Ret_Num . '*' . $autor['Aut_Sri'] . '**' . $Ses_Emp_Cod . '*' . $autor['Pun_Sri'], $obBD_conexion);
        if (!empty($row_rs_RetDoc['Ret_Cod'])) {
            $responce['message'] = "La Retencion No. $Ret_Num  con Autorizacion No. $Aut_Sri ya existe!";
        }
    }
    $rise = ($Tic_Sri * 1 == 2 || $Tic_Sri * 1 == 9); // rise, nota de venta
    if ($rise) $iva_cero = $obBD_con1->getRowConsulta(68, '0', $obBD_conexion);
    /* cierro en caso de error */
    if (!empty($responce['message'])) {
        echo json_encode($responce);
        exit();
    }
    $obBD_ins1 =  new Class_Log_Datos_Factu;
    $obBD_conexionIns = new Class_Log_Conexion_Factu($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);

    if (!empty($idCargaExitosa)) { //validar que no se registre si algo da error
        // $obBD_con1->operacionobBD(201, $idCargaExitosa, $obBD_conexion);
        $obBD_con1->operacionobBD(201, $idCargaExitosa, $obBD_conexionIns);
    }

    try {
        //ChromePhp::log("AUT_CODLIQ:".$Aut_Codliq);
        if ($Tic_Cod == 3    && !empty($Aut_Codliq)) {
            require_once('../LOGICA/fac_log_electronica.php');
            // function getLiquidacionClaveAcceso($Aut_Cod, $Doc_Fec, $Doc_Num, $obBD)
            // $Cop_Aut =  $obBD_con1->getLiquidacionClaveAcceso($Ses_Emp_Cod, $Ses_Suc_Cod, $Aut_Cod,  $Cop_Fec, $Cop_Num, $obBD_conexion);
            $Cop_Aut =  $obBD_con1->getLiquidacionClaveAcceso($Aut_Codliq,  $Cop_Fec, $Cop_Num, $obBD_conexion);

            $claveAccesoliq = $Cop_Aut;
            $Cop_Num =  $Pun_Sri . $Cop_Num;
        }

        /* Cabecera de la factura de compra */
        $meseCop = explode('-', $Cop_Fec);
        $Cop_Sec = $obBD_con1->codigoSecMensualAuto($Pec_Cod, $meseCop[1], $obBD_conexion); // Secuencia de compras por mes

        //ChromePhp::log("TIA COD::".$Tic_Cod);
        // $obBD_ins1->operacionobBD(11, $Tic_Cod . '*' . $Prv_Cod . '*' . $Ciu_Cod . '*' . trim($Cop_Num) . '*' . trim($Cop_Aut) . '*' . $Cop_Fec . '*' . $hoy . '*' . trim($Cop_Obs) . '*' . $Cop_Cad . '*' . $Cop_Imf . '*' . $Tri_Cod . '*' . $Cop_Des . '*' . $Pec_Cod . '*' . (empty($Tpc_Cod) ? 1 : $Tpc_Cod) . '*' . (isset($Cop_Ntd) ? $Cop_Ntd : '') . '*' . (isset($Cop_Nns) ? $Cop_Nns : '') . '*' . (isset($Cop_Nna) ? $Cop_Nna : '') . '*' . $Vnd_Cod . '*' . $Cop_Sec . '*' . $Con_Cod . '*' . $Cop_Irb . '*', $obBD_conexionIns);
        $obBD_ins1->operacionobBD(11, $Tic_Cod . '*' . $Prv_Cod . '*' . $Ciu_Cod . '*' . trim($Cop_Num) . '*' . trim($Cop_Aut) . '*'
            . $Cop_Fec . '*' . $hoy . '*' . trim($Cop_Obs) . '*' . $Cop_Cad . '*' . $Cop_Imf . '*' . $Tri_Cod . '*' . $Cop_Des . '*'
            . $Pec_Cod . '*' . (empty($Tpc_Cod) ? 1 : $Tpc_Cod) . '*' . (isset($Cop_Ntd) ? $Cop_Ntd : '') . '*'
            . (isset($Cop_Nns) ? $Cop_Nns : '') . '*' . (isset($Cop_Nna) ? $Cop_Nna : '') . '*'
            . $Vnd_Cod . '*' . $Cop_Sec . '*' . $Con_Cod . '*' .
            $Cop_Irb . '*'  . (!empty($Aut_Codliq) ? $Aut_Codliq : '') . '*' . $t_iva_pres . '*' . $t_imp_combustible . '*' . $t_prop . '*' . $t_adic . '*', $obBD_conexionIns);

        $Cop_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
        /*Update Cop_Ide*/
        $obBD_ins1->operacionobBD(1009, array('Cop_Ide' => $Cop_Ide, 'Cop_Cod' => $Cop_Cod), $obBD_conexionIns);
        //Registrar documento de la negociación
        if (isset($Cod_Neg) && !empty($Cod_Neg) && $Cod_Neg != 0 && $Cod_Neg !== 'null') {
            $obBD_ins1->operacionobBD(1007, $Cod_Neg . '*' . $Cop_Cod . '*' . 'CMP', $obBD_conexionIns);
        }
        
        //Registrar documento de la preliquidacion
        if (isset($Cod_Prl) && !empty($Cod_Prl) && $Cod_Prl != 0 && $Cod_Prl !== 'null') {
            if (isset($configs["Cof_Prl"]) && $configs["Cof_Prl"] == 'S') {
                $sql_ver = "SELECT Mal_Tot_Cob FROM manifiesto_liquidacion_maq WHERE Mal_Cod = " . (int)$Cod_Prl;
                $res_ver = @mysqli_query($obBD_conexionIns->conexion, $sql_ver);
                if ($res_ver && $row_ver = mysqli_fetch_assoc($res_ver)) {
                    $total_liq = (float)$row_ver['Mal_Tot_Cob'];
                    $total_compra = isset($t_rubros) ? (float)str_replace(',', '', $t_rubros) : 0;
                    if (abs($total_liq - $total_compra) > 0.01) {
                        $compra_formato = number_format($total_compra, 2);
                        $preliq_formato = number_format($total_liq, 2);
                        throw new Exception("Existe diferencia en valores<br><span style=\"color: #4CAF50; font-weight: bold;\">Compra: $$compra_formato</span><br><span style=\"color: #FF9800; font-weight: bold;\">Preliquidacion: $$preliq_formato</span><br>Los totales deben coincidir para esta accion");
                    }
                }
                $sql_update_preliq = "UPDATE manifiesto_liquidacion_maq SET Cop_Cod = " . (int)$Cop_Cod . " WHERE Mal_Cod = " . (int)$Cod_Prl;
                @mysqli_query($obBD_conexionIns->conexion, $sql_update_preliq);
            }
        }


        /**Cabecera de la retencion */
        //inicio
        if ($rs_infoEmpresa['Ret_Scom'] == "S"  &&  $Ret_Asu == "S") {
            if ($Retencion) {
                $Ret_Fec = (!empty($Ret_Fec) ? $Ret_Fec : $Cop_Fec);
                //  $obBD_ins1->operacionobBD(53, $Cop_Cod . '*' . $Ret_Num . '*' . $Ret_Fec . '*' . trim($Cop_Obs) . '*' . $tipo_compr . '*' . $Vnd_Cod . '*' . $Aut_Cod . '*' . (isset($claveAcceso) ? $claveAcceso : '') . '*' . (!empty($Ret_Asu) ? $Ret_Asu : 'N') . '*' . $Ret_Uca . '*' . $Ret_Pca, $obBD_conexionIns);
                $obBD_ins1->operacionobBD(53, $Cop_Cod . '*' . 0 . '*' . $Ret_Fec . '*' . trim($Cop_Obs) . '*' . $tipo_compr . '*' . $Vnd_Cod . '*' . $Aut_Cod . '*' . '' . '*' . (!empty($Ret_Asu) ? $Ret_Asu : 'N') . '*' . $Ret_Uca . '*' . $Ret_Pca, $obBD_conexionIns);
                $Ret_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
            }
        } else if ($Retencion) {
            $Ret_Fec = (!empty($Ret_Fec) ? $Ret_Fec : $Cop_Fec);
            $obBD_ins1->operacionobBD(53, $Cop_Cod . '*' . $Ret_Num . '*' . $Ret_Fec . '*' . trim($Cop_Obs) . '*' . $tipo_compr . '*' . $Vnd_Cod . '*' . $Aut_Cod . '*' . (isset($claveAcceso) ? $claveAcceso : '') . '*' . (!empty($Ret_Asu) ? $Ret_Asu : 'N') . '*' . $Ret_Uca . '*' . $Ret_Pca, $obBD_conexionIns);
            $Ret_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
            if ($isClaveAccesoExterna)
                $obBD_ins1->operacionobBD(80, $Ret_Cod . '*' . $claveAcceso, $obBD_conexionIns);
        }
        //fin


        /* Creacion del comprobante contable */
        //ChromePhp::log("asfsadfasdfasdfasdfasdfasdf::".$configs['Cof_Con'] );
        if ($configs['Cof_Con'] == 'S') {
            $Com_Con = $Cop_Obs;
            $Iva_Costo = 0;
            $Tia_Asi = $obBD_con1->getRowConsulta(13, ($For_Cod != 2 ? 1 : 2), $obBD_conexion);
            $meseCom = explode('-', $Com_Fec);
            $Com_Num = $obBD_con1->getComNumPecAuto($Tia_Asi['Tia_Cod'], $Pec_Cod, $Com_Fec, $obBD_conexion); // Secuencia de comprobante por mes y por tipo
            $campo = 'Prv_Cod';
            /* Cabecera del Comprobante */
            //ChromePhp::log("asfsadfasdfasdfasdfasdfasdf::".$Tia_Asi['Tia_Cod'] );
            $obBD_ins1->operacionobBD(14, $Pec_Cod . '*' . $Prv_Cod . '*' . $Com_Num . '*' . $Com_Fec . '*' . ("P/R. $Tic_Des $Cop_Num ") . trim($Com_Con) . '*' . $Tia_Asi['Tia_Cod'] . '*' . $t_rubros . '*' . trim($Cop_Obs) . '*' . $campo, $obBD_conexionIns);
            $Com_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
            $obBD_ins1->operacionobBD(15, $Com_Cod . '*' . $Cop_Cod, $obBD_conexionIns); // relacion compra comprobante
            /* Inserta datos en el detalle del asiento (por items) */
            foreach ($items as &$item) {
                $addIva = round(($item['Iva_Cos'] == 'S' && $item['Iva_Por'] * 1 > 0 ? (($item['Cop_Imp'] - ($Cop_Des > 0 ? $item['Cop_Imp'] * $Cop_Des / 100 : 0)) * $item['Iva_Por'] / 100) : 0), 2);
                $Iva_Costo = $Iva_Costo + $addIva;
                $cuenta = $obBD_con1->getRowConsulta(16, $Pec_Cop['Pla_Cod'] . '*' . $item['Pro_Cod'] . '*' . 'C', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del producto: <u>' . $item['Ite_Lar'] . '</u>!');
                //$item['Pld_Cod']=$cuenta['Pld_Cod'];

                //Guardar el total 
                $item['Cop_Imp'] = $item['Cop_Dec'] > 0 ? $item['Cop_Can'] * $item['Cop_Pru'] : $item['Cop_Imp'];
                $obBD_ins1->operacionobBD(17, array($Com_Cod, 'D', ($item['Cop_Imp'] + $addIva), $cuenta['Pld_Des'], $item['Ite_Lar'], $item['Pld_Cod']), $obBD_conexionIns);  // inserta asiento // Item
                $Asi_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
                // Guardar los códigos Asi_Cod en un array temporal para registrar luego en det_compra
                if (!isset($array_asi_cod)) $array_asi_cod = array();
                $array_asi_cod[] = array('Asi_Cod' => $Asi_Cod, 'Pro_Cod' => $item['Pro_Cod'],   'Cop_Cod' => $Cop_Cod, 'Pld_Cod' => $cuenta['Pld_Cod']);
            }
            unset($item);
            /* CCPP Cuentas por pagar */
            if ($For_Cod * 1 == 2) {
                $obBD_ins1->operacionobBD(55, $Com_Cod . '*' . $Cop_Cod . '*' . $Cpp_Ven . '*' . trim($Cpp_Obs), $obBD_conexionIns);
                $Cpp_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
            }
            /* Inserta datos en el detalle del asiento (por codigo retenci�n) */
            if ($Ret_Asu == 'S')  $cuenta_ret_asu = $obBD_con1->getRowConsulta(28, $Pec_Cop['Pla_Cod'] . '*' . 'RA', $obBD_conexion);

            $totalReal = $Val_Pcc + $Ren_Tot;
            if (($For_Cod * 1 == 2) && $Ret_Asu != 'S') {
                if ($Retencion && $Ret_Num > 0) {
                    $Com_Con_Ret = "RETENCION DE LA COMPRA NUMERO " . $Cop_Num;
                    $Tia_Asi_Ret = $obBD_con1->getRowConsulta(133, 15, $obBD_conexion);
                    $meseCom = explode('-', $Com_Fec);
                    $Com_Num_Ret = $obBD_con1->getComNumPecAuto($Tia_Asi_Ret['Tia_Cod'], $Pec_Cod, $Com_Fec, $obBD_conexion);
                    $campo = 'Prv_Cod';
                    /* Cabecera del Comprobante */
                    $obBD_ins1->operacionobBD(14, $Pec_Cod . '*' . $Prv_Cod . '*' . $Com_Num_Ret . '*' . $Ret_Fec . '*' . trim($Com_Con_Ret) . '*' . $Tia_Asi_Ret['Tia_Cod'] . '*' . $Ren_Tot . '*' . 'RETENCION' . '*' . $campo, $obBD_conexionIns);
                    $Com_Cod_Ret = $obBD_ins1->insercionid($obBD_conexionIns);

                    foreach ($rets as $ret) {
                        if (("0" . $ret['Ren_Val']) * 1 > 0) {
                            $cuenta = $obBD_con1->getRowConsulta(52, $Pec_Cop['Pla_Cod'] . '*' . $ret['Ren_Cod'] . '*' . 'C', $obBD_conexion);
                            if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del Codigo: <u>' . $ret['Ren_Sri'] . '</u>!');
                            $obBD_ins1->operacionobBD(17, $Com_Cod_Ret . '*' . 'H' . '*' . $ret['Ren_Val'] . '*' . $cuenta['Pld_Des'] . '*' . $ret['Ren_Con'] . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
                        }
                    }
                    //Asiento de proveedores varios para el comprobante de retencion
                    $obBD_ins1->operacionobBD(17, $Com_Cod_Ret . '*' . ('D') . '*' . $Ren_Tot . '*' . '' . '*' . ('Doc.' . $Cop_Num) . '*' . $Pag_Pld, $obBD_conexionIns);
                    $Asi_Cod_Ret = $obBD_ins1->insercionid($obBD_conexionIns);
                    //Crear abono para la CUENTA X PAGAR 
                    $obBD_ins1->operacionobBD(255, array('Com_Cod' => $Com_Cod_Ret, 'Pag_Cod' => 50, 'Pag_Fec' => $Ret_Fec, 'Pag_Val' => $Ren_Tot, 'Pag_Obs' => "ABONO POR RETENCION", 'Cpp_Cod' => $Cpp_Cod, 'Asi_Cod' => $Asi_Cod_Ret), $obBD_conexionIns);
                }
                //Cuenta proveedores varios con valor completo
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . ('H') . '*' . $totalReal . '*' . '' . '*' . ('Doc.' . $Cop_Num) . '*' . $Pag_Pld, $obBD_conexionIns);
            } else {
                if ($Retencion && $Ret_Num > 0) {
                    foreach ($rets as $ret) {
                        if (("0" . $ret['Ren_Val']) * 1 > 0) {
                            $cuenta = $obBD_con1->getRowConsulta(52, $Pec_Cop['Pla_Cod'] . '*' . $ret['Ren_Cod'] . '*' . 'C', $obBD_conexion);
                            if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del Codigo: <u>' . $ret['Ren_Sri'] . '</u>!');
                            $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . 'H' . '*' . $ret['Ren_Val'] . '*' . $cuenta['Pld_Des'] . '*' . $ret['Ren_Con'] . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // retencion
                            if ($Ret_Asu == 'S') {
                                if (!isset($cuenta_ret_asu['Pld_Cod']) && empty($cuenta_ret_asu['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable de: <u>Retenciones Asumidas</u>!');
                                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . 'D' . '*' . $ret['Ren_Val'] . '*' . '' . '*' . 'ASUMIDA ' . $ret['Ren_Con'] . '*' . $cuenta_ret_asu['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // retencion asumida
                            }
                        }
                    }
                }
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . ('H') . '*' . $Val_Pcc . '*' . '' . '*' . ('Doc.' . $Cop_Num) . '*' . $Pag_Pld, $obBD_conexionIns);
            }


            /* IVA */
            $iva = $t_iva * 1 - $Iva_Costo;
            if ($iva > 0) {
                if (empty($Iva_Pag))  throw new Exception('Revisar la parametrizacion contable de: <u>Iva Pagado</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . ('D') . '*' . $iva . '*' . 'IVA' . '*' . 'IVA' . '*' . $Iva_Pag, $obBD_conexionIns);  // inserta asiento // Iva
            }
            /* DESCUENTO */
            if ($Cop_Des > 0 || $t_pdescuento > 0) {
                if ($t_pdescuento > 0) {
                    $t_descuento = $t_pdescuento + $t_descuento;
                } // Guardar los descuentos por productos.
                $cuenta = $obBD_con1->getRowConsulta(28, $Pec_Cop['Pla_Cod'] . '*' . 'CDS', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Descuentos en Compras</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . ('H') . '*' . $t_descuento . '*' . 'DESCUENTO' . '*' . 'DESCUENTO' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // descuento
            }
            if ($t_ice * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Pec_Cop['Pla_Cod'] . '*' . 'ICC', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>ICE en Compras</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . ('D') . '*' . $t_ice . '*' . 'ICE' . '*' . 'ICE' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE
            }
            if ($Cop_Irb * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Pec_Cop['Pla_Cod'] . '*' . 'IRC', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>IRBPNR en Compras</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . ('D') . '*' . $Cop_Irb . '*' . 'IRBPNR' . '*' . 'IRBPNR' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE
            }

            if ($t_imp_combustible * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Pec_Cop['Pla_Cod'] . '*' . 'IG', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Impuesto a combustibles 3/1000 en Compras</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . ('D') . '*' . $t_imp_combustible . '*' . 'Impuesto a combustibles 3/1000' . '*' . 'Impuesto a combustibles 3/1000' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE
            }

            if ($t_iva_pres * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Pec_Cop['Pla_Cod'] . '*' . 'IPS', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>IVA presuntivo en Compras</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . ('D') . '*' . $t_iva_pres . '*' . 'IVA presuntivo' . '*' . 'Impuesto presuntivo' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE
            }

            if ($t_prop * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Pec_Cop['Pla_Cod'] . '*' . 'PRO', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>PROPINA</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . ('D') . '*' . $t_prop . '*' . 'PROPINA' . '*' . 'PROPINA' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // PROPINAS
            }

            if ($t_adic * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Pec_Cop['Pla_Cod'] . '*' . 'OTR', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>OTROS (Valores Adicionales)</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . ('D') . '*' . $t_adic . '*' . 'OTROS' . '*' . 'OTROS' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // OTROS - Valor Adicional
            }
        }

        //arreglo Caja Chica
        if ($For_Cod * 1 == 3) {
            //$obBD_ins1->operacionobBD(69, $Cop_Cod . '*' . '0' . '*' . 'P', $obBD_conexionIns);
            $obBD_ins1->operacionobBD('det_reposicion.insert', array('Cop_Cod' => $Cop_Cod, 'Dre_Int' => 1, 'Com_Cod' => (!empty($Com_Cod) ? $Com_Cod : null), 'Rep_Cod' => '0', 'Dre_Tip' => 'P'), $obBD_conexionIns);
        }

        /* Inserta datos en el detalle de la compra */
        $kardex = array('IoE' => 'I', 'Kar_Fec' => $Cop_Fec, 'Kar_Hor' => date("H:i:s"), 'Cop_Cod' => $Cop_Cod, 'Vnd_Cod' => $Vnd_Cod);
        $array_kardex = array();
        foreach ($items as $i => $item) {
            $item['Cop_Cod'] = $Cop_Cod;
            $item['Cop_Int'] = $i + 1;
            if ($rise) $item['Iva_Cod'] = $iva_cero['Iva_Cod'];
            // Asegurarse de que el 'Asi_Cod' correspondiente al item esté presente en el array antes de guardar
            if ($configs['Cof_Con'] == 'S' && isset($array_asi_cod[$i]['Asi_Cod'])) {
                $item['Asi_Cod'] = $array_asi_cod[$i]['Asi_Cod'];
            } else {
                unset($item['Asi_Cod']);
            }
            /* Item Documento */
            $obBD_ins1->operacionobBD(12, $item, $obBD_conexionIns);
            /* Control de Inventarios */
            if (($Tic_Sri * 1 != 0 || (isset($configs['Cof_Stk']) && $configs['Cof_Stk'] == 'S')) && ($item['Adq_Cor'] == 'B' || $item['Adq_Cor'] == 'SM')) {
                $s_add = true;
                // $imp = ((1) * $item['Cop_Imp'] - ($Cop_Des > 0 ? $item['Cop_Imp'] * $Cop_Des / 100 : 0));
                if ($t_pdescuento > 0) {
                    $imp = ((1) * $item['Cop_Imp'] - ($item['Cop_Decv']  > 0 ? $item['Cop_Decv']  : 0));
                } else {
                    $imp = ((1) * $item['Cop_Imp'] - ($Cop_Des > 0 ? $item['Cop_Imp'] * $Cop_Des / 100 : 0));
                }

                foreach ($array_kardex as &$k) {
                    if ($item['Pro_Cod'] == $k['Pro_Cod']) {
                        $k['Kar_Can'] += (1) * $item['Cop_Can'];
                        $k['Kar_Ims'] += $imp;
                        $k['Kar_Prs'] = $k['Kar_Ims'] / $k['Kar_Can'];
                        $s_add = false;
                        break;
                    }
                }
                unset($k);
                if ($s_add) {
                    $kardexIE = array_merge($kardex, array(
                        'Pro_Cod' => $item['Pro_Cod'],
                        'Iva_Cod' => $item['Iva_Cod'],
                        'Kar_Can' => (1) * $item['Cop_Can'],
                        'Kar_Prs' => $imp / $item['Cop_Can'],
                        'Kar_Ims' => $imp
                    ));
                    array_push($array_kardex, $kardexIE);
                }
            }
            /* Detalle de la retencion */
            /* if ($Retencion) {
                if ($t_pdescuento > 0) {
                    $des_indivi = ($item['Cop_Decv'] > 0 ? $item['Cop_Decv'] : 0);
                } else {
                    $des_indivi = ($Cop_Des > 0 ? ($item['Cop_Imp'] * $Cop_Des) / 100 : 0);
                }
                //$des_indivi = ($Cop_Des > 0 ? ($item['Cop_Imp'] * $Cop_Des) / 100 : 0);
                if (!empty($item['Ret_Ren_Cod']))
                    $obBD_ins1->operacionobBD(54, $Ret_Cod . '*' . ($item['Cop_Imp'] * 1 - $des_indivi) . '*' . $item['Ret_Ren_Cod'] . '*' . 'R' . '*' . $item['Cop_Int'] . '*' . $item['Adq_Cod'], $obBD_conexionIns);
                if (!empty($item['Iva_Ren_Cod']) && $item['Iva_Por'] * 1 > 0) {
                    $Imp = $item['Cop_Pru'] * $item['Cop_Can'];
                    // $Dec = ($item['Cop_Dec'] * 1 > 0 ? ($Imp * $item['Cop_Dec']) / 100 : 0);
                    $ImpDes = $Imp /*- $Dec*/ /*- $des_indivi;
                    $Ice = ($item['Cop_Ice'] * 1 > 0 ? ($ImpDes * $item['Cop_Ice']) / 100 : 0);
                    $obBD_ins1->operacionobBD(54, $Ret_Cod . '*' . formato_numero(($ImpDes + $Ice) * ($item['Iva_Por'] / 100), 2, 1) . '*' . $item['Iva_Ren_Cod'] . '*' . 'I' . '*' . $item['Cop_Int'] . '*' . $item['Adq_Cod'], $obBD_conexionIns);
                }
            }*/



            if ($Retencion) {
                // Calcular el importe base de manera consistente
                $Imp = (isset($item['Cop_Pru']) && isset($item['Cop_Can'])) ? $item['Cop_Pru'] * $item['Cop_Can'] : $item['Cop_Imp'];
                // Calcular el descuento individual: priorizar descuento por producto (Cop_Decv) si existe
                // Verificar primero si el item tiene descuento individual, independientemente de $t_pdescuento
                if (isset($item['Cop_Decv']) && $item['Cop_Decv'] * 1 > 0) {
                    $des_indivi = $item['Cop_Decv'];
                } elseif (isset($t_pdescuento) && $t_pdescuento > 0) {
                    // Si hay descuentos por producto pero este item no tiene Cop_Decv, no hay descuento para este item
                    $des_indivi = 0;
                } else {
                    // Usar descuento general como porcentaje
                    $des_indivi = ($Cop_Des > 0 ? ($Imp * $Cop_Des) / 100 : 0);
                }
                // Base imponible para retenciones (después de aplicar el descuento)
                $base_retencion = $Imp - $des_indivi;
                if (!empty($item['Ret_Ren_Cod']))
                    $obBD_ins1->operacionobBD(54, $Ret_Cod . '*' . $base_retencion . '*' . $item['Ret_Ren_Cod'] . '*' . 'R' . '*' . $item['Cop_Int'] . '*' . $item['Adq_Cod'], $obBD_conexionIns);
                if (!empty($item['Iva_Ren_Cod']) && $item['Iva_Por'] * 1 > 0) {
                    // $Dec = ($item['Cop_Dec'] * 1 > 0 ? ($Imp * $item['Cop_Dec']) / 100 : 0);
                    $ImpDes = $Imp /*- $Dec */ - $des_indivi;
                    $Ice = ($item['Cop_Ice'] * 1 > 0 ? ($ImpDes * $item['Cop_Ice']) / 100 : 0);
                    $obBD_ins1->operacionobBD(54, $Ret_Cod . '*' . formato_numero(($ImpDes + $Ice) * ($item['Iva_Por'] / 100), 2, 1) . '*' . $item['Iva_Ren_Cod'] . '*' . 'I' . '*' . $item['Cop_Int'] . '*' . $item['Adq_Cod'], $obBD_conexionIns);
                }
            }
        }
        /* registro de kardex y stocks */
        foreach ($array_kardex as $i => $k) {
            $k['Kar_Int'] = $i + 1;
            $obBD_ins1->updateStockProd($Ses_Suc_Cod, $k, true, $obBD_conexion, $obBD_conexionIns, $Bod_Cod);
        }
        if (isset($reembolsos) && is_array($reembolsos) && count($reembolsos > 0)) {
            $Rem_Int = 0;
            foreach ($reembolsos as $rem) {
                $Rem_Int++;
                $rem = array_merge($rem, array('Cop_Cod' => $Cop_Cod, 'Rem_Int' => $Rem_Int));
                $obBD_ins1->operacionobBD('compra_reembolsos.insert', $rem, $obBD_conexionIns);
            }
        }
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $responce['message'] = $e->getMessage();
        echo json_encode($responce);
        exit();
    }
    $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if ($obBD_ins1->Error == 0) {
        $responce = array('success' => true, 'Cop_Cod' => $Cop_Cod, 'Cop_Sec' => $Cop_Sec, 'Com_Cod' => isset($Com_Cod) ? $Com_Cod : NULL, 'Ret_Cod' => isset($Ret_Cod) ? $Ret_Cod : NULL, 'Tic_Des' => $Tic_Des, 'Mes' => mes($meseCop[1], 1) . "/$meseCop[0]");
        $reportes = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        // detalle del documento
        if (!empty($Cop_Cod)) {
            $responce['Cop_Data'] = array('Tic_Des' => $Tic_Des, 'proveedor' => $proveedor, 'Cop_Num' => $Cop_Num, 'Cop_Fec' => $Cop_Fec, 'Cop_Aut' => $Cop_Aut);
            $responce['Cop_Rows'] = $obBD_con1->getArrayConsulta(26, $Cop_Cod, $obBD_conexion);
            $responce['Cop_Link'] = "" . ($Tic_Sri * 1 == 3 && !empty($reportes[3]) ? "$reportes[3]?Cop_Cod=" : baseUrl("../../facturacion/FRONT/fac_pri_fac_detallecompras_1.0.php?com_codigo")) . "=$Cop_Cod";
        }

        // detalle del asiento contable
        if (!empty($Com_Cod)) {
            $responce['Com_Data'] = array('Com_Con' => $Cop_Obs, 'Com_Fec' => $Com_Fec, 'Com_Val' => $t_rubros, 'Tia_Des' => $Tia_Asi['Tia_Des'], 'Codigo' => $Tia_Asi['Tia_Abr'] . '-' . $meseCom[1] . '-' . $Com_Num);
            $responce['Com_Rows'] = $obBD_con1->getArrayConsulta(27, $Com_Cod, $obBD_conexion);
            $responce['Com_Link'] = "" . (!empty($reportes[1]) ? $reportes[1] : baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php")) . "?codigo=$Com_Cod&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Asi[Tia_Cod]&Pec_Cod=$Pec_Cop[Pec_Cod]";
        }


        //union de documento
        // if (!empty($Cop_Cod) && !empty($Com_Cod)) {
        //     $responce['Cop_Data'] = array(
        //         'Tic_Des' => $Tic_Des, 
        //         'proveedor' => $proveedor, 
        //         'Cop_Num' => $Cop_Num, 
        //         'Cop_Fec' => $Cop_Fec, 
        //         'Cop_Aut' => $Cop_Aut
        //     );
        //     $responce['Cop_Rows'] = $obBD_con1->getArrayConsulta(26, $Cop_Cod, $obBD_conexion);

        //     $responce['Com_Data'] = array(
        //         'Com_Con' => $Cop_Obs, 
        //         'Com_Fec' => $Com_Fec, 
        //         'Com_Val' => $t_rubros, 
        //         'Tia_Des' => $Tia_Asi['Tia_Des'], 
        //         'Codigo' => $Tia_Asi['Tia_Abr'] . '-' . $meseCom[1] . '-' . $Com_Num
        //     );
        //     $responce['Com_Rows'] = $obBD_con1->getArrayConsulta(27, $Com_Cod, $obBD_conexion);

        // $responce['combined'] = array_merge($responce['Cop_Rows'], $responce1['Com_Rows']);

        $responce['All_Link'] = baseUrl("../../facturacion/FRONT/fac_pri_fac_alldocument_1.0.php") . "?com_codigo=$Cop_Cod&codigo=$Com_Cod&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Asi[Tia_Cod]&Pec_Cod=$Pec_Cop[Pec_Cod]";

        // }

        // llamado para imprimir el comprobante de retencion

        // detalle de la retencion
        if (!empty($Com_Cod_Ret)) {
            $responce['Com_Data_Ret'] = array('Codigo_Ret' => $Com_Cod_Ret, 'Tia_Des_Ret' => $Tia_Asi_Ret['Tia_Des'], 'Com_Con_Ret' => $Com_Con_Ret, 'Com_Fec_Ret' => $Ret_Fec, 'Com_Val_Ret' => $Ren_Tot);
            $responce['Com_Rows_Ret'] = $obBD_con1->getArrayConsulta(27, $Com_Cod_Ret, $obBD_conexion);
            $responce['Com_Link_Ret'] = "" . (!empty($reportes[1]) ? $reportes[1] : baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php")) . "?codigo=$Com_Cod_Ret&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Asi_Ret[Tia_Cod]&Pec_Cod=$Pec_Cop[Pec_Cod]";
        }

        if ($Tic_Cod == 3 && !empty($Aut_Codliq)) { //Genera xml si es una liquidacion de compras
            //ChromePhp::log("Ingresa");
            require_once('../LOGICA/fac_log_electronica.php');
            $obBD_elect_liq =  new Class_Log_Datos_LiquidacionCompras_Elect();

            $Cop_Num_aux =  $Cop_Cod;

            //ChromePhp::log("Clave acceso " . $claveAccesoliq);
            $responce['xml'] = $obBD_elect_liq->createXmlLiquidacionCompra($Cop_Num_aux, $Aut_Codliq, $claveAccesoliq, $obBD_conexion);
            $responce['Ret_Xmls'] = baseUrl("../FRONT/" . $Ses_Emp_Cod . '/' . $claveAccesoliq . '.xml');
        }

        if (!empty($Ret_Cod)) {
            $responce['Ret_Cod'] = $Ret_Cod;
            $responce['Ret_Link'] = "" . (isset($reportes[2]) ? $reportes[2] : '') . "?Ret_Cod=$Ret_Cod";
            if (/*$configs['Cof_Gce']=='S'*/$Aut_Tem == 'E' && $Ret_Num !== 0 && !$isClaveAccesoExterna) {
                $rs_infoEmpresa = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
                $rs_infoCliente = $obBD_con1->getRowConsulta(61, $Aut_Cod, $obBD_conexion);
                //$responce['xml']=$obBD_con1->retencionElectronica($Ses_Emp_Cod, $Ses_Suc_Cod, $Prs_Cod, array_merge($rs_infoCliente, array('Ret_Cod'=>$Ret_Cod, 'Ret_Fec'=>$Ret_Fec, 'Ret_Num'=>str_pad($Ret_Num, 9, "0", STR_PAD_LEFT))), $obBD_conexion);
                $responce['xml'] = $obBD_elect->createXmlRetencion($Ret_Cod, $Aut_Cod, $claveAcceso, $obBD_conexion);
                $responce['Ret_Xmls'] = baseUrl("../FRONT/" . $Ses_Emp_Cod . '/' . $claveAcceso . '.xml');
                // envio del mail
                //$meseRet = explode('-', $Ret_Fec);
                //$datoElect=array('{varPrsCod}'=>$Prs_Cod,'{varEmpCod}'=>$Ses_Emp_Cod,'{Emp_Nom}'=>$Ses_Emp_Nom, '{Tic_Des}'=>'RETENCION', '{Prs_Ced}'=>$Prs_Ced, '{proveedor}'=>$proveedor, '{Prs_Cor}'=>$Prs_Cor, '{claveAcceso}'=>$claveAcceso, '{fecha}'=>$meseRet[2].' de '.mes($meseRet[1],1).' '.$meseRet[0], '{secuencia}'=>$rs_infoEmpresa["Suc_Sri"].'-'.$rs_infoCliente["Pun_Sri"].'-'.str_pad($Ret_Num, 9, "0", STR_PAD_LEFT));
                //$responce['mail']=$obBD_con1->sendMailRet($datoElect, reporteHtml($datoElect,'fac_pri_ret_ele.html'));
                //$responce['mail'] = $obBD_elect->sendMailDoc($Ret_Cod,$Prs_Cor,NULL,$obBD_conexion);
            }
        }
    } else {
        $responce = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_ins1->MsgError);
        //ChromePhp::log($obBD_ins1->MsgError);
    }
    $obBD_con1->echoJson($responce);
}
/* Valida numero de retenci�n */
if (isset($validaRetNum)) {
    $autoriz = $obBD_con1->getRowConsulta(48, $vendedor['Pun_Cod'] . '*' . $tipo_compr, $obBD_conexion); //Consulta las autorizaciones de las retenciones
    //$rs_infEmpFacElec = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
    $electronica = ($autoriz['Aut_Tem'] == 'E'); //($rs_infEmpFacElec['Cof_Gce']=='S');
    $row_max_codig = $obBD_con1->getRowConsulta(51, $Ses_Suc_Cod . '*' . $autoriz['Aut_Sri'] . '*' . $autoriz['Aut_Ini'] . '*' . $autoriz['Aut_Fin'] . '*' . $autoriz['Tic_Cod'] . '*' . $autoriz['Pun_Sri'], $obBD_conexion); //Consulta el maximo numero de retenciones en base a la autorizacion
    $Ret_Id_Man = ($row_max_codig['next']);
    if (empty($vendedor['Pun_Cod']) || empty($autoriz['Aut_Cod'])) $resp = array('success' => false, 'message' => "No tiene autorizacion para generar Retenciones!", Ret_Num_Old => 0, Ret_Num => '');
    else {
        $resp = array_merge(array('success' => true, 'Ret_Num' => $Ret_Id_Man, 'Ret_Num_Old' => $Ret_Num), $autoriz);
        if (!empty($Ret_Num)) {
            $num_existe_gencod = $obBD_con1->getRowConsulta(50, $Ses_Suc_Cod . '*' . $autoriz['Aut_Sri'] . '*' . $Ret_Num . '*' . $autoriz['Pun_Sri'], $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI
            if ($num_existe_gencod['total'] * 1 > 0) {
                $resp['success'] = false;
                $resp['message'] = "La Retención Número $Ret_Num ya Existe en el Sistema!";
            }
        } else $resp['success'] = false;
        $resp['Aut_Sri'] = ($electronica ? 'Electronica' : $autoriz['Aut_Sri']);
    }
    $obBD_con1->echoJson($resp);
}
/* buscar cuentas contables */
if (isset($cuenAjax)) {
    if (!empty($Cop_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(9, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
    else $Pec_Cop = array('Pla_Cod' => '');
    $responce = $obBD_con1->getPageGridJson('det_plan.selectWhere', array_merge($_GET, array('where' => array('det_plan.Pla_Cod' => $Pec_Cop['Pla_Cod']), 'setWhere' => array('isActive', 'isDetalle'))), $obBD_conexion);
}

$Pec_Cop = $obBD_con1->getRowConsulta(33, $Ses_Emp_Cod, $obBD_conexion);
if (!empty($Pec_Cop['Pec_Fei'])) $hoy = substr($Pec_Cop['Pec_Fei'], 0, 4) . substr($hoy, 4, 10);
$insert = true;
$rs_tip_compr = $obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('clean' => true, 'where' => array('Tic_Est' => 'A')), $obBD_conexion);
$listaActividad = $obBD_con1->getArrayConsulta('proveedore.selectWhere', array(
    'clean' => true,
    'unsetColsInit' => true,
    'setWhere' => array('listaTacDistinctPorEmpresa'),
    'where' => array(
        'proveedore.Emp_Cod' => $Ses_Emp_Cod,
        'proveedore.Prv_Est' => 'A',
    ),
    'order' => 'Prv_Tac ASC',
), $obBD_conexion);

//Obtener datos de CCxPP 08/10/2025
if (isset($saldoCCxPP)) {
    // Obtencion de valores
    $Cop_Fec = isset($_POST['Cop_Fec']) && !empty($_POST['Cop_Fec']) ? $_POST['Cop_Fec'] : $hoy;
    $Pec_Cop = $obBD_con1->getRowConsulta(9, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
    $Pec_Cod = isset($Pec_Cop['Pec_Cod']) ? $Pec_Cop['Pec_Cod'] : null;
    $Prv_Cod = null;

    if (isset($_POST['Prv_Cod'])) $Prv_Cod = $_POST['Prv_Cod'];
    if (isset($_POST['Pec_Cod'])) $Pec_Cod = $_POST['Pec_Cod'];
    $Emp_Cod = isset($Ses_Emp_Cod) ? $Ses_Emp_Cod : null;

    // Validar parámetros requeridos
    if ($Prv_Cod && $Pec_Cod && $Emp_Cod) {
        $rows = $obBD_con1->getArrayConsulta(1014, $Prv_Cod  . '*' . $Pec_Cod . '*' . $Emp_Cod, $obBD_conexion);
        $totalSaldo = 0;
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (isset($row['Saldo'])) {
                    $totalSaldo += floatval($row['Saldo']);
                }
            }
        }
        $response = array('success' => true, 'totalSaldo' => round($totalSaldo, 2), 'rows' => $rows);
    }
    echo json_encode($response);
    exit();
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Compras Registrar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript">
        var gridFact, index, Cof_Con = '<?php echo $configs['Cof_Con']; ?>',
            cod_banano = <?php echo $cod_banano; ?>;
    </script>
    <script>
        var docs, items, pagos, data = [],
            vet_num_ant = 0,
            tic_cod_ant = 0,
            edit_doc = 0,
            Vet_Index = 1,
            Vet_Selected, index;
        <?php $array_documentos = $obBD_con1->getArrayConsulta(1003, $vendedor['Pun_Cod'], $obBD_conexion);  ?>;
        var array_documentos = <?php echo json_encode($array_documentos); ?>;
    </script>

    <script language="javascript" src="../../framework/plugins/validadorCedulaRucFinal.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/fac_val_factu.js?gh=100"></script>
    <script language="javascript" src="../../framework/plugins/cedulaRuc.js"></script>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar Documentos de Compras</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="documentoMain">
                <?php include '../COMPONENTES/facComFormEdit.php'; ?>
                <div class="col-xs-12">
                    <button class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                </div>
                <div class="col-xs-12 Titulos2">
                    <hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
                </div>
            </div>
            <div id="documentoResult" class="form-horizontal normal" style="display: none;">
                <div class="row">
                    <div class="col-xs-6" id="resultContent">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Resultado De la Transacción</legend>
                            <div>
                                <h4 style="text-align: center; font-weight: 900;">El Documento se guardo con Éxito!</h4>
                                <p class="form-control-static resp" name="Tic_Des"></p>
                                <p class="resp"><span>&raquo;Mes:</span><span style="color:coral;" class="databind" name="Mes"></span></p>
                                <p class="resp"><span>&raquo;Sec:</span><span style="color:teal;" class="databind" name="Cop_Sec"></span></p>
                                <p class="resp"><span>&raquo;Cod:</span><span style="color: #CE0000;" class="databind" name="Cop_Cod"></span></p>
                                <div style="padding-top: 15px; text-align: center;">
                                    <button class="btn btn-sm btn-success" onclick="clearDocument();$('#documentoResult').moveComp('#documentoMain').updateGridsSizes();">
                                        <i class="glyphicon glyphicon-file"></i> Nuevo Documento
                                    </button>
                                    <button id="btnAllPrint" class="btn btn-sm btn-success" onclick="$.imprimirUrl($(this).data('url'))" style="margin-top: -2px;">
                                        <i class="glyphicon glyphicon-print"></i> Imprimir Documentación
                                    </button>
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
                                <div class="col-xs-3"><span name="Cop_Fec" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Numero:</label>
                                <div class="col-xs-4"><span name="Cop_Num" type="text" class="form-control input-xs "></span></div>
                                <label class="col-xs-2 control-label label-xs">Autorización:</label>
                                <div class="col-xs-3"><span name="Cop_Aut" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Proveedor:</label>
                                <div class="col-xs-9"><span name="proveedor" type="text" class="form-control input-xs "></span></div>
                            </div>
                            <table id="copresult"></table>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset" id="retForm">
                            <legend class="Titulos2">Datos de la Retención</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Numero:</label>
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
                                    <label class="col-xs-3 control-label label-xs">Cód. Comp.:</label>
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
                                    <label class="col-xs-3 control-label label-xs">Observación:</label>
                                    <div class="col-xs-9"><span name="Com_Con" type="text" class="form-control input-xs "></span></div>
                                </div>
                                <table id="asiento"></table>
                            </fieldset>
                        </div>
                    <?php } ?>
                    <?php if ($configs['Cof_Con'] == 'S') { ?>
                        <div class="col-xs-6" id="compFormRet">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Comprobante de Retenci&oacute;n</legend>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Cód. Comp.:</label>
                                    <div class="col-xs-3"><span name="Codigo_Ret" type="text" class="form-control input-xs "></span></div>
                                    <label class="col-xs-3 control-label label-xs">Fecha:</label>
                                    <div class="col-xs-3"><span name="Com_Fec_Ret" type="text" class="form-control input-xs "></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Asiento:</label>
                                    <div class="col-xs-4"><span name="Tia_Des_Ret" type="text" class="form-control input-xs "></span></div>
                                    <label class="col-xs-2 control-label label-xs">Valor:</label>
                                    <div class="col-xs-3"><span name="Com_Val_Ret" type="text" class="form-control input-xs "></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Observación:</label>
                                    <div class="col-xs-9"><span name="Com_Con_Ret" type="text" class="form-control input-xs "></span></div>
                                </div>
                                <table id="asientoRet"></table>
                            </fieldset>
                        </div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(function() {
            checkFechaIva('<?php echo $hoy; ?>');
            checkCuentaPago();
            validaRetNum();
        });
    </script>
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTO-->
    <div id="proDialog" title="B&uacute;squeda de Productos">
        <form class="form-horizontal normal"><input type="text" name="Cop_Fec" class="Cop_Fec" value="<?php echo $hoy; ?>" style="display: none;" /></form>
    </div>
    <!-- FIN DEL DIALOGO PRODUCTO-->
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
    <div id="provDialog" title="B&uacute;squeda de Proveedor"></div>
    <!-- Negociaciones-->
    <div id="negDialog" title="B&uacute;squeda de Negociación">
        <form id="frm_nego" name="frm_nego" class="form-horizontal normal" action="javascript:$('#containerNegoci').Search('#frm_nego','negociacionesAjax'); ">
            <fieldset class="exa-fieldset" id="prodFormTemp">
                <div class="col-xs-12 col-sm-12">
                    <legend class="Titulos2">B&uacute;squeda</legend>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <div class="input-group">
                                <input id="search" name="search" onkeydown=" this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
                                <span class="input-group-btn">
                                    <button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="-1">
                                        <span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <input type="text" tabindex="-1" style="display:none;">
                </div>
            </fieldset>
        </form>
        <table id="containerNegoci"></table>
    </div>

    <!-- Preliquidacion Dialog -->
    <div id="prlDialog" title="B&uacute;squeda de Preliquidaci&oacute;n">
        <form id="frm_prl" name="frm_prl" class="form-horizontal normal" action="javascript:$('#containerPrl').Search('#frm_prl','preliquidacionAjax'); ">
            <fieldset class="exa-fieldset" id="prodFormTemp">
                <div class="col-xs-12 col-sm-12">
                    <legend class="Titulos2">B&uacute;squeda</legend>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <div class="input-group">
                                <input id="search" name="search" onkeydown=" this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
                                <span class="input-group-btn">
                                    <button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Preliquidación" tabindex="-1">
                                        <span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <input type="text" tabindex="-1" style="display:none;">
                </div>
            </fieldset>
        </form>
        <table id="containerPrl"></table>
    </div>
    <script>
        function selectProvee(provee) {
            var reset = ($('#reset').val() !== '0');
            $('#provFormTemp').setData($.extend(provee, {
                op_opciones: 'c'
            })).find('.dialogSearch').addClass('x');
            $('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Con'] === 'S' ? 'ok green' : 'remove blue'));
            $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Esp'] === 'S' ? 'ok green' : 'remove blue'));
            if (provee['Prv_Esp'] === 'S') {
                $.alert("ADVERTENCIA: Contribuyente especial no se debe retener IVA");
            }
            $('#provDialog').dialog('close');
            if (provee['Prv_Cod']) {
                $.ajax({
                    url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                    type: 'POST',
                    data: {
                        saldoCCxPP: true,
                        Prv_Cod: provee['Prv_Cod'],
                        Cop_Fec: $('#Cop_Fec').val()
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        $("#Prv_Sal").val("Calculando...");
                    },
                    success: function(res) {
                        if (res && res.success) {
                            $('#Prv_Sal').val("$ " + res.totalSaldo);
                        } else {
                            $('#Prv_Sal').val("$ 0.00");
                        }
                    }
                });
            }

            $('#docuFormTemp').setData({
                For_Cod: 1,
                Tri_Cod: 2
            }, reset).find(':input').removeAttr('readonly');
            if (reset) {
                $('#docuFormTemp').setData({
                    Cop_Fec: '<?php echo $hoy; ?>',
                    Com_Fec: '<?php echo $hoy; ?>'
                });
                // Establecer Ret_Fec con la fecha actual por defecto
                var hoy = new Date();
                var mes = (hoy.getMonth() + 1);
                var dia = hoy.getDate();
                var fechaActual = hoy.getFullYear() + '-' + (mes < 10 ? '0' : '') + mes + '-' + (dia < 10 ? '0' : '') + dia;
                $('#Ret_Fec').val(fechaActual);
                $('#Cop_Fec').trigger('change');
            }
            if (provee.op_ide === '01') $('#op_ide1').prop('checked', true).trigger('change');
            if (provee.op_ide === '02') $('#op_ide2').prop('checked', true).trigger('change');
            if (provee.op_ide === '03') $('#op_ide3').prop('checked', true).trigger('change');
            if ($.isEmpty(provee.op_ide)) {
                if (provee.Prs_Ced.length == 13) $('#op_ide1').prop('checked', true).trigger('change');
                if (provee.Prs_Ced.length == 10) $('#op_ide2').prop('checked', true).trigger('change');
            }
            $.getDataJson('', {
                'cargarDefault': true,
                'Prv_Cod': provee['Prv_Cod']
            }, function(res) {
                if (res['rows'] != null) {
                    if (res['rows']['Tri_Cod'] != null) {
                        $("#Tri_Cod").val(res['rows']['Tri_Cod']);
                    }
                    if (res['rows']['Tic_Cod'] != null) {
                        $("#Tic_Cod").val(res['rows']['Tic_Cod']);
                    }
                    if (res['rows']['Prd_Aut'] != null) {
                        $("#Cop_Aut").val(res['rows']['Prd_Aut']);
                    }
                    if (res['rows']['Ciu_Cod'] != null) {
                        $("#Ciu_Cod").val(res['rows']['Ciu_Cod']).trigger("chosen:updated");
                    }
                    if (res['rows']['Prd_Imp'] != null) {
                        $("#Cop_Imf").val(res['rows']['Prd_Imp']);
                    }
                    if (res['rows']['Prd_Cad'] != null) {
                        $("#Cop_Cad").val(res['rows']['Prd_Cad']);
                    }
                }

            }, function(err) {
                console.log(err['message']);
            });

            $('#Ciu_Cod').trigger('chosen:updated');
            //$('.validate:not(.ret_num)').find('i').removeAttr('class');
            $('#For_Cod').val(1).removeAttr('disabled').trigger('change');
            $('.pagoCredito').hide();
            $('#Cpp_Ven').removeAttr('required');
            $('#Pag_Pld').removeAttr('disabled');
            validaCopNum();
            checkLiquidacion();
        }

        function clearDocument() {
            $('.formDatos:not(.footerFact)').setData({
                op_opciones: 'c',
                Cal_Inv: 'N'
            });
            $('#docuFormTemp').setData({
                For_Cod: 1,
                Tri_Cod: 2,
                Cop_Fec: '<?php echo $hoy; ?>',
                Com_Fec: '<?php echo $hoy; ?>'
            }).find(':input').attr('readonly');
            // Establecer Ret_Fec con la fecha actual por defecto
            var hoy = new Date();
            var mes = (hoy.getMonth() + 1);
            var dia = hoy.getDate();
            var fechaActual = hoy.getFullYear() + '-' + (mes < 10 ? '0' : '') + mes + '-' + (dia < 10 ? '0' : '') + dia;
            $('#Ret_Fec').val(fechaActual);
            $('#Cop_Fec').trigger('change');
            $('#Ciu_Cod').trigger('chosen:updated');
            $('.validate').find('i').removeAttr('class');
            gridFact.clearGrid();
            $('#asumirRet').prop('checked', false).hide();
            $('#Cop_Aut').attr('title', '');
            addItem({});
            validaRetNum();

            $('.claveExterna').css('display', 'none');
            $('#claveAccesoExt').val("").attr('required', false);
            $("#isClaveExterna").val("");

            // Limpiar los campos contenidos en footerFact
            $('#c_tresxmil').prop('checked', false);
            $('#t_imp_combustible').val("0.00");
            $('#t_iva_pres').val("0.00");
            $('#ch_prop').prop('checked', false);
            $('#t_prop').val("0.00");
            $('#ch_adic').prop('checked', false);
            $('#t_adic').val("0.00");
        }
    </script>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
    <div id="codiDialog" title="B&uacute;squeda de Códigos Retención">
        <form class="form-horizontal normal"><input type="text" name="Cop_Fec" class="Cop_Fec" value="<?php echo $hoy; ?>" style="display: none;" />
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

    <div id="provCreateDialog" title="Registrar Proveedor" style="display:none;">
        <input id="reset" name="reset" type="hidden" class="text" />
        <form class="form-horizontal normal" id="provCreateForm" action="javascript:if(ValidacionCedulaRucService.esIdentificacionValida($('#Prs_Ced').val())['success']){ guardaProvee(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos del Proveedor</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                    <div class="col-xs-5">
                        <div class="input-group input-group-xs">
                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="if(ValidacionCedulaRucService.esIdentificacionValida(this.value)['success'] && ValidacionCedulaRucService.esIdentificacionValida(this.value)['tipo_abrev'] !== 'PA'){ $('#Ide_Cod').val(this.value.length===10?2:1); $('#Prv_Tic').val(ValidacionCedulaRucService.esIdentificacionValida(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchProvee(this.value); }else{ searchProvee(this.value); $('#Ide_Cod').val(3); $('#Prv_Tic').val('');};" required="" />
                            <span class="input-group-addon validate"><i></i></span>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="checkbox check-big" style="position:absolute;">
                            <label><input type="checkbox" name="Prv_Esp" value="S" offval="N">Contr. Esp.</label>
                            <label><input type="checkbox" name="Prv_Ris" value="S" offval="N" disabled>RISE</label>
                            <label><input type="checkbox" name="Prv_Reg" value="S" offval="N" disabled>Reg. Micro.</label>
                            <label><input type="checkbox" name="Prv_Con" value="S" offval="N">Obli. Cont.</label>

                            <label><input type="checkbox" name="Prv_Rim_Emp" value="S" offval="N" onclick="toggleCheckbox('Prv_Rim_Np')">RIMPE Emprendedor</label>
                            <label><input type="checkbox" name="Prv_Rim_Np" value="S" offval="N" onclick="toggleCheckbox('Prv_Rim_Emp')">RIMPE Neg. Popular</label>
                            <label><input type="checkbox" name="Prv_Ag_Ret" value="S" offval="N">Agente Retención</label>
                            <label><input type="checkbox" name="Prv_Gct" value="S" offval="N">Gran. Contribuyente</label>

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
                        <select id="Prv_Tic" name="Prv_Tic" class="form-control input-xs" required="" onchange="if(this.value==='N'){ $('.juridico').hide();$('.natural').show(); }else{ $('.natural').hide();$('.juridico').show(); }">
                            <option value="N">NATURAL</option>
                            <option value="J">JURIDICO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Razón Social:</span></label>
                    <div class="col-xs-5"><input name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs">Nombres:</label>
                    <div class="col-xs-5"><input name="Prs_Nom" type="text" class="form-control input-xs" /></div>
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
                <div class="form-group juridico">
                    <label class="col-xs-3 control-label label-xs">Nomb.Comerc.:</label>
                    <div class="col-xs-5"><input name="Prv_Com" type="text" class="form-control input-xs" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs">Tipo Actividad:</label>
                    <div class="col-xs-5">
                        <div class="input-group input-group-xs">
                            <select name="Prv_Tac" id="Prv_Tac" class="form-control input-xs">
                                <option value="">— Seleccionar —</option>
                                <?php foreach ($listaActividad as $row) {
                                    $tac = isset($row['Prv_Tac']) ? $row['Prv_Tac'] : '';
                                    $tacEsc = htmlspecialchars((string) $tac, ENT_QUOTES, 'UTF-8');
                                    echo '<option value="' . $tacEsc . '">' . $tacEsc . '</option>';
                                } ?>
                            </select>
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-info btn-xs" title="Agregar actividad" onclick="abrirDialogoPrvTacFact(); return false;" tabindex="-1"><i class="glyphicon glyphicon-plus"></i></button>
                            </span>
                        </div>
                    </div>
                </div>
            </fieldset>
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos de Ubicación</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                    <div class="col-xs-4">
                        <select name="Ciu_Cod" class="form-control input-xs" required="">
                            <option value=""></option>
                            <?php foreach ($rs_ciudad as $row) {
                                echo "<option value='{$row['Ciu_Cod']}' data-prov='{$row['Pro_Nom']}'>" . utf8_encode($row['Ciu_Des']) . "</option>";
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
    <!-- FIN DEL DIALOGO PROVEEDOR-->
    <div id="provTacCreateDialogFact" style="display:none;" title="Nueva actividad económica">
        <div style="padding:10px 6px 4px;border-radius:10px;background:linear-gradient(180deg,#fafbfd 0%,#ffffff 55%);border:1px solid rgba(0,0,0,.06);box-shadow:0 4px 18px rgba(15,23,42,.06);">
            <p style="margin:0 0 14px;font-size:13px;line-height:1.45;color:#64748b;">Describe la actividad en pocas palabras. El texto se guardará tal cual en el proveedor.</p>
            <div class="form-group" id="prvTacDescGroupFact" style="margin-bottom:0;">
                <label for="prvTacDescInputFact" style="font-size:12px;font-weight:600;color:#334155;margin-bottom:6px;display:block;">Actividad <span class="text-danger">*</span></label>
                <input type="text" id="prvTacDescInputFact" class="form-control input-xs" maxlength="20" autocomplete="off" placeholder="Ej. Comercio al por menor" />
                <div id="prvTacDescMeterFact" style="margin-top:6px;font-size:11px;color:#94a3b8;text-align:right;"><span id="prvTacDescCountFact">0</span> / 20 caracteres</div>
                <span id="prvTacDescErrFact" class="help-block text-danger" style="display:none;margin-top:8px;font-size:12px;"></span>
            </div>
        </div>
    </div>
    <?php include("../COMPONENTES/facComReembolsos.php"); ?>

    <div id="loadXml" title="Cargar Documento Electronico">
        <form id="formElectronico" class="form-horizontal normal">
            <div class="form-group">
                <label class="col-xs-12 control-label label-sm">Agrupar Detalle: <input type="checkbox" id="agrupa" value="S" class="check-big" /></label>
            </div>

            <div id='fileXML'>
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Cargar Archivo XML</legend>
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-sm">XML:</label>
                        <div class="col-xs-10">
                            <input name="file" type="file" class="form-control input-sm" accept="text/xml" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-2"></div>
                        <div class="col-xs-10"><button type="button" onclick="loadElectronico()" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-upload"></i> Cargar Archivo</button></div>
                    </div>
                </fieldset>
            </div>

            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Cargar por Clave de Acceso</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-sm">Clave:</label>
                    <div class="col-xs-10">
                        <input id="clave" name="clave" type="text" class="form-control input-sm" />
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-2"></div>
                    <div class="col-xs-10"><button type="button" onclick="loadElectronico()" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-upload"></i> Cargar Clave Accesso</button></div>
                </div>
            </fieldset>
        </form>
        <div class="alert alert-danger" style="display:none; margin-bottom: 0;">Error: <span id="alertXml"></span></div>
    </div>
    <script>
        function validaRetFec() {
            var Ret_Fec = $('#Ret_Fec').val(),
                Aut_Cad = $('#Ret_Fec').data('Aut_Cad');
            if ($.varValid(Aut_Cad) && Aut_Cad.length > 0) {
                if (Ret_Fec > Aut_Cad) {
                    $('#Ret_Fec').createFlyout('No puede ser mayor a <u class="orange">' + Aut_Cad + '</u> !', {
                        icon: 'exclamation',
                        placement: 'right_bottom'
                    });
                    $('#Ret_Fec').val($('#Cop_Fec').val()).flyout('show');
                    Ret_Fec = Aut_Cad;
                }
            }
        }
        function actualizarContadorPrvTacFact(maxLen) {
            var n = $('#prvTacDescInputFact').val().length;
            $('#prvTacDescCountFact').text(n);
            var $m = $('#prvTacDescMeterFact');
            if (n >= maxLen) {
                $m.css({ color: '#dc2626', fontWeight: 600 });
            } else {
                $m.css({ color: '#94a3b8', fontWeight: '' });
            }
        }
        function abrirDialogoPrvTacFact() {
            $('#provTacCreateDialogFact').dialog('open');
        }
        function aplicarNuevaPrvTacFact() {
            var maxLen = 20;
            var $inp = $('#prvTacDescInputFact');
            var desc = $.trim($inp.val()).toLocaleUpperCase('es');
            var $grp = $('#prvTacDescGroupFact');
            var $err = $('#prvTacDescErrFact');
            $err.hide().text('');
            $grp.removeClass('has-error');
            if (!desc.length) {
                $err.text('Ingrese una descripción.').show();
                $grp.addClass('has-error');
                $inp.trigger('focus');
                return false;
            }
            if (desc.length > maxLen) {
                $err.text('La actividad no puede superar ' + maxLen + ' caracteres.').show();
                $grp.addClass('has-error');
                return false;
            }
            var $sel = $('#provCreateForm #Prv_Tac');
            var $matchOpt = null;
            $sel.find('option').each(function() {
                var v = $(this).val();
                if (v === '') return true;
                if ($.trim(v).toLocaleUpperCase('es') === desc) {
                    $matchOpt = $(this);
                    return false;
                }
            });
            if ($matchOpt && $matchOpt.length) {
                if ($matchOpt.val() !== desc) $matchOpt.attr('value', desc).text(desc);
            } else {
                $sel.append($('<option></option>').attr('value', desc).text(desc));
            }
            $sel.val(desc).trigger('chosen:updated');
            return true;
        }
        $('#loadXml').createDialog({
            width: 500,
            height: 250,
            icon: 'fa fa-globe'
        });
        $('#negDialog').dialog({
            autoOpen: false
        });

        function loadElectronico() {
            var formData = new FormData(document.getElementById("formElectronico"));
            formData.append("loadElectronico", true);
            formData.append("agrupa", $('#agrupa').is(':checked') ? 'S' : 'N');
            $("#loader").show();
            $.ajax({
                url: "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",
                type: "post",
                dataType: "json",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(re) {

                    if (re.success === true) {
                        clearDocument();
                        $('#formDocumento').setData(re.data);
                        $('#Cop_Fec').trigger('change');
                        $('#Cop_Irb').val(re.data['Cop_Irb']);
                        $('#Cop_Des').val('0.00');
                        $('#t_descuento').val('0.00');
                        $('#ch_prop').prop('checked', (re.data['propina'] && parseFloat(re.data['propina']) != 0) ? true : false); //nuevo campo
                        $('#t_prop').val(re.data['propina'] ? re.data['propina'] : '0.00'); // nuevo campo
                        // nuevo campo t_noiva (ojo: usamos t_noiva y no noiva)
                        $('#t_noiva').val(re.data['t_noiva'] ? re.data['t_noiva'] : '0.00'); // total No Objeto IVA
                        $('#idCargaExitosa').val(re.data['idCargaExitosa']);
                        $('#reset').val(0);
                        if (!$.varValid(re.data['Prv_Cod']) || re.data['Prv_Cod'] === '') {
                            $('#provCreateForm').setData(re.data);
                            $('#provCreateForm #Prv_Tac').trigger('chosen:updated');
                            $('#Ide_Cod').val(re.data['Prs_Ced'].length === 10 ? 2 : 1);
                            $('#Prv_Tic').val(ValidacionCedulaRucService.esIdentificacionValida(re.data['Prs_Ced'])['tipo_abrev'] === 'NA' ? 'N' : 'J').trigger('change');
                            if ($.varValid(re.data['Prs_Cod']) && re.data['Prs_Cod'] !== '') $('#Prs_Ced').trigger('change');

                            // Bloquear Prs_Ced si viene de carga masiva
                            if ($.varValid(re.data['idCargaExitosa']) && re.data['idCargaExitosa'] !== '') {
                                $('#Prs_Ced').prop('readonly', true).addClass('readOnly');
                            } else {
                                $('#Prs_Ced').prop('readonly', false).removeClass('readOnly');
                            }

                            $('#provCreateDialog').dialog('open');
                        } else {
                            selectProvee(re.data);
                        }
                        $.jgrid.inlineEdit = {
                            focusField: false
                        };
                        $.each(re.items, function(i, v) {
                            var lastId = gridFact.jqGrid('getCol', 'index', false, 'max');
                            gridFact.jqGrid('saveRow', lastId, false, 'clientArray');
                            gridFact.changeRow(lastId, $.extend(v, v['Iva_Por'] * 1 > 0 ? {
                                Iva_Cod: v['Iva_Cod'],
                                Iva_Por: v['Iva_Por'],
                                /*  Iva_Cod: $('#Iva_Cod').val(),
                                  Iva_Por: $('#Iva_Cod option:selected').data('ivapor'),*/
                                Cop_Ice: null
                            } : {
                                Iva_Ren_Cod: '',
                                Iva_Ren_Con: '',
                                Iva_Ren_Por: '',
                                Iva_Ren_Sri: '',
                                Cop_Ice: null
                            }), {}, true);
                            gridFact.jqGrid('editRow', lastId);
                            addItem({});
                        });
                        //gridFact.find('tr#'+gridFact.jqGrid('getCol','index',false,'max')).hide();
                        $('#formElectronico').setData({});
                        $('#loadXml').dialog('close');
                        $('#Tic_Cod').trigger('change');
                    } else {
                        $.alert(re.message);
                    }
                },
                error: function() {
                    $.alert();
                },
                complete: function() {
                    $("#loader").fadeOut("slow");
                }
            });
        }
    </script>
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script>
        $.clearValidate();
    </script>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
    <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
    <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />

    <!--carga automatica del documento -->
    <script type="text/javascript">
        <?php
        $claveCarga =  $_SESSION['claCargar'];
        $cargaMasiva = $_SESSION['cargaMasiva'];
        unset($_SESSION['claCargar']);
        unset($_SESSION['cargaMasiva']);
        if ($cargaMasiva == true) {
        ?>
            $('#cargarElectronico').click();
            $('#fileXML').hide();
            $('#clave').val("<?php echo $claveCarga; ?>");
            $('#clave').prop('readonly', 'true');
            //loadElectronico();
            $('#Cop_Num').prop('disabled', 'true');
            $('#Cop_Aut').prop('disabled', 'true');
        <?php
            $cargaMasiva = false;
        }
        ?>
        //Solo se puede seleccionar RIMPE Emprendedor o Negocio popular
        function toggleCheckbox(otherCheckboxName) {
            const otherCheckbox = document.querySelector(`input[name="${otherCheckboxName}"]`);
            otherCheckbox.checked = false;
        }
        //Ver negociaciones
        var containerNegoci = $("#containerNegoci");
        var containerPrl = $("#containerPrl");

        $(function() {
            $('#provCreateForm #Prv_Tac').createChosen('input-xs', {
                width: '100%',
                placeholder_text_single: '- Seleccionar -',
                search_contains: true
            });
            var PRV_TAC_MAX_FACT = 20;
            $('#provTacCreateDialogFact').createDialog({
                title: 'Nueva actividad económica',
                width: 440,
                height: 260,
                noTitleStuff: false,
                icon: 'plus-sign',
                buttons: [{
                    text: 'Agregar',
                    icons: { primary: 'ui-icon-check' },
                    click: function() {
                        if (aplicarNuevaPrvTacFact()) $(this).dialog('close');
                    }
                }, {
                    text: 'Cancelar',
                    icons: { primary: 'ui-icon-closethick' },
                    click: function() { $(this).dialog('close'); }
                }],
                afterOpen: function() {
                    var $inp = $('#prvTacDescInputFact');
                    $inp.val('');
                    $('#prvTacDescGroupFact').removeClass('has-error');
                    $('#prvTacDescErrFact').hide().text('');
                    actualizarContadorPrvTacFact(PRV_TAC_MAX_FACT);
                    setTimeout(function() { $inp.trigger('focus'); }, 80);
                }
            });
            $(document).on('input', '#prvTacDescInputFact', function() {
                actualizarContadorPrvTacFact(PRV_TAC_MAX_FACT);
            });
            
            $('#prlDialog').dialog({
                autoOpen: false,
                width: 600,
                height: 400
            });

            armargrid();
        });

        function armargrid() {
            containerNegoci.createGrid({
                width: 260,
                height: 140,
                colModel: [{
                        label: 'Cod.Cop',
                        name: 'Cod_Neg',
                        width: 50
                    },
                    {
                        label: 'Num.Agu',
                        name: 'Num_Neg',
                        width: 80
                    },
                    {
                        label: '&nbsp;',
                        name: 'act1',
                        width: 30,
                        align: 'center',
                        viewable: false,
                        formatter: 'gridButton',
                        formatoptions: {
                            action: selectNego
                        }
                    },
                ],
                jsonReader: {
                    root: "response",
                    repeatitems: false
                },
                datatype: "local",
                footerrow: false
            });
            
            containerPrl.createGrid({
                width: 550,
                height: 250,
                colModel: [
                    { label: 'Cod.Prl', name: 'Mal_Cod', width: 50 },
                    { label: 'Num.Prl', name: 'Mal_Num', width: 80 },
                    { label: 'Fec.Prl', name: 'Mal_Fec', width: 80 },
                    { label: 'Obs.Prl', name: 'Mal_Obs', width: 200 },
                    { label: 'Total', name: 'Mal_Tot_Cob', width: 80, align: 'right' },
                    { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectPrl } }
                ],
                jsonReader: { root: "response", repeatitems: false },
                datatype: "local",
                footerrow: false
            });
        }

        function selectNego(data) {
            $('#Num_Neg').val(data['Num_Neg']);
            $('#Cod_Neg').val(data['Cod_Neg']);
            $('#negDialog').dialog('close');
        }
        
        function selectPrl(data) {
            $('[name="Cod_Prl"]').val(data['Mal_Cod']);
            $('[name="Num_Prl"]').val(data['Mal_Num']);
            
            $('[name="Cop_Obs"]').val(data['Mal_Num']);
            $('[name="Cpp_Obs"]').val(data['Mal_Num']);
            $('#Cop_Obs').val(data['Mal_Num']);
            
            $('#prlDialog').dialog('close');
        }

        function limpiarCamposPrl() {
            $('[name="Cod_Prl"]').val("");
            $('[name="Num_Prl"]').val("");
        }
    </script>

</BODY>

</HTML>