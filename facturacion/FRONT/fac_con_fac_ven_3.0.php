<?php

/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factura.php');
require_once('../LOGICA/tes_log_kardex.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

// ----- PARA EL TAB2 - TOTALES ----- //

/* Creacion del objeto mysql para las consultas */
$obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos(true);
$obBD_con2 = new Class_Log_Datos_facturaVenta;

$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* ver si exite un cliente */
if (isset($searchCliente)) {
    $responce = $obBD_con2->getRowConsulta(177, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con2->getRowConsulta(188, $responce['Prs_Cod'] . '*' . $Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Cli_Cod'])) ? $responce['existe'] = true : $responce['existe'] = false;
    $obBD_con1->echoJson($responce);
}

// -- NUEVO VER SI ME SIRVE -- //
if (isset($getDateServ)) {
    $resp['hoy'] = date("Y-m-d");
    $obBD_con1->echoJson($resp);
}

//Listar los clientes registrados en la empresa
if (isset($clieAjax)) {
    $response = $obBD_con2->getPageGridJson(1, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
    $Sel = $obBD_con1->select()->from('viaje', array('Viajes' => $obBD_con1->expr('COUNT(Via_Cod)')));
    foreach ($response['rows'] as &$v) {
        $Sel->unsetWhere()->where("Cli_Cod=? AND Via_Est='A' AND Vet_Cod IS NULL", $v['Cli_Cod']);
        $via = $obBD_con1->getRowConsulta(null, $Sel, $obBD_conexion);
        $v['Viajes'] = $via['Viajes'];
    }
    unset($v);
    $obBD_con1->echoJson($response);
}

/* ver si exite un cliente */
if (isset($clieAjax2)) {
    $responce['rows'] = $obBD_con2->getArrayConsulta(2, $Prs_Ced . '*' . $Ses_Emp_Cod, $obBD_conexion, true);
    $responce['total'] = count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

// -- NUEVO VER SI ME SIRVE -- //
if (isset($existeNumdoc)) {
    $rs_numdocumento = $obBD_con2->getRowConsulta(11, $Ses_Suc_Cod . '*' . $Aut_Sri . '*' . $Vet_Num . '*' . (isset($Vet_Cod) ? $Vet_Cod : '') . '*' . $Pun_Sri, $obBD_conexion);
    if ($rs_numdocumento['total'] * 1 > 0) {
        $response['existe'] = true;
    } else {
        $response['existe'] = false;
    }
    echo json_encode($response);
    exit();
}

// -- NUEVOS VER SI ME SIRVE -- /
//Sección para obtener los ivas de la tabla iva 
$ivas = $obBD_con2->getArrayConsulta(16, "", $obBD_conexion);
/* Configuraciones de la Empresa */
$configs = $obBD_con2->getRowConsulta(12, $Ses_Emp_Cod, $obBD_conexion);

// -- NUEVO VER SI ME SIRVE -- /
/* Consulta los productos */
if (isset($proAjax)) {
    if (!empty($Caj_Fec)) $Pec_Cop = $obBD_con2->getRowConsulta(78, $Ses_Emp_Cod . '*' . $Caj_Fec, $obBD_conexion);
    else $Pec_Cop = array('Pla_Cod' => null);
    $contar = $obBD_con2->getRowConsulta(13, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*', $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con2->getArrayConsulta(13, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*' . $pagination['limits'], $obBD_conexion);
        foreach ($responce['rows'] as &$r) {
            /*$r['Precios']=$obBD_con1->getArrayConsulta(14, $Ses_Suc_Cod.'*'.$r['Pro_Cod'].'*'.'A', $obBD_conexion);
            $precio = $obBD_con1->getRowConsulta(14, $Ses_Suc_Cod.'*'.$r['Pro_Cod'].'*'.'A'.'*'.'D'.'*', $obBD_conexion);
            if(!empty($precio['Pre_Pvp'])){
                $r=array_merge($r,$precio);
                $r['Vet_Pru']=$r['Pre_Pvp'];
            }*/

            if (isset($Bodega_Cod) and $Bodega_Cod != '') {
                $tipoBod = $obBD_con1->getRowConsulta('bodega.selectWhere', array('clean' => true, 'where' => array('Suc_Cod' => $Ses_Suc_Cod, 'Bod_Cod' => $Bodega_Cod, 'Bod_Est' => 'A')), $obBD_conexion);
                if ($tipoBod['Bod_Tip'] == 'P') {
                    $bodega = ' AND (kardex_ie.Bod_Cod is null or kardex_ie.Bod_Cod=' . $Bodega_Cod . ')';
                } else {
                    $bodega = ' AND kardex_ie.Bod_Cod=' . $Bodega_Cod;
                }

                $stockProducto = $obBD_con1->getRowConsulta('kardex_ie.12', array('Pro_Cod' => $r['Pro_Cod'], 'Bodega' => $bodega), $obBD_conexion);
                $r['Stk_Can'] = round($stockProducto['Stk_Can'], 2);
            }

            $r['Precios'] = array(0 => array(
                'Pre_Cod' => $r['Pre_Cod'],
                'Pre_Des' => $r['Pre_Des'],
                'Pre_Est' => $r['Pre_Est'],
                'Pre_Fin' => $r['Pre_Fin'],
                'Pre_Ini' => $r['Pre_Ini'],
                'Pre_Pvp' => $r['Pre_Pvp'],
                'Tpv_Cod' => $r['Tpv_Cod'],
                'Tpv_Des' => 'Standar'
            ));

            if ($configs['Cof_Con'] == 'S' && !empty($Pla_Cod)) {
                $cuenta = $obBD_con2->getRowConsulta(15, $Pla_Cod . '*' . $r['Pro_Cod'] . '*' . 'V', $obBD_conexion);
                if (!empty($cuenta['Pld_Cod'])) $r = array_merge($r, $cuenta);
            }
        }
        unset($r);
    }
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

/* Consulta los totales */
if (isset($ajaxTotales)) {
    $FILTERS = array();
    // array_push($FILTERS, $op_est != 'I' ? 'isActive' : 'isInactive'); //filtro solo de activo e inactivo

    // Filtro por estado (Activo, Inactivo, Todos)
    switch ($op_est) {
        case 'A': // Activos
            array_push($FILTERS, 'isActive');
            break;
        case 'I': // Inactivos
            array_push($FILTERS, 'isInactive');
            break;
        case 'T': // Todos (no aplica filtro)
        default:   // Por defecto muestra todos
            break;
    }

    if ($range == 'S') array_push($FILTERS, 'byDateRange');
    if ($Chk_Ret != 'T') array_push($FILTERS, 'hasRetencion');
    if ($Tic_Cod != 'T') array_push($FILTERS, 'byTipoCompr');
    if ($cedul == 'S') array_push($FILTERS, 'byCliCod');
    if ($Suc_Cod != 'T') array_push($FILTERS, 'bySucCod');
    if ($Vnd_Cod != 'V') array_push($FILTERS, 'byVndCod');
    if ($For_Cod != 'T') array_push($FILTERS, 'byTipPago');
    if ($Chk_Reem == 'S') array_push($FILTERS, 'hasReembolso');
    if ($Pun_Cod != 'T') array_push($FILTERS, 'byPunCod');
     if ($Pag_Cod != 'T') array_push($FILTERS, 'byPagCod');

    $response = $obBD_con1->getPageGrid('ventas', array_merge($_GET, array('where' => array(), 'setWhere' => array_merge($FILTERS, array(/*'setUsuario','setRetencion',*/'setTotales')))));
    $totalGlobal = $obBD_con1->getRowConsulta('ventas', array_merge($_GET, array('where' => array(), 'unsetCols' => array(/*'Vnd_Cod','Pun_Cod','Vendedor'*/), 'setWhere' => array_merge($FILTERS, array('setEmpCod',/*'setUsuario','setRetencion',*/ 'isSummary')))));
    $response['userdata'] = array_merge($totalGlobal, array('Vet_Obs' => '<div class="txtRight">TOTAL GLOBAL:</div>', 'Tot_Renta' => 0, 'Tot_Iva' => 0));

    // [OPT] El N+1 se eliminó: comprobante, pagos y retenciones vienen desde el SQL principal
    foreach ($response['rows'] as &$row) {
        $factorNC = (isset($row['Tic_Sri']) && $row['Tic_Sri'] === '04') ? -1 : 1;
        $response['userdata']['Tot_Renta'] += ($row['Tot_Renta'] * 1 * $factorNC);
        $response['userdata']['Tot_Iva'] += ($row['Tot_Iva'] * 1 * $factorNC);
    }
    $obBD_con1->echoJson($response);
}

/* busqueda de documentos */
if (isset($searchDocument)) {
    $data = $_GET;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $responce = $obBD_con2->getPageGrid(34, $data, $obBD_conexion);
    if ($responce['total'] > 0) {
        foreach ($responce['rows'] as &$row) {
            $row['Cpc_Edit'] = 'S';
            $row['Cpc_Min'] = 0;
            if (!empty($row['Cpc_Cod'])) {
                $Pagos1 = $obBD_con2->getRowConsulta(57, $row['Cpc_Cod'] . '*' . 'A', $obBD_conexion);
                if ($Pagos1['total'] * 1 > 0) {
                    $row['Cpc_Det'] = 'S'; //tiene pagos activos
                    $Pagos1 = $obBD_con2->getRowConsulta(57, $row['Cpc_Cod'] . '*' . 'A' . '*' . 'SUM', $obBD_conexion);
                    $row['Cpc_Min'] = round($Pagos1['total'] * 1, 2);
                }
                $Pagos2 = $obBD_con2->getRowConsulta(57, $row['Cpc_Cod'], $obBD_conexion);
                if ($Pagos2['total'] * 1 > 0) $row['Cpc_Edit'] = 'N'; //tiene algun pago vinculado
            }
            if ($configs['Cof_Con'] == 'S' && !empty($row['Com_Cod'])) {
                $cuentas = $obBD_con2->getRowConsulta(39, $row['Com_Cod'], $obBD_conexion);
                $row['Pld_Cod_Pag'] = $cuentas['Pld_Cod'];
                $otras_comp = $obBD_con2->getRowConsulta(65, $row['Com_Cod'], $obBD_conexion);
                if ($otras_comp['total'] * 1 > 1) $row['Com_Edit'] = 'N';
            }
        }
        unset($row);
    }
    $obBD_con2->echoJson($responce);
}

// -- NUEVO VER SI ME SIRVE -- /
/* Consulta el detalle del documento */
if (isset($docDetalle)) {
    $resp['Vet_items'] = $obBD_con2->getArrayConsulta(93, $Vet_Cod, $obBD_conexion);
    $obBD_con2->echoJson($resp);
}

// -- NUEVO VER SI ME SIRVE -- /
//Sección para extraer el Pun_Cod y Vnd_Cod del usuario sobre la tabla vendedor
$rs_Punto = $obBD_con2->getRowConsulta(7, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);

// -- NUEVO VER SI ME SIRVE -- /
if (isset($getDataPunto)) {
    $resp = $obBD_con2->getRowConsulta(7, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
    $obBD_con2->echoJson($resp);
}
// -- NUEVO VER SI ME SIRVE -- /
if (isset($autorizaAjax)) {
    $obBD_con2->getPageGridJson(100, $rs_Punto['Pun_Cod'] . '*' . $Tic_Cod, $obBD_conexion, $page, $rows);
}

if (isset($cargarDocumentos)) {
    if ($Aut_Cod == '') $Aut_Cod = 0;
    if ($Tic_Cod == '') $Tic_Cod = 0;
    $array_documentos = $obBD_con2->getArrayConsulta(8, $rs_Punto['Pun_Cod'] . '*' . $Aut_Cod, $obBD_conexion);
    if ($Tic_Cod > 0) {
        $array_doc = $obBD_con2->getArrayConsulta(101, $rs_Punto['Pun_Cod'] . '*' . $Aut_Cod . '*' . $Tic_Cod, $obBD_conexion);
        $array_documentos = array_merge($array_documentos, $array_doc);
    }
    echo json_encode($array_documentos);
    exit();
}

if (isset($cargarDoc)) {
    $responce = $obBD_con2->getRowConsulta(91, $vet_cod, $obBD_conexion);
    $responce['items'] = $obBD_con2->getArrayConsulta(93, $vet_cod, $obBD_conexion);
    $responce['Iva_Por'] = 0;
    foreach ($responce['items'] as $r) if ($r['Iva_Por'] * 1 > 0) {
        $responce['Iva_Por'] = $r['Iva_Por'];
        break;
    }
    // para viajes
    $Sel = $obBD_con1->select()->from('viaje', array('Viajes' => $obBD_con1->expr('COUNT(Via_Cod)')))->where("Cli_Cod=? AND Via_Est='A' AND (Vet_Cod IS NULL OR Vet_Cod=?)", array($Cli_Cod, $vet_cod));
    $via = $obBD_con1->getRowConsulta(null, $Sel, $obBD_conexion);
    $responce['Viajes'] = $via['Viajes'];
    $responce['Viajes_Sel'] = $obBD_con1->getArrayConsulta('viaje', array('Vet_Cod' => $vet_cod, 'setWhere' => array('isActive')), $obBD_conexion);
    if (is_array($responce['Viajes_Sel']) && count($responce['Viajes_Sel']) > 0) foreach ($responce['Viajes_Sel'] as &$r) {
        $prod = $obBD_con2->getRowConsulta(13, '' . '*' . $Ses_Emp_Cod . '*' . '' . "* AND producto.Pro_Cod=$r[Pro_Cod]", $obBD_conexion);
        if ($configs['Cof_Con'] == 'S' && !empty($Pla_Cod)) {
            $cuenta = $obBD_con2->getRowConsulta(15, $Pla_Cod . '*' . $r['Pro_Cod'] . '*' . 'V', $obBD_conexion);
            if (!empty($cuenta['Pld_Cod'])) $prod = array_merge($prod, $cuenta);
        }
        $r['Producto'] = $prod;
    }
    unset($r);
    foreach ($responce['items'] as &$r) {
        $viajes = $obBD_con1->getArrayConsulta('viaje', array('unsetCols' => true, 'addCols' => array('viaje' => 'Via_Cod'), 'Vet_Cod' => $vet_cod, 'Vet_Ite' => $r['Vet_Ite']), $obBD_conexion);
        $r['Viajes'] = is_array($viajes) && !empty($viajes) ? array_map(function ($e) {
            return $e['Via_Cod'];
        }, $viajes) : '';
    }
    unset($r);
    $responce['pagos'] = $obBD_con2->getArrayConsulta(92, $vet_cod, $obBD_conexion);
    if ($Aut_Cod == '') $Aut_Cod = 0;
    if ($Tic_Cod == '') $Tic_Cod = 0;
    $array_documentos = $obBD_con2->getArrayConsulta(8, $rs_Punto['Pun_Cod'] . '*' . $Aut_Cod, $obBD_conexion);
    if ($Tic_Cod > 0) {
        $array_doc = $obBD_con2->getArrayConsulta(101, $rs_Punto['Pun_Cod'] . '*' . $Aut_Cod . '*' . $Tic_Cod, $obBD_conexion);
        $array_documentos = array_merge($array_documentos, $array_doc);
    }
    $array_reembolsos = $obBD_con1->getArrayConsulta("venta_reembolsos.selectWhere", array('where' => array('venta_reembolsos.Vet_Cod' => $vet_cod)), $obBD_conexion);
    if (count($array_reembolsos) > 0) {
        $responce['reembolsos'] = array();
        foreach ($array_reembolsos as $val) {
            $array_cop = $obBD_con1->getRowConsulta('compras.selectWhere', array('where' => array('compras.Cop_Cod' => $val['Cop_Cod']), 'setWhere' => array('setTotales')), $obBD_conexion);
            array_push($responce['reembolsos'], $array_cop);
        }
    }
    $responce['documentos'] = $array_documentos;
    //$obBD_con1->echoLog($responce['documentos']);
    $responce['success'] = true;
    $obBD_con1->echoJson($responce); // si se deshabilita se pierde la informacion
}

/* Consulta de IMEI disponibles para un producto */
if (isset($imeiAjax)) {
    $Pro_Cod = isset($_GET['Pro_Cod']) ? trim($_GET['Pro_Cod']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $Ime_Tip = isset($_GET['Ime_Tip']) ? trim($_GET['Ime_Tip']) : '';
    
    if (!empty($Pro_Cod)) {
        $obBD_imei = new Class_Log_Datos_Imei();
        $obBD_conexion_imei = new Class_Log_Conexion_Imei($Ses_Dat_Dis);
        
        // Obtener IMEI: Pro_Cod * Suc_Cod
        $imeis = $obBD_imei->getArrayConsulta(11, $Pro_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion_imei);
        
        // Filtrar según el tipo seleccionado
        $imeis_filtrados = array();
        if (is_array($imeis) && count($imeis) > 0) {
            foreach ($imeis as $imei) {
                $ime_tip = isset($imei['Ime_Tip']) ? trim($imei['Ime_Tip']) : '';
                $vet_cod = isset($imei['Vet_Cod']) ? $imei['Vet_Cod'] : null;
                
                // Aplicar filtro de tipo
                $pasa_filtro_tipo = true;
                if (!empty($Ime_Tip)) {
                    if ($Ime_Tip == 'P') {
                        // Solo Pendiente: Ime_Tip = 'P' y sin Vet_Cod
                        $pasa_filtro_tipo = ($ime_tip == 'P' || empty($ime_tip)) && (empty($vet_cod) || $vet_cod == 0 || $vet_cod === null || $vet_cod === 'NULL');
                    } else if ($Ime_Tip == 'V') {
                        // Solo Vendido: Ime_Tip = 'V'
                        $pasa_filtro_tipo = ($ime_tip == 'V');
                    }
                }
                
                if ($pasa_filtro_tipo) {
                    // Filtrar por búsqueda si existe
                    if (empty($search) || 
                        stripos($imei['Ime_Num'], $search) !== false || 
                        stripos($imei['Ime_Tip_Des'], $search) !== false) {
                        $imeis_filtrados[] = array(
                            'Ime_Cod' => $imei['Ime_Cod'],
                            'Ime_Num' => $imei['Ime_Num'],
                            'Ime_Tip' => $imei['Ime_Tip'],
                            'Ime_Tip_Des' => $imei['Ime_Tip_Des'],
                            'Ime_Est' => $imei['Ime_Est']
                        );
                    }
                }
            }
        }
        
        // Paginación manual
        $total = count($imeis_filtrados);
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $rows = isset($_GET['rows']) ? intval($_GET['rows']) : 50;
        $offset = ($page - 1) * $rows;
        $imeis_paginados = array_slice($imeis_filtrados, $offset, $rows);
        
        $pagination = pages($total, $page, $rows);
        $response = $pagination['data'];
        $response['rows'] = $imeis_paginados;
        
        utf8_encode_deep($response['rows']);
        echo json_encode($response);
    } else {
        $response = array('total' => 0, 'page' => 1, 'records' => 0, 'rows' => array());
        echo json_encode($response);
    }
    exit();
}

// -- NUEVO VER SI ME SIRVE -- /
if (isset($cargarReportes)) {
    try {
        $response['reportes'] = $obBD_con2->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        $response['success'] = true;
    } catch (Exception $ex) {
        $response['message'] = $ex->getMessage();
    }
    $obBD_con2->echoJson($response);
}

// -- NUEVO VER SI ME SIRVE -- /
/* busqueda de documentos */
if (isset($comprasReembolsoAjax)) {
    $obBD_con1->getPageGridJson('compras.selectWhere', array_merge($_GET, array('where' => "", 'setWhere' => array('isActive', 'setTotales', "notInReembolsos"))), $obBD_conexion);
}

$rs_tip_compr = $obBD_con1->getArray('tipo_compr', array('Tic_Est' => 'A'));
$rs_periodo = $obBD_con1->getArrayConsulta("ventas.6", $Ses_Emp_Cod, $obBD_conexion);

$bodegas = $obBD_con1->getArrayConsulta('bodega.1', array('Suc_Cod' => $Ses_Suc_Cod, 'Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion);

// $rs_tipo = $obBD_con1->getArrayConsulta(69, '', $obBD_conexion);

// ----- PARA EL TAB4 - ULTIMAS VENTAS ----- //
/* Creacion del Objeto de conexion */
$obBD_conexion1 = new Class_Log_Conexion_Kar($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con3 =  new Class_Log_Datos_Kar;
/* Evita el reenvio */
$thisPost = new Post_Block;

if (isset($proAjax)) {
    $contar = $obBD_con3->getRowConsulta(1052, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*', $obBD_conexion1);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $responce['rows'] = $obBD_con3->getArrayConsulta(1052, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*' . $pagination['limits'], $obBD_conexion1);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

if (isset($ajaxProd)) {
    $Ite_Cod = $Pro_Cod;
    $ini = $hoy;
    $responce['success'] = true;
    $kardex1 = $obBD_con3->getArrayConsulta(1048, $ini . '*' . $Ite_Cod, $obBD_conexion1);
    if (count($kardex1) == 1 && $kardex1[0]['Saldo'] !== 0 && $kardex1[0]['Stock'] != 0) {
        $kardex1[0]['Promedio'] = round(($kardex1[0]['Saldo'] / $kardex1[0]['Stock']), 6);
    } else {
        $kardex1[0]['Promedio'] = 0;
        $kardex1[0]['Saldo'] = 0;
        $kardex1[0]['Stock'] = 0;
    }
    list($ann, $mes, $dia) = explode('[/.-]', $ini);
    $kardex1[0]['Kar_Det'] = '<b>Saldo al ' . $dia . ', de ' . mes($mes, 1) . ', ' . $ann . '</b>';
    $responce['stocks'] = $kardex1[0];

    $responce['prod'] = $obBD_con3->getRowConsulta(1051, $Ite_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion1);
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if (isset($ajaxKardex)) {
    $Ite_Cod = $Pro_Cod;
    if (!empty($Vnd_Cod)) $Vnd_Cod = " AND ventas.Vnd_Cod=$Vnd_Cod ";
    else $Vnd_Cod = '';
    $responce['rows'] = $obBD_con3->getArrayConsulta(1055, $Ses_Emp_Cod . '*' . $Ses_Suc_Cod . '*' . $Ite_Cod . '*' . $ini . '*' . $fin . '*' . $Vnd_Cod, $obBD_conexion1);
    $responce['success'] = true;
    $responce['records'] = count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

if (isset($ajaxDetalleVentas)) {
    $data = $_GET;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    // Mapear el orden personalizado si existe
    if (!empty($CustomOrderBy)) {
        $data['order'] = " ORDER BY " . $CustomOrderBy;
    } else {
        $data['order'] = " ORDER BY Vet_Num ASC, persona.Prs_Ape ASC, persona.Prs_Nom ASC";
    }
    $obBD_con2->getPageGridJson(269, $data, $obBD_conexion, $page, $rows);
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!-- <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE> -->
    <TITLE><?Php echo "Ventas Consultar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?php
    $mask_model = 'model1';
    require_once("../../mascaras/unified-loader.php");
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.5/jszip.min.js"></script>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script>
        //utilizacion de variable global que identifica el padre del archivo js validaciones
        <?php $array_documentos = $obBD_con1->getArrayConsulta(8, $rs_Punto['Pun_Cod'], $obBD_conexion, true); ?>
        var array_documentos = <?php echo json_encode($array_documentos); ?>,
            ivas_venta = <?php echo json_encode($ivas) ?>;;
        var edicion_ventas = true,
            vet_num_ant = 0,
            tic_cod_ant = 0;
        var docs, items = $('#itemsGrid'),
            pagos = $('#pagosGrid'),
            data = [],
            Vet_Index = 1,
            Vet_Selected, index, Cof_Con = '<?php echo $configs['Cof_Con']; ?>';
        var array_vendedor = <?php echo json_encode($rs_Punto); ?>;
        // Variable para identificar que estamos en fac_con_fac_ven_3.0.php
        var es_fac_con_fac_ven = true;
    </script>
     <script>
        var Ses_Emp_Cod = <?php echo json_encode($Ses_Emp_Cod); ?>;
    </script>
    <script type="text/ecmascript" src="../VALIDACIONES/fac_val_factura.js?x=0"></script>
    <style></style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Consultar Documentos de Ventas</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">

            <div id="documentoMain" style="visibility: hidden;">
                <?php include '../COMPONENTES/facVentFormEdit.php'; ?>
                <div class="row">
                    <div class="col-xs-12">
                        <button class="btn btn-sm btn-inverse btn-main" onclick="$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
                    </div>
                </div>
            </div>

            <div id="documentoSearch" class="ui-tabs ui-tab-fix noPaddingH">
                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">

                    <li><a href="#tab2"><i class="glyphicon glyphicon-th-list"></i> Totales</a></li>
                    <li><a href="#tab1"><i class="glyphicon glyphicon-list-alt"></i> Individual</a></li>
                    <li><a href="#tab3"><i class="fa fa-cart-arrow-down" style="font-size: 1.3em;"></i> Por Producto</a></li>
                    <li><a href="#tab4"><i class="glyphicon glyphicon-th-list" style="font-size: 1.3em;"></i> Detalle</a></li>
                </ul>
                <div id="tab1" class="ui-tabs-panel">
                    <div id="documentoSearch">
                        <form id="serachDocDorm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#serachDocDorm','searchDocument');">
                            <div class="row">
                                <input name="order" type="hidden" value="" />
                                <input name="fecha_inicio" type="hidden" value="" />
                                <input name="fecha_fin" type="hidden" value="" />

                                <div class="col-xs-6">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Búsqueda</legend>
                                        <div class="form-group">

                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Cliente&nbsp;&nbsp;&nbsp;</label>
                                                <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;&nbsp;</label>
                                                <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;No. Documento&nbsp;&nbsp;</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                            <div class="col-xs-7">
                                                <div class="input-group">
                                                    <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus class="form-control input-sm clearable submit" />
                                                    <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento" tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                                </div><!-- /input-group -->
                                            </div><input type="text" tabindex="-1" style="display:none;" />
                                        </div>
                                    </fieldset>
                                </div>
                                <div class="col-xs-6">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Filtros</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Documento:</label>
                                            <div class="col-xs-10">
                                                <select name="Tic_Cod" class="form-control input-xs">
                                                    <option value="">
                                                        << TODOS >>
                                                    </option>
                                                    <?php foreach ($rs_tip_compr as $row) {
                                                        if ($row['Tic_Sri'] != 4 && $row['Tic_Sri'] != 5 && $row['Tic_Sri'] != 7 && $row['Tic_Sri'] != 23 && $row['Tic_Sri'] != 24)
                                                            echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Periodo:</label>
                                            <div class="col-xs-3">
                                                <!-- <select name="Pec_Cod" class="form-control input-xs search_pec" onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled');"> -->
                                                <select name="Pec_Cod" class="form-control input-xs search_pec" onchange="if(this.value==='') { $('#Cmb_Mes').attr('disabled','disabled'); } else { $('#Cmb_Mes').removeAttr('disabled'); } calcularFechasFiltro();">
                                                    <option value="">
                                                        << TODOS >>
                                                    </option>
                                                    <?php $rs_perio = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
                                                    foreach ($rs_periodo as $row) { ?>
                                                        <option value="<?php echo $row['Pec_Cod']; ?>" data-inicio="<?php echo $row['Pec_Fei']; ?>" data-fin="<?php echo $row['Pec_Fef']; ?>"><?php echo $row['Anio']; ?></option>
                                                    <?php   } ?>
                                                </select>
                                            </div>
                                            <label class="col-xs-1 control-label label-xs">Mes:</label>
                                            <div class="col-xs-3">
                                                <!-- <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec" disabled="disabled"> -->
                                                <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec" disabled="disabled" onchange="calcularFechasFiltro();">
                                                    <option value="">
                                                        << TODOS >>
                                                    </option>
                                                    <?Php for ($i = 1; $i <= 12; $i++) { ?>
                                                        <option <?php if ($i == $mes) {
                                                                    echo "selected=''";
                                                                } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1); ?>
                                                        </option><?Php } ?>
                                                </select>
                                            </div>

                                            <label class="col-xs-2 control-label label-xs">Mis Ingresos</label>
                                            <div class="col-xs-1">
                                                <input type="checkbox" value="S" offval="N" id="mis_ingresos" name="mis_ingresos">
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </form>
                        <!-- BLOQUE DE CARGA DE DATOS PARA EL GRIDVIEW -->
                        <div style="min-height: 300px;">
                            <table id="searchGrid"></table> <!-- GRIDVIEW DE LOS PRODUCTOS -->
                            <table id="searchGridPager"></table> <!-- PAGINADOR DEL GRIDVIEW -->
                            <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop green"></span> Contiene Pagos | <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="fa fa-globe green"></span> Facturación Electronica Validada | <span class="glyphicon glyphicon-lock orange"></span> Formato Anterior</div>
                        </div>

                        <script>
                            function setOpt(val) {
                                if (val === 'd') $('.search_pec').attr('disabled', 'disabled');
                                else $('.search_pec').removeAttr('disabled');
                            }

                            // Función para calcular las fechas cuando cambian periodo y mes
                            function calcularFechasFiltro() {
                                var pecCod = $('select[name="Pec_Cod"]').val();
                                var cmbMes = $('#Cmb_Mes').val();
                                var fechaInicio = $('input[name="fecha_inicio"]');
                                var fechaFin = $('input[name="fecha_fin"]');
                                
                                // Limpiar fechas si no hay periodo seleccionado
                                if (!pecCod || pecCod === '') {
                                    fechaInicio.val('');
                                    fechaFin.val('');
                                    return;
                                }
                                
                                // Obtener las fechas del periodo desde los atributos data
                                var optionSeleccionado = $('select[name="Pec_Cod"] option:selected');
                                var fechaInicioPeriodo = optionSeleccionado.attr('data-inicio');
                                var fechaFinPeriodo = optionSeleccionado.attr('data-fin');
                                
                                if (!fechaInicioPeriodo || !fechaFinPeriodo) {
                                    fechaInicio.val('');
                                    fechaFin.val('');
                                    return;
                                }
                                
                                // Función auxiliar para formatear fechas a YYYY-MM-DD
                                var formatoFecha = function(fecha) {
                                    var año = fecha.getFullYear();
                                    var mes = fecha.getMonth() + 1;
                                    var dia = fecha.getDate();
                                    mes = (mes < 10 ? '0' : '') + mes;
                                    dia = (dia < 10 ? '0' : '') + dia;
                                    return año + '-' + mes + '-' + dia;
                                };
                                
                                // Función auxiliar para parsear fecha desde string YYYY-MM-DD
                                var parsearFecha = function(fechaStr) {
                                    var partes = fechaStr.split('-');
                                    if (partes.length === 3) {
                                        return new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
                                    }
                                    return new Date(fechaStr);
                                };
                                
                                // Si hay un mes seleccionado, calcular el rango de ese mes
                                if (cmbMes && cmbMes !== '') {
                                    // Convertir fecha de inicio del periodo a objeto Date
                                    var fechaIni = parsearFecha(fechaInicioPeriodo);
                                    var fechaFinPer = parsearFecha(fechaFinPeriodo);
                                    var mesSeleccionado = parseInt(cmbMes) - 1; // Los meses en JS van de 0-11
                                    var añoPeriodo = fechaIni.getFullYear();
                                    
                                    // Crear fecha de inicio del mes seleccionado
                                    var fechaInicioMes = new Date(añoPeriodo, mesSeleccionado, 1);
                                    
                                    // Crear fecha de fin del mes seleccionado (último día del mes)
                                    var fechaFinMes = new Date(añoPeriodo, mesSeleccionado + 1, 0);
                                    
                                    // Verificar que las fechas del mes estén dentro del rango del periodo
                                    if (fechaInicioMes < fechaIni) {
                                        fechaInicioMes = new Date(fechaIni);
                                    }
                                    if (fechaFinMes > fechaFinPer) {
                                        fechaFinMes = new Date(fechaFinPer);
                                    }
                                    
                                    fechaInicio.val(formatoFecha(fechaInicioMes));
                                    fechaFin.val(formatoFecha(fechaFinMes));
                                } else {
                                    // Si no hay mes seleccionado, usar todo el periodo
                                    fechaInicio.val(fechaInicioPeriodo);
                                    fechaFin.val(fechaFinPeriodo);
                                }
                            }
                            
                            // Calcular fechas iniciales si hay valores seleccionados al cargar la página
                            $(document).ready(function() {
                                calcularFechasFiltro();
                            });

                            function updateDocument() {
                                var filaCalc = addItem({});
                                var rows = $('#itemsGrid').jqGrid('getRowData'),
                                    des_val = $('#t_descuento').val(),
                                    prop = $('#t_prop').val(),
                                    des_por = $('#Vet_Des').val(),
                                    tot = {
                                        t_subtotal: 0,
                                        t_noiva: 0, //nuevo campo
                                        t_iva0: 0,
                                        t_iva5: 0,
                                        t_iva12: 0,
                                        t_iva15: 0,
                                        t_iva: 0,
                                        t_ice: 0,
                                        t_descuento: (isNaN(des_val) ? 0 : des_val * 1),
                                        t_prop: (!isNaN(prop) ? prop * 1 : 0),
                                        Vet_Des: (isNaN(des_por) || des_por * 1 === 0 ? 0 : des_por * 1),
                                        t_rubros: 0
                                    },
                                    Tic_Sri = $('#Tic_Cod').find('option:selected').data('ticsri') * 1,
                                    rise = (Tic_Sri === 2 || Tic_Sri === 9);

                                for (var i = 0, z = rows.length; i < z; i++) {
                                    var row = rows[i];
                                    if (row['Pro_Cod'] !== '') {
                                        row['Vet_Imp'] = (row['Vet_Imp'] * 1);
                                        row['Iva_Por'] = rise ? 0 : ('0' + row['Iva_Por']) * 1; //captura porcentaje del iva
                                        row['Ice_Por'] = ('0' + row['Ice_Por']) * 1;
                                        tot['t_subtotal'] = tot['t_subtotal'] + row['Vet_Imp'];

                                        if (row['Iva_Por'] === 0 || rise) {
                                            // tot['t_iva0'] = tot['t_iva0'] + row['Vet_Imp']; //0%
                                            if(row['Iva_Sri'] == 6){ // nueva validacion para no objeto iva
                                                console.log("entro con el no objeto iva");
                                                tot['t_noiva'] = tot['t_noiva'] + row['Vet_Imp']; //nuevo campo para total sin iva
                                            }else{
                                                tot['t_iva0'] = tot['t_iva0'] + row['Vet_Imp']; //0%
                                            }
                                        } else if (row['Iva_Por'] === 12 || rise) {
                                            tot['t_iva12'] = tot['t_iva12'] + row['Vet_Imp']; //12%
                                        } else if (row['Iva_Por'] === 5 || rise) {
                                            tot['t_iva5'] = tot['t_iva5'] + row['Vet_Imp']; //5%
                                        } else {
                                            tot['t_iva15'] = tot['t_iva15'] + row['Vet_Imp']; //15%
                                        }
                                    }
                                }

                                if ($('#ch_prop').is(':checked')) {
                                    tot['t_prop'] = tot['t_subtotal'] * 0.10;
                                }

                                tot['Vet_Des'] = (tot['t_descuento'] > 0 ? (tot['t_subtotal'] >= tot['t_descuento'] ? tot['t_descuento'] * 100 / tot['t_subtotal'] : 100) : tot['t_descuento'] * 1);
                                for (var i = 0, z = rows.length; i < z; i++) {
                                    var row = rows[i],
                                        des_glob = (tot['Vet_Des'] > 0 ? row['Vet_Imp'] * tot['Vet_Des'] / 100 : 0),
                                        ice = (row['Ice_Por'] > 0 ? (row['Vet_Imp'] - des_glob) * row['Ice_Por'] / 100 : 0);

                                    if (row['Pro_Cod'] !== '') {
                                        if (row['Iva_Por'] > 0 && !rise) {
                                            tot['t_ice'] = tot['t_ice'] + ice;
                                            tot['t_iva'] = tot['t_iva'] + (row['Vet_Imp'] + ice - des_glob) * row['Iva_Por'] / 100;
                                        }
                                    }
                                }
                                tot['t_iva'] = $.round(tot['t_iva']);
                                tot['t_ice'] = $.round(tot['t_ice']);
                                tot['t_rubros'] = tot['t_subtotal'] + tot['t_iva'] + tot['t_ice'] - tot['t_descuento'] + tot['t_prop'];

                                var pagos_tot = $('#pagosGrid').jqGrid('getCol', 'Vet_Tot', false, 'sum');
                                $('#Val_Pcc').val($.toFixed(tot['t_rubros'] - pagos_tot));

                                var opcionDeshabilitar = "01 - SIN UTILIZACION DEL SISTEMA FINANCIERO";
                                // if (tot['t_rubros'] >= 500) {
                                //     $("#Tpc_Cod option:contains('" + opcionDeshabilitar + "')").prop("disabled", true);
                                // } else {
                                //     $("#Tpc_Cod option:contains('" + opcionDeshabilitar + "')").prop("disabled", false);
                                // }
                                var tpcCodSelect = $('#Tpc_Cod');
                                var totalRubros = tot['t_rubros'] * 1;
                                
                                // Función para establecer el valor del select
                                function establecerTpcCod(valor) {
                                    if (tpcCodSelect.length > 0) {
                                        var option = tpcCodSelect.find('option[value="' + valor + '"]');
                                        if (option.length > 0) {
                                            // Remover selección de todas las opciones
                                            tpcCodSelect.find('option').prop('selected', false);
                                            // Seleccionar la opción deseada
                                            option.prop('selected', true);
                                            // Establecer el valor del select
                                            tpcCodSelect.val(valor);
                                            // Forzar el cambio si es necesario
                                            if (tpcCodSelect.val() != valor) {
                                                tpcCodSelect.val(valor);
                                            }
                                            // Disparar el evento change
                                            tpcCodSelect.trigger('change');
                                        }
                                    }
                                }
                                
                                // Usar setTimeout para asegurar que el DOM esté listo
                                setTimeout(function() {
                                    if (totalRubros >= 500) {
                                        $("#Tpc_Cod option:contains('" + opcionDeshabilitar + "')").prop("disabled", true);
                                        // Seleccionar automáticamente la opción 20 si el total es >= 500
                                        establecerTpcCod('20');
                                    } else {
                                        $("#Tpc_Cod option:contains('" + opcionDeshabilitar + "')").prop("disabled", false);
                                        // Seleccionar automáticamente la opción 1 si el total es < 500
                                        establecerTpcCod('1');
                                    }
                                }, 100);

                                $.each(tot, function(k, v) {
                                    tot[k] = $.toFixed(v, k !== 'Vet_Des' ? 2 : 10);
                                });
                                $('#formTotales').setData(tot);
                                $('#Vet_Des').val(tot['Vet_Des']);
                                calculaRetencion();
                                $('#itemsGrid').jqGrid('delRowData', filaCalc);
                                return tot;
                            }

                            // cambia los ivas de los items
                            function changeIvas() {
                                var ids = $('#itemsGrid').jqGrid('getDataIDs'),
                                    iva = {
                                        Iva_Cod: $('#Iva_Cod').val(),
                                        Iva_Por: $('#Iva_Cod option:selected').data('ivapor')
                                    };
                                $('.iva_por').html(iva['Iva_Por']);
                                for (var i = 0; i < ids.length; i++) {
                                    if ('0' + $('#itemsGrid').jqGrid('getCell', ids[i], 'Iva_Por') * 1 > 0)
                                        $('#itemsGrid').changeRow(ids[i], iva);
                                }
                                updateDocument();
                            }

                            // retenciones
                            function calculaRetencion() {
                                var filaCalc = addItem({});
                                var pagos_tot = $('#pagosGrid').jqGrid('getCol', 'Vet_Tot', false, 'sum');
                                var ids = $('#itemsGrid').jqGrid('getDataIDs'),
                                    rets = [],
                                    tot = {
                                        'Ret_Ren_Tot': 0,
                                        'Iva_Ren_Tot': 0,
                                        'Ren_Tot': 0,
                                        'Val_Pcc': 0,
                                        'Ret_Uca': 0,
                                        'Ret_Pca': 0,
                                        'Ret_Ica': 0
                                    },
                                    Tic_Sri = $('#Tic_Cod').find('option:selected').data('ticsri') * 1,
                                    rise = (Tic_Sri === 2 || Tic_Sri === 9),
                                    Vet_Des = $('#Vet_Des').val() * 1;

                                if (ids.length < 1) {
                                    $('#retencion').clearGrid();
                                    $('.reteTot').setData({
                                        Val_Pcc: '0.00'
                                    });
                                    return;
                                }
                                for (var i = 0, z = ids.length; i < z; i++) {
                                    var row = $('#itemsGrid').jqGrid('getLocalRow', ids[i]),
                                        row_Imp = ((row['Vet_Imp'] * 1) - (Vet_Des > 0 ? (row['Vet_Imp'] * Vet_Des / 100) : 0));
                                    if ($.varValid(row['Ret_Ren_Cod']) && row['Ret_Ren_Cod'].length > 0 && !rise) {
                                        var add = true,
                                            ret = {
                                                'Ren_Ret': 'R',
                                                'Ren_Rete': 'RENTA',
                                                'Ren_Cod': row['Ret_Ren_Cod'],
                                                'Ren_Por': row['Ret_Ren_Por'],
                                                'Ren_Sri': row['Ret_Ren_Sri'],
                                                'Ren_Con': row['Ret_Ren_Con'],
                                                'Ren_Imp': row_Imp
                                            };
                                        $.each(rets, function(i, v) {
                                            if (ret['Ren_Cod'] === v['Ren_Cod'] && ret['Ren_Por'] === v['Ren_Por']) {
                                                rets[i]['Ren_Imp'] += ret['Ren_Imp'];
                                                add = false;
                                            }
                                        });
                                        if (add) rets.push(ret);
                                        //if(String(ret['Ren_Sri'])===String(cod_banano)){ tot['Ret_Uca']+=row['Cop_Can']*1;tot['Ret_Ica']+=row_Imp; }
                                    }
                                    if ($.varValid(row['Iva_Ren_Cod']) && row['Iva_Ren_Cod'].length > 0 && !rise) {
                                        var ice_por = ('0' + row['Ice_Por']) * 1,
                                            ice = (ice_por > 0 ? row_Imp * ice_por / 100 : 0);
                                        var add = true,
                                            ret = {
                                                Ren_Ret: 'I',
                                                Ren_Rete: 'IVA',
                                                Ren_Cod: row['Iva_Ren_Cod'],
                                                Ren_Por: row['Iva_Ren_Por'],
                                                Ren_Sri: row['Iva_Ren_Sri'],
                                                Ren_Con: row['Iva_Ren_Con'],
                                                Ren_Imp: (row_Imp + ice) * (row['Iva_Por'] / 100)
                                            };
                                        $.each(rets, function(i, v) {
                                            if (ret['Ren_Cod'] === v['Ren_Cod']) {
                                                rets[i]['Ren_Imp'] += ret['Ren_Imp'];
                                                add = false;
                                            }
                                        });
                                        if (add) rets.push(ret);
                                    }
                                }
                                $.each(rets, function(i, v) {
                                    rets[i]['Ren_Val'] = $.round(v['Ren_Imp'] * v['Ren_Por'] / 100);
                                    //rets[i]['Ren_Val']=$.round(v['Ren_Imp'])*v['Ren_Por']/100;
                                    tot[(v['Ren_Ret'] === 'R' ? 'Ret' : 'Iva') + '_Ren_Tot'] += rets[i]['Ren_Val'];
                                });

                                tot['Ren_Tot'] = tot['Ret_Ren_Tot'] + tot['Iva_Ren_Tot'];
                                tot['Val_Pcc'] = $('#t_rubros').val() * 1 - ($('#Ret_Asu').prop('checked') ? 0 : tot['Ren_Tot']);
                                (tot['Ren_Tot'] > 0 ? $('.ret_field').removeAttr('disabled') : $('.ret_field').val('').attr('disabled', 'disabled'));
                                $.each(tot, function(k, v) {
                                    tot[k] = $.toFixed(v);
                                });

                                if (tot['Ret_Uca'] * 1 > 0 && tot['Ret_Ica'] * 1 > 0) {
                                    tot['Ret_Pca'] = $.round(tot['Ret_Ica'] / tot['Ret_Uca'], 8);
                                    tot['Ret_Uca'] = $.round(tot['Ret_Uca'], 0);
                                    $('.cod_banano').show().find('input').attr('required', 'required');
                                } else {
                                    tot['Ret_Uca'] = tot['Ret_Pca'] = tot['Ret_Ica'] = '';
                                    $('.cod_banano').hide().find('input').removeAttr('required');
                                }

                                $('.reteTot').setData(tot);
                                var pagos_mod = $('#pagosGrid').getDataIDs();

                                $('.porCobrar').setData({
                                    'Val_Pcc_2': $.toFixed(tot['Val_Pcc'] - pagos_tot)
                                });
                                if (pagos_mod.length === 1 && $('#Val_Pcc').val() * 1 > 0) {
                                    $('#pagosGrid').jqGrid('setCell', pagos_mod[0], 'Vet_Tot', $('#Val_Pcc').val() * 1);
                                    $('#pagosGrid').trigger('reloadGrid');
                                    $('.porCobrar').setData({
                                        'Val_Pcc_2': $.toFixed(0)
                                    });
                                }

                                igualarPagos();

                                $('#retencion').setRows(rets);
                                $('#itemsGrid').jqGrid('delRowData', filaCalc);
                                return $.toFixed(tot['Val_Pcc'] - pagos_tot);
                            }

                            function igualarPagos() {
                                var pagos_mod = $('#pagosGrid').getDataIDs();
                                var pagos_tot = $('#pagosGrid').jqGrid('getCol', 'Vet_Tot', false, 'sum');
                                if (pagos_mod.length > 1 && $('#Val_Pcc').val() * 1 > 0) {
                                    for (var i = 0, max = pagos_mod.length; i < max; i++) {
                                        if ($('#Val_Pcc_2').val() * 1 !== 0) {
                                            var pago_cal = $('#pagosGrid').jqGrid('getCell', pagos_mod[i], 'Vet_Tot') * 1;
                                            pago_cal = pago_cal + (pago_cal / pagos_tot * $('#Val_Pcc_2').val() * 1);
                                            $('#pagosGrid').jqGrid('setCell', pagos_mod[i], 'Vet_Tot', pago_cal);
                                        }

                                    }
                                    $('#pagosGrid').trigger('reloadGrid');
                                    var pagos_tot = $('#pagosGrid').jqGrid('getCol', 'Vet_Tot', false, 'sum');
                                    $('.porCobrar').setData({
                                        'Val_Pcc_2': $.toFixed($('#Val_Pcc').val() - pagos_tot)
                                    });
                                }
                            }

                            function calcularPorcentaje(e) {
                                let form_rete = $('#changeReteDialog').getData();
                                $('#itemsGrid').stopGridEdit();
                                $('#form_change_rete').setData({
                                    'Ret_Ren_Por': (($(e).val() * 100)) / ((items.getRowData(form_rete.index)['Vet_Imp'] * 1) - $('#t_descuento').val())
                                }, false);
                                $('#itemsGrid').startGridEdit();
                            }

                            function validarNum(Vet_Xml) {
                                $('#formDocumento').setData({
                                    'Aut_Sri': (Vet_Xml != '' ? 'Electr&oacute;nica' : ['Normal'])
                                }, false);
                            }

                            function agregaRetencion(data) {
                                var form = $("#codiForm").getData(),
                                    ret = {};

                                $.each(data, function(k, v) {
                                    ret[(form["tipo"] === "R" ? "Ret_" : "Iva_") + k] = v;
                                });
                                ret["select"] = "";
                                if (form["checkRentaIva"] === "N") $('#itemsGrid').changeRow(form["index"], ret);
                                else {
                                    //falla con la ultima fila en edicion de documentos de ventas
                                    var ids = $('#itemsGrid').jqGrid("getDataIDs");
                                    //se eliminó el -1 en la linea z=ids.length
                                    for (var i = 0, z = ids.length - 1; i < z; i++)
                                        $('#').changeRow(ids[i], ret);
                                }
                                updateDocument();
                                calculaRetencion();
                                $("#codiDialog").dialog("close");
                            }

                            function agregaRetencion2(data) {
                                var form = $("#codiForm").getData(),
                                    ret = {};

                                $.each(data, function(k, v) {
                                    ret[(form["tipo"] === "R" ? "Ret_" : "Iva_") + k] = v;
                                });
                                ret["select"] = "";
                                if (form["checkRentaIva"] === "N") $('#itemsGrid').changeRow(form["index"], ret);
                                else {
                                    //falla con la ultima fila en edicion de documentos de ventas
                                    var ids = $('#itemsGrid').jqGrid("getDataIDs");
                                    //se eliminó el -1 en la linea z=ids.length
                                    for (var i = 0, z = ids.length; i < z; i++) $('#itemsGrid').changeRow(ids[i], ret);
                                }
                                updateDocument();
                                calculaRetencion();
                                $("#codiDialog").dialog("close");
                            }

                            // Añade un item al documento
                            function addPago(pago, carga_inicial = false) {
                                var next = $('#pagosGrid').jqGrid('getCol', 'Vet_Num', false, 'max');
                                var text = $('#Pag_Cod').find('option:selected').text().toUpperCase();
                                pago['Vet_Num'] = (isNaN(next) ? 1 : next + 1);
                                pago['Forma_Cod'] = (pago['For_Cod'] == 1) ? 'Contado' : 'Credito';
                                pago['Tipo_Cod'] = pago['Pag_Des'];
                                // Para el Banco - CAMBIAR POR UNA CONSULTA QUE LO HAGA MEJOR
                                if (pago['Tipo_Cod'] != 'Cheque' || pago['Tipo_Cod'] != 'Transferencia' || pago['Tipo_Cod'] != 'Deposito') {
                                    if (pago['Bak_Cod'] == 1)
                                        pago['Bak_Cod'] = 'Ninguno';
                                }

                                if (pago['Tipo_Cod'] === 'Cheque') {
                                    if (pago['Bak_Cod'] == 1) {
                                        pago['Bak_Cod'] = 'Ninguno';
                                    } else if (pago['Bak_Cod'] == 2) {
                                        pago['Bak_Cod'] = 'Banco Internacional';
                                    } else if (pago['Bak_Cod'] == 3) {
                                        pago['Bak_Cod'] = 'Banco de Machala';
                                    } else if (pago['Bak_Cod'] == 4) {
                                        pago['Bak_Cod'] = 'Banco de Guayaquil';
                                    } else if (pago['Bak_Cod'] == 5) {
                                        pago['Bak_Cod'] = 'Banco del Pacifico';
                                    } else if (pago['Bak_Cod'] == 6) {
                                        pago['Bak_Cod'] = 'Banco del Pichincha';
                                    } else if (pago['Bak_Cod'] == 7) {
                                        pago['Bak_Cod'] = 'Banco de Loja';
                                    } else if (pago['Bak_Cod'] == 8) {
                                        pago['Bak_Cod'] = 'Banco de Rumiñahui';
                                    } else if (pago['Bak_Cod'] == 9) {
                                        pago['Bak_Cod'] = 'Banco Bolivariano';
                                    } else if (pago['Bak_Cod'] == 10) {
                                        pago['Bak_Cod'] = 'Banco Produbanco';
                                    } else if (pago['Bak_Cod'] == 11) {
                                        pago['Bak_Cod'] = 'BanEcuador';
                                    } else if (pago['Bak_Cod'] == 12) {
                                        pago['Bak_Cod'] = 'ProCredit';
                                    } else if (pago['Bak_Cod'] == 13) {
                                        pago['Bak_Cod'] = 'Banco del Austro';
                                    } else if (pago['Bak_Cod'] == 14) {
                                        pago['Bak_Cod'] = 'Cooperativa de Ahorro y Credito Santa Rosa Ltda';
                                    } else if (pago['Bak_Cod'] == 15) {
                                        pago['Bak_Cod'] = 'Cooperativa de Ahorro y Credito Juventud Ecuatori';
                                    } else {
                                        pago['Bak_Cod'] = 'Desconocido';
                                    }
                                }

                                if (pago['Tipo_Cod'] === 'Transferencia' || pago['Tipo_Cod'] === 'Deposito') {
                                    if (pago['Bak_Cod'] == 1) {
                                        pago['Bak_Cod'] = 'Banco Pichincha Cta.Ahorro XXX';
                                    } else if (pago['Bak_Cod'] == 2) {
                                        pago['Bak_Cod'] = 'Banco de Guayaquil 123456';
                                    } else {
                                        pago['Bak_Cod'] = 'Desconocido';
                                    }
                                }





                                // if (text === 'TRANSFERENCIA' || text === 'DEPOSITO') {
                                //     pago['Pag_Pld'] = (carga_inicial ? pago['Pag_Pld'] : $('#Ban_Cod option:selected').data('pldcod'));
                                // }

                                // if (text === 'CHEQUE') {
                                //     if (carga_inicial == false) {
                                //         pago['Bak_Cod'] = $('#Bak_Cod').val();
                                //         pago['Fec_che'] = $('#Fec_che').val();
                                //     }
                                // }

                                // if (carga_inicial && pago['Pag_Pld'] * 1 <= 0) {
                                //     pago['Pag_Pld'] = $('#Pag_Pld').val();
                                // }

                                $('#pagosGrid').jqGrid('addRowData', next, pago);
                                $('#pagosGrid').trigger('reloadGrid');
                                $('#pagosDialog').dialog('close');
                                var pagos_tot = $('#pagosGrid').jqGrid('getCol', 'Vet_Tot', false, 'sum');
                                calculaRetencion();
                                // updateDocument();
                                $('#For_Cod').val(1).trigger('change');
                                $('.porCobrar').setData({
                                    'Val_Pcc_2': $.toFixed($('#Val_Pcc').val() - pagos_tot)
                                });
                            }

                            function cargarDoc(doc) {
                                // console.log("Data: ", doc); //deshabilitar por seguridad
                                init_load = true;
                                $('#Check_Comprobante').prop('checked', (doc['Com_Exi'] === "S" ? true : false));
                                $('#itemsGrid').clearGridData();
                                vet_num_ant = doc['Vet_Num'];
                                tic_cod_ant = doc['Tic_Cod'];
                                editDoc = true;
                                AutCod = doc['Aut_Cod'];
                                TicCod = doc['Tic_Cod'];
                                Bod_Cod = doc['Bod_Cod'];
                                $('#editDoc').setData({});
                                $('#Pec_Cod').attr('disabled', true);
                                $('#Tpc_Cod').val(doc['Tpc_Cod'] * 1);

                                $.getDataJson('', {
                                    'cargarDoc': true,
                                    'vet_cod': doc['Vet_Cod'],
                                    'Aut_Cod': AutCod,
                                    'Tic_Cod': TicCod,
                                    'Cli_Cod': doc['Cli_Cod']
                                }, function(resp) {
                                    $('#editDoc').setData(doc);
                                    $('#For_Cod').val(resp['For_Cod']).trigger('change');
                                    $('#Pag_Cod').val(resp['Pag_Cod']).trigger('change');
                                    $('#Bak_Cod').val(resp['Bak_Cod']).trigger('change');

                                    $('#clieFormTemp,#viajesForm').setData({
                                        Prs_Ced: doc['Prs_Ced'],
                                        Cli_Cod: doc['Cli_Cod'],
                                        cliente: doc['cliente'],
                                        Tic_Cod: TicCod,
                                        op_opciones: 'c'
                                    });
                                    $('#viajesSelectedGrid').setRows(resp['Viajes_Sel'] || []);
                                    $('.viajes')[$.vv(resp['Viajes']) && resp['Viajes'].toNum() > 0 ? 'show' : 'hide']();
                                    $.SearchOrDialog('#clieDialog', selectCliente);

                                    // if (doc['Pec_Cod']) {
                                    //     $('#Pec_Cod').val(doc['Pec_Cod']);
                                    // } else {
                                    //     var periodo_selec = doc['Vet_Fec'] ? doc['Vet_Fec'].split("-")[0] : '';
                                    //     // var periodo_selec = doc['Pec_Cod'] ? doc['Pec_Cod'].split("-")[0] : '';
                                    //     $("#Pec_Cod").find('option:contains("' + periodo_selec + '")').prop('selected', true);
                                    // }

                                    var date;
                                    if ($("#tab1").is(":visible")) {
                                        date = new Date(doc['Vet_Fec']);
                                    } else if ($("#tab2").is(":visible")) {
                                        date = new Date(doc['Caj_Fec']);
                                    }
                                    var year = date.getFullYear();
                                    doc['Pec_Cod'] = year;

                                    var sel_fecha = $('#Pec_Cod').find('option:selected');
                                    $('#Caj_Fec').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
                                    $('.placod').val(sel_fecha.data('placod'));
                                    $('#Pec_Cod').val(year); // refleja datos de periodo
                                    if ($("#tab1").is(":visible")) {
                                        $('#Caj_Fec').val(doc['Vet_Fec']); // refleja datos de fecha para tab1
                                    } else if ($("#tab2").is(":visible")) {
                                        $('#Caj_Fec').val(doc['Caj_Fec']); // refleja datos de fecha para tab2
                                    }
                                    $('#Ciu_Cod').val(doc['Ciu_Cod']); // refleja datos de ciudad
                                    $('#Tic_Cod_aux').val(TicCod); // refleja datos de tipo de documento
                                    $("textarea[name=Vet_Obs]").val(doc['Vet_Obs']); //refleja datos de observaciones

                                    $('#itemsGrid').jqGrid('delRowData', 1);
                                    $.each(resp['items'], function(x, item) {
                                        //    addItem(item, item['Vet_Can'], item['Vet_Pru']);
                                        $('#itemsGrid').jqGrid('addRowData', item.index, item);
                                    });
                                    // Ajustar columnas después de cargar los items
                                    setTimeout(function() {
                                        ajustarColumnasPorEmpresa();
                                    }, 200);

                                    $("#Vet_Rem").prop('checked', false);
                                    if ($.vv(resp['reembolsos']) && resp['reembolsos'].length > 0) {
                                        $("#Vet_Rem").prop('checked', true);
                                        reembolsos.setRows(resp['reembolsos']);
                                    }
                                    $("#Vet_Rem").trigger('change');
                                    aBorrar = addItem({});
                                    var aCobrar = $('#Val_Pcc_2').val() * 1;

                                    $('#pagosGrid').clearGridData();
                                    // console.log("Pagos: ", resp['pagos']); //deshabilitar por seguridad
                                    $.each(resp['pagos'], function(x, pago) {
                                        addPago(pago, true);
                                    });
                                    if (resp['Iva_Por'] * 1 > 0){
                                            $('#Iva_Cod').val($('#Iva_Cod').find('option[data-ivapor=' + resp['Iva_Por'] + ']').val());
                                    }
                                    // Si Iva_Por es 0 y Iva_Sri es igual a 6, mostrar el valor en t_noiva
                                    if ((resp['Iva_Por'] * 1 === 0) && (resp['Iva_Sri'] * 1 === 6)) {
                                        $('#t_noiva').val(resp['Iva_Por']);
                                    }

                                    if (doc['Ret_Fec']) {
                                        // Convertir al formato adecuado si es necesario
                                        const fechaValida = new Date(doc['Ret_Fec']).toISOString().split('T')[0];
                                        $('#Ret_Fec_aux').val(fechaValida.trim());
                                        $('#Ret_Fec_aux').prop('disabled', false).prop('readonly', false);
                                    }
                                    setTimeout(() => {
                                        $('#Ret_Fec_aux').val(doc['Ret_Fec']).trigger('input').trigger('change');
                                    }, 500);

                                    updateDocument();
                                    $('#itemsGrid').jqGrid('delRowData', aBorrar);

                                    var botones_pagos = $('#pagosPager_left').find('td.btn-success');
                                    var btn_pagos_activos = $('.porCobrar').find('span.input-group-btn');
                                    if ((doc['Cpc_Min'] * 1) <= 0) {
                                        btn_pagos_activos.addClass('hidden');
                                        botones_pagos.removeClass('hidden');
                                        pago_min = 0;
                                    } else {
                                        pago_min = doc['Cpc_Min'] * 1;
                                        btn_pagos_activos.removeClass('hidden');
                                        btn_pagos_activos.attr('title', 'Posee pagos activos por $' + pago_min + ' !').tooltip({
                                            placement: 'left'
                                        }).tooltip('show').focus();
                                        botones_pagos.not(botones_pagos.find('span.glyphicon-credit-card').parent().parent()).addClass('hidden');
                                    }
                                    var html;
                                    html += '<option value="">Seleccione...</option>';
                                    $.each(resp['documentos'], function(i, v) {
                                        if (doc['Vet_Fec'] >= v['Aut_Fci'] && doc['Vet_Fec'] <= v['Aut_Cad']) {
                                            html += '<option value=' + v['Tic_Cod'] + ' data-ticcod=' + v['Tic_Cod'] + ' data-ticsri=' + v['Tic_Sri'] + ' data-puncod=' + v['Pun_Cod'] + ' data-autcod=' + v['Aut_Cod'] + ' data-autsri=' + v['Aut_Sri'] + ' data-auttem=' + v['Aut_Tem'] + ' data-autima=' + v['Aut_Ima'] + ' data-punsri=' + v['Pun_Sri'] + ' data-sucsri=' + v['Suc_Sri'] + ' data-autini=' + v['Aut_Ini'] + ' data-autfin=' + v['Aut_Fin'] + ' data-autfci=' + v['Aut_Fci'] + ' data-autcad=' + v['Aut_Cad'] + '>' + v['Tic_Sri'] + ' - ' + v['Tic_Des'] + '</option>';
                                        }
                                    });

                                    if (resp['Bod_Cod']) {
                                        $("#Bod_Cod").val(resp['Bod_Cod'].Bod_Cod);
                                    }

                                    $("#Ret_Ren_Tot").val(parseFloat(doc['Tot_Renta']) + parseFloat(doc['Tot_Iva']));
                                    $("#Iva_Ren_Tot").val(doc['Tot_Iva']);
                                    $("#Ren_Tot").val(parseFloat(doc['Tot_Renta']) + parseFloat(doc['Tot_Iva']));

                                    var ch_prop = document.getElementById('ch_prop');
                                    if (doc.Vet_Prop && doc.Vet_Prop != "0.00") {
                                        ch_prop.checked = true;
                                    } else { //Caso contrario lo desactiva
                                        ch_prop.checked = false;
                                    }

                                    $('#Tic_Cod').html(html);
                                    $('#Tic_Cod').val(doc['Tic_Cod']).trigger('change');
                                    $('#Vet_Des').val(doc['Vet_Des']).trigger('change');
                                    validarNum(doc['Secuencia']);
                                    $('#Vet_Num').val(doc['Secuencia']).trigger('change'); // visualiza la secuencia

                                    $('#t_descuento').val($('#t_subtotal').val() * $('#Vet_Des').val() * 1 / 100).trigger('change');
                                    $('#Ret_Num').val(doc['Ret_Num']);
                                    $('input[name=Ret_Aut_Sri]').val(doc['Ret_Aut']); //visualiza la autorizacion de retencion
                                    $('#Ret_Num').trigger('change'); // visualiza el numero de retencion

                                    $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
                                    $('#documentoMain').find(':input:not(.btn-main)').attr({
                                        readonly: true,
                                        tabindex: '-1'
                                    }).end().find('select,td.btn,button:not(.btn-ret,.btn-main),input').attr({
                                        disabled: true
                                    }).unbind('click').end().find('select,input').addClass('readOnly');
                                    // $('#Iva_Cod,#Iva_Pag').hide();
                                    $('#documentos').find('tbody tr:last').hide();
                                    addItem({});


                                });
                            }

                            // GridView para visualizacion de los registros de VENTAS
                            $('#searchGrid').createGrid({
                                caption: 'Resultado de la Búsqueda',
                                height: 270,
                                datatype: "local",
                                caption: 'Resultados <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="order by caja_aper.Caj_Fec DESC ">Fecha Venta</option><option value="order by Vet_Num DESC ">Num. Documento</option></select>&nbsp;</div>',
                                colModel: [
                                    { label: 'Cód. Int.', name: 'Vet_Cod', width: 30, align: "center", key: true },
                                    { label: 'Compr.', name: 'Com_Exi', width: 20, align: "center", formatter: 'truefalse',
                                        formatoptions: { yesMsg: 'Tiene Comprobante', noMsg: ' ' },
                                        title: false
                                    },
                                    { label: 'Reten.', name: 'Ret_Exi', width: 20, align: "center", formatter: 'truefalse',
                                        formatoptions: { yesMsg: 'Tiene Retencion', noMsg: ' '
                                        },
                                        title: false
                                    },
                                    { label: 'Pago', name: 'Pago', width: 35, align: "center" },
                                    { label: 'P. SRI', name: 'Tpc_Sri', width: 20, align: "center", formatter: 'title',
                                        formatoptions: { title: function(o) { return o['Tpc_Des']; } },
                                        title: false
                                    },
                                    { label: 'Tipo Documento', name: 'Tic_Des', width: 100 },
                                    { label: 'Com_Cod', name: 'Com_Cod', width: 100, hidden: true },
                                    { label: 'No. Documento', name: 'Vet_Num', width: 90, align: "center" },
                                    { label: 'Fecha', name: 'Vet_Fec', width: 45, align: "center" },
                                    { label: 'Cliente', name: 'cliente_per', width: 150 },
                                    { label: 'Estado', name: 'Vet_Est', width: 20, align: "center", formatter: 'estado', title: false },
                                    { label: '&nbsp;', name: 'act2', width: 20, align: 'center', viewable: false, formatter: 'gridButton',
                                        formatoptions: {
                                            action: ImpDoc,
                                            title: 'Imprimir Documento',
                                            icon: 'print',
                                            type: 'info'
                                        },
                                        title: false
                                    },
                                    { label: '&nbsp;', name: 'act2', width: 20, align: 'center', viewable: false, formatter: 'gridButton',
                                        formatoptions: {
                                            action: ImpCom, title: 'Imprimir Comprobante', icon: 'print', type: 'info'
                                        },
                                        title: false
                                    },
                                    { label: '&nbsp;', name: 'act0', width: 20, align: 'center', viewable: false, formatter: 'gridButton',
                                        formatoptions: {
                                            action: viewInfo, title: 'Ver Documento', icon: 'info-sign', type: 'info'
                                        },
                                        title: false
                                    },
                                    { label: 'XML', name: 'act02', width: 20, align: 'center', viewable: false, formatter: 'gridButton',
                                        formatoptions: {
                                            action: descargar, title: 'Ver XML', icon: 'file', type: 'info',
                                            conditional: function(o) {
                                                return o.Vet_Est !== 'I' && !$.isEmpty(o.Vet_Xml);
                                            }
                                        },
                                        title: false
                                    },
                                    { label: 'PDF', name: 'act02', width: 20, align: 'center', viewable: false, formatter: 'gridButton',
                                        formatoptions: {
                                            action: viewPdfVenta, title: 'Ver PDF', icon: 'file', type: 'info',
                                            conditional: function(o) {
                                                return o.Vet_Est !== 'I' && !$.isEmpty(o.Vet_Xml);
                                            }
                                        },
                                        title: false
                                    },
                                    { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'edicion', title: false }
                                ],
                                loadComplete: function(data) {
                                    if ($.varValid(data.rows))
                                        for (var i = 0, z = data.rows.length; i < z; i++) {
                                            if (data.rows[i]['Vet_Est'] === 'I' || data.rows[i]['Vet_Est'] === 'E') $("#" + data.rows[i].Vet_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
                                            //if(data.rows[i]['Ret_Aut'] ==='S' || data.rows[i]['Rcc_Det'] ==='S' )  $("#"+data.rows[i].Vet_Cod+' td:not(.jqgrid-rownum)').addClass('cellBlue2');
                                            if (data.rows[i]['Cpc_Det'] === 'S' || data.rows[i]['Cpc_Edit'] === 'N') $("#" + data.rows[i].Vet_Cod + ' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                                        }
                                }
                            }, false, '#searchGridPager', {
                                refresh: true
                            });
                            //$('.formDatos:').find(':input').removeAttr('readonly');

                            // GridView para visualizacion de los datos de VENTAS
                            $('#itemsGrid').createGrid({
                                caption: (Nota_CreDeb === true ? '<div class="pull-right" formDatos><span>Afecta Inventario:&nbsp;</span><input id="afecta_inventario" name="Cal_Inv" type="checkbox" class="check-big"/>&nbsp;</div>' : ''),
                                data: [],
                                rowNum: 10000000,
                                height: 'auto',
                                footerrow: true,
                                headertitles: true,
                                selectGridRows: false,
                                colModel: [
                                    { name: 'select', label: '<i class="glyphicon glyphicon-check"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton',
                                        formatoptions: {
                                            action: edit_reten ? '' : openItemSelector,
                                            icon: 'check',
                                            title: (edit_reten ? 'No es posible Cambiar el Item' : 'Seleccionar Item'),
                                            data: function(o) {
                                                return o.index;
                                            },
                                            conditional: function(o) {
                                                return !$.isArray(o.Viajes);
                                            },
                                            caseFalse: function(o) {
                                                return $.isArray(o.Viajes) ? $.createIcon('fa-truck grey') : '';
                                            }
                                        },
                                        resizable: false
                                    },
                                    { name: 'Vet_Index', label: 'Vet_Index', width: 40, align: 'center', hidden: true },
                                    { name: 'Viajes', label: 'Viajes', width: 40, align: 'center', formatter: 'json', hidden: true },
                                    { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', key: true, hidden: true },
                                    { name: 'Pro_Cod', label: 'C&oacute;d.Int.', width: 20, sorttype: 'int', align: 'center', hidden: true },
                                    { name: 'Vet_Can', label: 'Cant.', labelLong: 'Cantidad', width: 40, align: 'right', title: false,
                                        editable: (edit_reten ? false : true),
                                        editoptions: { dataInit: styleCant }
                                    },
                                    { name: 'Uni_Des', label: 'Uni.', labelLong: 'Unidad', width: 25, resizable: false },
                                    // { name: 'Des_Adi', label: 'Des. Adicional', width: 75, editable: true },
                                    { name: 'Ite_Lar', label: 'Descripci&oacute;n', width: 150 },
                                    { name: 'Pld_Cod', label: 'Pld_Cod', width: 20, hidden: true },
                                    { name: 'Pld_Cdc', label: 'Cuenta', width: 50, formatter: 'title',
                                        formatoptions: {
                                            title: function(o) {
                                                return o['Pld_Cdc'] + ' - ' + o['Pld_Des'];
                                            }
                                        },
                                        title: false
                                    },
                                    { name: 'Pld_Des', label: 'Pld_Des', width: 20, hidden: true },
                                    { name: 'Des_Adi', label: 'Des. Adicional', width: 75, editable: true },
                                    { name: 'add_imei', label: 'IMEI', labelLong: 'Seleccionar IMEI', width: 70, align: 'center', title: false, formatter: 'imeiSelector', resizable: false, hidden: true },
                                    { name: 'Ime_Cod', label: 'Ime_Cod', width: 20, hidden: true },
                                    { name: 'Ime_Num', label: 'Nro. IMEI', labelLong: 'Número IMEI', width: 80, align: 'center', title: false, editable: false, resizable: false, hidden: true },
                                    { name: 'Vet_Dec', label: 'Desc(%)', labelLong: 'Descuento %', align: 'right', width: 30, editable: (edit_reten ? false : true) },
                                    { name: 'Vet_Pru', label: 'P. Unitario', labelLong: 'Precio Unitario', width: 70, align: 'right',
                                        title: false /*, summaryRound: 8,formatter:"currency",formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.',decimalPlaces: 8, defaultValue: ''}*/ ,
                                        editable: (edit_reten ? false : true),
                                        editoptions: { dataInit: stylePru }
                                    },
                                    { name: 'Vet_Imp', label: 'Importe', width: 55, align: 'right', summaryRound: 4, formatter: 'currency',
                                        formatoptions: {
                                            prefix: '',
                                            thousandsSeparator: ',',
                                            decimalSeparator: '.',
                                            decimalPlaces: <?php echo (($Ses_Emp_Cod == 534) || ($Ses_Emp_Cod == 554) || ($Ses_Emp_Cod == 531) ? 4 : 2); ?>,
                                            defaultValue: '<?php echo (($Ses_Emp_Cod == 534) || ($Ses_Emp_Cod == 554) || ($Ses_Emp_Cod == 531) ? '0.0000' : '0.00'); ?>'
                                        },
                                        classes: 'columnHighlight1'
                                    },
                                    { name: 'Iva_Cod', label: 'CodIva', width: 20, hidden: true
                                    },
                                    //CAMPO NUEVO
                                    { name: 'Iva_Por', label: 'IVA(%)', labelLong: 'Porcentaje IVA', width: 35, align: 'center', title: false, resizable: false },
                                    //{name:'Iva_Por',label:'IVA', width:15,align:'center', formatter:'truefalse', formatoptions:{yesMsg:'Grava IVA',noMsg:'No Grava IVA'}, title:false, resizable: false },
                                    { name: 'Ice_Int', label: 'CodIce', width: 20, hidden: true },
                                    { name: 'Ice_Por', label: 'ICE %', width: 20, align: 'right', title: false, resizable: false },
                                    { name: 'Iva_Sri', label: 'IVA_SRI', labelLong: 'IVA_SRI', width: 10, align: 'center', title: false, resizable: false, hidden: true }, // nuevo campo
                                    { name: 'Ret_Mod', label: 'Ret Mod.', width: 20, hidden: true, formatter: 'truefalse', title: false, resizable: false },
                                    { name: 'Ret_Ren_Sri', label: 'I. Renta', labelLong: 'Impuesto a la Renta', hidden: (Nota_CreDeb ? true : false), width: 35, align: 'center', title: false, formatter: 'impRenta', resizable: false },
                                    { name: 'Ret_Ren_Cod', label: 'Ret Ren_Cod', width: 20, hidden: true },
                                    { name: 'Ret_Ren_Por', label: 'Ret Ren_Por', width: 20, hidden: true },
                                    { name: 'Ret_Ren_Con', label: 'Ret Ren_Con', width: 20, hidden: true },
                                    { name: 'Iva_Ren_Sri', label: 'Ret. IVA', labelLong: 'Retenci&oacute;n del IVA', hidden: (Nota_CreDeb ? true : false), width: 35, align: 'center', title: false, formatter: 'retIva', resizable: false },
                                    { name: 'Iva_Ren_Cod', label: 'Iva Ren_Cod', width: 20, hidden: true },
                                    { name: 'Iva_Ren_Por', label: 'Iva Ren_Por', width: 20, hidden: true },
                                    { name: 'Iva_Ren_Con', label: 'Iva Ren_Con', width: 20, hidden: true },
                                    { name: 'Adq_Cod', label: 'CodAdq', width: 20, hidden: true },
                                    { name: 'Adq_Cor', label: 'Adq.', labelLong: 'Adquisiciones', width: 20, align: 'center', title: false, formatter: 'title',
                                        formatoptions: {
                                            title: function(o) { return o['Adq_Des']; }
                                        },
                                        resizable: false
                                    },
                                    { name: 'delete', label: '<i class="glyphicon glyphicon-remove icon-grey"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton',
                                        formatoptions: {
                                            action: (edit_reten ? '' : deleteItem),
                                            icon: 'remove',
                                            title: (edit_reten ? 'No es posible Eliminar Item' : 'Eliminar Item'),
                                            type: 'danger',
                                            data: function(o) {
                                                return o.index;
                                            },
                                            attr: {
                                                'tabindex': '-1'
                                            },
                                            conditional: function(o) {
                                                return !(!$.varValid(o['Pro_Cod']) || o['Pro_Cod'] === '') && !$.isArray(o.Viajes);
                                            },
                                            caseFalse: function(o) {
                                                return !$.varValid(o['Pro_Cod']) || o['Pro_Cod'] === '' ? ' ' : ($.isArray(o.Viajes) ? $.createIcon('remove grey') : '');
                                            }
                                        },
                                        resizable: false
                                    }
                                ]
                            }, true, 'itemsGridPager', {
                                view: false
                            }).gridButtonsAdd([
                                { caption: 'Agregar Productos', buttonicon: 'plus',
                                    onClickButton: function() {
                                        if (edit_reten)
                                            return;
                                        if (!available()) {
                                            $.alert('No hay espacio para mas items en este documento!');
                                            return;
                                        }
                                        index = 0;
                                        $('#proDialog').dialog('open');
                                        $.Search('pro');
                                    }
                                },
                                { caption: 'Remover Todos', buttonicon: 'remove',
                                    onClickButton: function() {
                                        if (edit_reten)
                                            return;
                                        items.clearGrid();
                                        $('#viajesSelectedGrid').clearGrid();
                                        changeIvas();
                                        addItem({});
                                    }
                                },
                                { caption: 'Viajes', buttonicon: 'fa-truck',
                                    onClickButton: function() {
                                        if (edit_reten)
                                            return;
                                        $('#viajesSelectedDialog').dialog('open');
                                    },
                                    id: 'viajeSel',
                                    classes: 'viajes',
                                    css: { display: 'none' }
                                }
                            ]);

                            // Ajustar visibilidad de columnas según código de empresa
                            window.ajustarColumnasPorEmpresa = function() {
                                var Emp_Cod = typeof Ses_Emp_Cod !== 'undefined' ? Ses_Emp_Cod : ($("#Emp_Cod").length ? $("#Emp_Cod").val() : null);
                                if (!Emp_Cod || !items || !items.jqGrid) return;
                                
                                if (Emp_Cod == 531 || Emp_Cod == 503) {
                                    // Empresa 531: mostrar add_imei e Ime_Num, ocultar Ice_Por
                                    if (!Nota_CreDeb) {
                                        $('#itemsGrid').jqGrid('showCol', 'Ime_Num');
                                    }
                                    $('#itemsGrid').jqGrid('showCol', 'Ime_Num');
                                    $('#itemsGrid').jqGrid('hideCol', 'Ice_Por');
                                } else {
                                    // Otras empresas: ocultar add_imei e Ime_Num, mostrar Ice_Por
                                    $('#itemsGrid').jqGrid('hideCol', 'add_imei');
                                    $('#itemsGrid').jqGrid('hideCol', 'Ime_Num');
                                    $('#itemsGrid').jqGrid('showCol', 'Ice_Por');
                                }
                            };
                            
                            // Ejecutar ajuste después de inicializar el grid
                            setTimeout(function() {
                                ajustarColumnasPorEmpresa();
                            }, 100);
                            
                            // Ajustar cuando cambie el código de empresa (si existe el elemento)
                            if ($("#Emp_Cod").length) {
                                $("#Emp_Cod").off('change.ajustarColumnas').on('change.ajustarColumnas', function() {
                                    ajustarColumnasPorEmpresa();
                                });
                            }

                            $('#itemsGrid').getFootRow(true);
                            $('#itemsGrid').jqGrid('footerData', 'set', {
                                Ite_Lar: '<div class="footerFact formDatos" class="formDatos">' +
                                    '<label style="position:relative;text-align: left;">Observaci&oacute;n:</label>' +
                                    '<textarea id="Vet_Obs" name="Vet_Obs" tabindex="12" class="text" onchange=""></textarea>' +
                                    '</div><div>&nbsp;</div><div>&nbsp;</div>',
                                Vet_Pru: '<div class="footerFact">' +
                                    '<label>SUBTOTAL:</label>' +
                                    '<label>NO OBJ. IVA:</label>' +
                                    '<label>TARIFA 0%:</label>' +
                                    '<label>TARIFA <span class="iva_por_5">5%</span>:</label> ' +
                                    '<label>TARIFA <span class="iva_por">15</span>:</label> ' +
                                    '<label><span class="iva_por_total"></span>TOTAL IVA:</label><label>ICE:</label>' +
                                    '<label>DESCUENTO:</label>' +
                                    /* Casilleros adicionales */
                                    '<label><input type="checkbox" style="cursor: pointer;transform: scale(1.2);" id="ch_prop" name="ch_prop" onchange="if(this.checked){ if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\'; } else { $(\'#t_prop\').val(0); } updateDocument();" class="text"> Propina 10%:</label>' +
                                    '<label class="total">TOTAL:</label>' +
                                    '</div>',
                                Vet_Imp: '<div class="footerFact formDatos" id="formTotales">' +
                                    '<input id="t_subtotal" name="t_subtotal" type="text" readonly/>' +
                                    '<input name="t_noiva" type="text" readonly/>' +
                                    '<input name="t_iva0" type="text" readonly/>' +
                                    '<input name="t_iva5" type="text" readonly/>' +
                                    '<input name="t_iva15" type="text" readonly/>' +
                                    '<input id="t_iva" name="t_iva" type="text" readonly/>' +
                                    '<input name="t_ice" type="text" readonly/>' +
                                    '<input id="t_descuento" name="t_descuento" type="text" onchange="updateDocument();" class="text" />' +
                                    /* Casilleros adicionales */
                                    '<input id="t_prop" name="t_prop" type="text" onchange="if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\';"  class="text"  />' +
                                    '<input id="t_rubros" name="t_rubros" type="text"  class="total" readonly/>' +
                                    '</div>',
                                Iva_Por: '<div class="footerFact formDatos">' +
                                    '<div style="height:56px;"></div>' +
                                    '<div style="position:absolute;text-align: left;">' +
                                    '<select id="Iva_Cod" name="Iva_Cod" style="max-width:100%;" onchange="changeIvas();" class="text">' + $('#Def_Ivas').html() + '</select>' +
                                    '</div><div style="height:75px;padding-top:38px;text-align: left;">' +
                                    '<input id="Vet_Des" name="Vet_Des" style="height:19px;position:absolute;display:none;" />' +
                                    '</div>'
                            }, false);


                            $("#Bodega_Cod").val($('#Bod_Cod').val());
                            $('#Bod_Cod').change(function() {
                                $("#Bodega_Cod").val($('#Bod_Cod').val());
                            });

                            // Gridview de los datos de PAGOS
                            $('#pagosGrid').createGrid({
                                // data:[], 
                                caption: 'Pagos',
                                rowNum: 10000000,
                                height: 'auto',
                                footerrow: true,
                                colModel: [
                                    { label: 'C&oacute;d.Int.', name: 'Vet_Num', key: true, width: 15, align: 'center', hidden: true },
                                    { label: 'fecha_ven.', name: 'Cpc_Ven', width: 15, align: 'center', hidden: true },
                                    { label: 'Ban_Cod.', name: 'Ban_Cod', width: 15, align: 'center', hidden: true },
                                    { label: 'Forma', name: 'Forma_Cod', width: 30, align: 'center', /*hidden:false,*/ classes: 'bgNoRight' },
                                    { label: 'Fec_che', name: 'Fec_che', width: 30, hidden: true, classes: 'bgNoRight' },
                                    { label: 'Tipo', name: 'Tipo_Cod', width: 30, align: 'center', /*hidden:false,*/ classes: 'bgNoRight', },
                                    { label: 'Pag_Pld', name: 'Pag_Pld', width: 30, hidden: true, classes: 'bgNoRight' },
                                    { label: 'Banco', name: 'Bak_Cod', width: 50, /*hidden:false,*/ align: 'center', classes: 'bgNoRight', },
                                    { label: 'Cta. Banco', name: 'Vet_Cue', width: 50, align: 'center', classes: 'bgNoRight' },
                                    { label: 'Doc./Cheque', name: 'Vet_Che', width: 50, align: 'center' },
                                    // dinero ingresado
                                    { label: 'Monto Ing.', name: 'Vet_Mon', width: 40, align: 'center', formatter: 'currency'},
                                    { label: 'Cambio', name: 'Vet_Cam', width: 40, align: 'center', formatter: 'currency' },
                                    { label: 'Monto', name: 'Vet_Tot', width: 40, align: 'right', formatter: 'currency', classes: 'bgNoColor' },
                                    // dias de plazo para credito
                                    { label: 'Dias. Plazo', name: 'Vet_Plz', width: 50, align: 'center'/*, hidden: true*/ },
                                    { label: '<i class="glyphicon glyphicon-remove"></i>', name: 'btn_remover', width: 20, align: 'center', formatter: 'gridButton',
                                        formatoptions: {
                                            action: deletePago,
                                            data: function(o) { return o.Vet_Num; },
                                            icon: 'remove', type: 'danger'
                                        }
                                    }
                                ],
                                loadComplete: function() {
                                    $(this).setGridSummary(['Vet_Tot'], {
                                        Vet_Che: '<div style="text-align:right;">TOTAL:</div>'
                                    });
                                }

                            }, true, 'pagosGridPager', {
                                view: false
                            }).gridButtonsAdd([
                                { caption: 'Agregar', buttonicon: 'glyphicon glyphicon-plus', class: 'a',
                                    onClickButton: function() {
                                        if ($('#Val_Pcc_2').val() * 1 <= 0) {
                                            $.alert('El saldo a cobrar es cero!');
                                            return;
                                        }
                                        registarPagos();
                                    }
                                }, {},
                                { caption: 'Al Contado', buttonicon: 'glyphicon glyphicon-usd',
                                    onClickButton: function() {
                                        if ($('#Val_Pcc_2').val() * 1 <= 0) {
                                            $.alert('El saldo a cobrar es cero!');
                                            return;
                                        }
                                        alContado();
                                    }
                                },
                                { caption: 'A Cr&eacute;dito', buttonicon: 'glyphicon glyphicon-credit-card',
                                    onClickButton: function() {
                                        if ($('#Val_Pcc_2').val() * 1 <= 0) {
                                            $.alert('El saldo a cobrar es cero!');
                                            return;
                                        }
                                        aCredito();
                                    }
                                }
                            ]);
                        </script>

                    </div>
                    <div id="editDoc" class="hidden">
                        <input name="Vet_Cod" />
                        <input name="Cpc_Cod" />
                        <input name="Tic_Sri" />
                        <input name="Com_Exi" />
                        <input name="Com_Cod" />
                        <input name="Caj_Cod" />
                        <input name="Cpc_Min" />
                    </div>
                </div>

                <div id="tab2" class="ui-tabs-panel">
                    <form id="formSearchReport" action="javascript:if(!$('#op_range').is(':checked') && !$('#op_cedul').is(':checked')) $.alert('Debe seleecionar al menos un filtro!'); else  $('#ReportResumen').Search('#formSearchReport', 'ajaxTotales');" class="form-horizontal normal">
                        <div class="row">
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtros:</legend>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Filtro:</label>
                                        <div class="col-sm-10">
                                            <span class="radioset">
                                                <input id="op_range" name="range" type="checkbox" onchange="setFilter('range',$(this));" value="S" checked><label for="op_range">Rango de Fechas</label>
                                                <input id="op_cedul" name="cedul" type="checkbox" onchange="setFilter('cedul',$(this));" value="S"><label for="op_cedul">&nbsp;Cliente&nbsp;</label>
                                                <!-- <input id="op_cedul" name="cedul" type="checkbox" onchange="setFilter('cedul',$(this)); $('.dateRangeInputs').toggle(!this.checked);" value="S"> -->
                                            </span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            <label>Estado:</label>&nbsp;
                                            <span class="radioset">
                                                <input id="op_est3" name="op_est" type="radio" value="T" checked='checked' style="cursor:pointer"><label for="op_est3"> Todas </label>
                                                <input id="op_est1" name="op_est" type="radio" value="A" checked='checked' style="cursor:pointer"><label for="op_est1"> Activas </label>
                                                <input id="op_est2" name="op_est" type="radio" value="I" style="cursor:pointer"><label for="op_est2">Anuladas</label>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-sm rangedate">Rango:</label>
                                        <div class="col-sm-10">
                                            <div class="input-group input-group-sm dateRangeInputs">
                                                <span class="range input-group-addon alert-info">Desde</span>
                                                <input type="text" name="Fec_Ini" class="form-control range" required="" value="<?php echo isset($_GET['Fec_Ini']) ? htmlspecialchars($_GET['Fec_Ini'], ENT_QUOTES, 'UTF-8') : date('Y-01-01'); ?>" />
                                                <span class="range input-group-addon alert-info">Hasta</span>
                                                <input type="text" name="Fec_Fin" class="form-control range" required="" value="<?php echo isset($_GET['Fec_Fin']) ? htmlspecialchars($_GET['Fec_Fin'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group cedul" style="display:none;">
                                        <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                                        <div class="col-xs-6" id="clieFormTemp">
                                            <input name="Cli_Cod" type="text" style="display:none;" />
                                            <input name="op_opciones" type="text" value="c" style="display: none;">
                                            <div class="input-group input-group-xs">
                                                <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#clieDialog',selectCliente, '#clieFormTemp');" type="text" placeholder="Ingrese Cliente..." class="form-control input-xs clearable dialogSearch cedul" tabindex="1" disabled="" />
                                                <span class="input-group-btn">
                                                    <button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs cedul" title="Buscar Cliente" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                </span>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="form-group cedul" style="display:none;">
                                        <label class="col-xs-2 control-label label-xs required">Cliente:</label>
                                        <div class="col-xs-6" id="clieFormTemp"><span name="cliente" class="form-control input-xs databind datatitle"></span></div>
                                    </div>
                                    <div class="form-group center">
                                        <button type="submit" class="btn btn-success btn-xs" title="Buscar"><i class="glyphicon glyphicon-search"></i> Buscar</button>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Tipos de Filtrado:</legend>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Sucursal:</label>
                                        <div class="col-sm-5">
                                            <?php $sucursal = $obBD_con1->getArray('sucursal.selectWhere', array('clean' => true, 'unsetCols' => true, 'addCols' => array('sucursal' => array('Suc_Cod', 'Suc_Des')), 'where' => array('Emp_Cod' => $Ses_Emp_Cod))); ?>
                                            <select name="Suc_Cod" class="form-control input-xs">
                                                <option value="T" selected="">
                                                    << TODAS >>
                                                </option>
                                                <?php foreach ($sucursal as $s) { ?>
                                                    <option value="<?php echo $s['Suc_Cod']; ?>"><?php echo $s['Suc_Des']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <label class="col-sm-2 control-label label-xs" style="margin-left: -10px;">Orden:</label>
                                        <div class="col-sm-3">
                                            <select name="CustomOrderBy" class="form-control input-xs">
                                                <option value="" selected="">
                                                    << Sin Ordenar >>
                                                </option>
                                                <option value="Cliente ASC">Cliente</option>
                                                <option value="Caj_Fec ASC">Fecha ASC</option>
                                                <option value="Caj_Fec DESC">Fecha DESC</option>
                                                <option value="Vet_Num ASC">Nro. Doc.</option>
                                                <option value="Tic_Des ASC">Tipo Doc.</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-top: 6px;">
                                        <label class="col-sm-2 control-label label-xs">Tipo.&nbsp;Doc.:</label>
                                        <div class="col-sm-5">
                                            <select name="Tic_Cod" class="form-control input-xs">
                                                <option value="T" selected="">
                                                    << TODOS >>
                                                </option>
                                                <?php
                                                function TicDes($v) {
                                                    return "$v[Tic_Sri] - $v[Tic_Des]";
                                                }
                                                function selFactura($v) {
                                                    return $v['Tic_Sri'] == 'T'; //antes estaba return $v['Tic_Sri'] == '01';
                                                }
                                                echo mb_convert_encoding($obBD_con1->htmlOptions($rs_tip_compr, 'Tic_Cod', 'TicDes', false, 'selFactura'), 'UTF-8', 'ISO-8859-1');
                                                ?>
                                            </select>
                                        </div>
                                        <!-- <div class="col-sm-4"><label class="label-xs"><input name="Chk_Ret" type="checkbox" id="Chk_Ret" class="check-big" value="S"><span>&nbsp; No Sujetas a Ret.</span></label></div> -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label label-xs" style="margin-left: -15px;">Tiene Retención:</label>
                                            <div class="col-sm-3">
                                                <select name="Chk_Ret" class="form-control input-xs" style="margin-top: 5px;">
                                                    <option value="T" selected="">
                                                        << Seleccione >>
                                                    </option>
                                                    <option value="S">SI</option>
                                                    <option value="NS">NO</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin-top: -10px;">
                                        <label class="col-sm-2 control-label label-xs">Vendedor:</label>
                                        <div class="col-sm-5">
                                            <?php $cajas =   $obBD_con2->getArrayConsulta(157, $Ses_Suc_Cod, $obBD_conexion); ?>
                                            <select name="Vnd_Cod" class="form-control input-xs">
                                                <option value="V" selected="">
                                                    << TODOS >>
                                                </option>
                                                <?php foreach ($cajas as $v) { ?>
                                                    <option value="<?php echo $v['Vnd_Cod']; ?>"><?php echo $v['Prs_Nom'] . " " . $v['Prs_Ape'] . "  (" . $v['Pun_Des'] . ")"; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <label class="col-sm-2 control-label label-xs" style="margin-left: -10px;">Agrupar Por:</label>
                                        <div class="col-sm-3">
                                            <select name="CustomGroupBy" class="form-control input-xs">
                                                <option value="" selected="">
                                                    << Sin Agrupar >>
                                                </option>
                                                <option value="Agr_Cli"> - Cliente -</option>
                                                <!-- <option value="Agr_Pro"> - Producto - </option> -->
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Forma de Pago:</label>
                                        <div class="col-sm-5">
                                            <select name="For_Cod" class="form-control input-xs">
                                                <option value="T" selected="">
                                                    << TODAS >>
                                                </option>
                                                <option value="Contado">Contado</option>
                                                <option value="Credito">Crédito</option>
                                            </select>
                                        </div>
                                        <label class="col-sm-2 control-label label-xs" style="margin-left: -10px;">Tiene Reembolso:</label>
                                        <div class="col-sm-3">
                                            <select name="Chk_Reem" id="Chk_Reem" class="form-control input-xs" style="margin-top: 5px;">
                                                <option value="T" selected="">
                                                    << Seleccione >>
                                                </option>
                                                <option value="S">SI</option>
                                                <option value="NS">NO</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Punto de SRI:</label>
                                        <div class="col-sm-5">
                                            <?php $punto =   $obBD_con2->getArrayConsulta(1588, $Ses_Suc_Cod, $obBD_conexion); ?>
                                            <select name="Pun_Cod" class="form-control input-xs" id="selectPuntoSri">
                                                <option value="T" selected="">
                                                    << TODOS >>
                                                </option>
                                                <?php foreach ($punto as $v) { ?>
                                                    <!-- <option value="<?php echo $v['Pun_Cod']; ?>"><?php echo $v['Pun_Des'] . ' - ' . $v['Pun_Sri']; ?></option> -->
                                                    <option value="<?php echo $v['Pun_Cod']; ?>" data-pun-sri="<?php echo $v['Pun_Sri']; ?>">
                                                        <?php echo '-- Punto de Emisión ' . $v['Pun_Sri'] . ' --'; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                            <input type="hidden" name="Pun_Sri" id="inputPunSri" value="">
                                            <script>
                                                document.getElementById('selectPuntoSri').addEventListener('change', function() {
                                                    const selected = this.options[this.selectedIndex];
                                                    const punSri = selected.dataset.punSri || '';
                                                    document.getElementById('inputPunSri').value = punSri;
                                                });
                                            </script>
                                        </div>
                                        <label class="col-sm-2 control-label label-xs" style="margin-left: -10px;">Doc.Pago:</label>
                                        <div class="col-sm-3">
                                            <?php $tipos_pago =   $obBD_con2->getArrayConsulta(175, $Ses_Suc_Cod, $obBD_conexion); ?>
                                            <select name="Pag_Cod" class="form-control input-xs" id="selectPagos">
                                                <option value="T" selected="">
                                                    << TODOS >>
                                                </option>
                                                <?php foreach ($tipos_pago as $v) { ?>
                                                    <option value="<?php echo $v['Pag_Cod']; ?>">
                                                        <?php echo  mb_convert_encoding($v['Pag_Des'], 'UTF-8', 'ISO-8859-1'); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-sm-12">
                            <div>
                                <table id="ReportResumen"></table>
                                <div id="ReportResumenPager"></div>
                                <div class="Titulos2">
                                    <span id="plan-footer">
                                        <strong>Leyenda:</strong>
                                        <span class="glyphicon glyphicon-ok green"></span> Con Retencion |
                                        <span class="glyphicon glyphicon-ok red"></span> Inactivo/Anulado
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab3" class="ui-tabs-panel"> <!--INICIO DEL BLOQUE DE LA ETIQUETA Ultimas -->
                    <div class="row">
                        <div class="col-xs-12">
                            <form id="formKardex" class="form-horizontal normal" action="javascript:$('#kardex').Search('#formKardex','ajaxKardex');">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Descripción Producto:</legend>
                                    <div class="row">
                                        <div class="col-xs-4">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Descripción:</label>
                                                <div class="col-xs-7">
                                                    <div class="input-group input-group-xs">
                                                        <input type="text" name="Pro_Cod" id="Pro_Cod" value="" style="display: none" />
                                                        <input id="producto" type="text" class="form-control" placeholder="Seleccione un Producto ..." required readonly />
                                                        <span class="input-group-btn">
                                                            <button class="btn btn-success" onclick="$('#proDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-search" title="Buscar Proveedor"></span></button>
                                                            <button class="btn btn-xs btn-success" onclick="clearAll();" type="button"><span class="glyphicon glyphicon-trash" title="Buscar Proveedor"></span></button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Marca:</label>
                                                <div class="col-xs-8">
                                                    <span class="form-control input-xs" id="pro_mar"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Adquisición:</label>
                                                <div class="col-xs-8">
                                                    <span class="form-control input-xs" id="pro_adq"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xs-4">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Categoria:</label>
                                                <div class="col-xs-8">
                                                    <span class="form-control input-xs" id="pro_cat"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Cod. Cat.:</label>
                                                <div class="col-xs-8">
                                                    <span class="form-control input-xs" id="cat_cod"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Observación:</label>
                                                <div class="col-xs-8">
                                                    <span class="form-control input-xs" id="pro_obs"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xs-4">
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">IVA:</label>
                                                <div class="col-xs-8">
                                                    <span class="form-control input-xs" id="pro_iva"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Cod. Barras:</label>
                                                <div class="col-xs-8">
                                                    <span class="form-control input-xs" id="pro_bar"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Ubicacion:</label>
                                                <div class="col-xs-8">
                                                    <span class="form-control input-xs" id="pro_ubi"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>

                                <div class="row">
                                    <div class="col-xs-4">
                                        <fieldset class="exa-fieldset">
                                            <legend class="Titulos2">Estado Actual:</legend>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Stock:</label>
                                                <div class="col-xs-8">
                                                    <span class="form-control input-xs" id="pro_stk"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Prec Prom.:</label>
                                                <div class="col-xs-8">
                                                    <span class="form-control input-xs" id="pro_pre"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Saldo Actual:</label>
                                                <div class="col-xs-8">
                                                    <span class="form-control input-xs" id="pro_sal"></span>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div class="col-xs-8">
                                        <fieldset class="exa-fieldset">
                                            <legend class="Titulos2">Filtros:</legend>
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <div class="form-group">
                                                        <label class="col-xs-2 control-label label-xs ">Desde:</label>
                                                        <div class="col-xs-3">
                                                            <input name="ini" type="text" id="ini" class="form-control input-xs">
                                                        </div>
                                                        <label class="col-xs-2 control-label label-xs ">Hasta:</label>
                                                        <div class="col-xs-3">
                                                            <input name="fin" type="text" id="fin" class="form-control input-xs">
                                                        </div>
                                                        <div class="col-xs-2">
                                                            <div class="">
                                                                <button type="button" onclick="this.form.submit();$('#kardex').jqGrid('setCaption', 'Salidas de Mercaderia '+' - '+($('#producto').val()!==''?$('#producto').val()+' - ':'')+'Desde '+ $('#ini').val()+' Hasta '+$('#fin').val());" class="btn btn-sm btn-success" title="Ejecutar Búsqueda">
                                                                    <span class="glyphicon glyphicon-filter"></span> &nbsp;Filtrar</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-xs-2 control-label label-xs ">Vendedor:</label>
                                                        <div class="col-xs-3">
                                                            <?php $vendedores = $obBD_con3->getArrayConsulta(5004, $Ses_Suc_Cod, $obBD_conexion1); ?>
                                                            <select name="Vnd_Cod" class="form-control input-xs">
                                                                <option value="" selected="">
                                                                    << TODOS >>
                                                                </option>
                                                                <?php foreach ($vendedores as $v) { ?>
                                                                    <?php echo "<option value='$v[Vnd_Cod]'>$v[Vendedor]</option>"; ?>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                        <!-- <label class="col-sm-2 control-label label-xs">Agrupar Por:</label>
                                                        <div class="col-sm-3">
                                                            <select name="CustomGroupBy" class="form-control input-xs">
                                                                <option value="T" selected=""><< Sin Agrupar >></option>
                                                                <option value="Agr_Cli"> - Cliente -</option>
                                                                <option value="Agr_Pro"> - Producto - </option>
                                                            </select>
                                                        </div> -->
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-xs-12" style="min-height: 350px;">
                            <table id="kardex"></table>
                            <div id="kardexPager"></div>
                            <script>
                                $(document).ready(function() {
                                    $.createDateRange('#ini', '#fin');
                                    $.createDateRange('#Fec_Ini_Tab4', '#Fec_Fin_Tab4');
                                    var kardexGrid = $("#kardex");
                                    kardexGrid.jqGrid({
                                        url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                        mtype: "GET",
                                        datatype: "local",
                                        regional: 'es',
                                        autowidth: true,
                                        shrinkToFit: true,
                                        height: 270,
                                        responsive: true,
                                        caption: '',
                                        hidegrid: false,
                                        cmTemplate: {
                                            sortable: false
                                        },
                                        colModel: [
                                            { label: '#', name: 'num', width: 20, align: "center" },
                                            { label: 'Cod.Int.', name: 'Vet_Key', key: true, hidden: true, viewable: true },
                                            { label: 'Fecha', name: 'Caj_Fec', align: "center", width: 30 },
                                            { label: 'Cliente', name: 'Prs_Ape', width: 70 },
                                            { label: 'Producto', name: 'Ite_Lar', width: 80 },
                                            { label: 'Des. Adicional', name: 'Des_Adi', width: 80 },
                                            { label: 'Num. Doc.', name: 'Vet_Num', width: 45, classes: 'columnHighlight2' },
                                            { label: 'Cant.', name: 'Vet_Can', width: 25, align: "right", formatter: 'interger', classes: 'columnHighlight1',
                                                summaryTpl: "{0}", summaryType: "sum", summaryRound: '2', summaryRoundType: 'round'
                                            },
                                            { label: 'V.Uni.', name: 'Vet_Pru', width: 35, align: "right", formatter: formatPrecio, classes: 'columnHighlight1' },
                                            { label: 'V.Tot.', name: 'Vet_Imp', width: 40, align: "right", formatter: 'currency',
                                                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' },
                                                summaryTpl: "{0}", summaryType: "sum", summaryRound: '2', summaryRoundType: 'round', classes: 'columnHighlight1'
                                            },
                                            { label: 'Desc.', name: 'Descuento', width: 25, align: "right", formatter: 'currency',
                                                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' },
                                                summaryTpl: "{0}", summaryType: "sum", summaryRound: '2', summaryRoundType: 'round', classes: 'columnHighlight2'
                                            },
                                            { label: 'IVA', name: 'Iva', width: 35, align: "right", formatter: 'currency',
                                                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' },
                                                summaryTpl: "{0}", summaryType: "sum", summaryRound: '2', summaryRoundType: 'round', classes: 'columnHighlight2'
                                            },
                                            { label: 'Total', name: 'Total', width: 40, align: "right", formatter: 'currency',
                                                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' },
                                                classes: 'columnHighlight2', summaryTpl: "{0}", summaryType: "sum", summaryRound: '2', summaryRoundType: 'round'
                                            },
                                            { label: 'C.Uni.', name: 'Unitario', width: 40, align: "right", formatter: formatPrecio, classes: 'columnHighlight2' },
                                        ],
                                        footerrow: true,
                                        rowNum: 1000,
                                        rowList: [1000, 5000, 10000, 15000, 20000],
                                        userDataOnFooter: true,
                                        totalPage: true,
                                        pager: "#kardexPager",
                                        loadComplete: function() {
                                            kardexGrid.jqGrid('footerData', 'set', {
                                                Vet_Can: kardexGrid.jqGrid('getCol', 'Vet_Can', false, 'sum'),
                                                Vet_Num: '<div style="text-align:right;">Totales:</div>',
                                                Total: kardexGrid.jqGrid('getCol', 'Total', false, 'sum'),
                                                Vet_Imp: kardexGrid.jqGrid('getCol', 'Vet_Imp', false, 'sum'),
                                                Iva: kardexGrid.jqGrid('getCol', 'Iva', false, 'sum'),
                                                Descuento: kardexGrid.jqGrid('getCol', 'Descuento', false, 'sum')
                                            });
                                        }
                                    });
                                    //seccion de pager de etiqueta por producto
                                    kardexGrid.navGrid('#kardexPager', {
                                        edit: false, add: false, del: false,
                                        search: false, refresh: true, view: true,
                                        position: "left", cloneToTop: false
                                        });
                                    // boton exportar a excel de etiqueta por producto
                                    kardexGrid.navButtonAdd('#kardexPager', {
                                        caption: " Exportar Excel&nbsp;",
                                        buttonicon: "glyphicon glyphicon-download-alt",
                                        onClickButton: function() {
                                            kardexGrid.jqGrid('exportGridExcel', {
                                                nombre: "Ventas",
                                                hoja: "HOJA 1",
                                                footer: true
                                            });
                                        },
                                        position: "last"
                                    });
                                    // boton imprimir de etiqueta por producto
                                    kardexGrid.navButtonAdd('#kardexPager', {
                                        caption: " Imprimir&nbsp;",
                                        buttonicon: "glyphicon glyphicon-print",
                                        onClickButton: function() {
                                            kardexGrid.jqGrid('printGrid', {
                                                nombre: "Ventas",
                                                hoja: "HOJA 1",
                                                footer: true
                                            });
                                        },
                                        position: "last"
                                    });

                                });

                                function formatInt(cellValue, options, rowdata, action) {
                                    if (cellValue === "" || cellValue * 1 === 0 || isNaN(cellValue) || cellValue === null || cellValue === 'null') return "";
                                    return cellValue;
                                }

                                function formatPrecio(cellValue, options, rowdata, action) {
                                    if (cellValue === "" || cellValue * 1 === 0 || isNaN(cellValue) || cellValue === null || cellValue === 'null') return "";
                                    var number = parseFloat(cellValue).toFixed(6);
                                    return number;
                                }

                                function formatValor(cellValue, options, rowdata, action) {
                                    if (cellValue === "" || cellValue * 1 === 0 || isNaN(cellValue) || cellValue === null || cellValue === 'null') return "";
                                    var number = parseFloat(cellValue).toFixed(2); //  Give us our number to 2 decimal places
                                    return $.fn.fmatter.call(this, "currency", number, options);
                                }
                            </script>
                        </div>
                    </div>
                </div> <!--FIN DEL BLOQUE DE LA ETIQUETA Ultimas -->

                <!-- <div id="tab4" class="ui-tabs-panel"> </div> POR SI NECESITAN OTRA PESTAÑA-->
                <div id="tab4" class="ui-tabs-panel">
                    <form id="formSearchDetalle" action="javascript:if(!$('#op_range_tab4').is(':checked') && !$('#op_cedul_tab4').is(':checked')) $.alert('Debe seleccionar al menos un filtro!'); else  $('#ReportDetalle').Search('#formSearchDetalle', 'ajaxDetalleVentas');" class="form-horizontal normal">
                        <div class="row">
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtros:</legend>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Filtro:</label>
                                        <div class="col-sm-10">
                                            <span class="radioset">
                                                <input id="op_range_tab4" name="range" type="checkbox" onchange="setFilter('range',$(this), 'tab4');" value="S" checked><label for="op_range_tab4">Rango de Fechas</label>
                                                <input id="op_cedul_tab4" name="cedul" type="checkbox" onchange="setFilter('cedul',$(this), 'tab4');" value="S"><label for="op_cedul_tab4">&nbsp;Cliente&nbsp;</label>
                                            </span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            <label>Estado:</label>&nbsp;
                                            <span class="radioset">
                                                <input id="op_est3_tab4" name="op_est" type="radio" value="T" style="cursor:pointer"><label for="op_est3_tab4"> Todas </label>
                                                <input id="op_est1_tab4" name="op_est" type="radio" value="A" checked='checked' style="cursor:pointer"><label for="op_est1_tab4"> Activas </label>
                                                <input id="op_est2_tab4" name="op_est" type="radio" value="I" style="cursor:pointer"><label for="op_est2_tab4">Anuladas</label>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-group range">
                                        <label class="col-sm-2 control-label label-sm">Rango:</label>
                                        <div class="col-sm-10">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-addon alert-info">Desde</span>
                                                <input type="text" id="Fec_Ini_Tab4" name="Fec_Ini" class="form-control range" required="" value="<?php echo date('Y-m-01'); ?>" />
                                                <span class="input-group-addon alert-info">Hasta</span>
                                                <input type="text" id="Fec_Fin_Tab4" name="Fec_Fin" class="form-control range" required="" value="<?php echo date('Y-m-d'); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group cedul" style="display:none;">
                                        <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                                        <div class="col-xs-6" id="clieFormTempTab4">
                                            <input name="Cli_Cod" type="text" style="display:none;" />
                                            <input name="op_opciones" type="text" value="c" style="display: none;">
                                            <div class="input-group input-group-xs">
                                                <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#clieDialog',selectClienteTab4, '#clieFormTempTab4');" type="text" placeholder="Ingrese Cliente..." class="form-control input-xs clearable dialogSearch cedul" tabindex="1" disabled="" />
                                                <span class="input-group-btn">
                                                    <button id="Cli_Btn_tab4" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs cedul" title="Buscar Cliente" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group cedul" style="display:none;">
                                        <label class="col-xs-2 control-label label-xs required">Cliente:</label>
                                        <div class="col-xs-6" id="clieFormTempTab4_2"><span name="cliente" class="form-control input-xs databind datatitle"></span></div>
                                    </div>
                                    <div class="form-group center">
                                        <button type="submit" class="btn btn-success btn-xs" title="Buscar"><i class="glyphicon glyphicon-search"></i> Buscar</button>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Tipos de Filtrado:</legend>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Sucursal:</label>
                                        <div class="col-sm-5">
                                            <select name="Suc_Cod" class="form-control input-xs">
                                                <option value="T" selected=""> << TODAS >> </option>
                                                <?php foreach ($sucursal as $s) { ?>
                                                    <option value="<?php echo $s['Suc_Cod']; ?>"><?php echo $s['Suc_Des']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <label class="col-sm-2 control-label label-xs" style="margin-left: -10px;">Orden:</label>
                                        <div class="col-sm-3">
                                            <select name="CustomOrderBy" class="form-control input-xs">
                                                <option value="" selected=""> << Sin Ordenar >> </option>
                                                <option value="Cliente ASC">Cliente</option>
                                                <option value="Caj_Fec ASC">Fecha ASC</option>
                                                <option value="Caj_Fec DESC">Fecha DESC</option>
                                                <option value="Vet_Num ASC">Nro. Doc.</option>
                                                <option value="Tic_Des ASC">Tipo Doc.</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-top: 6px;">
                                        <label class="col-sm-2 control-label label-xs">Tipo.&nbsp;Doc.:</label>
                                        <div class="col-sm-5">
                                            <select name="Tic_Cod" class="form-control input-xs">
                                                <!-- <option value="T" selected=""> << TODOS >> </option> -->
                                                <?php echo mb_convert_encoding($obBD_con1->htmlOptions($rs_tip_compr, 'Tic_Cod', 'TicDes', false, 'selFactura'), 'UTF-8', 'ISO-8859-1'); ?>
                                            </select>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-sm-12">
                            <div>
                                <table id="ReportDetalle"></table>
                                <div id="ReportDetallePager"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
    <div id="proDialog" title="B&uacute;squeda de Productos">
        <form class="form-horizontal normal">
            <fieldset>
                <legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>
                    <div class="col-md-5 radioset">
                        <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                        <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7">
                        <div class="input-group">
                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus class="form-control input-sm " />
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Producto"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </div> <!-- FIN DEL DIALOGO CUENTAS-->

    <script>
        $.createSearchDialog('proDialog', [
            { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'Descripción', name: 'Ite_Lar', width: 110 },
            { label: 'Desc.Corta', name: 'Ite_Cor', width: 110 },
            { label: 'Marca', name: 'Mar_Des', width: 40 },
            { label: 'Tipo', name: 'Cat_Des', width: 110, align: "center" },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 20, align: 'center', viewable: false,
                formatter: function(cellvalue, options, rowObject) {
                    return '<span class="btn btn-success btn-xs" title="Enviar al Cr&eacute;dito" onclick="SelectProd(\'' + rowObject.Pro_Cod + '\',\'' + rowObject.Ite_Lar + '\');"><i class="glyphicon glyphicon-arrow-right"></i></span>';
                }
            }
        ]);

        function SelectProd(id, desc) {
            $('#Pro_Cod').val(id);
            $('#producto').val(desc);
            var today = new Date();
            //$('#ini').datepicker("setDate", new Date(today.getTime() - (30 * 24 * 3600 * 1000)));
            //$('#fin').datepicker("setDate", today);
            $('#proDialog').dialog('close');
            $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>', {
                'Pro_Cod': id,
                'ajaxProd': true
            }, function(response) {
                if (response['success'] === true) {
                    $('#pro_cat').html(response['prod']['Cat_Des']);
                    $('#cat_cod').html(response['prod']['Pro_Cdc']);
                    $('#pro_obs').html(response['prod']['Pro_Obs']);

                    $('#pro_mar').html(response['prod']['Mar_Des']);
                    $('#pro_adq').html(response['prod']['Adq_Des']);

                    $('#pro_iva').html(response['prod']['Iva_Por']);
                    $('#pro_bar').html(response['prod']['Pro_Bar']);
                    $('#pro_ubi').html(response['prod']['Ubi_Des']);

                    $('#pro_stk').html(response['stocks']['Stock']);
                    $('#pro_pre').html(response['stocks']['Promedio']);
                    $('#pro_sal').html(response['stocks']['Saldo']);
                } else {
                    $.alert("No se logro obtener informacion del Producto!");
                }
            }, 'json').fail(function(error) {
                $.alert("El Servidor ha fallado en responder!");
            });;
            $('#kardex').jqGrid('setCaption', 'Salidas de Mercaderia ' + ' - ' + desc + ' - ' + 'Desde ' + $('#ini').val() + ' Hasta ' + $('#fin').val());
            $('#kardex').Search('#formKardex', 'ajaxKardex');
        }

        function clearAll() {
            $('#Pro_Cod').val('');
            $('#producto').val('');

            $('#pro_cat').html('');
            $('#cat_cod').html('');
            $('#pro_obs').html('');

            $('#pro_mar').html('');
            $('#pro_adq').html('');

            $('#pro_iva').html('');
            $('#pro_bar').html('');
            $('#pro_ubi').html('');

            $('#pro_stk').html('');
            $('#pro_pre').html('');
            $('#pro_sal').html('');

            $('#kardex').jqGrid('setCaption', '');
            // $('#kardex').clearGrid();
            $('#kardex').Search('#formKardex', 'ajaxKardex');
        }

        $(document).ready(function() {
            $("#Vnd_Cod").createChosen('input-xs');
            $("input[type=text].form-control").addClass('text');
        });
    </script>

    <script type="text/javascript">
        var sumatorias = [
            { label: 'No Obj. IVA', campo: 'NoIVA'},
            { label: 'Base 0%', campo: 'Sub_0' },
            { label: 'Base 5%', campo: 'Sub_5' },
            { label: 'Base 8%', campo: 'Sub_8' },
            { label: 'Base IVA', campo: 'Sub_12' },
            { label: 'Base 15%', campo: 'Sub_15' },
            { label: 'Descuento', campo: 'Descu' },
            { label: 'ICE', campo: 'Ice_Tot' },
            { label: 'IVA', campo: 'Iva_Tot' },
            { label: 'IRBPNR', campo: 'Irbpnr' },
            { label: 'TOTAL', campo: 'Total' },
            { label: 'Imp. Renta', campo: 'Tot_Renta' },
            { label: 'Ret. IVA', campo: 'Tot_Iva' }
        ];

        function sumNotNC(v, n, obj) {
            return isNaN(v) ? 0 : (obj['Tic_Sri'] === '04' ? -1 * v : v);
        }

        $(function() {
            $('#documentoMain').css('visibility', '').hide();
            $("#tab1").hide();
            $("#tab2").show();

            $('#documentoSearch').createTabs({
                activate: function(event, ui) {
                    if (ui.newPanel.attr('id') === 'tab4') {
                        $("#ReportDetalle").jqGrid("resizeGrid");
                    }
                }
            });
            var ReportResumen = $("#ReportResumen");
            var ReportDetalle = $("#ReportDetalle");

            ReportDetalle.createGrid({
                height: 230,
                autowidth: true,
                shrinkToFit: true,
                datatype: 'local',
                caption: "Resultados de la búsqueda (Detalle)",
                colModel: [
                    { label: 'Cód. Int.', name: 'Vet_Cod', width: 50, align: "center", key: true },
                    { label: 'No. Docto.', name: 'Secuencia', width: 100, align: "center" },
                    { label: 'Fecha', name: 'Caj_Fec', width: 80, align: "center" },
                    { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 80, align: "center" },
                    { label: 'Cliente', name: 'Cliente', width: 180 },
                    { label: 'Concepto', name: 'Ite_Lar', width: 150 },
                    { label: 'Cantidad', name: 'Vet_Can', width: 70, align: "right", 
                        formatter: function(cv) { return cv ? cv.replace(/[\r\n]+/g, '<br>') : ''; } 
                    },
                    { label: 'Detalle', name: 'Des_Adi', width: 170, 
                        formatter: function(cv) { return cv ? cv.replace(/[\r\n]+/g, '<br>') : ''; } 
                    },
                    { label: 'Total', name: 'Total', width: 80, align: "right", formatter: 'number' }
                ],
                footerrow: true,
                totalPage: true,
                totalCols: ['Total'],
                totalDefault: {
                    Des_Adi: '<div style="text-align:right; font-weight:bold;">TOTAL:</div>'
                },
                rowNum: 1000,
                pager: "#ReportDetallePager"
            }, false, "ReportDetallePager").gridButtonsAdd([null,
                { caption: "Exportar Excel&nbsp;", buttonicon: "download-alt",
                    onClickButton: function() {
                        ReportDetalle.jqGrid('exportGridExcel', {
                            nombre: "Ventas_Detalle",
                            hoja: "HOJA 1",
                            footer: true
                        });
                    },
                    position: "last"
                },
                { caption: "Imprimir/PDF", buttonicon: "print",
                    onClickButton: function() {
                        var params = {
                            op: 3,
                            hdd: 1,
                            txt_fec_ini: $('#Fec_Ini_Tab4').val(),
                            txt_fec_fin: $('#Fec_Fin_Tab4').val(),
                            optest: $('input[name="op_est"]:checked', '#formSearchDetalle').val(),
                            Tic_Cod: $('select[name="Tic_Cod"]', '#formSearchDetalle').val(),
                            Cli_Cod: $('input[name="Cli_Cod"]', '#formSearchDetalle').val(),
                            Suc_Cod: $('select[name="Suc_Cod"]', '#formSearchDetalle').val()
                        };
                        
                        // Crear un formulario temporal para enviar por POST al nuevo reporte 3.0
                        var $form = $('<form>', {
                            action: 'fac_pri_fac_total_3.0.php',
                            method: 'POST',
                            target: '_blank'
                        }).appendTo('body');

                        $.each(params, function(name, value) {
                            $('<input>').attr({
                                type: 'hidden',
                                name: name,
                                value: value
                            }).appendTo($form);
                        });

                        $form.submit().remove();
                    },
                    position: "last"
                }
            ]);

            ReportResumen.createGrid({
                height: 230,
                autowidth: true,
                shrinkToFit: false,
                datatype: 'local',
                stateCol: 'Cop_Est',
                caption: "Resultados de la búsqueda",
                postData: $("#formTotales").getData("ajaxTotales"),
                colModel: [
                    { label: 'Cod.Int.', name: 'Vet_Cod', index: 'Vet_Cod',width: "50px", key: true, hidden: false }, 
                    { label: 'Tip.Sri', name: 'Tic_Sri', width: 30, align: "center" }, 
                    { label: 'Tip.Doc.', name: 'Tic_Des', width: "125px", align: "center",
                        formatter: function(cellvalue, options, rowObject) {
                            var customGroupBy = $('select[name="CustomGroupBy"]').val();
                            return customGroupBy === 'Agr_Cli' ? 'Doc. Agrupado' : cellvalue;
                        }
                    },
                    { label: 'Nro.Doc.', name: 'Secuencia', width: "150px", align: "center",
                        cellattr: function(rowId, val, rawObject, cm, rdata) {
                            return 'style="' + excelFormats.text + '"';
                        },
                        formatter: function(cellvalue, options, rowObject) {
                            // Escapamos los datos JSON para evitar problemas con caracteres especiales
                            var jsonData = JSON.stringify(rowObject).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            // Verificamos si CustomGroupBy tiene el valor 'Agr_Cli'
                            var customGroupBy = $('select[name="CustomGroupBy"]').val();
                            if (customGroupBy === 'Agr_Cli') {
                                return cellvalue; // No se agrega el enlace si está agrupado por cliente
                            }
                            return '<a href="javascript:void(0);" onclick="cargarDoc(' + jsonData + ');">' + cellvalue + ' <span style="color:#254463" class="glyphicon glyphicon-new-window"></span></a>';
                        }
                    },
                    { label: 'sec.', name: 'Secuencia',  width: 30,  align: "center",   hidden: true,  }, 
                    { label: 'Cod.SRI.', name: 'Vet_Sri', width: "200px", align: "center",
                        cellattr: function(rowId, val, rawObject, cm, rdata) {
                            return 'style="' + excelFormats.text + '"';
                        },
                        tittle: false,
                    },
                    { label: 'Est.', name: 'Vet_Est', width: "30px", align: "center", key: true, hidden: true },
                    { label: 'Fecha', name: 'Caj_Fec', width: "75px", align: "center" }, 
                    { label: 'Cédula/Ruc', name: 'Prs_Ced', width: "100px", align: "center", excel: 'text' },
                    { label: 'Cod.Int.', name: 'Vet_Cod', width: "50px", align: "center", key: true, hidden: true },
                    { label: 'Cod.Compr.', name: 'Com_Cod', width: "50px", align: "center", hidden: true, key: true },
                    { label: 'Cliente', name: 'Cliente', width: "200px" },
                    { label: 'Observación', name: 'Vet_Obs', width: "200px" },
                    { label: 'No Obj. IVA', name: 'NoIVA', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'Base 0%', name: 'Sub_0', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'Base 5%', name: 'Sub_5', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'Base 8%', name: 'Sub_8', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'Base 12%', name: 'Sub_12', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'Base 15%', name: 'Sub_15', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'Desc', name: 'Descu', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'ICE', name: 'Ice_Tot', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'IVA',  name: 'Iva_Tot', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'IRBPNR', name: 'Irbpnr', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    // { label: 'Propina', name: 'Prop', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'TOTAL', name: 'Total', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'Reten.', name: 'Ret_Num', width: "50px", align: "center", hidden: true, formatter: 'truefalse',
                        formatoptions: {
                            yesMsg: function(o) {
                                return 'Ret. Num.: <u class="blue">' + o.Ret_Num + '</u>';
                            },
                            noMsg: 'Sin Retención',
                            yesColor: function(o) {
                                return o.Cop_Est === 'I' ? 'red' : 'green';
                            }
                        },
                        title: false
                        // cellattr: function(rowId, val, rawObject, cm, rdata) {
                        //     return 'style="' + excelFormats.text + '"';
                        // }
                    },
                    { label: 'Reten.', name: 'Ret_Exi', width: "50px", align: "center", formatter: 'truefalse',
                        formatoptions: {
                            yesMsg: function(o) {
                                return 'Ret. Num.: <u class="blue">' + o.Ret_Num + '</u>';
                            },
                            noMsg: 'Sin Retención',
                            yesColor: function(o) {
                                return o.Cop_Est === 'I' ? 'red' : 'green';
                            }
                        },
                        title: false
                        // cellattr: function(rowId, val, rawObject, cm, rdata) {
                        //     return 'style="' + excelFormats.text + '"';
                        // }
                    },
                    { label: 'Fec.Ret.', name: 'Ret_Fec', width: "75px", align: "center" },
                    { label: 'Num.Ret.', name: 'Ret_Num', width: "75px", align: "center" },
                    { label: 'Aut. Retención', name: 'Ret_Aut', width: "100px", align: "center", excel: 'text' },
                    { label: 'Renta', name: 'Tot_Renta', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'Ret. Iva', name: 'Tot_Iva', width: "75px", align: "right", formatter: 'number', summaryType: 'sumNotNC' },
                    { label: 'Compr.', name: 'Com_Codigo', width: "50px", align: "center", formatter: 'truefalse',
                        formatoptions: {
                            yesMsg: function(o) {
                                return o['Com_Est'] === 'I' ?
                                    'Comprobante: <u class="blue">' + o.Com_Codigo + '</u> Inactivo' :
                                    'Comprobante: <u class="blue">' + o.Com_Codigo + '</u>';
                            },
                            noMsg: /*'<u class="glyphicon glyphicon-remove red"></u>'*/ 'Sin Comprobante',
                            yesColor: function(o) {
                                return o['Com_Est'] === 'I' ? 'red' : 'green';
                            }
                        },
                        title: false
                    },
                    { label: 'Pago', name: 'Forma_Pago', width: "60px", align: "center" },
                    { label: 'Doc.Pago', name: 'FormasPago', width: "60px", align: "center" },
                    { label: '&nbsp;', name: 'act0', width: "30px", align: 'center', viewable: false, formatter: 'gridButton',
                        formatoptions: { action: 'viewInfo', title: 'Ver Documento', icon: 'info-sign', type: 'info' },
                        title: false
                    }
                ],
                loadComplete: function() {
                    var $grid = $(this);
                    var ids = $grid.jqGrid('getDataIDs');
                    ids.forEach(function(id) {
                        var rowData = $grid.jqGrid('getRowData', id);
                        if ($.trim(rowData.Vet_Est).toUpperCase() === 'I') {
                            $grid.jqGrid('setRowData', id, false, {
                                background: '#FADDDD'
                            });
                        }
                    });
                },
                footerrow: true,
                rowNum: 100,
                rowList: [100, 250, 500, 1000, 5000],
                userDataOnFooter: true,
                totalPage: true,
                totalCols: ['NoIVA','Sub_0', 'Sub_5', 'Sub_8', 'Sub_12', 'Sub_15', 'Descu', 'Ice_Tot', 'Iva_Tot', 'Irbpnr', 'Total', 'Tot_Renta', 'Tot_Iva'],
                totalDefault: {
                    Vet_Obs: '<div class="txtRight">TOTAL PAGINA:</div>'
                }
            }, false, "ReportResumenPager").gridButtonsAdd([null,
                { caption: "Exportar Excel&nbsp;", buttonicon: "download-alt",
                    onClickButton: function() {
                        ReportResumen.jqGrid('exportGridExcel', {
                            nombre: "Ventas",
                            hoja: "HOJA 1",
                            footer: true
                        });
                    },
                    position: "last"
                },
                { caption: "Imprimir&nbsp;", buttonicon: "print",
                    onClickButton: function() {
                        var chkRetValue = $('[name="Chk_Ret"]').val() || 'N';
                        var columnasExcluir = [];

                       // Exclusion de columnas segun lo necesitado
                        if (chkRetValue === 'T') { 
                            columnasExcluir = [0, 26, 32, 35];
                        } else if (chkRetValue === 'S') { 
                            columnasExcluir = [0, 26, 32, 35];
                        } else { 
                            columnasExcluir = [0, 25, 26, 27, 28, 29, 30, 31, 32, 35]; 
                        }

                        var html = generarReporteHTML("ReportResumen", {

                            <?php
                            $empresa = $obBD_con1->getRowConsulta('empresas.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod)), $obBD_conexion);
                            $Emp_Nom = $empresa['Emp_Nom'];
                            ?>
                            titulo: "<?php echo $Emp_Nom; ?>",
                            subtitulo: "Reporte de Ventas",
                            excluirColumnas: columnasExcluir,
                            camposTotales: ["NoIVA","Sub_0", "Sub_5", "Sub_8", "Sub_12", "Sub_15",
                                "Descu", "Ice_Tot", "Iva_Tot", "Irbpnr", "Total",
                                "Tot_Renta", "Tot_Iva"
                            ]
                        });

                        if (html) {
                            var win = window.open('', '_blank');
                            win.document.write(html);
                            win.document.close();
                            win.focus();
                            win.print();
                        }
                    }
                },
                { caption: "Exportar PDF&nbsp;.Zip",
                    buttonicon: "download-alt",
                    onClickButton: function() {
                        descargarPDF();
                    },
                    position: "last"
                },
                { caption: "Exportar XML&nbsp;.zip",
                    buttonicon: "download-alt",
                    onClickButton: function() {
                        descargarXML();
                    },
                    position: "last"
                }
            ]);

            $('.dateRangeInputs').createDateRange(30);
            $('#sumatorias').createDialogDetail([
                { label: 'Campo', name: 'label', width: "75px" },
                { label: 'Valor', name: 'val', width: "75px", align: "right", formatter: 'currency' }
            ]);
        });

        // Vista previa del detalle de la factura
        function viewInfo(doc) {
            $('#docDetaDialog').setData(doc);
            $('#RetenViewGrid')[$.varValid(doc['Com_Cod']) && doc['Ret_Exi'] === 'S' ? 'show' : 'hide']();
            $('#itemsGrid').jqGrid('clearGridData');
            $.post('', {
                'docDetalle': true,
                'Vet_Cod': doc['Vet_Cod'],
                'Com_Cod': doc['Com_Cod']
            }, function(resp) {
                $('#detaDocu').setRows(resp['Vet_items']);
                if (resp['Vet_items']) {
                    $.each(resp['Vet_items'], function(x, item) {
                        addItem(item, item['Vet_Can'], item['Vet_Pru']);
                    });
                    updateDocument();
                }
                if (resp['Vet_items'] && resp['Vet_items'].length > 0) {
                    const retenData = resp['Vet_items'].map(item => ({
                        Ren_Cod: item.Ret_Ren_Cod,
                        Ren_Ret: item.Ret_Ren_Ret,
                        Ren_Rete: 'Renta',
                        Ren_Sri: item.Ret_Ren_Sri,
                        Ren_Con: item.Ret_Ren_Con,
                        Ren_Imp: item.Vet_Imp,
                        Ren_Por: item.Ret_Ren_Por,
                        Ren_Val: (item.Vet_Imp * item.Ret_Ren_Por / 100).toFixed(2)
                    })).filter(item => item.Ren_Cod); // Filter out items without Ret_Ren_Cod
                    $('#detaReten').setRows(retenData);
                }
                $('#docDetaDialog').dialog('open').updateGridsSizes();
            }, 'json').fail(function() {
                $.alert('error inesperado');
            });
        }

        // funcion generalizada para Imprimir y Exportar a Excel
        function generarReporteHTML(gridId, opciones) {
            var grid = $("#" + gridId);
            var gridData = grid.jqGrid('getRowData');

            if (gridData.length === 0) {
                $.alert('No hay datos para procesar.');
                return null;
            }

            var excludedIndexes = opciones.excluirColumnas || [];
            var titulo = opciones.titulo || "Reporte";
            var subtitulo = opciones.subtitulo || "Reporte de Ventas";
            var mostrarTotales = opciones.mostrarTotales !== false;
            var colModel = grid.jqGrid('getGridParam', 'colModel');
            var camposTotales = opciones.camposTotales || [];

            // Calcular totales
            var totals = {};
            camposTotales.forEach(function(key) {
                totals[key] = 0;
                gridData.forEach(function(row) {
                    totals[key] += parseFloat(row[key]) || 0;
                });
            });

            var htmlContent = '<html><head><title>' + titulo + '</title>';
            htmlContent += '<style>';
            htmlContent += '@media print { tfoot {display: none !important;} }';
            htmlContent += 'table {width: 100%; border-collapse: collapse;}';
            htmlContent += 'th, td {border: 1px solid black; padding: 5px; text-align: left; font-size: 12px;}';
            htmlContent += 'th {background-color: #f2f2f2;}';
            htmlContent += '.totales-final {font-weight: bold; background-color: #eee;}';
            htmlContent += '.ajustar-texto { word-break: break-word; white-space: normal; max-width: 98px; font-size: 11px; }';
            htmlContent += '.formato-texto { mso-number-format:"\@"; }'; // Estilo específico para Excel
            htmlContent += '</style>';
            htmlContent += '</head><body>';

            // Encabezado con título y subtítulo
            htmlContent += '<h2 style="text-align: center;">' + titulo + '</h2>';
            htmlContent += '<h3 style="text-align: center;">' + subtitulo + '</h3>';
            if ($('input[name="Fec_Ini"]').val() || $('input[name="Fec_Fin"]').val()) {
                const formatDate = (date) => {
                    const [year, month, day] = date.split('-');
                    return `${day}-${month}-${year}`;
                };
                const formattedStartDate = $('input[name="Fec_Ini"]').val() ? formatDate($('input[name="Fec_Ini"]').val()) : '';
                const formattedEndDate = $('input[name="Fec_Fin"]').val() ? formatDate($('input[name="Fec_Fin"]').val()) : '';
                htmlContent += '<p style="text-align: center;"><strong>Desde:</strong> ' + formattedStartDate + ' &nbsp;&nbsp;&nbsp; <strong>Hasta:</strong> ' + formattedEndDate + '</p>';
            }

            htmlContent += '<table><thead><tr>';
            htmlContent += '<th>#</th>';

            var includedColumns = [];
            colModel.forEach(function(col, idx) {
                if (!col.hidden && excludedIndexes.indexOf(idx) === -1) {
                    htmlContent += '<th>' + col.label + '</th>';
                    includedColumns.push({
                        name: col.name,
                        isText: ['Prs_Ced', 'Ret_Aut'].includes(col.name) // Identificar columnas que deben ser texto
                    });
                }
            });

            htmlContent += '</tr></thead><tbody>';

            // Filas de datos
            gridData.forEach(function(row, idx) {
                htmlContent += '<tr>';
                htmlContent += '<td>' + (idx + 1) + '</td>';

                includedColumns.forEach(function(col) {
                    var estilo = '';
                    if (col.name === 'Ret_Aut') {
                        estilo = 'class="formato-texto ajustar-texto" style="mso-number-format:\\@;"';
                    } else if (col.isText) {
                        estilo = 'class="formato-texto"';
                    } else if (col.name === 'Cliente') {
                        estilo = 'class="ajustar-texto"';
                    } else if (col.name === 'Vet_Sri') {
                        estilo = 'class="ajustar-texto"';
                    } else if (col.name === 'Vet_Obs') {
                        estilo = 'class="ajustar-texto"';
                    }
                    htmlContent += '<td ' + estilo + '>' + (row[col.name] || '') + '</td>';
                });

                htmlContent += '</tr>';
            });

            // Fila de Totales
            if (mostrarTotales && gridData.length > 0) {
                htmlContent += '<tr class="totales-final">';
                htmlContent += '<td></td>'; // columna de contador #

                var nombreColClientes = "Vet_Obs";
                includedColumns.forEach(function(col) {
                    if (col.name === nombreColClientes) {
                        htmlContent += '<td style="text-align: right;">TOTALES:</td>';
                    } else if (totals.hasOwnProperty(col.name)) {
                        htmlContent += '<td style="text-align: right;">' + totals[col.name].toFixed(2) + '</td>';
                    } else {
                        htmlContent += '<td></td>';
                    }
                });

                htmlContent += '</tr>';
            }

            htmlContent += '</tbody></table>';
            htmlContent += '<div style="text-align: right; margin-top: 20px;">Generado el ' + new Date().toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }).replace(/\//g, '-') + ' por EXA [Software Contable]</div>';
            htmlContent += '</body></html>';

            return htmlContent;
        }

        function setFilter(cl, $t, tab) {
            var ch = $t.is(':checked');
            // $('span.' + cl)[!ch ? 'addClass' : 'removeClass']('alert-disabled');
            // $('input.' + cl).prop('required', ch).prop('disabled', !ch);
            // $('div.form-group.' + cl)[!ch ? 'hide' : 'show']();
            var selector = tab ? '#' + tab + ' ' : '';
            $(selector + 'span.' + cl)[!ch ? 'addClass' : 'removeClass']('alert-disabled');
            $(selector + 'input.' + cl).prop('required', ch).prop('disabled', !ch);
            $(selector + 'div.form-group.' + cl)[!ch ? 'hide' : 'show']();
        }


        function descargarPDF() {
            var rows = $("#ReportResumen").jqGrid("getDataIDs");
            console.log(rows);
            var currentDomain = window.location.origin;
            if (rows.length > 0) {
                document.getElementById("loader").style.display = "block";
                var pdfDataList = [];
                for (var i = 0; i < rows.length; i++) {
                    var rowData = $("#ReportResumen").jqGrid("getRowData", rows[i]);
                    var id = rowData.Vet_Cod;
                    var secuencia = rowData.Secuencia; // Asegúrate que este campo existe
                    console.log(secuencia);
                    if (id && secuencia) {
                        pdfDataList.push({
                            id: id,
                            nombre: secuencia.trim() + ".pdf"
                        });
                    }
                }
                var zip = new JSZip();
                var promises = pdfDataList.map(function(pdfData) {
                    return new Promise(function(resolve) {
                        var xhr = new XMLHttpRequest();
                        var link = currentDomain + '/facturacion/COMPONENTES/tesPdfElectronicos.php?type=VENTAS&Doc_Cod=' + pdfData.id;
                        xhr.open("GET", link, true);
                        xhr.responseType = "blob";
                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                var blob = xhr.response;
                                zip.file(pdfData.nombre, blob); // Usa el nombre de la secuencia
                            }
                            resolve();
                        };
                        xhr.onerror = function() {
                            resolve(); // Siempre resolver para que Promise.all funcione
                        };
                        xhr.send();
                    });
                });
                Promise.all(promises).then(function() {
                    zip.generateAsync({
                        type: "blob"
                    }).then(function(content) {
                        var link = document.createElement("a");
                        link.href = window.URL.createObjectURL(content);
                        link.download = "archivosPDF.zip";
                        link.click();
                        document.getElementById("loader").style.display = "none";
                    });
                });
            }
        }
        //Descargar el XML de las facturas.
        function descargarXML() {
            var rows = $("#ReportResumen").jqGrid("getDataIDs");
            var currentDomain = window.location.origin;
            if (rows.length > 0) {
                document.getElementById("loader").style.display = "block";
                var xmlDataList = [];
                for (var i = 0; i < rows.length; i++) {
                    var rowData = $("#ReportResumen").jqGrid("getRowData", rows[i]);
                    var id = rowData.Vet_Sri; // nombre del XML
                    var secuencia = rowData.Secuencia; // nombre visible
                    if (id && secuencia) {
                        xmlDataList.push({
                            id: id.trim(),
                            nombre: secuencia.trim() + ".xml"
                        });
                    }
                }
                var zip = new JSZip();
                var promises = xmlDataList.map(function(xmlData) {
                    return new Promise(function(resolve) {
                        var link = currentDomain + "/facturacion/FRONT/" + Ses_Emp_Cod + "/" + xmlData.id + "_A.xml";
                        var xhr = new XMLHttpRequest();
                        xhr.open("GET", link, true);
                        xhr.responseType = "blob";
                        xhr.onload = function() {
                            if (xhr.status === 200 && xhr.response && xhr.response.size > 100) {
                                zip.file(xmlData.nombre, xhr.response);
                            } else {
                                console.warn("Archivo no válido:", xmlData.nombre);
                            }
                            resolve();
                        };
                        xhr.onerror = function() {
                            console.error("Error al descargar:", xmlData.nombre);
                            resolve();
                        };
                        xhr.send();
                    });
                });
                Promise.all(promises).then(function() {
                    zip.generateAsync({
                        type: "blob"
                    }).then(function(content) {
                        var link = document.createElement("a");
                        link.href = window.URL.createObjectURL(content);
                        link.download = "archivosXML.zip";
                        link.click();
                        document.getElementById("loader").style.display = "none";
                    });
                });
            }
        }
    </script>

    <script>
        // Inicio del dialogo para buscar cuentas contables
        $(function() {
            var asie = {
                height: 75,
                caption: 'Detalle Retención',
                sortable: true,
                sortname: 'Ren_Rete',
                sortorder: "desc",
                footerrow: true,
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
                loadComplete: function() {
                    $(this).setGridSummary(['Debe', 'Haber'], {
                        Glosa: "<div style='text-align:right;'>TOTALES:</div>"
                    });
                }
            };

            var opts = {
                height: 75,
                caption: 'Detalle Retención',
                sortable: true,
                sortname: 'Ren_Rete',
                sortorder: "desc",
                footerrow: true,
                colModel: [{
                        label: 'Cód.Int.',
                        name: 'Ren_Cod',
                        key: true,
                        width: 15,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'Cód.Int.',
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
                        label: 'Código ',
                        name: 'Ren_Sri',
                        width: 15,
                        align: 'center'
                    },
                    {
                        label: 'Descripción ',
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
                        label: 'Retención.',
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
                caption: 'Detalle Retención'
            }), true);
            $('#reteresult').getFootRow(true);
            $('#retencion').createGrid($.extend(opts, {
                height: 219,
                width: 593,
                responsive: false,
                caption: 'Detalle Retención <button type="button" role="button" tabindex="-1" class="ui-button ui-widget ui-state-default ui-corner-all pull-right" title="Cerrar Ventana" onclick="$(\'#retDetaDialog\').dialog(\'close\')"><span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span></button>'
            }), true);
            $('#retencion').getFootRow(true);
            $('#detaReten').createGrid($.extend(opts, {
                height: 'auto',
                width: 650,
                responsive: false,
                caption: null,
                rownumbers: false
            }), true);
            $('#detaReten').getFootRow(true);

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
                width: 700,
                noTitleStuff: true,
                noBorder: true
            });
        });
    </script> <!-- Fin del diólogo para Detalle de Factura -->

    <div id="docDetaDialog" title="Detalle de la Factura" style="display: none;">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Documento:</legend>
            <div class="form-horizontal normal" style="padding: 0 4px;">
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                    <div class="col-xs-4"><span name="Prs_Ced" class="form-control input-xs"></span></div>
                    <label class="col-xs-2 control-label label-xs">Doc.Num.:</label>
                    <div class="col-xs-4"><span name="Vet_Num" class="form-control input-xs"></span></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Cliente:</label>
                    <div class="col-xs-6">
                        <span name="Cliente" class="form-control input-xs"></span>
                        <span name="cliente_per" class="form-control input-xs" style="display: none;"></span>
                        <script>
                            $(function() {
                                $('#documentoSearch').on('tabsactivate', function(event, ui) {
                                    if (ui.newPanel.attr('id') === 'tab1') {
                                        $('span[name="cliente_per"]').show();
                                        $('span[name="Cliente"]').hide();
                                    } else if (ui.newPanel.attr('id') === 'tab2') {
                                        $('span[name="Cliente"]').show();
                                        $('span[name="cliente_per"]').hide();
                                    }
                                });
                                // tab2 Seleccionada por default
                                $('span[name="Cliente"]').show();
                                $('span[name="cliente_per"]').hide();
                            });
                        </script>
                    </div>
                    <label class="col-xs-1 control-label label-xs">Fecha:</label>
                    <div class="col-xs-3">
                        <span name="Caj_Fec" class="form-control input-xs"></span>
                        <span name="Vet_Fec" class="form-control input-xs" style="display: none;"></span>
                        <script>
                            $(function() {
                                $('#documentoSearch').on('tabsactivate', function(event, ui) {
                                    if (ui.newPanel.attr('id') === 'tab1') {
                                        $('span[name="Vet_Fec"]').show();
                                        $('span[name="Caj_Fec"]').hide();
                                    } else if (ui.newPanel.attr('id') === 'tab2') {
                                        $('span[name="Caj_Fec"]').show();
                                        $('span[name="Vet_Fec"]').hide();
                                    }
                                });
                                //  tab2 Seleccionada por default
                                $('span[name="Caj_Fec"]').show();
                                $('span[name="Vet_Fec"]').hide();
                            });
                        </script>
                    </div>
                </div>
                <div class="form-group condensed">
                    <div class="col-xs-12" style="margin-top: 10px;">
                        <div class="pull-right">
                            <table id="detaDocu"></table>
                        </div>
                    </div>
                    <div class="col-xs-12" style="text-align: right; font-size: 8px; padding-top: 2px;">
                        <b>CREACIÓN:</b>
                        <span name="Vet_Sys" class="databind"></span> &nbsp;&nbsp;-&nbsp;&nbsp;
                        <b>USUARIO:</b>
                        <span name="vendedor_per" class="databind"></span>
                        <span name="Vendedor" class="databind"></span>
                    </div>
                </div>
            </div>
        </fieldset>
        <fieldset class="exa-fieldset" id="RetenViewGrid">
            <legend class="Titulos2">Retencion:</legend>
            <div class="form-horizontal normal" style="padding: 0 4px;">
                <div class="form-group">
                    <label class="col-xs-1 control-label label-xs">Numero.:</label>
                    <div class="col-xs-3" style="width: 180px;"><span name="Ret_Num" class="form-control input-xs"></span></div>
                    <label class="col-xs-1 control-label label-xs">Fecha:</label>
                    <div class="col-xs-3" style="width: 130px;"><span name="Ret_Fec" class="form-control input-xs"></span></div>
                    <label class="col-xs-1 control-label label-xs" style="margin-left: -5px ;margin-right: 30px;">Autorización.:</label>
                    <div class="col-xs-3"><span name="Ret_Aut" class="form-control input-xs"></span></div>
                </div>
                <div class="form-group condensed">
                    <div class="col-xs-12" style="margin-top: 10px;">
                        <div class="pull-right">
                            <table id="detaReten"></table>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>

    <script>
        $(function() {
            var opts = {
                height: 75,
                postData: {
                    CheListAjax: true
                },
                caption: 'Detalle Venta',
                colModel: [{
                        label: 'Cód.Int.',
                        name: 'Vet_Int',
                        key: true,
                        width: 15,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'Cantidad ',
                        name: 'Vet_Can',
                        width: 45,
                        align: 'right'
                    },
                    {
                        label: 'Item',
                        name: 'Ite_Lar',
                        width: 160
                    },
                    {
                        label: 'P. Unit.',
                        name: 'Vet_Pru',
                        width: 65,
                        align: 'right'
                    },
                    {
                        label: 'Importe',
                        name: 'Vet_Imp',
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
                ]
            };
            $('#detaDocu').createGrid($.extend(opts, {
                height: 'auto',
                width: 650,
                responsive: false,
                caption: null,
                rownumbers: false
            }), true);
        });
    </script>

    <!-- Inicio del diólogo para buscar clientes -->
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
                label: 'Cédula/RUC',
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
            $('#clieFormTemp,#viajesForm').setData($.extend(cliente, {
                op_opciones: 'c'
            }));
            $('#viajesSelectedGrid').setRows([]);
            $('#Cli_Con_Search').removeAttr('class').addClass('glyphicon glyphicon-' + (cliente['Cli_Con'] === 'S' ? 'ok green' : 'remove blue'));
            // $('#Cli_Esp_Search').removeAttr('class').addClass('glyphicon glyphicon-' + (cliente['Cli_Esp'] === 'S' ? 'ok green' : 'remove blue'));
            $('.viajes')[$.vv(cliente['Viajes']) && cliente['Viajes'].toNum() > 0 ? 'show' : 'hide']();
            $('#clieDialog').dialog('close');

            $.post("", {
                enableDisableCampos: true,
                Cli_Cod: cliente['Cli_Cod']
            }, function(responce) {
                if (responce['success'] === true) {

                    if (responce['data_ant'] === 'none' || !(responce['data_ant'] > 0)) {
                        $("#anticipo").css("display", "none");
                    } else {
                        $("#anticipo").css("display", "block");
                    }

                    $("#ant_msg").html(responce['data_ant'] === 'none' || !(responce['data_ant'] > 0) ? "$ 0.00" : $.numFormat(responce['data_ant']));
                    $("#ant_msg")[responce['data_ant'] === 'none' || !(responce['data_ant'] > 0) ? 'removeClass' : 'addClass']('alert alert-danger bold');
                } else {
                    $("#anticipo").css("display", "none");
                    $("#ant_msg").html("$ 0.00");
                    $("#ant_msg").removeClass('alert alert-danger bold');
                    $.alert(responce['message']);
                }
            }, 'json');
        }

        function selectClienteTab4(cliente) {
            $('#clieFormTempTab4, #clieFormTempTab4_2').setData($.extend(cliente, {
                op_opciones: 'c'
            }));
            $('#clieDialog').dialog('close');
        }
    </script>

    <script>
        // DIALOG create cliente
        $('#clieCreateDialog').createDialog({
            icon: 'plus',
            width: 500,
            height: 430
        });
        $('#For_Cod').val(1).trigger('change');
    </script>
    <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <div id="sumatorias" title="Sumatoria Reporte"></div>
</BODY>

</HTML>