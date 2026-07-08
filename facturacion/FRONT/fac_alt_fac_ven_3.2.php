<?php

/**
 * @abstract Permite realizar el registro de una descripcion adicional por item de venta
 * @author Alejandro Camacho
 * @version 1.0
 * Fecha de creación  2017-07-25
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factura.php');
require_once('../../inventario/LOGICA/inv_log_imei.php');
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
//Seccion de Aquisicion, Iva, Produ_Plan y det_plan de carga proforma
if (isset($prfAdicionalAjax)) {
    $detCargaPrf = array(
        'success' => true,
        'otroDetalle' => $obBD_con1->getArrayConsultaSql("SELECT adquisicio.Adq_Des, produ_plan.Pro_Cod, produ_plan.Pld_Cod, produ_plan.Tip_Pld, det_plan.Pld_Cdc, det_plan.Pld_Des, det_plan.Pla_Cod, iva.Iva_Por FROM producto
        INNER JOIN item ON item.Ite_Cod = producto.Ite_Cod
        INNER JOIN categorias ON categorias.Cat_Cod = item.Cat_Cod
        LEFT JOIN marca ON marca.Mar_Cod = producto.Ite_Cod
        INNER JOIN adquisicio ON adquisicio.Adq_Cod = producto.Adq_Cod
        INNER JOIN unidad ON unidad.Uni_Cod = producto.Uni_Cod
        INNER JOIN ubicacion ON ubicacion.Ubi_Cod = producto.Ubi_Cod
        INNER JOIN produ_plan ON produ_plan.Pro_Cod = producto.Pro_Cod
        INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod
        INNER JOIN iva ON iva.Iva_Cod = producto.Iva_Cod WHERE (categorias.Emp_Cod='$Ses_Emp_Cod') AND ( producto.Pro_Cod ='$Pro_Cod' ) AND (Tip_Pld='V' OR Tip_Pld='I') LIMIT 0, 50;", $obBD_conexion),
    );
    $obBD_con1->echoJson($detCargaPrf);
}


//Seccion de carga de detalle proforma y el vendedor $Prf_Cod
if (isset($profDetalleAjaxSC)) {
    $resProformas = array(
        'success' => true,
        'prfSinCuenta' => $obBD_con1->getArrayConsultaSql("SELECT proformas.Prf_Cod,Pfd_Int,proformas_det.Pro_Cod,Prf_Imp,Prf_Cant,unidad.Uni_Cod,Uni_Des,Prf_Pru,Prf_Des,
    Prf_Num,Prf_Obs,item.Ite_Cod,adquisicio.Adq_Cod,Ite_Lar,Adq_Des,Adq_Cor,
    iva.Iva_Cod,Iva_Por,Prf_Adi
  FROM
    proformas
    INNER JOIN proformas_det ON (proformas.Prf_Cod = proformas_det.Prf_Cod)
    INNER JOIN producto ON (proformas_det.Pro_Cod = producto.Pro_Cod)
    INNER JOIN iva ON (proformas_det.Iva_Cod = iva.Iva_Cod)
    INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
    INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
    INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
  WHERE proformas.Prf_Cod='$Prf_Cod';", $obBD_conexion),
    );
    $obBD_con1->echoJson($resProformas);
}

if (isset($profDetalleAjax)) {
    $resProformas = array(
        'success' => true,
        'todasPrf' => $obBD_con1->getArrayConsultaSql("SELECT proformas.Prf_Cod,Pfd_Int,proformas_det.Pro_Cod,Prf_Imp,Prf_Cant,unidad.Uni_Cod,Uni_Des,Prf_Pru,Prf_Des,
		  Prf_Num,Prf_Obs,item.Ite_Cod,adquisicio.Adq_Cod,Ite_Lar,Adq_Des,Adq_Cor,det_plan.Pld_Cod,Tip_Pld,Pld_Cdc,
		  Pld_Des,det_plan.Pla_Cod,iva.Iva_Cod,Iva_Por,Prf_Adi
		FROM
		  proformas
		  INNER JOIN proformas_det ON (proformas.Prf_Cod = proformas_det.Prf_Cod)
		  INNER JOIN producto ON (proformas_det.Pro_Cod = producto.Pro_Cod)
		  INNER JOIN iva ON (proformas_det.Iva_Cod = iva.Iva_Cod)
		  LEFT JOIN produ_plan ON (producto.Pro_Cod = produ_plan.Pro_Cod AND (Tip_Pld='V' OR Tip_Pld='I'))
		  INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
		  INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
		  INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
		  LEFT JOIN det_plan ON (det_plan.Pld_Cod = produ_plan.Pld_Cod)
        WHERE proformas.Prf_Cod='$Prf_Cod';", $obBD_conexion),

        'vendedorAct' => $obBD_con1->getArrayConsultaSql("SELECT vendedor.*, CONCAT(Prs_Nom,' ',Prs_Ape) AS Vendedor, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced, persona.Prs_Dir, persona.Prs_Cor, puntos_imp.Pun_Des AS Punto, puntos_imp.Suc_Cod FROM vendedor INNER JOIN persona ON persona.Prs_Cod=vendedor.Prs_Cod INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=vendedor.Pun_Cod WHERE (puntos_imp.Suc_Cod='$Ses_Suc_Cod') AND Vnd_Cod='$Vnd_Cod';", $obBD_conexion),

    );
    $obBD_con1->echoJson($resProformas);
}

//Seccion para listar las proformas registrdas
if (isset($prfAjax)) {
    $responce = $obBD_con1->getPageGridJson('proformas.selectWhere', $_GET, $obBD_conexion);
    $obBD_con1->echoLog($responce);
}

//Secci�n para listar los clientes registrados en la empresa
if (isset($clieAjax)) {
    $response = $obBD_con1->getPageGrid(1, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
    $Sel = $obBD_con1->select()->from('viaje', array('Viajes' => $obBD_con1->expr('COUNT(Via_Cod)')));
    require_once('../../transportecarga/LOGICA/tca_log_ticket.php');
    $obBD_ticket = new Class_Log_Datos_ticket;
    $obBD_conexion_ticket = new Class_Log_Conexion_ticket($Ses_Dat_Dis);
    foreach ($response['rows'] as &$v) {
        $Sel->unsetWhere()->where("Cli_Cod=? AND Via_Est='A' AND Vet_Cod IS NULL", $v['Cli_Cod']);
        $via = $obBD_con1->getRowConsulta(null, $Sel, $obBD_conexion);
        $v['Viajes'] = $via['Viajes'];
        // Contar tickets del cliente (solo activos, no facturados)
        $tickets_data = array('Emp_Cod' => $Ses_Emp_Cod, 'Cli_Cod' => $v['Cli_Cod'], 'Tck_Est' => 'A');
        $tickets_count = $obBD_ticket->getRowConsulta(50, $tickets_data, $obBD_conexion_ticket);
        $v['Tickets'] = isset($tickets_count['total']) ? intval($tickets_count['total']) : 0;
    }
    unset($v);
    $obBD_con1->echoJson($response);
}

/* ver si exite un cliente */
if (isset($provAjax2)) {
    $responce['rows'] = $obBD_con1->getArrayConsulta(2, $Prs_Ced . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $responce['total'] = count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}
/* guarda un nuevo cliente */
if (isset($guardaClieAjax)) {
    $data = $_POST;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $data['Cli_Cor'] = $data['Prs_Cor'];
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    if (empty($Prs_Cod)) {
        $obBD_con1->operacionobBD(3, $data, $obBD_conexion);
        $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
    } else {
        $pers = $obBD_con1->getRowConsulta('persona.selectWhere', array('clean' => true, 'Prs_Cod' => $Prs_Cod), $obBD_conexion);
        if (empty($Prs_Cor) && !empty($pers['Prs_Cor'])) $data['Cli_Cor'] = $pers['Prs_Cor'];
        $up = array();
        if (!empty($Prs_Cor) && empty($pers['Prs_Cor'])) $up['Prs_Cor'] = $Prs_Cor;
        if (!empty($Prs_Dir) && empty($pers['Prs_Dir'])) $up['Prs_Dir'] = $Prs_Dir;
        if (!empty($Prs_Tel) && empty($pers['Prs_Tel'])) $up['Prs_Tel'] = $Prs_Tel;

        if (!empty($up)) $obBD_con1->operacionobBD('persona.update', array_merge($up, array('where' => array('Prs_Cod' => $Prs_Cod))), $obBD_conexion);
    }
    $obBD_con1->operacionobBD(4, $data, $obBD_conexion);
    $data['Cli_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
    $data['cliente'] = trim($data['Prs_Ape'] . ' ' . $data['Prs_Nom']);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true, 'clie' => $data);
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacci�n!', 'error' => $obBD_con1->MsgError);
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
    $response['Veh_Cod'] = $obBD_con1->getArrayConsulta(179, array('Aut_Cod' => $Aut_Cod), $obBD_conexion);
    $response['Vet_Num'] = $siguiente['siguiente'];
    $response['contador'] = $siguiente['contador'];
    echo json_encode($response);
    exit();
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
$llave = $obBD_con1->getRowConsulta(1005, $Ses_Emp_Cod, $obBD_conexion); //Traer las llaves para firmar documento

//Para realizar la negociacion de camaron
if ($configs["Cof_NegCam"] == 'S') {
    $grupo_empresas = $obBD_con1->getRowConsulta(176, $Ses_Emp_Cod, $obBD_conexion);
    if (isset($negociacionesAjax)) {
        $Emp_Cod = $Ses_Emp_Cod;
        if (!empty($grupo_empresas["Emp_Cod"])) {
            $empresas = array_merge((array)$Emp_Cod, (array)$grupo_empresas["Emp_Cod"]);
            $Emp_Cod = implode(",", $empresas);
        }
        $data_negociaciones = $obBD_con1->getArrayConsulta(168,  $Emp_Cod . '*' . $search, $obBD_conexion);
        $obBD_con1->echoJson($data_negociaciones);
    }
}

if (isset($viajesAjax)) {
    $page = $obBD_con1->getPageGrid('viaje', array_merge($_GET, array('setWhere' => array('isActive', 'notHasVetCod'))), $obBD_conexion);
    foreach ($page['rows'] as &$r) {
        $prod = $obBD_con1->getRowConsulta(13, '' . '*' . $Ses_Emp_Cod . '*' . '' . "* AND producto.Pro_Cod=$r[Pro_Cod]", $obBD_conexion);
        if ($configs['Cof_Con'] == 'S' && !empty($Pla_Cod)) {
            $cuenta = $obBD_con1->getRowConsulta(15, $Pla_Cod . '*' . $r['Pro_Cod'] . '*' . 'V', $obBD_conexion);
            if (!empty($cuenta['Pld_Cod'])) $prod = array_merge($prod, $cuenta);
        }
        $r['Producto'] = $prod;
        //$obBD_con1->echoLog($prod);
    }
    unset($r);
    $obBD_con1->echoJson($page);
}



// Seccion para cargar tickets para facturar
if (isset($ticketsAjax)) {
    require_once('../../transportecarga/LOGICA/tca_log_ticket.php');
    $obBD_ticket = new Class_Log_Datos_ticket;
    $obBD_conexion_ticket = new Class_Log_Conexion_ticket($Ses_Dat_Dis);
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rows = isset($_GET['rows']) ? intval($_GET['rows']) : 50;
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $data["Tck_Est"] = 'A'; // Solo tickets activos
    // Filtrar por Tck_Tip si se proporciona, por defecto solo activos
    if (isset($data['Tck_Tip']) && $data['Tck_Tip'] !== '') {
        $data['Tck_Tip'] = $data['Tck_Tip'];
    } else {
        $data['Tck_Tip'] = 'A';
    }
    // Obtener tickets disponibles (todos, pero ordenados: A primero, F después)
    $contar = $obBD_ticket->getRowConsulta(50, $data, $obBD_conexion_ticket);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    // Agregar ordenamiento: Tck_Tip='A' primero, luego Tck_Tip='F'
    $data["order"] = "ORDER BY CASE WHEN ticket_cantera.Tck_Tip = 'A' THEN 0 ELSE 1 END, ticket_cantera.Tck_Cod DESC";
    if ($contar['total'] > 0) {
        $tickets = $obBD_ticket->getArrayConsulta(50, $data, $obBD_conexion_ticket);
        // Para cada ticket, obtener sus detalles y agrupar por producto
        foreach ($tickets as &$ticket) {
            $detalles = $obBD_ticket->getArrayConsulta(30, array('Tck_Cod' => $ticket['Tck_Cod']), $obBD_conexion_ticket);
            // Agrupar detalles por producto
            $productos_ticket = array();
            foreach ($detalles as $det) {
                $pro_cod = $det['Pro_Cod'];
                if (!isset($productos_ticket[$pro_cod])) {
                    $prod = $obBD_con1->getRowConsulta(13, '' . '*' . $Ses_Emp_Cod . '*' . '' . "* AND producto.Pro_Cod=$pro_cod", $obBD_conexion);
                    if ($configs['Cof_Con'] == 'S' && !empty($Pla_Cod)) {
                        $cuenta = $obBD_con1->getRowConsulta(15, $Pla_Cod . '*' . $pro_cod . '*' . 'V', $obBD_conexion);
                        if (!empty($cuenta['Pld_Cod'])) $prod = array_merge($prod, $cuenta);
                    }
                    $productos_ticket[$pro_cod] = array('Producto' => $prod,  'Detalles' => array(), 'Tck_Can' => 0, 'Tck_Pru' => 0, 'Tck_Imp' => 0);
                }
                $productos_ticket[$pro_cod]['Detalles'][] = $det;
                $productos_ticket[$pro_cod]['Tck_Can'] += floatval($det['Dtk_Can']);
                $productos_ticket[$pro_cod]['Tck_Imp'] += floatval($det['Dtk_Tot']);
            }
            // Calcular precio unitario promedio
            foreach ($productos_ticket as &$prod_tick) {
                if ($prod_tick['Tck_Can'] > 0) {
                    $prod_tick['Tck_Pru'] = $prod_tick['Tck_Imp'] / $prod_tick['Tck_Can'];
                }
            }
            $ticket['Productos'] = array_values($productos_ticket);
            $ticket['Tck_Det'] = $detalles; // Guardar detalles completos
        }
        $responce['rows'] = $tickets;
    } else {
        $responce['rows'] = array();
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

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
    $obBD_con1->getPageGridJson(100, $rs_Punto['Pun_Cod'] . '*' . $Tic_Cod, $obBD_conexion, $page, $rows);
}

if (isset($getDataPunto)) {
    $resp = $obBD_con1->getRowConsulta(7, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
    $obBD_con1->echoJson($resp);
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
                    if (
                        empty($search) ||
                        stripos($imei['Ime_Num'], $search) !== false ||
                        stripos($imei['Ime_Tip_Des'], $search) !== false
                    ) {
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

/* Secci�n para realizar el guardado */
if (isset($saveDocument)) {
    $obBD_con1->validaCierrePeriodo('ventas', 'Caj_Fec', 'Vet_Cod', $Caj_Fec, null, $obBD_conexion, 'S');
    if (preg_match('/^9{8,}/', $Prs_Ced)  &&    $t_rubros > 50) {
        if ($Tic_Sri != 0) {
            $responce['message'] = "La normativa del SRI indica que si el cliente supera un monto de 50 USD, no debe ser considerado como consumidor final.";
        }
    }
    /* Creacion de Objetos de Conexiones para Proceso de Guardado de Venta*/
    $obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_facturaVenta;
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
    /*Verifica usuario tenga Permisos de Vendedor*/
    if (empty($vendedor['Vnd_Cod'])) {
        $responce['message'] = "No tiene permisos de Vendedor!";
    }
    $Vnd_Cod = $vendedor['Vnd_Cod'];
    $Vnd_Cod_aux = (!empty($select_vendedores)) ? $select_vendedores : NULL;
    $Vet_Sri = '';

    //if (is_string($items)) $items = json_decode(stripslashes($items), true);

    if (is_string($items)) {
        $items = json_decode(stripslashes($items), true);
        // Decodificar recursivamente cualquier campo que sea JSON string
        if (is_array($items)) {
            foreach ($items as &$item) {
                if (isset($item['Tickets']) && is_string($item['Tickets'])) {
                    //$decoded = json_decode($item['Tickets'], true);
                    $decoded = (json_decode( stripslashes($item['Tickets']), true));
                    if (is_array($decoded)) {
                        $item['Tickets'] = $decoded;
                    }
                }
                if (isset($item['Tickets_Originales']) && is_string($item['Tickets_Originales'])) {
                    //$decoded = json_decode($item['Tickets_Originales'], true);
                    $decoded =  (json_decode( stripslashes($item['Tickets_Originales']), true));
                    if (is_array($decoded)) {
                        $item['Tickets_Originales'] = $decoded;
                    }
                }
            }
            unset($item);
        }
    }



    try {
        //Seccion para verificar si la caja ya fue aperturada
        $rs_Caja = $obBD_con1->getRowConsulta(76, $rs_Punto['Pun_Cod'] . '*' . $Caj_Fec, $obBD_conexion);
        if (empty($rs_Caja['Caj_Cod'])) {
            //Secci�n para aperturar la caja a trav�s de insert a la tabla caja_aper
            $obBD_conIns->operacionobBD(77, $rs_Punto['Pun_Cod'] . '*' . $Caj_Fec, $obBD_conexionIns);
            //Secci�n para obtener el id ingresado en la tabla caja_aper
            $Caj_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
        } else {
            $Caj_Cod = $rs_Caja['Caj_Cod'];
        }
        /* valida que no exista el documento */
        $num_existe_gencod = $obBD_con1->getRowConsulta(50, $Ses_Suc_Cod . '*' . $Aut_Sri . '*' . $Vet_Num . '*' . $Vet_Cod . '*' . $Pun_Sri, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI
        if ($num_existe_gencod['total'] * 1 > 0) {
            $responce['message'] = "El documento No. $Vet_Num ya existe!";
        }
        if ($Aut_Tem == 'E' && $Vet_Num !== 0 && $input_autorizacion == '') {
            $Vet_Aut = 'N';
            require_once('../LOGICA/fac_log_electronica.php');
            $obBD_elect =  new Class_Log_Datos_Factura_Elect();
            //$claveAcceso=$obBD_con1->getDocClaveAcceso($Ses_Emp_Cod, $Ses_Suc_Cod, $Tic_Sri, $Aut_Cod, $Caj_Fec, $Vet_Num, $obBD_conexion);
            $claveAcceso = $obBD_elect->getClaveAcceso($Aut_Cod, $Caj_Fec, $Vet_Num, $obBD_conexion);
            if (empty($claveAcceso))
                $responce['message'] = "Error al generar <u>Clave de Acceso</u> del <i>Comprobante Electrónico</i>!";
        }
        if (!empty($input_autorizacion)) {
            $Vet_Aut = 'S';
            $claveAcceso = $input_autorizacion;
            $Vet_Sri = $input_autorizacion;
        }

        $rise = ($Tic_Sri * 1 == 2 || $Tic_Sri * 1 == 9); // rise, nota de venta
        if ($rise)
            $iva_cero = $obBD_con1->getRowConsulta(68, '0', $obBD_conexion);
        /* cierro en caso de error */
        if (!empty($responce['message'])) {
            echo json_encode($responce);
            exit();
        }
        if (isset($rets)) {
            if (empty($Ret_Fec) && count($rets) > 0) {
                $Ret_Fec = $hoy;
            }
        } else {
            $Ret_Fec = NULL;
        }
        /* Cabecera de la factura de venta */
        /*  Actualizado - actualiza 06/08/2018 ingresa el Cod de Proforma */
        $encabezado_venta = array('Tic_Cod' => $Tic_Cod, 'Cli_Cod' => $Cli_Cod, 'Ciu_Cod' => $Ciu_Cod, 'Caj_Cod' => $Caj_Cod, 'Vet_Ide' => $Vet_Ide, 'Vnd_Cod' => $Vnd_Cod, 'Vet_Num' => $Vet_Num, 'Vet_Obs' => $Vet_Obs, 'Aut_Cod' => $Aut_Cod, 'Vet_Des' => $Vet_Des, 'Vet_Hor' => $hora, 'Vet_Xml' => (isset($claveAcceso) ? $claveAcceso : ''), 'Vet_Aut' => (isset($Vet_Aut) ? $Vet_Aut : ''), 'Ret_Num' => $Ret_Num, 'Ret_Fec' => $Ret_Fec, 'Ret_Aut' => $Ret_Aut_Sri, 'Tpc_Cod' => $Tpc_Cod, 'Vet_Sri' => $Vet_Sri, 'Prf_Cod' => $Prf_Cod, 'Vnd_Cod_Aux' => $Vnd_Cod_aux, 'Vet_Prop' => $t_prop, 'Veh_Cod' => $Veh_Cod);
        $obBD_conIns->operacionobBD(140, $encabezado_venta, $obBD_conexionIns);
        $Vet_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
        //Registrar documento de negociación de camarón
        if (isset($Cod_Neg) && !empty($Cod_Neg) && $Cod_Neg != 0) {
            $obBD_conIns->operacionobBD(169, $Cod_Neg . '*' . $Vet_Cod . '*' . 'VNT' . '*' . $Tip_Prod, $obBD_conexionIns);
        }
        /* actualizo del Vet_Cod en proforma */
        $proformaCons =  $obBD_con1->getRowConsultaSql("select * from proformas where Prf_Cod='$Prf_Cod';", $obBD_conexionIns);
        $cod_pro_unique = array();
        /* Inserta datos en el detalle de la venta */
        $kardex = array('IoE' => 'E', 'Kar_Fec' => $Caj_Fec, 'Kar_Hor' => date("H:i:s"), 'Vet_Cod' => $Vet_Cod, 'Vnd_Cod' => $Vnd_Cod);
        //AGREGA EL DETALLE DE VENTA EN EL SQL 866
        $array_kardex = array();
        $s_add = true;
        foreach ($items as $i => $item) {
            $item['Vet_Cod'] = $Vet_Cod;
            $item['Vet_Ite'] = $i + 1;
            if ($rise) $item['Iva_Cod'] = $iva_cero['Iva_Cod'];
            /* Item Documento */
            if ($item['Ret_Mod'] * 1 > 0) {
                // verificar si existe retencion
                $referencia = $obBD_con1->getRowConsulta(122, array('Pla_Cod' => $Plan_Cod, 'Ren_Sri' => $item['Ret_Ren_Sri'], 'Ren_Por' => $item['Ret_Ren_Por']), $obBD_conexion);
                if (count($referencia) > 0) {
                    $item['Ret_Ren_Cod'] = $referencia['Ren_Cod'];
                } else {
                    $referencia = $obBD_con1->getRowConsulta(122, array('Pla_Cod' => $Plan_Cod, 'Ren_Sri' => $item['Ret_Ren_Sri']), $obBD_conexion);
                    $referencia['Ren_Por'] = $item['Ret_Ren_Por'];
                    $referencia['Ren_Est'] = 'I';
                    $obBD_conIns->operacionobBD(123, $referencia, $obBD_conexionIns);
                    $item['Ret_Ren_Cod'] = $obBD_conIns->insercionid($obBD_conexionIns);
                }
            }
            $obBD_conIns->operacionobBD(866, $item, $obBD_conexionIns);
            if (isset($item['Viajes']) && is_array($item['Viajes'])) // Nuevo de viajes transporte
                foreach ($item['Viajes'] as $Via_Cod) {
                    $obBD_conIns->operacionobBD('viaje.update', array('Vet_Cod' => $Vet_Cod, 'Vet_Ite' => $item['Vet_Ite'], 'where' => array('Via_Cod' => $Via_Cod)), $obBD_conexionIns);
                }


            $ticketsArray = null;
            // Intentar obtener Tickets de diferentes formas posibles
            if (isset($item['Tickets'])) {
                if (is_array($item['Tickets']) && !empty($item['Tickets'])) {
                    $ticketsArray = $item['Tickets'];
                } elseif (is_string($item['Tickets'])) {
                    $decoded = json_decode($item['Tickets'], true);
                    if (is_array($decoded) && !empty($decoded)) {
                        $ticketsArray = $decoded;
                    }
                }
            }
            if ($ticketsArray === null && isset($item['Tickets_Originales'])) {
                if (is_array($item['Tickets_Originales']) && !empty($item['Tickets_Originales'])) {
                    $ticketsArray = $item['Tickets_Originales'];
                } elseif (is_string($item['Tickets_Originales'])) {
                    $decoded = json_decode($item['Tickets_Originales'], true);
                    if (is_array($decoded) && !empty($decoded)) {
                        $ticketsArray = $decoded;
                    }
                }
            }

            if ($ticketsArray !== null && is_array($ticketsArray) && !empty($ticketsArray)) {
                foreach ($ticketsArray as $idx => $ticketData) { // Extraer Tck_Cod de diferentes formatos posibles
                    $Tck_Cod = 0;
                    if (is_numeric($ticketData)) {
                        $Tck_Cod = intval($ticketData);
                    } elseif (is_array($ticketData)) {
                        // Si es un array, buscar Tck_Cod dentro
                        if (isset($ticketData['Tck_Cod'])) {
                            $Tck_Cod = intval($ticketData['Tck_Cod']);
                        } elseif (isset($ticketData[0]) && is_numeric($ticketData[0])) {
                            $Tck_Cod = intval($ticketData[0]);
                        }
                    } elseif (is_string($ticketData) && is_numeric($ticketData)) {
                        $Tck_Cod = intval($ticketData);
                    }
                    if ($Tck_Cod > 0) {
                        try {
                            $params = array('Tck_Cod' => $Tck_Cod, 'Tck_Tip' => 'F');
                            $result = $obBD_conIns->operacionobBD(867, $params, $obBD_conexionIns);
                        } catch (Exception $e) {
                            throw new Exception('Error al actualizar estado ticket');
                        }
                    }
                }
            }


            /* Actualizar IMEI si el item tiene un IMEI asociado */
            if (isset($item['Ime_Cod']) && !empty($item['Ime_Cod']) && $item['Ime_Cod'] !== '0' && $item['Ime_Cod'] !== 'undefined') {
                // Actualizar directamente usando la conexión de la transacción actual
                $Ime_Cod = intval($item['Ime_Cod']);
                $Pro_Cod = $item['Pro_Cod'];
                $Ime_Num = isset($item['Ime_Num']) && !empty($item['Ime_Num']) ? mysqli_real_escape_string($obBD_conexionIns->conexion, $item['Ime_Num']) : '';
                $sql_update_imei = "UPDATE imei SET 
                                    Vet_Cod = $Vet_Cod,
                                    Ime_Tip = 'V'
                                    WHERE Ime_Cod = $Ime_Cod";
                mysqli_query($obBD_conexionIns->conexion, $sql_update_imei);
            }
            /* Control de Inventarios */
            if (($Tic_Sri * 1 != 0 || (isset($configs['Cof_Stk']) && $configs['Cof_Stk'] == 'S')) && ($item['Adq_Cor'] == 'B' || $item['Adq_Cor'] == 'SM')) {
                if ($Tic_Sri * 1 != 1 || (isset($configs['Cof_Stk2']) && $configs['Cof_Stk2'] == 'S')) {
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
                    if ($s_add == true) {
                        $kardexIE = array_merge($kardex, array('Kar_Int' => $i + 1, 'Iva_Cod' => $item['Iva_Cod'], 'Pro_Cod' => $item['Pro_Cod'], 'Kar_Sal' => (1) * $item['Vet_Can'],  'Kar_Pre' => $item['Vet_Pru'] * 1,  'Kar_Ime' => (1) * $item['Vet_Imp']));
                        array_push($array_kardex, $kardexIE);
                    }
                }
            }
        }
        //CONTROLAR LA VENTA DE PRODUCTOS CON STOCK NEGATIVO
         $venderStockNegativo = $obBD_con1->getRowConsulta(151, $Ses_Emp_Cod, $obBD_conexion);
         if ($venderStockNegativo['Cof_Stk_Neg'] == 'N') {
             foreach ($array_kardex as $k) {
                 $stockProducto = $obBD_con1->getRowConsulta(150, $k['Pro_Cod'], $obBD_conexion);
                 $productoDesc = $obBD_con1->getRowConsulta(152, $k['Pro_Cod'], $obBD_conexion);
                 if ($stockProducto['Stk_Can'] <  $k['Kar_Sal']) throw new Exception('Stock insuficiente para vender el producto: <u>' . $productoDesc['Ite_Lar'] . '</u>!');
             }
        }

        foreach ($array_kardex as $k) {
            $obBD_conIns->updateStockProd($Ses_Suc_Cod, $k, true, $obBD_conexion, $obBD_conexionIns);
        }
        /* REGISTRO PAGO VENTA */
        foreach ($pagos as $i => &$pag) {
            $pag['Vet_Num'] = $i;
            $pag['Vet_Cod'] = $Vet_Cod;
            $obBD_conIns->operacionobBD(72, $pag, $obBD_conexionIns);  // inserta pago_venta
            //INSETAR EN LA TABLA CHEQUES_EXT if pag_cod = 3  BAK_COD - VET_CUE - VET_CHE - VET_TOT - CPC_VET(FECHA) $CLIENTE  $CLI_COD
            if ($pag['Tipo_Cod'] == '3') {
                $pag['Cliente'] = $cliente;
                $pag['Cli_Cod'] = $Cli_Cod;
                $obBD_conIns->operacionobBD(145, $pag, $obBD_conexionIns);
                $Che_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
                $obBD_conIns->operacionobBD(146, array('Vet_Cod' => $Vet_Cod, 'Che_Cod' => $Che_Cod), $obBD_conexionIns);
            }
        }
        unset($pag);
        /* Creacion del comprobante contable */
        if ($configs['Cof_Con'] == 'S' && (($Tic_Sri * 1 != 0) || $Check_Comprobante * 1 === 1)) {
            $Com_Con = 'REG. VENTA ' . $Vet_Num . '  /' . $com_con;
            $Com_Fec = $Caj_Fec;
            $Tia_Asi = $obBD_con1->getRowConsulta(80, 7, $obBD_conexion);
            $meseCom = explode('-', $Com_Fec);
            $Com_Num = $obBD_con1->codigoComprAuto($Tia_Asi['Tia_Cod'], $Pec_Cod, $meseCom[1], $obBD_conexion); // Secuencia de comprobante por mes y por tipo
            $campo = 'Cli_Cod';
            /* Cabecera del Comprobante */
            $obBD_conIns->operacionobBD(70, $Pec_Cod . '*' . $Cli_Cod . '*' . $Com_Num . '*' . $Com_Fec . '*' . trim($Com_Con) . '*' . $Tia_Asi['Tia_Cod'] . '*' . $t_rubros . '*' . trim($Vet_Obs) . '*' . $campo, $obBD_conexionIns);
            $Com_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
            $obBD_conIns->operacionobBD(83, $Com_Cod . '*' . $Vet_Cod, $obBD_conexionIns); // relacion venta comprobante
            /* Inserta datos en el detalle del asiento (por items) */
            foreach ($items as &$item) {
                $cuenta = $obBD_con1->getRowConsulta(84, $Plan_Cod . '*' . $item['Pro_Cod'] . '*' . 'V', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del producto: <u>' . $item['Ite_Lar'] . '</u>!');
                $item['Pld_Cod'] = $cuenta['Pld_Cod'];
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . 'H' . '*' . ($item['Vet_Imp']) . '*' . $cuenta['Pld_Des'] . '*' . $item['Ite_Lar'] . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Item
            } //unset($item);
            /* IVA */
            if ($t_iva * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(88, $Plan_Cod, $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Iva Cobrado</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('H') . '*' . $t_iva . '*' . 'IVA' . '*' . 'IVA' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Iva
            }
            /* DESCUENTO */
            if ($Vet_Des > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Plan_Cod . '*' . 'DV', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Descuentos en Ventas</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $t_descuento . '*' . 'DESCUENTO' . '*' . 'DESCUENTO' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // descuento
            }
            if ($t_ice * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Plan_Cod . '*' . 'ICV', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>ICE en Ventas</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('H') . '*' . $t_ice . '*' . 'ICE' . '*' . 'ICE' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE
            }
            // validacion de parametrizacion de propina
            if ($t_prop * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Plan_Cod . '*' . 'VPR', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Propinas en Ventas</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('H') . '*' . $t_prop . '*' . 'PROPINA' . '*' . 'PROPINA' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE
            }
            /* Pago */
            /* REVISAR VARIOS PAGOS/ANTICIPOS */
            foreach ($pagos as $pag) {
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $pag['Vet_Tot'] . '*' . $pag['Vet_Num'] . '*' . ('Doc.' . $Vet_Num) . '*' . $pag['Pag_Pld'], $obBD_conexionIns);  // inserta asiento // Iva
                //ChromePhp::log($pag);
                /* CCPP Cuentas por Cobrar */ //ojo por ahora sigue dependiendo de contabilidad
                if ($pag['Forma_Cod'] * 1 == 2) {
                    $obBD_conIns->operacionobBD(55, $Com_Cod . '*' . $Vet_Cod . '*' . $pag['Cpc_Ven'] . '*' . (isset($pag['Cpc_Obs']) ? trim($pag['Cpc_Obs']) : ''), $obBD_conexionIns);
                    $Cpc_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                }
            }
            if (isset($rets)) {
                foreach ($rets as $ret) {
                    if ($ret['Ren_Sri'] * 1 === 338 && ($ret['Ren_Sri'] * 1 != 1.0 && $ret['Ren_Sri'] * 1 != 1.25 && $ret['Ren_Sri'] * 1 != 1.5 && $ret['Ren_Sri'] * 1 != 2.0)) {
                        $cuenta = $obBD_con1->getRowConsulta(103, $Plan_Cod . '*' . $ret['Ren_Sri'] . '*' . 'V', $obBD_conexion);
                    } else {
                        $cuenta = $obBD_con1->getRowConsulta(52, $Plan_Cod . '*' . $ret['Ren_Cod'] . '*' . 'V', $obBD_conexion);
                    }
                    if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) {
                        throw new Exception('Revisar la parametrizacion contable del Codigo: <u>' . $ret['Ren_Sri'] . '</u>!');
                    }
                    $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . 'D' . '*' . $ret['Ren_Val'] . '*' . $cuenta['Pld_Des'] . '*' . $ret['Ren_Con'] . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // retencion
                }
            }
        }
        if (isset($reembolsos) && is_array($reembolsos) && count($reembolsos > 0)) {
            foreach ($reembolsos as $rem) {
                $obBD_conIns->operacionobBD('venta_reembolsos.insert', array('Cop_Cod' => $rem, 'Vet_Cod' => $Vet_Cod), $obBD_conexionIns);
            }
        }
    } catch (Exception $ex) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns);

        $responce['message'] = $ex->getMessage();
        echo json_encode($responce);
        exit();
    }

    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
    if ($obBD_conIns->Error == 0) {
        $reportes = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        //$obBD_con1->echoLog($reportes);
        $response = array(
            'success' => true,
            'Vet_Impr' => "" . (!empty($reportes[1]) ? "$reportes[1]?Vet_Cod=" : "") . "$Vet_Cod",
            'Vet_Cod' => $Vet_Cod,
            'Vet_Num' => $Vet_Num,
            'Vet_Fec' => $Caj_Fec,
            'Tic_Des' => $Tic_Txt,
            'Vet_Prop' => $Vet_Prop
        );

        if ($Aut_Tem == 'E' && $input_autorizacion == '') {
            $rs_infoEmpresa = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
            $rs_infoCliente = $obBD_con1->getRowConsulta(61, $Aut_Cod, $obBD_conexion);
            $xml = $obBD_elect->createXmlFactura($Vet_Cod, $Aut_Cod, $claveAcceso, $obBD_conexion);
            $response['Vet_Xmls'] = baseUrl("../FRONT/" . $Ses_Emp_Cod . '/' . $claveAcceso . '.xml');
            $response['xml'] = base64_encode($xml);
        }

        if (!empty($Vet_Cod)) {
            $response['Vet_Data'] = array('Tic_Des' => $Tic_Txt, 'cliente' => $cliente, 'Vet_Num' => $Vet_Num, 'Vet_Fec' => $Caj_Fec, 'Vet_Aut' => $Aut_Sri);
            $response['Vet_Rows'] = $obBD_con1->getArrayConsulta(79, $Vet_Cod, $obBD_conexion);
            $response['Vet_Link'] = "" . (!empty($reportes[1]) ? "$reportes[1]?Vet_Cod=" : "") . "$Vet_Cod";
            if ($Aut_Tem == 'E')
                $response['Xml_Path'] = ("../FRONT/" . $Ses_Emp_Cod . '/' . $claveAcceso . '.xml');
        }
        if (!empty($Com_Cod)) {
            $response['Com_Data'] = array('Codigo' => $Com_Cod, 'Tia_Des' => $Tia_Asi['Tia_Des'], 'Com_Con' => $Vet_Obs, 'Com_Fec' => $Caj_Fec, 'Com_Val' => $t_rubros);
            $response['Com_Rows'] = $obBD_con1->getArrayConsulta(27, $Com_Cod, $obBD_conexion);
            $response['Com_Link'] = "" .  (!empty($reportes[2]) ? $reportes[2] . "?codigo=" : baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php") . "?codigo=") . $Com_Cod;

            // $response['Com_Link'] = "" . (!empty($reportes[2]) ? "$reportes[2]?codigo=" : "") . "$Com_Cod";
        }
        if (isset($rets)) {
            $response['Ret_Cod'] = $Ret_Cod;
            $response['Ret_Data'] = array('Ret_Num' => $Ret_Num, 'Aut_Sri' => $Ret_Aut_Sri, 'Ret_Fec' => $Ret_Fec, 'Ren_Tot' => $Ren_Tot, 'Iva_Ren_Tot' => $Iva_Ren_Tot, 'Ret_Ren_Tot' => $Ret_Ren_Tot);
            $response['Ret_Rows'] = $rets;
        }

        //FIRMAR  FACTURA Y ENVIAR AL CORREO
        $ruta_xmls = $APP_REAL_PATH . "/facturacion/FRONT/$Ses_Emp_Cod/"; //obtener ruta xml
        require_once('../../Librerias/FactElect/FirmaElectronica.php');
        $DocElect = new FirmaElectronica();
        $DocElect->setProduction(($configs['Cof_Fac'] * 1 == 2));
        $xml = $ruta_xmls . $claveAcceso; //Ruta del xml 
        $DocElect->setFileSignedPath($xml . '_F.xml'); //enviar a firmar xml
        //Datos para firma factura
        $response['data'] = $obBD_con1->getArrayConsulta(174, $Vet_Cod, $obBD_conexion);
        $response['Doc_Fir']   = $response['data'][0]['Doc_Fir'];
        $response['Doc_Env']   = $response['data'][0]['Doc_Env'];
        $response['Doc_Mail']  = $response['data'][0]['Doc_Mail'];
        $response['Doc_Num']   = $response['data'][0]['Doc_Num'];
        $response['Doc_Cod']   = $response['data'][0]['Doc_Cod'];
        $response['Doc_Aut']   = $response['data'][0]['Doc_Aut'];
        $response['Doc_Xml']   = $response['data'][0]['Doc_Xml'];
        $response['Doc_Sri']   = $response['data'][0]['Doc_Sri'];
        $response['Email']     = $response['data'][0]['Email'];
        $response['Aut_Cod_Est']  = " <span style='color:red'>NO SE PUDO AUTORIZAR EL DOCUMENTO</span>";
        //Fin datos para firma factura
        if (is_readable($xml . ".xml")) {
            $doc = $DocElect->sendToSign($xml . ".xml", $ruta_xmls . $llave['Lla_Rut'], $llave['Lla_Cla']);
            if ($doc['success'] == true && !empty($doc['xml'])) {
                $response['Doc_Fir'] = 'S';
            } else {
                $response['Error'] = 'Error al Firmar el documento!. ' . $doc['message'];
            }
        } else $response['Error'] = "Error no se encontro el <u>XML</u> ";


        //Autorizar factura
        if ($save_aut == 'save_aut') {
            if ($response['Doc_Fir'] == 'S' && $response['Doc_Env'] != 'S') {
                $result = $DocElect->sendToSri();
                if ($result['success'] == true) {
                    $response['Doc_Env'] = 'S';
                } else $response['Error'] = "<span>Error al enviar el documento!<br/>[<i style='color:red;'>$result[message]</i>]" . (!empty($result['informacionAdicional']) ? "<br/>$result[informacionAdicional]</span>" : '');
            }

            if ($response['Doc_Fir'] == 'S' && $response['Doc_Env'] == 'S' && $response['Doc_Aut'] != 'S') {
                $DocElect->setFileAutorized($xml . '_A.xml');
                $result = $DocElect->autorizarSri($response['Doc_Xml']);
                if ($result['success'] == true) {
                    $response['Doc_Aut'] = 'S';
                    $response['Selection'] = 'N';
                    $response['Error'] = 'Se Autorizó Correctamente!<br/><u class="green">' . $result['numeroAutorizacion'] . '</u>';
                    $response['numeroAutorizacion'] = $result['numeroAutorizacion'];
                    $response['Doc_Cod'] =  $Vet_Cod;
                    $obBD_con1->operacionobBD(173, $response, $obBD_conexion);
                    if (is_readable($xml . ".xml")) unlink($xml . ".xml");
                    if (is_readable($xml . "_F.xml")) unlink($xml . "_F.xml");
                    //if ($send_mail == true &&  $response['tabla'] != 'guias_remis') {
                    $response['Type'] = 'VENTAS';
                    $response['Doc_Mail'] = 'N';
                    $response['Aut_Cod_Est']  = "<span style='color:green;'>SI</span>";
                    $response['Xml_Path'] = ("../FRONT/" . $Ses_Emp_Cod . '/' . $claveAcceso . '_A.xml');
                    if (!empty($response['Email']) && trim($response['Email']) != '' && trim($response['Email']) != '-' && trim($response['Email']) != '0') {
                        require_once('../LOGICA/fac_log_electronica.php');
                        $obBD_elect = getClassElect($response['Type']);
                        $response['Doc_Mail'] = $obBD_elect->sendMailDoc($response['Doc_Cod'], $response['Email'], NULL, $obBD_conexion, true) == true ? 'S' : 'N';
                        if ($response['Doc_Mail'] == 'N') $response['error'] = "<span>Error al enviar el email!<br/>[<i style='color:blue;'>Se autorizo corectamente pero no se pudo enviar el mail</i>]</span>";
                    } else $response['error'] = "<span>Error al enviar el email!<br/>[<i style='color:blue;'>Se autorizo corectamente pero no se registro ningun email para enviar el documento</i>]</span>";
                    // }


                } else {
                    $response['Error'] = "<span>Error al autorizar el documento!<br/>[<i style='color:red;'>$result[message]</i>]" . (!empty($result['informacionAdicional']) ? "<br/>$result[informacionAdicional]</span>" : '');
                }
            }
        } //Fin firmar factura
    } else {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }

    //ChromePhp::log($obBD_conIns->MsgError);
    echo json_encode($response);
    exit();
}
/* busqueda de documentos */
if (isset($comprasReembolsoAjax)) {
    $obBD_con1->getPageGridJson('compras.selectWhere', array_merge($_GET, array('where' => "", 'setWhere' => array('isActive', 'setTotales', "notInReembolsos"))), $obBD_conexion);
}


//Cargar el proceso en caso de haber activado vendedores
$is_vendedor_activo = $obBD_con1->getRowConsulta(1003, $Ses_Emp_Cod  . '*' . $Ses_Usu_Cod, $obBD_conexion);



//REGISTRAR DATOS EXTRAS "Observación de la factura"
if (isset($saveExtRutAjax)) {
    $data = $_POST;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $obBD_con1->operacionobBD(1006, $data, $obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true, 'Registrado con éxito');
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', 'error' => $obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if (isset($LoadExtRutAjax)) {
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $response['rows'] = $obBD_con1->getArrayConsulta(1007, $Ses_Emp_Cod . '*' . $searchload, $obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $response['success'] = true;
    }
    $obBD_con1->echoJson($response);
    exit();
}

//Eliminar EXTRA
if (isset($detelteExtAjax)) {
    $data = $_GET;
    $obBD_con1->operacionobBD(1008, $data, $obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true, 'Registro eliminado');
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', 'error' => $obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}
// nueva consulta 08/10/2025
if (isset($saldoCCxCC)) {
    // Obtencion de valores
    $Pec_Cod = null;
    $Cli_Cod = null;
    if (isset($_POST['Pec_Cod'])) $Pec_Cod = $_POST['Pec_Cod'];
    if (isset($_POST['Cli_Cod'])) $Cli_Cod = $_POST['Cli_Cod'];

    // Empresa actual (desde sesión o variable global)
    $Emp_Cod = isset($Ses_Emp_Cod) ? $Ses_Emp_Cod : null;

    // Validar parámetros requeridos
    if ($Pec_Cod && $Emp_Cod && $Cli_Cod) {
        $rows = $obBD_con1->getArrayConsulta(180, $Pec_Cod . '*' . $Emp_Cod . '*' . $Cli_Cod, $obBD_conexion);
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

// nueva consulta 08/10/2025
if (isset($cupoCredito)) {
    $aux_Cli_Cod = isset($_POST['aux_Cli_Cod']) ? $_POST['aux_Cli_Cod'] : null;
    $response = array('success' => false, 'message' => 'No se pudo obtener el cupo de crédito');
    if ($aux_Cli_Cod) {
        $row = $obBD_con1->getRowConsulta(181, $aux_Cli_Cod, $obBD_conexion);
        if ($row) {
            $response = array('success' => true, 'data' => $row);
        }
    }
    echo json_encode($response);
    exit();
}



?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Ventas Registrar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <script language="javascript" src="../VALIDACIONES/fac_val_factura2.js?x=131"></script>
    <style>
        /* Ocultar campos de búsqueda automáticos del modal IMEI */
        #imeiForm fieldset:has(label:contains("Filtrar Por:")),
        #imeiForm fieldset:has(label:contains("Búsqueda:")),
        #imeiForm .form-group:has(label:contains("Filtrar Por:")),
        #imeiForm .form-group:has(label:contains("Búsqueda:")),
        #imeiForm label:contains("Filtrar Por:"),
        #imeiForm label:contains("Búsqueda:"),
        #imeiForm input[name="search"],
        #imeiForm button:contains("Buscar"),
        #imeiForm .radioset:has(input[value="d"]),
        #imeiForm .radioset:has(input[value="c"]) {
            display: none !important;
        }

        /* Ocultar todo fieldset excepto el que contiene "Tipo:" */
        #imeiForm fieldset:not(:has(label:contains("Tipo:"))) {
            display: none !important;
        }
    </style>
    <script>
        $('.panel-main').hide();
        inicializarDocVenta();
        $('.panel-main').show();
        //setTimeout(function(){ $("#Pec_Cod").trigger('change'); }, 1000);
        var docs, items, pagos, data = [],
            vet_num_ant = 0,
            tic_cod_ant = 0,
            Vet_Index = 1,
            Vet_Selected, index, Cof_Con = '<?php echo $configs['Cof_Con']; ?>';
        <?php $array_documentos = $obBD_con1->getArrayConsulta(8, $rs_Punto['Pun_Cod'], $obBD_conexion); ?>
        var array_documentos = <?php echo json_encode($array_documentos); ?>,
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

        .footrow td[aria-describedby="items_Vet_Pru"],
        .footrow td[aria-describedby="items_Vet_Imp"] {
            padding: 0 !important;
        }
    </style>
</HEAD>

<BODY>


    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar Documento de Venta</h3>
            <p id="cabeceraPuntoImp" class="text-right col-xs-12  " style="margin-top:-15px;">punto de impresion</p>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="factura">
                <div class="row">
                    <div class="col-xs-12" id="panelVentas">
                        <div class="row">
                            <div id="pagosDialog" title="Agregar Pagos">
                                <form id="pagosForm" class="form-horizontal normal" action="javascript:addPago($('#pagosForm').getData());">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Forma:</label>
                                        <div class="col-xs-6">
                                            <?php $rs_forma = $obBD_con1->getArrayConsulta(89, '', $obBD_conexion); ?>
                                            <select id="For_Cod" name="For_Cod" class="form-control input-xs readOnly" data-trigger="" required="">
                                                <option value="">Seleccione...</option>
                                                <?php foreach ($rs_forma as $row) {
                                                    echo "<option value='$row[For_Cod]'  " . ($row['For_Des'] == 'Contado' ? "selected=''" : '') . ">$row[For_Des]</option>";
                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Tipo:</label>
                                        <div class="col-xs-6">
                                            <?php $rs_tipo = $obBD_con1->getArrayConsulta(69, '', $obBD_conexion); ?>
                                            <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs readOnly" data-trigger="" required="">
                                                <?php
                                                echo "<option value='' data-forcod=''>Seleccione...</option>";
                                                foreach ($rs_tipo as $row) {
                                                    if (!endsWith(strtoupper(trim($row['Pag_Des'])), 'PAGAR') && !startsWith(strtoupper(trim($row['Pag_Des'])), 'CRUCE')) echo "<option value='$row[Pag_Cod]' data-forcod='$row[For_Cod]'>$row[Pag_Des]</option>";
                                                } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- <?php if ($configs['Cof_Con'] == 'S') { ?> -->
                                    <div class="form-group cuenta_pago">
                                        <label class="col-xs-3 control-label label-xs">Cuenta:</label>
                                        <div class="col-xs-9">
                                            <select id="Pag_Pld" name="Pag_Pld" class="form-control input-xs readOnly" required=""></select>
                                        </div>
                                    </div>
                                    <!-- <?php } ?> -->
                                    <!-- bancos en la base de datos -->
                                    <!-- cuentas bancaria -->

                                    <div class="form-group bancos">
                                        <label class="col-xs-3 control-label label-xs required">Banco:</label>
                                        <div class="col-xs-6">
                                            <?php $rs_bancos = $obBD_con1->getArrayConsulta(18, '', $obBD_conexion); ?>
                                            <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs readOnly" required="">
                                                <?php foreach ($rs_bancos as $row) {
                                                    echo "<option value='$row[Bak_Cod]' >$row[Bak_Des]</option>";
                                                } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group banco">
                                        <label class="col-xs-3 control-label label-xs required">Banco:</label>
                                        <div class="col-xs-6">
                                            <?php $rs_banco = $obBD_con1->getArrayConsulta(71, $Ses_Emp_Cod, $obBD_conexion); ?>
                                            <select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly" data-trigger="" required="">
                                                <?php foreach ($rs_banco as $row) {
                                                    echo "<option value='$row[Ban_Cod]' data-pldcod='$row[Pld_Cod]' data-bancue='$row[Ban_Cue]'>$row[Pld_Des]</option>";
                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group cuen_ban" style="display: none;">
                                        <label class="col-xs-3 control-label label-xs required">Cta&nbsp;Banco (desde):</label>
                                        <div class="col-xs-6">
                                            <input type="text" id="Vet_Cue" name="Vet_Cue" onchange="" class="form-control input-xs">
                                        </div>
                                    </div>
                                    <div class="form-group cuen_ban" style="display: none;">
                                        <label class="col-xs-3 control-label label-xs required">Número:</label>
                                        <div class="col-xs-6">
                                            <div class="input-group input-group-xs">
                                                <input type="text" id="Vet_Che" name="Vet_Che" onchange="" class="form-control input-xs">
                                                <span class="input-group-addon validate"><i class="glyphicon glyphicon-ok green"></i></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group fecha_cheque" style="display: none;">
                                        <label class="col-xs-3 control-label label-xs required">Cheque:</label>
                                        <div class="col-xs-6">
                                            <input id="Fec_che" name="Fec_che" type="text" class="form-control input-xs datepickers">
                                        </div>
                                    </div>

                                    <div class="form-group pagoCredito" style="display: none;">
                                        <input type="text" id="aux_Cli_Cod" name="aux_Cli_Cod" style="display:none;">
                                        <input type="text" name="aux_Prs_Cop" style="display: none;" />
                                        <label class="col-xs-3 control-label label-xs required">Dias de Credito:</label>
                                        <div class="col-xs-6">
                                            <input id="Dia_Cred" name="Dia_Cred" type="text" class="form-control input-xs"
                                                oninput="
                                                    var dias = parseInt(this.value, 10);
                                                    var fechaBase = document.getElementById('Caj_Fec') ? document.getElementById('Caj_Fec').value : '';
                                                    if (!isNaN(dias) && fechaBase) {
                                                        var parts = fechaBase.split('-');
                                                        var base = new Date(parts[0], parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                                                        base.setDate(base.getDate() + dias);
                                                        var yyyy = base.getFullYear();
                                                        var mm = ('0' + (base.getMonth() + 1)).slice(-2);
                                                        var dd = ('0' + base.getDate()).slice(-2);
                                                        document.getElementById('Cpc_Ven').value = yyyy + '-' + mm + '-' + dd;
                                                    }
                                                " />
                                        </div>
                                        <input type="text" name="Cpc_Min" style="display:none" />
                                        <div class="col-xs-12" style="margin-top: 8px;">
                                            <label class="col-xs-3 control-label label-xs required">Fecha Vencimiento:</label>
                                            <div class="col-xs-6">
                                                <input id="Cpc_Ven" name="Cpc_Ven" type="text" class="form-control input-xs datepickers" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group pagoCredito obs_credito" style="display: none;">
                                        <label class="col-xs-3 control-label label-xs">Observación:</label>
                                        <div class="col-xs-9">
                                            <textarea name="Cpc_Obs" class="form-control input-xs"></textarea>
                                        </div>
                                    </div>

                                    <div class="form-group info-tarjeta" style="display: none;">
                                        <label class="col-xs-3 control-label label-xs" title="Numero Lote">No Lote:</label>
                                        <div class="col-xs-9">
                                            <input id='Vet_Nlt' name="Vet_Nlt" type="text" class="form-control input-xs ">
                                        </div>
                                    </div>
                                    <div class="form-group info-tarjeta" style="display: none;">
                                        <label class="col-xs-3 control-label label-xs" title="Numero Transaccion">No Trans:</label>
                                        <div class="col-xs-9">
                                            <input id='Vet_Nts' name="Vet_Nts" type="text" class="form-control input-xs ">
                                        </div>
                                    </div>
                                    <div class="form-group info-tarjeta" style="display: none;">
                                        <label class="col-xs-3 control-label label-xs" title="Numero Autorizacion">No Auto:</label>
                                        <div class="col-xs-9">
                                            <input id='Vet_Nau' name="Vet_Nau" type="text" class="form-control input-xs ">
                                        </div>
                                    </div>
                                    <hr>

                                    <div class="form-group saldos">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-addon bold alert-warning" style="width:140px;">Saldo a Cobrar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right pull-right"></i></span>
                                                <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                                                <input id='saldo_pago' name="Vet_Tot" type="text" class="form-control bold span" style="text-align: right;font-size: 15px;padding-right: 20px;" required="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group saldos">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-addon bold alert-info" style="width:140px;">Monto Dinero&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right pull-right"></i></span>
                                                <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                                                <input id='monto_pago' name="Vet_Mon" type="text" class="form-control bold span clearable" style="text-align: right;font-size: 15px;padding-right: 20px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group saldos">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-sm">
                                                <span id='cam_sal' class="input-group-addon bold alert-danger" style="width:140px;"><b>Por Cobrar</b>&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right pull-right"></i></span>
                                                <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                                                <input id='cambio_pago' name="Vet_Cam" type="text" class="form-control bold span" style="text-align: right;font-size: 15px;padding-right: 20px;" readonly="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group center">
                                        <button class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> Agregar</button>
                                    </div>
                                </form>
                            </div>
                            <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">

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
                                            <label class="col-xs-2 control-label label-xs required">Cédula/RUC:</label>
                                            <div class="col-xs-6">
                                                <input name="Prs_Cod" type="text" style="display:none;" />
                                                <input name="Prs_Cor" type="text" style="display:none;" />
                                                <input name="Cli_Cod" type="text" style="display:none;" />
                                                <input name="op_opciones" type="text" value="c" style="display: none;">
                                                <div class="input-group input-group-xs">
                                                    <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#clieDialog',selectCliente);" type="text" placeholder="Ingrese Cliente..." class="form-control input-xs datatrigger clearable dialogSearch" tabindex="1" required="" />
                                                    <span class="input-group-btn">
                                                        <button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                        <!-- <button id="Rgt_Btn" type="button" onclick="$('#clieCreateForm').setData({}).find('.validate').find('i').removeAttr('class'); $('#clieCreateDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Cliente" tabindex="2"><span class="glyphicon glyphicon-plus"></span></button> -->
                                                        <button id="Via_Btn" type="button" onclick="$('#viajesGrid').clearGridData(); $('#viajesDialog').dialog('open');" class="btn btn-success btn-xs viajes" title="Seleccionar Viajes" tabindex="2" style="display:none;"><span class="fa fa-truck"></span></button>
                                                        <button id="prof_btn" type="button" onclick="$('#prfDialog').dialog('open');" class="btn btn-success btn-xs" title="Cargar Proforma" tabindex="2"><span class="glyphicon glyphicon-open-file"></span></button>
                                                        <button id="CargarTicketsBtn" type="button" class="btn btn-success btn-xs tickets" title="Cargar Tickets" tabindex="2" style="display:none;" onclick="$('#ticketsGrid').clearGridData(); $('#ticketsDialog').dialog('open');"><span class="fa fa-ticket"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <span class="radioset">
                                                    <input id="op_ide1" name="Vet_Ide" type="radio" value="4" disabled style="cursor:pointer" onchange="cambioTipoDoc()"><label title="Facturar como R.U.C" for="op_ide1">&nbsp;Ruc&nbsp; </label>
                                                    <input id="op_ide2" name="Vet_Ide" type="radio" value="5" disabled style="cursor:pointer" onchange="cambioTipoDoc()"><label title="Facturar como CEDULA" for="op_ide2">&nbsp;Ced&nbsp;</label>
                                                    <input id="op_ide3" name="Vet_Ide" type="radio" value="6" disabled style="cursor:pointer" onchange="cambioTipoDoc()"><label title="Facturar como PASAPORTE" for="op_ide3">&nbsp;Pas&nbsp;</label>
                                                    <input id="op_ide4" name="Vet_Ide" type="radio" value="7" disabled style="cursor:pointer" onchange="cambioTipoDoc()"><label title="Facturar como CONSUMIDOR FINAL" for="op_ide4">&nbsp;Con&nbsp;</label>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Cliente:</label>
                                            <div class="col-xs-10"><span id="Cliente" name="cliente" class="form-control input-xs databind datatitle"></span></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Dirección:</label>
                                            <div class="col-xs-4"><span name="Prs_Dir" type="text" class="form-control input-xs databind datatitle"></span></div>
                                            <label class="col-xs-1 control-label label-xs">Correo:</label>
                                            <div class="col-xs-5"><span name="Prs_Cor" type="text" class="form-control input-xs databind datatitle"></span></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs" style="margin-right: 10px;">Celular/Teléfono:</label>
                                            <div class="col-xs-4"><span name="Prs_Tel" type="text" class="form-control input-xs databind datatitle"></span></div>
                                            <?php if ($configs['Cof_Sld'] == 'S') { ?>
                                                <label class="col-xs-3 control-label label-xs" style="text-decoration: underline;">Saldo de CCxCC:</label>
                                                <div class="col-xs-3" style="margin-left: -10px;">
                                                    <input id="Cli_Sal" name="Cli_Sal" type="text" class="form-control input-xs databind" style="text-align: right;" readonly />
                                                </div>
                                        </div>
                                    <?php } ?>

                                    </fieldset>
                                    <div class="alert alert-warning" role="alert" style="font-weight:bold; font-size:12px; border:1.5px solid #ffeeba; background-color:#fdfbe6; color:#ff6666;">
                                        <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                                        <strong>¡Importante!</strong> A partir del <u>1ro de enero de 2026</u>, las ventas deben ser <b>autorizadas de manera inmediata.</b>
                                    </div>
                                </div>
                                <div class="col-md-7 col-xs-12">
                                    <fieldset class="exa-fieldset" id="docuFormTemp">
                                        <legend class="Titulos2">Datos del Documento</legend>
                                        <input type="text" name="Vet_Cod" style="display: none;" />
                                        <input type="text" name="Com_Cod" style="display: none;" />
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Periodo:</label>
                                            <div class="col-xs-2">
                                                <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" style="text-align: center;">
                                                    <?php $rs_perio = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
                                                    foreach ($rs_perio as $row) { ?>
                                                        <option value="<?php echo $row['Pec_Cod']; ?>" data-inicio="<?php echo $row['Pec_Fei']; ?>" data-fin="<?php echo $row['Pec_Fef']; ?>" data-PlaCod="<?php echo $row['Pla_Cod']; ?>"><?php echo $row['Anio']; ?></option>
                                                    <?php   } ?>
                                                </select>
                                            </div>
                                            <label class="col-xs-1 control-label label-xs">Fecha:</label>
                                            <div class="col-xs-3">
                                                <input type="text" id="Caj_Fec" name="Caj_Fec" class="form-control input-xs datepickers" style="text-align: center;">
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
                                            <label class="col-xs-2 control-label label-xs required">Número:</label>
                                            <div class="col-xs-5">
                                                <div class="input-group input-group-xs">
                                                    <span id="Pun_Sri" name="Pun_Sri" class="input-group-addon alert-info"></span>
                                                    <input type="text" id="Vet_Num" name="Vet_Num" onchange="validarTic_Cod()" class="form-control input-xs trigger" tabindex="5" required="" data-container="body" data-toggle="popover" />
                                                    <span class="input-group-addon validate"><i></i></span>
                                                </div>
                                            </div>
                                            <label id="mensajeAutorizacion" class="col-xs-5 control-label label-xs hidden  red"></label>

                                            <div class="form-check hidden" id="div_check_comp">
                                                <div class="col-xs-5">
                                                    <label class="form-check-label">
                                                        <input type="checkbox" id="Check_Comprobante" value=1 name="Check_Comprobante" class="form-check-input">
                                                        Crear Comprobante
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-xs-5">
                                                <div class="addAutorizacion">
                                                    <div class="input-group">
                                                        <span class="input-group-btn"><button type=button id="btnAddAut" title="Autorizacion Externa" class="btn-xs btn btn-warning"><span class="glyphicon glyphicon-alert"></span></button></span>
                                                        <input maxlength="55" minlength="49" title="minimo 49 caracteres" name="input_autorizacion" size="" class="form-control input-xs" />
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <div id="FPrfNum" class="form-group" style="display:none;">
                                            <label class="col-xs-2 control-label label-xs">Prof. Num:</label>
                                            <div class="col-xs-2">
                                                <div class="col-xs-12 input-group input-group-xs">
                                                    <span id="Prf_Num" name="Prf_Num" align='center' class="form-control input-xs databind"></span>
                                                    <span id="Prf_Cod" name="Prf_Cod" align='center' style="display: none;" class="form-control input-xs databind"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>


                                    <!--Verificar si existe el proceso -->
                                    <div class="col-md-6">
                                        <?php if (($is_vendedor_activo["total_rows"]) >= 1) {  ?>
                                            <!--Registrar el vendedor   -->
                                            <fieldset class="exa-fieldset" id="clieFormTemp">
                                                <legend class="Titulos2">Vendedor</legend>
                                                <div class="form-group">
                                                    <label class="col-xs-2 control-label label-xs ">Seleccionar:</label>
                                                    <div class="col-xs-10">
                                                        <?php $data_personal = $obBD_con1->getArrayConsulta(1004, $Ses_Emp_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion); ?>

                                                        <select id="select_vendedores" name="select_vendedores" class="form-control input-xs">
                                                            <option value="">- Seleccionar vendedor -</option>
                                                            <?php
                                                            foreach ($data_personal as $value) { ?>
                                                                <option value="<?php echo $value['Vnd_Cod'] ?>"><?php echo  $value['Vnd_Cod'] . " " . $value['Prs_Nom'] . " " . $value['Prs_Ape'] ?> </option>
                                                            <?php   }  ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </fieldset>
                                            <!-- Fin Registrar el vendedor -->
                                        <?php } ?>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Concepto contable:</label>
                                            <div class="col-xs-10">
                                                <textarea name="com_con" id="com_con" class="form-control input-xs"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Negociacion -->
                                    <?php if ($configs["Cof_NegCam"] == 'S') { ?>
                                        <div class="col-md-12">
                                            <fieldset class="exa-fieldset" id="formNeg">
                                                <legend class="Titulos2">Negociación:</legend>
                                                <div class="form-group col-md-6">
                                                    <label class="col-xs-4 control-label label-xs">Neg. camarón:</label>
                                                    <div class="col-xs-8">
                                                        <div class="input-group input-group-xs">
                                                            <input type="text" name="Num_Neg" id="Num_Neg" placeholder="Ingrese cod.Negociación..." class="form-control input-xs clearable dialogSearch" tabindex="1" readonly />
                                                            <input type="text" name="Cod_Neg" id="Cod_Neg" style="display:none;" />
                                                            <span class="input-group-btn">
                                                                <button id="Prv_Btn_" type="button" onclick="$('#negDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group col-md-6 d-flex align-items-center">
                                                    <small>Ninguno</small>
                                                    <input type="radio" name="Tip_Prod" id="Tip_Prod_N" value="" class="ml-2 mr-3">
                                                    <small>Balanceado</small>
                                                    <input type="radio" name="Tip_Prod" id="Tip_Prod_B" value="B" class="ml-2 mr-3">
                                                    <small>Larva</small>
                                                    <input type="radio" name="Tip_Prod" id="Tip_Prod_L" value="L" class="ml-2">
                                                    <small>Flete</small>
                                                    <input type="radio" name="Tip_Prod" id="Tip_Flet_F" value="F" class="ml-2">
                                                    <small>Insumos</small>
                                                    <input type="radio" name="Tip_Prod" id="Tip_Ins_I" value="I" class="ml-2">
                                                    <small>Otros. Desc.</small>
                                                    <input type="radio" name="Tip_Prod" id="Tip_Otr_D" value="D" class="ml-2">
                                                </div>
                                            </fieldset>
                                        </div>
                                    <?php } ?>
                                    <!-- Fin Negociacion -->
                                </div>
                            </form>
                            <div class="col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
                                <table id="items"></table>
                                <div id="itemsPager"></div>
                            </div>
                            <div class="col-md-7 col-xs-12">
                                <form id="reteFormTemp" action="javascript:" class="formDatos form-horizontal normal">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Datos de la Retención</legend>
                                        <input type="text" name="Ret_Cod" style="display: none;" id="Ret_Cod" />
                                        <input type="text" name="Ret_Xml" style="display: none;" />
                                        <input type="text" name="Aut_Cod" style="display: none;" id="Aut_Cod_Old" />
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs ">Numero:</label>
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
                                                    <span class="input-group-addon input-xs" title="Fecha de la Retención"><i class="glyphicon glyphicon-info-sign blue"></i></span>
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
                                                        <button type="button" onclick="$('#retDetaDialog').dialog('open')" class="btn btn-info" title="Ver Detalle Retención" tabindex="-1"><span class="glyphicon glyphicon-eye-open"></span></button>
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
                                    <input type="hidden" id="save_aut" name="save_aut" value="guardar">
                                </form>
                                <div class="">
                                    <div class="condensed" style="min-height: 100px; padding-bottom: 5px;">
                                        <table id="pagos"></table>
                                        <div id="pagosPager"></div>
                                    </div>
                                    <!--div>
                                        <button class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                        <button class="btn btn-sm btn-primary" id="save_aut" onclick="$('#save_aut').val('save_aut');/*aut_fac('save_aut'); */  $('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar y Autorizar</button>
                                    </div-->
                                </div>
                            </div>

                            <div class="col-md-5 col-xs-12  form-horizontal normal">
                                <div id="divReembolsos">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2"><input type="checkbox" id="Vet_Rem" name="Vet_Rem" class="check-big" onchange="setReembolsosGrid($(this));" />&nbsp;&nbsp;Reembolsos</legend>
                                        <div class="condensed" id="gridReembolsos">
                                            <table id="reembolsos"></table>
                                            <div id="reembolsosPager"></div>
                                        </div>
                                    </fieldset>
                                </div>
                                <form id="pagoFormTemp" action="javascript:" class="formDatos">

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
                                                        $row = array_map(function($v) { return mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1'); }, $row); // Convert each element to UTF-8
                                                        $selected = '';
                                                        if ($row[$Tpc_Sri] == 1) {
                                                            $selected = 'Selected';
                                                        }
                                                        // echo "<option value='" . $row['Tpc_Cod'] . "' " . $selected .  " >" . mb_convert_encoding($row['Tpc_Sri'], 'UTF-8', 'ISO-8859-1') . " - " . mb_convert_encoding($row['Tpc_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
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
                                <div id="trans_carga" style="display: none">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Transportista</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Transportista:</label>
                                            <div class="col-xs-5">
                                                <span id="Ext_Nom" name="Ext_Nom" type="text" class="form-control input-xs databind"></span>
                                            </div>
                                            <label class="col-xs-2 control-label label-xs">Vehiculo:</label>
                                            <div class="col-xs-3">
                                                <select id="Veh_Cod" name="Veh_Cod" defaultValue=1 class="form-control input-xs"></select>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                        <div class="row center-block">

                        </div>
                        <!-- BOTON DE GUARDADO -->
                        <div style="margin-left: 10px;" class="text-center">
                            <button class="btn btn-sm btn-primary" id="save_aut" onclick="$('#save_aut').val('guardar');$('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                            <?php //if ($Ses_Emp_Cod == 96  || $Ses_Emp_Cod == 361 || $Ses_Emp_Cod == 427   ) { 
                            ?>
                            <button class="btn btn-sm btn-success" id="save_aut" onclick="$('#save_aut').val('save_aut');$('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar y Autorizar</button>
                            <?php //} 
                            ?>
                        </div>
                    </div>
                    <div class="col-xs-12 Titulos2">
                        <hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
                    </div>
                </div>
            </div>
            <div id="documentoResult" class="form-horizontal normal" style="display: none;">
                <div class="row">
                    <div class="col-xs-6" id="resultContent">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Resultado De la Transacción</legend>
                            <div>
                                <h4 style="text-align: center; font-weight: 900;">El Documento se guardo con Éxito!</h4>
                                <p class="form-control-static resp" data-name="Tic_Des"></p>
                                <p class="resp"><span>&raquo;Fec:</span><span style="color:coral;" class="databind" data-name="Vet_Fec"></span></p>
                                <p class="resp"><span>&raquo;Num:</span><span style="color:teal;" class="databind" data-name="Vet_Num"></span></p>
                                <p class="resp"><span>&raquo;Cod:</span><span style="color: #CE0000;" class="databind" data-name="Vet_Cod"></span></p>
                                <p style="text-align: center;font-size: 28px;"><span>&raquo;Autoriz:</span><span class="databind" data-name="Aut_Cod_Est"></span></p>



                                <div style="padding-top: 15px; text-align: center;">
                                    <form name="frm_pdf" id="frm_pdf" action="../COMPONENTES/tesPdfFacturaElectronica_2.0.php" method="post" target="_blank">
                                        <button type="button" class="btn btn-sm btn-success" onclick="clearDocument();$('#documentoResult').moveComp('#factura').updateGridsSizes();"><i class="glyphicon glyphicon-file"></i> Nuevo Documento</button>


                                        <button type="button" class="btn btn-sm btn-success" name="Vet_Impr" id="Vet_Impr" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Documento</button>


                                        <button id="frm_pdf_btn" type="button" class="btn btn-sm btn-primary" onclick="this.form.submit()"><i class="glyphicon glyphicon-file"></i> <span>Pdf</span> </button>
                                        <input name="urlXml" id="urlXml" type="hidden" value="" alt="">
                                        <input name="op" id="op" type="hidden" value="I" alt="">
                                        <input name="logoUrl" id="logoUrl" type="hidden" value="<?php echo $_SESSION['Ses_Emp_Log']; ?>" alt="">
                                    </form>
                                </div>



                            </div>
                        </fieldset>
                    </div>
                    <div class="col-xs-6" id="copForm">
                        <div class="col-xs-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Documento</legend>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Documento:</label>
                                    <div class="col-xs-5"><span name="Tic_Des" type="text" class="form-control input-xs "></span></div>
                                    <label class="col-xs-1 control-label label-xs">Fecha:</label>
                                    <div class="col-xs-3"><span name="Vet_Fec" type="text" class="form-control input-xs "></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Numero:</label>
                                    <div class="col-xs-4"><span name="Vet_Num" type="text" class="form-control input-xs "></span></div>
                                    <label class="col-xs-2 control-label label-xs">Autorización:</label>
                                    <div class="col-xs-3"><span name="Vet_Aut" type="text" class="form-control input-xs "></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Cliente:</label>
                                    <div class="col-xs-9"><span name="cliente" type="text" class="form-control input-xs "></span></div>
                                </div>
                                <table id="copresult"></table>
                            </fieldset>
                        </div>
                        <div class="col-xs-6"></div>
                        <div class="col-xs-6">
                            <div class="separator"></div>
                            <div class="input-group input-group-xs frm_ticket_btn">
                                <select class="form-control" id='select_printer'></select>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-xs btn-primary" onclick="sendToPrinter();"><i class="glyphicon glyphicon-print"></i> Imprimir Ticket</button>
                                </span>
                            </div><!-- /input-group -->
                        </div>
                        <script>

                        </script>
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
                                    <label class="col-xs-3 control-label label-xs">Céd. Comp.:</label>
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
                                                label: 'Céd.Int.',
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
                                                label: 'Cédigo',
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
    <!-- Inicio del di�logo para buscar viajes -->
    <div id="viajesDialog" title="B&uacute;squeda de Viajes" style="display: none;">
        <form id="viajesForm" class="form-horizontal normal" action="javascript:$.Search('viajes')">
            <input type="text" name="Cli_Cod" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-xs-1 control-label label-xs">RUC:</label>
                    <div class="col-xs-3"><span name="Prs_Ced" type="text" class="form-control input-xs "></span></div>
                    <label class="col-xs-1 control-label label-xs">Cliente:</label>
                    <div class="col-xs-7"><span name="cliente" type="text" class="form-control input-xs "></span></div>
                </div>
                <div class="form-group">
                    <div class="col-xs-4">&nbsp;</div>
                    <div class="col-xs-8">
                        <div class="input-group input-group-xs">
                            <span class="input-group-addon"><input type="checkbox" name="byDates" onchange="$(this.form).find('.viaRange').prop('disabled',!$(this).is(':checked'));" class="check-big databind datatrigger" value="S" offval="N" default="N" /></span>
                            <span class="input-group-addon bold alert-info">Desde:</span>
                            <input name="Fec_Ini" type="text" id="txt_fec_ini" size="10" class="form-control input-xs viaRange" disabled="" required="" />
                            <span class="input-group-addon bold alert-info">Hasta:</span>
                            <input name="Fec_Fin" type="text" id="txt_fec_fin" size="10" class="form-control input-xs viaRange" disabled="" required="" />
                            <span class="input-group-btn">
                                <button type="submit" onclick="if(this.form.checkValidity())this.form.submit()" class="btn btn-success btn-xs" title="Buscar Documento" tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button>
                            </span>
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
    <!-- Inicio del di�logo para buscar viajes -->
    <div id="viajesSelectedDialog" title="Viajes Seleccionados"></div>

    <!-- Inicio del dilogo para buscar tickets -->
    <div id="ticketsDialog" title="B&uacute;squeda de Tickets" style="display: none;">
        <form id="ticketsForm" class="form-horizontal normal" action="javascript:$.Search('tickets')">
            <input type="text" name="Cli_Cod" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-xs-1 control-label label-xs">Estado:</label>
                    <div class="col-xs-3">
                        <select name="Tck_Tip" id="ticketsEstado" class="form-control input-xs" onchange="$('#ticketsGrid').trigger('reloadGrid');">
                            <option value="A" selected="selected">Activos</option>
                            <option value="F">Facturados</option>
                            <!--option value="">Todos</option-->
                        </select>
                    </div>
                    <label class="col-xs-1 control-label label-xs">RUC:</label>
                    <div class="col-xs-3"><span name="Prs_Ced" type="text" class="form-control input-xs "></span></div>
                    <label class="col-xs-1 control-label label-xs">Cliente:</label>
                    <div class="col-xs-3"><span name="cliente" type="text" class="form-control input-xs "></span></div>
                </div>
                <div class="form-group">
                    <div class="col-xs-8">
                        <div class="input-group input-group-xs">
                            <span class="input-group-addon"><input type="checkbox" name="byDates" onchange="$(this.form).find('.ticketRange').prop('disabled',!$(this).is(':checked'));" class="check-big databind datatrigger" value="S" offval="N" default="N" /></span>
                            <span class="input-group-addon bold alert-info">Desde:</span>
                            <input name="Fec_Ini" type="text" id="txt_ticket_fec_ini" size="10" class="form-control input-xs ticketRange" disabled="" required="" />
                            <span class="input-group-addon bold alert-info">Hasta:</span>
                            <input name="Fec_Fin" type="text" id="txt_ticket_fec_fin" size="10" class="form-control input-xs ticketRange" disabled="" required="" />
                            <span class="input-group-btn">
                                <button type="submit" onclick="if(this.form.checkValidity())this.form.submit()" class="btn btn-success btn-xs" title="Buscar Tickets" tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button>
                            </span>
                        </div>
                    </div>
                    <div class="col-xs-4 text-right">
                        <div class="form-group">
                            <div class="col-xs-offset-1 col-xs-11">
                                <button type="button" class="btn btn-primary btn-xs" onclick="seleccionarTicketsMarcados();" title="Seleccionar Tickets Marcados">
                                    <span class="glyphicon glyphicon-arrow-right"></span> Seleccionar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
    <!-- Inicio del dilogo para tickets seleccionados -->
    <div id="ticketsSelectedDialog" title="Tickets Seleccionados"></div>

    <!-- Inicio del di�logo para buscar clientes -->
    <div id="clieDialog" title="B&uacute;squeda de Cliente">
        <form class="form-horizontal normal"> </form>
    </div>
    <!-- Inicio del di�logo para buscar proformas -->
    <div id="prfDialog" title="B&uacute;squeda de Proformas"></div>
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

            $('#clieFormTemp,#viajesForm,#ticketsForm').setData($.extend(cliente, {
                op_opciones: 'c'
            }));

           /* $('#clieFormTemp,#viajesForm').setData($.extend(cliente, {
                op_opciones: 'c'
            }));*/
            if (cliente.op_ide === '04') $('#op_ide1').prop('checked', true).trigger('change');
            if (cliente.op_ide === '05') $('#op_ide2').prop('checked', true).trigger('change');
            if (cliente.op_ide === '06' || cliente.op_ide === '08') $('#op_ide3').prop('checked', true).trigger('change');
            if (cliente.op_ide === '07') $('#op_ide4').prop('checked', true).trigger('change');

            if ((cliente.op_ide === '04' || cliente.op_ide === '05') && cliente.Prs_Ced.substring(2, 3) * 1 !== 9) {
                $('input[name=Vet_Ide]').attr("disabled", true).trigger('change')
                $('input[name=Vet_Ide][value="4"]').attr("disabled", false).trigger('change')
                $('input[name=Vet_Ide][value="5"]').attr("disabled", false).trigger('change')
            } else {
                $('input[name=Vet_Ide]').attr("disabled", true).trigger('change')
            }
            $('#viajesSelectedGrid').setRows([]);
             $('#ticketsSelectedGrid').setRows([]);
            $('.viajes')[$.vv(cliente['Viajes']) && cliente['Viajes'].toNum() > 0 ? 'show' : 'hide']();
             $('.tickets')[$.vv(cliente['Tickets']) && cliente['Tickets'].toNum() > 0 ? 'show' : 'hide']();
            $('#clieDialog').dialog('close');

            //nuevo bloque añadido
            $("#aux_Cli_Cod").val(cliente.Cli_Cod);

            // Variables necesarias
            var Pec_Cod = $("#Pec_Cod").val();
            var Cli_Cod = cliente.Cli_Cod;

            if (Pec_Cod && Cli_Cod) {
                $.ajax({
                    url: "fac_alt_fac_ven_3.2.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        saldoCCxCC: true,
                        Pec_Cod: Pec_Cod,
                        Cli_Cod: Cli_Cod
                    },
                    beforeSend: function() {
                        $("#Cli_Sal").val("Calculando...");
                    },
                    success: function(response) {
                        if (response.success) {
                            // Asignar el saldo total al input, anteponiendo el signo $
                            $("#Cli_Sal").val("$ " + response.totalSaldo.toFixed(2));
                        } else {
                            $("#Cli_Sal").val("$ 0.00");
                        }
                    },
                    error: function(xhr, status, error) {
                        $("#Cli_Sal").val("$ 0.00");
                    }
                });
            } else {
                $("#Cli_Sal").val("$ 0.00");
            }
        }
    </script>

    <!-- Inicio del di�logo para registrar clientes -->
    <div id="clieCreateDialog" title="Registrar Cliente" style="display:none;">
        <form class="form-horizontal normal" id="clieCreateForm" action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaCliente(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos del Cliente</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
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
                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Razón Social:</span></label>
                    <div class="col-xs-9"><input name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
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
                <div class="form-group juridico" style="display: none;">
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
                    <div class="col-xs-5">
                        <input name="Prs_Cor" type="mail" class="form-control input-xs" required="" />
                        <small><strong style="color:#ffa500;font-size:12px">
                                <i class="glyphicon glyphicon-alert"></i>
                            </strong> Recuerde revisar el correo electrónico para garantizar que el comprobante sea entregado exitosamente.</small>
                    </div>
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
    <div id="codiDialog" title="B&uacute;squeda de Códigos Retención">
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

    <!-- INICIO DEL DIALOGO BUSCAR IMEI -->
    <div id="imeiDialog" title="B&uacute;squeda de IMEI">
        <form id="imeiForm" class="form-horizontal normal">
            <input type="text" name="Pro_Cod" class="Pro_Cod" style="display: none;" />
            <input type="text" name="index" class="index" style="display: none;" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Tipo:</label>
                    <div class="col-xs-10 radioset">
                        <input id="imei_tipo_todos" name="imei_tipo" type="radio" value="" /><label for="imei_tipo_todos">&nbsp;&nbsp;Todos&nbsp;&nbsp;</label>
                        <input id="imei_tipo_pendiente" name="imei_tipo" type="radio" value="P" checked="" /><label for="imei_tipo_pendiente">&nbsp;&nbsp;Pendiente&nbsp;&nbsp;</label>
                        <input id="imei_tipo_vendido" name="imei_tipo" type="radio" value="V" /><label for="imei_tipo_vendido">&nbsp;&nbsp;Vendido&nbsp;&nbsp;</label>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>

    <div id="changeReteDialog" title="Cambiar valor de Retención" style="display:none;">
        <form class="form-horizontal normal" id='form_change_rete' action="javascript:CambiarRetencion(this)">
            <input type="text" name="index" class="hidden">

            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Valor:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs"><span class="input-group-addon">$</span><input class="form-control input-xs " name="Ret_Valor" id="valor_ret" onkeyup="calcularPorcentaje(this)" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" placeholder="0.00" /></div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Porcentaje:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs"><input class="form-control input-xs nospin" name="Ret_Ren_Por" id="porcentaje_ret" type="number" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" min=1 max=2 step=any /><span class="input-group-addon">%</span></div>
                </div>
            </div>
            <div class="center">
                <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            </div>
        </form>
    </div>

    <input type="hidden" id="Emp_Cod" name="Emp_Cod" value="<?php echo  $Ses_Emp_Cod ?>">


    <script>
        $.createSearchDialog('codiDialog', [{
                label: 'Céd.Int.',
                name: 'Ren_Cod',
                key: true,
                width: 25,
                align: "center"
            },
            {
                label: 'Cédigo',
                name: 'Ren_Sri',
                width: 25,
                align: "center"
            },
            {
                label: 'Descripción',
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
            title: 'Búsqueda',
            options: []
        });

        $.createSearchDialog('imeiDialog', [{
                label: 'Cod.Int.',
                name: 'Ime_Cod',
                index: 'Ime_Cod',
                width: 80,
                key: true,
                hidden: true
            },
            {
                label: 'IMEI',
                name: 'Ime_Num',
                index: 'Ime_Num',
                width: 150,
                classes: 'highlightSearch'
            },
            {
                label: 'Tipo',
                name: 'Ime_Tip_Des',
                index: 'Ime_Tip_Des',
                width: 150,
                align: 'center',
                formatter: function(cellvalue) {
                    var colores = {
                        'Pendiente': '#FFA500',
                        'Vendido': '#28a745',
                        'Con Novedad': '#dc3545',
                        'Rechazado': '#6c757d'
                    };
                    var color = colores[cellvalue] || '#000';
                    return '<span style="color: ' + color + '; font-weight: bold;">' + (cellvalue || 'Pendiente') + '</span>';
                }
            },
            {
                label: 'Estado',
                name: 'Ime_Est',
                index: 'Ime_Est',
                width: 100,
                formatter: function(cellvalue) {
                    return cellvalue === 'A' ? 'Activo' : 'Inactivo';
                }
            },
            {
                label: '&nbsp;',
                name: 'act1',
                width: 60,
                align: 'right',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: selectImei,
                    icon: 'arrow-right',
                    title: 'Seleccionar IMEI',
                    type: 'success'
                }
            }
        ], null, 600, 500, null, {
            url: '?imeiAjax=true',
            datatype: 'json',
            mtype: 'GET',
            beforeRequest: function() {
                // Actualizar postData con Pro_Cod y tipo del formulario antes de cada petición
                var Pro_Cod = $('#imeiForm input[name="Pro_Cod"]').val() || '';
                var Ime_Tip = $('#imeiForm input[name="imei_tipo"]:checked').val() || '';
                $(this).jqGrid('setGridParam', {
                    postData: {
                        'Pro_Cod': Pro_Cod,
                        'Ime_Tip': Ime_Tip
                    }
                });
                return true;
            }
        }, {
            title: 'Seleccionar IMEI',
            options: []
        });

        // Función para ocultar campos de búsqueda automáticos
        function ocultarCamposBusquedaImei() {
            // Ocultar todos los fieldsets que NO contengan "Tipo:"
            $('#imeiForm fieldset').each(function() {
                public $fieldset = $(this);
                var tieneTipo = $fieldset.find('label:contains("Tipo:")').length > 0;
                var legend = $fieldset.find('legend').text().trim();
                // Si NO tiene "Tipo:" Y el legend NO es "Filtros", ocultarlo
                if (!tieneTipo && legend !== 'Filtros') {
                    $fieldset.css('display', 'none !important').hide();
                }
            });
            // Ocultar elementos de búsqueda directamente
            $('#imeiForm input[name="search"]').closest('.form-group, fieldset').css('display', 'none !important').hide();
            $('#imeiForm').find('label:contains("Filtrar Por:")').closest('.form-group, fieldset').css('display', 'none !important').hide();
            $('#imeiForm').find('label:contains("Búsqueda:")').closest('.form-group, fieldset').css('display', 'none !important').hide();
            $('#imeiForm button:contains("Buscar")').closest('.form-group, fieldset').css('display', 'none !important').hide();
            // Ocultar radiosets que contengan "Apellido/Nombre" o "Cédula/R.U.C"
            $('#imeiForm .radioset').each(function() {
                public $radioset = $(this);
                var html = $radioset.html();
                if (html.indexOf('Apellido/Nombre') !== -1 || html.indexOf('Cédula/R.U.C') !== -1) {
                    $radioset.closest('.form-group, fieldset').css('display', 'none !important').hide();
                }
            });
        }

        // Ocultar cuando se abra el diálogo
        $('#imeiDialog').on('dialogopen', function() {
            ocultarCamposBusquedaImei();
            setTimeout(ocultarCamposBusquedaImei, 50);
            setTimeout(ocultarCamposBusquedaImei, 150);
            setTimeout(ocultarCamposBusquedaImei, 300);
        });

        // También usar MutationObserver para detectar cuando se agreguen elementos
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function(mutations) {
                if ($('#imeiDialog').is(':visible')) {
                    ocultarCamposBusquedaImei();
                }
            });

            // Observar cambios en el formulario cuando el diálogo esté abierto
            $('#imeiDialog').on('dialogopen', function() {
                var targetNode = document.getElementById('imeiForm');
                if (targetNode) {
                    observer.observe(targetNode, {
                        childList: true,
                        subtree: true
                    });
                }
            });

            $('#imeiDialog').on('dialogclose', function() {
                observer.disconnect();
            });
        }

        // Agregar evento para recargar grid cuando cambie el filtro
        $('#imeiForm input[name="imei_tipo"]').on('change', function() {
            public $grid = $('#imeiGrid');
            var Pro_Cod = $('#imeiForm input[name="Pro_Cod"]').val() || '';
            var Ime_Tip = $(this).val() || '';
            // Actualizar postData antes de recargar
            $grid.jqGrid('setGridParam', {
                postData: {
                    'Pro_Cod': Pro_Cod,
                    'Ime_Tip': Ime_Tip
                },
                page: 1
            });
            // Recargar el grid
            $grid.trigger('reloadGrid', [{
                page: 1
            }]);
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
                hidden: true
            },
            {
                label: 'Descripción',
                name: 'Ite_Lar',
                width: 180,
                classes: 'highlightSearch'
            },
            {
                label: 'Detalle',
                name: 'Pro_Obs',
                width: 70,
                classes: 'highlightSearch'
            },
            {
                label: 'Des.Corta',
                name: 'Ite_Cor',
                width: 40,
                classes: 'highlightSearch'
            },
            {
                label: 'Marca',
                name: 'Mar_Des',
                width: 40
            },
            {
                label: 'Categ.',
                name: 'Cat_Des',
                width: 30,
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
            //PORCENTAJE IVA
            {
                label: 'IVA(%)',
                name: 'Iva_Por',
                width: 28,
                align: "center"
            }, //fin porcentaje IVA
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
                label: 'Stock',
                name: 'Stk_Can',
                width: 25,
                align: "center"
            },
            {
                label: 'PVP',
                name: 'Vet_Pru',
                width: 40,
                align: "right",
                formatter: 'currency'
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
        ], null, 900, null, null, {
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

    <!-- DIALOGO DETALLE RETENCION -->
    <div id="retDetaDialog" title="Retención">
        <div class="condensed-header">
            <table id="retencion"></table>
        </div>
    </div>
    <script>
        var opts = {
            height: 75,
            caption: 'Detalle Retención',
            sortable: true,
            sortname: 'Ren_Rete',
            sortorder: "desc",
            footerrow: true,
            colModel: [{
                    label: 'Céd.Int.',
                    name: 'Ren_Cod',
                    key: true,
                    width: 15,
                    align: "center",
                    hidden: true
                },
                {
                    label: 'Céd.Int.',
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
            caption: 'Detalle Retención <button id="btnRetPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right hidden" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button><button id="btnRetXml" onclick="window.open($(this).data(\'url\'));" class="btn btn-success btn-xs pull-right" style="margin-top: -2px; display:none; margin-right:2px; "><i class="glyphicon glyphicon-download-alt"></i> Descargar XML</button>'
        }), true);
        $('#reteresult').getFootRow(true);
        $('#retencion').createGrid($.extend(opts, {
            height: 219,
            width: 593,
            responsive: false,
            caption: 'Detalle Retención <button type="button" role="button" tabindex="-1" class="ui-button ui-widget ui-state-default ui-corner-all pull-right" title="Cerrar Ventana" onclick="$(\'#retDetaDialog\').dialog(\'close\')"><span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span></button>'
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
    <!--INICIO DEL DIALOGO BUSCAR compras-->
    <div id="comprasReembolsoDialog" title="B&uacute;squeda de Compras para Reembolsar"></div>
    <!--DIALOGO DE DESTINATARIOS-->
    <div id="destinoCreateDialog" title="Registrar/Seleccionar destinos" style="display:none;">
        <form class="form-horizontal normal" id="ExtinfoCreateForm" action="javascript: addInfoExtra();">
            <input name="Ext_Cod" id="Ext_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2"> Registrar/Seleccionar destinos </legend>
                <div class="form-group natural">
                    <label class="col-xs-2 control-label label-xs required">Destinatario:</label>
                    <div class="col-xs-10"><input name="Ext_Nom" id="Ext_Nom" type="text" class="form-control input-xs" required /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs ">Ciudad:</label>
                    <div class="col-xs-4">
                        <input name="Ext_Ciu" id="Ext_Ciu" type="text" class="form-control input-xs" />
                    </div>
                    <label class="col-xs-2 control-label label-xs ">Destino:</label>
                    <div class="col-xs-4">
                        <input name="Ext_Dest" id="Ext_Dest" type="text" class="form-control input-xs" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Ruta:</label>
                    <div class="col-xs-4">
                        <input name="Ext_Ruta" id="Ext_Ruta" type="text" class="form-control input-xs" />
                    </div>

                    <label class="col-xs-2 control-label label-xs ">Celular:</label>
                    <div class="col-xs-4">
                        <input name="Ext_Telf" id="Ext_Telf" type="text" class="form-control input-xs" />
                    </div>
                </div>


                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Fecha:</label>
                    <div class="col-xs-7">
                        <input name="Ext_Fec" id="Ext_Fec" type="datetime-local" class="form-control input-xs" />
                    </div>
                    <div class="col-xs-3">
                        <button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                    </div>
                </div>
                <div class="Titulos2">
                    <hr><b>NOTA:</b> Los campos marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
                </div>
            </fieldset>
        </form>

        <form class="form-horizontal normal" name="ExtinfoLoadForm" id="ExtinfoLoadForm" action="javascript:$('#lista_datos_extra').Search('#ExtinfoLoadForm','LoadExtRutAjax');">
            <div class="form-group">
                <label class="col-xs-2 control-label label-xs">Nombres:</label>
                <div class="col-xs-7">
                    <input name="searchload" id="searchload" type="text" class="form-control input-xs" />
                </div>
                <div class="col-xs-3">
                    <button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <table id="lista_datos_extra" style="width: 100%!important;"></table>
                    <div id="Datos_Ext"></div>
                </div>
            </div>
        </form>
    </div>

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

    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <script>
        $('#Pec_Cod').trigger('change');
        $.clearValidate();
        //Abrir dialogo
        $('#destinoCreateDialog').createDialog({
            icon: 'plus',
            width: 600,
            height: 450
        });
        //Ver negociaciones
        $('#negDialog').dialog({
            autoOpen: false
        });
        var containerNegoci = $("#containerNegoci");
        armargrid();
    </script>
    <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>

</BODY>

</HTML>