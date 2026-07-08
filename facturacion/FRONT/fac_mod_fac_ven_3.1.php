<?php

/**
 * @abstract Permite realizar la modificacion de docuemntos de venta
 * @author Erick Cordova
 * @version 1.0
 * Fecha de creacion  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


$tipo_compr = 6; //Tipo de comprobante de la retencion
$cod_banano = 338; //Codigo de Retencion del Banano

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_facturaVenta;

//$obBD_con1->debug(true);
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* Cambiar Tipo de Pago */
if (isset($saveChangePago)) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $obBD_con1->validaCierrePeriodo('ventas', 'Caj_Fec', 'Vet_Cod', $Caj_Fec, $Vet_Cod, $obBD_conexion, 'S');
    try {
        //SE ENVIA CREDITO
        if ($For_Cod * 1 == 2) {
            //ESTABA A CREDITO
            if (isset($Cpc_Cod) && !empty($Cpc_Cod) && !is_null($Cpc_Cod)) {
                $obBD_con1->operacionobBD('ccpp_cobrar.update', array('Cpc_Ven' => $Cpc_Ven, 'Cpc_Obs' => $Cpc_Obs, 'where' => array('Vet_Cod' => $Vet_Cod, 'Cpc_Cod' => $Cpc_Cod, 'Com_Cod' => $Com_Cod)), $obBD_conexion);
            }
            //ESTABA A CONTADO
            else {

                $codigoRetencion = $obBD_con1->getArrayConsulta(995, array('Vet_Cod' => $Vet_Cod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
                if (!empty($codigoRetencion) && !is_null($codigoRetencion)) {
                    $comprobanteVenta = $obBD_con1->getRowConsulta(996, array('Com_Cod' => $Com_Cod), $obBD_conexion);

                    //Crear Comprobante para la retencion
                    $Tia_Asi_Ret = $obBD_con1->getRowConsulta(80, 17, $obBD_conexion);
                    $Com_Num_Ret = $obBD_con1->codigoComprAuto($Tia_Asi_Ret['Tia_Cod'], $comprobanteVenta['Pec_Cod'], $comprobanteVenta['Com_Fec'], $obBD_conexion);
                    $Com_Con_Ret = 'RETENCION DE VENTA ' . $Vet_Num;
                    $campo = 'Cli_Cod';

                    $obBD_con1->operacionobBD(70, $comprobanteVenta['Pec_Cod'] . '*' . $comprobanteVenta['Cli_Cod'] . '*' . $Com_Num_Ret . '*' . $codigoRetencion[0]['Ret_Fec'] . '*' . trim($Com_Con_Ret) . '*' . $Tia_Asi_Ret['Tia_Cod'] . '*' . '0' . '*' . 'RETENCION' . '*' . $campo, $obBD_conexion);
                    $Com_Cod_Ret = $obBD_con1->insercionid($obBD_conexion->conexion);

                    //update los asientos de la retencion con el nuevo comprobante creado
                    foreach ($codigoRetencion as $key) {
                        $obBD_con1->operacionobBD(997, array('Com_Cod' => $Com_Cod, 'Com_Ret' => $Com_Cod_Ret, 'Pld_Cod' => $key['Pld_Cod']), $obBD_conexion);
                    }

                    //Obtener el total de la retencion 
                    $total = $obBD_con1->getRowConsulta(998, array('Com_Cod' => $Com_Cod_Ret), $obBD_conexion);
                    //crear un asiento debe en el comprobante de retencion con el valor de la retencion y la nueva cuenta que se esta enviando de credito 
                    $obBD_con1->operacionobBD(999, array('Com_Cod' => $Com_Cod_Ret, 'Asi_Val' => $total['totalRetencion'], 'Pld_Cod' => $Pag_Pld, 'Vet_Num' => $Vet_Num), $obBD_conexion);
                    $Asi_Cod_Ret = $obBD_con1->insercionid($obBD_conexion);

                    //actualizar el valor del asiento del comprobante de compra en el debe mas el valor total de la retencion 
                    $obBD_con1->operacionobBD(10000, array('Com_Cod' => $Com_Cod, 'Asi_Val' => $total['totalRetencion'], 'Pld_Cod' => $Pag_Pld), $obBD_conexion);
                    //actualiza el valor del comprobante de retencion
                    $obBD_con1->operacionobBD(10011, array('Com_Cod' => $Com_Cod_Ret, 'Com_Val' => $total['totalRetencion']), $obBD_conexion);

                    $obBD_con1->operacionobBD(10022, array('Cpc_Ven' => $Cpc_Ven, 'Cpc_Obs' => $Cpc_Obs, 'Vet_Cod' => $Vet_Cod, 'Com_Cod' => $Com_Cod), $obBD_conexion);
                    $Cpc_Cod = $obBD_con1->insercionid($obBD_conexion);
                    //Crear detalle 
                    $obBD_con1->operacionobBD(255, array('Com_Cod' => $Com_Cod_Ret, 'Pag_Cod' => 50, 'Cpc_Fec' => $codigoRetencion['Ret_Fec'], 'Cpc_Val' => $total['totalRetencion'], 'Cpc_Obs' => "ABONO POR RETENCION", 'Cpc_Cod' => $Cpc_Cod, 'Asi_Cod' => $Asi_Cod_Ret), $obBD_conexion);
                } else {
                    $obBD_con1->operacionobBD('ccpp_cobrar.insert', array('Cpc_Ven' => $Cpc_Ven, 'Cpc_Obs' => $Cpc_Obs, 'Vet_Cod' => $Vet_Cod, 'Com_Cod' => $Com_Cod), $obBD_conexion);
                }
            }
            //DE CREDITO A CONTADO
        } else if (isset($Cpc_Cod) && !empty($Cpc_Cod) && !is_null($Cpc_Cod)) {

            $onlyRetencion = false;
            $Pagos1 = $obBD_con1->getRowConsulta(57, $Cpc_Cod . '*' . 'A', $obBD_conexion);
            $retencionValue = $obBD_con1->getRowConsulta(577, $Cpc_Cod . '*' . 'A', $obBD_conexion);

            if (round($Pagos1['total'] * 1, 2) == round($retencionValue['total'] * 1, 2)) {
                $onlyRetencion = true;
            }
            if ($onlyRetencion) {

                $compRetencion = $obBD_con1->getRowConsulta(990, $Cpc_Cod, $obBD_conexion); //valor y codigoComp de la retencion
                $obBD_con1->operacionobBD(991, array('Com_Ret' => $compRetencion['Com_Cod']), $obBD_conexion); //elimino el asiento del debe del comprobante de retencion
                $obBD_con1->operacionobBD(9911, array('Cpc_Val' => $compRetencion['Cpc_Val'], 'Com_Cod' => $Com_Cod, 'Pld_Cod' => $Pag_Pld), $obBD_conexion); //actualizo el valor del haber del comprobante de retencion
                $obBD_con1->operacionobBD(992, array('Com_Cod' => $Com_Cod, 'Com_Ret' => $compRetencion['Com_Cod']), $obBD_conexion); //cambio de asientos del comprobante de retencion al de compra
                $obBD_con1->operacionobBD(993, array('Cpc_Cod' => $Cpc_Cod), $obBD_conexion); //elimina detalle de cuentas por pagar
                $obBD_con1->operacionobBD(994, array('Com_Ret' => $compRetencion['Com_Cod']), $obBD_conexion); //elimina el comprobante de la retencion
                $obBD_con1->operacionobBD('ccpp_cobrar.deleteWhere', array('where' => array('Vet_Cod' => $Vet_Cod, 'Cpc_Cod' => $Cpc_Cod, 'Com_Cod' => $Com_Cod)), $obBD_conexion);
            } else {
                $obBD_con1->operacionobBD('ccpp_cobrar.deleteWhere', array('where' => array('Vet_Cod' => $Vet_Cod, 'Cpc_Cod' => $Cpc_Cod, 'Com_Cod' => $Com_Cod)), $obBD_conexion);
            }
        }

        //$obBD_con1->operacionobBD('ventas.update', array('Tri_Cod'=>$Tri_Cod,'Con_Cod'=>isset($Con_Cod)&&!empty($Con_Cod)?$Con_Cod:null, 'where'=>array('Vet_Cod'=>$Vet_Cod)), $obBD_conexion);
        $obBD_con1->operacionobBD('asientos.update', array('Pld_Cod' => $Pag_Pld, 'where' => array('Com_Cod' => $Com_Cod, 'Pld_Cod' => $Pld_Cod_Pag, 'Asi_Deh' => 'H')), $obBD_conexion);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }

    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $resp = array('success' => true);
    } else {
        $resp = array('success' => false, 'message' => 'No se pudo realizar la transacción!', 'error' => $obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($resp);
}


/* ver si exite un cliente */
if (isset($searchCliente)) {
    $responce = $obBD_con1->getRowConsulta(177, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con1->getRowConsulta(188, $responce['Prs_Cod'] . '*' . $Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Cli_Cod'])) ? $responce['existe'] = true : $responce['existe'] = false;
    $obBD_con1->echoJson($responce);
}

if (isset($getDateServ)) {
    $resp['hoy'] = date("Y-m-d");
    $obBD_con1->echoJson($resp);
}

//Secci�n para listar los clientes registrados en la empresa
//if(isset($clieAjax)){ $obBD_con1->getPageGridJson(1,  $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion,$page, $rows); }
if (isset($clieAjax)) {
    $response = $obBD_con1->getPageGrid(1, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
    $Sel = $obBD_con1->select()->from('viaje', array('Viajes' => $obBD_con1->expr('COUNT(Via_Cod)')));
    foreach ($response['rows'] as &$v) {
        $Sel->unsetWhere()->where("Cli_Cod=? AND Via_Est='A' AND Vet_Cod IS NULL", $v['Cli_Cod']);
        $via = $obBD_con1->getRowConsulta(null, $Sel, $obBD_conexion);
        $v['Viajes'] = $via['Viajes'];
    }
    unset($v);
    $obBD_con1->echoJson($response);
}

//Secci�n para obtener el n�mero de secuencia
if (isset($numeroSec)) {
    $response = $obBD_con1->getRowConsulta(9, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod . '*' . $Tic_Cod . '*' . $Aut_Cod, $obBD_conexion);
    if (isset($Aut_Sri)) $response['Aut_Sri'] = $Aut_Sri;
    $siguiente = $obBD_con1->getRowConsulta(10, $response['Aut_Ini'] . '*' . $response['Aut_Fin'] . '*' . $response['Aut_Sri'] . '*' . $Tic_Cod . '*' . $Ses_Suc_Cod . '*' . $Pun_Sri, $obBD_conexion);
    $response['Veh_Cod']= $obBD_con1->getArrayConsulta(179, array('Aut_Cod'=>$Aut_Cod), $obBD_conexion);     
    $response['Vet_Num'] = $siguiente['siguiente'];
    $response['contador'] = $siguiente['contador'];
    echo json_encode($response);
    exit();
}


if (isset($existeNumdoc)) {
    $rs_numdocumento = $obBD_con1->getRowConsulta(11, $Ses_Suc_Cod . '*' . $Aut_Sri . '*' . $Vet_Num . '*' . (isset($Vet_Cod) ? $Vet_Cod : '') . '*' . $Pun_Sri, $obBD_conexion);
    if ($rs_numdocumento['total'] * 1 > 0) {
        $response['existe'] = true;
    } else {
        $response['existe'] = false;
    }
    echo json_encode($response);
    exit();
}

if (isset($buscarCuentas)) {
    $contado1 = $obBD_con1->getArrayConsulta(19, $Pla_Cod . '*' . $Ses_Emp_Cod . '*' . $Pag_Cod, $obBD_conexion);
    $contado2 = $obBD_con1->getArrayConsulta(20, $Pla_Cod, $obBD_conexion);
    $contado = array_merge($contado2, $contado1);
    $response['Contado'] = $contado;
    $credito = $obBD_con1->getArrayConsulta(90, $Pla_Cod . '*' . '2', $obBD_conexion);
    $response['Credito'] = $credito;
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

/* ver si exite un proveedor */
if (isset($provAjax2)) {
    $responce['rows'] = $obBD_con1->getArrayConsulta(2, $Prs_Ced . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $responce['total'] = count($responce['rows']);
    $obBD_con1->echoJson($responce);
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
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', 'error' => $obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}
/* Consulta datos del documento si existe */
if (isset($ajaxCopNum)) {
    $resp = array('success' => true);
    if (!empty($Tic_Cod) && !empty($Cop_Num)) {
        $row_rs_CodDoc = $obBD_con1->getRowConsulta(7, $Prv_Cod . '*' . $Tic_Cod . '*' . $Cop_Num . '*' . $Cop_Cod, $obBD_conexion);
        if ($row_rs_CodDoc['Cop_Cod'] != "")
            $resp = array('success' => false, 'message' => 'El documento ya Existe en el Sistema!');
    } else $resp['success'] = '';
    $obBD_con1->echoJson($resp);
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

$ivas = $obBD_con1->getArrayConsulta(16, "", $obBD_conexion);      //Secci�n para obtener los ivas de la tabla iva


/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(12, $Ses_Emp_Cod, $obBD_conexion);
$vendedor = $obBD_con1->getRowConsulta(85, $Ses_Suc_Cod . '*' . $Ses_Prs_Cod, $obBD_conexion);

if (isset($viajesAjax)) {
    $page = $obBD_con1->getPageGrid('viaje', array_merge($_GET, array('where' => 'viaje.Vet_Cod IS NULL' . (isset($Vet_Cod) && !empty($Vet_Cod) ? " OR viaje.Vet_Cod=$Vet_Cod" : ''), 'setWhere' => array('isActive'))), $obBD_conexion, true);
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
/* Consulta del tipo de productos */
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

/* busqueda de documentos */
if (isset($searchDocument)) {
    $data = $_GET;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getPageGrid(34, $data, $obBD_conexion);
    if ($responce['total'] > 0) {
        foreach ($responce['rows'] as &$row) {
            $row['Cpc_Edit'] = 'S';
            $row['Cpc_Min'] = 0;
            if (!empty($row['Cpc_Cod'])) {
                $Pagos1 = $obBD_con1->getRowConsulta(57, $row['Cpc_Cod'] . '*' . 'A', $obBD_conexion);
                if ($Pagos1['total'] * 1 > 0) {

                    $row['onlyRetencion'] = false;
                    //COMPROBAR SI SOLO TIENE EL PAGO DE RETENCION
                    $retencionValue = $obBD_con1->getRowConsulta(577, $row['Cpc_Cod'] . '*' . 'A', $obBD_conexion);
                    if (round($Pagos1['total'] * 1, 2) == round($retencionValue['total'] * 1, 2)) {
                        $row['onlyRetencion'] = true;
                        $row['Cpc_Det'] = 'N';
                        $row['Cpc_Edit'] = 'S'; //tiene pagos activos
                    } else {
                        $row['Cpc_Det'] = 'S';
                        $row['Cpc_Edit'] = 'N'; //tiene pagos activos
                    }
                    $Pagos1 = $obBD_con1->getRowConsulta(57, $row['Cpc_Cod'] . '*' . 'A' . '*' . 'SUM', $obBD_conexion);
                    $row['Cpc_Min'] = round($Pagos1['total'] * 1, 2);
                }
                //$Pagos2=$obBD_con1->getRowConsulta(57, $row['Cpc_Cod'].'*'.'A', $obBD_conexion);
                //if($Pagos2['total']*1>0) $row['Cpc_Edit']='N'; //tiene algun pago vinculado
            }
            if ($configs['Cof_Con'] == 'S' && !empty($row['Com_Cod'])) {
                $cuentas = $obBD_con1->getRowConsulta(39, $row['Com_Cod'], $obBD_conexion);
                if (empty($cuentas) or is_null($cuentas)) {
                    $cuentas = $obBD_con1->getRowConsulta(399, $row['Com_Cod'], $obBD_conexion);
                }
                $row['Pld_Cod_Pag'] = $cuentas['Pld_Cod'];
                $otras_comp = $obBD_con1->getRowConsulta(65, $row['Com_Cod'], $obBD_conexion);
                if ($otras_comp['total'] * 1 > 1) $row['Com_Edit'] = 'N';
            }
        }
        unset($row);
    }
    $obBD_con1->echoJson($responce);
}

/* reviso las cuentas pago */
if (isset($cuentasPago)) {
    $responce['cuentas'] = '';
    $Pec_Cod = $obBD_con1->getRowConsulta(1000, $Ses_Emp_Cod . '*' . $Vet_Fec, $obBD_conexion);
    if ($For_Cod * 1 == 2)
        $cuentas = $obBD_con1->getArrayConsulta(1002, $Pec_Cod['Pla_Cod'] . '*' . $For_Cod, $obBD_conexion);
    if ($For_Cod * 1 == 1)
        $cuentas = $obBD_con1->getArrayConsulta(1001, $Pec_Cod['Pla_Cod'] . '*' . $For_Cod, $obBD_conexion);

    $responce['total'] = count($cuentas);
    foreach ($cuentas as $row)
        $responce['cuentas'] = $responce['cuentas'] . '<option value="' . $row['Pld_Cod'] . '" data-extra="' . (isset($row['extra']) ? $row['extra'] : '') . '" ' . ($row['Pld_Cod'] == $Pld_Cod ? 'selected="selected"' : '') . '>' . $row['Pld_Des'] . '</option>';
    if ($responce['total'] > 1)
        $responce['cuentas'] = "<option value=''>Seleccione...</option>" . $responce['cuentas'];
    //if(!empty($Pld_Cod)) $responce['Pld_Cod']=$Pld_Cod;
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
        $responce['options'] = $responce['options'] . '<option value="' . $row['Iva_Cod'] . '" data-ivapor="' . $row['Iva_Por'] . '" ' . (empty($Iva_Cod) ? ($responce['iva_activo']['Iva_Por'] == $row['Iva_Por'] ? 'selected="selected"' : '') : ($row['Iva_Cod'] == $Iva_Cod ? 'selected="selected"' : '')) . '>' . $row['Iva_Por'] . ' %</option>';
    if ($configs['Cof_Con'] == 'S') {
        $responce['cuentas'] = '';
        $Pec_Cod = $obBD_con1->getRowConsulta(9, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
        $iva_pag = $obBD_con1->getArrayConsulta(20, $Pec_Cod['Pla_Cod'], $obBD_conexion);
        foreach ($iva_pag as $row)
            $responce['cuentas'] = $responce['cuentas'] . '<option value="' . $row['Pld_Cod'] . '" ' . (isset($Pld_Cod) && $row['Pld_Cod'] == $Pld_Cod ? 'selected="selected"' : '') . ' >' . $row['Pld_Des'] . '</option>';
    }
    $responce['success'] = true;
    $obBD_con1->echoJson($responce);
}


/* Consulta el detalle del documento */
if (isset($docDetalle)) {
    $resp['Vet_items'] = $obBD_con1->getArrayConsulta(93, $Vet_Cod, $obBD_conexion);
    $obBD_con1->echoJson($resp);
}

//Secci�n para extraer el Pun_Cod y Vnd_Cod del usuario sobre la tabla vendedor
$rs_Punto = $obBD_con1->getRowConsulta(7, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);

if (isset($getDataPunto)) {
    $resp = $obBD_con1->getRowConsulta(7, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
    $obBD_con1->echoJson($resp);
}

/* Valida numero de retenci�n */
if (isset($validaRetNum)) {
    $autoriz = $obBD_con1->getRowConsulta(48, $vendedor['Pun_Cod'] . '*' . $tipo_compr, $obBD_conexion); //Consulta las autorizaciones de las retenciones
    //$rs_infEmpFacElec = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
    $electronica = ($autoriz['Aut_Tem'] == 'E'); //($rs_infEmpFacElec['Cof_Gce']=='S');
    $row_max_codig = $obBD_con1->getRowConsulta(51, $Ses_Suc_Cod . '*' . $autoriz['Aut_Sri'] . '*' . $autoriz['Aut_Ini'] . '*' . $autoriz['Aut_Fin'] . '*' . $autoriz['Tic_Cod'], $obBD_conexion); //Consulta el maximo numero de retenciones en base a la autorizacion
    $Ret_Id_Man = ($row_max_codig['next']);
    if (empty($vendedor['Pun_Cod']) || empty($autoriz['Aut_Cod'])) $resp = array('success' => false, 'message' => "No tiene autorizacion para generar Retenciones!", 'Ret_Num_Old' => 0, 'Ret_Num' => '');
    else {
        $resp = array_merge(array('success' => true, 'Ret_Num' => $Ret_Id_Man, 'Ret_Num_Old' => $Ret_Num, 'Ret_Cod' => $Ret_Cod), $autoriz);
        if (!empty($Ret_Num)) {
            $num_existe_gencod = $obBD_con1->getRowConsulta(50, $Ses_Suc_Cod . '*' . $autoriz['Aut_Sri'] . '*' . $Ret_Num . '*' . $Ret_Cod, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI
            if ($num_existe_gencod['total'] * 1 > 0) {
                $resp['success'] = false;
                $resp['message'] = "La Retención Número $Ret_Num ya Existe en el Sistema!";
            }
        } else $resp['success'] = false;
        $resp['Aut_Sri'] = ($electronica ? 'Electronica' : $autoriz['Aut_Sri']);
    }
    $obBD_con1->echoJson($resp);
}

if (isset($autorizaAjax)) {
    $obBD_con1->getPageGridJson(100, $rs_Punto['Pun_Cod'] . '*' . $Tic_Cod, $obBD_conexion, $page, $rows);
}

if (isset($cargarDocumentos)) {
    if ($Aut_Cod == '') $Aut_Cod = 0;
    if ($Tic_Cod == '') $Tic_Cod = 0;
    $array_documentos = $obBD_con1->getArrayConsulta(8, $rs_Punto['Pun_Cod'] . '*' . $Aut_Cod, $obBD_conexion);
    if ($Tic_Cod > 0) {
        $array_doc = $obBD_con1->getArrayConsulta(101, $rs_Punto['Pun_Cod'] . '*' . $Aut_Cod . '*' . $Tic_Cod, $obBD_conexion);
        $array_documentos = array_merge($array_documentos, $array_doc);
    }
    echo json_encode($array_documentos);
    exit();
}




if (isset($cargarDoc)) {
    $responce = $obBD_con1->getRowConsulta(91, $vet_cod, $obBD_conexion);
    $responce['items'] = $obBD_con1->getArrayConsulta(93, $vet_cod, $obBD_conexion);
    $responce['Bod_Cod'] = $obBD_con1->getRowConsulta(154, $vet_cod, $obBD_conexion);
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
        $prod = $obBD_con1->getRowConsulta(13, '' . '*' . $Ses_Emp_Cod . '*' . '' . "* AND producto.Pro_Cod=$r[Pro_Cod]", $obBD_conexion);
        if ($configs['Cof_Con'] == 'S' && !empty($Pla_Cod)) {
            $cuenta = $obBD_con1->getRowConsulta(15, $Pla_Cod . '*' . $r['Pro_Cod'] . '*' . 'V', $obBD_conexion);
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
    $responce['pagos'] = $obBD_con1->getArrayConsulta(92, $vet_cod, $obBD_conexion);
    if ($Aut_Cod == '') $Aut_Cod = 0;
    if ($Tic_Cod == '') $Tic_Cod = 0;
    $array_documentos = $obBD_con1->getArrayConsulta(8, $rs_Punto['Pun_Cod'] . '*' . $Aut_Cod, $obBD_conexion);
    if ($Tic_Cod > 0) {
        $array_doc = $obBD_con1->getArrayConsulta(101, $rs_Punto['Pun_Cod'] . '*' . $Aut_Cod . '*' . $Tic_Cod, $obBD_conexion);
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
    $obBD_con1->echoJson($responce);
}

if (isset($cargarReportes)) {
    try {
        $response['reportes'] = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        $response['success'] = true;
    } catch (Exception $ex) {
        $response['message'] = $ex->getMessage();
    }
    $obBD_con1->echoJson($response);
}



/* Secci�n para realizar el guardado */
if (isset($saveDocument)) {
    $obBD_con1->validaCierrePeriodo('ventas', 'Caj_Fec', 'Vet_Cod', $Caj_Fec, $editDoc['Vet_Cod'], $obBD_conexion, 'S');
    if (preg_match('/^9{8,}/', $Prs_Ced)  &&    $t_rubros > 50) {
        if($Tic_Sri != 0){
            $responce['message'] = "La normativa del SRI indica que si el cliente supera un monto de 50 USD, no debe ser considerado como consumidor final.";
        }
    }
    /* Creacion de Objetos de Conexiones para Proceso de Guardado de Venta*/
    $obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_facturaVenta;
    /*Habilita Debuger de SQLs en Proceso de Guardado de Venta*/
    //$obBD_con1->debug(true);
    //$obBD_conIns->debug(true);
    /*Inicio de Transaccion*/
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
    /*Verifica usuario tenga Permisos de Vendedor*/
    if (empty($vendedor['Vnd_Cod'])) {
        $responce['message'] = "No tiene permisos de Vendedor!";
    }
    $Vnd_Cod = $vendedor['Vnd_Cod'];
    $Vet_Cod = $editDoc['Vet_Cod'];
    $Vet_Sri = '';
    if (is_string($items)) $items = json_decode(stripslashes($items), true);
    try {
        //Secci�n para verificar si la caja ya fue aperturada
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
        if ($num_existe_gencod['total'] * 1 > 0 && $Vet_Num != $Vet_Num_Ant) {
            $responce['message'] = "El doc. $Tic_Txt No. $Vet_Num ya existe!";
        }
        if ($Aut_Tem == 'E' && $Vet_Num !== 0 && $input_autorizacion == '') {
            $Vet_Aut = 'N';
            require_once('../LOGICA/fac_log_electronica.php');
            $obBD_elect =  new Class_Log_Datos_Factura_Elect();
            //$claveAcceso=$obBD_con1->getDocClaveAcceso($Ses_Emp_Cod, $Ses_Suc_Cod, $Tic_Sri, $Aut_Cod, $Caj_Fec, $Vet_Num, $obBD_conexion);
            $claveAcceso = $obBD_elect->getClaveAcceso($Aut_Cod, $Caj_Fec, $Vet_Num, $obBD_conexion);
            if (empty($claveAcceso)) $responce['message'] = "Error al generar <u>Clave de Acceso</u> del <i>Comprobante Electrónico</i>!";
            //if(!$obBD_con1->createUsuCliente($Ses_Emp_Cod, $Ses_Suc_Cod, $Prs_Cod, $Prs_Ced, $obBD_conexion)) $responce['message']='Error al crear usuario de <u>Comprobantes Electr�nicos</u>!';
        }
        if (!empty($input_autorizacion)) {
            $Vet_Aut = 'S';
            $claveAcceso = $input_autorizacion;
            $Vet_Sri = $input_autorizacion;
        }

        $rise = ($Tic_Sri * 1 == 2 || $Tic_Sri * 1 == 9); // rise, nota de venta
        if ($rise) $iva_cero = $obBD_con1->getRowConsulta(68, '0', $obBD_conexion);
        /* cierro en caso de error */
        if (!empty($responce['message'])) {
            echo json_encode($responce);
            exit();
        }

        if (isset($rets)) {
            if (empty($Ret_Fec) && count($rets) > 0) {
                $Ret_Fec = $Caj_Fec;
            }
        } else {
            $Ret_Fec = NULL;
        }

        $cabeceraVenta = array('Tic_Cod' => $Tic_Cod, 'Cli_Cod' => $Cli_Cod, 'Ciu_Cod' => $Ciu_Cod, 'Caj_Cod' => $Caj_Cod, 'Vnd_Cod' => $rs_Punto['Vnd_Cod'], 'Vet_Num' => $Vet_Num, 'Vet_Obs' => $Vet_Obs, 'Aut_Cod' => $Aut_Cod, 'Vet_Des' => $Vet_Des, 'Vet_Hor' => $hora, 'Vet_Xml' => (isset($claveAcceso) ? $claveAcceso : ''), 'Vet_Aut' => (isset($Vet_Aut) ? $Vet_Aut : ''), 'Ret_Num' => $Ret_Num, 'Ret_Fec' => $Ret_Fec, 'Ret_Aut' => $Ret_Aut_Sri, 'Tpc_Cod' => $Tpc_Cod, 'Vet_Cod' => $Vet_Cod, 'Vet_Sri' => $Vet_Sri, 'Veh_Cod' => $Veh_Cod);
        /* Cabecera de la factura de venta */
        $obBD_conIns->operacionobBD(141, $cabeceraVenta, $obBD_conexionIns);

        //Eliminando Cardex de Venta a Modificar
        //if($Tic_Sri*1!=0){
        $horaKardexAnterior = date("H:i:s");

        $row_kard_old = $obBD_con1->getArrayConsulta(43, $Vet_Cod, $obBD_conexion);
        if (is_array($row_kard_old) && !empty($row_kard_old)) {
            $obBD_Stock =  new Class_Log_Datos_facturaVenta;
            $obBD_conexionStock = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
            $obBD_Stock->inicio_transaccion($obBD_conexionStock->conexion);
            foreach ($row_kard_old as $row) {
                $horaKardexAnterior = $row['Kar_Hor'];
                $row['IoE'] = 'E';
                $row['Kar_Sal'] = $row['Kar_Sal'] * -1;
                $row['Kar_Pre'] = $row['kar_Pre'] * 1;
                $row['Kar_Ime'] = $row['Kar_Ime'] * -1;
                $obBD_Stock->updateStockProd($Ses_Suc_Cod, $row, false, $obBD_conexion, $obBD_conexionStock); //revierte el stock
            }
            $obBD_Stock->fin_transaccion_nomsn($obBD_conexionStock->conexion);
            if ($obBD_Stock->Error != 0) throw new Exception('Error al limpiar los antiguos valores del <u>KARDEX</u>!');
            $obBD_conIns->operacionobBD(44, $Vet_Cod, $obBD_conexionIns); // limpia el kardex
        }
        //}
        //Eliminando items de Documento
        $obBD_conIns->operacionobBD('viaje.update', array('Vet_Cod' => null, 'Vet_Ite' => null, 'where' => array('Vet_Cod' => $Vet_Cod)), $obBD_conexionIns);
        $obBD_conIns->operacionobBD(97, $Vet_Cod, $obBD_conexionIns);
        $cod_pro_unique = array();
        /* Inserta datos en el detalle de la venta */

        $kardex = array('IoE' => 'E', 'Kar_Fec' => $Caj_Fec, 'Kar_Hor' => $horaKardexAnterior, 'Vet_Cod' => $Vet_Cod, 'Vnd_Cod' => $Vnd_Cod);
        $array_kardex = array();
        $s_add = true;
        foreach ($items as $i => $item) {
            $item['Vet_Cod'] = $Vet_Cod;
            $item['Vet_Ite'] = $i + 1;
            if ($rise) $item['Iva_Cod'] = $iva_cero['Iva_Cod'];
            /* Item Documento */

            if ($item['Ret_Mod'] * 1 > 0) {
                //              verificar si existe retencion
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

            $obBD_conIns->operacionobBD(86, $item, $obBD_conexionIns);
            if (isset($item['Viajes']) && is_array($item['Viajes'])) // Nuevo de viajes transporte
                foreach ($item['Viajes'] as $Via_Cod) {
                    $obBD_conIns->operacionobBD('viaje.update', array('Vet_Cod' => $Vet_Cod, 'Vet_Ite' => $item['Vet_Ite'], 'where' => array('Via_Cod' => $Via_Cod)), $obBD_conexionIns);
                }

            /* Control de Inventarios */
            if (($Tic_Sri * 1 != 0 || (isset($configs['Cof_Stk']) && $configs['Cof_Stk'] == 'S')) && $item['Adq_Cor'] == 'B') {
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
                        $kardexIE = array_merge($kardex, array(
                            'Kar_Int' => $i + 1,
                            'Iva_Cod' => $item['Iva_Cod'],
                            'Pro_Cod' => $item['Pro_Cod'],
                            'Kar_Sal' => (1) * $item['Vet_Can'], //$obBD_conIns->CantidadStock($item['Pro_Cod'],$items),
                            'Kar_Pre' => $item['Vet_Pru'] * 1,
                            'Kar_Ime' => (1) * $item['Vet_Imp'],
                            //'Kar_Rep'=>(in_array($item['Pro_Cod'],$cod_pro_unique)?true:false),
                            //'Kar_Max'=>$obBD_conIns->CantidadStock($item['Pro_Cod'],$items),
                        ));
                        array_push($array_kardex, $kardexIE);
                    }
                }
            }
        }
        foreach ($array_kardex as $k) {
            //array_push($cod_pro_unique, $item['Pro_Cod']);
            $obBD_conIns->updateStockProd($Ses_Suc_Cod, $k, true, $obBD_conexion, $obBD_conexionIns, $Bod_Cod);
        }


        //BORRANDO PAGOS VENTA//
        $obBD_conIns->operacionobBD(95, $Vet_Cod, $obBD_conexionIns);
        //ANULAR CHEQUE DE LOS PAGOS 
        $obBD_conIns->operacionobBD(147, $Vet_Cod, $obBD_conexionIns);
        //ELIMINAR LA RELACION DE LA VENTA CON EL CHEQUE
        $obBD_conIns->operacionobBD(148, $Vet_Cod, $obBD_conexionIns);

        $tipo_de_pago = 0;

        /* REGISTRO PAGO VENTA */
        foreach ($pagos as $i => $pag) {
            $tipo_de_pago = $pag['Forma_Cod'];
            $pag['Vet_Num'] = $Vet_Num;
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


        if ($editDoc['Cpc_Cod'] > 0 && $editDoc['Cpc_Min'] <= 0 && $tipo_de_pago < 2) {
            $obBD_conIns->operacionobBD(96, $Vet_Cod . '*' . $editDoc['Cpc_Cod'], $obBD_conexionIns);
        }

        /* Creacion del comprobante contable */
        if ($configs['Cof_Con'] == 'S' && (($Tic_Sri * 1 != 0) || $Check_Comprobante * 1 === 1)) {
            $Com_Con = 'REG. VENTA ' . $Vet_Num;
            $Com_Fec = $Caj_Fec;
            $Tia_Asi = $obBD_con1->getRowConsulta(80, 7, $obBD_conexion);
            $meseCom = explode('-', $Com_Fec);
            $row_cop_old = $obBD_con1->getRowConsultaSql("SELECT Com_Fec,Com_Num FROM comprobantes WHERE Com_Cod='" . $editDoc['Com_Cod'] . "';", $obBD_conexion);
            if (substr($Com_Fec, 0, 7) !== substr($row_cop_old['Com_Fec'], 0, 7)) {
                $Com_Num = $obBD_con1->codigoComprAuto($Tia_Asi['Tia_Cod'], $Pec_Cod, $meseCom[1], $obBD_conexion); // Secuencia de comprobante por mes y por tipo
            } else $Com_Num = $row_cop_old['Com_Num'];

            $campo = 'Cli_Cod';
            //$obBD_conIns->echoLog($Com_Num);
            /* Cabecera del Comprobante */
            $obBD_conIns->operacionobBD(70, $Pec_Cod . '*' . $Cli_Cod . '*' . $Com_Num . '*' . $Com_Fec . '*' . trim($Com_Con) . '*' . $Tia_Asi['Tia_Cod'] . '*' . $t_rubros . '*' . trim($Vet_Obs) . '*' . $campo . '*' . $editDoc['Com_Cod'], $obBD_conexionIns);
            $Com_Cod = $editDoc['Com_Cod'];
            if (empty($Com_Cod) || ($Com_Cod * 1) <= 0) {
                $Com_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                $obBD_conIns->operacionobBD(83, $Com_Cod . '*' . $Vet_Cod, $obBD_conexionIns); // relacion venta comprobante
            } else $obBD_conIns->operacionobBD(41, $Com_Cod, $obBD_conexionIns); // Elimina el asiento anterior

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

            if ($t_descuento > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Plan_Cod . '*' . 'DV', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Descuentos en Ventas</u>!');

                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $t_descuento . '*' . 'DESCUENTO' . '*' . 'DESCUENTO' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // descuento
            }

            if ($t_ice * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Plan_Cod . '*' . 'ICV', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>ICE en Ventas</u>!');

                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('H') . '*' . $t_ice . '*' . 'ICE' . '*' . 'ICE' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE
            }


            /* REVISAR VARIOS PAGOS/ANTICIPOS */
            foreach ($pagos as $pag) {
                /* CCPP Cuentas por Cobrar */
                if ($pag['Forma_Cod'] * 1 == 2) {
                    if (!empty($editDoc['Cpc_Cod'])) {
                        $obBD_conIns->operacionobBD(966, $editDoc['Cpc_Cod'], $obBD_conexionIns);
                        $totalReal = $pag['Vet_Tot'] + $Ren_Tot;
                        $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $totalReal . '*' . $pag['Vet_Num'] . '*' . ('Doc.' . $Vet_Num) . '*' . $pag['Pag_Pld'], $obBD_conexionIns);
                        $obBD_conIns->operacionobBD(55, $Com_Cod . '*' . $Vet_Cod . '*' . $pag['Cpc_Ven'] . '*' . (isset($pag['Cpc_Obs']) ? $pag['Cpc_Obs'] : '') . '*' . $editDoc['Cpc_Cod'], $obBD_conexionIns);
                    } else {
                        $totalReal = $pag['Vet_Tot'] + $Ren_Tot;
                        $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $totalReal . '*' . $pag['Vet_Num'] . '*' . ('Doc.' . $Vet_Num) . '*' . $pag['Pag_Pld'], $obBD_conexionIns);
                        $obBD_conIns->operacionobBD(55, $Com_Cod . '*' . $Vet_Cod . '*' . $pag['Cpc_Ven'] . '*' . (isset($pag['Cpc_Obs']) ? $pag['Cpc_Obs'] : ''), $obBD_conexionIns);
                    }
                } else {
                    $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $pag['Vet_Tot'] . '*' . $pag['Vet_Num'] . '*' . ('Doc.' . $Vet_Num) . '*' . $pag['Pag_Pld'], $obBD_conexionIns);
                }
            }


            //EN CASO DE SER A CREDITO COMO MANEJAR LA RETENCION
            if ($pag['Forma_Cod'] * 1 == 2) {
                if (isset($rets)) {
                    //Crear Comprobante para la retencion
                    $Tia_Asi_Ret = $obBD_con1->getRowConsulta(80, 17, $obBD_conexion);
                    $Com_Num_Ret = $obBD_con1->codigoComprAuto($Tia_Asi_Ret['Tia_Cod'], $Pec_Cod, $meseCom[1], $obBD_conexion);
                    $Com_Con_Ret = 'RETENCION DE ' . $Com_Con;
                    $obBD_conIns->operacionobBD(70, $Pec_Cod . '*' . $Cli_Cod . '*' . $Com_Num_Ret . '*' . $Ret_Fec . '*' . trim($Com_Con_Ret) . '*' . $Tia_Asi_Ret['Tia_Cod'] . '*' . $Ren_Tot . '*' . 'RETENCION' . '*' . $campo, $obBD_conexionIns);
                    $Com_Cod_Ret = $obBD_conIns->insercionid($obBD_conexionIns->conexion);

                    //Crear Asientos DEBE
                    foreach ($rets as $ret) {
                        if ($ret['Ren_Sri'] * 1 === 338 && ($ret['Ren_Sri'] * 1 != 1.0 && $ret['Ren_Sri'] * 1 != 1.25 && $ret['Ren_Sri'] * 1 != 1.5 && $ret['Ren_Sri'] * 1 != 2.0)) {
                            $cuenta = $obBD_con1->getRowConsulta(103, $Plan_Cod . '*' . $ret['Ren_Sri'] . '*' . 'V', $obBD_conexion);
                        } else {
                            $cuenta = $obBD_con1->getRowConsulta(52, $Plan_Cod . '*' . $ret['Ren_Cod'] . '*' . 'V', $obBD_conexion);
                        }
                        if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) {
                            throw new Exception('Revisar la parametrizacion contable del Codigo: <u>' . $ret['Ren_Sri'] . '</u>!');
                        }
                        $obBD_conIns->operacionobBD(87, $Com_Cod_Ret . '*' . 'D' . '*' . $ret['Ren_Val'] . '*' . $cuenta['Pld_Des'] . '*' . $ret['Ren_Con'] . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
                    }
                    //Crear Asientos HABER
                    foreach ($pagos as $pag) {
                        $obBD_conIns->operacionobBD(87, $Com_Cod_Ret . '*' . ('H') . '*' . $Ren_Tot . '*' . $pag['Vet_Num'] . '*' . ('Doc.' . $Vet_Num) . '*' . $pag['Pag_Pld'], $obBD_conexionIns);
                    }

                    //Crear abono de cuentas por cobrar con la retencion
                    $obBD_conIns->operacionobBD(1133, array('Com_Cod' => $Com_Cod_Ret, 'Pag_Cod' => 50, 'Cpc_Fec' => $Ret_Fec, 'Cpc_Val' => $Ren_Tot, 'Cpc_Obs' => "ABONO POR RETENCION", 'Cpc_Cod' => $editDoc['Cpc_Cod']), $obBD_conexionIns);
                }
            }
            //EN CASO DE SER AL CONTADO
            else {
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
        } else {

            if ($editDoc['Com_Exi'] == 'S' && $editDoc['Cpc_Min'] <= 0) { //borando comprobante de venta
                $obBD_conIns->operacionobBD(41, $editDoc['Com_Cod'], $obBD_conexionIns); //borrando datos de la tabla asientos
                $obBD_conIns->operacionobBD(99, $editDoc['Com_Cod'] . "*" . $editDoc['Vet_Cod'], $obBD_conexionIns); //borando relacion Venta_comprobante
                $obBD_conIns->operacionobBD(98, $editDoc['Com_Cod'], $obBD_conexionIns); //borrando comprobante de tabla comprobantes

            }
        }
        if (isset($reembolsos))
            $obBD_conIns->operacionobBD('venta_reembolsos.deleteWhere', array('Vet_Cod' => $Vet_Cod), $obBD_conexionIns);
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
        $response = array(
            'success' => true,
            'Vet_Impr' => "" . (!empty($reportes[1]) ? "$reportes[1]?Vet_Cod=" : "") . "$Vet_Cod",
            'Vet_Cod' => $Vet_Cod,
            'Vet_Num' => $Vet_Num,
            'Vet_Fec' => $Caj_Fec,
            'Tic_Des' => $Tic_Txt
        );

        if ($Aut_Tem == 'E' && $input_autorizacion == '') {
            $rs_infoEmpresa = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
            $rs_infoCliente = $obBD_con1->getRowConsulta(61, $Aut_Cod, $obBD_conexion);
            //$responce['xml']=$obBD_con1->documentoElectronico(5, $Ses_Emp_Cod, $Ses_Suc_Cod, $Tic_Sri, array_merge($rs_infoCliente, array('Vet_Cod'=>$Vet_Cod, 'Vet_Fec'=>$Caj_Fec, 'Vet_Num'=>str_pad($Vet_Num, 9, "0", STR_PAD_LEFT))), $obBD_conexion);
            /*$response['xml']*/
            $xml = $obBD_elect->createXmlFactura($Vet_Cod, $Aut_Cod, $claveAcceso, $obBD_conexion);
            $response['Vet_Xmls'] = baseUrl("../FRONT/" . $Ses_Emp_Cod . '/' . $claveAcceso . '.xml');
            $response['xml'] = base64_encode($xml);
            //$meseVet = explode('-', $Caj_Fec);
            //$datoElect=array('{varPrsCod}'=>$Prs_Cod,'{varEmpCod}'=>$Ses_Emp_Cod,'{Emp_Nom}'=>$Ses_Emp_Nom, '{Tic_Des}'=>isset($Tic_Des)?$Tic_Des:'', '{Prs_Ced}'=>$Prs_Ced, '{proveedor}'=>$cliente, '{Prs_Cor}'=>$Prs_Cor, '{claveAcceso}'=>$claveAcceso, '{fecha}'=>$meseVet[2].' de '.mes($meseVet[1],1).' '.$meseVet[0], '{secuencia}'=>$rs_infoEmpresa["Suc_Sri"].'-'.$rs_infoCliente["Pun_Sri"].'-'.str_pad($Vet_Num, 9, "0", STR_PAD_LEFT));
            //$responce['mail']=$obBD_con1->sendMailDoc($datoElect, reporteHtml($datoElect,'fac_pri_ret_ele.html'));
            //$responce['mail'] = $obBD_elect->sendMailDoc($Vet_Cod,$Prs_Cor,NULL,$obBD_conexion);
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

            //  $response['Com_Link'] = "" . (!empty($reportes[2]) ? "$reportes[2]?codigo=" : "") . "$Com_Cod";
        }

        if (!empty($Com_Cod_Ret)) {
            $response['Com_Data_Ret'] = array('Codigo_Ret' => $Com_Cod_Ret, 'Tia_Des_Ret' => $Tia_Asi_Ret['Tia_Des'], 'Com_Con_Ret' => $Com_Con_Ret, 'Com_Fec_Ret' => $Ret_Fec, 'Com_Val_Ret' => $Ren_Tot);
            $response['Com_Rows_Ret'] = $obBD_con1->getArrayConsulta(27, $Com_Cod_Ret, $obBD_conexion);
            $response['Com_Link_Ret'] = "" . (!empty($reportes[2]) ? "$reportes[2]?codigo=" : "") . "$Com_Cod_Ret";
        }

        if (isset($rets)) {
            $response['Ret_Cod'] = $Ret_Cod;
            $response['Ret_Data'] = array('Ret_Num' => $Ret_Num, 'Aut_Sri' => $Ret_Aut_Sri, 'Ret_Fec' => $Ret_Fec, 'Ren_Tot' => $Ren_Tot, 'Iva_Ren_Tot' => $Iva_Ren_Tot, 'Ret_Ren_Tot' => $Ret_Ren_Tot);
            $response['Ret_Rows'] = $rets;
        }
    } else {
        $response = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }

    $obBD_conIns->echoJson($response);
}

/* Editar observacion de una venta */
if (isset($editarObservacion)) {
    $data = $_POST;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $obBD_con1->operacionobBD(970, $data, $obBD_conexion);
    if ($configs['Cof_Con'] == 'S') {
        $obBD_con1->operacionobBD(971, $data, $obBD_conexion);
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true);
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', 'error' => $obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($responce);
}

/* busqueda de documentos */
if (isset($comprasReembolsoAjax)) {
    $obBD_con1->getPageGridJson('compras.selectWhere', array_merge($_GET, array('where' => "", 'setWhere' => array('isActive', 'setTotales', "notInReembolsos"))), $obBD_conexion);
}
//$obBD_con1->echoLog($_SESSION['Ses_Emp_Log']);
$rs_tip_compr = $obBD_con1->getArrayConsulta(30, '', $obBD_conexion);
$rs_periodo = $obBD_con1->getArrayConsulta(33, $Ses_Emp_Cod, $obBD_conexion);





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




?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Ventas Modificar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <SCRIPT>
        //utilizacion de variable global que identifica el padre del archivo js validaciones
        <?php $array_documentos = $obBD_con1->getArrayConsulta(8, $rs_Punto['Pun_Cod'], $obBD_conexion); ?>
        var array_documentos = <?php echo json_encode($array_documentos); ?>,
            ivas_venta = <?php echo json_encode($ivas) ?>;;
        var edicion_ventas = true,
            vet_num_ant = 0,
            tic_cod_ant = 0;
        var docs, items, pagos, data = [],
            Vet_Index = 1,
            Vet_Selected, index, Cof_Con = '<?php echo $configs['Cof_Con']; ?>';
        var array_vendedor = <?php echo json_encode($rs_Punto); ?>;
    </script>
    <script language="javascript" src="../../framework/plugins/validadorCedulaRucFinal.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/fac_val_factura.js?x=21"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.5/jszip.min.js"></script>
    <script>
        inicializarDocVenta(false);
    </script>
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
            <h3 class="panel-title ">&raquo; Modificar Documentos de Ventas</h3>
            <p id="cabeceraPuntoImp" class="text-right col-xs-12  " style="margin-top:-15px;">punto de impresion</p>
        </div>

        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
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
                                                << TODOS>>
                                            </option>
                                            <?php foreach ($rs_tip_compr as $row) {
                                                $row = array_map(function($v) { return mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1'); }, $row); // Convert each element to UTF-8
                                                if ($row['Tic_Sri'] != 4 && $row['Tic_Sri'] != 5 && $row['Tic_Sri'] != 7 && $row['Tic_Sri'] != 23 && $row['Tic_Sri'] != 24)
                                                    echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Periodo:</label>
                                    <div class="col-xs-2">
                                        <select name="Pec_Cod" class="form-control input-xs search_pec" onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled');">
                                            <option value="">
                                                << TODOS>>
                                            </option>
                                            <?php $rs_perio = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
                                            foreach ($rs_perio as $row) { ?>
                                                <option value="<?php echo $row['Pec_Cod']; ?>" data-inicio="<?php echo $row['Pec_Fei']; ?>" data-fin="<?php echo $row['Pec_Fef']; ?>"><?php echo $row['Anio']; ?></option>
                                            <?php   } ?>

                                        </select>

                                    </div>
                                    <label class="col-xs-2 control-label label-xs">Mes:</label>
                                    <div class="col-xs-2">
                                        <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec" disabled="disabled">
                                            <option value="">
                                                << TODOS>>
                                            </option>
                                            <?Php for ($i = 1; $i <= 12; $i++) { ?><option <?php if ($i == $mes) {
                                                                                                echo "selected=''";
                                                                                            } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1); ?></option><?Php } ?>
                                        </select>
                                    </div>

                                    <label class="col-xs-2 control-label label-xs">Mis Ingresos</label>
                                    <div class="col-xs-2">
                                        <input type="checkbox" value="S" offval="N" id="mis_ingresos" name="mis_ingresos">
                                    </div>


                                </div>
                            </fieldset>
                        </div>
                    </div>
                </form>
                <div style="min-height: 300px;">
                    <table id="searchGrid"></table>
                    <table id="searchGridPager"></table>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop green"></span> Contiene Pagos | <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="fa fa-globe green"></span> Retención Electronica Validada | <span class="glyphicon glyphicon-lock orange"></span> Formato Anterior</div>
                </div>
                <br>
                <div>
                    <button type="button" onclick="descargarPDF()" class="btn btn-success btn-sm" title="Descargar pdfs"><i class="glyphicon glyphicon-download-alt"></i> <span>Descargar PDFs</span></button>
                </div>

                <script>
                    function descargarPDF() {
                        var rows = $("#searchGrid").jqGrid("getDataIDs");
                        var currentDomain = window.location.origin;

                        // Muestra el loader
                        if (rows.length > 0) {
                            document.getElementById("loader").style.display = "block";
                            var pdfUrls = [];

                            for (var i = 0; i < rows.length; i++) {
                                var rowData = $("#searchGrid").jqGrid("getRowData", rows[i]);
                                var id = rowData.Vet_Cod;
                                if (id) {
                                    pdfUrls.push(id);
                                }
                            }

                            var zip = new JSZip();
                            var promises = pdfUrls.map(function(pdfUrl, index) {
                                return new Promise(function(resolve) {
                                    var xhr = new XMLHttpRequest();
                                    var link = currentDomain + '/facturacion/COMPONENTES/tesPdfElectronicos.php?type=VENTAS&Doc_Cod=' + pdfUrl;
                                    xhr.open("GET", link, true);
                                    xhr.responseType = "blob";

                                    xhr.onload = function() {
                                        if (xhr.status === 200) {
                                            var blob = xhr.response;
                                            zip.file("documento_" + pdfUrl + ".pdf", blob);
                                            resolve();
                                        }
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
                                    link.download = "archivosPDF.zip"; // Nombre del archivo ZIP
                                    link.click();

                                    // Oculta el loader cuando todas las promesas se resuelven
                                    document.getElementById("loader").style.display = "none";
                                });
                            });
                        }
                    }


                    function setOpt(val) {
                        if (val === 'd') $('.search_pec').attr('disabled', 'disabled');
                        else $('.search_pec').removeAttr('disabled');
                    }


                    function cargarDoc(doc) {
                        init_load = true;
                        $('#Check_Comprobante').prop('checked', (doc['Com_Exi'] === "S" ? true : false));
                        items.clearGridData();
                        vet_num_ant = doc['Vet_Num'];
                        tic_cod_ant = doc['Tic_Cod'];
                        editDoc = true;
                        AutCod = doc['Aut_Cod'];
                        TicCod = doc['Tic_Cod'];
                        $('#editDoc').setData({});
                        // $('#Pec_Cod').attr('disabled', true); Bloquear el periodo contable
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
                            $('#clieFormTemp,#viajesForm').setData({
                                Prs_Ced: doc['Prs_Ced'],
                                Cli_Cod: doc['Cli_Cod'],
                                cliente: doc['cliente_per'],
                                op_opciones: 'c'
                            });
                            $('#viajesSelectedGrid').setRows(resp['Viajes_Sel'] || []);
                            $('.viajes')[$.vv(resp['Viajes']) && resp['Viajes'].toNum() > 0 ? 'show' : 'hide']();
                            $.SearchOrDialog('#clieDialog', selectCliente);
                            if (doc['Pec_Cod']) {
                                $('#Pec_Cod').val(doc['Pec_Cod']);
                            } else {
                                var periodo_selec = doc['Vet_Fec'].split("-")[0];
                                $("#Pec_Cod").find('option:contains("' + periodo_selec + '")').prop('selected', true);
                            }
                            var sel_fecha = $("#Pec_Cod").find('option:selected');
                            $('#Caj_Fec').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
                            $('.placod').val(sel_fecha.data('placod'));
                            $('#Caj_Fec').val(doc['Vet_Fec']);
                            $("textarea[name=Vet_Obs]").val(doc['Vet_Obs']);
                            //$('#docuFormTemp').setData({'Tic_Cod':doc['Tic_Cod'],'Vet_Num':doc['Vet_Num']},false);
                            items.jqGrid('delRowData', 1);
                            $.each(resp['items'], function(x, item) {
                                addItem(item, item['Vet_Can'], item['Vet_Pru']);
                            });
                            $("#Vet_Rem").prop('checked', false);
                            if ($.vv(resp['reembolsos']) && resp['reembolsos'].length > 0) {
                                $("#Vet_Rem").prop('checked', true);
                                reembolsos.setRows(resp['reembolsos']);
                            }
                            $("#Vet_Rem").trigger('change');
                            aBorrar = addItem({});
                            var aCobrar = $('#Val_Pcc_2').val() * 1;
                            pagos.clearGridData();
                            console.log(resp['pagos']);
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

                            //updateDocument();
                            items.jqGrid('delRowData', aBorrar);
                            $('#Ret_Fec').val(doc['Ret_Fec']);
                            var botones_pagos = $('#pagosPager_left').find('td.btn-success');
                            var btn_pagos_activos = $('.porCobrar').find('span.input-group-btn');
                            if ((doc['Cpc_Min'] * 1) <= 0) {
                                btn_pagos_activos.addClass('hidden');
                                botones_pagos.removeClass('hidden');
                                pago_min = 0;
                            } else {
                                pago_min = doc['Cpc_Min'] * 1;
                                btn_pagos_activos.removeClass('hidden');
                                btn_pagos_activos.createFlyout('Posee pagos activos por <i class="glyphicon glyphicon-usd">' + pago_min + '</i> !', {
                                    icon: 'exclamation',
                                    placement: 'left_top'
                                });
                                btn_pagos_activos.flyout('show').focus();
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

                            $('#Tic_Cod').html(html);
                            $('#Tic_Cod').val(doc['Tic_Cod']).trigger('change');
                            $('#Vet_Des').val(doc['Vet_Des']).trigger('change');
                            $('#t_descuento').val($('#t_subtotal').val() * $('#Vet_Des').val() * 1 / 100).trigger('change');
                            $('#Ret_Num').val(doc['Ret_Num']);
                            $('input[name=Ret_Aut_Sri]').val(doc['Ret_Aut']);
                            $('#Ret_Num').trigger('change');

                            $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
                            addItem({});
                        });
                    }
                    $('#searchGrid').createGrid({
                        caption: 'Resultado de la Búsqueda',
                        height: 270,
                        datatype: "local",
                        caption: 'Resultados <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="order by caja_aper.Caj_Fec DESC ">Fecha Venta</option><option value="order by Vet_Num DESC ">Num. Documento</option><select>&nbsp;</div>',
                        colModel: [{
                                label: 'Cód. Int.',
                                name: 'Vet_Cod',
                                width: 30,
                                align: "center",
                                key: true
                            },
                            {
                                label: 'Compr.',
                                name: 'Com_Exi',
                                width: 20,
                                align: "center",
                                formatter: 'truefalse',
                                formatoptions: {
                                    yesMsg: 'Tiene Comprobante',
                                    noMsg: ' '
                                },
                                title: false
                            },
                            {
                                label: 'Reten.',
                                name: 'Ret_Exi',
                                width: 20,
                                align: "center",
                                formatter: 'truefalse',
                                formatoptions: {
                                    yesMsg: 'Tiene Retencion',
                                    noMsg: ' '
                                },
                                title: false
                            },
                            {
                                label: 'Pago',
                                name: 'Pago',
                                width: 35,
                                align: "center"
                            },
                            {
                                label: 'P. SRI',
                                name: 'Tpc_Sri',
                                width: 20,
                                align: "center",
                                formatter: 'title',
                                formatoptions: {
                                    title: function(o) {
                                        return o['Tpc_Des'];
                                    }
                                },
                                title: false
                            },
                            {
                                label: 'Tipo Documento',
                                name: 'Tic_Des',
                                width: 100
                            },
                            {
                                label: 'Com_Cod',
                                name: 'Com_Cod',
                                width: 100,
                                hidden: true
                            },
                            {
                                label: 'No. Documento',
                                name: 'Vet_Num',
                                width: 90,
                                align: "center"
                            },
                            {
                                label: 'Fecha',
                                name: 'Vet_Fec',
                                width: 45,
                                align: "center"
                            },
                            {
                                label: 'Cliente',
                                name: 'cliente_per',
                                width: 150
                            },
                            {
                                label: 'Estado',
                                name: 'Vet_Est',
                                width: 20,
                                align: "center",
                                formatter: 'estado',
                                title: false
                            },
                            {
                                label: '&nbsp;',
                                name: 'act2',
                                width: 20,
                                align: 'center',
                                viewable: false,
                                formatter: 'gridButton',
                                formatoptions: {
                                    action: ImpDoc,
                                    title: 'Imprimir Documento',
                                    icon: 'print',
                                    type: 'info'
                                },
                                title: false
                            },
                            {
                                label: '&nbsp;',
                                name: 'act2',
                                width: 20,
                                align: 'center',
                                viewable: false,
                                formatter: 'gridButton',
                                formatoptions: {
                                    action: ImpCom,
                                    title: 'Imprimir Comprobante',
                                    icon: 'print',
                                    type: 'info'
                                },
                                title: false
                            },
                            {
                                label: '&nbsp;',
                                name: 'act0',
                                width: 20,
                                align: 'center',
                                viewable: false,
                                formatter: 'gridButton',
                                formatoptions: {
                                    action: viewInfo,
                                    title: 'Ver Documento',
                                    icon: 'info-sign',
                                    type: 'info'
                                },
                                title: false
                            },
                            {
                                label: '&nbsp;',
                                name: 'act0',
                                width: 20,
                                align: 'center',
                                viewable: false,
                                formatter: 'gridButton',
                                formatoptions: {
                                    action: editObservacion,
                                    title: 'Editar observación',
                                    icon: 'search',
                                    type: 'info'
                                },
                                title: false
                            },
                            {
                                label: 'XML',
                                name: 'act02',
                                width: 20,
                                align: 'center',
                                viewable: false,
                                formatter: 'gridButton',
                                formatoptions: {
                                    action: descargar,
                                    title: 'Ver XML',
                                    icon: 'file',
                                    type: 'info',
                                    conditional: function(o) {
                                        return o.Vet_Est !== 'I' && !$.isEmpty(o.Vet_Xml);
                                    }
                                },
                                title: false
                            },
                            {
                                label: 'PDF',
                                name: 'act02',
                                width: 20,
                                align: 'center',
                                viewable: false,
                                formatter: 'gridButton',
                                formatoptions: {
                                    action: viewPdfVenta,
                                    title: 'Ver PDF',
                                    icon: 'file',
                                    type: 'info',
                                    conditional: function(o) {
                                        return o.Vet_Est !== 'I' && !$.isEmpty(o.Vet_Xml);
                                    }
                                },
                                title: false
                            },
                            {
                                label: '&nbsp;',
                                name: 'act1',
                                width: 20,
                                align: 'center',
                                viewable: false,
                                formatter: 'edicion',
                                title: false
                            }
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
            <div id="documentoMain" style="visibility: hidden;">
                <div class="row">
                    <div class="col-xs-12" id="panelVentas">
                        <div class="row">
                            <div id="pagosDialog" title="Agregar Pagos" style="display: none;">
                                <form id="pagosForm" class="form-horizontal normal" action="javascript:addPago($('#pagosForm').getData())">
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

                                    <?php if ($configs['Cof_Con'] == 'S') { ?>
                                        <div class="form-group cuenta_pago">
                                            <label class="col-xs-3 control-label label-xs">Cuenta:</label>
                                            <div class="col-xs-9">
                                                <select id="Pag_Pld" name="Pag_Pld" class="form-control input-xs readOnly" required=""></select>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <!-- bancos en la base de datos -->
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

                                    <!-- cuentas bancaria -->
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
                                        <label class="col-xs-3 control-label label-xs required">Cta&nbsp;Banco:</label>
                                        <div class="col-xs-9">
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

                                    <?php if ($configs['Cof_Con'] == 'S') { ?>
                                        <div class="form-group pagoCredito" style="display: none;">
                                            <input type="text" name="Cpc_Min" style="display:none" />
                                            <label class="col-xs-3 control-label label-xs required">Vencimiento:</label>
                                            <div class="col-xs-6">
                                                <input id="Cpc_Ven" name="Cpc_Ven" type="text" class="form-control input-xs datepickers" />
                                            </div>
                                        </div>
                                        <div class="form-group pagoCredito obs_credito" style="display: none;">
                                            <label class="col-xs-3 control-label label-xs">Observación:</label>
                                            <div class="col-xs-9">
                                                <textarea name="Cpc_Obs" class="form-control input-xs"></textarea>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    
                                    <div class="form-group info-tarjeta" style="display: none;">                                         
                                        <label class="col-xs-3 control-label label-xs" title="Numero Lote">No Lote:</label>
                                        <div class="col-xs-9">
                                            <input id='Vet_Nlt' name="Vet_Nlt" type="text" class="form-control input-xs " >                                                
                                        </div>
                                    </div>
                                    <div class="form-group info-tarjeta" style="display: none;">  
                                        <label class="col-xs-3 control-label label-xs" title="Numero Transaccion">No Trans:</label>
                                        <div class="col-xs-9">
                                            <input id='Vet_Nts' name="Vet_Nts" type="text" class="form-control input-xs " >
                                        </div>
                                    </div>
                                    <div class="form-group info-tarjeta" style="display: none;">  
                                        <label class="col-xs-3 control-label label-xs" title="Numero Autorizacion">No Auto:</label>
                                        <div class="col-xs-9">
                                            <input id='Vet_Nau' name="Vet_Nau" type="text" class="form-control input-xs " >                                                
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
                                            <input class='hidden' id='Vet_Num_Ant' readonly="" />
                                        </div>
                                    </div>
                                    <div class="form-group center">
                                        <button class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> Agregar</button>
                                    </div>
                                </form>
                            </div>


                            <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument(true);">
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
                                    <?php if (isset($tipospago)) foreach ($tipospago as $row) { ?><option value="<?php echo $row['Pag_Cod']; ?>" data-forcod="<?php echo $row['For_Cod']; ?>"><?php echo mb_convert_encoding($row['Pag_Des'], 'ISO-8859-1', 'UTF-8'); ?></option><?php } ?>
                                </select>

                                <!--bancos-->
                                <select id="bak_cod" name="bak_cod" class="form-control input-xs" style="display: none;">
                                    <?php if (isset($bankos)) foreach ($bankos as $row) { ?><option value="<?php echo $row['Bak_Cod']; ?>"><?php echo mb_convert_encoding($row['Bak_Des'], 'ISO-8859-1', 'UTF-8'); ?></option><?php } ?>
                                </select>

                                <!--cuentas contado=1, credito=2-->
                                <select id="pld_cod" name="pld_cod" class="form-control input-xs" style="display: none;"></select>

                                <div class="col-md-5 col-xs-12">
                                    <fieldset class="exa-fieldset" id="clieFormTemp">
                                        <legend class="Titulos2">Datos del Cliente</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Cédula/RUC:</label>
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
                                                        <button id="Via_Btn" type="button" onclick="$('#viajesGrid').clearGridData(); $('#viajesDialog').dialog('open');" class="btn btn-success btn-xs viajes" title="Seleccionar Viajes" tabindex="2" style="display:none;"><span class="fa fa-truck"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Cliente:</label>
                                            <div class="col-xs-10"><span name="cliente" class="form-control input-xs databind datatitle"></span></div>
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
                                        </div>

                                    </fieldset>

                                    <?php $bodegas = $obBD_con1->getArrayConsulta('bodega.1', array('Suc_Cod' => $Ses_Suc_Cod, 'Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion); ?>

                                    <fieldset class="exa-fieldset" <?php if (count($bodegas) == 0) echo 'style="display:none; "'; ?>>
                                        <legend class="Titulos2"></legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Bodega:</label>
                                            <div class="col-xs-10">
                                                <select id="Bod_Cod" name="Bod_Cod" class="form-control input-xs">
                                                    <?php if (count($bodegas) > 0) foreach ($bodegas as $row) {
                                                        echo "<option value='$row[Bod_Cod]'>$row[Bod_Nom]</option>";
                                                    } ?>
                                                </select>
                                            </div>
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
                                            <label class="col-xs-2 control-label label-xs required">Número:</label>
                                            <div class="col-xs-5">
                                                <div class="input-group input-group-xs">
                                                    <span id="Pun_Sri" name="Pun_Sri" class="input-group-addon alert-info"></span>
                                                    <input type="text" id="Vet_Num" name="Vet_Num" onchange="validarTic_Cod()" class="form-control input-xs trigger" tabindex="5" required="" data-container="body" data-toggle="popover" />
                                                    <span class="input-group-addon validate"><i></i></span>
                                                </div>
                                            </div>
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
                                                    <button type=button id="btnAddAut" title="Autorizacion Externa" class="col-xs-1 input-xs btn btn-warning "><i class="glyphicon glyphicon-alert"></i></button>
                                                    <div><input maxlength="55" minlength="49" title="minimo 49 caracteres" class="input-xs" name="input_autorizacion" size="" /></div>
                                                </div>
                                            </div>

                                        </div>
                                    </fieldset>
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
                                                    <span class="input-group-addon bold alert-warning">Monto a Pagar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i></span>
                                                    <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                                                    <input id="Val_Pcc" name="Val_Pcc" type="text" class="form-control bold span" style="text-align: right;font-size: 15px; background-color: white;" readonly="" />
                                                    <span id="infoLiquida" class="input-group-addon validate" style="display:none;"><i></i></span>

                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                </form>
                                <div>
                                    <div class="condensed" style="min-height: 100px; padding-bottom: 5px;">
                                        <table id="pagos"></table>
                                        <div id="pagosPager"></div>
                                    </div>
                                    <!-- <div>
                                        <button class="black btn btn-sm btn-inverse" onclick="clearDocument();$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i>Atr&aacute;s</button>
                                        <button class="btn btn-sm btn-primary" type="button" onclick="$('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                    </div> -->
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
                                                        $selected = '';
                                                        if ($row['Tpc_Sri'] == 1) {
                                                            $selected = 'Selected';
                                                        }
                                                        echo "<option value='" . $row['Tpc_Cod'] . "' " . $selected .  " >" . mb_convert_encoding($row['Tpc_Sri'], 'UTF-8', 'ISO-8859-1') . " - " . mb_convert_encoding($row['Tpc_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
                                                        //echo "<option value='$row[Tpc_Cod]' ".$selected."  >$row[Tpc_Sri] - $row[Tpc_Des]</option>";
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

                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-info" onclick="$('.porCobrar').find('span.input-group-btn').flyout('show').focus();" tabindex="-1"><span class="fa fa-money white"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
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
                                </form>
                            </div>


                        </div>
                        <div class="row center-block">

                        </div>

                        <div>
                            <button class="black btn btn-sm btn-inverse" onclick="clearDocument();$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i>Atr&aacute;s</button>
                            <button class="btn btn-sm btn-primary" type="button" onclick="$('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                    </div>
                    <div class="col-xs-12 Titulos2">
                        <hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
                    </div>
                </div>
            </div>

            <div id="documentoResult" class="form-horizontal normal" style="visibility: hidden;">
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
                                <div style="padding-top: 15px; text-align: center;">
                                    <form name="frm_pdf" id="frm_pdf" action="../COMPONENTES/tesPdfFacturaElectronica_2.0.php" method="post" target="_blank">
                                        <button type="button" class="btn btn-sm btn-success" onclick="clearDocument(); $('#searchGrid').trigger( 'reloadGrid' ); $('#documentoResult').moveComp('#documentoSearch').updateGridsSizes();"><i class="glyphicon glyphicon-search"></i> Buscar Documento</button>
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
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del Documento</legend>
                            <div class="col-xs-12">
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
                        </fieldset>
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
                                    }, true);
                                    $.clearFooterDiario("#asiento");
                                </script>
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
                                </fiedset>
                                <script>
                                    $('#asientoRet').createGrid({
                                        height: 75,
                                        postData: {
                                            CheListAjax: true
                                        },
                                        caption: 'Asiento Contable <button id="btnComPrintRet" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>',
                                        rowNum: 10000,
                                        footerrow: true,
                                        userDataOnFooter: true,
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
                                    }, true);
                                    $.clearFooterDiario("#asientoRet");
                                </script>
                        </div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Inicio del di�logo para buscar clientes -->
    <div id="clieDialog" title="B&uacute;squeda de Cliente" style="display: none;">
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
            $('#clieFormTemp').setData($.extend(cliente, {
                op_opciones: 'c'
            }));
            $('#clieDialog').dialog('close');
        }
    </script>

    <div id="autorizaDialog" title="B&uacute;squeda de Autorizaciones" style="display: none;">
        <form class="form-horizontal normal" id="autorizaForm">
            <input type="text" name="Tic_Cod" class="hidden" />
            <input type="text" name="Pun_Cod" class="hidden" />
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



    <!-- Inicio del di�logo para registrar clientes -->
    <div id="clieCreateDialog" title="Registrar Cliente" style="display:none;">
        <form class="form-horizontal normal" id="clieCreateForm" action="javascript:if(ValidacionCedulaRucService.esIdentificacionValida($('#Prs_Ced').val())['success']){ guardaCliente(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos del Cliente</legend>

                <!--<div class="form-group">
                        <label class="col-xs-3 control-label label-xs">Ciudadano:</label>
                        <div class="col-xs-5" >
                            <div class="btn-group" data-toggle="buttons">
                                <label id="lb_ec" class="btn btn-success btn-xs">
                                    <input id="radioec" name="tipo" value="Ec" type="radio" checked=""><i id="spanec" class="fa fa-check"></i> Ecuatoriano
                                </label>
                                <label id="lb_ex" class="btn btn-default btn-xs">
                                    <input id="radioex" name="tipo" value="Ex" type="radio"><i id="spanex" class="fa fa-check" style="display: none;"></i> Extranjero
                                </label>
                            </div>
                        </div>
                    </div> -->
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                    <div class="col-xs-5">
                        <div class="input-group input-group-xs">
                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="validar(1)" required="" />
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
                        <?php $rs_identi = $obBD_con1->getArrayConsulta(299, '', $obBD_conexion); ?>
                        <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                            <option value="">Seleccionar</option>
                            <?php foreach ($rs_identi as $row) {
                                echo "<option value='$row[Ide_Cod]' data-tipo='$row[Tipo]'>$row[Ide_Des]</option>";
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
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Nomb.Comerc.:</label>
                    <div class="col-xs-9"><input name="Cli_Fac" type="text" class="form-control input-xs" /></div>
                </div>
            </fieldset>
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos de Ubicación</legend>
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
    <script type="text/javascript">
        var gridFact, index, Cof_Con = '<?php echo $configs['Cof_Con']; ?>',
            cod_banano = <?php echo $cod_banano; ?>;
        $(function() {
            $('#documentoMain').css('visibility', '').hide();
            $('#documentoResult').css('visibility', '').hide();
        });
    </script>

    <!--INICIO DEL DIALOGO BUSCAR PRODUCTO-->
    <div id="proDialog" title="B&uacute;squeda de Productos" style="display: none;">
        <form class="form-horizontal normal">
            <input type="text" name="Pla_Cod" class="placod" style="display: none;" />
            <input type="text" id="Bodega_Cod" name="Bodega_Cod" style="display: none;" />
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
                label: 'Descripción',
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
                label: 'Stock',
                name: 'Stk_Can',
                width: 25,
                align: "center"
            },
            {
                label: 'Tipo',
                name: 'Tpv_Des',
                width: 25,
                align: "center"
            },
            {
                label: 'Precio',
                name: 'Vet_Pru',
                width: 40,
                align: "right",
                formatter: 'currency'
            },
            {
                label: 'P.V.P.',
                name: 'PVP',
                width: 30,
                align: 'right',
                formatter: function(cv, opts, robj) {
                    if (!$.varValid(robj['Pre_Pvp']) || !$.varValid($('#Def_Ivas option:selected').data('ivapor'))) return '';
                    return !($.round(robj['Iva_Por']) > 0) ? $.toFixed(robj['Pre_Pvp']) : $.toFixed($.round(robj['Pre_Pvp']) + $.round(robj['Pre_Pvp']) * $('#Def_Ivas option:selected').data('ivapor') / 100);
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
    <!-- FIN DEL DIALOGO PRODUCTO-->
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
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
    <div id="provDialog" title="B&uacute;squeda de Proveedor" style="display: none;">
        <form class="form-horizontal normal"> </form>
    </div>
    <script>
        // DIALOG BUSCAR proveedor
        $.createSearchDialog('provDialog', [{
                label: 'C&oacute;d.Int.',
                name: 'Prv_Cod',
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
                label: 'Proveedor',
                name: 'proveedor',
                width: 100
            },
            {
                label: 'Cont.',
                name: 'Prv_Con',
                width: 20,
                align: "center",
                labelLong: 'Obligado a Llevar Contabilidad',
                formatter: 'truefalse',
                formatoptions: {
                    msg: false
                }
            },
            {
                label: 'Espe.',
                name: 'Prv_Esp',
                width: 20,
                align: "center",
                labelLong: 'Contribuyente Especial',
                formatter: 'truefalse',
                formatoptions: {
                    msg: false
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
                    action: selectProvee
                }
            }
        ], null, null, null, {
            headertitles: true
        }, {
            title: 'Proveedor',
            text: 'Prs_Ced'
        });

        function selectProvee(provee) {
            $('#clieFormTemp,#viajesForm').setData($.extend(provee, {
                op_opciones: 'c'
            })).find('.dialogSearch').addClass('x');
            $('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Con'] === 'S' ? 'ok green' : 'remove blue'));
            $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Esp'] === 'S' ? 'ok green' : 'remove blue'));
            $('#provDialog').dialog('close');
            $('#viajesSelectedGrid').setRows([]);
            $('.viajes')[$.vv(cliente['Viajes']) && cliente['Viajes'].toNum() > 0 ? 'show' : 'hide']();
            checkLiquidacion();
            validaCopNum();
        }
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
    <script>
        $.createSearchDialog('codiDialog', [{
                label: 'Cód.Int.',
                name: 'Ren_Cod',
                key: true,
                width: 25,
                align: "center"
            },
            {
                label: 'Código',
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
    </script>
    <!--
    <div id="provCreateDialog" title="Registrar Proveedor" style="display:none;">
        <form class="form-horizontal normal" id="provCreateForm" action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaProvee(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset" >
                <legend class="Titulos2">Datos del Proveedor</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">C�dula/RUC:</label>
                    <div class="col-xs-5" >
                        <div class="input-group input-group-xs">
                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="if(validaNoIdentif(this.value)['success']){ $('#Ide_Cod').val(this.value.length===10?2:1); $('#Prv_Tic').val(validaNoIdentif(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchProvee(this.value); }else{ $('#Ide_Cod').val(''); $('#Prv_Tic').val(''); $(this).fieldValid(false,validaNoIdentif(this.value)['message']); };" required="" />
                            <span class="input-group-addon validate" ><i></i></span>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="checkbox check-big" style="position:absolute;">
                          <label><input type="checkbox" name="Prv_Esp" value="S" offval="N">Contrib. Especial</label>
                          <label><input type="checkbox" name="Prv_Con" value="S" offval="N">Obligado Contab.</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Documento:</label>
                    <div class="col-xs-5" >
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
                    <div class="col-xs-4" >
                        <select id="Prv_Tic" name="Prv_Tic" class="form-control input-xs" required="" onchange="if(this.value==='N'){ $('.juridico').hide();$('.natural').show(); }else{ $('.natural').hide();$('.juridico').show(); }">
                            <option value = "N" >NATURAL</option>
                            <option value = "J" >JURIDICO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Raz�n Social:</span></label>
                    <div class="col-xs-9" ><input name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs">Nombres:</label>
                    <div class="col-xs-9" ><input name="Prs_Nom" type="text" class="form-control input-xs" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs required">Genero:</label>
                    <div class="col-xs-4" >
                        <select name="Prs_Sex" class="form-control input-xs">
                            <option value = "M" >MASCULINO</option>
                            <option value = "F" >FEMENINO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Nomb.Comerc.:</label>
                    <div class="col-xs-9" ><input name="Prv_Com" type="text" class="form-control input-xs" required="" /></div>
                </div>
            </fieldset>
            <fieldset class="exa-fieldset" >
                <legend class="Titulos2">Datos de Ubicaci�n</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                    <div class="col-xs-4" >
                        <select name="Ciu_Cod" class="form-control input-xs" required="" >
                            <option value=""></option>
                            <?php foreach ($rs_ciudad as $row) {
                                echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Direcci�n:</label>
                    <div class="col-xs-9" ><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Tel�fono:</label>
                    <div class="col-xs-4" ><input name="Prs_Tel" type="text" class="form-control input-xs" required="" pattern="\d*" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Mail:</label>
                    <div class="col-xs-5" ><input name="Prs_Cor" type="mail" class="form-control input-xs" required="" /></div>
                </div>
            </fieldset>
            <div class="center">
                <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            </div>
            <div class="Titulos2"><hr><b>NOTA:</b> Los campos marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
        </form>

    </div>
    -->
    <!-- FIN DEL DIALOGO PROVEEDOR-->
    <!-- DIALOGO DETALLE RETENCION -->
    <div id="retDetaDialog" title="Retención">
        <div class="condensed-header">
            <table id="retencion"></table>
        </div>
    </div>
    <script>
        // DIALOG create cliente
        $('#clieCreateDialog').createDialog({
            icon: 'plus',
            width: 500,
            height: 460
        });
        // Buscar una persona
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
                width: 550,
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
                width: 600,
                noTitleStuff: false,
                noBorder: true
            });
            if ($('#docDetaObservacion').length > 0)
                $('#docDetaObservacion').createDialog({
                    height: 200,
                    width: 600,
                    noTitleStuff: false,
                    noBorder: true
                });
        });
    </script>
    <!-- DIALOGO seleccion Autorizacion -->

    <!--Fin DETALLE Autorizaciones -->

    <!-- DIALOGO DETALLE DOCUMENTO -->
    <div id="docDetaDialog" title="Documento" style="display: none;">
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
                    <div class="col-xs-6"><span name="cliente_per" class="form-control input-xs"></span></div>
                    <label class="col-xs-1 control-label label-xs">Fecha:</label>
                    <div class="col-xs-3"><span name="Vet_Fec" class="form-control input-xs"></span></div>
                </div>
                <div class="form-group condensed">
                    <div class="col-xs-12">
                        <div class="pull-right">
                            <table id="detaDocu"></table>
                        </div>
                    </div>
                    <div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;"><b>CREACIÓN:</b> <span name="Vet_Sys" class="databind"></span> &nbsp;&nbsp;-&nbsp;&nbsp; <b>USUARIO:</b> <span name="vendedor_per" class="databind"></span></div>
                </div>
            </div>
        </fieldset>
        <fieldset class="exa-fieldset" id="RetenViewGrid">
            <legend class="Titulos2">Retencion:</legend>
            <div class="form-horizontal normal" style="padding: 0 4px;">
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Numero.:</label>
                    <div class="col-xs-3"><span name="Ret_Num" class="form-control input-xs"></span></div>
                    <label class="col-xs-1 control-label label-xs">Fecha:</label>
                    <div class="col-xs-3"><span name="Ret_Fec" class="form-control input-xs"></span></div>
                    <label class="col-xs-2 control-label label-xs">Autorización.:</label>
                    <div class="col-xs-1"><span name="Ret_Aut" class="form-control input-xs"></span></div>
                </div>
                <div class="form-group condensed">
                    <div class="col-xs-12">
                        <div class="pull-right">
                            <table id="detaReten"></table>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>



    <div id="changePagoDialog" title="Cambiar Forma de Pago" style="display:none;">
        <form class="form-horizontal normal" id="changePagoForm" action="javascript:saveChangePago();">
            <input type="text" name="Vet_Cod" class="hidden" />
            <input type="text" name="Cpc_Cod" class="hidden" />
            <input type="text" name="Com_Cod" class="hidden" />
            <input type="text" name="Caj_Fec" class="hidden" />
            <input type="text" name="Pld_Cod_Pag" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Forma de Pago</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Tipo:</label>
                    <div class="col-xs-10"><span name="Tic_Des" class="form-control input-xs"></span></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Cliente:</label>
                    <div class="col-xs-10"><span name="cliente_per" class="form-control input-xs"></span></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Secuencia:</label>
                    <div class="col-xs-5"><span name="Vet_Num" class="form-control input-xs"></span></div>
                    <label class="col-xs-2 control-label label-xs">Fecha:</label>
                    <div class="col-xs-3"><span name="Vet_Fec" class="form-control input-xs"></span></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs required">Forma:</label>
                    <div class="col-xs-3">
                        <select id="For_Cod2" name="For_Cod" class="form-control input-xs readOnly" data-trigger="" onchange="checkCuentaPago2();" required="">
                            <?php foreach ($rs_forma as $row) {
                                echo "<option value='$row[For_Cod]' " . ($row['For_Des'] == 'Contado' ? "selected=''" : '') . ">$row[For_Des]</option>";
                            } ?>
                        </select>
                    </div>
                    <?php if ($configs['Cof_Con'] == 'S') { ?>
                        <label class="col-xs-2 control-label label-xs required">Cuenta:</label>
                        <div class="col-xs-5">
                            <select id="Pag_Pld2" name="Pag_Pld" class="form-control input-xs readOnly" required=""></select>
                        </div>
                    <?php } ?>
                </div>
                <div class="form-group pagoCredito2" style="display: none;">
                    <label class="col-xs-2 control-label label-xs required">Vencimiento:</label>
                    <div class="col-xs-3">
                        <input id="Cpc_Ven2" name="Cpc_Ven" type="text" class="form-control input-xs datepickers" />
                    </div>
                    <label class="col-xs-2 control-label label-xs">Observaci&oacute;n:</label>
                    <div class="col-xs-5">
                        <textarea name="Cpc_Obs" class="form-control input-xs"></textarea>
                    </div>
                </div>
            </fieldset>
            <div class="center">
                <div clas="separator"></div>
                <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            </div>
        </form>
    </div>


    <!-- DIALOGO OBSERVACION -->
    <div id="docDetaObservacion" title="Documento">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Observacion:</legend>
            <div class="form-horizontal normal" style="padding: 0 4px;">
                <div class="form-group">
                    <input type="text" id="Vet_Codigo" name="Vet_Cod" style="display: none;">
                    <textarea class="form-control" id="Vet_Observacion" name="Vet_Obs" rows="5" style="resize: none"></textarea>
                    <br>
                    <div class="col text-center">
                        <button id='btnEditarObservacion' class="btn-sm btn-success">Guardar </button>
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
                        width: 130
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
                width: 550,
                responsive: false,
                caption: null,
                rownumbers: false
            }), true);
        });

        $("#Bodega_Cod").val($('#Bod_Cod').val());
        $('#Bod_Cod').change(function() {
            $("#Bodega_Cod").val($('#Bod_Cod').val());
        });

        // $('#detaDocu').createGrid($.extend(opts,{height:'auto',width:550,responsive:false,caption:null,rownumbers:false}),true);
    </script>



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

    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <script>
        $.clearValidate();
        $('#destinoCreateDialog').createDialog({
            icon: 'plus',
            width: 600,
            height: 450
        });
    </script>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
    <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />
</BODY>

</HTML>